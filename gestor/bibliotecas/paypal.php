<?php
/**
 * Biblioteca PayPal REST API
 *
 * Implementa integração completa com PayPal REST API para processamento de pagamentos.
 * Suporta autenticação OAuth 2.0, criação de pedidos, capturas, reembolsos e webhooks.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-paypal'] = Array(
    'versao' => '1.0.0',
);

/**
 * Obtém a URL base da API do PayPal baseado no modo (sandbox/live).
 *
 * @global array $_CONFIG Configurações do sistema
 *
 * @return string URL base da API
 */
function paypal_obter_url_api(){
    global $_CONFIG;
    
    $mode = isset($_CONFIG['paypal']['mode']) ? $_CONFIG['paypal']['mode'] : 'sandbox';
    
    if($mode === 'live'){
        return 'https://api-m.paypal.com';
    }
    
    return 'https://api-m.sandbox.paypal.com';
}

/**
 * Obtém as credenciais do PayPal baseado no modo (sandbox/live).
 *
 * @global array $_CONFIG Configurações do sistema
 *
 * @return array|false Array com client_id e client_secret ou false se não configurado
 */
function paypal_obter_credenciais(){
    global $_CONFIG;
    
    $mode = isset($_CONFIG['paypal']['mode']) ? $_CONFIG['paypal']['mode'] : 'sandbox';
    
    if(!isset($_CONFIG['paypal'][$mode])){
        return false;
    }
    
    $credenciais = $_CONFIG['paypal'][$mode];
    
    if(!isset($credenciais['client_id']) || !isset($credenciais['client_secret'])){
        return false;
    }
    
    return Array(
        'client_id' => $credenciais['client_id'],
        'client_secret' => $credenciais['client_secret']
    );
}

/**
 * Realiza requisição HTTP para a API do PayPal.
 *
 * @param array|false $params Parâmetros da requisição
 * @param string $params['endpoint'] Endpoint da API (ex: '/v1/oauth2/token')
 * @param string $params['method'] Método HTTP (GET, POST, PATCH, etc) - padrão: GET
 * @param array $params['data'] Dados para enviar no body (opcional)
 * @param array $params['headers'] Headers customizados (opcional)
 * @param bool $params['auth_basic'] Usar Basic Auth com credenciais (opcional)
 * @param string $params['access_token'] Token OAuth para autenticação (opcional)
 *
 * @return array|false Array com resposta ou false em erro
 */
function paypal_requisicao($params = false){
    global $_GESTOR;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetro obrigatório
    if(!isset($endpoint)){
        return false;
    }
    
    // Método padrão
    if(!isset($method)){
        $method = 'GET';
    }
    
    // Construir URL completa
    $url = paypal_obter_url_api() . $endpoint;
    
    // Inicializar cURL
    $ch = curl_init();
    
    // Configurações básicas
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Headers padrão
    $default_headers = Array(
        'Content-Type: application/json',
        'Accept: application/json'
    );
    
    // Basic Auth com credenciais
    if(isset($auth_basic) && $auth_basic === true){
        $credenciais = paypal_obter_credenciais();
        if(!$credenciais){
            curl_close($ch);
            return false;
        }
        curl_setopt($ch, CURLOPT_USERPWD, $credenciais['client_id'] . ':' . $credenciais['client_secret']);
    }
    
    // OAuth Bearer Token
    if(isset($access_token)){
        $default_headers[] = 'Authorization: Bearer ' . $access_token;
    }
    
    // Merge headers customizados
    if(isset($headers) && is_array($headers)){
        $default_headers = array_merge($default_headers, $headers);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $default_headers);
    
    // Adicionar dados ao body
    if(isset($data)){
        if(is_array($data)){
            $json_data = json_encode($data);
        } else {
            $json_data = $data;
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    }
    
    // Executar requisição
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    // Verificar erros de cURL
    if($curl_error){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-curl-error',
                'mensagem' => $curl_error,
                'detalhes' => Array(
                    'url' => $url,
                    'method' => $method
                )
            ));
        }
        return false;
    }
    
    // Decodificar resposta JSON
    $response_data = json_decode($response, true);
    
    // Retornar resposta com código HTTP
    return Array(
        'http_code' => $http_code,
        'data' => $response_data,
        'raw' => $response
    );
}

/**
 * Autentica com PayPal e obtém access token OAuth 2.0.
 *
 * Gera um access token usando Client Credentials Grant.
 * O token é válido por cerca de 9 horas.
 *
 * @global array $_GESTOR Sistema global com configurações
 * @global array $_CONFIG Configurações do sistema
 *
 * @param array|false $params Parâmetros da função (opcional)
 * @param bool $params['force_refresh'] Forçar nova autenticação ignorando cache (opcional)
 *
 * @return array|false Array com token ou false em erro
 * @return string return['access_token'] Token de acesso OAuth 2.0
 * @return string return['token_type'] Tipo do token (Bearer)
 * @return int return['expires_in'] Tempo de expiração em segundos
 * @return int return['expires_at'] Timestamp de expiração
 */
function paypal_autenticar($params = false){
    global $_GESTOR;
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Verificar cache de token (se não forçar refresh)
    if(!isset($force_refresh) || $force_refresh !== true){
        if(isset($_GESTOR['paypal-token']) && isset($_GESTOR['paypal-token']['expires_at'])){
            // Verificar se token ainda é válido (com margem de 5 minutos)
            if($_GESTOR['paypal-token']['expires_at'] > (time() + 300)){
                return $_GESTOR['paypal-token'];
            }
        }
    }
    
    // Fazer requisição de autenticação
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/oauth2/token',
        'method' => 'POST',
        'auth_basic' => true,
        'headers' => Array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        'data' => 'grant_type=client_credentials'
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-auth-error',
                'mensagem' => 'Erro ao autenticar com PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    // Extrair dados do token
    $token_data = $response['data'];
    
    // Adicionar timestamp de expiração
    $token_data['expires_at'] = time() + $token_data['expires_in'];
    
    // Salvar no cache global
    $_GESTOR['paypal-token'] = $token_data;
    
    return $token_data;
}

/**
 * Cria um pedido (order) no PayPal.
 *
 * Cria um pedido de pagamento que pode ser aprovado pelo comprador.
 * Após aprovação, o pedido deve ser capturado para concluir o pagamento.
 *
 * @global array $_CONFIG Configurações do sistema
 *
 * @param array|false $params Parâmetros da função
 * @param float $params['valor'] Valor total do pedido (obrigatório)
 * @param string $params['moeda'] Código da moeda (opcional, padrão: BRL)
 * @param string $params['descricao'] Descrição do pedido (opcional)
 * @param array $params['itens'] Array de itens do pedido (opcional)
 * @param string $params['url_retorno'] URL de retorno após aprovação (opcional)
 * @param string $params['url_cancelamento'] URL de retorno após cancelamento (opcional)
 * @param string $params['referencia'] ID de referência customizado (opcional)
 *
 * @return array|false Array com dados do pedido ou false em erro
 * @return string return['id'] ID do pedido criado
 * @return string return['status'] Status do pedido
 * @return string return['approve_url'] URL para aprovação do pedido
 */
function paypal_criar_pedido($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($valor)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Moeda padrão
    if(!isset($moeda)){
        $moeda = isset($_CONFIG['paypal']['currency']) ? $_CONFIG['paypal']['currency'] : 'BRL';
    }
    
    // Construir estrutura do pedido
    $order_data = Array(
        'intent' => 'CAPTURE',
        'purchase_units' => Array(
            Array(
                'amount' => Array(
                    'currency_code' => $moeda,
                    'value' => number_format($valor, 2, '.', '')
                )
            )
        )
    );
    
    // Adicionar descrição
    if(isset($descricao)){
        $order_data['purchase_units'][0]['description'] = $descricao;
    }
    
    // Adicionar referência customizada
    if(isset($referencia)){
        $order_data['purchase_units'][0]['custom_id'] = $referencia;
    }
    
    // Adicionar itens detalhados
    if(isset($itens) && is_array($itens)){
        $items_array = Array();
        $items_total = 0;
        
        foreach($itens as $item){
            if(!isset($item['nome']) || !isset($item['quantidade']) || !isset($item['preco'])){
                continue;
            }
            
            $item_total = $item['quantidade'] * $item['preco'];
            $items_total += $item_total;
            
            $items_array[] = Array(
                'name' => $item['nome'],
                'quantity' => (string)$item['quantidade'],
                'unit_amount' => Array(
                    'currency_code' => $moeda,
                    'value' => number_format($item['preco'], 2, '.', '')
                )
            );
        }
        
        if(count($items_array) > 0){
            $order_data['purchase_units'][0]['items'] = $items_array;
            $order_data['purchase_units'][0]['amount']['breakdown'] = Array(
                'item_total' => Array(
                    'currency_code' => $moeda,
                    'value' => number_format($items_total, 2, '.', '')
                )
            );
        }
    }
    
    // Adicionar URLs de retorno
    if(isset($url_retorno) || isset($url_cancelamento)){
        $order_data['application_context'] = Array(
            'brand_name' => isset($_CONFIG['site']['nome']) ? $_CONFIG['site']['nome'] : 'Conn2Flow',
            'landing_page' => 'NO_PREFERENCE',
            'user_action' => 'PAY_NOW'
        );
        
        if(isset($url_retorno)){
            $order_data['application_context']['return_url'] = $url_retorno;
        }
        
        if(isset($url_cancelamento)){
            $order_data['application_context']['cancel_url'] = $url_cancelamento;
        }
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/checkout/orders',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $order_data
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-create-order-error',
                'mensagem' => 'Erro ao criar pedido no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    $order = $response['data'];
    
    // Extrair URL de aprovação
    $approve_url = null;
    if(isset($order['links'])){
        foreach($order['links'] as $link){
            if($link['rel'] === 'approve'){
                $approve_url = $link['href'];
                break;
            }
        }
    }
    
    // Retornar dados do pedido
    return Array(
        'id' => $order['id'],
        'status' => $order['status'],
        'approve_url' => $approve_url,
        'order_data' => $order
    );
}

/**
 * Captura um pedido (order) aprovado no PayPal.
 *
 * Após o comprador aprovar o pedido, esta função captura o pagamento.
 * O valor é transferido da conta do comprador para a conta do vendedor.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['order_id'] ID do pedido a ser capturado (obrigatório)
 *
 * @return array|false Array com dados da captura ou false em erro
 * @return string return['id'] ID da captura
 * @return string return['status'] Status da captura
 * @return array return['capture_data'] Dados completos da captura
 */
function paypal_capturar_pedido($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($order_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/checkout/orders/' . $order_id . '/capture',
        'method' => 'POST',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-capture-error',
                'mensagem' => 'Erro ao capturar pedido no PayPal',
                'detalhes' => Array(
                    'order_id' => $order_id,
                    'response' => $response
                )
            ));
        }
        return false;
    }
    
    $capture = $response['data'];
    
    // Extrair ID e status da captura
    $capture_id = null;
    $capture_status = $capture['status'];
    
    if(isset($capture['purchase_units'][0]['payments']['captures'][0])){
        $capture_id = $capture['purchase_units'][0]['payments']['captures'][0]['id'];
        $capture_status = $capture['purchase_units'][0]['payments']['captures'][0]['status'];
    }
    
    return Array(
        'id' => $capture_id,
        'status' => $capture_status,
        'order_id' => $order_id,
        'capture_data' => $capture
    );
}

/**
 * Consulta detalhes de um pedido no PayPal.
 *
 * Busca informações completas sobre um pedido específico.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['order_id'] ID do pedido a ser consultado (obrigatório)
 *
 * @return array|false Array com dados do pedido ou false em erro
 */
function paypal_consultar_pedido($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($order_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/checkout/orders/' . $order_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-get-order-error',
                'mensagem' => 'Erro ao consultar pedido no PayPal',
                'detalhes' => Array(
                    'order_id' => $order_id,
                    'response' => $response
                )
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Processa reembolso de uma captura no PayPal.
 *
 * Reembolsa total ou parcialmente um pagamento capturado.
 *
 * @global array $_CONFIG Configurações do sistema
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['capture_id'] ID da captura a ser reembolsada (obrigatório)
 * @param float $params['valor'] Valor do reembolso - se não informado, reembolsa total (opcional)
 * @param string $params['moeda'] Código da moeda (opcional, padrão: BRL)
 * @param string $params['nota'] Nota ou motivo do reembolso (opcional)
 *
 * @return array|false Array com dados do reembolso ou false em erro
 * @return string return['id'] ID do reembolso
 * @return string return['status'] Status do reembolso
 */
function paypal_reembolsar($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($capture_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados do reembolso
    $refund_data = Array();
    
    // Reembolso parcial
    if(isset($valor)){
        if(!isset($moeda)){
            $moeda = isset($_CONFIG['paypal']['currency']) ? $_CONFIG['paypal']['currency'] : 'BRL';
        }
        
        $refund_data['amount'] = Array(
            'currency_code' => $moeda,
            'value' => number_format($valor, 2, '.', '')
        );
    }
    
    // Adicionar nota
    if(isset($nota)){
        $refund_data['note_to_payer'] = $nota;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/payments/captures/' . $capture_id . '/refund',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $refund_data
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-refund-error',
                'mensagem' => 'Erro ao processar reembolso no PayPal',
                'detalhes' => Array(
                    'capture_id' => $capture_id,
                    'response' => $response
                )
            ));
        }
        return false;
    }
    
    $refund = $response['data'];
    
    return Array(
        'id' => $refund['id'],
        'status' => $refund['status'],
        'capture_id' => $capture_id,
        'refund_data' => $refund
    );
}

/**
 * Consulta detalhes de um reembolso no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['refund_id'] ID do reembolso a ser consultado (obrigatório)
 *
 * @return array|false Array com dados do reembolso ou false em erro
 */
function paypal_consultar_reembolso($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($refund_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/payments/refunds/' . $refund_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-get-refund-error',
                'mensagem' => 'Erro ao consultar reembolso no PayPal',
                'detalhes' => Array(
                    'refund_id' => $refund_id,
                    'response' => $response
                )
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Valida assinatura de webhook do PayPal.
 *
 * Verifica se um webhook recebido é autêntico usando validação de assinatura.
 * Requer Webhook ID configurado no $_CONFIG.
 *
 * @global array $_CONFIG Configurações do sistema
 *
 * @param array|false $params Parâmetros da função
 * @param array $params['headers'] Headers HTTP recebidos no webhook (obrigatório)
 * @param string $params['body'] Body JSON recebido no webhook (obrigatório)
 *
 * @return bool True se válido, false caso contrário
 */
function paypal_validar_webhook($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($headers) || !isset($body)){
        return false;
    }
    
    // Verificar se webhook_id está configurado
    if(!isset($_CONFIG['paypal']['webhook_id'])){
        if(function_exists('log_registro')){
            log_registro(Array(
                'tipo' => 'paypal-webhook-config-error',
                'mensagem' => 'Webhook ID não configurado'
            ));
        }
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Extrair headers necessários
    $transmission_id = isset($headers['PAYPAL-TRANSMISSION-ID']) ? $headers['PAYPAL-TRANSMISSION-ID'] : 
                       (isset($headers['Paypal-Transmission-Id']) ? $headers['Paypal-Transmission-Id'] : null);
    
    $transmission_time = isset($headers['PAYPAL-TRANSMISSION-TIME']) ? $headers['PAYPAL-TRANSMISSION-TIME'] :
                        (isset($headers['Paypal-Transmission-Time']) ? $headers['Paypal-Transmission-Time'] : null);
    
    $transmission_sig = isset($headers['PAYPAL-TRANSMISSION-SIG']) ? $headers['PAYPAL-TRANSMISSION-SIG'] :
                       (isset($headers['Paypal-Transmission-Sig']) ? $headers['Paypal-Transmission-Sig'] : null);
    
    $cert_url = isset($headers['PAYPAL-CERT-URL']) ? $headers['PAYPAL-CERT-URL'] :
               (isset($headers['Paypal-Cert-Url']) ? $headers['Paypal-Cert-Url'] : null);
    
    $auth_algo = isset($headers['PAYPAL-AUTH-ALGO']) ? $headers['PAYPAL-AUTH-ALGO'] :
                (isset($headers['Paypal-Auth-Algo']) ? $headers['Paypal-Auth-Algo'] : null);
    
    if(!$transmission_id || !$transmission_time || !$transmission_sig || !$cert_url || !$auth_algo){
        return false;
    }
    
    // Construir payload de validação
    $validation_data = Array(
        'transmission_id' => $transmission_id,
        'transmission_time' => $transmission_time,
        'transmission_sig' => $transmission_sig,
        'cert_url' => $cert_url,
        'auth_algo' => $auth_algo,
        'webhook_id' => $_CONFIG['paypal']['webhook_id'],
        'webhook_event' => json_decode($body, true)
    );
    
    // Fazer requisição de validação
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/notifications/verify-webhook-signature',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $validation_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        return false;
    }
    
    // Verificar resultado da validação
    $validation_result = $response['data'];
    
    if(isset($validation_result['verification_status']) && 
       $validation_result['verification_status'] === 'SUCCESS'){
        return true;
    }
    
    return false;
}

/**
 * Processa evento de webhook do PayPal.
 *
 * Interpreta e processa eventos recebidos via webhook.
 * Deve ser chamado após validação do webhook.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['body'] Body JSON do webhook (obrigatório)
 * @param callable $params['callback'] Função callback para processar evento (opcional)
 *
 * @return array|false Array com dados do evento processado ou false em erro
 * @return string return['event_type'] Tipo do evento
 * @return string return['resource_type'] Tipo do recurso
 * @return array return['event_data'] Dados completos do evento
 */
function paypal_processar_webhook($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($body)){
        return false;
    }
    
    // Decodificar JSON
    if(is_string($body)){
        $event_data = json_decode($body, true);
    } else {
        $event_data = $body;
    }
    
    if(!$event_data || !isset($event_data['event_type'])){
        return false;
    }
    
    // Extrair informações do evento
    $event_type = $event_data['event_type'];
    $resource_type = isset($event_data['resource_type']) ? $event_data['resource_type'] : null;
    $resource = isset($event_data['resource']) ? $event_data['resource'] : null;
    
    // Preparar resultado
    $result = Array(
        'event_type' => $event_type,
        'resource_type' => $resource_type,
        'resource' => $resource,
        'event_data' => $event_data
    );
    
    // Chamar callback se fornecido
    if(isset($callback) && is_callable($callback)){
        call_user_func($callback, $result);
    }
    
    // Log do evento
    if(function_exists('log_registro')){
        log_registro(Array(
            'tipo' => 'paypal-webhook-received',
            'mensagem' => 'Webhook PayPal processado: ' . $event_type,
            'detalhes' => Array(
                'event_type' => $event_type,
                'resource_type' => $resource_type
            )
        ));
    }
    
    return $result;
}
