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
 * @version 1.3.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-usuario']							=	Array(
	'versao' => '1.3.0',
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
		// REQ-297: última barreira de todas as vias de login web (senha, 2FA, social e distribuído).
		if(usuario_hierarquia_bloqueado((int)$id_usuarios)) return false;

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
		gestor_cookie_emitir($_CONFIG['cookie-authname'], $token, [
			'expires' => $expiration,
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => gestor_cookie_is_secure(), // Apenas HTTPS
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
			gestor_cookie_emitir($_CONFIG['cookie-authprofile'], hash('sha256', $perfil_usuario), [
				'expires' => $expiration,
				'path' => '/',
				'domain' => $_SERVER['SERVER_NAME'],
				'secure' => gestor_cookie_is_secure(), // Apenas HTTPS
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
			// req-118: a trava de credenciais também precisa existir na base Tailwind — sem a
			// variante, a página em Tailwind puro receberia um modal Fomantic sem CSS nem JS, ou
			// seja, um bloqueio de segurança invisível ao usuário. A guarda existe porque esta
			// biblioteca também é carregada em contexto público, onde `interface` pode não estar.
			$componenteAutorizacao = 'interface-formulario-autorizacao-provisoria';

			if(function_exists('interface_componente_variante')){
				$componenteAutorizacao = interface_componente_variante($componenteAutorizacao);
			}

			$pagina = gestor_componente(Array(
				'id' => $componenteAutorizacao,
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

// ===== Personal Access Tokens e códigos de recuperação (req-119)

/** Prefixo que identifica um Personal Access Token deste sistema. */
define('USUARIO_API_TOKEN_PREFIXO', 'c2f_pat_');

/**
 * Diz se o schema já suporta Personal Access Tokens (req-119 / BATCH-122).
 *
 * O código chega por arquivos e o schema por migração; as duas coisas não pousam juntas em toda
 * instalação. Enquanto a tabela não existir, a funcionalidade inteira se comporta como se não
 * estivesse instalada — e nenhuma tela que já funcionava quebra.
 *
 * @return bool
 */
function usuario_api_tokens_disponivel(){
	// Falha fechado também quando o detector não está carregado: sem meio de confirmar o schema, a
	// funcionalidade nova não é oferecida.
	if(!function_exists('gestor_schema_tabela_existe')) return false;

	return gestor_schema_tabela_existe('usuarios_api_tokens');
}

/**
 * Diz se o schema já suporta códigos de recuperação de 2FA (req-119 / BATCH-122).
 *
 * Sem a coluna, o 2FA continua funcionando normalmente: apenas não há códigos para gerar nem para
 * resgatar. Perder o 2FA inteiro por causa de uma coluna ausente seria muito pior.
 *
 * @return bool
 */
function usuario_recovery_codes_disponivel(){
	if(!function_exists('gestor_schema_campo_existe')) return false;

	return gestor_schema_campo_existe('two_factor_recovery_codes','usuarios');
}

/**
 * Diz se uma string TEM O FORMATO de um Personal Access Token.
 *
 * Função PURA. É o desempate entre PAT e token OAuth 2.0 no mesmo cabeçalho `Authorization: Bearer`:
 * sem ela, todo PAT passaria pelo validador de JWT, falharia na decodificação e o usuário receberia
 * "token inválido" sem nenhuma pista do motivo.
 *
 * Não valida o token — só o formato. Quem decide se ele vale é `usuario_api_token_validar()`.
 *
 * @param string $token Valor bruto recebido no cabeçalho.
 *
 * @return bool
 */
function usuario_api_token_formato($token){
	if(!is_string($token)) return false;

	$prefixo = USUARIO_API_TOKEN_PREFIXO;

	return (strncmp($token,$prefixo,strlen($prefixo)) === 0)
		&& (strlen($token) > strlen($prefixo));
}

/**
 * Calcula o hash de armazenamento de um Personal Access Token.
 *
 * Função PURA. SHA-256 sem sal por decisão explícita: diferente de senha, o token é um segredo de
 * 64 hexadecimais gerado por CSPRNG — não há dicionário a proteger, e a busca precisa ser feita PELO
 * hash (um sal por linha obrigaria a varrer a tabela inteira a cada requisição de API).
 *
 * @param string $token Token em texto puro.
 *
 * @return string Hash hexadecimal de 64 caracteres.
 */
function usuario_api_token_hash($token){
	return hash('sha256',(string)$token);
}

/**
 * Extrai o prefixo exibível de um token.
 *
 * Função PURA. É o que permite ao usuário reconhecer a chave na listagem sem que o segredo precise
 * ser recuperável.
 *
 * @param string $token Token em texto puro.
 *
 * @return string Prefixo do sistema mais os 8 primeiros caracteres da parte aleatória.
 */
function usuario_api_token_prefixo($token){
	$token = (string)$token;
	$prefixo = USUARIO_API_TOKEN_PREFIXO;
	$corpo = (strncmp($token,$prefixo,strlen($prefixo)) === 0) ? substr($token,strlen($prefixo)) : $token;

	return $prefixo.substr($corpo,0,8);
}

/**
 * Decide se um token registrado está utilizável AGORA.
 *
 * Função PURA — separada da consulta porque é a regra que a API aplica a cada requisição, e porque
 * o desfecho precisa distinguir revogado de expirado (o usuário resolve os dois de formas
 * diferentes).
 *
 * @param array $registro Linha de `usuarios_api_tokens` (`status`, `expiracao`).
 * @param int|null $agora Timestamp de referência; omitido usa `time()`.
 *
 * @return string `ativo`, `revogado` ou `expirado`.
 */
function usuario_api_token_situacao($registro,$agora = null){
	$registro = is_array($registro) ? $registro : Array();
	$agora = ($agora === null) ? time() : (int)$agora;

	$status = isset($registro['status']) ? (string)$registro['status'] : '';

	if($status === 'R') return 'revogado';
	if($status !== 'A') return 'revogado';

	$expiracao = isset($registro['expiracao']) ? (string)$registro['expiracao'] : '';

	// Expiração vazia é token perpétuo — decisão do usuário no momento da criação, não um defeito.
	if($expiracao === '') return 'ativo';

	$limite = strtotime($expiracao);

	if($limite === false) return 'ativo';

	return ($limite < $agora) ? 'expirado' : 'ativo';
}

/**
 * Gera um Personal Access Token para o usuário.
 *
 * O token em texto puro é devolvido UMA ÚNICA VEZ: o banco guarda apenas o hash, então não há
 * caminho de recuperação — nem para o próprio usuário, nem para quem tiver acesso ao banco.
 *
 * @param int $id_usuario Dono do token.
 * @param string $nome Identificador amigável.
 * @param array $escopos Permissões autorizadas.
 * @param int|null $dias_expiracao Validade em dias; `null` para token sem expiração.
 *
 * @return array|false `['token' => …, 'prefixo' => …, 'expiracao' => …]` ou false se inválido.
 */
function usuario_api_token_gerar($id_usuario,$nome,$escopos = Array(),$dias_expiracao = null){
	$id_usuario = (int)$id_usuario;
	$nome = trim((string)$nome);

	if($id_usuario <= 0 || $nome === '') return false;
	if(!usuario_api_tokens_disponivel()) return false;

	gestor_incluir_biblioteca('seguranca');

	// CSPRNG (contrato do BATCH-107): token de acesso previsível é credencial pública.
	$token = USUARIO_API_TOKEN_PREFIXO.seguranca_token_aleatorio(32);

	$expiracao = null;

	if($dias_expiracao !== null && (int)$dias_expiracao > 0){
		$expiracao = date('Y-m-d H:i:s',time() + ((int)$dias_expiracao * 86400));
	}

	$escopos = array_values(array_filter((array)$escopos,function($e){ return is_string($e) && trim($e) !== ''; }));

	$campos = null; $campo_sem_aspas_simples = null;

	$campo_nome = "id_usuarios"; $campo_valor = $id_usuario; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "nome"; $campo_valor = banco_escape_field($nome); $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "token_prefix"; $campo_valor = usuario_api_token_prefixo($token); $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "token_hash"; $campo_valor = usuario_api_token_hash($token); $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	// JSON ASCII-safe: a conexão do gestor é `utf8` de 3 bytes e um escopo com emoji truncaria a linha.
	$campo_nome = "escopos"; $campo_valor = banco_escape_field(json_encode($escopos)); $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "status"; $campo_valor = 'A'; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; $campos[] = Array($campo_nome,$campo_valor,true);

	if($expiracao !== null){
		$campo_nome = "expiracao"; $campo_valor = $expiracao; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	}

	banco_insert_name($campos,"usuarios_api_tokens");

	return Array(
		'token' => $token,
		'prefixo' => usuario_api_token_prefixo($token),
		'expiracao' => $expiracao,
	);
}

/**
 * Valida um Personal Access Token e registra o uso.
 *
 * O retorno é COMPATÍVEL com `oauth2_validar_token()` de propósito: a API já sabe consumir aquele
 * formato, e divergir aqui obrigaria cada endpoint a tratar dois contratos.
 *
 * @param string $token_puro Token recebido no cabeçalho.
 *
 * @return array|false Dados do usuário autenticado ou false.
 */
function usuario_api_token_validar($token_puro){
	if(!usuario_api_token_formato($token_puro)) return false;
	if(!usuario_api_tokens_disponivel()) return false;

	$registros = banco_select(Array(
		'tabela' => 'usuarios_api_tokens',
		'campos' => Array(
			'id_usuarios_api_tokens',
			'id_usuarios',
			'escopos',
			'expiracao',
			'status',
		),
		'extra' => "WHERE token_hash='".banco_escape_field(usuario_api_token_hash($token_puro))."'",
	));

	if(!is_array($registros) || !isset($registros[0])) return false;

	$registro = $registros[0];

	if(usuario_api_token_situacao($registro) !== 'ativo') return false;

	$usuarios = banco_select(Array(
		'tabela' => 'usuarios',
		'campos' => Array(
			'id_usuarios',
			'nome',
			'email',
			'usuario',
			'id_usuarios_perfis',
		),
		'extra' => "WHERE id_usuarios='".(int)$registro['id_usuarios']."' AND status='A'",
	));

	// Usuário inativo ou removido derruba o token junto: a credencial pertence à conta, não a si mesma.
	if(!is_array($usuarios) || !isset($usuarios[0])) return false;
	if(usuario_hierarquia_bloqueado((int)$usuarios[0]['id_usuarios'])) return false;

	banco_update_campo('ultimo_uso','NOW()',true);
	banco_update_executar('usuarios_api_tokens',"WHERE id_usuarios_api_tokens='".(int)$registro['id_usuarios_api_tokens']."'");

	$escopos = json_decode((string)($registro['escopos'] ?? '[]'),true);

	return Array(
		'id_usuarios' => $usuarios[0]['id_usuarios'],
		'nome' => $usuarios[0]['nome'],
		'email' => $usuarios[0]['email'],
		'usuario' => $usuarios[0]['usuario'],
		'id_usuarios_perfis' => $usuarios[0]['id_usuarios_perfis'],
		'scope' => is_array($escopos) ? implode(' ',$escopos) : '',
		'api_token' => true,
	);
}

/**
 * Revoga um Personal Access Token do usuário informado.
 *
 * O `id_usuarios` entra no WHERE porque o id do token viaja pelo formulário: sem ele, um id
 * adivinhado revogaria a chave de outra conta.
 *
 * @param int $id_token Identificador do token.
 * @param int $id_usuario Dono do token.
 *
 * @return bool True quando o comando foi emitido.
 */
function usuario_api_token_revogar($id_token,$id_usuario){
	$id_token = (int)$id_token;
	$id_usuario = (int)$id_usuario;

	if($id_token <= 0 || $id_usuario <= 0) return false;
	if(!usuario_api_tokens_disponivel()) return false;

	// Revogar em vez de apagar preserva a auditoria: a linha continua contando quando o token foi
	// criado e quando foi usado pela última vez.
	banco_update_campo('status','R');
	banco_update_campo('data_modificacao','NOW()',true);
	banco_update_executar(
		'usuarios_api_tokens',
		"WHERE id_usuarios_api_tokens='".$id_token."' AND id_usuarios='".$id_usuario."'"
	);

	return true;
}

/**
 * Lista os tokens de um usuário, já anotados com a situação corrente.
 *
 * @param int $id_usuario Dono dos tokens.
 *
 * @return array Lista de tokens (mais recentes primeiro).
 */
function usuario_api_tokens_listar($id_usuario){
	$id_usuario = (int)$id_usuario;

	if($id_usuario <= 0) return Array();
	if(!usuario_api_tokens_disponivel()) return Array();

	$registros = banco_select(Array(
		'tabela' => 'usuarios_api_tokens',
		'campos' => Array(
			'id_usuarios_api_tokens',
			'nome',
			'token_prefix',
			'escopos',
			'expiracao',
			'ultimo_uso',
			'status',
			'data_criacao',
		),
		'extra' => "WHERE id_usuarios='".$id_usuario."' ORDER BY data_criacao DESC",
	));

	if(!is_array($registros)) return Array();

	$tokens = Array();

	foreach($registros as $registro){
		if(!is_array($registro)) continue;

		$escopos = json_decode((string)($registro['escopos'] ?? '[]'),true);

		$registro['escopos'] = is_array($escopos) ? $escopos : Array();
		$registro['situacao'] = usuario_api_token_situacao($registro);

		$tokens[] = $registro;
	}

	return $tokens;
}

// ===== Códigos de recuperação do 2FA (req-119)

/**
 * Gera códigos de recuperação em texto puro.
 *
 * Função PURA quanto ao formato (a aleatoriedade vem do CSPRNG). O alfabeto exclui `0`, `O`, `1`,
 * `I` e `L`: o código é feito para ser copiado à mão de um papel, e esses caracteres são os que o
 * usuário digita errado.
 *
 * @param int $quantidade Quantos códigos gerar.
 *
 * @return array Lista de códigos no formato `XXXX-XXXX`.
 */
function usuario_recovery_codes_gerar($quantidade = 10){
	$quantidade = (int)$quantidade;
	if($quantidade <= 0) return Array();

	$alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
	$limite = strlen($alfabeto) - 1;
	$codigos = Array();

	for($i = 0; $i < $quantidade; $i++){
		$bruto = '';
		for($j = 0; $j < 8; $j++){
			$bruto .= $alfabeto[random_int(0,$limite)];
		}
		$codigos[] = substr($bruto,0,4).'-'.substr($bruto,4,4);
	}

	return $codigos;
}

/**
 * Normaliza um código de recuperação para comparação.
 *
 * Função PURA. O usuário digita o código lendo de um papel: hífen, espaço e caixa variam, e nenhuma
 * dessas variações deveria custar uma das dez chances que ele tem.
 *
 * @param string $codigo Código digitado.
 *
 * @return string Código só com os caracteres significativos, em caixa alta.
 */
function usuario_recovery_code_normalizar($codigo){
	return strtoupper(preg_replace('/[^A-Za-z0-9]/','',(string)$codigo));
}

/**
 * Calcula o hash de armazenamento de um código de recuperação.
 *
 * Função PURA sobre a forma normalizada — gravar o hash do texto digitado faria `abcd-1234` e
 * `ABCD1234` virarem códigos diferentes.
 *
 * @param string $codigo Código em texto puro.
 *
 * @return string Hash hexadecimal.
 */
function usuario_recovery_code_hash($codigo){
	return hash('sha256',usuario_recovery_code_normalizar($codigo));
}

/**
 * Consome um código de recuperação de uma lista de hashes.
 *
 * Função PURA — é o coração do resgate de acesso e a única parte que decide se um código é de uso
 * único de verdade. Devolve a lista SEM o código usado; quem persiste é o chamador.
 *
 * @param string $codigo Código digitado pelo usuário.
 * @param array $hashes Lista de hashes ainda válidos.
 *
 * @return array{valido:bool,restantes:array}
 */
function usuario_recovery_code_consumir($codigo,$hashes){
	$hashes = array_values(array_filter((array)$hashes,function($h){ return is_string($h) && $h !== ''; }));
	$normalizado = usuario_recovery_code_normalizar($codigo);

	if($normalizado === ''){
		return Array('valido' => false, 'restantes' => $hashes);
	}

	$alvo = usuario_recovery_code_hash($codigo);
	$restantes = Array();
	$valido = false;

	foreach($hashes as $hash){
		// hash_equals em vez de === : a comparação acontece por tentativa de login.
		if(!$valido && hash_equals($hash,$alvo)){
			$valido = true;
			continue;
		}
		$restantes[] = $hash;
	}

	return Array('valido' => $valido, 'restantes' => $restantes);
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

// ===== Hierarquia de usuários: titular e sub-usuários (REQ-297)

/**
 * Resolve a organização (tenant) de um usuário: o titular, ou o próprio usuário.
 *
 * É a chave de isolamento de dados para módulos e APIs. Conta sem titular devolve o próprio id, o
 * que mantém o contrato `organization_id = user_id` dos módulos existentes. A hierarquia tem um só
 * nível: um sub-usuário nunca é titular (ver `usuario_equipe_titular()`).
 *
 * Sem `id_usuarios_pai` no banco (atualização antes da migração) a consulta devolve false e a
 * resposta é o próprio usuário.
 *
 * @global array $_GESTOR Sistema global; `usuario-id` é o usuário da sessão ou do token.
 *
 * @param int|null $id_usuario Usuário consultado; null usa o usuário autenticado.
 *
 * @return int Id da organização, ou 0 sem usuário autenticado.
 */
function usuario_organizacao_id(?int $id_usuario = null): int {
	global $_GESTOR;

	$id = $id_usuario ?? (int)($_GESTOR['usuario-id'] ?? 0);

	if($id <= 0) return 0;

	if(!isset($_GESTOR['usuario-organizacao'][$id])){
		$usuario = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios',
			'campos' => Array('id_usuarios_pai'),
			'extra' => "WHERE id_usuarios='".$id."'",
		));

		$_GESTOR['usuario-organizacao'][$id] = !empty($usuario['id_usuarios_pai']) ? (int)$usuario['id_usuarios_pai'] : $id;
	}

	return $_GESTOR['usuario-organizacao'][$id];
}

/**
 * Diz se a hierarquia impede o acesso de um usuário.
 *
 * Só sub-usuários são afetados: conta própria segue as regras de sempre. O bloqueio é calculado a
 * cada verificação, sem alterar o status do filho, então a conta volta sozinha quando a condição
 * deixa de existir. Bloqueiam: o próprio filho desativado, titular inativo e equipe desligada no
 * perfil do titular (chave desligada ou limite 0), mesmo com membros cadastrados. O status do filho
 * entra aqui porque o guardião de sessão web não o confere.
 *
 * @param int $id_usuario Usuário consultado.
 *
 * @return bool True quando o acesso deve ser recusado.
 */
function usuario_hierarquia_bloqueado(int $id_usuario): bool {
	$usuario = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('id_usuarios_pai', 'status'),
		'extra' => "WHERE id_usuarios='".$id_usuario."'",
	));

	if(!$usuario || empty($usuario['id_usuarios_pai'])) return false;
	if($usuario['status'] !== 'A') return true;

	$id_titular = (int)$usuario['id_usuarios_pai'];

	$titular = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('status'),
		'extra' => "WHERE id_usuarios='".$id_titular."'",
	));

	if(!$titular || $titular['status'] !== 'A') return true;

	return !usuario_equipe_cota($id_titular)['ativa'];
}

/**
 * Lê a conta que pode gerir uma equipe: ativa e que não seja sub-usuária.
 *
 * @param int $id_usuario Usuário consultado.
 *
 * @return array|null Dados do titular ou null.
 */
function usuario_equipe_titular(int $id_usuario): ?array {
	$titular = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('id_usuarios', 'id_usuarios_perfis', 'id_usuarios_pai', 'id_hosts', 'status'),
		'extra' => "WHERE id_usuarios='".$id_usuario."'",
	));

	if(!$titular || !empty($titular['id_usuarios_pai']) || $titular['status'] !== 'A') return null;

	return $titular;
}

/**
 * Lê o perfil de uma conta, com a configuração de equipe.
 *
 * @param int $id_usuario Usuário dono do perfil.
 *
 * @return array|null Linha de `usuarios_perfis` (id, max_sub_users, sub_users_active) ou null.
 */
function usuario_equipe_perfil_da_conta(int $id_usuario): ?array {
	$usuario = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('id_usuarios_perfis'),
		'extra' => "WHERE id_usuarios='".$id_usuario."'",
	));

	if(empty($usuario['id_usuarios_perfis'])) return null;

	$perfil = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios_perfis',
		'campos' => Array('id', 'max_sub_users', 'sub_users_active'),
		'extra' => "WHERE id_usuarios_perfis='".(int)$usuario['id_usuarios_perfis']."'",
	));

	return $perfil ?: null;
}

/**
 * Lista os membros da equipe de um titular, sem os excluídos.
 *
 * @param int $id_titular Conta titular.
 *
 * @return array Linhas com id_usuarios, nome, email, status, id_usuarios_perfis e data_criacao.
 */
function usuario_equipe_membros(int $id_titular): array {
	$membros = banco_select(Array(
		'tabela' => 'usuarios',
		'campos' => Array('id_usuarios', 'nome', 'email', 'status', 'id_usuarios_perfis', 'data_criacao'),
		'extra' => "WHERE id_usuarios_pai='".$id_titular."' AND status!='D' ORDER BY nome ASC",
	));

	return is_array($membros) ? $membros : Array();
}

/**
 * Calcula a situação da equipe de um titular.
 *
 * A equipe só funciona com a chave do perfil ligada e limite diferente de 0. Só membros ativos
 * ocupam assento. Depois de uma troca para um plano menor os membros além do limite continuam
 * ativos: nada é desativado, apenas novos cadastros e ativações são recusados.
 *
 * @param int $id_titular Conta titular.
 *
 * @return array Chaves ativa, limite, usados, ilimitado e disponivel.
 */
function usuario_equipe_cota(int $id_titular): array {
	$perfil = usuario_equipe_perfil_da_conta($id_titular);

	$limite = (int)($perfil['max_sub_users'] ?? 0);
	$ativa = !empty($perfil['sub_users_active']) && $limite !== 0;
	$usados = count(array_filter(usuario_equipe_membros($id_titular), function($membro){ return $membro['status'] === 'A'; }));

	return Array(
		'ativa' => $ativa,
		'limite' => $limite,
		'usados' => $usados,
		'ilimitado' => $limite < 0,
		'disponivel' => $ativa && ($limite < 0 || $usados < $limite),
	);
}

/**
 * Diz se a gestão de equipe se aplica à conta.
 *
 * @param int $id_usuario Usuário consultado.
 *
 * @return bool True quando a conta é titular e a equipe está ligada no perfil dela.
 */
function usuario_equipe_habilitada(int $id_usuario): bool {
	return usuario_equipe_titular($id_usuario) !== null && usuario_equipe_cota($id_usuario)['ativa'];
}

/**
 * Monta o filtro SQL que limita um sub-usuário ao que o titular tem hoje.
 *
 * A permissão efetiva do membro é a do perfil dele E a do perfil do titular: quando o titular perde
 * um módulo, os membros perdem junto, sem precisar reescrever os perfis da equipe. Conta própria
 * recebe filtro vazio.
 *
 * @param int $id_usuario Usuário consultado.
 * @param string $coluna 'modulo' ou 'operacao'.
 *
 * @return string Trecho a concatenar no WHERE da consulta de permissão.
 */
function usuario_equipe_permissao_sql(int $id_usuario, string $coluna = 'modulo'): string {
	$id_titular = usuario_organizacao_id($id_usuario);

	if(!$id_titular || $id_titular === $id_usuario) return '';

	$perfil = usuario_equipe_perfil_da_conta($id_titular);

	if(!$perfil) return ' AND 1=0';

	$tabela = $coluna === 'operacao' ? 'usuarios_perfis_modulos_operacoes' : 'usuarios_perfis_modulos';

	return " AND ".$coluna." IN (SELECT ".$coluna." FROM ".$tabela." WHERE perfil='".banco_escape_field($perfil['id'])."')";
}

/**
 * Lista o que o titular pode distribuir aos perfis da equipe: só o que ele próprio recebeu.
 *
 * `equipe` nunca entra, porque sub-usuário não gere equipe. `perfil-usuario` fica fora da escolha:
 * todo membro recebe, para cuidar da própria senha.
 *
 * @global array $_GESTOR Sistema global; `linguagem-codigo` escolhe os nomes.
 *
 * @param int $id_titular Conta titular.
 *
 * @return array Chaves `modulos` (id => nome, modulo_grupo_id) e `operacoes` (id => nome, modulo_id).
 */
function usuario_equipe_permissoes_disponiveis(int $id_titular): array {
	global $_GESTOR;

	$disponiveis = Array('modulos' => Array(), 'operacoes' => Array());
	$perfil = usuario_equipe_perfil_da_conta($id_titular);

	if(!$perfil) return $disponiveis;

	$perfil_sql = banco_escape_field($perfil['id']);
	$idioma = banco_escape_field($_GESTOR['linguagem-codigo'] ?? 'pt-br');

	$linhas = banco_select(Array(
		'tabela' => 'modulos',
		'campos' => Array('id', 'nome', 'modulo_grupo_id'),
		'extra' => "WHERE status='A' AND language='".$idioma."' AND id NOT IN ('equipe','perfil-usuario')"
			." AND id IN (SELECT modulo FROM usuarios_perfis_modulos WHERE perfil='".$perfil_sql."') ORDER BY nome ASC",
	));

	foreach((is_array($linhas) ? $linhas : Array()) as $linha){
		$disponiveis['modulos'][$linha['id']] = $linha;
	}

	$linhas = banco_select(Array(
		'tabela' => 'modulos_operacoes',
		'campos' => Array('id', 'nome', 'modulo_id'),
		'extra' => "WHERE status='A' AND language='".$idioma."'"
			." AND id IN (SELECT operacao FROM usuarios_perfis_modulos_operacoes WHERE perfil='".$perfil_sql."') ORDER BY nome ASC",
	));

	foreach((is_array($linhas) ? $linhas : Array()) as $linha){
		if(isset($disponiveis['modulos'][$linha['modulo_id']])) $disponiveis['operacoes'][$linha['id']] = $linha;
	}

	return $disponiveis;
}

/**
 * Lista os perfis de equipe de um titular, com módulos e operações marcados.
 *
 * @param int $id_titular Conta titular.
 *
 * @return array Linhas com id_usuarios_perfis, id, nome, modulos e operacoes.
 */
function usuario_equipe_perfis(int $id_titular): array {
	$perfis = banco_select(Array(
		'tabela' => 'usuarios_perfis',
		'campos' => Array('id_usuarios_perfis', 'id', 'nome'),
		'extra' => "WHERE id_usuarios_dono='".$id_titular."' AND status!='D' ORDER BY nome ASC",
	));

	$saida = Array();

	foreach((is_array($perfis) ? $perfis : Array()) as $perfil){
		$perfil_sql = banco_escape_field($perfil['id']);

		$modulos = banco_select(Array('tabela' => 'usuarios_perfis_modulos', 'campos' => Array('modulo'), 'extra' => "WHERE perfil='".$perfil_sql."'"));
		$operacoes = banco_select(Array('tabela' => 'usuarios_perfis_modulos_operacoes', 'campos' => Array('operacao'), 'extra' => "WHERE perfil='".$perfil_sql."'"));

		$perfil['modulos'] = array_column(is_array($modulos) ? $modulos : Array(), 'modulo');
		$perfil['operacoes'] = array_column(is_array($operacoes) ? $operacoes : Array(), 'operacao');
		$saida[] = $perfil;
	}

	return $saida;
}

/**
 * Procura um perfil de equipe pelo id numérico, só entre os do titular.
 *
 * @param int $id_titular Conta titular.
 * @param int $id_perfil Perfil procurado.
 *
 * @return array|null Linha do perfil ou null.
 */
function usuario_equipe_perfil(int $id_titular, int $id_perfil): ?array {
	$perfil = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios_perfis',
		'campos' => Array('id_usuarios_perfis', 'id', 'nome'),
		'extra' => "WHERE id_usuarios_perfis='".$id_perfil."' AND id_usuarios_dono='".$id_titular."' AND status!='D'",
	));

	return $perfil ?: null;
}

/**
 * Cria ou altera um perfil de equipe.
 *
 * O limite é conferido aqui, no servidor: pedido com módulo ou operação que o titular não tem é
 * recusado por inteiro, e não só filtrado, porque indica formulário adulterado.
 *
 * @param int $id_titular Conta titular.
 * @param array $dados Campos `id_perfil` (0 = novo), `nome`, `modulos` e `operacoes`.
 *
 * @return array `ok` true com `id_perfil`, ou `ok` false com `erro` (id de variável do módulo).
 */
function usuario_equipe_perfil_salvar(int $id_titular, array $dados): array {
	global $_GESTOR;

	if(!usuario_equipe_habilitada($id_titular)) return Array('ok' => false, 'erro' => 'team-not-allowed');

	$nome = trim((string)($dados['nome'] ?? ''));
	$id_perfil = (int)($dados['id_perfil'] ?? 0);
	$modulos = array_values(array_unique(array_map('strval', (array)($dados['modulos'] ?? Array()))));
	$operacoes = array_values(array_unique(array_map('strval', (array)($dados['operacoes'] ?? Array()))));

	if($nome === '' || mb_strlen($nome, 'UTF-8') > 255) return Array('ok' => false, 'erro' => 'team-profile-name-required');
	if(!$modulos) return Array('ok' => false, 'erro' => 'team-profile-modules-required');

	$disponiveis = usuario_equipe_permissoes_disponiveis($id_titular);

	if(array_diff($modulos, array_keys($disponiveis['modulos'])) || array_diff($operacoes, array_keys($disponiveis['operacoes']))){
		return Array('ok' => false, 'erro' => 'team-permission-denied');
	}

	$nome_sql = banco_escape_field($nome);

	if($id_perfil){
		$perfil = usuario_equipe_perfil($id_titular, $id_perfil);

		if(!$perfil) return Array('ok' => false, 'erro' => 'team-profile-not-found');

		if(banco_update("nome='".$nome_sql."',versao=versao+1,data_modificacao=NOW()", 'usuarios_perfis', "WHERE id_usuarios_perfis='".$id_perfil."'") === false){
			return Array('ok' => false, 'erro' => 'team-save-error');
		}

		$id = $perfil['id'];
	} else {
		$id = banco_identificador(Array(
			'id' => 'equipe '.$id_titular.' '.$nome_sql,
			'tabela' => Array(
				'nome' => 'usuarios_perfis',
				'campo' => 'id',
				'id_nome' => 'id_usuarios_perfis',
			),
		));

		$campos = null;
		$campos[] = Array('id_usuarios_dono', $id_titular, true);
		$campos[] = Array('nome', $nome_sql);
		$campos[] = Array('id', $id);
		$campos[] = Array('language', banco_escape_field($_GESTOR['linguagem-codigo'] ?? 'pt-br'));
		$campos[] = Array('status', 'A');
		$campos[] = Array('versao', 1, true);
		$campos[] = Array('data_criacao', 'NOW()', true);
		$campos[] = Array('data_modificacao', 'NOW()', true);

		if(banco_insert_name($campos, 'usuarios_perfis') === false) return Array('ok' => false, 'erro' => 'team-save-error');

		$id_perfil = (int)banco_last_id();
	}

	$id_sql = banco_escape_field($id);

	banco_delete('usuarios_perfis_modulos', "WHERE perfil='".$id_sql."'");
	banco_delete('usuarios_perfis_modulos_operacoes', "WHERE perfil='".$id_sql."'");

	foreach(array_merge($modulos, Array('perfil-usuario')) as $modulo){
		banco_insert_name(Array(Array('perfil', $id_sql), Array('modulo', banco_escape_field($modulo))), 'usuarios_perfis_modulos');
	}

	foreach($operacoes as $operacao){
		banco_insert_name(Array(Array('perfil', $id_sql), Array('operacao', banco_escape_field($operacao))), 'usuarios_perfis_modulos_operacoes');
	}

	return Array('ok' => true, 'id_perfil' => $id_perfil);
}

/**
 * Exclui um perfil de equipe que nenhum membro use.
 *
 * @param int $id_titular Conta titular.
 * @param int $id_perfil Perfil a excluir.
 *
 * @return array `ok` true, ou `ok` false com `erro` (id de variável do módulo).
 */
function usuario_equipe_perfil_excluir(int $id_titular, int $id_perfil): array {
	if(!usuario_equipe_habilitada($id_titular)) return Array('ok' => false, 'erro' => 'team-not-allowed');

	$perfil = usuario_equipe_perfil($id_titular, $id_perfil);

	if(!$perfil) return Array('ok' => false, 'erro' => 'team-profile-not-found');

	foreach(usuario_equipe_membros($id_titular) as $membro){
		if((int)$membro['id_usuarios_perfis'] === $id_perfil) return Array('ok' => false, 'erro' => 'team-profile-in-use');
	}

	if(banco_update("status='D',versao=versao+1,data_modificacao=NOW()", 'usuarios_perfis', "WHERE id_usuarios_perfis='".$id_perfil."'") === false){
		return Array('ok' => false, 'erro' => 'team-save-error');
	}

	$id_sql = banco_escape_field($perfil['id']);

	banco_delete('usuarios_perfis_modulos', "WHERE perfil='".$id_sql."'");
	banco_delete('usuarios_perfis_modulos_operacoes', "WHERE perfil='".$id_sql."'");

	return Array('ok' => true);
}

/**
 * Serializa as operações que ocupam assento de um titular.
 *
 * Sem isto, dois pedidos simultâneos contariam o mesmo assento livre e ultrapassariam a cota.
 * A trava é da conexão, e o MySQL a libera sozinho se o processo morrer.
 *
 * @param int $id_titular Conta titular.
 *
 * @return bool True quando a trava foi obtida.
 */
function usuario_equipe_travar(int $id_titular): bool {
	$resultado = banco_sql("SELECT GET_LOCK('c2f-equipe-".$id_titular."',5)");

	return !empty($resultado[0]) && (int)reset($resultado[0]) === 1;
}

/**
 * Libera a trava de `usuario_equipe_travar()`.
 *
 * @param int $id_titular Conta titular.
 *
 * @return void
 */
function usuario_equipe_destravar(int $id_titular): void {
	banco_query("SELECT RELEASE_LOCK('c2f-equipe-".$id_titular."')");
}

/**
 * Cria um sub-usuário na equipe do titular.
 *
 * O perfil precisa ser um perfil de equipe do próprio titular, então o membro nunca recebe mais do
 * que o titular tem. O login do membro é o e-mail.
 *
 * @param int $id_titular Conta titular.
 * @param array $dados Campos `nome`, `email`, `senha` e `id_perfil`.
 *
 * @return array `ok` true com `id_usuarios`, ou `ok` false com `erro` (id de variável do módulo).
 */
function usuario_equipe_membro_criar(int $id_titular, array $dados): array {
	$titular = usuario_equipe_titular($id_titular);

	if(!$titular || !usuario_equipe_cota($id_titular)['ativa']) return Array('ok' => false, 'erro' => 'team-not-allowed');

	$nome = trim((string)($dados['nome'] ?? ''));
	$email = trim((string)($dados['email'] ?? ''));
	$senha = (string)($dados['senha'] ?? '');
	$id_perfil = (int)($dados['id_perfil'] ?? 0);

	if($nome === '' || mb_strlen($nome, 'UTF-8') > 255) return Array('ok' => false, 'erro' => 'team-name-required');
	if(mb_strlen($email, 'UTF-8') > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return Array('ok' => false, 'erro' => 'team-email-invalid');
	if(strlen($senha) < 12) return Array('ok' => false, 'erro' => 'team-password-weak');
	if(!usuario_equipe_perfil($id_titular, $id_perfil)) return Array('ok' => false, 'erro' => 'team-profile-required');

	if(!usuario_equipe_travar($id_titular)) return Array('ok' => false, 'erro' => 'team-busy');

	try {
		if(!usuario_equipe_cota($id_titular)['disponivel']) return Array('ok' => false, 'erro' => 'team-quota-reached');

		$nome_sql = banco_escape_field($nome);
		$email_sql = banco_escape_field($email);

		$existente = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios',
			'campos' => Array('id_usuarios'),
			'extra' => "WHERE (usuario='".$email_sql."' OR email='".$email_sql."') AND status!='D'",
		));

		if($existente) return Array('ok' => false, 'erro' => 'team-email-taken');

		$id = banco_identificador(Array(
			'id' => $nome_sql,
			'tabela' => Array(
				'nome' => 'usuarios',
				'campo' => 'id',
				'id_nome' => 'id_usuarios',
			),
		));

		// O login compara a senha já escapada: o hash precisa nascer da mesma forma.
		$senha_hash = password_hash(banco_escape_field($senha), PASSWORD_ARGON2I, Array('cost' => 9));

		$nomes = explode(' ', preg_replace('/\s+/', ' ', $nome_sql));
		$primeiro_nome = array_shift($nomes);
		$ultimo_nome = array_pop($nomes);

		$campos = null;
		$campos[] = Array('id_usuarios_pai', (int)$titular['id_usuarios'], true);
		$campos[] = Array('id_usuarios_perfis', $id_perfil, true);
		if(!empty($titular['id_hosts'])) $campos[] = Array('id_hosts', (int)$titular['id_hosts'], true);
		$campos[] = Array('nome', $nome_sql);
		$campos[] = Array('nome_conta', $nome_sql);
		$campos[] = Array('id', $id);
		$campos[] = Array('usuario', $email_sql);
		$campos[] = Array('email', $email_sql);
		$campos[] = Array('senha', $senha_hash);
		$campos[] = Array('primeiro_nome', $primeiro_nome);
		if($nomes) $campos[] = Array('nome_do_meio', implode(' ', $nomes));
		if($ultimo_nome !== null) $campos[] = Array('ultimo_nome', $ultimo_nome);
		$campos[] = Array('status', 'A');
		$campos[] = Array('versao', 1, true);
		$campos[] = Array('data_criacao', 'NOW()', true);
		$campos[] = Array('data_modificacao', 'NOW()', true);

		if(banco_insert_name($campos, 'usuarios') === false) return Array('ok' => false, 'erro' => 'team-save-error');

		return Array('ok' => true, 'id_usuarios' => (int)banco_last_id());
	} finally {
		usuario_equipe_destravar($id_titular);
	}
}

/**
 * Troca o perfil de equipe de um membro.
 *
 * @param int $id_titular Conta titular.
 * @param int $id_membro Sub-usuário alvo.
 * @param int $id_perfil Perfil de equipe do titular.
 *
 * @return array `ok` true, ou `ok` false com `erro` (id de variável do módulo).
 */
function usuario_equipe_membro_perfil(int $id_titular, int $id_membro, int $id_perfil): array {
	if(!usuario_equipe_habilitada($id_titular)) return Array('ok' => false, 'erro' => 'team-not-allowed');
	if(!usuario_equipe_perfil($id_titular, $id_perfil)) return Array('ok' => false, 'erro' => 'team-profile-required');

	$filtro = "WHERE id_usuarios='".$id_membro."' AND id_usuarios_pai='".$id_titular."' AND status!='D'";

	$membro = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('id_usuarios'),
		'extra' => $filtro,
	));

	if(!$membro) return Array('ok' => false, 'erro' => 'team-member-not-found');

	if(banco_update("id_usuarios_perfis='".$id_perfil."',versao=versao+1,data_modificacao=NOW()", 'usuarios', $filtro) === false){
		return Array('ok' => false, 'erro' => 'team-save-error');
	}

	return Array('ok' => true);
}

/**
 * Ativa ou desativa um membro da equipe do titular.
 *
 * O dono do membro entra no WHERE: o id viaja pelo formulário e, sem isso, um id adivinhado
 * alteraria a conta de outra organização.
 *
 * @param int $id_titular Conta titular.
 * @param int $id_membro Sub-usuário alvo.
 * @param string $status 'A' para ativar, 'I' para desativar.
 *
 * @return array `ok` true, ou `ok` false com `erro` (id de variável do módulo).
 */
function usuario_equipe_membro_status(int $id_titular, int $id_membro, string $status): array {
	if(!in_array($status, Array('A', 'I'), true) || !usuario_equipe_habilitada($id_titular)){
		return Array('ok' => false, 'erro' => 'team-not-allowed');
	}

	if(!usuario_equipe_travar($id_titular)) return Array('ok' => false, 'erro' => 'team-busy');

	try {
		$filtro = "WHERE id_usuarios='".$id_membro."' AND id_usuarios_pai='".$id_titular."' AND status!='D'";

		$membro = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios',
			'campos' => Array('status'),
			'extra' => $filtro,
		));

		if(!$membro) return Array('ok' => false, 'erro' => 'team-member-not-found');
		if($membro['status'] === $status) return Array('ok' => true);

		if($status === 'A' && !usuario_equipe_cota($id_titular)['disponivel']){
			return Array('ok' => false, 'erro' => 'team-quota-reached');
		}

		if(banco_update("status='".$status."',versao=versao+1,data_modificacao=NOW()", 'usuarios', $filtro) === false){
			return Array('ok' => false, 'erro' => 'team-save-error');
		}

		return Array('ok' => true);
	} finally {
		usuario_equipe_destravar($id_titular);
	}
}

?>
