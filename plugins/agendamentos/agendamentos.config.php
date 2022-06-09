<?php

/**********
	Descrição: Configuração do plugin: agendamentos.
**********/

// ===== Definições da variável configuração.

$configuracao = Array(
	'id' => 'agendamentos',
	'versao_num' => 1,
	'versao' => '1.0.0',
	'cronAtivo' => true,
	'moduloConfig' => 'configuracoes-agendamentos',
	'menusPadroes' => Array(
		'menuMinhaConta' => Array(
			'versao' => 1,
			'itens' => Array(
				'agendamentos' => Array(
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