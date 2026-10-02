<?php
/** REQ-092: signed installation context and short-lived, single-use exchanges. */

function modulo_distribuido_pdo() {
    global $_BANCO;
    static $conexoes = [];
    $dsn = 'mysql:host=' . ($_BANCO['host'] ?? 'localhost') . ';dbname=' . ($_BANCO['nome'] ?? '') . ';charset=utf8mb4';
    $chave = hash('sha256', $dsn . ($_BANCO['usuario'] ?? ''));
    if (!isset($conexoes[$chave])) {
        $conexoes[$chave] = new PDO($dsn, $_BANCO['usuario'] ?? '', $_BANCO['senha'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]);
    }
    return $conexoes[$chave];
}

function modulo_distribuido_registro_emitir($tipo, array $dados, $secret, $ttl = 120, $pdo = null) {
    if ($secret === '') return false;
    $pdo = $pdo ?? modulo_distribuido_pdo();
    $codigo = bin2hex(random_bytes(32));
    $iv = random_bytes(12);
    $tag = '';
    $cifrado = openssl_encrypt(json_encode($dados, JSON_THROW_ON_ERROR), 'aes-256-gcm',
        hash('sha256', $secret, true), OPENSSL_RAW_DATA, $iv, $tag, $tipo);
    if ($cifrado === false) return false;
    $stmt = $pdo->prepare('INSERT INTO distributed_exchanges (id,kind,payload,expires_at,consumed) VALUES (?,?,?,?,0)');
    $stmt->execute([hash_hmac('sha256', $codigo, $secret), $tipo, base64_encode($iv . $tag . $cifrado), time() + $ttl]);
    return $codigo;
}

function modulo_distribuido_registro_consumir($codigo, $tipo, $secret, $pdo = null) {
    if (!is_string($codigo) || !preg_match('/^[a-f0-9]{64}$/D', $codigo) || $secret === '') return false;
    $pdo = $pdo ?? modulo_distribuido_pdo();
    $id = hash_hmac('sha256', $codigo, $secret);
    $stmt = $pdo->prepare('UPDATE distributed_exchanges SET consumed=1 WHERE id=? AND kind=? AND consumed=0 AND expires_at>=?');
    $stmt->execute([$id, $tipo, time()]);
    if ($stmt->rowCount() !== 1) return false;
    $stmt = $pdo->prepare('SELECT payload FROM distributed_exchanges WHERE id=?');
    $stmt->execute([$id]);
    $bruto = base64_decode((string)$stmt->fetchColumn(), true);
    if ($bruto === false || strlen($bruto) < 28) return false;
    $json = openssl_decrypt(substr($bruto, 28), 'aes-256-gcm', hash('sha256', $secret, true),
        OPENSSL_RAW_DATA, substr($bruto, 0, 12), substr($bruto, 12, 16), $tipo);
    $dados = $json === false ? null : json_decode($json, true);
    return is_array($dados) ? $dados : false;
}

/** HMAC is checked before the nonce is consumed; duplicates fail atomically. */
function modulo_distribuido_validar_envelope($corpo, $assinatura, $secret, $slug, $pdo = null) {
    if (!is_string($corpo) || strlen($corpo) > 2097152) return false;
    if (!modulo_distribuido_verificar_assinatura($corpo, $assinatura, $secret)) return false;
    $dados = json_decode((string)$corpo, true);
    if (!is_array($dados) || !is_int($dados['timestamp'] ?? null)
        || abs(time() - $dados['timestamp']) > 120
        || !is_string($dados['nonce'] ?? null) || !preg_match('/^[a-f0-9]{16,64}$/D', $dados['nonce'])
        || ($dados['modulo'] ?? null) !== $slug) return false;
    try {
        $pdo = $pdo ?? modulo_distribuido_pdo();
        $stmt = $pdo->prepare('INSERT INTO distributed_exchanges (id,kind,payload,expires_at,consumed) VALUES (?,?,?,?,1)');
        $stmt->execute([hash_hmac('sha256', 'nonce:' . $dados['nonce'], $secret), 'nonce', '', time() + 240]);
        // Each row is bounded by its validity window; no persistent replay cache growth.
        $stmt = $pdo->prepare('DELETE FROM distributed_exchanges WHERE expires_at<?');
        $stmt->execute([time() - 240]);
        return $dados;
    } catch (\Throwable $e) {
        return false;
    }
}

function modulo_distribuido_instalacao($app, $slug) {
    $instalacoes = modulo_distribuido_config_get('modulo-distribuido.installations', []);
    $instalacao = is_string($app) ? ($instalacoes[$app] ?? null) : null;
    if (!is_array($instalacao) || empty($instalacao['secret']) || empty($instalacao['url'])
        || !in_array($slug, $instalacao['modules'] ?? [], true)) return false;
    $url = parse_url($instalacao['url']);
    if (!$url || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
        || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) return false;
    $instalacao['url'] = rtrim($instalacao['url'], '/');
    return $instalacao;
}

/** The distributed host sends the browser to the central system's official sign-in. */
function modulo_distribuido_login_url(array $config, $retorno) {
    $app = modulo_distribuido_config_get('modulo-distribuido.app-id', '');
    $url = modulo_distribuido_config_get('modulo-distribuido.url', '');
    if ($app === '' || $url === '' || empty($config['secret'])) return false;
    $estado = bin2hex(random_bytes(32));
    $dados = ['contexto' => 'modulo-distribuido', 'app_id' => $app, 'distribuido_url' => rtrim($url, '/'),
        'modulo' => $config['slug'], 'state' => $estado, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
    gestor_sessao_variavel('distributed-login', ['state' => $estado, 'retorno' => $retorno, 'modulo' => $config['slug'], 'expires_at' => time() + 900]);
    $corpo = json_encode($dados, JSON_UNESCAPED_SLASHES);
    return rtrim($config['central-url'], '/') . '/signin/?' . http_build_query([
        'distributed_context' => base64_encode($corpo), 'distributed_signature' => modulo_distribuido_assinar($corpo, $config['secret'])]);
}

function modulo_distribuido_login_contexto() {
    if (isset($_GET['distributed_context'])) {
        $corpo = base64_decode((string)$_GET['distributed_context'], true);
        $dados = $corpo === false ? null : json_decode($corpo, true);
        $instalacao = is_array($dados) ? modulo_distribuido_instalacao($dados['app_id'] ?? '', $dados['modulo'] ?? '') : false;
        if (!$instalacao || ($dados['distribuido_url'] ?? '') !== $instalacao['url']
            || ($dados['contexto'] ?? '') !== 'modulo-distribuido'
            || !preg_match('/^[a-f0-9]{64}$/D', (string)($dados['state'] ?? ''))
            || !modulo_distribuido_validar_envelope($corpo, $_GET['distributed_signature'] ?? '', $instalacao['secret'], $dados['modulo'])) {
            http_response_code(403);
            exit;
        }
        $dados['expires_at'] = time() + 900;
        gestor_sessao_variavel('distributed-login', $dados);
        // Clean the URL before captcha, OAuth providers or any other outbound navigation.
        gestor_redirecionar('signin/');
    }
    $dados = gestor_sessao_variavel('distributed-login');
    if (!is_array($dados) || ($dados['expires_at'] ?? 0) < time()) return false;
    return $dados;
}

/** Called only after the complete official login, including its second factor. */
function modulo_distribuido_login_sucesso($id_usuarios) {
    $dados = gestor_sessao_variavel('distributed-login');
    if (!is_array($dados)) return;
    $instalacao = modulo_distribuido_instalacao($dados['app_id'] ?? '', $dados['modulo'] ?? '');
    if (!$instalacao || ($dados['expires_at'] ?? 0) < time()) {
        gestor_sessao_variavel_del('distributed-login');
        return;
    }
    $tokens = autenticacao_distribuido_gerar_tokens($id_usuarios);
    if (!$tokens) modulo_distribuido_indisponivel();
    $codigo = modulo_distribuido_registro_emitir('login', ['tokens' => $tokens, 'contexto' => $dados], $instalacao['secret']);
    if (!$codigo) modulo_distribuido_indisponivel();
    gestor_sessao_variavel_del('distributed-login');
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    gestor_redirecionar($instalacao['url'] . '/_distributed/callback/?' . http_build_query([
        'code' => $codigo, 'state' => $dados['state'], 'modulo' => $dados['modulo']]), '', true);
}

/** Middleware for the distributed overlay, before the local page/permission router. */
function modulo_distribuido_proxy_rota() {
    global $_GESTOR;
    $modulos = modulo_distribuido_config_get('modulo-distribuido.modules', []);
    $caminho = $_GESTOR['caminho'] ?? [];
    $slug = $caminho[0] ?? '';
    if ($slug === '_distributed' && ($caminho[1] ?? '') === 'callback') {
        $pendente = gestor_sessao_variavel('distributed-login');
        if (!is_array($pendente) || ($pendente['expires_at'] ?? 0) < time()
            || !is_string($_GET['state'] ?? null) || !hash_equals($pendente['state'], $_GET['state'])
            || ($pendente['modulo'] ?? '') !== ($_GET['modulo'] ?? '')) { http_response_code(403); exit; }
        $config = modulo_distribuido_canal_distribuido([], ['slug' => $pendente['modulo']]);
        $payload = ['modulo' => $config['slug'], 'app_id' => modulo_distribuido_config_get('modulo-distribuido.app-id'),
            'code' => $_GET['code'] ?? '', 'state' => $pendente['state'], 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
        $resposta = modulo_distribuido_enviar($payload, array_merge($config, ['acao' => 'exchange']));
        $envelope = $resposta['data'] ?? [];
        $corpo = isset($envelope['body']) ? base64_decode($envelope['body'], true) : false;
        $tokens = $corpo === false ? false : modulo_distribuido_validar_envelope($corpo, $envelope['signature'] ?? '', $config['secret'], $config['slug']);
        if (!$tokens || ($tokens['state'] ?? '') !== $pendente['state'] || empty($tokens['access_token'])) modulo_distribuido_indisponivel();
        modulo_distribuido_persistir_token('modulo-distribuido-token', $tokens);
        gestor_sessao_variavel_del('distributed-login');
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        gestor_redirecionar($pendente['retorno']);
    }
    if (!in_array($slug, $modulos, true)) return false;
    $config = modulo_distribuido_canal_distribuido([], ['slug' => $slug]);
    $token = modulo_distribuido_token_sessao('modulo-distribuido-token', $slug);
    $guarda = modulo_distribuido_guardiao($config, ['token' => $token]);
    if (($guarda['resposta']['status'] ?? '') === 'error') modulo_distribuido_indisponivel();
    if ($guarda['estado'] === 'login') {
        $url = modulo_distribuido_login_url($config, implode('/', $caminho) . '/');
        if (!$url) modulo_distribuido_indisponivel();
        gestor_redirecionar($url, '', true);
    }
    if ($guarda['estado'] !== 'iframe') {
        http_response_code(403);
        echo modulo_distribuido_shell('sem-permissao');
        exit;
    }
    $resposta = modulo_distribuido_enviar(['modulo' => $slug, 'app_id' => modulo_distribuido_config_get('modulo-distribuido.app-id'),
        'token' => $token, 'route' => implode('/', $caminho) . '/', 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))],
        array_merge($config, ['acao' => 'iframe-ticket']));
    $ticket = $resposta['data']['ticket'] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/D', (string)$ticket)) modulo_distribuido_indisponivel();
    $src = rtrim($config['central-url'], '/') . '/_distributed/embed/?' . http_build_query([
        'ticket' => $ticket, 'app_id' => modulo_distribuido_config_get('modulo-distribuido.app-id'), 'modulo' => $slug]);
    $_GESTOR['pagina'] = modulo_distribuido_shell('iframe', $src);
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    echo $_GESTOR['pagina'];
    exit;
}

function modulo_distribuido_shell($estado, $src = null, array $textosExtra = []) {
    global $_GESTOR;
    $componente = gestor_componente(['id' => 'modulo-distribuido-app', 'return_css' => true]);
    $textos = modulo_distribuido_textos(null, $textosExtra);
    $html = modulo_distribuido_render_componente($componente['html'] ?? '', $estado, $src, $textos);
    $shell = gestor_componente(['id' => 'modulo-distribuido-shell']);
    return str_replace(['#distributed-language#', '#distributed-title#', '#distributed-css#', '#distributed-content#'],
        [htmlspecialchars($_GESTOR['linguagem-codigo'] ?? 'pt-br', ENT_QUOTES, 'UTF-8'),
         htmlspecialchars($textos['c2f-md-iframe-title'], ENT_QUOTES, 'UTF-8'), $componente['css'] ?? '', $html], (string)$shell);
}

function modulo_distribuido_indisponivel() {
    http_response_code(503);
    header('Cache-Control: no-store');
    echo modulo_distribuido_shell('login', null, ['c2f-md-login-subtitle' => gestor_variaveis(['id' => 'c2f-md-unavailable'])]);
    exit;
}

/** Extract every CRUD table after masking values; schema-qualified names fail closed. */
function modulo_distribuido_sql_tabelas($sql) {
    if (!modulo_distribuido_sql_segura($sql)) return false;
    $mascara = preg_replace('/\'(?:\\\\.|\'\'|[^\'\\\\])*\'|"(?:\\\\.|""|[^"\\\\])*"/s', "''", (string)$sql);
    if (preg_match('/\bUSING\b|\bUPDATE\b[^;]*?,[^;]*?\bSET\b|\b(?:FROM|JOIN|UPDATE|INTO)\s*\'\'/i', $mascara)) return false;
    preg_match_all('/\b(?:FROM|JOIN|UPDATE|INTO)\s+(`?[a-zA-Z_][a-zA-Z0-9_]*`?)(\s*\.)?/i', $mascara, $matches, PREG_SET_ORDER);
    $tabelas = [];
    foreach ($matches as $match) {
        if (!empty($match[2])) return false;
        $tabelas[] = trim($match[1], '`');
    }
    // Comma joins, CTEs and other ambiguous forms are not accepted by this channel.
    if (preg_match('/\bFROM\b(?:(?!\b(?:WHERE|GROUP|ORDER|LIMIT|JOIN|SET|VALUES)\b)[^;])*?,\s*`?[a-zA-Z_]/i', $mascara)) return false;
    return array_values(array_unique($tabelas));
}

function modulo_distribuido_sql_autorizada($sql, array $tabelas) {
    $usadas = modulo_distribuido_sql_tabelas($sql);
    return is_array($usadas) && count($usadas) > 0 && count(array_diff($usadas, $tabelas)) === 0;
}

/** Route names in the prefix isolate concurrent browser tabs without changing modules. */
function modulo_distribuido_prefixo_normalizar() {
    global $_GESTOR;
    $caminho = $_GESTOR['caminho'] ?? [];
    if (($caminho[0] ?? '') !== '_distributed' || ($caminho[1] ?? '') !== 'run') return;
    $id = $caminho[2] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/D', $id)) { http_response_code(403); exit; }
    $_GESTOR['distributed-context-id'] = $id;
    $_GESTOR['caminho'] = array_slice($caminho, 3);
    // API/gateway controllers dispatch before session validation and must never inherit a browser context.
    if (in_array($_GESTOR['caminho'][0] ?? '', ['_api', '_gateways', '_distributed'], true)) { http_response_code(403); exit; }
    $barra = substr($_GESTOR['caminho-total'] ?? '', -1) === '/' ? '/' : '';
    $_GESTOR['caminho-total'] = implode('/', $_GESTOR['caminho']) . $barra;
    // Public vendor assets may be served by the web server outside the PHP tree.
    // Keep their canonical origin so relative font URLs reach the same handler.
    if (($_GESTOR['caminho'][0] ?? '') === 'vendor'
        && !in_array('..', $_GESTOR['caminho'], true)
        && in_array(strtolower(pathinfo($_GESTOR['caminho-total'], PATHINFO_EXTENSION)),
            ['css', 'js', 'woff', 'woff2', 'ttf', 'eot', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
        header('Location: ' . rtrim($_GESTOR['url-raiz'], '/') . '/' . implode('/', array_map('rawurlencode', $_GESTOR['caminho'])));
        exit;
    }
    foreach (['url-raiz', 'url-full', 'url-full-http'] as $chave) {
        if (isset($_GESTOR[$chave])) $_GESTOR[$chave] = rtrim($_GESTOR[$chave], '/') . '/_distributed/run/' . $id . '/';
    }
}

function modulo_distribuido_cookie_contexto() {
    global $_GESTOR, $_CONFIG;
    $caminho = $_GESTOR['caminho'] ?? [];
    if (empty($_GESTOR['distributed-context-id'])
        && !(($caminho[0] ?? '') === '_distributed' && ($caminho[1] ?? '') === 'embed')) return;
    $_GESTOR['distributed-cookie'] = true;
    foreach (['session-authname', 'cookie-authname', 'cookie-authprofile', 'cookie-verify'] as $chave) {
        $_CONFIG[$chave] .= '-distributed';
    }
}

function modulo_distribuido_central_rota() {
    global $_GESTOR;
    $caminho = $_GESTOR['caminho'] ?? [];
    if (($caminho[0] ?? '') === '_distributed' && ($caminho[1] ?? '') === 'embed') {
        $slug = $_GET['modulo'] ?? '';
        $instalacao = modulo_distribuido_instalacao($_GET['app_id'] ?? '', $slug);
        if (!$instalacao) { http_response_code(403); exit; }
        $dados = modulo_distribuido_registro_consumir($_GET['ticket'] ?? '', 'iframe', $instalacao['secret']);
        if (!$dados || ($dados['app_id'] ?? '') !== $_GET['app_id'] || ($dados['modulo'] ?? '') !== $slug) { http_response_code(403); exit; }
        gestor_incluir_biblioteca('oauth2');
        gestor_incluir_biblioteca('autenticacao');
        $permissao = modulo_distribuido_middleware_central($dados['token'], $slug);
        if ($permissao['estado'] !== 'permitido' || $permissao['id_usuarios'] !== $dados['id_usuarios']) { http_response_code(403); exit; }
        gestor_incluir_biblioteca('usuario');
        usuario_gerar_token_autorizacao(['id_usuarios' => $dados['id_usuarios'], 'sessao' => true]);
        $id = bin2hex(random_bytes(32));
        $dados['expires_at'] = time() + 900;
        gestor_sessao_variavel('distributed-context-' . $id, $dados);
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store');
        gestor_redirecionar('_distributed/run/' . $id . '/' . $dados['route']);
    }
    if (empty($_GESTOR['distributed-context-id'])) return;
    $dados = gestor_sessao_variavel('distributed-context-' . $_GESTOR['distributed-context-id']);
    if (!is_array($dados) || ($dados['expires_at'] ?? 0) < time()) { http_response_code(401); exit; }
    $instalacao = modulo_distribuido_instalacao($dados['app_id'], $dados['modulo']);
    if (!$instalacao) { http_response_code(403); exit; }
    $_GESTOR['distributed-context'] = $dados;
    header_remove('X-Frame-Options');
    $csp = modulo_distribuido_config_get('security.csp', '');
    $csp = preg_replace('/(?:^|;)\s*frame-ancestors\s+[^;]*/i', '', $csp);
    header("Content-Security-Policy: " . trim($csp, '; ') . "; frame-ancestors 'self' " . $instalacao['url']);
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
}

/** Activate only around execution of the original authorized module. */
function modulo_distribuido_modulo_iniciar($slug) {
    global $_GESTOR;
    $dados = $_GESTOR['distributed-context'] ?? null;
    if (!$dados) return false;
    $instalacao = modulo_distribuido_instalacao($dados['app_id'], $slug);
    gestor_incluir_biblioteca('oauth2');
    gestor_incluir_biblioteca('autenticacao');
    $permissao = modulo_distribuido_middleware_central($dados['token'], $slug);
    if (!$instalacao || $permissao['estado'] !== 'permitido' || $permissao['id_usuarios'] !== $dados['id_usuarios']) { http_response_code(403); exit; }
    banco_distribuido_iniciar(['slug' => $slug, 'endpoint' => $instalacao['url'] . '/_api',
        'secret' => $instalacao['secret'], 'tables' => $instalacao['tables'] ?? []]);
    return true;
}
