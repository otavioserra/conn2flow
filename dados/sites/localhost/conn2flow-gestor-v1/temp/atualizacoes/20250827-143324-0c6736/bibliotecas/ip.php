<?php

global $_GESTOR;

$_GESTOR['biblioteca-ip']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares


// ===== Funções principais

function ip_check($ip, $allow_private = false, $proxy_ip = []){
	//Check for valid IP. If 'allow_private' flag is set to truthy, it allows private IP ranges as valid client IP as well. (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
	//Pass your trusted reverse proxy IPs as $proxy_ip to exclude them from being valid.
	
	if(!is_string($ip) || is_array($proxy_ip) && in_array($ip, $proxy_ip)) return false;
	$filter_flag = FILTER_FLAG_NO_RES_RANGE;

	if(!$allow_private){
		//Disallow loopback IP range which doesn't get filtered via 'FILTER_FLAG_NO_PRIV_RANGE' [1]
		//[1] https://www.php.net/manual/en/filter.filters.validate.php
		if(preg_match('/^127\.$/', $ip)) return false;
		$filter_flag |= FILTER_FLAG_NO_PRIV_RANGE;
	}

	return filter_var($ip, FILTER_VALIDATE_IP, $filter_flag) !== false;
}

function ip_get($allow_private = false){
	//Get client's IP or null if nothing looks valid
	//Place your trusted proxy server IPs here.
	$proxy_ip = ['127.0.0.1'];

	//The header to look for (Make sure to pick the one that your trusted reverse proxy is sending or else you can get spoofed)
	$header = 'HTTP_X_FORWARDED_FOR'; //HTTP_CLIENT_IP, HTTP_X_FORWARDED, HTTP_FORWARDED_FOR, HTTP_FORWARDED

	//If 'REMOTE_ADDR' seems to be a valid client IP, use it.
	if(ip_check($_SERVER['REMOTE_ADDR'], $allow_private, $proxy_ip)) return $_SERVER['REMOTE_ADDR'];

	if(isset($_SERVER[$header])){
		//Split comma separated values [1] in the header and traverse the proxy chain backwards.
		//[1] https://en.wikipedia.org/wiki/X-Forwarded-For#Format
		$chain = array_reverse(preg_split('/\s*,\s*/', $_SERVER[$header]));
		foreach($chain as $ip) if(ip_check($ip, $allow_private, $proxy_ip)) return $ip;
	}

	return null;
}

?>