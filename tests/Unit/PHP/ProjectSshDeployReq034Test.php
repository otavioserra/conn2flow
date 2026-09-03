<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Support/ProjectEnvironmentResolver.php';

/**
 * REQ-034 / BATCH-155 — transporte SSH do pipeline de projeto.
 *
 * O que estes testes protegem, em ordem de risco:
 *
 * 1. O destino do rsync. `deploy_mode: "ssh"` manda arquivos para uma máquina que não é esta;
 *    um caminho relativo cairia no home da conta SSH e "/" alcançaria a raiz do convidado.
 *    As guardas são recusas explícitas, não confiança no servidor.
 * 2. A citação do comando remoto. `ssh` concatena os argumentos e o servidor os entrega ao
 *    shell — interpolar valor do environment.json ali seria execução remota arbitrária.
 * 3. O modo local intacto. Projetos Docker e bare-metal não declaram `deploy_mode`; qualquer
 *    regressão neles não apareceria no Lab, só no ambiente de quem já funcionava.
 */
final class ProjectSshDeployReq034Test extends TestCase
{
    private static function repoRoot(): string
    {
        return dirname(CONN2FLOW_GESTOR_ROOT);
    }

    private static function scriptsRoot(): string
    {
        return self::repoRoot() . DIRECTORY_SEPARATOR . 'ai-workspace'
            . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . 'scripts';
    }

    private static function lib(): string
    {
        return self::scriptsRoot() . DIRECTORY_SEPARATOR . 'lib'
            . DIRECTORY_SEPARATOR . 'project-transport.sh';
    }

    private static function conteudo(string $caminho): string
    {
        self::assertFileExists($caminho);
        return (string) file_get_contents($caminho);
    }

    // ===================== 1. Biblioteca de transporte =====================

    public function testBibliotecaDeTransporteExisteEEhSintaticamenteValida(): void
    {
        $lib = self::lib();
        self::assertFileExists($lib);

        // `bash -n` só existe onde há bash; no Windows o Git Bash acompanha o repositório.
        $bash = self::localizarBash();
        if ($bash === null) {
            self::markTestSkipped('bash não disponível para checagem de sintaxe.');
        }

        $saida = [];
        $codigo = 0;
        exec(escapeshellarg($bash) . ' -n ' . escapeshellarg($lib) . ' 2>&1', $saida, $codigo);
        self::assertSame(0, $codigo, implode(PHP_EOL, $saida));
    }

    public function testCaminhoRemotoRelativoOuRaizEhRecusado(): void
    {
        $conteudo = self::conteudo(self::lib());

        self::assertStringContainsString('ssh_target_path cannot be the filesystem root', $conteudo);
        self::assertStringContainsString('ssh_target_path must be an absolute path', $conteudo);
    }

    public function testConfiguracaoSshIncompletaFalhaEmVezDeSincronizarPelaMetade(): void
    {
        $conteudo = self::conteudo(self::lib());

        self::assertStringContainsString('requires ssh_host, ssh_user and ssh_target_path', $conteudo);
        self::assertStringContainsString('ssh_port must be numeric', $conteudo);
        self::assertStringContainsString('Unsupported deploy_mode ', $conteudo);
    }

    public function testConexaoNaoInterativaEhObrigatoria(): void
    {
        // Sem BatchMode, uma chave ausente vira prompt de senha e o pipeline fica pendurado
        // até o timeout do chamador, sem dizer o motivo.
        $conteudo = self::conteudo(self::lib());
        self::assertStringContainsString('-o BatchMode=yes', $conteudo);
        self::assertStringContainsString('-o ConnectTimeout=', $conteudo);
    }

    public function testComandoRemotoEhCitadoArgumentoAArgumento(): void
    {
        $conteudo = self::conteudo(self::lib());

        self::assertStringContainsString("remote_cmd=\$(printf '%q ' \"\$@\")", $conteudo);
        self::assertStringContainsString('cd $(printf \'%q\' "$PT_REMOTE_PATH")', $conteudo);
    }

    public function testPosseEhDevolvidaAoDonoDoDocroot(): void
    {
        // rsync com sudo cria arquivo root:root; sem o chown o pool PHP-FPM do tenant
        // perde a leitura do que acabou de ser publicado.
        $conteudo = self::conteudo(self::lib());
        self::assertStringContainsString('project_transport_finalize', $conteudo);
        self::assertStringContainsString('sudo chown -R', $conteudo);
        self::assertStringContainsString('ssh_chown must be ', $conteudo);
    }

    public function testValorHostilEmSshRunAsEhRecusado(): void
    {
        $conteudo = self::conteudo(self::lib());
        self::assertStringContainsString('ssh_run_as must be a plain user name', $conteudo);
    }

    // ===================== 2. Scripts do pipeline =====================

    /** @return list<array{0: string}> */
    public static function scriptsDoPipeline(): array
    {
        return [
            'sync-core'   => ['projects' . DIRECTORY_SEPARATOR . 'sync-core-to-project.sh'],
            'sync-files'  => ['projects' . DIRECTORY_SEPARATOR . 'synchronize-project.sh'],
            'sync-db'     => ['dev-environment' . DIRECTORY_SEPARATOR . 'updates-manager-database.sh'],
        ];
    }

    /** @dataProvider scriptsDoPipeline */
    public function testScriptDoPipelineCarregaOTransporteCompartilhado(string $relativo): void
    {
        $conteudo = self::conteudo(self::scriptsRoot() . DIRECTORY_SEPARATOR . $relativo);

        self::assertStringContainsString('/../lib/project-transport.sh"', $conteudo);
        self::assertStringContainsString('project_transport_resolve', $conteudo);
    }

    public function testRsyncDosDoisScriptsAceitaAsOpcoesDeTransporte(): void
    {
        foreach (['sync-core-to-project.sh', 'synchronize-project.sh'] as $arquivo) {
            $conteudo = self::conteudo(
                self::scriptsRoot() . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . $arquivo
            );

            // Em transporte local o array é vazio e a linha é exatamente a de antes.
            self::assertStringContainsString('"${PT_RSYNC_OPTS[@]}"', $conteudo, $arquivo);
        }
    }

    public function testSyncFilesPublicaOverlayDistribuidoIrmaoPeloMesmoTransporte(): void
    {
        $conteudo = self::conteudo(
            self::scriptsRoot() . DIRECTORY_SEPARATOR . 'projects'
            . DIRECTORY_SEPARATOR . 'synchronize-project.sh'
        );

        self::assertStringContainsString('DISTRIBUTED_OVERLAY_SOURCE="$(dirname "$ORIGEM")/gestor-distribuido"', $conteudo);
        self::assertStringContainsString('DISTRIBUTED_OVERLAY_REMOTE_PATH="$(dirname "$PT_REMOTE_PATH")/gestor-distribuido"', $conteudo);
        self::assertStringContainsString('run_project_rsync "$DISTRIBUTED_OVERLAY_SOURCE" "$DISTRIBUTED_OVERLAY_DEST"', $conteudo);
        self::assertStringContainsString('project_transport_finalize_path "$DISTRIBUTED_OVERLAY_REMOTE_PATH"', $conteudo);
    }

    public function testCaminhosComplementaresRemotosMantemGuardasDeSeguranca(): void
    {
        $conteudo = self::conteudo(self::lib());

        self::assertStringContainsString('project_transport_ensure_remote_path', $conteudo);
        self::assertStringContainsString('project_transport_finalize_path', $conteudo);
        self::assertStringContainsString('refusing to create the filesystem root', $conteudo);
        self::assertStringContainsString('refusing to chown the filesystem root', $conteudo);
    }

    public function testModoLocalContinuaLendoTargetEPathTests(): void
    {
        foreach (['sync-core-to-project.sh', 'synchronize-project.sh'] as $arquivo) {
            $conteudo = self::conteudo(
                self::scriptsRoot() . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . $arquivo
            );

            self::assertStringContainsString('.target // empty', $conteudo, $arquivo);
            self::assertStringContainsString('.path_tests // empty', $conteudo, $arquivo);
        }
    }

    public function testAtualizadorDeBancoGanhaModoSshSemPerderDockerEHost(): void
    {
        $conteudo = self::conteudo(
            self::scriptsRoot() . DIRECTORY_SEPARATOR . 'dev-environment'
            . DIRECTORY_SEPARATOR . 'updates-manager-database.sh'
        );

        self::assertStringContainsString('EXECUTION_MODE="ssh"', $conteudo);
        self::assertStringContainsString(
            'project_transport_remote_exec php "$PHP_SCRIPT" "${PHP_ARGS[@]}"',
            $conteudo
        );

        // Os dois modos anteriores continuam intactos.
        self::assertStringContainsString('(cd "$PATH_HOST" && php "$PHP_SCRIPT" "${PHP_ARGS[@]}")', $conteudo);
        self::assertStringContainsString('docker exec conn2flow-app php "$PHP_SCRIPT" "${PHP_ARGS[@]}"', $conteudo);
    }

    public function testCaminhoRemotoDoAtualizadorEhRelativoAoCwdDoGestor(): void
    {
        // O bootstrap do config.php exige a raiz do Gestor como diretório de trabalho (req-152);
        // o `project_transport_remote_exec` entra nela com `cd` antes de chamar o PHP.
        $conteudo = self::conteudo(
            self::scriptsRoot() . DIRECTORY_SEPARATOR . 'dev-environment'
            . DIRECTORY_SEPARATOR . 'updates-manager-database.sh'
        );

        self::assertStringContainsString(
            'PHP_SCRIPT="controladores/atualizacoes/atualizacoes-banco-de-dados.php"',
            $conteudo
        );
    }

    // ===================== 3. Resolvedor de projeto =====================

    public function testResolvedorExpoeODeployModeEOAlvoSsh(): void
    {
        $raiz = $this->criarAmbienteTemporario([
            'name' => 'SSH',
            'path' => './gestor-fonte',
            'url' => 'https://exemplo.local/',
            'deploy_mode' => 'ssh',
            'ssh_host' => '192.0.2.10',
            'ssh_user' => 'deploy',
            'ssh_port' => 2222,
            'ssh_target_path' => '/home/tenant/web/exemplo.local/conn2flow-gestor/',
            'ssh_run_as' => 'tenant',
            'ssh_sudo' => true,
        ]);

        $resolvido = (new ProjectEnvironmentResolver($raiz))->resolve('projeto-ssh');

        self::assertSame('ssh', $resolvido['deployMode']);
        self::assertIsArray($resolvido['ssh']);
        self::assertSame('deploy', $resolvido['ssh']['user']);
        self::assertSame('192.0.2.10', $resolvido['ssh']['host']);
        self::assertSame(2222, $resolvido['ssh']['port']);
        self::assertSame('tenant', $resolvido['ssh']['runAs']);
        self::assertTrue($resolvido['ssh']['sudo']);

        // A barra final entraria duplicada na linha do rsync.
        self::assertSame('/home/tenant/web/exemplo.local/conn2flow-gestor', $resolvido['ssh']['path']);
    }

    public function testProjetoSemDeployModeContinuaLocalESemAlvoSsh(): void
    {
        $raiz = $this->criarAmbienteTemporario([
            'name' => 'Local',
            'path' => './gestor-fonte',
            'url' => 'http://localhost/',
        ]);

        $resolvido = (new ProjectEnvironmentResolver($raiz))->resolve('projeto-ssh');

        self::assertSame('local', $resolvido['deployMode']);
        self::assertNull($resolvido['ssh']);
    }

    public function testDeployModeSshSemEnderecoFalhaNoResolvedor(): void
    {
        $raiz = $this->criarAmbienteTemporario([
            'name' => 'SSH incompleto',
            'path' => './gestor-fonte',
            'url' => 'https://exemplo.local/',
            'deploy_mode' => 'ssh',
            'ssh_host' => '192.0.2.10',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ssh_user/');

        (new ProjectEnvironmentResolver($raiz))->resolve('projeto-ssh');
    }

    // ===================== 4. Bootstrap CLI do Gestor =====================

    public function testBootstrapCliNaoSobrescreveOHostDeclaradoPeloChamador(): void
    {
        // O sufixo de cookie/sessão nasce de basename($domainBase), que vem do SERVER_NAME.
        // Fixá-lo em 'localhost' fazia toda sessão gerada por CLI para outro host sair com
        // nome de cookie que o site jamais lê: o cookie chega e é ignorado, e a tela apenas
        // redireciona para /signin. Mesma família do que o req-032 corrigiu no ramo do cron.
        $config = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'config.php');

        self::assertMatchesRegularExpression(
            '/if \(!isset\(\$_SERVER\[\'SERVER_NAME\'\]\) \|\| trim\(\(string\)\$_SERVER\[\'SERVER_NAME\'\]\) === \'\'\) \{\s*\$_SERVER\[\'SERVER_NAME\'\] = \'localhost\';/',
            $config,
            'o ramo CLI precisa tratar localhost como PADRÃO, não como imposição'
        );

        // O padrão continua existindo para quem não declara host algum (Phinx, scripts).
        self::assertStringContainsString("\$_SERVER['SERVER_NAME'] = 'localhost';", $config);
    }

    public function testGeradorDeCookieSabeFalarComUmGestorRemoto(): void
    {
        $comando = (string) file_get_contents(
            self::repoRoot() . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'AuthCookieCommand.php'
        );

        self::assertStringContainsString('generateOverSsh', $comando);

        // Sem isto, o comando exigia um config.php local e nenhuma rota autenticada de projeto
        // publicado por SSH podia ser homologada.
        self::assertStringContainsString('if ($ssh === null && !is_file($configFile))', $comando);

        // O gerador e o JSON carregam credencial de sessão: não podem sobrar em /tmp da VM.
        self::assertStringContainsString('sudo rm -f ', $comando);
    }

    /** @param array<string, mixed> $projeto */
    private function criarAmbienteTemporario(array $projeto): string
    {
        $raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req034-' . bin2hex(random_bytes(6));
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

    /** @var list<string> */
    private array $paraRemover = [];

    protected function tearDown(): void
    {
        foreach ($this->paraRemover as $raiz) {
            self::removerArvore($raiz);
        }
        $this->paraRemover = [];
    }

    private static function removerArvore(string $caminho): void
    {
        if (!is_dir($caminho)) {
            if (is_file($caminho)) {
                @unlink($caminho);
            }
            return;
        }

        foreach (array_diff((array) scandir($caminho), ['.', '..']) as $item) {
            self::removerArvore($caminho . DIRECTORY_SEPARATOR . $item);
        }

        @rmdir($caminho);
    }

    private static function localizarBash(): ?string
    {
        foreach (['/usr/bin/bash', '/bin/bash', 'C:\\Program Files\\Git\\bin\\bash.exe'] as $candidato) {
            if (is_file($candidato)) {
                return $candidato;
            }
        }

        return null;
    }
}
