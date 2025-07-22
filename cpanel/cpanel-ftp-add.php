<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;
global $_CPANEL_USER;

cpanel_query(Array(
	'cpanel_user' => $_CPANEL_USER,
	'cpanel_module' => "Ftp",
	'cpanel_func' => "addftp",
	'data' => array(
		'user' => $_CPANEL['FTP_ADD']['user'],
		'pass' => $_CPANEL['FTP_ADD']['pass'],
		'homedir' => $_CPANEL['FTP_ADD']['homedir'],
		'quota' => $_CPANEL['FTP_ADD']['quota']
	),
));

?>