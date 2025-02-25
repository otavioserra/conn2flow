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
$_LOCAL_ID					=	"downloads";
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

$_DIRETORIO_ROOT			=	$_SYSTEM['PATH'] . 'files' . $_SYSTEM['SEPARADOR'] . 'tmp' . $_SYSTEM['SEPARADOR'];
$_INTERNET_ROOT				=	'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . 'files/uploads/';

$_HTML['titulo'] 			= 	$_HTML['titulo']."Downloads.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['tinyMce'].
$_JS['swfUpload'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'pastas_usuarios';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'pastas_usuarios';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Arquivos';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções de assistência

function alerta($nAlerta){ // v 2
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
	global $_DIRETORIO_ROOT;
	global $_INTERNET_ROOT;
	
	if(!$_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"]){
		global $_CONEXAO_BANCO;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.caminho',
			))
			,
			"usuario_pasta as t1, pastas_usuarios as t2",
			"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
			." AND t1.id_pastas_usuarios=t2.id_pastas_usuarios"
		);
		
		if($resultado){
			$_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"] = $resultado[0]['t2.caminho'] . $_SYSTEM['SEPARADOR'];
			if(!is_dir($_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"]))$_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"] = -1;
		} else {
			$usuario_grupo = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_grupo',
				))
				,
				"usuario_grupo",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
			);
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					't2.caminho',
				))
				,
				"grupo_pasta as t1, pastas_usuarios as t2",
				"WHERE t1.id_grupo='".$usuario_grupo[0]['id_grupo']."'"
				." AND t1.id_pastas_usuarios=t2.id_pastas_usuarios"
			);
			
			if($resultado){
				$_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"] = $resultado[0]['t2.caminho'] . $_SYSTEM['SEPARADOR'];
				if(!is_dir($_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"]))$_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"] = -1;
			} else {
				$_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"] = -1;
			}
		}
		
		if($connect_db)banco_fechar_conexao();
	}
	
	$diretorios = $_SESSION[$_SYSTEM['ID']."DIRETORIOS"];
	$_DIRETORIO_ATUAL = ($_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"] != -1 ? $_SESSION[$_SYSTEM['ID']."DIRETORIO_RAIZ_USUARIO"] : $_DIRETORIO_ROOT);
	$_INTERNET_ATUAL = $_INTERNET_ROOT;
	
	if($_SESSION[$_SYSTEM['ID']."uploads"] && $_INTERFACE_OPCAO != 'uploads'){
		$_SESSION[$_SYSTEM['ID']."uploads"] = false;
		$_INTERFACE['menu_paginas_reiniciar'] = true;
	}
	
	if($_REQUEST['raiz']){
		$_SESSION[$_SYSTEM['ID']."DIRETORIOS"] = null;
	}
	
	if($_REQUEST['diretorio']){
		if(!$diretorios)
			$diretorios = Array();
		if(is_dir($_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"].$_REQUEST['diretorio'])){
			array_push($diretorios,$_REQUEST['diretorio']);
			$_SESSION[$_SYSTEM['ID']."DIRETORIOS"] = $diretorios;
		}
	}
	
	if($diretorios && !$_REQUEST['raiz']){
		$informacao_acima .= "<div id=\"menu_diretorios\" class=\"lista_header\">\n";
		$informacao_acima .= htmlA("?opcao=lista&raiz=sim","raiz",$target,$id,$extra);
		
		foreach($diretorios as $dir){
			$filho++;
			$informacao_acima .= ' / ' . htmlA("?opcao=lista&filho=".$filho,$dir,$target,$id,$extra);
			$_DIRETORIO_ATUAL .= $dir . $_SYSTEM['SEPARADOR'];
			$_INTERNET_ATUAL .= $dir . '/';
			
			if($_REQUEST['filho'] && $_REQUEST['filho'] == $filho){
				$diretorios_novo = $diretorios;
				for($i=0;$i<count($diretorios)-$filho;$i++){
					array_pop($diretorios_novo);
				}
				$_SESSION[$_SYSTEM['ID']."DIRETORIOS"] = $diretorios_novo;
				break;
			}
		}
		
		$informacao_acima .= "</div>\n";
	}
	
	$_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"] = $_DIRETORIO_ATUAL;
	
	$informacao_acima .= "<div id=\"caminho_conteiner\">\n";
	$informacao_acima .= "	<table width=\"100%\" border=\"0\" cellspacing=\"5\" class=\"tabela_lista\"><tr>\n";
	$informacao_acima .= "		<td width=\"150\" class=\"lista_header\">Caminho do Arquivo:</td>\n";
	$informacao_acima .= "		<td class=\"lista_cel\" id=\"caminho_texto\">Texto</td>\n";
	$informacao_acima .= "	</tr></table>\n";
	$informacao_acima .= "</div>\n";
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'arquivos' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
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
	
	if(operacao('download')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?download=sim&id=#id', // link da opção
			'title' => 'Salvar ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 12, // Coluna background image
			'img_linha' => 1, // Linha background image
			'arquivo' => true,
			'target' => '_blank',
			'legenda' => 'Salvar', // Legenda
		);
	}

	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'diretorio' => $_DIRETORIO_ATUAL, // Opção para alteração do layout
		'internet' => $_INTERNET_ATUAL, // Opção para alteração do layout
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ( $_INTERFACE_OPCAO == 'add2' ? '': ' ' . $_LISTA['ferramenta']) , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => false, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar legenda
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => $menu_principal,
		'menu_opcoes' => $menu_opcoes,
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => 'Ação', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 22), // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'informacao_abaixo' => $informacao_abaixo,
		'informacao_acima' => $informacao_acima,
		'tabela_nao_connect' => true,
		'raiz' => $_HTML['separador'],
		'permissao_download' => operacao('download'),
		'layout_pagina' => true,
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'arquivos';
	
	return interface_layout(parametros_interface());
}

function arquivos_downloads(){
	global $_SYSTEM;
	
	if(operacao('download')){
		$id = $_REQUEST["id"];
		
		if($id){
			$file = $_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"] . $id;
			
			$arq = explode(".", $id);
			if(count($arq) > 1) $extensao = $arq[count($arq)-1]; else $extensao = "c";
			
			if(is_file($file)){
				header("Content-type: ". mime_types($extensao));
				header("Content-Length: ".filesize($file));
				header("Content-Disposition: attachment; filename=".$id);
				header("Content-Transfer-Encoding: binary");
				readfile($file);
			} else
				echo $file. "Arquivo não encontrado!";
		}
	} else {
		echo "Acesso negado!";
	}
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	return $saida;
}

function start(){	
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_HTML;
	
	if($_REQUEST["download"]){
		arquivos_downloads();
	} else if($_FILES['uploadfile']['size'] > 0)
		echo operacao('uploads') ? uploadfile() : "Acesso negado!";
	else if(!$_REQUEST["ajax"]){
		if($_GET["opcao"])				$opcoes = $_GET["opcao"];
		if($_POST["opcao"])				$opcoes = $_POST["opcao"];
		
		$_PAGINA_OPCAO = $opcoes;
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'lista':						$saida = lista();break;
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