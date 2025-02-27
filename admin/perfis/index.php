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

$_VERSAO_MODULO				=	'1.0.2';
$_LOCAL_ID					=	"perfis";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Perfis.";

$_HTML['js'] .= 
$_JS['menu'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'usuario_perfil';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'usuario_perfil';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Perfis';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']					=	$_CAMINHO_RELATIVO_RAIZ;

// Funções de assistência

function alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	global $_MENSAGEM_ERRO;
	global $_SYSTEM;
	
	switch ($nAlerta){
		//case 1:		$mensSaida	=	"";break;
		default:	$mensSaida	=	$nAlerta;
	}

	$_ALERTA = $mensSaida;
}

// Funções do Sistema

function perfis($params = false){
	global $_SYSTEM;
	
	$modelo = modelo_abrir('html.html');
	$perfis = modelo_tag_val($modelo,'<!-- perfis < -->','<!-- perfis > -->');
	
	$cel_nome = 'operacao'; $cel[$cel_nome] = modelo_tag_val($perfis,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$perfis = modelo_tag_in($perfis,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'modulo'; $cel[$cel_nome] = modelo_tag_val($perfis,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$perfis = modelo_tag_in($perfis,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulo',
			'id_modulo_pai',
			'nome',
			'descricao',
			'imagem',
		))
		,
		"modulo",
		"WHERE id_modulo!='6' AND status='A'"
		." ORDER BY id_modulo_pai ASC,nome ASC"
	);
	$modulos_operacao = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulo_operacao',
			'id_modulo',
			'nome',
			'descricao',
		))
		,
		"modulo_operacao",
		"ORDER BY nome ASC"
	);
	
	$count_modulos = 0;
	$count_operacoes = 0;
	$checked = ' checked="checked"';
	$linha_grade = 20;
	$coluna_grade = 20;
	
	if($modulos){
		foreach($modulos as $modulo){
			if(!$modulo['id_modulo_pai']){
				$pai_cel = $perfis;
				$pai_cel = modelo_var_troca($pai_cel,"#grupo_"."nome",$modulo['nome']);
				$pai_cel = modelo_var_troca($pai_cel,"#grupo_"."descricao",$modulo['descricao']);
				$pai[$modulo['id_modulo']] = $pai_cel;
			} else {
				$modulo['checked'] = '';
				if($params){
					$perfils_modulo = $params['perfil_modulo'];
					if($perfils_modulo)
					foreach($perfils_modulo as $perfil_modulo){
						if($perfil_modulo['id_modulo'] == $modulo['id_modulo']){
							$modulo['checked'] = $checked;
						}
					}
				}
				
				if($modulo['imagem']){
					list($linha,$coluna) = explode(',',$modulo['imagem']);
				} else {
					$linha = 1;
					$coluna = 6;
				}
				
				(int)$linha;(int)$coluna;
				$linha--;$coluna--;
				
				$linha = $linha * $linha_grade;
				$coluna = $coluna * $coluna_grade;
				
				$cel_nome = 'modulo';
				$cel_aux = $cel[$cel_nome];
				$cel_aux = modelo_var_troca($cel_aux,"#modulo_"."val",$modulo['id_modulo']);
				$cel_aux = modelo_var_troca($cel_aux,"#modulo_"."id",'modulo_'.$count_modulos);
				$cel_aux = modelo_var_troca($cel_aux,"#modulo_"."name",'modulo_'.$count_modulos);
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#modulo_"."nome",$modulo['nome']);
				$cel_aux = modelo_var_troca($cel_aux,"#modulo_"."descricao",$modulo['descricao']);
				$cel_aux = modelo_var_troca($cel_aux,"#"."img_linha"."#",$linha);
				$cel_aux = modelo_var_troca($cel_aux,"#"."img_coluna"."#",$coluna);
				$cel_aux = modelo_var_troca($cel_aux,"#modulo_"."checked",$modulo['checked']);
				
				$num_operacoes = 0;
				
				foreach($modulos_operacao as $modulo_operacao){
					if($modulo['id_modulo'] == $modulo_operacao['id_modulo']){
						$modulo_operacao['checked'] = '';
						if($params){
							$perfils_modulos_operacao = $params['perfil_modulo_operacao'];
							if($perfils_modulos_operacao)
							foreach($perfils_modulos_operacao as $perfil_modulo_operacao){
								if(
									$perfil_modulo_operacao['id_modulo'] == $modulo_operacao['id_modulo'] &&
									$perfil_modulo_operacao['id_modulo_operacao'] == $modulo_operacao['id_modulo_operacao']
								){
									$modulo_operacao['checked'] = $checked;
								}
							}
						}
						
						$cel_nome2 = 'operacao';
						
						$cel_aux2 = $cel[$cel_nome2];
						$cel_aux2 = modelo_var_troca($cel_aux2,"#operacao_"."val",$modulo_operacao['id_modulo'].','.$modulo_operacao['id_modulo_operacao']);
						$cel_aux2 = modelo_var_troca($cel_aux2,"#operacao_"."class",' modulo_'.$count_modulos);
						$cel_aux2 = modelo_var_troca($cel_aux2,"#operacao_"."id",'operacao_'.$count_operacoes);
						$cel_aux2 = modelo_var_troca($cel_aux2,"#operacao_"."name",'operacao_'.$count_operacoes);
						$cel_aux2 = modelo_var_troca($cel_aux2,"#operacao_"."nome",$modulo_operacao['nome']);
						$cel_aux2 = modelo_var_troca($cel_aux2,"#operacao_"."descricao",$modulo_operacao['descricao']);
						$cel_aux2 = modelo_var_troca($cel_aux2,"#operacao_"."checked",$modulo_operacao['checked']);
						
						$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_nome2.' -->',$cel_aux2.($num_operacoes % 4 == 3 ? '<div class="clear"></div>' : ''));
						
						$count_operacoes++;
						$num_operacoes++;
					}
				}
				
				$pai[$modulo['id_modulo_pai']] = modelo_var_in($pai[$modulo['id_modulo_pai']],'<!-- '.$cel_nome.' -->',$cel_aux);
				
				$count_modulos++;
			}
		}
	}
	
	if($pai){
		foreach($pai as $dados){
			$grupos .= $dados;
		}
	}
	
	$cel_nome = 'modulo';		$grupos = modelo_var_troca_tudo($grupos,'<!-- '.$cel_nome.' -->','');
	$cel_nome = 'operacao';		$grupos = modelo_var_troca_tudo($grupos,'<!-- '.$cel_nome.' -->','');
	
	return Array(
		'perfis' => $grupos,
		'operacoes' => $count_operacoes,
		'modulos' => $count_modulos,
	);	
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
	
	banco_conectar();
	
	$perfis = perfis();
	
	$pagina = paginaTrocaVarValor($pagina,'#nome',$nome);
	$pagina = paginaTrocaVarValor($pagina,'#perfis',$perfis['perfis']);
	$pagina = paginaTrocaVarValor($pagina,'#operacoes',$perfis['operacoes']);
	$pagina = paginaTrocaVarValor($pagina,'#modulos',$perfis['modulos']);
	
	banco_fechar_conexao();
	
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
	global $_INTERFACE;
	
	banco_conectar();
	
	$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	$id_tabela = banco_last_id();
	$campos = null;
	
	$modulos = $_REQUEST['modulos'];
	$operacoes = $_REQUEST['operacoes'];
	
	if($modulos){
		for($i=0;$i<$modulos;$i++){
			$modulo_chk = $_REQUEST['modulo_'.$i];
			if($modulo_chk){
				$bd_modulo_id[] = $modulo_chk;
				$bd_perfil_id[] = $id_tabela;
			}
		}
		
		if($bd_modulo_id){
			$campos[] = Array('id_usuario_perfil',$bd_perfil_id);
			$campos[] = Array('id_modulo',$bd_modulo_id);
			
			banco_insert_name_varios($campos,'usuario_perfil_modulo');
		}
	}
	
	$campos = null;
	$bd_modulo_id = null;
	$bd_perfil_id = null;
	
	if($operacoes){
		for($i=0;$i<$operacoes;$i++){
			$operacao_chk = $_REQUEST['operacao_'.$i];
			if($operacao_chk){
				$valores = explode(',',$operacao_chk);
				$bd_modulo_id[] = $valores[0];
				$bd_modulo_operacao_id[] = $valores[1];
				$bd_perfil_id[] = $id_tabela;
			}
		}
		
		if($bd_modulo_id){
			$campos[] = Array('id_usuario_perfil',$bd_perfil_id);
			$campos[] = Array('id_modulo_operacao',$bd_modulo_operacao_id);
			$campos[] = Array('id_modulo',$bd_modulo_id);
			
			banco_insert_name_varios($campos,'usuario_perfil_modulo_operacao');
		}
	}
	
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
	global $_MODULOS_ID;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		banco_conectar();
		
		$usuario_perfil_modulo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulo',
			))
			,
			'usuario_perfil_modulo',
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);		
		$usuario_perfil_modulo_operacao = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulo_operacao',
				'id_modulo',
			))
			,
			'usuario_perfil_modulo_operacao',
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$campos_guardar = Array(
			'nome' => $tabela[0]['nome'],
			'modulos' => $usuario_perfil_modulo,
			'operacoes' => $usuario_perfil_modulo_operacao,
		);
		
		$params = Array(
			'perfil_modulo' => $usuario_perfil_modulo,
			'perfil_modulo_operacao' => $usuario_perfil_modulo_operacao,
		);
		
		$perfis = perfis($params);
	
		$pagina = paginaTrocaVarValor($pagina,'#nome',$tabela[0]['nome']);
		$pagina = paginaTrocaVarValor($pagina,'#perfis',$perfis['perfis']);
		$pagina = paginaTrocaVarValor($pagina,'#operacoes',$perfis['operacoes']);
		$pagina = paginaTrocaVarValor($pagina,'#modulos',$perfis['modulos']);
		
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
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		$campos = null;
		
		$modulos = $_REQUEST['modulos'];
		$operacoes = $_REQUEST['operacoes'];
		
		// ======================================== Módulos ==============================================
		
		$modulos_antes = $campos_antes['modulos'];
		
		for($i=0;$i<$modulos;$i++){
			$modulo_chk = $_REQUEST['modulo_'.$i];
			
			if($modulo_chk){
				$found = false;
				if($modulos_antes)
				foreach($modulos_antes as $modulo_antes){
					if($modulo_antes['id_modulo'] == $modulo_chk){
						$found = true;
						$modulos_found[] = $modulo_chk;
						break;
					}
				}
				
				if(!$found){
					$bd_modulo_id[] = $modulo_chk;
					$bd_perfil_id[] = $id;
				}
			}
		}
		
		if($modulos_antes)
		foreach($modulos_antes as $modulo_antes){
			$found = false;
			if($modulos_found)
			foreach($modulos_found as $modulo_found){
				if($modulo_antes['id_modulo'] == $modulo_found){
					$found = true;
					break;
				}
			}
			
			if(!$found){
				banco_delete
				(
					"usuario_perfil_modulo",
					"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
					." AND id_modulo='".$modulo_antes['id_modulo']."'"
				);
			}
		}
		
		if($bd_modulo_id){
			$campos[] = Array('id_usuario_perfil',$bd_perfil_id);
			$campos[] = Array('id_modulo',$bd_modulo_id);
			
			banco_insert_name_varios($campos,'usuario_perfil_modulo');
		}
		
		// ======================================== Operações ==============================================
		
		$campos = null;
		$bd_modulo_id = null;
		$bd_perfil_id = null;
		
		$operacoes_antes = $campos_antes['operacoes'];
		
		for($i=0;$i<$operacoes;$i++){
			$operacao_chk = $_REQUEST['operacao_'.$i];
			
			if($operacao_chk){
				$valores = explode(',',$operacao_chk);
				$found = false;
				if($operacoes_antes)
				foreach($operacoes_antes as $operacao_antes){
					if(
						$operacao_antes['id_modulo'] == $valores[0] &&
						$operacao_antes['id_modulo_operacao'] == $valores[1]
					){
						$found = true;
						$operacoes_found[] = $valores[1];
						break;
					}
				}
				
				if(!$found){
					$bd_modulo_id[] = $valores[0];
					$bd_modulo_operacao_id[] = $valores[1];
					$bd_perfil_id[] = $id;
				}
			}
		}
		
		if($operacoes_antes)
		foreach($operacoes_antes as $operacao_antes){
			$found = false;
			if($operacoes_found)
			foreach($operacoes_found as $operacao_found){
				if($operacao_antes['id_modulo_operacao'] == $operacao_found){
					$found = true;
					break;
				}
			}
			
			if(!$found){
				banco_delete
				(
					"usuario_perfil_modulo_operacao",
					"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
					." AND id_modulo='".$operacao_antes['id_modulo']."'"
					." AND id_modulo_operacao='".$operacao_antes['id_modulo_operacao']."'"
				);
			}
		}
		
		if($bd_modulo_id){
			$campos[] = Array('id_usuario_perfil',$bd_perfil_id);
			$campos[] = Array('id_modulo_operacao',$bd_modulo_operacao_id);
			$campos[] = Array('id_modulo',$bd_modulo_id);
			
			banco_insert_name_varios($campos,'usuario_perfil_modulo_operacao');
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
		
		if($id != '2'){
			banco_conectar();
			banco_update
			(
				$_LISTA['tabela']['status']."='D'",
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			banco_fechar_conexao();
		} else {
			alerta('Esse perfil não pode ser excluído uma vez que o mesmo é utilizado como perfil dos cadastrados pela linha de frente do portal!');
		}
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
			case 'add':							$saida = (operacao('adicionar') ? add() : lista()); break;
			case 'add_base':					$saida = (operacao('adicionar') ? add_base() : lista());break;
			case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'editar_base':					$saida = (operacao('editar') ? editar_base() : lista());break;
			case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
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