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
	//$id = $_POST["galeria"];
	$usuario = $_POST["usuario"];
	$sessao = $_POST["sessao"];
	
	$status = 'A';
	
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
		$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."background".$_SYSTEM['SEPARADOR'];
		$caminho_internet 		= 	"files/background/";
		$caminho_video	 		=	$_SYSTEM['PATH']."images".$_SYSTEM['SEPARADOR']."icons".$_SYSTEM['SEPARADOR']."play_modelo.jpg";
		
		if(!is_dir($caminho_fisico)){
			mkdir($caminho_fisico,0777,true);
			chmod($caminho_fisico,0777);
		}
		
		$aux = explode('.',basename($_FILES['uploadfile']['name']));
		$extensao = strtolower($aux[count($aux)-1]);
		
		$background = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_variavel_global',
				'variavel',
				'valor',
			))
			,
			"variavel_global",
			"WHERE grupo='background'"
		);
		
		foreach($background as $back){
			$val = $back['valor'];
			$id->$back['variavel'] = $back['id_variavel_global'];
			switch($back['variavel']){
				case 'dados':
					$dados = $val;
				break;
				case 'id':
					if(!$num) $num = 1;
					$num = (int)$val;
				break;
				
			}
		}
		
		$img_original = "background".$num.".".$extensao;
		$img_mini =  "background".$num."_mini.".$extensao;
		
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
			
			switch($extensao){
				case 'webm':
				case 'ogv':
				case 'm4v':
					$flag->video = true;
				break;
			}
			
			if($flag->video){
				$img_mini = "background".$num."_mini.jpg";
				$mini = $caminho_fisico . $img_mini;
				copy($caminho_video,$mini);
				chmod($mini, 0777);
				$tipo = 'vid';
			} else {
				$original = $caminho_fisico . $img_original;
				$mini = $caminho_fisico . $img_mini;
				
				$_RESIZE_IMAGE_Y_ZERO = true;
				
				resize_image($original, $mini, $_SYSTEM['IMG_MINI_WIDTH'], $_SYSTEM['IMG_MINI_HEIGHT'],false,false,true);
				$tipo = 'img';
			}
			
			$dados .= $tipo . ',' . $status . ',' . $num . ',' . $caminho_internet . $img_original . ',' . $caminho_internet . $img_mini . ';';
			
			banco_update
			(
				"valor='".$dados."'",
				"variavel_global",
				"WHERE id_variavel_global='" . $id->dados . "'"
			);
			banco_update
			(
				"valor='".($num+1)."'",
				"variavel_global",
				"WHERE id_variavel_global='" . $id->id . "'"
			);
			
		} else {
			$saida = "erro 2 - ".$_FILES['uploadfile']['error']." --- ".$_FILES['uploadfile']['tmp_name']." %%% ".$file."($size)";
		}
		
		banco_fechar_conexao();
		
		return $saida;
	} else {
		return "erro 3 - Usuário sem acesso!";
	}
}

echo "Resposta: " . uploadfile();

?>