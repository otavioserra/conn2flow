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
$_LOCAL_ID					=	"pedidos_baixa";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Pedidos Baixa.";

$_HTML['js'] = 
$_JS['menu'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'pedidos';
$_LISTA['tabela']['campo']		=	'codigo';
$_LISTA['tabela']['id']			=	'id_pedidos';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Pedidos Baixa';
$_LISTA['ferramenta_unidade']	=	'o pedido';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

$_PEDIDOS_BAIXA_SLEEP_TIME	=	10;

// Funções do Sistema

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
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? '' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'home_mini.png', // caminho da imagem
		'name' => 'Início', // Nome do menu
	);
	
	
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
		'informacao_titulo' => $_LISTA['ferramenta'] , // Título da Informação
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

function baixar_pedido($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- form < -->','<!-- form > -->');
	
	
	
	// ======================================================================================
	
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

// ======================================================================================

function ajax_nao_tem_pedido($params = false){
	global $_PEDIDOS_BAIXA_SLEEP_TIME;
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	switch($opcao){
		case 'pedido':
			$resultado = banco_select_name(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"pedidos",
				"WHERE codigo='".$codigo."'"
			);
			
			$motivo = 'errou a senha do <b>pedido</b> ao tentar baixar.';
		break;
		case 'servico':
			$resultado = banco_select_name(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"pedidos_servicos",
				"WHERE codigo='".$codigo."'"
			);
			
			$motivo = 'errou a senha do <b>serviço</b> ao tentar baixar.';
		break;
		case 'baixar':
			switch($pedido_servico){
				case 'pedido':
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'id_pedidos',
						))
						,
						"pedidos",
						"WHERE codigo='".$codigo."'"
					);
					
					$motivo = 'errou a senha do <b>pedido</b> ao confirmar a baixa.';
				break;
				case 'servico':
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'id_pedidos',
						))
						,
						"pedidos_servicos",
						"WHERE codigo='".$codigo."'"
					);
					
					$motivo = 'errou a senha do <b>serviço</b> ao confirmar a baixa.';
				break;
			}
		break;
		
	}
	
	if($resultado){
		$id_pedidos = $resultado[0]['id_pedidos'];
		
		log_banco(Array(
			'id_referencia' => $id_pedidos,
			'grupo' => 'pedidos',
			'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> '.$motivo,
		));
	}
	
	sleep($_PEDIDOS_BAIXA_SLEEP_TIME);
	$saida = Array(
		'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Código e/ou senha incorreto(s)!</p>"),
	);
	
	return $saida;
}

function ajax_status_nao_ativo($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($status == 'F'){
		$resultado3 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'usuario',
			))
			,
			"usuario",
			"WHERE id_usuario='".$id_usuario_baixa."'"
		);
		$resultado4 = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.nome',
			))
			,
			"usuario_grupo as t1,grupo as t2",
			"WHERE t1.id_usuario='".$id_usuario_baixa."'"
			." AND t1.id_grupo=t2.id_grupo"
			." ORDER BY t2.nome ASC"
		);
		
		$grupos = false;
		if($resultado4){
			$grupos = ' do(s) seguinte(s) <b>';
			$flag4 = false;
			foreach($resultado4 as $res4){
				$grupos .= ($flag4?', ':'').$res4['t2.nome'];
				$flag4 = true;
			}
			$grupos .= '</b> ';
		}
		
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Esse ".$opcao_txt." já foi baixado no sistema!</p><p>O usuário <a href=\"../usuarios/?opcao=ver&id=".$id_usuario_baixa."\"><b>".$resultado3[0]['usuario']."</b></a>".$grupos." fez a baixa na data <b>".data_hora_from_datetime_to_text($data_baixa)."</b>.</p>"),
		);
	} else if($status == 'N' || $status == 'P'){
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Esse ".$opcao_txt." ainda está em processo de pagamento!</p>"),
		);
	} else if($status == 'B'){
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Esse ".$opcao_txt." está bloqueado, favor entrar em contato com o administrador da loja para saber como proceder!</p>"),
		);
	} else if($status == 'D'){
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Esse ".$opcao_txt." foi excluído, favor entrar em contato com o administrador da loja para saber como proceder!</p>"),
		);
	} else if($status == '5'){
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Esse ".$opcao_txt." está em processo de disputa para devolução de dinheiro!</p>"),
		);
	} else if($status == '6'){
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Esse ".$opcao_txt." teve o voucher cancelado e o dinheiro devolvido ao comprador!</p>"),
		);
	} else if($status == '7'){
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Esse ".$opcao_txt." teve o voucher cancelado!</p>"),
		);
	} else {
		$saida = Array(
			'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Motivo não definido, favor entrar em contato com o suporte para saber como proceder!</p>"),
		);
	}
	
	log_banco(Array(
		'id_referencia' => $id_pedidos,
		'grupo' => 'pedidos',
		'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> tentou baixar o '.$opcao_txt.' com status diferente de <b>Pago</b>',
	));
	
	return $saida;
}

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	global $_CONEXAO_BANCO;
	global $_PEDIDOS_BAIXA_SLEEP_TIME;
	
	$opcao = $_REQUEST['opcao'];
	$codigo = $_REQUEST['codigo'];
	$senha = $_REQUEST['senha'];
	$pedido_servico = $_REQUEST['pedido_servico'];
	$id = $_REQUEST['id'];
	$observacao_baixa = utf8_decode($_REQUEST['observacao']);
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(
		$codigo &&
		$senha &&
		$opcao
	){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		switch($opcao){
			case 'servico':
				$opcao_txt = '<b>serviço</b>';
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos_servicos',
						'id_pedidos',
						'id_servicos',
						'status',
						'data_baixa',
						'id_usuario_baixa',
						'validade',
					))
					,
					"pedidos_servicos",
					"WHERE codigo='".$codigo."'"
					." AND senha='".$senha."'"
				);
				
				if($resultado){
					$id_pedidos_servicos = $resultado[0]['id_pedidos_servicos'];
					$id_pedidos = $resultado[0]['id_pedidos'];
					$id_servicos = $resultado[0]['id_servicos'];
					$validade = $resultado[0]['validade'];
					
					$status = $resultado[0]['status'];
					
					if($status == 'A'){
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'data',
							))
							,
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						$data = data_hora_from_datetime_to_text($resultado[0]['data']);
						
						$data_full = $resultado[0]['data'];
						$data_arr = explode(' ',$data_full);
						
						$validade = floor((strtotime($data_arr[0] . " + ".$validade." day")/86400 - strtotime(date('Y-m-d'))/86400));
		
						if($validade >= 0){
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									'nome',
								))
								,
								"servicos",
								"WHERE id_servicos='".$id_servicos."'"
							);
							
							$servicos = $resultado[0]['nome'];
							
							$modelo = modelo_abrir('html.html');
							$pagina = modelo_tag_val($modelo,'<!-- retorno < -->','<!-- retorno > -->');
							
							$pagina = modelo_var_troca($pagina,"#pedido-servico-titulo#",'<b>serviço</b>');
							$pagina = modelo_var_troca($pagina,"#codigo#",$codigo);
							$pagina = modelo_var_troca($pagina,"#data#",$data);
							$pagina = modelo_var_troca($pagina,"#servicos#",$servicos);
							
							$pagina = modelo_var_troca($pagina,"#pedido-servico#",$opcao);
							
							$pagina = modelo_var_troca($pagina,"#baixar-id#",$id_pedidos_servicos);
							$pagina = modelo_var_troca($pagina,"#baixar-senha#",$senha);
							$pagina = modelo_var_troca($pagina,"#baixar-codigo#",$codigo);
							
							$saida = Array(
								'confirmacao' => true,
								'html' => utf8_encode($pagina),
							);
						} else {
							$saida = Array(
								'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>A <b>validade</b> desse serviço está vencida!</p>"),
							);
						}
					} else {
						$saida = ajax_status_nao_ativo(Array(
							'id_pedidos' => $id_pedidos,
							'opcao_txt' => $opcao_txt,
							'status' => $status,
							'data_baixa' => $resultado[0]['data_baixa'],
							'id_usuario_baixa' => $resultado[0]['id_usuario_baixa'],
						));
					}
				} else {
					$saida = ajax_nao_tem_pedido(Array(
						'codigo' => $codigo,
						'opcao' => $opcao,
					));
				}
			break;
			case 'pedido':
				$opcao_txt = '<b>pedido</b>';
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos',
						'status',
						'data_baixa',
						'id_usuario_baixa',
					))
					,
					"pedidos",
					"WHERE codigo='".$codigo."'"
					." AND senha='".$senha."'"
				);
				
				if($resultado){
					$id_pedidos = $resultado[0]['id_pedidos'];
					$status = $resultado[0]['status'];
					
					if($status == 'A'){
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'data',
							))
							,
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						$data = data_hora_from_datetime_to_text($resultado[0]['data']);
						
						$data_full = $resultado[0]['data'];
						$data_arr = explode(' ',$data_full);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_servicos',
								'codigo',
								'status',
								'validade',
							))
							,
							"pedidos_servicos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						$servicos = '<ol>'."\n";
						
						$servicos_vencidos = false;
						
						if($resultado)
						foreach($resultado as $res){
							$res2 = banco_select_name
							(
								banco_campos_virgulas(Array(
									'nome',
								))
								,
								"servicos",
								"WHERE id_servicos='".$res['id_servicos']."'"
							);
							
							$validade = $res['validade'];
							
							$validade = floor((strtotime($data_arr[0] . " + ".$validade." day")/86400 - strtotime(date('Y-m-d'))/86400));
			
							if($validade >= 0){
								if($res['status'] == 'F'){
									$servicos2 .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
								} else {
									$servicos .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
									$servicos_flag = true;
								}
							} else {
								if($res['status'] == 'A'){
									$servicos3 .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
									$servicos_vencidos = true;
								} else {
									$servicos2 .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
								}
							}
						}
						
						$servicos .= '</ol>'."\n";
						
						if(!$servicos_vencidos){
							if($servicos2){
								$titulo_servicos = '<p>Os seguintes serviços já foram baixados em outro atendimento e NÃO podem mais ser baixados:</p>';
								$servicos2 = $titulo_servicos.'<ol>'."\n".$servicos2.'</ol>'."\n";
								$servicos2 = '<div id="servicos-ja-baixados">'.$servicos2.'</div>';
								$servicos .= $servicos2;
							}
							
							$modelo = modelo_abrir('html.html');
							$pagina = modelo_tag_val($modelo,'<!-- retorno < -->','<!-- retorno > -->');
							
							$pagina = modelo_var_troca($pagina,"#pedido-servico-titulo#",$opcao_txt);
							$pagina = modelo_var_troca($pagina,"#codigo#",$codigo);
							$pagina = modelo_var_troca($pagina,"#data#",$data);
							$pagina = modelo_var_troca($pagina,"#servicos#",$servicos);
							
							$pagina = modelo_var_troca($pagina,"#pedido-servico#",$opcao);
							
							$pagina = modelo_var_troca($pagina,"#baixar-id#",$id_pedidos);
							$pagina = modelo_var_troca($pagina,"#baixar-senha#",$senha);
							$pagina = modelo_var_troca($pagina,"#baixar-codigo#",$codigo);
							
							$saida = Array(
								'confirmacao' => true,
								'html' => utf8_encode($pagina),
							);
						} else {
							$saida = Array(
								'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>A <b>validade</b> dos seguintes serviços está vencida:</p>".'<ol>'."\n".$servicos3.'</ol>'.($servicos_flag?"<p>Só é possível agora baixar os seguintes serviços que estão dentro do prazo de validade:</p>".$servicos:'')),
							);
						}
					} else {
						$saida = ajax_status_nao_ativo(Array(
							'id_pedidos' => $id_pedidos,
							'opcao_txt' => $opcao_txt,
							'status' => $status,
							'data_baixa' => $resultado[0]['data_baixa'],
							'id_usuario_baixa' => $resultado[0]['id_usuario_baixa'],
						));
					}
				} else {
					$saida = ajax_nao_tem_pedido(Array(
						'codigo' => $codigo,
						'opcao' => $opcao,
					));
				}
			break;
			case 'baixar':
				if($pedido_servico){
					$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
					$protocolo = md5(senha_gerar2('50'));
					
					switch($pedido_servico){
						case 'servico':
							$opcao_txt = '<b>serviço</b>';
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_pedidos_servicos',
									'id_pedidos',
									'id_servicos',
									'status',
									'data_baixa',
									'id_usuario_baixa',
									'validade',
								))
								,
								"pedidos_servicos",
								"WHERE codigo='".$codigo."'"
								." AND senha='".$senha."'"
							);
							
							if($resultado){
								$id_pedidos_servicos = $resultado[0]['id_pedidos_servicos'];
								$id_pedidos = $resultado[0]['id_pedidos'];
								$status = $resultado[0]['status'];
								$validade = $resultado[0]['validade'];
								
								if($status == 'A'){
									$resultado = banco_select_name
									(
										banco_campos_virgulas(Array(
											'data',
										))
										,
										"pedidos",
										"WHERE id_pedidos='".$id_pedidos."'"
									);
									
									$data_full = $resultado[0]['data'];
									$data_arr = explode(' ',$data_full);
									
									$validade = floor((strtotime($data_arr[0] . " + ".$validade." day")/86400 - strtotime(date('Y-m-d'))/86400));
					
									if($validade >= 0){
										$id_usuario_baixa = $usuario['id_usuario'];
										
										banco_update
										(
											"status='F',".
											"id_usuario_baixa='".$id_usuario_baixa."',".
											"observacao_baixa='".$observacao_baixa."',".
											"data_baixa=NOW(),".
											"protocolo_baixa='".$protocolo."'",
											"pedidos_servicos",
											"WHERE id_pedidos_servicos='".$id_pedidos_servicos."'"
										);
										
										$resultado2 = banco_select_name
										(
											banco_campos_virgulas(Array(
												'id_pedidos_servicos',
											))
											,
											"pedidos_servicos",
											"WHERE id_pedidos='".$id_pedidos."'"
											." AND status='A'"
										);
										
										if(!$resultado2){
											banco_update
											(
												"status='F',".
												"id_usuario_baixa='".$id_usuario_baixa."',".
												"observacao_baixa='".$observacao_baixa."',".
												"data_baixa=NOW(),".
												"protocolo_baixa='".$protocolo."'",
												"pedidos",
												"WHERE id_pedidos='".$id_pedidos."'"
											);
										}
										
										$saida = Array(
											'baixado' => true,
											'html' => utf8_encode("<p><b>Baixado com sucesso!</b></p><p>Protocolo: ".$protocolo),
										);
										
										log_banco(Array(
											'id_referencia' => $id_pedidos,
											'grupo' => 'pedidos',
											'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> baixou o '.$opcao_txt.' de código: <b>'.$codigo.'</b> - protocolo: <b>'.$protocolo.'</b>',
										));
									} else {
										$saida = Array(
											'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>A <b>validade</b> desse serviço está vencida!</p>"),
										);
									}
								} else {
									$saida = ajax_status_nao_ativo(Array(
										'id_pedidos' => $id_pedidos,
										'opcao_txt' => $opcao_txt,
										'status' => $status,
										'data_baixa' => $resultado[0]['data_baixa'],
										'id_usuario_baixa' => $resultado[0]['id_usuario_baixa'],
									));
								}
							} else {
								$saida = ajax_nao_tem_pedido(Array(
									'codigo' => $codigo,
									'opcao' => $opcao,
									'pedido_servico' => $pedido_servico,
								));
							}
						break;
						case 'pedido':
							$opcao_txt = '<b>pedido</b>';
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_pedidos',
									'status',
									'data_baixa',
									'id_usuario_baixa',
									'data',
								))
								,
								"pedidos",
								"WHERE codigo='".$codigo."'"
								." AND senha='".$senha."'"
							);
							
							if($resultado){
								$id_pedidos = $resultado[0]['id_pedidos'];
								$status = $resultado[0]['status'];
								
								if($status == 'A'){
									$data_full = $resultado[0]['data'];
									$data_arr = explode(' ',$data_full);
									
									$resultado = banco_select_name
									(
										banco_campos_virgulas(Array(
											'id_servicos',
											'codigo',
											'status',
											'validade',
										))
										,
										"pedidos_servicos",
										"WHERE id_pedidos='".$id_pedidos."'"
									);
									
									$servicos = '<ol>'."\n";
									
									$servicos_vencidos = false;
									
									if($resultado)
									foreach($resultado as $res){
										$res2 = banco_select_name
										(
											banco_campos_virgulas(Array(
												'nome',
											))
											,
											"servicos",
											"WHERE id_servicos='".$res['id_servicos']."'"
										);
										
										$validade = $res['validade'];
										
										$validade = floor((strtotime($data_arr[0] . " + ".$validade." day")/86400 - strtotime(date('Y-m-d'))/86400));
						
										if($validade >= 0){
											if($res['status'] == 'F'){
												$servicos2 .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
											} else {
												$servicos .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
												$servicos_flag = true;
											}
										} else {
											if($res['status'] == 'A'){
												$servicos3 .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
												$servicos_vencidos = true;
											} else {
												$servicos2 .= '<li>'.$res['codigo'].' - '.$res2[0]['nome'].'</li>'."\n";
											}
										}
									}
									
									$servicos .= '</ol>'."\n";
									
									if(!$servicos_vencidos){
										$id_usuario_baixa = $usuario['id_usuario'];
										
										banco_update
										(
											"status='F',".
											"id_usuario_baixa='".$id_usuario_baixa."',".
											"observacao_baixa='".$observacao_baixa."',".
											"data_baixa=NOW(),".
											"protocolo_baixa='".$protocolo."'",
											"pedidos",
											"WHERE id_pedidos='".$id_pedidos."'"
										);
										banco_update
										(
											"status='F',".
											"id_usuario_baixa='".$id_usuario_baixa."',".
											"observacao_baixa='".$observacao_baixa."',".
											"data_baixa=NOW(),".
											"protocolo_baixa='".$protocolo."'",
											"pedidos_servicos",
											"WHERE id_pedidos='".$id_pedidos."'"
											." AND status!='F'"
										);
										
										$saida = Array(
											'baixado' => true,
											'html' => utf8_encode("<p><b>Baixado com sucesso!</b></p><p>Protocolo: ".$protocolo),
										);
										
										log_banco(Array(
											'id_referencia' => $id_pedidos,
											'grupo' => 'pedidos',
											'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> baixou o '.$opcao_txt.' - protocolo: <b>'.$protocolo.'</b>',
										));
									} else {
										$saida = Array(
											'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>A <b>validade</b> dos seguintes serviços está vencida:</p>".'<ol>'."\n".$servicos3.'</ol>'.($servicos_flag?"<p>Só é possível agora baixar os seguintes serviços que estão dentro do prazo de validade:</p>".$servicos:'')),
										);
									}
								} else {
									$saida = ajax_status_nao_ativo(Array(
										'id_pedidos' => $id_pedidos,
										'opcao_txt' => $opcao_txt,
										'status' => $status,
										'data_baixa' => $resultado[0]['data_baixa'],
										'id_usuario_baixa' => $resultado[0]['id_usuario_baixa'],
									));
								}
							} else {
								$saida = ajax_nao_tem_pedido(Array(
									'codigo' => $codigo,
									'opcao' => $opcao,
									'pedido_servico' => $pedido_servico,
								));
							}
						break;
						default:
							$saida = Array(
								'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Motivo não definido, favor entrar em contato com o suporte para saber como proceder! ERRO: 3</p>"),
							);
					}
				} else {
					$saida = Array(
						'html' => utf8_encode("<p><b>NÃO É POSSÍVEL DAR BAIXA</b></p><p>Motivo não definido, favor entrar em contato com o suporte para saber como proceder! ERRO: 4</p>"),
					);
				}
			break;
			
		}
	} else {
		sleep($_PEDIDOS_BAIXA_SLEEP_TIME);
		$saida = Array(
			'baixado' => false,
			'html' => utf8_encode("É necessário definir todos os campos antes de executar"),
		);
	}
	
	return json_encode($saida);
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
			case 'editar':						$saida = (operacao('editar') ? baixar_pedido() : baixar_pedido('ver'));break;
			default: 							$saida = baixar_pedido('ver');$_SESSION[$_SYSTEM['ID'].'active_tab'] = false;
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