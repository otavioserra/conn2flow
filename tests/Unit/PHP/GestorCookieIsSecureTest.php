<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários para gestor_cookie_is_secure() — REQ-133
 *
 * Cenários:
 *  1. HTTPS via $_SERVER['HTTPS'] = 'on' → true
 *  2. HTTPS via porta 443 → true
 *  3. HTTPS via X-Forwarded-Proto → true
 *  4. HTTP + produção (development-env = false) → true (fail-secure)
 *  5. HTTP + desenvolvimento (development-env = true) → false
 *  6. Nenhum $_SERVER definido + produção → true (fail-secure)
 */
final class GestorCookieIsSecureTest extends TestCase
{
    private array $originalServer;
    /** @var mixed */
    private $originalGestor;

    protected function setUp(): void
    {
        global $_GESTOR;

        $this->originalServer = $_SERVER;
        $this->originalGestor = $_GESTOR;

        // Reset para estado limpo
        unset($_SERVER['HTTPS'], $_SERVER['SERVER_PORT'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_GESTOR['development-env'] = false;
    }

    protected function tearDown(): void
    {
        global $_GESTOR;

        $_SERVER = $this->originalServer;
        $_GESTOR = $this->originalGestor;
    }

    // ===== Cenário 1: HTTPS via $_SERVER['HTTPS'] = 'on'
    public function testHttpsOnRetornaTrue(): void
    {
        global $_GESTOR;
        $_GESTOR['development-env'] = true; // mesmo em dev, HTTPS deve forçar true
        $_SERVER['HTTPS'] = 'on';

        self::assertTrue(gestor_cookie_is_secure());
    }

    // ===== Cenário 2: HTTPS via porta 443
    public function testPorta443RetornaTrue(): void
    {
        global $_GESTOR;
        $_GESTOR['development-env'] = true;
        $_SERVER['SERVER_PORT'] = '443';

        self::assertTrue(gestor_cookie_is_secure());
    }

    // ===== Cenário 3: HTTPS via X-Forwarded-Proto (reverse proxy)
    public function testXForwardedProtoHttpsRetornaTrue(): void
    {
        global $_GESTOR;
        $_GESTOR['development-env'] = true;
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        self::assertTrue(gestor_cookie_is_secure());
    }

    // ===== Cenário 4: HTTP puro + produção → true (fail-secure)
    public function testHttpProducaoRetornaTrue(): void
    {
        global $_GESTOR;
        $_GESTOR['development-env'] = false;

        self::assertTrue(gestor_cookie_is_secure());
    }

    // ===== Cenário 5: HTTP puro + desenvolvimento → false (único cenário permissivo)
    public function testHttpDesenvolvimentoRetornaFalse(): void
    {
        global $_GESTOR;
        $_GESTOR['development-env'] = true;

        self::assertFalse(gestor_cookie_is_secure());
    }

    // ===== Cenário 6: Sem $_SERVER['HTTPS'] + produção → true (fail-secure)
    public function testSemServidorDefinidoProducaoRetornaTrue(): void
    {
        global $_GESTOR;
        unset($_SERVER['HTTPS'], $_SERVER['SERVER_PORT'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_GESTOR['development-env'] = false;

        self::assertTrue(gestor_cookie_is_secure());
    }

    // ===== Cenário extra: HTTPS = 'off' não conta como HTTPS
    public function testHttpsOffNaoContaComoHttps(): void
    {
        global $_GESTOR;
        $_GESTOR['development-env'] = true;
        $_SERVER['HTTPS'] = 'off';

        self::assertFalse(gestor_cookie_is_secure());
    }

    // ===== Cenário extra: X-Forwarded-Proto em maiúsculas
    public function testXForwardedProtoMaiusculasRetornaTrue(): void
    {
        global $_GESTOR;
        $_GESTOR['development-env'] = true;
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'HTTPS';

        self::assertTrue(gestor_cookie_is_secure());
    }
}
