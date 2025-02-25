<?php

/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@age1.com.br
	
	B2Make
	
	Copyright (c) 2014 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/
// Funções de Iniciação do sistema

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"updateservices";
$_PERMISSAO					=	false;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	".";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function autenticar_host(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	
	$pass = $_REQUEST['pass'];
	$pub_id = $_REQUEST['pub_id'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_usuario',
		))
		,
		"usuario",
		"WHERE pub_id='".$pub_id."'"
	);
	
	if($resultado){
		$id_usuario = $resultado[0]['id_usuario'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'token',
				'token_verificacao',
				'ftp_site_pass',
			))
			,
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS TRUE"
		);
		
		$token_local = $resultado[0]['token'];
		$token_verificacao = $resultado[0]['token_verificacao'];
		$ftp_site_pass = $resultado[0]['ftp_site_pass'];
		
		$token_verificacao_teste = md5($pass . $token_local);
		
		if($token_verificacao_teste == $token_verificacao){
			$_SESSION[$_SYSTEM['ID']."host_permissao"] = true;
			$_SESSION[$_SYSTEM['ID']."host_id_usuario"] = $id_usuario;
			
			$pass = md5($ftp_site_pass);
			
			return Array(
				'status' => 'Ok',
				'pass' => $pass,
			);
		} else {
			return Array(
				'error' => $_LOCAL_ID.': Token inválido',
			);
		}
	} else {
		return Array(
			'error' => $_LOCAL_ID.': Pub_id não conhecido',
		);
	}
}

function verificar_autenticacao(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	
	if(!$_SESSION[$_SYSTEM['ID']."host_permissao"]){
		header('Content-Type: text/xml; charset=utf-8');
		echo formatar_xml(Array('error' => $_LOCAL_ID.': Host não autenticado no sistema!'));
		exit;
	}
}

function services(){
	global $_SYSTEM;
	
	verificar_autenticacao();
	
	$id_usuario = $_SESSION[$_SYSTEM['ID']."host_id_usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'services_list',
		))
		,
		"host",
		"WHERE id_usuario='".$id_usuario."'"
		." AND atual IS TRUE"
	);
	
	return Array(
		'status' => 'Ok',
		'services_list' => $resultado[0]['services_list'],
	);
}

function start(){
	global $_HOST_VERSION;
	global $_LOCAL_ID;
	
	$opcao = $_REQUEST['option'];
	
	switch($opcao){
		case 'autenticate':
			return autenticar_host();
		break;
		case 'services':
			return services();
		break;
		default:
			return Array(
				'error' => $_LOCAL_ID.': Opção não definida!',
			);
	}
}


function main(){
	$saida = start();
	
	header('Content-Type: text/xml; charset=utf-8');
	echo formatar_xml($saida);
}

main();

?>