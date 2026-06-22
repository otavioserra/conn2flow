<?php

global $_GESTOR;

$_GESTOR['modulo-id'] = 'forms';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/forms.json'), true);

// ===== Funções Auxiliares

function forms_normalize_array($array) {
	if (is_array($array)) {
		ksort($array);
		foreach ($array as $key => $value) {
			$array[$key] = forms_normalize_array($value);
		}
	}
	return $array;
}

function forms_schema_default() {
	return [
		'form_action' => '',
		'access_max_simple' => '',
		'access_max' => '',
		'force_recaptcha' => false,
		'email' => [
			'recipients' => '',
			'reply_to' => '',
			'reply_to_name' => '',
			'subject' => '',
			'message_component' => '',
		],
		'redirects' => [
			'success' => ['type' => 'url', 'path' => ''],
			'error' => ['type' => 'url', 'path' => ''],
		],
		'fields' => [],
		'template_id' => '',
	];
}

function forms_schema_decode($fields_schema, $template_id = '') {
	$schema = json_decode($fields_schema ?: '{}', true);
	if (!is_array($schema)) {
		$schema = [];
	}
	$schema = array_replace_recursive(forms_schema_default(), $schema);
	if (empty($schema['template_id']) && $template_id !== '') {
		$schema['template_id'] = $template_id;
	}
	if (!isset($schema['fields']) || !is_array($schema['fields'])) {
		$schema['fields'] = [];
	}
	return $schema;
}

function forms_item_variables() {
	return [
		['id' => 'label'],
		['id' => 'name'],
		['id' => 'placeholder'],
		['id' => 'type'],
		['id' => 'required'],
		['id' => 'options'],
		['id' => 'form_id', 'global' => true],
		['id' => 'form_action', 'global' => true],
	];
}

function forms_convert_text_vars_to_storage($value) {
	global $_GESTOR;

	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];

	return preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $value ?? '');
}

function forms_convert_storage_vars_to_text($value) {
	global $_GESTOR;

	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];

	return preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $value ?? '');
}

function forms_template_options($selected_id = null, $has_custom_code = false) {
	global $_GESTOR;

	$templates = banco_select_name(
		banco_campos_virgulas(['nome', 'id', 'framework_css']),
		'templates',
		"WHERE status='A'"
		.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
		.' AND target="forms"'
		.' ORDER BY nome ASC'
	);

	$options = '';
	if ($templates) {
		foreach ($templates as $template) {
			$is_selected = ($selected_id && $template['id'] == $selected_id);
			$framework = $template['framework_css'] ?? '';
			$selected_original = ($is_selected && !$has_custom_code) ? ' selected' : '';
			$options .= '<option value="'.$template['id'].'" data-framework="'.$framework.'"'.$selected_original.'>'.$template['nome'].'</option>';
			if ($is_selected && $has_custom_code) {
				$options .= '<option value="'.$template['id'].'-modificado" data-framework="'.$framework.'" selected>'.$template['nome'].' - (Modificado)</option>';
			}
		}
	}

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#template_placeholder_option#', gestor_variaveis(['modulo' => 'admin-templates', 'id' => 'form-name-placeholder']));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#template_id_options#', $options);
}

function forms_prepare_editor_page($schema, $html = '', $css = '', $css_compiled = '', $html_extra_head = '', $modo = 'adicionarEditar') {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$has_custom_code = (!empty($html) || !empty($css));

	forms_template_options($schema['template_id'] ?? '', $has_custom_code);

	$_GESTOR['pagina'] .= '<script>var forms_initial_schema = '.json_encode($schema, JSON_UNESCAPED_UNICODE).';</script>';

	$params = [
		'modulo' => $modulo,
		'alvo' => 'forms',
		'alvos_modelos' => 'forms',
		'target_variables' => forms_item_variables(),
		'html' => $html,
		'html_extra_head' => $html_extra_head,
	];
	$params[$modo] = true;

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#html-editor#', html_editor_componente($params));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-html#', $html);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-css#', $css);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-css-compiled#', $css_compiled);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-html-extra-head#', $html_extra_head);
}

function forms_insert_common_fields(&$campos, $id, $usuario) {
	global $_GESTOR;

	$campo_sem_aspas_simples = false;
	$fields_schema = $_REQUEST['fields_schema'] ?? '{}';
	$schema = forms_schema_decode($fields_schema, $_REQUEST['template_id'] ?? '');
	$fields_schema = json_encode($schema, JSON_UNESCAPED_UNICODE);

	$campos[] = ['id_users', $usuario['id_usuarios'], $campo_sem_aspas_simples];
	if (isset($_REQUEST['name']) && $_REQUEST['name'] !== '') $campos[] = ['name', banco_escape_field($_REQUEST['name'])];
	$campos[] = ['id', $id, $campo_sem_aspas_simples];
	if (isset($_REQUEST['description']) && $_REQUEST['description'] !== '') $campos[] = ['description', banco_escape_field($_REQUEST['description'])];
	if (isset($_REQUEST['module']) && $_REQUEST['module'] !== '') $campos[] = ['module', banco_escape_field($_REQUEST['module'])];
	if (isset($_REQUEST['template_id']) && $_REQUEST['template_id'] !== '') $campos[] = ['template_id', banco_escape_field($_REQUEST['template_id'])];
	$campos[] = ['fields_schema', banco_escape_field($fields_schema)];

	$_REQUEST['css_compiled'] = forms_convert_text_vars_to_storage($_REQUEST['css_compiled'] ?? '');
	$_REQUEST['html_extra_head'] = forms_convert_text_vars_to_storage($_REQUEST['html_extra_head'] ?? '');
	foreach (['html', 'css', 'css_compiled', 'html_extra_head'] as $field) {
		if (isset($_REQUEST[$field]) && $_REQUEST[$field] !== '') {
			$campos[] = [$field, banco_escape_field($_REQUEST[$field])];
		}
	}

	$campos[] = ['language ', $_GESTOR['linguagem-codigo'], $campo_sem_aspas_simples];
}

function forms_meta_dados($retorno_bd, $modulo) {
	$metaDados = [];
	if (isset($retorno_bd[$modulo['tabela']['data_criacao']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-date-start']), 'dado' => interface_formatar_dado(['dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'])];
	if (isset($retorno_bd[$modulo['tabela']['data_modificacao']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-date-modification']), 'dado' => interface_formatar_dado(['dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'])];
	if (isset($retorno_bd[$modulo['tabela']['versao']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-version']), 'dado' => $retorno_bd[$modulo['tabela']['versao']]];
	if (isset($retorno_bd[$modulo['tabela']['status']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-status']), 'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(['modulo' => 'interface','id' => 'field-status-active']).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(['modulo' => 'interface','id' => 'field-status-inactive']).'</b></div>' : '')];
	return $metaDados;
}

function forms_interface_finalizar($opcao, $id = null, $metaDados = [], $status_atual = '', $module = '') {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$config = [
		'formulario' => [
			'validacao' => [
				[
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label']),
					'identificador' => 'name',
				],
			],
			'campos' => [
				[
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'module',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'three column',
					'placeholder' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder']),
					'tabela' => [
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $module,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					],
				],
			],
		],
	];

	if ($opcao !== 'adicionar') {
		$config['id'] = $id;
		$config['metaDados'] = $metaDados;
		$config['banco'] = [
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		];
		$config['botoes'] = [
			'adicionar' => [
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/',
				'rotulo' => gestor_variaveis(['modulo' => 'interface','id' => 'label-button-insert']),
				'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-insert']),
				'icon' => 'plus circle',
				'cor' => 'blue',
			],
			'clonar' => [
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/clonar/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(['modulo' => 'interface','id' => 'label-button-clone']),
				'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-clone']),
				'icon' => 'clone',
				'cor' => 'teal',
			],
			'status' => [
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A').'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(['modulo' => 'interface','id' => 'label-button-desactive']) : gestor_variaveis(['modulo' => 'interface','id' => 'label-button-active'])),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-desactive']) : gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-active'])),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash'),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown'),
			],
			'excluir' => [
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(['modulo' => 'interface','id' => 'label-button-delete']),
				'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-delete']),
				'icon' => 'trash alternate',
				'cor' => 'red',
			],
		];
	}

	$_GESTOR['interface'][$opcao]['finalizar'] = $config;
}

// ===== Funções Principais

function forms_adicionar() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	if (isset($_GESTOR['adicionar-banco'])) {
		$usuario = gestor_usuario();
		interface_validacao_campos_obrigatorios([
			'campos' => [
				[
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label']),
				],
			],
		]);

		$id = banco_identificador([
			'id' => banco_escape_field($_REQUEST['name']),
			'tabela' => [
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			],
		]);

		$campos = [];
		forms_insert_common_fields($campos, $id, $usuario);
		banco_insert_name($campos, $modulo['tabela']['nome']);
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	$schema = forms_schema_default();
	forms_prepare_editor_page($schema);
	gestor_pagina_javascript_incluir();
	gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>');
	forms_interface_finalizar('adicionar');
}

function forms_editar() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$id = $_GESTOR['modulo-registro-id'];

	$camposBanco = ['id', 'id_forms', 'name', 'description', 'module', 'template_id', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head', 'status'];
	$camposBancoEditar = array_merge($camposBanco, [$modulo['tabela']['status'], $modulo['tabela']['versao'], $modulo['tabela']['data_criacao'], $modulo['tabela']['data_modificacao']]);

	if (isset($_GESTOR['atualizar-banco'])) {
		if (!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBanco),
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'"
		)) {
			interface_alerta(['redirect' => true, 'msg' => gestor_variaveis(['modulo' => 'interface','id' => 'alert-database-field-before-error'])]);
			gestor_redirecionar_raiz();
		}

		interface_validacao_campos_obrigatorios([
			'campos' => [
				['regra' => 'texto-obrigatorio', 'campo' => 'name', 'label' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label'])],
			],
		]);

		$editar = [
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'",
		];
		$alteracoes = [];
		$backups = [];

		if (banco_select_campos_antes('name') != ($_REQUEST['name'] ?? null)) {
			$editar['dados'][] = "name='".banco_escape_field($_REQUEST['name'])."'";
			if (!isset($_REQUEST['_gestor-nao-alterar-id'])) $alterar_id = true;
			$alteracoes[] = ['campo' => 'form-name-label', 'valor_antes' => banco_select_campos_antes('name'), 'valor_depois' => banco_escape_field($_REQUEST['name'])];
		}

		if (isset($alterar_id)) {
			$rows = banco_select_name(banco_campos_virgulas([$modulo['tabela']['id_numerico']]), $modulo['tabela']['nome'], "WHERE ".$modulo['tabela']['id']."='".$id."'");
			if ($rows) {
				$id_novo = banco_identificador([
					'id' => banco_escape_field($_REQUEST['name']),
					'tabela' => [
						'nome' => $modulo['tabela']['nome'],
						'campo' => $modulo['tabela']['id'],
						'id_nome' => $modulo['tabela']['id_numerico'],
						'id_valor' => $rows[0][$modulo['tabela']['id_numerico']],
						'where' => "language='".$_GESTOR['linguagem-codigo']."'",
					],
				]);
				$editar['dados'][] = $modulo['tabela']['id']."='".$id_novo."'";
				$alteracoes[] = ['campo' => 'field-id', 'valor_antes' => $id, 'valor_depois' => $id_novo];
				$_GESTOR['modulo-registro-id'] = $id_novo;
			}
		}

		foreach (['module', 'template_id', 'description'] as $campo_nome) {
			$request_value = $_REQUEST[$campo_nome] ?? '';
			if (banco_select_campos_antes($campo_nome) != $request_value) {
				$editar['dados'][] = $campo_nome."='".banco_escape_field($request_value)."'";
				$alteracoes[] = ['campo' => 'form-'.$campo_nome.'-label'];
				if (banco_select_campos_antes($campo_nome)) $backups[] = ['campo' => $campo_nome, 'valor' => addslashes(banco_select_campos_antes($campo_nome))];
			}
		}

		if (isset($_REQUEST['fields_schema'])) {
			$request_schema = forms_schema_decode($_REQUEST['fields_schema'], $_REQUEST['template_id'] ?? '');
			$request_valor = json_encode($request_schema, JSON_UNESCAPED_UNICODE);
			$valor_request = forms_normalize_array(json_decode($request_valor, true));
			$valor_banco = forms_normalize_array(json_decode(banco_select_campos_antes('fields_schema'), true));
			if ($valor_banco !== $valor_request) {
				$editar['dados'][] = "fields_schema='".banco_escape_field($request_valor)."'";
				$alteracoes[] = ['campo' => 'form-fields_schema-label'];
			}
		}

		$_REQUEST['css_compiled'] = forms_convert_text_vars_to_storage($_REQUEST['css_compiled'] ?? '');
		$_REQUEST['html_extra_head'] = forms_convert_text_vars_to_storage($_REQUEST['html_extra_head'] ?? '');
		foreach (['html', 'css', 'css_compiled', 'html_extra_head'] as $campo_nome) {
			if (isset($_REQUEST[$campo_nome]) && banco_select_campos_antes($campo_nome) != $_REQUEST[$campo_nome]) {
				$editar['dados'][] = $campo_nome."='".banco_escape_field($_REQUEST[$campo_nome])."'";
				$alteracoes[] = ['campo' => 'form-'.$campo_nome.'-label'];
				if (banco_select_campos_antes($campo_nome)) $backups[] = ['campo' => $campo_nome, 'valor' => addslashes(banco_select_campos_antes($campo_nome))];
			}
		}

		if (isset($editar['dados'])) {
			$editar['dados'][] = 'user_modified = 1';
			$editar['dados'][] = $modulo['tabela']['versao'].' = '.$modulo['tabela']['versao'].' + 1';
			$editar['dados'][] = $modulo['tabela']['data_modificacao'].'=NOW()';
			banco_update(banco_campos_virgulas($editar['dados']), $editar['tabela'], $editar['extra']);

			foreach ($backups as $backup) {
				interface_backup_campo_incluir([
					'id_numerico' => interface_modulo_variavel_valor(['variavel' => $modulo['tabela']['id_numerico']]),
					'versao' => interface_modulo_variavel_valor(['variavel' => $modulo['tabela']['versao']]),
					'campo' => $backup['campo'],
					'valor' => $backup['valor'],
				]);
			}

			interface_historico_incluir([
				'id' => $id,
				'tabela' => [
					'nome' => $modulo['tabela']['nome'],
					'id_numerico' => $modulo['tabela']['id_numerico'],
					'versao' => $modulo['tabela']['versao'],
				],
				'alteracoes' => $alteracoes,
			]);
		}

		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}

	$retorno_bd = banco_select_editar(
		banco_campos_virgulas($camposBancoEditar),
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'"
	);

	if (!$_GESTOR['banco-resultado']) gestor_redirecionar_raiz();

	$name = $retorno_bd['name'] ?? '';
	$description = $retorno_bd['description'] ?? '';
	$module = $retorno_bd['module'] ?? '';
	$template_id = $retorno_bd['template_id'] ?? '';
	$fields_schema = $retorno_bd['fields_schema'] ?? '';
	$html = $retorno_bd['html'] ?? '';
	$css = $retorno_bd['css'] ?? '';
	$css_compiled = forms_convert_storage_vars_to_text($retorno_bd['css_compiled'] ?? '');
	$html_extra_head = forms_convert_storage_vars_to_text($retorno_bd['html_extra_head'] ?? '');
	$schema = forms_schema_decode($fields_schema, $template_id);

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#name#', $name);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#id#', $id);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#description#', $description);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#fields_schema#', json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

	forms_prepare_editor_page($schema, $html, $css, $css_compiled, $html_extra_head, 'editar');
	gestor_pagina_javascript_incluir();
	gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>');
	forms_interface_finalizar('editar', $id, forms_meta_dados($retorno_bd, $modulo), $retorno_bd[$modulo['tabela']['status']] ?? '', $module);
}

function forms_clonar() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$id = $_GESTOR['modulo-registro-id'];
	$camposBanco = ['id', 'id_forms', 'description', 'module', 'template_id', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head', 'status'];
	$camposBancoClonar = array_merge($camposBanco, [$modulo['tabela']['status'], $modulo['tabela']['versao'], $modulo['tabela']['data_criacao'], $modulo['tabela']['data_modificacao']]);

	if (isset($_GESTOR['adicionar-banco'])) {
		$usuario = gestor_usuario();
		interface_validacao_campos_obrigatorios([
			'campos' => [
				['regra' => 'texto-obrigatorio', 'campo' => 'name', 'label' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label'])],
			],
		]);

		$new_id = banco_identificador([
			'id' => banco_escape_field($_REQUEST['name']),
			'tabela' => [
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			],
		]);

		$campos = [];
		forms_insert_common_fields($campos, $new_id, $usuario);
		banco_insert_name($campos, $modulo['tabela']['nome']);
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$new_id);
	}

	$retorno_bd = banco_select_editar(
		banco_campos_virgulas($camposBancoClonar),
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'"
	);

	if (!$_GESTOR['banco-resultado']) gestor_redirecionar_raiz();

	$description = $retorno_bd['description'] ?? '';
	$module = $retorno_bd['module'] ?? '';
	$template_id = $retorno_bd['template_id'] ?? '';
	$schema = forms_schema_decode($retorno_bd['fields_schema'] ?? '', $template_id);
	$html = $retorno_bd['html'] ?? '';
	$css = $retorno_bd['css'] ?? '';
	$css_compiled = forms_convert_storage_vars_to_text($retorno_bd['css_compiled'] ?? '');
	$html_extra_head = forms_convert_storage_vars_to_text($retorno_bd['html_extra_head'] ?? '');

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#id#', $id);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#description#', $description);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#fields_schema#', json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#html-original#', htmlspecialchars($html, ENT_QUOTES));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#css-original#', htmlspecialchars($css, ENT_QUOTES));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#css-compiled-original#', htmlspecialchars($css_compiled, ENT_QUOTES));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#html-extra-head-original#', htmlspecialchars($html_extra_head, ENT_QUOTES));

	forms_prepare_editor_page($schema, $html, $css, $css_compiled, $html_extra_head, 'adicionarEditar');
	gestor_pagina_javascript_incluir();
	gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>');
	forms_interface_finalizar('clonar', $id, forms_meta_dados($retorno_bd, $modulo), $retorno_bd[$modulo['tabela']['status']] ?? '', $module);
}

function forms_visualizar() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$id = $_GESTOR['modulo-registro-id'];
	$camposBanco = ['id', 'id_forms', 'name', 'description', 'module', 'fields_schema', 'status'];
	$camposBancoVisualizar = array_merge($camposBanco, [$modulo['tabela']['status'], $modulo['tabela']['versao'], $modulo['tabela']['data_criacao'], $modulo['tabela']['data_modificacao']]);

	$retorno_bd = banco_select_editar(
		banco_campos_virgulas($camposBancoVisualizar),
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'"
	);

	if (!$_GESTOR['banco-resultado']) gestor_redirecionar_raiz();

	$fields_schema = json_encode(forms_schema_decode($retorno_bd['fields_schema'] ?? ''), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#name#', $retorno_bd['name'] ?? '');
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#id#', $id);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#module#', $retorno_bd['module'] ?? '');
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#description#', $retorno_bd['description'] ?? '');
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#fields_schema#', $fields_schema);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#forms-info-definition#', gestor_componente(['id' => 'forms-info-definition', 'modulo' => $_GESTOR['modulo-id']]));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#form_info#', json_encode($modulo['resources'][$_GESTOR['linguagem-codigo']]['form_info'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

	gestor_pagina_javascript_incluir();
	$_GESTOR['interface']['visualizar']['finalizar'] = [
		'campoTitulo' => $modulo['tabela']['nome_especifico'],
		'id' => $id,
		'metaDados' => forms_meta_dados($retorno_bd, $modulo),
		'banco' => [
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		],
		'botoes' => [
			'adicionar' => [
				'url' => '../adicionar/',
				'rotulo' => gestor_variaveis(['modulo' => 'interface','id' => 'label-button-insert']),
				'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-insert']),
				'icon' => 'plus circle',
				'cor' => 'blue',
			],
		],
	];
}

function forms_interfaces_padroes() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	switch ($_GESTOR['opcao']) {
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = [
				'banco' => [
					'nome' => $modulo['tabela']['nome'],
					'campos' => ['name', 'description', 'module', $modulo['tabela']['data_modificacao']],
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "language='".$_GESTOR['linguagem-codigo']."'",
				],
				'tabela' => [
					'colunas' => [
						['id' => 'name', 'nome' => gestor_variaveis(['modulo' => 'interface','id' => 'field-name']), 'ordenar' => 'asc'],
						['id' => 'description', 'nome' => gestor_variaveis(['modulo' => 'interface','id' => 'field-description']), 'ordenar' => 'asc'],
						[
							'id' => 'module',
							'nome' => gestor_variaveis(['modulo' => 'modulos','id' => 'module-name']),
							'formatar' => [
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">N/A</span>',
								'tabela' => [
									'nome' => 'modulos',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
								],
							],
						],
						['id' => $modulo['tabela']['data_modificacao'], 'nome' => gestor_variaveis(['modulo' => 'interface','id' => 'field-date-modification']), 'formatar' => 'dataHora', 'nao_procurar' => true],
					],
				],
				'opcoes' => [
					// 'visualizar' => ['url' => 'view/', 'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-view']), 'icon' => 'file alternate outline', 'cor' => 'basic brown'],
					'editar' => ['url' => 'editar/', 'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-edit']), 'icon' => 'edit', 'cor' => 'basic blue'],
					'clonar' => ['url' => 'clonar/', 'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-clone']), 'icon' => 'clone', 'cor' => 'basic teal'],
					'ativar' => ['opcao' => 'status', 'status_atual' => 'I', 'status_mudar' => 'A', 'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-active']), 'icon' => 'eye slash', 'cor' => 'basic brown'],
					'desativar' => ['opcao' => 'status', 'status_atual' => 'A', 'status_mudar' => 'I', 'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-desactive']), 'icon' => 'eye', 'cor' => 'basic green'],
					'excluir' => ['opcao' => 'excluir', 'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-delete']), 'icon' => 'trash alternate', 'cor' => 'basic red'],
				],
				'botoes' => [
					'adicionar' => ['url' => 'adicionar/', 'rotulo' => gestor_variaveis(['modulo' => 'interface','id' => 'label-button-insert']), 'tooltip' => gestor_variaveis(['modulo' => 'interface','id' => 'tooltip-button-insert']), 'icon' => 'plus circle', 'cor' => 'blue'],
				],
			];
		break;
	}
}

function forms_test_email_page() {
	global $_GESTOR;

	$testEmailPage = gestor_componente([
		'id' => 'base-email',
		'modulo' => 'contatos',
	]);

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#test-email-page#', $testEmailPage);
}

// ==== Ajax

function forms_ajax_template_load() {
	global $_GESTOR;

	$template_id = $_REQUEST['params']['template_id'] ?? '';
	$template = banco_select([
		'unico' => true,
		'tabela' => 'templates',
		'campos' => ['nome', 'html', 'css', 'framework_css'],
		'extra' => "WHERE id='".banco_escape_field($template_id)."' AND target='forms' AND language='".$_GESTOR['linguagem-codigo']."' AND status='A'",
	]);

	if (!$template) {
		$_GESTOR['ajax-json'] = ['status' => 'Erro', 'message' => 'Modelo de formulário não encontrado.'];
		return;
	}

	$template['html'] = forms_convert_storage_vars_to_text($template['html'] ?? '');
	$_GESTOR['ajax-json'] = [
		'status' => 'Ok',
		'modelo' => ['name' => $template['nome'], 'id' => $template_id],
		'html' => $template['html'] ?? '',
		'css' => $template['css'] ?? '',
		'framework_css' => $template['framework_css'] ?? '',
		'campos' => forms_item_variables(),
	];
}

function forms_ajax_widget_preview() {
	global $_GESTOR;

	$html_input = $_REQUEST['params']['html'] ?? ($_REQUEST['html'] ?? '');
	$css_input = $_REQUEST['params']['css'] ?? ($_REQUEST['css'] ?? '');
	$form_id = $_REQUEST['params']['form_id'] ?? ($_REQUEST['form_id'] ?? '');
	$fields_schema_input = $_REQUEST['params']['fields_schema'] ?? ($_REQUEST['fields_schema'] ?? '{}');

	if (!function_exists('forms_widget_render_inline')) {
		require_once(__DIR__.'/forms.widget.php');
	}

	$rendered = forms_widget_render_inline([
		'html' => $html_input,
		'css' => $css_input,
		'form_id' => $form_id,
		'fields_schema' => $fields_schema_input,
	]);

	$_GESTOR['ajax-json'] = ['status' => 'Ok', 'html' => $rendered];
}

// ==== Start

function forms_start() {
	global $_GESTOR;

	gestor_incluir_bibliotecas();

	if ($_GESTOR['ajax']) {
		interface_ajax_iniciar();

		switch ($_GESTOR['ajax-opcao']) {
			case 'template-load': forms_ajax_template_load(); break;
			case 'widget-preview': forms_ajax_widget_preview(); break;
		}

		interface_ajax_finalizar();
	} else {
		forms_interfaces_padroes();
		interface_iniciar();

		switch ($_GESTOR['opcao']) {
			case 'visualizar': forms_visualizar(); break;
			case 'adicionar': forms_adicionar(); break;
			case 'editar': forms_editar(); break;
			case 'clonar': forms_clonar(); break;
			case 'test-email-page': forms_test_email_page(); break;
		}

		interface_finalizar();
	}
}

forms_start();
