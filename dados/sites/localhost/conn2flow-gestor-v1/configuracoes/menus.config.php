<?php

/**********
	Descrição: Menus configurações.
**********/

// ===== Definições da variável.

$configuracao = Array(
	'menusPadroes' => Array(
		'menuPaginaInicial' => Array(
			'inativo' => true,
			'versao' => 1,
			'itens' => Array(
				'sobre' => Array(
					'label' => 'label-sobre',
					'tipo' => 'menu-ancora',
					'url' => '#sobre',
				),
				'local-e-data' => Array(
					'label' => 'label-local-e-data',
					'tipo' => 'menu-ancora',
					'url' => '#local-e-data',
				),
				'ingressos' => Array(
					'label' => 'label-ingressos',
					'tipo' => 'menu-ancora',
					'url' => '#ingressos',
				),
				'mapa' => Array(
					'label' => 'label-mapa',
					'tipo' => 'menu-ancora',
					'url' => '#mapa',
				),
			),
		),
		'menuMinhaConta' => Array(
			'versao' => 1,
			'itens' => Array(
				'meus-pedidos' => Array(
					'label' => 'label-meus-pedidos',
					'tipo' => 'url-interna',
					'url' => '/meus-pedidos/',
				),
				'meus-dados' => Array(
					'label' => 'label-meus-dados',
					'tipo' => 'url-interna',
					'url' => '/meus-dados/',
				),
				'sair' => Array(
					'label' => 'label-sair',
					'tipo' => 'url-interna',
					'url' => '/identificacao/?sair=sim',
				),
			),
		),
	),
);

// ===== Retorno da variável.

return $configuracao;

?>