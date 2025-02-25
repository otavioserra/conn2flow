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

$_VERSAO_MODULO				=	'1.0.3';
$_LOCAL_ID					=	"orders";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_MAILER			=	true;
$_INCLUDE_LOJA				=	true;
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
		$b2make_stores_permissoes = $_PROJETO['b2make_stores_permissoes'];
		$perm_flag = false;
		
		if($b2make_stores_permissoes)
		foreach($b2make_stores_permissoes as $perm){
			if($permissao_modulos[$perm]){
				if(!$perm_local)$perm_local = $perm;
				$perm_flag = true;
			}
		}
		
		if(!$perm_flag){
			header("Location: ".$_CAMINHO_MODULO_RAIZ);
		} else {
			redirecionar('store/'.$perm_local);
		}
	}
}

$_HTML['titulo'] 						= 	$_HTML['titulo']."Pedidos.";
$_HTML['variaveis']['titulo-modulo']	=	'Pedidos';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['tinyMce'].
$_JS['maskedInput'];

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"../../includes/ecommerce/css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'pedidos';
$_LISTA['tabela']['campo']			=	'codigo';
$_LISTA['tabela']['id']				=	'id_'.'pedidos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Pedidos';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

$_VARIAVEIS_JS['gestor_opcao_editar'] = 'ver';

// Funções do Sistema

function redirecionar($local = false,$sem_root = false){
	global $_SYSTEM;
	global $_AJAX_PAGE;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PROJETO;
	global $_REDIRECT_PAGE;
	global $_ALERTA;
	
	if($local){
		$local = ($sem_root?'':'/' . $_SYSTEM['ROOT']) . ($local == '/' ?'':$local);
	} else {
		switch($_SESSION[$_SYSTEM['ID']."permissao_id"]){
			//case '2': $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = $_CAMINHO_RELATIVO_RAIZ.$_HTML['ADMIN']; break;
			default: $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $_HTML['ADMIN'];
		}
		
		if($_PROJETO['redirecionar']){
			$permissao_id = $_SESSION[$_SYSTEM['ID']."permissao_id"];
			
			if($_PROJETO['redirecionar']['permissao_id']){
				$dados = $_PROJETO['redirecionar']['permissao_id'];
				foreach($dados as $dado){
					if($dado['id'] == $permissao_id) $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $dado['local'];
				}
			}	
		}
		
		$local = $_SESSION[$_SYSTEM['ID']."redirecionar_local"];
	}
	
	if($_AJAX_PAGE){
		if($_REDIRECT_PAGE){
			$_VARIAVEIS_JS['redirecionar'] = $local;
			$_REDIRECT_PAGE = false;
		} else {
			$_VARIAVEIS_JS['redirecionar_ajax'] = $local;
		}
		echo pagina();
		exit(0);
	} else {
		if($_ALERTA)$_SESSION[$_SYSTEM['ID']."alerta"] = $_ALERTA;
		header("Location: ".$local);
		exit(0);
	}
	
}

function envio_select($opcao){
	global $_BANCO_PREFIXO,
	$_SYSTEM_ID,
	$_PAGINA_LOCAL,
	$_URL;
	
	$nome = 'envio';
	$id = $nome . '_id';
	
	$opcoes = Array(
		Array(	'Entregue' , 'F'		),
		Array(	'Enviado' , 'E'		),
		Array(	'Não enviado' , 'N'		),
		Array(	'Retirado em Mãos' , 'M'	),
	);
	
	for($i=0;$i<count($opcoes);$i++){
		$options[] = $opcoes[$i][0];
		$optionsValue[] = $opcoes[$i][1];
		
		if($opcao == $opcoes[$i][1]){
			$optionSelected = $i;
		}
	}
	
	if(!$optionSelected && count($opcoes) == 1)
		$optionSelected = 1;
	
	$select = formSelect($nome,$id,$options,$optionsValue,$optionSelected,$extra);
	
	return $select;
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
	global $_PROJETO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	// =============================== Filtro ====================== < =
	
	if(
		$_INTERFACE_OPCAO != 'editar'
	){
		$filtro = $_SESSION[$_SYSTEM['ID']."pedidos-filtro"];
		
		if($_REQUEST['filtro-data-1']) $filtro['filtro-data-1'] = $_REQUEST['filtro-data-1'];
		if($_REQUEST['filtro-data-2']) $filtro['filtro-data-2'] = $_REQUEST['filtro-data-2'];
		if($_REQUEST['filtro-status']) $filtro['filtro-status'] = $_REQUEST['filtro-status'];
		if($_REQUEST['filtro-servicos']) $filtro['filtro-servicos'] = $_REQUEST['filtro-servicos'];
		
		if($_REQUEST['filtro-esquecer']) $filtro = false;
		
		$status_options_raw = $_PROJETO['B2MAKE_STORE_STATUS_MUDAR_TITULO'];
		
		foreach($status_options_raw as $sp_code => $sp_val){
			if($filtro['filtro-status'] && $filtro['filtro-status'] == $sp_code){
				$status_selected_value = $sp_code;
				$status_selected_text = $sp_val;
			}
			
			$status_options[] = Array(
				'value' => $sp_code,
				'text' => $sp_val,
			);
		}
		
		$status_pagamento = componentes_select(Array(
			'input_name' => 'filtro-status',
			'selected_value' => $status_selected_value,
			'selected_text' => $status_selected_text,
			'unselected_value' => '-1',
			'unselected_text' => 'Todos os Status',
			'options' => $status_options,
			'cont_class_extra' => ' filtros-cont',
		));
		
		$servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'id_servicos',
			))
			,
			"servicos",
			"WHERE id_loja='".$usuario['id_loja']."'"
			." AND status!='D'"
			." ORDER BY nome ASC"
		);
		
		if($servicos)
		foreach($servicos as $servico){
			if($filtro['filtro-servicos'] && $filtro['filtro-servicos'] == $servico['id_servicos']){
				$servico_selected_value = $servico['id_servicos'];
				$servico_selected_text = $servico['nome'];
			}
			
			$service_options[] = Array(
				'value' => $servico['id_servicos'],
				'text' => $servico['nome'],
			);
		}
		
		$status_servicos = componentes_select(Array(
			'input_name' => 'filtro-servicos',
			'selected_value' => $servico_selected_value,
			'selected_text' => $servico_selected_text,
			'unselected_value' => '-1',
			'unselected_text' => 'Todos os Serviços',
			'options' => $service_options,
			'cont_class_extra' => ' filtros-cont',
		));
		
		$data_1 .= componentes_datepicker(Array(
			'input_name' => 'filtro-data-1',
			'datepicker_value' => ($filtro['filtro-data-1'] ? $filtro['filtro-data-1'] : ''),
			'cont_class_extra' => ' filtros-cont',
		));
		
		$data_2 .= componentes_datepicker(Array(
			'input_name' => 'filtro-data-2',
			'datepicker_value' => ($filtro['filtro-data-2'] ? $filtro['filtro-data-2'] : ''),
			'cont_class_extra' => ' filtros-cont',
		));
		
		$informacao_acima = '
		<form action="." method="POST" class="filtro-cont">
			<div id="filtro-label">Filtrar por:</div>
			'.$status_servicos.'
			'.$status_pagamento.'
			<div for="filtro-data-1" class="filtros-cont-2">de</div>
			'.$data_1.'
			<div for="filtro-data-2" class="filtros-cont-2">até</div>
			'.$data_2.'
			<input type="submit" value="Filtrar" id="filtrar-button">
		</form>
		<form action="." method="POST" class="filtro-cont">
			<input type="hidden" value="1" name="filtro-esquecer">
			<input type="submit" value="Limpar" id="esquecer-button">
		</form>
		';
		
		$_SESSION[$_SYSTEM['ID']."pedidos-filtro"] = $filtro;
	}
	
	if($filtro['filtro-data-1']) $filtro_where = " AND t1.data >= '".data_padrao_date($filtro['filtro-data-1'])."'";
	if($filtro['filtro-data-2']) $filtro_where .= " AND t1.data <= '".data_padrao_date($filtro['filtro-data-2'])."'";
	if($filtro['filtro-status'] && $filtro['filtro-status'] != '-1') $filtro_where .= " AND t1.status = '".$filtro['filtro-status']."'";
	if($filtro['filtro-servicos'] && $filtro['filtro-servicos'] != '-1'){
		$tabela_relacionada_where .= " AND t1.id_pedidos=t2.id_pedidos AND t2.id_servicos='".$filtro['filtro-servicos']."'";
		$tabela_relacionada_group_by = " GROUP BY t1.".$_LISTA['tabela']['campo'];
	}
	
	// =============================== Filtro ====================== > =
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' DESC';
	
	$tabela_campos[] = 't1.'.$_LISTA['tabela']['status'];
	$tabela_campos[] = 't1.'.$_LISTA['tabela']['id'];
	$tabela_campos[] = 't1.'.$_LISTA['tabela']['campo'];
	$tabela_campos[] = 't1.'.$_LISTA['tabela']['id'];
	$tabela_campos[] = 't1.'.'valor_total';
	$tabela_campos[] = 't1.'.'data';
	$tabela_campos[] = 't1.'.'status';
	$tabela_campos[] = 't1.'.'status';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? '' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => '../../dashboard/',// link da opção
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
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		/* if(operacao('excluir')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => '#', // link da opção
				'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
				'img' => $_HTML['separador'].$_HTML['ICONS'] . 'db_remove.png', // caminho da imagem
				'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
				'width' => '40', // OPCIONAL - tamanho x da imagem
				'height' => '40', // OPCIONAL - y da imagem
				'name' => 'Excluir', // Nome do menu
			);
		} */
		if(!$usuario['id_usuario_pai']){
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
		/* if(!$loja_online_produtos){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=imprimir-voucher&id=#id', // link da opção
				'title' => 'Imprimir Voucher', // título da opção
				'img_coluna' => 3, // Coluna background image
				'img_linha' => 2, // Linha background image
				'name' => 'Imprimir Voucher', // Nome do menu
			);
		} */
		
		$busca_ativa = false;
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'codigo',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$_REQUEST['id']."'"
		);
		
		$pedido_nome = 'Pedido '.$tabela[0]['codigo'];
	} else {
		$busca_ativa = operacao('buscar');
		
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=export', // link da opção
			'title' => 'Exportar ' . $_LISTA['ferramenta'], // título da opção
			'img_coluna' => 2, // Coluna background image
			'img_linha' => 2, // Linha background image
			'name' => 'Exportar', // Nome do menu
		);
	}
	
	
	//if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
		);
	//}
	/* if(operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=editar&id=#id', // link da opção
			'title' => 'Editar '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'editar.png', // caminho da imagem
			'legenda' => 'Editar', // Legenda
		);
	}*/
	
	if(!$usuario['id_usuario_pai']){
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
	}
	if(!$loja_online_produtos){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=imprimir-voucher&id=#id', // link da opção
			'title' => 'Imprimir Voucher', // título da opção
			'img_coluna' => 15, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Imprimir Voucher', // Legenda
		);
	}
	
	/*if(operacao('excluir')){
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
		'width' => '90', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Cliente', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'id' => 2, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
		'tabela' => 2, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'mudar_valor' => 2, // OPCIONAL - Se faz parte de outra tabela de número desse valor
		'padrao_se_nao_existe' => true,
		'valor_padrao_se_nao_existe' => '',
		'campos' => Array('nome','ultimo_nome'), // OPCIONAL - Nome do campo da tabela
		'campos_layout' => '#nome# #ultimo_nome#', // OPCIONAL - Nome do campo da tabela
		'campo_id' => 't2.id_pedidos', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Valor', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '90', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'dinheiro_reais' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data Hora', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '140', // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'data_hora' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Baixa', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '140', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
		'valor_padrao_se_nao_existe' => '', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => Array(
			'F' => '<span class="status-baixado"></span>',
			'A' => '<span class="status-nao-baixado"></span>',
		),
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'ordenar' => true, // Valor do campo
		'width' => '140', // Valor do campo
		'align' => 'center', // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
		'valor_padrao_se_nao_existe' => '<span style="color:red;">Não Definido</span>', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => $_PROJETO['B2MAKE_STORE_STATUS_2'],
	);
	
	// ------------------------------ Outra Tabela -------------------------
	
	$outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => 'loja_usuarios', // Nome da tabela
		'campos' => Array(
			'email',
		), // Array com os nomes dos campos
		'extra' => '', // Tabela extra
	);
	$outra_tabela[] = Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
		'nome' => 'loja_usuarios as t1,loja_usuarios_pedidos as t2', // Nome da tabela
		'campos' => Array(
			't1.nome',
			't1.ultimo_nome',
		), // Array com os nomes dos campos
		'extra' => ' AND t1.id_loja_usuarios=t2.id_loja_usuarios', // Tabela extra
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => ($pedido_nome ? $pedido_nome : ' '. $_LISTA['ferramenta']) , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => $busca_ativa, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Cancelar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_nome' => $_LISTA['tabela']['nome'].' AS t1'.($tabela_relacionada_where ? ', pedidos_servicos AS t2' : ''), // Nome da tabela
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE t1.".$_LISTA['tabela']['status']."!='D' AND t1.id_loja='".$usuario['id_loja']."'".$filtro_where.$tabela_relacionada_where.$tabela_relacionada_group_by." ", // Tabela extra
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
		'relaciona_tabela' => $relaciona_tabela,
		'informacao_abaixo' => $informacao_abaixo,
		'informacao_acima' => $informacao_acima,
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
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_ALERTA;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	
	if($id){
		$usuario_sess = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id."'"
			." AND id_loja='".$usuario_sess['id_loja']."'"
		);
		
		if(!$pedido){
			$_ALERTA = 'Este pedido n&atilde;o pertence a sua loja!';
			
			return lista();
		}
		
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form'.($loja_online_produtos?'2':'').' < -->','<!-- form'.($loja_online_produtos?'2':'').' > -->');
		
		$cel_nome = 'servicos'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		$cel_nome = 'produtos'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		if(!operacao('editar')){
			$cel_nome2 = 'validade'; $cel[$cel_nome] = modelo_tag_in($cel[$cel_nome],'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
			$cel_nome2 = 'mudar_envio'; $cel[$cel_nome] = modelo_tag_in($cel[$cel_nome],'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
		}
		
		banco_conectar();
		
		if($loja_online_produtos){
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
					'presente',
					'de',
					'para',
					'mensagem',
					'observacao_baixa',
					'interno',
					'cortesia',
					'valor_total',
					'dest_nome',
					'dest_endereco',
					'dest_num',
					'dest_complemento',
					'dest_bairro',
					'dest_cidade',
					'dest_uf',
					'dest_cep',
					'tipo_frete',
					'valor_frete',
					'status_envio',
					'codigo_rastreio',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			$loja_usuarios_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't2.nome',
					't2.ultimo_nome',
					't2.id_loja_usuarios',
					't2.telefone',
					't2.cnpj_selecionado',
					't2.cnpj',
					't2.cpf',
					't2.email',
				))
				,
				"loja_usuarios_pedidos as t1,loja_usuarios as t2",
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_loja_usuarios=t2.id_loja_usuarios"
			);
			
			// ================================= Local de Edição ===============================
			// Pegue os campos da interface e campos_guardar aqui
			
			/* $campos_guardar = Array(
				'codigo' => $tabela[0]['codigo'],
				'descricao' => $tabela[0]['descricao'],
			); */
			
			// ================================= Status ===============================
			
			$status_mudar = $_PROJETO['B2MAKE_STORE_STATUS'];
			
			$status = $tabela[0]['status'];
			
			foreach($status_mudar as $chave => $valor){
				if($tabela[0]['status'] == $chave){
					$tabela[0]['status'] = $valor;
					break;
				}
			}
			
			if(!$tabela[0]['status_envio']){
				$tabela[0]['status_envio'] = '<span style="color:red;">Não enviado</span>';
				$status_envio = 'N';
			} else {
				$status_envio_mudar = Array(
					'F' => '<span style="color:green;">Entregue</span>',
					'M' => '<span style="color:green;">Retirado em Mãos</span>',
					'E' => '<span style="color:blue;">Enviado</span>',
					'N' => '<span style="color:red;">Não enviado</span>',
				);
				
				$status_envio = $tabela[0]['status_envio'];
				
				foreach($status_envio_mudar as $chave => $valor){
					if($tabela[0]['status_envio'] == $chave){
						$tabela[0]['status_envio'] = $valor;
						break;
					}
				}
			}
			
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				'usuario',
				"WHERE id_usuario='".$tabela[0]['id_usuario_baixa']."'"
			);
			
			$tipo_frete = $tabela[0]['tipo_frete'];
			
			switch($tipo_frete){
				case '1': $tipo_frete = 'SEDEX'; break;
				case '2': $tipo_frete = 'PAC'; break;
				default: $tipo_frete = 'Retirar em mãos';
			}
			
			$envio = envio_select($status_envio);
			
			$pagina = paginaTrocaVarValor($pagina,'#usuario_pedido#',($loja_usuarios_pedidos[0]['t2.id_loja_usuarios']?"<a href=\"../customers/?opcao=ver&id=".$loja_usuarios_pedidos[0]['t2.id_loja_usuarios']."\"><b>".$loja_usuarios_pedidos[0]['t2.nome'].' '.$loja_usuarios_pedidos[0]['t2.ultimo_nome']."</b></a>":"Pedido Sem Usuário"));
			$pagina = paginaTrocaVarValor($pagina,'#codigo#',$tabela[0]['codigo']);
			$pagina = paginaTrocaVarValor($pagina,'#senha#',$tabela[0]['senha']);
			$pagina = paginaTrocaVarValor($pagina,'#status#',$tabela[0]['status']);
			$pagina = paginaTrocaVarValor($pagina,'#status_envio#',$tabela[0]['status_envio']);
			$pagina = paginaTrocaVarValor($pagina,'#valor_total#',preparar_float_4_texto($tabela[0]['valor_total']));
			$pagina = paginaTrocaVarValor($pagina,'#valor_total_frete#',preparar_float_4_texto(((float)$tabela[0]['valor_total']+(float)$tabela[0]['valor_frete'])));
			$pagina = paginaTrocaVarValor($pagina,'#presente#',($tabela[0]['presente'] == '1' ? 'Sim' : 'Não'));
			$pagina = paginaTrocaVarValor($pagina,'#interno#',($tabela[0]['interno']?'<span class="interno_sim">Sim</span>':'<span class="interno_nao">Não</span>'));
			$pagina = paginaTrocaVarValor($pagina,'#cortesia#',($tabela[0]['cortesia']?'<span class="cortesia_sim">Sim</span>':'<span class="cortesia_nao">Não</span>'));
			
			$pagina = paginaTrocaVarValor($pagina,'#dest_nome#',$tabela[0]['dest_nome']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_endereco#',$tabela[0]['dest_endereco']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_num#',($tabela[0]['dest_num']?' ':'').$tabela[0]['dest_num']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_complemento#',($tabela[0]['dest_complemento']?' ':'').$tabela[0]['dest_complemento']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_bairro#',($tabela[0]['dest_bairro']?' - ':'').$tabela[0]['dest_bairro']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_cidade#',$tabela[0]['dest_cidade']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_uf#',($tabela[0]['dest_uf']?' / ':'').$tabela[0]['dest_uf']);
			$pagina = paginaTrocaVarValor($pagina,'#dest_cep#',$tabela[0]['dest_cep']);
			$pagina = paginaTrocaVarValor($pagina,'#tipo_frete#',$tipo_frete);
			$pagina = paginaTrocaVarValor($pagina,'#valor_frete#',preparar_float_4_texto($tabela[0]['valor_frete']));
			$pagina = paginaTrocaVarValor($pagina,'#codigo_rastreio#',$tabela[0]['codigo_rastreio']);
			$pagina = paginaTrocaVarValor($pagina,'#envio#',$envio);
			
			$pagina = paginaTrocaVarValor($pagina,'#opcoes#',$opcoes);
			
			$log_bd = banco_select_name
			(
				banco_campos_virgulas(Array(
					'valor',
					'data',
				))
				,
				"log",
				"WHERE id_referencia='".$id."'"
				." AND grupo='pedidos'"
				." ORDER BY data DESC"
			);
			
			if($log_bd)
			foreach($log_bd as $log){
				$log_txt .= ($log_txt ? '<br>' : '') . data_hora_from_datetime_to_text($log['data']) . ' - ' . $log['valor'];
			}
			
			$pagina = paginaTrocaVarValor($pagina,'#log#',$log_txt);
			
			$pagina = paginaTrocaVarValor($pagina,'#data#',data_hora_from_datetime_to_text($tabela[0]['data']));
			
			$data_full = $tabela[0]['data'];
			$data_arr = explode(' ',$data_full);
			
			$pedidos_produtos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.valor_original',
					't1.sub_total',
					't1.desconto',
					't1.codigo',
					't1.status',
					't2.nome',
				))
				,
				'pedidos_produtos as t1,produtos as t2',
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_produtos=t2.id_produtos"
				." ORDER BY t2.nome ASC"
			);
			
			$cel_nome = 'produtos';
			if($pedidos_produtos)
			foreach($pedidos_produtos as $res){
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#opcoes#",$opcoes);
				$cel_aux = modelo_var_troca($cel_aux,"#valor_original#",($res['t1.valor_original']?preparar_float_4_texto($res['t1.valor_original']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#sub_total#",($res['t1.sub_total']?preparar_float_4_texto($res['t1.sub_total']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#desconto#",$res['t1.desconto']);
				$cel_aux = modelo_var_troca($cel_aux,"#nome#",$res['t2.nome']);
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#codigo#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#codigo_servico#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#data_pedido#",$data_arr[0]);
				$cel_aux = modelo_var_troca($cel_aux,"#senha#",$res['t1.senha']);
				
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		} else {
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
					'presente',
					'de',
					'para',
					'mensagem',
					'observacao_baixa',
					'interno',
					'cortesia',
					'valor_total',
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			$loja_usuarios_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't2.nome',
					't2.id_loja_usuarios',
					't2.ultimo_nome',
					't2.telefone',
					't2.cnpj_selecionado',
					't2.cnpj',
					't2.cpf',
					't2.email',
				))
				,
				"loja_usuarios_pedidos as t1,loja_usuarios as t2",
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_loja_usuarios=t2.id_loja_usuarios"
			);
			
			// ================================= Local de Edição ===============================
			// Pegue os campos da interface e campos_guardar aqui
			
			/* $campos_guardar = Array(
				'codigo' => $tabela[0]['codigo'],
				'descricao' => $tabela[0]['descricao'],
			); */
			
			// ================================= Status ===============================
			
			$status_mudar = $_PROJETO['B2MAKE_STORE_STATUS_2'];
			
			$status = $tabela[0]['status'];
			$id_loja_usuarios = $loja_usuarios_pedidos[0]['t2.id_loja_usuarios'];
			
			foreach($status_mudar as $chave => $valor){
				if($tabela[0]['status'] == $chave){
					$tabela[0]['status'] = $valor;
					break;
				}
			}
			
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				'usuario',
				"WHERE id_usuario='".$tabela[0]['id_usuario_baixa']."'"
			);
			
			$pagina = paginaTrocaVarValor($pagina,'#usuario_pedido#',($loja_usuarios_pedidos[0]['t2.id_loja_usuarios']?"<a href=\"../customers/?opcao=ver&id=".$loja_usuarios_pedidos[0]['t2.id_loja_usuarios']."\"><b>".$loja_usuarios_pedidos[0]['t2.nome'].' '.$loja_usuarios_pedidos[0]['t2.ultimo_nome']."</b></a>":"Pedido Sem Usuário"));
			$pagina = paginaTrocaVarValor($pagina,'#usuario_email#',$loja_usuarios_pedidos[0]['t2.email']);
			$pagina = paginaTrocaVarValor($pagina,'#usuario_telefone#',$loja_usuarios_pedidos[0]['t2.telefone']);
			$pagina = paginaTrocaVarValor($pagina,'#label_cpf_ou_cnpj#',($loja_usuarios_pedidos[0]['t2.cnpj_selecionado'] ? 'CNPJ' : 'CPF'));
			$pagina = paginaTrocaVarValor($pagina,'#usuario_cpf_cnpj#',($loja_usuarios_pedidos[0]['t2.cnpj_selecionado'] ? $loja_usuarios_pedidos[0]['t2.cnpj'] : $loja_usuarios_pedidos[0]['t2.cpf']));
			$pagina = paginaTrocaVarValor($pagina,'#codigo#',$tabela[0]['codigo']);
			
			if($tabela[0]['senha']){
				$pagina = paginaTrocaVarValor($pagina,'#senha#',$tabela[0]['senha']);
			} else {
				$cel_nome = 'pedido-senha'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			}
			
			$pagina = paginaTrocaVarValor($pagina,'#status#',$tabela[0]['status']);
			$pagina = paginaTrocaVarValor($pagina,'#valor_total#',preparar_float_4_texto($tabela[0]['valor_total']));
			$pagina = paginaTrocaVarValor($pagina,'#presente#',($tabela[0]['presente'] == '1' ? 'Sim' : 'Não'));
			$pagina = paginaTrocaVarValor($pagina,'#interno#',($tabela[0]['interno']?'<span class="interno_sim">Sim</span>':'<span class="interno_nao">Não</span>'));
			$pagina = paginaTrocaVarValor($pagina,'#cortesia#',($tabela[0]['cortesia']?'<span class="cortesia_sim">Sim</span>':'<span class="cortesia_nao">Não</span>'));
			
			$pagina = paginaTrocaVarValor($pagina,'#opcoes#',$opcoes);
			
			if($tabela[0]['presente'] == '1'){
				$pagina = paginaTrocaVarValor($pagina,'#de#',$tabela[0]['de']);
				$pagina = paginaTrocaVarValor($pagina,'#para#',$tabela[0]['para']);
				$pagina = paginaTrocaVarValor($pagina,'#mensagem#',$tabela[0]['mensagem']);
			} else {
				$cel_nome2 = 'mensagem-cel'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
			}
			
			if($status == 'F'){
				$pagina = paginaTrocaVarValor($pagina,'#protocolo#',$tabela[0]['protocolo_baixa']);
				$pagina = paginaTrocaVarValor($pagina,'#data_baixa#',data_hora_from_datetime_to_text($tabela[0]['data_baixa']));
				$pagina = paginaTrocaVarValor($pagina,'#usuario#',($usuario_sess['id_usuario_pai'] ? "<a href=\"../usuarios/?opcao=editar&id=".$tabela[0]['id_usuario_baixa']."\"><b>".$usuario[0]['nome']."</b></a>" : "<a href=\"../../management/my-profile/\"><b>".$usuario[0]['nome']."</b></a>"));
				$pagina = paginaTrocaVarValor($pagina,'#observacao_baixa#',$tabela[0]['observacao_baixa']);
				
				if(!$tabela[0]['observacao_baixa']){
					$cel_nome2 = 'baixa-voucher-observacao-cel'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
				}
			} else {
				$cel_nome2 = 'baixa-voucher-cel'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
			}
			
			$log_bd = banco_select_name
			(
				banco_campos_virgulas(Array(
					'valor',
					'data',
				))
				,
				"log",
				"WHERE id_referencia='".$id."'"
				." AND grupo='pedidos'"
				." ORDER BY data DESC"
			);
			
			if($log_bd)
			foreach($log_bd as $log){
				$log_txt .= ($log_txt ? '<br>' : '') . data_hora_from_datetime_to_text($log['data']) . ' - ' . $log['valor'];
			}
			
			$pagina = paginaTrocaVarValor($pagina,'#log#',$log_txt);
			
			$pagina = paginaTrocaVarValor($pagina,'#data#',data_hora_from_datetime_to_text($tabela[0]['data']));
			
			$data_full = $tabela[0]['data'];
			$data_arr = explode(' ',$data_full);
			
			$loja = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.id',
				))
				,
				'loja as t1,pedidos as t2',
				"WHERE t2.id_pedidos='".$id."'"
				." AND t1.id_loja=t2.id_loja"
			);
			
			$_VARIAVEIS_JS['b2make_loja_atual'] = $loja[0]['t1.id'];
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.id_pedidos_servicos',
					't1.valor_original',
					't1.sub_total',
					't1.desconto',
					't1.codigo',
					't1.senha',
					't1.protocolo_baixa',
					't1.id_usuario_baixa',
					't1.data_baixa',
					't1.validade',
					't1.validade_data',
					't1.validade_tipo',
					't1.observacao_baixa',
					't1.status',
					't2.nome',
					't1.identificacao_nome',
					't1.identificacao_documento',
					't1.identificacao_telefone',
					't1.nome',
				))
				,
				'pedidos_servicos as t1,servicos as t2',
				"WHERE t1.id_pedidos='".$id."'"
				." AND t1.id_servicos=t2.id_servicos"
				." ORDER BY t2.nome ASC"
			);
			
			$usuario_sess = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$cel_nome = 'servicos';
			if($pedidos_servicos)
			foreach($pedidos_servicos as $res){
				$cel_aux = $cel[$cel_nome];
				
				$usuario = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
						'ultimo_nome',
					))
					,
					'usuario',
					"WHERE id_usuario='".$res['t1.id_usuario_baixa']."'"
				);
				
				if($res['t1.status'] == 'F'){
					$cel_aux = modelo_var_troca($cel_aux,"#opcoes_baixa#",$opcoes_baixa);
					$cel_aux = modelo_var_troca($cel_aux,"#observacao_baixa#",$res['t1.observacao_baixa']);
					$cel_aux = modelo_var_troca($cel_aux,"#protocolo_baixa#",$res['t1.protocolo_baixa']);
					$cel_aux = modelo_var_troca($cel_aux,"#data_baixa#",data_hora_from_datetime_to_text($res['t1.data_baixa']));
					$cel_aux = modelo_var_troca($cel_aux,"#usuario_baixa#",($usuario_sess['id_usuario_pai'] ? "<a href=\"../../management/users/?opcao=editar&id=".$res['t1.id_usuario_baixa']."\"><b>".$usuario[0]['nome'].' '.$usuario[0]['ultimo_nome']."</b></a>" : "<a href=\"../../management/my-profile/\"><b>".$usuario[0]['nome']."</b></a>"));
					$cel_nome2 = 'validade'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
					
					if(!$res['t1.observacao_baixa']){
						$cel_nome2 = 'baixa-observacao-cel'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
					}
				} else {
					$cel_nome2 = 'baixa-cel'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
				}
				
				if($res['t1.status'] != 'A'){
					$cel_nome2 = 'baixar-manual-cel'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
				}
				
				if($res['t1.status'] != 'A' || $id_loja_usuarios != $usuario_sess['id_loja_usuarios']){
					$cel_nome2 = 'voucher-cel'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
				}
				
				if($id_loja_usuarios != $usuario_sess['id_loja_usuarios']){
					$cel_nome2 = 'identificacao'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->','');
				}
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#id_pedidos#",$id);
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#id_pedidos_servicos#",$res['t1.id_pedidos_servicos']);
				$cel_aux = modelo_var_troca($cel_aux,"#opcoes#",$opcoes);
				$cel_aux = modelo_var_troca($cel_aux,"#valor_original#",($res['t1.valor_original']?preparar_float_4_texto($res['t1.valor_original']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#sub_total#",($res['t1.sub_total']?preparar_float_4_texto($res['t1.sub_total']):''));
				$cel_aux = modelo_var_troca($cel_aux,"#desconto#",$res['t1.desconto']);
				$cel_aux = modelo_var_troca($cel_aux,"#nome#",($res['t1.nome'] ? $res['t1.nome'] : $res['t2.nome']));
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#codigo#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#codigo_servico#",$res['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#data_pedido#",$data_arr[0]);
				
				if($res['t1.senha']){
					$cel_aux = modelo_var_troca($cel_aux,"#senha#",$res['t1.senha']);
				} else {
					$cel_aux_nome = 'servicos-senha'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_aux_nome.' < -->','<!-- '.$cel_aux_nome.' > -->','');
				}
				
				$cel_aux = modelo_var_troca($cel_aux,"#validade_tipo#",$res['t1.validade_tipo']);
				$cel_aux = modelo_var_troca($cel_aux,"#identificacao_nome#",$res['t1.identificacao_nome']);
				$cel_aux = modelo_var_troca($cel_aux,"#identificacao_documento#",$res['t1.identificacao_documento']);
				$cel_aux = modelo_var_troca($cel_aux,"#identificacao_telefone#",$res['t1.identificacao_telefone']);
				
				if($res['t1.validade_tipo'] == 'D'){
					$data_hora = data_hora_from_datetime($res['t1.validade_data']);
					$cel_aux = modelo_var_troca($cel_aux,"#validade#",$data_hora[0].' '.$data_hora[1]);
					$cel_aux = modelo_var_troca($cel_aux,"#validade_data#",$data_hora[0]);
					$cel_aux = modelo_var_troca($cel_aux,"#validade_hora#",$data_hora[1]);
				} else {
					$cel_aux = modelo_var_troca($cel_aux,"#validade#",date("d/m/Y",strtotime($data_arr[0] . " + ".$res['t1.validade']." day")));
					$cel_aux = modelo_var_troca($cel_aux,"#validade_data#",date("d/m/Y",strtotime($data_arr[0] . " + ".$res['t1.validade']." day")));
					$cel_aux = modelo_var_troca($cel_aux,"#validade_hora#",'');
				}
				
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		}
		
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

function bloqueio(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_ALERTA;
	
	if($_GET["id"]){
		$id = $_GET["id"];
		$tipo = $_GET["tipo"];
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		if($usuario['id_usuario_pai']){
			return editar();
		}
		
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id."'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		if(!$pedido){
			$_ALERTA = 'Este pedido n&atilde;o pertence a sua loja!';
			
			return lista();
		}
		
		if($tipo == '1'){
			$status = '7';
			$status_tit = 'Cancelado';
		} else {
			$status = 'A';
			$status_tit = 'Pago';
		}
		
		banco_conectar();
		banco_update
		(
			$_LISTA['tabela']['status']."='".$status."'",
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		banco_update
		(
			$_LISTA['tabela']['status']."='".$status."'",
			'pedidos_servicos',
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		log_banco(Array(
			'id_referencia' => $id,
			'grupo' => 'pedidos',
			'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> alterou o <b>status</b> para <b>'.$status_tit.'</b>',
		));
		
		banco_fechar_conexao();
	}
	
	return editar();
}

function voucher($imprimir = false){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_BANCO_PREFIXO;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_ALERTA;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$id_pedidos = $_REQUEST['id'];
	
	if($id_pedidos){
		// ============================== Pedido Atual
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
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
				'id_voucher_layouts',
				'validade',
				'validade_data',
				'validade_tipo',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND status='A'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		if($pedido){
			$pedido = $pedido[0];
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_layout']){
					$voucher = $_PROJETO['ecommerce']['voucher_layout'];
				}
			}
			if($_VARS['ecommerce']){
				if($_VARS['ecommerce']['voucher_base']){
					$voucher_base = $_VARS['ecommerce']['voucher_base'];
				}
			}
			
			if(!$voucher){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher < -->','<!-- voucher > -->');
				
				$voucher = $pagina;
			}
			if(!$voucher_base){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_base < -->','<!-- voucher_base > -->');
				
				$voucher_base = $pagina;
			}
			
			// ============================== Voucher Layout
			
			$voucher_layout = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_voucher_layouts',
					'imagem_topo',
					'imagem_textura',
					'cor_fundo',
				))
				,
				"voucher_layouts",
				"WHERE status='A'"
				." AND id_voucher_layouts='".$pedido['id_voucher_layouts']."'"
			);
			
			$voucher_layouts = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_voucher_layouts',
					'imagem_topo',
					'imagem_textura',
					'cor_fundo',
				))
				,
				"voucher_layouts",
				"WHERE status='A'"
				." ORDER BY id_voucher_layouts ASC"
			);
			
			$data_full = $pedido['data'];
			$data_arr = explode(' ',$data_full);
			
			if($pedido['validade_tipo'] == 'D'){
				$voucher_base = modelo_var_troca($voucher_base,"#data-expiracao#",data_hora_from_datetime_to_text($pedido['validade_data']));
			} else {
				$data_full = $pedido['data'];
				$data_arr = explode(' ',$data_full);
				
				if($pedido['validade']){
					$periodo = $pedido['validade'];
				} else {
					$periodo = $_ESERVICE['pedido_validade'];
				}
				
				$voucher_base = modelo_var_troca($voucher_base,"#data-expiracao#",date("d/m/Y",strtotime($data_arr[0] . " + ".$periodo." day")));
			}
			
			$voucher = modelo_var_troca($voucher,"#voucher-topo#",$voucher_topo);
			$voucher = modelo_var_troca($voucher,"#voucher-base#",$voucher_base);
			
			$voucher = modelo_var_troca($voucher,"#voucher-codigo#",$pedido['codigo']);
			$voucher = modelo_var_troca($voucher,"#voucher-senha#",$pedido['senha']);
			$voucher = modelo_var_troca($voucher,"#voucher-de#",$pedido['de']);
			$voucher = modelo_var_troca($voucher,"#voucher-para#",$pedido['para']);
			$voucher = modelo_var_troca($voucher,"#voucher-mensagem#",$pedido['mensagem']);
			$voucher = modelo_var_troca($voucher,"#voucher-concluir#",$voucher_concluir);
			
			// ============ Voucher Layout
			
			if($voucher_layout){
				$voucher = modelo_var_troca($voucher,"#imagem_topo#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_topo']);
				$voucher = modelo_var_troca_tudo($voucher,"#imagem_textura#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_textura']);
				$voucher = modelo_var_troca($voucher,"#cor_fundo#",$voucher_layout[0]['cor_fundo']);
				
				$id_voucher_layouts = $voucher_layout[0]['id_voucher_layouts'];
			} else {
				$voucher = modelo_var_troca($voucher,"#imagem_topo#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layouts[0]['imagem_topo']);
				$voucher = modelo_var_troca_tudo($voucher,"#imagem_textura#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layouts[0]['imagem_textura']);
				$voucher = modelo_var_troca($voucher,"#cor_fundo#",$voucher_layouts[0]['cor_fundo']);
				
				$id_voucher_layouts = $voucher_layouts[0]['id_voucher_layouts'];
			}
			
			$cel_nome = 'voucher-layouts'; $cel[$cel_nome] = modelo_tag_val($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			$voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' -->','');
			
			$voucher = modelo_var_troca($voucher,'#voucher-tema-id-pedido#',$id_pedidos);
			
			// ============
			
			$cel_nome = 'lista-servicos'; $cel[$cel_nome] = modelo_tag_val($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.senha',
					't1.validade_tipo',
					't1.validade',
					't1.validade_data',
					't2.nome',
					't2.observacao',
				))
				,
				"pedidos_servicos as t1,servicos as t2",
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." AND t1.status='A'"
				." ORDER BY t2.nome ASC"
			);
			
			if($pedidos_servicos)
			foreach($pedidos_servicos as $pedido_servico){
				$cel_nome = 'lista-servicos';
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#servico-nome#",$pedido_servico['t2.nome'].($pedido_servico['t2.observacao']? '<br><span style="color:#888;"><b>Observa&ccedil;&atilde;o:</b> '.$pedido_servico['t2.observacao'] .'</span>': ''));
				
				
				if($pedido_servico['t1.validade_tipo'] == 'D'){
					$cel_aux = modelo_var_troca($cel_aux,"#servico-validade#",data_hora_from_datetime_to_text($pedido_servico['t1.validade_data']));
				} else {						
					$cel_aux = modelo_var_troca($cel_aux,"#servico-validade#",date("d/m/Y",strtotime($data_arr[0] . " + ".$pedido_servico['t1.validade']." day")));
				}
				
				$cel_aux = modelo_var_troca($cel_aux,"#servico-validade#",date("d/m/Y",strtotime($data_arr[0] . " + ".$pedido_servico['t1.validade']." day")));
				$cel_aux = modelo_var_troca($cel_aux,"#servico-codigo#",$pedido_servico['t1.codigo']);
				$cel_aux = modelo_var_troca($cel_aux,"#servico-senha#",$pedido_servico['t1.senha']);
				
				$voucher = modelo_var_in($voucher,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' -->','');
			
			$voucher_impressao = $voucher;
			$cel_nome = 'remover_impressao'; $voucher_impressao = modelo_tag_in($voucher_impressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			if(!$pedido['presente']){
				$cel_nome = 'de-para'; $voucher_impressao = modelo_tag_in($voucher_impressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				$cel_nome = 'de-para'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			} else {
				$cel_nome = 'de-para'; $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' < -->',''); $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' > -->','');
			}
			
			$_SESSION[$_SYSTEM['ID']."versao-impressao"] = $voucher_impressao;
			
			$cel_nome = 'remover_impressao'; $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' < -->',''); $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' > -->','');
			
			$menu_opcao = '<table width="100%" cellpadding="0" cellspacing="0">
	<tbody><tr>
		<td class="in_fundo"></td>
		<td class="in_fundo"></td>
		<td class="in_top_dir"></td>
	</tr>
	<tr>
		<td class="in_fundo">&nbsp;</td>
		<td class="in_fundo">
		#menu_opcao#
		</td>
		<td class="in_meio_dir">&nbsp;</td>
	</tr>
	<tr>
		<td class="in_inf_esq">&nbsp;</td>
		<td class="in_inf_meio">&nbsp;</td>
		<td class="in_inf_dir">&nbsp;</td>
	</tr>
</tbody></table>
<h2>Voucher</h2>';
			if($imprimir){
				$menu_opcao = modelo_var_troca($menu_opcao,"#menu_opcao#",'<img src="../../images/icons/imprimir.png" id="imprimir">');
			} else {
				$menu_opcao = modelo_var_troca($menu_opcao,"#menu_opcao#",'email');
			}
			
			$voucher = $menu_opcao . $voucher;
			
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
			$_INTERFACE['inclusao'] = $voucher;
		
			return interface_layout(parametros_interface());
		} else {
			$_ALERTA .= '<p style="color:red;">O seu pedido não está ativo</p><p>Houve algum problema com o pagamento ou o mesmo está em processamento.</p><p>Se você efetuou o pagamento e houve confirmação, aguarde no máximo 30 minutos até o sistema atualizar os pagamentos e tente novamente. De qualquer forma o sistema enviará automaticamente o seu voucher no seu email assim que houver confirmação de pagamento pelo sistema de pagamento escolhido.</p>';
			
			return lista();
		}
	} else
		return lista();
}

function export(){
	global $_SYSTEM;
	global $_LISTA;
	global $_PROJETO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	// =============================== Filtro ====================== < =
	
	$filtro = $_SESSION[$_SYSTEM['ID']."pedidos-filtro"];
	
	if($filtro['filtro-data-1']) $filtro_where = " AND t1.data >= '".data_padrao_date($filtro['filtro-data-1'])."'";
	if($filtro['filtro-data-2']) $filtro_where .= " AND t1.data <= '".data_padrao_date($filtro['filtro-data-2'])."'";
	if($filtro['filtro-status'] && $filtro['filtro-status'] != '-1') $filtro_where .= " AND t1.status = '".$filtro['filtro-status']."'";
	if($filtro['filtro-servicos'] && $filtro['filtro-servicos'] != '-1'){
		$tabela_relacionada_where .= " AND t1.id_pedidos=t2.id_pedidos AND t2.id_servicos='".$filtro['filtro-servicos']."'";
		$tabela_relacionada_group_by = " GROUP BY t1.".$_LISTA['tabela']['campo'];
	}
	
	// =============================== Filtro ====================== > =
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.'.'id_pedidos',
			't1.'.'codigo',
			't1.'.'senha',
			't1.'.'data',
			't1.'.'valor_total',
			't1.'.'status',
		))
		,
		'pedidos AS t1'.($tabela_relacionada_where ? ', pedidos_servicos AS t2' : ''),
		"WHERE t1.id_loja='".$usuario['id_loja']."'"
		.($_REQUEST['only-paid'] ? " AND t1.status='A'" : '')
		.$filtro_where.$tabela_relacionada_where.$tabela_relacionada_group_by
	);
	
	$status_mudar = $_PROJETO['B2MAKE_STORE_STATUS_MUDAR_TITULO'];
	
	$pedidos[] = Array(
		'Codigo',
		'Data',
		'Valor Total',
		'Status',
		'Cliente Nome Completo',
		'Cliente Nome',
		'Cliente Último Nome',
		'Cliente CPF',
		'Cliente CNPJ',
		'Cliente Telefone',
		'Cliente Email',
		'>>>',
		'Codigo',
		'Serviço Nome',
		'Identificação Nome',
		'Identificação Documento',
		'Identificação Telefone',
		'Sub Total',
		'Status',
	);
	
	if($resultado)
	foreach($resultado as $res){
		$loja_usuarios_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_loja_usuarios',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_pedidos='".$res['t1.id_pedidos']."'"
		);
		
		$usuario_bd = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'ultimo_nome',
				'email',
				'cpf',
				'cnpj',
				'telefone',
			))
			,
			"loja_usuarios",
			"WHERE id_loja_usuarios='".$loja_usuarios_pedidos[0]['id_loja_usuarios']."'"
		);
		
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_servicos',
				'codigo',
				'senha',
				'sub_total',
				'status',
				'identificacao_nome',
				'identificacao_documento',
				'identificacao_telefone',
			))
			,
			"pedidos_servicos",
			"WHERE id_pedidos='".$res['t1.id_pedidos']."'"
		);

		foreach($status_mudar as $chave => $valor){
			if($res['t1.status'] == $chave){
				$res['t1.status'] = $valor;
				break;
			}
		}
		
		if($pedidos_servicos)
		foreach($pedidos_servicos as $res2){
			$servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				"servicos",
				"WHERE id_servicos='".$res2['id_servicos']."'"
			);
			
			foreach($status_mudar as $chave => $valor){
				if($res2['status'] == $chave){
					$res2['status'] = $valor;
					break;
				}
			}
			
			$pedidos[] = Array(
				$res['t1.codigo'],
				data_from_datetime_to_text($res['t1.data']),
				preparar_float_4_texto($res['t1.valor_total']),
				$res['t1.status'],
				$usuario_bd[0]['nome'].' '.$usuario_bd[0]['ultimo_nome'],
				$usuario_bd[0]['nome'],
				$usuario_bd[0]['ultimo_nome'],
				$usuario_bd[0]['cpf'],
				$usuario_bd[0]['cnpj'],
				$usuario_bd[0]['telefone'],
				$usuario_bd[0]['email'],
				'>>>',
				$res2['codigo'],
				$servicos[0]['nome'],
				$res2['identificacao_nome'],
				$res2['identificacao_documento'],
				$res2['identificacao_telefone'],
				preparar_float_4_texto($res2['sub_total']),
				$res2['status'],
			);
		}
	}
	
	$array = $pedidos;

	header("Content-Disposition: attachment; filename=\"Pedidos-Exportados-".date('d-m-Y H-i-s').".xls\"");
	header("Content-Type: application/vnd.ms-excel;");
	header("Pragma: no-cache");
	header("Expires: 0");
	$out = fopen("php://output", 'w');
	foreach ($array as $data)
	{
		fputcsv($out, $data,"\t");
	}
	fclose($out);
	
	exit;
}

// ======================================================================================

function senha_gerar2($limite){
	$CaracteresAceitos = 'abcdefghijklmnopqrstuvxywzABCDEFGHIJKLMNOPQRSTUVXYWZ';
	$CaracteresAceitos_especiais = '@*';
	$CaracteresAceitos2 = '0123456789';
	
	$max = strlen($CaracteresAceitos)-1;
	$max2 = strlen($CaracteresAceitos2)-1;
	$max3 = strlen($CaracteresAceitos_especiais)-1;

	$password = null;

	for($i=0; $i < $limite; $i++) {
		if($i==0){
			$password .= $CaracteresAceitos{mt_rand(0, $max)};
		} else {
			if(mt_rand(0, 7) == 7)
				$password .= $CaracteresAceitos_especiais{mt_rand(0, $max3)};
			else if(mt_rand(0, 3) == 3)
				$password .= $CaracteresAceitos2{mt_rand(0, $max2)};
			else
				$password .= $CaracteresAceitos{mt_rand(0, $max)};
		}
	}

	return $password;
}

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
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = $_REQUEST["query"];
		if(!$query) return;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		banco_conectar();
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.codigo',
				't4.codigo',
				't3.nome',
				't3.ultimo_nome',
				't3.email',
				't1.id_pedidos',
			))
			,
			"pedidos as t1,loja_usuarios_pedidos as t2,loja_usuarios as t3,pedidos_servicos as t4",
			"WHERE (
				(UCASE(t1.codigo) LIKE UCASE('%" . $query . "%'))
				OR 
				(UCASE(t4.codigo) LIKE UCASE('%" . $query . "%'))
				OR 
				(UCASE(t3.nome) LIKE UCASE('%" . $query . "%'))
			)"
			." AND t1.id_pedidos=t4.id_pedidos"
			." AND t1.id_pedidos=t2.id_pedidos"
			." AND t2.id_loja_usuarios=t3.id_loja_usuarios"
			." AND t1.status!='D'"
			." AND t1.id_loja='".$usuario['id_loja']."'"
		);
		
		banco_fechar_conexao();

		if($resultado)
		foreach($resultado as $res){
			$saida[] = Array(
				'value' => $res['t1.codigo'].' - '.$res['t4.codigo'].' - '.$res['t3.nome'].' '.$res['t3.ultimo_nome'].' <'.$res['t3.email'].'>',
				'id' => $res['t1.id_pedidos'],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['mudar_envio']){
		banco_conectar();
		if(operacao('editar')){
			$status_envio = $_REQUEST['opcao'];
			$codigo_rastreio = $_REQUEST['codigo'];
			$id = $_REQUEST['id'];
			
			if($status_envio && $id){
				$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
				
				banco_update
				(
					($codigo_rastreio?"codigo_rastreio='".$codigo_rastreio."',":"").
					"status_envio='".$status_envio."'",
					"pedidos",
					"WHERE id_pedidos='".$id."'"
					." AND id_loja='".$usuario['id_loja']."'"
				);
				
				// =========== Notificar cliente
				
				global $_ECOMMERCE;
				global $_VARS;
				global $_HTML;
				
				$_ECOMMERCE['apenas_incluir'] = true;
				
				require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/index.php');
				
				if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						't3.nome',
						't3.email',
						't1.codigo',
					))
					,
					"pedidos as t1,loja_usuarios_pedidos as t2,loja_usuarios as t3",
					"WHERE t1.id_pedidos='".$id."'"
					." AND t1.id_pedidos=t2.id_pedidos"
					." AND t2.id_loja_usuarios=t3.id_loja_usuarios"
					." AND t1.id_loja='".$usuario['id_loja']."'"
				);
				
				if($resultado){
					$nome = $resultado[0]['t3.nome'];
					$email = $resultado[0]['t3.email'];
					$codigo = $resultado[0]['t1.codigo'];
					
					$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
					
					$parametros['from_name'] = $_HTML['TITULO'];
					$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
					
					$parametros['email_name'] = $nome;
					$parametros['email'] = $email;
					
					$parametros['subject'] = $_VARS['ecommerce']['pagseguro_notificacoes_assunto'];
					
					$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#codigo#",$codigo);
					
					switch($status_envio){
						case 'E':
							$titulo = "Enviado";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_enviado'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						case 'F':
							$titulo = "Entregue";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_entregue'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						case 'N':
							$titulo = "Não enviado";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_nao_enviado'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						case 'M':
							$titulo = "Retirado em Mãos";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['envio_notificacoes_retirado_em_maos'];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						break;
						
					}
					
					$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
					
					log_banco(Array(
						'id_referencia' => $id,
						'grupo' => 'pedidos',
						'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> alterou o <b>status de envio</b> para <b>'.$titulo.'</b>'.($codigo_rastreio?' - codigo_rastreio: <b>'.$codigo_rastreio.'</b>':''),
					));
					
					if($codigo_rastreio)$parametros['mensagem'] .= '<p>Se desejar rastrear o seu pedido acesso o site dos correios (<a href="http://www.correios.com.br/">http://www.correios.com.br/</a>) e utilize o seguinte código de rastreio: <b>'.$codigo_rastreio.'</b></p>';
					$parametros['mensagem'] .= ecommerce_pagseguro_lista_produtos($id);
					$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
					
					if($parametros['enviar_mail'])enviar_mail($parametros);
				} else {
					$saida = '<p>Esse pedido não pertence ao seu usuário.</p>';
				}
			} else {
				$saida = '<p>Houve um problema com a referência do pedido e opção. Contate o suporte técnico.</p>';
			}
		} else {
			$saida = '<p>Você não tem permissão de mudar o envio.</p>';
		}
	}
	
	if($_REQUEST['validade']){
		banco_conectar();
		if(operacao('editar')){
			
			$validade_tipo = $_REQUEST['validade_tipo'];
			$validade_hora = $_REQUEST['validade_hora'];
			
			if($validade_tipo == 'D'){
				$hora_arr = explode(':',$validade_hora);
				
				$total_segundos_horario = (int)$hora_arr[0]*3600 + (int)$hora_arr[1]*60 + (int)$hora_arr[2];
				
				$validade = floor((strtotime(data_padrao_date($_REQUEST['validade']))/86400 + $total_segundos_horario/86400 - strtotime($_REQUEST['data_pedido'])/86400));
			} else {
				$validade = floor((strtotime(data_padrao_date($_REQUEST['validade']))/86400 - strtotime($_REQUEST['data_pedido'])/86400));
			}
			
			if($validade >= 0){
				$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos',
					))
					,
					"pedidos",
					"WHERE codigo='".$_REQUEST['pedido_id']."'"
					." AND id_loja='".$usuario['id_loja']."'"
				);
				
				if($resultado){
					if($validade_tipo == 'D'){
						banco_update
						(
							"validade_data='".data_padrao_date($_REQUEST['validade']).' '.$_REQUEST['validade_hora']."'",
							"pedidos_servicos",
							"WHERE codigo='".$_REQUEST['id']."'"
						);
					} else {
						banco_update
						(
							"validade='".$validade."'",
							"pedidos_servicos",
							"WHERE codigo='".$_REQUEST['id']."'"
						);
					}
					
					log_banco(Array(
						'id_referencia' => $resultado[0]['id_pedidos'],
						'grupo' => 'pedidos',
						'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> alterou a validade para '.$validade.' dia(s) - '.$_REQUEST['validade'].' '.$_REQUEST['validade_hora'],
					));
				} else {
					$saida = '<p>Esse pedido n&atilde;o pertence ao seu usu&aacute;rio.</p>';
				}
			} else {
				$saida = '<p>Não é possível criar uma validade de uso de data do passado.</p>';
			}
		} else {
			$saida = '<p>Você não tem permissão de mudar a validade de uso.</p>';
		}
	}
	
	if($_REQUEST['baixar_voucher']){
		banco_conectar();
		if(operacao('editar')){
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$pedido_id = $_REQUEST['pedido_id'];
			$pedido_servico_id = $_REQUEST['pedido_servico_id'];
			$observacao = $_REQUEST['observacao'];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$pedido_id."'"
				." AND id_loja='".$usuario['id_loja']."'"
			);
			
			if($resultado){
				$id_usuario_baixa = $usuario['id_usuario'];
				$protocolo = md5(senha_gerar2('50'));
				
				banco_update
				(
					"status='F',".
					"id_usuario_baixa='".$id_usuario_baixa."',".
					"observacao_baixa='".$observacao."',".
					"data_baixa=NOW(),".
					"protocolo_baixa='".$protocolo."'",
					"pedidos_servicos",
					"WHERE id_pedidos_servicos='".$pedido_servico_id."'"
				);
				
				$pedidos_servicos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'codigo',
					))
					,
					"pedidos_servicos",
					"WHERE id_pedidos_servicos='".$pedido_servico_id."'"
				);
				
				$codigo = $pedidos_servicos[0]['codigo'];
				
				$resultado2 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos_servicos',
					))
					,
					"pedidos_servicos",
					"WHERE id_pedidos='".$pedido_id."'"
					." AND status='A'"
				);
				
				if(!$resultado2){
					banco_update
					(
						"status='F',".
						"id_usuario_baixa='".$id_usuario_baixa."',".
						"observacao_baixa='".$observacao."',".
						"data_baixa=NOW(),".
						"protocolo_baixa='".$protocolo."'",
						"pedidos",
						"WHERE id_pedidos='".$pedido_id."'"
					);
				}
				
				log_banco(Array(
					'id_referencia' => $pedido_id,
					'grupo' => 'pedidos',
					'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> baixou o serviço de código: <b>'.$codigo.'</b> - protocolo: <b>'.$protocolo.'</b>',
				));
			} else {
				$saida = '<p>Esse pedido n&atilde;o pertence a sua loja.</p>';
			}
		} else {
			$saida = '<p>Você não tem permissão para baixar voucher.</p>';
		}
	}
	
	if($_REQUEST['identidade']){
		banco_conectar();
		if(operacao('editar')){
			$id_pedidos = $_REQUEST['id_pedidos'];
			$id_pedidos_servicos = $_REQUEST['id_pedidos_servicos'];
			$identificacao_nome = $_REQUEST['nome'];
			$identificacao_documento = $_REQUEST['doc'];
			$identificacao_telefone = $_REQUEST['tel'];
			
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
				." AND id_loja='".$usuario['id_loja']."'"
			);
			
			if($resultado){
				$campo_tabela = "pedidos_servicos";
				$campo_tabela_extra = "WHERE id_pedidos='".$id_pedidos."'"
				." AND id_pedidos_servicos='".$id_pedidos_servicos."'";
				
				$campo_nome = "identificacao_nome"; $campo_valor = $identificacao_nome; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "identificacao_documento"; $campo_valor = $identificacao_documento; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "identificacao_telefone"; $campo_valor = $identificacao_telefone; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				
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
				
				$pedidos_servicos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'codigo',
					))
					,
					$campo_tabela,
					$campo_tabela_extra
				);
				
				log_banco(Array(
					'id_referencia' => $resultado[0]['id_pedidos'],
					'grupo' => 'pedidos',
					'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> alterou a identidade do serviço <b>['.$pedidos_servicos[0]['codigo'].']</b> para nome: '.$identificacao_nome.', documento: '.$identificacao_documento.' e telefone: '.$identificacao_telefone.'.',
				));
			} else {
				$saida = '<p>Esse pedido n&atilde;o pertence ao seu usu&aacute;rio.</p>';
			}
		} else {
			$saida = '<p>Você não tem permissão de mudar a identidade.</p>';
		}
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
			case 'busca_ver':
			case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			case 'imprimir-voucher':			$saida = voucher(true);break;
			case 'email-voucher':				$saida = voucher();break;
			case 'export':						$saida = export();break;
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