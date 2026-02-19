<?php
/**
 * =====================================================================
 * CONN2FLOW - PLATAFORMA DE GATEWAYS DE PAGAMENTO
 * =====================================================================
 * 
 * Controlador responsável por receber webhooks, notificações e retornos
 * de gateways de pagamento externos (PayPal, Stripe, PagBank, etc).
 * 
 * Endpoints disponíveis:
 * - /_gateways/paypal/webhook     - Webhooks do PayPal
 * - /_gateways/paypal/return      - Retorno de pagamento aprovado
 * - /_gateways/paypal/cancel      - Retorno de pagamento cancelado
 * - /_gateways/{modulo}/endpoint  - Ponte para módulos privados
 * 
 * @package     Conn2Flow
 * @subpackage  Controladores
 * @version     2.0.0
 * @author      Conn2Flow
 * @since       2025
 * 
 * =====================================================================
 */

global $_GESTOR;
global $_CONFIG;

$_GESTOR['modulo-id'] = 'plataforma-gateways';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = Array(
    'versao' => '2.0.0',
);

// =========================== CONFIGURAÇÕES DE SEGURANÇA ===========================

/**
 * Lista de IPs autorizados para webhooks do PayPal
 * @see https://developer.paypal.com/api/rest/requests/#link-ipaddresses
 */
define('PAYPAL_WEBHOOK_IPS', Array(
    // PayPal Production IPs
    '64.4.240.0/21',
    '64.4.248.0/22',
    '66.211.168.0/22',
    '91.243.72.0/22',
    '173.0.80.0/20',
    '173.0.82.0/23',
    '173.0.84.0/22',
    '198.54.216.0/21',
    // PayPal Sandbox IPs (para desenvolvimento)
    '173.0.81.0/24',
    '173.0.81.33',
    '173.0.81.1',
));

/**
 * Rate limiting - máximo de requisições por minuto por IP
 */
define('GATEWAYS_RATE_LIMIT', 60);

/**
 * Tempo máximo de processamento (segundos)
 */
define('GATEWAYS_MAX_EXECUTION_TIME', 30);

// =========================== FUNÇÕES DE SEGURANÇA ===========================

/**
 * Verifica se o IP está dentro de um range CIDR
 * 
 * @param string $ip IP a verificar
 * @param string $cidr Range CIDR (ex: 192.168.1.0/24)
 * @return bool
 */
function plataforma_gateways_ip_in_cidr($ip, $cidr) {
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    
    list($subnet, $mask) = explode('/', $cidr);
    $subnet_long = ip2long($subnet);
    $ip_long = ip2long($ip);
    $mask_long = -1 << (32 - $mask);
    $subnet_long &= $mask_long;
    
    return ($ip_long & $mask_long) === $subnet_long;
}

/**
 * Verifica se o IP do cliente é de uma fonte confiável
 * 
 * @param string $gateway Identificador do gateway (paypal, stripe, etc)
 * @return bool
 */
function plataforma_gateways_verificar_ip($gateway) {
    $client_ip = $_SERVER['REMOTE_ADDR'];
    
    // Verificar header X-Forwarded-For se estiver atrás de proxy
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $client_ip = trim($forwarded_ips[0]);
    }
    
    switch ($gateway) {
        case 'paypal':
            foreach (PAYPAL_WEBHOOK_IPS as $allowed_ip) {
                if (plataforma_gateways_ip_in_cidr($client_ip, $allowed_ip)) {
                    return true;
                }
            }
            // Em ambiente de desenvolvimento, permitir qualquer IP
            if (isset($_CONFIG['paypal']['mode']) && $_CONFIG['paypal']['mode'] === 'sandbox') {
                return true;
            }
            break;
            
        case 'stripe':
            // Stripe usa validação por assinatura, não por IP
            return true;
            
        case 'pagbank':
            // PagBank/PagSeguro IPs
            $pagbank_ips = Array(
                '186.234.48.0/20',
                '186.234.32.0/19',
            );
            foreach ($pagbank_ips as $allowed_ip) {
                if (plataforma_gateways_ip_in_cidr($client_ip, $allowed_ip)) {
                    return true;
                }
            }
            break;
    }
    
    return false;
}

/**
 * Rate limiting simples baseado em arquivo
 * 
 * @param string $identifier Identificador único (IP ou token)
 * @return bool True se permitido, false se bloqueado
 */
function plataforma_gateways_rate_limit($identifier) {
    global $_GESTOR;
    
    $rate_file = $_GESTOR['logs-path'] . 'rate_limits/' . md5($identifier) . '.json';
    $rate_dir = dirname($rate_file);
    
    if (!is_dir($rate_dir)) {
        mkdir($rate_dir, 0755, true);
    }
    
    $now = time();
    $window = 60; // 1 minuto
    $limit = GATEWAYS_RATE_LIMIT;
    
    $data = Array('requests' => Array());
    
    if (file_exists($rate_file)) {
        $content = file_get_contents($rate_file);
        $data = json_decode($content, true) ?: Array('requests' => Array());
    }
    
    // Remover requisições antigas
    $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    // Verificar limite
    if (count($data['requests']) >= $limit) {
        return false;
    }
    
    // Adicionar nova requisição
    $data['requests'][] = $now;
    file_put_contents($rate_file, json_encode($data));
    
    return true;
}

/**
 * Valida Content-Type da requisição
 * 
 * @param array $allowed_types Tipos permitidos
 * @return bool
 */
function plataforma_gateways_validar_content_type($allowed_types = Array('application/json')) {
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    foreach ($allowed_types as $type) {
        if (stripos($content_type, $type) !== false) {
            return true;
        }
    }
    
    return false;
}

// =========================== FUNÇÕES DE LOG ===========================

/**
 * Registra log de webhook/notificação
 * 
 * @param array $params Parâmetros do log
 *   - gateway: string - Identificador do gateway
 *   - endpoint: string - Endpoint acessado
 *   - status: string - Status (success, error, warning)
 *   - message: string - Mensagem de log
 *   - data: array - Dados adicionais
 */
function plataforma_gateways_log($params = Array()) {
    global $_GESTOR;
    
    $gateway = isset($params['gateway']) ? $params['gateway'] : 'unknown';
    $endpoint = isset($params['endpoint']) ? $params['endpoint'] : '';
    $status = isset($params['status']) ? $params['status'] : 'info';
    $message = isset($params['message']) ? $params['message'] : '';
    $data = isset($params['data']) ? $params['data'] : Array();
    
    $log_dir = $_GESTOR['logs-path'] . 'gateways/' . $gateway . '/';
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . date('Y-m-d') . '.log';
    
    $log_entry = Array(
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'endpoint' => $endpoint,
        'status' => $status,
        'message' => $message,
        'data' => $data,
    );
    
    // Preparar linha de log (JSON)
    $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE);

    // Usar biblioteca de logs do sistema para gravar a linha
    gestor_incluir_biblioteca('log');

    // Garantir diretório (já criado acima, mas reafirmar)
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Escrever usando log_disco — mantém histórico centralizado em `logs-path`
    // Arquivo resultante: {logs-path}/gateways/{gateway}/{gateway}-YYYY-MM-DD.log
    log_disco($log_line, 'gateways/' . $gateway . '/' . $gateway);
}

// =========================== FUNÇÕES DE RESPOSTA ===========================

/**
 * Retorna resposta JSON com status 200
 * 
 * @param array $data Dados a retornar
 */
function plataforma_gateways_resposta_sucesso($data = Array()) {
    http_response_code(200);
    header("Content-Type: application/json; charset=UTF-8");
    
    $response = array_merge(Array(
        'status' => 'OK',
        'statusCode' => 200,
    ), $data);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Retorna resposta JSON com erro
 * 
 * @param int $code Código HTTP
 * @param string $message Mensagem de erro
 * @param array $extra Dados extras
 */
function plataforma_gateways_resposta_erro($code, $message, $extra = Array()) {
    http_response_code($code);
    header("Content-Type: application/json; charset=UTF-8");
    
    $response = array_merge(Array(
        'status' => 'ERROR',
        'statusCode' => $code,
        'message' => $message,
    ), $extra);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Retorna erro 400 Bad Request
 */
function plataforma_gateways_400($message = 'Bad Request') {
    plataforma_gateways_resposta_erro(400, $message);
}

/**
 * Retorna erro 401 Unauthorized
 */
function plataforma_gateways_401($message = 'Unauthorized') {
    plataforma_gateways_resposta_erro(401, $message);
}

/**
 * Retorna erro 403 Forbidden
 */
function plataforma_gateways_403($message = 'Forbidden') {
    plataforma_gateways_resposta_erro(403, $message);
}

/**
 * Retorna erro 404 Not Found
 */
function plataforma_gateways_404($message = 'Not Found') {
    plataforma_gateways_resposta_erro(404, $message);
}

/**
 * Retorna erro 429 Too Many Requests
 */
function plataforma_gateways_429() {
    plataforma_gateways_resposta_erro(429, 'Too Many Requests', Array(
        'retry_after' => 60,
    ));
}

/**
 * Retorna erro 500 Internal Server Error
 */
function plataforma_gateways_500($message = 'Internal Server Error') {
    plataforma_gateways_resposta_erro(500, $message);
}

// =========================== PAYPAL - HANDLERS ===========================

/**
 * Processa webhook do PayPal
 */
function plataforma_gateways_paypal_webhook() {
    global $_GESTOR;
    global $_CONFIG;
    
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        plataforma_gateways_log(Array(
            'gateway' => 'paypal',
            'endpoint' => 'webhook',
            'status' => 'error',
            'message' => 'Método não permitido: ' . $_SERVER['REQUEST_METHOD'],
        ));
        plataforma_gateways_resposta_erro(405, 'Method Not Allowed');
    }
    
    // Verificar Content-Type
    if (!plataforma_gateways_validar_content_type(Array('application/json'))) {
        plataforma_gateways_log(Array(
            'gateway' => 'paypal',
            'endpoint' => 'webhook',
            'status' => 'error',
            'message' => 'Content-Type inválido',
        ));
        plataforma_gateways_400('Invalid Content-Type');
    }
    
    // Obter payload
    $payload = file_get_contents('php://input');
    
    if (empty($payload)) {
        plataforma_gateways_log(Array(
            'gateway' => 'paypal',
            'endpoint' => 'webhook',
            'status' => 'error',
            'message' => 'Payload vazio',
        ));
        plataforma_gateways_400('Empty payload');
    }
    
    $data = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        plataforma_gateways_log(Array(
            'gateway' => 'paypal',
            'endpoint' => 'webhook',
            'status' => 'error',
            'message' => 'JSON inválido: ' . json_last_error_msg(),
        ));
        plataforma_gateways_400('Invalid JSON');
    }
    
    // Incluir biblioteca PayPal
    gestor_incluir_biblioteca('paypal');
    
    // Validar assinatura do webhook
    $headers = getallheaders();
    $valido = paypal_validar_webhook(Array(
        'headers' => $headers,
        'body' => $payload,
    ));
    
    if (!$valido) {
        plataforma_gateways_log(Array(
            'gateway' => 'paypal',
            'endpoint' => 'webhook',
            'status' => 'error',
            'message' => 'Assinatura do webhook inválida',
            'data' => Array(
                'event_type' => isset($data['event_type']) ? $data['event_type'] : 'unknown',
            ),
        ));
        plataforma_gateways_401('Invalid webhook signature');
    }
    
    // Processar evento via biblioteca PayPal (não-fatal)
    // A biblioteca faz processamento interno; se falhar, usamos os dados
    // brutos do JSON para garantir que os hooks dos módulos sejam disparados.
    $evento = paypal_processar_webhook(Array(
        'body' => $payload,
    ));
    
    if (!$evento) {
        // A biblioteca não conseguiu processar, mas temos o JSON decodificado.
        // Construir estrutura compatível a partir do $data bruto para os hooks.
        plataforma_gateways_log(Array(
            'gateway' => 'paypal',
            'endpoint' => 'webhook',
            'status' => 'warning',
            'message' => 'Biblioteca PayPal não processou o evento — usando dados brutos para hooks',
            'data' => $data,
        ));
        
        $evento = Array(
            'event_type' => isset($data['event_type']) ? $data['event_type'] : 'unknown',
            'resource_type' => isset($data['resource_type']) ? $data['resource_type'] : null,
            'resource' => isset($data['resource']) ? $data['resource'] : null,
            'event_data' => $data,
        );
    }
    
    // Log de sucesso
    plataforma_gateways_log(Array(
        'gateway' => 'paypal',
        'endpoint' => 'webhook',
        'status' => 'success',
        'message' => 'Webhook processado: ' . $evento['event_type'],
        'data' => Array(
            'event_id' => isset($data['id']) ? $data['id'] : null,
            'event_type' => $evento['event_type'],
            'resource_id' => isset($evento['resource']['id']) ? $evento['resource']['id'] : null,
        ),
    ));
    
    // Dispara hook para módulos processarem o evento
    // Os hooks SEMPRE são disparados, independente se a biblioteca processou ou não.
    // Isso permite que módulos personalizados tratem webhooks de forma customizada.
    $resultado_hook = plataforma_gateways_disparar_hook('paypal', 'webhook', $evento);
    
    // Retornar sucesso para o PayPal
    plataforma_gateways_resposta_sucesso(Array(
        'event_id' => isset($data['id']) ? $data['id'] : null,
        'processed' => true,
    ));
}

/**
 * Processa retorno de pagamento aprovado do PayPal
 */
function plataforma_gateways_paypal_return() {
    global $_GESTOR;
    
    // Obter parâmetros de retorno
    $token = isset($_GET['token']) ? $_GET['token'] : null;
    $payer_id = isset($_GET['PayerID']) ? $_GET['PayerID'] : null;
    $ba_token = isset($_GET['ba_token']) ? $_GET['ba_token'] : null; // Para assinaturas
    $subscription_id = isset($_GET['subscription_id']) ? $_GET['subscription_id'] : null;
    
    // Log do retorno
    plataforma_gateways_log(Array(
        'gateway' => 'paypal',
        'endpoint' => 'return',
        'status' => 'info',
        'message' => 'Retorno de pagamento recebido',
        'data' => Array(
            'token' => $token,
            'payer_id' => $payer_id,
            'ba_token' => $ba_token,
            'subscription_id' => $subscription_id,
        ),
    ));
    
    // Dispara hook para módulos processarem o retorno
    $resultado = plataforma_gateways_disparar_hook('paypal', 'return', Array(
        'token' => $token,
        'payer_id' => $payer_id,
        'ba_token' => $ba_token,
        'subscription_id' => $subscription_id,
        'query_string' => $_GET,
    ));
    
    // Se o hook retornou uma URL de redirecionamento, usar ela
    if (isset($resultado['redirect_url'])) {
        header('Location: ' . $resultado['redirect_url']);
        exit;
    }
    
    // Redirecionar para página padrão de sucesso
    $success_url = $_GESTOR['url-raiz'] . 'checkout/sucesso/';
    if ($token) {
        $success_url .= '?token=' . urlencode($token);
    }
    
    header('Location: ' . $success_url);
    exit;
}

/**
 * Processa cancelamento de pagamento do PayPal
 */
function plataforma_gateways_paypal_cancel() {
    global $_GESTOR;
    
    // Obter parâmetros de cancelamento
    $token = isset($_GET['token']) ? $_GET['token'] : null;
    
    // Log do cancelamento
    plataforma_gateways_log(Array(
        'gateway' => 'paypal',
        'endpoint' => 'cancel',
        'status' => 'info',
        'message' => 'Pagamento cancelado pelo usuário',
        'data' => Array(
            'token' => $token,
        ),
    ));
    
    // Dispara hook para módulos processarem o cancelamento
    $resultado = plataforma_gateways_disparar_hook('paypal', 'cancel', Array(
        'token' => $token,
        'query_string' => $_GET,
    ));
    
    // Se o hook retornou uma URL de redirecionamento, usar ela
    if (isset($resultado['redirect_url'])) {
        header('Location: ' . $resultado['redirect_url']);
        exit;
    }
    
    // Redirecionar para página padrão de cancelamento
    $cancel_url = $_GESTOR['url-raiz'] . 'checkout/cancelado/';
    if ($token) {
        $cancel_url .= '?token=' . urlencode($token);
    }
    
    header('Location: ' . $cancel_url);
    exit;
}

// =========================== PONTE PARA MÓDULOS ===========================

/**
 * Resolve o diretório base de um módulo (padrão ou plugin).
 *
 * Para plugins o slug do plugin corresponde ao nome da pasta do plugin,
 * portanto NÃO é necessária uma consulta ao banco para resolver o caminho.
 *
 * @param string $modulo_id ID do módulo
 * @param string|null $plugin_id ID de plugin (slug = nome da pasta do plugin)
 * @return string|null Caminho do diretório do módulo ou null
 */
function plataforma_gateways_resolver_modulo_dir($modulo_id, $plugin_id = null) {
    global $_GESTOR;

    if (!empty($plugin_id)) {
        // Usar o slug do plugin diretamente como nome da pasta
        return rtrim($_GESTOR['plugins-path'], '/') . '/' . $plugin_id . '/modulos/' . $modulo_id . '/';
    }

    return $_GESTOR['modulos-path'] . $modulo_id . '/';
}

/**
 * Carrega o arquivo de hook de um módulo a partir do seu JSON de configuração.
 * 
 * Lê o arquivo {modulo_id}.json, procura a chave hooks.{hook_name} que indica
 * o nome do arquivo PHP a ser incluído. Inclui o arquivo e retorna o nome
 * da função esperada.
 * 
 * @param string $modulo_id ID do módulo
 * @param string $plugin_id ID do plugin (ou null para módulo padrão)
 * @param string $hook_name Nome do hook (ex: 'plataforma_gateways')
 * @return string|null Nome da função do hook ou null se não encontrado
 */
function plataforma_gateways_carregar_hook($modulo_id, $plugin_id, $hook_name) {
    $modulo_dir = plataforma_gateways_resolver_modulo_dir($modulo_id, $plugin_id);
    
    if (!$modulo_dir) {
        return null;
    }
    
    // Ler JSON de configuração do módulo
    $json_path = $modulo_dir . $modulo_id . '.json';
    
    if (!file_exists($json_path)) {
        plataforma_gateways_log(Array(
            'gateway' => 'system',
            'endpoint' => 'hook-loader',
            'status' => 'warning',
            'message' => 'Arquivo JSON do módulo não encontrado: ' . $modulo_id,
            'data' => Array('path' => $json_path),
        ));
        return null;
    }
    
    $json_content = file_get_contents($json_path);
    $modulo_config = json_decode($json_content, true);
    
    if (!$modulo_config || json_last_error() !== JSON_ERROR_NONE) {
        plataforma_gateways_log(Array(
            'gateway' => 'system',
            'endpoint' => 'hook-loader',
            'status' => 'error',
            'message' => 'JSON inválido no módulo: ' . $modulo_id,
            'data' => Array('json_error' => json_last_error_msg()),
        ));
        return null;
    }
    
    // Buscar configuração do hook
    $hook_file = isset($modulo_config['hooks'][$hook_name]) ? $modulo_config['hooks'][$hook_name] : null;
    
    if (empty($hook_file)) {
        return null;
    }
    
    // Incluir o arquivo de hook
    $hook_path = $modulo_dir . $hook_file;
    
    if (!file_exists($hook_path)) {
        plataforma_gateways_log(Array(
            'gateway' => 'system',
            'endpoint' => 'hook-loader',
            'status' => 'error',
            'message' => 'Arquivo de hook não encontrado: ' . $hook_file,
            'data' => Array('module' => $modulo_id, 'path' => $hook_path),
        ));
        return null;
    }
    
    include_once($hook_path);
    
    // Nome da função esperada: {modulo_id_com_underscores}_plataforma_gateways
    $funcao_id = str_replace('-', '_', $modulo_id);
    $funcao = $funcao_id . '_plataforma_gateways';
    
    if (function_exists($funcao)) {
        return $funcao;
    }
    
    plataforma_gateways_log(Array(
        'gateway' => 'system',
        'endpoint' => 'hook-loader',
        'status' => 'warning',
        'message' => 'Função de hook não encontrada após include: ' . $funcao,
        'data' => Array('module' => $modulo_id, 'file' => $hook_file),
    ));
    
    return null;
}

/**
 * Dispara hook para módulos que possuem hooks configurados no JSON.
 * 
 * Busca módulos com coluna hooks IS NOT NULL, lê o JSON de cada módulo
 * para encontrar o arquivo de hook configurado em hooks.plataforma_gateways,
 * inclui o arquivo e executa a função {modulo_id}_plataforma_gateways().
 * 
 * @param string $gateway Identificador do gateway
 * @param string $action Ação (webhook, return, cancel, etc)
 * @param array $data Dados do evento
 * @return array|null Resultado do processamento ou null
 */
function plataforma_gateways_disparar_hook($gateway, $action, $data = Array()) {
    global $_GESTOR;
    
    $resultado = null;
    
    // Buscar módulos que possuem hooks configurados
    $modulos = banco_select(Array(
        'tabela' => 'modulos',
        'campos' => ['id', 'plugin'],
        'extra' => "WHERE hooks IS NOT NULL AND status != 'D'",
    ));
    
    if ($modulos) {
        foreach ($modulos as $modulo) {
            $modulo_id = $modulo['id'];
            
            // Carregar hook a partir do JSON do módulo
            $funcao = plataforma_gateways_carregar_hook($modulo_id, $modulo['plugin'] ?? null, 'plataforma_gateways');
            
            if (!$funcao) {
                continue;
            }
            
            try {
                $resultado_modulo = $funcao(Array(
                    'gateway' => $gateway,
                    'action' => $action,
                    'data' => $data,
                ));
                
                // Se o módulo retornou um resultado com redirect_url, usar ele
                if (isset($resultado_modulo['redirect_url'])) {
                    $resultado = $resultado_modulo;
                }
                
                // Se o módulo marcou como processado, sair do loop
                if (isset($resultado_modulo['processed']) && $resultado_modulo['processed']) {
                    break;
                }
            } catch (Exception $e) {
                plataforma_gateways_log(Array(
                    'gateway' => $gateway,
                    'endpoint' => 'hook',
                    'status' => 'error',
                    'message' => 'Erro ao executar hook do módulo: ' . $modulo_id,
                    'data' => Array(
                        'error' => $e->getMessage(),
                    ),
                ));
            }
        }
    }
    
    return $resultado;
}

/**
 * Processa requisição direta para módulo específico
 * Rota: /_gateways/{modulo_id}/{endpoint}
 * 
 * @param string $modulo_id ID do módulo
 * @param string $endpoint Endpoint específico
 */
function plataforma_gateways_modulo_direto($modulo_id, $endpoint) {
    global $_GESTOR;
    
    // Sanitizar modulo_id
    $modulo_id = preg_replace('/[^a-z0-9\-_]/', '', strtolower($modulo_id));
    
    if (empty($modulo_id)) {
        plataforma_gateways_404('Module not found');
    }
    
    // Verificar se o módulo existe, está ativo e possui hooks configurados
    $modulo = banco_select(Array(
        'tabela' => 'modulos',
        'campos' => ['id', 'plugin'],
        'extra' => "WHERE id = '" . banco_escape_field($modulo_id) . "' AND hooks IS NOT NULL AND status != 'D'",
    ));
    
    if (!$modulo) {
        plataforma_gateways_log(Array(
            'gateway' => 'module',
            'endpoint' => $modulo_id . '/' . $endpoint,
            'status' => 'error',
            'message' => 'Módulo não encontrado, inativo ou sem hooks configurados',
        ));
        plataforma_gateways_404('Module not found');
    }
    
    // Carregar hook a partir do JSON do módulo
    $funcao = plataforma_gateways_carregar_hook($modulo_id, $modulo[0]['plugin'] ?? null, 'plataforma_gateways');
    
    if (!$funcao) {
        plataforma_gateways_log(Array(
            'gateway' => 'module',
            'endpoint' => $modulo_id . '/' . $endpoint,
            'status' => 'error',
            'message' => 'Hook plataforma_gateways não configurado no JSON do módulo',
        ));
        plataforma_gateways_404('Gateway hook not configured in module');
    }
    
    // Log da requisição
    plataforma_gateways_log(Array(
        'gateway' => 'module',
        'endpoint' => $modulo_id . '/' . $endpoint,
        'status' => 'info',
        'message' => 'Requisição direcionada para módulo',
    ));
    
    // Executar função do módulo
    try {
        $resultado = $funcao(Array(
            'gateway' => 'direct',
            'action' => $endpoint,
            'data' => Array(
                'method' => $_SERVER['REQUEST_METHOD'],
                'query_string' => $_GET,
                'payload' => file_get_contents('php://input'),
                'headers' => getallheaders(),
            ),
        ));
        
        // Se o módulo retornou dados, enviar como resposta
        if ($resultado) {
            if (isset($resultado['redirect_url'])) {
                header('Location: ' . $resultado['redirect_url']);
                exit;
            }
            
            plataforma_gateways_resposta_sucesso($resultado);
        }
    } catch (Exception $e) {
        plataforma_gateways_log(Array(
            'gateway' => 'module',
            'endpoint' => $modulo_id . '/' . $endpoint,
            'status' => 'error',
            'message' => 'Erro ao executar função do módulo',
            'data' => Array('error' => $e->getMessage()),
        ));
        plataforma_gateways_500('Module execution error');
    }
    
    // Se chegou aqui sem resultado, retornar sucesso genérico
    plataforma_gateways_resposta_sucesso();
}

// =========================== ROUTER PRINCIPAL ===========================

/**
 * Inicializa a plataforma de gateways
 */
function plataforma_gateways_start() {
    global $_GESTOR;
    
    // Definir tempo máximo de execução
    set_time_limit(GATEWAYS_MAX_EXECUTION_TIME);
    
    // Verificar rate limiting
    $client_ip = $_SERVER['REMOTE_ADDR'];
    if (!plataforma_gateways_rate_limit($client_ip)) {
        plataforma_gateways_log(Array(
            'gateway' => 'system',
            'endpoint' => 'rate_limit',
            'status' => 'warning',
            'message' => 'Rate limit excedido',
            'data' => Array('ip' => $client_ip),
        ));
        plataforma_gateways_429();
    }
    
    // Obter segmentos do caminho
    // $_GESTOR['caminho'][0] = '_gateways'
    // $_GESTOR['caminho'][1] = gateway_id ou modulo_id
    // $_GESTOR['caminho'][2] = endpoint
    
    $gateway = isset($_GESTOR['caminho'][1]) ? strtolower($_GESTOR['caminho'][1]) : null;
    $endpoint = isset($_GESTOR['caminho'][2]) ? strtolower($_GESTOR['caminho'][2]) : null;
    
    // Verificar se há gateway/módulo especificado
    if (empty($gateway)) {
        plataforma_gateways_resposta_sucesso(Array(
            'service' => 'Conn2Flow Payment Gateways Platform',
            'version' => '2.0.0',
            'endpoints' => Array(
                'paypal' => '/_gateways/paypal/{webhook|return|cancel}',
                'modules' => '/_gateways/{module_id}/{endpoint}',
            ),
        ));
    }
    
    // Roteamento por gateway
    switch ($gateway) {
        case 'paypal':
            switch ($endpoint) {
                case 'webhook':
                    plataforma_gateways_paypal_webhook();
                    break;
                    
                case 'return':
                    plataforma_gateways_paypal_return();
                    break;
                    
                case 'cancel':
                    plataforma_gateways_paypal_cancel();
                    break;
                    
                default:
                    // Endpoint desconhecido do PayPal
                    plataforma_gateways_log(Array(
                        'gateway' => 'paypal',
                        'endpoint' => $endpoint,
                        'status' => 'error',
                        'message' => 'Endpoint não encontrado',
                    ));
                    plataforma_gateways_404('PayPal endpoint not found');
            }
            break;
            
        case 'stripe':
            // TODO: Implementar handlers do Stripe
            plataforma_gateways_404('Stripe gateway not implemented yet');
            break;
            
        case 'pagbank':
        case 'pagseguro':
            // TODO: Implementar handlers do PagBank/PagSeguro
            plataforma_gateways_404('PagBank gateway not implemented yet');
            break;
            
        default:
            // Tentar como módulo direto
            plataforma_gateways_modulo_direto($gateway, $endpoint);
    }
}

// =========================== INICIAR PLATAFORMA ===========================

plataforma_gateways_start();

?>