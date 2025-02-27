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
$_LOCAL_ID					=	"mensagens";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Mensagens.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	$_LOCAL_ID;
$_LISTA['tabela']['campo']			=	'assunto';
$_LISTA['tabela']['id']				=	'id_'.$_LOCAL_ID;
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Mensagens';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']					=	$_CAMINHO_RELATIVO_RAIZ;

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
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_usuario = $usuario['id_usuario'];
	
	if(!$_SESSION[$_SYSTEM['ID'].'caixa'])	$_SESSION[$_SYSTEM['ID'].'caixa'] = 'entrada';
	if($_REQUEST['caixa']){
		switch($_REQUEST['caixa']){
			case 'entrada': $_SESSION[$_SYSTEM['ID'].'caixa'] = 'entrada'; break;
			case 'saida': $_SESSION[$_SYSTEM['ID'].'caixa'] = 'saida'; break;
		}
	}
	
	
	switch($_SESSION[$_SYSTEM['ID'].'caixa']){
		case 'entrada':
			banco_conectar();
			$mensagens_usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_mensagens',
				))
				,
				"mensagens_usuario",
				"LIMIT 1"
			);
			$mensagens_grupo = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_mensagens',
				))
				,
				"mensagens_grupo",
				"LIMIT 1"
			);
			$usuario_grupo = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_grupo',
				))
				,
				"usuario_grupo",
				"LIMIT 1"
			);
			banco_fechar_conexao();
			
			$tabela_order = 't1.'.$_LISTA['tabela']['id'].' DESC';
			//$tabela_order = $_LISTA['tabela']['campo'].' ASC';
			
			$tabela_campos[] = 't1.'.$_LISTA['tabela']['id'];
			$tabela_campos[] = 't1.'.'status';
			$tabela_campos[] = 't1.'.$_LISTA['tabela']['campo'];
			$tabela_campos[] = 't1.'.'id_usuario';
			$tabela_campos[] = 't1.'.'data';
			
			$tabela['nome'] = 
				"mensagens AS t1".
				($mensagens_usuario? ",mensagens_usuario AS t2":"").
				($mensagens_grupo && $usuario_grupo? ",mensagens_grupo AS t3,usuario_grupo AS t4":"");
			$tabela['extra'] = 
				"WHERE t1.".$_LISTA['tabela']['status']."!='D'".
				($mensagens_usuario || ( $mensagens_grupo && $usuario_grupo ) ? " AND 
				(":"")."
					".($mensagens_usuario? "( 
						t2.id_usuario='".$id_usuario."'
						 AND 
						t1.id_mensagens=t2.id_mensagens
					)":"").
					($mensagens_usuario && $mensagens_grupo && $usuario_grupo?" OR ":"").
					($mensagens_grupo && $usuario_grupo? "(
						t4.id_usuario='".$id_usuario."'
						 AND 
						t3.id_grupo=t4.id_grupo
						 AND 
						t1.id_mensagens=t3.id_mensagens
					)":"").
				($mensagens_usuario || ( $mensagens_grupo && $usuario_grupo )? ")":"")." GROUP BY t1.id_mensagens "
				;
			$tabela['order'] = $tabela_order;
			$tabela['campos'] = $tabela_campos;
			$caixa_titulo = 'Caixa de Entrada';
		break;
		case 'saida':
			$tabela_order = $_LISTA['tabela']['id'].' DESC';
			//$tabela_order = $_LISTA['tabela']['campo'].' ASC';
			
			$tabela_campos[] = $_LISTA['tabela']['id'];
			$tabela_campos[] = 'status';
			$tabela_campos[] = $_LISTA['tabela']['campo'];
			$tabela_campos[] = 'para';
			$tabela_campos[] = 'data';
	
			$tabela['nome'] = $_LISTA['tabela']['nome'];
			$tabela['extra'] = 
				"WHERE ".$_LISTA['tabela']['status']."!='D'".
				" AND id_usuario='".$id_usuario."'"
				;
			$tabela['order'] = $tabela_order;
			$tabela['campos'] = $tabela_campos;
			$caixa_titulo = 'Caixa de Saída';
		break;
	}
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? $caixa_titulo : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'home_mini.png', // caminho da imagem
		'name' => 'Início', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?caixa=entrada', // link da opção
		'title' => 'Caixa de Entrada', // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'income.png', // caminho da imagem
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
		'name' => 'Caixa de Entrada', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?caixa=saida', // link da opção
		'title' => 'Caixa de Saída', // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'outcome.png', // caminho da imagem
		'width' => '40', // OPCIONAL - tamanho x da imagem
		'height' => '40', // OPCIONAL - y da imagem
		'name' => 'Caixa de Saída', // Nome do menu
	);
	if(operacao('enviar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Nova Mensagem', // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'newMensage.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
			'name' => 'Nova Mensagem', // Nome do menu
		);
	}
	if($_INTERFACE_OPCAO == 'editar'){
		$informacao_id = $_INTERFACE['informacao_id'];
	}
	
	if(operacao('ver')){
		$menu_opcoes[] = Array( // Opção: Editar
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver Mensagem', // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'operacoes.png', // caminho da imagem
			'legenda' => 'Ver', // Legenda
		);
	}
	
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
		'campo' => 'Assunto', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	if($_SESSION[$_SYSTEM['ID'].'caixa'] == 'entrada'){
		$header_campos[] = Array( // array com todos os campos do cabeçalho
			'campo' => 'De', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'id' => 1, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
			'tabela' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
			'mudar_valor' => 1, // OPCIONAL - Se faz parte de outra tabela de número desse valor
			'campo' => 'nome', // OPCIONAL - Nome do campo da tabela
			'campo_id' => 'id_usuario', // OPCIONAL - Nome do campo da tabela
			'campo_de' => true, // OPCIONAL - Nome do campo da tabela
			'align' => $valor, // OPCIONAL - alinhamento horizontal
		);
	} else {
		$header_campos[] = Array( // array com todos os campos do cabeçalho
			'campo' => 'Para', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$campos[] = Array( // OPCIONAL - array com os dados dos campos
			'align' => $valor, // OPCIONAL - alinhamento horizontal
		);
	}
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'width' => '120',
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data_hora' => true, // OPCIONAL - alinhamento horizontal
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Outra Tabela -------------------------
	
	$outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => 'usuario', // Nome da tabela
		'campos' => Array(
			'nome',
			'sobrenome',
		), // Array com os nomes dos campos
		'extra' => '', // Tabela extra
	);
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo, // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => true, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $tabela['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela['campos'], // Array com os nomes dos campos
		'tabela_extra' => $tabela['extra'], // Tabela extra
		'tabela_order' => $tabela['order'], // Ordenação da tabela
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => $menu_principal,
		'menu_opcoes' => $menu_opcoes,
		'header_campos' => $header_campos,
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => 'Ação', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 20.5), // OPCIONAL - tamanho x da célula
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
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	
	$in_titulo = "Nova Mensagem";
	$botao = "Enviar";
	$opcao = "add_base";
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#para',$para);
	$pagina = paginaTrocaVarValor($pagina,'#para_js_id','0');
	$pagina = paginaTrocaVarValor($pagina,'#para_id','');
	$pagina = paginaTrocaVarValor($pagina,'#para_opcao','');
	$pagina = paginaTrocaVarValor($pagina,'#assunto',$assunto);
	$pagina = paginaTrocaVarValor($pagina,'#mensagem',$mensagem);
	
	// ======================================================================================
	
	$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['inclusao'] = $pagina;
	
	return interface_layout(parametros_interface());
}

function add_base(){
	global $_SYSTEM;
	global $_HTML;
	global $_LISTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_usuario = $usuario['id_usuario'];
	
	banco_conectar();
	
	$email_campos['de'] = 'De'; $_REQUEST['de'] = $usuario['nome'] . ($usuario['sobrenome']?' '.$usuario['sobrenome']:'');
	
	$campo_nome = "id_usuario"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$id_usuario);
	$campo_nome = "para"; $post_nome = $campo_nome; $descricao = "Para";					if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);$email_campos[$campo_nome] = $descricao;
	$campo_nome = "assunto"; $post_nome = $campo_nome;  $descricao = "Assunto";				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);$email_campos[$campo_nome] = $descricao;
	$campo_nome = "mensagem"; $post_nome = $campo_nome; $descricao = "Mensagem";				if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);$email_campos[$campo_nome] = $descricao;
	$campo_nome = "data"; $post_nome = $campo_nome; 					$campos[] = Array($campo_nome,'NOW()',true);
	$campo_nome = "status"; $post_nome = $campo_nome; 					$campos[] = Array($campo_nome,'A');
	
	banco_insert_name($campos,$_LISTA['tabela']['nome']);
	
	$id_mensagens = banco_last_id();
	
	switch($_REQUEST['para_opcao']){
		case 'usuario':
			$campo_nome = "id_mensagens"; 	$campos2[] = Array($campo_nome,$id_mensagens);
			$campo_nome = "id_usuario"; 	$campos2[] = Array($campo_nome,$_REQUEST['para_id']);
			
			banco_insert_name($campos2,'mensagens_usuario');
			
			$usuario_to = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
					'sobrenome',
					'email',
				))
				,
				"usuario",
				"WHERE id_usuario='".$_REQUEST['para_id']."'"
			);
			
			$parametros['from_name'] = $_SYSTEM['CONTATO_NOME'];
			$parametros['from'] = $_SYSTEM['CONTATO_EMAIL'];
			
			$parametros['email_name'] = $usuario_to[0]['nome'] . ($usuario_to[0]['sobrenome']?' '.$usuario_to[0]['sobrenome']:'');
			$parametros['email'] = $usuario_to[0]['email'];
			
			$parametros['subject'] = 'Nova Mensagem - Portal '.$_HTML['TITULO'];
			
			$parametros['mensagem'] .= "<h1>Nova Mensagem - Portal ".$_HTML['TITULO']."</h1>\n";
			$parametros['mensagem'] .= "<h3>Mensagem preenchida:</h3>\n";
			$parametros['mensagem'] .= "<table>\n";
			
			foreach($email_campos as $campo => $descricao){
				$parametros['mensagem'] .= "<tr><td width=\"130\">".$descricao."</td><td>".$_REQUEST[$campo]."</td></tr>\n";
			}
			
			$parametros['mensagem'] .= "</table>\n";
			if($_SYSTEM['MAILER_ASSINATURA'])$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
			
			enviar_mail($parametros);
		break;
		case 'grupo':
			$grupo = explode(',',$_REQUEST['para_id']);
			
			foreach($grupo as $id_grupo){
				$campo_nome = "id_mensagens"; 	$campos2[] = Array($campo_nome,$id_mensagens);
				$campo_nome = "id_grupo"; 		$campos2[] = Array($campo_nome,$id_grupo);
				
				banco_insert_name($campos2,'mensagens_grupo');
			}
		break;
		
	}
	
	banco_fechar_conexao();

	return lista();
}

function ver(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- ver < -->','<!-- ver > -->');
		
		banco_conectar();
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario',
				'para',
				'assunto',
				'mensagem',
				'data',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		$usuario = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'sobrenome',
			))
			,
			"usuario",
			"WHERE id_usuario='".$tabela[0]['id_usuario']."'"
		);
		
		$pagina = paginaTrocaVarValor($pagina,'#data',data_hora_from_datetime_to_text($tabela[0]['data']));
		$pagina = paginaTrocaVarValor($pagina,'#de',($usuario[0]['sobrenome']?$usuario[0]['nome'] . ' ' . $usuario[0]['sobrenome'] : $usuario[0]['nome']));
		$pagina = paginaTrocaVarValor($pagina,'#para',$tabela[0]['para']);
		$pagina = paginaTrocaVarValor($pagina,'#assunto',$tabela[0]['assunto']);
		$pagina = paginaTrocaVarValor($pagina,'#mensagem',$tabela[0]['mensagem']);
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = "Ver Mensagem";
		
		$_INTERFACE_OPCAO = 'editar'; 
		$_INTERFACE['informacao_titulo'] = $in_titulo;
		$_INTERFACE['informacao_id'] = $id;
		$_INTERFACE['inclusao'] = $pagina;
	
		return interface_layout(parametros_interface());
	} else
		return lista();
}

function escolher_para(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_HTML;
	
	$_HTML['LAYOUT'] = $_CAMINHO_MODULO_RAIZ."popup.html";
	$id = $_REQUEST[id];
	
	$pagina = paginaModelo('html.html');
	$pagina = paginaTagValor($pagina,'<!-- para < -->','<!-- para > -->');
	
	$pagina = modelo_var_troca($pagina,"#titulo","Escolher Para");
	
	return $pagina;
}

function para(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_HTML;
	
	$_HTML['LAYOUT'] = $_CAMINHO_MODULO_RAIZ."popup.html";
	
	if($_REQUEST[usuario]){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- usuario < -->','<!-- usuario > -->');
		
		$pagina = modelo_var_troca($pagina,"#titulo","Escolher Para");
		
		$nao_mostrar_anterior = true;
	}
	
	if($_REQUEST[grupo]){
		$pagina = grupo();
		
		$nao_mostrar_anterior = true;
	}
	
	if(!$nao_mostrar_anterior){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- para < -->','<!-- para > -->');
		
		$pagina = modelo_var_troca($pagina,"#titulo","Escolher Para");
	}
	
	return $pagina;
}

function grupo(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE;
	
	$id = $_REQUEST[id];
	$num_cols = 3;
	
	banco_conectar();
	if($id){
		$mensagens_grupo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_grupo',
			))
			,
			"mensagens_grupo",
			"WHERE id_mensagens='".$id."'"
		);
	}
	$grupo = banco_select_name
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
	$pagina = paginaTagValor($pagina,'<!-- grupo < -->','<!-- grupo > -->');
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$cel1 = modelo_tag_val($pagina,'<!-- cel1 < -->','<!-- cel1 > -->');
	$pagina = modelo_tag_in($pagina,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');
	$cel2 = modelo_tag_val($pagina,'<!-- cel2 < -->','<!-- cel2 > -->');
	$pagina = modelo_tag_in($pagina,'<!-- cel2 < -->','<!-- cel2 > -->','<!-- cel2 -->');
	
	$num_linhas = floor(count($grupo)/$num_cols)+1;
	$count = 0;
	
	$checked = " checked=\"checked\"";
	
	for($i=0;$i<count($grupo);$i++){
		if(!$linha[$count]){
			$linha[$count] = $cel2;
		}
		
		$checar = "";
		
		for($j=0;$j<count($mensagens_grupo);$j++){
			if($grupo[$i]['id_grupo'] == $mensagens_grupo[$j]['id_grupo']){
				$checar = $checked;
				$id_grupo[$i] = $mensagens_grupo[$j]['id_grupo'];
				break;
			}
		}
		
		$cel_aux = $cel1;
		
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_id",$grupo[$i]['id_grupo']);
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_name",$grupo[$i]['nome']);
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_num_11",$i);
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_num_12",$i);
		$cel_aux = modelo_var_troca($cel_aux,"#grupo_num_21",$i);
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
	
	$pagina = modelo_var_troca($pagina,"#num",count($grupo));
	
	// ======================================================================================
	
	return $pagina;
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$id_usuario = $usuario['id_usuario'];
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;

		banco_conectar();
		
		$mensagens_usuario = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_mensagens',
			))
			,
			"mensagens_usuario",
			"LIMIT 1"
		);
		$mensagens_grupo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_mensagens',
			))
			,
			"mensagens_grupo",
			"LIMIT 1"
		);
		$usuario_grupo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_grupo',
			))
			,
			"usuario_grupo",
			"LIMIT 1"
		);
		
		$resultado = banco_select
		(
			"t1.".$_LISTA['tabela']['id'] . ",t1." . $_LISTA['tabela']['campo'],
			"mensagens AS t1".
			($mensagens_usuario? ",mensagens_usuario AS t2":"").
			($mensagens_grupo && $usuario_grupo? ",mensagens_grupo AS t3,usuario_grupo AS t4":""),
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND 
			t1.".$_LISTA['tabela']['status']."!='D' AND 
				(
					".
					($mensagens_usuario || ( $mensagens_grupo && $usuario_grupo ) ? "(":"").
					($mensagens_usuario? "( 
						t2.id_usuario='".$id_usuario."'
						 AND 
						t1.id_mensagens=t2.id_mensagens
					)":"").
					($mensagens_usuario && $mensagens_grupo && $usuario_grupo?" OR ":"").
					($mensagens_grupo && $usuario_grupo? "(
						t4.id_usuario='".$id_usuario."'
						 AND 
						t3.id_grupo=t4.id_grupo
						 AND 
						t1.id_mensagens=t3.id_mensagens
					)":"").
				($mensagens_usuario || ( $mensagens_grupo && $usuario_grupo )? ") OR ":"").
				"	t1.id_usuario='".$id_usuario."'
				) GROUP BY t1.id_mensagens "
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
	
	if($_REQUEST['query_id'] == 'busca_nome2' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;

		banco_conectar();
		
		$resultado = banco_select
		(
			"id_usuario,nome,sobrenome",
			"usuario",
			"WHERE ( (UCASE(nome) LIKE UCASE('%" . $query . "%')) OR (UCASE(sobrenome) LIKE UCASE('%" . $query . "%')) ) AND status!='D' AND id_usuario!='".$id_usuario."'"
		);
		
		banco_fechar_conexao();

		for($i=0;$i<count($resultado);$i++){
			$saida[] = Array(
				'value' => utf8_encode(($resultado[$i][2]?$resultado[$i][1]." ".$resultado[$i][2]:$resultado[$i][1])),
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
	global $_HTML;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'menu_'.$_LOCAL_ID:
			case 'lista':						$saida = lista();break;
			case 'add':							$saida = (operacao('enviar') ? add() : lista());break;
			case 'add_base':					$saida = (operacao('enviar') ? add_base() : lista());break;
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? ver() : lista());break;
			case 'escolher_para':				$saida = escolher_para();break;
			case 'para':						$saida = para();break;
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