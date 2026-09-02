<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * req-141 / CR-002 — regenera o CSS derivado a partir do HTML EFETIVO do banco.
 *
 * O `resources:sync` compila os arquivos de `resources/`, mas o runtime serve o HTML do banco, e
 * tudo que nasce no editor online vive só lá. Este comando fecha o ciclo, compilando contra o HTML
 * que é realmente entregue e carimbando a procedência do resultado.
 */
final class CssRebuildCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'css:rebuild';
    }

    public function getDescription(): string
    {
        return 'Recompile derived CSS from the HTML stored in the database and stamp its provenance.';
    }

    public function getAliases(): array
    {
        return ['css:regenerar', 'css:regen'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f css:rebuild [--project=ID] [--gestor=PATH] [--tipo=T] [--id=ID]\n" .
               "                       [--limite=N] [--todos] [--dry-run]\n\n" .
               "Compiles Tailwind against the HTML that the runtime actually serves (the database),\n" .
               "writes css_precompiled and stamps css_source_hash. Without --todos, only stale\n" .
               "resources are rebuilt. Requires the Tailwind CLI (node_modules).\n\n" .
               "Options:\n" .
               "  --project=ID  Resolve the gestor path from environment.json (devProjects).\n" .
               "  --gestor=PATH Use this gestor path directly.\n" .
               "  --tipo=T      Restrict to one table (paginas, layouts, componentes, templates).\n" .
               "  --id=ID       Restrict to a single resource id.\n" .
               "  --limite=N    Stop after N recompilations.\n" .
               "  --todos       Rebuild every resource, not only the stale ones.\n" .
               "  --dry-run     Compile and report, writing nothing.
" .
               "  --confirmar-remoto  Required when the project has local=false in environment.json.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $gestorPath = (string)($input->getOption('gestor') ?? '');
        $projectId = (string)($input->getOption('project') ?? '');
        $envFile = '';

        if ($gestorPath === '' && $projectId !== '') {
            try {
                $resolvido = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
                $gestorPath = (string)$resolvido['gestorPath'];
                $envFile = (string)($resolvido['envFile'] ?? '');
            } catch (Throwable $e) {
                $output->error($e->getMessage());
                return 1;
            }

            // `local` no environment.json é a autoridade sobre "posso mexer sem perguntar?".
            // Este comando REESCREVE o CSS de milhares de registros, e projetos de teste e de
            // produção compartilham o mesmo mirror — só a configuração os distingue. Uma checagem
            // declarativa não depende de o agente lembrar qual identificador é qual.
            $config = is_array($resolvido['config'] ?? null) ? $resolvido['config'] : [];
            $ehLocal = filter_var($config['local'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $output->write("  projeto: {$projectId} | local: " . ($ehLocal ? 'true' : 'false')
                . ' | url: ' . (string)($resolvido['accessUrl'] ?? '?') . PHP_EOL);

            // Projeto publicado por SSH: o `.env`, o banco e o CSS derivado estão na VM, e este
            // comando só sabe operar sobre um Gestor no disco local. Dizer isso aqui evita que o
            // agente PHP falhe adiante com "ERRO: .env do gestor nao encontrado", que aponta para
            // um arquivo ausente quando o que falta é o transporte.
            $ssh = is_array($resolvido['ssh'] ?? null) ? $resolvido['ssh'] : null;
            if ($ssh !== null && !is_file($envFile)) {
                $output->error(
                    "Projeto '{$projectId}' usa deploy_mode \"ssh\": o Gestor em execução está em "
                    . "{$ssh['user']}@{$ssh['host']}:{$ssh['path']} e não há `.env` no repositório "
                    . 'de autoria para regenerar o CSS derivado a partir daqui. Rode o comando na '
                    . 'VM, ou informe --gestor=/caminho/local com um Gestor configurado.'
                );
                return 1;
            }

            if (!$ehLocal && !$input->getOption('confirmar-remoto')) {
                $output->error(
                    "Projeto '{$projectId}' está marcado como local=false em environment.json ("
                    . (string)($resolvido['accessUrl'] ?? '?') . '). '
                    . 'Este comando reescreve CSS em massa. Peça autorização ao operador e use '
                    . '--confirmar-remoto para prosseguir.'
                );
                return 1;
            }
        }

        if ($gestorPath === '') {
            // Sem projeto declarado, o alvo é o ambiente de TESTE do sistema — que é para onde o
            // `manager:update-all` sincroniza. O `gestor/` do repositório é código-fonte: não tem
            // `.env` nem banco, então apontar para lá faria a etapa falhar sempre.
            $ambienteSistema = $this->rootPath . DIRECTORY_SEPARATOR . 'dev-environment'
                . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sites'
                . DIRECTORY_SEPARATOR . 'localhost' . DIRECTORY_SEPARATOR . 'conn2flow-gestor';

            $gestorPath = is_dir($ambienteSistema . DIRECTORY_SEPARATOR . 'autenticacoes')
                ? $ambienteSistema
                : $this->rootPath . DIRECTORY_SEPARATOR . 'gestor';
        }

        $scriptPath = $this->rootPath . '/gestor/controladores/agents/arquitetura/css-regenerar.php';
        if (!file_exists($scriptPath)) {
            $output->error("CSS rebuild script not found at: {$scriptPath}");
            return 1;
        }

        $cmd = [PHP_BINARY, $scriptPath, '--gestor=' . $gestorPath];
        if ($envFile !== '') {
            $cmd[] = '--env=' . $envFile;
        }
        foreach (['tipo', 'id', 'limite'] as $opcao) {
            if ($input->getOption($opcao)) {
                $cmd[] = '--' . $opcao . '=' . (string)$input->getOption($opcao);
            }
        }
        foreach (['todos', 'dry-run', 'confirmar-remoto'] as $flag) {
            if ($input->getOption($flag)) {
                $cmd[] = '--' . $flag;
            }
        }

        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error('Failed to spawn the CSS rebuild subprocess.');
            return 1;
        }

        fclose($pipes[0]);

        while ($line = fgets($pipes[1])) {
            $output->write($line);
        }
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 && is_string($stderr) && trim($stderr) !== '') {
            $output->error(trim($stderr));
        }

        return $exitCode === 0 ? 0 : 1;
    }
}
