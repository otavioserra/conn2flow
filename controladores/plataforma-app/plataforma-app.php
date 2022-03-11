<?php

// ===== Plataforma responsável por receber solicitações do 'aplicativo mobile'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-app';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

function stream_php(){
	$json = file_get_contents('php://input');
	$arrAux = json_decode($json, true);
	
	if($arrAux){
		if(gettype($arrAux[0]) == "array"){
			foreach($arrAux[0] as $key => $valor){
				$_REQUEST[$key] = $valor;
			}
		} else {
			foreach($arrAux as $key => $valor){
				$_REQUEST[$key] = $valor;
			}
		}
	}
}

// =========================== Funções da Plataforma

function plataforma_app_baixar_voucher(){
	global $_GESTOR;
	
	$id_hosts = $_GESTOR['usuario-host-id'];
	
	if(isset($_REQUEST['opcao']) && isset($_REQUEST['codigo'])){
		$opcao = $_REQUEST['opcao'];
		$codigo = $_REQUEST['codigo'];
		
		// ===== Separar o pedido do JWT.
		
		$pedido_e_JWT = explode(".=",$codigo);
		
		$pedido = base64_decode($pedido_e_JWT[0]);
		$JWT = $pedido_e_JWT[1];
		
		// ===== Verificar se o pedido faz parte do host do usuário logado.
		
		$hosts_pedidos = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_pedidos',
			'campos' => Array(
				'voucher_chave',
				'status',
				'id_hosts_pedidos',
				'id_hosts_usuarios',
			),
			'extra' => 
				"WHERE codigo='".banco_escape_field($pedido)."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		if($hosts_pedidos){
			$voucher_chave = $hosts_pedidos['voucher_chave'];
			$status = $hosts_pedidos['status'];
			$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
			$id_hosts_usuarios = $hosts_pedidos['id_hosts_usuarios'];
			
			// ===== Verificar se o status é 'pago'.
			
			if($status == 'pago'){
				// ===== Validar o código do voucher enviado.
				
				gestor_incluir_biblioteca('autenticacao');
				
				$voucherCodigo = autenticacao_validar_jwt_chave_publica(Array(
					'token' => $JWT,
					'chavePublica' => $voucher_chave,
				));
				
				if($voucherCodigo){
					// ===== Verificar o status do voucher.
					
					$hosts_vouchers = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_vouchers',
						'campos' => Array(
							'status',
							'id_hosts_vouchers',
						),
						'extra' => 
							"WHERE codigo='".$voucherCodigo."'"
							." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					if($hosts_vouchers){
						$voucherStatus = $hosts_vouchers['status'];
						$id_hosts_vouchers = $hosts_vouchers['id_hosts_vouchers'];
						
						// ===== Verificar se o status do voucher é válido.
						
						if($voucherStatus == 'jwt-bd-expirado' || $voucherStatus == 'jwt-gerado'){
							// ===== Tratar cada opção enviada.
							
							switch($opcao){
								// ===== Verifica se o voucher enviado é válido.
								
								case 'verificar':
									// ===== Pegar dados do voucher.
								
									$hosts_vouchers = banco_select(Array(
										'unico' => true,
										'tabela' => 'hosts_vouchers',
										'campos' => Array(
											'id_hosts_servicos',
											'id_hosts_servicos_variacoes',
											'nome',
											'documento',
											'telefone',
											'loteVariacao',
										),
										'extra' => 
											"WHERE codigo='".$voucherCodigo."'"
											." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
											." AND id_hosts='".$id_hosts."'"
									));
									
									$dadosRetorno = Array(
										'voucherCodigo' => $voucherCodigo,
										'nome' => $hosts_vouchers['nome'],
										'documento' => $hosts_vouchers['documento'],
										'telefone' => $hosts_vouchers['telefone'],
									);
									
									// ===== Pegar os dados do lote e variação caso necessário.
									
									if($hosts_vouchers['loteVariacao']){
										// ===== Pegar os dados do pedido serviço variação.
										
										$hosts_pedidos_servico_variacoes = banco_select(Array(
											'unico' => true,
											'tabela' => 'hosts_pedidos_servico_variacoes',
											'campos' => Array(
												'nome_servico',
												'nome_lote',
												'nome_variacao',
											),
											'extra' => 
												"WHERE id_hosts_servicos='".$hosts_vouchers['id_hosts_servicos']."'"
												." AND id_hosts_servicos_variacoes='".$hosts_vouchers['id_hosts_servicos_variacoes']."'"
												." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
												." AND id_hosts='".$id_hosts."'"
										));
										
										$dadosRetorno['servicoNome'] = $hosts_pedidos_servico_variacoes['nome_servico'];
										$dadosRetorno['servicoLoteEVariacao'] = $hosts_pedidos_servico_variacoes['nome_lote'].' - '.$hosts_pedidos_servico_variacoes['nome_variacao'];
									} else {
										// ===== Pegar dados do pedido serviço.
									
										$hosts_pedidos_servicos = banco_select(Array(
											'unico' => true,
											'tabela' => 'hosts_pedidos_servicos',
											'campos' => Array(
												'nome',
											),
											'extra' => 
												"WHERE id_hosts_servicos='".$hosts_vouchers['id_hosts_servicos']."'"
												." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
												." AND id_hosts='".$id_hosts."'"
										));
										
										$dadosRetorno['servicoNome'] = $hosts_pedidos_servicos['nome'];
									}
									
									// ===== Retornar os dados.
									
									plataforma_app_200($dadosRetorno);
								break;
								case 'baixar':
									// ===== Atualizar o status do voucher no banco de dados.
									
									banco_update_campo('status','usado');
									banco_update_campo('data_uso','NOW()',true);
									
									banco_update_executar('hosts_vouchers',"WHERE id_hosts_vouchers='".$id_hosts_vouchers."'");
									
									// ===== Atualizar pedido no banco.
									
									banco_update_campo('data_modificacao','NOW()',true);
									banco_update_campo('versao','versao+1',true);
									
									banco_update_executar('hosts_pedidos',"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'");
									
									// ===== Gravar histórico no pedido.
									
									gestor_incluir_biblioteca('log');
									
									log_usuarios(Array(
										'id_hosts' => $id_hosts,
										'id_usuarios' => $_GESTOR['usuario-id'],
										'id' => $id_hosts_pedidos,
										'tabela' => Array(
											'nome' => 'hosts_pedidos',
											'versao' => 'versao',
											'id_numerico' => 'id_hosts_pedidos',
										),
										'alteracoes' => Array(
											Array(
												'modulo' => 'pedidos',
												'alteracao' => 'orders-finish',
												'alteracao_txt' => 'Voucher <b>#'.$voucherCodigo.'</b> baixado com sucesso!',
											)
										),
									));
									
									// ===== Pegar dados do voucher.
								
									$hosts_vouchers = banco_select(Array(
										'unico' => true,
										'tabela' => 'hosts_vouchers',
										'campos' => Array(
											'id_hosts_servicos',
											'id_hosts_servicos_variacoes',
											'loteVariacao',
										),
										'extra' => 
											"WHERE codigo='".$voucherCodigo."'"
											." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
											." AND id_hosts='".$id_hosts."'"
									));
									
									// ===== Pegar os dados do lote e variação caso necessário.
									
									if($hosts_vouchers['loteVariacao']){
										// ===== Pegar os dados do pedido serviço variação.
										
										$hosts_pedidos_servico_variacoes = banco_select(Array(
											'unico' => true,
											'tabela' => 'hosts_pedidos_servico_variacoes',
											'campos' => Array(
												'nome_servico',
												'nome_lote',
												'nome_variacao',
											),
											'extra' => 
												"WHERE id_hosts_servicos='".$hosts_vouchers['id_hosts_servicos']."'"
												." AND id_hosts_servicos_variacoes='".$hosts_vouchers['id_hosts_servicos_variacoes']."'"
												." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
												." AND id_hosts='".$id_hosts."'"
										));
										
										$servicoNome = $hosts_pedidos_servico_variacoes['nome_servico'];
										$voucherSubtitulo = $hosts_pedidos_servico_variacoes['nome_lote'].' - '.$hosts_pedidos_servico_variacoes['nome_variacao'];
									} else {
										// ===== Pegar dados do pedido serviço.
									
										$hosts_pedidos_servicos = banco_select(Array(
											'unico' => true,
											'tabela' => 'hosts_pedidos_servicos',
											'campos' => Array(
												'nome',
											),
											'extra' => 
												"WHERE id_hosts_servicos='".$hosts_vouchers['id_hosts_servicos']."'"
												." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
												." AND id_hosts='".$id_hosts."'"
										));
										
										$servicoNome = $hosts_pedidos_servicos['nome'];
									}
									
									// ===== Buscar no banco de dados o email do usuário dono do pedido.
									
									$hosts_usuarios = banco_select(Array(
										'unico' => true,
										'tabela' => 'hosts_usuarios',
										'campos' => Array(
											'nome',
											'email',
										),
										'extra' => 
											"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
											." AND id_hosts='".$id_hosts."'"
									));
									
									$nome = $hosts_usuarios['nome'];
									$email = $hosts_usuarios['email'];
									
									// ===== Dados do email a ser enviado com a confirmação da baixa.
									
									$voucherTitulo = 'Voucher #'.$voucherCodigo.' utilizado no estabelecimento';
									
									// ===== Enviar email da baixa do voucher.
									
									gestor_incluir_biblioteca('host');
									gestor_incluir_biblioteca('comunicacao');
									
									if(comunicacao_email(Array(
										'destinatarios' => Array(
											Array(
												'email' => $email,
											),
										),
										'mensagem' => Array(
											'assunto' => $voucherTitulo,
											'htmlLayoutID' => 'layout-email-baixa-voucher',
											'htmlVariaveis' => Array(
												Array(
													'variavel' => '#voucherID#',
													'valor' => '#'.$voucherCodigo.': '.$servicoNome . (isset($voucherSubtitulo) ? ' - '.$voucherSubtitulo : ''),
												),
												Array(
													'variavel' => '#nome#',
													'valor' => $nome,
												),
												Array(
													'variavel' => '#assinatura#',
													'valor' => modelo_var_troca_tudo(gestor_componente(Array('id' => 'hosts-layout-emails-assinatura')),'@[[url]]@',host_url(Array('opcao'=>'full','id_hosts' => $id_hosts)).'identificacao/')
												),
											),
										),
									))){
										$emailSucesso = true;
									} else {
										$emailSucesso = false;
									}
									
									// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
		
									gestor_incluir_biblioteca('api-cliente');
									
									$retorno = api_cliente_app_vouchers(Array(
										'opcao' => 'atualizar-status',
										'id_hosts' => $id_hosts,
										'id_hosts_vouchers' => $id_hosts_vouchers,
									));
									
									if(!$retorno['completed']){
										$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-orders-finished-api-client-error'));
										
										$message = modelo_var_troca($message,"#error-msg#",$retorno['error-msg']);
										
									} else {
										$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-orders-finished'));
									}
									
									$message = modelo_var_troca($message,"#codigo#",$voucherCodigo);
									
									// ===== Retornar OK.
								
									plataforma_app_200(Array(
										'message' => $message,
									));
								break;
								default:
									$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-codigo-opcao-mandatory'));
							}
						} else {
							if($voucherStatus == 'usado'){
								$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-voucher-used'));
							} else {
								$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-voucher-status-error'));
								$message = modelo_var_troca_tudo($message,"#estado#",$voucherStatus);
							}
							
							$message = modelo_var_troca_tudo($message,"#pedido#",$pedido);
							$message = modelo_var_troca_tudo($message,"#voucher#",$voucherCodigo);
						}
					} else {
						$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-voucher-not-found'));
						$message = modelo_var_troca_tudo($message,"#pedido#",$pedido);
					}
				} else {
					$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-voucher-not-valid'));
					$message = modelo_var_troca_tudo($message,"#pedido#",$pedido);
				}
			} else {
				$variaveis = banco_select(Array(
					'unico' => true,
					'tabela' => 'variaveis',
					'campos' => Array(
						'valor',
					),
					'extra' => 
						"WHERE modulo='_sistema'"
						." AND grupo='pedidos-status'"
						." AND id='".$status."'"
				));
				
				$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-order-not-paied'));
				$message = modelo_var_troca_tudo($message,"#status#",strip_tags($variaveis['valor']));
			}
		} else {
			$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-order-not-found'));
			$message = modelo_var_troca_tudo($message,"#pedido#",$pedido);
		}
	} else {
		$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-codigo-opcao-mandatory'));
	}
	
	// ===== Retorno de mensagem de erro caso não consiga validar as opções.
	
	plataforma_app_200(Array(
		'message' => $message,
		'status' => 'NOT-OK',
	));
}

function plataforma_app_login(){
	global $_GESTOR;
	
	// ===== Validador provisório!!!
	
	
	if(sha1($_REQUEST['appID']) !== 'a45aa0844e67182bf608916891e6080a7d436dc8'){
		plataforma_app_401();
	}
	
	// ===== Google reCAPTCHA v3
	
	$recaptchaValido = false;
	
	if(isset($_GESTOR['usuario-recaptcha-active'])){
		if($_GESTOR['usuario-recaptcha-active'] && $_GESTOR['app-recaptcha-active']){
			// ===== Variáveis de comparação do reCAPTCHA
			
			$recaptchaSecretKey = $_GESTOR['usuario-recaptcha-server'];
			
			$token = $_POST['token'];
			$action = $_POST['action'];
			
			// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);
			$arrResponse = json_decode($response, true);
			
			// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
			
			if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
	} else {
		$recaptchaValido = true;
	}
	
	$user_invalid = true;
	
	if($recaptchaValido){
		// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
		
		$usuario = banco_escape_field($_REQUEST['user']);
		$senha = $_REQUEST['pass'];
		
		$usuarios = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios',
				'senha',
				'status',
			))
			,
			"usuarios",
			"WHERE usuario='".$usuario."'"
			." AND status!='D'"
		);
		
		// ===== Rotinas de validação de usuário
		
		if($usuarios){
			$senha_hash = $usuarios[0]['senha'];
			
			if(password_verify($senha, $senha_hash)){
				// ===== Pegar dados do usuário.
				
				$status = $usuarios[0]['status'];
				$id_usuarios = $usuarios[0]['id_usuarios'];
				
				if($status == 'A'){
					// ===== Verificar o host do usuário.
					
					$hosts_usuarios_admins = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios_admins',
						'campos' => Array(
							'id_hosts',
						),
						'extra' => 
							"WHERE id_usuarios='".$id_usuarios."'"
					));
					
					if($hosts_usuarios_admins){
						$user_invalid = false;
						
						// ===== Gerar token do usuário.
						
						gestor_incluir_biblioteca('usuario');
						
						$tokenObj = usuario_app_gerar_token_autorizacao(Array(
							'id_usuarios' => $id_usuarios,
						));
						
						// ===== Pegar demais dados do usuário do banco de dados.
						
						$usuarios = banco_select(Array(
							'unico' => true,
							'tabela' => 'usuarios',
							'campos' => Array(
								'nome',
							),
							'extra' => 
								"WHERE id_usuarios='".$id_usuarios."'"
						));
						
						$hosts = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts',
							'campos' => Array(
								'user_cpanel',
							),
							'extra' => 
								"WHERE id_usuarios='".$id_usuarios."'"
						));
						
						// ===== Retornar os dados do usuário e o token.
						
						plataforma_app_200(Array(
							'token' => $tokenObj['token'],
							'expiration' => $tokenObj['expiration'],
							'userData' => Array(
								'nome' => $usuarios['nome'],
								'codigo' => ($hosts['user_cpanel'] ? $hosts['user_cpanel'] : $id_usuarios),
								'avatar' => false,
							),
						));
					} else {
						$user_without_host = true;
					}
				} else {
					$user_inactive = true;
				}
			}
		}
	} else {
		// ===== Se o recaptcha for inválido, alertar o usuário.
		
		sleep(3);
		
		$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'recaptcha-invalid'));
		
		$message = modelo_var_troca_tudo($alerta,"#url#",$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url']);
		
		plataforma_app_200(Array(
			'message' => $message,
			'status' => 'recaptchaInvalid',
		));
	}

	// ===== Se o usuário for inválido, redirecionar signin.
	
	if($user_invalid){
		sleep(3);
		
		if(isset($user_without_host)){
			$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-without-host'));
		} else if(isset($user_inactive)){
			$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-inactive'));
		} else {
			$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-or-password-invalid'));
		}
		
		plataforma_app_200(Array(
			'message' => $message,
			'status' => 'userInvalid',
		));
	}
	
	return null;
}

// =========================== Funções de Acesso

function plataforma_app_401(){
	http_response_code(401);
	
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '401',
		'info' => 'JSON unauthorized',
	));
	exit;
}

function plataforma_app_404(){
	http_response_code(404);
	
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '404',
		'info' => 'JSON not found',
	));
	exit;
}

function plataforma_app_200($data = null){
	global $_GESTOR;
	
	// ===== Caso tenha sido renovado o token, devolver o token novo.
	
	if(isset($_GESTOR['usuario-token-renew'])){
		$data['tokenRenew'] = true;
		$data['newToken'] = $_GESTOR['usuario-token-new-token'];
		$data['newExpiration'] = $_GESTOR['usuario-token-new-expiration'];
	}
	
	// ===== Tratar o retorno.
	
	if(isset($data)){
		if(!isset($data['status'])){
			$data['status'] = 'OK';
		}
		
		$saida = $data;
	} else {
		$saida = Array(
			'status' => 'OK',
		);
	}
	
	// ===== Devolver o código de ok e os dados.
	
	http_response_code(200);
	
	echo json_encode($saida);
	exit;
}

function plataforma_app_permissao_token($token = ''){
	global $_GESTOR;
	
	// ===== Verifica se existe o cookie de autenticação enviado pelo app.
	
	$JWTToken = $token;
	
	if(!existe($JWTToken)){
		return false;
	}
	
	// ===== Abrir chave privada e a senha da chave
	
	$keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
	
	$fp = fopen($keyPrivatePath,"r");
	$keyPrivateString = fread($fp,8192);
	fclose($fp);
	
	$chavePrivadaSenha = $_GESTOR['openssl-password'];
	
	// ===== Verificar se o JWT é válido.
	
	$tokenPubId = gestor_permissao_validar_jwt(Array(
		'token' => $JWTToken,
		'chavePrivada' => $keyPrivateString,
		'chavePrivadaSenha' => $chavePrivadaSenha,
	));
	
	if($tokenPubId){
		// ===== Verifica se o token está ativo. Senão estiver invalidar o cookie.
		
		$usuarios_tokens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios_tokens',
				'id_usuarios',
				'pubIDValidation',
				'data_criacao',
				'expiration',
			))
			,
			"usuarios_tokens",
			"WHERE pubID='".$tokenPubId."'"
			." AND origem='".$_GESTOR['app-origem']."'"
		);
		
		if($usuarios_tokens){
			// ===== Verificar se o token não expirou.
			
			$expiration = $usuarios_tokens[0]['expiration'];
			$expiracao_ok = false;
			
			// ===== Se o tempo de expiração do token for maior que o tempo agora, é porque este token está ativo. Senão está vencido e deve ser deletado.
			
			if((int)$expiration > time()){
				$expiracao_ok = true;
			} else {
				$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
				
				banco_delete
				(
					"usuarios_tokens",
					"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
				);
			}
			
			if($expiracao_ok){
				// ===== Validar o token com o hash de validação para evitar geração de token por hacker caso ocorra roubo da tabela 'usuarios_tokens'.
				
				$bd_hash = $usuarios_tokens[0]['pubIDValidation'];
				$token_hash = hash_hmac($_GESTOR['usuario-hash-algo'], $tokenPubId, $_GESTOR['usuario-hash-password']);
				
				if($bd_hash === $token_hash){
					$data_criacao = $usuarios_tokens[0]['data_criacao'];
					$id_usuarios = $usuarios_tokens[0]['id_usuarios'];
					
					// ===== Verificar o host do usuário.
					
					$hosts_usuarios_admins = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios_admins',
						'campos' => Array(
							'id_hosts',
						),
						'extra' => 
							"WHERE id_usuarios='".$id_usuarios."'"
					));
					
					if($hosts_usuarios_admins){
						// ===== Verificar se precisa renovar JWTToken, se sim, apagar token anterior e criar um novo no lugar.
						
						$time_criacao = strtotime($data_criacao);
						
						if($time_criacao + $_GESTOR['app-token-renewtime'] < time()){
							gestor_incluir_biblioteca('usuario');
							
							$tokenObj = usuario_app_gerar_token_autorizacao(Array(
								'id_usuarios' => $id_usuarios,
							));
							
							$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
							
							banco_delete
							(
								"usuarios_tokens",
								"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
							);
							
							// ===== Renovar o token no cliente.
							
							$_GESTOR['usuario-token-renew'] = true;
							$_GESTOR['usuario-token-new-token'] = $tokenObj['token'];
							$_GESTOR['usuario-token-new-expiration'] = $tokenObj['expiration'];
						}
						
						$_GESTOR['usuario-id'] = $id_usuarios;
						$_GESTOR['usuario-token-id'] = $tokenPubId;
						$_GESTOR['usuario-host-id'] = $hosts_usuarios_admins['id_hosts'];
						
						return true;
					}
				}
			} else {
				plataforma_app_200(Array(
					'status' => 'tokenExpired',
				));
			}
		} else {
			plataforma_app_200(Array(
				'status' => 'tokenExpired',
			));
		}
	}
	
	return false;
}

function plataforma_app_start(){
	global $_GESTOR;
	global $_INDEX;
	
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset: UTF-8");
	
	stream_php();
	
	// ===== Acessos públicos a plataforma.
	
	switch($_GESTOR['caminho'][1]){
		case 'login': $dados = plataforma_app_login(); $acessoPublico = true; break;
	}
	
	// ===== Acessos privados a plataforma.
	
	if(!isset($acessoPublico)){
		$token = (isset($_REQUEST['token']) ? $_REQUEST['token'] : '');

		if(plataforma_app_permissao_token($token)){

			plataforma_app_200(Array(
				'local' => 'plataforma_app_permissao_token'
			));

			switch($_GESTOR['caminho'][1]){
				case 'baixar-voucher': $dados = plataforma_app_baixar_voucher(); break;
			}
		} else {
			plataforma_app_401();
		}
	}
	
	// ===== Caso haja dados criados por alguma opção, retornar JSON e finalizar. Senão retornar JSON 404.
	
	if(isset($dados)){
		echo json_encode($dados);
		exit;
	}
	
	plataforma_app_404();
}

// =========================== Inciar Plataforma

plataforma_app_start();

?>