<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'publisher-highlights';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/publisher-highlights.json'), true);

// ===== Funções Auxiliares

function publisher_highlights_normalize_array($array) {
    if (is_array($array)) {
        ksort($array);
        foreach ($array as $key => $value) {
            $array[$key] = publisher_highlights_normalize_array($value);
        }
    }
    return $array;
}

/**
 * Extrai a lista de variáveis `[[item#NOME]]` (sem o cerco @...@) encontradas no HTML
 * informado. Usado para alimentar a aba de variáveis do html-editor no alvo
 * `publisher-highlights` (req-004 item 7).
 *
 * @param string $html
 * @return array Lista de [['id' => 'titulo'], ['id' => 'resumo'], ...] sem duplicatas.
 */
function publisher_highlights_extract_item_variables($html){
	if(empty($html) || !is_string($html)) return [];

	$pattern = '/@\[\[item#([a-zA-Z0-9_\-]+)\]\]@/';
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
 * Recupera os dados (slug + name + fields_schema) do publicador associado pelo slug textual.
 * Usado tanto na tela de edição (para popular o painel de vinculação) quanto no widget renderer.
 *
 * @param string $publisher_id Slug alfanumérico do publisher (D-022).
 * @return array|null Registro do publisher ou null se inexistente.
 */
function publisher_highlights_publisher_by_slug($publisher_id){
	global $_GESTOR;

	if(empty($publisher_id)) return null;

	$retorno = banco_select(Array(
		'unico' => true,
		'tabela' => 'publisher',
		'campos' => Array(
			'id_publisher',
			'id',
			'name',
			'template_id',
			'fields_schema',
		),
		'extra' =>
			"WHERE id='".banco_escape_field($publisher_id)."'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
			." AND status!='D'"
	));

	return $retorno ?: null;
}

// ===== Funções Principais

function publisher_highlights_adicionar(){
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
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'publisher_id',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-label')),
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
		$campo_nome = "publisher_id"; $post_nome = $campo_nome;					if(isset($_REQUEST[$post_nome]) && $_REQUEST[$post_nome])	$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));

		// ===== fields_schema (regras de curadoria + variable_mapping) — converte [[item#xxx]] -> @[[item#xxx]]@

		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema_str = $_REQUEST['fields_schema'] ?? '';
		if($fields_schema_str !== ''){
			$fields_schema_str = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $fields_schema_str);
			$campos[] = Array('fields_schema', banco_escape_field($fields_schema_str));
		}

		// ===== html / css (do html-editor.php)

		foreach(['html','css'] as $clonable_field){
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

	// ===== Publishers para seleção (dropdown publisher_id)

	publisher_highlights_publisher_options(null);

	// ===== Templates para seleção (dropdown template_id)

	publisher_highlights_template_options(null);

	// ===== Schema inicial vazio para o JS reidratar UI

	$schema_inicial = ['rule' => 'latest', 'count' => 4, 'order_by' => 'date_desc', 'selected_items' => [], 'variable_mapping' => []];
	$_GESTOR['pagina'] .= '<script>var publisher_highlights_initial_schema = '.json_encode($schema_inicial).';</script>';

	// ===== HTML Editor (alvo publisher-highlights)

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente(Array(
		'adicionarEditar' => true,
		'modulo' => $modulo,
		'alvo' => 'publisher-highlights',
		'alvos_modelos' => 'publisher-highlights',
	)));

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html#','');
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css#','');

	// ===== Inclusão Módulo JS

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
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'publisher_id',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-label')),
					'identificador' => 'publisher_id',
				),
			)
		)
	);
}

function publisher_highlights_editar(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	// ===== Identificador do registro

	$id = $_GESTOR['modulo-registro-id'];

	// ===== Definição dos campos do banco de dados para editar.

	$camposBanco = Array(
		'id',
		'id_publisher_highlights',
		'name',
		'publisher_id',
		'fields_schema',
		'html',
		'css',
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

		// ===== publisher_id (slug textual)

		$campo_nome = "publisher_id"; $request_name = $campo_nome; $alteracoes_name = $campo_nome;
		if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){
			$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'";
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));
		}

		// ===== fields_schema (regra de curadoria + variable_mapping)
		// Converte [[item#xxx]] -> @[[item#xxx]]@ no JSON string antes de salvar.

		$campo_nome = "fields_schema"; $request_name = $campo_nome; $alteracoes_name = $campo_nome;
		if(isset($_REQUEST[$request_name])){
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			$openText = $_GESTOR['variavel-global']['openText'];
			$closeText = $_GESTOR['variavel-global']['closeText'];

			$request_formatado = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), ($_REQUEST[$request_name] ? $_REQUEST[$request_name] : ''));

			$valor_request = publisher_highlights_normalize_array(json_decode($request_formatado, true));
			$valor_banco = publisher_highlights_normalize_array(json_decode(banco_select_campos_antes($campo_nome), true));

			if ($valor_banco !== $valor_request) {
				$editar['dados'][] = $campo_nome . "='" . banco_escape_field($request_formatado) . "'";
				$alteracoes[] = Array('campo' => 'form-' . $alteracoes_name . '-label');
			}
		}

		// ===== html / css (do html-editor.php) — com backup do valor anterior.

		foreach(['html','css'] as $campo_nome){
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
		$publisher_id = (isset($retorno_bd['publisher_id']) ? $retorno_bd['publisher_id'] : '');
		$fields_schema = (isset($retorno_bd['fields_schema']) ? $retorno_bd['fields_schema'] : '');
		$html = (isset($retorno_bd['html']) ? $retorno_bd['html'] : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');

		// Converter @[[...]]@ -> [[...]] no fields_schema para edição no frontend
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $fields_schema);

		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#name#',$name);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);

		// Defaults garantem retrocompatibilidade com registros gravados antes do req-004.
		$fields_schema_decoded = json_decode($fields_schema, true) ?: [];
		$fields_schema_decoded += ['rule' => 'latest', 'count' => 4, 'order_by' => 'date_desc', 'selected_items' => [], 'variable_mapping' => []];

		$schema_json = json_encode($fields_schema_decoded);
		$_GESTOR['pagina'] .= '<script>var publisher_highlights_initial_schema = '.$schema_json.';</script>';

		// ===== Publisher dropdown
		publisher_highlights_publisher_options($publisher_id);

		// ===== Template dropdown
		publisher_highlights_template_options(null);

		// ===== HTML Editor (alvo publisher-highlights) — edição do template HTML/CSS no banco.
		// A biblioteca html-editor é incluída automaticamente via `bibliotecas` no manifesto.

		$publisher_record = publisher_highlights_publisher_by_slug($publisher_id);

		// Coletar variáveis @[[item#X]]@ do HTML salvo + variable_mapping para alimentar o html-editor.
		$item_variables = publisher_highlights_extract_item_variables($html);
		if(!empty($fields_schema_decoded['variable_mapping'])){
			$mapped = [];
			foreach($item_variables as $iv){ $mapped[$iv['id']] = true; }
			foreach($fields_schema_decoded['variable_mapping'] as $var => $_field){
				if(!isset($mapped[$var])){
					$item_variables[] = ['id' => $var];
					$mapped[$var] = true;
				}
			}
		}

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente(Array(
			'editar' => true,
			'modulo' => $modulo,
			'alvo' => 'publisher-highlights',
			'alvos_modelos' => 'publisher-highlights',
			'publisher' => $publisher_record,
			'target_variables' => $item_variables,
		)));

		// Conteúdos atuais para o editor
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html#',$html);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css#',$css);

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
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'publisher_id',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-label')),
					'identificador' => 'publisher_id',
				),
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'publisher_id',
					'nome' => 'publisher_id',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'publisherDropdown',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-placeholder')),
					'tabela' => Array(
						'nome' => 'publisher',
						'campo' => 'name',
						'id_numerico' => 'id',
						'id_selecionado' => $publisher_id,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'" AND status!="D"',
					),
				)
			)
		)
	);
}

function publisher_highlights_clonar(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	$id = $_GESTOR['modulo-registro-id'];

	$camposBanco = Array(
		'id',
		'id_publisher_highlights',
		'name',
		'publisher_id',
		'fields_schema',
		'html',
		'css',
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
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'publisher_id',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-label')),
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
		$campo_nome = "publisher_id"; $post_nome = $campo_nome;					if(isset($_REQUEST[$post_nome]) && $_REQUEST[$post_nome])	$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));

		// fields_schema: converter de frontend [[...]] para backend @[[...]]@

		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema_str = $_REQUEST['fields_schema'] ?? '{}';
		$fields_schema_str = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $fields_schema_str);

		$campo_nome = "fields_schema"; $campo_valor = $fields_schema_str;		if(isset($_REQUEST['fields_schema']) && $_REQUEST['fields_schema'])	$campos[] = Array($campo_nome,banco_escape_field($campo_valor));

		// html / css clonados do registro de origem (via campos ocultos no formulário)

		foreach(['html','css'] as $clonable_field){
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
		$publisher_id = (isset($retorno_bd['publisher_id']) ? $retorno_bd['publisher_id'] : '');
		$fields_schema = (isset($retorno_bd['fields_schema']) ? $retorno_bd['fields_schema'] : '');
		$html = (isset($retorno_bd['html']) ? $retorno_bd['html'] : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');

		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		$fields_schema = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $fields_schema);

		$fields_schema_decoded = json_decode($fields_schema, true) ?: [];
		$fields_schema_decoded += ['rule' => 'latest', 'count' => 4, 'order_by' => 'date_desc', 'selected_items' => [], 'variable_mapping' => []];
		$schema_json = json_encode($fields_schema_decoded);
		$_GESTOR['pagina'] .= '<script>var publisher_highlights_initial_schema = '.$schema_json.';</script>';

		publisher_highlights_publisher_options($publisher_id);
		publisher_highlights_template_options(null);

		// HTML/CSS de origem precisam viajar no submit (campos ocultos no formulário de clonar)
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#html-original#',htmlspecialchars($html, ENT_QUOTES));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#css-original#',htmlspecialchars($css, ENT_QUOTES));

		// HTML Editor (alvo publisher-highlights) — para que o usuário também possa ajustar
		// o template no momento da clonagem antes de salvar.
		$publisher_record = publisher_highlights_publisher_by_slug($publisher_id);
		$item_variables = publisher_highlights_extract_item_variables($html);
		if(!empty($fields_schema_decoded['variable_mapping'])){
			$mapped = [];
			foreach($item_variables as $iv){ $mapped[$iv['id']] = true; }
			foreach($fields_schema_decoded['variable_mapping'] as $var => $_field){
				if(!isset($mapped[$var])){
					$item_variables[] = ['id' => $var];
					$mapped[$var] = true;
				}
			}
		}

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente(Array(
			'adicionarEditar' => true,
			'modulo' => $modulo,
			'alvo' => 'publisher-highlights',
			'alvos_modelos' => 'publisher-highlights',
			'publisher' => $publisher_record,
			'target_variables' => $item_variables,
		)));

		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-html#',$html);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-css#',$css);
	} else {
		gestor_redirecionar_raiz();
	}

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
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'publisher_id',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-label')),
					'identificador' => 'publisher_id',
				),
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'publisher_id',
					'nome' => 'publisher_id',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'publisherDropdown',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-placeholder')),
					'tabela' => Array(
						'nome' => 'publisher',
						'campo' => 'name',
						'id_numerico' => 'id',
						'id_selecionado' => $publisher_id,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'" AND status!="D"',
					),
				)
			)
		)
	);
}

function publisher_highlights_interfaces_padroes(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'name',
						'publisher_id',
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
							'id' => 'publisher_id',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-label')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">N/A</span>',
								'tabela' => Array(
									'nome' => 'publisher',
									'campo_trocar' => 'name',
									'campo_referencia' => 'id',
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'" AND status!="D"',
								),
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
 * Popula `#publisher_id_options#` no template com os options do dropdown de publishers
 * (slug + name). Marca o `id_selecionado` como selected quando informado.
 */
function publisher_highlights_publisher_options($selected_id = null){
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

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#publisher_placeholder_option#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher_id-placeholder')));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#publisher_id_options#',$publisher_id_options);
}

/**
 * Popula `#template_id_options#` no template com os options do dropdown de templates
 * com `target = 'publisher-highlights'`. Marca o `id_selecionado` como selected quando informado.
 * Também substitui `#template_placeholder_option#` pelo placeholder padrão de admin-templates
 * (req-004 item 2 e 3).
 */
function publisher_highlights_template_options($selected_id = null){
	global $_GESTOR;

	$templates = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id',
		))
		,
		'templates',
		"WHERE status='A'"
		.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
		.' AND target="publisher-highlights"'
		." ORDER BY nome ASC"
	);

	$template_id_options = '';
	if($templates){
		foreach($templates as $template){
			$selected = ($selected_id && $template['id'] == $selected_id) ? ' selected' : '';
			$template_id_options .= '<option value="'.$template['id'].'"'.$selected.'>'.$template['nome'].'</option>';
		}
	}

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#template_placeholder_option#',gestor_variaveis(Array('modulo' => 'admin-templates','id' => 'form-name-placeholder')));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#template_id_options#',$template_id_options);
}

// ==== Ajax

/**
 * AJAX: dado um template do publisher-highlights, retorna o nome e os campos `item#X`
 * encontrados no HTML para popular a interface de mapeamento de variáveis.
 */
function publisher_highlights_ajax_template_load(){
	global $_GESTOR;

	$template_id = $_REQUEST['params']['template_id'] ?? '';

	$template = banco_select(Array(
		'unico' => true,
		'tabela' => 'templates',
		'campos' => Array('nome', 'html', 'css', 'framework_css'),
		'extra' => "WHERE id='".banco_escape_field($template_id)."' AND target='publisher-highlights' AND language='".$_GESTOR['linguagem-codigo']."' AND status='A'"
	));

	if(!$template){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Erro',
			'message' => 'Modelo de destaque não encontrado.'
		);
		return;
	}

	$fields = [];
	if($template['html']){
		preg_match_all('/@\[\[item#([a-zA-Z0-9_\-]+)\]\]@/', $template['html'], $matches);
		if(isset($matches[1])){
			$uniqueFields = [];
			foreach($matches[1] as $name){
				if(empty($name)) continue;
				if(!isset($uniqueFields[$name])) $uniqueFields[$name] = ['id' => $name];
			}
			$fields = array_values($uniqueFields);
		}
	}

	// para que o renderizador as encontre.
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
 * AJAX: dado um publisher (slug), retorna os campos disponíveis declarados em
 * `publisher.fields_schema.fields[]` mais campos padrões (titulo/url/data),
 * para popular o painel de mapeamento `[[item#X]]`.
 */
function publisher_highlights_ajax_publisher_load(){
	global $_GESTOR;

	$publisher_id = $_REQUEST['params']['publisher_id'] ?? '';

	$publisher = publisher_highlights_publisher_by_slug($publisher_id);
	if(!$publisher){
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Erro',
			'message' => 'Publicador não encontrado.'
		);
		return;
	}

	$schema = json_decode($publisher['fields_schema'] ?? '{}', true) ?: [];
	$campos_publisher = [];

	// Campos padrões do publisher (sempre disponíveis)
	$campos_publisher[] = ['id' => 'titulo', 'name' => 'Título da publicação', 'type' => 'text'];
	$campos_publisher[] = ['id' => 'url', 'name' => 'URL da publicação', 'type' => 'text'];
	$campos_publisher[] = ['id' => 'data', 'name' => 'Data', 'type' => 'text'];

	// Construir conjunto de IDs com linked_template:true a partir do template_map do publisher.
	$template_map = isset($schema['template_map']) && is_array($schema['template_map']) ? $schema['template_map'] : [];
	$linked_ids = [];
	foreach($template_map as $tm){
		if(!empty($tm['linked_template'])){
			$linked_ids[$tm['id']] = true;
		}
	}

	if(isset($schema['fields']) && is_array($schema['fields'])){
		foreach($schema['fields'] as $f){
			$field_id = $f['id'] ?? '';
			// Se existirem entradas linked_template no template_map, filtrar; caso contrário incluir todos.
			if(!empty($linked_ids) && !isset($linked_ids[$field_id])) continue;
			$campos_publisher[] = [
				'id' => $field_id,
				'name' => $f['label'] ?? $field_id,
				'type' => $f['type'] ?? 'text',
				'description' => $f['description'] ?? '',
			];
		}
	}

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'publisher' => Array(
			'id' => $publisher['id'],
			'name' => $publisher['name'],
		),
		'campos' => $campos_publisher,
	);
}

/**
 * AJAX: busca páginas (paginas) ativas vinculadas ao `publisher_id` selecionado,
 * opcionalmente filtradas por um termo de busca (`q`), para o dropdown manual
 * com pesquisa do Fomantic UI (req-004 item 6).
 *
 * Retorna `{ status, results: [{ value: slug, name: nome }, ...] }` no formato
 * esperado pelo `apiSettings` do Fomantic UI Dropdown.
 */
function publisher_highlights_ajax_publisher_pages_search(){
	global $_GESTOR;

	// req-007 item 1: aceitar parâmetros tanto em params[...] quanto na raiz do POST.
	$publisher_id = $_REQUEST['params']['publisher_id'] ?? ($_REQUEST['publisher_id'] ?? '');
	$q = trim((string)($_REQUEST['params']['q'] ?? ($_REQUEST['q'] ?? '')));

	$debug = [
		'publisher_id' => $publisher_id,
		'q' => $q,
		'language' => $_GESTOR['linguagem-codigo'],
		'sql' => null,
		'count' => 0,
	];

	if(empty($publisher_id)){
		$debug['note'] = 'empty publisher_id';
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Ok',
			'success' => true,
			'results' => [],
			'debug' => $debug,
		);
		return;
	}

	$where = "WHERE pp.publisher_id='".banco_escape_field($publisher_id)."'"
		." AND p.status='A'"
		." AND p.language='".$_GESTOR['linguagem-codigo']."'";

	if($q !== ''){
		$q_escaped = banco_escape_field($q);
		$where .= " AND (p.nome LIKE '%".$q_escaped."%' OR p.id LIKE '%".$q_escaped."%')";
	}

	$extra = $where." ORDER BY p.nome ASC LIMIT 50";
	$debug['sql'] = "SELECT p.id, p.nome FROM paginas AS p INNER JOIN publisher_pages AS pp ON pp.page_id = p.id AND pp.language = p.language ".$extra;

	$rows = banco_select(Array(
		'tabela' => 'paginas AS p INNER JOIN publisher_pages AS pp ON pp.page_id = p.id AND pp.language = p.language',
		'campos' => Array('p.id', 'p.nome'),
		'extra' => $extra,
	));

	$results = [];
	if(is_array($rows)){
		foreach($rows as $row){
			$results[] = [
				'value' => $row['p.id'] ?? '',
				'name' => $row['p.nome'] ?? ($row['p.id'] ?? ''),
			];
		}
	}

	$debug['count'] = count($results);

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'success' => true,
		'results' => $results,
		'debug' => $debug,
	);
}

/**
 * AJAX: dado um array de slugs (`params.ids`) + publisher_id, retorna os nomes
 * correspondentes para pré-hidratar a seleção no dropdown manual ao abrir
 * Edição/Clonagem (req-004 item 6).
 */
function publisher_highlights_ajax_publisher_pages_fetch(){
	global $_GESTOR;

	// req-007 item 1: aceitar parâmetros em params[...] e na raiz.
	$publisher_id = $_REQUEST['params']['publisher_id'] ?? ($_REQUEST['publisher_id'] ?? '');
	$ids = $_REQUEST['params']['ids'] ?? ($_REQUEST['ids'] ?? []);

	if(!is_array($ids)) $ids = [];

	$ids = array_values(array_filter(array_map('strval', $ids), function($s){ return $s !== ''; }));

	if(empty($publisher_id) || empty($ids)){
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
		'tabela' => 'paginas AS p INNER JOIN publisher_pages AS pp ON pp.page_id = p.id AND pp.language = p.language',
		'campos' => Array('p.id', 'p.nome'),
		'extra' =>
			"WHERE pp.publisher_id='".banco_escape_field($publisher_id)."'"
			." AND p.status='A'"
			." AND p.language='".$_GESTOR['linguagem-codigo']."'"
			." AND p.id IN (".$ids_in.")",
	));

	$results = [];
	if(is_array($rows)){
		foreach($rows as $row){
			$results[] = [
				'value' => $row['p.id'] ?? '',
				'name' => $row['p.nome'] ?? ($row['p.id'] ?? ''),
			];
		}
	}

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'results' => $results,
	);
}

/**
 * AJAX: renderiza o widget com inputs crus (html, css, publisher_id, fields_schema)
 * para a aba "Visualizador ao Vivo" da tela de edição (req-007 item 4).
 *
 * Retorna `{ status, html }` onde `html` é a saída final do widget (com <style> embed).
 */
function publisher_highlights_ajax_widget_preview(){
	global $_GESTOR;

	$html_input  = $_REQUEST['params']['html'] ?? ($_REQUEST['html'] ?? '');
	$css_input   = $_REQUEST['params']['css'] ?? ($_REQUEST['css'] ?? '');
	$publisher_id = $_REQUEST['params']['publisher_id'] ?? ($_REQUEST['publisher_id'] ?? '');
	$fields_schema_input = $_REQUEST['params']['fields_schema'] ?? ($_REQUEST['fields_schema'] ?? '{}');

	// O frontend manda as variáveis no formato `[[item#X]]`. Converter de volta para `@[[item#X]]@`
	// para que o renderizador as encontre.
	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];

	$html_normalized = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $html_input);

	// Garantir a biblioteca do widget incluída (precisamos da função render_inline).
	if(!function_exists('publisher_highlights_widget_render_inline')){
		require_once(__DIR__.'/publisher-highlights.widget.php');
	}

	$rendered = publisher_highlights_widget_render_inline([
		'html' => $html_normalized,
		'css' => $css_input,
		'publisher_id' => $publisher_id,
		'fields_schema' => $fields_schema_input,
	]);

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'html' => $rendered,
	);
}

// ==== Start

function publisher_highlights_start(){
    global $_GESTOR;

	gestor_incluir_bibliotecas();

	if($_GESTOR['ajax']){
		interface_ajax_iniciar();

		switch($_GESTOR['ajax-opcao']){
			case 'template-load': publisher_highlights_ajax_template_load(); break;
			case 'publisher-load': publisher_highlights_ajax_publisher_load(); break;
			case 'publisher-pages-search': publisher_highlights_ajax_publisher_pages_search(); break;
			case 'publisher-pages-fetch': publisher_highlights_ajax_publisher_pages_fetch(); break;
			case 'widget-preview': publisher_highlights_ajax_widget_preview(); break;
		}

		interface_ajax_finalizar();
	} else {
		publisher_highlights_interfaces_padroes();

		interface_iniciar();

		switch($_GESTOR['opcao']){
			case 'adicionar': publisher_highlights_adicionar(); break;
			case 'editar': publisher_highlights_editar(); break;
			case 'clonar': publisher_highlights_clonar(); break;
		}

		interface_finalizar();
	}
}

publisher_highlights_start();
