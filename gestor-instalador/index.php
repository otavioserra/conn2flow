<?php

// Função para enviar erros em formato JSON
function send_json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

require_once 'src/Translator.php';
require_once 'src/helpers.php';

// Se for uma requisição GET, apenas exibe o formulário
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Determina o idioma a partir do GET ou usa um padrão
    $lang = 'pt-br';
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt-br', 'en-us'])) {
        $lang = $_GET['lang'];
    }

    $translator = Translator::getInstance();
    $translator->load($lang);

    if (isset($_GET['success']) && $_GET['success'] === 'true') {
        require_once 'views/success.php';
    } else {
        require_once 'views/installer.php';
    }
    exit;
}

// A partir daqui, tratamos apenas requisições POST (AJAX)
header('Content-Type: application/json');

require_once 'src/Installer.php';

// Para requisições POST (AJAX), carrega o idioma enviado no corpo da requisição
$lang = 'pt-br';
if (isset($_POST['lang']) && in_array($_POST['lang'], ['pt-br', 'en-us'])) {
    $lang = $_POST['lang'];
}
$translator = Translator::getInstance();
$translator->load($lang);

try {
    // A ação determina qual etapa da instalação executar
    $action = $_POST['action'] ?? 'validate_input';
    
    $installer = new Installer($_POST);
    $response = $installer->runStep($action);
    
    echo json_encode($response);

} catch (Exception $e) {
    send_json_error($e->getMessage(), 500);
}
?>