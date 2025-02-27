<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;
	
whm_query(array(
	'option' => "unsuspendacct",
	'data' => array(
		'user' => $_CPANEL['ACCT']['user'],
	),
));

?>