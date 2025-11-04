<?php
/**
 * Biblioteca de Autenticação
 *
 * Gerencia autenticação segura com JWT (JSON Web Tokens), criptografia RSA,
 * controle de acesso, geração de tokens, validação de credenciais e
 * proteção contra ataques de força bruta.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.2.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-autenticacao']							=	Array(
	'versao' => '1.2.0',
);

// ===== Funções auxiliares

/**
 * Gera um número aleatório criptograficamente seguro.
 *
 * Utiliza openssl_random_pseudo_bytes() para gerar números verdadeiramente
 * aleatórios, adequados para uso em criptografia e segurança.
 *
 * @param int $min Valor mínimo (inclusivo).
 * @param int $max Valor máximo (inclusivo).
 * 
 * @return int Número aleatório seguro entre $min e $max.
 */
function autenticacao_crypto_rand_secure($min, $max) {
	
	// Calcular range e validar
	$range = $max - $min;
	if ($range < 0) return $min; // Range inválido, retorna mínimo
	
	// Calcular bytes necessários baseado no range
	$log = log($range, 2);
	$bytes = (int) ($log / 8) + 1; // comprimento em bytes
	$bits = (int) $log + 1; // comprimento em bits
	$filter = (int) (1 << $bits) - 1; // máscara de bits
	
	// Gerar número aleatório dentro do range
	do {
		$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
		$rnd = $rnd & $filter; // descartar bits irrelevantes
	} while ($rnd >= $range);
	
	return $min + $rnd;
}

/**
 * Gera um token JWT assinado com chave RSA para autenticação de cliente.
 *
 * Cria um JSON Web Token com header, payload e assinatura RSA. O token
 * contém informações do host, expiração e ID público, sendo assinado
 * com chave pública RSA. Suporta fragmentação para payloads grandes.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['host'] Host de acesso do JWT (obrigatório).
 * @param int $params['expiration'] Timestamp de expiração do JWT (obrigatório).
 * @param string $params['pubID'] ID público do token para referência (obrigatório).
 * @param string $params['chavePublica'] Chave pública RSA para assinar (obrigatório).
 * 
 * @return string|false Token JWT completo ou false em caso de erro.
 */
function autenticacao_cliente_gerar_jwt($params = false){
	// Limite de caracteres para criptografia RSA
	$cryptMaxCharsValue = 245; // Limitação do openssl_private_encrypt()
	
	// Extrair parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($host) && isset($expiration) && isset($pubID) && isset($chavePublica)){
		// ===== Montar Header do JWT

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

// ===== Funções principais

/**
 * Gera par de chaves pública e privada OpenSSL com algoritmo RSA.
 *
 * Cria um par de chaves RSA com fallback automático para diferentes
 * configurações (SHA512/SHA256, 2048/1024 bits) para compatibilidade
 * com diferentes ambientes. Suporta criptografia da chave privada com senha.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['tipo'] Tipo da chave (RSA obrigatório).
 * @param string $params['senha'] Senha para encriptar a chave privada (opcional).
 * 
 * @return array|false Array com 'publica' e 'privada', ou false em erro.
 */
function autenticacao_openssl_gerar_chaves($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Inicializar variável de retorno
	
	$chaves = false;
	
	if(isset($tipo)){
		switch($tipo){
			case 'RSA':
				// Limpa erros anteriores do OpenSSL
				while (openssl_error_string() !== false) {
					// Remove todos os erros da fila
				}
				
				// Lista de configurações para tentar (da mais complexa para a mais simples)
				$configs = [
					// Configuração padrão completa
					[
						"digest_alg" => "sha512",
						"private_key_bits" => 2048,
						"private_key_type" => OPENSSL_KEYTYPE_RSA,
					],
					// Configuração alternativa SHA256
					[
						"digest_alg" => "sha256",
						"private_key_bits" => 2048,
						"private_key_type" => OPENSSL_KEYTYPE_RSA,
					],
					// Configuração mínima para Windows com chave menor
					[
						"digest_alg" => "sha256",
						"private_key_bits" => 1024,
						"private_key_type" => OPENSSL_KEYTYPE_RSA,
					],
					// Configuração apenas com tipo (usa padrões do sistema)
					[
						"private_key_type" => OPENSSL_KEYTYPE_RSA,
					],
					// Configuração vazia (usa todos os padrões)
					[]
				];
				
				$res = false;
				$configUsada = null;
				
				foreach ($configs as $index => $config) {
					// Limpa erros antes de cada tentativa
					while (openssl_error_string() !== false) {
						// Remove erros da fila
					}
					
					$res = openssl_pkey_new($config);
					
					if ($res !== false) {
						$configUsada = $index + 1;
						break;
					}
					
					// Se falhou, registra o erro mas continua tentando
					$error = openssl_error_string();
					if ($error) {
						error_log("OpenSSL tentativa " . ($index + 1) . " falhou: " . $error);
					}
				}
				
				// Se todas as configurações falharam
				if ($res === false) {
					$lastError = '';
					while (($error = openssl_error_string()) !== false) {
						$lastError = $error;
					}
					
					// Informações do sistema para debug
					$phpVersion = PHP_VERSION;
					$opensslVersion = OPENSSL_VERSION_TEXT ?? 'Não disponível';
					$osInfo = php_uname();
					
					throw new Exception(
						"Erro crítico ao gerar chave OpenSSL. Todas as configurações falharam.\n" .
						"Último erro: " . ($lastError ?: "Erro desconhecido") . "\n" .
						"PHP: {$phpVersion}\n" .
						"OpenSSL: {$opensslVersion}\n" .
						"Sistema: {$osInfo}\n" .
						"Sugestão: Verifique se a extensão OpenSSL está habilitada e configurada corretamente."
					);
				}
				
				// Log da configuração que funcionou
				if ($configUsada) {
					error_log("OpenSSL: Chave gerada com sucesso usando configuração {$configUsada}");
				}
				
				// Exporta a chave privada
				if(isset($senha)){
					$exportResult = openssl_pkey_export($res, $chavePrivada, $senha);
				} else {
					$exportResult = openssl_pkey_export($res, $chavePrivada);
				}
				
				// Verifica se a exportação foi bem-sucedida
				if ($exportResult === false) {
					$error = '';
					while (($err = openssl_error_string()) !== false) {
						$error = $err;
					}
					throw new Exception("Erro ao exportar chave privada: " . ($error ?: "Erro desconhecido na exportação"));
				}
				
				// Obtém os detalhes da chave (incluindo a chave pública)
				$chavePrivadaDetalhes = openssl_pkey_get_details($res);
				
				// Verifica se conseguiu obter os detalhes da chave
				if ($chavePrivadaDetalhes === false) {
					$error = '';
					while (($err = openssl_error_string()) !== false) {
						$error = $err;
					}
					throw new Exception("Erro ao obter detalhes da chave: " . ($error ?: "Erro desconhecido nos detalhes"));
				}
				
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
 * Gera uma senha aleatória segura com caracteres variados.
 *
 * Cria senhas fortes combinando letras maiúsculas, minúsculas, números
 * e caracteres especiais usando geração criptograficamente segura.
 *
 * @param int $length Comprimento da senha (padrão: 32 caracteres).
 * 
 * @return string Senha aleatória gerada.
 */
function autenticacao_gerar_senha($length=32){
	
	$senha = "";$codeAlphabet = "";
	$count = 0;
	
    $code[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $code[] = "abcdefghijklmnopqrstuvwxyz";
    $code[] = "0123456789";
    $code[] = "!@#$%^&*";
	
	for($i=0;$i<count($code);$i++){
		$codeAlphabet .= $code[$i];
	}
	
    for($i=0;$i<$length;$i++){
		if($i == $length - count($code) + $count){
			$found = false;
			for($j=0;$j<strlen($code[$count]);$j++){
				if(strpos($senha, $code[$count][$j]) !== false){
					$found = true;
					break;
				}
			}
			
			if($found){
				$senha .= $codeAlphabet[autenticacao_crypto_rand_secure(0,strlen($codeAlphabet))];
			} else {
				$senha .= $code[$count][autenticacao_crypto_rand_secure(0,strlen($code[$count]))];
			}
			
			$count++;
		} else {
			$senha .= $codeAlphabet[autenticacao_crypto_rand_secure(0,strlen($codeAlphabet))];
		}
    }
	
    return $senha;
}

/**
 * Gera JWT assinado com chave pública RSA.
 *
 * @param array|false $params Parâmetros (host, expiration, pubID, chavePublica obrigatórios).
 * @return string|false Token JWT ou false em erro.
 */
function autenticacao_gerar_jwt_chave_publica($params = false){
	$cryptMaxCharsValue = 245;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
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

/**
 * Gera JWT assinado com chave privada RSA.
 *
 * @param array|false $params Parâmetros (host, expiration, pubID, chavePrivada, chavePrivadaSenha obrigatórios; payload opcional).
 * @return string|false Token JWT ou false em erro.
 */
function autenticacao_gerar_jwt_chave_privada($params = false){
	$cryptMaxCharsValue = 245;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($host) && isset($expiration) && isset($pubID) && isset($chavePrivada) && isset($chavePrivadaSenha)){
		// ===== Header

		$header = [
		   'alg' => 'RSA',
		   'typ' => 'JWT'
		];

		$header = json_encode($header);
		$header = base64_encode($header);

		// ===== Payload (usar payload customizado se fornecido, senão usar padrão)

		if(isset($payload) && is_array($payload)){
			// Usar payload customizado fornecido
			$payloadData = $payload;
		} else {
			// Payload padrão
			$payloadData = [
				'iss' => $host, // The issuer of the token
				'exp' => $expiration, // This will define the expiration in NumericDate value. The expiration MUST be after the current date/time.
				'sub' => $pubID, // ID público do totken
			];
		}

		$payload = json_encode($payloadData);
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

/**
 * Valida JWT usando chave pública RSA.
 *
 * @param array|false $params Parâmetros (token, chavePublica obrigatórios; retornarPayloadCompleto opcional).
 * @param bool $params['retornarPayloadCompleto'] Se true, retorna o payload completo em vez de apenas o pubID.
 * @return array|string|false Payload decodificado, pubID ou false se inválido.
 */
function autenticacao_validar_jwt_chave_publica($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
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
				// Se tudo estiver válido, retorna o pubID do token ou o payload completo conforme solicitado.
				
				if(isset($retornarPayloadCompleto) && $retornarPayloadCompleto === true){
					return $payload;
				} else {
					return $payload['sub'];
				}
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

/**
 * Valida JWT usando chave privada RSA.
 *
 * @param array|false $params Parâmetros (token, chavePrivada, chavePrivadaSenha obrigatórios).
 * @return array|false Payload decodificado ou false se inválido.
 */
function autenticacao_validar_jwt_chave_privada($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
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

/**
 * Gera token JWT de validação para cliente.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @global array $_CRON Sistema de cron.
 * @param array|false $params Parâmetros (id_hosts obrigatório, pubID opcional).
 * @return array Token gerado ou array vazio em erro.
 */
function autenticacao_cliente_gerar_token_validacao($params = false){
	global $_GESTOR;
	global $_CONFIG;
	global $_CRON;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($id_hosts)){
	
		// ===== Definir variáveis para gerar o JWT
		
		$expiration = time() + $_CONFIG['autenticacao-token-lifetime'];
		
		// ===== Pegar a chave pública do host
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'chave_publica',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		if($hosts){
			$chavePublica = $hosts[0]['chave_publica'];
			
			// ===== Gerar ID do Token
			
			if(isset($pubID)){
				$tokenPubId = $pubID;
			} else {
				$tokenPubId = md5(uniqid(rand(), true));
			}
			
			// ===== Gerar o token JWT
			
			$token = autenticacao_cliente_gerar_jwt(Array(
				'host' => (isset($_CRON['SERVER_NAME']) ? $_CRON['SERVER_NAME'] : $_SERVER['SERVER_NAME']),
				'expiration' => $expiration,
				'pubID' => $tokenPubId,
				'chavePublica' => $chavePublica,
			));
			
			return Array(
				'token' => $token,
				'pubID' => $tokenPubId,
			);
		}
	}
	
	return Array();
}

/**
 * Verifica estado de acesso do usuário com proteção anti-spam.
 *
 * Retorna se o acesso é permitido baseado em tentativas anteriores e bloqueios por IP.
 * Previne ataques de força bruta limitando tentativas de acesso.
 *
 * @global array $_GESTOR Sistema global.
 * @param array|false $params Parâmetros (tipo obrigatório).
 * @return array Estado do acesso com 'permitido', 'status' e 'mensagem' opcional.
 */
function autenticacao_acesso_verificar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Inicializar resposta padrão
	
	$retorno = [
		'permitido' => false,
		'status' => 'livre',
	];
	
	if(isset($tipo)){
		// ===== Limpar os acessos antigos.
		
		autenticacao_acessos_limpeza();
		
		// ===== Pegar o IP do usuário.

		gestor_incluir_biblioteca('ip');

		$ip = ip_get();
		
		// ===== Verificar se o limite de erros de acesso foram atingidos na tabela acessos e tratar cada caso baseado no máximo de erros de acesso.
		
		$acessos = banco_select(Array(
			'unico' => true,
			'tabela' => 'acessos',
			'campos' => Array(
				'status',
			),
			'extra' => 
				"WHERE tipo='".$tipo."'"
				." AND ip='".$ip."'"
		));
		
		if($acessos){
			$retorno['status'] = $acessos['status'];
			
			switch($acessos['status']){
				case 'bloqueado':
					$retorno['permitido'] = false;
				break;
				default:
					$retorno['permitido'] = true;
			}
		} else {
			$retorno['permitido'] = true;
		}
	}
	
	return $retorno;
}

/**
 * Cadastra tentativa de acesso do usuário para controle anti-spam.
 *
 * Registra acessos em locais do sistema para rastreamento e prevenção de abuso.
 * Suporta limitação automática de tentativas por IP.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @param array|false $params Parâmetros (tipo obrigatório, antispam opcional).
 * @return void
 */
function autenticacao_acesso_cadastrar($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($tipo)){
		// ===== Quantidade total de cadastros do tipo informado e quantidade de bloqueios de um IP.
		
		$quantidade = 0;
		$bloqueios = 0;
		
		// ===== Pegar o IP do usuário.

		gestor_incluir_biblioteca('ip');

		$ip = ip_get();
		
		// ===== Verificar se existe a tabela acessos para o ip atual.
		
		$acessos = banco_select(Array(
			'unico' => true,
			'tabela' => 'acessos',
			'campos' => Array(
				'quantidade',
				'bloqueios',
			),
			'extra' => 
				"WHERE tipo='".$tipo."'"
				." AND ip='".$ip."'"
		));
		
		// ===== Pegar a quantidade atual e incrementar um.
		
		if($acessos){
			$quantidade = ($acessos['quantidade'] ? (int)$acessos['quantidade'] : 0);
			$bloqueios = ($acessos['bloqueios'] ? (int)$acessos['bloqueios'] : 0);
		}
		
		$quantidade++;
		
		// ===== Definir o estado do acesso.
		
		$maximoCadastros = $_CONFIG['acessos-maximo-cadastros'][$tipo];
		$maximoCadastrosSimples = $_CONFIG['acessos-maximo-cadastros-simples'][$tipo];
		
		if(isset($antispam)){
			if($quantidade < $maximoCadastrosSimples){
				$status = 'livre';
			} else if($quantidade < $maximoCadastros){
				$status = 'antispam';
			} else {
				$status = 'bloqueado';
			}
		} else {
			if($quantidade < $maximoCadastros){
				$status = 'livre';
			} else {
				$status = 'bloqueado';
			}
		}
		
		// ===== Caso seja bloqueado, calcular tempo limite de bloqueio.
		
		if($status == 'bloqueado'){
			$bloqueios++;
			$tempo_bloqueio = $bloqueios * $_CONFIG['acessos-tempo-bloqueio-ip'] + time();
		}
		
		// ===== Atualizar ou criar o registro de acesso com o cadastro no banco de dados.
		
		if($acessos){
			banco_update_campo('status',$status);
			banco_update_campo('tempo_modificacao',time());
			
			if(isset($tempo_bloqueio)){
				banco_update_campo('bloqueios',$bloqueios);
				banco_update_campo('tempo_bloqueio',$tempo_bloqueio);
				banco_update_campo('quantidade','0');
			} else {
				banco_update_campo('quantidade',$quantidade);
			}
			
			banco_update_executar('acessos',"WHERE tipo='".$tipo."' AND ip='".$ip."'");
		} else {
			banco_insert_name_campo('ip',$ip);
			banco_insert_name_campo('tipo',$tipo);
			banco_insert_name_campo('tempo_modificacao',time());
			banco_insert_name_campo('status',$status);
			
			if(isset($tempo_bloqueio)){
				banco_insert_name_campo('bloqueios',$bloqueios);
				banco_insert_name_campo('tempo_bloqueio',$tempo_bloqueio);
				banco_insert_name_campo('quantidade','0');
			} else {
				banco_insert_name_campo('quantidade',$quantidade);
			}
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"acessos"
			);
		}
	}
}

/**
 * Confirma acesso bem-sucedido do usuário.
 *
 * Atualiza registro de acesso marcando como confirmado ('C') e resetando contador de tentativas.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @param array|false $params Parâmetros (tipo obrigatório).
 * @return void
 */
function autenticacao_acesso_confirmar($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	// ===== 
	
	if(isset($tipo)){
		// ===== Pegar o IP do usuário.

		gestor_incluir_biblioteca('ip');

		$ip = ip_get();
		
		// ===== Verificar se existe a tabela acessos para o ip atual.
		
		$acessos = banco_select(Array(
			'unico' => true,
			'tabela' => 'acessos',
			'campos' => Array(
				'quantidade',
				'bloqueios',
			),
			'extra' => 
				"WHERE tipo='".$tipo."'"
				." AND ip='".$ip."'"
		));
		
		// ===== Definir o estado do acesso.
		
		$status = 'livre';
		
		// ===== Atualizar o registro de acesso com confirmação no banco de dados.
		
		if($acessos){
			banco_update_campo('status',$status);
			banco_update_campo('tempo_modificacao',time());
			banco_update_campo('quantidade','0');
			
			banco_update_executar('acessos',"WHERE tipo='".$tipo."' AND ip='".$ip."'");
		}
	}
}

/**
 * Registra falha de acesso do usuário.
 *
 * Incrementa contador de tentativas falhas e bloqueia IP após limite excedido.
 * Parte do sistema anti-spam e proteção contra força bruta.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @param array|false $params Parâmetros (tipo obrigatório).
 * @return void
 */
function autenticacao_acesso_falha($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	// ===== 
	
	if(isset($tipo)){
		// ===== Quantidade total de falhas do tipo informado e quantidade de bloqueios de um IP.
		
		$quantidade = 0;
		$bloqueios = 0;
		
		// ===== Pegar o IP do usuário.

		gestor_incluir_biblioteca('ip');

		$ip = ip_get();
		
		// ===== Verificar se existe a tabela acessos para o ip atual.
		
		$acessos = banco_select(Array(
			'unico' => true,
			'tabela' => 'acessos',
			'campos' => Array(
				'quantidade',
				'bloqueios',
			),
			'extra' => 
				"WHERE tipo='".$tipo."'"
				." AND ip='".$ip."'"
		));
		
		// ===== Pegar a quantidade atual e incrementar um.
		
		if($acessos){
			$quantidade = ($acessos['quantidade'] ? (int)$acessos['quantidade'] : 0);
			$bloqueios = ($acessos['bloqueios'] ? (int)$acessos['bloqueios'] : 0);
		}
		
		$quantidade++;
		
		// ===== Definir o estado do acesso.
		
		$maximoLoginsSimples = $_CONFIG['acessos-maximo-logins-simples'];
		$maximoFalhasLogins = $_CONFIG['acessos-maximo-falhas-logins'];
		
		if($quantidade < $maximoLoginsSimples){
			$status = 'livre';
		} else if($quantidade < $maximoFalhasLogins){
			$status = 'antispam';
		} else {
			$status = 'bloqueado';
		}
		
		// ===== Caso seja bloqueado, calcular tempo limite de bloqueio.
		
		if($status == 'bloqueado'){
			$bloqueios++;
			$tempo_bloqueio = $bloqueios * $_CONFIG['acessos-tempo-bloqueio-ip'] + time();
		}
		
		// ===== Atualizar ou criar o registro de acesso com falha no banco de dados.
		
		if($acessos){
			banco_update_campo('status',$status);
			banco_update_campo('tempo_modificacao',time());
			
			if(isset($tempo_bloqueio)){
				banco_update_campo('bloqueios',$bloqueios);
				banco_update_campo('tempo_bloqueio',$tempo_bloqueio);
				banco_update_campo('quantidade','0');
			} else {
				banco_update_campo('quantidade',$quantidade);
			}
			
			banco_update_executar('acessos',"WHERE tipo='".$tipo."' AND ip='".$ip."'");
		} else {
			banco_insert_name_campo('ip',$ip);
			banco_insert_name_campo('tipo',$tipo);
			banco_insert_name_campo('tempo_modificacao',time());
			banco_insert_name_campo('status',$status);
			
			if(isset($tempo_bloqueio)){
				banco_insert_name_campo('bloqueios',$bloqueios);
				banco_insert_name_campo('tempo_bloqueio',$tempo_bloqueio);
				banco_insert_name_campo('quantidade','0');
			} else {
				banco_insert_name_campo('quantidade',$quantidade);
			}
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"acessos"
			);
		}
	}
}

/**
 * Limpa registros antigos da tabela de acessos.
 *
 * Remove registros de acessos expirados para manter banco otimizado.
 * Executado periodicamente pelo sistema de cron.
 *
 * @global array $_GESTOR Sistema global.
 * @global array $_CONFIG Configurações.
 * @param array|false $params Parâmetros da função.
 * @return void
 */
function autenticacao_acessos_limpeza($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Remover registros antigos
	
	$tempoDesbloqueio = $_CONFIG['acessos-tempo-desbloqueio-ip'];
	
	banco_delete
	(
		"acessos",
		"WHERE tempo_modificacao + ".$tempoDesbloqueio." < ".time()
	);
	
	// ===== Desbloquear ips com tempo de bloqueio expirado.
	
	banco_update_campo('status','livre');
	
	banco_update_executar('acessos',"WHERE status='bloqueado' AND tempo_bloqueio < ".time());
	
}

/**
 * Encripta valor usando chave pública RSA.
 *
 * @param array|false $params Parâmetros (valor, chavePublica obrigatórios).
 * @return string|false Valor encriptado em base64 ou false em erro.
 */
function autenticacao_encriptar_chave_publica($params = false){
	$cryptMaxCharsValue = 245;
	
	if($params)foreach($params as $var => $val)$$var = $val;

	// ===== Validar parâmetros obrigatórios

	if(isset($valor) && isset($chavePublica)){
		try {
			// ===== Valor base64 encode

			$valor = base64_encode($valor);

			// ===== Valor a ser encriptado

			$rawDataSource = $valor;
			
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

			// ===== Retornar o valor encriptado
			
			return $encodedData;
		} catch (Exception $e) {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Encripta valor usando chave privada RSA.
 *
 * @param array|false $params Parâmetros (valor, chavePrivada, chavePrivadaSenha obrigatórios).
 * @return string|false Valor encriptado em base64 ou false em erro.
 */
function autenticacao_encriptar_chave_privada($params = false){
	$cryptMaxCharsValue = 245;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios

	if(isset($valor) && isset($chavePrivada) && isset($chavePrivadaSenha)){
		try {
			// ===== Valor base64 encode

			$valor = base64_encode($valor);

			// ===== Valor a ser encriptado

			$rawDataSource = $valor;
			
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

			// ===== Retornar o valor encriptado

			return $encodedData;
		} catch (Exception $e) {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Decripta valor usando chave pública RSA.
 *
 * @param array|false $params Parâmetros (criptografia, chavePublica obrigatórios).
 * @return string|false Valor decriptado ou false em erro.
 */
function autenticacao_decriptar_chave_publica($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($criptografia) && isset($chavePublica)){
		try {
			// ===== Criptografia a ser decifrada

			$encodedData = $criptografia;
			
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

			// ===== Retornar valor decifrado
		
			$decodedData = base64_decode($decodedData);

			return $decodedData;
		} catch (Exception $e) {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Decripta valor usando chave privada RSA.
 *
 * @param array|false $params Parâmetros (criptografia, chavePrivada, chavePrivadaSenha obrigatórios).
 * @return string|false Valor decriptado ou false em erro.
 */
function autenticacao_decriptar_chave_privada($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($criptografia) && isset($chavePrivada) && isset($chavePrivadaSenha)){
		try {
			// ===== Criptografia a ser decifrada

			$encodedData = $criptografia;
			
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

			// ===== Retornar valor decifrado
		
			$decodedData = base64_decode($decodedData);

			return $decodedData;
		} catch (Exception $e) {
			return false;
		}
	} else {
		return false;
	}
}

?>