<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'seguranca.php';

/**
 * Cobertura das funções puras de endurecimento de segurança (bloco de IP e User-Agent).
 * As funções de sessão/CSRF dependem de banco e são validadas em integração/runtime.
 */
final class SegurancaTest extends TestCase
{
    public function testIpBlocoRetornaTresPrimeirosOctetosIpv4(): void
    {
        self::assertSame('200.100.50', seguranca_ip_bloco('200.100.50.25'));
        self::assertSame('10.0.0', seguranca_ip_bloco('10.0.0.255'));
        self::assertSame('192.168.1', seguranca_ip_bloco('192.168.1.1'));
    }

    public function testIpBlocoFazFallbackParaIpv6OuValorDesconhecido(): void
    {
        self::assertSame('::1', seguranca_ip_bloco('::1'));
        self::assertSame('2001:db8::ff00:42:8329', seguranca_ip_bloco('2001:db8::ff00:42:8329'));
        self::assertSame('', seguranca_ip_bloco(''));
    }

    public function testUserAgentTruncaEm255Caracteres(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = str_repeat('A', 300);
        self::assertSame(255, strlen(seguranca_user_agent()));

        unset($_SERVER['HTTP_USER_AGENT']);
        self::assertSame('', seguranca_user_agent());
    }
}
