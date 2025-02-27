<?php

$_INDEX							=	Array();

if($_SERVER['SERVER_NAME'] == "localhost"){
	$_INDEX['sistemas-dir']											=	'';
	$_INDEX['sistemas-dir-root']									=	'../';
} else if($_SERVER['SERVER_NAME'] == "beta.entrey.com.br"){
	$_INDEX['sistemas-dir-root'] = $_INDEX['sistemas-dir']			=	'../';
} else {
	$_INDEX['sistemas-dir-root'] = $_INDEX['sistemas-dir']			=	'../';
}

require_once($_INDEX['sistemas-dir-root'] . 'b2make-gestor/gestor.php');

?>