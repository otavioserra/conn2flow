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

    // Validação real do token OAuth 2.0
    gestor_incluir_biblioteca('oauth2');

    $token_validacao = oauth2_validar_token(Array('token' => $token));
    if(!$token_validacao || !is_array($token_validacao)){
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

// =========================== Handlers de Endpoint PROJECT

function api_handle_project() {
    global $_GESTOR;

    // Verificar sub-endpoint
    $sub_endpoint = isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null;

    switch ($sub_endpoint) {
        case 'update':
            api_project_update();
            break;

        default:
            api_response_error('Sub-endpoint PROJECT não encontrado: ' . $sub_endpoint, 404);
    }
}

function api_project_update() {
    global $_GESTOR;

    // Requer autenticação
    api_authenticate(true);

    // Obter project ID do header
    $project_id = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    // Verificar se é multipart/form-data
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($content_type, 'multipart/form-data') === false) {
        api_response_error('Content-Type deve ser multipart/form-data', 400);
    }

    // Verificar se arquivo foi enviado
    if (!isset($_FILES['project_zip']) || $_FILES['project_zip']['error'] !== UPLOAD_ERR_OK) {
        api_response_error('Arquivo project_zip não foi enviado ou houve erro no upload', 400);
    }

    $uploaded_file = $_FILES['project_zip'];
    $temp_path = $uploaded_file['tmp_name'];
    $original_name = $uploaded_file['name'];

    // Validar extensão do arquivo
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if ($file_extension !== 'zip') {
        api_response_error('Apenas arquivos ZIP são permitidos', 400);
    }

    // Verificar tamanho do arquivo (máximo 100MB)
    $max_size = 100 * 1024 * 1024; // 100MB
    if ($uploaded_file['size'] > $max_size) {
        api_response_error('Arquivo muito grande. Máximo permitido: 100MB', 400);
    }

    // Criar diretório temporário para processamento
    $temp_dir = $_GESTOR['logs-path'] . 'temp_projects/';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }

    // Diretório temporário único para este upload
    $extract_dir = $temp_dir . 'upload_' . time() . '_' . uniqid() . '/';
    mkdir($extract_dir, 0755, true);

    try {
        // Mover arquivo para local temporário seguro
        $zip_path = $extract_dir . 'project.zip';
        if (!move_uploaded_file($temp_path, $zip_path)) {
            throw new Exception('Falha ao salvar arquivo temporário');
        }

        // Descompactar ZIP
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            throw new Exception('Falha ao abrir arquivo ZIP');
        }

        $zip->extractTo($extract_dir);
        $zip->close();

        // Usar a raiz do sistema como destino
        $project_path = $_GESTOR['ROOT_PATH'];

        // Encontrar o diretório do conteúdo do projeto (pode ser diretamente no extract_dir ou em um subdiretório)
        $project_content_dir = $extract_dir;
        
        // Verificar se há um único diretório dentro do extract_dir (caso comum de ZIP com diretório raiz)
        $extracted_items = array_diff(scandir($extract_dir), ['.', '..']);
        if (count($extracted_items) === 1 && is_dir($extract_dir . DIRECTORY_SEPARATOR . $extracted_items[0])) {
            $project_content_dir = $extract_dir . DIRECTORY_SEPARATOR . $extracted_items[0];
        }

        // Copiar arquivos do projeto (sobrescrever existentes na raiz)
        api_copy_directory($project_content_dir, $project_path);

        // Executar atualização de banco de dados do projeto (inline, sem shell_exec)
        api_executar_atualizacao_banco($project_path, $project_id);

        // Limpar arquivos temporários
        api_remove_directory($extract_dir);

        // Resposta de sucesso
        $response_data = [
            'file_size' => $uploaded_file['size'],
            'updated_at' => date('c'),
            'status' => 'updated'
        ];

        api_response_success($response_data, 'Projeto atualizado com sucesso');

    } catch (Exception $e) {
        // Limpar arquivos temporários em caso de erro
        if (isset($extract_dir) && is_dir($extract_dir)) {
            api_remove_directory($extract_dir);
        }

        api_response_error('Erro durante atualização do projeto: ' . $e->getMessage(), 500);
    }
}

// =========================== Handlers de Endpoint SYSTEM

function api_handle_system() {
    global $_GESTOR;

    $sub_endpoint = isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null;

    switch ($sub_endpoint) {
        case 'update':
            api_system_update();
            break;

        default:
            api_response_error('Sub-endpoint SYSTEM não encontrado: ' . $sub_endpoint, 404);
    }
}

function api_system_update() {
    global $_GESTOR;

    // Requer autenticação
    api_authenticate(true);

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    // Obter ação do POST ou da query string
    $action = $_POST['action'] ?? $_REQUEST['action'] ?? null;
    if (!$action) {
        api_response_error('Parâmetro "action" é obrigatório. Ações válidas: start, deploy, db, finalize, status, cancel', 400);
    }

    $valid_actions = ['start', 'deploy', 'db', 'finalize', 'status', 'cancel'];
    if (!in_array($action, $valid_actions)) {
        api_response_error('Ação inválida: ' . $action . '. Válidas: ' . implode(', ', $valid_actions), 400);
    }

    // Construir parâmetros para o sistema de atualização
    $params = ['action' => $action];

    // Para ação start, repassar configurações adicionais
    if ($action === 'start') {
        $param_keys = [
            'domain', 'tag', 'only_files', 'only_db', 'dry_run', 'local',
            'debug', 'no_db', 'force_all', 'log_diff', 'backup', 'no_verify',
            'download_only', 'skip_download', 'tables', 'clean_temp', 'logs_retention_days'
        ];
        foreach ($param_keys as $key) {
            $val = $_POST[$key] ?? $_REQUEST[$key] ?? null;
            if ($val !== null) {
                $params[$key] = $val;
            }
        }
        // Domínio padrão
        if (empty($params['domain'])) {
            $params['domain'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        }
    }

    // Para ações que operam em sessão, exigir sid
    if (in_array($action, ['deploy', 'db', 'finalize', 'status', 'cancel'])) {
        $sid = $_POST['sid'] ?? $_REQUEST['sid'] ?? null;
        if (!$sid) {
            api_response_error('Parâmetro "sid" (session ID) é obrigatório para a ação: ' . $action, 400);
        }
        $params['sid'] = $sid;
    }

    // Executar atualização via include do script (mesma abordagem de admin-atualizacoes)
    $result = api_call_system_update($params);

    if (isset($result['error'])) {
        $http_code = 500;
        // Erros de sessão são 400 (bad request)
        if (strpos($result['error'], 'Sessão') !== false || strpos($result['error'], 'inválida') !== false) {
            $http_code = 400;
        }
        api_response_error($result['error'], $http_code, $result);
    }

    api_response_success($result, 'Ação "' . $action . '" executada com sucesso');
}

/**
 * Chama o script atualizacoes-sistema.php simulando uma requisição web.
 * Mesma técnica utilizada por admin_atualizacoes_call_system().
 */
function api_call_system_update(array $params): array {
    global $_GESTOR;

    $script = $_GESTOR['ROOT_PATH'] . 'controladores/atualizacoes/atualizacoes-sistema.php';

    if (!is_file($script)) {
        return ['error' => 'Script de atualização não encontrado: ' . $script];
    }

    // Construir query string a partir dos parâmetros
    $query = http_build_query($params);

    // Salvar estado atual de $_GET e $_REQUEST
    $saved_get = $_GET;
    $saved_request = $_REQUEST;

    // Simular requisição web (mesma abordagem de admin-atualizacoes)
    $_GET = $_REQUEST = [];
    parse_str($query, $_GET);
    $_REQUEST = $_GET;

    // Incluir script e capturar saída JSON
    ob_start();
    try {
        include $script;
    } catch (Throwable $e) {
        ob_end_clean();
        $_GET = $saved_get;
        $_REQUEST = $saved_request;
        return ['error' => 'Exceção durante atualização: ' . $e->getMessage()];
    }
    $raw = ob_get_clean();

    // Restaurar estado
    $_GET = $saved_get;
    $_REQUEST = $saved_request;

    // Decodificar resposta JSON
    $json = json_decode($raw, true);
    if ($json === null) {
        return ['error' => 'Resposta inválida do sistema de atualização', 'raw' => substr($raw, 0, 2000)];
    }

    return $json;
}

// =========================== Funções Auxiliares para Manipulação de Arquivos

function api_executar_atualizacao_banco($project_path, $project_id = null) {
    global $_GESTOR, $_BANCO;

    // Caminho para o script de atualização de banco
    $script = $_GESTOR['ROOT_PATH'] . 'controladores/atualizacoes/atualizacoes-banco-de-dados.php';

    if (!file_exists($script)) {
        throw new Exception('Script de atualização de banco não encontrado: ' . $script);
    }

    // Configurar opções CLI para execução inline
    $cli = [
        'env-dir' => $_SERVER['SERVER_NAME'] ?? 'localhost', // domínio padrão
        'db' => [
            'host' => $_BANCO['host'],
            'name' => $_BANCO['nome'],
            'user' => $_BANCO['usuario'],
            'pass' => $_BANCO['senha'] ?? '',
        ],
        'debug' => false,
        'force-all' => false,
        'tables' => null,
        'log-diff' => false,
        'dry-run' => false,
        'project' => $project_id ?? null,
    ];

    // Definir opções globais para o script
    $GLOBALS['CLI_OPTS'] = $cli;

    // Executar o script inline (sem processo externo)
    try {
        require $script;
    } catch (Throwable $e) {
        throw new Exception('Falha na atualização de banco de dados: ' . $e->getMessage());
    }
}

function api_copy_directory($source, $destination) {
    if (!is_dir($source)) {
        return false;
    }

    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
        $destinationPath = $destination . DIRECTORY_SEPARATOR . $file;

        if (is_dir($sourcePath)) {
            api_copy_directory($sourcePath, $destinationPath);
        } else {
            copy($sourcePath, $destinationPath);
        }
    }
    closedir($dir);
    return true;
}

function api_remove_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            api_remove_directory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

// =========================== Handler OAuth Refresh

function api_oauth_refresh() {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    $data = api_get_request_body();

    // Validar refresh_token
    if (!isset($data['refresh_token']) || empty($data['refresh_token'])) {
        api_response_error('Campo "refresh_token" é obrigatório', 400);
    }

    // Incluir biblioteca OAuth2
    gestor_incluir_biblioteca('oauth2');

    // Tentar renovar tokens
    $novos_tokens = oauth2_renovar_token(Array(
        'refresh_token' => $data['refresh_token']
    ));

    if (!$novos_tokens) {
        api_response_error('Refresh token inválido ou expirado', 401);
    }

    // Retornar novos tokens
    api_response_success([
        'access_token' => $novos_tokens['access_token'],
        'token_type' => $novos_tokens['token_type'],
        'expires_in' => $novos_tokens['expires_in'],
        'refresh_token' => $novos_tokens['refresh_token'],
        'scope' => $novos_tokens['scope']
    ], 'Tokens renovados com sucesso');
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
        case 'oauth':
            // Verificar sub-endpoint OAuth
            $sub_endpoint = isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null;

            switch ($sub_endpoint) {
                case 'refresh':
                    api_oauth_refresh();
                    break;
                default:
                    // Redirecionar para o endpoint OAuth existente
                    header('Location: ' . $_GESTOR['url-raiz'] . 'oauth-authenticate/');
                    exit;
            }
            break;

        case 'status':
            api_response_success(['status' => 'API operacional', 'version' => '1.0.0']);
            break;

        case 'health':
            api_response_success(['status' => 'healthy', 'timestamp' => time()]);
            break;

        case 'project':
            api_handle_project();
            break;

        case 'system':
            api_handle_system();
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
