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

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"orders-add";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_LOJA				=	true;
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

$_HTML['titulo'] 						= 	$_HTML['titulo']."Pedidos Criar.";
$_HTML['variaveis']['titulo-modulo']	=	'Pedidos Criar';

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
"<script type=\"text/javascript\" src=\"../js.js?v=".$_VERSAO_MODULO."\"></script>\n".
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= 
"<link href=\"../css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"<link href=\"../../includes/ecommerce/css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'pedidos';
$_LISTA['tabela']['campo']			=	'codigo';
$_LISTA['tabela']['id']				=	'id_'.'pedidos';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Pedidos Internos';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

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
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'valor_total';
	$tabela_campos[] = 'data';
	$tabela_campos[] = 'status';
	$tabela_campos[] = 'cortesia';
	
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
		'dinheiro' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'data_hora' => true,
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
		'valor_padrao_se_nao_existe' => '<span style="color:red;">Não Definido</span>', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => Array(
			'5' => '<span style="color:red;">Em disputa</span>',
			'6' => '<span style="color:brown;">Dinheiro Devolvido</span>',
			'7' => '<span style="color:brown;">Cancelado</span>',
			'F' => '<span style="color:brown;">Finalizado</span>',
			'A' => '<span style="color:green;">Pago</span>',
			'B' => '<span style="color:red;">Bloqueado</span>',
			'D' => '<span style="color:red;">Deletado</span>',
			'N' => '<span style="color:blue;">Aguardando pagamento</span>',
			'P' => '<span style="color:blue;">Em análise</span>',
		),
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'campo' => 'Cortesia', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => 'center', // OPCIONAL - alinhamento horizontal
		'padrao_se_nao_existe' => true, // OPCIONAL - alinhamento horizontal
		'valor_padrao_se_nao_existe' => '<span style="color:blue;">Não</span>', // OPCIONAL - alinhamento horizontal
		'mudar_valor_array' => Array(
			'1' => '<span style="color:green;">Sim</span>',
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
			
			$id_usuario = $usuario['id_usuario'];
			
			banco_update
			(
				"pedido_atual=NULL",
				"usuario_pedidos",
				"WHERE id_usuario='".$id_usuario."'"
			);
			
			$campos = null;
			
			$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "pedido_atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"usuario_pedidos"
			);
			
			$_INTERFACE['menu_paginas_reiniciar'] = true;
			
			return lista();
		}
	} else {
		$_INTERFACE['menu_paginas_reiniciar'] = true;
		
		return lista();
	}
}

function editar($param = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
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
				'email',
				'opt_in',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$campos_guardar = Array(
			'nome' => $tabela[0]['nome'],
			'email' => $tabela[0]['email'],
		);
		
		$pagina = paginaTrocaVarValor($pagina,'#nome',$tabela[0]['nome']);
		$pagina = paginaTrocaVarValor($pagina,'#email',$tabela[0]['email']);
		$pagina = paginaTrocaVarValor($pagina,'#edit_email',$tabela[0]['email']);
		$pagina = paginaTrocaVarValor($pagina,'#data',data_hora_from_datetime_to_text($tabela[0]['opt_in']));
		
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
		$campo_nome = "nome";				 		if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "email";						if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
		$campo_nome = "opt_in"; 						$editar['tabela'][] = $campo_nome."=NOW()";
	
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
					'versao',
				))
				,
				"voucher_layouts",
				"WHERE status='A'"
				." AND id_voucher_layouts='".$pedido['id_voucher_layouts']."'"
			);
			
			$data_full = $pedido['data'];
			$data_arr = explode(' ',$data_full);
			
			$voucher_base = modelo_var_troca($voucher_base,"#data-expiracao#",date("d/m/Y",strtotime($data_arr[0] . " + ".($_ECOMMERCE['pedido_validade'] ? $_ECOMMERCE['pedido_validade'] : 90)." day")));
			
			$voucher = modelo_var_troca($voucher,"#voucher-topo#",$voucher_topo);
			$voucher = modelo_var_troca($voucher,"#voucher-base#",$voucher_base);
			
			$voucher = modelo_var_troca($voucher,"#voucher-codigo#",$pedido['codigo']);
			$voucher = modelo_var_troca($voucher,"#voucher-senha#",$pedido['senha']);
			$voucher = modelo_var_troca($voucher,"#voucher-de#",$pedido['de']);
			$voucher = modelo_var_troca($voucher,"#voucher-para#",$pedido['para']);
			$voucher = modelo_var_troca($voucher,"#voucher-mensagem#",$pedido['mensagem']);
			$voucher = modelo_var_troca($voucher,"#voucher-concluir#",$voucher_concluir);
			
			// ============ Voucher Layout
			
			$voucher = modelo_var_troca($voucher,"#imagem_topo#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_topo'].'?v='.$voucher_layout[0]['versao']);
			$voucher = modelo_var_troca_tudo($voucher,"#imagem_textura#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_textura'].'?v='.$voucher_layout[0]['versao']);
			$voucher = modelo_var_troca($voucher,"#cor_fundo#",$voucher_layout[0]['cor_fundo']);
			
			$id_voucher_layouts = $voucher_layout[0]['id_voucher_layouts'];
			
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
					't1.validade',
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
		$query = utf8_decode($_REQUEST["query"]);
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
				'value' => utf8_encode($resultado[$i][1]),
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['query_id'] == 'servicos' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
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
				'value' => utf8_encode($resultado[$i][1]),
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['query_id'] == 'layout' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
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
				'value' => utf8_encode($resultado[$i][1]),
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
			case 'imprimir-voucher':			$saida = voucher(true);break;
			case 'email-voucher':				$saida = voucher();break;
			//case 'editar':						$saida = (operacao('editar') ? editar() : lista());break;
			case 'busca_ver':					header('Location: ../pedidos/?opcao=ver&id='.$_REQUEST['id']); break;
			//case 'ver':							$saida = (operacao('ver') ? editar('ver') : lista());break;
			//case 'editar_base':					$saida = (operacao('editar') ? editar_base() : lista());break;
			//case 'excluir':						$saida = (operacao('excluir') ? excluir() : lista());break;
			//case 'bloqueio':					$saida = (operacao('bloquear') ? bloqueio() : lista());break;
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