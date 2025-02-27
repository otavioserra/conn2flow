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

$_VERSAO_MODULO				=	'1.0.1';
$_LOCAL_ID					=	"uploadfile";
$_PERMISSAO					=	false;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
$_HTML['LAYOUT']			=	"../layout.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function uploadfile(){
	global $_SYSTEM;
	
	$diretorio = $_POST["diretorio"];
	$usuario = $_POST["usuario"];
	$sessao = $_POST["sessao"];
	
	banco_conectar();
	
	$upload_permissao = banco_select_name
	(
		"id_upload_permissao"
		,
		"upload_permissao",
		"WHERE usuario='".$usuario."'".
		" AND session_id='".$sessao."'"
	);
	
	if($upload_permissao){
		$size = $_FILES['uploadfile']['size'];
		
		if($size > 10000000){
			$saida = "erro 1 - file size > 10 MB";
			unlink($_FILES['uploadfile']['tmp_name']);
			exit;
		}
		
		$filename = valid_filename($_FILES['uploadfile']['name']);
		
		if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $diretorio . $filename )){
			$mens = $diretorio . $filename;
			chmod($diretorio . $filename, 0777);
			$saida = "success: " . $mens;
		} else {
			$saida = "erro 2 - ".$_FILES['uploadfile']['error']." --- ".$_FILES['uploadfile']['tmp_name']." %%% ".$file."($size)";
		}
		
		return $saida;
	} else {
		return "erro 3 - usuário sem acesso!";
	}
	
	banco_fechar_conexao();
}

echo "Resposta: " . uploadfile();

?>