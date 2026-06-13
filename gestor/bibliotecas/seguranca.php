<?php
/**
 * Biblioteca de Endurecimento de Segurança
 *
 * Proteção contra Session Hijacking (validação de User-Agent + bloco de IP) e
 * utilitários de token CSRF. Usa o armazenamento de sessão do sistema
 * (`gestor_sessao_variavel`, persistido na tabela `sessoes_variaveis`).
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-seguranca'] = Array(
    'versao' => '1.0.0',
);

// ===== Helpers

/**
 * Retorna o bloco de rede do IP (3 primeiros octetos no IPv4).
 *
 * @param string|null $ip IP a avaliar (padrão: REMOTE_ADDR).
 * @return string Bloco de rede (ex.: "200.100.50") ou o IP original.
 */
function seguranca_ip_bloco($ip = null){
    if($ip === null) $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = (string)$ip;

    if(strpos($ip, '.') !== false){
        $partes = explode('.', $ip);
        if(count($partes) >= 3) return $partes[0].'.'.$partes[1].'.'.$partes[2];
    }

    return $ip; // IPv6 ou formato desconhecido: compara o valor inteiro
}

/**
 * Retorna o User-Agent atual (truncado).
 *
 * @return string
 */
function seguranca_user_agent(){
    return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
}

// ===== Session Hijacking

/**
 * Registra na sessão o User-Agent e o bloco de IP do cliente no momento do login.
 *
 * @return void
 */
function seguranca_sessao_registrar(){
    gestor_sessao_variavel('client_user_agent', seguranca_user_agent());
    gestor_sessao_variavel('client_ip_block', seguranca_ip_bloco());
}

/**
 * Valida a conformidade do User-Agent e bloco de IP atuais com os registrados.
 *
 * Fail-safe: se os marcadores ainda não foram registrados (sessões anteriores à
 * proteção), não bloqueia — evita derrubar usuários legítimos retroativamente.
 *
 * @return bool true se conforme (ou não registrado); false em discrepância suspeita.
 */
function seguranca_sessao_validar(){
    $uaSalvo = gestor_sessao_variavel('client_user_agent');
    $ipSalvo = gestor_sessao_variavel('client_ip_block');

    if(!existe($uaSalvo) && !existe($ipSalvo)){
        return true;
    }

    if($uaSalvo !== seguranca_user_agent()) return false;
    if($ipSalvo !== seguranca_ip_bloco()) return false;

    return true;
}

/**
 * Invalida a sessão/token atual em caso de sequestro suspeito.
 *
 * @param string|null $tokenPubId pubID do token de autorização a remover.
 * @return void
 */
function seguranca_sessao_invalidar($tokenPubId = null){
    if($tokenPubId){
        banco_delete("usuarios_tokens", "WHERE pubID='".banco_escape_field($tokenPubId)."'");
    }

    gestor_sessao_variavel_del('client_user_agent');
    gestor_sessao_variavel_del('client_ip_block');
}

// ===== CSRF

/**
 * Obtém o token CSRF da sessão, gerando-o na primeira chamada.
 *
 * @return string
 */
function gestor_csrf_token(){
    $token = gestor_sessao_variavel('csrf_token');

    if(!existe($token)){
        $token = bin2hex(random_bytes(32));
        gestor_sessao_variavel('csrf_token', $token);
    }

    return $token;
}

/**
 * Valida um token CSRF recebido contra o armazenado na sessão.
 *
 * @param string $token Token recebido na requisição.
 * @return bool
 */
function gestor_csrf_validar($token){
    $esperado = gestor_sessao_variavel('csrf_token');

    if(!existe($esperado) || !is_string($token) || $token === '') return false;

    return hash_equals((string)$esperado, (string)$token);
}

?>
