<?php

/**********
	Descrição: autenticação configurações gerais.
**********/

global $_CONFIG;

// ===== Definições de variáveis de sessão.

$_CONFIG['session-authname']					=	'_BPSID'; // Nome do cookie de sessão de usuário
$_CONFIG['session-lifetime']					=	3600*3; // 3 horas para as sessões serem deletadas
$_CONFIG['session-garbagetime']					=	3600*24; // 24 horas para tokens de sessões serem deletados
$_CONFIG['session-garbage-colector-time']		=	3600; // 1 hora para sessões antigas serem deletados.

// ===== Definições de variáveis de cookie.

$_CONFIG['cookie-authname']						=	'_BPUSID'; // Nome do cookie de sessão de autenticação de usuário
$_CONFIG['cookie-verify']						=	'_BPCVID'; // Nome do cookie de sessão de autenticação de usuário
$_CONFIG['cookie-lifetime']						=	86400*15; // 15 dias para os cookie do token de acesso serem deletados
$_CONFIG['cookie-renewtime']					=	3600*24; // 24 horas para renovar automaticamente cookie do token de acesso

// ===== Definições de acesso à chave de segurança principal.

$_CONFIG['openssl-password']					=	'SECRET'; // Senha da chave RSA atual

// ===== Definições de segurança e outras configurações de usuário.

$_CONFIG['usuario-hash-password']				=	'SECRET'; // Senha de gerador de hash
$_CONFIG['usuario-hash-algo']					=	'sha512'; // Algorítmo usado para criar hash
$_CONFIG['usuario-recaptcha-active']			=	true; // Ativar Recaptcha v3 
$_CONFIG['usuario-recaptcha-site']				=	'SECRET'; // Recaptcha site
$_CONFIG['usuario-recaptcha-server']			=	'SECRET'; // Recaptcha server
$_CONFIG['usuario-maximo-senhas-invalidas']		=	3; // Máximo de vezes que pode errar a senha em autorização provisória, antes de ser deconectado automaticamente
$_CONFIG['usuario-autorizacao-lifetime']		=	60*5; // 5 minutos definido / Tempo máximo em segundos que uma autorização provisória é válida.
$_CONFIG['token-lifetime']						=	3600*1; // 1 hora para os tokens provisórios serem deletadas.
$_CONFIG['plano-teste-id-usuario-perfil']		=	'2'; // Identificador do perfil do usuário para planos testes (TRIAL).
$_CONFIG['autenticacao-token-lifetime']			=	86400*180; // 180 dias para os token de autenticacao serem expirados.

// ===== Definições de segurança e outras configurações da plataforma cliente.

$_CONFIG['platform-lifetime']					=	60*15; // 15 minutos para os tokens de acesso a plataforma serem deletados.
$_CONFIG['platform-hash-password']				=	'SECRET'; // Senha de gerador de hash
$_CONFIG['platform-hash-algo']					=	'sha512'; // Algorítmo usado para criar hash
$_CONFIG['platform-recaptcha-active']			=	true; // Ativar Recaptcha v3 nos hosts
$_CONFIG['platform-recaptcha-site']				=	'SECRET'; // Recaptcha site nos hosts
$_CONFIG['platform-recaptcha-server']			=	'SECRET'; // Recaptcha server nos hosts

// ===== Definições de segurança e outras configurações do aplicativo.

$_CONFIG['app-recaptcha-active']				=	false; // Ativar Recaptcha v3 no APP
$_CONFIG['app-token-lifetime']					=	86400*30; // 30 dias para os token de acesso serem deletados.
$_CONFIG['app-token-renewtime']					=	3600*24; // 24 horas para renovar automaticamente o token de acesso
$_CONFIG['app-origem']							=	'app'; // Identificador do APP 

// ===== Definições de controle de acessos.

$_CONFIG['acessos-maximo-falhas-logins']		=	10; // Máximo de falhas de logins antes de bloquear o IP de acesso.
$_CONFIG['acessos-maximo-logins-simples']		=	3; // Máximo de falhas de logins antes de ativar anti-spam de formulários (reCAPTCHA).
$_CONFIG['acessos-tempo-bloqueio-ip']			=	86400*1; // Tempo inicial de limite de bloqueio de um IP.
$_CONFIG['acessos-tempo-desbloqueio-ip']		=	86400*30; // Tempo de desbloqueio de um IP.
$_CONFIG['acessos-maximo-cadastros']			=	Array( // Máximo de cadastros antes de bloquear o IP para novos cadastros por tipo.
	'signup' => 1,
	'formulario-contato' => 10,
);
$_CONFIG['acessos-maximo-cadastros-simples']	=	Array( // Máximo de cadastros antes de ativar o antispam para novos cadastros por tipo.
	'signup' => 1,
	'formulario-contato' => 3,
);

// ===== Configuração do serviço de emails.

$_CONFIG['email'] = Array(
	'ativo'				=>	true,
	'server'			=>	Array(
		'host'				=>	'b2make.com',
		'user'				=>	'noreply@b2make.com',
		'pass'				=>	'secret',
		'secure'			=>	true,
		'port'				=>	465,
	),
	'sender'			=>	Array(
		'from'				=>	'noreply@b2make.com',
		'fromName'			=>	'B2make',
		'replyTo'			=>	'noreply@b2make.com',
		'replyToName'		=>	'B2make',
	),
);

?>