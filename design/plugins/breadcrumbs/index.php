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

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"site_builder";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../../";
$_CAMINHO_MODULO_RAIZ		=	"../../../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

// ======================================= Ajax Chamadas ===============================================

function ajax_pagina_arvore_pai($atual_id){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_pai',
			'nome',
			'id',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site='".$atual_id."'"
	);
	
	if($resultado){
		if($resultado[0]['id_site_pai']){
			$pagina = Array(
				'nome' => $resultado[0]['nome'],
				'id' => $resultado[0]['id'],
				'id_site_pai' => $resultado[0]['id_site_pai'],
				'pai' => ajax_pagina_arvore_pai($resultado[0]['id_site_pai']),
			);
			
			return $pagina;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function ajax_pagina_arvore(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$atual_id = $_REQUEST['atual_id'];
	
	if($atual_id){
		$saida = Array(
			'pagina_arvore' => ajax_pagina_arvore_pai($atual_id),
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'NoIdDefined'
		);
	}
	
	return $saida;
}

// ======================================================================================

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
}

function ajax(){
	global $_SYSTEM;
	global $_AJAX_OUT_VARS;
	global $_PROJETO;
	
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $permissao){
		if($permissao == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
			$permissao_flag = true;
			break;
		}
	}
	
	if(!$permissao_flag){
		$saida = Array(
			'status' => 'SemPermissao',
		);
		
		return json_encode($saida);
	}
	
	switch($_REQUEST["opcao"]){
		case 'pagina-arvore': $saida = ajax_pagina_arvore(); break;
	}
	
	return (!$_AJAX_OUT_VARS['not-json-encode'] ? json_encode($saida) : $saida);
}

function start(){
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
		xml();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			//case 'testes':						$saida = site_testes(); break;
			//default: 							$saida = site_layout();
		}
		
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

start();

?>