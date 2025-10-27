<?php

/**
 * Biblioteca de Plugins - Template base para funções de plugins
 *
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-template']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Gera uma opção de template (função exemplo/template).
 *
 * Função template que serve como exemplo de estrutura para novas
 * funções relacionadas a plugins. Deve ser renomeada e adaptada
 * conforme a necessidade específica.
 *
 * @param array|false $params Array de parâmetros nomeados ou false.
 * @return void
 */
function template_opcao($params = false){
	global $_GESTOR;
	
	// Extrai variáveis do array de parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros esperados:
	// 
	// variavel - Tipo - Obrigatório|Opcional - Descrição da variável.
	// 
	// ===== 
	
	// Implementação da lógica
	if(isset($variavel)){
		// Código a ser implementado
	}
}

?>