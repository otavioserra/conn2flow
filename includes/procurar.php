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

$_VERSAO_MODULO_INCLUDE				=	'2.1.0';

function procurar_conteudo_permisao($id,$pai = false){
	$conteudo_permisao[] = 'no_search';
	
	if(!$pai){
		$permisao = banco_select_name
		(
			banco_campos_virgulas($conteudo_permisao)
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'".
			" AND tipo='C'"
		);
	} else {
		$permisao2 = banco_select_name
		(
			banco_campos_virgulas($conteudo_permisao)
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'".
			" AND tipo='L'"
		);
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
			return procurar_conteudo_permisao($conteudo[0]['id_conteudo_pai'],true);
		else
			return Array();
	}
}

function procurar_menu_paginas($parametros){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_HTML;
	global $_DADOS;
	global $_MENU_NUM;
	global $_MENU_COMPUTADO;
	global $_MENU_PAGINA;
	global $_MENU_NUM_PAGINAS;
	global $_ALERTA;
	global $_PROCURAR_SQL;
	global $_PROCURAR_CAMPOS;
	global $_OPCAO;
	global $_CAMINHO;
	global $_PESQUISA_BROWSER;
	global $_LAYOUT_BASICO;
	global $_IDENTIFICADOR;
	global $_HTML_DADOS;
	
	$parametros['layout_tag1'] = '<!-- menu_paginas < -->';
	$parametros['layout_tag2'] = '<!-- menu_paginas > -->';
	
	$id = $parametros['menu_paginas_id'];
	$reiniciar = $parametros['menu_paginas_reiniciar'];
	$forcar_inicio = $parametros['forcar_inicio'];
	$tabela_campos = $parametros['tabela_campos'];
	$pesquisa = $parametros['pesquisa'];
	
	$_MENU_NUM++;
	
	$tabela_campos[] = 'id_conteudo';
	$tabela_campos[] = 'identificador';
	$tabela_campos[] = 'titulo';
	$tabela_campos[] = 'sub_titulo';
	$tabela_campos[] = 'texto';
	$tabela_campos[] = 'imagem_pequena';
	$tabela_campos[] = 'imagem_grande';
	$tabela_campos[] = 'data';
	$tabela_campos[] = 'caminho_raiz';
	
	$galerias_campos[] = 'galerias.id_galerias';
	$galerias_campos[] = 'galerias.identificador';
	$galerias_campos[] = 'galerias.nome';
	$galerias_campos[] = 'galerias.nome';
	$galerias_campos[] = 'galerias.descricao';
	$galerias_campos[] = 'imagens.local_mini';
	$galerias_campos[] = 'imagens.local_original';
	$galerias_campos[] = 'galerias.data';
	$galerias_campos[] = 'galerias.data';
	
	$galerias_videos_campos[] = 'galerias_videos.id_galerias_videos';
	$galerias_videos_campos[] = 'galerias_videos.identificador';
	$galerias_videos_campos[] = 'galerias_videos.nome';
	$galerias_videos_campos[] = 'galerias_videos.nome';
	$galerias_videos_campos[] = 'galerias_videos.descricao';
	$galerias_videos_campos[] = 'videos.imagem_mini';
	$galerias_videos_campos[] = 'videos.imagem_original';
	$galerias_videos_campos[] = 'galerias_videos.data';
	$galerias_videos_campos[] = 'galerias_videos.data';
	
	$_PROCURAR_CAMPOS['tabela_campos'] = $tabela_campos;
	$_PROCURAR_CAMPOS['galerias_campos'] = $galerias_campos;
	$_PROCURAR_CAMPOS['galerias_videos_campos'] = $galerias_videos_campos;
	
	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();
	if(!$parametros['tabela_nao_connect']){
		if($_REQUEST['pesquisa']){
			$pesquisa = $_REQUEST['pesquisa'];
			$_SESSION[$_SYSTEM['ID'].'pesquisa_query'] = $_REQUEST['pesquisa'];
		} else {
			if($_LAYOUT_BASICO){
				$pesquisa = $_SESSION[$_SYSTEM['ID'].'pesquisa_query'];
			}
		}
		
		if($_OPCAO == 'procurar')$forcar_inicio = true;
		
		if(!$pesquisa){
			$pesquisa = utf8_decode($_CAMINHO[count($_CAMINHO)-1]);
			$forcar_inicio = true;
			
			$_PESQUISA_BROWSER = true;
		}
		
		$_HTML_DADOS['titulo'] = $_HTML['TITULO'].$_HTML['TITULO_SEPARADOR'].'Procurar'.$_HTML['TITULO_SEPARADOR'].$pesquisa;
		$_IDENTIFICADOR = criar_identificador($pesquisa);
		
		$pesquisa_partes = explode(' ',$pesquisa);
		
		if(count($pesquisa_partes) > 1){
			$pesquisa_conteudo = " WHERE ";
			$pesquisa_galerias = " WHERE ";
			$pesquisa_galerias_videos = " WHERE ";
			foreach($pesquisa_partes as $pesquisa_parte){
				$count++;
				$pesquisa_conteudo .= 
				"(UCASE(titulo) LIKE UCASE('%".$pesquisa_parte."%')"
				." OR UCASE(sub_titulo) LIKE UCASE('%".$pesquisa_parte."%')"
				." OR UCASE(texto) LIKE UCASE('%".$pesquisa_parte."%')"
				." OR UCASE(keywords) LIKE UCASE('%".$pesquisa_parte."%')"
				.")";
				
				$pesquisa_galerias .= 
				"(UCASE(galerias.descricao) LIKE UCASE('%".$pesquisa_parte."%')"
				." OR (UCASE(imagens.descricao) LIKE UCASE('%".$pesquisa_parte."%') AND imagens.status='A' AND imagens.id_galerias=galerias.id_galerias)"
				.")";
				
				$pesquisa_galerias_videos .= 
				"(UCASE(galerias_videos.descricao) LIKE UCASE('%".$pesquisa_parte."%')"
				." OR (UCASE(videos.descricao) LIKE UCASE('%".$pesquisa_parte."%') AND videos.status='A' AND videos.id_galerias_videos=galerias_videos.id_galerias_videos)"
				.")";
				
				if($count < count($pesquisa_partes)){
					$pesquisa_conteudo .= " AND ";
					$pesquisa_galerias .= " AND ";
					$pesquisa_galerias_videos .= " AND ";
				}
			}
		}
		
		$_PROCURAR_SQL =
			"(SELECT 'conteudo' AS tabela,".banco_campos_virgulas($tabela_campos)." FROM conteudo"
			.(count($pesquisa_partes) > 1 ?
				$pesquisa_conteudo
			:
				" WHERE (UCASE(titulo) LIKE UCASE('%".$pesquisa."%')"
				." OR UCASE(sub_titulo) LIKE UCASE('%".$pesquisa."%')"
				." OR UCASE(texto) LIKE UCASE('%".$pesquisa."%')"
				." OR UCASE(keywords) LIKE UCASE('%".$pesquisa."%')"
				.")"
			)
			." AND status='A')"
			." UNION "
			."(SELECT 'galerias' AS tabela,".banco_campos_virgulas($galerias_campos)." FROM galerias,imagens"
			.(count($pesquisa_partes) > 1 ?
				$pesquisa_galerias
			:
				" WHERE (UCASE(galerias.descricao) LIKE UCASE('%".$pesquisa."%')"
				." OR (UCASE(imagens.descricao) LIKE UCASE('%".$pesquisa."%') AND imagens.status='A' AND imagens.id_galerias=galerias.id_galerias)"
				.")"
			)
			." AND galerias.status='A' GROUP BY galerias.id_galerias)"
			." UNION "
			."(SELECT 'galerias_videos' AS tabela,".banco_campos_virgulas($galerias_videos_campos)." FROM galerias_videos,videos"
			.(count($pesquisa_partes) > 1 ?
				$pesquisa_galerias_videos
			:
				" WHERE (UCASE(galerias_videos.descricao) LIKE UCASE('%".$pesquisa."%')"
				." OR (UCASE(videos.descricao) LIKE UCASE('%".$pesquisa."%') AND videos.status='A' AND videos.id_galerias_videos=galerias_videos.id_galerias_videos)"
				.")"
			)
			." AND galerias_videos.status='A' GROUP BY galerias_videos.id_galerias_videos)"
			." ORDER BY data DESC"
		;

		$res = banco_sql_names(
			$_PROCURAR_SQL
			,
			banco_campos_virgulas(array_merge($tabela_campos,$galerias_campos,$galerias_videos_campos))
		);
	}
	if($connect)banco_fechar_conexao();
	
	if($res){
		$_DADOS = true;
		
		if($forcar_inicio){
			$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = false;
			$_SESSION[$_SYSTEM['ID'].$id."dados_num"] = false;
			$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = false;
		}
	
		if(
			!$_SESSION[$_SYSTEM['ID'].$id."dados_num"] || 
			$_SESSION[$_SYSTEM['ID'].$id."dados_num"] != count($res)
		){
			$numDados = $_SESSION[$_SYSTEM['ID'].$id."dados_num"] = count($res);
			$reiniciar = true;
		} else {
			$numDados = $_SESSION[$_SYSTEM['ID'].$id."dados_num"];
		}
		
		if($_SESSION[$_SYSTEM['ID'].$id."pagina_menu_num_paginas"] != $_HTML['MENU_NUM_PAGINAS'])
			$reiniciar = true;
		
		if($reiniciar){
			if($numDados % $_HTML['MENU_NUM_PAGINAS'] != 0)
				$nPaginas = floor($numDados / $_HTML['MENU_NUM_PAGINAS']) + 1;
			else
				$nPaginas = floor($numDados / $_HTML['MENU_NUM_PAGINAS']);
			
			if(!$_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
				$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = $nPaginas;
				$_SESSION[$_SYSTEM['ID'].$id."pagina"] = 1;
				$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
				$_SESSION[$_SYSTEM['ID'].$id."pagina_menu_num_paginas"] = $_HTML['MENU_NUM_PAGINAS'];
			} else if($_SESSION[$_SYSTEM['ID'].$id."nPaginas"] != $nPaginas){
				$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = $nPaginas;
				if($_SESSION[$_SYSTEM['ID'].$id."pagina"] > $_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
					$_SESSION[$_SYSTEM['ID'].$id."pagina"] = $nPaginas;
				}
				if($_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] >= $_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = $nPaginas-1;
				}
			}
			
			$_MENU_NUM_PAGINAS = $nPaginas;
		}
		
		if($_SESSION[$_SYSTEM['ID'].$id."nPaginas"] > 1){
			if(!$_MENU_PAGINA)
				$_MENU_PAGINA = $_SESSION[$_SYSTEM['ID'].$id."pagina"];
			
			$url			=	$_SERVER["PHP_SELF"];
			$pagina			=	$_MENU_PAGINA;
			$nPaginas		=	$_SESSION[$_SYSTEM['ID'].$id."nPaginas"];
			
			switch($_REQUEST[opcao_menu]){
				case 'comeco':		$pagina = 1;break;
				case 'anterior':	$pagina--;break;
				case 'proximo':		$pagina++;break;
				case 'ultimo':		$pagina = $nPaginas;break;
				case 'paginas':		$pagina = (int)($_REQUEST[paginas]);break;
			}
			
			if($pagina < 1)
				$pagina = 1;
			
			if($pagina > $_SESSION[$_SYSTEM['ID'].$id."nPaginas"])
				$pagina = $_SESSION[$_SYSTEM['ID'].$id."nPaginas"];
			
			if($_REQUEST[opcao_menu] && !$_MENU_COMPUTADO){
				if($pagina == 1)
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
				else
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = ($pagina-1)*$_HTML['MENU_NUM_PAGINAS'];
			}
			
			if(!$_MENU_COMPUTADO)
				$_SESSION[$_SYSTEM['ID'].$id."pagina"]	= $pagina;
			
			if(!$parametros['menu_dont_show']){
				$modelo = paginaModelo($parametros['layout_url']);
				$modelo = paginaTagValor($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);
				$options = paginaTagValor($modelo,'<!-- options < -->','<!-- options > -->');
				$modelo = paginaTrocaTag($modelo,'<!-- options < -->','<!-- options > -->','<!-- options -->');
				
				$modelo = paginaTrocaVarValor($modelo,'#num_menu',$_MENU_NUM);
				$modelo = paginaTrocaVarValor($modelo,'#menu_id','menu_form'.$_MENU_NUM);
				$modelo = paginaTrocaVarValor($modelo,'#menu_nome','menu_form'.$_MENU_NUM);
				$modelo = paginaTrocaVarValor($modelo,'#menu_opcao',$id);
				$modelo = paginaTrocaVarValor($modelo,'#url',$url);
				
				if($pagina == 1){
					$modelo = paginaTrocaTag($modelo,'<!-- comeco < -->','<!-- comeco > -->','&nbsp;');
					$modelo = paginaTrocaTag($modelo,'<!-- anterior < -->','<!-- anterior > -->','&nbsp;');
				} else if($pagina == $nPaginas){
					$modelo = paginaTrocaTag($modelo,'<!-- proximo < -->','<!-- proximo > -->','&nbsp;');
					$modelo = paginaTrocaTag($modelo,'<!-- ultimo < -->','<!-- ultimo > -->','&nbsp;');
				}
				
				for($i=1;$i<=$nPaginas;$i++){
					$options_aux = $options;
					
					if($pagina == $i)
						$checked = ' selected="selected"';
					else
						$checked = NULL;
					
					$options_aux = paginaTrocaVarValor($options_aux,'#num_pagina_valor',($i));
					$options_aux = paginaTrocaVarValor($options_aux,'#checked',$checked);
					$options_aux = paginaTrocaVarValor($options_aux,'#num_pagina',($i));
					
					$modelo = paginaInserirValor($modelo,'<!-- options -->',$options_aux);
				}
				
				$menu = $modelo;
			}
			
			$_MENU_COMPUTADO = true;
		}
	} else {
		$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
		$_SESSION[$_SYSTEM['ID'].$id."pagina"] = 1;
		$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = 1;
	}
	
	return $menu;
}

function procurar_lista($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_LAYOUT_BASICO;
	global $_MODULOS_TAGS;
	global $_ALERTA;
	global $_MENU_PAGINAS_INICIAL;
	global $_VARIAVEIS_JS;
	global $_IDENTIFICADOR;
	global $_PESQUISA_BROWSER;
	global $_PROCURAR_SQL;
	global $_PROCURAR_CAMPOS;
	
	if(!$_AJAX_PAGE)$_MENU_PAGINAS_INICIAL = 1;
	
	$parametros['layout_tag1'] = '<!-- lista < -->';
	$parametros['layout_tag2'] = '<!-- lista > -->';
	$parametros['result_box_1'] = '<!-- result_box < -->';
	$parametros['result_box_2'] = '<!-- result_box > -->';
	$parametros['result_cel_1'] = '<!-- result_cel < -->';
	$parametros['result_cel_2'] = '<!-- result_cel > -->';
	
	if(!$parametros['tabela_width'])				$parametros['tabela_width'] = '100%';
	if(!$parametros['class_extra'])					$parametros['class_extra'] = 'col_margin';
	if(!$parametros['result_width'])				$parametros['result_width'] = '200';
	if(!$parametros['num_cols'])					$parametros['num_cols'] = 4;
	
	$num_cols = $parametros['num_cols'];
	
	$_VARIAVEIS_JS['procurar_pesquisa'] = urlencode($_REQUEST['pesquisa']);
	
	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);
	
	$modelo = modelo_var_troca($modelo,"#procurar_titulo",$parametros['procurar_titulo']);
	$modelo = modelo_var_troca($modelo,"#procurar_sub_titulo",$parametros['procurar_sub_titulo']);
	$modelo = modelo_var_troca($modelo,"#tabela_width",$parametros['tabela_width']);
	$modelo = modelo_var_troca($modelo,"#result_width",$parametros['result_width']);
	
	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();
	
	if($_DADOS){
		$result_cel = modelo_tag_val($modelo,$parametros['result_cel_1'],$parametros['result_cel_2']);
		$modelo = modelo_tag_in($modelo,$parametros['result_cel_1'],$parametros['result_cel_2'],'<!-- result_cel -->');
		$result_box = modelo_tag_val($modelo,$parametros['result_box_1'],$parametros['result_box_2']);
		$modelo = modelo_tag_in($modelo,$parametros['result_box_1'],$parametros['result_box_2'],'<!-- result_box -->');
		
		if($_LAYOUT_BASICO){
			$modelo = '<!-- result_box -->';
		}
		
		$tabela = banco_sql_names(
			$_PROCURAR_SQL
			." LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS']
			,
			"tabela,".banco_campos_virgulas(array_merge($_PROCURAR_CAMPOS['tabela_campos'],$_PROCURAR_CAMPOS['galerias_campos'],$_PROCURAR_CAMPOS['galerias_videos_campos']))
		);
		
		$tamanho_texto['titulo'] = 100;
		$tamanho_texto['sub_titulo'] = 150;
		$tamanho_texto['texto'] = 200;
		
		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------
		
		$count_dados = 0;
		foreach($tabela as $dados){
			$permisao = false;
			if($dados['tabela'] == 'conteudo')$permisao = procurar_conteudo_permisao($dados['id_conteudo']);
			
			if(!$permisao['no_search']){
				$nao_colocar_class = false;
				if(
					!$cel_aux ||
					$count_dados % $num_cols == 0
				){
					if($cel_aux)$modelo = modelo_var_in($modelo,'<!-- result_box -->',$cel_aux);
					$cel_aux = $result_box;
					$nao_colocar_class = true;
				}
				
				$caminho_raiz = $dados['caminho_raiz'];
				
				if($dados['tabela'] == 'galerias') $url_prefixo = 'galerias-imagens/';
				else if($dados['tabela'] == 'galerias_videos') $url_prefixo = 'galerias-videos/';
				else $url_prefixo = $caminho_raiz;
				
				$id = $dados['identificador'];
				$cod = $dados['id_conteudo'];
				$versao = $dados['versao'];
				
				$cel_aux2 = $result_cel;
				$count_col = 0;
				$img_mini_lay = false;
				$img_mini = false;
				foreach($dados as $chave => $dado){
					if($chave != 'versao'){
						if($dado){
							if(
								($chave == 'imagem_pequena' ||
								$chave == 'imagem_grande') &&
								$chave
							){
								if($dado){
									if(!$img_mini){
										$mini_aux = explode('.',$dado);
										$mini_path = $mini_aux[0] . "_mini." . $mini_aux[1];
										
										if(is_file($_SYSTEM['PATH'].$mini_path)){
											$img_mini_lay = html(Array(
												'tag' => 'img',
												'val' =>  '',
												'attr' => Array(
													'src' => '!#caminho_raiz#!'.$mini_path.'?v='.($versao?$versao:'0.1'),
													'class' => 'aimg',
												),
											));
											
											$img_mini_lay = html(Array(
												'tag' => 'a',
												'val' => $img_mini_lay,
												'attr' => Array(
													'href' => $id ? '/'.$_SYSTEM['ROOT'].$url_prefixo.$id.'/' : '/'.$_SYSTEM['ROOT'].$cod.'/',
													'style' => 'float:left;margin-right: 7px;',
												),
											));
											
											$img_mini = true;
										}
									}
								}
							} else {
								$found_tag = false;
								if($chave == 'texto'){
									if($_MODULOS_TAGS)
									foreach($_MODULOS_TAGS as $tag){
										if(preg_match('/'.$tag.'/i', $dado) > 0){
											$cel_aux2 = modelo_tag_in($cel_aux2,'<!-- '.$chave.' < -->','<!-- '.$chave.' > -->','');
											$found_tag = true;
										}
									}
								}
								
								if(!$found_tag){
									$dado = limitar_texto_html($dado,$tamanho_texto[$chave]);
									
									$dado = html(Array(
										'tag' => 'a',
										'val' => $dado,
										'attr' => Array(
											'href' => $id ? '/'.$_SYSTEM['ROOT'].$url_prefixo.$id.'/' : '/'.$_SYSTEM['ROOT'].$cod.'/',
										),
									));
									
									$cel_aux2 = modelo_var_troca($cel_aux2,"#".$chave,$dado);
								}
							}
						} else {
							$cel_aux2 = modelo_tag_in($cel_aux2,'<!-- '.$chave.' < -->','<!-- '.$chave.' > -->','');
						}
						$count_col++;
					}
				}
				
				$cel_aux2 = modelo_var_troca($cel_aux2,"#img_mini#",$img_mini_lay);
				if(!$nao_colocar_class) $class_extra = ' '.$parametros['class_extra']; else $class_extra = '';
				$cel_aux2 = modelo_var_troca($cel_aux2,"#class_extra",$class_extra);
				
				$cel_aux = modelo_var_in($cel_aux,'<!-- result_cel -->',$cel_aux2);
				
				$count_dados++;
			}
		}
		
		$modelo = modelo_var_in($modelo,'<!-- result_box -->',$cel_aux);
	} else
		$modelo = modelo_tag_in($modelo,$parametros['result_cel_1'],$parametros['result_cel_2'],'<p>'.$parametros['mensagem_erro'].'</p>');
	
	if($connect)banco_fechar_conexao();	
	
	return $modelo;
}

function procurar_layout($parametros){
	global $_SYSTEM;
	global $_LAYOUT_BASICO;
	
	$parametros['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'procurar.html';
	$parametros['layout_tag1'] = '<!-- layout < -->';
	$parametros['layout_tag2'] = '<!-- layout > -->';
	
	if(!$parametros['procurar_titulo'])			$parametros['procurar_titulo'] = 'Procurar';
	if(!$parametros['procurar_sub_titulo'])		$parametros['procurar_sub_titulo'] = 'Resultados encontrados para essa busca';
	if(!$parametros['mensagem_erro'])		$parametros['mensagem_erro'] = 'Não foram encontrados resultados!';
	if(!$parametros['result_width'])			$parametros['result_width'] = '200';
	if(!$parametros['css']){
		$parametros['css'] = Array(
			'class_extra' => '',
		);
	}
	
	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);
	
	$modelo = modelo_var_troca_tudo($modelo,"#css_tabela_lista",$parametros['css']['tabela_lista']);
	$modelo = modelo_var_troca_tudo($modelo,"#css_lista_header",$parametros['css']['lista_header']);
	$modelo = modelo_var_troca_tudo($modelo,"#css_lista_cel",$parametros['css']['lista_cel']);
	
	if($parametros['informacao_titulo']){
		$modelo = modelo_var_troca($modelo,"#informacao_titulo",$parametros['informacao_titulo']);
	} else {
		$modelo = modelo_tag_in($modelo,'<!-- informacao_titulo < -->','<!-- informacao_titulo > -->','<!-- informacao_titulo -->');
	}
	
	if($parametros['mais_informacao_acima'])	$mais_informacao_acima = $parametros['mais_informacao_acima'];
	if($parametros['mais_informacao_abaixo'])	$mais_informacao_abaixo = $parametros['mais_informacao_abaixo'];
	
	switch($parametros['opcao']){
		case 'lista':
			if($parametros['menu_pagina_acima'])		$menu_paginas_1 = procurar_menu_paginas(
				Array(
					'menu_dont_show' => $parametros['menu_dont_show'], // Id do menu
					'menu_paginas_id' => $parametros['menu_paginas_id'], // Id do menu
					'menu_paginas_reiniciar' => $parametros['menu_paginas_reiniciar'], // Reiniciar do menu
					'forcar_inicio' => $parametros['forcar_inicio'], // Reiniciar do menu
					'tabela_id' => $parametros['tabela_campos'][$parametros['tabela_id_posicao']], // tag delimitadora do menu
					'tabela_nome' => $parametros['tabela_nome'], // tag delimitadora do menu
					'tabela_extra' => $parametros['tabela_extra'], // cel de cada opção do menu
					'layout_url' => $parametros['layout_url'], // url do layout
					'pesquisa' => $parametros['pesquisa'], // pesquisa
					'tabela_campos' => $parametros['tabela_campos'], // pesquisa
				)
			);
			if($parametros['menu_pagina_embaixo'])		$menu_paginas_2 = procurar_menu_paginas(
				Array(
					'menu_dont_show' => $parametros['menu_dont_show'], // Id do menu
					'menu_paginas_id' => $parametros['menu_paginas_id'], // Id do menu
					'menu_paginas_reiniciar' => $parametros['menu_paginas_reiniciar'], // Reiniciar do menu
					'forcar_inicio' => $parametros['forcar_inicio'], // Reiniciar do menu
					'tabela_id' => $parametros['tabela_campos'][$parametros['tabela_id_posicao']], // tag delimitadora do menu
					'tabela_nome' => $parametros['tabela_nome'], // tag delimitadora do menu
					'tabela_extra' => $parametros['tabela_extra'], // cel de cada opção do menu
					'layout_url' => $parametros['layout_url'], // url do layout
					'pesquisa' => $parametros['pesquisa'], // pesquisa
					'tabela_campos' => $parametros['tabela_campos'], // pesquisa
				)
			);
			
			if($_LAYOUT_BASICO){
				$modelo = procurar_lista($parametros);
			} else {
				$modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.procurar_lista($parametros).$mais_informacao_abaixo);
			}
		break;
		default:
			$modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.$parametros['inclusao'].$mais_informacao_abaixo);
	}
	
	$modelo = modelo_var_troca($modelo,"#menu_paginas_1",$menu_paginas_1);
	$modelo = modelo_var_troca($modelo,"#menu_paginas_2",$menu_paginas_2);
	$modelo = modelo_var_troca($modelo,"#_informacao_acima",$parametros['informacao_acima']);
	$modelo = modelo_var_troca($modelo,"#_informacao_abaixo",$parametros['informacao_abaixo']);
	
	return $modelo;
}

?>