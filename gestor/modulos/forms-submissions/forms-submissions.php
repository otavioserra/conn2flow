<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'forms-submissions';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/forms-submissions.json'), true);

// ===== Funções Auxiliares

/**
 * Retorna o label localizado do form_status a partir dos resources do módulo.
 *
 * @param string $statusId ID do status (ex: 'new', 'responded').
 * @return string Label localizado do status.
 */
function forms_submissions_get_status_label($statusId){
	global $_GESTOR;
	$modulo = $_GESTOR['modulo#forms-submissions'];
	$lang = $_GESTOR['linguagem-codigo'];
	$formStatusOptions = $modulo['resources'][$lang]['form_status'] ?? [];
	foreach($formStatusOptions as $opt){
		if($opt['id'] === $statusId) return $opt['name'];
	}
	return ucfirst($statusId);
}

// ===== Funções Principais

function forms_submissions_visualizar(){
	global $_GESTOR;
	global $_CONFIG;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$lang = $_GESTOR['linguagem-codigo'];
	
	// ===== Identificador do registro
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para visualizar.
	
	$camposBanco = Array(
		'id',
		'id_forms_submissions',
		'form_id',
		'name',
		'fields_values',
		'form_status',
		'status'
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoVisualizar = array_merge($camposBanco,$camposBancoPadrao);

	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoVisualizar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$lang."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$name = $retorno_bd['name'] ?? '';
		$fields_values_raw = $retorno_bd['fields_values'] ?? '';
		$form_id = $retorno_bd['form_id'] ?? '';
		$form_status = $retorno_bd['form_status'] ?? 'new';
		$id_numerico = $retorno_bd['id_forms_submissions'] ?? '';
		
		// ===== Parse fields_values JSON
		
		$fieldsData = json_decode($fields_values_raw, true);
		$fields = $fieldsData['fields'] ?? [];
		$emailStatus = $fieldsData['email_status'] ?? '';
		$responses = $fieldsData['responses'] ?? [];
		
		// ===== Buscar definição do formulário na tabela forms para obter labels e tipos
		
		$formDefinition = banco_select(Array(
			'unico' => true,
			'tabela' => 'forms',
			'campos' => Array('fields_schema'),
			'extra' => "WHERE id='".$form_id."' AND language='".$lang."'"
		));
		
		$formFieldsMap = [];
		$replyEmail = '';
		$replyName = $name;
		if($formDefinition){
			$formSchema = json_decode($formDefinition['fields_schema'], true);
			if(isset($formSchema['fields'])){
				foreach($formSchema['fields'] as $fieldDef){
					$formFieldsMap[$fieldDef['name']] = $fieldDef;
					// Encontrar campo de email para reply
					if($fieldDef['type'] === 'email'){
						foreach($fields as $f){
							if($f['name'] === $fieldDef['name']){
								$replyEmail = $f['value'];
								break;
							}
						}
					}
				}
			}
		}
		
		// ===== Substituir variáveis básicas no template
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#name#',$name);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#form_id#',$form_id);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id_numerico#',$id_numerico);
		// Apresentar fields_values de forma indentada (pretty-print JSON quando possível)
		$fields_values_pretty = $fields_values_raw;
		if(!empty($fields_values_raw)){
			if(is_array($fieldsData)){
				$fields_values_pretty = json_encode($fieldsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
			} else {
				$tmp = json_decode($fields_values_raw, true);
				if(json_last_error() === JSON_ERROR_NONE && (is_array($tmp) || is_object($tmp))){
					$fields_values_pretty = json_encode($tmp, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
				}
			}
		}

		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#fields_values#',$fields_values_pretty);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#reply_email#',$replyEmail);
		
		// ===== Gerar badge de email status
		
		$emailStatusBadge = '';
		if($emailStatus === 'email-sent'){
			$labelText = gestor_variaveis(['id' => 'sent-label', 'modulo' => $_GESTOR['modulo-id']]);
			$emailStatusBadge = '<div class="ui green label"><i class="check icon"></i> '.$labelText.'</div>';
		} elseif($emailStatus === 'email-not-sent'){
			$labelText = gestor_variaveis(['id' => 'not-sent-label', 'modulo' => $_GESTOR['modulo-id']]);
			$emailStatusBadge = '<div class="ui red label"><i class="times icon"></i> '.$labelText.'</div>';
		} else {
			$emailStatusBadge = '<div class="ui grey label">N/A</div>';
		}
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#email_status_badge#',$emailStatusBadge);
		
		// ===== Gerar badge de form_status atual
		
		$formStatusLabel = forms_submissions_get_status_label($form_status);
		$formStatusColor = 'blue';
		if($form_status === 'responded') $formStatusColor = 'green';
		$formStatusBadge = '<div class="ui '.$formStatusColor.' label">'.$formStatusLabel.'</div>';
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#form_status_badge#',$formStatusBadge);
		
		// ===== Processar opções de form_status (células do select)
		
		$formStatusOptions = $modulo['resources'][$lang]['form_status'] ?? [];
		$cel_nome = 'status_option';
		$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$_GESTOR['pagina'] = modelo_tag_troca_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		foreach($formStatusOptions as $statusOpt){
			$cel_aux = $cel[$cel_nome];
			$selected = ($statusOpt['id'] === $form_status) ? 'selected' : '';
			$cel_aux = modelo_var_troca($cel_aux, Array(
				'#status_id#' => $statusOpt['id'],
				'#status_name#' => $statusOpt['name'],
				'#status_selected#' => $selected,
			));
			$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->','');
		
		// ===== Processar células de campos enviados
		
		$cel_nome = 'cel';
		$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$_GESTOR['pagina'] = modelo_tag_troca_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		foreach($fields as $field){
			$cel_aux = $cel[$cel_nome];
			$fieldName = $field['name'];
			$fieldValue = $field['value'] ?? '';
			
			// Obter label e tipo do schema do formulário
			$fieldLabel = ucfirst($fieldName);
			$fieldType = 'text';
			if(isset($formFieldsMap[$fieldName])){
				$fieldLabel = $formFieldsMap[$fieldName]['label'] ?? $fieldLabel;
				$fieldType = $formFieldsMap[$fieldName]['type'] ?? 'text';
			}
			
			// Formatar valor por tipo
			if($fieldType === 'textarea'){
				$fieldValue = nl2br(htmlspecialchars($fieldValue, ENT_COMPAT, 'UTF-8'));
			} elseif($fieldType === 'email' && filter_var($fieldValue, FILTER_VALIDATE_EMAIL)){
				$fieldValue = '<a href="mailto:' . htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') . '</a>';
			} else {
				$fieldValue = htmlspecialchars($fieldValue, ENT_COMPAT, 'UTF-8');
			}
			
			$cel_aux = modelo_var_troca($cel_aux, Array(
				'#field_label#' => $fieldLabel,
				'#field_value#' => $fieldValue,
			));
			$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->','');
		
		// ===== Processar histórico de respostas
		
		if(!empty($responses)){
			$cel_nome = 'response';
			$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$_GESTOR['pagina'] = modelo_tag_troca_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			foreach($responses as $resp){
				$cel_aux = $cel[$cel_nome];
				$respMessage = nl2br(htmlspecialchars($resp['message'] ?? '', ENT_COMPAT, 'UTF-8'));
				$respDate = isset($resp['date']) ? interface_formatar_dado(Array('dado' => $resp['date'], 'formato' => 'dataHora')) : '';
				$respStatusLabel = '';
				if(isset($resp['status'])){
					if($resp['status'] === 'sent'){
						$sentLabel = gestor_variaveis(['id' => 'sent-label', 'modulo' => $_GESTOR['modulo-id']]);
						$respStatusLabel = '<div class="ui mini green label"><i class="check icon"></i> '.$sentLabel.'</div>';
					} else {
						$failLabel = gestor_variaveis(['id' => 'failed-label', 'modulo' => $_GESTOR['modulo-id']]);
						$respStatusLabel = '<div class="ui mini red label"><i class="times icon"></i> '.$failLabel.'</div>';
					}
				}
				
				$cel_aux = modelo_var_troca($cel_aux, Array(
					'#response_message#' => $respMessage,
					'#response_date#' => $respDate,
					'#response_status#' => $respStatusLabel,
				));
				$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->','');
			
			// Manter seção do histórico
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'<!-- response_history < -->','');
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'<!-- response_history > -->','');
		} else {
			// Remover seção do histórico se não há respostas
			$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- response_history < -->','<!-- response_history > -->');
		}
		
		// ===== Controlar visibilidade da seção de reply
		
		if(empty($replyEmail)){
			$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- reply_section < -->','<!-- reply_section > -->');
		} else {
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'<!-- reply_section < -->','');
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'<!-- reply_section > -->','');
		}
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão variáveis JS
	
	gestor_js_variavel_incluir('formsSubmissions', Array(
		'idNumerico' => $id_numerico ?? '',
	));
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface visualizar finalizar opções
	
	$_GESTOR['interface']['visualizar']['finalizar'] = Array(
		'campoTitulo' => $modulo['tabela']['nome_especifico'],
		'id' => $id,
		'metaDados' => $metaDados,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
	);
}

function forms_submissions_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'name',
						'form_id',
						'form_status',
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "language='".$_GESTOR['linguagem-codigo']."'",
				),
				'tabela' => Array(
					'colunas' => Array(
						Array(
							'id' => 'name',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'form_id',
							'nome' => gestor_variaveis(['id' => 'table-column-form', 'modulo' => $_GESTOR['modulo-id']]),
							'ordenar' => 'asc',
							'formatar' => Array(
								'id' => 'encapsular',
								'capsula' => '<div class="ui basic label">#id#</div>',
								'variavel' => '#id#',
							),
						),
						Array(
							'id' => 'form_status',
							'nome' => gestor_variaveis(['id' => 'table-column-status', 'modulo' => $_GESTOR['modulo-id']]),
							'ordenar' => 'asc',
							'formatar' => Array(
								'id' => 'outroArray',
								'campo_troca' => 'id',
								'campo_alvo' => 'name',
								'valores' => $modulo['resources'][$_GESTOR['linguagem-codigo']]['form_status'] ?? [],
							)
						),
						Array(
							'id' => $modulo['tabela']['data_modificacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
					),
				),
				'opcoes' => Array(
					'visualizar' => Array(
						'url' => 'view/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-view')),
						'icon' => 'file alternate outline',
						'cor' => 'basic brown',
					),
				),
				'botoes' => Array(
					
				),
			);
		break;
	}
}

// ==== Ajax

function forms_submissions_ajax_forms_process(){
	global $_GESTOR;
	
	// ===== Incluir biblioteca de formulário para processar o envio do formulário.
	gestor_incluir_biblioteca('formulario');
	
	// ===== Processar o formulário usando a biblioteca de formulário.
	formulario_processador();
}

/**
 * Atualiza o form_status de um registro de submissão.
 */
function forms_submissions_ajax_update_status(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#forms-submissions'];
	$lang = $_GESTOR['linguagem-codigo'];
	
	$id_numerico = $_POST['id_numerico'] ?? null;
	$form_status = $_POST['form_status'] ?? null;
	
	if(!$id_numerico || !$form_status){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => gestor_variaveis(Array('id' => 'error-msg-missing-fields','modulo' => $_GESTOR['modulo-id'])),
		);
		return;
	}
	
	// Validar se o status é permitido
	$allowedStatuses = array_column($modulo['resources'][$lang]['form_status'] ?? [], 'id');
	if(!in_array($form_status, $allowedStatuses)){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => gestor_variaveis(['id' => 'error-msg-invalid-status', 'modulo' => $_GESTOR['modulo-id']]),
		);
		return;
	}
	
	banco_update_campo('form_status', banco_escape_field($form_status));
	banco_update_executar('forms_submissions', "WHERE id_forms_submissions='" . banco_escape_field($id_numerico) . "'");
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'success',
		'message' => gestor_variaveis(['id' => 'success-msg-status-updated', 'modulo' => $_GESTOR['modulo-id']]),
		'form_status_label' => forms_submissions_get_status_label($form_status),
	);
}

/**
 * Envia uma resposta por email para o contato e atualiza o registro.
 */
function forms_submissions_ajax_reply(){
	global $_GESTOR;
	global $_CONFIG;
	
	$lang = $_GESTOR['linguagem-codigo'];
	
	$id_numerico = $_POST['id_numerico'] ?? null;
	$reply_message = $_POST['reply_message'] ?? '';
	$reply_email = $_POST['reply_email'] ?? '';
	
	if(!$id_numerico || empty($reply_message) || empty($reply_email)){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => gestor_variaveis(['id' => 'error-msg-missing-fields', 'modulo' => $_GESTOR['modulo-id']]),
		);
		return;
	}
	
	// Validar email
	if(!filter_var($reply_email, FILTER_VALIDATE_EMAIL)){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => gestor_variaveis(['id' => 'error-msg-invalid-email', 'modulo' => $_GESTOR['modulo-id']]),
		);
		return;
	}
	
	// Buscar dados da submissão
	$submission = banco_select(Array(
		'unico' => true,
		'tabela' => 'forms_submissions',
		'campos' => Array('id_forms_submissions', 'form_id', 'name', 'fields_values'),
		'extra' => "WHERE id_forms_submissions='" . banco_escape_field($id_numerico) . "'"
	));
	
	if(!$submission){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => gestor_variaveis(['id' => 'error-msg-submission-not-found', 'modulo' => $_GESTOR['modulo-id']]),
		);
		return;
	}
	
	// Buscar definição do formulário para labels
	$formDefinition = banco_select(Array(
		'unico' => true,
		'tabela' => 'forms',
		'campos' => Array('fields_schema'),
		'extra' => "WHERE id='" . $submission['form_id'] . "' AND language='" . $lang . "'"
	));
	
	$formFieldsMap = [];
	if($formDefinition){
		$schema = json_decode($formDefinition['fields_schema'], true);
		if(isset($schema['fields'])){
			foreach($schema['fields'] as $fieldDef){
				$formFieldsMap[$fieldDef['name']] = $fieldDef;
			}
		}
	}
	
	// Parse fields_values
	$fieldsData = json_decode($submission['fields_values'], true);
	$fields = $fieldsData['fields'] ?? [];
	
	// Preparar template de email de resposta
	$mensagem = gestor_componente(Array('id' => 'prepared-response-email'));
	
	// Substituir variáveis de resposta
	$reply_message_html = nl2br(htmlspecialchars($reply_message, ENT_QUOTES, 'UTF-8'));
	$mensagem = modelo_var_troca_tudo($mensagem, '#response_message#', $reply_message_html);
	$mensagem = modelo_var_troca_tudo($mensagem, '#name#', htmlspecialchars($submission['name'], ENT_QUOTES, 'UTF-8'));
	
	// Processar células dos campos originais
	$cel_nome = 'cel';
	$cel[$cel_nome] = modelo_tag_val($mensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$mensagem = modelo_tag_troca_val($mensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	foreach($fields as $field){
		$cel_aux = $cel[$cel_nome];
		$fieldName = $field['name'];
		$fieldValue = htmlspecialchars($field['value'] ?? '', ENT_QUOTES, 'UTF-8');
		$fieldLabel = ucfirst($fieldName);
		
		if(isset($formFieldsMap[$fieldName])){
			$fieldLabel = $formFieldsMap[$fieldName]['label'] ?? $fieldLabel;
			$fieldType = $formFieldsMap[$fieldName]['type'] ?? 'text';
			
			if($fieldType === 'textarea'){
				$fieldValue = nl2br($fieldValue);
			} elseif($fieldType === 'email' && filter_var($fieldValue, FILTER_VALIDATE_EMAIL)){
				$fieldValue = '<a href="mailto:' . $fieldValue . '" style="color: #3b82f6;">' . $fieldValue . '</a>';
			}
		}
		
		$cel_aux = modelo_var_troca($cel_aux, Array(
			'#label#' => $fieldLabel,
			'#value#' => $fieldValue,
			'#valor#' => $fieldValue,
		));
		$mensagem = modelo_var_in($mensagem,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	$mensagem = modelo_var_troca($mensagem,'<!-- '.$cel_nome.' -->','');
	
	// Processar imagens para embedding
	gestor_incluir_biblioteca('formulario');
	$resultadoImagens = formulario_email_processar_imagens($mensagem);
	$mensagem = $resultadoImagens['html'];
	$imagens = $resultadoImagens['imagens'];
	
	// Configurar remetente com 2 níveis: fields_schema -> $_CONFIG
	$emailData = [];
	if($formDefinition && isset($schema['email']) && is_array($schema['email'])){
		$emailData = $schema['email'];
	}
	$responderPara = !empty($emailData) && isset($emailData['reply_to']) ? $emailData['reply_to'] : (!empty($_CONFIG['email']['sender']['replyTo']) ? $_CONFIG['email']['sender']['replyTo'] : null);
	$responderParaNome = !empty($emailData) && isset($emailData['reply_to_name']) ? $emailData['reply_to_name'] : (!empty($_CONFIG['email']['sender']['replyToName']) ? $_CONFIG['email']['sender']['replyToName'] : null);
	
	// Assunto do email
	$subjectPrefix = gestor_variaveis(['id' => 'email-subject-prefix', 'modulo' => $_GESTOR['modulo-id']]);
	$assunto = $subjectPrefix . $submission['name'];
	
	// Enviar email
	gestor_incluir_biblioteca('comunicacao');
	
	$emailEnviado = comunicacao_email(Array(
		'destinatarios' => Array(
			Array('email' => $reply_email, 'nome' => $submission['name'])
		),
		'remetente' => Array(
			'responderPara' => $responderPara,
			'responderParaNome' => $responderParaNome,
		),
		'mensagem' => Array(
			'assunto' => $assunto,
			'htmlCompleto' => $mensagem,
			'imagens' => $imagens ?? null,
		),
	));
	
	// Registrar resposta no histórico
	$responseEntry = Array(
		'message' => $reply_message,
		'date' => date('Y-m-d H:i:s'),
		'email' => $reply_email,
		'status' => $emailEnviado ? 'sent' : 'failed',
	);
	
	if(!isset($fieldsData['responses'])) $fieldsData['responses'] = [];
	$fieldsData['responses'][] = $responseEntry;
	
	// Atualizar fields_values e form_status
	banco_update_campo('fields_values', json_encode($fieldsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	banco_update_campo('form_status', 'responded');
	banco_update_executar('forms_submissions', "WHERE id_forms_submissions='" . banco_escape_field($id_numerico) . "'");
	
	if($emailEnviado){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'success',
			'message' => gestor_variaveis(['id' => 'success-msg-reply-sent', 'modulo' => $_GESTOR['modulo-id']]),
		);
	} else {
		$_GESTOR['ajax-json'] = Array(
			'status' => 'warning',
			'message' => gestor_variaveis(['id' => 'warning-msg-reply-recorded-email-error', 'modulo' => $_GESTOR['modulo-id']]),
		);
	}
}


// ==== Start

function forms_submissions_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'forms-process': forms_submissions_ajax_forms_process(); break;
			case 'update-status': forms_submissions_ajax_update_status(); break;
			case 'reply': forms_submissions_ajax_reply(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		forms_submissions_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'visualizar': forms_submissions_visualizar(); break;
		}
		
		interface_finalizar();
	}
}

forms_submissions_start();

?>