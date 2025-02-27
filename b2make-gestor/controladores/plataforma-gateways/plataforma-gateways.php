<?php

// ===== Plataforma responsável por receber solicitações de 'gateways de pagamentos'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-gateways';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

function plataforma_historico_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// alteracoes - Array - Opcional - Conjunto de dados relativos a alteração que foi feita num dados registro.
		// campo - String - Opcional - Identificador do campo que foi alterado caso necessário. Sistema buscará o valor na linguagem: código do módulo/id do campo.
		// opcao - String - Opcional - Opção extra necessária para desparar pequenos hacks no histórico que não segue um padrão.
		// filtro - String - Opcional - Filtro necessário para formatar os dados.
		// alteracao - String - Opcional - Identificador da alteração. Sistema buscará o valor na linguagem: interface/id do campo.
		// alteracao_txt - String - Opcional - Caso necessário completar uma alteração, este campo pode ser passado com o valor literal da alteração.
		// valor_antes - String - Opcional - Valor antes da alteração.
		// valor_depois - String - Opcional - Valor após a alteração.
		// tabela - Array - Opcional - Tabela que será comparada com os valores antes e depois caso definido para trocar ids por nomes.
			// nome - String - Obrigatório - nome da tabela do banco de dados.
			// campo - String - Obrigatório - campo da tabela que será retornado como valor textual dos ids.
			// id_numerico - String - Obrigatório - identificador numérico dos dados da tabela.
	// deletar - Bool - Opcional - Caso definido, incrementar em 1 a versão, pois deletar a inclusão de histórico é anterior a atualização final do registro para status='D'.
	// id_numerico_manual - Int - Opcional - Caso definido, o id_numerico do registro será manualmente definido.
	// modulo_id - String - Opcional - Caso definido, vinculará o registro manualmente neste módulo.
	// sem_id - Bool - Opcional - Caso definido, não vinculará nenhum ID ao histórico.
		// versao - Int - Opcional - Definir manualmente a versão do registro.
	// tabela - Array - Opcional - Tabela que será usada ao invés da tabela principal do módulo.
		// nome - String - Obrigatório - nome da tabela do banco de dados.
		// versao - String - Obrigatório - Campo versao da tabela do banco de dados.
		// id_numerico - String - Obrigatório - identificador numérico dos dados da tabela.
		// id - Bool - Opcional - Caso definido, vai usar o campo id como campo referencial e não o id_numerico.
	
	// ===== Possibilidades
	/*
		Na inclusão há 3 possibilidades de passagem por parâmetros:
		
		1 - campo - o histórico só mostrará o nome do campo que foi alterado.
		2 - campo, valor_antes e valor_depois - o histórico mostra o valor antes e depois de uma alteração.
		3 - alteracao, alteracao_txt [campo] - o histórico mostra um valor pré-definido, caso necessário informar um valor a mais, basta informar a 'alteracao_txt' e se quiser também o 'campo'. E caso o valor do 'alteracao' tenha marcação #campo# , o sistema subistituirá esse valor com o nome do 'campo'.
	*/
	// ===== 
	
	if(isset($alteracoes)){
		$usuario = gestor_usuario();
		
		if(!isset($tabela)){
			$tabela = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela'];
		}
		
		if(!isset($sem_id)){
			if(isset($id_numerico_manual)){
				$id_numerico = $id_numerico_manual;
			} else {
				$id_numerico = interface_modulo_variavel_valor(Array('variavel' => $tabela['id_numerico']));
			}

			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$tabela['versao'],
				))
				,
				$tabela['nome'],
				"WHERE ".$tabela['id_numerico']."='".$id_numerico."'"
			);
			
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		
		foreach($alteracoes as $alteracao){
			$campos = null; $campo_sem_aspas_simples = null;
			
			if(isset($_GESTOR['host-id'])){ $campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 												$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "modulo"; $campo_valor = (isset($modulo_id) ? $modulo_id : $_GESTOR['modulo-id']); 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			if(!isset($sem_id)){
				$campo_nome = "id"; $campo_valor = $id_numerico; 																$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			}
			
			$campo_nome = "versao"; $campo_valor = (isset($deletar) ? 1 : 0) + (int)$versao_bd; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			$campo_nome = "campo"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "opcao"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "filtro"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao_txt"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "valor_antes"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "valor_depois"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "tabela"; if(isset($alteracao[$campo_nome])){$campo_valor = json_encode($alteracao[$campo_nome]);		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			$campo_nome = "data"; $campo_valor = 'NOW()'; 				$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"historico"
			);
		}
	}
}

// =========================== Funções da Plataforma

function plataforma_paypal_gestor_taxa_return(){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('log');
	
	// ===== Caso exista retorno disparado pelo PayPal, gravar log para futuras implementações. É que a cobrança de taxa é feita entre os servidores e não tem contato diretamente com usuários.
	
	switch($_REQUEST['action']){
		case 'cancel':
		case 'return':
			log_disco('[plataforma_paypal_gestor_taxa_return] - action: '.$_REQUEST['action']);
		break;
		default:
			log_disco('[plataforma_paypal_gestor_taxa_return] - no-action');
	}
}

function plataforma_gateways_paypal_webhooks(){
	global $_GESTOR;
	
	// ===== Variáveis controles e recebidas.
	
	$ambiente = $_GESTOR['caminho'][2];
	$pub_id = $_GESTOR['caminho'][3];
	$verificar_webhook = true;
	$webhook_verified = false;
	
	// ===== Só continue se houver o ambiente e o pub_id definidos.
	
	if($ambiente && $pub_id){
		$hosts = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts',
			'campos' => Array(
				'id_hosts',
			),
			'extra' => 
				"WHERE pub_id='".$pub_id."'"
		));
		
		// ===== Verfica se o host foi encontrado.
		
		if($hosts){
			// ===== Incluir bibliotecas.
			
			gestor_incluir_biblioteca('paypal');
			
			// ===== Pegar o identificador do host.
			
			$id_hosts = $hosts['id_hosts'];
			
			// ===== Pegar o token dado o ambiente.
			
			if($ambiente == 'live'){
				$hosts_paypal = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_paypal',
					'campos' => Array(
						'app_code',
						'app_secret',
						'app_token',
						'app_token_time',
						'app_expires_in',
						'app_webhook_id',
					),
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
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
				$webhook_id = $hosts_paypal['app_webhook_id'];
			} else {
				$hosts_paypal = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_paypal',
					'campos' => Array(
						'app_sandbox_code',
						'app_sandbox_secret',
						'app_sandbox_token',
						'app_sandbox_token_time',
						'app_sandbox_expires_in',
						'app_sandbox_webhook_id',
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
				$webhook_id = $hosts_paypal['app_sandbox_webhook_id'];
			}
			
			// ===== Verificar se o webhook é válido.
			
			if($verificar_webhook){
				// ===== Pegar informações da requisição.
				
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
				
				// ===== Validar os dados diretamente no servidor do PayPal.
				
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

				// ===== Tratar o retorno da validação.
				
				gestor_incluir_biblioteca('log');
				
				if(empty($result)){
					log_disco('[plataforma_gateways_paypal_webhooks][verificar_webhook] - empty result - id_hosts: '.$id_hosts);
				} else {
					$json = json_decode($result,true);
					
					if($json['verification_status'] == 'SUCCESS'){
						$webhook_verified = true;
					} else {
						log_disco('[plataforma_gateways_paypal_webhooks][verificar_webhook] - verification_status FAILURE - id_hosts: '.$id_hosts . ' - JSON: '.print_r($json,true));
					}
				}
				
				curl_close($ch);
			}
			
			// ===== Caso verificado, dar continuidade a mudança do estado de um pedido.
			
			if($webhook_verified){
				// ===== Pegar os valores da requisição.
				
				if($verificar_webhook){
					$webhook_event = $obj['webhook_event'];
					
					$event_type = $webhook_event->event_type;
					$invoice_number = $webhook_event->resource->invoice_number;
					$pay_id = $webhook_event->resource->parent_payment;
					$status = $webhook_event->resource->state;
				} else {
					$event_type = 'PAYMENT.SALE.COMPLETED';
					$invoice_number = 'E1131';
					$pay_id = 'PAY-6XN0180711637743TLPXSKPY';
					$status = 'completed';
				}
				
				// ===== Verificar se existe o pedido no banco de dados para cada tipo de evento disparado pelo webhook.
				
				switch($event_type){
					case 'RISK.DISPUTE.CREATED':
					case 'CUSTOMER.DISPUTE.CREATED':
						// ===== Dados do pedido que entrou em disputa.
					
						$invoice_number = $webhook_event->resource->disputed_transactions[0]->invoice_number;
						$dispute_id = $webhook_event->id;
						
						// ===== Pegar os dados do pedido no banco.
					
						$hosts_pedidos = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_pedidos',
							'campos' => Array(
								'id_hosts_pedidos',
							),
							'extra' => 
								"WHERE codigo='".$invoice_number."'"
								." AND id_hosts='".$id_hosts."'"
						));
						
						// ===== Se existir o pedido, pegar o id do mesmo e do pagamento.
						
						if($hosts_pedidos){
							$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
							
							// ===== Pegar os dados do pagamento no banco.
						
							$hosts_paypal_pagamentos = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_paypal_pagamentos',
								'campos' => Array(
									'final_id',
								),
								'extra' => 
									"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							$final_id = $hosts_paypal_pagamentos['final_id'];
						}
					break;
					default:
						// ===== Pegar os dados do pagamento no banco.
					
						$hosts_paypal_pagamentos = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_paypal_pagamentos',
							'campos' => Array(
								'id_hosts_pedidos',
								'final_id',
							),
							'extra' => 
								"WHERE pay_id='".$pay_id."'"
								." AND id_hosts='".$id_hosts."'"
						));
						
						if($hosts_paypal_pagamentos){
							$id_hosts_pedidos = $hosts_paypal_pagamentos['id_hosts_pedidos'];
							$final_id = $hosts_paypal_pagamentos['final_id'];
						}
				}
				
				// ===== Caso tenha encontrado o pedido, fazer o tratamento de cada mudança de estado.
				
				if(isset($id_hosts_pedidos)){
					// ===== Caso um pedido já tenha sido 'completado' por uma outra requisição de pagamento, apenas mudar o estado da requisição atual e finalizar.
					
					switch($event_type){
						case 'RISK.DISPUTE.CREATED':
						case 'CUSTOMER.DISPUTE.CREATED':
						
						break;
						default:
							$hosts_paypal_pagamentos = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_paypal_pagamentos',
								'campos' => Array(
									'id_hosts_pedidos',
								),
								'extra' => 
									"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
									." AND pay_id!='".$pay_id."'"
									." AND status='completed'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							if($hosts_paypal_pagamentos){
								banco_update_campo('status',$status);
								
								banco_update_executar('hosts_paypal_pagamentos',"WHERE pay_id='".$pay_id."'");
								
								// ===== Retornar ok.
								
								plataforma_gateways_200();
							}
					}
					
					// ===== Código do pedido.
					
					$codigo = $invoice_number;
					
					// ===== Dados do pedido.
					
					$hosts_pedidos = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_pedidos',
						'campos' => Array(
							'id_hosts_usuarios',
						),
						'extra' => 
							"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Dados do usuário.
					
					$hosts_usuarios = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios',
						'campos' => Array(
							'nome',
							'email',
						),
						'extra' => 
							"WHERE id_hosts_usuarios='".$hosts_pedidos['id_hosts_usuarios']."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					$nome = $hosts_usuarios['nome'];
					$email = $hosts_usuarios['email'];
					
					// ===== Variáveis do Host.
					
					gestor_incluir_biblioteca('host');
					$loja_nome = host_loja_nome(Array('id_hosts' => $id_hosts));
					
					// ===== Mudar valores padrões do assunto e assinatura.
					
					$email_assunto = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'emails-subject'));
					$email_assinatura = gestor_componente(Array('id' => 'plataforma-gateways-emails-assinatura'));
					
					$email_assunto = modelo_var_troca_tudo($email_assunto,"[[codigo]]",$codigo);
					$email_assinatura = modelo_var_troca_tudo($email_assinatura,"@[[loja-nome]]@",$loja_nome);
					
					// ===== Incluir assinatura no layout da mensagem.
					
					$mensagemVariaveis[] = Array('variavel' => '@[[assinatura]]@','valor' => $email_assinatura);
					
					// ===== Tratar cada estado.
					
					switch($event_type){
						case 'PAYMENT.SALE.PENDING':
							$mensagemLayout = 'plataforma-gateways-mensagem-pendente';
							$statusTitulo = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'status-pending'));
							$statusPedido = 'pendente';
						break;
						case 'PAYMENT.SALE.COMPLETED':
							// ===== Email layout e status.
							
							$mensagemLayout = 'plataforma-gateways-mensagem-pago';
							$statusTitulo = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'status-paid'));
							$statusPedido = 'pago';
							
							// ===== URL das compras do usuário.
							
							$dominio = host_url(Array('opcao' => 'full','id_hosts' => $id_hosts));
							$url = '<a href="'.$dominio.'meus-pedidos/">'.$dominio.'meus-pedidos/</a>';
							
							// ===== URL do minhas compras.
							
							$mensagemVariaveis[] = Array('variavel' => '@[[url]]@','valor' => $url);
							
							// ===== Dados dos serviços do pedido.
							
							$hosts_pedidos_servicos = banco_select(Array(
								'tabela' => 'hosts_pedidos_servicos',
								'campos' => Array(
									'id_hosts_servicos',
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							if($hosts_pedidos_servicos)
							foreach($hosts_pedidos_servicos as $pedSer){
								// ===== Identificador do serviço.
								
								$id_hosts_servicos = $pedSer['id_hosts_servicos'];
								
								// ===== Pegar dados do serviço.
								
								$hosts_servicos = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos',
									'campos' => Array(
										'quantidade_pedidos_pendentes',
										'quantidade_pedidos',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts='".$id_hosts."'"
								));
								
								// ===== Quantidade para alterar no estoque início.
								
								$pedidosQuantidade = (int)$hosts_servicos['quantidade_pedidos'];
								$pedidosPendentesQuantidade = (int)$hosts_servicos['quantidade_pedidos_pendentes'];
								$quantidadeEstoqueAlterar = (int)$pedSer['quantidade'];
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade_pedidos=".$pedidosQuantidade." + ".$quantidadeEstoqueAlterar.","
									."quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." - ".$quantidadeEstoqueAlterar,
									"hosts_servicos",
									"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									." AND id_hosts='".$id_hosts."'"
								);
							}
							
							// ===== Dados das variações dos serviços do pedido.
							
							$hosts_pedidos_servico_variacoes = banco_select(Array(
								'tabela' => 'hosts_pedidos_servico_variacoes',
								'campos' => Array(
									'id_hosts_servicos',
									'id_hosts_servicos_variacoes',
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							if($hosts_pedidos_servico_variacoes)
							foreach($hosts_pedidos_servico_variacoes as $pedSer){
								// ===== Identificador do serviço.
								
								$id_hosts_servicos_variacoes = $pedSer['id_hosts_servicos_variacoes'];
								$id_hosts_servicos = $pedSer['id_hosts_servicos'];
								
								// ===== Pegar dados do serviço.
								
								$hosts_servicos_variacoes = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos_variacoes',
									'campos' => Array(
										'quantidade_pedidos_pendentes',
										'quantidade_pedidos',
									),
									'extra' => 
										"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
										." AND id_hosts='".$id_hosts."'"
								));
								
								// ===== Quantidade para alterar no estoque início.
								
								$pedidosQuantidade = (int)$hosts_servicos_variacoes['quantidade_pedidos'];
								$pedidosPendentesQuantidade = (int)$hosts_servicos_variacoes['quantidade_pedidos_pendentes'];
								$quantidadeEstoqueAlterar = (int)$pedSer['quantidade'];
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade_pedidos=".$pedidosQuantidade." + ".$quantidadeEstoqueAlterar.","
									."quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." - ".$quantidadeEstoqueAlterar,
									"hosts_servicos_variacoes",
									"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
									." AND id_hosts='".$id_hosts."'"
								);
								
								// ===== Pegar dados do serviço.
								
								$hosts_servicos = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos',
									'campos' => Array(
										'quantidade_pedidos_pendentes',
										'quantidade_pedidos',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts='".$id_hosts."'"
								));
								
								// ===== Quantidade para alterar no estoque início.
								
								$pedidosQuantidade = (int)$hosts_servicos['quantidade_pedidos'];
								$pedidosPendentesQuantidade = (int)$hosts_servicos['quantidade_pedidos_pendentes'];
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade_pedidos=".$pedidosQuantidade." + ".$quantidadeEstoqueAlterar.","
									."quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." - ".$quantidadeEstoqueAlterar,
									"hosts_servicos",
									"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									." AND id_hosts='".$id_hosts."'"
								);
							}
							
							// ===== Cobrança da comissão do gestor.
							
							gestor_incluir_biblioteca('paypal');
							
							paypal_reference_gestor_taxa(Array(
								'id_hosts' => $id_hosts,
								'id_hosts_pedidos' => $id_hosts_pedidos,
							));
						break;
						case 'RISK.DISPUTE.CREATED':
						case 'CUSTOMER.DISPUTE.CREATED':
							$mensagemLayout = 'plataforma-gateways-mensagem-disputa';
							$statusTitulo = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'status-dispute'));
							$statusPedido = 'disputa';
						break;
						case 'PAYMENT.SALE.REFUNDED':
							$mensagemLayout = 'plataforma-gateways-mensagem-reembolso';
							$statusTitulo = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'status-refunded'));
							$statusPedido = 'reembolso';
						break;
						case 'PAYMENT.SALE.DENIED':
							$mensagemLayout = 'plataforma-gateways-mensagem-negado';
							$statusTitulo = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'status-denied'));
							$statusPedido = 'negado';
						break;
						default: 
							$mensagemLayout = 'gateways-de-pagamentos-mensagem-mudanca-de-estado';
							$statusTitulo = $event_type;
							$statusPedido = $event_type;
					}
					
					// ===== Atualizar estado do pedido.
					
					banco_update
					(
						"data_modificacao=NOW(),".
						"versao=versao+1,".
						"status='".$statusPedido."'",
						"hosts_pedidos",
						"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
						." AND id_hosts='".$id_hosts."'"
					);
					
					// ===== Incluir o histórico da alteração no pedido.
					
					gestor_incluir_biblioteca('log');
					
					log_controladores(Array(
						'id_hosts' => $id_hosts,
						'controlador' => 'paypal-webhook',
						'id' => $id_hosts_pedidos,
						'tabela' => Array(
							'nome' => 'hosts_pedidos',
							'versao' => 'versao',
							'id_numerico' => 'id_hosts_pedidos',
						),
						'alteracoes' => Array(
							Array(
								'modulo' => 'pedidos',
								'alteracao' => 'update-status',
								'alteracao_txt' => 'Alteração do status para: <b>'.$statusTitulo.'</b> | <b>ID Final: '.$final_id.'</b> | <b>Evento: '.$event_type.'</b>'.(isset($pay_id) ? ' | <b>ID Requisição: '.$pay_id.'</b>' : '').(isset($dispute_id) ? ' | <b>ID Disputa: '.$dispute_id.'</b>' : ''),
							)
						),
					));
					
					// ===== Alterar campos do assunto e da mensagem.
					
					$email_assunto = modelo_var_troca_tudo($email_assunto,"[[status]]",$statusTitulo);
					
					$mensagemVariaveis[] = Array('variavel' => '@[[status]]@','valor' => $statusTitulo);
					$mensagemVariaveis[] = Array('variavel' => '@[[codigo]]@','valor' => $codigo);
					$mensagemVariaveis[] = Array('variavel' => '@[[titulo]]@','valor' => $loja_nome);
					
					// ===== Enviar email com a mudança de estado.
					
					gestor_incluir_biblioteca('comunicacao');
					
					if(comunicacao_email(Array(
						'destinatarios' => Array(
							Array(
								'email' => $email,
								'nome' => $nome,
							),
						),
						'mensagem' => Array(
							'assunto' => $email_assunto,
							'htmlLayoutID' => $mensagemLayout,
							'htmlVariaveis' => $mensagemVariaveis,
						),
					))){
						// Email de mudança de estado enviado com sucesso!
					}
					
					// ===== Atualizar host do cliente.
					
					gestor_incluir_biblioteca('api-cliente');
					
					$retorno = api_cliente_pedidos(Array(
						'opcao' => 'atualizar',
						'id_hosts' => $id_hosts,
						'pedidos' => Array(
							$id_hosts_pedidos,
						),
					));
					
					if(!$retorno['completed']){
						log_disco('[plataforma_gateways_paypal_webhooks][api_cliente_pedidos] - api error - id_hosts: '.$id_hosts.' - retorno: '.print_r($retorno,true));
					}
					
					// ===== Retornar ok.
					
					plataforma_gateways_200();
				}
			}
		}
	}
	
	return null;
}

// =========================== Funções de Acesso

function plataforma_gateways_404(){
	http_response_code(404);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '404',
		'info' => 'JSON not found',
	));
	exit;
}

function plataforma_gateways_200(){
	http_response_code(200);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'OK',
	));
	exit;
}

function plataforma_gateways_start(){
	global $_GESTOR;
	global $_INDEX;
	
	// ===== Verifica a opção, executa interface caso encontrado e retorna os dados
	
	switch($_GESTOR['caminho'][1]){
		case 'ppplus-webhooks': $dados = plataforma_gateways_paypal_webhooks(); break;
		case 'paypal-gestor-taxa-return': $dados = plataforma_paypal_gestor_taxa_return(); break;
	}
	
	// ===== Caso haja dados criados por alguma opção, retornar JSON e finalizar. Senão retornar JSON 404.
	
	if(isset($dados)){
		header("Content-Type: application/json; charset: UTF-8");
		echo json_encode($dados);
		exit;
	}
	
	plataforma_gateways_404();
}

// =========================== Inciar Plataforma

plataforma_gateways_start();

?>