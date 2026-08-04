<?php
/**
 * Biblioteca Stripe — integração REST com a API do Stripe (api.stripe.com).
 *
 * Espelha os padrões da biblioteca `paypal.php`: configuração via tabela
 * `gateways_pagamentos` (modo gateway) ou `$_CONFIG['stripe']`, funções `stripe_*`
 * autocontidas e log via `log_disco`.
 *
 * Convenção de credenciais no gateway (tipo `stripe`):
 *  - `client_id`     → Publishable key (pk_test_... / pk_live_...)
 *  - `client_secret` → Secret key (sk_test_... / sk_live_...)
 *  - `webhook_secret`→ Signing secret do endpoint de webhook (whsec_...)
 *
 * A versão da API é fixada em `STRIPE_API_VERSION` para que o payload não mude
 * conforme a versão default da conta (ex.: expand de `latest_invoice.payment_intent`).
 */

define('STRIPE_API_VERSION', '2024-06-20');

function stripe_log_registro($logData = []){
    gestor_incluir_biblioteca('log');
    log_disco((is_array($logData) ? json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : (string)$logData), 'stripe');
}

/**
 * Configura a biblioteca a partir de um registro da tabela `gateways_pagamentos`.
 *
 * @param array|false $params ['id' => id do gateway] ou vazio para o gateway padrão do tipo stripe.
 * @return bool
 */
function stripe_gateways_pagamentos_configurar($params = false){
    global $_GESTOR;
    global $_CONFIG;

    if($params)foreach($params as $var => $val)$$var = $val;

    $gateway = null;

    if(isset($id)){
        $gateway = banco_select(Array(
            'unico' => true,
            'tabela' => 'gateways_pagamentos',
            'campos' => Array('*'),
            'extra' => "WHERE id = '" . banco_escape_field($id) . "' AND status = 'A'"
        ));
    } else {
        $gateway = banco_select(Array(
            'unico' => true,
            'tabela' => 'gateways_pagamentos',
            'campos' => Array('*'),
            'extra' => "WHERE tipo = 'stripe' AND padrao = 'S' AND status = 'A'"
        ));
        if(!$gateway){
            $gateway = banco_select(Array(
                'unico' => true,
                'tabela' => 'gateways_pagamentos',
                'campos' => Array('*'),
                'extra' => "WHERE tipo = 'stripe' AND status = 'A' ORDER BY id ASC LIMIT 1"
            ));
        }
    }

    if(!$gateway || ($gateway['tipo'] ?? '') !== 'stripe'){
        stripe_log_registro(Array(
            'tipo' => 'stripe-gateway-config-error',
            'mensagem' => 'Gateway Stripe não encontrado no banco',
            'detalhes' => Array('id' => $id ?? null)
        ));
        return false;
    }

    $_CONFIG['stripe'] = Array(
        'mode' => $gateway['ambiente'] === 'P' ? 'live' : 'sandbox',
        'currency' => $gateway['moeda'] ?? 'BRL',
        'publishable_key' => $gateway['client_id'],
        'secret_key' => $gateway['client_secret'],
        'webhook_secret' => $gateway['webhook_secret'] ?? '',
    );

    $_GESTOR['biblioteca-stripe']['modo-gateway'] = true;
    $_GESTOR['biblioteca-stripe']['gateway-id'] = $gateway['id'];
    $_GESTOR['biblioteca-stripe']['gateway-dados'] = $gateway;

    return true;
}

function stripe_is_modo_gateway(){
    global $_GESTOR;
    return !empty($_GESTOR['biblioteca-stripe']['modo-gateway']);
}

function stripe_obter_gateway_id(){
    global $_GESTOR;
    return $_GESTOR['biblioteca-stripe']['gateway-id'] ?? null;
}

function stripe_obter_gateway_dados(){
    global $_GESTOR;
    return $_GESTOR['biblioteca-stripe']['gateway-dados'] ?? null;
}

/**
 * @return array|false ['secret_key','publishable_key','webhook_secret','currency','mode'] ou false.
 */
function stripe_obter_credenciais(){
    global $_CONFIG;

    if(empty($_CONFIG['stripe']['secret_key'])){
        return false;
    }

    return Array(
        'secret_key' => $_CONFIG['stripe']['secret_key'],
        'publishable_key' => $_CONFIG['stripe']['publishable_key'] ?? '',
        'webhook_secret' => $_CONFIG['stripe']['webhook_secret'] ?? '',
        'currency' => $_CONFIG['stripe']['currency'] ?? 'BRL',
        'mode' => $_CONFIG['stripe']['mode'] ?? 'sandbox',
    );
}

/**
 * Requisição HTTP à API do Stripe.
 *
 * O Stripe usa `application/x-www-form-urlencoded` com colchetes para estruturas
 * aninhadas (`items[0][price]=...`) — `http_build_query` produz exatamente isso.
 *
 * @param array|false $params
 * @param string $params['endpoint'] Ex.: '/v1/payment_intents' (obrigatório)
 * @param string $params['method'] GET|POST|DELETE (padrão GET)
 * @param array  $params['data'] Corpo (POST) ou query string (GET)
 * @param string $params['idempotency_key'] Chave de idempotência (opcional)
 * @return array|false ['http_code' => int, 'data' => array] ou false
 */
function stripe_requisicao($params = false){
    if($params)foreach($params as $var => $val)$$var = $val;

    if(!isset($endpoint)){
        return false;
    }

    $credenciais = stripe_obter_credenciais();
    if(!$credenciais){
        stripe_log_registro(Array('tipo' => 'stripe-request-error', 'mensagem' => 'Credenciais Stripe não configuradas', 'detalhes' => Array('endpoint' => $endpoint)));
        return false;
    }

    $method = isset($method) ? strtoupper($method) : 'GET';
    $url = 'https://api.stripe.com' . $endpoint;

    $headers = Array(
        'Authorization: Bearer ' . $credenciais['secret_key'],
        'Stripe-Version: ' . STRIPE_API_VERSION,
    );
    if(isset($idempotency_key) && $idempotency_key !== ''){
        $headers[] = 'Idempotency-Key: ' . $idempotency_key;
    }

    $ch = curl_init();

    if($method === 'GET'){
        if(isset($data) && is_array($data) && !empty($data)){
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        }
    } else {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_POSTFIELDS, (isset($data) && is_array($data)) ? http_build_query($data) : '');
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if($response === false){
        stripe_log_registro(Array('tipo' => 'stripe-request-error', 'mensagem' => 'Erro cURL na requisição Stripe', 'detalhes' => Array('endpoint' => $endpoint, 'erro' => $curl_error)));
        return false;
    }

    $decoded = json_decode($response, true);

    if($http_code >= 400){
        stripe_log_registro(Array(
            'tipo' => 'stripe-api-error',
            'mensagem' => 'Erro na API do Stripe',
            'detalhes' => Array('endpoint' => $endpoint, 'method' => $method, 'http_code' => $http_code, 'error' => $decoded['error'] ?? null),
        ));
    }

    return Array('http_code' => $http_code, 'data' => is_array($decoded) ? $decoded : Array());
}

// ============================================================================
// VALORES E FORMATOS
// ============================================================================

/** Moedas sem casas decimais na API do Stripe (subset relevante). */
function stripe_moeda_sem_decimais($moeda){
    return in_array(strtoupper((string)$moeda), Array('JPY', 'KRW', 'VND', 'CLP', 'PYG'), true);
}

/** Converte valor decimal para a menor unidade da moeda (centavos em BRL). */
function stripe_valor_menor_unidade($valor, $moeda = 'BRL'){
    if(stripe_moeda_sem_decimais($moeda)) return (int) round((float)$valor);
    return (int) round(((float)$valor) * 100);
}

/** Converte da menor unidade para decimal. */
function stripe_valor_decimal($valor, $moeda = 'BRL'){
    if(stripe_moeda_sem_decimais($moeda)) return (float)$valor;
    return ((float)$valor) / 100;
}

// ============================================================================
// CLIENTES
// ============================================================================

/**
 * Localiza um cliente pelo e-mail ou cria um novo. Evita duplicar customers a cada checkout.
 *
 * @param array $params ['email' => string, 'nome' => string opcional, 'metadata' => array opcional]
 * @return array|false Dados do customer ou false.
 */
function stripe_obter_ou_criar_cliente($params = Array()){
    $email = trim((string)($params['email'] ?? ''));
    if($email === '') return false;

    $busca = stripe_requisicao(Array(
        'endpoint' => '/v1/customers/search',
        'method' => 'GET',
        'data' => Array('query' => "email:'" . str_replace("'", '', $email) . "'", 'limit' => 1),
    ));
    if($busca && $busca['http_code'] === 200 && !empty($busca['data']['data'][0]['id'])){
        return $busca['data']['data'][0];
    }

    $dados = Array('email' => $email);
    if(!empty($params['nome'])) $dados['name'] = $params['nome'];
    if(!empty($params['metadata']) && is_array($params['metadata'])) $dados['metadata'] = $params['metadata'];

    $criacao = stripe_requisicao(Array('endpoint' => '/v1/customers', 'method' => 'POST', 'data' => $dados));
    if(!$criacao || $criacao['http_code'] !== 200 || empty($criacao['data']['id'])) return false;

    return $criacao['data'];
}

// ============================================================================
// PAGAMENTO AVULSO (PaymentIntent)
// ============================================================================

/**
 * Cria um PaymentIntent para pagamento único no Payment Element.
 *
 * @param array $params ['valor' => decimal, 'moeda' => 'BRL', 'customer_id' => opc,
 *                       'descricao' => opc, 'referencia' => opc (metadata), 'idempotency_key' => opc]
 * @return array|false ['id','client_secret','status'] ou false.
 */
function stripe_criar_payment_intent($params = Array()){
    if(!isset($params['valor']) || (float)$params['valor'] <= 0) return false;

    $moeda = strtolower((string)($params['moeda'] ?? 'BRL'));
    $dados = Array(
        'amount' => stripe_valor_menor_unidade($params['valor'], $moeda),
        'currency' => $moeda,
        'automatic_payment_methods' => Array('enabled' => 'true'),
    );
    if(!empty($params['customer_id'])) $dados['customer'] = $params['customer_id'];
    if(!empty($params['descricao'])) $dados['description'] = $params['descricao'];
    if(!empty($params['referencia'])) $dados['metadata'] = Array('referencia' => $params['referencia']);

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/payment_intents',
        'method' => 'POST',
        'data' => $dados,
        'idempotency_key' => $params['idempotency_key'] ?? '',
    ));
    if(!$resp || $resp['http_code'] !== 200 || empty($resp['data']['client_secret'])) return false;

    return Array(
        'id' => $resp['data']['id'],
        'client_secret' => $resp['data']['client_secret'],
        'status' => $resp['data']['status'] ?? null,
    );
}

function stripe_consultar_payment_intent($params = Array()){
    if(empty($params['payment_intent_id'])) return false;
    $resp = stripe_requisicao(Array('endpoint' => '/v1/payment_intents/' . rawurlencode($params['payment_intent_id'])));
    if(!$resp || $resp['http_code'] !== 200) return false;
    return $resp['data'];
}

// ============================================================================
// ASSINATURAS (Billing)
// ============================================================================

/**
 * Cria uma assinatura em `default_incomplete` para confirmação no Payment Element.
 *
 * O retorno inclui o `client_secret` do PaymentIntent da primeira fatura (cobrança
 * imediata) ou do SetupIntent pendente (trial/valor zero), com o tipo correspondente.
 *
 * Com `trial_period_days`, a assinatura nasce em `trialing` e o Stripe cobra o método salvo
 * automaticamente no vencimento — nesse caso não há cobrança imediata e o `client_secret`
 * devolvido é o de um SetupIntent (`secret_type = 'setup'`), que apenas autoriza o cartão.
 *
 * @param array $params ['customer_id' => obrig, 'price_id' => obrig, 'referencia' => opc,
 *                       'metadata' => opc, 'idempotency_key' => opc, 'trial_period_days' => opc]
 * @return array|false ['id','status','client_secret','secret_type' => 'payment'|'setup','subscription_data'] ou false.
 */
function stripe_criar_assinatura($params = Array()){
    if(empty($params['customer_id']) || empty($params['price_id'])) return false;

    $dados = Array(
        'customer' => $params['customer_id'],
        'items' => Array(Array('price' => $params['price_id'])),
        'payment_behavior' => 'default_incomplete',
        'payment_settings' => Array('save_default_payment_method' => 'on_subscription'),
        'expand' => Array('latest_invoice.payment_intent', 'pending_setup_intent'),
    );

    // Período de teste: o trial não vem do Price, precisa ser declarado aqui.
    $trialDias = (int)($params['trial_period_days'] ?? 0);
    if($trialDias > 0){
        $dados['trial_period_days'] = $trialDias;
        // Sem método de pagamento no vencimento, pausar preserva o vínculo e o histórico da
        // assinatura (cancelar obrigaria o cliente a refazer o checkout do zero).
        $dados['trial_settings'] = Array('end_behavior' => Array('missing_payment_method' => 'pause'));
    }
    $metadata = Array();
    if(!empty($params['referencia'])) $metadata['referencia'] = $params['referencia'];
    if(!empty($params['metadata']) && is_array($params['metadata'])) $metadata = array_merge($metadata, $params['metadata']);
    if(!empty($metadata)) $dados['metadata'] = $metadata;

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/subscriptions',
        'method' => 'POST',
        'data' => $dados,
        'idempotency_key' => $params['idempotency_key'] ?? '',
    ));
    if(!$resp || $resp['http_code'] !== 200 || empty($resp['data']['id'])) return false;

    $sub = $resp['data'];
    $client_secret = null;
    $secret_type = null;

    if(!empty($sub['latest_invoice']['payment_intent']['client_secret'])){
        $client_secret = $sub['latest_invoice']['payment_intent']['client_secret'];
        $secret_type = 'payment';
    } elseif(!empty($sub['pending_setup_intent']['client_secret'])){
        $client_secret = $sub['pending_setup_intent']['client_secret'];
        $secret_type = 'setup';
    }

    return Array(
        'id' => $sub['id'],
        'status' => $sub['status'] ?? null,
        'client_secret' => $client_secret,
        'secret_type' => $secret_type,
        'subscription_data' => $sub,
    );
}

/**
 * Consulta uma assinatura.
 *
 * `expand` é o que decide se campos como `latest_invoice` voltam como objeto ou apenas como id
 * em string. Sem ele não há como ler o status do PaymentIntent da primeira fatura nem o valor
 * cobrado — quem precisa desses dados deve pedir o expand explicitamente.
 *
 * @param array $params ['subscription_id' => obrig, 'expand' => array opcional]
 * @return array|false
 */
function stripe_consultar_assinatura($params = Array()){
    if(empty($params['subscription_id'])) return false;

    $query = Array();
    if(!empty($params['expand']) && is_array($params['expand'])){
        $query['expand'] = array_values($params['expand']);
    }

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/subscriptions/' . rawurlencode($params['subscription_id']),
        'data' => $query,
    ));
    if(!$resp || $resp['http_code'] !== 200) return false;
    return $resp['data'];
}

/** Cancela definitivamente uma assinatura. */
function stripe_cancelar_assinatura($params = Array()){
    if(empty($params['subscription_id'])) return false;
    $dados = Array();
    if(!empty($params['motivo'])) $dados['cancellation_details'] = Array('comment' => mb_substr((string)$params['motivo'], 0, 500));
    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/subscriptions/' . rawurlencode($params['subscription_id']),
        'method' => 'DELETE',
        'data' => $dados,
    ));
    return ($resp && $resp['http_code'] === 200);
}

/**
 * Troca o preço de uma assinatura ativa (upgrade/downgrade) com proration nativa.
 *
 * @param array $params ['subscription_id' => obrig, 'price_id' => obrig,
 *                       'proration_behavior' => 'always_invoice'|'create_prorations'|'none' (padrão always_invoice)]
 * @return array|false Assinatura atualizada ou false.
 */
function stripe_trocar_preco_assinatura($params = Array()){
    if(empty($params['subscription_id']) || empty($params['price_id'])) return false;

    $sub = stripe_consultar_assinatura(Array('subscription_id' => $params['subscription_id']));
    if(!$sub) return false;

    $itemId = $sub['items']['data'][0]['id'] ?? null;
    if(!$itemId) return false;

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/subscriptions/' . rawurlencode($params['subscription_id']),
        'method' => 'POST',
        'data' => Array(
            'items' => Array(Array('id' => $itemId, 'price' => $params['price_id'])),
            'proration_behavior' => $params['proration_behavior'] ?? 'always_invoice',
            'payment_behavior' => 'allow_incomplete',
        ),
    ));
    if(!$resp || $resp['http_code'] !== 200) return false;
    return $resp['data'];
}

/** Suspende a cobrança (pause_collection) mantendo a assinatura viva. */
function stripe_suspender_assinatura($params = Array()){
    if(empty($params['subscription_id'])) return false;
    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/subscriptions/' . rawurlencode($params['subscription_id']),
        'method' => 'POST',
        'data' => Array('pause_collection' => Array('behavior' => 'void')),
    ));
    return ($resp && $resp['http_code'] === 200);
}

/**
 * Reativa uma assinatura pausada. Não ressuscita cancelada.
 *
 * São dois mecanismos distintos de pausa: `pause_collection` (status segue `active`, cobrança
 * suspensa — usado pela operação `suspend` do módulo) e o status `paused`, em que o Stripe coloca
 * a assinatura quando o teste termina sem método de pagamento. O primeiro se desfaz limpando o
 * campo; o segundo exige o endpoint dedicado `/resume`.
 */
function stripe_ativar_assinatura($params = Array()){
    if(empty($params['subscription_id'])) return false;

    $assinatura = stripe_consultar_assinatura(Array('subscription_id' => $params['subscription_id']));
    $status = is_array($assinatura) ? (string)($assinatura['status'] ?? '') : '';

    if($status === 'paused'){
        $resp = stripe_requisicao(Array(
            'endpoint' => '/v1/subscriptions/' . rawurlencode($params['subscription_id']) . '/resume',
            'method' => 'POST',
            'data' => Array('billing_cycle_anchor' => 'now'),
        ));
        return ($resp && $resp['http_code'] === 200);
    }

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/subscriptions/' . rawurlencode($params['subscription_id']),
        'method' => 'POST',
        'data' => Array('pause_collection' => ''),
    ));
    return ($resp && $resp['http_code'] === 200);
}

// ============================================================================
// REEMBOLSO
// ============================================================================

/**
 * Reembolsa um pagamento.
 *
 * @param array $params ['payment_intent_id' => ou 'charge_id', 'valor' => decimal opc (total se ausente), 'moeda' => opc]
 * @return array|false
 */
function stripe_reembolsar($params = Array()){
    $dados = Array();
    if(!empty($params['payment_intent_id'])) $dados['payment_intent'] = $params['payment_intent_id'];
    elseif(!empty($params['charge_id'])) $dados['charge'] = $params['charge_id'];
    else return false;

    if(isset($params['valor']) && (float)$params['valor'] > 0){
        $dados['amount'] = stripe_valor_menor_unidade($params['valor'], $params['moeda'] ?? 'BRL');
    }

    $resp = stripe_requisicao(Array('endpoint' => '/v1/refunds', 'method' => 'POST', 'data' => $dados));
    if(!$resp || $resp['http_code'] !== 200) return false;
    return $resp['data'];
}

// ============================================================================
// CATÁLOGO (Produtos e Preços)
// ============================================================================

/**
 * Cria um produto no catálogo (`POST /v1/products`).
 *
 * @param array $params ['nome' => obrig, 'descricao' => opc, 'imagens' => array de URLs opc,
 *                       'metadata' => array opc, 'ativo' => bool (padrão true), 'idempotency_key' => opc]
 * @return array|false Produto criado (`prod_...`) ou false.
 */
function stripe_criar_produto($params = Array()){
    $nome = trim((string)($params['nome'] ?? ''));
    if($nome === '') return false;

    $dados = Array('name' => $nome, 'active' => (isset($params['ativo']) && !$params['ativo']) ? 'false' : 'true');
    if(!empty($params['descricao'])) $dados['description'] = $params['descricao'];
    if(!empty($params['imagens']) && is_array($params['imagens'])) $dados['images'] = array_values(array_filter($params['imagens']));
    if(!empty($params['metadata']) && is_array($params['metadata'])) $dados['metadata'] = $params['metadata'];

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/products',
        'method' => 'POST',
        'data' => $dados,
        'idempotency_key' => $params['idempotency_key'] ?? '',
    ));
    if(!$resp || $resp['http_code'] !== 200 || empty($resp['data']['id'])) return false;
    return $resp['data'];
}

/**
 * Atualiza um produto existente (`POST /v1/products/{id}`).
 *
 * Só envia os campos presentes em `$params` — o Stripe faz merge parcial, então omitir
 * uma chave preserva o valor remoto. Para limpar a lista de imagens, envie `'imagens' => Array()`.
 *
 * @param array $params ['product_id' => obrig, 'nome' => opc, 'descricao' => opc,
 *                       'imagens' => array opc, 'metadata' => array opc, 'ativo' => bool opc]
 * @return array|false Produto atualizado ou false.
 */
function stripe_atualizar_produto($params = Array()){
    if(empty($params['product_id'])) return false;

    $dados = Array();
    if(isset($params['nome']) && trim((string)$params['nome']) !== '') $dados['name'] = $params['nome'];
    if(array_key_exists('descricao', $params)) $dados['description'] = (string)$params['descricao'];
    if(array_key_exists('imagens', $params) && is_array($params['imagens'])){
        $imagens = array_values(array_filter($params['imagens']));
        // Lista vazia precisa ir como `images[]=''` para o Stripe entender "remover todas".
        $dados['images'] = empty($imagens) ? Array('') : $imagens;
    }
    if(!empty($params['metadata']) && is_array($params['metadata'])) $dados['metadata'] = $params['metadata'];
    if(isset($params['ativo'])) $dados['active'] = $params['ativo'] ? 'true' : 'false';

    if(empty($dados)) return false;

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/products/' . rawurlencode($params['product_id']),
        'method' => 'POST',
        'data' => $dados,
    ));
    if(!$resp || $resp['http_code'] !== 200) return false;
    return $resp['data'];
}

function stripe_consultar_produto($params = Array()){
    if(empty($params['product_id'])) return false;
    $resp = stripe_requisicao(Array('endpoint' => '/v1/products/' . rawurlencode($params['product_id'])));
    if(!$resp || $resp['http_code'] !== 200) return false;
    return $resp['data'];
}

/**
 * Lista produtos da conta (`GET /v1/products`).
 *
 * @param array $params ['limite' => 1..100 (padrão 100), 'ativo' => bool opc, 'starting_after' => opc]
 * @return array|false ['data' => array de produtos, 'has_more' => bool] ou false.
 */
function stripe_listar_produtos($params = Array()){
    $query = Array('limit' => max(1, min(100, (int)($params['limite'] ?? 100))));
    if(isset($params['ativo'])) $query['active'] = $params['ativo'] ? 'true' : 'false';
    if(!empty($params['starting_after'])) $query['starting_after'] = $params['starting_after'];

    $resp = stripe_requisicao(Array('endpoint' => '/v1/products', 'data' => $query));
    if(!$resp || $resp['http_code'] !== 200) return false;

    return Array(
        'data' => $resp['data']['data'] ?? Array(),
        'has_more' => !empty($resp['data']['has_more']),
    );
}

/** Arquiva um produto (o Stripe não permite exclusão de produto com preços associados). */
function stripe_arquivar_produto($params = Array()){
    if(empty($params['product_id'])) return false;
    return (bool) stripe_atualizar_produto(Array('product_id' => $params['product_id'], 'ativo' => false));
}

/**
 * Cria um preço (`POST /v1/prices`).
 *
 * O valor de um preço é imutável na API do Stripe: mudar amount/moeda/recorrência exige criar
 * um preço novo e arquivar o anterior — não existe update de `unit_amount`.
 *
 * @param array $params ['product_id' => obrig, 'valor' => decimal obrig, 'moeda' => 'BRL',
 *                       'intervalo' => 'day'|'week'|'month'|'year' (ausente = pagamento avulso),
 *                       'intervalo_quantidade' => int (padrão 1 — trimestral = month/3),
 *                       'apelido' => opc, 'metadata' => opc, 'idempotency_key' => opc]
 * @return array|false Preço criado (`price_...`) ou false.
 */
function stripe_criar_preco($params = Array()){
    if(empty($params['product_id'])) return false;
    if(!isset($params['valor']) || (float)$params['valor'] < 0) return false;

    $moeda = strtolower((string)($params['moeda'] ?? 'BRL'));
    $dados = Array(
        'product' => $params['product_id'],
        'currency' => $moeda,
        'unit_amount' => stripe_valor_menor_unidade($params['valor'], $moeda),
    );

    $intervalo = (string)($params['intervalo'] ?? '');
    if($intervalo !== ''){
        $dados['recurring'] = Array(
            'interval' => $intervalo,
            'interval_count' => max(1, (int)($params['intervalo_quantidade'] ?? 1)),
        );
    }
    if(!empty($params['apelido'])) $dados['nickname'] = $params['apelido'];
    if(!empty($params['metadata']) && is_array($params['metadata'])) $dados['metadata'] = $params['metadata'];

    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/prices',
        'method' => 'POST',
        'data' => $dados,
        'idempotency_key' => $params['idempotency_key'] ?? '',
    ));
    if(!$resp || $resp['http_code'] !== 200 || empty($resp['data']['id'])) return false;
    return $resp['data'];
}

function stripe_consultar_preco($params = Array()){
    if(empty($params['price_id'])) return false;

    $query = Array();
    if(!empty($params['expand']) && is_array($params['expand'])) $query['expand'] = array_values($params['expand']);

    $resp = stripe_requisicao(Array('endpoint' => '/v1/prices/' . rawurlencode($params['price_id']), 'data' => $query));
    if(!$resp || $resp['http_code'] !== 200) return false;
    return $resp['data'];
}

/**
 * Lista preços da conta (`GET /v1/prices`).
 *
 * @param array $params ['product_id' => opc (filtra por produto), 'limite' => 1..100,
 *                       'ativo' => bool opc, 'starting_after' => opc, 'expand' => array opc]
 * @return array|false ['data' => array de preços, 'has_more' => bool] ou false.
 */
function stripe_listar_precos($params = Array()){
    $query = Array('limit' => max(1, min(100, (int)($params['limite'] ?? 100))));
    if(!empty($params['product_id'])) $query['product'] = $params['product_id'];
    if(isset($params['ativo'])) $query['active'] = $params['ativo'] ? 'true' : 'false';
    if(!empty($params['starting_after'])) $query['starting_after'] = $params['starting_after'];
    if(!empty($params['expand']) && is_array($params['expand'])) $query['expand'] = array_values($params['expand']);

    $resp = stripe_requisicao(Array('endpoint' => '/v1/prices', 'data' => $query));
    if(!$resp || $resp['http_code'] !== 200) return false;

    return Array(
        'data' => $resp['data']['data'] ?? Array(),
        'has_more' => !empty($resp['data']['has_more']),
    );
}

/** Arquiva um preço (`active=false`). Preço em uso por assinatura viva continua cobrando. */
function stripe_arquivar_preco($params = Array()){
    if(empty($params['price_id'])) return false;
    $resp = stripe_requisicao(Array(
        'endpoint' => '/v1/prices/' . rawurlencode($params['price_id']),
        'method' => 'POST',
        'data' => Array('active' => 'false'),
    ));
    return ($resp && $resp['http_code'] === 200);
}

// ============================================================================
// WEBHOOK
// ============================================================================

/**
 * Valida a assinatura de um webhook (`Stripe-Signature`) por HMAC-SHA256.
 *
 * @param array $params ['payload' => corpo cru, 'signature_header' => header, 'tolerancia' => seg (300)]
 * @return array|false Evento decodificado ou false.
 */
function stripe_validar_webhook($params = Array()){
    $credenciais = stripe_obter_credenciais();
    $secret = $credenciais ? $credenciais['webhook_secret'] : '';
    $payload = (string)($params['payload'] ?? '');
    $header = (string)($params['signature_header'] ?? '');
    $tolerancia = (int)($params['tolerancia'] ?? 300);

    if($secret === '' || $payload === '' || $header === '') return false;

    $timestamp = null;
    $assinaturas = Array();
    foreach(explode(',', $header) as $parte){
        $kv = explode('=', trim($parte), 2);
        if(count($kv) !== 2) continue;
        if($kv[0] === 't') $timestamp = (int)$kv[1];
        if($kv[0] === 'v1') $assinaturas[] = $kv[1];
    }

    if(!$timestamp || empty($assinaturas)) return false;
    if(abs(time() - $timestamp) > $tolerancia) return false;

    $esperada = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $valida = false;
    foreach($assinaturas as $assinatura){
        if(hash_equals($esperada, $assinatura)){ $valida = true; break; }
    }
    if(!$valida){
        stripe_log_registro(Array('tipo' => 'stripe-webhook-invalid-signature', 'mensagem' => 'Assinatura de webhook Stripe inválida'));
        return false;
    }

    $evento = json_decode($payload, true);
    return is_array($evento) ? $evento : false;
}

// ============================================================================
// AUXILIARES
// ============================================================================

/** Testa a conexão com as credenciais atuais (GET /v1/balance). */
function stripe_testar_conexao(){
    $resp = stripe_requisicao(Array('endpoint' => '/v1/balance'));
    return ($resp && $resp['http_code'] === 200);
}

function stripe_traduzir_status($status){
    $mapa = Array(
        'active' => 'Ativa',
        'trialing' => 'Em período de teste',
        'incomplete' => 'Incompleta (aguardando pagamento)',
        'incomplete_expired' => 'Expirada sem pagamento',
        'past_due' => 'Pagamento em atraso',
        'canceled' => 'Cancelada',
        'unpaid' => 'Não paga',
        'paused' => 'Pausada',
        'succeeded' => 'Concluído',
        'processing' => 'Processando',
        'requires_payment_method' => 'Aguardando método de pagamento',
        'requires_action' => 'Aguardando autenticação',
    );
    return $mapa[$status] ?? $status;
}

function stripe_info(){
    $credenciais = stripe_obter_credenciais();
    return Array(
        'biblioteca' => 'stripe',
        'api_version' => STRIPE_API_VERSION,
        'configurada' => ($credenciais !== false),
        'modo_gateway' => stripe_is_modo_gateway(),
        'mode' => $credenciais ? $credenciais['mode'] : null,
    );
}
