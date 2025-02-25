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

$_VERSAO_MODULO				=	'2.0.1';
$_LOCAL_ID					=	"services";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_LOJA				=	true;
$_INCLUDE_PUBLISHER			=	true;
$_INCLUDE_SITE				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
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

$_HTML['titulo'] 						= 	$_HTML['titulo']."Serviços.";
$_HTML['variaveis']['titulo-modulo']	=	'Serviços';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'].
$_JS['daterange'];

$_HTML['js'] .= 
"	<link href=\"jquery-file-upload/jquery.fileupload.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"	<script type=\"text/javascript\" src=\"jquery-file-upload/jquery.iframe-transport.js?v=".$_VERSAO_MODULO."\"></script>\n".
"	<script type=\"text/javascript\" src=\"jquery-file-upload/jquery.fileupload.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'servicos';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'servicos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Serviços';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// B2make

function appendHTML(DOMNode $parent, $source) {
    $tmpDoc = new DOMDocument();
    $tmpDoc->loadHTML($source);
    foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
        $node = $parent->ownerDocument->importNode($node);
        $parent->appendChild($node);
    }
}

function servico_publicar_pagina($params = false){
	global $_SYSTEM;
	global $_DEBUG_CONT;
	global $_B2MAKE_URL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	$id_loja = $usuario['id_loja'];
	
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
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'html',
			'html_mobile',
		))
		,
		"site",
		"WHERE id_usuario='".$id_usuario."'"
		." AND id='pagina-de-servicos'"
	);
	
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
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'html_mobile',
			))
			,
			"site",
			"WHERE id_usuario='".$id_usuario."'"
			." AND id='pagina-de-servicos'"
		);
	}
	
	$servicos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'descricao',
			'imagem_path',
			'imagem_path_mini',
			'imagem_biblioteca',
			'imagem_biblioteca_id',
			'preco',
			'versao',
			'validade',
			'validade_data',
			'validade_tipo',
			'quantidade',
			'titulo_personalizado',
			'lote',
		))
		,
		"servicos",
		"WHERE id_servicos='".$id_servicos."'"
		." AND id_loja='".$id_loja."'"
	);
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'parcelamento',
			'parcelamento_valor_minimo',
			'parcelamento_maximo_parcelas',
			'parcelamento_modelo_informativo',
		))
		,
		"loja",
		"WHERE id_loja='".$id_loja."'"
	);
	
	if($resultado){
		if($servicos){
			// Publicar Lista de Serviços
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'url_mobile',
					'url_files',
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
			$url = ($host[0]['dominio_proprio'] ? '//'.$host[0]['dominio_proprio'].'/' : str_replace('http:','',$host[0]['url'])) . $path;
			$url_mobile = '//'.rtrim($host[0]['url_mobile'], '/').'/'.$path;
			
			if($servicos[0]['imagem_biblioteca']){
				if($servicos[0]['imagem_biblioteca_id']){
					$servicos_biblioteca_imagens = banco_select_name
					(
						banco_campos_virgulas(Array(
							'file',
						))
						,
						"servicos_biblioteca_imagens",
						"WHERE id_usuario='".$id_usuario."'"
						." AND id_servicos_biblioteca_imagens='".$servicos[0]['imagem_biblioteca_id']."'"
					);
					
					$url_imagem = http_define_ssl($host[0]['url_files']) . $_SYSTEM['SITE']['ftp-files-services-path'] . '/mini/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $servicos[0]['versao'];
				}
			} else {
				if($servicos[0]['imagem_path_mini'])$url_imagem = $_B2MAKE_URL . $servicos[0]['imagem_path_mini'] . '?v='. $servicos[0]['versao'];
			}
			
			if($servicos[0]['validade_data']) $data = data_hora_from_datetime($servicos[0]['validade_data']);
			
			if($data){
				$servicos[0]['validade_data'] = $data[0];
				$servicos[0]['validade_hora'] = $data[1];
			}
			
			// Lote 
			
			if($servicos[0]['lote']){
				$lote = true;
				
				$servicos_lotes = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
						'preco',
					))
					,
					"servicos_lotes",
					"WHERE id_servicos='".$id_servicos."'"
					." AND visivel_de <= NOW()"
					." AND visivel_ate >= NOW()"
				);
				
				$lote_nome = $servicos_lotes[0]['nome'];
				$lote_preco = $servicos_lotes[0]['preco'];
			}
			
			// Publicar Página do Serviços
			
			$htmls[] = $resultado[0]['html'];
			if($mobile && $resultado[0]['html_mobile'])$htmls[] = $resultado[0]['html_mobile'];
			$count = 0;
			
			foreach($htmls as $html_aux){
				if($html_aux){
					$google_fonts_loaded = false;
					$dom = new DOMDocument("1.0", "UTF-8");
					$dom->loadHTML(mb_convert_encoding($html_aux, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | HTML_PARSE_NOIMPLIED | LIBXML_HTML_NODEFDTD);
					
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
										$mudar_valor = ($servicos[0]['titulo_personalizado'] ? $servicos[0]['titulo_personalizado'] : $servicos[0]['nome']);
									break;
									case '@e-services#lote-titulo':
										$mudar_valor = ($lote ? $lote_nome : ' ');
									break;
									case '@e-services#descricao':
										$mudar_valor = $servicos[0]['descricao'];
									break;
									case '@e-services#preco':
										$mudar_valor = ($lote ? 'R$ ' . preparar_float_4_texto($lote_preco) : 'R$ ' . preparar_float_4_texto($servicos[0]['preco']));
									break;
									case '@e-services#parcelamento':
										if($loja[0]['parcelamento']){
											$preco = (float)$servicos[0]['preco'];
											$parcelamento_valor_minimo = (float)$loja[0]['parcelamento_valor_minimo'];
											$parcelamento_maximo_parcelas = (int)$loja[0]['parcelamento_maximo_parcelas'];
											$parcelamento_modelo_informativo = $loja[0]['parcelamento_modelo_informativo'];
											
											$parcelamento = parcelamento(Array(
												'valor_total' => $preco,
												'valor_minimo' => $parcelamento_valor_minimo,
												'maximo_parcelas' => $parcelamento_maximo_parcelas,
											));
											
											$parcelamento_modelo_informativo = modelo_var_troca($parcelamento_modelo_informativo,"#QUANT#",$parcelamento['quantidade']);
											$parcelamento_modelo_informativo = modelo_var_troca($parcelamento_modelo_informativo,"#VALOR#",preparar_float_4_texto($parcelamento['valor']));
											
											$mudar_valor = $parcelamento_modelo_informativo;
										} else {
											$mudar_valor = ' ';
										}
									break;
								}
								
								$mudar_valor = preg_replace('/&/i', '&amp;', $mudar_valor);
								
								if($mudar_valor)
									$widget->childNodes->item(0)->childNodes->item(0)->nodeValue = $mudar_valor;
							break;
							case 'imagem':
								$mudar_valor = false;
								
								switch($widget->attributes->getNamedItem('data-marcador')->value){
									case '@e-services#imagem':
										$mudar_valor = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
										if($servicos[0]['imagem_biblioteca']){
											if($servicos[0]['imagem_biblioteca_id']){
												$mudar_valor = http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $servicos[0]['versao'];
											}
										} else {
											if($servicos[0]['imagem_path'])$mudar_valor = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
										}
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
									$dataValidadeData = $dom->createAttribute('data-validade_data');
									$dataValidadeData->value = $servicos[0]['validade_data'];
									$dataValidadeHora = $dom->createAttribute('data-validade_hora');
									$dataValidadeHora->value = $servicos[0]['validade_hora'];
									$dataValidadeTipo = $dom->createAttribute('data-validade_tipo');
									$dataValidadeTipo->value = $servicos[0]['validade_tipo'];
									$dataPreco = $dom->createAttribute('data-preco');
									$dataPreco->value = $servicos[0]['preco'];
									$dataNome = $dom->createAttribute('data-nome');
									$dataNome->value = $servicos[0]['nome'];
									$dataDescricao = $dom->createAttribute('data-descricao');
									$dataDescricao->value = $servicos[0]['descricao'];
									$dataQuantidade = $dom->createAttribute('data-quantidade');
									$dataQuantidade->value = $servicos[0]['quantidade'];
							
									if(!$servicos[0]['quantidade'] || (int)$servicos[0]['quantidade'] == 0)$serviceComprar->childNodes->item(0)->childNodes->item(0)->nodeValue = 'Indispon&iacute;vel';
									
									$serviceComprar->appendChild($dataId);
									$serviceComprar->appendChild($dataValidade);
									$serviceComprar->appendChild($dataValidadeData);
									$serviceComprar->appendChild($dataValidadeHora);
									$serviceComprar->appendChild($dataValidadeTipo);
									$serviceComprar->appendChild($dataPreco);
									$serviceComprar->appendChild($dataNome);
									$serviceComprar->appendChild($dataDescricao);
									$serviceComprar->appendChild($dataQuantidade);
									if($serviceComprar->hasAttribute('target'))$serviceComprar->removeAttribute('target');
								}
							break;
							case 'iframe':
								$iframe = urldecode($widget->attributes->getNamedItem('data-iframe-code')->value);
								
								$widget->childNodes->item(0)->nodeValue = '';
								
								appendHTML($widget->childNodes->item(0),$iframe);
								$widget->removeAttribute('data-iframe-code');
							break;
						}
					}
					
					if($count > 0){
						$finder = new DomXPath($dom);
						$id = "b2make-pagina-options";
						$pagina_options = $finder->query("//*[@id='$id']")->item(0);
						
						$pagina_options->setAttribute('data-device','phone');
					}
					
					$html_aux = $dom->saveHTML();
					//$debug = $dom->saveHTML();
					
					$google_fontes_aux = '';
					
					if($google_fonts_loaded){
						for($i=0;$i<count($google_fonts_loaded);$i++){
							$google_fontes_aux = $google_fontes_aux . ($google_fontes_aux ? '|' : '') . preg_replace('/ /i', '+', $google_fonts_loaded[$i]);
						}
					}
					
					//$_DEBUG_CONT = $debug;
					
					$html_aux = preg_replace('/\r\n|\r|\n/i', '<br>', $html_aux);
					$html_aux = preg_replace('/  /i', '&nbsp;&nbsp;', $html_aux);
					$html_aux = preg_replace('/<html><body>/i', '', $html_aux);
					$html_aux = preg_replace('/<\/body><\/html>/i', '', $html_aux);
					$html_aux = preg_replace('/<br>$/i', '', $html_aux);
					
					if($count > 0){
						$google_fontes_mobile = $google_fontes_aux;
						$html_mobile = $html_aux;
					} else {
						$google_fontes = $google_fontes_aux;
						$html = $html_aux;
					}
					
					$count++;
				}
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
			
			servico_publicar_ftp(Array(
				'html' => $html,
				'google_fontes' => $google_fontes,
				'servico_nome' => $servicos[0]['nome'],
				'id_site' => $id_site,
				'id_servicos' => $id_servicos,
				'titulo_pagina' => ($servicos[0]['titulo_personalizado'] ? $servicos[0]['titulo_personalizado'] : false),
			));
			
			if($mobile && $html_mobile){
				servico_publicar_ftp(Array(
					'html' => $html_mobile,
					'google_fontes' => $google_fontes,
					'servico_nome' => $servicos[0]['nome'],
					'id_site' => $id_site,
					'id_servicos' => $id_servicos,
					'titulo_pagina' => ($servicos[0]['titulo_personalizado'] ? $servicos[0]['titulo_personalizado'] : false),
					'mobile' => $mobile,
				));
			}
			
			publisher_sitemaps();
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
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
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
		
		if($id_site){
			site_delete_page(Array(
				'nao_fechar_ftp' => $nao_fechar_ftp,
				'nao_remover_banco' => $nao_remover_banco,
				'id_site' => $id_site,
			));
		}
		
		// =============== Ativar atualização serviços do host
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.url',
			))
			,
			"loja as t1,host as t2",
			"WHERE t1.id_loja='".$usuario['id_loja']."'"
			." AND t1.id_usuario=t2.id_usuario"
		);
		
		$url = $host[0]['t2.url'] . 'platform/services/' . $id_servicos;
		
		curl_post_async($url);
		
		publisher_sitemaps();
	} else {
		$_ALERTA = 'Esse serviço não pertence a sua id_loja: '.$id_loja;
	}
}

function servico_publicar_ftp($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_HTML_META;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$site = site_publish_page(Array(
		'nao_fechar_ftp' => true,
		'id_site' => $id_site,
		'html' => $html,
		'google_fontes' => $google_fontes,
		'mobile' => $mobile,
		'titulo_pagina' => $titulo_pagina,
		'layout_site' => $_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html',
	));
	
	switch($site['status']){
		case 'Ok':
			// =============== Ativar atualização serviços do host
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					't2.url',
				))
				,
				"loja as t1,host as t2",
				"WHERE t1.id_loja='".$usuario['id_loja']."'"
				." AND t1.id_usuario=t2.id_usuario"
			);
			
			$url = $host[0]['t2.url'] . 'platform/services/' . $id_servicos;
			curl_post_async($url);
	
			// Instalar Página Serviço caso não tenham sido criados ainda.
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site',
				))
				,
				"site",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND id='servicos'"
				." AND publicado IS NULL"
			);
			
			if($resultado){
				ftp_chdir($_CONEXAO_FTP,'/');
				
				$site = site_publish_page(Array(
					'nao_fechar_ftp' => true,
					'html_do_banco' => true,
					'mobile' => $mobile,
					'layout_site' => $_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html',
					'pagina_where' => "WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"." AND id='servicos'",
				));
			}
			
			ftp_fechar_conexao();
		break;
		case 'FtpNotConnected':
			
		break;
		case 'HtmlNull':
			
		break;
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
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
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
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
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
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id='servicos'"
		." AND id_site_pai='".$id_site_raiz."'"
	);
	
	if(!$servicos){
		$campos = null;
		
		$html = modelo_abrir($_SYSTEM['PATH'].'store'.$_SYSTEM['SEPARADOR'].'pagina-servicos-lista.html');
		$html_mobile = modelo_abrir($_SYSTEM['PATH'].'store'.$_SYSTEM['SEPARADOR'].'pagina-servicos-lista-mobile.html');
		
		$html = modelo_var_troca($html,"#titulo#",'Servi&ccedil;os');
		$html_mobile = modelo_var_troca($html_mobile,"#titulo#",'Servi&ccedil;os');
		$html = modelo_var_troca($html,"#service-data#",' data-categoria="todos-servicos"');
		$html_mobile = modelo_var_troca($html_mobile,"#service-data#",' data-categoria="todos-servicos"');
		
		$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_pai"; $campo_valor = $id_site_raiz; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = 'Serviços'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pagina_titulo"; $campo_valor = 'Serviços'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = 'servicos'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html_mobile"; $campo_valor = $html_mobile; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html_mobile_saved"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "google_fontes"; $campo_valor = 'Open+Sans'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "google_fontes_mobile"; $campo_valor = 'Open+Sans'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
		$campo_nome = "meta_robots"; if($_REQUEST[$campo_nome]){ $campo_valor = $_REQUEST[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples); }
		
		banco_insert_name
		(
			$campos,
			"site"
		);
		
		$id_site = banco_last_id();
		
		// ===== URL personalizada ou URL ancorada em uma categoria
		
		if($categoria_id || $url_personalizada){
			if($url_personalizada){
				$url = site_criar_identificador($url_personalizada,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']));
				
				$campos = null;
				
				$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "url"; $campo_valor = $url; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				
				banco_insert_name
				(
					$campos,
					"urls_personalizadas"
				);
				
				$url = '/'.$url.'/';
				
				$id_urls_personalizadas = banco_last_id();
			} else {
				$servicos_categorias = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id',
					))
					,
					"servicos_categorias",
					"WHERE id_loja='".$usuario['id_loja']."'"
					." AND id_servicos_categorias='".$categoria_id."'"
				);
				
				$url = '/'.$servicos_categorias[0]['id'].'/'.$id.'/';
			}
			
			if($url){
				$url = "url='".$url."',";
			}
			
			if($id_urls_personalizadas){
				$id_urls_personalizadas = "id_urls_personalizadas='".$id_urls_personalizadas."',";
			}
		}
		
		// =====
		
		banco_update
		(
			$id_urls_personalizadas.
			$url.
			"id_site='".$id_site."'",
			"servicos",
			"WHERE id_servicos='".$id_servicos."'"
		);
		
		servico_publicar_pagina(Array(
			'id_servicos' => $id_servicos,
			'id_site' => $id_site,
			'pagina_add' => true,
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	}
	
	return $saida;
}

function servico_pagina_edit($params = false){
	global $_SYSTEM;
	global $_CAMPOS_ALTERADOS;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$site = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
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
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND id='servicos'"
		." AND id_site_pai='".$id_site_raiz."'"
	);
	
	$pai_id = $servicos[0]['id_site'];
	
	if($id_site){
		servico_remover_pagina(Array(
			'id_servicos' => $id_servicos,
			'nao_remover_banco' => true,
			'nao_fechar_ftp' => true,
		));
		
		$id = site_criar_identificador($nome,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),$id_site,$pai_id);
		
		$campo_tabela = "site";
		$campo_tabela_extra = "WHERE id_site='".$id_site."'";
		
		$campo_nome = "nome"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "pagina_titulo"; $campo_valor = $nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "id"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "data_modificacao"; $campo_valor = $id; $editar[$campo_tabela][] = $campo_nome."=NOW()";
		$campo_nome = "meta_robots"; if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome.($_REQUEST[$campo_nome] ? "='" . $_REQUEST[$campo_nome] . "'" : "=NULL"); $_CAMPOS_ALTERADOS[$campo_nome] = true; }
		
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
		
		// ===== URL personalizada ou URL ancorada em uma categoria
		
		$campo_tabela = "servicos";
		$campo_tabela_extra = "WHERE id_servicos='".$id_servicos."'";
		
		if($categoria_mudou || $url_personalizada_mudou || $nome_mudou){
			if($url_personalizada){
				if($campos_antes['id_urls_personalizadas']){
					$url = site_criar_identificador($url_personalizada,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']));
					
					banco_update
					(
						"url='".$url."'",
						"urls_personalizadas",
						"WHERE id_urls_personalizadas='".$campos_antes['id_urls_personalizadas']."'"
					);
					
					$id_urls_personalizadas = $campos_antes['id_urls_personalizadas'];
				} else {
					$url = site_criar_identificador($url_personalizada,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']));
				
					$campos = null;
					
					$campo_nome = "id_usuario"; $campo_valor = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "url"; $campo_valor = $url; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"urls_personalizadas"
					);
					
					$id_urls_personalizadas = banco_last_id();
				}
				
				$url = '/'.$url.'/';
			} else {
				if($campos_antes['id_urls_personalizadas']){
					banco_delete
					(
						"urls_personalizadas",
						"WHERE id_urls_personalizadas='".$campos_antes['id_urls_personalizadas']."'"
					);
				}
			}
			
			if($categoria_id && !$url){
				$servicos_categorias = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id',
					))
					,
					"servicos_categorias",
					"WHERE id_loja='".$usuario['id_loja']."'"
					." AND id_servicos_categorias='".$categoria_id."'"
				);
				
				$url = '/'.$servicos_categorias[0]['id'].'/'.$id.'/';
			}
			
			if($url){
				$campo_nome = "url"; $campo_valor = $url; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			} else {
				$campo_nome = "url"; $editar[$campo_tabela][] = $campo_nome."=NULL";
			}
			
			if($url_personalizada_mudou){
				if($id_urls_personalizadas){
					$campo_nome = "id_urls_personalizadas"; $campo_valor = $id_urls_personalizadas; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				} else {
					$campo_nome = "id_urls_personalizadas"; $editar[$campo_tabela][] = $campo_nome."=NULL";
				}
			}
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
		
		// =====
		
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
	
	if($id_site)
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
				'nao_remover_banco' => true,
			));
		break;
		
	}
}

function servico_start_vars(){
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

function categorias_select($id_selected = false){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$nome = 'categorias';
	
	$nome_bd = 'nome';
	$id_bd = 'id_servicos_categorias';
	
	$tabela = banco_select_name
	(
		banco_campos_virgulas(Array(
			$id_bd,
			$nome_bd,
		))
		,
		"servicos_categorias",
		"WHERE id_loja='".$usuario['id_loja']."'"
	);
	
	$max = count($tabela);
	
	$options[] = "Padrão";
	$optionsValue[] = "-1";
	
	if($tabela)
	foreach($tabela as $linha){
		$options[] = $linha[$nome_bd];
		$optionsValue[] = $linha[$id_bd];
		
		$cont++;
		
		if($id_selected && $id_selected == $linha[$id_bd]){
			$optionSelected = $cont;
		}
	}
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'');
	
	return $select;
}

function categorias_nome($id_servicos_categorias){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$servicos_categorias = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
		))
		,
		"servicos_categorias",
		"WHERE id_loja='".$usuario['id_loja']."'"
		." AND id_servicos_categorias='".$id_servicos_categorias."'"
	);
	
	return ($servicos_categorias[0]['nome'] ? $servicos_categorias[0]['nome'] : 'Padrão') ;
}

function visibilidade_select($id_selected = false){
	$nome = 'visibilidade';
	
	$opcoes = Array(
		Array(
			'texto' => 'Sempre',
			'valor' => 'sempre',
		),
		Array(
			'texto' => 'Data Início',
			'valor' => 'dataInicio',
		),
		Array(
			'texto' => 'Data Fim',
			'valor' => 'dataFim',
		),
		Array(
			'texto' => 'Período',
			'valor' => 'dataPeriodo',
		),
	);
	
	
	if($opcoes)
	foreach($opcoes as $opcao){
		$options[] = $opcao['texto'];
		$optionsValue[] = $opcao['valor'];
		
		$cont++;
		
		if($id_selected && $id_selected == $opcao['valor']){
			$optionSelected = ($cont - 1);
		}
	}
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'');
	
	return $select;
}

function visibilidade_nome($id_selected = false){
	$nome = 'visibilidade';
	
	$opcoes = Array(
		Array(
			'texto' => 'Sempre',
			'valor' => 'sempre',
		),
		Array(
			'texto' => 'Data Início',
			'valor' => 'dataInicio',
		),
		Array(
			'texto' => 'Data Fim',
			'valor' => 'dataFim',
		),
		Array(
			'texto' => 'Período',
			'valor' => 'dataPeriodo',
		),
	);
	
	if($opcoes)
	foreach($opcoes as $opcao){
		if($id_selected && $id_selected == $opcao['valor']){
			return $opcao['texto'];
		}
	}
}

function meta_robots_select($id_selected = false){
	$nome = 'meta_robots';
	
	$opcoes = Array(
		Array(
			'texto' => 'Indexar e Seguir Links',
			'valor' => '',
		),
		Array(
			'texto' => 'Não Indexar',
			'valor' => 'noindex',
		),
		Array(
			'texto' => 'Não Seguir Links',
			'valor' => 'nofollow',
		),
		Array(
			'texto' => 'Não Indexar e Não Seguir Links',
			'valor' => 'noindex, nofollow',
		),
	);
	
	
	if($opcoes)
	foreach($opcoes as $opcao){
		$options[] = $opcao['texto'];
		$optionsValue[] = $opcao['valor'];
		
		$cont++;
		
		if($id_selected && $id_selected == $opcao['valor']){
			$optionSelected = ($cont - 1);
		}
	}
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'');
	
	return $select;
}

function meta_robots_nome($id_selected = ''){
	$nome = 'meta_robots';
	
	$opcoes = Array(
		Array(
			'texto' => 'Indexar e Seguir Links',
			'valor' => '',
		),
		Array(
			'texto' => 'Não Indexar',
			'valor' => 'noindex',
		),
		Array(
			'texto' => 'Não Seguir Links',
			'valor' => 'nofollow',
		),
		Array(
			'texto' => 'Não Indexar e Não Seguir Links',
			'valor' => 'noindex, nofollow',
		),
	);
	
	
	if($opcoes)
	foreach($opcoes as $opcao){
		if($id_selected == $opcao['valor']){
			return $opcao['texto'];
		}
	}
}

function lote_select($id_selected = false){
	$nome = 'lote';
	
	$opcoes = Array(
		Array(
			'texto' => 'Único',
			'valor' => 'unico',
		),
		Array(
			'texto' => 'Variado',
			'valor' => 'variado',
		),
	);
	
	
	if($opcoes)
	foreach($opcoes as $opcao){
		$options[] = $opcao['texto'];
		$optionsValue[] = $opcao['valor'];
		
		$cont++;
		
		if($id_selected && $id_selected == $opcao['valor']){
			$optionSelected = ($cont - 1);
		}
	}
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'');
	
	return $select;
}

function lotes_inserir($lotes = false){
	if($lotes){
		foreach($lotes as $lote){
			$lote_txt .= ($lote_txt ? ', ':''). 'foi <b>adicionado</b> o lote de nome <b>'.$lote.'</b>';
		}
		
		return ' - '.$lote_txt;
	} else {
		return '';
	}
}

function lotes_remover($lotes = false){
	if($lotes){
		foreach($lotes as $lote){
			$lote_txt .= ($lote_txt ? ', ':''). 'foi <b>removido</b> o lote de nome <b>'.$lote.'</b>';
		}
		
		return ' - '.$lote_txt;
	} else {
		return '';
	}
}

function lotes_modificar($lotes = false){
	$lotes_label = Array(
		'nome' => 'Nome',
		'preco' => 'Preço',
		'quantidade' => 'Quantidade',
		'visivel_de' => 'Visível De',
		'visivel_ate' => 'Visível Até',
	);
	
	if($lotes){
		foreach($lotes as $campo => $lote){
			if(!$lotes_campos[$lote['id']]){
				$lotes_campos[$lote['id']] = ($lotes_campos ? ', ':'').'foi <b>atualizado</b> o lote de nome <b>'.$lote['nome'].'</b>'
				.' nos seguinte(s) campo(s): ';
			} else {
				$lotes_campos[$lote['id']] .= ', ';
			}
			
			$lotes_campos[$lote['id']] .= '<b>'.$lotes_label[$campo].'</b> alterado de: <b>'.$lote['de'].'</b> para: <b>'.$lote['para'].'</b>';
		}
		
		foreach($lotes_campos as $lote){
			$lote_txt .= ($lote_txt ? ', ':''). $lote;
		}
		
		return ' - '.$lote_txt;
	} else {
		return '';
	}
}

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
	$tabela_campos[] = 'quantidade';
	$tabela_campos[] = 'preco';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? '' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => '../../dashboard/',// link da opção
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
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Adicionar', // Nome do menu
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
	
	/* $header_campos[] = Array( // array com todos os campos do cabeçalho
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
	); */
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Quantidade', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'left', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'left', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Preço', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'left', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'left', // OPCIONAL - alinhamento horizontal
		'dinheiro_reais' => true, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
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
	
	$pagina = paginaTrocaVarValor($pagina,"#meta-dados#",'A definir');
	$pagina = paginaTrocaVarValor($pagina,"#categoria#",categorias_select());
	$pagina = paginaTrocaVarValor($pagina,"#url_personalizada#",$url_personalizada);
	$pagina = paginaTrocaVarValor($pagina,"#html_meta_robots#",meta_robots_select());
	$pagina = paginaTrocaVarValor($pagina,"#nome",$nome);
	$pagina = paginaTrocaVarValor($pagina,"#descricao",$descricao);
	$pagina = paginaTrocaVarValor($pagina,"#titulo_personalizado#",$titulo_personalizado);
	$pagina = paginaTrocaVarValor($pagina,"#validade#",$validade);
	$pagina = paginaTrocaVarValor($pagina,"#imagem_path#",$imagem_path);
	$pagina = paginaTrocaVarValor($pagina,"#lote-select#",lote_select());
	$pagina = paginaTrocaVarValor($pagina,"#lotes-quantidade#",$lotes_quantidade);
	$pagina = paginaTrocaVarValor($pagina,"#lotes-dados#",$lotes_dados);
	$pagina = paginaTrocaVarValor($pagina,"#quantidade#",$quantidade);
	$pagina = paginaTrocaVarValor($pagina,"#preco#",$preco);
	$pagina = paginaTrocaVarValor($pagina,"#visibilidade-select#",visibilidade_select());
	$pagina = paginaTrocaVarValor($pagina,"#data-inicio#",$data_inicio);
	$pagina = paginaTrocaVarValor($pagina,"#data-fim#",$data_fim);
	$pagina = paginaTrocaVarValor($pagina,"#data-periodo#",$data_periodo);
	$pagina = paginaTrocaVarValor($pagina,"#observacao#",$observacao);
	$pagina = paginaTrocaVarValor($pagina,"#validade_data#",$validade_data);
	$pagina = paginaTrocaVarValor($pagina,"#validade_hora#",$validade_hora);
	$pagina = paginaTrocaVarValor($pagina,"#validade_tipo#",'P');
	
	$cel_nome = 'hitorico'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$pagina = paginaTrocaVarValor($pagina,'#imagem_biblioteca_id#',$imagem_biblioteca_id);
	$pagina = paginaTrocaVarValor($pagina,'#imagem_tipo#','1');
	
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
	
	$campo_nome = "id_servicos_categorias"; $post_nome = 'categorias';				 	if($_POST[$post_nome] != '-1'){		$campos[] = Array($campo_nome,$_POST[$post_nome]); $categoria_id = $_POST[$post_nome]; }
	$campo_nome = "url_personalizada"; $post_nome = $campo_nome; 						if($_POST[$post_nome]){				$campos[] = Array($campo_nome,$_POST[$post_nome]); $url_personalizada = $_POST[$post_nome]; }
	$campo_nome = "nome"; $post_nome = $campo_nome; 									if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "titulo_personalizado"; $post_nome = $campo_nome; 					if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "observacao"; $post_nome = $campo_nome; 								if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "visibilidade"; $post_nome = $campo_nome; 							if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "validade"; $post_nome = $campo_nome; 								if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "validade_data"; $post_nome = $campo_nome; 							if($_POST[$post_nome])				$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]).' '.($_REQUEST['validade_hora'] ? $_REQUEST['validade_hora'] : '00:00').':00');
	$campo_nome = "validade_tipo"; $post_nome = $campo_nome; 							if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "descricao"; $post_nome = $campo_nome; 								if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "lote"; $post_nome = $campo_nome; 									if($_POST[$post_nome] == 'variado')	$campos[] = Array($campo_nome,'1',true);
	$campo_nome = "quantidade"; $post_nome = $campo_nome; 								if($_POST[$post_nome])				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "preco"; $post_nome = $campo_nome; 									if($_POST[$post_nome])				$campos[] = Array($campo_nome,preparar_texto_4_float($_POST[$post_nome]));
	$campo_nome = "id_loja"; $campo_valor = $usuario['id_loja']; 						$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "status"; $post_nome = $campo_nome; 									$campos[] = Array($campo_nome,'A');
	$campo_nome = "versao"; $post_nome = $campo_nome; 									$campos[] = Array($campo_nome,'1');
	
	$campo_nome = "data_criacao"; $post_nome = $campo_nome; 							$campos[] = Array($campo_nome,'NOW()',true);
	$campo_nome = "data_modificacao"; $post_nome = $campo_nome; 						$campos[] = Array($campo_nome,'NOW()',true);
	
	switch($_REQUEST['visibilidade']){
		case 'dataInicio':
			$visivel_de = data_hora_padrao_datetime($_REQUEST['data-inicio']);
		break;
		case 'dataFim':
			$visivel_ate = data_hora_padrao_datetime($_REQUEST['data-fim']);
		break;
		case 'dataPeriodo':
			$data_periodo = explode(" até ",$_REQUEST['data-periodo']);
			
			$visivel_de = data_hora_padrao_datetime($data_periodo[0]);
			$visivel_ate = data_hora_padrao_datetime($data_periodo[1]);
		break;
	}
	
	$campo_nome = "visivel_de"; $post_nome = $campo_nome; 								if($visivel_de)						$campos[] = Array($campo_nome,$visivel_de);
	$campo_nome = "visivel_ate"; $post_nome = $campo_nome; 								if($visivel_ate)					$campos[] = Array($campo_nome,$visivel_ate);
	
	$campo_nome = "imagem_biblioteca"; $post_nome = $campo_nome; 						if($_POST[$post_nome] == '1')		$campos[] = Array($campo_nome,$_POST[$post_nome],true);
	$campo_nome = "imagem_biblioteca_id"; $post_nome = 'biblioteca-imagens-id'; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$id_servicos = banco_last_id();
	
	if($_REQUEST['lote'] == 'variado'){
		$lotes_quantidade = $_REQUEST['lotes-quantidade'];
		
		for($i = 1;$i<=(int)$lotes_quantidade;$i++){
			$campos = null;
			
			$campo_nome = "id_servicos"; $campo_valor = $id_servicos; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $post_nome = 'lote-'.$campo_nome.'-'.$i; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "quantidade"; $post_nome = 'lote-'.$campo_nome.'-'.$i; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "preco"; $post_nome = 'lote-'.$campo_nome.'-'.$i; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,preparar_texto_4_float($_REQUEST[$post_nome]));
			
			$data_periodo = explode(" até ",$_REQUEST['lote-periodo-'.$i]);
			
			$visivel_de = data_hora_padrao_datetime($data_periodo[0]);
			$visivel_ate = data_hora_padrao_datetime($data_periodo[1]);
			
			$campo_nome = "visivel_de"; $campo_valor = $visivel_de; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "visivel_ate"; $campo_valor = $visivel_ate; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	
			banco_insert_name
			(
				$campos,
				"servicos_lotes"
			);
		}
	}
	
	log_banco(Array(
		'id_referencia' => $id_servicos,
		'grupo' => 'servicos',
		'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].($usuario['sobrenome'] ? ' '.$usuario['sobrenome'] : '').'</b> criou este serviço.',
	));
	
	guardar_arquivo($_FILES['imagem_path'],'imagem','imagem_path',$id_servicos);
	servico_pagina_add(Array(
		'nome' => $_POST['nome'],
		'id_servicos' => $id_servicos,
		'categoria_id' => $categoria_id,
		'url_personalizada' => $url_personalizada,
	));
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	if($_REQUEST['more_options']){
		header('Location: ../../design/?'.$_REQUEST['more_options']);
	} else {
		header('Location: ./?opcao=editar&id='.$id_servicos);
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
				'url',
				'url_personalizada',
				'validade',
				'imagem_path',
				'quantidade',
				'preco',
				'observacao',
				'visibilidade',
				'visivel_de',
				'visivel_ate',
				'id_site',
				'validade_data',
				'validade_tipo',
				'imagem_biblioteca',
				'imagem_biblioteca_id',
				'data_criacao',
				'data_modificacao',
				'id_servicos_categorias',
				'id_urls_personalizadas',
				'titulo_personalizado',
				'lote',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		if($tabela){
			// ================================= Local de Edição ===============================
			// Pegue os campos da interface e campos_guardar aqui
			
			$site = banco_select_name
			(
				banco_campos_virgulas(Array(
					'meta_robots',
				))
				,
				'site',
				"WHERE id_site='".$tabela[0]['id_site']."'"
			);
			
			if($tabela[0]['validade_data']) $data = data_hora_from_datetime($tabela[0]['validade_data']);
			
			if($data){
				$tabela[0]['validade_data'] = $data[0];
				$tabela[0]['validade_hora'] = $data[1];
			}
			
			$campos_guardar = Array(
				'id_servicos_categorias' => $tabela[0]['id_servicos_categorias'],
				'id_site' => $tabela[0]['id_site'],
				'url_personalizada' => $tabela[0]['url_personalizada'],
				'id_urls_personalizadas' => $tabela[0]['id_urls_personalizadas'],
				'meta_robots' => $site[0]['meta_robots'],
				'nome' => $tabela[0]['nome'],
				'titulo_personalizado' => $tabela[0]['titulo_personalizado'],
				'observacao' => $tabela[0]['observacao'],
				'visibilidade' => $tabela[0]['visibilidade'],
				'visivel_de' => $tabela[0]['visivel_de'],
				'visivel_ate' => $tabela[0]['visivel_ate'],
				'validade' => $tabela[0]['validade'],
				'descricao' => $tabela[0]['descricao'],
				'imagem_path' => $tabela[0]['imagem_path'],
				'quantidade' => $tabela[0]['quantidade'],
				'validade_data' => $tabela[0]['validade_data'],
				'validade_hora' => $tabela[0]['validade_hora'],
				'validade_tipo' => $tabela[0]['validade_tipo'],
				'imagem_biblioteca' => $tabela[0]['imagem_biblioteca'],
				'imagem_biblioteca_id' => $tabela[0]['imagem_biblioteca_id'],
				'preco' => preparar_float_4_texto($tabela[0]['preco']),
				'lote' => $tabela[0]['lote'],
			);
			
			$remover = '<div><a href="#link#"><img src="../../images/icons/db_remove.png" alt="Remover" width="32" height="32" border="0" title="Clique para remover esse ítem" /></a></div>';
			
			$tabela[0]['descricao'] = preg_replace('/\r\n/i', '&#13;&#10;', $tabela[0]['descricao']);
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'https',
					'dominio_proprio',
					'user_cpanel',
				))
				,
				"host",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$path = site_pagina_diretorio($tabela[0]['id_site'],false,true);
			
			if($tabela[0]['id_servicos_categorias']){
				$servicos_categorias = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id',
					))
					,
					"servicos_categorias",
					"WHERE id_loja='".$usuario['id_loja']."'"
					." AND id_servicos_categorias='".$tabela[0]['id_servicos_categorias']."'"
				);
				
				$path = preg_replace('/servicos/i', $servicos_categorias[0]['id'], $path);
			}
			
			if($tabela[0]['url']){
				$path = ltrim($tabela[0]['url'],'/');
			}
			
			$url = ($host[0]['https'] ? preg_replace('/http:/i', 'https:', $host[0]['url']):preg_replace('/https:/i', 'http:', $host[0]['url'])) . $path;
			if($host[0]['dominio_proprio'])$dominio_proprio = ($host[0]['https'] ? 'https:':'http:').'//' . $host[0]['dominio_proprio'] . '/' . $path;
			
			$log_bd = banco_select_name
			(
				banco_campos_virgulas(Array(
					'valor',
					'data',
				))
				,
				"log",
				"WHERE id_referencia='".$id."'"
				." AND grupo='servicos'"
				." ORDER BY data DESC"
			);
			
			if($log_bd){
				foreach($log_bd as $log){
					$log_txt .= ($log_txt ? '<br>' : '') . data_hora_from_datetime_to_text($log['data']) . ' - ' . $log['valor'];
				}
			} else {
				$cel_nome = 'hitorico'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			}
			
			$tabela[0]['data_criacao'] = data_hora_from_datetime_to_text($tabela[0]['data_criacao']);
			$tabela[0]['data_modificacao'] = data_hora_from_datetime_to_text($tabela[0]['data_modificacao']);
			
			$pagina = paginaTrocaVarValor($pagina,'#meta-dados#','<div id="qrcode"><img src=".?opcao=qrcode&content='.urlencode($url).'"></div><a href="'.$url.'" target="_blank">'.$url.'</a>'.($dominio_proprio?'<br><a href="'.$dominio_proprio.'" target="_blank">'.$dominio_proprio.'</a> OBS: É necessário que o seu domínio esteja configurado corretamente para funcionar esta opção. Caso não funcione, é necessário entrar em contato com o suporte para saber como proceder.':'').($tabela[0]['data_modificacao'] ? '<br>Última Modificação: '.$tabela[0]['data_modificacao'].($tabela[0]['data_criacao'] ? ' | Data Criação: '.$tabela[0]['data_criacao'] : '') : ''));
			$pagina = paginaTrocaVarValor($pagina,'#categoria#',categorias_select(($tabela[0]['id_servicos_categorias'] ? $tabela[0]['id_servicos_categorias'] : false)));
			$pagina = paginaTrocaVarValor($pagina,'#url_personalizada#',$tabela[0]['url_personalizada']);
			$pagina = paginaTrocaVarValor($pagina,'#html_meta_robots#',meta_robots_select($site[0]['meta_robots']));
			$pagina = paginaTrocaVarValor($pagina,'#nome',$tabela[0]['nome']);
			$pagina = paginaTrocaVarValor($pagina,'#titulo_personalizado#',$tabela[0]['titulo_personalizado']);
			$pagina = paginaTrocaVarValor($pagina,'#observacao#',$tabela[0]['observacao']);
			$pagina = paginaTrocaVarValor($pagina,'#validade#',$tabela[0]['validade']);
			$pagina = paginaTrocaVarValor($pagina,'#descricao',$tabela[0]['descricao']);
			$pagina = paginaTrocaVarValor($pagina,'#imagem_path#',($tabela[0]['imagem_path']?modelo_var_troca($remover,"#link#",'?opcao=remover_item&item=imagem_path&id='.$id).'<img src="'.path_com_versao_arquivo($tabela[0]['imagem_path']).'">':''));
			$pagina = paginaTrocaVarValor($pagina,'#quantidade#',$tabela[0]['quantidade']);
			$pagina = paginaTrocaVarValor($pagina,'#preco#',preparar_float_4_texto($tabela[0]['preco']));
			$pagina = paginaTrocaVarValor($pagina,"#visibilidade-select#",visibilidade_select($tabela[0]['visibilidade']));
			
			switch($tabela[0]['visibilidade']){
				case 'dataInicio':
					$data_inicio = data_hora_from_datetime_to_text($tabela[0]['visivel_de'],'D/ME/A H:MI');
				break;
				case 'dataFim':
					$data_fim = data_hora_from_datetime_to_text($tabela[0]['visivel_ate'],'D/ME/A H:MI');
				break;
				case 'dataPeriodo':
					$data_periodo = data_hora_from_datetime_to_text($tabela[0]['visivel_de'],'D/ME/A H:MI') . " até " . data_hora_from_datetime_to_text($tabela[0]['visivel_ate'],'D/ME/A H:MI');
				break;
			}
			
			$pagina = paginaTrocaVarValor($pagina,"#data-inicio#",$data_inicio);
			$pagina = paginaTrocaVarValor($pagina,"#data-fim#",$data_fim);
			$pagina = paginaTrocaVarValor($pagina,"#data-periodo#",$data_periodo);
			$pagina = paginaTrocaVarValor($pagina,'#validade_tipo#',$tabela[0]['validade_tipo']);
			$pagina = paginaTrocaVarValor($pagina,'#validade_data#',$tabela[0]['validade_data']);
			$pagina = paginaTrocaVarValor($pagina,'#validade_hora#',$tabela[0]['validade_hora']);
			$pagina = paginaTrocaVarValor($pagina,'#imagem_biblioteca_id#',$tabela[0]['imagem_biblioteca_id']);
			$pagina = paginaTrocaVarValor($pagina,'#log#',$log_txt);
			
			if($tabela[0]['imagem_biblioteca']){
				$pagina = paginaTrocaVarValor($pagina,'#imagem_tipo#','1');
			} else {
				$pagina = paginaTrocaVarValor($pagina,'#imagem_tipo#','2');
			}
			
			// ============================== Lotes ========================================
			
			$servicos_lotes = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos_lotes',
					'nome',
					'preco',
					'quantidade',
					'visivel_de',
					'visivel_ate',
				))
				,
				"servicos_lotes",
				"WHERE id_servicos='".$id."'"
			);
			
			if($servicos_lotes)
			foreach($servicos_lotes as $sl){
				$lotes_quantidade++;
				
				$visivel_de = data_hora_from_datetime_to_text($sl['visivel_de'],'D/ME/A H:MI');
				$visivel_ate = data_hora_from_datetime_to_text($sl['visivel_ate'],'D/ME/A H:MI');
				
				$lotes_dados[] = Array(
					'id' => $lotes_quantidade,
					'id_servicos_lotes' => $sl['id_servicos_lotes'],
					'nome' => $sl['nome'],
					'quantidade' => $sl['quantidade'],
					'preco' => preparar_float_4_texto($sl['preco']),
					'visivel_de' => $visivel_de,
					'visivel_ate' => $visivel_ate,
					'periodo' => $visivel_de . ' até ' . $visivel_ate,
				);
			}
			
			$campos_guardar['lotes_dados'] = $lotes_dados;
			
			$lotes_dados = htmlentities(json_encode($lotes_dados));
			
			$pagina = paginaTrocaVarValor($pagina,"#lote-select#",lote_select($tabela[0]['lote'] ? 'variado' : 'unico'));
			$pagina = paginaTrocaVarValor($pagina,"#lotes-quantidade#",$lotes_quantidade);
			$pagina = paginaTrocaVarValor($pagina,"#lotes-dados#",$lotes_dados);
			
			// ======================================================================================
			
			banco_fechar_conexao();
			
			campos_antes_guardar($campos_guardar);
			
			$in_titulo = $param ? "Visualizar" : "Editar";
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
	global $_CAMPOS_ALTERADOS;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($id){
		$campos_antes = campos_antes_recuperar();
		
		$servico = banco_select_name
		(
			banco_campos_virgulas(Array(
				$_LISTA['tabela']['id'],
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		if(!$servico){
			return lista();
		}
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "id_servicos_categorias"; if(($campos_antes[$campo_nome] && $campos_antes[$campo_nome] != $_POST['categorias']) || (!$campos_antes[$campo_nome] && $_POST['categorias'] != '-1')){$editar['tabela'][] = ($_POST['categorias'] == '-1' ? $campo_nome."=NULL" : $campo_nome."='" . $_POST['categorias'] . "'"); $campos_alterados[$campo_nome] = true; if($_POST['categorias'] != '-1') $categoria_id = $_POST['categorias']; $categoria_mudou = true; }
		$campo_nome = "url_personalizada"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true; $url_personalizada = $_POST[$campo_nome]; $url_personalizada_mudou = true; if(!$categoria_mudou && $_POST['categorias'] != '-1'){ $categoria_id = $_POST['categorias']; } }
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true; if(!$categoria_mudou && $_POST['categorias'] != '-1'){ $nome_mudou = true; $categoria_id = $_POST['categorias']; }}
		$campo_nome = "titulo_personalizado"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true;}
		$campo_nome = "observacao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true;}
		$campo_nome = "validade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true;}
		$campo_nome = "validade_tipo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true;}
		$campo_nome = "validade_data"; if(($campos_antes[$campo_nome] != $_POST[$campo_nome] || $campos_antes['validade_hora'] != $_POST['validade_hora'].($_POST['validade_hora'] ? ':00':''))){$editar['tabela'][] = ($_POST[$campo_nome] && $_REQUEST['validade_hora'] ? $campo_nome."='" . (data_padrao_date($_POST[$campo_nome]).' '.($_REQUEST['validade_hora'] ? $_REQUEST['validade_hora'] : '00:00').':00'). "'" : $campo_nome."=NULL"); $campos_alterados[$campo_nome] = true;}
		
		$tst = '('.$campos_antes[$campo_nome].' != '.$_POST[$campo_nome].' || '.$campos_antes['validade_hora'].' != '.$_POST['validade_hora'].':00)';
		
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true;}
		$campo_nome = "lote"; if(($campos_antes[$campo_nome] && $_POST[$campo_nome] != 'variado') || (!$campos_antes[$campo_nome] && $_POST[$campo_nome] == 'variado')){$editar['tabela'][] = $campo_nome.'='.($_POST[$campo_nome] == 'variado' ? "1" : "NULL"); $campos_alterados[$campo_nome] = true; $campos_alterados_principal[$campo_nome] = true; }
		$campo_nome = "quantidade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true;}
		$campo_nome = "preco"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . preparar_texto_4_float($_POST[$campo_nome]) . "'"; $campos_alterados[$campo_nome] = true;}
		$campo_nome = "imagem_biblioteca"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome] == '1' ? '1' : 'NULL');}
		$campo_nome = "versao"; $editar['tabela'][] = $campo_nome."=versao+1";
		
		$campo_nome = "data_modificacao"; $editar['tabela'][] = $campo_nome."=NOW()";
		
		$campo_nome = "visibilidade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $campos_alterados[$campo_nome] = true;}
		
		switch($_REQUEST['visibilidade']){
			case 'dataInicio':
				$visivel_de = data_hora_padrao_datetime($_REQUEST['data-inicio']);
			break;
			case 'dataFim':
				$visivel_ate = data_hora_padrao_datetime($_REQUEST['data-fim']);
			break;
			case 'dataPeriodo':
				$data_periodo = explode(" até ",$_REQUEST['data-periodo']);
				
				$visivel_de = data_hora_padrao_datetime($data_periodo[0]);
				$visivel_ate = data_hora_padrao_datetime($data_periodo[1]);
			break;
		}
		
		$campo_nome = "visivel_de"; if($campos_antes[$campo_nome] != $visivel_de){$editar['tabela'][] = $campo_nome.($visivel_de ? "='" . $visivel_de . "'" : "=NULL"); $campos_alterados[$campo_nome] = true;}
		$campo_nome = "visivel_ate"; if($campos_antes[$campo_nome] != $visivel_ate){$editar['tabela'][] = $campo_nome.($visivel_ate ? "='" . $visivel_ate . "'" : "=NULL"); $campos_alterados[$campo_nome] = true;}
		
		if($_REQUEST['biblioteca-imagens-id']){
			$servicos_biblioteca_imagens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'file',
				))
				,
				"servicos_biblioteca_imagens",
				"WHERE id_servicos_biblioteca_imagens='".$_REQUEST['biblioteca-imagens-id']."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
			if($servicos_biblioteca_imagens){
				$campo_nome = "imagem_biblioteca_id"; $post_nome = 'biblioteca-imagens-id';if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$post_nome] . "'"; $campos_alterados[$campo_nome] = true;}
			}
		} else {
			$campo_nome = "imagem_biblioteca_id"; $post_nome = 'biblioteca-imagens-id';if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][] = ($_POST[$post_nome] ? $campo_nome."='" . $_POST[$post_nome] . "'" : $campo_nome."=NULL"); $campos_alterados[$campo_nome] = true;}
		}
		
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
		
		// ============================== Lotes ========================================
		
		$lotes_dados = $campos_antes['lotes_dados'];
		$lotes_quantidade = $_REQUEST['lotes-quantidade'];
		
		for($i = 1;$i<=(int)$lotes_quantidade;$i++){
			if($_REQUEST['lote-identificador-'.$i]){
				if($lotes_dados){
					$lotes_dados_aux = false;
					
					foreach($lotes_dados as $ld){
						if($ld['id_servicos_lotes'] == $_REQUEST['lote-identificador-'.$i]){
							
							$editar = false;$editar_sql = false;
							
							$campo_tabela = "servicos_lotes";
							$campo_tabela_extra = "WHERE id_servicos_lotes='".$ld['id_servicos_lotes']."' AND id_servicos='".$id."'";
							
							$lote_nome = $ld["nome"];
							
							$campo_nome = "nome"; if($ld[$campo_nome] != $_REQUEST['lote-'.$campo_nome.'-'.$i]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST['lote-'.$campo_nome.'-'.$i] . "'"; $lote_nome = $_REQUEST['lote-'.$campo_nome.'-'.$i]; $campos_alterados['lote'] = true; $campos_alterados_valor['lote'][$campo_nome] = Array('id' => $ld['id_servicos_lotes'], 'nome' => $lote_nome, 'de' => $ld[$campo_nome], 'para' => $_REQUEST['lote-'.$campo_nome.'-'.$i]);}
							$campo_nome = "quantidade"; if($ld[$campo_nome] != $_REQUEST['lote-'.$campo_nome.'-'.$i]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST['lote-'.$campo_nome.'-'.$i] . "'"; $campos_alterados['lote'] = true; $campos_alterados_valor['lote'][$campo_nome] = Array('id' => $ld['id_servicos_lotes'], 'nome' => $lote_nome, 'de' => $ld[$campo_nome], 'para' => $_REQUEST['lote-'.$campo_nome.'-'.$i]);}
							$campo_nome = "preco"; if($ld[$campo_nome] != $_REQUEST['lote-'.$campo_nome.'-'.$i]){$editar[$campo_tabela][] = $campo_nome."='" . preparar_texto_4_float($_REQUEST['lote-'.$campo_nome.'-'.$i]) . "'"; $campos_alterados['lote'] = true; $campos_alterados_valor['lote'][$campo_nome] = Array('id' => $ld['id_servicos_lotes'], 'nome' => $lote_nome, 'de' => $ld[$campo_nome], 'para' => $_REQUEST['lote-'.$campo_nome.'-'.$i]);}
							
							$data_periodo = explode(" até ",$_REQUEST['lote-periodo-'.$i]);
							
							$visivel_de = data_hora_padrao_datetime($data_periodo[0]);
							$visivel_ate = data_hora_padrao_datetime($data_periodo[1]);
							
							$campo_nome = "visivel_de"; if($ld[$campo_nome] != $data_periodo[0]){$editar[$campo_tabela][] = $campo_nome."='" . $visivel_de . "'"; $campos_alterados['lote'] = true; $campos_alterados_valor['lote'][$campo_nome] = Array('id' => $ld['id_servicos_lotes'], 'nome' => $lote_nome, 'de' => $ld[$campo_nome], 'para' => $data_periodo[0]);}
							$campo_nome = "visivel_ate"; if($ld[$campo_nome] != $data_periodo[1]){$editar[$campo_tabela][] = $campo_nome."='" . $visivel_ate . "'"; $campos_alterados['lote'] = true; $campos_alterados_valor['lote'][$campo_nome] = Array('id' => $ld['id_servicos_lotes'], 'nome' => $lote_nome, 'de' => $ld[$campo_nome], 'para' => $data_periodo[1]);}
							
							$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
							
							if($editar_sql[$campo_tabela]){
								banco_update
								(
									$editar_sql[$campo_tabela],
									$campo_tabela,
									$campo_tabela_extra
								);
							}
						} else {
							$lotes_dados_aux[] = $ld;
						}
					}
					
					$lotes_dados = $lotes_dados_aux;
				}
			} else {
				$campos = null;
				
				$campos_alterados['lote'] = true;
				$campos_adicionado['lote'][] = $_REQUEST['lote-nome-'.$i];
				
				$campo_nome = "id_servicos"; $campo_valor = $id; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "nome"; $post_nome = 'lote-'.$campo_nome.'-'.$i; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "quantidade"; $post_nome = 'lote-'.$campo_nome.'-'.$i; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "preco"; $post_nome = 'lote-'.$campo_nome.'-'.$i; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,preparar_texto_4_float($_REQUEST[$post_nome]));
				
				$data_periodo = explode(" até ",$_REQUEST['lote-periodo-'.$i]);
				
				$visivel_de = data_hora_padrao_datetime($data_periodo[0]);
				$visivel_ate = data_hora_padrao_datetime($data_periodo[1]);
				
				$campo_nome = "visivel_de"; $campo_valor = $visivel_de; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "visivel_ate"; $campo_valor = $visivel_ate; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
				banco_insert_name
				(
					$campos,
					"servicos_lotes"
				);
			}
		}
		
		if($lotes_dados){
			foreach($lotes_dados as $ld){
				$campos_alterados['lote'] = true;
				$campos_removidos['lote'][] = $ld['nome'];
				
				banco_delete
				(
					"servicos_lotes",
					"WHERE id_servicos_lotes='".$ld['id_servicos_lotes']."' AND id_servicos='".$id."'"
				);
			}
		}
		
		// ======================================================================================
		
		servico_pagina_edit(Array(
			'id_servicos' => $id,
			'categoria_id' => $categoria_id,
			'categoria_mudou' => $categoria_mudou,
			'url_personalizada' => $url_personalizada,
			'url_personalizada_mudou' => $url_personalizada_mudou,
			'nome_mudou' => $nome_mudou,
			'id_site' => $campos_antes['id_site'],
			'campos_antes' => $campos_antes,
			'nome' => $_POST['nome'],
		));
		
		$campos_dados["id_servicos_categorias"] = Array('label' => 'Categoria');
		$campos_dados["url_personalizada"] = Array('label' => 'URL Personalizada');
		$campos_dados["nome"] = Array('label' => 'Nome');
		$campos_dados["titulo_personalizado"] = Array('label' => 'Título Personalizado');
		$campos_dados["observacao"] = Array('label' => 'Observação');
		$campos_dados["validade"] = Array('label' => 'Validade de Uso');
		$campos_dados["validade_tipo"] = Array('label' => 'Validade de Uso Tipo');
		$campos_dados["validade_data"] = Array('label' => 'Validade de Uso Data');
		$campos_dados["descricao"] = Array('label' => 'Descrição');
		$campos_dados["quantidade"] = Array('label' => 'Quantidade');
		$campos_dados["preco"] = Array('label' => 'Preço');
		$campos_dados["imagem_biblioteca_id"] = Array('label' => 'Imagem da Biblioteca');
		$campos_dados["visibilidade"] = Array('label' => 'Visibilidade');
		$campos_dados["visivel_de"] = Array('label' => 'Visível De');
		$campos_dados["visivel_ate"] = Array('label' => 'Visível Até');
		$campos_dados["meta_robots"] = Array('label' => 'HTML Meta Robots');
		$campos_dados["lote"] = Array('label' => 'Lote');
		
		if($_CAMPOS_ALTERADOS){
			if(!$campos_alterados) $campos_alterados = Array();
			$campos_alterados = array_merge($campos_alterados, $_CAMPOS_ALTERADOS);
		}
		
		if($campos_alterados){
			foreach($campos_alterados as $campo => $valor){
				switch($campo){
					case 'observacao': 
					case 'descricao': 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado';
					break;
					case 'meta_robots': 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>' . meta_robots_nome($campos_antes[$campo]) . '</b> - para: <b>' . meta_robots_nome($_REQUEST[$campo]) . '</b>';
					break;
					case 'visibilidade': 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>' . visibilidade_nome($campos_antes[$campo]) . '</b> - para: <b>' . visibilidade_nome($_REQUEST[$campo]) . '</b>';
					break;
					case 'visivel_de': 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>'.($campos_antes[$campo] ? data_hora_from_datetime_to_text($campos_antes[$campo]) : 'Sempre').'</b> - para: <b>'.($visivel_de ? data_hora_from_datetime_to_text($visivel_de) : 'Sempre').'</b>';
					break;
					case 'visivel_ate': 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>'.($campos_antes[$campo] ? data_hora_from_datetime_to_text($campos_antes[$campo]) : 'Sempre').'</b> - para: <b>'.($visivel_ate ? data_hora_from_datetime_to_text($visivel_ate) : 'Sempre').'</b>';
					break;
					case 'imagem_biblioteca_id': 
						if($servicos_biblioteca_imagens[0]['file']){
							$urlImg = http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'];
						}
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado para a <b>Imagem: '.($urlImg?'<a target="b2make-image" href="'.$urlImg.'">'.$urlImg.'</a>' : 'Nenhum').'</b>';
					break;
					case 'validade_tipo': 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>'.($campos_antes[$campo] == 'P' ? 'Por Período':'Por Data').'</b> - para: <b>'.($_REQUEST[$campo] == 'P' ? 'Por Período':'Por Data').'</b>';
					break;
					case 'validade_data': 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>'.($campos_antes[$campo] ? $campos_antes[$campo].' '.$campos_antes['validade_hora']:'Nenhum').'</b> - para: <b>'.($_POST[$campo] && $_REQUEST['validade_hora'] ? $_POST[$campo].' '.($_REQUEST['validade_hora'] ? $_REQUEST['validade_hora'] : '00:00').':00':'Nenhum').'</b>';
					break;
					case 'id_servicos_categorias':
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>'.categorias_nome($campos_antes[$campo]).'</b> - para: <b>'.categorias_nome($_REQUEST['categorias']).'</b>';
					break;
					case 'lote':
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado'.($campos_alterados_principal[$campo] ? ' de: <b>'.($campos_antes[$campo] ? 'Variado' : 'Único').'</b> - para: <b>'.($_REQUEST[$campo] ? 'Variado' : 'Único').'</b>' : '').lotes_inserir($campos_adicionado['lote']).lotes_modificar($campos_alterados_valor['lote']).lotes_remover($campos_removidos['lote']);
					break;
					default: 
						$campos_alterados_txt .= ($campos_alterados_txt ? '; ':'').'O campo <b>'.$campos_dados[$campo]['label'].'</b> foi modificado de: <b>'.($campos_antes[$campo] ? limite_texto($campos_antes[$campo],100) : 'Nenhum(a)').'</b> - para: <b>'.limite_texto($_REQUEST[$campo],100).'</b>';
				}
			}
			
			log_banco(Array(
				'id_referencia' => $id,
				'grupo' => 'servicos',
				'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].($usuario['sobrenome'] ? ' '.$usuario['sobrenome'] : '').'</b> editou este serviço e alterou: '.$campos_alterados_txt.'.',
			));
		}
		
		// ======================================================================================
		
		banco_fechar_conexao();
	}
	
	if($_REQUEST['more_options']){
		header('Location: ../../design/?'.$_REQUEST['more_options']);
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
	
	return editar();
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_B2MAKE_URL;
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."servicos".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/servicos/";
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
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
		$nome_arquivo_mini = $campo . '_mini' . $id_tabela . "." . $extensao;
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			$original = $caminho_fisico . $nome_arquivo;
			$mini = $caminho_fisico . $nome_arquivo_mini;
			
			$new_width = $_SYSTEM['IMG_MINI_WIDTH'];
			$new_height = $_SYSTEM['IMG_MINI_HEIGHT'];
			
			if($_PROJETO['servicos']){
				if($_PROJETO['servicos']['new_width']) $new_width = $_PROJETO['servicos']['new_width'];
				if($_PROJETO['servicos']['new_height']) $new_height = $_PROJETO['servicos']['new_height'];
				if($_PROJETO['servicos']['recorte_y']) $_RESIZE_IMAGE_Y_ZERO = true;
			}
			
			resize_image($original, $original, $new_width, $new_height,false,false,false);
			
			if($_PROJETO['servicos']){
				if($_PROJETO['servicos']['new_width_mini']) $new_width = $_PROJETO['servicos']['new_width_mini'];
				if($_PROJETO['servicos']['new_height_mini']) $new_height = $_PROJETO['servicos']['new_height_mini'];
				if($_PROJETO['servicos']['recorte_y_mini']) $_RESIZE_IMAGE_Y_ZERO = true;
			}
			
			resize_image($original, $mini, $new_width, $new_height,false,false,false);
		}
		
		log_banco(Array(
			'id_referencia' => $id_tabela,
			'grupo' => 'servicos',
			'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].($usuario['sobrenome'] ? ' '.$usuario['sobrenome'] : '').'</b> editou este serviço e adicionou uma <b>Imagem: <a target="b2make-image" href="'.$_B2MAKE_URL.$caminho_internet.$nome_arquivo.'">'.$_B2MAKE_URL.$caminho_internet.$nome_arquivo.'</a></b>.',
		));
		
		banco_update
		(
			$campo."_mini='".$caminho_internet.$nome_arquivo_mini."',".
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
				
				log_banco(Array(
					'id_referencia' => $id,
					'grupo' => 'servicos',
					'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].($usuario['sobrenome'] ? ' '.$usuario['sobrenome'] : '').'</b> editou este serviço e removeu a <b>Imagem</b>.',
				));
			
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

function services_qrcode(){
	global $_SYSTEM;
	global $_ESERVICE;
	global $_CAMINHO;
	
	$conteudo = rawurldecode($_REQUEST['content']);
	
	if($conteudo){
		include('../../'.$_SYSTEM['INCLUDE_PATH']."php/qrlib/qrlib.php");
		
		QRcode::png($conteudo, false, QR_ECLEVEL_H, 4);
	}
	
	exit;
}

// ======================================= Ajax Calls ===============================================

function ajax_biblioteca_imagens_lista(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_servicos_biblioteca_imagens',
			'file',
			'width',
			'height',
		))
		,
		"servicos_biblioteca_imagens",
		"WHERE status='A'"
		." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." ORDER BY id_servicos_biblioteca_imagens ASC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			if($res['file']){
				if(!$res['width'] || !$res['height']){
					$imgInfo = getimagesize($_SYSTEM['SITE']['url-files'] . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $res['file']);
					
					banco_update
					(
						"width='".$imgInfo[0]."',".
						"height='".$imgInfo[1]."'",
						"servicos_biblioteca_imagens",
						"WHERE id_servicos_biblioteca_imagens='".$res['id_servicos_biblioteca_imagens']."'"
					);
				} else {
					$imgInfo[0] = $res['width'];
					$imgInfo[1] = $res['height'];
				}
				
				$images[] = Array(
					'id' => $res['id_servicos_biblioteca_imagens'],
					'imagem' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $res['file'],
					'mini' => http_define_ssl($_SYSTEM['SITE']['url-files']) . $_SYSTEM['SITE']['ftp-files-services-path'] . '/mini/' . $res['file'],
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
				'id_servicos_biblioteca_imagens',
				'file',
			))
			,
			"servicos_biblioteca_imagens",
			"WHERE id_servicos_biblioteca_imagens='".$id."'"
			." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND status='A'"
		);
		
		if($resultado){
			$servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos',
					'nome',
				))
				,
				"servicos",
				"WHERE imagem_biblioteca_id='".$id."'"
				." AND id_loja='".$usuario['id_loja']."'"
				." AND status!='D'"
			);
			
			if(!$servicos){
				banco_update
				(
					"status='D'",
					"servicos_biblioteca_imagens",
					"WHERE id_servicos_biblioteca_imagens='".$id."'"
					." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				);
				
				$file = $resultado[0]['file'];
				
				//if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
				if(!$_CONEXAO_FTP)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SYSTEM['SITE']['ftp-files-host'],
					'user' => $_SYSTEM['SITE']['ftp-files-user'],
					'pass' => $_SYSTEM['SITE']['ftp-files-pass'],
				));
				
				if($_CONEXAO_FTP){
					ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-files-services-path']);
					
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
				$servicos_msg .= '<ul>';
				foreach($servicos as $ser){
					$servicos_msg .= '<li><a href="./?opcao=editar&id='.$ser['id_servicos'].'">'.$ser['nome'].'</a></li>';
				}
				$servicos_msg .= '</ul>';
				
				$saida = Array(
					'msg' => '<p>Não é possível excluir esta imagem uma vez que a mesma está vinculado ao(s) seguinte(s) serviço(s):</p>'.$servicos_msg,
					'status' => 'IdVinculadoServico',
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

function ajax_observacao_padrao(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'observacao_servicos',
		))
		,
		"loja",
		"WHERE id_loja='".$usuario['id_loja']."'"
	);
	
	$saida = Array(
		'padrao' => $resultado[0]['observacao_servicos'],
		'status' => 'Ok',
	);
	
	return $saida;
}

function ajax_descricao_padrao(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'descricao_servicos',
		))
		,
		"loja",
		"WHERE id_loja='".$usuario['id_loja']."'"
	);
	
	$saida = Array(
		'padrao' => $resultado[0]['descricao_servicos'],
		'status' => 'Ok',
	);
	
	return $saida;
}

function ajax_categoria_add(){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($_REQUEST['nome']){
		$site = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND id_site_pai IS NULL"
		);
		
		$id_site_raiz = $site[0]['id_site'];
		
		$nome = $_REQUEST['nome'];
		
		$id = site_criar_identificador($nome,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),false,$id_site_raiz);
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_host',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$id_host = $host[0]['id_host'];
		$id_loja = $usuario['id_loja'];
		
		$campo_nome = "id_loja"; $campo_valor = $id_loja; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"servicos_categorias"
		);
		
		$id_servicos_categorias = banco_last_id();
		
		$campos = null;
		
		$html = modelo_abrir($_SYSTEM['PATH'].'store'.$_SYSTEM['SEPARADOR'].'pagina-servicos-lista.html');
		$html_mobile = modelo_abrir($_SYSTEM['PATH'].'store'.$_SYSTEM['SEPARADOR'].'pagina-servicos-lista-mobile.html');
		
		$html = modelo_var_troca($html,"#titulo#",$nome);
		$html_mobile = modelo_var_troca($html_mobile,"#titulo#",$nome);
		$html = modelo_var_troca($html,"#service-data#",' data-categoria="categoria" data-categoria-id="'.$id_servicos_categorias.'"');
		$html_mobile = modelo_var_troca($html_mobile,"#service-data#",' data-categoria="categoria" data-categoria-id="'.$id_servicos_categorias.'"');
		
		$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_pai"; $campo_valor = $id_site_raiz; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pagina_titulo"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html_mobile"; $campo_valor = $html_mobile; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html_mobile_saved"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "google_fontes"; $campo_valor = 'Open+Sans'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "google_fontes_mobile"; $campo_valor = 'Open+Sans'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
			"servicos_categorias",
			"WHERE id_servicos_categorias='".$id_servicos_categorias."'"
		);
		
		$site = site_publish_page(Array(
			'nao_fechar_ftp' => true,
			'id_site' => $id_site,
			'html' => $html,
			'google_fontes' => 'Open+Sans',
			'mobile' => false,
			'layout_site' => $_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html',
		));
		
		$site = site_publish_page(Array(
			'id_site' => $id_site,
			'html' => $html_mobile,
			'google_fontes' => 'Open+Sans',
			'mobile' => true,
			'layout_site' => $_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html',
		));
		
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'msg' => 'Name not defined',
			'status' => 'ERRO',
		);
	}
	return $saida;
}

function ajax_categoria_edit(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($_REQUEST['nome'] && $_REQUEST['id']){
		$id_loja = $usuario['id_loja'];
		$nome = $_REQUEST['nome'];
		$id_servicos_categorias = $_REQUEST['id'];
		
		$site = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND id_site_pai IS NULL"
		);
		
		$id_site_raiz = $site[0]['id_site'];
		
		$servicos_categorias = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"servicos_categorias",
			"WHERE id_servicos_categorias='".$id_servicos_categorias."'"
			." AND id_loja='".$id_loja."'"
		);
		
		if($servicos_categorias){
			$id_site = $servicos_categorias[0]['id_site'];
			
			site_delete_page(Array(
				'nao_fechar_ftp' => true,
				'nao_remover_banco' => true,
				'id_site' => $id_site,
			));
			
			$id = site_criar_identificador($nome,($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),$id_site,$id_site_raiz);
			
			banco_update
			(
				"nome='".$nome."',".
				"id='".$id."'",
				"servicos_categorias",
				"WHERE id_servicos_categorias='".$id_servicos_categorias."'"
			);
			
			$site = banco_select_name
			(
				banco_campos_virgulas(Array(
					'html',
					'html_mobile',
					'google_fontes',
				))
				,
				"site",
				"WHERE id_site='".$id_site."'"
			);
			
			$htmls['desktop'] = $site[0]['html'];
			$htmls['mobile'] = $site[0]['html_mobile'];
			$google_fontes = $site[0]['google_fontes'];
			
			if($htmls)
			foreach($htmls as $key => $html_aux){
				$google_fonts_loaded = false;
				$dom = new DOMDocument();
				$dom->loadHTML($html_aux, LIBXML_HTML_NOIMPLIED | HTML_PARSE_NOIMPLIED | LIBXML_HTML_NODEFDTD);
				
				$finder = new DomXPath($dom);
				$classname = "b2make-widget";
				$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
				
				if($nodes)
				foreach($nodes as $widget){
					switch($widget->attributes->getNamedItem('data-type')->value){
						case 'texto':
							$mudar_valor = false;
							
							switch($widget->attributes->getNamedItem('data-marcador')->value){
								case '@cat-services#titulo':
									$mudar_valor = $nome;
								break;
							}
							
							$mudar_valor = preg_replace('/&/i', '&amp;', $mudar_valor);
							
							if($mudar_valor)
								$widget->childNodes->item(0)->childNodes->item(0)->nodeValue = $mudar_valor;
						break;
					}
				}
				
				$html_aux = $dom->saveHTML();
				
				switch($key){
					case 'desktop': $html = $html_aux; break;
					case 'mobile': $html_mobile = $html_aux; break;
				}
			}
			
			banco_update
			(
				"html='".addslashes($html)."',".
				"html_mobile='".addslashes($html_mobile)."',".
				"nome='".$nome."',".
				"pagina_titulo='".$nome."',".
				"id='".$id."'",
				"site",
				"WHERE id_site='".$id_site."'"
			);
			
			$site = site_publish_page(Array(
				'nao_fechar_ftp' => true,
				'id_site' => $id_site,
				'html' => $html,
				'google_fontes' => $google_fontes,
				'mobile' => false,
				'layout_site' => $_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html',
			));
			
			$site = site_publish_page(Array(
				'id_site' => $id_site,
				'html' => $html_mobile,
				'google_fontes' => $google_fontes,
				'mobile' => true,
				'layout_site' => $_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html',
			));
			
			$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
			
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'msg' => 'This category isn\'t yours',
				'status' => 'ERRO',
			);
		}
	} else {
		$saida = Array(
			'msg' => 'Name and/or id not defined',
			'status' => 'ERRO',
		);
	}
	return $saida;
}

function ajax_categoria_del(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($_REQUEST['id']){
		$id_loja = $usuario['id_loja'];
		$id_servicos_categorias = $_REQUEST['id'];
		
		$servicos_categorias = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
			))
			,
			"servicos_categorias",
			"WHERE id_servicos_categorias='".$id_servicos_categorias."'"
			." AND id_loja='".$id_loja."'"
		);
		
		if($servicos_categorias){
			$id_site = $servicos_categorias[0]['id_site'];
			
			site_delete_page(Array(
				'id_site' => $id_site,
			));
			
			banco_delete
			(
				"servicos_categorias",
				"WHERE id_servicos_categorias='".$id_servicos_categorias."'"
			);
			
			banco_update
			(
				"id_servicos_categorias=NULL",
				"servicos",
				"WHERE id_servicos_categorias='".$id_servicos_categorias."'"
			);
			
			$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
			
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'msg' => 'This category isn\'t yours',
				'status' => 'ERRO',
			);
		}
	} else {
		$saida = Array(
			'msg' => 'Id not defined',
			'status' => 'ERRO',
		);
	}
	return $saida;
}

// ======================================================================================

function teste(){
	global $_SYSTEM;
	global $_DEBUG_CONT;
	global $_B2MAKE_URL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
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
	
	require($_SYSTEM['PATH'].'includes/php/phpQuery/phpQuery.php');
	
	
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
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = $_REQUEST["query"];
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
				'value' => $resultado[$i][1],
				'id' => $resultado[$i][0],
			);
		}
	}
	
	switch($_REQUEST["opcao"]){
		case 'biblioteca_imagens_lista': $saida = ajax_biblioteca_imagens_lista(); break;
		case 'biblioteca_imagens_delete': $saida = ajax_biblioteca_imagens_delete(); break;
		case 'observacao_padrao': $saida = ajax_observacao_padrao(); break;
		case 'descricao_padrao': $saida = ajax_descricao_padrao(); break;
		case 'categoria-add': $saida = ajax_categoria_add(); break;
		case 'categoria-edit': $saida = ajax_categoria_edit(); break;
		case 'categoria-del': $saida = ajax_categoria_del(); break;
	}
	
	return json_encode($saida);
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
		servico_start_vars();
		
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
			case 'qrcode':						$saida = services_qrcode();break;
			case 'teste':						$saida = teste();break;
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