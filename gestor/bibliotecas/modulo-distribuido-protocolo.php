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
        // Each row is bounded by its validity window; no persistent replay cache growth. req-213: the
        // sweep is sampled, so a screen with dozens of queries pays one write per query, not two.
        if (random_int(1, 25) === 1) {
            $stmt = $pdo->prepare('DELETE FROM distributed_exchanges WHERE expires_at<?');
            $stmt->execute([time() - 240]);
        }
        return $dados;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Installation registered at the central, by app_id. req-213: a project may keep the registry
 * outside the .env (a table with per-installation secrets) and declare the reader in
 * `$_CONFIG['modulo-distribuido']['installations-provider']`: callable(string $app): ?array with the
 * same keys of an .env entry (url, secret, modules, tables). A provider that knows the app_id is the
 * authority, even to say it is disabled (false); null falls back to the .env.
 */
function modulo_distribuido_instalacao($app, $slug) {
    if (!is_string($app) || $app === '') return false;
    $instalacao = null;
    $provedor = modulo_distribuido_config_get('modulo-distribuido.installations-provider', null);
    if (is_callable($provedor)) {
        try {
            $instalacao = call_user_func($provedor, $app);
        } catch (\Throwable $e) {
            error_log('MODULO-DISTRIBUIDO: installations provider failed');
            return false;
        }
        if ($instalacao === false) return false;
    }
    if ($instalacao === null) {
        $instalacoes = modulo_distribuido_config_get('modulo-distribuido.installations', []);
        $instalacao = $instalacoes[$app] ?? null;
    }
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
    // req-213: the second-factor and social sign-in paths reach this hook without the library that
    // issues the tokens. The hook manager swallows the error, and the user landed on the central
    // dashboard instead of returning to the installation.
    gestor_incluir_biblioteca('autenticacao');
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

/**
 * req-215: query string of a panel address at the customer's site, in canonical form, or ''.
 *
 * A deep link into a panel (`orders/details/?id=...`) and the return of an authorization at a third
 * party (`.../callback/?code=...&state=...`) arrive at the customer's site and continue inside the
 * iframe. Only plain `GET` parameters travel: the router's own parameter and anything that is not a
 * scalar are left out, and the result is rebuilt, never copied from the raw request.
 */
function modulo_distribuido_consulta_canonica($parametros, $limite = 2000) {
    if (!is_array($parametros)) return '';
    $limpos = [];
    foreach ($parametros as $chave => $valor) {
        if (!is_string($chave) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{0,63}$/D', $chave)) continue;
        if (stripos($chave, '_gestor') === 0 || stripos($chave, 'ajax') === 0) continue;
        if (!is_scalar($valor)) continue;
        $limpos[$chave] = (string)$valor;
    }
    $consulta = http_build_query($limpos, '', '&', PHP_QUERY_RFC3986);
    return strlen($consulta) <= $limite ? $consulta : '';
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
    // req-215: a module whose execution copy declares it has no panel is served by this site itself.
    $copia = modulo_distribuido_resolver_manifesto(($_GESTOR['modulos-path'] ?? '') . $slug . '/' . $slug . '.json');
    if (is_array($copia) && ($copia['scope'] ?? '') === 'distributed-execution' && ($copia['panel'] ?? true) === false) return false;
    $config = modulo_distribuido_canal_distribuido([], ['slug' => $slug]);
    $token = modulo_distribuido_token_sessao('modulo-distribuido-token', $slug);
    $guarda = modulo_distribuido_guardiao($config, ['token' => $token]);
    if (($guarda['resposta']['status'] ?? '') === 'error') modulo_distribuido_indisponivel();
    $consulta = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') ? modulo_distribuido_consulta_canonica($_GET) : '';
    if ($guarda['estado'] === 'login') {
        $url = modulo_distribuido_login_url($config, implode('/', $caminho) . '/' . ($consulta !== '' ? '?' . $consulta : ''));
        if (!$url) modulo_distribuido_indisponivel();
        gestor_redirecionar($url, '', true);
    }
    if ($guarda['estado'] !== 'iframe') {
        http_response_code(403);
        echo modulo_distribuido_shell('sem-permissao');
        exit;
    }
    $resposta = modulo_distribuido_enviar(['modulo' => $slug, 'app_id' => modulo_distribuido_config_get('modulo-distribuido.app-id'),
        'token' => $token, 'route' => implode('/', $caminho) . '/', 'query' => $consulta, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))],
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
    preg_match_all('/`[^`]*`|\'\'|[A-Za-z_@$][A-Za-z0-9_$]*|\d+(?:\.\d+)?|\S/', $mascara, $m);
    $tokens = $m[0];
    $tabelas = [];
    // req-211: every table reference must be a plain name or a derived SELECT. Anything else after a
    // table keyword (parenthesized name, ODBC escape, TABLE statement) fails closed, and so does a
    // comma inside a table list, whatever its position among the joins.
    $profundidade = 0;
    $lista = [];
    $total = count($tokens);
    for ($i = 0; $i < $total; $i++) {
        $token = $tokens[$i];
        $palavra = strtoupper($token);
        if ($token === '(') { $profundidade++; continue; }
        if ($token === ')') {
            unset($lista[$profundidade]);
            if (--$profundidade < 0) return false;
            continue;
        }
        if ($token === ',') {
            if (!empty($lista[$profundidade])) return false;
            continue;
        }
        if ($token === '{' || $token === '}') return false;
        if (in_array($palavra, ['USING', 'TABLE', 'LATERAL', 'PARTITION', 'HANDLER'], true)) return false;
        if (in_array($palavra, ['WHERE', 'GROUP', 'ORDER', 'LIMIT', 'HAVING', 'UNION', 'SET', 'VALUES'], true)) {
            unset($lista[$profundidade]);
            continue;
        }
        // FOR UPDATE and ON DUPLICATE KEY UPDATE name no table.
        if ($palavra === 'UPDATE' && in_array(strtoupper($tokens[$i - 1] ?? ''), ['FOR', 'KEY'], true)) continue;
        if (!in_array($palavra, ['FROM', 'JOIN', 'STRAIGHT_JOIN', 'UPDATE', 'INTO'], true)) continue;
        $abreLista = $palavra === 'FROM' || $palavra === 'UPDATE';
        $proximo = $tokens[$i + 1] ?? '';
        if ($proximo === '(') {
            if (strtoupper($tokens[$i + 2] ?? '') !== 'SELECT') return false;
            if ($abreLista) $lista[$profundidade] = true;
            continue;
        }
        $nome = ($proximo !== '' && $proximo[0] === '`') ? substr($proximo, 1, -1) : $proximo;
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $nome) || ($tokens[$i + 2] ?? '') === '.') return false;
        $tabelas[] = $nome;
        if ($abreLista) $lista[$profundidade] = true;
        $i++;
    }
    if ($profundidade !== 0) return false;
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
        $consulta = is_string($dados['query'] ?? null) ? $dados['query'] : '';
        gestor_redirecionar('_distributed/run/' . $id . '/' . $dados['route'] . ($consulta !== '' ? '?' . $consulta : ''));
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

/**
 * req-214: the installation on whose behalf the central is running the current module, or null.
 * A module that keeps a local side effect (a public page, a file) asks this before writing it at
 * the central: under a distributed context that effect belongs to the customer's site.
 */
function modulo_distribuido_contexto() {
    global $_GESTOR;
    $dados = $_GESTOR['distributed-context'] ?? null;
    return is_array($dados) ? $dados : null;
}

/** Public base URL of the site the current module is being managed for (the customer's, or this one). */
function modulo_distribuido_url_publica() {
    global $_GESTOR;
    $dados = modulo_distribuido_contexto();
    if ($dados) {
        $instalacao = modulo_distribuido_instalacao($dados['app_id'] ?? '', $dados['modulo'] ?? '');
        if ($instalacao) return $instalacao['url'] . '/';
    }
    return (string)($_GESTOR['url-full'] ?? '/');
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

/**
 * req-215: at the customer's site, the execution copy of a module only acts when the installation
 * contracted it.
 *
 * Every distributed host receives the same overlay, with the execution copy of every module. What
 * tells one customer from another is the list of contracted modules in its `.env`. A copy
 * (`"scope": "distributed-execution"` in its manifest) is active when the module is in that list, or
 * when one of the modules in its `active_with` is: the storefront has no panel of its own and comes
 * with the products, for example. Any other module is always active.
 */
function modulo_distribuido_execucao_ativa($modulo, $plugin = null): bool {
    global $_GESTOR;
    static $cache = [];
    if (!is_string($modulo) || $modulo === '' || !empty($plugin)) return true;
    $raiz = (string)($_GESTOR['modulos-path'] ?? '');
    $chave = $raiz . '|' . $modulo;
    if (isset($cache[$chave])) return $cache[$chave];
    if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/iD', $modulo)) return $cache[$chave] = true;
    $arquivo = $raiz . $modulo . '/' . $modulo . '.json';
    $manifesto = is_file($arquivo) ? json_decode((string)file_get_contents($arquivo), true) : null;
    if (!is_array($manifesto) || ($manifesto['scope'] ?? '') !== 'distributed-execution') return $cache[$chave] = true;
    $contratados = (array)modulo_distribuido_config_get('modulo-distribuido.modules', []);
    $chaves = array_merge([$modulo], array_filter((array)($manifesto['active_with'] ?? []), 'is_string'));
    return $cache[$chave] = (bool)array_intersect($chaves, $contratados);
}

/**
 * req-215: asks the customer's site to run a local routine of the module being managed.
 *
 * The panel runs at the central, but some effects only exist at the customer's site: an e-mail sent
 * with the site's identity, a public page, a refund with the local credential. The module declares
 * those routines in the manifest of its execution copy (`routines`); here the central asks for one
 * by name, over the signed channel, and receives what it returned.
 *
 * @param string $nome Routine name, as declared at the customer's site.
 * @param array  $args Positional arguments; they travel as JSON.
 *
 * @return array|null null outside a distributed context (the caller keeps its local behaviour);
 *                    ['ok' => true, 'retorno' => mixed] or ['ok' => false, 'erro' => string].
 */
function modulo_distribuido_rotina($nome, array $args = []) {
    global $_BANCO, $_GESTOR;
    if (!function_exists('banco_distribuido_ativo') || !banco_distribuido_ativo()) return null;
    $config = $_BANCO['distribuido'];
    $usuario = function_exists('gestor_usuario') ? gestor_usuario() : null;
    $payload = [
        'versao' => 1,
        'modulo' => $config['slug'] ?? '',
        'rotina' => (string)$nome,
        'args' => array_values($args),
        'linguagem' => $_GESTOR['linguagem-codigo'] ?? null,
        'usuario' => is_array($usuario) ? ['id' => (int)($usuario['id_usuarios'] ?? 0), 'nome' => (string)($usuario['nome'] ?? '')] : null,
        'timestamp' => time(),
        'nonce' => bin2hex(random_bytes(16)),
    ];
    $resposta = modulo_distribuido_enviar($payload, array_merge($config, ['acao' => 'rotina', 'timeout' => 30]));
    if (($resposta['status'] ?? '') !== 'ok') {
        return ['ok' => false, 'erro' => is_string($resposta['message'] ?? null) ? $resposta['message'] : 'routine-failed'];
    }
    return ['ok' => true, 'retorno' => $resposta['retorno'] ?? null];
}

/**
 * req-215: the routine a module's execution copy declares under that name, or null.
 *
 * Manifest: `"routines": {"<name>": {"function": "...", "file": "<file in the module folder>",
 * "libraries": ["<library id>", ...]}}`. Only what is declared may run: the name asked by the central
 * never becomes a function name by itself.
 *
 * @return array{function: string, file: ?string, libraries: string[]}|null
 */
function modulo_distribuido_rotina_resolver(array $manifesto, $nome) {
    if (!is_string($nome) || !preg_match('/^[a-z0-9][a-z0-9._-]{0,79}$/D', $nome)) return null;
    $rotina = $manifesto['routines'][$nome] ?? null;
    if (!is_array($rotina)) return null;
    $funcao = $rotina['function'] ?? null;
    if (!is_string($funcao) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,119}$/D', $funcao)) return null;
    $arquivo = $rotina['file'] ?? null;
    if ($arquivo !== null && (!is_string($arquivo) || !preg_match('/^[a-z0-9][a-z0-9._-]{0,119}\.php$/D', $arquivo))) return null;
    $bibliotecas = [];
    foreach ((array)($rotina['libraries'] ?? []) as $biblioteca) {
        if (!is_string($biblioteca) || !preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/D', $biblioteca)) return null;
        $bibliotecas[] = $biblioteca;
    }
    return ['function' => $funcao, 'file' => $arquivo, 'libraries' => $bibliotecas];
}

/**
 * req-215: runs, at the customer's site, the routine the central asked for.
 *
 * @param string $slug        Module being managed (already authenticated by the envelope).
 * @param array  $payload     Decoded body: `rotina`, `args`, `linguagem`, `usuario`.
 * @param string $modulosPath Folder of the modules of this installation.
 *
 * @return array Response ready for json_encode: {status: ok, retorno} or {status: error, message}.
 */
function modulo_distribuido_rotina_executar($slug, array $payload, $modulosPath) {
    global $_GESTOR;
    $args = $payload['args'] ?? [];
    if (!is_array($args)) return ['status' => 'error', 'message' => 'routine-invalid'];

    $pasta = rtrim((string)$modulosPath, '/\\') . '/' . $slug . '/';
    $manifesto = modulo_distribuido_resolver_manifesto($pasta . $slug . '.json');
    $rotina = is_array($manifesto) ? modulo_distribuido_rotina_resolver($manifesto, $payload['rotina'] ?? null) : null;
    if (!$rotina) return ['status' => 'error', 'message' => 'routine-denied'];

    $linguagem = $payload['linguagem'] ?? null;
    if (is_string($linguagem) && preg_match('/^[a-z]{2}(-[a-z]{2})?$/D', $linguagem)
        && (empty($_GESTOR['languages']) || in_array($linguagem, (array)$_GESTOR['languages'], true))) {
        $_GESTOR['linguagem-codigo'] = $linguagem;
    }
    // Who asked, for the routine that wants to record it. It is the central's operator, not a local user.
    $usuario = is_array($payload['usuario'] ?? null) ? $payload['usuario'] : [];
    $_GESTOR['distributed-routine'] = ['modulo' => $slug, 'rotina' => $payload['rotina'],
        'usuario' => ['id' => (int)($usuario['id'] ?? 0), 'nome' => mb_substr((string)($usuario['nome'] ?? ''), 0, 200)]];

    ob_start();
    try {
        foreach ($rotina['libraries'] as $biblioteca) {
            if (function_exists('gestor_incluir_biblioteca')) gestor_incluir_biblioteca($biblioteca);
        }
        if ($rotina['file'] !== null) {
            if (!is_file($pasta . $rotina['file'])) return ['status' => 'error', 'message' => 'routine-denied'];
            require_once $pasta . $rotina['file'];
        }
        if (!function_exists($rotina['function'])) return ['status' => 'error', 'message' => 'routine-denied'];
        $retorno = call_user_func_array($rotina['function'], array_values($args));
    } catch (\Throwable $e) {
        // The reason stays in the customer's log; the central only learns that it failed.
        error_log('MODULO-DISTRIBUIDO: routine ' . $slug . '/' . $payload['rotina'] . ' failed: ' . get_class($e) . ' at ' . basename($e->getFile()) . ':' . $e->getLine());
        return ['status' => 'error', 'message' => 'routine-failed'];
    } finally {
        ob_end_clean();
        unset($_GESTOR['distributed-routine']);
    }

    if (json_encode($retorno, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) === false) $retorno = null;
    return ['status' => 'ok', 'retorno' => $retorno];
}
