<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;

cpanel_query(Array(
	'cpanel_user' => $_CPANEL['DB_ADD']['cpuser'],
	'cpanel_module' => "MysqlFE",
	'cpanel_func' => "createdb",
	'data' => array(
		'db' => $_CPANEL['DB_ADD']['name'],
	),
));

cpanel_query(Array(
	'cpanel_user' => $_CPANEL['DB_ADD']['cpuser'],
	'cpanel_module' => "MysqlFE",
	'cpanel_func' => "createdbuser",
	'data' => array(
		'dbuser' => $_CPANEL['DB_ADD']['user'],
		'password' => $_CPANEL['DB_ADD']['pass'],
	),
));

cpanel_query(Array(
	'cpanel_user' => $_CPANEL['DB_ADD']['cpuser'],
	'cpanel_module' => "MysqlFE",
	'cpanel_func' => "setdbuserprivileges",
	'data' => array(
		'privileges' => 'ALL PRIVILEGES',
		'db' => $_CPANEL['DB_ADD']['name'],
		'dbuser' => $_CPANEL['DB_ADD']['user'],
	),
));

?>