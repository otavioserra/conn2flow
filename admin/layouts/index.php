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

$_VERSAO_MODULO				=	'1.1.1';
$_LOCAL_ID					=	"layouts";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Layouts.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['tinyMce'].
$_JS['CodeMirror'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['ferramenta']				=	'Layouts';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

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
	$tabela_campos[] = 'descricao';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'home_mini.png', // caminho da imagem
		'name' => 'Início', // Nome do menu
	);
	
	/* $menu_opcoes[] = Array();
	$header_campos[] = Array();
	$campos[] = Array(); */
	
	// ------------------------------ Parâmetros -------------------------
	
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
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_nao_connect' => true, // Array com os nomes dos campos
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

function editar($param = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_HTML_POS_PROCESSAMENTO;
	global $_TESTAR_FLAG;
	global $_CONEXAO_BANCO;
	global $_VERSAO_TESTES;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$LAYOUTS_VERSAO = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"variavel_global",
		"WHERE variavel='LAYOUTS_VERSAO'"
	);
	if($connect_db)banco_fechar_conexao();
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
	
	if($_REQUEST["arquivo_real"]){
		$html = file_get_contents($_SYSTEM['TEMA_PATH'].'layout.html');
		$css = file_get_contents($_SYSTEM['TEMA_PATH'].'layout.css');
		$pagina = modelo_var_troca($pagina,"#arquivo_carregado#","Arquivo Real");
	} else if($_REQUEST["arquivo_original"]){
		$html = file_get_contents($_SYSTEM['TEMA_PATH'].'layout-original.html');
		$css = file_get_contents($_SYSTEM['TEMA_PATH'].'layout-original.css');
		$pagina = modelo_var_troca($pagina,"#arquivo_carregado#","Arquivo Original");
	} else if($_REQUEST["arquivo_temp"]){
		$html = file_get_contents($_SYSTEM['TEMA_PATH'].'layout-temp.html');
		$css = file_get_contents($_SYSTEM['TEMA_PATH'].'layout-temp.css');
		$pagina = modelo_var_troca($pagina,"#arquivo_carregado#","Arquivo Temporário");
	} else {
		if(!is_file($_SYSTEM['TEMA_PATH'].'layout-temp.html')){
			$html = file_get_contents($_SYSTEM['TEMA_PATH'].'layout.html');
			$css = file_get_contents($_SYSTEM['TEMA_PATH'].'layout.css');
			
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout-temp.html',$html);
			chmod($_SYSTEM['TEMA_PATH'].'layout-temp.html', 0777);
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout-temp.css',$css);
			chmod($_SYSTEM['TEMA_PATH'].'layout-temp.css', 0777);
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout-original.html',$html);
			chmod($_SYSTEM['TEMA_PATH'].'layout-original.html', 0777);
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout-original.css',$css);
			chmod($_SYSTEM['TEMA_PATH'].'layout-original.css', 0777);
			
		} else {
			$filesnames[] = Array(
				'arquivo_carregado' => 'Arquivo Real',
				'filename' => 'layout',
			);
			$filesnames[] = Array(
				'arquivo_carregado' => 'Arquivo Temporário',
				'filename' => 'layout-temp',
			);
			$filesnames[] = Array(
				'arquivo_carregado' => 'Arquivo Original',
				'filename' => 'layout-original',
			);
			
			$time = 0;
			foreach($filesnames as $file){
				if(filemtime($_SYSTEM['TEMA_PATH'].$file['filename'].'.html') > $time){
					$time = filemtime($_SYSTEM['TEMA_PATH'].$file['filename'].'.html');
					$filename = $file['filename'];
					$arquivo_carregado = $file['arquivo_carregado'];
				}
			}
			
			$html = file_get_contents($_SYSTEM['TEMA_PATH'].$filename.'.html');
			$css = file_get_contents($_SYSTEM['TEMA_PATH'].$filename.'.css');
		}
		
		$pagina = modelo_var_troca($pagina,"#arquivo_carregado#",$arquivo_carregado);
	}
	
	$_HTML_POS_PROCESSAMENTO['#html#'] = $html;
	$_HTML_POS_PROCESSAMENTO['#css#'] = $css;
	
	// ======================================================================================
	
	campos_antes_guardar($campos_guardar);
	
	$in_titulo = $param ? "Visualizar" : "Modificar";
	$botao1 = "Gravar 1";
	$botao2 = "Gravar 2";
	$opcao = "editar_base";
	
	$pagina = paginaTrocaVarValor($pagina,"#testar_flag",($_REQUEST["testar"]?'sim':''));
	$pagina = paginaTrocaVarValor($pagina,"#url_teste",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?_layouts_teste=sim&_layouts_versao='.$LAYOUTS_VERSAO[0]['valor']);
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao1",$botao1);
	$pagina = paginaTrocaVarValor($pagina,"#botao2",$botao2);
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
}

function editar_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	
	if(operacao('editar')){
		$campos_antes = campos_antes_recuperar();
		
		if($_REQUEST['publicar']){
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout.html',stripslashes($_REQUEST['html']));
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout.css',stripslashes($_REQUEST['css']));
		} else {
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout-temp.html',stripslashes($_REQUEST['html']));
			file_put_contents($_SYSTEM['TEMA_PATH'].'layout-temp.css',stripslashes($_REQUEST['css']));
		}
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		banco_update
		(
			"valor=valor+1",
			"variavel_global",
			"WHERE variavel='LAYOUTS_VERSAO'"
		);
		
		// ======================================================================================
		
		banco_fechar_conexao();
	}
	
	return editar('ver');
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
			case 'editar':						$saida = (operacao('editar') ? editar() : editar('ver'));break;
			case 'editar_base':					$saida = (operacao('editar') ? editar_base() : editar('ver'));break;
			default: 							$saida = editar('ver');
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