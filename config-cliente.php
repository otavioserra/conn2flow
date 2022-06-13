<?php

// ===== Definições de variáveis gerais do gestor

$_GESTOR['versao']								=	'1.0.0'; // Versão do gestor como um todo.
$_GESTOR['id']									=	'entrey-'; // Identificador básico do gestor
$_GESTOR['modulos-caminho']						=	'bibliotecas/'; // Caminho relativo a raiz dos módulos bibliotecas do gestor
$_GESTOR['session-authname']					=	'_HSID'; // Nome do cookie de sessão de usuário
$_GESTOR['session-lifetime']					=	60*60*3; // 3 horas para as sessões serem deletadas
$_GESTOR['session-garbagetime']					=	60*60*24; // 24 horas para tokens de sessões serem deletados
$_GESTOR['session-garbage-colector-time']		=	60*60; // 1 hora para sessões antigas serem deletados.
$_GESTOR['cookie-authname']						=	'_HUSID'; // Nome do cookie de sessão de autenticação de usuário
$_GESTOR['cookie-verify']						=	'_HCVID'; // Nome do cookie de sessão de autenticação de usuário
$_GESTOR['cookie-lifetime']						=	60*60*24*15; // 15 dias para os cookie do token de acesso serem deletados
$_GESTOR['cookie-renewtime']					=	60*60*24; // 24 horas para renovar automaticamente cookie do token de acesso
$_GESTOR['platform-lifetime']					=	60*15; // 15 minutos para os tokens de acesso a plataforma serem deletados.
$_GESTOR['token-lifetime']						=	60*60*1; // 1 hora para os tokens provisórios serem deletadas.
$_GESTOR['usuario-autorizacao-lifetime']		=	60*5; // 5 minutos definido / Tempo máximo em segundos que uma autorização provisória é válida.

// ===== Definição dos marcadores de abertura e fechamento de varíaveis globais.

$_GESTOR['variavel-global']						=	Array(
	'open' => '@[[', // Abertura de uma variável na execução da mesma
	'close' => ']]@', // Fechamento de uma variável na execução da mesma
	'openText' => '[[', // Abertura de uma variável na definição da mesma
	'closeText' => ']]', // Fechamento de uma variável na definição da mesma
);

// ===== Definições de variáveis padrões do sistema que dependem de host 

$_GESTOR['url-raiz']							=	'/';
$_GESTOR['url-full']							=	'//'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];
$_GESTOR['url-full-http']						=	'https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'];

// ===== Arquivos caminhos

$_GESTOR['ROOT_PATH']							=	$_SERVER['DOCUMENT_ROOT'].'/'.$_INDEX['sistemas-dir'];

$_GESTOR['contents-path']						=	$_GESTOR['ROOT_PATH'].'contents';
$_GESTOR['contents-basedir']					=	'files';
$_GESTOR['contents-thumbnail']					=	'mini';
$_GESTOR['plugins-path']						=	$_GESTOR['ROOT_PATH'].'plugins/';

// ===== Definição e inclusão de todas as bibliotecas necessárias para o funcionamento do gestor

$_GESTOR['bibliotecas-dados'] = Array(
	'banco' => Array('banco.php'),
	'modelo' => Array('modelo.php'),
	'api-servidor' => Array('api-servidor.php'),
	'pagina' => Array('pagina.php'),
	'formato' => Array('formato.php'),
	'html' => Array('html.php'),
	'layout' => Array('layout.php'),
	'interface' => Array('interface.php'),
	'formulario' => Array('formulario.php'),
	'usuario' => Array('usuario.php'),
	'log' => Array('log.php'),
);

if($_GESTOR['bibliotecas'])
foreach($_GESTOR['bibliotecas'] as $_biblioteca){
	$_caminhos = $_GESTOR['bibliotecas-dados'][$_biblioteca];
	
	if($_caminhos)
	foreach($_caminhos as $_caminho){
		include($_GESTOR['modulos-caminho'].$_caminho);
	}
}

?>