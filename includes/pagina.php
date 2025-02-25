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

$_VERSAO_MODULO_INCLUDE				=	'1.5.4';

function pagina_alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	global $_MENSAGEM_ERRO;
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	
	if(!$_DESATIVAR_PADRAO['login']){
		switch ($nAlerta){
			case 1:		$mensSaida	=	"<p>Mensagem Enviada com sucesso!</p><p>Em breve um de nossos atendentes entrará em contato!</p>";break;
			case 2:		$mensSaida	=	"<p>Usuário inexistente!</p><p>Nota: Favor preencher corretamente o nome de usuário do sistema.</p><p>".$_MENSAGEM_ERRO."</p>";																			break;
			case 3: 	$mensSaida	=	"<p>Usuário inativado no sistema!</p><p>Nota: Favor entrar em contato com administrador do sistema para reativa-lo!</p><p>Contato: " . $_SYSTEM['ADMIN_EMAIL'] . "</p><p>".$_MENSAGEM_ERRO."</p>";				break;
			case 4: 	$mensSaida	=	"<p>Você atingiu a quantidade limite de tentativas de login nesse período!</p><p>Nota: Por motivos de segurança você deve aguardar ".floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60)." minuto(s) antes de tentar novamente!</p><p>Qualquer dúvida entre em contato pelo e-mail: <a href=\"mailto:".$_SYSTEM['ADMIN_EMAIL']."\">" . $_SYSTEM['ADMIN_EMAIL'] . "</a></p>";			break;
			case 5:		$mensSaida	=	"<p>E-MAIL DESCADASTRADO COM SUCESSO!</p><p>Você não receberá mais nossos e-mails!</p>";break;
			case 6:		$mensSaida	=	"<p>Senha Incorreta!</p><p>Nota: Favor preencher corretamente a senha.</p><p>".$_MENSAGEM_ERRO."</p>";																			break;
			//case 1:		$mensSaida	=	"";break;
			default:	$mensSaida	=	$nAlerta;
		}

		$_ALERTA = $mensSaida;
	}
}

// ================================= Módulos 1.4 ===============================

function pagina_banner_galeria_videos_rotativos($params){
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	global $_PERMISSAO;
	global $_PROJETO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$galeria_grupo = $params['galeria_grupo'];
	$pagina = $params['pagina'];
	$cel_nome = $params['cel_nome'];
	$ordenacao = $params['ordenacao'];
	$limitar_width = $params['limitar_width'];
	$limite = $params['limite'];
	$banner_div_attr = $params['banner_div_attr'];
	$link_externo = $params['link_externo'];
	$link_unico = $params['link_unico'];
	$link_se_nao_existe = $params['link_se_nao_existe'];
	$mostrar_label = $params['mostrar_label'];
	$imagem_grande = $params['imagem_grande'];
	$link_target = ($params['link_target']?$params['link_target']:'_self');
	
	$campos = Array(
		't1.id_videos',
		't1.descricao',
	);
	
	if($imagem_grande){
		$campos[] = 't1.imagem_grande';
	} else {
		$campos[] = 't1.imagem_media';
	}
	
	$conteudos = banco_select_name
	(
		banco_campos_virgulas($campos)
		,
		"videos AS t1,galerias_videos AS t2",
		"WHERE t1.status='A'"
		." AND t2.status='A'"
		." AND t1.id_galerias_videos=t2.id_galerias_videos"
		.($galeria_grupo?
			" AND t2.grupo='".$galeria_grupo."'"
			:
			""
		)
		." ORDER BY ".( $ordenacao ? $ordenacao : "RAND()")
		.( $limite ? " LIMIT " . $limite : "")
	);
	
	if($conteudos){
		$banners = "<!-- banners -->";
		$total = count($conteudos);
		foreach($conteudos as $conteudo){
			$link = '';
			
			$cod = $conteudo['t1.id_videos'];
			$alt = $conteudo['t1.descricao'];
			
			if($imagem_grande){
				$imagem = $conteudo['t1.imagem_grande'];
			} else {
				$imagem = $conteudo['t1.imagem_media'];
			}
			
			if($_PROJETO['pagina']['banner-escala-cinza']){
				$imagem_aux = explode('.',$imagem);
				$imagem2 = $imagem;
				$imagem = $imagem_aux[0].'-pb.'.$imagem_aux[1];
			}
			
			$image_info = imagem_info($imagem);
			
			$width = $image_info[0];
			$height = $image_info[1];
			
			if($limitar_width){
				if($width > $limitar_width){
					$height = floor(($height*$limitar_width)/$width);
					$width = $limitar_width;
				}
			}
			
			$attr = Array(
				'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem.$versao,
				'title' => $titulo,
				'alt' => $alt,
				'width' => $width,
				'height' => $height,
				'border' => '0',
			);
			
			if($_PROJETO['pagina']['banner-escala-cinza'])$attr['class'] = 'image_hover';
			if($_PROJETO['pagina']['banner-escala-cinza'])$attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$imagem2;
			
			$banner = html(Array(
				'tag' => 'img',
				'val' => '',
				'attr' => $attr,
			));
			
			if($_PROJETO['pagina']['banner-escala-cinza']){
				$conteiner_images_hiddens .= html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => Array(
						'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem2,
						'style' => 'display:none;',
					),
				));
			}
			
			if($link_unico){
				$link_unico_aux = modelo_var_troca($link_unico,'#cod',$cod);
				$banner = html(Array(
					'tag' => 'a',
					'val' => $banner,
					'attr' => Array(
						'href' => $link_unico_aux,
						'target' => $link_target,
					)
				));
			} else if($link_externo && $link){
				$link_aux = modelo_var_troca($link,'#cod',$cod);
				$banner = html(Array(
					'tag' => 'a',
					'val' => $banner,
					'attr' => Array(
						'href' => $link_aux,
						'target' => $link_target,
					)
				));
			} else if($link_se_nao_existe){
				$banner = html(Array(
					'tag' => 'a',
					'val' => $banner,
					'attr' => Array(
						'href' => $link_se_nao_existe,
						'target' => $link_target,
					)
				));
			}
			
			if($mostrar_label){
				$label = html(Array(
					'tag' => 'div',
					'val' => $titulo,
					'attr' => Array(
						'style' => 'color: #FFFFFF;font-size: 14px;font-family: Tahoma, Geneva, sans-serif; overflow: hidden; height: 20px; width: 100%; line-height: 20px; font-weight: bold;',
					)
				)).html(Array(
					'tag' => 'div',
					'val' => $sub_titulo,
					'attr' => Array(
						'style' => 'color: #FFFFFF;font-size: 12px;font-family: Tahoma, Geneva, sans-serif; overflow: hidden; height: 20px; width: 100%; line-height: 20px;',
					)
				));
				
				$banner .= html(Array(
					'tag' => 'div',
					'val' => '',
					'attr' => Array(
						'style' => 'z-index: 1; position: absolute; bottom: 0px; left: 0px; background-color: #000000; opacity:0.50; -moz-opacity: 0.50; filter: alpha(opacity=50); width: 100%; height: 50px;',
					)
				)).html(Array(
					'tag' => 'div',
					'val' => $label,
					'attr' => Array(
						'style' => 'z-index: 2; position: absolute; bottom: 0px; left: 0px; padding: 5px; width: 100%; height: 40px;',
					)
				));
			}
			
			$cel = html(Array(
				'tag' => 'div',
				'val' => $banner,
				'attr' => Array(
					'class' => 'img-container',
				)
			));
			
			$count++;
			
			if($total == $count){
				$banners = modelo_var_troca($banners,'<!-- banners -->',"\n".$cel."\n");
			} else {
				$banners = modelo_var_in($banners,'<!-- banners -->',"\n".$cel);
			}
		}
	}
	
	if($limitar_width){
		$banner_div_attr['width'] = $limitar_width;
		$banner_div_attr['style'] = 'text-align: center;';
	}
	
	$conteiner = html(Array(
		'tag' => 'div',
		'val' => $banners,
		'attr' => $banner_div_attr,
	));
	
	if($_PROJETO['pagina']['banner-escala-cinza'])$conteiner .= $conteiner_images_hiddens;
	
	if($connect_db)banco_fechar_conexao();
	
	if($pagina)
		return modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->',$conteiner);
	else
		return $conteiner;
}

// ================================= Módulos 1.1 ===============================

function pagina_banner_rotativo($params){
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	global $_PERMISSAO;
	global $_PROJETO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$galeria_grupo = $params['galeria_grupo'];
	$pagina = $params['pagina'];
	$cel_nome = $params['cel_nome'];
	$galeria = $params['galeria'];
	$identificador = $params['identificador'];
	$ordenacao = $params['ordenacao'];
	$limitar_width = $params['limitar_width'];
	$limite = $params['limite'];
	$banner_div_attr = $params['banner_div_attr'];
	$imagem_pequena = $params['imagem_pequena'];
	$image_hover = $params['image_hover'];
	$link_externo = $params['link_externo'];
	$link_externo_url_prefix = $params['link_externo_url_prefix'];
	$link_unico = $params['link_unico'];
	$link_se_nao_existe = $params['link_se_nao_existe'];
	$mostrar_label = $params['mostrar_label'];
	$limit_metade = $params['limit_metade'];
	$ordenacao_invertida = $params['ordenacao_invertida'];
	$link_target = ($params['link_target']?$params['link_target']:'_self');
	
	if($galeria){
		$conteudos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_imagens',
				't1.descricao',
				't1.local_media',
			))
			,
			"imagens AS t1,galerias AS t2",
			"WHERE t1.status='A'"
			." AND t2.status='A'"
			." AND t1.id_galerias=t2.id_galerias"
			.($galeria_grupo?
				" AND t2.grupo='".$galeria_grupo."'"
				:
				""
			)
			." ORDER BY ".( $ordenacao ? $ordenacao : "RAND()")
			.( $limite ? " LIMIT " . $limite : "")
		);
	} else {
		$conteudos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_conteudo',
				't1.titulo',
				't1.sub_titulo',
				't1.imagem_grande',
				't1.imagem_pequena',
				't1.link_externo',
				't1.versao',
				't1.imagem_pequena_title',
				't1.imagem_pequena_alt',
				't1.imagem_grande_title',
				't1.imagem_grande_alt',
			))
			,
			"conteudo as t1,conteudo as t2",
			"WHERE t2.identificador='".$identificador."'"
			." AND t1.status='A'"
			." AND t1.id_conteudo_pai=t2.id_conteudo"
			." ORDER BY t1.ordem ASC,".( $ordenacao ? $ordenacao : "RAND()")
			.( $limite ? " LIMIT " . $limite : "")
		);
	}
	
	if($conteudos){
		$banners = "<!-- banners -->";
		$total = count($conteudos);
		
		if($ordenacao_invertida) $conteudos = array_reverse($conteudos);
		
		foreach($conteudos as $conteudo){
			$link = '';
			
			if($galeria){
				$cod = $conteudo['t1.id_imagens'];
				$imagem = $conteudo['t1.local_media'];
				$alt = $conteudo['t1.descricao'];
			} else {
				$cod = $conteudo['t1.id_conteudo'];
				$imagem = ($imagem_pequena ? $conteudo['t1.imagem_pequena'] : $conteudo['t1.imagem_grande']);
				
				$titulo_aux = ($imagem_pequena ? $conteudo['t1.imagem_pequena_title'] : $conteudo['t1.imagem_grande_title']);
				$alt_aux = ($imagem_pequena ? $conteudo['t1.imagem_pequena_alt'] : $conteudo['t1.imagem_grande_alt']);
				
				$titulo = ( $titulo_aux ? $titulo_aux : $conteudo['t1.titulo'] );
				$sub_titulo = $conteudo['t1.sub_titulo'];
				
				$alt = $alt_aux;
				$link = $conteudo['t1.link_externo'];
				if($link[0] == '/') $link = '/'.$_SYSTEM['ROOT'] . substr($link,1,strlen($link)-1);
				$versao = '?v='.$conteudo['t1.versao'];
			}
			
			if($_PROJETO['pagina']['banner-escala-cinza']){
				$imagem_aux = explode('.',$imagem);
				$imagem2 = $imagem;
				$imagem = $imagem_aux[0].'-pb.'.$imagem_aux[1];
			}
			
			if($image_hover){
				$imagem2 = ($imagem_pequena ? $conteudo['t1.imagem_grande'] : $conteudo['t1.imagem_pequena']);
			}
			
			$image_info = imagem_info($imagem);
			
			$width = $image_info[0];
			$height = $image_info[1];
			
			if($limitar_width){
				if($width > $limitar_width){
					$height = floor(($height*$limitar_width)/$width);
					$width = $limitar_width;
				}
			}
			
			$attr = Array(
				'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem.$versao,
				'title' => $titulo,
				'alt' => $alt,
				'width' => $width,
				'height' => $height,
				'border' => '0',
			);
			
			if($_PROJETO['pagina']['banner-escala-cinza'])$attr['class'] = 'image_hover';
			if($_PROJETO['pagina']['banner-escala-cinza'])$attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$imagem2;
			
			if($image_hover)$attr['class'] = 'image_hover';
			if($image_hover)$attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$imagem2;
			
			$banner = html(Array(
				'tag' => 'img',
				'val' => '',
				'attr' => $attr,
			));
			
			if($_PROJETO['pagina']['banner-escala-cinza'] || $image_hover){
				$conteiner_images_hiddens .= html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => Array(
						'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem2,
						'style' => 'display:none;',
					),
				));
			}
			
			if($link_unico){
				$link_unico_aux = modelo_var_troca($link_unico,'#cod',$cod);
				$banner = html(Array(
					'tag' => 'a',
					'val' => $banner,
					'attr' => Array(
						'href' => $link_unico_aux,
						'target' => $link_target,
					)
				));
			} else if($link_externo && $link){
				$link_aux = modelo_var_troca($link,'#cod',$cod);
				$banner = html(Array(
					'tag' => 'a',
					'val' => $banner,
					'attr' => Array(
						'href' => $link_aux,
						'target' => $link_target,
					)
				));
			} else if($link_se_nao_existe){
				$banner = html(Array(
					'tag' => 'a',
					'val' => $banner,
					'attr' => Array(
						'href' => $link_se_nao_existe,
						'target' => $link_target,
					)
				));
			}
			
			if($mostrar_label){
				$label = html(Array(
					'tag' => 'div',
					'val' => $titulo,
					'attr' => Array(
						'style' => 'color: #FFFFFF;font-size: 14px;font-family: Tahoma, Geneva, sans-serif; overflow: hidden; height: 20px; width: 100%; line-height: 20px; font-weight: bold;',
					)
				)).html(Array(
					'tag' => 'div',
					'val' => $sub_titulo,
					'attr' => Array(
						'style' => 'color: #FFFFFF;font-size: 12px;font-family: Tahoma, Geneva, sans-serif; overflow: hidden; height: 20px; width: 100%; line-height: 20px;',
					)
				));
				
				$banner .= html(Array(
					'tag' => 'div',
					'val' => '',
					'attr' => Array(
						'style' => 'z-index: 1; position: absolute; bottom: 0px; left: 0px; background-color: #000000; opacity:0.50; -moz-opacity: 0.50; filter: alpha(opacity=50); width: 100%; height: 50px;',
					)
				)).html(Array(
					'tag' => 'div',
					'val' => $label,
					'attr' => Array(
						'style' => 'z-index: 2; position: absolute; bottom: 0px; left: 0px; padding: 5px; width: 100%; height: 40px;',
					)
				));
			}
			
			$cel = html(Array(
				'tag' => 'div',
				'val' => $banner,
				'attr' => Array(
					'class' => 'img-container',
				)
			));
			
			$count++;
			
			if($limit_metade){
				if($total % 2 != 0 && $ordenacao_invertida){
					$impar_ordenacao_invertida = true;
				}
				
				if($total/2 <= $count + ($impar_ordenacao_invertida?1:0)){
					$banners = modelo_var_troca($banners,'<!-- banners -->',"\n".$cel."\n");
					break;
				} else {
					$banners = modelo_var_in($banners,'<!-- banners -->',"\n".$cel);
				}
			} else {
				if($total == $count){
					$banners = modelo_var_troca($banners,'<!-- banners -->',"\n".$cel."\n");
				} else {
					$banners = modelo_var_in($banners,'<!-- banners -->',"\n".$cel);
				}
			}
		}
		
		if($limitar_width){
			$banner_div_attr['width'] = $limitar_width;
			$banner_div_attr['style'] = 'text-align: center;';
		}
		
		$conteiner = html(Array(
			'tag' => 'div',
			'val' => $banners,
			'attr' => $banner_div_attr,
		));
	}
	
	if($_PROJETO['pagina']['banner-escala-cinza'])$conteiner .= $conteiner_images_hiddens;
	
	if($connect_db)banco_fechar_conexao();
	
	if($pagina)
		return modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->',$conteiner);
	else
		return $conteiner;
}

function pagina_galeria_select($params){
	/* 
	$select = opcao_select_change(Array(
		'nome' => 'select',
		'tabela_campos' => Array(
			'id' => 'id',
			'nome' => 'nome',
		),
		'tabela_nome' => 'tabela_nome',
		'tabela_extra' => false,
		'tabela_order' => 'campo DESC',
		'opcao_inicial' => 'Selecione',
		'link_extra' => 'opcao=valor',
		'url' => false,
	));
	*/
	
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	$nome = $params['nome'];
	$tabela_campos = $params['tabela_campos'];
	$tabela_nome = $params['tabela_nome'];
	$tabela_extra = $params['tabela_extra'];
	$tabela_order = $params['tabela_order'];
	$opcao_inicial = $params['opcao_inicial'];
	$opcao_inicial_id = $params['opcao_inicial_id'];
	$link_extra = $params['link_extra'];
	$url = $params['url'];
	$id = $nome . '_id';
	
	if($_REQUEST[$id])	$_SESSION[$_SYSTEM['ID'].$id] = $_REQUEST[$id];
	if($opcao_inicial_id)	$_SESSION[$_SYSTEM['ID'].$id] = $opcao_inicial_id;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultados = banco_select_name
	(
		banco_campos_virgulas(Array(
			$tabela_campos['id'],
			$tabela_campos['nome'],
		))
		,
		$tabela_nome,
		$tabela_extra
		.($tabela_order ? " ORDER BY " . $tabela_order : "")
	);
	if($connect_db)banco_fechar_conexao();
	
	if($opcao_inicial){
		$options[] = $opcao_inicial . "...";
		$optionsValue[] = "-1";
	}
	
	$cont = 0;
	if($resultados)
	foreach($resultados as $resultado){
		$options[] = $resultado[$tabela_campos['nome']];
		$optionsValue[] =  $resultado[$tabela_campos['id']];
		
		$cont++;
		
		if($cont == 1 && !$_SESSION[$_SYSTEM['ID'].$id]){
			$_SESSION[$_SYSTEM['ID'].$id] = $resultado[$tabela_campos['id']];
		}
		
		if($_SESSION[$_SYSTEM['ID'].$id] == $resultado[$tabela_campos['id']]){
			$optionSelected = $cont;
			if(!$opcao_inicial)$optionSelected--;
		}
	}
	
	if(!$optionSelected){
		$optionSelected = 1;
		if(!$opcao_inicial)$optionSelected--;
	}
	
	if($link_extra)$link_extra .= '&';
	if($url)$url .= '?';
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'onchange=window.open("'.$url.$link_extra.$id.'="+this.value+"#galerias","_self")');
	
	return $select;
}

function pagina_galeria($params){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$imagem_inicial_id = $params['imagem_inicial_id'];
	$galeria_id = $params['galeria_id'];
	$ordenacao = $params['ordenacao'];
	$limite = $params['limite'];
	$local_url = $params['local_url'];
	$pagina = $params['pagina'];
	$cel_nome = $params['cel_nome'];
	$tabela_order = $params['tabela_order'];
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes/pagina.html');
	$conteiner = modelo_tag_val($modelo,'<!-- galeria < -->','<!-- galeria > -->');
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($conteiner,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$conteiner = modelo_tag_in($conteiner,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	if($imagem_inicial_id){
		$imagem_inicial = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_galerias',
				'descricao',
				'local_mini',
				'local_original',
			))
			,
			"imagens",
			"WHERE id_imagens='".$imagem_inicial_id."'"
		);
		
		if($imagem_inicial){
			$galeria_id = $imagem_inicial[0]['id_galerias'];
		}
	}
	
	$galeria_select = pagina_galeria_select(Array(
		'opcao_inicial_id' => $galeria_id,
		'nome' => 'galeria_select',
		'tabela_campos' => Array(
			'id' => 'id_galerias',
			'nome' => 'nome',
		),
		'tabela_nome' => 'galerias',
		'tabela_extra' => "WHERE status='A'",
		'tabela_order' => $tabela_order ? $tabela_order : 'nome ASC',
		'opcao_inicial' => 'Selecione a galeria',
		'link_extra' => $local_url,
		'url' => false,
	));
	
	$conteiner = modelo_var_troca($conteiner,"#galerias#",$galeria_select);
	
	if(!$galeria_id)$galeria_id = $_SESSION[$_SYSTEM['ID'].'galeria_select_id'];
	
	$imagens = banco_select_name
	(
		banco_campos_virgulas(Array(
			'descricao',
			'local_mini',
			'local_original',
		))
		,
		"imagens",
		"WHERE status='A'"
		.($imagem_inicial_id ? " AND id_imagens!='".$imagem_inicial_id."'" : "")
		." AND id_galerias='".$galeria_id."'"
		." ORDER BY ".( $ordenacao ? $ordenacao : "id_imagens DESC")
		.( $limite ? " LIMIT " . $limite : "")
	);
	
	if($imagem_inicial){
		$cel_nome = 'cel';
		$cel_aux = $cel[$cel_nome];
		
		$num_imagens++;
		
		if(!$imagem_inicial[0]['descricao']) $imagem_inicial[0]['descricao'] = 'Imagem '.$num_imagens;
		
		$cel_aux = modelo_var_troca($cel_aux,"#imagem#grande#",$_CAMINHO_RELATIVO_RAIZ.$imagem_inicial[0]['local_original']);
		$cel_aux = modelo_var_troca($cel_aux,"#imagem#pequena#",$_CAMINHO_RELATIVO_RAIZ.$imagem_inicial[0]['local_mini']);
		$cel_aux = modelo_var_troca($cel_aux,"#descricao#",limite_texto($imagem_inicial[0]['descricao'],250));
		$cel_aux = modelo_var_troca_tudo($cel_aux,"#titulo#",limite_texto($imagem_inicial[0]['descricao'],250));
		
		$conteiner = modelo_var_in($conteiner,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	
	if($imagens)
	foreach($imagens as $imagem){
		$cel_nome = 'cel';
		$cel_aux = $cel[$cel_nome];
		
		$num_imagens++;
		
		if(!$imagem['descricao']) $imagem['descricao'] = 'Imagem '.$num_imagens;
		
		$cel_aux = modelo_var_troca($cel_aux,"#imagem#grande#",$_CAMINHO_RELATIVO_RAIZ.$imagem['local_original']);
		$cel_aux = modelo_var_troca($cel_aux,"#imagem#pequena#",$_CAMINHO_RELATIVO_RAIZ.$imagem['local_mini']);
		$cel_aux = modelo_var_troca($cel_aux,"#descricao#",limite_texto($imagem['descricao'],250));
		$cel_aux = modelo_var_troca_tudo($cel_aux,"#titulo#",limite_texto($imagem['descricao'],250));
		
		$conteiner = modelo_var_in($conteiner,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	
	if($connect_db)banco_fechar_conexao();
	
	if($pagina)
		return modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->',$conteiner);
	else
		return $conteiner;
}

function pagina_chamada($params){
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$identificador_auxiliar = $params['identificador_auxiliar'];
	$identificador = $params['identificador'];
	$cel = $params['cel'];
	$pagina = $params['pagina'];
	$campos = $params['campos'];
	$limite_texto = $params['limite_texto'];
	$pai_id = $params['pai_id'];
	$order = $params['order'];
	$order_especifica = $params['order_especifica'];
	$layout = $params['layout'];
	$link_se_nao_existe = $params['link_se_nao_existe'];
	$link_texto = $params['link_texto'];
	$link_target = $params['link_target'];
	$link_conteudo = $params['link_conteudo'];
	$link_raiz = $params['link_raiz'];
	$classes = $params['classes'];
	$no_defaults = $params['no_defaults'];
	$escala_cinza_hover = $params['escala_cinza_hover'];
	$forcar_miniatura = $params['forcar_miniatura'];
	
	if($identificador_auxiliar) $campos[] = 'identificador';
	
	if($pai_id){
		foreach($campos as $campo){
			$campos_novo[] = 't1.'.$campo;
			
			switch($campo){
				case 'imagem_pequena':
					$campos_novo[] = 't1.'.'imagem_pequena_title';
					$campos_novo[] = 't1.'.'imagem_pequena_alt';
				break;
				case 'imagem_grande':
					$campos_novo[] = 't1.'.'imagem_grande_title';
					$campos_novo[] = 't1.'.'imagem_grande_alt';
				break;
				case 'titulo_img':
					$campos_novo[] = 't1.'.'titulo_img_title';
					$campos_novo[] = 't1.'.'titulo_img_alt';
				break;
			}
		}
		$campos_novo[] = 't1.'.'caminho_raiz';
		
		$campos = $campos_novo;
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			"conteudo AS t1,conteudo AS t2",
			"WHERE t2.".($identificador_auxiliar?"identificador_auxiliar='".$identificador_auxiliar."'":"identificador='".$identificador."'")
			." AND t1.id_conteudo_pai=t2.id_conteudo"
			." AND t1.status='A'"
			.($order_especifica ? $order_especifica : ($order?" ORDER BY t1.".$order." LIMIT 1":" ORDER BY t1.ordem ASC"))
		);
	} else {
		$campos_novo = false;
		foreach($campos as $campo){
			$campos_novo[] = $campo;
			
			switch($campo){
				case 'imagem_pequena':
					$campos_novo[] = 'imagem_pequena_title';
					$campos_novo[] = 'imagem_pequena_alt';
				break;
				case 'imagem_grande':
					$campos_novo[] = 'imagem_grande_title';
					$campos_novo[] = 'imagem_grande_alt';
				break;
				case 'titulo_img':
					$campos_novo[] = 'titulo_img_title';
					$campos_novo[] = 'titulo_img_alt';
				break;
			}
		}
		
		$campos_novo[] = 'caminho_raiz';
		
		$campos = $campos_novo;
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			"conteudo",
			"WHERE ".($identificador_auxiliar?"identificador_auxiliar='".$identificador_auxiliar."'":"identificador='".$identificador."'")
			." AND status='A'"
		);
	}
	
	$campos_todos = Array(
		'imagem_pequena',
		'imagem_grande',
		'titulo_img',
		'link_externo',
		'titulo',
		'sub_titulo',
		'data',
		'texto',
		'musica',
		'galeria',
		'parametros',
		'videos_youtube',
	);
	
	if($resultado){
		if($identificador_auxiliar) $identificador = $resultado[0][($pai_id?'t1.':'').'identificador'];
	
		foreach($campos_todos as $campo){
			if($resultado[0][($pai_id?'t1.':'').$campo] && $campo != 'identificador'){
				$dado = $resultado[0][($pai_id?'t1.':'').$campo];
				
				switch($campo){
					case 'imagem_pequena':
					case 'imagem_grande':
					case 'titulo_img':
						switch($campo){
							case 'imagem_pequena':
								$title = $resultado[0][($pai_id?'t1.':'').'imagem_pequena_title'];
								$alt = $resultado[0][($pai_id?'t1.':'').'imagem_pequena_alt'];
							break;
							case 'imagem_grande':
								$title = $resultado[0][($pai_id?'t1.':'').'imagem_grande_title'];
								$alt = $resultado[0][($pai_id?'t1.':'').'imagem_grande_alt'];
							break;
							case 'titulo_img':
								$title = $resultado[0][($pai_id?'t1.':'').'titulo_img_title'];
								$alt = $resultado[0][($pai_id?'t1.':'').'titulo_img_alt'];
							break;
						}
						
						if($forcar_miniatura[$campo]){
							$imagem_bd_aux = explode('.',$dado);
							$dado = $imagem_bd_aux[0].'-mini.'.$imagem_bd_aux[1];
						}
						
						if($escala_cinza_hover){
							$imagem_bd_aux = explode('.',$dado);
							$imagem_path = $imagem_bd_aux[0].'-pb.'.$imagem_bd_aux[1];
							$imagem_path2 = $dado;
							$dado = $_CAMINHO_RELATIVO_RAIZ.$imagem_path;
						} else {
							$dado = $_CAMINHO_RELATIVO_RAIZ.$dado;
						}
						
						$attr = Array(
							'src' => $dado,
							'title' => $title,
							'alt' => $alt,
							'border' => '0',
						);
						
						if($escala_cinza_hover){
							$attr['class'] = 'image_hover';
							$attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$imagem_path2;
						}
						
						if($classes[$campo])$attr['class'] = $classes[$campo];
						
						if(!$no_defaults[$campo]){
							$dado = html(Array(
								'tag' => 'img',
								'val' => '',
								'attr' => $attr
							));
							
							if($escala_cinza_hover){
								$dado .= html(Array(
									'tag' => 'img',
									'val' => '',
									'attr' => Array(
										'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path2,
										'style' => 'display:none;',
									),
								));
							}
						}
					break;
					case 'titulo':
						$attr = Array();
						if($classes[$campo])$attr['class'] = $classes[$campo];
						
						if(!$no_defaults[$campo])
							$dado = html(Array(
								'tag' => 'h1',
								'val' => $dado,
								'attr' => $attr
							));
					break;
					case 'sub_titulo':
						$attr = Array();
						if($classes[$campo])$attr['class'] = $classes[$campo];
						
						if(!$no_defaults[$campo])
							$dado = html(Array(
								'tag' => 'h2',
								'val' => $dado,
								'attr' => $attr
							));
					break;
					case 'link_externo':
						if($link_raiz)$dado = '/'.$_SYSTEM['ROOT'] . $dado;
						if($dado[0] == '/') $dado = '/'.$_SYSTEM['ROOT'] . substr($dado,1,strlen($dado)-1);
						
						$attr = Array(
							'href' => $dado,
							'target' => $link_target ? $link_target : '_blank',
						);
						if($classes[$campo])$attr['class'] = $classes[$campo];
						
						if(!$no_defaults[$campo])
							$dado = html(Array(
								'tag' => 'a',
								'val' => $link_texto ? $link_texto : $dado,
								'attr' => $attr
							));
					break;
					case 'data':
						$dado = data_hora_from_datetime_to_text($dado);
						
						$attr = Array();
						if($classes[$campo])$attr['class'] = $classes[$campo];
						
						if(!$no_defaults[$campo])
							$dado = html(Array(
								'tag' => 'p',
								'val' => $dado,
								'attr' => $attr
							));
					break;
					case 'texto':
						$dado = ( $limite_texto ? limitar_texto_html($dado,$limite_texto,'<h1><h2><h3><p><a><i><ul><li><br><b><strong>') : $dado );
					break;
					
				}
			} else {
				$dado = '';
				
				switch($campo){
					case 'link_externo':
						if($link_se_nao_existe){
							$attr = Array(
								'href' => $link_se_nao_existe,
								'target' => $link_target ? $link_target : '_blank',
							);
							if($classes[$campo])$attr['class'] = $classes[$campo];
							
							if(!$no_defaults[$campo])
								$dado = html(Array(
									'tag' => 'a',
									'val' => $link_texto ? $link_texto : $link_se_nao_existe,
									'attr' => $attr
								));
							else 
								$dado = $link_se_nao_existe;
						}
						
						if($link_conteudo){							
							$attr = Array(
								'href' => '/'.$_SYSTEM['ROOT'].$resultado[0][($pai_id?'t1.':'').'caminho_raiz'].$identificador.($pai_id?'/'.$resultado[0]['t1.'.'identificador']:''),
								'target' => $link_target ? $link_target : '_blank',
							);
							if($classes[$campo])$attr['class'] = $classes[$campo];
							
							if(!$no_defaults[$campo])
								$dado = html(Array(
									'tag' => 'a',
									'val' => $link_texto ? $link_texto : '/'.$_SYSTEM['ROOT'].$resultado[0][($pai_id?'t1.':'').'caminho_raiz'].$identificador.($pai_id?'/'.$resultado[0]['t1.'.'identificador']:''),
									'attr' => $attr
								));
							else 
								$dado = '/'.$_SYSTEM['ROOT'].$resultado[0][($pai_id?'t1.':'').'caminho_raiz'].$identificador.($pai_id?'/'.$resultado[0]['t1.'.'identificador']:'');
						}
					break;
				}
			}
			
			if($layout[$campo])$dado = modelo_var_troca($layout[$campo],"#dados#",$dado);
			
			$pagina = modelo_var_troca_tudo($pagina,"#".($cel?$cel:($identificador_auxiliar?$identificador_auxiliar:$identificador))."#".$campo."#",$dado);
		}
	} else {
		foreach($campos_todos as $campo){
			$pagina = modelo_var_troca_tudo($pagina,"#".($cel?$cel:($identificador_auxiliar?$identificador_auxiliar:$identificador))."#".$campo."#",$dado);
		}
	}
	
	if($connect_db)banco_fechar_conexao();
	
	return $pagina;
}

// ================================= B2Make ===============================

function pagina_permissao_menu(){
	global $_PROJETO;
	
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $b2make_permissao_id){
		if($_SESSION[$_SYSTEM['ID']."permissao_id"] == $b2make_permissao_id){
			$found = true;
			break;
		}
	}
	
	if($found){
		return true;
	} else {
		return false;
	}
}

// ================================= Módulos 1.0 ===============================

function pagina_redirecionar($url,$target){
	if(!$target)		$target = "_self";
	
	$saida = '<script language="JavaScript">\n';
	$saida .= 'window.open("'.$url.'","'.$target.'")';
	$saida .= '</script>\n';
	
	return $saida;
}

function paginaTagValor($pagina,$tagInicial,$tagFinal){
	$posInicial = strpos($pagina, $tagInicial);
	$posFinal = strpos($pagina, $tagFinal);
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posInicial = $posInicial+strlen($tagInicial);
		$len = $posFinal-$posInicial;
		
		$valor = substr($pagina,$posInicial,$len);
	}
	
	return $valor;
}

function paginaTrocaVarValor($pagina,$var,$valor){
	$posInicial = strpos($pagina, $var);
	
	if($posInicial === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posInicial+strlen($var);
		
		$parteAnterior = substr($pagina,0,$posInicial);
		$partePosterior = substr($pagina,$posFinal,(strlen($pagina)-$posFinal));
		
		$pagina = $parteAnterior . $valor . $partePosterior;
	}
	
	return $pagina;
}

function paginaTrocaTag($pagina,$tagInicial,$tagFinal,$tagNova){
	$posInicial = strpos($pagina, $tagInicial);
	$posFinal = strpos($pagina, $tagFinal);
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posFinal+strlen($tagFinal);
		
		$parteAnterior = substr($pagina,0,$posInicial);
		$partePosterior = substr($pagina,$posFinal,(strlen($pagina)-$posFinal));
		
		$pagina = $parteAnterior . $tagNova . $partePosterior;
	}
	
	return $pagina;
}

function paginaInserirValor($pagina,$tag,$valor){
	$posInicial = strpos($pagina, $tag);
	
	if($posInicial === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posInicial+strlen($tag);
		
		$parteAnterior = substr($pagina,0,$posInicial);
		$partePosterior = substr($pagina,$posFinal,(strlen($pagina)-$posFinal));
		
		$pagina = $parteAnterior . $valor . $partePosterior;
		
		$pagina = $parteAnterior . $valor . $tag . $partePosterior;
	}
	
	return $pagina;
}

function paginaDeleteTag($pagina,$tagInicial,$tagFinal){
	$posInicial = strpos($pagina, $tagInicial);
	$posFinal = strpos($pagina, $tagFinal);
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posFinal+strlen($tagFinal);
		
		$parteAnterior = substr($pagina,0,$posInicial);
		$partePosterior = substr($pagina,$posFinal,(strlen($pagina)-$posFinal));
		
		$pagina = $parteAnterior . $partePosterior;
	}
	
	return $pagina;
}

function paginaModelo($localModelo){
	$arq = file($localModelo);
	
	for($i=0;$i<count($arq);$i++){
		$pagina .= $arq[$i];
	}
	
	$pagina = paginaDeleteTag($pagina,'<!--!#del#(#!-->','<!--!#del#)#!-->');
	
	return $pagina;
}

function paginaMetaHtml(){
	global $_HTML_META;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	
	$caminho_raiz = $_REQUEST['caminho'];
	
	if($caminho_raiz[strlen($caminho_raiz)-1] != '/' && $caminho_raiz){	
		$caminho_raiz .= '/';
	}
	
	/* if($_SERVER['SERVER_NAME'] != "localhost"){
		$pattern = '/www./i';
		if(preg_match($pattern, $_SYSTEM['DOMINIO']) > 0){
			$dominio = $_SYSTEM['DOMINIO'];
		} else {
			$dominio = 'www.'.$_SYSTEM['DOMINIO'];
		}
	} else { */
		$dominio = $_SYSTEM['DOMINIO'];
	//}
	
	if($_HTML_DADOS['404']){
		$meta_canonical = "	<link rel=\"canonical\" href=\"//".$dominio."/".$_SYSTEM['ROOT']."pagina-404\" />\n";
		$_HTML_META['og:url'] = "//".$dominio."/".$_SYSTEM['ROOT']."pagina-404";
	} else {
		$meta_canonical = "	<link rel=\"canonical\" href=\"//".$dominio."/".$_SYSTEM['ROOT'].$caminho_raiz."\" />\n";
		$_HTML_META['og:url'] = "//".$dominio."/".$_SYSTEM['ROOT'].$caminho_raiz;
	}
	
	$_HTML_META['og:type'] = "article";
	$_HTML_META['og:locale'] = "pt_BR";
	
	$noindex = 'noindex, follow';
	$noindexNofollow = 'noindex, nofollow';
	
	$meta_tag_def = "	<meta #var_name=\"#meta\" content=\"#content\" />\n";
	
	if($_HTML_META){
		foreach($_HTML_META as $meta => $content){
			if($_HTML_DADOS['404']){
				if(
					$content ||
					($meta == "description" && ($_HTML['description'] || $_HTML_DADOS['description'])) ||
					($meta == "keywords" && ($_HTML['keywords'] || $_HTML_DADOS['keywords']))
				){
					$var_name = "name";
					switch($meta){
						case "Content-Language":
						case "Expires":
						case "Pragma":
						case "Cache-Control":
						case "Content-Language":
							$var_name = "http-equiv";
						break;
						case "og:title":
						case "og:url":
						case "og:description":
						case "og:image":
						case "og:type":
						case "og:locale":
							$var_name = "property";
						break;
					}
					
					if($meta == "description") $content = '404';
					if($meta == "keywords") $content = '404,404 page,página não encontrada,page not found';
					if($meta == "robots") $content = $noindexNofollow;
					
					$meta_tag = $meta_tag_def;
					$meta_tag = modelo_var_troca($meta_tag,"#var_name",$var_name);
					$meta_tag = modelo_var_troca($meta_tag,"#meta",$meta);
					$meta_tag = modelo_var_troca($meta_tag,"#content",$content);
					
					if($content != ' ')$meta_tags .= $meta_tag;
				}
			} else {
				if(
					$content ||
					($meta == "description" && ($_HTML['description'] || $_HTML_DADOS['description'])) ||
					($meta == "keywords" && ($_HTML['keywords'] || $_HTML_DADOS['keywords']))
				){
					$var_name = "name";
					switch($meta){
						case "Content-Language":
						case "Expires":
						case "Pragma":
						case "Cache-Control":
						case "Content-Language":
							$var_name = "http-equiv";
						break;
						case "og:title":
						case "og:url":
						case "og:description":
						case "og:image":
						case "og:type":
						case "og:locale":
							$var_name = "property";
						break;
					}
					
					if($meta == "description" && ($_HTML['description'] || $_HTML_DADOS['description'])) $content = $_HTML_DADOS['description'] ? $_HTML_DADOS['description'] : $_HTML['description'];
					if($meta == "keywords" && ($_HTML['keywords'] || $_HTML_DADOS['keywords'])) $content = $_HTML_DADOS['keywords'] ? $_HTML_DADOS['keywords'] : $_HTML['keywords'];
					
					if($meta == "robots" && ($_HTML_DADOS['noindex'] || $_REQUEST['_layouts_teste'])) $content = $noindex;
					if($meta == "robots" && ($_HTML_DADOS['noindexNofollow'])) $content = $noindexNofollow;
					
					$meta_tag = $meta_tag_def;
					$meta_tag = modelo_var_troca($meta_tag,"#var_name",$var_name);
					$meta_tag = modelo_var_troca($meta_tag,"#meta",$meta);
					$meta_tag = modelo_var_troca($meta_tag,"#content",$content);
					
					if($content != ' ')$meta_tags .= $meta_tag;
				}
			}
		}
	}
	
	return $meta_tags.$meta_canonical;
}

function paginaGoogleAnalytics($script){
	global $_SYSTEM;
	global $_PERMISSAO;
	global $_HTML_DADOS;
	global $_GOOGLE_ANALITYCS_STOP;
	
	return $script && !$_SYSTEM['INSTALL'] && !$_PERMISSAO && !$_REQUEST['_layouts_teste'] && !$_GOOGLE_ANALITYCS_STOP ? $script : "";
}

function pagina_variaveis_js(){
	global $_OPCAO;
	global $_AUDIO_PATH;
	global $_MENU_DINAMICO;
	global $_MENU_NUM_PAGINAS;
	global $_IDENTIFICADOR;
	global $_DEBUG;
	global $_LOG;
	global $_ALERTA;
	global $_HTML;
	global $_SYSTEM;
	global $_SCRIPTS_JS;
	global $_STYLESHEETS;
	global $_VARIAVEIS_JS;
	
	$variaveis_js = Array(
		'opcao' => $_OPCAO,
		'audio_path' => $_AUDIO_PATH,
		'menu_dinamico' => $_MENU_DINAMICO,
		'menu_paginas' => $_MENU_NUM_PAGINAS,
		'identificador' => $_IDENTIFICADOR,
		'debug' => $_DEBUG,
		'log' => $_LOG ? $_LOG : null,
		'alerta' => $_ALERTA,
		'addthis' => $_HTML['ADD-THIS'] ? 1 : null,
		'permissao' => $_SESSION[$_SYSTEM['ID']."permissao"] ? true : false,
		'permissao_loja' => $_SESSION[$_SYSTEM['ID']."loja-permissao"] ? true : false,
		'ler_scripts' => $_SCRIPTS_JS ? $_SCRIPTS_JS : false,
		'ler_css' => $_STYLESHEETS ? $_STYLESHEETS : false,
	);
	
	$variaveis_js = json_encode(($_VARIAVEIS_JS?array_merge($variaveis_js, $_VARIAVEIS_JS):$variaveis_js));
	
	return $variaveis_js;
}

function pagina(){
	global $_SYSTEM;
	global $_HTML;
	global $_HTML_META;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_VERSAO;
	global $_HTML_DADOS;
	global $_MOBILE;
	global $_ALERTA;
	global $_AJAX_PAGE;
	global $_DEBUG;
	global $_IDENTIFICADOR;
	global $_MENU_NUM_PAGINAS;
	global $_OPCAO;
	global $_POPUP;
	global $_HTML_POS_PROCESSAMENTO;
	global $_VERSAO_MODULO;
	global $_MENU_DINAMICO;
	global $_AUDIO_PATH;
	global $_MENU_PAGINAS_INICIAL;
	global $_PERMISSAO;
	global $_PROJETO_VERSAO;
	global $_VARIAVEIS_JS;
	global $_PROJETO_DATA;
	global $_PROJETO;
	global $_JANELA;
	global $_VARS;
	global $_MOBILE_2;
	global $_LOG;
	global $_SCRIPTS_JS;
	global $_STYLESHEETS;
	global $_PAGINA_SEM_PROCESSAMENTO;
	global $_MENU_LATERAL;
	global $_DEBUG_CONT;
	global $_B2MAKE_URL;
	
	if($_PAGINA_SEM_PROCESSAMENTO){
		return $_HTML['body'];
	}
	
	if($_PROJETO['facebook_app_ip']){
		$facebook_widget = '
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "http://connect.facebook.net/pt_BR/sdk.js#xfbml=1&appId='.$_PROJETO['facebook_app_ip'].'&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, \'script\', \'facebook-jssdk\'));</script>';
	}
	
	if($_SESSION[$_SYSTEM['ID']."alerta"]){
		pagina_alerta($_SESSION[$_SYSTEM['ID']."alerta"]);
		$_SESSION[$_SYSTEM['ID']."alerta"] = false;
	}
	
	if($_HTML_DADOS['404']){
		$paginaTitulo = $_HTML_META['og:title'] = $_HTML['TITULO_ANTES'].$_HTML['TITULO_SEPARADOR'].'404'.$_HTML['TITULO_SEPARADOR'].'Página não encontrada';
	} else {
		$paginaTitulo = $_HTML_META['og:title'] = $_HTML_DADOS['titulo'] ? $_HTML_DADOS['titulo'] : ( $_HTML['TITULO_ANTES'].$_HTML['TITULO_SEPARADOR'] == $_HTML['titulo'] ? $_HTML['TITULO_ANTES'] : $_HTML['titulo']);
	}
	
	if(!$_AJAX_PAGE){
		if(!$_PERMISSAO && !$_NAO_INCLUDE_HEAD_BODY){
			if($_MOBILE>0)
				$localModelo = $_HTML['MOBILE'];
			else if($_REQUEST['_layouts_teste'])
				$localModelo = $_SYSTEM['PROJETO_PATH']."layout-temp.html";
			else if($_HTML_DADOS['news_layout']){
				$_NAO_INCLUDE_HEAD_BODY = true;
				$localModelo = $_SYSTEM['PROJETO_PATH']."layout_newsletter.html";
			} else if($_HTML_DADOS['no_layout'])
				$localModelo = $_SYSTEM['PROJETO_PATH']."layout_landing_page.html";
			else if($_SYSTEM['PRIMEIRA_EXECUCAO'] && !$_PERMISSAO)
				$localModelo = $_SYSTEM['PATH']."includes/instalacao.html";
			else if(!$_SYSTEM['LAYOUT_PRINCIPAL'] && !$_PERMISSAO)
				$localModelo = $_SYSTEM['PATH']."includes/inicio-provisorio.html";
			else if($_POPUP)
				$localModelo = $_SYSTEM['PATH']."admin/popup.html";
			else if($_HTML['LAYOUT'])
				$localModelo = $_HTML['LAYOUT'];
			else
				$localModelo = "layout.html";
			
			$pagina = modelo_abrir($localModelo);
			
			if($_VARS['background']['ativo']){
				if($_VARS['background']['dados']){
					$dados = explode(';',$_VARS['background']['dados']);
					
					if($dados)
					foreach($dados as $dado){
						$bg = explode(',',$dado);
						if($bg[1] == 'A')
							$background_dinamico[] = $bg[3];
					}
					
					$_VARIAVEIS_JS['background_dinamico'] = $background_dinamico;
					
					preg_match("/<body[^>]*>(.*?)<\/body>/is", $pagina, $matches);
					
					$body = '<body>
	<div id="_background_video_mask">
	</div>
	<div id="_background_video">
		<div id="_background_player"></div>
	</div>
	<div id="_background_site_bg"></div>
	<div id="_background_site_bg_mask"></div>
	<div id="_background_bg_principal">'.$matches[1].'</body>';
					
					$pagina = preg_replace("/<body[^>]*>(.*?)<\/body>/is", $body, $pagina);
				}
			}
			
			$index = modelo_abrir($_SYSTEM['PATH'] . "includes" . $_SYSTEM['SEPARADOR'] . "index.html");
			
			if($_MOBILE){
				$layout_head = modelo_tag_val($index,'<!-- layout_head_mobile < -->','<!-- layout_head_mobile > -->');
				$layout_body = modelo_tag_val($index,'<!-- layout_body_mobile < -->','<!-- layout_body_mobile > -->');
			} else {
				$layout_head = modelo_tag_val($index,'<!-- layout_head < -->','<!-- layout_head > -->');
				$layout_body = modelo_tag_val($index,'<!-- layout_body < -->','<!-- layout_body > -->');
			}
			
			$pagina = modelo_var_in($pagina,"</head>",$layout_head.$_PROJETO['layout-head-in']);
			$pagina = modelo_var_in($pagina,"</body>",$facebook_widget.$layout_body.$_PROJETO['layout-body-in']);
		} else {
			if($_REQUEST['_layouts_teste'])
				$localModelo = $_SYSTEM['PROJETO_PATH']."layout-temp.html";
			else if($_HTML_DADOS['news_layout']){
				$_NAO_INCLUDE_HEAD_BODY = true;
				$localModelo = $_SYSTEM['PROJETO_PATH']."layout_newsletter.html";
			} else if($_HTML_DADOS['no_layout'])
				$localModelo = $_SYSTEM['PROJETO_PATH']."layout_landing_page.html";
			else if($_SYSTEM['PRIMEIRA_EXECUCAO'] && !$_PERMISSAO)
				$localModelo = $_SYSTEM['PATH']."includes/instalacao.html";
			else if(!$_SYSTEM['LAYOUT_PRINCIPAL'] && !$_PERMISSAO)
				$localModelo = $_SYSTEM['PATH']."includes/inicio-provisorio.html";
			else if($_POPUP)
				$localModelo = $_SYSTEM['PATH']."admin/popup.html";
			else if($_HTML['LAYOUT'])
				$localModelo = $_HTML['LAYOUT'];
			else
				$localModelo = "layout.html";
			
			$pagina = modelo_abrir($localModelo);
			
			$menu_user_perfil = '| <a href="!#caminho_raiz#!admin/dados_pessoais/">perfil</a> ';
			
			if(!$_SESSION[$_SYSTEM['ID']."admin"]){
				$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
				
				if(!$permissao_modulos['dados_pessoais']){
					$menu_user_perfil = '';
				}
			}
			
			$pagina = modelo_var_troca($pagina,"!#menu_user_perfil#!",$menu_user_perfil);
		}
		
		if($_MENU_LATERAL) $pagina = modelo_var_troca($pagina,"<!--!#menu-lateral#!-->",	require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'menu/index.php'));
		
		$pagina = modelo_tag_del($pagina,"<!--!#del#(#!-->","<!--!#del#)#!-->");
		
		$pagina = modelo_var_troca($pagina,"<!--!#html_meta#!-->",paginaMetaHtml());
		$pagina = modelo_var_troca($pagina,"<!--!#googleAnalytics#!-->",paginaGoogleAnalytics($_HTML['google-analytics']));
		$pagina = modelo_var_troca($pagina,"!#icon#!","http://" . ( strstr($_SERVER['SERVER_NAME'],"www") ? str_replace("www.","",$_SERVER['SERVER_NAME']) : $_SERVER['SERVER_NAME']) . "/favicon.ico");
		$pagina = modelo_var_troca($pagina,"!#icon2#!","http://" . ( strstr($_SERVER['SERVER_NAME'],"www") ? str_replace("www.","",$_SERVER['SERVER_NAME']) : $_SERVER['SERVER_NAME']) . "/favicon.ico");
		
		$pagina = modelo_var_troca($pagina,"<!--!#paginaCssPadrao#!-->",$_HTML['css_padrao']);
		
		if($_DEBUG_CONT){
			$_DEBUG_CONT = '<div style="width:800px;height:400px;position:fixed;bottom:0px;left:0px;overflow:auto;"><div style="padding:7px;color:red;font-size:25px;background-color:#000;line-height:30px;">Debug Conteiner</div><div>' . $_DEBUG_CONT . '</div></div>';
		}
		
		$pagina = modelo_var_troca($pagina,"!#body#!",$_HTML['body'].$_DEBUG_CONT);
		$pagina = modelo_var_troca($pagina,"!#flashvars#!",$_HTML['flashvars']);
		$pagina = modelo_var_troca($pagina,"!#menu#!",$_HTML['menu']);
		$pagina = modelo_var_troca($pagina,"!#gestor_avatar#!",$_HTML['gestor_avatar']);
		
		$pagina = modelo_var_troca_tudo($pagina,"!#lay_moldura#!",		$_HTML['ADMIN_WIDTH']);
		$pagina = modelo_var_troca($pagina,"!#paginaTitulo#!",		$paginaTitulo);
		$pagina = modelo_var_troca($pagina,"<!--!#paginaCss#!-->",	$_HTML['css']);
		$pagina = modelo_var_troca($pagina,"<!--!#paginaJs#!-->",	$_HTML['js_padrao'].$_HTML['js']);
		
	} else {
		$pagina = $_HTML['body'];
	}
	
	if($_HTML['variaveis'])
	foreach($_HTML['variaveis'] as $variavel => $valor){
		if($valor == ' ') $valor = '';
		$pagina = modelo_var_troca_tudo($pagina,"!#".$variavel."#!",$valor);
	}
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$usuario['nome'])
		$usuario_nome = $usuario['usuario'];
	else
		$usuario_nome = $usuario['nome'];
	
	if($usuario_nome){
		$usuario_arr = explode(' ',$usuario_nome);
		$usuario_nome = $usuario_arr[0];
	}
	
	$pagina = modelo_var_troca($pagina,"!#user#!",($_SYSTEM['USER_NOME_NO_DEFAULT']?"":"Usuário: ").$usuario_nome);
	
	$variaveis_js = Array(
		'mobile' => $_MOBILE_2,
		'opcao_atual' => $_OPCAO,
		'log' => $_LOG ? $_LOG : null,
		'audio_path' => $_AUDIO_PATH,
		'menu_dinamico_inicial' => $_MENU_DINAMICO,
		'menu_dinamico' => ($_MENU_PAGINAS_INICIAL?$_MENU_PAGINAS_INICIAL:$_MENU_NUM_PAGINAS),
		'menu_paginas' => $_MENU_NUM_PAGINAS,
		'identificador' => $_IDENTIFICADOR,
		'site_teste' => $_REQUEST['_layouts_teste'],
		'site_raiz' => '/'.$_SYSTEM['SITE_ROOT'],
		'addthis' => $_HTML['ADD-THIS'] ? 1 : null,
		'permissao' => $_SESSION[$_SYSTEM['ID']."permissao"] ? true : false,
		'permissao_loja' => $_SESSION[$_SYSTEM['ID']."loja-permissao"] ? true : false,
		'permissao_menu' => $_SESSION[$_SYSTEM['ID']."permissao"] ? pagina_permissao_menu() : false,
		'usuario_nome' => $_SESSION[$_SYSTEM['ID']."usuario"] ? $_SESSION[$_SYSTEM['ID']."usuario"]["nome"] : "Anônimo",
		'login_logout_url' => ($_PROJETO['login_logout_url']?$_PROJETO['login_logout_url']:'meus-pedidos'),
		'ler_scripts' => $_SCRIPTS_JS ? $_SCRIPTS_JS : false,
		'ler_css' => $_STYLESHEETS ? $_STYLESHEETS : false,
	);
	
	if($_HTML_DADOS['noindex'])$variaveis_js['noindex'] = true;
	if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode'])$variaveis_js['dark_mode'] = true;
	
	$pagina = modelo_var_troca_tudo($pagina,"!#data#!",date("Y"));
	$pagina = modelo_var_troca_tudo($pagina,"!#data_projeto#!",$_PROJETO_DATA);
	$pagina = modelo_var_troca_tudo($pagina,"!#caminho_raiz_dominio#!",'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT']);
	$pagina = modelo_var_troca_tudo($pagina,"!#caminho_raiz#!",$_CAMINHO_RELATIVO_RAIZ);
	$pagina = modelo_var_troca_tudo($pagina,"!#caminho_projeto#!",$_SYSTEM['PROJETO_ROOT']);
	$pagina = modelo_var_troca_tudo($pagina,"!#caminho_tema#!",$_SYSTEM['PROJETO_ROOT']);
	$pagina = modelo_var_troca_tudo($pagina,"!#versao-sistema#!",$_VERSAO);
	$pagina = modelo_var_troca_tudo($pagina,"!#versao-modulo#!",$_VERSAO_MODULO);
	$pagina = modelo_var_troca_tudo($pagina,"!#versao-projeto#!",$_PROJETO_VERSAO);
	$pagina = modelo_var_troca_tudo($pagina,"!#versao#!","?v=".$_VERSAO);
	$pagina = modelo_var_troca_tudo($pagina,"!#b2make-url#!",$_B2MAKE_URL);
	
	$pagina = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $pagina);
	
	if(!$_AJAX_PAGE){
		$pagina = html_layout($pagina);
		
		if($_HTML_POS_PROCESSAMENTO){
			foreach($_HTML_POS_PROCESSAMENTO as $var => $val){
				$pagina = modelo_var_troca_tudo($pagina,$var,$val);
			}
		}
	} else {
		if($_HTML_POS_PROCESSAMENTO){
			foreach($_HTML_POS_PROCESSAMENTO as $var => $val){
				$pagina = modelo_var_troca_tudo($pagina,$var,$val);
			}
		}
		
		$pagina = Array(
			'opcao' => $_OPCAO,
			'audio_path' => $_AUDIO_PATH,
			'menu_dinamico' => $_MENU_DINAMICO,
			'menu_paginas' => $_MENU_NUM_PAGINAS,
			'identificador' => $_IDENTIFICADOR,
			'debug' => $_DEBUG,
			'log' => $_LOG ? $_LOG : null,
			'alerta' => $_ALERTA,
			'page' => $pagina,
			'titulo' => $paginaTitulo,
			'addthis' => $_HTML['ADD-THIS'] ? 1 : null,
			'permissao' => $_SESSION[$_SYSTEM['ID']."permissao"] ? true : false,
			'permissao_loja' => $_SESSION[$_SYSTEM['ID']."loja-permissao"] ? true : false,
			'ler_scripts' => $_SCRIPTS_JS ? $_SCRIPTS_JS : false,
			'ler_css' => $_STYLESHEETS ? $_STYLESHEETS : false,
		);
		
		$pagina = json_encode(($_VARIAVEIS_JS?array_merge($pagina, $_VARIAVEIS_JS):$pagina));
	}
	
	if($_MOBILE){
		$mobile_variaveis_js = Array(
			'opcao' => $_OPCAO,
			'audio_path' => $_AUDIO_PATH,
			'menu_dinamico' => $_MENU_DINAMICO,
			'menu_paginas' => $_MENU_NUM_PAGINAS,
			'identificador' => $_IDENTIFICADOR,
			'debug' => $_DEBUG,
			'log' => $_LOG ? $_LOG : null,
			'alerta' => $_ALERTA,
			'titulo' => $paginaTitulo,
			'addthis' => $_HTML['ADD-THIS'] ? 1 : null,
			'permissao' => $_SESSION[$_SYSTEM['ID']."permissao"] ? true : false,
			'permissao_loja' => $_SESSION[$_SYSTEM['ID']."loja-permissao"] ? true : false,
		);
		
		$mobile_variaveis_js = json_encode(($_VARIAVEIS_JS?array_merge($mobile_variaveis_js, $_VARIAVEIS_JS):$mobile_variaveis_js));
		
		$_SESSION[$_SYSTEM['ID'].'mobile_variaveis_js'] = $mobile_variaveis_js;
	}
	
	$variaveis_js = json_encode(($_VARIAVEIS_JS?array_merge($variaveis_js, $_VARIAVEIS_JS):$variaveis_js));
	
	$pagina = modelo_var_troca_tudo($pagina,"!#variaveis_js#!",$variaveis_js);
	
	return $pagina;
}

?>