<?php

// ===== API responsável por disparar solicitações ao 'servidor'.

global $_GESTOR;

$_GESTOR['biblioteca-api-servidor']							=	Array(
	'versao' => '1.0.2',
);

// ===== Funções de chamadas do cliente.

function api_servidor_carrinho($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id_hosts_servicos - Int - Obrigatório - Identificador numérico do serviço.
	// quantidade - Int - Obrigatório - Quantidade do serviço.
	// variacao_id - Int - Opcional - Identificador numérico da variação do serviço.
	
	// ===== 
	
	if(isset($opcao)){
		$dados = Array();
		
		switch($opcao){
			case 'adicionar':
			case 'diminuir':
				$dados = Array(
					'sessao_id' => $_GESTOR['session-id'],
					'id_hosts_servicos' => $id_hosts_servicos,
					'quantidade' => $quantidade,
				);
				
				if(isset($variacao_id)) $dados['variacao_id'] = $variacao_id;
			break;
			case 'excluir':
				$dados = Array(
					'sessao_id' => $_GESTOR['session-id'],
					'id_hosts_servicos' => $id_hosts_servicos,
				);
				
				if(isset($variacao_id)) $dados['variacao_id'] = $variacao_id;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'carrinho',
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_servidor_identificacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// Se opcao == 'logar'
	
	// usuario_token - Array - Obrigatório - Dados do token de usuário.
	// email - String - Obrigatório - Email do usuário do host.
	// senha - String - Obrigatório - Senha do usuário do host.
	// token - String - Opcional - Token do reCAPATCHA caso ativo do host.
	// usuarioTokenID - String - Opcional - Token do reCAPATCHA caso ativo do host.
	
	// Se opcao == 'criarConta'
	
	// email - String - Obrigatório - Email do usuário do host.
	// token - String - Opcional - Token do reCAPATCHA caso ativo do host.
	
	// Se opcao == 'cadastrar'
	
	// email - String - Obrigatório - Email do usuário do host.
	// token - String - Opcional - Token do reCAPATCHA caso ativo do host.
	
	// ===== 
	
	if(isset($opcao)){
		$dados = Array();
		
		switch($opcao){
			case 'logar':
				// ===== Campos obrigatórios.
				
				if(!isset($email) && !isset($senha)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: usuario_token, email e senha'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'usuario_token' => (isset($usuario_token) ? $usuario_token : Array()),
					'email' => $email,
					'senha' => (isset($senha) ? $senha : ''),
				);
				
				if(isset($token)){ $dados['token'] = $token;}
				if(isset($usuarioTokenID)){ $dados['usuarioTokenID'] = $usuarioTokenID;}
			break;
			case 'sair':
				// ===== Campos obrigatórios.
				
				if(!isset($usuarioID) && !isset($usuarioTokenID)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: usuarioID e usuarioTokenID'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'usuarioID' => $usuarioID,
					'usuarioTokenID' => $usuarioTokenID,
				);
			break;
			case 'criarConta':
				// ===== Campos obrigatórios.
				
				if(!isset($email)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: email'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'email' => $email,
				);
				
				if(isset($token)){ $dados['token'] = $token;}
			break;
			case 'cadastrar':
				// ===== Campos obrigatórios.
				
				if(!isset($nome) && !isset($email) && !isset($senha)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: nome, email e senha'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'usuario_token' => (isset($usuario_token) ? $usuario_token : Array()),
					'email' => $email,
					'senha' => (isset($senha) ? $senha : ''),
					'telefone' => (isset($telefone) ? $telefone : ''),
					'nome' => (isset($nome) ? $nome : ''),
					'cnpj_ativo' => (isset($cnpj_ativo) ? $cnpj_ativo : ''),
				);
				
				if(isset($cpf)){ $dados['cpf'] = $cpf;}
				if(isset($cnpj)){ $dados['cnpj'] = $cnpj;}
				if(isset($token)){ $dados['token'] = $token;}
			break;
			case 'esqueceuSenha':
				// ===== Campos obrigatórios.
				
				if(!isset($email) && !isset($tokenPubId)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: email e tokenPubId'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'email' => $email,
					'tokenPubId' => $tokenPubId,
				);
				
				if(isset($token)){ $dados['token'] = $token;}
			break;
			case 'redefinirSenha':
				// ===== Campos obrigatórios.
				
				if(!isset($senha) && !isset($tokenPubId)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: senha e tokenPubId'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'senha' => $senha,
					'tokenPubId' => $tokenPubId,
					'tokenID' => $tokenID,
					'userIP' => $userIP,
					'userUserAgent' => $userUserAgent,
				);
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'identificacao',
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_servidor_emissao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// ===== 
	
	if(isset($opcao)){
		$dados = Array();
		
		switch($opcao){
			case 'criarPedido':
				// ===== Campos obrigatórios.
				
				if(!isset($sessao_id) && !isset($id_hosts_carrinho) && !isset($id_hosts_usuarios)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: sessao_id, id_hosts_carrinho e id_hosts_usuarios'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'sessao_id' => $sessao_id,
					'id_hosts_carrinho' => $id_hosts_carrinho,
					'id_hosts_usuarios' => $id_hosts_usuarios,
				);
			break;
			case 'salvarIdentidades':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'vouchersAlterados' => $vouchersAlterados,
				);
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'emissao',
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_servidor_pagamento($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// ===== 
	
	if(isset($opcao)){
		$dados = Array();
		
		switch($opcao){
			case 'paypalplus-criar':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'outroPagador' => $outroPagador,
					'botao' => $botao,
				);
			break;
			case 'paypalplus-pagar':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'pay_id' => $pay_id,
					'payerID' => $payerID,
					'rememberedCard' => $rememberedCard,
					'installmentsValue' => $installmentsValue,
					'paypalButton' => $paypalButton,
					'outroPagador' => $outroPagador,
				);
			break;
			case 'log':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'msg' => $msg,
					'erro' => $erro,
				);
			break;
			case 'pedido-gratuito-processar':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
				);
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'pagamento',
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_servidor_voucher($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// ===== 
	
	if(isset($opcao)){
		$dados = Array();
		
		switch($opcao){
			case 'todos':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'reemitir' => $reemitir,
				);
			break;
			case 'alterar-identificacao':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'voucherID' => $voucherID,
					'nome' => $nome,
					'documento' => $documento,
					'telefone' => $telefone,
				);
			break;
			case 'enviar-email':
				// ===== Dados para enviar.
			
				$dados = Array(
					'codigo' => $codigo,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'voucherID' => $voucherID,
					'email' => $email,
				);
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'voucher',
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_servidor_usuario($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// Se opcao == 'editar'
	
	// usuarioID - Int - Obrigatório - Identificador do usuário.
	// campo - String - Obrigatório - Campo que quer mudar.
	
	// ===== 
	
	if(isset($opcao)){
		$dados = Array();
		
		switch($opcao){
			case 'editar':
				// ===== Campos obrigatórios.
				
				if(!isset($usuarioID) && !isset($campo)){
					return api_servidor_retornar_erro(Array('msg' => 'MANDATORY_FIELDS_NOT_INFORMED: usuarioID e campo'));
				}
				
				// ===== Dados para enviar.
			
				$dados = Array(
					'usuarioID' => $usuarioID,
					'campo' => $campo,
				);
				
				if(isset($usuario)){ $dados['usuario'] = $usuario;}
				if(isset($email)){ $dados['email'] = $email;}
				if(isset($nome)){ $dados['nome'] = $nome;}
				if(isset($senha)){ $dados['senha'] = $senha;}
				if(isset($telefone)){ $dados['telefone'] = $telefone;}
				if(isset($cnpj_ativo)){ $dados['cnpj_ativo'] = $cnpj_ativo;}
				if(isset($cpf)){ $dados['cpf'] = $cpf;}
				if(isset($cnpj)){ $dados['cnpj'] = $cnpj;}
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'usuario',
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

// ===== Funções auxiliares.

function api_servidor_retorno_verificacao($params = false){
	/**********
		Descrição: tratamento de retornos
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// retorno - Array - Obrigatório - Array de retorno da interface.
	// redirecionar - String - Opcional - Local de redirecionamento, senão reler página.
	
	// ===== 
	
	if(!isset($retorno)){
		$erro = true;
		
		$retorno = Array(
			'status' => 'ERROR_api_servidor_retorno',
			'error-msg' => 'ERROR: api_servidor_retorno',
		);
	} else {
		if(!$retorno['completed']){
			$erro = true;
		}
	}
	
	if(isset($erro)){
		$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
		
		$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
		
		if($ajax){
			return Array(
				'status' => 'API_ERROR',
				'msg' => $alerta,
			);
		} else {
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
		}
		
		// ===== Reler a página ou redirecionar.
		
		if(isset($redirecionar)){
			gestor_redirecionar($redirecionar);
		} else {
			gestor_reload_url();
		}
		
		return false;
	} else {
		return true;
	}
}

function api_servidor_retorno_dados($params = false){
	/**********
		Descrição: dados do retorno caso houver
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// retorno - Array - Obrigatório - Array de retorno da interface.
	
	// ===== 
	
	if(isset($retorno)){
		// ===== Dados de retorno.
		
		$dados = Array();
		if(isset($retorno['data'])){
			$dados = $retorno['data'];
		}
		
		return $dados;
	} else {
		return Array();
	}
}

function api_servidor_retornar_erro($params = false){
	/**********
		Descrição: Retornar erro com mensagem caso exista.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// msg - String - Obrigatório - Mensagem de erro.
	
	// ===== 
	
	if(isset($msg)){
		$retorno['error-msg'] = $msg;
		$retorno['error'] = true;
		$retorno['completed'] = false;
		
		return $retorno;
	}
}

function api_servidor_gerar_jwt($params = false){
	$cryptMaxCharsValue = 245; // There are char limitations on openssl_private_encrypt() and in the url below are explained how define this value based on openssl key format: https://www.php.net/manual/en/function.openssl-private-encrypt.php#119810
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// host - String - Obrigatório - Host de acesso do JWT.
	// expiration - Int - Obrigatório - Expiração do JWT.
	// pubID - String - Obrigatório - ID público do token para referência.
	// chavePrivada - String - Obrigatório - Chave privada para gerar a assinatura do token.
	// chavePrivadaSenha - String - Obrigatório - Senha da chave privada.
	
	// ===== 
	
	if(isset($host) && isset($expiration) && isset($pubID) && isset($chavePrivada) && isset($chavePrivadaSenha)){
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
		
		$resPrivateKey = openssl_get_privatekey($chavePrivada,$chavePrivadaSenha);
		
		$partialData = '';
		$encodedData = '';
		$split = str_split($rawDataSource , $cryptMaxCharsValue);
		foreach($split as $part){
			openssl_private_encrypt($part, $partialData, $resPrivateKey);
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

function api_servidor_validar_jwt($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - Token JWT de verificação.
	// chavePrivada - String - Obrigatório - Chave privada para conferir a assinatura do token.
	// chavePrivadaSenha - String - Obrigatório - Senha da chave privada.
	
	// ===== 
	
	if(isset($token) && isset($chavePrivada) && isset($chavePrivadaSenha)){
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
		
		$resPrivateKey = openssl_get_privatekey($chavePrivada,$chavePrivadaSenha);
		
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
			
			openssl_private_decrypt($part2, $partialDecodedData, $resPrivateKey);
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

// ===== Funções principais.

function api_servidor_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// serverHost - String - Obrigatório - Host do servidor.
	
	// ===== 
	
	if(isset($serverHost)){
		// ===== Definir variáveis para gerar o JWT
		
		$expiration = time() + $_GESTOR['platform-lifetime'];
		
		// ===== Abrir chave privada e a senha da chave
		
		$chavePrivada = $_GESTOR['plataforma-cliente']['chave-seguranca']['chave'];
		$chavePrivadaSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['senha'];
		$hashAlgo = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-algo'];
		$hashSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-senha'];
		
		// ===== Deletar todos os tokens que atingiram o tempo de expiração.
		
		banco_delete
		(
			"plataforma_tokens",
			"WHERE expiration < ".time()
		);
		
		// ===== Gerar ou pegar ID do Token.
		
		$plataforma_tokens = banco_select(Array(
			'unico' => true,
			'tabela' => 'plataforma_tokens',
			'campos' => Array(
				'pubID',
			),
			'extra' => 
				"WHERE remoto IS NULL"
		));
		
		if($plataforma_tokens){
			$tokenPubId = $plataforma_tokens['pubID'];
		} else {
			$tokenPubId = md5(uniqid(rand(), true));
			
			$pubIDValidation = hash_hmac($hashAlgo, $tokenPubId, $hashSenha);
			
			// ====== Salvar token no banco
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "pubID"; $campo_valor = $tokenPubId; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "expiration"; $campo_valor = $expiration; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 					$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"plataforma_tokens"
			);
		}
		
		// ===== Gerar o token JWT
		
		$token = api_servidor_gerar_jwt(Array(
			'host' => $serverHost,
			'expiration' => $expiration,
			'pubID' => $tokenPubId,
			'chavePrivada' => $chavePrivada,
			'chavePrivadaSenha' => $chavePrivadaSenha,
		));
		
		return $token;
	}
	
	return false;
}

function api_servidor_interface($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// interface - String - Obrigatório - Identificador da interface que se deseja acessar no servidor.
	// opcao - String - Opcional - Opção para acessar na interface do servidor.
	// dados - Array - Opcional - Dados necessários para enviar para o servidor.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno = Array(
		'error-msg' => '',
		'status' => '',
		'error' => false,
		'completed' => false,
	);
	
	if(isset($interface)){
		// ===== Procurar no config os hosts do servidor e o pub id.
		
		$hostId = $_GESTOR['plataforma-cliente']['id'];
		$hostsServidor = $_GESTOR['plataforma-cliente']['hosts'];
		$plataformaId = $_GESTOR['plataforma-cliente']['plataforma-id'];
		
		foreach($hostsServidor as $host){
			if($host['id'] == $plataformaId){
				$serverHost = $host['host'];
				break;
			}
		}
		
		// ===== Gerar Token de autorização para conferência pelo servidor se a conexão provêm de fato do cliente e não de outro que se passa pelo cliente.
		
		$token = api_servidor_gerar_token_autorizacao(Array(
			'serverHost' => $serverHost,
		));
		
		// ===== Conectar na plataforma do servidor na interface requisitada
		
		$protocolo = 'https://';
		$url = $protocolo . $serverHost . '/_plataforma/' . $interface;
		
		// ===== Montar o campo 'data' que será enviado ao servidor.
		
		$data = false;
		
		$data['token'] = $token;
		$data['hostId'] = $hostId;
		
		if(isset($opcao)) $data['opcao'] = $opcao;
		if(isset($dados)) $data['dados'] = json_encode($dados);
		
		// ===== Montar o cURL da conexão com todas as opções
		
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
		} else if(isset($plataformaRetorno['error'])){
			$retorno['error-msg'] = '[error] '.$plataformaRetorno['error'].' '.$plataformaRetorno['error_msg']; $retorno['error'] = true;
		} else if($plataformaRetorno['status'] != 'OK'){
			$retorno['status'] = $plataformaRetorno['status'];
			if(!isset($plataformaRetorno['error-msg'])){
				$retorno['error-msg'] = '[not-OK] '.print_r($plataformaRetorno,true);
			} else {
				$retorno['error-msg'] = $plataformaRetorno['error-msg'];
			}
			$retorno['error'] = true;
			if(isset($plataformaRetorno['data'])) $retorno['data'] = $plataformaRetorno['data'];
		} else {
			if(isset($plataformaRetorno['data'])) $retorno['data'] = $plataformaRetorno['data'];
			$retorno['completed'] = true;
		}
		
		return $retorno;
	} else {
		// ===== Caso não seja definida os parâmetros obrigatórios, retornar erro.
		
		$retorno['error-msg'] = 'interface is mandatory';
		$retorno['error'] = true;
		
		return $retorno;
	}
}

?>