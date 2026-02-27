<?php
/**
 * Biblioteca de widgets do sistema.
 *
 * Gerencia widgets reutilizáveis do Conn2Flow.
 * 
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 2.0.0
 */

global $_GESTOR;

// Registro da versão da biblioteca e configurações de widgets
$_GESTOR['biblioteca-widgets']							=	Array(
	'versao' => '2.0.0',
);

// ===== Funções auxiliares


// ===== Funções principais

/**
 * Processa e renderiza um widget completo por ID.
 *
 * Função principal para obter widgets. Busca o widget e devolve o controlador específico do widget para processar o HTML, além de incluir CSS e JS necessários.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador único do widget (obrigatório).
 * 
 * @return string HTML processado e completo do widget ou string vazia se não encontrado.
 */
function widgets_get($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida ID fornecido
	if(isset($id)){
		// o identificador pode ser uma string simples (nome do widget) ou
		// no formato "MODULO_ID->FUNCAO(JSON_PARAMS)". Neste último caso
		// incluímos o arquivo PHP do módulo, decodificamos os parâmetros
		// e chamamos a função solicitada para obter o HTML de retorno.
		$callbackResult = '';

		// tenta capturar módulo, função e parâmetros via regex
		if(preg_match('/^([a-zA-Z0-9_\-]+)->([a-zA-Z0-9_\-]+)\((.*)\)$/', $id, $m)){
			$module = $m[1];
			$func   = $m[2];
			$json   = $m[3];

			// converter JSON para array (params opcionais)
			$paramsArray = json_decode($json, true);
			if(!is_array($paramsArray)){
				$paramsArray = array();
			}

			// incluir o arquivo do widget do módulo se existir
			$widgetFile = $_GESTOR['modulos-path'] . $module . '/' . $module . '.widget.php';
			if(file_exists($widgetFile)){
				require_once($widgetFile);
			}

			// chamar a função se estiver disponível
			if(function_exists($func)){
				$callbackResult = call_user_func($func, $paramsArray);
			}
		}

		// caso não seja o formato modular, tentar recuperar widget clássico
		if($callbackResult === ''){
			// pesquisa simples por ID — mantém compatibilidade com versões anteriores
			// Este trecho pode ser adaptado para buscar widgets armazenados no banco,
			// mas por enquanto retorna vazio.
			// Ex: widgets armazenados em pastas de recursos, etc.
		}

		return $callbackResult;
	}
	
	return '';
}

?>