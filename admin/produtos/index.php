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
$_LOCAL_ID					=	"produtos";
$_PERMISSAO					=	true;
$_INCLUDE_ADMIN_CONTEUDO	=	true;
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
$_JS['swfUpload'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'produtos';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'produtos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Produtos';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções de Assistência

function destaques_atualizar($id_produtos,$destaque){
	if($destaque > 0){
		$destaques_produtos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_produtos',
				'destaque',
			))
			,
			"produtos",
			"WHERE status='A'"
			." AND destaque = ".$destaque
		);
		
		if(count($destaques_produtos) > 1){
			foreach($destaques_produtos as $dest){
				if($id_produtos == $dest['id_produtos'])
					continue;
				
				banco_update
				(
					"destaque = destaque + 1",
					"produtos",
					"WHERE id_produtos='".$dest['id_produtos']."'"
				);
				
				destaques_atualizar($dest['id_produtos'],(int)$dest['destaque']+1);
			}
		}
	}
}

function destaques_produtos($params = false){
	global $_CONEXAO_BANCO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$categorias_produtos){
		$destaques_produtos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'destaque',
				'nome',
			))
			,
			"produtos",
			"WHERE status='A'"
			." AND destaque > 0"
			." ORDER BY destaque ASC"
		);
	}
	
	if($destaques_produtos){
		if(!$id_categorias_produtos_pai)$lista = '<ul id="destaque-principal">'."\n";
		foreach($destaques_produtos as $destaque){
			$li = '<li>'.$destaque['destaque'].' - '.$destaque['nome'].'</li>'."\n";
			$lista .= $li;
		}
		$lista .= '</ul>'."\n";
	
		return $lista;
	} else {
		return 'Sem destaques';
	}
}

function categorias_produtos($params = false){
	global $_CONEXAO_BANCO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$categorias_produtos){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$categorias_produtos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_categorias_produtos',
				'id_categorias_produtos_pai',
				'nome',
			))
			,
			"categorias_produtos",
			"WHERE status='A'"
			." ORDER BY nome ASC"
		);
	}
	
	if($categorias_produtos){
		if(!$id_categorias_produtos_pai)$lista = '<ul id="categoria-principal">'."\n";
		foreach($categorias_produtos as $categoria){
			if($categoria['id_categorias_produtos_pai'] == $id_categorias_produtos_pai){
				$filhos = false;
				foreach($categorias_produtos as $categoria2){
					if($categoria['id_categorias_produtos'] == $categoria2['id_categorias_produtos_pai']){
						$filhos = true;
					}
				}
				
				if(!$categoria['id_categorias_produtos_pai'] || $id_categorias_produtos_pai){
					$li = '<li data-id="'.$categoria['id_categorias_produtos'].'"'.($id_categorias_produtos == $categoria['id_categorias_produtos']?' class="categoria-selecionada"':'').'><div class="categoria-nome">'.$categoria['nome'].'</div>#filhos#<div class="categorias-controles"><div class="categorias-add"></div><div class="categorias-editar"></div><div class="categorias-excluir"></div></div></li>'."\n";
					
					if($filhos){
						$li = modelo_var_troca($li,"#filhos#",'<ul id="categoria-pai-'.$categoria['id_categorias_produtos'].'">'.categorias_produtos(Array(
							'id_categorias_produtos_pai' => $categoria['id_categorias_produtos'],
							'categorias_produtos' => $categorias_produtos,
							'id_categorias_produtos' => $id_categorias_produtos,
						)).'</ul>');
					} else {
						$li = modelo_var_troca($li,"#filhos#",'');
					}
					
					$lista .= $li;
				}
			}
		}
		if(!$id_categorias_produtos_pai){
			$lista .= '</ul>'."\n";
			$lista = '<div id="categorias-lista">'.$lista.'</div>'."\n";
		}
	
		return $lista;
	} else {
		return '';
	}
}

function categorias_filhos_excluir($id_categoria_pai = false){
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$categorias_produtos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
			'id_categorias_produtos',
		))
		,
		"categorias_produtos",
		"WHERE id_categorias_produtos_pai='".$id_categoria_pai."'"
	);
	
	if($categorias_produtos){
		foreach($categorias_produtos as $cat){
			admin_conteudo_excluir(Array(
				'id' => $cat['id_conteudo'],
			));
			
			banco_update
			(
				"status='D'",
				"categorias_produtos",
				"WHERE id_categorias_produtos='".$cat['id_categorias_produtos']."'"
			);
			
			categorias_filhos_excluir($cat['id_categorias_produtos']);
		}
	}
}

// Funções do Sistema

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
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
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
	if(operacao('bloquear')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
			'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 6, // Coluna background image
			'img_linha' => 1, // Linha background image
			'img_coluna2' => 5, // Coluna background image
			'img_linha2' => 1, // Linha background image
			'bloquear' => true, // Se eh botão de bloqueio
			'legenda' => 'Ativar/Desativar', // Legenda
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
		'campo' => 'Desconto De', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Desconto Até', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Visível De', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Visível Até', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data' => true, // OPCIONAL - alinhamento horizontal
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Quantidade', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // OPCIONAL - Tamanho horizontal
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
		'busca_url' => $_URL, // Url da busca
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
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$session_id = session_id();
	
	if(!$_SESSION[$_SYSTEM['ID']."upload_permissao"]){
		
		$_SESSION[$_SYSTEM['ID']."upload_permissao"] = Array(
			'usuario' => $usuario['usuario'],
			'session_id' => $session_id,
		);
		
		banco_delete
		(
			"upload_permissao",
			"WHERE data <= (NOW() - INTERVAL 1 DAY)"
		);
		banco_insert
		(
			"'" . $usuario['usuario'] . "',".
			"'" . $session_id . "',".
			"NOW()",
			"upload_permissao"
		);
	}
	
	removeDirectory($_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."tmp".$_SYSTEM['SEPARADOR'].$session_id.$_SYSTEM['SEPARADOR'].'produtos'.$_SYSTEM['SEPARADOR']);
	
	$upload_permissao = $_SESSION[$_SYSTEM['ID']."upload_permissao"];
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,"#nome",$nome);
	$pagina = paginaTrocaVarValor($pagina,"#url#",'A definir');
	$pagina = paginaTrocaVarValor($pagina,"#destaque#",$destaque);
	$pagina = paginaTrocaVarValor($pagina,"#descricao",$descricao);
	$pagina = paginaTrocaVarValor($pagina,"#visivel_de#",$visivel_de);
	$pagina = paginaTrocaVarValor($pagina,"#visivel_ate#",$visivel_de);
	$pagina = paginaTrocaVarValor($pagina,"#validade#",$visivel_de);
	$pagina = paginaTrocaVarValor($pagina,"#imagem_path#",$imagem_path);
	$pagina = paginaTrocaVarValor($pagina,"#quantidade#",'999999999');
	$pagina = paginaTrocaVarValor($pagina,"#peso#",$peso);
	$pagina = paginaTrocaVarValor($pagina,"#largura#",$largura);
	$pagina = paginaTrocaVarValor($pagina,"#altura#",$altura);
	$pagina = paginaTrocaVarValor($pagina,"#comprimento#",$comprimento);
	$pagina = paginaTrocaVarValor($pagina,"#preco#",$preco);
	$pagina = paginaTrocaVarValor($pagina,"#desconto#",$desconto);
	$pagina = paginaTrocaVarValor($pagina,"#desconto_de#",$desconto_de);
	$pagina = paginaTrocaVarValor($pagina,"#desconto_ate#",$desconto_ate);
	$pagina = paginaTrocaVarValor($pagina,"#observacao#",$observacao);
	$pagina = paginaTrocaVarValor($pagina,"#usuario",$upload_permissao['usuario']);
	$pagina = paginaTrocaVarValor($pagina,"#sessao",$upload_permissao['session_id']);
	
	$pagina = paginaTrocaVarValor($pagina,"#id_categorias_produtos#",$id_categorias_produtos);
	$pagina = paginaTrocaVarValor($pagina,"#categorias#",categorias_produtos());
	$pagina = paginaTrocaVarValor($pagina,"#destaques#",destaques_produtos());
	
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
	
	$campo_nome = "id_categorias_produtos"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "destaque"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "observacao"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "visivel_de"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "visivel_ate"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "validade"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "descricao"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "quantidade"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "preco"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,preparar_texto_4_float($_POST[$post_nome]));
	$campo_nome = "peso"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,preparar_texto_4_float($_POST[$post_nome]));
	$campo_nome = "largura"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "altura"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "comprimento"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "desconto"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,((int)$_POST[$campo_nome] > 100 ? 100 :$_POST[$campo_nome]));
	$campo_nome = "desconto_de"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "desconto_ate"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,data_padrao_date($_POST[$post_nome]));
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$id_produtos = banco_last_id();
	
	destaques_atualizar($id_produtos,$_REQUEST['destaque']);
	
	$session_id = session_id();
	
	$caminho_fisico_session = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."tmp".$_SYSTEM['SEPARADOR'].$session_id.$_SYSTEM['SEPARADOR'];
	$caminho_fisico_tmp = $caminho_fisico_session.'produtos'.$_SYSTEM['SEPARADOR'];
	
	if(is_dir($caminho_fisico_tmp)){
		$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$id_produtos.$_SYSTEM['SEPARADOR'];
		rename($caminho_fisico_tmp, $caminho_fisico);
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
		))
		,
		"categorias_produtos",
		"WHERE id_categorias_produtos='".$_REQUEST['id_categorias_produtos']."'"
		." AND status='A'"
	);
	
	if($resultado){
		$id_conteudo_pai = $resultado[0]['id_conteudo'];
	}
	
	$id_conteudo = admin_conteudo_add(Array(
		'id_conteudo_pai' => $id_conteudo_pai,
		'campos' => Array(
			'titulo' => $_POST['nome'],
			'texto' => $_POST['descricao'],
			'produto' => $id_produtos,
		),
	));
	
	banco_update
	(
		"id_conteudo='".$id_conteudo."'",
		"produtos",
		"WHERE id_produtos='".$id_produtos."'"
	);
	
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
				'quantidade',
				'preco',
				'desconto',
				'desconto_de',
				'desconto_ate',
				'observacao',
				'id_conteudo',
				'id_categorias_produtos',
				'peso',
				'largura',
				'altura',
				'comprimento',
				'destaque',
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
			'destaque' => $tabela[0]['destaque'],
			'id_categorias_produtos' => $tabela[0]['id_categorias_produtos'],
			'id_conteudo' => $tabela[0]['id_conteudo'],
			'observacao' => $tabela[0]['observacao'],
			'visivel_de' => $tabela[0]['visivel_de'],
			'visivel_ate' => $tabela[0]['visivel_ate'],
			'desconto_de' => $tabela[0]['desconto_de'],
			'desconto_ate' => $tabela[0]['desconto_ate'],
			'validade' => $tabela[0]['validade'],
			'descricao' => $tabela[0]['descricao'],
			'quantidade' => $tabela[0]['quantidade'],
			'desconto' => $tabela[0]['desconto'],
			'largura' => $tabela[0]['largura'],
			'altura' => $tabela[0]['altura'],
			'preco' => preparar_float_4_texto($tabela[0]['preco']),
			'peso' => preparar_float_4_texto($tabela[0]['peso']),
		);
		
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'caminho_raiz',
				'identificador',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$tabela[0]['id_conteudo']."'"
		);
		
		$url = '<a target="_blank" href="http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$conteudo[0]['caminho_raiz'].$conteudo[0]['identificador'].'">http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$conteudo[0]['caminho_raiz'].$conteudo[0]['identificador'].'</a>';
		
		$pagina = paginaTrocaVarValor($pagina,'#url#',$url);
		$pagina = paginaTrocaVarValor($pagina,'#nome',$tabela[0]['nome']);
		$pagina = paginaTrocaVarValor($pagina,'#destaque#',$tabela[0]['destaque']);
		$pagina = paginaTrocaVarValor($pagina,'#observacao#',$tabela[0]['observacao']);
		$pagina = paginaTrocaVarValor($pagina,'#visivel_de#',$tabela[0]['visivel_de']);
		$pagina = paginaTrocaVarValor($pagina,'#visivel_ate#',$tabela[0]['visivel_ate']);
		$pagina = paginaTrocaVarValor($pagina,'#desconto_de#',$tabela[0]['desconto_de']);
		$pagina = paginaTrocaVarValor($pagina,'#desconto_ate#',$tabela[0]['desconto_ate']);
		$pagina = paginaTrocaVarValor($pagina,'#validade#',$tabela[0]['validade']);
		$pagina = paginaTrocaVarValor($pagina,'#descricao',$tabela[0]['descricao']);
		$pagina = paginaTrocaVarValor($pagina,'#quantidade#',$tabela[0]['quantidade']);
		$pagina = paginaTrocaVarValor($pagina,'#desconto#',$tabela[0]['desconto']);
		$pagina = paginaTrocaVarValor($pagina,'#largura#',$tabela[0]['largura']);
		$pagina = paginaTrocaVarValor($pagina,'#altura#',$tabela[0]['altura']);
		$pagina = paginaTrocaVarValor($pagina,'#comprimento#',$tabela[0]['comprimento']);
		$pagina = paginaTrocaVarValor($pagina,'#preco#',preparar_float_4_texto($tabela[0]['preco']));
		$pagina = paginaTrocaVarValor($pagina,'#peso#',preparar_float_4_texto($tabela[0]['peso']));
		
		$pagina = paginaTrocaVarValor($pagina,"#id_categorias_produtos#",$tabela[0]['id_categorias_produtos']);
		$pagina = paginaTrocaVarValor($pagina,"#categorias#",categorias_produtos(Array(
			'id_categorias_produtos' => $tabela[0]['id_categorias_produtos'],
		)));
		$pagina = paginaTrocaVarValor($pagina,"#destaques#",destaques_produtos());
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		$session_id = session_id();
		
		if(!$_SESSION[$_SYSTEM['ID']."upload_permissao"]){
			
			$_SESSION[$_SYSTEM['ID']."upload_permissao"] = Array(
				'usuario' => $usuario['usuario'],
				'session_id' => $session_id,
			);
			
			banco_delete
			(
				"upload_permissao",
				"WHERE data <= (NOW() - INTERVAL 1 DAY)"
			);
			banco_insert
			(
				"'" . $usuario['usuario'] . "',".
				"'" . $session_id . "',".
				"NOW()",
				"upload_permissao"
			);
		}
		
		$upload_permissao = $_SESSION[$_SYSTEM['ID']."upload_permissao"];
		
		$pagina = paginaTrocaVarValor($pagina,"#usuario",$upload_permissao['usuario']);
		$pagina = paginaTrocaVarValor($pagina,"#sessao",$upload_permissao['session_id']);
		
		$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$id.$_SYSTEM['SEPARADOR'];
		$caminho_internet = '/'.$_SYSTEM['ROOT']."files/produtos/produto".$id."/";
		
		if(is_dir($caminho_fisico)){
			$abreDir = opendir($caminho_fisico);

			while (false !== ($file = readdir($abreDir))) {
				if ($file==".." || $file ==".") continue;
				
				if(preg_match('/produto_mini_/i', $file) > 0){
					$filename = preg_replace('/produto_mini_/i', '', $file);
					$filenameArr = explode('.',$filename);
					
					$img_mini = $caminho_internet . $file;
					$img_pequena = $caminho_internet . 'produto_pequeno_' . $filenameArr[0] . '.' . $filenameArr[1];
					$img_grande = $caminho_internet . 'produto_' . $filenameArr[0] . '.' . $filenameArr[1];
					
					$imagem_path .= '<div class="galeria-foto" style="background-image:url('.$img_mini.')"><div class="galeria-excluir-mask"><div class="galeria-excluir-foto" data-img-grande="'.$img_grande.'" data-img-pequena="'.$img_pequena.'" data-img-mini="'.$img_mini.'"></div></div></div>';
				}
			}

			closedir($abreDir);
		}
		
		$pagina = paginaTrocaVarValor($pagina,'#imagem_path#',$imagem_path);
		
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
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $mudar_conteudo = true;}
		$campo_nome = "id_categorias_produtos"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=".($_POST[$campo_nome]?"'" . $_POST[$campo_nome] . "'":"NULL"); $mudar_conteudo_raiz = true;}
		$campo_nome = "destaque"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "observacao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "visivel_de"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "visivel_ate"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "desconto_de"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "desconto_ate"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."=" . ($_POST[$campo_nome]? ($_POST[$campo_nome] != '__/__/____' ? "'" . data_padrao_date($_POST[$campo_nome]) . "'":'NULL'):'NULL');}
		$campo_nome = "validade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $mudar_conteudo = true;}
		$campo_nome = "quantidade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "desconto"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . ((int)$_POST[$campo_nome] > 100 ? 100 :$_POST[$campo_nome]) . "'";}
		$campo_nome = "largura"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "altura"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "comprimento"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "peso"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . preparar_texto_4_float($_POST[$campo_nome]) . "'";}
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
		
		destaques_atualizar($id,$_REQUEST['destaque']);
		
		if($mudar_conteudo){
			$id_conteudo = $campos_antes['id_conteudo'];
			
			admin_conteudo_editar(Array(
				'id_conteudo' => $id_conteudo,
				'campos' => Array(
					'titulo' => $_POST['nome'],
					'texto' => $_POST['descricao'],
				),
			));
		}
		
		if($mudar_conteudo_raiz){
			$id_conteudo = $campos_antes['id_conteudo'];
			$id_categorias_produtos = $_REQUEST['id_categorias_produtos'];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
				))
				,
				"categorias_produtos",
				"WHERE id_categorias_produtos='".$id_categorias_produtos."'"
			);
			
			$id_conteudo_pai_novo = $resultado[0]['id_conteudo'];
			
			admin_conteudo_mudar_pai(Array(
				'id_conteudo_pai_novo' => $id_conteudo_pai_novo,
				'id_conteudo' => $id_conteudo,
			));
		}
		
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
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		if($resultado[0]['id_conteudo'])
			admin_conteudo_excluir(Array(
				'id' => $resultado[0]['id_conteudo'],
			));
		
		$diretorio_produtos = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$id;
		removeDirectory($diretorio_produtos);
		
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
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/produtos/";
	
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
		
		alerta("Ítem removido com sucesso!");
		
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
	global $_PROJETO;
	
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
	
	if($_REQUEST['exluir_foto']){
		$usuario = $_POST["usuario"];
		$sessao = $_POST["sessao"];
		
		banco_conectar();
		$upload_permissao = banco_select_name
		(
			"id_upload_permissao"
			,
			"upload_permissao",
			"WHERE usuario='".$usuario."'".
			" AND session_id='".$sessao."'"
		);
		
		if($upload_permissao){
			$id = $_REQUEST['id'];
			
			$img_grande = $_REQUEST['img_grande'];
			$img_pequena = $_REQUEST['img_pequena'];
			$img_mini = $_REQUEST['img_mini'];
			
			$img_grande = explode('/',$img_grande);
			$img_pequena = explode('/',$img_pequena);
			$img_mini = explode('/',$img_mini);
			
			$img_grande = $img_grande[count($img_grande)-1];
			$img_pequena = $img_pequena[count($img_pequena)-1];
			$img_mini = $img_mini[count($img_mini)-1];
			
			$img_pequena = preg_replace('/\.\./i', '', $img_pequena);
			$img_grande = preg_replace('/\.\./i', '', $img_grande);
			$img_mini = preg_replace('/\.\./i', '', $img_mini);
			
			if(!$id){
				$caminho_fisico_session = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."tmp".$_SYSTEM['SEPARADOR'].$sessao.$_SYSTEM['SEPARADOR'];
				$caminho_fisico_produto = $caminho_fisico_session.'produtos'.$_SYSTEM['SEPARADOR'];
				$caminho_fisico = $caminho_fisico_produto;
			} else {
				$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$id.$_SYSTEM['SEPARADOR'];
			}
			
			
			if(is_file($caminho_fisico.$img_pequena)){
				unlink($caminho_fisico.$img_pequena);
				$saida = 'ok';
			}
			if(is_file($caminho_fisico.$img_grande)){
				unlink($caminho_fisico.$img_grande);
				$saida2 = 'ok2';
			}
			if(is_file($caminho_fisico.$img_mini)){
				unlink($caminho_fisico.$img_mini);
				$saida3 = 'ok3';
			}
			
			return $saida.','.$saida2.','.$saida3;
		} else {
			return 'erro';
		}
	}
	
	switch($_REQUEST['opcao']){
		case 'categoria_add':
			$categoria = utf8_decode($_REQUEST['categoria']);
			$categoria_id_pai = $_REQUEST['categoria_id'];
			
			global $_CONEXAO_BANCO;
			
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			if($categoria_id_pai){
				$categorias_produtos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_conteudo',
					))
					,
					"categorias_produtos",
					"WHERE id_categorias_produtos='".$categoria_id_pai."'"
				);
				
				if($categorias_produtos)$id_conteudo_pai = $categorias_produtos[0]['id_conteudo'];
			}
			
			$id_conteudo = admin_conteudo_add(Array(
				'id_conteudo_pai' => $id_conteudo_pai,
				'tipo' => 'L',
				'campos' => Array(
					'titulo' => $categoria,
				),
			));
			
			// ============================= Modificar Permissão =======================
			
			$campos_permissao_modificar = Array(
				'titulo' => true,
				'categoria' => true,
			);
			
			if($_PROJETO['produtos'])
			if($_PROJETO['produtos']['campos_admin_categoria']){
				$campos_admin_categoria = $_PROJETO['produtos']['campos_admin_categoria'];
				
				foreach($campos_admin_categoria as $adm_campo => $adm_valor){
					if($adm_campo == 'menu_principal'){
						if(!$categoria_id_pai){
							$campos_permissao_modificar[$adm_campo] = $adm_valor;
						}
					} else {
						$campos_permissao_modificar[$adm_campo] = $adm_valor;
					}
				}
			}
			
			admin_permisao_modificar(Array(
				'id' => $id_conteudo,
				'tipo' => 'C',
				'campos' => $campos_permissao_modificar,
			));
			
			// ============================= Modificar Permissão Filhos =======================
			
			$campos_permissao_modificar_filhos = Array(
				'titulo' => true,
				'texto' => true,
				'produto' => true,
			);
			
			if($_PROJETO['produtos'])
			if($_PROJETO['produtos']['campos_admin_categoria_filhos']){
				$campos_admin_categoria_filhos = $_PROJETO['produtos']['campos_admin_categoria_filhos'];
				
				foreach($campos_admin_categoria_filhos as $adm_campo => $adm_valor){
					$campos_permissao_modificar_filhos[$adm_campo] = $adm_valor;
				}
			}
			
			admin_permisao_modificar(Array(
				'id' => $id_conteudo,
				'tipo' => 'L',
				'campos' => $campos_permissao_modificar_filhos,
			));
			
			$campo_nome = "id_categorias_produtos_pai"; $campo_valor = $categoria_id_pai; 			if($campo_valor)				$campos[] = Array($campo_nome,$campo_valor);
			$campo_nome = "id_conteudo"; 				$campo_valor = $id_conteudo; 			if($campo_valor)				$campos[] = Array($campo_nome,$campo_valor);
			$campo_nome = "nome"; 						$campo_valor = $categoria; 				if($campo_valor)				$campos[] = Array($campo_nome,$campo_valor);
			$campo_nome = "status"; $campo_valor = 'A';	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"categorias_produtos"
			);
			
			$id_categorias_produtos = banco_last_id();
			
			return $id_categorias_produtos;
		break;
		case 'categoria_editar':
			$categoria = utf8_decode($_REQUEST['categoria']);
			$categoria_id = $_REQUEST['categoria_id'];
			
			global $_CONEXAO_BANCO;
			
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$categorias_produtos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
				))
				,
				"categorias_produtos",
				"WHERE id_categorias_produtos='".$categoria_id."'"
			);
			
			admin_conteudo_editar(Array(
				'id_conteudo' => $categorias_produtos[0]['id_conteudo'],
				'campos' => Array(
					'titulo' => $categoria,
				),
			));
			
			banco_update
			(
				"nome='".$categoria."'",
				"categorias_produtos",
				"WHERE id_categorias_produtos='".$categoria_id."'"
			);
		break;
		case 'categoria_excluir':
			$categoria_id = $_REQUEST['categoria_id'];
			
			global $_CONEXAO_BANCO;
			
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$categorias_produtos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
				))
				,
				"categorias_produtos",
				"WHERE id_categorias_produtos='".$categoria_id."'"
			);
			
			admin_conteudo_excluir(Array(
				'id' => $categorias_produtos[0]['id_conteudo'],
			));
			
			banco_update
			(
				"status='D'",
				"categorias_produtos",
				"WHERE id_categorias_produtos='".$categoria_id."'"
			);
			
			categorias_filhos_excluir($categoria_id);
		break;
		
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