<?php

function cpanel_log($txt){
	global $_CPANEL;
	global $_CPANEL_LOG_PATH;
	
	if($_CPANEL['LOG_TXT']){
		$_CPANEL['LOG_TXT_VALUE'] .= $txt;
	} else {
		$path = $_CPANEL_LOG_PATH;
		$sep = DIRECTORY_SEPARATOR;
		$path_logs = $path.$sep.'logs';
		$log_file = $path_logs.$sep.'cpanel-'.date('d-m-Y').'.log';
		
		if(!is_dir($path_logs)){
			mkdir($path_logs);
		}
		
		if(is_file($log_file)){
			$log_txt = file_get_contents($log_file);
		}
		
		$log_txt .= "[".date('H:i:s')."] - ".$txt."\n";
		
		file_put_contents($log_file,$log_txt);
	}
}

function cpanel_find_error($xml){
	global $_CPANEL;
	
	libxml_use_internal_errors(true);
	$doc = simplexml_load_string($xml);

	if($doc){
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($xml);

		$cpanelresult = $xmlDoc->getElementsByTagName('cpanelresult');

		foreach( $cpanelresult as $result ){
			$errors = $result->getElementsByTagName( "error" );
			$error = $errors->item(0)->nodeValue;
			
			if($error){
				$_CPANEL['ERROR'] = utf8_decode($error);
			}
		}
		
		$xmlObj = new SimpleXMLElement($xml);
		$status = $xmlObj->result[0]->status;
		
		if($status == '0'){
			$statusmsgs = $xmlObj->result[0]->statusmsg;
			$_CPANEL['ERROR'] = utf8_decode($statusmsgs);
		}
	} else {
		libxml_clear_errors();
	}
}

function whm_query($params = false){
	global $_CPANEL;
	global $_CPANEL_SERVER_DOMAIN;
	global $_CPANEL_SERVER_TOKEN;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$data = http_build_query($data);

	$host = $_CPANEL_SERVER_DOMAIN;
	$user = "root";
	$token = $_CPANEL_SERVER_TOKEN;

	$query = "https://" . $host . ":2087/json-api/".$option."?api.version=1".($data ? "&".$data : "");

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);

	$header[0] = "Authorization: whm $user:$token";
	curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
	curl_setopt($curl, CURLOPT_URL, $query);

	$result = curl_exec($curl);
	
	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if($http_status != 200){
		if($_CPANEL['LOG']){
			cpanel_log("Error HTTP STATUS:" . $http_status . " returned");
		}
	} else {
		if($_CPANEL['LOG']){
			$json = json_decode($result);
			cpanel_log($query."\n".print_r($json,true));
		}
		
		if($return){
			$return_data = json_decode($result);
		}
	}

	curl_close($curl);
	
	if($return){
		return $return_data;
	}
}

function cpanel_query($params = false){
	global $_CPANEL;
	global $_CPANEL_SERVER_DOMAIN;
	global $_CPANEL_SERVER_TOKEN;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$data = http_build_query($data);

	$host = $_CPANEL_SERVER_DOMAIN;
	$user = "root";
	$token = $_CPANEL_SERVER_TOKEN;

	$query = "https://" . $host . ":2087/json-api/cpanel?cpanel_jsonapi_user=".$cpanel_user."&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=".$cpanel_module."&cpanel_jsonapi_func=".$cpanel_func.($data ? "&".$data : "");

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);

	$header[0] = "Authorization: whm $user:$token";
	curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
	curl_setopt($curl, CURLOPT_URL, $query);

	$result = curl_exec($curl);

	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if($http_status != 200){
		if($_CPANEL['LOG']){
			cpanel_log("Error HTTP STATUS:" . $http_status . " returned");
		}
	} else {
		if($_CPANEL['LOG']){
			$json = json_decode($result);
			cpanel_log(print_r($json,true));
		}
		
		if($return){
			$return_data = json_decode($result);
		}
	}

	curl_close($curl);
	
	if($return){
		return $return_data;
	}
}

?>