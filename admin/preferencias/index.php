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
$_LOCAL_ID					=	"preferencias";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Preferências.";

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
$_LISTA['ferramenta']			=	'Preferências';
$_LISTA['ferramenta_unidade']	=	'a preferência';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

function identificador_unico($id,$num,$id_variavel_global){
	$conteudo = banco_select
	(
		"id_variavel_global"
		,
		"variavel_global",
		"WHERE "."variavel"."='".($num ? $id.'-'.$num : $id)."'"
		.($id_variavel_global?" AND id_variavel_global!='".$id_variavel_global."'":"")
		." AND grupo='categorias'"
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1,$id_variavel_global);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador($id,$id_variavel_global = false){
	$tam_max_id = 90;
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
	if(count($id_aux) > 1 && is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		return identificador_unico($id,$num,$id_variavel_global);
	} else {
		return identificador_unico($id,0,$id_variavel_global);
	}
}

// =====================================================

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
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?opcao=add_categoria', // link da opção
		'title' => 'Adicionar Categoria ' . $_LISTA['ferramenta'], // título da opção
		'img_coluna' => 3, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Categoria', // Nome do menu
	);
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
	$exclusao_botoes = modelo_tag_val($modelo,'<!-- exclusao_botoes < -->','<!-- exclusao_botoes > -->');
	
	$cel_nome = 'categoria'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$cel_nome = 'text'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'string'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'int'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'float'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'bool'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'status'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'tinymce'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	
	banco_conectar();
	
	$categorias = banco_select_name
	(
		banco_campos_virgulas(Array(
			'variavel',
			'valor',
			'descricao',
			'id_variavel_global',
		))
		,
		"variavel_global",
		"WHERE (grupo='categorias')"
	);
	
	if($categorias)
	foreach($categorias as $cat){
		$categorias_where .= " OR grupo='".$cat['variavel']."'";
		
		$cel_aux = $cel['categoria'];
		
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#cat-id#',$cat['id_variavel_global']);
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#cat-grupo#',$cat['variavel']);
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#cat-titulo#',$cat['valor']);
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#cat-descricao#',$cat['descricao']);
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#cat-opcoes#','<!-- '.$cat['variavel'].'#opcoes -->');
		
		$_SESSION[$_SYSTEM['ID'].'lista_tab'][$cat['id_variavel_global']] = $cat['variavel'];
		$categorias_ids[$cat['variavel']] = $cat['id_variavel_global'];
		
		$cel_nome = 'categoria'; $pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		$cel_nome = 'categorias'; $pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',"	<li><a href=\"#".$cat['variavel']."\">".$cat['valor']."</a></li>\n");
	}
	
	$variaveis_globais = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_variavel_global',
			'grupo',
			'variavel',
			'valor',
			'tipo',
			'descricao',
		))
		,
		"variavel_global",
		"WHERE (grupo='html_meta' OR grupo='html' OR grupo='system'".$categorias_where.")"
		.(operacao('mostrar_todas') ?
			""
			:
			" status!='O'"
			.(!$_SYSTEM['INSTALL']?" AND status='A'":"")
		)
		." ORDER BY grupo ASC, tipo ASC, variavel ASC"
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
				'grupo' => $variavel_global['grupo'],
			);
			
			$variavel_global['variavel'] = $depois;
		}
		
		if($variavel_global['variavel']){
			$campos_guardar[$variavel_global['grupo'].'-'.$variavel_global['variavel']] = $variavel_global['valor'];
			$campos_nome[] = Array(
				'grupo' => $variavel_global['grupo'],
				'variavel' => $variavel_global['variavel'],
			);
			$flag[$variavel_global['grupo']][$variavel_global['variavel']] = true;
		}
		
		if($variavel_global['tipo'] == 'bool')
			if($variavel_global['valor'])
				$variavel_global['valor'] = ' checked="checked"';
		
		$cel_aux = $cel[strtolower($variavel_global['tipo'])];
		
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#variavel',$variavel_global['variavel']);
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#post-name-id#',$variavel_global['grupo'].'-'.$variavel_global['variavel']);
		$cel_aux = modelo_var_troca($cel_aux,'#valor',$variavel_global['valor']);
		$cel_aux = modelo_var_troca_tudo($cel_aux,'#descricao',$variavel_global['descricao']);
		
		if(
			$variavel_global['grupo'] == 'html_meta' ||
			$variavel_global['grupo'] == 'html' ||
			$variavel_global['grupo'] == 'system'
		){
			$pagina = modelo_var_in($pagina,'#'.$variavel_global['grupo'].'$',$cel_aux);
		} else {
			$cel_aux2 = $exclusao_botoes;
			$cel_aux2 = modelo_var_troca_tudo($cel_aux2,'#cat-id#',$categorias_ids[$variavel_global['grupo']]);
			$cel_aux2 = modelo_var_troca_tudo($cel_aux2,'#pre-id#',$variavel_global['id_variavel_global']);
			
			$cel_aux = modelo_var_troca($cel_aux,'<!-- exclusao_botao -->',$cel_aux2);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$variavel_global['grupo'].'#opcoes -->',$cel_aux);
		}
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
	$_INTERFACE['informacao_titulo'] = "Lista";
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
	
	foreach($campos_nome as $campo){
		$campo_nome = $campo['variavel'];
		$campo_grupo = $campo['grupo'];
		
		$antes = false;
		if($vars_mudadas)
		foreach($vars_mudadas as $var_mudada){
			if($campo_nome == $var_mudada['depois'] && $campo_grupo == $var_mudada['grupo']){
				$antes = $var_mudada['antes'];
				break;
			}
		}
		
		if($campos_antes['flag'][$campo_grupo][$campo_nome]){
			$post_nome = $campo_grupo.'-'.$campo_nome;
			
			switch($campo_nome){
				case 'IMG_GRANDE_WIDTH':
				case 'IMG_GRANDE_HEIGHT':
					if($_POST[$post_nome] && !$gallerific_flag){
						if(
							$campos_antes[$campo_grupo.'-'.$campo_nome] != $_POST[$post_nome] &&
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
			
			if($campos_antes[$post_nome] != $_POST[$post_nome]){$editar['tabela'][$post_nome] = "valor='" . $_POST[$post_nome] . "'";}
			
			if($editar['tabela'][$post_nome]){
				banco_update
				(
					$editar['tabela'][$post_nome],
					"variavel_global",
					"WHERE variavel='".($antes?$antes:$campo_nome)."'"
					." AND grupo='".$campo_grupo."'"
				);
			}
			
			$editar = false;
		} else {
			$campos[] = Array("variavel",($antes?$antes:$campo_nome));
			if($_POST[$campo_nome])		$campos[] = Array("valor",$_POST[$campo_nome]);
			
			banco_insert_name($campos,"variavel_global");
			$campos = false;
		}
	}
	
	// ======================================================================================
	
	banco_fechar_conexao();
	
	$_SESSION[$_SYSTEM['ID'].'variaveis_globais'] = false;
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ."admin/preferencias/");
}

function add_categoria($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- form2 < -->','<!-- form2 > -->');
	
	$pagina = modelo_var_troca($pagina,'#categoria#','');
	$pagina = modelo_var_troca($pagina,'#descricao#','');
	
	// ======================================================================================
	
	campos_antes_guardar($campos_guardar);
	
	$in_titulo = $param ? "Visualizar" : "Modificar";
	$botao = "Gravar";
	$opcao = "add_categoria_base";
	
	$pagina = modelo_var_troca($pagina,"#form_url",$_LOCAL_ID);
	$pagina = modelo_var_troca($pagina,"#botao",$botao);
	$pagina = modelo_var_troca($pagina,"#opcao",$opcao);
	$pagina = modelo_var_troca($pagina,"#id",$id);
	
	if(!operacao('add_categoria'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$_INTERFACE_OPCAO = 'editar'; 
	$_INTERFACE['informacao_titulo'] = "Adicionar Categoria";
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function add_categoria_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	banco_conectar();
	
	$variavel = criar_identificador($_REQUEST['categoria']);
	
	$campo_nome = "valor"; $post_nome = 'categoria'; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "descricao"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	$campo_nome = "variavel"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$variavel);
	$campo_nome = "grupo"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'categorias');
	$campo_nome = "tipo"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'string');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
	$id = banco_last_id();
	
	$_SESSION[$_SYSTEM['ID'].'lista_tab'][$id] = $variavel;
	$_SESSION[$_SYSTEM['ID'].'active_tab'] = $variavel;
	
	return editar('ver');
}

function editar_categoria($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form2 < -->','<!-- form2 > -->');
		
		banco_conectar();
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
				'descricao',
				'variavel',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$campos_guardar = Array(
			'valor' => $tabela[0]['valor'],
			'descricao' => $tabela[0]['descricao'],
			'variavel' => $tabela[0]['variavel'],
		);
		
		$pagina = paginaTrocaVarValor($pagina,'#categoria#',$tabela[0]['valor']);
		$pagina = paginaTrocaVarValor($pagina,'#descricao#',$tabela[0]['descricao']);
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "editar_categoria_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
		$_INTERFACE_OPCAO = 'editar';
		$_INTERFACE['local'] = 'conteudo';
		$_INTERFACE['informacao_titulo'] = $in_titulo." Categoria";
		$_INTERFACE['informacao_tipo'] = $tipo;
		$_INTERFACE['informacao_id'] = $id;
		$_INTERFACE['inclusao'] = $pagina;
	
		return interface_layout(parametros_interface());
	} else
		return editar('ver');
}

function editar_categoria_base(){
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
		
		$variavel = criar_identificador($_REQUEST['categoria'],$id);
		
		if($campos_antes['variavel'] != $variavel){
			banco_update
			(
				"grupo='".$variavel."'",
				"variavel_global",
				"WHERE grupo='".$campos_antes['variavel']."'"
			);
		}
		
		$campo_tabela = "tabela";
		$campo_nome = "valor"; $post_nome = 'categoria'; if($campos_antes[$campo_nome] != $_REQUEST[$post_nome]){$editar['tabela'][] = $campo_nome."='" . $_REQUEST[$post_nome] . "'";}
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";}
		$campo_nome = "variavel"; if($campos_antes[$campo_nome] != $variavel){$editar['tabela'][] = $campo_nome."='" . $variavel . "'";}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		$_SESSION[$_SYSTEM['ID'].'lista_tab'][$id] = $variavel;
		$_SESSION[$_SYSTEM['ID'].'active_tab'] = $variavel;
		
		// ======================================================================================
		
		banco_fechar_conexao();
	}
	
	return editar('ver');
}

function excluir_categoria(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		
		banco_conectar();
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'variavel',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_delete
		(
			$_LISTA['tabela']['nome'],
			"WHERE grupo='".$resultado[0]['variavel']."'"
		);
		banco_delete
		(
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_fechar_conexao();

		$_SESSION[$_SYSTEM['ID'].'active_tab'] = false;
	}
	
	return editar('ver');
}

// =============================================== Preferência ========================================

function identificador_unico2($id,$num,$grupo_pai,$id_variavel_global){
	$conteudo = banco_select
	(
		"id_variavel_global"
		,
		"variavel_global",
		"WHERE "."variavel"."='".($num ? $id.'-'.$num : $id)."'"
		.($id_variavel_global?" AND id_variavel_global!='".$id_variavel_global."'":"")
		." AND grupo='".$grupo_pai."'"
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico2($id,$num + 1,$grupo_pai,$id_variavel_global);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador2($id,$grupo_pai,$id_variavel_global = false){
	$tam_max_id = 90;
	//$id = retirar_acentos(trim($id));
	
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
	if(count($id_aux) > 1 && is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		return identificador_unico2($id,$num,$grupo_pai,$id_variavel_global);
	} else {
		return identificador_unico2($id,0,$grupo_pai,$id_variavel_global);
	}
}

function tipo_select(){
	global $_BANCO_PREFIXO,
	$_SYSTEM_ID,
	$_PAGINA_LOCAL,
	$_URL;
	
	$nome = 'tipo';
	$id = $nome . '_id';
	
	$cel_nome = 'text'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'string'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'int'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'float'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'bool'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'status'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'tinymce'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	
	
	$opcoes = Array(
		Array(	"Booleano" , "bool"		),
		Array(	"Flutuante" , "float"		),
		Array(	"Inteiro" , "int"		),
		Array(	"Status" , "status"		),
		Array(	"String" , "string"		),
		Array(	"Texto" , "text"		),
		Array(	"TinyMCE" , "tinymce"		),
	);
	
	for($i=0;$i<count($opcoes);$i++){
		$options[] = $opcoes[$i][0];
		$optionsValue[] = $opcoes[$i][1];
		
		if($_SESSION[$_SYSTEM_ID.$id] == $opcoes[$i][1]){
			$optionSelected = $i;
		}
	}
	
	if(!$optionSelected && count($opcoes) == 1)
		$optionSelected = 1;
	
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'size="7"');
	
	return $select;
}

function add_preferencia($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- form3 < -->','<!-- form3 > -->');
	
	$pagina = modelo_var_troca($pagina,'#tipo#',tipo_select());
	$pagina = modelo_var_troca($pagina,'#variavel#','');
	$pagina = modelo_var_troca($pagina,'#descricao#','');
	
	// ======================================================================================
	
	campos_antes_guardar($campos_guardar);
	
	$in_titulo = $param ? "Visualizar" : "Modificar";
	$botao = "Gravar";
	$opcao = "add_preferencia_base";
	
	$pagina = modelo_var_troca($pagina,"#form_url",$_LOCAL_ID);
	$pagina = modelo_var_troca($pagina,"#botao",$botao);
	$pagina = modelo_var_troca($pagina,"#opcao",$opcao);
	$pagina = modelo_var_troca($pagina,"#id#",$_REQUEST["id"]);
	$pagina = modelo_var_troca($pagina,"#id2#",$id2);
	
	if(!operacao('add_categoria'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$_INTERFACE_OPCAO = 'editar'; 
	$_INTERFACE['informacao_titulo'] = "Adicionar Preferência de Categoria de";
	$_INTERFACE['informacao_id'] = $id2;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function add_preferencia_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	$id = $_REQUEST["id"];
	
	banco_conectar();
	
	$grupo_pai = $_SESSION[$_SYSTEM['ID'].'lista_tab'][$id];
	$variavel = criar_identificador2($_REQUEST['variavel'],$grupo_pai);
	
	$campo_nome = "descricao"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "tipo"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	$campo_nome = "variavel"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$variavel);
	$campo_nome = "grupo"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$grupo_pai);
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
	$_SESSION[$_SYSTEM['ID'].'active_tab'] = $_SESSION[$_SYSTEM['ID'].'lista_tab'][$id];
	
	$_SESSION[$_SYSTEM['ID'].'variaveis_globais'] = null;

	return editar('ver');
}

function editar_preferencia($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	if($_REQUEST["id2"])					$id2 = $_REQUEST["id2"];
	
	if($id2){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form3 < -->','<!-- form3 > -->');
		
		banco_conectar();
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'tipo',
				'descricao',
				'variavel',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id2."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$campos_guardar = Array(
			'tipo' => $tabela[0]['tipo'],
			'descricao' => $tabela[0]['descricao'],
			'variavel' => $tabela[0]['variavel'],
		);
		
		$_SESSION[$_SYSTEM_ID.'tipo_id'] = $tabela[0]['tipo'];
		
		$pagina = paginaTrocaVarValor($pagina,'#tipo#',tipo_select());
		$pagina = paginaTrocaVarValor($pagina,'#variavel#',$tabela[0]['variavel']);
		$pagina = paginaTrocaVarValor($pagina,'#descricao#',$tabela[0]['descricao']);
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "editar_preferencia_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id#",$id);
		$pagina = paginaTrocaVarValor($pagina,"#id2#",$id2);
		
		if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
		$_INTERFACE_OPCAO = 'editar';
		$_INTERFACE['local'] = 'conteudo';
		$_INTERFACE['informacao_titulo'] = $in_titulo." Preferência de Categoria de";
		$_INTERFACE['informacao_tipo'] = $tipo;
		$_INTERFACE['informacao_id'] = $id;
		$_INTERFACE['inclusao'] = $pagina;
		
		return interface_layout(parametros_interface());
	} else
		return editar('ver');
}

function editar_preferencia_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	if($_REQUEST["id2"])					$id2 = $_REQUEST["id2"];
	
	if($id2){
		$campos_antes = campos_antes_recuperar();
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$grupo_pai = $_SESSION[$_SYSTEM['ID'].'lista_tab'][$id];
		$variavel = criar_identificador2($_REQUEST['variavel'],$grupo_pai,$id2);
		
		$campo_tabela = "tabela";
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";}
		$campo_nome = "tipo"; if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";}
		$campo_nome = "variavel"; if($campos_antes[$campo_nome] != $variavel){$editar['tabela'][] = $campo_nome."='" . $variavel . "'";}
		$campo_nome = "valor"; $editar['tabela'][] = $campo_nome."=NULL";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id2."'"
				." AND grupo='".$grupo_pai."'"
			);
		}
		
		// ======================================================================================
		
		$_SESSION[$_SYSTEM['ID'].'active_tab'] = $_SESSION[$_SYSTEM['ID'].'lista_tab'][$id];
		
		$_SESSION[$_SYSTEM['ID'].'variaveis_globais'] = null;

		banco_fechar_conexao();
	}
	
	return editar('ver');
}

function excluir_preferencia(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	if($_REQUEST["id2"])					$id2 = $_REQUEST["id2"];
	
	if($id2){		
		banco_conectar();
		banco_delete
		(
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id2."'"
		);
		banco_fechar_conexao();
	}
	
	$_SESSION[$_SYSTEM['ID'].'active_tab'] = $_SESSION[$_SYSTEM['ID'].'lista_tab'][$id];
	
	return editar('ver');
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
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
	global $_HTML;
	global $_VARIAVEIS_JS;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'editar':						$saida = (operacao('editar') ? editar() : editar('ver'));break;
			case 'editar_base':					$saida = (operacao('editar') ? editar_base() : editar('ver'));break;
			case 'add_categoria':				$saida = (operacao('add_categoria') ? add_categoria() : add_categoria('ver'));break;
			case 'add_categoria_base':			$saida = (operacao('add_categoria') ? add_categoria_base() : add_categoria('ver'));break;
			case 'editar_categoria':			$saida = (operacao('editar') ? editar_categoria() : editar_categoria('ver'));break;
			case 'editar_categoria_base':		$saida = (operacao('editar') ? editar_categoria_base() : editar('ver'));break;
			case 'excluir_categoria':			$saida = (operacao('editar') ? excluir_categoria() : editar('ver'));break;
			case 'add_preferencia':				$saida = (operacao('add_categoria') ? add_preferencia() : add_preferencia('ver'));break;
			case 'add_preferencia_base':		$saida = (operacao('add_categoria') ? add_preferencia_base() : add_preferencia('ver'));break;
			case 'editar_preferencia':			$saida = (operacao('editar') ? editar_preferencia() : editar_preferencia('ver'));break;
			case 'editar_preferencia_base':		$saida = (operacao('editar') ? editar_preferencia_base() : editar('ver'));break;
			case 'excluir_preferencia':			$saida = (operacao('editar') ? excluir_preferencia() : editar('ver'));break;
			default: 							$saida = editar('ver');$_SESSION[$_SYSTEM['ID'].'active_tab'] = false;
		}
		
		
		$_VARIAVEIS_JS['active_tab'] = $_SESSION[$_SYSTEM['ID'].'active_tab'];
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

start();

?>