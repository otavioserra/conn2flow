<?php

$_INDEX							=	Array();

if($_SERVER['SERVER_NAME'] == "localhost"){
	$_INDEX['sistemas-dir']											=	'';
	$_INDEX['sistemas-dir-root']									=	'../';
} else if($_SERVER['SERVER_NAME'] == "dominio"){
	$_INDEX['sistemas-dir-root'] = $_INDEX['sistemas-dir']			=	'../';
} else {
	$_INDEX['sistemas-dir-root'] = $_INDEX['sistemas-dir']			=	'../';
}

require_once($_INDEX['sistemas-dir-root'] . 'conn2flow-gestor/gestor.php');

?>