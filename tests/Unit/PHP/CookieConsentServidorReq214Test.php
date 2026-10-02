<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/cookie-consent.php';

/**
 * req-214: decisão de cookies lida no servidor e marca do item atual do menu do painel.
 */
final class CookieConsentServidorReq214Test extends TestCase
{
    protected function tearDown(): void
    {
        unset($_COOKIE[COOKIE_CONSENT_COOKIE]);
    }

    private function decidir(array $categorias): void
    {
        $_COOKIE[COOKIE_CONSENT_COOKIE] = json_encode(['v' => '1', 't' => time(), 'c' => $categorias]);
    }

    public function testSemDecisaoSoONecessarioEPermitido(): void
    {
        self::assertSame(['decided' => false, 'categories' => []], cookie_consent_estado());
        self::assertTrue(cookie_consent_permitido('necessary'));
        self::assertFalse(cookie_consent_permitido('marketing'));
        self::assertFalse(cookie_consent_permitido('analytics'));
    }

    public function testCategoriaPermitidaERecusada(): void
    {
        $this->decidir(['necessary' => true, 'analytics' => false, 'marketing' => true]);

        self::assertTrue(cookie_consent_estado()['decided']);
        self::assertTrue(cookie_consent_permitido('marketing'));
        self::assertFalse(cookie_consent_permitido('analytics'));
        self::assertFalse(cookie_consent_permitido('preferences'));   // criada depois da decisão: nasce negada
        self::assertTrue(cookie_consent_permitido('necessary'));
    }

    public function testSoTrueLiteralPermite(): void
    {
        $this->decidir(['marketing' => 'true', 'analytics' => 1, 'Outra Coisa' => true]);

        self::assertFalse(cookie_consent_permitido('marketing'));
        self::assertFalse(cookie_consent_permitido('analytics'));
        self::assertArrayNotHasKey('Outra Coisa', cookie_consent_estado()['categories']);
    }

    public function testCookieIlegivelValeComoSemDecisao(): void
    {
        foreach (['', 'não é json', '[]', '{"c":"x"}', str_repeat('a', 5000)] as $valor) {
            $_COOKIE[COOKIE_CONSENT_COOKIE] = $valor;
            self::assertFalse(cookie_consent_estado()['decided'], $valor === '' ? 'vazio' : substr($valor, 0, 12));
            self::assertFalse(cookie_consent_permitido('marketing'));
        }
    }

    /** Biblioteca fora do mapa não carrega: `gestor_incluir_biblioteca()` só conhece o que o config registra. */
    public function testBibliotecaEstaRegistradaNoMapaDoCore(): void
    {
        $config = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/config.php');

        self::assertStringContainsString("'cookie-consent' => Array('cookie-consent.php'),", $config);
    }

    public function testMenuDoPainelMarcaOItemAtualComAriaCurrent(): void
    {
        $gestor = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        self::assertStringContainsString('<a aria-current="page"', $gestor);

        $js = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/assets/global/global.js');
        self::assertStringContainsString('window.gestorMenuPosicionarAtual = menuPosicionarAtual;', $js);
        // A rolagem guardada da visita anterior não pode desfazer o posicionamento.
        self::assertSame(2, substr_count($js, '!window.gestorMenuAtualPosicionado && sessionStorage.getItem('));
        foreach (['.menuComputerCont', '#conn2flow-menu-principal', '[data-admin-sidebar]'] as $contexto) {
            self::assertStringContainsString($contexto, substr($js, strpos($js, 'function menuContextos()'), 900));
        }
    }
}
