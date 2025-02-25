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
$_LOCAL_ID					=	"uploadplayermusicas";
$_PERMISSAO					=	false;
$_INCLUDE_FTP				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	"";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }

function identificador_unico($id,$num,$id_site_player_musicas_mp3s,$id_auxiliar){
	$conteudo = banco_select
	(
		"id_site_player_musicas_mp3s"
		,
		"site_player_musicas_mp3s",
		"WHERE name='".($num ? $id.'-'.$num : $id)."'"
		.($id_site_player_musicas_mp3s?" AND id_site_player_musicas_mp3s!='".$id_site_player_musicas_mp3s."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1,$id_site_player_musicas_mp3s,$id_auxiliar);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador($id,$id_site_player_musicas_mp3s = false,$id_auxiliar = false){
	$tam_max_id = 90;
	$id = retirar_acentos(trim($id));
	
	$pre_id_aux = explode('-',$id);
	
	if($pre_id_aux)
	foreach($pre_id_aux as $pre){
		$count++;
		if($pre){
			$pre_id .= $pre;
			
			if(strlen($pre_id) > $tam_max_id){
				break;
			} else {
				$pre_id .= (count($pre_id_aux) > $count ? '-' : '');
			}
		}
	}
	
	$id = $pre_id;
	
	$id_aux = explode('-',$id);
	$count = 0;
	if(count($id_aux) > 1 && is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		return identificador_unico($id,$num,$id_site_player_musicas_mp3s,$id_auxiliar);
	} else {
		return identificador_unico($id,0,$id_site_player_musicas_mp3s,$id_auxiliar);
	}
}

function uploadfile(){
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_PROJETO;
	global $_CONEXAO_FTP;
	
	$id = $_REQUEST["player"];
	$usuario = $_REQUEST["user"];
	$sessao = $_REQUEST["session_id"];
	$id_upload = $_REQUEST["id_upload"];
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
		if(!$_CONEXAO_FTP)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario',
				'id_usuario_pai',
			))
			,
			"usuario",
			"WHERE usuario='".$usuario."'"
			." AND status='A'"
		);
		
		$id_usuario = ($resultado[0]['id_usuario_pai'] ? $resultado[0]['id_usuario_pai'] : $resultado[0]['id_usuario']);
		
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'path',
			))
			,
			"site_player_musicas",
			"WHERE id_site_player_musicas='".$id."'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		if(!$resultado2){
			return json_encode(Array(
				'status' => 'Error - user without permission'
			));
		}
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-playermusicas-path']);
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_player_musicas",
				"WHERE id_site_player_musicas='".$id."'"
			);
			
			$player_musicas_path = $resultado[0]['path'];
			
			ftp_chdir($_CONEXAO_FTP,$player_musicas_path);
		} else {
			return json_encode(Array(
				'status' => 'Error conexao FTP nao realizada'
			));
		}
		
		$aux = explode('.',basename($_FILES['files']['name'][0]));
		$extensao = strtolower($aux[count($aux)-1]);
		
		$nome = preg_replace('/\.'.$extensao.'/i', '', $_FILES['files']['name'][0]);
		$nome_original = $nome;
		$nome = criar_identificador($nome);
		
		$extensao = strtolower($extensao);
		
		$nome_extensao = $nome . '.' . $extensao;
		
		$size = $_FILES['files']['size'][0];
		
		if($size > 10000000){
			unlink($_FILES['files']['tmp_name'][0]);
			ftp_fechar_conexao();
			return json_encode(Array(
				'status' => 'Error - file size > 10 MB'
			));
		}
		
		$tmp_mp3 = $_SYSTEM['TMP'].'mp3-tmp'.session_id().'.'.$extensao;
		
		if(move_uploaded_file($_FILES['files']['tmp_name'][0], $tmp_mp3)){  
			$_RESIZE_IMAGE_Y_ZERO = true;
			
			$campos = null;
			
			$campo_nome = "id_site_player_musicas"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome_original"; $campo_valor = $nome_original; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = 'A'; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"site_player_musicas_mp3s"
			);
			
			$id_site_player_musicas_mp3s = banco_last_id();
			
			$campo_tabela = "site_player_musicas_mp3s";
			$campo_nome = "name"; 				$editar[$campo_tabela][] = $campo_nome."='" . $nome . "'";
			$campo_nome = "file"; 				$editar[$campo_tabela][] = $campo_nome."='" . $nome_extensao . "'";
			$campo_nome = "ext"; 				$editar[$campo_tabela][] = $campo_nome."='" . $extensao . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"site_player_musicas_mp3s",
					"WHERE id_site_player_musicas_mp3s='".$id_site_player_musicas_mp3s."'"
				);
			}
			
			ftp_put_file($nome_extensao, $tmp_mp3);
			
			unlink($tmp_mp3);
			
			ftp_fechar_conexao();
			
			banco_update
			(
				"diskchanged=NULL",
				"host",
				"WHERE id_usuario='".$id_usuario."'"
				." AND atual IS TRUE"
			);
			
			return json_encode(Array(
				'status' => 'Ok',
				'id_upload' => $id_upload,
				'id' => $id_site_player_musicas_mp3s,
				'nome_original' => $nome_original,
				'mp3' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-playermusicas-path'] . '/' . $player_musicas_path . '/' . $nome_extensao,
				'size' => $size,
				'file' => $nome_extensao,
			));
		} else {
			ftp_fechar_conexao();
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