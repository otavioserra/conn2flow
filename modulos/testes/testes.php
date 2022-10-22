<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'testes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.9',
	'bibliotecas' => Array('interface','html','pagina'),
	'tabela' => Array(
		'nome' => 'template',
		'id' => 'id',
		'id_numerico' => 'id_'.'template',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
);

function testes_testes(){
	global $_GESTOR;
	
	$pagina = '';
	
	// ===== Área de testes.
	
	$id_hosts = '16';
	$mesAlvo = 11;
	$anoAlvo = 2022;
	
	$data_inicial_mes = '01-11-2022';
	$data_final_mes = '30-11-2022';
	
	// ===== Pegar os dados das escalas qualificadas no banco de dados.
	
	$hosts_escalas = banco_select(Array(
		'tabela' => 'hosts_escalas',
		'campos' => '*',
		'extra' => 
			"WHERE mes='".$mesAlvo."'"
			." AND ano='".$anoAlvo."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	if($hosts_escalas)
	foreach($hosts_escalas as $escala){
		unset($escala['id_hosts']);
		
		$hosts_escalas_proc[] = $escala;
	}
	
	// ===== Pegar os dados das escalas datas qualificadas no banco de dados.
	
	$hosts_escalas_datas = banco_select(Array(
		'tabela' => 'hosts_escalas_datas',
		'campos' => '*',
		'extra' => 
			"WHERE data>='".$data_inicial_mes."'"
			." AND data<='".$data_final_mes."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	if($hosts_escalas_datas)
	foreach($hosts_escalas_datas as $escala_data){
		unset($escala_data['id_hosts']);
		
		$hosts_escalas_datas_proc[] = $escala_data;
	}
	
	// ===== Incluir os dados no host de cada cliente.
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_interface(Array(
		'interface' => 'cron-escalas',
		'plugin' => 'escalas',
		'id_hosts' => $id_hosts,
		'opcao' => 'atualizar',
		'dados' => Array(
			'escalas' => (isset($hosts_escalas_proc) ? $hosts_escalas_proc : Array()),
			'escalas_datas' => (isset($hosts_escalas_datas_proc) ? $hosts_escalas_datas_proc : Array()),
		),
	));
	
	// ===== Caso haja algum erro, incluir no log do cron.
	
	if(!$retorno['completed']){
		echo
			'FUNCAO: cron-escalas[atualizar]'."\n".
			'ID-HOST: '.$id_hosts."\n".
			'ERROR-MSG: '."\n".
			$retorno['error-msg']
		;
	} else {
		echo 'Foi!';
	}
	
	exit;
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Alteração da página.
	
	$_GESTOR['pagina'] .= $pagina;
	
}

function testes_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function testes_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function testes_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': testes_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		testes_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'testes': testes_testes(); break;
		}
		
		interface_finalizar();
	}
}

testes_start();

?>