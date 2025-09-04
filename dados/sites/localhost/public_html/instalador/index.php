<?php

$_INDEX							=	Array();

if($_SERVER['SERVER_NAME'] == "localhost"){
	$_INDEX['sistemas-dir']			=	'/var/www/sites/localhost/conn2flow-gestor/';
} else {
	$_INDEX['sistemas-dir']			=	'/var/www/sites/localhost/conn2flow-gestor/';
}

require_once($_INDEX['sistemas-dir'] . 'gestor.php');

?>