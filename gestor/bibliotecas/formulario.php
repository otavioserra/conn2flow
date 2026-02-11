<?php
/**
 * Biblioteca de validação e processamento de formulários.
 *
 * Fornece funções para validação client-side e server-side de formulários,
 * incluindo regras personalizadas, campos obrigatórios, validação de email,
 * integração com Google reCAPTCHA v2 e v3.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.4
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-formulario']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

/**
 * Inclui JavaScript da biblioteca de formulários na página.
 *
 * Carrega o arquivo JavaScript necessário para validações client-side.
 * Usa controle para incluir apenas uma vez por requisição.
 * 
 * @param array|false $params Parâmetros da função.
 * 
 * @param array $params['js_vars'] Array de variáveis JavaScript a serem incluídas (opcional).
 * 
 * @return void
 */
function formulario_incluir_js($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Variáveis padrões 
	$js_padroes = [];

	// Mesclar variáveis personalizadas com as padrões
	if(isset($js_vars) && is_array($js_vars)){
		$js_padroes = array_merge($js_padroes, $js_vars);
	}

	// Incluir variáveis JS para a página
	gestor_js_variavel_incluir('form',$js_padroes);

	// Incluir o JavaScript da biblioteca de formulários
	gestor_pagina_javascript_incluir('biblioteca','formulario');
}

// ===== Funções principais

/**
 * Controlador de Formulários.
 *
 * Função controlado de formulários lida com controlador do backend e frontend, processamento de dados, integração com banco de dados, etc.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['formId'] ID do formulário (obrigatório).
 * @param string $params['formAction'] Ação do formulário (opcional).
 * @param string $params['formAjaxOpcao'] Opção AJAX do formulário (opcional).
 * 
 * @return void
 */
function formulario_controlador($params = false){
	global $_GESTOR;
	global $_CONFIG;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;

	if(isset($formId)){
        // ===== Limpeza automática aleatória (~1% das requisições)
        if(rand(0, 100) == 0){
            formulario_acessos_limpeza();
        }

		// ==== Pegar o componente do formulário.
		$form_ui = gestor_componente([
			'id' => 'form-ui'
		]);

		// ==== Extrair valores do componente HTML
		$form_ui_cel = [];

		// Extrair prompts
		$cel_nome = 'prompts';
		$form_ui_cel[$cel_nome] = modelo_tag_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$form_ui = modelo_tag_troca_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

		// Extrair ui-texts
		$cel_nome = 'ui-texts';
		$form_ui_cel[$cel_nome] = modelo_tag_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$form_ui = modelo_tag_troca_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

		// Extrair ui-components
		$cel_nome = 'ui-components';
		$form_ui_cel[$cel_nome] = modelo_tag_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$form_ui = modelo_tag_troca_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

		// Extrair block-wrapper
		$cel_nome = 'block-wrapper';
		$form_ui_cel[$cel_nome] = modelo_tag_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$form_ui = modelo_tag_troca_val($form_ui,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

		// Processar valores individuais dos prompts
		$form_ui_prompts = [];
		if(isset($form_ui_cel['prompts'])){
			$form_ui_prompts['empty'] = modelo_tag_val($form_ui_cel['prompts'], '<div class="prompt-empty">', '</div>');
			$form_ui_prompts['email'] = modelo_tag_val($form_ui_cel['prompts'], '<div class="prompt-email">', '</div>');
		}

		// Processar valores individuais dos ui-texts
		$form_ui_texts = [];
		if(isset($form_ui_cel['ui-texts'])){
			$form_ui_texts['loading'] = modelo_tag_val($form_ui_cel['ui-texts'], '<div class="ui-text-loading">', '</div>');
			$form_ui_texts['timeoutError'] = modelo_tag_val($form_ui_cel['ui-texts'], '<div class="ui-text-timeout-error">', '</div>');
			$form_ui_texts['generalError'] = modelo_tag_val($form_ui_cel['ui-texts'], '<div class="ui-text-general-error">', '</div>');
			$form_ui_texts['requireV2Message'] = modelo_tag_val($form_ui_cel['ui-texts'], '<div class="ui-text-require-v2-message">', '</div>');
		}

		// Processar valores individuais dos ui-components
		$form_ui_components = [];
		if(isset($form_ui_cel['ui-components'])){
			$form_ui_components['dimmerFomantic'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- dimmerFomantic -->', '<!-- /dimmerFomantic -->');
			$form_ui_components['dimmerTailwind'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- dimmerTailwind -->', '<!-- /dimmerTailwind -->');
			$form_ui_components['errorElement'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- errorElement -->', '<!-- /errorElement -->');
			$form_ui_components['errorElementFomantic'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- errorElementFomantic -->', '<!-- /errorElementFomantic -->');
			$form_ui_components['errorElementTailwind'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- errorElementTailwind -->', '<!-- /errorElementTailwind -->');
			$form_ui_components['recaptchaV2'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- recaptchaV2 -->', '<!-- /recaptchaV2 -->');
			$form_ui_components['formDisabled'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- formDisabled -->', '<!-- /formDisabled -->');
		}

		// Processar block-wrapper
		$form_ui_block_wrapper = '';
		if(isset($form_ui_cel['block-wrapper'])){
			$form_ui_block_wrapper = modelo_tag_val($form_ui_cel['block-wrapper'], '<!-- blockWrapper -->', '<!-- /blockWrapper -->');
		}

        // ===== Verificar a permissão do acesso.
        $acesso = formulario_acesso_verificar(['tipo' => $formId]);

        // ===== Devolver mensagem de bloqueio caso o IP esteja bloqueado, senão incluir o formulário normalmente.
        if(!$acesso['permitido']){
            $blockWrapper = $form_ui_block_wrapper;
        }

		// ===== Buscar definição do formulário na tabela forms
        $formDefinition = banco_select(Array(
            'unico' => true,
            'tabela' => 'forms',
            'campos' => Array(
                'fields_schema',
                'status',
            ),
            'extra' => "WHERE id='$formId' AND language='".$_GESTOR['linguagem-codigo']."'"
        ));

        // ==== Extrair campos e redirects do schema do formulário para passar para o JS
        $fieldsDoJson = [];
        $redirectsDoJson = [];
        $formStatus = 'A'; // Padrão ativo
        $forceRecaptchaV3 = false; // Padrão
        if($formDefinition){
            $schema = json_decode($formDefinition['fields_schema'], true);
            if(isset($schema['fields'])){
                $fieldsDoJson = $schema['fields'];
            }
            if(isset($schema['redirects'])){
                $redirectsDoJson = $schema['redirects'];
            }
            if(isset($schema['force_recaptcha']) && $schema['force_recaptcha'] === true){
                $forceRecaptchaV3 = true;
            }
            $formStatus = $formDefinition['status'];
        }

        // ===== Incluir google reCAPTCHA caso ativo (v3 se status != 'livre' ou forçado)
        if(isset($_CONFIG['usuario-recaptcha-active']) && ($acesso['status'] != 'livre' || $forceRecaptchaV3)){
            if($_CONFIG['usuario-recaptcha-active']){
                $googleRecaptchaActive = true;
                $googleRecaptchaSite = $_CONFIG['usuario-recaptcha-site'];
                $googleRecaptchaAction = $formId . '-action';
                
                gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$googleRecaptchaSite.'"></script>');
            }
        }

        // ===== Incluir google reCAPTCHA v2 caso ativo
        if(isset($_CONFIG['usuario-recaptcha-v2-active']) && $_CONFIG['usuario-recaptcha-v2-active']){
            $googleRecaptchaV2Active = true;
            $googleRecaptchaV2Site = $_CONFIG['usuario-recaptcha-v2-site'];
        }
        
        // ===== Inclusão Módulo JS
        
        gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
        gestor_pagina_javascript_incluir();
        
        // ===== Fallback para redirects padrão se não definidos
        if(empty($redirectsDoJson)){
            $redirectsDoJson = [
                'success' => ['path' => '/sucesso/', 'type' => 'url'],
                'error' => ['path' => '/erro/', 'type' => 'url']
            ];
        }
        
        // ===== Incluir o JS
        $js_vars = [
            'formId' => $formId,
            'formAction' => $formAction ?? $_GESTOR['url-raiz'] . 'forms-submissions-process/',
            'formStatus' => $formStatus,
            'ajaxOpcao' => $formAjaxOpcao ?? 'forms-process',
			'serverTimestamp' => time(),
            'blockWrapper' => $blockWrapper ?? null,
            'googleRecaptchaActive' => $googleRecaptchaActive ?? null,
            'googleRecaptchaSite' => $googleRecaptchaSite ?? null,
            'googleRecaptchaAction' => $googleRecaptchaAction ?? null,
            'googleRecaptchaV2Active' => $googleRecaptchaV2Active ?? null,
            'googleRecaptchaV2Site' => $googleRecaptchaV2Site ?? null,
            'framework' => str_replace(['fomantic-ui', 'tailwindcss'], ['fomantic', 'tailwind'], $_GESTOR['pagina#framework_css']),
            'fields' => $fieldsDoJson,
            'prompts' => $form_ui_prompts,
            'redirects' => $redirectsDoJson,
            'ui' => [
                'texts' => $form_ui_texts,
                'components' => $form_ui_components,
            ],
        ];
        formulario_incluir_js(['js_vars' => $js_vars]);
    }
}

/**
 * Processador de Formulários.
 *
 * Função processador de formulários no backend, processamento de dados, integração com banco de dados, etc.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * 
 * @return void
 */
function formulario_processador($params = false){
	global $_GESTOR;
	global $_CONFIG;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;

	// ==== Extrair mensagens AJAX do componente HTML
	$form_ui_ajax = gestor_componente([
		'id' => 'form-ui'
	]);

	$form_ui_ajax_cel = modelo_tag_val($form_ui_ajax,'<!-- ajax-messages < -->','<!-- ajax-messages > -->');

	$form_ui_ajax_messages = [];
	if($form_ui_ajax_cel){
		$form_ui_ajax_messages['formIdMissing'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-form-id-missing">', '</div>');
		$form_ui_ajax_messages['suspectedBot'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-suspected-bot">', '</div>');
		$form_ui_ajax_messages['requestExpired'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-request-expired">', '</div>');
		$form_ui_ajax_messages['captchaFailed'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-captcha-failed">', '</div>');
		$form_ui_ajax_messages['captchaV2Failed'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-captcha-v2-failed">', '</div>');
		$form_ui_ajax_messages['formDisabled'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-form-disabled">', '</div>');
		$form_ui_ajax_messages['requiredField'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-required-field">', '</div>');
		$form_ui_ajax_messages['minLength'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-min-length">', '</div>');
		$form_ui_ajax_messages['invalidEmail'] = modelo_tag_val($form_ui_ajax_cel, '<div class="ajax-message-invalid-email">', '</div>');
	}

	$formId = $_REQUEST['_formId'] ?? null;

	if(!$formId){
		// Retorno do AJAX em caso de formId ausente
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['formIdMissing'] ?? 'Form ID missing.',
		);
		return;
	}

	// Sanitização do formId para evitar SQL Injection (mesmo que seja usado como parâmetro em funções seguras, é uma boa prática)
	$formId = banco_escape_field($formId);
	
	// ===== Validar honeypot (anti-bot)
	if(!empty($_REQUEST['honeypot'])){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['suspectedBot'] ?? 'Suspected bot activity.',
		);
		return;
	}
	
	// ===== Validar timestamp (anti-replay, max 2 dias)
	$timestamp = $_REQUEST['timestamp'] ?? 0;
	$limite = 172800; // 2 dias
	if(time() - $timestamp > $limite){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['requestExpired'] ?? 'Request expired.',
		);
		return;
	}
	
	// ===== Verificar acesso e status
	$acesso = formulario_acesso_verificar(['tipo' => $formId]);
	
	// ===== Rate limiting e CAPTCHA
	$captchaV2Ativo = false;

	// ===== Buscar definição do formulário na tabela forms
    $formDefinition = banco_select(Array(
        'unico' => true,
        'tabela' => 'forms',
        'campos' => Array(
            'fields_schema',
            'status',
        ),
        'extra' => "WHERE id='$formId' AND language='".$_GESTOR['linguagem-codigo']."'"
    ));

	// Flag para forçar v3 (do schema)
	$forceRecaptchaV3 = false; // Padrão
	if($formDefinition){
        $schema = json_decode($formDefinition['fields_schema'], true);

		if(isset($schema['force_recaptcha']) && $schema['force_recaptcha'] === true){
			$forceRecaptchaV3 = true;
		}
	}
	
	$recaptchaValido = false;
	if(isset($_CONFIG['usuario-recaptcha-active']) && ($acesso['status'] != 'livre' || $forceRecaptchaV3)){
		$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
		$token = $_REQUEST['token'] ?? null;
		$action = $_REQUEST['action'] ?? null;
		
		// Chamada reCAPTCHA v3
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $recaptchaSecretKey, 'response' => $token]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$arrResponse = json_decode($response, true);
		
		if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5){
			$recaptchaValido = true;
		} elseif($arrResponse["score"] < 0.5 && isset($_CONFIG['usuario-recaptcha-v2-active']) && $_CONFIG['usuario-recaptcha-v2-active']){
			$captchaV2Ativo = true;
		}
	} else {
		$recaptchaValido = true;
	}
	
	if(!$recaptchaValido && !$captchaV2Ativo){
		formulario_acesso_falha(['tipo' => $formId]);
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['captchaFailed'] ?? 'CAPTCHA validation failed.',
		);
		return;
	}
	
	if($captchaV2Ativo){
		// Verificar se v2 foi enviado
		$recaptchaV2Response = $_REQUEST['g-recaptcha-response'] ?? null;
		if(!$recaptchaV2Response){
			formulario_acesso_falha(['tipo' => $formId]);
			$_GESTOR['ajax-json'] = Array(
				'status' => 'require_v2',
			);
			return;
		}
		
		// Validar v2
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $_CONFIG['usuario-recaptcha-server-v2'], 'response' => $recaptchaV2Response]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$arrResponse = json_decode($response, true);
		
		if(!$arrResponse["success"]){
			formulario_acesso_falha(['tipo' => $formId]);
			$_GESTOR['ajax-json'] = Array(
				'status' => 'error',
				'message' => $form_ui_ajax_messages['captchaV2Failed'] ?? 'CAPTCHA v2 validation failed.',
			);
			return;
		}
	}

	// ===== Verificar pegar dados do formulário para validação e armazenamento
    $definedFields = [];
    $fieldNameValue = $formId . '-' . time(); // Fallback
    $redirectSuccess = '/sucesso/'; // Padrão
    if($formDefinition){
        // ===== Verificar se o formulário está ativo
        if($formDefinition['status'] !== 'A'){
            $_GESTOR['ajax-json'] = Array(
                'status' => 'error',
                'message' => $form_ui_ajax_messages['formDisabled'] ?? 'Form is disabled.',
            );
            return;
        }

        if(isset($schema['fields'])){
            $definedFields = array_column($schema['fields'], 'name');
        }
        if(isset($schema['field_name']) && isset($_REQUEST[$schema['field_name']])){
            $fieldNameValue = $_REQUEST[$schema['field_name']];
        }
        if(isset($schema['redirects']['success'])){
            $redirectSuccess = $schema['redirects']['success']['path'];
        }
        
        // ===== Validar campos obrigatórios
        foreach($schema['fields'] as $field){
            $fieldName = $field['name'];
            $fieldValue = trim($_REQUEST[$fieldName] ?? '');
            
            if(isset($field['required']) && $field['required']){
                if(empty($fieldValue)){
                    formulario_acesso_falha(['tipo' => $formId]);
                    $_GESTOR['ajax-json'] = Array(
                        'status' => 'error',
                        'message' => modelo_var_troca($form_ui_ajax_messages['requiredField'] ?? 'Campo obrigatório: #fieldLabel#.', '#fieldLabel#', ($field['label'] ?? $fieldName)),
                    );
                    return;
                }
                
                // Validação adicional para texto/textarea (mínimo 3 caracteres)
                if(in_array($field['type'], ['text', 'textarea']) && strlen($fieldValue) < 3){
                    formulario_acesso_falha(['tipo' => $formId]);
                    $_GESTOR['ajax-json'] = Array(
                        'status' => 'error',
                        'message' => modelo_var_troca($form_ui_ajax_messages['minLength'] ?? 'Campo #fieldLabel# deve ter pelo menos 3 caracteres.', '#fieldLabel#', ($field['label'] ?? $fieldName)),
                    );
                    return;
                }
            }
        }
        
        // ===== Validar campos obrigatórios (exemplo básico, personalize conforme formId)
        // Validação dinâmica para campos do tipo 'email'
        foreach($schema['fields'] as $field){
            if($field['type'] === 'email' && isset($_REQUEST[$field['name']]) && !filter_var($_REQUEST[$field['name']], FILTER_VALIDATE_EMAIL)){
                formulario_acesso_falha(['tipo' => $formId]);
                $_GESTOR['ajax-json'] = Array(
                    'status' => 'error',
                    'message' => $form_ui_ajax_messages['invalidEmail'] ?? 'Invalid email.',
                );
                return;
            }
        }
    }
	
	// ===== Salvar em forms_submissions com estrutura de campos
    $fieldsValues = [];
    foreach($_REQUEST as $key => $value){
        if(!in_array($key, ['_formId', 'ajax', 'ajaxOpcao', 'token', 'action', 'fingerprint', 'timestamp', 'honeypot', 'g-recaptcha-response'])){
            $field = ['name' => $key, 'value' => $value];
            if(!in_array($key, $definedFields)){
                $field['undefined'] = true;
            }
            $fieldsValues[] = $field;
        }
    }
    
    $submissionId = banco_identificador(Array(
        'id' => banco_escape_field($fieldNameValue),
        'tabela' => Array(
            'nome' => 'forms_submissions',
            'campo' => 'id',
            'id_nome' => 'id',
            'where' => "form_id='".$formId."' AND language='".$_GESTOR['linguagem-codigo']."'",
        ),
    ));
    
    banco_insert_name([
        ['form_id', $formId, false],
        ['name', banco_escape_field($fieldNameValue), false],
        ['id', $submissionId, false],
        ['fields_values', banco_escape_field(json_encode($fieldsValues)), false],
        ['language', $_GESTOR['linguagem-codigo'], false],
    ], 'forms_submissions');
	
	// ===== Logar sucesso
	formulario_acesso_cadastrar(['tipo' => $formId]);
	
	// ===== Retornar sucesso
	$_GESTOR['ajax-json'] = Array(
        'status' => 'success',
        'redirect' => $redirectSuccess
    );
	return;
}

/**
 * Verifica acesso para formulários com fingerprinting.
 *
 * @param array|false $params Parâmetros (tipo obrigatório).
 * @return array Estado do acesso.
 */
function formulario_acesso_verificar($params = false){
    if($params)foreach($params as $var => $val)$$var = $val;
    
    $retorno = ['permitido' => false, 'status' => 'livre'];
    
    if(isset($tipo)){
        gestor_incluir_biblioteca('ip');
        $ip = ip_get();
        $fingerprint = $_REQUEST['fingerprint'] ?? null;
        
        // Verificar bloqueio temporário
        $bloqueio = banco_select(Array(
            'unico' => true,
            'tabela' => 'forms_blocks',
            'campos' => Array('id_forms_blocks'),
            'extra' => "WHERE ip='$ip' AND fingerprint='$fingerprint' AND unblock_at > NOW()"
        ));
        if($bloqueio){
            $retorno['permitido'] = false;
            $retorno['status'] = 'bloqueado';
            return $retorno;
        }
        
        $retorno['permitido'] = true;
    }
    
    return $retorno;
}

/**
 * Cadastra tentativa para formulários com fingerprinting.
 *
 * @param array|false $params Parâmetros (tipo obrigatório).
 * @return void
 */
function formulario_acesso_cadastrar($params = false){
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($tipo)){
        gestor_incluir_biblioteca('ip');
        $ip = ip_get();
        $fingerprint = $_REQUEST['fingerprint'] ?? null;
        
        // Verificar status usando formulario_acesso_verificar
        $acesso = formulario_acesso_verificar(['tipo' => $tipo]);
        
        if($acesso['status'] == 'bloqueado'){
            // Bloquear por 24h se ainda não bloqueado
            banco_insert_name([
                ['ip', banco_escape_field($ip), false],
                ['fingerprint', banco_escape_field($fingerprint), false],
                ['unblock_at', date('Y-m-d H:i:s', strtotime('+24 hours')), true]
            ], 'forms_blocks');
            return;
        }
        
        // Logar tentativa de sucesso
        banco_insert_name([
            ['form_id', banco_escape_field($tipo), false],
            ['ip', banco_escape_field($ip), false],
            ['fingerprint', banco_escape_field($fingerprint), false],
            ['created_at', date('Y-m-d H:i:s'), true],
            ['success', '1', true]
        ], 'forms_logs');
    }
}

/**
 * Registra falha para formulários com fingerprinting.
 *
 * @param array|false $params Parâmetros (tipo obrigatório).
 * @return void
 */
function formulario_acesso_falha($params = false){
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($tipo)){
        gestor_incluir_biblioteca('ip');
        $ip = ip_get();
        $fingerprint = $_REQUEST['fingerprint'] ?? null;
        
        // Logar falha
        banco_insert_name([
            ['form_id', banco_escape_field($tipo), false],
            ['ip', banco_escape_field($ip), false],
            ['fingerprint', banco_escape_field($fingerprint), false],
            ['created_at', date('Y-m-d H:i:s'), true],
            ['success', '0', true]
        ], 'forms_logs');
    }
}

/**
 * Limpa registros antigos das tabelas de formulários.
 *
 * Remove logs expirados e desbloqueia IPs com tempo de bloqueio expirado.
 * Executado periodicamente pelo sistema de cron.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @param array|false $params Parâmetros da função.
 * @return void
 */
function formulario_acessos_limpeza($params = false){
    global $_GESTOR;
    global $_CONFIG;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Tempo padrão para limpeza (30 dias, ajuste via config se necessário)
    $tempoLimpeza = isset($_CONFIG['forms-tempo-limpeza']) ? $_CONFIG['forms-tempo-limpeza'] : (30 * 24 * 60 * 60); // 30 dias em segundos
    
    // ===== Remover logs antigos de forms_logs
    banco_delete(
        "forms_logs",
        "WHERE created_at < DATE_SUB(NOW(), INTERVAL " . ($tempoLimpeza / (24 * 60 * 60)) . " DAY)"
    );
    
    // ===== Desbloquear IPs com tempo de bloqueio expirado em forms_blocks
    banco_delete(
        "forms_blocks",
        "WHERE unblock_at < NOW()"
    );
}

// ===== Funções dos widgets

/**
 * Configura validação de formulário com regras personalizadas.
 *
 * Sistema completo de validação client-side usando JavaScript.
 * Suporta múltiplas regras (email, CPF, CNPJ, telefone, comparação, regex, etc).
 * Gera código JavaScript inline com configurações de validação.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['formId'] ID do formulário HTML (obrigatório).
 * @param array $params['validacao'] Array de regras de validação (opcional).
 * @param string $params['validacao'][]['regra'] Nome da regra: 'email', 'cpf', 'cnpj', 'telefone', 'email-comparacao', etc (obrigatório).
 * @param string $params['validacao'][]['campo'] Nome do campo HTML a validar (obrigatório).
 * @param string $params['validacao'][]['label'] Label do campo para mensagens de erro (obrigatório).
 * @param string $params['validacao'][]['identificador'] ID alternativo do campo (opcional).
 * @param array $params['validacao'][]['removerRegra'] Regras padrão a remover (opcional).
 * @param array $params['validacao'][]['comparacao'] Dados para regra 'email-comparacao' (opcional).
 * @param array $params['regrasExtra'] Regras adicionais além das padrões (opcional).
 * 
 * @return void
 */
function formulario_validacao($params = false){
	global $_GESTOR;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
		// regex - String - Obrigatório - Regex que será usado pelo validador de formulário.
		// regexPermitedChars - String - Obrigatório - Caracteres permitidos que será mostrado junto com a mensagem de erro.
	
	// Se regra = 'regexNecessary'
		
		// regex - String - Obrigatório - Regex que será usado pelo validador de formulário.
		// regexNecessaryChars - String - Obrigatório - Caracteres necessários que será mostrado junto com a mensagem de erro.
	
	// Se regra = 'manual'
		
		// regrasManuais - Array - Opcional - Conjunto regras definidas manualmente.
	
	// ===== 
	
	if(isset($validacao) && isset($formId)){
		foreach($validacao as $regra){
			switch($regra['regra']){
				case 'manual':
					$regras_validacao[$regra['campo']] = Array(
						'rules' => $regra['regrasManuais'],
					);
				break;
				case 'texto-obrigatorio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[3]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
						)
					);
				break;
				case 'texto-obrigatorio-verificar-campo':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-verify-field'));
					$prompt[4] = modelo_var_troca_tudo($prompt[4],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[3]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
						)
					);
					
					if(isset($regra['identificador'])){
						$validarCampos[$regra['identificador']] = Array(
							'prompt' => $prompt[4],
							'campo' => $regra['campo'],
						);
					} else {
						$validarCampos[$regra['campo']] = Array(
							'prompt' => $prompt[4],
						);
					}
				break;
				case 'selecao-obrigatorio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-select'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
						)
					);
				break;
				case 'nao-vazio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
						)
					);
				break;
				case 'email':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'email',
								'prompt' => $prompt[2],
							),
						)
					);
				break;
				case 'senha':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length-password'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-password-chars'));
					$prompt[4] = modelo_var_troca($prompt[4],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[12]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
							Array(
								'type' => 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])/]',
								'prompt' => $prompt[4],
							),
						)
					);
				break;
				case 'email-comparacao':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'notEmpty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'email',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[3],
									),
								)
							);
						}
					}
				break;
				case 'senha-comparacao':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length-password'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
							$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
							
							$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[4] = modelo_var_troca($prompt[4],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[4] = modelo_var_troca($prompt[4],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$prompt[5] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-password-chars'));
							$prompt[5] = modelo_var_troca($prompt[5],"#label#",$regra['label']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'notEmpty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'minLength[12]',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'maxLength[100]',
										'prompt' => $prompt[3],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[4],
									),
									Array(
										'type' => 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])/]',
										'prompt' => $prompt[5],
									),
								)
							);
						}
					}
				break;
				case 'email-comparacao-verificar-campo':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-verify-field'));
							$prompt[4] = modelo_var_troca_tudo($prompt[4],"#label#",$regra['label']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'notEmpty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'email',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[3],
									),
								)
							);
							
							if(isset($regra['identificador'])){
								$validarCampos[$regra['identificador']] = Array(
									'prompt' => $prompt[4],
									'campo' => $regra['campo'],
								);
							} else {
								$validarCampos[$regra['campo']] = Array(
									'prompt' => $prompt[4],
								);
							}
						}
					}
				break;
			}
			
			if(isset($regra['regrasExtra'])){
				$regrasExtra = $regra['regrasExtra'];
				foreach($regrasExtra as $regraExtra){
					switch($regraExtra['regra']){
						case 'regexPermited':
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-regex-permited-chars'));
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#label#",$regra['label']);
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#permited-chars#",$regraExtra['regexPermitedChars']);
							
							$regras_validacao[$regra['campo']]['rules'][] = Array(
								'type' => 'regExp['.$regraExtra['regex'].']',
								'prompt' => $prompt[1],
							);
						break;
						case 'regexNecessary':
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-regex-necessary-chars'));
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#label#",$regra['label']);
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#necessary-chars#",$regraExtra['regexNecessaryChars']);
							
							$regras_validacao[$regra['campo']]['rules'][] = Array(
								'type' => 'regExp['.$regraExtra['regex'].']',
								'prompt' => $prompt[1],
							);
						break;
					}
				}
			}
			
			if(isset($regra['removerRegra'])){
				$rules = $regras_validacao[$regra['campo']]['rules'];
				unset($rulesAux);
				
				foreach($rules as $rule){
					$removeuRegra = false;
					foreach($regra['removerRegra'] as $removerRegra){
						if($rule['type'] == $removerRegra){
							$removeuRegra = true;
							break;
						}
					}
					
					if(!$removeuRegra){
						$rulesAux[] = $rule;
					}
				}
				
				if(isset($rulesAux)){
					$regras_validacao[$regra['campo']]['rules'] = $rulesAux;
				}
			}
			
			if(isset($regra['identificador'])){
				$regras_validacao[$regra['campo']]['identifier'] = $regra['identificador'];
			}
		}
		
		// ===== Inclui as regras de validação no javascript
		
		if(isset($regras_validacao)){
			if(!isset($_GESTOR['javascript-vars']['formulario'])){
				$_GESTOR['javascript-vars']['formulario'] = Array();
			}
		
			$_GESTOR['javascript-vars']['formulario'][$formId]['regrasValidacao'] = $regras_validacao;
		}
		
		if(isset($validarCampos)){
			if(!isset($_GESTOR['javascript-vars']['formulario'])){
				$_GESTOR['javascript-vars']['formulario'] = Array();
			}
		
			$_GESTOR['javascript-vars']['formulario']['validarCampos'] = $validarCampos;
		}
		
		// ===== Incluir JS do módulo.
		
		formulario_incluir_js();
	}
}

/**
 * Valida campos obrigatórios no servidor (server-side).
 *
 * Executa validação de campos após submissão do formulário.
 * Suporta validação de texto (min/max caracteres), seleção e email.
 * Exibe alertas e redireciona se validação falhar.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['redirect'] URL de redirecionamento em caso de erro (opcional, usa reload se omitido).
 * @param array $params['campos'] Array de campos a validar (opcional).
 * @param string $params['campos'][]['regra'] Tipo de validação: 'texto-obrigatorio', 'selecao-obrigatorio', 'email-obrigatorio' (obrigatório).
 * @param string $params['campos'][]['campo'] Nome do campo no $_REQUEST (obrigatório).
 * @param string $params['campos'][]['label'] Label do campo para mensagens (obrigatório).
 * @param int $params['campos'][]['min'] Tamanho mínimo para 'texto-obrigatorio' (opcional, padrão 3).
 * @param int $params['campos'][]['max'] Tamanho máximo para 'texto-obrigatorio' (opcional, padrão 100).
 * 
 * @return void Exibe alerta e redireciona se validação falhar.
 */
function formulario_validacao_campos_obrigatorios($params = false){
	global $_GESTOR;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val; 
	
	// Valida cada campo conforme regra especificada
	if(isset($campos)){
		foreach($campos as $campo){
			switch($campo['regra']){
				case 'texto-obrigatorio':
					// Valida tamanho mínimo e máximo do texto
					$min = (isset($campo['min']) ? $campo['min'] : 3);
					$max = (isset($campo['max']) ? $campo['max'] : 100);
					
					$len = strlen($_REQUEST[$campo['campo']]);
					
					if($len < $min){
						$naoValidou = true;
					} else if($len > $max){
						$naoValidou = true;
					}
					
					if(isset($naoValidou)){
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'formulario','id' => 'validation-min-max-length'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#min#",$min);
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#max#",$max);
					}
				break;
				case 'selecao-obrigatorio':
					// Valida se campo de seleção tem valor
					if(!existe($_REQUEST[$campo['campo']])){
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'formulario','id' => 'validation-select'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
						
						$naoValidou = true;
					}
				break;
				case 'email-obrigatorio':
					// Valida formato de email usando regex
					$email = $_REQUEST[$campo['campo']];
					$regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@([a-z0-9-]{2,})+(\.[a-z0-9-]{2,})*$/';
					
					if(!preg_match($regex, $email)){
						$naoValidou = true;
						
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'formulario','id' => 'validation-email'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
					}
				break;
			}
			
			// Para na primeira validação que falhar
			if(isset($naoValidou)){
				break;
			}
		}
	}
	
	// Exibe alerta se validação falhou
	if(isset($naoValidouMsgAlerta)){
		interface_alerta(Array('msg' => $naoValidouMsgAlerta));
	}
	
	// Redireciona se validação falhou
	if(isset($naoValidou)){
		if(isset($redirect)){
			gestor_redirecionar($redirect);
		} else {
			gestor_reload_url();
		}
	}
}

/**
 * Obtém chave do site Google reCAPTCHA.
 *
 * Busca configuração de reCAPTCHA (v2 ou v3) no banco de dados.
 * Retorna chave pública do site se ativo, null caso contrário.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @return string|null Chave pública do site reCAPTCHA ou null se desativado.
 */
function formulario_google_recaptcha(){
	global $_GESTOR;
	
	// Busca configurações de reCAPTCHA no banco
	$variaveis = banco_select(Array(
		'tabela' => 'variaveis',
		'campos' => Array(
			'id',
			'valor',
		),
		'extra' => 
			"WHERE modulo='google-recaptcha'"
	));
	
	// Processa configurações encontradas
	if($variaveis){
		foreach($variaveis as $variavel){
			$googleRecaptcha[$variavel['id']] = $variavel['valor'];
		}
		
		// Determina tipo e chave conforme versão ativa
		if(isset($googleRecaptcha['tipo'])){
			switch($googleRecaptcha['tipo']){
				case 'recaptcha-v3':
					if(isset($googleRecaptcha['chave-site'])){
						if($googleRecaptcha['chave-site']){
							$chave = $googleRecaptcha['chave-site'];
						}
					}
					if(isset($googleRecaptcha['ativo'])){
						if($googleRecaptcha['ativo']){
							$ativo = true;
						}
					}
				break;
				case 'recaptcha-v2':
					if(isset($googleRecaptcha['chave-site-v2'])){
						if($googleRecaptcha['chave-site-v2']){
							$chave = $googleRecaptcha['chave-site-v2'];
						}
					}
					if(isset($googleRecaptcha['ativo-v2'])){
						if($googleRecaptcha['ativo-v2']){
							$ativo = true;
						}
					}
				break;
			}
		}
	}
	
	// Retorna chave se ativo, null caso contrário
	if(isset($ativo) && isset($chave)){
		return $chave;
	} else {
		return null;
	}
}

/**
 * Obtém tipo de Google reCAPTCHA configurado.
 *
 * Verifica qual versão do reCAPTCHA está ativa (v2 ou v3).
 * Retorna string com tipo ou null se desativado.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @return string|null 'recaptcha-v2' ou 'recaptcha-v3', ou null se desativado.
 */
function formulario_google_recaptcha_tipo(){
	global $_GESTOR;
	
	$variaveis = banco_select(Array(
		'tabela' => 'variaveis',
		'campos' => Array(
			'id',
			'valor',
		),
		'extra' => 
			"WHERE modulo='google-recaptcha'"
			." AND id='tipo'"
	));
	
	if($variaveis){
		foreach($variaveis as $variavel){
			$googleRecaptcha[$variavel['id']] = $variavel['valor'];
		}
		
		if(isset($googleRecaptcha['tipo'])){
			if($googleRecaptcha['tipo']){
				return $googleRecaptcha['tipo'];
			}
		}
	}
	
	return null;
}

?>