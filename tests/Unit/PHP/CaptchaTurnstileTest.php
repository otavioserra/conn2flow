<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/seguranca.php';

final class CaptchaTurnstileTest extends TestCase
{
    private array $configOriginal;
    private array $postOriginal;

    protected function setUp(): void
    {
        global $_CONFIG;
        $this->configOriginal = $_CONFIG;
        $this->postOriginal = $_POST;
        $_CONFIG['captcha-provider'] = 'cloudflare-turnstile';
        $_CONFIG['turnstile-secret-key'] = '1x0000000000000000000000000000000AA';
    }

    protected function tearDown(): void
    {
        global $_CONFIG;
        $_CONFIG = $this->configOriginal;
        $_POST = $this->postOriginal;
    }

    public function testNoneDispensaToken(): void
    {
        self::assertTrue(gestor_captcha_validar(null, ['provider' => 'none']));
    }

    public function testTurnstileAceitaRespostaValidaEEnviaPayload(): void
    {
        $_POST['cf-turnstile-response'] = 'test-token';
        $calls = 0;
        $result = gestor_captcha_validar(null, ['transport' => static function (string $url, array $payload) use (&$calls): string {
            $calls++;
            TestCase::assertSame('https://challenges.cloudflare.com/turnstile/v0/siteverify', $url);
            TestCase::assertSame('1x0000000000000000000000000000000AA', $payload['secret']);
            TestCase::assertSame('test-token', $payload['response']);
            return '{"success":true}';
        }]);
        self::assertSame(['success' => true], $result);
        self::assertSame(1, $calls);
    }

    public function testTurnstileRejeitaFalhaRedeJsonEChaveInvalida(): void
    {
        foreach ([false, '{', '{"success":false,"error-codes":["invalid-input-secret"]}'] as $response) {
            self::assertFalse(gestor_captcha_validar('token', [
                'secret' => '2x0000000000000000000000000000000AA',
                'transport' => static fn (): bool|string => $response,
            ]));
        }
    }

    public function testRespostaDeErroPodeSerInspecionadaNoPainel(): void
    {
        $result = gestor_captcha_validar('token', [
            'return_response' => true,
            'secret' => '2x0000000000000000000000000000000AA',
            'transport' => static fn (): string => '{"success":false,"error-codes":["invalid-input-secret"]}',
        ]);
        self::assertSame(['success' => false, 'error-codes' => ['invalid-input-secret']], $result);
    }

    public function testTokenAusenteNaoChamaRede(): void
    {
        self::assertFalse(gestor_captcha_validar('', ['transport' => static function (): never {
            throw new RuntimeException('Rede não deveria ser chamada');
        }]));
    }

    public function testGoogleValidaActionEScore(): void
    {
        $options = [
            'provider' => 'google-recaptcha', 'secret' => 'test-secret', 'action' => 'logar',
            'transport' => static fn (): string => '{"success":true,"action":"logar","score":0.9}',
        ];
        self::assertIsArray(gestor_captcha_validar('token', $options));
        $options['action'] = 'signup';
        self::assertFalse(gestor_captcha_validar('token', $options));
    }
}
