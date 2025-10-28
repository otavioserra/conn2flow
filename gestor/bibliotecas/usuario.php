<?php
/**
 * Biblioteca de gerenciamento de usuários e autenticação.
 *
 * Fornece funções para geração de chaves OpenSSL, tokens JWT,
 * autorização de usuários e gerenciamento de sessões. Suporta
 * autenticação via cookies, tokens provisórios e dados de hosts.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.1.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-usuario']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Gera par de chaves pública/privada OpenSSL.
 *
 * Cria chaves criptográficas usando algoritmos OpenSSL para
 * assinatura e validação de tokens JWT.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['tipo'] Tipo de chave: 'RSA' (obrigatório).
 * @param string $params['senha'] Senha para proteger chave privada (opcional).
 * 
 * @return array|false Array com 'publica' e 'privada' ou false.
 */
function usuario_openssl_gerar_chaves($params = false){
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$chaves = false;
	
	// Gera chaves conforme tipo especificado
	if(isset($tipo)){
		switch($tipo){
			case 'RSA':
				// Configuração RSA com SHA-512 e 2048 bits
				$config = array(
					"digest_alg" => "sha512",
					"private_key_bits" => 2048,
					"private_key_type" => OPENSSL_KEYTYPE_RSA,
				);
				
				// Gera novo par de chaves
				$res = openssl_pkey_new($config);
				
				// Exporta chave privada com ou sem senha
				if(isset($senha)){
					openssl_pkey_export($res, $chavePrivada,$senha);
				} else {
					openssl_pkey_export($res, $chavePrivada);
				}
				
				// Extrai chave pública
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

/**
 * Gera token JWT assinado com RSA.
 *
 * Cria JSON Web Token com header, payload e assinatura RSA.
 * Suporta payloads grandes dividindo em chunks de 245 caracteres.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['host'] Host emissor do token (obrigatório).
 * @param int $params['expiration'] Unix timestamp de expiração (obrigatório).
 * @param string $params['pubID'] ID público do token (obrigatório).
 * @param string $params['chavePublica'] Chave pública RSA para assinar (obrigatório).
 * 
 * @return string|false Token JWT ou false se inválido.
 */
function usuario_gerar_jwt($params = false){
	// Limite de caracteres para openssl_private_encrypt
	$cryptMaxCharsValue = 245;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($host) && isset($expiration) && isset($pubID) && isset($chavePublica)){
		// ===== Monta header do JWT
		$header = [
		   'alg' => 'RSA',
		   'typ' => 'JWT'
		];

		$header = json_encode($header);
		$header = base64_encode($header);

		// ===== Monta payload do JWT
		$payload = [
			'iss' => $host, // Emissor do token
			'exp' => $expiration, // Expiração em NumericDate
			'sub' => $pubID, // ID público do token
		];

		$payload = json_encode($payload);
		$payload = base64_encode($payload);

		// ===== Une header com payload para assinatura
		$rawDataSource = $header.".".$payload;
		
		// ===== Assina usando RSA SSL dividindo em chunks
		$resPublicKey = openssl_get_publickey($chavePublica);

		$partialData = '';
		$encodedData = '';
		// Divide em chunks de 245 caracteres
		$split = str_split($rawDataSource , $cryptMaxCharsValue);
		foreach($split as $part){
			openssl_public_encrypt($part, $partialData, $resPublicKey);
			$encodedData .= (strlen($encodedData) > 0 ? '.':'') . base64_encode($partialData);
		}
		
		$encodedData = base64_encode($encodedData);
		
		$signature = $encodedData;
		
		// ===== Retorna JWT completo
		$JWTToken = $header.".".$payload.".".$signature;
		
		return $JWTToken;
	} else {
		return false;
	}
}

function usuario_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
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
			$expiration = time() + $_CONFIG['cookie-lifetime'];
		}
		
		$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
		
		$fp = fopen($keyPublicPath,"r");
		$chavePublica = fread($fp,8192);
		fclose($fp);
		
		// ===== Gerar ID do Token
		
		$tokenPubId = md5(uniqid(rand(), true));
		
		$pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Gerar o token JWT
		
		$token = usuario_gerar_jwt(Array(
			'host' => $_SERVER['SERVER_NAME'],
			'expiration' => $expiration,
			'chavePublica' => $chavePublica,
			'pubID' => $tokenPubId,
		));
		
		// ===== Salvar cookie no client do usuário
		
		setcookie($_CONFIG['cookie-authname'], $token, [
			'expires' => $expiration,
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		// ===== Pegar o IP do usuário.
		
		gestor_incluir_biblioteca('ip');
		
		$ip = ip_get();
		
		// ====== Salvar token no banco
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubID"; $campo_valor = $tokenPubId; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		//$campo_nome = "fingerprint"; $campo_valor = $fingerprint; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "ip"; $campo_valor = $ip; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
	global $_CONFIG;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_usuarios - Int - Obrigatório - Identificador do usuário dentro do sistema.
	
	// ===== 
	
	if(isset($id_usuarios)){
		// ===== Definir variáveis para gerar o JWT
		
		$expiration = time() + $_CONFIG['app-token-lifetime'];
		
		$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
		
		$fp = fopen($keyPublicPath,"r");
		$chavePublica = fread($fp,8192);
		fclose($fp);
		
		// ===== Gerar ID do Token
		
		$tokenPubId = md5(uniqid(rand(), true));
		
		$pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Gerar o token JWT
		
		$token = usuario_gerar_jwt(Array(
			'host' => $_SERVER['SERVER_NAME'],
			'expiration' => $expiration,
			'chavePublica' => $chavePublica,
			'pubID' => $tokenPubId,
		));
		
		// ===== Pegar o IP do usuário.
		
		gestor_incluir_biblioteca('ip');
		
		$ip = ip_get();
		
		// ====== Salvar token no banco
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubID"; $campo_valor = $tokenPubId; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "expiration"; $campo_valor = $expiration; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "ip"; $campo_valor = $ip; 									$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "user_agent"; $campo_valor = $_SERVER['HTTP_USER_AGENT']; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "origem"; $campo_valor = $_CONFIG['app-origem']; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
	global $_CONFIG;
	
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
			if(time() > $_CONFIG['usuario-autorizacao-lifetime'] + (int)gestor_sessao_variavel('usuario-autorizacao-provisoria')){
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
			if(time() > $_CONFIG['usuario-autorizacao-lifetime'] + (int)gestor_sessao_variavel('usuario-autorizacao-provisoria')){
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