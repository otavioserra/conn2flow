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
	$id = $_POST["galeria"];
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
		$campos = null;
		
		$campo_nome = "id_galerias"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"imagens"
		);
		
		$imagens_id = banco_last_id();
		
		$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."galeria".$_SYSTEM['SEPARADOR'];
		$caminho_internet 		= 	"files/galeria/";
		
		$aux = explode('.',basename($_FILES['uploadfile']['name']));
		$extensao = strtolower($aux[count($aux)-1]);
		
		if($_SYSTEM['IMG_GRANDE_WIDTH'] && $_SYSTEM['IMG_GRANDE_HEIGHT'])$flag_grande = true;
		if($_SYSTEM['IMG_MEDIA_WIDTH'] && $_SYSTEM['IMG_MEDIA_HEIGHT'])$flag_media = true;
		if($_SYSTEM['IMG_MINI_WIDTH'] && $_SYSTEM['IMG_MINI_HEIGHT'])$flag_mini = true;
		
		$img_original = "galeria_".$id."_imagem_".$imagens_id.".".$extensao;
		if($flag_grande)$img_grande = "galeria_".$id."_imagem_".$imagens_id."_grande.".$extensao;
		if($flag_media)$img_media = "galeria_".$id."_imagem_".$imagens_id."_media.".$extensao;
		if($flag_mini){
			$img_mini = "galeria_".$id."_imagem_".$imagens_id."_mini.".$extensao;
			if($_PROJETO['galeria']['criar-miniatura-escala-cinza'])$img_mini_pb = "galeria_".$id."_imagem_".$imagens_id."_mini_pb.".$extensao;
		}
		
		$file = $caminho_fisico . $img_original; 
		$size = $_FILES['uploadfile']['size'];
		
		if($size > 10000000){
			$saida = "erro 1 - file size > 10 MB";
			unlink($_FILES['uploadfile']['tmp_name']);
			exit;
		}
		
		if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $file)){ 
			$saida = "success"; 
			chmod($file, 0777);
		} else {
			$saida = "erro 2 - ".$_FILES['uploadfile']['error']." --- ".$_FILES['uploadfile']['tmp_name']." %%% ".$file."($size)";
		}
		
		$original = $caminho_fisico . $img_original;
		if($flag_grande)$grande = $caminho_fisico . $img_grande;
		if($flag_media)$media = $caminho_fisico . $img_media;
		if($flag_mini){
			$mini = $caminho_fisico . $img_mini;
			if($_PROJETO['galeria']['criar-miniatura-escala-cinza'])$mini_pb = $caminho_fisico . $img_mini_pb;
		}
		
		$_RESIZE_IMAGE_Y_ZERO = true;
		
		if($flag_grande)resize_image($original, $grande, $_SYSTEM['IMG_GRANDE_WIDTH'], $_SYSTEM['IMG_GRANDE_HEIGHT'],false,false,true);
		if($flag_media)resize_image($original, $media, $_SYSTEM['IMG_MEDIA_WIDTH'], $_SYSTEM['IMG_MEDIA_HEIGHT'],false,false,true);
		if($flag_mini){
			resize_image($original, $mini, $_SYSTEM['IMG_MINI_WIDTH'], $_SYSTEM['IMG_MINI_HEIGHT'],false,false,true);
			if($_PROJETO['galeria']['criar-miniatura-escala-cinza'])filtrar_image($mini, $mini_pb, IMG_FILTER_GRAYSCALE);
		}
		
		$campo_tabela = "imagens";
		$campo_nome = "local_original"; 				$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_original . "'";
		$campo_nome = "local_grande"; if($flag_grande){	$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_grande . "'";}
		$campo_nome = "local_media"; if($flag_media){	$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_media . "'";}
		$campo_nome = "local_mini"; if($flag_mini){		$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_mini . "'";}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				"imagens",
				"WHERE id_imagens='".$imagens_id."'"
			);
		}
		
		banco_fechar_conexao();
		
		return $saida;
	} else {
		return "erro 3 - Usuário sem acesso!";
	}
}

echo "Resposta: " . uploadfile();

?>