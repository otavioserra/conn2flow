<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * REQ-131 (site): o administrador de um tenant novo nascia sem perfil; a recusa de módulo manda para
 * `dashboard/`, e a recusa do próprio painel redirecionava para ele mesmo, sem fim.
 */
final class GestorPainelSemLacoReq131Test extends TestCase
{
    private static function fonte(string $arquivo): string
    {
        return str_replace("\r\n", "\n", (string)file_get_contents(dirname(__DIR__, 3) . '/' . $arquivo));
    }

    /** @return array<string,array{0:mixed,1:mixed,2:bool}> */
    public static function rotasProvider(): array
    {
        return [
            'modulo dashboard' => ['dashboard', 'qualquer/', true],
            'caminho dashboard/' => ['', 'dashboard/', true],
            'caminho sem barra' => [null, 'dashboard', true],
            'outro modulo' => ['usuarios', 'usuarios/', false],
            'subcaminho do painel' => ['', 'dashboard/widgets/', false],
            'prefixo parecido' => ['dashboards', 'dashboards/', false],
            'nada' => [null, null, false],
        ];
    }

    #[DataProvider('rotasProvider')]
    public function testReconheceARotaDoPainel(mixed $modulo, mixed $caminho, bool $esperado): void
    {
        self::assertSame($esperado, gestor_permissao_rota_painel($modulo, $caminho));
    }

    public function testRecusaDoPainelVira403EmVezDeRedirecionarParaSiMesmo(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_permissao(){');
        $corpo = substr($fonte, $inicio, strpos($fonte, 'function gestor_usuario(){') - $inicio);

        self::assertStringContainsString("\$rota_painel = gestor_permissao_rota_painel(\$_GESTOR['modulo'] ?? '', \$_GESTOR['caminho-total'] ?? '');", $corpo);
        self::assertStringContainsString('if(!gestor_permissao_modulo(!$rota_painel)){', $corpo);

        // A tela 403 precisa vir antes de qualquer `redirect => dashboard/`.
        $negado = strpos($corpo, 'gestor_roteador_acesso_restrito_negado(');
        $redirect = strpos($corpo, "'redirect' => 'dashboard/'");
        self::assertNotFalse($negado);
        self::assertLessThan($redirect, $negado);
        self::assertStringContainsString("'titulo' => 'panel-access-denied-title'", $corpo);
    }

    public function testTextosDaRecusaDoPainelExistemNosDoisIdiomas(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $ids = array_column(json_decode(self::fonte("gestor/resources/{$lang}/variables.json"), true), 'id');
            self::assertContains('panel-access-denied-title', $ids, $lang);
            self::assertContains('panel-access-denied-text', $ids, $lang);
        }
    }

    public function testInstaladorDaPerfilAoAdministradorSemPerfil(): void
    {
        $fonte = self::fonte('gestor-instalador/src/Installer.php');
        $inicio = strpos($fonte, 'private function ensureAdminUser()');
        $corpo = substr($fonte, $inicio, strpos($fonte, 'private function addWarning(') - $inicio);

        self::assertStringContainsString("UPDATE usuarios SET id_usuarios_perfis = (SELECT id_usuarios_perfis FROM usuarios_perfis", $corpo);
        self::assertStringContainsString("WHERE id = 'administradores' ORDER BY id_usuarios_perfis LIMIT 1", $corpo);
        self::assertStringContainsString('(id_usuarios_perfis IS NULL OR id_usuarios_perfis = 0)', $corpo);
    }
}
