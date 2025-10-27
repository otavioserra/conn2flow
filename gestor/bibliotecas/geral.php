<?php

global $_GESTOR;

$_GESTOR['biblioteca-geral']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Converte quebras de linha em tags HTML <br>.
 *
 * Inclui a tag <br> antes do fim de cada linha na string fornecida,
 * facilitando a exibição de textos com quebras de linha em HTML.
 *
 * @param string $string A string onde as quebras de linha serão convertidas.
 * @return string Retorna a string com as tags <br> incluídas, ou a string original se vazia.
 */
function geral_nl2br($string = ''){
	// Verifica se a string existe e não está vazia
	if(existe($string)){
		// Converte quebras de linha (\n) em tags HTML <br>
		return nl2br($string);
	}
	
	// Retorna a string original se estiver vazia
	return $string;
}

?>