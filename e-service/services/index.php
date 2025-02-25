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

$_VERSAO_MODULO				=	'1.1.0';
$_LOCAL_ID					=	"services";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_LOJA				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
$_HTML['LAYOUT']			=	"../layout.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

$_HTML['titulo'] 						= 	$_HTML['titulo']."Serviços.";
$_HTML['variaveis']['titulo-modulo']	=	'Serviços';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"../js.js?v=".$_VERSAO_MODULO."\"></script>\n".
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= 
"<link href=\"../css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'servicos';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'servicos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Serviços';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// B2make

function site_identificador_unico($id,$num,$id_site,$id_usuario,$id_site_pai){
	global $_B2MAKE_URL_WORDS_BLOCKED;
	
	if($id_site_pai){
		$conteudo = banco_select
		(
			"id_site_pai"
			,
			"site",
			"WHERE id_usuario='".$id_usuario."'"
			." AND id_site='".$id_site_pai."'"
		);
		
		if(!$conteudo[0]['id_site_pai'])
		if($_B2MAKE_URL_WORDS_BLOCKED){
			foreach($_B2MAKE_URL_WORDS_BLOCKED as $word){
				if($word == $id){
					$num = $num + 1;
					break;
				}
			}
		}
	}
	
	$conteudo = banco_select
	(
		"id_site"
		,
		"site",
		"WHERE id='".($num ? $id.'-'.$num : $id)."'"
		." AND id_usuario='".$id_usuario."'"
		.($id_site?" AND id_site!='".$id_site."'":"")
		.($id_site_pai?" AND id_site_pai='".$id_site_pai."'":"")
	);
	
	if($conteudo){
		return site_identificador_unico($id,$num + 1,$id_site,$id_usuario,$id_site_pai);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function site_criar_identificador($id,$id_usuario,$id_site = false,$id_site_pai = false){
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
		
		return site_identificador_unico($id,$num,$id_site,$id_usuario,$id_site_pai);
	} else {
		return site_identificador_unico($id,0,$id_site,$id_usuario,$id_site_pai);
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
	
	return array_reverse($directories);
}

function site_pagina_diretorio($id_site,$nao_inserir_dir_atual = false,$nao_ftp = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	
	$dirs = site_pagina_pais($id_site,$nao_inserir_dir_atual);
	
	if($dirs)
	foreach($dirs as $dir){
		if(!$nao_ftp){
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-site-path'].$dirs_antes . $dir )) {
				ftp_mkdir($_CONEXAO_FTP, $dir); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$dir);
		}
		
		$dirs_antes .= $dir . '/';
	}
	
	return ($dirs_antes ? $dirs_antes : '');
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
			$caminho = site_pagina_diretorio($res['id_site'],false,true);
			
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

function servico_publicar_pagina($params = false){
	global $_SYSTEM;
	global $_DEBUG_CONT;
	global $_B2MAKE_URL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = $usuario['id_usuario'];
	$id_loja = $usuario['id_loja'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'html',
		))
		,
		"site",
		"WHERE id_usuario='".$id_usuario."'"
		." AND id='pagina-de-servicos'"
	);
	$servicos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'descricao',
			'imagem_path',
			'preco',
			'versao',
			'validade',
			'quantidade',
		))
		,
		"servicos",
		"WHERE id_servicos='".$id_servicos."'"
		." AND id_loja='".$id_loja."'"
	);
	
	if($resultado){
		if($servicos){
			// Publicar Lista de Serviços
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'dominio_proprio',
					'services_list',
				))
				,
				"host",
				"WHERE id_usuario='".$id_usuario."'"
				." AND atual IS TRUE"
			);
			
			$path = site_pagina_diretorio($id_site,false,true);
			$url = $host[0]['url'] . $path;
			$services_list = json_decode($host[0]['services_list'],true);
			
			if($services_list){
				foreach($services_list as $num => $service){
					if($service['id'] == $id_servicos){
						$service_index = $num;
						$service_found = true;
						break;
					}
				}
			} else {
				$services_list = Array();
			}
			
			//$url_imagem = $_B2MAKE_URL . 'site/images/b2make-album-sem-imagem.png';
			if($servicos[0]['imagem_path'])$url_imagem = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
			
			$service_data = Array(
				'id' => $id_servicos,
				'url' => $url,
				'nome' => utf8_encode($servicos[0]['nome']),
				'descricao' => utf8_encode($servicos[0]['descricao']),
				'quantidade' => $servicos[0]['quantidade'],
				'validade' => $servicos[0]['validade'],
				'preco' => $servicos[0]['preco'],
			);
			
			if($url_imagem)$service_data['url_imagem'] = $url_imagem;
			
			if($service_found){
				unset($services_list[$service_index]);
			}
			array_unshift($services_list, $service_data);
			
			$json_services_list = json_encode($services_list);
			
			banco_update
			(
				"services_list='".addslashes($json_services_list)."'",
				"host",
				"WHERE id_usuario='".$id_usuario."'"
				." AND atual IS NOT NULL"
			);
			
			// Publicar Página do Serviços
			
			$html = $resultado[0]['html'];
			
			$dom = new DOMDocument();
			$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | HTML_PARSE_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			
			$finder = new DomXPath($dom);
			$classname = "b2make-widget";
			$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
			
			if($nodes)
			foreach($nodes as $widget){
				$font_name = false;
				if($widget->attributes->getNamedItem('data-google-font')){
					$font_name = $widget->attributes->getNamedItem('data-font-family')->value;
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
				
				switch($widget->attributes->getNamedItem('data-type')->value){
					case 'texto':
						$mudar_valor = false;
						
						switch($widget->attributes->getNamedItem('data-marcador')->value){
							case '@e-services#titulo':
								$mudar_valor = utf8_encode($servicos[0]['nome']);
							break;
							case '@e-services#descricao':
								$mudar_valor = utf8_encode($servicos[0]['descricao']);
							break;
							case '@e-services#preco':
								$mudar_valor = 'R$ ' . preparar_float_4_texto($servicos[0]['preco']);
							break;
							
						}
						
						if($mudar_valor)
							$widget->childNodes->item(0)->childNodes->item(0)->nodeValue = $mudar_valor;
					break;
					case 'imagem':
						$mudar_valor = false;
						
						switch($widget->attributes->getNamedItem('data-marcador')->value){
							case '@e-services#imagem':
								$mudar_valor = $_B2MAKE_URL . 'site/images/b2make-album-sem-imagem.png';
								if($servicos[0]['imagem_path'])$mudar_valor = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
							break;
						}
						
						if($mudar_valor)
							$widget->childNodes->item(0)->attributes->getNamedItem('src')->value = $mudar_valor;
					break;
					case 'download':
						$mudar_valor = false;
						
						switch($widget->attributes->getNamedItem('data-marcador')->value){
							case '@e-services#comprar':
								$mudar_valor = '#';
							break;
						}
						
						if($mudar_valor){
							$serviceComprar = $widget->childNodes->item(0)->childNodes->item(0);
							
							$serviceComprar->attributes->getNamedItem('href')->value = $mudar_valor;
							
							$serviceComprar->attributes->getNamedItem('class')->value = $serviceComprar->attributes->getNamedItem('class')->value . ' b2make-service-inserir-carrinho';
							
							$dataId = $dom->createAttribute('data-id');
							$dataId->value = $id_servicos;
							$dataValidade = $dom->createAttribute('data-validade');
							$dataValidade->value = $servicos[0]['validade'];
							$dataPreco = $dom->createAttribute('data-preco');
							$dataPreco->value = $servicos[0]['preco'];
							$dataNome = $dom->createAttribute('data-nome');
							$dataNome->value = utf8_encode($servicos[0]['nome']);
							$dataDescricao = $dom->createAttribute('data-descricao');
							$dataDescricao->value = utf8_encode($servicos[0]['descricao']);
							$dataQuantidade = $dom->createAttribute('data-quantidade');
							$dataQuantidade->value = utf8_encode($servicos[0]['quantidade']);
					
							if(!$servicos[0]['quantidade'] || (int)$servicos[0]['quantidade'] == 0)$serviceComprar->childNodes->item(0)->childNodes->item(0)->nodeValue = 'Indispon&iacute;vel';
							
							$serviceComprar->appendChild($dataId);
							$serviceComprar->appendChild($dataValidade);
							$serviceComprar->appendChild($dataPreco);
							$serviceComprar->appendChild($dataNome);
							$serviceComprar->appendChild($dataDescricao);
							$serviceComprar->appendChild($dataQuantidade);
							if($serviceComprar->hasAttribute('target'))$serviceComprar->removeAttribute('target');
						}
					break;
					
				}
			}
			
			$html = $dom->saveHTML();
			//$debug = $dom->saveHTML();
			
			$google_fontes = '';
			
			if($google_fonts_loaded){
				for($i=0;$i<count($google_fonts_loaded);$i++){
					$google_fontes = $google_fontes . ($google_fontes ? '|' : '') . preg_replace('/ /i', '+', $google_fonts_loaded[$i]);
				}
			}
			
			//$_DEBUG_CONT = $debug;
			
			$html = preg_replace('/\r\n|\r|\n/i', '<br>', $html);
			$html = preg_replace('/  /i', '&nbsp;&nbsp;', $html);
			$html = preg_replace('/<html><body>/i', '', $html);
			$html = preg_replace('/<\/body><\/html>/i', '', $html);
			$html = preg_replace('/<br>$/i', '', $html);
			
			banco_update
			(
				"html='".addslashes($html)."'",
				"site",
				"WHERE id_site='".$id_site."'"
			);
			
			servico_publicar_ftp(Array(
				'html' => $html,
				'json_services_list' => $json_services_list,
				'google_fontes' => $google_fontes,
				'servico_nome' => $servicos[0]['nome'],
				'id_site' => $id_site,
			));
		} else {
			$_ALERTA = 'Esse serviço não pertence a sua id_loja: '.$id_loja;
		}	
	} else {
		$_ALERTA = 'Não existe site para o id_usuario: '.$id_usuario;
	}	
}

function servico_remover_pagina($params = false){
	global $_SYSTEM;
	global $_DEBUG_CONT;
	global $_B2MAKE_URL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = $usuario['id_usuario'];
	$id_loja = $usuario['id_loja'];
	
	$servicos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'descricao',
			'imagem_path',
			'preco',
			'id_site',
		))
		,
		"servicos",
		"WHERE id_servicos='".$id_servicos."'"
		." AND id_loja='".$id_loja."'"
	);
	
	if($servicos){
		$id_site = $servicos[0]['id_site'];
		
		// Publicar Lista de Serviços
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
				'dominio_proprio',
				'services_list',
			))
			,
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS TRUE"
		);
		
		$path = site_pagina_diretorio($id_site,false,true);
		$url = $host[0]['url'] . $path;
		$services_list = json_decode($host[0]['services_list'],true);
		
		if($services_list){
			foreach($services_list as $num => $service){
				if($service['id'] == $id_servicos){
					$service_index = $num;
					$service_found = true;
					break;
				}
			}
		} else {
			$services_list = Array();
		}
		
		if($service_found){
			unset($services_list[$service_index]);
		}
		
		if($services_list){
			foreach($services_list as $service){
				$services_list_proc[] = $service;
			}
			
			$services_list = $services_list_proc;
		} else {
			$services_list = Array();
		}
		
		$json_services_list = json_encode($services_list);
		
		banco_update
		(
			"services_list='".addslashes($json_services_list)."'",
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS NOT NULL"
		);
		
		if($id_site)
		servico_remover_ftp(Array(
			'bloqueio' => $bloqueio,
			'id_site' => $id_site,
			'json_services_list' => $json_services_list,
		));
	} else {
		$_ALERTA = 'Esse serviço não pertence a sua id_loja: '.$id_loja;
	}
}

function servico_publicar_ftp($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$html = preg_replace('/&lt;/i', '<', $html);
	$html = preg_replace('/&gt;/i', '>', $html);
	$html = preg_replace('/<!--script-->/i', '</script>', $html);
	
	if($html){
		if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
			'manual' => true,
			'host' => $_SYSTEM['SITE']['ftp-site-host'],
			'user' => $_SYSTEM['SITE']['ftp-site-user'],
			'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
		));
		
		if($_CONEXAO_FTP){
			$pagina = modelo_abrir($_SYSTEM['PATH'].'site'.$_SYSTEM['SEPARADOR'].'layout-site.html');
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
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
				))
				,
				"site",
				"WHERE id_site='".$id_site."'"
			);
			
			if($resultado){
				if($resultado[0]['id'])$id = $resultado[0]['id'];
				if($resultado[0]['pagina_titulo'])$pagina_titulo = $resultado[0]['pagina_titulo'];
				if($resultado[0]['pagina_description'])$meta .= "	<meta name=\"description\" content=\"".$resultado[0]['pagina_description']."\">\n";
				if($resultado[0]['pagina_keywords'])$meta .= "	<meta name=\"keywords\" content=\"".$resultado[0]['pagina_keywords']."\">\n";
				if($resultado[0]['pagina_head_extra'])$meta .= "	".$resultado[0]['pagina_head_extra']."\n";
				if($resultado[0]['data_criacao'])$meta .= "	<meta http-equiv=\"date\" content=\"".date("D M d Y H:i:s \G\M\TO",strtotime($resultado[0]['data_criacao']))."\">\n";
				if($resultado[0]['data_modificacao'])$meta .= "	<meta http-equiv=\"last-modified\" content=\"".date("D M d Y H:i:s \G\M\TO",strtotime($resultado[0]['data_modificacao']))."\">\n";
			}
			
			$meta .= "	<meta http-equiv=\"cache-control\" content=\"max-age=0\" />\n";
			$meta .= "	<meta http-equiv=\"cache-control\" content=\"no-cache\" />\n";
			$meta .= "	<meta http-equiv=\"expires\" content=\"0\" />\n";
			$meta .= "	<meta http-equiv=\"expires\" content=\"Tue, 01 Jan 1980 1:00:00 GMT\" />\n";
			$meta .= "	<meta http-equiv=\"pragma\" content=\"no-cache\" />\n";
			
			if($resultado[0]['id_site_pai']){
				$resultado2 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'pagina_favicon',
						'pagina_favicon_version',
					))
					,
					"site",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND id_site_pai IS NULL"
				);
				
				if($resultado2[0]['pagina_favicon']){
					$resultado[0]['pagina_favicon'] = $resultado2[0]['pagina_favicon'];
					$resultado[0]['pagina_favicon_version'] = $resultado2[0]['pagina_favicon_version'];
				}
			}
			
			if($resultado[0]['pagina_favicon']) $favicon = '	<link rel="shortcut icon" href="'.$_SYSTEM['SITE']['url-files'].$_SYSTEM['SITE']['ftp-files-imagens-path'].'/favicon.ico'.($resultado[0]['pagina_favicon_version'] ? '?v='.$resultado[0]['pagina_favicon_version'] : '').'">'."\n";
			if($google_fontes) $google_fontes = '	<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family='.$google_fontes.'">'."\n";
			
			$pagina = modelo_var_troca($pagina,"#b2make-head-title#",($pagina_titulo?$pagina_titulo:$servico_nome));
			$pagina = modelo_var_troca($pagina,"<!-- b2make-meta -->",$meta);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-css -->",$favicon.$google_fontes.$_SYSTEM['SITE']['b2make-css']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-jquery -->",$_SYSTEM['SITE']['jquery']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-js -->",$_SYSTEM['SITE']['b2make-js']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-js-extra -->",$_SYSTEM['SITE']['js-extra']);
			$pagina = modelo_var_troca($pagina,"#b2make-body#",$html);

			if($_SYSTEM['SITE']['ftp-site-path'])ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-site-path']);
			
			$nome_file = 'index.html';
			$tmp_file = $_SYSTEM['TMP'].'pagina-tmp'.session_id();
			file_put_contents($tmp_file, $pagina);
			
			$path = site_pagina_diretorio($id_site);
			
			ftp_put_file($nome_file, $tmp_file);
			
			// Guardar lista em outro arquivo para widgets lerem este arquivo
			
			ftp_cdup($_CONEXAO_FTP);
			
			file_put_contents($tmp_file, $json_services_list);
			ftp_put_file('services-list.json', $tmp_file);
			
			// Instalar JSON de configuração e Página Serviço caso não tenham sido criados ainda.
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site',
				))
				,
				"site",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND id='servicos'"
				." AND publicado IS NULL"
			);
			
			if($resultado){
				$pagina = modelo_abrir($_SYSTEM['PATH'].'site'.$_SYSTEM['SEPARADOR'].'layout-site.html');
				$pagina_titulo = false;
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
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
						'html',
					))
					,
					"site",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND id='servicos'"
				);
				
				if($resultado){
					$html = '';
					if($resultado[0]['html'])$html = $resultado[0]['html'];
					if($resultado[0]['id'])$id = $resultado[0]['id'];
					if($resultado[0]['pagina_titulo'])$pagina_titulo = $resultado[0]['pagina_titulo'];
					if($resultado[0]['pagina_description'])$meta .= "	<meta name=\"description\" content=\"".$resultado[0]['pagina_description']."\">\n";
					if($resultado[0]['pagina_keywords'])$meta .= "	<meta name=\"keywords\" content=\"".$resultado[0]['pagina_keywords']."\">\n";
					if($resultado[0]['pagina_head_extra'])$meta .= "	".$resultado[0]['pagina_head_extra']."\n";
					if($resultado[0]['data_criacao'])$meta .= "	<meta http-equiv=\"date\" content=\"".date("D M d Y H:i:s \G\M\TO",strtotime($resultado[0]['data_criacao']))."\">\n";
					if($resultado[0]['data_modificacao'])$meta .= "	<meta http-equiv=\"last-modified\" content=\"".date("D M d Y H:i:s \G\M\TO",strtotime($resultado[0]['data_modificacao']))."\">\n";
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
						"WHERE id_usuario='".$usuario['id_usuario']."'"
						." AND id_site_pai IS NULL"
					);
					
					if($resultado2[0]['pagina_favicon']){
						$resultado[0]['pagina_favicon'] = $resultado2[0]['pagina_favicon'];
						$resultado[0]['pagina_favicon_version'] = $resultado2[0]['pagina_favicon_version'];
					}
				}
				
				if($resultado[0]['pagina_favicon']) $favicon = '	<link rel="shortcut icon" href="'.$_SYSTEM['SITE']['url-files'].$_SYSTEM['SITE']['ftp-files-imagens-path'].'/favicon.ico'.($resultado[0]['pagina_favicon_version'] ? '?v='.$resultado[0]['pagina_favicon_version'] : '').'">'."\n";
				
				$pagina = modelo_var_troca($pagina,"#b2make-head-title#",($pagina_titulo?$pagina_titulo:$servico_nome));
				$pagina = modelo_var_troca($pagina,"<!-- b2make-meta -->",$meta);
				$pagina = modelo_var_troca($pagina,"<!-- b2make-css -->",$favicon.$_SYSTEM['SITE']['b2make-css']);
				$pagina = modelo_var_troca($pagina,"<!-- b2make-jquery -->",$_SYSTEM['SITE']['jquery']);
				$pagina = modelo_var_troca($pagina,"<!-- b2make-js -->",$_SYSTEM['SITE']['b2make-js']);
				$pagina = modelo_var_troca($pagina,"<!-- b2make-js-extra -->",$_SYSTEM['SITE']['js-extra']);
				$pagina = modelo_var_troca($pagina,"#b2make-body#",$html);

				$nome_file = 'index.html';
				$tmp_file = $_SYSTEM['TMP'].'pagina-tmp'.session_id();
				file_put_contents($tmp_file, $pagina);
				
				ftp_put_file($nome_file, $tmp_file);
				
				// Instalar arquivo de configuração
				
				$json_config = json_encode(Array(
					'pub_id' => $usuario['pub_id'],
				));
				
				file_put_contents($tmp_file, $json_config);
				ftp_put_file('config.json', $tmp_file);
				
				$campo_tabela = "site";
				$campo_tabela_extra = "WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND id='servicos'";
				
				$campo_nome = "publicado"; $campo_valor = $campo_valor; $editar[$campo_tabela][] = $campo_nome."=1";
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
			}
			
			ftp_fechar_conexao();
			
			unlink($tmp_file);
			
			$campo_tabela = "site";
			$campo_tabela_extra = "WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND id_site='".$id_site."'";
			
			$campo_nome = "publicado"; $campo_valor = $campo_valor; $editar[$campo_tabela][] = $campo_nome."=1";
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
		}
	}
}

function servico_remover_ftp($params = false){
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
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND id_site='".$filho['id_site']."'"
				);
			}
			
			// Guardar lista em outro arquivo para widgets lerem este arquivo
			
			$tmp_file = $_SYSTEM['TMP'].'pagina-tmp'.session_id();
			file_put_contents($tmp_file, $json_services_list);
			ftp_put_file('services-list.json', $tmp_file);
			
			ftp_fechar_conexao();
		} else if($_B2MAKE_PAGINA_LOCAL){
			foreach($filhos as $filho){
				if(!$bloqueio)
				banco_delete
				(
					"site",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND id_site='".$filho['id_site']."'"
				);
			}
		}
	}
}

function servico_pagina_add($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_host',
		))
		,
		"host",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND atual IS TRUE"
	);
	
	$id_host = $host[0]['id_host'];
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND id_site_pai IS NULL"
	);
	
	$id_site_raiz = $site[0]['id_site'];
	
	$servicos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND id='servicos'"
		." AND id_site_pai='".$id_site_raiz."'"
	);
	
	if(!$servicos){
		$campos = null;
		
		$html = modelo_abrir($_SYSTEM['PATH'].'e-service'.$_SYSTEM['SEPARADOR'].'pagina-servicos-lista.html');
		
		$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_pai"; $campo_valor = $id_site_raiz; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = 'Serviços'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pagina_titulo"; $campo_valor = 'Serviços'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = 'servicos'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html"; $campo_valor = utf8_decode($html); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"site"
		);
		
		$pai_id = banco_last_id();
	} else {
		$pai_id = $servicos[0]['id_site'];
	}
	
	if($pai_id){
		$id = site_criar_identificador($nome,$usuario['id_usuario'],false,$pai_id);
		
		$campos = null;
		
		$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_pai"; $campo_valor = $pai_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
			"servicos",
			"WHERE id_servicos='".$id_servicos."'"
		);
		
		servico_publicar_pagina(Array(
			'id_servicos' => $id_servicos,
			'id_site' => $id_site,
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	}
	
	return $saida;
}

function servico_pagina_edit($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND id_site_pai IS NULL"
	);
	
	$id_site_raiz = $site[0]['id_site'];
	
	$servicos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND id='servicos'"
		." AND id_site_pai='".$id_site_raiz."'"
	);
	
	$pai_id = $servicos[0]['id_site'];
	
	if($id_site){
		$id = site_criar_identificador($nome,$usuario['id_usuario'],$id_site,$pai_id);
		
		$campo_tabela = "site";
		$campo_tabela_extra = "WHERE id_site='".$id_site."'";
		
		$campo_nome = "nome"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "id"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "data_modificacao"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."=NOW()";
		
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
		
		servico_publicar_pagina(Array(
			'id_servicos' => $id_servicos,
			'id_site' => $id_site,
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	}
	
	return $saida;
}

function servico_pagina_excluir($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"servicos",
		"WHERE id_loja='".$usuario['id_loja']."'"
		." AND id_servicos='".$id_servicos."'"
	);
	
	$id_site = $site[0]['id_site'];
	
	if($id_site){
		servico_remover_pagina(Array(
			'id_servicos' => $id_servicos,
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	}
}

function servico_pagina_bloqueio($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"servicos",
		"WHERE id_servicos='".$id_servicos."'"
		." AND id_loja='".$usuario['id_loja']."'"
	);
	
	$id_site = $resultado[0]['id_site'];
	
	switch($status){
		case 'A':
			servico_publicar_pagina(Array(
				'id_servicos' => $id_servicos,
				'id_site' => $id_site,
			));
		break;
		case 'B':
			servico_remover_pagina(Array(
				'id_servicos' => $id_servicos,
				'bloqueio' => true,
			));
		break;
		
	}
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
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'desconto_de';
	$tabela_campos[] = 'desconto_ate';
	$tabela_campos[] = 'visivel_de';
	$tabela_campos[] = 'visivel_ate';
	$tabela_campos[] = 'quantidade';
	$tabela_campos[] = 'preco';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
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
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Adicionar', // Nome do menu
		);
	}
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
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
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Desconto De', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Desconto Até', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Visível De', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Visível Até', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Quantidade', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Preço (R$)', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'dinheiro' => true, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => 'E-Service / ' . $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => operacao('buscar'), // Formulário de busca
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
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_loja='".$usuario['id_loja']."'", // Tabela extra
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
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
	
	if($_REQUEST['site']){
		$more_options = 'widget_id='.$_REQUEST['widget_id'];
	}
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,"#url",'A definir');
	$pagina = paginaTrocaVarValor($pagina,"#nome",$nome);
	$pagina = paginaTrocaVarValor($pagina,"#descricao",$descricao);
	$pagina = paginaTrocaVarValor($pagina,"#visivel_de#",$visivel_de);
	$pagina = paginaTrocaVarValor($pagina,"#visivel_ate#",$visivel_de);
	$pagina = paginaTrocaVarValor($pagina,"#validade#",$visivel_de);
	$pagina = paginaTrocaVarValor($pagina,"#imagem_path#",$imagem_path);
	$pagina = paginaTrocaVarValor($pagina,"#quantidade#",$quantidade);
	$pagina = paginaTrocaVarValor($pagina,"#preco#",$preco);
	$pagina = paginaTrocaVarValor($pagina,"#desconto#",$desconto);
	$pagina = paginaTrocaVarValor($pagina,"#desconto_de#",$desconto_de);
	$pagina = paginaTrocaVarValor($pagina,"#desconto_ate#",$desconto_ate);
	$pagina = paginaTrocaVarValor($pagina,"#observacao#",$observacao);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	$pagina = paginaTrocaVarValor($pagina,"#more_options",$more_options);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE_OPCAO = 'add';
	
	return interface_layout(parametros_interface());
}

function add_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	banco_conectar();
	
	$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "observacao"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "visivel_de"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "visivel_ate"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "validade"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "descricao"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "quantidade"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "preco"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,preparar_texto_4_float($_POST[$post_nome]));
	$campo_nome = "desconto"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,((int)$_POST[$campo_nome] > 100 ? 100 :$_POST[$campo_nome]));
	$campo_nome = "desconto_de"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "desconto_ate"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "id_loja"; $campo_valor = $usuario['id_loja']; 			$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	$campo_nome = "versao"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'1');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$id_tabela = banco_last_id();
	
	guardar_arquivo($_FILES['imagem_path'],'imagem','imagem_path',$id_tabela);
	servico_pagina_add(Array(
		'nome' => $_POST['nome'],
		'id_servicos' => $id_tabela,
	));
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	if($_REQUEST['more_options']){
		header('Location: ../../site/?'.$_REQUEST['more_options']);
	} else {
		return lista();
	}
}

function editar($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		banco_conectar();
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'descricao',
				'visivel_de',
				'visivel_ate',
				'validade',
				'imagem_path',
				'quantidade',
				'preco',
				'desconto',
				'desconto_de',
				'desconto_ate',
				'observacao',
				'id_site',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		if($tabela){
			// ================================= Local de Edição ===============================
			// Pegue os campos da interface e campos_guardar aqui
			
			$tabela[0]['visivel_de'] = data_from_date_to_text($tabela[0]['visivel_de']);
			$tabela[0]['visivel_ate'] = data_from_date_to_text($tabela[0]['visivel_ate']);
			$tabela[0]['desconto_de'] = data_from_date_to_text($tabela[0]['desconto_de']);
			$tabela[0]['desconto_ate'] = data_from_date_to_text($tabela[0]['desconto_ate']);
			
			$campos_guardar = Array(
				'id_site' => $tabela[0]['id_site'],
				'nome' => $tabela[0]['nome'],
				'observacao' => $tabela[0]['observacao'],
				'visivel_de' => $tabela[0]['visivel_de'],
				'visivel_ate' => $tabela[0]['visivel_ate'],
				'desconto_de' => $tabela[0]['desconto_de'],
				'desconto_ate' => $tabela[0]['desconto_ate'],
				'validade' => $tabela[0]['validade'],
				'descricao' => $tabela[0]['descricao'],
				'imagem_path' => $tabela[0]['imagem_path'],
				'quantidade' => $tabela[0]['quantidade'],
				'desconto' => $tabela[0]['desconto'],
				'preco' => preparar_float_4_texto($tabela[0]['preco']),
			);
			
			$remover = '<div><a href="#link#"><img src="../../images/icons/db_remove.png" alt="Remover" width="32" height="32" border="0" title="Clique para remover esse ítem" /></a></div>';
			
			$tabela[0]['descricao'] = preg_replace('/\r\n/i', '&#13;&#10;', $tabela[0]['descricao']);
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'dominio_proprio',
				))
				,
				"host",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND atual IS TRUE"
			);
			
			$path = site_pagina_diretorio($tabela[0]['id_site'],false,true);
			
			$url = $host[0]['url'] . $path;
			if($host[0]['dominio_proprio'])$dominio_proprio = $host[0]['dominio_proprio'] . $path;
			
			$pagina = paginaTrocaVarValor($pagina,'#url','<a href="'.$url.'" target="_blank">'.$url.'</a>'.($dominio_proprio?'<br><a href="'.$dominio_proprio.'" target="_blank">'.$dominio_proprio.'</a> OBS: É necessário que o seu domínio esteja configurado corretamente para funcionar esta opção. Caso não funcione, é necessário entrar em contato com o suporte para saber como proceder.':''));
			$pagina = paginaTrocaVarValor($pagina,'#nome',$tabela[0]['nome']);
			$pagina = paginaTrocaVarValor($pagina,'#observacao#',$tabela[0]['observacao']);
			$pagina = paginaTrocaVarValor($pagina,'#visivel_de#',$tabela[0]['visivel_de']);
			$pagina = paginaTrocaVarValor($pagina,'#visivel_ate#',$tabela[0]['visivel_ate']);
			$pagina = paginaTrocaVarValor($pagina,'#desconto_de#',$tabela[0]['desconto_de']);
			$pagina = paginaTrocaVarValor($pagina,'#desconto_ate#',$tabela[0]['desconto_ate']);
			$pagina = paginaTrocaVarValor($pagina,'#validade#',$tabela[0]['validade']);
			$pagina = paginaTrocaVarValor($pagina,'#descricao',$tabela[0]['descricao']);
			$pagina = paginaTrocaVarValor($pagina,'#imagem_path#',($tabela[0]['imagem_path']?modelo_var_troca($remover,"#link#",'?opcao=remover_item&item=imagem_path&id='.$id).'<img src="'.path_com_versao_arquivo($tabela[0]['imagem_path']).'">':''));
			$pagina = paginaTrocaVarValor($pagina,'#quantidade#',$tabela[0]['quantidade']);
			$pagina = paginaTrocaVarValor($pagina,'#desconto#',$tabela[0]['desconto']);
			$pagina = paginaTrocaVarValor($pagina,'#preco#',preparar_float_4_texto($tabela[0]['preco']));
			
			// ======================================================================================
			
			banco_fechar_conexao();
			
			campos_antes_guardar($campos_guardar);
			
			$in_titulo = $param ? "Visualizar" : "Modificar";
			$botao = "Gravar";
			$opcao = "editar_base";
			
			if($_REQUEST['site']){
				$more_options = 'widget_id='.$_REQUEST['widget_id'];
			}
			
			$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
			$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
			$pagina = paginaTrocaVarValor($pagina,"#id",$id);
			$pagina = paginaTrocaVarValor($pagina,"#more_options",$more_options);
			
			if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			$_INTERFACE_OPCAO = 'editar';
			$_INTERFACE['local'] = 'conteudo';
			$_INTERFACE['informacao_titulo'] = $in_titulo;
			$_INTERFACE['informacao_tipo'] = $tipo;
			$_INTERFACE['informacao_id'] = $id;
			$_INTERFACE['inclusao'] = $pagina;
		
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
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($id){
		$campos_antes = campos_antes_recuperar();
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "observacao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "visivel_de"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "visivel_ate"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "desconto_de"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "desconto_ate"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "validade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "quantidade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "desconto"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . ((int)$_POST[$campo_nome] > 100 ? 100 :$_POST[$campo_nome]) . "'";}
		$campo_nome = "preco"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . preparar_texto_4_float($_POST[$campo_nome]) . "'";}
		$campo_nome = "versao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=versao+1";}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
				." AND id_loja='".$usuario['id_loja']."'"
			);
		}
		
		if($_FILES['imagem_path']['size'] != 0)		{guardar_arquivo($_FILES['imagem_path'],'imagem','imagem_path',$id,$campos_antes['imagem_path']);}
		
		servico_pagina_edit(Array(
			'id_servicos' => $id,
			'id_site' => $campos_antes['id_site'],
			'nome' => $_POST['nome'],
		));
		
		// ======================================================================================
		
		banco_fechar_conexao();
	}
	
	if($_REQUEST['more_options']){
		header('Location: ../../site/?'.$_REQUEST['more_options']);
	} else {
		return lista();
	}
}

function excluir(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		banco_conectar();
		banco_update
		(
			$_LISTA['tabela']['status']."='D'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		servico_pagina_excluir(Array(
			'id_servicos' => $id
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
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		servico_pagina_bloqueio(Array(
			'id_servicos' => $id,
			'status' => $status,
		));
		banco_fechar_conexao();
	}
	
	return lista();
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."servicos".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/servicos/";
	
	if(!is_dir($caminho_fisico)){
		mkdir($caminho_fisico);
		chmod($caminho_fisico , 0777);
	}
	
	if(
		$uploaded['size'] != 0
	){
		switch($tipo){
			case 'imagem':
				if
				(
					$uploaded['type'] == mime_types("jpe") ||
					$uploaded['type'] == mime_types("jpeg") ||
					$uploaded['type'] == mime_types("jpg") ||
					$uploaded['type'] == mime_types("pjpeg") ||
					$uploaded['type'] == mime_types("png") ||
					$uploaded['type'] == mime_types("x-png") ||
					$uploaded['type'] == mime_types("swf") ||
					$uploaded['type'] == mime_types("gif")
				){
					$cadastrar = true;
				}
			break;
			case 'musica':
				if
				(
					$uploaded['type'] == mime_types("mp3") ||
					$uploaded['type'] == mime_types("mp3_2")
				){
					$cadastrar = true;
				}
			break;
			case 'video':
				if
				(
					$uploaded['type'] == mime_types("flv") ||
					$uploaded['type'] == mime_types("mp4")
				){
					$cadastrar = true;
				}
			break;
		}
	}
	
	if($cadastrar){
		if
		(
			$uploaded['type'] == mime_types("jpe") ||
			$uploaded['type'] == mime_types("jpeg") ||
			$uploaded['type'] == mime_types("pjpeg") ||
			$uploaded['type'] == mime_types("jpg")
		){
			$extensao = "jpg";
		} else if
		(
			$uploaded['type'] == mime_types("png") ||
			$uploaded['type'] == mime_types("x-png") 
		){
			$extensao = "png";
		} else if
		(
			$uploaded['type'] == mime_types("gif")
		){
			$extensao = "gif";
		} else if
		(
			$uploaded['type'] == mime_types("swf")
		){
			$extensao = "swf";
		} else if
		(
			$uploaded['type'] == mime_types("mp3") ||
			$uploaded['type'] == mime_types("mp3_2")
		){
			$extensao = "mp3";
		} else if
		(
			$uploaded['type'] == mime_types("flv")
		){
			$extensao = "flv";
		}  else if
		(
			$uploaded['type'] == mime_types("mp4")
		){
			$extensao = "mp4";
		} 
		
		$nome_arquivo = $campo . $id_tabela . "." . $extensao;
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			$original = $caminho_fisico . $nome_arquivo;
			
			$new_width = $_SYSTEM['IMG_MINI_WIDTH'];
			$new_height = $_SYSTEM['IMG_MINI_HEIGHT'];
			
			if($_PROJETO['servicos']){
				if($_PROJETO['servicos']['new_width']) $new_width = $_PROJETO['servicos']['new_width'];
				if($_PROJETO['servicos']['new_height']) $new_height = $_PROJETO['servicos']['new_height'];
				if($_PROJETO['servicos']['recorte_y']) $_RESIZE_IMAGE_Y_ZERO = true;
			}
			
			resize_image($original, $original, $new_width, $new_height,false,false,true);
		}
		
		banco_update
		(
			$campo."='".$caminho_internet.$nome_arquivo."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id_tabela."'"
		);
	}
}

function remover_item(){
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	global $_LISTA;
	
	$id = $_REQUEST['id'];
	$item = $_REQUEST['item'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."servicos".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/servicos/";
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($item && $id){
		if(
			$item == 'imagem_path'
		){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$item,
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
				." AND id_loja='".$usuario['id_loja']."'"
			);
			
			if($resultado){
				banco_update
				(
					$item."=NULL",
					$_LISTA['tabela']['nome'],
					"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
				);
			
				$resultado[0][$item] = str_replace($caminho_internet,$caminho_fisico,$resultado[0][$item]);
				if(is_file($resultado[0][$item]))unlink($resultado[0][$item]);
				alerta("Ítem removido com sucesso!");
			} else {
				alerta("Não é possível remover, essa imagem não faz parte do seu usuário!");
			}
		}	
		
		
		return editar();
	}
}

// ======================================================================================

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
		
		$dom = new DOMDocument("1.0", "ISO-8859-1");
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		
		$mp3player = $dom->appendChild(new DOMElement('mp3player'));
		
		$mp3 = $mp3player->appendChild(new DOMElement('mp3'));
		$attr = $mp3->setAttributeNode(new DOMAttr('id', 1));
		
		$title = $mp3->appendChild(new DOMElement('title',utf8_encode($conteudo[0]['titulo'])));
		$artist = $mp3->appendChild(new DOMElement('artist',utf8_encode($conteudo[0]['sub_titulo'])));
		$url = $mp3->appendChild(new DOMElement('url',utf8_encode($_HTML['separador'].$conteudo[0]['musica'])));
		
		header("Content-Type: text/xml");
		echo $dom->saveXML();
	}
}

function ajax(){
	global $_SYSTEM;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		banco_fechar_conexao();

		for($i=0;$i<count($resultado);$i++){
			$saida[] = Array(
				'value' => utf8_encode($resultado[$i][1]),
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	return utf8_encode($saida);
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