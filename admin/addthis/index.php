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
$_LOCAL_ID					=	"addthis";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Addthis.";

$_HTML['js'] = 
$_JS['menu'].
$_JS['tinyMce'].
$_JS['maskedInput'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'variavel_global';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_variavel_global';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Addthis';
$_LISTA['ferramenta_unidade']	=	'a preferência';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	
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
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'home_mini.png', // caminho da imagem
		'name' => 'Início', // Nome do menu
	);
	/* $menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL, // link da opção
		'title' => 'Lista ' . $_LISTA['ferramenta'], // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'lista.jpg', // caminho da imagem
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
	); */
	/* $menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?opcao=add', // link da opção
		'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'entrar.png', // caminho da imagem
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
	); */
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

function editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- form < -->','<!-- form > -->');
	$opcoes = modelo_tag_val($modelo,'<!-- opcoes < -->','<!-- opcoes > -->');
	
	$cel_nome = 'text'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'string'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'int'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'float'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'bool'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'status'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'tinymce'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	
	banco_conectar();
	
	$variaveis_globais = banco_select_name
	(
		banco_campos_virgulas(Array(
			'grupo',
			'variavel',
			'valor',
			'tipo',
			'descricao',
		))
		,
		"variavel_global",
		"WHERE variavel='ADD-THIS'"
		."ORDER BY grupo ASC, tipo ASC, variavel ASC"
	);
	
	if($variaveis_globais)
	foreach($variaveis_globais as $variavel_global){
		$replacement = '-';
		$pattern = '/\./i';
		$antes = $variavel_global['variavel'];
		
		if(preg_match($pattern, $antes) > 0){
			$depois = preg_replace($pattern, $replacement, $antes);
			$campos_guardar['var_mudada'][] = Array(
				'antes' => $antes,
				'depois' => $depois,
			);
			
			$variavel_global['variavel'] = $depois;
		}
		
		if($variavel_global['variavel']){
			$campos_guardar[$variavel_global['variavel']] = $variavel_global['valor'];
			$campos_nome[] = $variavel_global['variavel'];
			$flag[$variavel_global['variavel']] = true;
		}
		
		if($variavel_global['tipo'] == 'bool')
			if($variavel_global['valor'])
				$variavel_global['valor'] = ' checked="checked"';
		
		$cel_aux = $cel[strtolower($variavel_global['tipo'])];
		
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#variavel',$variavel_global['variavel']);
		$cel_aux = modelo_var_troca($cel_aux,'#valor',$variavel_global['valor']);
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#descricao',$variavel_global['descricao']);
		
		$pagina = modelo_var_in($pagina,'#'.$variavel_global['grupo'].'$',$cel_aux);
	}
	
	$pagina = modelo_var_troca($pagina,'#html_meta$','');
	$pagina = modelo_var_troca($pagina,'#html$','');
	$pagina = modelo_var_troca($pagina,'#system$','');
	
	$campos_guardar['flag'] = $flag;
	$campos_guardar['campos_nome'] = $campos_nome;
	
	// ======================================================================================
	
	banco_fechar_conexao();
	
	campos_antes_guardar($campos_guardar);
	
	$in_titulo = $param ? "Visualizar" : "Modificar";
	$botao = "Gravar";
	$opcao = "editar_base";
	
	$pagina = modelo_var_troca($pagina,"#form_url",$_LOCAL_ID);
	$pagina = modelo_var_troca($pagina,"#botao",$botao);
	$pagina = modelo_var_troca($pagina,"#opcao",$opcao);
	$pagina = modelo_var_troca($pagina,"#id",$id);
	
	if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$_INTERFACE_OPCAO = 'editar'; 
	$_INTERFACE['informacao_titulo'] = $botao;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function editar_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_CAMINHO_RELATIVO_RAIZ;
	
	$campos_antes = campos_antes_recuperar();
	
	banco_conectar();
	
	// ================================= Local de Edição ===============================
	// Altere os campos da tabela e POST aqui, e modifique o UPDATE
	$campo_tabela = "tabela";
	
	$s = $_SYSTEM['SEPARADOR'];
	$campos_nome = $campos_antes['campos_nome'];
	$gallerific_flag = false;
	$filename = $_SYSTEM['PATH'] . 'files' . $s . 'galleriffic-2.0' . $s . 'css' . $s . 'galleriffic_resolucao.css';
	
	$vars_mudadas = $campos_antes['var_mudada'];
	
	foreach($campos_nome as $campo_nome){
		$antes = false;
		if($vars_mudadas)
		foreach($vars_mudadas as $var_mudada){
			if($campo_nome == $var_mudada['depois']){
				$antes = $var_mudada['antes'];
				break;
			}
		}
		
		if($campos_antes['flag'][$campo_nome]){
			switch($campo_nome){
				case 'IMG_GRANDE_WIDTH':
				case 'IMG_GRANDE_HEIGHT':
					if($_POST[$post_nome] && !$gallerific_flag){
						if(
							$campos_antes[$campo_nome] != $_POST[$post_nome] &&
							$_POST['IMG_GRANDE_WIDTH'] &&
							$_POST['IMG_GRANDE_HEIGHT']
						){
							$width = (int)$_POST['IMG_GRANDE_WIDTH'] + 5;
							$height = $_POST['IMG_GRANDE_HEIGHT'];
							
							$modelo = modelo_abrir('html.html');
							$gallerific = modelo_tag_val($modelo,'<!-- gallerific < -->','<!-- gallerific > -->');
							
							$gallerific = modelo_var_troca($gallerific,"#width#",$width);
							$gallerific = modelo_var_troca_tudo($gallerific,"#height#",$height);
							
							file_put_contents($filename,$gallerific);
							
							$gallerific_flag = true;
						}
					}
				break;
			}
			
			$post_nome = $campo_nome; if($campos_antes[$campo_nome] != $_POST[$post_nome]){$editar['tabela'][$campo_nome] = "valor='" . $_POST[$post_nome] . "'";}
			if($editar['tabela'][$campo_nome]){
				banco_update
				(
					$editar['tabela'][$campo_nome],
					"variavel_global",
					"WHERE variavel='".($antes?$antes:$campo_nome)."'"
				);
			}
		} else {
			$campos[] = Array("variavel",($antes?$antes:$campo_nome));
			if($_POST[$campo_nome])		$campos[] = Array("valor",$_POST[$campo_nome]);
			
			banco_insert_name($campos,"variavel_global");
		}
		$campos = false;
	}
	
	// ======================================================================================
	
	banco_fechar_conexao();
	
	session_unset();
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ."login/");
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