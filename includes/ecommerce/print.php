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
$_PUBLICO					=	true;
$_LOCAL_ID					=	"print";
$_CAMINHO_RELATIVO_RAIZ		=	'../../';

include($_CAMINHO_RELATIVO_RAIZ."config.php");

$_HTML['titulo'] 			= 	$_HTML['titulo'] . 'Página de Impressão.';

if(!$_MOBILE){
	$_HTML['js'] .= 
	'	<link href="'.$_SYSTEM['TEMA_ROOT'].'layout'.($_REQUEST['_layouts_teste']?'-temp':'').'.css?v='.($_REQUEST['_layouts_versao']?$_REQUEST['_layouts_versao']:$_PROJETO_VERSAO).'" rel="stylesheet" type="text/css" />'."\n";
	
	$_HTML['css'] .= "	<link href=\"".$_CAMINHO_RELATIVO_RAIZ."includes/css/index.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
	$_HTML['css'] .= "	<link href=\"".$_CAMINHO_RELATIVO_RAIZ."includes/ecommerce/css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	
	echo '<!DOCTYPE html>
<html>
<head>
	<title>'.$_HTML['titulo'].'</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	'.$_HTML['css'].'
	<script type="text/javascript">
		var variaveis_js = '.$variaveis_js.';
	</script>
	'.$_HTML['js_padrao'].$_HTML['js'].'
</head>
<body onload="window.print()">
	'.$_SESSION[$_SYSTEM['ID']."versao-impressao"].'
</body>
</html>';
}

main();

?>