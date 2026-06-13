<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . '2fa.php';

/**
 * Cobertura das funções puras de 2FA (TOTP/HOTP RFC 4226/6238 e Base32).
 * As funções de e-mail dependem de banco e não são exercitadas aqui.
 */
final class TwoFactorTest extends TestCase
{
    /** Segredo ASCII de teste do RFC, equivalente Base32. */
    private const SECRET_ASCII = '12345678901234567890';
    private const SECRET_B32 = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function testBase32EncodeDecodeBateComVetorRfc(): void
    {
        self::assertSame(self::SECRET_B32, two_factor_base32_encode(self::SECRET_ASCII));
        self::assertSame(self::SECRET_ASCII, two_factor_base32_decode(self::SECRET_B32));
    }

    /**
     * @return array<int, array{0:int,1:string}>
     */
    public static function vetoresHotp(): array
    {
        // RFC 4226, Apêndice D.
        return [
            [0, '755224'], [1, '287082'], [2, '359152'], [3, '969429'], [4, '338314'],
            [5, '254676'], [6, '287922'], [7, '162583'], [8, '399871'], [9, '520489'],
        ];
    }

    /**
     * @dataProvider vetoresHotp
     */
    public function testHotpBateComVetoresRfc4226(int $counter, string $esperado): void
    {
        self::assertSame($esperado, two_factor_hotp(self::SECRET_B32, $counter));
    }

    public function testTotpBateComVetoresRfc6238(): void
    {
        // TOTP de 6 dígitos derivado dos vetores SHA1 do RFC 6238.
        self::assertSame('287082', two_factor_hotp(self::SECRET_B32, (int) floor(59 / 30)));
        self::assertSame('081804', two_factor_hotp(self::SECRET_B32, (int) floor(1111111109 / 30)));
    }

    public function testGenerateSecretProduzBase32De16Caracteres(): void
    {
        $secret = two_factor_generate_secret();
        self::assertSame(16, strlen($secret));
        self::assertSame(1, preg_match('/^[A-Z2-7]+$/', $secret));
    }

    public function testValidateCodeAceitaJanelaDeMaisMenosUmCiclo(): void
    {
        $secret = two_factor_generate_secret();
        $slice = (int) floor(time() / 30);

        self::assertTrue(two_factor_validate_code($secret, two_factor_hotp($secret, $slice)));
        self::assertTrue(two_factor_validate_code($secret, two_factor_hotp($secret, $slice - 1)));
        self::assertTrue(two_factor_validate_code($secret, two_factor_hotp($secret, $slice + 1)));
    }

    public function testValidateCodeRejeitaForaDaJanelaEMalFormados(): void
    {
        $secret = two_factor_generate_secret();
        $slice = (int) floor(time() / 30);

        self::assertFalse(two_factor_validate_code($secret, two_factor_hotp($secret, $slice + 5)));
        self::assertFalse(two_factor_validate_code($secret, '000'));
        self::assertFalse(two_factor_validate_code($secret, 'abcdef'));
    }

    public function testGetQrCodeMontaUriOtpauthEsperada(): void
    {
        $uri = two_factor_get_qr_code('user@exemplo.com', self::SECRET_B32);

        self::assertStringStartsWith('otpauth://totp/Conn2Flow:', $uri);
        self::assertStringContainsString('secret=' . self::SECRET_B32, $uri);
        self::assertStringContainsString('issuer=Conn2Flow', $uri);
    }
}
