<?php

// ===== Plataforma responsável por receber solicitações do 'cliente'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-cliente';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.3',
);

// =========================== Funções Extras

function plataforma_cliente_recaptcha($token,$action){
	global $_GESTOR;
	
	// ===== Google reCAPTCHA v3
	
	$recaptchaValido = false;
	
	if(isset($_GESTOR['platform-recaptcha-active'])){
		if($_GESTOR['platform-recaptcha-active']){
			// ===== Variáveis de comparação do reCAPTCHA
			
			$recaptchaSecretKey = $_GESTOR['platform-recaptcha-server'];
			
			// ===== Identificador do Host.
			
			$id_hosts = $_GESTOR['host-id'];
			
			// ===== Verificar se o host tem reCAPTCHA está ativado.
			
			$hosts = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts',
				'campos' => Array(
					'google_recaptcha_ativo',
					'google_recaptcha_secret',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
			));
			
			if($hosts['google_recaptcha_ativo']){
				if($hosts['google_recaptcha_secret']){
					$recaptchaSecretKey = $hosts['google_recaptcha_secret'];
				}
			}
			
			
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
	
	return $recaptchaValido;
}

// =========================== Funções Auxiliares

function plataforma_cliente_gerar_jwt($params = false){
	$cryptMaxCharsValue = 245; // There are char limitations on openssl_private_encrypt() and in the url below are explained how define this value based on openssl key format: https://www.php.net/manual/en/function.openssl-private-encrypt.php#119810
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// host - String - Obrigatório - Host de acesso do JWT.
	// expiration - Int - Obrigatório - Expiração do JWT.
	// pubID - String - Obrigatório - ID público do token para referência.
	// chavePublica - String - Obrigatório - Chave pública para assinar o JWT.
	
	// ===== 
	
	if(isset($host) && isset($expiration) && isset($pubID) && isset($chavePublica)){
		// ===== Header

		$header = [
		   'alg' => 'RSA',
		   'typ' => 'JWT'
		];

		$header = json_encode($header);
		$header = base64_encode($header);

		// ===== Payload

		$payload = [
			'iss' => $host, // The issuer of the token
			'exp' => $expiration, // This will define the expiration in NumericDate value. The expiration MUST be after the current date/time.
			'sub' => $pubID, // ID público do totken
		];

		$payload = json_encode($payload);
		$payload = base64_encode($payload);

		// ===== Unir header com payload para gerar assinatura

		$rawDataSource = $header.".".$payload;
		
		// ===== Assinar usando RSA SSL
		
		$resPublicKey = openssl_get_publickey($chavePublica);

		$partialData = '';
		$encodedData = '';
		$split = str_split($rawDataSource , $cryptMaxCharsValue);
		foreach($split as $part){
			openssl_public_encrypt($part, $partialData, $resPublicKey);
			$encodedData .= (strlen($encodedData) > 0 ? '.':'') . base64_encode($partialData);
		}
		
		$encodedData = base64_encode($encodedData);
		
		$signature = $encodedData;
		
		// ===== Finalizar e devolver o JWT token

		$JWTToken = $header.".".$payload.".".$signature;
		
		return $JWTToken;
	} else {
		return false;
	}
}

function plataforma_cliente_validar_jwt($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - Token JWT de verificação.
	// chavePublica - String - Obrigatório - Chave pública para conferir a assinatura do token.
	
	// ===== 
	
	if(isset($token) && isset($chavePublica)){
		// ===== Quebra o token em header, payload e signature
		
		$part = explode(".",$token);
		
		if(gettype($part) != 'array'){
			return false;
		}
		
		$header = $part[0];
		$payload = $part[1];
		$signature = $part[2];

		$encodedData = $signature;
		
		// ===== Abrir chave privada com a senha
		
		$resPublicKey = openssl_get_publickey($chavePublica);
		
		// ===== Decode base64 to reaveal dots (Dots are used in JWT syntaxe)

		$encodedData = base64_decode($encodedData);

		// ===== Decrypt data in parts if necessary. Using dots as split separator.

		$rawEncodedData = $encodedData;

		$countCrypt = 0;
		$partialDecodedData = '';
		$decodedData = '';
		$split2 = explode('.',$rawEncodedData);
		foreach($split2 as $part2){
			$part2 = base64_decode($part2);
			
			openssl_public_decrypt($part2, $partialDecodedData, $resPublicKey);
			$decodedData .= $partialDecodedData;
		}

		// ===== Validate JWT
		
		if($header.".".$payload === $decodedData){
			$payload = base64_decode($payload);
			$payload = json_decode($payload,true);
			
			// ===== Verifica se as variáveis existem, senão foi formatado errado e não deve aceitar.
			
			if(!isset($payload['exp']) || !isset($payload['sub'])){
				return false;
			}
			
			$expiracao_ok = false;
			
			// ===== Se o tempo de expiração do token for menor que o tempo agora, é porque este token está vencido.
			
			if((int)$payload['exp'] > time()){
				$expiracao_ok = true;
			}
			
			if($expiracao_ok){
				// Se tudo estiver válido, retorna o pubID do token.
				
				return $payload['sub'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function plataforma_cliente_validar_token_autorizacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - JWT gerado pelo cliente.
	// hostId - String - Obrigatório - ID público do host do cliente.
	
	// ===== 
	
	if(isset($token) && isset($hostId)){
		// ===== Verifica se existe o token.
		
		$JWTToken = $token;
		
		if(!existe($JWTToken)){
			return false;
		}
		
		// ===== Pegar a chave pública do host
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'chave_publica',
			))
			,
			"hosts",
			"WHERE pub_id='".$hostId."'"
		);
		
		if($hosts){
			$chavePublica = $hosts[0]['chave_publica'];
			
			// ===== Verificar se o JWT é válido.
			
			$tokenPubId = plataforma_cliente_validar_jwt(Array(
				'token' => $JWTToken,
				'chavePublica' => $chavePublica,
			));
			
			return $tokenPubId;
		}
	}
	
	return false;
}

function plataforma_cliente_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// hostId - String - Obrigatório - ID público do host do cliente.
	
	// ===== 
	
	if(isset($hostId)){
	
		// ===== Definir variáveis para gerar o JWT
		
		$expiration = time() + $_GESTOR['platform-lifetime'];
		
		// ===== Pegar a chave pública do host
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'chave_publica',
			))
			,
			"hosts",
			"WHERE pub_id='".$hostId."'"
		);
		
		if($hosts){
			$chavePublica = $hosts[0]['chave_publica'];
			
			// ===== Gerar ID do Token
			
			$tokenPubId = md5(uniqid(rand(), true));
			
			// ===== Gerar o token JWT
			
			$token = plataforma_cliente_gerar_jwt(Array(
				'host' => $_SERVER['SERVER_NAME'],
				'expiration' => $expiration,
				'pubID' => $tokenPubId,
				'chavePublica' => $chavePublica,
			));
			
			return $token;
		}
	}
	
	return false;
}

// =========================== Funções da Plataforma

function plataforma_cliente_carrinho(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'adicionar':
		case 'diminuir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: sessao_id, id_hosts_servicos e quantidade.
			
			if(isset($dados['sessao_id']) && isset($dados['id_hosts_servicos']) && isset($dados['quantidade'])){
				$quantidade = (int)$dados['quantidade'];
				$id_hosts_servicos = banco_escape_field($dados['id_hosts_servicos']);
				$sessao_id = banco_escape_field($dados['sessao_id']);
				
				// ===== Verificar se é variação.
				
				if(isset($dados['variacao_id'])){
					$variacao_id = $dados['variacao_id'];
				}
				
				// ===== Pegar dados do serviço.
				
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'quantidade',
						'quantidade_carrinhos',
						'status',
						'id_hosts_arquivos_Imagem',
						'nome',
						'preco',
						'gratuito',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_servicos){
					// ===== Caso o serviço estiver ativo 'A' continua.
				
					if($hosts_servicos['status'] == 'A'){
						// ===== Verificar se é uma variação de um lote.
						
						$variacao = false;
						if(isset($variacao_id)){
							$variacao = true;
							
							// ===== Baixar do banco de dados os dados da variação.
							
							$hosts_servicos_variacoes = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_servicos_variacoes',
								'campos' => Array(
									'id_hosts_servicos_lotes',
									'id_hosts_servicos_variacoes',
									'quantidade',
									'quantidade_carrinhos',
									'nome',
									'preco',
									'gratuito',
								),
								'extra' => 
									"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									." AND id_hosts_servicos_variacoes='".$variacao_id."'"
							));
						}
						
						// ===== Verificar quantidade disponível em estoque.
						
						switch($opcao){
							case 'adicionar':
								// ===== Tratar a quantidade baseado na variação.
								
								if($variacao){
									$quantidadeEstoque = (int)$hosts_servicos_variacoes['quantidade'];
								} else {
									$quantidadeEstoque = (int)$hosts_servicos['quantidade'];
								}
								
								if($quantidadeEstoque < $quantidade){
									return Array(
										'status' => 'UNAVAILABLE_AMOUNT',
									);
								}
							break;
						}
						
						// ===== Criar carrinho caso não exista.
						
						$hosts_carrinho = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_carrinho',
							'campos' => Array(
								'id_hosts_carrinho',
							),
							'extra' => 
								"WHERE sessao_id='".$sessao_id."'"
								." AND id_hosts='".$id_hosts."'"
						));
						
						if(!$hosts_carrinho){
							$campos = null; $campo_sem_aspas_simples = null;
							
							$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "sessao_id"; $campo_valor = $sessao_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
							
							banco_insert_name
							(
								$campos,
								"hosts_carrinho"
							);
							
							$id_hosts_carrinho = banco_last_id();
						} else {
							$id_hosts_carrinho = $hosts_carrinho['id_hosts_carrinho'];
						}
						
						if($variacao){
							// ===== Quantidade para alterar no estoque início.
							
							$estoqueQuantidade = (int)$hosts_servicos_variacoes['quantidade'];
							$carrinhosQuantidade = (int)$hosts_servicos_variacoes['quantidade_carrinhos'];
							$quantidadeEstoqueAlterar = 0;
							$estoqueDiminuir = true;
							
							// ===== Verificar se já existe a variação do serviço no carrinho. Se sim, atualizar a quantidade, senão criar um novo.
							
							$hosts_carrinho_servico_variacoes = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_carrinho_servico_variacoes',
								'campos' => Array(
									'id_hosts_carrinho_servico_variacoes',
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
									." AND id_hosts_servicos_variacoes='".$variacao_id."'"
							));
							
							if($hosts_carrinho_servico_variacoes){
								// ===== Atualizar a quantidade atual.
								
								switch($opcao){
									case 'adicionar':
										$quantidadeNova = (int)$hosts_carrinho_servico_variacoes['quantidade'] + $quantidade;
										$quantidadeEstoqueAlterar = $quantidade;
									break;
									case 'diminuir':
										$quantidadeNova = (int)$hosts_carrinho_servico_variacoes['quantidade'] - $quantidade;
										
										if($quantidadeNova < 0){
											$quantidadeNova = 0;
										}
										
										if($estoqueQuantidade >= $quantidade){
											$quantidadeEstoqueAlterar = $quantidade;
										} else {
											$quantidadeEstoqueAlterar = $estoqueQuantidade;
										}
										
										$estoqueDiminuir = false;
									break;
								}
								
								// ===== Atualizar o banco de dados a nova quantidade.
								
								$campo_tabela = "hosts_carrinho_servico_variacoes";
								$campo_tabela_extra = "WHERE id_hosts_carrinho_servico_variacoes='".$hosts_carrinho_servico_variacoes['id_hosts_carrinho_servico_variacoes']."'";
								
								$campo_nome = "quantidade"; $campo_valor = $quantidadeNova; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
								
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
								
								$id_hosts_carrinho_servico_variacoes = $hosts_carrinho_servico_variacoes['id_hosts_carrinho_servico_variacoes'];
							} else {
								// ===== Pegar o nome do lote.
								
								$hosts_servicos_lotes = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos_lotes',
									'campos' => Array(
										'nome',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts_servicos_lotes='".$hosts_servicos_variacoes['id_hosts_servicos_lotes']."'"
								));
								
								// ===== Caso seja gratuito, zerar o preço mesmo que positivo.
								
								if($hosts_servicos_variacoes['gratuito']){
									$hosts_servicos_variacoes['preco'] = '0';
								}
								
								// ===== Adicionar ao banco.
								
								$campos = null; $campo_sem_aspas_simples = null;
								
								$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_carrinho"; $campo_valor = $id_hosts_carrinho; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos"; $campo_valor = $id_hosts_servicos; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos_lotes"; $campo_valor = $hosts_servicos_variacoes['id_hosts_servicos_lotes']; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos_variacoes"; $campo_valor = $variacao_id; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome_servico"; $campo_valor = $hosts_servicos['nome'];														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome_lote"; $campo_valor = $hosts_servicos_lotes['nome'];													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome_variacao"; $campo_valor = $hosts_servicos_variacoes['nome'];											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "preco"; $campo_valor = $hosts_servicos_variacoes['preco'];													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "quantidade"; $campo_valor = $quantidade;																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_arquivos_Imagem"; $campo_valor = $hosts_servicos['id_hosts_arquivos_Imagem'];						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "gratuito"; $campo_valor = ($hosts_servicos_variacoes['gratuito'] ? '1' : 'NULL');							$campos[] = Array($campo_nome,$campo_valor,true);
								
								banco_insert_name
								(
									$campos,
									"hosts_carrinho_servico_variacoes"
								);
								
								$id_hosts_carrinho_servico_variacoes = banco_last_id();
								
								// ===== Quantidade para alterar no estoque.
								
								$quantidadeEstoqueAlterar = $quantidade;
							}
							
							// ===== Alterar o estoque do serviço variação.
							
							if($quantidadeEstoqueAlterar > 0){
								if($estoqueDiminuir){
									banco_update
									(
										"quantidade=".$estoqueQuantidade." - ".$quantidadeEstoqueAlterar.","
										."quantidade_carrinhos=".$carrinhosQuantidade." + ".$quantidadeEstoqueAlterar,
										"hosts_servicos_variacoes",
										"WHERE id_hosts_servicos_variacoes='".$variacao_id."'"
									);
								} else {
									banco_update
									(
										"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
										."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
										"hosts_servicos_variacoes",
										"WHERE id_hosts_servicos_variacoes='".$variacao_id."'"
									);
								}
							}
							
							// ===== Alterar a quantidade_carrinhos do serviço.
							
							$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
							
							if($quantidadeEstoqueAlterar > 0){
								if($estoqueDiminuir){
									banco_update
									(
										"quantidade_carrinhos=".$carrinhosQuantidade." + ".$quantidadeEstoqueAlterar,
										"hosts_servicos",
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									);
								} else {
									banco_update
									(
										"quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
										"hosts_servicos",
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									);
								}
							}
						} else {
							// ===== Quantidade para alterar no estoque início.
							
							$estoqueQuantidade = (int)$hosts_servicos['quantidade'];
							$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
							$quantidadeEstoqueAlterar = 0;
							$estoqueDiminuir = true;
							
							// ===== Verificar se já existe serviço no carrinho. Se sim, atualizar a quantidade, senão criar um novo.
							
							$hosts_carrinho_servicos = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_carrinho_servicos',
								'campos' => Array(
									'id_hosts_carrinho_servicos',
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
									." AND id_hosts_servicos='".$id_hosts_servicos."'"
							));
							
							if($hosts_carrinho_servicos){
								// ===== Atualizar a quantidade atual.
								
								switch($opcao){
									case 'adicionar':
										$quantidadeNova = (int)$hosts_carrinho_servicos['quantidade'] + $quantidade;
										$quantidadeEstoqueAlterar = $quantidade;
									break;
									case 'diminuir':
										$quantidadeNova = (int)$hosts_carrinho_servicos['quantidade'] - $quantidade;
										
										if($quantidadeNova < 0){
											$quantidadeNova = 0;
										}
										
										if($estoqueQuantidade >= $quantidade){
											$quantidadeEstoqueAlterar = $quantidade;
										} else {
											$quantidadeEstoqueAlterar = $estoqueQuantidade;
										}
										
										$estoqueDiminuir = false;
									break;
								}
								
								// ===== Atualizar o banco de dados a nova quantidade.
								
								$campo_tabela = "hosts_carrinho_servicos";
								$campo_tabela_extra = "WHERE id_hosts_carrinho_servicos='".$hosts_carrinho_servicos['id_hosts_carrinho_servicos']."'";
								
								$campo_nome = "quantidade"; $campo_valor = $quantidadeNova; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
								
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
								
								$id_hosts_carrinho_servicos = $hosts_carrinho_servicos['id_hosts_carrinho_servicos'];
							} else {
								// ===== Caso seja gratuito, zerar o preço mesmo que positivo.
								
								if($hosts_servicos['gratuito']){
									$hosts_servicos['preco'] = '0';
								}
								
								// ===== Adicionar ao banco.
								
								$campos = null; $campo_sem_aspas_simples = null;
								
								$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_carrinho"; $campo_valor = $id_hosts_carrinho; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos"; $campo_valor = $id_hosts_servicos; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome"; $campo_valor = $hosts_servicos['nome'];																$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "preco"; $campo_valor = $hosts_servicos['preco'];																$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "quantidade"; $campo_valor = $quantidade;																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_arquivos_Imagem"; $campo_valor = $hosts_servicos['id_hosts_arquivos_Imagem'];						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "gratuito"; $campo_valor = ($hosts_servicos['gratuito'] ? '1' : 'NULL');										$campos[] = Array($campo_nome,$campo_valor,true);
								
								banco_insert_name
								(
									$campos,
									"hosts_carrinho_servicos"
								);
								
								$id_hosts_carrinho_servicos = banco_last_id();
								
								// ===== Quantidade para alterar no estoque.
								
								$quantidadeEstoqueAlterar = $quantidade;
							}
							
							// ===== Alterar o estoque do serviço.
							
							if($quantidadeEstoqueAlterar > 0){
								if($estoqueDiminuir){
									banco_update
									(
										"quantidade=".$estoqueQuantidade." - ".$quantidadeEstoqueAlterar.","
										."quantidade_carrinhos=".$carrinhosQuantidade." + ".$quantidadeEstoqueAlterar,
										"hosts_servicos",
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									);
								} else {
									banco_update
									(
										"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
										."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
										"hosts_servicos",
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									);
								}
							}
						}
						
						// ===== Atualizar data modificação do carrinho.
						
						banco_update
						(
							"data_modificacao=NOW()",
							"hosts_carrinho",
							"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
						);
						
						// ===== Retornar carrinho atualizado.
						
						$hosts_carrinho = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_carrinho',
							'campos' => '*',
							'extra' => 
								"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
						));
						
						unset($hosts_carrinho['id_hosts']);
						
						if($variacao){
							$hosts_carrinho_servico_variacoes = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_carrinho_servico_variacoes',
								'campos' => '*',
								'extra' => 
									"WHERE id_hosts_carrinho_servico_variacoes='".$id_hosts_carrinho_servico_variacoes."'"
							));
							
							unset($hosts_carrinho_servico_variacoes['id_hosts']);
							
							// ===== Quantidade atual do estoque.
							
							$hosts_servicos_variacoes = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_servicos_variacoes',
								'campos' => Array(
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_servicos_variacoes='".$variacao_id."'"
							));
							
							// ===== Retornar o carrinho e o serviço.
							
							$retorno = Array(
								'status' => 'OK',
								'data' => Array(
									'variacao' => true,
									'carrinho' => $hosts_carrinho,
									'carrinho_servico_variacoes' => $hosts_carrinho_servico_variacoes,
									'servico-variacao-quantidade' => (int)$hosts_servicos_variacoes['quantidade'],
								)
							);
						} else {
							$hosts_carrinho_servicos = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_carrinho_servicos',
								'campos' => '*',
								'extra' => 
									"WHERE id_hosts_carrinho_servicos='".$id_hosts_carrinho_servicos."'"
							));
							
							unset($hosts_carrinho_servicos['id_hosts']);
							
							// ===== Quantidade atual do estoque.
							
							$hosts_servicos = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_servicos',
								'campos' => Array(
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
							));
							
							// ===== Retornar o carrinho e o serviço.
							
							$retorno = Array(
								'status' => 'OK',
								'data' => Array(
									'carrinho' => $hosts_carrinho,
									'carrinho_servicos' => $hosts_carrinho_servicos,
									'servico-quantidade' => (int)$hosts_servicos['quantidade'],
								)
							);
						}
					} else {
						$retorno = Array(
							'status' => 'SERVICE_NOT_AVAILABLE',
						);
					}
				} else {
					$retorno = Array(
						'status' => 'SERVICE_NOT_FOUND:'.$id_hosts_servicos,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'excluir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: sessao_id, id_hosts_servicos e quantidade.
			
			if(isset($dados['sessao_id']) && isset($dados['id_hosts_servicos'])){
				$id_hosts_servicos = banco_escape_field($dados['id_hosts_servicos']);
				$sessao_id = banco_escape_field($dados['sessao_id']);
				
				// ===== Verificar se é variação.
				
				if(isset($dados['variacao_id'])){
					$variacao_id = $dados['variacao_id'];
				}
				
				// ===== Pegar dados do serviço.
				
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'status',
						'quantidade',
						'quantidade_carrinhos',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_servicos){
					// ===== Caso o serviço estiver ativo 'A' continua.
				
					if($hosts_servicos['status'] == 'A'){
						// ===== Criar carrinho caso não exista.
						
						$hosts_carrinho = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_carrinho',
							'campos' => Array(
								'id_hosts_carrinho',
							),
							'extra' => 
								"WHERE sessao_id='".$sessao_id."'"
								." AND id_hosts='".$id_hosts."'"
						));
						
						if($hosts_carrinho){
							$id_hosts_carrinho = $hosts_carrinho['id_hosts_carrinho'];
							
							if(isset($variacao_id)){
								// ===== Pegar dados atualizados da variação.
								
								$hosts_servicos_variacoes = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos_variacoes',
									'campos' => Array(
										'quantidade',
										'quantidade_carrinhos',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts_servicos_variacoes='".$variacao_id."'"
								));
								
								// ===== Pegar quantidade de serviços do carrinho.
								
								$hosts_carrinho_servico_variacoes = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_carrinho_servico_variacoes',
									'campos' => Array(
										'quantidade',
									),
									'extra' => 
										"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
										." AND id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts_servicos_variacoes='".$variacao_id."'"
								));
								
								$quantidadeEstoqueAlterar = (int)$hosts_carrinho_servico_variacoes['quantidade'];
							
								// ===== Excluir o serviço do carrinho.
								
								banco_delete
								(
									"hosts_carrinho_servico_variacoes",
									"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
									." AND id_hosts_servicos='".$id_hosts_servicos."'"
									." AND id_hosts_servicos_variacoes='".$variacao_id."'"
								);
								
								// ===== Alterar o estoque.
								
								if($quantidadeEstoqueAlterar > 0){
									// ===== Quantidade para alterar no estoque início.
									
									$estoqueQuantidade = (int)$hosts_servicos_variacoes['quantidade'];
									$carrinhosQuantidade = (int)$hosts_servicos_variacoes['quantidade_carrinhos'];
									
									banco_update
									(
										"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
										."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
										"hosts_servicos_variacoes",
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts_servicos_variacoes='".$variacao_id."'"
									);
									
									// ===== Alterar a quantidade_carrinhos do serviço.
								
									$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
									
									banco_update
									(
										"quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
										"hosts_servicos",
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									);
								}
								
								// ===== Quantidade atual do estoque.
								
								$hosts_servicos_variacoes = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos_variacoes',
									'campos' => Array(
										'quantidade',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts_servicos_variacoes='".$variacao_id."'"
								));
								
								// ===== Retornar ok.
								
								$retorno = Array(
									'status' => 'OK',
									'data' => Array(
										'servico-variacao-quantidade' => (int)$hosts_servicos_variacoes['quantidade'],
									)
								);
							} else {
								// ===== Pegar quantidade de serviços do carrinho.
								
								$hosts_carrinho_servicos = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_carrinho_servicos',
									'campos' => Array(
										'quantidade',
									),
									'extra' => 
										"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
										." AND id_hosts_servicos='".$id_hosts_servicos."'"
								));
								
								$quantidadeEstoqueAlterar = (int)$hosts_carrinho_servicos['quantidade'];
							
								// ===== Excluir o serviço do carrinho.
								
								banco_delete
								(
									"hosts_carrinho_servicos",
									"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
									." AND id_hosts_servicos='".$id_hosts_servicos."'"
								);
								
								// ===== Alterar o estoque.
								
								if($quantidadeEstoqueAlterar > 0){
									// ===== Quantidade para alterar no estoque início.
									
									$estoqueQuantidade = (int)$hosts_servicos['quantidade'];
									$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
									
									banco_update
									(
										"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
										."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
										"hosts_servicos",
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									);
								}
								
								// ===== Quantidade atual do estoque.
								
								$hosts_servicos = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos',
									'campos' => Array(
										'quantidade',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
								));
								
								// ===== Retornar ok.
								
								$retorno = Array(
									'status' => 'OK',
									'data' => Array(
										'servico-quantidade' => (int)$hosts_servicos['quantidade'],
									)
								);
							}
							
							// ===== Atualizar data modificação do carrinho.
							
							banco_update
							(
								"data_modificacao=NOW()",
								"hosts_carrinho",
								"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
							);
						} else {
							$retorno = Array(
								'status' => 'CART_NOT_FOUND',
							);
						}
					} else {
						$retorno = Array(
							'status' => 'SERVICE_NOT_AVAILABLE',
						);
					}
				} else {
					$retorno = Array(
						'status' => 'SERVICE_NOT_FOUND:'.$id_hosts_servicos,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
	
	return $retorno;
}

function plataforma_cliente_identificacao(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'logar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: email e senha.
			
			if(isset($dados['email']) && isset($dados['senha']) && isset($dados['usuario_token'])){
				if(plataforma_cliente_recaptcha($dados['token'],$opcao)){
					$user_invalid = true;
					
					// ===== Verificar se o usuário já está logado, caso esteja, deletar token anterior no banco.
					
					if(isset($dados['usuarioTokenID'])){
						banco_delete
						(
							"hosts_usuarios_tokens",
							"WHERE pubID='".$dados['usuarioTokenID']."'"
							." AND id_hosts='".$id_hosts."'"
						);
					}
					
					// ===== Passar o email para letras minúsculas.
					
					$dados['email'] = strtolower($dados['email']);
					
					// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
					
					$usuario = banco_escape_field($dados['email']);
					$senha = banco_escape_field($dados['senha']);
					$user_inactive = false;
					
					$hosts_usuarios = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_hosts_usuarios',
							'senha',
							'status',
						))
						,
						"hosts_usuarios",
						"WHERE usuario='".$usuario."'"
						." AND status!='D'"
						." AND id_hosts='".$id_hosts."'"
					);
					
					// ===== Rotinas de validação de usuário
					
					if($hosts_usuarios){
						$senha_hash = $hosts_usuarios[0]['senha'];
						
						if(password_verify($senha, $senha_hash)){
							$status = $hosts_usuarios[0]['status'];
							$id_hosts_usuarios = $hosts_usuarios[0]['id_hosts_usuarios'];
							
							if($status == 'A'){
								$user_invalid = false;
							} else {
								$user_inactive = true;
							}
						}
					}
					
					// ===== Se o usuário for inválido, redirecionar signin.
					
					if($user_invalid){
						sleep(3);
						
						if($user_inactive){
							$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-inactive'));
						} else {
							$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-or-password-invalid'));
						}
						
						$retorno = Array(
							'status' => 'USER_INVALID',
							'error-msg' => $alerta,
						);
					} else {
						// ====== Salvar token no banco
						
						$campos = null; $campo_sem_aspas_simples = null;
						
						$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_hosts_usuarios"; $campo_valor = $id_hosts_usuarios; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "pubID"; $campo_valor = $dados['usuario_token']['pubID']; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "expiration"; $campo_valor = $dados['usuario_token']['expiration']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "ip"; $campo_valor = $dados['usuario_token']['ip']; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "user_agent"; $campo_valor = $dados['usuario_token']['user_agent']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 										$campos[] = Array($campo_nome,$campo_valor,true);
						
						banco_insert_name
						(
							$campos,
							"hosts_usuarios_tokens"
						);
						
						$id_hosts_usuarios_tokens = banco_last_id();
						
						// ===== Retornar os dados.
						
						$retorno = Array(
							'status' => 'OK',
							'data' => Array(
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'id_hosts_usuarios_tokens' => $id_hosts_usuarios_tokens,
							),
						);
					}
				} else {
					// ===== Se o recaptcha for inválido, alertar o usuário.
					
					sleep(3);
					
					$botaoTxt = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid-btn'));
					
					$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid'));
					
					$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
					
					$retorno = Array(
						'status' => 'RECAPTCHA_INVALID',
						'error-msg' => $alerta,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'sair':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: usuarioID e usuarioTokenID.
			
			if(isset($dados['usuarioID']) && isset($dados['usuarioTokenID'])){
				// ===== Deletar o usuário token.
				
				banco_delete
				(
					"hosts_usuarios_tokens",
					"WHERE id_hosts_usuarios_tokens='".$dados['usuarioTokenID']."'"
					." AND id_hosts_usuarios='".$dados['usuarioID']."'"
					." AND id_hosts='".$id_hosts."'"
				);
				
				// ===== Retornar ok.
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'criarConta':
			
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: email.
			
			if(isset($dados['email'])){
				if(plataforma_cliente_recaptcha($dados['token'],$opcao)){
					// ===== Passar o email para letras minúsculas.
					
					$dados['email'] = strtolower($dados['email']);
					
					// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
					
					$usuario = banco_escape_field($dados['email']);
					
					$hosts_usuarios = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_hosts_usuarios',
							'senha',
							'status',
						))
						,
						"hosts_usuarios",
						"WHERE usuario='".$usuario."'"
						." AND status!='D'"
						." AND id_hosts='".$id_hosts."'"
					);
					
					// ===== Verifica se já existe o usuário. Se sim, retornar mensagem de erro. Senão o OK.
					
					if($hosts_usuarios){
						sleep(3);
						
						$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-already-exists-email'));
						
						$retorno = Array(
							'status' => 'THERE_IS_USER',
							'error-msg' => $alerta,
						);
					} else {
						$retorno = Array(
							'status' => 'OK',
							'data' => Array(
								'email' => $dados['email'],
							),
						);
					}
				} else {
					// ===== Se o recaptcha for inválido, alertar o usuário.
					
					sleep(3);
					
					$botaoTxt = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid-btn'));
					
					$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid'));
					
					$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
					
					$retorno = Array(
						'status' => 'RECAPTCHA_INVALID',
						'error-msg' => $alerta,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'cadastrar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: email.
			
			if(isset($dados['email'])){
				if(plataforma_cliente_recaptcha($dados['token'],$opcao)){
					// ===== Passar o email para letras minúsculas.
					
					$dados['email'] = strtolower($dados['email']);
					
					// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
					
					$usuario = banco_escape_field($dados['email']);
					
					$hosts_usuarios = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_hosts_usuarios',
							'senha',
							'status',
						))
						,
						"hosts_usuarios",
						"WHERE usuario='".$usuario."'"
						." AND status!='D'"
						." AND id_hosts='".$id_hosts."'"
					);
					
					// ===== Verifica se já existe o usuário. Se sim, retornar mensagem de erro. Senão o OK.
					
					if($hosts_usuarios){
						sleep(3);
						
						$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-inactive'));
						
						$retorno = Array(
							'status' => 'THERE_IS_USER',
							'error-msg' => $alerta,
						);
					} else {
						// ===== Identificador único do usuário.
						
						$id = banco_identificador(Array(
							'id' => $dados['nome'],
							'tabela' => Array(
								'nome' => 'hosts_usuarios',
								'campo' => 'id',
								'id_nome' => 'id_hosts_usuarios',
								'where' => 'id_hosts="'.$id_hosts.'"',
							),
						));
						
						// ===== Gerar hash da senha
						
						$senha = $dados['senha'];
						
						$senhaHash = password_hash($senha, PASSWORD_ARGON2I, ["cost" => 9]);
						
						// ===== Separar os nomes (primeiro, do meio e último)
						
						$nome = $dados['nome'];
						$nome = ucwords(strtolower(trim($nome)));
						
						$nomes = explode(' ',$nome);
						
						if(count($nomes) > 2){
							for($i=0;$i<count($nomes);$i++){
								if($i==0){
									$primeiro_nome = $nomes[$i];
								} else if($i==count($nomes) - 1){
									$ultimo_nome = $nomes[$i];
								} else {
									$nome_do_meio .= (isset($nome_do_meio) ? ' ':'') . $nomes[$i];
								}
							}
						} else if(count($nomes) > 1){
							$primeiro_nome = $nomes[0];
							$ultimo_nome = $nomes[1];
						} else {
							$primeiro_nome = $nomes[0];
						}
						
						// ====== Criar usuário no banco.
						
						banco_insert_name_campo('id_hosts',$id_hosts);
						banco_insert_name_campo('id',$id);
						banco_insert_name_campo('nome',$dados['nome']);
						banco_insert_name_campo('nome_conta',$dados['nome']);
						
						banco_insert_name_campo('usuario',$dados['email']);
						banco_insert_name_campo('email',$dados['email']);
						banco_insert_name_campo('telefone',$dados['telefone']);
						banco_insert_name_campo('senha',$senhaHash);
						
						if($dados['cnpj_ativo'] == 'sim'){ banco_insert_name_campo('cnpj_ativo','1',true); }
						
						if(isset($dados['cpf'])){ banco_insert_name_campo('cpf',$dados['cpf']); }
						if(isset($dados['cnpj'])){ banco_insert_name_campo('cnpj',$dados['cnpj']); }
						
						if(isset($primeiro_nome)){ banco_insert_name_campo('primeiro_nome',$primeiro_nome); }
						if(isset($nome_do_meio)){ banco_insert_name_campo('nome_do_meio',$nome_do_meio); }
						if(isset($ultimo_nome)){ banco_insert_name_campo('ultimo_nome',$ultimo_nome); }
						
						banco_insert_name_campo('status','A');
						banco_insert_name_campo('versao','1');
						banco_insert_name_campo('data_criacao','NOW()',true);
						banco_insert_name_campo('data_modificacao','NOW()',true);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"hosts_usuarios"
						);
						
						$id_hosts_usuarios = banco_last_id();
						
						// ====== Criar token usuário no banco.
						
						banco_insert_name_campo('id_hosts',$id_hosts);
						banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
						banco_insert_name_campo('pubID',$dados['usuario_token']['pubID']);
						banco_insert_name_campo('expiration',$dados['usuario_token']['expiration']);
						banco_insert_name_campo('ip',$dados['usuario_token']['ip']);
						banco_insert_name_campo('user_agent',$dados['usuario_token']['user_agent']);
						banco_insert_name_campo('data_criacao','NOW()',true);

						banco_insert_name
						(
							banco_insert_name_campos(),
							"hosts_usuarios_tokens"
						);
						
						$id_hosts_usuarios_tokens = banco_last_id();
						
						// ===== Pegar dados do hosts_usuarios do banco de dados.
						
						$usuarios = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_usuarios',
							'campos' => '*',
							'extra' => 
								"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						));
						
						unset($usuarios['id_hosts']);
						unset($usuarios['senha']);
						
						// ===== Retornar os dados.
						
						$retorno = Array(
							'status' => 'OK',
							'data' => Array(
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'id_hosts_usuarios_tokens' => $id_hosts_usuarios_tokens,
								'usuarios' => $usuarios,
							),
						);
					}
				} else {
					// ===== Se o recaptcha for inválido, alertar o usuário.
					
					sleep(3);
					
					$botaoTxt = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid-btn'));
					
					$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid'));
					
					$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
					
					$retorno = Array(
						'status' => 'RECAPTCHA_INVALID',
						'error-msg' => $alerta,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'esqueceuSenha':
			
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: email e tokenPubId.
			
			if(isset($dados['email']) && isset($dados['tokenPubId'])){
				if(plataforma_cliente_recaptcha($dados['token'],$opcao)){
					// ===== Passar o email para letras minúsculas.
					
					$dados['email'] = strtolower($dados['email']);
					
					// ===== Pegar os campos obrigatórios.
					
					$email = banco_escape_field($dados['email']);
					$tokenPubId = banco_escape_field($dados['tokenPubId']);
					
					// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
					
					$hosts_usuarios = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios',
						'campos' => Array(
							'id_hosts_usuarios',
							'status',
							'email',
							'nome',
						),
						'extra' => 
							"WHERE email='".$email."'"
							." AND status!='D'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Verifica se já existe o usuário. Se sim, enviar email. Senão retornar o erro.
					
					if($hosts_usuarios){
						$id_hosts_usuarios = $hosts_usuarios['id_hosts_usuarios'];
						$status = $hosts_usuarios['status'];
						$email = $hosts_usuarios['email'];
						$nome = $hosts_usuarios['nome'];
						
						if($status == 'A'){
							// ===== Criar o token e guardar o mesmo no banco
							
							$expiration = time() + $_GESTOR['token-lifetime'];
				
							$pubID = hash_hmac($_GESTOR['usuario-hash-algo'], $tokenPubId, $_GESTOR['usuario-hash-password']);
							
							$campos = null; $campo_sem_aspas_simples = null;
							
							$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id_hosts_usuarios"; $campo_valor = $id_hosts_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id"; $campo_valor = 'forgot-password';				 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "pubID"; $campo_valor = $pubID; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "expiration"; $campo_valor = $expiration; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 						$campos[] = Array($campo_nome,$campo_valor,true);
							
							banco_insert_name
							(
								$campos,
								"hosts_tokens"
							);
							
							$tokens_id = banco_last_id();
							
							// ===== Enviar o email com as instruções para renovar a senha.
							
							$numero = date('Ymd') . $tokens_id;
							
							$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'forgot-password-mail-subject')),"#numero#",$numero);
							
							gestor_incluir_biblioteca(Array('comunicacao','host'));
							
							if(comunicacao_email(Array(
								'destinatarios' => Array(
									Array(
										'email' => $email,
										'nome' => $nome,
									),
								),
								'mensagem' => Array(
									'assunto' => $assunto,
									'htmlLayoutID' => 'hosts-layout-email-esqueceu-senha',
									'htmlVariaveis' => Array(
										Array(
											'variavel' => '#nome#',
											'valor' => $nome,
										),
										Array(
											'variavel' => '#url#',
											'valor' => '<a target="identificacao" href="'.host_url(Array('opcao'=>'full')).'identificacao-redefinir-senha/?id='.$tokenPubId.'">'.host_url(Array('opcao'=>'full')).'identificacao-redefinir-senha/?id='.$tokenPubId.'</a>',
										),
										Array(
											'variavel' => '#expiracao#',
											'valor' => $_GESTOR['token-lifetime'] / 3600,
										),
										Array(
											'variavel' => '#assinatura#',
											'valor' => modelo_var_troca_tudo(gestor_componente(Array('id' => 'hosts-layout-emails-assinatura')),'@[[url]]@',host_url(Array('opcao'=>'full')).'identificacao/')
										),
									),
								),
							))){
								// ===== Retornar mensagem de confirmação e o token criado.
								
								$message = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'forgot-password-confirmation-message-content'));
								
								$message = modelo_var_troca_tudo($message,"#email#",$email);
								
								$retorno = Array(
									'status' => 'OK',
									'data' => Array(
										'message' => $message,
										'id_hosts_usuarios' => $id_hosts_usuarios,
									)
								);
							} else {
								$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-email-not-sent'));
								
								$retorno = Array(
									'status' => 'EMAIL_NOT_SENT',
									'error-msg' => $alerta,
								);
							}
						} else {
							$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-inactive'));
							
							$retorno = Array(
								'status' => 'USER_INACTIVE',
								'error-msg' => $alerta,
							);
						}
					} else {
						sleep(3);
						
						$alerta = modelo_var_troca(gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-email-invalid')),"#email#",$email);
						
						$retorno = Array(
							'status' => 'THERE_IS_NO_USER',
							'error-msg' => $alerta,
						);
					}
				} else {
					// ===== Se o recaptcha for inválido, alertar o usuário.
					
					sleep(3);
					
					$botaoTxt = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid-btn'));
					
					$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-recaptcha-invalid'));
					
					$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
					
					$retorno = Array(
						'status' => 'RECAPTCHA_INVALID',
						'error-msg' => $alerta,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'redefinirSenha':
			
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: senha e tokenPubId.
			
			if(isset($dados['senha']) && isset($dados['tokenPubId'])){
				// ===== Pegar os campos obrigatórios.
				
				$senha = banco_escape_field($dados['senha']);
				$tokenPubId = banco_escape_field($dados['tokenPubId']);
				$tokenID = banco_escape_field($dados['tokenID']);
				$userIP = banco_escape_field($dados['userIP']);
				$userUserAgent = banco_escape_field($dados['userUserAgent']);
				
				// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
				
				$pubID = hash_hmac($_GESTOR['usuario-hash-algo'], $tokenPubId, $_GESTOR['usuario-hash-password']);
				
				// ===== Remover todos os tokens expirados.
				
				banco_delete
				(
					"hosts_tokens",
					"WHERE expiration < ".time()
				);
				
				// ===== Verificar o token no banco.
				
				$hosts_tokens = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_tokens',
					'campos' => Array(
						'id_hosts_tokens',
						'id_hosts_usuarios',
					),
					'extra' => 
						"WHERE pubID='".$pubID."'"
				));
				
				// ===== Caso autorizado atualizar senha no banco, senão retornar erro.
				
				if($hosts_tokens){
					// ===== Pegar o identificador do usuário.
					
					$id_hosts_tokens = $hosts_tokens['id_hosts_tokens'];
					$id_hosts_usuarios = $hosts_tokens['id_hosts_usuarios'];
					
					// ===== Gerar hash da senha
					
					$senhaHash = password_hash($senha, PASSWORD_ARGON2I, ["cost" => 9]);
					
					// ===== Atualizar senha no banco da conta do usuário.
					
					banco_update
					(
						"senha='".$senhaHash."'",
						"hosts_usuarios",
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					);
					
					// ===== Remover todos os acessos logados no sistema.
					
					banco_delete
					(
						"hosts_usuarios_tokens",
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					);
					
					// ===== Remover todos os acessos logados no sistema.
					
					banco_delete
					(
						"hosts_tokens",
						"WHERE id_hosts_tokens='".$id_hosts_tokens."'"
					);
					
					// ===== Criar histórico de alterações.
					
					$alteracaoTxt = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'reset-password'));
					
					$alteracaoTxt = modelo_var_troca($alteracaoTxt,"#ip#",$userIP);
					$alteracaoTxt = modelo_var_troca($alteracaoTxt,"#user-agent#",$userUserAgent);
					
					gestor_incluir_biblioteca('log');
					
					log_hosts_usuarios(Array(
						'id_hosts' => $id_hosts,
						'id_hosts_usuarios' => $id_hosts_usuarios,
						'id' => $id_hosts_usuarios,
						'tabela' => Array(
							'nome' => 'hosts_usuarios',
							'versao' => 'versao',
							'id_numerico' => 'id_hosts_usuarios',
						),
						'alteracoes' => Array(
							Array(
								'modulo' => 'hosts-usuarios',
								'alteracao' => 'reset-password',
								'alteracao_txt' => $alteracaoTxt,
							)
						),
					));
					
					// ===== Pegar os dados do usuário que serão usados para informar o mesmo.
					
					$hosts_usuarios = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios',
						'campos' => Array(
							'nome',
							'email',
						),
						'extra' => 
							"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					));
					
					$nome = $hosts_usuarios['nome'];
					$email = $hosts_usuarios['email'];
					
					// ===== Enviar o email informando da alteração da senha com sucesso.
					
					$numero = date('Ymd') . $tokenID;
					
					$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'password-redefined-mail-subject')),"#numero#",$numero);
					
					gestor_incluir_biblioteca('comunicacao');
					
					if(comunicacao_email(Array(
						'destinatarios' => Array(
							Array(
								'email' => $email,
								'nome' => $nome,
							),
						),
						'mensagem' => Array(
							'assunto' => $assunto,
							'htmlLayoutID' => 'layout-email-senha-redefinida',
							'htmlVariaveis' => Array(
								Array(
									'variavel' => '#nome#',
									'valor' => $nome,
								),
								Array(
									'variavel' => '#assinatura#',
									'valor' => gestor_componente(Array(
										'id' => 'layout-emails-assinatura',
									)),
								),
							),
						),
					))){
						$email_not_sent = false;
					} else {
						$email_not_sent = true;
					}
					
					// ===== Mensagem de retorno com as instruções.
					
					$message = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'redefine-password-confirmation-message-content'));
					$message = modelo_var_troca_tudo($message,"#url#",'<a href="/identificacao/">'.gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'forgot-password-login-button')).'</a>');
					
					// ===== Retornar OK.
					
					$retorno = Array(
						'status' => 'OK',
						'data' => Array(
							'message' => $message,
							'alteracaoTxt' => $alteracaoTxt,
						)
					);
				} else {
					sleep(3);
					
					$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-redefine-password-expiration-or-invalid'));
					
					$retorno = Array(
						'status' => 'TOKEN_EXPIRATION_OR_INVALID',
						'error-msg' => $alerta,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'areaRestrita':
			
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: email e tokenPubId.
			
			if(isset($dados['senha']) && isset($dados['usuarioID'])){
				// ===== Invalidar inicialmente o usuário.
				
				$user_invalid = true;
				
				// ===== Pegar os campos obrigatórios.
				
				$senha = banco_escape_field($dados['senha']);
				$id_hosts_usuarios = banco_escape_field($dados['usuarioID']);
				
				// ===== Procurar o usuário solicitado.
				
				$hosts_usuarios = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_usuarios',
					'campos' => Array(
						'senha',
						'status',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_usuarios){
					// ===== Conferir se o usuário está ativo no sistema.
					
					if($hosts_usuarios['status'] != 'A'){
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-usuario-inativo'));
						
						return Array(
							'status' => 'INACTIVE_USER',
							'error-msg' => $alerta,
						);
					}
					
					// ===== Verificar se a senha é correta.
					
					$senha_hash = $hosts_usuarios['senha'];
					
					if(password_verify($senha, $senha_hash)){
						$user_invalid = false;
					}
					
					// ===== Se o usuário for inválido, redirecionar signin.
					
					if($user_invalid){
						sleep(3);
						
						$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-or-password-invalid'));
						
						$retorno = Array(
							'status' => 'USER_INVALID',
							'error-msg' => $alerta,
							'data' => Array(
								'usuarioMaxSenhaInvalidas' => $_GESTOR['usuario-maximo-senhas-invalidas'],
							),
						);
					} else {
						// ===== Retornar os dados.
						
						$retorno = Array(
							'status' => 'OK',
						);
					}
				} else {
					sleep(3);
					
					$alerta = modelo_var_troca(gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-email-invalid')),"#email#",$email);
					
					$retorno = Array(
						'status' => 'THERE_IS_NO_USER',
						'error-msg' => $alerta,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
	
	return $retorno;
}

function plataforma_cliente_emissao(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'criarPedido':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: sessao_id, id_hosts_usuarios e id_hosts_carrinho.
			
			if(isset($dados['sessao_id']) && isset($dados['id_hosts_carrinho']) && isset($dados['id_hosts_usuarios'])){
				// ===== Filtrar o campo.
				
				$sessao_id = banco_escape_field($dados['sessao_id']);
				$id_hosts_carrinho = banco_escape_field($dados['id_hosts_carrinho']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				
				// ===== Verificar o carrinho no banco.
				
				$hosts_carrinho = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_carrinho',
					'campos' => Array(
						'id_hosts_carrinho',
					),
					'extra' => 
						"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
						." AND sessao_id='".$sessao_id."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_carrinho){
					// ===== Incluir configurações.
					
					$config = gestor_incluir_configuracao(Array(
						'id' => 'loja-configuracoes.config',
					));
					
					// ===== Verificar os serviços do carrinho.
					
					$hosts_carrinho_servicos = banco_select(Array(
						'tabela' => 'hosts_carrinho_servicos',
						'campos' => Array(
							'id_hosts_servicos',
							'id_hosts_arquivos_Imagem',
							'nome',
							'preco',
							'quantidade',
							'gratuito',
						),
						'extra' => 
							"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Verificar as variações dos serviços do carrinho.
					
					$hosts_carrinho_servico_variacoes = banco_select(Array(
						'tabela' => 'hosts_carrinho_servico_variacoes',
						'campos' => Array(
							'id_hosts_servicos',
							'id_hosts_servicos_lotes',
							'id_hosts_servicos_variacoes',
							'id_hosts_arquivos_Imagem',
							'nome_servico',
							'nome_lote',
							'nome_variacao',
							'preco',
							'quantidade',
							'gratuito',
						),
						'extra' => 
							"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Total Pedidos.
					
					$total_hosts_pedidos = banco_total_rows
					(
						"hosts_pedidos",
						""
					);
					
					// ===== Total Vouchers.
					
					$total_hosts_vouchers = banco_total_rows
					(
						"hosts_vouchers",
						""
					);
					
					// ===== Total valor.
					
					$total = 0;
					
					// ===== Varrrer todos os seviços e criar os mesmos no novo pedido.
					
					if($hosts_carrinho_servicos){
						foreach($hosts_carrinho_servicos as $carrServ){
							if((int)$carrServ['quantidade'] > 0){
								// ===== Criar pedido caso não tenha sido criado ainda.
								
								if(!isset($pedido)){
									// ===== Definição do código do pedido.
									
									$codigo = 'P' . ($total_hosts_pedidos + $config['pedidos']['codigoInicial']);
									
									// ===== Incluir pedido no banco de dados.
									
									$campos = null; $campo_sem_aspas_simples = null;
									
									$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_hosts_usuarios"; $campo_valor = $id_hosts_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id"; $campo_valor = $codigo; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "codigo"; $campo_valor = $codigo; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "status"; $campo_valor = 'novo'; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "versao"; $campo_valor = '1'; 								$campos[] = Array($campo_nome,$campo_valor,true);
									$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 						$campos[] = Array($campo_nome,$campo_valor,true);
									$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 					$campos[] = Array($campo_nome,$campo_valor,true);
									
									banco_insert_name
									(
										$campos,
										"hosts_pedidos"
									);
									
									$id_hosts_pedidos = banco_last_id();
									
									$pedido = true;
								}
								
								// ===== Referência do serviço.
								
								$id_hosts_servicos = $carrServ['id_hosts_servicos'];
								
								// ===== Criar serviço no pedido.
								
								$campos = null; $campo_sem_aspas_simples = null;
		
								$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 												$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_pedidos"; $campo_valor = $id_hosts_pedidos; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos"; $campo_valor = $id_hosts_servicos; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_arquivos_Imagem"; $campo_valor = $carrServ['id_hosts_arquivos_Imagem']; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome"; $campo_valor = $carrServ['nome']; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "preco"; $campo_valor = $carrServ['preco']; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "quantidade"; $campo_valor = $carrServ['quantidade']; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "gratuito"; $campo_valor = ($carrServ['gratuito'] ? '1' : 'NULL');					$campos[] = Array($campo_nome,$campo_valor,true);
								
								banco_insert_name
								(
									$campos,
									"hosts_pedidos_servicos"
								);
								
								// ===== Atualizar o valor total do pedido.
								
								$total += (float)$carrServ['preco'] * (int)$carrServ['quantidade'];
								
								// ===== Criar os vouchers de cada serviço.
								
								for($voucher = 0;$voucher < (int)$carrServ['quantidade'];$voucher++){
									// ===== Definição do código do voucher.
									
									$codigo = 'V' . ($total_hosts_vouchers + $config['pedidos']['codigoInicial']);
									
									// ===== Criar voucher do serviço.
									
									$campos = null; $campo_sem_aspas_simples = null;
			
									$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 									$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_hosts_pedidos"; $campo_valor = $id_hosts_pedidos; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_hosts_servicos"; $campo_valor = $id_hosts_servicos; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "codigo"; $campo_valor = $codigo; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "status"; $campo_valor = 'novo'; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									
									banco_insert_name
									(
										$campos,
										"hosts_vouchers"
									);
									
									$total_hosts_vouchers++;
								}
								
								// ===== Pegar dados do serviço.
								
								$hosts_servicos = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos',
									'campos' => Array(
										'quantidade_pedidos_pendentes',
										'quantidade_carrinhos',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts='".$id_hosts."'"
								));
								
								// ===== Quantidade para alterar no estoque início.
								
								$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
								$pedidosPendentesQuantidade = (int)$hosts_servicos['quantidade_pedidos_pendentes'];
								$quantidadeEstoqueAlterar = (int)$carrServ['quantidade'];
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." + ".$quantidadeEstoqueAlterar.","
									."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
									"hosts_servicos",
									"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
								);
							}
						}
						
						// ===== Remover carrinho serviços.	
						
						banco_delete
						(
							"hosts_carrinho_servicos",
							"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
							." AND id_hosts='".$id_hosts."'"
						);
					}
					
					// ===== Varrrer todas as variações dos seviços e criar os mesmos no novo pedido.
					
					if($hosts_carrinho_servico_variacoes){
						foreach($hosts_carrinho_servico_variacoes as $carrServ){
							if((int)$carrServ['quantidade'] > 0){
								// ===== Criar pedido caso não tenha sido criado ainda.
								
								if(!isset($pedido)){
									// ===== Definição do código do pedido.
									
									$codigo = 'P' . ($total_hosts_pedidos + $config['pedidos']['codigoInicial']);
									
									// ===== Incluir pedido no banco de dados.
									
									$campos = null; $campo_sem_aspas_simples = null;
									
									$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_hosts_usuarios"; $campo_valor = $id_hosts_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id"; $campo_valor = $codigo; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "codigo"; $campo_valor = $codigo; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "status"; $campo_valor = 'novo'; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "versao"; $campo_valor = '1'; 								$campos[] = Array($campo_nome,$campo_valor,true);
									$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 						$campos[] = Array($campo_nome,$campo_valor,true);
									$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 					$campos[] = Array($campo_nome,$campo_valor,true);
									
									banco_insert_name
									(
										$campos,
										"hosts_pedidos"
									);
									
									$id_hosts_pedidos = banco_last_id();
									
									$pedido = true;
								}
								
								// ===== Referência do serviço, lote e variação.
								
								$id_hosts_servicos = $carrServ['id_hosts_servicos'];
								$id_hosts_servicos_variacoes = $carrServ['id_hosts_servicos_variacoes'];
								$id_hosts_servicos_lotes = $carrServ['id_hosts_servicos_lotes'];
								
								// ===== Criar serviço no pedido.
								
								$campos = null; $campo_sem_aspas_simples = null;
		
								$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 												$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_pedidos"; $campo_valor = $id_hosts_pedidos; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos"; $campo_valor = $id_hosts_servicos; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos_lotes"; $campo_valor = $id_hosts_servicos_lotes; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_servicos_variacoes"; $campo_valor = $id_hosts_servicos_variacoes; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "id_hosts_arquivos_Imagem"; $campo_valor = $carrServ['id_hosts_arquivos_Imagem']; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome_servico"; $campo_valor = $carrServ['nome_servico']; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome_lote"; $campo_valor = $carrServ['nome_lote']; 									$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "nome_variacao"; $campo_valor = $carrServ['nome_variacao']; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "preco"; $campo_valor = $carrServ['preco']; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "quantidade"; $campo_valor = $carrServ['quantidade']; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								$campo_nome = "gratuito"; $campo_valor = ($carrServ['gratuito'] ? '1' : 'NULL');					$campos[] = Array($campo_nome,$campo_valor,true);
								
								banco_insert_name
								(
									$campos,
									"hosts_pedidos_servico_variacoes"
								);
								
								// ===== Atualizar o valor total do pedido.
								
								$total += (float)$carrServ['preco'] * (int)$carrServ['quantidade'];
								
								// ===== Criar os vouchers de cada serviço.
								
								for($voucher = 0;$voucher < (int)$carrServ['quantidade'];$voucher++){
									// ===== Definição do código do voucher.
									
									$codigo = 'V' . ($total_hosts_vouchers + $config['pedidos']['codigoInicial']);
									
									// ===== Criar voucher do serviço.
									
									$campos = null; $campo_sem_aspas_simples = null;
			
									$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_hosts_pedidos"; $campo_valor = $id_hosts_pedidos; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_hosts_servicos"; $campo_valor = $id_hosts_servicos; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_hosts_servicos_variacoes"; $campo_valor = $id_hosts_servicos_variacoes; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "codigo"; $campo_valor = $codigo; 															$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "status"; $campo_valor = 'novo'; 																$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "loteVariacao"; $campo_valor = '1';															$campos[] = Array($campo_nome,$campo_valor,true);
									
									banco_insert_name
									(
										$campos,
										"hosts_vouchers"
									);
									
									$total_hosts_vouchers++;
								}
								
								// ===== Pegar dados do serviço.
								
								$hosts_servicos_variacoes = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos_variacoes',
									'campos' => Array(
										'quantidade_pedidos_pendentes',
										'quantidade_carrinhos',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts='".$id_hosts."'"
										." AND id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
								));
								
								// ===== Quantidade para alterar no estoque início.
								
								$carrinhosQuantidade = (int)$hosts_servicos_variacoes['quantidade_carrinhos'];
								$pedidosPendentesQuantidade = (int)$hosts_servicos_variacoes['quantidade_pedidos_pendentes'];
								$quantidadeEstoqueAlterar = (int)$carrServ['quantidade'];
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." + ".$quantidadeEstoqueAlterar.","
									."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
									"hosts_servicos_variacoes",
									"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
									." AND id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
								);
								
								// ===== Pegar dados do serviço.
								
								$hosts_servicos = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_servicos',
									'campos' => Array(
										'quantidade_pedidos_pendentes',
										'quantidade_carrinhos',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts='".$id_hosts."'"
								));
								
								// ===== Quantidade para alterar no estoque início.
								
								$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
								$pedidosPendentesQuantidade = (int)$hosts_servicos['quantidade_pedidos_pendentes'];
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." + ".$quantidadeEstoqueAlterar.","
									."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
									"hosts_servicos",
									"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
								);
							}
						}
						
						// ===== Remover carrinho variações dos serviços.	
						
						banco_delete
						(
							"hosts_carrinho_servico_variacoes",
							"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
							." AND id_hosts='".$id_hosts."'"
						);
					}
					
					// ===== Remover carrinho.	
					
					banco_delete
					(
						"hosts_carrinho",
						"WHERE id_hosts_carrinho='".$id_hosts_carrinho."'"
						." AND sessao_id='".$sessao_id."'"
						." AND id_hosts='".$id_hosts."'"
					);
					
					// ===== Atualizar valor total do pedido no banco.
					
					if(isset($pedido)){
						banco_update
						(
							"total='".$total."'",
							"hosts_pedidos",
							"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
						);
						
						// ===== Campos para criar no host.
					
						$config = gestor_incluir_configuracao(Array(
							'id' => 'pedidos.config',
						));
						
						// ===== Retornar o pedido com os serviços e vouchers.
						
						$hosts_pedidos = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_pedidos',
							'campos' => $config['criarCampos'],
							'extra' => 
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
						));
						
						$hosts_pedidos_servicos = banco_select(Array(
							'tabela' => 'hosts_pedidos_servicos',
							'campos' => '*',
							'extra' => 
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
						));
						
						$hosts_pedidos_servicos_proc = Array();
						if($hosts_pedidos_servicos)
						foreach($hosts_pedidos_servicos as $pedServ){
							unset($pedServ['id_hosts']);
							
							$hosts_pedidos_servicos_proc[] = $pedServ;
						}
						
						$hosts_pedidos_servico_variacoes = banco_select(Array(
							'tabela' => 'hosts_pedidos_servico_variacoes',
							'campos' => '*',
							'extra' => 
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
						));
						
						$hosts_pedidos_servico_variacoes_proc = Array();
						if($hosts_pedidos_servico_variacoes)
						foreach($hosts_pedidos_servico_variacoes as $pedServ){
							unset($pedServ['id_hosts']);
							
							$hosts_pedidos_servico_variacoes_proc[] = $pedServ;
						}
						
						$hosts_vouchers = banco_select(Array(
							'tabela' => 'hosts_vouchers',
							'campos' => $config['vouchersCriarCampos'],
							'extra' => 
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
						));
						
						if($hosts_vouchers)
						foreach($hosts_vouchers as $pedVou){
							$hosts_vouchers_proc[] = $pedVou;
						}
						
						// ===== Retornar dados.
						
						$retorno = Array(
							'status' => 'OK',
							'data' => Array(
								'hosts_pedidos' => $hosts_pedidos,
								'hosts_pedidos_servicos' => $hosts_pedidos_servicos_proc,
								'hosts_pedidos_servico_variacoes' => $hosts_pedidos_servico_variacoes_proc,
								'hosts_vouchers' => $hosts_vouchers_proc,
							),
						);
					} else {
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-carrinho-vazio'));
						
						$retorno = Array(
							'status' => 'ORDER_NOT_COMPLETED',
							'error-msg' => $alerta,
						);
					}
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-carrinho-nao-encontrado'));
					
					$retorno = Array(
						'status' => 'CART_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'salvarIdentidades':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo e id_hosts_usuarios.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios'])){
				// ===== Filtrar o campo.
				
				$codigo = banco_escape_field($dados['codigo']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				
				if(isset($dados['vouchersAlterados'])){
					$vouchersAlterados = $dados['vouchersAlterados'];
				}
				
				// ===== Pedidos no banco de dados.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND codigo='".$codigo."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Caso exista o pedido, continuar, senão retornar erro.
				
				if($hosts_pedidos){
					$hosts_vouchers = banco_select(Array(
						'tabela' => 'hosts_vouchers',
						'campos' => Array(
							'id_hosts_vouchers',
							'codigo',
							'nome',
							'documento',
							'telefone',
						),
						'extra' => 
							"WHERE id_hosts_pedidos='".$hosts_pedidos['id_hosts_pedidos']."'"
					));
					
					// ===== Atualizar a identidade de cada voucher.
					
					if($hosts_vouchers){
						foreach($hosts_vouchers as $hosts_voucher){
							$found_alterado = false;
							$atualizar = false;
							
							// ===== Procurar identidades alteradas.
							
							if(isset($vouchersAlterados)){
								foreach($vouchersAlterados as $voucherID => $vouchersAlterado){
									if($hosts_voucher['codigo'] == $voucherID){
										$voucher = $vouchersAlterado;
										$found_alterado = true;
										$atualizar = true;
										break;
									}
								}
							}
							
							// ===== Caso encontrou uma identidade alterada, aplicar os valores para o voucher.
							
							if(!$found_alterado){
								// ===== Senão existe identidade, criar dos dados do usuário.
								
								if(!existe($hosts_voucher['nome']) || !existe($hosts_voucher['documento']) || !existe($hosts_voucher['telefone'])){
									// ===== Senão pegar os dados da conta do usuário específico.
									
									if(!isset($usuario)){
										gestor_incluir_biblioteca('usuario');
										
										$usuarioDados = usuario_host_dados(Array('id_hosts_usuarios' => $id_hosts_usuarios));
										
										$usuario['nome'] = $usuarioDados['nome'];
										$usuario['documento'] = (existe($usuarioDados['cnpj_ativo']) ? $usuarioDados['cnpj'] : $usuarioDados['cpf']);
										$usuario['telefone'] = $usuarioDados['telefone'];
									}
									
									$voucher = $usuario;
									$atualizar = true;
								}
							}
							
							// ===== Atualizar a identidade no banco de dados.
							
							if($atualizar){
								banco_update_campo('nome',$voucher['nome']);
								banco_update_campo('documento',$voucher['documento']);
								banco_update_campo('telefone',$voucher['telefone']);
								
								banco_update_executar('hosts_vouchers',"WHERE id_hosts_vouchers='".$hosts_voucher['id_hosts_vouchers']."'");
							}
						}
						
						// ===== Atualizar pedido no banco.
						
						banco_update
						(
							"data_modificacao=NOW(),".
							"versao=versao+1",
							"hosts_pedidos",
							"WHERE id_hosts_pedidos='".$hosts_pedidos['id_hosts_pedidos']."'"
						);
						
						// ===== Incluir o histórico da alteração no pedido.
						
						gestor_incluir_biblioteca('log');
						
						log_hosts_usuarios(Array(
							'id_hosts' => $id_hosts,
							'id_hosts_usuarios' => $id_hosts_usuarios,
							'id' => $hosts_pedidos['id_hosts_pedidos'],
							'tabela' => Array(
								'nome' => 'hosts_pedidos',
								'versao' => 'versao',
								'id_numerico' => 'id_hosts_pedidos',
							),
							'alteracoes' => Array(
								Array(
									'modulo' => 'pedidos',
									'alteracao' => 'update-emissao',
									'alteracao_txt' => 'Usuário salvou a identificação do(s) voucher(s)',
								)
							),
						));
						
						// ===== Campos para atualizar.
					
						$config = gestor_incluir_configuracao(Array(
							'id' => 'pedidos.config',
						));
						
						// ===== Retornar o hosts_vouchers do pedido.
						
						$hosts_vouchers = banco_select(Array(
							'tabela' => 'hosts_vouchers',
							'campos' => $config['vouchersAtualizarCampos'],
							'extra' => 
								"WHERE id_hosts_pedidos='".$hosts_pedidos['id_hosts_pedidos']."'"
						));
						
						if($hosts_vouchers){
							foreach($hosts_vouchers as $hosts_voucher){
								unset($hosts_voucher['id_hosts']);
								
								$hosts_vouchers_proc[] = $hosts_voucher;
							}
						}
						
						// ===== Retornar dados.
						
						$retorno = Array(
							'status' => 'OK',
							'data' => Array(
								'hosts_vouchers' => $hosts_vouchers_proc,
							),
						);
					}
				} else {
					$retorno = Array(
						'status' => 'ORDER_NOT_FOUND',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
	
	return $retorno;
}

function plataforma_cliente_pagamento(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'paypalplus-criar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo, id_hosts_usuarios, botao e outroPagador.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios']) && isset($dados['botao']) && isset($dados['outroPagador'])){
				// ===== Filtrar os campos.
				
				$codigo = banco_escape_field($dados['codigo']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				$outroPagador = $dados['outroPagador'];
				$botao = $dados['botao'];
				
				if($outroPagador != 'nao') $outro_pagador = json_decode($outroPagador,true);
				
				// ===== Procurar o pedido solicitado.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
						'status',
						'live',
					),
					'extra' => 
						"WHERE codigo='".$codigo."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_pedidos){
					// ===== Id do pedido.
					
					$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
					
					// ===== Pegar dados do PayPal.
					
					$hosts_paypal = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_paypal',
						'campos' => Array(
							'app_installed',
							'app_active',
							'app_live',
						),
						'extra' => 
							"WHERE id_hosts='".$id_hosts."'"
					));
					
					// ===== Verificar se o Paypal está ativo ou então não instalado.
					
					if($hosts_paypal['app_installed'] && $hosts_paypal['app_active']){
						// ===== Conferir o status atual.
						
						switch($hosts_pedidos['status']){
							case 'novo':
								// ===== Se for primeira requisição do pedido, atualizar status e se é live ou sandbox.
								
								banco_update_campo('status','aguardando-pagamento');
								if($hosts_paypal['app_live']) banco_update_campo('live','1',true);
								
								banco_update_executar('hosts_pedidos',"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'");
								
								// ===== Definir se é live ou sandbox.
								
								if($hosts_paypal['app_live']) $live = true;
							break;
							case 'aguardando-pagamento':
								// ===== Definir se é live ou sandbox.
								
								if($hosts_pedidos['live']) $live = true;
							break;
							default:
								// ===== Não permitir pagamento com status diferente de 'novo' e 'aguardando-pagamento'.
								
								$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-status-invalido'));
								
								return Array(
									'status' => 'STATUS_INVALID',
									'error-msg' => $alerta,
								);
						}
						
						// ===== Limitar quantidade máxima de requisição de pagamentos por período.
						
						$config = gestor_incluir_configuracao(Array(
							'id' => 'paypal.config',
						));
						
						$minutos = $config['pagamentos-tentativas-minutos'];
						$ppplus_periodo_segundos = 60 * $minutos;
						
						$hosts_paypal_pagamentos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_hosts_paypal_pagamentos',
							))
							,
							"hosts_paypal_pagamentos",
							"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							." AND UNIX_TIMESTAMP(data_criacao) > ".(time()-$ppplus_periodo_segundos).""
						);
						
						if(count($hosts_paypal_pagamentos) >= $config['pagamentos-maximo-tentativas']){
							$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-tentativas-maximas-alcancadas'));
							
							$alerta = modelo_var_troca_tudo($alerta,"#maximo#",$var);
							$alerta = modelo_var_troca_tudo($alerta,"#tentativas#",$var);
							
							return Array(
								'status' => 'MAXIMUM_ATTEMPTS_PER_PERIOD',
								'error-msg' => $alerta,
							);
						}
						
						// ===== Criar a requisição de pagamento.
						
						gestor_incluir_biblioteca('paypal');
						gestor_incluir_biblioteca('log');
						
						$retorno = paypal_criar_pagamento(Array(
							'codigo' => $codigo,
							'id_hosts' => $id_hosts,
							'id_hosts_usuarios' => $id_hosts_usuarios,
							'live' => (isset($live) ? true : null),
							'outro_pagador' => (isset($outro_pagador) ? $outro_pagador : null),
						));
						
						if(!$retorno['completed']){
							log_disco('[plataforma_cliente_pagamento][paypalplus-criar] - [codigo-'.$codigo.'][id_hosts_usuarios-'.$id_hosts_usuarios.'][id_hosts-'.$id_hosts.'] - error retorno: '.print_r($retorno,true));
							
							return $retorno;
						} else {
							// ===== Atualizar pedido no banco.
							
							banco_update
							(
								"data_modificacao=NOW(),".
								"versao=versao+1",
								"hosts_pedidos",
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							);
							
							// ===== Gravar histórico no pedido.
							
							log_hosts_usuarios(Array(
								'id_hosts' => $id_hosts,
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'id' => $id_hosts_pedidos,
								'tabela' => Array(
									'nome' => 'hosts_pedidos',
									'versao' => 'versao',
									'id_numerico' => 'id_hosts_pedidos',
								),
								'alteracoes' => Array(
									Array(
										'modulo' => 'pedidos',
										'alteracao' => 'payment-created',
										'alteracao_txt' => 'Nova requisição de pagamento: <b>ID Requisição: '.$retorno['ppplus']['pay_id'].' - '.($botao == 'sim' ? 'PAYPAL' : (isset($outro_pagador) ? 'CARTÃO DE TERCEIRO' : 'CARTÃO PRÓPRIO')).'</b>',
									)
								),
							));
							
							// ===== Campos para atualizar.
						
							$config = gestor_incluir_configuracao(Array(
								'id' => 'pedidos.config',
							));
							
							// ===== Atualizar pedido no host.
							
							$hosts_pedidos = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_pedidos',
								'campos' => $config['atualizarCampos'],
								'extra' => 
									"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							$pedidos_proc = $hosts_pedidos;
							
							// ===== Retornar dados.
							
							return Array(
								'status' => 'OK',
								'data' => Array(
									'ppplus' => $retorno['ppplus'],
									'pedido' => $pedidos_proc,
								),
							);
						}
					} else {
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-inativo'));
						
						return Array(
							'status' => 'PAYPAL_INACTIVE',
							'error-msg' => $alerta,
						);
					}
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-encontrado'));
					
					return Array(
						'status' => 'ORDER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'paypalplus-pagar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo, id_hosts_usuarios, pay_id e payerID.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios']) && isset($dados['pay_id']) && isset($dados['payerID'])){
				// ===== Filtrar os campos.
				
				$codigo = banco_escape_field($dados['codigo']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				$pay_id = banco_escape_field($dados['pay_id']);
				$payerID = banco_escape_field($dados['payerID']);
				$rememberedCard = banco_escape_field($dados['rememberedCard']);
				$installmentsValue = banco_escape_field($dados['installmentsValue']);
				$paypalButton = $dados['paypalButton'];
				$outroPagador = $dados['outroPagador'];
				
				// ===== Procurar o pedido solicitado.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
						'status',
						'live',
					),
					'extra' => 
						"WHERE codigo='".$codigo."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_pedidos){
					// ===== Id do pedido.
					
					$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
					
					// ===== Pegar dados do PayPal.
					
					$hosts_paypal = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_paypal',
						'campos' => Array(
							'app_installed',
							'app_active',
							'app_live',
						),
						'extra' => 
							"WHERE id_hosts='".$id_hosts."'"
					));
					
					// ===== Verificar se o Paypal está ativo ou então não instalado.
					
					if($hosts_paypal['app_installed'] && $hosts_paypal['app_active']){
						// ===== Conferir o status atual.
						
						switch($hosts_pedidos['status']){
							case 'novo':
								// ===== Se for primeira requisição do pedido, atualizar status e se é live ou sandbox.
								
								banco_update_campo('status','aguardando-pagamento');
								if($hosts_paypal['app_live']) banco_update_campo('live','1',true);
								
								banco_update_executar('hosts_pedidos',"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'");
								
								// ===== Definir se é live ou sandbox.
								
								if($hosts_paypal['app_live']) $live = true;
							break;
							case 'aguardando-pagamento':
								// ===== Definir se é live ou sandbox.
								
								if($hosts_pedidos['live']) $live = true;
							break;
							default:
								// ===== Não permitir pagamento com status diferente de 'novo' e 'aguardando-pagamento'.
								
								$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-status-invalido'));
								
								return Array(
									'status' => 'STATUS_INVALID',
									'error-msg' => $alerta,
								);
						}
						
						// ===== Guardar o hash do cartão.
						
						if($outroPagador == 'nao' && strlen($rememberedCard) > 5){
							banco_update_campo('ppp_remembered_card_hash',$rememberedCard);
							
							banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."' AND id_hosts='".$id_hosts."'");
						}
						
						// ===== Criar a requisição de pagamento.
						
						gestor_incluir_biblioteca('paypal');
						gestor_incluir_biblioteca('log');
						
						$retorno = paypal_executar_pagamento(Array(
							'codigo' => $codigo,
							'id_hosts' => $id_hosts,
							'pay_id' => $pay_id,
							'payerID' => $payerID,
							'installmentsValue' => $installmentsValue,
							'paypalButton' => ($paypalButton == 'nao' ? null : true),
							'live' => (isset($live) ? true : null),
						));
						
						if(!$retorno['completed']){
							log_disco('[plataforma_cliente_pagamento][paypalplus-pagar] - [codigo-'.$codigo.'][id_hosts_usuarios-'.$id_hosts_usuarios.'][id_hosts-'.$id_hosts.'] - error retorno: '.print_r($retorno,true));
							
							return $retorno;
						} else {
							// ===== Fazer alterações caso seja pedido pago ou pendente.
							
							if($retorno['pending']){
								$status = 'pendente';
								$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-pagamento-pendente'));
								$logMsg = 'Finalização do processo de pagamento! Status: <b>Pagamento em Análise</b> | <b>ID Final: '.$retorno['final_id'].'</b> | <b>ID Requisição: '.$pay_id.'</b>';
							} else {
								$status = 'pago';
								$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-pagamento-completado'));
								$logMsg = 'Finalização do pagamento executado com sucesso! Status: <b>Pago</b> | <b>ID Final: '.$retorno['final_id'].'</b> | <b>ID Requisição: '.$pay_id.'</b>';
							}
							
							// ===== Atualizar pedido no banco.
							
							banco_update
							(
								"status='".$status."',".
								"data_modificacao=NOW(),".
								"versao=versao+1",
								"hosts_pedidos",
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							);
							
							// ===== Gravar histórico no pedido.
							
							log_hosts_usuarios(Array(
								'id_hosts' => $id_hosts,
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'id' => $id_hosts_pedidos,
								'tabela' => Array(
									'nome' => 'hosts_pedidos',
									'versao' => 'versao',
									'id_numerico' => 'id_hosts_pedidos',
								),
								'alteracoes' => Array(
									Array(
										'modulo' => 'pedidos',
										'alteracao' => 'payment-completed',
										'alteracao_txt' => $logMsg,
									)
								),
							));
							
							// ===== Campos para atualizar.
						
							$config = gestor_incluir_configuracao(Array(
								'id' => 'pedidos.config',
							));
							
							// ===== Atualizar pedido no host.
							
							$hosts_pedidos = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_pedidos',
								'campos' => $config['atualizarCampos'],
								'extra' => 
									"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							$pedidos_proc = $hosts_pedidos;
							
							// ===== Retornar dados.
							
							return Array(
								'status' => 'OK',
								'data' => Array(
									'pending' => ($retorno['pending'] ? 'sim' : 'nao'),
									'alerta' => $alerta,
									'pedido' => $pedidos_proc,
								),
							);
						}
					} else {
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-inativo'));
						
						return Array(
							'status' => 'PAYPAL_INACTIVE',
							'error-msg' => $alerta,
						);
					}
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-encontrado'));
					
					return Array(
						'status' => 'ORDER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'log':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo, id_hosts_usuarios, msg e erro.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios']) && isset($dados['msg']) && isset($dados['erro'])){
				// ===== Filtrar os campos.
				
				$codigo = banco_escape_field($dados['codigo']);
				$msg = preg_replace('/'.preg_quote('\\').'/i', '', $dados['msg']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				$erro = $dados['erro'];
				
				$erro = json_decode($erro,true);
				
				// ===== Procurar o pedido solicitado.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
						'status',
						'live',
					),
					'extra' => 
						"WHERE codigo='".$codigo."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_pedidos){
					// ===== Id do pedido.
					
					$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
					
					// ===== Guardar log técnico.
					
					gestor_incluir_biblioteca('log');
					
					log_disco('[plataforma_cliente_pagamento][log] - [codigo-'.$codigo.'][id_hosts_usuarios-'.$id_hosts_usuarios.'][id_hosts-'.$id_hosts.'] - erro: '.print_r($erro,true));
					
					// ===== Atualizar pedido no banco.
					
					banco_update
					(
						"data_modificacao=NOW(),".
						"versao=versao+1",
						"hosts_pedidos",
						"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
					);
					
					// ===== Gravar histórico no pedido.
					
					log_hosts_usuarios(Array(
						'id_hosts' => $id_hosts,
						'id_hosts_usuarios' => $id_hosts_usuarios,
						'id' => $id_hosts_pedidos,
						'tabela' => Array(
							'nome' => 'hosts_pedidos',
							'versao' => 'versao',
							'id_numerico' => 'id_hosts_pedidos',
						),
						'alteracoes' => Array(
							Array(
								'modulo' => 'pedidos',
								'alteracao' => 'log',
								'alteracao_txt' => 'Alertado ao cliente no pagamento: <b>'.$msg.'</b>',
							)
						),
					));
					
					// ===== Retornar dados.
					
					return Array(
						'status' => 'OK',
					);
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-encontrado'));
					
					return Array(
						'status' => 'ORDER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'pedido-gratuito-processar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo e id_hosts_usuarios.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios'])){
				// ===== Filtrar os campos.
				
				$codigo = banco_escape_field($dados['codigo']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				
				// ===== Procurar o pedido solicitado.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
						'status',
						'total',
					),
					'extra' => 
						"WHERE codigo='".$codigo."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_pedidos){
					// ===== Id do pedido.
					
					$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
					
					// ===== Conferir o status atual.
					
					switch($hosts_pedidos['status']){
						case 'novo':
						case 'aguardando-pagamento':
							// ===== Caso seja pedido novo ou aguardando-pagamento permitir continuar.
							
						break;
						default:
							// ===== Não permitir pagamento com status diferente de 'novo' e 'aguardando-pagamento'.
							
							$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-paypal-status-invalido'));
							
							return Array(
								'status' => 'STATUS_INVALID',
								'error-msg' => $alerta,
							);
					}
					
					// ===== Se o valor total do pedido for maior que zero, retornar erro e não prosseguir.
					
					if((int)$hosts_pedidos['total'] > 0){
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pagamento-gratuito-total-maior-que-zero'));
						
						return Array(
							'status' => 'STATUS_INVALID',
							'error-msg' => $alerta,
						);
					}
					
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
					
					// ===== Fazer alterações de pedido pago.
					
					$status = 'pago';
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pagamento-gratuito-completado'));
					$logMsg = 'Finalização de pedido <b>gratuito</b> executado com sucesso! Status: <b>Pago</b>';
				
					// ===== Atualizar pedido no banco.
					
					banco_update
					(
						"status='".$status."',".
						"data_modificacao=NOW(),".
						"versao=versao+1",
						"hosts_pedidos",
						"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
					);
					
					// ===== Gravar histórico no pedido.
					
					gestor_incluir_biblioteca('log');
					
					log_hosts_usuarios(Array(
						'id_hosts' => $id_hosts,
						'id_hosts_usuarios' => $id_hosts_usuarios,
						'id' => $id_hosts_pedidos,
						'tabela' => Array(
							'nome' => 'hosts_pedidos',
							'versao' => 'versao',
							'id_numerico' => 'id_hosts_pedidos',
						),
						'alteracoes' => Array(
							Array(
								'modulo' => 'pedidos',
								'alteracao' => 'payment-completed',
								'alteracao_txt' => $logMsg,
							)
						),
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
							"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
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
					
					// ===== Email layout e status.
					
					$mensagemLayout = 'plataforma-gateways-mensagem-pago-gratuito';
					$statusTitulo = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'status-paid'));
					
					// ===== URL das compras do usuário.
					
					$dominio = host_url(Array('opcao' => 'full','id_hosts' => $id_hosts));
					$url = '<a href="'.$dominio.'meus-pedidos/">'.$dominio.'meus-pedidos/</a>';
					
					// ===== URL do minhas compras.
					
					$mensagemVariaveis[] = Array('variavel' => '@[[url]]@','valor' => $url);
					
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
					
					// ===== Campos para atualizar.
				
					$config = gestor_incluir_configuracao(Array(
						'id' => 'pedidos.config',
					));
					
					// ===== Atualizar pedido no host.
					
					$hosts_pedidos = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_pedidos',
						'campos' => $config['atualizarCampos'],
						'extra' => 
							"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					$pedidos_proc = $hosts_pedidos;
					
					// ===== Retornar dados.
					
					return Array(
						'status' => 'OK',
						'data' => Array(
							'alerta' => $alerta,
							'pedido' => $pedidos_proc,
						),
					);
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-encontrado'));
					
					return Array(
						'status' => 'ORDER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		default:
			return  Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
}

function plataforma_cliente_voucher(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'todos':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo e id_hosts_usuarios.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios'])){
				// ===== Filtrar os campos.
				
				$codigo = banco_escape_field($dados['codigo']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				$reemitir = $dados['reemitir'];
				
				// ===== Procurar o pedido solicitado.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
						'jwt_emitidos',
						'jwt_bd_expiracao',
						'status',
					),
					'extra' => 
						"WHERE codigo='".$codigo."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_pedidos){
					// ===== Conferir se o pedido foi pago.
					
					if($hosts_pedidos['status'] != 'pago'){
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-pago'));
						
						return Array(
							'status' => 'ORDER_NOT_PAYED',
							'error-msg' => $alerta,
						);
					}
					
					// ===== Id do pedido.
					
					$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
					
					// ===== Verificar expiração da emissão.
					
					if($hosts_pedidos['jwt_bd_expiracao']){
						$expiracao_time = strtotime($hosts_pedidos['jwt_bd_expiracao']);
						
						if(time() > $expiracao_time){
							$expirados = true;
						} else {
							$expirados = false;
						}
					} else {
						$expirados = true;
					}
					
					// ===== Verificar se é necessário emitir os vouchers.
					
					if(!$hosts_pedidos['jwt_emitidos'] || ($reemitir == 'sim' && $expirados)){
						// ===== Se for emitir pela primeira vez, não é expirado.
						
						if(!$hosts_pedidos['jwt_emitidos']){
							$expirados = false;
						}
						
						// ===== Configuração dos pedidos.
						
						$config = gestor_incluir_configuracao(Array(
							'id' => 'pedidos.config',
						));
						
						// ===== Gerar chaves de segurança para a emissão dos vouchers.
						
						gestor_incluir_biblioteca('autenticacao');
						
						$chavePrivadaSenha = autenticacao_gerar_senha();
				
						$chaves = autenticacao_openssl_gerar_chaves(Array(
							'tipo' => 'RSA', 
							'senha' => $chavePrivadaSenha, 
						));
						
						$chavePrivada = $chaves['privada'];
						$voucher_chave = $chaves['publica'];
						
						// ===== Validade do Voucher.
						
						$expiration = time() + $config['pedidosValidadeDias'] * ( 60 * 60 * 24 );
						
						// ===== Emitir vouchers.
						
						$hosts_vouchers = banco_select(Array(
							'tabela' => 'hosts_vouchers',
							'campos' => Array(
								'id_hosts_vouchers',
								'codigo',
								'status',
							),
							'extra' => 
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
						));
						
						if($hosts_vouchers)
						foreach($hosts_vouchers as $voucher){
							// ===== Se o voucher já foi baixado, não permitir reemitir o voucher.
							
							if($voucher['status'] != 'usado'){
								// ===== Marcar que reemitiu pelo menos um para atualizar o pedido.
								
								$reemitiuPeloMenosUm = true;
								
								// ===== Gerar o JWT de cada voucher.
								
								$jwt = autenticacao_gerar_jwt_chave_privada(Array(
									'host' => 'Entrey',
									'expiration' => $expiration,
									'pubID' => $voucher['codigo'],
									'chavePrivada' => $chavePrivada,
									'chavePrivadaSenha' => $chavePrivadaSenha,
								));
								
								// ===== Guardar provisoriamente o JWT no banco de dados.
								
								banco_update_campo('jwt_bd',base64_encode($codigo).'.='.$jwt);
								banco_update_campo('status','jwt-gerado');
								
								banco_update_executar('hosts_vouchers',"WHERE id_hosts_vouchers='".$voucher['id_hosts_vouchers']."'");
							}
						}
						
						// ===== Verifica se precisa atualizar o pedido.
						
						if(isset($reemitiuPeloMenosUm)){
							// ===== Atualizar pedido no banco.
							
							banco_update_campo('voucher_chave',$voucher_chave);
							banco_update_campo('jwt_emitidos','1',true);
							banco_update_campo('jwt_bd_expiracao',"ADDTIME(NOW(),'".$config['qrCodeGuardarBancoTempoLimiteHoras'].":00:00')",true,false);
							banco_update_campo('jwt_bd_expirado','NULL',true);
							banco_update_campo('data_modificacao','NOW()',true);
							banco_update_campo('versao','versao+1',true);
							
							banco_update_executar('hosts_pedidos',"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'");
							
							// ===== Gravar histórico no pedido.
							
							gestor_incluir_biblioteca('log');
							
							log_hosts_usuarios(Array(
								'id_hosts' => $id_hosts,
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'id' => $id_hosts_pedidos,
								'tabela' => Array(
									'nome' => 'hosts_pedidos',
									'versao' => 'versao',
									'id_numerico' => 'id_hosts_pedidos',
								),
								'alteracoes' => Array(
									Array(
										'modulo' => 'pedidos',
										'alteracao' => 'vouchers-issued',
										'alteracao_txt' => 'Vouchers emitidos com sucesso',
									)
								),
							));
						}
					}
					
					// ===== Valores dos vouchers a serem devolvidos.
					
					$vouchers = Array();
					
					// ===== Pegar os dados dos vouchers para atualizar os dados do host.
					
					$hosts_vouchers = banco_select(Array(
						'tabela' => 'hosts_vouchers',
						'campos' => Array(
							'id_hosts_vouchers',
							'jwt_bd',
							'status',
							'data_uso',
						),
						'extra' => 
							"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Verificar se está expirado e não foi enviado o pedido de reemissão.
					
					if($reemitir == 'nao' && $expirados){
						// ===== Formatar os dados de retorno.
						
						if($hosts_vouchers)
						foreach($hosts_vouchers as $voucher){
							$vouchers[$voucher['id_hosts_vouchers']] = Array(
								'status' => $voucher['status'],
								'data_uso' => $voucher['data_uso'],
							);
						}
						
						// ===== Retornar dados.
						
						return Array(
							'status' => 'OK',
							'data' => Array(
								'expirados' => 'sim',
								'vouchers' => $vouchers,
							),
						);
					} else {
						// ===== Gerar imagens do QRCode e formatar os dados de retorno.
						
						gestor_incluir_biblioteca('autenticacao');
						
						if($hosts_vouchers)
						foreach($hosts_vouchers as $voucher){
							if($voucher['status'] != 'usado'){
								$qrCodeImagem = autenticacao_qr_code(Array(
									'conteudo' => $voucher['jwt_bd'],
								));
								
								$vouchers[$voucher['id_hosts_vouchers']] = Array(
									'qrCodeImagem' => $qrCodeImagem,
									'status' => $voucher['status'],
									'data_uso' => $voucher['data_uso'],
								);
							} else {
								$vouchers[$voucher['id_hosts_vouchers']] = Array(
									'status' => $voucher['status'],
									'data_uso' => $voucher['data_uso'],
								);
							}
						}
						
						// ===== Retornar dados.
						
						return Array(
							'status' => 'OK',
							'data' => Array(
								'vouchers' => $vouchers,
								'reemitiuPeloMenosUm' => (isset($reemitiuPeloMenosUm) ? 'sim' : 'nao'),
							),
						);
					}
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-encontrado'));
					
					return Array(
						'status' => 'ORDER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'alterar-identificacao':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo e id_hosts_usuarios.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios'])){
				// ===== Filtrar os campos.
				
				$codigo = banco_escape_field($dados['codigo']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				$voucherID = banco_escape_field($dados['voucherID']);
				$nome = banco_escape_field($dados['nome']);
				$documento = banco_escape_field($dados['documento']);
				$telefone = banco_escape_field($dados['telefone']);
				
				// ===== Procurar o pedido solicitado.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
						'jwt_emitidos',
						'jwt_bd_expiracao',
						'status',
					),
					'extra' => 
						"WHERE codigo='".$codigo."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_pedidos){
					// ===== Conferir se o pedido foi pago.
					
					if($hosts_pedidos['status'] != 'pago'){
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-pago'));
						
						return Array(
							'status' => 'ORDER_NOT_PAYED',
							'error-msg' => $alerta,
						);
					}
					
					// ===== Id do pedido.
					
					$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
					
					// ===== Verificar expiração da emissão.
					
					if($hosts_pedidos['jwt_bd_expiracao']){
						$expiracao_time = strtotime($hosts_pedidos['jwt_bd_expiracao']);
						
						if(time() > $expiracao_time){
							$expirados = true;
						} else {
							$expirados = false;
						}
					} else {
						$expirados = true;
					}
					
					// ===== Verificar se o JWT do voucher não está expirado.
					
					if(!$expirados){
						// ===== Verificar a existência do voucher.
						
						$hosts_vouchers = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_vouchers',
							'campos' => Array(
								'id_hosts_vouchers',
							),
							'extra' => 
								"WHERE codigo='".$voucherID."'"
								." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
						));
						
						if($hosts_vouchers){
							// ===== Identificador do voucher.
							
							$id_hosts_vouchers = $hosts_vouchers['id_hosts_vouchers'];
							
							// ===== Atualizar identificação do voucher.
							
							banco_update_campo('nome',$nome);
							banco_update_campo('documento',$documento);
							banco_update_campo('telefone',$telefone);
							
							banco_update_executar('hosts_vouchers',"WHERE id_hosts_vouchers='".$id_hosts_vouchers."'");
							
							// ===== Atualizar pedido no banco.
							
							banco_update
							(
								"data_modificacao=NOW(),".
								"versao=versao+1",
								"hosts_pedidos",
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							);
							
							// ===== Incluir o histórico da alteração no pedido.
							
							gestor_incluir_biblioteca('log');
							
							log_hosts_usuarios(Array(
								'id_hosts' => $id_hosts,
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'id' => $id_hosts_pedidos,
								'tabela' => Array(
									'nome' => 'hosts_pedidos',
									'versao' => 'versao',
									'id_numerico' => 'id_hosts_pedidos',
								),
								'alteracoes' => Array(
									Array(
										'modulo' => 'pedidos',
										'alteracao' => 'update-voucher',
										'alteracao_txt' => 'Usuário atualizou a identificação do voucher: <b>#'.$voucherID.'</b>',
									)
								),
							));
							
							// ===== Campos para atualizar.
						
							$config = gestor_incluir_configuracao(Array(
								'id' => 'pedidos.config',
							));
							
							// ===== Retornar o hosts_voucher do pedido.
							
							$hosts_vouchers = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_vouchers',
								'campos' => $config['vouchersAtualizarCampos'],
								'extra' => 
									"WHERE id_hosts_vouchers='".$id_hosts_vouchers."'"
							));
							
							// ===== Retornar dados.
							
							return Array(
								'status' => 'OK',
								'data' => Array(
									'voucher' => $hosts_vouchers,
									'msg' => gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-voucher-alterar-identificacao-sucesso')),
								),
							);
						} else {
							$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-voucher-nao-encontrado'));
							
							return Array(
								'status' => 'VOUCHER_NOT_FOUND',
								'error-msg' => $alerta,
							);
						}
					} else {
						return Array(
							'status' => 'JWT_EXPIRED',
						);
					}
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-encontrado'));
					
					return Array(
						'status' => 'ORDER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'enviar-email':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: codigo e id_hosts_usuarios.
			
			if(isset($dados['codigo']) && isset($dados['id_hosts_usuarios'])){
				// ===== Filtrar os campos.
				
				$codigo = banco_escape_field($dados['codigo']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				$voucherID = banco_escape_field($dados['voucherID']);
				$email = banco_escape_field($dados['email']);
				
				// ===== Procurar o pedido solicitado.
				
				$hosts_pedidos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_pedidos',
					'campos' => Array(
						'id_hosts_pedidos',
						'jwt_emitidos',
						'jwt_bd_expiracao',
						'status',
					),
					'extra' => 
						"WHERE codigo='".$codigo."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_pedidos){
					// ===== Conferir se o pedido foi pago.
					
					if($hosts_pedidos['status'] != 'pago'){
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-pago'));
						
						return Array(
							'status' => 'ORDER_NOT_PAYED',
							'error-msg' => $alerta,
						);
					}
					
					// ===== Id do pedido.
					
					$id_hosts_pedidos = $hosts_pedidos['id_hosts_pedidos'];
					
					// ===== Verificar expiração da emissão.
					
					if($hosts_pedidos['jwt_bd_expiracao']){
						$expiracao_time = strtotime($hosts_pedidos['jwt_bd_expiracao']);
						
						if(time() > $expiracao_time){
							$expirados = true;
						} else {
							$expirados = false;
						}
					} else {
						$expirados = true;
					}
					
					// ===== Verificar se o JWT do voucher não está expirado.
					
					if(!$expirados){
						// ===== Verificar a existência do voucher.
						
						$hosts_vouchers = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_vouchers',
							'campos' => Array(
								'id_hosts_vouchers',
								'id_hosts_servicos',
								'id_hosts_servicos_variacoes',
								'nome',
								'documento',
								'telefone',
								'jwt_bd',
								'loteVariacao',
							),
							'extra' => 
								"WHERE codigo='".$voucherID."'"
								." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
						));
						
						if($hosts_vouchers){
							// ===== Variáveis do voucher.
							
							$id_hosts_vouchers = $hosts_vouchers['id_hosts_vouchers'];
							$id_hosts_servicos = $hosts_vouchers['id_hosts_servicos'];
							$id_hosts_servicos_variacoes = $hosts_vouchers['id_hosts_servicos_variacoes'];
							$nome = $hosts_vouchers['nome'];
							$documento = $hosts_vouchers['documento'];
							$telefone = $hosts_vouchers['telefone'];
							$jwt_bd = $hosts_vouchers['jwt_bd'];
							$loteVariacao = $hosts_vouchers['loteVariacao'];
							
							if($loteVariacao){
								$hosts_pedidos_servico_variacoes = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_pedidos_servico_variacoes',
									'campos' => Array(
										'id_hosts_arquivos_Imagem',
										'nome_servico',
										'nome_lote',
										'nome_variacao',
									),
									'extra' => 
										"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
										." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
								));
								
								$nome_servico = $hosts_pedidos_servico_variacoes['nome_servico'];
								$id_hosts_arquivos_Imagem = $hosts_pedidos_servico_variacoes['id_hosts_arquivos_Imagem'];
								$voucherSubtitulo = $hosts_pedidos_servico_variacoes['nome_lote'].' - '.$hosts_pedidos_servico_variacoes['nome_variacao'];
							} else {
								$hosts_pedidos_servicos = banco_select(Array(
									'unico' => true,
									'tabela' => 'hosts_pedidos_servicos',
									'campos' => Array(
										'id_hosts_arquivos_Imagem',
										'nome',
									),
									'extra' => 
										"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
										." AND id_hosts_pedidos='".$id_hosts_pedidos."'"
								));
								
								$nome_servico = $hosts_pedidos_servicos['nome'];
								$id_hosts_arquivos_Imagem = $hosts_pedidos_servicos['id_hosts_arquivos_Imagem'];
							}
							
							$voucherTitulo = 'Voucher #'.$voucherID.': '.$nome_servico;
							
							// ===== Buscar a imagem mini.
							
							$caminho_mini = '';
							
							$id_hosts_arquivos = $id_hosts_arquivos_Imagem;
							
							if(isset($id_hosts_arquivos)){
								$hosts_arquivos = banco_select_name(
									banco_campos_virgulas(Array(
										'caminho_mini',
									)),
									"hosts_arquivos",
									"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
								);
								
								if($hosts_arquivos){
									if(existe($hosts_arquivos[0]['caminho_mini'])){
										$caminho_mini = $hosts_arquivos[0]['caminho_mini'];
									}
								}
							}
							
							// ===== Imagem Mini ou Imagem Referência do serviço.
							
							if(existe($caminho_mini)){
								$imgSrc = $caminho_mini;
							} else {
								$imgSrc = 'images/imagem-padrao.png';
							}
							
							gestor_incluir_biblioteca('host');
							
							$servicoImg = host_url(Array('opcao' => 'full','id_hosts' => $id_hosts)) . $imgSrc;
							
							// ===== Gerar o qwCodeImg.
							
							gestor_incluir_biblioteca('autenticacao');
							
							$qrCodeImg = autenticacao_qr_code(Array(
								'conteudo' => $jwt_bd,
								'tmpImg' => true,
							));
							
							// ===== Criar o PDF do voucher.
							
							gestor_incluir_biblioteca('pdf');
							
							$pdf = pdf_voucher(Array(
								'caminho' => $caminho,
								'servicoImg' => $servicoImg,
								'qrCodeImg' => $qrCodeImg,
								'voucherTitulo' => $voucherTitulo,
								'voucherSubtitulo' => (isset($voucherSubtitulo) ? $voucherSubtitulo : null),
								'nome' => $nome,
								'documento' => $documento,
								'telefone' => $telefone,
								'loteVariacao' => $loteVariacao,
							));
							
							// ===== Enviar email com o voucher.
							
							gestor_incluir_biblioteca('comunicacao');
							
							if(comunicacao_email(Array(
								'destinatarios' => Array(
									Array(
										'email' => $email,
									),
								),
								'mensagem' => Array(
									'assunto' => $voucherTitulo . (isset($voucherSubtitulo) ? ' - '.$voucherSubtitulo : ''),
									'htmlLayoutID' => 'layout-email-voucher',
									'htmlVariaveis' => Array(
										Array(
											'variavel' => '#voucherID#',
											'valor' => '#'.$voucherID,
										),
										Array(
											'variavel' => '#assinatura#',
											'valor' => modelo_var_troca_tudo(gestor_componente(Array('id' => 'hosts-layout-emails-assinatura')),'@[[url]]@',host_url(Array('opcao'=>'full')).'identificacao/')
										),
									),
									'anexos' => Array(
										Array(
											'nome' => $voucherTitulo . (isset($voucherSubtitulo) ? ' - '.$voucherSubtitulo : '').'.pdf',
											'caminho' => $pdf,
											'tmpCaminho' => $pdf,
										),
									)
								),
							))){
								$emailSucesso = true;
							} else {
								$emailSucesso = false;
							}
							
							// ===== Atualizar pedido no banco.
							
							banco_update
							(
								"data_modificacao=NOW(),".
								"versao=versao+1",
								"hosts_pedidos",
								"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
							);
							
							// ===== Incluir o histórico da alteração no pedido.
							
							gestor_incluir_biblioteca('log');
							
							log_hosts_usuarios(Array(
								'id_hosts' => $id_hosts,
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'id' => $id_hosts_pedidos,
								'tabela' => Array(
									'nome' => 'hosts_pedidos',
									'versao' => 'versao',
									'id_numerico' => 'id_hosts_pedidos',
								),
								'alteracoes' => Array(
									Array(
										'modulo' => 'pedidos',
										'alteracao' => 'update-voucher',
										'alteracao_txt' => ($emailSucesso ? 'Usuário enviou o voucher <b>#'.$voucherID.'</b> para o email <b>'.$email.'</b>' : 'Usuário tentou enviar o voucher <b>#'.$voucherID.'</b> para o email <b>'.$email.'</b> mas NÃO foi enviado por algum erro.'),
									)
								),
							));
							
							// ===== Retornar dados.
							
							return Array(
								'status' => 'OK',
								'data' => Array(
									'msg' => gestor_variaveis(Array('modulo' => 'loja', 'id' => ($emailSucesso ? 'alerta-voucher-email-sucesso' : 'alerta-voucher-email-nao-enviado'))),
								),
							);
						} else {
							$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-voucher-nao-encontrado'));
							
							return Array(
								'status' => 'VOUCHER_NOT_FOUND',
								'error-msg' => $alerta,
							);
						}
					} else {
						return Array(
							'status' => 'JWT_EXPIRED',
						);
					}
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-pedido-nao-encontrado'));
					
					return Array(
						'status' => 'ORDER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		default:
			return  Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
}

function plataforma_cliente_usuario(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'editar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: usuarioID e campo.
			
			if(isset($dados['usuarioID']) && isset($dados['campo'])){
				// ===== Filtrar os campos.
				
				$campo = banco_escape_field($dados['campo']);
				$id_hosts_usuarios = banco_escape_field($dados['usuarioID']);
				
				// ===== Procurar o usuário solicitado.
				
				$hosts_usuarios = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_usuarios',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_usuarios){
					// ===== Conferir se o usuário está ativo no sistema.
					
					if($hosts_usuarios['status'] != 'A'){
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-usuario-inativo'));
						
						return Array(
							'status' => 'INACTIVE_USER',
							'error-msg' => $alerta,
						);
					}
					
					// ===== Variáveis valores iniciais.
					
					$alteracaoCampos = Array();
					$alteracaoTxt = '';
					
					// ===== Alterar o campo conforme solicitado.
					
					switch($campo){
						case 'nome':
							// ===== Separar os nomes (primeiro, do meio e último)
							
							$nome = $dados['nome'];
							$nome = ucwords(strtolower(trim($nome)));
							
							$nomes = explode(' ',$nome);
							
							if(count($nomes) > 2){
								for($i=0;$i<count($nomes);$i++){
									if($i==0){
										$primeiro_nome = $nomes[$i];
									} else if($i==count($nomes) - 1){
										$ultimo_nome = $nomes[$i];
									} else {
										$nome_do_meio .= (isset($nome_do_meio) ? ' ':'') . $nomes[$i];
									}
								}
							} else if(count($nomes) > 1){
								$primeiro_nome = $nomes[0];
								$ultimo_nome = $nomes[1];
							} else {
								$primeiro_nome = $nomes[0];
							}
							
							// ===== Atualizar o nome no banco de dados.
							
							banco_update_campo('nome',$dados['nome']);
							
							if(isset($primeiro_nome)){ banco_update_campo('primeiro_nome',$primeiro_nome); }
							if(isset($nome_do_meio)){ banco_update_campo('nome_do_meio',$nome_do_meio); }
							if(isset($ultimo_nome)){ banco_update_campo('ultimo_nome',$ultimo_nome); }
							
							banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'");
						
							// ===== Campos que devem ser retornados.
							
							$alteracaoCampos[] = 'nome';
							$alteracaoCampos[] = 'primeiro_nome';
							$alteracaoCampos[] = 'nome_do_meio';
							$alteracaoCampos[] = 'ultimo_nome';
							
							// ===== Alteração no histórico.
							
							$alteracaoTxt = '<b>Nome</b> atualizado com sucesso! Valor anterior: <b>'.$hosts_usuarios['nome'].'</b>';
						
							// ===== Campo encontrado.
						
							$campoEncontrado = true;
						break;
						case 'email':
							// ===== Verificar se já existe o email em alguma outra conta.
							
							$email = banco_escape_field($dados['email']);
							
							$hosts_usuarios_outro = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_hosts_usuarios',
								))
								,
								"hosts_usuarios",
								"WHERE email='".$email."'"
								." AND id_hosts_usuarios!='".$id_hosts_usuarios."'"
								." AND status!='D'"
								." AND id_hosts='".$id_hosts."'"
							);
							
							// ===== Se existir, devolve erro.
							
							if($hosts_usuarios_outro){
								$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alert-there-is-a-field'));
								$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'loja','id' => 'identificacao-email-label')));
								$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($dados['email']));
								
								return Array(
									'status' => 'THIS_EMAIL_ALREADY_EXISTS',
									'error-msg' => $alerta,
								);
							}
							
							// ===== Atualizar o email no banco de dados.
							
							banco_update_campo('email',$dados['email']);
							
							banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'");
						
							// ===== Campos que devem ser retornados.
							
							$alteracaoCampos[] = 'email';
							
							// ===== Alteração no histórico.
							
							$alteracaoTxt = '<b>Email</b> atualizado com sucesso! Valor anterior: <b>'.$hosts_usuarios['email'].'</b>';
							
							// ===== Campo encontrado.
						
							$campoEncontrado = true;
						break;
						case 'usuario':
							// ===== Verificar se já existe o usuário em alguma outra conta.
						
							$usuario = banco_escape_field($dados['usuario']);
							
							$hosts_usuarios_outro = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_hosts_usuarios',
								))
								,
								"hosts_usuarios",
								"WHERE usuario='".$usuario."'"
								." AND id_hosts_usuarios!='".$id_hosts_usuarios."'"
								." AND status!='D'"
								." AND id_hosts='".$id_hosts."'"
							);
							
							// ===== Se existir, devolve erro.
							
							if($hosts_usuarios_outro){
								$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alert-there-is-a-field'));
								$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'loja','id' => 'identificacao-usuario-label')));
								$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($dados['usuario']));
								
								return Array(
									'status' => 'THIS_USER_ALREADY_EXISTS',
									'error-msg' => $alerta,
								);
							}
							
							// ===== Atualizar o usuario no banco de dados.
							
							banco_update_campo('usuario',$dados['usuario']);
							
							banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'");
						
							// ===== Campos que devem ser retornados.
							
							$alteracaoCampos[] = 'usuario';
							
							// ===== Alteração no histórico.
							
							$alteracaoTxt = '<b>Usuário</b> atualizado com sucesso! Valor anterior: <b>'.$hosts_usuarios['usuario'].'</b>';
							
							// ===== Campo encontrado.
						
							$renovarToken = true;
							$campoEncontrado = true;
						break;
						case 'senha':
							// ===== Gerar hash da senha
							
							$senha = $dados['senha'];
							
							$senhaHash = password_hash($senha, PASSWORD_ARGON2I, ["cost" => 9]);
							
							// ===== Atualizar a senha no banco de dados.
							
							banco_update_campo('senha',$senhaHash);
							
							banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'");
						
							// ===== Alteração no histórico.
							
							$alteracaoTxt = '<b>Senha</b> atualizada com sucesso!';
						
							// ===== Campo encontrado.
						
							$renovarToken = true;
							$campoEncontrado = true;
						break;
						case 'telefone':
							// ===== Atualizar o telefone no banco de dados.
							
							banco_update_campo('telefone',$dados['telefone']);
							
							banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'");
						
							// ===== Campos que devem ser retornados.
							
							$alteracaoCampos[] = 'telefone';
							
							// ===== Alteração no histórico.
							
							$alteracaoTxt = '<b>Telefone</b> atualizado com sucesso! Valor anterior: <b>'.$hosts_usuarios['telefone'].'</b>';
							
							// ===== Campo encontrado.
						
							$campoEncontrado = true;
						break;
						case 'documento':
							// ===== Verificar se é CPF ou CNPJ.
						
							if($dados['cnpj_ativo'] == 'sim'){
								// ===== Atualizar o cnpj no banco de dados.
								
								banco_update_campo('cnpj',$dados['cnpj']);
								banco_update_campo('cnpj_ativo','1',true);
								
								banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'");
							
								// ===== Campos que devem ser retornados.
								
								$alteracaoCampos[] = 'cnpj';
								$alteracaoCampos[] = 'cnpj_ativo';
								
								// ===== Alteração no histórico.
								
								$alteracaoTxt = '<b>CNPJ</b> atualizado com sucesso! Valor anterior: <b>'.($hosts_usuarios['cnpj'] ? $hosts_usuarios['cnpj'] : 'Nenhum').'</b>';
							} else {
								// ===== Atualizar o cpf no banco de dados.
								
								banco_update_campo('cpf',$dados['cpf']);
								banco_update_campo('cnpj_ativo','NULL',true);
								
								banco_update_executar('hosts_usuarios',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'");
							
								// ===== Campos que devem ser retornados.
								
								$alteracaoCampos[] = 'cpf';
								$alteracaoCampos[] = 'cnpj_ativo';
								
								// ===== Alteração no histórico.
								
								$alteracaoTxt = '<b>CPF</b> atualizado com sucesso! Valor anterior: <b>'.($hosts_usuarios['cpf'] ? $hosts_usuarios['cpf'] : 'Nenhum').'</b>';
							}
							
							// ===== Campo encontrado.
						
							$campoEncontrado = true;
						break;
					}
					
					// ===== Se campo não encontrado, devolver erro.
					
					if(!isset($campoEncontrado)){
						$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-usuario-campo-nao-encontrado'));
						
						return Array(
							'status' => 'FIELD_NOT_FOUND',
							'error-msg' => $alerta,
						);
					}
					
					// ===== Renovar o usuários tokens.
					
					if(isset($renovarToken)){
						// ===== Deletar o usuário token.
						
						banco_delete
						(
							"hosts_usuarios_tokens",
							"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
							." AND id_hosts='".$id_hosts."'"
						);
					}
					
					// ===== Atualizar hosts_usuarios no banco.
					
					banco_update
					(
						"data_modificacao=NOW(),".
						"versao=versao+1",
						"hosts_usuarios",
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					);
					
					// ===== Incluir o histórico da alteração no hosts_usuarios.
					
					gestor_incluir_biblioteca('log');
					
					log_hosts_usuarios(Array(
						'id_hosts' => $id_hosts,
						'id_hosts_usuarios' => $id_hosts_usuarios,
						'id' => $id_hosts_usuarios,
						'tabela' => Array(
							'nome' => 'hosts_usuarios',
							'versao' => 'versao',
							'id_numerico' => 'id_hosts_usuarios',
						),
						'alteracoes' => Array(
							Array(
								'modulo' => 'hosts-usuarios',
								'alteracao' => 'update-data',
								'alteracao_txt' => $alteracaoTxt,
							)
						),
					));
					
					// ===== Incluir os campos padrões também.
					
					$alteracaoCampos[] = 'versao';
					$alteracaoCampos[] = 'data_modificacao';
					
					// ===== Retornar o hosts_usuarios atualizado.
					
					$hosts_usuarios = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios',
						'campos' => $alteracaoCampos,
						'extra' => 
							"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					));
					
					// ===== Retornar dados.
					
					return Array(
						'status' => 'OK',
						'data' => Array(
							'usuario' => $hosts_usuarios,
							'alteracaoTxt' => $alteracaoTxt,
							'alerta' => gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-usuario-alterar-dados-sucesso')),
						),
					);
				} else {
					$alerta = gestor_variaveis(Array('modulo' => 'loja','id' => 'alerta-usuario-nao-encontrado'));
					
					return Array(
						'status' => 'USER_NOT_FOUND',
						'error-msg' => $alerta,
					);
				}
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		default:
			return  Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
}

// =========================== Funções de Acesso

function plataforma_cliente_autenticar_servidor(){
	global $_GESTOR;
	
	if(isset($_REQUEST['token']) && isset($_REQUEST['token_validacao_id']) && isset($_REQUEST['id'])){
		$token = $_REQUEST['token'];
		$token_validacao_id = banco_escape_field($_REQUEST['token_validacao_id']);
		$pub_id = banco_escape_field($_REQUEST['id']);
		
		// ===== Verifica se existe o token.
		
		$JWTToken = $token;
		
		if(!existe($JWTToken)){
			return false;
		}
		
		// ===== Pegar a chave pública do host
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'chave_publica',
			))
			,
			"hosts",
			"WHERE pub_id='".$pub_id."'"
		);
		
		if($hosts){
			$chavePublica = $hosts[0]['chave_publica'];
			
			// ===== Verificar se o JWT é válido.
			
			$tokenPubId = plataforma_cliente_validar_jwt(Array(
				'token' => $JWTToken,
				'chavePublica' => $chavePublica,
			));
			
			if($tokenPubId){
				// ===== Verifica se o token está ativo. Senão estiver invalidar o token.
				
				$plataforma_tokens = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_hosts',
						'pubIDValidation',
					))
					,
					"plataforma_tokens",
					"WHERE pubID='".$token_validacao_id."'"
				);
				
				if($plataforma_tokens){
					// ===== Limpeza dos tokens mais antigos no banco de dados.
					
					$invalidar_token = false;
					
					// ===== Deletar todos os tokens que atingiram o tempo de expiração.
					
					banco_delete
					(
						"plataforma_tokens",
						"WHERE expiration < ".time()
					);
					
					// ===== Verificar se um dos tokens excluídos é o token atual. Se sim, invalidar token.
					
					$plataforma_tokens_verificar = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_plataforma_tokens',
						))
						,
						"plataforma_tokens",
						"WHERE pubID='".$token_validacao_id."'"
					);
					
					if(!$plataforma_tokens_verificar){
						$invalidar_token = true;
					}
					
					if(!$invalidar_token){
						// ===== Validar o token com o hash de validação para evitar geração de token por hacker caso ocorra roubo da tabela 'plataforma_tokens'.
						
						
						$bd_hash = $plataforma_tokens[0]['pubIDValidation'];
						$token_hash = hash_hmac($_GESTOR['platform-hash-algo'], $token_validacao_id, $_GESTOR['platform-hash-password']);
						
						if($bd_hash === $token_hash){
							$id_hosts = $plataforma_tokens[0]['id_hosts'];
							
							$_GESTOR['plataforma-hosts-id'] = $id_hosts;
							$_GESTOR['plataforma-token-id'] = $token_validacao_id;
							
							return true;
						}
					}
				}
			}
		}
	}
	
	return false;
}

function plataforma_cliente_autenticacao(){
	global $_GESTOR;
	
	if(isset($_REQUEST['token']) && isset($_REQUEST['hostId'])){
		$hostId = banco_escape_field($_REQUEST['hostId']);
		
		$token_validacao_id = plataforma_cliente_validar_token_autorizacao(Array(
			'token' => $_REQUEST['token'],
			'hostId' => $hostId,
		));
		
		if($token_validacao_id){
			// ===== Buscar no banco de dados os dados do host necessários.
		
			$hosts = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_hosts',
					'dominio',
				))
				,
				"hosts",
				"WHERE pub_id='".$hostId."'"
			);
			
			if($hosts){
				
				$dominio = $hosts[0]['dominio'];
				$id_hosts = $hosts[0]['id_hosts'];
				
				$retorno['error-msg'] = '';
				$retorno['error'] = false;
				$retorno['completed'] = false;
				
				$token = plataforma_cliente_gerar_token_autorizacao(Array(
					'hostId' => $hostId,
				));
				
				$_GESTOR['host-id'] = $id_hosts;
				
				// ===== Deletar todos os tokens que atingiram o tempo de expiração.
				
				banco_delete
				(
					"plataforma_tokens",
					"WHERE expiration < ".time()
				);
				
				// ===== Verificar pubID do Token remoto.
				
				$plataforma_tokens = banco_select(Array(
					'unico' => true,
					'tabela' => 'plataforma_tokens',
					'campos' => Array(
						'pubID',
					),
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND remoto IS NOT NULL"
				));
				
				if($plataforma_tokens){
					$tokenPubId = $plataforma_tokens['pubID'];
					
					if($tokenPubId == $token_validacao_id){
						return true;
					}
				}
				
				// ===== Conectar na plataforma do servidor na interface 'autenticar'.
				
				$url = $dominio . '/_plataforma/autenticar/';
				
				$data = false;
				
				$data['token'] = $token;
				$data['token_validacao_id'] = $token_validacao_id;
				
				$data = http_build_query($data);
				$curl = curl_init($url);

				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				$json = curl_exec($curl);
				
				curl_close($curl);
				
				$plataformaRetorno = json_decode($json,true);
				
				// ===== Tratar os erros de retorno da plataforma caso haja ou então devolver o retorno para a requisição com o status ok e/ou os dados.
				
				if(!$plataformaRetorno){
					$retorno['error-msg'] = '[no-json] '.$json; $retorno['error'] = true;
				} else if($plataformaRetorno['error']){
					$retorno['error-msg'] = '[error] '.$plataformaRetorno['error'].' '.$plataformaRetorno['error_msg']; $retorno['error'] = true;
				} else if($plataformaRetorno['status'] != 'OK'){
					$retorno['error-msg'] = '[not-OK] '.$plataformaRetorno['status']; $retorno['error'] = true;
				} else {
					if($plataformaRetorno['data']) $retorno['data'] = $plataformaRetorno['data'];
					$retorno['completed'] = true;
					
					// ===== Armazenar token remoto.
					
					$tokenPubId = $token_validacao_id;
				
					$pubIDValidation = hash_hmac($_GESTOR['platform-hash-algo'], $tokenPubId, $_GESTOR['platform-hash-password']);
					$expiration = time() + $_GESTOR['platform-lifetime'];
					
					// ====== Salvar token no banco
					
					$campos = null; $campo_sem_aspas_simples = null;
					
					$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "pubID"; $campo_valor = $tokenPubId; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "expiration"; $campo_valor = $expiration; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 					$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "remoto"; $campo_valor = '1'; 							$campos[] = Array($campo_nome,$campo_valor,true);
					
					banco_insert_name
					(
						$campos,
						"plataforma_tokens"
					);
				}
				
				return $retorno['completed'];
			}
		}
	}
	
	return false;
}

function plataforma_cliente_404(){
	http_response_code(404);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '404',
		'info' => 'JSON not found',
	));
	exit;
}

function plataforma_cliente_401(){
	http_response_code(401);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '401',
		'info' => 'JSON unauthorized',
	));
	exit;
}

function plataforma_cliente_200(){
	http_response_code(200);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'OK',
		'statusCode' => '200',
	));
	exit;
}

function plataforma_cliente_start(){
	global $_GESTOR;
	global $_INDEX;
	
	// ===== Caso seja uma operação de autenticar o cliente no servidor
	
	switch($_GESTOR['caminho'][1]){
		case 'autenticar':
			if(!plataforma_cliente_autenticar_servidor()){
				plataforma_cliente_401();
			} else {
				plataforma_cliente_200();
			}
		break;
	}
	
	// ===== Verifica se o cliente tem autorização para acessar a plataforma. Senão retornar JSON 401.
	
	if(!plataforma_cliente_autenticacao()){
		plataforma_cliente_401();
	}
	
	// ===== Verifica a opção, executa interface caso encontrado e retorna os dados
	
	switch($_GESTOR['caminho'][1]){
		case 'carrinho': $dados = plataforma_cliente_carrinho(); break;
		case 'identificacao': $dados = plataforma_cliente_identificacao(); break;
		case 'emissao': $dados = plataforma_cliente_emissao(); break;
		case 'pagamento': $dados = plataforma_cliente_pagamento(); break;
		case 'voucher': $dados = plataforma_cliente_voucher(); break;
		case 'usuario': $dados = plataforma_cliente_usuario(); break;
	}
	
	// ===== Caso haja dados criados por alguma opção, retornar JSON e finalizar. Senão retornar JSON 404.
	
	if(isset($dados)){
		header("Content-Type: application/json; charset: UTF-8");
		echo json_encode($dados);
		exit;
	}
	
	plataforma_cliente_404();
}

// =========================== Inciar Plataforma

plataforma_cliente_start();

?>