<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'comunicacao-configuracoes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_variaveis',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_variaveis',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
);

// ===== Funções Auxiliares

// ===== Funções Principais

function configuracoes_config(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Configurações das variáveis.
	
	$config = gestor_incluir_configuracao(Array(
		'id' => $_GESTOR['modulo-id'].'.config',
	));
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Interface de administração da configuração salvar.
		
		gestor_incluir_biblioteca('configuracao');
		
		$retorno = configuracao_hosts_salvar(Array(
			'modulo' => $_GESTOR['modulo-id'],
			'grupos' => Array(
				'padrao-host',
			),
			'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
			'tabela' => $modulo['tabela'],
		));
		
		if(isset($retorno['alterouVariavel'])){
			
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface de configurações do host.
	
	gestor_incluir_biblioteca('configuracao');
	
	configuracao_hosts(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'grupos' => Array(
			'padrao-host',
		),
		'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
		'marcador' => '<!-- comunicacao-configuracoes -->',
	));
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['config']['finalizar'] = Array(
		'formulario' => Array(
			'campos' => Array(
				
			),
		)
	);
}

function configuracoes_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function configuracoes_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function configuracoes_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': configuracoes_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		configuracoes_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'config': configuracoes_config(); break;
		}
		
		interface_finalizar();
	}
}

configuracoes_start();

?>