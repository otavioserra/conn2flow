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
$_LOCAL_ID					=	"updates";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Updates.";

$_HTML['js'] = 
$_JS['menu'].
$_JS['tinyMce'].
$_JS['maskedInput'].
$_JS['jstorage'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

/* $_LISTA['tabela']['nome']		=	'variavel_global';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_variavel_global';
$_LISTA['tabela']['status']		=	'status'; */
$_LISTA['ferramenta']			=	'Updates';
$_LISTA['ferramenta_unidade']	=	'a update';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

$_UPDATE_TMP = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'tmp'.$_SYSTEM['SEPARADOR'];
$_UPDATE_FILE = $_UPDATE_TMP.'update.zip';

// Funções do Sistema

function guardar_arquivo($uploaded){
	
}

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_UPDATE_FILE;
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = 'status';
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'email';
	$tabela_campos[] = 'data';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Início', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?opcao=enviar', // link da opção
		'title' => 'Enviar ' . $_LISTA['ferramenta'], // título da opção
		'img_coluna' => 2, // Coluna background image
		'img_linha' => 2, // Linha background image
		'name' => 'Enviar', // Nome do menu
	);
	if(is_file($_UPDATE_FILE)){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=instalar', // link da opção
			'title' => 'Instalar ' . $_LISTA['ferramenta'], // título da opção
			'img_coluna' => 4, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Instalar', // Nome do menu
		);
	}
	
	//if($_INTERFACE_OPCAO == 'editar'){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		/* $menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => '#', // link da opção
			'title' => 'Excluir o(a) ' . $_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'db_remove.png', // caminho da imagem
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		);
		$menu_principal[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
			'title' => 'Ativar/Desativar o(a) '.$_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo_grande_2.png', // caminho da imagem
			'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado_grande_2.png', // caminho da imagem
			'bloquear' => true, // Se eh botão de bloqueio
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=grupos&id=#id', // link da opção
			'title' => 'Grupo d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'grupo_big.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=newsletter_buscar&id=#id', // link da opção
			'title' => 'Enviar Newsletter', // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'email_big.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		); */
		
	//}
	
	$menu_opcoes[] = Array( // Opção: Conteúdo
		'url' => $_URL . '?opcao=newsletter_buscar&id=#id', // link da opção
		'title' => 'Enviar Newsletter', // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'email.png', // caminho da imagem
	);
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => $_URL . '?opcao=grupos&id=#id', // link da opção
		'title' => 'Grupo d'.$_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'grupo.png', // caminho da imagem
	);
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
		'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo.png', // caminho da imagem
		'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado.png', // caminho da imagem
		'bloquear' => true, // Se eh botão de bloqueio
	);
	$menu_opcoes[] = Array( // Opção: Editar
		'url' => $_URL . '?opcao=editar&id=#id', // link da opção
		'title' => 'Editar ' . $_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'editar.png', // caminho da imagem
	);
	$menu_opcoes[] = Array( // Opção: Excluir
		'url' => '#', // link da opção
		'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'excluir.png', // caminho da imagem
		'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Nome', // Valor do campo
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'E-mail', // Valor do campo
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data', // Valor do campo
		'align' => 'center',
		'width' => '120',
	);
	
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data_hora' => true, // OPCIONAL - mostrar dados formatados para data
		'align' => 'center',
	);
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => false, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_nao_connect' => true, // Se deve ou não conectar na tabela de referência
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
		'layout_pagina' => true,
		
	);
	
	return $parametros;
}

function atualizar_robo(){

}

function enviar(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- enviar < -->','<!-- enviar > -->');
	
	$_INTERFACE_OPCAO = 'editar'; 
	$_INTERFACE['informacao_titulo'] = 'Enviar';
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function enviar_salvar(){
	global $_LISTA;
	global $_SYSTEM;
	global $_ALERTA;
	global $_UPDATE_FILE;
	
	if(
		$_FILES['update']['size'] != 0
	){
		if
		(
			$_FILES['update']['type'] == "multipart/x-zip" ||
			$_FILES['update']['type'] == "application/x-compressed" ||
			$_FILES['update']['type'] == "application/x-compress" ||
			$_FILES['update']['type'] == "application/octet-stream" ||
			$_FILES['update']['type'] == "application/x-zip-compressed" ||
			$_FILES['update']['type'] == "application/x-zip" ||
			$_FILES['update']['type'] == "application/zip" 
		){
			$cadastrar = true;
		} else {
			$_ALERTA = "ERRO NO ENVIO: Extensão do arquivo inválido!";
		}
	} else {
		$_ALERTA = "ERRO NO ENVIO: Arquivo vazio!";
	}
	
	if($cadastrar){
		if(is_file($_UPDATE_FILE))$existe_arquivo = true;
		
		if(!move_uploaded_file($_FILES['update']['tmp_name'], $_UPDATE_FILE))
			$_ALERTA = "ERRO NO ENVIO: Problemas com o UPLOAD do arquivo: ".$_FILES['update']['tmp_name'];
		else {
			chmod($_UPDATE_FILE , 0777);
			
			$_ALERTA = "Envio efetuado com sucesso!";
		}
	}
	
	return instalar();
}

function instalar(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_UPDATE_FILE;
	global $_VARS;
	global $_VARIAVEIS_JS;
	
	if($_REQUEST['opcao'] == 'atualizar'){
		$_VARIAVEIS_JS['atualizar'] = 1;
	}
	
	if(!is_file($_UPDATE_FILE)){
		return enviar();
	}
	
	if($_VARS['ftp']['usuario'] && $_VARS['ftp']['senha']){
		$modelo = modelo_abrir('html.html');
		$pagina = modelo_tag_val($modelo,'<!-- instalar < -->','<!-- instalar > -->');
	} else {
		$pagina = '<h2>Sistema aguardando definição de conta FTP<h2><p>Para instalar o update é necessário definir o usuário e senha FTP no <a href="../ftp/">Servidor FTP</a>.</p>';
	}
	
	$_INTERFACE_OPCAO = 'editar'; 
	$_INTERFACE['informacao_titulo'] = 'Instalar';
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function inicio(){
	global $_UPDATE_FILE;
	
	if(is_file($_UPDATE_FILE)){
		return instalar();
	} else {
		return enviar();
	}
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	return $saida;
}

function start(){	
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_HTML;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'enviar':						$saida = enviar();break;
			case 'enviar_salvar':				$saida = enviar_salvar();break;
			case 'atualizar':
			case 'instalar':					$saida = instalar();break;
			default: 							$saida = inicio();
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