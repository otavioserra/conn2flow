<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** REQ-052 / BATCH-054 — auditoria SSH e confirmação automática restrita à VM local. */
final class CssAuditSshReq052Test extends TestCase
{
    public function testCssAuditDelegaProjetoSshSemRepassarProjectOuExigirEnvLocal(): void
    {
        $fonte = self::conteudo('CssAuditCommand.php');

        self::assertStringContainsString('SshRemoteTransport', $fonte);
        self::assertStringContainsString('auditarViaSsh', $fonte);
        self::assertStringContainsString("'controladores/agents/arquitetura/css-auditoria.php'", $fonte);
        self::assertStringContainsString("'--gestor=.'", $fonte);
        self::assertStringContainsString('buildRemoteCommand', $fonte);
        self::assertStringContainsString("hasOption('simular-remoto')", $fonte);
        self::assertStringNotContainsString("\$argv[] = '--project='", $fonte);
    }

    public function testPipelineAutorizaSomenteVmSshMarcadaComoLocal(): void
    {
        $fonte = self::conteudo('ProjectUpdateAllCommand.php');

        self::assertMatchesRegularExpression(
            "/deployMode'[\s\S]{0,180}?'ssh'[\s\S]{0,180}?config\['local'\]/",
            $fonte
        );
        self::assertStringContainsString(
            "\$confirmarRemoto = \$input->hasOption('confirmar-remoto') || \$autorizarVmLocal",
            $fonte
        );
        self::assertSame(2, substr_count($fonte, "if (\$confirmarRemoto)"));
    }

    private static function conteudo(string $arquivo): string
    {
        $caminho = dirname(CONN2FLOW_GESTOR_ROOT) . '/cli/src/Commands/' . $arquivo;
        self::assertFileExists($caminho);
        return (string) file_get_contents($caminho);
    }
}
