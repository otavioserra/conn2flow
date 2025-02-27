<?php

/**********
	Descrição: autenticação nos bancos de dados. 
		Para cada banco de dados de ambiente diferente, é necessário definir uma entrada no array $_BANCOS conforme a sintaxe abaixo.
**********/

global $_BANCOS;

// ===== Definições das variáveis de cada banco.

$_BANCOS['localhost'] = Array(
	'tipo'				=>	'mysqli',
	'usuario'			=>	'user',
	'senha'				=>	'secret',
	'nome'				=>	'db_name',
	'host'				=>	'127.0.0.1',
);

$_BANCOS['b2make.com'] = Array(
	'tipo'				=>	'mysqli',
	'usuario'			=>	'user',
	'senha'				=>	'secret',
	'nome'				=>	'db_name',
	'host'				=>	'localhost',
);

$_BANCOS['beta.b2make.com'] = Array(
	'tipo'				=>	'mysqli',
	'usuario'			=>	'user',
	'senha'				=>	'secret',
	'nome'				=>	'db_name',
	'host'				=>	'localhost',
);

?>