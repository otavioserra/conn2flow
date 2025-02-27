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
$_LOCAL_ID					=	"uploadimg";
$_PERMISSAO					=	false;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
$_HTML['LAYOUT']			=	"../layout.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function uploadfile(){
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_PROJETO;
	
	$new_width = 150;
	$new_height = 95;
	$id = $_POST["id"];
	$usuario = $_POST["usuario"];
	$sessao = $_POST["sessao"];
	$img_mini_w = 100;
	$img_mini_h = 75;
	$img_pequeno_w = 300;
	$img_pequeno_h = 300;
	
	if($_PROJETO['produtos']){
		if($_PROJETO['produtos']['img_mini_w']){
			$img_mini_w = $_PROJETO['produtos']['img_mini_w'];
		}
		if($_PROJETO['produtos']['img_mini_h']){
			$img_mini_h = $_PROJETO['produtos']['img_mini_h'];
		}
		if($_PROJETO['produtos']['img_pequeno_w']){
			$img_pequeno_w = $_PROJETO['produtos']['img_pequeno_w'];
		}
		if($_PROJETO['produtos']['img_pequeno_h']){
			$img_pequeno_h = $_PROJETO['produtos']['img_pequeno_h'];
		}
		
	}
	
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
		if(!$id){
			$caminho_fisico_session = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."tmp".$_SYSTEM['SEPARADOR'].$sessao.$_SYSTEM['SEPARADOR'];
			$caminho_fisico_produto = $caminho_fisico_session.'produtos'.$_SYSTEM['SEPARADOR'];
			
			if(!is_dir($caminho_fisico_session)){
				mkdir($caminho_fisico_session, 0777);
				chmod($caminho_fisico_session, 0777);
			}
			if(!is_dir($caminho_fisico_produto)){
				mkdir($caminho_fisico_produto, 0777);
				chmod($caminho_fisico_produto, 0777);
			}
			
			$caminho_fisico = $caminho_fisico_produto;
			$caminho_internet = '/'.$_SYSTEM['ROOT']."files/tmp/".$sessao."/produtos/";
		} else {
			$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$id.$_SYSTEM['SEPARADOR'];
			
			if(!is_dir($caminho_fisico)){
				mkdir($caminho_fisico, 0777);
				chmod($caminho_fisico, 0777);
			}
			
			$caminho_internet = '/'.$_SYSTEM['ROOT']."files/produtos/produto".$id."/";
		}
		
		$aux = explode('.',basename($_FILES['uploadfile']['name']));
		$extensao = strtolower($aux[count($aux)-1]);
		
		$uniqid = uniqid();
		
		$img_original = 'produto_' . $uniqid . '.' . $extensao;
		$img_mini = 'produto_mini_' . $uniqid . '.' . $extensao;
		$img_pequeno = 'produto_pequeno_' . $uniqid . '.' . $extensao;
		
		$file = $caminho_fisico . $img_original;
		$size = $_FILES['uploadfile']['size'];
		
		if($size > 10000000){
			return "erro 1 - file size > 10 MB";
			unlink($_FILES['uploadfile']['tmp_name']);
			exit;
		}
		
		if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file)){ 
			$saida = $caminho_internet.$img_original.','.$caminho_internet.$img_pequeno.','.$caminho_internet.$img_mini;
			chmod($file, 0777);
		} else {
			$saida = "erro 2 - ".$_FILES['uploadfile']['error']." --- ".$_FILES['uploadfile']['tmp_name']." %%% ".$file."($size)";
		}
		
		$original = $caminho_fisico . $img_original;
		$mini = $caminho_fisico . $img_mini;
		$pequeno = $caminho_fisico . $img_pequeno;
		
		resize_image($original, $mini, $img_mini_w, $img_mini_h,false,false,true);
		resize_image($original, $pequeno, $img_pequeno_w, $img_pequeno_h,false,false,true);
		
		return $saida;
	} else {
		return "erro 3 - Usuário sem acesso!";
	}
}

echo "Resposta: " . uploadfile();

?>