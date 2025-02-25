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
$_LOCAL_ID					=	"upload-files";
$_PERMISSAO					=	false;
$_INCLUDE_FTP				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	"";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }

function uploadfiles(){
	global $_SYSTEM;
	
	$name = $_REQUEST['name'];
	$lastModified = $_REQUEST['lastModified'];
	$size = $_FILES['files']['size'][0];
	
	$aux = explode('.',basename($name));
	$extensao = strtolower($aux[count($aux)-1]);
	
	$nome = preg_replace('/\.'.$extensao.'/i', '', $name);
	
	$extensao = strtolower($extensao);
	
	$nome_extensao = $nome . '.' . $extensao;
	
	$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
	$tmp_image_mini = $_SYSTEM['TMP'].'imagem-mini-tmp'.session_id().'.'.$extensao;
	
	$tmp_image = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'tmp'.$_SYSTEM['SEPARADOR'].$nome_extensao;
	
	if(move_uploaded_file($_FILES['files']['tmp_name'][0], $tmp_image)){
		//
	} else {
		gravar_log('Não Foi: '.print_r($_FILES['files']['tmp_name'],true));
	}
	
	$saida[] = Array(
		'name' => $name,
		'size' => $size,
		'url' => '//beta.b2make.com/files/tmp/'.$nome_extensao,
		'thumbnailUrl' => '//beta.b2make.com/files/tmp/'.$nome_extensao,
		'deleteUrl' => '//beta.b2make.com/files/tmp/'.$nome_extensao,
		'deleteType' => 'DELETE',
	);
	
	$json_return = json_encode($saida);
	
	header('Content-type: application/json');
	echo $json_return;
}

uploadfiles();

?>