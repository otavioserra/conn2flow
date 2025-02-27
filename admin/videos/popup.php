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

$_VERSAO_MODULO				=	'1.2.1';
$_LOCAL_ID					=	"videos";
$_POPUP						=	true;
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Galerias de Vídeos.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['tinyMce'].
$_JS['swfUpload'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'galerias_videos';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_'.'galerias_videos';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Galerias de Vídeos';
$_LISTA['ferramenta_unidade']	=	'a galeria de vídeo';

$_LISTA_3['tabela']['nome']		=	'videos_grupos';
$_LISTA_3['tabela']['campo']	=	'grupo';
$_LISTA_3['tabela']['id']		=	'id_'.'videos_grupos';
$_LISTA_3['tabela']['status']	=	'status';
$_LISTA_3['ferramenta']			=	'Vídeos Grupos';

$_HTML['separador'] = $_CAMINHO_RELATIVO_RAIZ;

$_URL				=	'popup.php';

// Funções de assistência

function alerta($nAlerta){
	global $_ALERT_DADOS;
	global $_ALERTA;
	
	switch ($nAlerta){
		//case 1:		$mensSaida	=	"";break;
		default:	$mensSaida	=	$nAlerta;
	}

	$_ALERTA = $mensSaida;
}

// Funções do Sistema

function galerias_grupos($params){
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
		'forcar_id' => $forcar_id ? $forcar_id : '-1', // Se quiser que um campo seja um id, útil para edição. Senão set esse valor para false
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
	$link_extra = $params['link_extra'];
	$onchange = $params['onchange'];
	$forcar_id = $params['forcar_id'];
	$url = $params['url'];
	$id = $nome . '_id';
	
	if($_REQUEST[$id])	$_SESSION[$_SYSTEM['ID'].$id] = $_REQUEST[$id];
	if($forcar_id)	$_SESSION[$_SYSTEM['ID'].$id] = $forcar_id;
	
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
		($tabela_extra?$tabela_extra:"WHERE status='A'")
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
		
		if($_SESSION[$_SYSTEM['ID'].$id] == $resultado[$tabela_campos['id']]){
			$optionSelected = $cont;
			if(!$opcao_inicial)$optionSelected--;
		}
	}
	
	/* if(!$optionSelected && $cont == 1){
		$optionSelected = 1;
		if(!$opcao_inicial)$optionSelected--;
	} */
	
	if($link_extra)$link_extra .= '&';
	if($url)$url .= '?';
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,$onchange ? 'onchange=window.open("'.$url.$link_extra.$id.'="+this.value,"_self")' : '');
	
	return $select;
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
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'data';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL, // link da opção
			'title' => 'Lista ' . $_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'lista.jpg', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Lista', // Nome do menu
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => '#', // link da opção
			'title' => 'Vincular ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'vincular_grande.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Vincular', // Nome do menu
			'link_extra' => ' class="videos_escolher" videos="'.$_SESSION[$_SYSTEM['ID']."id"].'"', // Nome do menu
		);
	}
	
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => '#',
		'title' => 'Vincular '.$_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'vincular.png', // caminho da imagem
		'legenda' => 'Vincular', // Legenda
		'link_extra' => ' class="videos_escolher" videos="#id"', // Nome do menu
	);
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => '', // OPCIONAL - Tamanho horizontal
	);
	
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
		'campo' => 'Data', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'width' => '120', // OPCIONAL - Tamanho horizontal
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data_hora' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ($_INTERFACE_OPCAO != 'layout_imagens' ? ' ' . $_LISTA['ferramenta'] : '') , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => operacao('buscar'), // Formulário de busca
		'busca_url' => "popup.php", // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D'", // Tabela extra
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
		
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface());
}

function editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		banco_conectar();
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'descricao',
				'grupo',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$nome = $resultado[0]['nome'];
		$descricao = $resultado[0]['descricao'];
		$grupo = $resultado[0]['grupo'];
		
		$campos_guardar = Array(
			'nome' => $nome,
			'descricao' => $descricao,
			'grupo' => $grupo,
		);
		
		$grupo = galerias_grupos(Array(
			'nome' => 'grupo',
			'tabela_campos' => Array(
				'id' => 'id_galerias_grupos',
				'nome' => 'grupo',
			),
			'tabela_nome' => 'galerias_grupos',
			'tabela_extra' => false,
			'tabela_order' => 'grupo ASC',
			'opcao_inicial' => 'Selecione o grupo',
			'link_extra' => false,
			'url' => false,
			'onchange' => false,
			'forcar_id' => $grupo ? $grupo : '-1',
		));
		
		$pagina = paginaTrocaVarValor($pagina,'#nome',$nome);
		$pagina = paginaTrocaVarValor($pagina,'#descricao',$descricao);
		$pagina = paginaTrocaVarValor($pagina,'#grupo#',$grupo);
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "editar_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
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

function grupos_parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_LISTA_3;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	
	$_LISTA = $_LISTA_3;
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=grupos', // link da opção
			'title' => 'Gerenciar Grupos', // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'grupo_generico.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Grupos', // Nome do menu
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => '#', // link da opção
			'title' => 'Vincular ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'vincular_grande.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Vincular', // Nome do menu
			'link_extra' => ' class="videos_grupo_escolher" videos_grupo="'.$_SESSION[$_SYSTEM['ID']."id"].'"', // Nome do menu
		);
	}
	
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => '#',
		'title' => 'Vincular '.$_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'vincular.png', // caminho da imagem
		'legenda' => 'Vincular', // Legenda
		'link_extra' => ' class="videos_grupo_escolher" videos_grupo="#id"', // Nome do menu
	);
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => '', // OPCIONAL - Tamanho horizontal
	);
	
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
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ($_INTERFACE_OPCAO != 'layout_imagens' ? ' ' . $_LISTA['ferramenta'] : '') , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => operacao('buscar'), // Formulário de busca
		'busca_url' => 'popup.php', // Url da busca
		'busca_opcao' => 'busca_ver2', // Opção da busca
		'busca_name' => 'busca_nome3',
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID."_grupos", // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D'", // Tabela extra
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
		
	);
	
	return $parametros;
}

function grupos(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(grupos_parametros_interface());
}

function grupos_editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA_3;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$_LISTA = $_LISTA_3;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- grupos < -->','<!-- grupos > -->');
		
		banco_conectar();
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'grupo',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$grupo = $resultado[0]['grupo'];
		
		$campos_guardar = Array(
			'grupo' => $grupo,
		);

		$pagina = paginaTrocaVarValor($pagina,'#grupo#',$grupo);
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "grupos_editar_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
		$_INTERFACE_OPCAO = 'editar';
		$_INTERFACE['local'] = 'conteudo';
		$_INTERFACE['informacao_titulo'] = $in_titulo;
		$_INTERFACE['informacao_tipo'] = $tipo;
		$_INTERFACE['informacao_id'] = $id;
		$_INTERFACE['inclusao'] = $pagina;
	
		return interface_layout(grupos_parametros_interface());
	} else 
		return grupos();
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	global $_LISTA_3;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D'"
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
	
	if($_REQUEST['query_id'] == 'busca_nome2' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D'"
			." AND id_categorias_txt='".$_SESSION[$_SYSTEM['ID']."id"]."'"
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
	
	if($_REQUEST['query_id'] == 'busca_nome3' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA_3['tabela']['id'] . "," . $_LISTA_3['tabela']['campo'],
			$_LISTA_3['tabela']['nome'],
			"WHERE UCASE(".$_LISTA_3['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA_3['tabela']['status']."!='D'"
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
	global $_HTML;
	
	if($_FILES['uploadfile']['size'] > 0)
		echo uploadfile();
	else if(!$_REQUEST["ajax"]){
		if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
		if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
		
		$_PAGINA_OPCAO = $opcoes;
		
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'menu_'.$_LOCAL_ID:
			case 'lista':						$saida = lista();break;
			case 'busca_ver':					$saida = (operacao('buscar') ? editar('ver') : lista());break;
			case 'menu_'.$_LOCAL_ID."_grupos":
			case 'grupos':						$saida = (operacao('grupos') ? grupos() : lista());break;
			case 'busca_ver2':					$saida = (operacao('grupos_ver') ? grupos_editar() : (operacao('buscar') ? grupos_editar('grupos_ver') : grupos()));break;
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