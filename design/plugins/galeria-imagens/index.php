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
$_LOCAL_ID					=	"site_builder";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../../";
$_CAMINHO_MODULO_RAIZ		=	"../../../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

// ======================================= Ajax Chamadas ===============================================

function ajax_galeria_imagens_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$nome = $_REQUEST['nome'];
	
	if($nome){
		$campos = null;
		
		$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site_galeria_imagens"
		);
		
		$id_site_galeria_imagens = banco_last_id();
		
		$num_total_rows = banco_total_rows
		(
			"site_galeria_imagens",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$galeria_imagens_nome = "galeriaimagens".$num_total_rows;
		
		banco_update
		(
			"nome='".$nome."',".
			"path='".$galeria_imagens_nome."'",
			"site_galeria_imagens",
			"WHERE id_site_galeria_imagens='".$id_site_galeria_imagens."'"
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-files-path'].'/'.$_SYSTEM['SITE']['ftp-files-galeria-imagens-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-galeria-imagens-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-galeria-imagens-path']);
			
			ftp_mkdir($_CONEXAO_FTP, $galeria_imagens_nome);
			
			ftp_fechar_conexao();
			
			$saida = Array(
				'status' => 'Ok',
				'galeria_imagens_nome' => $nome,
				'galeria_imagens_id' => $id_site_galeria_imagens,
			);
		} else {
			$saida = Array(
				'status' => 'FtpNotConnected'
			);
		}
	} else {
		$saida = Array(
			'status' => 'NoName'
		);
	}
	
	return $saida;
}

function ajax_galeria_imagens(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_galeria_imagens',
			'nome',
		))
		,
		"site_galeria_imagens",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_galeria_imagens ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_galeria_imagens' => $res['id_site_galeria_imagens'],
				'nome' => $res['nome'],
			);
		}
		
		$saida = Array(
			'resultado' => $resultado2,
			'status' => 'Ok'
		);
	} else {
		$saida = Array(
			'status' => 'Vazio'
		);
	}
	
	return $saida;
}

function ajax_galeria_imagens_images(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_galeria_imagens_imagens',
				'file',
				'width',
				'height',
			))
			,
			"site_galeria_imagens_imagens",
			"WHERE id_site_galeria_imagens='".$id."'"
			." AND status='A'"
			." ORDER BY id_site_galeria_imagens_imagens ASC"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_galeria_imagens",
				"WHERE id_site_galeria_imagens='".$id."'"
			);
			
			$galeriaimagens_path = $resultado2[0]['path'];
			
			foreach($resultado as $res){
				if($res['file']){
					if(!$res['width'] || !$res['height']){
						$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-galeria-imagens-path'] . '/' . $galeriaimagens_path . '/' . $res['file']);
						
						banco_update
						(
							"width='".$imgInfo[0]."',".
							"height='".$imgInfo[1]."'",
							"site_galeria_imagens_imagens",
							"WHERE id_site_galeria_imagens_imagens='".$res['id_site_galeria_imagens_imagens']."'"
						);
					} else {
						$imgInfo[0] = $res['width'];
						$imgInfo[1] = $res['height'];
					}
					
					$images[] = Array(
						'file' => $res['file'],
						'id' => $res['id_site_galeria_imagens_imagens'],
						'imagem' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-galeria-imagens-path'] . '/' . $galeriaimagens_path . '/' . $res['file'],
						'mini' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-galeria-imagens-path'] . '/' . $galeriaimagens_path . '/mini/' . $res['file'],
						'width' => $imgInfo[0],
						'height' => $imgInfo[1],
					);
				}
			}
			
			$saida = Array(
				'status' => 'Ok',
				'images' => $images
			);
		} else {
			$saida = Array(
				'status' => 'NaoExisteId'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_galeria_imagens_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_galeria_imagens',
			))
			,
			"site_galeria_imagens",
			"WHERE id_site_galeria_imagens='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_galeria_imagens";
			$campo_tabela_extra = "WHERE id_site_galeria_imagens='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
			$nome = $_REQUEST['nome'];
			
			$campo_nome = "nome"; $editar[$campo_tabela][] = $campo_nome."='" . $nome . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					$campo_tabela,
					$campo_tabela_extra
				);
			}
			
			$saida = Array(
				'status' => 'Ok',
				'nome' => $nome,
			);
		} else {
			$saida = Array(
				'status' => 'NaoExisteId'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_galeria_imagens_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_galeria_imagens',
				'path',
			))
			,
			"site_galeria_imagens",
			"WHERE id_site_galeria_imagens='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"site_galeria_imagens_imagens",
				"WHERE id_site_galeria_imagens='".$_REQUEST["id"]."'"
				." AND status='A'"
			);
			
			banco_update
			(
				"status='D'",
				"site_galeria_imagens_imagens",
				"WHERE id_site_galeria_imagens='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_galeria_imagens",
				"WHERE id_site_galeria_imagens='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				$galeria_imagens_path = $resultado[0]['path'];
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-galeria-imagens-path']);
				ftp_chdir($_CONEXAO_FTP,$galeria_imagens_path);
				
				if($resultado2){
					foreach($resultado2 as $res){
						$file = $res['file'];
						$size += ftp_size($_CONEXAO_FTP,$file);
						ftp_delete($_CONEXAO_FTP,$file);
					}
					
					ftp_chdir($_CONEXAO_FTP,'mini');
					
					foreach($resultado2 as $res){
						$file = $res['file'];
						$size += ftp_size($_CONEXAO_FTP,$file);
						ftp_delete($_CONEXAO_FTP,$file);
					}
					
					ftp_chdir($_CONEXAO_FTP,'..');
					ftp_rmdir($_CONEXAO_FTP,'mini');
					ftp_chdir($_CONEXAO_FTP,'..');
				} else {
					ftp_rmdir($_CONEXAO_FTP,'mini');
					ftp_chdir($_CONEXAO_FTP,'..');
				}
				
				ftp_rmdir($_CONEXAO_FTP,$galeria_imagens_path);
				
				ftp_fechar_conexao();
				
				$saida = Array(
					'size' => $size,
					'status' => 'Ok'
				);
			} else {
				$saida = Array(
					'status' => 'FtpNotConnected',
				);
			}
		} else {
			$saida = Array(
				'status' => 'NaoExisteId'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_galeria_imagens_images_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$galeria_imagens = $_REQUEST["galeria_imagens"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_galeria_imagens',
				'path',
			))
			,
			"site_galeria_imagens",
			"WHERE id_site_galeria_imagens='".$galeria_imagens."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_galeria_imagens_imagens',
					'file',
				))
				,
				"site_galeria_imagens_imagens",
				"WHERE id_site_galeria_imagens_imagens='".$id."'"
				." AND status='A'"
			);
			
			if($resultado2){
				banco_update
				(
					"status='D'",
					"site_galeria_imagens_imagens",
					"WHERE id_site_galeria_imagens_imagens='".$id."'"
				);
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					$galeria_imagens_path = $resultado[0]['path'];
					$file = $resultado2[0]['file'];
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-galeria-imagens-path']);
					ftp_chdir($_CONEXAO_FTP,$galeria_imagens_path);
					
					$size += ftp_size($_CONEXAO_FTP,$file);
					ftp_delete($_CONEXAO_FTP,$file);
					
					ftp_chdir($_CONEXAO_FTP,'mini');
					
					$size += ftp_size($_CONEXAO_FTP,$file);
					ftp_delete($_CONEXAO_FTP,$file);
					
					ftp_fechar_conexao();
					
					$saida = Array(
						'size' => $size,
						'status' => 'Ok',
					);
				} else {
					$saida = Array(
						'status' => 'FtpNotConnected',
					);
				}
			} else {
				$saida = Array(
					'status' => 'NaoExisteId'
				);
			}
		} else {
			$saida = Array(
				'status' => 'GaleriaNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

// ======================================================================================

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
}

function ajax(){
	global $_SYSTEM;
	global $_AJAX_OUT_VARS;
	global $_PROJETO;
	
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $permissao){
		if($permissao == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
			$permissao_flag = true;
			break;
		}
	}
	
	if(!$permissao_flag){
		$saida = Array(
			'status' => 'SemPermissao',
		);
		
		return json_encode($saida);
	}
	
	switch($_REQUEST["opcao"]){
		case 'galeria-imagens-add': $saida = ajax_galeria_imagens_add(); break;
		case 'galeria-imagens': $saida = ajax_galeria_imagens(); break;
		case 'galeria-imagens-images': $saida = ajax_galeria_imagens_images(); break;
		case 'galeria-imagens-edit': $saida = ajax_galeria_imagens_edit(); break;
		case 'galeria-imagens-del': $saida = ajax_galeria_imagens_del(); break;
		case 'galeria-imagens-images-del': $saida = ajax_galeria_imagens_images_delete(); break;
	}
	
	return (!$_AJAX_OUT_VARS['not-json-encode'] ? json_encode($saida) : $saida);
}

function start(){
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_LISTA;
	global $_HTML;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if($_REQUEST[xml]){
		xml();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			//case 'testes':						$saida = site_testes(); break;
			//default: 							$saida = site_layout();
		}
		
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

start();

?>