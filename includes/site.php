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

$_VERSAO_MODULO_INCLUDE				=	'1.2.0';

function site_append_HTML(DOMNode $parent, $source) {
    $tmpDoc = new DOMDocument();
    $tmpDoc->loadHTML($source);
    foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
        $node = $parent->ownerDocument->importNode($node);
        $parent->appendChild($node);
    }
}

function site_library_update($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_B2MAKE_FTP_FILES_PATH;

	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($widget){
		switch($widget){
			case 'formularios':
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
				);
				
				if($resultado){
					foreach($resultado as $res){
						$resultado2 = banco_select_name
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
							"WHERE id_site_formularios='".$res['id_site_formularios']."'"
							." ORDER BY ordem ASC"
						);
						
						$campos = false;
						
						if($resultado2){
							foreach($resultado2 as $res2){
								$campo = Array(
									'id' => $res2['id_site_formularios_campos'],
									'campo' => $res2['campo'],
									'title' => $res2['title'],
									'obrigatorio' => $res2['obrigatorio'],
									'ordem' => $res2['ordem'],
									'tipo' => $res2['tipo'],
									'options_label' => $res2['options_label'],
								);
								
								if(
									$campo['tipo'] == 'select' ||
									$campo['tipo'] == 'checkbox'
								){
									$resultado3 = banco_select_name
									(
										banco_campos_virgulas(Array(
											'nome',
											'id_site_formularios_campos_opcoes',
										))
										,
										"site_formularios_campos_opcoes",
										"WHERE id_site_formularios_campos='".$campo['id']."'"
										." ORDER BY nome ASC"
									);
									
									if($resultado3){
										$campo_opcoes = false;
										
										foreach($resultado3 as $res3){
											$campo_opcoes[] = Array(
												'id' => $res3['id_site_formularios_campos_opcoes'],
												'nome' => $res3['nome'],
											);
										}
										
										$campo['campo_opcoes'] = $campo_opcoes;
									}
								}
								
								$campos[] = $campo;
							}
							
							$json[] = Array(
								'id' => $res['id_site_formularios'],
								'nome' => $res['nome'],
								'campos' => $campos,
							);
						}
					}
					
				} else {
					$json[] = Array(
						'id' => '-1',
						'nome' => 'padrao',
						'campos' => Array(),
					);
				}
			break;
			case 'posts-filter':
				$resultado = banco_select_name
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
				);
				
				if($resultado){
					foreach($resultado as $res){
						$tags[] = Array(
							'nome' => $res['nome'],
							'cor' => $res['cor'],
							'id_site_conteudos_tags' => $res['id_site_conteudos_tags'],
							'id_site_conteudos_tags_pai' => $res['id_site_conteudos_tags_pai'],
						);
					}
					
					$json = Array('tags' => $tags);
				} else {
					$json = Array('tags' => Array());
				}
			break;
			
		}
		
		$json = json_encode($json,JSON_UNESCAPED_UNICODE);
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'json',
			))
			,
			"site_library",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND widget='".$widget."'"
		);
		
		if(!$resultado){
			$campos = null;
			
			$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "widget"; $campo_valor = $widget; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "json"; $campo_valor = $json; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"site_library"
			);
		} else {
			banco_update
			(
				"json='".$json."'",
				"site_library",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND widget='".$widget."'"
			);
		}
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$url = $host[0]['url'] . 'platform/library/'.$widget ;
		curl_post_async($url);
	}
}

function site_pagina_filhos($id_site,$level = 1){
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id',
			'id_site',
			'publicado',
		))
		,
		"site",
		"WHERE id_site_pai='".$id_site."'"
	);
	
	if(!$filhos) $filhos = Array();
	
	if($resultado){
		foreach($resultado as $res){
			$id = $res['id'];
			$publicado = $res['publicado'];
			$caminho = site_pagina_diretorio($res['id_site'],false,true,false);
			
			$filhos_retorno = site_pagina_filhos($res['id_site'],$level+1);
			
			if($filhos_retorno)
				$filhos = array_merge($filhos,$filhos_retorno);
			
			$filhos[] = Array(
				'id' => $id,
				'id_site' => $res['id_site'],
				'publicado' => $publicado,
				'caminho' => $caminho,
				'level' => $level,
			);
		}
		
		return $filhos;
	} else {
		return false;
	}
}

function site_pagina_pais($id_site,$nao_inserir_dir_atual){
	while(true){
		$count++;
		if(!$id_site || $count > 100) break;
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'id_site_pai',
			))
			,
			"site",
			"WHERE id_site='".$id_site."'"
		);
		
		if($resultado){
			if($resultado[0]['id_site_pai']){
				$id = $resultado[0]['id'];
				$id_site = $resultado[0]['id_site_pai'];
				
				if(!$nao_inserir_dir_atual)$directories[] = $id;
				$nao_inserir_dir_atual = false;
			} else {
				$id_site = false;
			}
		}
	}
	
	return ($directories ? array_reverse($directories) : Array());
}

function site_pagina_diretorio($id_site,$nao_inserir_dir_atual = false,$nao_ftp = false,$mobile = false,$robo = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	
	if(!$nao_ftp && $robo)ftp_chdir($_CONEXAO_FTP, '~');
	
	$dirs = site_pagina_pais($id_site,$nao_inserir_dir_atual);
	
	if($mobile){
		if(!$nao_ftp){
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-site-user'].':'.$_SYSTEM['SITE']['ftp-site-pass'].'@'.$_SYSTEM['SITE']['ftp-site-host'].'/'.$_SYSTEM['SITE']['ftp-mobile-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-mobile-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-mobile-path']);
		}
	}
	
	if($dirs)
	foreach($dirs as $dir){
		if(!$nao_ftp){
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-site-user'].':'.$_SYSTEM['SITE']['ftp-site-pass'].'@'.$_SYSTEM['SITE']['ftp-site-host'].'/'.$_SYSTEM['SITE']['ftp-site-path'].($mobile ? $_SYSTEM['SITE']['ftp-mobile-path'].'/':'').$dirs_antes . $dir )) {
				ftp_mkdir($_CONEXAO_FTP, $dir); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$dir);
		}
		
		$dirs_antes .= $dir . '/';
	}
	
	return ($dirs_antes ? $dirs_antes : '');
}

function site_publish_page($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_B2MAKE_PATH;
	global $_HTML_META;
	global $_B2MAKE_SITE_SUFIX_REGEX;
	global $_B2MAKE_FTP_FILES_PATH;

	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$html = preg_replace('/&lt;/i', '<', $html);
	$html = preg_replace('/&gt;/i', '>', $html);
	$html = preg_replace('/<!--script-->/i', '</script>', $html);
	
	if(!$ftp_site_host) $ftp_site_host = $_SYSTEM['SITE']['ftp-site-host'];
	if(!$ftp_site_user) $ftp_site_user = $_SYSTEM['SITE']['ftp-site-user'];
	if(!$ftp_site_pass) $ftp_site_pass = $_SYSTEM['SITE']['ftp-site-pass'];
	if(!$layout_site) $layout_site = $_SYSTEM['PATH'].$_B2MAKE_PATH.$_SYSTEM['SEPARADOR'].'layout-site.html';
	if(!$pagina_where){ if($id_site) $pagina_where = "WHERE id_site='".$id_site."' AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"; else $pagina_where = "WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"." AND atual IS TRUE";}
	
	if($html || $html_do_banco){
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $ftp_site_host,
			'user' => $ftp_site_user,
			'pass' => $ftp_site_pass,
		));
		
		if($_CONEXAO_FTP){
			$pagina = modelo_abrir($layout_site);
			
			if($google_fontes){
				banco_update
				(
					"google_fontes='".$google_fontes."'",
					"site",
					$pagina_where
				);
			}
			
			$site_campos = Array(
				'pagina_titulo',
				'pagina_description',
				'pagina_keywords',
				'pagina_head_extra',
				'pagina_favicon',
				'pagina_favicon_version',
				'id_site',
				'id',
				'data_criacao',
				'data_modificacao',
				'id_site_pai',
				'publicado',
				'publicado_mobile',
				'google_fontes',
				'meta_robots',
			);
			
			if($html_do_banco)
			if($mobile){
				$site_campos[] = 'html';
				$site_campos[] = 'html_mobile';
				$site_campos[] = 'html_mobile_temp';
			} else {
				$site_campos[] = 'html';
				$site_campos[] = 'html_temp';
			}
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas($site_campos)
				,
				"site",
				$pagina_where
			);
			
			if($html_do_banco)
			if($mobile){
				$html = ($resultado[0]['html_mobile_temp'] ? (string)$resultado[0]['html_mobile_temp'] : ($resultado[0]['html_mobile'] ? (string)$resultado[0]['html_mobile'] : (string)$resultado[0]['html']));
			} else {
				$html = ($resultado[0]['html_temp'] ? (string)$resultado[0]['html_temp'] : (string)$resultado[0]['html']);
			}
			
			// ================= Pré-Processamento Widgets ==================
			
			if($robo){
				phpQuery::newDocumentHTML($html);
				
				foreach(pq('.b2make-widget') as $el){
					$widget = pq($el);
					
					switch($widget->attr('data-type')){
						case 'iframe':
							$iframe = urldecode($widget->attr('data-iframe-code'));
							
							$widget->html('');
							$widget->append($iframe);
							//$widget->removeAttr('data-iframe-code');
						break;
						case 'breadcrumbs':
							$widget->attr('data-id',$resultado[0]['id_site']);
						break;
					}
				}
				
				if($mobile){
					$pagina_options = pq('#b2make-pagina-options');
					$pagina_options->attr('data-device','phone');
				} else {
					$pagina_options = pq('#b2make-pagina-options');
					$pagina_options->attr('data-device','desktop');
				}
				
				$html = phpQuery::getDocument()->htmlOuter();
			}
			
			// ================================================================
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'url_files',
					'url_mobile',
					'dominio_proprio',
					'google_analytic',
					'google_site_verification',
					'meta_global',
					'site_cache',
					'https',
					'user_cpanel',
					'google_fonts',
					'body_global',
					'javascript_global_published',
					'css_global_published',
					'global_version',
					'id_host',
					'site_version',
				))
				,
				"host",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$meta .= "	<meta name=\"generator\" content=\"".$_HTML_META['generator']."\">\n";
			
			$path = site_pagina_diretorio($resultado[0]['id_site'],false,true,$mobile,$robo);
			
			if($host[0]['body_global']){
				$body_global = "\n".$host[0]['body_global'];
			}
			
			if($host[0]['javascript_global_published'] || $host[0]['css_global_published']){
				$javascript_css_globais .= "\n	<link href=\"".$host[0]['url']."files/global.css".($host[0]['global_version']?'?v='.$host[0]['global_version']:'')."\" rel=\"stylesheet\" type=\"text/css\">\n";
				$javascript_css_globais .= "	<script src=\"".$host[0]['url']."files/global.js".($host[0]['global_version']?'?v='.$host[0]['global_version']:'')."\" type=\"text/javascript\"></script>\n";
			}
			
			if($mobile){
				$meta .= "	<link rel=\"canonical\" href=\"".rtrim($host[0]['url'], '/') . '/' .$path."\">\n";
				$viewport = $_SYSTEM['SITE']['mobile-screen-width'];
			} else {
				$meta .= "	<link rel=\"alternate\" media=\"only screen and (max-width: ".$_SYSTEM['SITE']['mobile-screen-width']."px)\" href=\"//".rtrim($host[0]['url_mobile'], '/') . '/' .$path."\">\n";
				$viewport = '1000';
			}
			
			if($resultado){
				if($resultado[0]['id_site'])$id_site = $resultado[0]['id_site'];
				if($resultado[0]['id'])$id = $resultado[0]['id'];
				if($resultado[0]['pagina_titulo'])$pagina_titulo = ($titulo_pagina ? $titulo_pagina : $resultado[0]['pagina_titulo']);
				if($resultado[0]['pagina_description'])$meta .= "	<meta name=\"description\" content=\"".$resultado[0]['pagina_description']."\">\n";
				if($resultado[0]['pagina_keywords'])$meta .= "	<meta name=\"keywords\" content=\"".$resultado[0]['pagina_keywords']."\">\n";
				if($resultado[0]['pagina_head_extra'])$meta .= "	".$resultado[0]['pagina_head_extra']."\n";
				if($resultado[0]['data_criacao'])$meta .= "	<meta http-equiv=\"date\" content=\"".date("D M d Y H:i:s \G\M\T -03:00",strtotime($resultado[0]['data_criacao']))."\">\n";
				if($resultado[0]['data_modificacao'])$meta .= "	<meta http-equiv=\"Cache-Control\" content=\"public, must-revalidate\">\n";
				if($resultado[0]['data_modificacao'])$meta .= "	<meta http-equiv=\"Last-Modified\" content=\"".date("D, d M Y H:i:s \G\M\T -03:00",strtotime($resultado[0]['data_modificacao']))."\">\n";
				if($resultado[0]['meta_robots'])$meta .= "	<meta name=\"robots\" content=\"".$resultado[0]['meta_robots']."\">\n";
			}
			
			if($host)
			foreach($host[0] as $key => $val){
				if(
					$key == 'meta_global' ||
					$key == 'google_site_verification' ||
					$key == 'google_analytic' 
				){
					if($val){
						$meta .= "	".$val . "\n";
					}
				}
			}
			
			if($resultado[0]['publicado']){	$publicado = true; }
			if($resultado[0]['publicado_mobile']){ $publicado_mobile = true; }
			
			if($resultado[0]['google_fontes']){ $google_fontes = $resultado[0]['google_fontes']; }
			
			$google_fonts_global = $host[0]['google_fonts'];
			
			if($google_fonts_global){
				if($google_fonts_global && $google_fontes){
					$fonts_arr = explode('|',$google_fontes);
					$fonts_global_arr = explode('|',$google_fonts_global);
					
					foreach($fonts_global_arr as $font_global){
						$found = false;
						
						foreach($fonts_arr as $font){
							if($font == $font_global){
								$found = true;
								break;
							}
						}
						
						if(!$found){
							$google_fontes = $google_fontes . '|' . $font_global;
						}
					}
				} else {
					$google_fontes = $google_fonts_global;
				}
			}
			
			if($resultado[0]['id_site_pai']){
				$resultado2 = banco_select_name
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
				
				if($resultado2[0]['pagina_favicon']){
					$resultado[0]['pagina_favicon'] = $resultado2[0]['pagina_favicon'];
					$resultado[0]['pagina_favicon_version'] = $resultado2[0]['pagina_favicon_version'];
				}
			}
			
			if($resultado[0]['pagina_favicon']){
				$favicon = '	<link rel="shortcut icon" href="'.preg_replace('/http:/i', '', $host[0]['url_files']).$_SYSTEM['SITE']['ftp-files-imagens-path'].'/favicon.ico'.($resultado[0]['pagina_favicon_version'] ? '?v='.$resultado[0]['pagina_favicon_version'] : '').'">'."\n";
			} else {
				$favicon = '	<link rel="shortcut icon" href="/favicon.ico">'."\n";
			}
			
			if($google_fontes) $google_fontes = '	<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family='.$google_fontes.'">'."\n";
			
			$pagina = modelo_var_troca($pagina,"#b2make-head-title#",($pagina_titulo?$pagina_titulo:'T&iacute;tulo da P&aacute;gina'));
			$pagina = modelo_var_troca($pagina,"<!-- b2make-meta -->",$meta);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-css -->",$favicon.$google_fontes.$_SYSTEM['SITE']['b2make-css']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-jquery -->",$_SYSTEM['SITE']['jquery']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-js -->",$_SYSTEM['SITE']['b2make-js']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-js-extra -->",$_SYSTEM['SITE']['js-extra'].$javascript_css_globais);
			$pagina = modelo_var_troca($pagina,"#viewport#",$viewport);
			$pagina = modelo_var_troca($pagina,"#b2make-body#",$html.$body_global);
			
			$pagina = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $pagina);
			
			if($host[0]['https']){
				$pagina = preg_replace("/http\:\/\//", "//", $pagina);
			}

			if($host[0]['dominio_proprio']){
				$pagina = preg_replace("/\/\/".$host[0]['user_cpanel'].$_B2MAKE_SITE_SUFIX_REGEX."\//", '//'.$host[0]['dominio_proprio'].'/', $pagina);
			}

			ftp_chdir($_CONEXAO_FTP,'~');
			if($_SYSTEM['SITE']['ftp-site-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-site-path']);
			
			$nome_file = 'index.html';
			$tmp_file = $_SYSTEM['TMP'].'pagina-tmp'.session_id();
			
			file_put_contents($tmp_file, $pagina);
			
			$path = site_pagina_diretorio($id_site,false,false,$mobile,$robo);
			
			ftp_put_file($nome_file, $tmp_file);
			
			unlink($tmp_file);
			
			// ============= Publicar versão desktop onde não há versão mobile ======
			
			if(!$mobile){
				$site = banco_select_name
				(
					banco_campos_virgulas(Array(
						'html_mobile',
					))
					,
					"site",
					$pagina_where
				);
				
				if(!$site[0]['html_mobile']){
					ftp_chdir($_CONEXAO_FTP, '~');
					
					$tmp_file = $_SYSTEM['TMP'].'pagina-2-tmp'.session_id();
			
					file_put_contents($tmp_file, $pagina);
					
					$path = site_pagina_diretorio($id_site,false,false,true,$robo);
					
					ftp_put_file($nome_file, $tmp_file);
			
					unlink($tmp_file);
				}
			}
			
			// ============= Atualizar versao do site
			
			if($atualizar_site_version){
				$site_version = $host[0]['site_version'];
				$id_host = $host[0]['id_host'];
				
				if(!$site_version) $site_version = 1; else $site_version = (int)$site_version + 1;
				
				$campo_tabela = "host";
				$campo_tabela_extra = "WHERE id_host='".$id_host."'";
				
				$campo_nome = "site_version"; $campo_valor = $campo_valor; $editar[$campo_tabela][] = $campo_nome."='".$site_version."'";
				
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
				
				// ============= Atualizar Platform ========
				
				$url_platform = $host[0]['url'] . 'platform/site-version';
				curl_post_async($url_platform);
				
				// =============
			}
			
			// =============
			
			if(!$nao_fechar_ftp)ftp_fechar_conexao();
			
			unlink($tmp_file);
			
			$campo_tabela = "site";
			$campo_tabela_extra = $pagina_where;
			
			if($mobile){
				$campo_nome = "publicado_mobile"; $editar[$campo_tabela][] = $campo_nome."=1";
				$campo_nome = "html_mobile_saved"; $editar[$campo_tabela][] = $campo_nome."=1";
				
				if($html_do_banco && $resultado[0]['html_mobile_temp']){
					$campo_nome = "html_mobile_temp"; $editar[$campo_tabela][] = $campo_nome."=NULL";
				}
			} else {
				$campo_nome = "publicado"; $editar[$campo_tabela][] = $campo_nome."=1";
				
				if($html_do_banco && $resultado[0]['html_temp']){
					$campo_nome = "html_temp"; $editar[$campo_tabela][] = $campo_nome."=NULL";
				}
			}
			
			$campo_nome = "publicado_id"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			
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
			
			if(!$robo)publisher_sitemaps();
			
			$saida = Array(
				'url' => ($mobile ? ($host[0]['https'] ? 'https:':'http:').'//'.rtrim($host[0]['url_mobile'], '/').'/'.$path : ($host[0]['https'] ? preg_replace('/http:/i', 'https:', $host[0]['url']):preg_replace('/https:/i', 'http:', $host[0]['url'])) . $path),
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'FtpNotConnected',
			);
		}
	} else {
		$saida = Array(
			'status' => 'HtmlNull',
		);
	}
	
	return $saida;
}

function site_delete_page($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;

	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
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
		
		if(!$ftp_site_host) $ftp_site_host = $_SYSTEM['SITE']['ftp-site-host'];
		if(!$ftp_site_user) $ftp_site_user = $_SYSTEM['SITE']['ftp-site-user'];
		if(!$ftp_site_pass) $ftp_site_pass = $_SYSTEM['SITE']['ftp-site-pass'];
		
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $ftp_site_host,
			'user' => $ftp_site_user,
			'pass' => $ftp_site_pass,
		));
		
		if($_CONEXAO_FTP){
			ftp_pasv($_CONEXAO_FTP, true);
			
			$raiz = ftp_pwd($_CONEXAO_FTP);
			
			foreach($filhos as $filho){
				// ====== Desktop 
				ftp_chdir($_CONEXAO_FTP,$raiz . $filho['caminho']);
				
				$files = ftp_nlist($_CONEXAO_FTP, ".");
				
				foreach($files as $file){
					if($file == '.' || $file == '..') continue;
					ftp_delete($_CONEXAO_FTP, $file);
				}
				
				ftp_cdup($_CONEXAO_FTP);
				
				ftp_rmdir($_CONEXAO_FTP, $raiz . $filho['caminho']);
				
				// ====== Mobile 
				ftp_chdir($_CONEXAO_FTP,'/mobile' . $raiz . $filho['caminho']);
				
				$files = ftp_nlist($_CONEXAO_FTP, ".");
				
				foreach($files as $file){
					if($file == '.' || $file == '..') continue;
					ftp_delete($_CONEXAO_FTP, $file);
				}
				
				ftp_cdup($_CONEXAO_FTP);
				
				ftp_rmdir($_CONEXAO_FTP, '/mobile' . $raiz . $filho['caminho']);
				// ======
				if(!$nao_remover_banco)
				banco_delete
				(
					"site",
					"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
					." AND id_site='".$filho['id_site']."'"
				);
			}
			
			if(!$nao_fechar_ftp)ftp_fechar_conexao();
		} else if($_B2MAKE_PAGINA_LOCAL){
			foreach($filhos as $filho){
				if(!$nao_remover_banco)
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

function site_identificador_unico($id,$num,$id_site,$id_usuario,$id_site_pai){
	global $_B2MAKE_URL_WORDS_BLOCKED;
	
	if($id_site_pai){
		$site = banco_select
		(
			"id_site_pai"
			,
			"site",
			"WHERE id_usuario='".$id_usuario."'"
			." AND id_site='".$id_site_pai."'"
		);
		
		if(!$site[0]['id_site_pai'])
		if($_B2MAKE_URL_WORDS_BLOCKED){
			foreach($_B2MAKE_URL_WORDS_BLOCKED as $word){
				if($word == $id){
					$num = $num + 1;
					break;
				}
			}
		}
	} else {
		if($_B2MAKE_URL_WORDS_BLOCKED){
			foreach($_B2MAKE_URL_WORDS_BLOCKED as $word){
				if($word == $id){
					$num = $num + 1;
					break;
				}
			}
		}
	}
	
	$site = banco_select
	(
		"id_site"
		,
		"site",
		"WHERE id='".($num ? $id.'-'.$num : $id)."'"
		." AND id_usuario='".$id_usuario."'"
		.($id_site?" AND id_site!='".$id_site."'":"")
		.($id_site_pai?" AND id_site_pai='".$id_site_pai."'":"")
	);
	
	$urls_personalizadas = banco_select
	(
		"id_urls_personalizadas"
		,
		"urls_personalizadas",
		"WHERE url='".($num ? $id.'-'.$num : $id)."'"
		." AND id_usuario='".$id_usuario."'"
	);
	
	if($site || $urls_personalizadas){
		return site_identificador_unico($id,$num + 1,$id_site,$id_usuario,$id_site_pai);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function site_criar_identificador($id,$id_usuario,$id_site = false,$id_site_pai = false){
	$tam_max_id = 90;
	$id = retirar_acentos($id);
	
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
		
		return site_identificador_unico($id,$num,$id_site,$id_usuario,$id_site_pai);
	} else {
		return site_identificador_unico($id,0,$id_site,$id_usuario,$id_site_pai);
	}
}

?>