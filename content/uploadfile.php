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
$_INCLUDE_FTP				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	"";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function uploadfile(){
	global $_SYSTEM;
	
	$usuario = $_REQUEST["user"];
	$sessao = $_REQUEST["session_id"];
	$id_upload = $_REQUEST["id_upload"];
	$input_id = $_REQUEST["input_id"];
	$_FILES['files']['name'][0] = $_REQUEST["name"];
	
	$upload_permissao = banco_select_name
	(
		"id_upload_permissao"
		,
		"upload_permissao",
		"WHERE usuario='".$usuario."'".
		" AND session_id='".$sessao."'"
	);
	
	if($upload_permissao && md5(session_id()) == $sessao){
		$aux = explode('.',basename($_FILES['files']['name'][0]));
		$extensao = strtolower($aux[count($aux)-1]);
		
		$nome = preg_replace('/\.'.$extensao.'/i', '', $_FILES['files']['name'][0]);
		
		$extensao = strtolower($extensao);
		
		$nome_extensao_original = $nome . '.' . $extensao;
		$nome_extensao = $sessao.'_'.$id_upload . '.' . $extensao;
		
		$size = $_FILES['files']['size'][0];
		
		if($size > 10000000){
			unlink($_FILES['files']['tmp_name'][0]);
			return json_encode(Array(
				'status' => 'Error - file size > 10 MB'
			));
		}
		
		if(!is_dir($_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'uploads-tmp')){
			mkdir($_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'uploads-tmp');
		}
		
		$files = glob($_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'uploads-tmp'.$_SYSTEM['SEPARADOR']."*");
		$now = time();
		
		if($files)
		foreach($files as $file){
			if(is_file($file)){
				if($now - filemtime($file) >= 60 * 20){
					unlink($file);
				}
			}
		}
		
		$tmp_arquivos = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'uploads-tmp'.$_SYSTEM['SEPARADOR'].$nome_extensao;
		
		if(move_uploaded_file($_FILES['files']['tmp_name'][0], $tmp_arquivos)){
			return json_encode(Array(
				'status' => 'Ok',
				'id_upload' => $id_upload,
				'size' => $size,
				'file_name' => $nome_extensao_original,
				'file_url_tmp' => '/'.$_SYSTEM['ROOT'].'files/uploads-tmp/'.$nome_extensao,
				'file_name_tmp' => $nome_extensao,
				'input_id' => $input_id,
			));
		} else {
			return json_encode(Array(
				'status' => 'Error - '.$_FILES['files']['error'][0]." --- ".$_FILES['files']['tmp_name'][0]." %%% ".$file."($size)"
			));
		}
	} else {
		return json_encode(Array('status' => 'SemPermissao'));
	}
	
}

header('Content-type: application/json');
echo uploadfile();

?>