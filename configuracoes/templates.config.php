<?php

/**********
	Descrição: configuração dos templates de páginas.
**********/

// ===== Definições da variável.

$configuracao = Array(
	'atualizar' => Array( // Templates que deverão serem atualizados na lista de todos os outros com suas configurações.
		'layout-principal' => Array(),
		'layout-loja' => Array(),
		'pagina-nao-encontrada' => Array(
			'layout-id' => 'layout-principal',
			'caminho' => '404/',
			'tipo' => 'pagina',
		),
		'carrinho' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'carrinho/',
			'tipo' => 'pagina',
			'modulo' => 'carrinho',
		),
		'identificacao' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'identificacao/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao',
		),
		'identificacao-area-restrita' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'identificacao-area-restrita/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-area-restrita',
		),
		'identificacao-cadastro' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'identificacao-cadastro/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-cadastro',
		),
		'identificacao-esqueceu-senha' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'identificacao-esqueceu-senha/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-esqueceu-senha',
		),
		'identificacao-redefinir-senha' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'identificacao-redefinir-senha/',
			'tipo' => 'pagina',
			'modulo' => 'identificacao-redefinir-senha',
		),
		'emissao' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'emissao/',
			'tipo' => 'pagina',
			'modulo' => 'emissao',
		),
		'pagamento' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'pagamento/',
			'tipo' => 'pagina',
			'modulo' => 'pagamento',
		),
		'voucher' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'voucher/',
			'tipo' => 'pagina',
			'modulo' => 'voucher',
		),
		'minha-conta' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'minha-conta/',
			'tipo' => 'pagina',
			'modulo' => 'minha-conta',
		),
		'meus-pedidos' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'meus-pedidos/',
			'tipo' => 'pagina',
			'modulo' => 'meus-pedidos',
		),
		'meus-dados' => Array(
			'layout-id' => 'layout-loja',
			'caminho' => 'meus-dados/',
			'tipo' => 'pagina',
			'modulo' => 'meus-dados',
		),
		'servicos' => Array(
			'multiplo' => true,
		),
		'postagens' => Array(
			'multiplo' => true,
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