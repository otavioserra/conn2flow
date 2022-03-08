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
	$_REQUEST = json_decode($json, TRUE);
}

// =========================== Funções da Plataforma

function plataforma_app_baixar_voucher(){
	
}

function plataforma_app_login(){
	global $_GESTOR;
	
	// ===== Validador provisório!!!
	
	
	if(sha1($_REQUEST['appID']) !== 'da39a3ee5e6b4b0d3255bfef95601890afd80709'){
		plataforma_app_200(Array(
			'comparacao' => sha1($_REQUEST['appID']).' !== da39a3ee5e6b4b0d3255bfef95601890afd80709'
		));
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
		$senha = banco_escape_field($_REQUEST['pass']);
		$user_inactive = false;
		
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
					$user_inactive = true;
				}
			}
		}
	} else {
		// ===== Se o recaptcha for inválido, alertar o usuário.
		
		sleep(3);
		
		$alerta = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'app-recaptcha-invalid'));
		
		$message = modelo_var_troca_tudo($alerta,"#url#",$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url']);
		
		plataforma_app_200(Array(
			'message' => $message,
		));
	}

	// ===== Se o usuário for inválido, redirecionar signin.
	
	if($user_invalid){
		sleep(3);
		
		if($user_inactive){
			$message = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-inactive'));
		} else {
			$message = gestor_variaveis(Array('modulo' => 'perfil-usuario','id' => 'alert-user-or-password-invalid'));
		}
		
		plataforma_app_200(Array(
			'message' => $message,
		));
	}
	
	return null;
}

// =========================== Funções de Acesso

function plataforma_app_401(){
	http_response_code(401);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '401',
		'info' => 'JSON unauthorized',
	));
	exit;
}

function plataforma_app_404(){
	http_response_code(404);
	
	header("Content-Type: application/json; charset: UTF-8");
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
	
	header("Content-Type: application/json; charset: UTF-8");
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
			." AND user_agent='".$_GESTOR['app-user-agent']."'"
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
					
					return true;
				}
			}
		}
	}
	
	return false;
}

function plataforma_app_start(){
	global $_GESTOR;
	global $_INDEX;
	
	header("Access-Control-Allow-Origin: *");
	stream_php();
	
	// ===== Acessos públicos a plataforma.
	
	switch($_GESTOR['caminho'][1]){
		case 'login': $dados = plataforma_app_login(); $acessoPublico = true; break;
	}
	
	// ===== Acessos privados a plataforma.
	
	if(!isset($acessoPublico)){
		$token = (isset($_REQUEST['token']) ? $_REQUEST['token'] : '');
		
		if(plataforma_app_permissao_token($token)){
			switch($_GESTOR['caminho'][1]){
				case 'baixar-voucher': $dados = plataforma_app_baixar_voucher(); break;
			}
		} else {
			plataforma_app_401();
		}
	}
	
	// ===== Caso haja dados criados por alguma opção, retornar JSON e finalizar. Senão retornar JSON 404.
	
	if(isset($dados)){
		header("Content-Type: application/json; charset: UTF-8");
		echo json_encode($dados);
		exit;
	}
	
	plataforma_app_404();
}

// =========================== Inciar Plataforma

plataforma_app_start();

?>