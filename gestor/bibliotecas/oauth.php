<?php
/**
 * Biblioteca de Login Social (OAuth 2.0)
 *
 * Implementa o fluxo Authorization Code do OAuth 2.0 para login social com Google
 * e Meta (Facebook): geração do link de autorização com proteção CSRF via `state`
 * e troca do código de autorização por um perfil verificado (uid + e-mail).
 *
 * Credenciais lidas do .env: OAUTH_GOOGLE_CLIENT_ID/SECRET e OAUTH_META_APP_ID/SECRET.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-oauth'] = Array(
    'versao' => '1.0.0',
);

// ===== Configuração por provedor

/**
 * Retorna a configuração de endpoints/credenciais de um provedor.
 *
 * @param string $provider 'google' ou 'meta'.
 * @return array|false Configuração ou false se o provedor não for suportado.
 */
function oauth_config($provider){
    $provider = strtolower((string)$provider);

    if($provider === 'google'){
        return Array(
            'client_id' => $_ENV['OAUTH_GOOGLE_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'] ?? '',
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
            'scope' => 'openid email profile',
        );
    }

    if($provider === 'meta'){
        return Array(
            'client_id' => $_ENV['OAUTH_META_APP_ID'] ?? '',
            'client_secret' => $_ENV['OAUTH_META_APP_SECRET'] ?? '',
            'auth_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
            'userinfo_url' => 'https://graph.facebook.com/me?fields=id,name,email',
            'scope' => 'email public_profile',
        );
    }

    return false;
}

/**
 * Monta a URI de callback do provedor (calculada a partir do domínio atual).
 *
 * @param string $provider 'google' ou 'meta'.
 * @return string URI de redirecionamento (ex.: https://dominio/_api/auth/callback/google).
 */
function oauth_redirect_uri($provider){
    global $_GESTOR;

    $base = isset($_GESTOR['url-full-http']) && $_GESTOR['url-full-http']
        ? $_GESTOR['url-full-http']
        : ('https://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '/');

    // Rota do módulo perfil-usuario (option oauth-callback); o provider acompanha como query.
    return rtrim($base, '/') . '/oauth-callback/?provider=' . strtolower((string)$provider);
}

// ===== Geração do link de autorização

/**
 * Gera o URL de redirecionamento para a tela de consentimento do provedor.
 * Grava o `state` (proteção CSRF) e o provedor na sessão.
 *
 * @param string $provider 'google' ou 'meta'.
 * @return string|false URL de autorização ou false se não configurado.
 */
function oauth_redirect_url($provider){
    $cfg = oauth_config($provider);
    if(!$cfg || empty($cfg['client_id'])) return false;

    $state = bin2hex(random_bytes(16));
    gestor_sessao_variavel('oauth_state', $state);
    gestor_sessao_variavel('oauth_provider', strtolower((string)$provider));

    $params = Array(
        'client_id' => $cfg['client_id'],
        'redirect_uri' => oauth_redirect_uri($provider),
        'response_type' => 'code',
        'scope' => $cfg['scope'],
        'state' => $state,
    );

    if(strtolower((string)$provider) === 'google'){
        $params['access_type'] = 'online';
        $params['prompt'] = 'select_account';
    }

    return $cfg['auth_url'] . '?' . http_build_query($params);
}

/**
 * Valida o parâmetro `state` retornado pelo provedor contra o gravado na sessão.
 *
 * @param string $state Valor recebido no callback.
 * @return bool true se conferir.
 */
function oauth_validate_state($state){
    $esperado = gestor_sessao_variavel('oauth_state');
    if($esperado === '' || $state === '' || $state === null) return false;

    return hash_equals((string)$esperado, (string)$state);
}

// ===== HTTP (cURL)

function oauth_http_post($url, $data){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Accept: application/json'));

    $resposta = curl_exec($ch);
    curl_close($ch);

    if($resposta === false) return false;
    return json_decode($resposta, true);
}

function oauth_http_get($url, $bearer = null){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $headers = Array('Accept: application/json');
    if($bearer){
        $headers[] = 'Authorization: Bearer ' . $bearer;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resposta = curl_exec($ch);
    curl_close($ch);

    if($resposta === false) return false;
    return json_decode($resposta, true);
}

// ===== Troca de código por perfil

/**
 * Troca o código de autorização por um token de acesso e retorna o perfil
 * verificado do usuário.
 *
 * @param string $provider 'google' ou 'meta'.
 * @param string $code Código de autorização recebido no callback.
 * @return array|false ['provider','uid','email','nome'] ou false em erro.
 */
function oauth_authenticate_code($provider, $code){
    $provider = strtolower((string)$provider);
    $cfg = oauth_config($provider);
    if(!$cfg || empty($cfg['client_id'])) return false;
    if($code === '' || $code === null) return false;

    // ===== Trocar o código por token de acesso

    $tokenResp = oauth_http_post($cfg['token_url'], Array(
        'client_id' => $cfg['client_id'],
        'client_secret' => $cfg['client_secret'],
        'code' => $code,
        'redirect_uri' => oauth_redirect_uri($provider),
        'grant_type' => 'authorization_code',
    ));

    if(!$tokenResp || empty($tokenResp['access_token'])) return false;
    $accessToken = $tokenResp['access_token'];

    // ===== Buscar o perfil verificado

    if($provider === 'google'){
        $perfil = oauth_http_get($cfg['userinfo_url'], $accessToken);
        $uid = $perfil['sub'] ?? null;
    } else {
        // Meta envia o token como parâmetro de query.
        $perfil = oauth_http_get($cfg['userinfo_url'] . '&access_token=' . urlencode($accessToken));
        $uid = $perfil['id'] ?? null;
    }

    if(!$perfil || !$uid) return false;

    return Array(
        'provider' => $provider,
        'uid' => (string)$uid,
        'email' => $perfil['email'] ?? null,
        'nome' => $perfil['name'] ?? '',
    );
}

?>
