<?php
/*********
	Descrição: instalador do Conn2Flow Gestor.
**********/

// ===== Força charset UTF-8 em todo o sistema

ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

require_once __DIR__ . '/src/InstallerGuard.php';
require_once __DIR__ . '/src/Translator.php';
require_once __DIR__ . '/src/helpers.php';

// ===== Definições de variáveis gerais do gestor.

$_GESTOR_INSTALADOR['versao']								=	InstallerGuard::VERSION; // Versão do gestor instalador (2.1.1).

// ===== Sonda de rewrite: responde antes da sessão e da trava para que o instalador
// possa confirmar que o servidor web injeta `_gestor-caminho` no front-controller.
if (isset($_REQUEST['_gestor-caminho'])
    && (string)$_REQUEST['_gestor-caminho'] === InstallerGuard::REWRITE_PROBE) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo InstallerGuard::REWRITE_PROBE_OK;
    exit;
}

$installerHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
session_name('C2F_INSTALLER');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $installerHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$installerBaseDir = __DIR__;
$installerLockPath = InstallerGuard::lockPath($installerBaseDir);
if (!isset($_SESSION['conn2flow_install_token'])) {
    $_SESSION['conn2flow_install_token'] = bin2hex(random_bytes(32));
}
$installToken = $_SESSION['conn2flow_install_token'];

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

/** Endereço da própria tela do instalador, usado nos redirecionamentos internos. */
function installer_self_url($lang)
{
    $self = strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?');
    $self = preg_replace('#[^A-Za-z0-9_./~-]#', '', (string)$self);
    if ($self === '') $self = '/';

    return $self . '?lang=' . urlencode((string)$lang);
}

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

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

// Carrega o idioma antes dos guards de segurança para que as telas de bloqueio sejam traduzidas.
$lang = 'pt-br';
$langSolicitado = (string)($isPost ? ($_POST['lang'] ?? '') : ($_GET['lang'] ?? ''));
if (in_array($langSolicitado, ['pt-br', 'en'], true)) {
    $lang = $langSolicitado;
}
$translator = Translator::getInstance();
$translator->load($lang);

$detectedWebServer = InstallerGuard::detectWebServer();

// ===== Chave de segurança pré-instalação
// Impede que terceiros executem o instalador em um servidor recém-provisionado.
// O modo debug local pode dispensar a chave declarando SKIP_SECURITY_KEY=1 no .env.debug.
$skipSecurityKey = isset($debugData['skip_security_key']) && (string)$debugData['skip_security_key'] === '1';
$installUnlocked = $skipSecurityKey || !empty($_SESSION['conn2flow_install_unlocked']);
$unlockError = '';

if (!$installUnlocked) {
    $installKeyPath = InstallerGuard::keyPath($installerBaseDir);
    $installKeyGerada = !is_file($installKeyPath);
    InstallerGuard::ensureKey($installerBaseDir);

    if ($isPost && ($_POST['action'] ?? '') === 'unlock_installer') {
        if (InstallerGuard::validateKey($installerBaseDir, $_POST['install_key'] ?? '')) {
            session_regenerate_id(true);
            $_SESSION['conn2flow_install_unlocked'] = true;
            header('Location: ' . installer_self_url($lang), true, 303);
            exit;
        }
        $unlockError = __('security_key_invalid', 'Chave de segurança inválida.');
    } elseif ($isPost) {
        header('Content-Type: application/json');
        send_json_error(__('security_key_required', 'Informe a chave de segurança do instalador antes de continuar.'), 403);
    }

    require_once __DIR__ . '/views/unlock.php';
    exit;
}

// ===== Trava de execução concorrente
// Somente a sessão dona da trava conduz a instalação; as demais recebem HTTP 423.
if (!InstallerGuard::lockAcquire($installerLockPath, $installToken)) {
    $mensagemTrava = __('installer_locked', 'Uma instalação já está em andamento neste servidor. Aguarde a conclusão.');
    if ($isPost) {
        header('Content-Type: application/json');
        send_json_error($mensagemTrava, 423);
    }
    http_response_code(423);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '"><head><meta charset="UTF-8">'
        . '<title>' . htmlspecialchars(__('installer_locked_title', 'Instalador bloqueado'), ENT_QUOTES, 'UTF-8') . '</title></head>'
        . '<body style="font-family:sans-serif;max-width:640px;margin:80px auto;text-align:center">'
        . '<h1>' . htmlspecialchars(__('installer_locked_title', 'Instalador bloqueado'), ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p>' . htmlspecialchars($mensagemTrava, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</body></html>';
    exit;
}

// ===== API de pré-requisitos do instalador (REQ-027)
// Rota canônica: `gestor-instalador/api/rewrite-probe`. Ela depende do próprio rewrite,
// que é justamente o que a sonda diagnostica, então `?api=rewrite-probe` é aceito como
// caminho determinístico — sempre alcançável pelo front-controller do instalador.
$apiRoute = InstallerGuard::resolveApiRoute($_REQUEST, $_SERVER);
if ($apiRoute === InstallerGuard::API_REWRITE_PROBE) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');

    $apiToken = (string)($_REQUEST['install_token'] ?? '');
    if ($apiToken === '' || !hash_equals($installToken, $apiToken)) {
        send_json_error(__('error_install_token_invalid', 'Token do instalador inválido.'), 403);
    }

    require_once __DIR__ . '/src/Installer.php';

    // O modo debug preenche o que o formulário ainda não informou (domínio, servidor web).
    $apiInput = $_REQUEST;
    foreach ($debugData as $debugKey => $debugValue) {
        if (!isset($apiInput[$debugKey])) $apiInput[$debugKey] = $debugValue;
    }

    // O navegador é quem executa a sonda HTTP; o PHP apenas registra o veredito.
    $apiOpcoes = [];
    if (isset($_REQUEST['rewrite_ok']) && (string)$_REQUEST['rewrite_ok'] !== '') {
        $apiOpcoes['rewrite_ok'] = in_array((string)$_REQUEST['rewrite_ok'], ['1', 'true', 'ok'], true);
    }

    try {
        $apiInstaller = new Installer($apiInput);
        echo json_encode(
            $apiInstaller->rewriteProbeReport($apiOpcoes),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    } catch (Exception $e) {
        send_json_error($e->getMessage(), 500);
    }
    exit;
}

// Se for uma requisição GET, apenas exibe o formulário
if (!$isPost) {
    if (!empty($debugData)) {
        // Se .env.debug existe, exibe tela de instalação via modo debug
        require_once __DIR__ . '/views/debug.php';
        exit;
    } else {
        // Modo normal: exibe formulário
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

    $postedToken = (string)($inputData['install_token'] ?? '');
    if ($postedToken === '' || !hash_equals($installToken, $postedToken)) {
        send_json_error(__('error_install_token_invalid', 'Token do instalador inválido.'), 403);
    }
    if (!InstallerGuard::lockOwner($installerLockPath, $postedToken)) {
        send_json_error(__('installer_locked', 'Uma instalação já está em andamento neste servidor. Aguarde a conclusão.'), 423);
    }
    // Renova o carimbo de atividade para que a trava não expire durante etapas longas.
    InstallerGuard::lockTouch($installerLockPath, $postedToken);

    // A ação determina qual etapa da instalação executar
    $action = $inputData['action'] ?? 'validate_input';

    if ($action === 'validate_input' && !empty($inputData['install_path'])) {
        $installedPath = rtrim((string)$inputData['install_path'], DIRECTORY_SEPARATOR . '/');
        if (is_file($installedPath . DIRECTORY_SEPARATOR . 'gestor.php')
            && is_file($installedPath . DIRECTORY_SEPARATOR . 'config.php')) {
            send_json_error('Este ambiente já está instalado. A reexecução do instalador foi bloqueada.', 409);
        }
    }

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
