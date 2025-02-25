<?php

ignore_user_abort(true);

include('config.php');
include('geral.php');
include('banco.php');

$_LOCAL_ID								=		"services";

$_BANCO['TYPE']							=		"mysqli";
$_BANCO['USUARIO']						=		$_B2MAKE['bd-user'];
$_BANCO['SENHA']						=		$_B2MAKE['bd-pass'];
$_BANCO['NOME']							=		$_B2MAKE['bd-user'];
$_BANCO['HOST']							=		"127.0.0.1";
$_BANCO['UTF8']							=		true;

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

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_COOKIEJAR, "file.txt");
		curl_setopt($curl, CURLOPT_COOKIEFILE, "file.txt");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$xml = curl_exec($curl);
		
		curl_close($curl);
		
		libxml_use_internal_errors(true);
		$obj_xml = simplexml_load_string($xml);

		if(!$obj_xml){
			echo formatar_xml(Array(
				'error' => utf8_encode($_LOCAL_ID.': XML inválido')
			));
			exit;
		}
		
		if(count($obj_xml->error) > 0){
			echo formatar_xml(Array(
				'error' => utf8_encode($_LOCAL_ID.': XML Dados inválidos: '.$xml)
			));
			exit;
		}
		
		$pass = $obj_xml->pass;
		
		$token_verificacao_teste = md5($pass . $_B2MAKE['token']);
		
		if($token_verificacao_teste == $_B2MAKE['token_verificacao']){
			if(!$_NO_SESSION)session_start();
			
			$_SESSION[$_SYSTEM['ID']."b2make_permissao"] = true;
		} else {
			echo formatar_xml(Array(
				'error' => utf8_encode($_LOCAL_ID.': B2Make não validado')
			));
			exit;
		}
	} else {
		echo formatar_xml(Array(
			'error' => utf8_encode($_LOCAL_ID.': Não existe "variavel_global"')
		));
		exit;
	}
}

function start(){
	global $_B2MAKE;
	global $_LOCAL_ID;
	
	autenticar_host();
	
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
			}
		}
	}
	
	$url = $url . 'services.php';
	
	$data = false;
	$data['option'] = 'services';
	
	$data = http_build_query($data);
	$curl = curl_init($url);

	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_COOKIEJAR, "file.txt");
	curl_setopt($curl, CURLOPT_COOKIEFILE, "file.txt");
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	$xml = curl_exec($curl);
	
	curl_close($curl);
	
	libxml_use_internal_errors(true);
	$obj_xml = simplexml_load_string($xml);
	
	if(!$obj_xml){
		return Array(
			'error' => utf8_encode($_LOCAL_ID.': XML inválido')
		);
	}
	
	if(count($obj_xml->error) > 0){
		return Array(
			'error' => utf8_encode($_LOCAL_ID.': XML Dados inválidos: '.$xml)
		);
	}

	$services_list = $obj_xml->services_list;
	
	file_put_contents($_SERVER["DOCUMENT_ROOT"].'/servicos/services-list.json',$services_list);
	
	return Array(
		'status' => 'Ok',
		'services_list' => $_SERVER["DOCUMENT_ROOT"].'/servicos/services-list.json',
	);
}

function main(){
	$saida = start();
	
	echo formatar_xml($saida);
}

main();

?>