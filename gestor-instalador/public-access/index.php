<?php

$_INDEX							=	Array();

if($_SERVER['SERVER_NAME'] == "dominio"){
	$_INDEX['sistemas-dir']			=	'caminho';
} else {
	$_INDEX['sistemas-dir']			=	'caminho';
}

require_once($_INDEX['sistemas-dir'] . 'gestor.php');

?>