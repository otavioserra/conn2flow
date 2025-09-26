<?php

global $_GESTOR;

$_GESTOR['modulo-id'] = 'admin-environment';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-environment.json'), true);

// ===== Interfaces Auxiliares

function admin_environment_env_read(){
    global $_GESTOR;
    
    // Usar as variáveis já carregadas pelo config.php via $_ENV
    // Isso evita conflito com a classe Dotenv que já foi usada no config.php
    $envData = [
        'USUARIO_RECAPTCHA_ACTIVE' => $_ENV['USUARIO_RECAPTCHA_ACTIVE'] ?? 'false',
        'USUARIO_RECAPTCHA_SITE' => $_ENV['USUARIO_RECAPTCHA_SITE'] ?? '',
        'USUARIO_RECAPTCHA_SERVER' => $_ENV['USUARIO_RECAPTCHA_SERVER'] ?? '',
        'EMAIL_ACTIVE' => $_ENV['EMAIL_ACTIVE'] ?? 'false',
        'EMAIL_HOST' => $_ENV['EMAIL_HOST'] ?? '',
        'EMAIL_USER' => $_ENV['EMAIL_USER'] ?? '',
        'EMAIL_PASS' => $_ENV['EMAIL_PASS'] ?? '',
        'EMAIL_SECURE' => $_ENV['EMAIL_SECURE'] ?? 'false',
        'EMAIL_PORT' => $_ENV['EMAIL_PORT'] ?? '587',
        'EMAIL_FROM' => $_ENV['EMAIL_FROM'] ?? '',
        'EMAIL_FROM_NAME' => $_ENV['EMAIL_FROM_NAME'] ?? '',
        'EMAIL_REPLY_TO' => $_ENV['EMAIL_REPLY_TO'] ?? '',
        'EMAIL_REPLY_TO_NAME' => $_ENV['EMAIL_REPLY_TO_NAME'] ?? '',
        'LANGUAGE_DEFAULT' => $_ENV['LANGUAGE_DEFAULT'] ?? ''
    ];
    
    return $envData;
}

function admin_environment_env_write($data){
    global $_GESTOR;
    
    // Caminho do arquivo .env
    $envPath = $_GESTOR['AUTH_PATH_SERVER'] . '.env';
    
    if(file_exists($envPath)){
        // Ler conteúdo atual
        $content = file_get_contents($envPath);
        $lines = explode("\n", $content);
        
        // Atualizar linhas específicas
        foreach($lines as $key => $line){
            $line = trim($line);
            
            // Pular comentários e linhas vazias
            if(empty($line) || strpos($line, '#') === 0){
                continue;
            }
            
            // Procurar pela variável
            foreach($data as $varName => $varValue){
                if(strpos($line, $varName . '=') === 0){
                    // Verificar se o valor precisa de aspas (contém espaços ou caracteres especiais)
                    $formattedValue = admin_environment_env_format_value($varValue);
                    $lines[$key] = $varName . '=' . $formattedValue;
                    unset($data[$varName]);
                    break;
                }
            }
        }
        
        // Adicionar novas variáveis se não existirem
        if(!empty($data)){
            $lines[] = '';
            $lines[] = '# ===== Novas configurações adicionadas pelo Admin Environment';
            foreach($data as $varName => $varValue){
                $formattedValue = admin_environment_env_format_value($varValue);
                $lines[] = $varName . '=' . $formattedValue;
            }
        }
        
        // Salvar arquivo
        $newContent = implode("\n", $lines);
        file_put_contents($envPath, $newContent);
        
        return true;
    }
    
    return false;
}

function admin_environment_test_recaptcha($token, $serverKey){
    global $_GESTOR;

    // Verificar o token com a API do Google reCAPTCHA v3
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $serverKey,
        'response' => $token
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $arrResponse = json_decode($response, true);
    
    // Verificar se a resposta foi bem-sucedida
    if ($arrResponse['success'] && isset($arrResponse['score'])) {
        $score = $arrResponse['score'];
        $action = $arrResponse['action'] ?? '';
        
        // Para teste, consideramos sucesso se score >= 0.5
        if ($score >= 0.5) {
            return [
                'success' => true,
                'score' => $score,
                'action' => $action,
                'message' => str_replace('{score}', $score, gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-test-success')))
            ];
        } else {
            return [
                'success' => false,
                'score' => $score,
                'action' => $action,
                'message' => str_replace('{score}', $score, gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-test-low-score')))
            ];
        }
    }

    // Se não foi sucesso, verificar códigos de erro
    $errorCodes = $arrResponse['error-codes'] ?? [];
    $errorMessage = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-error-generic'));
    
    if (in_array('missing-input-secret', $errorCodes)) {
        $errorMessage = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-error-missing-secret'));
    } elseif (in_array('invalid-input-secret', $errorCodes)) {
        $errorMessage = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-error-invalid-secret'));
    } elseif (in_array('missing-input-response', $errorCodes)) {
        $errorMessage = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-error-missing-response'));
    } elseif (in_array('invalid-input-response', $errorCodes)) {
        $errorMessage = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-error-invalid-response'));
    } elseif (in_array('timeout-or-duplicate', $errorCodes)) {
        $errorMessage = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-error-timeout-duplicate'));
    }
    
    return [
        'success' => false,
        'error_codes' => $errorCodes,
        'message' => $errorMessage
    ];
}

function admin_environment_test_email($config){
    global $_GESTOR;
    
    // Enviar email de teste
    gestor_incluir_biblioteca('comunicacao');

    $default = [
        'destinatarios' => [
            [
                'email' => $config['EMAIL_FROM'],
                'nome' => $config['EMAIL_FROM_NAME']
            ]
        ],
        'mensagem' => [
            'assunto' => 'Teste de Email - Admin Environment - nº ' . date('YmdHis'),
            'html' => '<h1>Teste de Configuração de Email</h1><p>Este é um email de teste enviado pelo módulo Admin Environment.</p><p>Data/Hora: ' . date('d/m/Y H:i:s') . '</p>'
        ]
    ];

    $default = array_merge($default, $config);

    return comunicacao_email($default);
}

function admin_environment_env_format_value($value){
    // Se o valor contém espaços, aspas simples, aspas duplas ou outros caracteres especiais, envolve em aspas duplas
    if (preg_match('/[\s\'"\\\\]/', $value)) {
        // Escapar aspas duplas dentro do valor
        $value = str_replace('"', '\\"', $value);
        return '"' . $value . '"';
    }
    
    // Para valores booleanos e numéricos simples, manter sem aspas
    if ($value === 'true' || $value === 'false' || is_numeric($value)) {
        return $value;
    }
    
    // Para outros valores simples, manter sem aspas
    return $value;
}

// ===== Interfaces Principais

function admin_environment_raiz(){
    global $_GESTOR;

    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    // ===== Ler configurações atuais do .env
    
    $envData = admin_environment_env_read();

    // ===== Preparar dados para o template
    
    $dados = [
        'usuario_recaptcha_active' => $envData['USUARIO_RECAPTCHA_ACTIVE'] ?? 'false',
        'usuario_recaptcha_site' => $envData['USUARIO_RECAPTCHA_SITE'] ?? '',
        'usuario_recaptcha_server' => $envData['USUARIO_RECAPTCHA_SERVER'] ?? '',
        'email_active' => $envData['EMAIL_ACTIVE'] ?? 'false',
        'email_host' => $envData['EMAIL_HOST'] ?? '',
        'email_user' => $envData['EMAIL_USER'] ?? '',
        'email_pass' => $envData['EMAIL_PASS'] ?? '',
        'email_secure' => $envData['EMAIL_SECURE'] ?? 'false',
        'email_port' => $envData['EMAIL_PORT'] ?? '587',
        'email_from' => $envData['EMAIL_FROM'] ?? '',
        'email_from_name' => $envData['EMAIL_FROM_NAME'] ?? '',
        'email_reply_to' => $envData['EMAIL_REPLY_TO'] ?? '',
        'email_reply_to_name' => $envData['EMAIL_REPLY_TO_NAME'] ?? '',
        'language_default' => $envData['LANGUAGE_DEFAULT'] ?? 'pt-br'
    ];

    // ===== Gerar opções de idioma
    
    $languageOptions = '';
    foreach ($modulo['systemLanguages'] as $lang) {
        $label = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'language-label-' . $lang));
        $selected = ($dados['language_default'] == $lang) ? ' selected' : '';
        $languageOptions .= '<option value="' . $lang . '"' . $selected . '>' . $label . '</option>';
    }
    $dados['language-options'] = $languageOptions;

    // ===== Inclusão do CodeMirror
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/theme/tomorrow-night-bright.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.css" />';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/selection/active-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/searchcursor.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/search.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/scroll/annotatescrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/jump-to-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/edit/matchbrackets.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/xml/xml.js"></script>';

    // ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
    
    // ===== Passar dados para o template
    
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#usuario-recaptcha-active#', $dados['usuario_recaptcha_active']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#usuario-recaptcha-active-checked#', $dados['usuario_recaptcha_active'] === 'true' ? 'checked' : '');
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#usuario-recaptcha-site#', $dados['usuario_recaptcha_site']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#usuario-recaptcha-server#', $dados['usuario_recaptcha_server']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-active#', $dados['email_active']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-active-checked#', $dados['email_active'] === 'true' ? 'checked' : '');
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-host#', $dados['email_host']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-user#', $dados['email_user']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-pass#', $dados['email_pass']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-secure#', $dados['email_secure']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-secure-checked#', $dados['email_secure'] === 'true' ? 'checked' : '');
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-port#', $dados['email_port']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-from#', $dados['email_from']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-from-name#', $dados['email_from_name']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-reply-to#', $dados['email_reply_to']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#email-reply-to-name#', $dados['email_reply_to_name']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#language-options#', $dados['language-options']);
}

function admin_environment_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function admin_environment_ajax_opcao(){
    global $_GESTOR;

    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    // ===== Lógica

    $payload = [];

    // ===== Dados de Retorno

    if(true){
        $_GESTOR['ajax-json'] = Array(
            'payload' => $payload,
            'status' => 'Ok',
        );
    } else {
        $_GESTOR['ajax-json'] = Array(
            'error' => 'Error msg'
        );
    }
}

function admin_environment_ajax_salvar(){
    global $_GESTOR;
    
    $data = [];
    
    // Coletar dados do formulário
    if(isset($_REQUEST['usuario_recaptcha_active'])) $data['USUARIO_RECAPTCHA_ACTIVE'] = $_REQUEST['usuario_recaptcha_active'];
    if(isset($_REQUEST['usuario_recaptcha_site'])) $data['USUARIO_RECAPTCHA_SITE'] = $_REQUEST['usuario_recaptcha_site'];
    if(isset($_REQUEST['usuario_recaptcha_server'])) $data['USUARIO_RECAPTCHA_SERVER'] = $_REQUEST['usuario_recaptcha_server'];
    if(isset($_REQUEST['email_active'])) $data['EMAIL_ACTIVE'] = $_REQUEST['email_active'];
    if(isset($_REQUEST['email_host'])) $data['EMAIL_HOST'] = $_REQUEST['email_host'];
    if(isset($_REQUEST['email_user'])) $data['EMAIL_USER'] = $_REQUEST['email_user'];
    if(isset($_REQUEST['email_pass'])) $data['EMAIL_PASS'] = $_REQUEST['email_pass'];
    if(isset($_REQUEST['email_secure'])) $data['EMAIL_SECURE'] = $_REQUEST['email_secure'];
    if(isset($_REQUEST['email_port'])) $data['EMAIL_PORT'] = $_REQUEST['email_port'];
    if(isset($_REQUEST['email_from'])) $data['EMAIL_FROM'] = $_REQUEST['email_from'];
    if(isset($_REQUEST['email_from_name'])) $data['EMAIL_FROM_NAME'] = $_REQUEST['email_from_name'];
    if(isset($_REQUEST['email_reply_to'])) $data['EMAIL_REPLY_TO'] = $_REQUEST['email_reply_to'];
    if(isset($_REQUEST['email_reply_to_name'])) $data['EMAIL_REPLY_TO_NAME'] = $_REQUEST['email_reply_to_name'];
    if(isset($_REQUEST['language_default'])) $data['LANGUAGE_DEFAULT'] = $_REQUEST['language_default'];
    
    // Salvar no .env
    $success = admin_environment_env_write($data);
    
    $_GESTOR['ajax-json'] = [
        'status' => $success ? 'success' : 'error',
        'message' => $success ? 'Configurações salvas com sucesso!' : 'Erro ao salvar configurações.'
    ];
}

function admin_environment_ajax_testar_recaptcha(){
    global $_GESTOR;
    
    $token = $_REQUEST['recaptcha_token'] ?? '';
    $serverKey = $_REQUEST['server_key'] ?? '';
    
    if (empty($token) || empty($serverKey)) {
        $_GESTOR['ajax-json'] = [
            'status' => 'error',
            'message' => 'Token ou Server Key do reCAPTCHA não fornecidos.'
        ];
        return;
    }
    
    $result = admin_environment_test_recaptcha($token, $serverKey);
    
    $_GESTOR['ajax-json'] = [
        'status' => $result['success'] ? 'success' : 'error',
        'message' => $result['message']
    ];
}

function admin_environment_ajax_testar_email(){
    global $_GESTOR;
    
    $config = [
        'EMAIL_TESTS' => true,
        'EMAIL_DEBUG' => $_REQUEST['email_debug'] ? ($_REQUEST['email_debug'] === 'true'? true : false) : false,
        'EMAIL_HOST' => $_REQUEST['email_host'] ?? '',
        'EMAIL_USER' => $_REQUEST['email_user'] ?? '',
        'EMAIL_PASS' => $_REQUEST['email_pass'] ?? '',
        'EMAIL_SECURE' => $_REQUEST['email_secure'] ? ($_REQUEST['email_secure'] === 'true'? true : false) : false,
        'EMAIL_PORT' => $_REQUEST['email_port'] ?? '587',
        'EMAIL_FROM' => $_REQUEST['email_from'] ?? '',
        'EMAIL_FROM_NAME' => $_REQUEST['email_from_name'] ?? '',
        'EMAIL_REPLY_TO' => $_REQUEST['email_reply_to'] ?? '',
        'EMAIL_REPLY_TO_NAME' => $_REQUEST['email_reply_to_name'] ?? ''
    ];

    if($config['EMAIL_DEBUG']) {
        ob_start();
    }
    
    $success = admin_environment_test_email($config);
    
    if($config['EMAIL_DEBUG']) {
        $debugMsgs = ob_get_clean();
        // Aplica os filtros
        $debugMsgs = html_entity_decode($debugMsgs);
        $debugMsgs = str_replace(['<br>', '<br/>', '<br />'], '', $debugMsgs);

        // Exibe o conteúdo filtrado
        echo "############### ".gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-head-title'))." ###############\n";
        echo $debugMsgs."\n\n############### ".gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-head-status'))." ###############\n";
        echo $success ? gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-success-msg'))."\n" : gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-error-msg'))."\n";
        echo $success ? gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-success-description'))."\n" : gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-error-description'))."\n";
        echo "############### ".gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-head-bottom'))." ###############";
        exit;
    } else {
        $_GESTOR['ajax-json'] = [
            'status' => $success ? 'success' : 'error',
            'message' => $success ? gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-success-description')) : gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-error-description'))
        ];
    }
}

// ==== Start

function admin_environment_start(){
    global $_GESTOR;

    gestor_incluir_bibliotecas();

    if($_GESTOR['ajax']){
        interface_ajax_iniciar();

        switch($_GESTOR['ajax-opcao']){
            case 'opcao': admin_environment_ajax_opcao(); break;
            case 'salvar': admin_environment_ajax_salvar(); break;
            case 'testar-recaptcha': admin_environment_ajax_testar_recaptcha(); break;
            case 'testar-email': admin_environment_ajax_testar_email(); break;
        }

        interface_ajax_finalizar();
    } else {
        admin_environment_interfaces_padroes();

        interface_iniciar();

        switch($_GESTOR['opcao']){
            case 'raiz': admin_environment_raiz(); break;
        }

        interface_finalizar();
    }
}

admin_environment_start();

?>
