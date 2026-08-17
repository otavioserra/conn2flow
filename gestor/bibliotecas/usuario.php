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
 * @version 1.2.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-usuario']							=	Array(
	'versao' => '1.2.0',
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

/**
 * Gera token de autorização para autenticação de usuários web.
 *
 * Cria token JWT, armazena em cookie HTTP-only seguro e salva no banco
 * de dados com informações de sessão (IP, user-agent, expiração).
 * Suporta cookies de sessão ou persistentes.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CONFIG Configurações do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param int $params['id_usuarios'] ID do usuário (obrigatório).
 * @param bool $params['sessao'] Se true, cria cookie de sessão que expira ao fechar navegador (opcional).
 * 
 * @return bool True se token criado com sucesso, false caso contrário.
 */
function usuario_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetro obrigatório
	if(isset($id_usuarios)){
		// ===== Define expiração do token
		if(isset($sessao)){
			$expiration = '0'; // Cookie de sessão
		} else {
			$expiration = time() + $_CONFIG['cookie-lifetime']; // Cookie persistente
		}
		
		// ===== Carrega chave pública para assinar JWT
		$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
		
		$fp = fopen($keyPublicPath,"r");
		$chavePublica = fread($fp,8192);
		fclose($fp);
		
		// ===== Gera identificadores únicos do token
		gestor_incluir_biblioteca('seguranca');
		$tokenPubId = seguranca_token_aleatorio(32);
		
		// Hash HMAC para validação adicional
		$pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Cria token JWT
		$token = usuario_gerar_jwt(Array(
			'host' => $_SERVER['SERVER_NAME'],
			'expiration' => $expiration,
			'chavePublica' => $chavePublica,
			'pubID' => $tokenPubId,
		));
		
		// ===== Define cookie seguro no navegador de autenticação
		setcookie($_CONFIG['cookie-authname'], $token, [
			'expires' => $expiration,
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true, // Apenas HTTPS
			'httponly' => true, // Não acessível via JavaScript
			'samesite' => 'Lax', // Proteção CSRF
		]);

		// ===== Pegar o perfil do usuário para definir cookie de perfil
		$usuarios_perfis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'up.id',
			))
			,
			"usuarios as u, usuarios_perfis as up",
			"WHERE u.id_usuarios='".$id_usuarios."' AND u.id_usuarios_perfis = up.id_usuarios_perfis "
		);

		$perfil_usuario = $usuarios_perfis[0]['up.id'] ?? null;
		
		if(isset($perfil_usuario)){
			// ===== Define cookie seguro no navegador do perfil do usuário
			setcookie($_CONFIG['cookie-authprofile'], hash('sha256', $perfil_usuario), [
				'expires' => $expiration,
				'path' => '/',
				'domain' => $_SERVER['SERVER_NAME'],
				'secure' => true, // Apenas HTTPS
				'httponly' => true, // Não acessível via JavaScript
				'samesite' => 'Lax', // Proteção CSRF
			]);
		}
		
		// ===== Obtém IP do usuário para auditoria

		gestor_incluir_biblioteca('ip');
		
		$ip = ip_get();
		
		// ===== Salva token no banco de dados
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubID"; $campo_valor = $tokenPubId; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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

/**
 * Gera token de autorização para aplicativos mobile.
 *
 * Similar ao token web, mas retorna o token e expiração em vez de criar cookie.
 * Usado para autenticação de apps que não suportam cookies.
 * Inclui campo 'origem' para identificar aplicativo.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CONFIG Configurações do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param int $params['id_usuarios'] ID do usuário (obrigatório).
 * 
 * @return array|false Array com 'token' e 'expiration', ou false se inválido.
 */
function usuario_app_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetro obrigatório
	if(isset($id_usuarios)){
		// ===== Define expiração específica para apps
		$expiration = time() + $_CONFIG['app-token-lifetime'];
		
		// ===== Carrega chave pública para assinar JWT
		$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
		
		$fp = fopen($keyPublicPath,"r");
		$chavePublica = fread($fp,8192);
		fclose($fp);
		
		// ===== Gera identificadores únicos do token
		gestor_incluir_biblioteca('seguranca');
		$tokenPubId = seguranca_token_aleatorio(32);
		
		// Hash HMAC para validação adicional
		$pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Cria token JWT
		$token = usuario_gerar_jwt(Array(
			'host' => $_SERVER['SERVER_NAME'],
			'expiration' => $expiration,
			'chavePublica' => $chavePublica,
			'pubID' => $tokenPubId,
		));
		
		// ===== Obtém IP do usuário para auditoria
		gestor_incluir_biblioteca('ip');
		
		$ip = ip_get();
		
		// ===== Salva token no banco com origem do app
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
		
		// Retorna token e expiração para o app
		return Array(
			'token' => $token,
			'expiration' => $expiration,
		);
	} else {
		return false;
	}
}

/**
 * Gerencia autorizações provisórias de usuários via sessão.
 *
 * Sistema de autenticação temporária para operações sensíveis.
 * Permite validar, invalidar, verificar ou mostrar modal de confirmação
 * quando autorização expirou ou não existe.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CONFIG Configurações do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param bool $params['verificar'] Retorna true/false se autorização existe e é válida (opcional).
 * @param bool $params['validar'] Cria nova autorização provisória (opcional).
 * @param bool $params['invalidar'] Remove autorização provisória (opcional).
 * @param array $params['verificarModal'] Verifica autorização e mostra modal se inválida (opcional).
 * @param string $params['verificarModal']['cancelarUrl'] URL de cancelamento (opcional).
 * @param string $params['verificarModal']['confirmarUrl'] URL de confirmação (opcional).
 * @param string $params['verificarModal']['autorizadoUrl'] URL de redirecionamento após autorização (opcional).
 * @param string $params['verificarModal']['autorizadoUrlQuerystring'] Query string adicional (opcional).
 * 
 * @return bool|void Retorna bool se 'verificar' está definido, void caso contrário.
 */
function usuario_autorizacao_provisoria($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar: cria nova autorização com timestamp atual
	if(isset($validar)){
		gestor_sessao_variavel('usuario-autorizacao-provisoria',time());
	}
	
	// ===== Invalidar: remove autorização da sessão
	if(isset($invalidar)){
		gestor_sessao_variavel_del('usuario-autorizacao-provisoria');
	}
	
	// ===== Verificar: retorna true/false se autorização é válida
	if(isset($verificar)){
		if(existe(gestor_sessao_variavel('usuario-autorizacao-provisoria'))){
			// Verifica se autorização expirou
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
	
	// ===== VerificarModal: mostra modal de autorização se inválida
	if(isset($verificarModal)){
		// Verifica validade da autorização
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
		
		// Se não é válida, carrega e configura modal de autorização
		if(!$valido){
			$pagina = gestor_componente(Array(
				'id' => 'interface-formulario-autorizacao-provisoria',
			));
			
			// Define URLs de cancelamento
			if(isset($verificarModal['cancelarUrl'])){
				$cancelarUrl = $_GESTOR['url-raiz'] . $verificarModal['cancelarUrl'];
			} else {
				$cancelarUrl = $_GESTOR['url-raiz'] . 'dashboard/';
			}
			
			// Define URLs de confirmação
			if(isset($verificarModal['confirmarUrl'])){
				$confirmarUrl = $_GESTOR['url-raiz'] . $verificarModal['confirmarUrl'];
			} else {
				$confirmarUrl = $_GESTOR['url-raiz'] . 'restrict-area/';
			}
			
			// Define URLs de redirecionamento pós-autorização
			if(isset($verificarModal['autorizadoUrl'])){
				$autorizadoUrl = '?redirect='.urlencode($verificarModal['autorizadoUrl']);
			} else {
				$autorizadoUrl = '?redirect='.urlencode('dashboard/');
			}
			
			// Adiciona query string adicional se fornecida
			if(isset($verificarModal['autorizadoUrlQuerystring'])){
				$autorizadoUrl .= '&'.$verificarModal['autorizadoUrlQuerystring'];
			}
			
			// Substitui variáveis no template do modal
			$pagina = modelo_var_troca($pagina,"#botao-cancelar-url#",$cancelarUrl);
			$pagina = modelo_var_troca($pagina,"#botao-confirmar-url#",$confirmarUrl.$autorizadoUrl);
			
			$pagina = modelo_var_troca($pagina,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-title')));
			$pagina = modelo_var_troca($pagina,"#mensagem#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-message')));
			$pagina = modelo_var_troca($pagina,"#botao-cancelar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-cancel')));
			$pagina = modelo_var_troca($pagina,"#botao-confirmar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'user-authorization-confirm')));
			
			// Adiciona modal ao início da página
			$_GESTOR['pagina'] = $pagina.$_GESTOR['pagina'];
		}
	}
}

// ===== Sessões ativas e dispositivos conectados (req-118)

/**
 * Identifica navegador, sistema operacional e tipo de dispositivo a partir do User-Agent.
 *
 * Função PURA — é ela que decide o texto que o usuário lê ao reconhecer (ou não) um acesso, e é a
 * única parte da auditoria de sessões testável sem banco.
 *
 * A ordem das comparações importa e não é alfabética: toda string de navegador baseado em Chromium
 * contém `Chrome`, e o Chrome do iOS/Android contém `Safari`. Por isso os derivados (Edge, Opera,
 * Samsung Internet) são testados ANTES do Chrome, e o Safari só é aceito quando nenhum deles casou.
 * Pelo mesmo motivo `Windows NT` é testado antes de qualquer coisa e `Android` antes de `Linux` —
 * todo Android é Linux no User-Agent.
 *
 * Navegador e sistema saem VAZIOS quando não reconhecidos, nunca com um rótulo tipo "Desconhecido":
 * a biblioteca é core e não tem idioma; quem exibe é que resolve a variável traduzida.
 *
 * @param string $userAgent Cabeçalho `User-Agent` bruto (pode vir vazio).
 *
 * @return array{navegador:string,sistema:string,dispositivo:string} Nomes crus, sem tradução.
 */
function usuario_user_agent_analisar($userAgent){
	$ua = is_string($userAgent) ? trim($userAgent) : '';

	if($ua === ''){
		return Array(
			'navegador' => '',
			'sistema' => '',
			'dispositivo' => 'desktop',
		);
	}

	// ===== Navegador (derivados do Chromium antes do Chrome; Safari por último)

	$navegador = '';

	$navegadores = Array(
		'Edge' => '/\bEdgA?\/|\bEdge\//i',
		'Opera' => '/\bOPR\/|\bOpera\b/i',
		'Samsung Internet' => '/\bSamsungBrowser\//i',
		'Vivaldi' => '/\bVivaldi\//i',
		'Brave' => '/\bBrave\//i',
		'Firefox' => '/\bFirefox\/|\bFxiOS\//i',
		'Chrome' => '/\bChrome\/|\bCriOS\/|\bChromium\//i',
		'Internet Explorer' => '/\bMSIE\b|\bTrident\//i',
		'Safari' => '/\bSafari\//i',
	);

	foreach($navegadores as $nome => $regex){
		if(preg_match($regex,$ua)){
			$navegador = $nome;
			break;
		}
	}

	// ===== Sistema operacional (Android antes de Linux; iOS antes de macOS)

	$sistema = '';

	$sistemas = Array(
		'Windows' => '/\bWindows NT\b|\bWindows Phone\b/i',
		'Android' => '/\bAndroid\b/i',
		'iOS' => '/\biPhone\b|\biPad\b|\biPod\b/i',
		'macOS' => '/\bMac OS X\b|\bMacintosh\b/i',
		'Chrome OS' => '/\bCrOS\b/i',
		'Linux' => '/\bLinux\b|\bX11\b/i',
	);

	foreach($sistemas as $nome => $regex){
		if(preg_match($regex,$ua)){
			$sistema = $nome;
			break;
		}
	}

	// ===== Dispositivo

	if(preg_match('/\biPad\b|\bTablet\b/i',$ua) || (preg_match('/\bAndroid\b/i',$ua) && !preg_match('/\bMobile\b/i',$ua))){
		$dispositivo = 'tablet';
	} else if(preg_match('/\bMobi\b|\bMobile\b|\biPhone\b|\biPod\b|\bWindows Phone\b/i',$ua)){
		$dispositivo = 'mobile';
	} else {
		$dispositivo = 'desktop';
	}

	return Array(
		'navegador' => $navegador,
		'sistema' => $sistema,
		'dispositivo' => $dispositivo,
	);
}

/**
 * Normaliza uma linha de `usuarios_tokens` para exibição no painel de sessões.
 *
 * Função PURA. Separada da consulta de propósito: é aqui que se decide qual sessão é marcada como
 * "este dispositivo" — comparação que, errada, faria o usuário revogar o próprio acesso achando que
 * derrubava outro.
 *
 * @param array $registro Linha de `usuarios_tokens` (`pubID`, `ip`, `user_agent`, `expiration`,
 *                        `data_criacao`, `origem`).
 * @param string|null $tokenAtual `pubID` do token da requisição corrente.
 *
 * @return array Sessão normalizada, com `atual`, `navegador`, `sistema`, `dispositivo` e `sessao`.
 */
function usuario_sessao_formatar($registro, $tokenAtual = null){
	$registro = is_array($registro) ? $registro : Array();

	$pubID = isset($registro['pubID']) ? (string)$registro['pubID'] : '';
	$userAgent = isset($registro['user_agent']) ? (string)$registro['user_agent'] : '';
	$expiration = isset($registro['expiration']) ? (int)$registro['expiration'] : 0;

	$agente = usuario_user_agent_analisar($userAgent);

	return Array(
		'pubID' => $pubID,
		'ip' => isset($registro['ip']) && $registro['ip'] !== null ? (string)$registro['ip'] : '',
		'user_agent' => $userAgent,
		'navegador' => $agente['navegador'],
		'sistema' => $agente['sistema'],
		'dispositivo' => $agente['dispositivo'],
		'origem' => isset($registro['origem']) && $registro['origem'] !== null ? (string)$registro['origem'] : '',
		'data_criacao' => isset($registro['data_criacao']) && $registro['data_criacao'] !== null ? (string)$registro['data_criacao'] : '',
		'expiration' => $expiration,
		// expiration 0 é cookie de sessão (morre ao fechar o navegador), não token expirado.
		'sessao' => ($expiration === 0),
		// Sem token corrente NENHUMA linha é marcada como atual: marcar a errada é pior que não marcar.
		'atual' => ($pubID !== '' && $tokenAtual !== null && $tokenAtual !== '' && hash_equals((string)$tokenAtual,$pubID)),
	);
}

/**
 * Lista as sessões ativas de um usuário, já normalizadas para exibição.
 *
 * @global array $_GESTOR Sistema global com configurações.
 *
 * @param int $id_usuario ID do usuário dono das sessões.
 * @param string|null $token_atual_pubID `pubID` do token da requisição corrente (opcional).
 *
 * @return array Lista de sessões (mais recentes primeiro); vazia quando não há usuário válido.
 */
function usuario_sessoes_listar($id_usuario, $token_atual_pubID = null){
	$id_usuario = (int)$id_usuario;

	if($id_usuario <= 0) return Array();

	$registros = banco_select(Array(
		'tabela' => 'usuarios_tokens',
		'campos' => Array(
			'pubID',
			'ip',
			'user_agent',
			'origem',
			'expiration',
			'data_criacao',
		),
		'extra' => "WHERE id_usuarios='".$id_usuario."' ORDER BY data_criacao DESC",
	));

	if(!is_array($registros)) return Array();

	$sessoes = Array();

	foreach($registros as $registro){
		if(!is_array($registro)) continue;
		$sessoes[] = usuario_sessao_formatar($registro,$token_atual_pubID);
	}

	return $sessoes;
}

/**
 * Revoga uma sessão específica do usuário informado.
 *
 * O `id_usuarios` entra no WHERE junto do `pubID`: o identificador chega do cliente e, sozinho,
 * permitiria derrubar a sessão de outro usuário.
 *
 * @param string $pubID Identificador público do token a remover.
 * @param int $id_usuario ID do usuário dono do token.
 *
 * @return bool True quando o comando foi emitido; false quando os parâmetros são inválidos.
 */
function usuario_sessao_revogar($pubID,$id_usuario){
	$pubID = is_string($pubID) ? trim($pubID) : '';
	$id_usuario = (int)$id_usuario;

	if($pubID === '' || $id_usuario <= 0) return false;

	banco_delete
	(
		"usuarios_tokens",
		"WHERE pubID='".banco_escape_field($pubID)."' AND id_usuarios='".$id_usuario."'"
	);

	return true;
}

/**
 * Encerra todas as sessões do usuário, exceto a da requisição corrente.
 *
 * Exige o token atual justamente para não deixar o usuário sem acesso nenhum: sem ele, a operação
 * seria um logout global disfarçado de "desconectar os outros dispositivos".
 *
 * @param string $token_atual_pubID `pubID` do token que deve ser preservado.
 * @param int $id_usuario ID do usuário dono dos tokens.
 *
 * @return bool True quando o comando foi emitido; false quando os parâmetros são inválidos.
 */
function usuario_sessoes_revogar_outras($token_atual_pubID,$id_usuario){
	$token_atual_pubID = is_string($token_atual_pubID) ? trim($token_atual_pubID) : '';
	$id_usuario = (int)$id_usuario;

	if($token_atual_pubID === '' || $id_usuario <= 0) return false;

	banco_delete
	(
		"usuarios_tokens",
		"WHERE id_usuarios='".$id_usuario."' AND pubID!='".banco_escape_field($token_atual_pubID)."'"
	);

	return true;
}

/**
 * Obtém dados do usuário do host atual.
 *
 * Retorna informações do usuário logado no host, com cache em $_USUARIO.
 * Se usuário não identificado, retorna dados de usuário anônimo.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_USUARIO Dados do usuário em cache.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['campo'] Nome do campo específico a retornar (opcional, retorna todos se omitido).
 * @param int $params['id_hosts_usuarios'] ID do usuário a buscar (opcional, usa usuário atual se omitido).
 * 
 * @return mixed|array Valor do campo ou array com todos os dados do usuário.
 */
function usuario_host_dados($params = false){
	global $_GESTOR;
	global $_USUARIO;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;

	// ===== Determina qual usuário buscar
	if(isset($id_hosts_usuarios)){
		$usuario_id = (existe($id_hosts_usuarios) ? $id_hosts_usuarios : null);
	} else {
		$usuario_id = null;
	}
	
	// ===== Se não há usuário, retorna dados anônimos
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
		// ===== Busca dados do usuário do banco (com cache)
		if(!isset($_USUARIO['host-dados'])){
			$hosts_usuarios = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_usuarios',
				'campos' => '*',
				'extra' => 
					"WHERE id_hosts_usuarios='".$usuario_id."'"
					." AND id_hosts='".$_GESTOR['host-id']."'"
			));
			
			// Armazena em cache
			$_USUARIO['host-dados'] = $hosts_usuarios;
		}
		
		// Retorna campo específico ou todos os dados
		if(isset($campo)){
			return $_USUARIO['host-dados'][$campo];
		} else {
			return $_USUARIO['host-dados'];
		}
	}
}

?>
