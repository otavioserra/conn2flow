<?php

/**********
	Descrição: Loja configurações.
**********/

// ===== Definições da variável.

$configuracao = Array(
	'campos' => Array(
		'nome' => Array(),
		'continuarComprando' => Array(),
		'cnpj' => Array(),
		'cpf' => Array(),
		'endereco' => Array(),
		'numero' => Array(),
		'complemento' => Array(),
		'bairro' => Array(),
		'cidade' => Array(),
		'uf' => Array(),
		'pais' => Array(),
		'cep' => Array(),
		'telefone' => Array(),
		'logomarca' => Array(),
	),
	'pedidos' => Array(
		'codigoInicial' => 10000, // Valor inicial dos códigos de pedidos.
		'prazoPagamento' => 60*60*24, // 24 horas para prazo de pagamento. Senão o pedido é cancelado e os serviços retornados ao estoque.
	),
);

// ===== Retorno da variável.

return $configuracao;

?>