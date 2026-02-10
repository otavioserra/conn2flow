<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'forms';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/forms.json'), true);

// ===== Funções Auxiliares



// ===== Funções Principais

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
		'template_id',
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
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$name = (isset($retorno_bd['name']) ? $retorno_bd['name'] : '');
		$template_id = (isset($retorno_bd['template_id']) ? $retorno_bd['template_id'] : '');
		$fields_schema = (isset($retorno_bd['fields_schema']) ? $retorno_bd['fields_schema'] : '');
		
		// ===== Alterar demais variáveis.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#name#',$name);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);
		
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
						'template_id',
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
							'id' => 'template_id',
							'nome' => gestor_variaveis(Array('modulo' => 'admin-templates','id' => 'form-name-placeholder')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">N/A</span>',
								'tabela' => Array(
									'nome' => 'templates',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'" AND target="forms"',
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
		}
		
		interface_finalizar();
	}
}

forms_start();

?>
