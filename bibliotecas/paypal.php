<?php

global $_GESTOR;

$_GESTOR['biblioteca-paypal']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function paypal_token_generate($params = false){
	/**********
		Descrição: geração de tokens do PayPal
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// clientId - String - Obrigatório - Usuário do APP de autenticação no PayPal.
	// secret - String - Obrigatório - Senha do APP de autenticação no PayPal.
	// gestor - Bool - Opcional - Se for geração para o gestor, não obrigar id_hosts.
	// live - Bool - Opcional - Se é requisição live ou sandbox.
	
	// Se not gestor
	
	// id_hosts - Int - Obrigatório - Identificador do host requerido.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Se não for gestor, obrigar id_hosts.
	
	if(!isset($gestor)){
		if(!isset($id_hosts)){
			return Array(
				'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-mandatory-fields')),
				'status' => 'mandatory-fields',
				'error' => true,
			);
		}
	}
	
	// ===== Verificar campos obrigatórios.
	
	if(isset($clientId) && isset($secret)){
		// ===== Fazer a requisição ao servidor do PayPal.
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://api.".(isset($live) ? "" : "sandbox.")."paypal.com/v1/oauth2/token");
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
		
		// ===== Tratar o retorno.
		
		if(empty($result)){
			$retorno = Array(
				'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-empty-result')),
				'status' => 'empty-result',
				'error' => true,
			);
		} else {
			$json = json_decode($result);
			
			if($json->error){
				$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-error'));
				
				$erro_var = modelo_var_troca($erro_var,"#error#",$json->error);
				$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->error_description);
				
				$retorno = Array(
					'error-msg' => $erro_var,
					'status' => 'error-result',
					'error' => true,
				);
			} else {
				$access_token = $json->access_token;
				$expires_in = $json->expires_in;
				$token_time = time();
				
				// ===== Atualizar os valores do token no banco de dados caso for uma requisição de hosts.
				
				if(isset($id_hosts)){
					banco_update_campo("app_".($live ? "" : "sandbox_")."token",$access_token);
					banco_update_campo("app_".($live ? "" : "sandbox_")."token_time",$token_time);
					banco_update_campo("app_".($live ? "" : "sandbox_")."expires_in",$expires_in);
					
					banco_update_executar('hosts_paypal',"WHERE id_hosts='".$id_hosts."'");
				}
				
				$retorno = Array(
					'token' => $access_token,
					'token_time' => $token_time,
					'expires_in' => $expires_in,
					'status' => 'OK',
					'completed' => true,
				);
			}
		}
		
		curl_close($ch);
	} else {
		$retorno = Array(
			'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-mandatory-fields')),
			'status' => 'mandatory-fields',
			'error' => true,
		);
	}
	
	// ===== Gerar log caso haja erros na geração do token.
	
	if($retorno['error']){
		gestor_incluir_biblioteca('log');
		log_disco('[paypal_token_generate] - error - retorno: '.print_r($retorno,true));
	}
	
	// ===== Retornar token com seus dados.
	
	return $retorno;
}

function paypal_gestor_token_generate($params = false){
	/**********
		Descrição: geração de tokens do PayPal do próprio gestor.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// live - Bool - Opcional - Se é requisição live ou sandbox.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Pegar autenticação do PayPal do Gestor.
	
	$config = gestor_incluir_configuracao(Array(
		'id' => 'paypal.config',
	));
	
	if(isset($live)){
		$clientId = $config['live-client-id'];
		$secret = $config['live-secret'];
	} else {
		$clientId = $config['sandbox-client-id'];
		$secret = $config['sandbox-secret'];
	}
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca('variaveis');
	
	$paypal = variaveis_sistema('paypal');
	
	// ===== Ver a necessidade de gerar o token ou não.
	
	$gerar_token = false;
	
	if(isset($live)){
		if($paypal['live-token']){
			if((int)$paypal['live-token-time']+(int)$paypal['live-expires-in'] < time()){
				$gerar_token = true;
			}
		} else {
			$gerar_token = true;
		}
		
		if(!$gerar_token){
			$retorno = Array(
				'token' => $paypal['live-token'],
				'status' => 'OK',
				'completed' => true,
			);
			
			return $retorno;
		}
	} else {
		if($paypal['sandbox-token']){
			if((int)$paypal['sandbox-token-time']+(int)$paypal['sandbox-expires-in'] < time()){
				$gerar_token = true;
			}
		} else {
			$gerar_token = true;
		}
		
		if(!$gerar_token){
			$retorno = Array(
				'token' => $paypal['sandbox-token'],
				'status' => 'OK',
				'completed' => true,
			);
			
			return $retorno;
		}
	}
	
	// ===== Gerar token.
	
	$tokenVars = Array(
		'clientId' => $clientId,
		'secret' => $secret,
		'gestor' => true,
	);
	
	if(isset($live)){
		$tokenVars['live'] = true;
	}
	
	$retorno = paypal_token_generate($tokenVars);
	
	// ===== Guardar token na variável global.
	
	if($retorno['completed']){
		if(isset($live)){
			variaveis_sistema_atualizar('paypal','live-token',$retorno['token']);
			variaveis_sistema_atualizar('paypal','live-token-time',$retorno['token_time']);
			variaveis_sistema_atualizar('paypal','live-expires-in',$retorno['expires_in']);
		} else {
			variaveis_sistema_atualizar('paypal','sandbox-token',$retorno['token']);
			variaveis_sistema_atualizar('paypal','sandbox-token-time',$retorno['token_time']);
			variaveis_sistema_atualizar('paypal','sandbox-expires-in',$retorno['expires_in']);
		}
	}
	
	// ===== Retornar token com seus dados.
	
	return $retorno;
}

function paypal_reference_gestor_taxa($params = false){
	/**********
		Descrição: Pagamento de taxa para o gestor de um pedido no PayPal.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host que pagará a comissão.
	// id_hosts_pedidos - Int - Obrigatório - Identificador do pedido que será cobrada comissão.
	
	// ===== 
	
	// ===== Incluir bibliotecas
	
	gestor_incluir_biblioteca('log');
	
	// ===== Definição do retorno.
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Verificar campos obrigatórios.
	
	if(isset($id_hosts) && isset($id_hosts_pedidos)){
		// ===== Verificar se este pedido já foi cobrado comissão. Caso positivo, não cobrar novamente e retornar.
		
		$hosts_paypal_gestor_taxas = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_paypal_gestor_taxas',
			'campos' => Array(
				'id_hosts_paypal_gestor_taxas',
			),
			'extra' => 
				"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		if($hosts_paypal_gestor_taxas){
			return Array(
				'status' => 'OK',
				'completed' => true,
			);
		}
		
		// ===== Pegar o PayPal 'reference_id' do host.
		
		$hosts_paypal = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_paypal',
			'campos' => Array(
				'reference_id',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
		));
		
		// ===== Senão existir o reference_id, retornar ok e não cobrar nada.
		
		if(!$hosts_paypal['reference_id']){
			log_disco('[paypal_reference_gestor_taxa] - reference_id não definido - id_hosts: '.$id_hosts.' - id_hosts_pedidos: '.$id_hosts_pedidos);
			
			return Array(
				'status' => 'OK',
				'completed' => true,
			);
		}
		
		$reference_id = $hosts_paypal['reference_id'];
		
		// ===== Pegar dados do pedido.
		
		$hosts_pedidos = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_pedidos',
			'campos' => Array(
				'codigo',
				'total',
				'live',
			),
			'extra' => 
				"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		$codigo = $hosts_pedidos['codigo'];
		$valor_total = $hosts_pedidos['total'];
		
		if($hosts_pedidos['live']){
			$live = true;
		} else {
			$live = false;
			
			// ===== Se for ambiente sandbox, não cobrar taxa e retornar.
			
			return Array(
				'status' => 'OK',
				'completed' => true,
			);
		}
		
		// ===== Cobrar taxa sobre o valor total do pedido.
		
		$config = gestor_incluir_configuracao(Array(
			'id' => 'paypal.config',
		));
		
		$valor_taxa = ((float)$valor_total*($config['gestor-taxa']/100));
		
		// ===== Pegar o token do gestor.
		
		if($live){
			$retorno = paypal_gestor_token_generate(Array(
				'live' => true,
			));
		} else {
			$retorno = paypal_gestor_token_generate();
		}
		
		if($retorno['error']){
			log_disco('[paypal_reference_gestor_taxa] - token não gerado - id_hosts: '.$id_hosts.' - id_hosts_pedidos: '.$id_hosts_pedidos);
			
			return $retorno;
		} else {
			$token = $retorno['token'];
		}
		
		// ===== Montar o objeto de pagaamento da taxa do PayPal.
		
		$obj['intent'] = 'sale';
		$obj['payer'] = Array(
			'payment_method' => 'paypal',
			'funding_instruments' => Array(
				Array(
					'billing' => Array(
						'billing_agreement_id' => $reference_id,
					)
				)
			),
		);
		$obj['application_context'] = Array(
			'brand_name' => 'Entrey',
			'shipping_preference' => 'NO_SHIPPING',
		);
		$obj['transactions'] = Array(
			Array(
				'amount' => Array(
					'currency' => 'BRL',
					'total' => number_format($valor_taxa, 2, '.', ''),
				),
				'description' => 'Cobrança de taxa sobre uso do Entrey do pedido '.$codigo.'.',
				'custom' => $codigo,
				'payment_options' => Array(
					'allowed_payment_method' => 'IMMEDIATE_PAY',
				),
				'item_list' => Array(
					'items' => Array(
						Array(
							'name' => 'Cobrança de taxa da Entrey.',
							'description' => 'Cobrança de taxa sobre uso do Entrey do pedido '.$codigo.'.',
							'quantity' => '1',
							'price' => number_format($valor_taxa, 2, '.', ''),
							'tax' => '0',
							'sku' => $codigo,
							'currency' => 'BRL',
						)
					),
				),
			),
		);
		$obj['redirect_urls'] = Array(
			'cancel_url' => $_GESTOR['url-full-http'].'_gateways/paypal-gestor-taxa-return/?action=cancel',
			'return_url' => $_GESTOR['url-full-http'].'_gateways/paypal-gestor-taxa-return/?action=return',
		);
		
		$json_send = json_encode($obj);
		
		// ===== Fazer a requisição ao servidor do PayPal.
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "https://api.".($live ? "" : "sandbox.")."paypal.com/v1/payments/payment");
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
		
		// ===== Tratar o retorno da requisição.
		
		if(empty($result)){
			log_disco('[paypal_reference_gestor_taxa] - empty result - id_hosts: '.$id_hosts.' - id_hosts_pedidos: '.$id_hosts_pedidos);
		} else {
			$json = json_decode($result);
			
			if($json->name){
				log_disco('[paypal_reference_gestor_taxa] - error returned - id_hosts: '.$id_hosts.' - id_hosts_pedidos: '.$id_hosts_pedidos.' - JSON Recebido: '.print_r($json,true));
			} else {
				$pay_id = $json->id;
				
				banco_insert_name_campo('id_hosts',$id_hosts);
				banco_insert_name_campo('id_hosts_pedidos',$id_hosts_pedidos);
				banco_insert_name_campo('pay_id',$pay_id);
				banco_insert_name_campo('data','NOW()',true);
				banco_insert_name_campo('valor', number_format($valor_taxa, 2, '.', ''));
				
				if($live){ banco_insert_name_campo('live','1',true); }
				
				banco_insert_name
				(
					banco_insert_name_campos(),
					"hosts_paypal_gestor_taxas"
				);
			}
		}

		curl_close($ch);
	} else {
		log_disco('[paypal_reference_gestor_taxa] - mandatory fields not defined - id_hosts: '.$id_hosts.' - id_hosts_pedidos: '.$id_hosts_pedidos);
		
		$retorno = Array(
			'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-mandatory-fields')),
			'status' => 'mandatory-fields',
			'error' => true,
		);
	}
	
	return $retorno;
}

function paypal_reference_create($params = false){
	/**********
		Descrição: Criar reference no PayPal para cobrança de taxas.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host requerido.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Verificar campos obrigatórios.
	
	if(isset($id_hosts)){
		// ===== Pegar o token do gestor.
		
		$live = true;
		
		$retorno = paypal_gestor_token_generate(Array(
			'live' => $live,
		));
		
		if($retorno['error']){
			return $retorno;
		} else {
			$token = $retorno['token'];
		}
		
		// ===== Pegar taxa do PayPal do Gestor.
		
		$config = gestor_incluir_configuracao(Array(
			'id' => 'paypal.config',
		));
		
		gestor_incluir_biblioteca('formato');
		
		$taxa = formato_dado_para('float-para-texto',$config['gestor-taxa']);
		
		// ===== Montar o objeto de criação do reference do PayPal.
		
		$description = gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-description'));
		
		$description = modelo_var_troca($description,"#taxa#",$taxa);
		
		$obj['description'] = $description;
		$obj['payer'] = Array(
			'payment_method' => 'PAYPAL',
		);
		$obj['plan'] = Array(
			'type' => 'MERCHANT_INITIATED_BILLING',
			'merchant_preferences' => Array(
				'cancel_url' => $_GESTOR['url-full-http'].'gateways-de-pagamentos/paypal-reference-return/?action=cancel',
				'return_url' => $_GESTOR['url-full-http'].'gateways-de-pagamentos/paypal-reference-return/?action=return',
				'accepted_pymt_type' => 'Instant',
			),
		);
		
		$json = json_encode($obj);
		
		// ===== Fazer a requisição ao servidor do PayPal.
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "https://api.".($live ? "" : "sandbox.")."paypal.com/v1/billing-agreements/agreement-tokens");
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
		
		// ===== Tratar o retorno da requisição.
		
		if(empty($result)){
			$retorno = Array(
				'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-empty-result')),
				'status' => 'empty-result',
				'error' => true,
			);
		} else {
			$json = json_decode($result);
			
			if($json->name){
				$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-token-error'));
				
				$erro_var = modelo_var_troca($erro_var,"#name#",$json->name);
				$erro_var = modelo_var_troca($erro_var,"#details#",$json->details[0]->name);
				$erro_var = modelo_var_troca($erro_var,"#message#",$json->details[0]->message);
				
				$retorno = Array(
					'error-msg' => $erro_var,
					'status' => 'error-result',
					'error' => true,
				);
			} else {
				$href = $json->links[0]->href;
				$token_ba_id = $json->token_id;
				
				// ===== Guardar o token numa sessão.
				
				gestor_sessao_variavel('paypal-reference-token-'.$id_hosts,$token_ba_id);
				
				// ===== Redirecionar para o PayPal dar continuidade.
				
				gestor_redirecionar($href,'',true);
			}
		}

		curl_close($ch);
	} else {
		$retorno = Array(
			'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-mandatory-fields')),
			'status' => 'mandatory-fields',
			'error' => true,
		);
	}
	
	return $retorno;
}

function paypal_reference_return($params = false){
	/**********
		Descrição: Tratar retorno da requisição do reference do PayPal.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host requerido.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Verificar campos obrigatórios.
	
	if(isset($id_hosts)){
		$live = true;
		
		if($_REQUEST['action'] == 'cancel'){
			return Array(
				'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-return-cancel')),
				'status' => 'cancel',
				'error' => true,
			);
		} else if($_REQUEST['action'] == 'return'){
			// ===== Recurperar a sessão com o token ba.
			
			$token_ba_id = gestor_sessao_variavel('paypal-reference-token-'.$id_hosts);
			gestor_sessao_variavel_del('paypal-reference-token-'.$id_hosts);
			
			// ===== Verificar se o token enviado é válido.
			
			if($token_ba_id != $_REQUEST['ba_token']){
				return Array(
					'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-return-token-invalid')),
					'status' => 'token-invalid',
					'error' => true,
				);
			}
			
			// ===== Pegar o token do gestor.
			
			$retorno = paypal_gestor_token_generate(Array(
				'live' => $live,
			));
			
			if($retorno['error']){
				return $retorno;
			} else {
				$token = $retorno['token'];
			}
			
			$obj['agreement_token'] = $token_ba_id;
			
			$json = json_encode($obj);
			
			// ===== Fazer a requisição ao servidor do PayPal.
			
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, "https://api.".($live ? "" : "sandbox.")."paypal.com/v1/billing-agreements/".$token_ba_id."/agreements");
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
			
			// ===== Tratar o retorno da requisição.
			
			if(empty($result)){
				$retorno = Array(
					'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-return-empty-result')),
					'status' => 'empty-result',
					'error' => true,
				);
			} else {
				$json = json_decode($result);
				
				if($json->error){
					$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-return-token-error'));
					
					$erro_var = modelo_var_troca($erro_var,"#error#",$json->error);
					$erro_var = modelo_var_troca($erro_var,"#description#",$json->error_description);
					
					$retorno = Array(
						'error-msg' => $erro_var,
						'status' => 'error-result',
						'error' => true,
					);
				} else {
					$reference_cancel_url = $json->links[0]->href;
					$reference_id = $json->id;
					
					banco_update_campo('reference_installed','1',true);
					banco_update_campo('reference_id',$reference_id);
					banco_update_campo('reference_cancel_url',$reference_cancel_url);
					
					banco_update_executar('hosts_paypal',"WHERE id_hosts='".$id_hosts."'");
					
					$retorno = Array(
						'status' => 'OK',
						'completed' => true,
					);
				}
			}

			curl_close($ch);
		}
	} else {
		$retorno = Array(
			'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-mandatory-fields')),
			'status' => 'mandatory-fields',
			'error' => true,
		);
	}
	
	return $retorno;
}

function paypal_reference_cancel($params = false){
	/**********
		Descrição: Cancelar reference no PayPal para cobrança de taxas.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// url - String - Obrigatório - URL de cancelamento.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Verificar campos obrigatórios.
	
	if(isset($url)){
		// ===== Pegar o token do gestor.
		
		$live = true;
		
		$retorno = paypal_gestor_token_generate(Array(
			'live' => $live,
		));
		
		if($retorno['error']){
			return $retorno;
		} else {
			$token = $retorno['token'];
		}
		
		// ===== Montar o objeto de cancelamento do reference do PayPal.
		
		$obj['note'] = 'Canceling the profile.';
		
		$json = json_encode($obj);
		
		// ===== Fazer a requisição ao servidor do PayPal.
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
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
		
		// ===== Tratar o retorno da requisição.
		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if($httpcode == 200){
			$retorno = Array(
				'status' => 'OK',
				'completed' => true,
			);
		} else if(empty($result)){
			$retorno = Array(
				'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-empty-result')),
				'status' => 'empty-result',
				'error' => true,
			);
		} else {
			$json = json_decode($result);
			
			if($json->name){
				$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-token-error'));
				
				$erro_var = modelo_var_troca($erro_var,"#name#",$json->name);
				$erro_var = modelo_var_troca($erro_var,"#details#",$json->details[0]->name);
				$erro_var = modelo_var_troca($erro_var,"#message#",$json->details[0]->message);
				
				$retorno = Array(
					'error-msg' => $erro_var,
					'status' => 'error-result',
					'error' => true,
				);
			} else {
				$retorno = Array(
					'error-msg' => 'Error not detected',
					'status' => 'error-not-detected',
					'error' => true,
				);
			}
		}

		curl_close($ch);
	} else {
		$retorno = Array(
			'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'token-mandatory-fields')),
			'status' => 'mandatory-fields',
			'error' => true,
		);
	}
	
	return $retorno;
}

function paypal_criar_pagamento($params = false){
	/**********
		Descrição: Criar um novo pagamento no PayPal.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// codigo - String - Obrigatório - Código do pedido.
	// id_hosts - Int - Obrigatório - Identificador do host.
	// id_hosts_usuarios - Int - Obrigatório - Identificador do usuário do host.
	// live - Bool - Opcional - Se é requisição live ou sandbox.
	// outro_pagador - Array - Opcional - Se é requisição é de outro pagador.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Verificar campos obrigatórios.
	
	if(isset($codigo) && isset($id_hosts)){
		// ===== Verificar se é live ou sandbox.
		
		if(isset($live)){
			// ===== Pegar dados do PayPal.
			
			$hosts_paypal = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_paypal',
				'campos' => Array(
					'app_code',
					'app_secret',
					'app_token',
					'app_token_time',
					'app_expires_in',
				),
				'extra' => 
					"WHERE id_dados='".$id_hosts."'"
			));
			
			// ===== Verificar se o token está expirado.
			
			$gerar_token = false;
			
			if($hosts_paypal['app_token']){
				if((int)$hosts_paypal['app_token_time']+(int)$hosts_paypal['app_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
			
			// ===== Se o token estiver expirado, gerar um novo.
			
			if($gerar_token){
				$retorno = paypal_token_generate(Array(
					'clientId' => $hosts_paypal['app_code'],
					'secret' => $hosts_paypal['app_secret'],
					'live' => true,
					'id_hosts' => $id_hosts,
				));
				
				if(!$retorno['error']){
					$hosts_paypal['app_token'] = $retorno['token'];
				}
			}
			
			$token = $hosts_paypal['app_token'];
		} else {
			// ===== Pegar dados do PayPal.
			
			$hosts_paypal = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_paypal',
				'campos' => Array(
					'app_sandbox_code',
					'app_sandbox_secret',
					'app_sandbox_token',
					'app_sandbox_token_time',
					'app_sandbox_expires_in',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
			));
			
			// ===== Verificar se o token está expirado.
			
			$gerar_token = false;
			
			if($hosts_paypal['app_sandbox_token']){
				if((int)$hosts_paypal['app_sandbox_token_time']+(int)$hosts_paypal['app_sandbox_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
			
			// ===== Se o token estiver expirado, gerar um novo.
			
			if($gerar_token){
				$retorno = paypal_token_generate(Array(
					'clientId' => $hosts_paypal['app_sandbox_code'],
					'secret' => $hosts_paypal['app_sandbox_secret'],
					'id_hosts' => $id_hosts,
				));
				
				if(!$retorno['error']){
					$hosts_paypal['app_sandbox_token'] = $retorno['token'];
				}
			}
			
			$token = $hosts_paypal['app_sandbox_token'];
		}
		
		// ===== Pegar nome da loja.
		
		gestor_incluir_biblioteca('host');
		
		$loja_nome = host_loja_nome(Array('id_hosts' => $id_hosts));
		$loja_url = host_url(Array('opcao' => 'full','id_hosts' => $id_hosts));
		
		// ===== Pegar dados do pedido.
		
		$hosts_pedidos = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_pedidos',
			'campos' => Array(
				'id_hosts_pedidos',
				'total',
			),
			'extra' => 
				"WHERE codigo='".$codigo."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
		
		$hosts_pedidos_servicos = banco_select(Array(
			'tabela' => 'hosts_pedidos_servicos',
			'campos' => Array(
				'id_hosts_pedidos_servicos',
				'nome',
				'quantidade',
				'preco',
			),
			'extra' => 
				"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		$hosts_pedidos_servico_variacoes = banco_select(Array(
			'tabela' => 'hosts_pedidos_servico_variacoes',
			'campos' => Array(
				'id_hosts_pedidos_servico_variacoes',
				'nome_servico',
				'nome_lote',
				'nome_variacao',
				'quantidade',
				'preco',
			),
			'extra' => 
				"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		// ===== Montar o objeto da requisição de pagamento no PayPal.
		
		$total = $hosts_pedidos['total'];
		
		if($hosts_pedidos_servicos)
		foreach($hosts_pedidos_servicos as $res){
			$items[] = Array(
				'name' => $res['nome'],
				//'description' => limite_texto($res['t2.descricao'],100),
				'quantity' => $res['quantidade'],
				'price' => number_format((float)$res['preco'], 2, '.', ''),
				'sku' => 'PS'.$res['id_hosts_pedidos_servicos'],
				'currency' => 'BRL',
			);
		}
		
		if($hosts_pedidos_servico_variacoes)
		foreach($hosts_pedidos_servico_variacoes as $res){
			$items[] = Array(
				'name' => $res['nome_servico'].' | '.$res['nome_lote'].' | '.$res['nome_variacao'],
				//'description' => limite_texto($res['t2.descricao'],100),
				'quantity' => $res['quantidade'],
				'price' => number_format((float)$res['preco'], 2, '.', ''),
				'sku' => 'PSV'.$res['id_hosts_pedidos_servico_variacoes'],
				'currency' => 'BRL',
			);
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
				'description' => 'Pedido '.$codigo,
				'payment_options' => Array(
					'allowed_payment_method' => 'IMMEDIATE_PAY',
				),
				'invoice_number' => $codigo,
				'item_list' => Array(
					'items' => $items,
				),
			)
		);
		$obj['redirect_urls'] = Array(
			'return_url' => $loja_url.'pagamento/?paypal-return=return',
			'cancel_url' => $loja_url.'pagamento/?paypal-return=cancel',
		);
		
		$json = json_encode($obj);
		
		// ===== Fazer a requisição ao servidor do PayPal.
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "https://api.".(isset($live) ? "" : "sandbox.")."paypal.com/v1/payments/payment");
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
		curl_close($ch);
		
		// ===== Tratar o retorno da requisição.
		
		if(empty($result)){
			$retorno = Array(
				'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-empty-result')),
				'status' => 'empty-result',
				'error' => true,
			);
		} else {
			$json = json_decode($result);
			
			if($json->error){
				$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-error'));
				
				$erro_var = modelo_var_troca($erro_var,"#error#",$json->error);
				$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->error_description);
				
				$retorno = Array(
					'error-msg' => $erro_var,
					'status' => 'error-result',
					'error' => true,
				);
			} else if($json->name){
				$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-error'));
				
				$erro_var = modelo_var_troca($erro_var,"#error#",$json->name);
				$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->message);
				
				$retorno = Array(
					'error-msg' => $erro_var,
					'status' => 'error-result',
					'error' => true,
				);
			} else {
				// ===== Pagador dados.
				
				if($outro_pagador){
					// ===== Pegar primeiro e último nomes.
					
					$nomeArr = explode(" ",$outro_pagador['nome']);
					
					$outro_pagador['nome'] = $nomeArr[0];
					$outro_pagador['ultimo_nome'] = $nomeArr[(count($nomeArr) - 1)];
					
					// ===== Verificar se todos os campos foram enviados.
					
					if(
						!$outro_pagador['nome'] ||
						!$outro_pagador['ultimo_nome'] ||
						!$outro_pagador['email'] ||
						!$outro_pagador['telefone'] ||
						!$outro_pagador['cnpj_ativo'] ||
						($outro_pagador['cnpj_ativo'] == 'sim' ? !$outro_pagador['cnpj'] : !$outro_pagador['cpf'])
					){
						return Array(
							'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-other-payer-error')),
							'status' => 'other-payer-error',
							'error' => true,
							'completed' => false,
						);
					}
					
					// ===== Montar dados do pagador.
					
					$pagador = Array(
						'first_name' => $outro_pagador['nome'],
						'last_name' => $outro_pagador['ultimo_nome'],
						'email' => $outro_pagador['email'],
						'telefone' => $outro_pagador['telefone'],
						'cnpj_ativo' => ($outro_pagador['cnpj_ativo'] == 'sim' ? true : null),
						'cpf' => $outro_pagador['cpf'],
						'cnpj' => $outro_pagador['cnpj'],
						'ppp_remembered_card_hash' => '',
					);
				} else {
					// ===== Pegar os dados do usuário.
					
					$hosts_usuarios = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios',
						'campos' => Array(
							'primeiro_nome',
							'ultimo_nome',
							'email',
							'telefone',
							'cnpj_ativo',
							'cpf',
							'cnpj',
							'ppp_remembered_card_hash',
						),
						'extra' => 
							"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Montar dados do pagador.
					
					$pagador = Array(
						'first_name' => $hosts_usuarios['primeiro_nome'],
						'last_name' => $hosts_usuarios['ultimo_nome'],
						'email' => $hosts_usuarios['email'],
						'telefone' => $hosts_usuarios['telefone'],
						'cnpj_ativo' => ($hosts_usuarios['cnpj_ativo'] ? $hosts_usuarios['cnpj_ativo'] : null),
						'cpf' => ($hosts_usuarios['cpf'] ? $hosts_usuarios['cpf'] : null),
						'cnpj' => ($hosts_usuarios['cnpj'] ? $hosts_usuarios['cnpj'] : null),
						'ppp_remembered_card_hash' => ($hosts_usuarios['ppp_remembered_card_hash'] ? $hosts_usuarios['ppp_remembered_card_hash'] : null),
					);
				}
				
				// ===== Retorno do PayPal.
				
				$pay_id = $json->id;
				$status = $json->state;
				$links = $json->links;
				
				if($links)
				foreach($links as $link){
					switch($link->rel){
						case 'approval_url': $approval_url = $link->href; break;
					}
				}
				
				// ===== Criar requisição de pagamento no banco.
				
				banco_insert_name_campo('id_hosts',$id_hosts);
				banco_insert_name_campo('id_hosts_pedidos',$id_hosts_pedidos);
				banco_insert_name_campo('pay_id',$pay_id);
				banco_insert_name_campo('pagador_primeiro_nome',$pagador['first_name']);
				banco_insert_name_campo('pagador_ultimo_nome',$pagador['last_name']);
				banco_insert_name_campo('pagador_email',$pagador['email']);
				banco_insert_name_campo('pagador_telefone',$pagador['telefone']);
				
				if(isset($pagador['cpf'])){ banco_insert_name_campo('pagador_cpf',$pagador['cpf']); }
				if(isset($pagador['cnpj'])){ banco_insert_name_campo('pagador_cnpj',$pagador['cnpj']); }
				if(isset($pagador['cnpj_ativo'])){ banco_insert_name_campo('pagador_selecionou_cnpj','1',true); }
				
				banco_insert_name_campo('data_criacao','NOW()',true);
				banco_insert_name_campo('data_modificacao','NOW()',true);
				
				if(isset($live)){ banco_insert_name_campo('live','1',true); }
				
				banco_insert_name_campo('status',$status);
				
				banco_insert_name
				(
					banco_insert_name_campos(),
					"hosts_paypal_pagamentos"
				);
				
				// ===== Montar variável de retorno para disparar o PayPal no cliente.
				
				$ppplus = $pagador;
				$ppplus['approval_url'] = $approval_url;
				$ppplus['pay_id'] = $pay_id;
				
				if($outro_pagador) $ppplus['outro_pagador'] = true;
				
				// ===== Retornar dados para o host.
				
				$retorno = Array(
					'ppplus' => $ppplus,
					'status' => 'OK',
					'completed' => true,
				);
			}
		}

		curl_close($ch);
	} else {
		$retorno = Array(
			'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-mandatory-fields')),
			'status' => 'mandatory-fields',
			'error' => true,
		);
	}
	
	return $retorno;
}

function paypal_executar_pagamento($params = false){
	/**********
		Descrição: Efetuar um pagamento no PayPal.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// codigo - String - Obrigatório - Código do pedido.
	// id_hosts - Int - Obrigatório - Identificador do host.
	// pay_id - String - Obrigatório - Identificador da compra criada pelo PayPal.
	// payerID - String - Obrigatório - Identificador do comprador criada pelo PayPal.
	// installmentsValue - String - Obrigatório - Quantidade de parcelas escolhidas pelo comprador.
	// live - Bool - Opcional - Se é requisição live ou sandbox.
	// paypalButton - Bool - Opcional - Se a requisição foi feita pelo botão do PayPal.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	// ===== Verificar campos obrigatórios.
	
	if(isset($codigo) && isset($id_hosts) && isset($pay_id)){
		// ===== Verificar se é live ou sandbox.
		
		if(isset($live)){
			// ===== Pegar dados do PayPal.
			
			$hosts_paypal = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_paypal',
				'campos' => Array(
					'app_code',
					'app_secret',
					'app_token',
					'app_token_time',
					'app_expires_in',
				),
				'extra' => 
					"WHERE id_dados='".$id_hosts."'"
			));
			
			// ===== Verificar se o token está expirado.
			
			$gerar_token = false;
			
			if($hosts_paypal['app_token']){
				if((int)$hosts_paypal['app_token_time']+(int)$hosts_paypal['app_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
			
			// ===== Se o token estiver expirado, gerar um novo.
			
			if($gerar_token){
				$retorno = paypal_token_generate(Array(
					'clientId' => $hosts_paypal['app_code'],
					'secret' => $hosts_paypal['app_secret'],
					'live' => true,
					'id_hosts' => $id_hosts,
				));
				
				if(!$retorno['error']){
					$hosts_paypal['app_token'] = $retorno['token'];
				}
			}
			
			$token = $hosts_paypal['app_token'];
		} else {
			// ===== Pegar dados do PayPal.
			
			$hosts_paypal = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_paypal',
				'campos' => Array(
					'app_sandbox_code',
					'app_sandbox_secret',
					'app_sandbox_token',
					'app_sandbox_token_time',
					'app_sandbox_expires_in',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
			));
			
			// ===== Verificar se o token está expirado.
			
			$gerar_token = false;
			
			if($hosts_paypal['app_sandbox_token']){
				if((int)$hosts_paypal['app_sandbox_token_time']+(int)$hosts_paypal['app_sandbox_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
			
			// ===== Se o token estiver expirado, gerar um novo.
			
			if($gerar_token){
				$retorno = paypal_token_generate(Array(
					'clientId' => $hosts_paypal['app_sandbox_code'],
					'secret' => $hosts_paypal['app_sandbox_secret'],
					'id_hosts' => $id_hosts,
				));
				
				if(!$retorno['error']){
					$hosts_paypal['app_sandbox_token'] = $retorno['token'];
				}
			}
			
			$token = $hosts_paypal['app_sandbox_token'];
		}
		
		// ===== Pegar dados do pedido.
		
		$hosts_pedidos = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_pedidos',
			'campos' => Array(
				'id_hosts_pedidos',
				'total',
			),
			'extra' => 
				"WHERE codigo='".$codigo."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
		
		// ===== Verificar se o pay_id enviado existe.
		
		$hosts_paypal_pagamentos = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_paypal_pagamentos',
			'campos' => Array(
				'id_hosts_paypal_pagamentos',
			),
			'extra' => 
				"WHERE pay_id='".$pay_id."'"
				." AND id_hosts='".$id_hosts."'"
				." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
		));
		
		if(!$hosts_paypal_pagamentos){
			$alerta = gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-execute-pay-id-not-found'));
			
			$retorno['status'] = 'PAY_ID_NOT_FOUND';
			$retorno['error-msg'] = $alerta;
			$retorno['error'] = true;
			
			return $retorno;
		}
		
		// ===== Fazer a requisição ao servidor do PayPal.
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "https://api.".(isset($live) ? "" : "sandbox.")."paypal.com/v1/payments/payment/".$pay_id."/execute/");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$token,
			(isset($paypalButton) ? 'paypal-partner-attribution-id: B2make_Ecom_EC' : 'paypal-partner-attribution-id: B2make_Ecom_PPPlus'),
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, '{"payer_id" : "'.$payerID.'" }');
		
		$result = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		// ===== Tratar o retorno da requisição.
		
		if(empty($result)){
			$retorno = Array(
				'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-execute-empty-result')),
				'status' => 'empty-result',
				'error' => true,
			);
		} else {
			$json = json_decode($result);
			
			if($httpcode == '400'){
				switch($json->name){
					case 'INSTRUMENT_DECLINED': 
						$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-execute-not-autorized'));
					break;
					default:
						$erro_var = gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-execute-error'));
						
						$erro_var = modelo_var_troca($erro_var,"#error#",$json->name);
						$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->message);
				}
				
				$retorno = Array(
					'error-msg' => $erro_var,
					'status' => 'error-result',
					'error' => true,
				);
			} else {
				// ===== Retorno do PayPal.
				
				$pay_id_confirmado = $json->id;
				$final_id = $json->transactions[0]->related_resources[0]->sale->id;
				$status = $json->transactions[0]->related_resources[0]->sale->state;
				
				// ===== Atualizar o status do pagamento, incluir o número de parcelas escolhidas e incluir o final_id.
				
				banco_update_campo('final_id',$final_id);
				banco_update_campo('status',$status);
				banco_update_campo('parcelas',$installmentsValue,true);
				banco_update_campo('data_modificacao','NOW()',true);
				
				banco_update_executar('hosts_paypal_pagamentos',"WHERE pay_id='".$pay_id_confirmado."' AND id_hosts='".$id_hosts."' AND id_hosts_pedidos='".$id_hosts_pedidos."'");
				
				// ===== Verificar se o pagamento ficou pendente ou já concluído.
				
				if($status == "completed"){
					$pending = false;
				} else {
					$pending = true;
				}
				
				// ===== Retornar dados para o host.
				
				$retorno = Array(
					'pending' => $pending,
					'final_id' => $final_id,
					'status' => 'OK',
					'completed' => true,
				);
			}
		}

		curl_close($ch);
	} else {
		$retorno = Array(
			'error-msg' => gestor_variaveis(Array('modulo' => 'paypal','id' => 'payment-mandatory-fields')),
			'status' => 'mandatory-fields',
			'error' => true,
		);
	}
	
	return $retorno;
}

?>