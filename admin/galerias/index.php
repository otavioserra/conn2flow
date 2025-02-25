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
$_LOCAL_ID					=	"galerias";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_CONTEUDO			=	true;
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Galerias de Imagens.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['tinyMce'].
$_JS['swfUpload'].
$_JS['prettyPhoto'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'galerias';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_'.'galerias';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Galerias de Imagens';
$_LISTA['ferramenta_unidade']	=	'a galeria';

$_LISTA_2['tabela']['nome']		=	'imagens';
$_LISTA_2['tabela']['campo']	=	'descricao';
$_LISTA_2['tabela']['id']		=	'id_'.'imagens';
$_LISTA_2['tabela']['status']	=	'status';
$_LISTA_2['ferramenta']			=	$_SESSION[$_SYSTEM['ID']."nome"];

$_LISTA_3['tabela']['nome']		=	'galerias_grupos';
$_LISTA_3['tabela']['campo']	=	'grupo';
$_LISTA_3['tabela']['id']		=	'id_'.'galerias_grupos';
$_LISTA_3['tabela']['status']	=	'status';
$_LISTA_3['ferramenta']			=	'Galeria Grupos';

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
		"id_galerias"
		,
		"galerias",
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
	$tabela_campos[] = 'descricao';
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
		
		if(operacao('imagens_ver')){
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
		$_INTERFACE_OPCAO == 'layout_imagens'
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
		if(operacao('imagens_uploads')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=upload_imagens', // link da opção
				'title' => 'Enviar imagens', // título da opção
				'img_coluna' => 2, // Coluna background image
				'img_linha' => 2, // Linha background image
				'name' => 'Enviar imagens', // Nome do menu
			);
		}
		if(operacao('imagens_ver')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=imagens', // link da opção
				'title' => 'Listar imagens', // título da opção
				'img_coluna' => 2, // Coluna background image
				'img_linha' => 1, // Linha background image
				'name' => 'Listar imagens', // Nome do menu
			);
		}
	}
	
	if(operacao('imagens_ver')){
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
		'campo' => 'Descrição', // Valor do campo
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
	
	$pagina = formulario_preparar(Array(
		'pagina' => $pagina,
		'local' => 'add',
	));
	
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
	
	$id_tabela = banco_last_id();
	
	banco_fechar_conexao();
	
	return Array(
		'id_conteudo' => $id_tabela
	);
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
		
		if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
		$pagina = formulario_preparar(Array(
			'pagina' => $pagina,
			'local' => 'editar',
		));
		
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
	
	return Array(
		'id_galerias' => $id
	);
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
		'title' => 'Lista Galerias', // título da opção
		'img_coluna' => 3, // Coluna background image
		'img_linha' => 2, // Linha background image
		'name' => 'Galerias', // Nome do menu
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
		'informacao_titulo' => $informacao_titulo . ($_INTERFACE_OPCAO != 'layout_imagens' ? ' ' . $_LISTA['ferramenta'] : '') , // Título da Informação
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
		
		return layout_imagens();
	} else 
		return lista();
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
	
	$id = $_SESSION[$_SYSTEM['ID']."id"];
	
	banco_conectar();
	
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
	
	$tabela = banco_select
	(
		"local_mini,descricao",
		$_LISTA_2['tabela']['nome'],
		"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
	);
	banco_fechar_conexao();
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- layout_imagens < -->','<!-- layout_imagens > -->');
	
	if($_PAGINA_OPCAO == 'upload_imagens'){
		$upload_arquivos_flag = true;
	} else {
		if($tabela){
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta",lista_imagens());
		} else {
			$upload_arquivos_flag = true;
		}
	}
	
	if($upload_arquivos_flag){
		if(operacao('imagens_uploads')){
			$ferramenta = paginaModelo('html.html');
			$ferramenta = paginaTagValor($ferramenta,'<!-- upload_imagens < -->','<!-- upload_imagens > -->');
			
			$ferramenta = paginaTrocaVarValor($ferramenta,"#galeria_id",$id);
			$ferramenta = paginaTrocaVarValor($ferramenta,"#usuario",$upload_permissao['usuario']);
			$ferramenta = paginaTrocaVarValor($ferramenta,"#sessao",$upload_permissao['session_id']);
			
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta",$ferramenta);
		} else {
			$pagina = paginaTrocaVarValor($pagina,"#ferramenta","Sem imagens cadastradas nessa galeria.");
		}
	}
	
	$pagina = modelo_var_troca_tudo($pagina,"!#caminho#!",$_HTML['separador']);
	
	$in_titulo = "Galeria " . $_LISTA_2['ferramenta'];
	
	$informacao_abaixo = formulario_preparar(Array(
		'sem_conteiner' => true,
		'local' => 'editar_static',
	));
	
	$_INTERFACE_OPCAO = 'layout_imagens';
	$_INTERFACE['local'] = 'conteudo';
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_tipo'] = $tipo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina.$informacao_abaixo;

	return interface_layout(parametros_interface());
}

function lista_imagens(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_DADOS;
	global $_PAGINA_MENU_NUM_PAGINAS;
	global $_LISTA;
	global $_HTML;
	
	$id = $_SESSION[$_SYSTEM['ID']."id"];
	$num_cols = 4;
	
	banco_conectar();
	
	$imagens = banco_select
	(
		"local_mini,descricao,id_imagens,local_original"
		,
		"imagens",
		"WHERE id_galerias='".$id."'".
		" AND status!='D'"
	);
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- lista_imagens < -->','<!-- lista_imagens > -->');
	
	if(!operacao('imagens_editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	if(!operacao('imagens_excluir'))$cel_nome = 'excluir'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	
	$col1 = paginaTagValor($pagina,'<!-- col1 < -->','<!-- col1 > -->');
	$pagina = paginaTrocaTag($pagina,'<!-- col1 < -->','<!-- col1 > -->','<!-- col1 -->');
	$row1 = paginaTagValor($pagina,'<!-- row1 < -->','<!-- row1 > -->');
	$pagina = paginaTrocaTag($pagina,'<!-- row1 < -->','<!-- row1 > -->','<!-- row1 -->');
	
	for($i=0;$i<count($imagens);$i++){
		if($i == 0)
			$cel_aux2 = $row1;

		$cel_aux = $col1;
		
		$inserir_col = true;
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#image",imagens($id,$imagens[$i][0],$imagens[$i][3],$imagens[$i][1]));
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#descricao_name",'descricao'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#descricao_id",'descricao'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#descricao_val",$imagens[$i][1]);
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#imagem_name",'imagem'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#imagem_id",'imagem'.$i);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#imagem_val",$imagens[$i][2]);
		
		$cel_aux = paginaTrocaVarValor($cel_aux,"#id",$imagens[$i][2]);
		$cel_aux = paginaTrocaVarValor($cel_aux,"#excluir_url",$_LOCAL_ID);
		
		$cel_aux2 = paginaInserirValor($cel_aux2,'<!-- col1 -->',$cel_aux);
		
		if($i > 0){
			if($i % $num_cols == $num_cols-1){
				$pagina = paginaInserirValor($pagina,'<!-- row1 -->',$cel_aux2);
				
				$cel_aux2 = $row1;
				
				$inserir_col = false;
			}
		}
		
		$descricao = $imagens[$i][1];
		
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
					"imagens",
					"WHERE id_imagens='".$_POST["imagem".$campo["num"]]."'"
				);
			}
		}
		
		banco_fechar_conexao();
	}
	
	return layout_imagens();
}

function excluir_imagens(){ // Sem necessidade de edição
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."galeria".$_SYSTEM['SEPARADOR'];
		
		banco_conectar();
		$imagens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'local_original',
				'local_grande',
				'local_media',
				'local_mini',
			))
			,
			"imagens",
			"WHERE id_imagens='".$id."'"
		);
		
		$campo = 'local_original';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		$campo = 'local_grande';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		$campo = 'local_media';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		$campo = 'local_mini';if($imagens[0][$campo]){$file = explode('/',$imagens[0][$campo]);unlink($caminho_fisico.$file[(count($file)-1)]);}
		
		banco_delete
		(
			'imagens',
			"WHERE id_imagens='".$id."'"
		);
		banco_fechar_conexao();
	}
	
	return layout_imagens();
}

function imagens($id,$mini,$original,$titulo){
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

// ==================================== Conteúdos Estáticos ==================================================

function galeria_conteudos($id_galerias){
	$conteudos = conteudo_com_permissao(Array(
		'campo' => 'galeria',
		'campo_valor' => $id_galerias,
	));
	
	$conteudos2 = conteudo_com_permissao(Array(
		'campo' => 'galeria_todas',
	));
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'grupo',
		))
		,
		"galerias",
		"WHERE id_galerias='".$id_galerias."'"
	);
	
	if($resultado){
		$galeria_grupo = $resultado[0]['grupo'];
	}

	if($galeria_grupo){
		$conteudos3 = conteudo_com_permissao(Array(
			'campo' => 'galeria_grupo',
			'campo_valor' => $galeria_grupo,
		));
	}
	
	if($conteudos2){
		if($conteudos){
			foreach($conteudos2 as $con2){
				$found = false;
				foreach($conteudos as $con){
					if($con == $con2){
						$found = true;
					}
				}
				
				if(!$found){
					$conteudos_inserir[] = $con2;
				}
			}
			
			if($conteudos_inserir)
			$conteudos = array_merge($conteudos,$conteudos_inserir);
		} else {
			$conteudos = $conteudos2;
		}
	}
	
	if($conteudos3){
		if($conteudos){
			foreach($conteudos3 as $con3){
				$found = false;
				foreach($conteudos as $con){
					if($con == $con3){
						$found = true;
					}
				}
				
				if(!$found){
					$conteudos_inserir2[] = $con3;
				}
			}
			
			if($conteudos_inserir2)
			$conteudos = array_merge($conteudos,$conteudos_inserir2);
		} else {
			$conteudos = $conteudos3;
		}
	}

	return $conteudos;
}

function editar_static(){
	$id_galerias = $_REQUEST['id_galerias'];
	
	$conteudos = galeria_conteudos($id_galerias);
	
	return json_encode(Array(
		'conteudos' => $conteudos,
		'Ok' => true
	));
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	global $_LISTA_3;
	
	if($_REQUEST['b2make_ajax']){
		if(!formulario_verificar_autenticidade()){
			return 'Sem permissao de acesso!';
		}

		switch($_REQUEST['opcao']){
			case 'add_base':			$saida = (operacao('adicionar') ? add_base() : Array('ERRO' => 'Sem permissão de acesso'));break;
			case 'editar_base':			$saida = (operacao('editar') ? editar_base() : Array('ERRO' => 'Sem permissão de acesso'));break;
			case 'editar_static':		$saida = (operacao('editar') ? editar_static() : Array('ERRO' => 'Sem permissão de acesso'));break;
		}
		
		$saida = json_encode($saida);
	}
	
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
			case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : lista());break;
			case 'imagens':						$saida = (operacao('imagens_ver') ? layout_imagens() : lista());break;
			case 'upload_imagens':				$saida = (operacao('imagens_uploads') ? layout_imagens() : lista());break;
			case 'busca_ver':					$saida = (operacao('imagens_ver') ? conteudo() : (operacao('buscar') ? editar('ver') : lista()));break;
			case 'conteudo':					$saida = (operacao('imagens_ver') ? conteudo() : lista());break;
			case 'editar_imagens':				$saida = (operacao('imagens_editar') ? editar_imagens() : lista());break;
			case 'imagem_excluir':				$saida = (operacao('imagens_excluir') ? excluir_imagens() : lista());break;
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