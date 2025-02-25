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

$_VERSAO_MODULO				=	'1.2.0';
$_LOCAL_ID					=	"e-service";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	".";
$_JS_TOOLTIP_INICIO			=	true;
$_HTML['LAYOUT']			=	"layout.html";


include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

$_HTML['titulo'] 						= 	$_HTML['titulo']."E-Service.";
$_HTML['variaveis']['titulo-modulo']	=	'E-Service';	

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

// Funções do sistema

function paginaInicial(){
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	
	
	return $pagina;
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	
	if($_GET[opcao])			$opcao = $_GET[opcao];
	if($_POST[opcao])			$opcao = $_POST[opcao];

	switch($opcao){
		//case 'mudar_logo_base':			$saida = (operacao('mudar_logo') ? mudar_logo_base() : paginaInicial());break;
		default: 						$saida = paginaInicial();
	}

	$_HTML['body'] = $saida;
	
	echo pagina();
}

main();

?>