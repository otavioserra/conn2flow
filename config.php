<?php

// ===== Definições de variáveis gerais do gestor

$_GESTOR['versao']								=	'1.0.19'; // Versão do gestor como um todo.
$_GESTOR['id']									=	'entrey-'; // Identificador básico do gestor
$_GESTOR['linguagem-codigo']					=	'pt-br'; // Linguagem padrão do gestor
$_GESTOR['modulos-bibliotecas']					=	'bibliotecas/'; // Caminho relativo a raiz dos módulos bibliotecas do gestor
$_GESTOR['session-authname']					=	'_ESID'; // Nome do cookie de sessão de usuário
$_GESTOR['session-lifetime']					=	60*60*3; // 3 horas para as sessões serem deletadas
$_GESTOR['session-garbagetime']					=	60*60*24; // 24 horas para tokens de sessões serem deletados
$_GESTOR['session-garbage-colector-time']		=	60*60; // 1 hora para sessões antigas serem deletados.
$_GESTOR['cookie-authname']						=	'_EUSID'; // Nome do cookie de sessão de autenticação de usuário
$_GESTOR['cookie-verify']						=	'_ECVID'; // Nome do cookie de sessão de autenticação de usuário
$_GESTOR['cookie-lifetime']						=	60*60*24*15; // 15 dias para os cookie do token de acesso serem deletados
$_GESTOR['cookie-renewtime']					=	60*60*24; // 24 horas para renovar automaticamente cookie do token de acesso
$_GESTOR['openssl-password']					=	'entrey-dgCJ-vL\ymYB53L,'; // Senha da chave RSA atual
$_GESTOR['usuario-hash-password']				=	'entrey-h"hC,r^pmUj7kSs{'; // Senha de gerador de hash
$_GESTOR['usuario-hash-algo']					=	'sha512'; // Algorítmo usado para criar hash
$_GESTOR['usuario-recaptcha-active']			=	true; // Ativar Recaptcha v3 
$_GESTOR['usuario-recaptcha-site']				=	'6Lcur6gaAAAAANPAHPglZ1NLdlsB9IIFOINzaYBC'; // Recaptcha site
$_GESTOR['usuario-recaptcha-server']			=	'6Lcur6gaAAAAAJoaQiE-4GKuhK8Vt5I0IfP1d967'; // Recaptcha server
$_GESTOR['usuario-maximo-senhas-invalidas']		=	3; // Máximo de vezes que pode errar a senha em autorização provisória, antes de ser deconectado automaticamente
$_GESTOR['usuario-autorizacao-lifetime']		=	60*5; // 5 minutos definido / Tempo máximo em segundos que uma autorização provisória é válida.
$_GESTOR['token-lifetime']						=	60*60*1; // 1 hora para os tokens provisórios serem deletadas.
$_GESTOR['pagina#contato-url']					=	'contato/'; // Página de contatos relativo a raiz do sistema.
$_GESTOR['plano-teste-id-usuario-perfil']		=	'2'; // Identificador do perfil do usuário para planos testes (TRIAL).
$_GESTOR['host-configuracao-id-modulo']			=	'15'; // Identificador módulo de configuração do host.
$_GESTOR['platform-lifetime']					=	60*15; // 15 minutos para os tokens de acesso a plataforma serem deletados.
$_GESTOR['platform-hash-password']				=	'OBWeggLLoDm!NMOO7@JXDfpe233Zb1^C'; // Senha de gerador de hash
$_GESTOR['platform-hash-algo']					=	'sha512'; // Algorítmo usado para criar hash
$_GESTOR['platform-recaptcha-active']			=	true; // Ativar Recaptcha v3 nos hosts
$_GESTOR['platform-recaptcha-site']				=	'6LewE8QcAAAAAOyOzcZufW9dkK7yRxMSQaUyBr1M'; // Recaptcha site nos hosts
$_GESTOR['platform-recaptcha-server']			=	'6LewE8QcAAAAAKZq0JboJ7QL_m2aAYleKuYAAxYN'; // Recaptcha server nos hosts
$_GESTOR['app-recaptcha-active']				=	false; // Ativar Recaptcha v3 no APP
$_GESTOR['app-token-lifetime']					=	60*60*24*30; // 30 dias para os token de acesso serem deletados.
$_GESTOR['app-token-renewtime']					=	60*60*24; // 24 horas para renovar automaticamente o token de acesso
$_GESTOR['app-origem']							=	'app'; // Identificador do APP 

// ===== Definição dos marcadores de abertura e fechamento de varíaveis globais.

$_GESTOR['variavel-global']						=	Array(
	'open' => '@[[', // Abertura de uma variável na execução da mesma
	'close' => ']]@', // Fechamento de uma variável na execução da mesma
	'openText' => '[[', // Abertura de uma variável na definição da mesma
	'closeText' => ']]', // Fechamento de uma variável na definição da mesma
);

// ===== Definições do banco de dados de cada host

$_GESTOR['bancoDef']['localhost'] = Array(
	'tipo'				=>	'mysqli',
	'usuario'			=>	'root',
	'senha'				=>	'serra123',
	'nome'				=>	'betaentr_gestor',
	'host'				=>	'127.0.0.1',
);

$_GESTOR['bancoDef']['beta.entrey.com.br'] = Array(
	'tipo'				=>	'mysqli',
	'usuario'			=>	'betaentr_gestor',
	'senha'				=>	'~qtAS6mD_aVF+NV.',
	'nome'				=>	'betaentr_gestor',
	'host'				=>	'localhost',
);

$_GESTOR['bancoDef']['entrey.com.br'] = Array(
	'tipo'				=>	'mysqli',
	'usuario'			=>	'entreyco_gestor',
	'senha'				=>	'%rM0ZAx+@;DQ',
	'nome'				=>	'entreyco_gestor',
	'host'				=>	'localhost',
);

// ===== Definições de variáveis padrões do sistema em hosts diferentes

if($_SERVER['SERVER_NAME'] == "localhost"){
	// ===== Banco e caminhos em disco das opções
	
	$_GESTOR['banco'] = $_GESTOR['bancoDef']['localhost'];
	
	$_GESTOR['url-raiz']							=	'/entrey/';
	$_GESTOR['bibliotecas-path']					=	$_SERVER['DOCUMENT_ROOT'].'/sistemas/b2make-gestor/bibliotecas/';
	$_GESTOR['modulos-path']						=	$_SERVER['DOCUMENT_ROOT'].'/sistemas/b2make-gestor/bibliotecas/';
	$_GESTOR['openssl-path']						=	$_SERVER['DOCUMENT_ROOT'].'/sistemas/b2make-gestor/chaves/gestor/';
	$_GESTOR['assets-path']							=	$_SERVER['DOCUMENT_ROOT'].'/sistemas/b2make-gestor/assets/';
	$_GESTOR['contents-path']						=	$_SERVER['DOCUMENT_ROOT'].'/sistemas/b2make-gestor/contents/';
	$_GESTOR['configuracoes-path']					=	$_SERVER['DOCUMENT_ROOT'].'/sistemas/b2make-gestor/configuracoes/';
	$_GESTOR['logs-path']							=	$_SERVER['DOCUMENT_ROOT'].'/sistemas/b2make-gestor/logs/';
	
	// ===== Configuração do serviço de emails
	
	$_GESTOR['email'] = Array(
		'ativo'				=>	true,
		'server'			=>	Array(
			'host'				=>	'beta.entrey.com.br',
			'user'				=>	'noreply@beta.entrey.com.br',
			'pass'				=>	'~mrBH^J-_HxO',
			'secure'			=>	true,
			'port'				=>	465,
		),
		'sender'			=>	Array(
			'from'				=>	'noreply@beta.entrey.com.br',
			'fromName'			=>	'Entrey Beta',
			'replyTo'			=>	'noreply@beta.entrey.com.br',
			'replyToName'		=>	'Entrey Beta',
		),
	);
	
	// ===== Configuração do server de cada host de usuário
	
	$_GESTOR['hosts-server'] = Array(
		'ativo'						=>	false,
	);
	
	$_GESTOR['plataforma-id'] = 'local';
} else if($_SERVER['SERVER_NAME'] == "beta.entrey.com.br"){
	// ===== Banco e caminhos em disco das opções
	
	$_GESTOR['banco'] = $_GESTOR['bancoDef']['beta.entrey.com.br'];
	
	$_GESTOR['url-raiz']							=	'/';
	$_GESTOR['bibliotecas-path']					=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/bibliotecas/';
	$_GESTOR['modulos-path']						=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/bibliotecas/';
	$_GESTOR['openssl-path']						=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/chaves/gestor/';
	$_GESTOR['assets-path']							=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/assets/';
	$_GESTOR['contents-path']						=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/contents/';
	$_GESTOR['configuracoes-path']					=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/configuracoes/';
	$_GESTOR['logs-path']							=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/logs/';
	
	// ===== Configuração do serviço de emails
	
	$_GESTOR['email'] = Array(
		'ativo'				=>	true,
		'server'			=>	Array(
			'host'				=>	'beta.entrey.com.br',
			'user'				=>	'noreply@beta.entrey.com.br',
			'pass'				=>	'~mrBH^J-_HxO',
			'secure'			=>	true,
			'port'				=>	465,
		),
		'sender'			=>	Array(
			'from'				=>	'noreply@beta.entrey.com.br',
			'fromName'			=>	'Entrey Beta',
			'replyTo'			=>	'noreply@beta.entrey.com.br',
			'replyToName'		=>	'Entrey Beta',
		),
	);
	
	// ===== Configuração do server de cada host de usuário
	
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
	
	$_GESTOR['plataforma-id'] = 'beta';
} else {
	// ===== Banco e caminhos em disco das opções
	
	$_GESTOR['banco'] = $_GESTOR['bancoDef']['entrey.com.br'];
	
	$_GESTOR['url-raiz']							=	'/';
	$_GESTOR['bibliotecas-path']					=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/bibliotecas/';
	$_GESTOR['modulos-path']						=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/bibliotecas/';
	$_GESTOR['openssl-path']						=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/chaves/gestor/';
	$_GESTOR['assets-path']							=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/assets/';
	$_GESTOR['contents-path']						=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/contents/';
	$_GESTOR['configuracoes-path']					=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/configuracoes/';
	$_GESTOR['logs-path']							=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/logs/';
	
	// ===== Chaves e senhas do ambiente live.
	
	$_GESTOR['openssl-password']					=	'entrey-rsLoj@Gvdd!a&O5ogpAXmR'; // Senha da chave RSA atual
	$_GESTOR['usuario-hash-password']				=	'entrey-0NapBuN2vq$#2oeVq9@G9e'; // Senha de gerador de hash
	
	$_GESTOR['openssl-path']						=	$_SERVER['DOCUMENT_ROOT'].'/../b2make-gestor/chaves/gestor/live/';
	
	// ===== Configuração do serviço de emails
	
	$_GESTOR['email'] = Array(
		'ativo'				=>	true,
		'server'			=>	Array(
			'host'				=>	'entrey.com.br',
			'user'				=>	'noreply@entrey.com.br',
			'pass'				=>	'&WE]Abh(ei2(',
			'secure'			=>	true,
			'port'				=>	465,
		),
		'sender'			=>	Array(
			'from'				=>	'noreply@entrey.com.br',
			'fromName'			=>	'Entrey',
			'replyTo'			=>	'noreply@entrey.com.br',
			'replyToName'		=>	'Entrey',
		),
	);
	
	// ===== Configuração do server de cada host de usuário
	
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
	
	$_GESTOR['plataforma-id'] 		= 'producao'; 
}

// ===== Definições de variáveis padrões do sistema que dependem de host 

$_GESTOR['url-full']							=	'//'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];
$_GESTOR['url-full-http']						=	'https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];

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
	'versao' => '1.2.0',
	'versao_num' => 16,
);

// ===== Definição e inclusão de todas as bibliotecas necessárias para o funcionamento do gestor

$_GESTOR['bibliotecas-dados'] = Array(
	'banco' => Array('banco.php'),
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