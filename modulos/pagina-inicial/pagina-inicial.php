<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'pagina-inicial';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// ==== Start

function pagina_inicial_start(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo CSS
	
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'jquery.glide/jeffry.in.css">');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'jquery.glide/jeffry.in.slider.css">');
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jquery.glide/jquery.glide.min.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
}

pagina_inicial_start();

?>