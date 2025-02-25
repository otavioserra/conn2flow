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
$_LOCAL_ID					=	"newsletter";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Newsletter.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'newsletter';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'newsletter';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Newsletter';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_LISTA_2['tabela']['nome']			=	'pastas_usuarios';
$_LISTA_2['tabela']['campo']			=	'nome';
$_LISTA_2['tabela']['id']				=	'id_'.'pastas_usuarios';
$_LISTA_2['tabela']['status']			=	'status';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

function select_layouts($selecionado = false){
	global $_BANCO_PREFIXO,
	$_SYSTEM_ID,
	$_PAGINA_LOCAL,
	$_URL;
	
	$nome = 'id_newsletter_layout';
	$id = $nome;
	
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$tabela = banco_select
	(
		"nome,".
		"id_newsletter_layout",
		"newsletter_layout",
		"WHERE status='A'"
		." ORDER BY nome"
	);
	
	$max = count($tabela);
	
	for($i=0;$i<$max;$i++){
		$options[] = $tabela[$i][0];
		$optionsValue[] =  $tabela[$i][1];
		
		if($selecionado){
			if($selecionado == $tabela[$i][1]){
				$optionSelected = $cont;
				
				$layout_txt_bd = banco_select
				(
					"layout",
					"newsletter_layout",
					"WHERE id_newsletter_layout='".$tabela[$i][1]."'"
				);
			}
		} else {
			if($tabela[$i][1] == '1'){
				$optionSelected = $cont;
			}
		}
		
		$cont++;
	}
	
	if(!$optionSelected && $max == 1){
		$optionSelected = 1;
		$display = 'none;';
		$layout_txt = '';
	} else {
		if($selecionado){
			if($selecionado == '1'){
				$display = 'none;';
				$layout_txt = '';
			} else {
				$display = 'block;';
				$layout_txt = $layout_txt_bd[0][0];
			}
		} else {
			$display = 'none;';
			$layout_txt = '';
		}
	}
	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'');
	
	// ===================================== Criação e Edição de Layouts ======================
	
	$layout = '
	<div class="link_hover adicionar" id="layout_add" title="Adicionar um Layout"></div>
	<div class="link_hover excluir" id="layout_del" title="Remover um Layout"></div>
	<input type="text" id="layout_add_txt">
	<input type="button" value="Ok" id="layout_add_btn">
	<div class="clear"></div>
	<div id="layout_cont" style="display:'.$display.'">
		<textarea name="layout" id="layout" class="tinymce">
		'.$layout_txt.'
		</textarea>
	</div>';
	
	return $select . $layout;
}

function identificador_unico($id,$num,$id_newsletter){
	global $_PALAVRAS_RESERVADAS;
	
	$conteudo = banco_select
	(
		"id_newsletter"
		,
		"newsletter",
		"WHERE identificador='".($num ? $id.'-'.$num : $id)."'"
		.($id_newsletter?" AND id_newsletter!='".$id_newsletter."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1,$id_newsletter);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador($id,$id_newsletter = false){
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
		
		return identificador_unico($id,$num,$id_newsletter);
	} else {
		return identificador_unico($id,0,$id_newsletter);
	}
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
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
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
	
	/* if(operacao('conteudo')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=conteudo&id=#id', // link da opção
			'title' => 'Conteúdos d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'lista_mini.png', // caminho da imagem
			'legenda' => 'Conteúdos', // Legenda
		);
	} */
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
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => $width, // OPCIONAL - Tamanho horizontal
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
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
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
	
	$cel_nome = 'topo'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,"#layout#",select_layouts());
	$pagina = paginaTrocaVarValor($pagina,"#nome#",$nome);
	$pagina = paginaTrocaVarValor($pagina,"#imagem_topo#",$imagem_topo);
	$pagina = paginaTrocaVarValor($pagina,"#conteudos#",$conteudos);
	$pagina = paginaTrocaVarValor($pagina,"#conteudos_ids#",$conteudos_ids);
	$pagina = paginaTrocaVarValor($pagina,"#imagem_rodape#",$imagem_rodape);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	$_INTERFACE_OPCAO = 'add';
	
	return interface_layout(parametros_interface());
}

function add_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE;
	
	banco_conectar();
	
	$identificador = $_REQUEST['nome'];
	$identificador = criar_identificador($identificador);
	
	$campo_nome = "id_newsletter_layout"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "identificador"; $post_nome = $campo_nome; 		$campos[] = Array($campo_nome,$identificador);
	$campo_nome = "versao"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'1');
	$campo_nome = "data"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'NOW()',true);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
	$id_newsletter = banco_last_id();
	
	guardar_arquivo($_FILES['imagem_topo'],'imagem','imagem_topo',$id_newsletter);
	guardar_arquivo($_FILES['imagem_rodape'],'imagem','imagem_rodape',$id_newsletter);
	
	if($_REQUEST['conteudos'])$ids_conteudo = explode(',',$_REQUEST['conteudos']);
	
	if($ids_conteudo){
		$caminho_fisico_dest 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."newsletter".$_SYSTEM['SEPARADOR'];
		$caminho_fisico_orig 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'];
		
		$caminho_internet_dest 		= 	"files/newsletter/";
		
		foreach($ids_conteudo as $id_conteudo){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'imagem_grande',
					'imagem_pequena',
				))
				,
				"conteudo",
				"WHERE id_conteudo='".$id_conteudo."'"
			);
			
			$nova_imagem_net = '';
			
			if($resultado[0]['imagem_grande']){
				$imagem_origem = preg_replace('/files\//i', '', $resultado[0]['imagem_grande']);
				
				$original = $caminho_fisico_orig . $imagem_origem;
				$nova_imagem = $caminho_fisico_dest . $imagem_origem;
				$nova_imagem_net = $caminho_internet_dest . $imagem_origem;
				
				if(!is_file($nova_imagem)){
					resize_image($original, $nova_imagem, $_SYSTEM['NEWSLETTER_IMG_WIDTH'], $_SYSTEM['NEWSLETTER_IMG_HEIGHT'],false,false,true);
				}
			} else if($resultado[0]['imagem_pequena']){
				$imagem_origem = preg_replace('/files\//i', '', $resultado[0]['imagem_pequena']);
				
				$original = $caminho_fisico_orig . $imagem_origem;
				$nova_imagem = $caminho_fisico_dest . $imagem_origem;
				$nova_imagem_net = $caminho_internet_dest . $imagem_origem;
				
				if(!is_file($nova_imagem)){
					resize_image($original, $nova_imagem, $_SYSTEM['NEWSLETTER_IMG_WIDTH'], $_SYSTEM['NEWSLETTER_IMG_HEIGHT'],false,false,true);
				}
			}
			
			$campos = null;
			if($nova_imagem_net){$campo_nome = "imagem"; $campo_valor = $nova_imagem_net; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "id_newsletter"; $campo_valor = $id_newsletter; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_conteudo"; $campo_valor = $id_conteudo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"newsletter_conteudo"
			);
		}
	}
	
	if($_POST['id_newsletter_layout'] != '1'){
		banco_update
		(
			"layout='".$_REQUEST['layout']."'",
			"newsletter_layout",
			"WHERE id_newsletter_layout='".$_POST['id_newsletter_layout']."'"
		);
	}
	
	$_INTERFACE['menu_paginas_reiniciar'] = true;
	
	return lista();
}

function editar($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		banco_conectar();
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'id_newsletter_layout',
				'imagem_topo',
				'imagem_rodape',
				'versao',
				'data',
				'identificador',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$campos_guardar = Array(
			'nome' => $tabela[0]['nome'],
			'id_newsletter_layout' => $tabela[0]['id_newsletter_layout'],
			'versao' => $tabela[0]['versao'],
		);
		
		$tabela[0]['imagem_topo'] = '<img src="/' . $_SYSTEM['ROOT'] . $tabela[0]['imagem_topo'] . '?v=' . $tabela[0]['versao'] . '">';
		$tabela[0]['imagem_rodape'] = '<img src="/' . $_SYSTEM['ROOT'] . $tabela[0]['imagem_rodape'] . '?v=' . $tabela[0]['versao'] . '">';
		$tabela[0]['identificador'] = '<a href="http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . 'nl/' . $tabela[0]['identificador'] . '" target="_blank">' . 'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . 'nl/' . $tabela[0]['identificador'] . '</a>';
		
		$pagina = paginaTrocaVarValor($pagina,'#nome#',$tabela[0]['nome']);
		$pagina = paginaTrocaVarValor($pagina,'#versao#',$tabela[0]['versao']);
		$pagina = paginaTrocaVarValor($pagina,'#data#',data_hora_from_datetime_to_text($tabela[0]['data']));
		$pagina = paginaTrocaVarValor($pagina,'#visualizar#',$tabela[0]['identificador']);
		$pagina = paginaTrocaVarValor($pagina,'#imagem_topo#',$tabela[0]['imagem_topo']);
		$pagina = paginaTrocaVarValor($pagina,'#imagem_rodape#',$tabela[0]['imagem_rodape']);
		$pagina = paginaTrocaVarValor($pagina,'#layout#',select_layouts($tabela[0]['id_newsletter_layout']));
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
			))
			,
			"newsletter_conteudo",
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		if($resultado)
		foreach($resultado as $res){
			$res2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'titulo',
				))
				,
				"conteudo",
				"WHERE id_conteudo='".$res['id_conteudo']."'"
			);
			
			$conteudos .= '<div class="cont-conteudos-entry" id="conteudos_'.$res['id_conteudo'].'"><img src="/'.$_SYSTEM['ROOT'].'images/icons/excluir.png" id="excluir_'.$res['id_conteudo'].'" class="cont-conteudos-excluir"> '.$res2[0]['titulo'].'</div>';
			$conteudos_ids .= ($conteudos_ids?',':'') . $res['id_conteudo'];
		}

		$pagina = paginaTrocaVarValor($pagina,'#conteudos#',$conteudos);
		$pagina = paginaTrocaVarValor($pagina,'#conteudos_ids#',$conteudos_ids);
		
		
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
		$campo_nome = "id_newsletter_layout"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";$mudar_identificador = true;}
		$campo_nome = "versao"; 		$editar['tabela'][] = $campo_nome."='".((int)$campos_antes[$campo_nome]+1)."'";
		$campo_nome = "data"; 		$editar['tabela'][] = $campo_nome."=NOW()";
		
		if($mudar_identificador){
			$identificador = $_REQUEST['nome'];
			$identificador = criar_identificador($identificador,$id);
			$campo_nome = "identificador"; 	$editar['tabela'][] = $campo_nome."='".$identificador."'";
		}
		
		$id_newsletter = $id;
		
		guardar_arquivo($_FILES['imagem_topo'],'imagem','imagem_topo',$id_newsletter);
		guardar_arquivo($_FILES['imagem_rodape'],'imagem','imagem_rodape',$id_newsletter);
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
		}
		
		if($_REQUEST['conteudos'])$ids_conteudo = explode(',',$_REQUEST['conteudos']);
		
		$newsletter_conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
			))
			,
			"newsletter_conteudo",
			"WHERE id_newsletter='".$id_newsletter."'"
		);
		
		if($newsletter_conteudo){
			foreach($newsletter_conteudo as $nc){
				$found = false;
				if($ids_conteudo)
				foreach($ids_conteudo as $id_conteudo){
					if($nc['id_conteudo'] == $id_conteudo){
						$found = true;
						break;
					}
				}
				if(!$found){
					banco_delete
					(
						"newsletter_conteudo",
						"WHERE id_newsletter='".$id_newsletter."'"
						." AND id_conteudo='".$nc['id_conteudo']."'"
					);
				}
			}
		}
		
		if($ids_conteudo){
			$caminho_fisico_dest 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."newsletter".$_SYSTEM['SEPARADOR'];
			$caminho_fisico_orig 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'];
			
			$caminho_internet_dest 		= 	"files/newsletter/";
			
			foreach($ids_conteudo as $id_conteudo){
				$found = false;
				if($newsletter_conteudo)
				foreach($newsletter_conteudo as $nc){
					if($nc['id_conteudo'] == $id_conteudo){
						$found = true;
						break;
					}
				
				}
				
				if(!$found){
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'imagem_grande',
							'imagem_pequena',
						))
						,
						"conteudo",
						"WHERE id_conteudo='".$id_conteudo."'"
					);
					
					$nova_imagem_net = '';
					
					if($resultado[0]['imagem_grande']){
						$imagem_origem = preg_replace('/files\//i', '', $resultado[0]['imagem_grande']);
						
						$original = $caminho_fisico_orig . $imagem_origem;
						$nova_imagem = $caminho_fisico_dest . $imagem_origem;
						$nova_imagem_net = $caminho_internet_dest . $imagem_origem;
						
						if(!is_file($nova_imagem)){
							resize_image($original, $nova_imagem, $_SYSTEM['NEWSLETTER_IMG_WIDTH'], $_SYSTEM['NEWSLETTER_IMG_HEIGHT'],false,false,true);
						}
					} else if($resultado[0]['imagem_pequena']){
						$imagem_origem = preg_replace('/files\//i', '', $resultado[0]['imagem_pequena']);
						
						$original = $caminho_fisico_orig . $imagem_origem;
						$nova_imagem = $caminho_fisico_dest . $imagem_origem;
						$nova_imagem_net = $caminho_internet_dest . $imagem_origem;
						
						if(!is_file($nova_imagem)){
							resize_image($original, $nova_imagem, $_SYSTEM['NEWSLETTER_IMG_WIDTH'], $_SYSTEM['NEWSLETTER_IMG_HEIGHT'],false,false,true);
						}
					}
					
					$campos = null;
					if($nova_imagem_net){$campo_nome = "imagem"; $campo_valor = $nova_imagem_net; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
					$campo_nome = "id_newsletter"; $campo_valor = $id_newsletter; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_conteudo"; $campo_valor = $id_conteudo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"newsletter_conteudo"
					);
				}
			}
		}
		
		if($_POST['id_newsletter_layout'] != '1'){
			banco_update
			(
				"layout='".$_REQUEST['layout']."'",
				"newsletter_layout",
				"WHERE id_newsletter_layout='".$_POST['id_newsletter_layout']."'"
			);
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
		
		banco_conectar();
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

function parametros_interface_conteudo(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA_2;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	
	$_LISTA['tabela']['nome']			=	'conteudo';
	$_LISTA['tabela2']['nome']			=	'conteudo_permissao';
	$_LISTA['tabela']['campo']			=	'titulo';
	$_LISTA['tabela']['id']				=	'id_'.'conteudo';
	$_LISTA['tabela']['status']			=	'status';
	$_LISTA['ferramenta']				=	'Conteúdos';
	$_LISTA['ferramenta_unidade']		=	'esse Conteúdo';
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = 'tipo';
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = 'data';
	
	if($_SESSION[$_SYSTEM['ID']."conteudo_arvore"]){
		$titulo_raiz_navegacao = 'Ir a raiz dos conteúdos';
		$titulo_navegacao = 'Ir na lista de conteúdos ';
		$conteudo_arvore = $_SESSION[$_SYSTEM['ID']."conteudo_arvore"];
		
		$informacao_acima = '<div class="lista_header">'.htmlA('?opcao=raiz','raiz',$target,$id,' title="'.$titulo_raiz_navegacao.'"') . ' / ';
		
		if($conteudo_arvore)
		foreach($conteudo_arvore as $filho){
			$count++;
			$informacao_acima .= htmlA('?opcao=lista_conteudo&id='.$filho['id'],$filho['nome'],$target,$id,' title="'.$titulo_navegacao.$filho['nome'].'"') . (count($conteudo_arvore) != $count ? ' / ' : '</div>');
		}
	}
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	$_INTERFACE['informacao_id'] = $informacao_id = $_SESSION[$_SYSTEM['ID']."newsletter_id"];
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
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
	if(operacao('conteudo')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=conteudo&id=#id', // link da opção
			'title' => 'Conteúdos d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'lista_conteudo_40.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Conteúdos', // Nome do menu
		);
	}
	if(operacao('conteudo')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=conteudo_add&id=#id', // link da opção
			'title' => 'Adicionar Conteúdos d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'newMensage.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Adicionar Conteúdos', // Nome do menu
		);
	}
	
	
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => '', // link da opção
		'title' => 'Selecionar '.$_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'selecionar.png', // caminho da imagem
		'legenda' => 'Selecionar', // Legenda
		'input_name' => 'selecionar',
		'input_type' => 'checkbox',
		'input_id' => 'conteudo_'.$informacao_id.'_#id',
		'class' => 'conteudo_select',
		'tabela' => 'newsletter_conteudo as t1,conteudo as t2',
		'tabela_campo' => 't1.id_conteudo',
		'tabela_extra' => "WHERE t1.id_conteudo=t2.id_conteudo AND t1.id_newsletter='".$informacao_id."' AND t2.id_conteudo='#id'",
	);
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Tipo', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'width' => '40', // OPCIONAL - Tamanho horizontal
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'valor_padrao' => 'C', // Valor do campo
		'opcao1' => htmlImage($_HTML['separador'].$_HTML['ICONS'] . 'conteudo_mini.png',$width,$height,'0',$id,$extra), // Valor do campo
		'opcao2' => '<table><tr><td>'.htmlA($_URL . '?opcao=lista_conteudo&id=#id"',htmlImage($_HTML['separador'].$_HTML['ICONS'] . 'lista_mini.png',$width,$height,'0',$id,' title="Lista de Conteúdos"'),$target,$id,$extra)
		.'</td></tr></table>', // Valor do campo
		'class1' => 'texto3', // Valor do campo
		'class2' => 'texto4', // Valor do campo
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Título', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'id', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		/* 'id' => 1, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
		'tabela' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'mudar_valor' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'padrao_se_nao_existe' => true,
		'valor_padrao_se_nao_existe' => '0',
		'campo' => 'hits', // OPCIONAL - Nome do campo da tabela
		'campo_id' => 'id_conteudo', // OPCIONAL - Nome do campo da tabela */
		'align' => 'center', // OPCIONAL - alinhamento horizontal */
		'width' => '60', // OPCIONAL - Tamanho horizontal
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
	
	// ------------------------------ Outra Tabela -------------------------
	
	/* $outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => $_BANCO_PREFIXO.$_LISTA_2['tabela']['nome'], // Nome da tabela
		'campos' => Array(
			'hits',
		), // Array com os nomes dos campos
		'extra' => ' AND status=\'A\'', // Tabela extra
	); */
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => 'Newsletter "'.$_SESSION[$_SYSTEM['ID']."newsletter_nome"].'" - Adicionar Conteúdos', // Título da Informação
		'forcar_informacao_id' => $informacao_id , // Id da Informação
		'forcar_tabela_status_posicao' => $informacao_id , // Id da Informação
		'busca' => operacao('buscar'), // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_titulo' => 'conteúdo', // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Habilitar legenda
		'input_ordem' => false, // Habilitar caixa salvar das ordens
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 3, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_conteudo_pai='".($_SESSION[$_SYSTEM['ID']."conteudo_pai"]?$_SESSION[$_SYSTEM['ID']."conteudo_pai"]:'0')."' ", // Tabela extra
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
		
	);
	
	return $parametros;
}

function lista_conteudo(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_CONEXAO_BANCO;
	
	$_LISTA['tabela']['nome']			=	'conteudo';
	$_LISTA['tabela2']['nome']			=	'conteudo_permissao';
	$_LISTA['tabela']['campo']			=	'titulo';
	$_LISTA['tabela']['id']				=	'id_'.'conteudo';
	$_LISTA['tabela']['status']			=	'status';
	$_LISTA['ferramenta']				=	'Conteúdos';
	$_LISTA['ferramenta_unidade']		=	'esse Conteúdo';
	
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	$conteudo_arvore = $_SESSION[$_SYSTEM['ID']."conteudo_arvore"];
	
	if($id){
		if($conteudo_arvore)
		foreach($conteudo_arvore as $filho){
			$arvore_aux[] = $filho;
			if($id == $filho['id']){
				$_SESSION[$_SYSTEM['ID']."conteudo_pai"]		= 	$filho['id'];
				$_SESSION[$_SYSTEM['ID']."titulo_pai"] 		= 	$filho['nome'];
				//$_LISTA['ferramenta']						=	$filho['nome'];
				$_SESSION[$_SYSTEM['ID']."conteudo_arvore"] 	= 	$arvore_aux;
				$found_filho = true;
				break;
			}
		}
		
		if(!$found_filho){
			if(!$_CONEXAO_BANCO)banco_conectar();
			
			$tabela = banco_select_name
			(
				banco_campos_virgulas(Array(
					'tipo',
					'titulo',
					'identificador',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			
			if($tabela[0]['tipo'] == 'T'){
				return editar_conteudo();
			} else {
				$_SESSION[$_SYSTEM['ID']."conteudo_pai"] = $id;
				$_SESSION[$_SYSTEM['ID']."titulo_pai"] = $tabela[0]['titulo'];
				//$_LISTA['ferramenta']			=	$_SESSION[$_SYSTEM['ID']."titulo_pai"];
				
				$conteudo_arvore[] = Array(
					'id' => $id,
					'nome' => $tabela[0]['titulo'],
					'identificador' => $tabela[0]['identificador'],
				);
				
				$_SESSION[$_SYSTEM['ID']."conteudo_arvore"] = $conteudo_arvore;
			}
		}
	}
	
	return conteudo_add();
}

function raiz_conteudo(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	
	$_SESSION[$_SYSTEM['ID']."conteudo_arvore"] = null;
	$_SESSION[$_SYSTEM['ID']."conteudo_pai"] = 0;
	$_SESSION[$_SYSTEM['ID']."titulo_pai"] = null;
	//$_LISTA['ferramenta']			=	'Conteúdos';
	
	return conteudo_add();
}

function conteudo($nao_mudar_id = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;	
	
	if($nao_mudar_id){
		$id = $_SESSION[$_SYSTEM['ID']."newsletter_id"];
	} else {
		if($_REQUEST["id"])						$id = $_REQUEST["id"];
	}
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- conteudo < -->','<!-- conteudo > -->');
		
		$_SESSION[$_SYSTEM['ID']."newsletter_id"] = $id;
		
		global $_CONEXAO_BANCO;
	
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$newsletter = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
			))
			,
			'newsletter',
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		$_SESSION[$_SYSTEM['ID']."newsletter_nome"] = $newsletter[0]['nome'];
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.titulo',
				't2.id_conteudo',
			))
			,
			'newsletter_conteudo as t1,conteudo as t2',
			"WHERE t1.".$_LISTA['tabela']['id']."='".$id."'"
			." AND t1.id_conteudo=t2.id_conteudo"
			." AND t2.status='A'"
		);
		
		if($tabela){
			$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			foreach($tabela as $res){
				$cel_nome = 'cel';
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#id#",$res['t2.id_conteudo']);
				$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$res['t2.titulo']);
				
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
			
			$_INTERFACE_OPCAO = 'editar';
			$_INTERFACE['local'] = 'conteudo';
			$_INTERFACE['informacao_titulo'] = 'Newsletter "'.$_SESSION[$_SYSTEM['ID']."newsletter_nome"].'" - Lista Conteúdos';
			$_INTERFACE['informacao_tipo'] = $tipo;
			$_INTERFACE['informacao_id'] = $id;
			$_INTERFACE['inclusao'] = $pagina;
		
			return interface_layout(parametros_interface());
		} else {
			return conteudo_add();
		}
	} else
		return lista();
}

function conteudo_add(){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface_conteudo());
}

function conteudo_excluir(){
	global $_SYSTEM;
	
	$id_newsletter = $_SESSION[$_SYSTEM['ID']."newsletter_id"];
	$id_conteudo = $_REQUEST['id'];
	
	if($id_conteudo){
		global $_CONEXAO_BANCO;
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		banco_delete
		(
			"newsletter_conteudo",
			"WHERE id_conteudo='".$id_conteudo."'"
			." AND id_newsletter='".$id_newsletter."'"
		);
	}
	
	return conteudo(true);
}

function conteudo_img(){

	return conteudo(true);
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."newsletter".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/newsletter/";
	
	if(
		$uploaded['size'] != 0
	){
		switch($tipo){
			case 'imagem':
				if
				(
					$uploaded['type'] == mime_types("jpe") ||
					$uploaded['type'] == mime_types("jpeg") ||
					$uploaded['type'] == mime_types("jpg") ||
					$uploaded['type'] == mime_types("pjpeg") ||
					$uploaded['type'] == mime_types("png") ||
					$uploaded['type'] == mime_types("x-png") ||
					$uploaded['type'] == mime_types("swf") ||
					$uploaded['type'] == mime_types("gif")
				){
					$cadastrar = true;
				}
			break;
			case 'musica':
				if
				(
					$uploaded['type'] == mime_types("mp3") ||
					$uploaded['type'] == mime_types("mp3_2")
				){
					$cadastrar = true;
				}
			break;
			case 'video':
				if
				(
					$uploaded['type'] == mime_types("flv") ||
					$uploaded['type'] == mime_types("mp4")
				){
					$cadastrar = true;
				}
			break;
		}
	}
	
	if($cadastrar){
		if
		(
			$uploaded['type'] == mime_types("jpe") ||
			$uploaded['type'] == mime_types("jpeg") ||
			$uploaded['type'] == mime_types("pjpeg") ||
			$uploaded['type'] == mime_types("jpg")
		){
			$extensao = "jpg";
		} else if
		(
			$uploaded['type'] == mime_types("png") ||
			$uploaded['type'] == mime_types("x-png") 
		){
			$extensao = "png";
		} else if
		(
			$uploaded['type'] == mime_types("gif")
		){
			$extensao = "gif";
		} else if
		(
			$uploaded['type'] == mime_types("swf")
		){
			$extensao = "swf";
		} else if
		(
			$uploaded['type'] == mime_types("mp3") ||
			$uploaded['type'] == mime_types("mp3_2")
		){
			$extensao = "mp3";
		} else if
		(
			$uploaded['type'] == mime_types("flv")
		){
			$extensao = "flv";
		}  else if
		(
			$uploaded['type'] == mime_types("mp4")
		){
			$extensao = "mp4";
		} 
		
		$nome_arquivo = $campo . $id_tabela . "." . $extensao;
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			$original = $caminho_fisico . $nome_arquivo;
			resize_image($original, $original, 660, 9999,false,false,false);
		}
		
		banco_update
		(
			$campo."='".$caminho_internet.$nome_arquivo."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id_tabela."'"
		);
	}
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
		
		$dom = new DOMDocument("1.0", "UTF-8");
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		
		$mp3player = $dom->appendChild(new DOMElement('mp3player'));
		
		$mp3 = $mp3player->appendChild(new DOMElement('mp3'));
		$attr = $mp3->setAttributeNode(new DOMAttr('id', 1));
		
		$title = $mp3->appendChild(new DOMElement('title',$conteudo[0]['titulo']));
		$artist = $mp3->appendChild(new DOMElement('artist',$conteudo[0]['sub_titulo']));
		$url = $mp3->appendChild(new DOMElement('url',$_HTML['separador'].$conteudo[0]['musica']));
		
		header("Content-Type: text/xml");
		echo $dom->saveXML();
	}
}

function ajax(){
	global $_SYSTEM;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	if($_REQUEST['conteudo_select']){
		$id_conteudo = $_REQUEST['id_conteudo'];
		$id_newsletter = $_REQUEST['id_newsletter'];
		$checked = $_REQUEST['checked'];
		
		global $_CONEXAO_BANCO;
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
	
		if($checked){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
				))
				,
				"newsletter_conteudo",
				"WHERE id_conteudo='".$id_conteudo."'"
				." AND id_newsletter='".$id_newsletter."'"
			);
			
			if($resultado){
				banco_delete
				(
					"newsletter_conteudo",
					"WHERE id_conteudo='".$id_conteudo."'"
					." AND id_newsletter='".$id_newsletter."'"
				);
			}
			
			$saida = Array(
				'checked' => 'sim',
			);
		} else {
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo',
				))
				,
				"newsletter_conteudo",
				"WHERE id_conteudo='".$id_conteudo."'"
				." AND id_newsletter='".$id_newsletter."'"
			);
			
			if(!$resultado){
				$campo_nome = "id_conteudo"; $campo_valor = $id_conteudo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_newsletter"; $campo_valor = $id_newsletter; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				
				banco_insert_name
				(
					$campos,
					"newsletter_conteudo"
				);
			}
			
			$saida = Array(
				'checked' => 'nao',
			);
		}
		
		if($connect_db)banco_fechar_conexao();
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['layout_change']){
		$query = $_REQUEST["layout_change"];
		if(!$query) return;

		banco_conectar();
		
		$newsletter_layout = banco_select
		(
			"layout",
			"newsletter_layout",
			"WHERE id_newsletter_layout='".$query."'"
		);
		
		$layout = $newsletter_layout[0]['layout'];
		
		banco_fechar_conexao();

		$saida = Array(
			'layout' => $layout,
		);
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['layout_add']){
		$query = $_REQUEST["layout_add"];
		if(!$query) return;

		banco_conectar();
		
		$newsletter_layout = banco_select
		(
			"layout",
			"newsletter_layout",
			"WHERE id_newsletter_layout='1'"
		);
		
		$layout = $newsletter_layout[0]['layout'];
		
		$campos = null;
	
		$campo_nome = "nome"; $campo_valor = $query; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "layout"; $campo_valor = $layout; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"newsletter_layout"
		);
		
		$id = banco_last_id();
		
		banco_fechar_conexao();

		$saida = Array(
			'layout' => $layout,
			'id' => $id,
		);
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['layout_del']){
		$query = $_REQUEST["layout_del"];
		if(!$query) return;

		banco_update
		(
			"id_newsletter_layout='1'",
			"newsletter",
			"WHERE id_newsletter_layout='".$query."'"
		);
		
		banco_update
		(
			"status='D'",
			"newsletter_layout",
			"WHERE id_newsletter_layout='".$query."'"
		);

		$saida = Array(
			'ok' => true,
		);
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['query_id'] == 'conteudos_txt'){
		$query = $_REQUEST["query"];
		if(!$query) return;

		banco_conectar();
		
		$resultado = banco_select
		(
			"id_conteudo,titulo",
			"conteudo",
			"WHERE UCASE(titulo) LIKE UCASE('%" . $query . "%') AND status='A'"
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
	
	return $saida;
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
			//case 'raiz':						$saida = (operacao('conteudo') ? raiz_conteudo() : lista());break;
			//case 'lista_conteudo':				$saida = (operacao('conteudo') ? lista_conteudo() : lista());break;
			//case 'conteudo':					$saida = (operacao('conteudo') ? conteudo() : lista());break;
			//case 'conteudo_add':				$saida = (operacao('conteudo') ? conteudo_add() : lista());break;
			//case 'conteudo_excluir':			$saida = (operacao('conteudo') ? conteudo_excluir() : lista());break;
			//case 'conteudo_img':				$saida = (operacao('conteudo') ? conteudo_img() : lista());break;
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