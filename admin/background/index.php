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

$_VERSAO_MODULO				=	'1.0.1';
$_LOCAL_ID					=	"background";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Background.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['swfUpload'].
$_JS['prettyPhoto'].
$_JS['jPlayer'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

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
	global $_VARS;
	global $_CONEXAO_BANCO;
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'descricao';
	
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
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
		'name' => 'Lista', // Nome do menu
	);
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=upload_imagens', // link da opção
			'title' => 'Enviar imagens', // título da opção
			'img_coluna' => 2, // Coluna background image
			'img_linha' => 2, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Enviar imagens', // Nome do menu
		);
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$background = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"variavel_global",
		"WHERE grupo='background'"
		." AND variavel='ativo'"
	);
	
	$background_ativo = $background[0]['valor'];
	
	if(operacao('bloquear')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=ativar_background&tipo='.($background_ativo?'B':'A'), // link da opção
			'title' => ($background_ativo?'Desativar':'Ativar').$_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . ($background_ativo?'ativo_grande_2':'bloqueado_grande_2') . '.png', // caminho da imagem
			'img_coluna' => ($background_ativo?6:7), // Coluna background image
			'img_linha' => 1, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => ($background_ativo?'Desativar':'Ativar'), // Nome do menu
		);
	}
	
	if(operacao('pasta_grupo')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=pasta_grupo&id=#id', // link da opção
			'title' => 'Pasta do Grupo', // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'pasta_usuario_mini.png', // caminho da imagem
			'legenda' => 'Pasta do Grupo', // Legenda
		);
	}
	if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'textos.png', // caminho da imagem
			'legenda' => 'Ver', // Legenda
		);
	}
	if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=editar&id=#id', // link da opção
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'editar.png', // caminho da imagem
			'legenda' => 'Editar', // Legenda
		);
	}
	if(operacao('bloquear')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
			'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo.png', // caminho da imagem
			'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado.png', // caminho da imagem
			'bloquear' => true, // Se eh botão de bloqueio
			'legenda' => 'Ativar/Desativar', // Legenda
		);
	}
	if(operacao('excluir')){
		$menu_opcoes[] = Array( // Opção: Excluir
			'url' => '#', // link da opção
			'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'excluir.png', // caminho da imagem
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
		'campo' => 'Título', // Valor do campo
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
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => false, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => false, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' ", // Tabela extra
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
	
	$_INTERFACE_OPCAO = 'nenhum';
	
	return interface_layout(parametros_interface());
}

function layout_imagens(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	global $_PAGINA_OPCAO;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_HTML;
	global $_VARS;
	
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	if(!$_SESSION[$_SYSTEM['ID']."upload_permissao"] && operacao('imagens_uploads')){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		$session_id = session_id();
		
		$_SESSION[$_SYSTEM['ID']."upload_permissao"] = Array(
			'usuario' => $usuario,
			'session_id' => $session_id,
		);
		
		banco_delete
		(
			"upload_permissao",
			"WHERE data <= (NOW() - INTERVAL 1 DAY)"
		);
		banco_insert
		(
			"'" . $usuario . "',".
			"'" . $session_id . "',".
			"NOW()",
			"upload_permissao"
		);
	}
	
	$upload_permissao = $_SESSION[$_SYSTEM['ID']."upload_permissao"];
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- layout_imagens < -->','<!-- layout_imagens > -->');
	
	if($_PAGINA_OPCAO == 'upload_imagens'){
		$upload_arquivos_flag = true;
	} else {
		if($_VARS['background']['dados']){
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta",lista_imagens());
		} else {
			$upload_arquivos_flag = true;
		}
	}
	
	if($upload_arquivos_flag){
		if(operacao('imagens_uploads')){
			$ferramenta = paginaModelo('html.html');
			$ferramenta = paginaTagValor($ferramenta,'<!-- upload_imagens < -->','<!-- upload_imagens > -->');
			
			$ferramenta = paginaTrocaVarValor($ferramenta,"#usuario",$upload_permissao['usuario']);
			$ferramenta = paginaTrocaVarValor($ferramenta,"#sessao",$upload_permissao['session_id']);
			
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta",$ferramenta);
		} else {
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta","Sem imagens/vídeos cadastrados.");
		}
	}
	
	$pagina = modelo_var_troca_tudo($pagina,"!#caminho#!",$_HTML['separador']);
	
	$in_titulo = "Background";
	
	$_INTERFACE_OPCAO = 'layout_imagens';
	$_INTERFACE['local'] = 'conteudo';
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_tipo'] = $tipo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function lista_imagens(){
	global $_SYSTEM;
	
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$background = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_variavel_global',
			'variavel',
			'valor',
		))
		,
		"variavel_global",
		"WHERE grupo='background'"
	);
	
	foreach($background as $back){
		$val = $back['valor'];
		switch($back['variavel']){
			case 'dados':
				$dados = $val;
				$dados_arr = explode(';',$dados);
			break;
			
		}
	}
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- lista_imagens < -->','<!-- lista_imagens > -->');
	
	if(!operacao('imagens_editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	if(!operacao('imagens_excluir'))$cel_nome = 'excluir'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$col1 = paginaTagValor($pagina,'<!-- col1 < -->','<!-- col1 > -->');
	$pagina = paginaTrocaTag($pagina,'<!-- col1 < -->','<!-- col1 > -->','<!-- col1 -->');
	
	foreach($dados_arr as $img_vid){
		if($img_vid){
			$img_vid_arr = explode(',',$img_vid);
			list($tipo,$status,$num,$original,$mini) = $img_vid_arr;
			
			$cel_aux = $col1;
			
			if($tipo == 'img'){
				$cel_aux = modelo_var_troca($cel_aux,"#img_vid#",imagens($num,$mini,$original,"Background ".$num));
			} else {
				$video = html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => Array(
						'src' => '/'.$_SYSTEM['ROOT'].$mini,
						'class' => 'video_call fotos-imagens-link',
					),
				));
				$video = html(Array(
					'tag' => 'a',
					'val' => $video,
					'attr' => Array(
						'href' => 'popup.php?video='.$original.'&iframe=true&width=660&height=510',
						'rel' => 'prettyPhoto[imagens]',
					),
				));
				$video = html(Array(
					'tag' => 'div',
					'val' => $video,
					'attr' => Array(
						'class' => 'video_cont',
					)
				));
				
				$cel_aux = modelo_var_troca($cel_aux,"#img_vid#",$video);
			}
			
			if($status == 'A'){
				$cel_aux = modelo_var_troca($cel_aux,"#tipo#",'B');
				$cel_aux = modelo_var_troca($cel_aux,"#bloq-title#",'Desativar');
				$cel_aux = modelo_var_troca($cel_aux,"#bloq-class#",'bloquear-ativo');
			} else {
				$cel_aux = modelo_var_troca($cel_aux,"#tipo#",'A');
				$cel_aux = modelo_var_troca($cel_aux,"#bloq-title#",'Ativar');
				$cel_aux = modelo_var_troca($cel_aux,"#bloq-class#",'bloquear-inativo');
			}
			
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#id#",$num);
			
			$pagina = modelo_var_in($pagina,'<!-- col1 -->',$cel_aux);
		}
	}	
	
	return $pagina;
}

function imagens($id,$mini,$original,$titulo){
	global $_SYSTEM;
	
	$parametros = Array(
		'frame_width' => false,
		'frame_margin' => '7',
		'imagem_pequena' => 'local_mini',
		'imagem_grande' => 'local_original',
		'menu_paginas_id' => 'imagens',
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
	
	if(!$class)$class = 'imagem';
	
	$imagem_path = $mini;
	
	if($imagem_path){
		$image_info = imagem_info($imagem_path);
		
		$width = $image_info[0];
		$height = $image_info[1];
		
		$imagem = html(Array(
			'tag' => 'img',
			'val' => '',
			'attr' => Array(
				'src' => '/'.$_SYSTEM['ROOT'].$imagem_path.$versao,
				'width' => $width,
				'height' => $height,
				'alt' => $titulo,
				//'border' => '0',
				//'class' => $link_class,
			)
		));
		
		$imagem = html(Array(
			'tag' => 'a',
			'val' => $imagem,
			'attr' => Array(
				'href' => '/'.$_SYSTEM['ROOT'].$original,
				'rel' => 'prettyPhoto['.$menu_paginas_id.']',
				'style' => 'width: '.$width.'px; height: '.($height).'px; display:block;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
				'class' => $link_class,
			)
		));
		
		$imagem = html(Array(
			'tag' => 'div',
			'val' => $imagem,
			'attr' => Array(
				'style' => 'text-align: center; width: '.$width.'px; height: '.($height+$link_class_ajuste_margin).'px; padding-top:1px;',
			)
		));
	}
	
	return $imagem;
}

function excluir(){
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	
	$id = $_REQUEST['id'];
	
	if($_REQUEST['id']){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$background = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"variavel_global",
			"WHERE grupo='background'"
			." AND variavel='dados'"
		);
		
		if($background[0]['valor'])
			$dados_arr = explode(';',$background[0]['valor']);
		
		if($dados_arr){
			foreach($dados_arr as $dado){
				$dado_arr = explode(',',$dado);
				
				if($dado_arr[2] == $id){
					$file1 = $dado_arr[3];
					$file2 = $dado_arr[4];
					
					if($_SYSTEM['SEPARADOR'] == '\\'){
						$file1 = preg_replace('/\//i', '\\', $file1);
						$file2 = preg_replace('/\//i', '\\', $file2);
					}
					
					unlink($_SYSTEM['PATH'].$file1);
					unlink($_SYSTEM['PATH'].$file2);
				} else {
					$dados_arr2[] = $dado;
				}
			}
			
			foreach($dados_arr2 as $dado){
				if($dado)$dados_str .= $dado . ';';
			}
			
			banco_update
			(
				"valor='".$dados_str."'",
				"variavel_global",
				"WHERE grupo='background'"
				." AND variavel='dados'"
			);
		}
	}
	
	return layout_imagens();
}

function bloqueio(){
	global $_CONEXAO_BANCO;
	
	$id = $_REQUEST['id'];
	$tipo = $_REQUEST['tipo'];
	
	if($_REQUEST['id']){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$background = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"variavel_global",
			"WHERE grupo='background'"
			." AND variavel='dados'"
		);
		
		if($background[0]['valor'])
			$dados_arr = explode(';',$background[0]['valor']);
		
		$count = 0;
		
		if($dados_arr){
			foreach($dados_arr as $dado){
				$dado_arr = explode(',',$dado);
				
				if($dado_arr[2] == $id){
					$dado = $dado_arr[0] . ',' . $tipo . ',' . $dado_arr[2] . ',' . $dado_arr[3] . ',' . $dado_arr[4]; 
					$dados_arr[$count] = $dado;
				}
				
				$count++;
			}
			
			foreach($dados_arr as $dado){
				if($dado)$dados_str .= $dado . ';';
			}
			
			banco_update
			(
				"valor='".$dados_str."'",
				"variavel_global",
				"WHERE grupo='background'"
				." AND variavel='dados'"
			);
		}
	}
	
	return layout_imagens();
}

function ativar_background(){
	global $_CONEXAO_BANCO;
	
	$tipo = $_REQUEST['tipo'];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	banco_update
	(
		"valor=".($tipo == 'A' ? "'1'" : "NULL"),
		"variavel_global",
		"WHERE grupo='background'"
		." AND variavel='ativo'"
	);
	
	return layout_imagens();
}

function ordenar_background(){
	global $_CONEXAO_BANCO;
	
	$id = $_REQUEST['id'];
	$tipo = $_REQUEST['tipo'];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$background = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"variavel_global",
		"WHERE grupo='background'"
		." AND variavel='dados'"
	);
	
	if($background[0]['valor'])
		$dados_arr = explode(';',$background[0]['valor']);
	
	$count = 0;
	
	if($dados_arr){
		foreach($dados_arr as $dado){
			$dado_arr = explode(',',$dado);
			
			if($dado_arr[2] == $id){
				if($tipo == 'esq'){
					if($count != 0){
						$dado1 = $dados_arr[$count];
						$dado2 = $dados_arr[$count-1];
						
						$dados_arr[$count] = $dado2;
						$dados_arr[$count-1] = $dado1;
					}
				} else {
					if($count < count($dados_arr)-2){
						$dado1 = $dados_arr[$count];
						$dado2 = $dados_arr[$count+1];
						
						$dados_arr[$count] = $dado2;
						$dados_arr[$count+1] = $dado1;
					}
				}
				
				break;
			}
			
			$count++;
		}
		
		foreach($dados_arr as $dado){
			if($dado)$dados_str .= $dado . ';';
		}
		
		banco_update
		(
			"valor='".$dados_str."'",
			"variavel_global",
			"WHERE grupo='background'"
			." AND variavel='dados'"
		);
	}
	
	return layout_imagens();
}

// ======================================================================================

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
}

function ajax(){
	global $_SYSTEM;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	return $saida;
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
			case 'lista':						$saida = layout_imagens();break;
			case 'upload_imagens':				$saida = (operacao('imagens_uploads') ? layout_imagens() : layout_imagens());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : layout_imagens());break;
			case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : layout_imagens());break;
			case 'ativar_background':			$saida = (operacao('bloquear') ? ativar_background() : layout_imagens());break;
			case 'ordenar_background':			$saida = (operacao('adicionar') ? ordenar_background() : layout_imagens());break;
			default: 							$saida = layout_imagens();
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