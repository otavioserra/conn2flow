<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;
global $_CPANEL_USER;

cpanel_query(Array(
	'cpanel_user' => $_CPANEL_USER,
	'cpanel_module' => "Ftp",
	'cpanel_func' => "passwd",
	'data' => array(
		'user' => $_CPANEL['FTP_PASSWD']['user'],
		'pass' => $_CPANEL['FTP_PASSWD']['pass']
	),
));

?>