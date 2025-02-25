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
$_LOCAL_ID					=	"config";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_LOJA				=	true;
$_MENU_LATERAL				=	true;
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

$_HTML['titulo'] 						= 	$_HTML['titulo']."Configurações.";
$_HTML['variaveis']['titulo-modulo']	=	'Configurações';	

$_HTML['js'] = 
$_JS['menu'].
$_JS['tinyMce'].
$_JS['maskedInput'].
"<script type=\"text/javascript\" src=\"../js.js?v=".$_VERSAO_MODULO."\"></script>\n".
'<script type="text/javascript" src="jquery.tabify.js?v='.$_VERSAO_MODULO.'"></script>'."\n".
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= 
"<link href=\"../css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'loja';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_loja';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Configurações';
$_LISTA['ferramenta_unidade']	=	'a configuração';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

function identificador_unico($id,$num,$id_loja){
	$conteudo = banco_select
	(
		"id_loja"
		,
		"loja",
		"WHERE "."id"."='".($num ? $id.'-'.$num : $id)."'"
		.($id_loja?" AND id_loja!='".$id_loja."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1,$id_loja);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador($id,$id_loja = false){
	global $_ESERVICE;
	
	$tam_max_id = 90;
	$id = retirar_acentos(trim($id));
	
	if($id == $_ESERVICE['minha-loja-id']){
		$id = $_ESERVICE['minha-loja-id'].'-'.$id_loja;
	}
	
	if($_ESERVICE['store-ids-proibidos'])
	foreach($_ESERVICE['store-ids-proibidos'] as $ids_proibidos){
		if($ids_proibidos == $id){
			$id = $id.'-1';
		}
	}
	
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
		
		return identificador_unico($id,$num,$id_loja);
	} else {
		return identificador_unico($id,0,$id_loja);
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
		'layout_tag1' => '<!-- layout_pagina_2 < -->',
		'layout_tag2' => '<!-- layout_pagina_2 > -->',
		
	);
	
	return $parametros;
}

function editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_B2MAKE_URL;
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- form < -->','<!-- form > -->');
	$opcoes = modelo_tag_val($modelo,'<!-- opcoes < -->','<!-- opcoes > -->');
	$exclusao_botoes = modelo_tag_val($modelo,'<!-- exclusao_botoes < -->','<!-- exclusao_botoes > -->');
	
	$cel_nome = 'categoria'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$cel_nome = 'image'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'static'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'text'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'string'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'int'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'float'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'bool'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'status'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'tinymce'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	
	banco_conectar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id',
			'email',
			'descricao',
			'email_assinatura',
			'cpf',
			'cnpj',
			'pagseguro_email',
			'pagseguro_token',
			'paypal_email',
			'paypal_user',
			'paypal_pass',
			'paypal_signature',
			'logomarca',
			'endereco',
			'numero',
			'complemento',
			'bairro',
			'cidade',
			'uf',
			'pais',
			'telefone',
			'versao',
		))
		,
		"loja",
		"WHERE id_loja='".$usuario['id_loja']."'"
		." AND status='A'"
	);
	
	$meta_dados = Array(
		'nome' => Array(
			'definicao' => 'Defini&ccedil;&atilde;o do nome da loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Nome',
			'size' => 50,
		),
		'id' => Array(
			'definicao' => 'Endereço onde está localizado a sua loja no E-Services.',
			'tipo' => 'static',
			'grupo' => 'loja',
			'titulo' => 'URL do E-Services',
		),
		'email' => Array(
			'definicao' => 'E-mail de contato da loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'E-mail',
			'size' => 50,
		),
		'descricao' => Array(
			'definicao' => 'Um texto explicativo do que &eacute; a loja.',
			'tipo' => 'tinymce',
			'grupo' => 'loja',
			'titulo' => 'Descrição',
		),
		'email_assinatura' => Array(
			'definicao' => 'Um texto que será incluído nas assinaturas dos emails enviados aos seus clientes.',
			'tipo' => 'tinymce',
			'grupo' => 'loja',
			'titulo' => 'Assinatura dos E-mails',
		),
		'logomarca' => Array(
			'definicao' => 'Logomarca da sua loja.',
			'tipo' => 'image',
			'grupo' => 'loja',
			'titulo' => 'Logomarca',
		),
		'cnpj' => Array(
			'definicao' => 'CNPJ da sua loja. Informação: pela lei nova é necessário informar o CNPJ ou o CPF do dono da loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'CNPJ',
			'size' => 30,
			'class' => 'cnpj',
		),
		'cpf' => Array(
			'definicao' => 'CPF do dono da loja senão houver CNPJ. Se informar o CNPJ não é necessário informar o CPF. Informação: pela lei nova é necessário informar o CNPJ ou o CPF do dono da loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'CPF',
			'size' => 30,
			'class' => 'cpf',
		),
		'endereco' => Array(
			'definicao' => 'Endereço da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Endereço',
			'size' => 50,
		),
		'numero' => Array(
			'definicao' => 'Número da sua loja.',
			'tipo' => 'int',
			'grupo' => 'loja',
			'titulo' => 'Número',
		),
		'complemento' => Array(
			'definicao' => 'Complemento da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Complemento',
		),
		'bairro' => Array(
			'definicao' => 'Bairro da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Bairro',
			'size' => 50,
		),
		'cidade' => Array(
			'definicao' => 'Cidade da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Cidade',
			'size' => 50,
		),
		'uf' => Array(
			'definicao' => 'UF da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'UF',
			'size' => 4,
			'maxlength' => 2,
			'class' => 'uppercase',
		),
		'pais' => Array(
			'definicao' => 'País da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'País',
			'size' => 50,
		),
		'telefone' => Array(
			'definicao' => 'Telefone da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Telefone',
			'size' => 50,
			'class' => 'telefone',
		),
		'pagseguro_notification_url' => Array(
			'definicao' => 'Endereço que deverá ser fornecido ao PagSeguro para o mesmo notificar o nosso sistema quando houver uma modificação de estado dos pagamentos.',
			'tipo' => 'static',
			'grupo' => 'pagseguro',
			'titulo' => 'URL de Notificações',
		),
		'pagseguro_email' => Array(
			'definicao' => 'O email da sua conta PagSeguro.',
			'tipo' => 'string',
			'grupo' => 'pagseguro',
			'titulo' => 'Email no PagSeguro',
		),
		'pagseguro_token' => Array(
			'definicao' => 'O token &eacute; um c&oacute;digo gerado pelo PagSeguro que dar&aacute; a plataforma B2Make acesso &agrave; sua conta PagSeguro. <b>IMPORTANTE: Por uma questão de segurança este campo ficará oculto!</b>',
			'tipo' => 'string',
			'grupo' => 'pagseguro',
			'titulo' => 'Token do PagSeguro',
		),
		'paypal_email' => Array(
			'definicao' => 'O paypal_email do PayPal &eacute; o seu email cadastrado no PayPal.',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Email no PayPal',
		),
		'paypal_user' => Array(
			'definicao' => 'O paypal_user do PayPal &eacute; o usu&aacute;rio da credencial do PayPal.',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Usuário do API',
		),
		'paypal_pass' => Array(
			'definicao' => 'O paypal_pass do PayPal &eacute; a senha da credencial do PayPal. <b>IMPORTANTE: Por uma questão de segurança este campo ficará oculto!</b>',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Senha do API',
		),
		'paypal_signature' => Array(
			'definicao' => 'O paypal_signature do PayPal &eacute; a assinatura da credencial do PayPal. <b>IMPORTANTE: Por uma questão de segurança este campo ficará oculto!</b>',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Assinatura do API',
		),
		'paypal_notification_url' => Array(
			'definicao' => 'Endereço que deverá ser fornecido ao PayPal para o mesmo notificar o nosso sistema quando houver uma modificação de estado dos pagamentos.',
			'tipo' => 'static',
			'grupo' => 'paypal',
			'titulo' => 'URL de Notificações',
		),
	);
	
	$loja[0]['pagseguro_notification_url'] = $_B2MAKE_URL . 'e-services/pagseguro-notifications/' . $usuario['pub_id'] . '/';
	$loja[0]['paypal_notification_url'] = $_B2MAKE_URL . 'e-services/paypal-notifications/' . $usuario['pub_id'] . '/';
	
	if($loja){
		foreach($loja[0] as $var => $valor){
			$campos_nome[] = $var;
			$campos_guardar[$var] = $valor;
			
			$cel_aux = $cel[$meta_dados[$var]['tipo']];
			
			$cel_aux = modelo_var_troca($cel_aux,'#titulo','<b>'.$meta_dados[$var]['titulo'].'</b>');
			$cel_aux = modelo_var_troca_tudo($cel_aux,'#variavel',$var);
			
			if(
				$var == 'paypal_pass' ||
				$var == 'paypal_signature' ||
				$var == 'pagseguro_token' 
			)
				$valor = '';
				
			if($var == 'id'){
				if($valor){
					$valor = $_B2MAKE_URL . 'e-services/'.$valor.'/';
					$valor = '<a href="'.$valor.'" target="_blank">'.$valor.'</a>';
				}
			}	
			
			if($var == 'logomarca'){
				if($loja[0]['versao'])$versao = '?v=' . $loja[0]['versao'];
				if($valor)$valor = '<img src="/'.$_SYSTEM['ROOT'].$valor.$versao.'"><a href="?opcao=remover_item&item=logomarca" class="deletar"></a>';
			}
			
			$class = $var;
			$size = 40;
			$maxlength = 100;
			if($meta_dados[$var]['tipo'] == 'int' || $meta_dados[$var]['tipo'] == 'float'){
				$size = 20;
				$maxlength = 20;
			}
			
			if($meta_dados[$var]['size'])$size = $meta_dados[$var]['size'];
			if($meta_dados[$var]['maxlength'])$maxlength = $meta_dados[$var]['maxlength'];
			if($meta_dados[$var]['class'])$class = $meta_dados[$var]['class'];
			
			$cel_aux = modelo_var_troca($cel_aux,'#class',$class);
			$cel_aux = modelo_var_troca($cel_aux,'#valor',$valor);
			$cel_aux = modelo_var_troca($cel_aux,'#size',$size);
			$cel_aux = modelo_var_troca($cel_aux,'#maxlength',$maxlength);
			$cel_aux = modelo_var_troca_tudo($cel_aux,'#descricao',$meta_dados[$var]['definicao']);
			
			$pagina = modelo_var_in($pagina,'#'.$meta_dados[$var]['grupo'].'$',$cel_aux);

		}
	}
	
	$pagina = modelo_var_troca($pagina,'#loja$','');
	$pagina = modelo_var_troca($pagina,'#pagseguro$','');
	$pagina = modelo_var_troca($pagina,'#paypal$','');
	
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
	
	if(!operacao('editar') || !$loja)$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
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
	global $_ALERTA;
	global $_ESERVICE;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$campos_antes = campos_antes_recuperar();
	
	banco_conectar();
	
	// ================================= Local de Edição ===============================
	// Altere os campos da tabela e POST aqui, e modifique o UPDATE
	$campo_tabela = "loja";
	$campo_tabela_extra = "WHERE id_loja='".$usuario['id_loja']."'"
	." AND status='A'";
	
	$campos_nome = $campos_antes['campos_nome'];
	
	foreach($campos_nome as $campo_nome){
		if(
			$campo_nome != 'logomarca' &&
			$campo_nome != 'pagseguro_notification_url' &&
			$campo_nome != 'paypal_notification_url' 
		){
			if(
				$campo_nome == 'paypal_pass' ||
				$campo_nome == 'paypal_signature' ||
				$campo_nome == 'pagseguro_token' 
			){
				if($_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";}
			} else {
				if(
					$campo_nome != 'versao' && 
					$campo_nome != 'id'
				){
					if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";}
				}
			}
			
			if($campo_nome == 'nome'){
				if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){
					if(!$_REQUEST[$campo_nome]){
						$id = $_ESERVICE['minha-loja-id'];
					} else {
						$id = $_REQUEST[$campo_nome];
					}
					
					$id_old = $campos_antes['id'];
					
					$id = criar_identificador($id,$usuario['id_loja']);
					$editar[$campo_tabela][] = "id='" . $id . "'";
					
					if($id != $id_old){
						$path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'stores'.$_SYSTEM['SEPARADOR'];
						
						$oldname = $path . $id_old;
						$newname = $path . $id;
						
						rename($oldname, $newname);
						$_SESSION[$_SYSTEM['ID']."pdw-user-path"] = false;
					}
				}
			}
		}
	}
	
	if(!$campos_antes['versao'])$campos_antes['versao'] = 0;
	
	(int)$campos_antes['versao']++;
	$editar[$campo_tabela][] = "versao='" . $campos_antes['versao'] . "'";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	if($_FILES['logomarca']['size'] != 0)		{guardar_arquivo($_FILES['logomarca'],'imagem','logomarca',$usuario['id_loja']);}
	
	$_ALERTA = 'Campos atualizados com sucesso!';
	
	// ======================================================================================
	
	banco_fechar_conexao();
	
	return editar();
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_ESERVICE;
	
	$caminho_fisico 			=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."stores".$_SYSTEM['SEPARADOR'];
	$caminho_internet 			= 	"files/stores/";
	
	if(!is_dir($caminho_fisico)){
		mkdir($caminho_fisico);
		chmod($caminho_fisico , 0777);
	}
	
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
			
			if($_ESERVICE['store-logomarca-width']) $new_width = $_ESERVICE['store-logomarca-width'];
			if($_ESERVICE['store-logomarca-height']) $new_height = $_ESERVICE['store-logomarca-height'];
			if($_ESERVICE['store-logomarca-recorte-y']) $_RESIZE_IMAGE_Y_ZERO = true;
			
			resize_image($original, $original, $new_width, $new_height,false,false,true);
			
			banco_update
			(
				$campo."='".$caminho_internet.$nome_arquivo."'",
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id_tabela."'"
			);
		}
	}
}

function remover_item(){
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	global $_LISTA;
	global $_ALERTA;
	
	$item = $_REQUEST['item'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id = $usuario['id_loja'];
	
	$caminho_fisico 			=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."stores".$_SYSTEM['SEPARADOR'];
	$caminho_internet 			= 	"files/stores/";
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($item && $id){
		if(
			$item == 'logomarca'
		){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$item,
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			
			if($resultado){
				banco_update
				(
					$item."=NULL",
					$_LISTA['tabela']['nome'],
					"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
				);
			
				$resultado[0][$item] = str_replace($caminho_internet,$caminho_fisico,$resultado[0][$item]);
				if(is_file($resultado[0][$item]))unlink($resultado[0][$item]);
				$_ALERTA = "Ítem removido com sucesso!";
			} else {
				$_ALERTA = "Não é possível remover, essa imagem não faz parte do seu usuário!";
			}
		}
		
		return editar();
	}
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
			case 'remover_item':				$saida = (operacao('editar') ? remover_item() : editar('ver'));break;
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