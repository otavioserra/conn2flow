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

$_VERSAO_MODULO				=	'1.3.1';
$_LOCAL_ID					=	"usuarios";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_MAILER			=	true;
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Usuários.";

$_HTML['js'] = 
$_JS['menu'].
$_JS['maskedInput'].
$_JS['jQueryPassStrengthMeter'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'usuario';
$_LISTA['tabela']['campo']		=	'usuario';
$_LISTA['tabela']['id']			=	'id_usuario';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Usuário';
$_LISTA['ferramenta_unidade']	=	'o usuário';

$_LISTA_2['tabela']['nome']			=	'pastas_usuarios';
$_LISTA_2['tabela']['campo']			=	'nome';
$_LISTA_2['tabela']['id']				=	'id_'.'pastas_usuarios';
$_LISTA_2['tabela']['status']			=	'status';

$_HTML['separador']		=	$_CAMINHO_RELATIVO_RAIZ;

// Funções de assistência

function alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	
	switch ($nAlerta){
		case 1:		$mensSaida	=	"E-mail enviado!\\n\\nResposta do sistema de e-mail:\\n\\n".$_ALERT_DADOS;break;
		//case 1:		$mensSaida	=	"";break;
		default:	$mensSaida	=	$nAlerta;
	}

	$_ALERTA = $mensSaida;
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
	
	if($_REQUEST['id']) $_SESSION[$_SYSTEM['ID']."usuario_atual_id"] = $_REQUEST['id'];
	
	$usuario_atual_id = $_SESSION[$_SYSTEM['ID']."usuario_atual_id"];
	
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$usuario_pasta = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pastas_usuarios',
		))
		,
		"usuario_pasta",
		"WHERE id_usuario='".$usuario_atual_id."'"
	);
	$usuario = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
		))
		,
		"usuario",
		"WHERE id_usuario='".$usuario_atual_id."'"
	);
	
	if($usuario_pasta)
	$pastas_usuarios = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
		))
		,
		"pastas_usuarios",
		"WHERE id_pastas_usuarios='".$usuario_pasta[0]['id_pastas_usuarios']."'"
	);
	
	if($usuario_pasta) $_SESSION[$_SYSTEM['ID']."usuario_pasta"] = true; else $_SESSION[$_SYSTEM['ID']."usuario_pasta"] = false;
	
	if($connect_db)banco_fechar_conexao();
	
	$informacao_acima = "<h3>Usuário: ".$usuario[0]['nome']."</h3>";
	$informacao_acima .= "<p>Pasta Vinculada: <b>".($pastas_usuarios?$pastas_usuarios[0]['nome']:"Nenhuma")."</b></p>";
	
	$_LISTA = $_LISTA_2;
	
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
		'name' => 'Lista', // Nome do menu
	);
	if($_INTERFACE_OPCAO == 'editar'){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		if(operacao('excluir')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => '#', // link da opção
				'title' => 'Excluir o(a) ' . $_LISTA['ferramenta'], // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'db_remove.png', // caminho da imagem
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Excluir', // Nome do menu
			);
		}
		if(operacao('bloquear')){
			$menu_principal[] = Array( // Opção: Bloquear
				'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
				'title' => 'Ativar/Desativar o(a) '.$_LISTA['ferramenta'], // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo_grande_2.png', // caminho da imagem
				'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado_grande_2.png', // caminho da imagem
				'bloquear' => true, // Se eh botão de bloqueio
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Ativar/Desativar', // Nome do menu
			);
		}
		if(operacao('grupos')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=grupos&id=#id', // link da opção
				'title' => 'Grupo d'.$_LISTA['ferramenta_unidade'], // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'grupo_big.png', // caminho da imagem
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Grupo', // Nome do menu
			);
		}
		if(operacao('pasta_usuario')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=pasta_usuario&id=#id', // link da opção
				'title' => 'Pasta de Usuário', // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'pasta_usuario.png', // caminho da imagem
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Pasta de Usuário', // Nome do menu
			);
		}
		
	}
	
	// ------------------------------ Menu Opções -------------------------
	
	if(operacao('pasta_usuario')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=pasta_usuario_add&pasta=#id', // link da opção
			'title' => 'Adicionar Pasta para o Usuário', // título da opção
			'img_coluna' => 14, // Coluna background image
			'img_linha' => 1, // Linha background image
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
		'menu_paginas_id' => "menu_pasta_usuario", // Identificador do menu
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
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	$pasta = $_REQUEST['pasta'];
	
	$usuario_atual_id = $_SESSION[$_SYSTEM['ID']."usuario_atual_id"];
	$usuario_pasta = $_SESSION[$_SYSTEM['ID']."usuario_pasta"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	if($usuario_pasta){
		$campo_tabela = "tabela";
		$campo_nome = "id_pastas_usuarios"; $campo_valor = $pasta; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				"usuario_pasta",
				"WHERE id_usuario='".$usuario_atual_id."'"
			);
		}
		$editar = false;$editar_sql = false;
	} else {
		$campos = null;
		
		$campo_nome = "id_usuario"; $campo_valor = $usuario_atual_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_pastas_usuarios"; $campo_valor = $pasta; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"usuario_pasta"
		);
	}
	if($connect_db)banco_fechar_conexao();
	
	return pasta_usuario_lista();
}

// ======================================================================================

function perfil_select(){
	global $_SYSTEM;
	
	$nome = 'perfil';
	$id = $nome . '_id';
	
	$tabela = banco_select
	(
		"nome,id_usuario_perfil",
		"usuario_perfil",
		"WHERE id_usuario_perfil='1'"
		." OR status='A'"
		." ORDER BY nome"
	);
	
	$max = count($tabela);
	
	$options[] = "Perfil...";
	$optionsValue[] = "-1";
	
	for($i=0;$i<$max;$i++){
		$options[] = $tabela[$i][0];
		$optionsValue[] =  $tabela[$i][1];
		
		$cont++;
		
		if($_SESSION[$_SYSTEM['ID'].$id] == $tabela[$i][1]){
			$optionSelected = $cont;
		}
	}
	
	if(!$optionSelected && $max == 1)
		$optionSelected = 1;
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'');
	
	return $select;
}

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'id_usuario_perfil';
	$tabela_campos[] = 'nome';
	$tabela_campos[] = 'sobrenome';
	$tabela_campos[] = 'email';
	$tabela_campos[] = 'cidade';
	$tabela_campos[] = 'data_login';
	
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
	if(operacao('moderar_dados_pessoais')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=moderar_dados_pessoais', // link da opção
			'title' => 'Moderar Dados Pessoais dos ' . $_LISTA['ferramenta'].'s', // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 2, // Linha background image
			'name' => 'Moderar', // Nome do menu
		);
	}
	if($_INTERFACE_OPCAO == 'editar'){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		if(operacao('excluir')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => '#', // link da opção
				'title' => 'Excluir o(a) ' . $_LISTA['ferramenta'], // título da opção
				'img_coluna' => 8, // Coluna background image
				'img_linha' => 1, // Linha background image
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
				'name' => 'Excluir', // Nome do menu
			);
		}
		if(operacao('bloquear')){
			$menu_principal[] = Array( // Opção: Bloquear
				'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
				'title' => 'Ativar/Desativar o(a) '.$_LISTA['ferramenta'], // título da opção
				'img_coluna' => 6, // Coluna background image
				'img_linha' => 1, // Linha background image
				'img_coluna2' => 7, // Coluna background image
				'img_linha2' => 1, // Linha background image
				'bloquear' => true, // Se eh botão de bloqueio
				'name' => 'Ativar/Desativar', // Nome do menu
			);
		}
		if(operacao('grupos')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=grupos&id=#id', // link da opção
				'title' => 'Grupo d'.$_LISTA['ferramenta_unidade'], // título da opção
				'img_coluna' => 1, // Coluna background image
				'img_linha' => 2, // Linha background image
				'name' => 'Grupo', // Nome do menu
			);
		}
		if(operacao('pasta_usuario')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=pasta_usuario&id=#id', // link da opção
				'title' => 'Pasta de Usuário', // título da opção
				'img_coluna' => 6, // Coluna background image
				'img_linha' => 2, // Linha background image
				'name' => 'Pasta de Usuário', // Nome do menu
			);
		}
		
	}
	
	if(operacao('pasta_usuario')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=pasta_usuario&id=#id', // link da opção
			'title' => 'Pasta do Usuário', // título da opção
			'img_coluna' => 14, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Pasta do Usuário', // Legenda
		);
	}
	if(operacao('grupos')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=grupos&id=#id', // link da opção
			'title' => 'Grupo d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 13, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Grupo', // Legenda
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
		$menu_opcoes[] = Array( // Opção: Editar
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
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'id_usuario', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Usuário', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Perfil', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'id' => 1, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
		'tabela' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'mudar_valor' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'campo' => 'nome', // OPCIONAL - Nome do campo da tabela
		'campo_id' => 'id_usuario_perfil', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'nome', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Sobrenome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'sobrenome', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'E-mail', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'email', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Cidade', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'cidade', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Login', // Valor do campo
		'align' => 'center',
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data_hora' => true, // OPCIONAL - mostrar dados formatados para data
		'campo' => 'data_cadastro', // OPCIONAL - Nome do campo da tabela
		'align' => 'center',
		'width' => '120',
	);
	
	$outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => 'usuario_perfil', // Nome da tabela
		'campos' => Array(
			'nome',
		), // Array com os nomes dos campos
		'extra' => $valor, // Tabela extra
	);
	
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
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_usuario_perfil!='1' AND status!='D' AND status!='V'", // Tabela extra
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
	
	$in_titulo = "Inserir";
	$botao = "Gravar";
	$opcao = "add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
	
	$pagina = paginaTrocaVarValor($pagina,'#historico#',$hitorico);
	
	$num_cols = 3;
	
	banco_conectar();
	
	$grupos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_grupo',
			'nome',
		))
		,
		"grupo",
		"WHERE status='A'".
		" ORDER BY nome ASC"
	);
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$cel1 = modelo_tag_val($pagina,'<!-- cel1 < -->','<!-- cel1 > -->');
	$pagina = modelo_tag_in($pagina,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');
	$cel2 = modelo_tag_val($pagina,'<!-- cel2 < -->','<!-- cel2 > -->');
	$pagina = modelo_tag_in($pagina,'<!-- cel2 < -->','<!-- cel2 > -->','<!-- cel2 -->');
	
	$num_linhas = floor(count($grupos)/$num_cols)+1;
	$count = 0;
	
	$checked = " checked=\"checked\"";
	
	for($i=0;$i<count($grupos);$i++){
		if(!$linha[$count]){
			$linha[$count] = $cel2;
		}
		
		$checar = "";
		
		for($j=0;$j<count($usuario_grupo);$j++){
			if($grupos[$i]['id_grupo'] == $usuario_grupo[$j]['id_grupo']){
				$checar = $checked;
				$id_grupo[$i] = $usuario_grupo[$j]['id_grupo'];
				break;
			}
		}
		
		$cel_aux = $cel1;
		
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_id",$grupos[$i]['id_grupo']);
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_name",$grupos[$i]['nome']);
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_num",$i);
		$cel_aux = modelo_var_troca($cel_aux,"#checked",$checar);
		
		$linha[$count] = modelo_var_in($linha[$count],'<!-- cel1 -->',$cel_aux);
		
		if($count < $num_linhas-1)
			$count++;
		else
			$count = 0;
	}
	
	if(count($grupos) == 0){
		$pagina = modelo_tag_in($pagina,'<!-- grupo_cel < -->','<!-- grupo_cel > -->','');
	}
	
	for($i=0;$i<count($linha);$i++){
		$pagina = modelo_var_in($pagina,'<!-- cel2 -->',$linha[$i]);
	}
	
	$campos_guardar = Array(
		'id_grupo' => $id_grupo,
	);
	
	$pagina = modelo_var_troca($pagina,"#grupos_num",count($grupos));
	
	campos_antes_guardar($campos_guardar);
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#usuario',$usuario);
	$pagina = paginaTrocaVarValor($pagina,'#edit_usuario',$edit_usuario);
	$pagina = paginaTrocaVarValor($pagina,'#senha',$senha);
	$pagina = paginaTrocaVarValor($pagina,'#senha2',$senha2);
	$pagina = paginaTrocaVarValor($pagina,'#perfil',perfil_select());
	$pagina = paginaTrocaVarValor($pagina,'#email',$email);
	$pagina = paginaTrocaVarValor($pagina,'#edit_email',$edit_email);
	$pagina = paginaTrocaVarValor($pagina,'#nome',$nome);
	$pagina = paginaTrocaVarValor($pagina,'#sobrenome',$sobrenome);
	$pagina = paginaTrocaVarValor($pagina,'#cep',$cep);
	$pagina = paginaTrocaVarValor($pagina,'#endereco',$endereco);
	$pagina = paginaTrocaVarValor($pagina,'#numero',$numero);
	$pagina = paginaTrocaVarValor($pagina,'#complemento',$complemento);
	$pagina = paginaTrocaVarValor($pagina,'#bairro',$bairro);
	$pagina = paginaTrocaVarValor($pagina,'#cidade',$cidade);
	$pagina = paginaTrocaVarValor($pagina,'#uf',$uf);
	$pagina = paginaTrocaVarValor($pagina,'#tel',$tel);
	$pagina = paginaTrocaVarValor($pagina,'#cel',$cel);
	
	$pagina = paginaTrocaVarValor($pagina,'#grupos',$grupos);
	
	banco_fechar_conexao();	
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	$pagina = paginaTrocaVarValor($pagina,"#cep_search",($_SYSTEM['CEP_SEARCH']?'1':''));
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	
	return interface_layout(parametros_interface());
}

function add_base(){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE;
	
	banco_conectar();
	
	$campo_nome = "id_usuario_perfil"; $post_nome = "perfil"; 		if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$_SYSTEM['USUARIO_STATUS']);
	$campo_nome = "usuario"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "senha"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,crypt($_POST[$post_nome]));
	$campo_nome = "email"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "sobrenome"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "endereco"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "numero"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "complemento"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "bairro"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "cidade"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "uf"; $post_nome = $campo_nome; 					if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "cep"; $post_nome = $campo_nome; 					if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "telefone"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "celular"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$usuario_id = banco_last_id();
	$campos = false;
	
	$id = $usuario_id;
	
	$campos_antes = campos_antes_recuperar();
	$id_grupo = $campos_antes['id_grupo'];
	
	for($i=0;$i<$_REQUEST[grupos_num];$i++){
		if($_REQUEST["grupo".$i]){
			if(!$id_grupo[$i]){
				banco_insert_tudo
				(
					"'".$id."',".
					"'".$_REQUEST["perfil"]."',".
					"'".$_REQUEST["grupo".$i]."'",
					"usuario_grupo"
				);
			}
		} else {
			if($id_grupo[$i]){
				banco_delete
				(
					"usuario_grupo",
					"WHERE id_usuario='".$id."'".
					" AND id_grupo='".$id_grupo[$i]."'"
				);
			}
		}
	}
	
	banco_fechar_conexao();
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;

	return lista();
}

function historico($params){
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
		'extra_select' => false,
		'option_value_igual_nome' => false,
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
	$opcao_inicial_id = $params['opcao_inicial_id'];
	$link_extra = $params['link_extra'];
	$onchange = $params['onchange'];
	$option_value_igual_nome = $params['option_value_igual_nome'];
	$extra_select = $params['extra_select'];
	$url = $params['url'];
	$id = $nome . '_id';
	
	if($_REQUEST[$id])	$_SESSION[$_SYSTEM['ID'].$id] = $_REQUEST[$id];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultados = banco_select_name
	(
		banco_campos_virgulas(Array(
			$tabela_campos['id'],
			$tabela_campos['nome'],
			$tabela_campos['moderacao'],
		))
		,
		$tabela_nome,
		$tabela_extra
		.($tabela_order ? " ORDER BY " . $tabela_order : "")
	);
	if($connect_db)banco_fechar_conexao();
	
	if($opcao_inicial){
		$options[] = $opcao_inicial;
		$optionsValue[] = ($opcao_inicial_id?$opcao_inicial_id:"-1");
	}
	
	$cont = 0;
	if($resultados){
		foreach($resultados as $resultado){
			if($resultado[$tabela_campos['moderacao']])$options[] = 'Aguardando Moderação'; else $options[] = data_hora_from_datetime_to_text($resultado[$tabela_campos['nome']]);
			$optionsValue[] = ($option_value_igual_nome ? $resultado[$tabela_campos['nome']] : $resultado[$tabela_campos['id']]);
			
			$cont++;
			
			if($_SESSION[$_SYSTEM['ID'].$id] == $resultado[$tabela_campos['id']]){
				$optionSelected = $cont;
				if(!$opcao_inicial)$optionSelected--;
			}
		}
		
		if($link_extra)$link_extra .= '&';
		$url .= '?';
		$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,($onchange ? 'onchange=window.open("'.$url.$link_extra.$id.'="+this.value,"_self")' : '') . ($extra_select ? ($onchange ? ' ' : '') . $extra_select : ''));
		
		return $select;
	} else {
		return false;
	}
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
		
		$num_cols = 3;
		
		banco_conectar();
		$usuario_grupo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_grupo',
			))
			,
			"usuario_grupo",
			"WHERE id_usuario='".$id."'"
		);
		
		$grupos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_grupo',
				'nome',
			))
			,
			"grupo",
			"WHERE status='A'".
			" ORDER BY nome ASC"
		);
		
		// ================================= Local de Edição ===============================
		// Altere os campos da interface com os valores iniciais
		
		$cel1 = modelo_tag_val($pagina,'<!-- cel1 < -->','<!-- cel1 > -->');
		$pagina = modelo_tag_in($pagina,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');
		$cel2 = modelo_tag_val($pagina,'<!-- cel2 < -->','<!-- cel2 > -->');
		$pagina = modelo_tag_in($pagina,'<!-- cel2 < -->','<!-- cel2 > -->','<!-- cel2 -->');
		
		$hitorico = historico(Array(
			'nome' => 'historico',
			'tabela_campos' => Array(
				'nome' => 'data_cadastro',
				'id' => 'id_usuario',
				'moderacao' => 'moderacao',
			),
			'tabela_nome' => 'usuario',
			'tabela_nome' => 'usuario',
			'tabela_extra' => "WHERE id_usuario_original='".$id."'",
			'tabela_order' => "data_cadastro DESC",
			'onchange' => true,
			'opcao_inicial' => 'Versão Atual',
			'opcao_inicial_id' => $id,
			'link_extra' => 'opcao=editar&id='.$id,
		));
		
		if($hitorico){
			$hitorico = html(Array(
				'tag' => 'h2',
				'val' => 'Histórico de Modificações: '.$hitorico,
				'attr' => false
			));
		}
		
		$pagina = paginaTrocaVarValor($pagina,'#historico#',$hitorico);
		
		$num_linhas = floor(count($grupos)/$num_cols)+1;
		$count = 0;
		
		$checked = " checked=\"checked\"";
		
		for($i=0;$i<count($grupos);$i++){
			if(!$linha[$count]){
				$linha[$count] = $cel2;
			}
			
			$checar = "";
			
			for($j=0;$j<count($usuario_grupo);$j++){
				if($grupos[$i]['id_grupo'] == $usuario_grupo[$j]['id_grupo']){
					$checar = $checked;
					$id_grupo[$i] = $usuario_grupo[$j]['id_grupo'];
					break;
				}
			}
			
			$cel_aux = $cel1;
			
			$cel_aux = modelo_var_troca($cel_aux,"#grupo_id",$grupos[$i]['id_grupo']);
			$cel_aux = modelo_var_troca($cel_aux,"#grupo_name",$grupos[$i]['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#grupo_num",$i);
			$cel_aux = modelo_var_troca($cel_aux,"#checked",$checar);
			
			$linha[$count] = modelo_var_in($linha[$count],'<!-- cel1 -->',$cel_aux);
			
			if($count < $num_linhas-1)
				$count++;
			else
				$count = 0;
		}
		
		if(count($grupos) == 0){
			$pagina = modelo_tag_in($pagina,'<!-- grupo_cel < -->','<!-- grupo_cel > -->','');
		}
		
		for($i=0;$i<count($linha);$i++){
			$pagina = modelo_var_in($pagina,'<!-- cel2 -->',$linha[$i]);
		}
		
		$pagina = modelo_var_troca($pagina,"#grupos_num",count($grupos));
	
		$usuario = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario_perfil',
				'usuario',
				'senha',
				'nome',
				'email',
				'sobrenome',
				'cep',
				'endereco',
				'numero',
				'complemento',
				'bairro',
				'cidade',
				'uf',
				'celular',
				'telefone',
			))
			,
			$_LISTA['tabela']['nome'],
			($_REQUEST['historico_id']?"WHERE ".$_LISTA['tabela']['id']."='".$_REQUEST['historico_id']."'":"WHERE ".$_LISTA['tabela']['id']."='".$id."'")
		);
		
		$campos_guardar = Array(
			'usuario' => $usuario[0]['usuario'],
			'senha' => $usuario[0]['senha'],
			'id_usuario_perfil' => $usuario[0]['id_usuario_perfil'],
			'email' => $usuario[0]['email'],
			'nome' => $usuario[0]['nome'],
			'sobrenome' => $usuario[0]['sobrenome'],
			'cep' => $usuario[0]['cep'],
			'endereco' => $usuario[0]['endereco'],
			'numero' => $usuario[0]['numero'],
			'complemento' => $usuario[0]['complemento'],
			'bairro' => $usuario[0]['bairro'],
			'cidade' => $usuario[0]['cidade'],
			'uf' => $usuario[0]['uf'],
			'celular' => $usuario[0]['celular'],
			'telefone' => $usuario[0]['telefone'],
			'id_grupo' => $id_grupo,
		);
		
		$_SESSION[$_SYSTEM['ID'].'perfil_id'] = $usuario[0]['id_usuario_perfil'];
		
		$pagina = paginaTrocaVarValor($pagina,'#usuario',$usuario[0]['usuario']);
		$pagina = paginaTrocaVarValor($pagina,'#edit_usuario',$usuario[0]['usuario']);
		$pagina = paginaTrocaVarValor($pagina,'#senha','');
		$pagina = paginaTrocaVarValor($pagina,'#senha2','');
		$pagina = paginaTrocaVarValor($pagina,'#perfil',perfil_select());
		$pagina = paginaTrocaVarValor($pagina,'#email',$usuario[0]['email']);
		$pagina = paginaTrocaVarValor($pagina,'#edit_email',$usuario[0]['email']);
		$pagina = paginaTrocaVarValor($pagina,'#nome',$usuario[0]['nome']);
		$pagina = paginaTrocaVarValor($pagina,'#sobrenome',$usuario[0]['sobrenome']);
		$pagina = paginaTrocaVarValor($pagina,'#cep',$usuario[0]['cep']);
		$pagina = paginaTrocaVarValor($pagina,'#endereco',$usuario[0]['endereco']);
		$pagina = paginaTrocaVarValor($pagina,'#numero',$usuario[0]['numero']);
		$pagina = paginaTrocaVarValor($pagina,'#complemento',$usuario[0]['complemento']);
		$pagina = paginaTrocaVarValor($pagina,'#bairro',$usuario[0]['bairro']);
		$pagina = paginaTrocaVarValor($pagina,'#cidade',$usuario[0]['cidade']);
		$pagina = paginaTrocaVarValor($pagina,'#uf',$usuario[0]['uf']);
		$pagina = paginaTrocaVarValor($pagina,'#tel',$usuario[0]['telefone']);
		$pagina = paginaTrocaVarValor($pagina,'#cel',$usuario[0]['celular']);
		
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
		$pagina = paginaTrocaVarValor($pagina,"#cep_search",($_SYSTEM['CEP_SEARCH']?'1':''));
	
		if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
		$_INTERFACE_OPCAO = 'editar'; 
		$_INTERFACE['informacao_titulo'] = $in_titulo;
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
		
		$campo_tabela = "usuario";
		$campo_nome = "usuario"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "senha"; if($_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . crypt($_POST[$campo_nome]) . "'";}
		$campo_nome = "id_usuario_perfil"; if($campos_antes[$campo_nome] != $_POST['perfil']){$editar[$campo_tabela][] = $campo_nome."='" . $_POST['perfil'] . "'"; $mudou_perfil = true;}
		$campo_nome = "email"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "sobrenome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "cep"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "endereco"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "numero"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "complemento"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "bairro"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "cidade"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "uf"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "telefone"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "celular"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
		if($mudou_perfil){
			banco_query("SET foreign_key_checks = 0");
			banco_update
			(
				"id_usuario_perfil='".$_POST['perfil']."'",
				"usuario_grupo",
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
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
		
		if($mudou_perfil){
			banco_query("SET foreign_key_checks = 1");
		}
		
		$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'D');
		$campo_nome = "id_usuario_original"; $post_nome = $campo_nome; 	$campos[] = Array($campo_nome,$id);
		$campo_nome = "id_usuario_perfil"; $post_nome = $campo_nome;	$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "usuario"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "senha"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "email"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "nome"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "sobrenome"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "endereco"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "numero"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "complemento"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "bairro"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "cidade"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "uf"; $post_nome = $campo_nome; 					$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "cep"; $post_nome = $campo_nome; 					$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "telefone"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "celular"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
		$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
		
		banco_insert_name($campos,$_LISTA['tabela']['nome']);
		
		// ======================================================================================
		
		$id_grupo = $campos_antes['id_grupo'];
		
		for($i=0;$i<$_REQUEST[grupos_num];$i++){
			if($_REQUEST["grupo".$i]){
				if(!$id_grupo[$i]){
					banco_insert_tudo
					(
						"'".$id."',".
						"'".$_REQUEST["perfil"]."',".
						"'".$_REQUEST["grupo".$i]."'",
						"usuario_grupo"
					);
				}
			} else {
				if($id_grupo[$i]){
					banco_delete
					(
						"usuario_grupo",
						"WHERE id_usuario='".$id."'".
						" AND id_grupo='".$id_grupo[$i]."'"
					);
				}
			}
		}
		
		banco_fechar_conexao();
	}
	
	return lista();
}

function excluir(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		banco_conectar();
		$mensagens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_mensagens',
			))
			,
			"mensagens",
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		if($mensagens)
		foreach($mensagens as $mensagem){
			banco_delete
			(
				"mensagens_grupo",
				"WHERE id_mensagens='".$mensagem['id_mensagens']."'"
			);
			banco_delete
			(
				"mensagens_usuario",
				"WHERE id_mensagens='".$mensagem['id_mensagens']."'"
			);
			banco_delete
			(
				"mensagens",
				"WHERE id_mensagens='".$mensagem['id_mensagens']."'"
			);
		}
		
		banco_delete
		(
			"mensagens_usuario",
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_delete
		(
			"usuario_grupo",
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_delete
		(
			"usuario_pasta",
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_delete
		(
			"usuario_pedidos",
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_delete
		(
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_fechar_conexao();
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function bloqueio(){
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

function grupos(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	
	$id = $_REQUEST[id];
	
	if($id){
		$in_titulo = "Grupo";
		$botao = "Gravar";
		$opcao = "grupos_base";
		$num_cols = 3;
		
		banco_conectar();
		$usuario = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario_perfil',
			))
			,
			"usuario",
			"WHERE id_usuario='".$id."'"
		);
		$usuario_grupo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_grupo',
			))
			,
			"usuario_grupo",
			"WHERE id_usuario='".$id."'"
		);
		$grupos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_grupo',
				'nome',
			))
			,
			"grupo",
			"WHERE status='A'".
			" ORDER BY nome ASC"
		);
		banco_fechar_conexao();
		
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- grupos < -->','<!-- grupos > -->');
		
		// ================================= Local de Edição ===============================
		// Altere os campos da interface com os valores iniciais
		
		$cel1 = modelo_tag_val($pagina,'<!-- cel1 < -->','<!-- cel1 > -->');
		$pagina = modelo_tag_in($pagina,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');
		$cel2 = modelo_tag_val($pagina,'<!-- cel2 < -->','<!-- cel2 > -->');
		$pagina = modelo_tag_in($pagina,'<!-- cel2 < -->','<!-- cel2 > -->','<!-- cel2 -->');
		
		$num_linhas = floor(count($grupos)/$num_cols)+1;
		$count = 0;
		
		$checked = " checked=\"checked\"";
		
		for($i=0;$i<count($grupos);$i++){
			if(!$linha[$count]){
				$linha[$count] = $cel2;
			}
			
			$checar = "";
			
			for($j=0;$j<count($usuario_grupo);$j++){
				if($grupos[$i]['id_grupo'] == $usuario_grupo[$j]['id_grupo']){
					$checar = $checked;
					$id_grupo[$i] = $usuario_grupo[$j]['id_grupo'];
					break;
				}
			}
			
			$cel_aux = $cel1;
			
			$cel_aux = modelo_var_troca($cel_aux,"#grupo_id",$grupos[$i]['id_grupo']);
			$cel_aux = modelo_var_troca($cel_aux,"#grupo_name",$grupos[$i]['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#grupo_num",$i);
			$cel_aux = modelo_var_troca($cel_aux,"#checked",$checar);
			
			$linha[$count] = modelo_var_in($linha[$count],'<!-- cel1 -->',$cel_aux);
			
			if($count < $num_linhas-1)
				$count++;
			else
				$count = 0;
		}
		
		for($i=0;$i<count($linha);$i++){
			$pagina = modelo_var_in($pagina,'<!-- cel2 -->',$linha[$i]);
		}
		
		$campos_guardar = Array(
			'id_grupo' => $id_grupo,
			'id_usuario_perfil' => $usuario[0]['id_usuario_perfil'],
		);
		
		$pagina = modelo_var_troca($pagina,"#num",count($grupos));
		$pagina = modelo_var_troca($pagina,"#usuario_perfil_id",$usuario[0]['id_usuario_perfil']);
		
		// ======================================================================================
		
		campos_antes_guardar($campos_guardar);
		
		$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		$_INTERFACE['informacao_titulo'] = $in_titulo;
		$_INTERFACE['inclusao'] = $pagina;
		
		return interface_layout(parametros_interface());
	} else {
		return lista();	
	}
}

function grupos_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	
	if($_POST["id"]){
		$id = $_POST["id"];
		$id_usuario_perfil = $_POST["id_usuario_perfil"];
		
		$campos_antes = campos_antes_recuperar();
		$id_grupo = $campos_antes['id_grupo'];
		
		banco_conectar();
		
		for($i=0;$i<$_REQUEST[num];$i++){
			if($_REQUEST["grupo".$i]){
				if(!$id_grupo[$i]){
					banco_insert_tudo
					(
						"'".$id."',".
						"'".$id_usuario_perfil."',".
						"'".$_REQUEST["grupo".$i]."'",
						"usuario_grupo"
					);
				}
			} else {
				if($id_grupo[$i]){
					banco_delete
					(
						"usuario_grupo",
						"WHERE id_usuario='".$id."'".
						" AND id_grupo='".$id_grupo[$i]."'"
					);
				}
			}
		}
		
		banco_fechar_conexao();
	}
	
	return lista();
}

function moderar_dados_pessoais(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$usuarios = banco_select_name
	(
		"*"
		,
		"usuario",
		"WHERE moderacao IS NOT NULL"
		." ORDER BY data_cadastro DESC"
	);
	
	if($usuarios){
		$layout = '
	<table width="100%" border="0" cellspacing="5" class="tabela_lista">
		<tr>
			<td align="center" class="lista_header" width="110">Campo</td>
			<td align="center" class="lista_header">Modificação</td>
		</tr>
		<!-- cel -->
	</table>
';
		$cel = '
		<tr>
			<td class="lista_cel">#campo#</td>
			<td class="lista_cel">#modificacao#</td>
		</tr>
';
		foreach($usuarios as $usuario){
			$usuario_atual = banco_select_name
			(
				"*"
				,
				"usuario",
				"WHERE id_usuario='".$usuario['id_usuario_original']."'"
			);
			
			$usuario_atual = $usuario_atual[0];
			
			$lay_aux = $layout;
			
			foreach($usuario as $campo => $dado){
				$flag = false;
				switch($campo){
					case 'id_usuario':
					case 'id_usuario_perfil':
					case 'status':
					case 'data_cadastro':
					case 'data_login':
					case 'id_usuario_original':
					case 'moderacao':
					case 'senha':
						$flag = true;
				}
				
				if(!$flag)
				if($usuario_atual[$campo] != $dado){
					$cel_aux = $cel;
					
					$cel_aux = modelo_var_troca($cel_aux,"#campo#",$campo);
					$cel_aux = modelo_var_troca($cel_aux,"#modificacao#",$usuario_atual[$campo].' -> '.$dado);
					
					$lay_aux = modelo_var_in($lay_aux,'<!-- cel -->',$cel_aux);
				}
			}
			
			$pagina .= $lay_aux;
		}

		$pagina = interface_layout(Array(
			'opcao' => 'generico',
			'inclusao' => $pagina,
			'tabela_nome' => 'usuario',
			'tabela_extra' => "WHERE moderacao IS NOT NULL",
			'tabela_order' => "data_cadastro DESC",
			'tabela_limit' => $limite,
			'tabela_id' => 'id_usuario',
			'tabela_id_posicao' => 0,
			'tabela_campos' => Array(
				'id_usuario',
			),
			'menu_paginas_id' => 'menu_moderar_dados_pessoais',
			'menu_pagina_embaixo' => true,
			'menu_paginas_inicial' => true,
			'nao_mostrar_menu' => $nao_mostrar_menu,
			'menu_dont_show' => true, // Título da Informação
			'not_scroll' => true, // Se quer que gere novos dados com Scroll do mouse
			'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		));
	} else {
		$pagina = '<p>Sem dados cadastrados.</p>';
	}
	
	$_INTERFACE_OPCAO = 'moderar'; 
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
	global $_LISTA;
	global $_LISTA_2;
	
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
	
	if($_REQUEST['usuario']){
		if($_REQUEST['usuario'] != $_REQUEST['edit_usuario']){
			banco_conectar();
			
			$resultado = banco_select
			(
				$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['campo']."='" . $_REQUEST['usuario'] . "' AND status!='D'"
			);
			
			banco_fechar_conexao();

			if($resultado){
				$saida = "sim";
			} else {
				$saida = "nao";
			}
		} else {
			$saida = "nao";
		}
	}
	
	if($_REQUEST['email']){
		if($_REQUEST['email'] != $_REQUEST['edit_email']){
			banco_conectar();
			
			$resultado = banco_select
			(
				$_LISTA['tabela']['id'],
				$_LISTA['tabela']['nome'],
				"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
			);
			$resultado2 = banco_select
			(
				"id_emails",
				"emails",
				"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
			);
			
			banco_fechar_conexao();

			if($resultado || $resultado2){
				$saida = "sim";
			} else {
				$saida = "nao";
			}
		} else {
			$saida = "nao";
		}
	}
	
	if($_REQUEST['cep']){
		$cep_part1 = explode('.',$_REQUEST['cep']);
		$cep_part2 = explode('-',$cep_part1[0].$cep_part1[1]);
		
		$cep = $cep_part2[0].$cep_part2[1];
		$cep_cid = $cep_part2[0].'000';
		
		banco_conectar();
		
		$endereco = banco_select_name
		(
			banco_campos_virgulas(Array(
				'bairro_codigo',
				'endereco_logradouro',
			))
			,
			"endereco",
			"WHERE endereco_cep='".$cep."'"
		);
		$bairro = banco_select_name
		(
			banco_campos_virgulas(Array(
				'bairro_descricao',
				'cidade_codigo',
			))
			,
			"bairro",
			"WHERE bairro_codigo='".$endereco[0]['bairro_codigo']."'"
		);
		$cidade = banco_select_name
		(
			banco_campos_virgulas(Array(
				'uf_codigo',
				'cidade_descricao',
			))
			,
			"cidade",
			"WHERE cidade_codigo='".$bairro[0]['cidade_codigo']."'"
		);
		$uf = banco_select_name
		(
			banco_campos_virgulas(Array(
				'uf_sigla',
			))
			,
			"uf",
			"WHERE uf_codigo='".$cidade[0]['uf_codigo']."'"
		);
		
		banco_fechar_conexao();

		$saida = "{\n";
		$saida .= "'endereco' : '".$endereco[0]['endereco_logradouro']."' ,\n";
		$saida .= "'bairro' : '".$bairro[0]['bairro_descricao']."' ,\n";
		$saida .= "'cidade' : '".$cidade[0]['cidade_descricao']."' ,\n";
		$saida .= "'uf' : '".$uf[0]['uf_sigla']."' ,\n";
		$saida .= "}\n";
	}
	
	return utf8_encode($saida);
}

function start(){	
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_HTML;
	global $_LOCAL_ID;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'menu_'.$_LOCAL_ID:
			case 'lista':						$saida = lista();break;
			case 'menu_pasta_usuario':
			case 'pasta_usuario':				$saida = (operacao('pasta_usuario') ? pasta_usuario_lista() : lista());break;
			case 'pasta_usuario_add':			$saida = (operacao('pasta_usuario') ? pasta_usuario_add() : lista());break;
			case 'add':							$saida = (operacao('adicionar') ? add() : lista());break;
			case 'add_base':					$saida = (operacao('adicionar') ? add_base() : lista());break;
			case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'editar_base':					$saida = (operacao('editar') ? editar_base() : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : lista());break;
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'grupos':						$saida = (operacao('grupos') ? grupos() : lista());break;
			case 'grupos_base':					$saida = (operacao('grupos') ? grupos_base() : lista());break;
			case 'menu_moderar_dados_pessoais':
			case 'moderar_dados_pessoais':		$saida = (operacao('moderar_dados_pessoais') ? moderar_dados_pessoais() : lista());break;
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