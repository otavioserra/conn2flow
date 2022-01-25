<?php

global $_GESTOR;

global $_CPANEL_SERVER_DOMAIN;
global $_CPANEL_SERVER_TOKEN;
global $_CPANEL_SERVER_USER_OWNER;
global $_CPANEL_IP_LOCAL;
global $_CPANEL;
global $_CPANEL_DEFAULT_PARK_REGEX;
global $_CPANEL_USER;
global $_CPANEL_IP;
global $_CPANEL_LOG_PATH;

//$_CPANEL['LOG'] = true;

$_CPANEL_SERVER_DOMAIN = "server.b2make.com";
$_CPANEL_SERVER_TOKEN = "0J3KNM4D5T1T2YLEB39OPUEM5FXV5VOP";
$_CPANEL_SERVER_USER_OWNER = "gestor";
$_CPANEL_IP_LOCAL = '127.0.0.1';

switch($_GESTOR['hosts-server']['local']){
	case 'betaServer0':
		$_CPANEL_DEFAULT_PARK_REGEX = 's0\.entrey\.com\.br';
		$_CPANEL_USER = 'root';
		$_CPANEL_IP = $_CPANEL_IP_LOCAL;
		$_CPANEL_LOG_PATH = '/home/betaentreycom/b2make-gestor';
	break;
	default:
		$_CPANEL_DEFAULT_PARK_REGEX = 's0\.entrey\.com\.br';
		$_CPANEL_USER = 'root';
		$_CPANEL_IP = $_CPANEL_IP_LOCAL;
		$_CPANEL_LOG_PATH = '/home/entreycom/b2make-gestor';
}

if($_CPANEL['CPANEL_USER']) $_CPANEL_USER = $_CPANEL['CPANEL_USER'];

?>