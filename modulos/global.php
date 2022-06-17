<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'global';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html'),
);

function global_impressao(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$_GESTOR['pagina'] = 'Impresso';
}

// ==== Ajax

function global_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function global_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': global_ajax_opcao(); break;
		}
	} else {
		switch($_GESTOR['opcao']){
			case 'impressao': global_impressao(); break;
		}
	}
}

global_start();

?>