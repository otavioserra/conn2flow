<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;

if($_CPANEL['ACCT']['domain_owner']){
	cpanel_query(Array(
		'cpanel_user' => $_CPANEL['ACCT']['user'],
		'cpanel_module' => "Park",
		'cpanel_func' => "unpark",
		'data' => array(
			'domain' => $_CPANEL['ACCT']['domain_park'],
		),
	));
	
	whm_query(array(
		'option' => "modifyacct",
		'data' => array(
			'user' => $_CPANEL['ACCT']['user'],
			'DNS' => $_CPANEL['ACCT']['domain_owner'],
		),
	));
}

?>