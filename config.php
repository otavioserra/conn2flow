<?php

/**********
	Descrição: Configuração do plugin: agendamentos. 
**********/

// ===== Definições da variável configuração.

$configuracao = Array(
	'id' => 'agendamentos',
	'versao_num' => 2,
	'versao' => '1.0.1',
	'cronAtivo' => true,
	'moduloConfig' => 'configuracoes-agendamentos',
	'menusPadroes' => Array(
		'menuMinhaConta' => Array(
			'versao' => 2,
			'itens' => Array(
				'agendamentos-host' => Array(
					'label' => 'label-agendamentos',
					'tipo' => 'url-interna',
					'url' => '/agendamentos/',
				),
			),
		),
	),
);

// ===== Retorno da variável.

return $configuracao;

?>