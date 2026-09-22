<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if(!defined('CONN2FLOW_DISABLE_MODULE_START')) define('CONN2FLOW_DISABLE_MODULE_START', true);
require_once CONN2FLOW_GESTOR_ROOT . '/modulos/perfil-usuario/perfil-usuario.php';

final class PerfilUsuarioCaptchaFallbackTest extends TestCase
{
    private array $configOriginal;
    private array $postOriginal;

    protected function setUp(): void
    {
        global $_CONFIG;
        $this->configOriginal = $_CONFIG;
        $this->postOriginal = $_POST;
        $_POST = [];
        $_CONFIG['captcha-provider'] = 'google-recaptcha';
        $_CONFIG['usuario-recaptcha-active'] = true;
        $_CONFIG['usuario-recaptcha-v2-active'] = true;
    }

    protected function tearDown(): void
    {
        global $_CONFIG;
        $_CONFIG = $this->configOriginal;
        $_POST = $this->postOriginal;
    }

    public function testTokenV3ValidoLiberaAutenticacao(): void
    {
        $chamadas = [];
        $resultado = perfil_usuario_captcha_avaliar(['status' => 'captcha'], 'logar',
            static function (?string $token, array $opcoes) use (&$chamadas): bool {
                $chamadas[] = [$token, $opcoes];
                return $opcoes === ['action' => 'logar'];
            });

        self::assertSame(['status' => 'valid', 'version' => 'v3'], $resultado);
        self::assertSame([[null, ['action' => 'logar']]], $chamadas);
    }

    public function testFalhaV3SolicitaCheckboxV2SemValidarCredenciais(): void
    {
        $resultado = perfil_usuario_captcha_avaliar(['status' => 'captcha'], 'logar',
            static fn (?string $token, array $opcoes): bool => false);

        self::assertSame(['status' => 'require_v2'], $resultado);
    }

    public function testTokenV2AprovadoLiberaAutenticacaoSemRepetirV3(): void
    {
        $_POST['g-recaptcha-response'] = 'checkbox-token';
        $chamadas = [];
        $resultado = perfil_usuario_captcha_avaliar(['status' => 'captcha'], 'logar',
            static function (?string $token, array $opcoes) use (&$chamadas): bool {
                $chamadas[] = [$token, $opcoes];
                return $token === null && $opcoes === ['v2' => true];
            });

        self::assertSame(['status' => 'valid', 'version' => 'v2'], $resultado);
        self::assertSame([[null, ['v2' => true]]], $chamadas);
    }
}
