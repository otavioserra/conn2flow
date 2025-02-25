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

function publisher_inform_daemon($params = false){
	global $_DAEMON;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_B2MAKE_PAGINA_LOCAL) return;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$myFile = $_DAEMON['path'] . "daemon.json";
	$json = file_get_contents($myFile) or die("Can't open file");
	
	$obj = json_decode($json);
	
	if($options)
	foreach($options as $opt){
		switch($opt){
			case 'pages': $obj->publish_pages = true; break;
			case 'sitemaps': $obj->publish_sitemaps = true; break;
			case 'services': $obj->publish_services = true; break;
			case 'content': $obj->publish_content = true; break;
			case 'area_global': $obj->publish_area_global = true; break;
			case 'update_client': 
				$obj->publish_update_client = true;
				
				if(!$obj->publish_update_client_data){
					$obj->publish_update_client_data = Array();
				}
				
				$obj->publish_update_client_data[] = $data;
			break;
		}
	}
	
	file_put_contents($_DAEMON['path'] . "daemon.json",json_encode($obj));
}

function publisher_acesso_provisorio_criar(){
	global $_SYSTEM;
	
	$s6a_4p1sqc = 'b2make$1$KF3.Ln2.$9CuNCH147DEMx7SXNWdwQ/';
	$limite = 100;
	$inc = rand(1,$limite);
	$d_token_code = md5($s6a_4p1sqc.$inc);
	
	$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($usuario['id_usuario_pai']){
		$d_token_site = hashPassword($senha,$usuario['ftp_site_pass'],$inc);
		$d_token_files = hashPassword($senha,$usuario['ftp_files_pass'],$inc);
	} else {
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'ftp_site_pass',
				'ftp_files_pass',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$d_token_site = hashPassword($senha,$resultado[0]['ftp_site_pass'],$inc);
		$d_token_files = hashPassword($senha,$resultado[0]['ftp_files_pass'],$inc);
	}
	
	$campo_tabela = "usuario";
	$campo_tabela_extra = "WHERE id_usuario='".$usuario['id_usuario']."'";
	
	$campo_nome = "d_token_site"; $campo_valor = $d_token_site; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "d_token_files"; $campo_valor = $d_token_files; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "d_token_code"; $campo_valor = $d_token_code; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "d_token_data"; $editar[$campo_tabela][] = $campo_nome."=NOW()";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
}

function publisher_page_filhos($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'publicado',
		))
		,
		"site",
		"WHERE id_site_pai='".$id_site."'"
	);
	
	if($resultado)
	foreach($resultado as $res){
		if($res['publicado']) $publicado = true; else $publicado = false;
		
		if($publicado){
			$campo_tabela = "site";
			$campo_tabela_extra = "WHERE id_site='".$res['id_site']."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
			$campo_nome = "publicar_id_usuario"; $campo_valor = $usuario['id_usuario']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			$campo_nome = "publicar_status"; $campo_valor = 'P'; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					$campo_tabela,
					$campo_tabela_extra
				);
			}
			$editar = false;$editar_sql = false;
		}
		
		publisher_page_filhos(Array(
			'id_site' => $res['id_site']
		));
	}
}

function publisher_page($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	publisher_acesso_provisorio_criar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($publicado){
		$campo_tabela = "site";
		$campo_tabela_extra = "WHERE id_site='".$id_site."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
		
		$campo_nome = "publicar_id_usuario"; $campo_valor = $usuario['id_usuario']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "publicar_status"; $campo_valor = 'P'; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
		$editar = false;$editar_sql = false;
	}
	
	publisher_page_filhos(Array(
		'id_site' => $id_site
	));
	
	publisher_inform_daemon(Array(
		'options' => Array('pages')
	));
}

function publisher_sitemaps($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	publisher_acesso_provisorio_criar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	banco_update
	(
		"sitemaps_data=NOW(),".
		"sitemaps=1,".
		"sitemaps_id_usuario='".$usuario['id_usuario']."'",
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
	);
	
	publisher_inform_daemon(Array(
		'options' => Array('sitemaps')
	));
}

function publisher_all_pages($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'publicado',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
	);
	
	if($resultado)
	foreach($resultado as $res){
		if($res['publicado']) $publicado = true;
		
		publisher_page(Array(
			'id_site' => $res['id_site'],
			'publicado' => $publicado
		));
	}
}

function publisher_service($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$varios_servicos)publisher_acesso_provisorio_criar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$campo_tabela = "servicos";
	$campo_tabela_extra = "WHERE id_servicos='".$id_servicos."' AND id_loja='".$usuario['id_loja']."'";
	
	$campo_nome = "publicar_id_usuario"; $campo_valor = $usuario['id_usuario']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "publicar_status"; $campo_valor = 'P'; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "publicar_data"; $campo_valor = 'NOW()'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
	if($mobile){$campo_nome = "publicar_mobile"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;}
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	publisher_inform_daemon(Array(
		'options' => Array('services')
	));
}

function publisher_pagina_mestre_servicos($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($ids_servicos){
		publisher_acesso_provisorio_criar();
		
		foreach($ids_servicos as $id){
			publisher_service(Array(
				'id_servicos' => $id,
				'varios_servicos' => true,
				'mobile' => $mobile,
			));
			
		}
	}
}

function publisher_content($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$varios_conteudos)publisher_acesso_provisorio_criar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$campo_tabela = "site_conteudos";
	$campo_tabela_extra = "WHERE id_site_conteudos='".$id_site_conteudos."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
	
	$campo_nome = "publicar_id_usuario"; $campo_valor = $usuario['id_usuario']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "publicar_status"; $campo_valor = 'P'; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "publicar_data"; $campo_valor = 'NOW()'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
	if($mobile){$campo_nome = "publicar_mobile"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;}
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	publisher_inform_daemon(Array(
		'options' => Array('content')
	));
}

function publisher_pagina_mestre_conteudos($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($id_site_conteudos){
		publisher_acesso_provisorio_criar();
		
		foreach($id_site_conteudos as $id){
			publisher_content(Array(
				'id_site_conteudos' => $id,
				'varios_conteudos' => true,
				'mobile' => $mobile,
			));
			
		}
	}
}

function publisher_all_areas_global($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_areas_globais',
			'id',
			'html',
		))
		,
		"site_areas_globais",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
	);
	
	$dominio_antes = $_REQUEST['dominio_antes'];
	$dominio_depois = $_REQUEST['dominio_depois'];
	
	$https = $_REQUEST['https'];
	
	$dominio_antes = preg_quote($dominio_antes);
	
	if($resultado)
	foreach($resultado as $res){
		if($dominio_antes && $dominio_depois){
			$html = $res['html'];
			$html = preg_replace('/\/\/'.$dominio_antes.'/i', '//'.$dominio_depois, $html);
			
			banco_update
			(
				"html='".addslashes($html)."'",
				"site_areas_globais",
				"WHERE id_site_areas_globais='".$res['id_site_areas_globais']."'"
			);
		}
		
		if($https){
			$html = $res['html'];
			$html = preg_replace("/http:/i", "https:", $html);
			
			banco_update
			(
				"html='".addslashes($html)."'",
				"site_areas_globais",
				"WHERE id_site_areas_globais='".$res['id_site_areas_globais']."'"
			);
		}
		
		publisher_area_global(Array(
			'id' => $res['id'],
		));
	}
}

function publisher_area_global($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	publisher_acesso_provisorio_criar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($id){
		$campo_tabela = "site_areas_globais";
		$campo_tabela_extra = "WHERE id='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
		
		$campo_nome = "publicar_id_usuario"; $campo_valor = $usuario['id_usuario']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "publicar_status"; $campo_valor = 'P'; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
		$editar = false;$editar_sql = false;
	}
	
	publisher_inform_daemon(Array(
		'options' => Array('area_global')
	));
}

function publisher_update_client($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	publisher_inform_daemon(Array(
		'options' => Array('update_client'),
		'data' => Array(
			'url' => $url,
			'pub_id' => $pub_id,
			'id_usuario' => $id_usuario,
			'time' => time(),
		),
	));
}

function publisher_block_time($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$atual_time = time();
	
	$publish_block_time = $atual_time + $block_time;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$campo_tabela = "host";
	$campo_tabela_extra = "WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."' AND atual IS TRUE";
	
	$campo_nome = "publish_block_time"; $campo_valor = $publish_block_time; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
}

function publisher_block_time_verify($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$atual_time = time();
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'publish_block_time',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."' AND atual IS TRUE"
	);
	
	$publish_block_time = (int)$resultado[0]['publish_block_time'];
	
	if($publish_block_time > 0){
		if($publish_block_time > $atual_time){
			return $publish_block_time - $atual_time;
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}

?>