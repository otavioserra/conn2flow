<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-195 — `stripe_criar_assinatura` aceita `add_invoice_items` (carrinho misto do conn2flow-site:
 * produtos e frete na primeira fatura da assinatura, uma cobrança só).
 *
 * Mesmo arranjo do StripeAssinaturaCupomTest: a função é extraída do fonte e roda contra um dublê.
 */
final class StripeAssinaturaItensAvulsosTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (function_exists('stripe_criar_assinatura_teste')) return;
        $fonte = str_replace("\r\n", "\n", (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/stripe.php'));
        self::assertSame(1, preg_match('/function stripe_criar_assinatura\(.*?\n}\n/s', $fonte, $m));
        $codigo = str_replace(['function stripe_criar_assinatura(', 'stripe_requisicao('], ['function stripe_criar_assinatura_teste(', 'stripe_requisicao_teste('], $m[0]);
        eval('function stripe_requisicao_teste($p){ $GLOBALS["stripe_payload"] = $p["data"]; return ["http_code" => 200, "data" => ["id" => "sub_1", "status" => "incomplete", "latest_invoice" => ["payment_intent" => ["client_secret" => "pi_secret"]]]]; }');
        eval($codigo);
    }

    public function testItensViramPriceDataNaPrimeiraFatura(): void
    {
        stripe_criar_assinatura_teste(['customer_id' => 'cus_1', 'price_id' => 'price_1', 'add_invoice_items' => [
            ['product' => 'prod_loja', 'amount' => '47.50', 'currency' => 'BRL', 'quantity' => 2],
            ['product' => 'prod_loja', 'amount' => 9.9, 'currency' => 'BRL'],
        ]]);
        self::assertSame([
            ['price_data' => ['currency' => 'brl', 'product' => 'prod_loja', 'unit_amount' => 4750], 'quantity' => 2],
            ['price_data' => ['currency' => 'brl', 'product' => 'prod_loja', 'unit_amount' => 990], 'quantity' => 1],
        ], $GLOBALS['stripe_payload']['add_invoice_items']);
    }

    public function testComItensOCupomVaiSoNoItemDoPlano(): void
    {
        stripe_criar_assinatura_teste(['customer_id' => 'cus_1', 'price_id' => 'price_1', 'coupon_id' => 'c2f_off',
            'add_invoice_items' => [['product' => 'prod_loja', 'amount' => 57.4, 'currency' => 'BRL']]]);
        self::assertArrayNotHasKey('discounts', $GLOBALS['stripe_payload']);
        self::assertSame([['coupon' => 'c2f_off']], $GLOBALS['stripe_payload']['items'][0]['discounts']);
    }

    public function testItemInvalidoOuZeradoEIgnorado(): void
    {
        stripe_criar_assinatura_teste(['customer_id' => 'cus_1', 'price_id' => 'price_1', 'add_invoice_items' => [
            ['amount' => 10], ['product' => 'prod_loja', 'amount' => 0], 'lixo',
        ]]);
        self::assertArrayNotHasKey('add_invoice_items', $GLOBALS['stripe_payload']);
    }

    public function testSemItensOPayloadNaoMuda(): void
    {
        stripe_criar_assinatura_teste(['customer_id' => 'cus_1', 'price_id' => 'price_1', 'trial_period_days' => 7]);
        self::assertArrayNotHasKey('add_invoice_items', $GLOBALS['stripe_payload']);
        self::assertSame(7, $GLOBALS['stripe_payload']['trial_period_days']);
    }
}
