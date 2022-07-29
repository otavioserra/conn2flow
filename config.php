<?php

/**********
	Descrição: Configuração do plugin: escalas.
**********/

// ===== Definições da variável configuração.

$configuracao = Array(
	'id' => 'escalas',
	'versao_num' => 1,
	'versao' => '1.0.0',
	'moduloConfig' => 'configuracoes-escalas',
	'menusPadroes' => Array(
		'menuMinhaConta' => Array(
			'versao' => 2,
			'itens' => Array(
				'escalas-host' => Array(
					'label' => 'label-escalas',
					'tipo' => 'url-interna',
					'url' => '/escalas/',
				),
			),
		),
	),
);

// ===== Retorno da variável.

return $configuracao;

?>