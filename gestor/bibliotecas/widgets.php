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
 * @global bool $_GESTOR['ajax'] Indica se a chamada é via AJAX.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador único do widget (obrigatório).
 * @param string $params['html'] (opcional) HTML estático capturado entre os marcadores
 *                               de wrapper `<!-- widgets#... < -->` e `<!-- widgets#... > -->`
 *                               em gestor_pagina_widgets(). Repassado ao callback do widget
 *                               através de $paramsArray['html'] para uso como template/preview
 *                               de mockup conforme arquitetura BATCH-008.
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

			// BATCH-008: Injetar o HTML estático capturado do wrapper na chave 'html'
			// dos parâmetros do callback. Permite ao widget acessar o mockup original da
			// página física para preview visual ou debug. Não substitui a renderização
			// real (que vem do banco de dados conforme D-023).
			if(isset($html)){
				$paramsArray['html'] = $html;
			}

			// incluir o arquivo do widget do módulo se existir
			$widgetFile = $_GESTOR['modulos-path'] . $module . '/' . $module . '.widget.php';
			if(file_exists($widgetFile)){
				require_once($widgetFile);
			}

			// Resolver o nome real da função PHP a chamar.
			// Convenção: módulo com hífen é normalizado para underscore e prefixado
			// no nome da função para evitar colisões entre widgets diferentes que
			// usem nomes genéricos como `render` na assinatura do wrapper. Ex:
			//   widgets#publisher-highlights->render(...)  =>  publisher_highlights_render
			//   widgets#dynamic-menus->render(...)        =>  dynamic_menus_render
			// Por compatibilidade retrógrada, se o nome prefixado não existir,
			// caímos no nome bare informado na assinatura.
			$module_underscored = str_replace('-', '_', $module);
			$prefixedFunc = $module_underscored . '_' . $func;

			$resolvedFunc = null;
			if($_GESTOR['ajax']){
				if(function_exists($prefixedFunc.'_ajax')){
					$resolvedFunc = $prefixedFunc.'_ajax';
				} else if(function_exists($func.'_ajax')){
					$resolvedFunc = $func.'_ajax';
				}
			} else {
				if(function_exists($prefixedFunc)){
					$resolvedFunc = $prefixedFunc;
				} else if(function_exists($func)){
					$resolvedFunc = $func;
				}
			}

			if($resolvedFunc !== null){
				if(!$_GESTOR['ajax']){
					// Incluir o widget no registro de widgets AJAX para uso posterior das chamadas AJAX para esse widget.
					if (!isset($_GESTOR['widgetsToAjax'])) {
						$widgetsToAjax = '';
					} else {
						$widgetsToAjax = $_GESTOR['widgetsToAjax'];
					}

					$widgetsAjaxList = array_filter(explode('<#;>', $widgetsToAjax));
					if (!in_array($id, $widgetsAjaxList, true)) {
						$widgetsAjaxList[] = $id;
						$_GESTOR['widgetsToAjax'] = implode('<#;>', $widgetsAjaxList);
					}
				}

				$callbackResult = call_user_func($resolvedFunc, $paramsArray);
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