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
$_LOCAL_ID					=	"contatos";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Conte�do.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'contatos';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'contatos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Contatos';
$_LISTA['ferramenta_unidade']		=	'esse contato';

$_LISTA['campos'][]					=	'nome';
$_LISTA['campos'][]					=	'email';
$_LISTA['campos'][]					=	'mensagem';
$_LISTA['campos'][]					=	'data';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Fun��es do Sistema

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
	
	$tabela_order = $_LISTA['tabela']['id'].' DESC';
	//$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'data';
	
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
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
		'name' => 'Lista', // Nome do menu
	);
	
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
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Excluir', // Nome do menu
			);
		}		
	}
	
	if(operacao('ver')){
		$menu_opcoes[] = Array( // Op��o: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da op��o
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
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
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo � oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => $width, // OPCIONAL - Tamanho horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo � oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Data', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'width' => '120', // OPCIONAL - Tamanho horizontal
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data_hora' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
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
		'legenda' => true, // Colocar legenda
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
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

function editar($param = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_PROJETO;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		if($_PROJETO['contatos']){
			if($_PROJETO['contatos']['layout']){
				$modificar['layout'] = true;
			}
		}
		
		if($modificar['layout']){
			$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
			$pagina = paginaTagValor($modelo,'<!-- contatos < -->','<!-- contatos > -->');
		} else {
			$pagina = paginaModelo('html.html');
			$pagina = paginaTagValor($pagina,'<!-- conteudo < -->','<!-- conteudo > -->');
		}
		
		banco_conectar();
		
		$campos = $_LISTA['campos'];
		
		if($_PROJETO['contatos']){
			if($_PROJETO['contatos']['campos']){
				$campos = array_merge($campos,$_PROJETO['contatos']['campos']);
			}
		}
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edi��o ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		foreach($campos as $campo){
			$campos_guardar[$campo] = $tabela[0][$campo];
		}
		
		if($tabela[0]['data']){
			$tabela[0]['data'] = data_hora_from_datetime_to_text($tabela[0]['data']);
		}
		
		foreach($campos as $campo){
			$pagina = modelo_var_troca_tudo($pagina,'#'.$campo,$tabela[0][$campo]);
		}
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		
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
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
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