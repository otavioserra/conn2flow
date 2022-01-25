<?php

global $_GESTOR;

$_GESTOR['biblioteca-configuracao']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function configuracao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variavel - Tipo - Obrigatório|Opcional - Descrição.
	
	// ===== 
	
	
}

?>