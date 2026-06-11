<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'contatos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/contatos.json'), true);

// ==== Start

function contatos_page(){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formulario');

	// Incluir o controlador do formulário para processar o envio do contato.
	formulario_controlador([
		'formId' => 'form-contact',
	]);
}

function contatos_start(){
    global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			// case 'ajaxOption': forms_submissions_ajax_option(); break;
		}
	} else {
		switch($_GESTOR['opcao']){
		    case 'contact': contatos_page(); break;
		}
	}
}

contatos_start();

?>