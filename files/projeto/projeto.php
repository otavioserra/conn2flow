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

function projeto_modificar_campos($params){
	$id = $params['id'];
	$campos = $params['campos'];
	$parametros = $params['parametros'];
	
	switch($id){
		case 'cadastro_banco':
		
		break;
		case 'contato_banco':
		
		break;
		case 'cadastrar_email':
		
		break;
		case 'procurar':
		
		break;
		case 'conteudo':
		
		break;
		case 'noticias_lista_dinamico':
		
		break;
		case 'blog_dinamico':
		
		break;
		
	}
	
	$saida = Array(
		'campos' => $campos,
		'parametros' => $parametros,
	);
	
	return $saida;
}

function projeto_modulos($params){
	$modulo_tag = $params['modulo_tag'];
	$modulo = $params['modulo'];
	
	/*
	Necessário adicionar o módulo no /files/projeto/config.php
	modulos_add_tags(Array(
		'#modulo_tag#',
	));
	*/
	/* 
	switch($modulo_tag){
		case '#modulo_tag#': 
			$modulo = '';
		break;
		
	} */
	
	return $modulo;
}

function projeto_pagina_nao_encontrada($params){
	$pagina = $params['pagina'];
	
	return $pagina;
}

function projeto_pagina_inicial($params){
	$pagina = $params['pagina'];
	
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	
	
	if($connect_db)banco_fechar_conexao();
	
	return $pagina;
}

function projeto_pagina_layout($params){
	$pagina = $params['pagina'];
	
	return $pagina;
}

function projeto_layout($params){
	$pagina = $params['pagina'];
	
	return $pagina;
}

function projeto_xml($params){
	$entrada = $params['entrada'];
	
	$saida = $entrada;
	
	return $saida;
}

function projeto_ajax($params){
	$entrada = $params['entrada'];
	$saida = $params['saida'];
	
	return $saida;
}

function projeto_main_opcao(){
	global $_OPCAO;
	
	switch($_OPCAO){
		//case 'opcao':					$saida = opcao(); break;
		default: 						$saida = conteudo();
	}
	
	return $saida;
}

function projeto_main($params){
	global $_VARIAVEIS_JS; // Variável global de passagem de dados para o JS
	
	$entrada = $params['entrada'];
	
	$saida = $entrada;
	
	return $saida;
}

?>