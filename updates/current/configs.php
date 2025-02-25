<?php

ignore_user_abort(true);

include('config.php');
include('functions.php');
include('geral.php');
include('banco.php');

$_LOCAL_ID								=		"configs";

$_BANCO['TYPE']							=		"mysqli";
$_BANCO['USUARIO']						=		$_B2MAKE['bd-user'];
$_BANCO['SENHA']						=		$_B2MAKE['bd-pass'];
$_BANCO['NOME']							=		$_B2MAKE['bd-user'];
$_BANCO['HOST']							=		"127.0.0.1";
$_BANCO['UTF8']							=		true;

function start(){
	global $_B2MAKE;
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
				case 'pub_id': $pub_id = $res['valor']; break;
			}
		}
	}
	
	$url = $url . 'configs.php';
	
	$pass = md5($_B2MAKE['bd-user'] . $_B2MAKE['bd-pass']);
	
	$data = false;
	$data['pass'] = $pass;
	$data['pub_id'] = $pub_id;
	
	$data = http_build_query($data);
	$curl = curl_init($url);

	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	$xml = curl_exec($curl);
	
	curl_close($curl);
	
	libxml_use_internal_errors(true);
	$obj_xml = simplexml_load_string($xml);
	
	if(!$obj_xml){
		return Array(
			'error' => $_LOCAL_ID.': XML inválido'
		);
	}
	
	if(count($obj_xml->error) > 0){
		return Array(
			'error' => $_LOCAL_ID.': XML Dados inválidos: '.$xml
		);
	}
	
	$loja = simpleXmlToArray($obj_xml->loja);
	
	echo $xml;
	
	echo "\n\n";
	
	foreach($loja as $chave => $valor){
		echo '<'.$chave.'>'.$valor.'</'.$chave.'>';
	}
	
	exit;

	return Array(
		'status' => 'Ok',
	);
}

function main(){
	print_xml(Array(
		'data' => start()
	));
}

main();

?>