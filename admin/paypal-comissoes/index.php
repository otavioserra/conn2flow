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
$_LOCAL_ID					=	"paypal-comissoes";
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

$_HTML['titulo'] 			= 	$_HTML['titulo']."Grupos.";

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['tinyMce'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'loja';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'loja';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Lojas';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_LISTA_2['tabela']['nome']			=	'pastas_usuarios';
$_LISTA_2['tabela']['campo']			=	'nome';
$_LISTA_2['tabela']['id']				=	'id_'.'pastas_usuarios';
$_LISTA_2['tabela']['status']			=	'status';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções B2make

function calcular_comissao($params){
	global $_PROJETO;
	
	$id = $params['dado'];
	
	$comissao_porcentagem = $_PROJETO['PAYPAL_COMISSAO_TAXA'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.valor_total',
		))
		,
		"pedidos as t1,pedidos_pagamentos as t2",
		"WHERE t1.id_pedidos=t2.id_pedidos"
		." AND t2.status='completed'"
		." AND t1.id_loja='".$id."'"
		." AND t2.comissao_paga IS NULL"
		." ORDER BY t1.id_pedidos ASC"
	);
	
	if($resultado)
	foreach($resultado as $res){
		$valor_total += (float)$res['t1.valor_total'];
	}
	
	return 'R$ '.preparar_float_4_texto((($comissao_porcentagem/100)*$valor_total));
}

function calcular_comissoes_pagas($params){
	$id = $params['dado'];
	
	$loja_comissoes = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"loja_comissoes",
		"WHERE id_loja='".$id."'"
		." ORDER BY data DESC"
	);
	
	if($loja_comissoes)
	foreach($loja_comissoes as $res){
		$valor_total += (float)$res['valor'];
	}
	
	return 'R$ '.preparar_float_4_texto($valor_total);
}

function cobrar_manualmente(){
	global $_ALERTA;
	global $_PROJETO;
	global $_SYSTEM;
	
	$id = $_REQUEST['id'];
	
	if($id){
		$comissao_porcentagem = $_PROJETO['PAYPAL_COMISSAO_TAXA'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.valor_total',
				't2.id_pedidos_pagamentos',
			))
			,
			"pedidos as t1,pedidos_pagamentos as t2",
			"WHERE t1.id_pedidos=t2.id_pedidos"
			." AND t2.status='completed'"
			." AND t1.id_loja='".$id."'"
			." AND t2.comissao_paga IS NULL"
			." ORDER BY t1.id_pedidos ASC"
		);
		
		if($resultado)
		foreach($resultado as $res){
			$id_pedidos_pagamentos = $res['t2.id_pedidos_pagamentos'];
			
			banco_update
			(
				"comissao_paga=1",
				"pedidos_pagamentos",
				"WHERE id_pedidos_pagamentos='".$id_pedidos_pagamentos."'"
			);
			
			$valor_total += (float)$res['t1.valor_total'];
		}
		
		$valor_comissao = number_format((($comissao_porcentagem/100)*$valor_total),2);
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$campos = null;
		
		$campo_nome = "id_loja"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "valor"; $campo_valor = $valor_comissao; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "manual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"loja_comissoes"
		);
		
		$_ALERTA = 'Cobrança executada com sucesso.';
	} else {
		$_ALERTA = 'É obrigatório enviar o identificador da loja para poder cobrar manualmente.';
	}
	
	return editar('ver');
}

function eservice_paypal_plus_token_generate($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$ch = curl_init();
	$clientId = $paypal_app_code;
	$secret = $paypal_app_secret;

	curl_setopt($ch, CURLOPT_URL, "https://api.".($paypal_app_live ? "" : "sandbox.")."paypal.com/v1/oauth2/token");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept: application/json',
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

	$result = curl_exec($ch);

	if(empty($result)){
		$saida = Array(
			'erro' => 1,
			'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar o token do PayPal Plus devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.",
		);
	} else {
		$json = json_decode($result);
		
		if($json->error){
			$saida = Array(
				'erro' => 2,
				'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar o token do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.",
			);
		} else {
			$access_token = $json->access_token;
			$expires_in = $json->expires_in;
			
			$editar[$campo_tabela][] = "paypal_app_token='" . $access_token . "'";
			$editar[$campo_tabela][] = "paypal_app_token_time='" . time() . "'";
			$editar[$campo_tabela][] = "paypal_app_expires_in='" . $expires_in . "'";
			
			$campo_tabela = "loja";
			$campo_tabela_extra = "WHERE id_loja='".$id_loja."'";
			
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
			
			$saida = Array(
				'token' => $access_token,
				'status' => 'Ok',
			);
		}
	}
	
	curl_close($ch);
	
	return $saida;
}

function cobrar_com_paypal(){
	global $_ALERTA;
	global $_PROJETO;
	global $_SYSTEM;
	
	$id = $_REQUEST['id'];
	
	if($id){
		$cobrar_comissao = true;
		
		$loja = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'id_usuario',
				'paypal_app_live',
				'paypal_app_code',
				'paypal_app_secret',
				'paypal_app_token',
				'paypal_app_token_time',
				'paypal_app_expires_in',
				'paypal_app_sandbox_code',
				'paypal_app_sandbox_secret',
				'paypal_app_sandbox_token',
				'paypal_app_sandbox_token_time',
				'paypal_app_sandbox_expires_in',
			))
			,
			"loja",
			"WHERE id_loja='".$id."'"
		);
		
		if(!$_PROJETO['PAYPAL_COMISSAO_TESTES']){
			if($loja[0]['paypal_app_token']){
				if((int)$loja[0]['paypal_app_token_time']+(int)$loja[0]['paypal_app_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
		} else {
			if($loja[0]['paypal_app_sandbox_token']){
				if((int)$loja[0]['paypal_app_sandbox_token_time']+(int)$loja[0]['paypal_app_sandbox_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
		}
		
		if($gerar_token){
			$retorno = eservice_paypal_plus_token_generate(Array(
				'paypal_app_code' => (!$_PROJETO['PAYPAL_COMISSAO_TESTES'] ? $loja[0]['paypal_app_code'] : $loja[0]['paypal_app_sandbox_code']),
				'paypal_app_secret' => (!$_PROJETO['PAYPAL_COMISSAO_TESTES'] ? $loja[0]['paypal_app_secret'] : $loja[0]['paypal_app_sandbox_secret']),
				'paypal_app_live' => (!$_PROJETO['PAYPAL_COMISSAO_TESTES'] ? true : false),
				'id_loja' => $id,
			));
			
			if($retorno['erro']){
				$_ALERTA = '<p style="color:red;">Houve um problema na renovação do token com o PayPal Plus. Tente novamente mais tarde. Mensagem de retorno: <b>'.$retorno['erro_msg'].'</b></p>';
				
				$cobrar_comissao = false;
			} else {
				if($loja[0]['paypal_app_live']){
					$loja[0]['paypal_app_token'] = $retorno['token'];
				} else {
					$loja[0]['paypal_app_sandbox_token'] = $retorno['token'];
				}
			}
		}
		
		if($cobrar_comissao){
			$comissao_porcentagem = $_PROJETO['PAYPAL_COMISSAO_TAXA'];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.valor_total',
					't2.id_pedidos_pagamentos',
				))
				,
				"pedidos as t1,pedidos_pagamentos as t2",
				"WHERE t1.id_pedidos=t2.id_pedidos"
				." AND t2.status='completed'"
				." AND t1.id_loja='".$id."'"
				." AND t2.comissao_paga IS NULL"
				." ORDER BY t1.id_pedidos ASC"
			);
			
			if($resultado)
			foreach($resultado as $res){
				$valor_total += (float)$res['t1.valor_total'];
			}
			
			$valor_comissao = number_format((($comissao_porcentagem/100)*$valor_total),2);
			
			$num_total_rows = banco_total_rows
			(
				"loja_comissoes",
				""
			);
			
			$sender_batch_id = 'B2M'.date('d') . date('m') . date('Y') . zero_a_esquerda(($num_total_rows+1),6);
			
			$obj['sender_batch_header'] = Array(
				'sender_batch_id' => $sender_batch_id,
				'email_subject' => $_PROJETO['PAYPAL_COMISSAO_ASSUNTO'].' de ' . preparar_float_4_texto($comissao_porcentagem) . '%',
				'email_message' => $_PROJETO['PAYPAL_COMISSAO_MENSAGEM'].' de ' . preparar_float_4_texto($comissao_porcentagem) . '%',
				'note' => $_PROJETO['PAYPAL_COMISSAO_MENSAGEM'].' de ' . preparar_float_4_texto($comissao_porcentagem) . '%',
			);
			
			$obj['items'][] = Array(
				'recipient_type' => 'PAYPAL_ID',
				'amount' => Array(
					'value' => $valor_comissao,
					'currency' => 'BRL',
				),
				'receiver' => $_PROJETO['PAYPAL_COMISSAO_ID'],
			);
			
			$token = (!$_PROJETO['PAYPAL_COMISSAO_TESTES'] ? $loja[0]['paypal_app_token'] : $loja[0]['paypal_app_sandbox_token']);

			$json = json_encode($obj);
			
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, "https://api".(!$_PROJETO['PAYPAL_COMISSAO_TESTES'] ? "" : ".sandbox").".paypal.com/v1/payments/payouts");
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Authorization: Bearer '.$token,
			));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			
			$result = curl_exec($ch);

			if(empty($result)) $_ALERTA = 'Não houve resposta perante o servidor do PayPal. Favor tentar novamente mais tarde.';
			else
			{
				$json_res = json_decode($result);
				
				if($json_res->batch_header){
					if($resultado)
					foreach($resultado as $res){
						$id_pedidos_pagamentos = $res['t2.id_pedidos_pagamentos'];
						
						banco_update
						(
							"comissao_paga=1",
							"pedidos_pagamentos",
							"WHERE id_pedidos_pagamentos='".$id_pedidos_pagamentos."'"
						);
					}
					
					$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
					
					$campos = null;
					
					$campo_nome = "id_loja"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "valor"; $campo_valor = $valor_comissao; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "paypal_id"; $campo_valor = $json_res->batch_header->payout_batch_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"loja_comissoes"
					);
					
					$_ALERTA = 'Cobrança executada com sucesso.';
				} else {
					$_ALERTA = 'Houve um erro na resposta perante o servidor do PayPal. Servidor do PayPal respondeu: ['.$json_res->name.'] - '.$json_res->message.' - <a href="'.$json_res->information_link.'" target="_blank">'.$json_res->information_link.'</a>.';
				}
			}

			curl_close($ch);
		}
	} else {
		$_ALERTA = 'É obrigatório enviar o identificador da loja para poder cobrar manualmente.';
	}
	
	return editar('ver');
}

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
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	
	if($_SESSION[$_SYSTEM['ID']."listar-todas"]){
		$listar_todas = true;
	}
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Início', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?listar-todas=sim', // link da opção
		'title' => 'Lista Todas as Lojas', // título da opção
		'img_coluna' => 2, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Lista Todas', // Nome do menu
	);
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_URL . '?paypal-instalado=sim', // link da opção
		'title' => 'Lista Apenas as lojas com PayPal Instalado', // título da opção
		'img_coluna' => 2, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Lista PayPal Instalado', // Nome do menu
	);
	/* if(operacao('adicionar')){
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=add', // link da opção
			'title' => 'Adicionar ' . $_LISTA['ferramenta'], // título da opção
			'img_coluna' => 3, // Coluna background image
			'img_linha' => 1, // Linha background image
			'name' => 'Adicionar', // Nome do menu
		);
	} */
	
	if(
		$_INTERFACE_OPCAO == 'editar'
	){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		/* if(operacao('excluir')){
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
		if(operacao('pasta_grupo')){
			$menu_principal[] = Array( // array com todos os campos das opções do menu
				'url' => $_URL . '?opcao=pasta_grupo&id=#id', // link da opção
				'title' => 'Pasta do Grupo', // título da opção
				'img_coluna' => 6, // Coluna background image
				'img_linha' => 2, // Linha background image
				'name' => 'Pasta do Grupo', // Nome do menu
			);
		} */
		
	}
	
	/*if(operacao('pasta_grupo')){
		$menu_opcoes[] = Array( // Opção: Permissão
			'url' => $_URL . '?opcao=pasta_grupo&id=#id', // link da opção
			'title' => 'Pasta do Grupo', // título da opção
			'img_coluna' => 14, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Pasta do Grupo', // Legenda
		);
	} */
	//if(operacao('ver') && !operacao('editar')){
		$menu_opcoes[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=ver&id=#id', // link da opção
			'title' => 'Ver '.$_LISTA['ferramenta_unidade'], // título da opção
			'img_coluna' => 1, // Coluna background image
			'img_linha' => 1, // Linha background image
			'legenda' => 'Ver', // Legenda
		);
	//}
	/* 
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
		'campo' => 'Nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Comissão a Receber', // Valor do campo
		'ordenar' => false, // Valor do campo
		'width' => '150',
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'funcao_local' => 'calcular_comissao', // OPCIONAL - alinhamento horizontal
		'funcao_params' => Array(
		
		), // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Comissões Pagas', // Valor do campo
		'ordenar' => false, // Valor do campo
		'width' => '150',
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
		'funcao_local' => 'calcular_comissoes_pagas', // OPCIONAL - alinhamento horizontal
		'funcao_params' => Array(
		
		), // OPCIONAL - alinhamento horizontal
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
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' ".(!$listar_todas ? " AND paypal_app_installed IS NOT NULL " : ""), // Tabela extra
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

function editar($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_PROJETO;
	
	if($_REQUEST["id"])						$id = $_REQUEST["id"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		banco_conectar();
		
		$tabela = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'paypal_app_installed',
			))
			,
			$_LISTA['tabela']['nome'],
			"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
		);
		
		// ================================= Calculo de Comissoes ===============================
		
		$comissao_porcentagem = $_PROJETO['PAYPAL_COMISSAO_TAXA'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.valor_total',
			))
			,
			"pedidos as t1,pedidos_pagamentos as t2",
			"WHERE t1.id_pedidos=t2.id_pedidos"
			." AND t2.status='completed'"
			." AND t1.id_loja='".$id."'"
			." AND t2.comissao_paga IS NULL"
			." ORDER BY t1.id_pedidos ASC"
		);
		
		if($resultado)
		foreach($resultado as $res){
			$valor_total += (float)$res['t1.valor_total'];
		}
		
		$loja_comissoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
				'data',
				'manual',
				'paypal_id',
				'id_usuario',
			))
			,
			"loja_comissoes",
			"WHERE id_loja='".$id."'"
			." ORDER BY data DESC"
		);
		
		if($loja_comissoes){
			foreach($loja_comissoes as $lc){
				$renovar_usuario = false;
				
				if(!$usuario_bd){
					$renovar_usuario = true;
				} else if($id_usuario != $lc['id_usuario']){
					$renovar_usuario = true;
				}
				
				$id_usuario = $lc['id_usuario'];
				
				if($renovar_usuario){
					$usuario_bd = banco_select_name
					(
						banco_campos_virgulas(Array(
							'usuario',
						))
						,
						"usuario",
						"WHERE id_usuario='".$id_usuario."'"
					);
				}
				
				$comissoes_pagas .= data_from_datetime_to_text($lc['data']) . ' - R$ ' . preparar_float_4_texto($lc['valor']) . ' - Usuário: ' . $usuario_bd[0]['usuario'] . ' - ' . ($lc['manual'] ? 'COBRANÇA MANUAL' : 'COBRANÇA PAYPAL - paypal_id: '.$lc['paypal_id']) .'<br>';
			}
		} else {
			$comissoes_pagas = 'Não há nenhuma comissão cobrada até o momento';
		}
		
		// ================================= Local de Edição ===============================
		// Pegue os campos da interface e campos_guardar aqui
		
		$comissao = (($comissao_porcentagem/100)*$valor_total);
		
		$pagina = paginaTrocaVarValor($pagina,'#nome',$tabela[0]['nome']);
		$pagina = paginaTrocaVarValor($pagina,'#comissoes-receber#','R$ '.preparar_float_4_texto($comissao));
		$pagina = paginaTrocaVarValor($pagina,'#comissoes-pagas#',$comissoes_pagas);
		
		$pagina = modelo_var_troca_tudo($pagina,"#id#",$id);
		
		if(!$tabela[0]['paypal_app_installed'] || $comissao == 0){
			$cel_nome = 'paypal'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		}
		if($comissao == 0){
			$cel_nome = 'manual'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
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
		
		if($_REQUEST['paypal-instalado']){
			$_SESSION[$_SYSTEM['ID']."listar-todas"] = false;
		}
		if($_REQUEST['listar-todas']){
			$_SESSION[$_SYSTEM['ID']."listar-todas"] = true;
		}
		
		switch($opcoes){
			case 'menu_'.$_LOCAL_ID:
			case 'lista':						$saida = lista();break;
			case 'busca_ver':
			case 'ver':							$saida = editar('ver');break;
			case 'cobrar-manualmente':			$saida = cobrar_manualmente();break;
			case 'cobrar-com-paypal':			$saida = cobrar_com_paypal();break;
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