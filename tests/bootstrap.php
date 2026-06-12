<?php

declare(strict_types=1);

error_reporting(E_ALL);

// Configura o caminho do OpenSSL no Windows se não estiver definido
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && !getenv('OPENSSL_CONF')) {
    $phpDir = dirname(PHP_BINARY);
    $possibleCnfPaths = [
        $phpDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf',
        $phpDir . DIRECTORY_SEPARATOR . 'openssl.cnf',
    ];
    foreach ($possibleCnfPaths as $cnfPath) {
        if (is_file($cnfPath)) {
            putenv("OPENSSL_CONF=" . $cnfPath);
            break;
        }
    }
}

define('CONN2FLOW_TESTING', true);
define('CONN2FLOW_ROOT', dirname(__DIR__));
define('CONN2FLOW_GESTOR_ROOT', CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor');

$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$_SERVER['QUERY_STRING'] = $_SERVER['QUERY_STRING'] ?? '';

global $_GESTOR, $_BANCO, $_CONFIG;

$_GESTOR = [
    'ROOT_PATH' => CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR,
    'linguagem-codigo' => 'pt-br',
    'linguagem-padrao' => 'pt-br',
    'url-raiz' => '/',
    'bibliotecas-path' => CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR,
    'recursos-incluidos-hashes' => [],
    'html-extra-head' => [],
    'css' => [],
    'css-compiled' => [],
];

$_BANCO = [
    'tipo' => getenv('CONN2FLOW_DB_CONNECTION') ?: 'sqlite',
    'host' => getenv('CONN2FLOW_DB_HOST') ?: 'localhost',
    'nome' => getenv('CONN2FLOW_DB_DATABASE') ?: ':memory:',
    'usuario' => getenv('CONN2FLOW_DB_USERNAME') ?: '',
    'senha' => getenv('CONN2FLOW_DB_PASSWORD') ?: '',
];

$_CONFIG = [
    'openssl-password' => getenv('CONN2FLOW_OPENSSL_PASSWORD') ?: 'conn2flow-tests',
];

$autoloadCandidates = [
    CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
    CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

$loadFullConfig = filter_var(getenv('CONN2FLOW_TEST_BOOTSTRAP_CONFIG'), FILTER_VALIDATE_BOOLEAN);
if ($loadFullConfig) {
    require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'config.php';
}

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'gestor.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'banco.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'autenticacao.php';
