<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;

if($_CPANEL['ACCT']['domain_owner']){
	whm_query(array(
		'option' => "modifyacct",
		'data' => array(
			'user' => $_CPANEL['ACCT']['user'],
			'DNS' => $_CPANEL['ACCT']['domain_owner'],
		),
	));
	
	$obj_json = cpanel_query(Array(
		'return' => true,
		'cpanel_user' => $_CPANEL['ACCT']['user'],
		'cpanel_module' => "Park",
		'cpanel_func' => "listparkeddomains",
		'data' => array(
			'regex' => $_CPANEL_DEFAULT_PARK_REGEX,
		),
	));
	
	if(!$obj_json->cpanelresult->data[0]->domain){
		cpanel_query(Array(
			'cpanel_user' => $_CPANEL['ACCT']['user'],
			'cpanel_module' => "Park",
			'cpanel_func' => "park",
			'data' => array(
				'domain' => $_CPANEL['ACCT']['domain_park'],
			),
		));
	}
}

?>