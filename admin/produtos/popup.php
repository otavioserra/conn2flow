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
$_LOCAL_ID					=	"servicos";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Produtos.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'produtos';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_'.'produtos';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Produtos';
$_LISTA['ferramenta_unidade']	=	'essa entrada';

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
	$tabela_campos[] = 'quantidade';
	$tabela_campos[] = 'preco';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL, // link da opção
			'title' => 'Lista ' . $_LISTA['ferramenta'], // título da opção
			'img_coluna' => 2, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Lista', // Nome do menu
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => '#', // link da opção
			'title' => 'Vincular ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 10, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Vincular', // Nome do menu
			'link_extra' => ' class="produto_escolher" produto="#id"', // Nome do menu
		);
	}
	
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => '#',
		'title' => 'Vincular '.$_LISTA['ferramenta_unidade'], // título da opção
		'img_coluna' => 11, // Coluna background image
		'img_linha' => 1, // Linha background image
		'legenda' => 'Vincular', // Legenda
		'link_extra' => ' class="produto_escolher" produto="#id"', // Nome do menu
	);
	
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
		'campo' => 'Quantidade', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Preço (R$)', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'dinheiro' => true, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
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
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
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
		
		// ================================= Local de Edição ===============================
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
		
		//$remover = '<div><a href="#link#"><img src="../../images/icons/db_remove.png" alt="Remover" width="32" height="32" border="0" title="Clique para remover esse ítem" /></a></div>';
		
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