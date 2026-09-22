<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** REQ-174 / BATCH-179 — hooks fazem parte do deploy de banco de qualquer projeto. */
final class ProjectHooksSyncReq174Test extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(CONN2FLOW_GESTOR_ROOT) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        self::assertFileExists($path);
        return (string) file_get_contents($path);
    }

    public function testFluxoDeBancoSincronizaHooksTambemComProject(): void
    {
        $controller = $this->source('gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php');

        self::assertStringContainsString("require_once \$BASE_PATH_DB . 'bibliotecas/hooks.php'", $controller);
        self::assertStringContainsString('function sincronizarHooksBanco(): array', $controller);
        self::assertStringContainsString(': sincronizarHooksBanco();', $controller);
        self::assertStringContainsString("!empty(\$CLI_OPTS['hooks-only'])", $controller);
        self::assertStringContainsString("\$out[\$m[1]] = \$m[2]", $controller);
        self::assertStringContainsString("'_hooks_summary'", $controller);
    }

    public function testSincronizacaoEhIdempotenteERefleteRemocao(): void
    {
        $root = dirname(CONN2FLOW_GESTOR_ROOT);
        $script = <<<'PHP'
$root = __ROOT__;
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_hooks_req174_' . uniqid('', true);
mkdir($tmp . '/modulos/demo', 0777, true);
mkdir($tmp . '/plugins', 0777, true);
mkdir($tmp . '/project/hooks', 0777, true);

$GLOBALS['rows'] = [];
function banco_escape_field($value) { return addslashes((string) $value); }
function banco_delete($table, $where) {
    $GLOBALS['rows'] = array_values(array_filter($GLOBALS['rows'], static function (array $row) use ($where): bool {
        if ($where === 'WHERE projeto=1') return empty($row['projeto']);
        if (preg_match("/modulo='([^']+)'/", $where, $match)) {
            return ($row['modulo'] ?? null) !== stripslashes($match[1]) || !empty($row['projeto']);
        }
        return true;
    }));
}
function banco_insert_name($fields, $table) {
    $row = [];
    foreach ($fields as $field) $row[$field[0]] = $field[1];
    $GLOBALS['rows'][] = $row;
}

require $root . '/gestor/bibliotecas/hooks.php';
require $root . '/gestor/controladores/atualizacoes/atualizacoes-hooks.php';

$module = ['hooks' => [
    'actions' => ['demo' => ['save' => ['one', 'two']]],
    'filters' => ['demo' => ['title' => 'filter_title']],
]];
$project = ['actions' => ['project' => ['deploy' => 'after_deploy']], 'filters' => []];
file_put_contents($tmp . '/modulos/demo/demo.json', json_encode($module));
file_put_contents($tmp . '/project/hooks/hooks.json', json_encode($project));

$_GESTOR = [
    'ROOT_PATH' => $tmp . '/',
    'modulos-path' => $tmp . '/modulos/',
    'plugins-path' => $tmp . '/plugins/',
];

$first = atualizacoes_hooks_sincronizar();
$firstRows = count($GLOBALS['rows']);
$second = atualizacoes_hooks_sincronizar();
$secondRows = count($GLOBALS['rows']);

$module = [];
file_put_contents($tmp . '/modulos/demo/demo.json', json_encode($module));
$third = atualizacoes_hooks_sincronizar();
$thirdRows = count($GLOBALS['rows']);

echo json_encode(compact('first', 'firstRows', 'second', 'secondRows', 'third', 'thirdRows'));
PHP;
        $script = str_replace('__ROOT__', var_export(str_replace('\\', '/', $root), true), $script);

        $process = proc_open([PHP_BINARY, '-r', $script], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, $stderr);
        $result = json_decode((string) $stdout, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(4, $result['first']['total']);
        self::assertSame(4, $result['firstRows']);
        self::assertSame($result['first'], $result['second']);
        self::assertSame(4, $result['secondRows'], 'a segunda execução duplicou hooks');
        self::assertSame(1, $result['third']['total']);
        self::assertSame(1, $result['thirdRows'], 'hooks removidos do JSON permaneceram no banco');
    }

    public function testComandoDedicadoUsaOMesmoTransporteSshHostDocker(): void
    {
        $command = $this->source('cli/src/Commands/ProjectSyncHooksCommand.php');
        $application = $this->source('cli/src/Console/Application.php');
        $transport = $this->source('ai-workspace/en/scripts/dev-environment/updates-manager-database.sh');

        self::assertStringContainsString("return 'project:sync-hooks'", $command);
        self::assertStringContainsString(". ' --hooks-only'", $command);
        self::assertStringContainsString('new ProjectSyncHooksCommand($this->rootPath)', $application);
        self::assertStringContainsString('--hooks-only)', $transport);
        self::assertStringContainsString('PHP_ARGS+=(--hooks-only)', $transport);
        foreach (['ssh', 'host', 'docker'] as $mode) {
            self::assertStringContainsString('EXECUTION_MODE="' . $mode . '"', $transport);
        }
        self::assertStringContainsString('project_transport_remote_exec php', $transport);
    }
}
