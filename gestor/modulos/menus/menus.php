<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'menus';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/menus.json'), true);

// ===== Funções Auxiliares

function menus_normalize_array($array) {
    if (is_array($array)) {
        ksort($array);
        foreach ($array as $key => $value) {
            $array[$key] = menus_normalize_array($value);
        }
    }
    return $array;
}

/**
 * Extrai a lista de variáveis `[[item#NOME]]` (sem o cerco @...@) encontradas no HTML
 * informado. Usado para alimentar a aba de variáveis do html-editor quando necessário.
 *
 * @param string $html
 * @return array Lista de [['id' => 'label'], ['id' => 'url'], ...] sem duplicatas.
 */
function menus_extract_item_variables($html){
	if(empty($html) || !is_string($html)) return [];

	$pattern = '/\[\[item#([a-zA-Z0-9_\-]+)\]\]/';
	preg_match_all($pattern, $html, $matches);

	if(empty($matches[1])) return [];

	$unique = [];
	foreach($matches[1] as $name){
		if($name === '') continue;
		$unique[$name] = ['id' => $name];
	}

	return array_values($unique);
}

/**
 * Variáveis fixas expostas pelos templates de menu ao Editor HTML (req-017 item 1).
 *
 * Diferente do `publisher-highlights` (variáveis dinâmicas mapeadas a um publicador), os
 * menus têm um conjunto fixo e conhecido de variáveis `[[item#X]]` resolvidas pelo widget
 * renderer. Esta lista alimenta a aba "Variáveis"/"Simular" do html-editor.
 *
 * @return array Lista no formato [['id' => 'label'], ['id' => 'url'], ...].
 */
function menus_variaveis_template(){
	return Array(
		Array('id' => 'label'),
		Array('id' => 'url'),
		Array('id' => 'target'),
		Array('id' => 'slug'),
		Array('id' => 'css_classes'),
		Array('id' => 'children'),
	);
}

function menus_schema_normalizar($schema){
	if(!is_array($schema)) $schema = [];

	if(!isset($schema['selected_items']) || !is_array($schema['selected_items'])) $schema['selected_items'] = [];
	if(!isset($schema['availability']) || $schema['availability'] !== 'condicional') $schema['availability'] = 'todos';
	if(!isset($schema['conditions']) || !is_array($schema['conditions'])) $schema['conditions'] = [];
	if(!isset($schema['menus']) || !is_array($schema['menus'])) $schema['menus'] = [];
	if(!isset($schema['menus']['visible_to_all']) || !is_array($schema['menus']['visible_to_all'])){
		$schema['menus']['visible_to_all'] = $schema['selected_items'];
	}
	if(!isset($schema['template_id'])) $schema['template_id'] = '';

	$conditions = [];
	foreach($schema['conditions'] as $cond){
		if(!is_array($cond) || empty($cond['slug'])) continue;
		$type = (isset($cond['type']) && in_array($cond['type'], ['publico','logado','perfil_usuario'], true)) ? $cond['type'] : 'publico';
		$slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string)$cond['slug']);
		$slug = trim(strtolower($slug), '-');
		if($slug === '') continue;
		if(!isset($schema['menus'][$slug]) || !is_array($schema['menus'][$slug])) $schema['menus'][$slug] = [];
		$condition = ['type' => $type, 'slug' => $slug];
		if($type === 'perfil_usuario'){
			$profile_ids = [];
			if(isset($cond['profile_ids']) && is_array($cond['profile_ids'])){
				foreach($cond['profile_ids'] as $profile_id){
					$profile_id = (string)$profile_id;
					if($profile_id !== '' && !in_array($profile_id, $profile_ids, true)) $profile_ids[] = $profile_id;
				}
			}
			$condition['profile_ids'] = $profile_ids;
			if(isset($cond['profiles']) && is_array($cond['profiles'])) $condition['profiles'] = $cond['profiles'];
		}
		$conditions[] = $condition;
	}
	$schema['conditions'] = $conditions;
	$schema['selected_items'] = $schema['menus']['visible_to_all'];

	return $schema;
}

// ===== Funções Principais

function menus_adicionar(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	// ===== Gravar registro no Banco

	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();

		// ===== Validação de campos obrigatórios

		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
			)
		));

		// ===== Definição do identificador

		$campos = null;
		$campo_sem_aspas_simples = false;

		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["name"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			),
		));

		// ===== Campos gerais

		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "name"; $post_nome = $campo_nome;							if(isset($_REQUEST[$post_nome]) && $_REQUEST[$post_nome])	$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id;									$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		// ===== fields_schema (itens curados + template_id) — converte [[item#xxx]] -> @[[item#xxx]]@

		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema_str = $_REQUEST['fields_schema'] ?? '';
		if($fields_schema_str !== ''){
			$fields_schema_str = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $fields_schema_str);
			$campos[] = Array('fields_schema', banco_escape_field($fields_schema_str));
		}

		// ===== html / css / css_compiled / html_extra_head (do html-editor.php)

		// req-028: css_compiled e html_extra_head podem conter variáveis do sistema [[VAR]];
		// converte para o formato de armazenamento @[[var]]@ antes de gravar.
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled'] ?? '');
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head'] ?? '');

		foreach(['html','css','css_compiled','html_extra_head'] as $clonable_field){
			if(isset($_REQUEST[$clonable_field]) && $_REQUEST[$clonable_field] !== ''){
				$campos[] = Array($clonable_field, banco_escape_field($_REQUEST[$clonable_field]));
			}
		}

		// ===== Campos comuns

		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);

		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);

		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	// ===== Templates para seleção (dropdown template_id)

	menus_template_options(null, false);

	// ===== Publicadores para o tipo de item `publicador` (req-018 / DEC-025)

	menus_publisher_options(null);

	// ===== Schema inicial vazio para o JS reidratar UI
	// Menus são livres de publicadores: o schema guarda apenas a curadoria de itens e o template.

	$schema_inicial = menus_schema_normalizar(['selected_items' => [], 'template_id' => '']);
	$_GESTOR['pagina'] .= '<script>var menus_initial_schema = '.json_encode($schema_inicial).';</script>';

	// ===== HTML Editor (alvo menus)

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente(Array(
		'adicionarEditar' => true,
		'modulo' => $modulo,
		'alvo' => 'menus',
		'alvos_modelos' => 'menus',
		'target_variables' => menus_variaveis_template(),
		'widget_js_include' => ['menus' => true],
	)));

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html#','');
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css#','');
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css-compiled#','');
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html-extra-head#','');

	// ===== Inclusão Módulo JS

	// req-016: o editor de árvore de menus é um componente próprio (Pointer Events em JS
	// vanilla + visual Fomantic-UI), sem dependência externa de drag-and-drop.
	gestor_pagina_javascript_incluir();

	// ===== Interface adicionar finalizar opções

	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
					'identificador' => 'name',
				),
			)
		)
	);
}

function menus_editar(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	// ===== Identificador do registro

	$id = $_GESTOR['modulo-registro-id'];

	// ===== Definição dos campos do banco de dados para editar.

	$camposBanco = Array(
		'id',
		'id_menus',
		'name',
		'fields_schema',
		'html',
		'css',
		'css_compiled',
		'html_extra_head',
		'status',
	);

	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);

	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;

	// ===== Gravar Atualizações no Banco

	if(isset($_GESTOR['atualizar-banco'])){
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-database-field-before-error'))
			));

			gestor_redirecionar_raiz();
		}

		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		));

		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'",
		);

		$alteracoes = [];
		$backups = [];

		// ===== name (com possível regravação de slug)

		$campo_nome = "name"; $request_name = $campo_nome; $alteracoes_name = $campo_nome;
		if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){
			$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'";
			if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;}
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));
		}

		if(isset($alterar_id)){
			$layouts = banco_select_name
			(
				banco_campos_virgulas(Array(
					$modulo['tabela']['id_numerico'],
				))
				,
				$modulo['tabela']['nome'],
				"WHERE ".$modulo['tabela']['id']."='".$id."'"
			);

			if($layouts){
				$id_novo = banco_identificador(Array(
					'id' => banco_escape_field($_REQUEST["name"]),
					'tabela' => Array(
						'nome' => $modulo['tabela']['nome'],
						'campo' => $modulo['tabela']['id'],
						'id_nome' => $modulo['tabela']['id_numerico'],
						'id_valor' => $layouts[0][$modulo['tabela']['id_numerico']],
						'where' => "language='".$_GESTOR['linguagem-codigo']."'",
					),
				));

				$alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
				$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
				$_GESTOR['modulo-registro-id'] = $id_novo;
			}
		}

		// ===== fields_schema (itens curados + template_id)
		// Converte [[item#xxx]] -> @[[item#xxx]]@ no JSON string antes de salvar.

		$campo_nome = "fields_schema"; $request_name = $campo_nome; $alteracoes_name = $campo_nome;
		if(isset($_REQUEST[$request_name])){
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			$openText = $_GESTOR['variavel-global']['openText'];
			$closeText = $_GESTOR['variavel-global']['closeText'];

			$request_formatado = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), ($_REQUEST[$request_name] ? $_REQUEST[$request_name] : ''));

			$valor_request = menus_normalize_array(json_decode($request_formatado, true));
			$valor_banco = menus_normalize_array(json_decode(banco_select_campos_antes($campo_nome), true));

			if ($valor_banco !== $valor_request) {
				$editar['dados'][] = $campo_nome . "='" . banco_escape_field($request_formatado) . "'";
				$alteracoes[] = Array('campo' => 'form-' . $alteracoes_name . '-label');
			}
		}

		// ===== html / css / css_compiled / html_extra_head (do html-editor.php) — com backup do valor anterior.

		// req-028: css_compiled e html_extra_head podem conter variáveis do sistema [[VAR]];
		// converte para o formato de armazenamento @[[var]]@ antes de comparar/gravar.
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		foreach(['css_compiled','html_extra_head'] as $campo_nome){
			if(isset($_REQUEST[$campo_nome])){
				$_REQUEST[$campo_nome] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST[$campo_nome]);
			}
		}

		foreach(['html','css','css_compiled','html_extra_head'] as $campo_nome){
			$request_name = $campo_nome;
			if(isset($_REQUEST[$request_name]) && banco_select_campos_antes($campo_nome) != $_REQUEST[$request_name]){
				$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'";
				$alteracoes[] = Array('campo' => 'form-'.$campo_nome.'-label');
				if(banco_select_campos_antes($campo_nome)){
					$backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));
				}
			}
		}

		// ===== Se houve alterações, persistir e gravar backup/histórico.

		if(isset($editar['dados'])){
			$campo_nome = 'user_modified'; $editar['dados'][] = $campo_nome." = 1";
			$campo_nome = $modulo['tabela']['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = $modulo['tabela']['data_modificacao']; $editar['dados'][] = $campo_nome."=NOW()";

			$editar['sql'] = banco_campos_virgulas($editar['dados']);

			if($editar['sql']){
				banco_update
				(
					$editar['sql'],
					$editar['tabela'],
					$editar['extra']
				);
			}

			if($backups){
				foreach($backups as $backup){
					interface_backup_campo_incluir(Array(
						'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
						'versao' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['versao'])),
						'campo' => $backup['campo'],
						'valor' => $backup['valor'],
					));
				}
			}

			interface_historico_incluir(Array(
				'id' => $id,
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'id_numerico' => $modulo['tabela']['id_numerico'],
					'versao' => $modulo['tabela']['versao'],
				),
				'alteracoes' => $alteracoes,
			));
		}

		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}

	// ===== Selecionar dados do banco

	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);

	if($_GESTOR['banco-resultado']){
		$name = (isset($retorno_bd['name']) ? $retorno_bd['name'] : '');
		$fields_schema = (isset($retorno_bd['fields_schema']) ? $retorno_bd['fields_schema'] : '');
		$html = (isset($retorno_bd['html']) ? $retorno_bd['html'] : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		$css_compiled = (isset($retorno_bd['css_compiled']) ? $retorno_bd['css_compiled'] : '');
		$html_extra_head = (isset($retorno_bd['html_extra_head']) ? $retorno_bd['html_extra_head'] : '');

		// Converter @[[...]]@ -> [[...]] no fields_schema para edição no frontend
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $fields_schema);

		// req-028: converte @[[var]]@ -> [[var]] em css_compiled e html_extra_head para edição no frontend.
		$css_compiled = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css_compiled);
		$html_extra_head = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html_extra_head);

		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#name#',$name);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);

		$fields_schema_decoded = menus_schema_normalizar(json_decode($fields_schema, true) ?: []);

		$schema_json = json_encode($fields_schema_decoded);
		$_GESTOR['pagina'] .= '<script>var menus_initial_schema = '.$schema_json.';</script>';

		// ===== Template dropdown
		menus_template_options($fields_schema_decoded['template_id'] ?? null, (!empty($html) || !empty($css)));

		// ===== Publicadores para o tipo de item `publicador` (req-018 / DEC-025)
		menus_publisher_options(null);

		// ===== HTML Editor (alvo menus) — edição do template HTML/CSS no banco.
		// A biblioteca html-editor é incluída automaticamente via `bibliotecas` no manifesto.

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente(Array(
			'editar' => true,
			'modulo' => $modulo,
			'alvo' => 'menus',
			'alvos_modelos' => 'menus',
			'target_variables' => menus_variaveis_template(),
			'widget_js_include' => ['menus' => true],
		)));

		// Conteúdos atuais para o editor
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html#',$html);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css#',$css);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css-compiled#',$css_compiled);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html-extra-head#',$html_extra_head);

		// ===== Popular metaDados

		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');

		$metaDados = [];
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}

	// ===== Inclusão Módulo JS

	// req-016: o editor de árvore de menus é um componente próprio (Pointer Events em JS
	// vanilla + visual Fomantic-UI), sem dependência externa de drag-and-drop.
	gestor_pagina_javascript_incluir();

	// ===== Interface editar finalizar opções

	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
		'botoes' => Array(
			'adicionar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'clonar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/clonar/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-clone')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-clone')),
				'icon' => 'clone',
				'cor' => 'teal',
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash' ),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown' ),
			),
			'excluir' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-delete')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'cor' => 'red',
			),
		),
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
					'identificador' => 'name',
				),
			),
		)
	);
}

function menus_clonar(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	$id = $_GESTOR['modulo-registro-id'];

	$camposBanco = Array(
		'id',
		'id_menus',
		'name',
		'fields_schema',
		'html',
		'css',
		'css_compiled',
		'html_extra_head',
		'status'
	);

	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);

	$camposBancoClonar = array_merge($camposBanco,$camposBancoPadrao);

	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();

		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		));

		$campos = null;
		$campo_sem_aspas_simples = false;

		$id_novo = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["name"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			),
		));

		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "name"; $post_nome = $campo_nome;							if(isset($_REQUEST[$post_nome]) && $_REQUEST[$post_nome])	$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id_novo;							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		// fields_schema: converter de frontend [[...]] para backend @[[...]]@

		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema_str = $_REQUEST['fields_schema'] ?? '{}';
		$fields_schema_str = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $fields_schema_str);

		$campo_nome = "fields_schema"; $campo_valor = $fields_schema_str;		if(isset($_REQUEST['fields_schema']) && $_REQUEST['fields_schema'])	$campos[] = Array($campo_nome,banco_escape_field($campo_valor));

		// html / css / css_compiled / html_extra_head clonados do registro de origem (via campos ocultos no formulário)

		// req-028: css_compiled e html_extra_head podem conter variáveis do sistema [[VAR]];
		// converte para o formato de armazenamento @[[var]]@ antes de gravar.
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled'] ?? '');
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head'] ?? '');

		foreach(['html','css','css_compiled','html_extra_head'] as $clonable_field){
			if(isset($_REQUEST[$clonable_field]) && $_REQUEST[$clonable_field] !== ''){
				$campos[] = Array($clonable_field, banco_escape_field($_REQUEST[$clonable_field]));
			}
		}

		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);

		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);

		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id_novo);
	}

	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoClonar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);

	if($_GESTOR['banco-resultado']){
		$fields_schema = (isset($retorno_bd['fields_schema']) ? $retorno_bd['fields_schema'] : '');
		$html = (isset($retorno_bd['html']) ? $retorno_bd['html'] : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		$css_compiled = (isset($retorno_bd['css_compiled']) ? $retorno_bd['css_compiled'] : '');
		$html_extra_head = (isset($retorno_bd['html_extra_head']) ? $retorno_bd['html_extra_head'] : '');

		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $fields_schema);

		// req-028: converte @[[var]]@ -> [[var]] em css_compiled e html_extra_head para edição no frontend.
		$css_compiled = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css_compiled);
		$html_extra_head = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html_extra_head);

		$fields_schema_decoded = menus_schema_normalizar(json_decode($fields_schema, true) ?: []);
		$schema_json = json_encode($fields_schema_decoded);
		$_GESTOR['pagina'] .= '<script>var menus_initial_schema = '.$schema_json.';</script>';

		menus_template_options($fields_schema_decoded['template_id'] ?? null, (!empty($html) || !empty($css)));

		// ===== Publicadores para o tipo de item `publicador` (req-018 / DEC-025)
		menus_publisher_options(null);

		// HTML/CSS de origem precisam viajar no submit (campos ocultos no formulário de clonar)
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#html-original#',htmlspecialchars($html, ENT_QUOTES));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#css-original#',htmlspecialchars($css, ENT_QUOTES));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#css-compiled-original#',htmlspecialchars($css_compiled, ENT_QUOTES));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#html-extra-head-original#',htmlspecialchars($html_extra_head, ENT_QUOTES));

		// HTML Editor (alvo menus) — para ajustar o template no momento da clonagem antes de salvar.
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente(Array(
			'adicionarEditar' => true,
			'modulo' => $modulo,
			'alvo' => 'menus',
			'alvos_modelos' => 'menus',
			'target_variables' => menus_variaveis_template(),
			'widget_js_include' => ['menus' => true],
		)));

		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html#',$html);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css#',$css);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css-compiled#',$css_compiled);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html-extra-head#',$html_extra_head);
	} else {
		gestor_redirecionar_raiz();
	}

	// req-016: o editor de árvore de menus é um componente próprio (Pointer Events em JS
	// vanilla + visual Fomantic-UI), sem dependência externa de drag-and-drop.
	gestor_pagina_javascript_incluir();

	$_GESTOR['interface']['clonar']['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
					'identificador' => 'name',
				),
			),
		)
	);
}

function menus_interfaces_padroes(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'name',
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
							'id' => $modulo['tabela']['data_modificacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
					),
				),
				'opcoes' => Array(
					'editar' => Array(
						'url' => 'editar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')),
						'icon' => 'edit',
						'cor' => 'basic blue',
					),
					'clonar' => Array(
						'url' => 'clonar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-clone')),
						'icon' => 'clone',
						'cor' => 'basic teal',
					),
					'ativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'I',
						'status_mudar' => 'A',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active')),
						'icon' => 'eye slash',
						'cor' => 'basic brown',
					),
					'desativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'A',
						'status_mudar' => 'I',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')),
						'icon' => 'eye',
						'cor' => 'basic green',
					),
					'excluir' => Array(
						'opcao' => 'excluir',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
						'icon' => 'trash alternate',
						'cor' => 'basic red',
					),
				),
				'botoes' => Array(
					'adicionar' => Array(
						'url' => 'adicionar/',
						'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
						'icon' => 'plus circle',
						'cor' => 'blue',
					),
				),
			);
		break;
	}
}

/**
 * Popula `#template_id_options#` no template com os options do dropdown de templates
 * com `target = 'menus'`. Marca o `id_selecionado` como selected quando informado.
 * Também substitui `#template_placeholder_option#` pelo placeholder padrão de admin-templates.
 */
function menus_template_options($selected_id = null, $has_custom_code = false){
	global $_GESTOR;

	$templates = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id',
			'framework_css',
		))
		,
		'templates',
		"WHERE status='A'"
		.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
		.' AND target="menus"'
		." ORDER BY nome ASC"
	);

	$template_id_options = '';
	if($templates){
		foreach($templates as $template){
			$is_selected = ($selected_id && $template['id'] == $selected_id);
			$framework = $template['framework_css'] ?? '';
			// A opção original só fica selecionada quando não há código customizado no registro.
			$selected_original = ($is_selected && !$has_custom_code) ? ' selected' : '';
			$template_id_options .= '<option value="'.$template['id'].'" data-framework="'.$framework.'"'.$selected_original.'>'.$template['nome'].'</option>';
			// Quando o registro tem HTML/CSS customizado, gera a opção "-modificado" já selecionada
			// para preservar o código do banco sem disparar o loadTemplate padrão.
			// A opção "-modificado" herda o mesmo data-framework do template base.
			if($is_selected && $has_custom_code){
				$template_id_options .= '<option value="'.$template['id'].'-modificado" data-framework="'.$framework.'" selected>'.$template['nome'].' - (Modificado)</option>';
			}
		}
	}

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#template_placeholder_option#',gestor_variaveis(Array('modulo' => 'admin-templates','id' => 'form-name-placeholder')));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#template_id_options#',$template_id_options);
}

/**
 * Popula `#publisher_id_options#` no construtor de itens do menu com os publicadores
 * ativos da tabela `publisher` (idioma corrente). Usado pelo tipo de item `publicador`
 * (req-018 / DEC-025), que gera dinamicamente sub-itens com as publicações do publicador.
 *
 * O dropdown vive no construtor de itens (não há publicador "global" do menu); por isso o
 * `$selected_id` normalmente é null — cada nó publicador guarda o próprio `publisher_id`.
 */
function menus_publisher_options($selected_id = null){
	global $_GESTOR;

	$publishers = banco_select_name
	(
		banco_campos_virgulas(Array(
			'name',
			'id',
		))
		,
		'publisher',
		"WHERE status='A'"
		.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
		." ORDER BY name ASC"
	);

	$publisher_id_options = '';
	if($publishers){
		foreach($publishers as $publisher){
			$selected = ($selected_id && $publisher['id'] == $selected_id) ? ' selected' : '';
			$publisher_id_options .= '<option value="'.$publisher['id'].'"'.$selected.'>'.$publisher['name'].'</option>';
		}
	}

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#publisher_id_options#',$publisher_id_options);
}

// ==== Ajax

/**
 * AJAX: dado um template do menus (target='menus'), retorna o nome e os campos `item#X`
 * encontrados no HTML para popular a interface, e carrega o html/css no editor.
 */
function menus_ajax_template_load(){
	global $_GESTOR;

	$template_id = $_REQUEST['params']['template_id'] ?? '';

	$template = banco_select(Array(
		'unico' => true,
		'tabela' => 'templates',
		'campos' => Array('nome', 'html', 'css', 'framework_css'),
		'extra' => "WHERE id='".banco_escape_field($template_id)."' AND target='menus' AND language='".$_GESTOR['linguagem-codigo']."' AND status='A'"
	));

	if(!$template){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Erro',
			'message' => 'Modelo de menu não encontrado.'
		);
		return;
	}

	$fields = [];
	if($template['html']){
		preg_match_all('/\[\[item#([a-zA-Z0-9_\-]+)\]\]/', $template['html'], $matches);
		if(isset($matches[1])){
			$uniqueFields = [];
			foreach($matches[1] as $name){
				if(empty($name)) continue;
				if(!isset($uniqueFields[$name])) $uniqueFields[$name] = ['id' => $name];
			}
			$fields = array_values($uniqueFields);
		}
	}

	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];

	$template['html'] = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $template['html']);

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'modelo' => Array(
			'name' => $template['nome'],
			'id' => $template_id,
		),
		'html' => $template['html'] ?? '',
		'css' => $template['css'] ?? '',
		'framework_css' => $template['framework_css'] ?? '',
		'campos' => $fields,
	);
}

/**
 * AJAX: busca páginas ativas do site (no idioma corrente), opcionalmente filtradas por
 * um termo de busca (`q`), para o autocomplete de itens do menu.
 *
 * Menus são livres de publicadores: a busca varre diretamente a tabela `paginas`.
 *
 * req-016 §3.1: filtro por tipo de página. O parâmetro `tipo` aceita:
 *  - 'pagina'  -> apenas páginas de conteúdo padrão (AND p.tipo='pagina')
 *  - 'sistema' -> apenas páginas de rotinas do sistema (AND p.tipo='sistema')
 *  - 'ambos'   -> qualquer tipo (sem filtro de tipo)
 * Quando omitido, aplica 'pagina' por padrão.
 *
 * Retorna `{ status, results: [{ value: slug, name: nome, url: caminho }, ...] }`.
 */
function menus_ajax_pages_search(){
	global $_GESTOR;

	$q = trim((string)($_REQUEST['params']['q'] ?? ($_REQUEST['q'] ?? '')));
	$tipo = trim((string)($_REQUEST['params']['tipo'] ?? ($_REQUEST['tipo'] ?? 'pagina')));

	$where = "WHERE p.status='A'"
		." AND p.language='".$_GESTOR['linguagem-codigo']."'";

	if($tipo === 'pagina' || $tipo === 'sistema'){
		$where .= " AND p.tipo='".banco_escape_field($tipo)."'";
	}

	if($q !== ''){
		$q_escaped = banco_escape_field($q);
		$where .= " AND (p.nome LIKE '%".$q_escaped."%' OR p.id LIKE '%".$q_escaped."%')";
	}

	$extra = $where." ORDER BY p.nome ASC LIMIT 50";

	$rows = banco_select(Array(
		'tabela' => 'paginas AS p',
		'campos' => Array('p.id', 'p.nome', 'p.caminho'),
		'extra' => $extra,
	));

	$results = [];
	if(is_array($rows)){
		foreach($rows as $row){
			$results[] = [
				'value' => $row['p.id'] ?? '',
				'name' => $row['p.nome'] ?? ($row['p.id'] ?? ''),
				'url' => isset($row['p.caminho']) && $row['p.caminho'] ? $_GESTOR['url-raiz'].$row['p.caminho'] : '',
			];
		}
	}

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'success' => true,
		'results' => $results,
	);
}

/**
 * AJAX: dado um array de slugs (`params.ids`), retorna os nomes correspondentes para
 * pré-hidratar a seleção de itens ao abrir Edição/Clonagem. Varre `paginas` diretamente.
 */
function menus_ajax_pages_fetch(){
	global $_GESTOR;

	$ids = $_REQUEST['params']['ids'] ?? ($_REQUEST['ids'] ?? []);

	if(!is_array($ids)) $ids = [];

	$ids = array_values(array_filter(array_map('strval', $ids), function($s){ return $s !== ''; }));

	if(empty($ids)){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Ok',
			'results' => [],
		);
		return;
	}

	$ids_escaped = array_map(function($id){
		return "'".banco_escape_field($id)."'";
	}, $ids);
	$ids_in = implode(',', $ids_escaped);

	$rows = banco_select(Array(
		'tabela' => 'paginas AS p',
		'campos' => Array('p.id', 'p.nome', 'p.caminho'),
		'extra' =>
			"WHERE p.status='A'"
			." AND p.language='".$_GESTOR['linguagem-codigo']."'"
			." AND p.id IN (".$ids_in.")",
	));

	$results = [];
	if(is_array($rows)){
		foreach($rows as $row){
			$results[] = [
				'value' => $row['p.id'] ?? '',
				'name' => $row['p.nome'] ?? ($row['p.id'] ?? ''),
				'url' => isset($row['p.caminho']) && $row['p.caminho'] ? $_GESTOR['url-raiz'].$row['p.caminho'] : '',
			];
		}
	}

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'results' => $results,
	);
}

/**
 * AJAX: busca perfis de usuário ativos para o autocomplete de condições por perfil.
 */
function menus_ajax_profiles_search(){
	global $_GESTOR;

	$q = trim((string)($_REQUEST['params']['q'] ?? ($_REQUEST['q'] ?? '')));

	$where = "WHERE status='A'";
	if(isset($_GESTOR['linguagem-codigo']) && $_GESTOR['linguagem-codigo']){
		$where .= " AND language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'";
	}

	if($q !== ''){
		$q_escaped = banco_escape_field($q);
		$where .= " AND (nome LIKE '%".$q_escaped."%' OR id LIKE '%".$q_escaped."%')";
	}

	$rows = banco_select(Array(
		'tabela' => 'usuarios_perfis',
		'campos' => Array('id_usuarios_perfis', 'id', 'nome'),
		'extra' => $where." ORDER BY nome ASC LIMIT 50",
	));

	$results = [];
	if(is_array($rows)){
		foreach($rows as $row){
			$id_num = (string)($row['id_usuarios_perfis'] ?? '');
			if($id_num === '') continue;
			$slug = (string)($row['id'] ?? '');
			$name = (string)($row['nome'] ?? $slug);
			$label = trim($name.($slug !== '' ? ' ('.$slug.')' : ''));
			$results[] = [
				'value' => $id_num,
				'name' => $label !== '' ? $label : $id_num,
				'slug' => $slug,
			];
		}
	}

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'success' => true,
		'results' => $results,
	);
}

/**
 * AJAX: renderiza o widget de menu com inputs crus (html, css, fields_schema) para a aba
 * "Pré-Visualização" da tela de edição. Retorna `{ status, html }`.
 */
function menus_ajax_widget_preview(){
	global $_GESTOR;

	$html_input  = $_REQUEST['params']['html'] ?? ($_REQUEST['html'] ?? '');
	$css_input   = $_REQUEST['params']['css'] ?? ($_REQUEST['css'] ?? '');
	$fields_schema_input = $_REQUEST['params']['fields_schema'] ?? ($_REQUEST['fields_schema'] ?? '{}');
	$preview_slug = $_REQUEST['params']['preview_slug'] ?? ($_REQUEST['preview_slug'] ?? '');

	// Garantir a biblioteca do widget incluída (precisamos da função render_inline).
	if(!function_exists('menus_widget_render_inline')){
		require_once(__DIR__.'/menus.widget.php');
	}

	$rendered = menus_widget_render_inline([
		'html' => $html_input,
		'css' => $css_input,
		'fields_schema' => $fields_schema_input,
		'preview_slug' => $preview_slug,
	]);

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'html' => $rendered,
	);
}

// ==== Start

function menus_start(){
    global $_GESTOR;

	gestor_incluir_bibliotecas();

	if($_GESTOR['ajax']){
		interface_ajax_iniciar();

		switch($_GESTOR['ajax-opcao']){
			case 'template-load': menus_ajax_template_load(); break;
			case 'pages-search': menus_ajax_pages_search(); break;
			case 'pages-fetch': menus_ajax_pages_fetch(); break;
			case 'profiles-search': menus_ajax_profiles_search(); break;
			case 'widget-preview': menus_ajax_widget_preview(); break;
		}

		interface_ajax_finalizar();
	} else {
		menus_interfaces_padroes();

		interface_iniciar();

		switch($_GESTOR['opcao']){
			case 'adicionar': menus_adicionar(); break;
			case 'editar': menus_editar(); break;
			case 'clonar': menus_clonar(); break;
		}

		interface_finalizar();
	}
}

menus_start();
