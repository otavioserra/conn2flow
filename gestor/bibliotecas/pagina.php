<?php
/**
 * Biblioteca de gerenciamento de páginas.
 *
 * Gerencia manipulação de conteúdo de páginas incluindo células,
 * variáveis globais e substituição de valores. Sistema de templates
 * baseado em marcadores HTML comentados.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-pagina']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Extrai e processa célula de conteúdo da página.
 *
 * Remove célula da página marcada por comentários HTML e retorna seu conteúdo.
 * Células podem ser marcadas com <!-- nome < --> ou <!-- nome [[ ]].
 *
 * @global array $_GESTOR Contém a página em $_GESTOR['pagina'].
 * 
 * @param string $nome Nome da célula a extrair (obrigatório).
 * @param bool $comentario Se true, usa formato [[ ]], senão usa < > (padrão: false).
 * @param bool $apagar Se true, remove célula completamente, senão deixa marcador (padrão: false).
 * 
 * @return string Conteúdo da célula ou string vazia.
 */
function pagina_celula($nome,$comentario = false,$apagar = false){
	global $_GESTOR;
	
	if(isset($nome)){
		// Extrai célula usando formato apropriado
		if($comentario){
			$celula = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$nome.' [[',']] '.$nome.' -->'); 
			// Substitui célula por marcador ou remove
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$nome.' [[',']] '.$nome.' -->',($apagar ? '' : '<!-- [['.$nome.']] -->'));
		} else {
			$celula = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$nome.' < -->','<!-- '.$nome.' > -->');
			// Substitui célula por marcador ou remove
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$nome.' < -->','<!-- '.$nome.' > -->',($apagar ? '' : '<!-- '.$nome.' -->'));
		}
		
		return $celula;
	}
	
	return '';
}

/**
 * Substitui variável por valor em célula específica.
 *
 * Procura e substitui variáveis dentro de uma célula de conteúdo.
 * Pode usar marcadores globais [[variavel]] ou específicos.
 *
 * @global array $_GESTOR Configurações de variáveis globais.
 * 
 * @param string $celula Conteúdo da célula (obrigatório).
 * @param string $variavel Nome da variável (obrigatório).
 * @param string $valor Valor para substituir (opcional).
 * @param bool $variavelEspecifica Se true, usa variável literal sem marcadores (padrão: false).
 * 
 * @return string Célula com variável substituída.
 */
function pagina_celula_trocar_variavel_valor($celula,$variavel,$valor,$variavelEspecifica = false){
	global $_GESTOR;
	
	if(isset($celula) && isset($variavel)){
		if($variavelEspecifica){
			// Troca variável específica sem adicionar marcadores
			return modelo_var_troca_tudo($celula,$variavel,(isset($valor) ? $valor : ''));
		} else {
			// Adiciona marcadores globais à variável
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			
			return modelo_var_troca_tudo($celula,$open.$variavel.$close,(isset($valor) ? $valor : ''));
		}
	} else {
		return $celula;
	}
}

/**
 * Inclui célula de conteúdo na página.
 *
 * Insere valor no marcador de célula <!-- nome --> da página.
 *
 * @global array $_GESTOR Contém a página em $_GESTOR['pagina'].
 * 
 * @param string $celula Nome da célula (obrigatório).
 * @param string $valor Valor a inserir (obrigatório).
 * 
 * @return void
 */
function pagina_celula_incluir($celula,$valor){
	global $_GESTOR;
	
	if(isset($celula) && isset($valor)){
		// Insere valor no marcador da célula
		$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$celula.' -->',(isset($valor)) ? $valor : '');
	}
}

/**
 * Substitui variável por valor na página.
 *
 * Procura e substitui variáveis globais [[variavel]] na página inteira.
 *
 * @global array $_GESTOR Contém a página e configurações de variáveis.
 * 
 * @param string $variavel Nome da variável (obrigatório).
 * @param string $valor Valor para substituir (obrigatório).
 * @param bool $variavelEspecifica Se true, usa variável literal sem marcadores (padrão: false).
 * 
 * @return void
 */
function pagina_trocar_variavel_valor($variavel,$valor,$variavelEspecifica = false){
	global $_GESTOR;
	
	if(isset($variavel) && isset($valor)){
		if($variavelEspecifica){
			// Troca variável específica
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$variavel,$valor);
		} else {
			// Adiciona marcadores globais
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$variavel.$close,$valor);
		}
	}
}

/**
 * Substitui variável por valor em código arbitrário.
 *
 * Versão genérica de substituição que funciona em qualquer string,
 * não apenas na página global.
 *
 * @global array $_GESTOR Configurações de marcadores de variáveis.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['codigo'] Código com variáveis (obrigatório).
 * @param string $params['variavel'] Nome da variável (obrigatório).
 * @param string $params['valor'] Valor para substituir (obrigatório).
 * 
 * @return string|null Código com variável substituída ou null.
 */
function pagina_trocar_variavel($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($codigo) && isset($variavel) && isset($valor)){
		// Adiciona marcadores globais à variável
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		
		return modelo_var_troca_tudo($codigo,$open.$variavel.$close,$valor);
	}
}

/**
 * Mascara variáveis globais para armazenamento em banco.
 *
 * Converte [[variavel-nome]] para @[[variavel-nome]]@ para preservar
 * variáveis ao salvar em banco de dados.
 *
 * @global array $_GESTOR Configurações de marcadores de variáveis.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['valor'] Valor a mascarar (obrigatório).
 * 
 * @return string Valor mascarado ou string vazia.
 */
function pagina_variaveis_globais_mascarar($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetro obrigatório
	if(isset($valor)){
		// Obtém marcadores
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		// Substitui marcadores normais por marcadores de texto
		return preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $valor);
	}
	
	return '';
}

/**
 * Desmascara variáveis globais vindas do banco.
 *
 * Converte @[[variavel-nome]]@ de volta para [[variavel-nome]] ao
 * recuperar do banco de dados.
 *
 * @global array $_GESTOR Configurações de marcadores de variáveis.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['valor'] Valor a desmascarar (obrigatório).
 * 
 * @return string Valor desmascarado ou string vazia.
 */
function pagina_variaveis_globais_desmascarar($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetro obrigatório
	if(isset($valor)){
		// Obtém marcadores
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		// Substitui marcadores de texto por marcadores normais
		return preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $valor);
	}
	
	return '';
}

?>