<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'forms';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/forms.json'), true);

// ===== Funções Auxiliares



// ===== Funções Principais

function forms_adicionar(){
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
					'campo' => 'template_id',
					'label' => gestor_variaveis(Array('modulo' => 'admin-templates','id' => 'form-name-placeholder')),
				)
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
		
		$campo_nome = "id_users"; $campo_valor = $usuario['id_usuarios']; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "name"; $post_nome = $campo_nome;      							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "description"; $post_nome = $campo_nome; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "module"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "template_id"; $post_nome = $campo_nome; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		$fields_schema_str = $_REQUEST['fields_schema'] ?? '[]';
		
		$campo_nome = "fields_schema"; $campo_valor = $fields_schema_str;				if($_REQUEST['fields_schema'])	$campos[] = Array($campo_nome,banco_escape_field($campo_valor));
		
		// ===== Campos comuns
		
		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
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
			)
		),
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'module',
				'nome' => 'module',
				'procurar' => true,
				'limpar' => true,
				'selectClass' => 'three column',
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
				'tabela' => Array(
					'nome' => 'modulos',
					'campo' => 'nome',
					'id_numerico' => 'id',
					'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
				),
			),
		),
	);
}

function forms_visualizar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do registro
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para visualizar.
	
	$camposBanco = Array(
		'id',
        'id_forms',
		'name',
		'description',
		'module',
		'fields_schema',
		'status'
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoVisualizar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;

    // ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoVisualizar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$name = (isset($retorno_bd['name']) ? $retorno_bd['name'] : '');
		$description = (isset($retorno_bd['description']) ? $retorno_bd['description'] : '');
		$module = (isset($retorno_bd['module']) ? $retorno_bd['module'] : '');
		$fields_schema = (isset($retorno_bd['fields_schema']) ? $retorno_bd['fields_schema'] : '');
		
		// ===== Formatar o JSON do fields_schema para exibição bonita
		
		if($fields_schema){
			$decoded = json_decode($fields_schema, true);
			if($decoded !== null){
				$fields_schema = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			}
		}

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
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/css/css.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/javascript/javascript.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/htmlmixed/htmlmixed.js"></script>');
		
		// ===== Alterar demais variáveis.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#name#',$name);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#module#',$module);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#description#',$description);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#fields_schema#',$fields_schema);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#form_info#',json_encode($modulo['resources'][$_GESTOR['linguagem-codigo']]['form_info'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
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

function forms_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'name',
						'description',
						'module',
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
							'id' => 'description',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-description')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'module',
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'module-name')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">N/A</span>',
								'tabela' => Array(
									'nome' => 'modulos',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
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
					'visualizar' => Array(
						'url' => 'view/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-view')),
						'icon' => 'file alternate outline',
						'cor' => 'basic brown',
					),
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

function forms_test_email_page(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$testEmailPage = gestor_componente(Array(
		'id' => 'base-email',
		'modulo' => 'contatos',
	));

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#test-email-page#',$testEmailPage);
}

// ==== Ajax



// ==== Start

function forms_start(){
    global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			// case 'ajaxOption': forms_ajax_option(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		forms_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
		    case 'visualizar': forms_visualizar(); break;
			case 'adicionar': forms_adicionar(); break;
		    // case 'editar': forms_editar(); break;
		    // case 'clonar': forms_clonar(); break;
		    case 'test-email-page': forms_test_email_page(); break;
		}
		
		interface_finalizar();
	}
}

forms_start();

?>
