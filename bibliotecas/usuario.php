<?php

global $_GESTOR;

$_GESTOR['biblioteca-usuario']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function usuario_openssl_gerar_chaves($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// tipo - String - Obrigatório - Tipo da chave openssl que será gerada usando o algoritmo correto.
	// senha - String - Opcional - Senha para encriptar a chave privada.
	
	// ===== 
	
	$chaves = false;
	
	if(isset($tipo)){
		switch($tipo){
			case 'RSA':
				$config = array(
					"digest_alg" => "sha512",
					"private_key_bits" => 2048,
					"private_key_type" => OPENSSL_KEYTYPE_RSA,
				);
				
				$res = openssl_pkey_new($config);
				
				if(isset($senha)){
					openssl_pkey_export($res, $chavePrivada,$senha);
				} else {
					openssl_pkey_export($res, $chavePrivada);
				}
				
				$chavePrivadaDetalhes = openssl_pkey_get_details($res);
				$chavePublica = $chavePrivadaDetalhes["key"];
				
				return Array(
					'publica' => $chavePublica,
					'privada' => $chavePrivada,
				);
			break;
		}
	}
	
	return $chaves;
}

function usuario_gerar_jwt($params = false){
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

function usuario_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_usuarios - Int - Obrigatório - Identificador do usuário dentro do sistema.
	// DESATIVADO - fingerprint - String - Obrigatório - Identificador único do browser.
	// sessao - Bool - Opcional - Caso definido, o cookie gerado será de sessão (caso o usuário não clique na opção para ficar conectado, a autenticação será excluída quando o navegador fechar).
	
	// ===== 
	
	//if(isset($id_usuarios) && isset($fingerprint)){ DESATIVADO O FINGERPRINT
	
	if(isset($id_usuarios)){
		// ===== Definir variáveis para gerar o JWT
		
		if(isset($sessao)){
			$expiration = '0';
		} else {
			$expiration = time() + $_GESTOR['cookie-lifetime'];
		}
		
		$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
		
		$fp = fopen($keyPublicPath,"r");
		$chavePublica = fread($fp,8192);
		fclose($fp);
		
		// ===== Gerar ID do Token
		
		$tokenPubId = md5(uniqid(rand(), true));
		
		$pubIDValidation = hash_hmac($_GESTOR['usuario-hash-algo'], $tokenPubId, $_GESTOR['usuario-hash-password']);
		
		// ===== Gerar o token JWT
		
		$token = usuario_gerar_jwt(Array(
			'host' => $_SERVER['SERVER_NAME'],
			'expiration' => $expiration,
			'chavePublica' => $chavePublica,
			'pubID' => $tokenPubId,
		));
		
		// ===== Salvar cookie no client do usuário
		
		setcookie($_GESTOR['cookie-authname'], $token, [
			'expires' => $expiration,
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		// ====== Salvar token no banco
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubID"; $campo_valor = $tokenPubId; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		//$campo_nome = "fingerprint"; $campo_valor = $fingerprint; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "ip"; $campo_valor = $_SERVER['REMOTE_ADDR']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "user_agent"; $campo_valor = $_SERVER['HTTP_USER_AGENT']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"usuarios_tokens"
		);
		
		return true;
	} else {
		return false;
	}
}

function usuario_app_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_usuarios - Int - Obrigatório - Identificador do usuário dentro do sistema.
	
	// ===== 
	
	if(isset($id_usuarios)){
		// ===== Definir variáveis para gerar o JWT
		
		$expiration = time() + $_GESTOR['app-token-lifetime'];
		
		$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
		
		$fp = fopen($keyPublicPath,"r");
		$chavePublica = fread($fp,8192);
		fclose($fp);
		
		// ===== Gerar ID do Token
		
		$tokenPubId = md5(uniqid(rand(), true));
		
		$pubIDValidation = hash_hmac($_GESTOR['usuario-hash-algo'], $tokenPubId, $_GESTOR['usuario-hash-password']);
		
		// ===== Gerar o token JWT
		
		$token = usuario_gerar_jwt(Array(
			'host' => $_SERVER['SERVER_NAME'],
			'expiration' => $expiration,
			'chavePublica' => $chavePublica,
			'pubID' => $tokenPubId,
		));
		
		// ====== Salvar token no banco
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubID"; $campo_valor = $tokenPubId; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "expiration"; $campo_valor = $expiration; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "ip"; $campo_valor = $_SERVER['REMOTE_ADDR']; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "user_agent"; $campo_valor = $_GESTOR['app-user-agent']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 						$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"usuarios_tokens"
		);
		
		return Array(
			'token' => $token,
			'expiration' => $expiration,
		);
	} else {
		return false;
	}
}

function usuario_autorizacao_provisoria($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// verificar - Bool - Opcional - Se definido, retorna verdadeiro ou falso caso a autorização provisória exista ou não.
	// validar - Bool - Opcional - Se definido, valida a autorização provisória.
	// invalidar - Bool - Opcional - Se definido, invalida a autorização provisória.
	
	// verificarModal - Conjunto - Opcional - Verifica se tem ou não a autorização provisória, senão tiver mostra modal para redirecionar usuário para a página de criação da autorização.
		// cancelarUrl - String - Opcional - URL que o usuário será redirecionado caso clique no botão cancelar.
		// confirmarUrl - String - Opcional - URL que o usuário será redirecionado caso clique no botão confirmar.
		// autorizadoUrl - String - Opcional - URL que o usuário será redirecionado caso seja autorizado.
		// autorizadoUrlQuerystring - String - Opcional - Query String que será incluído junto da 'autorizadoUrl'.
		
	// ===== 
	
	if(isset($validar)){
		gestor_sessao_variavel('usuario-autorizacao-provisoria',time());
	}
	
	if(isset($invalidar)){
		gestor_sessao_variavel_del('usuario-autorizacao-provisoria');
	}
	
	if(isset($verificar)){
		if(existe(gestor_sessao_variavel('usuario-autorizacao-provisoria'))){
			if(time() > $_GESTOR['usuario-autorizacao-lifetime'] + (int)gestor_sessao_variavel('usuario-autorizacao-provisoria')){
				gestor_sessao_variavel_del('usuario-autorizacao-provisoria');
				
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	
	if(isset($verificarModal)){
		if(existe(gestor_sessao_variavel('usuario-autorizacao-provisoria'))){
			if(time() > $_GESTOR['usuario-autorizacao-lifetime'] + (int)gestor_sessao_variavel('usuario-autorizacao-provisoria')){
				gestor_sessao_variavel_del('usuario-autorizacao-provisoria');
				
				$valido = false;
			} else {
				$valido = true;
			}
		} else {
			$valido = false;
		}
		
		if(!$valido){
			$pagina = gestor_componente(Array(
				'id' => 'interface-formulario-autorizacao-provisoria',
			));
			
			if(isset($verificarModal['cancelarUrl'])){
				$cancelarUrl = $_GESTOR['url-raiz'] . $verificarModal['cancelarUrl'];
			} else {
				$cancelarUrl = $_GESTOR['url-raiz'] . 'dashboard/';
			}
			
			if(isset($verificarModal['confirmarUrl'])){
				$confirmarUrl = $_GESTOR['url-raiz'] . $verificarModal['confirmarUrl'];
			} else {
				$confirmarUrl = $_GESTOR['url-raiz'] . 'restrict-area/';
			}
			
			if(isset($verificarModal['autorizadoUrl'])){
				$autorizadoUrl = '?redirect='.urlencode($verificarModal['autorizadoUrl']);
			} else {
				$autorizadoUrl = '?redirect='.urlencode('dashboard/');
			}
			
			if(isset($verificarModal['autorizadoUrlQuerystring'])){
				$autorizadoUrl .= '&'.$verificarModal['autorizadoUrlQuerystring'];
			}
			
			$pagina = modelo_var_troca($pagina,"#botao-cancelar-url#",$cancelarUrl);
			$pagina = modelo_var_troca($pagina,"#botao-confirmar-url#",$confirmarUrl.$autorizadoUrl);
			
			$pagina = modelo_var_troca($pagina,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-title')));
			$pagina = modelo_var_troca($pagina,"#mensagem#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-message')));
			$pagina = modelo_var_troca($pagina,"#botao-cancelar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-cancel')));
			$pagina = modelo_var_troca($pagina,"#botao-confirmar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-confirm')));
			
			$_GESTOR['pagina'] = $pagina.$_GESTOR['pagina'];
		}
	}
}

function usuario_host_dados($params = false){
	global $_GESTOR;
	global $_USUARIO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// campo - String - Opcional - Caso queira o retorno de apenas um campo do usuário. Senão retorna todos os dados num Array.
	// id_hosts_usuarios - Int - Opcional - Definir o usuário manualmente.
	
	// ===== 

	// ===== Verificar o usuário.
	
	if(isset($id_hosts_usuarios)){
		$usuario_id = (existe($id_hosts_usuarios) ? $id_hosts_usuarios : null);
	} else {
		$usuario_id = null;
	}
	
	if(!isset($usuario_id)){
		$usuarioAnonimo = Array(
			'id_hosts_usuarios' => '0',
			'nome_conta' => 'Anônimo',
			'nome' => 'Anônimo',
			'id' => 'anonimo',
			'usuario' => 'anonimo',
			'email' => 'anonimo',
		);
		
		if(isset($campo)){
			return $usuarioAnonimo[$campo];
		} else {
			return $usuarioAnonimo;
		}
	} else {
		if(!isset($_USUARIO['host-dados'])){
			$hosts_usuarios = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_usuarios',
				'campos' => '*',
				'extra' => 
					"WHERE id_hosts_usuarios='".$usuario_id."'"
					." AND id_hosts='".$_GESTOR['host-id']."'"
			));
			
			$_USUARIO['host-dados'] = $hosts_usuarios;
		}
		
		if(isset($campo)){
			return $_USUARIO['host-dados'][$campo];
		} else {
			return $_USUARIO['host-dados'];
		}
	}
}

?>