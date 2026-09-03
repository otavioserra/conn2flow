<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** REQ-051 / BATCH-053 — TLS local e diagnóstico verboso do updater por API. */
final class ProjectUpdateSystemVmReq051Test extends TestCase
{
    public function testScriptAtivaInsecureSomentePorSinalLocalExplicito(): void
    {
        $script = $this->source('ai-workspace/en/scripts/projects/update-system.sh');

        self::assertStringContainsString('[[ "$PROJECT_URL" =~ ^https://[^/]*\\.local([:/]|$) ]]', $script);
        self::assertStringContainsString('.api.insecure_ssl', $script);
        self::assertStringContainsString('CURL_INSECURE_ARGS=(--insecure)', $script);
        self::assertStringContainsString('"${CURL_INSECURE_ARGS[@]}"', $script);
    }

    public function testFalhaDeTransporteNaoEhEngolidaPeloSetE(): void
    {
        $script = $this->source('ai-workspace/en/scripts/projects/update-system.sh');

        self::assertStringContainsString('2>"$curl_error_file") || API_CURL_EXIT=$?', $script);
        self::assertStringContainsString('log_error "cURL: $API_CURL_ERROR"', $script);
        self::assertStringContainsString('log_error "Response body:"', $script);
        self::assertStringContainsString('HTTP ${API_HTTP_CODE:-unknown}', $script);
    }

    public function testTodosOsPassosHttpUsamDiagnosticoCompartilhado(): void
    {
        $script = $this->source('ai-workspace/en/scripts/projects/update-system.sh');

        foreach (['Starting update session', 'Deploy', 'Database update', 'Finalize'] as $step) {
            self::assertStringContainsString('print_api_failure "' . $step . '"', $script);
        }
    }

    public function testComandoPhpRepassaFlagInsecure(): void
    {
        $command = $this->source('cli/src/Commands/ProjectUpdateSystemCommand.php');

        self::assertStringContainsString("hasOption('insecure')", $command);
        self::assertStringContainsString("' --insecure'", $command);
        self::assertStringContainsString('[--insecure]', $command);
    }

    public function testTemplateDocumentaOptInSeguroPorPadrao(): void
    {
        $template = json_decode(
            $this->source('dev-environment/templates/environment/environment.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertFalse($template['devProjects']['project_ID']['api']['insecure_ssl']);
    }

    private function source(string $relativePath): string
    {
        $path = dirname(CONN2FLOW_GESTOR_ROOT) . '/' . $relativePath;
        self::assertFileExists($path);
        return (string) file_get_contents($path);
    }
}
