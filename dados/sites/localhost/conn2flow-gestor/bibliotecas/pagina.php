<?php

global $_GESTOR;

$_GESTOR['biblioteca-pagina']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function pagina_celula($nome,$comentario = false,$apagar = false){
	/**********
		Descrição: trocar variável da página por um valor.
	**********/
	
	global $_GESTOR;
	
	if(isset($nome)){
		if($comentario){
			$celula = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$nome.' [[',']] '.$nome.' -->'); 
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$nome.' [[',']] '.$nome.' -->',($apagar ? '' : '<!-- [['.$nome.']] -->'));
		} else {
			$celula = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$nome.' < -->','<!-- '.$nome.' > -->');
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$nome.' < -->','<!-- '.$nome.' > -->',($apagar ? '' : '<!-- '.$nome.' -->'));
		}
		
		return $celula;
	}
	
	return '';
}

function pagina_celula_trocar_variavel_valor($celula,$variavel,$valor,$variavelEspecifica = false){
	/**********
		Descrição: trocar variável de uma célula específica por um valor.
	**********/
	
	global $_GESTOR;
	
	if(isset($celula) && isset($variavel)){
		if($variavelEspecifica){
			return modelo_var_troca_tudo($celula,$variavel,(isset($valor) ? $valor : ''));
		} else {
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			
			return modelo_var_troca_tudo($celula,$open.$variavel.$close,(isset($valor) ? $valor : ''));
		}
	} else {
		return $celula;
	}
}

function pagina_celula_incluir($celula,$valor){
	/**********
		Descrição: incluir célula na página.
	**********/
	
	global $_GESTOR;
	
	if(isset($celula) && isset($valor)){
		$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$celula.' -->',(isset($valor)) ? $valor : '');
	}
}

function pagina_trocar_variavel_valor($variavel,$valor,$variavelEspecifica = false){
	/**********
		Descrição: trocar variável da página por um valor.
	**********/
	
	global $_GESTOR;
	
	if(isset($variavel) && isset($valor)){
		if($variavelEspecifica){
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$variavel,$valor);
		} else {
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$variavel.$close,$valor);
		}
	}
}

function pagina_trocar_variavel($params = false){
	/**********
		Descrição: trocar variável por um valor de um código qualquer.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// codigo - String - Obrigatório - Código com a variável.
	// variavel - String - Obrigatório - Variável a ser procurada.
	// valor - String - Obrigatório - Valor a ser trocado.
	
	// ===== 
	
	if(isset($codigo) && isset($variavel) && isset($valor)){
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		
		return modelo_var_troca_tudo($codigo,$open.$variavel.$close,$valor);
	}
}

function pagina_variaveis_globais_mascarar($params = false){
	/**********
		Descrição: mascarar todas as variáveis globais de [[variavel-nome]] para @[[variavel-nome]]@ afim de incluir no banco de dados o resultado.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - Valor a ser mascarado para guardar no banco de dados.
	
	// ===== 
	
	if(isset($valor)){
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		return preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $valor);
	}
	
	return '';
}

function pagina_variaveis_globais_desmascarar($params = false){
	/**********
		Descrição: desmascarar todas as variáveis globais de @[[variavel-nome]]@ para [[variavel-nome]] vinda do banco de dados para usar livremente o texto.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - Valor a ser desmascarado vindo do banco de dados.
	
	// ===== 
	
	if(isset($valor)){
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		return preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $valor);
	}
	
	return '';
}

?>