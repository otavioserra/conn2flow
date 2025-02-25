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
$_LOCAL_ID					=	"videos";
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
$_JS['prettyPhoto'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'galerias_videos';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_'.'galerias_videos';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Galerias de Vídeos';
$_LISTA['ferramenta_unidade']	=	'a galeria de vídeo';

$_LISTA_2['tabela']['nome']		=	'videos';
$_LISTA_2['tabela']['campo']	=	'descricao';
$_LISTA_2['tabela']['id']		=	'id_'.'videos';
$_LISTA_2['tabela']['status']	=	'status';
$_LISTA_2['ferramenta']			=	$_SESSION[$_SYSTEM['ID']."nome"];

$_LISTA_3['tabela']['nome']		=	'videos_grupos';
$_LISTA_3['tabela']['campo']	=	'grupo';
$_LISTA_3['tabela']['id']		=	'id_'.'videos_grupos';
$_LISTA_3['tabela']['status']	=	'status';
$_LISTA_3['ferramenta']			=	'Vídeos Grupos';

$_HTML['separador'] = $_CAMINHO_RELATIVO_RAIZ;

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

function gerarThumb($photo,$output,$new_width){
    $source = imagecreatefromstring(file_get_contents($photo));
    list($width, $height) = getimagesize($photo);
    if($width>$new_width){
        $new_height = ($new_width/$width) * $height;
        $thumb = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagejpeg($thumb, $output, 100);
    } else {
        copy($photo, $output);
    }
}

// Funções do Sistema

function identificador_unico($id,$num = 0){
	global $_PALAVRAS_RESERVADAS;
	
	$conteudo = banco_select
	(
		"id_galerias_videos"
		,
		"galerias_videos",
		"WHERE identificador='".($num ? $id.'-'.$num : $id)."'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1);
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

function criar_identificador($id){
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
		
		return identificador_unico($id,$num);
	} else {
		return identificador_unico($id);
	}
}

function videos_grupos($params){
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
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Início', // Nome do menu
	);
	if(operacao('grupos')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=grupos', // link da opção
			'title' => 'Gerenciar Grupos de '.$_LISTA['ferramenta'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 2, // Linha background image
			'name' => 'Grupos', // Nome do menu
		);
	}
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
		
		if(operacao('videos_ver')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=conteudo&id=#id', // link da opção
				'title' => 'Conteúdo d' . $_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 10, // Coluna background image
				'img_linha' => 1, // Linha background image
				'name' => 'Conteúdo', // Nome do menu
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
	} else if(
		$_INTERFACE_OPCAO == 'layout_videos'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		if(operacao('editar')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=editar&id='.$_SESSION[$_SYSTEM['ID']."id"], // link da opção
				'title' => 'Editar essa galeria', // título da opção
				'img_coluna' => 5, // Coluna background image
				'img_linha' => 1, // Linha background image
				'name' => 'Editar', // Nome do menu
			);
		}
		if(operacao('videos_enviar')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=videos_enviar', // link da opção
				'title' => 'Enviar vídeos', // título da opção
				'img_coluna' => 2, // Coluna background image
				'img_linha' => 2, // Linha background image
				'name' => 'Enviar vídeos', // Nome do menu
			);
		}
		if(operacao('videos_ver')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=imagens', // link da opção
				'title' => 'Listar vídeos', // título da opção
				'img_coluna' => 2, // Coluna background image
				'img_linha' => 1, // Linha background image
				'name' => 'Listar vídeos', // Nome do menu
			);
		}
	}
	
	if(operacao('videos_ver')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=conteudo&id=#id', // link da opção
			'title' => 'Conteúdo d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 2, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Conteúdo', // Legenda
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
		'informacao_titulo' => $informacao_titulo . ($_INTERFACE_OPCAO != 'layout_videos' ? ' ' . $_LISTA['ferramenta'] : '') , // Título da Informação
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
	
	$nome = $params['nome'];
	$tabela_campos = $params['tabela_campos'];
	$tabela_nome = $params['tabela_nome'];
	$tabela_extra = $params['tabela_extra'];
	$tabela_order = $params['tabela_order'];
	$opcao_inicial = $params['opcao_inicial'];
	$link_extra = $params['link_extra'];
	$url = $params['url'];
	
	$grupo = videos_grupos(Array(
		'nome' => 'grupo',
		'tabela_campos' => Array(
			'id' => 'id_videos_grupos',
			'nome' => 'grupo',
		),
		'tabela_nome' => 'videos_grupos',
		'tabela_extra' => false,
		'tabela_order' => 'grupo ASC',
		'opcao_inicial' => 'Selecione o grupo',
		'link_extra' => false,
		'url' => false,
		'onchange' => false,
		'forcar_id' => false,
	));
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#nome',$nome);
	$pagina = paginaTrocaVarValor($pagina,'#descricao',$descricao);
	$pagina = paginaTrocaVarValor($pagina,'#grupo#',$grupo);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
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
	global $_USUARIO_ATIVO_2;
	global $_LISTA;
	global $_INTERFACE;
	
	banco_conectar();
	
	$identificador = $_REQUEST['nome'];
	$identificador = criar_identificador($identificador);
	
	$campos = null;
	
	$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "nome"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "descricao"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "grupo"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "identificador"; $post_nome = $campo_nome; 		$campos[] = Array($campo_nome,$identificador);
	
	banco_insert_name
	(
		$campos,
		$_LISTA['tabela']['nome']
	);
	
	banco_fechar_conexao();
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;

	return lista();
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
		
		$grupo = videos_grupos(Array(
			'nome' => 'grupo',
			'tabela_campos' => Array(
				'id' => 'id_videos_grupos',
				'nome' => 'grupo',
			),
			'tabela_nome' => 'videos_grupos',
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
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	
	if($_POST["id"]){
		$id = $_POST["id"];
		
		$campos_antes = campos_antes_recuperar();
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		$campo_tabela = "tabela";
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $mudar_identificador = true;}
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "grupo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
		if($mudar_identificador){
			$identificador = $_REQUEST['nome'];
			$identificador = criar_identificador($identificador);
			$campo_nome = "identificador"; 	$editar['tabela'][] = $campo_nome."='".$identificador."'";
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
		
		// ======================================================================================
		
		banco_fechar_conexao();
	}
	
	return lista();
}

function excluir(){ // Sem necessidade de edição
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM;
	global $_INTERFACE;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."galeria".$_SYSTEM['SEPARADOR'];
		
		banco_conectar();
		
		$imagens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_imagens',
				'local_original',
				'local_grande',
				'local_media',
				'local_mini',
			))
			,
			"imagens",
			"WHERE id_galerias='".$id."'"
		);
		
		if($imagens)
		foreach($imagens as $imagem){
			$campo = 'local_original';if($imagem[$campo]){$file = explode('/',$imagem[$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
			$campo = 'local_grande';if($imagem[$campo]){$file = explode('/',$imagem[$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
			$campo = 'local_media';if($imagem[$campo]){$file = explode('/',$imagem[$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
			$campo = 'local_mini';if($imagem[$campo]){$file = explode('/',$imagem[$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
			
			banco_delete
			(
				'imagens',
				"WHERE id_imagens='".$imagem['id_imagens']."'"
			);
		}
		
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

function bloqueio(){ // Sem necessidade de edição
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

// ===================== Grupos ===========================

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
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Início', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL, // link da opção
		'title' => 'Lista Galerias de Vídeos', // título da opção
		'img_coluna' => 3, // Coluna background image
		'img_linha' => 2, // Linha background image
		'name' => 'Galerias de Vídeos', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?opcao=grupos', // link da opção
		'title' => 'Lista ' . $_LISTA['ferramenta'], // título da opção
		'img_coluna' => 2, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Lista', // Nome do menu
	);
	if(operacao('grupos_adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=grupos_add', // link da opção
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
		
		if(operacao('grupos_excluir')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => '#', // link da opção
				'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 8, // Coluna background image
				'img_linha' => 1, // Linha background image
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','grupos_excluir')\"", // OPCIONAL - parâmetros extras no link
				'name' => 'Excluir', // Nome do menu
			);
		}
		if(operacao('grupos_bloquear')){
			$menu_principal[] = Array( // Opção: Bloquear
				'url' => $_URL . '?opcao=grupos_bloqueio&tipo=#tipo&id=#id', // link da opção
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
	
	if(operacao('grupos_ver') && !operacao('grupos_editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=grupos_ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
		);
	}
	if(operacao('grupos_editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=grupos_editar&id=#id', // link da opção
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Editar', // Legenda
		);
	}
	if(operacao('grupos_bloquear')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=grupos_bloqueio&tipo=#tipo&id=#id', // link da opção
			'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 6, // Coluna background image
			'img_linha' => 1, // Linha background image
			'img_coluna2' => 5, // Coluna background image
			'img_linha2' => 1, // Linha background image
			'bloquear' => true, // Se eh botão de bloqueio
			'legenda' => 'Ativar/Desativar', // Legenda
		);
	}
	if(operacao('grupos_excluir')){
		$menu_opcoes[] = Array( // Opção: Excluir
			'url' => '#', // link da opção
			'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 7, // Coluna background image
			'img_linha' => 1, // Linha background image
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','grupos_excluir')\"", // OPCIONAL - parâmetros extras no link
			'legenda' => 'Excluir', // Legenda
		);
	}
	
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
		'informacao_titulo' => $informacao_titulo . ($_INTERFACE_OPCAO != 'layout_videos' ? ' ' . $_LISTA['ferramenta'] : '') , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => operacao('buscar'), // Formulário de busca
		'busca_url' => $_URL, // Url da busca
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
		'layout_pagina' => true,
		
	);
	
	return $parametros;
}

function grupos(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(grupos_parametros_interface());
}

function grupos_add(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_INTERFACE_OPCAO;
	
	$in_titulo = "Inserir Grupos";
	$botao = "Gravar";
	$opcao = "grupos_add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- grupos < -->','<!-- grupos > -->');
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#grupo#',$grupo);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE_OPCAO = 'add';
	
	return interface_layout(grupos_parametros_interface());
}

function grupos_add_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_USUARIO_ATIVO_2;
	global $_LISTA_3;
	global $_INTERFACE;
	
	$_LISTA = $_LISTA_3;
	
	banco_conectar();
	
	$identificador = $_REQUEST['nome'];
	$identificador = criar_identificador($identificador);
	
	$campos = null;
	
	$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "grupo"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	
	banco_insert_name
	(
		$campos,
		$_LISTA['tabela']['nome']
	);
	
	banco_fechar_conexao();
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;

	return grupos();
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
		
		if(!operacao('grupos_editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
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

function grupos_editar_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA_3;
	
	$_LISTA = $_LISTA_3;
	
	if($_POST["id"]){
		$id = $_POST["id"];
		
		$campos_antes = campos_antes_recuperar();
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		$campo_tabela = "tabela";
		$campo_nome = "grupo"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
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
	
	return grupos();
}

function grupos_excluir(){ // Sem necessidade de edição
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA_3;
	global $_SYSTEM;
	global $_INTERFACE;
	
	$_LISTA = $_LISTA_3;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		banco_conectar();
		banco_update
		(
			"grupo=NULL",
			"galerias",
			"WHERE grupo='".$id."'"
		);
		banco_update
		(
			$_LISTA['tabela']['status']."='D'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_fechar_conexao();
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return grupos();
}

function grupos_bloqueio(){ // Sem necessidade de edição
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA_3;
	
	$_LISTA = $_LISTA_3;
	
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
	
	return grupos();
}

// ===================================================================
function conteudo(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_LISTA_2;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	
	if($id){
		banco_conectar();
		$tabela = banco_select
		(
			"nome",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_fechar_conexao();
		
		$_SESSION[$_SYSTEM['ID']."id"] = $id;
		$_SESSION[$_SYSTEM['ID']."nome"] = $tabela[0][0];
		
		$_LISTA_2['ferramenta']			=	$_SESSION[$_SYSTEM['ID']."nome"];
		
		return layout_videos();
	} else 
		return lista();
}

function layout_videos(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	global $_PAGINA_OPCAO;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_HTML;
	
	$id = $_SESSION[$_SYSTEM['ID']."id"];
	
	banco_conectar();
	
	$tabela = banco_select
	(
		"imagem_mini,descricao",
		$_LISTA_2['tabela']['nome'],
		"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
	);
	banco_fechar_conexao();
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- layout_videos < -->','<!-- layout_videos > -->');
	
	if($_PAGINA_OPCAO == 'videos_enviar'){
		$videos_enviar_flag = true;
	} else {
		if($tabela){
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta",lista_videos());
		} else {
			$videos_enviar_flag = true;
		}
	}
	
	if($videos_enviar_flag){
		if(operacao('videos_enviar')){
			$ferramenta = paginaModelo('html.html');
			$ferramenta = paginaTagValor($ferramenta,'<!-- videos_enviar < -->','<!-- videos_enviar > -->');
			
			$ferramenta = paginaTrocaVarValor($ferramenta,"#galeria_id",$id);
			$ferramenta = paginaTrocaVarValor($ferramenta,"#opcao",'cadastrar_videos');
			$ferramenta = paginaTrocaVarValor($ferramenta,"#botao",'Gravar');
			
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta",$ferramenta);
		} else {
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta","Sem vídeos cadastradas nessa galeria.");
		}
	}
	
	$pagina = modelo_var_troca_tudo($pagina,"!#caminho#!",$_HTML['separador']);
	
	$in_titulo = "Galeria " . $_LISTA_2['ferramenta'];
	
	$_INTERFACE_OPCAO = 'layout_videos';
	$_INTERFACE['local'] = 'conteudo';
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_tipo'] = $tipo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function cadastrar_videos(){
	global $_SYSTEM;
	global $_VARS;
	
	$videos_str = $_REQUEST['videos_youtube'];
	$id = $_REQUEST['id'];
	
	if($videos_str){
		$videos = explode(',',$videos_str);
		$image_front = $_SYSTEM['PATH'].'images'.$_SYSTEM['SEPARADOR'].'icons'.$_SYSTEM['SEPARADOR'].'play_video.png';
		
		$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."videos".$_SYSTEM['SEPARADOR'];
		$caminho_internet 		= 	"files/videos/";
		
		$extensao = 'jpg';
		
		if($_VARS['videos']['IMG_GRANDE_WIDTH'] && $_VARS['videos']['IMG_GRANDE_HEIGHT'])$flag_grande = true;
		if($_VARS['videos']['IMG_MEDIA_WIDTH'] && $_VARS['videos']['IMG_MEDIA_HEIGHT'])$flag_media = true;
		if($_VARS['videos']['IMG_MINI_WIDTH'] && $_VARS['videos']['IMG_MINI_HEIGHT'])$flag_mini = true;
		
		if($videos){
			banco_conectar();
			foreach($videos as $video){
				if($video){
					$video = str_replace(" ","",$video);
					
					$headers = get_headers('http://gdata.youtube.com/feeds/api/videos/' . $video);
					if(strpos($headers[0], '200')) {
						$campos = null;
			
						$campo_nome = "id_galerias_videos"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "codigo"; $campo_valor = $video; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "status"; $campo_valor = 'A'; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						
						banco_insert_name
						(
							$campos,
							"videos"
						);
						
						$videos_id = banco_last_id();
						
						$url = 'http://img.youtube.com/vi/'.$video.'/0.jpg';
						$img_original = 'videos_youtube_'.$id.'_imagem_'.$videos_id.'.jpg';
						
						$original = $caminho_fisico . $img_original;
						file_put_contents($original, file_get_contents($url));
						
						chmod($original , 0777);
						image_hover_image($original, $image_front);
						
						if($flag_grande)$img_grande = "videos_youtube_".$id."_imagem_".$videos_id."_grande.".$extensao;
						if($flag_media)$img_media = "videos_youtube_".$id."_imagem_".$videos_id."_media.".$extensao;
						if($flag_mini)$img_mini = "videos_youtube_".$id."_imagem_".$videos_id."_mini.".$extensao;
						
						if($flag_grande)$grande = $caminho_fisico . $img_grande;
						if($flag_media)$media = $caminho_fisico . $img_media;
						if($flag_mini)$mini = $caminho_fisico . $img_mini;
						
						$_RESIZE_IMAGE_Y_ZERO = true;
						
						if($flag_grande)resize_image($original, $grande, $_VARS['videos']['IMG_GRANDE_WIDTH'], $_VARS['videos']['IMG_GRANDE_HEIGHT'],false,false,true);
						if($flag_media)resize_image($original, $media, $_VARS['videos']['IMG_MEDIA_WIDTH'], $_VARS['videos']['IMG_MEDIA_HEIGHT'],false,false,true);
						if($flag_mini)resize_image($original, $mini, $_VARS['videos']['IMG_MINI_WIDTH'], $_VARS['videos']['IMG_MINI_HEIGHT'],false,false,true);
						
						$campo_tabela = "videos";
						$campo_nome = "imagem_original"; 					$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_original . "'";
						$campo_nome = "imagem_grande"; if($flag_grande){	$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_grande . "'";}
						$campo_nome = "imagem_media"; if($flag_media){		$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_media . "'";}
						$campo_nome = "imagem_mini"; if($flag_mini){		$editar[$campo_tabela][] = $campo_nome."='" . $caminho_internet . $img_mini . "'";}
						
						$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
						
						if($editar_sql[$campo_tabela]){
							banco_update
							(
								$editar_sql[$campo_tabela],
								"videos",
								"WHERE id_videos='".$videos_id."'"
							);
						}
					}
				}
			}
			banco_fechar_conexao();
		}
		
		return layout_videos();
	} else 
		return lista();
}

function lista_videos(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_DADOS;
	global $_PAGINA_MENU_NUM_PAGINAS;
	global $_LISTA;
	global $_HTML;
	
	$id = $_SESSION[$_SYSTEM['ID']."id"];
	$num_cols = 4;
	
	banco_conectar();
	
	$videos = banco_select
	(
		"imagem_mini,descricao,id_videos,codigo"
		,
		"videos",
		"WHERE id_galerias_videos='".$id."'".
		" AND status!='D'"
	);
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- lista_videos < -->','<!-- lista_videos > -->');
	
	if(!operacao('videos_editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	if(!operacao('videos_excluir'))$cel_nome = 'excluir'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	
	$col1 = paginaTagValor($pagina,'<!-- col1 < -->','<!-- col1 > -->');
	$pagina = paginaTrocaTag($pagina,'<!-- col1 < -->','<!-- col1 > -->','<!-- col1 -->');
	$row1 = paginaTagValor($pagina,'<!-- row1 < -->','<!-- row1 > -->');
	$pagina = paginaTrocaTag($pagina,'<!-- row1 < -->','<!-- row1 > -->','<!-- row1 -->');
	
	for($i=0;$i<count($videos);$i++){
		if($i == 0)
			$cel_aux2 = $row1;

		$cel_aux = $col1;
		
		$inserir_col = true;
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#image",videos($videos[$i][3],$id,$videos[$i][2],$videos[$i][1]));
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#descricao_name",'descricao'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#descricao_id",'descricao'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#descricao_val",$videos[$i][1]);
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#imagem_name",'imagem'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#imagem_id",'imagem'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#imagem_val",$videos[$i][2]);
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#id",$videos[$i][2]);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#excluir_url",$_LOCAL_ID);
		
		$cel_aux2 = paginaInserirValor($cel_aux2,'<!-- col1 -->',$cel_aux);
		
		if($i > 0){
			if($i % $num_cols == $num_cols-1){
				$pagina = paginaInserirValor($pagina,'<!-- row1 -->',$cel_aux2);
				
				$cel_aux2 = $row1;
				
				$inserir_col = false;
			}
		}
		
		$descricao = $videos[$i][1];
		
		$campos_guardar[] = Array(
			'num'		=> $i,
			'descricao' => $descricao,
		);
	}
	
	campos_antes_guardar($campos_guardar);
	
	if($inserir_col)
		$pagina = paginaInserirValor($pagina,'<!-- row1 -->',$cel_aux2);
	
	banco_fechar_conexao();	
	
	return $pagina;
}

function editar_imagens(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	
	$id = $_SESSION[$_SYSTEM['ID']."id"];
	
	$campos_antes = campos_antes_recuperar();
	
	if($campos_antes){
		banco_conectar();
		
		foreach($campos_antes as $campo){
			if($campo["descricao"] != $_POST["descricao".$campo["num"]]){
				banco_update
				(
					"descricao='".$_POST["descricao".$campo["num"]]."'",
					"videos",
					"WHERE id_videos='".$_POST["imagem".$campo["num"]]."'"
				);
			}
		}
		
		banco_fechar_conexao();
	}
	
	return layout_videos();
}

function excluir_imagens(){ // Sem necessidade de edição
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."videos".$_SYSTEM['SEPARADOR'];
		
		banco_conectar();
		$imagens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'imagem_original',
				'imagem_grande',
				'imagem_media',
				'imagem_mini',
			))
			,
			"videos",
			"WHERE id_videos='".$id."'"
		);
		
		$campo = 'imagem_original';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		$campo = 'imagem_grande';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		$campo = 'imagem_media';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		$campo = 'imagem_mini';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		
		banco_delete
		(
			'videos',
			"WHERE id_videos='".$id."'"
		);
		banco_fechar_conexao();
	}
	
	return layout_videos();
}

function videos($video_cod,$id,$id_video,$titulo){
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
	
	if(!$class)$class = 'imagem';
	
	$imagem_path = 'files/videos/videos_youtube_'.$id.'_imagem_'.$id_video.'_mini.jpg';
	
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
				'href' => 'http://www.youtube.com/watch?v='.$video_cod,
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

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	global $_LISTA_3;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = $_REQUEST["query"];
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
				'value' => $resultado[$i][1],
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['query_id'] == 'busca_nome2' && operacao('buscar')){
		$query = $_REQUEST["query"];
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
				'value' => $resultado[$i][1],
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['query_id'] == 'busca_nome3' && operacao('buscar')){
		$query = $_REQUEST["query"];
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
			case 'add':							$saida = (operacao('adicionar') ? add() : lista()); break;
			case 'add_base':					$saida = (operacao('adicionar') ? add_base() : lista());break;
			case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'editar_base':					$saida = (operacao('editar') ? editar_base() : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : lista());break;
			case 'imagens':						$saida = (operacao('videos_ver') ? layout_videos() : lista());break;
			case 'videos_enviar':				$saida = (operacao('videos_enviar') ? layout_videos() : lista());break;
			case 'cadastrar_videos':			$saida = (operacao('videos_enviar') ? cadastrar_videos() : lista());break;
			case 'busca_ver':					$saida = (operacao('videos_ver') ? conteudo() : (operacao('buscar') ? editar('ver') : lista()));break;
			case 'conteudo':					$saida = (operacao('videos_ver') ? conteudo() : lista());break;
			case 'editar_imagens':				$saida = (operacao('videos_editar') ? editar_imagens() : lista());break;
			case 'imagem_excluir':				$saida = (operacao('videos_excluir') ? excluir_imagens() : lista());break;
			case 'menu_'.$_LOCAL_ID."_grupos":
			case 'grupos':						$saida = (operacao('grupos') ? grupos() : lista());break;
			case 'grupos_add':					$saida = (operacao('grupos_adicionar') ? grupos_add() : lista()); break;
			case 'grupos_add_base':				$saida = (operacao('grupos_adicionar') ? grupos_add_base() : lista());break;
			case 'busca_ver2':					$saida = (operacao('grupos_ver') ? grupos_editar() : (operacao('buscar') ? grupos_editar('grupos_ver') : grupos()));break;
			case 'grupos_editar':				$saida = (operacao('grupos_editar') ? grupos_editar() : lista());break;
			case 'grupos_ver':					$saida = (operacao('grupos_ver') ? grupos_editar('grupos_ver') : lista());break;
			case 'grupos_editar_base':			$saida = (operacao('grupos_editar') ? grupos_editar_base() : lista());break;
			case 'grupos_excluir':				$saida = (operacao('grupos_excluir') ? grupos_excluir() : lista());break;
			case 'grupos_bloqueio':				$saida = (operacao('grupos_bloquear') ? grupos_bloqueio() : lista());break;
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