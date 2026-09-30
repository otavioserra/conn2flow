<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-193 — parcelamento opcional em PaymentIntent avulso.
 *
 * A função é extraída do fonte e executada contra um dublê de `stripe_requisicao`.
 */
final class StripePaymentIntentInstallmentsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (function_exists('stripe_criar_payment_intent_teste')) return;
        $fonte = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/stripe.php');
        self::assertSame(1, preg_match('/function stripe_criar_payment_intent\(.*?\n}\n/s', $fonte, $m));
        $codigo = str_replace(
            ['function stripe_criar_payment_intent(', 'stripe_requisicao(', 'stripe_valor_menor_unidade('],
            ['function stripe_criar_payment_intent_teste(', 'stripe_requisicao_payment_intent_teste(', 'stripe_valor_menor_unidade_teste('],
            $m[0]
        );
        eval('function stripe_requisicao_payment_intent_teste($params){ $GLOBALS["stripe_payment_intent_request"] = $params; return ["http_code" => 200, "data" => ["id" => "pi_1", "client_secret" => "pi_secret", "status" => "requires_payment_method"]]; }');
        eval('function stripe_valor_menor_unidade_teste($valor, $moeda = "BRL"){ return (int) round((float) $valor * 100); }');
        eval($codigo);
    }

    public function testSemOpcaoMantemPayloadLegado(): void
    {
        $resultado = stripe_criar_payment_intent_teste([
            'valor' => 15,
            'moeda' => 'BRL',
            'customer_id' => 'cus_1',
            'descricao' => 'Pedido',
            'referencia' => 'order_1',
        ]);

        self::assertSame([
            'amount' => 1500,
            'currency' => 'brl',
            'automatic_payment_methods' => ['enabled' => 'true'],
            'customer' => 'cus_1',
            'description' => 'Pedido',
            'metadata' => ['referencia' => 'order_1'],
        ], $GLOBALS['stripe_payment_intent_request']['data']);
        self::assertSame('pi_secret', $resultado['client_secret']);
    }

    public function testParcelamentoPodeSerHabilitadoExplicitamente(): void
    {
        stripe_criar_payment_intent_teste([
            'valor' => 15,
            'installments' => ['enabled' => true],
        ]);

        self::assertSame(
            ['card' => ['installments' => ['enabled' => 'true']]],
            $GLOBALS['stripe_payment_intent_request']['data']['payment_method_options'] ?? null
        );
        self::assertSame(
            'payment_method_options%5Bcard%5D%5Binstallments%5D%5Benabled%5D=true',
            http_build_query([
                'payment_method_options' => $GLOBALS['stripe_payment_intent_request']['data']['payment_method_options'],
            ])
        );
    }

    public function testParcelamentoDesabilitadoNaoAlteraPayload(): void
    {
        stripe_criar_payment_intent_teste([
            'valor' => 15,
            'installments' => ['enabled' => false],
        ]);

        self::assertArrayNotHasKey(
            'payment_method_options',
            $GLOBALS['stripe_payment_intent_request']['data'] ?? []
        );
    }
}
