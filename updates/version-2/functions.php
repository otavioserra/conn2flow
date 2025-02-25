<?php

function print_xml($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	header('Content-Type: text/xml; charset=utf-8');
	echo formatar_xml($data);
	if(!$nao_sair)exit;
}

function autenticar_host(){
	global $_B2MAKE;
	global $_SYSTEM;
	global $_LOCAL_ID;
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'variavel',
			'valor',
		))
		,
		"variavel_global",
		"WHERE status='A'"
		." AND grupo='b2make'"
	);
	
	if($resultado){
		foreach($resultado as $res){
			switch($res['variavel']){
				case 'url': $url = $res['valor']; break;
				case 'version': $version = $res['valor']; break;
				case 'pub_id': $pub_id = $res['valor']; break;
			}
		}
		
		$pass = md5($_B2MAKE['bd-user'] . $_B2MAKE['bd-pass']);
		
		$data['pass'] = $pass;
		$data['pub_id'] = $pub_id;
		$data['option'] = 'autenticate';
		
		$url = $url . 'services.php';
		
		$data = http_build_query($data);
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_COOKIEJAR, ".curl-cookiejar");
		curl_setopt($curl, CURLOPT_COOKIEFILE, ".curl-cookiejar");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$xml = curl_exec($curl);
		
		curl_close($curl);
		
		libxml_use_internal_errors(true);
		$obj_xml = simplexml_load_string($xml);

		if(!$obj_xml){
			print_xml(Array(
				'data' => Array(
					'error' => $_LOCAL_ID.': XML inválido'
				)
			));
		}
		
		if(count($obj_xml->error) > 0){
			print_xml(Array(
				'data' => Array(
					'error' => $_LOCAL_ID.': XML Dados inválidos: '.$xml
				)
			));
		}
		
		$pass = $obj_xml->pass;
		
		$token_verificacao_teste = md5($pass . $_B2MAKE['token']);
		
		if($token_verificacao_teste == $_B2MAKE['token_verificacao']){
			$_SESSION[$_SYSTEM['ID']."b2make_permissao"] = true;
		} else {
			print_xml(Array(
				'data' => Array(
					'error' => $_LOCAL_ID.': B2Make não validado'
				)
			));
		}
	} else {
		print_xml(Array(
			'data' => Array(
				'error' => $_LOCAL_ID.': Não existe "variavel_global"'
			)
		));
	}
}

?>