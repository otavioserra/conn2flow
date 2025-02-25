<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@age1.com.br
	
	B2Make
	
	Copyright (c) 2014 AgeOne Digital Marketing

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

// !!!!!!!!!!!!!!! IMPORTANTE - Acesso simultâneo de um mesmo IP no php de forma paralelo e não em fila. Sessão segura conexões em paralelo !!!!!!!!!!!!!!!!

session_write_close();

// Para testes

//$_USUARIO_ID = '214';

// Funções Locais

global $_ESERVICES;
global $_PROJETO;

$_ESERVICES['status_mudar'] = $_PROJETO['B2MAKE_STORE_STATUS_MUDAR_TITULO'];
$_ESERVICES['status_mudar_cores'] = $_PROJETO['B2MAKE_STORE_STATUS_MUDAR_CORES_2'];

// ====================================== PayPal Plus ======================================

function platform_paypal_plus_token_generate($params = false){
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
			
			$editar[$campo_tabela][] = "paypal_app_".($paypal_app_live ? "" : "sandbox_")."token='" . $access_token . "'";
			$editar[$campo_tabela][] = "paypal_app_".($paypal_app_live ? "" : "sandbox_")."token_time='" . time() . "'";
			$editar[$campo_tabela][] = "paypal_app_".($paypal_app_live ? "" : "sandbox_")."expires_in='" . $expires_in . "'";
			
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

function platform_paypal_plus_attempt_pay($params = false){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PROJETO;
	global $_B2MAKE_URL;
	global $_ESERVICE;
	global $_VARIAVEIS_JS;
	global $_USUARIO_ID;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.codigo',
			't2.id_loja',
			't1.id_pedidos',
		))
		,
		"loja_usuarios_pedidos as t1,pedidos as t2",
		"WHERE t1.id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t1.pedido_atual IS NOT NULL"
	);
	
	if($loja_usuarios_pedidos){
		$codigo_referencia = $loja_usuarios_pedidos[0]['t2.codigo'];
		$id_pedidos = $loja_usuarios_pedidos[0]['t1.id_pedidos'];
		$id_loja = $loja_usuarios_pedidos[0]['t2.id_loja'];
	
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.codigo',
				't1.quantidade',
				't1.sub_total',
				't2.nome',
				't2.descricao',
			))
			,
			'pedidos_servicos as t1,servicos as t2',
			"WHERE t1.id_pedidos='".$id_pedidos."'"
			." AND t1.id_servicos=t2.id_servicos"
			." ORDER BY t2.nome ASC"
		);
		
		if($pedidos_servicos){
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
				"WHERE id_loja='".$id_loja."'"
			);
			
			if($loja){
				$id_usuario = $loja[0]['id_usuario'];
				$loja_nome = $loja[0]['nome'];
				
				$resultado3 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'pub_id',
					))
					,
					"usuario",
					"WHERE id_usuario='".$id_usuario."'"
				);
				
				if($resultado3){
					if($resultado3[0]['pub_id']){
						$pub_id = $resultado3[0]['pub_id'];
						
					} else {
						$loja_problema = true;
					}
				} else {
					$loja_problema = true;
				}
			} else {
				$loja_problema = true;
			}
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'dominio_proprio',
					'https',
				))
				,
				"host",
				"WHERE id_usuario='".$_USUARIO_ID."'"
			);
			
			$url_site = http_define_ssl(($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']),$host[0]['https']);
			
			if($loja[0]['paypal_app_live']){
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
				$retorno = platform_paypal_plus_token_generate(Array(
					'paypal_app_code' => ($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_code'] : $loja[0]['paypal_app_sandbox_code']),
					'paypal_app_secret' => ($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_secret'] : $loja[0]['paypal_app_sandbox_secret']),
					'paypal_app_live' => $loja[0]['paypal_app_live'],
					'id_loja' => $id_loja,
				));
				
				if($retorno['erro']){
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus:</b> erro na renovação do token - ['.$retorno['erro'].'] '.$retorno['erro_msg'],
					));
					
					$msg = '<p style="color:red;">Houve um problema na renova&ccedil;&atilde;o do token com o PayPal Plus. Tente novamente mais tarde. Mensagem de retorno: <b>'.$retorno['erro_msg'].'</b></p>';
					return Array(
						'msg' => $msg,
						'status' => 'ErrorRenewToken',
					);
				} else {
					if($loja[0]['paypal_app_live']){
						$loja[0]['paypal_app_token'] = $retorno['token'];
					} else {
						$loja[0]['paypal_app_sandbox_token'] = $retorno['token'];
					}
				}
			}
			
			if($loja_problema){
				$msg = '<p style="color:red;">N&atilde;o &eacute; poss&iacute;vel pagar o seu pedido atual de c&oacute;digo: <b>'.$codigo_referencia.'</b> com PayPal Plus uma vez que esta loja est&aacute; momentaneamente fora de servi&ccedil;o. Tente novamente mais tarde.</p>';
				return Array(
					'msg' => $msg,
					'status' => 'ErrorStoreInactive',
				);
			}
			
			$minutos = $_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'];
			$ppplus_periodo_segundos = 60 * $minutos;
			
			$pedidos_pagamentos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'paypal_plus_link_approval_url',
					'identificador_externo',
				))
				,
				"pedidos_pagamentos",
				"WHERE operadora='paypalplus'"
				." AND id_pedidos='".$id_pedidos."'"
				." AND UNIX_TIMESTAMP(data_criacao) > ".(time()-$ppplus_periodo_segundos).""
			);
			
			if(count($pedidos_pagamentos) <= $_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX']){
				foreach($pedidos_servicos as $res){
					$items[] = Array(
						'name' => $res['t2.nome'],
						'description' => limite_texto($res['t2.descricao'],100),
						'quantity' => $res['t1.quantidade'],
						'price' => number_format((float)$res['t1.sub_total'], 2, '.', ''),
						'sku' => $res['t1.codigo'],
						'currency' => 'BRL',
					);
					
					$total += (float)$res['t1.sub_total'];
				}
				
				$obj['intent'] = 'sale';
				$obj['payer'] = Array(
					'payment_method' => 'paypal',
				);
				$obj['application_context'] = Array(
					'brand_name' => $loja_nome,
					'shipping_preference' => 'NO_SHIPPING',
				);
				$obj['transactions'] = Array(
					Array(
						'amount' => Array(
							'currency' => 'BRL',
							'total' => number_format($total, 2, '.', ''),
							'details' => Array(
								'shipping' => '0.00',
								'subtotal' => number_format($total, 2, '.', ''),
							),
						),
						'description' => 'Pedido '.$codigo_referencia,
						'payment_options' => Array(
							'allowed_payment_method' => 'IMMEDIATE_PAY',
						),
						'invoice_number' => $codigo_referencia,
						'item_list' => Array(
							'items' => $items,
						),
					)
				);
				$obj['redirect_urls'] = Array(
					'return_url' => $url_site.'paypalplus-return/',
					'cancel_url' => $url_site.'paypalplus-cancel/',
				);
				
				if($outro_pagador){
					if(
						!$outro_pagador['nome'] ||
						!$outro_pagador['ultimo_nome'] ||
						!$outro_pagador['email'] ||
						!$outro_pagador['telefone'] ||
						!$outro_pagador['cpf_cnpj_check'] ||
						($outro_pagador['cpf_cnpj_check'] == 'CNPJ' ? !$outro_pagador['cnpj'] : !$outro_pagador['cpf'])
					){
						$msg = '<p style="color:red;">&Eacute; necess&aacute;rio preencher todos os dados do comprador antes de clicar no bot&atilde;o CONTINUAR.</p>';
						return Array(
							'msg' => $msg,
							'status' => 'ErrorIncompleteBuyerData',
						);
					}
					
					$pagador = Array(
						'first_name' => $outro_pagador['nome'],
						'last_name' => $outro_pagador['ultimo_nome'],
						'email' => $outro_pagador['email'],
						'telefone' => $outro_pagador['telefone'],
						'cnpj_selecionado' => ($outro_pagador['cpf_cnpj_check'] == 'CNPJ' ? true : false),
						'cpf' => $outro_pagador['cpf'],
						'cnpj' => $outro_pagador['cnpj'],
						'ppp_remembered_card_hash' => '',
					);
				} else {
					$nome_arr = explode(' ',$loja_usuarios['nome']);
					
					$pagador = Array(
						'first_name' => $nome_arr[0],
						'last_name' => $loja_usuarios['ultimo_nome'],
						'email' => $loja_usuarios['email'],
						'telefone' => $loja_usuarios['telefone'],
						'cnpj_selecionado' => $loja_usuarios['cnpj_selecionado'],
						'cpf' => $loja_usuarios['cpf'],
						'cnpj' => $loja_usuarios['cnpj'],
						'ppp_remembered_card_hash' => $loja_usuarios['ppp_remembered_card_hash'],
					);
				}
				
				$json_send = json_encode($obj);
				
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL, "https://api.".($loja[0]['paypal_app_live'] ? "" : "sandbox.")."paypal.com/v1/payments/payment");
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Authorization: Bearer '.($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_token'] : $loja[0]['paypal_app_sandbox_token']),
				));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_send);
				
				$result = curl_exec($ch);
				curl_close($ch);
				
				if(empty($result)){
					$saida = Array(
						'erro' => 4,
						'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar a requisi&ccedil;&atilde;o de pagamento do PayPal Plus devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.",
					);
				} else {
					$json = json_decode($result);
					
					if($json->error){
						$saida = Array(
							'erro' => 5,
							'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar a requisi&ccedil;&atilde;o de pagamento do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.",
						);
					} else if($json->name){
						$saida = Array(
							'erro' => 5,
							'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar a requisi&ccedil;&atilde;o de pagamento do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->name." - ".$json->message."</b>.",
						);
					} else {
						$identificador_externo = $json->id;
						$status = $json->state;
						$links = $json->links;
						
						if($links)
						foreach($links as $link){
							switch($link->rel){
								case 'approval_url': $paypal_plus_link_approval_url = $link->href; break;
							}
						}
						
						$campos = null;
						
						$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "operadora"; $campo_valor = 'paypalplus'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "identificador_externo"; $campo_valor = $identificador_externo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "paypal_plus_link_approval_url"; $campo_valor = $paypal_plus_link_approval_url; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_primeiro_nome"; $campo_valor = $pagador['first_name']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_ultimo_nome"; $campo_valor = $pagador['last_name']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_email"; $campo_valor = $pagador['email']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_telefone"; $campo_valor = $pagador['telefone']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_cpf"; $campo_valor = $pagador['cpf']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_cnpj"; $campo_valor = $pagador['cnpj']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_selecionou_cnpj"; $campo_valor = $pagador['cnpj_selecionado']; 		if($campo_valor) $campos[] = Array($campo_nome,'1',true);
						$campo_nome = "status"; $campo_valor = $status; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						if($loja[0]['paypal_app_live']){
							$campo_nome = "live"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						}
						
						banco_insert_name
						(
							$campos,
							"pedidos_pagamentos"
						);

						$saida = Array(
							'status' => 'Ok',
						);
					}
				}
				
				if($saida['erro']){
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus:</b> erro na requisição de pagamento - ['.$saida['erro'].'] '.$saida['erro_msg'],
					));
					
					$msg = '<p style="color:red;">'.$saida['erro_msg'].'</p>';
					return Array(
						'msg' => $msg,
						'status' => 'ErrorAttemptPay',
					);
				} else {
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus:</b> requisição de pagamento criada com sucesso. <b>ID PROVISÓRIO: '.$identificador_externo.' - '.($outro_pagador ? 'CARTÃO DE CRÉDITO DE TERCEIRO' : 'CARTÃO DE CRÉDITO PRÓPRIO').'</b>',
					));
				}
				
				$pagador['telefone'] = preg_replace("/[^0-9]/", "", $pagador['telefone']);
				$pagador['cpf'] = preg_replace("/[^0-9]/", "", $pagador['cpf']);
				$pagador['cnpj'] = preg_replace("/[^0-9]/", "", $pagador['cnpj']);
				
				return Array(
					'ppp_ativo' => ($loja[0]['paypal_app_live'] ? 'sim' : 'nao'),
					'ppp_link_approval_url' => $paypal_plus_link_approval_url,
					'ppp_id' => $identificador_externo,
					'ppp_first_name' => $pagador['first_name'],
					'ppp_last_name' => $pagador['last_name'],
					'ppp_email' => $pagador['email'],
					'ppp_telefone' => $pagador['telefone'],
					'ppp_document_type' => ($pagador['cnpj_selecionado'] ? 'BR_CNPJ' : 'BR_CPF'),
					'ppp_document' => ($pagador['cnpj_selecionado'] ? $pagador['cnpj'] : $pagador['cpf']),
					'ppp_remembered_card_hash' => $pagador['ppp_remembered_card_hash'],
					'status' => 'OK',
				);
			} else {
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>PayPal Plus:</b> bloqueio - usuário foi bloqueado momentaneamente para pagar este pedido por exceder o limite de tentativas de pagamento. São permitidas <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX'].'</b> tentativas em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.',
				));
				
				$msg = '<p style="color:red;">Voc&ecirc; foi bloqueado momentaneamente para pagar este pedido por exceder o limite de tentativas de pagamento. S&atilde;o permitidas <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX'].'</b> tentativas em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.</p><p>Tente pagar novamente em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.</p>';
				return Array(
					'msg' => $msg,
					'status' => 'ErrorAttemptsTries',
				);
			}
		} else {
			$msg = '<p>O seu pedido n&atilde;o tem itens cadastrados. Favor entrar em contato com o suporte t&eacute;cnico para saber como proceder e informe o ERRO: paypal_plus_pay 3</p>';
			return Array(
				'msg' => $msg,
				'status' => 'ErrorOrderWithoutServices',
			);
		}
	} else {
		$msg = '<p>Voc&ecirc; ainda n&atilde;o tem pedidos cadastrados para fazer pagamentos.</p>';
		return Array(
			'msg' => $msg,
			'status' => 'ErrorNoOrders',
		);
	}
}

function platform_paypal_button_attempt_pay($params = false){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PROJETO;
	global $_B2MAKE_URL;
	global $_ESERVICE;
	global $_VARIAVEIS_JS;
	global $_USUARIO_ID;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.codigo',
			't2.id_loja',
			't1.id_pedidos',
		))
		,
		"loja_usuarios_pedidos as t1,pedidos as t2",
		"WHERE t1.id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t1.pedido_atual IS NOT NULL"
	);
	
	if($loja_usuarios_pedidos){
		$codigo_referencia = $loja_usuarios_pedidos[0]['t2.codigo'];
		$id_pedidos = $loja_usuarios_pedidos[0]['t1.id_pedidos'];
		$id_loja = $loja_usuarios_pedidos[0]['t2.id_loja'];
	
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.codigo',
				't1.quantidade',
				't1.sub_total',
				't2.nome',
				't2.descricao',
			))
			,
			'pedidos_servicos as t1,servicos as t2',
			"WHERE t1.id_pedidos='".$id_pedidos."'"
			." AND t1.id_servicos=t2.id_servicos"
			." ORDER BY t2.nome ASC"
		);
		
		if($pedidos_servicos){
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
				"WHERE id_loja='".$id_loja."'"
			);
			
			if($loja[0]['paypal_app_live']){
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
			
			$host = banco_select_name
			(
				banco_campos_virgulas(Array(
					'url',
					'dominio_proprio',
					'https',
				))
				,
				"host",
				"WHERE id_usuario='".$_USUARIO_ID."'"
			);
			
			$url_site = http_define_ssl(($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']),$host[0]['https']);
			
			if($gerar_token){
				$retorno = platform_paypal_plus_token_generate(Array(
					'paypal_app_code' => ($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_code'] : $loja[0]['paypal_app_sandbox_code']),
					'paypal_app_secret' => ($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_secret'] : $loja[0]['paypal_app_sandbox_secret']),
					'paypal_app_live' => $loja[0]['paypal_app_live'],
					'id_loja' => $id_loja,
				));
				
				if($retorno['erro']){
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus:</b> erro na renovação do token - ['.$retorno['erro'].'] '.$retorno['erro_msg'],
					));
					
					$msg = '<p style="color:red;">Houve um problema na renovação do token com o PayPal Plus. Tente novamente mais tarde. Mensagem de retorno: <b>'.$retorno['erro_msg'].'</b></p>';
					
					return Array(
						'msg' => $msg,
						'status' => 'ErrorRenewToken',
					);
				} else {
					if($loja[0]['paypal_app_live']){
						$loja[0]['paypal_app_token'] = $retorno['token'];
					} else {
						$loja[0]['paypal_app_sandbox_token'] = $retorno['token'];
					}
				}
			}
			
			$minutos = $_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'];
			$ppplus_periodo_segundos = 60 * $minutos;
			
			$pedidos_pagamentos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'paypal_plus_link_approval_url',
					'identificador_externo',
				))
				,
				"pedidos_pagamentos",
				"WHERE operadora='paypalplus'"
				." AND id_pedidos='".$id_pedidos."'"
				." AND UNIX_TIMESTAMP(data_criacao) > ".(time()-$ppplus_periodo_segundos).""
			);
			
			if(count($pedidos_pagamentos) <= $_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX']){
				foreach($pedidos_servicos as $res){
					$items[] = Array(
						'name' => $res['t2.nome'],
						'description' => limite_texto($res['t2.descricao'],100),
						'quantity' => $res['t1.quantidade'],
						'price' => number_format((float)$res['t1.sub_total'], 2, '.', ''),
						'sku' => $res['t1.codigo'],
						'currency' => 'BRL',
					);
					
					$total += (float)$res['t1.sub_total'];
				}
				
				$obj['intent'] = 'sale';
				$obj['payer'] = Array(
					'payment_method' => 'paypal',
				);
				$obj['application_context'] = Array(
					'brand_name' => $loja[0]['nome'],
					'shipping_preference' => 'NO_SHIPPING',
				);
				$obj['transactions'] = Array(
					Array(
						'amount' => Array(
							'currency' => 'BRL',
							'total' => number_format($total, 2, '.', ''),
							'details' => Array(
								'shipping' => '0.00',
								'subtotal' => number_format($total, 2, '.', ''),
							),
						),
						'description' => 'Pedido '.$codigo_referencia,
						'payment_options' => Array(
							'allowed_payment_method' => 'IMMEDIATE_PAY',
						),
						'invoice_number' => $codigo_referencia,
						'item_list' => Array(
							'items' => $items,
						),
					)
				);
				$obj['redirect_urls'] = Array(
					'return_url' => $url_site.'paypalplus-button-return/',
					'cancel_url' => $url_site.'paypalplus-button-cancel/',
				);
				
				$json_send = json_encode($obj);
				
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL, "https://api.".($loja[0]['paypal_app_live'] ? "" : "sandbox.")."paypal.com/v1/payments/payment");
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Authorization: Bearer '.($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_token'] : $loja[0]['paypal_app_sandbox_token']),
				));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_send);

				$result = curl_exec($ch);
				curl_close($ch);

				if(empty($result)){
					$saida = Array(
						'erro' => 4,
						'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar a requisi&ccedil;&atilde;o de pagamento do PayPal Plus devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.",
					);
				} else {
					$json = json_decode($result);
					
					if($json->error){
						$saida = Array(
							'erro' => 5,
							'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar a requisi&ccedil;&atilde;o de pagamento do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.",
						);
					} else if($json->name){
						$saida = Array(
							'erro' => 6,
							'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar a requisi&ccedil;&atilde;o de pagamento do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->name." - ".$json->message."</b>.",
						);
					} else {
						$identificador_externo = $json->id;
						$status = $json->state;
						$links = $json->links;
						
						if($links)
						foreach($links as $link){
							switch($link->rel){
								case 'approval_url': $paypal_plus_link_approval_url = $link->href; break;
							}
						}
						
						$campos = null;
						
						$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "operadora"; $campo_valor = 'paypalplus'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "identificador_externo"; $campo_valor = $identificador_externo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "paypal_plus_link_approval_url"; $campo_valor = $paypal_plus_link_approval_url; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_primeiro_nome"; $campo_valor = $pagador['first_name']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_ultimo_nome"; $campo_valor = $pagador['last_name']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_email"; $campo_valor = $pagador['email']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_telefone"; $campo_valor = $pagador['telefone']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_cpf"; $campo_valor = $pagador['cpf']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_cnpj"; $campo_valor = $pagador['cnpj']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pagador_selecionou_cnpj"; $campo_valor = $pagador['cnpj_selecionado']; 		if($campo_valor) $campos[] = Array($campo_nome,'1',true);
						$campo_nome = "status"; $campo_valor = $status; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						if($loja[0]['paypal_app_live']){
							$campo_nome = "live"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						}
						
						banco_insert_name
						(
							$campos,
							"pedidos_pagamentos"
						);

						$saida = Array(
							'status' => 'Ok',
						);
					}
				}
				
				if($saida['erro']){
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus:</b> erro na requisição de pagamento - ['.$saida['erro'].'] '.$saida['erro_msg'],
					));
					
					$msg = '<p style="color:red;">'.$saida['erro_msg'].'</p>';
					
					return Array(
						'msg' => $msg,
						'status' => 'ErrorAttemptPay',
					);
				} else {
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus:</b> requisição de pagamento criada com sucesso. <b>ID PROVISÓRIO: '.$identificador_externo.' - PAGUE COM O PAYPAL</b>',
					));
				}
				
				return Array(
					'id' => $identificador_externo,
					'status' => 'OK',
				);
			} else {
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>PayPal Plus:</b> bloqueio - usuário foi bloqueado momentaneamente para pagar este pedido por exceder o limite de tentativas de pagamento. São permitidas <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX'].'</b> tentativas em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.',
				));
				
				$msg = '<p style="color:red;">Voc&ecirc; foi bloqueado momentaneamente para pagar este pedido por exceder o limite de tentativas de pagamento. S&atilde;o permitidas <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX'].'</b> tentativas em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.</p><p>Tente pagar novamente em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.</p>';
				
				return Array(
					'msg' => $msg,
					'status' => 'ErrorAttemptsTries',
				);
			}
		} else {
			$msg = '<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: paypal_plus_pay 3.</p>';
			
			return Array(
				'msg' => $msg,
				'status' => 'OrderWithoutItens',
			);
		}
	} else {
		$msg = '<p>Você ainda não tem pedidos cadastrados para fazer pagamentos.</p>';
		
		return Array(
			'msg' => $msg,
			'status' => 'NoOrderRegistered',
		);
	}
}

// ====================================== Funções Locais ======================================

function platform_qrcode($code,$filename){
	global $_SYSTEM;
	global $_ESERVICE;
	global $_CAMINHO;
	
	$conteudo = rawurldecode($code);
	
	if($conteudo){
		include($_SYSTEM['INCLUDE_PATH']."php/qrlib/qrlib.php");
		
		$path = $_SYSTEM['TMP'] . $filename;
		
		QRcode::png($conteudo, $path, QR_ECLEVEL_H, 4);
		
		return $path;
	} else {
		return false;
	}
}

function platform_voucher($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_SYSTEM;
	global $_ESERVICE;
	global $_B2MAKE_URL;
	global $_B2MAKE_FTP_FILES_PATH;
	global $_B2MAKE_FTP_SITE_HOST;
	global $_USUARIO_ID;
	
	if($pedido_id){
		$loja = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'id_usuario',
				'logomarca',
				'versao',
				'nome',
				'endereco',
				'numero',
				'complemento',
				'bairro',
				'cidade',
				'uf',
				'pais',
				'cpf',
				'cnpj',
				'telefone',
				'pagseguro_parcelas_sem_juros',
				'esquema_cores',
				'fontes',
				'url_continuar_comprando',
			))
			,
			"loja",
			"WHERE id_loja='".$id_loja."'"
		);
		
		$loja_atual = $loja[0]['id'];
		$loja_atual_logomarca = $loja[0]['logomarca'];
		$loja_atual_dados = Array(
			'nome' => $loja[0]['nome'],
			'endereco' => $loja[0]['endereco'],
			'numero' => $loja[0]['numero'],
			'complemento' => $loja[0]['complemento'],
			'bairro' => $loja[0]['bairro'],
			'cidade' => $loja[0]['cidade'],
			'uf' => $loja[0]['uf'],
			'pais' => $loja[0]['pais'],
			'cpf' => $loja[0]['cpf'],
			'cnpj' => $loja[0]['cnpj'],
			'telefone' => $loja[0]['telefone'],
			'email' => $loja[0]['email'],
			'pagseguro_parcelas_sem_juros' => $loja[0]['pagseguro_parcelas_sem_juros'],
			'esquema_cores' => $loja[0]['esquema_cores'],
			'fontes' => $loja[0]['fontes'],
			'url_continuar_comprando' => $loja[0]['url_continuar_comprando'],
		);
		
		$loja_usuarios_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_pedidos='".$pedido_id."'"
			." AND id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		);
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.https',
				't1.url',
				't1.url_mobile',
				't1.user_host',
				't1.dominio_proprio',
			))
			,
			"host as t1,loja as t2",
			"WHERE t2.id_loja='".$id_loja."'"
			." AND t1.id_usuario=t2.id_usuario"
		);
		
		$url_site = http_define_ssl(($host[0]['t1.dominio_proprio'] ? 'http://'.$host[0]['t1.dominio_proprio'].'/' : $host[0]['t1.url']),$host[0]['t1.https']);
		$url_mobile = http_define_ssl(($host[0]['t1.dominio_proprio'] ? 'http://m.'.$host[0]['t1.dominio_proprio'].'/' : ($host[0]['t1.url_mobile'] ? 'http://'.$host[0]['t1.url_mobile'].'/' : 'http://m.'.$host[0]['t1.user_host'].'.'.$_B2MAKE_FTP_SITE_HOST.'/' )),$host[0]['t1.https']);
		
		if($loja_usuarios_pedidos){
			$pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'data',
					'codigo',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$pedido_id."'"
			);
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos_servicos',
					'id_voucher_layouts',
					'id_servicos',
					'de',
					'para',
					'mensagem',
					'presente',
					'codigo',
					'senha',
					'validade',
					'validade_data',
					'validade_tipo',
					'identificacao_nome',
					'identificacao_documento',
					'identificacao_telefone',
					'nome',
				))
				,
				"pedidos_servicos",
				"WHERE id_pedidos='".$pedido_id."'"
				.($pedido_servico_id ? " AND id_pedidos_servicos='".$pedido_servico_id."'" : "")
			);
			
			$pedidos_servicos_senhas = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos_servicos',
					'semente',
				))
				,
				"pedidos_servicos_senhas",
				"WHERE id_pedidos='".$pedido_id."'"
				.($loja_usuarios['versao_voucher'] ? " AND versao='".$loja_usuarios['versao_voucher']."'" : '')
				.($pedido_servico_id ? " AND id_pedidos_servicos='".$pedido_servico_id."'" : "")
			);
			
			if($mail){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
				$modelo = modelo_tag_val($modelo,'<!-- voucher-mail < -->','<!-- voucher-mail > -->');
			} else {
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
				$modelo = modelo_tag_val($modelo,'<!-- voucher-2 < -->','<!-- voucher-2 > -->');
			}
			
			$cel_nome = 'voucher-cel'; $cel[$cel_nome] = modelo_tag_val($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$modelo = modelo_tag_in($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			if($pedidos_servicos){
				foreach($pedidos_servicos as $ps){
					$cel_voucher = 'voucher-cel';
					$voucher = $cel[$cel_voucher];
					
					$presente_flag = false;
					$presente_img = false;
					$descricao_extra = false;
					$service_img = false;
					$comprador = false;
					$linha_1 = false;
					$linha_2 = false;
					$linha_3 = false;
					
					$count++;
					
					$servicos = banco_select_name
					(
						banco_campos_virgulas(Array(
							'nome',
							'observacao',
							'imagem_path',
							'imagem_biblioteca',
							'imagem_biblioteca_id',
							'versao',
						))
						,
						"servicos",
						"WHERE id_servicos='".$ps['id_servicos']."'"
					);
					
					$imagem_file = 'b2make-album-sem-imagem.png';
					$imagem_path = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
					if($servicos[0]['imagem_biblioteca']){
						if($servicos[0]['imagem_biblioteca_id']){
							$servicos_biblioteca_imagens = banco_select_name
							(
								banco_campos_virgulas(Array(
									'file',
								))
								,
								"servicos_biblioteca_imagens",
								"WHERE id_servicos_biblioteca_imagens='".$servicos[0]['imagem_biblioteca_id']."'"
							);
							
							$imagem_file = $servicos_biblioteca_imagens[0]['file'];
							$imagem_path = $url_site . $_B2MAKE_FTP_FILES_PATH . '/' . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $servicos[0]['versao'];
						}
					} else {
						if($servicos[0]['imagem_path']){
							$imagem_file = 'servico-imagem-tmp-'.$ps['id_servicos'];
							$imagem_path = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
						}
					}
					
					if($ps['presente'] && $ps['id_voucher_layouts']){
						$voucher_layouts = banco_select_name
						(
							banco_campos_virgulas(Array(
								'imagem_topo',
							))
							,
							"voucher_layouts",
							"WHERE id_voucher_layouts='".$ps['id_voucher_layouts']."'"
						);
						
						if($voucher_layouts){
							if($mail){
								$presente_img = 'cid:presente_img'.$count;
								$mail_imgs[] = array(
									'cid' => 'presente_img'.$count,
									'src' => $_SYSTEM['PATH'].$voucher_layouts[0]['imagem_topo'],
									'name' => 'Presente Imagem',
								);
							} else {
								$presente_img = $_B2MAKE_URL.$voucher_layouts[0]['imagem_topo'];
							}
							
							$presente_flag = true;
						}
					}
					
					if($ps['presente']){
						if($presente_flag){
							$voucher = modelo_var_troca($voucher,"#presente-img#",$presente_img);
						} else {
							$cel_nome = 'cel-presente-img'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
						}
						
						if($ps['de'] || $ps['para'] || $ps['mensagem']){
							$voucher = modelo_var_troca($voucher,"#presente-cartao#",
								($ps['de'] ? 'De: '.$ps['de'].'<br>' : '').
								($ps['para'] ? 'Para: '.$ps['para'].'<br>' : '').
								($ps['mensagem'] ? 'Mensagem: '.$ps['mensagem'] : '')
							);
						} else {
							if(!$presente_flag){
								$cel_nome = 'cel-presente'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
							}
							
							$cel_nome = 'cel-presente-cartao'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
						}
					} else {
						$cel_nome = 'cel-presente'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					if($ps['validade_tipo'] == 'D'){
						$descricao_extra .= '<br>Validade de Uso: '.data_hora_from_datetime_to_text($ps['validade_data']);
					} else {
						$data_full = $pedidos[0]['data'];
						$data_arr = explode(' ',$data_full);
						
						if($ps['validade']){
							$periodo = $ps['validade'];
						} else {
							$periodo = $_ESERVICE['pedido_validade'];
						}
						
						$descricao_extra .= '<br>Validade de Uso: '.date("d/m/Y",strtotime($data_arr[0] . " + ".$periodo." day"));
					}
					
					$descricao_extra .= '<br>Observação: '.nl2br($servicos[0]['observacao']);
					
					$voucher = modelo_var_troca($voucher,"#service-desc#",
						'<p>'.$ps['codigo'].' '.$ps['senha'].'</p>'.
						($ps['nome'] ? $ps['nome'] : $servicos[0]['nome']).'<br>'.
						$descricao_extra
					);
					
					
					if($mail){
						if($imagem_path){
							$tmp_image = $_SYSTEM['TMP'].$imagem_file;
							file_put_contents($tmp_image, file_get_contents($imagem_path));
							
							$voucher = modelo_var_troca($voucher,"#service-img#",'cid:service_img'.$count);
							
							$mail_imgs[] = array(
								'cid' => 'service_img'.$count,
								'src' => $tmp_image,
								'name' => 'Serviço Imagem',
								'tmp_image' => $tmp_image,
							);
						} else {
							$cel_nome = 'service-img'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
						}
					} else {
						$service_img = $imagem_path;
						$voucher = modelo_var_troca($voucher,"#service-img#",($service_img ? ' style="background-image:url('.$service_img.');"' : ''));
					}
					
					// Gerar senha do voucher
					
					$semente = false;
					$criar_senha = true;
					if($pedidos_servicos_senhas)
					foreach($pedidos_servicos_senhas as $pss){
						if($pss['id_pedidos_servicos'] == $ps['id_pedidos_servicos']){
							$semente = $pss['semente'];
							$criar_senha = false;
							break;
						}
					}
					
					if(!$semente){
						$semente = getToken(512);
					}
					
					$senha = hashPassword($semente,$loja_usuarios_senha);
					
					if($criar_senha){
						$crypt = crypt(sha1($senha));
						
						$campos = null;
						
						$campo_nome = "id_pedidos"; $campo_valor = $pedido_id; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_pedidos_servicos"; $campo_valor = $ps['id_pedidos_servicos']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "semente"; $campo_valor = $semente; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "senha"; $campo_valor = $crypt; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						
						if($loja_usuarios['versao_voucher']){
							$campo_nome = "versao"; $campo_valor = $loja_usuarios['versao_voucher']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
						
						banco_insert_name
						(
							$campos,
							"pedidos_servicos_senhas"
						);
					}
					
					if($mail){
						$qrcode = platform_qrcode(rawurlencode('#'.($loja_usuarios['versao_voucher'] ? 'V'.$loja_usuarios['versao_voucher']:'').$ps['codigo'].'#'.sha1($senha)),'qrcode-tmp'.randomString().'-'.$count.'.png');
						
						if($qrcode){
							$voucher = modelo_var_troca($voucher,"#qrcode#",'cid:qrcode_img'.$count);
							
							$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.randomString().'-'.$count.'.png';
							$img = file_get_contents($qrcode);
							file_put_contents($tmp_image, $img);
							unlink($qrcode);

							$mail_imgs[] = array(
								'cid' => 'qrcode_img'.$count,
								'src' => $tmp_image,
								'temp' => $tmp_image,
								'name' => 'QRCode Imagem',
							);
						}
					} else {
						if($mobile == 'sim'){
							$url = $url_mobile;
						} else {
							$url = $url_site;
						}
						
						$qrcode = $url . 'store/qrcode/' . $ps['codigo'];
						$voucher = modelo_var_troca($voucher,"#qrcode#",$qrcode);
					}
					
					if($ps['identificacao_nome']){
						$comprador .= $ps['identificacao_nome'].'<br>';
						$comprador .= $ps['identificacao_documento'].'<br>';
						$comprador .= $ps['identificacao_telefone'];
					} else {
						$comprador .= $loja_usuarios['nome'].' '.$loja_usuarios['ultimo_nome'].'<br>';
						$comprador .= ($loja_usuarios['cnpj_selecionado'] ? $loja_usuarios['cnpj'] : $loja_usuarios['cpf']).'<br>';
						$comprador .= $loja_usuarios['telefone'];
					}
					
					$voucher = modelo_var_troca($voucher,"#comprador#",$comprador);
					
					if($mail){
						if($loja_atual_logomarca){
							list($width, $height, $type, $attr) = getimagesize($_SYSTEM['PATH'].$loja_atual_logomarca);
							
							$res = contain_resolution(145,70,$width,$height);
							
							$voucher = modelo_var_troca($voucher,"#store-img#",'cid:loja_logomarca'.$count);
							$voucher = modelo_var_troca($voucher,"#store-img-size#",'width: '.$res['width'].'px; height: '.$res['height'].'px;');
							
							$mail_imgs[] = array(
								'cid' => 'loja_logomarca'.$count,
								'src' => $_SYSTEM['PATH'].$loja_atual_logomarca,
								'name' => 'Logomarca Imagem',
							);
						} else {
							$cel_nome = 'store-img'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
						}
					} else {
						$voucher = modelo_var_troca($voucher,"#store-img#",($loja_atual_logomarca ? ' style="background-image:url('.$_B2MAKE_URL.$loja_atual_logomarca.');"' : ' style="display:none;"'));
					}
					
					$dados_loja = $loja_atual_dados;
					
					if($dados_loja['nome']) $linha_1 = $dados_loja['nome'];
					
					if($dados_loja['cnpj']) $linha_2 .= 'CNPJ: '.$dados_loja['cnpj']; else if($dados_loja['cpf']) $linha_2 .= 'CPF: '.$dados_loja['cpf'];
					
					if($dados_loja['endereco']) $linha_3 .= $dados_loja['endereco'];
					if($dados_loja['numero']) $linha_3 .= ', '.$dados_loja['numero'];
					if($dados_loja['complemento']) $linha_3 .= ' '.$dados_loja['complemento'];
					if($dados_loja['telefone']) $linha_3 .= ' - '.$dados_loja['telefone'];
					if($dados_loja['bairro']) $linha_3 .= ' - '.$dados_loja['bairro'];
					if($dados_loja['cidade']) $linha_3 .= ' - '.$dados_loja['cidade'];
					if($dados_loja['uf']) $linha_3 .= ' - '.$dados_loja['uf'];
					if($dados_loja['pais']) $linha_3 .= ' - '.$dados_loja['pais'];
					
					$voucher = modelo_var_troca($voucher,"#store-desc#",
						'<b>'.$linha_1.'</b><br>'.
						$linha_2.'<br>'.
						$linha_3.'<br>'
					);
					
					$modelo = modelo_var_in($modelo,'<!-- '.$cel_voucher.' -->',($count > 1 ? '<hr style="margin:15px 10px;">':'').$voucher);
				}
				
				$modelo = modelo_var_troca($modelo,'<!-- '.$cel_voucher.' -->','');
				
				if($count > 1){
					$titulo = $dados_loja['nome'].($ajax || $mail ? ' - Visualizar Voucher - ' : ' - Impressão Voucher - ').$pedidos[0]['codigo'].' - Vários serviços';
				} else {
					$titulo = $dados_loja['nome'].($ajax || $mail ? ' - Visualizar Voucher - ' : ' - Impressão Voucher - ').$ps['codigo'].' - '.($ps['nome'] ? $ps['nome'] : $servicos[0]['nome']);
				}
			}
			
			return Array(
				'voucher' => ($ajax ? $modelo : $modelo),
				'titulo' => ($ajax ? $titulo : $titulo),
				'status' => 'Ok',
				'mail_imgs' => $mail_imgs,
			);
		}
	}
}

function platform_publish_priority($path,$data){
	$path_aux = explode('/',$path);
	$data_aux = explode(' ',$data);
	$data_aux2 = explode('-',$data_aux[0]);
	
	$fator1 = date('Y') - $data_aux2[0];
	$fator2 = count($path_aux);
	
	if($fator1 > 9)$fator1 = 9;
	if($fator2 > 8)$fator2 = 8;
	
	return '0.'.((90 - $fator2*10) + (10 - $fator1));
}

// ====================================== Interfaces de Atualizações ======================================

function platform_site_version($params = false){
	global $_USUARIO_ID;
	global $_HOST_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'site_version',
		))
		,
		"host",
		"WHERE id_host='".$_HOST_ID."'"
	);
	
	$site_version = $host[0]['site_version'];
	
	return Array(
		'status' => 'OK',
		'site_version' => $site_version,
	);
}

function platform_sitemaps($params = false){
	global $_USUARIO_ID;
	global $_HOST_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
			'data_modificacao',
			'publicado_mobile',
			'nome',
			'id_site_pai',
			'publicado',
		))
		,
		"site",
		"WHERE id_host='".$_HOST_ID."'"
	);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'url',
			'url_mobile',
			'dominio_proprio',
			'mobile',
		))
		,
		"host",
		"WHERE id_host='".$_HOST_ID."'"
	);
	
	$mobile = $host[0]['mobile'];
	$url = ($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']);
	$url_mobile = '//'.$host[0]['url_mobile'];
	
	$url = str_replace('http:','',$url);
	
	if($resultado)
	foreach($resultado as $res){
		$path = platform_site_pagina_diretorio($res['id_site'],false,true);
		
		$nome = $res['nome'];
		$id_site_pai = $res['id_site_pai'];
		
		if($id_site_pai) $raiz = false; else $raiz = true;
		
		$url_fim2 = $url.$path;
		$url_mobile_fim2 = rtrim($url_mobile, '/').'/'.$path;
		$pri = platform_publish_priority($path,$res['data_modificacao']);
		
		if($res['publicado']){
			if($mobile && $res['publicado_mobile']){
				$json['sites'][] = Array(
					'url' => $url_fim2,
					'url_mobile' => $url_mobile_fim2,
					'raiz' => $raiz,
					'nome' => $nome,
					'id_site_pai' => $id_site_pai,
					'id_site' => $res['id_site'],
				);
			} else {
				$json['sites'][] = Array(
					'url' => $url_fim2,
					'raiz' => $raiz,
					'nome' => $nome,
					'id_site_pai' => $id_site_pai,
					'id_site' => $res['id_site'],
				);
			}
		} else {
			$json['sites_unpublished'][] = Array(
				'url' => $url_fim2,
				'url_mobile' => $url_mobile_fim2,
				'raiz' => $raiz,
				'nome' => $nome,
				'id_site_pai' => $id_site_pai,
				'id_site' => $res['id_site'],
			);
		}
	}
	
	$json = json_encode($json,JSON_UNESCAPED_UNICODE);
	
	return Array(
		'status' => 'OK',
		'json' => $json,
	);
}

function platform_library($params = false){
	global $_USUARIO_ID;
	global $_HOST_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	if($_REQUEST['widget']){
		$widget_where =  " AND widget='".$_REQUEST['widget']."'";
	}
	
	$site_library = banco_select_name
	(
		banco_campos_virgulas(Array(
			'json',
			'widget',
		))
		,
		"site_library",
		"WHERE id_usuario='".$_USUARIO_ID."'"
		.($widget_where ? $widget_where : '')
	);
	
	if($site_library)
	foreach($site_library as $sl){
		$site_library_proc[] = Array(
			'json' => $sl['json'],
			'widget' => $sl['widget'],
		);
	}
	
	return Array(
		'status' => 'OK',
		'library' => $site_library_proc,
	);
}

function platform_tags($params = false){
	global $_USUARIO_ID;
	global $_HOST_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$option = $_REQUEST['option'];
	
	if(!$option){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	if($_REQUEST['id_site_conteudos_tags']){
		$id_site_conteudos_tags = explode(',',$_REQUEST['id_site_conteudos_tags']);
		
		foreach($id_site_conteudos_tags as $id_site_conteudos_tag){
			$conteudos_tags_where .= ($conteudos_tags_where ? ' OR ' : '') . "id_site_conteudos_tags='".$id_site_conteudos_tag."'";
		}
		
		$conteudos_tags_where = " AND (".$conteudos_tags_where.")";
	}
	
	$site_conteudos_tags = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_conteudos_tags',
			'nome',
			'cor',
			'status',
		))
		,
		"site_conteudos_tags",
		"WHERE id_usuario='".$_USUARIO_ID."'"
		.($conteudos_tags_where ? $conteudos_tags_where : '')
	);
	
	switch($option){
		case 'edit':
			if($site_conteudos_tags)
			foreach($site_conteudos_tags as $sct){
				$cor = $sct['cor'];
				$id_site_conteudos_tags = $sct['id_site_conteudos_tags'];
				
				$tags_aux = Array(
					'id_site_conteudos_tags' => $id_site_conteudos_tags,
					'nome' => $sct['nome'],
				);
				
				if($cor)$tags_aux['cor'] = $cor;
				
				$tags[] = $tags_aux;
			}
			
			return Array(
				'status' => 'OK',
				'tags' => $tags,
			);
		break;
		case 'del':
			if($site_conteudos_tags)
			foreach($site_conteudos_tags as $sct){
				if($sct['status'] != 'A'){
					$id_site_conteudos_tags = $sct['id_site_conteudos_tags'];
					
					$tags_aux = Array(
						'id_site_conteudos_tags' => $id_site_conteudos_tags,
					);
					
					$tags[] = $tags_aux;
				}
			}
			
			return Array(
				'status' => 'OK',
				'tags' => $tags,
			);
		break;
	}
}

function platform_conteudos($params = false){
	global $_USUARIO_ID;
	global $_HOST_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	if($_REQUEST['id_site_conteudos']){
		$id_site_conteudos = explode(',',$_REQUEST['id_site_conteudos']);
		
		foreach($id_site_conteudos as $id_site_conteudo){
			$conteudos_where .= ($conteudos_where ? ' OR ' : '') . "id_site_conteudos='".$id_site_conteudo."'";
		}
		
		$conteudos_where = " AND (".$conteudos_where.")";
	}
	
	$site_conteudos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site_conteudos',
			'id_site_conteudos_tipos',
			'id_site',
			'nome',
			'texto',
			'data_criacao',
			'data_modificacao',
			'imagem_path',
			'imagem_path_mini',
			'versao',
			'versao_tipo',
			'status',
		))
		,
		"site_conteudos",
		"WHERE id_host='".$_HOST_ID."'"
		.($conteudos_where ? $conteudos_where : '')
	);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'url',
			'url_mobile',
			'dominio_proprio',
		))
		,
		"host",
		"WHERE id_usuario='".$_USUARIO_ID."'"
		." AND atual IS TRUE"
	);
	
	if($site_conteudos)
	foreach($site_conteudos as $sc){
		$id_site_conteudos = $sc['id_site_conteudos'];
		$id_site_conteudos_tipos = $sc['id_site_conteudos_tipos'];
		$id_site = $sc['id_site'];
		$url_imagem = false;
		$url_imagem_2 = false;
		
		if($id_site_conteudos_tipos){
			$site_conteudos_campos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_campos',
					'id',
					'widget',
				))
				,
				"site_conteudos_campos",
				"WHERE id_site_conteudos_tipos='".$id_site_conteudos_tipos."'"
			);
			
			$site_conteudos_site_conteudos_campos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site_conteudos_campos',
					'id_site_conteudos_tipos',
					'valor',
				))
				,
				"site_conteudos_site_conteudos_campos",
				"WHERE id_site_conteudos='".$id_site_conteudos."'"
			);
		}
		
		$site_conteudos_tags_site_conteudos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site_conteudos_tags',
				'principal',
			))
			,
			"site_conteudos_tags_site_conteudos",
			"WHERE id_site_conteudos='".$id_site_conteudos."'"
		);
		
		if($site_conteudos_tags_site_conteudos){
			foreach($site_conteudos_tags_site_conteudos as $res){
				$site_conteudos_tags = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
						'cor',
					))
					,
					"site_conteudos_tags",
					"WHERE id_usuario='".$_USUARIO_ID."'"
					." AND id_site_conteudos_tags='".$res['id_site_conteudos_tags']."'"
					." AND status='A'"
				);
				
				if($site_conteudos_tags){
					$tag = Array(
						'id_site_conteudos' => $id_site_conteudos,
						'id_site_conteudos_tags' => $res['id_site_conteudos_tags'],
						'nome' => $site_conteudos_tags[0]['nome'],
						'cor' => $site_conteudos_tags[0]['cor'],
					);
					
					if($site_conteudos_tags[0]['cor']){$tag['cor'] = $site_conteudos_tags[0]['cor'];	}
					if($res['principal']){$tag['principal'] = true;	}
					
					$tags[] = $tag;
				}
			}
		}
		
		if($id_site_conteudos_tipos){
			if($site_conteudos_campos)
			foreach($site_conteudos_campos as $site_conteudos_campo){
				$valor = false;
				
				if($site_conteudos_site_conteudos_campos)
				foreach($site_conteudos_site_conteudos_campos as $site_conteudos_site_conteudos_campo){
					if($site_conteudos_campo['id_site_conteudos_campos'] == $site_conteudos_site_conteudos_campo['id_site_conteudos_campos']){
						$valor = $site_conteudos_site_conteudos_campo['valor'];
						break;
					}
				}
				
				$conteudos_campos[] = Array(
					'id_site_conteudos' => $id_site_conteudos,
					'id' => $site_conteudos_campo['id'],
					'valor' => addslashes($valor),
					'widget' => $site_conteudos_campo['widget'],
				);
			}
		}
		
		$path = platform_site_pagina_diretorio($id_site,false,true);
		$url = ($host[0]['dominio_proprio'] ? '//'.$host[0]['dominio_proprio'].'/' : $host[0]['url']) . $path;
		$url_mobile = '//'.rtrim($host[0]['url_mobile'], '/').'/'.$path;
		
		if($sc['imagem_path_mini'])$url_imagem = $_B2MAKE_URL . $sc['imagem_path_mini'] . '?v='. $sc['versao'];
		if($sc['imagem_path'])$url_imagem_2 = $_B2MAKE_URL . $sc['imagem_path'] . '?v='. $sc['versao'];
		
		$conteudos_aux = Array(
			'id_site_conteudos' => $id_site_conteudos,
			'url' => $url,
			'url_mobile' => $url_mobile,
			'nome' => $sc['nome'],
			'status' => $sc['status'],
			'texto' => addslashes($sc['texto']),
		);
		
		if($id_site_conteudos_tipos)$conteudos_aux['id_site_conteudos_tipos'] = $id_site_conteudos_tipos;
		if($sc['data_criacao'])$conteudos_aux['data_criacao'] = $sc['data_criacao'];
		if($sc['data_modificacao'])$conteudos_aux['data_modificacao'] = $sc['data_modificacao'];
		if($url_imagem)$conteudos_aux['url_imagem'] = $url_imagem;
		if($url_imagem_2)$conteudos_aux['url_imagem_2'] = $url_imagem_2;
		
		$conteudos[] = $conteudos_aux;
	}
	
	return Array(
		'status' => 'OK',
		'tags' => $tags,
		'conteudos' => $conteudos,
		'conteudos_campos' => $conteudos_campos,
	);
}

function platform_services($params = false){
	global $_USUARIO_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	if($_REQUEST['id_servicos']){
		$id_servicos = explode(',',$_REQUEST['id_servicos']);
		
		foreach($id_servicos as $id_servico){
			$servicos_where .= ($servicos_where ? ' OR ' : '') . "id_servicos='".$id_servico."'";
		}
		
		$servicos_where = " AND (".$servicos_where.")";
	}
	
	$servicos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_servicos',
			'id_site',
			'nome',
			'descricao',
			'url',
			'imagem_path',
			'imagem_path_mini',
			'imagem_biblioteca',
			'imagem_biblioteca_id',
			'quantidade',
			'preco',
			'validade',
			'validade_data',
			'validade_tipo',
			'observacao',
			'status',
			'versao',
			'data_criacao',
			'data_modificacao',
			'id_servicos_categorias',
			'visivel_de',
			'visivel_ate',
			'lote',
		))
		,
		"servicos",
		"WHERE id_loja='".$loja[0]['id_loja']."'"
		.($servicos_where ? $servicos_where : '')
	);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'url',
			'dominio_proprio',
			'https',
		))
		,
		"host",
		"WHERE id_usuario='".$_USUARIO_ID."'"
		." AND atual IS TRUE"
	);
	
	if($servicos)
	foreach($servicos as $ser){
		$caminho = platform_site_pagina_diretorio($ser['id_site'],false,true,$mobile,$robo);
		
		$imagem = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
		if($ser['imagem_biblioteca']){
			if($ser['imagem_biblioteca_id']){
				$servicos_biblioteca_imagens = banco_select_name
				(
					banco_campos_virgulas(Array(
						'file',
					))
					,
					"servicos_biblioteca_imagens",
					"WHERE id_usuario='".$_USUARIO_ID."'"
					." AND id_servicos_biblioteca_imagens='".$ser['imagem_biblioteca_id']."'"
				);
				
				$imagem = http_define_ssl(($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']) . 'files/' . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $ser['versao'] , $host[0]['https']);
			}
		} else {
			if($ser['imagem_path'])$imagem = $_B2MAKE_URL . $ser['imagem_path'] . '?v='. $ser['versao'];
		}
		
		// Lotes
		
		$servicos_lotes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_servicos_lotes',
				'nome',
				'preco',
				'quantidade',
				'visivel_de',
				'visivel_ate',
			))
			,
			"servicos_lotes",
			"WHERE id_servicos='".$ser['id_servicos']."'"
		);
		
		if($servicos_lotes)
		foreach($servicos_lotes as $sl){
			$servicosLotesAux = Array(
				'id_servicos' => $ser['id_servicos'],
				'id_servicos_lotes' => $sl['id_servicos_lotes'],
				'visivel_de' => $sl['visivel_de'],
				'visivel_ate' => $sl['visivel_ate'],
			);
			
			if($sl['nome'])$servicosLotesAux['nome'] = $sl['nome'];
			if($sl['preco'])$servicosLotesAux['preco'] = $sl['preco'];
			if($sl['quantidade'])$servicosLotesAux['quantidade'] = $sl['quantidade'];
			
			$servicosLotesProcessados[] = $servicosLotesAux;
		}
		
		// ===
		
		$servicosAux = Array(
			'id_servicos' => $ser['id_servicos'],
			'nome' => $ser['nome'],
			'descricao' => $ser['descricao'],
			'caminho' => $caminho,
			'imagem' => $imagem,
			'quantidade' => $ser['quantidade'],
			'preco' => $ser['preco'],
			'validade' => $ser['validade'],
			'validade_data' => $ser['validade_data'],
			'validade_tipo' => $ser['validade_tipo'],
			'observacao' => addslashes($ser['observacao']),
			'status' => $ser['status'],
			'versao' => $ser['versao'],
		);
		
		if($ser['data_criacao'])$servicosAux['data_criacao'] = $ser['data_criacao'];
		if($ser['data_modificacao'])$servicosAux['data_modificacao'] = $ser['data_modificacao'];
		if($ser['id_servicos_categorias'])$servicosAux['id_servicos_categorias'] = $ser['id_servicos_categorias'];
		if($ser['url'])$servicosAux['url'] = $ser['url'];
		if($ser['visivel_de'])$servicosAux['visivel_de'] = $ser['visivel_de'];
		if($ser['visivel_ate'])$servicosAux['visivel_ate'] = $ser['visivel_ate'];
		if($ser['lote'])$servicosAux['lote'] = $ser['lote'];
		
		$servicosProcessados[] = $servicosAux;
	}
	
	
	
	return Array('status'=>'OK','servicos'=>$servicosProcessados,'servicosLotes'=>$servicosLotesProcessados);
}

function platform_atualizar_dados(){
	global $_USUARIO_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;
	global $_HTML_META;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'id',
			'nome',
			'descricao',
			'email',
			'endereco',
			'numero',
			'complemento',
			'bairro',
			'cidade',
			'uf',
			'pais',
			'cnpj',
			'telefone',
			'logomarca',
			'versao',
			'voucher_sem_para_presente',
			'identificacao_voucher',
			'voucher_sem_escolha_tema',
			'url_continuar_comprando',
			'widget_loja',
			'esquema_cores',
			'fontes',
			'loja_url_cliente',
			'parcelamento',
			'parcelamento_valor_minimo',
			'parcelamento_maximo_parcelas',
			'parcelamento_modelo_informativo',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if($loja){
		if($loja[0]['logomarca'])$loja[0]['logomarca'] = $_B2MAKE_URL . $loja[0]['logomarca'] . '?v=' . $loja[0]['versao'];
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
				'url_files',
				'url_mobile',
				'google_analytic',
				'google_site_verification',
				'meta_global',
				'body_global',
				'javascript_global_published',
				'css_global_published',
				'global_version',
			))
			,
			"host",
			"WHERE id_usuario='".$_USUARIO_ID."'"
			." AND atual IS TRUE"
		);
		
		$site = banco_select_name
		(
			banco_campos_virgulas(Array(
				'pagina_favicon',
				'pagina_favicon_version',
			))
			,
			"site",
			"WHERE id_usuario='".$_USUARIO_ID."'"
			." AND id_site_pai IS NULL"
		);
		
		$generator = "	<meta name=\"generator\" content=\"".$_HTML_META['generator']."\">\n";
		
		$loja[0]['nome'] = $loja[0]['nome'];
		$loja[0]['descricao'] = $loja[0]['descricao'];
		$loja[0]['endereco'] = $loja[0]['endereco'];
		$loja[0]['numero'] = $loja[0]['numero'];
		$loja[0]['complemento'] = $loja[0]['complemento'];
		$loja[0]['bairro'] = $loja[0]['bairro'];
		$loja[0]['cidade'] = $loja[0]['cidade'];
		$loja[0]['uf'] = $loja[0]['uf'];
		$loja[0]['pais'] = $loja[0]['pais'];
		
		$host[0]['meta_global'] = addslashes($host[0]['meta_global']);
		$host[0]['body_global'] = addslashes($host[0]['body_global']);
		$host[0]['google_analytic'] = addslashes($host[0]['google_analytic']);
		
		return Array(
			'status' => 'Ok',
			'loja' => $loja[0],
			'host' => Array(
				'favicon' => $favicon,
				'generator' => $generator,
				'url' => $host[0]['url'],
				'url_mobile' => $host[0]['url_mobile'],
				'url_files' => $host[0]['url_files'],
				'google_analytic' => $host[0]['google_analytic'],
				'google_site_verification' => $host[0]['google_site_verification'],
				'javascript_global_published' => $host[0]['javascript_global_published'],
				'css_global_published' => $host[0]['css_global_published'],
				'global_version' => $host[0]['global_version'],
				'pagina_favicon' => $site[0]['pagina_favicon'],
				'pagina_favicon_version' => $site[0]['pagina_favicon_version'],
				'meta_global' => $host[0]['meta_global'],
				'body_global' => $host[0]['body_global'],
			),
		);
	} else {
		return Array(
			'error' => 'Loja não encontrada',
		);
	}
}

// ====================================== Interfaces de Loja ======================================

function platform_site_pagina_pais($id_site,$nao_inserir_dir_atual){
	while(true){
		$count++;
		if(!$id_site || $count > 100) break;
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'id_site_pai',
			))
			,
			"site",
			"WHERE id_site='".$id_site."'"
		);
		
		if($resultado){
			if($resultado[0]['id_site_pai']){
				$id = $resultado[0]['id'];
				$id_site = $resultado[0]['id_site_pai'];
				
				if(!$nao_inserir_dir_atual)$directories[] = $id;
				$nao_inserir_dir_atual = false;
			} else {
				$id_site = false;
			}
		}
	}
	
	return ($directories ? array_reverse($directories) : Array());
}

function platform_site_pagina_diretorio($id_site,$nao_inserir_dir_atual = false,$nao_ftp = false,$mobile = false,$robo = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	
	if(!$nao_ftp && $robo)ftp_chdir($_CONEXAO_FTP, '~');
	
	$dirs = platform_site_pagina_pais($id_site,$nao_inserir_dir_atual);
	
	if($mobile){
		if(!$nao_ftp){
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-site-user'].':'.$_SYSTEM['SITE']['ftp-site-pass'].'@'.$_SYSTEM['SITE']['ftp-site-host'].'/'.$_SYSTEM['SITE']['ftp-mobile-path'])) {
				ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-mobile-path']); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-mobile-path']);
		}
	}
	
	if($dirs)
	foreach($dirs as $dir){
		if(!$nao_ftp){
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-site-user'].':'.$_SYSTEM['SITE']['ftp-site-pass'].'@'.$_SYSTEM['SITE']['ftp-site-host'].'/'.$_SYSTEM['SITE']['ftp-site-path'].$dirs_antes . $dir )) {
				ftp_mkdir($_CONEXAO_FTP, $dir); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$dir);
		}
		
		$dirs_antes .= $dir . '/';
	}
	
	return ($dirs_antes ? $dirs_antes : '');
}

function platform_cart($params = false){
	global $_USUARIO_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$operacao = $_REQUEST['operacao'];
	$id_servicos = $_REQUEST['id_servicos'];
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'url_continuar_comprando',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	$url_continuar_comprando = $loja[0]['url_continuar_comprando'];
	
	if($loja[0]['url_continuar_comprando']){
		$url_continuar_comprando = $loja[0]['url_continuar_comprando'];
	} else {
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
			))
			,
			"host",
			"WHERE id_usuario='".$_USUARIO_ID."'"
		);
		
		$url_continuar_comprando = $host[0]['url'];
	}
	
	if($operacao == 'add'){
		if($id_servicos){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
					'titulo_personalizado',
					'preco',
					'quantidade',
					'imagem_biblioteca',
					'imagem_biblioteca_id',
					'versao',
					'imagem_path',
					'visivel_de',
					'visivel_ate',
					'lote',
				))
				,
				"servicos",
				"WHERE id_loja='".$id_loja."'"
				." AND id_servicos='".$id_servicos."'"
			);
			
			if($resultado){
				if($resultado[0]['visivel_de'] || $resultado[0]['visivel_ate']){
					$agora = time();
					
					if($resultado[0]['visivel_de'] && $resultado[0]['visivel_ate']){
						$visivel_de = strtotime($resultado[0]['visivel_de']);
						$visivel_ate = strtotime($resultado[0]['visivel_ate']);
						
						if($agora < $visivel_de || $agora > $visivel_ate){
							$indisponivel = true;
						}
					} else if($resultado[0]['visivel_de']){
						$visivel_de = strtotime($resultado[0]['visivel_de']);
						
						if($agora < $visivel_de){
							$indisponivel = true;
						}
					} else if($resultado[0]['visivel_ate']){
						$visivel_ate = strtotime($resultado[0]['visivel_ate']);
						
						if($agora > $visivel_ate){
							$indisponivel = true;
						}
					}
				}
				
				$lote = $resultado[0]['lote'];
				
				if($lote){
					$servicos_lotes = banco_select_name
					(
						banco_campos_virgulas(Array(
							'quantidade',
							'nome',
							'preco',
						))
						,
						"servicos_lotes",
						"WHERE id_servicos='".$id_servicos."'"
						." AND visivel_de <= NOW()"
						." AND visivel_ate >= NOW()"
					);
					
					if(!$servicos_lotes){
						$indisponivel = true;
					} else {
						$lote_nome = $servicos_lotes[0]['nome'];
						$lote_preco = $servicos_lotes[0]['preco'];
						$lote_quantidade = $servicos_lotes[0]['quantidade'];
					}
				}
				
				if(!$indisponivel){
					if($lote){
						if((int)$servicos_lotes[0]['quantidade'] >= 1){
							$quantidade_ok = true;
						}
					} else {
						if((int)$resultado[0]['quantidade'] >= 1){
							$quantidade_ok = true;
						}
					}
					
					if($quantidade_ok){
						$imagem = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
						if($resultado[0]['imagem_biblioteca']){
							if($resultado[0]['imagem_biblioteca_id']){
								$host = banco_select_name
								(
									banco_campos_virgulas(Array(
										'url',
										'dominio_proprio',
										'https',
									))
									,
									"host",
									"WHERE id_usuario='".$_USUARIO_ID."'"
									." AND atual IS TRUE"
								);
								
								$servicos_biblioteca_imagens = banco_select_name
								(
									banco_campos_virgulas(Array(
										'file',
									))
									,
									"servicos_biblioteca_imagens",
									"WHERE id_usuario='".$_USUARIO_ID."'"
									." AND id_servicos_biblioteca_imagens='".$resultado[0]['imagem_biblioteca_id']."'"
								);
								
								$imagem = http_define_ssl(($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']) . 'files/' . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $resultado[0]['versao'] , $host[0]['https']);
							}
						} else {
							if($resultado[0]['imagem_path'])$imagem = $_B2MAKE_URL . $resultado[0]['imagem_path'] . '?v='. $resultado[0]['versao'];
						}
						
						return Array(
							'status' => 'OK',
							'url_continuar_comprando' => $url_continuar_comprando,
							'carrinho' => Array(
								'nome' => ($resultado[0]['titulo_personalizado'] ? $resultado[0]['titulo_personalizado'] : $resultado[0]['nome']) . ($lote ? ' - '.$lote_nome : ''),
								'preco' => ($lote ? $lote_preco : $resultado[0]['preco']),
								'imagem' => $imagem,
							),
							'disponibilidade' => Array(
								'quantidade' => ($lote ? ((int)$lote_quantidade > 100 ? 100 : $lote_quantidade) : ((int)$resultado[0]['quantidade'] > 100 ? 100 : $resultado[0]['quantidade'])),
								'preco' => ($lote ? $lote_preco : $resultado[0]['preco']),
							)
						);
					} else {
						$msg = 'Não há quantidade suficiente em estoque para adicionar no carrinho o serviço desejado.';
					}
				} else {
					$msg = 'Este serviço não está disponível para compra.';
				}
			} else {
				$msg = 'O serviço informado não foi encontrado nesta loja. Favor refazer seu procedimento de compra.';
			}
		} else {
			$msg = 'Não é possível adicionar o serviço ao carrinho pois o serviço não foi informado. Favor informar o serviço desejado afim de incluí-lo no carrinho.';
		}
	} else if($operacao == 'url_continuar_comprando'){
		return Array(
			'status' => 'OK',
			'url_continuar_comprando' => $url_continuar_comprando,
		);
	}
	
	return Array('status'=>'OK','msg'=>$msg);
}

function platform_logar($params = false){
	global $_USUARIO_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;
	global $_MENSAGEM_ERRO;
	global $_ALERTA;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$email = $_REQUEST['email'];
	$senha = $_REQUEST['senha'];
	$ip = $_REQUEST['ip'];
	
	$email = strtolower(trim($email));
	
	if(!$email || !$senha || !$ip){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	banco_delete
	(
		"bad_list",
		"WHERE UNIX_TIMESTAMP(data_primeira_tentativa) < ".(time()-$_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']).""
	);
	
	$bad_list = banco_select_name
	(
		banco_campos_virgulas(Array(
			'num_tentativas_login',
		))
		,
		"bad_list",
		"WHERE ip='".$ip."'"
	);
	
	if($bad_list[0]['num_tentativas_login'] < $_SYSTEM['LOGIN_MAX_TENTATIVAS'] - 1){
		$loja_usuarios = banco_select_name(
			"*",
			"loja_usuarios",
			"WHERE email='".$email."' AND status!='D'"
			." AND id_loja='".$id_loja."'"
		);
		
		if(!$bad_list){
			$numero_tentativas = 1;
			
			$campos = null;
			
			$campo_nome = "ip"; $campo_valor = $ip; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "num_tentativas_login"; $campo_valor = 1; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_primeira_tentativa"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"bad_list"
			);
		} else {
			$numero_tentativas = ($bad_list[0]['num_tentativas_login'] + 1);
			
			$campo_tabela = "bad_list";
			$campo_nome = "num_tentativas_login"; $campo_valor = $numero_tentativas; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";

			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"bad_list",
					"WHERE ip='".$ip."'"
				);
			}
			$editar = false;$editar_sql = false;
		}
		
		$_MENSAGEM_ERRO = 'Você pode tentar mais <b>'.($_SYSTEM['LOGIN_MAX_TENTATIVAS'] - $numero_tentativas).'</b> vezes antes que sua conta seja bloqueada por '.floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60).' minutos(s).';
		
		if($loja_usuarios){
			if(crypt($senha, $loja_usuarios[0]['senha']) == $loja_usuarios[0]['senha']){
				if($loja_usuarios[0]['status'] != "A"){
					alerta(3);
				} else {
					$senha_sessao = sha1(crypt($loja_usuarios[0]['senha']).mt_rand());
					$loja_usuarios[0]['senha_sessao'] = $senha_sessao;
					
					banco_update
					(
						"senha_sessao='".$senha_sessao."',".
						"data_login=NOW()",
						"loja_usuarios",
						"WHERE email='".$email."'"
						." AND id_loja='".$id_loja."'"
					);
					banco_delete
					(
						"bad_list",
						"WHERE ip='".$ip."'"
					);
					
					foreach($loja_usuarios[0] as $chave => $valor){
						if($chave != 'senha'){
							if($valor){
								$loja_usuarios_proc[$chave] = $valor;
							}
						}
					}
					
					return Array(
						'status' => 'OK',
						'autorizado' => 'true',
						'loja_usuarios' => $loja_usuarios_proc,
					);
				}
			} else {
				alerta(6);
			}
		} else {
			alerta(2);
		}
		
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $_ALERTA,
		);
	} else {
		$msg = 'Você atingiu a quantidade limite de tentativas de login nesse período. Por motivos de segurança você deve aguardar '.floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60).' minutos(s) antes de tentar novamente. Qualquer dúvida entre em contato pelo e-mail: '.$_SYSTEM['ADMIN_EMAIL_HTML'].'.';
		
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	}
}

function platform_signup_request($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	$email_assinatura = $loja[0]['email_assinatura'];
	$nome_loja = $loja[0]['nome'];
	
	$email = $_REQUEST['email'];
	$senha = $_REQUEST['senha'];
	$ip = $_REQUEST['ip'];
	
	if(!$email || !$senha || !$ip){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$resultado = banco_select
	(
		"id_loja_usuarios",
		'loja_usuarios',
		"WHERE email='" . strtolower($email) . "' AND status!='D'"
		." AND id_loja='".$id_loja."'"
	);
	
	if($resultado){
		$msg = 'E-mail j&aacute; est&aacute; em uso! Escolha outro!';
		
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'tentativas',
		))
		,
		"cadastro_ips",
		"WHERE ip='".$ip."'"
	);
	
	if($resultado){
		banco_update
		(
			"tentativas=tentativas+1",
			"cadastro_ips",
			"WHERE ip='".$ip."'"
		);
	} else {
		$campos = null;
		
		$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "tentativas"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "ip"; $campo_valor = $ip; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"cadastro_ips"
		);
		$campos = null;
	}
	
	if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
	
	$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
	
	$campo_nome = "id_loja";										$campos[] = Array($campo_nome,$id_loja);
	$campo_nome = "nome"; $post_nome = 'primeiro_nome';				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
	$campo_nome = "ultimo_nome"; $post_nome = 'ultimo_nome';		if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
	$campo_nome = "email"; $post_nome = "email"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags(strtolower($_REQUEST[$post_nome])));
	$campo_nome = "senha"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,crypt($_REQUEST[$post_nome]));
	$campo_nome = "telefone"; $post_nome = $campo_nome;				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
	$campo_nome = "cpf"; $post_nome = $campo_nome;					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
	$campo_nome = "cnpj"; $post_nome = $campo_nome;					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
	$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
	
	$campo_nome = "cnpj_selecionado"; $post_nome = 'cpf_cnpj_check';
	if($_REQUEST[$post_nome] == 'CPF'){
		$campos[] = Array($campo_nome,'NULL',true);
	} else {
		$campos[] = Array($campo_nome,'1',true);
	}
	
	banco_insert_name
	(
		$campos,
		"loja_usuarios"
	);
	
	$id_loja_usuarios = banco_last_id();
	
	$codigo = date('dmY').zero_a_esquerda($id_loja_usuarios,6);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'url',
			'dominio_proprio',
			'https',
		))
		,
		"host",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	$url = http_define_ssl(($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']),$host[0]['https']);
	
	$url2 = html(Array(
		'tag' => 'a',
		'val' => $url.'identification/',
		'attr' => Array(
			'href' => $url.'identification/',
		)
	));
	
	if($_VARS['autenticar']){
		if($_VARS['autenticar']['cadastro_assunto']){
			$email_assunto = $_VARS['autenticar']['cadastro_assunto'];
		}
	}
	if($_VARS['autenticar']){
		if($_VARS['autenticar']['cadastro_mensagem']){
			$email_mensagem = $_VARS['autenticar']['cadastro_mensagem'];
		}
	}
	
	if(!$email_assunto){
		if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- email_assunto < -->','<!-- email_assunto > -->');
		
		$email_assunto = $pagina;
	}
	if(!$email_mensagem){
		if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- email_mensagem < -->','<!-- email_mensagem > -->');
		
		$email_mensagem = $pagina;
	}
	
	$email_assunto = modelo_var_troca_tudo($email_assunto,"#cod#",$codigo);
	$email_assunto = modelo_var_troca_tudo($email_assunto,"#titulo#",$nome_loja);
	
	$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#titulo#",$nome_loja);
	$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#codigo#",$codigo);
	$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",strip_tags($_REQUEST["primeiro-nome"]));
	$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url2#",$url2);
	
	$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
	
	$parametros['from_name'] = $nome_loja;
	$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
	
	$parametros['email_name'] = strip_tags($_REQUEST["primeiro_nome"]);
	$parametros['email'] = strip_tags(strtolower($_REQUEST["email"]));
	
	$parametros['subject'] = $email_assunto;
	$parametros['mensagem'] = $email_mensagem;
	$parametros['mensagem'] .= $email_assinatura;
	
	$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#loja-nome#",$nome_loja);
	
	if($parametros['enviar_mail'])enviar_mail($parametros);
	
	// ============= Logar Usuário ==================
	
	$loja_usuarios = banco_select_name(
		"*",
		"loja_usuarios",
		"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
		." AND id_loja='".$id_loja."'"
	);
	
	$senha_sessao = sha1(crypt($loja_usuarios[0]['senha']).mt_rand());
	$loja_usuarios[0]['senha_sessao'] = $senha_sessao;
	
	banco_update
	(
		"senha_sessao='".$senha_sessao."',".
		"data_login=NOW()",
		"loja_usuarios",
		"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
		." AND id_loja='".$id_loja."'"
	);
	banco_delete
	(
		"bad_list",
		"WHERE ip='".$ip."'"
	);
	
	foreach($loja_usuarios[0] as $chave => $valor){
		if($chave != 'senha'){
			if($valor){
				$loja_usuarios_proc[$chave] = $valor;
			}
		}
	}
	
	return Array(
		'status' => 'OK',
		'autorizado' => 'true',
		'loja_usuarios' => $loja_usuarios_proc,
	);
}

function platform_forgot_your_password_request($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	$email_assinatura = $loja[0]['email_assinatura'];
	$nome_loja = $loja[0]['nome'];
	
	$email = $_REQUEST['email'];
	
	if(!$email){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja_usuarios',
			'nome',
		))
		,
		"loja_usuarios",
		"WHERE email='".$email."' AND status!='D'"
		." AND id_loja='".$id_loja."'"
	);
	
	if($loja_usuarios){
		if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
		
		$key = crypt(sha1(rand().$email));
		$key = preg_replace('/[\$\.\/]/i', '', $key);
		
		$id_loja_usuarios = $loja_usuarios[0]['id_loja_usuarios'];
		$nome = $loja_usuarios[0]['nome'];
		
		banco_update
		(
			"cadastro_key='".$key."'",
			"loja_usuarios",
			"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
		);
		
		$codigo = date('dmY').zero_a_esquerda($id_loja_usuarios,10);
		
		$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
				'dominio_proprio',
				'https',
			))
			,
			"host",
			"WHERE id_usuario='".$_USUARIO_ID."'"
		);
		
		$url = http_define_ssl(($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']),$host[0]['https']);
		
		$url = html(Array(
			'tag' => 'a',
			'val' => $url.'generate-new-password/'.$codigo.'/'.$key,
			'attr' => Array(
				'href' => $url.'generate-new-password/'.$codigo.'/'.$key,
			)
		));
		
		$parametros['from_name'] = $nome_loja;
		$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
		
		$parametros['email_name'] = strip_tags($nome);
		$parametros['email'] = strip_tags($email);
		
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['esqueceu_senha_assunto']){
				$email_assunto = $_VARS['autenticar']['esqueceu_senha_assunto'];
			}
		}
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['esqueceu_senha_mensagem']){
				$email_mensagem = $_VARS['autenticar']['esqueceu_senha_mensagem'];
			}
		}
		
		if(!$email_assunto){
			$email_assunto = 'Recuperação de senha nº #cod#';
		}
		
		if(!$email_mensagem){
			$email_mensagem = '<h1>Recuperação de senha nº #codigo#</h1>';
			$email_mensagem .= '<p>Para recuperar a sua senha acesse esse link: #url#</p>';
			$email_mensagem .= '<p>Se você não tentou recuperar a sua senha desconsidere esse email automático.</p>';
		}
		
		$parametros['subject'] = $email_assunto;
		$parametros['mensagem'] = $email_mensagem;
		
		$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#cod#",$codigo);
		
		$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
		$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#nome#",$nome);
		$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#url#",$url);
		$parametros['mensagem'] .= $email_assinatura;
		
		$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#loja-nome#",$nome_loja);
		
		$campos = Array(
			'codigo' => $codigo,
			'nome' => $nome,
			'url' => $url,
		);
		
		$params = Array(
			'id' => 'esqueceu_senha_banco',
			'campos' => $campos,
			'parametros' => $parametros,
		);
		
		$saida = projeto_modificar_campos($params);
		
		$campos = $saida['campos'];
		$parametros = $saida['parametros'];
		
		if($parametros['enviar_mail'])enviar_mail($parametros);
		
		$msg = 'Foi enviada uma mensagem para o email fornecido. Entre no seu programa de email e siga os passos definidos na mensagem enviada.';
		
		return Array(
			'status' => 'OK',
			'autorizado' => 'true',
			'msg' => $msg,
		);
	} else {
		$msg = 'Email informado <b style="color:red;">inexistente</b>!<p></p>Preencha o email corretamente e envie novamente!';
		
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	}
}

function platform_generate_new_password($params = false){
	global $_USUARIO_ID;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$cod = $_REQUEST['cod'];
	$key = $_REQUEST['key'];
	
	if(!$cod || !$key){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$cod_original = $cod;
	$cod = substr($cod,8);
	$cod = zero_a_esquerda_retirar($cod);
	
	$loja_usuarios = banco_select_name
	(
		banco_campos_virgulas(Array(
			'cadastro_key',
		))
		,
		"loja_usuarios",
		"WHERE id_loja_usuarios='".$cod."'"
	);
	
	if($key == $loja_usuarios[0]['cadastro_key']){
		$key = crypt(sha1(rand().$cod));
		$key = preg_replace('/[\$\.\/]/i', '', $key);

		banco_update
		(
			"cadastro_key='".$key."'",
			"loja_usuarios",
			"WHERE id_loja_usuarios='".$cod."'"
		);
		
		return Array(
			'status' => 'OK',
			'autorizado' => 'true',
			'key' => $key,
			'cod' => $cod_original,
		);
	} else {
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
		);
	}
}

function platform_password_reset_request($params = false){
	global $_USUARIO_ID;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$cod = $_REQUEST['cod'];
	$key = $_REQUEST['key'];
	$senha = $_REQUEST['senha'];
	
	if(!$cod || !$key || !$senha){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$cod = substr($cod,8);
	$cod = zero_a_esquerda_retirar($cod);
	
	$loja_usuarios = banco_select_name
	(
		'*'
		,
		"loja_usuarios",
		"WHERE id_loja_usuarios='".$cod."'"
	);
	
	if($key == $loja_usuarios[0]['cadastro_key']){
		$loja_usuario = $loja_usuarios[0];
		
		if($loja_usuario['versao_voucher']){
			$versao_voucher = (int)$loja_usuario['versao_voucher'] + 1;
		} else {
			$versao_voucher = 1;
		}
		
		banco_update
		(
			"versao_voucher='".$versao_voucher."',".
			"senha='".crypt($senha)."',"
			."cadastro_key=NULL",
			"loja_usuarios",
			"WHERE id_loja_usuarios='".$cod."'"
		);
		
		$usuario = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario',
			))
			,
			"usuario",
			"WHERE id_loja_usuarios='".$cod."'"
		);
		
		if($usuario){
			banco_update
			(
				"versao_voucher='".$versao_voucher."',".
				"senha='".crypt($senha)."',"
				."cadastro_key=NULL",
				"usuario",
				"WHERE id_loja_usuarios='".$cod."'"
			);
		}
		
		return Array(
			'status' => 'OK',
			'autorizado' => 'true',
		);
	} else {
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
		);
	}
}

function platform_checkout($params = false){
	global $_USUARIO_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;
	global $_MENSAGEM_ERRO;
	global $_ALERTA;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$pedido_bruto = $_REQUEST['pedido_bruto'];
	
	if(!$pedido_bruto){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$pedido_bruto_arr = explode('_',$pedido_bruto);
	
	if($pedido_bruto_arr)
	foreach($pedido_bruto_arr as $item){
		$pedido_dados = explode('-',$item);
		$id = $pedido_dados[0];
		$quant = $pedido_dados[1];
		
		if($id && $quant){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'preco',
					'quantidade',
					'lote',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id."'"
				." AND id_loja='".$id_loja."'"
			);
			
			$lote = $resultado[0]['lote'];
			
			if($lote){
				$servicos_lotes = banco_select_name
				(
					banco_campos_virgulas(Array(
						'quantidade',
						'preco',
					))
					,
					"servicos_lotes",
					"WHERE id_servicos='".$id."'"
					." AND visivel_de <= NOW()"
					." AND visivel_ate >= NOW()"
				);
				
				if($resultado && $servicos_lotes){
					if((int)$quant > (int)$servicos_lotes[0]['quantidade']){
						$servico_sem_estoque = true;
						$pedido_sem_estoque[] = Array(
							'id' => $id,
							'quantidade_estoque' => $servicos_lotes[0]['quantidade'],
							'quantidade' => $quant,
						);
					} else {
						$pedido = true;
					}
				}
			} else {
				if($resultado){
					if((int)$quant > (int)$resultado[0]['quantidade']){
						$servico_sem_estoque = true;
						$pedido_sem_estoque[] = Array(
							'id' => $id,
							'quantidade_estoque' => $resultado[0]['quantidade'],
							'quantidade' => $quant,
						);
					} else {
						$pedido = true;
					}
				}
			}
		}
	}

	$retorno['status'] = 'OK';
	
	if($servico_sem_estoque) $retorno['servico_sem_estoque'] = true;
	if($pedido) $retorno['pedido'] = true;
	if($pedido_sem_estoque) $retorno['pedido_sem_estoque'] = $pedido_sem_estoque;
	
	return $retorno;
}

function platform_request_register($params = false){
	global $_USUARIO_ID;
	global $_B2MAKE_URL;
	global $_SYSTEM;
	global $_MENSAGEM_ERRO;
	global $_ALERTA;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$id_loja_usuarios = $_REQUEST['id_loja_usuarios'];
	$carrinho = json_decode(stripslashes(urldecode($_REQUEST['carrinho'])),true);
	
	if(!$id_loja_usuarios || !$carrinho){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	// ============================== Cadastrar pedido
	
	$campos = null;
	
	$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
	//$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "status"; $campo_valor = 'N'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "id_loja"; $campo_valor = $id_loja; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	
	banco_insert_name
	(
		$campos,
		"pedidos"
	);
	
	$id_pedidos = banco_last_id();
	
	$valor_total = 0;
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.url',
			't2.id_host',
			't2.url_files',
			't2.https',
		))
		,
		"loja as t1,host as t2",
		"WHERE t1.id_loja='".$id_loja."'"
		." AND t1.id_usuario=t2.id_usuario"
	);
	
	$id_host = $host[0]['t2.id_host'];
	$url_files = $host[0]['t2.url_files'];
	$https = $host[0]['t2.https'];
	
	// ============================== Cadastrar itens do pedido
	
	foreach($carrinho as $id_servicos => $item){
		if(!$servicos_computados[$id_servicos]){
			$id_servicos_return .= ($id_servicos_return ? ',':'') . $id_servicos;
			$servicos_computados[$id_servicos] = true;
		}
		
		$id_servicos_lotes = false;
		
		$quant = $item['quantidade'];
		$preco = $item['preco'];
		$nome = $item['nome'];
		
		$servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'descricao',
				'preco',
				'validade',
				'validade_data',
				'validade_tipo',
				'desconto',
				'desconto_de',
				'desconto_ate',
				'visivel_de',
				'visivel_ate',
				'id_site',
				'imagem_path',
				'imagem_path_mini',
				'versao',
				'quantidade',
				'imagem_biblioteca',
				'imagem_biblioteca_id',
				'lote',
			))
			,
			"servicos",
			"WHERE id_servicos='".$id_servicos."'"
		);
		
		$id_site = $servicos[0]['id_site'];
		
		$lote = $servicos[0]['lote'];
		
		if($lote){
			$servicos_lotes = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_servicos_lotes',
					'quantidade',
					'preco',
				))
				,
				"servicos_lotes",
				"WHERE id_servicos='".$id_servicos."'"
				." AND visivel_de <= NOW()"
				." AND visivel_ate >= NOW()"
			);
			
			$id_servicos_lotes = $servicos_lotes[0]['id_servicos_lotes'];
			
			banco_update
			(
				"quantidade = (quantidade - ".$quant.")",
				"servicos_lotes",
				"WHERE id_servicos='".$id_servicos."'"
				." AND id_servicos_lotes='".$id_servicos_lotes."'"
			);
		} else {
			banco_update
			(
				"quantidade = (quantidade - ".$quant.")",
				"servicos",
				"WHERE id_servicos='".$id_servicos."'"
			);
		}
		
		$time = time();
		if($servicos[0]['desconto']){
			$desconto_de_ate = true;
		} else {
			$desconto_de_ate = false;
		}
		
		if($servicos[0]['desconto_de']){
			$de = strtotime($servicos[0]['desconto_de']);
			
			if($time < $de){
				$desconto_de_ate = false;
			}
		}
		
		if($servicos[0]['desconto_ate']){
			$ate = strtotime($servicos[0]['desconto_ate']);
			
			if($time > $ate){
				$desconto_de_ate = false;
			}
		}
		
		$validade = (int)$servicos[0]['validade'];
		(int)$quant;
		
		if($desconto_de_ate){
			if($desconto_cupom){
				$desconto1 = 100 - (float)$servicos[0]['desconto'];
				$desconto2 = 100 - (float)$desconto_cupom;

				$desconto = $desconto1 * ($desconto2/100);
				
				$valor_original = (float)$preco;
				
				$preco = (($valor_original * $desconto) / 100);
				
				$desconto = 100 - $desconto;
			} else {
				$desconto = (float)$servicos[0]['desconto'];
				$valor_original = (float)$preco;
				
				$preco = (($valor_original * (100 - $desconto)) / 100);
			}
		} else {
			if($desconto_cupom){
				$desconto = (float)$desconto_cupom;
				$valor_original = (float)$preco;
				
				$preco = (($valor_original * (100 - $desconto)) / 100);
			} else {
				$desconto = false;
				$valor_original = false;
				
				$preco = (float)$preco;
			}
		}
		
		$sub_total = $preco*$quant;
		$valor_total = $valor_total + $sub_total;
		
		for($i=0;$i<$quant;$i++){
			$campos = null;
			
			$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_servicos"; $campo_valor = $id_servicos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "quantidade"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "desconto"; $campo_valor = $desconto; 		if($desconto)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "validade"; $campo_valor = $validade; 		if($validade)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "validade_data"; $campo_valor = $servicos[0]['validade_data']; 	if($campo_valor)if($campo_valor != '0000-00-00 00:00:00')	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "validade_tipo"; $campo_valor = $servicos[0]['validade_tipo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "valor_original"; if($valor_original){$campo_valor = number_format($valor_original, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "sub_total"; $campo_valor = number_format($preco, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = 'N'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			if($id_servicos_lotes){$campo_nome = "id_servicos_lotes"; $campo_valor = $id_servicos_lotes; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
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
	
	// ============================== Vincular Pedido ao Cupom
	
	if($desconto_cupom){
		$campos = null;
		
		$campo_nome = "id_cupom_desconto"; $campo_valor = $desconto_cupom_id; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"cupom_desconto_pedidos"
		);
	}
	
	// ============================== Gerar código do pedido e atualizar valor total
	
	banco_update
	(
		($servicos[0]['validade'] ? "validade='".$servicos[0]['validade']."'," : "").
		"validade_tipo='".$servicos[0]['validade_tipo']."',".
		($validade_data  && $validade_data != '0000-00-00 00:00:00'? "validade_data='".$validade_data."'," : '').
		"valor_total='".number_format($valor_total, 2, ".", "")."',".
		"codigo='E".((int)$id_pedidos+1000)."'",
		"pedidos",
		"WHERE id_pedidos='".$id_pedidos."'"
	);
	
	// ============================== Vincular pedido com o usuário
	
	banco_update
	(
		"pedido_atual=NULL",
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
	);
	
	$campos = null;
	
	$campo_nome = "id_loja_usuarios"; $campo_valor = $id_loja_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "id_loja"; $campo_valor = $id_loja; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "pedido_atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	
	banco_insert_name
	(
		$campos,
		"loja_usuarios_pedidos"
	);

	$retorno['status'] = 'OK';
	$retorno['id_servicos'] = $id_servicos_return;
	
	return $retorno;
}

function platform_emission($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_B2MAKE_URL;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$id_loja_usuarios = $_REQUEST['id_loja_usuarios'];
	
	if(!$id_loja_usuarios){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if($loja_usuarios_pedidos){
		$id_pedidos = $loja_usuarios_pedidos[0]['id_pedidos'];
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'codigo',
				'status',
				'valor_total',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		if($pedido[0]['status'] != 'N'){
			$status_mudar = $_ESERVICES['status_mudar'];
			
			if($status_mudar)
			foreach($status_mudar as $chave => $valor){
				if($pedido[0]['status'] == $chave){
					$pedido[0]['status'] = $valor;
					break;
				}
			}
			
			$msg = '<p style="color:red;">N&atilde;o &eacute; poss&iacute;vel mudar a emiss&atilde;o do seu pedido atual de c&oacute;digo: <b>'.$pedido[0]['codigo'].'</b> uma vez que ele est&aacute; com estado: <b>'.$pedido[0]['status'].'</b>. Favor alterar a emiss&atilde;o na p&aacute;gina de Pedidos para definir as novas identifica&ccedil;&otilde;es.</p>';
			return Array(
				'status' => 'OK',
				'naoAutorizado' => 'true',
				'msg' => $msg,
			);
		}
		
		$pedido_codigo = $pedido[0]['codigo'];
		$valor_total = (float)$pedido[0]['valor_total'];
		
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_servicos',
				'sub_total',
				'codigo',
				'nome',
			))
			,
			"pedidos_servicos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		if($pedidos_servicos)
		foreach($pedidos_servicos as $ps){
			$found = false;
			
			if($pedido_proc){
				if($pedido_proc[$ps['id_servicos']]){
					$pedido_proc[$ps['id_servicos']]['quantidade']++;
					$found = true;
				}
			}
			
			if(!$found){
				$servicos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
						'versao',
						'imagem_biblioteca',
						'imagem_biblioteca_id',
						'imagem_path',
					))
					,
					"servicos",
					"WHERE id_servicos='".$ps['id_servicos']."'"
				);
				
				$imagem = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
				if($servicos[0]['imagem_biblioteca']){
					if($servicos[0]['imagem_biblioteca_id']){
						$host = banco_select_name
						(
							banco_campos_virgulas(Array(
								'url',
								'dominio_proprio',
								'https',
							))
							,
							"host",
							"WHERE id_usuario='".$_USUARIO_ID."'"
							." AND atual IS TRUE"
						);
						
						$servicos_biblioteca_imagens = banco_select_name
						(
							banco_campos_virgulas(Array(
								'file',
							))
							,
							"servicos_biblioteca_imagens",
							"WHERE id_usuario='".$_USUARIO_ID."'"
							." AND id_servicos_biblioteca_imagens='".$servicos[0]['imagem_biblioteca_id']."'"
						);
						
						$imagem = http_define_ssl(($host[0]['dominio_proprio'] ? 'http://'.$host[0]['dominio_proprio'].'/' : $host[0]['url']) . 'files/' . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $servicos[0]['versao'] , $host[0]['https']);
					}
				} else {
					if($servicos[0]['imagem_path'])$imagem = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
				}
				
				$pedido_proc[$ps['id_servicos']] = Array(
					'quantidade' => 1,
					'preco' => $ps['sub_total'],
					'nome' => ($ps['nome'] ? $ps['nome'] : $servicos[0]['nome']),
					'imagem' => $imagem,
				);
			}
			
			$emission_card[] = Array(
				'nome' => $pedido_proc[$ps['id_servicos']]['nome'],
				'imagem' => $pedido_proc[$ps['id_servicos']]['imagem'],
				'codigo' => $ps['codigo'],
			);
		}
		
		if($pedido_proc)
		foreach($pedido_proc as $pedido){
			$sumario[] = Array(
				'nome' => $pedido['nome'],
				'quantidade' => $pedido['quantidade'],
				'subtotal' => preparar_float_4_texto(number_format(((float)$pedido['preco']*$pedido['quantidade']), 2, '.', '')),
			);
		}
		
		return Array(
			'status' => 'OK',
			'autorizado' => 'true',
			'pedido_codigo' => $pedido_codigo,
			'valor_total' => $valor_total,
			'emission_card' => $emission_card,
			'sumario' => $sumario,
		);
	} else {
		$msg = 'Voc&ecirc; n&atilde;o tem nenhum pedido cadastrado em sua conta.';
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	}
}

function platform_payment($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_B2MAKE_URL;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'paypal_app_installed',
			'paypal_app_active',
			'paypal_app_live',
			'paypal_plus_inactive',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$opcao_pagamento = $_REQUEST['opcao_pagamento'];
	
	if(!$loja_usuarios){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];

	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if($loja_usuarios_pedidos){
		$id_pedidos = $loja_usuarios_pedidos[0]['id_pedidos'];
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'codigo',
				'status',
				'valor_total',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		if($pedido[0]['status'] != 'N'){
			$status_mudar = $_ESERVICES['status_mudar'];
			
			if($status_mudar)
			foreach($status_mudar as $chave => $valor){
				if($pedido[0]['status'] == $chave){
					$pedido[0]['status'] = $valor;
					break;
				}
			}
			
			$msg = '<p style="color:red;">N&atilde;o &eacute; poss&iacute;vel pagar o seu pedido atual de c&oacute;digo: <b>'.$pedido[0]['codigo'].'</b> uma vez que ele est&aacute; com estado: <b>'.$pedido[0]['status'].'</b>. Favor escolher na p&aacute;gina de Pedidos um pedido com estado: <b>'.$_ESERVICES['status_mudar']['N'].'</b> para prosseguir com o pagamento.</p>';
			return Array(
				'status' => 'OK',
				'naoAutorizado' => 'true',
				'msg' => $msg,
			);
		}
		
		if(!$loja[0]['paypal_app_installed'] || !$loja[0]['paypal_app_active'] || $loja[0]['paypal_plus_inactive']){
			$sem_paypal_plus = true;
		}
		
		if(!$loja[0]['paypal_app_installed'] || !$loja[0]['paypal_app_active']){
			$sem_paypal = true;
		}
		
		switch($opcao_pagamento){
			case 'other-payer':
				if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && !$loja[0]['paypal_plus_inactive']){
					$payment_formas = true;
				} else if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && $loja[0]['paypal_plus_inactive']) {
					return Array(
						'status' => 'OK',
						'redirect' => '/payment/paypal',
					);
				}
			break;
			case 'paypal':
				if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active']){
					$ppp_ativo = ($loja[0]['paypal_app_live'] ? 'sim' : 'nao');
					$payment_formas = true;
				}
			break;
			default:
				if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && !$loja[0]['paypal_plus_inactive']){
					$payment_data = platform_paypal_plus_attempt_pay(Array(
						'loja_usuarios' => $loja_usuarios,
					));
					
					if($payment_data['status'] != 'OK'){
						return Array(
							'status' => 'OK',
							'naoAutorizado' => 'true',
							'msg' => $payment_data['msg'],
						);
					}
					
					$payment_formas = true;
				} else if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && $loja[0]['paypal_plus_inactive']) {
					return Array(
						'status' => 'OK',
						'redirect' => '/payment/paypal',
					);
				}
		}
		
		$pedido_codigo = $pedido[0]['codigo'];
		$valor_total = (float)$pedido[0]['valor_total'];
		
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_servicos',
				'sub_total',
				'codigo',
				'nome',
			))
			,
			"pedidos_servicos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		if($pedidos_servicos)
		foreach($pedidos_servicos as $ps){
			$found = false;
			
			if($pedido_proc){
				if($pedido_proc[$ps['id_servicos']]){
					$pedido_proc[$ps['id_servicos']]['quantidade']++;
					$found = true;
				}
			}
			
			if(!$found){
				$servicos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
					))
					,
					"servicos",
					"WHERE id_servicos='".$ps['id_servicos']."'"
				);
				
				$pedido_proc[$ps['id_servicos']] = Array(
					'quantidade' => 1,
					'preco' => $ps['sub_total'],
					'nome' => ($ps['nome'] ? $ps['nome'] : $servicos[0]['nome']),
				);
			}
		}
		
		if($pedido_proc)
		foreach($pedido_proc as $pedido){
			$sumario[] = Array(
				'nome' => $pedido['nome'],
				'quantidade' => $pedido['quantidade'],
				'subtotal' => preparar_float_4_texto(number_format(((float)$pedido['preco']*$pedido['quantidade']), 2, '.', '')),
			);
		}
		
		$retorno = Array(
			'status' => 'OK',
			'autorizado' => 'true',
			'pedido_codigo' => $pedido_codigo,
			'valor_total' => $valor_total,
			'sumario' => $sumario,
			'id_pedido_atual' => $id_pedidos,
		);
		
		if($payment_formas) $retorno['payment_formas'] = $payment_formas;
		if($payment_data) $retorno['payment_data'] = $payment_data;
		if($ppp_ativo) $retorno['ppp_ativo'] = $ppp_ativo;
		if($sem_paypal_plus) $retorno['sem_paypal_plus'] = $sem_paypal_plus;
		if($sem_paypal) $retorno['sem_paypal'] = $sem_paypal;
		
		return $retorno;
	} else {
		$msg = 'Voc&ecirc; n&atilde;o tem nenhum pedido cadastrado em sua conta.';
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	}
}

function platform_ppplus_pay($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_B2MAKE_URL;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
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
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$ppp_id = $_REQUEST['ppp_id'];
	$installmentsValue = $_REQUEST['installmentsValue'];
	$payerID = $_REQUEST['payerID'];
	$rememberedCard = $_REQUEST['rememberedCard'];
	$outroPagador = $_REQUEST['outroPagador'];
	
	if($_REQUEST['paypalButton'] == 'sim') $paypalButton = true;
	
	if(!$loja_usuarios || !$ppp_id || !$installmentsValue || !$payerID){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	if($outroPagador != 'sim'){
		banco_update
		(
			"ppp_remembered_card_hash='".$rememberedCard."'",
			"loja_usuarios",
			"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		);
	}
	
	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if(!$loja_usuarios_pedidos){
		$msg = 'Usu&aacute;rio n&atilde;o encontrado.';
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	}
	
	$id_pedidos = $loja_usuarios_pedidos[0]['id_pedidos'];
	
	if($loja[0]['paypal_app_live']){
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
		$retorno = platform_paypal_plus_token_generate(Array(
			'paypal_app_code' => ($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_code'] : $loja[0]['paypal_app_sandbox_code']),
			'paypal_app_secret' => ($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_secret'] : $loja[0]['paypal_app_sandbox_secret']),
			'paypal_app_live' => $loja[0]['paypal_app_live'],
			'id_loja' => $id_loja,
		));
		
		if($retorno['erro']){
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>PayPal Plus:</b> erro na renovação do token - ['.$retorno['erro'].'] '.$retorno['erro_msg'],
			));
			
			$msg = 'Houve um problema na renova&ccedil;&atilde;o do token com o PayPal Plus. Tente novamente mais tarde. Mensagem de retorno: <b>'.$retorno['erro_msg'].'</b>.';
			return Array(
				'status' => 'OK',
				'naoAutorizado' => 'true',
				'msg' => $msg,
			);
		} else {
			if($loja[0]['paypal_app_live']){
				$loja[0]['paypal_app_token'] = $retorno['token'];
			} else {
				$loja[0]['paypal_app_sandbox_token'] = $retorno['token'];
			}
		}
	}
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, "https://api.".($loja[0]['paypal_app_live'] ? "" : "sandbox.")."paypal.com/v1/payments/payment/".$ppp_id."/execute/");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer '.($loja[0]['paypal_app_live'] ? $loja[0]['paypal_app_token'] : $loja[0]['paypal_app_sandbox_token']),
		($paypalButton ? 'paypal-partner-attribution-id: B2make_Ecom_EC' : 'paypal-partner-attribution-id: B2make_Ecom_PPPlus'),
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, '{"payer_id" : "'.$payerID.'" }');

	$result = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	curl_close($ch);

	if(empty($result)){
		$msg = 'N&atilde;o foi poss&iacute;vel finalizar o pagamento do PayPal Plus devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.';
		
		log_banco(Array(
			'id_referencia' => $id_pedidos,
			'grupo' => 'pedidos',
			'valor' => '<b>PayPal Plus:</b> erro na finalização de pagamento: '.$msg.'.',
		));
		
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	} else {
		$json = json_decode($result);
		
		if($httpcode == '400'){
			switch($json->name){
				case 'INSTRUMENT_DECLINED': $msg = '<b>Sua operadora de cart&otilde;es N&Atilde;O autorizou o pagamento. Favor entrar em contato com o seu banco afim de saber como proceder. Depois disso, tente pagar novamente.</b><br>Erro Completo: '; break;
			}

			$msg .= "N&atilde;o foi poss&iacute;vel finalizar o pagamento do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->name." - ".$json->message." - <a href=\"".$json->information_link."\" target=\"b2make-errors\">".$json->information_link."</a></b>.";
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>PayPal Plus:</b> erro na finalização de pagamento: '.$msg.'.',
			));
			
			return Array(
				'status' => 'OK',
				'naoAutorizado' => 'true',
				'msg' => $msg,
			);
		} else {
			$identificador_externo = $json->id;
			$ppplus_final_id = $json->transactions[0]->related_resources[0]->sale->id;
			$status = $json->transactions[0]->related_resources[0]->sale->state;
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>PayPal Plus:</b> finalização de pagamento executado com sucesso. Status retornado: <b>'.$status.'</b> | <b>ID FINAL: '.$ppplus_final_id.'</b> | <b>ID PROVISÓRIO: '.$identificador_externo.'</b>',
			));
			$campos = null;
			
			$campo_tabela = "pedidos_pagamentos";
			$campo_tabela_extra = "WHERE identificador_externo='".$identificador_externo."'";
			
			$campo_nome = "status"; $campo_valor = $status; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			$campo_nome = "paypal_plus_final_id"; $campo_valor = $ppplus_final_id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			$campo_nome = "data_modificacao"; $campo_valor = $campo_valor; $editar[$campo_tabela][] = $campo_nome."=NOW()";
			
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
			
			if($status == "completed"){
				banco_update
				(
					"status='A'",
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
				
				banco_update
				(
					"status='A'",
					"pedidos_servicos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
				
				$msg = '<p>Pagamento conclu&iacute;do com sucesso!</p>';
			} else {
				$msg = '<p>Seu pagamento ainda n&atilde;o foi completamente processado. Assim que o status de pagamento for alterado, o B2make ir&aacute; entrar em contato com novas informa&ccedil;&otilde;es atrav&eacute;s do E-mail. Ou ent&atilde;o voc&ecirc; pode acessar seus Pedidos e verificar se o mesmo j&aacute; foi processado.</p>';
				$pending = true;
			}
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.id_servicos',
					't1.quantidade',
					't1.sub_total',
					't2.nome',
					't1.nome',
				))
				,
				'pedidos_servicos as t1,servicos as t2',
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." ORDER BY t2.nome ASC"
			);
			
			foreach($pedidos_servicos as $res){
				$itens[] = Array(
					'name' => ($res['t1.nome'] ? $res['t1.nome'] : $res['t2.nome']),
					'quantity' => $res['t1.quantidade'],
					'price' => $res['t1.sub_total'],
					'id' => $res['t1.id_servicos'],
				);
				
				$total += (float)$res['t1.sub_total'];
			}
			
			$retorno = Array(
				'status' => 'OK',
				'autorizado' => 'true',
				'transaction_id' => $id_pedidos,
				'itens' => $itens,
				'total' => $total,
				'msg' => $msg,
			);
			
			if($pending) $retorno['pending'] = $pending;
			
			return $retorno;
		}
	}
}

function platform_ppplus_other_payer_attempt_pay($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_B2MAKE_URL;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$outro_pagador = json_decode(stripslashes($_REQUEST['outro_pagador']),true);
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	
	if(!$outro_pagador || !$loja_usuarios){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$outro_pagador['nome'] = $outro_pagador['nome'];
	$outro_pagador['ultimo_nome'] = $outro_pagador['ultimo_nome'];
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];
	
	$payment_data = platform_paypal_plus_attempt_pay(Array(
		'loja_usuarios' => $loja_usuarios,
		'outro_pagador' => $outro_pagador,
	));
	
	if($payment_data['status'] != 'OK'){
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $payment_data['msg'],
		);
	} else {
		return Array(
			'status' => 'OK',
			'autorizado' => 'true',
			'payment_data' => $payment_data,
		);
	}
}

function platform_ppb_attempt_pay($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_B2MAKE_URL;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'paypal_app_installed',
			'paypal_app_active',
			'paypal_app_live',
			'paypal_plus_inactive',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	
	if(!$loja_usuarios){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];

	$payment_data = platform_paypal_button_attempt_pay(Array(
		'loja_usuarios' => $loja_usuarios,
	));
	
	if($payment_data['status'] != 'OK'){
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $payment_data['msg'],
		);
	} else {
		return Array(
			'status' => 'OK',
			'autorizado' => 'true',
			'payment_data' => $payment_data,
		);
	}
}

function platform_emission_request($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_B2MAKE_URL;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$id_loja_usuarios = $_REQUEST['id_loja_usuarios'];
	$identification = json_decode(stripslashes($_REQUEST['identification']),true);
	
	if(!$id_loja_usuarios || !$identification){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if($loja_usuarios_pedidos){
		$id_pedidos = $loja_usuarios_pedidos[0]['id_pedidos'];
		
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos_servicos',
				'codigo',
			))
			,
			"pedidos_servicos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		if($pedidos_servicos)
		foreach($pedidos_servicos as $ps){
			$campo_tabela = "pedidos_servicos";
			$campo_tabela_extra = "WHERE id_pedidos_servicos='".$ps['id_pedidos_servicos']."'";
			
			$campo_nome = "identificacao_nome"; $editar[$campo_tabela][] = $campo_nome."='" . $identification[$ps['codigo']]['nome'] . "'";
			$campo_nome = "identificacao_documento"; $editar[$campo_tabela][] = $campo_nome."='" . $identification[$ps['codigo']]['documento'] . "'";
			$campo_nome = "identificacao_telefone"; $editar[$campo_tabela][] = $campo_nome."='" . $identification[$ps['codigo']]['telefone'] . "'";
			
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
		}
		
		return Array(
			'status' => 'OK',
			'autorizado' => 'true',
		);
	} else {
		$msg = 'Voc&ecirc; n&atilde;o tem nenhum pedido cadastrado em sua conta.';
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
			'msg' => $msg,
		);
	}
}

function platform_purchases($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_ESERVICE;
	global $_B2MAKE_URL;
	global $_HTML;
	global $_B2MAKE_FTP_FILES_PATH;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$loja_usuarios_senha = $_REQUEST['loja_usuarios_senha'];
	
	$limite = (int)$_HTML['MENU_NUM_PAGINAS'];
	
	if($_REQUEST['page']) $page = (int)$_REQUEST['page'];
	
	if(!$loja_usuarios || !$loja_usuarios_senha){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$status_mudar = $_ESERVICES['status_mudar'];
	$status_mudar_cores = $_ESERVICES['status_mudar_cores'];
	$sem_resultados_titulo = 'Sem compras cadastradas!';

	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.id_pedidos',
			't2.codigo',
			't2.status',
			't2.data',
			't2.senha',
			't2.validade',
			't2.validade_data',
			't2.validade_tipo',
			't2.protocolo_baixa',
			't2.data_baixa',
			't2.observacao_baixa',
		))
		,
		"loja_usuarios_pedidos as t1,pedidos as t2",
		"WHERE t1.id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND t2.id_loja='".$id_loja."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." ORDER BY t2.data DESC"
		." LIMIT ".($page?($page*$limite).',':'') . ($limite + 1)
	);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.https',
			't1.url',
			't1.dominio_proprio',
		))
		,
		"host as t1,loja as t2",
		"WHERE t2.id_loja='".$id_loja."'"
		." AND t1.id_usuario=t2.id_usuario"
	);
	
	$count = 0;
	
	if($resultado){
		foreach($resultado as $pedido){
			$status = $pedido['t2.status'];
			$codigo = $pedido['t2.codigo'];
			$total = 0;
			$id_pedidos = $pedido['t2.id_pedidos'];
			$senha_pedidos = $pedido['t2.senha'];
			
			if($status_mudar)
			foreach($status_mudar as $chave => $valor){
				if($status == $chave){
					$pedido['t2.status'] = $valor;
					break;
				}
			}
			
			if($status_mudar_cores)
			foreach($status_mudar_cores as $chave => $valor){
				if($status == $chave){
					$status_cor = $valor;
					break;
				}
			}
			
			$data = data_hora_from_datetime_to_text($pedido['t2.data']);
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos_servicos',
					'id_servicos',
					'id_voucher_layouts',
					'sub_total',
					'codigo',
					'senha',
					'validade',
					'validade_data',
					'validade_tipo',
					'presente',
					'de',
					'para',
					'mensagem',
					'protocolo_baixa',
					'data_baixa',
					'observacao_baixa',
					'status',
					'identificacao_nome',
					'identificacao_documento',
					'identificacao_telefone',
					'nome',
				))
				,
				"pedidos_servicos",
				"WHERE id_pedidos='".$id_pedidos."'"
			);
			
			$pedidos_servicos_senhas = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos_servicos',
					'semente',
				))
				,
				"pedidos_servicos_senhas",
				"WHERE id_pedidos='".$id_pedidos."'"
				.($loja_usuarios['versao_voucher'] ? " AND versao='".$loja_usuarios['versao_voucher']."'" : '')
			);
			
			$pedido_proc = false;
			
			if($pedidos_servicos)
			foreach($pedidos_servicos as $ps){
				$status_servico = $ps['status'];
				$found = false;
				
				if($pedido_proc){
					if($pedido_proc[$ps['id_servicos']]){
						$found = true;
					}
				}
				
				if($status_mudar)
				foreach($status_mudar as $chave => $valor){
					if($status_servico == $chave){
						$ps['status'] = $valor;
						break;
					}
				}
				
				if($status_mudar_cores)
				foreach($status_mudar_cores as $chave => $valor){
					if($status_servico == $chave){
						$status_servico_cor = $valor;
						break;
					}
				}
				
				if(!$found){
					$servicos = banco_select_name
					(
						banco_campos_virgulas(Array(
							'nome',
							'imagem_path',
							'imagem_biblioteca',
							'imagem_biblioteca_id',
							'observacao',
							'versao',
						))
						,
						"servicos",
						"WHERE id_servicos='".$ps['id_servicos']."'"
					);
					
					$imagem_path = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
					if($servicos[0]['imagem_biblioteca']){
						if($servicos[0]['imagem_biblioteca_id']){
							$servicos_biblioteca_imagens = banco_select_name
							(
								banco_campos_virgulas(Array(
									'file',
								))
								,
								"servicos_biblioteca_imagens",
								"WHERE id_servicos_biblioteca_imagens='".$servicos[0]['imagem_biblioteca_id']."'"
							);
							
							$imagem_path = http_define_ssl(($host[0]['t1.dominio_proprio'] ? 'http://'.$host[0]['t1.dominio_proprio'].'/' : $host[0]['t1.url']),$host[0]['t1.https']) . $_B2MAKE_FTP_FILES_PATH . '/' . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $servicos[0]['versao'];
						}
					} else {
						if($servicos[0]['imagem_path'])$imagem_path = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
					}
					
					$pedido_proc[$ps['id_servicos']] = Array(
						'nome' => $servicos[0]['nome'],
						'observacao' => $servicos[0]['observacao'],
						'imagem_path' => $imagem_path,
					);
				}

				$sub_total = (float)$ps['sub_total'];
				
				$qrcode = false;
				
				if($status_servico == 'A'){
					// Gerar senha do voucher
					
					$semente = false;
					$criar_senha = true;
					if($pedidos_servicos_senhas)
					foreach($pedidos_servicos_senhas as $pss){
						if($pss['id_pedidos_servicos'] == $ps['id_pedidos_servicos']){
							$semente = $pss['semente'];
							$criar_senha = false;
							break;
						}
					}
					
					if(!$semente){
						$semente = getToken(512);
					}
					
					$senha = hashPassword($semente,$loja_usuarios_senha);
					
					if($criar_senha){
						$crypt = crypt(sha1($senha));
						
						$campos = null;
						
						$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_pedidos_servicos"; $campo_valor = $ps['id_pedidos_servicos']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "semente"; $campo_valor = $semente; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "senha"; $campo_valor = $crypt; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						
						if($loja_usuarios['versao_voucher']){
							$campo_nome = "versao"; $campo_valor = $loja_usuarios['versao_voucher']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
						
						banco_insert_name
						(
							$campos,
							"pedidos_servicos_senhas"
						);
					}
					
					$qrcode = rawurlencode('#'.($loja_usuarios['versao_voucher'] ? 'V'.$loja_usuarios['versao_voucher']:'').$ps['codigo'].'#'.sha1($senha));
					
					if($ps['presente']){
						$para_presente = true;
						
						if($ps['id_voucher_layouts']){
							$id_voucher_layouts = $ps['id_voucher_layouts'];
							
							$layout_voucher_found = false;
							
							if($layout_voucher_proc){
								if($layout_voucher_proc[$ps['id_voucher_layouts']]){
									$layout_voucher_found = true;
								}
							}
							
							if(!$layout_voucher_found){
								$voucher_layouts = banco_select_name
								(
									banco_campos_virgulas(Array(
										'imagem_topo',
									))
									,
									"voucher_layouts",
									"WHERE id_voucher_layouts='".$ps['id_voucher_layouts']."'"
								);
								
								$layout_voucher_proc[$ps['id_voucher_layouts']] = Array(
									'imagem_topo' => $voucher_layouts[0]['imagem_topo'],
								);
							}
							
							$voucher_layouts = Array(
								'id' => $ps['id_voucher_layouts'],
								'imagem' => $_B2MAKE_URL.$layout_voucher_proc[$ps['id_voucher_layouts']]['imagem_topo'],
							);
						} else {
							$voucher_layouts = false;
						}
					} else {
						$para_presente = false;
					}
					
					$presente_destinatario = false;
					
					if($ps['de']){ $presente_destinatario['de'] = $ps['de']; }
					if($ps['para']){ $presente_destinatario['para'] = $ps['para']; }
					if($ps['mensagem']){ $presente_destinatario['mensagem'] = $ps['mensagem']; }
				}
				
				$descricao_extra = false;
				
				switch($status){
					case 'N': break;
					case 'A':
						if($ps['validade_tipo'] == 'D'){
							$descricao_extra .= '<br>Validade de Uso: '.data_hora_from_datetime_to_text($ps['validade_data']);
						} else {
							$data_full = $pedido['t2.data'];
							$data_arr = explode(' ',$data_full);
							
							if($ps['validade']){
								$periodo = $ps['validade'];
							} else {
								$periodo = $_ESERVICE['pedido_validade'];
							}
							
							$descricao_extra .= '<br>Validade de Uso: '.date("d/m/Y",strtotime($data_arr[0] . " + ".$periodo." day"));
						}
					
					break;
					default:
						
				}
				
				if($pedido_proc[$ps['id_servicos']]['observacao'])$descricao_extra .= '<br>Observação: '.nl2br($pedido_proc[$ps['id_servicos']]['observacao']);
				
				// Idenficação
				
				if($ps['identificacao_nome']){
					$identificacao_nome = $ps['identificacao_nome'];
					$identificacao_documento = $ps['identificacao_documento'];
					$identificacao_telefone = $ps['identificacao_telefone'];
				} else {
					$identificacao_nome = $loja_usuarios['nome'].' '.$loja_usuarios['ultimo_nome'];
					$identificacao_documento = ($loja_usuarios['cnpj_selecionado'] ? $loja_usuarios['cnpj'] : $loja_usuarios['cpf']);
					$identificacao_telefone = $loja_usuarios['telefone'];
				}
				
				$identificacao = Array(
					'nome' => $identificacao_nome,
					'documento' => $identificacao_documento,
					'telefone' => $identificacao_telefone,
				);
				
				$total += $sub_total;
				$count++;
				
				$voucher = Array(
					'id_pedidos' => $id_pedidos,
					'id_pedidos_servicos' => $ps['id_pedidos_servicos'],
					'codigo' => $ps['codigo'],
					'nome' => ($ps['nome'] ? $ps['nome'] : $pedido_proc[$ps['id_servicos']]['nome']),
					'status' => $status_servico,
					'status_texto' => $ps['status'],
					'status_cor' => $status_servico_cor,
					'imagem' => $pedido_proc[$ps['id_servicos']]['imagem_path'],
					'preco' => 'R$ '.preparar_float_4_texto(number_format($sub_total, 2, '.', '')),
					'identificacao' => $identificacao,
				);
				
				if($status_servico == 'F'){
					$voucher['protocolo_baixa'] = 'Protocolo: '.data_hora_from_datetime_to_text($ps['data_baixa'].' '.$ps['protocolo_baixa'].($ps['observacao_baixa'] ? '<br>Observação: '.$ps['observacao_baixa'] : ''));
				}
				
				if($para_presente) $voucher['para_presente'] = true;
				if($presente_destinatario) $voucher['presente_destinatario'] = $presente_destinatario;
				if($qrcode) $voucher['qrcode'] = $qrcode;
				if($voucher_layouts) $voucher['voucher_layouts'] = $voucher_layouts;
				if($descricao_extra) $voucher['descricao_extra'] = $descricao_extra;
				
				$vouchers[] = $voucher;
			}
			
			$pedidos_data = Array(
				'id_pedidos' => $id_pedidos,
				'codigo' => $codigo,
				'total' => 'R$ '.preparar_float_4_texto(number_format($total, 2, '.', '')),
				'data' => $data,
				'status' => $status,
				'status_texto' => $pedido['t2.status'],
				'status_cor' => $status_cor,
			);
			
			if($status == 'F'){
				$pedidos_data['protocolo_baixa'] = 'Protocolo: '.data_hora_from_datetime_to_text($pedido['t2.data_baixa'].' '.$pedido['t2.protocolo_baixa'].($pedido['t2.observacao_baixa'] ? '<br>Observação: '.$pedido['t2.observacao_baixa'] : ''));
			}
			
			$pedidos[] = $pedidos_data;
			
			$cont++;
			if($cont >= $limite){
				break;
			}
		}
		
		if($limite >= count($resultado)){
			$sem_mais_resultados = true;
		}
		
		if(!$page){
			$voucher_layouts = banco_select_name
			(
				banco_campos_virgulas(Array(
					'imagem_topo',
					'id_voucher_layouts',
				))
				,
				"voucher_layouts",
				"WHERE id_loja='".$id_loja."'"
				." ORDER BY id_voucher_layouts DESC"
				." LIMIT 30"
			);
			
			if($voucher_layouts)
			foreach($voucher_layouts as $vl){
				$voucher_layouts_todos[] = Array(
					'id' => $vl['id_voucher_layouts'],
					'imagem' => $_B2MAKE_URL.$vl['imagem_topo'],
				);
			}
		}
		
		$retorno = Array(
			'status' => 'OK',
			'autorizado' => 'true',
			'pedidos' => $pedidos,
			'vouchers' => $vouchers,
		);
		
		if($voucher_layouts_todos) $retorno['voucher_layouts_todos'] = $voucher_layouts_todos;
		if($sem_mais_resultados) $retorno['sem_mais_resultados'] = true;
		
		return $retorno;
	} else {
		$texto = $sem_resultados_titulo;
		return Array(
			'status' => 'OK',
			'semResultados' => 'true',
			'texto' => $texto,
		);
	}
}

function platform_purchases_pay($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$id = $_REQUEST['id'];
	
	if(!$loja_usuarios || !$id){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	banco_update
	(
		"pedido_atual=NULL",
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
	);
	banco_update
	(
		"pedido_atual='1'",
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND id_pedidos='".$id."'"
	);
	
	$retorno = Array(
		'status' => 'OK',
		'autorizado' => 'true',
	);
	
	return $retorno;
}

function platform_purchases_identification_save($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$id_pedidos = $_REQUEST['id_pedidos'];
	$id_pedidos_servicos = $_REQUEST['id_pedidos_servicos'];
	$identificacao_nome = $_REQUEST['identificacao_nome'];
	$identificacao_documento = $_REQUEST['identificacao_documento'];
	$identificacao_telefone = $_REQUEST['identificacao_telefone'];
	
	if(!$loja_usuarios || !$id_pedidos || !$id_pedidos_servicos){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];

	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_pedidos='".$id_pedidos."'"
		." AND id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
	);
	
	if($loja_usuarios_pedidos){
		$pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND status='A'"
		);
		
		if($pedidos){
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'codigo',
				))
				,
				"pedidos_servicos",
				"WHERE id_pedidos='".$id_pedidos."'"
				." AND id_pedidos_servicos='".$id_pedidos_servicos."'"
				." AND status='A'"
			);
			
			if($pedidos_servicos){
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
				
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].' '.$loja_usuarios['ultimo_nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> alterou a identificação do voucher do serviço de código ['.$pedidos_servicos[0]['codigo'].'] para nome -> '.$identificacao_nome.', documento -> '.$identificacao_documento.', telefone -> '.$identificacao_telefone,
				));
				
				$retorno = Array(
					'status' => 'Ok',
					'autorizado' => 'true',
				);
			} else {
				$retorno = Array(
					'msg' => 'Não é permitido alterar a identificação de serviços inativos no sistema.',
					'naoAutorizado' => 'true',
				);
			}
		} else {
			$retorno = Array(
				'msg' => 'Não é permitido alterar a identificação de pedidos inativos no sistema.',
				'naoAutorizado' => 'true',
			);
		}
	} else {
		$retorno = Array(
			'msg' => 'Não é possível alterar a identificação!</p><p>Esse pedido não pertence ao seu usuário.',
			'naoAutorizado' => 'true',
		);
	}
	
	return $retorno;
}

function platform_purchases_email_enviar($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$loja_usuarios_senha = $_REQUEST['loja_usuarios_senha'];
	$id_pedidos = $_REQUEST['id_pedidos'];
	$id_pedidos_servicos = $_REQUEST['id_pedidos_servicos'];
	$email = $_REQUEST['email'];
	
	if(!$loja_usuarios || !$loja_usuarios_senha || !$id_pedidos || !$id_pedidos_servicos || !$email){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];

	if($email){
		$loja_usuarios_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		);
		
		if($loja_usuarios_pedidos){
			$pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'envios_email',
					'id_loja',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
				." AND status='A'"
			);
			
			if($pedidos){
				if($pedidos[0]['envios_email']){
					if($pedidos[0]['envios_email'] > 10){
						$flag = true;
					}
				} else {
					banco_update
					(
						"envios_email=0",
						"pedidos",
						"WHERE id_pedidos='".$id_pedidos."'"
					);
				}
				
				if(!$flag){
					banco_update
					(
						"envios_email=envios_email+1",
						"pedidos",
						"WHERE id_pedidos='".$id_pedidos."'"
					);
					
					$loja_nome = $loja[0]['nome'];
					
					$assunto = $_VARS['ecommerce']['voucher_email_assunto'];
					$mensagem = $_VARS['ecommerce']['voucher_email_mensagem'];
					
					$pedidos_servicos = banco_select_name
					(
						banco_campos_virgulas(Array(
							'codigo',
						))
						,
						"pedidos_servicos",
						"WHERE id_pedidos='".$id_pedidos."'"
						." AND id_pedidos_servicos='".$id_pedidos_servicos."'"
						." AND status='A'"
					);
					
					if($pedidos_servicos){
						$voucher = platform_voucher(Array(
							'id_loja' => $id_loja,
							'loja_usuarios' => $loja_usuarios,
							'loja_usuarios_senha' => $loja_usuarios_senha,
							'pedido_id' => $id_pedidos,
							'pedido_servico_id' => $id_pedidos_servicos,
							'mail' => true,
						));
						
						$mensagem = $voucher['voucher'];
						$embedded_imgs = $voucher['mail_imgs'];
						
						$mensagem = modelo_var_troca($mensagem,"#html-title#",$pedidos[0]['codigo']);
						
						$assunto = modelo_var_troca($assunto,"#codigo#",$pedidos_servicos[0]['codigo']);
						
						email_enviar(Array(
							'from_name' => $loja_nome,
							'email_nome' => $email_nome,
							'email' => $email,
							'assunto' => $assunto,
							'mensagem' => $mensagem,
							'embedded_imgs' => $embedded_imgs,
							'html_sem_modelo' => true,
							'nao_inserir_assinatura' => true,
						));
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> enviou voucher do serviço de código ['.$pedidos_servicos[0]['codigo'].'] para o seguinte email -> '.$email,
						));
						
						$retorno = Array(
							'status' => 'Ok',
							'autorizado' => 'true',
						);
					} else {
						$retorno = Array(
							'msg' => 'Não é permitido enviar emails de serviços inativos no sistema.',
							'naoAutorizado' => 'true',
						);
					}
				} else {
					$retorno = Array(
						'msg' => 'Você já enviou mais de 10 emails para esse pedido</p><p>Não é permitido enviar mais emails para esse pedido.',
						'naoAutorizado' => 'true',
					);
				}
			} else {
				$retorno = Array(
					'msg' => 'Não é permitido enviar emails de pedidos inativos no sistema.',
					'naoAutorizado' => 'true',
				);
			}
		} else {
			$retorno = Array(
				'msg' => 'Não é possível enviar esse email!</p><p>Esse pedido não pertence ao seu usuário.',
				'naoAutorizado' => 'true',
			);
		}
	} else {
		$retorno = Array(
			'msg' => 'Não é possível enviar esse email!</p><p>O email e/ou o voucher não está definido.',
			'naoAutorizado' => 'true',
		);
	}
	
	return $retorno;
}

function platform_purchases_print($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$loja_usuarios_senha = $_REQUEST['loja_usuarios_senha'];
	$id_pedidos = $_REQUEST['id_pedidos'];
	$id_pedidos_servicos = $_REQUEST['id_pedidos_servicos'];
	$mobile = $_REQUEST['mobile'];
	
	if(!$loja_usuarios || !$loja_usuarios_senha || !$id_pedidos || !$id_pedidos_servicos){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];

	$voucher = platform_voucher(Array(
		'id_loja' => $id_loja,
		'loja_usuarios' => $loja_usuarios,
		'loja_usuarios_senha' => $loja_usuarios_senha,
		'pedido_id' => $id_pedidos,
		'pedido_servico_id' => $id_pedidos_servicos,
		'mobile' => $mobile,
	));
	
	$retorno = Array(
		'status' => 'Ok',
		'autorizado' => 'true',
		'voucher' => $voucher['voucher'],
		'titulo' => $voucher['titulo'],
	);
	
	return $retorno;
}

function platform_purchases_view($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$loja_usuarios_senha = $_REQUEST['loja_usuarios_senha'];
	$id_pedidos = $_REQUEST['id_pedidos'];
	$id_pedidos_servicos = $_REQUEST['id_pedidos_servicos'];
	$mobile = $_REQUEST['mobile'];
	
	if(!$loja_usuarios || !$loja_usuarios_senha || !$id_pedidos || !$id_pedidos_servicos){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];

	$voucher = platform_voucher(Array(
		'id_loja' => $id_loja,
		'loja_usuarios' => $loja_usuarios,
		'loja_usuarios_senha' => $loja_usuarios_senha,
		'pedido_id' => $id_pedidos,
		'pedido_servico_id' => $id_pedidos_servicos,
		'mobile' => $mobile,
	));
	
	$retorno = Array(
		'status' => 'Ok',
		'autorizado' => 'true',
		'voucher' => $voucher['voucher'],
	);
	
	return $retorno;
}

function platform_purchases_presente_mudar($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$id_pedidos = $_REQUEST['id_pedidos'];
	$id_pedidos_servicos = $_REQUEST['id_pedidos_servicos'];
	$tipo = $_REQUEST['tipo'];
	
	if(!$loja_usuarios || !$id_pedidos || !$id_pedidos_servicos || !$tipo){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND id_pedidos='".$id_pedidos."'"
	);
	
	if($loja_usuarios_pedidos){
		banco_update
		(
			"presente=".($tipo == 'presente' ? '1' : 'NULL'),
			"pedidos_servicos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND id_pedidos_servicos='".$id_pedidos_servicos."'"
		);
	}
	
	$retorno = Array(
		'status' => 'Ok',
		'autorizado' => 'true',
	);
	
	return $retorno;
}

function platform_purchases_data_save($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	$id_pedidos = $_REQUEST['id_pedidos'];
	$id_pedidos_servicos = $_REQUEST['id_pedidos_servicos'];
	$imagem_id = $_REQUEST['imagem_id'];
	$de = $_REQUEST['de'];
	$para = $_REQUEST['para'];
	$mensagem = $_REQUEST['mensagem'];
	
	if(!$loja_usuarios || !$id_pedidos || !$id_pedidos_servicos || !$imagem_id){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$loja_usuarios_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND id_pedidos='".$id_pedidos."'"
	);
	
	if($loja_usuarios_pedidos){
		banco_update
		(
			"de=".($de ? "'".$de."'" : 'NULL').",".
			"para=".($para ? "'".$para."'" : 'NULL').",".
			"mensagem=".($mensagem ? "'".$mensagem."'" : 'NULL').",".
			"id_voucher_layouts=".($imagem_id != '-1' ? $imagem_id : 'NULL'),
			"pedidos_servicos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND id_pedidos_servicos='".$id_pedidos_servicos."'"
		);
	}
	
	$retorno = Array(
		'status' => 'Ok',
		'autorizado' => 'true',
	);
	
	return $retorno;
}

function platform_account($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	
	if(!$loja_usuarios){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
			'data',
		))
		,
		"log",
		"WHERE id_referencia='".$id_loja.'-'.$loja_usuarios['id_loja_usuarios']."'"
		." AND grupo='loja_usuarios'"
		." ORDER BY data DESC"
	);
	
	if($resultado){
		foreach($resultado as $res){
			$historico .= data_hora_from_datetime_to_text($res['data']).' - '.$res['valor'].'<br>';
		}
	}
	
	$retorno = Array(
		'status' => 'Ok',
		'autorizado' => 'true',
	);
	
	if($historico) $retorno['historico'] = $historico;
	
	return $retorno;
}

function platform_account_update($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_VARS;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'nome',
			'email_assinatura',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$loja_usuarios = json_decode(stripslashes($_REQUEST['loja_usuarios']),true);
	
	if(!$loja_usuarios){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$_REQUEST['nome'] = $_REQUEST['nome'];
	$_REQUEST['ultimo-nome'] = $_REQUEST['ultimo-nome'];
	$loja_usuarios['nome'] = $loja_usuarios['nome'];
	$loja_usuarios['ultimo_nome'] = $loja_usuarios['ultimo_nome'];
	
	$campo_tabela = "loja_usuarios";
	$campo_tabela_extra = "WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'";
	
	$padrao_texto_log = 'O usuário <b>['.$loja_usuarios['email'].']</b> alterou o(s) seguinte(s) campo(s): ';
	$padrao_texto_log_cel = '<b>#campo#</b> de <b>#de#</b> para <b>#para#</b>. ';
	$padrao_nada = 'NADA';
	
	$campo_nome = "nome"; if($loja_usuarios[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($loja_usuarios[$campo_nome] ? $loja_usuarios[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
	$campo_nome = "ultimo-nome"; $campo_nome_bd = "ultimo_nome"; if($loja_usuarios[$campo_nome_bd] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome_bd."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome_bd); $cel = modelo_var_troca($cel,"#de#",($loja_usuarios[$campo_nome_bd] ? $loja_usuarios[$campo_nome_bd] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
	$campo_nome = "telefone"; if($loja_usuarios[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($loja_usuarios[$campo_nome] ? $loja_usuarios[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
	$campo_nome = "cpf"; if($loja_usuarios[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($loja_usuarios[$campo_nome] ? $loja_usuarios[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
	$campo_nome = "cnpj"; if($loja_usuarios[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($loja_usuarios[$campo_nome] ? $loja_usuarios[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
	
	$campo_nome = "cnpj_selecionado"; if($_REQUEST['cnpj_selecionado'] == 'sim'){ $editar[$campo_tabela][] = $campo_nome."=1"; } else { $editar[$campo_tabela][] = $campo_nome."=NULL"; }
	
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
	
	if($historico){
		$campos = null;
		
		$campo_nome = "id_referencia"; $campo_valor = $id_loja.'-'.$loja_usuarios['id_loja_usuarios']; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "grupo"; $campo_valor = 'loja_usuarios'; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "valor"; $campo_valor = $padrao_texto_log . $historico; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data"; $campo_valor = 'NOW()'; 																$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"log"
		);
	}
	
	$retorno = Array(
		'status' => 'Ok',
		'autorizado' => 'true',
		'msg' => 'Seus dados foram atualizados com sucesso!',
	);
	
	return $retorno;
}

// ====================================== Funções de Controle e Manipulação de Dados ======================================

function platform_authorize($_params = false){
	global $_LOCAL_ID;
	global $_LAST_XML;
	global $_USUARIO_ID;
	global $_HOST_ID;
	
	if($_params)foreach($_params as $var => $val)$$var = $val;
	
	$pub_id = $_REQUEST['pub_id'];
	$token = $_REQUEST['token'];
	
	if(!$authorized)
	if((!$pub_id || !$token)){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Not authorized (0)',
		)));
	}
	
	$usuario = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_usuario',
		))
		,
		"usuario",
		"WHERE status='A'"
		." AND pub_id='".$pub_id."'"
	);
	
	if(!$authorized)
	if(!$usuario){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Not authorized (1)',
		)));
	}
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_host',
			'url',
		))
		,
		"host",
		"WHERE status='A'"
		." AND id_usuario='".$usuario[0]['id_usuario']."'"
	);
	
	$_USUARIO_ID = $usuario[0]['id_usuario'];
	
	if(!$authorized)
	if(!$host){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Not authorized (2)',
		)));
	}
	
	$_HOST_ID = $host[0]['id_host'];
	
	if($authorized){
		return;
	}
	
	$url = parse_url($host[0]['url'], PHP_URL_HOST);
	
	$url = $url . '/platform/authorize/';
	
	$data = false;
	$data['pub_id'] = $pub_id;
	$data['token'] = $token;
	
	if($params)$data = array_merge($data,$params);
	
	$data = http_build_query($data);
	$curl = curl_init($url);

	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	$xml = curl_exec($curl);
	$_LAST_XML = $xml;
	
	curl_close($curl);
	
	libxml_use_internal_errors(true);
	$obj_xml = simplexml_load_string($xml);
	
	if(!$obj_xml){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Not authorized (3)',
		)));
	}
	
	if($obj_xml->error){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Not authorized (4)',
		)));
	}
	
	if($obj_xml->status != 'OK'){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Not authorized (5)',
		)));
	}
}

function platform_echo_xml($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	header('Content-Type: text/xml; charset=UTF-8;');
	echo formatar_xml($data);
	banco_fechar_conexao();
	exit;
}

function platform_opcao_nao_econtrada(){
	platform_echo_xml(Array('data'=>Array(
		'error' => 'Not defined option (0)',
	)));
}

function platform_gravar_log($params = false){
	global $_USUARIO_ID;
	global $_SYSTEM;
	global $_ESERVICES;
	global $_B2MAKE_URL;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	platform_authorize();
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO_ID."'"
	);
	
	if(!$loja){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Store not found (0)',
		)));
	}
	
	$id_loja = $loja[0]['id_loja'];
	
	$id_pedido_atual = $_REQUEST['id_pedido_atual'];
	$msg = $_REQUEST['msg'];
	$erro = $_REQUEST['erro'];
	
	if(!$id_pedido_atual || !$msg || !$erro){
		platform_echo_xml(Array('data'=>Array(
			'error' => 'Data not informed (0)',
		)));
	}
	
	$msg = $msg;
	$erro = $erro;

	$pedido = banco_select_name
	(
		banco_campos_virgulas(Array(
			'codigo',
			'status',
			'valor_total',
		))
		,
		"pedidos",
		"WHERE id_pedidos='".$id_pedido_atual."'"
		." AND id_loja='".$id_loja."'"
	);
	
	if($pedido){
		log_banco(Array(
			'id_referencia' => $id_pedido_atual,
			'grupo' => 'pedidos',
			'valor' => '<b>PayPal Plus:</b> tentativa de pagamento com seguinte erro: <b>mensagem de alerta: '.$msg.'</b> | <b>retorno da API de pagamento: '.$erro.'</b>.',
		));
		
		return Array(
			'status' => 'OK',
		);
	} else {
		return Array(
			'status' => 'OK',
			'naoAutorizado' => 'true',
		);
	}
}

// ====================================== Execução principal do módulo ====================================== 

function platform_main(){
	global $_CAMINHO;
	global $_XML_LOCAL;
	global $_XML_CHARSET;
	
	$_XML_LOCAL = 'platform';
	
	$opcao = $_CAMINHO[1];
	
	switch($opcao){
		case 'cart':							$saida = platform_cart(); break;
		case 'logar':							$saida = platform_logar(); break;
		case 'signup-request':					$saida = platform_signup_request(); break;
		case 'checkout':						$saida = platform_checkout(); break;
		case 'request-register':				$saida = platform_request_register(); break;
		case 'forgot-your-password-request':	$saida = platform_forgot_your_password_request(); break;
		case 'generate-new-password':			$saida = platform_generate_new_password(); break;
		case 'password-reset-request':			$saida = platform_password_reset_request(); break;
		case 'emission':						$saida = platform_emission(); break;
		case 'emission-request':				$saida = platform_emission_request(); break;
		case 'payment':							$saida = platform_payment(); break;
		case 'ppplus-pay':						$saida = platform_ppplus_pay(); break;
		case 'ppb-attempt-pay':					$saida = platform_ppb_attempt_pay(); break;
		case 'ppplus-other-payer-attempt-pay':	$saida = platform_ppplus_other_payer_attempt_pay(); break;
		case 'gravar-log':						$saida = platform_gravar_log(); break;
		case 'purchases':						$saida = platform_purchases(); break;
		case 'purchases-pay':					$saida = platform_purchases_pay(); break;
		case 'purchases-identification-save':	$saida = platform_purchases_identification_save(); break;
		case 'purchases-email-enviar':			$saida = platform_purchases_email_enviar(); break;
		case 'purchases-print':					$saida = platform_purchases_print(); break;
		case 'purchases-view':					$saida = platform_purchases_view(); break;
		case 'purchases-presente-mudar':		$saida = platform_purchases_presente_mudar(); break;
		case 'purchases-data-save':				$saida = platform_purchases_data_save(); break;
		case 'account':							$saida = platform_account(); break;
		case 'account-update':					$saida = platform_account_update(); break;
		case 'site-version':					$saida = platform_site_version(); break;
		case 'sitemaps':						$saida = platform_sitemaps(); break;
		case 'library':							$saida = platform_library(); break;
		case 'tags':							$saida = platform_tags(); break;
		case 'conteudos':						$saida = platform_conteudos(); break;
		case 'services':						$saida = platform_services(); break;
		case 'atualizar':						$saida = platform_atualizar_dados(); break;
		default: 								$saida = platform_opcao_nao_econtrada();
	}
	
	$_XML_CHARSET = 'UTF-8';
	header('Content-Type: text/xml; charset='.$_XML_CHARSET.';');
	echo formatar_xml($saida);
	banco_fechar_conexao();
	exit;
}

return platform_main();

?>