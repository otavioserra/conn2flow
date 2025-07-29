<?php

global $_GESTOR;

$_GESTOR['biblioteca-html']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function html_iniciar($params = false){
	/**********
		Descrição: cria um novo objeto HTML
	**********/
	
	global $_HTML;
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - Valor do html.
	// gestor - Bool - Opcional - Se definido, pega o valor da página do gestor.
	
	// ===== 
	
	if(isset($gestor)){
		if(existe($_GESTOR['pagina'])){
			$valor = $_GESTOR['pagina'];
		}
	}
	
	if(isset($valor)){
		$_HTML['dom'] = new DOMDocument("1.0", "UTF-8");
		$_HTML['dom']->loadHTML(mb_convert_encoding($valor, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD);
	}
}

function html_finalizar($params = false){
	/**********
		Descrição: salvar o objeto HTML e retornar o valor
	**********/
	
	global $_HTML;
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// gestor - Bool - Opcional - Se definido, grava o valor na página do gestor.
	
	// ===== 
	
	if(isset($_HTML['dom'])){
		$retorno = html_entity_decode($_HTML['dom']->saveHTML());
		
		$retorno = preg_replace('/<html><body>/i', '', $retorno);
		$retorno = preg_replace('/<\/body><\/html>/i', '', $retorno);
		
		$_HTML['dom'] = null;
		
		if(isset($gestor)){
			$_GESTOR['pagina'] = $retorno;
			return;
		} else {
			return $retorno;
		}
	}
	
	// ===== Retornar nada caso não tenha o objeto HTML.
	
	return '';
}

function html_consulta($params = false){
	/**********
		Descrição: fazer uma consulta no objeto HTML aberto.
	**********/
	
	global $_HTML;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - XPath da consulta. Exemplos: https://devhints.io/xpath.
	
	// ===== 
	
	if(isset($_HTML['dom'])){
		if(isset($valor)){
			$xpath = new DOMXpath($_HTML['dom']);
			return $xpath->query($valor);
		}
	}
	
	return new DOMNodeList();
}

function html_atributo($params = false){
	/**********
		Descrição: Manipulação de atributos.
	**********/
	
	global $_HTML;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção de manipulação de atributo.
	
	// Se opcao == 'valor'.
	
	// atributo - String - Obrigatório - Opção de consulta.
	// consulta - String - Obrigatório - Seletor para consulta (padrão tipo 'classe').
	
	// Se opcao == 'mudar'.
	
	// atributo - String - Obrigatório - Opção de consulta.
	// consulta - String - Obrigatório - Seletor para consulta (padrão tipo 'classe').
	// valor - String - Obrigatório - Valor a ser mudado.
	
	// ===== 
	
	if(isset($_HTML['dom'])){
		if(isset($opcao)){
			switch($opcao){
				case 'valor':
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

function html_valor($params = false){
	/**********
		Descrição: Manipulação do valor.
	**********/
	
	global $_HTML;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção de manipulação de atributo.
	
	// Se opcao == 'mudar'.
	
	// consulta - String - Obrigatório - Seletor para consulta (padrão tipo 'classe').
	// valor - String - Obrigatório - Valor a ser mudado.
	
	// ===== 
	
	if(isset($_HTML['dom'])){
		if(isset($opcao)){
			switch($opcao){
				case 'mudar':
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

function html_adicionar_classe($params = false){
	/**********
		Descrição: Adicionar classe a um elemento.
	**********/
	
	global $_HTML;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// consulta - String - Obrigatório - Seletor para consulta (padrão tipo 'classe').
	// classe - String - Obrigatório - Classe que será adicionada.
	
	// ===== 
	
	if(isset($consulta) && isset($classe)){
		$valor = html_atributo(Array(
			'consulta' => $consulta,
			'atributo' => 'class',
			'opcao' => 'valor',
		));
		
		$valor = trim($valor) . ' ' . $classe;
		
		html_atributo(Array(
			'consulta' => $consulta,
			'atributo' => 'class',
			'opcao' => 'mudar',
			'valor' => $valor
		));
	}
}

function html_elemento($params = false){
	/**********
		Descrição: Manipulação do valor.
	**********/
	
	global $_HTML;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção de manipulação de atributo.
	
	// Se opcao == 'excluir'.
	
	// consulta - String - Obrigatório - Seletor para consulta (padrão tipo 'classe').
	
	// ===== 
	
	if(isset($_HTML['dom'])){
		if(isset($opcao)){
			switch($opcao){
				case 'excluir':
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

?>