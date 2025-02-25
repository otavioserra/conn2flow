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
$_LOCAL_ID					=	"customers";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_MAILER			=	true;
$_MENU_LATERAL				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
$_MENU_LATERAL_GESTOR		=	true;
$_HTML['LAYOUT']			=	$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

$_HTML['titulo'] 			= 	$_HTML['titulo']."Clientes.";
$_HTML['variaveis']['titulo-modulo']	=	'Clientes';

$_HTML['js'] = 
$_JS['menu'].
$_JS['maskedInput'].
'<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'maskedInput/jquery.mask.js?v='.$_VERSAO.'" type="text/javascript"></script>'.
$_JS['jQueryPassStrengthMeter'];

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'loja_usuarios';
$_LISTA['tabela']['campo']		=	'email';
$_LISTA['tabela']['id']			=	'id_loja_usuarios';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Loja Usuários';
$_LISTA['ferramenta_unidade']	=	'o usuário';

$_LISTA_2['tabela']['nome']			=	'pastas_usuarios';
$_LISTA_2['tabela']['campo']			=	'nome';
$_LISTA_2['tabela']['id']				=	'id_'.'pastas_usuarios';
$_LISTA_2['tabela']['status']			=	'status';

$_HTML['separador']		=	$_CAMINHO_RELATIVO_RAIZ;

$_VARIAVEIS_JS['gestor_opcao_editar'] = 'ver';

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

function perfil_select(){
	global $_SYSTEM;
	global $_PROJETO;
	
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

// ======================================================================================

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = 'nome,ultimo_nome ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = 'nome';
	$tabela_campos[] = 'ultimo_nome';
	$tabela_campos[] = 'email';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? '' : $_INTERFACE['informacao_titulo']);
	
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
		
	}
	
	if(operacao('ver')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
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
		'campo' => 'Nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'nome', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Último nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'ultimo_nome', // OPCIONAL - Nome do campo da tabela
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
		'informacao_titulo' => $informacao_titulo . ' ' . 'Clientes' , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => true, // Formulário de busca
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
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_loja='".$usuario['id_loja']."' ", // Tabela extra
		'tabela_extra_2' => "", // Tabela extra
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
		'layout_tag1' => '<!-- layout_pagina_2 < -->',
		'layout_tag2' => '<!-- layout_pagina_2 > -->',
		
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface());
}

function editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_REQUEST["id"])					$id = $_REQUEST["id"];
	
	if($id){
		if(!usuario_da_conta($id)) return lista();
		
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		$num_cols = 3;
		
		banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da interface com os valores iniciais
		
		$cel1 = modelo_tag_val($pagina,'<!-- cel1 < -->','<!-- cel1 > -->');
		$pagina = modelo_tag_in($pagina,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');
		$cel2 = modelo_tag_val($pagina,'<!-- cel2 < -->','<!-- cel2 > -->');
		$pagina = modelo_tag_in($pagina,'<!-- cel2 < -->','<!-- cel2 > -->','<!-- cel2 -->');
		
		$pagina = paginaTrocaVarValor($pagina,'#historico#',$hitorico);
		
		$num_linhas = floor(count($grupos)/$num_cols)+1;
		$count = 0;
		
		$checked = " checked=\"checked\"";
		
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
				'nome',
				'ultimo_nome',
				'email',
				'telefone',
				'cpf',
				'cnpj',
				'cnpj_selecionado',
			))
			,
			$_LISTA['tabela']['nome'],
			($_REQUEST['historico_id']?"WHERE ".$_LISTA['tabela']['id']."='".$_REQUEST['historico_id']."'":"WHERE ".$_LISTA['tabela']['id']."='".$id."'")
		);
		
		$campos_guardar = Array(
			'email' => $usuario[0]['email'],
			'nome' => $usuario[0]['nome'],
			'ultimo_nome' => $usuario[0]['ultimo_nome'],
			'telefone' => $usuario[0]['telefone'],
			'cnpj' => $usuario[0]['cnpj'],
			'cnpj_selecionado' => $usuario[0]['cnpj_selecionado'],
		);
		
		$pagina = paginaTrocaVarValor($pagina,'#email',$usuario[0]['email']);
		$pagina = paginaTrocaVarValor($pagina,'#nome',$usuario[0]['nome']);
		$pagina = paginaTrocaVarValor($pagina,'#ultimo_nome',$usuario[0]['ultimo_nome']);
		$pagina = paginaTrocaVarValor($pagina,'#tel',$usuario[0]['telefone']);
		$pagina = paginaTrocaVarValor($pagina,'#cpf',$usuario[0]['cpf']);
		$pagina = paginaTrocaVarValor($pagina,'#cnpj',$usuario[0]['cnpj']);
		
		// ======================================================================================
		
		banco_fechar_conexao();
		
		campos_antes_guardar($campos_guardar);
		
		$in_titulo = $param ? "Visualizar" : "Editar";
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

function usuario_da_conta($id){
	global $_SYSTEM;
	global $_LISTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja_usuarios',
		))
		,
		$_LISTA['tabela']['nome'],
		"WHERE status!='D' AND id_loja='".$usuario['id_loja']."' AND id_loja_usuarios='".$id."'"
	);
	
	if(!$resultado){
		return false;
	} else {
		return true;
	}
	
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
	global $_LISTA;
	global $_LISTA_2;
	
	if($_REQUEST['query_id'] == 'busca_nome'){
		$query = $_REQUEST["query"];
		if(!$query) return;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		banco_conectar();
		
		$resultado = banco_select
		(
			"id_loja_usuarios,nome,ultimo_nome,email",
			$_LISTA['tabela']['nome'],
			"WHERE (UCASE(nome) LIKE UCASE('%" . $query . "%') OR UCASE(ultimo_nome) LIKE UCASE('%" . $query . "%') OR UCASE(email) LIKE UCASE('%" . $query . "%')) AND status!='D'"
			." AND id_loja='".$usuario['id_loja']."' ORDER BY nome,email"
		);
		
		banco_fechar_conexao();

		for($i=0;$i<count($resultado);$i++){
			$saida[] = Array(
				'value' => $resultado[$i][1].' '.$resultado[$i][2].' <'.$resultado[$i][3].'>',
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
	
	return $saida;
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
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
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