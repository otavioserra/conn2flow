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
	
	$found = false;
	
	$impressao = gestor_sessao_variavel('impressao');
	
	echo 'Var>> '.print_r($impressao,true)."<br>";exit;
	
	if(gettype($impressao) == "array"){
		gestor_sessao_variavel_del('impressao');
		
		$found = true;
		
		$_GESTOR['pagina'] = $impressao['pagina'];
		
		if(isset($impressao['titulo'])){
			$_GESTOR['pagina#titulo-extra'] = $impressao['titulo'];
		}
	}
	
	if(!$found){
		$impressaoSemDados = gestor_componente(Array(
			'id' => 'impressao-sem-dados',
		));
		
		$_GESTOR['pagina'] = $impressaoSemDados;
	}
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