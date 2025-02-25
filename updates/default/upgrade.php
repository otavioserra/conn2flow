<?php

global $_LOCAL_ID;

$_LOCAL_ID								=		"upgrade";

function upgrade_start(){
	
}

function upgrade_main(){
	global $_UPGRADE_RETURN;
	
	$saida = upgrade_start();
	
	$_UPGRADE_RETURN = formatar_xml($saida);
}

upgrade_main();

?>