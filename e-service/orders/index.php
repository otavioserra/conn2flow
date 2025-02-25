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
$_LOCAL_ID					=	"orders";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_MAILER			=	true;
$_INCLUDE_LOJA				=	true;
$_MENU_LATERAL				=	true;
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

$_HTML['titulo'] 						= 	$_HTML['titulo']."Pedidos.";
$_HTML['variaveis']['titulo-modulo']	=	'Pedidos';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['tinyMce'].
$_JS['maskedInput'].
"<script type=\"text/javascript\" src=\"../js.js?v=".$_VERSAO_MODULO."\"></script>\n".
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= 
"<link href=\"../css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"<link href=\"../../includes/ecommerce/css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'pedidos';
$_LISTA['tabela']['campo']			=	'codigo';
$_LISTA['tabela']['id']				=	'id_'.'pedidos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Pedidos';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Fun��es do Sistema

function envio_select($opcao){
	global $_BANCO_PREFIXO,
	$_SYSTEM_ID,
	$_PAGINA_LOCAL,
	$_URL;
	
	$nome = 'envio';
	$id = $nome . '_id';
	
	$opcoes = Array(
		Array(	'Entregue' , 'F'		),
		Array(	'Enviado' , 'E'		),
		Array(	'N�o enviado' , 'N'		),
		Array(	'Retirado em M�os' , 'M'	),
	);
	
	for($i=0;$i<count($opcoes);$i++){
		$options[] = $opcoes[$i][0];
		$optionsValue[] = $opcoes[$i][1];
		
		if($opcao == $opcoes[$i][1]){
			$optionSelected = $i;
		}
	}
	
	if(!$optionSelected && count($opcoes) == 1)
		$optionSelected = 1;
	
	$select = formSelect($nome,$id,$options,$optionsValue,$optionSelected,$extra);
	
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
	global $_PROJETO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = 'valor_total';
	$tabela_campos[] = 'data';
	$tabela_campos[] = 'status';
	
	if($loja_online_produtos)$tabela_campos[] = 'status_envio';
	
	if(!$loja_online_produtos){
		$tabela_campos[] = 'id_usuario_baixa';
		$tabela_campos[] = 'data_baixa';
		$tabela_campos[] = 'interno';
		$tabela_campos[] = 'cortesia';
	}
	
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
	/* if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das op��es do menu
			'url' => $_URL . '?opcao=add', // link da op��o
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // t�tulo da op��o
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'entrar.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Adicionar', // Nome do menu
		);
	} */
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		/* if(operacao('excluir')){
			$menu_principal[] = Array( // array com todos os campos das op��es do menu
				'url' => '#', // link da op��o
				'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // t�tulo da op��o
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'db_remove.png', // caminho da imagem
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - par�metros extras no link
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Excluir', // Nome do menu
			);
		} */
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
		if(!$loja_online_produtos){
			$menu_principal[] = Array( // array com todos os campos das op��es do menu
				'url' => $_URL . '?opcao=imprimir-voucher&id=#id', // link da op��o
				'title' => 'Imprimir Voucher', // t�tulo da op��o
				'img_coluna' => 3, // Coluna background image
				'img_linha' => 2, // Linha background image
				'name' => 'Imprimir Voucher', // Nome do menu
			);
		}
	}
	
	//if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Op��o: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da op��o
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
		);
	//}
	/* if(operacao('editar')){
		$menu_opcoes[] = Array( // Op��o: Bloquear
			'url' => $_URL . '?opcao=editar&id=#id', // link da op��o
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'editar.png', // caminho da imagem
			'legenda' => 'Editar', // Legenda
		);
	}*/
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
	if(!$loja_online_produtos){
		$menu_opcoes[] = Array( // Op��o: Bloquear
			'url' => $_URL . '?opcao=imprimir-voucher&id=#id', // link da op��o
			'title' => 'Imprimir Voucher', // t�tulo da op��o
			'img_coluna' => 15, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Imprimir Voucher', // Legenda
		);
	}
	/*if(operacao('excluir')){
		$menu_opcoes[] = Array( // Op��o: Excluir
			'url' => '#', // link da op��o
			'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // t�tulo da op��o
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'excluir.png', // caminho da imagem
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - par�metros extras no link
			'legenda' => 'Excluir', // Legenda
		);
	} */
	
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
		'campo' => 'C�digo', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '90', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Cliente', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'id' => 2, // OPCIONAL - Se � ID da tabela e � refer�ncia para outra tabela de n�mero desse valor
		'tabela' => 2, // OPCIONAL - Se faz parte de outra tabela de n�mero desse valor
		'mudar_valor' => 2, // OPCIONAL - Se faz parte de outra tabela de n�mero desse valor
		'padrao_se_nao_existe' => true,
		'valor_padrao_se_nao_existe' => '',
		'campo' => 'nome', // OPCIONAL - Nome do campo da tabela
		'campo_id' => 't2.id_pedidos', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Valor', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '90', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'dinheiro' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Data', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '100', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'data_hora' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabe�alho
		'campo' => 'Pagamento', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '80', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
		'valor_padrao_se_nao_existe' => '<span style="color:red;">N�o Definido</span>', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => Array(
			'5' => '<span style="color:red;">Em disputa</span>',
			'6' => '<span style="color:brown;">Dinheiro Devolvido</span>',
			'7' => '<span style="color:brown;">Cancelado</span>',
			'F' => '<span style="color:brown;">Finalizado</span>',
			'A' => '<span style="color:green;">Pago</span>',
			'B' => '<span style="color:red;">Bloqueado</span>',
			'D' => '<span style="color:red;">Deletado</span>',
			'N' => '<span style="color:blue;">Aguardando pagamento</span>',
			'P' => '<span style="color:blue;">Em an�lise</span>',
		),
	);
	
	if($loja_online_produtos){
		$header_campos[] = Array( // array com todos os campos do cabe�alho
			'campo' => 'Envio', // Valor do campo
			'ordenar' => true, // Valor do campo
			'width' => '150', // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
			'valor_padrao_se_nao_existe' => '<span style="color:red;">N�o enviado</span>', // OPCIONAL - alinhamento horizontal
			'mudar_valor_array' => Array(
				'F' => '<span style="color:green;">Entregue</span>',
				'M' => '<span style="color:green;">Retirado em M�os</span>',
				'E' => '<span style="color:blue;">Enviado</span>',
			),
		);
	}
	
	if(!$loja_online_produtos){
		$header_campos[] = Array( // array com todos os campos do cabe�alho
			'campo' => 'Usu�rio Baixa', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'id' => 1, // OPCIONAL - Se � ID da tabela e � refer�ncia para outra tabela de n�mero desse valor
			'tabela' => 1, // OPCIONAL - Se faz parte de outra tabela de n�mero desse valor
			'mudar_valor' => 1, // OPCIONAL - Se faz parte de outra tabela de n�mero desse valor
			'padrao_se_nao_existe' => true,
			'valor_padrao_se_nao_existe' => '',
			'campo' => 'usuario', // OPCIONAL - Nome do campo da tabela
			'campo_id' => 'id_usuario', // OPCIONAL - Nome do campo da tabela
			'align' => $valor, // OPCIONAL - alinhamento horizontal
		);
		
		$header_campos[] = Array( // array com todos os campos do cabe�alho
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'campo' => 'Data Baixa', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'data' => true,
		);
		
		$header_campos[] = Array( // array com todos os campos do cabe�alho
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'campo' => 'Interno', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
			'valor_padrao_se_nao_existe' => '<span style="color:blue;">N�o</span>', // OPCIONAL - alinhamento horizontal
			'mudar_valor_array' => Array(
				'1' => '<span style="color:green;">Sim</span>',
			),
		);
		
		$header_campos[] = Array( // array com todos os campos do cabe�alho
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'campo' => 'Cortesia', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
			'valor_padrao_se_nao_existe' => '<span style="color:blue;">N�o</span>', // OPCIONAL - alinhamento horizontal
			'mudar_valor_array' => Array(
				'1' => '<span style="color:green;">Sim</span>',
			),
		);
	}
	
	// ------------------------------ Outra Tabela -------------------------
	
	$outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => 'usuario', // Nome da tabela
		'campos' => Array(
			'usuario',
		), // Array com os nomes dos campos
		'extra' => '', // Tabela extra
	);
	$outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => 'usuario as t1,usuario_pedidos as t2', // Nome da tabela
		'campos' => Array(
			't1.nome',
		), // Array com os nomes dos campos
		'extra' => ' AND t1.id_usuario=t2.id_usuario', // Tabela extra
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
		'bloquear_titulo_2' => "Cancelar " . $_LISTA['ferramenta_unidade'], // T�tulo 2 do bot�o bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_loja='".$usuario['id_loja']."' ", // Tabela extra
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
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
	
	// ================================= Local de Edi��o ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,"#nome",$nome);
	$pagina = paginaTrocaVarValor($pagina,"#descricao",$descricao);
	
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
	$campo_nome = "descricao"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	
	//banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function editar($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_PROJETO;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form'.($loja_online_produtos?'2':'').' < -->','<!-- form'.($loja_online_produtos?'2':'').' > -->');
		
		$cel_nome = 'servicos'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		$cel_nome = 'produtos'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		if(!operacao('editar')){
			$cel_nome2 = 'validade'; $cel[$cel_nome] = modelo_tag_in($cel[$cel_nome],'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
			$cel_nome2 = 'mudar_envio'; $cel[$cel_nome] = modelo_tag_in($cel[$cel_nome],'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
		}
		
		banco_conectar();
		
		if($loja_online_produtos){
			$tabela = banco_select_name
			(
				banco_campos_virgulas(Array(
					'codigo',
					'senha',
					'data',
					'protocolo_baixa',
					'id_usuario_baixa',
					'data_baixa',
					'status',
					'presente',
					'de',
					'para',
					'mensagem',
					'observacao_baixa',
					'interno',
					'cortesia',
					'valor_total',
					'dest_nome',
					'dest_endereco',
					'dest_num',
					'dest_complemento',
					'dest_bairro',
					'dest_cidade',
					'dest_uf',
					'dest_cep',
					'tipo_frete',
					'valor_frete',
					'status_envio',
					'codigo_rastreio',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			$usuario_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't2.nome',
					't2.id_usuario',
				))
				,
				"usuario_pedidos as t1,usuario as t2",
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_usuario=t2.id_usuario"
			);
			
			// ================================= Local de Edi��o ===============================
			// Pegue os campos da interface e campos_guardar aqui
			
			/* $campos_guardar = Array(
				'codigo' => $tabela[0]['codigo'],
				'descricao' => $tabela[0]['descricao'],
			); */
			
			// ================================= Status ===============================
			
			$status_mudar = Array(
				'5' => '<span style="color:red;">Em disputa</span>',
				'6' => '<span style="color:brown;">Dinheiro Devolvido</span>',
				'7' => '<span style="color:brown;">Cancelado</span>',
				'F' => '<span style="color:brown;">Finalizado</span>',
				'A' => '<span style="color:green;">Pago</span>',
				'B' => '<span style="color:red;">Bloqueado</span>',
				'D' => '<span style="color:red;">Deletado</span>',
				'N' => '<span style="color:blue;">Aguardando pagamento</span>',
				'P' => '<span style="color:blue;">Em an�lise</span>',
			);
			
			$status = $tabela[0]['status'];
			
			foreach($status_mudar as $chave => $valor){
				if($tabela[0]['status'] == $chave){
					$tabela[0]['status'] = $valor;
					break;
				}
			}
			
			if(!$tabela[0]['status_envio']){
				$tabela[0]['status_envio'] = '<span style="color:red;">N�o enviado</span>';
				$status_envio = 'N';
			} else {
				$status_envio_mudar = Array(
					'F' => '<span style="color:green;">Entregue</span>',
					'M' => '<span style="color:green;">Retirado em M�os</span>',
					'E' => '<span style="color:blue;">Enviado</span>',
					'N' => '<span style="color:red;">N�o enviado</span>',
				);
				
				$status_envio = $tabela[0]['status_envio'];
				
				foreach($status_envio_mudar as $chave => $valor){
					if($tabela[0]['status_envio'] == $chave){
						$tabela[0]['status_envio'] = $valor;
						break;
					}
				}
			}
			
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				'usuario',
				"WHERE id_usuario='".$tabela[0]['id_usuario_baixa']."'"
			);
			
			$tipo_frete = $tabela[0]['tipo_frete'];
			
			switch($tipo_frete){
				case '1': $tipo_frete = 'SEDEX'; break;
				case '2': $tipo_frete = 'PAC'; break;
				default: $tipo_frete = 'Retirar em m�os';
			}
			
			$envio = envio_select($status_envio);
			
			$pagina = paginaTrocaVarValor($pagina,'#usuario_pedido#',($usuario_pedidos[0]['t2.id_usuario']?"<a href=\"../usuarios/?opcao=editar&id=".$usuario_pedidos[0]['t2.id_usuario']."\"><b>".$usuario_pedidos[0]['t2.nome']."</b></a>":"Pedido Sem Usu�rio"));
			$pagina = paginaTrocaVarValor($pagina,'#codigo#',$tabela[0]['codigo']);
			$pagina = paginaTrocaVarValor($pagina,'#senha#',$tabela[0]['senha']);
			$pagina = paginaTrocaVarValor($pagina,'#status#',$tabela[0]['status']);
			$pagina = paginaTrocaVarValor($pagina,'#status_envio#',$tabela[0]['status_envio']);
			$pagina = paginaTrocaVarValor($pagina,'#valor_total#',preparar_float_4_texto($tabela[0]['valor_total']));
			$pagina = paginaTrocaVarValor($pagina,'#valor_total_frete#',preparar_float_4_texto(((float)$tabela[0]['valor_total']+(float)$tabela[0]['valor_frete'])));
			$pagina = paginaTrocaVarValor($pagina,'#presente#',($tabela[0]['presente'] == '1' ? 'Sim' : 'N�o'));
			$pagina = paginaTrocaVarValor($pagina,'#interno#',($tabela[0]['interno']?'<span style="color:green;">Sim</span>':'<span style="color:blue;">N�o</span>'));
			$pagina = paginaTrocaVarValor($pagina,'#cortesia#',($tabela[0]['cortesia']?'<span style="color:green;">Sim</span>':'<span style="color:blue;">N�o</span>'));
			
			$pagina = paginaTrocaVarValor($pagina,'#dest_nome#',$tabela[0]['dest_nome']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_endereco#',$tabela[0]['dest_endereco']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_num#',($tabela[0]['dest_num']?' ':'').$tabela[0]['dest_num']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_complemento#',($tabela[0]['dest_complemento']?' ':'').$tabela[0]['dest_complemento']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_bairro#',($tabela[0]['dest_bairro']?' - ':'').$tabela[0]['dest_bairro']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_cidade#',$tabela[0]['dest_cidade']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_uf#',($tabela[0]['dest_uf']?' / ':'').$tabela[0]['dest_uf']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_cep#',$tabela[0]['dest_cep']);
			$pagina = paginaTrocaVarValor($pagina,'#tipo_frete#',$tipo_frete);
			$pagina = paginaTrocaVarValor($pagina,'#valor_frete#',preparar_float_4_texto($tabela[0]['valor_frete']));
			$pagina = paginaTrocaVarValor($pagina,'#codigo_rastreio#',$tabela[0]['codigo_rastreio']);
			$pagina = paginaTrocaVarValor($pagina,'#envio#',$envio);
			
			$pagina = paginaTrocaVarValor($pagina,'#opcoes#',$opcoes);
			
			$log_bd = banco_select_name
			(
				banco_campos_virgulas(Array(
					'valor',
					'data',
				))
				,
				"log",
				"WHERE id_referencia='".$id."'"
				." AND grupo='pedidos'"
				." ORDER BY data DESC"
			);
			
			if($log_bd)
			foreach($log_bd as $log){
				$log_txt .= ($log_txt ? '<br>' : '') . data_hora_from_datetime_to_text($log['data']) . ' - ' . $log['valor'];
			}
			
			$pagina = paginaTrocaVarValor($pagina,'#log#',$log_txt);
			
			$pagina = paginaTrocaVarValor($pagina,'#data#',data_hora_from_datetime_to_text($tabela[0]['data']));
			
			$data_full = $tabela[0]['data'];
			$data_arr = explode(' ',$data_full);
			
			$pedidos_produtos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.valor_original',
					't1.sub_total',
					't1.desconto',
					't1.codigo',
					't1.status',
					't2.nome',
				))
				,
				'pedidos_produtos as t1,produtos as t2',
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_produtos=t2.id_produtos"
				." ORDER BY t2.nome ASC"
			);
			
			$cel_nome = 'produtos';
			if($pedidos_produtos)
			foreach($pedidos_produtos as $res){
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#opcoes#",$opcoes);
				$cel_aux = modelo_var_troca($cel_aux,"#valor_original#",($res['t1.valor_original']?preparar_float_4_texto($res['t1.valor_original']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#sub_total#",($res['t1.sub_total']?preparar_float_4_texto($res['t1.sub_total']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#desconto#",$res['t1.desconto']);
				$cel_aux = modelo_var_troca($cel_aux,"#nome#",$res['t2.nome']);
				$cel_aux = modelo_var_troca($cel_aux,"#codigo#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#codigo_servico#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#data_pedido#",$data_arr[0]);
				$cel_aux = modelo_var_troca($cel_aux,"#senha#",$res['t1.senha']);
				
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		} else {
			$tabela = banco_select_name
			(
				banco_campos_virgulas(Array(
					'codigo',
					'senha',
					'data',
					'protocolo_baixa',
					'id_usuario_baixa',
					'data_baixa',
					'status',
					'presente',
					'de',
					'para',
					'mensagem',
					'observacao_baixa',
					'interno',
					'cortesia',
					'valor_total',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			$usuario_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't2.nome',
					't2.id_usuario',
				))
				,
				"usuario_pedidos as t1,usuario as t2",
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_usuario=t2.id_usuario"
			);
			
			// ================================= Local de Edi��o ===============================
			// Pegue os campos da interface e campos_guardar aqui
			
			/* $campos_guardar = Array(
				'codigo' => $tabela[0]['codigo'],
				'descricao' => $tabela[0]['descricao'],
			); */
			
			// ================================= Status ===============================
			
			$status_mudar = Array(
				'5' => '<span style="color:red;">Em disputa</span>',
				'6' => '<span style="color:brown;">Dinheiro Devolvido</span>',
				'7' => '<span style="color:brown;">Cancelado</span>',
				'F' => '<span style="color:brown;">Finalizado</span>',
				'A' => '<span style="color:green;">Pago</span>',
				'B' => '<span style="color:red;">Bloqueado</span>',
				'D' => '<span style="color:red;">Deletado</span>',
				'N' => '<span style="color:blue;">Aguardando pagamento</span>',
				'P' => '<span style="color:blue;">Em an�lise</span>',
			);
			
			$status = $tabela[0]['status'];
			
			foreach($status_mudar as $chave => $valor){
				if($tabela[0]['status'] == $chave){
					$tabela[0]['status'] = $valor;
					break;
				}
			}
			
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				'usuario',
				"WHERE id_usuario='".$tabela[0]['id_usuario_baixa']."'"
			);
			
			$pagina = paginaTrocaVarValor($pagina,'#usuario_pedido#',($usuario_pedidos[0]['t2.id_usuario']?"<a href=\"../usuarios/?opcao=editar&id=".$usuario_pedidos[0]['t2.id_usuario']."\"><b>".$usuario_pedidos[0]['t2.nome']."</b></a>":"Pedido Sem Usu�rio"));
			$pagina = paginaTrocaVarValor($pagina,'#codigo#',$tabela[0]['codigo']);
			$pagina = paginaTrocaVarValor($pagina,'#senha#',$tabela[0]['senha']);
			$pagina = paginaTrocaVarValor($pagina,'#status#',$tabela[0]['status']);
			$pagina = paginaTrocaVarValor($pagina,'#valor_total#',preparar_float_4_texto($tabela[0]['valor_total']));
			$pagina = paginaTrocaVarValor($pagina,'#presente#',($tabela[0]['presente'] == '1' ? 'Sim' : 'N�o'));
			$pagina = paginaTrocaVarValor($pagina,'#interno#',($tabela[0]['interno']?'<span style="color:green;">Sim</span>':'<span style="color:blue;">N�o</span>'));
			$pagina = paginaTrocaVarValor($pagina,'#cortesia#',($tabela[0]['cortesia']?'<span style="color:green;">Sim</span>':'<span style="color:blue;">N�o</span>'));
			
			$pagina = paginaTrocaVarValor($pagina,'#opcoes#',$opcoes);
			
			if($tabela[0]['presente'] == '1'){
				$pagina = paginaTrocaVarValor($pagina,'#de#',$tabela[0]['de']);
				$pagina = paginaTrocaVarValor($pagina,'#para#',$tabela[0]['para']);
				$pagina = paginaTrocaVarValor($pagina,'#mensagem#',$tabela[0]['mensagem']);
			} else {
				$cel_nome2 = 'mensagem-cel'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
			}
			
			if($status == 'F'){
				$pagina = paginaTrocaVarValor($pagina,'#protocolo#',$tabela[0]['protocolo_baixa']);
				$pagina = paginaTrocaVarValor($pagina,'#data_baixa#',data_hora_from_datetime_to_text($tabela[0]['data_baixa']));
				$pagina = paginaTrocaVarValor($pagina,'#usuario#',"<a href=\"../usuarios/?opcao=editar&id=".$tabela[0]['id_usuario_baixa']."\"><b>".$usuario[0]['nome']."</b></a>");
				$pagina = paginaTrocaVarValor($pagina,'#observacao_baixa#',$tabela[0]['observacao_baixa']);
			} else {
				$cel_nome2 = 'baixa-voucher-cel'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
			}
			
			$log_bd = banco_select_name
			(
				banco_campos_virgulas(Array(
					'valor',
					'data',
				))
				,
				"log",
				"WHERE id_referencia='".$id."'"
				." AND grupo='pedidos'"
				." ORDER BY data DESC"
			);
			
			if($log_bd)
			foreach($log_bd as $log){
				$log_txt .= ($log_txt ? '<br>' : '') . data_hora_from_datetime_to_text($log['data']) . ' - ' . $log['valor'];
			}
			
			$pagina = paginaTrocaVarValor($pagina,'#log#',$log_txt);
			
			$pagina = paginaTrocaVarValor($pagina,'#data#',data_hora_from_datetime_to_text($tabela[0]['data']));
			
			$data_full = $tabela[0]['data'];
			$data_arr = explode(' ',$data_full);
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.valor_original',
					't1.sub_total',
					't1.desconto',
					't1.codigo',
					't1.senha',
					't1.protocolo_baixa',
					't1.id_usuario_baixa',
					't1.data_baixa',
					't1.validade',
					't1.observacao_baixa',
					't1.status',
					't2.nome',
				))
				,
				'pedidos_servicos as t1,servicos as t2',
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_servicos=t2.id_servicos"
				." ORDER BY t2.nome ASC"
			);
			
			$cel_nome = 'servicos';
			if($pedidos_servicos)
			foreach($pedidos_servicos as $res){
				$cel_aux = $cel[$cel_nome];
				
				$usuario = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
					))
					,
					'usuario',
					"WHERE id_usuario='".$res['t1.id_usuario_baixa']."'"
				);
				
				if($res['t1.status'] == 'F'){
					$cel_aux = modelo_var_troca($cel_aux,"#opcoes_baixa#",$opcoes_baixa);
					$cel_aux = modelo_var_troca($cel_aux,"#observacao_baixa#",$res['t1.observacao_baixa']);
					$cel_aux = modelo_var_troca($cel_aux,"#protocolo_baixa#",$res['t1.protocolo_baixa']);
					$cel_aux = modelo_var_troca($cel_aux,"#data_baixa#",data_hora_from_datetime_to_text($res['t1.data_baixa']));
					$cel_aux = modelo_var_troca($cel_aux,"#usuario_baixa#","<a href=\"../usuarios/?opcao=editar&id=".$tabela[0]['id_usuario_baixa']."\"><b>".$usuario[0]['nome']."</b></a>");
					$cel_nome2 = 'validade'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
				} else {
					$cel_nome2 = 'baixa-cel'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
				}
				
				$cel_aux = modelo_var_troca($cel_aux,"#opcoes#",$opcoes);
				$cel_aux = modelo_var_troca($cel_aux,"#valor_original#",($res['t1.valor_original']?preparar_float_4_texto($res['t1.valor_original']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#sub_total#",($res['t1.sub_total']?preparar_float_4_texto($res['t1.sub_total']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#desconto#",$res['t1.desconto']);
				$cel_aux = modelo_var_troca($cel_aux,"#nome#",$res['t2.nome']);
				$cel_aux = modelo_var_troca($cel_aux,"#codigo#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#codigo_servico#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#data_pedido#",$data_arr[0]);
				$cel_aux = modelo_var_troca($cel_aux,"#senha#",$res['t1.senha']);
				$cel_aux = modelo_var_troca($cel_aux,"#validade#",date("d/m/Y",strtotime($data_arr[0] . " + ".$res['t1.validade']." day")));
				
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		}
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "editar_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		//if(!operacao('editar'))
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
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
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
			$status = '7';
			$status_tit = 'Cancelado';
		} else {
			$status = 'A';
			$status_tit = 'Pago';
		}
		
		banco_conectar();
		banco_update
		(
			$_LISTA['tabela']['status']."='".$status."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_update
		(
			$_LISTA['tabela']['status']."='".$status."'",
			'pedidos_servicos',
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		log_banco(Array(
			'id_referencia' => $id,
			'grupo' => 'pedidos',
			'valor' => '<b>Administra��o:</b> o usu�rio <b>'.$usuario['nome'].'</b> alterou o <b>status</b> para <b>'.$status_tit.'</b>',
		));
		
		banco_fechar_conexao();
	}
	
	return lista();
}

function voucher($imprimir = false){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_BANCO_PREFIXO;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_ALERTA;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$id_pedidos = $_REQUEST['id'];
	
	if($id_pedidos){
		// ============================== Pedido Atual
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
				'data',
				'presente',
				'de',
				'para',
				'mensagem',
				'codigo',
				'senha',
				'id_voucher_layouts',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND status='A'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		if($pedido){
			$pedido = $pedido[0];
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_layout']){
					$voucher = $_PROJETO['ecommerce']['voucher_layout'];
				}
			}
			if($_VARS['ecommerce']){
				if($_VARS['ecommerce']['voucher_base']){
					$voucher_base = $_VARS['ecommerce']['voucher_base'];
				}
			}
			
			if(!$voucher){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher < -->','<!-- voucher > -->');
				
				$voucher = $pagina;
			}
			if(!$voucher_base){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_base < -->','<!-- voucher_base > -->');
				
				$voucher_base = $pagina;
			}
			
			// ============================== Voucher Layout
			
			$voucher_layout = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_voucher_layouts',
					'imagem_topo',
					'imagem_textura',
					'cor_fundo',
				))
				,
				"voucher_layouts",
				"WHERE status='A'"
				." AND id_voucher_layouts='".$pedido['id_voucher_layouts']."'"
			);
			
			$voucher_layouts = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_voucher_layouts',
					'imagem_topo',
					'imagem_textura',
					'cor_fundo',
				))
				,
				"voucher_layouts",
				"WHERE status='A'"
				." ORDER BY id_voucher_layouts ASC"
			);
			
			$data_full = $pedido['data'];
			$data_arr = explode(' ',$data_full);
			
			$voucher_base = modelo_var_troca($voucher_base,"#data-expiracao#",date("d/m/Y",strtotime($data_arr[0] . " + ".($_ECOMMERCE['pedido_validade'] ? $_ECOMMERCE['pedido_validade'] : 90)." day")));
			
			$voucher = modelo_var_troca($voucher,"#voucher-topo#",$voucher_topo);
			$voucher = modelo_var_troca($voucher,"#voucher-base#",$voucher_base);
			
			$voucher = modelo_var_troca($voucher,"#voucher-codigo#",$pedido['codigo']);
			$voucher = modelo_var_troca($voucher,"#voucher-senha#",$pedido['senha']);
			$voucher = modelo_var_troca($voucher,"#voucher-de#",$pedido['de']);
			$voucher = modelo_var_troca($voucher,"#voucher-para#",$pedido['para']);
			$voucher = modelo_var_troca($voucher,"#voucher-mensagem#",$pedido['mensagem']);
			$voucher = modelo_var_troca($voucher,"#voucher-concluir#",$voucher_concluir);
			
			// ============ Voucher Layout
			
			if($voucher_layout){
				$voucher = modelo_var_troca($voucher,"#imagem_topo#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_topo']);
				$voucher = modelo_var_troca_tudo($voucher,"#imagem_textura#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_textura']);
				$voucher = modelo_var_troca($voucher,"#cor_fundo#",$voucher_layout[0]['cor_fundo']);
				
				$id_voucher_layouts = $voucher_layout[0]['id_voucher_layouts'];
			} else {
				$voucher = modelo_var_troca($voucher,"#imagem_topo#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layouts[0]['imagem_topo']);
				$voucher = modelo_var_troca_tudo($voucher,"#imagem_textura#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layouts[0]['imagem_textura']);
				$voucher = modelo_var_troca($voucher,"#cor_fundo#",$voucher_layouts[0]['cor_fundo']);
				
				$id_voucher_layouts = $voucher_layouts[0]['id_voucher_layouts'];
			}
			
			$cel_nome = 'voucher-layouts'; $cel[$cel_nome] = modelo_tag_val($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			$voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' -->','');
			
			$voucher = modelo_var_troca($voucher,'#voucher-tema-id-pedido#',$id_pedidos);
			
			// ============
			
			$cel_nome = 'lista-servicos'; $cel[$cel_nome] = modelo_tag_val($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.senha',
					't1.validade',
					't2.nome',
					't2.observacao',
				))
				,
				"pedidos_servicos as t1,servicos as t2",
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." AND t1.status='A'"
				." ORDER BY t2.nome ASC"
			);
			
			if($pedidos_servicos)
			foreach($pedidos_servicos as $pedido_servico){
				$cel_nome = 'lista-servicos';
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#servico-nome#",$pedido_servico['t2.nome'].($pedido_servico['t2.observacao']? '<br><span style="color:#888;"><b>Observa&ccedil;&atilde;o:</b> '.$pedido_servico['t2.observacao'] .'</span>': ''));
				$cel_aux = modelo_var_troca($cel_aux,"#servico-validade#",date("d/m/Y",strtotime($data_arr[0] . " + ".$pedido_servico['t1.validade']." day")));
				$cel_aux = modelo_var_troca($cel_aux,"#servico-codigo#",$pedido_servico['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#servico-senha#",$pedido_servico['t1.senha']);
				
				$voucher = modelo_var_in($voucher,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' -->','');
			
			$voucher_impressao = $voucher;
			$cel_nome = 'remover_impressao'; $voucher_impressao = modelo_tag_in($voucher_impressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			if(!$pedido['presente']){
				$cel_nome = 'de-para'; $voucher_impressao = modelo_tag_in($voucher_impressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				$cel_nome = 'de-para'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			} else {
				$cel_nome = 'de-para'; $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' < -->',''); $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' > -->','');
			}
			
			$_SESSION[$_SYSTEM['ID']."versao-impressao"] = $voucher_impressao;
			
			$cel_nome = 'remover_impressao'; $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' < -->',''); $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' > -->','');
			
			$menu_opcao = '<table width="100%" cellpadding="0" cellspacing="0">
	<tbody><tr>
		<td class="in_fundo"></td>
		<td class="in_fundo"></td>
		<td class="in_top_dir"></td>
	</tr>
	<tr>
		<td class="in_fundo">&nbsp;</td>
		<td class="in_fundo">
		#menu_opcao#
		</td>
		<td class="in_meio_dir">&nbsp;</td>
	</tr>
	<tr>
		<td class="in_inf_esq">&nbsp;</td>
		<td class="in_inf_meio">&nbsp;</td>
		<td class="in_inf_dir">&nbsp;</td>
	</tr>
</tbody></table>
<h2>Voucher</h2>';
			if($imprimir){
				$menu_opcao = modelo_var_troca($menu_opcao,"#menu_opcao#",'<img src="../../images/icons/imprimir.png" id="imprimir">');
			} else {
				$menu_opcao = modelo_var_troca($menu_opcao,"#menu_opcao#",'email');
			}
			
			$voucher = $menu_opcao . $voucher;
			
			$in_titulo = $param ? "Visualizar" : "Modificar";
			$botao = "Gravar";
			$opcao = "editar_base";
			
			$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
			$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
			$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
			$pagina = paginaTrocaVarValor($pagina,"#id",$id);
			
			if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			$_INTERFACE_OPCAO = 'editar';
			$_INTERFACE['local'] = 'conteudo';
			$_INTERFACE['informacao_titulo'] = $in_titulo;
			$_INTERFACE['informacao_tipo'] = $tipo;
			$_INTERFACE['informacao_id'] = $id;
			$_INTERFACE['inclusao'] = $voucher;
		
			return interface_layout(parametros_interface());
		} else {
			$_ALERTA .= '<p style="color:red;">O seu pedido n�o est� ativo</p><p>Houve algum problema com o pagamento ou o mesmo est� em processamento.</p><p>Se voc� efetuou o pagamento e houve confirma��o, aguarde no m�ximo 30 minutos at� o sistema atualizar os pagamentos e tente novamente. De qualquer forma o sistema enviar� automaticamente o seu voucher no seu email assim que houver confirma��o de pagamento pelo sistema de pagamento escolhido.</p>';
			
			return lista();
		}
	} else
		return lista();
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
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		banco_conectar();
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.codigo',
				't3.nome',
				't1.id_pedidos',
			))
			,
			"pedidos as t1,usuario_pedidos as t2,usuario as t3",
			"WHERE (
				(UCASE(t1.codigo) LIKE UCASE('%" . $query . "%'))
				OR 
				(UCASE(t3.nome) LIKE UCASE('%" . $query . "%'))
			)"
			." AND t1.id_pedidos=t2.id_pedidos"
			." AND t2.id_usuario=t3.id_usuario"
			." AND t1.status!='D'"
			." AND t1.id_loja='".$usuario['id_loja']."'"
		);
		
		banco_fechar_conexao();

		if($resultado)
		foreach($resultado as $res){
			$saida[] = Array(
				'value' => utf8_encode($res['t1.codigo'].' - '.$res['t3.nome']),
				'id' => $res['t1.id_pedidos'],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['mudar_envio']){
		banco_conectar();
		if(operacao('editar')){
			$status_envio = $_REQUEST['opcao'];
			$codigo_rastreio = $_REQUEST['codigo'];
			$id = $_REQUEST['id'];
			
			if($status_envio && $id){
				$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
				
				banco_update
				(
					($codigo_rastreio?"codigo_rastreio='".$codigo_rastreio."',":"").
					"status_envio='".$status_envio."'",
					"pedidos",
					"WHERE id_pedidos='".$id."'"
					." AND id_loja='".$usuario['id_loja']."'"
				);
				
				// =========== Notificar cliente
				
				global $_ECOMMERCE;
				global $_VARS;
				global $_HTML;
				
				$_ECOMMERCE['apenas_incluir'] = true;
				
				require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/index.php');
				
				if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						't3.nome',
						't3.email',
						't1.codigo',
					))
					,
					"pedidos as t1,usuario_pedidos as t2,usuario as t3",
					"WHERE t1.id_pedidos='".$id."'"
					." AND t1.id_pedidos=t2.id_pedidos"
					." AND t2.id_usuario=t3.id_usuario"
					." AND t1.id_loja='".$usuario['id_loja']."'"
				);
				
				if($resultado){
					$nome = $resultado[0]['t3.nome'];
					$email = $resultado[0]['t3.email'];
					$codigo = $resultado[0]['t1.codigo'];
					
					$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
					
					$parametros['from_name'] = $_HTML['TITULO'];
					$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
					
					$parametros['email_name'] = $nome;
					$parametros['email'] = $email;
					
					$parametros['subject'] = $_VARS['ecommerce']['pagseguro_notificacoes_assunto'];
					
					$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#codigo#",$codigo);
					
					switch($status_envio){
						case 'E':
							$titulo = "Enviado";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_enviado'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						case 'F':
							$titulo = "Entregue";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_entregue'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						case 'N':
							$titulo = "N�o enviado";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_nao_enviado'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						case 'M':
							$titulo = "Retirado em M�os";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_retirado_em_maos'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						
					}
					
					$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
					
					log_banco(Array(
						'id_referencia' => $id,
						'grupo' => 'pedidos',
						'valor' => '<b>Administra��o:</b> o usu�rio <b>'.$usuario['nome'].'</b> alterou o <b>status de envio</b> para <b>'.$titulo.'</b>'.($codigo_rastreio?' - codigo_rastreio: <b>'.$codigo_rastreio.'</b>':''),
					));
					
					if($codigo_rastreio)$parametros['mensagem'] .= '<p>Se desejar rastrear o seu pedido acesso o site dos correios (<a href="http://www.correios.com.br/">http://www.correios.com.br/</a>) e utilize o seguinte c�digo de rastreio: <b>'.$codigo_rastreio.'</b></p>';
					$parametros['mensagem'] .= ecommerce_pagseguro_lista_produtos($id);
					$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
					
					if($parametros['enviar_mail'])enviar_mail($parametros);
				} else {
					$saida = '<p>Esse pedido n�o pertence ao seu usu�rio.</p>';
				}
			} else {
				$saida = '<p>Houve um problema com a refer�ncia do pedido e op��o. Contate o suporte t�cnico.</p>';
			}
		} else {
			$saida = '<p>Voc� n�o tem permiss�o de mudar o envio.</p>';
		}
	}
	
	if($_REQUEST['validade']){
		banco_conectar();
		if(operacao('editar')){
			
			$validade = floor((strtotime(data_padrao_date($_REQUEST['validade']))/86400 - strtotime($_REQUEST['data_pedido'])/86400));
			
			if($validade >= 0){
				$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos',
					))
					,
					"pedidos",
					"WHERE codigo='".$_REQUEST['pedido_id']."'"
					." AND id_loja='".$usuario['id_loja']."'"
				);
				
				if($resultado){
					banco_update
					(
						"validade='".$validade."'",
						"pedidos_servicos",
						"WHERE codigo='".$_REQUEST['id']."'"
					);
					
					log_banco(Array(
						'id_referencia' => $resultado[0]['id_pedidos'],
						'grupo' => 'pedidos',
						'valor' => '<b>Administra��o:</b> o usu�rio <b>'.$usuario['nome'].'</b> alterou a validade para '.$validade.' dia(s) - '.$_REQUEST['validade'],
					));
				} else {
					$saida = '<p>Esse pedido n&atilde;o pertence ao seu usu&aacute;rio.</p>';
				}
			} else {
				$saida = '<p>N�o � poss�vel criar uma validade de data do passado.</p>';
			}
		} else {
			$saida = '<p>Voc� n�o tem permiss�o de mudar a validade.</p>';
		}
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
			//case 'add':							$saida = (operacao('adicionar') ? add() : lista()); break;
			//case 'add_base':					$saida = (operacao('adicionar') ? add_base() : lista());break;
			//case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'imprimir-voucher':			$saida = voucher(true);break;
			case 'email-voucher':				$saida = voucher();break;
			//case 'editar_base':					$saida = (operacao('editar') ? editar_base() : lista());break;
			//case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : lista());break;
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