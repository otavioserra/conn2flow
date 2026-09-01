<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Seleção Docker/host do atualizador de banco de projetos (req-152 / BATCH-154). */
final class ProjectDatabaseExecutionModeTest extends TestCase
{
    private function script(): string
    {
        return dirname(CONN2FLOW_GESTOR_ROOT) . DIRECTORY_SEPARATOR . 'ai-workspace'
            . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . 'scripts'
            . DIRECTORY_SEPARATOR . 'dev-environment' . DIRECTORY_SEPARATOR
            . 'updates-manager-database.sh';
    }

    public function testMantemExecucaoDockerSemShellIntermediario(): void
    {
        $conteudo = (string) file_get_contents($this->script());

        self::assertStringContainsString('EXECUTION_MODE="docker"', $conteudo);
        self::assertStringContainsString(
            'docker exec conn2flow-app php "$PHP_SCRIPT" "${PHP_ARGS[@]}"',
            $conteudo
        );
        self::assertStringNotContainsString('docker exec conn2flow-app bash -c', $conteudo);
    }

    public function testTargetHostValidoSelecionaPhpNativo(): void
    {
        $conteudo = (string) file_get_contents($this->script());

        self::assertStringContainsString(
            'HOST_PHP_SCRIPT="${TARGET_PATH}controladores/atualizacoes/atualizacoes-banco-de-dados.php"',
            $conteudo
        );
        self::assertMatchesRegularExpression(
            '/if \[ -f "\$HOST_PHP_SCRIPT" \]; then\s+PATH_HOST="\$TARGET_PATH"\s+EXECUTION_MODE="host"/',
            $conteudo
        );
        self::assertStringContainsString(
            '(cd "$PATH_HOST" && php "$PHP_SCRIPT" "${PHP_ARGS[@]}")',
            $conteudo
        );
    }

    public function testTargetHostInvalidoFalhaExplicitamente(): void
    {
        $conteudo = (string) file_get_contents($this->script());

        self::assertStringContainsString('has neither dockerPath nor an executable host target', $conteudo);
        self::assertStringContainsString('PHP CLI is required for host execution.', $conteudo);
    }

    public function testArgumentosContinuamSeparadosEValidados(): void
    {
        $conteudo = (string) file_get_contents($this->script());

        self::assertStringContainsString('PHP_ARGS=(--debug --log-diff)', $conteudo);
        self::assertStringContainsString('PHP_ARGS+=("--tables=$TABLES")', $conteudo);
        self::assertStringContainsString('PHP_ARGS+=("--project=$PROJECT_TARGET")', $conteudo);
        self::assertStringContainsString('[[ ! "$PROJECT_TARGET" =~ ^[a-zA-Z0-9_-]+$ ]]', $conteudo);
    }

    public function testBootstrapPhinxCompartilhaEstadoGlobalDoGestor(): void
    {
        $phinx = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'phinx.php';
        $conteudo = (string) file_get_contents($phinx);

        self::assertMatchesRegularExpression(
            '/global \$_GESTOR;\s+require_once \$configPath;/',
            $conteudo,
            'config.php precisa reconstruir o mesmo estado global usado pelas bibliotecas do atualizador CLI'
        );
    }
}
