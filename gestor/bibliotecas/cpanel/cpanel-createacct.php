<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;
global $_CPANEL_SERVER_USER_OWNER;

whm_query(array(
	'option' => "createacct",
	'data' => array(
		'username' => $_CPANEL['ACCT']['user'],
		'password' => $_CPANEL['ACCT']['pass'],
		'domain' => $_CPANEL['ACCT']['domain'],
		'plan' => $_CPANEL['ACCT']['plan'],
		'contactemail' => $_CPANEL['ACCT']['email'],
		'owner' => $_CPANEL_SERVER_USER_OWNER,
	),
));

?>