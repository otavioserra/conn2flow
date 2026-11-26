<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Conn2Flow\Cli\Support\SshRemoteTransport;
use Throwable;

/**
 * req-028 / BATCH-023 — publicação dos assets estáticos em `public_html/dist/`.
 *
 * O código PHP do gestor mora fora da pasta pública. Sem esta publicação, TODA imagem, fonte, CSS
 * e JS do painel atravessa o front-controller e o controlador `arquivo-estatico` — um processo
 * PHP-FPM por arquivo, com sessão, roteamento e leitura de banco antes de um `readfile()`. Depois
 * dela, o Nginx e o Apache entregam o arquivo direto do disco.
 *
 * Três decisões sustentam o comando:
 *
 * 1. **O caminho dentro de `dist/` é o caminho da URL.** `recursos_dist_mapear_fonte()` é a fonte
 *    única do contrato e é compartilhada com o runtime, então publicação e resolução não podem
 *    divergir.
 * 2. **Só extensões de asset web são publicadas.** A lista é de permissão, não de bloqueio: um
 *    arquivo novo de tipo inesperado não vaza para a pasta pública por descuido.
 * 3. **A publicação é idempotente e verificável.** O manifesto guarda o SHA-1 do conteúdo
 *    publicado; rodar de novo sem mudanças não reescreve nada, e o hash é o token de cache busting
 *    emitido nas tags HTML.
 */
final class AssetsPublishCommand implements CommandInterface
{
    private string $rootPath;
    private ?string $projectGestorPath = null;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'assets:publish';
    }

    public function getDescription(): string
    {
        return 'Publish processed static assets into public_html/dist/ so the web server serves them without PHP.';
    }

    public function getAliases(): array
    {
        return ['assets:publicar', 'dist:publish'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f assets:publish [--project=ID] [--public=PATH] [--dev] [--clean] [--dry-run]\n\n" .
               "Copies the core and module assets to <public>/dist/, keeping the published path\n" .
               "identical to the public URL, and writes <public>/dist/.manifest.json with the SHA-1\n" .
               "of every published file (used as the cache-busting token in the HTML tags).\n\n" .
               "Options:\n" .
               "  --project=ID   Resolve the DocumentRoot from environment.json (devProjects). With\n" .
               "                 deploy_mode=\"ssh\", publishes locally and rsyncs into ssh_public_path.\n" .
               "  --public=PATH  DocumentRoot to publish into. Defaults to PUBLIC_PATH in gestor/.env.\n" .
               "  --dev          Publish the authored JavaScript instead of the .min.js derivative.\n" .
               "  --clean        Remove obsolete managed assets locally and mirror staging over SSH.\n" .
               "  --dry-run      Report what would be published, writing nothing.\n" .
               "  --opcional     Used by pipelines: a missing DocumentRoot is reported, not an error.\n" .
               "  --confirmar-remoto  Required to push dist/ to a VM over SSH.\n" .
               "  --simular-remoto    Print the rsync command line without executing it.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Publicação de assets estáticos (dist/)');

        require_once dirname(__DIR__, 3) . '/gestor/bibliotecas/dist-publicacao.php';
        $this->projectGestorPath = null;

        $remoto = $this->resolverAlvoDeProjeto($input, $output);
        if ($remoto === false) {
            return $input->hasOption('opcional') ? 0 : 1;
        }

        // Publicação remota: o `dist/` é montado numa área de staging local e enviado por rsync,
        // para que a etapa continue idempotente e verificável antes de tocar na VM.
        $publicPath = $remoto !== null
            ? rtrim(str_replace('\\', '/', $remoto['staging']), '/') . '/'
            : $this->resolverPublicPath($input, $output);

        if ($publicPath === null) {
            return $input->hasOption('opcional') ? 0 : 1;
        }

        $distPath = $publicPath . 'dist' . DIRECTORY_SEPARATOR;
        $dryRun = $input->hasOption('dry-run');
        $preferirMinificado = !$input->hasOption('dev');

        $output->writeln('  DocumentRoot: ' . $publicPath);
        $output->writeln('  Destino:      ' . $distPath);
        if ($dryRun) {
            $output->writeln('  Modo:         simulação (nada será escrito)');
        }
        $output->writeln('');

        $resumo = dist_publicar_completo($this->rootPath . '/gestor', $distPath, [
            'projeto' => $this->projectGestorPath,
            'preferir_minificado' => $preferirMinificado,
            'limpar' => $input->hasOption('clean'),
            'simular' => $dryRun,
            'relatar' => static function (string $acao, string $caminho) use ($output, $dryRun): void {
                if ($acao === 'falha') {
                    $output->warning('Falha ao publicar: ' . $caminho);
                } elseif ($dryRun) {
                    $output->writeln('  [dry-run] ' . $acao . ' ' . $caminho);
                }
            },
        ]);

        $output->writeln(sprintf(
            'publicados: %d | atualizados: %d | ja atuais: %d | removidos: %d | falhas: %d | total: %.1f KB',
            $resumo['publicados'],
            $resumo['atualizados'],
            $resumo['inalterados'],
            $resumo['removidos'],
            $resumo['falhas'],
            $resumo['bytes'] / 1024
        ));

        if ($resumo['falhas'] > 0) {
            $output->error('A publicação encontrou falhas em ' . $distPath);
            return 1;
        }

        if (!$dryRun) {
            $output->success('Assets publicados em ' . $distPath);
        }

        if ($remoto !== null) {
            return $this->enviarParaVm($remoto, $distPath, $input, $output);
        }

        return 0;
    }

    /**
     * req-050 — resolve o destino quando o alvo é um projeto do `environment.json`.
     *
     * Sem isto, o pipeline `project:update-all` chamava esta etapa sem alvo, ela lia o
     * `PUBLIC_PATH` do `.env` do CORE e publicava o `dist/` do projeto no DocumentRoot de OUTRO
     * site — ou, na ausência do `.env`, simplesmente pulava a etapa.
     *
     * @return array{staging: string, transport: SshRemoteTransport}|null|false
     *         null = alvo local resolvido normalmente; false = erro já reportado.
     */
    private function resolverAlvoDeProjeto(InputInterface $input, OutputInterface $output): array|null|false
    {
        $projectId = (string)($input->getOption('project') ?? '');
        if ($projectId === '') {
            return null;
        }

        try {
            $resolvido = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return false;
        }

        // Assets from the project override the core just as the deploy overlay does.
        $this->projectGestorPath = $resolvido['gestorPath'];
        $ssh = is_array($resolvido['ssh'] ?? null) ? $resolvido['ssh'] : null;
        if ($ssh === null) {
            return null;
        }

        $config = is_array($resolvido['config'] ?? null) ? $resolvido['config'] : [];

        try {
            $transport = new SshRemoteTransport($ssh, $config);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return false;
        }

        if ($transport->publicPath() === null) {
            $output->writeln("  Projeto '{$projectId}' usa deploy_mode \"ssh\" mas não declara "
                . 'ssh_public_path: publicação ignorada, os assets seguem sendo servidos pelo '
                . 'arquivo-estatico.');
            return false;
        }

        // Alcançar outra máquina nunca é implícito, mesmo com local=true.
        if (!$input->hasOption('confirmar-remoto') && !$input->hasOption('simular-remoto')) {
            $output->error(
                "Projeto '{$projectId}' publica em {$transport->describe()}. Enviar assets para a VM "
                . 'exige --confirmar-remoto (ou --simular-remoto para apenas ver o comando).'
            );
            return false;
        }

        $staging = $this->rootPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR
            . 'assets-publish' . DIRECTORY_SEPARATOR . $projectId . DIRECTORY_SEPARATOR;

        return ['staging' => $staging, 'transport' => $transport];
    }

    /**
     * @param array{staging: string, transport: SshRemoteTransport} $remoto
     */
    private function enviarParaVm(array $remoto, string $distPath, InputInterface $input, OutputInterface $output): int
    {
        $transport = $remoto['transport'];
        $destino = $transport->publicPath() . '/dist';

        $mkdir = $transport->buildEnsureDirectoryCommand($destino);
        $rsync = $transport->buildRsyncCommand($distPath, $destino, $input->hasOption('clean'), ['/.publish.lock']);
        if (PHP_OS_FAMILY === 'Windows') {
            // Reuse the pipeline's paired cwRsync/OpenSSH and Cygwin path handling.
            $script = $this->rootPath . '/ai-workspace/en/scripts/projects/publish-project-assets.sh';
            $rsync = 'bash ' . escapeshellarg($script) . ' '
                . escapeshellarg((new ProjectEnvironmentResolver($this->rootPath))->getEnvironmentJsonPath()) . ' '
                . escapeshellarg((string)$input->getOption('project')) . ' '
                . escapeshellarg($distPath) . ($input->hasOption('clean') ? ' --clean' : '');
        }

        $output->writeln('');
        $output->writeln('  destino remoto: ' . $transport->target() . ':' . $destino);
        $output->writeln('  preparar:       ' . $mkdir);
        $output->writeln('  enviar:         ' . $rsync);

        if ($input->hasOption('simular-remoto') || $input->hasOption('dry-run')) {
            $output->info('Simulação: os comandos acima não foram executados.');
            return 0;
        }

        foreach ([$mkdir, $rsync] as $comando) {
            $codigo = $this->runShellCommand($comando, $output);
            if ($codigo !== 0) {
                $output->error("Publicação remota falhou (código {$codigo}). Os assets locais "
                    . 'foram gerados em ' . $distPath . ' e podem ser reenviados.');
                return 1;
            }
        }

        $output->success('Assets publicados em ' . $transport->target() . ':' . $destino);
        return 0;
    }

    private function runShellCommand(string $comando, OutputInterface $output): int
    {
        $process = proc_open($comando, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error('Não foi possível iniciar o processo de transporte.');
            return 1;
        }

        fclose($pipes[0]);

        while ($linha = fgets($pipes[1])) {
            $output->write($linha);
        }
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $codigo = proc_close($process);

        if ($codigo !== 0 && is_string($stderr) && trim($stderr) !== '') {
            $output->error(trim($stderr));
        }

        return $codigo;
    }

    /**
     * DocumentRoot de publicação: opção explícita, `PUBLIC_PATH` do `.env` ou nada.
     * Nunca adivinhamos o diretório: escrever na pasta errada de um servidor compartilhado é pior
     * do que não publicar.
     */
    private function resolverPublicPath(InputInterface $input, OutputInterface $output): ?string
    {
        $declarado = (string)($input->getOption('public') ?? '');

        if ($declarado === '') {
            $declarado = (string)($this->lerEnv('PUBLIC_PATH') ?? '');
        }

        if ($declarado === '') {
            // Chamado de dentro de um pipeline, a ausência de DocumentRoot não é erro: a instalação
            // simplesmente não usa `dist/` e todo asset segue pelo controlador `arquivo-estatico`.
            if ($input->hasOption('opcional')) {
                $output->writeln('  DocumentRoot não declarado (PUBLIC_PATH ausente): publicação '
                    . 'ignorada, os assets seguem sendo servidos pelo arquivo-estatico.');
                return null;
            }

            $output->error('DocumentRoot não informado. Use --public=/caminho/public_html '
                . 'ou declare PUBLIC_PATH no gestor/.env.');
            return null;
        }

        $normalizado = rtrim(str_replace('\\', '/', $declarado), '/');
        if ($normalizado === '' || !is_dir($normalizado)) {
            $output->error("DocumentRoot inexistente: {$declarado}");
            return null;
        }

        return $normalizado . '/';
    }

    /** Lê uma chave do `gestor/.env` sem carregar o bootstrap completo do gestor. */
    private function lerEnv(string $chave): ?string
    {
        $envPath = $this->rootPath . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath)) {
            return null;
        }

        $linhas = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($linhas as $linha) {
            if (strpos(trim($linha), '#') === 0 || strpos($linha, '=') === false) {
                continue;
            }
            [$nome, $valor] = explode('=', $linha, 2);
            if (trim($nome) !== $chave) {
                continue;
            }

            return trim(trim(trim($valor), '"'), "'");
        }

        return null;
    }

}
