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
$_LOCAL_ID					=	"uploads";
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

$_DIRETORIO_ROOT			=	$_SYSTEM['PATH'] . 'files' . $_SYSTEM['SEPARADOR'] . 'uploads' . $_SYSTEM['SEPARADOR'];
$_INTERNET_ROOT				=	'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . 'files/uploads/';

$_HTML['titulo'] 			= 	$_HTML['titulo']."Uploads.";

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

function recursive_remove_directory($directory, $empty=FALSE){
	// if the path has a slash at the end we remove it here
	if(substr($directory,-1) == '/'){
		$directory = substr($directory,0,-1);
	}

	// if the path is not valid or is not a directory ...
	if(!file_exists($directory) || !is_dir($directory)){
		// ... we return false and exit the function
		return FALSE;

		// ... if the path is not readable
	} elseif(!is_readable($directory)){
		// ... we return false and exit the function
		return FALSE;

	// ... else if the path is readable
	} else {

		// we open the directory
		$handle = opendir($directory);

		// and scan through the items inside
		while (FALSE !== ($item = readdir($handle))){
			// if the filepointer is not the current directory
			// or the parent directory
			if($item != '.' && $item != '..'){
				// we build the new path to delete
				$path = $directory.'/'.$item;

				// if the new path is a directory
				if(is_dir($path)) 
				{
					// we call this function with the new path
					recursive_remove_directory($path);

				// if the new path is a file
				}else{
					// we remove the file
					if(is_writable($path))
						unlink($path);
				}
			}
		}
		// close the directory
		closedir($handle);

		// if the option to empty is not set to true
		if($empty == FALSE){
			// try to delete the now empty directory
			if(is_writable($directory))
			if(!rmdir($directory))
			{
				// return false if not possible
				return FALSE;
			}
		}
		// return success
		return TRUE;
	}
}

// Funções do Sistema

function pasta_usuario_parametros_interface(){
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
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista Modelo de Pasta de Usuário' : $_INTERFACE['informacao_titulo']);
	
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
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
		'name' => 'Lista', // Nome do menu
	);
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Adicionar Pasta', // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Adicionar', // Nome do menu
		);
	}
	if(operacao('uploads')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=uploads', // link da opção
			'title' => 'Uplodas de Arquivos para a Pasta Atual', // título da opção
			'img_coluna' => 2, // Coluna background image
			'img_linha' => 2, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Uplodas de Arquivos', // Nome do menu
		);
	}
	if(operacao('pasta_usuario')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=pasta_usuario', // link da opção
			'title' => 'Modelos de Pastas de Usuários', // título da opção
			'img_coluna' => 6, // Coluna background image
			'img_linha' => 2, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Modelos de Pastas', // Nome do menu
		);
	}
	
	// ------------------------------ Menu Opções -------------------------
	
	if(operacao('pasta_usuario_excluir')){
		$menu_opcoes[] = Array( // Opção: Excluir
			'url' => '#', // link da opção
			'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 7, // Coluna background image
			'img_linha' => 1, // Linha background image
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','pasta_usuario_excluir')\"", // OPCIONAL - parâmetros extras no link
			'legenda' => 'Ver', // Legenda
		);
	}
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => $width, // OPCIONAL - Tamanho horizontal
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
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => false, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' ", // Tabela extra
		'tabela_order' => $tabela_order, // Ordenação da tabela
		'menu_paginas_id' => "menu_modelos_acesso", // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => $menu_principal,
		'menu_opcoes' => $menu_opcoes,
		'header_campos' => $header_campos,
		'campos' => $campos,
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => 'Ação', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 22), // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'informacao_abaixo' => $informacao_abaixo,
		'informacao_acima' => $informacao_acima,
		'tabela_nao_connect' => false,
		'layout_pagina' => true,
	);
	
	return $parametros;
}

function pasta_usuario_lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(pasta_usuario_parametros_interface());
}

function pasta_usuario_add(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_INTERFACE_OPCAO;
	
	$in_titulo = "Inserir Modelo de Pasta de Usuário";
	$botao = "Gravar";
	$opcao = "pasta_usuario_add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form2 < -->','<!-- form2 > -->');
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#titulo','Nome do Modelo');
	$pagina = paginaTrocaVarValor($pagina,'#nome',$_REQUEST['pasta']);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$_REQUEST['pasta']);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE_OPCAO = 'add2';
	
	return interface_layout(parametros_interface());
}

function pasta_usuario_add_base(){
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$caminho = $_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"] . $_REQUEST['id'];
	
	if($_SERVER['SERVER_NAME'] == "localhost"){
		$replacement = '\\\\\\';
		$pattern = '/\\\\/i';
		$caminho = preg_replace($pattern, $replacement, $caminho);
	}
	$campos = null;
	
	$campo_nome = "status"; $campo_valor = "A"; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "caminho"; $campo_valor = $caminho; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "nome"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	
	banco_insert_name
	(
		$campos,
		"pastas_usuarios"
	);
	
	if($connect_db)banco_fechar_conexao();
	
	return pasta_usuario_lista();
}

function pasta_usuario_excluir(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		banco_conectar();
		banco_delete
		(
			"usuario_pasta",
			"WHERE id_pastas_usuarios='".$id."'"
		);
		banco_delete
		(
			"grupo_pasta",
			"WHERE id_pastas_usuarios='".$id."'"
		);
		banco_delete
		(
			"pastas_usuarios",
			"WHERE id_pastas_usuarios='".$id."'"
		);
		banco_fechar_conexao();
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return pasta_usuario_lista();
}

// ======================================================================================

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
	
	$diretorios = $_SESSION[$_SYSTEM['ID']."DIRETORIOS"];
	$_DIRETORIO_ATUAL = $_DIRETORIO_ROOT;
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
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
		'name' => 'Lista', // Nome do menu
	);
	if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Adicionar Pasta', // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Adicionar', // Nome do menu
		);
	}
	if(operacao('uploads')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=uploads', // link da opção
			'title' => 'Uploads de Arquivos para a Pasta Atual', // título da opção
			'img_coluna' => 2, // Coluna background image
			'img_linha' => 2, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Uploads de Arquivos', // Nome do menu
		);
	}
	if(operacao('pasta_usuario')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=pasta_usuario', // link da opção
			'title' => 'Modelos de Pastas de Usuários', // título da opção
			'img_coluna' => 6, // Coluna background image
			'img_linha' => 2, // Linha background image
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Modelos de Pastas', // Nome do menu
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
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Excluir', // Nome do menu
			);
		}	
	}
	
	if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=lista&diretorio=#id', // link da opção
			'title' => 'Conteúdo d'.$_LISTA['ferramenta_unidade'], // título da opção2
			'diretorio' => true,
			'legenda' => 'Conteúdo', // Legenda
		);
	}
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
	if(operacao('url')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => '#', // link da opção
			'title' => 'Caminho d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 2, // Linha background image
			'arquivo' => true,
			'opcao' => true,
			'classa' => 'caminho',
			'legenda' => 'Caminho', // Legenda
		);
	}
	if(operacao('pasta_usuario')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=pasta_usuario_add&pasta=#id', // link da opção
			'title' => 'Adicionar Pasta do Usuário', // título da opção
			'img_coluna' => 14, // Coluna background image
			'img_linha' => 1, // Linha background image
			'diretorio' => true,
			'legenda' => 'Adicionar Pasta', // Legenda
		);
	}
	if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=editar&id=#id', // link da opção
			'title' => 'Editar ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Editar', // Legenda
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
		'legenda' => true, // Colocar o menu em cima
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
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#titulo','Diretório');
	$pagina = paginaTrocaVarValor($pagina,'#nome',$nome);
	
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
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_USUARIO_ATIVO_2;
	global $_LISTA;
	
	$path = $_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"];
	
	mkdir($path.valid_filename($_REQUEST["nome"]),0777,true);
	chmod($path.valid_filename($_REQUEST["nome"]),0777);
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function editar($param = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		if(is_dir($_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"] . $id)){
			$titulo = "Diretório";
			$diretorio = true;
		} else {
			$titulo = "Arquivo";
		}
		
		$pagina = paginaTrocaVarValor($pagina,'#titulo',$titulo);
		$pagina = paginaTrocaVarValor($pagina,'#nome',$id);
		
		$campos_guardar = Array(
			'nome' => $id,
			'diretorio' => $diretorio,
		);
		
		// ======================================================================================
		
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
		
		$path = $_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"];
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){rename($path.$campos_antes[$campo_nome],$path.valid_filename($_POST[$campo_nome]));}
		
		// ======================================================================================
	}
	
	return lista();
}

function excluir(){ // Sem necessidade de edição
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		$path = $_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"];
		
		if(is_dir($path.$id))
			recursive_remove_directory($path.$id);
		else {
			if(is_writable($path.$id)){
				unlink($path.$id);
			} else {
				alerta("Você não tem permissão para deletar esse arquivo!");
			}	
		}
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function uploads(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_INTERFACE_OPCAO;
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "add_base";
	
	$id = $_SESSION[$_SYSTEM['ID']."id"];
	
	banco_conectar();
	
	if(!$_SESSION[$_SYSTEM['ID']."upload_permissao"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		$session_id = session_id();
		
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
	
	$_SESSION[$_SYSTEM['ID']."uploads"] = true;
	$upload_permissao = $_SESSION[$_SYSTEM['ID']."upload_permissao"];
	
	banco_fechar_conexao();
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- upload < -->','<!-- upload > -->');
	
	$pagina = paginaTrocaVarValor($pagina,"#diretorio",$_SESSION[$_SYSTEM['ID']."DIRETORIO_ATUAL"]);
	$pagina = paginaTrocaVarValor($pagina,"#usuario",$upload_permissao['usuario']);
	$pagina = paginaTrocaVarValor($pagina,"#sessao",$upload_permissao['session_id']);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE_OPCAO = 'uploads';
	
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
	
	return utf8_encode($saida);
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
			case 'menu_modelos_acesso':
			case 'pasta_usuario':				$saida = (operacao('pasta_usuario') ? pasta_usuario_lista() : lista()); break;
			case 'pasta_usuario_add':			$saida = (operacao('pasta_usuario') ? pasta_usuario_add() : lista()); break;
			case 'pasta_usuario_add_base':		$saida = (operacao('pasta_usuario') ? pasta_usuario_add_base() : lista()); break;
			case 'pasta_usuario_excluir':		$saida = (operacao('pasta_usuario_excluir') ? pasta_usuario_excluir() : lista());break;
			case 'add':							$saida = (operacao('adicionar') ? add() : lista()); break;
			case 'add_base':					$saida = (operacao('adicionar') ? add_base() : lista());break;
			case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'editar_base':					$saida = (operacao('editar') ? editar_base() : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			case 'uploads':						$saida = (operacao('uploads') ? uploads() : lista());break;
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