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

$_PROJETO_VERSAO							=		"1.0.0";
//$_PROJETO_JS								=	$_JS['jquery-tweet'];
$_PROJETO_DATA								=	2014;
$_AMBIENTE_TESTE							=	false;
$_AMBIENTE_TESTE_USER						=	'teste';
$_AMBIENTE_TESTE_PASS						=	'teste123';

//--------------------------------------- Variáveis do Banco de dados ------------------------------------------------

if($_SERVER['SERVER_NAME'] == "localhost"){
	$_BANCO['TYPE']							=		"mysql";
	$_BANCO['USUARIO']						=		"root";
	$_BANCO['SENHA']						=		"serra123";
	$_BANCO['NOME']							=		"cms4_1";
	$_BANCO['HOST']							=		"localhost";
	$_BANCO['UTF8']							=		true;
} else {
	$_BANCO['TYPE']							=		"mysql";
	$_BANCO['USUARIO']						=		"usuario";
	$_BANCO['SENHA']						=		"senha";
	$_BANCO['NOME']							=		"banco";
	$_BANCO['HOST']							=		"localhost";
	$_BANCO['UTF8']							=		false;
}

if($_LOCAL_ID == "index"){
/* modulos_add_tags(Array(
	'#modulo#',
)); */
}

function config_modificar_globais(){
	global $_LOCAL_ID;
	global $_INCLUDE_MODULO;
	
	switch($_LOCAL_ID){
		case 'index':
			//$_INCLUDE_MODULO = true;
		break;
	}
	
}

config_modificar_globais();

?>