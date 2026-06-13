<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'jwt.php';

/**
 * Cobertura das funções puras do JWT (codificação Base64 URL-safe e configuração).
 * O ciclo completo de rotação/grace depende de banco e é validado em integração
 * (banco real) e pelo teste standalone registrado na evidência do BATCH-030.
 */
final class JwtTest extends TestCase
{
    public function testBase64UrlEncodeNaoContemCaracteresNaoSafe(): void
    {
        // Bytes que em base64 padrão produziriam '+', '/' e '='.
        $bin = base64_decode('++//==', true);
        $encoded = jwt_base64url_encode($bin === false ? "\xfb\xff" : $bin);

        self::assertSame(0, preg_match('/[+\/=]/', $encoded));
    }

    public function testBase64UrlRoundTripPreservaBinario(): void
    {
        foreach (['', 'a', 'payload', random_bytes(1), random_bytes(15), random_bytes(64)] as $amostra) {
            $encoded = jwt_base64url_encode($amostra);
            self::assertSame($amostra, jwt_base64url_decode($encoded));
        }
    }

    public function testConfiguracoesUsamDefaultsQuandoEnvAusente(): void
    {
        unset($_ENV['AUTH_JWT_ROTATION_DAYS'], $_ENV['AUTH_JWT_GRACE_HOURS']);
        self::assertSame(30, jwt_rotation_days());
        self::assertSame(24, jwt_grace_hours());

        $_ENV['AUTH_JWT_ROTATION_DAYS'] = '7';
        $_ENV['AUTH_JWT_GRACE_HOURS'] = '12';
        self::assertSame(7, jwt_rotation_days());
        self::assertSame(12, jwt_grace_hours());

        unset($_ENV['AUTH_JWT_ROTATION_DAYS'], $_ENV['AUTH_JWT_GRACE_HOURS']);
    }

    public function testNovaChaveTemEstruturaEsperada(): void
    {
        $chave = jwt_nova_chave('active');

        self::assertArrayHasKey('key_id', $chave);
        self::assertArrayHasKey('key_secret', $chave);
        self::assertArrayHasKey('created_at', $chave);
        self::assertSame('active', $chave['status']);
        self::assertSame(16, strlen($chave['key_id']));
        self::assertSame(64, strlen($chave['key_secret']));
    }
}
