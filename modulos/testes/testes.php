<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'testes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.8',
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
	
	$datasStr = '01/08/2022,04/08/2022,08/08/2022';
	
	$pagina = '<div class="datepicker" data-date="'.$datasStr.'"></div><input type="hidden" name="datas" value="'.$datasStr.'">';
	
	// ===== Área de testes.
	
	gestor_pagina_javascript_incluir('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.standalone.min.css" integrity="sha512-p4vIrJ1mDmOVghNMM4YsWxm0ELMJ/T0IkdEvrkNHIcgFsSzDi/fV7YxzTzb3mnMvFPawuIyIrHcpxClauEfpQg==" crossorigin="anonymous" referrerpolicy="no-referrer" />');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha512-T/tUfKSV1bihCnd+MxKD0Hm1uBBroVYBOYSk1knyvQ9VyZJpc/ALb4P0r6ubwVPSGB2GvjeoMAJJImBG12TiaQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.pt-BR.min.js"></script>');
	
	$_GESTOR['javascript-vars']['escalas'] = Array(
		'highlightedTooltip' => "Dia com evento",
		'startDate' => "01/07/2022",
		'endDate' => "31/08/2022",
		'datasDesabilitadas' => Array("02/08/2022","18/08/2022","19/08/2022","20/08/2022","21/08/2022"),
		'datasDestacadas' => Array("01/08/2022","08/08/2022","10/08/2022","07/08/2022","30/08/2022","31/08/2022"),
	);
	
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