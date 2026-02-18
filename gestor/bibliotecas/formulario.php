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

/**
 * Processa imagens locais no HTML do email para embedding automático.
 *
 * Identifica imagens que começam com @[[pagina#url-raiz]]@, converte para caminhos absolutos,
 * gera CIDs únicos e substitui no HTML para embedding via PHPMailer.
 *
 * @global array $_GESTOR Sistema global.
 * @param string $html HTML do email a ser processado.
 * @return array Array com HTML processado e array de imagens para embedding.
 */
function formulario_email_processar_imagens($html) {
    global $_GESTOR;
    
    $imagens = [];
    $contador = 0;
    
    // Regex para encontrar todas as imagens locais
    $pattern = '/src="(@\[\[pagina#url-raiz\]\]@[^"]+)"/i';
    
    // Debug: testar regex
    preg_match_all($pattern, $html, $matches);
    
    // Substituir cada ocorrência
    $html = preg_replace_callback($pattern, function($matches) use (&$imagens, &$contador, $_GESTOR) {
        $contador++;
        $caminhoOriginal = $matches[1];
        
        // Extrair o caminho relativo removendo @[[pagina#url-raiz]]@
        $caminhoRelativo = str_replace('@[[pagina#url-raiz]]@', '', $caminhoOriginal);
        
        // Tentar encontrar o arquivo nos caminhos possíveis
        $caminhoAbsoluto = null;
        
        // Primeiro tentar no assets-path (arquivos do gestor e módulos)
        $caminhoTentativa1 = $_GESTOR['assets-path'] . $caminhoRelativo;
        if(file_exists($caminhoTentativa1)){
            $caminhoAbsoluto = $caminhoTentativa1;
        } else {
            // Segundo tentar no contents-path (arquivos gerenciados pelos usuários)
            $caminhoTentativa2 = $_GESTOR['contents-path'] . $caminhoRelativo;
            if(file_exists($caminhoTentativa2)){
                $caminhoAbsoluto = $caminhoTentativa2;
            }
        }
        
        // Se encontrou o arquivo, adicionar ao array de imagens
        if($caminhoAbsoluto){
            // Gerar CID único
            $cid = 'img-' . $contador;
            
            // Adicionar ao array de imagens
            $imagens[] = [
                'caminho' => $caminhoAbsoluto,
                'cid' => $cid,
                'nome' => basename($caminhoAbsoluto)
            ];
            
            // Retornar o src com CID
            return 'src="cid:' . $cid . '"';
        } else {
            // Se não encontrou, manter o src original (não processar)
            return $matches[0];
        }
    }, $html);
    
    return [
        'html' => $html ?? null,
        'imagens' => $imagens ?? null
    ];
}

// ===== Funções principais

/**
 * Controlador de Formulários.
 *
 * Função controlador de formulários no backend, processamento de dados, integração com banco de dados, etc.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CONFIG Configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['formId'] ID do formulário HTML (obrigatório).
 * @param string $params['formAction'] URL de ação do formulário (opcional).
 * @param string $params['formAjaxOpcao'] Opção AJAX (opcional).
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
			$form_ui_prompts['empty'] = modelo_tag_val($form_ui_cel['prompts'], '<!-- prompt-empty < -->', '<!-- prompt-empty > -->');
			$form_ui_prompts['email'] = modelo_tag_val($form_ui_cel['prompts'], '<!-- email < -->', '<!-- email > -->');
		}

		// Processar valores individuais dos ui-texts
		$form_ui_texts = [];
		if(isset($form_ui_cel['ui-texts'])){
			$form_ui_texts['loading'] = modelo_tag_val($form_ui_cel['ui-texts'], '<!-- ui-text-loading < -->', '<!-- ui-text-loading > -->');
			$form_ui_texts['timeoutError'] = modelo_tag_val($form_ui_cel['ui-texts'], '<!-- ui-text-timeout-error < -->', '<!-- ui-text-timeout-error > -->');
			$form_ui_texts['generalError'] = modelo_tag_val($form_ui_cel['ui-texts'], '<!-- text-general-error < -->', '<!-- text-general-error > -->');
			$form_ui_texts['requireV2Message'] = modelo_tag_val($form_ui_cel['ui-texts'], '<!-- text-require-v2-message < -->', '<!-- text-require-v2-message > -->');
		}

		// Processar valores individuais dos ui-components
		$form_ui_components = [];
		if(isset($form_ui_cel['ui-components'])){
			$form_ui_components['dimmerFomantic'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- dimmerFomantic -->', '<!-- /dimmerFomantic -->');
			$form_ui_components['dimmerTailwind'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- dimmerTailwind -->', '<!-- /dimmerTailwind -->');
			$form_ui_components['errorElementFomantic'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- errorElementFomantic -->', '<!-- /errorElementFomantic -->');
			$form_ui_components['errorElementTailwind'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- errorElementTailwind -->', '<!-- /errorElementTailwind -->');
			$form_ui_components['recaptchaV2'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- recaptchaV2 -->', '<!-- /recaptchaV2 -->');
			$form_ui_components['formDisabled'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- formDisabled -->', '<!-- /formDisabled -->');
			$form_ui_components['errorMessageFomantic'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- errorMessageFomantic -->', '<!-- /errorMessageFomantic -->');
			$form_ui_components['errorMessageTailwind'] = modelo_tag_val($form_ui_cel['ui-components'], '<!-- errorMessageTailwind -->', '<!-- /errorMessageTailwind -->');
		}

		// Processar block-wrapper
		if(isset($form_ui_cel['block-wrapper'])){
			$form_ui_components['blockWrapperFomantic'] = modelo_tag_val($form_ui_cel['block-wrapper'], '<!-- blockWrapperFomantic -->', '<!-- /blockWrapperFomantic -->');
			$form_ui_components['blockWrapperTailwind'] = modelo_tag_val($form_ui_cel['block-wrapper'], '<!-- blockWrapperTailwind -->', '<!-- /blockWrapperTailwind -->');
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

        // ===== Verificar a permissão do acesso.
        $acesso = formulario_acesso_verificar(['tipo' => $formId]);

        // ===== Devolver mensagem de bloqueio caso o IP esteja bloqueado, senão incluir o formulário normalmente.
        if(!$acesso['permitido']){
            $framework = $_GESTOR['pagina#framework_css'];
            $blockWrapperKey = 'blockWrapper' . ($framework === 'fomantic-ui' ? 'Fomantic' : 'Tailwind');
            $blockWrapper = $form_ui_components[$blockWrapperKey] ?? '';
        }

        // ==== Extrair campos e redirects do schema do formulário para passar para o JS
        $fieldsDoJson = [];
        $redirectsDoJson = [];
        $formStatus = 'A'; // Padrão ativo
        $forceRecaptchaV3 = false; // Padrão
        if($formDefinition){
            $schema = json_decode($formDefinition['fields_schema'], true);
            if(isset($schema['form_action'])){
                $formAction = $_GESTOR['url-raiz'] . $schema['form_action'];
            }
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
                $googleRecaptchaAction = str_replace('-', '_', $formId) . '_action';
            }
        }

        // ===== Incluir google reCAPTCHA v2 caso ativo
        if(isset($_CONFIG['usuario-recaptcha-v2-active']) && $_CONFIG['usuario-recaptcha-v2-active']){
            $googleRecaptchaV2Active = true;
            $googleRecaptchaV2Site = $_CONFIG['usuario-recaptcha-v2-site'];
        }
        
        // ===== Inclusão Módulo JS
        
        $versao = isset($_GESTOR['biblioteca-formulario']['versao']) ? $_GESTOR['biblioteca-formulario']['versao'] : '1.0.0';
        gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$versao.'"></script>');
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
            'framework' => $_GESTOR['pagina#framework_css'],
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
 * @global array $_CONFIG Configurações.
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
		$form_ui_ajax_messages['formIdMissing'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-form-id-missing < -->', '<!-- ajax-message-form-id-missing > -->');
		$form_ui_ajax_messages['suspectedBot'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-suspected-bot < -->', '<!-- ajax-message-suspected-bot > -->');
		$form_ui_ajax_messages['requestExpired'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-request-expired < -->', '<!-- ajax-message-request-expired > -->');
		$form_ui_ajax_messages['captchaFailed'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-captcha-failed < -->', '<!-- ajax-message-captcha-failed > -->');
		$form_ui_ajax_messages['captchaV2Failed'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-captcha-v2-failed < -->', '<!-- ajax-message-captcha-v2-failed > -->');
		$form_ui_ajax_messages['formDisabled'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-form-disabled < -->', '<!-- ajax-message-form-disabled > -->');
		$form_ui_ajax_messages['requiredField'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-required-field < -->', '<!-- ajax-message-required-field > -->');
		$form_ui_ajax_messages['minLength'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-min-length < -->', '<!-- ajax-message-min-length > -->');
		$form_ui_ajax_messages['maxLength'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-max-length < -->', '<!-- ajax-message-max-length > -->');
		$form_ui_ajax_messages['invalidEmail'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-invalid-email < -->', '<!-- ajax-message-invalid-email > -->');
		$form_ui_ajax_messages['blocked'] = modelo_tag_val($form_ui_ajax_cel, '<!-- ajax-message-blocked < -->', '<!-- ajax-message-blocked > -->');
	}

	$formId = $_POST['_formId'] ?? null;

	if(!$formId){
		// Retorno do AJAX em caso de formId ausente
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['formIdMissing'] ?? 'Form ID missing.',
		);
		return false;
	}

	// Sanitização do formId para evitar SQL Injection (mesmo que seja usado como parâmetro em funções seguras, é uma boa prática)
	$formId = banco_escape_field($formId);
	
	// ===== Validar honeypot (anti-bot)
	if(!empty($_POST['honeypot'])){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['suspectedBot'] ?? 'Suspected bot activity.',
		);
		return false;
	}
	
	// ===== Validar timestamp (anti-replay, max 2 dias)
	$timestamp = $_POST['timestamp'] ?? 0;
	$limite = 172800; // 2 dias
	if(time() - $timestamp > $limite){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['requestExpired'] ?? 'Request expired.',
		);
		return false;
	}

	// ===== Buscar definição do formulário na tabela forms
    $formDefinition = banco_select(Array(
        'unico' => true,
        'tabela' => 'forms',
        'campos' => Array(
            'name',
            'fields_schema',
            'status',
        ),
        'extra' => "WHERE id='$formId' AND language='".$_GESTOR['linguagem-codigo']."'"
    ));

	// ===== Verificar acesso e status
	$acesso = formulario_acesso_verificar(['tipo' => $formId]);

	if($acesso['status'] == 'bloqueado'){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['blocked'] ?? 'Form is blocked.',
		);
		return false;
	}
	
	// ===== Rate limiting e CAPTCHA
	$captchaV2Ativo = false;

	// Flag para forçar v3 (do schema)
	$forceRecaptchaV3 = false; // Padrão
	$maxCadastros = $_CONFIG['formularios-maximo-cadastros'];
	$maxCadastrosSimples = $_CONFIG['formularios-maximo-cadastros-simples'];
	if($formDefinition){
        $schema = json_decode($formDefinition['fields_schema'], true);

		if(isset($schema['force_recaptcha']) && $schema['force_recaptcha'] === true){
			$forceRecaptchaV3 = true;
		}
		if(isset($schema['access_max'])){
			$maxCadastros = (int)$schema['access_max'];
		}
		if(isset($schema['access_max_simple'])){
			$maxCadastrosSimples = (int)$schema['access_max_simple'];
		}
	}
	
	$recaptchaValido = false;
	if(isset($_CONFIG['usuario-recaptcha-active']) && $_CONFIG['usuario-recaptcha-active'] && ($acesso['status'] != 'livre' || $forceRecaptchaV3)){
		$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
		$token = $_POST['token'] ?? null;
		$action = $_POST['action'] ?? null;
		
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
		formulario_acesso_falha(['tipo' => $formId, 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $form_ui_ajax_messages['captchaFailed'] ?? 'CAPTCHA validation failed.',
		);
		return false;
	}
	
	if($captchaV2Ativo){
		// Verificar se v2 foi enviado
		$recaptchaV2Response = $_POST['g-recaptcha-response'] ?? null;
		if(!$recaptchaV2Response){
			$_GESTOR['ajax-json'] = Array(
				'status' => 'require_v2',
			);
			return false;
		}
		
		// Validar v2
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $_CONFIG['usuario-recaptcha-v2-server'], 'response' => $recaptchaV2Response]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$arrResponse = json_decode($response, true);
		
		if(!$arrResponse["success"]){
			formulario_acesso_falha(['tipo' => $formId, 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);
			$_GESTOR['ajax-json'] = Array(
				'status' => 'error',
				'message' => $form_ui_ajax_messages['captchaV2Failed'] ?? 'CAPTCHA v2 validation failed.',
			);
			return false;
		}
	}

	// ===== Verificar pegar dados do formulário para validação e armazenamento
    $definedFields = [];
    $fieldNameValue = $formId . '-' . time(); // Fallback
    $fieldEmailValue = ''; // Fallback
    $formName = ''; // Fallback
    $redirectSuccess = '/sucesso/'; // Padrão
    $emailData = [];
    if($formDefinition){
        // ===== Verificar se o formulário está ativo
        if($formDefinition['status'] !== 'A'){
            $_GESTOR['ajax-json'] = Array(
                'status' => 'error',
                'message' => $form_ui_ajax_messages['formDisabled'] ?? 'Form is disabled.',
            );
            return false;
        }

        if(isset($formDefinition['name'])){
            $formName = $formDefinition['name'];
        }

        if(isset($schema['fields'])){
            $definedFields = array_column($schema['fields'], 'name');
        }
        if(isset($schema['redirects']['success'])){
            $redirectSuccess = $schema['redirects']['success']['path'];
        }
        if(isset($schema['email']) && is_array($schema['email'])){
            $emailData = $schema['email'];
        }
        if(isset($schema['field_name']) && isset($_POST[$schema['field_name']])){
            $fieldNameValue = $_POST[$schema['field_name']];
			$fieldNameValueFlag = true;
        }
        if(isset($schema['field_email']) && isset($_POST[$schema['field_email']])){
            $fieldEmailValue = $_POST[$schema['field_email']];
        }

		$responderPara = !empty($fieldEmailValue) ? $fieldEmailValue : (!empty($emailData) && isset($emailData['reply_to']) ? $emailData['reply_to'] : (!empty($_CONFIG['email']['sender']['replyTo']) ? $_CONFIG['email']['sender']['replyTo'] : null));
		$responderParaNome = isset($fieldNameValueFlag) ? $fieldNameValue : (!empty($emailData) && isset($emailData['reply_to_name']) ? $emailData['reply_to_name'] : (!empty($_CONFIG['email']['sender']['replyToName']) ? $_CONFIG['email']['sender']['replyToName'] : null));
        
        // ===== Validar campos obrigatórios
        foreach($schema['fields'] as $field){
            $fieldName = $field['name'];
            $fieldValue = trim($_POST[$fieldName] ?? '');
            
            if(isset($field['required']) && $field['required']){
                if(empty($fieldValue)){
                    formulario_acesso_falha(['tipo' => $formId, 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);
                    $_GESTOR['ajax-json'] = Array(
                        'status' => 'error',
                        'message' => modelo_var_troca($form_ui_ajax_messages['requiredField'] ?? 'Campo obrigatório: #fieldLabel#.', '#fieldLabel#', ($field['label'] ?? $fieldName)),
                    );
                    return false;
                }
                
                // Validação adicional para texto/textarea (mínimo 3 caracteres)
                if(in_array($field['type'], ['text', 'textarea']) && mb_strlen($fieldValue, 'UTF-8') < 3){
                    formulario_acesso_falha(['tipo' => $formId, 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);
                    $_GESTOR['ajax-json'] = Array(
                        'status' => 'error',
                        'message' => modelo_var_troca($form_ui_ajax_messages['minLength'] ?? 'Campo #fieldLabel# deve ter pelo menos 3 caracteres.', '#fieldLabel#', ($field['label'] ?? $fieldName)),
                    );
                    return false;
                }

                // ==== Máximo por tipo (padrões: text/email=254, textarea=10000) - pode ser sobrescrito por field.max_length
                $maxLength = null;
                if(isset($field['max_length'])){
                    $maxLength = (int)$field['max_length'];
                } else {
                    if(in_array($field['type'], ['text','email'])){
                        $maxLength = 254;
                    } elseif($field['type'] === 'textarea'){
                        $maxLength = 10000;
                    }
                }

                if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
                    formulario_acesso_falha(['tipo' => $formId, 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);
                    $msg = $form_ui_ajax_messages['maxLength'] ?? 'Field #fieldLabel# exceeded maximum length of #max# characters.';
                    $msg = modelo_var_troca($msg, '#fieldLabel#', ($field['label'] ?? $fieldName));
                    $msg = modelo_var_troca($msg, '#max#', $maxLength);
                    $_GESTOR['ajax-json'] = Array(
                        'status' => 'error',
                        'message' => $msg,
                    );
                    return false;
                }
            }
        }
        
        // ===== Validar campos obrigatórios (exemplo básico, personalize conforme formId)
        // Validação dinâmica para campos do tipo 'email'
        foreach($schema['fields'] as $field){
            if($field['type'] === 'email' && isset($_POST[$field['name']]) && !filter_var($_POST[$field['name']], FILTER_VALIDATE_EMAIL)){
                formulario_acesso_falha(['tipo' => $formId, 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);
                $_GESTOR['ajax-json'] = Array(
                    'status' => 'error',
                    'message' => $form_ui_ajax_messages['invalidEmail'] ?? 'Invalid email.',
                );
                return false;
            }
        }

		// Validação de tamanho máximo para campos com valor (aplica também a campos opcionais)
		foreach($schema['fields'] as $field){
			$fieldName = $field['name'];
			$fieldValue = trim($_POST[$fieldName] ?? '');

			$maxLength = isset($field['max_length']) ? (int)$field['max_length'] : (
				in_array($field['type'], ['text','email']) ? 254 : (
					($field['type'] === 'textarea') ? 10000 : 1000
				)
			);

			if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
				formulario_acesso_falha(['tipo' => $formId, 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);
				$msg = $form_ui_ajax_messages['maxLength'] ?? 'Field #fieldLabel# exceeded maximum length of #max# characters.';
				$msg = modelo_var_troca($msg, '#fieldLabel#', ($field['label'] ?? $fieldName));
				$msg = modelo_var_troca($msg, '#max#', $maxLength);
				$_GESTOR['ajax-json'] = Array(
					'status' => 'error',
					'message' => $msg,
				);
				return false;
			}
			
            $fieldsValues[] = [
				'name' => $fieldName,
				'value' => $fieldValue ?? '',
			];
        }
    }
    
    $submissionId = banco_identificador(Array(
        'id' => banco_escape_field($fieldNameValue),
        'tabela' => Array(
            'nome' => 'forms_submissions',
            'campo' => 'id',
            'id_nome' => 'id',
            'where' => "language='".$_GESTOR['linguagem-codigo']."'",
        ),
    ));
    
    banco_insert_name([
        ['form_id', $formId, false],
        ['name', banco_escape_field($fieldNameValue), false],
        ['id', $submissionId, false],
        ['language', $_GESTOR['linguagem-codigo'], false],
    ], 'forms_submissions');

	$form_last_id = banco_last_id();
	
	// ===== Cadastrar o acesso para controle de bloqueios e rate limiting
	formulario_acesso_cadastrar(['tipo' => $formId, 'antispam' => ($acesso['status'] == 'antispam'), 'maximoCadastros' => $maxCadastros, 'maximoCadastrosSimples' => $maxCadastrosSimples]);

	// ===== Remetentes e Destinatários dos emails.

	if(!empty($_CONFIG['email']['sender']['from']) && !empty($_CONFIG['email']['sender']['fromName'])){
		$defaultSender = $_CONFIG['email']['sender']['fromName'] . ' <' . $_CONFIG['email']['sender']['from'] . '>';
	} else if(!empty($_CONFIG['email']['sender']['from'])){
		$defaultSender = $_CONFIG['email']['sender']['from'];
	} else {
		$defaultSender = null;
	}
			
	$destinatariosTXT = !empty($emailData) && isset($emailData['recipients']) ? $emailData['recipients'] : $defaultSender;
	
	$destinatarios = explode(';',trim($destinatariosTXT));

	if($destinatarios)
	foreach($destinatarios as $destinatario){
		$destinatario = trim($destinatario);
		
		if(preg_match('/</', $destinatario) > 0){
			$arrAux = explode('<',trim($destinatario));
			$nomeAux = $arrAux[0];
			$emailAux = rtrim($arrAux[1], '>');
			
			$destinatatiosArr[] = Array(
				'email' => $emailAux,
				'nome' => $nomeAux,
			);
		} else {
			$destinatatiosArr[] = Array(
				'email' => $destinatario,
			);
		}
	}
	
	// ===== Formatar o email.
	
	$numero = date('Ymd') . $form_last_id;

	$assunto = !empty($emailData) && isset($emailData['subject']) ? $emailData['subject'] : gestor_variaveis(Array('id' => 'forms-subject-emails'));
	
	$assunto = modelo_var_troca($assunto,"#code#",$numero);
	$assunto = modelo_var_troca($assunto,"#formName#",$formName);

	$mensagem = !empty($emailData) && isset($emailData['message_component']) ? gestor_componente(Array('id' => $emailData['message_component'])) : gestor_componente(Array('id' => 'forms-prepared-email'));
	
	$mensagem = modelo_var_troca($mensagem,"#code#",$numero);
	$mensagem = modelo_var_troca($mensagem,"#formName#",$formName);

	// ===== Processar template de email com campos do formulário
	
	// Extrair a célula 'cel' do modelo
	$cel_nome = 'cel';
	$cel[$cel_nome] = modelo_tag_val($mensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$mensagem = modelo_tag_troca_val($mensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	// Preparar array de campos com labels e valores
	$camposProcessados = [];
	if($formDefinition && isset($schema['fields'])){
		foreach($schema['fields'] as $field){
			$fieldName = $field['name'];
			$fieldLabel = isset($field['label']) ? $field['label'] : ucfirst($fieldName); // Fallback para o nome se não houver label
			$fieldValue = '';
			
			// Encontrar o valor do campo nos dados enviados (original para formatação)
			$rawValue = isset($_POST[$fieldName]) ? $_POST[$fieldName] : '';

			// Formatações específicas por tipo
			if($field['type'] === 'email' && filter_var($rawValue, FILTER_VALIDATE_EMAIL)){
				// Converter email em link clicável (usar rawValue para preservar formatação limpa)
				$fieldValueFormatted = '<a href="mailto:' . htmlspecialchars($rawValue, ENT_COMPAT, 'UTF-8') . '">' . htmlspecialchars($rawValue, ENT_COMPAT, 'UTF-8') . '</a>';
			} else {
				// Sanitizar o valor para prevenir XSS e injeções
				$fieldValue = htmlspecialchars($rawValue, ENT_QUOTES, 'UTF-8');
				
				$fieldValueFormatted = $fieldValue;
			}

			// Para textarea preserve quebras de linha (usar $fieldValue - já sanitizado). Para outros tipos, remova tags.
			if(isset($field['type']) && $field['type'] === 'textarea'){
				$plainForPreview = preg_replace("/\r\n|\r/", "\n", $fieldValue);
			} else {
				$plainForPreview = strip_tags($fieldValueFormatted);
			}

			// Se for textarea, converter quebras para <br> para o email HTML (preview), mantendo #valor_full# com formatação completa
			$preview = (isset($field['type']) && $field['type'] === 'textarea') ? nl2br($plainForPreview) : $plainForPreview;

			$camposProcessados[] = [
				'#label#' => $fieldLabel,
				'#valor#' => $preview,
				'#valor_full#' => $fieldValueFormatted
			];
		}
	}
	
	// Processar cada campo na célula
	$celulasProcessadas = '';
	foreach($camposProcessados as $campo){
		$cel_aux = $cel[$cel_nome];
		$cel_aux = modelo_var_troca($cel_aux, $campo);
		$celulasProcessadas .= $cel_aux;
	}
	
	// Inserir células processadas de volta no modelo
	$mensagem = modelo_var_in($mensagem,'<!-- '.$cel_nome.' -->',$celulasProcessadas);
	
	// Remover a célula original
	$mensagem = modelo_var_troca($mensagem,'<!-- '.$cel_nome.' -->','');

	// ===== Processar imagens locais para embedding automático
	$resultadoImagens = formulario_email_processar_imagens($mensagem);
	$mensagem = $resultadoImagens['html'];
	$imagens = $resultadoImagens['imagens'];
	
	// ===== Enviar o email.
	
	gestor_incluir_biblioteca('comunicacao');
	
	$emailEnviado = comunicacao_email(Array(
		'destinatarios' => $destinatatiosArr ?? null,
		'remetente' => Array(
			'responderPara' => $responderPara ?? null,
			'responderParaNome' => $responderParaNome ?? null,
		),
		'mensagem' => Array(
			'assunto' => $assunto,
			'htmlCompleto' => $mensagem,
			'imagens' => $imagens ?? null,
		),
	));
	
	// ===== Atualizar status do envio de email no registro
	if($emailEnviado){
		$statusEmail = 'email-sent';
	} else {
		$statusEmail = 'email-not-sent';
	}
	
	// Adicionar status ao fields_values
	$fieldsValuesFinal = [];
	$fieldsValuesFinal['fields'] = $fieldsValues;
	$fieldsValuesFinal['email_status'] = $statusEmail;
	
	// Atualizar o registro na tabela forms_submissions
	banco_update_campo('fields_values', json_encode($fieldsValuesFinal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	banco_update_executar('forms_submissions', "WHERE id_forms_submissions='" . $form_last_id . "'");
	
	// ===== Retornar sucesso
	$_GESTOR['ajax-json'] = Array(
		'status' => 'success',
		'form_last_id' => $form_last_id,
		'redirect' => $redirectSuccess
	);
	return true;
}

/**
 * Verifica estado de acesso para formulários com proteção anti-spam.
 *
 * Retorna se o acesso é permitido baseado em tentativas anteriores e bloqueios por IP.
 * Previne ataques de força bruta limitando tentativas de acesso.
 *
 * @global array $_GESTOR Sistema global.
 * @param array|false $params Parâmetros (tipo obrigatório).
 * @return array Estado do acesso com 'permitido' e 'status'.
 */
function formulario_acesso_verificar($params = false){
    if($params)foreach($params as $var => $val)$$var = $val;
    
    $retorno = ['permitido' => false, 'status' => 'livre'];
    
    if(isset($tipo)){
        // ===== Limpar os acessos antigos.
        formulario_acessos_limpeza();
        
        gestor_incluir_biblioteca('ip');
        $ip = ip_get();
        $fingerprint = !empty($_POST['fingerprint']) ? $_POST['fingerprint'] : null;
        
        // Verificar bloqueio temporário
        $bloqueio = banco_select(Array(
            'unico' => true,
            'tabela' => 'forms_blocks',
            'campos' => Array('id_forms_blocks'),
            'extra' => "WHERE ip='$ip'" . ($fingerprint ? " AND fingerprint='$fingerprint'" : "") . " AND unblock_at > NOW()"
        ));
        if($bloqueio){
            $retorno['permitido'] = false;
            $retorno['status'] = 'bloqueado';
            return $retorno;
        }
        
        // ===== Verificar se o limite de erros de acesso foram atingidos na tabela acessos e tratar cada caso baseado no máximo de erros de acesso.
        $tipoAcesso = 'form-' . $tipo;
        $acessos = banco_select(Array(
            'unico' => true,
            'tabela' => 'acessos',
            'campos' => Array(
                'status',
            ),
            'extra' => 
                "WHERE tipo='".$tipoAcesso."'"
                ." AND ip='".$ip."'"
        ));
        
        if($acessos){
            $retorno['status'] = $acessos['status'];
            
            switch($acessos['status']){
                case 'bloqueado':
                    $retorno['permitido'] = false;
                break;
                default:
                    $retorno['permitido'] = true;
            }
        } else {
            $retorno['permitido'] = true;
        }
    }
    
    return $retorno;
}

/**
 * Cadastra tentativa de acesso para formulários com controle anti-spam.
 *
 * Registra acessos em locais do sistema para rastreamento e prevenção de abuso.
 * Suporta limitação automática de tentativas por IP.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @param array|false $params Parâmetros (tipo obrigatório, antispam opcional, maximoCadastros opcional, maximoCadastrosSimples opcional).
 * @return void
 */
function formulario_acesso_cadastrar($params = false){
    global $_CONFIG;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($tipo)){
        // ===== Quantidade total de cadastros do tipo informado e quantidade de bloqueios de um IP.
        
        $quantidade = 0;
        $bloqueios = 0;
        
        // ===== Pegar o IP do usuário.

        gestor_incluir_biblioteca('ip');

        $ip = ip_get();
        $fingerprint = !empty($_POST['fingerprint']) ? $_POST['fingerprint'] : null;
        
        $tipoAcesso = 'form-' . $tipo;
        
        // ===== Verificar se existe a tabela acessos para o ip atual.
        
        $acessos = banco_select(Array(
            'unico' => true,
            'tabela' => 'acessos',
            'campos' => Array(
                'quantidade',
                'bloqueios',
            ),
            'extra' => 
                "WHERE tipo='".$tipoAcesso."'"
                ." AND ip='".$ip."'"
        ));
        
        // ===== Pegar a quantidade atual e incrementar um.
        
        if($acessos){
            $quantidade = ($acessos['quantidade'] ? (int)$acessos['quantidade'] : 0);
            $bloqueios = ($acessos['bloqueios'] ? (int)$acessos['bloqueios'] : 0);
        }
        
        $quantidade++;
        
        // ===== Definir os máximos
        
        $maximoCadastros = $maximoCadastros ?? $_CONFIG['formularios-maximo-cadastros'];
        $maximoCadastrosSimples = $maximoCadastrosSimples ?? $_CONFIG['formularios-maximo-cadastros-simples'];
        
        // ===== Definir o estado do acesso.
        
        if(isset($antispam)){
            if($quantidade < $maximoCadastrosSimples){
                $status = 'livre';
            } else if($quantidade < $maximoCadastros){
                $status = 'antispam';
            } else {
                $status = 'bloqueado';
            }
        } else {
            if($quantidade < $maximoCadastros){
                $status = 'livre';
            } else {
                $status = 'bloqueado';
            }
        }
        
        // ===== Caso seja bloqueado, calcular tempo limite de bloqueio.
        
        if($status == 'bloqueado'){
            $bloqueios++;
            $tempo_bloqueio = $bloqueios * $_CONFIG['formularios-tempo-bloqueio-ip'] + time();
        }
        
        // ===== Atualizar ou criar o registro de acesso com o cadastro no banco de dados.
        
        if($acessos){
            banco_update_campo('status',$status);
            banco_update_campo('tempo_modificacao',time());
            
            if(isset($tempo_bloqueio)){
                banco_update_campo('bloqueios',$bloqueios);
                banco_update_campo('tempo_bloqueio',$tempo_bloqueio);
                banco_update_campo('quantidade','0');
            } else {
                banco_update_campo('quantidade',$quantidade);
            }
            
            banco_update_executar('acessos',"WHERE tipo='".$tipoAcesso."' AND ip='".$ip."'");
        } else {
            banco_insert_name_campo('ip',$ip);
            banco_insert_name_campo('tipo',$tipoAcesso);
            banco_insert_name_campo('tempo_modificacao',time());
            banco_insert_name_campo('status',$status);
            
            if(isset($tempo_bloqueio)){
                banco_insert_name_campo('bloqueios',$bloqueios);
                banco_insert_name_campo('tempo_bloqueio',$tempo_bloqueio);
                banco_insert_name_campo('quantidade','0');
            } else {
                banco_insert_name_campo('quantidade',$quantidade);
            }
            
            banco_insert_name
            (
                banco_insert_name_campos(),
                "acessos"
            );
        }
        
        // Verificar se foi bloqueado para bloquear em forms_blocks
        if($status == 'bloqueado'){
            // Bloquear por 24h se ainda não bloqueado
            banco_insert_name([
                ['ip', banco_escape_field($ip)],
                $fingerprint ? ['fingerprint', banco_escape_field($fingerprint)] : null,
                ['unblock_at', date('Y-m-d H:i:s', strtotime('+24 hours'))]
            ], 'forms_blocks');
            return;
        }
        
        // Logar tentativa de sucesso
        banco_insert_name([
            ['form_id', banco_escape_field($tipo), false],
            ['ip', banco_escape_field($ip), false],
            $fingerprint ? ['fingerprint', banco_escape_field($fingerprint), false] : null,
            ['created_at', date('Y-m-d H:i:s'), false],
            ['success', '1', true]
        ], 'forms_logs');
    }
}

/**
 * Registra falha de acesso para formulários.
 *
 * Incrementa contador de tentativas falhas e bloqueia IP após limite excedido.
 * Parte do sistema anti-spam e proteção contra força bruta.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @param array|false $params Parâmetros (tipo obrigatório, maximoCadastros opcional, maximoCadastrosSimples opcional).
 * @return void
 */
function formulario_acesso_falha($params = false){
    global $_CONFIG;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($tipo)){
        // ===== Quantidade total de falhas do tipo informado e quantidade de bloqueios de um IP.
        
        $quantidade = 0;
        $bloqueios = 0;
        
        // ===== Pegar o IP do usuário.

        gestor_incluir_biblioteca('ip');

        $ip = ip_get();
        $fingerprint = !empty($_POST['fingerprint']) ? $_POST['fingerprint'] : null;
        
        $tipoAcesso = 'form-' . $tipo;
        
        // ===== Verificar se existe a tabela acessos para o ip atual.
        
        $acessos = banco_select(Array(
            'unico' => true,
            'tabela' => 'acessos',
            'campos' => Array(
                'quantidade',
                'bloqueios',
            ),
            'extra' => 
                "WHERE tipo='".$tipoAcesso."'"
                ." AND ip='".$ip."'"
        ));
        
        // ===== Pegar a quantidade atual e incrementar um.
        
        if($acessos){
            $quantidade = ($acessos['quantidade'] ? (int)$acessos['quantidade'] : 0);
            $bloqueios = ($acessos['bloqueios'] ? (int)$acessos['bloqueios'] : 0);
        }
        
        $quantidade++;
        
        // ===== Definir os máximos
        
        $maximoCadastros = $maximoCadastros ?? $_CONFIG['formularios-maximo-cadastros'];
        $maximoCadastrosSimples = $maximoCadastrosSimples ?? $_CONFIG['formularios-maximo-cadastros-simples'];
        
        // ===== Definir o estado do acesso.
        
        if($quantidade < $maximoCadastrosSimples){
            $status = 'livre';
        } else if($quantidade < $maximoCadastros){
            $status = 'antispam';
        } else {
            $status = 'bloqueado';
        }
        
        // ===== Caso seja bloqueado, calcular tempo limite de bloqueio.
        
        if($status == 'bloqueado'){
            $bloqueios++;
            $tempo_bloqueio = $bloqueios * $_CONFIG['formularios-tempo-bloqueio-ip'] + time();
        }
        
        // ===== Atualizar ou criar o registro de acesso com falha no banco de dados.
        
        if($acessos){
            banco_update_campo('status',$status);
            banco_update_campo('tempo_modificacao',time());
            
            if(isset($tempo_bloqueio)){
                banco_update_campo('bloqueios',$bloqueios);
                banco_update_campo('tempo_bloqueio',$tempo_bloqueio);
                banco_update_campo('quantidade','0');
            } else {
                banco_update_campo('quantidade',$quantidade);
            }
            
            banco_update_executar('acessos',"WHERE tipo='".$tipoAcesso."' AND ip='".$ip."'");
        } else {
            banco_insert_name_campo('ip',$ip);
            banco_insert_name_campo('tipo',$tipoAcesso);
            banco_insert_name_campo('tempo_modificacao',time());
            banco_insert_name_campo('status',$status);
            
            if(isset($tempo_bloqueio)){
                banco_insert_name_campo('bloqueios',$bloqueios);
                banco_insert_name_campo('tempo_bloqueio',$tempo_bloqueio);
                banco_insert_name_campo('quantidade','0');
            } else {
                banco_insert_name_campo('quantidade',$quantidade);
            }
            
            banco_insert_name
            (
                banco_insert_name_campos(),
                "acessos"
            );
        }
        
        // Verificar se foi bloqueado para bloquear em forms_blocks
        if($status == 'bloqueado'){
            // Bloquear por 24h se ainda não bloqueado
            banco_insert_name([
                ['ip', banco_escape_field($ip)],
                $fingerprint ? ['fingerprint', banco_escape_field($fingerprint)] : null,
                ['unblock_at', date('Y-m-d H:i:s', strtotime('+24 hours'))]
            ], 'forms_blocks');
        }
        
        // Logar falha
        banco_insert_name([
            ['form_id', banco_escape_field($tipo), false],
            ['ip', banco_escape_field($ip), false],
            $fingerprint ? ['fingerprint', banco_escape_field($fingerprint), false] : null,
            ['created_at', date('Y-m-d H:i:s'), false],
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
    $tempoLimpeza = isset($_CONFIG['formularios-tempo-limpeza']) ? $_CONFIG['formularios-tempo-limpeza'] : (30 * 24 * 60 * 60); // 30 dias em segundos
    
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
    
    // ===== Desbloquear acessos com tempo_bloqueio expirado
    banco_update_campo('status','livre');
    banco_update_campo('quantidade','0');
    banco_update_campo('tempo_bloqueio','NULL', true);
    banco_update_executar('acessos',"WHERE status='bloqueado' AND tempo_bloqueio > 0 AND tempo_bloqueio < ".time());
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