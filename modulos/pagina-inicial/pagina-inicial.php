<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'pagina-inicial';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.21',
);

// ==== Start

function pagina_inicial_start(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo CSS
	
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'fomantic-UI.caroucel/caroucel.css?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'">');
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'fomantic-UI.caroucel/caroucel.js?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
}

pagina_inicial_start();

?>