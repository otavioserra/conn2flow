<?php

global $_GESTOR;

$_GESTOR['biblioteca-geral']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function geral_nl2br($string = ''){
	/**********
		Descrição: incluir <br> antes do fim de cada linha
	**********/
	
	if(existe($string)){
		return nl2br($string);
	}
	
	return $string;
}

?>