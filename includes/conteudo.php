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

$_VERSAO_MODULO_INCLUDE				=	'1.7.0';

function conteudo_categoria_produtos_filhos($id){
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_categorias_produtos',
		))
		,
		"categorias_produtos",
		"WHERE id_categorias_produtos_pai='".$id."'"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$ids_aux = conteudo_categoria_produtos_filhos($res['id_categorias_produtos']);
			$ids .= ($ids?',':'').$res['id_categorias_produtos'].($ids_aux?',':'').$ids_aux;
		}
		
		return $ids;
	} else {
		return false;
	}
}

function conteudo_categoria_produtos($id){
	global $_SYSTEM;
	global $_PROJETO;
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_categorias_produtos',
		))
		,
		"categorias_produtos",
		"WHERE id_conteudo='".$id."'"
	);
	
	if($resultado){
		$ids = conteudo_categoria_produtos_filhos($resultado[0]['id_categorias_produtos']);
		$ids = $resultado[0]['id_categorias_produtos'].($ids?',':'').$ids;
		
		$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/index.php');
		
		$_PROJETO['ecommerce']['loja_online'] = '
	<div id="_loja-online-cont">
	<!-- loja-online -->
	</div>';
		
		$pagina = ecommerce_loja_online(Array(
			'nao_mudar_titulo' => true,
			'nao_mudar_metas' => true,
			'categorias_produtos' => $ids,
		));
	}
	
	return $pagina;
}

function conteudo_glossario($texto){
	global $_VARIAVEIS_JS;
	global $_VERSAO_MODULO_INCLUDE;
	global $_SCRIPTS_JS;
	global $_STYLESHEETS;
	
	$_STYLESHEETS[] = 'includes/js/zglossary/jquery.zglossary.min.css?v='.$_VERSAO_MODULO_INCLUDE;
	$_SCRIPTS_JS[] = 'includes/js/zglossary/jquery.zglossary.min.js?v='.$_VERSAO_MODULO_INCLUDE;
	$_VARIAVEIS_JS['glossario'] = true;
	
	return $texto;
}

function conteudo_servico_duvidas($id){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_CAMINHO;
	global $_CONEXAO_BANCO;
	global $_VARS;
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['duvidas']){
			$layout = $_PROJETO['ecommerce']['duvidas'];
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- duvidas < -->','<!-- duvidas > -->');
		
		$layout = $pagina;
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'caminho_raiz',
		))
		,
		"conteudo",
		"WHERE identificador='".$id."'"
	);
	
	$url = html(Array(
		'tag' => 'a',
		'val' => $resultado[0]['titulo'],
		'attr' => Array(
			'href' => '/'.$_SYSTEM['ROOT'].$resultado[0]['caminho_raiz'].$id,
		)
	));
	
	$layout = modelo_var_troca_tudo($layout,"#identificador#",$url);
	$layout = modelo_var_troca_tudo($layout,"#id#",$id);
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	return '<div style="display:none;"><div id="_duvidas-conteiner">'.$layout.'</div></div>';
}

function conteudo_indique($id){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_CAMINHO;
	global $_CONEXAO_BANCO;
	global $_VARS;
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['indique']){
			$layout = $_PROJETO['ecommerce']['indique'];
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- indique < -->','<!-- indique > -->');
		
		$layout = $pagina;
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'caminho_raiz',
		))
		,
		"conteudo",
		"WHERE identificador='".$id."'"
	);
	
	$url = html(Array(
		'tag' => 'a',
		'val' => $resultado[0]['titulo'],
		'attr' => Array(
			'href' => '/'.$_SYSTEM['ROOT'].$resultado[0]['caminho_raiz'].$id,
		)
	));
	
	$layout = modelo_var_troca_tudo($layout,"#identificador#",$url);
	$layout = modelo_var_troca_tudo($layout,"#id#",$id);
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	return '<div style="display:none;"><div id="_indique-conteiner">'.$layout.'</div></div>';
}

function conteudo_servico_conteudos($id){
	global $_SYSTEM;
	global $_HTML;
	global $_HTML_META;
	global $_HTML_DADOS;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONTEUDO_VARS;
	global $_CONTEUDO_VARS_SEM_PERMISSAO;
	global $_CAMINHO;
	global $_ALERTA;
	global $_MENU_ID;
	global $_OPCAO;
	global $_INTERFACE;
	global $_MENU_DINAMICO;
	global $_AUDIO_PATH;
	global $_VARIAVEIS_JS;
	global $_CONTEUDO_ID_AUX;
	global $_PROJETO;
	
	$forcar = $params['forcar'];
	
	$parametros['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'index.html';
	
	if(!$params['erro_nao_encontrado'])$params['erro_nao_encontrado'] = "Resultado não encontrado";
	
	$conteudo_vars = array_merge($_CONTEUDO_VARS,$_CONTEUDO_VARS_SEM_PERMISSAO);
	
	if($params['forcar_id']) $id = $params['forcar_id'];
	if($params['forcar_cod']) $cod = $params['forcar_cod'];
	
	if(!$_CONEXAO_BANCO)$connect = true;
	if($connect)banco_conectar();
	
	$conteudo_vars_2 = $conteudo_vars;
	array_push($conteudo_vars_2,'hits');
	array_push($conteudo_vars_2,'id_conteudo_pai');
	array_push($conteudo_vars_2,'description');
	array_push($conteudo_vars_2,'tipo');
	array_push($conteudo_vars_2,'titulo_img_title');
	array_push($conteudo_vars_2,'titulo_img_alt');
	array_push($conteudo_vars_2,'imagem_pequena_title');
	array_push($conteudo_vars_2,'imagem_pequena_alt');
	array_push($conteudo_vars_2,'imagem_grande_title');
	array_push($conteudo_vars_2,'imagem_grande_alt');
	array_push($conteudo_vars,'description');
	array_push($conteudo_vars,'galeria_todas');
	array_push($conteudo_vars,'videos_todas');
	array_push($conteudo_vars,'conteudos_relacionados');
	array_push($conteudo_vars,'comentarios');
	array_push($conteudo_vars,'comentarios_facebook');
	array_push($conteudo_vars,'categoria');
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas($conteudo_vars_2)
		,
		"conteudo",
		"WHERE identificador='".$id."'"
		." AND status='A'"
	);
	
	if(!$id)$id = $resultado[0]['identificador'];
	if(!$cod)$cod = $resultado[0]['id_conteudo'];
	
	if($_PROJETO['conteudo']){
		if($_PROJETO['conteudo']['permissao'][$id]){
			$conteudo_perfil = true;
			if($_PROJETO['conteudo']['permissao'][$id] == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
				$conteudo_perfil = false;
			}
		}
	}
	
	if($conteudo_perfil){
		$pagina = login();
	} else {
		if($resultado){
			banco_update
			(
				"hits='".($resultado[0]['hits']?$resultado[0]['hits']+1:1)."'",
				"conteudo",
				"WHERE identificador='".$id."'"
			);
			
			$conteudo_permissao = conteudo_permisao($cod);
			
			//if($conteudo_permissao['no_robots'])$_HTML_DADOS['noindex'] = true;
			//if($conteudo_permissao['no_layout'])$_HTML_DADOS['no_layout'] = true;
			
			if(!$conteudo_permissao['layout_status']){
				$modelo = paginaModelo($parametros['layout_url']);
				$pagina = paginaTagValor($modelo,'<!-- conteudo < -->','<!-- conteudo > -->');
			} else {
				$pagina = $conteudo_permissao['layout'];
			}
			$variavel = $params['variavel'];
			
			if($conteudo_permissao['conteiner_posicao'] && $conteudo_permissao['cont_padrao_posicao_x']){
				$posicao['conteiner_posicao_x'] = $conteudo_permissao['cont_padrao_posicao_x'];
			}
			if($conteudo_permissao['conteiner_posicao'] && $conteudo_permissao['cont_padrao_posicao_y']){
				$posicao['conteiner_posicao_y'] = $conteudo_permissao['cont_padrao_posicao_y'];
			}
			
			foreach($conteudo_vars as $var){
				$cel_nome = $var; 
				
				switch($var){
					case 'conteiner_posicao_x':
						if($conteudo_permissao['conteiner_posicao'] && $resultado[0][$cel_nome]){
							$posicao['conteiner_posicao_x'] = $resultado[0][$cel_nome];
						}
					break;
					case 'conteiner_posicao_y':
						if($conteudo_permissao['conteiner_posicao'] && $resultado[0][$cel_nome]){
							$posicao['conteiner_posicao_y'] = $resultado[0][$cel_nome];
						}
					break;
					case 'titulo':
					case 'description':
					case 'keywords':
						if($var == 'titulo'){
							$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
							//$_HTML_DADOS[$var] = $titulo . $resultado[0][$cel_nome];
						} else {
							//$_HTML_DADOS[$var] = ($resultado[0][$cel_nome]?$resultado[0][$cel_nome]:' ');
						}
					break;
				}
				
				if(!$conteudo_permissao[$cel_nome]){ 
					if($var == 'sub_titulo' && $params['forcar_h1_to_h2']){
						$dado = $sub_titulo;
						$pagina = modelo_var_troca($pagina,"#".$cel_nome."#",$dado);
					} else if($var == 'titulo' && $params['forcar_sub_to_tit']){
						$dado = $resultado[0]['sub_titulo'];
						$pagina = modelo_var_troca($pagina,"#".$cel_nome."#",$dado);
						$pagina = modelo_tag_in($pagina,'<!-- sub_titulo < -->','<!-- sub_titulo > -->','');
					} else {
						$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
				} else {
					$dado = $resultado[0][$cel_nome];
					$variaveis[$var] = $dado;
					
					if($variavel[$var]){
						$modificacoes = $variavel[$var];
						
						if($modificacoes['link']){
							$link = $modificacoes['link'];
							$dado = html(Array(
								'tag' => 'a',
								'val' => $dado,
								'attr' => Array(
									'href' => $link['href'],
									'target' => $link['target'],
									'class' => $link['class'],
									'title' => $link['title'],
								)
							));
						}
						if($modificacoes['data'])$dado = data_hora_from_datetime_to_text($dado);
					} else {
						if($forcar[$var]){
							$dado = $forcar[$var];
						}
						
						switch($var){
							case 'data': if($dado)$dado = data_hora_from_datetime_to_text($dado); break;
							case 'titulo_img':
							case 'imagem_pequena':
							case 'imagem_grande':
								if($dado){
									$image_info = imagem_info($dado);
									
									$width = $image_info[0];
									$height = $image_info[1];
									$dado = html(Array(
										'tag' => 'img',
										'val' => '',
										'attr' => Array(
											'src' => $_CAMINHO_RELATIVO_RAIZ.$dado,
											'title' => $resultado[0][$var.'_title'],
											'alt' => $resultado[0][$var.'_alt'],
											'width' => $width,
											'height' => $height,
										)
									));
								}
							break;
							case 'texto':
							case 'texto2':
								if($dado){
									if($conteudo_permissao['glossario']){
										$dado = conteudo_glossario($dado);
									}
								}
							break;
							case 'titulo':
								if($_PROJETO['conteudo']['titulo_como_menu_navegacao']){
									$dado = conteudo_titulo_como_menu_navegacao($resultado[0]['id_conteudo_pai']) . $dado;
								} else {
									if($params['retranca_titulo']){
										$titulo_aux = $dado;
										$dado = $params['retranca_titulo'];
									} else if($params['forcar_h1']){
										$sub_titulo = $dado;
										$dado = $params['forcar_h1'];
									}
								}
							break;
							case 'sub_titulo':
								if($params['forcar_h1_to_h2']){
									$dado = $sub_titulo;
								}
							break;
							case 'author':
								if($dado){
									$_HTML_META['author'] = $dado;
								}
							break;
							case 'musica':
								if($dado){
									$_AUDIO_PATH = $dado;
									$dado = conteudo_jplayer();
								}
							break;
							case 'galeria':
								if($dado)$dado = conteudo_galerias_imagens_pretty_photo($dado);
							break;
							case 'galeria_grupo':
								if($dado){
									$_HTML['variaveis']['menu_dinamico_inicial'] = 
									$_MENU_DINAMICO = $id;
									if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
									
									$galeria_grupo_vars = Array(
										'opcao' => 'galerias',
										'ferramenta' => 'galerias',
										'frame_width' => false,
										'frame_margin' => '12',
										'tabela_nome' => 'galerias',
										'tabela_extra' => "WHERE status='A' AND grupo='".$dado."'",
										'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
										'tabela_limit' => false,
										'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
										'tabela_id_posicao' => 0,
										'tabela_campos' => Array(
											'id_galerias',
											'nome',
											'identificador',
										),
										'menu_paginas_id' => 'menu_galeria_grupo-imagens',
										'num_colunas' => 3,
										'link_externo' => true,
										'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-imagens/#id/',
										'link_target' => '_self',
										'menu_pagina_embaixo' => true,
										'imagem_tamanho' => 'local_media',
										'titulo_class' => 'fotos-galerias-titulo',
										'link_class' => 'fotos-galerias-link',
										'link_class_ajuste_margin' => 10,
										'menu_dont_show' => true, // Título da Informação
										'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
										'desativar_conteiner_secundario' => true,
									);
									
									if($_PROJETO['conteudo']){
										if($_PROJETO['conteudo']['galeria_grupo_vars']){
											$galeria_grupo_vars_2 = $_PROJETO['conteudo']['galeria_grupo_vars'];
											
											foreach($galeria_grupo_vars_2 as $key => $var){
												$galeria_grupo_vars[$key] = $var;
											}
										}
									}
									
									$dado = interface_layout($galeria_grupo_vars);
								}
							break;
							case 'galeria_todas':
								$_HTML['variaveis']['menu_dinamico_inicial'] = 
								$_MENU_DINAMICO = $id;
								if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
								
								$galeria_todas_vars = Array(
									'opcao' => 'galerias',
									'ferramenta' => 'galerias',
									'frame_width' => false,
									'frame_margin' => '12',
									'tabela_nome' => 'galerias',
									'tabela_extra' => "WHERE status='A'",
									'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
									'tabela_limit' => false,
									'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
									'tabela_id_posicao' => 0,
									'tabela_campos' => Array(
										'id_galerias',
										'nome',
										'identificador',
									),
									'menu_paginas_id' => 'menu_galerias-imagens',
									'num_colunas' => 3,
									'link_externo' => true,
									'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-imagens/#id/',
									'link_target' => '_self',
									'menu_pagina_embaixo' => true,
									'imagem_tamanho' => 'local_media',
									'titulo_class' => 'fotos-galerias-titulo',
									'link_class' => 'fotos-galerias-link',
									'link_class_ajuste_margin' => 10,
									'menu_dont_show' => true, // Título da Informação
									'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
									'desativar_conteiner_secundario' => true,
								);
								
								if($_PROJETO['conteudo']){
									if($_PROJETO['conteudo']['galeria_todas_vars']){
										$galeria_todas_vars_2 = $_PROJETO['conteudo']['galeria_todas_vars'];
										
										foreach($galeria_todas_vars_2 as $key => $var){
											$galeria_todas_vars[$key] = $var;
										}
									}
								}
								
								$dado = interface_layout($galeria_todas_vars);
							break;
							case 'videos':
								if($dado)$dado = conteudo_galerias_videos_youtube_pretty_photo($dado);
							break;
							case 'videos_grupo':
								if($dado){
									$_HTML['variaveis']['menu_dinamico_inicial'] = 
									$_MENU_DINAMICO = $id;
									if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
									
									$videos_grupo_vars = Array(
										'opcao' => 'galerias',
										'ferramenta' => 'galerias',
										'frame_width' => false,
										'frame_margin' => '12',
										'tabela_nome' => 'galerias_videos',
										'tabela_extra' => "WHERE status='A' AND grupo='".$dado."'",
										'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
										'tabela_limit' => false,
										'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
										'tabela_id_posicao' => 0,
										'tabela_campos' => Array(
											'id_galerias_videos',
											'nome',
											'identificador',
										),
										'menu_paginas_id' => 'menu_galeria_grupo-videos',
										'num_colunas' => 3,
										'link_externo' => true,
										'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-videos/#id/',
										'link_target' => '_self',
										'menu_pagina_embaixo' => true,
										'imagem_tamanho' => 'imagem_media',
										'titulo_class' => 'fotos-galerias-videos-titulo',
										'link_class' => 'fotos-videos-link',
										'link_class_ajuste_margin' => 10,
										'menu_dont_show' => true, // Título da Informação
										'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
										'desativar_conteiner_secundario' => true,
										'galeria_imagens_banco_nome' => 'videos',
										'galeria_imagens_banco_id' => 'id_galerias_videos',
									);
									
									if($_PROJETO['conteudo']){
										if($_PROJETO['conteudo']['videos_grupo_vars']){
											$videos_grupo_vars_2 = $_PROJETO['conteudo']['videos_grupo_vars'];
											
											foreach($videos_grupo_vars_2 as $key => $var){
												$videos_grupo_vars[$key] = $var;
											}
										}
									}
									
									$dado = interface_layout($videos_grupo_vars);
								}
							break;
							case 'videos_todas':
								$_HTML['variaveis']['menu_dinamico_inicial'] = 
								$_MENU_DINAMICO = $id;
								if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
								
								$videos_todas_vars = Array(
									'opcao' => 'galerias',
									'ferramenta' => 'galerias',
									'frame_width' => false,
									'frame_margin' => '12',
									'tabela_nome' => 'galerias_videos',
									'tabela_extra' => "WHERE status='A'",
									'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
									'tabela_limit' => false,
									'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
									'tabela_id_posicao' => 0,
									'tabela_campos' => Array(
										'id_galerias_videos',
										'nome',
										'identificador',
									),
									'menu_paginas_id' => 'menu_galerias-videos',
									'num_colunas' => 3,
									'link_externo' => true,
									'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-videos/#id/',
									'link_target' => '_self',
									'menu_pagina_embaixo' => true,
									'imagem_tamanho' => 'imagem_media',
									'titulo_class' => 'fotos-galerias-videos-titulo',
									'link_class' => 'fotos-videos-link',
									'link_class_ajuste_margin' => 10,
									'menu_dont_show' => true, // Título da Informação
									'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
									'desativar_conteiner_secundario' => true,
									'galeria_imagens_banco_nome' => 'videos',
									'galeria_imagens_banco_id' => 'id_galerias_videos',
								);
								
								if($_PROJETO['conteudo']){
									if($_PROJETO['conteudo']['videos_todas_vars']){
										$videos_todas_vars_2 = $_PROJETO['conteudo']['videos_todas_vars'];
										
										foreach($videos_todas_vars_2 as $key => $var){
											$videos_todas_vars[$key] = $var;
										}
									}
								}
								
								$dado = interface_layout($videos_todas_vars);
							break;
							case 'conteudos_relacionados':
								if($resultado[0]['tipo'] == 'L'){
									$dado = conteudo_filhos($cod,$id,$resultado[0]['id_conteudo_pai']);
								}
							break;
							case 'comentarios':
								$dado = conteudo_comentarios($resultado[0]['id_conteudo']);
							break;
							case 'servico':
								if($dado)$dado = conteudo_servico($dado,$id);
							break;
							
						}
					}
					
					$pagina = modelo_var_troca_tudo($pagina,"#".$cel_nome."#",$dado);
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' < -->','');
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' > -->','');
				}
			
			}
			
			//if($posicao['conteiner_posicao_x'] && $posicao['conteiner_posicao_y']){
		//		$_VARIAVEIS_JS['conteiner_posicao'] = $conteudo_permissao['conteiner_posicao'];
		//		$_VARIAVEIS_JS['conteiner_posicao_x'] = $posicao['conteiner_posicao_x'];
		//		$_VARIAVEIS_JS['conteiner_posicao_y'] = $posicao['conteiner_posicao_y'];
		//		$_VARIAVEIS_JS['conteiner_posicao_efeito'] = $conteudo_permissao['conteiner_posicao_efeito'];
		//		$_VARIAVEIS_JS['conteiner_posicao_tempo'] = $conteudo_permissao['conteiner_posicao_tempo'];
			//}
			
			if($params['retranca_titulo']){
				$pagina = modelo_var_troca_tudo($pagina,$params['retranca_cel_titulo_aux'],$titulo_aux);
			}
			
			if($conteudo_permissao['addthis']){
				if(preg_match('/#addthis#/i', $pagina) > 0){
					$cel_nome = 'addthis';
					$pagina = modelo_var_troca_tudo($pagina,"#".$cel_nome."#",$_SYSTEM['ADD-THIS']);
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' < -->','');
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' > -->','');
				} else if($params['modulo_noticias']){
					$params['info_abaixo'] = $_SYSTEM['ADD-THIS'] . $params['info_abaixo'];
				} else {
					$params['info_abaixo'] .= $_SYSTEM['ADD-THIS'];
				}
				//$_HTML['ADD-THIS'] = true;
			} else {
				if(preg_match('/#addthis#/i', $pagina) > 0){
					$cel_nome = 'addthis';
					$pagina = modelo_var_troca_tudo($pagina,"#".$cel_nome."#",'');
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' < -->','');
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' > -->','');
				}
			}
			
			$pagina = modelo_var_troca($pagina,"#info_acima#",$params['info_acima']);
			$pagina = modelo_var_troca($pagina,"#info_abaixo#",$params['info_abaixo']);
		} else {
			$erro = true;
		}
	}
	
	$modulos_tags = $params['modulos_tags'];
	
	if($modulos_tags)
	foreach($modulos_tags as $tag){
		if(preg_match('/'.$tag.'/i', $pagina) > 0){
			$modulo = modulos(Array(
				'modulo_tag' => $tag,
			));
			
			$pagina = preg_replace('/'.$tag.'/i', $modulo, $pagina);
		}
	}
	
	if($connect)banco_fechar_conexao();
	
	if($params['layout']){
		$pagina = modelo_var_troca($params['layout'],"!#CONTEUDO#!",$pagina);
	}
	
	if($_PROJETO['conteudo']){
		if($_PROJETO['conteudo']['produto_servico_como_comprar_layout']){
			$pagina = modelo_var_troca($_PROJETO['conteudo']['produto_servico_como_comprar_layout'],"#pagina#",$pagina);
		}
	}
	
	return '<div style="display:none;"><div id="_'.$id.'-conteiner">'.$pagina.'</div></div>';
}

function conteudo_parcela_valor_pagseguro($valor_total,$parcela){
	$fator = Array(
		1 => 1,
		2 => 0.52255,
		3 => 0.35347,
		4 => 0.26898,
		5 => 0.21830,
		6 => 0.18453,
		7 => 0.16044,
		8 => 0.14240,
		9 => 0.12838,
		10 => 0.11717,
		11 => 0.10802,
		12 => 0.10040,
		13 => 0.09397,
		14 => 0.08846,
		15 => 0.08371,
		16 => 0.07955,
		17 => 0.07589,
		18 => 0.07265,
	);
	
	$parcela_valor = $valor_total * $fator[$parcela];
	
	if($parcela_valor >= 5){
		return Array($parcela => $parcela_valor);
	} else {
		if($parcela > 2){
			return conteudo_parcela_valor_pagseguro($valor_total,$parcela-1);
		} else {
			return Array(1 => $valor_total);
		}
	}
}

function conteudo_servico($id,$identificador){
	global $_PROJETO;
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'descricao',
			'imagem_path',
			'preco',
			'visivel_de',
			'visivel_ate',
			'desconto',
			'desconto_de',
			'desconto_ate',
			'validade',
			'observacao',
		))
		,
		"servicos",
		"WHERE id_servicos='".$id."'"
		." AND quantidade > 0"
		." AND status='A'"
	);
	
	if($resultado){
		$flag_de_ate = true;
		$desconto_de_ate = false;
		
		if($resultado[0]['visivel_de']){
			$resultado_de = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id."'"
				." AND visivel_de <= NOW()"
			);
			
			if(!$resultado_de){
				$flag_de_ate = false;
			}
		}
		
		if($resultado[0]['visivel_ate']){
			$resultado_ate = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id."'"
				." AND visivel_ate >= NOW()"
			);
			
			if(!$resultado_ate){
				$flag_de_ate = false;
			}
		}
		
		if($flag_de_ate){
			if($resultado[0]['desconto']){
				$desconto_de_ate = true;
			}
			
			if($resultado[0]['desconto_de']){
				$desconto_de_ate = true;
				$desconto_de = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_servicos',
					))
					,
					"servicos",
					"WHERE id_servicos='".$id."'"
					." AND desconto_de <= NOW()"
				);
				
				if(!$desconto_de){
					$desconto_de_ate = false;
				}
			}
			
			if($resultado[0]['desconto_ate']){
				$desconto_de_ate = true;
				$desconto_ate = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_servicos',
					))
					,
					"servicos",
					"WHERE id_servicos='".$id."'"
					." AND desconto_ate >= NOW()"
				);
				
				if(!$desconto_ate){
					$desconto_de_ate = false;
				}
			}
			
			$servico = $resultado[0];
			
			if($_PROJETO['conteudo']){
				if($_PROJETO['conteudo']['servico_layout']){
					$layout = $_PROJETO['conteudo']['servico_layout'];
				}
			}
			
			if(!$layout){
				$layout = '
				<div class="_servico-cont" id="#id#" data-preco="#preco-float#" data-titulo="#titulo#" data-validade="#validade#" data-observacao="#observacao#" data-desconto="#desconto#">
					<div class="_servico-img">#img#</div>
					<div class="_servico-cont-int">
						<div class="_servico-quant">#quant#</div>
						<div class="clear"></div>
						<div class="_servico-preco">#preco#</div>
						<a href="#_como-comprar-conteiner" class="_servico-como-comprar _ajax_nao _colorbox">COMO<br><span class="_servico-span">COMPRAR</span></a>
						<a href="#_indique-conteiner" class="_servico-indique _ajax_nao _colorbox">INDIQUE<br><span class="_servico-span">ESTE SERVIÇO</span></a>
						<a href="#_perguntas-frequentes-conteiner" class="_servico-perguntas _ajax_nao _colorbox">PERGUNTAS<br><span class="_servico-span">FREQUENTES</span></a>
						<a href="#_duvidas-conteiner" class="_servico-duvidas _ajax_nao _colorbox">DÚVIDA<br><span class="_servico-span">TIRE SUA DÚVIDA<br>SOBRE ESTE SERVIÇO</span></a>
					</div>
					<div class="_servico-botao">COMPRAR</div>
				</div>
				<div class="clear"></div>';
			}
			
			$quant = '<label class="_servico-quant-lbl"><b>Quant.</b> a comprar</label><input type="text" class="inteiro _servico-quant-inp" maxlength="1" value="1">';
			
			if($desconto_de_ate){
				$valor_desconto_float = (($servico['preco'] * (100 - $servico['desconto'])) / 100);
				$parcela_valor = conteudo_parcela_valor_pagseguro($valor_desconto_float,10);
				
				$desconto = $servico['desconto'];
				
				if($parcela_valor)
				foreach($parcela_valor as $parcela => $valor_parcela){
					break;
				}
				
				$valor_parcela = number_format($valor_parcela, 2, ",", ".");
				$valor_total = number_format($servico['preco'], 2, ",", ".");
				$valor_desconto = number_format($valor_desconto_float, 2, ",", ".");
				
				$preco = 'De <span class="_servico-valtxt2">R$ '.$valor_total.'</span><br>Por <span class="_servico-valtxt3">R$ '.$valor_desconto.'</span><br>em <span class="_servico-valtxt4">'.$parcela.'x</span> de <span class="_servico-valtxt4">R$ '.$valor_parcela.'</span>';
				$preco_float = $valor_desconto_float;
			} else {
				$parcela_valor = conteudo_parcela_valor_pagseguro($servico['preco'],10);
				
				if($parcela_valor)
				foreach($parcela_valor as $parcela => $valor_parcela){
					break;
				}
				
				$valor_parcela = number_format($valor_parcela, 2, ",", ".");
				$valor_total = number_format($servico['preco'], 2, ",", ".");
			
				$preco = 'Por <span class="_servico-valtxt">R$ '.$valor_total.'</span><br>em <span class="_servico-valtxt4">'.$parcela.'x</span> de <span class="_servico-valtxt4">R$ '.$valor_parcela.'</span>';
				$preco_float = $servico['preco'];
			}
			
			$layout = modelo_var_troca($layout,"#img#",'<img src="'.path_com_versao_arquivo($servico['imagem_path']).' alt="'.$servico['nome'].'">');
			$layout = modelo_var_troca($layout,"#tit#",$servico['nome']);
			$layout = modelo_var_troca($layout,"#titulo#",$servico['nome']);
			$layout = modelo_var_troca($layout,"#validade#",$servico['validade']);
			$layout = modelo_var_troca($layout,"#observacao#",$servico['observacao']);
			$layout = modelo_var_troca($layout,"#desc#",$servico['descricao']);
			$layout = modelo_var_troca($layout,"#quant#",$quant);
			$layout = modelo_var_troca($layout,"#desconto#",$desconto);
			$layout = modelo_var_troca($layout,"#preco#",$preco);
			$layout = modelo_var_troca($layout,"#preco-float#",$preco_float);
			$layout = modelo_var_troca($layout,"#id#",$id);
			$layout = modelo_var_troca($layout,"#url-como-comprar#",'/'.$_SYSTEM['ROOT'].'como-comprar');
			$layout = modelo_var_troca($layout,"#url-indique#",'/'.$_SYSTEM['ROOT'].'indique/'.$identificador);
			$layout = modelo_var_troca($layout,"#url-perguntas#",'/'.$_SYSTEM['ROOT'].'perguntas-frequentes');
			$layout = modelo_var_troca($layout,"#url-duvidas#",'/'.$_SYSTEM['ROOT'].'duvidas/'.$identificador);
			
			$layout .= conteudo_servico_conteudos('como-comprar');
			$layout .= conteudo_servico_conteudos('perguntas-frequentes');
			$layout .= conteudo_indique($identificador);
			$layout .= conteudo_servico_duvidas($identificador);
			
			return $layout;
		} else {
			return '';
		}
	} else {
		return '';
	}
}

function conteudo_produto($id,$identificador){
	global $_PROJETO;
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	global $_STYLESHEETS;
	
	$_STYLESHEETS[] = 'includes/ecommerce/css.css?v='.$_VERSAO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'descricao',
			'preco',
			'visivel_de',
			'visivel_ate',
			'desconto',
			'desconto_de',
			'desconto_ate',
			'validade',
			'observacao',
			'quantidade',
		))
		,
		"produtos",
		"WHERE id_produtos='".$id."'"
		." AND status='A'"
	);
	
	if($resultado){
		$flag_de_ate = true;
		$desconto_de_ate = false;
		
		if((int)$resultado[0]['quantidade'] > 0){
			if($resultado[0]['visivel_de']){
				$resultado_de = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_produtos',
					))
					,
					"produtos",
					"WHERE id_produtos='".$id."'"
					." AND visivel_de <= NOW()"
				);
				
				if(!$resultado_de){
					$flag_de_ate = false;
				}
			}
			
			if($resultado[0]['visivel_ate']){
				$resultado_ate = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_produtos',
					))
					,
					"produtos",
					"WHERE id_produtos='".$id."'"
					." AND visivel_ate >= NOW()"
				);
				
				if(!$resultado_ate){
					$flag_de_ate = false;
				}
			}
			
			if($flag_de_ate){
				if($resultado[0]['desconto']){
					$desconto_de_ate = true;
				}
				
				if($resultado[0]['desconto_de']){
					$desconto_de_ate = true;
					$desconto_de = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_produtos',
						))
						,
						"produtos",
						"WHERE id_produtos='".$id."'"
						." AND desconto_de <= NOW()"
					);
					
					if(!$desconto_de){
						$desconto_de_ate = false;
					}
				}
				
				if($resultado[0]['desconto_ate']){
					$desconto_de_ate = true;
					$desconto_ate = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_produtos',
						))
						,
						"produtos",
						"WHERE id_produtos='".$id."'"
						." AND desconto_ate >= NOW()"
					);
					
					if(!$desconto_ate){
						$desconto_de_ate = false;
					}
				}
				
				$servico = $resultado[0];
				
				if($_PROJETO['conteudo']){
					if($_PROJETO['conteudo']['servico_layout']){
						$layout = $_PROJETO['conteudo']['servico_layout'];
					}
				}
				
				if(!$layout){
					$layout = '
					<div class="_servico-cont" id="#id#" data-preco="#preco-float#" data-titulo="#titulo#" data-validade="#validade#" data-observacao="#observacao#" data-desconto="#desconto#">
						<div class="_servico-img">#img#</div>
						<div class="_servico-cont-int">
							<div class="_servico-quant">#quant#</div>
							<div class="clear"></div>
							<div class="_servico-preco">#preco#</div>
							<a href="#_como-comprar-conteiner" class="_servico-como-comprar _ajax_nao _colorbox">COMO<br><span class="_servico-span">COMPRAR</span></a>
							<a href="#_indique-conteiner" class="_servico-indique _ajax_nao _colorbox">INDIQUE<br><span class="_servico-span">ESTE SERVIÇO</span></a>
							<a href="#_perguntas-frequentes-conteiner" class="_servico-perguntas _ajax_nao _colorbox">PERGUNTAS<br><span class="_servico-span">FREQUENTES</span></a>
							<a href="#_duvidas-conteiner" class="_servico-duvidas _ajax_nao _colorbox">DÚVIDA<br><span class="_servico-span">TIRE SUA DÚVIDA<br>SOBRE ESTE SERVIÇO</span></a>
						</div>
						<div class="_servico-botao">COMPRAR</div>
					</div>
					<div class="clear"></div>';
				}
				
				$quant = '<label class="_servico-quant-lbl"><b>Quant.</b> a comprar</label><input type="text" class="inteiro _servico-quant-inp" maxlength="1" value="1">';
				
				if($desconto_de_ate){
					$valor_desconto_float = (($servico['preco'] * (100 - $servico['desconto'])) / 100);
					$parcela_valor = conteudo_parcela_valor_pagseguro($valor_desconto_float,10);
					
					$desconto = $servico['desconto'];
					
					if($parcela_valor)
					foreach($parcela_valor as $parcela => $valor_parcela){
						break;
					}
					
					$valor_parcela = number_format($valor_parcela, 2, ",", ".");
					$valor_total = number_format(($servico['preco']?$servico['preco']:0), 2, ",", ".");
					$valor_desconto = number_format($valor_desconto_float, 2, ",", ".");
					
					$preco = 'De <span class="_servico-valtxt2">R$ '.$valor_total.'</span><br>Por <span class="_servico-valtxt3">R$ '.$valor_desconto.'</span><br>em <span class="_servico-valtxt4">'.$parcela.'x</span> de <span class="_servico-valtxt4">R$ '.$valor_parcela.'</span>';
					$preco_float = $valor_desconto_float;
				} else {
					$parcela_valor = conteudo_parcela_valor_pagseguro($servico['preco'],10);
					
					if($parcela_valor)
					foreach($parcela_valor as $parcela => $valor_parcela){
						break;
					}
					
					$valor_parcela = number_format($valor_parcela, 2, ",", ".");
					$valor_total = number_format($servico['preco'], 2, ",", ".");
				
					$preco = 'Por <span class="_servico-valtxt">R$ '.$valor_total.'</span><br>em <span class="_servico-valtxt4">'.$parcela.'x</span> de <span class="_servico-valtxt4">R$ '.$valor_parcela.'</span>';
					$preco_float = $servico['preco'];
				}
				
				$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$id.$_SYSTEM['SEPARADOR'];
				$caminho_internet = '/'.$_SYSTEM['ROOT']."files/produtos/produto".$id."/";
				
				if(is_dir($caminho_fisico)){
					$abreDir = opendir($caminho_fisico);

					while (false !== ($file = readdir($abreDir))) {
						if ($file==".." || $file ==".") continue;
						
						if(preg_match('/produto_mini_/i', $file) > 0){
							$idExt = preg_replace('/produto_mini_/i', '', $file);
							$idExtArr = explode('.',$idExt);
							
							$mini = $file;
							$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
							$grande = preg_replace('/mini_/i', '', $file);
							
							if(!$primeiraImagem){
								$primeiraImagem = '<img id="_elevateZoom" src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
							}
							
							$menuImagens .= '<a href="#" data-image="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"> <img id="_elevateZoom" src="'.$caminho_internet.$mini.'" /> </a>';
						}
					}

					closedir($abreDir);
				}
				
				if($primeiraImagem){
					$images = '<div id="_elevateZoom-cont">' . $primeiraImagem . '<div id="_elevateZoom-gallery">' . $menuImagens . '</div>' . '</div>';
				}
				
				$layout = modelo_var_troca($layout,"#img#",$images);
				$layout = modelo_var_troca($layout,"#tit#",$servico['nome']);
				$layout = modelo_var_troca_tudo($layout,"#titulo#",$servico['nome']);
				$layout = modelo_var_troca($layout,"#validade#",$servico['validade']);
				$layout = modelo_var_troca($layout,"#observacao#",$servico['observacao']);
				$layout = modelo_var_troca($layout,"#desc#",$servico['descricao']);
				$layout = modelo_var_troca($layout,"#quant#",$quant);
				$layout = modelo_var_troca($layout,"#desconto#",$desconto);
				$layout = modelo_var_troca($layout,"#preco#",$preco);
				$layout = modelo_var_troca($layout,"#preco-float#",$preco_float);
				$layout = modelo_var_troca($layout,"#id#",$id);
				$layout = modelo_var_troca($layout,"#url-como-comprar#",'/'.$_SYSTEM['ROOT'].'como-comprar');
				$layout = modelo_var_troca($layout,"#url-indique#",'/'.$_SYSTEM['ROOT'].'indique/'.$identificador);
				$layout = modelo_var_troca($layout,"#url-perguntas#",'/'.$_SYSTEM['ROOT'].'perguntas-frequentes');
				$layout = modelo_var_troca($layout,"#url-duvidas#",'/'.$_SYSTEM['ROOT'].'duvidas/'.$identificador);
				
				$layout .= conteudo_servico_conteudos('como-comprar');
				$layout .= conteudo_servico_conteudos('perguntas-frequentes');
				$layout .= conteudo_indique($identificador);
				$layout .= conteudo_servico_duvidas($identificador);
				
				return $layout;
			} else {
				return '';
			}
		} else {
			if($_PROJETO['conteudo']){
				if($_PROJETO['conteudo']['sem_estoque_layout']){
					$layout = $_PROJETO['conteudo']['sem_estoque_layout'];
				}
			}
			
			if(!$layout){
				$layout = '
				<div class="_servico-cont">
					<div class="_servico-img">#img#</div>
					<div class="_servico-cont-int _indisponivel-cont-int">
						<div><h3>Produto indisponível!</h3><p>Me avise quando tiver disponobilidade?<br><input type="button" value="AVISAR" id="_indisponivel-btn"></p></div>
						<form id="_indisponivel-form">
							<label for="_indisponivel-nome">Nome:</label>
							<input type="text" id="_indisponivel-nome" name="indisponivel-nome">
							<label for="_indisponivel-email">Email:</label>
							<input type="text" id="_indisponivel-email" name="indisponivel-email">
							<input type="submit" value="Enviar">
							<input type="hidden" name="opcao" value="ecommerce-indisponivel">
							<input type="hidden" name="indisponivel-id" value="#id#">
						</form>
					</div>
				</div>
				<div class="clear"></div>';
			}
			
			$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$id.$_SYSTEM['SEPARADOR'];
			$caminho_internet = '/'.$_SYSTEM['ROOT']."files/produtos/produto".$id."/";
			
			if(is_dir($caminho_fisico)){
				$abreDir = opendir($caminho_fisico);

				while (false !== ($file = readdir($abreDir))) {
					if ($file==".." || $file ==".") continue;
					
					if(preg_match('/produto_mini_/i', $file) > 0){
						$idExt = preg_replace('/produto_mini_/i', '', $file);
						$idExtArr = explode('.',$idExt);
						
						$mini = $file;
						$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
						$grande = preg_replace('/mini_/i', '', $file);
						
						if(!$primeiraImagem){
							$primeiraImagem = '<img id="_elevateZoom" src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
						}
						
						$menuImagens .= '<a href="#" data-image="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"> <img id="_elevateZoom" src="'.$caminho_internet.$mini.'" /> </a>';
					}
				}

				closedir($abreDir);
			}
			
			if($primeiraImagem){
				$images = '<div id="_elevateZoom-cont">' . $primeiraImagem . '<div id="_elevateZoom-gallery">' . $menuImagens . '</div>' . '</div>';
			}
			
			$layout = modelo_var_troca($layout,"#img#",$images);
			$layout = modelo_var_troca($layout,"#tit#",$servico['nome']);
			$layout = modelo_var_troca($layout,"#id#",$id);
			
			return $layout;
		}
	} else {
		return '';
	}
}

function conteudo_comentarios_pai($resultado,$id){
	foreach($resultado as $res){
		if($res['id_comentarios'] == $id){
			if($res['pai']){
				return conteudo_comentarios_pai($resultado,$res['pai']);
			} else {
				return $id;
			}
		}
	}
	
	return $id;
}

function conteudo_comentarios($id){
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_VARS;
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	$titulo = '<div class="comentarios-titulo">#quantidade# Comentários</div>';
	
	$layout = '
	<div class="comentarios-cont" id="comentario-#num#">
		<div class="comentarios-texto">
			<div class="comentarios-autor">#autor#</div>
			<div class="comentarios-data">#data#</div>
			<div class="comentarios-conteudo">#conteudo#</div>
			<input type="button" name="comentarios-responder" class="comentarios-responder" value="Responder" data="#id_pai#" />
			#mais-comentarios#
		</div>
	</div>
';
	$layout2 = '
	<div class="comentarios-cont" id="comentario-#num#">
		<div class="comentarios-texto">
			<div class="comentarios-autor">#autor#</div>
			<div class="comentarios-data">#data#</div>
			<div class="comentarios-conteudo">#conteudo#</div>
			<input type="button" name="comentarios-responder" class="comentarios-responder" value="Responder" data="#id_pai#" />
		</div>
	</div>
';
	$hr = '<hr class="comentarios-hr" />';
	
	$formulario = '
	<span id="comentario-posicao-form"></span>
	<div id="comentarios-form-cont">
		'.$hr.'
		<div class="comentario-form-titulo">Deixe um Comentário <span id="comentario-cancelar">Cancelar resposta</span></div>
		<div class="comentario-form-info">O seu endereço de email não será publicado</div>
		<form action="" id="form_comentarios" name="form_comentarios">
			<label for="comentarios-nome">Nome</label>
			<input type="text" name="nome" id="comentarios-nome" />
			<label for="comentarios-email">Email</label>
			<input type="text" name="email" id="comentarios-email" />
			<label for="comentarios-conteudo">Comentário</label>
			<textarea name="conteudo" id="comentarios-conteudo"></textarea>
			<div id="recaptcha_div"></div>
			<input type="hidden" name="id_conteudo" id="comentarios-id_conteudo" value="'.$id.'" />
			<input type="hidden" name="pai" id="comentarios-pai" value="" />
			<input type="hidden" name="opcao" value="comentarios_banco" />
			<input type="button" name="comentarios-publicar" value="Publicar Comentário" id="comentario-form-botao" />
		</form>
	</div>
';
	
	if($_PROJETO['conteudo_comentarios']){
		if($_PROJETO['conteudo_comentarios']['titulo']) $titulo = $_PROJETO['conteudo_comentarios']['titulo'];
		if($_PROJETO['conteudo_comentarios']['layout']) $layout = $_PROJETO['conteudo_comentarios']['layout'];
		if($_PROJETO['conteudo_comentarios']['layout2']) $layout2 = $_PROJETO['conteudo_comentarios']['layout2'];
		if($_PROJETO['conteudo_comentarios']['formulario']) $formulario = $_PROJETO['conteudo_comentarios']['formulario'];
		if($_PROJETO['conteudo_comentarios']['hr']) $hr = $_PROJETO['conteudo_comentarios']['hr'];
	
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_comentarios',
			'autor',
			'conteudo',
			'data',
			'pai',
		))
		,
		"comentarios",
		"WHERE id_conteudo='".$id."'"
		." AND status='A'"
		." ORDER BY data ASC"
	);
	
	$comentarios = '<!-- comentarios -->';
	
	if($resultado){
		foreach($resultado as $res){
			$cel_nome = 'comentarios';
			
			if(!$res['pai']){
				$cel_aux = $layout;
			
				$var = 'num'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res['id_comentarios']);
				$var = 'autor'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res[$var]);
				$var = 'conteudo'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res[$var]);
				$var = 'data'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res[$var]);
				$var = 'id_pai'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res['id_comentarios']);
				
				$cel_aux = modelo_var_troca($cel_aux,"#mais-comentarios#","<!-- mais-comentarios#".$res['id_comentarios']." -->");
				$comentarios = modelo_var_in($comentarios,'<!-- '.$cel_nome.' -->',$cel_aux);
			} else {
				$cel_aux = $layout2;
			
				$var = 'num'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res['id_comentarios']);
				$var = 'autor'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res[$var]);
				$var = 'conteudo'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res[$var]);
				$var = 'data'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$res[$var]);
				
				$pai = conteudo_comentarios_pai($resultado,$res['pai']);
				
				$var = 'id_pai'; $cel_aux = modelo_var_troca($cel_aux,"#".$var."#",$pai);
				
				$comentarios = modelo_var_in($comentarios,"<!-- mais-comentarios#".$pai." -->",$cel_aux);
			}
		}
		
		$comentarios = '
	'.modelo_var_troca($titulo,"#quantidade#",count($resultado)).'
	'.$hr.'
	'.$comentarios.'
	'.$formulario.'
	'.$hr.'
';
	
	} else {
		$comentarios = '
	'.$formulario.'
	'.$hr.'
';
	}
	
	return $comentarios;
}

function conteudo_jplayer(){
	return '<div id="jquery_jplayer_1" class="jp-jplayer"></div>
  <div id="jp_container_1" class="jp-audio">
    <div class="jp-type-single">
      <div class="jp-gui jp-interface">
        <ul class="jp-controls">
          <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
          <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
          <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
          <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
          <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
          <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
        </ul>
        <div class="jp-progress">
          <div class="jp-seek-bar">
            <div class="jp-play-bar"></div>
          </div>
        </div>
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
        <div class="jp-time-holder">
          <div class="jp-current-time"></div>
          <div class="jp-duration"></div>
          <ul class="jp-toggles">
            <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
            <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
          </ul>
        </div>
      </div>
      <div class="jp-title">
        <ul>
          <li>Bubble</li>
        </ul>
      </div>
      <div class="jp-no-solution">
        <span>Update Required</span>
        To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
      </div>
    </div>
  </div>';
}

function conteudo_galerias_imagens_pretty_photo($id){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_CAMINHO;
	global $_MENU_DINAMICO;
	global $_MENU_NAO_MUDAR;
	global $_OPCAO;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	
	$_MENU_NAO_MUDAR = true;
	
	$_INTERFACE['forcar_inicio'] = true;
	
	$galerias_vars = Array(
		'opcao' => 'galerias_imagens_pretty_photo',
		'ferramenta' => 'imagens dessa galeria',
		'frame_width' => false,
		'frame_margin' => '7',
		'tabela_nome' => 'imagens',
		'tabela_extra' => "WHERE status='A' AND id_galerias='".$id."'",
		'tabela_order' => "id_imagens DESC" . ($limite ? " LIMIT ".$limite : ""),
		'tabela_limit' => true,
		'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
		'tabela_id_posicao' => 0,
		'tabela_campos' => Array(
			'descricao',
			'local_mini',
			'local_media',
			'local_grande',
			'local_original',
		),
		'imagem_pequena' => 'local_mini',
		'imagem_grande' => 'local_original',
		'menu_paginas_id' => 'galerias_'.$id,
		'num_colunas' => 8,
		'link_externo' => true,
		'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias/#id/',
		'link_target' => '_self',
		'menu_pagina_embaixo' => true,
		'titulo_class' => 'fotos-galerias-titulo',
		'link_class' => 'fotos-imagens-link',
		'link_class_ajuste_margin' => 4,
		'menu_dont_show' => true, // Título da Informação
		'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		'desativar_conteiner_secundario' => true,
	);
	
	if($_PROJETO['conteudo']){
		if($_PROJETO['conteudo']['galerias_vars']){
			$array_var_aux = $_PROJETO['conteudo']['galerias_vars'];
			
			foreach($array_var_aux as $key => $var){
				$galerias_vars[$key] = $var;
			}
		}
	}
	
	$imagens = interface_layout($galerias_vars);
	
	if($_PROJETO['galerias']){
		if($_PROJETO['galerias']['layout']){
			$layout = true;
			
			$pagina = $_PROJETO['galerias']['layout'];
			
			$pagina = modelo_var_troca($pagina,"#nome#",$_PROJETO['conteudo']['galeria_imagens_titulo']);
			$pagina = modelo_var_troca($pagina,"#imagens#",$imagens);
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'] . 'projeto.html');
		$pagina = modelo_tag_val($modelo,'<!-- galerias < -->','<!-- galerias > -->');
		
		$pagina = modelo_var_troca($pagina,"#nome#",$_PROJETO['conteudo']['galeria_imagens_titulo']);
		$pagina = modelo_var_troca($pagina,"#imagens#",$imagens);
	}
	
	if($_PROJETO['galerias']){
		if($_PROJETO['galerias']['conteiner_posicao']){
			$_VARIAVEIS_JS['conteiner_posicao'] = $_PROJETO['galerias']['conteiner_posicao'];
			$_VARIAVEIS_JS['conteiner_posicao_x'] = $_PROJETO['galerias']['conteiner_posicao_x'];
			$_VARIAVEIS_JS['conteiner_posicao_y'] = $_PROJETO['galerias']['conteiner_posicao_y'];
			$_VARIAVEIS_JS['conteiner_posicao_efeito'] = $_PROJETO['galerias']['conteiner_posicao_efeito'];
			$_VARIAVEIS_JS['conteiner_posicao_tempo'] = $_PROJETO['galerias']['conteiner_posicao_tempo'];
		}
	}
	
	return $pagina.'<div class="clear"></div>';
}

function conteudo_galerias_videos_youtube_pretty_photo($id){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_CAMINHO;
	global $_MENU_DINAMICO;
	global $_MENU_NAO_MUDAR;
	global $_OPCAO;
	global $_PROJETO;
	
	$_MENU_NAO_MUDAR = true;
	
	$_INTERFACE['forcar_inicio'] = true;
	
	$params = Array(
		'opcao' => 'galerias_videos_youtube_pretty_photo',
		'ferramenta' => 'vídeos dessa galeria',
		'frame_width' => '770',
		'frame_margin' => '7',
		'tabela_nome' => 'videos',
		'tabela_extra' => "WHERE status='A' AND id_galerias_videos='".$id."'",
		'tabela_order' => "id_videos DESC" . ($limite ? " LIMIT ".$limite : ""),
		'tabela_limit' => true,
		'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
		'tabela_id_posicao' => 0,
		'tabela_campos' => Array(
			'descricao',
			'imagem_mini',
			'codigo',
		),
		'imagem_pequena' => 'imagem_mini',
		'codigo' => 'codigo',
		'menu_paginas_id' => 'galerias_videos_'.$id,
		'num_colunas' => 8,
		'link_externo' => true,
		'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-videos/#id/',
		'link_target' => '_self',
		'menu_pagina_embaixo' => true,
		'titulo_class' => 'fotos-galerias-videos-titulo',
		'link_class' => 'fotos-videos-link',
		'link_class_ajuste_margin' => 4,
		'menu_dont_show' => true, // Título da Informação
		'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		'desativar_conteiner_secundario' => true,
	);
	
	if($_PROJETO['conteudo']){
		if($_PROJETO['conteudo']['galerias_videos']){
			$galerias_videos = $_PROJETO['conteudo']['galerias_videos'];
			
			foreach($galerias_videos as $param => $val){
				$params[$param] = $val;
			}
		}
	}
	
	$videos = interface_layout($params);
	
	$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'] . 'projeto.html');
	$pagina = modelo_tag_val($modelo,'<!-- galerias_videos < -->','<!-- galerias_videos > -->');
	
	$pagina = modelo_var_troca($pagina,"#nome#",$_PROJETO['conteudo']['galeria_videos_titulo']);
	$pagina = modelo_var_troca($pagina,"#videos#",$videos);
	
	return $pagina;
}

function conteudo_raiz_url($id,$cod_pai){
	global $_SYSTEM;
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'identificador',
			'id_conteudo_pai',
		))
		,
		"conteudo",
		"WHERE id_conteudo='".$cod_pai."'"
	);
	
	if($resultado[0]['id_conteudo_pai']){
		$id = $resultado[0]['identificador'].'/'.$id;
		return conteudo_raiz_url($id,$resultado[0]['id_conteudo_pai']);
	} else {
		return '/'.$_SYSTEM['ROOT'].($resultado[0]['identificador']?$resultado[0]['identificador'].'/':'').($id?$id.'/':'');
	}
}

function conteudo_filhos($cod,$id,$cod_pai){
	global $_HTML;
	global $_PROJETO;
	global $_SYSTEM;
	
	$campos = Array(
		'titulo',
		'identificador',
	);
	
	if($_PROJETO['conteudo_filhos']){
		if($_PROJETO['conteudo_filhos']['campos'])$campos = array_merge($campos, $_PROJETO['conteudo_filhos']['campos']);
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas($campos)
		,
		"conteudo",
		"WHERE id_conteudo_pai='".$cod."'"
		." AND status='A'"
		." ORDER BY ordem ASC, id_conteudo DESC LIMIT ".$_HTML['MENU_NUM_PAGINAS']
	);
	
	if($_PROJETO['conteudo_filhos']){
		if($_PROJETO['conteudo_filhos']['layout']){
			$not_default = true;
			$layout = $_PROJETO['conteudo_filhos']['layout'];
			$barra = $_PROJETO['conteudo_filhos']['barra'];
		}
	}
	
	if($not_default){
		if($resultado){
			foreach($resultado as $res){
				$cel_aux = $layout;
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#url_conteudo#",conteudo_raiz_url($id,$cod_pai) . $res['identificador']);
				
				if($campos)
				foreach($campos as $campo){
					if($res[$campo]){
						$cel_aux = modelo_var_troca_tudo($cel_aux,"#".$campo."#",$res[$campo]);
						$cel_aux = modelo_var_troca_tudo($cel_aux,'<!-- '.$campo.' < -->','');
						$cel_aux = modelo_var_troca_tudo($cel_aux,'<!-- '.$campo.' > -->','');
					} else {
						$cel_aux = modelo_tag_in($cel_aux,'<!-- '.$campo.' < -->','<!-- '.$campo.' > -->','');
					}
				}
				
				$cont++;
				$filhos .= ($cont > 1 && $barra?$barra:'' ).$cel_aux;
			}
		}
	} else {
		if($resultado){
			foreach($resultado as $dado){
				$a = html(Array(
					'tag' => 'a',
					'val' => $dado['titulo'],
					'attr' => Array(
						'href' => conteudo_raiz_url($id,$cod_pai) . $dado['identificador'],
					)
				));
				$filhos .= html(Array(
					'tag' => 'li',
					'val' => $a,
					'attr' => Array(
						'class' => 'in_li_conteudos_relacionados',
					)
				))."\n";
			}
			
			$filhos = html(Array(
				'tag' => 'ul',
				'val' => $filhos,
				'attr' => Array(
					'class' => 'in_ul_conteudos_relacionados',
				)
			))."\n";
		}
		
		if($_PROJETO['conteudo_filhos']){
			if($_PROJETO['conteudo_filhos']['titulo'])$titulo = $_PROJETO['conteudo_filhos']['titulo'];
		}
		
		if($filhos){
			$filhos = html(Array(
				'tag' => 'h2',
				'val' => ($titulo?$titulo:"Conteúdos Relacionados:"),
				'attr' => Array()
			))."\n".$filhos;
			
			$filhos = html(Array(
				'tag' => 'div',
				'val' => $filhos,
				'attr' => Array()
			))."\n";
		}
	}
	
	return $filhos;
}

function conteudo_permisao($id,$pai = false,$nivel = 0,$conteudo_vars = false){
	global $_CONTEUDO_VARS;
	
	if(!$conteudo_vars){
		$conteudo_vars = $_CONTEUDO_VARS;
		$conteudo_vars[] = 'addthis';
		$conteudo_vars[] = 'no_robots';
		$conteudo_vars[] = 'layout_status';
		$conteudo_vars[] = 'layout';
		$conteudo_vars[] = 'no_layout';
		$conteudo_vars[] = 'galeria_todas';
		$conteudo_vars[] = 'videos_todas';
		$conteudo_vars[] = 'conteudos_relacionados';
		$conteudo_vars[] = 'conteiner_posicao';
		$conteudo_vars[] = 'conteiner_posicao_efeito';
		$conteudo_vars[] = 'conteiner_posicao_tempo';
		$conteudo_vars[] = 'comentarios';
		$conteudo_vars[] = 'cont_padrao_posicao_x';
		$conteudo_vars[] = 'cont_padrao_posicao_y';
		$conteudo_vars[] = 'glossario';
		$conteudo_vars[] = 'menu_navegacao';
		$conteudo_vars[] = 'comentarios_facebook';
		$conteudo_vars[] = 'categoria';
	}
	
	if(!$pai){
		$permisao = banco_select_name
		(
			banco_campos_virgulas($conteudo_vars)
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'".
			" AND tipo='C'"
		);
	} else {
		if($nivel == 0) $nivel = 1;		
		$permisao2 = banco_select_name
		(
			banco_campos_virgulas($conteudo_vars)
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'".
			" AND tipo='".$nivel."'"
		);
		
		if(!$permisao2){
			$permisao2 = banco_select_name
			(
				banco_campos_virgulas($conteudo_vars)
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'".
				" AND tipo='L'"
			);
		}
	}
	
	if($permisao){
		return $permisao[0];
	} else if($permisao2){
		return $permisao2[0];
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_pai',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		
		if($conteudo)
			return conteudo_permisao($conteudo[0]['id_conteudo_pai'],true,$nivel+1,$conteudo_vars);
		else
			return Array();
	}
}

function conteudo_titulo_como_menu_navegacao($id_pai){
	global $_SYSTEM;
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'caminho_raiz',
			'identificador',
			'id_conteudo_pai',
		))
		,
		"conteudo",
		"WHERE id_conteudo='".$id_pai."'"
	);
	
	if($resultado)
		$menu = conteudo_titulo_como_menu_navegacao($resultado[0]['id_conteudo_pai']) . '<a href="/'.$_SYSTEM['ROOT'].$resultado[0]['caminho_raiz'].$resultado[0]['identificador'].'">'.$resultado[0]['titulo'].'</a> / ';
	
	return $menu;
}

function conteudo_menu_navegacao($cod_pai,$link = false){
	global $_HTML;
	global $_SYSTEM;
	global $_PROJETO;
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'identificador',
			'caminho_raiz',
			'id_conteudo_pai',
			'id_conteudo',
		))
		,
		"conteudo",
		"WHERE id_conteudo='".$cod_pai."'"
	);
	
	if($resultado)
		$link = '<a href="/'.$_SYSTEM['ROOT'].$resultado[0]['caminho_raiz'].$resultado[0]['identificador'].'">'.$resultado[0]['titulo'].'</a>' . ($link ? ' / ' . $link : '');
	
	if($resultado[0]['id_conteudo_pai'] && $resultado[0]['id_conteudo_pai'] != $cod_pai){
		return conteudo_menu_navegacao($resultado[0]['id_conteudo_pai'],$link);
	} else {
		if($resultado){
			if($_PROJETO['conteudo']){
				if($_PROJETO['conteudo']['conteudo_menu_navegacao']){
					$layout = $_PROJETO['conteudo']['conteudo_menu_navegacao'];
				}
			}
			
			if(!$layout){
				$layout =  '<div id="_cms-menu_navegacao"><a href="#url#">#titulo#</a>#link#</div>';
			}
			
			$layout = modelo_var_troca_tudo($layout,"#url#",'/'.$_SYSTEM['ROOT']);
			$layout = modelo_var_troca_tudo($layout,"#titulo#",$_HTML['TITULO']);
			$layout = modelo_var_troca_tudo($layout,"#link#",($link ? ' / ' . $link : ''));
		}
		
		return $layout;
	}
}

function conteudo_mostrar($params){
	global $_SYSTEM;
	global $_HTML;
	global $_HTML_META;
	global $_HTML_DADOS;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONTEUDO_VARS;
	global $_CONTEUDO_VARS_SEM_PERMISSAO;
	global $_CAMINHO;
	global $_ALERTA;
	global $_MENU_ID;
	global $_OPCAO;
	global $_INTERFACE;
	global $_MENU_DINAMICO;
	global $_AUDIO_PATH;
	global $_VARIAVEIS_JS;
	global $_CONTEUDO_ID_AUX;
	global $_PROJETO;
	
	$forcar = $params['forcar'];
	
	$parametros['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'index.html';
	
	if(!$params['erro_nao_encontrado'])$params['erro_nao_encontrado'] = "Resultado não encontrado";
	
	$conteudo_vars = array_merge($_CONTEUDO_VARS,$_CONTEUDO_VARS_SEM_PERMISSAO);
	
	if($_CAMINHO[count($_CAMINHO)-1])$cod_aux = $_CAMINHO[count($_CAMINHO)-1]; else $cod_aux = $_CAMINHO[count($_CAMINHO)-2];
	if(is_numeric($cod_aux))$cod = $cod_aux; else $id = $cod_aux;
	
	if($params['forcar_id']) $id = $params['forcar_id'];
	if($params['forcar_cod']) $cod = $params['forcar_cod'];
	
	if(!$_CONEXAO_BANCO)$connect = true;
	if($connect)banco_conectar();
	
	$conteudo_vars_2 = $conteudo_vars;
	array_push($conteudo_vars_2,'hits');
	array_push($conteudo_vars_2,'id_conteudo_pai');
	array_push($conteudo_vars_2,'description');
	array_push($conteudo_vars_2,'tipo');
	array_push($conteudo_vars_2,'titulo_img_title');
	array_push($conteudo_vars_2,'titulo_img_alt');
	array_push($conteudo_vars_2,'imagem_pequena_title');
	array_push($conteudo_vars_2,'imagem_pequena_alt');
	array_push($conteudo_vars_2,'imagem_grande_title');
	array_push($conteudo_vars_2,'imagem_grande_alt');
	array_push($conteudo_vars,'description');
	array_push($conteudo_vars,'galeria_todas');
	array_push($conteudo_vars,'videos_todas');
	array_push($conteudo_vars,'conteudos_relacionados');
	array_push($conteudo_vars,'comentarios');
	array_push($conteudo_vars,'menu_navegacao');
	array_push($conteudo_vars,'comentarios_facebook');
	array_push($conteudo_vars,'categoria');
	
	$caminho_raiz = $_REQUEST[caminho];
	$caminho_raiz = preg_replace('/'.$id.'/i', '', $caminho_raiz);
	$caminho_raiz = preg_replace('/\/\//i', '/', $caminho_raiz);
	if($caminho_raiz == '/')$caminho_raiz = '';
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas($conteudo_vars_2)
		,
		"conteudo",
		($_CONTEUDO_ID_AUX?"WHERE identificador_auxiliar='".$_CONTEUDO_ID_AUX."'":($id ? "WHERE identificador='".$id."'" . ($caminho_raiz?" AND caminho_raiz='".$caminho_raiz."'":"") : "WHERE id_conteudo='".$cod."'"))
		." AND status='A'"
	);
	
	if(!$id)$id = $resultado[0]['identificador'];
	if(!$cod)$cod = $resultado[0]['id_conteudo'];
	
	if($_PROJETO['conteudo']){
		if($_PROJETO['conteudo']['permissao'][$id]){
			$conteudo_perfil = true;
			if($_PROJETO['conteudo']['permissao'][$id] == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
				$conteudo_perfil = false;
			}
		}
	}
	
	if($conteudo_perfil){
		$pagina = login();
	} else {
		if($resultado){
			banco_update
			(
				"hits='".($resultado[0]['hits']?$resultado[0]['hits']+1:1)."'",
				"conteudo",
				"WHERE identificador='".$id."'"
			);
			
			$conteudo_permissao = conteudo_permisao($cod);
			
			if($conteudo_permissao['no_robots'])$_HTML_DADOS['noindex'] = true;
			if($conteudo_permissao['no_layout'])$_HTML_DADOS['no_layout'] = true;
			
			if(!$conteudo_permissao['layout_status']){
				$modelo = paginaModelo($parametros['layout_url']);
				$pagina = paginaTagValor($modelo,'<!-- conteudo < -->','<!-- conteudo > -->');
			} else {
				$pagina = $conteudo_permissao['layout'];
			}
			$variavel = $params['variavel'];
			
			if($conteudo_permissao['conteiner_posicao'] && $conteudo_permissao['cont_padrao_posicao_x']){
				$posicao['conteiner_posicao_x'] = $conteudo_permissao['cont_padrao_posicao_x'];
			}
			if($conteudo_permissao['conteiner_posicao'] && $conteudo_permissao['cont_padrao_posicao_y']){
				$posicao['conteiner_posicao_y'] = $conteudo_permissao['cont_padrao_posicao_y'];
			}
			
			foreach($conteudo_vars as $var){
				$cel_nome = $var; 
				
				switch($var){
					case 'conteiner_posicao_x':
						if($conteudo_permissao['conteiner_posicao'] && $resultado[0][$cel_nome]){
							$posicao['conteiner_posicao_x'] = $resultado[0][$cel_nome];
						}
					break;
					case 'conteiner_posicao_y':
						if($conteudo_permissao['conteiner_posicao'] && $resultado[0][$cel_nome]){
							$posicao['conteiner_posicao_y'] = $resultado[0][$cel_nome];
						}
					break;
					case 'titulo':
					case 'description':
					case 'keywords':
						if($var == 'titulo'){
							$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
							$_HTML_DADOS[$var] = $titulo . $resultado[0][$cel_nome];
						} else {
							$_HTML_DADOS[$var] = ($resultado[0][$cel_nome]?$resultado[0][$cel_nome]:' ');
						}
					break;
				}
				
				$dado = '';
				
				if(!$conteudo_permissao[$cel_nome]){ 
					if($var == 'sub_titulo' && $params['forcar_h1_to_h2']){
						$dado = $sub_titulo;
						$pagina = modelo_var_troca($pagina,"#".$cel_nome."#",$dado);
					} else if($var == 'titulo' && $params['forcar_sub_to_tit']){
						$dado = $resultado[0]['sub_titulo'];
						$pagina = modelo_var_troca($pagina,"#".$cel_nome."#",$dado);
						$pagina = modelo_tag_in($pagina,'<!-- sub_titulo < -->','<!-- sub_titulo > -->','');
					} else {
						$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
				} else {
					$dado = $resultado[0][$cel_nome];
					$variaveis[$var] = $dado;
					
					if($variavel[$var]){
						$modificacoes = $variavel[$var];
						
						if($modificacoes['link']){
							$link = $modificacoes['link'];
							$dado = html(Array(
								'tag' => 'a',
								'val' => $dado,
								'attr' => Array(
									'href' => $link['href'],
									'target' => $link['target'],
									'class' => $link['class'],
									'title' => $link['title'],
								)
							));
						}
						if($modificacoes['data'])$dado = data_hora_from_datetime_to_text($dado);
					} else {
						if($forcar[$var]){
							$dado = $forcar[$var];
						}
						
						switch($var){
							case 'menu_navegacao': $dado = conteudo_menu_navegacao($resultado[0]['id_conteudo_pai']); break;
							case 'data': if($dado)$dado = data_hora_from_datetime_to_text($dado); break;
							case 'titulo_img':
							case 'imagem_pequena':
							case 'imagem_grande':
								if($dado){
									$image_info = imagem_info($dado);
									
									$width = $image_info[0];
									$height = $image_info[1];
									$dado = html(Array(
										'tag' => 'img',
										'val' => '',
										'attr' => Array(
											'src' => $_CAMINHO_RELATIVO_RAIZ.$dado,
											'title' => $resultado[0][$var.'_title'],
											'alt' => $resultado[0][$var.'_alt'],
											'width' => $width,
											'height' => $height,
										)
									));
								}
							break;
							case 'texto':
							case 'texto2':
								if($dado){
									if($conteudo_permissao['glossario']){
										$dado = conteudo_glossario($dado);
									}
								}
							break;
							case 'titulo':
								if($_PROJETO['conteudo']['titulo_como_menu_navegacao']){
									$dado = conteudo_titulo_como_menu_navegacao($resultado[0]['id_conteudo_pai']) . $dado;
								} else {
									if($params['retranca_titulo']){
										$titulo_aux = $dado;
										$dado = $params['retranca_titulo'];
									} else if($params['forcar_h1']){
										$sub_titulo = $dado;
										$dado = $params['forcar_h1'];
									}
								}
							break;
							case 'sub_titulo':
								if($params['forcar_h1_to_h2']){
									$dado = $sub_titulo;
								}
							break;
							case 'author':
								if($dado){
									$_HTML_META['author'] = $dado;
								}
							break;
							case 'musica':
								if($dado){
									$_AUDIO_PATH = $dado;
									$dado = conteudo_jplayer();
								}
							break;
							case 'galeria':
								if($dado)$dado = conteudo_galerias_imagens_pretty_photo($dado);
							break;
							case 'galeria_grupo':
								if($dado){
									$_HTML['variaveis']['menu_dinamico_inicial'] = 
									$_MENU_DINAMICO = $id;
									if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
									
									$galeria_grupo_vars = Array(
										'opcao' => 'galerias',
										'ferramenta' => 'galerias',
										'frame_width' => false,
										'frame_margin' => '12',
										'tabela_nome' => 'galerias',
										'tabela_extra' => "WHERE status='A' AND grupo='".$dado."'",
										'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
										'tabela_limit' => false,
										'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
										'tabela_id_posicao' => 0,
										'tabela_campos' => Array(
											'id_galerias',
											'nome',
											'identificador',
										),
										'menu_paginas_id' => 'menu_galeria_grupo-imagens',
										'num_colunas' => 3,
										'link_externo' => true,
										'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-imagens/#id/',
										'link_target' => '_self',
										'menu_pagina_embaixo' => true,
										'imagem_tamanho' => 'local_media',
										'titulo_class' => 'fotos-galerias-titulo',
										'link_class' => 'fotos-galerias-link',
										'link_class_ajuste_margin' => 10,
										'menu_dont_show' => true, // Título da Informação
										'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
										'desativar_conteiner_secundario' => true,
									);
									
									if($_PROJETO['conteudo']){
										if($_PROJETO['conteudo']['galeria_grupo_vars']){
											$galeria_grupo_vars_2 = $_PROJETO['conteudo']['galeria_grupo_vars'];
											
											foreach($galeria_grupo_vars_2 as $key => $var){
												$galeria_grupo_vars[$key] = $var;
											}
										}
									}
									
									$dado = interface_layout($galeria_grupo_vars);
								}
							break;
							case 'galeria_todas':
								$_HTML['variaveis']['menu_dinamico_inicial'] = 
								$_MENU_DINAMICO = $id;
								if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
								
								$galeria_todas_vars = Array(
									'opcao' => 'galerias',
									'ferramenta' => 'galerias',
									'frame_width' => false,
									'frame_margin' => '12',
									'tabela_nome' => 'galerias',
									'tabela_extra' => "WHERE status='A'",
									'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
									'tabela_limit' => false,
									'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
									'tabela_id_posicao' => 0,
									'tabela_campos' => Array(
										'id_galerias',
										'nome',
										'identificador',
									),
									'menu_paginas_id' => 'menu_galerias-imagens',
									'num_colunas' => 3,
									'link_externo' => true,
									'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-imagens/#id/',
									'link_target' => '_self',
									'menu_pagina_embaixo' => true,
									'imagem_tamanho' => 'local_media',
									'titulo_class' => 'fotos-galerias-titulo',
									'link_class' => 'fotos-galerias-link',
									'link_class_ajuste_margin' => 10,
									'menu_dont_show' => true, // Título da Informação
									'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
									'desativar_conteiner_secundario' => true,
								);
								
								if($_PROJETO['conteudo']){
									if($_PROJETO['conteudo']['galeria_todas_vars']){
										$galeria_todas_vars_2 = $_PROJETO['conteudo']['galeria_todas_vars'];
										
										foreach($galeria_todas_vars_2 as $key => $var){
											$galeria_todas_vars[$key] = $var;
										}
									}
								}
								
								$dado = interface_layout($galeria_todas_vars);
							break;
							case 'videos':
								if($dado)$dado = conteudo_galerias_videos_youtube_pretty_photo($dado);
							break;
							case 'videos_grupo':
								if($dado){
									$_HTML['variaveis']['menu_dinamico_inicial'] = 
									$_MENU_DINAMICO = $id;
									if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
									
									$videos_grupo_vars = Array(
										'opcao' => 'galerias',
										'ferramenta' => 'galerias',
										'frame_width' => false,
										'frame_margin' => '12',
										'tabela_nome' => 'galerias_videos',
										'tabela_extra' => "WHERE status='A' AND grupo='".$dado."'",
										'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
										'tabela_limit' => false,
										'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
										'tabela_id_posicao' => 0,
										'tabela_campos' => Array(
											'id_galerias_videos',
											'nome',
											'identificador',
										),
										'menu_paginas_id' => 'menu_galeria_grupo-videos',
										'num_colunas' => 3,
										'link_externo' => true,
										'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-videos/#id/',
										'link_target' => '_self',
										'menu_pagina_embaixo' => true,
										'imagem_tamanho' => 'imagem_media',
										'titulo_class' => 'fotos-galerias-videos-titulo',
										'link_class' => 'fotos-videos-link',
										'link_class_ajuste_margin' => 10,
										'menu_dont_show' => true, // Título da Informação
										'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
										'desativar_conteiner_secundario' => true,
										'galeria_imagens_banco_nome' => 'videos',
										'galeria_imagens_banco_id' => 'id_galerias_videos',
									);
									
									if($_PROJETO['conteudo']){
										if($_PROJETO['conteudo']['videos_grupo_vars']){
											$videos_grupo_vars_2 = $_PROJETO['conteudo']['videos_grupo_vars'];
											
											foreach($videos_grupo_vars_2 as $key => $var){
												$videos_grupo_vars[$key] = $var;
											}
										}
									}
									
									$dado = interface_layout($videos_grupo_vars);
								}
							break;
							case 'videos_todas':
								$_HTML['variaveis']['menu_dinamico_inicial'] = 
								$_MENU_DINAMICO = $id;
								if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
								
								$videos_todas_vars = Array(
									'opcao' => 'galerias',
									'ferramenta' => 'galerias',
									'frame_width' => false,
									'frame_margin' => '12',
									'tabela_nome' => 'galerias_videos',
									'tabela_extra' => "WHERE status='A'",
									'tabela_order' => "data DESC" . ($limite ? " LIMIT ".$limite : ""),
									'tabela_limit' => false,
									'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
									'tabela_id_posicao' => 0,
									'tabela_campos' => Array(
										'id_galerias_videos',
										'nome',
										'identificador',
									),
									'menu_paginas_id' => 'menu_galerias-videos',
									'num_colunas' => 3,
									'link_externo' => true,
									'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-videos/#id/',
									'link_target' => '_self',
									'menu_pagina_embaixo' => true,
									'imagem_tamanho' => 'imagem_media',
									'titulo_class' => 'fotos-galerias-videos-titulo',
									'link_class' => 'fotos-videos-link',
									'link_class_ajuste_margin' => 10,
									'menu_dont_show' => true, // Título da Informação
									'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
									'desativar_conteiner_secundario' => true,
									'galeria_imagens_banco_nome' => 'videos',
									'galeria_imagens_banco_id' => 'id_galerias_videos',
								);
								
								if($_PROJETO['conteudo']){
									if($_PROJETO['conteudo']['videos_todas_vars']){
										$videos_todas_vars_2 = $_PROJETO['conteudo']['videos_todas_vars'];
										
										foreach($videos_todas_vars_2 as $key => $var){
											$videos_todas_vars[$key] = $var;
										}
									}
								}
								
								$dado = interface_layout($videos_todas_vars);
							break;
							case 'conteudos_relacionados':
								if($resultado[0]['tipo'] == 'L'){
									$dado = conteudo_filhos($cod,$id,$resultado[0]['id_conteudo_pai']);
								}
							break;
							case 'comentarios':
								$dado = conteudo_comentarios($resultado[0]['id_conteudo']);
							break;
							case 'servico':
								if($dado)$dado = conteudo_servico($dado,$id);
							break;
							case 'produto':
								if($dado)$dado = conteudo_produto($dado,$id);
							break;
							case 'comentarios_facebook':
								$caminho = $_REQUEST[caminho];
								$dominio = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
								
								if($caminho[strlen($caminho)-1] != '/' && $caminho){	
									$caminho .= '/';
								}
								
								if($_PROJETO['conteudo'])
								if($_PROJETO['conteudo']['facebook_posts']){
									$facebook_posts = $_PROJETO['conteudo']['facebook_posts'];
								}
								
								if($_PROJETO['conteudo'])
								if($_PROJETO['conteudo']['facebook_width']){
									$facebook_width = $_PROJETO['conteudo']['facebook_width'];
								}
								
								if($_PROJETO['conteudo'])
								if($_PROJETO['conteudo']['facebook_colorscheme']){
									$facebook_colorscheme = $_PROJETO['conteudo']['facebook_colorscheme'];
								}
								
								$dado = '<div id="facebook-commentarios" class="fb-comments" data-href="'.'http://'.$dominio.'/'.$_SYSTEM['ROOT'].$caminho.'" data-numposts="'.($facebook_posts?$facebook_posts:5).'" data-width="'.($facebook_width?$facebook_width:500).'" data-colorscheme="'.($facebook_colorscheme?$facebook_colorscheme:'light').'"></div>';
							break;
							case 'categoria':
								$params['info_abaixo'] .= conteudo_categoria_produtos($resultado[0]['id_conteudo']);
							break;
							
						}
					}
				}
				
				$pagina = modelo_var_troca_tudo($pagina,"#".$cel_nome."#",$dado);
				$pagina = modelo_var_troca_tudo($pagina,'<!-- '.$cel_nome.' -->',$dado);
				$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' < -->','');
				$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' > -->','');
				$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
			}
			
			if($_PROJETO['conteudo']){
				if($_PROJETO['conteudo']['menu_navegacao_forcar']){
					$params['info_acima'] .= conteudo_menu_navegacao($resultado[0]['id_conteudo_pai']);
				}
			}
			
			if($posicao['conteiner_posicao_x'] && $posicao['conteiner_posicao_y']){
				$_VARIAVEIS_JS['conteiner_posicao'] = $conteudo_permissao['conteiner_posicao'];
				$_VARIAVEIS_JS['conteiner_posicao_x'] = $posicao['conteiner_posicao_x'];
				$_VARIAVEIS_JS['conteiner_posicao_y'] = $posicao['conteiner_posicao_y'];
				$_VARIAVEIS_JS['conteiner_posicao_efeito'] = $conteudo_permissao['conteiner_posicao_efeito'];
				$_VARIAVEIS_JS['conteiner_posicao_tempo'] = $conteudo_permissao['conteiner_posicao_tempo'];
			}
			
			if($params['retranca_titulo']){
				$pagina = modelo_var_troca_tudo($pagina,$params['retranca_cel_titulo_aux'],$titulo_aux);
			}
			
			if($conteudo_permissao['addthis']){
				if(preg_match('/#addthis#/i', $pagina) > 0){
					$cel_nome = 'addthis';
					$pagina = modelo_var_troca_tudo($pagina,"#".$cel_nome."#",$_SYSTEM['ADD-THIS']);
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' < -->','');
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' > -->','');
				} else if($params['modulo_noticias']){
					$params['info_abaixo'] = $_SYSTEM['ADD-THIS'] . $params['info_abaixo'];
				} else {
					$params['info_abaixo'] .= $_SYSTEM['ADD-THIS'];
				}
				$_HTML['ADD-THIS'] = true;
			} else {
				if(preg_match('/#addthis#/i', $pagina) > 0){
					$cel_nome = 'addthis';
					$pagina = modelo_var_troca_tudo($pagina,"#".$cel_nome."#",'');
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' < -->','');
					$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' > -->','');
				}
			}
			
			$pagina = modelo_var_troca($pagina,"#info_acima#",$params['info_acima']);
			$pagina = modelo_var_troca($pagina,"#info_abaixo#",$params['info_abaixo']);
		} else {
			$erro = true;
		}
	}
	
	$modulos_tags = $params['modulos_tags'];
	
	if($modulos_tags)
	foreach($modulos_tags as $tag){
		if(preg_match('/'.$tag.'/i', $pagina) > 0){
			$modulo = modulos(Array(
				'modulo_tag' => $tag,
			));
			
			$pagina = preg_replace('/'.$tag.'/i', $modulo, $pagina);
		}
	}
	
	if($connect)banco_fechar_conexao();
	
	if($params['layout']){
		$pagina = modelo_var_troca($params['layout'],"!#CONTEUDO#!",$pagina);
	}
	
	return Array(
		'variaveis' => $variaveis,
		'pagina' => $pagina,
		'erro' => $erro,
	);
}

?>