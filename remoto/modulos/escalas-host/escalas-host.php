<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'escalas-host';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.20',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function escalas_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
	));
	
	// ===== Codificação do plugin.
	
	
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step','');
	layout_trocar_variavel_valor('layout#step-mobile','');
	
	// ===== Finalizar o layout com as variáveis padrões.
	
	layout_loja();
	
	// ===== Finalizar interface.
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	interface_finalizar();
	
	// ===== Incluir bibliotecas.
	
	//gestor_pagina_javascript_incluir('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.css" integrity="sha512-Fppbdpv9QhevzDE+UHmdxL4HoW8HantO+rC8oQB2hCofV+dWV2hePnP5SgiWR1Y1vbJeYONZfzQc5iII6sID2Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />');
	//gestor_pagina_javascript_incluir('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.min.css" integrity="sha512-rxThY3LYIfYsVCWPCW9dB0k+e3RZB39f23ylUYTEuZMDrN/vRqLdaCBo/FbvVT6uC2r0ObfPzotsfKF9Qc5W5g==" crossorigin="anonymous" referrerpolicy="no-referrer" />');
	gestor_pagina_javascript_incluir('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.standalone.min.css" integrity="sha512-p4vIrJ1mDmOVghNMM4YsWxm0ELMJ/T0IkdEvrkNHIcgFsSzDi/fV7YxzTzb3mnMvFPawuIyIrHcpxClauEfpQg==" crossorigin="anonymous" referrerpolicy="no-referrer" />');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha512-T/tUfKSV1bihCnd+MxKD0Hm1uBBroVYBOYSk1knyvQ9VyZJpc/ALb4P0r6ubwVPSGB2GvjeoMAJJImBG12TiaQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.pt-BR.min.js"></script>');
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('plugin');
}

// ==== Ajax

function escalas_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function escalas_start(){
	global $_GESTOR;
	
	// ===== Verificar se o usuário está logado.
	
	gestor_permissao();
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': escalas_ajax_opcao(); break;
			default: escalas_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': escalas_opcao(); break;
			default: escalas_padrao();
		}
	}
}

escalas_start();

?>