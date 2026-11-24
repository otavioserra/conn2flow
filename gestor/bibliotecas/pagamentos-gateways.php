<?php
/**
 * Contrato universal plug-and-play de gateways de pagamento (REQ-301 / BATCH-310).
 *
 * Uma única fachada para todos os provedores: cada gateway (`stripe`, `mercadopago`, `paypal` ou
 * futuros) é um DRIVER — um array de callables com as mesmas operações (configurar, testar,
 * iniciar cobrança, sincronizar, reembolsar, link de painel e validar/normalizar webhook).
 * Os módulos consumidores conversam só com `pagamentos_gateway_*` e recebem contratos canônicos;
 * nunca precisam saber o formato de assinatura, JSON ou máquina de estados de cada provedor.
 *
 * Extensão: `hook_apply_filters('pagamentos', 'gateways.drivers', $drivers)` acrescenta ou
 * substitui drivers. Um driver é:
 *   [
 *     'biblioteca'         => 'stripe',            // incluída via gestor_incluir_biblioteca (opcional)
 *     'configurar'         => callable(array $linha): bool,
 *     'testar'             => callable(array $linha): array ['ok','error'],
 *     'iniciar'            => callable(array $linha, array $contexto, array $capacidades): array,
 *     'sincronizar'        => callable(array $linha, array $contexto): array (contrato de sincronização),
 *     'reembolsar'         => callable(array $linha, array $contexto): array,
 *     'dashboard_url'      => callable(array $linha, string $paymentId): ?string,
 *     'validar_webhook'    => callable(string $raw, array $headers, array $query): array|false,
 *     'normalizar_webhook' => callable(array $evento, array $query): array (campos do envelope),
 *     'resolver_webhook'   => callable(array $linha, array $envelope): array (opcional; busca autenticada),
 *     'ambiente_evento'    => callable(array $evento): ?string 'P'|'S' (opcional),
 *     'validacao_local'    => bool,                // assinatura conferida sem chamada de rede
 *     'capacidades'        => callable(array $linha, array $config): array (opcional),
 *   ]
 *
 * Estados canônicos de pagamento: awaiting | processing | paid | failed | refunded |
 * partially_refunded | cancelled.
 */

define('PAGAMENTOS_GATEWAYS_STATUS', Array('awaiting', 'processing', 'paid', 'failed', 'refunded', 'partially_refunded', 'cancelled'));

define('PAGAMENTOS_GATEWAYS_EVENTOS', Array(
    'payment.paid', 'payment.processing', 'payment.failed', 'payment.refunded', 'payment.partially_refunded',
    'payment.cancelled', 'subscription.updated', 'subscription.cancelled', 'subscription.payment_succeeded',
    'subscription.payment_failed', 'other',
));

// ============================================================================
// DRIVERS
// ============================================================================

/** Catálogo de drivers registrados (nativos + extensões por hook). */
function pagamentos_gateways_drivers(){
    $drivers = Array(
        'stripe' => Array(
            'biblioteca' => 'stripe',
            'configurar' => 'pagamentos_gateways_stripe_configurar',
            'testar' => 'pagamentos_gateways_stripe_testar',
            'iniciar' => 'pagamentos_gateways_stripe_iniciar',
            'sincronizar' => 'pagamentos_gateways_stripe_sincronizar',
            'reembolsar' => 'pagamentos_gateways_stripe_reembolsar',
            'dashboard_url' => 'pagamentos_gateways_stripe_dashboard_url',
            'validar_webhook' => 'pagamentos_gateways_stripe_validar_webhook',
            'normalizar_webhook' => 'pagamentos_gateways_stripe_normalizar_webhook',
            'ambiente_evento' => 'pagamentos_gateways_stripe_ambiente_evento',
            'validacao_local' => true,
            'capacidades' => 'pagamentos_gateways_stripe_capacidades',
        ),
        'mercadopago' => Array(
            'biblioteca' => 'mercadopago',
            'configurar' => 'pagamentos_gateways_mercadopago_configurar',
            'testar' => 'pagamentos_gateways_mercadopago_testar',
            'iniciar' => 'pagamentos_gateways_mercadopago_iniciar',
            'sincronizar' => 'pagamentos_gateways_mercadopago_sincronizar',
            'reembolsar' => 'pagamentos_gateways_mercadopago_reembolsar',
            'dashboard_url' => 'pagamentos_gateways_mercadopago_dashboard_url',
            'validar_webhook' => 'pagamentos_gateways_mercadopago_validar_webhook',
            'normalizar_webhook' => 'pagamentos_gateways_mercadopago_normalizar_webhook',
            'resolver_webhook' => 'pagamentos_gateways_mercadopago_resolver_webhook',
            'ambiente_evento' => 'pagamentos_gateways_mercadopago_ambiente_evento',
            'validacao_local' => true,
            'capacidades' => 'pagamentos_gateways_mercadopago_capacidades',
        ),
        'paypal' => Array(
            'biblioteca' => 'paypal',
            'configurar' => 'pagamentos_gateways_paypal_configurar',
            'testar' => 'pagamentos_gateways_paypal_testar',
            'iniciar' => 'pagamentos_gateways_paypal_iniciar',
            'sincronizar' => 'pagamentos_gateways_paypal_sincronizar',
            'reembolsar' => 'pagamentos_gateways_paypal_reembolsar',
            'dashboard_url' => 'pagamentos_gateways_paypal_dashboard_url',
            'validar_webhook' => 'pagamentos_gateways_paypal_validar_webhook',
            'normalizar_webhook' => 'pagamentos_gateways_paypal_normalizar_webhook',
            // A validação do PayPal é uma chamada à API (verify-webhook-signature).
            'validacao_local' => false,
            'capacidades' => 'pagamentos_gateways_paypal_capacidades',
        ),
    );

    if(function_exists('hook_apply_filters')){
        $filtrados = hook_apply_filters('pagamentos', 'gateways.drivers', $drivers);
        if(is_array($filtrados)) $drivers = $filtrados;
    }

    $validos = Array();
    foreach($drivers as $tipo => $driver){
        $tipo = strtolower((string)$tipo);
        if(!preg_match('/^[a-z0-9_-]+$/', $tipo) || !is_array($driver)) continue;
        $validos[$tipo] = $driver;
    }
    return $validos;
}

/** Driver de um tipo, com a biblioteca dele já incluída; null quando não registrado. */
function pagamentos_gateways_driver($tipo){
    $drivers = pagamentos_gateways_drivers();
    $tipo = strtolower((string)$tipo);
    if(!isset($drivers[$tipo])) return null;
    $driver = $drivers[$tipo];
    if(!empty($driver['biblioteca'])) gestor_incluir_biblioteca($driver['biblioteca']);
    return $driver;
}

/** Executa uma operação do driver; null quando o driver não a implementa. */
function pagamentos_gateways_chamar($driver, $operacao, ...$args){
    $fn = $driver[$operacao] ?? null;
    if(!is_callable($fn)) return null;
    return $fn(...$args);
}

// ============================================================================
// CONSULTA ORDENADA E CAPACIDADES
// ============================================================================

/** A coluna `prioridade` existe? Instalações anteriores à migration usam a ordem sem ela. */
function pagamentos_gateways_tem_prioridade(){
    global $_GESTOR;
    if(!isset($_GESTOR['pagamentos-gateways']['tem-prioridade'])){
        $_GESTOR['pagamentos-gateways']['tem-prioridade'] = function_exists('banco_campo_existe')
            ? (bool)banco_campo_existe('prioridade', 'gateways_pagamentos')
            : false;
    }
    return $_GESTOR['pagamentos-gateways']['tem-prioridade'];
}

/** Ordem canônica: prioridade ASC (100 quando nula), padrão primeiro, id numérico ASC. */
function pagamentos_gateways_ordenar($linhas){
    usort($linhas, function($a, $b){
        $pa = (isset($a['prioridade']) && $a['prioridade'] !== '' && $a['prioridade'] !== null) ? (int)$a['prioridade'] : 100;
        $pb = (isset($b['prioridade']) && $b['prioridade'] !== '' && $b['prioridade'] !== null) ? (int)$b['prioridade'] : 100;
        if($pa !== $pb) return $pa <=> $pb;
        $da = (($a['padrao'] ?? 'N') === 'S') ? 0 : 1;
        $db = (($b['padrao'] ?? 'N') === 'S') ? 0 : 1;
        if($da !== $db) return $da <=> $db;
        return ((int)($a['id_gateways_pagamentos'] ?? 0)) <=> ((int)($b['id_gateways_pagamentos'] ?? 0));
    });
    return $linhas;
}

/**
 * Lista os gateways ativos com driver registrado, na ordem canônica.
 *
 * @param array $filtros ['tipo' => string, 'currency' => 'BRL', 'ambiente' => 'S'|'P',
 *                        'supports_installments' => bool, 'supports_one_time' => bool,
 *                        'supports_recurring' => bool, 'supports_pix' => bool]
 * @return array Linhas da tabela `gateways_pagamentos` (cada uma com a chave extra `_capacidades`).
 */
function pagamentos_gateways_listar_ativos($filtros = Array()){
    gestor_incluir_biblioteca('banco');
    $filtros = is_array($filtros) ? $filtros : Array();

    $where = "WHERE status = 'A'";
    if(!empty($filtros['tipo'])) $where .= " AND tipo = '" . banco_escape_field(strtolower((string)$filtros['tipo'])) . "'";

    $ordem = "CASE WHEN padrao = 'S' THEN 0 ELSE 1 END ASC, id_gateways_pagamentos ASC";
    if(pagamentos_gateways_tem_prioridade()) $ordem = 'COALESCE(prioridade, 100) ASC, ' . $ordem;

    $linhas = banco_select(Array(
        'tabela' => 'gateways_pagamentos',
        'campos' => '*',
        'extra' => $where . ' ORDER BY ' . $ordem,
    ));
    if(!is_array($linhas)) return Array();

    $drivers = pagamentos_gateways_drivers();
    $resultado = Array();
    foreach($linhas as $linha){
        if(!is_array($linha) || ($linha['status'] ?? 'A') !== 'A') continue;
        if(!isset($drivers[strtolower((string)($linha['tipo'] ?? ''))])) continue;

        $cap = pagamentos_gateway_capacidades($linha);
        if(!empty($filtros['currency']) && strtoupper((string)$filtros['currency']) !== strtoupper((string)($linha['moeda'] ?? 'BRL'))) continue;
        if(!empty($filtros['ambiente']) && (string)$filtros['ambiente'] !== (string)($linha['ambiente'] ?? 'S')) continue;
        foreach(Array('supports_installments', 'supports_one_time', 'supports_recurring', 'supports_pix') as $flag){
            if(array_key_exists($flag, $filtros) && $filtros[$flag] !== null && (bool)$filtros[$flag] !== (bool)$cap[$flag]) continue 2;
        }

        $linha['_capacidades'] = $cap;
        $resultado[] = $linha;
    }

    return pagamentos_gateways_ordenar($resultado);
}

/** JSON de `configuracoes`: seção do tipo (`{"mercadopago": {...}}`) sobre as chaves planas. */
function pagamentos_gateways_config($linha){
    $cfg = json_decode((string)($linha['configuracoes'] ?? ''), true);
    if(!is_array($cfg)) return Array();
    $tipo = strtolower((string)($linha['tipo'] ?? ''));
    $secao = (isset($cfg[$tipo]) && is_array($cfg[$tipo])) ? $cfg[$tipo] : Array();
    return array_replace($cfg, $secao);
}

/** Booleano tolerante a "true"/"S"/1 vindos de JSON digitado no painel. */
function pagamentos_gateways_bool($valor, $padrao){
    if($valor === null) return $padrao;
    if(is_bool($valor)) return $valor;
    if(is_numeric($valor)) return ((int)$valor) !== 0;
    $v = strtolower(trim((string)$valor));
    if(in_array($v, Array('true', 's', 'sim', 'yes', 'on'), true)) return true;
    if(in_array($v, Array('false', 'n', 'nao', 'não', 'no', 'off', ''), true)) return false;
    return $padrao;
}

/**
 * Capacidades padronizadas de um gateway.
 *
 * @return array ['tipo','gateway_id','supports_installments','max_installments' (1..12),
 *                'interest_payer' ('buyer'|'merchant'),'supports_one_time','supports_recurring',
 *                'supports_pix','render_mode' ('auto'|'button_first'),'priority','currency','ambiente']
 */
function pagamentos_gateway_capacidades($gatewayRow){
    $linha = is_array($gatewayRow) ? $gatewayRow : Array();
    $tipo = strtolower((string)($linha['tipo'] ?? ''));
    $cfg = pagamentos_gateways_config($linha);

    $cap = Array(
        'tipo' => $tipo,
        'gateway_id' => isset($linha['id']) ? (string)$linha['id'] : null,
        'supports_installments' => false,
        'max_installments' => 1,
        'interest_payer' => 'buyer',
        'supports_one_time' => true,
        'supports_recurring' => false,
        'supports_pix' => false,
        'render_mode' => 'auto',
        'priority' => (isset($linha['prioridade']) && $linha['prioridade'] !== '' && $linha['prioridade'] !== null) ? (int)$linha['prioridade'] : 100,
        'currency' => strtoupper((string)($linha['moeda'] ?? 'BRL')),
        'ambiente' => (($linha['ambiente'] ?? 'S') === 'P') ? 'P' : 'S',
    );

    $drivers = pagamentos_gateways_drivers();
    $fn = $drivers[$tipo]['capacidades'] ?? null;
    if(is_callable($fn)){
        $especificas = $fn($linha, $cfg);
        if(is_array($especificas)) $cap = array_replace($cap, $especificas);
    }

    $cap['render_mode'] = in_array($cfg['render_mode'] ?? $cap['render_mode'], Array('auto', 'button_first'), true) ? ($cfg['render_mode'] ?? $cap['render_mode']) : 'auto';
    $cap['interest_payer'] = in_array($cap['interest_payer'], Array('buyer', 'merchant'), true) ? $cap['interest_payer'] : 'buyer';
    $cap['max_installments'] = max(1, min(12, (int)$cap['max_installments']));
    $cap['supports_installments'] = (bool)$cap['supports_installments'] && $cap['max_installments'] > 1;
    if(!$cap['supports_installments']) $cap['max_installments'] = 1;
    foreach(Array('supports_one_time', 'supports_recurring', 'supports_pix') as $flag) $cap[$flag] = (bool)$cap[$flag];

    return $cap;
}

/** Leitura comum do bloco `installments` da configuração. */
function pagamentos_gateways_capacidades_parcelamento($cfg, $habilitadoPadrao){
    $inst = (isset($cfg['installments']) && is_array($cfg['installments'])) ? $cfg['installments'] : Array();
    $habilitado = pagamentos_gateways_bool($inst['enabled'] ?? null, $habilitadoPadrao);
    return Array(
        'supports_installments' => $habilitado,
        'max_installments' => $habilitado ? (int)($inst['max_installments'] ?? 12) : 1,
        'interest_payer' => (string)($inst['interest_payer'] ?? 'buyer'),
    );
}

// ============================================================================
// FACHADA UNIFICADA
// ============================================================================

/** Carrega a linha do gateway: aceita a própria linha, o `id` alfanumérico ou o id numérico. */
function pagamentos_gateways_resolver_linha($gatewayIdOrRow){
    if(is_array($gatewayIdOrRow)) return $gatewayIdOrRow;
    $valor = trim((string)$gatewayIdOrRow);
    if($valor === '') return null;

    gestor_incluir_biblioteca('banco');
    $campo = ctype_digit($valor) ? 'id_gateways_pagamentos' : 'id';
    $linha = banco_select(Array(
        'unico' => true,
        'tabela' => 'gateways_pagamentos',
        'campos' => '*',
        'extra' => "WHERE " . $campo . " = '" . banco_escape_field($valor) . "' AND status = 'A'",
    ));
    return is_array($linha) ? $linha : null;
}

/** Contrato canônico de erro, para que todo retorno tenha as mesmas chaves. */
function pagamentos_gateways_erro($tipo, $gatewayId, $codigo, $extra = Array()){
    return array_replace(Array(
        'ok' => false,
        'gateway_type' => (string)$tipo,
        'gateway_id' => $gatewayId !== null ? (string)$gatewayId : null,
        'error' => (string)$codigo,
    ), $extra);
}

/**
 * Configura a biblioteca do provedor com as credenciais do gateway.
 *
 * @return array|false Linha do gateway configurado ou false.
 */
function pagamentos_gateway_configurar($gatewayIdOrRow){
    $linha = pagamentos_gateways_resolver_linha($gatewayIdOrRow);
    if(!$linha) return false;
    $driver = pagamentos_gateways_driver($linha['tipo'] ?? '');
    if(!$driver) return false;
    return pagamentos_gateways_chamar($driver, 'configurar', $linha) ? $linha : false;
}

/** Testa credenciais/conexão. @return array ['ok','gateway_type','gateway_id','error'] */
function pagamentos_gateway_testar($gatewayRow){
    $linha = pagamentos_gateways_resolver_linha($gatewayRow);
    $tipo = $linha['tipo'] ?? '';
    $id = $linha['id'] ?? null;
    if(!$linha || !($driver = pagamentos_gateways_driver($tipo))) return pagamentos_gateways_erro($tipo, $id, 'driver-unavailable');
    if(!pagamentos_gateways_chamar($driver, 'configurar', $linha)) return pagamentos_gateways_erro($tipo, $id, 'config-failed');

    $r = pagamentos_gateways_chamar($driver, 'testar', $linha);
    if(!is_array($r)) $r = Array('ok' => (bool)$r, 'error' => $r ? null : 'test-failed');
    return Array('ok' => !empty($r['ok']), 'gateway_type' => $tipo, 'gateway_id' => $id, 'error' => !empty($r['ok']) ? null : (string)($r['error'] ?? 'test-failed'));
}

/**
 * Inicia uma cobrança avulsa no gateway.
 *
 * O valor vem SEMPRE do servidor (o módulo calcula a partir do banco); o contexto não deve
 * transportar preço vindo do navegador. Parcelas acima da capacidade do gateway são recusadas,
 * nunca ajustadas em silêncio.
 *
 * @param array $contexto ['amount' => decimal, 'currency' => 'BRL', 'reference' => string,
 *                         'description', 'installments' => int, 'token', 'payment_method_id',
 *                         'issuer_id', 'payer' => array, 'customer_id', 'metadata' => array,
 *                         'notification_url', 'return_url', 'cancel_url', 'idempotency_key']
 * @return array ['ok','gateway_type','gateway_id','gateway_payment_id','gateway_order_id',
 *                'canonical_status','client_secret','approve_url','pix','raw','error']
 */
function pagamentos_gateway_iniciar_cobranca($gatewayRow, $contexto){
    $linha = pagamentos_gateways_resolver_linha($gatewayRow);
    $tipo = $linha['tipo'] ?? '';
    $id = $linha['id'] ?? null;
    $contexto = is_array($contexto) ? $contexto : Array();

    if(!$linha || !($driver = pagamentos_gateways_driver($tipo))) return pagamentos_gateways_erro($tipo, $id, 'driver-unavailable');
    if((float)($contexto['amount'] ?? 0) <= 0) return pagamentos_gateways_erro($tipo, $id, 'invalid-amount');

    $cap = pagamentos_gateway_capacidades($linha);
    $parcelas = max(1, (int)($contexto['installments'] ?? 1));
    if($parcelas > $cap['max_installments']) return pagamentos_gateways_erro($tipo, $id, 'installments-not-allowed');
    if(!empty($contexto['currency']) && strtoupper((string)$contexto['currency']) !== $cap['currency']) return pagamentos_gateways_erro($tipo, $id, 'currency-mismatch');
    $contexto['installments'] = $parcelas;
    $contexto['currency'] = $cap['currency'];

    if(!pagamentos_gateways_chamar($driver, 'configurar', $linha)) return pagamentos_gateways_erro($tipo, $id, 'config-failed');
    $r = pagamentos_gateways_chamar($driver, 'iniciar', $linha, $contexto, $cap);
    if(!is_array($r)) return pagamentos_gateways_erro($tipo, $id, 'operation-unsupported');

    return array_replace(Array(
        'ok' => false, 'gateway_type' => $tipo, 'gateway_id' => $id, 'gateway_payment_id' => null,
        'gateway_order_id' => null, 'canonical_status' => 'awaiting', 'client_secret' => null,
        'approve_url' => null, 'pix' => null, 'raw' => Array(), 'error' => null,
    ), $r, Array('gateway_type' => $tipo, 'gateway_id' => $id));
}

/**
 * Sincroniza o estado de um pagamento pela API autenticada do gateway, com prova de posse.
 *
 * Com `expected_amount`, `expected_currency` ou `expected_reference` no contexto, divergência
 * devolve `ok = false` e `error = amount-mismatch|currency-mismatch|reference-mismatch`;
 * `canonical_status` continua descrevendo o gateway, mas quem recebe `ok = false` NÃO aplica estado.
 *
 * @param array $contexto ['gateway_payment_id' => id, 'gateway_order_id' => id (PayPal),
 *                         'expected_amount', 'expected_currency', 'expected_reference']
 * @return array Contrato de sincronização (ver REQ-301 §2 Frente 2).
 */
function pagamentos_gateway_sincronizar($gatewayRow, $contexto){
    $linha = pagamentos_gateways_resolver_linha($gatewayRow);
    $tipo = $linha['tipo'] ?? '';
    $id = $linha['id'] ?? null;
    $contexto = is_array($contexto) ? $contexto : Array();

    $base = Array(
        'ok' => false, 'canonical_status' => 'awaiting', 'gateway_type' => (string)$tipo,
        'gateway_id' => $id !== null ? (string)$id : null, 'gateway_payment_id' => (string)($contexto['gateway_payment_id'] ?? ''),
        'gateway_order_id' => $contexto['gateway_order_id'] ?? null, 'gateway_subscription_id' => null,
        'amount' => 0.0, 'currency' => '', 'installments' => 1, 'refunded_amount' => null,
        'reference' => null, 'raw' => Array(), 'error' => null,
    );

    if(!$linha || !($driver = pagamentos_gateways_driver($tipo))) return array_replace($base, Array('error' => 'driver-unavailable'));
    if(empty($contexto['gateway_payment_id']) && empty($contexto['gateway_order_id'])) return array_replace($base, Array('error' => 'missing-payment-id'));
    if(!pagamentos_gateways_chamar($driver, 'configurar', $linha)) return array_replace($base, Array('error' => 'config-failed'));

    $r = pagamentos_gateways_chamar($driver, 'sincronizar', $linha, $contexto);
    if(!is_array($r)) return array_replace($base, Array('error' => 'operation-unsupported'));

    $r = array_replace($base, $r, Array('gateway_type' => (string)$tipo, 'gateway_id' => $base['gateway_id']));
    $r['amount'] = round((float)$r['amount'], 2);
    $r['currency'] = strtoupper((string)$r['currency']);
    $r['installments'] = max(1, (int)$r['installments']);
    $r['gateway_payment_id'] = (string)$r['gateway_payment_id'];
    if(!in_array($r['canonical_status'], PAGAMENTOS_GATEWAYS_STATUS, true)) $r['canonical_status'] = 'processing';

    if($r['ok']){
        if(isset($contexto['expected_amount']) && abs(round((float)$contexto['expected_amount'], 2) - $r['amount']) >= 0.005){
            $r['ok'] = false; $r['error'] = 'amount-mismatch';
        } elseif(!empty($contexto['expected_currency']) && strtoupper((string)$contexto['expected_currency']) !== $r['currency']){
            $r['ok'] = false; $r['error'] = 'currency-mismatch';
        } elseif(isset($contexto['expected_reference']) && (string)$contexto['expected_reference'] !== (string)$r['reference']){
            $r['ok'] = false; $r['error'] = 'reference-mismatch';
        }
    }

    return $r;
}

/**
 * Reembolsa total (sem `amount`) ou parcialmente um pagamento.
 *
 * @param array $contexto ['gateway_payment_id' => obrig (pi_/ch_ no Stripe, capture no PayPal,
 *                         Order `ORD...` ou id numérico legado no Mercado Pago),
 *                         'gateway_transaction_id' => opc (PAY... do reembolso parcial de Order),
 *                         'amount' => decimal opc,
 *                         'currency' => opc, 'reason' => opc, 'idempotency_key' => opc]
 * @return array ['ok','gateway_type','gateway_id','refund_id','refunded_amount','status','raw','error']
 */
function pagamentos_gateway_reembolsar($gatewayRow, $contexto){
    $linha = pagamentos_gateways_resolver_linha($gatewayRow);
    $tipo = $linha['tipo'] ?? '';
    $id = $linha['id'] ?? null;
    $contexto = is_array($contexto) ? $contexto : Array();

    if(!$linha || !($driver = pagamentos_gateways_driver($tipo))) return pagamentos_gateways_erro($tipo, $id, 'driver-unavailable');
    if(empty($contexto['gateway_payment_id'])) return pagamentos_gateways_erro($tipo, $id, 'missing-payment-id');
    if(isset($contexto['amount']) && (float)$contexto['amount'] <= 0) return pagamentos_gateways_erro($tipo, $id, 'invalid-amount');
    if(!pagamentos_gateways_chamar($driver, 'configurar', $linha)) return pagamentos_gateways_erro($tipo, $id, 'config-failed');

    $r = pagamentos_gateways_chamar($driver, 'reembolsar', $linha, $contexto);
    if(!is_array($r)) return pagamentos_gateways_erro($tipo, $id, 'operation-unsupported');

    return array_replace(Array(
        'ok' => false, 'refund_id' => null, 'refunded_amount' => null, 'status' => null, 'raw' => Array(), 'error' => null,
    ), $r, Array('gateway_type' => $tipo, 'gateway_id' => $id));
}

/** Link do pagamento no painel do provedor (null quando não há id ou driver). */
function pagamentos_gateway_dashboard_url($gatewayRow, $paymentId){
    $linha = pagamentos_gateways_resolver_linha($gatewayRow);
    $paymentId = trim((string)$paymentId);
    if(!$linha || $paymentId === '' || !preg_match('/^[A-Za-z0-9_.:-]+$/', $paymentId)) return null;
    $drivers = pagamentos_gateways_drivers();
    $driver = $drivers[strtolower((string)($linha['tipo'] ?? ''))] ?? null;
    if(!$driver) return null;
    $url = pagamentos_gateways_chamar($driver, 'dashboard_url', $linha, $paymentId);
    return is_string($url) && $url !== '' ? $url : null;
}

// ============================================================================
// WEBHOOK — ENVELOPE CANÔNICO
// ============================================================================

/** Header sem distinção de maiúsculas (getallheaders devolve a grafia do cliente). */
function pagamentos_gateways_header($headers, $nome){
    if(!is_array($headers)) return '';
    $nome = strtolower((string)$nome);
    foreach($headers as $chave => $valor){
        if(strtolower((string)$chave) === $nome) return is_array($valor) ? (string)reset($valor) : (string)$valor;
    }
    return '';
}

/** Envelope vazio com todas as chaves do contrato. */
function pagamentos_gateways_envelope_base($tipo){
    return Array(
        'valid' => false,
        'gateway_type' => (string)$tipo,
        'gateway_id' => null,
        'event_id' => null,
        'event_type' => 'unknown',
        'canonical_event' => 'other',
        'reference' => null,
        'gateway_payment_id' => null,
        'gateway_order_id' => null,
        'gateway_subscription_id' => null,
        'resource' => Array(),
        'event_data' => Array(),
        'error' => null,
    );
}

/**
 * Normaliza um evento já decodificado (validado ou não) no envelope canônico, sem rede.
 *
 * @param bool $valido Se a assinatura foi conferida por quem chama.
 */
function pagamentos_gateway_normalizar_evento($gatewayType, $evento, $query = Array(), $valido = false, $gatewayId = null){
    $tipo = strtolower((string)$gatewayType);
    $envelope = pagamentos_gateways_envelope_base($tipo);
    $evento = is_array($evento) ? $evento : Array();
    $query = is_array($query) ? $query : Array();

    $drivers = pagamentos_gateways_drivers();
    $driver = $drivers[$tipo] ?? null;
    if(!$driver){
        $envelope['error'] = 'driver-unknown';
        $envelope['event_data'] = $evento;
        return $envelope;
    }

    $campos = pagamentos_gateways_chamar($driver, 'normalizar_webhook', $evento, $query);
    if(is_array($campos)) $envelope = array_replace($envelope, $campos);

    $envelope['gateway_type'] = $tipo;
    $envelope['valid'] = (bool)$valido;
    $envelope['gateway_id'] = $gatewayId !== null ? (string)$gatewayId : null;
    $envelope['event_data'] = $evento;
    if(!is_array($envelope['resource'])) $envelope['resource'] = Array();
    if(!in_array($envelope['canonical_event'], PAGAMENTOS_GATEWAYS_EVENTOS, true)) $envelope['canonical_event'] = 'other';
    foreach(Array('event_id', 'reference', 'gateway_payment_id', 'gateway_order_id', 'gateway_subscription_id') as $campo){
        $envelope[$campo] = (is_scalar($envelope[$campo]) && (string)$envelope[$campo] !== '') ? (string)$envelope[$campo] : null;
    }
    $envelope['event_type'] = (string)$envelope['event_type'];
    return $envelope;
}

/** Guarda/restaura o estado global das bibliotecas para a validação não vazar configuração. */
function pagamentos_gateways_estado($tipo, $restaurar = null){
    global $_GESTOR, $_CONFIG;
    $chaves = Array('biblioteca-' . $tipo, $tipo . '-token');
    if($restaurar === null){
        $estado = Array('config' => $_CONFIG[$tipo] ?? null, 'gestor' => Array());
        foreach($chaves as $chave) $estado['gestor'][$chave] = $_GESTOR[$chave] ?? null;
        return $estado;
    }
    if($restaurar['config'] === null) unset($_CONFIG[$tipo]); else $_CONFIG[$tipo] = $restaurar['config'];
    foreach($restaurar['gestor'] as $chave => $valor){
        if($valor === null) unset($_GESTOR[$chave]); else $_GESTOR[$chave] = $valor;
    }
    return null;
}

/**
 * Valida e normaliza um webhook no Envelope Canônico.
 *
 * Tenta os gateways ativos do tipo (o indicado em `$gatewayIdHint` primeiro, depois os do
 * ambiente declarado pelo evento, depois a ordem canônica) até um aceitar a assinatura. Com um
 * gateway válido e driver com `resolver_webhook` (Mercado Pago), o recurso é buscado na API
 * autenticada — o webhook só sinaliza; o estado vem do provedor. O estado global das
 * bibliotecas é restaurado ao final.
 *
 * @return array Envelope canônico (`valid = false` + `error` quando nenhum gateway confirma).
 */
function pagamentos_gateway_validar_e_normalizar_webhook($gatewayType, $rawBody, $headers, $query = Array(), $gatewayIdHint = null){
    $tipo = strtolower((string)$gatewayType);
    $rawBody = (string)$rawBody;
    $headers = is_array($headers) ? $headers : Array();
    $query = is_array($query) ? $query : Array();

    $evento = $rawBody !== '' ? json_decode($rawBody, true) : Array();
    if(!is_array($evento)) $evento = Array();
    if($tipo === 'mercadopago') unset($evento['_resource']);

    $driver = pagamentos_gateways_driver($tipo);
    if(!$driver){
        $envelope = pagamentos_gateway_normalizar_evento($tipo, $evento, $query);
        $envelope['error'] = 'driver-unknown';
        return $envelope;
    }

    $candidatos = pagamentos_gateways_listar_ativos(Array('tipo' => $tipo));
    if(!$candidatos){
        $envelope = pagamentos_gateway_normalizar_evento($tipo, $evento, $query);
        $envelope['error'] = 'no-active-gateway';
        return $envelope;
    }

    $ambiente = pagamentos_gateways_chamar($driver, 'ambiente_evento', $evento);
    $hint = $gatewayIdHint !== null ? (string)$gatewayIdHint : '';
    $peso = function($linha) use ($hint, $ambiente){
        if($hint !== '' && ($hint === (string)($linha['id'] ?? '') || $hint === (string)($linha['id_gateways_pagamentos'] ?? ''))) return 0;
        if($ambiente !== null && $ambiente === (($linha['ambiente'] ?? 'S') === 'P' ? 'P' : 'S')) return 1;
        return 2;
    };
    $ordenados = Array();
    foreach($candidatos as $i => $linha) $ordenados[] = Array($peso($linha), $i, $linha);
    usort($ordenados, function($a, $b){ return ($a[0] <=> $b[0]) ?: ($a[1] <=> $b[1]); });

    $estado = pagamentos_gateways_estado($tipo);
    try {
        foreach($ordenados as $item){
            $linha = $item[2];
            if(!pagamentos_gateways_chamar($driver, 'configurar', $linha)) continue;
            $validado = pagamentos_gateways_chamar($driver, 'validar_webhook', $rawBody, $headers, $query);
            if(!is_array($validado)) continue;
            if($tipo === 'mercadopago') unset($validado['_resource']);

            $envelope = pagamentos_gateway_normalizar_evento($tipo, $validado, $query, true, $linha['id'] ?? null);
            $resolvido = pagamentos_gateways_chamar($driver, 'resolver_webhook', $linha, $envelope);
            if(is_array($resolvido)) $envelope = $resolvido;
            return $envelope;
        }
    } finally {
        pagamentos_gateways_estado($tipo, $estado);
    }

    $envelope = pagamentos_gateway_normalizar_evento($tipo, $evento, $query);
    $envelope['error'] = 'invalid-signature';
    return $envelope;
}

/** Estado canônico de pagamento → evento canônico. */
function pagamentos_gateways_evento_por_status($status){
    $mapa = Array(
        'paid' => 'payment.paid', 'processing' => 'payment.processing', 'awaiting' => 'payment.processing',
        'failed' => 'payment.failed', 'refunded' => 'payment.refunded',
        'partially_refunded' => 'payment.partially_refunded', 'cancelled' => 'payment.cancelled',
    );
    return $mapa[$status] ?? 'other';
}

// ============================================================================
// DRIVER STRIPE
// ============================================================================

function pagamentos_gateways_stripe_configurar($linha){
    return stripe_gateways_pagamentos_configurar(Array('id' => $linha['id'] ?? ''));
}

function pagamentos_gateways_stripe_testar($linha){
    $ok = stripe_testar_conexao();
    return Array('ok' => $ok, 'error' => $ok ? null : 'connection-failed');
}

function pagamentos_gateways_stripe_capacidades($linha, $cfg){
    // O Stripe não oferece parcelamento no cartão no Brasil (available_plans: []).
    return Array(
        'supports_installments' => false,
        'max_installments' => 1,
        'supports_recurring' => true,
        'supports_pix' => pagamentos_gateways_bool($cfg['pix'] ?? null, false),
    );
}

function pagamentos_gateways_stripe_status($pi){
    $mapa = Array(
        'succeeded' => 'paid', 'processing' => 'processing', 'requires_capture' => 'processing',
        'requires_payment_method' => 'awaiting', 'requires_confirmation' => 'awaiting',
        'requires_action' => 'awaiting', 'canceled' => 'cancelled',
    );
    $status = $mapa[(string)($pi['status'] ?? '')] ?? 'processing';
    $charge = is_array($pi['latest_charge'] ?? null) ? $pi['latest_charge'] : Array();
    $reembolsado = (int)($charge['amount_refunded'] ?? 0);
    if($status === 'paid' && $reembolsado > 0){
        $status = ($reembolsado >= (int)($charge['amount'] ?? $pi['amount'] ?? 0)) ? 'refunded' : 'partially_refunded';
    }
    return $status;
}

function pagamentos_gateways_stripe_iniciar($linha, $contexto, $cap){
    $pi = stripe_criar_payment_intent(Array(
        'valor' => $contexto['amount'],
        'moeda' => $contexto['currency'],
        'customer_id' => $contexto['customer_id'] ?? null,
        'descricao' => $contexto['description'] ?? null,
        'referencia' => $contexto['reference'] ?? null,
        'idempotency_key' => $contexto['idempotency_key'] ?? '',
    ));
    if(!$pi) return Array('ok' => false, 'error' => 'gateway-rejected');
    return Array(
        'ok' => true,
        'gateway_payment_id' => $pi['id'],
        'client_secret' => $pi['client_secret'],
        'canonical_status' => pagamentos_gateways_stripe_status($pi),
        'raw' => $pi,
    );
}

function pagamentos_gateways_stripe_sincronizar($linha, $contexto){
    $id = (string)($contexto['gateway_payment_id'] ?? '');
    if(!preg_match('/^pi_[A-Za-z0-9]+$/', $id)) return Array('ok' => false, 'error' => 'invalid-payment-id');
    $resp = stripe_requisicao(Array('endpoint' => '/v1/payment_intents/' . rawurlencode($id), 'data' => Array('expand' => Array('latest_charge'))));
    if(!$resp || $resp['http_code'] !== 200) return Array('ok' => false, 'error' => 'gateway-unavailable');

    $pi = $resp['data'];
    $moeda = strtoupper((string)($pi['currency'] ?? ''));
    $charge = is_array($pi['latest_charge'] ?? null) ? $pi['latest_charge'] : Array();
    $reembolsado = (int)($charge['amount_refunded'] ?? 0);

    return Array(
        'ok' => true,
        'canonical_status' => pagamentos_gateways_stripe_status($pi),
        'gateway_payment_id' => (string)($pi['id'] ?? $id),
        'amount' => stripe_valor_decimal($pi['amount'] ?? 0, $moeda),
        'currency' => $moeda,
        'installments' => (int)($charge['payment_method_details']['card']['installments']['plan']['count'] ?? 1),
        'refunded_amount' => $reembolsado > 0 ? stripe_valor_decimal($reembolsado, $moeda) : null,
        'reference' => $pi['metadata']['referencia'] ?? null,
        'raw' => $pi,
    );
}

function pagamentos_gateways_stripe_reembolsar($linha, $contexto){
    $id = (string)$contexto['gateway_payment_id'];
    $params = Array('moeda' => $contexto['currency'] ?? ($linha['moeda'] ?? 'BRL'));
    if(strpos($id, 'ch_') === 0) $params['charge_id'] = $id; else $params['payment_intent_id'] = $id;
    if(isset($contexto['amount'])) $params['valor'] = $contexto['amount'];

    $r = stripe_reembolsar($params);
    if(!$r) return Array('ok' => false, 'error' => 'gateway-rejected');
    return Array(
        'ok' => in_array($r['status'] ?? '', Array('succeeded', 'pending'), true),
        'refund_id' => $r['id'] ?? null,
        'refunded_amount' => stripe_valor_decimal($r['amount'] ?? 0, $r['currency'] ?? 'BRL'),
        'status' => $r['status'] ?? null,
        'raw' => $r,
        'error' => in_array($r['status'] ?? '', Array('succeeded', 'pending'), true) ? null : 'refund-' . ($r['status'] ?? 'failed'),
    );
}

function pagamentos_gateways_stripe_dashboard_url($linha, $paymentId){
    $teste = (($linha['ambiente'] ?? 'S') === 'P') ? '' : 'test/';
    return 'https://dashboard.stripe.com/' . $teste . 'payments/' . rawurlencode($paymentId);
}

function pagamentos_gateways_stripe_validar_webhook($raw, $headers, $query){
    $evento = stripe_validar_webhook(Array('payload' => $raw, 'signature_header' => pagamentos_gateways_header($headers, 'stripe-signature')));
    return is_array($evento) ? $evento : false;
}

function pagamentos_gateways_stripe_ambiente_evento($evento){
    if(!isset($evento['livemode'])) return null;
    return $evento['livemode'] ? 'P' : 'S';
}

/** Id Stripe com o prefixo esperado, de string ou objeto expandido. */
function pagamentos_gateways_stripe_id($valor, $prefixo){
    if(is_array($valor)) $valor = $valor['id'] ?? '';
    return (is_string($valor) && strpos($valor, $prefixo) === 0) ? $valor : null;
}

function pagamentos_gateways_stripe_normalizar_webhook($evento, $query){
    $tipoEvento = (string)($evento['type'] ?? 'unknown');
    $obj = is_array($evento['data']['object'] ?? null) ? $evento['data']['object'] : Array();
    $objId = (string)($obj['id'] ?? '');

    $canonico = 'other';
    switch($tipoEvento){
        case 'payment_intent.succeeded': $canonico = 'payment.paid'; break;
        case 'payment_intent.processing': $canonico = 'payment.processing'; break;
        case 'payment_intent.payment_failed': $canonico = 'payment.failed'; break;
        case 'payment_intent.canceled': $canonico = 'payment.cancelled'; break;
        case 'charge.succeeded': $canonico = 'payment.paid'; break;
        case 'charge.failed': $canonico = 'payment.failed'; break;
        case 'charge.refunded':
            $canonico = ((int)($obj['amount_refunded'] ?? 0) >= (int)($obj['amount'] ?? 0)) ? 'payment.refunded' : 'payment.partially_refunded';
            break;
        case 'checkout.session.completed':
        case 'checkout.session.async_payment_succeeded':
            $canonico = (($obj['payment_status'] ?? '') === 'paid') ? 'payment.paid' : 'payment.processing'; break;
        case 'checkout.session.async_payment_failed': $canonico = 'payment.failed'; break;
        case 'invoice.paid':
        case 'invoice.payment_succeeded': $canonico = 'subscription.payment_succeeded'; break;
        case 'invoice.payment_failed': $canonico = 'subscription.payment_failed'; break;
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
        case 'customer.subscription.paused':
        case 'customer.subscription.resumed': $canonico = 'subscription.updated'; break;
        case 'customer.subscription.deleted': $canonico = 'subscription.cancelled'; break;
    }

    $pagamento = pagamentos_gateways_stripe_id($obj['payment_intent'] ?? null, 'pi_')
        ?? pagamentos_gateways_stripe_id($objId, 'pi_')
        ?? pagamentos_gateways_stripe_id($objId, 'ch_');
    $assinatura = pagamentos_gateways_stripe_id($objId, 'sub_')
        ?? pagamentos_gateways_stripe_id($obj['subscription'] ?? null, 'sub_')
        ?? pagamentos_gateways_stripe_id($obj['parent']['subscription_details']['subscription'] ?? null, 'sub_');
    $pedido = pagamentos_gateways_stripe_id($objId, 'cs_') ?? pagamentos_gateways_stripe_id($objId, 'in_') ?? pagamentos_gateways_stripe_id($obj['invoice'] ?? null, 'in_');

    $meta = is_array($obj['metadata'] ?? null) ? $obj['metadata'] : Array();
    $referencia = $meta['referencia'] ?? ($meta['reference'] ?? ($obj['client_reference_id'] ?? ($obj['parent']['subscription_details']['metadata']['referencia'] ?? null)));

    return Array(
        'event_id' => $evento['id'] ?? null,
        'event_type' => $tipoEvento,
        'canonical_event' => $canonico,
        'reference' => $referencia,
        'gateway_payment_id' => $pagamento,
        'gateway_order_id' => $pedido,
        'gateway_subscription_id' => $assinatura,
        'resource' => $obj,
    );
}

// ============================================================================
// DRIVER MERCADO PAGO
// ============================================================================

function pagamentos_gateways_mercadopago_configurar($linha){
    return mercadopago_gateways_pagamentos_configurar(Array('gateway' => $linha));
}

function pagamentos_gateways_mercadopago_testar($linha){
    return mercadopago_testar_conexao();
}

function pagamentos_gateways_mercadopago_capacidades($linha, $cfg){
    return array_replace(pagamentos_gateways_capacidades_parcelamento($cfg, true), Array(
        'supports_recurring' => false,
        'supports_pix' => pagamentos_gateways_bool($cfg['pix'] ?? null, true),
    ));
}

/**
 * Contrato de sincronização a partir de um recurso do Mercado Pago: Order da Orders API v1
 * (`gateway_payment_id` = `gateway_order_id` = `ORD...`; o `PAY...` fica em `raw`) ou pagamento legado.
 */
function pagamentos_gateways_mercadopago_contrato($pagamento){
    if(mercadopago_e_ordem($pagamento)){
        $o = mercadopago_ordem_normalizar($pagamento);
        return Array(
            'ok' => true,
            'canonical_status' => $o['canonical_status'],
            'gateway_payment_id' => $o['id'],
            'gateway_order_id' => $o['id'] !== '' ? $o['id'] : null,
            'amount' => $o['amount'],
            'currency' => $o['currency'],
            'installments' => $o['installments'],
            'refunded_amount' => $o['refunded_amount'] > 0 ? $o['refunded_amount'] : null,
            'reference' => $o['external_reference'],
            'raw' => $pagamento,
        );
    }
    $reembolsado = (float)($pagamento['transaction_amount_refunded'] ?? 0);
    return Array(
        'ok' => true,
        'canonical_status' => mercadopago_status_canonico_pagamento($pagamento),
        'gateway_payment_id' => (string)($pagamento['id'] ?? ''),
        'gateway_order_id' => isset($pagamento['order']['id']) ? (string)$pagamento['order']['id'] : null,
        'amount' => (float)($pagamento['transaction_amount'] ?? 0),
        'currency' => (string)($pagamento['currency_id'] ?? ''),
        'installments' => (int)($pagamento['installments'] ?? 1),
        'refunded_amount' => $reembolsado > 0 ? $reembolsado : null,
        'reference' => $pagamento['external_reference'] ?? null,
        'raw' => $pagamento,
    );
}

function pagamentos_gateways_mercadopago_iniciar($linha, $contexto, $cap){
    $pagamento = mercadopago_criar_pagamento(Array(
        'valor' => $contexto['amount'],
        'payment_method_id' => $contexto['payment_method_id'] ?? '',
        'token' => $contexto['token'] ?? null,
        'installments' => $contexto['installments'],
        'issuer_id' => $contexto['issuer_id'] ?? null,
        'payer' => $contexto['payer'] ?? Array(),
        'referencia' => $contexto['reference'] ?? null,
        'descricao' => $contexto['description'] ?? null,
        'metadata' => $contexto['metadata'] ?? null,
        'notification_url' => $contexto['notification_url'] ?? null,
        'date_of_expiration' => $contexto['date_of_expiration'] ?? null,
        'expiration_time' => $contexto['expiration_time'] ?? null,
        'payment_type' => $contexto['payment_type'] ?? null,
        'statement_descriptor' => $contexto['statement_descriptor'] ?? null,
        'idempotency_key' => $contexto['idempotency_key'] ?? '',
    ));
    if(!$pagamento){
        $erro = mercadopago_ultimo_erro();
        return Array('ok' => false, 'error' => $erro ? 'gateway-rejected:' . $erro['code'] : 'invalid-request');
    }

    $contrato = pagamentos_gateways_mercadopago_contrato($pagamento);
    $falhou = $contrato['canonical_status'] === 'failed';

    if(mercadopago_e_ordem($pagamento)){
        $o = mercadopago_ordem_normalizar($pagamento);
        $pagOrdem = mercadopago_ordem_pagamento($pagamento);
        $detalhe = (string)($pagOrdem['status_detail'] ?? '') ?: ($o['status_detail'] ?: 'failed');
        return Array(
            'ok' => !$falhou,
            'gateway_payment_id' => $contrato['gateway_payment_id'],
            'gateway_order_id' => $contrato['gateway_order_id'],
            'canonical_status' => $contrato['canonical_status'],
            'pix' => $o['pix'],
            'approve_url' => $o['pix'] === null ? $o['ticket_url'] : null,
            'raw' => $pagamento,
            'error' => $falhou ? 'payment-' . $detalhe : null,
        );
    }

    $tx = $pagamento['point_of_interaction']['transaction_data'] ?? null;
    return Array(
        'ok' => !$falhou,
        'gateway_payment_id' => $contrato['gateway_payment_id'],
        'gateway_order_id' => $contrato['gateway_order_id'],
        'canonical_status' => $contrato['canonical_status'],
        'pix' => is_array($tx) ? Array(
            'qr_code' => $tx['qr_code'] ?? null,
            'qr_code_base64' => $tx['qr_code_base64'] ?? null,
            'ticket_url' => $tx['ticket_url'] ?? null,
        ) : null,
        'approve_url' => $pagamento['transaction_details']['external_resource_url'] ?? null,
        'raw' => $pagamento,
        'error' => $falhou ? 'payment-' . ($pagamento['status_detail'] ?? 'rejected') : null,
    );
}

function pagamentos_gateways_mercadopago_sincronizar($linha, $contexto){
    $pagamento = mercadopago_consultar_pagamento(Array('payment_id' => $contexto['gateway_payment_id'] ?? ''));
    if(!$pagamento) return Array('ok' => false, 'error' => 'gateway-unavailable');
    return pagamentos_gateways_mercadopago_contrato($pagamento);
}

function pagamentos_gateways_mercadopago_reembolsar($linha, $contexto){
    $params = Array('payment_id' => $contexto['gateway_payment_id'], 'idempotency_key' => $contexto['idempotency_key'] ?? '');
    if(isset($contexto['amount'])) $params['valor'] = $contexto['amount'];
    if(!empty($contexto['gateway_transaction_id'])) $params['transaction_id'] = $contexto['gateway_transaction_id'];
    $r = mercadopago_reembolsar($params);
    if(!$r){
        $erro = mercadopago_ultimo_erro();
        return Array('ok' => false, 'error' => $erro ? 'gateway-rejected:' . $erro['code'] : 'invalid-request');
    }
    $resumo = mercadopago_reembolso_resumo($r);
    $ok = !in_array(strtolower((string)($resumo['status'] ?? 'approved')), Array('rejected', 'cancelled', 'canceled', 'failed'), true);
    return Array(
        'ok' => $ok,
        'refund_id' => $resumo['id'],
        'refunded_amount' => $resumo['amount'],
        'status' => $resumo['status'],
        'raw' => $r,
        'error' => $ok ? null : 'refund-' . ($resumo['status'] ?? 'failed'),
    );
}

function pagamentos_gateways_mercadopago_dashboard_url($linha, $paymentId){
    return 'https://www.mercadopago.com.br/activities?q=' . rawurlencode($paymentId);
}

function pagamentos_gateways_mercadopago_validar_webhook($raw, $headers, $query){
    $r = mercadopago_validar_webhook(Array(
        'x_signature' => pagamentos_gateways_header($headers, 'x-signature'),
        'x_request_id' => pagamentos_gateways_header($headers, 'x-request-id'),
        'payload' => $raw,
        'query' => $query,
    ));
    return is_array($r) ? $r : false;
}

function pagamentos_gateways_mercadopago_ambiente_evento($evento){
    if(!isset($evento['live_mode'])) return null;
    return pagamentos_gateways_bool($evento['live_mode'], false) ? 'P' : 'S';
}

function pagamentos_gateways_mercadopago_normalizar_webhook($evento, $query){
    $tipoEvento = mercadopago_webhook_tipo($evento, $query);
    $acao = (string)($evento['action'] ?? '');
    $dataId = mercadopago_webhook_data_id($evento, $query);
    $recurso = is_array($evento['_resource'] ?? null) ? $evento['_resource'] : Array();

    $campos = Array(
        'event_id' => isset($evento['id']) ? (string)$evento['id'] : null,
        'event_type' => $acao !== '' ? $acao : $tipoEvento,
        'canonical_event' => 'other',
        'resource' => $recurso,
    );

    if($tipoEvento === 'payment'){
        $campos['gateway_payment_id'] = $dataId;
        if($recurso){
            $campos['canonical_event'] = pagamentos_gateways_evento_por_status(mercadopago_status_canonico_pagamento($recurso));
            $campos['reference'] = $recurso['external_reference'] ?? null;
            $campos['gateway_order_id'] = isset($recurso['order']['id']) ? (string)$recurso['order']['id'] : null;
        }
    } elseif($tipoEvento === 'order'){
        // Orders API v1 (`order.processed`, `order.canceled`, `order.failed`, `order.refunded`...).
        // O id da Order é o `gateway_payment_id` guardado pelos módulos. Sem recurso autenticado,
        // o `data.status` do corpo não é usado: estado só vem de GET /v1/orders/{id}.
        $idOrdem = ($dataId !== null && preg_match('/^ord[a-z0-9]+$/i', $dataId)) ? strtoupper($dataId) : $dataId;
        $campos['gateway_payment_id'] = $idOrdem;
        $campos['gateway_order_id'] = $idOrdem;
        $campos['resource'] = Array();
    } elseif($tipoEvento === 'subscription_preapproval'){
        $campos['gateway_subscription_id'] = $dataId;
        $cancelada = in_array((string)($recurso['status'] ?? ''), Array('cancelled'), true);
        $campos['canonical_event'] = $cancelada ? 'subscription.cancelled' : 'subscription.updated';
        $campos['reference'] = $recurso['external_reference'] ?? null;
    } elseif($tipoEvento === 'merchant_order' || $tipoEvento === 'topic_merchant_order_wh'){
        $campos['gateway_order_id'] = $dataId;
    }

    return $campos;
}

/**
 * O webhook do Mercado Pago só sinaliza: o estado vem da consulta autenticada —
 * `type = order` → GET /v1/orders/{id}; `type = payment` → GET /v1/payments/{id} (ou a Order,
 * quando o id já é `ORD...`).
 */
function pagamentos_gateways_mercadopago_resolver_webhook($linha, $envelope){
    $tipo = mercadopago_webhook_tipo($envelope['event_data']);
    $id = $envelope['gateway_payment_id'];
    if($tipo === 'order' || ($tipo === 'payment' && mercadopago_id_ordem((string)$id))){
        if(!mercadopago_id_ordem((string)$id)){
            return array_replace($envelope, Array('valid' => false, 'error' => 'invalid-resource-id'));
        }
        $ordem = mercadopago_consultar_ordem(Array('order_id' => $id));
        $idAutenticado = $ordem['id'] ?? null;
        if(!is_string($idAutenticado) || strcasecmp($idAutenticado, $id) !== 0){
            return array_replace($envelope, Array('valid' => false, 'resource' => Array(), 'reference' => null, 'canonical_event' => 'other', 'error' => 'order-unavailable'));
        }
        return array_replace($envelope, Array(
            'gateway_payment_id' => $idAutenticado,
            'gateway_order_id' => $idAutenticado,
            'resource' => $ordem,
            'reference' => $ordem['external_reference'] ?? null,
            'canonical_event' => pagamentos_gateways_evento_por_status(mercadopago_status_canonico_ordem($ordem)),
        ));
    }
    if($tipo !== 'payment' || empty($id)) return $envelope;
    $pagamento = mercadopago_consultar_pagamento(Array('payment_id' => $id));
    if(!$pagamento) return array_replace($envelope, Array('error' => 'resource-unavailable'));

    $evento = $envelope['event_data'];
    $evento['_resource'] = $pagamento;
    $novo = pagamentos_gateway_normalizar_evento('mercadopago', $evento, Array(), $envelope['valid'], $envelope['gateway_id']);
    unset($novo['event_data']['_resource']);
    return $novo;
}

// ============================================================================
// DRIVER PAYPAL
// ============================================================================

function pagamentos_gateways_paypal_configurar($linha){
    return paypal_gateways_pagamentos_configurar(Array('id' => $linha['id'] ?? ''));
}

function pagamentos_gateways_paypal_testar($linha){
    $ok = (bool)paypal_autenticar();
    return Array('ok' => $ok, 'error' => $ok ? null : 'connection-failed');
}

function pagamentos_gateways_paypal_capacidades($linha, $cfg){
    return array_replace(pagamentos_gateways_capacidades_parcelamento($cfg, false), Array(
        'supports_recurring' => true,
        'supports_pix' => false,
    ));
}

/** Estado canônico a partir de um pedido (order) do PayPal. */
function pagamentos_gateways_paypal_status($pedido){
    $captura = $pedido['purchase_units'][0]['payments']['captures'][0] ?? null;
    if(is_array($captura)){
        $mapa = Array('COMPLETED' => 'paid', 'PENDING' => 'processing', 'DECLINED' => 'failed', 'FAILED' => 'failed',
            'REFUNDED' => 'refunded', 'PARTIALLY_REFUNDED' => 'partially_refunded');
        return $mapa[strtoupper((string)($captura['status'] ?? ''))] ?? 'processing';
    }
    $mapa = Array('CREATED' => 'awaiting', 'SAVED' => 'awaiting', 'PAYER_ACTION_REQUIRED' => 'awaiting',
        'APPROVED' => 'processing', 'COMPLETED' => 'paid', 'VOIDED' => 'cancelled');
    return $mapa[strtoupper((string)($pedido['status'] ?? ''))] ?? 'processing';
}

function pagamentos_gateways_paypal_iniciar($linha, $contexto, $cap){
    $pedido = paypal_criar_pedido(Array(
        'valor' => $contexto['amount'],
        'moeda' => $contexto['currency'],
        'descricao' => $contexto['description'] ?? '',
        'referencia' => $contexto['reference'] ?? null,
        'url_retorno' => $contexto['return_url'] ?? null,
        'url_cancelamento' => $contexto['cancel_url'] ?? null,
        'request_id' => $contexto['idempotency_key'] ?? null,
    ));
    if(!$pedido) return Array('ok' => false, 'error' => 'gateway-rejected');
    return Array(
        'ok' => true,
        'gateway_order_id' => $pedido['id'],
        'approve_url' => $pedido['approve_url'] ?? null,
        'canonical_status' => pagamentos_gateways_paypal_status($pedido['order_data'] ?? Array('status' => $pedido['status'] ?? '')),
        'raw' => $pedido['order_data'] ?? $pedido,
    );
}

function pagamentos_gateways_paypal_sincronizar($linha, $contexto){
    $orderId = (string)($contexto['gateway_order_id'] ?? ($contexto['gateway_payment_id'] ?? ''));
    if(!preg_match('/^[A-Z0-9]+$/i', $orderId)) return Array('ok' => false, 'error' => 'invalid-payment-id');
    $pedido = paypal_consultar_pedido(Array('order_id' => $orderId));
    if(!$pedido) return Array('ok' => false, 'error' => 'gateway-unavailable');

    $unidade = $pedido['purchase_units'][0] ?? Array();
    $captura = $unidade['payments']['captures'][0] ?? Array();
    $reembolsado = 0.0;
    foreach(($unidade['payments']['refunds'] ?? Array()) as $refund) $reembolsado += (float)($refund['amount']['value'] ?? 0);

    return Array(
        'ok' => true,
        'canonical_status' => pagamentos_gateways_paypal_status($pedido),
        'gateway_payment_id' => (string)($captura['id'] ?? ''),
        'gateway_order_id' => (string)($pedido['id'] ?? $orderId),
        'amount' => (float)($unidade['amount']['value'] ?? 0),
        'currency' => (string)($unidade['amount']['currency_code'] ?? ''),
        'refunded_amount' => $reembolsado > 0 ? $reembolsado : null,
        'reference' => $unidade['custom_id'] ?? ($unidade['reference_id'] ?? null),
        'raw' => $pedido,
    );
}

function pagamentos_gateways_paypal_reembolsar($linha, $contexto){
    $params = Array('capture_id' => (string)$contexto['gateway_payment_id']);
    if(isset($contexto['amount'])){
        $params['valor'] = (float)$contexto['amount'];
        $params['moeda'] = $contexto['currency'] ?? ($linha['moeda'] ?? 'BRL');
    }
    $r = paypal_reembolsar($params);
    if(!$r) return Array('ok' => false, 'error' => 'gateway-rejected');
    $ok = in_array(strtoupper((string)($r['status'] ?? '')), Array('COMPLETED', 'PENDING'), true);
    return Array(
        'ok' => $ok,
        'refund_id' => $r['id'] ?? null,
        'refunded_amount' => isset($r['refund_data']['amount']['value']) ? (float)$r['refund_data']['amount']['value'] : (isset($contexto['amount']) ? (float)$contexto['amount'] : null),
        'status' => $r['status'] ?? null,
        'raw' => $r,
        'error' => $ok ? null : 'refund-' . strtolower((string)($r['status'] ?? 'failed')),
    );
}

function pagamentos_gateways_paypal_dashboard_url($linha, $paymentId){
    $host = (($linha['ambiente'] ?? 'S') === 'P') ? 'https://www.paypal.com' : 'https://www.sandbox.paypal.com';
    return $host . '/activity/payment/' . rawurlencode($paymentId);
}

function pagamentos_gateways_paypal_validar_webhook($raw, $headers, $query){
    $normalizados = Array();
    foreach($headers as $chave => $valor) $normalizados[strtoupper((string)$chave)] = $valor;
    if(!paypal_validar_webhook(Array('headers' => $normalizados, 'body' => $raw))) return false;
    $evento = json_decode($raw, true);
    return is_array($evento) ? $evento : false;
}

function pagamentos_gateways_paypal_normalizar_webhook($evento, $query){
    $tipoEvento = strtoupper((string)($evento['event_type'] ?? 'unknown'));
    $r = is_array($evento['resource'] ?? null) ? $evento['resource'] : Array();
    $rid = isset($r['id']) ? (string)$r['id'] : null;

    $canonico = 'other';
    $pagamento = null; $pedido = null; $assinatura = null;
    switch($tipoEvento){
        case 'PAYMENT.CAPTURE.COMPLETED': $canonico = 'payment.paid'; $pagamento = $rid; break;
        case 'PAYMENT.CAPTURE.PENDING': $canonico = 'payment.processing'; $pagamento = $rid; break;
        case 'PAYMENT.CAPTURE.DENIED':
        case 'PAYMENT.CAPTURE.DECLINED': $canonico = 'payment.failed'; $pagamento = $rid; break;
        case 'PAYMENT.CAPTURE.REFUNDED':
        case 'PAYMENT.CAPTURE.REVERSED': $canonico = 'payment.refunded'; $pagamento = $rid; break;
        case 'CHECKOUT.ORDER.APPROVED': $canonico = 'payment.processing'; $pedido = $rid; break;
        case 'CHECKOUT.ORDER.COMPLETED': $canonico = 'payment.paid'; $pedido = $rid; break;
        case 'CHECKOUT.ORDER.VOIDED': $canonico = 'payment.cancelled'; $pedido = $rid; break;
        case 'BILLING.SUBSCRIPTION.ACTIVATED':
        case 'BILLING.SUBSCRIPTION.UPDATED':
        case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
        case 'BILLING.SUBSCRIPTION.SUSPENDED': $canonico = 'subscription.updated'; $assinatura = $rid; break;
        case 'BILLING.SUBSCRIPTION.CANCELLED':
        case 'BILLING.SUBSCRIPTION.EXPIRED': $canonico = 'subscription.cancelled'; $assinatura = $rid; break;
        case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED': $canonico = 'subscription.payment_failed'; $assinatura = $rid; break;
        case 'PAYMENT.SALE.COMPLETED':
            $pagamento = $rid;
            $assinatura = $r['billing_agreement_id'] ?? null;
            $canonico = $assinatura ? 'subscription.payment_succeeded' : 'payment.paid';
            break;
    }

    // Reembolso parcial: o recurso do evento REFUNDED é a captura com o valor restante.
    if($tipoEvento === 'PAYMENT.CAPTURE.REFUNDED' && strtoupper((string)($r['status'] ?? '')) === 'PARTIALLY_REFUNDED'){
        $canonico = 'payment.partially_refunded';
    }

    if($pedido === null) $pedido = $r['supplementary_data']['related_ids']['order_id'] ?? null;
    $referencia = $r['custom_id'] ?? ($r['purchase_units'][0]['custom_id'] ?? null);

    return Array(
        'event_id' => $evento['id'] ?? null,
        'event_type' => $tipoEvento,
        'canonical_event' => $canonico,
        'reference' => $referencia,
        'gateway_payment_id' => $pagamento,
        'gateway_order_id' => $pedido,
        'gateway_subscription_id' => $assinatura,
        'resource' => $r,
    );
}
