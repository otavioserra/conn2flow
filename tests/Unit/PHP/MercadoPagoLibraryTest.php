<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-301 / BATCH-310 — biblioteca `gestor/bibliotecas/mercadopago.php`.
 *
 * Cada cenário roda num processo à parte com `tests/Fixtures/PagamentosGatewaysHarness.php`:
 * banco simulado em memória e HTTP pelo transporte injetável (nenhuma chamada real à API).
 */
final class MercadoPagoLibraryTest extends TestCase
{
    private function rodar(string $codigo): array
    {
        $script = "<?php\nrequire " . var_export(CONN2FLOW_ROOT . '/tests/Fixtures/PagamentosGatewaysHarness.php', true) . ";\n"
            . $codigo
            . "\necho json_encode(['saida' => \$saida ?? null, 'avisos' => \$GLOBALS['avisos'], 'http' => \$GLOBALS['http'], 'logs' => \$GLOBALS['logs'], 'sqls' => \$GLOBALS['sqls'], 'config' => \$GLOBALS['_CONFIG']]);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req301-');
        try {
            file_put_contents($arquivo, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            $r = json_decode(implode("\n", $linhas), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame([], $r['avisos'], 'Avisos do PHP durante o cenário');
            return $r;
        } finally {
            unlink($arquivo);
        }
    }

    private const CONFIGURAR = '$GLOBALS["gateways"] = [gateway(["id" => "mp-a", "client_secret" => "TEST-tok-a", "webhook_secret" => "seg-a"])];'
        . 'mercadopago_gateways_pagamentos_configurar(["id" => "mp-a"]);';

    public function testConfiguracaoPorIdESemIdPelaPrioridade(): void
    {
        $r = $this->rodar('$GLOBALS["gateways"] = [
            gateway(["id" => "mp-baixa", "prioridade" => 50, "padrao" => "S", "client_secret" => "TEST-baixa"]),
            gateway(["id" => "mp-alta", "prioridade" => 10, "client_secret" => "TEST-alta", "ambiente" => "P"]),
            gateway(["id" => "stripe-x", "tipo" => "stripe", "prioridade" => 1]),
        ];
        $semId = mercadopago_gateways_pagamentos_configurar();
        $a = [mercadopago_obter_gateway_id(), mercadopago_obter_credenciais()];
        $porId = mercadopago_gateways_pagamentos_configurar(["id" => "mp-baixa"]);
        $b = [mercadopago_obter_gateway_id(), mercadopago_obter_credenciais()["access_token"], mercadopago_is_modo_gateway()];
        $inexistente = mercadopago_gateways_pagamentos_configurar(["id" => "stripe-x"]);
        $saida = compact("semId", "a", "porId", "b", "inexistente");');

        $s = $r['saida'];
        self::assertTrue($s['semId']);
        self::assertSame('mp-alta', $s['a'][0], 'Sem id, vale o Mercado Pago de menor prioridade');
        self::assertSame('TEST-alta', $s['a'][1]['access_token']);
        self::assertSame('live', $s['a'][1]['mode']);
        self::assertTrue($s['porId']);
        self::assertSame(['mp-baixa', 'TEST-baixa', true], $s['b']);
        self::assertFalse($s['inexistente'], 'Gateway de outro tipo não configura o Mercado Pago');
    }

    public function testRequisicaoUsaBearerEIdempotenciaSoEmPost(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            resposta(200, ["ok" => 1]); resposta(201, ["id" => 1]); resposta(201, ["id" => 2]);
            $saida = [
                mercadopago_requisicao(["endpoint" => "/v1/payment_methods", "data" => ["a" => "b"]]),
                mercadopago_requisicao(["endpoint" => "/v1/payments", "method" => "POST", "data" => ["x" => 1]]),
                mercadopago_requisicao(["endpoint" => "/v1/payments", "method" => "POST", "data" => [], "idempotency_key" => "chave-fixa"]),
                mercadopago_requisicao(["endpoint" => "sem-barra"]),
            ];');

        [$get, $post, $postFixo] = $r['http'];
        self::assertCount(3, $r['http'], 'Endpoint inválido não chega ao transporte');
        self::assertSame('https://api.mercadopago.com/v1/payment_methods?a=b', $get['url']);
        self::assertSame('GET', $get['method']);
        self::assertContains('Authorization: Bearer TEST-tok-a', $get['headers']);
        self::assertEmpty(preg_grep('/^X-Idempotency-Key/i', $get['headers']), 'GET sem chave de idempotência');
        self::assertNull($get['body']);

        self::assertSame('{"x":1}', $post['body']);
        self::assertContains('Content-Type: application/json', $post['headers']);
        self::assertMatchesRegularExpression('/^X-Idempotency-Key: [0-9a-f]{32}$/', implode("\n", preg_grep('/^X-Idempotency-Key/', $post['headers'])));
        self::assertContains('X-Idempotency-Key: chave-fixa', $postFixo['headers']);
        self::assertSame('{}', $postFixo['body'], 'Corpo vazio vira objeto JSON');

        self::assertSame(['http_code' => 200, 'data' => ['ok' => 1]], $r['saida'][0]);
        self::assertFalse($r['saida'][3]);
    }

    public function testSemCredenciaisNaoChamaApi(): void
    {
        $r = $this->rodar('$saida = [mercadopago_requisicao(["endpoint" => "/v1/payments"]), mercadopago_testar_conexao()];');
        self::assertFalse($r['saida'][0]);
        self::assertSame(['ok' => false, 'http_code' => null, 'error' => 'missing-credentials'], $r['saida'][1]);
        self::assertSame([], $r['http']);
    }

    /** REQ-305: Order de cartão parcelado aprovada (resposta da Orders API v1). */
    private const ORDEM_CARTAO = '["id" => "ORDTST01CARTAO", "type" => "online", "processing_mode" => "automatic", "external_reference" => "ecommerce_3A261110-ABC",
        "total_amount" => "199.91", "total_paid_amount" => "199.91", "status" => "processed", "status_detail" => "accredited",
        "transactions" => ["payments" => [["id" => "PAY01CARTAO", "amount" => "199.91", "paid_amount" => "199.91", "status" => "processed", "status_detail" => "accredited",
            "payment_method" => ["id" => "master", "type" => "credit_card", "installments" => 10]]]]]';

    /** REQ-305: Order Pix aguardando pagamento, com QR Code em `payment_method`. */
    private const ORDEM_PIX = '["id" => "ORDTST01PIX", "external_reference" => "ecommerce:1", "total_amount" => "50.00", "total_paid_amount" => "0.00",
        "status" => "action_required", "status_detail" => "waiting_transfer",
        "transactions" => ["payments" => [["id" => "PAY01PIX", "amount" => "50.00", "status" => "action_required", "status_detail" => "waiting_transfer",
            "date_of_expiration" => "2026-11-24T10:00:00.000-03:00",
            "payment_method" => ["id" => "pix", "type" => "bank_transfer", "qr_code" => "00020126pix", "qr_code_base64" => "iVBOR", "ticket_url" => "https://mp/pix"]]]]]';

    public function testCriarOrdemCartaoParceladoEPixNaOrdersApi(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            resposta(201, ' . self::ORDEM_CARTAO . ');
            resposta(201, ' . self::ORDEM_PIX . ');
            $cartao = mercadopago_criar_pagamento([
                "valor" => "199.905", "payment_method_id" => "master", "token" => "tok_card", "installments" => 15,
                "issuer_id" => "25", "payer" => ["email" => "a@b.com", "first_name" => "APRO", "last_name" => "Teste", "identification" => ["type" => "CPF", "number" => "12345678909"]],
                "referencia" => "ecommerce:261110-ABC", "descricao" => "Pedido", "metadata" => ["pedido" => 9],
                "notification_url" => "https://site/_gateways/orders/mercadopago/webhook", "idempotency_key" => "pedido-9",
            ]);
            $pix = mercadopago_criar_pagamento(["valor" => 50, "payment_method_id" => "pix", "payer" => ["email" => "a@b.com"], "expiration_time" => "PT30M"]);
            $debito = mercadopago_ordem_payload(["valor" => 10, "payment_method_id" => "debvisa", "payment_type" => "debit_card", "token" => "t", "installments" => 6, "payer" => ["email" => "a@b.com"]]);
            $boleto = mercadopago_ordem_payload(["valor" => 10, "payment_method_id" => "bolbradesco", "payer" => ["email" => "a@b.com"]]);
            $pixData = mercadopago_ordem_payload(["valor" => 10, "payment_method_id" => "pix", "payer" => ["email" => "a@b.com"], "date_of_expiration" => date("c", time() + 7200)]);
            $semToken = mercadopago_criar_pagamento(["valor" => 50, "payment_method_id" => "visa", "payer" => ["email" => "a@b.com"]]);
            $semEmail = mercadopago_criar_pagamento(["valor" => 50, "payment_method_id" => "pix", "payer" => []]);
            $valorZero = mercadopago_criar_pagamento(["valor" => 0, "payment_method_id" => "pix", "payer" => ["email" => "a@b.com"]]);
            $normalCartao = mercadopago_ordem_normalizar($cartao);
            $normalPix = mercadopago_ordem_normalizar($pix);
            $saida = compact("cartao", "pix", "debito", "boleto", "pixData", "semToken", "semEmail", "valorZero", "normalCartao", "normalPix");');

        self::assertCount(2, $r['http'], 'Pagamentos inválidos não chegam à API');
        self::assertSame('https://api.mercadopago.com/v1/orders', $r['http'][0]['url']);
        self::assertSame('POST', $r['http'][0]['method']);
        self::assertContains('X-Idempotency-Key: pedido-9', $r['http'][0]['headers']);
        self::assertContains('Authorization: Bearer TEST-tok-a', $r['http'][0]['headers']);

        $corpo = json_decode($r['http'][0]['body'], true);
        self::assertSame('online', $corpo['type']);
        self::assertSame('automatic', $corpo['processing_mode']);
        self::assertSame('199.91', $corpo['total_amount'], 'Valor em string com 2 casas');
        self::assertSame('ecommerce_3A261110-ABC', $corpo['external_reference'], 'Referência codificada no padrão da Orders API');
        self::assertSame('ecommerce:261110-ABC', $r['saida']['cartao']['external_reference'], 'Order devolvida com a referência do Conn2Flow');
        self::assertSame('ecommerce_3A261110-ABC', $r['saida']['cartao']['external_reference_gateway']);
        self::assertSame('Pedido', $corpo['description']);
        self::assertSame(['email' => 'a@b.com', 'first_name' => 'APRO', 'last_name' => 'Teste', 'identification' => ['type' => 'CPF', 'number' => '12345678909']], $corpo['payer']);
        self::assertSame([['amount' => '199.91', 'payment_method' => ['id' => 'master', 'type' => 'credit_card', 'token' => 'tok_card', 'installments' => 12]]],
            $corpo['transactions']['payments'], 'Parcelas limitadas a 12; total = soma dos pagamentos');
        foreach (['metadata', 'notification_url', 'transaction_amount', 'payment_method_id', 'issuer_id'] as $campo) {
            self::assertArrayNotHasKey($campo, $corpo, $campo . ' não existe na Orders API');
        }

        $pix = json_decode($r['http'][1]['body'], true);
        self::assertSame('50.00', $pix['total_amount']);
        self::assertSame(['amount' => '50.00', 'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer'], 'expiration_time' => 'PT30M'], $pix['transactions']['payments'][0]);

        self::assertSame(['id' => 'debvisa', 'type' => 'debit_card', 'token' => 't', 'installments' => 1], $r['saida']['debito']['transactions']['payments'][0]['payment_method'], 'Débito sempre 1x');
        self::assertSame('ticket', $r['saida']['boleto']['transactions']['payments'][0]['payment_method']['type']);
        self::assertMatchesRegularExpression('/^PT1(19|20)M$/', $r['saida']['pixData']['transactions']['payments'][0]['expiration_time'], 'Data absoluta vira duração ISO 8601');

        self::assertSame('ORDTST01CARTAO', $r['saida']['cartao']['id']);
        self::assertFalse($r['saida']['semToken']);
        self::assertFalse($r['saida']['semEmail']);
        self::assertFalse($r['saida']['valorZero']);

        $c = $r['saida']['normalCartao'];
        self::assertSame(['ORDTST01CARTAO', 'PAY01CARTAO', 'paid', 10, 'BRL', 'credit_card'], [$c['id'], $c['payment_id'], $c['canonical_status'], $c['installments'], $c['currency'], $c['payment_type']]);
        self::assertEquals(199.91, $c['amount']);
        self::assertNull($c['pix']);
        $p = $r['saida']['normalPix'];
        self::assertSame('awaiting', $p['canonical_status']);
        self::assertSame(['qr_code' => '00020126pix', 'qr_code_base64' => 'iVBOR', 'ticket_url' => 'https://mp/pix'], $p['pix']);
        self::assertSame('2026-11-24T10:00:00.000-03:00', $p['date_of_expiration']);
    }

    public function testReferenciaExternaReversivelNoPadraoDaOrdersApi(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            $casos = ["ecommerce:261123-PKV7X9", "sub_12:pay-ABC", "ja-valida-123", "a_3A b", "çã/ok"];
            $saida = ["ida" => [], "volta" => []];
            foreach ($casos as $c) { $e = mercadopago_referencia_codificar($c); $saida["ida"][$c] = $e; $saida["volta"][$c] = mercadopago_referencia_decodificar($e); }
            $saida["longa"] = mercadopago_referencia_codificar(str_repeat(":", 22));
            $saida["limite"] = mercadopago_referencia_codificar(str_repeat("a", 64));
            $saida["criarLonga"] = mercadopago_criar_pagamento(["valor" => 10, "payment_method_id" => "pix", "payer" => ["email" => "a@b.com"], "referencia" => str_repeat("r", 65)]);');

        self::assertSame([
            'ecommerce:261123-PKV7X9' => 'ecommerce_3A261123-PKV7X9', 'sub_12:pay-ABC' => 'sub_5F12_3Apay-ABC',
            'ja-valida-123' => 'ja-valida-123', 'a_3A b' => 'a_5F3A_20b', 'çã/ok' => '_C3_A7_C3_A3_2Fok',
        ], $r['saida']['ida']);
        foreach ($r['saida']['ida'] as $codificada) self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{1,64}$/', $codificada);
        self::assertSame(array_combine(array_keys($r['saida']['volta']), array_keys($r['saida']['volta'])), $r['saida']['volta'], 'Ida e volta sem perda, inclusive com "_" no original');
        self::assertFalse($r['saida']['longa'], '22 × ":" viram 66 caracteres');
        self::assertSame(str_repeat('a', 64), $r['saida']['limite']);
        self::assertFalse($r['saida']['criarLonga'], 'Truncar quebraria a prova de posse: recusa sem chamar a API');
        self::assertSame([], $r['http']);
        self::assertStringContainsString('mercadopago-reference-too-long', json_encode($r['logs']));
    }

    public function testConsultaEReembolsoDeOrdemTotalEParcial(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            resposta(200, ' . self::ORDEM_CARTAO . ');
            resposta(201, ["id" => "ORDTST01CARTAO", "status" => "refunded", "status_detail" => "refunded",
                "transactions" => ["refunds" => [["id" => "REF01TOTAL", "transaction_id" => "PAY01CARTAO", "amount" => "199.91", "status" => "processed"]]]]);
            resposta(200, ' . self::ORDEM_CARTAO . ');
            resposta(201, ["id" => "ORDTST01CARTAO", "status" => "processed", "status_detail" => "partially_refunded",
                "transactions" => ["refunds" => [["id" => "REF01PARC", "transaction_id" => "PAY01CARTAO", "amount" => "30.50", "status" => "processed"]]]]);
            resposta(201, ["id" => "ORDTST01CARTAO", "status" => "processed", "status_detail" => "partially_refunded"]);
            $saida = [
                "consulta" => mercadopago_consultar_pagamento(["payment_id" => "ORDTST01CARTAO"]),
                "total" => mercadopago_reembolsar(["payment_id" => "ORDTST01CARTAO", "idempotency_key" => "ref-1"]),
                "parcial" => mercadopago_reembolsar(["payment_id" => "ORDTST01CARTAO", "valor" => 30.499]),
                "parcialComPay" => mercadopago_reembolsar(["payment_id" => "ORDTST01CARTAO", "valor" => 5, "transaction_id" => "PAY01CARTAO"]),
                "ordemInvalida" => mercadopago_consultar_ordem(["order_id" => "123"]),
            ];
            $saida["resumoTotal"] = mercadopago_reembolso_resumo($saida["total"]);
            $saida["resumoParcial"] = mercadopago_reembolso_resumo($saida["parcial"]);
            $saida["resumoSemLista"] = mercadopago_reembolso_resumo($saida["parcialComPay"]);');

        $h = $r['http'];
        self::assertCount(5, $h);
        self::assertSame(['GET', 'https://api.mercadopago.com/v1/orders/ORDTST01CARTAO'], [$h[0]['method'], $h[0]['url']]);
        self::assertSame('https://api.mercadopago.com/v1/orders/ORDTST01CARTAO/refund', $h[1]['url']);
        self::assertSame('{}', $h[1]['body'], 'Reembolso total vai com corpo vazio');
        self::assertContains('X-Idempotency-Key: ref-1', $h[1]['headers']);
        self::assertSame('https://api.mercadopago.com/v1/orders/ORDTST01CARTAO', $h[2]['url'], 'Parcial sem PAY consulta a Order');
        self::assertSame(['transactions' => [['id' => 'PAY01CARTAO', 'amount' => '30.50']]], json_decode($h[3]['body'], true));
        self::assertSame(['transactions' => [['id' => 'PAY01CARTAO', 'amount' => '5.00']]], json_decode($h[4]['body'], true), 'PAY informado dispensa a consulta');

        self::assertSame('ORDTST01CARTAO', $r['saida']['consulta']['id']);
        self::assertFalse($r['saida']['ordemInvalida'], 'Id numérico não é Order');
        self::assertEquals(['id' => 'REF01TOTAL', 'amount' => 199.91, 'status' => 'processed'], $r['saida']['resumoTotal']);
        self::assertEquals(['id' => 'REF01PARC', 'amount' => 30.5, 'status' => 'processed'], $r['saida']['resumoParcial']);
        self::assertSame(['id' => null, 'amount' => null, 'status' => 'processed'], $r['saida']['resumoSemLista']);
    }

    public function testApiLegadaPaymentsPorConfiguracaoDoGateway(): void
    {
        $r = $this->rodar('$GLOBALS["gateways"] = [gateway(["id" => "mp-a", "client_secret" => "TEST-tok-a", "configuracoes" => json_encode(["mercadopago" => ["api" => "payments"]])])];
            mercadopago_gateways_pagamentos_configurar(["id" => "mp-a"]);
            resposta(201, ["id" => 111, "status" => "approved"]);
            resposta(201, ["id" => 222, "status" => "pending", "point_of_interaction" => ["transaction_data" => ["qr_code" => "000201"]]]);
            $cartao = mercadopago_criar_pagamento([
                "valor" => "199.905", "payment_method_id" => "visa", "token" => "tok_card", "installments" => 15,
                "issuer_id" => "25", "payer" => ["email" => "a@b.com", "identification" => ["type" => "CPF", "number" => "1"]],
                "referencia" => "ecommerce:261110-ABC", "descricao" => "Pedido", "metadata" => ["pedido" => 9],
                "notification_url" => "https://site/_gateways/orders/mercadopago/webhook", "idempotency_key" => "pedido-9",
            ]);
            $pix = mercadopago_criar_pagamento(["valor" => 50, "payment_method_id" => "pix", "payer" => ["email" => "a@b.com"]]);
            $semToken = mercadopago_criar_pagamento(["valor" => 50, "payment_method_id" => "visa", "payer" => ["email" => "a@b.com"]]);
            $semEmail = mercadopago_criar_pagamento(["valor" => 50, "payment_method_id" => "pix", "payer" => []]);
            $valorZero = mercadopago_criar_pagamento(["valor" => 0, "payment_method_id" => "pix", "payer" => ["email" => "a@b.com"]]);
            $saida = compact("cartao", "pix", "semToken", "semEmail", "valorZero");');

        self::assertCount(2, $r['http'], 'Pagamentos inválidos não chegam à API');
        $corpo = json_decode($r['http'][0]['body'], true);
        self::assertSame('https://api.mercadopago.com/v1/payments', $r['http'][0]['url']);
        self::assertSame(199.91, $corpo['transaction_amount']);
        self::assertSame('tok_card', $corpo['token']);
        self::assertSame(12, $corpo['installments'], 'Parcelas limitadas a 12');
        self::assertSame(25, $corpo['issuer_id']);
        self::assertSame('ecommerce:261110-ABC', $corpo['external_reference']);
        self::assertSame(['pedido' => 9], $corpo['metadata']);
        self::assertSame('https://site/_gateways/orders/mercadopago/webhook', $corpo['notification_url']);
        self::assertContains('X-Idempotency-Key: pedido-9', $r['http'][0]['headers']);

        $pix = json_decode($r['http'][1]['body'], true);
        self::assertSame('pix', $pix['payment_method_id']);
        self::assertArrayNotHasKey('token', $pix);
        self::assertArrayNotHasKey('installments', $pix);

        self::assertSame(111, $r['saida']['cartao']['id']);
        self::assertSame('000201', $r['saida']['pix']['point_of_interaction']['transaction_data']['qr_code']);
        self::assertFalse($r['saida']['semToken']);
        self::assertFalse($r['saida']['semEmail']);
        self::assertFalse($r['saida']['valorZero']);
    }

    public function testConsultaEReembolsoTotalEParcial(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            resposta(200, ["id" => 123, "status" => "approved"]);
            resposta(201, ["id" => 9001, "amount" => 100]);
            resposta(201, ["id" => 9002, "amount" => 30.5]);
            $saida = [
                mercadopago_consultar_pagamento(["payment_id" => "123"]),
                mercadopago_reembolsar(["payment_id" => "123"]),
                mercadopago_reembolsar(["payment_id" => "123", "valor" => 30.499]),
                mercadopago_consultar_pagamento(["payment_id" => "../users/me"]),
                mercadopago_reembolsar(["payment_id" => ""]),
            ];');

        self::assertCount(3, $r['http']);
        self::assertSame('GET', $r['http'][0]['method']);
        self::assertSame('https://api.mercadopago.com/v1/payments/123', $r['http'][0]['url']);
        self::assertSame('https://api.mercadopago.com/v1/payments/123/refunds', $r['http'][1]['url']);
        self::assertSame('{}', $r['http'][1]['body'], 'Reembolso total vai sem amount');
        self::assertSame(['amount' => 30.5], json_decode($r['http'][2]['body'], true));
        self::assertSame(9002, $r['saida'][2]['id']);
        self::assertFalse($r['saida'][3], 'Id com caminho é recusado');
        self::assertFalse($r['saida'][4]);
    }

    public function testErroDaApiDevolveFalseERegistraSemVazarToken(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            resposta(400, ["error" => "bad_request", "message" => "invalid token", "cause" => [["code" => 2006]]]);
            $GLOBALS["respostas"][] = ["http_code" => 0, "body" => false, "error" => "timeout"];
            $a = mercadopago_criar_pagamento(["valor" => 10, "payment_method_id" => "pix", "payer" => ["email" => "a@b.com"]]);
            $erroA = mercadopago_ultimo_erro();
            $b = mercadopago_consultar_pagamento(["payment_id" => "1"]);
            $erroB = mercadopago_ultimo_erro();
            $saida = compact("a", "erroA", "b", "erroB");');

        self::assertFalse($r['saida']['a']);
        self::assertSame('bad_request', $r['saida']['erroA']['code']);
        self::assertSame(400, $r['saida']['erroA']['http_code']);
        self::assertFalse($r['saida']['b']);
        self::assertSame('network', $r['saida']['erroB']['code']);
        $logs = json_encode($r['logs']);
        self::assertStringContainsString('mercadopago-api-error', $logs);
        self::assertStringNotContainsString('TEST-tok-a', $logs, 'Access Token nunca vai para o log');
        self::assertSame('mercadopago', $r['logs'][0][0]);
    }

    public function testTestarConexaoConfereAmbienteEPar(): void
    {
        $r = $this->rodar('$GLOBALS["gateways"] = [
            gateway(["id" => "prod-com-teste", "ambiente" => "P", "client_secret" => "TEST-1", "client_id" => "APP_USR-1"]),
            gateway(["id" => "par-misto", "ambiente" => "S", "client_secret" => "APP_USR-1", "client_id" => "TEST-1"]),
            gateway(["id" => "ok", "ambiente" => "P", "client_secret" => "APP_USR-tok", "client_id" => "APP_USR-pub"]),
        ];
        $saida = [];
        foreach (["prod-com-teste", "par-misto", "ok", "ok"] as $id) {
            mercadopago_gateways_pagamentos_configurar(["id" => $id]);
            $saida[] = mercadopago_testar_conexao();
        }
        resposta(200, []);');

        // A fila de respostas é preenchida depois: as duas chamadas válidas recebem a resposta padrão 500.
        self::assertSame('environment-mismatch', $r['saida'][0]['error']);
        self::assertSame('key-pair-mismatch', $r['saida'][1]['error']);
        self::assertSame(['ok' => false, 'http_code' => 500, 'error' => 'http-500'], $r['saida'][2]);
        self::assertCount(2, $r['http'], 'Incoerência é detectada sem chamada à API');
        self::assertSame('https://api.mercadopago.com/v1/payment_methods', $r['http'][0]['url']);

        $ok = $this->rodar('$GLOBALS["gateways"] = [gateway(["id" => "ok", "ambiente" => "P", "client_secret" => "APP_USR-tok", "client_id" => "APP_USR-pub"])];
            mercadopago_gateways_pagamentos_configurar(["id" => "ok"]); resposta(200, [["id" => "pix"]]); $saida = mercadopago_testar_conexao();');
        self::assertSame(['ok' => true, 'http_code' => 200, 'error' => null], $ok['saida']);
    }

    public function testWebhookValidaHmacConformeManifestoOficial(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            $ts = (string)time();
            $corpo = json_encode(["action" => "payment.updated", "type" => "payment", "data" => ["id" => "ABC123"], "id" => 777]);
            $valida = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "ABC123", "req-1", $ts), "x_request_id" => "req-1", "payload" => $corpo, "query" => ["data_id" => "ABC123", "type" => "payment"]]);
            $soQuery = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "999", "req-2", $ts), "x_request_id" => "req-2", "payload" => "", "query" => ["data_id" => "999", "type" => "payment"]]);
            $semRequestId = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "999", "", $ts), "payload" => "", "query" => ["data_id" => "999"]]);
            $segredoErrado = mercadopago_validar_webhook(["x_signature" => assinatura_mp("outro", "ABC123", "req-1", $ts), "x_request_id" => "req-1", "payload" => $corpo, "query" => ["data_id" => "ABC123"]]);
            $idTrocado = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "ABC123", "req-1", $ts), "x_request_id" => "req-1", "payload" => $corpo, "query" => ["data_id" => "OUTRO"]]);
            $semHeader = mercadopago_validar_webhook(["payload" => $corpo, "query" => ["data_id" => "ABC123"]]);
            $velho = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "999", "", "1000"), "payload" => "", "query" => ["data_id" => "999"], "tolerancia" => 300]);
            $jsonRuim = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "999", "", $ts), "payload" => "{", "query" => ["data_id" => "999"]]);
            $GLOBALS["_CONFIG"]["mercadopago"]["webhook_secret"] = "";
            $semSegredo = mercadopago_validar_webhook(["x_signature" => assinatura_mp("", "999", "", $ts), "payload" => "", "query" => ["data_id" => "999"]]);
            $saida = compact("valida", "soQuery", "semRequestId", "segredoErrado", "idTrocado", "semHeader", "velho", "jsonRuim", "semSegredo");');

        $s = $r['saida'];
        self::assertSame(777, $s['valida']['id']);
        self::assertSame('ABC123', $s['valida']['data']['id'], 'Corpo preservado; minúsculas só no manifesto');
        self::assertSame(['data' => ['id' => '999'], 'type' => 'payment'], $s['soQuery'], 'Notificação só por query string');
        self::assertIsArray($s['semRequestId'], 'Parte ausente sai do manifesto');
        foreach (['segredoErrado', 'idTrocado', 'semHeader', 'velho', 'jsonRuim', 'semSegredo'] as $caso) {
            self::assertFalse($s[$caso], $caso);
        }
        self::assertStringContainsString('mercadopago-webhook-invalid-signature', json_encode($r['logs']));
    }

    public function testTraducaoDeStatusParaEstadosCanonicos(): void
    {
        $r = $this->rodar('$saida = [];
            foreach (["approved", "authorized", "pending", "in_process", "in_mediation", "rejected", "cancelled", "refunded", "charged_back", "desconhecido", "", "APPROVED"] as $s) $saida[$s ?: "vazio"] = mercadopago_traduzir_status($s);
            $saida["parcial"] = mercadopago_status_canonico_pagamento(["status" => "approved", "transaction_amount" => 100, "transaction_amount_refunded" => 30]);
            $saida["total"] = mercadopago_status_canonico_pagamento(["status" => "approved", "transaction_amount" => 100, "transaction_amount_refunded" => 100]);
            $saida["pago"] = mercadopago_status_canonico_pagamento(["status" => "approved", "transaction_amount" => 100, "transaction_amount_refunded" => 0]);');

        self::assertSame([
            'approved' => 'paid', 'authorized' => 'processing', 'pending' => 'awaiting', 'in_process' => 'processing',
            'in_mediation' => 'processing', 'rejected' => 'failed', 'cancelled' => 'cancelled', 'refunded' => 'refunded',
            'charged_back' => 'refunded', 'desconhecido' => 'processing', 'vazio' => 'awaiting', 'APPROVED' => 'paid',
            'parcial' => 'partially_refunded', 'total' => 'refunded', 'pago' => 'paid',
        ], $r['saida']);
    }

    public function testStatusCanonicosDaOrdersApi(): void
    {
        $r = $this->rodar('$casos = [
                "processed/accredited" => ["processed", "accredited"], "processed/partially_refunded" => ["processed", "partially_refunded"],
                "refunded/refunded" => ["refunded", "refunded"], "action_required/waiting_payment" => ["action_required", "waiting_payment"],
                "action_required/waiting_transfer" => ["action_required", "waiting_transfer"], "action_required/waiting_capture" => ["action_required", "waiting_capture"],
                "action_required/waiting_retry" => ["action_required", "waiting_retry"], "created/created" => ["created", "created"],
                "processing/in_process" => ["processing", "in_process"], "failed/failed" => ["failed", "failed"], "failed/expired" => ["failed", "expired"],
                "canceled/canceled" => ["canceled", "canceled"], "canceled/expired" => ["canceled", "expired"], "charged_back/settled" => ["charged_back", "settled"],
                "rejected/x" => ["rejected", "x"], "novo/x" => ["novo", "x"],
            ];
            $saida = [];
            foreach ($casos as $k => [$st, $det]) $saida[$k] = mercadopago_status_canonico_pagamento(["id" => "ORD1", "status" => $st, "status_detail" => $det, "total_amount" => "100.00"]);
            $refunds = fn($v) => ["transactions" => ["payments" => [["id" => "PAY1"]], "refunds" => [["amount" => $v, "status" => "processed"], ["amount" => "50.00", "status" => "failed"]]]];
            $saida["refundsParcial"] = mercadopago_status_canonico_ordem(["status" => "processed", "status_detail" => "accredited", "total_amount" => "100.00"] + $refunds("30.00"));
            $saida["refundsTotal"] = mercadopago_status_canonico_ordem(["status" => "processed", "status_detail" => "accredited", "total_amount" => "100.00"] + $refunds("100.00"));
            $saida["eOrdem"] = [mercadopago_e_ordem(["id" => "ORD01X"]), mercadopago_e_ordem(["id" => 123]), mercadopago_e_ordem(["transactions" => ["payments" => []]])];
            $saida["ids"] = [mercadopago_id_ordem("ORD01M851JCA"), mercadopago_id_ordem("123"), mercadopago_id_ordem("ORD/../x"), mercadopago_id_ordem(5)];');

        self::assertSame([
            'processed/accredited' => 'paid', 'processed/partially_refunded' => 'partially_refunded', 'refunded/refunded' => 'refunded',
            'action_required/waiting_payment' => 'awaiting', 'action_required/waiting_transfer' => 'awaiting',
            'action_required/waiting_capture' => 'processing', 'action_required/waiting_retry' => 'processing', 'created/created' => 'awaiting',
            'processing/in_process' => 'processing', 'failed/failed' => 'failed', 'failed/expired' => 'cancelled',
            'canceled/canceled' => 'cancelled', 'canceled/expired' => 'cancelled', 'charged_back/settled' => 'refunded',
            'rejected/x' => 'failed', 'novo/x' => 'processing',
            'refundsParcial' => 'partially_refunded', 'refundsTotal' => 'refunded',
            'eOrdem' => [true, false, true], 'ids' => [true, false, false, false],
        ], $r['saida'], 'Status desconhecido nunca vira paid; reembolso falho não conta');
    }

    public function testWebhookDeOrderValidaHmacComIdEmMinusculas(): void
    {
        $r = $this->rodar(self::CONFIGURAR . '
            $ts = (string)(time() * 1000);
            $corpo = json_encode(["action" => "order.processed", "type" => "order", "live_mode" => false, "api_version" => "v1",
                "data" => ["id" => "ORD01M851JCA32683WCG3XHNR66G4", "status" => "processed", "external_reference" => "ecommerce:1"]]);
            $q = ["data_id" => "ORD01M851JCA32683WCG3XHNR66G4", "type" => "order"];
            $valida = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "ORD01M851JCA32683WCG3XHNR66G4", "rq-o", $ts), "x_request_id" => "rq-o", "payload" => $corpo, "query" => $q, "tolerancia" => 300]);
            $maiusculas = mercadopago_validar_webhook(["x_signature" => "ts=" . $ts . ",v1=" . hash_hmac("sha256", "id:ORD01M851JCA32683WCG3XHNR66G4;request-id:rq-o;ts:" . $ts . ";", "seg-a"), "x_request_id" => "rq-o", "payload" => $corpo, "query" => $q]);
            $outraOrdem = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "ORD01OUTRA", "rq-o", $ts), "x_request_id" => "rq-o", "payload" => $corpo, "query" => $q]);
            $soQuery = mercadopago_validar_webhook(["x_signature" => assinatura_mp("seg-a", "ORD01ABC", "", $ts), "payload" => "", "query" => ["data_id" => "ORD01ABC", "type" => "order"]]);
            $saida = compact("valida", "maiusculas", "outraOrdem", "soQuery");');

        $s = $r['saida'];
        self::assertSame('order.processed', $s['valida']['action']);
        self::assertSame('ORD01M851JCA32683WCG3XHNR66G4', $s['valida']['data']['id'], 'Corpo preservado em maiúsculas');
        self::assertFalse($s['maiusculas'], 'Manifesto oficial usa data.id alfanumérico em minúsculas');
        self::assertFalse($s['outraOrdem']);
        self::assertSame(['data' => ['id' => 'ORD01ABC'], 'type' => 'order'], $s['soQuery']);
    }
}
