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

$_VERSAO_MODULO				=	'1.2.2';
$_LOCAL_ID					=	"email_markenting";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_MAILER			=	true;
$_INCLUDE_SLICER			=	true;
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Email Markenting.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'email_markenting';
$_LISTA['tabela']['campo']			=	'assunto';
$_LISTA['tabela']['id']				=	'id_'.'email_markenting';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Email Marketing';
$_LISTA['ferramenta_unidade']		=	'essa email marketing';

$_HTML['separador']					=	$_CAMINHO_RELATIVO_RAIZ;

// Funções de assistência

function alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	
	switch ($nAlerta){
		case 1:		$mensSaida	=	"Só é permitido enviar imagem do tipo JPG";break;
		case 2:		$mensSaida	=	"Não há emails para enviar!";break;
		//case 1:		$mensSaida	=	"";break;
		default:	$mensSaida	=	"Houve algum problema!";
	}

	$_ALERTA = $mensSaida;
}

function url_path(){
	global $_SYSTEM;
	
	return 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'];
}

// Manipulação de Imagem

function imagem_pecas_tabela($entrada_fisica,$saida_fisica,$saida_url,$peca_x,$peca_y,$imagem_url){
	if(file_exists($entrada_fisica)){
		$slicer = new Slicer();
		$slicer->set_picture($entrada_fisica);
		$slicer->set_slice_res($peca_x,$peca_y);
		$slicer->save_slices_res("jpg",$saida_fisica);
		
		if($imagem_url){
			$linkP1 = "<a href=\"".$imagem_url."\" target=\"_blank\">";
			$linkP2 = "</a>";
		}

		$tabela = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		$c = 0;
		for($i=0;$i<$slicer->slice_hor;$i++){
			$tabela .= "<tr>\n";
			for($j=0;$j<$slicer->slice_ver;$j++){
				$tabela .= "<td>".$linkP1."<img src=\"".$saida_url.$c.".jpg\" border=\"0\">".$linkP2."</td>\n";
				chmod($saida_fisica.$c.".jpg",0777);
				$c++;
			}
			$tabela .= "</tr>\n";
		}
		$tabela .= "</table>\n";
	}
	
	return $tabela;
}

// Funções do Sistema

// ================================= Responsáveis ===============================

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_EMAIL;
	
	$informacao_abaixo = html(Array(
		'tag' => 'input',
		'attr' => Array(
			'name' => 'enviando',
			'id' => 'enviando',
			'type' => 'hidden',
			'value' => $_EMAIL['enviando'] ? '1' : false,
		),
	));
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = 'status';
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => '../',// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'home_mini.png', // caminho da imagem
		'name' => 'Início', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL, // link da opção
		'title' => 'Lista ' . $_LISTA['ferramenta'], // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'lista.jpg', // caminho da imagem
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
		'name' => 'Lista', // Nome do menu
	);
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'entrar.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Adicionar', // Nome do menu
		);
	}
	
	if($_INTERFACE_OPCAO == 'editar'){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		if(operacao('excluir')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => '#', // link da opção
				'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'db_remove.png', // caminho da imagem
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Excluir', // Nome do menu
			);
		}
		if(operacao('enviar_emails')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => '#', // link da opção
				'title' => 'Enviar e-mail', // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'email_big.png', // caminho da imagem
				'link_extra' => " class=\"enviar_mail\" id=\"enviar_mail_#id\"", // OPCIONAL - parâmetros extras no link
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Enviar e-mail', // Nome do menu
			);
		}
	}
	
	if(operacao('enviar_emails')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=envios', // link da opção
			'title' => 'Envios ' . $_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'envios.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Envios', // Nome do menu
		);
	}
	
	if(operacao('enviar_emails')){
		$menu_opcoes[] = Array( // Opção: Conteúdo
			'url' => '#', // link da opção
			'title' => 'Enviar e-mails', // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'email.png', // caminho da imagem
			'link_extra' => " class=\"enviar_mail\" id=\"enviar_mail_#id\"", // OPCIONAL - parâmetros extras no link
			'legenda' => 'Enviar e-mails', // Legenda
		);
	}
	if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'textos.png', // caminho da imagem
			'legenda' => 'Ver', // Legenda
		);
	}
	if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=editar&id=#id', // link da opção
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'editar.png', // caminho da imagem
			'legenda' => 'Editar', // Legenda
		);
	}
	if(operacao('excluir')){
		$menu_opcoes[] = Array( // Opção: Excluir
			'url' => '#', // link da opção
			'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'excluir.png', // caminho da imagem
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
			'legenda' => 'Excluir', // Legenda
		);
	}
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
		'width' => 50,
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Assunto', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => ($_INTERFACE_OPCAO == "grupos" ? false : operacao('buscar')), // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar legenda
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D'", // Tabela extra
		'tabela_order' => $tabela_order, // Ordenação da tabela
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => ($_INTERFACE_OPCAO == "grupos" ? false : $menu_principal),
		'menu_opcoes' => ($_INTERFACE_OPCAO == "grupos" ? false : $menu_opcoes),
		'header_campos' => $header_campos,
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => 'Ação', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 22), // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'campos' => $campos,
		'informacao_abaixo' => $informacao_abaixo,
		'layout_pagina' => true,
		
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	global $_EMAIL;
	
	banco_conectar();
	$variavel_global = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"variavel_global",
		"WHERE variavel='MAILER'"
	);
	
	$_EMAIL['enviando'] = $variavel_global[0]['valor'] == 'A' ? true : false;
	banco_fechar_conexao();
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface());
}

function add(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#assunto',$assunto);
	$pagina = paginaTrocaVarValor($pagina,'#imagem',$imagem);
	$pagina = paginaTrocaVarValor($pagina,'#imagem_url',$imagem_url);
	$pagina = paginaTrocaVarValor($pagina,'#texto',$texto);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	
	return interface_layout(parametros_interface());
}

function add_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	$sep = $_SYSTEM['SEPARADOR'];
	$extensao = "jpg";
	
	banco_conectar();
	
	$file_var = 'imagem';
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$sep."email_mkt".$sep;
	$caminho_internet 		= 	url_path() . "files/email_mkt/";
	
	$campo_nome = "assunto"; $post_nome = $campo_nome; 					if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "texto"; $post_nome = $campo_nome; 					if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "imagem_url"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 					$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
	$id_tabela = banco_last_id();
	
	if(
		$_FILES[$file_var]['size'] != 0
	){
		if
		(
			$_FILES[$file_var]['type'] == mime_types("jpe") ||
			$_FILES[$file_var]['type'] == mime_types("jpeg") ||
			$_FILES[$file_var]['type'] == mime_types("jpg") ||
			$_FILES[$file_var]['type'] == mime_types("pjpeg")
		){
			$cadastrar = true;
		} else
			alerta(1);
	}
	
	if($cadastrar){
		$nome_arquivo = "image" . $id_tabela . "." . $extensao;
		
		if(move_uploaded_file($_FILES[$file_var]['tmp_name'], $caminho_fisico 	. $nome_arquivo)){
			chmod($caminho_fisico.$nome_arquivo,0777);
			$entrada_fisica = $caminho_fisico 	. $nome_arquivo;
			$saida_fisica = $caminho_fisico . "email_markenting_" . $id_tabela . "_peca_" ;
			$saida_url = $caminho_internet . "email_markenting_" . $id_tabela . "_peca_";
			$peca_x = 150;
			$peca_y = 100;
			
			if($_REQUEST[imagem_url])	$imagem_url = $_REQUEST[imagem_url];
			
			$new_w = $_SYSTEM['EMAIL_MARKENTING_IMG_WIDTH'];
			$new_h = 999999;
			
			resize_image($entrada_fisica, $entrada_fisica, $new_w, $new_h);
			
			$tabela = imagem_pecas_tabela($entrada_fisica,$saida_fisica,$saida_url,$peca_x,$peca_y,$imagem_url);
		} else
			echo "<br>não foi";
		
		banco_update
		(
			"imagem_tabela='".$tabela."',".
			"imagem='".$caminho_internet.$nome_arquivo."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id_tabela."'"
		);
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
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
				'assunto',
				'imagem',
				'imagem_url',
				'texto',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		$campos_guardar = Array(
			'assunto' => $tabela[0]['assunto'],
			'texto' => $tabela[0]['texto'],
			'imagem_url' => $tabela[0]['imagem_url'],
			
		);
		
		$pagina = paginaTrocaVarValor($pagina,'#assunto',$tabela[0]['assunto']);
		$pagina = paginaTrocaVarValor($pagina,'#imagem',($tabela[0]['imagem'] ? htmlImage($tabela[0]['imagem'],$width,$height,$border,$id,$extra):''));
		$pagina = paginaTrocaVarValor($pagina,'#imagem_url',($tabela[0]['imagem_url']?$tabela[0]['imagem_url']:''));
		$pagina = paginaTrocaVarValor($pagina,'#texto',$tabela[0]['texto']);
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "editar_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
		$_INTERFACE_OPCAO = 'editar'; 
		$_INTERFACE['informacao_titulo'] = $botao;
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
	
	$sep = $_SYSTEM['SEPARADOR'];
	
	if($_POST["id"]){
		$id = $_POST["id"];
		$campos_antes = campos_antes_recuperar();
		$extensao = "jpg";
		$nome_arquivo = "image" . $id . "." . $extensao;
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "assunto"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "texto"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "imagem_url"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'"; $mudar_tabela = true; $imagem_url = $_POST[$campo_nome];} else {$imagem_url = $campos_antes[$campo_nome];}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		$file_var = 'imagem';
		
		$caminho_fisico 		=	$_SYSTEM['PATH']."files".$sep."email_mkt".$sep;
		$caminho_internet 		= 	url_path() . "files/email_mkt/";
		
		if(
			$_FILES[$file_var]['size'] != 0
		){
			if
			(
				$_FILES[$file_var]['type'] == mime_types("jpe") ||
				$_FILES[$file_var]['type'] == mime_types("jpeg") ||
				$_FILES[$file_var]['type'] == mime_types("pjpeg") ||
				$_FILES[$file_var]['type'] == mime_types("jpg")
			){
				$cadastrar = true;
			} else
				alerta(1);
		}
		
		if($cadastrar){			
			$dir 		=	$_SYSTEM['PATH']."files".$sep."email_mkt".$sep;
			$arq_nome = "email_markenting_" . $id . "_peca_" ;
			$arq_nome2 = "image" . $id . ".jpg" ;
			
			if(file_exists($dir.$arq_nome2))
				unlink($dir.$arq_nome2);
			
			$d = dir($dir);
			
			while(false !== ($entry = $d->read())){
				if(is_file($dir.$entry)){
					$aux = explode($arq_nome,$entry);
					
					if($aux[1]){
						unlink($dir.$entry);
					}
				}
			}
			
			$d->close();
			
			if(move_uploaded_file($_FILES[$file_var]['tmp_name'], $caminho_fisico 	. $nome_arquivo)){
				chmod($caminho_fisico.$nome_arquivo,0777);
				$mudar_tabela = true;
			} else
				echo "<br>não foi";
			
			banco_update
			(
				"imagem='".$caminho_internet.$nome_arquivo."'",
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		if($mudar_tabela){
			$entrada_fisica = $caminho_fisico 	. $nome_arquivo;
			$saida_fisica = $caminho_fisico . "email_markenting_" . $id . "_peca_" ;
			$saida_url = $caminho_internet . "email_markenting_" . $id . "_peca_";
			$peca_x = 150;
			$peca_y = 100;
			
			$new_w = $_SYSTEM['EMAIL_MARKENTING_IMG_WIDTH'];
			$new_h = 999999;
			
			resize_image($entrada_fisica, $entrada_fisica, $new_w, $new_h);
			
			$tabela = imagem_pecas_tabela($entrada_fisica,$saida_fisica,$saida_url,$peca_x,$peca_y,$imagem_url);
			
			banco_update
			(
				"imagem_tabela='".$tabela."'",
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
		
		if($_SYSTEM['EMAIL_MARKENTING_EXCLUIR_IMG']){
			$dir 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."email_mkt".$_SYSTEM['SEPARADOR'];
			$arq_nome = "email_markenting_" . $id . "_peca_" ;
			$arq_nome2 = "image" . $id . ".jpg" ;
			
			unlink($dir.$arq_nome2);
			
			$d = dir($dir);
			
			while(false !== ($entry = $d->read())){
				if(!is_dir($dir.$entry."/")){
					$aux = explode($arq_nome,$entry);
					
					if($aux[1]){
						unlink($dir.$entry);
					}
				}
			}
			
			$d->close();
		}
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function envios(){
	global $_SYSTEM;
	global $_HTML;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_EMAIL;
	
	banco_conectar();
	$variavel_global = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"variavel_global",
		"WHERE variavel='MAILER'"
	);
	$num_emails = banco_total_rows
	(
		"emails",
		"WHERE status='A'"
	);
	$MAILER_NUM_EMAIL = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"variavel_global",
		"WHERE variavel='MAILER_NUM_EMAIL'"
	);
	$email_markenting = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.assunto',
		))
		,
		"email_markenting as t1, variavel_global as t2",
		"WHERE t2.variavel='MAILER_NEWSLETTER'"
		." AND t1.id_email_markenting=t2.valor"
	);
	
	$MAILER_NUM_EMAIL = $MAILER_NUM_EMAIL[0]['valor'];
	$email_markenting = $email_markenting[0]['t1.assunto'];
	
	$_EMAIL['enviando'] = $variavel_global[0]['valor'] == 'A' ? true : false;
	banco_fechar_conexao();
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "envios";
	
	$tag = $_EMAIL['enviando'] ? 'envios' : 'livre';
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- '.$tag.' < -->','<!-- '.$tag.' > -->');
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	if($_EMAIL['enviando']){
		$status = html(Array(
			'tag' => 'img','val' => '',
			'attr' => Array(
				'src' => $_CAMINHO_RELATIVO_RAIZ.$_HTML['ICONS'].'lendo.gif',
				'title' => 'Enviando...',
			),
		));
		$acao = html(Array(
			'tag' => 'a',
			'val' => html(Array(
				'tag' => 'img',
				'val' => '',
				'attr' => Array(
					'src' => $_CAMINHO_RELATIVO_RAIZ.$_HTML['ICONS'].'parar.png',
					'border' => '0',
					'title' => 'Parar o envio de e-mails',
				),
			)),
			'attr' => Array(
				'href' => '?opcao=envios_parar',
			),
		));
		
		$pagina = paginaTrocaVarValor($pagina,'#acao',$acao);
		$pagina = paginaTrocaVarValor($pagina,'#status',$status);
		$pagina = paginaTrocaVarValor($pagina,'#email_markenting',$email_markenting);
		$pagina = paginaTrocaVarValor($pagina,'#enviados',$MAILER_NUM_EMAIL);
		$pagina = paginaTrocaVarValor($pagina,'#restando',$num_emails - $MAILER_NUM_EMAIL);
		$pagina = paginaTrocaVarValor($pagina,'#total',$num_emails);
	}
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	
	return interface_layout(parametros_interface());
}

function envios_parar(){
	banco_conectar();
	banco_update
	(
		"valor='B'",
		"variavel_global",
		"WHERE variavel='MAILER'"
	);
	banco_fechar_conexao();
	
	return lista();
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_LISTA;
	global $_LISTA_2;
	
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
	
	if($_REQUEST['enviar_mail']){
		banco_conectar();
		$MAILER_NEWSLETTER = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"variavel_global",
			"WHERE variavel='MAILER_NEWSLETTER' AND valor='".$_REQUEST['id']."'"
		);
		
		if(!$MAILER_NEWSLETTER){
			if($_REQUEST['id']){
				banco_update
				(
					"valor='".$_REQUEST['id']."'",
					"variavel_global",
					"WHERE variavel='MAILER_NEWSLETTER'"
				);
			} else {
				$nao_ativar = true;
			}
		}
		
		if(!$nao_ativar){
			banco_update
			(
				"valor='A'",
				"variavel_global",
				"WHERE variavel='MAILER'"
			);
		}
		
		banco_fechar_conexao();
	}
	
	return $saida;
}

function start(){
	global $_LOCAL_ID,
	$_PAGINA_OPCAO,
	$_SYSTEM_ID,
	$_INTERFACE_OPCAO,
	$_HTML;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if(!$_REQUEST["ajax"]){
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
			case 'envios':						$saida = (operacao('enviar_emails') ? envios() : lista());break;
			case 'envios_parar':				$saida = (operacao('enviar_emails') ? envios_parar() : lista());break;
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