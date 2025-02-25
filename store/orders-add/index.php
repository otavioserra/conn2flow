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
$_LOCAL_ID					=	"orders-add";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_LOJA				=	true;
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

$_HTML['titulo'] 						= 	$_HTML['titulo']."Criar Pedidos.";
$_HTML['variaveis']['titulo-modulo']	=	'Criar Pedidos';

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'];

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
$_LISTA['ferramenta']				=	'Criar Pedidos';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

$_VARIAVEIS_JS['gestor_opcao_editar_url'] = '../orders/?opcao=ver&id=';

// Funções de assistência

// Funções do Sistema

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
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' DESC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'valor_total';
	$tabela_campos[] = 'data';
	$tabela_campos[] = 'status';
	$tabela_campos[] = 'cortesia';
	
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
		}
		if(operacao('bloquear')){
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
	
	if(operacao('ver')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => '../orders/?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
		);
	}
	
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => $_URL . '?opcao=imprimir-voucher&id=#id', // link da opção
		'title' => 'Imprimir Voucher', // título da opção
		'img_coluna' => 15, // Coluna background image
		'img_linha' => 1, // Linha background image
		'legenda' => 'Imprimir Voucher', // Legenda
	);
	
	/* $menu_opcoes[] = Array( // Opção: Bloquear
		'url' => $_URL . '?opcao=email-voucher&id=#id', // link da opção
		'title' => 'Email Voucher', // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'url.png', // caminho da imagem
		'legenda' => 'Email Voucher', // Legenda
	); */
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Identificador', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => '60', // OPCIONAL - Tamanho horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Codigo', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Valor', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'dinheiro_reais' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data Hora', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'data_hora' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'campo' => 'Status', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
		'valor_padrao_se_nao_existe' => '<span style="color:red;">Não Definido</span>', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => $_PROJETO['B2MAKE_STORE_STATUS_2'],
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'campo' => 'Cortesia', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
		'valor_padrao_se_nao_existe' => '<span class="cortesia_nao">Não</span>', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => Array(
			'1' => '<span class="cortesia_sim">Sim</span>',
		),
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
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_loja='".$usuario['id_loja']."' AND interno IS NOT NULL ", // Tabela extra
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

function add_ant(){
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
	
	$pagina = paginaTrocaVarValor($pagina,'#lista-servicos#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#layout#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#layout_id#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#de#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#para#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#mensagem#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#chk_cortesia#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#chk_presente#',$valor);
	
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

function add_base_ant(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_USUARIO_ATIVO_2;
	global $_LISTA;
	global $_INTERFACE;
	global $_ALERTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	banco_conectar();
	
	$itens_arr = explode(',',$_REQUEST['lista-servicos']);
	
	if($itens_arr){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		// ============================== Verificar se tem quantidade em estoque de todos os itens
		
		if($itens_arr)
		foreach($itens_arr as $id_servicos){
			$quant = $_REQUEST['servico-quant-'.$id_servicos];
			
			$pertence = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id_servicos."'"
				." AND id_loja='".$usuario['id_loja']."'"
			);
			
			if(!$pertence){
				$_ALERTA .= '<p><b style="color:red;">Não foi possível registrar seu pedido</b></p><p>Serviço(s) não pertencem ao seu usuário.</p>';
				
				return add();
			}
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id_servicos."'"
				." AND quantidade - ".$quant." >= 0"
				." AND status='A'"
			);
			
			if($resultado){
				
			} else {
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
					))
					,
					"servicos",
					"WHERE id_servicos='".$id_servicos."'"
				);
				
				$flag = true;
				$lista_sem_estoque .= '<li>'.$resultado[0]['nome'].'</li>'."\n";
			}
		}
		
		if($flag){
			$_ALERTA .= '<p><b style="color:red;">Não foi possível registrar seu pedido</b></p><p>Serviço(s) indisponíveis no momento:</p><ul>'.$lista_sem_estoque.'</ul>';
			
			return add();
		} else {
			// ============================== Cadastrar pedido
			$campos = null;
			
			$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "interno"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "cortesia"; if($_REQUEST['cortesia']){$campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "presente"; if($_REQUEST['presente']){$campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "de"; if($_REQUEST[$campo_nome]){$campo_valor = $_REQUEST[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "para"; if($_REQUEST[$campo_nome]){$campo_valor = $_REQUEST[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "mensagem"; if($_REQUEST[$campo_nome]){$campo_valor = $_REQUEST[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "id_loja"; $campo_valor = $usuario['id_loja']; 			$campos[] = Array($campo_nome,$campo_valor);
			$campo_nome = "id_voucher_layouts"; if($_REQUEST['layout_id']){$campo_valor = $_REQUEST['layout_id']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			banco_insert_name
			(
				$campos,
				"pedidos"
			);
			
			$id_pedidos = banco_last_id();
			
			$valor_total = 0;
			
			// ============================== Cadastrar itens do pedido
			
			foreach($itens_arr as $id_servicos){
				$quant = $_REQUEST['servico-quant-'.$id_servicos];
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'preco',
						'validade',
						'validade_tipo',
						'validade_data',
						'desconto',
						'desconto_de',
						'desconto_ate',
					))
					,
					"servicos",
					"WHERE id_servicos='".$id_servicos."'"
				);
				
				banco_update
				(
					"quantidade = (quantidade - ".$quant.")",
					"servicos",
					"WHERE id_servicos='".$id_servicos."'"
				);
				
				$time = time();
				$desconto_de_ate = false;
				if($resultado[0]['desconto_de']){
					$desconto_de_ate = true;
					$de = strtotime($resultado[0]['desconto_de']);
					
					if($time < $de){
						$desconto_de_ate = false;
					}
				}
				
				if($resultado[0]['desconto_ate']){
					$desconto_de_ate = true;
					$ate = strtotime($resultado[0]['desconto_ate']);
					
					if($time > $ate){
						$desconto_de_ate = false;
					}
				}
				
				$validade = (int)$resultado[0]['validade'];
				$validade_data = $resultado[0]['validade_data'];
				$validade_tipo = $resultado[0]['validade_tipo'];
				(int)$quant;
				
				if($desconto_de_ate){
					$desconto = (float)$resultado[0]['desconto'];
					$valor_original = (float)$resultado[0]['preco'];
					
					$preco = (($valor_original * (100 - $desconto)) / 100);
					
					$sub_total = $preco*$quant;
					$valor_total = $valor_total + $sub_total;
				} else {
					$desconto = false;
					$valor_original = false;
					
					$preco = (float)$resultado[0]['preco'];
					
					$sub_total = $preco*$quant;
					$valor_total = $valor_total + $sub_total;
				}
				
				for($i=0;$i<$quant;$i++){
					$campos = null;
					
					$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_servicos"; $campo_valor = $id_servicos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "quantidade"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "desconto"; $campo_valor = $desconto; 		if($desconto)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "validade"; $campo_valor = $validade; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "validade_data"; $campo_valor = $validade_data; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "validade_tipo"; $campo_valor = $validade_tipo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "valor_original"; if($valor_original){$campo_valor = number_format($valor_original, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
					$campo_nome = "sub_total"; $campo_valor = number_format($preco, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"pedidos_servicos"
					);
					
					$id_pedidos_servicos = banco_last_id();
					
					banco_update
					(
						"codigo='S".((int)$id_pedidos_servicos + 1000)."'",
						"pedidos_servicos",
						"WHERE id_pedidos_servicos='".$id_pedidos_servicos."'"
					);
				}
			}
			
			// ============================== Gerar código do pedido e atualizar valor total
			
			banco_update
			(
				"valor_total='".number_format($valor_total, 2, ".", "")."',".
				"codigo='E".((int)$id_pedidos+1000)."'",
				"pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
			);
			
			// ============================== Vincular pedido com o usuário
			
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$id_loja_usuarios = $usuario['id_loja_usuarios'];
			
			banco_update
			(
				"pedido_atual=NULL",
				"loja_usuarios_pedidos",
				"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
			);
			
			$campos = null;
			
			$campo_nome = "id_loja_usuarios"; $campo_valor = $id_loja_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_loja"; $campo_valor = $usuario['id_loja']; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "pedido_atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"loja_usuarios_pedidos"
			);
			
			$_INTERFACE['menu_paginas_reiniciar'] = true;
			
			return lista();
		}
	} else {
		$_INTERFACE['menu_paginas_reiniciar'] = true;
		
		return lista();
	}
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
	$pagina = paginaTagValor($pagina,'<!-- form-2 < -->','<!-- form-2 > -->');
	
	// ================================= Local de Edição ===============================
	// Altere os campos da interface com os valores iniciais
	
	$pagina = paginaTrocaVarValor($pagina,'#lista-servicos#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#layout#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#layout_id#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#de#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#para#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#mensagem#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#chk_cortesia#',$valor);
	$pagina = paginaTrocaVarValor($pagina,'#chk_presente#',$valor);
	
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
	global $_ALERTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	banco_conectar();
	
	$itens_arr = explode(',',$_REQUEST['lista-servicos']);
	
	if($itens_arr){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		// ============================== Verificar se tem quantidade em estoque de todos os itens
		
		if($itens_arr)
		foreach($itens_arr as $id_servicos){
			$quant = $_REQUEST['servico-quant-'.$id_servicos];
			
			$pertence = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id_servicos."'"
				." AND id_loja='".$usuario['id_loja']."'"
			);
			
			if(!$pertence){
				$_ALERTA .= '<p><b style="color:red;">Não foi possível registrar seu pedido</b></p><p>Serviço(s) não pertencem ao seu usuário.</p>';
				
				return add();
			}
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id_servicos."'"
				." AND quantidade - ".$quant." >= 0"
				." AND status='A'"
			);
			
			if($resultado){
				
			} else {
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
					))
					,
					"servicos",
					"WHERE id_servicos='".$id_servicos."'"
				);
				
				$flag = true;
				$lista_sem_estoque .= '<li>'.$resultado[0]['nome'].'</li>'."\n";
			}
		}
		
		if($flag){
			$_ALERTA .= '<p><b style="color:red;">Não foi possível registrar seu pedido</b></p><p>Serviço(s) indisponíveis no momento:</p><ul>'.$lista_sem_estoque.'</ul>';
			
			return add();
		} else {
			// ============================== Cadastrar pedido
			$campos = null;
			
			$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			//$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "interno"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "cortesia"; if($_REQUEST['cortesia']){$campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "presente"; if($_REQUEST['presente']){$campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "de"; if($_REQUEST[$campo_nome]){$campo_valor = $_REQUEST[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "para"; if($_REQUEST[$campo_nome]){$campo_valor = $_REQUEST[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "mensagem"; if($_REQUEST[$campo_nome]){$campo_valor = $_REQUEST[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "id_loja"; $campo_valor = $usuario['id_loja']; 			$campos[] = Array($campo_nome,$campo_valor);
			$campo_nome = "id_voucher_layouts"; if($_REQUEST['layout_id']){$campo_valor = $_REQUEST['layout_id']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			banco_insert_name
			(
				$campos,
				"pedidos"
			);
			
			$id_pedidos = banco_last_id();
			
			$valor_total = 0;
			
			// ============================== Cadastrar itens do pedido
			
			foreach($itens_arr as $id_servicos){
				$quant = $_REQUEST['servico-quant-'.$id_servicos];
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'preco',
						'validade',
						'validade_tipo',
						'validade_data',
						'desconto',
						'desconto_de',
						'desconto_ate',
					))
					,
					"servicos",
					"WHERE id_servicos='".$id_servicos."'"
				);
				
				banco_update
				(
					"quantidade = (quantidade - ".$quant.")",
					"servicos",
					"WHERE id_servicos='".$id_servicos."'"
				);
				
				$time = time();
				$desconto_de_ate = false;
				if($resultado[0]['desconto_de']){
					$desconto_de_ate = true;
					$de = strtotime($resultado[0]['desconto_de']);
					
					if($time < $de){
						$desconto_de_ate = false;
					}
				}
				
				if($resultado[0]['desconto_ate']){
					$desconto_de_ate = true;
					$ate = strtotime($resultado[0]['desconto_ate']);
					
					if($time > $ate){
						$desconto_de_ate = false;
					}
				}
				
				$validade = (int)$resultado[0]['validade'];
				$validade_data = $resultado[0]['validade_data'];
				$validade_tipo = $resultado[0]['validade_tipo'];
				(int)$quant;
				
				if($desconto_de_ate){
					$desconto = (float)$resultado[0]['desconto'];
					$valor_original = (float)$resultado[0]['preco'];
					
					$preco = (($valor_original * (100 - $desconto)) / 100);
					
					$sub_total = $preco*$quant;
					$valor_total = $valor_total + $sub_total;
				} else {
					$desconto = false;
					$valor_original = false;
					
					$preco = (float)$resultado[0]['preco'];
					
					$sub_total = $preco*$quant;
					$valor_total = $valor_total + $sub_total;
				}
				
				for($i=0;$i<$quant;$i++){
					$campos = null;
					
					$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_servicos"; $campo_valor = $id_servicos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					//$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "quantidade"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "desconto"; $campo_valor = $desconto; 		if($desconto)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "validade"; $campo_valor = $validade; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "validade_data"; $campo_valor = $validade_data; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "validade_tipo"; $campo_valor = $validade_tipo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "valor_original"; if($valor_original){$campo_valor = number_format($valor_original, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
					$campo_nome = "sub_total"; $campo_valor = number_format($preco, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"pedidos_servicos"
					);
					
					$id_pedidos_servicos = banco_last_id();
					
					banco_update
					(
						"codigo='S".((int)$id_pedidos_servicos + 1000)."'",
						"pedidos_servicos",
						"WHERE id_pedidos_servicos='".$id_pedidos_servicos."'"
					);
				}
			}
			
			// ============================== Gerar código do pedido e atualizar valor total
			
			banco_update
			(
				"valor_total='".number_format($valor_total, 2, ".", "")."',".
				"codigo='E".((int)$id_pedidos+1000)."'",
				"pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
			);
			
			// ============================== Vincular pedido com o usuário
			
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$id_loja_usuarios = $usuario['id_loja_usuarios'];
			
			banco_update
			(
				"pedido_atual=NULL",
				"loja_usuarios_pedidos",
				"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
			);
			
			$campos = null;
			
			$campo_nome = "id_loja_usuarios"; $campo_valor = $id_loja_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_loja"; $campo_valor = $usuario['id_loja']; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "pedido_atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"loja_usuarios_pedidos"
			);
			
			$_INTERFACE['menu_paginas_reiniciar'] = true;
			
			header('Location: ../orders/?opcao=ver&id='.$id_pedidos);
		}
	} else {
		$_INTERFACE['menu_paginas_reiniciar'] = true;
		
		return lista();
	}
}

// ======================================================================================

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
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
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D' AND interno IS NOT NULL "
			." AND id_loja='".$usuario['id_loja']."'"
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
	
	if($_REQUEST['query_id'] == 'servicos' && operacao('buscar')){
		$query = $_REQUEST["query"];
		if(!$query) return;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];

		banco_conectar();
		
		$resultado = banco_select
		(
			"id_servicos,nome",
			"servicos",
			"WHERE UCASE(nome) LIKE UCASE('%" . $query . "%') AND status='A'"
			." AND id_loja='".$usuario['id_loja']."'"
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
	
	if($_REQUEST['query_id'] == 'layout' && operacao('buscar')){
		$query = $_REQUEST["query"];
		if(!$query) return;

		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		banco_conectar();
		
		$resultado = banco_select
		(
			"id_voucher_layouts,nome",
			"voucher_layouts",
			"WHERE UCASE(nome) LIKE UCASE('%" . $query . "%') AND status='A'"
			." AND id_loja='".$usuario['id_loja']."'"
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
	
	if($_REQUEST['email']){
		if($_REQUEST['email'] != $_REQUEST['edit_email']){
			banco_conectar();
			
			$resultado = banco_select
			(
				"id_usuario",
				"usuario",
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
			//case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'busca_ver':					header('Location: ../orders/?opcao=ver&id='.$_REQUEST['id']); break;
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