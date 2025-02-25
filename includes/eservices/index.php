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

// Funções Internas

global $_VERSAO;
global $_SCRIPTS_JS;$_SCRIPTS_JS[] = 'includes/eservices/js.js?v='.$_VERSAO;
global $_STYLESHEETS;$_STYLESHEETS[] = 'includes/eservices/css.css?v='.$_VERSAO;
global $_JS;global $_CAMINHO_RELATIVO_RAIZ;global $_HTML;global $_VERSAO;$_JS['maskedInput'] = '<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'maskedInput/jquery.mask.js?v='.$_VERSAO.'" type="text/javascript"></script>';

global $_ESERVICES;
global $_PROJETO;

$_ESERVICES['status_mudar'] = $_PROJETO['B2MAKE_STORE_STATUS_MUDAR_TITULO'];
$_ESERVICES['status_mudar_cores'] = $_PROJETO['B2MAKE_STORE_STATUS_MUDAR_CORES'];

// ====================================== PayPal Plus ======================================

function eservice_paypal_plus_lista_servicos($id_pedidos){
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't3.nome',
			't3.observacao',
			't2.sub_total',
			't2.validade',
			't2.validade_data',
			't2.validade_tipo',
			't2.id_servicos',
		))
		,
		"pedidos as t1,pedidos_servicos as t2,servicos as t3",
		"WHERE t1.id_pedidos='".$id_pedidos."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t2.id_servicos=t3.id_servicos"
	);
	
	if($resultado)
	foreach($resultado as $res){
		$found = false;
		if($formatado)
		foreach($formatado as $key => $val){
			if($val['id_servicos'] == $res['t2.id_servicos']){
				$found = true;
				$formatado[$key]['quant']++;
				break;
			}
		}
		
		if(!$found){
			$formatado[] = Array(
				'quant' => 1,
				'sub_total' => (float)$res['t2.sub_total'],
				'id_servicos' => $res['t2.id_servicos'],
				'nome' => $res['t3.nome'],
				'validade' => $res['t2.validade'],
				'validade_data' => $res['t2.validade_data'],
				'validade_tipo' => $res['t2.validade_tipo'],
				'observacao' => $res['t3.observacao'],
			);
		}
	}
	
	$table = '
<h3>Seu Pedido</h3>
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:15px 0px 0px 0px;">
    <tr style="background-color:#E1E1E1;">
        <td style="font-weight:bold;">Serviço</td>
        <td style="width:20px;text-align:center;font-weight:bold;">Quant.</td>
        <td style="width:110px;text-align:center;font-weight:bold;">Preço unit.</td>
        <td style="width:110px;text-align:center;font-weight:bold;">Sub Total</td>
    </tr>
    <!-- item_tr -->
</table>';

	$servicos_total = 0;
	$valor_total = 0;

	$cel_nome = 'item_tr';
	if($formatado)
	foreach($formatado as $for){
		$sub_total = $for['quant'] * $for['sub_total'];
		
		$servicos_total += $for['quant'];
		$valor_total += $sub_total;
		
		$item_tr = '<tr>'."\n";
		$item_tr .= '	<td style="border:solid 1px #FFF;background-color:#FFF;">'.$for['nome'].'' . ($for['validade_tipo'] == 'D' ? ($for['validade_data']? '<br>Valido até <b>'.data_hora_from_datetime_to_text($for['validade_data']).'</b>' : '') : ($for['validade']? '<br>Validade de <b>'.$for['validade'].'</b> dia(s)' : '')) . ($for['observacao']? '<br>Observa&ccedil;&atilde;o: '.nl2br($for['observacao']) : '') . '</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">'.$for['quant'].'</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">R$ '.preparar_float_4_texto(number_format($for['sub_total'], 2, '.', '')).'</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">R$ '.preparar_float_4_texto(number_format($sub_total, 2, '.', '')).'</td>'."\n";
		$item_tr .= '</tr>';
		
		$table = modelo_var_in($table,'<!-- '.$cel_nome.' -->',$item_tr);
	}
	$table = modelo_var_troca($table,'<!-- '.$cel_nome.' -->','');
	
	$table .= '
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:0px 0px 15px 0px;">
    <tr cellspacing="5">
		<td style="font-weight:bold;">'.$servicos_total.' serviço(s) no pedido.</td>
		<td style="text-align:right;font-weight:bold;">Total dos Serviços: R$ '.preparar_float_4_texto(number_format($valor_total, 2, '.', '')).'</td>
	</tr>
</table>';
	
	return $table;
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

function eservice_paypal_plus_attempt_pay($params = false){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PROJETO;
	global $_B2MAKE_URL;
	global $_ESERVICE;
	global $_VARIAVEIS_JS;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
	
	if($_PROJETO['PPPLUS_TESTES'] && !$outro_pagador){
		$_VARIAVEIS_JS['ppplus_testes'] = true;
		
		return '
	<div id="paypal-plus-comprador-cont" class="b2make-payment-formas-cont" data-id="cartao-de-credito" data-selecionado="sim">
		<img id="paypal-plus-comprador-testes-img" src="!#caminho_raiz#!includes/eservices/images/b2make-paypalplus-testes.png" style="display:none;">
	</div>';
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
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
				$retorno = eservice_paypal_plus_token_generate(Array(
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
					
					if($outro_pagador){
						return Array(
							'msg' => $msg,
							'status' => 'ErrorRenewToken',
						);
					} else {
						alerta($msg);
						redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
					}
				} else {
					if($loja[0]['paypal_app_live']){
						$loja[0]['paypal_app_token'] = $retorno['token'];
					} else {
						$loja[0]['paypal_app_sandbox_token'] = $retorno['token'];
					}
				}
			}
			
			if($loja_problema){
				$msg = '<p style="color:red;">Não é possível pagar o seu pedido atual de código: <b>'.$codigo_referencia.'</b> com PayPal Plus uma vez que esta loja está momentaneamente fora de serviço. Tente novamente mais tarde.</p>';
				
				if($outro_pagador){
					return Array(
						'msg' => $msg,
						'status' => 'ErrorStoreInactive',
					);
				} else {
					alerta($msg);
					redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
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
					'return_url' => $_B2MAKE_URL.'e-services/'.$_ESERVICE['loja-atual'].'/paypalplus-return/' . $pub_id,
					'cancel_url' => $_B2MAKE_URL.'e-services/'.$_ESERVICE['loja-atual'].'/paypalplus-cancel/' . $pub_id,
				);
				
				if($outro_pagador){
					if(
						!$_REQUEST['nome'] ||
						!$_REQUEST['ultimo_nome'] ||
						!$_REQUEST['email'] ||
						!$_REQUEST['telefone'] ||
						!$_REQUEST['cpf_cnpj_check'] ||
						($_REQUEST['cpf_cnpj_check'] == 'CNPJ' ? !$_REQUEST['cnpj'] : !$_REQUEST['cpf'])
					){
						$msg = '<p style="color:red;">É necessário preencher todos os dados do comprador antes de clicar no botão CONTINUAR.</p>';
						
						return Array(
							'msg' => $msg,
							'status' => 'ErrorIncompleteBuyerData',
						);
					}
					
					$pagador = Array(
						'first_name' => $_REQUEST['nome'],
						'last_name' => $_REQUEST['ultimo_nome'],
						'email' => $_REQUEST['email'],
						'telefone' => $_REQUEST['telefone'],
						'cnpj_selecionado' => ($_REQUEST['cpf_cnpj_check'] == 'CNPJ' ? true : false),
						'cpf' => $_REQUEST['cpf'],
						'cnpj' => $_REQUEST['cnpj'],
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
					
					if($outro_pagador){
						return Array(
							'msg' => $msg,
							'status' => 'ErrorAttemptPay',
						);
					} else {
						alerta($msg);
						redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
					}
				} else {
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus:</b> requisição de pagamento criada com sucesso. ID: <b>'.$identificador_externo.' - '.($outro_pagador ? 'CARTÃO DE CRÉDITO DE TERCEIRO' : 'CARTÃO DE CRÉDITO PRÓPRIO').'</b>',
					));
				}
				
				$pagador['telefone'] = preg_replace("/[^0-9]/", "", $pagador['telefone']);
				$pagador['cpf'] = preg_replace("/[^0-9]/", "", $pagador['cpf']);
				$pagador['cnpj'] = preg_replace("/[^0-9]/", "", $pagador['cnpj']);
				
				if($outro_pagador){
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
						'status' => 'Ok',
					);
				} else {
					$_VARIAVEIS_JS['ppp_ativo'] = ($loja[0]['paypal_app_live'] ? 'sim' : 'nao');
					$_VARIAVEIS_JS['ppp_link_approval_url'] = $paypal_plus_link_approval_url;
					$_VARIAVEIS_JS['ppp_id'] = $identificador_externo;
					
					$_VARIAVEIS_JS['ppp_first_name'] = $pagador['first_name'];
					$_VARIAVEIS_JS['ppp_last_name'] = $pagador['last_name'];
					$_VARIAVEIS_JS['ppp_email'] = $pagador['email'];
					$_VARIAVEIS_JS['ppp_telefone'] = $pagador['telefone'];
					
					if($pagador['cnpj_selecionado']){
						$_VARIAVEIS_JS['ppp_document_type'] = 'BR_CNPJ';
						$_VARIAVEIS_JS['ppp_document'] = $pagador['cnpj'];
					} else {
						$_VARIAVEIS_JS['ppp_document_type'] = 'BR_CPF';
						$_VARIAVEIS_JS['ppp_document'] = $pagador['cpf'];
					}
					
					$_VARIAVEIS_JS['ppp_remembered_card_hash'] = $pagador['ppp_remembered_card_hash'];
					
					$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
					$paypal_plus = modelo_tag_val($modelo,'<!-- paypal_plus_cont < -->','<!-- paypal_plus_cont > -->');
					
					return $paypal_plus;
				}
			} else {
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>PayPal Plus:</b> bloqueio - usuário foi bloqueado momentaneamente para pagar este pedido por exceder o limite de tentativas de pagamento. São permitidas <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX'].'</b> tentativas em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.',
				));
				
				$msg = '<p style="color:red;">Voc&ecirc; foi bloqueado momentaneamente para pagar este pedido por exceder o limite de tentativas de pagamento. S&atilde;o permitidas <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX'].'</b> tentativas em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.</p><p>Tente pagar novamente em <b>'.$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS'].'</b> minutos.</p>';
				
				if($outro_pagador){
					return Array(
						'msg' => $msg,
						'status' => 'ErrorAttemptsTries',
					);
				} else {
					alerta($msg);
					redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
				}
			}
		} else {
			alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: paypal_plus_pay 3.</p>');
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/');
		}
	} else {
		alerta('<p>Você ainda não tem pedidos cadastrados para fazer pagamentos.</p>');
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/');
	}
}

function eservice_paypal_button_attempt_pay($params = false){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PROJETO;
	global $_B2MAKE_URL;
	global $_ESERVICE;
	global $_VARIAVEIS_JS;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
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
			
			if($gerar_token){
				$retorno = eservice_paypal_plus_token_generate(Array(
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
					'return_url' => $_B2MAKE_URL.'e-services/'.$_ESERVICE['loja-atual'].'/paypalplus-button-return/' . $pub_id,
					'cancel_url' => $_B2MAKE_URL.'e-services/'.$_ESERVICE['loja-atual'].'/paypalplus-button-cancel/' . $pub_id,
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
						'valor' => '<b>PayPal Plus:</b> requisição de pagamento criada com sucesso. ID: <b>'.$identificador_externo.' - PAGUE COM O PAYPAL</b>',
					));
				}
				
				return Array(
					'id' => $identificador_externo,
					'status' => 'Ok',
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

function eservice_paypal_plus_pay(){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PROJETO;
	global $_B2MAKE_URL;
	global $_ESERVICE;
	global $_VARIAVEIS_JS;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
	
	if(
		!$_REQUEST['payerID'] || 
		!$_REQUEST['installmentsValue'] || 
		!$_REQUEST['ppp_id']
	)
		return Array(
			'status' => 'RequestsDontInformed'
		);
		
	$ppp_id = $_REQUEST['ppp_id'];
	$payerID = $_REQUEST['payerID'];
	$installmentsValue = $_REQUEST['installmentsValue'];
	$rememberedCard = $_REQUEST['rememberedCard'];
	$outroPagador = $_REQUEST['outroPagador'];
	
	if($_REQUEST['paypalButton'] == 'sim') $paypalButton = true;
	
	if($outroPagador != 'sim'){
		$_SESSION[$_SYSTEM['ID']."loja_usuarios"]["ppp_remembered_card_hash"] = $rememberedCard;
		
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
	
	if(!$loja_usuarios_pedidos)
		return Array(
			'status' => 'UserUnknown'
		);
	
	$id_pedidos = $loja_usuarios_pedidos[0]['id_pedidos'];
	
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
	
	if($gerar_token){
		$retorno = eservice_paypal_plus_token_generate(Array(
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
			
			return Array(
				'erro' => 3,
				'erro_msg' => '<p style="color:red;">Houve um problema na renovação do token com o PayPal Plus. Tente novamente mais tarde. Mensagem de retorno: <b>'.$retorno['erro_msg'].'</b></p>',
				'status' => 'PayPalPlusErrorToken',
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
		$saida = Array(
			'status' => 'PayPalPlusEmptyResult',
			'erro' => 1,
			'erro_msg' => "N&atilde;o foi poss&iacute;vel finalizar o pagamento do PayPal Plus devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.",
		);
	} else {
		$json = json_decode($result);
		
		if($httpcode == '400'){
			switch($json->name){
				case 'INSTRUMENT_DECLINED': $msg = '<b>Sua operadora de cart&otilde;es N&Atilde;O autorizou o pagamento. Favor entrar em contato com o seu banco afim de saber como proceder. Depois disso, tente pagar novamente.</b><br>Erro Completo: '; break;
			}
			
			$saida = Array(
				'status' => 'PayPalPlusCustomError',
				'erro' => 2,
				'erro_msg' => $msg."N&atilde;o foi poss&iacute;vel finalizar o pagamento do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->name." - ".$json->message." - <a href=\"".$json->information_link."\" target=\"b2make-errors\">".$json->information_link."</a></b>.",
			);
		} else {
			$identificador_externo = $json->id;
			$ppplus_final_id = $json->transactions[0]->related_resources[0]->sale->id;
			$status = $json->transactions[0]->related_resources[0]->sale->state;
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>PayPal Plus:</b> finalização de pagamento executado com sucesso. Status retornado: <b>'.$status.'</b>',
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
				))
				,
				'pedidos_servicos as t1,servicos as t2',
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." ORDER BY t2.nome ASC"
			);
			
			foreach($pedidos_servicos as $res){
				$itens[] = Array(
					'name' => $res['t2.nome'],
					'quantity' => $res['t1.quantidade'],
					'price' => $res['t1.sub_total'],
					'id' => $res['t1.id_servicos'],
				);
				
				$total += (float)$res['t1.sub_total'];
			}
			
			$saida = Array(
				'pending' => $pending,
				'transaction_id' => $id_pedidos,
				'itens' => $itens,
				'total' => $total,
				'msg' => $msg,
				'status' => 'Ok',
			);
		}
	}
	
	if($saida['erro']){
		log_banco(Array(
			'id_referencia' => $id_pedidos,
			'grupo' => 'pedidos',
			'valor' => '<b>PayPal Plus:</b> erro na finalização de pagamento - ['.$saida['erro'].'] '.$saida['erro_msg'],
		));
	}
	
	return $saida;
}

function eservice_paypal_plus_outro_pagador_pre_pay(){
	global $_SYSTEM;
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$paypal_plus_outro = modelo_tag_val($modelo,'<!-- paypal_plus_outro_cont < -->','<!-- paypal_plus_outro_cont > -->');
	
	$paypal_plus_outro = modelo_var_troca($paypal_plus_outro,"#primeiro-nome#",$var);
	$paypal_plus_outro = modelo_var_troca($paypal_plus_outro,"#ultimo-nome#",$var);
	$paypal_plus_outro = modelo_var_troca($paypal_plus_outro,"#email#",$var);
	$paypal_plus_outro = modelo_var_troca($paypal_plus_outro,"#telefone#",$var);
	$paypal_plus_outro = modelo_var_troca($paypal_plus_outro,"#cpf#",$var);
	$paypal_plus_outro = modelo_var_troca($paypal_plus_outro,"#cnpj#",$var);
	
	return $paypal_plus_outro;
}

function eservice_paypal_cont(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	
	$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'paypal_app_live',
		))
		,
		"loja",
		"WHERE id_loja='".$id_loja."'"
	);
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$paypal = modelo_tag_val($modelo,'<!-- paypal_cont < -->','<!-- paypal_cont > -->');
	
	$_VARIAVEIS_JS['ppp_ativo'] = ($loja[0]['paypal_app_live'] ? 'sim' : 'nao');
	
	return $paypal;
}

function eservice_ppplus_webhooks(){
	global $_CAMINHO;
	global $_SYSTEM;
	global $_HTML;
	global $_VARS;
	
	$ambiente = $_CAMINHO[2];
	$pub_id = $_CAMINHO[3];
	$verificar_webhook = true;
	$webhook_verified = false;
	
	if($ambiente && $pub_id){
		$usuario = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario',
			))
			,
			"usuario",
			"WHERE pub_id='".$pub_id."'"
		);
		
		if($usuario){
			if($ambiente == 'live'){
				$loja = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_loja',
						'paypal_app_code',
						'paypal_app_secret',
						'paypal_app_token',
						'paypal_app_token_time',
						'paypal_app_expires_in',
						'paypal_app_webhook_id',
					))
					,
					"loja",
					"WHERE id_usuario='".$usuario[0]['id_usuario']."'"
				);
				
				$id_loja = $loja[0]['id_loja'];
				$gerar_token = false;
				
				if($loja[0]['paypal_app_token']){
					if((int)$loja[0]['paypal_app_token_time']+(int)$loja[0]['paypal_app_expires_in'] < time()){
						$gerar_token = true;
					}
				} else {
					$gerar_token = true;
				}
				
				if($gerar_token){
					$retorno = eservice_paypal_plus_token_generate(Array(
						'paypal_app_code' => $loja[0]['paypal_app_code'],
						'paypal_app_secret' => $loja[0]['paypal_app_secret'],
						'paypal_app_live' => true,
						'id_loja' => $id_loja,
					));
					
					if(!$retorno['erro']){
						$loja[0]['paypal_app_token'] = $retorno['token'];
					}
				}
				
				$token = $loja[0]['paypal_app_token'];
				$webhook_id = $loja[0]['paypal_app_webhook_id'];
			} else {
				$loja = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_loja',
						'paypal_app_sandbox_code',
						'paypal_app_sandbox_secret',
						'paypal_app_sandbox_token',
						'paypal_app_sandbox_token_time',
						'paypal_app_sandbox_expires_in',
						'paypal_app_sandbox_webhook_id',
					))
					,
					"loja",
					"WHERE id_usuario='".$usuario[0]['id_usuario']."'"
				);
				
				$id_loja = $loja[0]['id_loja'];
				$gerar_token = false;
				
				if($loja[0]['paypal_app_sandbox_token']){
					if((int)$loja[0]['paypal_app_sandbox_token_time']+(int)$loja[0]['paypal_app_sandbox_expires_in'] < time()){
						$gerar_token = true;
					}
				} else {
					$gerar_token = true;
				}
				
				if($gerar_token){
					$retorno = eservice_paypal_plus_token_generate(Array(
						'paypal_app_code' => $loja[0]['paypal_app_sandbox_code'],
						'paypal_app_secret' => $loja[0]['paypal_app_sandbox_secret'],
						'paypal_app_live' => false,
						'id_loja' => $id_loja,
					));
					
					if(!$retorno['erro']){
						$loja[0]['paypal_app_sandbox_token'] = $retorno['token'];
					}
				}
				
				$token = $loja[0]['paypal_app_sandbox_token'];
				$webhook_id = $loja[0]['paypal_app_sandbox_webhook_id'];
			}
			
			if($verificar_webhook){
				$headers = apache_request_headers();
				$body = file_get_contents('php://input');
				
				$obj['transmission_id'] = $headers['Paypal-Transmission-Id'];
				$obj['transmission_time'] = $headers['Paypal-Transmission-Time'];
				$obj['cert_url'] = $headers['Paypal-Cert-Url'];
				$obj['auth_algo'] = $headers['Paypal-Auth-Algo'];
				$obj['transmission_sig'] = $headers['Paypal-Transmission-Sig'];
				$obj['webhook_id'] = $webhook_id;
				$obj['webhook_event'] = json_decode($body);
				
				$json_send = json_encode($obj);
				
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, "https://api".($ambiente == 'live' ? "" : ".sandbox").".paypal.com/v1/notifications/verify-webhook-signature");
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Authorization: Bearer '.$token,
				));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_send);

				$result = curl_exec($ch);

				if(empty($result)){
					gravar_log('eservice_ppplus_webhooks: empty result - id_usuario: '.$usuario[0]['id_usuario']);
				} else {
					$json = json_decode($result);
					
					if($json->verification_status == 'SUCCESS'){
						$webhook_verified = true;
					} else {
						gravar_log('eservice_ppplus_webhooks: verification_status FAILURE - id_usuario: '.$usuario[0]['id_usuario']);
					}
				}
				
				curl_close($ch);
			}
			
			if($webhook_verified){
				if($verificar_webhook){
					$webhook_event = $obj['webhook_event'];
					
					$event_type = $webhook_event->event_type;
					$invoice_number = $webhook_event->resource->invoice_number;
					$parent_payment = $webhook_event->resource->parent_payment;
					$state = $webhook_event->resource->state;
				} else {
					$event_type = 'PAYMENT.SALE.COMPLETED';
					$invoice_number = 'E1131';
					$parent_payment = 'PAY-6XN0180711637743TLPXSKPY';
					$state = 'completed';
				}
				
				switch($event_type){
					case 'RISK.DISPUTE.CREATED':
					case 'CUSTOMER.DISPUTE.CREATED':
						$invoice_number = $webhook_event->resource->disputed_transactions[0]->invoice_number;
						$dispute_id = $webhook_event->id;
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_pedidos',
							))
							,
							"pedidos",
							"WHERE codigo='".$invoice_number."'"
						);
						
						$id_pedidos = $resultado[0]['id_pedidos'];
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'paypal_plus_final_id',
							))
							,
							"pedidos_pagamentos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						$paypal_plus_final_id = $resultado[0]['paypal_plus_final_id'];
					break;
					default:
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_pedidos',
								'paypal_plus_final_id',
							))
							,
							"pedidos_pagamentos",
							"WHERE identificador_externo='".$parent_payment."'"
						);
						
						$id_pedidos = $resultado[0]['id_pedidos'];
						$paypal_plus_final_id = $resultado[0]['paypal_plus_final_id'];
				}
				
				if($id_pedidos){
					switch($event_type){
						case 'RISK.DISPUTE.CREATED':
						case 'CUSTOMER.DISPUTE.CREATED':
						
						break;
						default:
							$pedidos_pagamentos = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_pedidos',
								))
								,
								"pedidos_pagamentos",
								"WHERE id_pedidos='".$id_pedidos."'"
								." AND identificador_externo!='".$parent_payment."'"
								." AND status='completed'"
							);
							
							if($pedidos_pagamentos){
								$campo_tabela = "pedidos_pagamentos";
								$campo_tabela_extra = "WHERE identificador_externo='".$parent_payment."'";
								
								$campo_nome = "status"; $campo_valor = $state; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
								
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
								
								exit;
							}
					}
					
					$reference = $invoice_number;
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							't3.nome',
							't3.email',
							't1.id_loja',
							't1.id_pedidos',
							't1.valor_total',
							't1.status',
						))
						,
						"pedidos as t1,loja_usuarios_pedidos as t2,loja_usuarios as t3",
						"WHERE t1.codigo='".$reference."'"
						." AND t1.id_pedidos=t2.id_pedidos"
						." AND t2.id_loja_usuarios=t3.id_loja_usuarios"
					);
					
					$nome = $resultado[0]['t3.nome'];
					$email = $resultado[0]['t3.email'];
					$id_loja = $resultado[0]['t1.id_loja'];
					$id_pedidos = $resultado[0]['t1.id_pedidos'];
					$valor_total = $resultado[0]['t1.valor_total'];
					$status_atual = $resultado[0]['t1.status'];
					
					$loja = banco_select_name
					(
						banco_campos_virgulas(Array(
							'nome',
							'email_assinatura',
							'email_assunto',
							'id',
							'loja_url_cliente',
							'id_usuario',
						))
						,
						"loja",
						"WHERE id_loja='".$id_loja."'"
					);
					
					$id_usuario = $loja[0]['id_usuario'];
					$loja_id = $loja[0]['id'];
					$loja_url_cliente = $loja[0]['loja_url_cliente'];
					$loja_nome = $loja[0]['nome'];
					$loja_email_assinatura = $loja[0]['email_assinatura'];
					$loja_email_assunto = $loja[0]['email_assunto'];
					
					$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
					
					if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
					
					$parametros['from_name'] = ($loja_nome?$loja_nome:$_HTML['TITULO']);
					$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
					
					$parametros['email_name'] = $nome;
					$parametros['email'] = $email;
					
					$parametros['subject'] = $loja_email_assunto;
					
					$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#codigo#",$reference);
					
					$loja_email_assinatura = modelo_var_troca_tudo($loja_email_assinatura,"#loja-nome#",($loja_nome?$loja_nome:$_HTML['TITULO']));
					
					switch($event_type){
						case 'PAYMENT.SALE.PENDING':
							banco_update
							(
								"status='P'",
								"pedidos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							banco_update
							(
								"status='P'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							$status_valor = 2;
							$titulo = "Em análise";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",($loja_nome?$loja_nome:$_HTML['TITULO']));
						break;
						case 'PAYMENT.SALE.COMPLETED':
							if($loja_url_cliente){
								$host = banco_select_name
								(
									banco_campos_virgulas(Array(
										'url',
									))
									,
									"host",
									"WHERE id_usuario='".$id_usuario."'"
								);
								
								$url_cliente = $host[0]['url'];
								
								$url = html(Array(
									'tag' => 'a',
									'val' => $url_cliente.'purchases',
									'attr' => Array(
										'href' => $url_cliente.'purchases',
									)
								));
							} else {
								$url = html(Array(
									'tag' => 'a',
									'val' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'e-services/'.$loja_id.'/purchases',
									'attr' => Array(
										'href' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'e-services/'.$loja_id.'/purchases',
									)
								));
							}
							
							banco_update
							(
								"paypal_code='".$parent_payment."',".
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
							
							$status_valor = 3;
							$titulo = "Pago";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",($loja_nome?$loja_nome:$_HTML['TITULO']));
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#url#",$url);
							
							eservice_paypal_reference_comissao(Array(
								'valor_total' => $valor_total,
								'id_pedidos' => $id_pedidos,
							));
							
							//$enviar_voucher = true;
						break;
						case 'RISK.DISPUTE.CREATED':
						case 'CUSTOMER.DISPUTE.CREATED':
							banco_update
							(
								"status='5'",
								"pedidos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							banco_update
							(
								"status='5'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							$status_valor = 5;
							$titulo = "Em disputa";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",($loja_nome?$loja_nome:$_HTML['TITULO']));
						break;
						case 'PAYMENT.SALE.REFUNDED':
							banco_update
							(
								"paypal_code=NULL,".
								"status='6'",
								"pedidos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							banco_update
							(
								"status='6'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							$status_valor = 6;
							$titulo = "Dinheiro Devolvido";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",($loja_nome?$loja_nome:$_HTML['TITULO']));
						break;
						case 'PAYMENT.SALE.DENIED':
							banco_update
							(
								"paypal_code=NULL,".
								"status='7'",
								"pedidos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							banco_update
							(
								"status='7'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
							
							$status_valor = 7;
							$titulo = "Cancelado";
							$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
							$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
							$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",($loja_nome?$loja_nome:$_HTML['TITULO']));
						break;
						default: 
							$titulo = $event_type;
					}
					
					log_banco(Array(
						'id_referencia' => $id_pedidos,
						'grupo' => 'pedidos',
						'valor' => '<b>PayPal Plus - Webhook:</b> alterou o status para: <b>'.$titulo.'</b> | <b>ID FINAL: '.$paypal_plus_final_id.'</b> | <b>EVENTO: '.$event_type.'</b>'.($parent_payment ? ' | <b>ID PROVISÓRIO: '.$parent_payment.'</b>' : '').($dispute_id ? ' | <b>ID DISPUTA: '.$dispute_id.'</b>' : ''),
					));
					
					$parametros['mensagem'] .= eservice_paypal_plus_lista_servicos($id_pedidos);
					$parametros['mensagem'] .= $loja_email_assinatura;
					
					if($parametros['enviar_mail'])enviar_mail($parametros);
				}
			}
		}
	}
	
	exit;
}

function eservice_paypal_reference_comissao($params = false){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PAYPAL;
	global $_PAYPAL_SANDBOX;
	global $_PROJETO;
	global $_B2MAKE_URL;
	global $_ESERVICE;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"pedidos_comissao",
		"WHERE id_pedidos='".$id_pedidos."'"
	);
	
	if($resultado)return;
	
	$pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'codigo',
		))
		,
		"pedidos",
		"WHERE id_pedidos='".$id_pedidos."'"
	);
	
	$valor_comissao = ((float)$valor_total*($_PROJETO['PAYPAL_COMISSAO_TAXA']/100));
	
	$campo_tabela = "loja";
	$campo_tabela_extra = "WHERE id_loja='".$pedidos[0]['id_loja']."'"
	." AND status='A'";
	
	$id_loja = $pedidos[0]['id_loja'];
	$codigo = $pedidos[0]['codigo'];
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'paypal_reference_id',
			'paypal_app_live',
		))
		,
		$campo_tabela,
		$campo_tabela_extra
	);
	
	$paypal_reference_id = $loja[0]['paypal_reference_id'];
	$paypal_app_live = ($loja[0]['paypal_app_live'] ? true : false);
	
	if(!$paypal_reference_id)return;
	
	$retorno = b2make_paypal_plus_token_generate(Array(
		'paypal_app_live' => $paypal_app_live,
	));
	
	if($retorno['erro']){
		log_banco(Array(
			'id_referencia' => $id_pedidos,
			'grupo' => 'paypal-comissoes',
			'valor' => '<b>PayPal Comissões:</b> erro na geração do token: '.$retorno['erro_msg'],
		));
		return;
	} else {
		$token = $retorno['token'];
	}
	
	$obj['intent'] = 'sale';
	$obj['payer'] = Array(
		'payment_method' => 'paypal',
		'funding_instruments' => Array(
			Array(
				'billing' => Array(
					'billing_agreement_id' => $paypal_reference_id,
				)
			)
		),
	);
	$obj['application_context'] = Array(
		'brand_name' => 'B2make',
		'shipping_preference' => 'NO_SHIPPING',
	);
	$obj['transactions'] = Array(
		Array(
			'amount' => Array(
				'currency' => 'BRL',
				'total' => number_format($valor_comissao, 2, '.', ''),
			),
			'description' => 'Cobrança de taxa sobre uso do B2make do pedido '.$codigo.'.',
			'custom' => $codigo,
			'payment_options' => Array(
				'allowed_payment_method' => 'IMMEDIATE_PAY',
			),
			'item_list' => Array(
				'items' => Array(
					Array(
						'name' => 'Cobrança de taxa da B2make.',
						'description' => 'Cobrança de taxa sobre uso do B2make do pedido '.$codigo.'.',
						'quantity' => '1',
						'price' => number_format($valor_comissao, 2, '.', ''),
						'tax' => '0',
						'sku' => $codigo,
						'currency' => 'BRL',
					)
				),
			),
		),
	);
	$obj['redirect_urls'] = Array(
		'cancel_url' => $_B2MAKE_URL.'e-services/paypal-reference-cancel',
		'return_url' => $_B2MAKE_URL.'e-services/paypal-reference-return',
	);
	
	$json_send = json_encode($obj);
	
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, "https://api.".($paypal_app_live ? "" : "sandbox.")."paypal.com/v1/payments/payment");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer '.$token,
		'paypal-partner-attribution-id: B2Make_Ecom_EcReference',
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_send);

	$result = curl_exec($ch);
	
	
	if(empty($result)){
		log_banco(Array(
			'id_referencia' => $id_pedidos,
			'grupo' => 'paypal-comissoes',
			'valor' => '<b>PayPal Comissões:</b> retorno do PayPal vazio.',
		));
	} else {
		$json = json_decode($result);
		
		if($json->name){
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'paypal-comissoes',
				'valor' => '<b>PayPal Comissões:</b> retorno do PayPal com erro: '. $json->name .' - ' . $json->message .' >>> '.$result."\n".' SENT >>> '.$json_send,
			));
		} else {
			$paypal_code = $json->id;
			
			$campos = null;
			
			$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "paypal_code"; $campo_valor = $paypal_code; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "valor"; $campo_valor = number_format($valor_comissao, 2, '.', ''); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"pedidos_comissao"
			);
		}
	}

	curl_close($ch);
}

function b2make_paypal_plus_token_generate($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_PROJETO;
	
	$ch = curl_init();
	
	if($paypal_app_live){
		$clientId = $_PROJETO['PAYPAL_B2MAKE_LIVE_ID'];
		$secret = $_PROJETO['PAYPAL_B2MAKE_LIVE_SECRET'];
	} else {
		$clientId = $_PROJETO['PAYPAL_B2MAKE_SANDBOX_ID'];
		$secret = $_PROJETO['PAYPAL_B2MAKE_SANDBOX_SECRET'];
	}
	
	$variavel_global = banco_select_name
	(
		banco_campos_virgulas(Array(
			'variavel',
			'valor',
		))
		,
		"variavel_global",
		"WHERE grupo='paypal'"
	);
	
	if($variavel_global)
	foreach($variavel_global as $vg){
		$paypal_b2make[$vg['variavel']] = $vg['valor'];
	}
	
	$gerar_token = false;
	
	if($paypal_app_live){
		if($paypal_b2make['b2make-live-token']){
			if((int)$paypal_b2make['b2make-live-token-time']+(int)$paypal_b2make['b2make-live-expires-in'] < time()){
				$gerar_token = true;
			}
		} else {
			$gerar_token = true;
		}
		
		if(!$gerar_token){
			$saida = Array(
				'token' => $paypal_b2make['b2make-live-token'],
				'status' => 'Ok',
			);
			
			return $saida;
		}
	} else {
		if($paypal_b2make['b2make-sandbox-token']){
			if((int)$paypal_b2make['b2make-sandbox-token-time']+(int)$paypal_b2make['b2make-sandbox-expires-in'] < time()){
				$gerar_token = true;
			}
		} else {
			$gerar_token = true;
		}
		
		if(!$gerar_token){
			$saida = Array(
				'token' => $paypal_b2make['b2make-sandbox-token'],
				'status' => 'Ok',
			);
			
			return $saida;
		}
	}
	
	
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
			
			$editar['b2make-'.($paypal_app_live ? 'live' : 'sandbox').'-token'] = $access_token;
			$editar['b2make-'.($paypal_app_live ? 'live' : 'sandbox').'-token-time'] = time();
			$editar['b2make-'.($paypal_app_live ? 'live' : 'sandbox').'-expires-in'] = $expires_in;
			
			if($editar)
			foreach($editar as $variavel => $valor){
				banco_update
				(
					"valor='".$valor."'",
					"variavel_global",
					"WHERE grupo='paypal'"
					." AND variavel='".$variavel."'"
				);
			}
			
			$saida = Array(
				'token' => $access_token,
				'status' => 'Ok',
			);
		}
	}
	
	curl_close($ch);
	
	return $saida;
}

function eservice_paypal_reference_comissao_pontual($params = false){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PAYPAL;
	global $_PAYPAL_SANDBOX;
	global $_PROJETO;
	global $_B2MAKE_URL;
	global $_ESERVICE;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$valor_comissao || !$id_loja || !$referente_a || !$codigo)return Array(
		'status' => 'Error',
		'msg' => 'Obrigatório definir os seguintes campos: valor_comissao, id_loja, referente_a e codigo',
	);;
	
	$valor_comissao = (float)$valor_comissao;
	
	$paypal_app_live = true;
	
	$campo_tabela = "loja";
	$campo_tabela_extra = "WHERE id_loja='".$id_loja."'"
	." AND status='A'";
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'paypal_reference_id',
		))
		,
		$campo_tabela,
		$campo_tabela_extra
	);
	
	$paypal_reference_id = $loja[0]['paypal_reference_id'];
	
	if(!$paypal_reference_id)return;
	
	$retorno = b2make_paypal_plus_token_generate(Array(
		'paypal_app_live' => $paypal_app_live,
	));
	
	if($retorno['erro']){
		log_banco(Array(
			'id_referencia' => $id_referencia,
			'grupo' => 'paypal-comissao-pontual',
			'valor' => '<b>PayPal Comissões:</b> erro na geração do token: '.$retorno['erro_msg'],
		));
		return;
	} else {
		$token = $retorno['token'];
	}
	
	$obj['intent'] = 'sale';
	$obj['payer'] = Array(
		'payment_method' => 'paypal',
		'funding_instruments' => Array(
			Array(
				'billing' => Array(
					'billing_agreement_id' => $paypal_reference_id,
				)
			)
		),
	);
	$obj['application_context'] = Array(
		'brand_name' => 'B2make',
		'shipping_preference' => 'NO_SHIPPING',
	);
	$obj['transactions'] = Array(
		Array(
			'amount' => Array(
				'currency' => 'BRL',
				'total' => number_format($valor_comissao, 2, '.', ''),
			),
			'description' => 'Cobrança de taxa sobre uso do B2make referente: '.$referente_a.'.',
			'custom' => $codigo,
			'payment_options' => Array(
				'allowed_payment_method' => 'IMMEDIATE_PAY',
			),
			'item_list' => Array(
				'items' => Array(
					Array(
						'name' => 'Cobrança de taxa da B2make.',
						'description' => 'Cobrança de taxa sobre uso do B2make referente: '.$referente_a.'.',
						'quantity' => '1',
						'price' => number_format($valor_comissao, 2, '.', ''),
						'tax' => '0',
						'sku' => $codigo,
						'currency' => 'BRL',
					)
				),
			),
		),
	);
	$obj['redirect_urls'] = Array(
		'cancel_url' => $_B2MAKE_URL.'e-services/paypal-reference-cancel',
		'return_url' => $_B2MAKE_URL.'e-services/paypal-reference-return',
	);
	
	$json_send = json_encode($obj);
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, "https://api.".($paypal_app_live ? "" : "sandbox.")."paypal.com/v1/payments/payment");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer '.$token,
		'paypal-partner-attribution-id: B2Make_Ecom_EcReference',
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_send);

	$result = curl_exec($ch);
	
	if(empty($result)){
		log_banco(Array(
			'id_referencia' => $id_referencia,
			'grupo' => 'paypal-comissoes-pontual',
			'valor' => '<b>PayPal Comissões:</b> retorno do PayPal vazio.',
		));
	} else {
		$json = json_decode($result);
		
		if($json->name){
			log_banco(Array(
				'id_referencia' => $id_referencia,
				'grupo' => 'paypal-comissoes-pontual',
				'valor' => '<b>PayPal Comissões:</b> retorno do PayPal com erro: '. $json->name .' - ' . $json->message .' >>> '.$result."\n".' SENT >>> '.$json_send,
			));
		} else {
			$paypal_code = $json->id;
			
			return Array(
				'status' => 'OK',
				'paypal-code' => $paypal_code,
			);
		}
	}

	curl_close($ch);
}

// E-Service

function eservice_permissao($local = 'e-services'){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ESERVICE;
	global $_ALERTA;
	
	if($_REQUEST[ajax])				$ajax = $_REQUEST[ajax];
	
	$permitido = false;
	
	if($_SESSION[$_SYSTEM['ID']."permissao"]){
		switch($local){
			case 'e-services/'.$_ESERVICE['loja-atual'].'/voucher-view': $permitido = true; break;
			case 'e-services/'.$_ESERVICE['loja-atual'].'/voucher-print': $permitido = true; break;
		}
	}
	
	if(!$permitido){
		if(!$_SESSION[$_SYSTEM['ID']."loja-permissao"]){
			$_SESSION[$_SYSTEM['ID'].'loja-logar-local'] = $local;
			if($ajax){
				$saida = Array(
					'access_denied' => true,
					'redirect_local' => 'e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself',
				);
				$saida = json_encode($saida);
				echo $saida;
				exit(0);
			} else {
				redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself'); 
			}
		} else {
			$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
			if($id_loja != $loja_usuarios['id_loja']){
				redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/logout'); 
			}
			
			if($local != 'e-services/'.$_ESERVICE['loja-atual'].'/account'){
				if(
					!$loja_usuarios['nome'] ||
					!$loja_usuarios['ultimo_nome'] ||
					!$loja_usuarios['telefone'] ||
					($loja_usuarios['cnpj_selecionado'] ? !$loja_usuarios['cnpj'] : !$loja_usuarios['cpf'] )
				){
					$_SESSION[$_SYSTEM['ID'].'after-update-account-local'] = $local;
					
					$_ALERTA = '&Eacute; necess&aacute;rio preencher todos os dados da sua conta de usu&aacute;rio antes de continuar.';
					redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/account');
				}
			}
		}
	}
}

function eservice_generate_new_password(){
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_DESATIVAR_PADRAO;
	global $_VARS;
	global $_SYSTEM;
	global $_HTML_DADOS;
	global $_HTML;
	global $_CAMINHO;
	global $_ALERTA;
	global $_ESERVICE;
	
	$alerta_invalido = '<p>Código de validação <b style="color:red;">inválido</b>!</p><p>O código de validação especificado é inválido ou já foi usado! Favor entrar em contato pelo email para saber como proceder reportando o ocorrido: <a href="mailto:'.$_SYSTEM['CONTATO_EMAIL'].'">'.$_SYSTEM['CONTATO_NOME'].'</a></p>';
	
	$caminho = explode('/',$_REQUEST[caminho]);
	
	if($caminho[count($caminho)-1] == NULL){
		array_pop($caminho);
	}
	
	if($_ESERVICE['iframe']){
		$cod = $_REQUEST['cod'];
		$key = $_REQUEST['key'];
	} else {
		$cod = $caminho[3];
		$key = $caminho[4];
	}
	
	if($cod && $key){
		$cod_original = $cod;
		$cod = substr($cod,8);
		$cod = zero_a_esquerda_retirar($cod);
		
		global $_CONEXAO_BANCO;
	
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$loja_usuarios = banco_select_name
		(
			banco_campos_virgulas(Array(
				'cadastro_key',
				'email',
			))
			,
			"loja_usuarios",
			"WHERE id_loja_usuarios='".$cod."'"
		);
		
		if($key == $loja_usuarios[0]['cadastro_key']){
			$key = crypt(sha1(rand().$cod));
			$key = preg_replace('/[\$\.\/]/i', '', $key);
			
			$email = $loja_usuarios[0]['email'];
	
			banco_update
			(
				"cadastro_key='".$key."'",
				"loja_usuarios",
				"WHERE id_loja_usuarios='".$cod."'"
			);
			
			$gerar_nova_senha_path = 'includes'.$_SYSTEM['SEPARADOR'].'index.html';
			
			if($_PROJETO['index']){
				if($_PROJETO['index']['gerar_nova_senha_path']){
					$gerar_nova_senha_path = $_PROJETO['index']['gerar_nova_senha_path'];
				}
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Gerar nova Senha.';
			
			$_HTML_DADOS['description'] = 'Página para geração de nova senha das contas de usuários do sistema.';
			$_HTML_DADOS['keywords'] = 'redefinir senha,redefinir,senha,redefinir,redefinição,redefinicao';
			
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
			$modulo = modelo_tag_val($modelo,'<!-- redefinir_senha < -->','<!-- redefinir_senha > -->');
			
			$modulo = modelo_var_troca($modulo,"#usuario#",$email);
			$modulo = modelo_var_troca($modulo,"#key#",$key);
			$modulo = modelo_var_troca($modulo,"#cod#",$cod_original);
			$modulo = modelo_var_troca($modulo,"#loja-atual#",$_ESERVICE['loja-atual']);
			
			if($_REQUEST['_iframe_session']){
				$modulo = modelo_var_troca($modulo,'<!-- iframe -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
			}
			
			$_MODULO_EXTERNO['#e-services-generate-new-password#'] = $modulo;
			
			$_CONTEUDO_ID_AUX = 'e-services-generate-new-password';
			
			$saida = opcao_nao_econtrada();
			
			return $saida;
		} else {
			alerta($alerta_invalido);
			seguranca_delay(Array('local' => 'forgot-your-password'));
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/forgot-your-password');
		}
	} else {
		alerta($alerta_invalido);
		seguranca_delay(Array('local' => 'forgot-your-password'));
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/forgot-your-password');
	}
}

function eservice_password_reset_request(){
	global $_DESATIVAR_PADRAO;
	global $_VARS;
	global $_SYSTEM;
	global $_HTML_DADOS;
	global $_HTML;
	global $_ESERVICE;
	
	if(!$_DESATIVAR_PADRAO['redefinir_senha_banco']){
		$cod = $_REQUEST['cod'];
		$key = $_REQUEST['key'];
		$alerta_invalido = '<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código de validação especificado é inválido ou já foi usado! Favor entrar em contato pelo email para saber como proceder reportando o ocorrido: <a href="mailto:'.$_SYSTEM['CONTATO_EMAIL'].'">'.$_SYSTEM['CONTATO_NOME'].'</a></p>';
		
		if($cod && $key){
			$cod = substr($cod,8);
			$cod = zero_a_esquerda_retirar($cod);
			
			global $_CONEXAO_BANCO;
		
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$loja_usuarios = banco_select_name
			(
				'*'
				,
				"loja_usuarios",
				"WHERE id_loja_usuarios='".$cod."'"
			);
			
			if($key == $loja_usuarios[0]['cadastro_key']){
				$url_loja_atual = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
				session_unset();
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"] = $url_loja_atual;
				
				$loja_usuario = $loja_usuarios[0];
				
				if($loja_usuario['versao_voucher']){
					$versao_voucher = (int)$loja_usuario['versao_voucher'] + 1;
				} else {
					$versao_voucher = 1;
				}
				
				banco_update
				(
					"versao_voucher='".$versao_voucher."',".
					"senha='".crypt($_REQUEST['senha'])."',"
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
						"senha='".crypt($_REQUEST['senha'])."',"
						."cadastro_key=NULL",
						"usuario",
						"WHERE id_loja_usuarios='".$cod."'"
					);
				}
				
				alerta('<p>Senha redefinida com sucesso!</p>');
				redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself');
			} else {
				alerta($alerta_invalido);
				seguranca_delay(Array('local' => 'forgot-your-password'));
				redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/forgot-your-password');
			}
		} else {
			alerta($alerta_invalido);
			seguranca_delay(Array('local' => 'forgot-your-password'));
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/forgot-your-password');
		}
	}
}

function eservice_forgot_your_password(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_HTML_DADOS;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_ESERVICE;
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$modulo = modelo_tag_val($modelo,'<!-- esqueceu_senha < -->','<!-- esqueceu_senha > -->');
	
	$modulo = modelo_var_troca($modulo,"#loja-atual#",$_ESERVICE['loja-atual']);
	
	if($_REQUEST['_iframe_session']){
		$modulo = modelo_var_troca($modulo,'<!-- iframe -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
	}
	
	$_HTML_DADOS['description'] = 'Página para recuperação de senha das contas de usuários do sistema.';
	$_HTML_DADOS['keywords'] = 'esqueceu senha,esqueceu,senha,recuperação,recuperacao,recuperação senha';
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	$_MODULO_EXTERNO['#e-services-forgot-your-password#'] = $modulo;
	
	$_CONTEUDO_ID_AUX = 'e-services-forgot-your-password';
	
	$saida = opcao_nao_econtrada();
	
	return $saida;
}

function eservice_forgot_your_password_request(){
	global $_SYSTEM;
	global $_HTML;
	global $_ALERTA;
	global $_VARS;
	global $_CONTEUDO_ID_AUX;
	global $_REMOTE_ADDR;
	global $_ESERVICE;
	
	if(recaptcha_verify()){
		banco_conectar();
		
		if($_REQUEST['esqueceu_senha-email']){
			$email = $_REQUEST['esqueceu_senha-email'];
			$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
			
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
				
				$key = crypt(sha1(rand().$_REQUEST["email"]));
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
				
				if($_ESERVICE['iframe']){
					$url = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
					
					$url = html(Array(
						'tag' => 'a',
						'val' => $url.'generate-new-password/?opcao=newpass&cod='.$codigo.'&key='.$key,
						'attr' => Array(
							'href' => $url.'generate-new-password/?opcao=newpass&cod='.$codigo.'&key='.$key,
						)
					));
				} else {
					$url = html(Array(
						'tag' => 'a',
						'val' => 'https://'.$_SYSTEM['DOMINIO'].$_SYSTEM['ROOT'].'/e-services/'.$_ESERVICE['loja-atual'].'/generate-new-password/'.$codigo.'/'.$key,
						'attr' => Array(
							'href' => 'https://'.$_SYSTEM['DOMINIO'].$_SYSTEM['ROOT'].'/e-services/'.$_ESERVICE['loja-atual'].'/generate-new-password/'.$codigo.'/'.$key,
						)
					));
				}
				
				$parametros['from_name'] = $_HTML['TITULO'];
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
				
				$loja = banco_select_name
				(
					banco_campos_virgulas(Array(
						'email_assinatura',
					))
					,
					"loja",
					"WHERE id='".$_ESERVICE['loja-atual']."'"
				);
				
				$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
				$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#nome#",$nome);
				$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#url#",$url);
				$parametros['mensagem'] .= $loja[0]['email_assinatura'];
				
				$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#loja-nome#",$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]["nome"]);
				
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
				
				alerta('<p>Foi enviada uma mensagem para o email fornecido. Entre no seu programa de email e siga os passos definidos na mensagem enviada.</p>');
				redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself');
			} else {
				$_ALERTA = '<p>Email informado <b style="color:red;">inexistente</b>!<p></p>Preencha o email corretamente e envie novamente!</p>';
				alerta($_ALERTA);
				seguranca_delay(Array('local' => 'forgot-your-password'));
				redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/forgot-your-password');
			}
		} else {
			$_ALERTA = '<p>Email informado <b style="color:red;">inexistente</b>!<p></p>Preencha o email corretamente e envie novamente!</p>';
			alerta($_ALERTA);
			seguranca_delay(Array('local' => 'forgot-your-password'));
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/forgot-your-password');
		}
	} else {
		$_ALERTA = '<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código recaptcha especificado é inválido!</p>';
		alerta($_ALERTA);
		seguranca_delay(Array('local' => 'forgot-your-password'));
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/forgot-your-password');
	}
}

function eservice_cancel_purchase(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	
	$_SESSION[$_SYSTEM['ID']."eservice_cadastrar_pedido"] = false;
	$_SESSION[$_SYSTEM['ID']."eservice_checkout"] = false;
	
	$_MODULO_EXTERNO['#e-services-signin#'] = $modulo;
	
	$_CONTEUDO_ID_AUX = 'e-services-cancel-purchase';
	
	$saida = opcao_nao_econtrada();
	
	return $saida;
}

function eservice_site_pagina_pais($id_site,$nao_inserir_dir_atual){
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
	
	return array_reverse($directories);
}

function eservice_site_pagina_diretorio($id_site,$nao_inserir_dir_atual = false,$nao_ftp = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	
	$dirs = eservice_site_pagina_pais($id_site,$nao_inserir_dir_atual);
	
	if($dirs)
	foreach($dirs as $dir){
		if(!$nao_ftp){
			if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-site-path'].$dirs_antes . $dir )) {
				ftp_mkdir($_CONEXAO_FTP, $dir); // create directories that do not yet exist
			}
			
			ftp_chdir($_CONEXAO_FTP,$dir);
		}
		
		$dirs_antes .= $dir . '/';
	}
	
	return ($dirs_antes ? $dirs_antes : '');
}

function eservice_payment_return(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_ESERVICE;
	global $_SYSTEM;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/payment');
	
	if(!$_SESSION[$_SYSTEM['ID']."payment-vars"]) return;
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"loja_usuarios_pedidos",
		"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
		." AND pedido_atual IS TRUE"
	);
	
	$id_pedidos = $resultado[0]['id_pedidos'];

	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'status',
		))
		,
		"pedidos",
		"WHERE id_pedidos='".$id_pedidos."'"
	);
	
	if($resultado[0]['status'] == 'A'){
		if($_PROJETO['ecommerce']){
			if($_PROJETO['ecommerce']['retorno_pagamento_sucesso_layout']){
				$layout = $_PROJETO['ecommerce']['retorno_pagamento_sucesso_layout'];
			}
		}
		
		$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
		$_HTML_DADOS['titulo'] = $titulo . 'Pagamento Efetuado com Sucesso.';
		
		$_HTML_DADOS['description'] = 'Página para de informação do retorno de pagamento de um pedido no pagseguro.';
		$_HTML_DADOS['keywords'] = 'pagamento retorno,pagamento serviços retorno,pagamento produtos retorno, retorno pagseguro';
		
		if(!$layout){
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_sucesso < -->','<!-- retorno_pagamento_sucesso > -->');
			
			$layout = $pagina;
		}
		
		$url = 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'e-services/'.$_ESERVICE['loja-atual'].'/voucher';
		
		$layout = modelo_var_troca($layout,"#url#",$url);
	} else {
		if($_SESSION[$_SYSTEM['ID']."payment-pagseguro-returned"]){
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['retorno_pagamento_processando_layout']){
					$layout = $_PROJETO['ecommerce']['retorno_pagamento_aguarde_layout'];
				}
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Pagamento Sendo Processado.';
			
			$_HTML_DADOS['description'] = 'Página para de informação do retorno de pagamento de um pedido no pagseguro.';
			$_HTML_DADOS['keywords'] = 'pagamento retorno,pagamento serviços retorno,pagamento produtos retorno, retorno pagseguro';
			
			if(!$layout){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_processando < -->','<!-- retorno_pagamento_processando > -->');
				
				$layout = $pagina;
			}
		} else {
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['retorno_pagamento_aguarde_layout']){
					$layout = $_PROJETO['ecommerce']['retorno_pagamento_aguarde_layout'];
				}
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Pagamento Aguardando Aprovação.';
			
			$_HTML_DADOS['description'] = 'Página para de informação do retorno de pagamento de um pedido no pagseguro.';
			$_HTML_DADOS['keywords'] = 'pagamento retorno,pagamento serviços retorno,pagamento produtos retorno, retorno pagseguro';
			
			if(!$layout){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_aguarde < -->','<!-- retorno_pagamento_aguarde > -->');
				
				$layout = $pagina;
			}
		}
		
		$_SESSION[$_SYSTEM['ID']."payment-pagseguro-returned"] = false;
	}
	
	return $layout;
}

function eservice_voucher_form_gift(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_ESERVICE;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/voucher');
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
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
		
		$pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'voucher_por_servico',
				'de',
				'para',
				'mensagem',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND voucher_por_servico IS NOT NULL"
		);
		
		if($pedidos){
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos_servicos',
					'codigo',
				))
				,
				"pedidos_servicos",
				"WHERE id_pedidos='".$id_pedidos."'"
				." AND voucher_por_servico IS NOT NULL"
			);
			
			$campo_tabela = 'pedidos_servicos';
			
			$campo_nome = "de"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "para"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "mensagem"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"pedidos_servicos",
					"WHERE id_pedidos_servicos='".$pedidos_servicos[0]['id_pedidos_servicos']."'"
				);
			}
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário:</b> mudou mensagem do PEDIDO SERVIÇO:'.$pedidos_servicos[0]['codigo'].' - <b>De:</b> '.strip_tags($_REQUEST['de']).' <b>Para:</b> '.strip_tags($_REQUEST['para']).' <b>Mensagem:</b> '.strip_tags($_REQUEST['mensagem']),
			));
			
			if(
				!$pedidos[0]['de'] ||
				!$pedidos[0]['para'] ||
				!$pedidos[0]['mensagem']
			){
				$editar = false;$editar_sql = false;
				$campo_tabela = 'pedidos';
				
				$campo_nome = "de"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
				$campo_nome = "para"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
				$campo_nome = "mensagem"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
				
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
		} else {
			$campo_tabela = 'pedidos';
			
			$campo_nome = "de"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "para"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "mensagem"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
			}
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário:</b> mudou mensagem <b>De:</b> '.strip_tags($_REQUEST['de']).' <b>Para:</b> '.strip_tags($_REQUEST['para']).' <b>Mensagem:</b> '.strip_tags($_REQUEST['mensagem']),
			));
		}
	}
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/voucher');
}

function eservice_howitworks(){
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	
	$_CONTEUDO_ID_AUX = 'e-services-how-it-works';
	
	$saida = opcao_nao_econtrada();
	
	return $saida;
}

function eservice_remover_css($voucher){
	$valores = Array(
		'display: inline;',
		'display: table-row;',
		'border: 0px none rgb(0, 0, 0);',
		'outline: rgb(0, 0, 0) none 0px;',
		'background-color: rgba(0, 0, 0, 0);',
		'fill: rgb(0, 0, 0);',
		'cursor: default;',
		'background-repeat: repeat repeat;',
		'background-position: 0% 0%;',
		'zoom: 1;',
		'widows: auto;',
		'visibility: visible;',
		'-webkit-transition: all 0s ease 0s;',
		'transition: all 0s ease 0s;',
		'top: auto;',
		'stroke: none;',
		'speak: normal;',
		'right: auto;',
		'resize: none;',
		'position: static;',
		'overflow: visible;',
		'outline: rgb(0, 0, 0) none 0px;',
		'orphans: auto;',
		'order: 0;',
		'opacity: 1;',
		'mask: none;',
		'left: auto;',
		'kerning: 0px;',
		'font-weight: normal;',
		'font-variant: normal;',
		'font-style: normal;',
		'float: none;',
		'flex: 0 1 auto;',
		'filter: none;',
		'fill: rgb(0, 0, 0);',
		'display: table-cell;',
		'direction: ltr;',
		'cursor: auto;',
		'clip: auto;',
		'clear: none;',
		'bottom: auto;',
		'background-size: auto;',
		'background-image: none;',
		'background-clip: border-box;',
		'background-origin: padding-box;',
		'background-attachment: scroll;',
	);
	
	if($valores)
	foreach($valores as $valor){
		$voucher = preg_replace('| '.preg_quote($valor).'|i', '', $voucher);
		$voucher = preg_replace('|'.preg_quote($valor).'|i', '', $voucher);
	}
	
	$voucher = preg_replace('/ class=(")?[^"]+(?(1)")/i', '', $voucher);
	$voucher = preg_replace('/ id=(")?[^"]+(?(1)")/i', '', $voucher);
	$voucher = preg_replace('/font-family(:)?[^;]+(?(1);)/i', '', $voucher);
	
	return '<div style="-webkit-print-color-adjust:exact;">'.$voucher.'</div>';
}

function eservice_pagina_inicial_loja(){
	global $_HTML;
	global $_HTML_DADOS;
	global $_ESERVICE;
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'descricao',
		))
		,
		"loja",
		"WHERE id='".$_ESERVICE['loja-atual']."'"
	);
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . $loja[0]['nome'] . '.';
	
	$pagina .= $loja[0]['descricao'];
	
	return $pagina;
}

function eservice_logout(){
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_HTML_DADOS;
	global $_REDIRECT_PAGE;
	global $_ESERVICE;
	
	if(!$_DESATIVAR_PADRAO['logout']){
		$delay = $_SESSION[$_SYSTEM['ID']."delay"];
		$url_atual = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
		
		session_unset();
		
		$_SESSION[$_SYSTEM['ID']."delay"] = $delay;
		$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"] = $url_atual;
		
		$_REDIRECT_PAGE = true;
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself');
	}
}

function eservice_cart(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_HTML_DADOS;
	global $_ALERTA;
	global $_ESERVICE;
	
	ini_set('session.gc_maxlifetime', 3600);
	
	$_HTML_DADOS['noindexNofollow'] = true;
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Carrinho de Compras.';
	
	if($_ESERVICE['iframe']){
		$operacao = $_REQUEST['operacao'];
		$id_servicos = $_REQUEST['id'];
	} else {
		$operacao = $_CAMINHO[3];
		$id_servicos = $_CAMINHO[4];
	}
	
	$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
	$loja_dados = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"];
	
	if($operacao == 'add'){
		if($id_servicos){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
					'preco',
					'quantidade',
				))
				,
				"servicos",
				"WHERE id_loja='".$id_loja."'"
				." AND id_servicos='".$id_servicos."'"
			);
			
			if($resultado){
				if((int)$resultado[0]['quantidade'] >= 1){
					if(!$_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"][$id_servicos]){
						$_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"][$id_servicos] = Array(
							'nome' => $resultado[0]['nome'],
							'preco' => $resultado[0]['preco'],
						);
						
						$_SESSION[$_SYSTEM['ID'].$id_loja."disponibilidade"][$id_servicos] = Array(
							'quantidade' => ((int)$resultado[0]['quantidade'] > 100 ? 100 : $resultado[0]['quantidade']),
							'preco' => $resultado[0]['preco'],
						);
					}
					
					$_SESSION[$_SYSTEM['ID'].$id_loja."add_cart_id"] = $id_servicos;
					
					$_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"][$id_servicos]['quantidade']++;
					
				} else {
					$_ALERTA = 'Não há quantidade suficiente em estoque para adicionar no carrinho o serviço desejado.';
				}
			} else {
				$_ALERTA = 'O serviço informado não foi encontrado nesta loja. Favor refazer seu procedimento de compra.';
			}
		} else {
			$_ALERTA = 'Não é possível adicionar o serviço ao carrinho pois o serviço não foi informado. Favor informar o serviço desejado afim de incluí-lo no carrinho.';
		}
		
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/cart');
	}
	
	$carrinho = $_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"];
	$disponibilidade = $_SESSION[$_SYSTEM['ID'].$id_loja."disponibilidade"];
	$add_cart_id = $_SESSION[$_SYSTEM['ID'].$id_loja."add_cart_id"];
	
	$_SESSION[$_SYSTEM['ID'].$id_loja."add_cart_id"] = false;
	
	$_VARIAVEIS_JS['b2make_disponibilidade'] = $disponibilidade;
	$_VARIAVEIS_JS['b2make_carrinho'] = $carrinho;
	
	if($add_cart_id)$_VARIAVEIS_JS['b2make_add_cart_id'] = $add_cart_id;
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- cart < -->','<!-- cart > -->');
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	if($carrinho){
		foreach($carrinho as $id_servicos => $servico){
			$cel_aux = $cel[$cel_nome];
			
			$sub_total = ((int)$servico['quantidade']*(float)$servico['preco']);
			$total += $sub_total;
			
			$cel_aux = modelo_var_troca($cel_aux,"#id_servicos#",$id_servicos);
			$cel_aux = modelo_var_troca($cel_aux,"#servico-nome#",'<div class="b2make-store-cart-remove">X</div><div class="b2make-store-cart-service-name">'.$servico['nome'].'.</div>');
			$cel_aux = modelo_var_troca($cel_aux,"#servico-preco#",preparar_float_4_texto($servico['preco']));
			$cel_aux = modelo_var_troca($cel_aux,"#servico-quantidade#",$servico['quantidade']);
			$cel_aux = modelo_var_troca($cel_aux,"#servico-sub-total#",preparar_float_4_texto($sub_total));
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
	}
	
	$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	
	$pagina = modelo_var_troca($pagina,"#continuar-comprando-url#",$loja_dados['url_continuar_comprando']);
	
	$pagina = modelo_var_troca($pagina,"#total#",preparar_float_4_texto($total));
	
	if($total <= 0)$_VARIAVEIS_JS['b2make_carrinho_zerado'] = true;
	
	return $pagina;
}

function eservice_pre_checkout(){
	global $_ESERVICE;
	global $_CAMINHO;
	global $_SYSTEM;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_HTML_DADOS;
	global $_HTML;
	global $_ALERTA;
	
	$_HTML_DADOS['noindexNofollow'] = true;
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Pré Checkout.';
	
	if($_CAMINHO[2]){
		$pedido_bruto = $_CAMINHO[3];
		
		if($pedido_bruto){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_loja',
					'status',
				))
				,
				"loja",
				"WHERE id='".$_ESERVICE['loja-atual']."'"
			);
			
			if($resultado2){
				if($resultado2[0]['status'] == 'A'){
					$id_loja = $resultado2[0]['id_loja'];
					
					$_SESSION[$_SYSTEM['ID']."eservice_checkout"] = Array(
						'id_loja' => $id_loja,
						'pedido_bruto' => $pedido_bruto,
					);
					
					redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/signup');
				} else {
					seguranca_delay(Array('local' => 'eservice_checkout'));
					$_ALERTA = 'Loja inativa no sistema. Não é possível finalizar sua compra';
					
					redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/cart');
				}
			} else {
				seguranca_delay(Array('local' => 'eservice_checkout'));
				$_ALERTA = 'Loja não encontrada. Favor refazer sua compra.';
				
				redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/cart');
			}
		} else {
			seguranca_delay(Array('local' => 'eservice_checkout'));
			$_ALERTA = 'Compra formatada de forma incorreta. Favor refazer sua compra.';
			
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/cart');
		}
	} else {
		seguranca_delay(Array('local' => 'eservice_checkout'));
		$_ALERTA = 'Compra formatada de forma incorreta. Favor refazer sua compra.';
		
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/cart');
	}
	
}

function eservice_identify_yourself(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_ESERVICE;
	
	// Verificação de Segurança B2Make
	
	$ip = $_SERVER["REMOTE_ADDR"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_cadastro_ips',
		))
		,
		"cadastro_ips",
		"WHERE UNIX_TIMESTAMP(data) < ".(time()-$_PROJETO['CADASTRO_IPS_PERIODO_SEGUNDOS']).""
	);
	
	if($resultado){
		banco_delete
		(
			"cadastro_ips",
			"WHERE UNIX_TIMESTAMP(data) < ".(time()-$_PROJETO['CADASTRO_IPS_PERIODO_SEGUNDOS']).""
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
		if($_PROJETO['CADASTRO_IPS_TENTATIVAS_MAX'] <= (int)$resultado[0]['tentativas']){
			$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = false;
		} else {
			$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = true;
		}
	} else {
		$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = true;
	}
	
	$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = false;
	
	if(!$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"]){
		$_VARIAVEIS_JS['recaptcha_enable'] = 'sim';
		$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	}
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$modulo = modelo_tag_val($modelo,'<!-- identify_yourself < -->','<!-- identify_yourself > -->');
	
	if($_ESERVICE['iframe']){
		$modulo = modelo_var_troca($modulo,"<!-- signup-iframe -->",'<input name="iframe" type="hidden" value="true">');
		$modulo = modelo_var_troca($modulo,"<!-- signin-iframe -->",'<input name="iframe" type="hidden" value="true">');
		
		$url = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
		$modulo = modelo_var_troca($modulo,"#esqueceu-senha-link#",$url . 'forgot-your-password/');
		$modulo = modelo_var_troca($modulo,"#target#",'_parent');
	} else {
		$modulo = modelo_var_troca($modulo,"#esqueceu-senha-link#",'!#caminho_raiz#!e-services/!#b2make-loja-atual#!/forgot-your-password');
		$modulo = modelo_var_troca($modulo,"#target#",'_self');
	}
	
	if($_REQUEST['_iframe_session']){
		$modulo = modelo_var_troca($modulo,'<!-- signup-iframe-session -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
		$modulo = modelo_var_troca($modulo,'<!-- signin-iframe-session -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
	}
	
	$_MODULO_EXTERNO['#e-services-identify-yourself#'] = $modulo;
	
	$_CONTEUDO_ID_AUX = 'e-services-identify-yourself';
	
	$saida = opcao_nao_econtrada();
	
	return $saida;
}

function eservice_signin(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_ESERVICE;
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself');
	
	/* $modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$modulo = modelo_tag_val($modelo,'<!-- signin < -->','<!-- signin > -->');
	
	$_MODULO_EXTERNO['#e-services-signin#'] = $modulo;
	
	$_CONTEUDO_ID_AUX = 'e-services-signin';
	
	$saida = opcao_nao_econtrada(); */
	
	return $saida;
}

function eservice_signin_request(){
	global $_LOGAR_REDIRECT_LOGIN;
	global $_ESERVICE;
	global $_SYSTEM;
	
	if(!$_SESSION[$_SYSTEM['ID'].'loja-logar-local']) $_SESSION[$_SYSTEM['ID'].'loja-logar-local'] = 'e-services/'.$_ESERVICE['loja-atual'].'/purchases';
	$_LOGAR_REDIRECT_LOGIN = 'e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself';
	
	return eservice_logar();
}

function eservice_signup_request(){
	global $_SYSTEM;
	global $_HTML;
	global $_VARS;
	global $_CONTEUDO_ID_AUX;
	global $_REMOTE_ADDR;
	global $_PROJETO;
	global $_ECOMMERCE;
	global $_ESERVICE;
	
	if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
		alerta('<p>Este E-mail não é válido! Escolha outro!</p>');
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself');
	}
	
	$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
	
	$resultado = banco_select
	(
		"id_loja_usuarios",
		'loja_usuarios',
		"WHERE email='" . strtolower($_REQUEST['email']) . "' AND status!='D'"
		." AND id_loja='".$id_loja."'"
	);
	
	if($resultado){
		alerta('<p>E-mail já está em uso!<p></p>Escolha outro!</p>');
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself');
	}
	
	if($_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"]){
		$validado = true;
	} else {
		$validado = recaptcha_verify();
	}
	
	if($validado){
		$ip = $_SERVER["REMOTE_ADDR"];
		
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
		
		if($_PROJETO['e-services']){
			$local = 'e-services/'.$_ESERVICE['loja-atual'].'/'.$_PROJETO['e-services']['permissao_local_inicial'];
			$id_usuario_perfil = $_PROJETO['e-services']['permissao_usuario'];
		}
		
		$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
		
		$campo_nome = "id_loja";										$campos[] = Array($campo_nome,$id_loja);
		$campo_nome = "nome"; $post_nome = $campo_nome;		 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "ultimo_nome"; $post_nome = $campo_nome;			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "email"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags(strtolower($_REQUEST[$post_nome])));
		$campo_nome = "senha"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,crypt($_REQUEST[$post_nome]));
		$campo_nome = "telefone"; $post_nome = $campo_nome;				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "cpf"; $post_nome = $campo_nome;					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "cnpj"; $post_nome = $campo_nome;					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
		
		$campo_nome = "cnpj_selecionado"; $post_nome = 'cpf-cnpj-check';
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
		
		if($_ESERVICE['iframe']){
			$url = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
			
			$url2 = html(Array(
				'tag' => 'a',
				'val' => $url.'identify-yourself/',
				'attr' => Array(
					'href' => $url.'identify-yourself/',
				)
			));
		} else {
			$url2 = html(Array(
				'tag' => 'a',
				'val' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself/',
				'attr' => Array(
					'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself/',
				)
			));
		}
		
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
		$email_assunto = modelo_var_troca_tudo($email_assunto,"#titulo#",$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]["nome"]);
		
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#titulo#",$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]["nome"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#codigo#",$codigo);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",strip_tags($_REQUEST["nome"]));
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url2#",$url2);
		
		$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
		
		$parametros['from_name'] = $_HTML['TITULO'];
		$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
		
		$parametros['email_name'] = strip_tags($_REQUEST["nome"]);
		$parametros['email'] = strip_tags(strtolower($_REQUEST["email"]));
		
		$loja = banco_select_name
		(
			banco_campos_virgulas(Array(
				'email_assinatura',
			))
			,
			"loja",
			"WHERE id='".$_ESERVICE['loja-atual']."'"
		);
		
		$parametros['subject'] = $email_assunto;
		$parametros['mensagem'] = $email_mensagem;
		$parametros['mensagem'] .= $loja[0]['email_assinatura'];
		
		$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#loja-nome#",$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]["nome"]);
		
		if($parametros['enviar_mail'])enviar_mail($parametros);
		
		alerta($alerta_sucesso);
		
		eservice_signup_cadastro_user_logar($id_loja_usuarios);
	} else {
		alerta('<p>Código de validação <b style="color:red;">inválido</b>!<br>O código recaptcha especificado é inválido!</p>');
	}
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself');
}

function eservice_signup_cadastro_user_logar($id_loja_usuarios){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ECOMMERCE;
	global $_REMOTE_ADDR;
	global $_DEBUG;
	global $_PROJETO;
	global $_ESERVICE;
	
	$loja_usuarios = banco_select_name
	(
		"*"
		,
		"loja_usuarios",
		"WHERE id_loja_usuarios='" . $id_loja_usuarios . "'"
	);
	
	$senha_sessao = sha1(crypt($loja_usuarios[0]['senha']).mt_rand());
	$loja_usuarios[0]['senha_sessao'] = $senha_sessao;
	
	banco_update
	(
		"senha_sessao='".$senha_sessao."',".
		"data_login=NOW()",
		"loja_usuarios",
		"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
	);
	banco_delete
	(
		"bad_list",
		"WHERE ip='".$_REMOTE_ADDR."'"
	);
	
	$_SESSION[$_SYSTEM['ID']."loja_usuarios"] = $loja_usuarios[0];
	$_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"] = $_REQUEST['senha'];
	
	$_SESSION[$_SYSTEM['ID']."loja-permissao"] = true;
	
	if($_SESSION[$_SYSTEM['ID'].'loja-logar-local']){
		$local = $_SESSION[$_SYSTEM['ID'].'loja-logar-local'];
		$_SESSION[$_SYSTEM['ID'].'loja-logar-local'] = false;
	} else {
		$local = 'e-services/'.$_ESERVICE['loja-atual'].'/'.$_PROJETO['e-services']['permissao_local_inicial'];
	}
	
	global $_REDIRECT_PAGE;
	$_REDIRECT_PAGE = true;
	
	if(!$_DEBUG)redirecionar($local);
}

function eservice_signup(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_HTML_DADOS;
	global $_ESERVICE;
	global $_VARS;
	global $_ALERTA;
	
	$_HTML_DADOS['noindexNofollow'] = true;
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Cadastro.';
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- signup-2 < -->','<!-- signup-2 > -->');
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'status',
		))
		,
		"loja",
		"WHERE id='".$_ESERVICE['loja-atual']."'"
	);
	
	$id_loja = $resultado[0]['id_loja'];
	
	$carrinho = $_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"];
	
	if($carrinho){
		$pagina = modelo_var_troca($pagina,"#sem-servico-extra#",' style="display:none;"');
		
		$cel_nome = 'cel';
		if($carrinho)
		foreach($carrinho as $servico){
			$cel_aux = $cel[$cel_nome];
			
			$servico['sub-total'] = (int)$servico['quantidade'] * (float)$servico['preco'];
			$total += $servico['sub-total'];
			
			$cel_aux = modelo_var_troca($cel_aux,"#servico#",$servico['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#quantidade#",$servico['quantidade']);
			$cel_aux = modelo_var_troca($cel_aux,"#preco#",preparar_float_4_texto($servico['preco']));
			$cel_aux = modelo_var_troca($cel_aux,"#sub-total#",preparar_float_4_texto($servico['sub-total']));
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		
		$pagina = modelo_var_troca($pagina,"#total#",preparar_float_4_texto($total));
	} else {
		$_ALERTA = '&Eacute; necess&aacute;rio ter pelo menos 1 servi&ccedil;o no seu carrinho para dar continuidade a sua compra.';
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/cart');
	}
	
	if($_CAMINHO[3] == 'bad-login'){
		$_VARIAVEIS_JS['bad_login'] = true;
	}
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	$_VARIAVEIS_JS['recaptcha_enable'] = 'sim';
	
	if($_ESERVICE['iframe']){
		$url = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
		$pagina = modelo_var_troca($pagina,"#esqueceu-senha-link#",$url . 'forgot-your-password/');
		$pagina = modelo_var_troca_tudo($pagina,"#target#",'_parent');
		$pagina = modelo_var_troca_tudo($pagina,"#cart-link#",$url . 'cart/');
	} else {
		$pagina = modelo_var_troca($pagina,"#esqueceu-senha-link#",'!#caminho_raiz#!e-services/!#b2make-loja-atual#!/forgot-your-password/');
		$pagina = modelo_var_troca_tudo($pagina,"#target#",'_self');
		$pagina = modelo_var_troca_tudo($pagina,"#cart-link#",$url . '!#caminho_raiz#!e-services/!#b2make-loja-atual#!/cart/');
	}
	
	if($_REQUEST['_iframe_session']){
		$pagina = modelo_var_troca($pagina,'<!-- signup-iframe-session -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
		$pagina = modelo_var_troca($pagina,'<!-- signin-iframe-session -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
	}
	
	return $pagina;
}

function eservice_signin_2_request(){
	global $_LOGAR_REDIRECT_LOGIN;
	global $_ESERVICE;
	global $_SYSTEM;
	
	$_SESSION[$_SYSTEM['ID'].'loja-logar-local'] = 'e-services/'.$_ESERVICE['loja-atual'].'/checkout';
	$_LOGAR_REDIRECT_LOGIN = 'e-services/'.$_ESERVICE['loja-atual'].'/signup';
	
	return eservice_logar();
}

function eservice_signup_2_request(){
	global $_SYSTEM;
	global $_HTML;
	global $_VARS;
	global $_CONTEUDO_ID_AUX;
	global $_REMOTE_ADDR;
	global $_PROJETO;
	global $_ECOMMERCE;
	global $_ESERVICE;
	
	if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
		alerta('<p>Este E-mail não é válido! Escolha outro!</p>');
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/signup');
	}
	
	$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
	
	$resultado = banco_select
	(
		"id_loja_usuarios",
		'loja_usuarios',
		"WHERE email='" . strtolower($_REQUEST['email']) . "' AND status!='D'"
		." AND id_loja='".$id_loja."'"
	);
	
	if($resultado){
		alerta('<p>E-mail já está em uso!<p></p>Escolha outro!</p>');
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/signup');
	}
	
	$validado = recaptcha_verify();
	
	if($validado){
		$ip = $_SERVER["REMOTE_ADDR"];
		
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
		
		if($_PROJETO['e-services']){
			$local = 'e-services/'.$_ESERVICE['loja-atual'].'/checkout';
			$id_usuario_perfil = $_PROJETO['e-services']['permissao_usuario'];
		}
		
		$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
		
		$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
		
		$campo_nome = "id_loja";										$campos[] = Array($campo_nome,$id_loja);
		$campo_nome = "nome"; $post_nome = 'primeiro-nome';				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "ultimo_nome"; $post_nome = 'ultimo-nome';		if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "email"; $post_nome = "email"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags(strtolower($_REQUEST[$post_nome])));
		$campo_nome = "senha"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,crypt($_REQUEST[$post_nome]));
		$campo_nome = "telefone"; $post_nome = $campo_nome;				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "cpf"; $post_nome = $campo_nome;					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "cnpj"; $post_nome = $campo_nome;					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
		
		$campo_nome = "cnpj_selecionado"; $post_nome = 'cpf-cnpj-check';
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
		
		if($_ESERVICE['iframe']){
			$url = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
			
			$url2 = html(Array(
				'tag' => 'a',
				'val' => $url.'identify-yourself/',
				'attr' => Array(
					'href' => $url.'identify-yourself/',
				)
			));
		} else {
			$url2 = html(Array(
				'tag' => 'a',
				'val' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself/',
				'attr' => Array(
					'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'e-services/'.$_ESERVICE['loja-atual'].'/identify-yourself/',
				)
			));
		}
		
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
		$email_assunto = modelo_var_troca_tudo($email_assunto,"#titulo#",$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]["nome"]);
		
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#titulo#",$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]["nome"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#codigo#",$codigo);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",strip_tags($_REQUEST["primeiro-nome"]));
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url2#",$url2);
		
		$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
		
		$parametros['from_name'] = $_HTML['TITULO'];
		$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
		
		$parametros['email_name'] = strip_tags($_REQUEST["primeiro-nome"]);
		$parametros['email'] = strip_tags(strtolower($_REQUEST["email"]));
		
		$loja = banco_select_name
		(
			banco_campos_virgulas(Array(
				'email_assinatura',
			))
			,
			"loja",
			"WHERE id='".$_ESERVICE['loja-atual']."'"
		);
		
		$parametros['subject'] = $email_assunto;
		$parametros['mensagem'] = $email_mensagem;
		$parametros['mensagem'] .= $loja[0]['email_assinatura'];
		
		$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#loja-nome#",$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]["nome"]);
		
		if($parametros['enviar_mail'])enviar_mail($parametros);
		
		eservice_signup_cadastro_user_logar_2($id_loja_usuarios);
	} else {
		alerta('<p>Código de validação <b style="color:red;">inválido</b>!<br>O código recaptcha especificado é inválido!</p>');
	}
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/signup');
}

function eservice_signup_cadastro_user_logar_2($id_loja_usuarios){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ECOMMERCE;
	global $_REMOTE_ADDR;
	global $_DEBUG;
	global $_PROJETO;
	global $_ESERVICE;
	
	$loja_usuarios = banco_select_name
	(
		"*"
		,
		"loja_usuarios",
		"WHERE id_loja_usuarios='" . $id_loja_usuarios . "'"
	);
	
	$senha_sessao = sha1(crypt($loja_usuarios[0]['senha']).mt_rand());
	$loja_usuarios[0]['senha_sessao'] = $senha_sessao;
	
	banco_update
	(
		"senha_sessao='".$senha_sessao."',".
		"data_login=NOW()",
		"loja_usuarios",
		"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
	);
	banco_delete
	(
		"bad_list",
		"WHERE ip='".$_REMOTE_ADDR."'"
	);
	
	$_SESSION[$_SYSTEM['ID']."loja_usuarios"] = $loja_usuarios[0];
	$_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"] = $_REQUEST['senha'];
	
	$_SESSION[$_SYSTEM['ID']."loja-permissao"] = true;
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/checkout');
}

function eservice_checkout(){
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_SYSTEM;
	global $_ESERVICE;
	
	if($_SESSION[$_SYSTEM['ID']."eservice_checkout_mens"]){
		$modulo = $_SESSION[$_SYSTEM['ID']."eservice_checkout_mens"];
		$_SESSION[$_SYSTEM['ID']."eservice_checkout_mens"] = false;
	}
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/checkout');
	
	if($_SESSION[$_SYSTEM['ID']."eservice_checkout"]){
		$checkout = $_SESSION[$_SYSTEM['ID']."eservice_checkout"];
		
		$id_loja = $checkout['id_loja'];
		$pedido_bruto_arr = explode('_',$checkout['pedido_bruto']);
		
		if($pedido_bruto_arr)
		foreach($pedido_bruto_arr as $item){
			$pedido_dados = explode('-',$item);
			$id = $pedido_dados[0];
			$quant = $pedido_dados[1];
			
			if($id && $quant){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
						'preco',
						'quantidade',
					))
					,
					"servicos",
					"WHERE id_servicos='".$id."'"
					." AND id_loja='".$id_loja."'"
				);
				
				if($resultado){
					if((int)$quant > (int)$resultado[0]['quantidade']){
						$disponibilidade = false;
						$servico_sem_estoque = true;
					} else {
						$disponibilidade = true;
						$pedido[] = $id.','.$quant;
					}
					
					$pre_pedido[] = Array(
						'nome' => $resultado[0]['nome'],
						'quant' => $quant,
						'preco' => $resultado[0]['preco'],
						'disponibilidade' => $disponibilidade,
					);
				}
			}
		}
		
		if($pedido){
			$_SESSION[$_SYSTEM['ID']."eservice_cadastrar_pedido"] = Array(
				'pedido' => $pedido,
				'id_loja' => $id_loja,
			);
			
			if($servico_sem_estoque){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
				$modulo = modelo_tag_val($modelo,'<!-- checkout_indisponibilidade < -->','<!-- checkout_indisponibilidade > -->');
				
				$cel_nome = 'cel1'; $cel[$cel_nome] = modelo_tag_val($modulo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
				$modulo = modelo_tag_in($modulo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
				
				if($pre_pedido)
				foreach($pre_pedido as $item){
					$cel_aux = $cel[$cel_nome];
					
					$nome = $item['nome'];
					$quant = $item['quant'];
					$preco = $item['preco'];
					
					if($item['disponibilidade']){
						$sub_total = (float)$preco*(int)$quant;
						$num_itens += (int)$quant;
						$disponibilidade = 'Sim';
						$preco_unitario = 'R$ '.preparar_float_4_texto($preco);
						$sub_total_txt = 'R$ '.preparar_float_4_texto($sub_total);
						$class = 'b2make-servico-pedido-disponivel';
					} else {
						$sub_total = 0;
						$disponibilidade = 'Não';
						$preco_unitario =  '--';
						$sub_total_txt =  '--';
						$quant =  '--';
						$class = 'b2make-servico-pedido-indisponivel';
					}
					
					$total = $total + $sub_total;
					
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#class#",' '.$class);
					$cel_aux = modelo_var_troca($cel_aux,"#nome#",$nome);
					$cel_aux = modelo_var_troca($cel_aux,"#quant#",$quant);
					$cel_aux = modelo_var_troca($cel_aux,"#disponibilidade#",$disponibilidade);
					$cel_aux = modelo_var_troca($cel_aux,"#preco-unitario#",$preco_unitario);
					$cel_aux = modelo_var_troca($cel_aux,"#sub-total#",$sub_total_txt);
					
					$modulo = modelo_var_in($modulo,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				$modulo = modelo_var_troca($modulo,'<!-- '.$cel_nome.' -->','');
				
				$total_txt = 'R$ '.preparar_float_4_texto($total);
				
				$modulo = modelo_var_troca($modulo,"#total#",$total_txt);
				$modulo = modelo_var_troca($modulo,"#num-itens#",$num_itens);
			} else {
				eservice_request_register();
			}
		} else {
			seguranca_delay(Array('local' => 'eservice_checkout'));
			$modulo = 'Infelizmente no momento todos os serviços do seu pedido estão indisponíveis para compra.';
		}
	} else {
		$modulo = '<p>Não há nenhuma solicitação de finalização de compra no momento. É necessário finalizar uma compra para poder acessar mais informações de CheckOut.</p>';
	}
	
	$_MODULO_EXTERNO['#e-services-checkout#'] = $modulo;
	
	$_CONTEUDO_ID_AUX = 'e-services-checkout';
	
	$saida = opcao_nao_econtrada();
	
	return $saida;
}

function eservice_request_register(){
	global $_SYSTEM;
	global $_ESERVICE;
	global $_B2MAKE_URL;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/checkout');
	
	if($_SESSION[$_SYSTEM['ID']."eservice_cadastrar_pedido"]){
		$cadastrar_pedido = $_SESSION[$_SYSTEM['ID']."eservice_cadastrar_pedido"];
		$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
		
		$id_loja_usuarios = $loja_usuarios['id_loja_usuarios'];
		$pedido = $cadastrar_pedido['pedido'];
		$id_loja = $cadastrar_pedido['id_loja'];
		
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
				't2.services_list',
				't2.id_host',
			))
			,
			"loja as t1,host as t2",
			"WHERE t1.id_loja='".$id_loja."'"
			." AND t1.id_usuario=t2.id_usuario"
		);
		
		$services_list = json_decode($host[0]['t2.services_list'],true);
		$id_host = $host[0]['t2.id_host'];
		
		// ============================== Cadastrar itens do pedido
		
		foreach($pedido as $item){
			list($id_servicos,$quant) = explode(',',$item);
			
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
					'versao',
					'quantidade',
				))
				,
				"servicos",
				"WHERE id_servicos='".$id_servicos."'"
			);
			
			$id_site = $servicos[0]['id_site'];
			
			banco_update
			(
				"quantidade = (quantidade - ".$quant.")",
				"servicos",
				"WHERE id_servicos='".$id_servicos."'"
			);
			
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
					
					$valor_original = (float)$servicos[0]['preco'];
					
					$preco = (($valor_original * $desconto) / 100);
					
					$desconto = 100 - $desconto;
				} else {
					$desconto = (float)$servicos[0]['desconto'];
					$valor_original = (float)$servicos[0]['preco'];
					
					$preco = (($valor_original * (100 - $desconto)) / 100);
				}
			} else {
				if($desconto_cupom){
					$desconto = (float)$desconto_cupom;
					$valor_original = (float)$servicos[0]['preco'];
					
					$preco = (($valor_original * (100 - $desconto)) / 100);
				} else {
					$desconto = false;
					$valor_original = false;
					
					$preco = (float)$servicos[0]['preco'];
				}
			}
			
			$sub_total = $preco*$quant;
			$valor_total = $valor_total + $sub_total;
			
			for($i=0;$i<$quant;$i++){
				$campos = null;
				
				$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_servicos"; $campo_valor = $id_servicos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "quantidade"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "desconto"; $campo_valor = $desconto; 		if($desconto)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "validade"; $campo_valor = $validade; 		if($validade)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "validade_data"; $campo_valor = $servicos[0]['validade_data']; 	if($campo_valor)if($campo_valor != '0000-00-00 00:00:00')	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "validade_tipo"; $campo_valor = $servicos[0]['validade_tipo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "valor_original"; if($valor_original){$campo_valor = number_format($valor_original, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
				$campo_nome = "sub_total"; $campo_valor = number_format($preco, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "status"; $campo_valor = 'N'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				
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
			
			$pedidos_servicos[] = Array(
				'id' => $id_servicos,
				'preco' => (string)$preco,
				'quant' => $quant,
				'titulo' => $servicos[0]['nome'],
				'href' => '',
			);
			
			// ======================================== Atualizar Lista de Serviços
			
			$path = eservice_site_pagina_diretorio($id_site,false,true);
			$url = $host[0]['t2.url'] . $path;
			
			$service_index = false;
			$service_found = false;
			if($services_list){
				foreach($services_list as $num => $service){
					if($service['id'] == $id_servicos){
						$service_index = $num;
						$service_found = true;
						break;
					}
				}
			} else {
				$services_list = Array();
			}
			
			//$url_imagem = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
			if($servicos[0]['imagem_path'])$url_imagem = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
			
			$validade_data = $servicos[0]['validade_data'];
			
			if($servicos[0]['validade_data']) $data = data_hora_from_datetime($servicos[0]['validade_data']);
			
			if($data){
				$servicos[0]['validade_data'] = $data[0];
				$servicos[0]['validade_hora'] = $data[1];
			}
			
			$service_data = Array(
				'id' => $id_servicos,
				'url' => $url,
				'nome' => $servicos[0]['nome'],
				'descricao' => $servicos[0]['descricao'],
				'quantidade' => ((int)$servicos[0]['quantidade'] - $quant > 0 ? (int)$servicos[0]['quantidade'] - $quant : 0),
				'validade' => $servicos[0]['validade'],
				'validade_data' => $servicos[0]['validade_data'],
				'validade_hora' => $servicos[0]['validade_hora'],
				'validade_tipo' => $servicos[0]['validade_tipo'],
				'preco' => $servicos[0]['preco'],
				'desconto' => $servicos[0]['desconto'],
				'desconto_de' => $servicos[0]['desconto_de'],
				'desconto_ate' => $servicos[0]['desconto_ate'],
				'visivel_de' => $servicos[0]['visivel_de'],
				'visivel_ate' => $servicos[0]['visivel_ate'],
			);
			
			if($url_imagem)$service_data['url_imagem'] = $url_imagem;
			
			if($service_found){
				unset($services_list[$service_index]);
			}
			array_unshift($services_list, $service_data);
		}
		
		// ======================================== Atualizar Lista de Serviços no Banco
		
		$json_services_list = json_encode($services_list);
		
		banco_update
		(
			"services_list='".addslashes($json_services_list)."'",
			"host",
			"WHERE id_host='".$id_host."'"
		);
		
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
		
		$_SESSION[$_SYSTEM['ID']."eservice_cadastrar_pedido"] = false;
		$_SESSION[$_SYSTEM['ID']."eservice_checkout"] = false;
		
		// =============== Ativar atualização serviços do host
		
		$url = $host[0]['t2.url'] . 'b2make/services.php';
		
		curl_post_async($url);
		
		// =============== Limpar carrinho
		
		$_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"] = false;
		$_SESSION[$_SYSTEM['ID'].$id_loja."disponibilidade"] = false;
		
		// =============== Redirecionar Pagamento
		
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/emission');
	}
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/checkout');
}

function eservice_emission(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_HTML_DADOS;
	global $_ESERVICE;
	global $_VARS;
	global $_ALERTA;
	global $_ESERVICES;
	
	$_HTML_DADOS['noindexNofollow'] = true;
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Emissão do Voucher.';
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- emission < -->','<!-- emission > -->');
	
	$cel_nome = 'cel-emission'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	$_VARIAVEIS_JS['usuario_nome_completo'] = $loja_usuarios['nome'].' '.$loja_usuarios['ultimo_nome'];
	$_VARIAVEIS_JS['usuario_telefone'] = $loja_usuarios['telefone'];
	$_VARIAVEIS_JS['usuario_documento'] = ($loja_usuarios['cnpj_selecionado'] ? $loja_usuarios['cnpj'] : $loja_usuarios['cpf']);
	
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
				'tipo_frete',
				'valor_frete',
				'valor_total',
				'dest_endereco',
				'status',
				'id_loja',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		$id_loja = $pedido[0]['id_loja'];
		
		if($_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"] != $id_loja){
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
		}
		
		$loja = banco_select_name
		(
			banco_campos_virgulas(Array(
				'pagseguro_app_code',
				'paypal_email',
				'paypal_user',
				'paypal_pass',
				'paypal_signature',
				'paypal_app_installed',
				'paypal_app_active',
				'paypal_plus_inactive',
			))
			,
			"loja",
			"WHERE id_loja='".$id_loja."'"
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
			
			alerta('<p style="color:red;">Não é possível mudar a emissão do seu pedido atual de código: <b>'.$pedido[0]['codigo'].'</b> uma vez que ele está com estado: <b>'.$pedido[0]['status'].'</b>. Favor alterar a emissão na página de Pedidos para definir as novas identificações.</p>');
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
		}
		
		$pedido_num = $pedido[0]['codigo'];
		$valor_frete = (int)$pedido[0]['valor_frete'];
		$valor_total = (float)$pedido[0]['valor_total'];
		
		$pagina = modelo_var_troca($pagina,"#sem-servico-extra#",' style="display:none;"');
		
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_servicos',
				'sub_total',
				'codigo',
			))
			,
			"pedidos_servicos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		$cel_nome = 'cel-emission';
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
					'nome' => $servicos[0]['nome'],
				);
			}
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#servico-nome#",$pedido_proc[$ps['id_servicos']]['nome']);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#servico-codigo#",$ps['codigo']);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#servico-input-name#",'servico_'.$ps['codigo']);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#servico-input-id-1#",'servico_'.$ps['codigo'].'_1');
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#servico-input-id-2#",'servico_'.$ps['codigo'].'_2');
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		
		$cel_nome = 'cel';
		if($pedido_proc)
		foreach($pedido_proc as $pedido){
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#servico#",$pedido['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#quantidade#",$pedido['quantidade']);
			$cel_aux = modelo_var_troca($cel_aux,"#preco#",'R$ '.preparar_float_4_texto(number_format((float)$pedido['preco'], 2, '.', '')));
			$cel_aux = modelo_var_troca($cel_aux,"#sub-total#",'R$ '.preparar_float_4_texto(number_format(((float)$pedido['preco']*$pedido['quantidade']), 2, '.', '')));
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		
		$pagina = modelo_var_troca($pagina,"#pedido-num#",$pedido_num);
		$pagina = modelo_var_troca($pagina,"#total#",preparar_float_4_texto($valor_total));
		
		if($_REQUEST['_iframe_session']){
			$pagina = modelo_var_troca($pagina,'<!-- emission-iframe-session -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
		}
	} else {
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
	}
	
	return $pagina;
}

function eservice_emission_request(){
	global $_SYSTEM;
	global $_ESERVICE;
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
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
			
			$campo_nome = "identificacao_nome"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST['servico_'.$ps['codigo'].'_nome'] . "'";
			$campo_nome = "identificacao_documento"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST['servico_'.$ps['codigo'].'_doc'] . "'";
			$campo_nome = "identificacao_telefone"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST['servico_'.$ps['codigo'].'_tel'] . "'";
			
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
	}
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/payment');
}

function eservice_payment(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_HTML_DADOS;
	global $_ESERVICE;
	global $_ESERVICES;
	
	$_HTML_DADOS['noindexNofollow'] = true;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/payment');
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Pagamento.';
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
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
				'tipo_frete',
				'valor_frete',
				'valor_total',
				'dest_endereco',
				'status',
				'id_loja',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		$id_loja = $pedido[0]['id_loja'];
		
		if($_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"] != $id_loja){
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
		}
		
		$loja = banco_select_name
		(
			banco_campos_virgulas(Array(
				'pagseguro_app_code',
				'paypal_email',
				'paypal_user',
				'paypal_pass',
				'paypal_signature',
				'paypal_app_installed',
				'paypal_app_active',
				'paypal_plus_inactive',
			))
			,
			"loja",
			"WHERE id_loja='".$id_loja."'"
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
			
			alerta('<p style="color:red;">Não é possível pagar o seu pedido atual de código: <b>'.$pedido[0]['codigo'].'</b> uma vez que ele está com estado: <b>'.$pedido[0]['status'].'</b>. Favor escolher na página de Pedidos um pedido com estado: <b>'.$_ESERVICES['status_mudar']['N'].'</b> para prosseguir com o pagamento</p>');
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
		}
		
		$pedido_num = $pedido[0]['codigo'];
		$valor_frete = (int)$pedido[0]['valor_frete'];
		$valor_total = (float)$pedido[0]['valor_total'];
		
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- payment-2 < -->','<!-- payment-2 > -->');
		
		$layout = $pagina;
		
		// Menu de opções de pagamento: marcar selecionado
		
		$opcao_pagamento = $_CAMINHO[3];
		
		switch($opcao_pagamento){
			case 'other-payer':	$layout = modelo_var_troca($layout,"#other-payer-extra#",' data-selecionado="sim"'); break;
			case 'paypal':	$layout = modelo_var_troca($layout,"#paypal-extra#",' data-selecionado="sim"'); break;
			default:
				$layout = modelo_var_troca($layout,"#credit-card-extra#",' data-selecionado="sim"');
		}
		
		$layout = modelo_var_troca($layout,"#other-payer-extra#",'');
		$layout = modelo_var_troca($layout,"#paypal-extra#",'');
		$layout = modelo_var_troca($layout,"#credit-card-extra#",'');
		
		// Menu de opções de pagamento: remover opções inativas 
		
		if(!$loja[0]['paypal_app_installed'] || !$loja[0]['paypal_app_active'] || $loja[0]['paypal_plus_inactive']){
			$cel_nome = 'ppplus'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->'); $sem_paypal_plus = true;
		}
		
		if(!$loja[0]['paypal_app_installed'] || !$loja[0]['paypal_app_active']){
			$cel_nome = 'paypal'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->'); $sem_paypal = true;
		}
		
		// Conteiner principal de formas de pagamento, inserir os conteiners para cada caso
		
		switch($opcao_pagamento){
			case 'other-payer':
				if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && !$loja[0]['paypal_plus_inactive']){
					$payment_formas = eservice_paypal_plus_outro_pagador_pre_pay();
				} else if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && $loja[0]['paypal_plus_inactive']) {
					redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/payment/paypal');
				}
			break;
			case 'paypal':
				if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active']){
					$payment_formas = eservice_paypal_cont();
				}
			break;
			default:
				if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && !$loja[0]['paypal_plus_inactive']){
					$payment_formas = eservice_paypal_plus_attempt_pay();
				} else if($loja[0]['paypal_app_installed'] && $loja[0]['paypal_app_active'] && $loja[0]['paypal_plus_inactive']) {
					redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/payment/paypal');
				}
		}
		
		// Se não houver formas de pagamento, clocar uma mensagem no conteiner de formas de pagamentos
		
		if(
			$sem_paypal_plus &&
			$sem_paypal 
		){
			$payment_formas = '<p>Não há nenhum gateway de pagamento operacional no momento. Favor tentar novamente mais tarde.</p>';
		}
		
		$layout = modelo_var_troca($layout,"#payment-formas#",$payment_formas);
		
		// Montar a ficha do pedido e colocar no conteiner específico
		
		$cel_nome = 'cel1'; $cel[$cel_nome] = modelo_tag_val($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		$layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		$pedidos_servicos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_servicos',
				'sub_total',
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
					'nome' => $servicos[0]['nome'],
				);
			}
		}
		
		if($pedido_proc)
		foreach($pedido_proc as $pedido){
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#servico#",$pedido['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#quantidade#",$pedido['quantidade']);
			$cel_aux = modelo_var_troca($cel_aux,"#preco#",'R$ '.preparar_float_4_texto(number_format((float)$pedido['preco'], 2, '.', '')));
			$cel_aux = modelo_var_troca($cel_aux,"#sub-total#",'R$ '.preparar_float_4_texto(number_format(((float)$pedido['preco']*$pedido['quantidade']), 2, '.', '')));
			
			$layout = modelo_var_in($layout,'<!-- '.$cel_nome.' -->',$cel_aux);
			
			$num_itens += $pedido['quantidade'];
		}
		
		$layout = modelo_var_troca($layout,"#frete#",'R$ '.preparar_float_4_texto(number_format((float)$valor_frete, 2, '.', '')));
		$layout = modelo_var_troca($layout,"#total#",'R$ '.preparar_float_4_texto(number_format(((float)$valor_frete+(float)$valor_total), 2, '.', '')));
		$layout = modelo_var_troca($layout,"#pedido-num#",$pedido_num);
		$layout = modelo_var_troca($layout,"#num-itens#",$num_itens);
		
		$_VARIAVEIS_JS['pagseguro_total'] = number_format(((float)$valor_frete+(float)$valor_total), 2, '.', '');
		
		$modulo = $layout;
	} else {
		redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
	}
	
	return $modulo;
}

function eservice_account(){
	global $_SYSTEM;
	global $_ESERVICE;
	global $_HTML_DADOS;
	global $_HTML;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/account');
	
	$_HTML_DADOS['noindexNofollow'] = true;
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Minha Conta.';
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$modulo = modelo_tag_val($modelo,'<!-- account < -->','<!-- account > -->');
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	$modulo = modelo_var_troca($modulo,"#nome#",$loja_usuarios['nome']);
	$modulo = modelo_var_troca($modulo,"#ultimo-nome#",$loja_usuarios['ultimo_nome']);
	$modulo = modelo_var_troca($modulo,"#telefone#",$loja_usuarios['telefone']);
	$modulo = modelo_var_troca($modulo,"#cpf_ou_cnpj#",($loja_usuarios['cnpj_selecionado'] ? '2' : '1'));
	$modulo = modelo_var_troca($modulo,"#cpf#",$loja_usuarios['cpf']);
	$modulo = modelo_var_troca($modulo,"#cnpj#",$loja_usuarios['cnpj']);
	
	if($_REQUEST['_iframe_session']){
		$modulo = modelo_var_troca($modulo,'<!-- iframe -->','<input name="_iframe_session" type="hidden" value="'.$_REQUEST['_iframe_session'].'">');
	}
	
	// ==================== Histórico ======================
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
			'data',
		))
		,
		"log",
		"WHERE id_referencia='".$loja_usuarios['id_loja'].'-'.$loja_usuarios['id_loja_usuarios']."'"
		." AND grupo='loja_usuarios'"
		." ORDER BY data DESC"
	);
	
	if(!$resultado){
		$cel_nome = 'cel-historico'; $modulo = modelo_tag_in($modulo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	} else {
		foreach($resultado as $res){
			$historico .= data_hora_from_datetime_to_text($res['data']).' - '.$res['valor'].'<br>';
		}
		
		$modulo = modelo_var_troca($modulo,"#historico#",$historico);
	}
	
	return $modulo;
}

function eservice_account_db(){
	global $_SYSTEM;
	global $_ESERVICE;
	global $_ALERTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	if($usuario){
		$campo_tabela = "loja_usuarios";
		$campo_tabela_extra = "WHERE id_loja_usuarios='".$usuario['id_loja_usuarios']."'";
		
		$padrao_texto_log = 'O usuário <b>['.$usuario['email'].']</b> alterou o(s) seguinte(s) campo(s): ';
		$padrao_texto_log_cel = '<b>#campo#</b> de <b>#de#</b> para <b>#para#</b>. ';
		$padrao_nada = 'NADA';
		
		$campo_nome = "nome"; if($usuario[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($usuario[$campo_nome] ? $usuario[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
		$campo_nome = "ultimo-nome"; $campo_nome_bd = "ultimo_nome"; if($usuario[$campo_nome_bd] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome_bd."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome_bd); $cel = modelo_var_troca($cel,"#de#",($usuario[$campo_nome_bd] ? $usuario[$campo_nome_bd] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
		$campo_nome = "telefone"; if($usuario[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($usuario[$campo_nome] ? $usuario[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
		$campo_nome = "cpf"; if($usuario[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($usuario[$campo_nome] ? $usuario[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
		$campo_nome = "cnpj"; if($usuario[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $cel = $padrao_texto_log_cel; $cel = modelo_var_troca($cel,"#campo#",$campo_nome); $cel = modelo_var_troca($cel,"#de#",($usuario[$campo_nome] ? $usuario[$campo_nome] : $padrao_nada)); $cel = modelo_var_troca($cel,"#para#",($_REQUEST[$campo_nome] ? $_REQUEST[$campo_nome] : $padrao_nada)); $historico .= $cel;}
		
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
			
			$campo_nome = "id_referencia"; $campo_valor = $usuario['id_loja'].'-'.$usuario['id_loja_usuarios']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "grupo"; $campo_valor = 'loja_usuarios'; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "valor"; $campo_valor = $padrao_texto_log . $historico; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data"; $campo_valor = 'NOW()'; 																$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"log"
			);
		}
		
		$_SESSION[$_SYSTEM['ID']."loja_usuarios"]['nome'] = $_REQUEST['nome'];
		$_SESSION[$_SYSTEM['ID']."loja_usuarios"]['ultimo_nome'] = $_REQUEST['ultimo-nome'];
		$_SESSION[$_SYSTEM['ID']."loja_usuarios"]['telefone'] = $_REQUEST['telefone'];
		$_SESSION[$_SYSTEM['ID']."loja_usuarios"]['cpf'] = $_REQUEST['cpf'];
		$_SESSION[$_SYSTEM['ID']."loja_usuarios"]['cnpj'] = $_REQUEST['cnpj'];
		$_SESSION[$_SYSTEM['ID']."loja_usuarios"]['cnpj_selecionado'] = ($_REQUEST['cnpj_selecionado'] == 'sim' ? true : false);
		
		$_ALERTA = 'Seus dados foram atualizados com sucesso!';
		
		if($_SESSION[$_SYSTEM['ID'].'after-update-account-local']){
			$local = $_SESSION[$_SYSTEM['ID'].'after-update-account-local'];
			$_SESSION[$_SYSTEM['ID'].'after-update-account-local'] = false;
			redirecionar($local);
		} else {
			redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/account');
		}
	}
}

function eservice_purchases($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ESERVICES;
	global $_VARIAVEIS_JS;
	global $_ESERVICE;
	global $_B2MAKE_URL;
	global $_B2MAKE_FTP_FILES_PATH;
	
	$_HTML_DADOS['noindexNofollow'] = true;
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Compras.';
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	$loja_usuarios_senha = $_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"];
	
	$status_mudar = $_ESERVICES['status_mudar'];
	$status_mudar_cores = $_ESERVICES['status_mudar_cores'];
	$cel_nome = 'purchases';
	$sem_resultados_titulo = 'Sem compras cadastradas!';
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- purchases < -->','<!-- purchases > -->');
	
	$cel_nome = 'cel-payment'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'cel-voucher'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'cel-order-comum'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	
	$cel_nome = 'cel-payment'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	$cel_nome = 'cel-voucher'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	$cel_nome = 'cel-order-comum'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	if($ajax){
		$pagina = '<!-- orders -->';
	}
	
	if($_REQUEST['page']){
		$page = (int)$_REQUEST['page'];
	}
	if($_REQUEST['limite']){
		$limite = (int)$_REQUEST['limite'];
	} else {
		if(!$limite){
			$limite = (int)$_HTML['MENU_NUM_PAGINAS'];
		}
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
	
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
			$total = 0;
			$id_pedidos = $pedido['t2.id_pedidos'];
			$senha_pedidos = $pedido['t2.senha'];
			
			switch($status){
				case 'N': $layout = $cel['cel-payment']; break;
				case 'A': $layout = $cel['cel-voucher']; break;
				default:
					$layout = $cel['cel-order-comum'];
			}
			
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
			
			$layout = modelo_var_troca($layout,"#order-title#",'PEDIDO - '.$pedido['t2.codigo'].($status == 'A' && $senha_pedidos ? ' '.$senha_pedidos : '').' - '.$data);
			$layout = modelo_var_troca($layout,"#order-id#",$id_pedidos);
			$layout = modelo_var_troca($layout,"#status-texto#",$pedido['t2.status']);
			$layout = modelo_var_troca($layout,"#status-texto-extra#",' style="color:'.$status_cor.';"');
			$layout = modelo_var_troca($layout,"#status-ball-extra#",' style="background-color:'.$status_cor.';"');
			
			if($status == 'F'){
				$layout = modelo_var_troca($layout,"#baixa#",'Protocolo Baixa: '.data_hora_from_datetime_to_text($pedido['t2.data_baixa']).' - '.$pedido['t2.protocolo_baixa'].($pedido['t2.observacao_baixa'] ? '<br>Observação Baixa: '.$pedido['t2.observacao_baixa'] : ''));
			} else {
				$cel_nome = 'cel-baixa'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			}
			
			$cel_nome = 'cel-order'; $cel_order = modelo_tag_val($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
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

				$cel_aux = $cel_order;
				$descricao_extra = '';
				
				$sub_total = (float)$ps['sub_total'];
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#pedido-service-id#",$ps['id_pedidos_servicos']);
				
				$cel_aux = modelo_var_troca($cel_aux,"#imagem#",$pedido_proc[$ps['id_servicos']]['imagem_path']);
				$cel_aux = modelo_var_troca($cel_aux,"#codigo-senha#",($status_servico == 'A' ? $ps['codigo'].($ps['senha'] ? ' '.$ps['senha']:'') : $ps['status']));
				
				if($status_servico != 'A'){
					$cel_nome_2 = 'cel-botoes'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
					$cel_nome_2 = 'cel-acoes'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
					
					$cel_aux = modelo_var_troca($cel_aux,"#service-status-text-extra#",' style="color:'.$status_servico_cor.';"');
					$cel_aux = modelo_var_troca($cel_aux,"#service-status-ball-extra#",' style="background-color:'.$status_servico_cor.';"');
					
					$cel_aux = modelo_var_troca($cel_aux,"#baixa-servico#",'Protocolo Baixa: '.data_hora_from_datetime_to_text($ps['data_baixa']).' - '.$ps['protocolo_baixa'].($ps['observacao_baixa'] ? '<br>Observação Baixa: '.$ps['observacao_baixa'] : ''));
				} else {
					$cel_nome_2 = 'cel-baixa-servico'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
					
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
					
					$qrcode_url = $_B2MAKE_URL.'e-services/'.$_ESERVICE['loja-atual'].'/qrcode/?content='.rawurlencode('#'.($loja_usuarios['versao_voucher'] ? 'V'.$loja_usuarios['versao_voucher']:'').$ps['codigo'].'#'.sha1($senha));
					
					$cel_aux = modelo_var_troca($cel_aux,"#qrcode#",$qrcode_url);
					
					if($ps['presente']){
						$pessoal_extra = '';
						$presente_extra = ' checked="checked"';
						$presente_editar_extra = '';
						$presente_cont_extra = ' data-edit="true"';
						
						if($ps['id_voucher_layouts']){
							$presente_tema_img_nenhum = '';
							
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
							
							$presente_tema_img_extra = ' style="background-image:url(/'.$_SYSTEM['ROOT'].$layout_voucher_proc[$ps['id_voucher_layouts']]['imagem_topo'].');" data-id="'.$ps['id_voucher_layouts'].'"';
						} else {
							$presente_tema_img_nenhum = 'NENHUMA IMAGEM';
							$presente_tema_img_extra = ' data-id="-1"';
						}
					} else {
						$pessoal_extra = ' checked="checked"';
						$presente_extra = '';
						$presente_editar_extra = ' style="display:none;"';
						$presente_cont_extra = '';
						$presente_tema_img_nenhum = 'NENHUMA IMAGEM';
						$presente_tema_img_extra = ' data-id="-1"';
					}
					
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#pessoal-extra#",$pessoal_extra);
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#presente-extra#",$presente_extra);
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#presente-editar-extra#",$presente_editar_extra);
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#presente-cont-extra#",$presente_cont_extra);
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#tema-img-nenhum#",$presente_tema_img_nenhum);
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#tema-img-extra#",$presente_tema_img_extra);
					
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#de#",$ps['de']);
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#para#",$ps['para']);
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#mensagem#",$ps['mensagem']);
				}
				
				switch($status){
					case 'N': break;
					case 'A':
						if($ps['validade_tipo'] == 'D'){
							$descricao_extra .= '<br>Validade: '.data_hora_from_datetime_to_text($ps['validade_data']);
						} else {
							$data_full = $pedido['t2.data'];
							$data_arr = explode(' ',$data_full);
							
							if($ps['validade']){
								$periodo = $ps['validade'];
							} else {
								$periodo = $_ESERVICE['pedido_validade'];
							}
							
							$descricao_extra .= '<br>Validade: '.date("d/m/Y",strtotime($data_arr[0] . " + ".$periodo." day"));
						}
					
					break;
					default:
						
				}
				
				if($pedido_proc[$ps['id_servicos']]['observacao'])$descricao_extra .= '<br>Observação: '.nl2br($pedido_proc[$ps['id_servicos']]['observacao']);
				
				$cel_aux = modelo_var_troca($cel_aux,"#descricao#",
					$pedido_proc[$ps['id_servicos']]['nome'].'<br>'.
					'Preço: '.'R$ '.preparar_float_4_texto(number_format((float)$ps['sub_total'], 2, '.', '')).
					$descricao_extra
				);
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#num#",$count);
				
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
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#identificacao_nome#",$identificacao_nome);
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#identificacao_documento#",$identificacao_documento);
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#identificacao_telefone#",$identificacao_telefone);
				
				$layout = modelo_var_in($layout,'<!-- '.$cel_nome.' -->',$cel_aux);
				
				$total += $sub_total;
				$count++;
			}
			$layout = modelo_var_troca($layout,'<!-- '.$cel_nome.' -->','');
			
			$layout = modelo_var_troca($layout,"#order-total#",'R$ '.preparar_float_4_texto(number_format($total, 2, '.', '')));
			
			$pagina = modelo_var_in($pagina,'<!-- orders -->',$layout);
			
			$cont++;
			if($cont >= $limite){
				break;
			}
		}
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->',$sem_resultados_titulo);
	}
	
	$data_vars = Array(
		'limite' => $limite,
		'opcao' => $cel_nome,
	);
	
	$_VARIAVEIS_JS['mais_resultados'] = json_encode($data_vars);
	
	if($page){
		$page++;
	} else {
		$page = 1;
		$mais_resultados = '
	'.(!$ajax ? '<div class="clear"></div>' : '').'
	<div id="b2make-orders-mais">Mais Resultados</div>';
	}
	
	if($limite >= count($resultado)){
		$sem_mais_resultados = true;
	} else {
		$pagina .= $mais_resultados;
	}
	
	if($ajax){
		return Array(
			'pagina' => $pagina,
			'sem_mais' => $sem_mais_resultados,
		);
	} else {
		if($resultado){
			$modulo = $pagina.'
			<div class="clear"></div>';
		} else {
			$modulo = $sem_resultados_titulo;
		}
	}
	
	return $modulo;
}

function eservice_qrcode(){
	global $_SYSTEM;
	global $_ESERVICE;
	global $_CAMINHO;
	
	//eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/qrcode');
	
	$conteudo = rawurldecode($_REQUEST['content']);
	
	if($conteudo){
		include($_SYSTEM['INCLUDE_PATH']."php/qrlib/qrlib.php");
		
		QRcode::png($conteudo, false, QR_ECLEVEL_H, 4);
	}
	
	exit;
}

function eservice_barcode(){
	global $_SYSTEM;
	global $_ESERVICE;
	global $_CAMINHO;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/barcode');
	
	$data = urldecode($_CAMINHO[3]);
	
	if($data){
		include($_SYSTEM['INCLUDE_PATH']."php/barcode/barcode.php");
		
		$format = 'png';
		$symbology = 'ean-13';
		
		$generator = new barcode_generator();
		
		$generator->output_image($format, $symbology, $data, Array(
			'w' => '290px',
			'h' => '100px',
			'ts' => '3',
			'th' => '15',
		));
	}
	
	exit;
}

function eservice_voucher_layouts($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ESERVICES;
	global $_VARIAVEIS_JS;
	global $_ESERVICE;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/voucher-layouts');
	
	$cel_nome = 'images';
	
	if($ajax){
		$pagina = '<!-- '.$cel_nome.' -->';
	}
	
	if($_REQUEST['page']){
		$page = (int)$_REQUEST['page'];
	}
	if($_REQUEST['limite']){
		$limite = (int)$_REQUEST['limite'];
	} else {
		if(!$limite){
			$limite = (int)$_HTML['MENU_NUM_PAGINAS'];
		}
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_voucher_layouts',
			'imagem_topo',
			'versao',
		))
		,
		"voucher_layouts",
		"WHERE id_loja='".$id_loja."'"
		." ORDER BY id_voucher_layouts DESC"
		." LIMIT ".($page?($page*$limite).',':'') . ($limite + 1)
	);
	
	$count = 0;
	
	if($resultado){
		foreach($resultado as $vl){
			$imagem = '<div class="b2make-voucher-layouts-img" style="background-image:url(/'.$_SYSTEM['ROOT'].$vl['imagem_topo'].'?v='.$vl['versao'].');" data-id="'.$vl['id_voucher_layouts'].'" data-url="/'.$_SYSTEM['ROOT'].$vl['imagem_topo'].'?v='.$vl['versao'].'"></div>';
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$imagem);
			
			$cont++;
			if($cont >= $limite){
				break;
			}
		}
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	}
	
	$data_vars = Array(
		'limite2' => $limite,
		'opcao' => $cel_nome,
	);
	
	$_VARIAVEIS_JS['mais_resultados'] = json_encode($data_vars);
	
	if($page){
		$page++;
	} else {
		$page = 1;
		$mais_resultados = '
	'.(!$ajax ? '<div class="clear"></div>' : '').'
	<div id="b2make-voucher-layouts-mais">Mais Resultados</div>';
	}
	
	if($limite >= count($resultado)){
		$sem_mais_resultados = true;
	} else {
		$pagina .= $mais_resultados;
	}
	
	if($ajax){
		return Array(
			'pagina' => $pagina,
			'sem_mais' => $sem_mais_resultados,
		);
	} else {
		if($resultado){
			$modulo = $pagina.'
			<div class="clear"></div>';
		} else {
			$modulo = $sem_resultados_titulo;
		}
	}
	
	return $modulo;
}

function eservice_voucher_presente($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ESERVICES;
	global $_VARIAVEIS_JS;
	global $_ESERVICE;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/voucher-presente');
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	$pedido_id = $_REQUEST['pedido_id'];
	$pedido_servico_id = $_REQUEST['pedido_servico_id'];
	$tipo = $_REQUEST['tipo'];
	
	if($pedido_id && $tipo){
		$loja_usuarios_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
			." AND id_pedidos='".$pedido_id."'"
		);
		
		if($loja_usuarios_pedidos){
			banco_update
			(
				"presente=".($tipo == 'presente' ? '1' : 'NULL'),
				"pedidos_servicos",
				"WHERE id_pedidos='".$pedido_id."'"
				." AND id_pedidos_servicos='".$pedido_servico_id."'"
			);
		}
	}
	
	return Array(
		'status' => 'Ok',
	);
}

function eservice_voucher_dados_salvar($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_CAMINHO;
	global $_CONTEUDO_ID_AUX;
	global $_MODULO_EXTERNO;
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ESERVICES;
	global $_VARIAVEIS_JS;
	global $_ESERVICE;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/voucher-dados-salvar');
	
	$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
	
	$pedido_id = $_REQUEST['pedido_id'];
	$pedido_servico_id = $_REQUEST['pedido_servico_id'];
	
	$de = $_REQUEST['de'];
	$para = $_REQUEST['para'];
	$mensagem = $_REQUEST['mensagem'];
	$img = $_REQUEST['img'];
	
	if($pedido_id && $pedido_servico_id){
		$loja_usuarios_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
			." AND id_pedidos='".$pedido_id."'"
		);
		
		if($loja_usuarios_pedidos){
			banco_update
			(
				"de=".($de ? "'".$de."'" : 'NULL').",".
				"para=".($para ? "'".$para."'" : 'NULL').",".
				"mensagem=".($mensagem ? "'".$mensagem."'" : 'NULL').",".
				"id_voucher_layouts=".($img != '-1' ? $img : 'NULL'),
				"pedidos_servicos",
				"WHERE id_pedidos='".$pedido_id."'"
				." AND id_pedidos_servicos='".$pedido_servico_id."'"
			);
		}
	}
	
	return Array(
		'status' => 'Ok',
	);
}

function eservice_voucher($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_SYSTEM;
	global $_ESERVICE;
	global $_B2MAKE_URL;
	global $_B2MAKE_FTP_FILES_PATH;
	
	if($pedido_id){
		$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
		$loja_usuarios_senha = $_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"];
		
		if($id_loja){
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
		} else {
			$loja_atual = $_ESERVICE['loja-atual'];
			$loja_atual_logomarca = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-logomarca"];
			$loja_atual_dados = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"];
		}
		
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
					
					$imagem_path = $_B2MAKE_URL . 'design/images/b2make-album-sem-imagem.png';
					if($servicos[0]['imagem_biblioteca']){
						if($servicos[0]['imagem_biblioteca_id']){
							$host = banco_select_name
							(
								banco_campos_virgulas(Array(
									't1.https',
									't1.url',
									't1.dominio_proprio',
								))
								,
								"host as t1,loja as t2",
								"WHERE t2.id_loja='".($id_loja ? $id_loja : $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"])."'"
								." AND t1.id_usuario=t2.id_usuario"
							);
							
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
							$imagem_path = http_define_ssl(($host[0]['t1.dominio_proprio'] ? 'http://'.$host[0]['t1.dominio_proprio'].'/' : $host[0]['t1.url']),$host[0]['t1.https']) . $_B2MAKE_FTP_FILES_PATH . '/' . $_SYSTEM['SITE']['ftp-files-services-path'] . '/' . $servicos_biblioteca_imagens[0]['file'] . '?v='. $servicos[0]['versao'];
						}
					} else {
						if($servicos[0]['imagem_path'])$imagem_path = $_B2MAKE_URL . $servicos[0]['imagem_path'] . '?v='. $servicos[0]['versao'];
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
								$presente_img = ' style="background-image:url('.$_B2MAKE_URL.$voucher_layouts[0]['imagem_topo'].');"';
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
						$descricao_extra .= '<br>Validade: '.data_hora_from_datetime_to_text($ps['validade_data']);
					} else {
						$data_full = $pedidos[0]['data'];
						$data_arr = explode(' ',$data_full);
						
						if($ps['validade']){
							$periodo = $ps['validade'];
						} else {
							$periodo = $_ESERVICE['pedido_validade'];
						}
						
						$descricao_extra .= '<br>Validade: '.date("d/m/Y",strtotime($data_arr[0] . " + ".$periodo." day"));
					}
					
					$descricao_extra .= '<br>Observação: '.nl2br($servicos[0]['observacao']);
					
					$voucher = modelo_var_troca($voucher,"#service-desc#",
						'<p>'.$ps['codigo'].' '.$ps['senha'].'</p>'.
						$servicos[0]['nome'].'<br>'.
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
					
					$qrcode = $_B2MAKE_URL.'e-services/'.$loja_atual.'/qrcode/?content='.rawurlencode('#'.($loja_usuarios['versao_voucher'] ? 'V'.$loja_usuarios['versao_voucher']:'').$ps['codigo'].'#'.sha1($senha));
					
					if($mail){
						$voucher = modelo_var_troca($voucher,"#qrcode#",'cid:qrcode_img'.$count);
						
						$tmp_image = $_SYSTEM['TMP'].'imagem-tmp'.session_id().'-'.$count.'.png';
						$img = file_get_contents($qrcode);
						file_put_contents($tmp_image, $img);

						$mail_imgs[] = array(
							'cid' => 'qrcode_img'.$count,
							'src' => $tmp_image,
							'temp' => $tmp_image,
							'name' => 'QRCode Imagem',
						);
					} else {
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
					$titulo = $dados_loja['nome'].($ajax || $mail ? ' - Visualizar Voucher - ' : ' - Impressão Voucher - ').$ps['codigo'].' - '.$servicos[0]['nome'];
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

function eservice_voucher_print(){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_ESERVICE;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/voucher-print');
	
	$pedido_id = $_CAMINHO[3];
	$pedido_servico_id = $_CAMINHO[4];
	
	$voucher = eservice_voucher(Array(
		'pedido_id' => $pedido_id,
		'pedido_servico_id' => $pedido_servico_id,
	));
	
	$_SESSION[$_SYSTEM['ID']."versao-impressao"] = $voucher['voucher'];
	$_SESSION[$_SYSTEM['ID']."versao-impressao-titulo"] = $voucher['titulo'];
	
	if($_ESERVICE['iframe'])$_ESERVICE['nao-redirecionar'] = true;
	
	redirecionar('includes/eservices/print.php');
	exit;
}

function eservice_voucher_view($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_SYSTEM;
	global $_ESERVICE;
	
	eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/voucher-view');
	
	$pedido_id = $_REQUEST['pedido_id'];
	$pedido_servico_id = $_REQUEST['pedido_servico_id'];
	
	$voucher = eservice_voucher(Array(
		'pedido_id' => $pedido_id,
		'pedido_servico_id' => $pedido_servico_id,
		'ajax' => $ajax,
	));
	
	return $voucher;
}

function eservice_voucher_page(){
	global $_ESERVICE;
	
	redirecionar('e-services/'.$_ESERVICE['loja-atual'].'/purchases');
}

function eservice_logar(){
	global $_SYSTEM;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_HTML;
	global $_LOCAL_ID;
	global $_MENSAGEM_ERRO;
	global $_REMOTE_ADDR;
	global $_DESATIVAR_PADRAO;
	global $_OPCAO;
	global $_REDIRECT_PAGE;
	global $_ECOMMERCE;
	global $_PROJETO;
	global $_LOGAR_REDIRECT_LOGIN;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if(!$_DESATIVAR_PADRAO['logar']){
		$usuario	=	$_REQUEST["usuario"];
		$senha		=	$_REQUEST["senha"];
		
		banco_conectar();
		
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
			"WHERE ip='".$_REMOTE_ADDR."'"
		);
		
		if($bad_list[0]['num_tentativas_login'] < $_SYSTEM['LOGIN_MAX_TENTATIVAS'] - 1){
			$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
			
			$loja_usuarios = banco_select_name(
				"*",
				"loja_usuarios",
				"WHERE email='".$usuario."' AND status!='D'"
				." AND id_loja='".$id_loja."'"
			);
			
			if(!$bad_list){
				$numero_tentativas = 1;
				
				$campos = null;
				
				$campo_nome = "ip"; $campo_valor = $_REMOTE_ADDR; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "num_tentativas_login"; $campo_valor = 1; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "data_primeira_tentativa"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				banco_insert_name
				(
					$campos,
					"bad_list"
				);
			} else {
				$numero_tentativas = ($bad_list[0]['num_tentativas_login'] + 1);
				
				$campo_tabela = "tabela";
				
				$campo_nome = "num_tentativas_login"; $campo_valor = $numero_tentativas; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";

				$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
				
				if($editar_sql[$campo_tabela]){
					banco_update
					(
						$editar_sql[$campo_tabela],
						"bad_list",
						"WHERE ip='".$_REMOTE_ADDR."'"
					);
				}
				$editar = false;$editar_sql = false;
			}
			
			$_MENSAGEM_ERRO = 'Você pode tentar mais <b>'.($_SYSTEM['LOGIN_MAX_TENTATIVAS'] - $numero_tentativas).'</b> vezes antes que sua conta seja bloqueada por '.floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60).' minutos(s).';
			$_OPCAO = 'login';
			//$pagina = login();
			
			if($_LOGAR_REDIRECT_LOGIN)$redirect_login = $_LOGAR_REDIRECT_LOGIN; else $redirect_login = 'autenticar';
			
			if($loja_usuarios){
				if(crypt($senha, $loja_usuarios[0]['senha']) == $loja_usuarios[0]['senha']){
					if($loja_usuarios[0]['status'] != "A"){
						seguranca_delay();
						alerta(3);
					} else {
						$senha_sessao = sha1(crypt($loja_usuarios[0]['senha']).mt_rand());
						$loja_usuarios[0]['senha_sessao'] = $senha_sessao;
						
						banco_update
						(
							"senha_sessao='".$senha_sessao."',".
							"data_login=NOW()",
							"loja_usuarios",
							"WHERE email='".$usuario."'"
							." AND id_loja='".$id_loja."'"
						);
						banco_delete
						(
							"bad_list",
							"WHERE ip='".$_REMOTE_ADDR."'"
						);
						
						$_SESSION[$_SYSTEM['ID']."loja_usuarios"] = $loja_usuarios[0];
						$_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"] = $senha;
						
						$_SESSION[$_SYSTEM['ID']."loja-permissao"] = true;
						
						// ============================== Mudar Local Padrão de logar
						
						if($_SESSION[$_SYSTEM['ID'].'loja-logar-local']){
							$local = $_SESSION[$_SYSTEM['ID'].'loja-logar-local'];
							$_SESSION[$_SYSTEM['ID'].'loja-logar-local'] = false;
						} else {
							$local = 'e-services/'.$_ESERVICE['loja-atual'].'/'.$_PROJETO['e-services']['permissao_local_inicial'];
						}
						
						$_REDIRECT_PAGE = true;
						redirecionar($local);
					}
				} else {
					seguranca_delay();
					alerta(6);
					redirecionar($redirect_login);
				}
			} else {
				seguranca_delay();
				alerta(2);
				redirecionar($redirect_login);
			}
		} else {
			seguranca_delay();
			$_MENSAGEM_ERRO = 'Você atingiu a quantidade limite de tentativas de login nesse período. Por motivos de segurança você deve aguardar '.floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60).' minutos(s) antes de tentar novamente. Qualquer dúvida entre em contato pelo e-mail: '.$_SYSTEM['ADMIN_EMAIL_HTML'].'.';
			alerta(4);
			redirecionar($redirect_login);
		}
		
		banco_fechar_conexao();
		
		if($_REQUEST['ecommerce']){
			redirecionar($redirect_login);
		}
		
		return $pagina;
	}
}

function eservice_testes(){
	global $_SYSTEM;
	global $_VARS;
	global $_DEBUG;
	
	$opt = 0;
	
	switch($opt){
		case 1:
			$_DEBUG = true;
			
			echo 'Entrou';
			
			$pedidos[0]['codigo'] = 'E1124';
			
			$email_nome = 'Fulano';
			//$email = 'otavio@b2make.com';
			//$email = 'pedro@b2make.com';
			//$email = 'miguel@b2make.com';
			$email = 'otavioserra@gmail.com';
			
			$pedido_id = '124';
			//$pedido_servico_id = '268';
			
			$voucher = eservice_voucher(Array(
				'pedido_id' => $pedido_id,
				'pedido_servico_id' => $pedido_servico_id,
				'mail' => true,
			));
			
			$loja_nome = 'Teste';
			
			$assunto = $_VARS['ecommerce']['voucher_email_assunto'];
			$mensagem = $_VARS['ecommerce']['voucher_email_mensagem'];
			
			$assunto = modelo_var_troca($assunto,"#codigo#",$pedidos[0]['codigo']);
			$mensagem = $voucher['voucher'];
			$embedded_imgs = $voucher['mail_imgs'];
			
			$mensagem = modelo_var_troca($mensagem,"#html-title#",$pedidos[0]['codigo']);
			
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

			exit;
		break;
		case 2:
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
			$voucher = modelo_tag_val($modelo,'<!-- voucher-2 < -->','<!-- voucher-2 > -->');
			
			return $voucher;
		break;
		case 3:
			return '<div class="b2make-payment-cont">
				<div id="b2make-payment-teste">Teste</div>
			</div>';
		break;
		
	}
}

function eservice_operacao(){
	global $_SYSTEM;
	global $_VARS;
	global $_DEBUG;
	
	$opt = 0;
	
	switch($opt){
		case 1:
			return print_r(eservice_paypal_reference_comissao_pontual(Array(
				'valor_comissao' => 0.0,
				'id_loja' => '0',
				'referente_a' => '',
				'codigo' => 'CP002',
			)),true);
		break;
	}
}

// Funções Locais

function eservices_ajax(){
	global $_OPCAO;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_VARS;
	global $_PROJETO;
	global $_CAMINHO;
	global $_ESERVICE;
	global $_HTML;
	
	$opcao = $_CAMINHO[2];
	
	if($opcao == 'orders'){
		$saida = eservice_orders(Array(
			'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'voucher-gift'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/');
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
		
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
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> mudou status de presente para '.($_REQUEST['flag'] == '1' ? 'Sim' : 'Não'),
			));
		}
		
		$saida = json_encode(Array('path'=>'voucher-gift'));
	}
	
	if($opcao == 'voucher-orders'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/');
		
		if($_REQUEST['id']){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
			if($_REQUEST['voucher']){
				$loja_usuarios_pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos',
					))
					,
					"loja_usuarios_pedidos",
					"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
					." AND id_pedidos='".$_REQUEST['id']."'"
				);
				
				if($loja_usuarios_pedidos){
					banco_update
					(
						"voucher_por_servico=" . ($_REQUEST['voucher_opcao'] == '1' ? "'1'" : 'NULL'),
						"pedidos",
						"WHERE id_pedidos='".$_REQUEST['id']."'"
					);
				}
			} else if($_REQUEST['servico']){
				$loja_usuarios_pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos',
					))
					,
					"loja_usuarios_pedidos",
					"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
					." AND id_pedidos='".$_REQUEST['id']."'"
				);
				
				if($loja_usuarios_pedidos){
					banco_update
					(
						"voucher_por_servico=NULL",
						"pedidos_servicos",
						"WHERE id_pedidos='".$_REQUEST['id']."'"
					);
					banco_update
					(
						"voucher_por_servico='1'",
						"pedidos_servicos",
						"WHERE id_pedidos='".$_REQUEST['id']."'"
						." AND codigo='".$_REQUEST['servico_opcao']."'"
					);
				}
			} else {
				$pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'status',
					))
					,
					"pedidos",
					"WHERE id_pedidos='".$_REQUEST['id']."'"
				);
				
				if($pedidos[0]['status'] == 'A') $_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-pago"] = true; else $_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-pago"] = false;
				
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
					." AND id_pedidos='".$_REQUEST['id']."'"
				);
			}
			
			$saida = json_encode(Array('path'=>'voucher-orders'));
		}
	}
	
	if($opcao == 'pay-orders'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/');
		
		if($_REQUEST['id']){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
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
				." AND id_pedidos='".$_REQUEST['id']."'"
			);
			
			$loja_usuarios_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"loja_usuarios_pedidos",
				"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
				." AND id_pedidos='".$_REQUEST['id']."'"
			);
			
			if($loja_usuarios_pedidos){
				$pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'status',
					))
					,
					"pedidos",
					"WHERE id_pedidos='".$_REQUEST['id']."'"
				);
				
				if($pedidos[0]['status'] == 'A') $_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-pago"] = true; else $_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-pago"] = false;
				
				if($loja_online_produtos){
					$pedidos_produtos_banco = banco_select_name
					(
						banco_campos_virgulas(Array(
							't1.sub_total',
							't2.nome',
							't2.id_produtos',
						))
						,
						"pedidos_produtos as t1,produtos as t2",
						"WHERE t1.id_pedidos='".$_REQUEST['id']."'"
						." AND t1.id_produtos=t2.id_produtos"
					);
					
					if($pedidos_produtos_banco)
					foreach($pedidos_produtos_banco as $pp){
						$flag = false;
						$flag2 = false;
						if($pedidos_produtos){
							foreach($pedidos_produtos as $key => $pp2){
								if($pp2['id'] == $pp['t2.id_produtos']){
									$pedidos_produtos[$key]['quant']++;
									$flag2 = true;
									break;
								}
							}
							
							if(!$flag2){
								$flag = true;
							}
						} else {
							$flag = true;
						}
						
						if($flag){
							$pedidos_produtos[] = Array(
								'id' => $pp['t2.id_produtos'],
								'preco' => $pp['t1.sub_total'],
								'quant' => 1,
								'titulo' => $pp['t2.nome'],
								'href' => '',
							);
						}
					}
					
					$saida = json_encode($pedidos_produtos);
				} else {
					$pedidos_servicos_banco = banco_select_name
					(
						banco_campos_virgulas(Array(
							't1.sub_total',
							't2.nome',
							't2.id_servicos',
						))
						,
						"pedidos_servicos as t1,servicos as t2",
						"WHERE t1.id_pedidos='".$_REQUEST['id']."'"
						." AND t1.id_servicos=t2.id_servicos"
					);
					
					if($pedidos_servicos_banco)
					foreach($pedidos_servicos_banco as $ps){
						$flag = false;
						$flag2 = false;
						if($pedidos_servicos){
							foreach($pedidos_servicos as $key => $ps2){
								if($ps2['id'] == $ps['t2.id_servicos']){
									$pedidos_servicos[$key]['quant']++;
									$flag2 = true;
									break;
								}
							}
							
							if(!$flag2){
								$flag = true;
							}
						} else {
							$flag = true;
						}
						
						if($flag){
							$pedidos_servicos[] = Array(
								'id' => $ps['t2.id_servicos'],
								'preco' => $ps['t1.sub_total'],
								'quant' => 1,
								'titulo' => $ps['t2.nome'],
								'href' => '',
							);
						}
					}
					
					$saida = json_encode($pedidos_servicos);
				}
			}
		}
	}
	
	if($opcao == 'voucher-temas'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/');
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
		
		$loja_usuarios_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
			." AND id_pedidos='".$_REQUEST['id_pedidos']."'"
		);
		
		if($loja_usuarios_pedidos){
			if($_REQUEST['id']){
				banco_update
				(
					"id_voucher_layouts='".$_REQUEST['id']."'",
					"pedidos",
					"WHERE id_pedidos='".$_REQUEST['id_pedidos']."'"
				);
			}
			
			log_banco(Array(
				'id_referencia' => $_REQUEST['id_pedidos'],
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> mudou tema para id_voucher_layouts='.$_REQUEST['id'],
			));
		}
		
		$saida = json_encode(Array('path'=>'voucher-temas'));
	}
	
	if($opcao == 'voucher-send-mail'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/');
		
		$voucher = $_REQUEST['voucher'];
		$email = $_REQUEST['email'];
		$id_pedidos = $_REQUEST['id_pedidos'];
		$flag = false;
		
		if($voucher && $email){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
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
						'codigo',
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
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
					}
					
					if(!$flag){
						banco_update
						(
							"envios_email=envios_email+1",
							"pedidos",
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
						
						$loja = banco_select_name
						(
							banco_campos_virgulas(Array(
								'nome',
								'email_assinatura',
							))
							,
							"loja",
							"WHERE id_loja='".$pedidos[0]['id_loja']."'"
						);
						
						$loja_nome = $loja[0]['nome'];
						$loja_email_assinatura = $loja[0]['email_assinatura'];
						
						$loja_email_assinatura = modelo_var_troca_tudo($loja_email_assinatura,"#loja-nome#",($loja_nome?$loja_nome:$_HTML['TITULO']));
						
						$assunto = $_VARS['ecommerce']['voucher_email_assunto'];
						$mensagem = $_VARS['ecommerce']['voucher_email_mensagem'];
						
						$voucher = $voucher;
						$voucher = preg_replace('/\\\"/i', '"', $voucher);
						$voucher = preg_replace("/\\\'/i", "'", $voucher);
						
						//$voucher = eservice_remover_css($voucher);
						
						$assunto = modelo_var_troca($assunto,"#codigo#",$pedidos[0]['codigo']);
						$mensagem .= $voucher;
						$mensagem .= $loja_email_assinatura;
						
						email_enviar(Array(
							'from_name' => $loja_nome,
							'email_nome' => $email_nome,
							'email' => $email,
							'assunto' => $assunto,
							'mensagem' => $mensagem,
							'nao_inserir_assinatura' => true,
						));
						
						$saida = Array(
							'ok' => true,
						);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> enviou voucher para o seguinte email -> '.$email,
						));
					} else {
						$saida = Array(
							'erro' => '<p>Você já enviou mais de 10 emails para esse pedido</p><p>Não é permitido enviar mais emails para esse pedido.</p>',
						);
					}
				} else {
					$saida = Array(
						'erro' => '<p>Não é permitido enviar emails de pedidos inativos no sistema.</p>',
					);
				}
			} else {
				$saida = Array(
					'erro' => '<p>Não é possível enviar esse email!</p><p>Esse pedido não pertence ao seu usuário.</p>',
				);
			}
		} else {
			$saida = Array(
				'erro' => '<p>Não é possível enviar esse email!</p><p>O email e/ou o voucher não está definido.</p>',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'voucher-complete'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/');
		
		$voucher = $_REQUEST['voucher'];
		$id_pedidos = $_REQUEST['id_pedidos'];
		$flag = false;
		
		if($voucher){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
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
				$email = $loja_usuarios['email'];
				$email_nome = $loja_usuarios['nome'];
				
				$pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'envios_email',
						'codigo',
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
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
					}
					
					if(!$flag){
						banco_update
						(
							"envios_email=envios_email+1",
							"pedidos",
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
						
						$assunto = $_VARS['ecommerce']['concluir_email_assunto'];
						$mensagem = $_VARS['ecommerce']['concluir_email_mensagem'];
						
						$voucher = $voucher;
						$voucher = preg_replace('/\\\"/i', '"', $voucher);
						$voucher = preg_replace("/\\\'/i", "'", $voucher);
						
						//$voucher = eservice_remover_css($voucher);
						
						$assunto = modelo_var_troca($assunto,"#codigo#",$pedidos[0]['codigo']);
						$mensagem .= $voucher;
						
						email_enviar(Array(
							'email_nome' => $email_nome,
							'email' => $email,
							'assunto' => $assunto,
							'mensagem' => $mensagem,
						));
						
						$saida = Array(
							'ok' => true,
						);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> clicou em concluir e enviou email -> '.$email,
						));
					} else {
						$saida = Array(
							'erro' => '<p>Você já enviou mais de 10 emails para esse pedido</p><p>Não é permitido enviar mais emails para esse pedido.</p>',
						);
					}
				} else {
					$saida = Array(
						'erro' => '<p>Não é permitido enviar emails de pedidos inativos no sistema.</p>',
					);
				}
			} else {
				$saida = Array(
					'erro' => '<p>Não é possível enviar esse email!</p><p>Esse pedido não pertence ao seu usuário.</p>',
				);
			}
		} else {
			$saida = Array(
				'erro' => '<p>Não é possível enviar esse email!</p><p>O voucher não está definido.</p>',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_OPCAO == 'payment-process'){
		if((int)$_REQUEST['count'] > 6) return $saida = json_encode(Array('status' => 'Ok'));
		
		$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
		
		if(!$loja_usuarios['id_loja_usuarios'])return;
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
			." AND pedido_atual IS TRUE"
		);
		
		$id_pedidos = $resultado[0]['id_pedidos'];
		
		$count = 0;
		
		while(true){
			$count++;
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'status',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
			);
			
			if($resultado[0]['status'] == 'A'){
				break;
			} else {
				if($count >= 5){
					$not_found = true;
					break;
				} else {
					sleep(1);
				}
			}
		}
		
		if($not_found){
			$saida = Array(
				'status' => 'Ok',
				'continuar' => 'Ok'
			);
		} else {
			$saida = Array(
				'status' => 'Ok'
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_OPCAO == 'ppplus-pay'){
		$saida = json_encode(eservice_paypal_plus_pay());
	}
	
	if($opcao == 'ppplus-other-buyer'){
		$saida = json_encode(eservice_paypal_plus_attempt_pay(Array('outro_pagador' => true)));
	}
	
	if($opcao == 'paypal-button-create-pay'){
		$saida = json_encode(eservice_paypal_button_attempt_pay());
		
		echo $saida;
		exit;
	}
	
	if($opcao == 'paypal-button-execute-pay'){
		$saida = json_encode(eservice_paypal_plus_pay());
		
		echo $saida;
		exit;
	}
	
	if($opcao == 'carrinho-quantidade'){
		$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
		
		$id_servicos = $_REQUEST['id'];
		$quantidade = $_REQUEST['quantidade'];
		
		$_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"][$id_servicos]['quantidade'] = $quantidade;
		
		$saida = Array(
			'status' => 'Ok'
		);
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'carrinho-excluir'){
		$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
		$id = $_REQUEST['id'];
		
		$carrinho = $_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"];
		
		if($carrinho)
		foreach($carrinho as $id_servico => $servico){
			if($id != $id_servico){
				$carrinho_novo[] = $servico;
			}
		}
		
		$_SESSION[$_SYSTEM['ID'].$id_loja."carrinho"] = $carrinho_novo;
		
		$disponibilidade = $_SESSION[$_SYSTEM['ID'].$id_loja."disponibilidade"];
		
		if($disponibilidade)
		foreach($disponibilidade as $id_servico => $servico){
			if($id != $id_servico){
				$disponibilidade_novo[] = $servico;
			}
		}
		
		$_SESSION[$_SYSTEM['ID'].$id_loja."disponibilidade"] = $disponibilidade_novo;
		
		$saida = Array(
			'status' => 'Ok',
		);
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'purchases'){
		$saida = eservice_purchases(Array(
			'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'voucher-layouts'){
		$saida = eservice_voucher_layouts(Array(
			'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'voucher-presente'){
		$saida = eservice_voucher_presente(Array(
			//'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'voucher-dados-salvar'){
		$saida = eservice_voucher_dados_salvar(Array(
			//'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'voucher-view'){
		$saida = eservice_voucher_view(Array(
			'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'purchase-pay'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/');
		
		if($_REQUEST['id']){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
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
				." AND id_pedidos='".$_REQUEST['id']."'"
			);
			
			$saida = json_encode(Array(
				'status' => 'Ok',
			));
		}
	}
	
	if($opcao == 'purchase-voucher-send-mail'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/purchase-voucher-send-mail');
		
		$email = $_REQUEST['email'];
		$pedido_id = $_REQUEST['pedido_id'];
		$pedido_servico_id = $_REQUEST['pedido_servico_id'];
		
		$id_pedidos = $pedido_id;
		$flag = false;
		
		if($email){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
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
						
						$loja = banco_select_name
						(
							banco_campos_virgulas(Array(
								'nome',
								'email_assinatura',
							))
							,
							"loja",
							"WHERE id_loja='".$pedidos[0]['id_loja']."'"
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
							." AND id_pedidos_servicos='".$pedido_servico_id."'"
							." AND status='A'"
						);
						
						if($pedidos_servicos){
							$voucher = eservice_voucher(Array(
								'pedido_id' => $pedido_id,
								'pedido_servico_id' => $pedido_servico_id,
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
							
							$saida = Array(
								'status' => 'Ok',
							);
							
							log_banco(Array(
								'id_referencia' => $id_pedidos,
								'grupo' => 'pedidos',
								'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> enviou voucher do serviço de código ['.$pedidos_servicos[0]['codigo'].'] para o seguinte email -> '.$email,
							));
						} else {
							$saida = Array(
								'erro' => '<p>Não é permitido enviar emails de serviços inativos no sistema.</p>',
							);
						}
					} else {
						$saida = Array(
							'erro' => '<p>Você já enviou mais de 10 emails para esse pedido</p><p>Não é permitido enviar mais emails para esse pedido.</p>',
						);
					}
				} else {
					$saida = Array(
						'erro' => '<p>Não é permitido enviar emails de pedidos inativos no sistema.</p>',
					);
				}
			} else {
				$saida = Array(
					'erro' => '<p>Não é possível enviar esse email!</p><p>Esse pedido não pertence ao seu usuário.</p>',
				);
			}
		} else {
			$saida = Array(
				'erro' => '<p>Não é possível enviar esse email!</p><p>O email e/ou o voucher não está definido.</p>',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'voucher-print'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/voucher-print');
	
		$pedido_id = $_REQUEST['pedido_id'];
		$pedido_servico_id = $_REQUEST['pedido_servico_id'];
		
		if($pedido_id && $pedido_servico_id){
			$voucher = eservice_voucher(Array(
				'pedido_id' => $pedido_id,
				'pedido_servico_id' => $pedido_servico_id,
			));
			
			$_SESSION[$_SYSTEM['ID']."versao-impressao"] = $voucher['voucher'];
			$_SESSION[$_SYSTEM['ID']."versao-impressao-titulo"] = $voucher['titulo'];
			
			if($_ESERVICE['iframe'])$_ESERVICE['nao-redirecionar'] = true;
			
			$saida = Array(
				'status' => 'Ok',
			);
		} else {
			$saida = Array(
				'status' => 'MissData',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'purchase-voucher-identification-change'){
		eservice_permissao('e-services/'.$_ESERVICE['loja-atual'].'/purchase-voucher-identification-change');
		
		$identificacao_nome = $_REQUEST['identificacao_nome'];
		$identificacao_documento = $_REQUEST['identificacao_documento'];
		$identificacao_telefone = $_REQUEST['identificacao_telefone'];
		$pedido_id = $_REQUEST['pedido_id'];
		$pedido_servico_id = $_REQUEST['pedido_servico_id'];
		
		$id_pedidos = $pedido_id;
		$flag = false;
		
		if($identificacao_nome && $identificacao_documento && $identificacao_telefone){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
			
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
						." AND id_pedidos_servicos='".$pedido_servico_id."'"
						." AND status='A'"
					);
					
					if($pedidos_servicos){
						$campo_tabela = "pedidos_servicos";
						$campo_tabela_extra = "WHERE id_pedidos='".$id_pedidos."'"
						." AND id_pedidos_servicos='".$pedido_servico_id."'";
						
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
						
						$saida = Array(
							'status' => 'Ok',
						);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>Usuário: '.($loja_usuarios['nome']?$loja_usuarios['nome'].' '.$loja_usuarios['ultimo_nome'].'['.$loja_usuarios['email'].']':$loja_usuarios['email']).'</b> alterou a identificação do voucher do serviço de código ['.$pedidos_servicos[0]['codigo'].'] para nome -> '.$identificacao_nome.', documento -> '.$identificacao_documento.', telefone -> '.$identificacao_telefone,
						));
					} else {
						$saida = Array(
							'erro' => '<p>Não é permitido alterar a identificação de serviços inativos no sistema.</p>',
						);
					}
				} else {
					$saida = Array(
						'erro' => '<p>Não é permitido alterar a identificação de pedidos inativos no sistema.</p>',
					);
				}
			} else {
				$saida = Array(
					'erro' => '<p>Não é possível alterar a identificação!</p><p>Esse pedido não pertence ao seu usuário.</p>',
				);
			}
		} else {
			$saida = Array(
				'erro' => '<p>Não é possível alterar a identificação!</p><p>Defina o nome, documento e telefone depois clique em SALVAR novamente.</p>',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($opcao == 'email-verificar'){
		seguranca_delay();
		
		$id_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"];
		
		banco_conectar();
		$resultado = banco_select
		(
			'id_loja_usuarios',
			'loja_usuarios',
			"WHERE email='" . strtolower($_REQUEST['email_usuario']) . "' AND status!='D'"
			." AND id_loja='".$id_loja."'"
		);
		
		banco_fechar_conexao();

		if($resultado){
			$saida = Array(
				'status' => 'EmUso',
			);
		} else {
			$saida = Array(
				'status' => 'Ok',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	return $saida;
}

function eservices_xml(){

}

function eservices_main(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_HTML;
	global $_OPCAO_ANTERIOR;
	global $_ID_ANTERIOR;
	global $_OPCAO;
	global $_CAMINHO;
	global $_AJAX_PAGE;
	global $_DESATIVAR_PADRAO;
	global $_VARIAVEIS_JS;
	global $_PROJETO;
	global $_ESERVICE;
	
	if(!$_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-inicializar"]){
		$_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-inicializar"] = true;
		
		$loja_usuarios = $_SESSION[$_SYSTEM['ID']."loja_usuarios"];
		
		$loja_usuarios_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"loja_usuarios_pedidos",
			"WHERE id_loja_usuarios='".$loja_usuarios['id_loja_usuarios']."'"
			." AND pedido_atual IS TRUE"
		);
		
		if($loja_usuarios_pedidos){
			$pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'status',
				))
				,
				"pedidos",
				"WHERE id_pedidos='".$loja_usuarios_pedidos[0]['id_pedidos']."'"
			);
			
			if($pedidos[0]['status'] == 'A') $_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-pago"] = true; else $_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-pago"] = false;
		}
	}
	
	if($_SESSION[$_SYSTEM['ID']."b2make-eservices-pedido-atual-pago"]) $_VARIAVEIS_JS['b2make_eservices_pedido_atual_pago'] = true;
	
	if($_REQUEST[xml])				$xml = $_REQUEST[xml];
	if($_REQUEST[ajax])				$ajax = $_REQUEST[ajax];
	
	switch($_CAMINHO[1]){
		case 'pre-checkout':					return eservice_pre_checkout(); break;
		case 'ppplus-webhooks':					return eservice_ppplus_webhooks(); break;
		case 'operacao':						return eservice_operacao(); break;
	}
	
	switch($_CAMINHO[2]){
		case 'paypal-button-create-pay':		$ajax = 'sim'; break;
		case 'paypal-button-execute-pay':		$ajax = 'sim'; break;
	}
	
	if($_CAMINHO[1]){
		$_ESERVICE['loja-atual'] = $_CAMINHO[1];
		$opcao = $_CAMINHO[2];
		
		if(!$_SESSION[$_SYSTEM['ID']."b2make-loja-atual"]){
			$loja_inicializar = true;
		} else if($_SESSION[$_SYSTEM['ID']."b2make-loja-atual"] != $_ESERVICE['loja-atual']){
			$loja_inicializar = true;
		}
		
		if($loja_inicializar){
			$_SESSION[$_SYSTEM['ID']."b2make-loja-atual"] = false;
			$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"] = false;
			$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-usuario-id"] = false;
			$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-logomarca"] = false;
			$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-versao"] = false;
			
			$loja = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_loja',
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
					'loja_url_cliente',
				))
				,
				"loja",
				"WHERE id='".$_ESERVICE['loja-atual']."'"
				." AND status='A'"
			);
			
			if($loja){
				$host = banco_select_name
				(
					banco_campos_virgulas(Array(
						'url',
					))
					,
					"host",
					"WHERE id_usuario='".$loja[0]['id_usuario']."'"
				);
				
				if(!$loja[0]['url_continuar_comprando']){
					$loja[0]['url_continuar_comprando'] = $host[0]['url'];
				}
				
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual"] = $_ESERVICE['loja-atual'];
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-id"] = $loja[0]['id_loja'];
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-usuario-id"] = $loja[0]['id_usuario'];
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-logomarca"] = $loja[0]['logomarca'];
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-versao"] = $loja[0]['versao'];
				$_SESSION[$_SYSTEM['ID']."b2make-loja-url-cliente"] = $loja[0]['loja_url_cliente'];
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"] = $host[0]['url'];
				$_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"] = Array(
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
					'url' => $host[0]['url'],
				);
				
			} else {
				if(!$xml){
					if(!$ajax){
						$saida = opcao_nao_econtrada();
						return $saida;
					} else {
						return '';
					}
				} else {
					return '';
				}
			}
		}
	} else {
		if(!$xml){
			if(!$ajax){
				$saida = opcao_nao_econtrada();
				return $saida;
			} else {
				return eservices_ajax();
			}
		} else {
			return '';
		}
	}
	
	global $_LAYOUT_NUM;$_LAYOUT_NUM = '2';
	
	if($_REQUEST['iframe']){
		$_LAYOUT_NUM = '-loja-iframe';
		$_VARIAVEIS_JS['b2make_loja_iframe'] = true;
		$_ESERVICE['iframe'] = true;
	}
	
	if($_SESSION[$_SYSTEM['ID']."b2make-loja-atual-versao"])$versao = '?v=' . $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-versao"];
	
	$_VARIAVEIS_JS['b2make_loja_atual'] = $_HTML['variaveis']['b2make-loja-atual'] = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual"];
	$_HTML['variaveis']['b2make-loja-atual-logo'] = '/'.$_SYSTEM['ROOT'].($_SESSION[$_SYSTEM['ID']."b2make-loja-atual-logomarca"] ? $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-logomarca"].$versao : 'images/store/b2make-logo-minha-loja.png');

	if($_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"]){
		$dados_loja = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-dados"];
		
		if($dados_loja['nome']) $linha_1 = $_HTML['TITULO'] = $dados_loja['nome'];
		
		if($dados_loja['endereco']) $linha_2 .= $dados_loja['endereco'];
		if($dados_loja['numero']) $linha_2 .= ', '.$dados_loja['numero'];
		if($dados_loja['complemento']) $linha_2 .= ' '.$dados_loja['complemento'];
		if($dados_loja['telefone']) $linha_2 .= ' - '.$dados_loja['telefone'];
		if($dados_loja['cnpj']) $linha_2 .= ' - '.$dados_loja['cnpj']; else if($dados_loja['cpf']) $linha_2 .= ' - '.$dados_loja['cpf'];
		if($dados_loja['bairro']) $linha_2 .= ' - '.$dados_loja['bairro'];
		if($dados_loja['cidade']) $linha_2 .= ' - '.$dados_loja['cidade'];
		if($dados_loja['uf']) $linha_2 .= ' - '.$dados_loja['uf'];
		if($dados_loja['pais']) $linha_2 .= ' - '.$dados_loja['pais'];
		
		
		$linha_3 .= $_ESERVICE['store-rodape-joja'] . date('Y');
		
		$_HTML['variaveis']['b2make-loja-atual-rodape'] = '<span>'.$linha_1.'</span>' . ($linha_2 ? '<br>'.$linha_2 : '') . ($linha_3 ? '<br>'.$linha_3 : '');
		
		$esquema_cores = $dados_loja['esquema_cores'];
		
		if(!$esquema_cores){
			$esquema_cores = '434142ff|rgb(67,65,66);ffffffff|rgb(255,255,255);d28d00ff|rgb(210,141,0)';
		}
		
		$esquema_cores_arr = explode(';',$esquema_cores);
		
		$cor_1 = explode('|',$esquema_cores_arr[0]);
		$cor_2 = explode('|',$esquema_cores_arr[1]);
		$cor_3 = explode('|',$esquema_cores_arr[2]);
		
		$_VARIAVEIS_JS['b2make_cart_cor_1'] = $cor_1[1];
		$_VARIAVEIS_JS['b2make_cart_cor_2'] = $cor_2[1];
		$_VARIAVEIS_JS['b2make_cart_cor_3'] = $cor_3[1];
		
		$fontes = $dados_loja['fontes'];
		
		$font_padrao = 'Open+Sans';
		
		if($fontes){
			$fontes = preg_replace('/"/i', '', $fontes);
			$fonte = preg_replace('/ /i', '+', $fontes);
		} else {
			$fonte = $font_padrao;
		}
		
		$_HTML['variaveis']['b2make-loja-atual-url'] = '/'.$_SYSTEM['ROOT'].'e-services/'.$_ESERVICE['loja-atual'].'/';
		$_HTML['variaveis']['b2make-loja-atual-url-site'] = $dados_loja['url'];
		$_VARIAVEIS_JS['b2make_loja_url_atual'] = $dados_loja['url'] . $opcao . '/';
		$_VARIAVEIS_JS['b2make_loja_url_base'] = $dados_loja['url'];
		
		$_HTML['variaveis']['b2make-loja-atual-fonte'] = $fonte;
		
		$_HTML['css'] .= '
<style>
html,select,#alerta_box_header,#alerta_box_texto,#alerta_box_texto p{
	font-family: \''.$fontes.'\', Verdana,  Geneva, sans-serif !important;
}
.b2make-loja-color-1{
	color: '.$cor_1[1].' !important;
}
.b2make-loja-bg-color-1{
	background-color: '.$cor_1[1].' !important;
}
.b2make-loja-color-2{
	color: '.$cor_2[1].' !important;
}
.b2make-loja-bg-color-2{
	background-color: '.$cor_2[1].' !important;
}
.b2make-loja-color-3{
	color: '.$cor_3[1].' !important;
}
.b2make-loja-bg-color-3{
	background-color: '.$cor_3[1].' !important;
}
</style>
';
	}
	
	if(!$xml){
		if(!$ajax){
			if($opcao){
				switch($opcao){
					case 'paypal-pay':						$saida = eservice_paypal_pay(); break;
					case 'paypal-pay2':						$saida = eservice_paypal_pay2(); break;
					case 'paypal-return':					$saida = eservice_paypal_return(); break;
					case 'paypal-cancel':					$saida = eservice_paypal_cancel(); break;
					case 'request-register':				$saida = eservice_request_register(); break;
					case 'cancel-purchase':					$saida = eservice_cancel_purchase(); break;
					case 'voucher-form-gift':				$saida = eservice_voucher_form_gift(); break;
					case 'payment-return':					$saida = eservice_payment_return(); break;
					case 'payment-card':					$saida = eservice_payment_card(); break;
					case 'payment':							$saida = eservice_payment(); break;
					case 'emission':						$saida = eservice_emission(); break;
					case 'identify-yourself':				$saida = eservice_identify_yourself(); break;
					case 'signin':							$saida = eservice_signin(); break;
					case 'signin-request':					$saida = eservice_signin_request(); break;
					case 'signin-2-request':				$saida = eservice_signin_2_request(); break;
					case 'signup':							$saida = eservice_signup(); break;
					case 'signup-request':					$saida = eservice_signup_request(); break;
					case 'signup-2-request':				$saida = eservice_signup_2_request(); break;
					case 'how-it-works':					$saida = eservice_howitworks(); break;
					case 'checkout':						$saida = eservice_checkout(); break;
					case 'forgot-your-password':			$saida = eservice_forgot_your_password(); break;
					case 'forgot-your-password-request':	$saida = eservice_forgot_your_password_request(); break;
					case 'generate-new-password':			$saida = eservice_generate_new_password(); break;
					case 'password-reset-request':			$saida = eservice_password_reset_request(); break;
					case 'logout':							$saida = eservice_logout(); break;
					case 'testes':							$saida = eservice_testes(); break;
					case 'cart':							$saida = eservice_cart(); break;
					case 'my-account':						$saida = eservice_account(); break;
					case 'account':							$saida = eservice_account(); break;
					case 'account-db':						$saida = eservice_account_db(); break;
					case 'pre-checkout':					$saida = eservice_pre_checkout(); break;
					case 'purchases':						$saida = eservice_purchases(); break;
					case 'voucher':							$saida = eservice_voucher_page(); break;
					case 'qrcode':							$saida = eservice_qrcode(); break;
					case 'barcode':							$saida = eservice_barcode(); break;
					case 'voucher-print':					$saida = eservice_voucher_print(); break;
					case 'voucher-print-store':				$saida = eservice_voucher_print_store(); break;
					case 'emission-request':				$saida = eservice_emission_request(); break;
					default: 								$saida = opcao_nao_econtrada();
				}
			} else {
				$saida = eservice_pagina_inicial_loja();
			}
			
			return $saida;
		} else {
			return eservices_ajax();
		}
	} else {
		return eservices_xml();
	}
}

if(!$_ESERVICES['apenas_incluir'])return eservices_main();

?>