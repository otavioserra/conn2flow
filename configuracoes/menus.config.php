<?php

/**********
	Descrição: Menus configurações.
**********/

// ===== Definições da variável.

$configuracao = Array(
	'menus-padroes' => Array(
		'menu-pagina-inicial' => Array(
			'versao' => '1',
			'label' => 'tit-menu-pagina-inicial',
			'items' => Array(
				'sobre' => Array(
					'label' => 'label-sobre',
					'tipo' => 'menu-ancora',
					'url' => '#sobre',
					'ordem' => '1',
				),
				'local-e-data' => Array(
					'label' => 'label-local-e-data',
					'tipo' => 'menu-ancora',
					'url' => '#local-e-data',
					'ordem' => '2',
				),
				'ingressos' => Array(
					'label' => 'label-ingressos',
					'tipo' => 'menu-ancora',
					'url' => '#ingressos',
					'ordem' => '3',
				),
				'mapa' => Array(
					'label' => 'label-mapa',
					'tipo' => 'menu-ancora',
					'url' => '#mapa',
					'ordem' => '4',
				),
			),
		),
	),
);

// ===== Retorno da variável.

return $configuracao;

?>