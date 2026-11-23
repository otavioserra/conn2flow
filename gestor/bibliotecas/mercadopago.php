<?php
/**
 * Biblioteca Mercado Pago — integração REST com a API do Mercado Pago (api.mercadopago.com).
 *
 * Espelha os padrões de `stripe.php` e `paypal.php`: configuração via tabela
 * `gateways_pagamentos` (modo gateway) ou `$_CONFIG['mercadopago']`, funções `mercadopago_*`
 * autocontidas e log via `log_disco` (REQ-301 / BATCH-310).
 *
 * Convenção de credenciais no gateway (tipo `mercadopago`):
 *  - `client_id`      → Public Key (TEST-... / APP_USR-...), usada no navegador pelo Brick
 *  - `client_secret`  → Access Token (TEST-... / APP_USR-...), só no servidor
 *  - `webhook_secret` → Assinatura secreta dos Webhooks (painel Mercado Pago Developers)
 *  - `configuracoes`  → JSON: {"mercadopago": {"installments": {"enabled": true, "max_installments": 12,
 *                       "interest_payer": "buyer"}, "render_mode": "auto"}}
 *
 * Transporte HTTP: `$_GESTOR['biblioteca-mercadopago']['transporte']` aceita um callable
 * `fn(array $req): array` (`$req` = method, url, headers, body, timeout; retorno = http_code,
 * body, error). Sem ele, a requisição sai por cURL. É o ponto de dublê dos testes unitários.
 *
 * API (REQ-305 / BATCH-314): os pagamentos nascem na **Orders API v1** (`POST /v1/orders`), padrão
 * das aplicações de Checkout Transparente — nelas, `POST /v1/payments` responde 401 (code 7). O id
 * guardado pelo chamador é o da Order (`ORD...`); consulta e reembolso escolhem a rota pelo id:
 * `ORD...` → `/v1/orders/{id}`, id numérico legado → `/v1/payments/{id}`. Aplicação antiga do tipo
 * "API de Pagamentos" mantém a criação legada com `configuracoes.mercadopago.api = "payments"`.
 */

define('MERCADOPAGO_API_URL', 'https://api.mercadopago.com');

/**
 * Estados do Mercado Pago → estados canônicos do Conn2Flow (contrato de pagamentos-gateways).
 * Cobre a API de Pagamentos legada e a Orders API v1 (`processed`, `action_required`, `canceled`...).
 */
define('MERCADOPAGO_STATUS_CANONICOS', Array(
    // Payments API (legado)
    'approved' => 'paid',
    'authorized' => 'processing',
    'pending' => 'awaiting',
    'in_process' => 'processing',
    'in_mediation' => 'processing',
    'rejected' => 'failed',
    'cancelled' => 'cancelled',
    'refunded' => 'refunded',
    'charged_back' => 'refunded',
    // Orders API v1
    'processed' => 'paid',
    'accredited' => 'paid',
    'created' => 'awaiting',
    'action_required' => 'awaiting',
    'waiting_payment' => 'awaiting',
    'waiting_transfer' => 'awaiting',
    'processing' => 'processing',
    'waiting_capture' => 'processing',
    'waiting_retry' => 'processing',
    'failed' => 'failed',
    'canceled' => 'cancelled',
    'expired' => 'cancelled',
    'partially_refunded' => 'partially_refunded',
));

/** Métodos sem cartão → `payment_method.type` da Orders API. Os demais ids são de cartão. */
define('MERCADOPAGO_TIPOS_METODO', Array(
    'pix' => 'bank_transfer',
    'bolbradesco' => 'ticket',
    'pec' => 'ticket',
    'account_money' => 'account_money',
));

function mercadopago_log_registro($logData = []){
    gestor_incluir_biblioteca('log');
    log_disco((is_array($logData) ? json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : (string)$logData), 'mercadopago');
}

// ============================================================================
// CONFIGURAÇÃO
// ============================================================================

/**
 * Configura a biblioteca a partir de um registro da tabela `gateways_pagamentos`.
 *
 * Sem `id`, usa o gateway Mercado Pago ativo de maior prioridade — a mesma ordem canônica de
 * `pagamentos_gateways_listar_ativos()` (prioridade, padrão, id numérico).
 *
 * @param array|false $params ['id' => id do gateway] ou ['gateway' => linha já carregada].
 * @return bool
 */
function mercadopago_gateways_pagamentos_configurar($params = false){
    global $_GESTOR;
    global $_CONFIG;

    if($params)foreach($params as $var => $val)$$var = $val;

    $linha = null;

    if(isset($gateway) && is_array($gateway)){
        $linha = $gateway;
    } elseif(isset($id)){
        $linha = banco_select(Array(
            'unico' => true,
            'tabela' => 'gateways_pagamentos',
            'campos' => '*',
            'extra' => "WHERE id = '" . banco_escape_field($id) . "' AND status = 'A'"
        ));
    } else {
        gestor_incluir_biblioteca('pagamentos-gateways');
        $ativos = function_exists('pagamentos_gateways_listar_ativos')
            ? pagamentos_gateways_listar_ativos(Array('tipo' => 'mercadopago'))
            : Array();
        $linha = $ativos[0] ?? null;
    }

    if(!$linha || ($linha['tipo'] ?? '') !== 'mercadopago' || ($linha['status'] ?? 'A') !== 'A'){
        mercadopago_log_registro(Array(
            'tipo' => 'mercadopago-gateway-config-error',
            'mensagem' => 'Gateway Mercado Pago não encontrado no banco',
            'detalhes' => Array('id' => $id ?? ($linha['id'] ?? null)),
        ));
        return false;
    }

    $configuracoes = json_decode((string)($linha['configuracoes'] ?? ''), true);
    if(!is_array($configuracoes)) $configuracoes = Array();

    $_CONFIG['mercadopago'] = Array(
        'mode' => ($linha['ambiente'] ?? 'S') === 'P' ? 'live' : 'sandbox',
        'currency' => $linha['moeda'] ?? 'BRL',
        'public_key' => (string)($linha['client_id'] ?? ''),
        'access_token' => (string)($linha['client_secret'] ?? ''),
        'webhook_secret' => (string)($linha['webhook_secret'] ?? ''),
        'configuracoes' => $configuracoes['mercadopago'] ?? $configuracoes,
    );

    $_GESTOR['biblioteca-mercadopago']['modo-gateway'] = true;
    $_GESTOR['biblioteca-mercadopago']['gateway-id'] = $linha['id'] ?? null;
    $_GESTOR['biblioteca-mercadopago']['gateway-dados'] = $linha;

    return true;
}

function mercadopago_is_modo_gateway(){
    global $_GESTOR;
    return !empty($_GESTOR['biblioteca-mercadopago']['modo-gateway']);
}

function mercadopago_obter_gateway_id(){
    global $_GESTOR;
    return $_GESTOR['biblioteca-mercadopago']['gateway-id'] ?? null;
}

function mercadopago_obter_gateway_dados(){
    global $_GESTOR;
    return $_GESTOR['biblioteca-mercadopago']['gateway-dados'] ?? null;
}

/**
 * @return array|false ['access_token','public_key','webhook_secret','currency','mode'] ou false.
 */
function mercadopago_obter_credenciais(){
    global $_CONFIG;

    if(empty($_CONFIG['mercadopago']['access_token'])){
        return false;
    }

    return Array(
        'access_token' => $_CONFIG['mercadopago']['access_token'],
        'public_key' => $_CONFIG['mercadopago']['public_key'] ?? '',
        'webhook_secret' => $_CONFIG['mercadopago']['webhook_secret'] ?? '',
        'currency' => $_CONFIG['mercadopago']['currency'] ?? 'BRL',
        'mode' => $_CONFIG['mercadopago']['mode'] ?? 'sandbox',
    );
}

/** Último erro da API (código, mensagem e causas do Mercado Pago), sem credenciais. */
function mercadopago_ultimo_erro(){
    global $_GESTOR;
    return $_GESTOR['biblioteca-mercadopago']['ultimo-erro'] ?? null;
}

// ============================================================================
// HTTP
// ============================================================================

/** Transporte cURL padrão. Retorna ['http_code' => int, 'body' => string|false, 'error' => string]. */
function mercadopago_transporte_curl($req){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $req['url']);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $req['method']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $req['headers']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)$req['timeout']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, (int)$req['timeout']));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    if($req['body'] !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $req['body']);

    $body = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);

    return Array('http_code' => $http_code, 'body' => $body, 'error' => $erro);
}

/**
 * Requisição HTTP à API do Mercado Pago (JSON nos dois sentidos).
 *
 * @param array|false $params
 * @param string $params['endpoint'] Ex.: '/v1/payments' (obrigatório)
 * @param string $params['method'] GET|POST|PUT (padrão GET)
 * @param array  $params['data'] Corpo JSON (POST/PUT) ou query string (GET)
 * @param string $params['idempotency_key'] X-Idempotency-Key; gerada automaticamente em POST
 * @param int    $params['timeout'] Segundos (padrão 30)
 * @return array|false ['http_code' => int, 'data' => array] ou false (sem credenciais / falha de rede).
 */
function mercadopago_requisicao($params = false){
    global $_GESTOR;

    if($params)foreach($params as $var => $val)$$var = $val;

    if(!isset($endpoint) || !is_string($endpoint) || $endpoint === '' || $endpoint[0] !== '/'){
        return false;
    }

    $credenciais = mercadopago_obter_credenciais();
    if(!$credenciais){
        mercadopago_log_registro(Array('tipo' => 'mercadopago-request-error', 'mensagem' => 'Credenciais Mercado Pago não configuradas', 'detalhes' => Array('endpoint' => $endpoint)));
        return false;
    }

    $method = isset($method) ? strtoupper((string)$method) : 'GET';
    $url = MERCADOPAGO_API_URL . $endpoint;
    $headers = Array(
        'Authorization: Bearer ' . $credenciais['access_token'],
        'Accept: application/json',
    );
    $body = null;

    if($method === 'GET'){
        if(isset($data) && is_array($data) && !empty($data)){
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        }
    } else {
        $headers[] = 'Content-Type: application/json';
        // Corpo vazio vai como objeto `{}` (a API recusa a lista `[]`).
        $body = json_encode((isset($data) && is_array($data) && !empty($data)) ? $data : new stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if($method === 'POST'){
            $chave = (isset($idempotency_key) && (string)$idempotency_key !== '') ? (string)$idempotency_key : bin2hex(random_bytes(16));
            $headers[] = 'X-Idempotency-Key: ' . $chave;
        }
    }

    $req = Array(
        'method' => $method,
        'url' => $url,
        'headers' => $headers,
        'body' => $body,
        'timeout' => isset($timeout) ? max(1, (int)$timeout) : 30,
    );

    $transporte = $_GESTOR['biblioteca-mercadopago']['transporte'] ?? null;
    $resposta = is_callable($transporte) ? $transporte($req) : mercadopago_transporte_curl($req);

    $_GESTOR['biblioteca-mercadopago']['ultimo-erro'] = null;

    if(!is_array($resposta) || ($resposta['body'] ?? false) === false){
        $_GESTOR['biblioteca-mercadopago']['ultimo-erro'] = Array('code' => 'network', 'message' => (string)($resposta['error'] ?? ''));
        mercadopago_log_registro(Array('tipo' => 'mercadopago-request-error', 'mensagem' => 'Erro de rede na requisição Mercado Pago', 'detalhes' => Array('endpoint' => $endpoint, 'method' => $method, 'erro' => $resposta['error'] ?? null)));
        return false;
    }

    $http_code = (int)($resposta['http_code'] ?? 0);
    $decoded = json_decode((string)$resposta['body'], true);
    if(!is_array($decoded)) $decoded = Array();

    if($http_code >= 400 || $http_code === 0){
        // Payments API: {error, message, cause}. Orders API v1: {errors: [{code, message, details}]}.
        $primeiro = (isset($decoded['errors'][0]) && is_array($decoded['errors'][0])) ? $decoded['errors'][0] : Array();
        $codigo = $decoded['error'] ?? ($primeiro['code'] ?? null);
        $mensagem = $decoded['message'] ?? ($primeiro['message'] ?? null);
        $causa = $decoded['cause'] ?? ($decoded['errors'] ?? null);
        $_GESTOR['biblioteca-mercadopago']['ultimo-erro'] = Array(
            'code' => (string)($codigo ?? ('http-' . $http_code)),
            'message' => (string)($mensagem ?? ''),
            'http_code' => $http_code,
            'cause' => $causa,
        );
        mercadopago_log_registro(Array(
            'tipo' => 'mercadopago-api-error',
            'mensagem' => 'Erro na API do Mercado Pago',
            'detalhes' => Array('endpoint' => $endpoint, 'method' => $method, 'http_code' => $http_code, 'error' => $codigo, 'message' => $mensagem, 'cause' => $causa),
        ));
    }

    return Array('http_code' => $http_code, 'data' => $decoded);
}

/** Resposta 2xx com corpo. */
function mercadopago_resposta_ok($resp){
    return is_array($resp) && $resp['http_code'] >= 200 && $resp['http_code'] < 300;
}

// ============================================================================
// CONEXÃO
// ============================================================================

/** Ambiente declarado pelo prefixo da credencial: 'S' (TEST-), 'P' (APP_USR-) ou null. */
function mercadopago_ambiente_credencial($chave){
    $chave = (string)$chave;
    if(strpos($chave, 'TEST-') === 0) return 'S';
    if(strpos($chave, 'APP_USR-') === 0) return 'P';
    return null;
}

/**
 * Testa as credenciais (GET /v1/payment_methods) e a coerência entre chaves e ambiente.
 *
 * Credencial `TEST-` num gateway de Produção é incoerente; o inverso (`APP_USR-` em Sandbox) é
 * aceito, porque os usuários de teste do Mercado Pago também recebem credenciais `APP_USR-`.
 *
 * @return array ['ok' => bool, 'http_code' => int|null, 'error' => string|null]
 */
function mercadopago_testar_conexao(){
    global $_CONFIG;

    $credenciais = mercadopago_obter_credenciais();
    if(!$credenciais) return Array('ok' => false, 'http_code' => null, 'error' => 'missing-credentials');

    $ambienteToken = mercadopago_ambiente_credencial($credenciais['access_token']);
    $ambienteChave = mercadopago_ambiente_credencial($credenciais['public_key']);
    $ambienteGateway = ($credenciais['mode'] === 'live') ? 'P' : 'S';

    if($ambienteGateway === 'P' && ($ambienteToken === 'S' || $ambienteChave === 'S')){
        return Array('ok' => false, 'http_code' => null, 'error' => 'environment-mismatch');
    }
    if($ambienteToken !== null && $ambienteChave !== null && $ambienteToken !== $ambienteChave){
        return Array('ok' => false, 'http_code' => null, 'error' => 'key-pair-mismatch');
    }

    $resp = mercadopago_requisicao(Array('endpoint' => '/v1/payment_methods', 'timeout' => 15));
    if(!$resp) return Array('ok' => false, 'http_code' => null, 'error' => 'network');
    if(!mercadopago_resposta_ok($resp)) return Array('ok' => false, 'http_code' => $resp['http_code'], 'error' => 'http-' . $resp['http_code']);

    return Array('ok' => true, 'http_code' => $resp['http_code'], 'error' => null);
}

// ============================================================================
// PAGAMENTOS
// ============================================================================

/**
 * Monta o corpo de `POST /v1/payments` (API de Pagamentos legada, só com
 * `configuracoes.mercadopago.api = "payments"`). Separada da requisição para ser verificável sem rede.
 *
 * @param array $params ['valor' => decimal obrig, 'payment_method_id' => obrig ('pix', 'bolbradesco',
 *                       'visa'...), 'token' => token do cartão (Brick), 'installments' => int,
 *                       'issuer_id' => opc, 'payer' => array (email obrig; identification, first_name...),
 *                       'referencia' => external_reference, 'descricao' => opc, 'metadata' => array opc,
 *                       'notification_url' => opc, 'statement_descriptor' => opc,
 *                       'date_of_expiration' => ISO 8601 opc (Pix/Boleto), 'capture' => bool opc]
 * @return array|false Corpo ou false quando faltam campos obrigatórios.
 */
function mercadopago_pagamento_payload($params = Array()){
    $valor = round((float)($params['valor'] ?? 0), 2);
    $metodo = trim((string)($params['payment_method_id'] ?? ''));
    $payer = (isset($params['payer']) && is_array($params['payer'])) ? $params['payer'] : Array();

    if($valor <= 0 || $metodo === '' || empty($payer['email'])) return false;

    $cartao = !in_array($metodo, Array('pix', 'bolbradesco', 'pec', 'account_money'), true);
    if($cartao && empty($params['token'])) return false;

    $dados = Array(
        'transaction_amount' => $valor,
        'payment_method_id' => $metodo,
        'payer' => $payer,
    );

    if($cartao){
        $dados['token'] = (string)$params['token'];
        $dados['installments'] = max(1, min(12, (int)($params['installments'] ?? 1)));
        if(!empty($params['issuer_id'])) $dados['issuer_id'] = is_numeric($params['issuer_id']) ? (int)$params['issuer_id'] : (string)$params['issuer_id'];
        if(isset($params['capture'])) $dados['capture'] = (bool)$params['capture'];
    }

    if(!empty($params['referencia'])) $dados['external_reference'] = mb_substr((string)$params['referencia'], 0, 256);
    if(!empty($params['descricao'])) $dados['description'] = mb_substr((string)$params['descricao'], 0, 256);
    if(!empty($params['metadata']) && is_array($params['metadata'])) $dados['metadata'] = $params['metadata'];
    if(!empty($params['notification_url'])) $dados['notification_url'] = (string)$params['notification_url'];
    if(!empty($params['statement_descriptor'])) $dados['statement_descriptor'] = mb_substr((string)$params['statement_descriptor'], 0, 22);
    if(!empty($params['date_of_expiration'])) $dados['date_of_expiration'] = (string)$params['date_of_expiration'];

    return $dados;
}

// ============================================================================
// ORDERS API v1
// ============================================================================

/** Id de Order da Orders API v1 (`ORD...`). Id numérico é pagamento legado. */
function mercadopago_id_ordem($id){
    return is_string($id) && preg_match('/^ORD[A-Za-z0-9]+$/', $id) === 1;
}

/** API de criação configurada no gateway: 'orders' (padrão) ou 'payments' (aplicação legada). */
function mercadopago_api_criacao(){
    global $_CONFIG;
    $api = strtolower(trim((string)($_CONFIG['mercadopago']['configuracoes']['api'] ?? '')));
    return $api === 'payments' ? 'payments' : 'orders';
}

/**
 * `external_reference` da Orders API só aceita `[A-Za-z0-9_-]` (até 64): o Lab recusou
 * `ecommerce:261123-X` com 400 `property_value` ("does not match pattern"). Codificação reversível
 * e legível: `_` e qualquer outro caractere fora do padrão viram `_XX` (hex maiúsculo) —
 * `ecommerce:261123-X` → `ecommerce_3A261123-X`.
 *
 * @return string|false Referência codificada, ou false se passar de 64 caracteres.
 */
function mercadopago_referencia_codificar($referencia){
    $codificada = preg_replace_callback('/[^A-Za-z0-9-]/', function($m){
        return '_' . strtoupper(bin2hex($m[0]));
    }, (string)$referencia);
    return strlen($codificada) <= 64 ? $codificada : false;
}

/** Inversa de `mercadopago_referencia_codificar`. */
function mercadopago_referencia_decodificar($referencia){
    if(!is_string($referencia)) return $referencia;
    return preg_replace_callback('/_([0-9A-F]{2})/', function($m){
        return chr(hexdec($m[1]));
    }, $referencia);
}

/**
 * Order devolvida pela API com `external_reference` decodificado (a referência do Conn2Flow);
 * o valor enviado ao Mercado Pago fica em `external_reference_gateway`.
 */
function mercadopago_ordem_decodificar($ordem){
    if(!is_array($ordem) || !isset($ordem['external_reference']) || !is_string($ordem['external_reference'])) return $ordem;
    $ordem['external_reference_gateway'] = $ordem['external_reference'];
    $ordem['external_reference'] = mercadopago_referencia_decodificar($ordem['external_reference']);
    return $ordem;
}

/** Valor decimal no formato da Orders API: string com 2 casas e ponto ("1999.00"). */
function mercadopago_valor_ordem($valor){
    return number_format(round((float)$valor, 2), 2, '.', '');
}

/**
 * `payment_method.type` da Orders API: Pix → bank_transfer, Boleto/Lotérica → ticket, demais →
 * credit_card. `debit_card`/`prepaid_card` só quando o chamador informa (`payment_type`).
 */
function mercadopago_tipo_metodo($metodo, $tipoInformado = null){
    $tipoInformado = strtolower(trim((string)$tipoInformado));
    if(in_array($tipoInformado, Array('credit_card', 'debit_card', 'prepaid_card', 'bank_transfer', 'ticket', 'account_money'), true)) return $tipoInformado;
    return MERCADOPAGO_TIPOS_METODO[strtolower((string)$metodo)] ?? 'credit_card';
}

/**
 * Duração ISO 8601 do Pix/Boleto (`expiration_time`) a partir de uma data de expiração absoluta.
 * A Orders API aceita de 30 minutos a 30 dias no Pix; fora disso, limita ao intervalo.
 */
function mercadopago_duracao_expiracao($dataExpiracao, $agora = null){
    $alvo = strtotime((string)$dataExpiracao);
    if($alvo === false) return null;
    $minutos = (int)ceil(($alvo - ($agora ?? time())) / 60);
    $minutos = max(30, min(30 * 24 * 60, $minutos));
    return 'PT' . $minutos . 'M';
}

/**
 * Monta o corpo de `POST /v1/orders` (Checkout Transparente, `processing_mode = automatic`).
 *
 * Mesmos parâmetros de `mercadopago_pagamento_payload` + 'payment_type' opc (credit_card,
 * debit_card...) e 'expiration_time' opc (duração ISO 8601). `metadata` e `notification_url` não
 * existem na Orders API: os webhooks são configurados no painel (evento "Order (Mercado Pago)").
 *
 * @return array|false Corpo ou false quando faltam campos obrigatórios.
 */
function mercadopago_ordem_payload($params = Array()){
    $valor = round((float)($params['valor'] ?? 0), 2);
    $metodo = strtolower(trim((string)($params['payment_method_id'] ?? '')));
    $payer = (isset($params['payer']) && is_array($params['payer'])) ? $params['payer'] : Array();

    if($valor <= 0 || $metodo === '' || empty($payer['email'])) return false;

    $tipo = mercadopago_tipo_metodo($metodo, $params['payment_type'] ?? null);
    $cartao = in_array($tipo, Array('credit_card', 'debit_card', 'prepaid_card'), true);
    if($cartao && empty($params['token'])) return false;

    $valorTexto = mercadopago_valor_ordem($valor);
    $metodoDados = Array('id' => $metodo, 'type' => $tipo);
    if($cartao){
        $metodoDados['token'] = (string)$params['token'];
        $metodoDados['installments'] = $tipo === 'credit_card' ? max(1, min(12, (int)($params['installments'] ?? 1))) : 1;
    }
    if(!empty($params['statement_descriptor'])) $metodoDados['statement_descriptor'] = mb_substr((string)$params['statement_descriptor'], 0, 22);

    $pagamento = Array('amount' => $valorTexto, 'payment_method' => $metodoDados);
    if(!$cartao){
        $duracao = !empty($params['expiration_time'])
            ? (string)$params['expiration_time']
            : (!empty($params['date_of_expiration']) ? mercadopago_duracao_expiracao($params['date_of_expiration']) : null);
        if($duracao) $pagamento['expiration_time'] = $duracao;
    }

    $pagador = Array('email' => (string)$payer['email']);
    foreach(Array('first_name', 'last_name', 'entity_type') as $campo){
        if(!empty($payer[$campo])) $pagador[$campo] = mb_substr((string)$payer[$campo], 0, 100);
    }
    if(!empty($payer['identification']['type']) && !empty($payer['identification']['number'])){
        $pagador['identification'] = Array('type' => (string)$payer['identification']['type'], 'number' => (string)$payer['identification']['number']);
    }

    $dados = Array(
        'type' => 'online',
        'processing_mode' => 'automatic',
        'total_amount' => $valorTexto,
        'payer' => $pagador,
        'transactions' => Array('payments' => Array($pagamento)),
    );
    if(!empty($params['referencia'])){
        // Truncar quebraria a prova de posse da sincronização: referência longa demais é recusada.
        $referencia = mercadopago_referencia_codificar($params['referencia']);
        if($referencia === false){
            mercadopago_log_registro(Array('tipo' => 'mercadopago-reference-too-long', 'mensagem' => 'external_reference excede 64 caracteres na Orders API', 'detalhes' => Array('tamanho' => strlen((string)$params['referencia']))));
            return false;
        }
        $dados['external_reference'] = $referencia;
    }
    if(!empty($params['descricao'])) $dados['description'] = mb_substr((string)$params['descricao'], 0, 150);

    return $dados;
}

/** Primeiro pagamento (`transactions.payments[0]`) de uma Order, ou array vazio. */
function mercadopago_ordem_pagamento($ordem){
    $p = $ordem['transactions']['payments'][0] ?? null;
    return is_array($p) ? $p : Array();
}

/** Soma dos reembolsos processados de uma Order (`transactions.refunds[]`). */
function mercadopago_ordem_reembolsado($ordem){
    $total = 0.0;
    foreach((array)($ordem['transactions']['refunds'] ?? Array()) as $r){
        if(!is_array($r)) continue;
        if(in_array(strtolower((string)($r['status'] ?? 'processed')), Array('failed', 'rejected', 'canceled', 'cancelled'), true)) continue;
        $total += (float)($r['amount'] ?? 0);
    }
    return round($total, 2);
}

/**
 * Normaliza uma Order no formato canônico consumido pelos módulos (sem rede).
 *
 * @return array ['id' (ORD...), 'payment_id' (PAY...), 'status', 'status_detail', 'canonical_status',
 *                'amount', 'paid_amount', 'refunded_amount', 'currency', 'installments',
 *                'external_reference', 'payment_method_id', 'payment_type', 'pix' => ?array,
 *                'ticket_url', 'date_of_expiration']
 */
function mercadopago_ordem_normalizar($ordem){
    global $_CONFIG;
    $ordem = is_array($ordem) ? $ordem : Array();
    $pag = mercadopago_ordem_pagamento($ordem);
    $metodo = is_array($pag['payment_method'] ?? null) ? $pag['payment_method'] : Array();
    $pix = (!empty($metodo['qr_code']) || !empty($metodo['qr_code_base64'])) ? Array(
        'qr_code' => $metodo['qr_code'] ?? null,
        'qr_code_base64' => $metodo['qr_code_base64'] ?? null,
        'ticket_url' => $metodo['ticket_url'] ?? null,
    ) : null;
    $moeda = (string)($ordem['currency'] ?? ($ordem['currency_id'] ?? ($_CONFIG['mercadopago']['currency'] ?? 'BRL')));

    return Array(
        'id' => (string)($ordem['id'] ?? ''),
        'payment_id' => isset($pag['id']) ? (string)$pag['id'] : null,
        'status' => (string)($ordem['status'] ?? ''),
        'status_detail' => (string)($ordem['status_detail'] ?? ($pag['status_detail'] ?? '')),
        'canonical_status' => mercadopago_status_canonico_ordem($ordem),
        'amount' => round((float)($ordem['total_amount'] ?? ($pag['amount'] ?? 0)), 2),
        'paid_amount' => round((float)($ordem['total_paid_amount'] ?? ($pag['paid_amount'] ?? 0)), 2),
        'refunded_amount' => mercadopago_ordem_reembolsado($ordem),
        'currency' => strtoupper($moeda),
        'installments' => max(1, (int)($metodo['installments'] ?? 1)),
        'external_reference' => $ordem['external_reference'] ?? null,
        'payment_method_id' => $metodo['id'] ?? null,
        'payment_type' => $metodo['type'] ?? null,
        'pix' => $pix,
        'ticket_url' => $metodo['ticket_url'] ?? null,
        'date_of_expiration' => $pag['date_of_expiration'] ?? null,
    );
}

// ============================================================================
// PAGAMENTOS (fachada: Orders API v1 com fallback legado)
// ============================================================================

/**
 * Cria um pagamento: cartão tokenizado pelo Payment Brick (parcelado ou à vista), Pix ou Boleto.
 *
 * Padrão: `POST /v1/orders` (Order `ORD...` com o pagamento em `transactions.payments[0]`).
 * Com `configuracoes.mercadopago.api = "payments"`: `POST /v1/payments` legado.
 *
 * Um cartão recusado volta com HTTP 2xx e status `failed`/`rejected`: o retorno é o recurso, e
 * quem decide é o status. `false` significa que a API não aceitou a requisição
 * (ver `mercadopago_ultimo_erro`).
 *
 * @param array $params Ver `mercadopago_ordem_payload` + 'idempotency_key' opc.
 * @return array|false Order (ou pagamento legado) criado, ou false.
 */
function mercadopago_criar_pagamento($params = Array()){
    $legado = mercadopago_api_criacao() === 'payments';
    $dados = $legado ? mercadopago_pagamento_payload($params) : mercadopago_ordem_payload($params);
    if($dados === false) return false;

    $resp = mercadopago_requisicao(Array(
        'endpoint' => $legado ? '/v1/payments' : '/v1/orders',
        'method' => 'POST',
        'data' => $dados,
        'idempotency_key' => $params['idempotency_key'] ?? '',
    ));
    if(!mercadopago_resposta_ok($resp) || empty($resp['data']['id'])) return false;

    return $legado ? $resp['data'] : mercadopago_ordem_decodificar($resp['data']);
}

/** Consulta uma Order (`GET /v1/orders/{id}`), com `external_reference` decodificado. */
function mercadopago_consultar_ordem($params = Array()){
    $id = (string)($params['order_id'] ?? '');
    if(!mercadopago_id_ordem($id)) return false;

    $resp = mercadopago_requisicao(Array('endpoint' => '/v1/orders/' . rawurlencode($id)));
    if(!mercadopago_resposta_ok($resp)) return false;
    return mercadopago_ordem_decodificar($resp['data']);
}

/**
 * Consulta pelo id guardado: `ORD...` → `GET /v1/orders/{id}`; numérico → `GET /v1/payments/{id}`.
 */
function mercadopago_consultar_pagamento($params = Array()){
    $id = (string)($params['payment_id'] ?? '');
    if(mercadopago_id_ordem($id)) return mercadopago_consultar_ordem(Array('order_id' => $id));
    if($id === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $id)) return false;

    $resp = mercadopago_requisicao(Array('endpoint' => '/v1/payments/' . rawurlencode($id)));
    if(!mercadopago_resposta_ok($resp)) return false;
    return $resp['data'];
}

/**
 * Reembolsa: total sem `valor`, parcial com `valor`.
 *
 * - `ORD...`: `POST /v1/orders/{id}/refund`. Total vai com corpo vazio; parcial vai com
 *   `transactions: [{id: PAY..., amount: "10.00"}]` — sem `transaction_id`, o PAY vem de
 *   `GET /v1/orders/{id}`. O retorno é a Order com `transactions.refunds[]`.
 * - Numérico: `POST /v1/payments/{id}/refunds` legado.
 *
 * @param array $params ['payment_id' => obrig, 'valor' => decimal opc, 'transaction_id' => opc,
 *                       'idempotency_key' => opc]
 * @return array|false Reembolso (Order atualizada ou refund legado) ou false.
 */
function mercadopago_reembolsar($params = Array()){
    $id = (string)($params['payment_id'] ?? '');
    $parcial = isset($params['valor']) && (float)$params['valor'] > 0;

    if(mercadopago_id_ordem($id)){
        $dados = Array();
        if($parcial){
            $transacao = (string)($params['transaction_id'] ?? '');
            if($transacao === ''){
                $ordem = mercadopago_consultar_ordem(Array('order_id' => $id));
                $transacao = (string)(mercadopago_ordem_pagamento($ordem ?: Array())['id'] ?? '');
            }
            if($transacao === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $transacao)) return false;
            $dados['transactions'] = Array(Array('id' => $transacao, 'amount' => mercadopago_valor_ordem($params['valor'])));
        }
        $resp = mercadopago_requisicao(Array(
            'endpoint' => '/v1/orders/' . rawurlencode($id) . '/refund',
            'method' => 'POST',
            'data' => $dados,
            'idempotency_key' => $params['idempotency_key'] ?? '',
        ));
        if(!mercadopago_resposta_ok($resp)) return false;
        return mercadopago_ordem_decodificar($resp['data']);
    }

    if($id === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $id)) return false;

    $dados = Array();
    if($parcial){
        $dados['amount'] = round((float)$params['valor'], 2);
    }

    $resp = mercadopago_requisicao(Array(
        'endpoint' => '/v1/payments/' . rawurlencode($id) . '/refunds',
        'method' => 'POST',
        'data' => $dados,
        'idempotency_key' => $params['idempotency_key'] ?? '',
    ));
    if(!mercadopago_resposta_ok($resp)) return false;
    return $resp['data'];
}

/**
 * Último reembolso de uma resposta de `mercadopago_reembolsar` no formato canônico:
 * ['id', 'amount', 'status']. Aceita a Order (`transactions.refunds[]`) e o refund legado.
 */
function mercadopago_reembolso_resumo($resposta){
    if(!is_array($resposta)) return Array('id' => null, 'amount' => null, 'status' => null);
    $refunds = $resposta['transactions']['refunds'] ?? null;
    if(is_array($refunds)){
        $ultimo = end($refunds);
        $ultimo = is_array($ultimo) ? $ultimo : Array();
        return Array(
            'id' => isset($ultimo['id']) ? (string)$ultimo['id'] : null,
            'amount' => isset($ultimo['amount']) ? round((float)$ultimo['amount'], 2) : null,
            'status' => (string)($ultimo['status'] ?? ($resposta['status'] ?? '')) ?: null,
        );
    }
    if(mercadopago_id_ordem((string)($resposta['id'] ?? ''))){
        return Array('id' => null, 'amount' => null, 'status' => isset($resposta['status']) ? (string)$resposta['status'] : null);
    }
    return Array(
        'id' => isset($resposta['id']) ? (string)$resposta['id'] : null,
        'amount' => isset($resposta['amount']) ? (float)$resposta['amount'] : null,
        'status' => $resposta['status'] ?? null,
    );
}

// ============================================================================
// WEBHOOK
// ============================================================================

/**
 * Lê o `data.id` da notificação. O PHP converte `?data.id=` em `$_GET['data_id']`; o corpo JSON
 * traz `data.id`; o IPN antigo usa `?id=`.
 */
function mercadopago_webhook_data_id($payload = Array(), $query = Array()){
    $query = is_array($query) ? $query : Array();
    $payload = is_array($payload) ? $payload : Array();
    foreach(Array($query['data_id'] ?? null, $query['data.id'] ?? null, $payload['data']['id'] ?? null, $query['id'] ?? null) as $valor){
        if(is_scalar($valor) && (string)$valor !== '') return (string)$valor;
    }
    return null;
}

/** Tipo da notificação informado no corpo ou na query (webhooks anteriores usam topic). */
function mercadopago_webhook_tipo($payload, $query = Array()){
    $tipo = $payload['type'] ?? $payload['topic'] ?? $query['type'] ?? $query['topic'] ?? null;
    return is_string($tipo) && $tipo !== '' ? $tipo : 'unknown';
}

/**
 * Valida a assinatura `x-signature` de um webhook por HMAC-SHA256.
 *
 * Manifesto oficial: `id:[data.id];request-id:[x-request-id];ts:[ts];` — parte ausente sai do
 * manifesto; `data.id` alfanumérico entra em minúsculas.
 *
 * @param array $params ['x_signature' => header, 'x_request_id' => header, 'payload' => corpo cru,
 *                       'query' => array da query string, 'data_id' => opc (sobrepõe a leitura),
 *                       'tolerancia' => segundos (0 = sem checagem de tempo; padrão 0)]
 * @return array|false Notificação decodificada (corpo + `data.id` resolvido) ou false.
 */
function mercadopago_validar_webhook($params = Array()){
    $credenciais = mercadopago_obter_credenciais();
    $secret = $credenciais ? (string)$credenciais['webhook_secret'] : (string)($GLOBALS['_CONFIG']['mercadopago']['webhook_secret'] ?? '');
    $header = (string)($params['x_signature'] ?? '');
    $requestId = trim((string)($params['x_request_id'] ?? ''));
    $payloadCru = (string)($params['payload'] ?? '');
    $query = (isset($params['query']) && is_array($params['query'])) ? $params['query'] : Array();
    $tolerancia = (int)($params['tolerancia'] ?? 0);

    if($secret === '' || $header === '') return false;

    $payload = $payloadCru !== '' ? json_decode($payloadCru, true) : Array();
    if($payloadCru !== '' && !is_array($payload)) return false;

    $ts = null;
    $assinaturas = Array();
    foreach(explode(',', $header) as $parte){
        $kv = explode('=', trim($parte), 2);
        if(count($kv) !== 2) continue;
        $chave = strtolower(trim($kv[0]));
        if($chave === 'ts') $ts = trim($kv[1]);
        if($chave === 'v1') $assinaturas[] = strtolower(trim($kv[1]));
    }
    if($ts === null || $ts === '' || !ctype_digit($ts) || empty($assinaturas)) return false;

    if($tolerancia > 0){
        $segundos = strlen($ts) > 10 ? intdiv((int)$ts, 1000) : (int)$ts;
        if(abs(time() - $segundos) > $tolerancia) return false;
    }

    $dataId = isset($params['data_id']) && (string)$params['data_id'] !== '' ? (string)$params['data_id'] : mercadopago_webhook_data_id($payload, $query);
    // Só o manifesto usa minúsculas: o id original (ex.: Order `ORD...`) segue para a consulta.
    $idManifesto = ($dataId !== null && ctype_alnum($dataId)) ? strtolower($dataId) : $dataId;

    $manifesto = '';
    if($idManifesto !== null) $manifesto .= 'id:' . $idManifesto . ';';
    if($requestId !== '') $manifesto .= 'request-id:' . $requestId . ';';
    $manifesto .= 'ts:' . $ts . ';';

    $esperada = hash_hmac('sha256', $manifesto, $secret);
    $valida = false;
    foreach($assinaturas as $assinatura){
        if(hash_equals($esperada, $assinatura)){ $valida = true; break; }
    }
    if(!$valida){
        mercadopago_log_registro(Array('tipo' => 'mercadopago-webhook-invalid-signature', 'mensagem' => 'Assinatura de webhook Mercado Pago inválida', 'detalhes' => Array('data_id' => $dataId, 'request_id' => $requestId)));
        return false;
    }

    if(!is_array($payload)) $payload = Array();
    if($dataId !== null){
        if(!isset($payload['data']) || !is_array($payload['data'])) $payload['data'] = Array();
        if(!isset($payload['data']['id'])) $payload['data']['id'] = $dataId;
    }
    $tipo = mercadopago_webhook_tipo($payload, $query);
    if($tipo !== 'unknown') $payload['type'] = $tipo;
    if($tipo === 'order' && $dataId !== null){
        $payload['data']['id'] = $dataId;
    }

    return $payload;
}

// ============================================================================
// STATUS
// ============================================================================

/**
 * Traduz o status do Mercado Pago (Payments API ou Orders API v1) para o estado canônico do Conn2Flow
 * (`awaiting|processing|paid|failed|refunded|partially_refunded|cancelled`).
 * Status desconhecido nunca vira `paid`: cai em `processing`.
 */
function mercadopago_traduzir_status($status){
    $status = strtolower(trim((string)$status));
    if($status === '') return 'awaiting';
    return MERCADOPAGO_STATUS_CANONICOS[$status] ?? 'processing';
}

/**
 * Estado canônico de uma Order (Orders API v1) por `status` + `status_detail`:
 * processed/accredited → paid; processed/partially_refunded → partially_refunded; refunded →
 * refunded; action_required/waiting_payment|waiting_transfer → awaiting;
 * action_required/waiting_capture|waiting_retry → processing; canceled|expired → cancelled;
 * failed → failed; charged_back → refunded. Reembolsos em `transactions.refunds[]` também contam.
 */
function mercadopago_status_canonico_ordem($ordem){
    if(!is_array($ordem)) return 'awaiting';
    $status = strtolower(trim((string)($ordem['status'] ?? '')));
    $detalhe = strtolower(trim((string)($ordem['status_detail'] ?? '')));

    if($status === 'action_required' && in_array($detalhe, Array('waiting_capture', 'waiting_retry'), true)) return 'processing';
    if($status === 'failed' && $detalhe === 'expired') return 'cancelled';
    if($status === 'processed' && in_array($detalhe, Array('partially_refunded', 'refunded'), true)) $status = $detalhe;

    $canonico = mercadopago_traduzir_status($status);
    if($canonico === 'paid'){
        $reembolsado = mercadopago_ordem_reembolsado($ordem);
        if($reembolsado > 0){
            $total = (float)($ordem['total_amount'] ?? 0);
            return ($total > 0 && $reembolsado + 0.005 >= $total) ? 'refunded' : 'partially_refunded';
        }
    }
    return $canonico;
}

/** Recurso da Orders API (Order `ORD...` ou envelope com `transactions`)? */
function mercadopago_e_ordem($recurso){
    return is_array($recurso) && (mercadopago_id_ordem((string)($recurso['id'] ?? '')) || isset($recurso['transactions']['payments']));
}

/**
 * Estado canônico de um pagamento inteiro: `approved` com reembolso parcial vira `partially_refunded`.
 * Recebe também uma Order da Orders API v1 (delegando a `mercadopago_status_canonico_ordem`).
 */
function mercadopago_status_canonico_pagamento($pagamento){
    if(!is_array($pagamento)) return 'awaiting';
    if(mercadopago_e_ordem($pagamento)) return mercadopago_status_canonico_ordem($pagamento);
    $canonico = mercadopago_traduzir_status($pagamento['status'] ?? '');
    $reembolsado = (float)($pagamento['transaction_amount_refunded'] ?? 0);
    if($canonico === 'paid' && $reembolsado > 0){
        $total = (float)($pagamento['transaction_amount'] ?? 0);
        return ($total > 0 && $reembolsado + 0.005 >= $total) ? 'refunded' : 'partially_refunded';
    }
    return $canonico;
}

function mercadopago_info(){
    $credenciais = mercadopago_obter_credenciais();
    return Array(
        'biblioteca' => 'mercadopago',
        'configurada' => ($credenciais !== false),
        'modo_gateway' => mercadopago_is_modo_gateway(),
        'mode' => $credenciais ? $credenciais['mode'] : null,
    );
}
