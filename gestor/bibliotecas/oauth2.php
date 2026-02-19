<?php
/**
 * Biblioteca OAuth 2.0
 *
 * Implementa servidor OAuth 2.0 para integração com aplicações externas.
 * Permite que sistemas externos acessem recursos do Conn2Flow via tokens OAuth 2.0.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-oauth2'] = Array(
    'versao' => '1.0.0',
);

/**
 * Gera tokens OAuth 2.0 usando credenciais de usuário do sistema.
 *
 * Recebe id_usuarios já validado e gera access_token e refresh_token.
 * Baseado no fluxo Client Credentials, mas usando credenciais de usuário como client_id/secret.
 *
 * @param array $params Parâmetros da função
 * @param int $params['id_usuarios'] ID do usuário (obrigatório)
 * @param string $params['grant_type'] Tipo de grant (obrigatório, deve ser 'client_credentials')
 * @param string $params['scope'] Escopo opcional (padrão: 'read')
 * @param string $params['url_redirect'] URL para redirecionamento após autenticação (opcional)
 *
 * @return array|false Array com tokens ou false em erro
 */
function oauth2_gerar_token_client_credentials($params = false){
    global $_GESTOR;
    global $_CONFIG;

    // ===== Extrair parâmetros

    if($params)foreach($params as $var => $val)$$var = $val;

    // ===== Validar parâmetros obrigatórios

    if(!isset($id_usuarios) || !isset($grant_type)){
        return false;
    }

    // ===== Validar grant_type

    if($grant_type !== 'client_credentials'){
        return false;
    }

    // ===== Verificar limite de tokens ativos por usuário (ex.: máximo 5)
    $max_tokens = isset($_CONFIG['oauth2']['maximo-tokens-usuario']) ? $_CONFIG['oauth2']['maximo-tokens-usuario'] : 5;
    $tokens_ativos = banco_select_name(
        banco_campos_virgulas(Array('COUNT(*) as total')),
        "oauth2_tokens",
        "WHERE id_usuarios='" . $id_usuarios . "' AND expiration > " . time()
    );

    if (isset($tokens_ativos) && isset($tokens_ativos[0]['total']) && $tokens_ativos[0]['total'] >= $max_tokens) {
        return false; // Ou lance erro: "Limite de tokens ativos atingido"
    }

    // ===== Limpar tokens expirados antes de gerar novos

    oauth2_limpar_tokens_expirados();

    // ===== Gerar tokens usando JWT

    $token_expiration = isset($_CONFIG['oauth2']['token-expiration']) ? $_CONFIG['oauth2']['token-expiration'] : 3600;
    $refresh_expiration = isset($_CONFIG['oauth2']['refresh-token-expiration']) ? $_CONFIG['oauth2']['refresh-token-expiration'] : 2592000;

    $access_token_expiration = time() + $token_expiration;
    $refresh_token_expiration = time() + $refresh_expiration;

    // ===== Abrir chave privada e a senha da chave
	
	$keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
	
	$fp = fopen($keyPrivatePath,"r");
	$chavePrivada = fread($fp,8192);
	fclose($fp);
	
	$chavePrivadaSenha = $_CONFIG['openssl-password'];

    // ===== Gerar pubID único para access_token

    $pubID = md5(uniqid(mt_rand(), true));

    // ===== Gerar pubIDValidation para access_token (hash HMAC)

    $pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $pubID, $_CONFIG['usuario-hash-password']);

    // ===== Payload do access_token

    $access_payload = Array(
        'iss' => $_SERVER['HTTP_HOST'],
        'sub' => $id_usuarios,
        'pubID' => $pubID,
        'exp' => $access_token_expiration,
        'iat' => time(),
        'scope' => isset($scope) ? $scope : 'read',
        'token_type' => 'access'
    );

    // ===== Gerar access_token com chave privada

    $access_token = autenticacao_gerar_jwt_chave_privada(Array(
        'host' => $_SERVER['HTTP_HOST'],
        'expiration' => $access_token_expiration,
        'pubID' => $pubID,
        'chavePrivada' => $chavePrivada,
        'chavePrivadaSenha' => $chavePrivadaSenha,
        'payload' => $access_payload
    ));

    if(!$access_token){
        return false;
    }

    // ===== Gerar pubID único para refresh_token

    $refresh_pubID = md5(uniqid(mt_rand(), true));

    // ===== Gerar pubIDValidation para refresh_token (hash HMAC)

    $refresh_pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $refresh_pubID, $_CONFIG['usuario-hash-password']);

    // ===== Payload do refresh_token

    $refresh_payload = Array(
        'iss' => $_SERVER['HTTP_HOST'],
        'sub' => $id_usuarios,
        'pubID' => $refresh_pubID,
        'exp' => $refresh_token_expiration,
        'iat' => time(),
        'token_type' => 'refresh'
    );

    // ===== Gerar refresh_token

    $refresh_token = autenticacao_gerar_jwt_chave_privada(Array(
        'host' => $_SERVER['HTTP_HOST'],
        'expiration' => $refresh_token_expiration,
        'pubID' => $refresh_pubID,
        'chavePrivada' => $chavePrivada,
        'chavePrivadaSenha' => $chavePrivadaSenha,
        'payload' => $refresh_payload
    ));

    if(!$refresh_token){
        return false;
    }

    // ===== Obtém IP do usuário para auditoria

    gestor_incluir_biblioteca('ip');
    
    $ip = ip_get();

    // ===== Armazenar tokens na tabela oauth2_tokens

    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    // ===== Inserir access_token

    banco_insert_name_campo('id_usuarios', $id_usuarios);
    banco_insert_name_campo('pubID', $pubID);
    banco_insert_name_campo('pubIDValidation', $pubIDValidation);
    banco_insert_name_campo('expiration', $access_token_expiration);
    banco_insert_name_campo('ip', $ip);
    banco_insert_name_campo('user_agent', $user_agent);
    banco_insert_name_campo('tipo', 'access');

    banco_insert_name
    (
        banco_insert_name_campos(),
        "oauth2_tokens"
    );

    // ===== Inserir refresh_token

    banco_insert_name_campo('id_usuarios', $id_usuarios);
    banco_insert_name_campo('pubID', $refresh_pubID);
    banco_insert_name_campo('pubIDValidation', $refresh_pubIDValidation);
    banco_insert_name_campo('expiration', $refresh_token_expiration);
    banco_insert_name_campo('ip', $ip);
    banco_insert_name_campo('user_agent', $user_agent);
    banco_insert_name_campo('tipo', 'refresh');

    banco_insert_name
    (
        banco_insert_name_campos(),
        "oauth2_tokens"
    );

    // ===== Retornar tokens

    $tokens = Array(
        'access_token' => $access_token,
        'token_type' => 'Bearer',
        'expires_in' => $token_expiration,
        'refresh_token' => $refresh_token,
        'scope' => isset($scope) ? $scope : 'read'
    );

    return $tokens;
}

/**
 * Valida token OAuth 2.0.
 *
 * Verifica se o access_token é válido, não expirou e está íntegro.
 *
 * @param array $params Parâmetros da função
 * @param string $params['token'] Access token a ser validado (obrigatório)
 *
 * @return array|false Dados do usuário se válido, false caso contrário
 */
function oauth2_validar_token($params = false){
    global $_GESTOR;
    global $_CONFIG;

    // ===== Extrair parâmetros

    if($params)foreach($params as $var => $val)$$var = $val;

    // ===== Validar parâmetro obrigatório

    if(!isset($token)){
        return false;
    }

    // ===== Incluir biblioteca de autenticação

    gestor_incluir_biblioteca('autenticacao');

    // ===== Abrir chave pública para validar JWT
	
	$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
	
	$fp = fopen($keyPublicPath,"r");
	$chavePublica = fread($fp,8192);
	fclose($fp);

    // ===== Validar token JWT

    $validacao = autenticacao_validar_jwt_chave_publica(Array(
        'token' => $token,
        'chavePublica' => $chavePublica,
        'retornarPayloadCompleto' => true
    ));

    if(!$validacao){
        return false;
    }

    // ===== Verificar se o token está na tabela oauth2_tokens

    $tokens = banco_select_name(
        banco_campos_virgulas(Array(
            'id_oauth2_tokens',
            'pubID',
            'pubIDValidation',
            'id_usuarios'
        )),
        "oauth2_tokens",
        "WHERE pubID='".banco_escape_field($validacao['pubID'])."'"
        ." AND tipo='access'"
    );

    if(!$tokens){
        return false;
    }

    // ===== Verificar se é access_token

    if(!isset($validacao['token_type']) || $validacao['token_type'] !== 'access'){
        return false;
    }

    // ===== Verificar expiração

    if($validacao['exp'] < time()){
        return false;
    }

    // ===== Buscar dados do usuário

    $id_usuarios = $validacao['sub'];

    $usuarios = banco_select_name(
        banco_campos_virgulas(Array(
            'id_usuarios',
            'nome',
            'email',
            'usuario',
            'id_usuarios_perfis'
        )),
        "usuarios",
        "WHERE id_usuarios='".$id_usuarios."'"
        ." AND status='A'"
    );

    if(!$usuarios){
        return false;
    }

    // ===== Retornar dados do usuário

    return Array(
        'id_usuarios' => $usuarios[0]['id_usuarios'],
        'nome' => $usuarios[0]['nome'],
        'email' => $usuarios[0]['email'],
        'usuario' => $usuarios[0]['usuario'],
        'id_usuarios_perfis' => $usuarios[0]['id_usuarios_perfis'],
        'scope' => $validacao['scope']
    );
}

/**
 * Autoriza requisição usando token OAuth 2.0.
 *
 * Middleware para validar Authorization: Bearer header em endpoints protegidos.
 *
 * @param array $params Parâmetros da função
 * @param string $params['header_authorization'] Valor do header Authorization (opcional, pega automaticamente)
 *
 * @return array|false Dados do usuário autorizado ou false
 */
function oauth2_autorizar_requisicao($params = false){
    // ===== Extrair parâmetros

    if($params)foreach($params as $var => $val)$$var = $val;

    // ===== Pegar header Authorization

    $auth_header = isset($header_authorization) ? $header_authorization : (isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '');

    if(!$auth_header){
        return false;
    }

    // ===== Verificar formato Bearer

    if(!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)){
        return false;
    }

    $token = $matches[1];

    // ===== Validar token

    return oauth2_validar_token(Array('token' => $token));
}

/**
 * Limpa tokens OAuth 2.0 expirados da tabela oauth2_tokens.
 *
 * Remove tokens cuja data de criação + tempo de expiração seja menor que o tempo atual.
 * Deve ser chamado periodicamente para manter a tabela limpa.
 *
 * @return bool True se limpeza executada com sucesso
 */
function oauth2_limpar_tokens_expirados(){
    global $_GESTOR;
    global $_CONFIG;

    // ===== Remover tokens expirados

    banco_delete(
        "oauth2_tokens",
        "WHERE expiration < ".time()
    );

    return true;
}

/**
 * Renova access token usando refresh token.
 *
 * Valida refresh token, gera novo access token e novo refresh token,
 * invalida o refresh token anterior.
 *
 * @param array $params Parâmetros da função
 * @param string $params['refresh_token'] Refresh token para renovação (obrigatório)
 * @param string $params['scope'] Escopo opcional (padrão: 'read')
 *
 * @return array|false Novos tokens ou false em erro
 */
function oauth2_renovar_token($params = false){
    global $_GESTOR;
    global $_CONFIG;

    // ===== Extrair parâmetros

    if($params)foreach($params as $var => $val)$$var = $val;

    // ===== Validar parâmetro obrigatório

    if(!isset($refresh_token)){
        return false;
    }

    // ===== Incluir biblioteca de autenticação

    gestor_incluir_biblioteca('autenticacao');

    // ===== Abrir chave pública para validar JWT
	
	$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
	
	$fp = fopen($keyPublicPath,"r");
	$chavePublica = fread($fp,8192);
	fclose($fp);

    // ===== Validar refresh token

    $validacao = autenticacao_validar_jwt_chave_publica(Array(
        'token' => $refresh_token,
        'chavePublica' => $chavePublica,
        'retornarPayloadCompleto' => true
    ));
    
    if(!$validacao){
        return false;
    }
    
    // ===== Verificar se é refresh_token

    if(!isset($validacao['token_type']) || $validacao['token_type'] !== 'refresh'){
        return false;
    }

    // ===== Verificar expiração
    
    if($validacao['exp'] < time()){
        return false;
    }

    // ===== Buscar refresh token na tabela e validar pubIDValidation

    $tokens = banco_select_name(
        banco_campos_virgulas(Array(
            'id_oauth2_tokens',
            'pubID',
            'pubIDValidation',
            'id_usuarios'
        )),
        "oauth2_tokens",
        "WHERE pubID='".banco_escape_field($validacao['pubID'])."'"
        ." AND tipo='refresh'"
    );

    if(!$tokens){
        return false;
    }

    // ===== Validar pubIDValidation (hash HMAC adicional)

    $pubID_from_db = $tokens[0]['pubID'];
    $pubIDValidation_from_db = $tokens[0]['pubIDValidation'];
    $expected_pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $pubID_from_db, $_CONFIG['usuario-hash-password']);

    if($pubIDValidation_from_db !== $expected_pubIDValidation){
        return false;
    }

    $id_usuarios = $tokens[0]['id_usuarios'];

    // ===== Invalidar refresh token anterior

    banco_delete(
        "oauth2_tokens",
        "WHERE id_oauth2_tokens='".$tokens[0]['id_oauth2_tokens']."'"
    );

    // ===== Gerar novos tokens

    $novos_tokens = oauth2_gerar_token_client_credentials(Array(
        'id_usuarios' => $id_usuarios,
        'grant_type' => 'client_credentials',
        'scope' => isset($scope) ? $scope : 'read'
    ));

    return $novos_tokens;
}

?>
