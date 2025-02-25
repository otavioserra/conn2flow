<?php

if(isset($_REQUEST['caminho'])){
	$_caminho = $_REQUEST['caminho'];
	$_CAMINHO = explode('/',strtolower($_REQUEST['caminho']));
	
	if($_CAMINHO[count($_CAMINHO)-1] == NULL){
		array_pop($_CAMINHO);
	}
	
	$_EXTENSAO = pathinfo($_caminho, PATHINFO_EXTENSION);
}

switch((isset($_CAMINHO) ? $_CAMINHO[0] : null)){
	case 'paginas':					require_once('../b2make-gestor/gestor.php'); break;
	default: 						require_once('start.php');
}

?>