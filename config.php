<?php

/**********
	Descrição: configurações gerais para o funcionamento do gestor.
**********/

// ===== Definições de variáveis gerais do gestor.

$_GESTOR['versao']								=	'1.5.0'; // Versão do gestor como um todo.
$_GESTOR['id']									=	'entrey-'; // Identificador básico do gestor
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

// ===== Verificar se existe a pasta de autenticacao. Senão existir finalizar e retornar erro 404.

if(!is_dir($_GESTOR['AUTH_PATH_SERVER'])){
	http_response_code(404);
	echo json_encode(Array(
		'error' => '404',
		'info' => 'Domain not found',
	));
	exit;
}

// ===== Incluir arquivos de autenticação do banco, de configurações e caminho para as chaves de segurança principais.

require_once($_GESTOR['AUTH_PATH_SERVER'] . 'banco.php');
require_once($_GESTOR['AUTH_PATH_SERVER'] . 'config.php');

$_GESTOR['openssl-path']						=	$_GESTOR['AUTH_PATH_SERVER'].'chaves/gestor/';

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

// ===== Definições de variáveis padrões do sistema em hosts diferentes

if($_SERVER['SERVER_NAME'] == "localhost"){
	// ===== Url raiz.
	
	$_GESTOR['url-raiz']							=	'/entrey/';
	
	// ===== Configuração do server de cada host de usuário.
	
	$_GESTOR['hosts-server'] = Array(
		'ativo'						=>	true,
		'cpanel-root-path'			=>	'/home/betaentreycom/b2make-cpanel/',
		'local'						=>	'betaServer0',
		'server'					=>	's0',
		'pacote-inicial'			=>	'TRIAL',
		'user-perfix'				=>	's0ub',
		'dominio'					=>	's0.entrey.com.br',
		'dominio-sufix-regex'		=>	's0\.entrey\.com\.br',
		'db-user-sufix'				=>	'_entrey',
		'ftp-user-sufix'			=>	'_entrey',
		'ftp-root'					=>	'/',
		'ftp-site-root'				=>	'/public_html/',
		'ftp-files-root'			=>	'/entrey/files/',
		'ftp-gestor-root'			=>	'/entrey/',
	);
	
	// ===== Identificador do ambiente da plataforma.
	
	$_GESTOR['plataforma-id'] = 'local';
} else if($_SERVER['SERVER_NAME'] == "beta.entrey.com.br"){
	// ===== Url raiz.
	
	$_GESTOR['url-raiz']							=	'/';
	
	// ===== Configuração do server de cada host de usuário.
	
	$_GESTOR['hosts-server'] = Array(
		'ativo'						=>	true,
		'cpanel-root-path'			=>	'/home/betaentreycom/b2make-cpanel/',
		'local'						=>	'betaServer0',
		'server'					=>	's0',
		'pacote-inicial'			=>	'TRIAL',
		'user-perfix'				=>	's0ub',
		'dominio'					=>	's0.entrey.com.br',
		'dominio-sufix-regex'		=>	's0\.entrey\.com\.br',
		'db-user-sufix'				=>	'_entrey',
		'ftp-user-sufix'			=>	'_entrey',
		'ftp-root'					=>	'/',
		'ftp-site-root'				=>	'/public_html/',
		'ftp-files-root'			=>	'/entrey/files/',
		'ftp-gestor-root'			=>	'/entrey/',
	);
	
	// ===== Identificador do ambiente da plataforma.
	
	$_GESTOR['plataforma-id'] = 'beta';
} else {
	// ===== Banco e url raiz.
	
	$_GESTOR['url-raiz']							=	'/';
	
	// ===== Configuração do server de cada host de usuário.
	
	$_GESTOR['hosts-server'] = Array(
		'ativo'						=>	true,
		'cpanel-root-path'			=>	'/home/entreycom/b2make-cpanel/',
		'local'						=>	'server0',
		'server'					=>	's0',
		'pacote-inicial'			=>	'TRIAL',
		'user-perfix'				=>	's0u',
		'dominio'					=>	's0.entrey.com.br',
		'dominio-sufix-regex'		=>	's0\.entrey\.com\.br',
		'db-user-sufix'				=>	'_entrey',
		'ftp-user-sufix'			=>	'_entrey',
		'ftp-root'					=>	'/',
		'ftp-site-root'				=>	'/public_html/',
		'ftp-files-root'			=>	'/entrey/files/',
		'ftp-gestor-root'			=>	'/entrey/',
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
			'host' => 'entrey.com.br',
		),
		'beta' => Array(
			'host' => 'beta.entrey.com.br',
		),
	)
);

// ===== Definições do gestor cliente.

$_GESTOR['gestor-cliente'] = Array(
	'versao' => '1.5.0',
	'versao_num' => 19,
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