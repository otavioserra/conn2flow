<?php

/**********
	Descrição: configurações globais de pedidos.
**********/

// ===== Definições da variável.

$configuracao = Array(
	'pedidosValidadeDias' => 90,
	'qrCodeGuardarBancoTempoLimiteHoras' => 3,
	'atualizarCampos' => Array(
		'id_hosts_pedidos',
		'total',
		'live',
		'status',
		'versao',
		'data_modificacao',
	),
	'criarCampos' => Array(
		'id_hosts_pedidos',
		'id_hosts_usuarios',
		'id',
		'codigo',
		'total',
		'live',
		'status',
		'versao',
		'data_criacao',
		'data_modificacao',
	),
	'vouchersAtualizarCampos' => Array(
		'id_hosts_vouchers',
		'id_hosts_pedidos',
		'id_hosts_servicos',
		'id_hosts_servicos_variacoes',
		'codigo',
		'nome',
		'documento',
		'telefone',
		'status',
		'loteVariacao',
	),
	'vouchersCriarCampos' => Array(
		'id_hosts_vouchers',
		'id_hosts_pedidos',
		'id_hosts_servicos',
		'id_hosts_servicos_variacoes',
		'codigo',
		'nome',
		'documento',
		'telefone',
		'status',
		'loteVariacao',
	),
);

// ===== Retorno da variável.

return $configuracao;

?>