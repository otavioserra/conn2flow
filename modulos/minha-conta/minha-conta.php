<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'minha-conta';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.1',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function minha_conta_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('interface');
	
	// ===== Pegar os dados do menu do banco de dados.
	
	$menus_itens = banco_select(Array(
		'tabela' => 'menus_itens',
		'campos' => Array(
			'id',
			'url',
			'label',
		),
		'extra' => 
			"WHERE menu_id='menuMinhaConta'"
			." AND inativo IS NULL"
	));
	
	// ===== Montar o menu.
	
	$cel_nome = 'menu-item'; $cel[$cel_nome] = pagina_celula($cel_nome);
	
	if($menus_itens)
	foreach($menus_itens as $item){
		if($item['id'] != 'sair'){
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"url",$item['url']);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"label",$item['label']);
			
			pagina_celula_incluir($cel_nome,$cel_aux);
		} else {
			$sair = $item;
		}
	}
	
	if(isset($sair)){
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"url",$sair['url']);
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"label",$sair['label']);
		
		pagina_celula_incluir($cel_nome,$cel_aux);
	}
	
	pagina_celula_incluir($cel_nome,'');
	
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
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('modulos');
}

// ==== Ajax

function minha_conta_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function minha_conta_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': minha_conta_ajax_opcao(); break;
			default: minha_conta_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': minha_conta_opcao(); break;
			default: minha_conta_padrao();
		}
	}
}

minha_conta_start();

?>