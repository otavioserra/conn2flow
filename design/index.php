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

$_VERSAO_MODULO				=	$_VERSAO;
$_LOCAL_ID					=	"site_builder";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_JQUERY_UI_CUSTOM			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_PUBLISHER			=	true;
$_INCLUDE_SITE				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	"../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

if(!$_SESSION[$_SYSTEM['ID']."tinymce-version"]){
	$_SESSION[$_SYSTEM['ID']."tinymce-version"] = time();
}

$_HTML['titulo'] 			= 	$_HTML['titulo']."Design.";

$_HTML['js'] .= 
$_JS['swfUpload'].
$_JS['jpicker'].
$_JS['tinyMce'].
//$_JS['prettyPhoto'].
"	<link href=\"prettyPhoto/css/prettyPhoto.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"	<link rel=\"stylesheet\" href=\"https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css\">\n".
"	<script src=\"https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js\"></script>\n".
"	<script src=\"https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js\"></script>\n".
"	<script type=\"text/javascript\" src=\"inputmask/jquery.inputmask.js?v=".$_VERSAO_MODULO."\"></script>\n".
"	<script type=\"text/javascript\" src=\"prettyPhoto/js/jquery.prettyPhoto.js?v=".$_VERSAO_MODULO."\"></script>\n".
"	<script type=\"text/javascript\" src=\"jquery.caret/jquery.caret.1.02.js?v=".$_VERSAO_MODULO."\"></script>\n".
"	<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n".
"	<script type=\"text/javascript\" src=\"jplayer/jquery.jplayer.min.js?v=".$_VERSAO_MODULO."\"></script>\n".
"	<link href=\"jquery-file-upload/jquery.fileupload.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"	<script type=\"text/javascript\" src=\"jquery-file-upload/jquery.iframe-transport.js?v=".$_VERSAO_MODULO."\"></script>\n".
"	<script type=\"text/javascript\" src=\"jquery-file-upload/jquery.fileupload.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "	<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "	<link href=\"?css=sim&opcao=tinymce&only-fonts=sim&v=".$_SESSION[$_SYSTEM['ID']."tinymce-version"]."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

$_SYSTEM['USER_NOME_NO_DEFAULT'] = true;

// Funções do Sistema

function redirecionar($local = false,$sem_root = false){
	global $_SYSTEM;
	global $_AJAX_PAGE;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PROJETO;
	global $_REDIRECT_PAGE;
	global $_ALERTA;
	
	if($local){
		$local = ($sem_root?'':'/' . $_SYSTEM['ROOT']) . ($local == '/' ?'':$local);
	} else {
		switch($_SESSION[$_SYSTEM['ID']."permissao_id"]){
			//case '2': $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = $_CAMINHO_RELATIVO_RAIZ.$_HTML['ADMIN']; break;
			default: $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $_HTML['ADMIN'];
		}
		
		if($_PROJETO['redirecionar']){
			$permissao_id = $_SESSION[$_SYSTEM['ID']."permissao_id"];
			
			if($_PROJETO['redirecionar']['permissao_id']){
				$dados = $_PROJETO['redirecionar']['permissao_id'];
				foreach($dados as $dado){
					if($dado['id'] == $permissao_id) $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $dado['local'];
				}
			}	
		}
		
		$local = $_SESSION[$_SYSTEM['ID']."redirecionar_local"];
	}
	
	if($_ALERTA)$_SESSION[$_SYSTEM['ID']."alerta"] = $_ALERTA;
	header("Location: ".$local);
	exit(0);
	
}

function site_options_start(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_B2MAKE_URL_SEM_SLASH;
	global $_B2MAKE_TESTES_PATH;
	global $_PROJETO;
	global $_VERSAO_MODULO;
	global $_B2MAKE_SCRIPTS_FORCE_RELOAD;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_SESSION[$_SYSTEM['ID']."upload_permissao"]){
		$session_id = md5(session_id());
		
		$_SESSION[$_SYSTEM['ID']."upload_permissao"] = Array(
			'usuario' => $usuario['usuario'],
			'session_id' => $session_id,
		);
		
		banco_delete
		(
			"upload_permissao",
			"WHERE data <= (NOW() - INTERVAL 1 DAY)"
		);
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_upload_permissao',
			))
			,
			"upload_permissao",
			"WHERE session_id='".$session_id."'"
			." AND usuario='".$usuario['usuario']."'"
		);
		
		if(!$resultado){
			banco_insert
			(
				"'" . $usuario['usuario'] . "',".
				"'" . $session_id . "',".
				"NOW()",
				"upload_permissao"
			);
		}
	}
	
	if(!$_SESSION[$_SYSTEM['ID']."host_design_flag"]){
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'installed',
				'cache_version',
				'mobile',
				'google_fonts',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);

		$_SESSION[$_SYSTEM['ID']."host_installed"] = $resultado2[0]['installed'];
		$_SESSION[$_SYSTEM['ID']."host_mobile"] = $resultado2[0]['mobile'];
		$_SESSION[$_SYSTEM['ID']."cache_version"] = $resultado2[0]['cache_version'];
		$_SESSION[$_SYSTEM['ID']."google_fonts_installed"] = $resultado2[0]['google_fonts'];
		$_SESSION[$_SYSTEM['ID']."multi_screen_device"] = 'desktop';
		$_SESSION[$_SYSTEM['ID']."host_design_flag"] = true;
	}
	
	if(!$_SESSION[$_SYSTEM['ID']."site"]){
		$resultado3 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'pagina_favicon',
				'pagina_favicon_version',
				'pagina_parallax',
				'pagina_menu_bolinhas',
				'pagina_menu_bolinhas_areas',
				'pagina_menu_bolinhas_layout',
				'instagram_token',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		if(!$resultado3[0]['pagina_favicon']){
			$resultado4 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'pagina_favicon',
					'pagina_favicon_version',
				))
				,
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND id_site_pai IS NULL"
			);
			
			if($resultado4[0]['pagina_favicon']){
				$resultado3[0]['pagina_favicon'] = $resultado4[0]['pagina_favicon'];
				$resultado3[0]['pagina_favicon_version'] = $resultado4[0]['pagina_favicon_version'];
			}
		}
		
		$resultado4 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'id',
				'mobile',
			))
			,
			"site_areas_globais",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado4){
			foreach($resultado4 as $res){
				$areas_globais[] = Array(
					'nome' => $res['nome'],
					'id' => $res['id'],
					'mobile' => ($res['mobile'] ? true : false),
				);
			}
		}
		
		$_SESSION[$_SYSTEM['ID']."site"] = Array(
			'pagina_favicon' => $resultado3[0]['pagina_favicon'],
			'pagina_favicon_version' => $resultado3[0]['pagina_favicon_version'],
			'pagina_parallax' => $resultado3[0]['pagina_parallax'],
			'pagina_menu_bolinhas' => $resultado3[0]['pagina_menu_bolinhas'],
			'pagina_menu_bolinhas_areas' => $resultado3[0]['pagina_menu_bolinhas_areas'],
			'pagina_menu_bolinhas_layout' => $resultado3[0]['pagina_menu_bolinhas_layout'],
			'instagram_token' => $resultado3[0]['instagram_token'],
			'areas_globais' => $areas_globais,
		);
	}
	
	$upload_permissao = $_SESSION[$_SYSTEM['ID']."upload_permissao"];
	$site = $_SESSION[$_SYSTEM['ID']."site"];
	$host_mobile = $_SESSION[$_SYSTEM['ID']."host_mobile"];
	$host_installed = $_SESSION[$_SYSTEM['ID']."host_installed"];
	$cache_version = $_SESSION[$_SYSTEM['ID']."cache_version"];
	$google_fonts_installed = $_SESSION[$_SYSTEM['ID']."google_fonts_installed"];
	if($host_mobile){$_VARIAVEIS_JS['host_mobile'] = 'sim';} else {$_SESSION[$_SYSTEM['ID']."multi_screen_device"] = 'desktop';}
	$multi_screen_device = $_SESSION[$_SYSTEM['ID']."multi_screen_device"];
	
	if($_SESSION[$_SYSTEM['ID']."permissao_id"] == $_PROJETO['b2make_permissao_id_modelo_site']) $_VARIAVEIS_JS['modelo_site'] = '1';
	if($_SESSION[$_SYSTEM['ID']."multi_screen_widgets_verify"]){$_SESSION[$_SYSTEM['ID']."multi_screen_widgets_verify"] = false; $multi_screen_widgets_verify = true;}
	
	$_VARIAVEIS_JS['b2make_gpk'] = $_PROJETO['GOOGLE_API_KEY'];
	$_VARIAVEIS_JS['multi_screen_device'] = $multi_screen_device;
	$_VARIAVEIS_JS['cache_version'] = $cache_version;
	$_VARIAVEIS_JS['google_fonts_installed'] = $google_fonts_installed;
	$_VARIAVEIS_JS['library_user'] = $upload_permissao['usuario'];
	$_VARIAVEIS_JS['library_id'] = $upload_permissao['session_id'];
	$_VARIAVEIS_JS['pub_id'] = $usuario['pub_id'];
	$_VARIAVEIS_JS['pagina_favicon'] = $site['pagina_favicon'];
	$_VARIAVEIS_JS['pagina_favicon_version'] = $site['pagina_favicon_version'];
	$_VARIAVEIS_JS['pagina_parallax'] = $site['pagina_parallax'];
	$_VARIAVEIS_JS['pagina_menu_bolinhas'] = $site['pagina_menu_bolinhas'];
	$_VARIAVEIS_JS['pagina_menu_bolinhas_areas'] = $site['pagina_menu_bolinhas_areas'];
	$_VARIAVEIS_JS['pagina_menu_bolinhas_layout'] = $site['pagina_menu_bolinhas_layout'];
	$_VARIAVEIS_JS['instagram_token'] = $site['instagram_token'];
	$_VARIAVEIS_JS['areas_globais'] = $site['areas_globais'];
	$_VARIAVEIS_JS['b2make_version'] = $_VERSAO_MODULO;
	$_VARIAVEIS_JS['b2make_local'] = 'design';
	$_VARIAVEIS_JS['b2make_site_print'] = print_r($_SYSTEM['SITE'],true);
	if($_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"]){
		$_VARIAVEIS_JS['reset_cache'] = 'sim';
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = false;
	}
	
	if($_SESSION[$_SYSTEM['ID']."areas_globais_html"]){
		$areas_globais_html = $_SESSION[$_SYSTEM['ID']."areas_globais_html"];
		
		foreach($areas_globais_html as $key => $val){
			$_VARIAVEIS_JS['areas_globais_html'][] = Array(
				'id' => $key,
				'versao' => $val['versao'],
			);
		}
	}
	
	if($_REQUEST['widget_id'])$_VARIAVEIS_JS['widget_id'] = $_REQUEST['widget_id'];
	if($_REQUEST['ler_scripts_force_reload'] || $_B2MAKE_SCRIPTS_FORCE_RELOAD)$_VARIAVEIS_JS['ler_scripts_force_reload'] = 'sim';
	if($multi_screen_widgets_verify)$_VARIAVEIS_JS['multi_screen_widgets_verify'] = 'sim';
	
	if($host_installed)$_VARIAVEIS_JS['host_installed'] = 'sim';
	if($usuario['avatar'])$_VARIAVEIS_JS['avatar'] = '/' . $_SYSTEM['ROOT'] .$_B2MAKE_TESTES_PATH . $usuario['avatar'];
}

function site_layout_make(){
	global $_B2MAKE;
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	
	if($_B2MAKE['site'])
	if($_B2MAKE['site']['template']){
		$layout_site = $_B2MAKE['site']['template'];
		$template = true;
	}
	
	if($_SESSION[$_SYSTEM['ID']."b2make-change-template"]){
		$_SESSION[$_SYSTEM['ID']."b2make-change-template"] = false;
		
		$id_site_templates = $_SESSION[$_SYSTEM['ID']."b2make-templates"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'id_site_segmentos',
			))
			,
			"site_templates",
			"WHERE id_site_templates='".$id_site_templates."'"
		);
		
		$id_site_segmentos = $resultado[0]['id_site_segmentos'];
		$layout_site = $resultado[0]['html'];
		$template = true;
		
		$_SESSION[$_SYSTEM['ID']."b2make-segmentos"] = $id_site_segmentos;
	}
	
	if(!$template){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'html_mobile',
				'html_mobile_saved',
				'id_site',
				'id_site_templates',
				'id',
				'pagina_mestre',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		if($_SESSION[$_SYSTEM['ID']."multi_screen_device"] == 'phone'){
			$layout_site = $resultado[0]['html_mobile'];
		} else {
			$layout_site = $resultado[0]['html'];
		}
		
		if(!$resultado[0]['html_mobile_saved']){
			$_VARIAVEIS_JS['multi_screen_widgets_verify'] = 'sim';
		}
		
		if($resultado[0]['pagina_mestre']){
			$_VARIAVEIS_JS['pagina_mestre'] = 'sim';
		}
		
		$_SESSION[$_SYSTEM['ID']."b2make-templates"] = $resultado[0]['id_site_templates'];
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-id"] = $resultado[0]['id_site'];
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-identificador"] = $resultado[0]['id'];
		
		if(!$_SESSION[$_SYSTEM['ID']."b2make-segmentos"]){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_segmentos',
				))
				,
				"site_templates",
				"WHERE id_site_templates='".$resultado[0]['id_site_templates']."'"
			);
		
			$_SESSION[$_SYSTEM['ID']."b2make-segmentos"] = $resultado2[0]['id_site_segmentos'];
		}
	}
	
	$_B2MAKE['site_templates'] = $_SESSION[$_SYSTEM['ID']."b2make-templates"];
	
	
	if(!$_B2MAKE['site_templates']) return site_templates_select();
	
	return $layout_site;
}

function site_templates_select(){
	global $_B2MAKE;
	
	redirecionar('management/templates');
}

function site_host(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_B2MAKE_PATH;
	
	if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].$_B2MAKE_PATH.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- site-host < -->','<!-- site-host > -->');
	
	$_VARIAVEIS_JS['define_host'] = true;
	
	return $pagina;
}

function site_layout(){
	global $_B2MAKE;
	global $_SYSTEM;
	
	$sites = paginas_mais_resultados(Array());
	
	site_options_start();
	
	return site_layout_make();
}

// ======================================================================================

function template_identificador_unico($id,$num,$id_site_templates,$id_auxiliar){
	$conteudo = banco_select
	(
		"id_site_templates"
		,
		"site_templates",
		"WHERE nome='".($num ? $id.'-'.$num : $id)."'"
		.($id_site_templates?" AND id_site_templates!='".$id_site_templates."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return template_identificador_unico($id,$num + 1,$id_site_templates,$id_auxiliar);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function template_criar_identificador($id,$id_site_templates = false,$id_auxiliar = false){
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
		
		return template_identificador_unico($id,$num,$id_site_templates,$id_auxiliar);
	} else {
		return template_identificador_unico($id,0,$id_site_templates,$id_auxiliar);
	}
}

function segmento_identificador_unico($id,$num,$id_site_segmentos,$id_auxiliar){
	$conteudo = banco_select
	(
		"id_site_segmentos"
		,
		"site_segmentos",
		"WHERE nome='".($num ? $id.'-'.$num : $id)."'"
		.($id_site_segmentos?" AND id_site_segmentos!='".$id_site_segmentos."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return segmento_identificador_unico($id,$num + 1,$id_site_segmentos,$id_auxiliar);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function segmento_criar_identificador($id,$id_site_segmentos = false,$id_auxiliar = false){
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
		
		return segmento_identificador_unico($id,$num,$id_site_segmentos,$id_auxiliar);
	} else {
		return segmento_identificador_unico($id,0,$id_site_segmentos,$id_auxiliar);
	}
}

function identificador_unico($id,$num,$id_site_galeria,$id_auxiliar){
	$conteudo = banco_select
	(
		"id_site_galeria"
		,
		"site_galeria",
		"WHERE nome='".($num ? $id.'-'.$num : $id)."'"
		.($id_site_galeria?" AND id_site_galeria!='".$id_site_galeria."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1,$id_site_galeria,$id_auxiliar);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador($id,$id_site_galeria = false,$id_auxiliar = false){
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
		
		return identificador_unico($id,$num,$id_site_galeria,$id_auxiliar);
	} else {
		return identificador_unico($id,0,$id_site_galeria,$id_auxiliar);
	}
}

function paginas_mais_resultados($params){
	global $_SYSTEM;
	global $_SITES_MAIS_PAGINAS;
	global $_VARIAVEIS_JS;
	
	$site_por_pagina = 20;
	$pagina = 1;
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"]){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
				'id_site_pai',
				'id',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		if($resultado[0]['id_site_pai']){
			$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"] = $resultado[0]['id_site_pai'];
		} else {
			$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"] = $resultado[0]['id_site'];
		}
		
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-id"] = $resultado[0]['id_site'];
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-identificador"] = $resultado[0]['id'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND id_site_pai IS NULL"
		);
		
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-id"] = $resultado[0]['id_site'];
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-nome"] = 'Página Inicial';
	}
	
	$id_site_pai = $_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"];
	
	if($_REQUEST['pagina']){
		$pagina = (int)$_REQUEST['pagina'];
	}
	
	$extra_sites = "WHERE id_site_pai='".$id_site_pai."'";
	
	$total_modelos = banco_total_rows(
		"site",
		$extra_sites
	);
	
	$total_paginas = ceil(($total_modelos + 1) / $site_por_pagina);
	
	if($total_paginas > $pagina){
		if($params['ajax'])
			$_SITES_MAIS_PAGINAS = true;
		else
			$_VARIAVEIS_JS['paginas_mais_resultados'] = 'sim';
	}
	
	$sites = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'nome',
		))
		,
		"site",
		$extra_sites
		.($pagina == 1 ? " LIMIT 0,".($site_por_pagina) : " LIMIT ".($site_por_pagina * ($pagina - 1)).",".$site_por_pagina)
	);
	
	if($sites)
	foreach($sites as $site){
		$sites_saida[] = Array(
			'nome' => $site['nome'],
			'id_site' => $site['id_site'],
		);
	}
	
	if(!$params['ajax']){
		$id_site_atual = $_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-id"];
		$identificador = $_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-identificador"];
		$id_raiz = $_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-id"];
		$nome_raiz = $_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-nome"];
		$nome_pai = $_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-nome"];
		
		$_VARIAVEIS_JS['menu_paginas'] = Array(
			'sites' => $sites_saida,
			'id_raiz' => $id_raiz,
			'nome_raiz' => $nome_raiz,
			'id_site_pai' => $id_site_pai,
			'nome_pai' => $nome_pai,
			'id_site_atual' => $id_site_atual,
			'identificador_atual' => $identificador,
			'total_paginas' => $total_paginas,
		);
		
		if($_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"] == $_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-id"]){
			$_VARIAVEIS_JS['menu_paginas']['raiz'] = 'sim';
		}
		
		return true;
	} else {
		return $sites_saida;
	}
}

function webfonts_processar_arquivo(){
	$fonts_json = file_get_contents('webfonts/webfonts-original.js');
	
	$fonts_arr = json_decode($fonts_json);
	
	$fonts_desativadas = Array(
		'Angkor',
		'Battambang',
		'Bayon',
		'Buda',
		'Coda Caption',
		'Content',
		'Dangrek',
		'Fasthand',
		'Freehand',
		'GFS Didot',
		'GFS Neohellenic',
		'Hanuman',
		'Kantumruy',
		'Kdam Thmor',
		'Khmer',
		'Koulen',
		'Metal',
		'Molle',
		'Moul',
		'Moulpali',
		'Nokora',
		'Odor Mean Chey',
		'Open Sans',
		'Preahvihear',
		'Merriweather',
		'Quattrocento',
		'Roboto',
		'Roboto Condensed',
		'Siemreap',
		'Suwannaphum',
		'Taprom',
		'Ubuntu',
		'UnifrakturCook',
	);
	
	foreach($fonts_arr->items as $item){
		$found = false;
		
		foreach($fonts_desativadas as $font_desativada){
			if($item->family == $font_desativada){
				$found = true;
			}
		}
		
		if(!$found){
			$fonts_processadas[] = Array(
				'family' => $item->family,
				'variants' => $item->variants,
			);
		}
	}
	
	echo json_encode($fonts_processadas);exit;
}

function site_formularios_identificador_unico($id,$num,$id_site_formularios,$id_site_formularios_campos){
	$conteudo = banco_select
	(
		"id_site_formularios_campos"
		,
		"site_formularios_campos",
		"WHERE campo='".($num ? $id.'-'.$num : $id)."'"
		.($id_site_formularios?" AND id_site_formularios='".$id_site_formularios."'":"")
		.($id_site_formularios_campos?" AND id_site_formularios_campos!='".$id_site_formularios_campos."'":"")
	);
	
	if($conteudo){
		return site_formularios_identificador_unico($id,$num + 1,$id_site_formularios,$id_site_formularios_campos);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function site_formularios_criar_identificador($id,$id_site_formularios = false,$id_site_formularios_campos = false){
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
		
		return site_formularios_identificador_unico($id,$num,$id_site_formularios,$id_site_formularios_campos);
	} else {
		return site_formularios_identificador_unico($id,0,$id_site_formularios,$id_site_formularios_campos);
	}
}

function atualizar_cache(){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_host',
			'cache_version',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
	);
	
	if(!$resultado[0]['cache_version']){
		banco_update
		(
			"cache_version='1'",
			"host",
			"WHERE id_host='".$resultado[0]['id_host']."'"
		);
	} else {
		banco_update
		(
			"cache_version='".((int)$resultado[0]['cache_version']+1)."'",
			"host",
			"WHERE id_host='".$resultado[0]['id_host']."'"
		);
	}
}

function configurar_loja(){
	global $_SYSTEM;
	global $_LOJA_REQUIRE;
	
	$_LOJA_REQUIRE = true;
	require_once($_SYSTEM['PATH'].'store/loja.php');
}

function configurar_conteudos(){
	global $_SYSTEM;
	global $_LOJA_REQUIRE;
	
	$_LOJA_REQUIRE = true;
	require_once($_SYSTEM['PATH'].'content/content.php');
}

function site_ftp_manual_password(){
	global $_SYSTEM;
	
	$saida .= '<div style="margin:20px 0px 0px 50px;font-size:20px;">';
	$saida .= 'FTP Manual Password<br><br>';
	$saida .= 'HOST: '.$_SYSTEM['SITE']['ftp-site-host'].'<br>';
	$saida .= 'USER: '.$_SYSTEM['SITE']['ftp-site-user'].'<br>';
	$saida .= 'PASS: '.$_SYSTEM['SITE']['ftp-site-pass'];
	$saida .= '</div>';
	
	return $saida;
}

// ======================================= Ajax Chamadas ===============================================

function ajax_widget_iframe_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$widget = $_REQUEST["widget"];
	$id = $_REQUEST["id"];
	
	if(!$_SESSION[$_SYSTEM['ID'].'site-widgets']){
		$_SESSION[$_SYSTEM['ID'].'site-widgets'] = Array();
	}
	
	$_SESSION[$_SYSTEM['ID'].'site-widgets'][$id] = $widget;
	
	return $saida;
}

function ajax_widget_iframe_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$id = $_REQUEST["id"];
	$widget = $_REQUEST["widget"];
	
	if($_SESSION[$_SYSTEM['ID'].'site-widgets'][$id]){
		$_SESSION[$_SYSTEM['ID'].'site-widgets'][$id] = $widget;
	}
	
	return $saida;
}

function ajax_widget_iframe(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$id = $_REQUEST["id"];
	
	if($_SESSION[$_SYSTEM['ID'].'site-widgets'][$id]){
		$saida =  
"<html>
<body>
".stripslashes($_SESSION[$_SYSTEM['ID'].'site-widgets'][$id])."
</body>
</html>";
	}
	
	$_AJAX_OUT_VARS['not-json-encode'] = true;
	
	return $saida;
}

function ajax_save(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$html = $_REQUEST['html'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site = $_REQUEST['id'];
	$google_fontes = $_REQUEST['google_fontes'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site='".$id_site."'"
	);
	
	if($resultado){
		if($_SESSION[$_SYSTEM['ID']."multi_screen_device"] == 'phone'){
			banco_update
			(
				"html_mobile_saved=1,".
				"google_fontes_mobile='".$google_fontes."',".
				"data_modificacao_mobile=NOW(),".
				"html_mobile='".addslashes($html)."'",
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND id_site='".$id_site."'"
			);
		} else {
			banco_update
			(
				"google_fontes='".$google_fontes."',".
				"data_modificacao=NOW(),".
				"html='".addslashes($html)."'",
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND id_site='".$id_site."'"
			);
		}
		
		if($_REQUEST['area_global_change'] == 's'){
			$areas_globais = json_decode(stripslashes($_REQUEST['area_global']));
			
			if($areas_globais)
			foreach($areas_globais as $ag){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'versao',
					))
					,
					"site_areas_globais",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id='". $ag->area_global_id ."'"
				);
				
				if(!$resultado[0]['versao']){
					$resultado[0]['versao'] = '1';
				} else {
					(int)$resultado[0]['versao']++;
				}
				
				banco_update
				(
					"versao='".$resultado[0]['versao']."',".
					"html='". $ag->area_global_html ."'",
					"site_areas_globais",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id='". $ag->area_global_id ."'"
				);
				
				$_SESSION[$_SYSTEM['ID']."areas_globais_html"][$ag->area_global_id] = Array(
					'versao' => $resultado[0]['versao'],
					'html' => $ag->area_global_html,
				);
				
				publisher_area_global(Array(
					'id' => $ag->area_global_id,
				));
				
				$_SESSION[$_SYSTEM['ID']."areas_globais_changed"] = true;
			}
		}
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'SemPermissao',
		);
	}

	return $saida;
}

function ajax_segmentos(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$segmentos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_segmentos',
			'nome',
		))
		,
		"site_segmentos",
		"WHERE status='A'"
		." AND id != 'padrao'"
		." ORDER BY nome ASC"
	);
	
	if($segmentos){
		$count = 0;
		foreach($segmentos as $seg){
			$segmentos[$count]['nome'] = $seg['nome'];
			$count++;
		}
		
		$saida = Array(
			'segmentos' => $segmentos,
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'NaoExisteSegmentos',
		);
	}
	
	return $saida;
}

function ajax_segmento_dados(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$segmentos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'descricao',
				'imagem',
				'imagem_versao',
			))
			,
			"site_segmentos",
			"WHERE status='A'"
			." AND id_site_segmentos='".$id."'"
		);
		
		if($segmentos){
			$segmento['nome'] = $segmentos[0]['nome'];
			$segmento['descricao'] = $segmentos[0]['descricao'];
			
			if($segmentos[0]['imagem']){
				$segmento['imagem'] = '//'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$segmentos[0]['imagem'];
				$segmento['imagem_versao'] = $segmentos[0]['imagem_versao'];
			}
			
			$saida = Array(
				'segmento' => $segmento,
				'status' => 'Ok',
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

function ajax_segmento_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		banco_update
		(
			"status='D'",
			"site_templates",
			"WHERE id_site_segmentos='".$id."'"
		);
		banco_update
		(
			"status='D'",
			"site_segmentos",
			"WHERE id_site_segmentos='".$id."'"
		);
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_segmento_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$nome = $_REQUEST['nome'];
	$descricao = $_REQUEST['descricao'];
	$image_id = (int)$_REQUEST['image_id'];
	
	$id = segmento_criar_identificador($nome);
	
	$campos = null;

	$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "descricao"; $campo_valor = $descricao; 		if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "imagem_versao"; $campo_valor = '0'; 		$campos[] = Array($campo_nome,$campo_valor);
	
	banco_insert_name
	(
		$campos,
		"site_segmentos"
	);
	
	$id = banco_last_id();
	
	$saida = Array(
		'id' => $id,
		'status' => 'Ok',
	);
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$type = "segmento";
	
	if(is_int($image_id) && $image_id > 0){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_biblioteca_imagens',
				'file',
				'width',
				'height',
			))
			,
			"site_biblioteca_imagens",
			"WHERE status='A'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND id_site_biblioteca_imagens='".$image_id."'"
		);
		
		if($resultado){
			$aux = explode('.',basename($resultado[0]['file']));
			$extensao = strtolower($aux[count($aux)-1]);
			
			$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
			$image_path = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'].$type."s".$_SYSTEM['SEPARADOR'];
			diretorio_criar_senao_existir($image_path);
			
			$image_target = $image_path . $type.$id.'.'.$extensao;
			$image_target_url = "files/".$type."s/".$type.$id.'.'.$extensao;
			
			copy($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $resultado[0]['file'], $tmp_image);
			
			$_RESIZE_IMAGE_Y_ZERO = true;
			resize_image($tmp_image, $image_target, $_SYSTEM['SITE'][$type.'-width'], $_SYSTEM['SITE'][$type.'-height'],false,false,true);

			unlink($tmp_image);
			
			banco_update
			(
				"imagem='".$image_target_url."',".
				"imagem_versao=imagem_versao + 1",
				"site_".$type."s",
				"WHERE id_site_".$type."s='".$id."'"
			);
		} else {
			$saida = Array(
				'status' => 'ImageOutroUser',
			);
		}
	}
	
	return $saida;
}

function ajax_segmento_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$id = $_REQUEST['id'];
	$nome = $_REQUEST['nome'];
	$descricao = $_REQUEST['descricao'];
	$image_id = (int)$_REQUEST['image_id'];
	
	$id_txt = segmento_criar_identificador($nome,$id);
	
	$campo_tabela = "site_segmentos";
	$campo_tabela_extra = "WHERE id_site_segmentos='".$id."'";
	
	$campo_nome = "nome"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "descricao"; $campo_valor = $descricao; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "id"; $campo_valor = $id_txt; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	
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

	$saida = Array(
		'status' => 'Ok',
	);
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$type = "segmento";
	
	if(is_int($image_id) && $image_id > 0){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_biblioteca_imagens',
				'file',
				'width',
				'height',
			))
			,
			"site_biblioteca_imagens",
			"WHERE status='A'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND id_site_biblioteca_imagens='".$image_id."'"
		);
		
		if($resultado){
			$aux = explode('.',basename($resultado[0]['file']));
			$extensao = strtolower($aux[count($aux)-1]);
			
			$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
			$image_path = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'].$type."s".$_SYSTEM['SEPARADOR'];
			diretorio_criar_senao_existir($image_path);
			
			$image_target = $image_path . $type.$id.'.'.$extensao;
			$image_target_url = "files/".$type."s/".$type.$id.'.'.$extensao;
			
			copy($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $resultado[0]['file'], $tmp_image);
			
			$_RESIZE_IMAGE_Y_ZERO = true;
			resize_image($tmp_image, $image_target, $_SYSTEM['SITE'][$type.'-width'], $_SYSTEM['SITE'][$type.'-height'],false,false,true);
			
			unlink($tmp_image);
			
			banco_update
			(
				"imagem='".$image_target_url."',".
				"imagem_versao=imagem_versao + 1",
				"site_".$type."s",
				"WHERE id_site_".$type."s='".$id."'"
			);
		} else {
			$saida = Array(
				'status' => 'ImageOutroUser',
			);
		}
	}
	
	return $saida;
}

function ajax_templates(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$templates = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_templates',
				'nome',
			))
			,
			"site_templates",
			"WHERE id_site_segmentos='".$id."'"
			." AND status='A'"
		);
		
		if($templates){
			$count = 0;
			foreach($templates as $tem){
				$templates[$count]['nome'] = $tem['nome'];
				$count++;
			}
			
			$saida = Array(
				'templates' => $templates,
				'status' => 'Ok',
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

function ajax_template_dados(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$templates = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'descricao',
				'imagem',
				'imagem_versao',
			))
			,
			"site_templates",
			"WHERE status='A'"
			." AND id_site_templates='".$id."'"
		);
		
		if($templates){
			$template['nome'] = $templates[0]['nome'];
			$template['descricao'] = $templates[0]['descricao'];
			
			if($templates[0]['imagem']){
				$template['imagem'] = '//'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$templates[0]['imagem'];
				$template['imagem_versao'] = $templates[0]['imagem_versao'];
			}
			
			$saida = Array(
				'template' => $template,
				'status' => 'Ok',
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

function ajax_template_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		banco_update
		(
			"status='D'",
			"site_templates",
			"WHERE id_site_templates='".$id."'"
		);
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_template_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$id_site_segmentos = $_REQUEST['id_site_segmentos'];
	$nome = $_REQUEST['nome'];
	$descricao = $_REQUEST['descricao'];
	$image_id = (int)$_REQUEST['image_id'];
	
	$id = template_criar_identificador($nome);
	
	$campos = null;

	$campo_nome = "id_site_segmentos"; $campo_valor = $id_site_segmentos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "descricao"; $campo_valor = $descricao; 		if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "imagem_versao"; $campo_valor = '0'; 		$campos[] = Array($campo_nome,$campo_valor);
	
	banco_insert_name
	(
		$campos,
		"site_templates"
	);
	
	$id = banco_last_id();

	$saida = Array(
		'id' => $id,
		'status' => 'Ok',
	);
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$type = "template";
	
	if(is_int($image_id) && $image_id > 0){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_biblioteca_imagens',
				'file',
				'width',
				'height',
			))
			,
			"site_biblioteca_imagens",
			"WHERE status='A'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND id_site_biblioteca_imagens='".$image_id."'"
		);
		
		if($resultado){
			$aux = explode('.',basename($resultado[0]['file']));
			$extensao = strtolower($aux[count($aux)-1]);
			
			$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
			$image_path = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'].$type."s".$_SYSTEM['SEPARADOR'];
			diretorio_criar_senao_existir($image_path);
			
			$image_target = $image_path . $type.$id.'.'.$extensao;
			$image_target_url = "files/".$type."s/".$type.$id.'.'.$extensao;
			
			copy($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $resultado[0]['file'], $tmp_image);
			
			$_RESIZE_IMAGE_Y_ZERO = true;
			resize_image($tmp_image, $image_target, $_SYSTEM['SITE'][$type.'-width'], $_SYSTEM['SITE'][$type.'-height'],false,false,true);
		
			unlink($tmp_image);
			
			banco_update
			(
				"imagem='".$image_target_url."',".
				"imagem_versao=imagem_versao + 1",
				"site_".$type."s",
				"WHERE id_site_".$type."s='".$id."'"
			);
		} else {
			$saida = Array(
				'status' => 'ImageOutroUser',
			);
		}
	}
	
	return $saida;
}

function ajax_template_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$id = $_REQUEST['id'];
	$nome = $_REQUEST['nome'];
	$descricao = $_REQUEST['descricao'];
	$image_id = (int)$_REQUEST['image_id'];
	
	$id_txt = template_criar_identificador($nome,$id);
	
	$campo_tabela = "site_templates";
	$campo_tabela_extra = "WHERE id_site_templates='".$id."'";
	
	$campo_nome = "nome"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "descricao"; $campo_valor = $descricao; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	$campo_nome = "id"; $campo_valor = $id_txt; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
	
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

	$saida = Array(
		'status' => 'Ok',
	);
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$type = "template";
	
	if(is_int($image_id) && $image_id > 0){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_biblioteca_imagens',
				'file',
				'width',
				'height',
			))
			,
			"site_biblioteca_imagens",
			"WHERE status='A'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND id_site_biblioteca_imagens='".$image_id."'"
		);
		
		if($resultado){
			$aux = explode('.',basename($resultado[0]['file']));
			$extensao = strtolower($aux[count($aux)-1]);
			
			$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
			$image_path = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'].$type."s".$_SYSTEM['SEPARADOR'];
			diretorio_criar_senao_existir($image_path);
			
			$image_target = $image_path . $type.$id.'.'.$extensao;
			$image_target_url = "files/".$type."s/".$type.$id.'.'.$extensao;
			
			copy($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $resultado[0]['file'], $tmp_image);
			
			$_RESIZE_IMAGE_Y_ZERO = true;
			resize_image($tmp_image, $image_target, $_SYSTEM['SITE'][$type.'-width'], $_SYSTEM['SITE'][$type.'-height'],false,false,true);

			unlink($tmp_image);
			
			banco_update
			(
				"imagem='".$image_target_url."',".
				"imagem_versao=imagem_versao + 1",
				"site_".$type."s",
				"WHERE id_site_".$type."s='".$id."'"
			);
		} else {
			$saida = Array(
				'status' => 'ImageOutroUser'.$image_id,
			);
		}
	}
	
	return $saida;
}

function ajax_template_save(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$id_site_templates = $_REQUEST['id'];
	$html = $_REQUEST['html'];
	
	banco_update
	(
		"html='".$html."'",
		"site_templates",
		"WHERE id_site_templates='".$id_site_templates."'"
	);
	
	$saida = Array(
		'status' => 'Ok',
	);	
	
	return $saida;
}

function ajax_agenda_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;

	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$campos = null;
	
	$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	
	banco_insert_name
	(
		$campos,
		"site_agenda"
	);
	
	$id_site_agenda = banco_last_id();
	
	$num_total_rows = banco_total_rows
	(
		"site_agenda",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
	);
	
	$agenda_nome = "agenda".$num_total_rows;
	
	banco_update
	(
		"nome='".$agenda_nome."'",
		"site_agenda",
		"WHERE id_site_agenda='".$id_site_agenda."'"
	);
	
	$saida = Array(
		'status' => 'Ok',
		'agenda_nome' => $agenda_nome,
		'agenda_id' => $id_site_agenda,
	);

	
	return $saida;
}

function ajax_agendas(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;

	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_agenda',
			'nome',
		))
		,
		"site_agenda",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY nome ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_agenda' => $res['id_site_agenda'],
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

function ajax_agenda_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_agenda',
			))
			,
			"site_agenda",
			"WHERE id_site_agenda='".$_REQUEST["id"]."'"
		);
		
		if($resultado){
			banco_update
			(
				"status='D'",
				"site_agenda",
				"WHERE id_site_agenda='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_eventos",
				"WHERE id_site_agenda='".$_REQUEST["id"]."'"
			);
			
			$saida = Array(
				'status' => 'Ok'
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

function ajax_agenda_name(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_agenda',
			))
			,
			"site_agenda",
			"WHERE id_site_agenda='".$_REQUEST["id"]."'"
		);
		
		if($resultado){
			banco_update
			(
				"nome='".($_REQUEST["name"]?$_REQUEST["name"]:"agenda".$_REQUEST["id"])."'",
				"site_agenda",
				"WHERE id_site_agenda='".$_REQUEST["id"]."'"
			);
			
			$saida = Array(
				'status' => 'Ok'
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

function ajax_agenda_eventos(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_eventos',
				'data',
				'hora',
				'nome_original',
				'descricao',
				'status',
			))
			,
			"site_eventos",
			"WHERE id_site_agenda='".$id."'"
			." AND status!='D'"
			." ORDER BY data ASC"
		);
		
		if($resultado){
			foreach($resultado as $res){
				$eventos[] = Array(
					'id' => $res['id_site_eventos'],
					'status' => $res['status'],
					'nome_original' => $res['nome_original'],
					'descricao' => $res['descricao'],
					'data' => data_from_date_to_text($res['data']),
					'hora' => hora_from_time_to_text($res['hora']),
				);
			}
			
			$saida = Array(
				'status' => 'Ok',
				'eventos' => $eventos
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

function ajax_eventos_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_eventos',
			))
			,
			"site_eventos",
			"WHERE id_site_eventos='".$id."'"
			." AND status='A'"
		);
		
		if($resultado){
			banco_update
			(
				"status='D'",
				"site_eventos",
				"WHERE id_site_eventos='".$id."'"
			);
			
			$saida = Array(
				'status' => 'Ok',
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

function ajax_eventos_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["agenda"]){
		$id_site_agenda = $_REQUEST["agenda"];
		$nome_original = $_REQUEST["nome_original"];
		$data = data_padrao_date($_REQUEST["data"]);
		$hora = $_REQUEST["hora"];
		$descricao = $_REQUEST["descricao"];
		
		$campos = null;
		
		$name = banco_identificador(Array(
			'id' => $nome_original,
			'tabela' => Array(
				'nome' => 'site_eventos',
				'campo' => 'name',
				'id_nome' => 'id_site_eventos',
				'id_valor' => false,
			),
		));
		
		$campo_nome = "id_site_agenda"; $campo_valor = $id_site_agenda; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "name"; $campo_valor = $name; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome_original"; $campo_valor = $nome_original; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "descricao"; $campo_valor = $descricao; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data"; $campo_valor = $data; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "hora"; $campo_valor = $hora; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site_eventos"
		);
		
		$id = banco_last_id();
		
		$saida = Array(
			'status' => 'Ok',
			'id' => $id,
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_eventos_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id_site_eventos"]){
		$id = $_REQUEST["id_site_eventos"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_eventos',
			))
			,
			"site_eventos",
			"WHERE id_site_eventos='".$id."'"
		);
		
		if($resultado){
			$campo_tabela = "site_eventos";
			$campo_tabela_extra = "WHERE id_site_eventos='".$id."'";
			
			$name = banco_identificador(Array(
				'id' => $_REQUEST['nome_original'],
				'tabela' => Array(
					'nome' => 'site_eventos',
					'campo' => 'name',
					'id_nome' => 'id_site_eventos',
					'id_valor' => $id,
				),
			));
			
			$campo_nome = "nome_original"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
			$campo_nome = "descricao"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
			$campo_nome = "data"; $editar[$campo_tabela][] = $campo_nome."='" . data_padrao_date($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "hora"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
			$campo_nome = "name"; $campo_valor = $name; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			
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

function ajax_eventos_block(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$status = $_REQUEST["status"];
		
		if($status){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_eventos',
				))
				,
				"site_eventos",
				"WHERE id_site_eventos='".$id."'"
			);
			
			if($resultado){
				banco_update
				(
					"status='".$status."'",
					"site_eventos",
					"WHERE id_site_eventos='".$id."'"
				);
				
				$saida = Array(
					'status' => 'Ok',
				);
			} else {
				$saida = Array(
					'status' => 'NaoExisteId:'.$id
				);
			}
		} else {
			$saida = Array(
				'status' => 'StatusNaoInformado'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_eventos_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
	
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_eventos',
			))
			,
			"site_eventos",
			"WHERE id_site_eventos='".$id."'"
		);
		
		if($resultado){
			banco_update
			(
				"status='D'",
				"site_eventos",
				"WHERE id_site_eventos='".$id."'"
			);
			
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'NaoExisteId:'.$id
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_publish_page(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PATH;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_HTML_META;
	global $_B2MAKE_FTP_SITE_HOST;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$html = stripslashes(html_entity_decode($_REQUEST['html']));
	$google_fontes = $_REQUEST['google_fontes'];
	
	if($_SESSION[$_SYSTEM['ID']."areas_globais_changed"]){
		$_SESSION[$_SYSTEM['ID']."areas_globais_changed"] = false;
		$atualizar_site_version = true;
	}
	
	$block_time = publisher_block_time_verify();
	
	$site = site_publish_page(Array(
		'atualizar_site_version' => $atualizar_site_version,
		'html' => $html,
		'google_fontes' => $google_fontes,
		'mobile' => ($_SESSION[$_SYSTEM['ID']."multi_screen_device"] == 'phone' ? true : false),
	));
	
	switch($site['status']){
		case 'Ok':
			$saida = Array(
				'url' => $site['url'],
				'status' => 'Ok',
			);
			
			if($block_time){
				$saida['block_time'] = $block_time;
				
				$saida['link'] = preg_replace("/^https:/i", "http:", $site['url']) . '?force_http=true';
				
				$host = banco_select_name
				(
					banco_campos_virgulas(Array(
						'url',
						'https',
						'user_cpanel',
					))
					,
					"host",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND atual IS TRUE"
				);
				
				$dominio_trocar = 'http'.($host[0]['https'] ? 's':'').'://'.$host[0]['user_cpanel'].'.'.$_B2MAKE_FTP_SITE_HOST.'/';
				
				$saida['link2'] = preg_replace('/'.preg_quote($host[0]['url'],'/').'/i', $dominio_trocar, $site['url']);
			}
		break;
		case 'FtpNotConnected':
			$saida = Array(
				'status' => 'FtpNotConnected'
			);
		break;
		case 'HtmlNull':
			$saida = Array(
				'status' => 'NaoExiste'
			);
		break;
	}
	
	return $saida;
}

function ajax_pagina_vars(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	switch($_REQUEST['variavel']){
		case 'pagina-title': $campo = "pagina_titulo='".$_REQUEST['value']."'"; $site = true; break;
		case 'pagina-description': $campo = "pagina_description='".$_REQUEST['value']."'"; $site = true; break;
		case 'pagina-keywords': $campo = "pagina_keywords='".$_REQUEST['value']."'"; $site = true; break;
		case 'pagina-codigo-head': $campo = "pagina_head_extra='".$_REQUEST['value']."'"; $site = true; break;
		case 'form-contato-email': $campo = "form_contato_email='".$_REQUEST['value']."'"; $site = true; break;
	}
	
	if($campo)
		if($site)
			banco_update
			(
				$campo,
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
	
	$saida = Array(
		'status' => 'Ok',
	);
	
	return $saida;
}

function ajax_input_start_values(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$json_str = json_decode(stripslashes($_REQUEST['str_json']),true);
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($json_str){
		foreach($json_str['variaveis'] as $var){
			switch($var['variavel']){
				case 'pagina-title': $campo = 'pagina_titulo'; $campos .= ($campos?',':'') . $campo; $ids[$campo] = $var['id']; $site = true; break;
				case 'pagina-description': $campo = 'pagina_description'; $campos .= ($campos?',':'') . $campo; $ids[$campo] = $var['id']; $site = true; break;
				case 'pagina-keywords': $campo = 'pagina_keywords'; $campos .= ($campos?',':'') . $campo; $ids[$campo] = $var['id']; $site = true; break;
				case 'pagina-codigo-head': $campo = 'pagina_head_extra'; $campos .= ($campos?',':'') . $campo; $ids[$campo] = $var['id']; $site = true; break;
				case 'form-contato-email': $campo = 'form_contato_email'; $campos .= ($campos?',':'') . $campo; $ids[$campo] = $var['id']; $site = true; break;
			}
		}
		
		if($site){
			$resultado = banco_select_name
			(
				$campos
				,
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			if($resultado)
			foreach($ids as $campo => $id){
				if($campo == 'form_contato_email' && !$resultado[0][$campo]) $resultado[0][$campo] = $usuario['email'];
				$valores[] = Array(
					'id' => $id,
					'val' => $resultado[0][$campo],
				);
			}
		}
		
		$saida = Array(
			'valores' => $valores,
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'JSON ERROR',
		);
	}
	
	return $saida;
}

function ajax_biblioteca_imagens_lista(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_biblioteca_imagens',
			'file',
			'width',
			'height',
		))
		,
		"site_biblioteca_imagens",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_biblioteca_imagens DESC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			if($res['file']){
				if(!$res['width'] || !$res['height']){
					$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $res['file']);
					
					banco_update
					(
						"width='".$imgInfo[0]."',".
						"height='".$imgInfo[1]."'",
						"site_biblioteca_imagens",
						"WHERE id_site_biblioteca_imagens='".$res['id_site_biblioteca_imagens']."'"
					);
				} else {
					$imgInfo[0] = $res['width'];
					$imgInfo[1] = $res['height'];
				}
				
				$images[] = Array(
					'id' => $res['id_site_biblioteca_imagens'],
					'imagem' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $res['file'],
					'mini' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/mini/' . $res['file'],
					'width' => $imgInfo[0],
					'height' => $imgInfo[1],
					'file' => $res['file'],
				);
			}
		}
		
		$saida = Array(
			'status' => 'Ok',
			'images' => $images
		);
	} else {
		$saida = Array(
			'status' => 'NaoHaImagens'
		);
	}
	
	return $saida;
}

function ajax_biblioteca_imagens_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_biblioteca_imagens',
				'file',
			))
			,
			"site_biblioteca_imagens",
			"WHERE id_site_biblioteca_imagens='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND status='A'"
		);
		
		if($resultado){
			banco_update
			(
				"status='D'",
				"site_biblioteca_imagens",
				"WHERE id_site_biblioteca_imagens='".$id."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			$file = $resultado[0]['file'];
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-imagens-path']);
				
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
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_arquivos_lista(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_arquivos',
			'file',
		))
		,
		"site_arquivos",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_arquivos DESC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			if($res['file']){
				$arquivos[] = Array(
					'id' => $res['id_site_arquivos'],
					'arquivo' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-arquivos-path'] . '/' . $res['file'],
					'file' => $res['file'],
				);
			}
		}
		
		$saida = Array(
			'status' => 'Ok',
			'arquivos' => $arquivos
		);
	} else {
		$saida = Array(
			'status' => 'NaoHaImagens'
		);
	}
	
	return $saida;
}

function ajax_arquivos_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_arquivos',
				'file',
			))
			,
			"site_arquivos",
			"WHERE id_site_arquivos='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND status='A'"
		);
		
		if($resultado){
			banco_update
			(
				"status='D'",
				"site_arquivos",
				"WHERE id_site_arquivos='".$id."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			$file = $resultado[0]['file'];
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-arquivos-path']);
				
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
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_album_fotos_add(){
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
			"site_album_fotos"
		);
		
		$id_site_album_fotos = banco_last_id();
		
		$num_total_rows = banco_total_rows
		(
			"site_album_fotos",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$album_fotos_nome = "albumfotos".$num_total_rows;
		
		banco_update
		(
			"nome='".$nome."',".
			"path='".$album_fotos_nome."'",
			"site_album_fotos",
			"WHERE id_site_album_fotos='".$id_site_album_fotos."'"
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-files-path'].'/'.$_SYSTEM['SITE']['ftp-files-albumfotos-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-albumfotos-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-albumfotos-path']);
			
			ftp_mkdir($_CONEXAO_FTP, $album_fotos_nome);
			
			ftp_fechar_conexao();
			
			$saida = Array(
				'status' => 'Ok',
				'album_fotos_nome' => $nome,
				'album_fotos_id' => $id_site_album_fotos,
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

function ajax_albuns_fotos(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_album_fotos',
			'nome',
			'legenda',
		))
		,
		"site_album_fotos",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_album_fotos ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_album_fotos' => $res['id_site_album_fotos'],
				'legenda' => $res['legenda'],
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

function ajax_albuns_fotos_images(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_fotos_imagens',
				'file',
				'width',
				'height',
				'descricao',
			))
			,
			"site_album_fotos_imagens",
			"WHERE id_site_album_fotos='".$id."'"
			." AND status='A'"
			." ORDER BY id_site_album_fotos_imagens ASC"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_album_fotos",
				"WHERE id_site_album_fotos='".$id."'"
			);
			
			$albumfotos_path = $resultado2[0]['path'];
			
			foreach($resultado as $res){
				if($res['file']){
					if(!$res['width'] || !$res['height']){
						$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-albumfotos-path'] . '/' . $albumfotos_path . '/' . $res['file']);
						
						banco_update
						(
							"width='".$imgInfo[0]."',".
							"height='".$imgInfo[1]."'",
							"site_album_fotos_imagens",
							"WHERE id_site_album_fotos_imagens='".$res['id_site_album_fotos_imagens']."'"
						);
					} else {
						$imgInfo[0] = $res['width'];
						$imgInfo[1] = $res['height'];
					}
					
					$images[] = Array(
						'file' => $res['file'],
						'descricao' => $res['descricao'],
						'id' => $res['id_site_album_fotos_imagens'],
						'imagem' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-albumfotos-path'] . '/' . $albumfotos_path . '/' . $res['file'],
						'mini' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-albumfotos-path'] . '/' . $albumfotos_path . '/mini/' . $res['file'],
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

function ajax_albuns_fotos_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_fotos',
			))
			,
			"site_album_fotos",
			"WHERE id_site_album_fotos='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_album_fotos";
			$campo_tabela_extra = "WHERE id_site_album_fotos='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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

function ajax_albuns_fotos_legenda_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_fotos',
			))
			,
			"site_album_fotos",
			"WHERE id_site_album_fotos='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_album_fotos";
			$campo_tabela_extra = "WHERE id_site_album_fotos='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
			$legenda = $_REQUEST['legenda'];
			
			$campo_nome = "legenda"; $editar[$campo_tabela][] = $campo_nome."='" . $legenda . "'";
			
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
				'legenda' => $legenda,
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

function ajax_albuns_fotos_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_fotos',
				'path',
			))
			,
			"site_album_fotos",
			"WHERE id_site_album_fotos='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"site_album_fotos_imagens",
				"WHERE id_site_album_fotos='".$_REQUEST["id"]."'"
				." AND status='A'"
			);
			
			banco_update
			(
				"status='D'",
				"site_album_fotos_imagens",
				"WHERE id_site_album_fotos='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_album_fotos",
				"WHERE id_site_album_fotos='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				$album_fotos_path = $resultado[0]['path'];
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-albumfotos-path']);
				ftp_chdir($_CONEXAO_FTP,$album_fotos_path);
				
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
				} else {
					ftp_chdir($_CONEXAO_FTP,'mini');
				}
				
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,'mini');
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,$album_fotos_path);
				
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

function ajax_albuns_fotos_images_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$album = $_REQUEST["album"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_fotos',
				'path',
			))
			,
			"site_album_fotos",
			"WHERE id_site_album_fotos='".$album."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_album_fotos_imagens',
					'file',
				))
				,
				"site_album_fotos_imagens",
				"WHERE id_site_album_fotos_imagens='".$id."'"
				." AND status='A'"
			);
			
			if($resultado2){
				banco_update
				(
					"status='D'",
					"site_album_fotos_imagens",
					"WHERE id_site_album_fotos_imagens='".$id."'"
				);
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					$album_fotos_path = $resultado[0]['path'];
					$file = $resultado2[0]['file'];
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-albumfotos-path']);
					ftp_chdir($_CONEXAO_FTP,$album_fotos_path);
					
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

function ajax_albuns_fotos_data_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_image = $_REQUEST['id_image'];
	$id_album = $_REQUEST['id_album'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_album_fotos',
		))
		,
		"site_album_fotos",
		"WHERE id_site_album_fotos='".$id_album."'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
	);
	
	if($resultado){
		$descricao = $_REQUEST["descricao"];
		
		banco_update
		(
			"descricao='".$descricao."'",
			"site_album_fotos_imagens",
			"WHERE id_site_album_fotos='".$id_album."'"
			." AND id_site_album_fotos_imagens='".$id_image."'"
		);
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'SemPermissao'
		);
	}
	
	return $saida;
}

function ajax_banners_add(){
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
			"site_banners"
		);
		
		$id_site_banners = banco_last_id();
		
		$num_total_rows = banco_total_rows
		(
			"site_banners",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$banners_nome = "banners".$num_total_rows;
		
		banco_update
		(
			"nome='".$nome."',".
			"path='".$banners_nome."'",
			"site_banners",
			"WHERE id_site_banners='".$id_site_banners."'"
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-files-path'].'/'.$_SYSTEM['SITE']['ftp-files-banners-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-banners-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-banners-path']);
			
			ftp_mkdir($_CONEXAO_FTP, $banners_nome);
			
			ftp_fechar_conexao();
			
			$saida = Array(
				'status' => 'Ok',
				'banners_nome' => $nome,
				'banners_id' => $id_site_banners,
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

function ajax_banners(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_banners',
			'nome',
		))
		,
		"site_banners",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_banners ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_banners' => $res['id_site_banners'],
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

function ajax_banners_images(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners_imagens',
				'file',
				'width',
				'height',
				'titulo',
				'sub_titulo',
				'url',
			))
			,
			"site_banners_imagens",
			"WHERE id_site_banners='".$id."'"
			." AND status='A'"
			." ORDER BY ordem ASC,id_site_banners_imagens ASC"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_banners",
				"WHERE id_site_banners='".$id."'"
			);
			
			$banners_path = $resultado2[0]['path'];
			
			foreach($resultado as $res){
				if($res['file']){
					if(!$res['width'] || !$res['height']){
						$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-banners-path'] . '/' . $banners_path . '/' . $res['file']);
						
						banco_update
						(
							"width='".$imgInfo[0]."',".
							"height='".$imgInfo[1]."'",
							"site_banners_imagens",
							"WHERE id_site_banners_imagens='".$res['id_site_banners_imagens']."'"
						);
					} else {
						$imgInfo[0] = $res['width'];
						$imgInfo[1] = $res['height'];
					}
					
					$images[] = Array(
						'titulo' => $res['titulo'],
						'sub_titulo' => $res['sub_titulo'],
						'url' => $res['url'],
						'file' => $res['file'],
						'id' => $res['id_site_banners_imagens'],
						'imagem' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-banners-path'] . '/' . $banners_path . '/' . $res['file'],
						'mini' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-banners-path'] . '/' . $banners_path . '/mini/' . $res['file'],
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

function ajax_banners_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners',
			))
			,
			"site_banners",
			"WHERE id_site_banners='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_banners";
			$campo_tabela_extra = "WHERE id_site_banners='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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

function ajax_banners_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners',
				'path',
			))
			,
			"site_banners",
			"WHERE id_site_banners='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"site_banners_imagens",
				"WHERE id_site_banners='".$_REQUEST["id"]."'"
				." AND status='A'"
			);
			
			banco_update
			(
				"status='D'",
				"site_banners_imagens",
				"WHERE id_site_banners='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_banners",
				"WHERE id_site_banners='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				$banners_path = $resultado[0]['path'];
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-banners-path']);
				ftp_chdir($_CONEXAO_FTP,$banners_path);
				
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
				} else {
					ftp_chdir($_CONEXAO_FTP,'mini');
				}
				
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,'mini');
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,$banners_path);
				
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

function ajax_banners_images_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$banners = $_REQUEST["banners"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners',
				'path',
			))
			,
			"site_banners",
			"WHERE id_site_banners='".$banners."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_banners_imagens',
					'file',
				))
				,
				"site_banners_imagens",
				"WHERE id_site_banners_imagens='".$id."'"
				." AND status='A'"
			);
			
			if($resultado2){
				banco_update
				(
					"status='D'",
					"site_banners_imagens",
					"WHERE id_site_banners_imagens='".$id."'"
				);
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					$banners_path = $resultado[0]['path'];
					$file = $resultado2[0]['file'];
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-banners-path']);
					ftp_chdir($_CONEXAO_FTP,$banners_path);
					
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

function ajax_banners_order(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["ids"]){
		$ids = $_REQUEST["ids"];
		$ids_arr = explode(';',$ids);
		
		if($ids_arr)
		foreach($ids_arr as $id){
			$id_arr = explode(',',$id);
			
			banco_update
			(
				"ordem='".$id_arr[1]."'",
				"site_banners_imagens",
				"WHERE id_site_banners_imagens='".$id_arr[0]."'"
			);
		}
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_banners_data_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$titulo = $_REQUEST["titulo"];
		$sub_titulo = $_REQUEST["sub_titulo"];
		$url = $_REQUEST["url"];
		
		banco_update
		(
			"titulo='".$titulo."',".
			"sub_titulo='".$sub_titulo."',".
			"url='".$url."'",
			"site_banners_imagens",
			"WHERE id_site_banners_imagens='".$id."'"
		);
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_slide_show_add(){
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
			"site_slide_show"
		);
		
		$id_site_slide_show = banco_last_id();
		
		$num_total_rows = banco_total_rows
		(
			"site_slide_show",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$slide_show_nome = "slideshow".$num_total_rows;
		
		banco_update
		(
			"nome='".$nome."',".
			"path='".$slide_show_nome."'",
			"site_slide_show",
			"WHERE id_site_slide_show='".$id_site_slide_show."'"
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-files-path'].'/'.$_SYSTEM['SITE']['ftp-files-slideshow-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-slideshow-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-slideshow-path']);
			
			ftp_mkdir($_CONEXAO_FTP, $slide_show_nome);
			
			ftp_fechar_conexao();
			
			$saida = Array(
				'status' => 'Ok',
				'slide_show_nome' => $nome,
				'slide_show_id' => $id_site_slide_show,
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

function ajax_slides_show(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_slide_show',
			'nome',
		))
		,
		"site_slide_show",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_slide_show ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_slide_show' => $res['id_site_slide_show'],
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

function ajax_slides_show_images(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_slide_show_imagens',
				'file',
				'width',
				'height',
			))
			,
			"site_slide_show_imagens",
			"WHERE id_site_slide_show='".$id."'"
			." AND status='A'"
			." ORDER BY id_site_slide_show_imagens ASC"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_slide_show",
				"WHERE id_site_slide_show='".$id."'"
			);
			
			$slideshow_path = $resultado2[0]['path'];
			
			foreach($resultado as $res){
				if($res['file']){
					
					if(!$res['width'] || !$res['height']){
						$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-slideshow-path'] . '/' . $slideshow_path . '/' . $res['file']);
					
						banco_update
						(
							"width='".$imgInfo[0]."',".
							"height='".$imgInfo[1]."'",
							"site_slide_show_imagens",
							"WHERE id_site_slide_show_imagens='".$res['id_site_slide_show_imagens']."'"
						);
					} else {
						$imgInfo[0] = $res['width'];
						$imgInfo[1] = $res['height'];
					}
					
					$images[] = Array(
						'id' => $res['id_site_slide_show_imagens'],
						'file' => $res['file'],
						'imagem' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-slideshow-path'] . '/' . $slideshow_path . '/' . $res['file'],
						'mini' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-slideshow-path'] . '/' . $slideshow_path . '/mini/' . $res['file'],
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

function ajax_slides_show_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_slide_show',
			))
			,
			"site_slide_show",
			"WHERE id_site_slide_show='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_slide_show";
			$campo_tabela_extra = "WHERE id_site_slide_show='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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

function ajax_slides_show_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_slide_show',
				'path',
			))
			,
			"site_slide_show",
			"WHERE id_site_slide_show='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"site_slide_show_imagens",
				"WHERE id_site_slide_show='".$_REQUEST["id"]."'"
				." AND status='A'"
			);
			
			banco_update
			(
				"status='D'",
				"site_slide_show_imagens",
				"WHERE id_site_slide_show='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_slide_show",
				"WHERE id_site_slide_show='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				$slide_show_path = $resultado[0]['path'];
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-slideshow-path']);
				ftp_chdir($_CONEXAO_FTP,$slide_show_path);
				
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
				} else {
					ftp_chdir($_CONEXAO_FTP,'mini');
				}
				
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,'mini');
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,$slide_show_path);
				
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

function ajax_slides_show_images_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$slide = $_REQUEST["slide"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_slide_show',
				'path',
			))
			,
			"site_slide_show",
			"WHERE id_site_slide_show='".$slide."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_slide_show_imagens',
					'file',
				))
				,
				"site_slide_show_imagens",
				"WHERE id_site_slide_show_imagens='".$id."'"
				." AND status='A'"
			);
			
			if($resultado2){
				banco_update
				(
					"status='D'",
					"site_slide_show_imagens",
					"WHERE id_site_slide_show_imagens='".$id."'"
				);
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					$slide_show_path = $resultado[0]['path'];
					$file = $resultado2[0]['file'];
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-slideshow-path']);
					ftp_chdir($_CONEXAO_FTP,$slide_show_path);
					
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

function ajax_player_musicas_add(){
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
			"site_player_musicas"
		);
		
		$id_site_player_musicas = banco_last_id();
		
		$num_total_rows = banco_total_rows
		(
			"site_player_musicas",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$player_musicas_nome = "playermusicas".$num_total_rows;
		
		banco_update
		(
			"nome='".$nome."',".
			"path='".$player_musicas_nome."'",
			"site_player_musicas",
			"WHERE id_site_player_musicas='".$id_site_player_musicas."'"
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-files-path'].'/'.$_SYSTEM['SITE']['ftp-files-playermusicas-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-playermusicas-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-playermusicas-path']);
			
			ftp_mkdir($_CONEXAO_FTP, $player_musicas_nome);
			
			ftp_fechar_conexao();
			
			$saida = Array(
				'status' => 'Ok',
				'player_musicas_nome' => $nome,
				'player_musicas_id' => $id_site_player_musicas,
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

function ajax_players_musicas(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_player_musicas',
			'nome',
		))
		,
		"site_player_musicas",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_player_musicas ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_player_musicas' => $res['id_site_player_musicas'],
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

function ajax_players_musicas_mp3s(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_player_musicas_mp3s',
				'nome_original',
				'file',
			))
			,
			"site_player_musicas_mp3s",
			"WHERE id_site_player_musicas='".$id."'"
			." AND status='A'"
			." ORDER BY id_site_player_musicas_mp3s ASC"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_player_musicas",
				"WHERE id_site_player_musicas='".$id."'"
			);
			
			$playermusicas_path = $resultado2[0]['path'];
			
			foreach($resultado as $res){
				if($res['file']){
					$mp3s[] = Array(
						'id' => $res['id_site_player_musicas_mp3s'],
						'nome_original' => $res['nome_original'],
						'mp3' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-playermusicas-path'] . '/' . $playermusicas_path . '/' . $res['file'],
					);
				}
			}
			
			$saida = Array(
				'status' => 'Ok',
				'mp3s' => $mp3s
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

function ajax_players_musicas_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_player_musicas',
			))
			,
			"site_player_musicas",
			"WHERE id_site_player_musicas='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_player_musicas";
			$campo_tabela_extra = "WHERE id_site_player_musicas='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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

function ajax_players_musicas_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_player_musicas',
				'path',
			))
			,
			"site_player_musicas",
			"WHERE id_site_player_musicas='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"site_player_musicas_mp3s",
				"WHERE id_site_player_musicas='".$_REQUEST["id"]."'"
				." AND status='A'"
			);
			
			banco_update
			(
				"status='D'",
				"site_player_musicas_mp3s",
				"WHERE id_site_player_musicas='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_player_musicas",
				"WHERE id_site_player_musicas='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				$player_musicas_path = $resultado[0]['path'];
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-playermusicas-path']);
				ftp_chdir($_CONEXAO_FTP,$player_musicas_path);
				
				if($resultado2){
					foreach($resultado2 as $res){
						$file = $res['file'];
						$size += ftp_size($_CONEXAO_FTP,$file);
						ftp_delete($_CONEXAO_FTP,$file);
					}
				}
				
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,$player_musicas_path);
				
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

function ajax_players_musicas_mp3s_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$player = $_REQUEST["player"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_player_musicas',
				'path',
			))
			,
			"site_player_musicas",
			"WHERE id_site_player_musicas='".$player."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_player_musicas_mp3s',
					'file',
				))
				,
				"site_player_musicas_mp3s",
				"WHERE id_site_player_musicas_mp3s='".$id."'"
				." AND status='A'"
			);
			
			if($resultado2){
				banco_update
				(
					"status='D'",
					"site_player_musicas_mp3s",
					"WHERE id_site_player_musicas_mp3s='".$id."'"
				);
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					$player_musicas_path = $resultado[0]['path'];
					$file = $resultado2[0]['file'];
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-playermusicas-path']);
					ftp_chdir($_CONEXAO_FTP,$player_musicas_path);
					
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

function ajax_album_musicas_add(){
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
			"site_album_musicas"
		);
		
		$id_site_album_musicas = banco_last_id();
		
		$num_total_rows = banco_total_rows
		(
			"site_album_musicas",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$album_musicas_nome = "albummusicas".$num_total_rows;
		
		banco_update
		(
			"nome='".$nome."',".
			"path='".$album_musicas_nome."'",
			"site_album_musicas",
			"WHERE id_site_album_musicas='".$id_site_album_musicas."'"
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-files-path'].'/'.$_SYSTEM['SITE']['ftp-files-albummusicas-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-albummusicas-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-albummusicas-path']);
			
			ftp_mkdir($_CONEXAO_FTP, $album_musicas_nome);
			
			ftp_fechar_conexao();
			
			$saida = Array(
				'status' => 'Ok',
				'album_musicas_nome' => $nome,
				'album_musicas_id' => $id_site_album_musicas,
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

function ajax_albuns_musicas(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_album_musicas',
			'nome',
		))
		,
		"site_album_musicas",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_album_musicas ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_album_musicas' => $res['id_site_album_musicas'],
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

function ajax_albuns_musicas_mp3s(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_musicas_mp3s',
				'nome_original',
				'file',
			))
			,
			"site_album_musicas_mp3s",
			"WHERE id_site_album_musicas='".$id."'"
			." AND status='A'"
			." ORDER BY id_site_album_musicas_mp3s ASC"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_album_musicas",
				"WHERE id_site_album_musicas='".$id."'"
			);
			
			$albummusicas_path = $resultado2[0]['path'];
			
			foreach($resultado as $res){
				if($res['file']){
					$mp3s[] = Array(
						'id' => $res['id_site_album_musicas_mp3s'],
						'nome_original' => $res['nome_original'],
						'mp3' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-albummusicas-path'] . '/' . $albummusicas_path . '/' . $res['file'],
					);
				}
			}
			
			$saida = Array(
				'status' => 'Ok',
				'mp3s' => $mp3s
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

function ajax_albuns_musicas_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_musicas',
			))
			,
			"site_album_musicas",
			"WHERE id_site_album_musicas='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_album_musicas";
			$campo_tabela_extra = "WHERE id_site_album_musicas='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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

function ajax_albuns_musicas_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_musicas',
				'path',
			))
			,
			"site_album_musicas",
			"WHERE id_site_album_musicas='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"site_album_musicas_mp3s",
				"WHERE id_site_album_musicas='".$_REQUEST["id"]."'"
				." AND status='A'"
			);
			
			banco_update
			(
				"status='D'",
				"site_album_musicas_mp3s",
				"WHERE id_site_album_musicas='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_album_musicas",
				"WHERE id_site_album_musicas='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				$album_musicas_path = $resultado[0]['path'];
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-albummusicas-path']);
				ftp_chdir($_CONEXAO_FTP,$album_musicas_path);
				
				if($resultado2){
					foreach($resultado2 as $res){
						$file = $res['file'];
						$size += ftp_size($_CONEXAO_FTP,$file);
						ftp_delete($_CONEXAO_FTP,$file);
					}
				}
				
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,$album_musicas_path);
				
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

function ajax_albuns_musicas_mp3s_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$album = $_REQUEST["album"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_album_musicas',
				'path',
			))
			,
			"site_album_musicas",
			"WHERE id_site_album_musicas='".$album."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_album_musicas_mp3s',
					'file',
				))
				,
				"site_album_musicas_mp3s",
				"WHERE id_site_album_musicas_mp3s='".$id."'"
				." AND status='A'"
			);
			
			if($resultado2){
				banco_update
				(
					"status='D'",
					"site_album_musicas_mp3s",
					"WHERE id_site_album_musicas_mp3s='".$id."'"
				);
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					$album_musicas_path = $resultado[0]['path'];
					$file = $resultado2[0]['file'];
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-albummusicas-path']);
					ftp_chdir($_CONEXAO_FTP,$album_musicas_path);
					
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

function ajax_foto_perfil(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_biblioteca_imagens',
			'file',
			'width',
			'height',
		))
		,
		"site_biblioteca_imagens",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site_biblioteca_imagens='".$_REQUEST['id']."'"
	);
	
	if($resultado){
		$aux = explode('.',basename($resultado[0]['file']));
		$extensao = strtolower($aux[count($aux)-1]);
		
		$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
		$image_avatar = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."avatares".$_SYSTEM['SEPARADOR'].'avatar'.$usuario['id_usuario'].'.'.$extensao;
		$image_avatar_url = "/files/avatares/avatar".$usuario['id_usuario'].'.'.$extensao;
		
		copy($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $resultado[0]['file'], $tmp_image);
		
		if(!$resultado[0]['width'] || !$resultado[0]['height']){
			$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . $resultado[0]['file']);
		
			banco_update
			(
				"width='".$imgInfo[0]."',".
				"height='".$imgInfo[1]."'",
				"site_biblioteca_imagens",
				"WHERE id_site_biblioteca_imagens='".$resultado[0]['id_site_biblioteca_imagens']."'"
			);
		} else {
			$imgInfo[0] = $resultado[0]['width'];
			$imgInfo[1] = $resultado[0]['height'];
		}
		
		if($imgInfo[0] < $imgInfo[1]){
			$_RESIZE_IMAGE_Y_ZERO = true;
			resize_image($tmp_image, $image_avatar, 150, 150,false,false,true);
		} else {
			resize_image($tmp_image, $image_avatar, 150, 150,false,false,false);
		}
		
		unlink($tmp_image);
		
		$_SESSION[$_SYSTEM['ID']."usuario"]['avatar'] = $image_avatar_url;
		
		banco_update
		(
			"avatar='".$image_avatar_url."'",
			"usuario",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
		);
	
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
			$saida = Array(
			'status' => 'ImageOutroUser',
		);
	}
	
	return $saida;
}

function ajax_help_texto(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST['id']){
		$id = $_REQUEST['id'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'titulo',
				'texto',
			))
			,
			"conteudo",
			"WHERE identificador_auxiliar='".$id."'"
			." AND status!='D'"
		);
		
		if($resultado){
			$saida = Array(
				'status' => 'Ok',
				'texto' => $resultado[0]['texto'],
				'titulo' => $resultado[0]['titulo'],
			);
		} else {
			$saida = Array(
				'status' => 'ThisHelpIdNotDefined',
			);
		}
	} else {
		$saida = Array(
			'status' => 'RequestIdNotDefined',
		);
	}
	
	return $saida;
}

function ajax_favicon(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$image_url = $_REQUEST['image_url'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($image_url && $usuario){
		$aux_name = explode('/',$image_url);
		$aux = explode('.',basename($aux_name[count($aux_name)-1]));
		$extensao = strtolower($aux[count($aux)-1]);
		
		$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
		$tmp_image_ico = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.ico';
		
		$image_url = preg_replace('/https:/i', 'http:', $image_url);
		
		//$image_url = 'http:'.$image_url;
		
		if(copy($image_url, $tmp_image)){
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
				
				if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.($_SYSTEM['SITE']['ftp-files-path']?$_SYSTEM['SITE']['ftp-files-path'].'/':'').$_SYSTEM['SITE']['ftp-files-imagens-path'])) {
					ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-imagens-path']); // create directories that do not yet exist
				}
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-imagens-path']);
				
			} else {
				return json_encode(Array(
					'status' => 'FtpNotConnected'
				));
			}
			
			//$_RESIZE_IMAGE_Y_ZERO = true;
			
			require(dirname( __FILE__ ) . '/php-ico-master/class-php-ico.php');
			
			resize_image($tmp_image, $tmp_image, 48, 48,false,false,true);
			
			$ico_lib = new PHP_ICO( $tmp_image,array( array( 16, 16 ), array( 32, 32 ), array( 48, 48 )) );
			$ico_lib->save_ico( $tmp_image_ico );
			
			resize_image($tmp_image, $tmp_image, 38, 38,false,false,true);
			
			$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
			$pagina_favicon = preg_replace('/http:/i', '', $_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . '__favicon.'.$extensao;
			
			$pagina_favicon_version = banco_select_name
			(
				"pagina_favicon_version"
				,
				"site",
				"WHERE id_usuario='".$id_usuario."'"
				." AND id_site_pai IS NULL"
			);
			
			banco_update
			(
				"pagina_favicon_version = ".($pagina_favicon_version[0]['pagina_favicon_version'] ? 'pagina_favicon_version + ' : '')."1,".
				"pagina_favicon='".$pagina_favicon."'",
				"site",
				"WHERE id_usuario='".$id_usuario."'"
				." AND id_site_pai IS NULL"
			);
			
			$pagina_favicon_version = banco_select_name
			(
				"pagina_favicon_version"
				,
				"site",
				"WHERE id_usuario='".$id_usuario."'"
				." AND id_site_pai IS NULL"
			);
			
			$version = $pagina_favicon_version[0]['pagina_favicon_version'];
			
			ftp_put_file('__favicon.'.$extensao, $tmp_image);
			ftp_put_file('favicon.ico', $tmp_image_ico);
			
			unlink($tmp_image);
			unlink($tmp_image_ico);
			
			ftp_fechar_conexao();
			
			banco_update
			(
				"diskchanged=NULL",
				"host",
				"WHERE id_usuario='".$id_usuario."'"
				." AND atual IS TRUE"
			);
			
			$_SESSION[$_SYSTEM['ID']."site"] = false;
			
			$saida = Array(
				'status' => 'Ok',
				'favicon' => preg_replace('/http:/i', '', $_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-imagens-path'] . '/' . '__favicon.'.$extensao,
				'version' => (int)$version,
			);
		} else {
			$saida = Array(
				'status' => 'ImageDontCopy'.$image_url,
			);
		}
	} else {
		$saida = Array(
			'status' => 'ImageDontExist',
		);
	}
	
	return $saida;
}

function ajax_pagina_parallax(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST['val']){
		$val = $_REQUEST['val'];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		if($resultado){
			banco_update
			(
				"pagina_parallax='".$val."'",
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$_SESSION[$_SYSTEM['ID']."site"]["pagina_parallax"] = $val;
	
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'SiteNaoPertenceAoUsuario',
			);
		}
	} else {
		$saida = Array(
			'status' => 'ValueNotDefined',
		);
	}
	
	return $saida;
}

function ajax_pagina_menu_bolinha(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST['val']){
		$val = $_REQUEST['val'];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		if($resultado){
			banco_update
			(
				"pagina_menu_bolinhas='".$val."'",
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$_SESSION[$_SYSTEM['ID']."site"]["pagina_menu_bolinhas"] = $val;
	
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'SiteNaoPertenceAoUsuario',
			);
		}
	} else {
		$saida = Array(
			'status' => 'ValueNotDefined',
		);
	}
	
	return $saida;
}

function ajax_pagina_menu_bolinha_areas(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST['val']){
		$val = $_REQUEST['val'];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		if($resultado){
			banco_update
			(
				"pagina_menu_bolinhas_areas='".$val."'",
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$_SESSION[$_SYSTEM['ID']."site"]["pagina_menu_bolinhas_areas"] = $val;
	
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'SiteNaoPertenceAoUsuario',
			);
		}
	} else {
		$saida = Array(
			'status' => 'ValueNotDefined',
		);
	}
	
	return $saida;
}

function ajax_pagina_menu_bolinha_layout(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST['val']){
		$val = $_REQUEST['val'];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		if($resultado){
			banco_update
			(
				"pagina_menu_bolinhas_layout='".$val."'",
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$_SESSION[$_SYSTEM['ID']."site"]["pagina_menu_bolinhas_layout"] = $val;
	
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'SiteNaoPertenceAoUsuario',
			);
		}
	} else {
		$saida = Array(
			'status' => 'ValueNotDefined',
		);
	}
	
	return $saida;
}

function ajax_pagina_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST['pai_id']){
		$pai_id = $_REQUEST['pai_id'];
		$atual_id = $_REQUEST['atual_id'];
		$tipo = $_REQUEST['tipo'];
		$nivel = $_REQUEST['nivel'];
		$nome = $_REQUEST['nome'];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id = site_criar_identificador($nome,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),false,$pai_id);
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_host',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$id_host = $resultado[0]['id_host'];
		$campos = null;
		
		switch($tipo){
			case 'duplicar':
			case 'modelo':
			case 'em_branco':
				if($tipo == 'duplicar'){
					$resultado = banco_select_name
					(
						'*'
						,
						"site",
						"WHERE id_site='".$atual_id."'"
					);
					
					if($resultado)
					foreach($resultado[0] as $key => $val){
						switch($key){
							case 'id_site':
							case 'id_host':
							case 'id_usuario':
							case 'id_site_pai':
							case 'nome':
							case 'id':
							case 'atual':
							case 'publicado':
							case 'publicado_id':
							case 'data_criacao':
							case 'data_modificacao':
								// nada a fazer
							break;
							case 'html':
							case 'pagina_head_extra':
								$val = addslashes($val);
							default:
								if($val){
									$campo_nome = $key; $campo_valor = $val; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								}
						}
					}
				}
				
				if($nivel == 'abaixo'){
					$pai_id = $atual_id;
				} else {
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_site',
							'id_site_pai',
						))
						,
						"site",
						"WHERE id_site='".$atual_id."'"
					);
					
					$pai_id = ($resultado[0]['id_site_pai'] ? $resultado[0]['id_site_pai'] : $resultado[0]['id_site']);
				}
				
				$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_site_pai"; $campo_valor = $pai_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				if($tipo == 'modelo' || $tipo == 'em_branco'){
					$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				}
				
				banco_insert_name
				(
					$campos,
					"site"
				);
				
				$id_site = banco_last_id();
				
				banco_update
				(
					"atual=NULL",
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND atual IS TRUE"
				);
				
				banco_update
				(
					"atual=1",
					"site",
					"WHERE id_site='".$id_site."'"
				);
				
				$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-id"] = $id_site;
				$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-identificador"] = $id;
				
				if(!$_B2MAKE_PAGINA_LOCAL)publisher_sitemaps();
				atualizar_cache();
				
				$saida = Array(
					'id_site' => $id_site,
					'status' => 'Ok',
				);
			break;
			case 'duplicar-raiz':
				$resultado = banco_select_name
				(
					'*'
					,
					"site",
					"WHERE id_site='".$atual_id."'"
				);
				
				$paginaInicial = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_site',
						'id',
					))
					,
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id_site_pai IS NULL"
				);
				
				$id_site = $paginaInicial[0]['id_site'];
				$id = $paginaInicial[0]['id'];
				
				$campo_tabela = "site";
				$campo_tabela_extra = "WHERE id_site='".$id_site."'";
				
				if($resultado)
				foreach($resultado[0] as $key => $val){
					switch($key){
						case 'id_site':
						case 'id_host':
						case 'id_usuario':
						case 'id_site_pai':
						case 'id_site_templates':
						case 'nome':
						case 'id':
						case 'atual':
						case 'publicado':
						case 'publicado_id':
						case 'data_criacao':
						case 'data_modificacao':
							// nada a fazer
						break;
						case 'html':
						case 'pagina_head_extra':
							$val = addslashes($val);
						default:
							if($val){
								$campo_nome = $key; $campo_valor = $val; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
							}
					}
				}
				
				$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
				
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
				
				banco_update
				(
					"atual=NULL",
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND atual IS TRUE"
				);
				
				banco_update
				(
					"atual=1",
					"site",
					"WHERE id_site='".$id_site."'"
				);
				
				$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-id"] = $id_site;
				$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-identificador"] = $id;
				
				if(!$_B2MAKE_PAGINA_LOCAL)publisher_sitemaps();
				atualizar_cache();
				
				$saida = Array(
					'id_site' => $id_site,
					'status' => 'Ok',
				);
			break;
		}
	} else {
		$saida = Array(
			'status' => 'PaiIdNaoDefinido',
		);
	}
	
	return $saida;
}

function ajax_paginas_mais_resultados(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_SITES_MAIS_PAGINAS;
	
	$resultados = paginas_mais_resultados(Array(
		'ajax' => true,
	));
	
	$saida = Array(
		'status' => 'Ok',
		'resultados' => $resultados
	);
	
	if($_SITES_MAIS_PAGINAS){
		$saida['mais_paginas'] = 'sim';
	}
	
	return $saida;
}

function ajax_pagina_mudar(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$id = $_REQUEST['id'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'id',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site='".$id."'"
	);
	
	if($resultado){
		banco_update
		(
			"atual=NULL",
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		banco_update
		(
			"atual=1",
			"site",
			"WHERE id_site='".$id."'"
		);
		
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-id"] = $id;
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-identificador"] = $resultado[0]['id'];
		$_SESSION[$_SYSTEM['ID']."site"] = false;
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'SemPermissao',
		);
	}
	
	return $saida;
}

function ajax_paginas_trocar_pai(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$id = $_REQUEST['id'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'nome',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site='".$id."'"
	);
	
	if($resultado){
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"] = $id;
		$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-nome"] = $resultado[0]['nome'];
		
		$saida = Array(
			'id' => $id,
			'nome' => $resultado[0]['nome'],
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'SemPermissao',
		);
	}
	
	return $saida;
}

function ajax_paginas_voltar_pai(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$id = $_REQUEST['id'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_pai',
			'nome',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site='".$id."'"
	);
	
	if($resultado){
		if($resultado[0]['id_site_pai']){
			$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"] = $resultado[0]['id_site_pai'];
			
			if($_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-id"] == $resultado[0]['id_site_pai']){
				$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-nome"] = $_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-nome"];				
			} else {
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
					))
					,
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id_site='".$resultado[0]['id_site_pai']."'"
				);
				
				$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-nome"] = $resultado[0]['nome'];
			}
		} else {
			$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"] = $_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-id"];
			$_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-nome"] = $_SESSION[$_SYSTEM['ID']."b2make-pagina-raiz-nome"];
		}
		
		$saida = Array(
			'pai_id' => $_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-id"],
			'nome' => $_SESSION[$_SYSTEM['ID']."b2make-pagina-pai-nome"],
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'SemPermissao',
		);
	}
	
	return $saida;
}

function ajax_pagina_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_B2MAKE_URL_WORDS_BLOCKED;
	
	$id_site = $_REQUEST['id'];
	$nome = $_REQUEST['nome'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_pai',
			'id_site',
			'id',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site='".$id_site."'"
	);
	
	if($resultado){
		$id_site_pai = $resultado[0]['id_site_pai'];
		$id = $resultado[0]['id'];
		
		if($id_site_pai){
			if($_B2MAKE_URL_WORDS_BLOCKED){
				foreach($_B2MAKE_URL_WORDS_BLOCKED as $word){
					if($word == $id){
						$found = true;
						break;
					}
				}
			}
			
			if($found){
				$saida = Array(
					'status' => 'NaoPodeEditar',
				);
			} else {
			
				$id = site_criar_identificador($nome,$usuario['id_usuario'],$id_site);
				
				$campo_tabela = "site";
				$campo_tabela_extra = "WHERE id_site='".$id_site."'";
				
				$campo_nome = "nome"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "id"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
				
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
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-site-host'],
					'user' => $_SYSTEM['SITE']['ftp-site-user'],
					'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
				));
				
				if($_CONEXAO_FTP){
					$path = site_pagina_diretorio($id_site,true,false,false);
					
					$id_old = $resultado[0]['id'];
					
					ftp_rename($_CONEXAO_FTP, $id_old, $id);
					
					ftp_chdir($_CONEXAO_FTP,'/');
					
					$path = site_pagina_diretorio($id_site,true,false,true);
					
					ftp_rename($_CONEXAO_FTP, $id_old, $id);
					
					ftp_fechar_conexao();
				}
				
				if(!$_B2MAKE_PAGINA_LOCAL)publisher_sitemaps();
				atualizar_cache();
				
				$saida = Array(
					'status' => 'Ok',
				);
			}
		} else {
			$saida = Array(
				'status' => 'NaoPodeEditar',
			);
		}
	} else {
		$saida = Array(
			'status' => 'SemPermissao',
		);
	}
	
	return $saida;
}

function ajax_pagina_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_B2MAKE_URL_WORDS_BLOCKED;
	global $_B2MAKE_DEBUG;
	
	$id_site = $_REQUEST['id'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_pai',
			'id_site',
			'id',
			'publicado',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id_site='".$id_site."'"
	);
	
	if($resultado){
		$id_site_pai = $resultado[0]['id_site_pai'];
		$id = $resultado[0]['id'];
		
		if($id_site_pai){
			if($_B2MAKE_URL_WORDS_BLOCKED){
				foreach($_B2MAKE_URL_WORDS_BLOCKED as $word){
					if($word == $id){
						$found = true;
						break;
					}
				}
			}
			
			if($found){
				$saida = Array(
					'status' => 'NaoPodeDeletar',
				);
			} else {
				$filhos = site_pagina_filhos($id_site);
				
				$id = $resultado[0]['id'];
				$publicado = $resultado[0]['publicado'];
				$caminho = site_pagina_diretorio($id_site,false,true,false);
				
				$filhos[] = Array(
					'id' => $id,
					'id_site' => $id_site,
					'publicado' => $publicado,
					'caminho' => $caminho,
					'level' => 0,
				);
				
				$debug .= 'Nao Conectou<br>';
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-site-host'],
					'user' => $_SYSTEM['SITE']['ftp-site-user'],
					'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
				));
				
				function sortByLevel($a, $b) {
					return $b['level'] - $a['level'];
				}

				usort($filhos,'sortByLevel');
				
				if($_CONEXAO_FTP){
					ftp_pasv($_CONEXAO_FTP, true);
					
					$raiz = ftp_pwd($_CONEXAO_FTP);
					
					foreach($filhos as $filho){
						if(ftp_chdir($_CONEXAO_FTP, $raiz.$filho['caminho'])){
							$files = ftp_nlist($_CONEXAO_FTP, ".");
							
							foreach($files as $file){
								if($file == '.' || $file == '..') continue;
								ftp_delete($_CONEXAO_FTP, $file);
							}
							
							ftp_cdup($_CONEXAO_FTP);
							
							ftp_rmdir($_CONEXAO_FTP, $raiz.$filho['caminho']);
						}
						
						if($filho['id_site']){
							banco_update
							(
								"status='D'",
								"servicos",
								"WHERE id_site='".$filho['id_site']."'"
							);
							banco_update
							(
								"status='D'",
								"site_conteudos",
								"WHERE id_site='".$filho['id_site']."'"
							);
						}
						
						banco_delete
						(
							"site",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND id_site='".$filho['id_site']."'"
						);
					}
					
					if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-mobile-path'])) {
						ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-mobile-path']); // create directories that do not yet exist
					}
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-mobile-path']);
					
					$raiz = ftp_pwd($_CONEXAO_FTP);
					
					$raiz = rtrim($raiz, '/') . '/';
					
					foreach($filhos as $filho){
						if(ftp_chdir($_CONEXAO_FTP, $raiz.$filho['caminho'])){
							$files = ftp_nlist($_CONEXAO_FTP, ".");
							
							foreach($files as $file){
								if($file == '.' || $file == '..') continue;
								ftp_delete($_CONEXAO_FTP, $file);
							}
							
							ftp_cdup($_CONEXAO_FTP);
							
							ftp_rmdir($_CONEXAO_FTP, $raiz.$filho['caminho']);
						}
					}
					
					ftp_fechar_conexao();
					publisher_sitemaps();
				} else if($_B2MAKE_PAGINA_LOCAL){
					foreach($filhos as $filho){
						banco_delete
						(
							"site",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND id_site='".$filho['id_site']."'"
						);
					}
				}
				
				atualizar_cache();
				
				$saida = Array(
					'deletado' => $raizes,
					'status' => 'Ok',
				);
			}
		} else {
			$saida = Array(
				'status' => 'NaoPodeDeletar',
			);
		}
	} else {
		$saida = Array(
			'status' => 'SemPermissao',
		);
	}
	
	return $saida;
}

function ajax_servicos(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_AJAX_ALERTA;
	
	configurar_loja();
	
	if(!$_AJAX_ALERTA){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_servicos',
				'nome',
				'descricao',
				'imagem_path',
				'preco',
			))
			,
			"servicos",
			"WHERE status='A'"
			." AND id_loja='".$usuario['id_loja']."'"
			." ORDER BY nome ASC"
		);
		
		if($resultado){
			foreach($resultado as $res){
				$resultado2[] = Array(
					'imagem_path' => $res['imagem_path'],
					'id_servicos' => $res['id_servicos'],
					'nome' => $res['nome'],
					'descricao' => $res['descricao'],
					'preco' => $res['preco'],
				);
			}
			
			$servicos_categorias = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos_categorias',
					'nome',
				))
				,
				"servicos_categorias",
				"WHERE id_loja='".$usuario['id_loja']."'"
				." ORDER BY nome ASC"
			);
			
			$saida = Array(
				'resultado' => $resultado2,
				'servicos_categorias' => $servicos_categorias,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'Vazio'
			);
		}
	} else {
		$saida = Array(
			'status' => 'LojaBloqueada',
			'alerta' => $_AJAX_ALERTA
		);
	}
	
	return $saida;
}

function ajax_servicos_html_list(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_AJAX_ALERTA;
	
	configurar_loja();
	
	if(!$_AJAX_ALERTA){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS NOT NULL"
		);
		
		$url = parse_url($host[0]['url'], PHP_URL_HOST);
		
		$url = $url . '/platform/services-list';
		
		$data = false;
		$data['ajax'] = 'sim';
		
		$data = http_build_query($data);
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$json = curl_exec($curl);
		
		curl_close($curl);
		
		$json = json_decode($json,true);
		
		if($json){
			$saida = Array(
				'services_list' => $json['servicos'],
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'Vazio'
			);
		}
	} else {
		$saida = Array(
			'status' => 'LojaBloqueada',
			'alerta' => $_AJAX_ALERTA
		);
	}
	
	return $saida;
}

function site_testes(){
	
	echo '<div style="margin:200px 0px 0px 200px;">Testes</div>';
}

function ajax_formularios(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_formularios',
			'nome',
			'assunto',
			'email',
		))
		,
		"site_formularios",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_formularios ASC"
	);
	
	if(!$resultado){
		$campos = null;
		
		$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = 'Formulário 1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "assunto"; $campo_valor = 'Contato recebido no seu site.'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "email"; $campo_valor = $usuario['email']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site_formularios"
		);
		
		$id_site_formularios = banco_last_id();
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
				'nome',
				'assunto',
				'email',
			))
			,
			"site_formularios",
			"WHERE status='A'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." ORDER BY id_site_formularios ASC"
		);
		
		
		$campos = null;
		
		$campo_nome = "id_site_formularios"; $campo_valor = $id_site_formularios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "ordem"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "tipo"; $campo_valor = 'text'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site_formularios_campos"
		);
		
		$id = banco_last_id();
		
		$campo_tabela = "site_formularios_campos";
		$campo_tabela_extra = "WHERE id_site_formularios_campos='".$id."'";
		
		$campo = 'campo'.$id;
		$title = 'campo'.$id;
		
		$campo_nome = "campo"; $campo_valor = $campo; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "title"; $campo_valor = $title; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "obrigatorio"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
	}
	
	if($resultado){
		foreach($resultado as $res){
			$campos = ajax_formularios_campos($res['id_site_formularios']);
			
			$resultado2[] = Array(
				'id_site_formularios' => $res['id_site_formularios'],
				'campos' => $campos['campos'],
				'nome' => $res['nome'],
				'assunto' => $res['assunto'],
				'email' => $res['email'],
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

function ajax_formularios_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$nome = $_REQUEST['nome'];
	
	if($nome){
		$campos = null;
		
		$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site_formularios"
		);
		
		$id_site_formularios = banco_last_id();
		
		$campos = null;
		
		$campo_nome = "id_site_formularios"; $campo_valor = $id_site_formularios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "ordem"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "tipo"; $campo_valor = 'text'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site_formularios_campos"
		);
		
		$id = banco_last_id();
		
		$campo_tabela = "site_formularios_campos";
		$campo_tabela_extra = "WHERE id_site_formularios_campos='".$id."'";
		
		$campo = 'campo'.$id;
		$title = 'campo'.$id;
		
		$campo_nome = "campo"; $campo_valor = $campo; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "title"; $campo_valor = $title; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "obrigatorio"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
		
		site_library_update(Array(
			'widget' => 'formularios',
		));
		
		$campos = ajax_formularios_campos($id_site_formularios);
		
		$saida = Array(
			'status' => 'Ok',
			'campos' => $campos,
			'formularios_nome' => $nome,
			'formularios_id' => $id_site_formularios,
		);
	} else {
		$saida = Array(
			'status' => 'NoName'
		);
	}
	
	return $saida;
}

function ajax_formularios_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_formularios";
			$campo_tabela_extra = "WHERE id_site_formularios='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
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

function ajax_formularios_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			banco_update
			(
				"status='D'",
				"site_formularios",
				"WHERE id_site_formularios='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'status' => 'Ok'
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

function ajax_formularios_campos_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site_formularios = $_REQUEST['id_site_formularios'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$ordem = 0;
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'ordem',
				))
				,
				"site_formularios_campos",
				"WHERE id_site_formularios='".$id_site_formularios."'"
			);
			
			if($resultado)
			foreach($resultado as $res){
				if($ordem < (int)$res['ordem']){
					$ordem = (int)$res['ordem'] + 1;
				}
			}
			
			$campo_nome = "id_site_formularios"; $campo_valor = $id_site_formularios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "ordem"; $campo_valor = $ordem; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "tipo"; $campo_valor = 'text'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"site_formularios_campos"
			);
			
			$id = banco_last_id();
			
			$campo_tabela = "site_formularios_campos";
			$campo_tabela_extra = "WHERE id_site_formularios_campos='".$id."'";
			
			$campo = 'campo'.$id;
			$title = 'campo'.$id;
			
			$campo_nome = "campo"; $campo_valor = $campo; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			$campo_nome = "title"; $campo_valor = $title; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			$campo_nome = "obrigatorio"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
			
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
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'id' => (string)$id,
				'ordem' => $ordem,
				'nome' => $title,
				'campo' => $campo,
				'obrigatorio' => '1',
				'id_site_formularios_campos' => (string)$id,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site_formularios = $_REQUEST['id_site_formularios'];
	$id = $_REQUEST['id'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			banco_delete
			(
				"site_formularios_campos",
				"WHERE id_site_formularios='".$id_site_formularios."'"
				." AND id_site_formularios_campos='".$id."'"
			);
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
		
			$saida = Array(
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos($id_site_formularios = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	if(!$id_site_formularios)$id_site_formularios = $_REQUEST['id_site_formularios'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_formularios_campos',
					'campo',
					'title',
					'obrigatorio',
					'ordem',
					'tipo',
					'options_label',
				))
				,
				"site_formularios_campos",
				"WHERE id_site_formularios='".$id_site_formularios."'"
				." ORDER BY ordem ASC"
			);
			
			if($resultado)
			for($i=0;$i<count($resultado);$i++){
				$resultado[$i]['title'] = $resultado[$i]['title'];
				$resultado[$i]['options_label'] = $resultado[$i]['options_label'];
				
				if(
					$resultado[$i]['tipo'] == 'select' ||
					$resultado[$i]['tipo'] == 'checkbox'
				){
					$resultado2 = banco_select_name
					(
						banco_campos_virgulas(Array(
							'nome',
							'id_site_formularios_campos_opcoes',
						))
						,
						"site_formularios_campos_opcoes",
						"WHERE id_site_formularios_campos='".$resultado[$i]['id_site_formularios_campos']."'"
						." ORDER BY nome ASC"
					);
					
					if($resultado2){
						$campo_opcoes = false;
						
						foreach($resultado2 as $res2){
							$campo_opcoes[] = Array(
								'id' => $res2['id_site_formularios_campos_opcoes'],
								'nome' => $res2['nome'],
							);
						}
						
						$resultado[$i]['campo_opcoes'] = $campo_opcoes;
					}
				}
			}
			
			$saida = Array(
				'campos' => $resultado,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_vars(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site_formularios = $_REQUEST['id'];
	$value = $_REQUEST['value'];
	$campo = $_REQUEST['campo'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			banco_update
			(
				$campo."='".$value."'",
				"site_formularios",
				"WHERE id_site_formularios='".$id_site_formularios."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			$saida = Array(
				'campos' => $resultado,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos_val(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site_formularios = $_REQUEST['id_formularios'];
	$id_site_formularios_campos = $_REQUEST['id_campos'];
	$value = $_REQUEST['value'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo = site_formularios_criar_identificador($value,$id_site_formularios,$id_site_formularios_campos);
			
			banco_update
			(
				"title='".$value."',".
				"campo='".$campo."'",
				"site_formularios_campos",
				"WHERE id_site_formularios_campos='".$id_site_formularios_campos."'"
				." AND id_site_formularios='".$id_site_formularios."'"
			);
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'campo' => $campo,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos_obrigatorio(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site_formularios = $_REQUEST['id_formularios'];
	$id_site_formularios_campos = $_REQUEST['id_campos'];
	$checked = $_REQUEST['checked'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			banco_update
			(
				"obrigatorio=".($checked == 's' ? '1' : 'NULL'),
				"site_formularios_campos",
				"WHERE id_site_formularios_campos='".$id_site_formularios_campos."'"
				." AND id_site_formularios='".$id_site_formularios."'"
			);
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'campo' => $campo,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos_tipo(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site_formularios = $_REQUEST['id_formularios'];
	$id_site_formularios_campos = $_REQUEST['id_campos'];
	$value = $_REQUEST['value'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			banco_update
			(
				"tipo='".$value."'",
				"site_formularios_campos",
				"WHERE id_site_formularios_campos='".$id_site_formularios_campos."'"
				." AND id_site_formularios='".$id_site_formularios."'"
			);
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'campo' => $campo,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos_order(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_site_formularios = $_REQUEST['id_formularios'];
	$ids = $_REQUEST['ids'];
	
	if($id_site_formularios){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_formularios',
			))
			,
			"site_formularios",
			"WHERE id_site_formularios='".$id_site_formularios."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$ids_arr = explode(';',$ids);
			
			if($ids_arr)
			foreach($ids_arr as $id){
				$id_arr = explode(',',$id);
				
				banco_update
				(
					"ordem='".$id_arr[1]."'",
					"site_formularios_campos",
					"WHERE id_site_formularios_campos='".$id_arr[0]."'"
					." AND id_site_formularios='".$id_site_formularios."'"
				);
			}
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'campo' => $campo,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'DadosNaoPertenceAoUsuario'
			);
		}
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos_opcoes_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$nome = $_REQUEST['nome'];
	$campo_id = $_REQUEST['campo_id'];
	
	if($nome){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_site_formularios',
			))
			,
			"site_formularios as t1, site_formularios_campos as t2",
			"WHERE t1.id_site_formularios=t2.id_site_formularios"
			." AND t1.id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND t2.id_site_formularios_campos='".$campo_id."'"
		);
		
		if($resultado){
			$campos = null;
			
			$campo_nome = "id_site_formularios_campos"; $campo_valor = $campo_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $campo_valor = $nome; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"site_formularios_campos_opcoes"
			);
			
			$id_site_formularios_campos_opcoes = banco_last_id();
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'status' => 'Ok',
				'nome' => $nome,
				'id' => $id_site_formularios_campos_opcoes,
			);
		} else {
			$saida = Array(
				'status' => 'NoData'
			);
		}
	} else {
		$saida = Array(
			'status' => 'NoName'
		);
	}
	
	return $saida;
}

function ajax_formularios_campos_opcoes_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		$campo_id = $_REQUEST['campo_id'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_site_formularios',
			))
			,
			"site_formularios as t1, site_formularios_campos as t2",
			"WHERE t1.id_site_formularios=t2.id_site_formularios"
			." AND t1.id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND t2.id_site_formularios_campos='".$campo_id."'"
		);
		
		if($resultado){
			$campo_tabela = "site_formularios_campos_opcoes";
			$campo_tabela_extra = "WHERE id_site_formularios_campos_opcoes='".$id."'";
			
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
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
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

function ajax_formularios_campos_opcoes_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		$id = $_REQUEST['id'];
		$campo_id = $_REQUEST['campo_id'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_site_formularios',
			))
			,
			"site_formularios as t1, site_formularios_campos as t2",
			"WHERE t1.id_site_formularios=t2.id_site_formularios"
			." AND t1.id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND t2.id_site_formularios_campos='".$campo_id."'"
		);
		
		if($resultado){
			banco_delete
			(
				"site_formularios_campos_opcoes",
				"WHERE id_site_formularios_campos_opcoes='".$id."'"
			);
			
			site_library_update(Array(
				'widget' => 'formularios',
			));
			
			$saida = Array(
				'status' => 'Ok'
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

function ajax_formularios_campos_label_change(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$value = $_REQUEST["value"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$campo_id = $_REQUEST['campo_id'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.id_site_formularios',
		))
		,
		"site_formularios as t1, site_formularios_campos as t2",
		"WHERE t1.id_site_formularios=t2.id_site_formularios"
		." AND t1.id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND t2.id_site_formularios_campos='".$campo_id."'"
	);
	
	if($resultado){
		$campo_tabela = "site_formularios_campos";
		$campo_tabela_extra = "WHERE id_site_formularios_campos='".$campo_id."'";
		
		$value = $_REQUEST['value'];
		
		$campo_nome = "options_label"; $editar[$campo_tabela][] = $campo_nome."='" . $value . "'";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
		
		site_library_update(Array(
			'widget' => 'formularios',
		));
		
		$saida = Array(
			'status' => 'Ok',
			'value' => $value,
		);
	} else {
		$saida = Array(
			'status' => 'NaoExisteId'
		);
	}
	
	return $saida;
}

function ajax_menu_paginas_start(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'id_site_pai',
			'nome',
			'id',
			'publicado',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY nome DESC"
	);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'url',
			'url_mobile',
			'https',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	if($resultado)
	foreach($resultado as $res){
		$path = site_pagina_diretorio($res['id_site'],false,true,false,false);
		
		$url = ($host[0]['https'] ? preg_replace('/http:/i', 'https:', $host[0]['url']):preg_replace('/https:/i', 'http:', $host[0]['url'])) . $path;
		$url_mobile = ($host[0]['https'] ? 'https:':'http:').'//'.rtrim($host[0]['url_mobile'], '/').'/'.$path;
		
		if($res['id_site_pai']){
			$paginas[$res['id_site_pai']][] = Array(
				'id' => $res['id_site'],
				'id2' => $res['id'],
				'publicado' => $res['publicado'],
				'url' => $url,
				'url_mobile' => $url_mobile,
				'nome' => $res['nome'],
			);
		} else {
			$paginas[0] = Array(
				'id' => $res['id_site'],
				'id2' => $res['id'],
				'publicado' => $res['publicado'],
				'url' => $url,
				'url_mobile' => $url_mobile,
				'nome' => 'Página Inicial',
			);
		}
	}
	
	$saida = Array(
		'paginas' => $paginas,
		'status' => 'Ok'
	);
	
	return $saida;
}

function ajax_menu_paginas_ler_conteudos(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$type = $_REQUEST['type'];
	
	switch($type){
		case 'servicos':
			configurar_loja();
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos',
					'nome',
				))
				,
				"servicos",
				"WHERE id_loja='".$usuario['id_loja']."'"
				." AND status='A'"
				." ORDER BY nome ASC"
			);
			
			if($resultado)
			foreach($resultado as $res){
				$resultado2[] = Array(
					'id' => $res['id_servicos'],
					'nome' => $res['nome'],
				);
			}
			
			$saida = Array(
				'resultado' => $resultado2,
				'status' => 'Ok'
			);
		break;
		case 'conteudos':
			configurar_conteudos();
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos',
					'nome',
				))
				,
				"site_conteudos",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND (id_site_conteudos_tipos IS NULL OR id_site_conteudos_tipos='-1')"
				." AND status='A'"
				." ORDER BY nome ASC"
			);
			
			if($resultado)
			foreach($resultado as $res){
				$resultado2[] = Array(
					'id' => $res['id_site_conteudos'],
					'nome' => $res['nome'],
				);
			}
			
			$saida = Array(
				'resultado' => $resultado2,
				'status' => 'Ok'
			);
		break;
		case 'conteudos-mais':
			configurar_conteudos();
			
			$id_site = $_SESSION[$_SYSTEM['ID']."b2make-pagina-atual-id"];
			
			$site_conteudos_tipos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tipos',
				))
				,
				"site_conteudos_tipos",
				"WHERE id_site='".$id_site."'"
			);
			
			$id_site_conteudos_tipos = $site_conteudos_tipos[0]['id_site_conteudos_tipos'];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos',
					'nome',
				))
				,
				"site_conteudos",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
				." AND status='A'"
				." ORDER BY nome ASC"
			);
			
			if($resultado)
			foreach($resultado as $res){
				$resultado2[] = Array(
					'id' => $res['id_site_conteudos'],
					'nome' => $res['nome'],
				);
			}
			
			$saida = Array(
				'resultado' => $resultado2,
				'status' => 'Ok'
			);
		break;
		
	}
	
	return $saida;
}

function ajax_menu_paginas_publicar_conteudos(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$type = $_REQUEST['type'];
	$conteudos_ids = $_REQUEST['conteudos_ids'];
	
	switch($type){
		case 'servicos':
			configurar_loja();
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos',
				))
				,
				"servicos",
				"WHERE id_loja='".$usuario['id_loja']."'"
				." AND status='A'"
				." ORDER BY nome ASC"
			);
			
			$ids_arr = explode(',',$conteudos_ids);
			
			if($ids_arr)
			foreach($ids_arr as $id){
				$encontrou = false;
				if($resultado)
				foreach($resultado as $res){
					if($id == $res['id_servicos']){
						$encontrou = true;
						break;
					}
				}
				
				if(!$encontrou){
					$id_sem_permissao = true;
					break;
				}
				
				$ids_servicos[] = $id;
			}
			
			if($id_sem_permissao){
				$saida = Array(
					'status' => 'SemPermissao'
				);
			} else {
				publisher_pagina_mestre_servicos(Array(
					'ids_servicos' => $ids_servicos,
					'mobile' => ($_SESSION[$_SYSTEM['ID']."multi_screen_device"] == 'phone' ? true : false),
				));
				
				$saida = Array(
					'resultado' => $resultado2,
					'status' => 'Ok'
				);
			}
		break;
		case 'conteudos':
		case 'conteudos-mais':
			configurar_conteudos();
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos',
				))
				,
				"site_conteudos",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND status='A'"
				." ORDER BY nome ASC"
			);
			
			$ids_arr = explode(',',$conteudos_ids);
			
			if($ids_arr)
			foreach($ids_arr as $id){
				$encontrou = false;
				if($resultado)
				foreach($resultado as $res){
					if($id == $res['id_site_conteudos']){
						$encontrou = true;
						break;
					}
				}
				
				if(!$encontrou){
					$id_sem_permissao = true;
					break;
				}
				
				$id_site_conteudos[] = $id;
			}
			
			if($id_sem_permissao){
				$saida = Array(
					'status' => 'SemPermissao',
					'ids' => $conteudos_ids
				);
			} else {
				publisher_pagina_mestre_conteudos(Array(
					'id_site_conteudos' => $id_site_conteudos,
					'mobile' => ($_SESSION[$_SYSTEM['ID']."multi_screen_device"] == 'phone' ? true : false),
				));
				
				$saida = Array(
					'resultado' => $resultado2,
					'status' => 'Ok'
				);
			}
		break;
		
	}
	
	return $saida;
}

function ajax_contents(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_AJAX_ALERTA;
	
	configurar_loja();
	
	if(!$_AJAX_ALERTA){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos',
				'nome',
				'texto',
				'imagem_path',
			))
			,
			"site_conteudos",
			"WHERE status='A'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." ORDER BY nome ASC"
		);
		
		if($resultado){
			foreach($resultado as $res){
				$resultado2[] = Array(
					'imagem_path' => $res['imagem_path'],
					'id_site_conteudos' => $res['id_site_conteudos'],
					'nome' => $res['nome'],
					'texto' => $res['texto'],
				);
			}
			
			$tags = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tags',
					'id_site_conteudos_tags_pai',
					'nome',
					'cor',
				))
				,
				"site_conteudos_tags",
				"WHERE status='A'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." ORDER BY nome ASC"
			);
			
			if($tags)
			foreach($tags as $tag){
				$tags2[] = Array(
					'id_site_conteudos_tags' => $tag['id_site_conteudos_tags'],
					'id_site_conteudos_tags_pai' => $tag['id_site_conteudos_tags_pai'],
					'nome' => $tag['nome'],
					'cor' => $tag['cor'],
				);
			}
			
			$conteudos_tipos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tipos',
					'nome',
				))
				,
				"site_conteudos_tipos",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." ORDER BY nome ASC"
			);
			
			if($conteudos_tipos)
			foreach($conteudos_tipos as $conteudos_tipo){
				$conteudos_tipo2[] = Array(
					'id_site_conteudos_tipos' => $conteudos_tipo['id_site_conteudos_tipos'],
					'nome' => $conteudos_tipo['nome'],
				);
			}
			
			$saida = Array(
				'conteudos_tipos' => $conteudos_tipo2,
				'tags' => $tags2,
				'resultado' => $resultado2,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'Vazio'
			);
		}
	} else {
		$saida = Array(
			'status' => 'LojaBloqueada',
			'alerta' => $_AJAX_ALERTA
		);
	}
	
	//echo print_r($saida,true);exit;
	
	return $saida;
}

function ajax_contents_html_list(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_AJAX_ALERTA;
	
	configurar_loja();
	
	if(!$_AJAX_ALERTA){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS NOT NULL"
		);
		
		$url = parse_url($host[0]['url'], PHP_URL_HOST);
		
		$url = $url . '/platform/contents-list';
		
		$data = false;
		$data['ajax'] = 'sim';
		
		$data = http_build_query($data);
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$json = curl_exec($curl);
		
		curl_close($curl);
		
		$json = json_decode($json,true);
		
		if($json){
			$saida = Array(
				'conteudos_list' => $json['conteudos_list'],
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'Vazio',
			);
		}
	} else {
		$saida = Array(
			'status' => 'LojaBloqueada',
			'alerta' => $_AJAX_ALERTA
		);
	}
	
	return $saida;
}

function ajax_multi_screen_change(){
	global $_SYSTEM;
	
	if($_REQUEST['device']){
		$_SESSION[$_SYSTEM['ID']."multi_screen_device"] = $_REQUEST['device'];
		
		if($_REQUEST['device'] == 'phone'){
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'html_mobile',
				))
				,
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			if(!$resultado[0]['html_mobile']){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'html',
					))
					,
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND atual IS TRUE"
				);
				
				banco_update
				(
					"html_mobile='".addslashes($resultado[0]['html'])."'",
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND atual IS TRUE"
				);
				
				$_SESSION[$_SYSTEM['ID']."multi_screen_widgets_verify"] = true;
			}
		}
		
		$saida = Array(
			'status' => 'Ok'
		);
	} else {
		$saida = Array(
			'status' => 'DeviceNotSent',
		);
	}
	
	return $saida;
}

function ajax_multi_screen_reset_mobile(){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'html',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	banco_update
	(
		"html_mobile='".addslashes($resultado[0]['html'])."'",
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	$_SESSION[$_SYSTEM['ID']."multi_screen_widgets_verify"] = true;
	
	$saida = Array(
		'status' => 'Ok'
	);
	
	return $saida;
}

function ajax_posts_filter(){
	global $_SYSTEM;
	global $_AJAX_ALERTA;
	
	configurar_loja();
	
	if(!$_AJAX_ALERTA){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tags',
				'nome',
				'cor',
			))
			,
			"site_conteudos_tags",
			"WHERE status='A'"
			." AND id_site_conteudos_tags_pai='0'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." ORDER BY nome ASC"
		);
		
		if($resultado){
			foreach($resultado as $res){
				$resultado2[] = Array(
					'cor' => $res['cor'],
					'id_site_conteudos_tags' => $res['id_site_conteudos_tags'],
					'nome' => $res['nome'],
				);
			}
			
			$ficha_html = paginaModelo('plugins/posts-filter/ficha-html/html.html');
			$ficha_html_vertical = paginaModelo('plugins/posts-filter/ficha-html-vertical/html.html');
			$sem_resultados_html = paginaModelo('plugins/posts-filter/sem-resultados-html/html.html');
			
			$site_conteudos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos',
					'nome',
					'texto',
					'imagem_path',
				))
				,
				"site_conteudos",
				"WHERE status='A'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." ORDER BY nome ASC"
			);
			
			if($site_conteudos){
				foreach($site_conteudos as $res){
					$site_conteudos2[] = Array(
						'id_site_conteudos' => $res['id_site_conteudos'],
						'nome' => $res['nome'],
					);
				}
			}
			
			$conteudos_tipos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tipos',
					'nome',
				))
				,
				"site_conteudos_tipos",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." ORDER BY nome ASC"
			);
			
			if($conteudos_tipos)
			foreach($conteudos_tipos as $conteudos_tipo){
				$conteudos_tipo2[] = Array(
					'id_site_conteudos_tipos' => $conteudos_tipo['id_site_conteudos_tipos'],
					'nome' => $conteudos_tipo['nome'],
				);
			}
			
			$saida = Array(
				'conteudos_tipos' => $conteudos_tipo2,
				'site_conteudos' => $site_conteudos2,
				'resultado' => $resultado2,
				'ficha_html' => $ficha_html,
				'ficha_html_vertical' => $ficha_html_vertical,
				'sem_resultados_html' => $sem_resultados_html,
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'Vazio'
			);
		}
	} else {
		$saida = Array(
			'status' => 'LojaBloqueada',
			'alerta' => $_AJAX_ALERTA
		);
	}
	
	return $saida;
}

function ajax_posts_filter_html_list(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_AJAX_ALERTA;
	
	configurar_loja();
	
	if(!$_AJAX_ALERTA){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'json',
			))
			,
			"site_library",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND widget='posts-filter'"
		);
		
		if($resultado){
			$saida = Array(
				'list' => json_decode($resultado[0]['json']),
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'Vazio'
			);
		}
	} else {
		$saida = Array(
			'status' => 'LojaBloqueada',
			'alerta' => $_AJAX_ALERTA
		);
	}
	
	return $saida;
}

function ajax_conteiner_banners_add(){
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
			"site_banners"
		);
		
		$id_site_banners = banco_last_id();
		
		$num_total_rows = banco_total_rows
		(
			"site_banners",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$banners_nome = "banners".$num_total_rows;
		
		banco_update
		(
			"nome='".$nome."',".
			"path='".$banners_nome."'",
			"site_banners",
			"WHERE id_site_banners='".$id_site_banners."'"
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-files-host'],
			'user' => $_SYSTEM['SITE']['ftp-files-user'],
			'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
		));
		
		if($_CONEXAO_FTP){
			if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
			
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-files-path'].'/'.$_SYSTEM['SITE']['ftp-files-banners-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-banners-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-banners-path']);
			
			ftp_mkdir($_CONEXAO_FTP, $banners_nome);
			
			ftp_fechar_conexao();
			
			$saida = Array(
				'status' => 'Ok',
				'conteiner_banners_nome' => $nome,
				'conteiner_banners_id' => $id_site_banners,
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

function ajax_conteiner_banners(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_banners',
			'nome',
		))
		,
		"site_banners",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_site_banners ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2[] = Array(
				'id_site_banners' => $res['id_site_banners'],
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

function ajax_conteiner_banners_images(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners_imagens',
				'file',
				'width',
				'height',
				'titulo',
				'sub_titulo',
				'url',
			))
			,
			"site_banners_imagens",
			"WHERE id_site_banners='".$id."'"
			." AND status='A'"
			." ORDER BY ordem ASC,id_site_banners_imagens ASC"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'path',
				))
				,
				"site_banners",
				"WHERE id_site_banners='".$id."'"
			);
			
			$banners_path = $resultado2[0]['path'];
			
			foreach($resultado as $res){
				if($res['file']){
					if(!$res['width'] || !$res['height']){
						$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-banners-path'] . '/' . $banners_path . '/' . $res['file']);
						
						banco_update
						(
							"width='".$imgInfo[0]."',".
							"height='".$imgInfo[1]."'",
							"site_banners_imagens",
							"WHERE id_site_banners_imagens='".$res['id_site_banners_imagens']."'"
						);
					} else {
						$imgInfo[0] = $res['width'];
						$imgInfo[1] = $res['height'];
					}
					
					$images[] = Array(
						'titulo' => $res['titulo'],
						'sub_titulo' => $res['sub_titulo'],
						'url' => $res['url'],
						'file' => $res['file'],
						'id' => $res['id_site_banners_imagens'],
						'imagem' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-banners-path'] . '/' . $banners_path . '/' . $res['file'],
						'mini' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-banners-path'] . '/' . $banners_path . '/mini/' . $res['file'],
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

function ajax_conteiner_banners_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners',
			))
			,
			"site_banners",
			"WHERE id_site_banners='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_banners";
			$campo_tabela_extra = "WHERE id_site_banners='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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

function ajax_conteiner_banners_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners',
				'path',
			))
			,
			"site_banners",
			"WHERE id_site_banners='".$_REQUEST["id"]."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"site_banners_imagens",
				"WHERE id_site_banners='".$_REQUEST["id"]."'"
				." AND status='A'"
			);
			
			banco_update
			(
				"status='D'",
				"site_banners_imagens",
				"WHERE id_site_banners='".$_REQUEST["id"]."'"
			);
			banco_update
			(
				"status='D'",
				"site_banners",
				"WHERE id_site_banners='".$_REQUEST["id"]."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				'manual' => true,
				'host' => $_SYSTEM['SITE']['ftp-files-host'],
				'user' => $_SYSTEM['SITE']['ftp-files-user'],
				'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
			));
			
			if($_CONEXAO_FTP){
				$banners_path = $resultado[0]['path'];
				
				ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-banners-path']);
				ftp_chdir($_CONEXAO_FTP,$banners_path);
				
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
				} else {
					ftp_chdir($_CONEXAO_FTP,'mini');
				}
				
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,'mini');
				ftp_chdir($_CONEXAO_FTP,'..');
				ftp_rmdir($_CONEXAO_FTP,$banners_path);
				
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

function ajax_conteiner_banners_images_delete(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$banners = $_REQUEST["banners"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_banners',
				'path',
			))
			,
			"site_banners",
			"WHERE id_site_banners='".$banners."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_banners_imagens',
					'file',
				))
				,
				"site_banners_imagens",
				"WHERE id_site_banners_imagens='".$id."'"
				." AND status='A'"
			);
			
			if($resultado2){
				banco_update
				(
					"status='D'",
					"site_banners_imagens",
					"WHERE id_site_banners_imagens='".$id."'"
				);
				
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					$banners_path = $resultado[0]['path'];
					$file = $resultado2[0]['file'];
					
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-banners-path']);
					ftp_chdir($_CONEXAO_FTP,$banners_path);
					
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

function ajax_conteiner_banners_order(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["ids"]){
		$ids = $_REQUEST["ids"];
		$ids_arr = explode(';',$ids);
		
		if($ids_arr)
		foreach($ids_arr as $id){
			$id_arr = explode(',',$id);
			
			banco_update
			(
				"ordem='".$id_arr[1]."'",
				"site_banners_imagens",
				"WHERE id_site_banners_imagens='".$id_arr[0]."'"
			);
		}
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_conteiner_banners_data_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$titulo = $_REQUEST["titulo"];
		$sub_titulo = $_REQUEST["sub_titulo"];
		$url = $_REQUEST["url"];
		
		banco_update
		(
			"titulo='".$titulo."',".
			"sub_titulo='".$sub_titulo."',".
			"url='".$url."'",
			"site_banners_imagens",
			"WHERE id_site_banners_imagens='".$id."'"
		);
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_importar_pagina_b2make(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($_REQUEST["codigo_html"]){
		$codigo_html = stripslashes($_REQUEST["codigo_html"]);
		
		include_once('../'.$_SYSTEM['INCLUDE_PATH']."php/phpQuery/phpQuery.php");
		
		phpQuery::newDocumentHTML($codigo_html);
		
		pq('#b2make-shadow')->remove();
		
		$html = pq('body')->html();
		
		$html = addslashes($html);
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$campo_tabela = "site";
		$campo_tabela_extra = "WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE";
		
		$campo_nome = "html"; $campo_valor = $html; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		
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
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'IdNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_conteiner_areas_globais_add(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$nome = $_REQUEST['nome'];
	$mobile = ($_REQUEST['mobile'] == 's' ? true : false);
	
	if($nome){
		$identificador = banco_identificador(Array(
			'id' => $nome,
			'tabela' => Array(
				'nome' => 'site_areas_globais',
				'campo' => 'id',
				'id_nome' => 'id',
				'id_valor' => false,
				'sem_status' => true,
			),
		));
		
		$campos = null;
		
		$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = $identificador; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		if($mobile){$campo_nome = "mobile"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);}
		
		banco_insert_name
		(
			$campos,
			"site_areas_globais"
		);
		
		$_SESSION[$_SYSTEM['ID']."site"]['areas_globais'][] = Array(
			'nome' => $nome,
			'id' => $identificador,
			'mobile' => ($mobile ? true : false),
		);
		
		$saida = Array(
			'status' => 'Ok',
			'conteiner_areas_globais_nome' => $nome,
			'conteiner_areas_globais_id' => $identificador,
		);
	} else {
		$saida = Array(
			'status' => 'NoName'
		);
	}
	
	return $saida;
}

function ajax_conteiner_areas_globais_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_areas_globais',
			))
			,
			"site_areas_globais",
			"WHERE id='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			$campo_tabela = "site_areas_globais";
			$campo_tabela_extra = "WHERE id='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'";
			
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
			
			$areas_globais = $_SESSION[$_SYSTEM['ID']."site"]['areas_globais'];
			
			if($areas_globais)
			foreach($areas_globais as $ag){
				if($ag['id'] == $id){
					$ag['nome'] = $nome;
				}
				
				$areas_globais_novo[] = $ag;
			}
			
			if($areas_globais_novo){
				$_SESSION[$_SYSTEM['ID']."site"]['areas_globais'] = $areas_globais_novo;
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

function ajax_conteiner_areas_globais_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_areas_globais',
			))
			,
			"site_areas_globais",
			"WHERE id='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			banco_delete
			(
				"site_areas_globais",
				"WHERE id='".$id."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			$areas_globais = $_SESSION[$_SYSTEM['ID']."site"]['areas_globais'];
			$areas_globais_novo = Array();
			
			if($areas_globais)
			foreach($areas_globais as $ag){
				if($ag['id'] != $id){
					$areas_globais_novo[] = $ag;
				}
			}
			
			$_SESSION[$_SYSTEM['ID']."site"]['areas_globais'] = $areas_globais_novo;
			
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

function ajax_conteiner_areas_globais_load(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["id"]){
		$id = $_REQUEST["id"];
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_areas_globais',
			))
			,
			"site_areas_globais",
			"WHERE id='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado){
			if($_SESSION[$_SYSTEM['ID']."areas_globais_html"]){
				$areas_globais_html = $_SESSION[$_SYSTEM['ID']."areas_globais_html"];
				
				foreach($areas_globais_html as $key => $val){
					if($key == $id){
						$found = true;
						$versao = $val['versao'];
						$html = $val['html'];
					}
				}
			}
			
			if(!$found){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'html',
						'versao',
					))
					,
					"site_areas_globais",
					"WHERE id='".$id."'"
					." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				);
				
				if($resultado){
					$found = true;
					$versao = $resultado[0]['versao'];
					$html = $resultado[0]['html'];
					
					$_SESSION[$_SYSTEM['ID']."areas_globais_html"][$id] = Array(
						'versao' => $versao,
						'html' => $html,
					);
				}
			}
			
			$saida = Array(
				'status' => 'Ok',
				'found' => ($found ? true : false),
				'versao' => $versao,
				'html' => $html,
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

function ajax_google_fonts_change(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$google_fontes = $_REQUEST["google_fontes"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_host',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	$id_host = $resultado[0]['id_host'];
	
	if($resultado){
		banco_update
		(
			"google_fonts='".$google_fontes."'",
			"host",
			"WHERE id_host='".$id_host."'"
		);
		
		$_SESSION[$_SYSTEM['ID']."google_fonts_installed"] = $google_fontes;
		$_SESSION[$_SYSTEM['ID']."tinymce-version"] = time();
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'HostFromAnotherUser'
		);
	}
	
	return $saida;
}

// ======================================================================================

function css(){
	global $_SYSTEM;
	
	if($_REQUEST["opcao"] == 'tinymce'){
		header("Content-Type: text/css");
		
		if($_SESSION[$_SYSTEM['ID']."google_fonts_installed"]){
			$fonts = explode('|',$_SESSION[$_SYSTEM['ID']."google_fonts_installed"]);
			
			if($fonts)
			foreach($fonts as $font){
				echo "@import url('https://fonts.googleapis.com/css?family=".$font."');\n";
			}
			
			echo "\n";
		}
		
		if(!$_REQUEST["only-fonts"])
		echo "html,p,h1,h2,h3,h4,h5,h6{
	line-height: normal !important;
}";
	}
}

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
}

function ajax(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_PROJETO;
	global $_ONLY_REQUIRE_ACCESS;
	global $_B2MAKE_PATH;
	
	switch($_REQUEST["opcao"]){
		case 'segmentos':
		case 'segmento-dados':
		case 'segmento-del':
		case 'segmento-add':
		case 'segmento-edit':
		case 'templates': 
		case 'template-dados':
		case 'template-del':
		case 'template-add':
		case 'template-edit':
		case 'template-save':
			if($_SESSION[$_SYSTEM['ID']."permissao_id"] != $_PROJETO['b2make_permissao_id_modelo_site']){
				$saida = Array(
					'status' => 'SemPermissao',
				);
				
				return json_encode($saida);
			}
		break;
	}
	
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
	
	switch($_REQUEST["modulo"]){
		case 'install-host': $_ONLY_REQUIRE_ACCESS = true; $saida = require_once($_SYSTEM['PATH'].$_B2MAKE_PATH.$_SYSTEM['SEPARADOR'].'install.host'.$_SYSTEM['SEPARADOR'].'index.php'); break;
	}
	
	switch($_REQUEST["opcao"]){
		case 'widget-iframe-add': $saida = ajax_widget_iframe_add(); break;
		case 'widget-iframe-edit': $saida = ajax_widget_iframe_edit(); break;
		case 'widget-iframe': $saida = ajax_widget_iframe(); break;
		case 'save': $saida = ajax_save(); break;
		case 'segmentos': $saida = ajax_segmentos(); break;
		case 'segmento-dados': $saida = ajax_segmento_dados(); break;
		case 'segmento-del': $saida = ajax_segmento_del(); break;
		case 'segmento-add': $saida = ajax_segmento_add(); break;
		case 'segmento-edit': $saida = ajax_segmento_edit(); break;
		case 'templates': $saida = ajax_templates(); break;
		case 'template-dados': $saida = ajax_template_dados(); break;
		case 'template-del': $saida = ajax_template_del(); break;
		case 'template-add': $saida = ajax_template_add(); break;
		case 'template-edit': $saida = ajax_template_edit(); break;
		case 'template-save': $saida = ajax_template_save(); break;
		case 'agenda-add': $saida = ajax_agenda_add(); break;
		case 'agendas': $saida = ajax_agendas(); break;
		case 'agenda-delete': $saida = ajax_agenda_delete(); break;
		case 'agenda-name': $saida = ajax_agenda_name(); break;
		case 'agenda-eventos': $saida = ajax_agenda_eventos(); break;
		case 'eventos-delete': $saida = ajax_eventos_delete(); break;
		case 'eventos-add': $saida = ajax_eventos_add(); break;
		case 'eventos-edit': $saida = ajax_eventos_edit(); break;
		case 'eventos-block': $saida = ajax_eventos_block(); break;
		case 'eventos-del': $saida = ajax_eventos_del(); break;
		case 'publish-page': $saida = ajax_publish_page(); break;
		case 'pagina-vars': $saida = ajax_pagina_vars(); break;
		case 'input-start-values': $saida = ajax_input_start_values(); break;
		case 'biblioteca_imagens_lista': $saida = ajax_biblioteca_imagens_lista(); break;
		case 'biblioteca_imagens_delete': $saida = ajax_biblioteca_imagens_delete(); break;
		case 'arquivos_lista': $saida = ajax_arquivos_lista(); break;
		case 'arquivos_delete': $saida = ajax_arquivos_delete(); break;
		case 'album-fotos-add': $saida = ajax_album_fotos_add(); break;
		case 'albuns-fotos': $saida = ajax_albuns_fotos(); break;
		case 'albuns-fotos-images': $saida = ajax_albuns_fotos_images(); break;
		case 'album-fotos-edit': $saida = ajax_albuns_fotos_edit(); break;
		case 'album-fotos-legenda-edit': $saida = ajax_albuns_fotos_legenda_edit(); break;
		case 'album-fotos-del': $saida = ajax_albuns_fotos_del(); break;
		case 'album-fotos-images-del': $saida = ajax_albuns_fotos_images_delete(); break;
		case 'album-fotos-data-edit': $saida = ajax_albuns_fotos_data_edit(); break;
		case 'banners-add': $saida = ajax_banners_add(); break;
		case 'banners': $saida = ajax_banners(); break;
		case 'banners-images': $saida = ajax_banners_images(); break;
		case 'banners-edit': $saida = ajax_banners_edit(); break;
		case 'banners-del': $saida = ajax_banners_del(); break;
		case 'banners-images-del': $saida = ajax_banners_images_delete(); break;
		case 'banners-order': $saida = ajax_banners_order(); break;
		case 'banners-data-edit': $saida = ajax_banners_data_edit(); break;
		case 'slide-show-add': $saida = ajax_slide_show_add(); break;
		case 'slides-show': $saida = ajax_slides_show(); break;
		case 'slide-show-images': $saida = ajax_slides_show_images(); break;
		case 'slide-show-edit': $saida = ajax_slides_show_edit(); break;
		case 'slide-show-del': $saida = ajax_slides_show_del(); break;
		case 'slide-show-images-del': $saida = ajax_slides_show_images_delete(); break;
		case 'player-musicas-add': $saida = ajax_player_musicas_add(); break;
		case 'players-musicas': $saida = ajax_players_musicas(); break;
		case 'player-musicas-mp3s': $saida = ajax_players_musicas_mp3s(); break;
		case 'player-musicas-edit': $saida = ajax_players_musicas_edit(); break;
		case 'player-musicas-del': $saida = ajax_players_musicas_del(); break;
		case 'player-musicas-mp3s-del': $saida = ajax_players_musicas_mp3s_delete(); break;
		case 'album-musicas-add': $saida = ajax_album_musicas_add(); break;
		case 'albuns-musicas': $saida = ajax_albuns_musicas(); break;
		case 'albuns-musicas-mp3s': $saida = ajax_albuns_musicas_mp3s(); break;
		case 'album-musicas-edit': $saida = ajax_albuns_musicas_edit(); break;
		case 'album-musicas-del': $saida = ajax_albuns_musicas_del(); break;
		case 'album-musicas-mp3s-del': $saida = ajax_albuns_musicas_mp3s_delete(); break;
		case 'foto-perfil': $saida = ajax_foto_perfil(); break;
		case 'help-texto': $saida = ajax_help_texto(); break;
		case 'favicon': $saida = ajax_favicon(); break;
		case 'pagina-parallax': $saida = ajax_pagina_parallax(); break;
		case 'pagina-menu-bolinha': $saida = ajax_pagina_menu_bolinha(); break;
		case 'pagina-menu-bolinhas-areas': $saida = ajax_pagina_menu_bolinha_areas(); break;
		case 'pagina-menu-bolinhas-layout': $saida = ajax_pagina_menu_bolinha_layout(); break;
		case 'pagina-add': $saida = ajax_pagina_add(); break;
		case 'paginas-mais-resultados': $saida = ajax_paginas_mais_resultados(); break;
		case 'pagina-mudar': $saida = ajax_pagina_mudar(); break;
		case 'paginas-trocar-pai': $saida = ajax_paginas_trocar_pai(); break;
		case 'paginas-voltar-pai': $saida = ajax_paginas_voltar_pai(); break;
		case 'pagina-edit': $saida = ajax_pagina_edit(); break;
		case 'pagina-delete': $saida = ajax_pagina_delete(); break;
		case 'servicos': $saida = ajax_servicos(); break;
		case 'servicos-html-list': $saida = ajax_servicos_html_list(); break;
		case 'formularios': $saida = ajax_formularios(); break;
		case 'formularios-add': $saida = ajax_formularios_add(); break;
		case 'formularios-edit': $saida = ajax_formularios_edit(); break;
		case 'formularios-del': $saida = ajax_formularios_del(); break;
		case 'formularios-campos': $saida = ajax_formularios_campos(); break;
		case 'formularios-campos-add': $saida = ajax_formularios_campos_add(); break;
		case 'formularios-campos-del': $saida = ajax_formularios_campos_del(); break;
		case 'formularios-campos-val': $saida = ajax_formularios_campos_val(); break;
		case 'formularios-campos-obrigatorio': $saida = ajax_formularios_campos_obrigatorio(); break;
		case 'formularios-campos-tipo': $saida = ajax_formularios_campos_tipo(); break;
		case 'formularios-campos-order': $saida = ajax_formularios_campos_order(); break;
		case 'formularios-campos-opcoes-add': $saida = ajax_formularios_campos_opcoes_add(); break;
		case 'formularios-campos-opcoes-edit': $saida = ajax_formularios_campos_opcoes_edit(); break;
		case 'formularios-campos-opcoes-del': $saida = ajax_formularios_campos_opcoes_del(); break;
		case 'formularios-campos-label-change': $saida = ajax_formularios_campos_label_change(); break;
		case 'formularios-vars': $saida = ajax_formularios_vars(); break;
		case 'menu-paginas-start': $saida = ajax_menu_paginas_start(); break;
		case 'menu-paginas-ler-conteudos': $saida = ajax_menu_paginas_ler_conteudos(); break;
		case 'menu-paginas-publicar-conteudos': $saida = ajax_menu_paginas_publicar_conteudos(); break;
		case 'contents': $saida = ajax_contents(); break;
		case 'contents-html-list': $saida = ajax_contents_html_list(); break;
		case 'multi-screen-change': $saida = ajax_multi_screen_change(); break;
		case 'multi-screen-reset-mobile': $saida = ajax_multi_screen_reset_mobile(); break;
		case 'posts-filter': $saida = ajax_posts_filter(); break;
		case 'posts-filter-html-list': $saida = ajax_posts_filter_html_list(); break;
		case 'conteiner-banners-add': $saida = ajax_conteiner_banners_add(); break;
		case 'conteiner-banners': $saida = ajax_conteiner_banners(); break;
		case 'conteiner-banners-images': $saida = ajax_conteiner_banners_images(); break;
		case 'conteiner-banners-edit': $saida = ajax_conteiner_banners_edit(); break;
		case 'conteiner-banners-del': $saida = ajax_conteiner_banners_del(); break;
		case 'conteiner-banners-images-del': $saida = ajax_conteiner_banners_images_delete(); break;
		case 'conteiner-banners-order': $saida = ajax_conteiner_banners_order(); break;
		case 'conteiner-banners-data-edit': $saida = ajax_conteiner_banners_data_edit(); break;
		case 'importar-pagina-b2make': $saida = ajax_importar_pagina_b2make(); break;
		case 'conteiner-areas-globais-add': $saida = ajax_conteiner_areas_globais_add(); break;
		case 'conteiner-areas-globais-edit': $saida = ajax_conteiner_areas_globais_edit(); break;
		case 'conteiner-areas-globais-del': $saida = ajax_conteiner_areas_globais_del(); break;
		case 'conteiner-areas-globais-load': $saida = ajax_conteiner_areas_globais_load(); break;
		case 'google-fonts-change': $saida = ajax_google_fonts_change(); break;
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
	} else if($_REQUEST[css]){
		css();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'testes':						$saida = site_testes(); break;
			case 'ftp-manual-password':			$saida = site_ftp_manual_password(); break;
			default: 							$saida = site_layout();
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