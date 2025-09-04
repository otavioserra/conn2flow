<?php

// ===== Definições do banco de dados.

$_GESTOR['banco'] = Array(
	'tipo'				=>	'mysqli',
	'usuario'			=>	'#bd-user#',
	'senha'				=>	'#bd-pass#',
	'nome'				=>	'#bd-name#',
	'host'				=>	'127.0.0.1',
);

// ===== Definições da comunicação entre este host e servidor do gestor.

$_GESTOR['plataforma-cliente'] = Array(
	'id' => '#pub-id#',
	'plataforma-id' => '#plataforma-id#',
	'plataforma-recaptcha-active' => '#plataforma-recaptcha-active#',
	'plataforma-recaptcha-site' => '#plataforma-recaptcha-site#',
	'chave-seguranca' => Array(
		'chave' => '#ssl-key#',
		'senha' => '#ssl-pass#',
		'hash-algo' => '#hash-algo#',
		'hash-senha' => '#hash-pass#',
	),
	'hosts' => Array(
		Array(
			'id' => 'producao',
			'host' => '#host-production#',
		),
		Array(
			'id' => 'beta',
			'host' => '#host-beta#',
		),
	),
);

// ===== Definições de segurança.

$_GESTOR['seguranca'] = Array(
	'chave-publica' => '#seguranca-chave-publica#',
	'chave-privada' => '#seguranca-chave-privada#',
	'chave-privada-senha' => '#seguranca-chave-privada-senha#',
	'hash-algo' => '#seguranca-hash-algo#',
	'hash-senha' => '#seguranca-hash-senha#',
);

?>