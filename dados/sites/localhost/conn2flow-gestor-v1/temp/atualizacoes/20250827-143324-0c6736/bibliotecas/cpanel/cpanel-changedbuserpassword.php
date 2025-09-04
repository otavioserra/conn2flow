<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;

cpanel_query(Array(
	'cpanel_user' => $_CPANEL['CPANEL_USER'],
	'cpanel_module' => "MysqlFE",
	'cpanel_func' => "changedbuserpassword",
	'data' => array(
		'dbuser' => $_CPANEL['DB_CHANGE_USER_PASS']['user'],
		'password' => $_CPANEL['DB_CHANGE_USER_PASS']['pass'],
	),
));

?>