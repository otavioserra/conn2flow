<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'testes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.9',
	'bibliotecas' => Array('interface','html','pagina','ip'),
	'tabela' => Array(
		'nome' => 'template',
		'id' => 'id',
		'id_numerico' => 'id_'.'template',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
	'resources' => [
		'pt-br' => [
			'layouts' => [],
			'pages' => [
			    [
			        'name' => 'Página de Testes',
			        'id' => 'pagina-de-testes',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'pagina-de-testes/',
			        'type' => 'page',
			        'option' => 'testes',
			        'root' => true,
			        'version' => '1.1',
			        'checksum' => [
			            'html' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'css' => '0b58367cd17703c084b92e6bf311f9a4',
			        ],
			    ],
			    [
			        'name' => 'Sem Permissão Teste',
			        'id' => 'sem-permissao-teste',
			        'layout' => 'layout-pagina-simples',
			        'path' => 'testes/sem-permissao/',
			        'type' => 'system',
			        'option' => 'sem-permissao',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => '599d814ccd070be528a96bc560fadcef',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			        ],
			    ],
			],
			'components' => [],
		],
	],
);

function getUserIP() {
	
    $ipaddress="";
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress="UNKNOWN";
	
	return gethostbyname($ipaddress);
}

function testes_testes(){
	global $_GESTOR;
	
	$pagina = 'IP: '.ip_get(true).' IPV4: '.getUserIP();
	
	// ===== Área de testes.
	
	
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/core.min.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/sha256.js"></script>');
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