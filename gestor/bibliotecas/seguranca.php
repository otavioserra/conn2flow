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
 * Gera um identificador hexadecimal com entropia criptograficamente segura.
 *
 * @param int $bytes Quantidade de bytes aleatórios (mínimo: 16 / 128 bits).
 * @return string
 */
function seguranca_token_aleatorio($bytes = 32){
    $bytes = (int)$bytes;
    if($bytes < 16) $bytes = 16;

    return bin2hex(random_bytes($bytes));
}

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
        $token = seguranca_token_aleatorio(32);
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
function gestor_csrf_validar($token, $esperado = null){
    if($esperado === null) $esperado = gestor_sessao_variavel('csrf_token');

    if(!existe($esperado) || !is_string($token) || $token === '') return false;

    return hash_equals((string)$esperado, (string)$token);
}

/**
 * Obtém o token CSRF enviado em campo de formulário ou cabeçalho HTTP.
 *
 * @return string
 */
function seguranca_csrf_token_requisicao(){
    if(isset($_SERVER['HTTP_X_CSRF_TOKEN'])) return (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
    if(isset($_POST['_csrf_token'])) return (string)$_POST['_csrf_token'];
    if(isset($_REQUEST['_csrf_token'])) return (string)$_REQUEST['_csrf_token'];

    return '';
}

/**
 * Informa se a rota usa autenticação M2M/Bearer e, portanto, não usa cookie de sessão.
 * O canal distribuído é protegido por HMAC dentro do controlador da API.
 *
 * @param array $caminho Segmentos normalizados da rota.
 * @return bool
 */
function seguranca_csrf_rota_isenta($caminho){
    if(!is_array($caminho) || !isset($caminho[0])) return false;

    return $caminho[0] === '_api' || $caminho[0] === 'api';
}

/**
 * Mantém compatibilidade somente no autoatualizador que introduziu o CSRF.
 *
 * A página do atualizador permanece aberta enquanto substitui os próprios
 * arquivos. Em instalações anteriores à 2.9.25, esse documento ainda não
 * possui o cliente que envia o token, embora o backend recém-instalado já o
 * conheça. A isenção termina automaticamente nas versões posteriores.
 *
 * @param array $caminho Segmentos normalizados da rota.
 * @param string $versao Versão atual do Gestor.
 * @return bool
 */
function seguranca_csrf_atualizador_transicao_isento($caminho, $versao){
    if(!is_array($caminho) || ($caminho[0] ?? '') !== 'admin-atualizacoes') return false;
    if(!is_string($versao) || $versao === '') return false;

    return version_compare($versao, '2.9.25', '<=');
}

/**
 * Reconhece a consulta de status do autoatualizador como operação de leitura.
 *
 * Versões antigas do cliente enviavam essa consulta por POST. A isenção é
 * limitada à ação status; deploy, banco, finalize e cancel continuam exigindo
 * o token CSRF nas versões em que a proteção já está ativa.
 *
 * @param array $caminho Segmentos normalizados da rota.
 * @param array $requisicao Parâmetros recebidos pela requisição.
 * @return bool
 */
function seguranca_csrf_atualizador_status_isento($caminho, $requisicao){
    if(!is_array($caminho) || ($caminho[0] ?? '') !== 'admin-atualizacoes') return false;
    if(!is_array($requisicao)) return false;

    $params = $requisicao['params'] ?? Array();
    return is_array($params) && ($params['acao'] ?? '') === 'status';
}

/**
 * Exige CSRF em métodos mutáveis autenticados pelo cookie do painel.
 *
 * @return bool true quando a requisição pode continuar.
 */
function seguranca_csrf_requisicao_validar(){
    global $_GESTOR;
    global $_CONFIG;

    $metodo = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if(!in_array($metodo, Array('POST', 'PUT', 'PATCH', 'DELETE'), true)) return true;
    if(seguranca_csrf_rota_isenta($_GESTOR['caminho'] ?? Array())) return true;
    if(seguranca_csrf_atualizador_transicao_isento($_GESTOR['caminho'] ?? Array(), $_GESTOR['versao'] ?? '')) return true;
    if(seguranca_csrf_atualizador_status_isento($_GESTOR['caminho'] ?? Array(), $_REQUEST)) return true;

    $cookieAuth = $_CONFIG['cookie-authname'] ?? '';
    if($cookieAuth === '' || !isset($_COOKIE[$cookieAuth])) return true;

    return gestor_csrf_validar(seguranca_csrf_token_requisicao());
}

?>
