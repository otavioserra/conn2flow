<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Conn2Flow\Cli\Support\SshRemoteTransport;
use PHPUnit\Framework\TestCase;

// O CLI tem autoloader próprio no `c2f.php`; a suíte carrega as classes de suporte diretamente.
require_once CONN2FLOW_ROOT . '/cli/src/Support/ProjectEnvironmentResolver.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/SshRemoteTransport.php';

/**
 * REQ-050 / BATCH-052 — `ssh_public_path` e execução SSH no pipeline multiprojeto.
 *
 * O que estes testes protegem, em ordem de risco:
 *
 * 1. **O destino do rsync de assets.** `assets:publish` passou a alcançar outra máquina. Um
 *    `ssh_public_path` relativo cairia no home da conta SSH e `/` alcançaria a raiz do convidado —
 *    com `--delete` isso apaga o sistema do outro lado. A recusa é aqui, não no servidor.
 * 2. **A citação do comando remoto.** `ssh` concatena os argumentos e o servidor os entrega ao
 *    shell; qualquer valor do `environment.json` interpolado ali seria execução remota arbitrária.
 * 3. **O modo local intacto.** Projetos sem `deploy_mode` continuam publicando no DocumentRoot
 *    local; uma regressão aí só apareceria no ambiente de quem já funcionava.
 */
final class ProjectSshPublicPathReq050Test extends TestCase
{
    /** @var list<string> */
    private array $paraRemover = [];

    protected function tearDown(): void
    {
        foreach ($this->paraRemover as $raiz) {
            $this->removerRecursivo($raiz);
        }
        $this->paraRemover = [];
    }

    // ===================== 1. Resolvedor =====================

    public function testResolvedorExpoeOPublicPathSemBarraFinal(): void
    {
        $raiz = $this->criarAmbienteTemporario([
            'name' => 'SSH',
            'path' => './gestor-fonte',
            'url' => 'https://exemplo.local/',
            'deploy_mode' => 'ssh',
            'ssh_host' => '192.0.2.10',
            'ssh_user' => 'deploy',
            'ssh_target_path' => '/home/tenant/web/exemplo.local/conn2flow-gestor/',
            'ssh_public_path' => '/home/tenant/web/exemplo.local/public_html/',
        ]);

        $resolvido = (new ProjectEnvironmentResolver($raiz))->resolve('projeto-ssh');

        // A barra final entraria duplicada na linha do rsync.
        self::assertSame('/home/tenant/web/exemplo.local/public_html', $resolvido['ssh']['publicPath']);
    }

    public function testPublicPathAusenteNaoQuebraOResolvedor(): void
    {
        $raiz = $this->criarAmbienteTemporario([
            'name' => 'SSH sem docroot',
            'path' => './gestor-fonte',
            'url' => 'https://exemplo.local/',
            'deploy_mode' => 'ssh',
            'ssh_host' => '192.0.2.10',
            'ssh_user' => 'deploy',
            'ssh_target_path' => '/home/tenant/web/exemplo.local/conn2flow-gestor',
        ]);

        $resolvido = (new ProjectEnvironmentResolver($raiz))->resolve('projeto-ssh');

        self::assertNull($resolvido['ssh']['publicPath']);
    }

    // ===================== 2. Transporte SSH =====================

    /** @return array{user: string, host: string, port: int, path: string, runAs: ?string, sudo: bool} */
    private static function alvo(array $sobrescritas = []): array
    {
        return array_merge([
            'user' => 'deploy',
            'host' => '192.0.2.10',
            'port' => 2222,
            'path' => '/home/tenant/web/exemplo.local/conn2flow-gestor',
            'runAs' => null,
            'sudo' => false,
        ], $sobrescritas);
    }

    public function testComandoRemotoEntraNaRaizDoGestorEEhCitadoArgumentoAArgumento(): void
    {
        $transporte = new SshRemoteTransport(self::alvo(), []);
        $comando = $transporte->buildRemoteCommand(['./c2f', 'css:rebuild', '--todos']);

        self::assertStringContainsString('-o BatchMode=yes', $comando);
        self::assertStringContainsString('-p 2222', $comando);
        self::assertComandoSshContem("cd '/home/tenant/web/exemplo.local/conn2flow-gestor'", $comando);
        self::assertComandoSshContem("'./c2f' 'css:rebuild' '--todos'", $comando);
    }

    public function testValorHostilNoComandoRemotoNaoEscapaDaCitacao(): void
    {
        $transporte = new SshRemoteTransport(self::alvo(), []);
        $valorHostil = "--id=x'; rm -rf /; #";
        $comando = $transporte->buildRemoteCommand(['./c2f', 'css:rebuild', $valorHostil]);

        // O apóstrofo é fechado, escapado e reaberto: o shell remoto vê UM argumento literal.
        self::assertSame("'--id=x'\\''; rm -rf /; #'", $transporte->posixQuote($valorHostil));

        // O único `&&` da linha é o que separa o `cd` do comando declarado pelo CLI: o `;` e o
        // `#` do valor hostil ficaram dentro das aspas simples e nunca viram sintaxe do shell.
        self::assertSame(1, substr_count($comando, '&&'));

        // O comando declarado pelo CLI termina onde o valor hostil termina: nada depois dele.
        // escapeshellarg() aplica a camada externa adequada ao shell local (Windows ou POSIX).
        $remotoEsperado = "cd " . $transporte->posixQuote('/home/tenant/web/exemplo.local/conn2flow-gestor')
            . " && " . $transporte->posixQuote('./c2f')
            . " " . $transporte->posixQuote('css:rebuild')
            . " " . $transporte->posixQuote($valorHostil);
        self::assertStringEndsWith(escapeshellarg($remotoEsperado), $comando);
    }

    public function testRunAsHostilEhRecusadoAntesDeChegarAoServidor(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ssh_run_as/');

        new SshRemoteTransport(self::alvo(['runAs' => 'tenant; rm -rf /']), []);
    }

    public function testRunAsValidoEntraComoSudoCitado(): void
    {
        $transporte = new SshRemoteTransport(self::alvo(['runAs' => 'tenant']), []);
        $comando = $transporte->buildRemoteCommand(['./c2f', 'css:rebuild']);

        self::assertComandoSshContem("sudo -u 'tenant' './c2f' 'css:rebuild'", $comando);
    }

    public function testEntrypointRemotoAceitaFormaComposta(): void
    {
        $transporte = new SshRemoteTransport(self::alvo(), ['ssh_cli_entrypoint' => 'php cli/c2f.php']);

        self::assertSame(['php', 'cli/c2f.php'], $transporte->cliEntrypointArgv());
    }

    // ===================== 3. Guardas do caminho remoto =====================

    public function testPublicPathRelativoEhRecusado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ssh_public_path must be an absolute path/');

        new SshRemoteTransport(self::alvo(), ['ssh_public_path' => 'public_html']);
    }

    public function testPublicPathNaRaizDoSistemaEhRecusado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ssh_public_path cannot be the filesystem root/');

        new SshRemoteTransport(self::alvo(), ['ssh_public_path' => '/']);
    }

    public function testPortaForaDaFaixaEhRecusada(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ssh_port/');

        new SshRemoteTransport(self::alvo(['port' => 0]), []);
    }

    // ===================== 4. Linha do rsync =====================

    public function testRsyncEnviaODistParaODocrootDaVm(): void
    {
        $transporte = new SshRemoteTransport(
            self::alvo(),
            ['ssh_public_path' => '/home/tenant/web/exemplo.local/public_html']
        );

        $comando = $transporte->buildRsyncCommand(
            'C:\\repo\\temp\\assets-publish\\projeto\\dist',
            $transporte->publicPath() . '/dist'
        );

        self::assertStringStartsWith('rsync -az ', $comando);
        self::assertStringContainsString('-o BatchMode=yes', $comando);
        self::assertStringContainsString('C:/repo/temp/assets-publish/projeto/dist/', $comando);
        self::assertStringContainsString('deploy@192.0.2.10:/home/tenant/web/exemplo.local/public_html/dist/', $comando);

        // Sem --clean o rsync não apaga: o docroot pode conter arquivos que não são nossos.
        self::assertStringNotContainsString('--delete', $comando);
    }

    public function testCleanHabilitaODeleteEOSudoElevaApenasORsyncRemoto(): void
    {
        $transporte = new SshRemoteTransport(
            self::alvo(['sudo' => true]),
            ['ssh_public_path' => '/home/tenant/web/exemplo.local/public_html']
        );

        $comando = $transporte->buildRsyncCommand('/tmp/dist', '/home/tenant/web/exemplo.local/public_html/dist', true);

        self::assertStringContainsString('--delete', $comando);
        self::assertStringContainsString('--rsync-path', $comando);
        self::assertStringContainsString('sudo rsync', $comando);
    }

    public function testDiretorioRemotoEhCriadoAntesDoPrimeiroEnvio(): void
    {
        $transporte = new SshRemoteTransport(self::alvo(), []);
        $comando = $transporte->buildEnsureDirectoryCommand('/home/tenant/web/exemplo.local/public_html/dist');

        self::assertComandoSshContem("mkdir -p '/home/tenant/web/exemplo.local/public_html/dist'", $comando);
    }

    // ===================== 5. Contratos dos comandos =====================

    public function testAssetsPublishAceitaProjetoEExigeConfirmacaoRemota(): void
    {
        $fonte = self::conteudo(self::cliRoot() . '/Commands/AssetsPublishCommand.php');

        self::assertStringContainsString("getOption('project')", $fonte);
        self::assertStringContainsString('SshRemoteTransport', $fonte);
        self::assertStringContainsString("hasOption('confirmar-remoto')", $fonte);
        self::assertStringContainsString('buildRsyncCommand', $fonte);
    }

    public function testCssRebuildDisparaNaVmEmVezDeApenasRecusar(): void
    {
        $fonte = self::conteudo(self::cliRoot() . '/Commands/CssRebuildCommand.php');

        self::assertStringContainsString('regenerarViaSsh', $fonte);
        self::assertStringContainsString('buildRemoteCommand', $fonte);
        self::assertStringContainsString('command -v c2f', $fonte);
        self::assertStringContainsString('controladores/agents/arquitetura/css-regenerar.php', $fonte);
        self::assertStringContainsString("'--gestor=.'", $fonte);

        // O disparo remoto nunca é implícito: a guarda de --confirmar-remoto precede a chamada.
        self::assertMatchesRegularExpression(
            "/getOption\('confirmar-remoto'\)[\s\S]{0,600}?return \\\$this->regenerarViaSsh\(/",
            $fonte
        );
    }

    public function testPipelineDeclaraOProjetoNaPublicacaoDeAssets(): void
    {
        $fonte = self::conteudo(self::cliRoot() . '/Commands/ProjectUpdateAllCommand.php');

        // Sem o id, a etapa 8/8 lia o PUBLIC_PATH do CORE e publicava no docroot de outro site.
        self::assertStringContainsString("'--project=' . \$project", $fonte);
    }

    public function testTemplateDoEnvironmentDocumentaOPublicPath(): void
    {
        $template = dirname(CONN2FLOW_GESTOR_ROOT) . '/dev-environment/templates/environment/environment.json';
        $dados = json_decode(self::conteudo($template), true);

        self::assertIsArray($dados);
        $projeto = $dados['devProjects']['project_ID'] ?? null;
        self::assertIsArray($projeto);

        foreach (['deploy_mode', 'ssh_user', 'ssh_host', 'ssh_target_path', 'ssh_public_path'] as $chave) {
            self::assertArrayHasKey($chave, $projeto, "{$chave} deve estar documentado no template");
        }

        self::assertSame('local', $projeto['deploy_mode']);
    }

    // ===================== Infraestrutura =====================

    private static function cliRoot(): string
    {
        return dirname(CONN2FLOW_GESTOR_ROOT) . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src';
    }

    private static function conteudo(string $caminho): string
    {
        self::assertFileExists($caminho);
        return (string) file_get_contents($caminho);
    }

    private static function assertComandoSshContem(string $trechoRemoto, string $comando): void
    {
        // escapeshellarg() envolve o comando remoto com aspas duplas no Windows e, em POSIX,
        // reabre as aspas simples internas como '\''. Normalizar só essa camada externa mantém
        // as asserções de conteúdo iguais nos dois runners sem enfraquecer os testes de injeção.
        self::assertStringContainsString($trechoRemoto, str_replace("'\\''", "'", $comando));
    }

    /** @param array<string, mixed> $projeto */
    private function criarAmbienteTemporario(array $projeto): string
    {
        $raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req050-' . bin2hex(random_bytes(6));
        $dados = $raiz . DIRECTORY_SEPARATOR . 'dev-environment' . DIRECTORY_SEPARATOR . 'data';
        $fonte = $raiz . DIRECTORY_SEPARATOR . 'gestor-fonte';

        mkdir($dados, 0777, true);
        mkdir($fonte, 0777, true);
        file_put_contents($fonte . DIRECTORY_SEPARATOR . 'config.php', "<?php\n");

        file_put_contents(
            $dados . DIRECTORY_SEPARATOR . 'environment.json',
            (string) json_encode(['devProjects' => ['projeto-ssh' => $projeto]], JSON_PRETTY_PRINT)
        );

        $this->paraRemover[] = $raiz;

        return $raiz;
    }

    private function removerRecursivo(string $caminho): void
    {
        if (!file_exists($caminho)) {
            return;
        }

        if (is_file($caminho) || is_link($caminho)) {
            @unlink($caminho);
            return;
        }

        foreach (scandir($caminho) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $this->removerRecursivo($caminho . DIRECTORY_SEPARATOR . $item);
        }

        @rmdir($caminho);
    }
}
