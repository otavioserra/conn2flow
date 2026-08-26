<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

/** @return never */
function c2f_auth_cookie_fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

/** @return array<string, string> */
function c2f_auth_cookie_options(array $arguments): array
{
    $options = [];
    foreach (array_slice($arguments, 1) as $argument) {
        if (!str_starts_with($argument, '--') || !str_contains($argument, '=')) {
            continue;
        }

        [$name, $value] = explode('=', substr($argument, 2), 2);
        $options[$name] = $value;
    }

    return $options;
}

$options = c2f_auth_cookie_options($argv);
$gestorPath = isset($options['gestor']) ? rtrim($options['gestor'], '/\\') : '';
$resultPath = $options['result'] ?? '';
$userIdent = $options['user'] ?? 'admin';
$host = $options['host'] ?? 'localhost';

if ($gestorPath === '' || $resultPath === '') {
    c2f_auth_cookie_fail('Missing required --gestor or --result option.');
}

$configFile = $gestorPath . DIRECTORY_SEPARATOR . 'config.php';
if (!is_file($configFile)) {
    c2f_auth_cookie_fail("Gestor config.php not found at: {$configFile}");
}

$_SERVER['SERVER_NAME'] = $host;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_USER_AGENT'] = 'Conn2Flow-CLI/1.0';

global $_INDEX, $_CONFIG, $_GESTOR;
$_INDEX = ['sistemas-dir' => $gestorPath . DIRECTORY_SEPARATOR];

try {
    require_once $configFile;

    foreach (['banco', 'gestor', 'usuario', 'seguranca', 'ip'] as $library) {
        $libraryFile = $gestorPath . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . $library . '.php';
        if (is_file($libraryFile)) {
            require_once $libraryFile;
        }
    }

    if (!function_exists('banco_conectar') || !function_exists('banco_select')) {
        c2f_auth_cookie_fail('Required database functions are unavailable.');
    }

    banco_conectar();
    $escapedUser = banco_escape_field($userIdent);
    $whereClause = ctype_digit($userIdent)
        ? "WHERE id_usuarios='{$escapedUser}'"
        : "WHERE nome='{$escapedUser}' OR id_usuarios='1'";

    $usuario = banco_select([
        'unico' => true,
        'tabela' => 'usuarios',
        'campos' => ['id_usuarios', 'nome'],
        'extra' => $whereClause,
    ]);

    if (!$usuario) {
        c2f_auth_cookie_fail("User '{$userIdent}' not found in database.");
    }

    $userId = (string)$usuario['id_usuarios'];
    $userName = (string)$usuario['nome'];
    $expiration = time() + ($_CONFIG['cookie-lifetime'] ?? 1296000);
    $opensslPath = $_GESTOR['openssl-path'] ?? ($gestorPath . DIRECTORY_SEPARATOR . 'openssl' . DIRECTORY_SEPARATOR);
    $keyPublicPath = $opensslPath . 'publica.key';

    if (!is_file($keyPublicPath)) {
        c2f_auth_cookie_fail("Public key not found at: {$keyPublicPath}");
    }

    $tokenPubId = md5(uniqid((string)rand(), true));
    $hashAlgo = $_CONFIG['usuario-hash-algo'] ?? 'sha256';
    $hashPassword = $_CONFIG['usuario-hash-password'] ?? '';
    $pubIDValidation = hash_hmac($hashAlgo, $tokenPubId, $hashPassword);
    $tokenJWT = usuario_gerar_jwt([
        'host' => $host,
        'expiration' => $expiration,
        'chavePublica' => file_get_contents($keyPublicPath),
        'pubID' => $tokenPubId,
    ]);

    if (!$tokenJWT) {
        c2f_auth_cookie_fail('Failed to generate JWT token. Check OpenSSL keys.');
    }

    $escapedUserId = banco_escape_field($userId);
    banco_delete('usuarios_tokens', "WHERE user_agent='Conn2Flow-CLI/1.0' AND id_usuarios='{$escapedUserId}'");

    $ip = function_exists('ip_get') ? ip_get() : '127.0.0.1';
    $campos = [
        ['id_usuarios', $userId, null],
        ['pubID', $tokenPubId, null],
        ['pubIDValidation', $pubIDValidation, null],
        ['expiration', $expiration, null],
        ['ip', $ip, null],
        ['user_agent', 'Conn2Flow-CLI/1.0', null],
        ['data_criacao', 'NOW()', true],
    ];
    banco_insert_name($campos, 'usuarios_tokens');

    $sessionId = function_exists('seguranca_token_aleatorio')
        ? seguranca_token_aleatorio(32)
        : bin2hex(random_bytes(16));
    $cookieAuthName = $_CONFIG['cookie-authname'] ?? '_C2FCID';
    $sessionAuthName = $_CONFIG['session-authname'] ?? '_C2FSID';

    $result = [
        'userId' => $userId,
        'userName' => $userName,
        'expiration' => $expiration,
        'domain' => $host,
        'cookieAuthName' => $cookieAuthName,
        'sessionAuthName' => $sessionAuthName,
        'tokenJWT' => $tokenJWT,
        'sessionId' => $sessionId,
    ];
    $encoded = json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    if (file_put_contents($resultPath, $encoded, LOCK_EX) === false) {
        c2f_auth_cookie_fail("Unable to write generator result at: {$resultPath}");
    }
} catch (Throwable $exception) {
    c2f_auth_cookie_fail($exception->getMessage());
}
