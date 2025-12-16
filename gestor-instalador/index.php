<?php
/*********
	Descrição: instalador do Conn2Flow Gestor.
**********/

// ===== Força charset UTF-8 em todo o sistema

ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// ===== Definições de variáveis gerais do gestor.

$_GESTOR_INSTALADOR['versao']								=	'1.5.2'; // Versão do gestor instalador.

// Função para enviar erros em formato JSON
function send_json_error($message, $statusCode = 400, $logContent = null)
{
    http_response_code($statusCode);
    $response = ['status' => 'error', 'message' => $message];
    if ($logContent) {
        $response['log_content'] = $logContent;
    }
    echo json_encode($response);
    exit;
}

/**
 * Lê as últimas N linhas de um arquivo.
 */
function read_last_lines($filepath, $lines = 20) {
    if (!file_exists($filepath)) {
        return "Log file not found: {$filepath}";
    }
    $file = new SplFileObject($filepath, 'r');
    $file->seek(PHP_INT_MAX);
    $last_line = $file->key();
    $iterator = new LimitIterator($file, max(0, $last_line - $lines), $last_line);
    return implode('', iterator_to_array($iterator));
}

require_once __DIR__ . '/src/Translator.php';
require_once __DIR__ . '/src/helpers.php';

// Se for uma requisição GET, apenas exibe o formulário
// Suporte ao modo debug via .env.debug
$debugEnvPath = __DIR__ . DIRECTORY_SEPARATOR . '.env.debug';
function load_debug_env($envPath)
{
    if (!file_exists($envPath)) return [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $data = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $data[strtolower(trim($key))] = trim($value);
        }
    }
    return $data;
}
$debugData = load_debug_env($debugEnvPath);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($debugData)) {
        // Se .env.debug existe, exibe tela de instalação via modo debug
        require_once __DIR__ . '/views/debug.php';
        exit;
    } else {
        // Modo normal: exibe formulário
        $lang = 'pt-br';
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt-br', 'en'])) {
            $lang = $_GET['lang'];
        }
        $translator = Translator::getInstance();
        $translator->load($lang);
        if (isset($_GET['success']) && $_GET['success'] === 'true') {
            require_once __DIR__ . '/views/success.php';
        } else {
            require_once __DIR__ . '/views/installer.php';
        }
        exit;
    }
}

// A partir daqui, tratamos apenas requisições POST (AJAX)
header('Content-Type: application/json');

require_once __DIR__ . '/src/Installer.php';

// Para requisições POST (AJAX), carrega o idioma enviado no corpo da requisição
$lang = 'pt-br';
if (isset($_POST['lang']) && in_array($_POST['lang'], ['pt-br', 'en'])) {
    $lang = $_POST['lang'];
}
$translator = Translator::getInstance();
$translator->load($lang);

try {
    // Suporte ao modo debug para requisições POST
    $debugData = load_debug_env($debugEnvPath);
    $inputData = $_POST;
    if (!empty($debugData)) {
        // Preenche os campos do instalador com os dados do .env.debug
        foreach ($debugData as $key => $value) {
            $inputData[$key] = $value;
        }
        // Ativa modo debug
        $inputData['debug'] = '1';
    }

    // A ação determina qual etapa da instalação executar
    $action = $inputData['action'] ?? 'validate_input';

    // Se modo debug e SKIP_DOWNLOAD=1, força etapa após download
    if (!empty($debugData) && isset($debugData['skip_download']) && $debugData['skip_download'] == '1' && $action === 'download_files') {
        $action = 'unzip_files';
    }

    $installer = new Installer($inputData);
    $response = $installer->runStep($action);
    echo json_encode($response);

} catch (Exception $e) {
    // Log do erro para debug
    error_log("Erro no instalador: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Lê as últimas linhas do log para enviar ao cliente
    $logFile = __DIR__ . '/installer.log';
    $logContent = read_last_lines($logFile, 30);

    // Se estamos em desenvolvimento, mostra mais detalhes
    $isDev = isset($_GET['debug']) || (isset($_POST['debug']) && $_POST['debug'] === '1');
    
    if ($isDev) {
        send_json_error($e->getMessage() . " (Arquivo: " . basename($e->getFile()) . ":" . $e->getLine() . ")", 500, $logContent);
    } else {
        send_json_error($e->getMessage(), 500, $logContent);
    }
}
?>