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
$_LOCAL_ID					=	"servicos";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Servi�os.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'servicos';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'servicos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Servi�os';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Fun��es do Sistema

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
	
	$menu_principal[] = Array( // array com todos os campos das op��es do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da op��o
		'title' => 'Voltar ao in�cio do sistema', // t�tulo da op��o
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'In�cio', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das op��es do menu
		'url' => $_URL, // link da op��o
		'title' => 'Lista ' . $_LISTA['ferramenta'], // t�tulo da op��o
		'img_coluna' => 2, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Lista', // Nome do menu
	);
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das op��es do menu
			'url' => $_URL . '?opcao=add', // link da op��o
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // t�tulo da op��o
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
			$menu_principal[] = Array( // array com todos os campos das op��es do menu
				'url' => '#', // link da op��o
				'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // t�tulo da op��o
				'img_coluna' => 8, // Coluna background image
				'img_linha' => 1, // Linha background image
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - par�metros extras no link
				'name' => 'Excluir', // Nome do menu
			);
		}
		if(operacao('bloquear')){
			$menu_principal[] = Array( // Op��o: Bloquear
				'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da op��o
				'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // t�tulo da op��o
				'img_coluna' => 6, // Coluna background image
				'img_linha' => 1, // Linha background image
				'img_coluna2' => 7, // Coluna background image
				'img_linha2' => 1, // Linha background image
				'bloquear' => true, // Se eh bot�o de bloqueio
				'name' => 'Ativar/Desativar', // Nome do menu
			);
		}
		
	}
	
	if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Op��o: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da op��o
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
		);
	}
	if(operacao('editar')){
		$menu_opcoes[] = Array( // Op��o: Bloquear
			'url' => $_URL . '?opcao=editar&id=#id', // link da op��o
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Editar', // Legenda
		);
	}
	if(operacao('bloquear')){
		$menu_opcoes[] = Array( // Op��o: Bloquear
			'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da op��o
			'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_coluna' => 6, // Coluna background image
			'img_linha' => 1, // Linha background image
			'img_coluna2' => 5, // Coluna background image
			'img_linha2' => 1, // Linha background image
			'bloquear' => true, // Se eh bot�o de bloqueio
			'legenda' => 'Ativar/Desativar', // Legenda
		);
	}
	if(operacao('excluir')){
		$menu_opcoes[] = Array( // Op��o: Excluir
			'url' => '#', // link da op��o
			'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_coluna' => 7, // Coluna background image
			'img_linha' => 1, // Linha background image
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - par�metros extras no link
			'legenda' => 'Excluir', // Legenda
		);
	}
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo � oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo � oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => $width, // OPCIONAL - Tamanho horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Desconto De', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Desconto At�', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Vis�vel De', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Vis�vel At�', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Quantidade', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Pre�o (R$)', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'dinheiro' => true, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Par�metros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Op��o para altera��o do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informa��o para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // T�tulo da Informa��o
		'informacao_id' => $informacao_id , // Id da Informa��o
		'busca' => operacao('buscar'), // Formul�rio de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Op��o da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // T�tulo 1 do bot�o bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // T�tulo 2 do bot�o bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' ", // Tabela extra
		'tabela_order' => $tabela_order, // Ordena��o da tabela
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => $menu_principal,
		'menu_opcoes' => $menu_opcoes,
		'header_campos' => $header_campos,
		'header_acao' => Array( // array com todos os campos do cabe�alho
			'campo' => 'A��o', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 22), // OPCIONAL - tamanho x da c�lula
			'height' => $valor, // OPCIONAL - tamanho x da c�lula
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
	
	// ================================= Local de Edi��o ===============================
	// Altere os campos da interface com os valores iniciais
	
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
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$id_tabela = banco_last_id();
	
	guardar_arquivo($_FILES['imagem_path'],'imagem','imagem_path',$id_tabela);
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function editar($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
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
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edi��o ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$tabela[0]['visivel_de'] = data_from_date_to_text($tabela[0]['visivel_de']);
		$tabela[0]['visivel_ate'] = data_from_date_to_text($tabela[0]['visivel_ate']);
		$tabela[0]['desconto_de'] = data_from_date_to_text($tabela[0]['desconto_de']);
		$tabela[0]['desconto_ate'] = data_from_date_to_text($tabela[0]['desconto_ate']);
		
		$campos_guardar = Array(
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
		
		$remover = '<div><a href="#link#"><img src="../../images/icons/db_remove.png" alt="Remover" width="32" height="32" border="0" title="Clique para remover esse �tem" /></a></div>';
		
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
		
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
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
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$campos_antes = campos_antes_recuperar();
		
		banco_conectar();
		
		// ================================= Local de Edi��o ===============================
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
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		if($_FILES['imagem_path']['size'] != 0)		{guardar_arquivo($_FILES['imagem_path'],'imagem','imagem_path',$id,$campos_antes['imagem_path']);}
		
		
		// ======================================================================================
		
		banco_fechar_conexao();
	}
	
	return lista();
}

function excluir(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		banco_conectar();
		banco_update
		(
			$_LISTA['tabela']['status']."='D'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
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
		);
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
			);
			
			banco_update
			(
				$item."=NULL",
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		
			$resultado[0][$item] = str_replace($caminho_internet,$caminho_fisico,$resultado[0][$item]);
			if(is_file($resultado[0][$item]))unlink($resultado[0][$item]);
		}	
		
		alerta("�tem removido com sucesso!");
		
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