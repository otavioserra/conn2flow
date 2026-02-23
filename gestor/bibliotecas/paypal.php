<?php
/**
 * Biblioteca PayPal REST API - Conn2Flow CMS
 *
 * Implementa integração completa com PayPal REST API para processamento de pagamentos.
 * Suporta autenticação OAuth 2.0, criação de pedidos, capturas, reembolsos, webhooks,
 * assinaturas, planos de cobrança, faturamento, pagamentos em lote e links de pagamento.
 *
 * APIs Suportadas:
 * - Orders API v2: Criação e captura de pedidos
 * - Payments API v2: Reembolsos e capturas
 * - Catalog Products API v1: Gerenciamento de produtos
 * - Subscriptions API v1: Planos e assinaturas recorrentes
 * - Invoicing API v2: Faturamento e notas fiscais
 * - Payouts API v1: Pagamentos em lote
 * - Webhooks API v1: Notificações de eventos
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 2.0.0
 * @author Conn2Flow Team
 * @link https://developer.paypal.com/docs/api/
 */

global $_GESTOR;

$_GESTOR['biblioteca-paypal'] = Array(
    'versao' => '3.0.0',
    'modo-gateway' => false,
    'gateway-id' => null,
    'gateway-dados' => null,
);

/**
 * Registra um log de evento relacionado ao PayPal.
 *
 * @return void 
 */
function paypal_log_registro($logData = []){
    gestor_incluir_biblioteca('log');

    log_disco(json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), 'paypal');
}

// ============================================================================
// GATEWAY DE PAGAMENTOS — CONFIGURAÇÃO VIA BANCO DE DADOS
// ============================================================================

/**
 * Configura a biblioteca PayPal para usar um gateway de pagamentos do banco.
 *
 * Busca o gateway na tabela gateways_pagamentos e configura $_CONFIG['paypal']
 * automaticamente. Também ativa o modo gateway para persistência de token no banco.
 *
 * @global array $_GESTOR Sistema global com configurações
 * @global array $_CONFIG Configurações do sistema
 *
 * @param array|false $params Parâmetros da função
 * @param int    $params['id']   ID específico do gateway (opcional)
 * @param string $params['tipo'] Tipo do gateway, ex: 'paypal' (opcional, padrão: 'paypal')
 *
 * @return bool True se configurado com sucesso, false caso contrário
 */
function paypal_gateways_pagamentos_configurar($params = false){
    global $_GESTOR;
    global $_CONFIG;

    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;

    // Tipo padrão
    if(!isset($tipo)){
        $tipo = 'paypal';
    }

    // Buscar gateway
    $gateway = null;

    if(isset($id)){
        // Busca por ID específico (alfanumérico)
        $gateway = banco_select(Array(
            'unico' => true,
            'tabela' => 'gateways_pagamentos',
            'campos' => Array('*'),
            'extra' => "WHERE id = '" . banco_escape_field($id) . "' AND status = 'A'"
        ));
    } else {
        // Busca gateway padrão do tipo
        $gateway = banco_select(Array(
            'unico' => true,
            'tabela' => 'gateways_pagamentos',
            'campos' => Array('*'),
            'extra' => "WHERE tipo = '" . banco_escape_field($tipo) . "' AND padrao = 'S' AND status = 'A'"
        ));

        // Fallback: primeiro gateway ativo do tipo (se não encontrou padrão)
        if(!$gateway){
            $gateway = banco_select(Array(
                'unico' => true,
                'tabela' => 'gateways_pagamentos',
                'campos' => Array('*'),
                'extra' => "WHERE tipo = '" . banco_escape_field($tipo) . "' AND status = 'A' ORDER BY id ASC LIMIT 1"
            ));
        }
    }

    if(!$gateway){
        paypal_log_registro(Array(
            'tipo' => 'paypal-gateway-config-error',
            'mensagem' => 'Gateway não encontrado no banco',
            'detalhes' => Array('id' => $id ?? null, 'tipo' => $tipo)
        ));
        return false;
    }

    // Configurar $_CONFIG com dados do gateway
    $paypal_mode = $gateway['ambiente'] === 'P' ? 'live' : 'sandbox';
    $_CONFIG['paypal'] = Array(
        'mode' => $paypal_mode,
        'currency' => $gateway['moeda'],
        'webhook_id' => $gateway['webhook_id'],
        $paypal_mode => Array(
            'client_id' => $gateway['client_id'],
            'client_secret' => $gateway['client_secret'],
        ),
    );

    // Ativar modo gateway na biblioteca
    $_GESTOR['biblioteca-paypal']['modo-gateway'] = true;
    $_GESTOR['biblioteca-paypal']['gateway-id'] = $gateway['id'];
    $_GESTOR['biblioteca-paypal']['gateway-dados'] = $gateway;

    // Restaurar token persistente do banco (se existir e válido)
    if(!empty($gateway['access_token']) && !empty($gateway['token_expires_at'])){
        $token_expires_at = (int) $gateway['token_expires_at'];

        // Verificar se token ainda é válido (com margem de 5 minutos)
        if($token_expires_at > (time() + 300)){
            $_GESTOR['paypal-token'] = Array(
                'access_token' => $gateway['access_token'],
                'token_type' => $gateway['token_type'] ?? 'Bearer',
                'expires_at' => $token_expires_at,
            );

            // Merge dados extras do token se existirem
            if(!empty($gateway['token_data'])){
                $token_extra = json_decode($gateway['token_data'], true);
                if(is_array($token_extra)){
                    $_GESTOR['paypal-token'] = array_merge($token_extra, $_GESTOR['paypal-token']);
                }
            }
        }
    }

    return true;
}

/**
 * Persiste o token OAuth no banco de dados do gateway ativo.
 *
 * Chamada internamente após autenticação bem-sucedida quando em modo gateway.
 *
 * @global array $_GESTOR Sistema global com configurações
 *
 * @param array $token_data Dados do token (access_token, token_type, expires_at, etc)
 *
 * @return bool True se persistido, false se não está em modo gateway
 */
function paypal_gateway_persistir_token($token_data){
    global $_GESTOR;

    if(!$_GESTOR['biblioteca-paypal']['modo-gateway'] || !$_GESTOR['biblioteca-paypal']['gateway-id']){
        return false;
    }

    $gateway_id = $_GESTOR['biblioteca-paypal']['gateway-id'];

    $campos_update = Array();
    $campos_update[] = "access_token = '" . banco_escape_field($token_data['access_token']) . "'";
    $campos_update[] = "token_type = '" . banco_escape_field($token_data['token_type'] ?? 'Bearer') . "'";
    $campos_update[] = "token_expires_at = '" . banco_escape_field($token_data['expires_at']) . "'";
    $campos_update[] = "token_data = '" . banco_escape_field(json_encode($token_data, JSON_UNESCAPED_UNICODE)) . "'";
    $campos_update[] = "data_modificacao = NOW()";

    banco_update(
        implode(', ', $campos_update),
        'gateways_pagamentos',
        "WHERE id = '" . banco_escape_field($gateway_id) . "' AND status != 'D'"
    );

    return true;
}

/**
 * Registra uma transação nas estatísticas do gateway ativo.
 *
 * Incrementa o contador de transações, soma o valor e atualiza a data da última transação.
 *
 * @global array $_GESTOR Sistema global com configurações
 *
 * @param array|false $params Parâmetros
 * @param float  $params['valor'] Valor da transação (opcional, padrão: 0)
 * @param string $params['moeda'] Moeda da transação (opcional)
 *
 * @return bool True se registrado, false se não está em modo gateway
 */
function paypal_gateway_registrar_transacao($params = false){
    global $_GESTOR;

    if(!$_GESTOR['biblioteca-paypal']['modo-gateway'] || !$_GESTOR['biblioteca-paypal']['gateway-id']){
        return false;
    }

    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;

    $gateway_id = $_GESTOR['biblioteca-paypal']['gateway-id'];
    $valor = isset($valor) ? (float) $valor : 0;

    banco_update(
        "total_transacoes = total_transacoes + 1, "
        . "total_valor = total_valor + " . number_format($valor, 2, '.', '') . ", "
        . "ultima_transacao = NOW(), "
        . "data_modificacao = NOW()",
        'gateways_pagamentos',
        "WHERE id = '" . banco_escape_field($gateway_id) . "' AND status != 'D'"
    );

    return true;
}

/**
 * Atualiza o status de conexão do gateway ativo.
 *
 * @global array $_GESTOR Sistema global com configurações
 *
 * @param bool   $sucesso  Se a conexão foi bem-sucedida
 * @param string $mensagem Mensagem descritiva do resultado
 *
 * @return bool True se atualizado, false se não está em modo gateway
 */
function paypal_gateway_atualizar_conexao($sucesso, $mensagem = ''){
    global $_GESTOR;

    if(!$_GESTOR['biblioteca-paypal']['modo-gateway'] || !$_GESTOR['biblioteca-paypal']['gateway-id']){
        return false;
    }

    $gateway_id = $_GESTOR['biblioteca-paypal']['gateway-id'];

    banco_update(
        "conexao_testada = '" . ($sucesso ? 'S' : 'N') . "', "
        . "conexao_data = NOW(), "
        . "conexao_mensagem = '" . banco_escape_field($mensagem) . "', "
        . "data_modificacao = NOW()",
        'gateways_pagamentos',
        "WHERE id = '" . banco_escape_field($gateway_id) . "' AND status != 'D'"
    );

    return true;
}

/**
 * Verifica se a biblioteca está operando em modo gateway (banco de dados).
 *
 * @global array $_GESTOR Sistema global com configurações
 *
 * @return bool True se está em modo gateway
 */
function paypal_is_modo_gateway(){
    global $_GESTOR;
    return isset($_GESTOR['biblioteca-paypal']['modo-gateway']) && $_GESTOR['biblioteca-paypal']['modo-gateway'] === true;
}

/**
 * Obtém o ID do gateway ativo.
 *
 * @global array $_GESTOR Sistema global com configurações
 *
 * @return string|null ID alfanumérico do gateway ou null se não está em modo gateway
 */
function paypal_obter_gateway_id(){
    global $_GESTOR;
    return $_GESTOR['biblioteca-paypal']['gateway-id'] ?? null;
}

/**
 * Obtém os dados completos do gateway ativo.
 *
 * @global array $_GESTOR Sistema global com configurações
 *
 * @return array|null Dados do gateway ou null se não está em modo gateway
 */
function paypal_obter_gateway_dados(){
    global $_GESTOR;
    return $_GESTOR['biblioteca-paypal']['gateway-dados'] ?? null;
}

// ============================================================================
// MODO PADRÃO — PERSISTÊNCIA DE TOKEN VIA VARIÁVEIS DO SISTEMA
// ============================================================================

/**
 * Persiste o token OAuth nas variáveis do sistema (modo padrão).
 *
 * Utiliza gestor_variaveis_alterar para salvar os dados do token
 * no módulo admin-environment, permitindo persistência entre requisições.
 *
 * @param array $token_data Dados do token (access_token, token_type, expires_at, etc)
 *
 * @return bool True se persistido com sucesso
 */
function paypal_padrao_persistir_token($token_data){
    gestor_variaveis_alterar(Array(
        'modulo' => 'admin-environment',
        'id' => 'paypal-access-token',
        'tipo' => 'string',
        'valor' => $token_data['access_token'] ?? ''
    ));

    gestor_variaveis_alterar(Array(
        'modulo' => 'admin-environment',
        'id' => 'paypal-token-type',
        'tipo' => 'string',
        'valor' => $token_data['token_type'] ?? 'Bearer'
    ));

    gestor_variaveis_alterar(Array(
        'modulo' => 'admin-environment',
        'id' => 'paypal-token-expires-at',
        'tipo' => 'string',
        'valor' => (string)($token_data['expires_at'] ?? '')
    ));

    gestor_variaveis_alterar(Array(
        'modulo' => 'admin-environment',
        'id' => 'paypal-token-data',
        'tipo' => 'string',
        'valor' => json_encode($token_data, JSON_UNESCAPED_UNICODE)
    ));

    return true;
}

/**
 * Restaura o token OAuth persistido nas variáveis do sistema (modo padrão).
 *
 * Carrega os dados do token salvo via gestor_variaveis e popula
 * o cache global $_GESTOR['paypal-token'] se o token ainda for válido.
 *
 * @global array $_GESTOR Sistema global com configurações
 *
 * @return bool True se token restaurado e válido, false caso contrário
 */
function paypal_padrao_restaurar_token(){
    global $_GESTOR;

    $access_token = gestor_variaveis(Array('modulo' => 'admin-environment', 'id' => 'paypal-access-token'));
    $token_expires_at = gestor_variaveis(Array('modulo' => 'admin-environment', 'id' => 'paypal-token-expires-at'));

    if(!empty($access_token) && !empty($token_expires_at)){
        $expires_at = (int) $token_expires_at;

        // Verificar se token ainda é válido (margem de 5 minutos)
        if($expires_at > (time() + 300)){
            $_GESTOR['paypal-token'] = Array(
                'access_token' => $access_token,
                'token_type' => gestor_variaveis(Array('modulo' => 'admin-environment', 'id' => 'paypal-token-type')) ?: 'Bearer',
                'expires_at' => $expires_at,
            );

            // Merge dados extras do token se existirem
            $token_data_str = gestor_variaveis(Array('modulo' => 'admin-environment', 'id' => 'paypal-token-data'));
            if(!empty($token_data_str)){
                $token_extra = json_decode($token_data_str, true);
                if(is_array($token_extra)){
                    $_GESTOR['paypal-token'] = array_merge($token_extra, $_GESTOR['paypal-token']);
                }
            }

            return true;
        }
    }

    return false;
}

// ============================================================================
// AUTO-CONFIGURAÇÃO — Ativa automaticamente o modo correto ao carregar
// ============================================================================

/**
 * Inicializa a biblioteca PayPal automaticamente baseado em $_CONFIG['paypal']['default'].
 *
 * Se 'gateway': configura via banco de dados (tabela gateways_pagamentos).
 * Se 'padrao': usa credenciais do .env via $_CONFIG e restaura token persistido.
 *
 * @global array $_GESTOR Sistema global com configurações
 * @global array $_CONFIG Configurações do sistema
 *
 * @return void
 */
function paypal_auto_configurar(){
    global $_GESTOR;
    global $_CONFIG;

    if(!isset($_CONFIG['paypal']['default'])) return;

    $modo_default = $_CONFIG['paypal']['default'];

    if($modo_default === 'gateway'){
        // Modo gateway — configurar via banco de dados
        paypal_gateways_pagamentos_configurar(Array('tipo' => 'paypal'));
    } else {
        // Modo padrão — restaurar token persistente das variáveis do sistema
        paypal_padrao_restaurar_token();
    }
}

// ============================================================================
// FUNÇÕES DE API — Core
// ============================================================================

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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-auth-error',
                'mensagem' => 'Erro ao autenticar com PayPal',
                'detalhes' => $response
            ));
        }

        // Atualizar status de conexão no gateway se em modo gateway
        if(paypal_is_modo_gateway()){
            paypal_gateway_atualizar_conexao(false, 'Erro na autenticação OAuth 2.0');
        }

        return false;
    }
    
    // Extrair dados do token
    $token_data = $response['data'];
    
    // Adicionar timestamp de expiração
    $token_data['expires_at'] = time() + $token_data['expires_in'];
    
    // Salvar no cache global
    $_GESTOR['paypal-token'] = $token_data;

    // Persistir token conforme o modo ativo
    if(paypal_is_modo_gateway()){
        paypal_gateway_persistir_token($token_data);
        paypal_gateway_atualizar_conexao(true, 'Autenticação bem-sucedida');
    } else {
        paypal_padrao_persistir_token($token_data);
    }
    
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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
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

    // Registrar estatística de transação no gateway
    if(paypal_is_modo_gateway() && $capture_status === 'COMPLETED'){
        $valor_captura = 0;
        if(isset($capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'])){
            $valor_captura = (float) $capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
        }
        paypal_gateway_registrar_transacao(Array('valor' => $valor_captura));
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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
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
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
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
    if(function_exists('paypal_log_registro')){
        paypal_log_registro(Array(
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

// ============================================================================
// CATALOG PRODUCTS API v1 - Gerenciamento de Produtos
// ============================================================================

/**
 * Cria um produto no catálogo do PayPal.
 *
 * Produtos são necessários para criar planos de assinatura.
 * Representa um bem ou serviço oferecido pelo comerciante.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['nome'] Nome do produto (obrigatório, 1-127 chars)
 * @param string $params['tipo'] Tipo: PHYSICAL, DIGITAL ou SERVICE (obrigatório)
 * @param string $params['descricao'] Descrição do produto (opcional, 1-256 chars)
 * @param string $params['categoria'] Categoria do produto (opcional, ex: SOFTWARE)
 * @param string $params['imagem_url'] URL da imagem do produto (opcional)
 * @param string $params['home_url'] URL da página do produto (opcional)
 * @param string $params['id'] ID customizado do produto (opcional, 6-50 chars)
 *
 * @return array|false Array com dados do produto ou false em erro
 */
function paypal_criar_produto($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($nome) || !isset($tipo)){
        return false;
    }
    
    // Validar tipo
    $tipos_validos = Array('PHYSICAL', 'DIGITAL', 'SERVICE');
    if(!in_array(strtoupper($tipo), $tipos_validos)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados do produto
    $product_data = Array(
        'name' => $nome,
        'type' => strtoupper($tipo)
    );
    
    // Campos opcionais
    if(isset($id)){
        $product_data['id'] = $id;
    }
    if(isset($descricao)){
        $product_data['description'] = $descricao;
    }
    if(isset($categoria)){
        $product_data['category'] = strtoupper($categoria);
    }
    if(isset($imagem_url)){
        $product_data['image_url'] = $imagem_url;
    }
    if(isset($home_url)){
        $product_data['home_url'] = $home_url;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/catalogs/products',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $product_data,
        'headers' => Array('Prefer: return=representation')
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-create-product-error',
                'mensagem' => 'Erro ao criar produto no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Lista produtos do catálogo do PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param int $params['pagina'] Número da página (opcional, padrão: 1)
 * @param int $params['limite'] Itens por página (opcional, padrão: 10, max: 20)
 * @param bool $params['total_requerido'] Incluir total de itens (opcional)
 *
 * @return array|false Array com lista de produtos ou false em erro
 */
function paypal_listar_produtos($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array();
    if(isset($pagina)){
        $query_params['page'] = (int)$pagina;
    }
    if(isset($limite)){
        $query_params['page_size'] = min((int)$limite, 20);
    }
    if(isset($total_requerido) && $total_requerido){
        $query_params['total_required'] = 'true';
    }
    
    $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/catalogs/products' . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-list-products-error',
                'mensagem' => 'Erro ao listar produtos no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Consulta detalhes de um produto no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['product_id'] ID do produto (obrigatório)
 *
 * @return array|false Array com dados do produto ou false em erro
 */
function paypal_consultar_produto($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($product_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/catalogs/products/' . $product_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-product-error',
                'mensagem' => 'Erro ao consultar produto no PayPal',
                'detalhes' => Array('product_id' => $product_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Atualiza um produto no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['product_id'] ID do produto (obrigatório)
 * @param string $params['descricao'] Nova descrição (opcional)
 * @param string $params['categoria'] Nova categoria (opcional)
 * @param string $params['imagem_url'] Nova URL da imagem (opcional)
 * @param string $params['home_url'] Nova URL da página (opcional)
 *
 * @return bool True se atualizado com sucesso, false em erro
 */
function paypal_atualizar_produto($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($product_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir operações de patch
    $patch_operations = Array();
    
    if(isset($descricao)){
        $patch_operations[] = Array(
            'op' => 'replace',
            'path' => '/description',
            'value' => $descricao
        );
    }
    if(isset($categoria)){
        $patch_operations[] = Array(
            'op' => 'replace',
            'path' => '/category',
            'value' => strtoupper($categoria)
        );
    }
    if(isset($imagem_url)){
        $patch_operations[] = Array(
            'op' => 'replace',
            'path' => '/image_url',
            'value' => $imagem_url
        );
    }
    if(isset($home_url)){
        $patch_operations[] = Array(
            'op' => 'replace',
            'path' => '/home_url',
            'value' => $home_url
        );
    }
    
    if(empty($patch_operations)){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/catalogs/products/' . $product_id,
        'method' => 'PATCH',
        'access_token' => $token['access_token'],
        'data' => $patch_operations
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-update-product-error',
                'mensagem' => 'Erro ao atualizar produto no PayPal',
                'detalhes' => Array('product_id' => $product_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

// ============================================================================
// SUBSCRIPTIONS API v1 - Planos de Cobrança
// ============================================================================

/**
 * Cria um plano de assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['product_id'] ID do produto associado (obrigatório)
 * @param string $params['nome'] Nome do plano (obrigatório, 1-127 chars)
 * @param array $params['ciclos'] Ciclos de cobrança (obrigatório)
 * @param string $params['descricao'] Descrição do plano (opcional)
 * @param string $params['status'] Status: ACTIVE ou INACTIVE (opcional, padrão: ACTIVE)
 * @param array $params['preferencias_pagamento'] Preferências de pagamento (opcional)
 * @param array $params['impostos'] Impostos aplicáveis (opcional)
 *
 * Estrutura de $params['ciclos'] (array de ciclos):
 * - tipo_tenure: REGULAR ou TRIAL
 * - frequencia_intervalo: Intervalo numérico (1, 2, 3...)
 * - frequencia_unidade: DAY, WEEK, MONTH ou YEAR
 * - total_ciclos: Total de ciclos (0 = infinito)
 * - preco: Valor do ciclo
 * - moeda: Código da moeda (BRL, USD, etc)
 *
 * @return array|false Array com dados do plano ou false em erro
 */
function paypal_criar_plano($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($product_id) || !isset($nome) || !isset($ciclos)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Moeda padrão
    $moeda_padrao = isset($_CONFIG['paypal']['currency']) ? $_CONFIG['paypal']['currency'] : 'BRL';
    
    // Construir ciclos de cobrança
    $billing_cycles = Array();
    $sequence = 1;
    
    foreach($ciclos as $ciclo){
        $billing_cycle = Array(
            'frequency' => Array(
                'interval_unit' => isset($ciclo['frequencia_unidade']) ? strtoupper($ciclo['frequencia_unidade']) : 'MONTH',
                'interval_count' => isset($ciclo['frequencia_intervalo']) ? (int)$ciclo['frequencia_intervalo'] : 1
            ),
            'tenure_type' => isset($ciclo['tipo_tenure']) ? strtoupper($ciclo['tipo_tenure']) : 'REGULAR',
            'sequence' => $sequence++,
            'total_cycles' => isset($ciclo['total_ciclos']) ? (int)$ciclo['total_ciclos'] : 0,
            'pricing_scheme' => Array(
                'fixed_price' => Array(
                    'value' => number_format(isset($ciclo['preco']) ? $ciclo['preco'] : 0, 2, '.', ''),
                    'currency_code' => isset($ciclo['moeda']) ? $ciclo['moeda'] : $moeda_padrao
                )
            )
        );
        
        $billing_cycles[] = $billing_cycle;
    }
    
    // Construir dados do plano
    $plan_data = Array(
        'product_id' => $product_id,
        'name' => $nome,
        'billing_cycles' => $billing_cycles,
        'payment_preferences' => Array(
            'auto_bill_outstanding' => true,
            'setup_fee_failure_action' => 'CONTINUE',
            'payment_failure_threshold' => 3
        )
    );
    
    // Campos opcionais
    if(isset($descricao)){
        $plan_data['description'] = $descricao;
    }
    if(isset($status)){
        $plan_data['status'] = strtoupper($status);
    }
    
    // Preferências de pagamento customizadas
    if(isset($preferencias_pagamento)){
        if(isset($preferencias_pagamento['taxa_setup'])){
            $plan_data['payment_preferences']['setup_fee'] = Array(
                'value' => number_format($preferencias_pagamento['taxa_setup'], 2, '.', ''),
                'currency_code' => isset($preferencias_pagamento['moeda']) ? $preferencias_pagamento['moeda'] : $moeda_padrao
            );
        }
        if(isset($preferencias_pagamento['auto_cobranca'])){
            $plan_data['payment_preferences']['auto_bill_outstanding'] = (bool)$preferencias_pagamento['auto_cobranca'];
        }
        if(isset($preferencias_pagamento['tentativas_falha'])){
            $plan_data['payment_preferences']['payment_failure_threshold'] = (int)$preferencias_pagamento['tentativas_falha'];
        }
    }
    
    // Impostos
    if(isset($impostos)){
        $plan_data['taxes'] = Array(
            'percentage' => number_format($impostos['percentual'], 2, '.', ''),
            'inclusive' => isset($impostos['inclusivo']) ? (bool)$impostos['inclusivo'] : false
        );
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/plans',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $plan_data,
        'headers' => Array('Prefer: return=representation')
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-create-plan-error',
                'mensagem' => 'Erro ao criar plano no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Lista planos de assinatura do PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['product_id'] Filtrar por produto (opcional)
 * @param int $params['pagina'] Número da página (opcional)
 * @param int $params['limite'] Itens por página (opcional, max: 20)
 * @param bool $params['total_requerido'] Incluir total (opcional)
 *
 * @return array|false Array com lista de planos ou false em erro
 */
function paypal_listar_planos($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array();
    if(isset($product_id)){
        $query_params['product_id'] = $product_id;
    }
    if(isset($pagina)){
        $query_params['page'] = (int)$pagina;
    }
    if(isset($limite)){
        $query_params['page_size'] = min((int)$limite, 20);
    }
    if(isset($total_requerido) && $total_requerido){
        $query_params['total_required'] = 'true';
    }
    
    $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/plans' . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-list-plans-error',
                'mensagem' => 'Erro ao listar planos no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Consulta detalhes de um plano no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['plan_id'] ID do plano (obrigatório)
 *
 * @return array|false Array com dados do plano ou false em erro
 */
function paypal_consultar_plano($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($plan_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/plans/' . $plan_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-plan-error',
                'mensagem' => 'Erro ao consultar plano no PayPal',
                'detalhes' => Array('plan_id' => $plan_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Ativa um plano de assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['plan_id'] ID do plano (obrigatório)
 *
 * @return bool True se ativado com sucesso, false em erro
 */
function paypal_ativar_plano($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($plan_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/plans/' . $plan_id . '/activate',
        'method' => 'POST',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-activate-plan-error',
                'mensagem' => 'Erro ao ativar plano no PayPal',
                'detalhes' => Array('plan_id' => $plan_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Desativa um plano de assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['plan_id'] ID do plano (obrigatório)
 *
 * @return bool True se desativado com sucesso, false em erro
 */
function paypal_desativar_plano($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($plan_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/plans/' . $plan_id . '/deactivate',
        'method' => 'POST',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-deactivate-plan-error',
                'mensagem' => 'Erro ao desativar plano no PayPal',
                'detalhes' => Array('plan_id' => $plan_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Atualiza os preços de um plano no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['plan_id'] ID do plano (obrigatório)
 * @param array $params['precos'] Array de esquemas de preços (obrigatório)
 *
 * Estrutura de $params['precos']:
 * - billing_cycle_sequence: Sequência do ciclo
 * - preco: Novo valor
 * - moeda: Código da moeda
 *
 * @return bool True se atualizado com sucesso, false em erro
 */
function paypal_atualizar_precos_plano($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($plan_id) || !isset($precos) || !is_array($precos)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Moeda padrão
    $moeda_padrao = isset($_CONFIG['paypal']['currency']) ? $_CONFIG['paypal']['currency'] : 'BRL';
    
    // Construir esquemas de preços
    $pricing_schemes = Array();
    foreach($precos as $preco){
        $pricing_schemes[] = Array(
            'billing_cycle_sequence' => isset($preco['billing_cycle_sequence']) ? (int)$preco['billing_cycle_sequence'] : 1,
            'pricing_scheme' => Array(
                'fixed_price' => Array(
                    'value' => number_format($preco['preco'], 2, '.', ''),
                    'currency_code' => isset($preco['moeda']) ? $preco['moeda'] : $moeda_padrao
                )
            )
        );
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/plans/' . $plan_id . '/update-pricing-schemes',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => Array('pricing_schemes' => $pricing_schemes)
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-update-plan-pricing-error',
                'mensagem' => 'Erro ao atualizar preços do plano no PayPal',
                'detalhes' => Array('plan_id' => $plan_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

// ============================================================================
// SUBSCRIPTIONS API v1 - Assinaturas
// ============================================================================

/**
 * Cria uma assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['plan_id'] ID do plano (obrigatório)
 * @param string $params['data_inicio'] Data de início (opcional, ISO 8601)
 * @param string $params['referencia'] ID de referência customizado (opcional)
 * @param array $params['assinante'] Dados do assinante (opcional)
 * @param array $params['application_context'] Contexto da aplicação (opcional)
 * @param string $params['url_retorno'] URL de retorno após aprovação (opcional)
 * @param string $params['url_cancelamento'] URL de retorno após cancelamento (opcional)
 *
 * @return array|false Array com dados da assinatura ou false em erro
 */
function paypal_criar_assinatura($params = false){
    global $_CONFIG;

    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($plan_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados da assinatura
    $subscription_data = Array(
        'plan_id' => $plan_id
    );
    
    // Data de início
    if(isset($data_inicio)){
        $subscription_data['start_time'] = $data_inicio;
    }
    
    // Referência customizada
    if(isset($referencia)){
        $subscription_data['custom_id'] = $referencia;
    }
    
    // Dados do assinante
    if(isset($assinante)){
        $subscriber = Array();
        if(isset($assinante['nome'])){
            $subscriber['name'] = Array(
                'given_name' => $assinante['nome'],
                'surname' => isset($assinante['sobrenome']) ? $assinante['sobrenome'] : ''
            );
        }
        if(isset($assinante['email'])){
            $subscriber['email_address'] = $assinante['email'];
        }
        if(!empty($subscriber)){
            $subscription_data['subscriber'] = $subscriber;
        }
    }
    
    // Contexto da aplicação
    $app_context = Array(
        'brand_name' => isset($_CONFIG['site']['nome']) ? $_CONFIG['site']['nome'] : 'Conn2Flow',
        'locale' => 'pt-BR',
        'user_action' => 'SUBSCRIBE_NOW',
        'payment_method' => Array(
            'payer_selected' => 'PAYPAL',
            'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
        )
    );
    
    if(isset($url_retorno)){
        $app_context['return_url'] = $url_retorno;
    }
    if(isset($url_cancelamento)){
        $app_context['cancel_url'] = $url_cancelamento;
    }
    
    $subscription_data['application_context'] = $app_context;
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/subscriptions',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $subscription_data,
        'headers' => Array('Prefer: return=representation')
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-create-subscription-error',
                'mensagem' => 'Erro ao criar assinatura no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    $subscription = $response['data'];
    
    // Extrair URL de aprovação
    $approve_url = null;
    if(isset($subscription['links'])){
        foreach($subscription['links'] as $link){
            if($link['rel'] === 'approve'){
                $approve_url = $link['href'];
                break;
            }
        }
    }
    
    return Array(
        'id' => $subscription['id'],
        'status' => $subscription['status'],
        'approve_url' => $approve_url,
        'subscription_data' => $subscription
    );
}

/**
 * Consulta detalhes de uma assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['subscription_id'] ID da assinatura (obrigatório)
 *
 * @return array|false Array com dados da assinatura ou false em erro
 */
function paypal_consultar_assinatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($subscription_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/subscriptions/' . $subscription_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-subscription-error',
                'mensagem' => 'Erro ao consultar assinatura no PayPal',
                'detalhes' => Array('subscription_id' => $subscription_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Suspende uma assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['subscription_id'] ID da assinatura (obrigatório)
 * @param string $params['motivo'] Motivo da suspensão (obrigatório)
 *
 * @return bool True se suspensa com sucesso, false em erro
 */
function paypal_suspender_assinatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($subscription_id) || !isset($motivo)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/subscriptions/' . $subscription_id . '/suspend',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => Array('reason' => $motivo)
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-suspend-subscription-error',
                'mensagem' => 'Erro ao suspender assinatura no PayPal',
                'detalhes' => Array('subscription_id' => $subscription_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Cancela uma assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['subscription_id'] ID da assinatura (obrigatório)
 * @param string $params['motivo'] Motivo do cancelamento (obrigatório)
 *
 * @return bool True se cancelada com sucesso, false em erro
 */
function paypal_cancelar_assinatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($subscription_id) || !isset($motivo)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/subscriptions/' . $subscription_id . '/cancel',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => Array('reason' => $motivo)
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-cancel-subscription-error',
                'mensagem' => 'Erro ao cancelar assinatura no PayPal',
                'detalhes' => Array('subscription_id' => $subscription_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Ativa uma assinatura suspensa no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['subscription_id'] ID da assinatura (obrigatório)
 * @param string $params['motivo'] Motivo da reativação (obrigatório)
 *
 * @return bool True se ativada com sucesso, false em erro
 */
function paypal_ativar_assinatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($subscription_id) || !isset($motivo)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/subscriptions/' . $subscription_id . '/activate',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => Array('reason' => $motivo)
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-activate-subscription-error',
                'mensagem' => 'Erro ao ativar assinatura no PayPal',
                'detalhes' => Array('subscription_id' => $subscription_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Captura pagamento autorizado de uma assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['subscription_id'] ID da assinatura (obrigatório)
 * @param float $params['valor'] Valor a capturar (obrigatório)
 * @param string $params['moeda'] Código da moeda (opcional)
 * @param string $params['nota'] Nota sobre a captura (obrigatório)
 *
 * @return array|false Array com dados da captura ou false em erro
 */
function paypal_capturar_assinatura($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($subscription_id) || !isset($valor) || !isset($nota)){
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
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/subscriptions/' . $subscription_id . '/capture',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => Array(
            'note' => $nota,
            'capture_type' => 'OUTSTANDING_BALANCE',
            'amount' => Array(
                'currency_code' => $moeda,
                'value' => number_format($valor, 2, '.', '')
            )
        )
    ));
    
    if(!$response || ($response['http_code'] !== 200 && $response['http_code'] !== 202)){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-capture-subscription-error',
                'mensagem' => 'Erro ao capturar pagamento da assinatura no PayPal',
                'detalhes' => Array('subscription_id' => $subscription_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Lista transações de uma assinatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['subscription_id'] ID da assinatura (obrigatório)
 * @param string $params['data_inicio'] Data de início (obrigatório, ISO 8601)
 * @param string $params['data_fim'] Data de fim (obrigatório, ISO 8601)
 *
 * @return array|false Array com lista de transações ou false em erro
 */
function paypal_listar_transacoes_assinatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($subscription_id) || !isset($data_inicio) || !isset($data_fim)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array(
        'start_time' => $data_inicio,
        'end_time' => $data_fim
    );
    $query_string = '?' . http_build_query($query_params);
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/billing/subscriptions/' . $subscription_id . '/transactions' . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-list-subscription-transactions-error',
                'mensagem' => 'Erro ao listar transações da assinatura no PayPal',
                'detalhes' => Array('subscription_id' => $subscription_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

// ============================================================================
// INVOICING API v2 - Faturamento
// ============================================================================

/**
 * Cria uma fatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param array $params['destinatario'] Dados do destinatário (obrigatório)
 * @param array $params['itens'] Itens da fatura (obrigatório)
 * @param string $params['moeda'] Código da moeda (opcional, padrão: BRL)
 * @param string $params['numero'] Número da fatura (opcional)
 * @param string $params['data_fatura'] Data da fatura (opcional, formato: YYYY-MM-DD)
 * @param string $params['data_vencimento'] Data de vencimento (opcional, formato: YYYY-MM-DD)
 * @param string $params['nota'] Nota para o pagador (opcional)
 * @param string $params['termos'] Termos e condições (opcional)
 * @param array $params['emissor'] Dados do emissor (opcional)
 * @param array $params['desconto'] Desconto da fatura (opcional)
 * @param array $params['envio'] Dados de envio/frete (opcional)
 *
 * Estrutura de $params['destinatario']:
 * - email: Email do destinatário
 * - nome: Nome do destinatário
 * - empresa: Nome da empresa (opcional)
 *
 * Estrutura de $params['itens'] (array de itens):
 * - nome: Nome do item
 * - descricao: Descrição do item (opcional)
 * - quantidade: Quantidade
 * - preco: Preço unitário
 * - imposto_percentual: Percentual de imposto (opcional)
 *
 * @return array|false Array com dados da fatura ou false em erro
 */
function paypal_criar_fatura($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($destinatario) || !isset($itens)){
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
    
    // Construir destinatário principal
    $primary_recipient = Array(
        'billing_info' => Array(
            'email_address' => $destinatario['email']
        )
    );
    
    if(isset($destinatario['nome'])){
        $primary_recipient['billing_info']['name'] = Array(
            'given_name' => $destinatario['nome'],
            'surname' => isset($destinatario['sobrenome']) ? $destinatario['sobrenome'] : ''
        );
    }
    if(isset($destinatario['empresa'])){
        $primary_recipient['billing_info']['business_name'] = $destinatario['empresa'];
    }
    
    // Construir itens da fatura
    $invoice_items = Array();
    foreach($itens as $item){
        $invoice_item = Array(
            'name' => $item['nome'],
            'quantity' => (string)(isset($item['quantidade']) ? $item['quantidade'] : 1),
            'unit_amount' => Array(
                'currency_code' => $moeda,
                'value' => number_format($item['preco'], 2, '.', '')
            )
        );
        
        if(isset($item['descricao'])){
            $invoice_item['description'] = $item['descricao'];
        }
        if(isset($item['imposto_percentual'])){
            $invoice_item['tax'] = Array(
                'name' => 'Imposto',
                'percent' => number_format($item['imposto_percentual'], 2, '.', '')
            );
        }
        
        $invoice_items[] = $invoice_item;
    }
    
    // Construir dados da fatura
    $invoice_data = Array(
        'detail' => Array(
            'currency_code' => $moeda
        ),
        'primary_recipients' => Array($primary_recipient),
        'items' => $invoice_items
    );
    
    // Número da fatura
    if(isset($numero)){
        $invoice_data['detail']['invoice_number'] = $numero;
    }
    
    // Datas
    if(isset($data_fatura)){
        $invoice_data['detail']['invoice_date'] = $data_fatura;
    }
    if(isset($data_vencimento)){
        $invoice_data['detail']['payment_term'] = Array(
            'due_date' => $data_vencimento
        );
    }
    
    // Nota
    if(isset($nota)){
        $invoice_data['detail']['note'] = $nota;
    }
    
    // Termos
    if(isset($termos)){
        $invoice_data['detail']['terms_and_conditions'] = $termos;
    }
    
    // Emissor
    if(isset($emissor)){
        $invoicer = Array();
        if(isset($emissor['nome'])){
            $invoicer['name'] = Array('business_name' => $emissor['nome']);
        }
        if(isset($emissor['email'])){
            $invoicer['email_address'] = $emissor['email'];
        }
        if(isset($emissor['website'])){
            $invoicer['website'] = $emissor['website'];
        }
        if(isset($emissor['logo_url'])){
            $invoicer['logo_url'] = $emissor['logo_url'];
        }
        if(!empty($invoicer)){
            $invoice_data['invoicer'] = $invoicer;
        }
    }
    
    // Desconto
    if(isset($desconto)){
        if(isset($desconto['percentual'])){
            $invoice_data['amount'] = Array(
                'breakdown' => Array(
                    'discount' => Array(
                        'percent' => number_format($desconto['percentual'], 2, '.', '')
                    )
                )
            );
        } elseif(isset($desconto['valor'])){
            $invoice_data['amount'] = Array(
                'breakdown' => Array(
                    'discount' => Array(
                        'invoice_discount' => Array(
                            'amount' => Array(
                                'currency_code' => $moeda,
                                'value' => number_format($desconto['valor'], 2, '.', '')
                            )
                        )
                    )
                )
            );
        }
    }
    
    // Envio/Frete
    if(isset($envio) && isset($envio['valor'])){
        if(!isset($invoice_data['amount'])){
            $invoice_data['amount'] = Array('breakdown' => Array());
        }
        $invoice_data['amount']['breakdown']['shipping'] = Array(
            'amount' => Array(
                'currency_code' => $moeda,
                'value' => number_format($envio['valor'], 2, '.', '')
            )
        );
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $invoice_data
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-create-invoice-error',
                'mensagem' => 'Erro ao criar fatura no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Lista faturas do PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param int $params['pagina'] Número da página (opcional)
 * @param int $params['limite'] Itens por página (opcional, max: 100)
 * @param bool $params['total_requerido'] Incluir total (opcional)
 *
 * @return array|false Array com lista de faturas ou false em erro
 */
function paypal_listar_faturas($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array();
    if(isset($pagina)){
        $query_params['page'] = (int)$pagina;
    }
    if(isset($limite)){
        $query_params['page_size'] = min((int)$limite, 100);
    }
    if(isset($total_requerido) && $total_requerido){
        $query_params['total_required'] = 'true';
    }
    
    $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices' . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-list-invoices-error',
                'mensagem' => 'Erro ao listar faturas no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Consulta detalhes de uma fatura no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 *
 * @return array|false Array com dados da fatura ou false em erro
 */
function paypal_consultar_fatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-invoice-error',
                'mensagem' => 'Erro ao consultar fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Envia uma fatura para o destinatário.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 * @param bool $params['enviar_para_emissor'] Enviar cópia para emissor (opcional)
 * @param bool $params['enviar_para_destinatario'] Enviar para destinatário (opcional, padrão: true)
 * @param string $params['nota'] Nota adicional (opcional)
 *
 * @return bool True se enviada com sucesso, false em erro
 */
function paypal_enviar_fatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados de envio
    $send_data = Array(
        'send_to_invoicer' => isset($enviar_para_emissor) ? (bool)$enviar_para_emissor : false,
        'send_to_recipient' => isset($enviar_para_destinatario) ? (bool)$enviar_para_destinatario : true
    );
    
    if(isset($nota)){
        $send_data['additional_note'] = $nota;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id . '/send',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $send_data
    ));
    
    if(!$response || ($response['http_code'] !== 200 && $response['http_code'] !== 202)){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-send-invoice-error',
                'mensagem' => 'Erro ao enviar fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Cancela uma fatura enviada no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 * @param string $params['assunto'] Assunto do email de cancelamento (opcional)
 * @param string $params['nota'] Nota sobre o cancelamento (opcional)
 * @param bool $params['notificar'] Notificar destinatário (opcional, padrão: true)
 *
 * @return bool True se cancelada com sucesso, false em erro
 */
function paypal_cancelar_fatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados de cancelamento
    $cancel_data = Array(
        'send_to_recipient' => isset($notificar) ? (bool)$notificar : true
    );
    
    if(isset($assunto)){
        $cancel_data['subject'] = $assunto;
    }
    if(isset($nota)){
        $cancel_data['note'] = $nota;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id . '/cancel',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $cancel_data
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-cancel-invoice-error',
                'mensagem' => 'Erro ao cancelar fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Envia lembrete de pagamento de uma fatura.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 * @param string $params['assunto'] Assunto do lembrete (opcional)
 * @param string $params['nota'] Nota do lembrete (opcional)
 *
 * @return bool True se enviado com sucesso, false em erro
 */
function paypal_lembrete_fatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados do lembrete
    $remind_data = Array();
    if(isset($assunto)){
        $remind_data['subject'] = $assunto;
    }
    if(isset($nota)){
        $remind_data['additional_note'] = $nota;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id . '/remind',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $remind_data
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-remind-invoice-error',
                'mensagem' => 'Erro ao enviar lembrete da fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Registra pagamento de uma fatura.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 * @param float $params['valor'] Valor do pagamento (obrigatório)
 * @param string $params['moeda'] Código da moeda (opcional)
 * @param string $params['metodo'] Método de pagamento (opcional): BANK_TRANSFER, CASH, CHECK, CREDIT_CARD, DEBIT_CARD, PAYPAL, WIRE_TRANSFER, OTHER
 * @param string $params['data'] Data do pagamento (opcional, formato: YYYY-MM-DD)
 * @param string $params['nota'] Nota sobre o pagamento (opcional)
 *
 * @return array|false Array com ID do pagamento ou false em erro
 */
function paypal_registrar_pagamento_fatura($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id) || !isset($valor)){
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
    
    // Construir dados do pagamento
    $payment_data = Array(
        'method' => isset($metodo) ? strtoupper($metodo) : 'OTHER',
        'amount' => Array(
            'currency_code' => $moeda,
            'value' => number_format($valor, 2, '.', '')
        )
    );
    
    if(isset($data)){
        $payment_data['payment_date'] = $data;
    }
    if(isset($nota)){
        $payment_data['note'] = $nota;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id . '/payments',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $payment_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-record-invoice-payment-error',
                'mensagem' => 'Erro ao registrar pagamento da fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Registra reembolso de uma fatura.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 * @param float $params['valor'] Valor do reembolso (obrigatório)
 * @param string $params['moeda'] Código da moeda (opcional)
 * @param string $params['metodo'] Método do reembolso (opcional)
 * @param string $params['data'] Data do reembolso (opcional, formato: YYYY-MM-DD)
 *
 * @return array|false Array com ID do reembolso ou false em erro
 */
function paypal_registrar_reembolso_fatura($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id) || !isset($valor)){
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
    
    // Construir dados do reembolso
    $refund_data = Array(
        'method' => isset($metodo) ? strtoupper($metodo) : 'OTHER',
        'amount' => Array(
            'currency_code' => $moeda,
            'value' => number_format($valor, 2, '.', '')
        )
    );
    
    if(isset($data)){
        $refund_data['refund_date'] = $data;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id . '/refunds',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $refund_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-record-invoice-refund-error',
                'mensagem' => 'Erro ao registrar reembolso da fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Gera QR Code para pagamento de uma fatura.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 * @param int $params['largura'] Largura em pixels (opcional, 150-500)
 * @param int $params['altura'] Altura em pixels (opcional, 150-500)
 *
 * @return string|false Base64 da imagem do QR Code ou false em erro
 */
function paypal_gerar_qrcode_fatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Dimensões do QR Code
    $qr_data = Array(
        'width' => isset($largura) ? max(150, min((int)$largura, 500)) : 300,
        'height' => isset($altura) ? max(150, min((int)$altura, 500)) : 300
    );
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id . '/generate-qr-code',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $qr_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-generate-invoice-qrcode-error',
                'mensagem' => 'Erro ao gerar QR Code da fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    // Retorna a imagem em base64
    return isset($response['raw']) ? $response['raw'] : $response['data'];
}

/**
 * Busca faturas no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['email_destinatario'] Email do destinatário (opcional)
 * @param string $params['email_emissor'] Email do emissor (opcional)
 * @param string $params['status'] Status da fatura (opcional): DRAFT, SENT, SCHEDULED, PAID, MARKED_AS_PAID, CANCELLED, REFUNDED
 * @param string $params['data_inicio'] Data de início (opcional, formato: YYYY-MM-DD)
 * @param string $params['data_fim'] Data de fim (opcional, formato: YYYY-MM-DD)
 * @param int $params['pagina'] Número da página (opcional)
 * @param int $params['limite'] Itens por página (opcional, max: 100)
 *
 * @return array|false Array com lista de faturas ou false em erro
 */
function paypal_buscar_faturas($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir critérios de busca
    $search_data = Array();
    
    if(isset($email_destinatario)){
        $search_data['recipient_email'] = $email_destinatario;
    }
    if(isset($email_emissor)){
        $search_data['invoicer_email'] = $email_emissor;
    }
    if(isset($status)){
        $search_data['status'] = Array(strtoupper($status));
    }
    if(isset($data_inicio)){
        $search_data['invoice_date_range'] = Array(
            'start' => $data_inicio
        );
        if(isset($data_fim)){
            $search_data['invoice_date_range']['end'] = $data_fim;
        }
    }
    
    // Paginação
    if(isset($pagina)){
        $search_data['page'] = (int)$pagina;
    }
    if(isset($limite)){
        $search_data['page_size'] = min((int)$limite, 100);
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/search-invoices',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $search_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-search-invoices-error',
                'mensagem' => 'Erro ao buscar faturas no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Deleta uma fatura (apenas rascunhos).
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['invoice_id'] ID da fatura (obrigatório)
 *
 * @return bool True se deletada com sucesso, false em erro
 */
function paypal_deletar_fatura($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($invoice_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/invoices/' . $invoice_id,
        'method' => 'DELETE',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 204){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-delete-invoice-error',
                'mensagem' => 'Erro ao deletar fatura no PayPal',
                'detalhes' => Array('invoice_id' => $invoice_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return true;
}

/**
 * Gera próximo número de fatura disponível.
 *
 * @return string|false Próximo número de fatura ou false em erro
 */
function paypal_gerar_numero_fatura(){
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v2/invoicing/generate-next-invoice-number',
        'method' => 'POST',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-generate-invoice-number-error',
                'mensagem' => 'Erro ao gerar número de fatura no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return isset($response['data']['invoice_number']) ? $response['data']['invoice_number'] : false;
}

// ============================================================================
// PAYOUTS API v1 - Pagamentos em Lote
// ============================================================================

/**
 * Cria um payout (pagamento em lote) no PayPal.
 *
 * Permite enviar pagamentos para múltiplos destinatários em uma única chamada.
 * Máximo de 15.000 itens por lote.
 *
 * @param array|false $params Parâmetros da função
 * @param array $params['itens'] Array de itens de pagamento (obrigatório)
 * @param string $params['assunto_email'] Assunto do email (opcional)
 * @param string $params['mensagem_email'] Mensagem do email (opcional)
 * @param string $params['sender_batch_id'] ID do lote (opcional, gerado se não informado)
 *
 * Estrutura de $params['itens'] (array de itens):
 * - destinatario: Email, telefone ou PayPal ID do destinatário
 * - tipo_destinatario: EMAIL, PHONE ou PAYPAL_ID (padrão: EMAIL)
 * - valor: Valor do pagamento
 * - moeda: Código da moeda (opcional)
 * - nota: Nota para o destinatário (opcional)
 * - sender_item_id: ID único do item (opcional)
 * - proposito: AWARDS, PRIZES, DONATIONS, GOODS, SERVICES, REBATES, CASHBACK, DISCOUNTS, NON_GOODS_OR_SERVICES (opcional)
 *
 * @return array|false Array com dados do payout ou false em erro
 */
function paypal_criar_payout($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($itens) || !is_array($itens) || empty($itens)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Moeda padrão
    $moeda_padrao = isset($_CONFIG['paypal']['currency']) ? $_CONFIG['paypal']['currency'] : 'BRL';
    
    // Construir itens do payout
    $payout_items = Array();
    $item_counter = 1;
    
    foreach($itens as $item){
        if(!isset($item['destinatario']) || !isset($item['valor'])){
            continue;
        }
        
        $payout_item = Array(
            'recipient_type' => isset($item['tipo_destinatario']) ? strtoupper($item['tipo_destinatario']) : 'EMAIL',
            'receiver' => $item['destinatario'],
            'amount' => Array(
                'value' => number_format($item['valor'], 2, '.', ''),
                'currency' => isset($item['moeda']) ? $item['moeda'] : $moeda_padrao
            )
        );
        
        if(isset($item['nota'])){
            $payout_item['note'] = $item['nota'];
        }
        if(isset($item['sender_item_id'])){
            $payout_item['sender_item_id'] = $item['sender_item_id'];
        } else {
            $payout_item['sender_item_id'] = 'item_' . $item_counter++;
        }
        if(isset($item['proposito'])){
            $payout_item['purpose'] = strtoupper($item['proposito']);
        }
        
        $payout_items[] = $payout_item;
    }
    
    if(empty($payout_items)){
        return false;
    }
    
    // Construir header do lote
    $sender_batch_header = Array(
        'sender_batch_id' => isset($sender_batch_id) ? $sender_batch_id : 'Payout_' . time() . '_' . mt_rand(1000, 9999)
    );
    
    if(isset($assunto_email)){
        $sender_batch_header['email_subject'] = $assunto_email;
    }
    if(isset($mensagem_email)){
        $sender_batch_header['email_message'] = $mensagem_email;
    }
    
    // Construir dados do payout
    $payout_data = Array(
        'sender_batch_header' => $sender_batch_header,
        'items' => $payout_items
    );
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/payments/payouts',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $payout_data
    ));
    
    if(!$response || $response['http_code'] !== 201){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-create-payout-error',
                'mensagem' => 'Erro ao criar payout no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Consulta detalhes de um payout no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['payout_batch_id'] ID do lote de payout (obrigatório)
 * @param int $params['pagina'] Número da página (opcional)
 * @param int $params['limite'] Itens por página (opcional)
 * @param bool $params['total_requerido'] Incluir total (opcional)
 *
 * @return array|false Array com dados do payout ou false em erro
 */
function paypal_consultar_payout($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($payout_batch_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array();
    if(isset($pagina)){
        $query_params['page'] = (int)$pagina;
    }
    if(isset($limite)){
        $query_params['page_size'] = (int)$limite;
    }
    if(isset($total_requerido) && $total_requerido){
        $query_params['total_required'] = 'true';
    }
    
    $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/payments/payouts/' . $payout_batch_id . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-payout-error',
                'mensagem' => 'Erro ao consultar payout no PayPal',
                'detalhes' => Array('payout_batch_id' => $payout_batch_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Consulta detalhes de um item de payout no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['payout_item_id'] ID do item de payout (obrigatório)
 *
 * @return array|false Array com dados do item ou false em erro
 */
function paypal_consultar_item_payout($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($payout_item_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/payments/payouts-item/' . $payout_item_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-payout-item-error',
                'mensagem' => 'Erro ao consultar item de payout no PayPal',
                'detalhes' => Array('payout_item_id' => $payout_item_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Cancela um item de payout não reclamado no PayPal.
 *
 * Apenas itens com status UNCLAIMED podem ser cancelados.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['payout_item_id'] ID do item de payout (obrigatório)
 *
 * @return array|false Array com dados do item cancelado ou false em erro
 */
function paypal_cancelar_item_payout($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($payout_item_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/payments/payouts-item/' . $payout_item_id . '/cancel',
        'method' => 'POST',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-cancel-payout-item-error',
                'mensagem' => 'Erro ao cancelar item de payout no PayPal',
                'detalhes' => Array('payout_item_id' => $payout_item_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

// ============================================================================
// PAYMENT LINKS - Links de Pagamento
// ============================================================================

/**
 * Gera um link de pagamento do PayPal.
 *
 * Cria um pedido e retorna a URL de aprovação para compartilhamento.
 * Ideal para pagamentos por link (sem integração de checkout).
 *
 * @param array|false $params Parâmetros da função
 * @param float $params['valor'] Valor do pagamento (obrigatório)
 * @param string $params['moeda'] Código da moeda (opcional, padrão: BRL)
 * @param string $params['descricao'] Descrição do pagamento (opcional)
 * @param array $params['itens'] Itens do pagamento (opcional)
 * @param string $params['referencia'] Referência customizada (opcional)
 * @param string $params['url_retorno'] URL de retorno após aprovação (opcional)
 * @param string $params['url_cancelamento'] URL de retorno após cancelamento (opcional)
 *
 * @return array|false Array com link e dados do pedido ou false em erro
 */
function paypal_gerar_link_pagamento($params = false){
    // Usar função de criação de pedido
    $pedido = paypal_criar_pedido($params);
    
    if(!$pedido){
        return false;
    }
    
    // Retornar apenas informações relevantes para link de pagamento
    return Array(
        'link' => $pedido['approve_url'],
        'order_id' => $pedido['id'],
        'status' => $pedido['status'],
        'order_data' => $pedido['order_data']
    );
}

/**
 * Verifica status de um link de pagamento.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['order_id'] ID do pedido (obrigatório)
 *
 * @return array|false Array com status do pedido ou false em erro
 */
function paypal_verificar_link_pagamento($params = false){
    // Usar função de consulta de pedido
    $pedido = paypal_consultar_pedido($params);
    
    if(!$pedido){
        return false;
    }
    
    // Determinar se foi pago
    $pago = in_array($pedido['status'], Array('COMPLETED', 'APPROVED'));
    
    return Array(
        'order_id' => $pedido['id'],
        'status' => $pedido['status'],
        'pago' => $pago,
        'order_data' => $pedido
    );
}

// ============================================================================
// DISPUTES API v1 - Disputas e Contestações
// ============================================================================

/**
 * Lista disputas no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['status'] Status da disputa (opcional): OPEN, WAITING_FOR_SELLER_RESPONSE, WAITING_FOR_BUYER_RESPONSE, UNDER_REVIEW, RESOLVED, EXPIRED
 * @param string $params['motivo'] Motivo da disputa (opcional): MERCHANDISE_OR_SERVICE_NOT_RECEIVED, MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED, UNAUTHORISED, CREDIT_NOT_PROCESSED, DUPLICATE_TRANSACTION, INCORRECT_AMOUNT, PAYMENT_BY_OTHER_MEANS, CANCELED_RECURRING_BILLING, PROBLEM_WITH_REMITTANCE
 * @param string $params['data_inicio'] Data de início (opcional, formato: YYYY-MM-DDTHH:MM:SS.SSSZ)
 * @param string $params['data_fim'] Data de fim (opcional, formato: YYYY-MM-DDTHH:MM:SS.SSSZ)
 * @param int $params['pagina'] Número da página (opcional)
 * @param int $params['limite'] Itens por página (opcional, 1-50)
 *
 * @return array|false Array com lista de disputas ou false em erro
 */
function paypal_listar_disputas($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array();
    
    if(isset($status)){
        $query_params['dispute_state'] = strtoupper($status);
    }
    if(isset($motivo)){
        $query_params['dispute_reason'] = strtoupper($motivo);
    }
    if(isset($data_inicio)){
        $query_params['start_time'] = $data_inicio;
    }
    if(isset($data_fim)){
        $query_params['disputed_transaction_id'] = $data_fim;
    }
    if(isset($pagina)){
        $query_params['page'] = (int)$pagina;
    }
    if(isset($limite)){
        $query_params['page_size'] = min((int)$limite, 50);
    }
    
    $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/customer/disputes' . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-list-disputes-error',
                'mensagem' => 'Erro ao listar disputas no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Consulta detalhes de uma disputa no PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['dispute_id'] ID da disputa (obrigatório)
 *
 * @return array|false Array com dados da disputa ou false em erro
 */
function paypal_consultar_disputa($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($dispute_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/customer/disputes/' . $dispute_id,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-dispute-error',
                'mensagem' => 'Erro ao consultar disputa no PayPal',
                'detalhes' => Array('dispute_id' => $dispute_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Aceita reclamação e faz reembolso ao comprador.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['dispute_id'] ID da disputa (obrigatório)
 * @param string $params['nota'] Nota explicativa (opcional)
 * @param float $params['valor_reembolso'] Valor do reembolso (opcional, padrão: valor total)
 * @param string $params['moeda'] Código da moeda (opcional)
 *
 * @return array|false Array com resultado ou false em erro
 */
function paypal_aceitar_disputa($params = false){
    global $_CONFIG;
    
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($dispute_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados
    $accept_data = Array(
        'accept_claim_type' => 'REFUND'
    );
    
    if(isset($nota)){
        $accept_data['note'] = $nota;
    }
    
    if(isset($valor_reembolso)){
        $moeda_padrao = isset($moeda) ? $moeda : (isset($_CONFIG['paypal']['currency']) ? $_CONFIG['paypal']['currency'] : 'BRL');
        $accept_data['refund_amount'] = Array(
            'currency_code' => $moeda_padrao,
            'value' => number_format($valor_reembolso, 2, '.', '')
        );
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/customer/disputes/' . $dispute_id . '/accept-claim',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $accept_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-accept-dispute-error',
                'mensagem' => 'Erro ao aceitar disputa no PayPal',
                'detalhes' => Array('dispute_id' => $dispute_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Contesta uma disputa fornecendo evidências.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['dispute_id'] ID da disputa (obrigatório)
 * @param string $params['mensagem'] Mensagem de contestação (obrigatório)
 * @param array $params['evidencias'] Array de evidências (opcional)
 *
 * Estrutura de $params['evidencias'] (array de evidências):
 * - tipo: PROOF_OF_FULFILLMENT, PROOF_OF_REFUND, PROOF_OF_DELIVERY, PROOF_OF_RETURN, PROOF_OF_TRACKING, PROOF_OF_PURCHASE
 * - notas: Notas explicativas
 * - documentos: Array de documentos (nome, conteudo base64)
 *
 * @return array|false Array com resultado ou false em erro
 */
function paypal_contestar_disputa($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($dispute_id) || !isset($mensagem)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados
    $provide_evidence_data = Array(
        'note' => $mensagem
    );
    
    if(isset($evidencias) && is_array($evidencias)){
        $evidence_documents = Array();
        foreach($evidencias as $evidencia){
            $evidence_doc = Array(
                'evidence_type' => isset($evidencia['tipo']) ? strtoupper($evidencia['tipo']) : 'OTHER'
            );
            if(isset($evidencia['notas'])){
                $evidence_doc['notes'] = $evidencia['notas'];
            }
            if(isset($evidencia['documentos']) && is_array($evidencia['documentos'])){
                $evidence_doc['documents'] = Array();
                foreach($evidencia['documentos'] as $doc){
                    $evidence_doc['documents'][] = Array(
                        'name' => $doc['nome'],
                        'content' => $doc['conteudo']
                    );
                }
            }
            $evidence_documents[] = $evidence_doc;
        }
        $provide_evidence_data['evidence_documents'] = $evidence_documents;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/customer/disputes/' . $dispute_id . '/provide-evidence',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $provide_evidence_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-provide-evidence-error',
                'mensagem' => 'Erro ao fornecer evidências para disputa no PayPal',
                'detalhes' => Array('dispute_id' => $dispute_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Envia mensagem para uma disputa.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['dispute_id'] ID da disputa (obrigatório)
 * @param string $params['mensagem'] Mensagem para o comprador (obrigatório)
 *
 * @return array|false Array com resultado ou false em erro
 */
function paypal_mensagem_disputa($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($dispute_id) || !isset($mensagem)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados
    $message_data = Array(
        'message' => $mensagem
    );
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/customer/disputes/' . $dispute_id . '/send-message',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $message_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-send-dispute-message-error',
                'mensagem' => 'Erro ao enviar mensagem para disputa no PayPal',
                'detalhes' => Array('dispute_id' => $dispute_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Escala uma disputa para reclamação.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['dispute_id'] ID da disputa (obrigatório)
 * @param string $params['nota'] Nota explicativa (opcional)
 *
 * @return array|false Array com resultado ou false em erro
 */
function paypal_escalar_disputa($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($dispute_id)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir dados
    $escalate_data = Array();
    if(isset($nota)){
        $escalate_data['note'] = $nota;
    }
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/customer/disputes/' . $dispute_id . '/escalate',
        'method' => 'POST',
        'access_token' => $token['access_token'],
        'data' => $escalate_data
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-escalate-dispute-error',
                'mensagem' => 'Erro ao escalar disputa no PayPal',
                'detalhes' => Array('dispute_id' => $dispute_id, 'response' => $response)
            ));
        }
        return false;
    }
    
    return $response['data'];
}

// ============================================================================
// TRANSACTIONS API - Consulta de Transações
// ============================================================================

/**
 * Lista transações da conta PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['data_inicio'] Data de início (obrigatório, formato: YYYY-MM-DDTHH:MM:SSZ)
 * @param string $params['data_fim'] Data de fim (obrigatório, formato: YYYY-MM-DDTHH:MM:SSZ)
 * @param string $params['transaction_id'] ID da transação específica (opcional)
 * @param string $params['status'] Status da transação (opcional): D (Denied), P (Pending), S (Successful), V (Reversed)
 * @param int $params['pagina'] Número da página (opcional)
 * @param int $params['limite'] Itens por página (opcional, max: 500)
 *
 * @return array|false Array com lista de transações ou false em erro
 */
function paypal_listar_transacoes($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($data_inicio) || !isset($data_fim)){
        return false;
    }
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array(
        'start_date' => $data_inicio,
        'end_date' => $data_fim,
        'fields' => 'all'
    );
    
    if(isset($transaction_id)){
        $query_params['transaction_id'] = $transaction_id;
    }
    if(isset($status)){
        $query_params['transaction_status'] = strtoupper($status);
    }
    if(isset($pagina)){
        $query_params['page'] = (int)$pagina;
    }
    if(isset($limite)){
        $query_params['page_size'] = min((int)$limite, 500);
    }
    
    $query_string = '?' . http_build_query($query_params);
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/reporting/transactions' . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-list-transactions-error',
                'mensagem' => 'Erro ao listar transações no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

/**
 * Consulta saldo da conta PayPal.
 *
 * @param array|false $params Parâmetros da função
 * @param string $params['moeda'] Código da moeda (opcional, retorna todas se não especificado)
 *
 * @return array|false Array com saldos ou false em erro
 */
function paypal_consultar_saldo($params = false){
    // Extrair parâmetros
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Obter token de autenticação
    $token = paypal_autenticar();
    if(!$token){
        return false;
    }
    
    // Construir query string
    $query_params = Array();
    if(isset($moeda)){
        $query_params['currency_code'] = strtoupper($moeda);
    }
    $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';
    
    // Fazer requisição
    $response = paypal_requisicao(Array(
        'endpoint' => '/v1/reporting/balances' . $query_string,
        'method' => 'GET',
        'access_token' => $token['access_token']
    ));
    
    if(!$response || $response['http_code'] !== 200){
        if(function_exists('paypal_log_registro')){
            paypal_log_registro(Array(
                'tipo' => 'paypal-get-balance-error',
                'mensagem' => 'Erro ao consultar saldo no PayPal',
                'detalhes' => $response
            ));
        }
        return false;
    }
    
    return $response['data'];
}

// ============================================================================
// UTILITIES - Funções Utilitárias
// ============================================================================

/**
 * Formata valor monetário para exibição.
 *
 * @param float $valor Valor numérico
 * @param string $moeda Código da moeda (padrão: BRL)
 *
 * @return string Valor formatado
 */
function paypal_formatar_valor($valor, $moeda = 'BRL'){
    $simbolos = Array(
        'BRL' => 'R$',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'MXN' => 'MX$',
        'ARS' => 'AR$',
        'CLP' => 'CL$',
        'COP' => 'CO$',
        'PEN' => 'S/'
    );
    
    $simbolo = isset($simbolos[$moeda]) ? $simbolos[$moeda] : $moeda . ' ';
    
    return $simbolo . ' ' . number_format($valor, 2, ',', '.');
}

/**
 * Traduz status do PayPal para português.
 *
 * @param string $status Status em inglês
 * @param string $tipo Tipo: order, subscription, invoice, dispute, payout
 *
 * @return string Status traduzido
 */
function paypal_traduzir_status($status, $tipo = 'order'){
    $traducoes = Array(
        'order' => Array(
            'CREATED' => 'Criado',
            'SAVED' => 'Salvo',
            'APPROVED' => 'Aprovado',
            'VOIDED' => 'Anulado',
            'COMPLETED' => 'Concluído',
            'PAYER_ACTION_REQUIRED' => 'Ação do Pagador Requerida'
        ),
        'subscription' => Array(
            'APPROVAL_PENDING' => 'Aguardando Aprovação',
            'APPROVED' => 'Aprovado',
            'ACTIVE' => 'Ativo',
            'SUSPENDED' => 'Suspenso',
            'CANCELLED' => 'Cancelado',
            'EXPIRED' => 'Expirado'
        ),
        'invoice' => Array(
            'DRAFT' => 'Rascunho',
            'SENT' => 'Enviada',
            'SCHEDULED' => 'Agendada',
            'PAID' => 'Paga',
            'MARKED_AS_PAID' => 'Marcada como Paga',
            'CANCELLED' => 'Cancelada',
            'REFUNDED' => 'Reembolsada',
            'PARTIALLY_PAID' => 'Parcialmente Paga',
            'PARTIALLY_REFUNDED' => 'Parcialmente Reembolsada',
            'MARKED_AS_REFUNDED' => 'Marcada como Reembolsada',
            'UNPAID' => 'Não Paga',
            'PAYMENT_PENDING' => 'Pagamento Pendente'
        ),
        'dispute' => Array(
            'OPEN' => 'Aberta',
            'WAITING_FOR_SELLER_RESPONSE' => 'Aguardando Resposta do Vendedor',
            'WAITING_FOR_BUYER_RESPONSE' => 'Aguardando Resposta do Comprador',
            'UNDER_REVIEW' => 'Em Análise',
            'RESOLVED' => 'Resolvida',
            'EXPIRED' => 'Expirada',
            'OTHER' => 'Outro'
        ),
        'payout' => Array(
            'PENDING' => 'Pendente',
            'PROCESSING' => 'Processando',
            'SUCCESS' => 'Sucesso',
            'DENIED' => 'Negado',
            'CANCELLED' => 'Cancelado'
        ),
        'payout_item' => Array(
            'SUCCESS' => 'Sucesso',
            'FAILED' => 'Falhou',
            'PENDING' => 'Pendente',
            'UNCLAIMED' => 'Não Reclamado',
            'RETURNED' => 'Retornado',
            'ONHOLD' => 'Em Espera',
            'BLOCKED' => 'Bloqueado',
            'REFUNDED' => 'Reembolsado',
            'REVERSED' => 'Revertido'
        )
    );
    
    if(isset($traducoes[$tipo][$status])){
        return $traducoes[$tipo][$status];
    }
    
    return $status;
}

/**
 * Verifica se uma assinatura está ativa.
 *
 * @param string $subscription_id ID da assinatura
 *
 * @return bool True se ativa, false caso contrário
 */
function paypal_assinatura_ativa($subscription_id){
    $assinatura = paypal_consultar_assinatura(Array(
        'subscription_id' => $subscription_id
    ));
    
    if(!$assinatura){
        return false;
    }
    
    return $assinatura['status'] === 'ACTIVE';
}

/**
 * Verifica se um pedido foi pago.
 *
 * @param string $order_id ID do pedido
 *
 * @return bool True se pago, false caso contrário
 */
function paypal_pedido_pago($order_id){
    $pedido = paypal_consultar_pedido(Array(
        'order_id' => $order_id
    ));
    
    if(!$pedido){
        return false;
    }
    
    return in_array($pedido['status'], Array('COMPLETED', 'APPROVED'));
}

/**
 * Verifica se uma fatura foi paga.
 *
 * @param string $invoice_id ID da fatura
 *
 * @return bool True se paga, false caso contrário
 */
function paypal_fatura_paga($invoice_id){
    $fatura = paypal_consultar_fatura(Array(
        'invoice_id' => $invoice_id
    ));
    
    if(!$fatura){
        return false;
    }
    
    return in_array($fatura['status'], Array('PAID', 'MARKED_AS_PAID'));
}

/**
 * Calcula taxa do PayPal para um valor.
 *
 * IMPORTANTE: Esta é uma estimativa. As taxas reais podem variar.
 * Consulte a tabela de taxas atualizada em paypal.com
 *
 * @param float $valor Valor da transação
 * @param string $tipo Tipo: nacional, internacional
 *
 * @return array Array com valor líquido e taxa estimada
 */
function paypal_calcular_taxa($valor, $tipo = 'nacional'){
    // Taxas aproximadas (podem variar)
    $taxas = Array(
        'nacional' => Array(
            'percentual' => 4.99,
            'fixo' => 0.60
        ),
        'internacional' => Array(
            'percentual' => 5.99,
            'fixo' => 0.60
        )
    );
    
    $taxa_config = isset($taxas[$tipo]) ? $taxas[$tipo] : $taxas['nacional'];
    
    $taxa_percentual = ($valor * $taxa_config['percentual']) / 100;
    $taxa_total = $taxa_percentual + $taxa_config['fixo'];
    $valor_liquido = $valor - $taxa_total;
    
    return Array(
        'valor_bruto' => $valor,
        'taxa_percentual' => round($taxa_percentual, 2),
        'taxa_fixa' => $taxa_config['fixo'],
        'taxa_total' => round($taxa_total, 2),
        'valor_liquido' => round($valor_liquido, 2)
    );
}

/**
 * Gera um ID único para transações.
 *
 * @param string $prefixo Prefixo do ID (opcional)
 *
 * @return string ID único
 */
function paypal_gerar_id($prefixo = 'TXN'){
    return $prefixo . '_' . date('YmdHis') . '_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Valida email do PayPal.
 *
 * @param string $email Email a validar
 *
 * @return bool True se válido
 */
function paypal_validar_email($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Converte data para formato PayPal.
 *
 * @param string|int $data Data (timestamp ou string)
 * @param bool $incluir_hora Incluir hora no formato
 *
 * @return string Data formatada
 */
function paypal_formatar_data($data, $incluir_hora = true){
    if(is_string($data)){
        $timestamp = strtotime($data);
    } else {
        $timestamp = $data;
    }
    
    if($incluir_hora){
        return date('Y-m-d\TH:i:s\Z', $timestamp);
    }
    
    return date('Y-m-d', $timestamp);
}

/**
 * Obtém informações da biblioteca PayPal.
 *
 * @return array Array com informações da biblioteca
 */
function paypal_info(){
    return Array(
        'versao' => '3.0.0',
        'autor' => 'Conn2Flow',
        'licenca' => 'MIT',
        'apis' => Array(
            'Orders API v2' => 'Pedidos e checkout',
            'Payments API v2' => 'Captura e reembolsos',
            'Catalog Products API v1' => 'Produtos do catálogo',
            'Billing Plans API v1' => 'Planos de assinatura',
            'Subscriptions API v1' => 'Assinaturas recorrentes',
            'Invoicing API v2' => 'Faturamento',
            'Payouts API v1' => 'Pagamentos em lote',
            'Disputes API v1' => 'Disputas e contestações',
            'Reporting API v1' => 'Transações e saldo',
            'Webhooks' => 'Notificações em tempo real'
        ),
        'funcoes' => Array(
            // Core
            'paypal_obter_url_api',
            'paypal_obter_credenciais',
            'paypal_requisicao',
            'paypal_autenticar',
            // Orders
            'paypal_criar_pedido',
            'paypal_capturar_pedido',
            'paypal_consultar_pedido',
            // Payments
            'paypal_reembolsar',
            'paypal_consultar_reembolso',
            // Webhooks
            'paypal_validar_webhook',
            'paypal_processar_webhook',
            // Catalog Products
            'paypal_criar_produto',
            'paypal_listar_produtos',
            'paypal_consultar_produto',
            'paypal_atualizar_produto',
            // Billing Plans
            'paypal_criar_plano',
            'paypal_listar_planos',
            'paypal_consultar_plano',
            'paypal_ativar_plano',
            'paypal_desativar_plano',
            'paypal_atualizar_precos_plano',
            // Subscriptions
            'paypal_criar_assinatura',
            'paypal_consultar_assinatura',
            'paypal_suspender_assinatura',
            'paypal_cancelar_assinatura',
            'paypal_ativar_assinatura',
            'paypal_capturar_assinatura',
            'paypal_listar_transacoes_assinatura',
            // Invoicing
            'paypal_criar_fatura',
            'paypal_listar_faturas',
            'paypal_consultar_fatura',
            'paypal_enviar_fatura',
            'paypal_cancelar_fatura',
            'paypal_lembrete_fatura',
            'paypal_registrar_pagamento_fatura',
            'paypal_registrar_reembolso_fatura',
            'paypal_gerar_qrcode_fatura',
            'paypal_buscar_faturas',
            'paypal_deletar_fatura',
            'paypal_gerar_numero_fatura',
            // Payouts
            'paypal_criar_payout',
            'paypal_consultar_payout',
            'paypal_consultar_item_payout',
            'paypal_cancelar_item_payout',
            // Payment Links
            'paypal_gerar_link_pagamento',
            'paypal_verificar_link_pagamento',
            // Disputes
            'paypal_listar_disputas',
            'paypal_consultar_disputa',
            'paypal_aceitar_disputa',
            'paypal_contestar_disputa',
            'paypal_mensagem_disputa',
            'paypal_escalar_disputa',
            // Transactions
            'paypal_listar_transacoes',
            'paypal_consultar_saldo',
            // Utilities
            'paypal_formatar_valor',
            'paypal_traduzir_status',
            'paypal_assinatura_ativa',
            'paypal_pedido_pago',
            'paypal_fatura_paga',
            'paypal_calcular_taxa',
            'paypal_gerar_id',
            'paypal_validar_email',
            'paypal_formatar_data',
            'paypal_info',
            // Gateway
            'paypal_gateways_pagamentos_configurar',
            'paypal_gateway_persistir_token',
            'paypal_gateway_registrar_transacao',
            'paypal_gateway_atualizar_conexao',
            'paypal_is_modo_gateway',
            'paypal_obter_gateway_id',
            'paypal_obter_gateway_dados',
            // Default Mode
            'paypal_padrao_persistir_token',
            'paypal_padrao_restaurar_token',
            'paypal_auto_configurar',
        )
    );
}

// ============================================================================
// INICIALIZAÇÃO AUTOMÁTICA
// ============================================================================

paypal_auto_configurar();