<?php

/**********
	Descrição: configurações gerais para o funcionamento do gestor.
**********/

// ===== Definições de variáveis gerais do gestor.

$_GESTOR['versao']								=	'1.7.0'; // Versão do gestor como um todo.
$_GESTOR['id']									=	'b2make-'; // Identificador básico do gestor
$_GESTOR['linguagem-codigo']					=	'pt-br'; // Linguagem padrão do gestor
$_GESTOR['host-configuracao-id-modulo']			=	'host-configuracao'; // Identificador módulo de configuração do host.

// ===== Definição dos marcadores de abertura e fechamento de varíaveis globais.

$_GESTOR['variavel-global']						=	Array(
	'open' => '@[[', // Abertura de uma variável na execução da mesma
	'close' => ']]@', // Fechamento de uma variável na execução da mesma
	'openText' => '[[', // Abertura de uma variável na definição da mesma
	'closeText' => ']]', // Fechamento de uma variável na definição da mesma
);

// ===== Detecção de execução do cron e definições de ambiente.

if(isset($_CRON)){
	$_SERVER['SERVER_NAME'] = $_CRON['SERVER_NAME'];
	$_GESTOR['ROOT_PATH'] = $_CRON['ROOT_PATH'];
} else {
	$_GESTOR['ROOT_PATH'] = $_SERVER['DOCUMENT_ROOT'].'/'.(isset($_INDEX['acesso-publico-dir']) ? $_INDEX['acesso-publico-dir'] : '').$_INDEX['sistemas-dir'].'b2make-gestor/';
}

// ===== Definição dos caminhos de autenticação.

$_GESTOR['AUTH_PATH']							=	$_GESTOR['ROOT_PATH'] . 'autenticacoes/';
$_GESTOR['AUTH_PATH_SERVER']					=	$_GESTOR['AUTH_PATH'] . $_SERVER['SERVER_NAME'] . '/';

// ===== Carregar dependências do Composer e o arquivo .env correto para o ambiente =====

require_once $_GESTOR['ROOT_PATH'] . 'vendor/autoload.php';

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
];

// O caminho das chaves agora também vem do .env, mas a pasta base é a do ambiente.
$_GESTOR['openssl-path'] = $_GESTOR['AUTH_PATH_SERVER'] . ($_ENV['OPENSSL_KEYS_SUBDIR'] ?? 'chaves/gestor/');

// ===== Definição do caminho em disco dos plugins.

$_GESTOR['plugins-path']						=	$_GESTOR['ROOT_PATH'].'../b2make-gestor-plugins/';

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

// ===== Definições de variáveis padrões do sistema em hosts diferentes

if($_SERVER['SERVER_NAME'] == "localhost"){
	// ===== Url raiz.
	
	$_GESTOR['url-raiz']							=	'/b2make/';
	
	// ===== Configuração do server de cada host de usuário.
	
	$_GESTOR['hosts-server'] = Array(
		'ativo'						=>	true,
		'user-root-path'			=>	'/home/',
		'cpanel-root-path'			=>	'/home/b2make/b2make-cpanel/',
		'local'						=>	'betaServer0',
		'server'					=>	's0',
		'pacote-inicial'			=>	'TRIAL',
		'user-perfix'				=>	's0ub',
		'dominio'					=>	's0.b2make.com',
		'dominio-sufix-regex'		=>	's0\.b2make\.com',
		'db-user-sufix'				=>	'_b2make',
		'ftp-user-sufix'			=>	'_b2make',
		'ftp-root'					=>	'/',
		'ftp-site-root'				=>	'/public_html/',
		'ftp-files-root'			=>	'/b2make/files/',
		'ftp-gestor-root'			=>	'/b2make/',
	);
	
	// ===== Identificador do ambiente da plataforma.
	
	$_GESTOR['plataforma-id'] = 'local';
} else if($_SERVER['SERVER_NAME'] == "beta.b2make.com"){
	// ===== Url raiz.
	
	$_GESTOR['url-raiz']							=	'/';
	
	// ===== Configuração do server de cada host de usuário.
	
	$_GESTOR['hosts-server'] = Array(
		'ativo'						=>	true,
		'user-root-path'			=>	'/home/',
		'cpanel-root-path'			=>	'/home/betab2makecom/b2make-cpanel/',
		'local'						=>	'betaServer0',
		'server'					=>	's0',
		'pacote-inicial'			=>	'TRIAL',
		'user-perfix'				=>	's0ub',
		'dominio'					=>	's0.b2make.com',
		'dominio-sufix-regex'		=>	's0\.b2make\.com',
		'db-user-sufix'				=>	'_b2make',
		'ftp-user-sufix'			=>	'_b2make',
		'ftp-root'					=>	'/',
		'ftp-site-root'				=>	'/public_html/',
		'ftp-files-root'			=>	'/b2make/files/',
		'ftp-gestor-root'			=>	'/b2make/',
	);
	
	// ===== Identificador do ambiente da plataforma.
	
	$_GESTOR['plataforma-id'] = 'beta';
} else {
	// ===== Banco e url raiz.
	
	$_GESTOR['url-raiz']							=	'/';
	
	// ===== Configuração do server de cada host de usuário.
	
	$_GESTOR['hosts-server'] = Array(
		'ativo'						=>	true,
		'user-root-path'			=>	'/home/',
		'cpanel-root-path'			=>	'/home/b2makecom/b2make-cpanel/',
		'local'						=>	'server0',
		'server'					=>	's0',
		'pacote-inicial'			=>	'TRIAL',
		'user-perfix'				=>	's0u',
		'dominio'					=>	's0.b2make.com',
		'dominio-sufix-regex'		=>	's0\.b2make\.com',
		'db-user-sufix'				=>	'_b2make',
		'ftp-user-sufix'			=>	'_b2make',
		'ftp-root'					=>	'/',
		'ftp-site-root'				=>	'/public_html/',
		'ftp-files-root'			=>	'/b2make/files/',
		'ftp-gestor-root'			=>	'/b2make/',
	);
	
	// ===== Identificador do ambiente da plataforma.
	
	$_GESTOR['plataforma-id'] 		= 'producao'; 
}

// ===== Definições de variáveis padrões do sistema que dependem de host 

$_GESTOR['url-full']							=	'//'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];
$_GESTOR['url-full-http']						=	'https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];

// ===== Definições dos caminhos relativos.

$_GESTOR['modulos-bibliotecas']					=	'bibliotecas/'; // Caminho relativo a raiz dos módulos bibliotecas do gestor
$_GESTOR['pagina#contato-url']					=	'contato/'; // Página de contatos relativo a raiz do sistema.

// ===== Definições da plataforma de comunicação entre clientes e servidor.

$_GESTOR['plataforma'] = Array(
	'hosts' => Array(
		'producao' => Array(
			'host' => 'b2make.com.br',
		),
		'beta' => Array(
			'host' => 'beta.b2make.com.br',
		),
	)
);

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

if($_GESTOR['bibliotecas'])
foreach($_GESTOR['bibliotecas'] as $_biblioteca){
	$_caminhos = $_GESTOR['bibliotecas-dados'][$_biblioteca];
	
	if($_caminhos)
	foreach($_caminhos as $_caminho){
		include($_GESTOR['modulos-bibliotecas'].$_caminho);
	}
}

?>