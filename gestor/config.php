<?php

/**********
	Descrição: configurações gerais para o funcionamento do gestor.
**********/

// ===== Definições de variáveis gerais do gestor.

$_GESTOR['versao']								=	'1.8.1'; // Versão do gestor como um todo.
$_GESTOR['id']									=	'conn2flow-'; // Identificador básico do gestor
$_GESTOR['linguagem-codigo']					=	'pt-br'; // Linguagem padrão do gestor
$_GESTOR['host-configuracao-id-modulo']			=	'host-configuracao'; // Identificador módulo de configuração do host.

// ===== Definição dos marcadores de abertura e fechamento de varíaveis globais.

$_GESTOR['variavel-global']						=	Array(
	'open' => '@[[', // Abertura de uma variável na execução da mesma
	'close' => ']]@', // Fechamento de uma variável na execução da mesma
	'openText' => '[[', // Abertura de uma variável na definição da mesma
	'closeText' => ']]', // Fechamento de uma variável na definição da mesma
);

if (php_sapi_name() === 'cli') {
	// Ambiente de linha de comando (usado por Phinx, scripts, etc.)
	// Define o ROOT_PATH de forma absoluta e confiável a partir da localização deste arquivo.
	$_GESTOR['ROOT_PATH'] = __DIR__ . '/';
	// Define um SERVER_NAME padrão para a lógica de configuração funcionar.
	$_SERVER['SERVER_NAME'] = 'localhost';
} else if(isset($_CRON)){
	// Ambiente de execução via CRON
	$_SERVER['SERVER_NAME'] = $_CRON['SERVER_NAME'];
	$_GESTOR['ROOT_PATH'] = $_CRON['ROOT_PATH'];
} else {
	// Ambiente de execução via Web (Apache, etc.)
	$_GESTOR['ROOT_PATH'] = $_INDEX['sistemas-dir'];
}

// ===== Definição dos caminhos de autenticação.

$_GESTOR['AUTH_PATH']							=	$_GESTOR['ROOT_PATH'] . 'autenticacoes/';
$_GESTOR['AUTH_PATH_SERVER']					=	$_GESTOR['AUTH_PATH'] . $_SERVER['SERVER_NAME'] . '/';

// ===== Carregar dependências do Composer e o arquivo .env correto para o ambiente =====

require_once $_GESTOR['ROOT_PATH'] . 'vendor/autoload.php';

if (class_exists(Dotenv\Dotenv::class)) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable($_GESTOR['AUTH_PATH_SERVER']);
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidPathException $e) {
        http_response_code(503);
        echo json_encode([
            'error' => '503',
            'info' => 'Configuration file (.env) not found for domain: ' . $_SERVER['SERVER_NAME'],
            'details' => 'Please ensure the directory exists and contains a valid .env file: ' . $_GESTOR['AUTH_PATH_SERVER']
        ]);
        exit;
    }
} else {
    // Se a classe Dotenv não existe, é porque as dependências do Composer não foram instaladas.
    http_response_code(500);
    echo "Erro Crítico: A classe Dotenv não foi encontrada. Execute 'composer install' na pasta 'gestor'.\n";
    exit(1); // Sai com um código de erro para scripts de linha de comando.
}

// ===== Popular as configurações globais a partir do .env =====

global $_BANCO, $_CONFIG;

// Configurações do Banco
$_BANCO = [
    'tipo'    => $_ENV['DB_CONNECTION'] ?? 'mysqli',
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'nome'    => $_ENV['DB_DATABASE'] ?? '',
    'usuario' => $_ENV['DB_USERNAME'] ?? '',
    'senha'   => $_ENV['DB_PASSWORD'] ?? '',
];

// Configurações Gerais
$_CONFIG = [
    'session-authname'                  => $_ENV['SESSION_AUTHNAME'] ?? '_BSID',
    'session-lifetime'                  => (int)($_ENV['SESSION_LIFETIME'] ?? 10800),
    'session-garbagetime'               => (int)($_ENV['SESSION_GARBAGETIME'] ?? 86400),
    'session-garbage-colector-time'     => (int)($_ENV['SESSION_GARBAGE_COLECTOR_TIME'] ?? 3600),
    'cookie-authname'                   => $_ENV['COOKIE_AUTHNAME'] ?? '_BUSID',
    'cookie-verify'                     => $_ENV['COOKIE_VERIFY'] ?? '_BCVID',
    'cookie-lifetime'                   => (int)($_ENV['COOKIE_LIFETIME'] ?? 1296000),
    'cookie-renewtime'                  => (int)($_ENV['COOKIE_RENEWTIME'] ?? 86400),
    'cookie-secure'                     => filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'openssl-password'                  => $_ENV['OPENSSL_PASSWORD'] ?? '',
    'usuario-hash-password'             => $_ENV['USUARIO_HASH_PASSWORD'] ?? '',
    'usuario-hash-algo'                 => $_ENV['USUARIO_HASH_ALGO'] ?? 'sha512',
    'usuario-recaptcha-active'          => filter_var($_ENV['USUARIO_RECAPTCHA_ACTIVE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'usuario-recaptcha-site'            => $_ENV['USUARIO_RECAPTCHA_SITE'] ?? '',
    'usuario-recaptcha-server'          => $_ENV['USUARIO_RECAPTCHA_SERVER'] ?? '',
    'usuario-maximo-senhas-invalidas'   => (int)($_ENV['USUARIO_MAXIMO_SENHAS_INVALIDAS'] ?? 3),
    'usuario-autorizacao-lifetime'      => (int)($_ENV['USUARIO_AUTORIZACAO_LIFETIME'] ?? 300),
    'token-lifetime'                    => (int)($_ENV['TOKEN_LIFETIME'] ?? 3600),
    'plano-teste-id-usuario-perfil'     => $_ENV['PLANO_TESTE_ID_USUARIO_PERFIL'] ?? '2',
    'autenticacao-token-lifetime'       => (int)($_ENV['AUTENTICACAO_TOKEN_LIFETIME'] ?? 15552000),
    
    // Configurações da Plataforma Cliente
    'platform-lifetime'                 => (int)($_ENV['PLATFORM_LIFETIME'] ?? 900),
    'platform-hash-password'            => $_ENV['PLATFORM_HASH_PASSWORD'] ?? '',
    'platform-hash-algo'                => $_ENV['PLATFORM_HASH_ALGO'] ?? 'sha512',
    'platform-recaptcha-active'         => filter_var($_ENV['PLATFORM_RECAPTCHA_ACTIVE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'platform-recaptcha-site'           => $_ENV['PLATFORM_RECAPTCHA_SITE'] ?? '',
    'platform-recaptcha-server'         => $_ENV['PLATFORM_RECAPTCHA_SERVER'] ?? '',
    
    // Configurações do Aplicativo
    'app-recaptcha-active'              => filter_var($_ENV['APP_RECAPTCHA_ACTIVE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'app-token-lifetime'                => (int)($_ENV['APP_TOKEN_LIFETIME'] ?? 2592000),
    'app-token-renewtime'               => (int)($_ENV['APP_TOKEN_RENEWTIME'] ?? 86400),
    'app-origem'                        => $_ENV['APP_ORIGEM'] ?? 'app',
    
    // Controle de Acessos
    'acessos-maximo-falhas-logins'      => (int)($_ENV['ACESSOS_MAXIMO_FALHAS_LOGINS'] ?? 10),
    'acessos-maximo-logins-simples'     => (int)($_ENV['ACESSOS_MAXIMO_LOGINS_SIMPLES'] ?? 3),
    'acessos-tempo-bloqueio-ip'         => (int)($_ENV['ACESSOS_TEMPO_BLOQUEIO_IP'] ?? 86400),
    'acessos-tempo-desbloqueio-ip'      => (int)($_ENV['ACESSOS_TEMPO_DESBLOQUEIO_IP'] ?? 2592000),
    'acessos-maximo-cadastros'          => [
        'signup' => (int)($_ENV['ACESSOS_MAXIMO_CADASTROS_SIGNUP'] ?? 1),
        'formulario-contato' => (int)($_ENV['ACESSOS_MAXIMO_CADASTROS_FORMULARIO_CONTATO'] ?? 10),
    ],
    'acessos-maximo-cadastros-simples'  => [
        'signup' => (int)($_ENV['ACESSOS_MAXIMO_CADASTROS_SIMPLES_SIGNUP'] ?? 1),
        'formulario-contato' => (int)($_ENV['ACESSOS_MAXIMO_CADASTROS_SIMPLES_FORMULARIO_CONTATO'] ?? 3),
    ],
];

// O caminho das chaves agora também vem do .env, mas a pasta base é a do ambiente.
$_GESTOR['openssl-path'] = $_GESTOR['AUTH_PATH_SERVER'] . ($_ENV['OPENSSL_KEYS_SUBDIR'] ?? 'chaves/gestor/');

// ===== Definição do caminho em disco dos plugins.

$_GESTOR['plugins-path']						=	$_GESTOR['ROOT_PATH'].'../conn2flow-gestor-plugins/';

// ===== Definição dos caminhos em disco padrões.

$_GESTOR['bibliotecas-path']					=	$_GESTOR['ROOT_PATH'].'bibliotecas/';
$_GESTOR['modulos-path']						=	$_GESTOR['ROOT_PATH'].'modulos/';
$_GESTOR['controladores-path']					=	$_GESTOR['ROOT_PATH'].'controladores/';
$_GESTOR['assets-path']							=	$_GESTOR['ROOT_PATH'].'assets/';
$_GESTOR['contents-path']						=	$_GESTOR['ROOT_PATH'].'contents/';
$_GESTOR['configuracoes-path']					=	$_GESTOR['ROOT_PATH'].'configuracoes/';
$_GESTOR['logs-path']							=	$_GESTOR['ROOT_PATH'].'logs/';

// ===== Nome da pasta do Fomantic UI principal relativo a pasta assets.

$_GESTOR['fomantic-ui-folder']					=	'fomantic-UI@2.9.0';

// ===== Carrega as configurações de ambiente do .env =====

$_GESTOR['plataforma-id'] = $_ENV['PLATAFORMA_ID'] ?? 'producao';
$_GESTOR['url-raiz'] = $_ENV['URL_RAIZ'] ?? '/';

$_GESTOR['hosts-server'] = [
    'ativo'                 => filter_var($_ENV['HOSTS_SERVER_ACTIVE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'user-root-path'        => $_ENV['HOSTS_SERVER_USER_ROOT_PATH'] ?? '/',
    'cpanel-root-path'      => $_ENV['HOSTS_SERVER_CPANEL_ROOT_PATH'] ?? '',
    'local'                 => $_ENV['HOSTS_SERVER_LOCAL'] ?? '',
    'server'                => $_ENV['HOSTS_SERVER_SERVER'] ?? '',
    'pacote-inicial'        => $_ENV['HOSTS_SERVER_PACOTE_INICIAL'] ?? 'TRIAL',
    'user-perfix'           => $_ENV['HOSTS_SERVER_USER_PERFIX'] ?? '',
    'dominio'               => $_ENV['HOSTS_SERVER_DOMINIO'] ?? '',
    'dominio-sufix-regex'   => $_ENV['HOSTS_SERVER_DOMINIO_SUFIX_REGEX'] ?? '',
    'db-user-sufix'         => $_ENV['HOSTS_SERVER_DB_USER_SUFIX'] ?? '',
    'ftp-user-sufix'        => $_ENV['HOSTS_SERVER_FTP_USER_SUFIX'] ?? '',
    'ftp-root'              => $_ENV['HOSTS_SERVER_FTP_ROOT'] ?? '/',
    'ftp-site-root'         => $_ENV['HOSTS_SERVER_FTP_SITE_ROOT'] ?? '/public_html/',
    'ftp-files-root'        => $_ENV['HOSTS_SERVER_FTP_FILES_ROOT'] ?? '',
    'ftp-gestor-root'       => $_ENV['HOSTS_SERVER_FTP_GESTOR_ROOT'] ?? '',
];

// ===== Definições de variáveis padrões do sistema que dependem de host 

$_GESTOR['url-full']							=	'//'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];
$_GESTOR['url-full-http']						=	'https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];

// ===== Definições dos caminhos relativos.

$_GESTOR['modulos-bibliotecas']					=	'bibliotecas/'; // Caminho relativo a raiz dos módulos bibliotecas do gestor
$_GESTOR['pagina#contato-url']					=	'contato/'; // Página de contatos relativo a raiz do sistema.

// ===== Definições da plataforma de comunicação entre clientes e servidor.

// Carrega a definição do host da plataforma a partir do .env do ambiente atual.
$platformHostId = $_ENV['PLATAFORMA_HOST_ID'] ?? 'producao';
$platformHostUrl = $_ENV['PLATAFORMA_HOST_URL'] ?? 'localhost';

$_GESTOR['plataforma']['hosts'][$platformHostId] = [
    'host' => $platformHostUrl,
];

// ===== Definições do gestor cliente.

$_GESTOR['gestor-cliente'] = Array(
	'versao' => '1.6.0',
	'versao_num' => 21,
);

// ===== Definição e inclusão de todas as bibliotecas necessárias para o funcionamento do gestor

$_GESTOR['bibliotecas-dados'] = Array(
	'banco' => Array('banco.php'),
	'gestor' => Array('gestor.php'),
	'modelo' => Array('modelo.php'),
	'interface' => Array('interface.php'),
	'html' => Array('html.php'),
	'usuario' => Array('usuario.php'),
	'comunicacao' => Array('comunicacao.php'),
	'arquivo' => Array('arquivo.php'),
	'ftp' => Array('ftp.php'),
	'api-cliente' => Array('api-cliente.php'),
	'pagina' => Array('pagina.php'),
	'formato' => Array('formato.php'),
	'configuracao' => Array('configuracao.php'),
	'host' => Array('host.php'),
	'paypal' => Array('paypal.php'),
	'variaveis' => Array('variaveis.php'),
	'log' => Array('log.php'),
	'autenticacao' => Array('autenticacao.php'),
	'pdf' => Array('pdf.php'),
	'cpanel' => Array('cpanel.php'),
	'ip' => Array('ip.php'),
	'widgets' => Array('widgets.php'),
	'formulario' => Array('formulario.php'),
	'geral' => Array('geral.php'),
);

if(isset($_GESTOR['bibliotecas']))
foreach($_GESTOR['bibliotecas'] as $_biblioteca){
	$_caminhos = $_GESTOR['bibliotecas-dados'][$_biblioteca];
	
	if($_caminhos)
	foreach($_caminhos as $_caminho){
		include($_GESTOR['modulos-bibliotecas'].$_caminho);
	}
}

?>