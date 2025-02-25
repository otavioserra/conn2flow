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

function projeto_categorias_pagina($params){
	global $_PROJETO;
	global $_SYSTEM;
	
	$pagina = $params['pagina'];
	$resultado = $params['resultado'];
	
	if($_PROJETO['CONTEUDO_CALLBACK_ID_PAI'] == $resultado['id_conteudo_pai']){
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'texto',
				'texto2',
			))
			,
			"conteudo",
			"WHERE id_externo='rodape-acima'"
		);
		
		if($resultado2){
			$pagina .= '<div id="b2make-acima-rodape-1">'.$resultado2[0]['texto'].'</div>';
			$pagina .= '<div id="b2make-acima-rodape-2">'.$resultado2[0]['texto2'].'</div>';
		}
		
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.titulo',
				't2.texto',
				't2.imagem_pequena',
			))
			,
			"conteudo as t1,conteudo as t2",
			"WHERE t1.id_externo='assets'"
			." AND t1.id_conteudo=t2.id_conteudo_pai"
			." AND t2.status='A'"
		);
		
		$pagina .= '	<div class="b2-site-cont">';
		if($resultado2)
		foreach($resultado2 as $res){
			$pagina .= '		<div class="b2-site-cont-col">
			<div class="b2-site-cont-col-imagem_pequena b2-titulo-4"><img src="/'.$_SYSTEM['ROOT'].$res['t2.imagem_pequena'].'"></div>
			<div class="b2-site-cont-col-titulo b2-titulo-4">'.$res['t2.titulo'].'</div>
			<div class="b2-site-cont-col-texto b2-titulo-4">'.$res['t2.texto'].'</div>
		</div>';
		}
		$pagina .= '</div>';
		
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'texto',
			))
			,
			"conteudo",
			"WHERE id_externo='acima-rodape-2'"
		);
		
		if($resultado2){
			$pagina .= '<div id="b2make-acima-rodape-3">'.$resultado2[0]['texto'].'</div>';
		}
		
		$resultado3 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'titulo',
				'id_conteudo',
				'identificador',
				'caminho_raiz',
			))
			,
			"conteudo",
			"WHERE id_conteudo_pai='".$resultado['id_conteudo_pai']."'"
			." AND status='A'"
			." ORDER BY ordem ASC,titulo ASC"
		);
		
		$menu = '<div id="b2make-menu-categorias">';
		$menu .= '	<div id="b2make-menu-categorias-prev"></div>';
		$menu .= '	<div id="b2make-menu-categorias-next"></div>';
		
		if($resultado3)
		foreach($resultado3 as $res){
			$menu .= '	<a href="/'.$_SYSTEM['ROOT'].$res['caminho_raiz'].$res['identificador'].'" class="b2make-menu-categoria'.($res['id_conteudo'] == $resultado['id_conteudo'] ? ' b2make-categoria-atual':'').'">'.$res['titulo'].'</a>';
		}
		
		$menu .= '</div>';
		
		$pagina = modelo_var_troca($pagina,"#menu-categorias#",$menu);
	}
	
	return $pagina;
}

function projeto_modificar_campos($params){
	$id = $params['id'];
	$campos = $params['campos'];
	$parametros = $params['parametros'];
	
	switch($id){
		case 'cadastro_banco':
		
		break;
		case 'contato_banco':
		
		break;
		case 'cadastrar_email':
		
		break;
		case 'procurar':
		
		break;
		case 'conteudo':
		
		break;
		case 'noticias_lista_dinamico':
		
		break;
		case 'blog_dinamico':
		
		break;
		
	}
	
	$saida = Array(
		'campos' => $campos,
		'parametros' => $parametros,
	);
	
	return $saida;
}

function projeto_modelos($params){
	global $_SYSTEM;
	global $_MODELO_MAIS_PAGINAS;
	global $_VARIAVEIS_JS;
	global $_CAMINHO;
	
	if($_CAMINHO[1] == 'template'){
		$template_id = $_CAMINHO[2];
	}
	
	$modelo_por_pagina = 16;
	$pagina = 1;
	
	if($_SESSION[$_SYSTEM['ID']."b2make-segmentos"])
		$id_site_segmentos = $_SESSION[$_SYSTEM['ID']."b2make-segmentos"];
	
	if($_REQUEST['segmento_id']){
		$id_site_segmentos = $_REQUEST['segmento_id'];
	}
	if($_REQUEST['pagina']){
		$pagina = (int)$_REQUEST['pagina'];
	}

	if(!$params['modelos']){
		$site_segmentos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_segmentos',
				'nome',
				'descricao',
				'imagem',
				'imagem_versao',
			))
			,
			"site_segmentos",
			"WHERE status='A'"
			." AND id!='padrao'"
			." ORDER BY nome ASC"
		);
		
		$modulo .= '<div id="b2make-templates-title">ESCOLHA O TEMPLATE QUE VOCÊ DESEJA</div>';
		$modulo .= '<select id="b2make-segmentos">';
		$modulo .= '	<option value="-1">Todos</option>';
		
		if($site_segmentos)
		foreach($site_segmentos as $segmento){
			$modulo .= '	<option value="'.$segmento['id_site_segmentos'].'"'.($id_site_segmentos == $segmento['id_site_segmentos'] ? ' selected="selected"' : '').'>'.$segmento['nome'].'</option>';
		}
		
		$modulo .= '</select>
		<div class="clear"></div>';
	}
	
	
	
	$extra_templates = "WHERE status='A'"
	." AND id!='padrao'"
	.(
		$id_site_segmentos && $id_site_segmentos != '-1' && $id_site_segmentos != '1'
			? " AND id_site_segmentos='".$id_site_segmentos."' ORDER BY id_site_templates DESC"
			: " ORDER BY id_site_templates DESC"
	);
	
	$total_modelos = banco_total_rows(
		"site_templates",
		$extra_templates
	);
	
	$total_paginas = ceil(($total_modelos + 1) / $modelo_por_pagina);
	
	if($total_paginas > $pagina){
		if($params['ajax'])
			$_MODELO_MAIS_PAGINAS = true;
		else
			$_VARIAVEIS_JS['modelo_mais_paginas'] = 'sim';
	}
	
	$site_templates = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_templates',
			'nome',
			'descricao',
			'imagem',
			'imagem_versao',
			'id',
		))
		,
		"site_templates",
		$extra_templates
		.($pagina == 1 ? " LIMIT 0,".($modelo_por_pagina-1) : " LIMIT ".(($modelo_por_pagina * ($pagina - 1)) - 1).",".$modelo_por_pagina)
	);
	
	if(!$params['modelos'])$modulo .= '<div id="b2make-templates">';
	
	if($pagina == 1) $modulo .= '<div data-id="1" data-nome="Em Branco" class="b2make-templates-cont">'.
		'<div class="b2make-templates-imagem" style="background-image:url('.'//'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'includes/autenticar/images/b2make-signup-sem-imagem.png)">
			<div class="b2make-templates-imagem-mask"></div>
			<div class="b2make-templates-escolher" style="top:70px;">Escolher</div>
		</div>'.
	'</div>';
	
	if($site_templates)
	foreach($site_templates as $template){
		$modulo .= '<div data-id="'.$template['id_site_templates'].'" data-identificador="'.$template['id'].'" data-nome="'.($params['ajax']?$template['nome']:$template['nome']).'" class="b2make-templates-cont">'.
			'<div class="b2make-templates-imagem" style="background-image:url('.'//'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].($template['imagem'] ? $template['imagem'].'?v='.$template['imagem_versao'] : 'files/projeto/images/b2make-template-branco.png').')">
				<div class="b2make-templates-imagem-mask"></div>
				<div class="b2make-templates-ver">Ver</div>
				<div class="b2make-templates-escolher">Escolher</div>
			</div>'.
		'</div>';
	}
	
	if(!$params['modelos'])$modulo .= '</div>
	<div class="clear"></div>
	<div id="b2make-templates-mais-opcoes">MAIS OPÇÕES</div>';
	
	if(!$params['widget'])$modulo = '	<div id="b2make-page-templates">
		'.$modulo.'
		
	</div>';
	
	$site_templates = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_templates',
			'nome',
		))
		,
		"site_templates",
		$extra_templates
	);
	
	if($site_templates)
	foreach($site_templates as $template){
		if($template_id == $template['id_site_templates']){
			$template_name = $template['nome'];
		}
	}
	
	if($template_id){
		$_VARIAVEIS_JS['template_id'] = $template_id;
		$_VARIAVEIS_JS['template_name'] = $template_name;
	}
	
	return $modulo;
}

function projeto_planos($params = false){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_VARIAVEIS_JS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_CAMINHO[1] == 'plan'){
		$plan_name = strtoupper($_CAMINHO[2]);
	}
	
	$planos = $_SYSTEM['B2MAKE_PLANOS'];
	
	if($planos)
	foreach($planos as $id => $plano){
		$nome = $plano['nome'];
		$nome_mostrar = $plano['nome_mostrar'];
		$detalhes = $plano['detalhes'];
		$not_show = $plano['not-show'];
		
		if(!$not_show){
			if($id == '1'){
				$preco = 'GRATUITO';
				$botao = 'COMECE J&Aacute;';
			} else {
				$preco = 'R$ '.preparar_float_4_texto($plano['valor']).'<br><span>mensal</span>';
				$botao = 'CONTRATE';
			}
			
			$planos_html .= '		<div class="b2make-signup-plans-cont">
				<div class="b2make-spc-nome">'.$nome_mostrar.'</div>
				<div class="b2make-spc-detalhes">'.$detalhes.'</div>
				<div class="b2make-spc-preco">'.$preco.'</div>
				<div class="b2make-spc-botao" data-id="'.$id.'" data-nome="'.$nome.'">'.$botao.'</div>
			</div>';
		}
		
		if($plan_name == strtoupper($nome)){
			$plan_id = $id;
		}
	}
	
	if(!$widget)$planos_html = '	<div id="b2make-page-plans">
		'.$planos_html.'
		
	</div>';
	
	if($plan_id){
		$_VARIAVEIS_JS['plan_id'] = $plan_id;
		$_VARIAVEIS_JS['plan_name'] = $plan_name;
	}
	
	return $planos_html;
}

function projeto_modulos($params){
	global $_SYSTEM;
	
	$modulo_tag = $params['modulo_tag'];
	$modulo = $params['modulo'];
	
	switch($modulo_tag){
		case '#segmentos#':
			redirecionar('templates');	
		break;
		case '#templates#':
			$_SESSION[$_SYSTEM['ID']."b2make-change-template"] = true;
			
			$modulo = projeto_modelos(Array(
				'modulo' => true
			));
		break;
		case '#planos#':
			$_SESSION[$_SYSTEM['ID']."b2make-change-template"] = true;
			
			$modulo = projeto_planos(Array(
				'modulo' => true
			));
		break;
		
	}
	
	return $modulo;
}

function projeto_pagina_nao_encontrada($params){
	$pagina = $params['pagina'];
	
	return $pagina;
}

function projeto_pagina_inicial($params){
	global $_VARIAVEIS_JS; 
	global $_SYSTEM; 
	global $_CONTEUDO_ID_AUX; 
	global $_HTML_DADOS; 
	global $_HTML; 
	
	$pagina = $params['pagina'];
	
	$_CONTEUDO_ID_AUX = 'b2make-home';
	
	$parametros = Array(
		'modulos_tags' => $_MODULOS_TAGS,
		'forcar_id' => $forcar_id,
		'info_abaixo' => $modulo,
		'forcar_h1' => $forcar_h1,
		'forcar_h1_to_h2' => $forcar_h1_to_h2,
		'forcar_sub_to_tit' => $forcar_sub_to_tit,
		'nao_mostrar_filhos' => $nao_mostrar_filhos,
		'modulo_noticias' => $modulo_noticias,
	);
	
	$retorno = conteudo_mostrar($parametros);
	$pagina = $retorno['pagina'];
	
	$_HTML_DADOS['titulo'] = $_HTML['titulo'];
	
	return $pagina;
}

function projeto_pagina_layout($params){
	$pagina = $params['pagina'];
	
	return $pagina;
}

function projeto_layout($params){
	$pagina = $params['pagina'];
	
	$resultado2 = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.titulo',
			't2.texto',
			't2.imagem_pequena',
		))
		,
		"conteudo as t1,conteudo as t2",
		"WHERE t1.id_externo='rodape-links'"
		." AND t1.id_conteudo=t2.id_conteudo_pai"
		." AND t2.status='A'"
	);
	
	$rodape .= '	<div class="b2-site-rodape">';
	if($resultado2)
	foreach($resultado2 as $res){
		$rodape .= '		<div class="b2-site-rodape-col">
		<div class="b2-site-rodape-col-titulo b2-titulo-4">'.$res['t2.titulo'].'</div>
		<div class="b2-site-rodape-col-texto b2-titulo-4">'.$res['t2.texto'].'</div>
	</div>';
	}
	$rodape .= '</div>';
	
	$pagina = modelo_var_troca($pagina,"#rodape#",$rodape);
	
	return $pagina;
}

function projeto_xml($params){
	$entrada = $params['entrada'];
	
	$saida = $entrada;
	
	return $saida;
}

function projeto_ajax($params){
	global $_SYSTEM;
	global $_MODELO_MAIS_PAGINAS;
	
	$entrada = $params['entrada'];
	$saida = $params['saida'];
	
	/* if($_REQUEST["opcao"] == 'b2make-segmentos'){
		if($_REQUEST["id"]){
			$id = $_REQUEST["id"];
			
			$_SESSION[$_SYSTEM['ID']."b2make-segmentos"] = $id;
			
			$saida = Array(
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'IdNaoInformado1'
			);
		}
		
		$saida = json_encode($saida);
	} */

	if($_REQUEST["opcao"] == 'b2make-templates'){
		if($_REQUEST["id"]){
			$id = $_REQUEST["id"];
			
			$_SESSION[$_SYSTEM['ID']."b2make-templates"] = $id;
			
			$saida = Array(
				'status' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'IdNaoInformado2'
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if(
		$_REQUEST["opcao"] == 'b2make-segmentos' ||
		$_REQUEST["opcao"] == 'b2make-modelos-mais'
	){
		$modelos = projeto_modelos(Array(
			'modelos' => true,
			'ajax' => true,
		));
		
		$saida = Array(
			'status' => 'Ok',
			'modelos' => $modelos
		);
		
		if($_MODELO_MAIS_PAGINAS){
			$saida['mais_paginas'] = 'sim';
		}
		
		$saida = json_encode($saida);
	}

	return $saida;
}

function projeto_main_opcao(){
	global $_OPCAO;
	global $_CAMINHO;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_VARIAVEIS_JS;
	global $_SYSTEM;
	
	$id = $_CAMINHO[0];
	
	if(!$_B2MAKE_PAGINA_LOCAL){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_host',
			))
			,
			"host",
			"WHERE user_host='".$id."'"
			." AND status='A'"
		);
		
		if($resultado){
			if($_CAMINHO[1]){
				$count = 0;
				foreach($_CAMINHO as $cam){
					if($count > 0){
						$caminho .= '/' . $cam;
					}
					
					$count++;
				}
			}
			
			echo file_get_contents('http://'.$id.'.b2make.com'.$caminho);
			exit;
		}
	}
	
	if($id == 'blog'){
		$identificador = $_CAMINHO[1];
		
		if($identificador){
			$_VARIAVEIS_JS['facebook_coments_url'] = 'https://'.$_SYSTEM['DOMINIO'].$_SYSTEM['ROOT'].'blog/'.$identificador;
		}
	}
	
	switch($_OPCAO){
		//case 'opcao':					$saida = opcao(); break;
		default: 						$saida = conteudo();
	}
	
	return $saida;
}

function projeto_main($params){
	global $_VARIAVEIS_JS; // Variável global de passagem de dados para o JS
	
	$entrada = $params['entrada'];
	
	$saida = $entrada;
	
	return $saida;
}

?>