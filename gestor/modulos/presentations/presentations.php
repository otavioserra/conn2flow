<?php

global $_GESTOR;

$_GESTOR['modulo-id'] = 'presentations';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/presentations.json'), true);

if (!function_exists('presentations_schema_default')) {
	require_once(__DIR__.'/presentations.widget.php');
}

// ===== Funções Auxiliares

function presentations_normalize_array($array) {
	if (is_array($array)) {
		ksort($array);
		foreach ($array as $key => $value) {
			$array[$key] = presentations_normalize_array($value);
		}
	}
	return $array;
}

function presentations_convert_text_vars_to_storage($value) {
	global $_GESTOR;

	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];

	return preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $value ?? '');
}

function presentations_convert_storage_vars_to_text($value) {
	global $_GESTOR;

	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];

	return preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $value ?? '');
}

function presentations_template_options($selected_id = null, $has_custom_code = false) {
	global $_GESTOR;

	$templates = banco_select_name(
		banco_campos_virgulas(['nome', 'id', 'framework_css']),
		'templates',
		"WHERE status='A'"
		.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
		.' AND target="presentations"'
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
				$options .= '<option value="'.$template['id'].'-modificado" data-framework="'.$framework.'" selected>'.$template['nome'].' - '.gestor_variaveis(['modulo' => $_GESTOR['modulo-id'], 'id' => 'template-modified-suffix']).'</option>';
			}
		}
	}

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#template_placeholder_option#', gestor_variaveis(['modulo' => 'admin-templates', 'id' => 'form-name-placeholder']));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#template_id_options#', $options);
}

/**
 * Monta o editor HTML e entrega ao JavaScript da tela o schema, os textos e a tag do controlador
 * público do widget (a pré-visualização roda o mesmo script que o site).
 */
function presentations_prepare_editor_page($schema, $html = '', $css = '', $css_compiled = '', $html_extra_head = '', $modo = 'adicionarEditar') {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$has_custom_code = (!empty($html) || !empty($css));

	presentations_template_options($schema['template_id'] ?? '', $has_custom_code);

	// Quadro de slides (req-209): seletor de imagens do gerenciador de arquivos, como no `galleries`,
	// e arrastar para reordenar.
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-iframe',
			'modal-alerta',
		)
	));
	if(!function_exists('assets_externos_incluir') && !empty($_GESTOR['bibliotecas-path'])){
		require_once($_GESTOR['bibliotecas-path'].'assets-externos.php');
	}
	assets_externos_incluir('sortablejs');
	gestor_pagina_javascript_incluir(['tipo' => 'slides', 'modulo_id' => 'presentations', 'versao' => presentations_get_version()]);

	gestor_js_variavel_incluir('presentationsAdmin', [
		'imagepick' => [
			'url' => $_GESTOR['url-full'].'admin-arquivos/?paginaIframe=sim',
			'head' => gestor_variaveis(['modulo' => 'interface', 'id' => 'widget-image-modal-head']),
			'cancel' => gestor_variaveis(['modulo' => 'interface', 'id' => 'widget-image-modal-cancel']),
		],
		'schema' => $schema,
		'widgetScript' => (string)gestor_pagina_javascript_incluir(['tipo' => 'widget', 'modulo_id' => 'presentations', 'versao' => presentations_get_version()], false, true),
		'textos' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'], 'conjunto' => true, 'padrao' => 'js-']),
	]);

	$params = [
		'modulo' => $modulo,
		'alvo' => 'presentations',
		'alvos_modelos' => 'presentations',
		'html' => $html,
		'html_extra_head' => $html_extra_head,
		'widget_js_include' => ['presentations' => true],
	];
	$params[$modo] = true;

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#html-editor#', html_editor_componente($params));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-html#', $html);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-css#', $css);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-css-compiled#', $css_compiled);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-html-extra-head#', $html_extra_head);
}

function presentations_template_id_request() {
	$template_id = (string)($_REQUEST['template_id'] ?? '');
	// O sufixo "-modificado" só existe na tela, para sinalizar edição sobre o modelo.
	if (substr($template_id, -11) === '-modificado') $template_id = substr($template_id, 0, -11);
	return $template_id;
}

function presentations_insert_common_fields(&$campos, $id, $usuario) {
	global $_GESTOR;

	$campo_sem_aspas_simples = false;
	$schema = presentations_schema_decode($_REQUEST['fields_schema'] ?? '{}', presentations_template_id_request());
	$fields_schema = json_encode($schema, JSON_UNESCAPED_UNICODE);

	$campos[] = ['id_usuarios', $usuario['id_usuarios'], $campo_sem_aspas_simples];
	if (isset($_REQUEST['name']) && $_REQUEST['name'] !== '') $campos[] = ['name', banco_escape_field($_REQUEST['name'])];
	$campos[] = ['id', $id, $campo_sem_aspas_simples];
	$campos[] = ['fields_schema', banco_escape_field($fields_schema)];

	$_REQUEST['css_compiled'] = presentations_convert_text_vars_to_storage($_REQUEST['css_compiled'] ?? '');
	$_REQUEST['html_extra_head'] = presentations_convert_text_vars_to_storage($_REQUEST['html_extra_head'] ?? '');
	foreach (['html', 'css', 'css_compiled', 'html_extra_head'] as $field) {
		if (isset($_REQUEST[$field]) && $_REQUEST[$field] !== '') {
			$campos[] = [$field, banco_escape_field($_REQUEST[$field])];
		}
	}

	$campos[] = ['language ', $_GESTOR['linguagem-codigo'], $campo_sem_aspas_simples];
}

function presentations_meta_dados($retorno_bd, $modulo) {
	$metaDados = [];
	if (isset($retorno_bd[$modulo['tabela']['data_criacao']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-date-start']), 'dado' => interface_formatar_dado(['dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'])];
	if (isset($retorno_bd[$modulo['tabela']['data_modificacao']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-date-modification']), 'dado' => interface_formatar_dado(['dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'])];
	if (isset($retorno_bd[$modulo['tabela']['versao']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-version']), 'dado' => $retorno_bd[$modulo['tabela']['versao']]];
	if (isset($retorno_bd[$modulo['tabela']['status']])) $metaDados[] = ['titulo' => gestor_variaveis(['modulo' => 'interface','id' => 'field-status']), 'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(['modulo' => 'interface','id' => 'field-status-active']).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(['modulo' => 'interface','id' => 'field-status-inactive']).'</b></div>' : '')];
	return $metaDados;
}

function presentations_interface_finalizar($opcao, $id = null, $metaDados = [], $status_atual = '') {
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

function presentations_validar_nome() {
	global $_GESTOR;

	interface_validacao_campos_obrigatorios([
		'campos' => [
			['regra' => 'texto-obrigatorio', 'campo' => 'name', 'label' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label'])],
		],
	]);
}

function presentations_novo_identificador($modulo, $id_valor = null) {
	global $_GESTOR;

	$tabela = [
		'nome' => $modulo['tabela']['nome'],
		'campo' => $modulo['tabela']['id'],
		'id_nome' => $modulo['tabela']['id_numerico'],
		'where' => "language='".$_GESTOR['linguagem-codigo']."'",
	];
	if ($id_valor !== null) $tabela['id_valor'] = $id_valor;

	return banco_identificador(['id' => banco_escape_field($_REQUEST['name']), 'tabela' => $tabela]);
}

// ===== Funções Principais

function presentations_adicionar() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	if (isset($_GESTOR['adicionar-banco'])) {
		$usuario = gestor_usuario();
		presentations_validar_nome();

		$id = presentations_novo_identificador($modulo);

		$campos = [];
		presentations_insert_common_fields($campos, $id, $usuario);
		banco_insert_name($campos, $modulo['tabela']['nome']);
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	presentations_prepare_editor_page(presentations_schema_default());
	gestor_pagina_javascript_incluir();
	presentations_interface_finalizar('adicionar');
}

function presentations_editar() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$id = $_GESTOR['modulo-registro-id'];

	$camposBanco = ['id', 'id_presentations', 'name', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head'];
	$camposBancoEditar = array_merge($camposBanco, [$modulo['tabela']['status'], $modulo['tabela']['versao'], $modulo['tabela']['data_criacao'], $modulo['tabela']['data_modificacao']]);
	$where = "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'";

	if (isset($_GESTOR['atualizar-banco'])) {
		if (!banco_select_campos_antes_iniciar(banco_campos_virgulas($camposBanco), $modulo['tabela']['nome'], $where)) {
			interface_alerta(['redirect' => true, 'msg' => gestor_variaveis(['modulo' => 'interface','id' => 'alert-database-field-before-error'])]);
			gestor_redirecionar_raiz();
		}

		presentations_validar_nome();

		$editar = ['tabela' => $modulo['tabela']['nome'], 'extra' => $where];
		$alteracoes = [];
		$backups = [];

		if (banco_select_campos_antes('name') != ($_REQUEST['name'] ?? null)) {
			$editar['dados'][] = "name='".banco_escape_field($_REQUEST['name'])."'";
			if (!isset($_REQUEST['_gestor-nao-alterar-id'])) $alterar_id = true;
			$alteracoes[] = ['campo' => 'form-name-label', 'valor_antes' => banco_select_campos_antes('name'), 'valor_depois' => banco_escape_field($_REQUEST['name'])];
		}

		if (isset($alterar_id)) {
			$rows = banco_select_name(banco_campos_virgulas([$modulo['tabela']['id_numerico']]), $modulo['tabela']['nome'], "WHERE ".$modulo['tabela']['id']."='".$id."' AND language='".$_GESTOR['linguagem-codigo']."'");
			if ($rows) {
				$id_novo = presentations_novo_identificador($modulo, $rows[0][$modulo['tabela']['id_numerico']]);
				$editar['dados'][] = $modulo['tabela']['id']."='".$id_novo."'";
				$alteracoes[] = ['campo' => 'field-id', 'valor_antes' => $id, 'valor_depois' => $id_novo];
				$_GESTOR['modulo-registro-id'] = $id_novo;
			}
		}

		if (isset($_REQUEST['fields_schema'])) {
			$request_schema = presentations_schema_decode($_REQUEST['fields_schema'], presentations_template_id_request());
			$request_valor = json_encode($request_schema, JSON_UNESCAPED_UNICODE);
			$valor_request = presentations_normalize_array(json_decode($request_valor, true));
			$valor_banco = presentations_normalize_array(json_decode((string)banco_select_campos_antes('fields_schema'), true));
			if ($valor_banco !== $valor_request) {
				$editar['dados'][] = "fields_schema='".banco_escape_field($request_valor)."'";
				$alteracoes[] = ['campo' => 'form-fields_schema-label'];
			}
		}

		$_REQUEST['css_compiled'] = presentations_convert_text_vars_to_storage($_REQUEST['css_compiled'] ?? '');
		$_REQUEST['html_extra_head'] = presentations_convert_text_vars_to_storage($_REQUEST['html_extra_head'] ?? '');
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

	$retorno_bd = banco_select_editar(banco_campos_virgulas($camposBancoEditar), $modulo['tabela']['nome'], $where);

	if (!$_GESTOR['banco-resultado']) gestor_redirecionar_raiz();

	$html = $retorno_bd['html'] ?? '';
	$css = $retorno_bd['css'] ?? '';
	$css_compiled = presentations_convert_storage_vars_to_text($retorno_bd['css_compiled'] ?? '');
	$html_extra_head = presentations_convert_storage_vars_to_text($retorno_bd['html_extra_head'] ?? '');
	$schema = presentations_schema_decode($retorno_bd['fields_schema'] ?? '');

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#name#', htmlspecialchars((string)($retorno_bd['name'] ?? ''), ENT_QUOTES, 'UTF-8'));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#id#', $id);

	presentations_prepare_editor_page($schema, $html, $css, $css_compiled, $html_extra_head, 'editar');
	gestor_pagina_javascript_incluir();
	presentations_interface_finalizar('editar', $id, presentations_meta_dados($retorno_bd, $modulo), $retorno_bd[$modulo['tabela']['status']] ?? '');
}

function presentations_clonar() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$id = $_GESTOR['modulo-registro-id'];
	$camposBanco = ['id', 'id_presentations', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head'];
	$camposBancoClonar = array_merge($camposBanco, [$modulo['tabela']['status'], $modulo['tabela']['versao'], $modulo['tabela']['data_criacao'], $modulo['tabela']['data_modificacao']]);

	if (isset($_GESTOR['adicionar-banco'])) {
		$usuario = gestor_usuario();
		presentations_validar_nome();

		$new_id = presentations_novo_identificador($modulo);

		$campos = [];
		presentations_insert_common_fields($campos, $new_id, $usuario);
		banco_insert_name($campos, $modulo['tabela']['nome']);
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$new_id);
	}

	$retorno_bd = banco_select_editar(
		banco_campos_virgulas($camposBancoClonar),
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'"
	);

	if (!$_GESTOR['banco-resultado']) gestor_redirecionar_raiz();

	$html = $retorno_bd['html'] ?? '';
	$css = $retorno_bd['css'] ?? '';
	$css_compiled = presentations_convert_storage_vars_to_text($retorno_bd['css_compiled'] ?? '');
	$html_extra_head = presentations_convert_storage_vars_to_text($retorno_bd['html_extra_head'] ?? '');
	$schema = presentations_schema_decode($retorno_bd['fields_schema'] ?? '');

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#id#', $id);

	presentations_prepare_editor_page($schema, $html, $css, $css_compiled, $html_extra_head, 'adicionarEditar');
	gestor_pagina_javascript_incluir();
	presentations_interface_finalizar('clonar', $id, presentations_meta_dados($retorno_bd, $modulo), $retorno_bd[$modulo['tabela']['status']] ?? '');
}

function presentations_interfaces_padroes() {
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	switch ($_GESTOR['opcao']) {
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = [
				'banco' => [
					'nome' => $modulo['tabela']['nome'],
					'campos' => ['name', $modulo['tabela']['data_modificacao']],
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "language='".$_GESTOR['linguagem-codigo']."'",
				],
				'tabela' => [
					'colunas' => [
						['id' => 'name', 'nome' => gestor_variaveis(['modulo' => 'interface','id' => 'field-name']), 'ordenar' => 'asc'],
						['id' => $modulo['tabela']['data_modificacao'], 'nome' => gestor_variaveis(['modulo' => 'interface','id' => 'field-date-modification']), 'formatar' => 'dataHora', 'nao_procurar' => true],
					],
				],
				'opcoes' => [
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

// ==== Ajax

function presentations_ajax_template_load() {
	global $_GESTOR;

	$template_id = $_REQUEST['params']['template_id'] ?? '';
	$template = banco_select([
		'unico' => true,
		'tabela' => 'templates',
		'campos' => ['nome', 'html', 'css', 'framework_css'],
		'extra' => "WHERE id='".banco_escape_field($template_id)."' AND target='presentations' AND language='".$_GESTOR['linguagem-codigo']."' AND status='A'",
	]);

	if (!$template) {
		$_GESTOR['ajax-json'] = ['status' => 'Erro', 'message' => gestor_variaveis(['modulo' => $_GESTOR['modulo-id'], 'id' => 'error-template-not-found'])];
		return;
	}

	$_GESTOR['ajax-json'] = [
		'status' => 'Ok',
		'modelo' => ['name' => $template['nome'], 'id' => $template_id],
		'html' => presentations_convert_storage_vars_to_text($template['html'] ?? ''),
		'css' => $template['css'] ?? '',
		'framework_css' => $template['framework_css'] ?? '',
	];
}

/** Renderiza o widget com o HTML, o CSS e o schema ainda não salvos, para a aba de pré-visualização. */
function presentations_ajax_widget_preview() {
	global $_GESTOR;

	$rendered = presentations_widget_render_inline([
		'html' => $_REQUEST['params']['html'] ?? ($_REQUEST['html'] ?? ''),
		'css' => $_REQUEST['params']['css'] ?? ($_REQUEST['css'] ?? ''),
		'fields_schema' => $_REQUEST['params']['fields_schema'] ?? ($_REQUEST['fields_schema'] ?? '{}'),
		'preview' => true,
	]);

	$_GESTOR['ajax-json'] = ['status' => 'Ok', 'html' => $rendered];
}

// ==== Start

function presentations_start() {
	global $_GESTOR;

	gestor_incluir_bibliotecas();

	if ($_GESTOR['ajax']) {
		interface_ajax_iniciar();

		switch ($_GESTOR['ajax-opcao']) {
			case 'template-load': presentations_ajax_template_load(); break;
			case 'widget-preview': presentations_ajax_widget_preview(); break;
		}

		interface_ajax_finalizar();
	} else {
		presentations_interfaces_padroes();
		interface_iniciar();

		switch ($_GESTOR['opcao']) {
			case 'adicionar': presentations_adicionar(); break;
			case 'editar': presentations_editar(); break;
			case 'clonar': presentations_clonar(); break;
		}

		interface_finalizar();
	}
}

presentations_start();
