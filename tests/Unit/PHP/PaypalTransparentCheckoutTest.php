<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-098 / BATCH-104 — contrato local do checkout transparente PayPal.
 *
 * Os testes unitários não acessam a sandbox: validam o filtro de payload e a
 * montagem estrutural do core; a resposta HTTP real permanece para homologação.
 */
final class PaypalTransparentCheckoutTest extends TestCase
{
    private static string $source;

    public static function setUpBeforeClass(): void
    {
        $path = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'paypal.php';
        self::$source = (string) file_get_contents($path);
        require_once $path;
    }

    public function testValidaPaymentSourceDeCartaoOuToken(): void
    {
        self::assertTrue(paypal_validar_payment_source([
            'card' => ['vault_id' => 'CARD-TOKEN-123'],
        ]));
        self::assertTrue(paypal_validar_payment_source([
            'token' => ['id' => 'SETUP-TOKEN-123', 'type' => 'SETUP_TOKEN'],
        ]));
    }

    public function testRejeitaPaymentSourceVazioOuDesconhecido(): void
    {
        self::assertFalse(paypal_validar_payment_source(false));
        self::assertFalse(paypal_validar_payment_source([]));
        self::assertFalse(paypal_validar_payment_source(['card' => []]));
        self::assertFalse(paypal_validar_payment_source(['paypal' => ['email' => 'buyer@example.test']]));
    }

    public function testClientTokenRejeitaCustomerIdInvalidoAntesDeChamarApi(): void
    {
        self::assertFalse(paypal_gerar_client_token('customer'));
        self::assertFalse(paypal_gerar_client_token(['customer_id' => '  ']));
        self::assertFalse(paypal_gerar_client_token(['customer_id' => 123]));
    }

    public function testHelperRejeitaPayloadInvalidoAntesDeChamarApi(): void
    {
        self::assertFalse(paypal_processar_pagamento_transparente(false));
        self::assertFalse(paypal_processar_pagamento_transparente(['tipo' => 'desconhecido']));
        self::assertFalse(paypal_processar_pagamento_transparente(['tipo' => 'pedido', 'valor' => 10]));
        self::assertFalse(paypal_processar_pagamento_transparente([
            'tipo' => 'pedido',
            'valor' => 0,
            'payment_source' => ['card' => ['vault_id' => 'token']],
        ]));
        self::assertFalse(paypal_processar_pagamento_transparente([
            'tipo' => 'assinatura',
            'payment_source' => ['card' => ['vault_id' => 'token']],
        ]));
    }

    public function testPedidoEncaminhaPaymentSourceNoTopoEUsaIdempotencia(): void
    {
        self::assertStringContainsString("\$order_data['payment_source'] = \$payment_source;", self::$source);
        self::assertStringContainsString("'PayPal-Request-Id: ' . \$idempotency_key", self::$source);
        self::assertStringContainsString("Array(200, 201)", self::$source);
    }

    public function testAssinaturaEncaminhaPaymentSourceDentroDoSubscriber(): void
    {
        self::assertStringContainsString(
            "\$subscription_data['subscriber']['payment_source'] = \$payment_source;",
            self::$source
        );
    }

    public function testInfoPublicaAsNovasFuncoesEAPIVersao(): void
    {
        $info = paypal_info();

        self::assertSame('3.1.0', $info['versao']);
        self::assertArrayHasKey('Identity API v1', $info['apis']);
        self::assertContains('paypal_gerar_client_token', $info['funcoes']);
        self::assertContains('paypal_processar_pagamento_transparente', $info['funcoes']);
        self::assertContains('paypal_validar_payment_source', $info['funcoes']);
    }
}
