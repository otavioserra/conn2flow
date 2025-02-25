<?php

/*
 ************************************************************************
 PagSeguro Config File
 ************************************************************************
 */

$_CAMINHO_RELATIVO_RAIZ					 = "../../../../../../";
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

$PagSeguroConfig = array();

$PagSeguroConfig['environment'] = array();
$PagSeguroConfig['environment']['environment'] = "production";

$PagSeguroConfig['credentials'] = array();

if($_SESSION['B2MAKE_PAGSEGURO_ESERVICES']){
	$PagSeguroConfig['credentials']['email'] = $_SESSION['B2MAKE_PAGSEGURO_ESERVICES']["email"];
	$PagSeguroConfig['credentials']['token'] = $_SESSION['B2MAKE_PAGSEGURO_ESERVICES']["token"];
	$PagSeguroConfig['log']['active'] = true;
} else if($_PAGSEGURO){
	$PagSeguroConfig['credentials']['email'] = $_PAGSEGURO["email"];
	$PagSeguroConfig['credentials']['token'] = $_PAGSEGURO["token"];
	$PagSeguroConfig['log']['active'] = true;
} else {
	$PagSeguroConfig['credentials']['email'] = "mail";
	$PagSeguroConfig['credentials']['token'] = "token";
}

$PagSeguroConfig['application'] = array();
$PagSeguroConfig['application']['charset'] = "UTF-8"; // UTF-8, UTF-8

$PagSeguroConfig['log'] = array();
$PagSeguroConfig['log']['active'] = true;
$PagSeguroConfig['log']['fileLocation'] = sys_get_temp_dir().$_SYSTEM['SEPARADOR']."pagseguro-log.txt";

if(!is_file($PagSeguroConfig['log']['fileLocation'])){
	file_put_contents($PagSeguroConfig['log']['fileLocation'],"============= PagSeguro Log ===============");
	chmod($PagSeguroConfig['log']['fileLocation'], 0777);
}

?>
