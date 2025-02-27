<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'gateways-de-pagamentos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.12',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		
	),
);

// ===== PayPal.

function gateways_de_pagamentos_paypal_reference_create(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Criar a requisição do reference.
	
	gestor_incluir_biblioteca('paypal');
	
	$retorno = paypal_reference_create(Array(
		'id_hosts' => $id_hosts,
	));
	
	// ===== Tratar o retorno.
	
	if($retorno['error']){
		if(existe($retorno['error-msg'])){
			$msg = $retorno['error-msg'];
		} else {
			$msg = gestor_variaveis(Array('modulo' => 'paypal','id' => 'error-not-defined'));
		}
	} else {
		$msg = gestor_variaveis(Array('modulo' => 'paypal','id' => 'error-not-defined'));
	}
	
	// ===== Alertar o usuário.
	
	interface_alerta(Array(
		'redirect' => true,
		'msg' => $msg
	));
	
	// ===== Redirecionar a página.
	
	gestor_redirecionar($_GESTOR['modulo-id'].'/paypal/');
}

function gateways_de_pagamentos_paypal_reference_return(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Tratar o retorno a requisição do reference.
	
	gestor_incluir_biblioteca('paypal');
	
	$retorno = paypal_reference_return(Array(
		'id_hosts' => $id_hosts,
	));
	
	// ===== Tratar o retorno.
	
	if($retorno['completed']){
		$msg = gestor_variaveis(Array('modulo' => 'paypal','id' => 'reference-return-success'));
	} else {
		if($retorno['error']){
			if(existe($retorno['error-msg'])){
				$msg = $retorno['error-msg'];
			} else {
				$msg = gestor_variaveis(Array('modulo' => 'paypal','id' => 'error-not-defined'));
			}
		} else {
			$msg = gestor_variaveis(Array('modulo' => 'paypal','id' => 'error-not-defined'));
		}
	}
	
	// ===== Alertar o usuário.
	
	interface_alerta(Array(
		'redirect' => true,
		'msg' => $msg
	));
	
	// ===== Redirecionar a página.
	
	gestor_redirecionar($_GESTOR['modulo-id'].'/paypal/');
}

function gateways_de_pagamentos_paypal_desinstalar(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Pegar todos os dados do PayPal no banco.
	
	$hosts_paypal = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_paypal',
		'campos' => '*',
		'extra' => 
			"WHERE id_hosts='".$id_hosts."'"
	));
	
	// ===== Senão estiver instalado, não prosseguir.
	
	if(!$hosts_paypal['app_installed']){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-not-installed'))
		));
		
		// ===== Redirecionar para a página do PayPal.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/paypal/');
	}
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca('paypal');
	
	// ===== Verificar se há o webhook id do live. Se sim, excluir o mesmo.
	
	if($hosts_paypal['app_webhook_id']){
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
		
		// ===== Excluir o webhook.
		
		$token = $hosts_paypal['app_token'];
		$id = $hosts_paypal['app_webhook_id'];
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/notifications/webhooks/".$id);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$token,
		));

		$result = curl_exec($ch);

		curl_close($ch);
	}
	
	// ===== Verificar se há o webhook id do sandbox. Se sim, excluir o mesmo.
	
	if($hosts_paypal['app_sandbox_webhook_id']){
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
		
		// ===== Excluir o webhook.
		
		$token = $hosts_paypal['app_sandbox_token'];
		$id = $hosts_paypal['app_sandbox_webhook_id'];
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/notifications/webhooks/".$id);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$token,
		));

		$result = curl_exec($ch);

		curl_close($ch);
	}
	
	// ===== Resetar variáveis no banco.
	
	banco_update_campo('app_code','NULL',true);
	banco_update_campo('app_secret','NULL',true);
	banco_update_campo('app_token','NULL',true);
	banco_update_campo('app_token_time','NULL',true);
	banco_update_campo('app_expires_in','NULL',true);
	banco_update_campo('app_webhook_id','NULL',true);
	banco_update_campo('app_sandbox_code','NULL',true);
	banco_update_campo('app_sandbox_secret','NULL',true);
	banco_update_campo('app_sandbox_token','NULL',true);
	banco_update_campo('app_sandbox_token_time','NULL',true);
	banco_update_campo('app_sandbox_expires_in','NULL',true);
	banco_update_campo('app_sandbox_webhook_id','NULL',true);
	banco_update_campo('app_active','NULL',true);
	banco_update_campo('app_installed','NULL',true);
	banco_update_campo('app_live','NULL',true);
	banco_update_campo('reference_installed','NULL',true);
	banco_update_campo('reference_id','NULL',true);
	banco_update_campo('reference_cancel_url','NULL',true);
	banco_update_campo('paypal_plus_inactive','NULL',true);
	
	banco_update_executar('hosts_paypal',"WHERE id_hosts='".$id_hosts."'");
	
	// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_variaveis(Array(
		'opcao' => 'paypal',
	));
	
	if(!$retorno['completed']){
		$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
		
		$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
	} else {
		// ===== Alerta desinstalado com sucesso.
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-uninstall-success'))
		));
	}
	
	// ===== Cancelar o reference caso exista.
	
	if($hosts_paypal['reference_id']){
		if(existe($hosts_paypal['reference_cancel_url'])){
			$retorno = paypal_reference_cancel(Array(
				'url' => $hosts_paypal['reference_cancel_url'],
			));
		}
	}
	
	// ===== Senão redirecionar para a página do PayPal.
	
	gestor_redirecionar($_GESTOR['modulo-id'].'/paypal/');
}

function gateways_de_pagamentos_paypal_instalar(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('host');
	
	// ===== Validação de campos obrigatórios
	
	interface_validacao_campos_obrigatorios(Array(
		'redirect' => $_GESTOR['modulo-id'].'/paypal/',
		'campos' => Array(
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'app_sandbox_code',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-sandbox-code')),
			),
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'app_sandbox_secret',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-sandbox-secret')),
			),
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'app_code',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-code')),
			),
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'app_secret',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-secret')),
			),
		)
	));
	
	// ===== Instalar o PayPal Live.
	
	$ch = curl_init();
	$clientId = $_REQUEST['app_code'];
	$secret = $_REQUEST['app_secret'];

	curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/oauth2/token");
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
		$erro_msg[] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-live-empty-result')); $erro = true;
	} else {
		$json = json_decode($result);
		
		if($json->error){
			$erro_var = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-live')); $erro = true;
			
			$erro_var = modelo_var_troca($erro_var,"#error#",$json->error);
			$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->error_description);
			
			$erro_msg[] = $erro_var;  $erro = true;
		} else {
			$access_token = $json->access_token;
			$expires_in = $json->expires_in;
			
			banco_update_campo('app_code',$clientId);
			banco_update_campo('app_secret',$secret);
			banco_update_campo('app_token',$access_token);
			banco_update_campo('app_token_time',time());
			banco_update_campo('app_expires_in',$expires_in);
			
			$ppplus_live_installed = true;
		}
	}

	curl_close($ch);
	
	// ===== Instalar o Webhook live.
	
	if($ppplus_live_installed){
		$obj['url'] = $_GESTOR['url-full-http'].'_gateways/ppplus-webhooks/live/'.host_pub_id().'/';
		$obj['event_types'] = Array(
			Array('name' => 'PAYMENT.SALE.COMPLETED'),
			Array('name' => 'PAYMENT.SALE.DENIED'),
			Array('name' => 'PAYMENT.SALE.PENDING'),
			Array('name' => 'PAYMENT.SALE.REFUNDED'),
			Array('name' => 'RISK.DISPUTE.CREATED'),
			Array('name' => 'CUSTOMER.DISPUTE.CREATED'),
		);
		
		$json = json_encode($obj);
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/notifications/webhooks");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$access_token,
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		$result = curl_exec($ch);
		
		if(empty($result)){
			$erro_msg[] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-webhook-live-empty-result')); $erro = true;
		} else {
			$json = json_decode($result);
			
			if($json->error){
				$erro_var = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-webhook-live')); $erro = true;
				
				$erro_var = modelo_var_troca($erro_var,"#error#",$json->error);
				$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->error_description);
				
				$erro_msg[] = $erro_var;  $erro = true;
			} else {
				$webhook_id = $json->id;
				
				banco_update_campo('app_webhook_id',$webhook_id);
				
				$ppplus_live_webhook_installed = true;
			}
		}

		curl_close($ch);
	}
	
	// ===== Instalar o PayPal Sandbox.

	$ch = curl_init();
	$clientId = $_REQUEST['app_sandbox_code'];
	$secret = $_REQUEST['app_sandbox_secret'];

	curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/oauth2/token");
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
		$erro_msg[] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-sandbox-empty-result')); $erro = true;
	} else {
		$json = json_decode($result);
		
		if($json->error){
			$erro_var = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-sandbox')); $erro = true;
			
			$erro_var = modelo_var_troca($erro_var,"#error#",$json->error);
			$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->error_description);
			
			$erro_msg[] = $erro_var;  $erro = true;
		} else {
			$access_token = $json->access_token;
			$expires_in = $json->expires_in;
			
			banco_update_campo('app_sandbox_code',$clientId);
			banco_update_campo('app_sandbox_secret',$secret);
			banco_update_campo('app_sandbox_token',$access_token);
			banco_update_campo('app_sandbox_token_time',time());
			banco_update_campo('app_sandbox_expires_in',$expires_in);
			
			$ppplus_sandbox_installed = true;
		}
	}

	curl_close($ch);
	
	// ===== Instalar o Webhook sandbox.
	
	if($ppplus_sandbox_installed){
		$obj['url'] = $_GESTOR['url-full-http'].'_gateways/ppplus-webhooks/sandbox/'.host_pub_id().'/';
		$obj['event_types'] = Array(
			Array('name' => 'PAYMENT.SALE.COMPLETED'),
			Array('name' => 'PAYMENT.SALE.DENIED'),
			Array('name' => 'PAYMENT.SALE.PENDING'),
			Array('name' => 'PAYMENT.SALE.REFUNDED'),
			Array('name' => 'RISK.DISPUTE.CREATED'),
			Array('name' => 'CUSTOMER.DISPUTE.CREATED'),
		);
		
		$json = json_encode($obj);
		
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/notifications/webhooks");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$access_token,
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		$result = curl_exec($ch);
		
		if(empty($result)){
			$erro_msg[] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-webhook-sandbox-empty-result')); $erro = true;
		} else {
			$json = json_decode($result);
			
			if($json->error){
				$erro_var = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-webhook-sandbox')); $erro = true;
				
				$erro_var = modelo_var_troca($erro_var,"#error#",$json->error);
				$erro_var = modelo_var_troca($erro_var,"#error_description#",$json->error_description);
				
				$erro_msg[] = $erro_var;  $erro = true;
			} else {
				$webhook_id = $json->id;
				
				banco_update_campo('app_sandbox_webhook_id',$webhook_id);
				
				$ppplus_sandbox_webhook_installed = true;
			}
		}

		curl_close($ch);
	}
	
	// ===== Ativar o app e marcar ele como instalado.
	
	if(
		isset($ppplus_sandbox_webhook_installed) && 
		isset($ppplus_live_webhook_installed) && 
		isset($ppplus_live_installed) && 
		isset($ppplus_sandbox_installed)
	){
		banco_update_campo('app_active','1',true);
		banco_update_campo('app_installed','1',true);
	}
	
	// ===== Se der algum problema, alertar o usuário. Senão, atualizar banco de dados.
	
	if(isset($erro)){
		$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'error-install'));
		
		$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($alerta,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $alerta = modelo_tag_in($alerta,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		if($erro_msg)
		foreach($erro_msg as $item){
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#item#",$item);
			
			$alerta = modelo_var_in($alerta,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		$alerta = modelo_var_troca($alerta,'<!-- '.$cel_nome.' -->','');
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
	} else {
		// ===== Atualizar dados no banco de dados.
		
		banco_update_executar('hosts_paypal',"WHERE id_hosts='".$id_hosts."'");
		
		// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
		
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_variaveis(Array(
			'opcao' => 'paypal',
		));
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
		} else {
			// ===== Alerta instalado com sucesso.
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-install-success'))
			));
		}
	}
	
	// ===== Redirecionar para a página do PayPal.
	
	gestor_redirecionar($_GESTOR['modulo-id'].'/paypal/');
}

function gateways_de_pagamentos_paypal(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Desinstalação.
	
	if($_REQUEST['desinstalar']){
		gateways_de_pagamentos_paypal_desinstalar();
	}
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca('pagina');
	
	// ===== Selecionar dados do banco de dados.
	
	$hosts_paypal = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_paypal',
		'campos' => Array(
			'app_installed',
			'reference_installed',
			'app_active',
			'app_live',
			'paypal_plus_inactive',
		),
		'extra' => 
			"WHERE id_hosts='".$id_hosts."'"
	));
	
	// ===== Se não existir, criar.
	
	if(!isset($hosts_paypal)){
		banco_insert_name_campo('id_hosts',$id_hosts);
		
		banco_insert_name
		(
			banco_insert_name_campos(),
			"hosts_paypal"
		);
		
		$hosts_paypal = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_paypal',
			'campos' => Array(
				'app_installed',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
		));
	}
	
	// ===== Verificar se há necessidade de instalação.
	
	if($hosts_paypal['app_installed']){
		// ===== Instalado.
		
		// ===== Verificar se reference está habilitado.
		
		if($hosts_paypal['reference_installed']){
			// ===== Definir estado dos botões de alteração de estado do APP PayPal.
			
			if($hosts_paypal['app_active']){
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'ativarBtn','active blue');
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'inativarBtn','');
			}{
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'ativarBtn','');
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'inativarBtn','active blue');
			}
			
			if($hosts_paypal['app_live']){
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'liveBtn','active blue');
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'sandboxBtn','');
			}{
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'liveBtn','');
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'sandboxBtn','active blue');
			}
			
			if($hosts_paypal['paypal_plus_inactive']){
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'pppAtivoBtn','');
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'pppInativoBtn','active blue');
			}{
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'pppAtivoBtn','active blue');
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'pppInativoBtn','');
			}
			
			// ===== Incluir modal para confirmação de desinstalação.
			
			$modal = gestor_componente(Array(
				'id' => 'interface-modal-generico',
			));
			
			$modal = modelo_var_troca($modal,"#titulo#",gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'uninstall-confirm-title')));
			$modal = modelo_var_troca($modal,"#mensagem#",gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'uninstall-confirm-menssage')));
			$modal = modelo_var_troca($modal,"#botao-cancelar#",gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'uninstall-confirm-button-cancel')));
			$modal = modelo_var_troca($modal,"#botao-confirmar#",gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'uninstall-confirm-button-confirm')));
			
			$_GESTOR['pagina'] .= $modal;
			
			// ===== Apagar célula reference.
			
			$cel_nome = 'reference'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		} else {
			// ===== Pegar taxa do PayPal do Gestor.
			
			$config = gestor_incluir_configuracao(Array(
				'id' => 'paypal.config',
			));
			
			gestor_incluir_biblioteca('formato');
			
			$taxa = formato_dado_para('float-para-texto',$config['gestor-taxa']);
			
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#taxa#",$taxa);
			
			// ===== Apagar célula controles.
			
			$cel_nome = 'controles'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		}
		
		// ===== Apagar célula instalação.
		
		$cel_nome = 'instalacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Título da Página.
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#titulo#",$_GESTOR['pagina#titulo']);
		
		// ===== Incluir componentes
		
		interface_componentes_incluir(Array(
			'componente' => Array(
				'modal-alerta',
			)
		));
		
		// ===== Inclusão Interface
		
		$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
		$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
		
		// ===== Interface Javascript Vars
		
		if(!isset($_GESTOR['javascript-vars']['interface'])){
			$_GESTOR['javascript-vars']['interface'] = Array();
		}
	} else {
		// ===== Não instalado.
		
		// ===== Gravar registro no Banco
		
		if(isset($_REQUEST['instalar'])){
			gateways_de_pagamentos_paypal_instalar();
		}
		
		// ===== Interface opção.
		
		$_GESTOR['interface-opcao'] = 'adicionar-incomum';
		
		// ===== Interface adicionar finalizar opções
		
		$_GESTOR['interface'][$_GESTOR['interface-opcao']]['finalizar'] = Array(
			'formulario' => Array(
				'validacao' => Array(
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'app_sandbox_code',
						'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-sandbox-code')),
					),
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'app_sandbox_secret',
						'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-sandbox-secret')),
					),
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'app_code',
						'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-code')),
					),
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'app_secret',
						'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'app-secret')),
					),
				)
			)
		);
		
		// ===== Apagar célula controles e reference.
		
		$cel_nome = 'reference'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		$cel_nome = 'controles'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();	
}

// ===== Principal.

function gateways_de_pagamentos_raiz(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#titulo#",$_GESTOR['pagina#titulo']);
}

function gateways_de_pagamentos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function gateways_de_pagamentos_ajax_paypal(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Ação requerida.
	
	switch($_REQUEST['acao']){
		case 'ativar':
		case 'inativar':
			// ===== Ativar/Inativar PayPal.
			
			if($_REQUEST['acao'] == 'ativar'){
				banco_update_campo('app_active','1',true);
			} else {
				banco_update_campo('app_active','NULL',true);
			}
			
			banco_update_executar('hosts_paypal',"WHERE id_hosts='".$id_hosts."'");
			
			// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_variaveis(Array(
				'opcao' => 'paypal',
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				$_GESTOR['ajax-json'] = Array(
					'status' => 'ERROR',
					'msg' => $alerta,
				);
			} else {
				// ===== Retornar Ok.
			
				$_GESTOR['ajax-json'] = Array(
					'status' => 'Ok',
				);
			}
		break;
		case 'live':
		case 'sandbox':
			// ===== Alterar ambiente Live/Sandbox do PayPal.
			
			if($_REQUEST['acao'] == 'live'){
				banco_update_campo('app_live','1',true);
			} else {
				banco_update_campo('app_live','NULL',true);
			}
			
			banco_update_executar('hosts_paypal',"WHERE id_hosts='".$id_hosts."'");
			
			// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_variaveis(Array(
				'opcao' => 'paypal',
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				$_GESTOR['ajax-json'] = Array(
					'status' => 'ERROR',
					'msg' => $alerta,
				);
			} else {
				// ===== Retornar Ok.
			
				$_GESTOR['ajax-json'] = Array(
					'status' => 'Ok',
				);
			}
		break;
		case 'pppAtivar':
		case 'pppInativar':
			// ===== Alterar ambiente Live/Sandbox do PayPal.
			
			if($_REQUEST['acao'] == 'pppInativar'){
				banco_update_campo('paypal_plus_inactive','1',true);
			} else {
				banco_update_campo('paypal_plus_inactive','NULL',true);
			}
			
			banco_update_executar('hosts_paypal',"WHERE id_hosts='".$id_hosts."'");
			
			// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_variaveis(Array(
				'opcao' => 'paypal',
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				$_GESTOR['ajax-json'] = Array(
					'status' => 'ERROR',
					'msg' => $alerta,
				);
			} else {
				// ===== Retornar Ok.
			
				$_GESTOR['ajax-json'] = Array(
					'status' => 'Ok',
				);
			}
		break;
		default:
			$_GESTOR['ajax-json'] = Array(
				'status' => 'acaoNaoDefinida',
			);
	}
}

// ==== Start

function gateways_de_pagamentos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'paypal': gateways_de_pagamentos_ajax_paypal(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		gateways_de_pagamentos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'paypal': gateways_de_pagamentos_paypal(); break;
			case 'paypal-reference-create': gateways_de_pagamentos_paypal_reference_create(); break;
			case 'paypal-reference-return': gateways_de_pagamentos_paypal_reference_return(); break;
			default: gateways_de_pagamentos_raiz();
		}
		
		interface_finalizar();
	}
}

gateways_de_pagamentos_start();

?>