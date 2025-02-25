<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */
// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

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

$root_sistema = _root_sistema();

require($_SERVER[DOCUMENT_ROOT].$_SYSTEM['SEPARADOR'].$root_sistema."files".$_SYSTEM['SEPARADOR']."projeto".$_SYSTEM['SEPARADOR']."config.externo.php");

$hybridauth = array(
	"base_url" => "https://".$_SERVER["HTTP_HOST"]."/".$root_sistema."includes/php/hybridauth/", 

	"providers" => array (
		// openid providers
		"OpenID" => array (
			"enabled" => false
		),

		"AOL"  => array ( 
			"enabled" => false 
		),

		"Yahoo" => array ( 
			"enabled" => false,
			"keys"    => array ( "id" => "", "secret" => "" )
		),

		"Google" => array ( 
			"enabled" => false,
			"keys"    => array ( "id" => "", "secret" => "" )
		),

		"Facebook" => array ( 
			"enabled" => true,
			"keys"    => array ( "id" => "", "secret" => "" ),
			"scope"   => ['email', 'user_birthday', 'user_hometown'],
		),

		"Twitter" => array ( 
			"enabled" => false,
			"keys"    => array ( "key" => "", "secret" => "" ) 
		),

		// windows live
		"Live" => array ( 
			"enabled" => false,
			"keys"    => array ( "id" => "", "secret" => "" ) 
		),

		"MySpace" => array ( 
			"enabled" => false,
			"keys"    => array ( "key" => "", "secret" => "" ) 
		),

		"LinkedIn" => array ( 
			"enabled" => false,
			"keys"    => array ( "key" => "", "secret" => "" ) 
		),

		"Foursquare" => array (
			"enabled" => false,
			"keys"    => array ( "id" => "", "secret" => "" ) 
		),
	),

	// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
	"debug_mode" => false,

	"debug_file" => ""
);

if($_HYBRIDAUTH){
	if($_HYBRIDAUTH['Facebook']['id']) $hybridauth["providers"]['Facebook']['keys']['id'] = $_HYBRIDAUTH['Facebook']['id'];
	if($_HYBRIDAUTH['Facebook']['secret']) $hybridauth["providers"]['Facebook']['keys']['secret'] = $_HYBRIDAUTH['Facebook']['secret'];
}

return $hybridauth;
