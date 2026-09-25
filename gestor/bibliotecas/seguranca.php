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

/** Valida o provedor configurado. O transporte pode ser injetado nos testes. */
function gestor_captcha_validar(?string $token = null, array $opcoes = []): array|bool{
    global $_CONFIG;

    $provider = $opcoes['provider'] ?? ($_CONFIG['captcha-provider'] ?? (!empty($_CONFIG['usuario-recaptcha-active']) ? 'google-recaptcha' : 'none'));
    if($provider === 'none') return true;
    if(!in_array($provider, ['google-recaptcha', 'cloudflare-turnstile'], true)) return false;

    $token = $token ?? ($_POST['cf-turnstile-response'] ?? $_POST['g-recaptcha-response'] ?? $_POST['token'] ?? '');
    if(!is_string($token) || trim($token) === '') return false;

    $secret = $opcoes['secret'] ?? ($provider === 'cloudflare-turnstile'
        ? ($_CONFIG['turnstile-secret-key'] ?? '')
        : (!empty($opcoes['v2']) ? ($_CONFIG['usuario-recaptcha-v2-server'] ?? '') : ($_CONFIG['usuario-recaptcha-server'] ?? '')));
    if(!is_string($secret) || $secret === '') return false;

    $url = $provider === 'cloudflare-turnstile'
        ? 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
        : 'https://www.google.com/recaptcha/api/siteverify';
    $payload = ['secret' => $secret, 'response' => $token];
    if($provider === 'cloudflare-turnstile' && !empty($_SERVER['REMOTE_ADDR'])){
        $payload['remoteip'] = $_SERVER['REMOTE_ADDR'];
    }

    if(isset($opcoes['transport']) && is_callable($opcoes['transport'])){
        $response = $opcoes['transport']($url, $payload);
    } else {
        $ch = curl_init($url);
        if($ch === false) return false;
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    if(!is_string($response)) return false;
    $result = json_decode($response, true);
    if(!is_array($result)) return false;
    if(($result['success'] ?? false) !== true) return !empty($opcoes['return_response']) ? $result : false;
    if($provider === 'google-recaptcha' && empty($opcoes['v2'])){
        if(array_key_exists('action', $opcoes) && (!is_string($opcoes['action']) || $opcoes['action'] === '' || ($result['action'] ?? null) !== $opcoes['action'])) return false;
        if(array_key_exists('action', $opcoes) && ($result['score'] ?? 0) < 0.5) return false;
    }
    return $result;
}

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

    // req-175: a rota de renovação existe justamente para quem ficou SEM token válido; exigir
    // o token nela tornaria a recuperação impossível. Ela só lê e devolve o token da própria sessão.
    if($caminho[0] === SEGURANCA_CSRF_ROTA_TOKEN) return true;

    return $caminho[0] === '_api' || $caminho[0] === 'api';
}

// ===== req-175: renovação silenciosa de CSRF

/** Código de máquina de CSRF ausente/vencido, lido pelos interceptores do `global.js`. */
const SEGURANCA_CSRF_ERRO_CODIGO = 'CSRF_INVALID_OR_EXPIRED';

/** Rota de sistema que devolve o token CSRF ativo da sessão. */
const SEGURANCA_CSRF_ROTA_TOKEN = '_gestor-csrf-token';

/**
 * Corpo JSON da recusa por CSRF (req-107, `code` acrescentado na req-175).
 *
 * `status` e `message` seguem no contrato de antes; `code` permite ao frontend separar um token
 * vencido (renovável) de um 403 legítimo de permissão, que nunca deve entrar em retry.
 *
 * @param string $mensagem Mensagem legível já existente.
 * @return array
 */
function seguranca_csrf_resposta_invalida_corpo($mensagem){
    return Array(
        'status' => 'error',
        'code' => SEGURANCA_CSRF_ERRO_CODIGO,
        'message' => (string)$mensagem,
    );
}

/**
 * Decide a resposta da rota `_gestor-csrf-token` (req-175). Função PURA: o roteador só emite.
 *
 * - Sem cookie de autenticação: visitante. Recebe o token para seguir usando formulários públicos.
 * - Cookie de autenticação presente e válido: token + `authenticated: true`.
 * - Cookie presente mas JWT recusado: a sessão de login EXPIROU. 401 com `AUTH_EXPIRED` e o
 *   cabeçalho `X-Gestor-Auth-Redirect`, o mesmo que o `global.js` já segue para o login. Sem
 *   token: renovar o CSRF de uma sessão morta só faria o retry falhar de novo mais adiante.
 *
 * @param array $contexto token, tem_cookie_auth, autenticado, url_raiz.
 * @return array ['http' => int, 'headers' => array, 'corpo' => array]
 */
function seguranca_csrf_token_resposta($contexto){
    $c = is_array($contexto) ? $contexto : Array();
    $temCookieAuth = !empty($c['tem_cookie_auth']);
    $autenticado = $temCookieAuth && !empty($c['autenticado']);

    $headers = Array(
        'Content-Type' => 'application/json; charset=UTF-8',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
    );

    if($temCookieAuth && !$autenticado){
        $raiz = rtrim(str_replace(Array("\r", "\n"), '', (string)($c['url_raiz'] ?? '/')), '/').'/';
        $headers['X-Gestor-Auth-Redirect'] = $raiz.'signin/';

        return Array(
            'http' => 401,
            'headers' => $headers,
            'corpo' => Array(
                'status' => 'error',
                'code' => 'AUTH_EXPIRED',
                'authenticated' => false,
                'redirect' => 'signin/',
            ),
        );
    }

    return Array(
        'http' => 200,
        'headers' => $headers,
        'corpo' => Array(
            'status' => 'success',
            'token' => (string)($c['token'] ?? ''),
            'authenticated' => $autenticado,
        ),
    );
}

/**
 * Normaliza o caminho de retorno enviado pelo cliente para o pós-login (req-175).
 *
 * Aceita apenas caminho RELATIVO à raiz do gestor: sem esquema, host, `//`, traversal ou
 * quebra de linha. Qualquer outra coisa vira string vazia (e o login cai no destino padrão).
 *
 * @param mixed $retorno Valor bruto recebido.
 * @return string Caminho terminado em `/`, ou ''.
 */
function seguranca_csrf_retorno_normalizar($retorno){
    if(!is_string($retorno)) return '';

    $retorno = trim($retorno);
    if($retorno === '' || strlen($retorno) > 512) return '';
    if(preg_match('/[\x00-\x1F\x7F\\\\]/', $retorno)) return '';
    if(strpos($retorno, '//') !== false || strpos($retorno, ':') !== false) return '';

    $caminho = (string)parse_url($retorno, PHP_URL_PATH);
    $caminho = ltrim($caminho, '/');

    $decodificado = $caminho;
    for($i = 0; $i < 3; $i++){
        $proximo = rawurldecode($decodificado);
        if($proximo === $decodificado) break;
        $decodificado = $proximo;
    }
    if(strpos($decodificado, '..') !== false) return '';
    if($caminho === '') return '';

    return rtrim($caminho, '/').'/';
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
 * Permite concluir uma sessao do autoatualizador iniciada por um cliente
 * anterior ao CSRF. O SID aleatorio e o estado persistido limitam a isencao
 * a uma transicao real, recente, inacabada e na etapa esperada.
 *
 * Clientes atuais marcam a sessao como csrf-capable e nunca usam esta isencao.
 *
 * @param array $caminho Segmentos normalizados da rota.
 * @param array $requisicao Parametros recebidos pela requisicao.
 * @param string|null $rootPath Raiz fisica do Gestor (injetavel nos testes).
 * @param int|null $agora Timestamp atual (injetavel nos testes).
 * @return bool
 */
function seguranca_csrf_atualizador_sessao_legada_isento($caminho, $requisicao, $rootPath = null, $agora = null){
    global $_GESTOR;

    if(!is_array($caminho) || ($caminho[0] ?? '') !== 'admin-atualizacoes') return false;
    if(!is_array($requisicao) || seguranca_csrf_token_requisicao() !== '') return false;

    $params = $requisicao['params'] ?? Array();
    if(!is_array($params)) return false;

    $acao = (string)($params['acao'] ?? '');
    $sid = (string)($params['sid'] ?? '');
    if(!in_array($acao, Array('deploy', 'db', 'finalize', 'cancel'), true)) return false;
    if(!preg_match('/^[a-f0-9]{16}$/D', $sid)) return false;

    if($rootPath === null) $rootPath = (string)($_GESTOR['ROOT_PATH'] ?? '');
    if($rootPath === '') return false;

    $arquivo = rtrim($rootPath, '/\\').DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'sessions'.DIRECTORY_SEPARATOR.$sid.'.json';
    if(!is_file($arquivo) || filesize($arquivo) > 1048576) return false;

    $estado = json_decode((string)@file_get_contents($arquivo), true);
    if(!is_array($estado) || !hash_equals($sid, (string)($estado['sid'] ?? ''))) return false;
    if(!empty($estado['finished']) || !empty($estado['opts']['csrf-capable'])) return false;

    $criadoEm = strtotime((string)($estado['created_at'] ?? ''));
    $agora = $agora ?? time();
    if($criadoEm === false || $criadoEm > $agora + 300 || $criadoEm < $agora - 21600) return false;

    $progresso = is_array($estado['progress'] ?? null) ? $estado['progress'] : Array();
    $bootstrapConcluido = !empty($progresso['bootstrap']['done']);
    $deployConcluido = !empty($progresso['deploy_files']['done']);
    $bancoConcluido = !empty($progresso['database']['done']);

    if($acao === 'deploy') return $bootstrapConcluido && !$deployConcluido;
    if($acao === 'db') return $deployConcluido && !$bancoConcluido;
    if($acao === 'finalize'){
        $semBanco = !empty($estado['opts']['only-files']) || !empty($estado['opts']['no-db']) || !empty($estado['opts']['download-only']);
        return $deployConcluido && ($bancoConcluido || $semBanco);
    }

    return true; // cancel de uma sessao valida e ainda inacabada.
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
    if(seguranca_csrf_atualizador_sessao_legada_isento($_GESTOR['caminho'] ?? Array(), $_REQUEST)) return true;

    $cookieAuth = $_CONFIG['cookie-authname'] ?? '';
    if($cookieAuth === '' || !isset($_COOKIE[$cookieAuth])) return true;

    return gestor_csrf_validar(seguranca_csrf_token_requisicao());
}

?>
