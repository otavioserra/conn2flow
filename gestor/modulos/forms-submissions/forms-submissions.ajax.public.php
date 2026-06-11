<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'forms-submissions';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/forms-submissions.json'), true);

// ==== Ajax

function forms_submissions_ajax_forms_process(){
	global $_GESTOR;
	
	// ===== Incluir biblioteca de formulário para processar o envio do formulário.
	gestor_incluir_biblioteca('formulario');
	
	// ===== Processar o formulário usando a biblioteca de formulário.
	formulario_processador();
}

// ==== Start

function forms_submissions_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'forms-process': forms_submissions_ajax_forms_process(); break;
		}
	}
}

forms_submissions_start();

?>