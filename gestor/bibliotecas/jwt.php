<?php
/**
 * Biblioteca JSON Web Token (JWT) com Rotação de Chaves
 *
 * Gera e valida tokens JWT assinados em HS256 (HMAC-SHA256) usando um conjunto de
 * chaves simétricas versionadas e armazenado no banco (tabela `variaveis`,
 * modulo='sistema', id='jwt_keys'). Suporta rotação de chaves com período de
 * carência (grace period) para validação retroativa de tokens recém-emitidos.
 *
 * Observação de contrato (req-030 §3.2): o período de carência é medido a partir
 * de quando a chave foi rotacionada para `expired` (campo `expired_at`), com
 * fallback para `created_at`. Medir somente por `created_at` invalidaria o grace
 * em produção (a chave ativa vive AUTH_JWT_ROTATION_DAYS dias antes de expirar).
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-jwt'] = Array(
    'versao' => '1.0.0',
);

// ===== Helpers Base64 URL-safe

function jwt_base64url_encode($data){
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_base64url_decode($data){
    $resto = strlen($data) % 4;
    if($resto) $data .= str_repeat('=', 4 - $resto);
    return base64_decode(strtr($data, '-_', '+/'));
}

// ===== Configurações (lidas do .env via $_ENV)

function jwt_rotation_days(){
    return (int)($_ENV['AUTH_JWT_ROTATION_DAYS'] ?? 30);
}

function jwt_grace_hours(){
    return (int)($_ENV['AUTH_JWT_GRACE_HOURS'] ?? 24);
}

// ===== Persistência do conjunto de chaves

/**
 * Carrega o conjunto de chaves JWT do banco.
 *
 * @return array Lista de chaves (cada uma com key_id, key_secret, created_at, status).
 */
function jwt_keys_load(){
    $registro = banco_select(Array(
        'unico' => true,
        'tabela' => 'variaveis',
        'campos' => Array('valor'),
        'extra' => "WHERE modulo='sistema' AND id='jwt_keys'",
    ));

    $keys = Array();
    if($registro && !empty($registro['valor'])){
        $decoded = json_decode($registro['valor'], true);
        if(is_array($decoded)) $keys = $decoded;
    }

    return $keys;
}

/**
 * Persiste o conjunto de chaves JWT no banco (insert ou update).
 *
 * @param array $keys Lista de chaves.
 * @return void
 */
function jwt_keys_save($keys){
    $valor = json_encode(array_values($keys));

    $existe = banco_select(Array(
        'unico' => true,
        'tabela' => 'variaveis',
        'campos' => Array('id_variaveis'),
        'extra' => "WHERE modulo='sistema' AND id='jwt_keys'",
    ));

    if($existe){
        banco_update_campo('valor', $valor);
        banco_update_executar('variaveis', "WHERE modulo='sistema' AND id='jwt_keys'");
    } else {
        banco_insert_name_campo('modulo', 'sistema');
        banco_insert_name_campo('id', 'jwt_keys');
        banco_insert_name_campo('valor', $valor);
        banco_insert_name_campo('tipo', 'json');
        banco_insert_name(banco_insert_name_campos(), 'variaveis');
    }
}

/**
 * Cria uma nova estrutura de chave JWT.
 *
 * @param string $status 'active' ou 'expired'.
 * @return array Chave gerada.
 */
function jwt_nova_chave($status = 'active'){
    return Array(
        'key_id' => bin2hex(random_bytes(8)),
        'key_secret' => bin2hex(random_bytes(32)),
        'created_at' => time(),
        'status' => $status,
    );
}

/**
 * Obtém a chave ativa; cria uma se ainda não existir.
 *
 * @return array Chave ativa.
 */
function jwt_get_active_key(){
    $keys = jwt_keys_load();

    foreach($keys as $k){
        if(($k['status'] ?? '') === 'active') return $k;
    }

    $nova = jwt_nova_chave('active');
    $keys[] = $nova;
    jwt_keys_save($keys);

    return $nova;
}

// ===== Geração e validação de tokens

/**
 * Gera um token JWT assinado com a chave ativa.
 *
 * @param array $payload Reivindicações do token.
 * @return string Token JWT (header.payload.signature).
 */
function jwt_generate_token($payload){
    if(!is_array($payload)) $payload = Array();

    $key = jwt_get_active_key();

    $header = Array('alg' => 'HS256', 'typ' => 'JWT', 'kid' => $key['key_id']);
    if(!isset($payload['iat'])) $payload['iat'] = time();

    $segH = jwt_base64url_encode(json_encode($header));
    $segP = jwt_base64url_encode(json_encode($payload));
    $assinatura = jwt_base64url_encode(hash_hmac('sha256', $segH . '.' . $segP, $key['key_secret'], true));

    return $segH . '.' . $segP . '.' . $assinatura;
}

/**
 * Valida um token JWT.
 *
 * Identifica a chave pelo `kid` do header. Se a chave estiver ativa e a assinatura
 * conferir, retorna status 'Active'. Se a chave estiver expirada mas dentro do
 * período de carência, retorna status 'Grace' (sinalizando renovação do token).
 *
 * @param string $token Token JWT.
 * @return array ['status' => 'Active'|'Grace', 'payload' => array]
 * @throws Exception Se o token for malformado, a chave desconhecida, a assinatura
 *                   inválida ou a chave estiver fora do período de carência.
 */
function jwt_validate_token($token){
    $partes = explode('.', (string)$token);
    if(count($partes) !== 3) throw new Exception('JWT malformado.');

    list($segH, $segP, $segS) = $partes;

    $header = json_decode(jwt_base64url_decode($segH), true);
    $payload = json_decode(jwt_base64url_decode($segP), true);
    if(!is_array($header) || !is_array($payload)) throw new Exception('JWT inválido.');

    $kid = $header['kid'] ?? null;
    if(!$kid) throw new Exception('JWT sem identificador de chave (kid).');

    $keys = jwt_keys_load();
    $chave = null;
    foreach($keys as $k){
        if(($k['key_id'] ?? '') === $kid){ $chave = $k; break; }
    }
    if(!$chave) throw new Exception('Chave JWT desconhecida.');

    $esperado = jwt_base64url_encode(hash_hmac('sha256', $segH . '.' . $segP, $chave['key_secret'], true));
    if(!hash_equals($esperado, $segS)) throw new Exception('Assinatura JWT inválida.');

    // ===== Assinatura confere: avaliar o status da chave

    if(($chave['status'] ?? '') === 'active'){
        return Array('status' => 'Active', 'payload' => $payload);
    }

    // ===== Chave expirada: verificar período de carência (a partir de expired_at)

    $base = (int)($chave['expired_at'] ?? $chave['created_at'] ?? 0);
    $graceLimite = $base + jwt_grace_hours() * 3600;

    if(time() <= $graceLimite){
        return Array('status' => 'Grace', 'payload' => $payload);
    }

    throw new Exception('Chave JWT fora do período de carência.');
}

/**
 * Rotaciona as chaves JWT: marca a ativa como expirada, gera uma nova ativa e
 * purga as chaves expiradas que já ultrapassaram o período de carência.
 *
 * @return array A nova chave ativa.
 */
function jwt_rotate_keys(){
    $keys = jwt_keys_load();
    $agora = time();
    $novas = Array();

    foreach($keys as $k){
        if(($k['status'] ?? '') === 'active'){
            $k['status'] = 'expired';
            if(!isset($k['expired_at'])) $k['expired_at'] = $agora;
        }

        if(($k['status'] ?? '') === 'expired'){
            $base = (int)($k['expired_at'] ?? $k['created_at'] ?? 0);
            $graceLimite = $base + jwt_grace_hours() * 3600;
            if($agora > $graceLimite) continue; // purga chave fora da carência
        }

        $novas[] = $k;
    }

    $nova = jwt_nova_chave('active');
    $novas[] = $nova;
    jwt_keys_save($novas);

    return $nova;
}

?>
