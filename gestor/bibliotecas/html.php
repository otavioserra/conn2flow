<?php
/**
 * Biblioteca de manipulação HTML via DOM.
 *
 * Fornece funções para manipulação de documentos HTML usando DOMDocument.
 * Permite consultas XPath, modificação de atributos, valores e estrutura.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-html']							=	Array(
	'versao' => '1.0.0',
);

/**
 * Inicializa objeto DOMDocument para manipulação HTML.
 *
 * Cria novo objeto DOM para parsing e manipulação de HTML.
 * Pode usar HTML fornecido ou pegar da página do gestor.
 *
 * @global array $_HTML Armazena objeto DOM em $_HTML['dom'].
 * @global array $_GESTOR Fonte da página se gestor=true.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['valor'] HTML a processar (obrigatório se gestor não definido).
 * @param bool $params['gestor'] Se true, usa $_GESTOR['pagina'] como fonte (opcional).
 * 
 * @return void
 */
function html_iniciar($params = false){
	global $_HTML;
	global $_GESTOR;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Usa página do gestor se solicitado
	if(isset($gestor)){
		if(existe($_GESTOR['pagina'])){
			$valor = $_GESTOR['pagina'];
		}
	}
	
	// Cria objeto DOM e carrega HTML
	if(isset($valor)){
		$_HTML['dom'] = new DOMDocument("1.0", "UTF-8");
		$_HTML['dom']->loadHTML(mb_convert_encoding($valor, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD);
	}
}

/**
 * Finaliza e retorna HTML do objeto DOM.
 *
 * Salva objeto DOM de volta para HTML, remove tags wrapper automáticas,
 * e opcionalmente grava na página do gestor.
 *
 * @global array $_HTML Contém objeto DOM.
 * @global array $_GESTOR Destino da página se gestor=true.
 * 
 * @param array|false $params Parâmetros da função.
 * @param bool $params['gestor'] Se true, grava em $_GESTOR['pagina'] (opcional).
 * 
 * @return string|void HTML processado ou void se gestor=true.
 */
function html_finalizar($params = false){
	global $_HTML;
	global $_GESTOR;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Salva DOM de volta para HTML
	if(isset($_HTML['dom'])){
		$retorno = html_entity_decode($_HTML['dom']->saveHTML());
		
		// Remove tags wrapper automáticas do DOMDocument
		$retorno = preg_replace('/<html><body>/i', '', $retorno);
		$retorno = preg_replace('/<\/body><\/html>/i', '', $retorno);
		
		// Limpa objeto DOM
		$_HTML['dom'] = null;
		
		// Grava em gestor ou retorna
		if(isset($gestor)){
			$_GESTOR['pagina'] = $retorno;
			return;
		} else {
			return $retorno;
		}
	}
	
	// Retorna vazio se objeto DOM não existe
	return '';
}

/**
 * Executa consulta XPath no objeto DOM.
 *
 * Permite consultas flexíveis usando sintaxe XPath no documento HTML.
 *
 * @global array $_HTML Contém objeto DOM.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['valor'] Expressão XPath (obrigatório).
 * 
 * @return DOMNodeList Resultado da consulta ou lista vazia.
 */
function html_consulta($params = false){
	global $_HTML;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Executa consulta XPath se DOM existe
	if(isset($_HTML['dom'])){
		if(isset($valor)){
			$xpath = new DOMXpath($_HTML['dom']);
			return $xpath->query($valor);
		}
	}
	
	return new DOMNodeList();
}

/**
 * Manipula atributos de elementos HTML.
 *
 * Permite obter ou modificar atributos de elementos selecionados por classe.
 *
 * @global array $_HTML Contém objeto DOM.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['opcao'] Operação: 'valor' ou 'mudar' (obrigatório).
 * @param string $params['consulta'] Nome da classe CSS (obrigatório).
 * @param string $params['atributo'] Nome do atributo (obrigatório).
 * @param string $params['valor'] Novo valor (obrigatório se opcao='mudar').
 * 
 * @return string|void Valor do atributo se opcao='valor', void se 'mudar'.
 */
function html_atributo($params = false){
	global $_HTML;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Processa opção solicitada
	if(isset($_HTML['dom'])){
		if(isset($opcao)){
			switch($opcao){
				case 'valor':
					// Retorna valor do atributo
					if(isset($consulta) && isset($atributo)){
						$nos = html_consulta(Array('valor' => "//*[contains(concat(' ',normalize-space(@class),' '),' ".$consulta." ')]"));
						
						foreach($nos as $no){
							if($no->hasAttributes()){
								return $no->getAttribute($atributo);
							}
						}
					}
				break;
				case 'mudar':
					// Modifica valor do atributo
					if(isset($consulta) && isset($atributo) && isset($valor)){
						$nos = html_consulta(Array('valor' => "//*[contains(concat(' ',normalize-space(@class),' '),' ".$consulta." ')]"));
						
						foreach($nos as $no){
							$no->setAttribute($atributo,$valor);
						}
						
						return;
					}
				break;
				default:
					
			}
		}
	}
	
	return '';
}

/**
 * Manipula valor (nodeValue) de elementos HTML.
 *
 * Modifica conteúdo textual de elementos selecionados por classe.
 *
 * @global array $_HTML Contém objeto DOM.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['opcao'] Operação: 'mudar' (obrigatório).
 * @param string $params['consulta'] Nome da classe CSS (obrigatório).
 * @param string $params['valor'] Novo valor textual (obrigatório).
 * 
 * @return void
 */
function html_valor($params = false){
	global $_HTML;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Processa opção solicitada
	if(isset($_HTML['dom'])){
		if(isset($opcao)){
			switch($opcao){
				case 'mudar':
					// Modifica valor textual do elemento
					if(isset($consulta) && isset($valor)){
						$nos = html_consulta(Array('valor' => "//*[contains(concat(' ',normalize-space(@class),' '),' ".$consulta." ')]"));
						
						foreach($nos as $no){
							$no->nodeValue = $valor;
						}
						
						return;
					}
				break;
				default:
					
			}
		}
	}
	
	return '';
}

/**
 * Adiciona classe CSS a elemento.
 *
 * Adiciona nova classe ao atributo class existente do elemento.
 *
 * @global array $_HTML Contém objeto DOM.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['consulta'] Nome da classe CSS existente (obrigatório).
 * @param string $params['classe'] Nova classe a adicionar (obrigatório).
 * 
 * @return void
 */
function html_adicionar_classe($params = false){
	global $_HTML;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Adiciona classe ao elemento
	if(isset($consulta) && isset($classe)){
		// Obtém classes atuais
		$valor = html_atributo(Array(
			'consulta' => $consulta,
			'atributo' => 'class',
			'opcao' => 'valor',
		));
		
		// Adiciona nova classe
		$valor = trim($valor) . ' ' . $classe;
		
		// Atualiza atributo class
		html_atributo(Array(
			'consulta' => $consulta,
			'atributo' => 'class',
			'opcao' => 'mudar',
			'valor' => $valor
		));
	}
}

/**
 * Manipula estrutura de elementos HTML.
 *
 * Permite operações estruturais como exclusão de elementos.
 *
 * @global array $_HTML Contém objeto DOM.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['opcao'] Operação: 'excluir' (obrigatório).
 * @param string $params['consulta'] Nome da classe CSS (obrigatório).
 * 
 * @return void
 */
function html_elemento($params = false){
	global $_HTML;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Processa opção solicitada
	if(isset($_HTML['dom'])){
		if(isset($opcao)){
			switch($opcao){
				case 'excluir':
					// Remove elementos do DOM
					if(isset($consulta)){
						$nos = html_consulta(Array('valor' => "//*[contains(concat(' ',normalize-space(@class),' '),' ".$consulta." ')]"));
						
						foreach($nos as $no){
							$no->parentNode->removeChild($no);
						}
						
						return;
					}
				break;
				default:
					
			}
		}
	}
}

/**
 * Formata e embeleza HTML usando Tidy.
 *
 * Aplica indentação e formatação consistente ao HTML.
 *
 * @param string $html HTML a formatar.
 * 
 * @return tidy Objeto tidy com HTML formatado.
 */
function html_beautify($html){
	// Configuração do Tidy para formatação
	$config = [
		'indent'         		=> true,
		'indent-spaces'         => 4,
		'output-xhtml'   		=> false,
		'output-html'   		=> true,
		'wrap-script-literals' 	=> true,
		'show-body-only' 		=> true,
		'wrap' 					=> 200,
		'break-before-br' 		=> true,
	];

	// Processa HTML com Tidy
	$tidy = new tidy;
	$tidy->parseString(stripslashes($html), $config, 'utf8');
	$tidy->cleanRepair();

	return $tidy;
}

?>