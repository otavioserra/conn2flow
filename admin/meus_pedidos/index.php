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
$_LOCAL_ID					=	"meus_pedidos";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Pedidos.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'pedidos';
$_LISTA['tabela']['campo']			=	'codigo';
$_LISTA['tabela']['id']				=	'id_'.'pedidos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Meus Pedidos';
$_LISTA['ferramenta_unidade']		=	'esse Pedido';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

function alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	global $_MENSAGEM_ERRO;
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_MOBILE;
	global $_VARIAVEIS_JS;
	
	switch ($nAlerta){
		default:	$mensSaida	=	$nAlerta;
	}

	$_ALERTA = $mensSaida;
	
	if($_MOBILE){
		$_VARIAVEIS_JS['alerta'] = $_ALERTA;
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
	
	$usuario = $_SESSION[$_SYSTEM['ID'].'usuario'];
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = 't1.'.$_LISTA['tabela']['status'];
	$tabela_campos[] = 't1.'.$_LISTA['tabela']['id'];
	$tabela_campos[] = 't1.'.$_LISTA['tabela']['campo'];
	$tabela_campos[] = 't1.'.'data';
	$tabela_campos[] = 't1.'.'status';
	$tabela_campos[] = 't1.'.'protocolo_baixa';
	$tabela_campos[] = 't1.'.'data_baixa';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
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
	/* if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'entrar.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Adicionar', // Nome do menu
		);
	} */
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Visualizar ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'arquivo-temporario.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Visualizar', // Nome do menu
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=voucher&id=#id', // link da opção
			'title' => 'Imprimir Voucher d' . $_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'imprimir.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Imprimir Voucher', // Nome do menu
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=pagar&id=#id', // link da opção
			'title' => 'Pagar ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'pagar.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Pagar', // Nome do menu
		);
		/* if(operacao('bloquear')){
			$menu_principal[] = Array( // Opção: Bloquear
				'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
				'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo_grande_2.png', // caminho da imagem
				'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado_grande_2.png', // caminho da imagem
				'bloquear' => true, // Se eh botão de bloqueio
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Ativar/Desativar', // Nome do menu
			);
		} */
	}
	
	//if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'textos.png', // caminho da imagem
			'legenda' => 'Ver', // Legenda
		);
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=voucher&id=#id', // link da opção
			'title' => 'Imprimir Voucher d' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'imprimir-mini.png', // caminho da imagem
			'legenda' => 'Imprimir Voucher', // Legenda
		);
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=pagar&id=#id', // link da opção
			'title' => 'Pagar ' . $_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'pagar-mini.png', // caminho da imagem
			'legenda' => 'Pagar', // Legenda
		);
	//}
	/* if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=editar&id=#id', // link da opção
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'editar.png', // caminho da imagem
			'legenda' => 'Editar', // Legenda
		);
	}
	if(operacao('bloquear')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
			'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo.png', // caminho da imagem
			'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado.png', // caminho da imagem
			'bloquear' => true, // Se eh botão de bloqueio
			'legenda' => 'Ativar/Desativar', // Legenda
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
	} */
	
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
		'campo' => 'Código', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'data' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => 'Não Definido', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => Array(
			'F' => '<span style="color:brown;">Finalizado</span>',
			'A' => '<span style="color:green;">Ativo</span>',
			'B' => '<span style="color:red;">Bloqueado</span>',
			'D' => '<span style="color:red;">Deletado</span>',
			'N' => '<span style="color:blue;">Novo</span>',
			'P' => '<span style="color:blue;">Pagamento</span>',
		),
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Protocolo Baixa', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data Baixa', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'data' => true,
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
		'tabela_nome' => $_LISTA['tabela']['nome']." as t1,usuario_pedidos as t2", // Nome da tabela
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE t1.".$_LISTA['tabela']['status']."!='D' AND t1.id_pedidos=t2.id_pedidos AND t2.id_usuario='".$usuario['id_usuario']."'", // Tabela extra
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
	
	$pagina = paginaTrocaVarValor($pagina,"#nome",$nome);
	$pagina = paginaTrocaVarValor($pagina,"#descricao",$descricao);
	
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
	
	$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "descricao"; $post_nome = $campo_nome; 			if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
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
				'codigo',
				'senha',
				'data',
				'protocolo_baixa',
				'id_usuario_baixa',
				'data_baixa',
				'status',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.usuario',
				't2.id_usuario',
			))
			,
			"usuario_pedidos as t1,usuario as t2",
			"WHERE t1.id_pedidos='".$id."'"
			." AND t1.id_usuario=t2.id_usuario"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		/* $campos_guardar = Array(
			'codigo' => $tabela[0]['codigo'],
			'descricao' => $tabela[0]['descricao'],
		); */
		
		$status_mudar = Array(
			'F' => '<span style="color:brown;">Finalizado</span>',
			'A' => '<span style="color:green;">Ativo</span>',
			'B' => '<span style="color:red;">Bloqueado</span>',
			'D' => '<span style="color:red;">Deletado</span>',
			'N' => '<span style="color:blue;">Novo</span>',
			'P' => '<span style="color:blue;">Pagamento</span>',
		);
		
		foreach($status_mudar as $chave => $valor){
			if($tabela[0]['status'] == $chave){
				$tabela[0]['status'] = $valor;
				break;
			}
		}
		
		$usuario = banco_select_name
		(
			banco_campos_virgulas(Array(
				'usuario',
			))
			,
			'usuario',
			"WHERE id_usuario='".$tabela[0]['id_usuario_baixa']."'"
		);
		
		$pagina = paginaTrocaVarValor($pagina,'#usuario_pedido#',"<a href=\"../usuarios/?opcao=editar&id=".$usuario_pedidos[0]['t2.id_usuario']."\"><b>".$usuario_pedidos[0]['t2.usuario']."</b></a>");
		$pagina = paginaTrocaVarValor($pagina,'#codigo#',$tabela[0]['codigo']);
		$pagina = paginaTrocaVarValor($pagina,'#status#',$tabela[0]['status']);
		$pagina = paginaTrocaVarValor($pagina,'#data#',data_hora_from_datetime_to_text($tabela[0]['data']));
		$pagina = paginaTrocaVarValor($pagina,'#protocolo#',$tabela[0]['protocolo_baixa']);
		$pagina = paginaTrocaVarValor($pagina,'#data_baixa#',data_hora_from_datetime_to_text($tabela[0]['data_baixa']));
		$pagina = paginaTrocaVarValor($pagina,'#usuario#',"<a href=\"../usuarios/?opcao=editar&id=".$tabela[0]['id_usuario_baixa']."\"><b>".$usuario[0]['usuario']."</b></a>");
		
		$cel_nome = 'servicos'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.codigo',
				't1.senha',
				't1.protocolo_baixa',
				't1.id_usuario_baixa',
				't1.data_baixa',
				't2.nome',
			))
			,
			'pedidos_servicos as t1,servicos as t2',
			"WHERE t1.id_pedidos='".$id."'"
			." AND t1.id_servicos=t2.id_servicos"
			." ORDER BY t2.nome ASC"
		);
		
		if($pedidos_servicos)
		foreach($pedidos_servicos as $res){
			$cel_nome = 'servicos';
			$cel_aux = $cel[$cel_nome];
			
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'usuario',
				))
				,
				'usuario',
				"WHERE id_usuario='".$res['t1.id_usuario_baixa']."'"
			);
			
			$cel_aux = modelo_var_troca($cel_aux,"#nome#",$res['t2.nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#codigo#",$res['t1.codigo']);
			$cel_aux = modelo_var_troca($cel_aux,"#protocolo_baixa#",$res['t1.protocolo_baixa']);
			$cel_aux = modelo_var_troca($cel_aux,"#data_baixa#",data_hora_from_datetime_to_text($res['t1.data_baixa']));
			$cel_aux = modelo_var_troca($cel_aux,"#usuario_baixa#","<a href=\"../usuarios/?opcao=editar&id=".$tabela[0]['id_usuario_baixa']."\"><b>".$usuario[0]['usuario']."</b></a>");
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Modificar";
		$botao = "Gravar";
		$opcao = "editar_base";
		
		$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
		$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
		$pagina = paginaTrocaVarValor($pagina,"#id",$id);
		
		//if(!operacao('editar'))
		$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		
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
		$campo_nome = "nome"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "descricao"; if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
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

function voucher(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_VARIAVEIS_JS;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"usuario_pedidos",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND id_pedidos='".$id."'"
	);
	
	if($usuario_pedidos){
		$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
		
		// ============================== Pedido Atual
		
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
				'data',
				'presente',
				'de',
				'para',
				'mensagem',
				'codigo',
				'senha',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND status='A'"
		);
		
		if($pedido){
			$pedido = $pedido[0];
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_titulo']){
					$voucher_titulo = $_PROJETO['ecommerce']['voucher_titulo'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_layout']){
					$voucher = $_PROJETO['ecommerce']['voucher_layout'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_menu_admin']){
					$voucher_menu = $_PROJETO['ecommerce']['voucher_menu_admin'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_topo']){
					$voucher_topo = $_PROJETO['ecommerce']['voucher_topo'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_base']){
					$voucher_base = $_PROJETO['ecommerce']['voucher_base'];
				}
			}
			
			if(!$voucher_titulo){
				$voucher_titulo = '
<h1>Voucher</h1>';
			}
			if(!$voucher){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher < -->','<!-- voucher > -->');
				
				$voucher = $pagina;
			}
			if(!$voucher_menu){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_menu_admin < -->','<!-- voucher_menu_admin > -->');
				
				$voucher_menu = $pagina;
			}
			if(!$voucher_presente){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_presente_admin < -->','<!-- voucher_presente_admin > -->');
				
				$voucher_presente = $pagina;
			}
			if(!$voucher_base){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_base < -->','<!-- voucher_base > -->');
				
				$voucher_base = $pagina;
			}
			
			$data_full = $pedido['data'];
			$data_arr = explode(' ',$data_full);
			
			$voucher_base = modelo_var_troca($voucher_base,"#data-expiracao#",date("d/m/Y",strtotime($data_arr[0] . " + ".$_ECOMMERCE['pedido_validade']." day")));
			
			$voucher_menu = modelo_var_troca($voucher_menu,"#lista-pedidos#",$lista_pedidos);
			$voucher_menu = modelo_var_troca($voucher_menu,"#presente-value#",($pedido['presente'] ? 'Para Você' : 'Para Presente'));
			$voucher_menu = modelo_var_troca($voucher_menu,"#presente-flag#",($pedido['presente'] ? '2' : '1'));
			
			$voucher_presente = modelo_var_troca($voucher_presente,"#de#",$pedido['de']);
			$voucher_presente = modelo_var_troca($voucher_presente,"#para#",$pedido['para']);
			$voucher_presente = modelo_var_troca($voucher_presente,"#mensagem#",$pedido['mensagem']);
			$voucher_presente = modelo_var_troca($voucher_presente,"#id#",$id);
			
			$voucher = modelo_var_troca($voucher,"#voucher-topo#",$voucher_topo);
			$voucher = modelo_var_troca($voucher,"#voucher-base#",$voucher_base);
			
			$voucher = modelo_var_troca($voucher,"#voucher-codigo#",$pedido['codigo']);
			$voucher = modelo_var_troca($voucher,"#voucher-senha#",$pedido['senha']);
			$voucher = modelo_var_troca($voucher,"#voucher-de#",$pedido['de']);
			$voucher = modelo_var_troca($voucher,"#voucher-para#",$pedido['para']);
			$voucher = modelo_var_troca($voucher,"#voucher-mensagem#",$pedido['mensagem']);
			
			$cel_nome = 'lista-servicos'; $cel[$cel_nome] = modelo_tag_val($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.senha',
					't1.quantidade',
					't2.nome',
				))
				,
				"pedidos_servicos as t1,servicos as t2",
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." AND t1.status='A'"
			);
			
			if($pedidos_servicos)
			foreach($pedidos_servicos as $pedido_servico){
				$cel_nome = 'lista-servicos';
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#servico-nome#",$pedido_servico['t2.nome']);
				$cel_aux = modelo_var_troca($cel_aux,"#servico-quant#",$pedido_servico['t1.quantidade']);
				$cel_aux = modelo_var_troca($cel_aux,"#servico-codigo#",$pedido_servico['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#servico-senha#",$pedido_servico['t1.senha']);
				
				$voucher = modelo_var_in($voucher,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' -->','');
			
			$voucher_impressao = $voucher;
			
			if(!$pedido['presente']){
				$cel_nome = 'de-para'; $voucher_impressao = modelo_tag_in($voucher_impressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			}
			
			$_SESSION[$_SYSTEM['ID']."versao-impressao"] = $voucher_impressao;
			
			$pagina = $voucher_menu.$voucher.$voucher_presente;
			
			$in_titulo = "Voucher";
			$botao = "Gravar";
			$opcao = "editar_base";
			
			$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
			$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
			$pagina = paginaTrocaVarValor($pagina,"#id",$id);
			
			//if(!operacao('editar'))
			$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			$_INTERFACE_OPCAO = 'editar';
			$_INTERFACE['local'] = 'conteudo';
			$_INTERFACE['informacao_titulo'] = $in_titulo;
			$_INTERFACE['informacao_tipo'] = $tipo;
			$_INTERFACE['informacao_id'] = $id;
			$_INTERFACE['inclusao'] = $pagina;
		
			return interface_layout(parametros_interface());
		} else {
			$pedido = banco_select_name
			(
				banco_campos_virgulas(Array(
					'status',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
			);
			
			$status_mudar = Array(
				'F' => '<span style="color:brown;">Finalizado</span>',
				'A' => '<span style="color:green;">Ativo</span>',
				'B' => '<span style="color:red;">Bloqueado</span>',
				'D' => '<span style="color:red;">Deletado</span>',
				'N' => '<span style="color:blue;">Novo</span>',
				'P' => '<span style="color:blue;">Pagamento</span>',
			);
			
			alerta('<p>O seu pedido está com status <b>'.$status_mudar[$pedido[0]['status']].'</b> e portanto não é possível imprimir o voucher do referido pedido.</p>
			<p>Apenas pedidos com o status <b>'.$status_mudar['A'].'</b> é permitido imprimir o voucher.</p>');
			
			return lista();
		}
	} else {
		alerta('<p style="color:red;">Você não tem pedidos cadastrados</p>');
		
		return lista();
	}
}

function voucher_form_presente(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"usuario_pedidos",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND id_pedidos='".$id."'"
		);
		
		if($usuario_pedidos){
			$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
			
			$campo_nome = "de"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
			$campo_nome = "para"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
			$campo_nome = "mensagem"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
			}
		}
		
		return voucher();
	} else {
		return lista();
	}
}

function pagar(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"usuario_pedidos",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND id_pedidos='".$id."'"
		);
		
		if($usuario_pedidos){
			$pedido = banco_select_name
			(
				banco_campos_virgulas(Array(
					'codigo',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$id."'"
				." AND status='N'"
			);
			
			if($pedido){
				$pedido_num = $pedido[0]['codigo'];
				
				if($_PROJETO['ecommerce']){
					if($_PROJETO['ecommerce']['pagamento_admin_layout']){
						$layout = $_PROJETO['ecommerce']['pagamento_admin_layout'];
					}
				}
				
				if(!$layout){
					$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
					$pagina = modelo_tag_val($modelo,'<!-- pagamento_admin < -->','<!-- pagamento_admin > -->');
					
					$layout = $pagina;
				}
				
				$layout = modelo_var_troca_tudo($layout,"#id#",$id);
				
				$in_titulo = "Pagar";
				$botao = "Gravar";
				$opcao = "editar_base";
				
				$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
				$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
				$pagina = paginaTrocaVarValor($pagina,"#id",$id);
				
				//if(!operacao('editar'))
				$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				
				$_INTERFACE_OPCAO = 'editar';
				$_INTERFACE['local'] = 'conteudo';
				$_INTERFACE['informacao_titulo'] = $in_titulo;
				$_INTERFACE['informacao_tipo'] = $tipo;
				$_INTERFACE['informacao_id'] = $id;
				$_INTERFACE['inclusao'] = $layout;
				
				return interface_layout(parametros_interface());
			} else {
				alerta('<p style="color:red;">Não é possível pagar novamente por esse pedido.</p><p>Este pedido já teve pagamento transferido para os meios de pagamentos disponíveis.</p>');
				
				return lista();
			}
		} else {
			alerta('<p style="color:red;">Este pedido não pertence ao seu usuário</p>');
			
			return lista();
		}
	} else
		return lista();
}

function pagseguro_pagar(){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.codigo',
				't1.id_pedidos',
			))
			,
			"usuario_pedidos as t1,pedidos as t2",
			"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
			." AND t1.id_pedidos=t2.id_pedidos"
			." AND t1.id_pedidos='".$id."'"
		);
		
		if($usuario_pedidos){
			$codigo_referencia = $usuario_pedidos[0]['t2.codigo'];
			$id_pedidos = $usuario_pedidos[0]['t1.id_pedidos'];
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.quantidade',
					't2.nome',
					't2.preco',
				))
				,
				'pedidos_servicos as t1,servicos as t2',
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." ORDER BY t2.nome ASC"
			);
			
			if($pedidos_servicos){
				include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."pagseguro-2.2.1".$_SYSTEM['SEPARADOR']."source".$_SYSTEM['SEPARADOR']."PagSeguroLibrary".$_SYSTEM['SEPARADOR']."PagSeguroLibrary.php");
				
				$paymentRequest = new PagSeguroPaymentRequest();
				
				foreach($pedidos_servicos as $res){	
					$paymentRequest->addItem($res['t1.codigo'], utf8_encode($res['t2.nome']), (int)$res['t1.quantidade'],number_format((float)$res['t2.preco'], 2, '.', ''));
				}
				
				$paymentRequest->setCurrency("BRL");
				$paymentRequest->setShippingType(3);
				//$paymentRequest->setRedirectURL($url_redirect);
				$paymentRequest->setReference($codigo_referencia);
				
				$credentials = PagSeguroConfig::getAccountCredentials();
				
				$url = $paymentRequest->register($credentials);
				
				header("Location: ".$url);
			} else {
				alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: pagseguro_pagar 1.</p>');
				
				return lista();
			}
		} else {
			alerta('<p>Você ainda não tem pedidos cadastrados para fazer pagamentos.</p>');
			
			return lista();
		}
	} else
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
	
	if($_REQUEST['opcao'] == 'voucher-presente'){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"usuario_pedidos",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND pedido_atual IS NOT NULL"
		);
		
		if($usuario_pedidos){
			$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
			
			if($_REQUEST['flag'] == '1'){
				banco_update
				(
					"presente='1'",
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
			} else {
				banco_update
				(
					"presente=NULL",
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
			}
		}
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
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'voucher':						$saida = voucher(); break;
			case 'voucher-form-presente':		$saida = voucher_form_presente(); break;
			case 'pagar':						$saida = pagar(); break;
			case 'pagseguro_pagar':				$saida = pagseguro_pagar(); break;
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