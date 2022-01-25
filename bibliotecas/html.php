<?php

$_GESTOR['biblioteca-html']							=	Array(
	'versao' => '1.0.0',
);

function html_beautify($html){
	$config = [
		'indent'         		=> true,
		'indent-spaces'         => 4,
		'output-xhtml'   		=> false,
		'output-html'   		=> true,
		'wrap-script-literals' 	=> true,
		'show-body-only' 		=> true,
		'wrap' 					=> 200,
		'break-before-br' 		=> true,
	];

	$tidy = new tidy;
	$tidy->parseString(stripslashes($html), $config, 'utf8');
	$tidy->cleanRepair();

	return $tidy;
}

?>