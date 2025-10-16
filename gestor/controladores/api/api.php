<?php

// ===== Controlador da API do Conn2Flow

global $_GESTOR;

$_GESTOR['modulo-id']							=	'api';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Headers CORS e Configurações

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Content-Type: application/json; charset=UTF-8');

// ===== Resposta para preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =========================== Rate Limiting Básico

function api_rate_limit_check($endpoint = 'default') {
    global $_GESTOR;

    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'api_rate_limit_' . $endpoint . '_' . $ip;
    $max_requests = 100; // 100 requisições por hora
    $window = 3600; // 1 hora em segundos

    // Implementação simples de rate limiting (em produção, usar Redis ou similar)
    $api_logs_path = $_GESTOR['logs-path'] . 'api/';
    if (!is_dir($api_logs_path)) {
        mkdir($api_logs_path, 0755, true);
    }
    $cache_file = $api_logs_path . 'api_rate_limit_' . md5($key) . '.cache';

    $current_time = time();
    $requests = [];

    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && isset($data['requests'])) {
            $requests = array_filter($data['requests'], function($timestamp) use ($current_time, $window) {
                return ($current_time - $timestamp) < $window;
            });
        }
    }

    if (count($requests) >= $max_requests) {
        return false; // Rate limit excedido
    }

    $requests[] = $current_time;

    // Manter apenas as últimas 1000 requisições para não crescer indefinidamente
    $requests = array_slice($requests, -1000);

    file_put_contents($cache_file, json_encode(['requests' => $requests]));

    return true;
}

// =========================== Autenticação

function api_authenticate($require_auth = false) {
    global $_GESTOR;

    if (!$require_auth) {
        return true; // Endpoint público
    }

    // Verificar token de autenticação
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    } elseif (isset($headers['X-API-Key'])) {
        $token = $headers['X-API-Key'];
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
    }

    if (!$token) {
        api_response_error('Token de autenticação não fornecido', 401);
    }

    // Validar token (placeholder - implementar validação real)
    // Por enquanto, aceitar qualquer token não vazio
    if (empty($token) || strlen($token) < 10) {
        api_response_error('Token de autenticação inválido', 401);
    }

    // Validação real do token JWT
    gestor_incluir_biblioteca('autenticacao');

    if(!autenticacao_api_validar_token(Array('token' => $token))){
        api_response_error('Token de autenticação inválido ou expirado', 401);
    }

    return true;
}

// =========================== Funções de Resposta

function api_response_success($data = null, $message = 'OK', $code = 200) {
    http_response_code($code);

    $response = [
        'status' => 'success',
        'message' => $message,
        'timestamp' => date('c')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_response_error($message = 'Erro interno do servidor', $code = 500, $details = null) {
    http_response_code($code);

    $response = [
        'status' => 'error',
        'message' => $message,
        'timestamp' => date('c')
    ];

    if ($details !== null) {
        $response['details'] = $details;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================== Parse do Corpo da Requisição

function api_get_request_body() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        api_response_error('JSON inválido no corpo da requisição', 400);
    }

    return $data;
}

// =========================== Handlers de Endpoint

function api_handle_ia() {
    global $_GESTOR;

    // Verificar sub-endpoint
    $sub_endpoint = isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null;

    switch ($sub_endpoint) {
        case 'generate':
            api_ia_generate();
            break;

        case 'status':
            api_ia_status();
            break;

        case 'models':
            api_ia_models();
            break;

        default:
            api_response_error('Sub-endpoint IA não encontrado: ' . $sub_endpoint, 404);
    }
}

function api_ia_generate() {
    // Requer autenticação
    api_authenticate(true);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    $data = api_get_request_body();

    // Validar dados obrigatórios
    if (!isset($data['prompt_id']) && !isset($data['prompt_custom'])) {
        api_response_error('Campo "prompt_id" ou "prompt_custom" é obrigatório', 400);
    }

    if (!isset($data['alvo']) || empty($data['alvo'])) {
        api_response_error('Campo "alvo" é obrigatório', 400);
    }

    // Preparar prompt final
    $prompt_final = '';

    // Se foi fornecido prompt_id, buscar na tabela
    if (isset($data['prompt_id']) && !empty($data['prompt_id'])) {
        // Buscar prompt na tabela prompts_ia
        $prompt_db = banco_select(Array(
            'unico' => true,
            'tabela' => 'prompts_ia',
            'campos' => Array('prompt'),
            'extra' => "WHERE id = '".banco_escape_field($data['prompt_id'])."' AND alvo = '".banco_escape_field($data['alvo'])."' AND status = 'A'"
        ));

        if ($prompt_db) {
            $prompt_final = $prompt_db['prompt'];
        } else {
            api_response_error('Prompt não encontrado ou inativo', 404);
        }
    }

    // Se foi fornecido prompt_custom, usar diretamente
    if (isset($data['prompt_custom']) && !empty($data['prompt_custom'])) {
        if (!empty($prompt_final)) {
            $prompt_final .= "\n\n"; // Separar se já havia prompt do banco
        }
        $prompt_final .= $data['prompt_custom'];
    }

    // Verificar se temos um prompt final
    if (empty($prompt_final)) {
        api_response_error('Prompt final está vazio', 400);
    }

    // Pegar servidor e modelo dos dados
    $servidor_id = isset($data['servidor_id']) ? $data['servidor_id'] : null;
    $modelo = isset($data['modelo']) ? $data['modelo'] : null;

    // Se não foi fornecido servidor_id, tentar pegar o padrão
    if (!$servidor_id) {
        $servidor_padrao = banco_select(Array(
            'unico' => true,
            'tabela' => 'servidores_ia',
            'campos' => Array('id_servidores_ia'),
            'extra' => "WHERE status = 'A' AND padrao = '1'"
        ));

        if ($servidor_padrao) {
            $servidor_id = $servidor_padrao['id_servidores_ia'];
        } else {
            api_response_error('Nenhum servidor IA padrão configurado', 500);
        }
    }

    // Incluir biblioteca IA e enviar prompt
    gestor_incluir_biblioteca('ia');

    $resultado_ia = ia_enviar_prompt(Array(
        'servidor_id' => $servidor_id,
        'modelo' => $modelo,
        'prompt' => $prompt_final
    ));

    // Verificar se houve erro
    if ($resultado_ia['status'] !== 'success') {
        api_response_error('Erro na geração IA: ' . $resultado_ia['message'], 500);
    }

    // Preparar resposta de sucesso
    $response_data = [
        'id' => uniqid('ia_'),
        'alvo' => $data['alvo'],
        'prompt_id' => $data['prompt_id'] ?? null,
        'prompt_custom' => $data['prompt_custom'] ?? null,
        'servidor_id' => $servidor_id,
        'modelo' => $modelo,
        'status' => 'completed',
        'result' => $resultado_ia['data']['texto_gerado'],
        'tokens_entrada' => $resultado_ia['data']['tokens_entrada'],
        'tokens_saida' => $resultado_ia['data']['tokens_saida'],
        'tokens_total' => $resultado_ia['data']['tokens_total'],
        'completed_at' => date('c')
    ];

    api_response_success($response_data, 'Geração IA concluída com sucesso');
}

function api_ia_status() {
    // Pode ser público ou privado dependendo da implementação futura
    api_authenticate(false);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'GET') {
        api_response_error('Método não permitido. Use GET.', 405);
    }

    // Verificar se há ID de requisição
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        api_response_error('Parâmetro "id" é obrigatório', 400);
    }

    $request_id = $_GET['id'];

    // TODO: Verificar status real da requisição
    // Por enquanto, resposta mock
    $response_data = [
        'id' => $request_id,
        'status' => 'completed',
        'result' => 'Resultado mock da IA',
        'completed_at' => date('c')
    ];

    api_response_success($response_data);
}

function api_ia_models() {
    // Requer autenticação
    api_authenticate(true);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'GET') {
        api_response_error('Método não permitido. Use GET.', 405);
    }

    // Buscar modelos disponíveis do arquivo JSON
    $modelos_path = $_GESTOR['ROOT_PATH'] . '/modulos/admin-ia/gemini/' . $_GESTOR['linguagem-codigo'] . '/data.json';

    if (!file_exists($modelos_path)) {
        api_response_error('Arquivo de modelos não encontrado', 500);
    }

    $modelos_data = json_decode(file_get_contents($modelos_path), true);

    if (!$modelos_data || !isset($modelos_data['models'])) {
        api_response_error('Dados de modelos inválidos', 500);
    }

    // Formatar resposta
    $models_response = [];
    foreach ($modelos_data['models'] as $modelo) {
        $models_response[] = [
            'id' => $modelo['name'],
            'name' => $modelo['displayName'],
            'description' => $modelo['description'],
            'version' => $modelo['version'],
            'input_token_limit' => $modelo['inputTokenLimit'],
            'output_token_limit' => $modelo['outputTokenLimit'],
            'thinking' => isset($modelo['thinking']) ? $modelo['thinking'] : false
        ];
    }

    api_response_success(['models' => $models_response]);
}

// =========================== Roteamento da API

function api_route_request() {
    global $_GESTOR;

    // Verificar se há caminho suficiente
    if (!isset($_GESTOR['caminho'][1])) {
        api_response_error('Endpoint não especificado', 400);
    }

    $endpoint = $_GESTOR['caminho'][1];
    $method = $_SERVER['REQUEST_METHOD'];

    // Rate limiting
    if (!api_rate_limit_check($endpoint)) {
        api_response_error('Rate limit excedido. Tente novamente mais tarde.', 429);
    }

    // Roteamento baseado no endpoint
    switch ($endpoint) {
        case 'ia':
            api_handle_ia();
            break;

        case 'status':
            api_response_success(['status' => 'API operacional', 'version' => '1.0.0']);
            break;

        case 'health':
            api_response_success(['status' => 'healthy', 'timestamp' => time()]);
            break;

        default:
            api_response_error('Endpoint não encontrado: ' . $endpoint, 404);
    }
}

// =========================== Inicialização da API

try {
    api_route_request();
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    api_response_error('Erro interno do servidor', 500);
}

?>
