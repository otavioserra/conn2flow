<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

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

global $_ONLY_REQUIRE_ACCESS;
global $_VERSAO_MODULO;
global $_LOCAL_ID;
global $_PERMISSAO;
global $_CAMINHO_RELATIVO_RAIZ;
global $_CAMINHO_MODULO_RAIZ;
global $_SYSTEM;

$_VERSAO_MODULO				=	'0.0.1';
$_LOCAL_ID					=	"install.host";
$_PERMISSAO					=	true;

if(!$_ONLY_REQUIRE_ACCESS)
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

// Funções do Sistema

function bih_pagina_inicial(){
	global $_B2MAKE;
	global $_SYSTEM;
	
	
	return '';
}

// ======================================= Ajax Chamadas ===============================================

function bih_ajax_start(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'installed',
			'installing',
		))
		,
		"host",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND atual IS TRUE"
	);
	
	if($resultado){
		$installed = $resultado[0]['installed'];
		$installing = $resultado[0]['installing'];
		
		if($installed){
			$saida = Array(
				'status' => 'Installed',
			);
		} else {
			if($installing){
				$saida = Array(
					'status' => 'Installing',
				);
			} else {
				$tokenPass = token_gerar('install.host');
				
				$saida = Array(
					'token' => $tokenPass['token'],
					'pass' => $tokenPass['pass'],
					'status' => 'Ok',
				);
			}
		}
	} else {
		$saida = Array(
			'status' => 'UserDontKnow',
		);
	}
	
	return $saida;
}

// ======================================================================================

function bih_xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
}

function bih_ajax(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_PROJETO;
	
	switch($_REQUEST["opcao"]){
		case 'start': $saida = bih_ajax_start(); break;
	}
	
	return (!$_AJAX_OUT_VARS['not-json-encode'] ? json_encode($saida) : $saida);
}

function bih_start(){
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_LISTA;
	global $_HTML;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if($_REQUEST[xml]){
		bih_xml();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			default: 							$saida = bih_pagina_inicial();
		}
		
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo bih_ajax();
	}
}

bih_start();

?>