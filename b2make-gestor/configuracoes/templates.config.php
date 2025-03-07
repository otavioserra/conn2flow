<?php

/**********
	Descrição: configuração dos templates de páginas.
**********/

// ===== Definições da variável.

$configuracao = Array(
	'versao' => 1,
	'atualizar' => Array( // Templates que deverão serem atualizados na lista de todos os outros com suas configurações.
		'layout-principal' => Array(),
		'layout-simples' => Array(),
		'pagina-nao-encontrada' => Array(
			'layout-id' => 'layout-principal', 
			'caminho' => '404/',
			'tipo' => 'pagina',
		),
		'pagina-inicial' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => '/',
			'tipo' => 'pagina',
		),
		'carrinho' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'carrinho/',
			'tipo' => 'pagina',
			'modulo' => 'carrinho',
			'sem_permissao' => true,
		),
		'identificacao' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'identificacao/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao',
			'sem_permissao' => true,
		),
		'identificacao-area-restrita' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'identificacao-area-restrita/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-area-restrita',
		),
		'identificacao-cadastro' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'identificacao-cadastro/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-cadastro',
			'sem_permissao' => true,
		),
		'identificacao-esqueceu-senha' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'identificacao-esqueceu-senha/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-esqueceu-senha',
			'sem_permissao' => true,
		),
		'identificacao-redefinir-senha' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'identificacao-redefinir-senha/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-redefinir-senha',
			'sem_permissao' => true,
		),
		'emissao' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'emissao/',
			'tipo' => 'pagina',
			'modulo' => 'emissao',
		),
		'pagamento' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'pagamento/',
			'tipo' => 'pagina',
			'modulo' => 'pagamento',
		),
		'voucher' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'voucher/',
			'tipo' => 'pagina',
			'modulo' => 'voucher',
		),
		'minha-conta' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'minha-conta/',
			'tipo' => 'pagina',
			'modulo' => 'minha-conta',
		),
		'meus-pedidos' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'meus-pedidos/',
			'tipo' => 'pagina',
			'modulo' => 'meus-pedidos',
		),
		'meus-dados' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'meus-dados/',
			'tipo' => 'pagina',
			'modulo' => 'meus-dados',
		),
		'servicos' => Array(
			'multiplo' => true,
			'sem_permissao' => true,
		),
		'postagens' => Array(
			'multiplo' => true,
			'sem_permissao' => true,
		),
		// Plugin: Agendamentos
		'agendamentos' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'agendamentos/',
			'tipo' => 'pagina',
			'modulo' => 'agendamentos-host',
			'plugin' => 'agendamentos',
		),
		'agendamentos-publico' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'agendamentos-publico/',
			'tipo' => 'pagina',
			'modulo' => 'agendamentos-host-publico',
			'plugin' => 'agendamentos',
			'sem_permissao' => true,
		),
		// Plugin: Escalas
		'escalas' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => 'escalas/',
			'tipo' => 'pagina',
			'modulo' => 'escalas-host',
			'plugin' => 'escalas',
		),
	),
	'layouts' => Array( // Identificadores dos layouts de cada página template.
		'servicos' => 'layout-principal',
		'postagens' => 'layout-principal',
	),
);

// ===== Retorno da variável.

return $configuracao;

?>