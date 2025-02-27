<?php

$_CAMINHO_RELATIVO_RAIZ					 = "../../../";
$_SYSTEM['SEPARADOR']					 =		DIRECTORY_SEPARATOR;

function _root_sistema(){
	global $_CAMINHO_RELATIVO_RAIZ;
	
	$path_aux = explode('../',$_CAMINHO_RELATIVO_RAIZ);
	$root_aux = explode('/',$_SERVER["SCRIPT_NAME"]);
	
	foreach($root_aux as $dir){
		$count++;
		if($dir){
			$root .= $dir . '/';
		}
		
		if($count + count($path_aux) == count($root_aux)){
			break;
		}
	}
	
	return $root;
}

require($_SERVER[DOCUMENT_ROOT].$_SYSTEM['SEPARADOR']._root_sistema()."files".$_SYSTEM['SEPARADOR']."projeto".$_SYSTEM['SEPARADOR']."config.externo.php");

if(!$_PAYPAL){
	$_PAYPAL['Credencial']['email'] = 'email';
	$_PAYPAL['Credencial']['user'] = 'user';
	$_PAYPAL['Credencial']['pass'] = 'pass';
	$_PAYPAL['Credencial']['signature'] = 'signature';
}

?>