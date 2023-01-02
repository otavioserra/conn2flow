<?php

global $_GESTOR;

$_GESTOR['biblioteca-autenticacao']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares

function autenticacao_crypto_rand_secure($min, $max) {
	/**********
		Descrição: gerador de número realmente aleatório
	**********/
	
	$range = $max - $min;
	if ($range < 0) return $min; // not so random...
	$log = log($range, 2);
	$bytes = (int) ($log / 8) + 1; // length in bytes
	$bits = (int) $log + 1; // length in bits
	$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
	do {
		$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
		$rnd = $rnd & $filter; // discard irrelevant bits
	} while ($rnd >= $range);
	return $min + $rnd;
}

function autenticacao_cliente_gerar_jwt($params = false){
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

// ===== Funções principais

function autenticacao_openssl_gerar_chaves($params = false){
	/**********
		Descrição: gerador de par chave pública e privada SSL
	**********/
	
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

function autenticacao_gerar_senha($length=32){
    /**********
		Descrição: gerador de senhas aleatórias
	**********/
	
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

function autenticacao_gerar_jwt_chave_publica($params = false){
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

function autenticacao_gerar_jwt_chave_privada($params = false){
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

function autenticacao_validar_jwt_chave_publica($params = false){
	
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

function autenticacao_validar_jwt_chave_privada($params = false){
	
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

function autenticacao_qr_code($params = false){
	/**********
		Descrição: gerador de QR Code
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// conteudo - String - Obrigatório - Valor textual do QR Code.
	// tmpImg - Bool - Opcional - Gerar imagem temporária do QR Code e retornar o caminho.
	
	// ===== 
	
	if(isset($conteudo)){
		if(isset($tmpImg)){
			// ===== Arquivo temporário.
			
			$path_temp = sys_get_temp_dir().'/';
			$temp_id = '-'.md5(uniqid(rand(), true));
			$tmpImagemPNG = $path_temp.'imagem'.$temp_id.'.png';
			
			// ===== Gerar QR Code com a biblioteca QRLIB.
			
			require_once $_GESTOR['bibliotecas-path'].'qrlib/qrlib.php';
			
			QRcode::png($conteudo, $tmpImagemPNG, QR_ECLEVEL_L, 4, 0);
			
			// ===== Retornar o arquivo temporário da Imagem.
			
			return $tmpImagemPNG;
		} else {
			// ===== Arquivo temporário.
			
			$path_temp = sys_get_temp_dir().'/';
			$temp_id = '-'.md5(uniqid(rand(), true));
			$tmpImagemPNG = $path_temp.'imagem'.$temp_id;
			
			// ===== Gerar QR Code com a biblioteca QRLIB.
			
			require_once $_GESTOR['bibliotecas-path'].'qrlib/qrlib.php';
			
			QRcode::png($conteudo, $tmpImagemPNG, QR_ECLEVEL_L, 4, 0);
			
			// ===== Gerar a imagem base64.
			
			$imagemBase64 = 'data:image/png;base64, ' . base64_encode(file_get_contents($tmpImagemPNG));
			
			// ===== Apagar arquivo temporário.
			
			unlink($tmpImagemPNG);
			
			// ===== Retornar Imagem.
			
			return $imagemBase64;
		}
	} else {
		return '';
	}
}

function autenticacao_cliente_gerar_token_validacao($params = false){
	global $_GESTOR;
	global $_CONFIG;
	global $_CRON;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - String - Obrigatório - ID do host do cliente.
	// pubID - String - Opcional - Pub ID do dado.
	
	// ===== 
	
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

function autenticacao_acesso_verificar($params = false){
	/**********
		Descrição: Função responsável por verificar o acesso de usuários e retornar o estado do acesso atual bem como mensagem de alerta caso necessário.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// tipo - String - Obrigatório - Identificador único do tipo de acesso.
	
	// ===== 
	
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

function autenticacao_acesso_falha($params = false){
	/**********
		Descrição: Função responsável por incluir falha de acesso de usuários.
	**********/
	
	global $_GESTOR;
	global $_CONFIG;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// tipo - String - Obrigatório - Identificador único do tipo de acesso.
	
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
		
		if($quantidade <= $maximoLoginsSimples){
			$status = 'livre';
		} else if($quantidade <= $maximoFalhasLogins){
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

function autenticacao_acessos_limpeza($params = false){
	/**********
		Descrição: Função responsável por limpar a tabela de acessos e remover registros antigos.
	**********/
	
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

?>