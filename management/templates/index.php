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
$_LOCAL_ID					=	"templates";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_LOJA				=	true;
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

$_HTML['titulo'] 						= 	$_HTML['titulo']."Templates.";
$_HTML['variaveis']['titulo-modulo']	=	'Templates';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"../js.js?v=".$_VERSAO_MODULO."\"></script>\n".
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= 
"<link href=\"../css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'site_templates';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'site_templates';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Templates';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// B2make

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
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	/* $menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => '../../dashboard/',// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Início', // Nome do menu
	); */
	
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
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => ' Gestão / ' . $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
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
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_loja='".$usuario['id_loja']."'", // Tabela extra
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

function editar($params = false){
	global $_SYSTEM;
	global $_MODELO_MAIS_PAGINAS;
	global $_VARIAVEIS_JS;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
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
	
	if($pagina == 1) $modulo .= '<div data-id="1" class="b2make-templates-cont">'.
		'<div class="b2make-templates-imagem" style="background-image:url('.'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'includes/autenticar/images/b2make-signup-sem-imagem.png)">
			<div class="b2make-templates-imagem-mask"></div>
			<div class="b2make-templates-escolher" style="top:70px;">Escolher</div>
		</div>'.
	'</div>';
	
	if($site_templates)
	foreach($site_templates as $template){
		$modulo .= '<div data-id="'.$template['id_site_templates'].'" data-identificador="'.$template['id'].'" data-nome="'.($params['ajax']?$template['nome']:$template['nome']).'" class="b2make-templates-cont">'.
			'<div class="b2make-templates-imagem" style="background-image:url('.'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].($template['imagem'] ? $template['imagem'].'?v='.$template['imagem_versao'] : 'files/projeto/images/b2make-template-branco.png').')">
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
	
	$modulo = paginaTrocaVarValor($modulo,"#botao",$botao);
	$modulo = paginaTrocaVarValor($modulo,"#opcao",$opcao);
	$modulo = paginaTrocaVarValor($modulo,"#id",$id);
	$modulo = paginaTrocaVarValor($modulo,"#more_options",$more_options);
	
	//if(!operacao('editar'))$cel_nome = 'botao'; $modulo = modelo_tag_in($modulo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$_INTERFACE_OPCAO = 'editar';
	$_INTERFACE['local'] = 'conteudo';
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_tipo'] = $tipo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $modulo;

	return interface_layout(parametros_interface());
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
		
		$dom = new DOMDocument("1.0", "UTF-8");
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		
		$mp3player = $dom->appendChild(new DOMElement('mp3player'));
		
		$mp3 = $mp3player->appendChild(new DOMElement('mp3'));
		$attr = $mp3->setAttributeNode(new DOMAttr('id', 1));
		
		$title = $mp3->appendChild(new DOMElement('title',$conteudo[0]['titulo']));
		$artist = $mp3->appendChild(new DOMElement('artist',$conteudo[0]['sub_titulo']));
		$url = $mp3->appendChild(new DOMElement('url',$_HTML['separador'].$conteudo[0]['musica']));
		
		header("Content-Type: text/xml");
		echo $dom->saveXML();
	}
}

function ajax(){
	global $_SYSTEM;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	global $_MODELO_MAIS_PAGINAS;
	global $_INTERFACE;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = $_REQUEST["query"];
		if(!$query) return;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D'"
			." AND id_loja='".$usuario['id_loja']."'"
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
	
	if($_REQUEST["opcao"] == 'b2make-templates'){
		if($_REQUEST["id"]){
			$id = $_REQUEST["id"];
			$_SESSION[$_SYSTEM['ID']."b2make-change-template"] = true;
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
		$modelos = editar(Array(
			'modelos' => true,
			'widget' => true,
			'ajax' => true,
		));
		
		$saida = Array(
			'status' => 'Ok',
			'modelos' => $_INTERFACE['inclusao']
		);
		
		if($_MODELO_MAIS_PAGINAS){
			$saida['mais_paginas'] = 'sim';
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
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if($_REQUEST[xml]){
		xml();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			//case 'remover_item':				$saida = remover_item();break;
			default: 							$saida = editar();
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