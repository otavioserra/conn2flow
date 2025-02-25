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

$_VERSAO_MODULO							=	'1.0.0';
$_LOCAL_ID								=	"tags";
$_PERMISSAO								=	true;
$_INCLUDE_INTERFACE						=	true;
$_MENU_LATERAL							=	true;
$_INCLUDE_CONTENT						=	true;
$_INCLUDE_PUBLISHER						=	true;
$_INCLUDE_SITE							=	true;
$_CAMINHO_RELATIVO_RAIZ					=	"../../";
$_CAMINHO_MODULO_RAIZ					=	"../";
$_MENU_LATERAL_GESTOR					=	true;
$_HTML['LAYOUT']						=	$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

$_HTML['titulo'] 						= 	$_HTML['titulo']."Tags.";
$_HTML['variaveis']['titulo-modulo']	=	'Tags';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"../painel.js?v=".$_VERSAO_MODULO."\"></script>\n".
'<script src="../../store/voucher-layouts/colorpicker/jquery.colorpicker.js"></script>
<link href="../../store/voucher-layouts/colorpicker/jquery.colorpicker.css" rel="stylesheet" type="text/css"/>
<script src="../../store/voucher-layouts/colorpicker/i18n/jquery.ui.colorpicker-nl.js"></script>
<script src="../../store/voucher-layouts/colorpicker/parts/jquery.ui.colorpicker-rgbslider.js"></script>
<script src="../../store/voucher-layouts/colorpicker/parts/jquery.ui.colorpicker-memory.js"></script>';

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']				=	'site_conteudos_tags';
$_LISTA['tabela']['campo']				=	'nome';
$_LISTA['tabela']['id']					=	'id_'.'site_conteudos_tags';
$_LISTA['tabela']['status']				=	'status';
$_LISTA['ferramenta']					=	'Tags';
$_LISTA['ferramenta_unidade']			=	'essa Entrada';

$_HTML['separador']						=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

function conteudo_tags_update($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'url',
		))
		,
		"host",
		"WHERE id_usuario='".$id_usuario."'"
		." AND atual IS TRUE"
	);
	
	if($opcao == 'bloqueio' && $status == 'A'){
		$site_conteudos_tags_site_conteudos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos',
			))
			,
			"site_conteudos_tags_site_conteudos",
			"WHERE id_site_conteudos_tags='".$id."'"
		);
		
		if($site_conteudos_tags_site_conteudos){
			foreach($site_conteudos_tags_site_conteudos as $sc){
				$id_site_conteudos .= ($id_site_conteudos ? ',' : '') . $sc['id_site_conteudos'];
			}
			
			$url = $host[0]['url'] . 'platform/conteudos/'.$id_site_conteudos;
			curl_post_async($url);
		}
	} else {
		switch($opcao){
			case 'edit':
				$url = $host[0]['url'] . 'platform/tags/edit/'.$id;
				curl_post_async($url);
			break;
			case 'excluir':
				$url = $host[0]['url'] . 'platform/tags/del/'.$id;
				curl_post_async($url);
			break;
			case 'bloqueio':
				if($status == 'B'){
					$url = $host[0]['url'] . 'platform/tags/del/'.$id;
					curl_post_async($url);
				}
			break;
		}
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
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	
	if($_SESSION[$_SYSTEM['ID']."tag_arvore"]){
		$nome_raiz_navegacao = 'Ir a raiz das tags de conteúdos';
		$nome_navegacao = 'Ir na lista das tags  de conteúdos ';
		$tag_arvore = $_SESSION[$_SYSTEM['ID']."tag_arvore"];
		
		$informacao_acima = '<div class="lista_header">'.htmlA('?opcao=raiz','raiz',$target,$id,' title="'.$nome_raiz_navegacao.'"') . ' / ';
		
		if($tag_arvore)
		foreach($tag_arvore as $filho){
			$count++;
			$informacao_acima .= htmlA('?opcao=lista_conteudo_tag&id='.$filho['id'],$filho['nome'],$target,$id,' title="'.$nome_navegacao.$filho['nome'].'"') . (count($tag_arvore) != $count ? ' / ' : '</div>');
		}
	}
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? '' : $_INTERFACE['informacao_titulo']);
	
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
	
	if(
		$_INTERFACE_OPCAO == 'editar' &&
		$_REQUEST["id"]
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
		if(operacao('editar')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=lista_conteudo_tag&id=#id', // link da opção
				'title' => 'Tags Filhos d' . $_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 7, // Coluna background image
				'img_linha' => 1, // Linha background image
				'img_coluna_ajuste' => 1, // Ajustar manualmente posicionamento da imagem
				'nao_filtrar_icons' => true, // Não filtrar filhos antigos. Usar mascara de icones nova diretamente
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Tags Filhos', // Nome do menu
			);
		}
		
		if(operacao('editar')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=modificar_caminho_raiz&id=#id', // link da opção
				'title' => 'Mudar Raiz d' . $_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 10, // Coluna background image
				'img_linha' => 1, // Linha background image
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Mudar Raiz', // Nome do menu
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
	if(operacao('editar') && !$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=lista_conteudo_tag&id=#id', // link da opção
			'title' => 'Tags Filhos d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 2, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Mudar Raiz', // Legenda
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
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Nome', // Valor do campo
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
		'busca_titulo' => $_LISTA['ferramenta'], // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Habilitar legenda
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
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_site_conteudos_tags_pai".($_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]?"='".$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]."'":"='0'")." AND id_usuario='".$id_usuario."' ", // Tabela extra
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
	global $_CONEXAO_BANCO;
	global $_SEM_CATEGORIA;
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
	
	if($_SEM_CATEGORIA){
		alerta('É necessário criar um categoia antes de adicionar novos conteúdos. Favor criar uma nova tag no formulário a seguir. Após isso acesse novamente os conteúdos para poder criar um novo conteúdo nesta tag.');
	}
	
	if($_REQUEST['site']){
		$more_options = 'widget_id='.$_REQUEST['widget_id'];
	}
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#nome',$servico_txt);
	$pagina = paginaTrocaVarValor($pagina,'#cor',$cor);

	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url#",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#more_options",$more_options);
	$pagina = modelo_var_troca_tudo($pagina,"#id",$id);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE_OPCAO = 'add_conteudo';
	
	return interface_layout(parametros_interface());
}

function add_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_USUARIO_ATIVO_2;
	global $_LISTA;
	global $_INTERFACE;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_CONTEUDO;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	if($_REQUEST['callback']){
		$campo_nome = "id_site_conteudos_tags_pai"; 						$campos[] = Array($campo_nome,($_REQUEST['raiz'] ? $_REQUEST['raiz'] : '0'));
	} else {
		$campo_nome = "id_site_conteudos_tags_pai"; 						$campos[] = Array($campo_nome,($_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"] ? $_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"] : '0'));
	}
	
	$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 			$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "nome"; $post_nome = $campo_nome; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "cor"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 					$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
	site_library_update(Array(
		'widget' => 'posts-filter',
	));
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	// ================================= Conjunto de Tags ===============================
	
	if($_REQUEST['callback']){
		$_SESSION[$_SYSTEM['ID']."alerta"] = 'Tag adicionada com sucesso!';
		
		header('Location: '.rawurldecode($_REQUEST['callback']));
	} else {
		if($_REQUEST['more_options']){
			header('Location: ../../design/?'.$_REQUEST['more_options']);
		} else {
			return lista();
		}
	}
}

function editar($param = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			$_LISTA['tabela']['id'],
		))
		,
		$_LISTA['tabela']['nome'],
		"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		." AND id_usuario='".$id_usuario."'"
	);
	
	if($id && $resultado){
		$modelo = paginaModelo('html.html');
		$pagina = paginaTagValor($modelo,'<!-- form < -->','<!-- form > -->');
		
		if($_REQUEST["buscar_opcao"] == 'busca_ver'){
			$tabela = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tags_pai',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			
			if($tabela[0]['id_site_conteudos_tags_pai']){
				$buscar_raiz = true;
				$buscar_pai = true;
				$id_site_conteudos_tags_pai = $tabela[0]['id_site_conteudos_tags_pai'];
			
				while($buscar_raiz){
					$tabela = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_site_conteudos_tags_pai',
							'nome',
						))
						,
						$_LISTA['tabela']['nome'],
						"WHERE ".$_LISTA['tabela']['id']."='".$id_site_conteudos_tags_pai."'"
					);
					
					if($buscar_pai){
						$buscar_pai = false;
						$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]		= 	$id_site_conteudos_tags_pai;
						$_SESSION[$_SYSTEM['ID']."nome_pai"] 		= 	$tabela[0]['nome'];
						$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_id_site_pai"] 		= 	$tabela[0]['id_site'];
					}
					
					$tag_arvore[] = Array(
						'id' => $id_site_conteudos_tags_pai,
						'nome' => $tabela[0]['nome'],
					);
					
					if(!$tabela[0]['id_site_conteudos_tags_pai']){
						$buscar_raiz = false;
					} else {
						$id_site_conteudos_tags_pai = $tabela[0]['id_site_conteudos_tags_pai'];
					}
				}
				
				$_SESSION[$_SYSTEM['ID']."tag_arvore"] = array_reverse($tag_arvore);
			} else {
				$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]		= 	0;
				$_SESSION[$_SYSTEM['ID']."nome_pai"] 		= 	null;
				$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_id_site_pai"] 		= 	null;
				$_SESSION[$_SYSTEM['ID']."tag_arvore"]	=	null;
			}
		}
		
		$campos[] = 'nome';
		$campos[] = 'cor';
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		foreach($campos as $campo){
			$campos_guardar[$campo] = $tabela[0][$campo];
		}
		
		foreach($campos as $campo){
			switch($campo){
				case 'id':
					// Não fazer nada
				break;
				default:
					$pagina = paginaTrocaVarValor($pagina,'#'.$campo,$tabela[0][$campo]);
			}
		}
		
		// ======================================================================================
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Editar";
		$botao = "Gravar";
		$opcao = "editar_base";
		
		if($_REQUEST['site']){
			$more_options = 'widget_id='.$_REQUEST['widget_id'];
		}
		
		$pagina = paginaTrocaVarValor($pagina,"#form_url#",$_LOCAL_ID);
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = modelo_var_troca_tudo($pagina,"#id",$id);
		$pagina = modelo_var_troca_tudo($pagina,"#more_options",$more_options);
		
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

function editar_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_CONTEUDO;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_ALERTA;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$campos_antes = campos_antes_recuperar();
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tags',
			))
			,
			"site_conteudos_tags",
			"WHERE id_usuario='".$id_usuario."'"
			." AND id_site_conteudos_tags='".$id."'"
		);
		
		if(!$resultado){
			$_ALERTA = '<p>Essa tag não faz parte da sua conta. Favor entrar em contato com o suporte para saber como proceder!</p>';
			return lista();
		}
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";

		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; }
		$campo_nome = "cor"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; }
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		site_library_update(Array(
			'widget' => 'posts-filter',
		));
		
		publisher_sitemaps();
		
		conteudo_tags_update(Array(
			'id' => $id,
			'opcao' => 'edit',
			'nome' => $_REQUEST['nome'],
			'cor' => $_REQUEST['cor'],
		));
	}
	
	if($_REQUEST['more_options']){
		header('Location: ../../design/?'.$_REQUEST['more_options']);
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
	global $_CONEXAO_BANCO;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_FTP_PUT_PASSIVE;
	global $_EXCLUIR_IDS;
	
	$id = $_GET["id"];
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			$_LISTA['tabela']['id'],
		))
		,
		$_LISTA['tabela']['nome'],
		"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		." AND id_usuario='".$id_usuario."'"
	);
	
	if($id && $resultado){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tags',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		excluir_filhos($id);
		
		banco_delete
		(
			"site_conteudos_tags_site_conteudos",
			"WHERE id_site_conteudos_tags='".$id."'"
		);
		banco_update
		(
			"status='D'",
			'site_conteudos_tags',
			"WHERE id_site_conteudos_tags='".$id."'"
		);
		
		site_library_update(Array(
			'widget' => 'posts-filter',
		));
		
		publisher_sitemaps();
		
		conteudo_tags_update(Array(
			'id' => $id,
			'opcao' => 'excluir',
		));
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function excluir_filhos($id,$primeira_recursao = true){
	global $_LISTA;
	global $_EXCLUIR_IDS;
	
	$resultado = banco_select
	(
		banco_campos_virgulas(Array(
			'id_site_conteudos_tags',
		))
		,
		'site_conteudos_tags',
		"WHERE id_site_conteudos_tags_pai='".$id."'"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$filho = banco_select
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tags',
				))
				,
				'site_conteudos_tags',
				"WHERE id_site_conteudos_tags_pai='".$res['id_site_conteudos_tags']."'"
				." AND status!='D'"
			);
			
			if($filho){
				excluir_filhos($res['id_site_conteudos_tags'],false);
			}
			
			banco_delete
			(
				"site_conteudos_tags_site_conteudos",
				"WHERE id_site_conteudos_tags='".$res['id_site_conteudos_tags']."'"
			);
			banco_update
			(
				"status='D'",
				'site_conteudos_tags',
				"WHERE id_site_conteudos_tags='".$res['id_site_conteudos_tags']."'"
			);
			
			conteudo_tags_update(Array(
				'id' => $res['id_site_conteudos_tags'],
				'opcao' => 'excluir',
			));
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
		
		site_library_update(Array(
			'widget' => 'posts-filter',
		));
		
		publisher_sitemaps();
		
		conteudo_tags_update(Array(
			'id' => $id,
			'opcao' => 'bloqueio',
			'status' => $status,
		));
	}
	
	return lista();
}

function lista_conteudo_tag(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_CONEXAO_BANCO;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	$tag_arvore = $_SESSION[$_SYSTEM['ID']."tag_arvore"];
	
	if($id){
		if($tag_arvore)
		foreach($tag_arvore as $filho){
			$arvore_aux[] = $filho;
			if($id == $filho['id']){
				$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]		= 	$filho['id'];
				$_SESSION[$_SYSTEM['ID']."nome_pai"] 		= 	$filho['nome'];
				$_SESSION[$_SYSTEM['ID']."tag_arvore"] 	= 	$arvore_aux;
				$found_filho = true;
				break;
			}
		}
		
		if(!$found_filho){
			if(!$_CONEXAO_BANCO)banco_conectar();
			
			$tabela = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			
			$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"] = $id;
			$_SESSION[$_SYSTEM['ID']."nome_pai"] = $tabela[0]['nome'];
			
			$tag_arvore[] = Array(
				'id' => $id,
				'nome' => $tabela[0]['nome'],
			);
			
			$_SESSION[$_SYSTEM['ID']."tag_arvore"] = $tag_arvore;
		}
	}
	
	return lista();
}

function raiz(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	
	$_SESSION[$_SYSTEM['ID']."tag_arvore"] = null;
	$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"] = false;
	$_SESSION[$_SYSTEM['ID']."nome_pai"] = null;
	$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_id_site_pai"] = null;
	
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
	
	if($_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]){
		$nome_raiz_navegacao = 'Inserir na raiz dos conteúdos';
		$nome_navegacao = 'Inserir na lista de conteúdos: ';
		$link = 'raiz';
		$id_pai = '-1';
		$tag_arvore = $_SESSION[$_SYSTEM['ID']."tag_arvore"];
		
		if($tag_arvore){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_tags_pai',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]."'"
			);
			
			if((int)$resultado[0]['id_site_conteudos_tags_pai'] > 0){
				$id_pai = $resultado[0]['id_site_conteudos_tags_pai'];
				
				$resultado2 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
					))
					,
					$_LISTA['tabela']['nome'],
					"WHERE ".$_LISTA['tabela']['id']."='".$id_pai."'"
				);
				
				$nome_raiz_navegacao = $nome_navegacao.$resultado2[0]['nome'];
				$link = $resultado2[0]['nome'];
			}
		}
		
		$informacao_acima = '
		<div class="lista_header">
			<div style="float:left; margin-left:5px;">Nível Acima:</div>
			<a href="'.$_URL . '?opcao=modificar_caminho_raiz_novo&id='.$id_pai.'&id_filho='.$id.'&subir_nivel=1" title="'.$nome_raiz_navegacao.'" style="display:block;">
				<div style="background-image:url(\''.$_HTML['separador'].$_HTML['ICONS'] . '../admin/icon-central.png\');background-position:-80px -16px; float:left;width:16px;height:16px; margin:0px 5px;"></div> '.$link.'
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
	if($_SESSION[$_SYSTEM['ID']."tag_arvore"] || operacao('modificar_raiz')){
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
			'img_coluna' => 6, // Coluna background image
			'img_linha' => 2, // Linha background image
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
		'campo' => 'Nome', // Valor do campo
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
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_site_conteudos_tags!='".$id."' AND id_site_conteudos_tags_pai='".($_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]?$_SESSION[$_SYSTEM['ID']."site_conteudos_tags_pai"]:'0')."' ", // Tabela extra
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

function modificar_caminho_raiz(){
	global $_INTERFACE_OPCAO;
	
	if($_REQUEST['id']){
		$_INTERFACE_OPCAO = 'lista';
		
		return interface_layout(modificar_caminho_raiz_parametros_interface());
	} else {
		return lista();
	}
}

function modificar_caminho_raiz_novo(){
	global $_LISTA;
	
	$id = $_REQUEST['id_filho'];
	$id_pai = $_REQUEST['id'];
	$subir_nivel = $_REQUEST['subir_nivel'];
	
	if($id_pai == '-1'){
		$id_pai = '0';
	}
	
	banco_update
	(
		"id_site_conteudos_tags_pai='".$id_pai."'",
		$_LISTA['tabela']['nome'],
		"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
	);
	
	if($id_pai == '0') return raiz(); else return lista_conteudo_tag();
}

function testes(){
	global $_SYSTEM;
	
}

// ======================================================================================

function xml(){
	
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
	
	return $saida;
}

function start(){
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_LISTA;
	global $_HTML;
	global $_SEM_CATEGORIA;
	
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
			case 'modificar_caminho_raiz':		$saida = (operacao('editar') ? modificar_caminho_raiz() : lista());break;
			case 'modificar_caminho_raiz_novo':	$saida = (operacao('editar') ? modificar_caminho_raiz_novo() : lista());break;
			case 'raiz':						$saida = raiz();break;
			case 'lista_conteudo_tag':	$saida = lista_conteudo_tag();break;
			case 'testes':						$saida = testes();break;
			case 'sem-tag':				$_SEM_CATEGORIA = true; $saida = (operacao('adicionar') ? add() : lista()); break;
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