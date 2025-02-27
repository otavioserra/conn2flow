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

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"eventos";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Eventos.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'].
$_JS['CodeMirror'].
$_JS['prettyPhoto'].
'<script type="text/javascript" src="jquery.tabify.js?v='.$_VERSAO_MODULO.'"></script>'."\n".
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'conteudo';
$_LISTA['tabela2']['nome']			=	'conteudo_permissao';
$_LISTA['tabela']['campo']			=	'titulo';
$_LISTA['tabela']['id']				=	'id_'.'conteudo';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Eventos';
$_LISTA['ferramenta_unidade']		=	'esse evento';

$_LISTA['campos'] = Array(
	'identificador',
	'titulo',
	'titulo_img',
	'sub_titulo',
	'keywords',
	'texto',
	'imagem_pequena',
	'imagem_grande',
	'musica',
	'link_externo',
	'data',
	'galeria',
	'parametros',
	'videos_youtube',
	'identificador_auxiliar',
	'galeria_grupo',
	'videos',
	'videos_grupo',
	'texto2',
	'author',
);

$_LISTA['campos_sem_permissao'] = Array(
	'conteiner_posicao_x',
	'conteiner_posicao_y',
);

$_LISTA['campos_extra'] = Array(
	'addthis',
	'no_search',
	'no_robots',
	'layout_status',
	'no_layout',
	'galeria_todas',
	'videos_todas',
	'conteudos_relacionados',
	'menu_principal',
	'menu_sitemap',
	'titulo_img_recorte_y',
	'imagem_pequena_recorte_y',
	'imagem_grande_recorte_y',
	'conteiner_posicao',
	'comentarios',
);

$_LISTA['campos_extra_texto'] = Array(
	'layout',
	'titulo_img_width',
	'titulo_img_height',
	'titulo_img_filters',
	'titulo_img_mask',
	'imagem_pequena_width',
	'imagem_pequena_height',
	'imagem_pequena_filters',
	'imagem_pequena_mask',
	'imagem_grande_width',
	'imagem_grande_height',
	'imagem_grande_filters',
	'imagem_grande_mask',
	'conteiner_posicao_efeito',
	'conteiner_posicao_tempo',
	'cont_padrao_posicao_x',
	'cont_padrao_posicao_y',
);

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções de assistência

function mp3_player($params){
	global $_LOCAL_ID;
	global $_HTML;
	global $_SYSTEM;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_VERSAO_MODULO;
	
	$mp3_id = $_SESSION[$_SYSTEM['ID']."mp3_id"];
	$mudou_valor = $_SESSION[$_SYSTEM['ID']."mudou_valor"];
	$mudou_musica = $mudou_valor['musica'];
	$mudou_valor['musica'] = false;
	$_SESSION[$_SYSTEM['ID']."mudou_valor"] = $mudou_valor;
	
	$path = $_HTML['separador'].'includes/flash/mp3_player/';
	$id = rand(1,100000);
	
	if($mudou_musica){
		$mudou_musica = '?alteracao='.$id.'&v='.$_VERSAO_MODULO;
	} else {
		$mudou_musica = '?v='.$_VERSAO_MODULO;
	}
	
	$mp3_id_var = 'mp3_id='.$mp3_id;
	
	$width = 300;
	$height = 120;
	
	return '
<div class="mp3_palyer">
<object id="_mp3_palyer'.$id.'" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$width.'" height="'.$height.'">
  <param name="movie" value="'.$path.'MP3Player.swf'.$mudou_musica.'" />
  <param name="FlashVars" value="'.$mp3_id_var.'" />
  <param name="quality" value="high" />
  <param name="wmode" value="opaque" />
  <param name="swfversion" value="6.0.65.0" />
  <!-- This param tag prompts users with Flash Player 6.0 r65 and higher to download the latest version of Flash Player. Delete it if you don&rsquo;t want users to see the prompt. -->
  <param name="expressinstall" value="'.$_CAMINHO_RELATIVO_RAIZ.'Scripts/expressInstall.swf" />
  <!-- Next object tag is for non-IE browsers. So hide it from IE using IECC. -->
  <!--[if !IE]>-->
  <object type="application/x-shockwave-flash" data="'.$path.'MP3Player.swf'.$mudou_musica.'" width="'.$width.'" height="'.$height.'">
    <!--<![endif]-->
    <param name="FlashVars" value="'.$mp3_id_var.'" />
	<param name="quality" value="high" />
    <param name="wmode" value="opaque" />
    <param name="swfversion" value="6.0.65.0" />
    <param name="expressinstall" value="'.$_CAMINHO_RELATIVO_RAIZ.'Scripts/expressInstall.swf" />
    <!-- The browser displays the following alternative content for users with Flash Player 6.0 and older. -->
    <div>
      <h4>Content on this page requires a newer version of Adobe Flash Player.</h4>
      <p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" width="112" height="33" /></a></p>
    </div>
    <!--[if !IE]>-->
  </object>
  <!--<![endif]-->
</object>
</div>';
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

function galerias($id){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_CAMINHO;
	global $_MENU_DINAMICO;
	global $_MENU_NAO_MUDAR;
	global $_OPCAO;
	
	$_MENU_NAO_MUDAR = true;
	
	$_INTERFACE['forcar_inicio'] = true;
	
	$imagens = interface_layout(Array(
		'opcao' => 'galerias_imagens_pretty_photo',
		'ferramenta' => 'imagens dessa galeria',
		'forcar_width' => 75,
		'forcar_height' => 75,
		'frame_width' => '770',
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
	));
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- galerias_conteiner < -->','<!-- galerias_conteiner > -->');
	
	$pagina = modelo_var_troca($pagina,"#imagens#",$imagens);
	
	return $pagina;
}

function galerias_videos($id){
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
	);
	
	if($_PROJETO['admin_conteudo']){
		if($_PROJETO['admin_conteudo']['galerias_videos']){
			$galerias_videos = $_PROJETO['admin_conteudo']['galerias_videos'];
			
			foreach($galerias_videos as $param => $val){
				$params[$param] = $val;
			}
		}
	}
	
	$videos = interface_layout($params);
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- galerias_videos_conteiner < -->','<!-- galerias_videos_conteiner > -->');
	
	$pagina = modelo_var_troca($pagina,"#videos#",$videos);
	
	return $pagina;
}

function videos($videos_str,$id,$versao){
	global $_SYSTEM;
	
	$parametros = Array(
		'frame_width' => false,
		'frame_margin' => '7',
		'imagem_pequena' => 'local_mini',
		'imagem_grande' => 'local_original',
		'menu_paginas_id' => 'videos_'.$id,
		'num_colunas' => 8,
		'line_height' => false,
		'titulo_class' => 'fotos-galerias-titulo',
		'link_class' => 'fotos-imagens-link',
		'link_class_ajuste_margin' => 4,
		'class' => false,
	);
	
	$link_class = $parametros['link_class'];
	$link_class_ajuste_margin = $parametros['link_class_ajuste_margin'];
	$imagem_pequena = $parametros['imagem_pequena'];
	$imagem_grande = $parametros['imagem_grande'];
	$titulo_class = $parametros['titulo_class'];
	$frame_width = $parametros['frame_width'];
	$frame_margin = $parametros['frame_margin'];
	$line_height = $parametros['line_height'];
	$class = $parametros['class'];
	$num_colunas = $parametros['num_colunas'];
	$menu_paginas_id = $parametros['menu_paginas_id'];
	
	$videos = explode(',',$videos_str);
	
	if($videos){
		if(!$class)$class = 'imagem';
		$count = 0;
		foreach($videos as $dados){
			if($dados){
				$count++;
				$titulo = 'Vídeo '.$count;
				
				$imagem_path = 'files/videos_youtube'.$id.'_'.$count.'.jpg';
				
				if($imagem_path){
					$image_info = imagem_info($imagem_path);
					
					$width = $image_info[0];
					$height = $image_info[1];
					
					$imagem = html(Array(
						'tag' => 'img',
						'val' => '',
						'attr' => Array(
							'src' => '/'.$_SYSTEM['ROOT'].$imagem_path.$versao,
							'alt' => $titulo,
							'width' => $width,
							'height' => $height,
							'border' => '0',
							'class' => $link_class,
						)
					));
					
					$imagem = html(Array(
						'tag' => 'a',
						'val' => $imagem,
						'attr' => Array(
							'href' => 'http://www.youtube.com/watch?v='.$dados,
							'rel' => 'prettyPhoto['.$menu_paginas_id.']',
							'style' => 'width: '.$width.'px; height: '.($height+$link_class_ajuste_margin).'px; display:block;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
						)
					));
					
					$imagem = html(Array(
						'tag' => 'li',
						'val' => $imagem,
						'attr' => Array(
							'style' => ($frame_width ? 'width: '.floor($frame_width/$num_colunas).'px; margin: 0px auto '.(2*$frame_margin).'px auto;':'margin: 0px '.$frame_margin.'px '.(2*$frame_margin).'px '.$frame_margin.'px;').' float: left; text-align: center;',
						)
					));

					$imagens .= "\n	".$imagem;
				}
			}
		}
		
		$imagens = html(Array(
			'tag' => 'ul',
			'val' => $imagens,
			'attr' => Array(
				'class' => 'gallery clearfix',
				'style' => 'list-style-type: none; margin: 10px 0px 0px 0px; padding: 0px;',
			)
		));
	}
	
	return $imagens."<div class=\"clear\"></div>\n";
}

// Funções do Sistema

function modulos_sistema_inativos(){
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	$modulos_aux = banco_select_name
	(
		banco_campos_virgulas(Array(
			'caminho',
		))
		,
		"modulo",
		"WHERE status='I'"
	);
	
	if($modulos_aux)
	foreach($modulos_aux as $modulo){
		$modulos[$modulo['caminho']] = true;
	}
	
	return $modulos;
}

function identificador_unico($id,$num,$id_conteudo,$id_auxiliar){
	global $_PALAVRAS_RESERVADAS;
	
	$conteudo = banco_select
	(
		"id_conteudo"
		,
		"conteudo",
		"WHERE ".($id_auxiliar?"identificador_auxiliar":"identificador")."='".($num ? $id.'-'.$num : $id)."'"
		.($id_conteudo?" AND id_conteudo!='".$id_conteudo."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1,$id_conteudo,$id_auxiliar);
	} else {
		if($_PALAVRAS_RESERVADAS)
		foreach($_PALAVRAS_RESERVADAS as $palavra){
			if($palavra == $id){
				$num++;
				break;
			}
		}
		
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador($id,$id_conteudo = false,$id_auxiliar = false){
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
		
		return identificador_unico($id,$num,$id_conteudo,$id_auxiliar);
	} else {
		return identificador_unico($id,0,$id_conteudo,$id_auxiliar);
	}
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
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = 'ordem';
	$tabela_campos[] = 'tipo';
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	if($_SYSTEM['INSTALL'])$tabela_campos[] = 'identificador';
	$tabela_campos[] = 'hits';
	$tabela_campos[] = 'data';
	
	if($_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"]){
		$titulo_raiz_navegacao = 'Ir a raiz dos conteúdos';
		$titulo_navegacao = 'Ir na lista de conteúdos ';
		$conteudo_arvore_2 = $_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"];
		
		//$informacao_acima = '<div class="lista_header">'.htmlA('?opcao=raiz','raiz',$target,$id,' title="'.$titulo_raiz_navegacao.'"') . ' / ';
		
		if($conteudo_arvore_2)
		foreach($conteudo_arvore_2 as $filho){
			$count++;
			//$informacao_acima .= htmlA('?opcao=lista_conteudo&id='.$filho['id'],$filho['nome'],$target,$id,' title="'.$titulo_navegacao.$filho['nome'].'"') . (count($conteudo_arvore_2) != $count ? ' / ' : '</div>');
		}
	}
	
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
	if($_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] || operacao('modificar_raiz')){
		if(operacao('adicionar')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=add_conteudo', // link da opção
				'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
				'img_coluna' => 3, // Coluna background image
				'img_linha' => 1, // Linha background image
				'name' => 'Adicionar', // Nome do menu
			);
		}
	}
	if(operacao('configuracao')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=permissao_lista&id='.($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0'), // link da opção
			'title' => 'Configuração do Conjunto de Conteúdos', // título da opção
			'img_coluna' => 4, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Configuração', // Nome do menu
		);
	}
	
	if(
		$_INTERFACE_OPCAO == 'editar' &&
		$_REQUEST["id"]
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		/* if(operacao('editar')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=modificar_caminho_raiz&id=#id', // link da opção
				'title' => 'Mudar Raiz d' . $_LISTA['ferramenta_unidade'], // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'change-root-grande.png', // caminho da imagem
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Mudar Raiz', // Nome do menu
			);
		} */
		
		if($_INTERFACE['local'] == 'permissao'){
			if(operacao('ver') && !operacao('editar')){
				$menu_principal[] = Array( // array com todos os campos das opções do menu
					'url' => $_URL . '?opcao=ver&id=#id', // link da opção
					'title' => 'Ver ' . $_LISTA['ferramenta_unidade'], // título da opção
					'img_coluna' => 3, // Coluna background image
					'img_linha' => 2, // Linha background image
					'name' => 'Ver', // Nome do menu
				);
			}
			if(operacao('editar')){
				$menu_principal[] = Array( // array com todos os campos das opções do menu
					'url' => $_URL . '?opcao=editar&id=#id', // link da opção
					'title' => 'Editar ' . $_LISTA['ferramenta_unidade'], // título da opção
					'img_coluna' => 5, // Coluna background image
					'img_linha' => 1, // Linha background image
					'name' => 'Editar', // Nome do menu
				);
			}
		} else {
			if(operacao('configuracao')){
				$menu_principal[] = Array( // array com todos os campos das opções do menu
					'url' => $_URL . '?opcao=permissao_conteudo&id=#id', // link da opção
					'title' => 'Configuração d' . $_LISTA['ferramenta_unidade'], // título da opção
					'img_coluna' => 4, // Coluna background image
					'img_linha' => 1, // Linha background image
					'name' => 'Configuração', // Nome do menu
				);
			}
		}
		
		if($_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] || operacao('modificar_raiz')){
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
	}
	
	if(
		$_INTERFACE_OPCAO == 'lista'
	){
		//if(operacao('configuracao')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=zerar_ordenacao', // link da opção
				'title' => 'Zerar ordenação dessa lista atual', // título da opção
				'img_coluna' => 9, // Coluna background image
				'img_linha' => 1, // Linha background image
				'name' => 'Zerar Ordenação', // Nome do menu
			);
		//}
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
	/* if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=modificar_caminho_raiz&id=#id', // link da opção
			'title' => 'Mudar Raiz d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 11, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Mudar Raiz', // Legenda
		);
	} */
	if(operacao('configuracao')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=permissao_conteudo&id=#id', // link da opção
			'title' => 'Configuração d' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 4, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Configuração', // Legenda
		);
	}
	if($_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] || operacao('modificar_raiz')){
		if(operacao('bloquear')){
			$menu_opcoes[] = Array( // Opção: Bloquear
				'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
				'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 6, // Coluna background image
				'img_linha' => 1, // Linha background image
				'img_coluna2' => 5, // Coluna background image
				'img_linha2' => 1, // Linha background image
				'legenda' => 'Ativar/Desativar', // Legenda
				'bloquear' => true, // Se eh botão de bloqueio
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
		'campo' => 'Ordem', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'input_ordem' => true, // OPCIONAL - alinhamento horizontal
		'width' => '50', // OPCIONAL - Tamanho horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Tipo', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'width' => '40', // OPCIONAL - Tamanho horizontal
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'valor_padrao' => 'C', // Valor do campo
		'opcao1' => htmlImage($_HTML['separador'].$_HTML['ICONS'] . 'conteudo_mini.png',$width,$height,'0',$id,$extra), // Valor do campo
		'opcao2' => '<table><tr><td>'.htmlA($_URL . '?opcao=lista_conteudo&id=#id"',htmlImage($_HTML['separador'].$_HTML['ICONS'] . 'lista_mini.png',$width,$height,'0',$id,' title="Lista de Conteúdos"'),$target,$id,$extra)
		.(operacao('configuracao') ? '</td><td>'. htmlA($_URL . '?opcao=permissao_lista&id=#id"',htmlImage($_HTML['separador'].$_HTML['ICONS'] . 'preferencias_mini.png',$width,$height,'0',$id,' title="Configuração do Conjunto de Conteúdos"'),$target,$id,$extra):'').'</td></tr></table>', // Valor do campo
		'class1' => 'texto3', // Valor do campo
		'class2' => 'texto4', // Valor do campo
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Título', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'id', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		/* 'id' => 1, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
		'tabela' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'mudar_valor' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'padrao_se_nao_existe' => true,
		'valor_padrao_se_nao_existe' => '0',
		'campo' => 'hits', // OPCIONAL - Nome do campo da tabela
		'campo_id' => 'id_conteudo', // OPCIONAL - Nome do campo da tabela */
		'align' => 'center', // OPCIONAL - alinhamento horizontal */
		'width' => '60', // OPCIONAL - Tamanho horizontal
	);
	
	if($_SYSTEM['INSTALL']){
		$header_campos[] = Array( // array com todos os campos do cabeçalho
			'campo' => 'Identificador', // Valor do campo
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'ordenar' => true, // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'padrao_se_nao_existe' => true,
			'valor_padrao_se_nao_existe' => '0',
			'align' => 'center', // OPCIONAL - alinhamento horizontal
		);
	}
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Acessos', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'padrao_se_nao_existe' => true,
		'valor_padrao_se_nao_existe' => '0',
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'width' => '60', // OPCIONAL - Tamanho horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'width' => '120', // OPCIONAL - Tamanho horizontal
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data_hora' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Outra Tabela -------------------------
	
	/* $outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => $_BANCO_PREFIXO.$_LISTA_2['tabela']['nome'], // Nome da tabela
		'campos' => Array(
			'hits',
		), // Array com os nomes dos campos
		'extra' => ' AND status=\'A\'', // Tabela extra
	); */
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => operacao('buscar'), // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_titulo' => 'eventos', // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Habilitar legenda
		'input_ordem' => true, // Habilitar caixa salvar das ordens
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 4, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_conteudo_pai='".($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0')."' ", // Tabela extra
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
		
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface());
}

function add_conteudo(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_INTERFACE_OPCAO;
	global $_CONEXAO_BANCO;
	global $_VARIAVEIS_JS;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	$permissao = permisao_conteudo($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0',true);
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "add_conteudo_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form2 < -->','<!-- form2 > -->');
	
	$pagina = paginaTrocaVarValor($pagina,'#caminho_raiz_2#','A definir');
	$pagina = paginaTrocaVarValor($pagina,'#identificador#','A definir');
	$pagina = paginaTrocaVarValor($pagina,'#data#','');
	$pagina = paginaTrocaVarValor($pagina,'#hora#','');
	
	// ================== Datas  =======================
	
	$cel_nome = 'datas-cel'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$cel_aux = $cel[$cel_nome];
	
	$cel_aux = modelo_var_troca_tudo($cel_aux,'#id-data#','1');
	$cel_aux = modelo_var_troca($cel_aux,'#data_val#','');
	$cel_aux = modelo_var_troca($cel_aux,'#hora_val#','');
	$cel_aux = modelo_var_troca($cel_aux,'#id_externo_val#','');
	$cel_aux = modelo_tag_in($cel_aux,'<!-- datas-del < -->','<!-- datas-del > -->','');
	
	$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
	$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	
	$pagina = modelo_var_troca($pagina,'#datas-ids-html#','1');
	
	$_VARIAVEIS_JS['eventos_data_id'] = 1;
	
	// ================== Módulos Inativos no Sistema =======================
	
	$modulos_inativos = modulos_sistema_inativos();
	if($modulos_inativos)
	foreach($modulos_inativos as $modulo => $valor){
		$pagina = modelo_tag_in($pagina,'<!-- '.$modulo.' < -->','<!-- '.$modulo.' > -->','');
	}
	
	$campos = array_merge($_LISTA['campos'],$_LISTA['campos_sem_permissao']);
	
	foreach($campos as $campo){
		if(
			$campo != 'titulo' &&
			$campo != 'data'
		){
			if(!$permissao[$campo])			$pagina = modelo_tag_in($pagina,'<!-- '.$campo.' < -->','<!-- '.$campo.' > -->','<!-- '.$campo.' -->');
		}
	}
	
	$campo = 'conteiner_posicao'; if(!$permissao[$campo]){
		$pagina = modelo_tag_in($pagina,'<!-- '.$campo.' < -->','<!-- '.$campo.' > -->','<!-- '.$campo.' -->');
		$pagina = modelo_tag_in($pagina,'<!-- remover_layout_menu < -->','<!-- remover_layout_menu > -->','');
		$pagina = modelo_tag_in($pagina,'<!-- remover_layout < -->','<!-- remover_layout > -->','');
	}
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$checked = ' checked="checked"';
	$tipo1 = true;
	
	if($tipo1)
		$CHK_TIP1 = $checked;
	else
		$CHK_TIP2 = $checked;
	
	$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'TIP1'.'#',$CHK_TIP1);
	$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'TIP2'.'#',$CHK_TIP2);
	$pagina = paginaTrocaVarValor($pagina,'#conteudo_disabled#',$disabled);
	
	$status1 = true;
	
	if($status1)
		$CHK_STATUS1 = $checked;
	else
		$CHK_STATUS2 = $checked;
	
	$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'STATUS1'.'#',$CHK_STATUS1);
	$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'STATUS2'.'#',$CHK_STATUS2);
	
	$data1 = true;
	
	if($data1)
		$CHK_DATA1 = $checked;
	else
		$CHK_DATA2 = $checked;
	
	$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'DATA1'.'#',$CHK_DATA1);
	$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'DATA2'.'#',$CHK_DATA2);
	
	if($_SESSION[$_SYSTEM['ID'].'conteudo_campos_temporario']){
		$campos_temp_aux = $_SESSION[$_SYSTEM['ID'].'conteudo_campos_temporario'];
		$_SESSION[$_SYSTEM['ID'].'conteudo_campos_temporario'] = false;
		
		if($campos_temp_aux){
			foreach($campos_temp_aux as $campo_temp){
				if(!$description){
					if($campo_temp[0] == 'description'){
						$description = $campo_temp[1];
					}
				}
				
				if(!$redes_titulo){
					if($campo_temp[0] == 'redes_titulo'){
						$redes_titulo = $campo_temp[1];
					}
				}
				
				if(!$redes_subtitulo){
					if($campo_temp[0] == 'redes_subtitulo'){
						$redes_subtitulo = $campo_temp[1];
					}
				}
				
				$campos_temp[$campo_temp[0]] = $campo_temp[1];
			}
		}
	}
	
	foreach($campos as $campo){
		switch($campo){
			case 'titulo_img':
			case 'imagem_pequena':
			case 'imagem_grande':
				$pagina = modelo_var_troca($pagina,"#".$campo."_"."name#",($campos_temp[$campo."_"."name"]?$campos_temp[$campo."_"."name"]:""));
				$pagina = modelo_var_troca($pagina,"#".$campo."_"."name_real#","A definir");
				$pagina = modelo_var_troca($pagina,"#".$campo."_"."title#",($campos_temp[$campo."_"."name"]?$campos_temp[$campo."_"."title"]:""));
				$pagina = modelo_var_troca($pagina,"#".$campo."_"."alt#",($campos_temp[$campo."_"."name"]?$campos_temp[$campo."_"."alt"]:""));
			break;
		}
		
		$pagina = paginaTrocaVarValor($pagina,'#'.$campo.'#',($campos_temp[$campo]?$campos_temp[$campo]:""));
	}
	
	$pagina = paginaTrocaVarValor($pagina,'#hits#','0');
	$pagina = paginaTrocaVarValor($pagina,'#description#',$description);
	$pagina = paginaTrocaVarValor($pagina,'#redes_titulo#',$redes_titulo);
	$pagina = paginaTrocaVarValor($pagina,'#redes_subtitulo#',$redes_subtitulo);
	$pagina = paginaTrocaVarValor($pagina,'#galeria_id#',$galeria_id);
	$pagina = paginaTrocaVarValor($pagina,'#galeria_grupo_id#',$galeria_grupo_id);
	$pagina = paginaTrocaVarValor($pagina,'#videos_id#',$videos_id);
	$pagina = paginaTrocaVarValor($pagina,'#videos_grupo_id#',$videos_grupo_id);
	$pagina = paginaTrocaVarValor($pagina,'#galeria_imagens#',$galeria_imagens);
	$pagina = paginaTrocaVarValor($pagina,'#galeria_grupo_imagens#',$galeria_grupo_imagens);
	$pagina = paginaTrocaVarValor($pagina,'#galerias_videos#',$galerias_videos);
	$pagina = paginaTrocaVarValor($pagina,'#galerias_grupo_videos#',$galeria_grupo_videos);
	
	$conjunto_tags = conjunto_tags(Array(
		'id' => $id,
	));
	
	$pagina = paginaTrocaVarValor($pagina,'#tags#',$conjunto_tags['tags']);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url#",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao#",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao#",$opcao);
	$pagina = modelo_var_troca_tudo($pagina,"#id#",$id);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE_OPCAO = 'add_conteudo';
	
	return interface_layout(parametros_interface());
}

function add_conteudo_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_USUARIO_ATIVO_2;
	global $_LISTA;
	global $_INTERFACE;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_CONTEUDO;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	$identificador = $_REQUEST['titulo'];
	$identificador = criar_identificador($identificador);
	
	if($_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"]){
		$conteudo_arvore_2 = $_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"];
		
		foreach($conteudo_arvore_2 as $raiz){
			$caminho_raiz .= $raiz['identificador'] . '/';
		}
	}
	
	if($_REQUEST['identificador_auxiliar']){
		$identificador_auxiliar = $_REQUEST['identificador_auxiliar'];
		$identificador_auxiliar = criar_identificador($identificador_auxiliar,false,true);
	}
	
	$campo_nome = "id_conteudo_pai"; 								$campos[] = Array($campo_nome,($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0'));
	$campo_nome = "tipo"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$_POST[$post_nome]); if($_POST[$post_nome] == 'L') $permisao_modelo = true;
	$campo_nome = "identificador"; $post_nome = $campo_nome; 		$campos[] = Array($campo_nome,$identificador);
	$campo_nome = "identificador_auxiliar"; $post_nome = $campo_nome; 		$campos[] = Array($campo_nome,$identificador_auxiliar);
	
	$campo_nome = "titulo"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "sub_titulo"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "author"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "keywords"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "description"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "redes_titulo"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "redes_subtitulo"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "texto"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,html_entity_decode($_POST[$post_nome],ENT_QUOTES,'ISO-8859-1'));
	$campo_nome = "texto2"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,html_entity_decode($_POST[$post_nome],ENT_QUOTES,'ISO-8859-1'));
	$campo_nome = "link_externo"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "videos_youtube"; $post_nome = $campo_nome; 		if($_POST[$post_nome]){		$campos[] = Array($campo_nome,$_POST[$post_nome]); $videos_youtube = $_POST[$post_nome]; }
	$campo_nome = "conteiner_posicao_x"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "conteiner_posicao_y"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);

	$campo_nome = "data_automatica"; $post_nome = $campo_nome; 		if($_POST[$post_nome]){
		$campos[] = Array($campo_nome,$_POST[$post_nome]);
		$campo_nome = "data"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'NOW()',true);
	} else {
		$campo_nome = "data"; $data = data_padrao_date($_REQUEST["data"])." ".$_REQUEST["hora"].":00";
		$campos[] = Array($campo_nome,$data);
	}
	
	$campo_nome = "titulo_img_name"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "titulo_img_title"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "titulo_img_alt"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "imagem_pequena_name"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "imagem_pequena_title"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "imagem_pequena_alt"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "imagem_grande_name"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "imagem_grande_title"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "imagem_grande_alt"; $post_nome = $campo_nome; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	
	$campo_nome = "galeria"; $post_nome = "galeria_id"; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "galeria_grupo"; $post_nome = "galeria_grupo_id"; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "videos"; $post_nome = "videos_id"; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "videos_grupo"; $post_nome = "videos_grupo_id"; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "versao"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'1');
	$campo_nome = "sitemap"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'1');
	$campo_nome = "rss"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'1');
	$campo_nome = "rss_redes"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'1');
	$campo_nome = "caminho_raiz"; $post_nome = $campo_nome; 		$campos[] = Array($campo_nome,$caminho_raiz);
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$id_tabela = banco_last_id();
	
	if($permisao_modelo){
		$permisao_modelo = permisao_modelo($id_tabela);
		
		if($permisao_modelo){
			$campos = false;
			
			$campo_nome = "id_conteudo"; 										$campos[] = Array($campo_nome,$id_tabela);
			$campo_nome = "tipo"; 												$campos[] = Array($campo_nome,$_POST[$campo_nome]);
			
			foreach($permisao_modelo as $campo => $valor){
				if($valor)
				$campos[] = Array($campo,$valor);
			}
			
			banco_insert_name($campos,$_LISTA['tabela2']['nome']);
		}
	}
	
	if(
		$_FILES['titulo_img'] ||
		$_FILES['imagem_pequena'] ||
		$_FILES['imagem_grande']
	){	
		$_PERMISSAO_CONTEUDO = permisao_conteudo_tudo($id_tabela);
	}
	
	if($videos_youtube)videos_youtube($videos_youtube,$id_tabela);
	guardar_arquivo($_FILES['titulo_img'],'imagem','titulo_img',$id_tabela);
	guardar_arquivo($_FILES['imagem_pequena'],'imagem','imagem_pequena',$id_tabela);
	guardar_arquivo($_FILES['imagem_grande'],'imagem','imagem_grande',$id_tabela);
	guardar_arquivo($_FILES['musica'],'musica','musica',$id_tabela);
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	// ================================= Conjunto de Tags ===============================
	
	if($_REQUEST['tags-flag']){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_tags',
			))
			,
			"conteudo_tags",
			""
		);
		
		if($resultado){
			foreach($resultado as $res){
				if($_REQUEST['tags'.$res['id_conteudo_tags']]){
					$array['id_conteudo_tags'][] = $res['id_conteudo_tags'];
					$array['id_conteudo'][] = $id_tabela;
				}
			}
			
			$dados[] = Array("id_conteudo_tags",$array['id_conteudo_tags']);
			$dados[] = Array("id_conteudo",$array['id_conteudo']);
			
			banco_insert_name_varios
			(
				$dados,
				"conteudo_conteudo_tags"
			);
		}
	}
	
	// ================== Datas  =======================
	
	if($_REQUEST['datas-ids-html']){
		$datas_ids_html = explode(',',$_REQUEST['datas-ids-html']);
	
		foreach($datas_ids_html as $datas_id_html){
			if($_REQUEST['data-'.$datas_id_html] && $_REQUEST['hora-'.$datas_id_html]){
				$data = data_padrao_date($_REQUEST['data-'.$datas_id_html]);
				$hora = $_REQUEST['hora-'.$datas_id_html];
				$data_hora = $data.' '.$hora.':00';
				
				$campos = null;
		
				$campo_nome = "id_conteudo"; $campo_valor = $id_tabela; 								if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
				$campo_nome = "id_html"; $campo_valor = $datas_id_html; 								if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
				$campo_nome = "data_hora"; $campo_valor = $data_hora; 									if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
				$campo_nome = "id_externo"; $campo_valor = $_REQUEST['id_externo-'.$datas_id_html]; 	if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
				
				banco_insert_name
				(
					$campos,
					"evento_datas"
				);
			}
		}
	}
	
	return lista();
}

function editar_conteudo($param = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_VARIAVEIS_JS;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$modelo = paginaModelo('html.html');
		$pagina = paginaTagValor($modelo,'<!-- form2 < -->','<!-- form2 > -->');
		$remover = paginaTagValor($modelo,'<!-- remover < -->','<!-- remover > -->');
		
		if(!$_CONEXAO_BANCO)banco_conectar();
		
		// ================== Módulos Inativos no Sistema =======================
	
		$modulos_inativos = modulos_sistema_inativos();
		if($modulos_inativos)
		foreach($modulos_inativos as $modulo => $valor){
			$pagina = modelo_tag_in($pagina,'<!-- '.$modulo.' < -->','<!-- '.$modulo.' > -->','');
		}
		
		if($_REQUEST["buscar_opcao"] == 'busca_ver'){
			$tabela = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_pai',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			
			if($tabela[0]['id_conteudo_pai']){
				$buscar_raiz = true;
				$buscar_pai = true;
				$id_conteudo_pai = $tabela[0]['id_conteudo_pai'];
			
				while($buscar_raiz){
					$tabela = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_conteudo_pai',
							'titulo',
							'identificador',
						))
						,
						$_LISTA['tabela']['nome'],
						"WHERE ".$_LISTA['tabela']['id']."='".$id_conteudo_pai."'"
					);
					
					if($buscar_pai){
						$buscar_pai = false;
						$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]		= 	$id_conteudo_pai;
						$_SESSION[$_SYSTEM['ID']."titulo_pai"] 		= 	$tabela[0]['titulo'];
						//$_LISTA['ferramenta']						=	$tabela[0]['titulo'];
					}
					
					$conteudo_arvore_2[] = Array(
						'id' => $id_conteudo_pai,
						'nome' => $tabela[0]['titulo'],
						'identificador' => $tabela[0]['identificador'],
					);
					
					if(!$tabela[0]['id_conteudo_pai']){
						$buscar_raiz = false;
					} else {
						$id_conteudo_pai = $tabela[0]['id_conteudo_pai'];
					}
				}
				
				$_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] = array_reverse($conteudo_arvore_2);
			} else {
				$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]		= 	0;
				$_SESSION[$_SYSTEM['ID']."titulo_pai"] 		= 	null;
				$_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"]	=	null;
				//$_LISTA['ferramenta']						=	'Conteúdo';
			}
		}
		
		$permissao = permisao_conteudo($id);
		
		$campos = array_merge($_LISTA['campos'],$_LISTA['campos_sem_permissao']);
		
		foreach($campos as $campo){
			if(
				$campo != 'titulo' &&
				$campo != 'data'
			){
				if(!$permissao[$campo])			$pagina = modelo_tag_in($pagina,'<!-- '.$campo.' < -->','<!-- '.$campo.' > -->','<!-- '.$campo.' -->');
			}
		}
		
		$campo = 'conteiner_posicao'; if(!$permissao[$campo]){
			$pagina = modelo_tag_in($pagina,'<!-- '.$campo.' < -->','<!-- '.$campo.' > -->','<!-- '.$campo.' -->');
			$pagina = modelo_tag_in($pagina,'<!-- remover_layout_menu < -->','<!-- remover_layout_menu > -->','');
			$pagina = modelo_tag_in($pagina,'<!-- remover_layout < -->','<!-- remover_layout > -->','');
		}
		
		$campos[] = 'hits';
		$campos[] = 'tipo';
		$campos[] = 'status';
		$campos[] = 'versao';
		$campos[] = 'caminho_raiz';
		$campos[] = 'data_automatica';
		$campos[] = 'description';
		$campos[] = 'redes_titulo';
		$campos[] = 'redes_subtitulo';
		
		$campos[] = 'titulo_img_name';
		$campos[] = 'titulo_img_title';
		$campos[] = 'titulo_img_alt';
		$campos[] = 'imagem_pequena_name';
		$campos[] = 'imagem_pequena_title';
		$campos[] = 'imagem_pequena_alt';
		$campos[] = 'imagem_grande_name';
		$campos[] = 'imagem_grande_title';
		$campos[] = 'imagem_grande_alt';
		
		$versao = '?v='.($tabela[0]['versao']?$tabela[0]['versao']:'0.1');
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$_SESSION[$_SYSTEM['ID']."mp3_id"] = $id;
		
		foreach($campos as $campo){
			$campos_guardar[$campo] = $tabela[0][$campo];
		}
		
		if($tabela[0]['data']){
			$data_hora = data_hora_from_datetime($tabela[0]['data']);
			$tabela[0]['data'] = $data_hora[0];
			$hora = $data_hora[1];
		}
		
		if($tabela[0]['titulo_img']){
			$img_aux = explode('/',$tabela[0]['titulo_img']);
			$imagem_real['titulo_img'] = $img_aux[count($img_aux)-1];
			
			$aux = explode('.',$tabela[0]['titulo_img']);
			
			if($aux[1] == 'swf'){
				$tam = getimagesize($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['titulo_img']);
				$tabela[0]['titulo_img'] = htmlFlash($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['titulo_img'].$versao,$tam[0],$tam[1]);
			} else
				$tabela[0]['titulo_img'] = htmlImage($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['titulo_img'].$versao,$width,$height,$border,$id,'style="max-width:750px;"');
			
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=titulo_img&id='.$id);
			$tabela[0]['titulo_img'] = $remover_aux . $tabela[0]['titulo_img'];
		}
		
		if($tabela[0]['imagem_pequena']){
			$img_aux = explode('/',$tabela[0]['imagem_pequena']);
			$imagem_real['imagem_pequena'] = $img_aux[count($img_aux)-1];
			
			$aux = explode('.',$tabela[0]['imagem_pequena']);
			
			if($aux[1] == 'swf'){
				$tam = getimagesize($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['imagem_pequena']);
				$tabela[0]['imagem_pequena'] = htmlFlash($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['imagem_pequena'].$versao,$tam[0],$tam[1]);
			} else
				$tabela[0]['imagem_pequena'] = htmlImage($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['imagem_pequena'].$versao,$width,$height,$border,$id,'style="max-width:750px;"');
			
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=imagem_pequena&id='.$id);
			$tabela[0]['imagem_pequena'] = $remover_aux . $tabela[0]['imagem_pequena'];
		}
		
		if($tabela[0]['imagem_grande']){
			$img_aux = explode('/',$tabela[0]['imagem_grande']);
			$imagem_real['imagem_grande'] = $img_aux[count($img_aux)-1];
			
			$aux = explode('.',$tabela[0]['imagem_grande']);
			
			if($aux[1] == 'swf'){
				$tam = getimagesize($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['imagem_grande']);
				$tabela[0]['imagem_grande'] = htmlFlash($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['imagem_grande'].$versao,$tam[0],$tam[1]);
			} else
				$tabela[0]['imagem_grande'] = htmlImage($_CAMINHO_RELATIVO_RAIZ.$tabela[0]['imagem_grande'].$versao,$width,$height,$border,$id,'style="max-width:750px;"');
			
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=imagem_grande&id='.$id);
			$tabela[0]['imagem_grande'] = $remover_aux . $tabela[0]['imagem_grande'];
		}
		
		if($tabela[0]['musica']){
			$params['caminho'] = $_CAMINHO_RELATIVO_RAIZ.$tabela[0]['musica'];
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=musica&id='.$id);
			$tabela[0]['musica'] = $remover_aux . mp3_player($params);
		}
		
		$galeria_id = $tabela[0]['galeria'];
		
		if($galeria_id){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				"galerias",
				"WHERE id_galerias='".$galeria_id."'"
			);
			
			$tabela[0]['galeria'] = $resultado[0]['nome'];
			$galeria_imagens = galerias($galeria_id);
			
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=galeria&id='.$id);
			$galeria_imagens = $remover_aux . $galeria_imagens;
		}
		
		$galeria_grupo_id = $tabela[0]['galeria_grupo'];
		
		if($galeria_grupo_id){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'grupo',
				))
				,
				"galerias_grupos",
				"WHERE id_galerias_grupos='".$galeria_grupo_id."'"
			);
			
			$tabela[0]['galeria_grupo'] = $resultado[0]['grupo'];
			
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=galeria_grupo&id='.$id);
			$galeria_grupo_imagens = $remover_aux;
		}
		
		$videos_id = $tabela[0]['videos'];
		
		if($videos_id){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				"galerias_videos",
				"WHERE id_galerias_videos='".$videos_id."'"
			);
			
			$tabela[0]['videos'] = $resultado[0]['nome'];
			$galerias_videos = galerias_videos($videos_id);
			
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=videos&id='.$id);
			$galerias_videos = $remover_aux . $galerias_videos;
		}
		
		$videos_grupo_id = $tabela[0]['videos_grupo'];
		
		if($videos_grupo_id){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'grupo',
				))
				,
				"videos_grupos",
				"WHERE id_videos_grupos='".$videos_grupo_id."'"
			);
			
			$tabela[0]['videos_grupo'] = $resultado[0]['grupo'];
			
			$remover_aux = $remover;
			$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&item=videos_grupo&id='.$id);
			$galerias_grupo_videos = $remover_aux;
		}
		
		foreach($campos as $campo){
			$pagina = paginaTrocaVarValor($pagina,'#'.$campo.'#',$tabela[0][$campo]);
			
			switch($campo){
				case 'titulo_img':
				case 'imagem_pequena':
				case 'imagem_grande':
					$pagina = modelo_var_troca($pagina,"#".$campo."_"."name#",$tabela[0][$campo."_"."name"]);
					$pagina = modelo_var_troca($pagina,"#".$campo."_"."name_real#",($imagem_real[$campo]?$imagem_real[$campo]:"A definir"));
					$pagina = modelo_var_troca($pagina,"#".$campo."_"."title#",$tabela[0][$campo."_"."title"]);
					$pagina = modelo_var_troca($pagina,"#".$campo."_"."alt#",$tabela[0][$campo."_"."alt"]);
				break;
				case 'videos_youtube':
					$cont_videos = videos($tabela[0][$campo],$id,$versao);
				break;
				
			}
		}
		
		$tipo = $tabela[0]['tipo'];
		$status = $tabela[0]['status'];
		$data_automatica = $tabela[0]['data_automatica'];
		$hits = $tabela[0]['hits'];
		$description = $tabela[0]['description'];
		$redes_titulo = $tabela[0]['redes_titulo'];
		$redes_subtitulo = $tabela[0]['redes_subtitulo'];
		$caminho_raiz = 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$tabela[0]['caminho_raiz'].$tabela[0]['identificador'];
		
		$caminho_raiz = html(Array(
			'tag' => 'a',
			'val' => $caminho_raiz,
			'attr' => Array(
				'href' => $caminho_raiz,
				'target' => '_blank',
			)
		));
		
		$checked = ' checked="checked"';
		
		if($tipo == 'C'){
			$CHK_TIP1 = $checked;
		} else {
			$CHK_TIP2 = $checked;
			
			$filhos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE id_conteudo_pai='".$id."'"
			);
			
			if($filhos){
				$disabled = ' disabled="disabled"';
			}
		}
		
		$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'TIP1'.'#',$CHK_TIP1);
		$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'TIP2'.'#',$CHK_TIP2);
		$pagina = paginaTrocaVarValor($pagina,'#conteudo_disabled#',$disabled);
		
		if($status == 'A')
			$CHK_STATUS1 = $checked;
		else
			$CHK_STATUS2 = $checked;
		
		$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'STATUS1'.'#',$CHK_STATUS1);
		$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'STATUS2'.'#',$CHK_STATUS2);
		
		if($data_automatica)
			$CHK_DATA1 = $checked;
		else
			$CHK_DATA2 = $checked;
		
		$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'DATA1'.'#',$CHK_DATA1);
		$pagina = paginaTrocaVarValor($pagina,'#CHK_'.'DATA2'.'#',$CHK_DATA2);
		
		$pagina = paginaTrocaVarValor($pagina,'#hits#',($hits?$hits:'0'));
		$pagina = paginaTrocaVarValor($pagina,'#description#',$description);
		$pagina = paginaTrocaVarValor($pagina,'#redes_titulo#',$redes_titulo);
		$pagina = paginaTrocaVarValor($pagina,'#redes_subtitulo#',$redes_subtitulo);
		$pagina = paginaTrocaVarValor($pagina,'#caminho_raiz_2#',$caminho_raiz);
		$pagina = paginaTrocaVarValor($pagina,'#hora#',$hora);
		$pagina = paginaTrocaVarValor($pagina,'#galeria_id#',$galeria_id);
		$pagina = paginaTrocaVarValor($pagina,'#galeria_grupo_id#',$galeria_grupo_id);
		$pagina = paginaTrocaVarValor($pagina,'#videos_id#',$videos_id);
		$pagina = paginaTrocaVarValor($pagina,'#videos_grupo_id#',$videos_grupo_id);
		$pagina = paginaTrocaVarValor($pagina,'#galeria_imagens#',$galeria_imagens);
		$pagina = paginaTrocaVarValor($pagina,'#galeria_grupo_imagens#',$galeria_grupo_imagens);
		$pagina = paginaTrocaVarValor($pagina,'#galerias_videos#',$galerias_videos);
		$pagina = paginaTrocaVarValor($pagina,'#galerias_grupo_videos#',$galerias_grupo_videos);
		
		$conjunto_tags = conjunto_tags(Array(
			'id' => $id,
		));
		
		$pagina = paginaTrocaVarValor($pagina,'#tags#',$conjunto_tags['tags']);
		
		// ================== Datas  =======================
	
		$cel_nome = 'datas-cel'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		$evento_datas = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_evento_datas',
				'id_html',
				'data_hora',
				'id_externo',
			))
			,
			"evento_datas",
			"WHERE id_conteudo='".$id."'"
			." ORDER BY data_hora ASC"
		);
		
		if($evento_datas){
			foreach($evento_datas as $evento_data){
				$cel_aux = $cel[$cel_nome];
				
				if($evento_data['id_html'] > $eventos_data_id_start) $eventos_data_id_start = $evento_data['id_html'];
				
				$data_hora = data_hora_from_datetime($evento_data['data_hora']);
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,'#id-data#',$evento_data['id_html']);
				$cel_aux = modelo_var_troca($cel_aux,'#data_val#',$data_hora[0]);
				$cel_aux = modelo_var_troca($cel_aux,'#hora_val#',$data_hora[1]);
				$cel_aux = modelo_var_troca($cel_aux,'#id_externo_val#',$evento_data['id_externo']);
				if(!$flag_eventos_data)$cel_aux = modelo_tag_in($cel_aux,'<!-- datas-del < -->','<!-- datas-del > -->','');
				
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
				$flag_eventos_data = true;
				
				$datas_ids_html = $datas_ids_html . ($datas_ids_html ? ',':'') . $evento_data['id_html'];
				
				$campos_guardar['eventos_datas'][$evento_data['id_html']] = Array(
					'data' => data_from_datetime_to_text($data_hora[0]),
					'hora' => $data_hora[1],
					'id_evento_datas' => $evento_data['id_evento_datas'],
					'id_externo' => $evento_data['id_externo'],
				);
			}
			
			$pagina = modelo_var_troca($pagina,'#datas-ids-html#',$datas_ids_html);
			$_VARIAVEIS_JS['eventos_data_id'] = $eventos_data_id_start;
		} else {
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca_tudo($cel_aux,'#id-data#','1');
			$cel_aux = modelo_var_troca($cel_aux,'#data_val#','');
			$cel_aux = modelo_var_troca($cel_aux,'#hora_val#','');
			$cel_aux = modelo_var_troca($cel_aux,'#id_externo_val#','');
			$cel_aux = modelo_tag_in($cel_aux,'<!-- datas-del < -->','<!-- datas-del > -->','');
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			
			$pagina = modelo_var_troca($pagina,'#datas-ids-html#','1');
			$_VARIAVEIS_JS['eventos_data_id'] = 1;
		}
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		
		// ======================================================================================
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "editar_conteudo_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#form_url#",$_LOCAL_ID);
		$pagina = paginaTrocaVarValor($pagina,"#botao#",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao#",$opcao);
		$pagina = modelo_var_troca_tudo($pagina,"#id#",$id);
		
		if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
		$_LISTA['conteudo_titulo'] = $tabela[0]['titulo'];
		
		$_INTERFACE_OPCAO = 'editar';
		$_INTERFACE['local'] = 'conteudo';
		$_INTERFACE['informacao_titulo'] = $in_titulo;
		$_INTERFACE['informacao_tipo'] = $tipo;
		$_INTERFACE['informacao_id'] = $id;
		$_INTERFACE['inclusao'] = $pagina;
	
		return interface_layout(parametros_interface());
	} else
		return lista();
}

function editar_conteudo_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_CONTEUDO;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$campos_antes = campos_antes_recuperar();
		
		if(!$_CONEXAO_BANCO)banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "identificador_auxiliar"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$mudar_identificador2 = true;}
		
		$campo_nome = "tipo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $tipo_alterar = $_POST[$campo_nome];}
		$campo_nome = "titulo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";$mudar_identificador = true;}
		$campo_nome = "sub_titulo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "author"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "keywords"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "description"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "redes_titulo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "redes_subtitulo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "texto"; if($campos_antes[$campo_nome] != html_entity_decode($_POST[$campo_nome],ENT_QUOTES,'ISO-8859-1')){$editar['tabela'][] = $campo_nome."='" . html_entity_decode($_POST[$campo_nome],ENT_QUOTES,'ISO-8859-1') . "'";}
		$campo_nome = "texto2"; if($campos_antes[$campo_nome] != html_entity_decode($_POST[$campo_nome],ENT_QUOTES,'ISO-8859-1')){$editar['tabela'][] = $campo_nome."='" . html_entity_decode($_POST[$campo_nome],ENT_QUOTES,'ISO-8859-1') . "'";}
		$campo_nome = "link_externo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "videos_youtube"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $videos_youtube = $_POST[$campo_nome]; $videos_antes = $campos_antes[$campo_nome]; }
		$campo_nome = "conteiner_posicao_x"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "conteiner_posicao_y"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
		$campo_nome = "data_automatica"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){if($_POST[$campo_nome])$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; else $editar['tabela'][] = $campo_nome."=NULL";}
		
		$campo_nome = "data_automatica"; $post_nome = $campo_nome; 		if($_POST[$post_nome]){
			$campo_nome = "data"; 		$editar['tabela'][] = $campo_nome."=NOW()";
		} else {
			$data = data_padrao_date($_REQUEST["data"])." ".$_REQUEST["hora"].":00";
			$campo_nome = "data"; 		$editar['tabela'][] = $campo_nome."='".$data."'";
		}
		
		$campo_nome = "titulo_img_name"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$campo_nome_2 = preg_replace('/_name/i', '', $campo_nome); $campo_valor_aux = mudar_arquivo_nome($campos_antes[$campo_nome_2],$_POST[$campo_nome]); if($campo_valor_aux){$editar['tabela'][] = $campo_nome_2."='" . $campo_valor_aux . "'";$campos_antes[$campo_nome_2] = $campo_valor_aux;} $editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "titulo_img_title"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "titulo_img_alt"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "imagem_pequena_name"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$campo_nome_2 = preg_replace('/_name/i', '', $campo_nome); $campo_valor_aux = mudar_arquivo_nome($campos_antes[$campo_nome_2],$_POST[$campo_nome]); if($campo_valor_aux){$editar['tabela'][] = $campo_nome_2."='" . $campo_valor_aux . "'";$campos_antes[$campo_nome_2] = $campo_valor_aux;} $editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "imagem_pequena_title"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "imagem_pequena_alt"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "imagem_grande_name"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$campo_nome_2 = preg_replace('/_name/i', '', $campo_nome); $campo_valor_aux = mudar_arquivo_nome($campos_antes[$campo_nome_2],$_POST[$campo_nome]); if($campo_valor_aux){$editar['tabela'][] = $campo_nome_2."='" . $campo_valor_aux . "'";$campos_antes[$campo_nome_2] = $campo_valor_aux;} $editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "imagem_grande_title"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "imagem_grande_alt"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
		$campo_nome = "galeria"; $post_nome = "galeria_id"; if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$post_nome]?"'".$_POST[$post_nome]."'":'NULL');}
		$campo_nome = "galeria_grupo"; $post_nome = "galeria_grupo_id"; if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$post_nome]?"'".$_POST[$post_nome]."'":'NULL');}
		$campo_nome = "videos"; $post_nome = "videos_id"; if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$post_nome]?"'".$_POST[$post_nome]."'":'NULL');}
		$campo_nome = "videos_grupo"; $post_nome = "videos_grupo_id"; if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$post_nome]?"'".$_POST[$post_nome]."'":'NULL');}
	
		$campo_nome = "status"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "versao"; 	$editar['tabela'][] = $campo_nome."='".((int)$campos_antes[$campo_nome]+1)."'";
		$campo_nome = "sitemap"; 	$editar['tabela'][] = $campo_nome."='1'";
		$campo_nome = "rss"; 	$editar['tabela'][] = $campo_nome."='1'";
		$campo_nome = "rss_redes"; 	$editar['tabela'][] = $campo_nome."='1'";
		
		if($mudar_identificador){
			$identificador = $_REQUEST['titulo'];
			$identificador = criar_identificador($identificador,$id);
			$campo_nome = "identificador"; 	$editar['tabela'][] = $campo_nome."='".$identificador."'";
			
			if($tipo_alterar == 'L' || $campos_antes['tipo'] == 'L'){
				modificar_caminho_raiz_filhos_2(Array(
					'id' => $id,
					'identificador' => $campos_antes['identificador'],
					'identificador_novo' => $identificador,
				));
			}
		}
		
		if($mudar_identificador2){
			if($_REQUEST['identificador_auxiliar']){
				$identificador_auxiliar = $_REQUEST['identificador_auxiliar'];
				$identificador_auxiliar = criar_identificador($identificador_auxiliar,$id,true);
			}
			
			$campo_nome = "identificador_auxiliar"; 	$editar['tabela'][] = $campo_nome."='".$identificador_auxiliar."'";
		}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		if($tipo_alterar){
			if($tipo_alterar == 'L'){
				$conteudo_permissao = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_conteudo_permissao',
					))
					,
					"conteudo_permissao",
					"WHERE id_conteudo='".$id."'".
					" AND tipo='L'"
				);
				
				if(!$conteudo_permissao){
					$permisao_modelo = permisao_modelo($id);
					
					if($permisao_modelo){
						$campos = false;
						
						$campo_nome = "id_conteudo"; 										$campos[] = Array($campo_nome,$id);
						$campo_nome = "tipo"; 												$campos[] = Array($campo_nome,$tipo_alterar);
					
						foreach($permisao_modelo as $campo => $valor){
							if($valor)
							$campos[] = Array($campo,$valor);
						}
						
						banco_insert_name($campos,$_LISTA['tabela2']['nome']);
					}
				}
			} else {
				banco_delete
				(
					"conteudo_permissao",
					"WHERE id_conteudo='".$id."'".
					" AND tipo!='C'"
				);
			}
		}
		
		if(
			$_FILES['titulo_img'] ||
			$_FILES['imagem_pequena'] ||
			$_FILES['imagem_grande']
		){	
			$_PERMISSAO_CONTEUDO = permisao_conteudo_tudo($id);
		}
		
		if($videos_youtube)videos_youtube($videos_youtube,$id,$videos_antes);
		if($_FILES['titulo_img']['size'] != 0)		{guardar_arquivo($_FILES['titulo_img'],'imagem','titulo_img',$id,$campos_antes['titulo_img']); $titulo_img = true;}
		if($_FILES['imagem_pequena']['size'] != 0)	{guardar_arquivo($_FILES['imagem_pequena'],'imagem','imagem_pequena',$id,$campos_antes['imagem_pequena']); $imagem_pequena = true;}
		if($_FILES['imagem_grande']['size'] != 0)	{guardar_arquivo($_FILES['imagem_grande'],'imagem','imagem_grande',$id,$campos_antes['imagem_grande']); $imagem_grande = true;}
		if($_FILES['musica']['size'] != 0)			{guardar_arquivo($_FILES['musica'],'musica','musica',$id,$campos_antes['musica']); $musica = true;}
		/* if($_FILES['video']['size'] != 0)			{guardar_arquivo($_FILES['video'],'video','video',$id,$campos_antes['video']); $video = true;}
		 */
		$_SESSION[$_SYSTEM['ID']."mudou_valor"] = Array(
			'musica' => $musica,
			//'video' => $video,
		);
		
		// ======================================================================================
		
		if($_REQUEST['galeria_escolher']){
			header('Location: '.$_CAMINHO_RELATIVO_RAIZ.'admin/galerias/?conteudo_escolher=sim&id_conteudo='.$id);
			exit(0);
		}
		
		// ================================= Conjunto de Tags ===============================
		
		if($_REQUEST['tags-flag']){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_tags',
				))
				,
				"conteudo_tags",
				""
			);
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_tags',
				))
				,
				"conteudo_conteudo_tags",
				"WHERE id_conteudo='".$id."'"
			);
			
			if($resultado){
				foreach($resultado as $res){
					if($_REQUEST['tags'.$res['id_conteudo_tags']]){
						$found = false;
						if($resultado2)
						foreach($resultado2 as $res2){
							if($res['id_conteudo_tags'] == $res2['id_conteudo_tags']){
								$found = true;
								break;
							}
						}
						
						if(!$found){
							$array['id_conteudo_tags'][] = $res['id_conteudo_tags'];
							$array['id_conteudo'][] = $id;
						}
					} else {
						$found = false;
						if($resultado2)
						foreach($resultado2 as $res2){
							if($res['id_conteudo_tags'] == $res2['id_conteudo_tags']){
								$found = true;
								break;
							}
						}
						
						if($found){
							banco_delete
							(
								"conteudo_conteudo_tags",
								"WHERE id_conteudo='".$id."'"
								." AND id_conteudo_tags='".$res['id_conteudo_tags']."'"
							);
						}
					}
				}
				
				if($array['id_conteudo_tags']){
					$dados[] = Array("id_conteudo_tags",$array['id_conteudo_tags']);
					$dados[] = Array("id_conteudo",$array['id_conteudo']);
					
					banco_insert_name_varios
					(
						$dados,
						"conteudo_conteudo_tags"
					);
				}
			}
		}
		
		// ================================= Eventos Datas ===============================
		
		$eventos_datas = $campos_antes['eventos_datas'];
		$datas_ids_html = explode(',',$_REQUEST['datas-ids-html']);
		
		if($datas_ids_html){
			foreach($datas_ids_html as $data_id_html){
				$found_data_evento = false;
				if($eventos_datas)
				foreach($eventos_datas as $id_html => $eventos_data){
					if($data_id_html == $id_html){
						$found_data_evento = true;
						break;
					}
				}

				$campos = null;
				if(!$found_data_evento){
					if($_REQUEST['data-'.$data_id_html] && $_REQUEST['hora-'.$data_id_html]){
						$data = data_padrao_date($_REQUEST['data-'.$data_id_html]);
						$hora = $_REQUEST['hora-'.$data_id_html];
						$data_hora = $data.' '.$hora.':00';
						
						$campos = null;
				
						$campo_nome = "id_conteudo"; $campo_valor = $id; 									if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
						$campo_nome = "id_html"; $campo_valor = $data_id_html; 								if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
						$campo_nome = "data_hora"; $campo_valor = $data_hora; 								if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
						$campo_nome = "id_externo"; $campo_valor = $_REQUEST['id_externo-'.$data_id_html]; 	if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
						
						banco_insert_name
						(
							$campos,
							"evento_datas"
						);
					}
				} else {
					$id_evento_datas = $eventos_data['id_evento_datas'];
					
					if($_REQUEST['data-'.$data_id_html] && $_REQUEST['hora-'.$data_id_html]){
						if(
							$_REQUEST['data-'.$data_id_html] != $eventos_data['data'] || 
							$_REQUEST['hora-'.$data_id_html] != $eventos_data['hora']
						){
							$editar['data_evento'] = false;
							
							$data = data_padrao_date($_REQUEST['data-'.$data_id_html]);
							$hora = $_REQUEST['hora-'.$data_id_html];
							$data_hora = $data.' '.$hora.($_REQUEST['hora-'.$data_id_html] != $eventos_data['hora'] ? ':00' : '');
					
							$campo_nome = "data_hora"; $campo_valor = $data_hora; 		$editar['data_evento'][] = $campo_nome."='" . $campo_valor . "'";
							$campo_nome = "id_externo";  								if($eventos_data['id_externo'] != $_REQUEST['id_externo-'.$data_id_html]) $editar['data_evento'][] = $campo_nome."='" . $_REQUEST['id_externo-'.$data_id_html] . "'";
							
							if($editar['data_evento']){
								$editar_sql['data_evento'] = banco_campos_virgulas($editar['data_evento']);
								
								banco_update
								(
									$editar_sql['data_evento'],
									'evento_datas',
									"WHERE id_evento_datas='".$id_evento_datas."'"
								);
							}
						} 
					} else {
						banco_delete
						(
							"evento_datas",
							"WHERE id_evento_datas='".$eventos_data['id_evento_datas']."'"
						);
					}
				}
			}
		}
		
		if($eventos_datas){
			foreach($eventos_datas as $id_html => $eventos_data){
				$found_data_evento = false;
				if($datas_ids_html)
				foreach($datas_ids_html as $data_id_html){
					if($data_id_html == $id_html){
						$found_data_evento = true;
					}
				}
				
				if(!$found_data_evento){
					banco_delete
					(
						"evento_datas",
						"WHERE id_evento_datas='".$eventos_data['id_evento_datas']."'"
					);
				}
			}
		}
	}
	
	return lista();
}

function excluir(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	global $_CONEXAO_BANCO;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		if(!$_CONEXAO_BANCO)banco_conectar();
		banco_update
		(
			"identificador=NULL,".
			"sitemap=1,".
			$_LISTA['tabela']['status']."='D'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		excluir_filhos($id);
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function excluir_filhos($id){
	global $_LISTA;
	
	$conteudo = banco_select
	(
		"id_conteudo"
		,
		"conteudo",
		"WHERE id_conteudo_pai='".$id."'"
	);
	
	if($conteudo){
		foreach($conteudo as $con){
			banco_update
			(
				"identificador=NULL,".
				$_LISTA['tabela']['status']."='D'",
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$con['id_conteudo']."'"
			);
			
			$filho = banco_select
			(
				"id_conteudo"
				,
				"conteudo",
				"WHERE id_conteudo_pai='".$con['id_conteudo']."'"
			);
			
			if($filho){
				excluir_filhos($con['id_conteudo']);
			}
		}
	}
}

function bloqueio(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_CONEXAO_BANCO;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		$tipo = $_GET["tipo"];
		
		if($tipo == '1'){
			$status = 'B';
		} else {
			$status = 'A';
		}
		
		if(!$_CONEXAO_BANCO)banco_conectar();
		banco_update
		(
			$_LISTA['tabela']['status']."='".$status."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
	}
	
	return lista();
}

function lista_conteudo(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_CONEXAO_BANCO;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	$conteudo_arvore_2 = $_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"];
	
	if($id){
		if($conteudo_arvore_2)
		foreach($conteudo_arvore_2 as $filho){
			$arvore_aux[] = $filho;
			if($id == $filho['id']){
				$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]		= 	$filho['id'];
				$_SESSION[$_SYSTEM['ID']."titulo_pai"] 		= 	$filho['nome'];
				//$_LISTA['ferramenta']						=	$filho['nome'];
				$_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] 	= 	$arvore_aux;
				$found_filho = true;
				break;
			}
		}
		
		if(!$found_filho){
			if(!$_CONEXAO_BANCO)banco_conectar();
			
			$tabela = banco_select_name
			(
				banco_campos_virgulas(Array(
					'tipo',
					'titulo',
					'identificador',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			
			if($tabela[0]['tipo'] == 'T'){
				return editar_conteudo();
			} else {
				$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"] = $id;
				$_SESSION[$_SYSTEM['ID']."titulo_pai"] = $tabela[0]['titulo'];
				//$_LISTA['ferramenta']			=	$_SESSION[$_SYSTEM['ID']."titulo_pai"];
				
				$conteudo_arvore_2[] = Array(
					'id' => $id,
					'nome' => $tabela[0]['titulo'],
					'identificador' => $tabela[0]['identificador'],
				);
				
				$_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] = $conteudo_arvore_2;
			}
		}
	}
	
	return lista();
}

function raiz(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	
	$_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] = null;
	$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"] = 0;
	$_SESSION[$_SYSTEM['ID']."titulo_pai"] = null;
	//$_LISTA['ferramenta']			=	'Conteúdos';
	
	return lista();
}

function ordenacao(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA_2;
	global $_CONEXAO_BANCO;
	
	$_LISTA = $_LISTA_2;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		$valor = $_GET["valor"];
		
		if(!$_CONEXAO_BANCO)banco_conectar();
		$textos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_categoria_conteudo',
				'ordem',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		if($valor > 0){
			banco_update
			(
				"ordem=ordem+1",
				$_LISTA['tabela']['nome'],
				"WHERE ordem>='".$valor."'".
				" AND id_categoria_conteudo='".$textos[0]['id_categoria_conteudo']."'"
			);
		} else {
			banco_update
			(
				"ordem=ordem-1",
				$_LISTA['tabela']['nome'],
				"WHERE ordem>'".$textos[0]['ordem']."'".
				" AND id_categoria_conteudo='".$textos[0]['id_categoria_conteudo']."'"
			);
		}
		
		banco_update
		(
			"ordem='".$valor."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
	}
	
	return textos_lista();
}

function remover_item(){
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	
	$id = $_REQUEST['id'];
	$item = $_REQUEST['item'];
	$conteudo_permissao = $_REQUEST['conteudo_permissao'];
	$tipo = $_REQUEST['tipo'];
	$permissao_lista = $_REQUEST['permissao_lista'];
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/";
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($conteudo_permissao){
		if($item){
			if(!$id){
				$id = '0';
			}
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$item,
				))
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'"
				." AND tipo='".$tipo."'"
			);
			
			banco_update
			(
				$item."=NULL",
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'"
				." AND tipo='".$tipo."'"
			);
			
			if(!$resultado[0][$item]){
				alerta("<p>Imagem NÃO removida!</p><p>NOTA: Essa imagem não pertence a essa configuração. Ela foi herdada da configuração pai do conteúdo / conjunto de conteúdo específico.</p><p>ALTERNATIVA: Clique no botão GRAVAR para criar uma configuração específica para esse conteúdo / conjunto de conteúdos e em seguida exclua a imagem novamente.</p>");
			} else {
				list($mask_imagem,$mask_posicao) = explode(';',$resultado[0][$item]);
				
				$aux = explode($item,$mask_imagem);
				$aux2 = explode('_'.$tipo,$aux[1]);
				
				$mask_imagem = str_replace($caminho_internet,$caminho_fisico,$mask_imagem);
				
				unlink($mask_imagem);
				alerta("Imagem removida com sucesso!");
			}
			
			$_REQUEST['nivel_id'] = $tipo;
			if($permissao_lista)$_REQUEST["opcao"] = 'permissao_lista';
			
			return permisao();
		}
	} else if($item && $id){
		if(
			$item == 'videos_grupo' ||
			$item == 'videos' ||
			$item == 'galeria_grupo' ||
			$item == 'galeria'
		){
			banco_update
			(
				$item."=NULL",
				"conteudo",
				"WHERE id_conteudo='".$id."'"
			);
		} else {
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$item,
				))
				,
				"conteudo",
				"WHERE id_conteudo='".$id."'"
			);
			
			banco_update
			(
				($item != 'musica' ?
					$item."_name=NULL,"
					.$item."_title=NULL,"
					.$item."_alt=NULL,"
				:
					""
				).$item."=NULL",
				"conteudo",
				"WHERE id_conteudo='".$id."'"
			);
			
			$mini_aux = explode('.',$resultado[0][$item]);
			$mini = $mini_aux[0] . "-mini." . $mini_aux[1];
			
			$mini = str_replace($caminho_internet,$caminho_fisico,$mini);
			$resultado[0][$item] = str_replace($caminho_internet,$caminho_fisico,$resultado[0][$item]);
			
			unlink($resultado[0][$item]);
			if(is_file($mini))unlink($mini);
			
		}
		
		alerta("Ítem removido com sucesso!");
		
		return editar_conteudo();
	}
}

function ordem(){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	if($_REQUEST['id']){
		$id_text = $_REQUEST['id'];
		
		$id_arr = explode(';',$id_text);
		
		if($id_arr)
		foreach($id_arr as $id_val){
			if($id_val){
				list($t['id_conteudo'],$t['ordem']) = explode(',',$id_val);
				if($t['ordem']>0)$ids_formatados[] = $t;
			}
		}
		
		if($ids_formatados){
			foreach ($ids_formatados as $key => $row) {
				$ordem[$key]  = $row['ordem'];
			}
			
			array_multisort($ordem, SORT_ASC, $ids_formatados);
		}
		
		if(!$_CONEXAO_BANCO)banco_conectar();
		$conteudos_ordem = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
			))
			,
			"conteudo",
			"WHERE id_conteudo_pai='".($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0')."'"
			." AND ordem > 0"
			." AND status!='D'"
			." ORDER BY ordem"
		);
		
		$conteudos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
				'ordem',
			))
			,
			"conteudo",
			"WHERE id_conteudo_pai='".($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0')."'"
			." AND status!='D'"
			." ORDER BY ordem"
		);
		
		if($conteudos_ordem){
			$count = 0;
			foreach($conteudos as $conteudo){
				if($ids_formatados)
				foreach($ids_formatados as $campo){
					if($campo['id_conteudo'] == $conteudo['id_conteudo']){
						if($campo['ordem'] != $conteudo['ordem']){
							$conteudos[$count]['ordem'] = $campo['ordem'];
						}
						break;
					}
				}
				$count++;
			}
			
			$ordem = false;
			foreach($conteudos as $key => $row){
				$ordem[$key]  = $row['ordem'];
			}
			
			array_multisort($ordem, SORT_ASC, $conteudos);
			
			$count = 0;
			foreach($conteudos as $conteudo){
				$count++;
				$campos[] = Array($conteudo['id_conteudo'],$count);
			}
			
			banco_update_varios($campos,'conteudo','ordem','id_conteudo');
		} else {
			if($ids_formatados)
			foreach($ids_formatados as $campo){
				$count++;
				$campos[] = Array($campo['id_conteudo'],$count);
			}
			
			foreach($conteudos as $conteudo){
				$flag = false;
				if($ids_formatados)
				foreach($ids_formatados as $campo){
					if($campo['id_conteudo'] == $conteudo['id_conteudo']){
						$flag = true;
						break;
					}
				}
				
				if(!$flag){
					$count++;
					$campos[] = Array($conteudo['id_conteudo'],$count);
				}
			}
			
			banco_update_varios($campos,'conteudo','ordem','id_conteudo');
		}
	}
	
	return lista();
}

function zerar_ordenacao(){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	banco_update
	(
		"ordem=NULL",
		"conteudo",
		"WHERE id_conteudo_pai='".($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0')."'"
	);
	
	return lista();
}

function modificar_caminho_raiz_parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_LISTA_2;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	global $_CONEXAO_BANCO;
	
	if($_REQUEST['id']){
		$id = $_REQUEST['id'];
	}
	
	if($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]){
		$titulo_raiz_navegacao = 'Inserir na raiz dos conteúdos';
		$titulo_navegacao = 'Inserir na lista de conteúdos: ';
		$link = 'raiz';
		$id_pai = '-1';
		$conteudo_arvore_2 = $_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"];
		
		if($conteudo_arvore_2){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$conteudo = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_pai',
				))
				,
				"conteudo",
				"WHERE id_conteudo='".$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]."'"
			);
			
			if((int)$conteudo[0]['id_conteudo_pai'] > 0){
				$id_pai = $conteudo[0]['id_conteudo_pai'];
				
				$conteudo2 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'titulo',
					))
					,
					"conteudo",
					"WHERE id_conteudo='".$id_pai."'"
				);
				
				$titulo_raiz_navegacao = $titulo_navegacao.$conteudo2[0]['titulo'];
				$link = $conteudo2[0]['titulo'];
			}
		}
		
		$informacao_acima = '
		<div class="lista_header">Nível Acima: 
			<a href="'.$_URL . '?opcao=modificar_caminho_raiz_novo&id='.$id_pai.'&id_filho='.$id.'&subir_nivel=1" title="'.$titulo_raiz_navegacao.'">
				<img src="'.$_HTML['separador'].$_HTML['ICONS'] . 'change-root-2.png'.'" border="0" /> '.$link.'
			</a>
		</div>';
	}
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	
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
	if($_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] || operacao('modificar_raiz')){
		if(operacao('adicionar')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=add_conteudo', // link da opção
				'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
				'img_coluna' => 3, // Coluna background image
				'img_linha' => 1, // Linha background image
				'name' => 'Adicionar', // Nome do menu
			);
		}
	}
	
	if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=modificar_caminho_raiz_novo&id=#id&id_filho='.$id, // link da opção
			'title' => 'Mudar Raiz d'.$_LISTA['ferramenta_unidade'].' para essa Lista de Conteúdos', // título da opção
			'img_coluna' => 10, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Mudar Raiz', // Legenda
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
		'campo' => 'Título', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'id', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		/* 'id' => 1, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
		'tabela' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'mudar_valor' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'padrao_se_nao_existe' => true,
		'valor_padrao_se_nao_existe' => '0',
		'campo' => 'hits', // OPCIONAL - Nome do campo da tabela
		'campo_id' => 'id_conteudo', // OPCIONAL - Nome do campo da tabela */
		'align' => 'center', // OPCIONAL - alinhamento horizontal */
		'width' => '60', // OPCIONAL - Tamanho horizontal
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
		'busca_titulo' => 'conteúdo', // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Habilitar legenda
		'input_ordem' => false, // Habilitar caixa salvar das ordens
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 2, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_conteudo!='".$id."' AND tipo='L' AND id_conteudo_pai='".($_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:'0')."' ", // Tabela extra
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
		
	);
	
	return $parametros;
}

function modificar_caminho_raiz(){
	global $_INTERFACE_OPCAO;
	
	if($_REQUEST['id']){
		$_INTERFACE_OPCAO = 'lista';
		
		return interface_layout(modificar_caminho_raiz_parametros_interface());
	} else {
		return lista();
	}
}

function modificar_caminho_raiz_filhos($params){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$conteudo = banco_select_name
	(
		banco_campos_virgulas(Array(
			'identificador',
			'caminho_raiz',
			'tipo',
		))
		,
		"conteudo",
		"WHERE id_conteudo='".$id."'"
	);
	
	if($conteudo){
		$tipo = $conteudo[0]['tipo'];
		$caminho_raiz = $conteudo[0]['caminho_raiz'];
		
		if($subir_nivel){
			$caminho_raiz = preg_replace('/'.$identificador.'\//i', '', $caminho_raiz);
		} else {
			if(!$identificador_pai && !$identificador_filho){
				if($caminho_raiz){
					$identificador_pai = rtrim($caminho_raiz,'/');
				} else {
					$identificador_filho = $conteudo[0]['identificador'];
				}
				
				$caminho_raiz .= $identificador . '/';
			} else {
				if($identificador_filho){
					$caminho_raiz = preg_replace('/'.$identificador_filho.'/i', $identificador . '/' . $identificador_filho, $caminho_raiz);
				} else {
					$caminho_raiz = preg_replace('/'.$identificador_pai.'/i', $identificador_pai . '/' . $identificador, $caminho_raiz);
				}
			}
		}
		
		banco_update
		(
			"caminho_raiz='".$caminho_raiz."'",
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		
		if($tipo == 'L'){
			$conteudo2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
				))
				,
				"conteudo",
				"WHERE id_conteudo_pai='".$id."'"
				." AND status!='D'"
			);
			
			if($conteudo2){
				foreach($conteudo2 as $con){
					modificar_caminho_raiz_filhos(Array(
						'id' => $con['id_conteudo'],
						'subir_nivel' => $subir_nivel,
						'identificador' => $identificador,
						'identificador_pai' => $identificador_pai,
						'identificador_filho' => $identificador_filho,
					));
				}
			}
		}
	}
}

function modificar_caminho_raiz_novo(){
	$id = $_REQUEST['id_filho'];
	$id_pai = $_REQUEST['id'];
	$subir_nivel = $_REQUEST['subir_nivel'];
	
	if($id_pai == '-1'){
		$id_pai = '0';
	}
	
	banco_conectar();
	
	if($subir_nivel){
		$pai_atual = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_pai',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'identificador',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$pai_atual[0]['id_conteudo_pai']."'"
		);
		
		$identificador = $conteudo[0]['identificador'];
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'identificador',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id_pai."'"
		);
		
		$identificador = $conteudo[0]['identificador'];
	}
	
	banco_update
	(
		"id_conteudo_pai='".$id_pai."'",
		"conteudo",
		"WHERE id_conteudo='".$id."'"
	);
	
	modificar_caminho_raiz_filhos(Array(
		'id' => $id,
		'subir_nivel' => $subir_nivel,
		'identificador' => $identificador,
	));
	
	if($id_pai == '0') return raiz(); else return lista_conteudo();
}

function modificar_caminho_raiz_filhos_2($params){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$conteudo = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
			'caminho_raiz',
			'tipo',
		))
		,
		"conteudo",
		"WHERE id_conteudo_pai='".$id."'"
		." AND status!='D'"
	);
	
	if($conteudo)
	foreach($conteudo as $con){
		$tipo = $con['tipo'];
		$caminho_raiz = $con['caminho_raiz'];
		$id_conteudo = $con['id_conteudo'];
		
		$caminho_raiz = preg_replace('/'.$identificador.'/i', $identificador_novo, $caminho_raiz);
		
		banco_update
		(
			"caminho_raiz='".$caminho_raiz."'",
			"conteudo",
			"WHERE id_conteudo='".$id_conteudo."'"
		);
		
		if($tipo == 'L'){
			modificar_caminho_raiz_filhos_2(Array(
				'id' => $id_conteudo,
				'identificador' => $identificador,
				'identificador_novo' => $identificador_novo,
			));
		}
	}
}

// ======================================= Configuração dos Conteúdos ===============================================

function permisao_modelo($id,$pai = false,$nivel = 1){
	global $_LISTA;
	
	$campos_todos = array_merge($_LISTA['campos'],$_LISTA['campos_extra'],$_LISTA['campos_extra_texto']);
	
	if($pai){
		if($nivel < 10){
			$permisao = banco_select_name
			(
				banco_campos_virgulas($campos_todos)
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'".
				" AND tipo='".($nivel)."'"
			);
		} else {
			return false;
		}
	}
	
	if($permisao){
		return $permisao[0];
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
			return permisao_modelo($conteudo[0]['id_conteudo_pai'],true,$nivel+1);
		else
			return false;
	}
}

function permisao_modelo_acima($id,$tipo,$pai = false,$nivel = 0){
	global $_LISTA;
	
	$campos_todos = array_merge($_LISTA['campos'],$_LISTA['campos_extra'],$_LISTA['campos_extra_texto']);
	
	if($pai){
		if($nivel < 10){
			$permisao = banco_select_name
			(
				banco_campos_virgulas($campos_todos)
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'".
				" AND tipo='".($nivel+(int)$tipo)."'"
			);
		} else {
			return false;
		}
	}
	
	if($permisao){
		return $permisao[0];
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
			return permisao_modelo_acima($conteudo[0]['id_conteudo_pai'],$tipo,true,$nivel+1);
		else
			return false;
	}
}

function permisao_pai($id,$pai = false,$nivel = 0){
	global $_LISTA;
	
	$campos_todos = array_merge($_LISTA['campos'],$_LISTA['campos_extra'],$_LISTA['campos_extra_texto']);
	
	if($pai){
		$permisao = banco_select_name
		(
			banco_campos_virgulas($campos_todos)
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'".
			" AND tipo='".$nivel."'"
		);
		
		if(!$permisao){
			$permisao = banco_select_name
			(
				banco_campos_virgulas($campos_todos)
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'".
				" AND tipo='L'"
			);
		}
	}
	
	if($permisao){
		return $permisao[0];
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
			return permisao_pai($conteudo[0]['id_conteudo_pai'],true,$nivel + 1);
		else
			return Array();
	}
}

function permisao_nivel($params){
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
		'onchange' => true,
		'extra_select' => false,
		'option_value_igual_nome' => false,
	));
	*/
	
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	$nome = $params['nome'];
	$resetar = $params['resetar'];
	$opcao_inicial = $params['opcao_inicial'];
	$link_extra = $params['link_extra'];
	$onchange = $params['onchange'];
	$option_value_igual_nome = $params['option_value_igual_nome'];
	$extra_select = $params['extra_select'];
	$url = $params['url'];
	$id = $nome . '_id';
	
	if($resetar)	$_SESSION[$_SYSTEM['ID'].$id] = false;
	if($_REQUEST[$id])	$_SESSION[$_SYSTEM['ID'].$id] = $_REQUEST[$id];
	
	if($opcao_inicial){
		$options[] = "1";
		$optionsValue[] = "L";
	}
	
	$cont = 0;
	for($i=2;$i<10;$i++){
		$options[] = $i;
		$optionsValue[] = $i;
		
		$cont++;
		
		if($_SESSION[$_SYSTEM['ID'].$id] == $i){
			$optionSelected = $cont;
			if(!$opcao_inicial)$optionSelected--;
		}
	}
	
	if(!$optionSelected && $cont == 1){
		$optionSelected = 1;
		if(!$opcao_inicial)$optionSelected--;
	}
	
	if($link_extra)$link_extra .= '&';
	$url .= '?';
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,($onchange ? 'onchange=window.open("'.$url.$link_extra.$id.'="+this.value,"_self")' : '') . ($extra_select ? ($onchange ? ' ' : '') . $extra_select : ''));
	
	return $select;
}

function permisao_nivel_ids($params){
	global $_CONEXAO_BANCO;
	
	$id = $params['id'];
	$nivel = $params['nivel'];
	$nivel_atual = $params['nivel_atual'];
	
	if(!$nivel_atual)$nivel_atual = 1;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($nivel_atual == $nivel){
		$conteudo_permissao = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_permissao',
				'id_conteudo',
			))
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'"
			." AND tipo='L'"
		);
		
		if($conteudo_permissao)
		foreach($conteudo_permissao as $cont_perm){
			$retorno[] = Array(
				'id_conteudo_permissao' => $cont_perm['id_conteudo_permissao'],
				'id_conteudo' => $cont_perm['id_conteudo'],
				'sitemap' => true,
			);
		}
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
			))
			,
			"conteudo",
			"WHERE id_conteudo_pai='".$id."'"
			." AND tipo='L'"
			." AND status!='D'"
		);
		
		if($conteudo)
		foreach($conteudo as $cont){
			$conteudo_permissao = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_permissao',
				))
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$cont['id_conteudo']."'"
				." AND tipo='".($nivel - $nivel_atual)."'"
			);
			
			if($conteudo_permissao){
				$retorno[] = Array(
					'id_conteudo_permissao' => $conteudo_permissao[0]['id_conteudo_permissao'],
					'id_conteudo' => $cont['id_conteudo'],
					'sitemap' => false,
				);
			}
			
			$retorno_filhos = permisao_nivel_ids(Array(
				'id' => $cont['id_conteudo'],
				'nivel' => $nivel,
				'nivel_atual' => $nivel_atual+1,
			));
			
			if($retorno_filhos){
				if($retorno){
					$retorno = array_merge($retorno,$retorno_filhos);
				} else {
					$retorno = $retorno_filhos;
				}
			}
		}
	}
	
	return $retorno;
}

function permisao(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	
	if(!$id){
		$id = '0';
	}
	
	$modelo = paginaModelo('html.html');
	$pagina = paginaTagValor($modelo,'<!-- form < -->','<!-- form > -->');
	$remover = paginaTagValor($modelo,'<!-- remover < -->','<!-- remover > -->');
	
	$modelo = paginaModelo($_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'index.html');
	$layout_padrao = paginaTagValor($modelo,'<!-- conteudo < -->','<!-- conteudo > -->');
	
	if($_REQUEST["opcao"] == 'permissao_lista'){
		$titulo_opcao = 'do Conjunto de Conteúdos';
		$tipo = 'L';
	} else {
		$titulo_opcao = 'do Conteúdo';
		$tipo = 'C';
		$cel_nome = 'nivel'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	$params = Array(
		'nome' => 'nivel',
		'link_extra' => 'opcao=permissao_lista&id='.$id,
		'opcao_inicial' => true,
		'onchange' => true,
		'resetar' => ($_REQUEST['nivel_id']?false:true),
	);
	
	if($_REQUEST['nivel_id'])$tipo = $_REQUEST['nivel_id'];
	
	$pagina = modelo_var_troca($pagina,"#nivel#",permisao_nivel($params));
	
	if($id == '0' && $tipo == 'L')$cel_nome = 'deletar'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$modulos_inativos = modulos_sistema_inativos();
	
	$campos_todos = array_merge($_LISTA['campos'],$_LISTA['campos_extra']);
	
	$campos_texto = $_LISTA['campos_extra_texto'];
	
	$campos_todos_banco = array_merge($campos_todos,$campos_texto);
	
	$tabela2 = banco_select_name
	(
		banco_campos_virgulas(
			$campos_todos_banco
		)
		,
		$_LISTA['tabela2']['nome'],
		"WHERE id_conteudo='".$id."'".
		" AND tipo='".$tipo."'"
	);
	
	$campos_guardar = Array(
		'permissao' => ($tabela2?true:false),
	);
	
	if(!$tabela2){
		if($tipo != 'L' && $tipo != 'C'){
			$tabela2[0] = permisao_modelo_acima($id,$tipo);
		} else {
			$tabela2[0] = permisao_pai($id);
		}
	}
	
	foreach($campos_todos_banco as $campo){
		$campos_guardar[$campo] = $tabela2[0][$campo];
	}
	
	$checked = ' checked="checked"';
	
	foreach($campos_todos as $campo){
		switch($campo){
			case 'titulo_img_recorte_y':
			case 'imagem_pequena_recorte_y':
			case 'imagem_grande_recorte_y':
				$pagina = paginaTrocaVarValor($pagina,'#CHK_'.$campo.'1#',($tabela2[0][$campo]?'':$checked));
				$pagina = paginaTrocaVarValor($pagina,'#CHK_'.$campo.'2#',($tabela2[0][$campo]?$checked:''));
			break;
			default: 
				$pagina = paginaTrocaVarValor($pagina,'#CHK_'.$campo.'#',($tabela2[0][$campo]?$checked:''));
		}
	}
	
	foreach($campos_texto as $campo){
		switch($campo){
			case 'titulo_img_mask':
			case 'imagem_pequena_mask':
			case 'imagem_grande_mask':
				if($tabela2[0][$campo]){
					list($mask_imagem,$mask_posicao) = explode(';',$tabela2[0][$campo]);
					list($mask_posicionar,$mask_posicao_hor,$mask_posicao_ver) = explode(',',$mask_posicao);
					
					if($mask_posicionar == '1'){
						$pos1 = $checked;
						$pos2 = '';
						$pos3 = '';
					} else if($mask_posicionar == '2'){
						$pos1 = '';
						$pos2 = $checked;
						$pos3 = '';
					} else {
						$pos1 = '';
						$pos2 = '';
						$pos3 = $checked;
					}
					
					if($mask_imagem){
						$mask_imagem = htmlImage($_CAMINHO_RELATIVO_RAIZ.$mask_imagem.$versao,$width,$height,$border,$id,'style="max-width:750px;"');
				
						$remover_aux = $remover;
						$remover_aux = modelo_var_troca($remover_aux,"#link#",'?opcao=remover_item&conteudo_permissao=sim&item='.$campo.'&id='.$id.'&tipo='.$tipo.($_REQUEST["opcao"] == 'permissao_lista'?'&permissao_lista=sim':''));
						$mask_imagem = $remover_aux . $mask_imagem;
					}
				} else {
					$mask_imagem = '';
					$pos1 = $checked;
					$pos2 = '';
					$pos3 = '';
					$mask_posicao_hor = '';
					$mask_posicao_ver = '';
				}
				
				$_SESSION[$_SYSTEM['ID'].$campo.'_pos_hor'.'_id'] = $mask_posicao_hor;
				$_SESSION[$_SYSTEM['ID'].$campo.'_pos_ver'.'_id'] = $mask_posicao_ver;
				
				$pagina = paginaTrocaVarValor($pagina,'#'.$campo.'#',$mask_imagem);
				$pagina = paginaTrocaVarValor($pagina,'#CHK_'.$campo.'_pos1#',$pos1);
				$pagina = paginaTrocaVarValor($pagina,'#CHK_'.$campo.'_pos2#',$pos2);
				$pagina = paginaTrocaVarValor($pagina,'#CHK_'.$campo.'_pos3#',$pos3);
				$pagina = paginaTrocaVarValor($pagina,'#'.$campo.'_pos_hor#',permissao_mask_select_hor($campo));
				$pagina = paginaTrocaVarValor($pagina,'#'.$campo.'_pos_ver#',permissao_mask_select_ver($campo));
			break;
			case 'conteiner_posicao_efeito':
				$pagina = paginaTrocaVarValor($pagina,'#'.$campo.'#',permissao_conteiner_posicao_efeito($campo,$tabela2[0][$campo]));
			break;
			default: 
				if($campo == 'layout')$valor = $layout_padrao; else $valor = '';
				
				$pagina = paginaTrocaVarValor($pagina,'#'.$campo.'#',($tabela2[0][$campo]?$tabela2[0][$campo]:$valor));
		}
	}
	
	$pagina = paginaTrocaVarValor($pagina,"#_titulo_opcao#",$titulo_opcao);
	
	if($modulos_inativos)
	foreach($modulos_inativos as $modulo => $valor){
		$pagina = modelo_tag_in($pagina,'<!-- '.$modulo.' < -->','<!-- '.$modulo.' > -->','');
	}
	
	// ======================================================================================
	
	campos_antes_guardar($campos_guardar);
	
	$in_titulo = "Permissão";
	$botao = "Gravar";
	$opcao = "permisao_base";
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url#",$_LOCAL_ID);
	$pagina = modelo_var_troca_tudo($pagina,"#botao#",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao#",$opcao);
	$pagina = modelo_var_troca_tudo($pagina,"#id#",$id);
	$pagina = modelo_var_troca_tudo($pagina,"#tipo#",$tipo);
	
	$_INTERFACE_OPCAO = 'editar'; 
	$_INTERFACE['local'] = 'permissao';
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function permisao_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_MASK;
	
	$campos_extra = $_LISTA['campos_extra'];
	$campos_extra_texto = $_LISTA['campos_extra_texto'];
	
	$id = $_POST["id"];
	$tipo = $_POST["tipo"];
	
	if(!$id)
		$id = '0';
	
	$campos_antes = campos_antes_recuperar();
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	// ================================= Local de Edição ===============================
	// Altere os campos da tabela e POST aqui, e modifique o UPDATE
	
	if($campos_antes['permissao']){
		$campo_tabela = "tabela2";
		
		foreach($_LISTA['campos'] as $campo){
			$campo_nome = $campo; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome]? '1' : '0');}
		}
		
		foreach($campos_extra as $campo){
			if(
				$campo == 'titulo_img_recorte_y' ||
				$campo == 'imagem_pequena_recorte_y' ||
				$campo == 'imagem_grande_recorte_y'
			){
				$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome] == '2'? '1' : '0');
			} else {
				$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome]? '1' : '0');
			}
		}
		
		foreach($campos_extra_texto as $campo){
			switch($campo){
				case 'titulo_img_mask':
				case 'imagem_pequena_mask':
				case 'imagem_grande_mask':
					list($mask_imagem,$mask_posicao) = explode(';',$campos_antes[$campo]);
					$_PERMISSAO_MASK[$campo] = Array(
						'mask_imagem' => $mask_imagem,
						'mask' => '#file#;'.$_REQUEST[$campo.'_pos'].','.$_REQUEST[$campo.'_pos_hor'].','.$_REQUEST[$campo.'_pos_ver'],
					);
				break;
				default:
					$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";
			}
		}
		
		$campo_nome = 'no_robots'; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome]? '1' : '0'); $no_robots = true; }
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela2']['nome'],
				"WHERE id_conteudo='".$id."'".
				" AND tipo='".$tipo."'"
			);
		}
	} else {	
		$campo_nome = "id_conteudo"; 										$campos[] = Array($campo_nome,$id);
		$campo_nome = "tipo"; 												$campos[] = Array($campo_nome,$tipo);
		
		foreach($_LISTA['campos'] as $campo){
			$campo_nome = $campo; $post_nome = $campo_nome; 				if($_POST[$post_nome]){		$campos[] = Array($campo_nome,$_POST[$post_nome]); $permissao[$campo_nome] = true;		}
		}
		
		foreach($campos_extra as $campo){
			if(
				$campo == 'titulo_img_recorte_y' ||
				$campo == 'imagem_pequena_recorte_y' ||
				$campo == 'imagem_grande_recorte_y'
			){
				$campo_nome = $campo; $post_nome = $campo_nome; 				if($_POST[$post_nome] == '2'){		$campos[] = Array($campo_nome,'1'); $permissao[$campo_nome] = true;		}
			} else {
				$campo_nome = $campo; $post_nome = $campo_nome; 				if($_POST[$post_nome]){		$campos[] = Array($campo_nome,$_POST[$post_nome]); $permissao[$campo_nome] = true;		}
			}
		}
		
		foreach($campos_extra_texto as $campo){
			switch($campo){
				case 'no_robots':
					if($_POST[$campo])$no_robots = true;
				break;
				case 'titulo_img_mask':
				case 'imagem_pequena_mask':
				case 'imagem_grande_mask':
					list($mask_imagem,$mask_posicao) = explode(';',$campos_antes[$campo]);
					
					$_PERMISSAO_MASK[$campo] = Array(
						'insert' => true,
						'mask_imagem' => $mask_imagem,
						'mask' => '#file#;'.$_REQUEST[$campo.'_pos'].','.$_REQUEST[$campo.'_pos_hor'].','.$_REQUEST[$campo.'_pos_ver'],
					);
				break;
				default:
					$campo_nome = $campo; $post_nome = $campo_nome; 				if($_POST[$post_nome]){		$campos[] = Array($campo_nome,$_POST[$post_nome]); $permissao[$campo_nome] = true;		}
			}
		}
		
		banco_insert_name($campos,$_LISTA['tabela2']['nome']);
	}
	
	$masks[] = 'titulo_img_mask';
	$masks[] = 'imagem_pequena_mask';
	$masks[] = 'imagem_grande_mask';
	
	if($masks)
	foreach($masks as $mask){
		permissao_mask_guardar($_FILES[$mask],$mask,$id,$tipo);
	}
	
	if($_REQUEST['nivel']){
		if($_REQUEST['nivel'] == 'L'){
			$nivel = 1;
		} else {
			$nivel = (int)$_REQUEST['nivel'];
		}
		
		$permissoes_listas = permisao_nivel_ids(Array(
			'id' => $id,
			'nivel' => $nivel,
		));
		
		if($permissoes_listas){
			
			$campo_tabela = "tabela3";
			
			foreach($_LISTA['campos'] as $campo){
				$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome]? '1' : '0');
			}
			
			foreach($campos_extra as $campo){
				if(
					$campo == 'titulo_img_recorte_y' ||
					$campo == 'imagem_pequena_recorte_y' ||
					$campo == 'imagem_grande_recorte_y'
				){
					$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome] == '2'? '1' : '0');
				} else {
					$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome]? '1' : '0');
				}
			}
			
			foreach($campos_extra_texto as $campo){
				switch($campo){
					case 'titulo_img_mask':
					case 'imagem_pequena_mask':
					case 'imagem_grande_mask':
						$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."='" . $_PERMISSAO_MASK[$campo]['campo_formatado'] . "'";
					break;
					default:
						$campo_nome = $campo; $editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";
				}
			}
			
			$campo_nome = 'no_robots'; $editar[$campo_tabela][] = $campo_nome."=" . ($_POST[$campo_nome]? '1' : '0');
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				foreach($permissoes_listas as $permissao){
					banco_update
					(
						$editar_sql[$campo_tabela],
						$_LISTA['tabela2']['nome'],
						"WHERE id_conteudo_permissao='".$permissao['id_conteudo_permissao']."'"
					);
					
					if($permissao['sitemap']){
						permisao_no_robots($permissao['id_conteudo']);
					}
				}
			}
		}
	}
	
	if($no_robots){
		if($tipo == 'C'){
			banco_update
			(
				"sitemap=1",
				$_LISTA['tabela']['nome'],
				"WHERE id_conteudo='".$id."'"
			);
		} else {
			permisao_no_robots($id);
		}
	}
	
	// ======================================================================================
	
	return lista();
}

function permisao_deletar(){
	global $_CONEXAO_BANCO;
	
	$id = $_REQUEST['id'];
	$tipo = $_REQUEST['tipo'];
	
	if(!$id && $tipo == 'L'){
		return lista();
	} else {
		if(!$_CONEXAO_BANCO)banco_conectar();
		banco_delete
		(
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."' AND tipo='".$tipo."'"
		);
	}
	
	return lista();
}

function permisao_no_robots($id){
	global $_LISTA;
	
	$tabela = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
			'tipo',
		))
		,
		$_LISTA['tabela']['nome'],
		"WHERE id_conteudo_pai='".$id."'"
	);
	
	if($tabela){
		foreach($tabela as $conteudo){
			banco_update
			(
				"sitemap=1",
				$_LISTA['tabela']['nome'],
				"WHERE id_conteudo='".$conteudo['id_conteudo']."'"
			);
			
			
			if($conteudo['tipo'] == 'L'){
				permisao_no_robots($conteudo['id_conteudo']);
			}
		}
	}
}

function permisao_conteudo($id,$pai = false,$nivel = 0){
	global $_LISTA;
	
	$insert = Array(
		'conteiner_posicao',
	);
	
	$campos = array_merge($_LISTA['campos'],$insert);
	
	if(!$pai){
		$permisao = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$_LISTA['tabela2']['nome'],
			"WHERE id_conteudo='".$id."'".
			" AND tipo='C'"
		);
	} else {
		if($nivel == 0) $nivel = 1;
		$permisao2 = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$_LISTA['tabela2']['nome'],
			"WHERE id_conteudo='".$id."'".
			" AND tipo='".$nivel."'"
		);
		
		if(!$permisao2){
			$permisao2 = banco_select_name
			(
				banco_campos_virgulas($campos)
				,
				$_LISTA['tabela2']['nome'],
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
			$_LISTA['tabela']['nome'],
			"WHERE id_conteudo='".$id."'"
		);
		
		if($conteudo)
			return permisao_conteudo($conteudo[0]['id_conteudo_pai'],true,$nivel+1);
		else
			return Array();
	}
}

function permisao_conteudo_tudo($id,$pai = false,$nivel = 0){
	global $_LISTA;
	
	$campos_todos = array_merge($_LISTA['campos'],$_LISTA['campos_extra'],$_LISTA['campos_extra_texto']);

	if(!$pai){
		$permisao = banco_select_name
		(
			banco_campos_virgulas($campos_todos)
			,
			$_LISTA['tabela2']['nome'],
			"WHERE id_conteudo='".$id."'".
			" AND tipo='C'"
		);
	} else {
		if($nivel == 0) $nivel = 1;	
		$permisao2 = banco_select_name
		(
			banco_campos_virgulas($campos_todos)
			,
			$_LISTA['tabela2']['nome'],
			"WHERE id_conteudo='".$id."'".
			" AND tipo='".$nivel."'"
		);
		
		if(!$permisao2){
			$permisao2 = banco_select_name
			(
				banco_campos_virgulas($campos_todos)
				,
				$_LISTA['tabela2']['nome'],
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
			$_LISTA['tabela']['nome'],
			"WHERE id_conteudo='".$id."'"
		);
		
		if($conteudo)
			return permisao_conteudo_tudo($conteudo[0]['id_conteudo_pai'],true,$nivel + 1);
		else
			return Array();
	}
}

function permissao_mask_select_hor($campo){
	global $_BANCO_PREFIXO,
	$_SYSTEM,
	$_PAGINA_LOCAL,
	$_URL;
	
	$nome = $campo.'_pos_hor';
	$id = $nome . '_id';
	
	$opcoes = Array(
		Array("Esquerda" , "esquerda"),
		Array("Centro" , "centro"),
		Array("Direita" , "direita"),
	);
	
	for($i=0;$i<count($opcoes);$i++){
		$options[] = $opcoes[$i][0];
		$optionsValue[] = $opcoes[$i][1];
		
		if($_SESSION[$_SYSTEM['ID'].$id] == $opcoes[$i][1]){
			$optionSelected = $i;
		}
	}
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,$extra);
	
	return $select;
}

function permissao_mask_select_ver($campo){
	global $_BANCO_PREFIXO,
	$_SYSTEM,
	$_PAGINA_LOCAL,
	$_URL;
	
	$nome = $campo.'_pos_ver';
	$id = $nome . '_id';
	
	$opcoes = Array(
		Array("Topo" , "topo"),
		Array("Meio" , "meio"),
		Array("Baixo" , "baixo"),
	);
	
	for($i=0;$i<count($opcoes);$i++){
		$options[] = $opcoes[$i][0];
		$optionsValue[] = $opcoes[$i][1];
		
		if($_SESSION[$_SYSTEM['ID'].$id] == $opcoes[$i][1]){
			$optionSelected = $i;
		}
	}
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,$extra);
	
	return $select;
}

function permissao_conteiner_posicao_efeito($campo,$valor){
	global $_BANCO_PREFIXO,
	$_SYSTEM,
	$_PAGINA_LOCAL,
	$_URL;
	
	$nome = $campo;
	
	$opcoes = Array(
		Array("Nenhum",""),
		Array("linear","linear"),
		Array("swing","swing"),
		Array("easeInQuad","easeInQuad"),
		Array("easeOutQuad","easeOutQuad"),
		Array("easeInOutQuad","easeInOutQuad"),
		Array("easeInCubic","easeInCubic"),
		Array("easeOutCubic","easeOutCubic"),
		Array("easeInOutCubic","easeInOutCubic"),
		Array("easeInQuart","easeInQuart"),
		Array("easeOutQuart","easeOutQuart"),
		Array("easeInOutQuart","easeInOutQuart"),
		Array("easeInQuint","easeInQuint"),
		Array("easeOutQuint","easeOutQuint"),
		Array("easeInOutQuint","easeInOutQuint"),
		Array("easeInSine","easeInSine"),
		Array("easeOutSine","easeOutSine"),
		Array("easeInOutSine","easeInOutSine"),
		Array("easeInExpo","easeInExpo"),
		Array("easeOutExpo","easeOutExpo"),
		Array("easeInOutExpo","easeInOutExpo"),
		Array("easeInCirc","easeInCirc"),
		Array("easeOutCirc","easeOutCirc"),
		Array("easeInOutCirc","easeInOutCirc"),
		Array("easeInElastic","easeInElastic"),
		Array("easeOutElastic","easeOutElastic"),
		Array("easeInOutElastic","easeInOutElastic"),
		Array("easeInBack","easeInBack"),
		Array("easeOutBack","easeOutBack"),
		Array("easeInOutBack","easeInOutBack"),
		Array("easeInBounce","easeInBounce"),
		Array("easeOutBounce","easeOutBounce"),
		Array("easeInOutBounce","easeInOutBounce"),
	);
	
	for($i=0;$i<count($opcoes);$i++){
		$options[] = ($i>0?$i . ". ":"") . $opcoes[$i][0];
		$optionsValue[] = $opcoes[$i][1];
		
		if($valor == $opcoes[$i][1]){
			$optionSelected = $i;
		}
	}
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,$extra);
	
	return $select;
}

function permissao_mask_guardar($uploaded,$campo,$id,$tipo){
	global $_LISTA;
	global $_SYSTEM;
	global $_PERMISSAO_MASK;
	
	$imagem = $_PERMISSAO_MASK[$campo]['mask_imagem'];
	$insert = $_PERMISSAO_MASK[$campo]['insert'];
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/";
	
	if(
		$uploaded['size'] != 0
	){
		if
		(
			$uploaded['type'] == mime_types("jpe") ||
			$uploaded['type'] == mime_types("jpeg") ||
			$uploaded['type'] == mime_types("jpg") ||
			$uploaded['type'] == mime_types("pjpeg") ||
			$uploaded['type'] == mime_types("png") ||
			$uploaded['type'] == mime_types("x-png") ||
			$uploaded['type'] == mime_types("gif")
		){
			$cadastrar = true;
		}
	} else if($imagem && $insert){
		$imagem_modelo = str_replace($caminho_internet,$caminho_fisico,$imagem);
		
		$path_parts = pathinfo($imagem_modelo);
		$extensao = $path_parts['extension'];
		
		$nome_arquivo = $campo . $id . "_" . $tipo . "." . $extensao;

		$imagem_destino = $caminho_fisico 	. $nome_arquivo;
		
		copy($imagem_modelo,$imagem_destino);
		chmod($imagem_destino , 0777);
		
		$imagem = $caminho_internet.$nome_arquivo;
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
		}
		
		$nome_arquivo = $campo . $id . "_" . $tipo . "." . $extensao;
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			$imagem = $caminho_internet.$nome_arquivo;
		}
	}
	
	$campo_formatado = modelo_var_troca($_PERMISSAO_MASK[$campo]['mask'],"#file#",$imagem);
	
	banco_update
	(
		$campo."='".$campo_formatado."'",
		$_LISTA['tabela2']['nome'],
		"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		." AND tipo='".$tipo."'"
	);
	
	$_PERMISSAO_MASK[$campo]['campo_formatado'] = $campo_formatado;
}

// ======================================= Manipulação de Arquivos ===============================================

function arquivo_real_unico($id,$ext,$num = 0){
	global $_SYSTEM;
	
	$file = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].($num ? $id.'-'.$num : $id).'.'.$ext;
	
	if(is_file($file)){
		return arquivo_real_unico($id,$ext,$num + 1);
	} else {
		return ($num ? $id.'-'.$num : $id).'.'.$ext;
	}
}

function criar_arquivo_real($id,$ext){
	$id = preg_replace('/.'.$ext.'/i', '', $id);
	
	$tam_max_id = 80;
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
	if(is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		return arquivo_real_unico($id,$ext,$num);
	} else {
		return arquivo_real_unico($id,$ext);
	}
}

function mudar_arquivo_nome($file,$new_name){
	global $_SYSTEM;
	
	if($file){
		$path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'];
		$file = preg_replace('/files\//i', '', $file);
		
		if(is_file($path.$file)){
			$path_info = pathinfo($path.$file);
			$new_name = criar_arquivo_real($new_name,$path_info['extension']);
			rename($path.$file,$path.$new_name);
			$mini = $path_info['filename'].'-mini.'.$path_info['extension'];
			if(is_file($path.$mini)){
				$new_name_mini = criar_arquivo_real($new_name.'-mini',$path_info['extension']);
				rename($path.$mini,$path.$new_name_mini);
			}
			
			return 'files/'.$new_name;
		} else {
			return false;
		}
	} else {
		return false;
	} 
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/";
	
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
		
		if($_REQUEST[$campo."_"."name"]){
			if($old_name){
				$old_name = preg_replace('/files\//i', '', $old_name);
				
				$nome_arquivo = $old_name;
				
				if($_PROJETO['conteudo']['criar-miniatura']){
					$path_info = pathinfo($path.$old_name);
					$mini_nome = $path_info['filename'].'-mini.'.$path_info['extension'];
				}
				
				if($_PROJETO['conteudo']['criar-imagem-escala-cinza']){
					$path_info = pathinfo($path.$old_name);
					$pb_nome = $path_info['filename'].'-pb.'.$path_info['extension'];
				}
				
				if($_PROJETO['conteudo']['criar-miniatura-escala-cinza']){
					$path_info = pathinfo($path.$old_name);
					$pb_mini_nome = $path_info['filename'].'-mini-pb.'.$path_info['extension'];
				}
				
			} else {
				$nome_arquivo = criar_arquivo_real($_REQUEST[$campo."_"."name"],$extensao);
				
				if($_PROJETO['conteudo']['criar-miniatura']){
					$mini_nome = criar_arquivo_real($nome_arquivo . "-mini",$extensao);
				}
				
				if($_PROJETO['conteudo']['criar-imagem-escala-cinza']){
					$pb_nome = criar_arquivo_real($nome_arquivo . "-pb",$extensao);
				}
				
				if($_PROJETO['conteudo']['criar-miniatura-escala-cinza']){
					$pb_mini_nome = criar_arquivo_real($nome_arquivo . "-mini-pb",$extensao);
				}
			}
		} else {
			$nome_arquivo = $campo . $id_tabela . "." . $extensao;
			
			if($_PROJETO['conteudo']['criar-miniatura']){
				$mini_nome = $campo . $id_tabela . "-mini." . $extensao;
			}
			if($_PROJETO['conteudo']['criar-imagem-escala-cinza']){
				$pb_nome = $campo . $id_tabela . "-pb." . $extensao;
			}
			
			if($_PROJETO['conteudo']['criar-miniatura-escala-cinza']){
				$pb_mini_nome = $campo . $id_tabela . "-mini-pb." . $extensao;
			}
		}
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			$original = $caminho_fisico . $nome_arquivo;
			
			if(
				$_PERMISSAO_CONTEUDO[$campo.'_width'] > 0 &&
				$_PERMISSAO_CONTEUDO[$campo.'_height'] > 0
			){
				$_RESIZE_IMAGE_Y_ZERO = false;
				if($_PERMISSAO_CONTEUDO[$campo.'_recorte_y'])$_RESIZE_IMAGE_Y_ZERO = true;
				resize_image($original, $original, $_PERMISSAO_CONTEUDO[$campo.'_width'], $_PERMISSAO_CONTEUDO[$campo.'_height'],false,false,true);
			}
			
			list($mask_imagem,$mask_posicao) = explode(';',$_PERMISSAO_CONTEUDO[$campo.'_mask']);
			
			if($mask_imagem){
				list($mask_posicionar,$mask_posicao_hor,$mask_posicao_ver) = explode(',',$mask_posicao);
				
				if($mask_posicionar != '3'){
					$mask_imagem = str_replace($caminho_internet,$caminho_fisico,$mask_imagem);
					image_mask($original, $original, $mask_imagem, ($mask_posicionar == '1'?false:true), $mask_posicao_hor, $mask_posicao_ver);
				}
			}
			
			if($_PROJETO['conteudo']['criar-miniatura']){
				if($_PROJETO['conteudo']['criar-miniatura']['width']) $width = $_PROJETO['conteudo']['criar-miniatura']['width']; else $width = $_SYSTEM['PROCURAR_IMG_WIDTH'];
				if($_PROJETO['conteudo']['criar-miniatura']['height']) $height = $_PROJETO['conteudo']['criar-miniatura']['height']; else $height = $_SYSTEM['PROCURAR_IMG_HEIGHT'];
				if($_PROJETO['conteudo']['criar-miniatura']['nao_moldurar']) $moldurar = false; else $moldurar = true;
				
				$mini = $caminho_fisico . $mini_nome;
				if(is_file($mini))$existe_mini = true;
				resize_image($original, $mini, $width, $height,false,false,$moldurar);
				if(!$existe_mini)chmod($mini , 0777);
			}
			
			if($_PROJETO['conteudo']['criar-imagem-escala-cinza']){
				$pb = $caminho_fisico . $pb_nome;
				if(is_file($pb))$existe_pb = true;
				filtrar_image($original, $pb, IMG_FILTER_GRAYSCALE);
				if(!$existe_pb)chmod($pb , 0777);
			}
			
			if($_PROJETO['conteudo']['criar-miniatura-escala-cinza']){
				$mini = $caminho_fisico . $mini_nome;
				$pb_mini = $caminho_fisico . $pb_mini_nome;
				if(is_file($pb_mini))$existe_pb_mini = true;
				filtrar_image($mini, $pb_mini, IMG_FILTER_GRAYSCALE);
				if(!$existe_pb_mini)chmod($pb_mini , 0777);
			}
		}
		
		banco_update
		(
			$campo."='".$caminho_internet.$nome_arquivo."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id_tabela."'"
		);
	}
}

// ======================================= Manipulação de Tags ===============================================

function conjunto_tags($params){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_conteudo_tags',
		))
		,
		"conteudo_tags",
		"ORDER BY nome ASC"
	);
	
	if($resultado){
		if($id){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_tags',
				))
				,
				"conteudo_conteudo_tags",
				"WHERE id_conteudo='".$id."'"
			);
		}
		
		$tags = '<!-- tags -->';
		$checked = ' checked="checked"';
		$mod_tags = '
		<div id="tags#id#" class="tags-conteiner-entry">
			<input type="checkbox" value="1" name="tags#id#" class="tags-checkbox"#checked# />
			<div class="tags-nome">#text#</div>
			<div class="tags-excluir"></div>
			<div class="clear"></div>
		</div>';
		
		foreach($resultado as $res){
			$selected = false;
			if($resultado2)
			foreach($resultado2 as $res2){
				if($res['id_conteudo_tags'] == $res2['id_conteudo_tags']){
					$selected = true;
					$tags_selecteds[] = $res2['id_conteudo_tags'];
					break;
				}
			}
			
			$cel_aux = $mod_tags;
			
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#id#",$res['id_conteudo_tags']);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#text#",$res['nome']);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#checked#",($selected?$checked:''));
			
			$tags = modelo_var_in($tags,'<!-- tags -->',$cel_aux);
		}
		$pagina = modelo_var_troca($pagina,'<!-- tags -->','');
	}
	
	$cont_tags = '
	<div id="tags-conteiner">
		'.$tags.'
		<div class="clear" id="tags-marcador"></div>
		<div id="tags-add">Adicionar Novas Tags</div>
		<div id="tags-add-cont">
			<label for="tags-add-texto">Nome da Tag:</label>
			<input type="text" id="tags-add-texto" maxlength="90" />
			<input type="button" id="tags-add-button" value="Adicionar" />
		</div>
		<div class="clear"></div>
		<input type="hidden" id="tags-flag" name="tags-flag" value="" />
	</div>';
	
	return Array(
		'tags' => $cont_tags,
		'tags_selecteds' => $tags_selecteds,
	);
}

// ======================================================================================

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	global $_CONEXAO_BANCO;
	
	if($_REQUEST['mp3_player']){
		$id = $_REQUEST['mp3_id'];
		
		if(!$_CONEXAO_BANCO)banco_conectar();
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
	global $_CONEXAO_BANCO;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;

		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D' AND id_conteudo_pai='".$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]."'"
		);

		for($i=0;$i<count($resultado);$i++){
			$saida[] = Array(
				'value' => utf8_encode($resultado[$i][1]),
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['del_tags']){
		$id = utf8_decode($_REQUEST["id"]);
		if(!$id) return;

		$id = preg_replace('/tags/i', '', $id);
		
		if(!$_CONEXAO_BANCO)banco_conectar();	
		banco_delete
		(
			"conteudo_conteudo_tags",
			"WHERE id_conteudo_tags='".$id."'"
		);
		banco_delete
		(
			"conteudo_tags",
			"WHERE id_conteudo_tags='".$id."'"
		);
		
		$saida = Array(
			'ok' => true,
		);
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['add_tags']){
		$text = utf8_decode($_REQUEST["text"]);
		if(!$text) return;

		if(!$_CONEXAO_BANCO)banco_conectar();
		$conteudo_tags = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
			))
			,
			"conteudo_tags",
			"WHERE UCASE(nome) = UCASE('" . $text . "')"
		);
		
		if($conteudo_tags){
			$saida = Array(
				'erro' => 1,
				'mens' => utf8_encode('<p>Já foi definida essa tag.</p>'),
			);
		} else {
			$tam_max_id = 90;
			$id = retirar_acentos(trim($text));
			
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
			
			$campos = null;
			
			$campo_nome = "nome"; $campo_valor = $text; 		$campos[] = Array($campo_nome,$campo_valor);
			$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor);
			
			banco_insert_name
			(
				$campos,
				"conteudo_tags"
			);
			
			$id_bd = banco_last_id();
			
			$saida = Array(
				'id' => $id_bd,
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['galeria_nome']){
		$id = utf8_decode($_REQUEST["galeria_id"]);
		if(!$id) return;

		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$resultado = banco_select
		(
			"nome",
			"galerias",
			"WHERE id_galerias='".$id."'"
		);
		
		$galeria_imagens = galerias($id);

		for($i=0;$i<count($resultado);$i++){
			$saida = Array(
				'nome' => $resultado[$i][0],
				'galeria_imagens' => utf8_encode($galeria_imagens),
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['galeria_grupo_nome']){
		$id = utf8_decode($_REQUEST["galeria_grupo_id"]);
		if(!$id) return;

		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$resultado = banco_select
		(
			"grupo",
			"galerias_grupos",
			"WHERE id_galerias_grupos='".$id."'"
		);

		for($i=0;$i<count($resultado);$i++){
			$saida = Array(
				'nome' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['videos_nome']){
		$id = utf8_decode($_REQUEST["videos_id"]);
		if(!$id) return;

		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$resultado = banco_select
		(
			"nome",
			"galerias_videos",
			"WHERE id_galerias_videos='".$id."'"
		);
		
		$galerias_videos = galerias_videos($id);

		for($i=0;$i<count($resultado);$i++){
			$saida = Array(
				'nome' => $resultado[$i][0],
				'galerias_videos' => utf8_encode($galerias_videos),
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['videos_grupo_nome']){
		$id = utf8_decode($_REQUEST["videos_grupo_id"]);
		if(!$id) return;

		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$resultado = banco_select
		(
			"grupo",
			"videos_grupos",
			"WHERE id_videos_grupos='".$id."'"
		);

		for($i=0;$i<count($resultado);$i++){
			$saida = Array(
				'nome' => $resultado[$i][0],
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
	global $_CONEXAO_BANCO;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if($_REQUEST[xml]){
		xml();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		$cal_id = $_SESSION[$_SYSTEM['ID'].'calendario_id'];
		if($opcoes == "conteudo_escolher"){
			if($_SESSION[$_SYSTEM['ID']."conteudo_escolher_id"]){$opcoes = 'editar'; $_REQUEST["id"] = $_SESSION[$_SYSTEM['ID']."conteudo_escolher_id"];} else {$opcoes = 'add_conteudo';}
		}
		$_SESSION[$_SYSTEM['ID']."conteudo_escolher"] = false;
		$_SESSION[$_SYSTEM['ID']."conteudo_escolher_id"] = false;
		
		if(!$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]){
			global $_CONEXAO_BANCO;
			
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
					'titulo',
					'identificador',
				))
				,
				"conteudo",
				"WHERE identificador='eventos'"
			);
			
			if($resultado){
				$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]		= 	$resultado[0]['id_conteudo'];
				$_SESSION[$_SYSTEM['ID']."titulo_pai"] 		= 	$resultado[0]['titulo'];
				
				$conteudo_arvore_2[] = Array(
					'id' => $resultado[0]['id_conteudo'],
					'nome' => $resultado[0]['titulo'],
					'identificador' => $resultado[0]['identificador'],
				);
				
				$_SESSION[$_SYSTEM['ID']."conteudo_arvore_2"] = array_reverse($conteudo_arvore_2);
			} else {
				$identificador = 'eventos';
				$titulo = 'Eventos';
				
				$campo_nome = "id_conteudo_pai"; 			$campos[] = Array($campo_nome,'0');
				$campo_nome = "tipo";  						$campos[] = Array($campo_nome,'L'); $permisao_modelo = true;
				$campo_nome = "identificador";  			$campos[] = Array($campo_nome,$identificador);
				$campo_nome = "titulo";  					$campos[] = Array($campo_nome,$titulo);
				$campo_nome = "data_automatica"; 			$campos[] = Array($campo_nome,'1');
				$campo_nome = "data"; 		 				$campos[] = Array($campo_nome,'NOW()',true);
				$campo_nome = "status"; 					$campos[] = Array($campo_nome,'A');
				$campo_nome = "versao";  					$campos[] = Array($campo_nome,'1');
				$campo_nome = "sitemap";  					$campos[] = Array($campo_nome,'1');
				$campo_nome = "rss";  						$campos[] = Array($campo_nome,'1');
				$campo_nome = "rss_redes";  				$campos[] = Array($campo_nome,'1');
				$campo_nome = "caminho_raiz"; 		 		$campos[] = Array($campo_nome,'');
				
				banco_insert_name($campos,$_LISTA['tabela']['nome']);
				$id_tabela = banco_last_id();
				
				if($permisao_modelo){
					$permisao_modelo = permisao_modelo($id_tabela);
					
					if($permisao_modelo){
						$campos = false;
						
						$campo_nome = "id_conteudo"; 										$campos[] = Array($campo_nome,$id_tabela);
						$campo_nome = "tipo"; 												$campos[] = Array($campo_nome,'L');
						
						foreach($permisao_modelo as $campo => $valor){
							if($valor)
							$campos[] = Array($campo,$valor);
						}
						
						banco_insert_name($campos,$_LISTA['tabela2']['nome']);
					}
				}
				
				$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"] = $id_tabela;
			}
		}
		
		switch($opcoes){
			case 'menu_'.$_LOCAL_ID:
			case 'menu_'.$_LOCAL_ID.$_SESSION[$_SYSTEM['ID']."conteudo_pai_2"]:
			case 'lista':						$saida = lista();break;
			case 'add_conteudo':				$saida = (operacao('adicionar') ? add_conteudo() : lista());break;
			case 'add_conteudo_base':			$saida = (operacao('adicionar') ? add_conteudo_base() : lista());break;
			case 'permissao_conteudo':
			case 'permissao_lista':				$saida = (operacao('configuracao') ? permisao() : lista());break;
			case 'permisao_base':				$saida = (operacao('configuracao') ? permisao_base() : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : lista());break;
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar_conteudo('ver') : lista());break;
			case 'editar':						$saida = (operacao('editar') ? editar_conteudo() : lista());break;
			case 'editar_conteudo_base':		$saida = (operacao('editar') ? editar_conteudo_base() : lista());break;
			//case 'modificar_caminho_raiz':		$saida = (operacao('editar') ? modificar_caminho_raiz() : lista());break;
			//case 'modificar_caminho_raiz_novo':	$saida = (operacao('editar') ? modificar_caminho_raiz_novo() : lista());break;
			case 'lista_conteudo':				$saida = lista_conteudo();break;
			case 'raiz':						$saida = raiz();break;
			case 'ordenacao':					$saida = ordenacao();break;
			case 'ordem':						$saida = ordem();break;
			case 'zerar_ordenacao':				$saida = zerar_ordenacao();break;
			case 'remover_item':				$saida = remover_item();break;
			case 'permisao_deletar':			$saida = permisao_deletar();break;
			default: 							$saida = lista();
		}
		
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
	
	if($_CONEXAO_BANCO)banco_fechar_conexao();
}

start();

?>