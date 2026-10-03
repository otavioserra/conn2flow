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
/** req-216: reserved channel slug for account-level actions (no module behind it). */
const MODULO_DISTRIBUIDO_SLUG_CONTA = '_conta';

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
        || ($slug !== MODULO_DISTRIBUIDO_SLUG_CONTA && !in_array($slug, $instalacao['modules'] ?? [], true))) return false;
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

/**
 * req-216: the distributed catalog that ships with this installation's package
 * (`project/distributed-modules.json`: `modules` with a panel at the central, `tables` the channel may
 * reach). Every customer receives the full package; who may open a panel is decided by the central,
 * by the user's profile. The `.env` lists stay optional and win when present.
 *
 * @return array{modules: string[], tables: string[]}
 */
function modulo_distribuido_catalogo_local() {
    global $_GESTOR;
    static $cache = [];
    $arquivo = (string)($_GESTOR['ROOT_PATH'] ?? '') . 'project/distributed-modules.json';
    if (isset($cache[$arquivo])) return $cache[$arquivo];
    $dados = is_file($arquivo) ? json_decode((string)file_get_contents($arquivo), true) : null;
    $limpa = static function ($lista) {
        return array_values(array_filter((array)$lista, static function ($v) { return is_string($v) && preg_match('/^[a-z0-9_-]{1,64}$/D', $v); }));
    };
    return $cache[$arquivo] = ['modules' => $limpa($dados['modules'] ?? []), 'tables' => $limpa($dados['tables'] ?? [])];
}

/** req-216: modules with a panel at the central, as this installation knows them. */
function modulo_distribuido_modulos_locais() {
    $env = (array)modulo_distribuido_config_get('modulo-distribuido.modules', []);
    if ($env) return array_values($env);
    // Só um site de cliente (com `app-id`) é host distribuído: o Central também guarda o catálogo,
    // para o cadastro das instalações, e não pode tratar os próprios módulos como de outro site.
    if ((string)modulo_distribuido_config_get('modulo-distribuido.app-id', '') === '') return [];
    return modulo_distribuido_catalogo_local()['modules'];
}

/** req-216: business tables the central channel may reach in this installation. */
function modulo_distribuido_tabelas_locais() {
    $env = (array)modulo_distribuido_config_get('modulo-distribuido.tables', []);
    if ($env) return array_values($env);
    // Só um site de cliente (com `app-id`) é host distribuído: o Central também guarda o catálogo,
    // para o cadastro das instalações, e não pode tratar os próprios módulos como de outro site.
    if ((string)modulo_distribuido_config_get('modulo-distribuido.app-id', '') === '') return [];
    return modulo_distribuido_catalogo_local()['tables'];
}

const MODULO_DISTRIBUIDO_ESTADOS = ['ativo', 'carencia', 'suspenso', 'encerrado'];
const MODULO_DISTRIBUIDO_CONTA_TTL = 600;

/**
 * req-216 (central): the account behind an installation, as the project decides it.
 *
 * `$_CONFIG['modulo-distribuido']['account-provider']`: callable(string $app): array with
 * `estado` (ativo | carencia | suspenso | encerrado), `modulos` (modules of the owner's plan, or
 * null for all), `destino` (absolute URL where a user without access is sent: the project's
 * subscription screen) and `mensagem`. No provider, or a provider that fails: active, all modules.
 * Billing never lives in the core.
 */
function modulo_distribuido_conta($app) {
    $padrao = ['estado' => 'ativo', 'modulos' => null, 'destino' => null, 'mensagem' => ''];
    $provedor = modulo_distribuido_config_get('modulo-distribuido.account-provider', null);
    if (!is_string($app) || $app === '' || !is_callable($provedor)) return $padrao;
    try {
        $conta = call_user_func($provedor, $app);
    } catch (\Throwable $e) {
        error_log('MODULO-DISTRIBUIDO: account provider failed');
        return $padrao;
    }
    if (!is_array($conta)) return $padrao;
    $estado = in_array($conta['estado'] ?? null, MODULO_DISTRIBUIDO_ESTADOS, true) ? $conta['estado'] : 'ativo';
    $modulos = isset($conta['modulos']) && is_array($conta['modulos']) ? array_values(array_filter($conta['modulos'], 'is_string')) : null;
    $destino = is_string($conta['destino'] ?? null) && preg_match('#^https?://#i', $conta['destino']) ? $conta['destino'] : null;
    return ['estado' => $estado, 'modulos' => $modulos, 'destino' => $destino, 'mensagem' => mb_substr((string)($conta['mensagem'] ?? ''), 0, 300)];
}

/**
 * req-216 (customer): the account state and the plan's modules, asked to the central over the
 * signed channel and kept here. Refreshed at most every MODULO_DISTRIBUIDO_CONTA_TTL seconds; when
 * the central does not answer, the last known state stays. A module that has been in the plan once
 * stays in `ativados`: unpaid bills take the panel away, never the running site.
 *
 * @param array $opcoes 'forcar' => true ignores the cache; 'transporte' and 'pdo' for tests.
 * @return array{estado: string, modulos: string[], ativados: string[], destino: ?string, atualizado_em: int, conhecido: bool}
 */
function modulo_distribuido_conta_local(array $opcoes = []) {
    static $memoria = null;
    if ($memoria !== null && empty($opcoes['forcar'])) return $memoria;

    $vazio = ['estado' => 'ativo', 'modulos' => [], 'ativados' => [], 'destino' => null, 'atualizado_em' => 0, 'valido_ate' => 0, 'conhecido' => false];
    $app = (string)modulo_distribuido_config_get('modulo-distribuido.app-id', '');
    $secret = (string)modulo_distribuido_config_get('modulo-distribuido.secret', '');
    if ($app === '' || $secret === '') return $memoria = $vazio;

    try { $pdo = $opcoes['pdo'] ?? modulo_distribuido_pdo(); } catch (\Throwable $e) { return $memoria = $vazio; }
    // Mesmo tamanho dos demais identificadores da tabela (64); o `kind` separa do resto.
    $id = hash('sha256', 'conta|' . $app);
    $guardado = null;
    try {
        $stmt = $pdo->prepare('SELECT payload FROM distributed_exchanges WHERE id=? AND kind=?');
        $stmt->execute([$id, 'conta']);
        $guardado = json_decode((string)$stmt->fetchColumn(), true);
    } catch (\Throwable $e) {
        $guardado = null;
    }
    $atual = is_array($guardado) ? array_merge($vazio, $guardado) : $vazio;
    if (empty($opcoes['forcar']) && $atual['conhecido'] && $atual['valido_ate'] > time()) return $memoria = $atual;

    $config = modulo_distribuido_canal_distribuido([], ['slug' => MODULO_DISTRIBUIDO_SLUG_CONTA]);
    if (isset($opcoes['transporte'])) $config['transporte'] = $opcoes['transporte'];
    $config['timeout'] = 5;
    $payload = ['modulo' => MODULO_DISTRIBUIDO_SLUG_CONTA, 'app_id' => $app, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
    $resposta = modulo_distribuido_enviar($payload, array_merge($config, ['acao' => 'estado']));
    $envelope = $resposta['data'] ?? [];
    $corpo = isset($envelope['body']) ? base64_decode((string)$envelope['body'], true) : false;
    $dados = $corpo === false ? false : modulo_distribuido_validar_envelope($corpo, $envelope['signature'] ?? '', $secret, MODULO_DISTRIBUIDO_SLUG_CONTA, $pdo);

    if (is_array($dados) && in_array($dados['estado'] ?? null, MODULO_DISTRIBUIDO_ESTADOS, true)) {
        $modulos = array_values(array_filter((array)($dados['modulos'] ?? []), 'is_string'));
        $atual = [
            'estado' => $dados['estado'],
            'modulos' => $modulos,
            'ativados' => array_values(array_unique(array_merge((array)$atual['ativados'], $modulos))),
            'destino' => is_string($dados['destino'] ?? null) ? $dados['destino'] : null,
            'atualizado_em' => time(),
            'valido_ate' => time() + MODULO_DISTRIBUIDO_CONTA_TTL,
            'conhecido' => true,
        ];
    } else {
        // The central did not answer (or answered something unsigned): keep the last known state
        // and try again in a minute, so a slow central does not slow every page down.
        $atual['valido_ate'] = time() + 60;
        if (!$atual['conhecido']) {
            $env = (array)modulo_distribuido_config_get('modulo-distribuido.modules', []);
            $atual['modulos'] = $atual['ativados'] = array_values($env);
        }
    }
    try {
        $stmt = $pdo->prepare('REPLACE INTO distributed_exchanges (id,kind,payload,expires_at,consumed) VALUES (?,?,?,?,1)');
        // The sweep of expired exchanges must never take this row: it carries the activated modules.
        $stmt->execute([$id, 'conta', json_encode($atual), time() + 315360000]);
    } catch (\Throwable $e) {
        error_log('MODULO-DISTRIBUIDO: account cache not written');
    }
    return $memoria = $atual;
}

/** req-216: account state of this installation (`ativo` where nothing says otherwise). */
function modulo_distribuido_conta_estado() {
    $conta = modulo_distribuido_conta_local();
    return in_array($conta['estado'], MODULO_DISTRIBUIDO_ESTADOS, true) ? $conta['estado'] : 'ativo';
}

/** req-216: may this site take a new sale (order, subscription)? Not when the account is suspended or closed. */
function modulo_distribuido_aceita_venda_nova() {
    return !in_array(modulo_distribuido_conta_estado(), ['suspenso', 'encerrado'], true);
}

/** Middleware for the distributed overlay, before the local page/permission router. */
function modulo_distribuido_proxy_rota() {
    global $_GESTOR;
    $modulos = modulo_distribuido_modulos_locais();
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
        // req-216: without access (profile, unpaid or closed account) the user goes where the central
        // says: the project's subscription screen, where plans, payment and cancellation live.
        $destino = $guarda['resposta']['data']['destino'] ?? null;
        if (is_string($destino) && preg_match('#^https?://#i', $destino)) {
            header('Cache-Control: no-store');
            gestor_redirecionar($destino, '', true);
        }
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
/** Static folders served outside the PHP tree; under the iframe prefix they go back to their own address. */
const MODULO_DISTRIBUIDO_PASTAS_ESTATICAS = ['vendor', 'favicon', 'images'];

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
    // Keep their canonical origin so relative font URLs reach the same handler. req-218: the same for the
    // static images the layouts point to with `url-raiz` (the portal logo under `favicon/`, `images/`).
    if (in_array($_GESTOR['caminho'][0] ?? '', MODULO_DISTRIBUIDO_PASTAS_ESTATICAS, true)
        && !in_array('..', $_GESTOR['caminho'], true)
        && in_array(strtolower(pathinfo($_GESTOR['caminho-total'], PATHINFO_EXTENSION)),
            ['css', 'js', 'woff', 'woff2', 'ttf', 'eot', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
        // The router lowercases `caminho`; the file name keeps its case in the original address.
        $original = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $prefixo = '/_distributed/run/' . $id . '/';
        $destino = strpos($original, $prefixo) !== false ? str_replace($prefixo, '/', $original) : '';
        // Never another host: one leading slash, no scheme.
        if ($destino === '' || $destino[0] !== '/' || strpos($destino, '//') === 0 || strpos($destino, '\\') !== false) {
            $destino = rtrim($_GESTOR['url-raiz'], '/') . '/' . implode('/', array_map('rawurlencode', $_GESTOR['caminho']));
        }
        header('Location: ' . $destino);
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
    // req-216: a closed account leaves the panel for the subscription screen; a suspended one sees,
    // but does not change.
    $conta = modulo_distribuido_conta($dados['app_id']);
    if ($conta['estado'] === 'encerrado') modulo_distribuido_sair_para($conta['destino']);
    $somenteLeitura = $conta['estado'] === 'suspenso';
    $_GESTOR['distributed-account'] = $conta;
    if ($somenteLeitura && modulo_distribuido_pedido_de_escrita()) {
        modulo_distribuido_recusar_escrita($conta);
    }
    banco_distribuido_iniciar(['slug' => $slug, 'endpoint' => $instalacao['url'] . '/_api', 'peer' => $dados['app_id'],
        'secret' => $instalacao['secret'], 'tables' => $instalacao['tables'] ?? [], 'somente-leitura' => $somenteLeitura]);
    return true;
}

/**
 * req-216: does this panel request change something? A form post or an option that writes through a
 * link (delete, status, clone). AJAX posts are reads most of the time (lists, searches); the ones that
 * write are refused by the channel itself.
 */
function modulo_distribuido_pedido_de_escrita() {
    global $_GESTOR;
    if (in_array((string)($_GESTOR['opcao'] ?? ''), ['excluir', 'status', 'clonar'], true)) return true;
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && empty($_GESTOR['ajax']);
}

/**
 * req-216: an address outside the iframe context, written so that the panel's own rewriting of
 * central addresses (which sends them through `/_distributed/run/<id>/`) does not reach it. The
 * browser decodes `&#47;` back to `/`.
 */
function modulo_distribuido_href_externo($url) {
    return str_replace('://', ':&#47;&#47;', htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8'));
}

/** req-216: answer a write while the panel is read-only, in the shape the request expects. */
function modulo_distribuido_recusar_escrita(array $conta) {
    global $_GESTOR;
    $texto = $conta['mensagem'] !== '' ? $conta['mensagem'] : 'Painel em modo de visualização: regularize a assinatura para alterar.';
    if (!empty($_GESTOR['ajax'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => $texto, 'read_only' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(403);
    header('Cache-Control: no-store');
    $link = $conta['destino'] ? '<p><a href="' . modulo_distribuido_href_externo($conta['destino']) . '" target="_top">Minha assinatura</a></p>' : '';
    echo '<!doctype html><meta charset="utf-8"><title>Somente visualização</title><body style="font-family:sans-serif;padding:2rem">'
        . '<p>' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</p>' . $link . '<p><a href="javascript:history.back()">Voltar</a></p></body>';
    exit;
}

/** req-216: leave the iframe for the given address (top window), or answer 403 without one. */
function modulo_distribuido_sair_para($destino) {
    global $_GESTOR;
    header('Cache-Control: no-store');
    if (!is_string($destino) || !preg_match('#^https?://#i', $destino)) { http_response_code(403); exit; }
    if (!empty($_GESTOR['ajax'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'redirect' => $destino], JSON_UNESCAPED_SLASHES);
        exit;
    }
    // `json_encode` sem JSON_UNESCAPED_SLASHES escreve `\/`: a reescrita de endereços não o alcança.
    echo '<!doctype html><meta charset="utf-8"><script>window.top.location.href=' . json_encode($destino, JSON_HEX_TAG) . ';</script>'
        . '<a href="' . modulo_distribuido_href_externo($destino) . '" target="_top">Minha assinatura</a>';
    exit;
}

/**
 * req-216: notice at the top of the panel when the account is in grace or read-only. Called after the
 * module ran, while the page is still in `$_GESTOR['pagina']`.
 */
function modulo_distribuido_aviso_conta() {
    global $_GESTOR;
    $conta = $_GESTOR['distributed-account'] ?? null;
    if (!is_array($conta) || !in_array($conta['estado'], ['carencia', 'suspenso'], true) || !empty($_GESTOR['ajax'])) return;
    $texto = $conta['mensagem'] !== '' ? $conta['mensagem']
        : ($conta['estado'] === 'suspenso' ? 'Assinatura suspensa: o painel está em modo de visualização.' : 'Há um pagamento pendente na sua assinatura.');
    $link = $conta['destino'] ? ' <a href="' . modulo_distribuido_href_externo($conta['destino']) . '" target="_top" style="color:inherit;font-weight:600;text-decoration:underline">Minha assinatura</a>' : '';
    $aviso = '<div data-c2f-conta="' . $conta['estado'] . '" role="status" style="position:sticky;top:0;z-index:9999;padding:.6rem 1rem;background:'
        . ($conta['estado'] === 'suspenso' ? '#fef3c7;color:#78350f' : '#e0f2fe;color:#0c4a6e') . ';font:14px/1.4 sans-serif">'
        . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . $link . '</div>';
    $_GESTOR['pagina'] = $aviso . (string)($_GESTOR['pagina'] ?? '');
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
    // req-216: active when the module (or one of `active_with`) is in the owner's plan, or has been.
    $conta = modulo_distribuido_conta_local();
    $habilitados = array_merge((array)$conta['modulos'], (array)$conta['ativados'], (array)modulo_distribuido_config_get('modulo-distribuido.modules', []));
    $chaves = array_merge([$modulo], array_filter((array)($manifesto['active_with'] ?? []), 'is_string'));
    return $cache[$chave] = (bool)array_intersect($chaves, $habilitados);
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
function modulo_distribuido_rotina($nome, array $args = [], array $opcoes = []) {
    global $_BANCO, $_GESTOR;
    if (!function_exists('banco_distribuido_ativo') || !banco_distribuido_ativo()) return null;
    $config = $_BANCO['distribuido'];
    // req-216: a read-only panel (account suspended) may only ask for routines that read.
    if (!empty($config['somente-leitura']) && empty($opcoes['leitura'])) {
        return ['ok' => false, 'erro' => 'read-only'];
    }
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

// =========================== req-217: origin confirmation and session keys
//
// The installation secret alone no longer talks over the channel. Whoever wants to talk (A) creates a
// single-use challenge and asks to open a session (`abrir`). The receiver (B) does not trust the request:
// it calls back the address *it* has registered for A (`central-url` at the customer; the registry `url`
// at the central) and asks whether the challenge is A's (`confirmar`). Only with A's "yes" B hands out a
// random session key, valid for 15 minutes, and every ordinary request is signed with it. A stolen secret,
// used from anywhere but the registered address, opens nothing.

const MODULO_DISTRIBUIDO_SESSAO_TTL = 900;
const MODULO_DISTRIBUIDO_ACOES_ABERTURA = ['abrir', 'confirmar'];

/** req-217: is the origin confirmation required here? `config.php` turns it on; absent means the old channel. */
function modulo_distribuido_origem_ativa(): bool {
    return modulo_distribuido_config_get('modulo-distribuido.confirmacao-origem', false) === true;
}

function modulo_distribuido_cifrar(array $dados, $chave, $aad) {
    $iv = random_bytes(12);
    $tag = '';
    $cifrado = openssl_encrypt(json_encode($dados, JSON_THROW_ON_ERROR), 'aes-256-gcm', hash('sha256', (string)$chave, true),
        OPENSSL_RAW_DATA, $iv, $tag, $aad);
    return $cifrado === false ? false : base64_encode($iv . $tag . $cifrado);
}

function modulo_distribuido_decifrar($bruto, $chave, $aad) {
    $bytes = base64_decode((string)$bruto, true);
    if ($bytes === false || strlen($bytes) < 28) return false;
    $json = openssl_decrypt(substr($bytes, 28), 'aes-256-gcm', hash('sha256', (string)$chave, true),
        OPENSSL_RAW_DATA, substr($bytes, 0, 12), substr($bytes, 12, 16), $aad);
    $dados = $json === false ? null : json_decode($json, true);
    return is_array($dados) ? $dados : false;
}

/** Signed answer of the opening actions, in the shape the other side validates. */
function modulo_distribuido_envelope_assinado(array $dados, $secret) {
    $body = json_encode($dados + ['modulo' => MODULO_DISTRIBUIDO_SLUG_CONTA, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))],
        JSON_UNESCAPED_SLASHES);
    return ['body' => base64_encode($body), 'signature' => modulo_distribuido_assinar($body, $secret)];
}

/** Opens the envelope another side answered (`data.body` + `data.signature`), or false. */
function modulo_distribuido_envelope_resposta($resposta, $secret, $pdo) {
    $envelope = is_array($resposta) ? ($resposta['data'] ?? []) : [];
    $corpo = is_array($envelope) && isset($envelope['body']) ? base64_decode((string)$envelope['body'], true) : false;
    return $corpo === false ? false : modulo_distribuido_validar_envelope($corpo, $envelope['signature'] ?? '', $secret, MODULO_DISTRIBUIDO_SLUG_CONTA, $pdo);
}

/**
 * req-217 (sender): the session to talk to `$config['endpoint']`, from memory, from the table, or opened now.
 *
 * @param array $config Channel config: endpoint, secret, peer (who is on the other side: an app_id, or
 *                      'central'), optionally transporte and pdo.
 * @return array|false ['sessao' => hex, 'chave' => base64, 'expira' => int, 'nova' => bool]
 */
function modulo_distribuido_sessao_saida(array $config, $renovar = false) {
    static $porConexao = null;
    $porConexao = $porConexao ?? new \WeakMap();
    $secret = (string)($config['secret'] ?? '');
    $endpoint = rtrim((string)($config['endpoint'] ?? ''), '/');
    if ($secret === '' || $endpoint === '') return false;
    try { $pdo = $config['pdo'] ?? modulo_distribuido_pdo(); } catch (\Throwable $e) { return false; }
    // A new secret means a new session; the id never reveals the secret.
    $id = hash('sha256', 'sessao-saida|' . $endpoint . '|' . hash('sha256', $secret));
    // Memory per connection (it goes away with it), so a page with dozens of queries reads the table once.
    $memoria = $porConexao[$pdo] ?? [];
    $chaveMemoria = $id;
    if (!$renovar) {
        if (isset($memoria[$chaveMemoria]) && $memoria[$chaveMemoria]['expira'] > time() + 30) return ['nova' => false] + $memoria[$chaveMemoria];
        try {
            $stmt = $pdo->prepare('SELECT payload FROM distributed_exchanges WHERE id=? AND kind=? AND expires_at>?');
            $stmt->execute([$id, 'sessao-saida', time() + 30]);
            $guardada = modulo_distribuido_decifrar($stmt->fetchColumn(), $secret, 'sessao-saida');
        } catch (\Throwable $e) {
            $guardada = false;
        }
        if (is_array($guardada) && ($guardada['expira'] ?? 0) > time() + 30) {
            $memoria[$chaveMemoria] = $guardada;
            $porConexao[$pdo] = $memoria;
            return ['nova' => false] + $guardada;
        }
    }
    unset($memoria[$chaveMemoria]);
    $porConexao[$pdo] = $memoria;
    $sessao = modulo_distribuido_sessao_abrir($config, $pdo);
    if (!$sessao) return false;
    try {
        $stmt = $pdo->prepare('REPLACE INTO distributed_exchanges (id,kind,payload,expires_at,consumed) VALUES (?,?,?,?,1)');
        $stmt->execute([$id, 'sessao-saida', modulo_distribuido_cifrar($sessao, $secret, 'sessao-saida'), $sessao['expira']]);
    } catch (\Throwable $e) {
        error_log('MODULO-DISTRIBUIDO: session not stored');
    }
    $memoria[$chaveMemoria] = $sessao;
    $porConexao[$pdo] = $memoria;
    return ['nova' => true] + $sessao;
}

/** req-217 (sender): challenge, `abrir`, and the session key the other side handed out after confirming. */
function modulo_distribuido_sessao_abrir(array $config, $pdo) {
    $secret = (string)$config['secret'];
    $peer = (string)($config['peer'] ?? '');
    $desafio = modulo_distribuido_registro_emitir('origem', ['peer' => $peer], $secret, 60, $pdo);
    if (!$desafio) return false;
    $payload = ['modulo' => MODULO_DISTRIBUIDO_SLUG_CONTA, 'desafio' => $desafio, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
    $app = (string)modulo_distribuido_config_get('modulo-distribuido.app-id', '');
    if ($app !== '') $payload['app_id'] = $app;
    $resposta = modulo_distribuido_enviar($payload, array_merge($config, ['slug' => MODULO_DISTRIBUIDO_SLUG_CONTA, 'acao' => 'abrir', 'timeout' => 10]));
    $dados = modulo_distribuido_envelope_resposta($resposta, $secret, $pdo);
    if (!is_array($dados) || !is_string($dados['desafio'] ?? null) || !hash_equals($desafio, $dados['desafio'])
        || !is_string($dados['sessao'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $dados['sessao'])) return false;
    $segredo = modulo_distribuido_decifrar($dados['chave'] ?? '', $secret . '|' . $desafio, 'sessao-chave');
    $chave = is_array($segredo) ? base64_decode((string)($segredo['chave'] ?? ''), true) : false;
    if ($chave === false || strlen($chave) !== 32) return false;
    return ['sessao' => $dados['sessao'], 'chave' => base64_encode($chave),
        'expira' => min((int)($dados['expira'] ?? 0), time() + MODULO_DISTRIBUIDO_SESSAO_TTL)];
}

/**
 * req-217 (receiver of `abrir`): asks the caller, at the address registered here, whether the challenge
 * is its own; only then creates the session.
 *
 * @param array  $dados   Envelope already validated with the installation secret.
 * @param string $peer    Who the caller is for this side: its app_id at the central, 'central' at the customer.
 * @param array  $retorno Channel config to the caller's *registered* address (endpoint, secret, transporte).
 * @return array|false Signed envelope with the session for the caller.
 */
function modulo_distribuido_sessao_conceder(array $dados, $secret, $peer, array $retorno, $pdo = null) {
    $desafio = $dados['desafio'] ?? null;
    if (!is_string($desafio) || !preg_match('/^[a-f0-9]{64}$/D', $desafio) || $secret === '' || empty($retorno['endpoint'])) return false;
    $pdo = $pdo ?? modulo_distribuido_pdo();
    $pergunta = ['modulo' => MODULO_DISTRIBUIDO_SLUG_CONTA, 'desafio' => $desafio, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
    $app = (string)modulo_distribuido_config_get('modulo-distribuido.app-id', '');
    if ($app !== '') $pergunta['app_id'] = $app;
    $resposta = modulo_distribuido_enviar($pergunta, array_merge($retorno,
        ['slug' => MODULO_DISTRIBUIDO_SLUG_CONTA, 'acao' => 'confirmar', 'secret' => $secret, 'timeout' => 10]));
    $confirmacao = modulo_distribuido_envelope_resposta($resposta, $secret, $pdo);
    if (!is_array($confirmacao) || ($confirmacao['confirmado'] ?? false) !== true
        || !hash_equals($desafio, (string)($confirmacao['desafio'] ?? ''))) {
        error_log('MODULO-DISTRIBUIDO: origin not confirmed for ' . ($peer !== '' ? $peer : 'unknown peer'));
        return false;
    }
    $sessao = bin2hex(random_bytes(32));
    $chave = random_bytes(32);
    $expira = time() + MODULO_DISTRIBUIDO_SESSAO_TTL;
    $stmt = $pdo->prepare('INSERT INTO distributed_exchanges (id,kind,payload,expires_at,consumed) VALUES (?,?,?,?,0)');
    $stmt->execute([hash_hmac('sha256', 'sessao:' . $sessao, $secret), 'sessao',
        modulo_distribuido_cifrar(['chave' => base64_encode($chave), 'peer' => (string)$peer], $secret, 'sessao'), $expira]);
    return modulo_distribuido_envelope_assinado(['sessao' => $sessao, 'expira' => $expira, 'desafio' => $desafio,
        // Only who created the challenge can read the key, even if the answer is seen on the way.
        'chave' => modulo_distribuido_cifrar(['chave' => base64_encode($chave)], $secret . '|' . $desafio, 'sessao-chave')], $secret);
}

/** req-217 (receiver of `confirmar`): "yes, that challenge is mine", once, and only for whom it was made. */
function modulo_distribuido_confirmar_origem(array $dados, $secret, $peer, $pdo = null) {
    $desafio = is_string($dados['desafio'] ?? null) ? $dados['desafio'] : '';
    $registro = modulo_distribuido_registro_consumir($desafio, 'origem', $secret, $pdo);
    $confirmado = is_array($registro) && hash_equals((string)($registro['peer'] ?? ''), (string)$peer);
    return modulo_distribuido_envelope_assinado(['confirmado' => $confirmado, 'desafio' => $desafio], $secret);
}

/**
 * req-217 (receiver of an ordinary request): validated body, or false.
 *
 * With a session header the body must be signed with that session's key, and the session must belong to
 * `$peer`. Without it, only the old channel (origin confirmation off) accepts the installation secret.
 */
function modulo_distribuido_receber($corpo, $assinatura, $sessao, $secret, $slug, $peer, $pdo = null) {
    if (!is_string($sessao) || $sessao === '') {
        return modulo_distribuido_origem_ativa() ? false : modulo_distribuido_validar_envelope($corpo, $assinatura, $secret, $slug, $pdo);
    }
    if (!preg_match('/^[a-f0-9]{64}$/D', $sessao) || !is_string($secret) || $secret === '') return false;
    try {
        $pdo = $pdo ?? modulo_distribuido_pdo();
        $stmt = $pdo->prepare('SELECT payload FROM distributed_exchanges WHERE id=? AND kind=? AND expires_at>=?');
        $stmt->execute([hash_hmac('sha256', 'sessao:' . $sessao, $secret), 'sessao', time()]);
        $registro = modulo_distribuido_decifrar($stmt->fetchColumn(), $secret, 'sessao');
    } catch (\Throwable $e) {
        return false;
    }
    if (!is_array($registro) || !hash_equals((string)($registro['peer'] ?? ''), (string)$peer)) return false;
    $chave = base64_decode((string)($registro['chave'] ?? ''), true);
    if ($chave === false || strlen($chave) !== 32) return false;
    return modulo_distribuido_validar_envelope($corpo, $assinatura, $chave, $slug, $pdo);
}
