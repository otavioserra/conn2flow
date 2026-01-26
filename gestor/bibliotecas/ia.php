<?php

global $_GESTOR;

$_GESTOR['biblioteca-ia']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Renderizar Prompt.
 *
 * Renderiza o componente de prompt IA com select de prompts filtrados por alvo.
 *
 * @param array $params Parâmetros da função.
 * @param string $params['alvo'] Nome do alvo do pré-prompt.
 * @param string $params['prompt_controls'] Controles extras do prompt.
 */
function ia_renderizar_prompt($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Verificar se alvo foi fornecido
	if(!isset($alvo) || !$alvo){
		return 'Erro: Parâmetro "alvo" é obrigatório.';
	}

	// Buscar conexoes da tabela servidores_ia.
	$servidores = banco_select(Array(
		'tabela' => 'servidores_ia',
		'campos' => Array(
			'id_servidores_ia',
			'nome',
			'padrao',
		),
		'extra' =>
			"WHERE status = 'A'"
			." ORDER BY padrao DESC, nome ASC"
	));

	// Se não houver conexões, retornar componente de aviso
	if(!$servidores){
		// Carregar componente
		$ia_sem_servidor = gestor_componente(Array(
			'id' => 'ia-sem-servidor',
		));

		gestor_js_variavel_incluir('ai',[
			'activated' => false,
		]);
		
		return $ia_sem_servidor;
	} else {
		gestor_js_variavel_incluir('ai',[
			'activated' => true,
		]);
	}

	// Incluir script JS
	gestor_pagina_javascript_incluir('biblioteca','ia');

	// ===== Inclusão do CodeMirror

	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/theme/tomorrow-night-bright.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.css" />');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/selection/active-line.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/searchcursor.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/search.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/scroll/annotatescrollbar.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/jump-to-line.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/edit/matchbrackets.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/xml/xml.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/javascript/javascript.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/markdown/markdown.js"></script>');

	// ===== BUSCAR PROMPTS =====

	// Buscar prompts da tabela prompts_ia filtrados por alvo
	$prompts = banco_select(Array(
		'tabela' => 'prompts_ia',
		'campos' => Array(
			'id',
			'nome',
		),
		'extra' =>
			"WHERE alvo = '".banco_escape_field($alvo)."' AND status = 'A' AND language = '".$_GESTOR['linguagem-codigo']."'"
	));

	// Gerar prompt do select
	$select_prompt = '';
	if($prompts){
		$select_prompt .= '<option value="">'.gestor_variaveis(Array('id' => 'ia-form-prompt-start-option')).'</option>';

		foreach($prompts as $prompt){
			$select_prompt .= '<option value="'.$prompt['id'].'">'.htmlspecialchars($prompt['nome']).'</option>';
		}
	} else {
		$without_prompt = gestor_variaveis(Array('id' => 'ia-component-without-prompt'));

		$select_prompt = '<option value="">'.$without_prompt.'</option>';
	}

	// ===== BUSCAR MODOS =====
	
	// Buscar modos da tabela modos_ia filtrados por alvo
	$modos = banco_select(Array(
		'tabela' => 'modos_ia',
		'campos' => Array(
			'id',
			'nome',
		),
		'extra' =>
			"WHERE alvo = '".banco_escape_field($alvo)."' AND status = 'A' AND language = '".$_GESTOR['linguagem-codigo']."'"
	));

	// Gerar modos do select
	$select_mode = '';
	if($modos){
		foreach($modos as $mode){
			$select_mode .= '<option value="'.$mode['id'].'">'.htmlspecialchars($mode['nome']).'</option>';
		}
	} else {
		$without_mode = gestor_variaveis(Array('id' => 'ia-component-without-mode'));

		$select_mode = '<option value="">'.$without_mode.'</option>';
	}

	// Pegar o modo padrão, se houver
	$modos = banco_select(Array(
		'unico' => true,
		'tabela' => 'modos_ia',
		'campos' => Array(
			'prompt',
		),
		'extra' =>
			"WHERE alvo = '".banco_escape_field($alvo)."' AND status = 'A' AND language = '".$_GESTOR['linguagem-codigo']."' AND padrao IS NOT NULL"
	));

	$modo_padrao = ($modos && isset($modos['prompt']) ? $modos['prompt'] : '');

	// ===== Conexões =====

	// Gerar servidores do select
	$select_connection = '';
	if($servidores){
		foreach($servidores as $servidor){
			$select_connection .= '<option value="'.$servidor['id_servidores_ia'].'">'.htmlspecialchars($servidor['nome']).'</option>';
		}
	} else {
		$without_servidor = gestor_variaveis(Array('id' => 'ia-component-without-servidor'));

		$select_connection = '<option value="">'.$without_servidor.'</option>';
	}

	// ===== Modelos =====

	// Pegar modelos disponíveis.
	$modelosData = json_decode(file_get_contents($_GESTOR['ROOT_PATH'].'/modulos/admin-ia/gemini/'.$_GESTOR['linguagem-codigo'].'/data.json'), true);
	$adminIAData = json_decode(file_get_contents($_GESTOR['ROOT_PATH'].'/modulos/admin-ia/admin-ia.json'), true);

	// Pegar modelo padrão
	$modelo_padrao = ($adminIAData && isset($adminIAData['apis']['gemini']['defaultModel']) ? $adminIAData['apis']['gemini']['defaultModel'] : 'gemini-1.5-flash');

	$select_model = '';
	$selected_model = '';
	if($modelosData && isset($modelosData['models']) && is_array($modelosData['models'])){
		foreach($modelosData['models'] as $modelo){
			if($modelo['name'] === $modelo_padrao){
				$selected_model = '<div class="text">
	  <span class="text">'.htmlspecialchars($modelo['displayName']).'</span>
      <span class="description">'.htmlspecialchars($modelo['description']).'</span>
    </div>';
			}
			$select_model .= '<div class="vertical item'.($modelo['name'] === $modelo_padrao ? ' selected' : '').'" data-value="'.htmlspecialchars($modelo['name']).'">
	  <span class="description">'.htmlspecialchars($modelo['description']).'</span>
	  <span class="text">'.htmlspecialchars($modelo['displayName']).'</span>
    </div>';
		}
	} else {
		$without_model = gestor_variaveis(Array('id' => 'ia-component-without-model'));

		$select_model = '<div class="item" data-value="">'.$without_model.'</div>';
	}

	// Incluir os componentes na página

	gestor_componentes_incluir([ 'id' => 'ia-prompt-modais']);

	// Definir títulos

	$titulo = gestor_variaveis(Array('id' => 'ia-component-title'));
	$form_titulo = gestor_variaveis(Array('id' => 'ia-form-title'));

	// Pegar msgensagens de erro AJAX

	$prompt_name_empty = gestor_variaveis(Array('id' => 'ia-ajax-msg-prompt-name-empty'));

	// Incluir variável JS
	gestor_js_variavel_incluir('ia',[
		'alvo' => $alvo,
		'msgs' => [
			'prompt_name_empty' => $prompt_name_empty,
		],
	]);

	// Carregar componente
	$ia_prompt = gestor_componente(Array(
		'id' => 'ia-prompt',
	));

	// Alterar variáveis no componente
	$ia_prompt = modelo_var_troca($ia_prompt,'#ia-form-title#',$form_titulo);
	$ia_prompt = modelo_var_troca($ia_prompt,'#titulo#',$titulo);
	$ia_prompt = modelo_var_troca($ia_prompt,'#select-prompt#',$select_prompt);
	$ia_prompt = modelo_var_troca($ia_prompt,'#select-mode#',$select_mode);
	$ia_prompt = modelo_var_troca($ia_prompt,'#modo-padrao#',$modo_padrao);
	$ia_prompt = modelo_var_troca($ia_prompt,'#select-connection#',$select_connection);
	$ia_prompt = modelo_var_troca($ia_prompt,'#select-model#',$select_model);
	$ia_prompt = modelo_var_troca($ia_prompt,'#selected-model#',$selected_model);
	$ia_prompt = modelo_var_troca($ia_prompt,'<!-- prompt-extra-controls -->',($prompt_controls ?? ''));

	// Retornar prompt renderizado
	return $ia_prompt;
}

/**
 * Enviar Prompt.
 *
 * Descrição
 *
 * @param <type> $var descrição.
 * @param string $params['servidor_id'] Identificador numérico do servidor IA.
 * @param string $params['modelo'] Nome do modelo IA.
 * @param string $params['prompt'] Prompt a ser enviado.
 */
function ia_enviar_prompt($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Pegar modelos disponíveis.
	$adminIAData = json_decode(file_get_contents($_GESTOR['ROOT_PATH'].'/modulos/admin-ia/admin-ia.json'), true);

	// Pegar modelo padrão e urlGenerateContent
	$modelo_padrao = ($adminIAData && isset($adminIAData['apis']['gemini']['defaultModel']) ? $adminIAData['apis']['gemini']['defaultModel'] : 'gemini-1.5-flash');
	$urlGenerateContent = ($adminIAData && isset($adminIAData['apis']['gemini']['urlGenerateContent']) ? $adminIAData['apis']['gemini']['urlGenerateContent'] : 'https:\/\/generativelanguage.googleapis.com\/v1beta\/{MODEL}:generateContent?key={API_KEY}');

	// Se modelo não for fornecido, usar o padrão
	if(!isset($modelo) || !$modelo){
		$modelo = $modelo_padrao;
	}

	// Verificar se servidor_id foi fornecido
	if(!isset($servidor_id) || !$servidor_id){
		return array(
			'status' => 'error',
			'message' => 'Parâmetro "servidor_id" é obrigatório.',
		);
	}

	// Buscar servidor na tabela servidores_ia
	$servidor = banco_select(Array(
		'unico' => true,
		'tabela' => 'servidores_ia',
		'campos' => Array(
			'chave_api',
		),
		'extra' =>
			"WHERE id_servidores_ia = '".banco_escape_field($servidor_id)."' AND status = 'A'"
	));

	// ===== Abrir chave publica e a senha da chave
    
    $keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
    
    $fp = fopen($keyPublicPath,"r");
    $keyPublicString = fread($fp,8192);
    fclose($fp);
    
	// Verificar se servidor foi encontrado
	if(!$servidor){
		return array(
			'status' => 'error',
			'message' => 'Servidor IA não encontrado ou inativo.',
		);
	}
    
	// Descriptografar chave API para uso
	gestor_incluir_biblioteca('autenticacao');

    $chave_api_descriptografada = autenticacao_decriptar_chave_publica(Array(
        'criptografia' => $servidor['chave_api'],
        'chavePublica' => $keyPublicString,
    ));

	// Preparar URL
	$urlGenerateContent = modelo_var_troca($urlGenerateContent,'{MODEL}',$modelo);
	$urlGenerateContent = modelo_var_troca($urlGenerateContent,'{API_KEY}',$chave_api_descriptografada);

	// Preparar dados para envio à API Gemini
	$requestData = array(
		'contents' => array(
			array(
				'parts' => array(
					array(
						'text' => $prompt
					)
				)
			)
		)
	);

	// Configurar cURL para enviar requisição
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $urlGenerateContent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json'
	));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Timeout de 2 minutos

	// Executar requisição
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);

	// Verificar se houve erro na requisição
	if ($curlError) {
		return array(
			'status' => 'error',
			'message' => 'Erro na comunicação com a API: ' . $curlError,
		);
	}

	// Verificar código HTTP
	if ($httpCode !== 200) {
		return array(
			'status' => 'error',
			'message' => 'Erro na API Gemini (HTTP ' . $httpCode . '): ' . $response,
		);
	}

	// Decodificar resposta JSON
	$responseData = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		return array(
			'status' => 'error',
			'message' => 'Erro ao processar resposta da API: JSON inválido',
		);
	}

	// Verificar se a resposta contém os dados esperados
	if (!isset($responseData['candidates']) || !is_array($responseData['candidates']) || empty($responseData['candidates'])) {
		return array(
			'status' => 'error',
			'message' => 'Resposta da API não contém dados válidos',
		);
	}

	// Extrair texto da resposta
	$candidate = $responseData['candidates'][0];
	if (!isset($candidate['content']['parts']) || !is_array($candidate['content']['parts']) || empty($candidate['content']['parts'])) {
		return array(
			'status' => 'error',
			'message' => 'Conteúdo da resposta está vazio',
		);
	}

	$generatedText = '';
	foreach ($candidate['content']['parts'] as $part) {
		if (isset($part['text'])) {
			$generatedText .= $part['text'];
		}
	}

	// Preparar dados de retorno
	$dataRetornoIA = array(
		'texto_gerado' => $generatedText,
		'modelo_usado' => $modelo,
		'tokens_entrada' => isset($responseData['usageMetadata']['promptTokenCount']) ? $responseData['usageMetadata']['promptTokenCount'] : null,
		'tokens_saida' => isset($responseData['usageMetadata']['candidatesTokenCount']) ? $responseData['usageMetadata']['candidatesTokenCount'] : null,
		'tokens_total' => isset($responseData['usageMetadata']['totalTokenCount']) ? $responseData['usageMetadata']['totalTokenCount'] : null,
		'resposta_completa' => $responseData
	);

	$return = array(
		'status' => 'success',
		'message' => 'Prompt processado com sucesso.',
		'data' => $dataRetornoIA,
	);

	return $return;
}

/**
 * Processar Retorno.
 *
 * Processa e formata o retorno da API de IA para uso no sistema.
 *
 * @param array $params Parâmetros da função.
 * @param array $params['dados_retorno'] Dados retornados pela API de IA.
 * @param string $params['formato'] Formato desejado para o retorno (texto, json, html).
 */
function ia_processar_retorno($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Parâmetros padrão
	$formato = isset($formato) ? $formato : 'texto';

	// Verificar se dados de retorno foram fornecidos
	if(!isset($dados_retorno) || !is_array($dados_retorno)){
		return array(
			'status' => 'error',
			'message' => 'Dados de retorno não fornecidos ou inválidos.',
		);
	}

	// Processar conforme formato solicitado
	switch($formato){
		case 'json':
			return array(
				'status' => 'success',
				'data' => $dados_retorno,
				'formato' => 'json'
			);
			break;

		case 'html':
			// Converter texto para HTML básico (quebrar linhas, etc)
			$texto = isset($dados_retorno['texto_gerado']) ? $dados_retorno['texto_gerado'] : '';
			$html = nl2br(htmlspecialchars($texto));

			return array(
				'status' => 'success',
				'data' => $html,
				'formato' => 'html'
			);
			break;

		case 'texto':
		default:
			// Retornar apenas o texto gerado
			$texto = isset($dados_retorno['texto_gerado']) ? $dados_retorno['texto_gerado'] : '';

			return array(
				'status' => 'success',
				'data' => $texto,
				'formato' => 'texto'
			);
			break;
	}
}

/**
 * AJAX Interface.
 *
 * Descrição
 *
 * @param array $params Parâmetros da função.
 */
function ia_ajax_interface($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	switch($_GESTOR['ajax-opcao']){
		case 'ia-prompts': ia_ajax_prompts(); break;
		case 'ia-modos': ia_ajax_modos(); break;
		case 'ia-prompt-edit': ia_ajax_prompt_edit(); break;
		case 'ia-prompt-new': ia_ajax_prompt_novo(); break;
		case 'ia-prompt-del': ia_ajax_prompt_del(); break;
	}
}

/**
 * AJAX Prompts.
 *
 * Retorna um prompt IA via AJAX para o frontend.
 *
 * @param array $params Parâmetros da função.
 */
function ia_ajax_prompts($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Verificar params AJAX
	if(!isset($_REQUEST['params'])){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-param-missing')),
		);
		return;
	}

	// Pegar parâmetros
	$target = (isset($_REQUEST['params']['target']) ? $_REQUEST['params']['target'] : '');
	$prompt_id = (isset($_REQUEST['params']['prompt_id']) ? $_REQUEST['params']['prompt_id'] : '');

	// Verificar params AJAX
	if(!isset($target) || !$target || !isset($prompt_id) || !$prompt_id){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-target-or-id-missing')),
		);
		return;
	}

	// Buscar prompt na tabela prompts_ia
	$prompt = banco_select(Array(
		'unico' => true,
		'tabela' => 'prompts_ia',
		'campos' => Array(
			'prompt',
		),
		'extra' =>
			"WHERE id = '".banco_escape_field($prompt_id)."' AND alvo = '".banco_escape_field($target)."' AND status = 'A' AND language = '".$_GESTOR['linguagem-codigo']."'"
	));

	// Verificar se prompt foi encontrado
	if($prompt) {
		// Retorno do AJAX
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Ok',
			'prompt' => $prompt['prompt'],
		);
	} else {
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-prompt-not-found')),
		);
		return;
	}
}

/**
 * AJAX Modos.
 *
 * Retorna um modo IA via AJAX para o frontend.
 *
 * @param array $params Parâmetros da função.
 */
function ia_ajax_modos($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Verificar params AJAX
	if(!isset($_REQUEST['params'])){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-param-missing')),
		);
		return;
	}

	// Pegar parâmetros
	$target = (isset($_REQUEST['params']['target']) ? $_REQUEST['params']['target'] : '');
	$mode_id = (isset($_REQUEST['params']['mode_id']) ? $_REQUEST['params']['mode_id'] : '');

	// Verificar params AJAX
	if(!isset($target) || !$target || !isset($mode_id) || !$mode_id){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-target-or-id-missing')),
		);
		return;
	}

	// Buscar prompt na tabela modos_ia
	$mode = banco_select(Array(
		'unico' => true,
		'tabela' => 'modos_ia',
		'campos' => Array(
			'prompt',
		),
		'extra' =>
			"WHERE id = '".banco_escape_field($mode_id)."' AND alvo = '".banco_escape_field($target)."' AND status = 'A' AND language = '".$_GESTOR['linguagem-codigo']."'"
	));

	// Verificar se modo foi encontrado
	if($mode) {
		// Retorno do AJAX
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Ok',
			'prompt' => $mode['prompt'],
		);
	} else {
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-mode-not-found')),
		);
		return;
	}
}

/**
 * AJAX Prompt Edit.
 *
 * Salva o prompt editado.
 *
 * @param array $params Parâmetros da função.
 * @param string $params['target'] Nome do alvo do pré-prompt.
 * @param string $params['prompt_id'] Identificador do prompt IA.
 * @param string $params['prompt'] Conteúdo do prompt IA.
 */
function ia_ajax_prompt_edit($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Verificar params AJAX
	if(!isset($_REQUEST['params'])){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-param-missing')),
		);
		return;
	}

	// Pegar parâmetros
	$target = (isset($_REQUEST['params']['target']) ? $_REQUEST['params']['target'] : '');
	$prompt_id = (isset($_REQUEST['params']['prompt_id']) ? $_REQUEST['params']['prompt_id'] : '');
	$prompt = (isset($_REQUEST['params']['prompt']) ? $_REQUEST['params']['prompt'] : '');

	// Verificar params AJAX
	if(!isset($target) || !$target || !isset($prompt_id) || !$prompt_id){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-target-or-id-missing')),
		);
		return;
	}

	// Atualizar prompt na tabela prompts_ia

	banco_update_campo('prompt',$prompt,false,true);

	banco_update_executar('prompts_ia',"WHERE id = '".banco_escape_field($prompt_id)."' AND alvo = '".banco_escape_field($target)."' AND language = '".$_GESTOR['linguagem-codigo']."'");

	// Retorno do AJAX
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

/**
 * AJAX Prompt Novo.
 *
 * Salva um prompt novo.
 *
 * @param array $params Parâmetros da função.
 * @param string $params['target'] Nome do alvo do pré-prompt.
 * @param string $params['nome'] Nome do prompt IA.
 * @param string $params['prompt'] Conteúdo do prompt IA.
 */
function ia_ajax_prompt_novo($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Verificar params AJAX
	if(!isset($_REQUEST['params'])){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-param-missing')),
		);
		return;
	}

	// Pegar parâmetros
	$target = (isset($_REQUEST['params']['target']) ? $_REQUEST['params']['target'] : '');
	$nome = (isset($_REQUEST['params']['nome']) ? $_REQUEST['params']['nome'] : '');
	$prompt = (isset($_REQUEST['params']['prompt']) ? $_REQUEST['params']['prompt'] : '');

	// Verificar params AJAX
	if(!isset($target) || !$target || !isset($nome) || !$nome){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-target-or-name-missing')),
		);
		return;
	}

	// ===== Definição do identificador
	
	$campos = null;
	$campo_sem_aspas_simples = false;
	
	$id = banco_identificador(Array(
		'id' => banco_escape_field($nome),
		'tabela' => Array(
			'nome' => 'prompts_ia',
			'campo' => 'id',
			'id_nome' => 'id_prompts_ia',
			'where' => "language='".$_GESTOR['linguagem-codigo']."'",
		),
	));

	// ===== Campos padrões
		
	$campo_nome = "nome"; $post_nome = "nome"; 					        			$campos[] = Array($campo_nome,banco_escape_field($nome));
	$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

	// ===== Campos específicos

	$campo_nome = "alvo"; $post_nome = $campo_nome; 								$campos[] = Array($campo_nome,banco_escape_field($target));
	$campo_nome = "prompt"; $post_nome = $campo_nome; 								$campos[] = Array($campo_nome,banco_escape_field($prompt));
	
	// ===== Campos comuns
	
	$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

	// Inserção no banco de dados
	banco_insert_name
	(
		$campos,
		'prompts_ia'
	);

	// Retorno do AJAX
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'id' => $id,
		'option' => '<option value="'.$id.'">'.htmlspecialchars($nome).'</option>',
	);
}

/**
 * AJAX Prompt Delete.
 *
 * Deletar um prompt.
 *
 * @param array $params Parâmetros da função.
 * @param string $params['target'] Nome do alvo do pré-prompt.
 * @param string $params['prompt_id'] Identificador do prompt IA.
 */
function ia_ajax_prompt_del($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Verificar params AJAX
	if(!isset($_REQUEST['params'])){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-param-missing')),
		);
		return;
	}

	// Pegar parâmetros
	$target = (isset($_REQUEST['params']['target']) ? $_REQUEST['params']['target'] : '');
	$prompt_id = (isset($_REQUEST['params']['prompt_id']) ? $_REQUEST['params']['prompt_id'] : '');

	// Verificar params AJAX
	if(!isset($target) || !$target || !isset($prompt_id) || !$prompt_id){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'msg' => gestor_variaveis(Array('id' => 'ia-ajax-error-target-or-id-missing')),
		);
		return;
	}

	// Deletar prompt na tabela prompts_ia

	banco_delete(
		"prompts_ia",
		"WHERE id = '".banco_escape_field($prompt_id)."' AND alvo = '".banco_escape_field($target)."' AND language = '".$_GESTOR['linguagem-codigo']."'"
	);

	// Retorno do AJAX
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

?>