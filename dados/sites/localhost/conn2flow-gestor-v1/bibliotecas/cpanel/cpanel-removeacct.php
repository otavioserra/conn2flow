<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;

whm_query(array(
	'option' => "removeacct",
	'data' => array(
		'username' => $_CPANEL['ACCT']['user'],
	),
));

?>