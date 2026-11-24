<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-301 / BATCH-310 — contrato plug-and-play `gestor/bibliotecas/pagamentos-gateways.php` e
 * ponte canônica em `gestor/controladores/plataforma-gateways/plataforma-gateways.php`.
 *
 * Cada cenário roda num processo à parte com `tests/Fixtures/PagamentosGatewaysHarness.php`:
 * banco simulado, HTTP do Mercado Pago pelo transporte injetável e assinaturas HMAC reais.
 */
final class PagamentosGatewaysInterfaceTest extends TestCase
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

    /** Três gateways fora de ordem: a ordem canônica precisa sair da biblioteca. */
    private const TRES = '$GLOBALS["gateways"] = [
        gateway(["id" => "paypal-1", "tipo" => "paypal", "prioridade" => null]),
        gateway(["id" => "mp-1", "tipo" => "mercadopago", "prioridade" => 20, "configuracoes" => json_encode(["mercadopago" => ["installments" => ["enabled" => true, "max_installments" => 10, "interest_payer" => "merchant"], "render_mode" => "button_first"]])]),
        gateway(["id" => "stripe-1", "tipo" => "stripe", "prioridade" => 20, "padrao" => "S", "client_secret" => "sk_test_1", "webhook_secret" => "whsec_1"]),
        gateway(["id" => "pagbank-1", "tipo" => "pagbank", "prioridade" => 1]),
        gateway(["id" => "inativo", "tipo" => "mercadopago", "prioridade" => 0, "status" => "I"]),
    ];';

    public function testDriversNativosEExtensaoPorFiltro(): void
    {
        $r = $this->rodar('$antes = array_keys(pagamentos_gateways_drivers());
            $GLOBALS["filtros"]["pagamentos.gateways.drivers"] = function ($d) { $d["Novo-Gw"] = ["configurar" => fn() => true]; $d["inv@lido"] = []; $d["sem-array"] = "x"; return $d; };
            $depois = array_keys(pagamentos_gateways_drivers());
            $saida = compact("antes", "depois");');

        self::assertSame(['stripe', 'mercadopago', 'paypal'], $r['saida']['antes']);
        self::assertSame(['stripe', 'mercadopago', 'paypal', 'novo-gw'], $r['saida']['depois']);
    }

    public function testListagemOrdenadaPorPrioridadePadraoEIdComFallbackSemColuna(): void
    {
        $r = $this->rodar(self::TRES . '
            $com = array_column(pagamentos_gateways_listar_ativos(), "id");
            $sqlCom = end($GLOBALS["sqls"]);
            $GLOBALS["_GESTOR"]["pagamentos-gateways"]["tem-prioridade"] = false;
            foreach ($GLOBALS["gateways"] as &$g) unset($g["prioridade"]); unset($g);
            $sem = array_column(pagamentos_gateways_listar_ativos(), "id");
            $sqlSem = end($GLOBALS["sqls"]);
            $saida = compact("com", "sqlCom", "sem", "sqlSem");');

        $s = $r['saida'];
        // stripe-1 e mp-1 empatam em 20: o padrão vem antes; paypal-1 sem prioridade conta como 100.
        self::assertSame(['stripe-1', 'mp-1', 'paypal-1'], $s['com'], 'Sem driver (pagbank) e inativo ficam de fora');
        self::assertStringContainsString("WHERE status = 'A' ORDER BY COALESCE(prioridade, 100) ASC, CASE WHEN padrao = 'S' THEN 0 ELSE 1 END ASC, id_gateways_pagamentos ASC", $s['sqlCom']);
        self::assertSame(['stripe-1', 'paypal-1', 'mp-1'], $s['sem'], 'Sem a coluna: padrão primeiro, depois id numérico');
        self::assertStringNotContainsString('prioridade', $s['sqlSem']);
    }

    public function testFiltrosDeCapacidadeTipoMoedaEAmbiente(): void
    {
        $r = $this->rodar(self::TRES . '
            $saida = [
                "parcela" => array_column(pagamentos_gateways_listar_ativos(["supports_installments" => true]), "id"),
                "recorrente" => array_column(pagamentos_gateways_listar_ativos(["supports_recurring" => true]), "id"),
                "pix" => array_column(pagamentos_gateways_listar_ativos(["supports_pix" => true]), "id"),
                "tipo" => array_column(pagamentos_gateways_listar_ativos(["tipo" => "paypal"]), "id"),
                "usd" => array_column(pagamentos_gateways_listar_ativos(["currency" => "USD"]), "id"),
                "prod" => array_column(pagamentos_gateways_listar_ativos(["ambiente" => "P"]), "id"),
                "cap" => pagamentos_gateways_listar_ativos(["tipo" => "mercadopago"])[0]["_capacidades"]["max_installments"],
            ];');

        self::assertSame(['mp-1'], $r['saida']['parcela']);
        self::assertSame(['stripe-1', 'paypal-1'], $r['saida']['recorrente']);
        self::assertSame(['mp-1'], $r['saida']['pix']);
        self::assertSame(['paypal-1'], $r['saida']['tipo']);
        self::assertSame([], $r['saida']['usd']);
        self::assertSame([], $r['saida']['prod']);
        self::assertSame(10, $r['saida']['cap']);
        self::assertStringContainsString("AND tipo = 'paypal'", implode("\n", $r['sqls']));
    }

    public function testCapacidadesPadronizadasDosTresDrivers(): void
    {
        $r = $this->rodar('$saida = [
            "stripe" => pagamentos_gateway_capacidades(gateway(["tipo" => "stripe", "id" => "s", "configuracoes" => json_encode(["installments" => ["enabled" => true, "max_installments" => 6], "pix" => true, "render_mode" => "xpto"])])),
            "mp" => pagamentos_gateway_capacidades(gateway(["tipo" => "mercadopago", "id" => "m", "prioridade" => "5"])),
            "mpLimite" => pagamentos_gateway_capacidades(gateway(["tipo" => "mercadopago", "configuracoes" => json_encode(["mercadopago" => ["installments" => ["max_installments" => 40, "interest_payer" => "ninguem"], "pix" => "false"]])])),
            "mpDesligado" => pagamentos_gateway_capacidades(gateway(["tipo" => "mercadopago", "configuracoes" => json_encode(["installments" => ["enabled" => "N"]])])),
            "paypal" => pagamentos_gateway_capacidades(gateway(["tipo" => "paypal", "ambiente" => "P"])),
            "paypalParcelado" => pagamentos_gateway_capacidades(gateway(["tipo" => "paypal", "configuracoes" => json_encode(["paypal" => ["installments" => ["enabled" => true, "max_installments" => 3]]])])),
        ];');

        $s = $r['saida'];
        self::assertSame([
            'tipo' => 'stripe', 'gateway_id' => 's', 'supports_installments' => false, 'max_installments' => 1,
            'interest_payer' => 'buyer', 'supports_one_time' => true, 'supports_recurring' => true, 'supports_pix' => true,
            'render_mode' => 'auto', 'priority' => 100, 'currency' => 'BRL', 'ambiente' => 'S',
        ], $s['stripe'], 'Stripe BR nunca parcela, mesmo configurado');
        self::assertTrue($s['mp']['supports_installments']);
        self::assertSame(12, $s['mp']['max_installments']);
        self::assertSame('buyer', $s['mp']['interest_payer']);
        self::assertTrue($s['mp']['supports_pix']);
        self::assertFalse($s['mp']['supports_recurring']);
        self::assertSame(5, $s['mp']['priority']);
        self::assertSame(12, $s['mpLimite']['max_installments'], 'Teto de 12 parcelas');
        self::assertSame('buyer', $s['mpLimite']['interest_payer'], 'Pagador de juros inválido volta ao padrão');
        self::assertFalse($s['mpLimite']['supports_pix']);
        self::assertFalse($s['mpDesligado']['supports_installments']);
        self::assertSame(1, $s['mpDesligado']['max_installments']);
        self::assertFalse($s['paypal']['supports_installments']);
        self::assertTrue($s['paypal']['supports_recurring']);
        self::assertSame('P', $s['paypal']['ambiente']);
        self::assertSame(3, $s['paypalParcelado']['max_installments']);
    }

    public function testIniciarCobrancaMercadoPagoRespeitaCapacidadesEDevolveContrato(): void
    {
        $r = $this->rodar(self::TRES . '
            resposta(201, ["id" => "ORDPIX555", "status" => "action_required", "status_detail" => "waiting_transfer", "total_amount" => "120.00", "external_reference" => "ecommerce:1",
                "transactions" => ["payments" => [["id" => "PAY555", "amount" => "120.00", "status" => "action_required", "status_detail" => "waiting_transfer",
                    "payment_method" => ["id" => "pix", "type" => "bank_transfer", "qr_code" => "pix-copia", "qr_code_base64" => "b64", "ticket_url" => "https://t"]]]]]);
            resposta(201, ["id" => "ORDCARD556", "status" => "failed", "status_detail" => "failed", "total_amount" => "120.00",
                "transactions" => ["payments" => [["id" => "PAY556", "status" => "failed", "status_detail" => "rejected_insufficient_amount", "payment_method" => ["id" => "visa", "type" => "credit_card", "installments" => 10]]]]]);
            $ctx = ["amount" => 120, "reference" => "ecommerce:1", "payment_method_id" => "pix", "payer" => ["email" => "c@d.com"]];
            $saida = [
                "excesso" => pagamentos_gateway_iniciar_cobranca("mp-1", $ctx + ["installments" => 11]),
                "moeda" => pagamentos_gateway_iniciar_cobranca("mp-1", $ctx + ["currency" => "USD"]),
                "zero" => pagamentos_gateway_iniciar_cobranca("mp-1", ["amount" => 0] + $ctx),
                "pix" => pagamentos_gateway_iniciar_cobranca("mp-1", $ctx),
                "recusado" => pagamentos_gateway_iniciar_cobranca("mp-1", ["payment_method_id" => "visa", "token" => "t", "installments" => 10] + $ctx),
                "semDriver" => pagamentos_gateway_iniciar_cobranca("pagbank-1", $ctx),
                "inexistente" => pagamentos_gateway_iniciar_cobranca("nao-existe", $ctx),
            ];');

        $s = $r['saida'];
        self::assertCount(2, $r['http'], 'Recusas de contrato não chegam ao provedor');
        self::assertSame('installments-not-allowed', $s['excesso']['error']);
        self::assertSame('currency-mismatch', $s['moeda']['error']);
        self::assertSame('invalid-amount', $s['zero']['error']);
        self::assertTrue($s['pix']['ok']);
        self::assertSame('mercadopago', $s['pix']['gateway_type']);
        self::assertSame('mp-1', $s['pix']['gateway_id']);
        self::assertSame('ORDPIX555', $s['pix']['gateway_payment_id'], 'O id guardado é o da Order');
        self::assertSame('ORDPIX555', $s['pix']['gateway_order_id']);
        self::assertSame('awaiting', $s['pix']['canonical_status']);
        self::assertSame(['qr_code' => 'pix-copia', 'qr_code_base64' => 'b64', 'ticket_url' => 'https://t'], $s['pix']['pix']);
        self::assertSame('https://api.mercadopago.com/v1/orders', $r['http'][0]['url']);
        $cartao = json_decode($r['http'][1]['body'], true);
        self::assertSame(['id' => 'visa', 'type' => 'credit_card', 'token' => 't', 'installments' => 10], $cartao['transactions']['payments'][0]['payment_method']);
        self::assertFalse($s['recusado']['ok']);
        self::assertSame('failed', $s['recusado']['canonical_status']);
        self::assertSame('payment-rejected_insufficient_amount', $s['recusado']['error'], 'Motivo vem do pagamento da Order');
        self::assertSame('driver-unavailable', $s['semDriver']['error']);
        self::assertSame('driver-unavailable', $s['inexistente']['error']);
    }

    public function testSincronizarComProvaDePosseEReembolso(): void
    {
        $pagamento = '["id" => 777, "status" => "approved", "transaction_amount" => 99.9, "currency_id" => "BRL", "installments" => 3, "external_reference" => "ecommerce:9", "transaction_amount_refunded" => 10, "order" => ["id" => 42]]';
        $r = $this->rodar(self::TRES . '
            resposta(200, ' . $pagamento . '); resposta(200, ' . $pagamento . '); resposta(200, ' . $pagamento . '); resposta(200, ' . $pagamento . ');
            resposta(201, ["id" => 31, "amount" => 10, "status" => "approved"]);
            $base = ["gateway_payment_id" => "777"];
            $saida = [
                "ok" => pagamentos_gateway_sincronizar("mp-1", $base + ["expected_amount" => 99.90, "expected_currency" => "brl", "expected_reference" => "ecommerce:9"]),
                "valor" => pagamentos_gateway_sincronizar("mp-1", $base + ["expected_amount" => 9.99]),
                "moeda" => pagamentos_gateway_sincronizar("mp-1", $base + ["expected_currency" => "USD"]),
                "referencia" => pagamentos_gateway_sincronizar("mp-1", $base + ["expected_reference" => "ecommerce:10"]),
                "semId" => pagamentos_gateway_sincronizar("mp-1", []),
                "reembolso" => pagamentos_gateway_reembolsar("mp-1", ["gateway_payment_id" => "777", "amount" => 10]),
                "reembolsoInvalido" => pagamentos_gateway_reembolsar("mp-1", ["gateway_payment_id" => "777", "amount" => -1]),
            ];');

        $s = $r['saida'];
        // assertEquals: a volta por JSON entre processos transforma 10.0 em 10.
        self::assertEquals([
            'ok' => true, 'canonical_status' => 'partially_refunded', 'gateway_type' => 'mercadopago', 'gateway_id' => 'mp-1',
            'gateway_payment_id' => '777', 'gateway_order_id' => '42', 'gateway_subscription_id' => null, 'amount' => 99.9,
            'currency' => 'BRL', 'installments' => 3, 'refunded_amount' => 10.0, 'reference' => 'ecommerce:9', 'error' => null,
        ], array_diff_key($s['ok'], ['raw' => 1]));
        self::assertSame(777, $s['ok']['raw']['id']);
        self::assertSame([false, 'amount-mismatch'], [$s['valor']['ok'], $s['valor']['error']]);
        self::assertSame([false, 'currency-mismatch'], [$s['moeda']['ok'], $s['moeda']['error']]);
        self::assertSame([false, 'reference-mismatch'], [$s['referencia']['ok'], $s['referencia']['error']]);
        self::assertSame('missing-payment-id', $s['semId']['error']);
        self::assertTrue($s['reembolso']['ok']);
        self::assertSame('31', $s['reembolso']['refund_id']);
        self::assertEquals(10.0, $s['reembolso']['refunded_amount']);
        self::assertSame(['amount' => 10], json_decode($r['http'][4]['body'], true));
        self::assertSame('invalid-amount', $s['reembolsoInvalido']['error']);
        self::assertCount(5, $r['http']);
    }

    /** REQ-305: Order da Orders API v1 sem campo de moeda (BR), com reembolso parcial processado. */
    private const ORDEM = '["id" => "ORD01SYNC", "status" => "processed", "status_detail" => "partially_refunded", "total_amount" => "99.90", "total_paid_amount" => "99.90",
        "external_reference" => "ecommerce_3A9", "transactions" => [
            "payments" => [["id" => "PAY01SYNC", "amount" => "99.90", "status" => "processed", "payment_method" => ["id" => "master", "type" => "credit_card", "installments" => 3]]],
            "refunds" => [["id" => "REF01", "transaction_id" => "PAY01SYNC", "amount" => "10.00", "status" => "processed"]]]]';

    public function testSincronizarEReembolsarOrdemDaOrdersApi(): void
    {
        $r = $this->rodar(self::TRES . '
            resposta(200, ' . self::ORDEM . '); resposta(200, ' . self::ORDEM . ');
            resposta(201, ["id" => "ORD01SYNC", "status" => "processed", "status_detail" => "partially_refunded",
                "transactions" => ["refunds" => [["id" => "REF01", "amount" => "10.00", "status" => "processed"], ["id" => "REF02", "amount" => "20.00", "status" => "processed"]]]]);
            resposta(201, ["id" => "ORD01SYNC", "status" => "refunded", "transactions" => ["refunds" => [["id" => "REF03", "amount" => "69.90", "status" => "failed"]]]]);
            $base = ["gateway_payment_id" => "ORD01SYNC"];
            $saida = [
                "ok" => pagamentos_gateway_sincronizar("mp-1", $base + ["expected_amount" => 99.90, "expected_currency" => "BRL", "expected_reference" => "ecommerce:9"]),
                "moeda" => pagamentos_gateway_sincronizar("mp-1", $base + ["expected_currency" => "USD"]),
                "parcial" => pagamentos_gateway_reembolsar("mp-1", $base + ["amount" => 20, "gateway_transaction_id" => "PAY01SYNC"]),
                "falho" => pagamentos_gateway_reembolsar("mp-1", $base),
            ];');

        $s = $r['saida'];
        self::assertEquals([
            'ok' => true, 'canonical_status' => 'partially_refunded', 'gateway_type' => 'mercadopago', 'gateway_id' => 'mp-1',
            'gateway_payment_id' => 'ORD01SYNC', 'gateway_order_id' => 'ORD01SYNC', 'gateway_subscription_id' => null, 'amount' => 99.9,
            'currency' => 'BRL', 'installments' => 3, 'refunded_amount' => 10.0, 'reference' => 'ecommerce:9', 'error' => null,
        ], array_diff_key($s['ok'], ['raw' => 1]), 'Moeda ausente na Order vem do gateway');
        self::assertSame('PAY01SYNC', $s['ok']['raw']['transactions']['payments'][0]['id']);
        self::assertSame([false, 'currency-mismatch'], [$s['moeda']['ok'], $s['moeda']['error']]);
        self::assertSame('https://api.mercadopago.com/v1/orders/ORD01SYNC', $r['http'][0]['url']);

        self::assertTrue($s['parcial']['ok']);
        self::assertSame('REF02', $s['parcial']['refund_id'], 'Último reembolso da lista');
        self::assertEquals(20.0, $s['parcial']['refunded_amount']);
        self::assertSame('https://api.mercadopago.com/v1/orders/ORD01SYNC/refund', $r['http'][2]['url']);
        self::assertSame(['transactions' => [['id' => 'PAY01SYNC', 'amount' => '20.00']]], json_decode($r['http'][2]['body'], true));
        self::assertSame('{}', $r['http'][3]['body']);
        self::assertFalse($s['falho']['ok']);
        self::assertSame('refund-failed', $s['falho']['error']);
        self::assertCount(4, $r['http']);
    }

    public function testWebhookDeOrderValidaHmacEBuscaOrdemAutenticada(): void
    {
        $r = $this->rodar('$GLOBALS["gateways"] = [
                gateway(["id" => "mp-loja-a", "prioridade" => 1, "webhook_secret" => "seg-a", "client_secret" => "TEST-a"]),
                gateway(["id" => "mp-loja-b", "prioridade" => 2, "webhook_secret" => "seg-b", "client_secret" => "TEST-b"]),
            ];
            $ts = (string)(time() * 1000);
            $id = "ORD01M851JCA32683WCG3XHNR66G4";
            // O corpo diz "processed", mas sem consulta autenticada nada é inferido: a API responde "canceled".
            $corpo = json_encode(["action" => "order.processed", "type" => "order", "live_mode" => false, "user_id" => "1",
                "data" => ["id" => "ORDBODY", "status" => "processed", "external_reference" => "forjado"],
                "_resource" => ["id" => "ORDBODY", "status" => "processed", "external_reference" => "forjado"]]);
            $h = ["x-signature" => assinatura_mp("seg-b", $id, "rq", $ts), "x-request-id" => "rq"];
            resposta(200, ["id" => $id, "status" => "canceled", "status_detail" => "expired", "total_amount" => "50.00", "external_reference" => "subId:payKey",
                "transactions" => ["payments" => [["id" => "PAY01", "status" => "canceled"]]]]);
            resposta(200, ["id" => $id, "status" => "processed", "status_detail" => "accredited", "total_amount" => "50.00", "external_reference" => "ecommerce:7",
                "transactions" => ["payments" => [["id" => "PAY01", "status" => "processed"]]]]);
            $cancelada = pagamentos_gateway_validar_e_normalizar_webhook("mercadopago", $corpo, $h, ["data_id" => $id, "type" => "order"]);
            $paga = plataforma_gateways_envelope_canonico("mercadopago", $corpo, $h, ["data_id" => $id, "type" => "order", "gateway_id" => "mp-loja-b"]);
            resposta(503, []);
            $indisponivel = plataforma_gateways_envelope_canonico("mercadopago", $corpo, $h, ["data_id" => $id, "type" => "order"]);
            resposta(200, ["id" => "ORDBODY", "status" => "processed"]);
            $porTopico = json_decode($corpo, true);
            unset($porTopico["type"]);
            $porTopico["topic"] = "order";
            $divergente = pagamentos_gateway_validar_e_normalizar_webhook("mercadopago", json_encode($porTopico), $h, ["data_id" => $id]);
            $invalido = pagamentos_gateway_validar_e_normalizar_webhook("mercadopago", $corpo, ["x-signature" => "ts=1,v1=00"], ["data_id" => $id, "type" => "order"]);
            $semRecurso = pagamentos_gateway_normalizar_evento("mercadopago", ["type" => "order", "action" => "order.refunded", "data" => ["id" => strtolower($id)]], [], true, "mp-loja-b");
            $saida = compact("cancelada", "paga", "indisponivel", "divergente", "invalido", "semRecurso");');

        $s = $r['saida'];
        $c = $s['cancelada'];
        self::assertTrue($c['valid']);
        self::assertSame('mp-loja-b', $c['gateway_id']);
        self::assertSame('order.processed', $c['event_type']);
        self::assertSame('payment.cancelled', $c['canonical_event'], 'Estado vem do GET /v1/orders, não do corpo');
        self::assertSame('subId:payKey', $c['reference']);
        self::assertSame(['ORD01M851JCA32683WCG3XHNR66G4', 'ORD01M851JCA32683WCG3XHNR66G4'], [$c['gateway_payment_id'], $c['gateway_order_id']]);
        self::assertSame(array_keys($c), ['valid', 'gateway_type', 'gateway_id', 'event_id', 'event_type', 'canonical_event', 'reference', 'gateway_payment_id', 'gateway_order_id', 'gateway_subscription_id', 'resource', 'event_data', 'error']);
        self::assertArrayNotHasKey('_resource', $c['event_data']);
        self::assertSame('https://api.mercadopago.com/v1/orders/ORD01M851JCA32683WCG3XHNR66G4', $r['http'][0]['url']);
        self::assertContains('Authorization: Bearer TEST-b', $r['http'][0]['headers']);

        self::assertSame(['payment.paid', 'ecommerce:7', true], [$s['paga']['canonical_event'], $s['paga']['reference'], $s['paga']['valid']], 'Ponte do controlador entrega o envelope aos módulos');
        self::assertSame($c['gateway_order_id'], $s['paga']['gateway_order_id']);
        foreach ([$s['indisponivel'], $s['divergente']] as $falha) {
            self::assertSame([false, 'order-unavailable', 'other', null, []], [
                $falha['valid'], $falha['error'], $falha['canonical_event'], $falha['reference'], $falha['resource'],
            ]);
        }
        self::assertFalse($s['invalido']['valid']);
        self::assertSame('other', $s['invalido']['canonical_event']);
        self::assertNull($s['invalido']['reference'], 'Referência do corpo não autenticado é ignorada');
        self::assertCount(4, $r['http'], 'Assinatura inválida não consulta a API');
        self::assertSame('ORD01M851JCA32683WCG3XHNR66G4', $s['semRecurso']['gateway_payment_id'], 'Id de Order volta para maiúsculas');
        self::assertSame('other', $s['semRecurso']['canonical_event']);
    }

    public function testLinksDePainelPorDriverEAmbiente(): void
    {
        $r = $this->rodar('$saida = [
            pagamentos_gateway_dashboard_url(gateway(["tipo" => "stripe"]), "pi_123"),
            pagamentos_gateway_dashboard_url(gateway(["tipo" => "stripe", "ambiente" => "P"]), "pi_123"),
            pagamentos_gateway_dashboard_url(gateway(["tipo" => "paypal"]), "CAP1"),
            pagamentos_gateway_dashboard_url(gateway(["tipo" => "paypal", "ambiente" => "P"]), "CAP1"),
            pagamentos_gateway_dashboard_url(gateway(["tipo" => "mercadopago"]), "777"),
            pagamentos_gateway_dashboard_url(gateway(["tipo" => "mercadopago"]), "<script>"),
            pagamentos_gateway_dashboard_url(gateway(["tipo" => "pagbank"]), "1"),
        ];');

        self::assertSame([
            'https://dashboard.stripe.com/test/payments/pi_123',
            'https://dashboard.stripe.com/payments/pi_123',
            'https://www.sandbox.paypal.com/activity/payment/CAP1',
            'https://www.paypal.com/activity/payment/CAP1',
            'https://www.mercadopago.com.br/activities?q=777',
            null,
            null,
        ], $r['saida']);
    }

    public function testDriverDeTerceiroEntraSemTocarNoCore(): void
    {
        $r = $this->rodar('$GLOBALS["filtros"]["pagamentos.gateways.drivers"] = function ($d) {
                $d["acme"] = [
                    "configurar" => function ($linha) { $GLOBALS["acme"][] = "config:" . $linha["id"]; return true; },
                    "testar" => fn($l) => ["ok" => true],
                    "iniciar" => fn($l, $c, $cap) => ["ok" => true, "gateway_payment_id" => "acme-" . $c["amount"], "canonical_status" => "processing"],
                    "sincronizar" => fn($l, $c) => ["ok" => true, "canonical_status" => "inventado", "gateway_payment_id" => $c["gateway_payment_id"], "amount" => 10, "currency" => "brl"],
                    "normalizar_webhook" => fn($e, $q) => ["event_type" => $e["tipo"], "canonical_event" => "payment.paid", "reference" => $e["ref"]],
                    "validar_webhook" => fn($raw, $h, $q) => ($h["X-Acme"] ?? "") === "ok" ? json_decode($raw, true) : false,
                    "validacao_local" => true,
                    "capacidades" => fn($l, $cfg) => ["supports_installments" => true, "max_installments" => 4],
                ];
                return $d;
            };
            $GLOBALS["gateways"] = [gateway(["id" => "acme-1", "tipo" => "acme"])];
            $corpo = json_encode(["tipo" => "pago", "ref" => "r-1"]);
            $saida = [
                "lista" => array_column(pagamentos_gateways_listar_ativos(["supports_installments" => true]), "id"),
                "testar" => pagamentos_gateway_testar("acme-1"),
                "iniciar" => pagamentos_gateway_iniciar_cobranca("acme-1", ["amount" => 10, "installments" => 4]),
                "sync" => pagamentos_gateway_sincronizar("acme-1", ["gateway_payment_id" => "acme-10", "expected_amount" => 10]),
                "webhook" => pagamentos_gateway_validar_e_normalizar_webhook("acme", $corpo, ["X-Acme" => "ok"]),
                "webhookFalso" => pagamentos_gateway_validar_e_normalizar_webhook("acme", $corpo, []),
                "conhecidos" => plataforma_gateways_conhecidos(),
                "chamadas" => $GLOBALS["acme"],
            ];');

        $s = $r['saida'];
        self::assertSame(['acme-1'], $s['lista']);
        self::assertTrue($s['testar']['ok']);
        self::assertSame('acme-10', $s['iniciar']['gateway_payment_id']);
        self::assertSame('acme', $s['iniciar']['gateway_type']);
        self::assertTrue($s['sync']['ok']);
        self::assertSame('processing', $s['sync']['canonical_status'], 'Estado fora do contrato vira processing');
        self::assertSame('BRL', $s['sync']['currency']);
        self::assertTrue($s['webhook']['valid']);
        self::assertSame('acme-1', $s['webhook']['gateway_id']);
        self::assertSame(['payment.paid', 'r-1', 'pago'], [$s['webhook']['canonical_event'], $s['webhook']['reference'], $s['webhook']['event_type']]);
        self::assertFalse($s['webhookFalso']['valid']);
        self::assertSame('invalid-signature', $s['webhookFalso']['error']);
        self::assertContains('acme', $s['conhecidos']);
        self::assertContains('mercadopago', $s['conhecidos']);
    }

    public function testWebhookMercadoPagoEscolheGatewayPelaAssinaturaEBuscaPagamentoAutenticado(): void
    {
        $r = $this->rodar('$GLOBALS["gateways"] = [
                gateway(["id" => "mp-loja-a", "prioridade" => 1, "webhook_secret" => "seg-a", "client_secret" => "TEST-a"]),
                gateway(["id" => "mp-loja-b", "prioridade" => 2, "webhook_secret" => "seg-b", "client_secret" => "TEST-b"]),
            ];
            $ts = (string)time();
            $corpo = json_encode(["action" => "payment.updated", "type" => "payment", "data" => ["id" => "123"], "id" => 9876, "live_mode" => false]);
            $h = ["X-Signature" => assinatura_mp("seg-b", "123", "rq", $ts), "x-request-id" => "rq"];
            resposta(200, ["id" => 123, "status" => "approved", "transaction_amount" => 50, "currency_id" => "BRL", "external_reference" => "subId:payKey"]);
            $GLOBALS["_CONFIG"]["mercadopago"] = ["marcador" => "intacto"];
            $valido = pagamentos_gateway_validar_e_normalizar_webhook("mercadopago", $corpo, $h, ["data_id" => "123", "type" => "payment"]);
            $configDepois = $GLOBALS["_CONFIG"]["mercadopago"];
            $gatewayDepois = $GLOBALS["_GESTOR"]["biblioteca-mercadopago"]["gateway-id"] ?? null;
            $invalido = pagamentos_gateway_validar_e_normalizar_webhook("mercadopago", $corpo, ["x-signature" => "ts=1,v1=00"], ["data_id" => "123"]);
            $GLOBALS["gateways"] = [];
            $semGateway = pagamentos_gateway_validar_e_normalizar_webhook("mercadopago", $corpo, $h, []);
            $desconhecido = pagamentos_gateway_validar_e_normalizar_webhook("pagbank", $corpo, [], []);
            $saida = compact("valido", "configDepois", "gatewayDepois", "invalido", "semGateway", "desconhecido");');

        $s = $r['saida'];
        $v = $s['valido'];
        self::assertTrue($v['valid']);
        self::assertSame('mp-loja-b', $v['gateway_id'], 'O gateway é o dono da assinatura, não o de maior prioridade');
        self::assertSame('9876', $v['event_id']);
        self::assertSame('payment.updated', $v['event_type']);
        self::assertSame('payment.paid', $v['canonical_event'], 'Estado vem do GET autenticado');
        self::assertSame('subId:payKey', $v['reference']);
        self::assertSame('123', $v['gateway_payment_id']);
        self::assertSame(50, $v['resource']['transaction_amount']);
        self::assertArrayNotHasKey('_resource', $v['event_data']);
        self::assertSame(array_keys($v), ['valid', 'gateway_type', 'gateway_id', 'event_id', 'event_type', 'canonical_event', 'reference', 'gateway_payment_id', 'gateway_order_id', 'gateway_subscription_id', 'resource', 'event_data', 'error']);
        self::assertCount(1, $r['http']);
        self::assertSame('https://api.mercadopago.com/v1/payments/123', $r['http'][0]['url']);
        self::assertContains('Authorization: Bearer TEST-b', $r['http'][0]['headers']);
        self::assertSame(['marcador' => 'intacto'], $s['configDepois'], 'Validação não vaza configuração');
        self::assertNull($s['gatewayDepois']);

        self::assertFalse($s['invalido']['valid']);
        self::assertSame('invalid-signature', $s['invalido']['error']);
        self::assertSame('123', $s['invalido']['gateway_payment_id']);
        self::assertSame('other', $s['invalido']['canonical_event'], 'Sem validação não há estado inferido');
        self::assertSame('no-active-gateway', $s['semGateway']['error']);
        self::assertSame('driver-unknown', $s['desconhecido']['error']);
    }

    public function testWebhookStripeValidadoENormalizado(): void
    {
        $r = $this->rodar('$GLOBALS["gateways"] = [
                gateway(["id" => "stripe-test", "tipo" => "stripe", "ambiente" => "S", "client_secret" => "sk_test", "webhook_secret" => "whsec_test", "prioridade" => 1]),
                gateway(["id" => "stripe-live", "tipo" => "stripe", "ambiente" => "P", "client_secret" => "sk_live", "webhook_secret" => "whsec_live", "prioridade" => 2]),
            ];
            $ev = fn($tipo, $obj, $live = true) => json_encode(["id" => "evt_1", "type" => $tipo, "livemode" => $live, "data" => ["object" => $obj]]);
            $pago = $ev("payment_intent.succeeded", ["id" => "pi_A1", "metadata" => ["referencia" => "ecommerce:7"]]);
            $saida = [
                "pago" => pagamentos_gateway_validar_e_normalizar_webhook("stripe", $pago, ["Stripe-Signature" => assinatura_stripe("whsec_live", $pago)]),
                "parcial" => ($p = $ev("charge.refunded", ["id" => "ch_B2", "payment_intent" => "pi_A1", "amount" => 1000, "amount_refunded" => 300])) ? pagamentos_gateway_validar_e_normalizar_webhook("stripe", $p, ["stripe-signature" => assinatura_stripe("whsec_live", $p)]) : null,
                "fatura" => pagamentos_gateway_normalizar_evento("stripe", json_decode($ev("invoice.paid", ["id" => "in_C3", "parent" => ["subscription_details" => ["subscription" => "sub_D4", "metadata" => ["referencia" => "sub:1"]]], "payment_intent" => "pi_E5"]), true)),
                "cancelada" => pagamentos_gateway_normalizar_evento("stripe", ["id" => "evt_2", "type" => "customer.subscription.deleted", "data" => ["object" => ["id" => "sub_D4"]]]),
                "falso" => pagamentos_gateway_validar_e_normalizar_webhook("stripe", $pago, ["Stripe-Signature" => assinatura_stripe("outro", $pago)]),
            ];');

        $s = $r['saida'];
        self::assertTrue($s['pago']['valid']);
        self::assertSame('stripe-live', $s['pago']['gateway_id'], 'livemode direciona ao gateway de produção');
        self::assertSame(['payment.paid', 'ecommerce:7', 'pi_A1', 'evt_1'], [$s['pago']['canonical_event'], $s['pago']['reference'], $s['pago']['gateway_payment_id'], $s['pago']['event_id']]);
        self::assertSame(['payment.partially_refunded', 'pi_A1'], [$s['parcial']['canonical_event'], $s['parcial']['gateway_payment_id']]);
        self::assertSame(['subscription.payment_succeeded', 'sub_D4', 'in_C3', 'pi_E5', 'sub:1'], [$s['fatura']['canonical_event'], $s['fatura']['gateway_subscription_id'], $s['fatura']['gateway_order_id'], $s['fatura']['gateway_payment_id'], $s['fatura']['reference']]);
        self::assertFalse($s['fatura']['valid']);
        self::assertSame(['subscription.cancelled', 'sub_D4'], [$s['cancelada']['canonical_event'], $s['cancelada']['gateway_subscription_id']]);
        self::assertFalse($s['falso']['valid']);
        self::assertSame('invalid-signature', $s['falso']['error']);
        self::assertArrayNotHasKey('stripe', $r['config'], 'Configuração do Stripe restaurada');
    }

    public function testNormalizacaoPayPal(): void
    {
        $r = $this->rodar('$n = fn($tipo, $res) => pagamentos_gateway_normalizar_evento("paypal", ["id" => "WH-1", "event_type" => $tipo, "resource" => $res]);
            $saida = [
                "captura" => $n("PAYMENT.CAPTURE.COMPLETED", ["id" => "CAP1", "custom_id" => "ecommerce:3", "supplementary_data" => ["related_ids" => ["order_id" => "ORD1"]]]),
                "parcial" => $n("PAYMENT.CAPTURE.REFUNDED", ["id" => "CAP1", "status" => "PARTIALLY_REFUNDED"]),
                "negada" => $n("PAYMENT.CAPTURE.DENIED", ["id" => "CAP2"]),
                "pedido" => $n("CHECKOUT.ORDER.APPROVED", ["id" => "ORD2", "purchase_units" => [["custom_id" => "ecommerce:4"]]]),
                "cancelada" => $n("BILLING.SUBSCRIPTION.CANCELLED", ["id" => "I-SUB"]),
                "mensalidade" => $n("PAYMENT.SALE.COMPLETED", ["id" => "SALE1", "billing_agreement_id" => "I-SUB"]),
                "outro" => $n("CUSTOMER.DISPUTE.CREATED", ["id" => "D1"]),
            ];');

        $s = $r['saida'];
        self::assertSame(['payment.paid', 'CAP1', 'ORD1', 'ecommerce:3', 'PAYMENT.CAPTURE.COMPLETED', 'WH-1'], [$s['captura']['canonical_event'], $s['captura']['gateway_payment_id'], $s['captura']['gateway_order_id'], $s['captura']['reference'], $s['captura']['event_type'], $s['captura']['event_id']]);
        self::assertSame('payment.partially_refunded', $s['parcial']['canonical_event']);
        self::assertSame('payment.failed', $s['negada']['canonical_event']);
        self::assertSame(['payment.processing', 'ORD2', 'ecommerce:4'], [$s['pedido']['canonical_event'], $s['pedido']['gateway_order_id'], $s['pedido']['reference']]);
        self::assertSame(['subscription.cancelled', 'I-SUB'], [$s['cancelada']['canonical_event'], $s['cancelada']['gateway_subscription_id']]);
        self::assertSame(['subscription.payment_succeeded', 'SALE1', 'I-SUB'], [$s['mensalidade']['canonical_event'], $s['mensalidade']['gateway_payment_id'], $s['mensalidade']['gateway_subscription_id']]);
        self::assertSame('other', $s['outro']['canonical_event']);
    }

    public function testPonteResolveRotasEMontaEnvelopeSemQuebrarLegado(): void
    {
        $r = $this->rodar('$k = plataforma_gateways_conhecidos();
            $GLOBALS["gateways"] = [gateway(["id" => "mp-1", "webhook_secret" => "seg"])];
            $ts = (string)time();
            $corpoPaypal = json_encode(["id" => "WH-9", "event_type" => "PAYMENT.CAPTURE.COMPLETED", "resource" => ["id" => "CAP9"]]);
            $corpoMp = json_encode(["type" => "payment", "data" => ["id" => "55"]]);
            resposta(200, ["id" => 55, "status" => "rejected"]);
            $saida = [
                "conhecidos" => $k,
                "legacy" => plataforma_gateways_resolver_rota(["_gateways", "mercadopago", "webhook"], $k),
                "modular" => plataforma_gateways_resolver_rota(["_gateways", "orders", "MercadoPago", "return"], $k),
                "direto" => plataforma_gateways_resolver_rota(["_gateways", "orders", "status-pedido"], $k),
                "stripe" => plataforma_gateways_resolver_rota(["_gateways", "subscriptions", "stripe", "webhook"], $k),
                "paypalModular" => plataforma_gateways_envelope_canonico("paypal", $corpoPaypal, [], []),
                "paypalValidado" => plataforma_gateways_envelope_canonico("paypal", $corpoPaypal, [], [], json_decode($corpoPaypal, true), "pp-1"),
                "mp" => plataforma_gateways_envelope_canonico("mercadopago", $corpoMp, ["x-signature" => assinatura_mp("seg", "55", "", $ts)], ["data_id" => "55", "gateway_id" => "mp-1"]),
                "lixo" => plataforma_gateways_envelope_canonico("paypal", "{nao-json", [], []),
            ];');

        $s = $r['saida'];
        foreach (['paypal', 'stripe', 'mercadopago', 'pagbank', 'pagseguro'] as $g) self::assertContains($g, $s['conhecidos']);
        self::assertSame(['modulo' => null, 'gateway' => 'mercadopago', 'endpoint' => 'webhook'], $s['legacy']);
        self::assertSame(['modulo' => 'orders', 'gateway' => 'mercadopago', 'endpoint' => 'return'], $s['modular']);
        self::assertSame(['modulo' => 'orders', 'gateway' => 'status-pedido', 'endpoint' => null], $s['direto']);
        self::assertSame(['modulo' => 'subscriptions', 'gateway' => 'stripe', 'endpoint' => 'webhook'], $s['stripe']);

        self::assertFalse($s['paypalModular']['valid'], 'PayPal valida remotamente: o Core só normaliza');
        self::assertSame('payment.paid', $s['paypalModular']['canonical_event']);
        self::assertCount(1, $r['http'], 'Só o GET autenticado do Mercado Pago; nenhuma chamada ao PayPal');
        self::assertTrue($s['paypalValidado']['valid']);
        self::assertSame('pp-1', $s['paypalValidado']['gateway_id']);
        self::assertTrue($s['mp']['valid']);
        self::assertSame('payment.failed', $s['mp']['canonical_event']);
        self::assertSame('other', $s['lixo']['canonical_event']);
        self::assertSame([], $s['lixo']['event_data']);
    }

    public function testControladorPreservaCamposLegadosEInjetaCanonico(): void
    {
        $fonte = (string)file_get_contents(CONN2FLOW_ROOT . '/gestor/controladores/plataforma-gateways/plataforma-gateways.php');

        foreach (['event_type', 'resource', 'event_data', 'needs_validation', 'raw_body', 'raw_headers', 'signature_header', 'query_string'] as $campo) {
            self::assertStringContainsString("'" . $campo . "'", $fonte, 'Campo legado ' . $campo);
        }
        self::assertSame(6, substr_count($fonte, 'plataforma_gateways_envelope_canonico('), 'Definição + Stripe (2) + PayPal (2) + universal');
        self::assertStringContainsString("'canonical'        => \$canonical", $fonte, 'Handler universal entrega o envelope');
        self::assertStringContainsString('plataforma_gateways_webhook_universal($gateway)', $fonte);
        self::assertStringContainsString("case 'cancel':", $fonte);
        // A confirmação do Stripe continua autocontida (REQ-265 a extrai isoladamente).
        self::assertMatchesRegularExpression('/function plataforma_gateways_stripe_confirmar_recebimento\(\$resultado\)\{\r?\n    if \(!empty\(\$resultado\[\'processed\'\]\)\) return;/', $fonte);
    }
}
