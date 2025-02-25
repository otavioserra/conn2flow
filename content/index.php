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

$_VERSAO_MODULO				=	'1.3.0';
$_LOCAL_ID					=	"content";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_CONTENT			=	true;
$_INCLUDE_PUBLISHER			=	true;
$_INCLUDE_PHPQUERY			=	true;
$_INCLUDE_SITE				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	".";
$_MENU_LATERAL_GESTOR		=	true;
$_HTML['LAYOUT']			=	$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

$_HTML['titulo'] 						= 	$_HTML['titulo']."Conteúdos.";
$_HTML['variaveis']['titulo-modulo']	=	'Conteúdos';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'];

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"../design/jquery-file-upload/jquery.iframe-transport.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"../design/jquery-file-upload/jquery.fileupload.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"../design/jquery-file-upload/jquery.fileupload.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

if(!$_SESSION[$_SYSTEM['ID']."tinymce-version"]){
	$_SESSION[$_SYSTEM['ID']."tinymce-version"] = time();
}

$_HTML['css'] .= "	<link href=\"../design/?css=sim&opcao=tinymce&only-fonts=sim&v=".$_SESSION[$_SYSTEM['ID']."tinymce-version"]."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'site_conteudos';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'site_conteudos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Conteúdos';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

$_B2MAKE_CONTENT_CAMPOS = Array(
	Array(
		'widget' => 'texto',
		'type' => 'texto-curto',
		'name' => 'Texto Curto',
	),
	Array(
		'widget' => 'texto',
		'type' => 'texto-longo',
		'name' => 'Texto Longo',
	),
	Array(
		'widget' => 'imagem',
		'type' => 'imagem',
		'name' => 'Imagem',
	),
	Array(
		'widget' => 'texto-complexo',
		'type' => 'texto-complexo',
		'name' => 'Texto Complexo',
	),
);

// B2make

function appendHTML(DOMNode $parent, $source) {
    $tmpDoc = new DOMDocument();
    $tmpDoc->loadHTML($source);
    foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
        $node = $parent->ownerDocument->importNode($node);
        $parent->appendChild($node);
    }
}

function campos_identificador_unico($id,$num,$id_site_conteudos_campos,$id_usuario){
	$site_conteudos_campos = banco_select
	(
		"id_site_conteudos_campos"
		,
		"site_conteudos_campos",
		"WHERE id='".($num ? $id.'-'.$num : $id)."'"
		." AND id_usuario='".$id_usuario."'"
		.($id_site_conteudos_campos?" AND id_site_conteudos_campos!='".$id_site_conteudos_campos."'":"")
	);
	
	if($site_conteudos_campos){
		return campos_identificador_unico($id,$num + 1,$id_site_conteudos_campos,$id_usuario);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function campos_criar_identificador($id,$id_usuario,$id_site_conteudos_campos = false){
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
		
		return campos_identificador_unico($id,$num,$id_site_conteudos_campos,$id_usuario);
	} else {
		return campos_identificador_unico($id,0,$id_site_conteudos_campos,$id_usuario);
	}
}

function site_conteudo_publicar_pagina($params = false){
	global $_SYSTEM;
	global $_DEBUG_CONT;
	global $_B2MAKE_URL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'mobile',
		))
		,
		"host",
		"WHERE id_usuario='".$id_usuario."'"
	);
	
	if($host[0]['mobile']){
		$mobile = true;
	}
	
	$site_conteudos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'texto',
			'imagem_path',
			'imagem_path_mini',
			'versao',
			'versao_tipo',
			'data_modificacao',
		))
		,
		"site_conteudos",
		"WHERE id_site_conteudos='".$id_site_conteudos."'"
		." AND id_usuario='".$id_usuario."'"
	);
	
	if($id_site_conteudos_tipos && $id_site_conteudos_tipos != '-1'){
		$site_conteudos_tipos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
				'versao',
			))
			,
			"site_conteudos_tipos",
			"WHERE id_usuario='".$id_usuario."'"
			." AND id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
		);
		
		$site = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'html_mobile',
			))
			,
			"site",
			"WHERE id_site='".$site_conteudos_tipos[0]['id_site']."'"
		);
	} else {
		$site = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'html_mobile',
			))
			,
			"site",
			"WHERE id_usuario='".$id_usuario."'"
			." AND id='pagina-de-conteudos'"
		);
	}
	
	if(!$pagina_add){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'html_mobile',
			))
			,
			"site",
			"WHERE id_site='".$id_site."'"
		);
		
		if($mobile && !$resultado[0]['html_mobile']){
			$resultado[0]['html_mobile'] = $site[0]['html_mobile'];
		}
	} else {
		if($id_site_conteudos_tipos){
			$resultado = $site;
		} else {
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'html',
					'html_mobile',
				))
				,
				"site",
				"WHERE id_usuario='".$id_usuario."'"
				." AND id='pagina-de-conteudos'"
			);
		}
	}
	
	if($id_site_conteudos_tipos){
		$site_conteudos_campos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_campos',
				'id',
				'widget',
			))
			,
			"site_conteudos_campos",
			"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
		);
		
		$site_conteudos_site_conteudos_campos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_campos',
				'id_site_conteudos_tipos',
				'valor',
			))
			,
			"site_conteudos_site_conteudos_campos",
			"WHERE id_site_conteudos='".$id_site_conteudos."'"
		);
	}
	
	if($resultado){
		if($site_conteudos){
			// Publicar Lista de Conteúdos
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'url_files',
					'url_mobile',
					'dominio_proprio',
				))
				,
				"host",
				"WHERE id_usuario='".$id_usuario."'"
				." AND atual IS TRUE"
			);
			
			if($host[0]['dominio_proprio']){
				$url_arr = parse_url($host[0]['dominio_proprio']);
				$host[0]['dominio_proprio'] = ($url_arr['host'] ? $url_arr['host'] : $url_arr['path']);
			}
			
			$path = site_pagina_diretorio($id_site,false,true);
			$url = ($host[0]['dominio_proprio'] ? '//'.$host[0]['dominio_proprio'].'/' : $host[0]['url']) . $path;
			$url_mobile = '//'.rtrim($host[0]['url_mobile'], '/').'/'.$path;
			$url_files = $host[0]['url_files'];
			
			//$url_imagem = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
			if($site_conteudos[0]['imagem_path_mini'])$url_imagem = $_B2MAKE_URL . $site_conteudos[0]['imagem_path_mini'] . '?v='. $site_conteudos[0]['versao'];
			if($site_conteudos[0]['imagem_path'])$url_imagem_2 = $_B2MAKE_URL . $site_conteudos[0]['imagem_path'] . '?v='. $site_conteudos[0]['versao'];
			
			// Publicar Página do Conteúdos
			
			$htmls[] = Array(
				'html' => $resultado[0]['html'],
				'tipo' => 'temp',
				'mobile' => false,
			);
			$htmls[] = Array(
				'html' => $resultado[0]['html'],
				'tipo' => 'modelo',
				'mobile' => false,
			);
			
			if($mobile && $resultado[0]['html_mobile']){
				$htmls[] = Array(
					'html' => $resultado[0]['html_mobile'],
					'tipo' => 'temp',
					'mobile' => true,
				);
				$htmls[] = Array(
					'html' => $resultado[0]['html_mobile'],
					'tipo' => 'modelo',
					'mobile' => true,
				);
			}
			
			foreach($htmls as $html_aux){
				$google_fonts_loaded = false;
				
				phpQuery::newDocumentHTML($html_aux['html']);
				
				foreach(pq('.b2make-widget') as $el){
					$widget = pq($el);
					
					$font_name = false;
					if($widget->attr('data-google-font')){
						$font_name = $widget->attr('data-font-family');
					}
					
					$found = false;
					if($font_name){
						for($i=0;$i<count($google_fonts_loaded);$i++){
							if($google_fonts_loaded[$i] == $font_name){
								$found = true;
								break;
							}
						}
						
						if(!$found){
							$google_fonts_loaded[] = $font_name;
						}
					}
					
					switch($widget->attr('data-type')){
						case 'texto':
							$mudar_valor = false;
							
							switch($widget->attr('data-marcador')){
								case '@conteudo#nome':
									$mudar_valor = $site_conteudos[0]['nome'];
								break;
								case '@conteudo#texto':
									$mudar_valor = $site_conteudos[0]['texto'];
								break;
							}
							
							if($mudar_valor){
								$mudar_valor = preg_replace('/\r\n|\r|\n/i', '<br>', $mudar_valor);
								$mudar_valor = preg_replace('/  /i', '&nbsp;&nbsp;', $mudar_valor);
								$mudar_valor = preg_replace('/<br>$/i', '', $mudar_valor);
								
								$widget->find('.b2make-texto-table')->find('.b2make-texto-cel')->html($mudar_valor);
							}
						break;
						case 'imagem':
							$mudar_valor = false;
							
							switch($widget->attr('data-marcador')){
								case '@conteudo#imagem':
									$mudar_valor = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
									if($site_conteudos[0]['imagem_path'])$mudar_valor = $_B2MAKE_URL . $site_conteudos[0]['imagem_path'] . '?v='. $site_conteudos[0]['versao'];
								break;
							}
							
							if($mudar_valor)
								$widget->find('img')->attr('src',$mudar_valor);
						break;
						case 'iframe':
							$iframe = urldecode($widget->attr('data-iframe-code'));
							
							$widget->find('.b2make-widget-out')->html($iframe);
							
							//$widget->attr('data-iframe-code',false);
						break;
						case 'breadcrumbs':
							$widget->attr('data-id',$id_site);
						break;
					}
					
					if($id_site_conteudos_tipos){
						if($site_conteudos_campos)
						foreach($site_conteudos_campos as $site_conteudos_campo){
							$valor = false;
							
							if($site_conteudos_campo['widget'] == $widget->attr('data-type')){
								
								if($site_conteudos_site_conteudos_campos)
								foreach($site_conteudos_site_conteudos_campos as $site_conteudos_site_conteudos_campo){
									if($site_conteudos_campo['id_site_conteudos_campos'] == $site_conteudos_site_conteudos_campo['id_site_conteudos_campos']){
										$valor = $site_conteudos_site_conteudos_campo['valor'];
										break;
									}
								}
								
								switch($widget->attr('data-type')){
									case 'texto':
										$mudar_valor = '';
										$mudar_valor_flag = false;
										
										switch($widget->attr('data-marcador')){
											case '@'.$site_conteudos_campo['id'].'#':
												$mudar_valor = $valor;
												$mudar_valor_flag = true;
											break;
										}
										
										if($mudar_valor_flag){
											$mudar_valor = preg_replace('/\r\n|\r|\n/i', '<br>', $mudar_valor);
											$mudar_valor = preg_replace('/  /i', '&nbsp;&nbsp;', $mudar_valor);
											$mudar_valor = preg_replace('/<br>$/i', '', $mudar_valor);
											
											$widget->find('.b2make-texto-table')->find('.b2make-texto-cel')->html($mudar_valor);
										}
									break;
									case 'texto-complexo':
										$mudar_valor = '';
										$mudar_valor_flag = false;
										
										switch($widget->attr('data-marcador')){
											case '@'.$site_conteudos_campo['id'].'#':
												$mudar_valor = $valor;
												$mudar_valor_flag = true;
											break;
										}
										
										if($mudar_valor_flag){
											$widget->find('.b2make-widget-out')->find('.b2make-texto-complexo')->html($mudar_valor);
										}
									break;
									case 'imagem':
										$mudar_valor = '';
										$mudar_valor_flag = false;
										
										switch($widget->attr('data-marcador')){
											case '@'.$site_conteudos_campo['id'].'#':
												$mudar_valor = $valor;
												$mudar_valor_flag = true;
											break;
										}
										
										if($mudar_valor_flag){
											if($mudar_valor){
												$widget->find('img')->attr('src',$url_files.$mudar_valor);
											} else {
												if($html_aux['tipo'] == 'temp'){
													$widget->remove();
												} else {
													$widget->find('img')->attr('src','//platform.b2make.com/design/images/b2make-album-sem-imagem.png');
												}
											}
										}
									break;
									
								}
							}
						}
					}
				}
				
				if($html_aux['mobile']){
					pq('#b2make-pagina-options')->attr('data-device','phone');
				}
				
				$html_processed_aux = phpQuery::getDocument()->htmlOuter();
				
				$google_fontes_aux = '';
				
				if($google_fonts_loaded){
					for($i=0;$i<count($google_fonts_loaded);$i++){
						$google_fontes_aux = $google_fontes_aux . ($google_fontes_aux ? '|' : '') . preg_replace('/ /i', '+', $google_fonts_loaded[$i]);
					}
				}
				
				if($html_aux['mobile']){
					$google_fontes_mobile = $google_fontes_aux;
					
					if($html_aux['tipo'] == 'temp'){
						$html_mobile_temp = $html_processed_aux;
					} else {
						$html_mobile = $html_processed_aux;
					}
				} else {
					$google_fontes = $google_fontes_aux;
					
					if($html_aux['tipo'] == 'temp'){
						$html_temp = $html_processed_aux;
					} else {
						$html = $html_processed_aux;
					}
				}
			}
			
			if($versao_tipo){
				banco_update
				(
					"versao_tipo='".$versao_tipo."'",
					"site_conteudos",
					"WHERE id_site_conteudos='".$id_site_conteudos."'"
					." AND id_usuario='".$id_usuario."'"
				);
			}
			
			banco_update
			(
				"html='".addslashes($html)."',".
				"html_mobile='".addslashes($html_mobile)."',".
				"google_fontes_mobile='".$google_fontes_mobile."',".
				"google_fontes='".$google_fontes."'",
				"site",
				"WHERE id_site='".$id_site."'"
			);
			
			site_conteudo_publicar_ftp(Array(
				'html' => $html_temp,
				'google_fontes' => $google_fontes,
				'site_conteudo_nome' => $site_conteudos[0]['nome'],
				'id_site' => $id_site,
				'mudar_categoria' => $mudar_categoria,
				'id_site_conteudos' => $id_site_conteudos,
			));
			
			if($mobile && $html_mobile_temp){
				site_conteudo_publicar_ftp(Array(
					'html' => $html_mobile_temp,
					'google_fontes' => $google_fontes,
					'site_conteudo_nome' => $site_conteudos[0]['nome'],
					'id_site' => $id_site,
					'mudar_categoria' => $mudar_categoria,
					'mobile' => $mobile,
				));
			}
		} else {
			$_ALERTA = 'Esse serviço não pertence a sua id_usuario: '.$id_usuario;
		}	
	} else {
		$_ALERTA = 'Não existe site para o id_usuario: '.$id_usuario;
	}	
}

function site_conteudo_remover_pagina($params = false){
	global $_SYSTEM;
	global $_DEBUG_CONT;
	global $_B2MAKE_URL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$site_conteudos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site_conteudos",
		"WHERE id_site_conteudos='".$id_site_conteudos."'"
		." AND id_usuario='".$id_usuario."'"
	);
	
	if($site_conteudos){
		$id_site = $site_conteudos[0]['id_site'];
		
		// Publicar Lista de Conteúdos
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
				'dominio_proprio',
			))
			,
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS TRUE"
		);
		
		$path = site_pagina_diretorio($id_site,false,true);
		$url = $host[0]['url'] . $path;
		
		if($id_site)
		site_conteudo_remover_ftp(Array(
			'bloqueio' => $bloqueio,
			'id_site' => $id_site,
			'id_site_conteudos' => $id_site_conteudos,
		));
		
		publisher_sitemaps();
	} else {
		$_ALERTA = 'Esse conteudo não pertence a sua id_usuario: '.$id_usuario;
	}
}

function site_conteudo_publicar_ftp($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_HTML_META;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$site = site_publish_page(Array(
		'nao_fechar_ftp' => true,
		'id_site' => $id_site,
		'html' => $html,
		'google_fontes' => $google_fontes,
		'mobile' => $mobile,
		'layout_site' => $_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html',
	));
	
	switch($site['status']){
		case 'Ok':
			// =============== Ativar atualização serviços do host
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
				))
				,
				"host",
				"WHERE id_usuario='".$id_usuario."'"
			);
			
			$url = $host[0]['url'] . 'platform/conteudos/' . $id_site_conteudos;
			curl_post_async($url);
			
			// =================== Mudar categoria
			
			if($mudar_categoria){
				$identificador_site_antigo = $mudar_categoria['identificador_site_antigo'];
				$id_categoria_antigo = $mudar_categoria['id_categoria_antigo'];
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_site',
					))
					,
					"site_conteudos_categorias",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id_site_conteudos_categorias='".$id_categoria_antigo."'"
				);
				
				$id_site_categoria = $resultado[0]['id_site'];
				
				$path = site_pagina_diretorio($id_site_categoria,false,true);
				
				ftp_recursive_delete('/'.$path.$identificador_site_antigo);
			}
			
			ftp_fechar_conexao();
		break;
		case 'FtpNotConnected':
			
		break;
		case 'HtmlNull':
			
		break;
	}
}

function site_conteudo_remover_ftp($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_DEBUG_CONT;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'id',
			'publicado',
		))
		,
		"site",
		"WHERE id_site='".$id_site."'"
	);
	
	if($resultado){
		$filhos = site_pagina_filhos($id_site);
		
		$id = $resultado[0]['id'];
		$publicado = $resultado[0]['publicado'];
		$caminho = site_pagina_diretorio($id_site,false,true);
		
		$filhos[] = Array(
			'id' => $id,
			'id_site' => $id_site,
			'publicado' => $publicado,
			'caminho' => $caminho,
			'level' => 0,
		);
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-site-host'],
			'user' => $_SYSTEM['SITE']['ftp-site-user'],
			'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
		));
		
		if($_CONEXAO_FTP){
			
			$raizes .= ' '.ftp_pwd($_CONEXAO_FTP);
			
			ftp_pasv($_CONEXAO_FTP, true);
			
			$raiz = ftp_pwd($_CONEXAO_FTP);
			
			foreach($filhos as $filho){
				ftp_chdir($_CONEXAO_FTP,$raiz . $filho['caminho']);
				
				$files = ftp_nlist($_CONEXAO_FTP, ".");
				
				foreach($files as $file){
					if($file == '.' || $file == '..') continue;
					ftp_delete($_CONEXAO_FTP, $file);
				}
				
				ftp_cdup($_CONEXAO_FTP);
				
				ftp_rmdir($_CONEXAO_FTP, $raiz . $filho['caminho']);
				
				if(!$bloqueio)
				banco_delete
				(
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id_site='".$filho['id_site']."'"
				);
			}
			
			ftp_fechar_conexao();
			
			// =============== Ativar atualização serviços do host
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
				))
				,
				"host",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			$url = $host[0]['url'] . 'platform/conteudos/' . $id_site_conteudos;
			curl_post_async($url);
		} else if($_B2MAKE_PAGINA_LOCAL){
			foreach($filhos as $filho){
				if(!$bloqueio)
				banco_delete
				(
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id_site='".$filho['id_site']."'"
				);
			}
		}
	}
}

function site_conteudo_pagina_add($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_host = $_SESSION[$_SYSTEM['ID']."b2make-site"]['id_host'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site_conteudos_categorias",
		"WHERE id_site_conteudos_categorias='".$id_site_conteudos_categorias."'"
	);
	
	$pai_id = $resultado[0]['id_site'];
	
	if($pai_id){
		$id = site_criar_identificador($nome,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),false,$pai_id);
		
		$campos = null;
		
		$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_pai"; $campo_valor = $pai_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pagina_titulo"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site"
		);
		
		$id_site = banco_last_id();
		
		banco_update
		(
			"id_site='".$id_site."'",
			"site_conteudos",
			"WHERE id_site_conteudos='".$id_site_conteudos."'"
		);
		
		site_conteudo_publicar_pagina(Array(
			'id_site_conteudos' => $id_site_conteudos,
			'id_site' => $id_site,
			'pagina_add' => true,
			'id_site_conteudos_tipos' => $id_site_conteudos_tipos,
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	}
	
	return $saida;
}

function site_conteudo_pagina_edit($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site_conteudos_categorias",
		"WHERE id_site_conteudos_categorias='".$id_site_conteudos_categorias."'"
	);
	
	$pai_id = $resultado[0]['id_site'];
	
	if($id_site){
		if($mudar_categoria){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id',
				))
				,
				"site",
				"WHERE id_site='".$id_site."'"
			);
			
			$mudar_categoria['identificador_site_antigo'] = $resultado[0]['id'];
		}
		
		$id = site_criar_identificador($nome,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),$id_site,$pai_id);
		
		$campo_tabela = "site";
		$campo_tabela_extra = "WHERE id_site='".$id_site."'";
		
		if($mudar_categoria){$campo_nome = "id_site_pai"; $campo_valor = $pai_id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";}
		
		$campo_nome = "nome"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "pagina_titulo"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "id"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "data_modificacao"; $campo_valor = $nada; $editar[$campo_tabela][] = $campo_nome."=NOW()";
		
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
		
		site_conteudo_publicar_pagina(Array(
			'id_site_conteudos' => $id_site_conteudos,
			'id_site' => $id_site,
			'mudar_categoria' => $mudar_categoria,
			'id_site_conteudos_tipos' => $id_site_conteudos_tipos,
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	}
	
	return $saida;
}

function site_conteudo_pagina_excluir($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$site_conteudos_site_conteudos_campos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_conteudos_campos',
			'file',
		))
		,
		"site_conteudos_site_conteudos_campos",
		"WHERE id_site_conteudos='".$id_site_conteudos."'"
	);
	
	if($site_conteudos_site_conteudos_campos)
	foreach($site_conteudos_site_conteudos_campos as $scc){
		$site_conteudos_campos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'widget',
			))
			,
			"site_conteudos_campos",
			"WHERE id_site_conteudos_campos='".$scc['id_site_conteudos_campos']."'"
		);
		
		if($site_conteudos_campos[0]['widget'] == 'imagem'){
			remover_arquivo_conteudos_campos(Array(
				'file' => $scc['file'],
			));
		}
	}
	
	if($_CONEXAO_FTP)ftp_fechar_conexao();
	
	banco_delete
	(
		"site_conteudos_site_conteudos_campos",
		"WHERE id_site_conteudos='".$id_site_conteudos."'"
	);
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site_conteudos",
		"WHERE id_usuario='".$id_usuario."'"
		." AND id_site_conteudos='".$id_site_conteudos."'"
	);
	
	$id_site = $site[0]['id_site'];
	
	if($id_site){
		site_conteudo_remover_pagina(Array(
			'id_site_conteudos' => $id_site_conteudos,
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	}
}

function site_conteudo_pagina_bloqueio($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site_conteudos",
		"WHERE id_site_conteudos='".$id_site_conteudos."'"
		." AND id_usuario='".$id_usuario."'"
	);
	
	$id_site = $resultado[0]['id_site'];
	
	switch($status){
		case 'A':
			site_conteudo_publicar_pagina(Array(
				'id_site_conteudos' => $id_site_conteudos,
				'id_site' => $id_site,
			));
		break;
		case 'B':
			site_conteudo_remover_pagina(Array(
				'id_site_conteudos' => $id_site_conteudos,
				'bloqueio' => true,
			));
		break;
		
	}
}

function categoria_principal($id = false,$id_pai = false,$nivel = 0){
	global $_SYSTEM;
	global $_LISTA;
	global $_CATEGORIAS_VAZIO;
	global $_CATEGORIAS_SELECTED_VALUE;
	global $_CATEGORIAS_SELECTED_TEXT;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_site_conteudos_categorias',
		))
		,
		"site_conteudos_categorias",
		"WHERE id_usuario='".$id_usuario."'"
		.($id_pai ? " AND id_site_conteudos_categorias_pai='".$id_pai."'" : " AND id_site_conteudos_categorias_pai IS NULL")
		." AND status!='D'"
		." ORDER BY nome ASC"
	);
	
	if($nivel > 0){
		for($i=1;$i<$nivel;$i++){
			$sep .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$sep .= '&#9492;&#9472;';
	}
	
	
	if($resultado){
		$_CATEGORIAS_VAZIO = false;
		
		foreach($resultado as $res){
			if(!$_CATEGORIAS_SELECTED_VALUE){
				$_CATEGORIAS_SELECTED_VALUE = $res['id_site_conteudos_categorias'];
				$_CATEGORIAS_SELECTED_TEXT = $sep.$res['nome'];
			}
			
			if($res['id_site_conteudos_categorias'] == $id){
				$_CATEGORIAS_SELECTED_VALUE = $res['id_site_conteudos_categorias'];
				$_CATEGORIAS_SELECTED_TEXT = $sep.$res['nome'];
			}
			
			$options[] = Array(
				'value' => $res['id_site_conteudos_categorias'],
				'text' => $sep.$res['nome'],
			);
			
			$options_filho = categoria_principal($id,$res['id_site_conteudos_categorias'],($nivel+1));
			
			if($options_filho) $options = array_merge($options,$options_filho);
		}
	}
	
	if(!$id_pai){
		$select = componentes_select(Array(
			'input_name' => 'categoria-principal',
			'input_params_extra' => ' id="categoria-principal"',
			'selected_value' => $_CATEGORIAS_SELECTED_VALUE,
			'selected_text' => $_CATEGORIAS_SELECTED_TEXT,
			'unselected_value' => $_CATEGORIAS_SELECTED_VALUE,
			'unselected_text' => $_CATEGORIAS_SELECTED_TEXT,
			'options' => $options,
		));
		
		return $select;
	} else {
		return $options;
	}
}

function tags($id = false,$id_pai = false,$nivel = 0){
	global $_SYSTEM;
	global $_LISTA;
	global $_CATEGORIAS_VAZIO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_site_conteudos_tags',
		))
		,
		"site_conteudos_tags",
		"WHERE id_usuario='".$id_usuario."'"
		.($id_pai ? " AND id_site_conteudos_tags_pai='".$id_pai."'" : " AND id_site_conteudos_tags_pai='0'")
		." AND status!='D'"
		." ORDER BY nome ASC"
	);
	
	if($nivel > 0){
		for($i=1;$i<$nivel;$i++){
			$sep .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$sep .= '&#9492;&#9472;';
	}
	
	if($resultado){
		$_CATEGORIAS_VAZIO = false;
		
		foreach($resultado as $res){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos',
				))
				,
				"site_conteudos_tags_site_conteudos",
				"WHERE id_site_conteudos_tags='".$res['id_site_conteudos_tags']."'"
			);
			
			$checked = '';
			if($resultado2)
			foreach($resultado2 as $res2){
				if($res2['id_site_conteudos'] == $id){
					$checked = ' checked="checked"';
				}
			}
			
			$options .= '<div class="tags-cont"'.($id_pai ? ' data-type="filho"' : ' data-type="pai"').($checked ? ' data-checked="sim"' : '').'><input class="tags-chk" type="checkbox" value="'.$res['id_site_conteudos_tags'].'"'.$checked.' name="tags-'.$res['id_site_conteudos_tags'].'"><span>'.$res['nome'].'</span></div>'."\n";
			
			$options .= tags($id,$res['id_site_conteudos_tags'],($nivel+1));
		}
	}
	
	if(!$id_pai){
		$select = '<div id="tags-options-cont">'."\n";
		$select .= $options;
		$select .= '</div>'."\n";
		
		return $select;
	} else {
		return $options;
	}
}

function tag_principal($id = false,$id_pai = false,$nivel = 0){
	global $_SYSTEM;
	global $_LISTA;
	global $_CATEGORIAS_VAZIO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_site_conteudos_tags',
		))
		,
		"site_conteudos_tags",
		"WHERE id_usuario='".$id_usuario."'"
		.($id_pai ? " AND id_site_conteudos_tags_pai='".$id_pai."'" : " AND id_site_conteudos_tags_pai='0'")
		." AND status!='D'"
		." ORDER BY nome ASC"
	);
	
	if($nivel > 0){
		for($i=1;$i<$nivel;$i++){
			$sep .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$sep .= '&#9492;&#9472;';
	}
	
	if($resultado){
		$_CATEGORIAS_VAZIO = false;
		
		foreach($resultado as $res){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos',
					'principal',
				))
				,
				"site_conteudos_tags_site_conteudos",
				"WHERE id_site_conteudos_tags='".$res['id_site_conteudos_tags']."'"
			);
			
			if($resultado2)
			foreach($resultado2 as $res2){
				if($res2['id_site_conteudos'] == $id){
					if($res2['principal']){
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}
					
					$options .= '<option value="'.$res['id_site_conteudos_tags'].'"'.$selected.'>'.($nivel == 0 ? '&#9500;' : '').$sep.$res['nome'].'</option>'."\n";
				}
			}
			
			
			$options .= tag_principal($id,$res['id_site_conteudos_tags'],($nivel+1));
		}
	}
	
	return $options;
}

function tipo($id = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_CATEGORIAS_VAZIO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_site_conteudos_tipos',
		))
		,
		"site_conteudos_tipos",
		"WHERE id_usuario='".$id_usuario."'"
		." ORDER BY nome ASC"
	);
	
	if($resultado)
	foreach($resultado as $res){
		if($res['id_site_conteudos_tipos'] == $id){
			$selected_value = $res['id_site_conteudos_tipos'];
			$selected_text = $res['nome'];
		}
		
		$options[] = Array(
			'value' => $res['id_site_conteudos_tipos'],
			'text' => $res['nome'],
		);
	}
	
	$select = componentes_select(Array(
		'input_name' => 'conteudo-tipo',
		'input_params_extra' => ' id="conteudo-tipo"',
		'selected_value' => $selected_value,
		'selected_text' => $selected_text,
		'unselected_value' => '-1',
		'unselected_text' => 'Padrão',
		'options' => $options,
	));
	
	return $select;

}

function tags_raiz($id = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_CATEGORIAS_VAZIO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_site_conteudos_tags',
		))
		,
		"site_conteudos_tags",
		"WHERE id_usuario='".$id_usuario."'"
		." AND id_site_conteudos_tags_pai='0'"
		." AND status!='D'"
		." ORDER BY nome ASC"
	);
	
	if($resultado)
	foreach($resultado as $res){
		if($res['id_site_conteudos_tags'] == $id){
			$selected_value = $res['id_site_conteudos_tags'];
			$selected_text = $res['nome'];
		}
		
		$options[] = Array(
			'value' => $res['id_site_conteudos_tags'],
			'text' => $res['nome'],
		);
	}
	
	$select = componentes_select(Array(
		'input_name' => 'tags-tipo',
		'input_params_extra' => ' id="tags-tipo"',
		'selected_value' => $selected_value,
		'selected_text' => $selected_text,
		'unselected_value' => '-1',
		'unselected_text' => 'Raiz',
		'options' => $options,
	));
	
	return $select;
}

function campos($type = false){
	global $_SYSTEM;
	global $_B2MAKE_CONTENT_CAMPOS;
	
	if($_B2MAKE_CONTENT_CAMPOS)
	foreach($_B2MAKE_CONTENT_CAMPOS as $res){
		if(!$unselected_value){
			$unselected_value = $res['type'];
			$unselected_text = $res['name'];
		}
		
		if($res['type'] == $type){
			$selected_value = $res['type'];
			$selected_text = $res['name'];
		}
		
		$options[] = Array(
			'value' => $res['type'],
			'text' => $res['name'],
		);
	}
	
	$select = componentes_select(Array(
		'input_name' => 'conteudo-campos',
		'input_params_extra' => ' id="conteudo-campos"',
		'selected_value' => $selected_value,
		'selected_text' => $selected_text,
		'unselected_value' => $unselected_value,
		'unselected_text' => $unselected_text,
		'options' => $options,
	));
	
	return $select;
}

function campos_html($params = false){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$resultados = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_conteudos_campos',
			'nome',
			'id',
			'tipo',
		))
		,
		"site_conteudos_campos",
		"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
	);
	
	$cel_nome = 'campos'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	if($resultados){
		foreach($resultados as $res){
			$cel_aux = $cel[$cel_nome];
			$valor = '';
			$class_extra = '';
			
			if($editar){
				$site_conteudos_site_conteudos_campos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'valor',
					))
					,
					"site_conteudos_site_conteudos_campos",
					"WHERE id_site_conteudos_campos='".$res['id_site_conteudos_campos']."'"
					." AND id_site_conteudos='".$id_site_conteudos."'"
					." AND id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
				);
				
				if($site_conteudos_site_conteudos_campos) $valor = $site_conteudos_site_conteudos_campos[0]['valor'];
			}
			
			$cel_tipo = $res['tipo'];
			
			$html_tipo = modelo_tag_val($pagina,'<!-- '.$cel_tipo.' < -->','<!-- '.$cel_tipo.' > -->');
			
			switch($cel_tipo){
				case 'imagem':
					if(!$host_dados){
						$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
						
						$host = banco_select_name
						(
							banco_campos_virgulas(Array(
								'url_files',
							))
							,
							"host",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND atual IS TRUE"
						);
						
						$host_dados = $host[0];
					}
					
					$html_tipo = modelo_var_troca($html_tipo,"#img-extra#",($valor ? ' data-img="'.$host_dados['url_files'].$valor.($versao?'?v='.$versao:'').'"' : ''));
				break;
				case 'texto-complexo':
					$_VARIAVEIS_JS['b2make_texto_complexo_ativo'] = true;
				break;
			}
			
			$html_tipo = modelo_var_troca($html_tipo,"#name#",'campos_'.$res['id']);
			$html_tipo = modelo_var_troca_tudo($html_tipo,"#id#",'campos_'.$res['id']);
			$html_tipo = modelo_var_troca($html_tipo,"#extra#",$extra);
			$html_tipo = modelo_var_troca($html_tipo,"#class-extra#",$class_extra);
			$html_tipo = modelo_var_troca($html_tipo,"#valor#",$valor);
			$html_tipo = modelo_var_troca($html_tipo,"#campo-nome#",$res['nome']);
			
			$cel_aux = modelo_var_troca($cel_aux,"#campo-nome#",$res['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#campo-valor#",$html_tipo);
			$cel_aux = modelo_var_troca($cel_aux,"#campo-marcador#",'@'.$res['id'].'#');
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#campo-id#",$res['id']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	}
	
	$site_conteudos_tipos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site_conteudos_tipos",
		"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
	);
	
	$id_site = $site_conteudos_tipos[0]['id_site'];
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'html',
			'html_mobile',
		))
		,
		"site",
		"WHERE id_site='".$id_site."'"
	);
	
	$pagina = modelo_var_troca($pagina,"#pagina-mestre-conteudo-tipo#",$site[0]['html']);
	$pagina = modelo_var_troca($pagina,"#pagina-mestre-conteudo-tipo-mobile#",$site[0]['html_mobile']);
	
	return $pagina;
}

// ================================= Upload ===============================

function upload_files(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	
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
	
	$upload_permissao = $_SESSION[$_SYSTEM['ID']."upload_permissao"];
	
	$_VARIAVEIS_JS['library_user'] = $upload_permissao['usuario'];
	$_VARIAVEIS_JS['library_id'] = $upload_permissao['session_id'];
}

// Funções do Sistema

function alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	
	switch ($nAlerta){
		//case 1:		$mensSaida	=	"";break;
		default:	$mensSaida	=	$nAlerta;
	}

	$_ALERTA = $mensSaida;
}

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_LISTA_2;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Todos' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => '../dashboard/',// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Início', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL, // link da opção
		'title' => 'Lista ' . $_LISTA['ferramenta'], // título da opção
		'img_coluna' => 2, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Lista', // Nome do menu
	);
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		if(operacao('bloquear')){
			$menu_principal[] = Array( // Opção: Bloquear
				'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
				'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 6, // Coluna background image
				'img_linha' => 1, // Linha background image
				'img_coluna2' => 7, // Coluna background image
				'img_linha2' => 1, // Linha background image
				'bloquear' => true, // Se eh botão de bloqueio
				'name' => 'Ativar/Desativar', // Nome do menu
			);
		}
		if(operacao('excluir')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => '#', // link da opção
				'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 8, // Coluna background image
				'img_linha' => 1, // Linha background image
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
				'name' => 'Excluir', // Nome do menu
			);
		}
		
	}
	
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Novo Conteúdo ' . $_LISTA['ferramenta'], // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Novo Conteúdo', // Nome do menu
		);
	}
	
	if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
		);
	}
	if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=editar&id=#id', // link da opção
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Editar', // Legenda
		);
	}
	if(operacao('bloquear')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
			'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 6, // Coluna background image
			'img_linha' => 1, // Linha background image
			'img_coluna2' => 5, // Coluna background image
			'img_linha2' => 1, // Linha background image
			'bloquear' => true, // Se eh botão de bloqueio
			'legenda' => 'Ativar/Desativar', // Legenda
		);
	}
	if(operacao('excluir')){
		$menu_opcoes[] = Array( // Opção: Excluir
			'url' => '#', // link da opção
			'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 7, // Coluna background image
			'img_linha' => 1, // Linha background image
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
			'legenda' => 'Excluir', // Legenda
		);
	}
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => $width, // OPCIONAL - Tamanho horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo/*  . ' ' . $_LISTA['ferramenta']  */, // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => ($_INTERFACE['sem_busca'] ? false : operacao('buscar')), // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_usuario='".$id_usuario."'", // Tabela extra
		'tabela_order' => $tabela_order, // Ordenação da tabela
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => $menu_principal,
		'menu_opcoes' => $menu_opcoes,
		'header_campos' => $header_campos,
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => 'Ação', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 22), // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'campos' => $campos,
		'outra_tabela' => $outra_tabela,
		'informacao_abaixo' => $informacao_abaixo,
		'informacao_acima' => $informacao_acima,
		'layout_pagina' => true,
		'layout_tag1' => '<!-- layout_pagina_2 < -->',
		'layout_tag2' => '<!-- layout_pagina_2 > -->',
		
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface());
}

function add(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_INTERFACE_OPCAO;
	global $_CATEGORIAS_VAZIO;
	global $_VARIAVEIS_JS;
	global $_HTML;
	
	$_HTML['css'] .= "<link href=\"css-interno.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
	
	if(!$_SESSION[$_SYSTEM['ID']."google_fonts_installed"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'google_fonts',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$_SESSION[$_SYSTEM['ID']."google_fonts_installed"] = $resultado2[0]['google_fonts'];
	}
	
	$_VARIAVEIS_JS['google_fonts_installed'] = $_SESSION[$_SYSTEM['ID']."google_fonts_installed"];
	
	$in_titulo = "Novo";
	$botao = "Salvar";
	$opcao = "add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form-2 < -->','<!-- form-2 > -->');
	
	if($_REQUEST['site']){
		$more_options = 'widget_id='.$_REQUEST['widget_id'];
	}
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$_CATEGORIAS_VAZIO = true;
	$categorias = categoria_principal();
	$tags = tags();
	$tipo = tipo(($_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"] ? $_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"] : false));
	$campos = campos();
	
	if($_CATEGORIAS_VAZIO){
		header('Location: category/?opcao=sem-categoria');
		exit;
	}
	
	$pagina = paginaTrocaVarValor($pagina,"#url",'');
	$pagina = paginaTrocaVarValor($pagina,"#data",'');
	$pagina = paginaTrocaVarValor($pagina,"#categoria",$categorias);
	$pagina = paginaTrocaVarValor($pagina,"#tags#",$tags);
	$pagina = paginaTrocaVarValor($pagina,"#tipo#",$tipo);
	$pagina = paginaTrocaVarValor($pagina,"#add-campos-select#",$campos);
	$pagina = paginaTrocaVarValor($pagina,"#nome#",$nome);
	$pagina = paginaTrocaVarValor($pagina,"#texto#",$texto);
	$pagina = paginaTrocaVarValor($pagina,"#imagem-principal-extra#",$imagem_path);
	$pagina = paginaTrocaVarValor($pagina,"#tags-raiz#",tags_raiz());
	$pagina = paginaTrocaVarValor($pagina,"#tags-principal-opt#",$tags_principal_opt);
	
	if(!$_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"] || $_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"] == '-1'){
		$cel_nome = 'add-campos'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		$cel_nome = 'campos'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	} else {
		$pagina = campos_html(Array(
			'pagina' => $pagina,
			'id_site_conteudos_tipos' => $_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"],
		));
	}
	
	if($_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"]){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'campo_imagem_excluido',
				'campo_texto_excluido',
			))
			,
			"site_conteudos_tipos",
			"WHERE id_site_conteudos_tipos='".$_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"]."'"
		);
		
		if($resultado[0]['campo_imagem_excluido']){$cel_nome = 'campo-imagem'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');}
		if($resultado[0]['campo_texto_excluido']){$cel_nome = 'campo-texto'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');}
	}
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	$pagina = paginaTrocaVarValor($pagina,"#more_options",$more_options);
	
	$cel_nome = 'campos-tipos'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE['sem_busca'] = true;
	$_INTERFACE_OPCAO = 'add';
	
	return interface_layout(parametros_interface());
}

function add_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	global $_CONEXAO_FTP;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$b2make_site = $_SESSION[$_SYSTEM['ID']."b2make-site"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	banco_conectar();
	
	$campo_nome = "id_site_conteudos_categorias"; $post_nome = 'categoria-principal'; 		if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "id_site_conteudos_tipos"; $post_nome = 'conteudo-tipo'; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "id_host"; $campo_valor = $b2make_site['id_host']; 						$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 								$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "nome"; $post_nome = $campo_nome; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "texto"; $post_nome = $campo_nome; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 										$campos[] = Array($campo_nome,'A');
	$campo_nome = "versao"; $post_nome = $campo_nome; 										$campos[] = Array($campo_nome,'1');
	$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 									$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 								$campos[] = Array($campo_nome,$campo_valor,true);
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$id_site_conteudos = banco_last_id();
	
	if($_REQUEST['conteudo-tipo'] != '-1'){
		$id_site_conteudos_tipos = $_REQUEST['conteudo-tipo'];
		
		$site_conteudos_campos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_campos',
				'id',
				'widget',
			))
			,
			"site_conteudos_campos",
			"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		if($site_conteudos_campos){
			foreach($site_conteudos_campos as $res){
				if($_REQUEST['campos_'.$res['id']] || $_FILES['campos_'.$res['id']]['size'] > 0){
					$campos = null;
					
					$campo_nome = "id_site_conteudos"; $campo_valor = $id_site_conteudos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_site_conteudos_campos"; $campo_valor = $res['id_site_conteudos_campos']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_site_conteudos_tipos"; $campo_valor = $id_site_conteudos_tipos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					switch($res['widget']){
						case 'imagem':
							$imagem = guardar_arquivo_conteudo_campos(Array(
								'uploaded' => $_FILES['campos_'.$res['id']],
								'tipo' => 'imagem',
							));
							
							if($imagem['status'] == 'Ok'){
								$campo_nome = "valor"; $campo_valor = $imagem['imagem']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "mini"; $campo_valor = $imagem['mini']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "file"; $campo_valor = $imagem['file']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome"; $campo_valor = $imagem['nome']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								
							}
						break;
						default:
							$campo_nome = 'valor'; $post_nome = 'campos_'.$res['id']; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
					}
					
					banco_insert_name
					(
						$campos,
						"site_conteudos_site_conteudos_campos"
					);
				}
			}
			
			if($_CONEXAO_FTP)ftp_fechar_conexao();
		}
	}
	
	if($_REQUEST['imagem-principal-input-change']) guardar_arquivo($_REQUEST['imagem-principal-input-change'],'imagem','imagem_path',$id_site_conteudos);
	
	if($_REQUEST['tag-principal-sel']){
		$tag_principal = $_REQUEST['tag-principal-sel'];
	}
	
	if($_REQUEST)
	foreach($_REQUEST as $key => $val){
		if(preg_match('/tags\-/', $key) > 0){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tags',
				))
				,
				"site_conteudos_tags",
				"WHERE id_usuario='".$id_usuario."'"
				." AND id_site_conteudos_tags='".$val."'"
			);
			
			if($resultado){
				$campos = null;
				
				$campo_nome = "id_site_conteudos"; $campo_valor = $id_site_conteudos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_site_conteudos_tags"; $campo_valor = $val; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				if($val == $tag_principal){$campo_nome = "principal"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);}
				
				banco_insert_name
				(
					$campos,
					"site_conteudos_tags_site_conteudos"
				);
			}
		}
	}
	
	site_conteudo_pagina_add(Array(
		'nome' => $_POST['nome'],
		'id_site_conteudos' => $id_site_conteudos,
		'id_site_conteudos_categorias' => $_REQUEST['categoria-principal'],
		'id_site_conteudos_tipos' => $id_site_conteudos_tipos,
	));
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	if($_REQUEST['more_options']){
		header('Location: ../design/?'.$_REQUEST['more_options']);
	} else {
		header('Location: ./?opcao=editar&id='.$id_site_conteudos);
	}
}

function editar($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_INTERFACE;
	global $_VARIAVEIS_JS;
	global $_HTML;
	
	$_HTML['css'] .= "<link href=\"css-interno.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
	
	if(!$_SESSION[$_SYSTEM['ID']."google_fonts_installed"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'google_fonts',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$_SESSION[$_SYSTEM['ID']."google_fonts_installed"] = $resultado2[0]['google_fonts'];
	}
	
	$_VARIAVEIS_JS['google_fonts_installed'] = $_SESSION[$_SYSTEM['ID']."google_fonts_installed"];
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form-2 < -->','<!-- form-2 > -->');
		
		banco_conectar();
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos',
				'id_site_conteudos_categorias',
				'id_site_conteudos_tipos',
				'nome',
				'texto',
				'imagem_path',
				'id_site',
				'data_criacao',
				'data_modificacao',
				'versao',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			." AND id_usuario='".$id_usuario."'"
			." AND status!='D'"
		);
		
		if($tabela){
			// ================================= Local de Edição ===============================
			// Pegue os campos da interface e campos_guardar aqui
			
			$campos_guardar = Array(
				'id_site_conteudos_categorias' => $tabela[0]['id_site_conteudos_categorias'],
				'id_site_conteudos_tipos' => $tabela[0]['id_site_conteudos_tipos'],
				'id_site' => $tabela[0]['id_site'],
				'nome' => $tabela[0]['nome'],
				'texto' => $tabela[0]['texto'],
				'imagem_path' => $tabela[0]['imagem_path'],
			);
			
			$remover = '<div><a href="#link#"><img src="../images/icons/db_remove.png" alt="Remover" width="32" height="32" border="0" title="Clique para remover esse ítem" /></a></div>';
			
			$tabela[0]['texto'] = preg_replace('/\r\n/i', '&#13;&#10;', $tabela[0]['texto']);
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'https',
					'dominio_proprio',
				))
				,
				"host",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$path = site_pagina_diretorio($tabela[0]['id_site'],false,true);
			
			if($tabela[0]['data_criacao'] || $tabela[0]['data_modificacao'])
				$data = 'Última modificação: '.data_hora_from_datetime_to_text($tabela[0]['data_modificacao']).' | '.( $tabela[0]['data_criacao'] ? 'Data criação: '.data_hora_from_datetime_to_text($tabela[0]['data_criacao']) : '');
			
			$url = ($host[0]['https'] ? preg_replace('/http:/i', 'https:', $host[0]['url']):preg_replace('/https:/i', 'http:', $host[0]['url'])) . $path;
			if($host[0]['dominio_proprio'])$dominio_proprio = ($host[0]['https'] ? 'https:':'http:').'//' . $host[0]['dominio_proprio'] . '/' . $path;
			
			$pagina = paginaTrocaVarValor($pagina,'#url','<a href="'.$url.'" target="_blank">'.$url.'</a>'.($dominio_proprio?'<br><a href="'.$dominio_proprio.'" target="_blank">'.$dominio_proprio.'</a> OBS: É necessário que o seu domínio esteja configurado corretamente para funcionar esta opção. Caso não funcione, é necessário entrar em contato com o suporte para saber como proceder.':''));
			$pagina = paginaTrocaVarValor($pagina,'#data',$data);
			$pagina = paginaTrocaVarValor($pagina,'#categoria',categoria_principal($tabela[0]['id_site_conteudos_categorias']));
			$pagina = paginaTrocaVarValor($pagina,'#tags#',tags($tabela[0]['id_site_conteudos']));
			$pagina = paginaTrocaVarValor($pagina,'#tipo#',tipo($tabela[0]['id_site_conteudos_tipos']));
			$pagina = paginaTrocaVarValor($pagina,"#tags-raiz#",tags_raiz());
			$pagina = paginaTrocaVarValor($pagina,'#tags-principal-opt#',tag_principal($tabela[0]['id_site_conteudos']));
			$pagina = paginaTrocaVarValor($pagina,'#add-campos-select#',campos());
			$pagina = paginaTrocaVarValor($pagina,'#nome#',$tabela[0]['nome']);
			$pagina = paginaTrocaVarValor($pagina,'#texto#',$tabela[0]['texto']);
			$pagina = paginaTrocaVarValor($pagina,'#imagem-principal-extra#',($tabela[0]['imagem_path'] ? ' data-img="'.path_com_versao_arquivo($tabela[0]['imagem_path']).'"' : ''));
			
			if(!$tabela[0]['id_site_conteudos_tipos'] || $tabela[0]['id_site_conteudos_tipos'] == '-1'){
				$cel_nome = 'add-campos'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				$cel_nome = 'campos'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			} else {
				$pagina = campos_html(Array(
					'pagina' => $pagina,
					'id_site_conteudos_tipos' => $tabela[0]['id_site_conteudos_tipos'],
					'editar' => true,
					'id_site_conteudos' => $id,
					'versao' => $tabela[0]['versao'],
				));
			}
			
			if($tabela[0]['id_site_conteudos_tipos']){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'campo_imagem_excluido',
						'campo_texto_excluido',
					))
					,
					"site_conteudos_tipos",
					"WHERE id_site_conteudos_tipos='".$tabela[0]['id_site_conteudos_tipos']."'"
				);
				
				if($resultado[0]['campo_imagem_excluido']){$cel_nome = 'campo-imagem'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');}
				if($resultado[0]['campo_texto_excluido']){$cel_nome = 'campo-texto'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');}
			}
			
			// ======================================================================================
			
			banco_fechar_conexao();
			
			campos_antes_guardar($campos_guardar);
			
			$in_titulo = $param ? "Visualizar" : "Editar";
			$botao = "Salvar";
			$opcao = "editar_base";
			
			if($_REQUEST['site']){
				$more_options = 'widget_id='.$_REQUEST['widget_id'];
			}
			
			$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
			$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
			$pagina = paginaTrocaVarValor($pagina,"#id",$id);
			$pagina = paginaTrocaVarValor($pagina,"#more_options",$more_options);
			
			$cel_nome = 'campos-tipos'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			$_INTERFACE_OPCAO = 'editar';
			$_INTERFACE['local'] = 'conteudo';
			$_INTERFACE['informacao_titulo'] = $in_titulo;
			$_INTERFACE['informacao_tipo'] = $tipo;
			$_INTERFACE['informacao_id'] = $id;
			$_INTERFACE['inclusao'] = $pagina;
			$_INTERFACE['sem_busca'] = true;
		
			return interface_layout(parametros_interface());
		} else {
			return lista();
		}
	} else
		return lista();
}

function editar_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	global $_CONEXAO_FTP;
	global $_ALERTA;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	if($id){
		$campos_antes = campos_antes_recuperar();
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "id_site_conteudos_categorias"; $post_nome = 'categoria-principal'; if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$post_nome] . "'"; $mudar_categoria = Array('id_categoria_antigo'=>$campos_antes[$campo_nome]);}
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "texto"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "versao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=versao+1";}
		$campo_nome = "data_modificacao"; $editar['tabela'][] = $campo_nome."=NOW()";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
				." AND id_usuario='".$id_usuario."'"
			);
		}
		
		if($_REQUEST['imagem-principal-input-change']){guardar_arquivo($_REQUEST['imagem-principal-input-change'],'imagem','imagem_path',$id,$campos_antes['imagem_path']);}
		if($_REQUEST['imagem-principal-input-del']){remover_item(Array('id' => $id,'item' => 'imagem_path'));}
		
		if($_REQUEST['conteudo-tipo'] != '-1'){
			$id_site_conteudos_tipos = $_REQUEST['conteudo-tipo'];
			
			$site_conteudos_campos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_campos',
					'id',
					'widget',
				))
				,
				"site_conteudos_campos",
				"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
				." AND id_usuario='".$id_usuario."'"
			);
			
			if($site_conteudos_campos){
				foreach($site_conteudos_campos as $res){
					$site_conteudos_site_conteudos_campos = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_site_conteudos',
							'file',
						))
						,
						"site_conteudos_site_conteudos_campos",
						"WHERE id_site_conteudos='".$id."'"
						." AND id_site_conteudos_campos='".$res['id_site_conteudos_campos']."'"
						." AND id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
					);
					
					if(!$site_conteudos_site_conteudos_campos){
						if($_REQUEST['campos_'.$res['id']] || $_REQUEST['campos_'.$res['id'].'-input-change']){
							$campos = null;
							
							$campo_nome = "id_site_conteudos"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id_site_conteudos_campos"; $campo_valor = $res['id_site_conteudos_campos']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id_site_conteudos_tipos"; $campo_valor = $id_site_conteudos_tipos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							
							switch($res['widget']){
								case 'imagem':
									$imagem = guardar_arquivo_conteudo_campos(Array(
										'tmp_name' => $_REQUEST['campos_'.$res['id'].'-input-change'],
										'data' => $_REQUEST['campos_'.$res['id'].'-input-data'],
										'tipo' => 'imagem',
									));
									
									if($_REQUEST['campos_'.$res['id'].'-input-del']){remover_item(Array('id' => $id,'id2' => $res['id_site_conteudos_campos'],'id3' => $id_site_conteudos_tipos,'item' => 'imagem_path_conteudos'));}
									
									if($imagem['status'] == 'Ok'){
										$campo_nome = "valor"; $campo_valor = $imagem['imagem']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
										$campo_nome = "mini"; $campo_valor = $imagem['mini']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
										$campo_nome = "file"; $campo_valor = $imagem['file']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
										$campo_nome = "nome"; $campo_valor = $imagem['nome']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									}
								break;
								default:
									$campo_nome = 'valor'; $post_nome = 'campos_'.$res['id']; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
							}
							
							banco_insert_name
							(
								$campos,
								"site_conteudos_site_conteudos_campos"
							);
						}
					} else {
						$editar = false;$editar_sql = false;
						
						$campo_tabela = "site_conteudos_site_conteudos_campos";
						$campo_tabela_extra = "WHERE id_site_conteudos='".$id."'"
						." AND id_site_conteudos_campos='".$res['id_site_conteudos_campos']."'"
						." AND id_site_conteudos_tipos='".$id_site_conteudos_tipos."'";
						
						switch($res['widget']){
							case 'imagem':
								if($_REQUEST['campos_'.$res['id'].'-input-del']){remover_item(Array('id' => $id,'id2' => $res['id_site_conteudos_campos'],'id3' => $id_site_conteudos_tipos,'item' => 'imagem_path_conteudos'));}
								
								if($_REQUEST['campos_'.$res['id'].'-input-change']){
									$imagem = guardar_arquivo_conteudo_campos(Array(
										'tmp_name' => $_REQUEST['campos_'.$res['id'].'-input-change'],
										'data' => $_REQUEST['campos_'.$res['id'].'-input-data'],
										'tipo' => 'imagem',
									));
									
									/* if($site_conteudos_site_conteudos_campos[0]['file'] != $imagem['file']){
										remover_arquivo_conteudos_campos(Array(
											'file' => $site_conteudos_site_conteudos_campos[0]['file'],
										));
									} */
									
									if($imagem['status'] == 'Ok'){
										$campo_nome = "valor"; $campo_valor = $imagem['imagem']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
										$campo_nome = "mini"; $campo_valor = $imagem['mini']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
										$campo_nome = "file"; $campo_valor = $imagem['file']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
										$campo_nome = "nome"; $campo_valor = $imagem['nome']; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
									}
								}
							break;
							default:
								$campo_nome = "valor"; $campo_valor = $_REQUEST['campos_'.$res['id']]; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
						}
						
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
				}
			}
			
			if($_CONEXAO_FTP)ftp_fechar_conexao();
		}
		
		$site_conteudos_tags_site_conteudos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tags',
				'principal',
			))
			,
			"site_conteudos_tags_site_conteudos",
			"WHERE id_site_conteudos='".$id."'"
		);
		
		if($_REQUEST['tag-principal-sel']){
			$tag_principal = $_REQUEST['tag-principal-sel'];
		}
		
		if($_REQUEST)
		foreach($_REQUEST as $key => $val){
			if(preg_match('/tags\-/', $key) > 0){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_site_conteudos_tags',
					))
					,
					"site_conteudos_tags",
					"WHERE id_usuario='".$id_usuario."'"
					." AND id_site_conteudos_tags='".$val."'"
				);
				
				if($resultado){
					$found = false;
					if($site_conteudos_tags_site_conteudos)
					foreach($site_conteudos_tags_site_conteudos as $res){
						if($val == $res['id_site_conteudos_tags']){
							$found = true;
							
							if($res['principal'] && $tag_principal != $res['id_site_conteudos_tags']){
								$tag_principal_mudou = true;
								$tag_principal_antes = $res['id_site_conteudos_tags'];
							}
							
							if($res['principal']){
								$tag_principal_achou = true;
							}
							
							break;
						}
					}
					
					if(!$found){
						$campos = null;
						
						$campo_nome = "id_site_conteudos"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_site_conteudos_tags"; $campo_valor = $val; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						
						banco_insert_name
						(
							$campos,
							"site_conteudos_tags_site_conteudos"
						);
					}
				}
				
				$site_conteudos_tags_ids[] = $val;
			}
		}
		
		if(!$tag_principal_achou){
			banco_update
			(
				"principal=1",
				"site_conteudos_tags_site_conteudos",
				"WHERE id_site_conteudos_tags='".$tag_principal."'"
				." AND id_site_conteudos='".$id."'"
			);
		}
		
		if($tag_principal_mudou){
			banco_update
			(
				"principal=NULL",
				"site_conteudos_tags_site_conteudos",
				"WHERE id_site_conteudos_tags='".$tag_principal_antes."'"
				." AND id_site_conteudos='".$id."'"
			);
			banco_update
			(
				"principal=1",
				"site_conteudos_tags_site_conteudos",
				"WHERE id_site_conteudos_tags='".$tag_principal."'"
				." AND id_site_conteudos='".$id."'"
			);
		}
		
		if($site_conteudos_tags_site_conteudos)
			foreach($site_conteudos_tags_site_conteudos as $res){
				
				$found = false;
				if($site_conteudos_tags_ids)
				foreach($site_conteudos_tags_ids as $res2){
					if($res['id_site_conteudos_tags'] == $res2){
						$found = true;
						break;
					}
				}
				
				if(!$found){
					banco_delete
					(
						"site_conteudos_tags_site_conteudos",
						"WHERE id_site_conteudos='".$id."'"
						." AND id_site_conteudos_tags='".$res['id_site_conteudos_tags']."'"
					);
				}
			}
		
		site_conteudo_pagina_edit(Array(
			'id_site_conteudos_categorias' => $_POST['categoria-principal'],
			'id_site_conteudos' => $id,
			'id_site' => $campos_antes['id_site'],
			'nome' => $_POST['nome'],
			'mudar_categoria' => $mudar_categoria,
			'id_site_conteudos_tipos' => $id_site_conteudos_tipos,
		));
		
		// ======================================================================================
		
		banco_fechar_conexao();
	}
	
	if($_REQUEST['more_options']){
		header('Location: ../design/?'.$_REQUEST['more_options']);
	} else {
		header('Location: ./?opcao=editar&id='.$id);
	}
}

function excluir(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		banco_conectar();
		banco_update
		(
			$_LISTA['tabela']['status']."='D'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		site_conteudo_pagina_excluir(Array(
			'id_site_conteudos' => $id
		));
		
		banco_fechar_conexao();
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function bloqueio(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	if($_GET["id"]){
		$id = $_GET["id"];
		$tipo = $_GET["tipo"];
		
		if($tipo == '1'){
			$status = 'B';
		} else {
			$status = 'A';
		}
		
		banco_conectar();
		banco_update
		(
			$_LISTA['tabela']['status']."='".$status."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		site_conteudo_pagina_bloqueio(Array(
			'id_site_conteudos' => $id,
			'status' => $status,
		));
		banco_fechar_conexao();
	}
	
	return lista();
}

function guardar_arquivo($tmp_name,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."site_conteudos".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/site_conteudos/";
	
	$extensao = substr(strrchr($tmp_name,'.'),1);
	
	$nome_arquivo = $campo . $id_tabela . "." . $extensao;
	$nome_arquivo_mini = $campo . '_mini' . $id_tabela . "." . $extensao;
	
	$tmp_path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'uploads-tmp'.$_SYSTEM['SEPARADOR'].$tmp_name;

	rename($tmp_path, $caminho_fisico . $nome_arquivo);

	$original = $caminho_fisico . $nome_arquivo;
	$mini = $caminho_fisico . $nome_arquivo_mini;
	
	$new_width = $_SYSTEM['IMG_MINI_WIDTH'];
	$new_height = $_SYSTEM['IMG_MINI_HEIGHT'];
	
	if($_PROJETO['site_conteudos']){
		if($_PROJETO['site_conteudos']['new_width']) $new_width = $_PROJETO['site_conteudos']['new_width'];
		if($_PROJETO['site_conteudos']['new_height']) $new_height = $_PROJETO['site_conteudos']['new_height'];
		if($_PROJETO['site_conteudos']['recorte_y']) $_RESIZE_IMAGE_Y_ZERO = true;
	}
	
	$imgInfo = getimagesize($original);
	
	$old_w = $imgInfo[0];
	$old_h = $imgInfo[1];
	
	if($old_w > $new_width || $old_h > $new_height){
		resize_image($original, $original, $new_width, $new_height,false,false,true);
	}
	
	if($_PROJETO['site_conteudos']){
		if($_PROJETO['site_conteudos']['new_width_mini']) $new_width = $_PROJETO['site_conteudos']['new_width_mini'];
		if($_PROJETO['site_conteudos']['new_height_mini']) $new_height = $_PROJETO['site_conteudos']['new_height_mini'];
		if($_PROJETO['site_conteudos']['recorte_y_mini']) $_RESIZE_IMAGE_Y_ZERO = true;
	}
	
	resize_image($original, $mini, $new_width, $new_height,false,false,true);
	
	banco_update
	(
		$campo."_mini='".$caminho_internet.$nome_arquivo_mini."',".
		$campo."='".$caminho_internet.$nome_arquivo."'",
		$_LISTA['tabela']['nome'],
		"WHERE ".$_LISTA['tabela']['id']."='".$id_tabela."'"
	);
}

function guardar_arquivo_conteudo_campos($params = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_CONEXAO_FTP;
	global $_ALERTA;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$_CONEXAO_FTP)ftp_conectar(Array(
		'manual' => true,
		'host' => $_SYSTEM['SITE']['ftp-files-host'],
		'user' => $_SYSTEM['SITE']['ftp-files-user'],
		'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
	));
	
	if($_CONEXAO_FTP){
		ftp_chdir($_CONEXAO_FTP,'/');
		
		if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
		
		if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.($_SYSTEM['SITE']['ftp-files-path']?$_SYSTEM['SITE']['ftp-files-path'].'/':'').$_SYSTEM['SITE']['ftp-files-conteudos-imagens-path'])) {
			ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-conteudos-imagens-path']); // create directories that do not yet exist
		}
		
		ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-conteudos-imagens-path']);
		
	} else {
		$_ALERTA = 'guardar_arquivo_conteudo_campos: Erro conexao FTP nao realizada';
		return Array(
			'status' => 'FtpNotConnected',
		);
	}
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	if($data) $data = json_decode(rawurldecode($data));
	
	$extensao = substr(strrchr($data->file_name,'.'),1);
	
	$nome = preg_replace('/\.'.$extensao.'/i', '', $data->file_name);
	
	$nome = banco_identificador(Array(
		'id' => $nome, // Valor cru
		'tabela' => Array(
			'nome' => 'site_conteudos_site_conteudos_campos',
			'campo' => 'nome',
			'id_nome' => 'id_usuario',
			'id_valor' => $id_usuario, // Informe apenas para edição
			'sem_status' => true, // Informe apenas para edição
		),
	));
	
	$extensao = strtolower($extensao);
	
	$nome_extensao = $nome . '.' . $extensao;
	
	$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'.'.$extensao;
	$tmp_image_mini = $_SYSTEM['TMP'].'imagem-mini-tmp'.session_id().'.'.$extensao;
	
	$tmp_path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'uploads-tmp'.$_SYSTEM['SEPARADOR'].$tmp_name;

	rename($tmp_path, $tmp_image);
	
	$_RESIZE_IMAGE_Y_ZERO = true;
	
	resize_image($tmp_image, $tmp_image, $_SYSTEM['SITE']['imagens-max-width'], $_SYSTEM['SITE']['imagens-max-height'],false,false,false);
	resize_image($tmp_image, $tmp_image_mini, $_SYSTEM['SITE']['imagens-mini-width'], $_SYSTEM['SITE']['imagens-mini-height'],false,false,false);
	
	ftp_put_file($nome_extensao, $tmp_image);
	
	if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.($_SYSTEM['SITE']['ftp-files-path']?$_SYSTEM['SITE']['ftp-files-path'].'/':'').$_SYSTEM['SITE']['ftp-files-conteudos-imagens-path'] . '/mini')) {
		ftp_mkdir($_CONEXAO_FTP, 'mini'); // create directories that do not yet exist
	}
	
	ftp_chdir($_CONEXAO_FTP,'mini');
	ftp_put_file($nome_extensao, $tmp_image_mini);
	
	unlink($tmp_image);
	unlink($tmp_image_mini);
	
	banco_update
	(
		"diskchanged=NULL",
		"host",
		"WHERE id_usuario='".$id_usuario."'"
		." AND atual IS TRUE"
	);
	
	return Array(
		'status' => 'Ok',
		'imagem' => $_SYSTEM['SITE']['ftp-files-conteudos-imagens-path'] . '/' . $nome_extensao,
		'mini' => $_SYSTEM['SITE']['ftp-files-conteudos-imagens-path'] . '/mini/' . $nome_extensao,
		'file' => $nome_extensao,
		'nome' => $nome,
	);
}

function remover_arquivo_conteudos_campos($params = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_CONEXAO_FTP;
	global $_ALERTA;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$file) return Array(
		'status' => 'FileUndefined',
	);
	
	if(!$_CONEXAO_FTP)ftp_conectar(Array(
		'manual' => true,
		'host' => $_SYSTEM['SITE']['ftp-files-host'],
		'user' => $_SYSTEM['SITE']['ftp-files-user'],
		'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
	));
	
	if($_CONEXAO_FTP){
		ftp_chdir($_CONEXAO_FTP,'/');
		
		if($_SYSTEM['SITE']['ftp-files-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-path']);
		
		if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.($_SYSTEM['SITE']['ftp-files-path']?$_SYSTEM['SITE']['ftp-files-path'].'/':'').$_SYSTEM['SITE']['ftp-files-conteudos-imagens-path'])) {
			ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-files-conteudos-imagens-path']); // create directories that do not yet exist
		}
		
		ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-conteudos-imagens-path']);
		
	} else {
		$_ALERTA = 'remover_arquivo_conteudos_campos: Erro conexao FTP nao realizada';
		return Array(
			'status' => 'FtpNotConnected',
		);
	}
	
	ftp_delete($_CONEXAO_FTP, $file);
	
	ftp_chdir($_CONEXAO_FTP,'mini');
	
	ftp_delete($_CONEXAO_FTP, $file);
}

function remover_item($params = false){
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	global $_LISTA;
	global $_ALERTA;
	
	$id = $_REQUEST['id'];
	$id2 = $_REQUEST['id2'];
	$id3 = $_REQUEST['id3'];
	$item = $_REQUEST['item'];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."site_conteudos".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/site_conteudos/";
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($item && $id){
		switch($item){
			case 'imagem_path_conteudos':
				if($id && $id2 && $id3){
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_site_conteudos',
						))
						,
						"site_conteudos",
						"WHERE id_site_conteudos='".$id."'"
						." AND id_usuario='".$id_usuario."'"
					);
					
					if($resultado){
						$site_conteudos_site_conteudos_campos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'file',
							))
							,
							"site_conteudos_site_conteudos_campos",
							"WHERE id_site_conteudos='".$id."'"
							." AND id_site_conteudos_campos='".$id2."'"
							." AND id_site_conteudos_tipos='".$id3."'"
						);
						
						remover_arquivo_conteudos_campos(Array(
							'file' => $site_conteudos_site_conteudos_campos[0]['file'],
						));
						
						banco_update
						(
							"file=NULL,".
							"mini=NULL,".
							"nome=NULL,".
							"valor=NULL",
							"site_conteudos_site_conteudos_campos",
							"WHERE id_site_conteudos='".$id."'"
							." AND id_site_conteudos_campos='".$id2."'"
							." AND id_site_conteudos_tipos='".$id3."'"
						);
					}
				}
			break;
			case 'imagem_path':
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						$item,
					))
					,
					$_LISTA['tabela']['nome'],
					"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
					." AND id_usuario='".$id_usuario."'"
				);
				
				if($resultado){
					banco_update
					(
						$item."=NULL",
						$_LISTA['tabela']['nome'],
						"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
					);
					
					$nome_original = str_replace($caminho_internet,'',$resultado[0][$item]);
				
					$resultado[0][$item] = str_replace($caminho_internet,$caminho_fisico,$resultado[0][$item]);
					if(is_file($resultado[0][$item]))unlink($resultado[0][$item]);
					
					$extensao = substr(strrchr($resultado[0][$item],'.'),1);
					$nome_arquivo_mini = $item . '_mini' . $id . "." . $extensao;
					
					$resultado[0][$item] = str_replace($nome_original,$nome_arquivo_mini,$resultado[0][$item]);
					if(is_file($resultado[0][$item]))unlink($resultado[0][$item]);
				}
			break;
		}	
		
		
		return editar();
	}
}

function pagina_mestre_del_campos($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'mobile',
		))
		,
		"host",
		"WHERE id_usuario='".$id_usuario."'"
	);
	
	if($host[0]['mobile']){
		$mobile = true;
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site_conteudos_tipos",
		"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
	);
	
	$id_site = $resultado[0]['id_site'];
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'html',
			'html_mobile',
		))
		,
		"site",
		"WHERE id_site='".$id_site."'"
	);
	
	$htmls[] = $site[0]['html'];
	if($mobile && $site[0]['html_mobile'])$htmls[] = $site[0]['html_mobile'];
	$count = 0;
	
	foreach($htmls as $html_aux){
		$dom = new DOMDocument();
		$dom->loadHTML($html_aux, LIBXML_HTML_NOIMPLIED | HTML_PARSE_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		
		$finder = new DomXPath($dom);
		$classname = "b2make-widget";
		$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
		if($nodes)
		foreach($nodes as $widget){
			if($widget->attributes->getNamedItem('id')->value == 'area-'.$marcador){
				$widget->parentNode->removeChild($widget);
				break;
			}
		}
		
		$html_aux = $dom->saveHTML();
		
		if($count > 0){
			$html_mobile = $html_aux;
		} else {
			$html = $html_aux;
		}
		
		$count++;
	}
	
	banco_update
	(
		($mobile ? "html_mobile='".addslashes($html_mobile)."'," : "").
		"html='".addslashes($html)."'",
		"site",
		"WHERE id_site='".$id_site."'"
	);
}

// ======================================================================================

function conteudo_acao_manual(){
	return lista();
}

function conteudo_tipo_mudar($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$_SESSION[$_SYSTEM['ID']."conteudo_id_site_conteudos_tipos"] = $id_site_conteudos_tipos;
	
	switch($opcao_atual){
		case 'editar':
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos',
				))
				,
				"site_conteudos",
				"WHERE id_site_conteudos='".$id_conteudo."'"
				." AND id_usuario='".$id_usuario."'"
			);
			
			if($resultado){
				$campo_tabela = "site_conteudos";
				$campo_tabela_extra = "WHERE id_site_conteudos='".$id_conteudo."'";
				
				$campo_nome = "id_site_conteudos_tipos"; $campo_valor = $id_site_conteudos_tipos; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				
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
				
				return true;
			} else {
				return false;
			}
		break;
	}
}

function teste(){
	global $_SYSTEM;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_CONEXAO_FTP;
	
	echo '<h1>Testes</h1>';

	exit;
}

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
	if($_REQUEST['mp3_player']){
		$id = $_SESSION[$_SYSTEM['ID']."mp3_id"];
		$categoria_id = 3;
		
		banco_conectar();
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'titulo',
				'sub_titulo',
				'musica',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		banco_fechar_conexao();
		
		$dom = new DOMDocument("1.0", "UTF-8");
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		
		$mp3player = $dom->appendChild(new DOMElement('mp3player'));
		
		$mp3 = $mp3player->appendChild(new DOMElement('mp3'));
		$attr = $mp3->setAttributeNode(new DOMAttr('id', 1));
		
		$title = $mp3->appendChild(new DOMElement('title',$conteudo[0]['titulo']));
		$artist = $mp3->appendChild(new DOMElement('artist',$conteudo[0]['sub_titulo']));
		$url = $mp3->appendChild(new DOMElement('url',$_HTML['separador'].$conteudo[0]['musica']));
		
		header("Content-Type: text/xml");
		echo $dom->saveXML();
	}
}

function ajax(){
	global $_SYSTEM;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	global $_B2MAKE_CONTENT_CAMPOS;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = $_REQUEST["query"];
		if(!$query) return;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		banco_fechar_conexao();

		for($i=0;$i<count($resultado);$i++){
			$saida[] = Array(
				'value' => $resultado[$i][1],
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'tipo-add'){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		
		$nome = $_REQUEST['nome'];
		$acao = $_REQUEST['acao'];
		$id = $_REQUEST['id'];
		$opcao_atual = $_REQUEST['opcao_atual'];
		$id_conteudo = $_REQUEST['id_conteudo'];
		
		if($nome){
			switch($acao){
				case 'add':
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_host',
						))
						,
						"site",
						"WHERE id_site_pai IS NULL"
						." AND id_usuario='".$id_usuario."'"
					);
					
					if($resultado){
						$id_host = $resultado[0]['id_host'];
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_site',
							))
							,
							"site",
							"WHERE id_usuario='".$id_usuario."'"
							." AND id='01-modelos-de-paginas'"
						);
						
						$id_site_modelo = $resultado[0]['id_site'];
						
						$html = modelo_abrir($_SYSTEM['PATH'].'content'.$_SYSTEM['SEPARADOR'].'pagina-conteudos.html');
						$html_mobile = modelo_abrir($_SYSTEM['PATH'].'content'.$_SYSTEM['SEPARADOR'].'pagina-conteudos-mobile.html');
						
						$id = site_criar_identificador($nome,$id_usuario,false,$id_site_modelo);
						
						$campos = null;
						
						$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_site_pai"; $campo_valor = $id_site_modelo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "html_mobile"; $campo_valor = $html_mobile; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "html_mobile_saved"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						$campo_nome = "pagina_mestre"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						banco_insert_name
						(
							$campos,
							"site"
						);
						
						$id_site = banco_last_id();
						
						$_SESSION[$_SYSTEM['ID']."usuario"]['content'] = true;
						$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
						
						$campos = null;
						
						$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_site"; $campo_valor = $id_site; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						
						banco_insert_name
						(
							$campos,
							"site_conteudos_tipos"
						);
						
						$id_site_conteudos_tipos = banco_last_id();
						
						conteudo_tipo_mudar(Array(
							'id_conteudo' => $id_conteudo,
							'id_site_conteudos_tipos' => $id_site_conteudos_tipos,
							'opcao_atual' => $opcao_atual,
						));
						
						$saida = Array(
							'status' => 'Ok',
						);
					} else {
						$saida = Array(
							'status' => 'IdNaoPertenceAoSeuUser',
						);
					}
				break;
				case 'edit':
					if($id){
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_site',
							))
							,
							"site_conteudos_tipos",
							"WHERE id_site_conteudos_tipos='".$id."'"
							." AND id_usuario='".$id_usuario."'"
						);
						
						if($resultado){
							$resultado2 = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_site',
								))
								,
								"site",
								"WHERE id_usuario='".$id_usuario."'"
								." AND id='01-modelos-de-paginas'"
							);
							
							$id_site = $resultado[0]['id_site'];
							$id_site_modelo = $resultado2[0]['id_site'];
							
							$id_novo = site_criar_identificador($nome,$id_usuario,$id_site,$id_site_modelo);
							
							banco_update
							(
								"nome='".$nome."'",
								"site_conteudos_tipos",
								"WHERE id_site_conteudos_tipos='".$id."'"
							);
							
							banco_update
							(
								"id='".$id_novo."',".
								"nome='".$nome."'",
								"site",
								"WHERE id_site='".$id_site."'"
							);
							
							$_SESSION[$_SYSTEM['ID']."usuario"]['content'] = true;
							$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
							
							$saida = Array(
								'status' => 'Ok',
							);
						} else {
							$saida = Array(
								'status' => 'IdNaoPertenceAoSeuUser',
							);
						}
					} else {
						$saida = Array(
							'status' => 'IdNaoDefinido',
						);
					}
				break;
			}
		} else {
			$saida = Array(
				'status' => 'NomeNaoDefinido',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'tipo-mudar'){
		$id_conteudo = $_REQUEST['id_conteudo'];
		$id_site_conteudos_tipos = $_REQUEST['id_site_conteudos_tipos'];
		$opcao_atual = $_REQUEST['opcao_atual'];
		
		$mudar = conteudo_tipo_mudar(Array(
			'id_conteudo' => $id_conteudo,
			'id_site_conteudos_tipos' => $id_site_conteudos_tipos,
			'opcao_atual' => $opcao_atual,
		));
		
		if($opcao_atual == 'editar'){
			if($mudar){
				$saida = Array(
					'status' => 'Ok',
				);
			} else {
				$saida = Array(
					'status' => 'IdNaoPertenceAoSeuUser',
				);
			}
		} else {
			$saida = Array(
				'status' => 'Ok',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'campos-add'){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		
		$nome = $_REQUEST['nome'];
		$campo_tipo_id = $_REQUEST['campo_tipo'];
		$conteudo_tipo_id = $_REQUEST['conteudo_tipo'];
		
		if($nome){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tipos',
					'versao',
				))
				,
				"site_conteudos_tipos",
				"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
				." AND id_usuario='".$id_usuario."'"
			);
			
			if($resultado){
				if($_B2MAKE_CONTENT_CAMPOS){
					foreach($_B2MAKE_CONTENT_CAMPOS as $res){
						if($res['type'] == $campo_tipo_id){
							$widget = $res['widget'];
							break;
						}
					}
				}
				
				$id = campos_criar_identificador($nome,$id_usuario);
				
				$campos = null;
				
				$campo_nome = "id_site_conteudos_tipos"; $campo_valor = $conteudo_tipo_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "tipo"; $campo_valor = $campo_tipo_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "widget"; $campo_valor = $widget; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				
				banco_insert_name
				(
					$campos,
					"site_conteudos_campos"
				);
				
				$versao = (int)$resultado[0]['versao'];
				$versao++;
				
				banco_update
				(
					"versao='".$versao."'",
					"site_conteudos_tipos",
					"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
				);
				
				$saida = Array(
					'status' => 'Ok',
					'marcador' => $id,
					'widget' => $widget,
				);
			} else {
				$saida = Array(
					'status' => 'IdNaoPertenceAoSeuUser',
				);
			}
		} else {
			$saida = Array(
				'status' => 'NomeNaoDefinido',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'campos-edit'){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		
		$nome = $_REQUEST['nome'];
		$campo_id = $_REQUEST['id'];
		$conteudo_tipo_id = $_REQUEST['conteudo_tipo'];
		
		if($nome){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tipos',
					'versao',
				))
				,
				"site_conteudos_tipos",
				"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
				." AND id_usuario='".$id_usuario."'"
			);
			
			if($resultado){
				$site_conteudos_campos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_site_conteudos_campos',
						'widget',
					))
					,
					"site_conteudos_campos",
					"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
					." AND id_usuario='".$id_usuario."'"
					." AND id='".$campo_id."'"
				);
				
				if($site_conteudos_campos){
					$id_site_conteudos_campos = $site_conteudos_campos[0]['id_site_conteudos_campos'];
					$widget = $site_conteudos_campos[0]['widget'];
					
					$id = campos_criar_identificador($nome,$id_usuario,$id_site_conteudos_campos);
					
					$campo_tabela = "site_conteudos_campos";
					$campo_tabela_extra = "WHERE id_site_conteudos_campos='".$id_site_conteudos_campos."'";
					
					$campo_nome = "nome"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
					$campo_nome = "id"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
					
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
					
					$versao = (int)$resultado[0]['versao'];
					$versao++;
					
					banco_update
					(
						"versao='".$versao."'",
						"site_conteudos_tipos",
						"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
					);
					
					$saida = Array(
						'status' => 'Ok',
						'marcador' => $id,
						'marcador_antigo' => $campo_id,
						'widget' => $widget,
					);
				} else {
					$saida = Array(
						'status' => 'CampoNaoPertenceAoSeuUser',
					);
				}
			} else {
				$saida = Array(
					'status' => 'IdNaoPertenceAoSeuUser',
				);
			}
		} else {
			$saida = Array(
				'status' => 'NomeNaoDefinido',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'campos-excluir'){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		
		$campo_id = $_REQUEST['id'];
		$conteudo_tipo_id = $_REQUEST['conteudo_tipo'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tipos',
				'versao',
			))
			,
			"site_conteudos_tipos",
			"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		if($resultado){
			$site_conteudos_campos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_campos',
					'widget',
				))
				,
				"site_conteudos_campos",
				"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
				." AND id_usuario='".$id_usuario."'"
				." AND id='".$campo_id."'"
			);
			
			if($site_conteudos_campos){
				$id_site_conteudos_campos = $site_conteudos_campos[0]['id_site_conteudos_campos'];
				
				banco_delete
				(
					"site_conteudos_site_conteudos_campos",
					"WHERE id_site_conteudos_campos='".$id_site_conteudos_campos."'"
				);
				
				banco_delete
				(
					"site_conteudos_campos",
					"WHERE id_site_conteudos_campos='".$id_site_conteudos_campos."'"
				);
				
				$versao = (int)$resultado[0]['versao'];
				$versao++;
				
				banco_update
				(
					"versao='".$versao."'",
					"site_conteudos_tipos",
					"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
				);
				
				$saida = Array(
					'status' => 'Ok',
					'marcador' => $campo_id,
					'widget' => $site_conteudos_campos[0]['widget'],
				);
			} else {
				$saida = Array(
					'status' => 'CampoNaoPertenceAoSeuUser',
				);
			}
		} else {
			$saida = Array(
				'status' => 'IdNaoPertenceAoSeuUser',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'campos-padrao-excluir'){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		
		$campo_id = $_REQUEST['id'];
		$conteudo_tipo_id = $_REQUEST['conteudo_tipo'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tipos',
			))
			,
			"site_conteudos_tipos",
			"WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		if($resultado){
			$campo_tabela = "site_conteudos_tipos";
			$campo_tabela_extra = "WHERE id_site_conteudos_tipos='".$conteudo_tipo_id."'"
			." AND id_usuario='".$id_usuario."'";
			
			switch($campo_id){
				case 'imagem':
					$campo_nome = "campo_imagem_excluido"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
				break;
				case 'texto':
					$campo_nome = "campo_texto_excluido"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
				break;
			}
			
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
				'status' => 'IdNaoPertenceAoSeuUser',
			);
		}
	}
	
	if($_REQUEST['opcao'] == 'pagina-mestre-atualizar'){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		
		$html = $_REQUEST['html'];
		$html_mobile = $_REQUEST['html_mobile'];
		$id_site_conteudos_tipos = $_REQUEST['conteudo_tipo'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tipos',
			))
			,
			"site_conteudos_tipos",
			"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
			." AND id_usuario='".$id_usuario."'"
		);
		
		if($resultado){
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'mobile',
				))
				,
				"host",
				"WHERE id_usuario='".$id_usuario."'"
			);
			
			if($host[0]['mobile']){
				$mobile = true;
			}
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site',
				))
				,
				"site_conteudos_tipos",
				"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
			);
			
			$id_site = $resultado[0]['id_site'];
			
			banco_update
			(
				($mobile ? "html_mobile='".$html_mobile."'," : "").
				"html='".$html."'",
				"site",
				"WHERE id_site='".$id_site."'"
			);
			
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'IdNaoPertenceAoSeuUser',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	return $saida;
}

function start(){
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_LISTA;
	global $_HTML;
	global $_OPCAO;
	
	if($_REQUEST["opcao"])				$opcoes = $_OPCAO = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	upload_files();
	
	if($_REQUEST[xml]){
		xml();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		if($_SESSION[$_SYSTEM['ID']."alerta"]){
			alerta($_SESSION[$_SYSTEM['ID']."alerta"]);
			$_SESSION[$_SYSTEM['ID']."alerta"] = false;
		}
		
		switch($opcoes){
			case 'menu_'.$_LOCAL_ID:
			case 'lista':						$saida = lista();break;
			case 'add':							$saida = (operacao('adicionar') ? add() : lista()); break;
			case 'add_base':					$saida = (operacao('adicionar') ? add_base() : lista());break;
			case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'editar_base':					$saida = (operacao('editar') ? editar_base() : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : lista());break;
			case 'remover_item':				$saida = remover_item();break;
			case 'teste':						$saida = teste();break;
			case 'acao-manual':					$saida = conteudo_acao_manual();break;
			default: 							$saida = lista();
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