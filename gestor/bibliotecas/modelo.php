<?php
/**
 * Biblioteca de manipulação de modelos/templates.
 *
 * Fornece funções para trabalhar com templates HTML, incluindo substituição
 * de variáveis, manipulação de blocos delimitados por tags, e carregamento
 * de arquivos de template.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.1.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-modelo']							=	Array(
	'versao' => '1.1.0',
);

/**
 * Substitui variáveis de input em um modelo HTML.
 *
 * Esta função permite trocar placeholders de inputs (name, id, value) em templates HTML,
 * facilitando a geração dinâmica de formulários.
 *
 * @param string $modelo O template HTML onde as substituições serão feitas.
 * @param string $name_input_in Nome do placeholder de entrada (ex: 'campo').
 * @param string $name_input_out Nome do campo de saída que substituirá o placeholder.
 * @param string $valor Valor a ser atribuído ao campo.
 * @return string Retorna o modelo com as variáveis substituídas.
 */
function modelo_input_in($modelo,$name_input_in,$name_input_out,$valor){
	// Substitui o placeholder name do input
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_name',$name_input_out);
	// Substitui o placeholder id do input
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_id',$name_input_out);
	// Substitui o placeholder value do input
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_val',$valor);
	
	return $modelo;
}

/**
 * Substitui a primeira ocorrência de uma variável em um modelo.
 *
 * Busca e substitui a primeira ocorrência de uma variável/placeholder no template.
 * Se a variável não for encontrada, o modelo é retornado sem alterações.
 * Pode receber um array associativo onde as chaves são os nomes das variáveis (sem #)
 * e os valores são as substituições, aplicando a cada par chave-valor.
 *
 * @param string $modelo O template onde a substituição será feita.
 * @param string|array $var A variável/placeholder a ser substituída, ou array de variáveis.
 * @param string $valor O valor que substituirá a variável (ignorado se $var for array).
 * @return string Retorna o modelo com a primeira ocorrência da variável substituída.
 */
function modelo_var_troca($modelo,$var,$valor = null){
	if(is_array($var)){
		// Se $var for um array, processa cada par chave-valor
		foreach($var as $key => $val){
			$placeholder = $key;
			$modelo = modelo_var_troca($modelo, $placeholder, $val);
		}
		return $modelo;
	} else {
		// Comportamento original: substitui uma única variável
		// Localiza a posição inicial da variável no modelo
		$posInicial = strpos($modelo, $var);
		
		$notFound = false;
		if($posInicial === false)
			$notFound = true;
		
		// Se a variável foi encontrada, realiza a substituição
		if(!$notFound){
			$posFinal = $posInicial+strlen($var);
			
			// Divide o modelo em duas partes: antes e depois da variável
			$parteAnterior = substr($modelo,0,$posInicial);
			$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
			
			// Reconstrói o modelo com o valor substituído
			$modelo = $parteAnterior . $valor . $partePosterior;
		}
		
		return $modelo;
	}
}

/**
 * Substitui a última ocorrência de uma variável em um modelo.
 *
 * Busca e substitui a última ocorrência de uma variável/placeholder no template
 * (case-insensitive). Se a variável não for encontrada, o modelo é retornado sem alterações.
 *
 * @param string $modelo O template onde a substituição será feita.
 * @param string $var A variável/placeholder a ser substituída.
 * @param string $valor O valor que substituirá a variável.
 * @return string Retorna o modelo com a última ocorrência da variável substituída.
 */
function modelo_var_troca_fim($modelo,$var,$valor){
	// Localiza a posição da última ocorrência da variável (case-insensitive)
	$posInicial = strripos($modelo, $var);
	
	$notFound = false;
	if($posInicial === false)
		$notFound = true;
	
	// Se a variável foi encontrada, realiza a substituição
	if(!$notFound){
		$posFinal = $posInicial+strlen($var);
		
		// Divide o modelo em duas partes: antes e depois da variável
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		// Reconstrói o modelo com o valor substituído
		$modelo = $parteAnterior . $valor . $partePosterior;
	}
	
	return $modelo;
}

/**
 * Substitui todas as ocorrências de uma variável em um modelo.
 *
 * Busca e substitui todas as ocorrências de uma variável/placeholder no template
 * usando expressão regular (case-insensitive).
 * Pode receber um array associativo onde as chaves são os nomes das variáveis (sem #)
 * e os valores são as substituições, aplicando a cada par chave-valor.
 *
 * @param string $modelo O template onde as substituições serão feitas.
 * @param string|array $var A variável/placeholder a ser substituída, ou array de variáveis.
 * @param string $valor O valor que substituirá todas as ocorrências da variável (ignorado se $var for array).
 * @return string Retorna o modelo com todas as ocorrências da variável substituídas.
 */
function modelo_var_troca_tudo($modelo,$var,$valor = null){
	if(is_array($var)){
		// Se $var for um array, processa cada par chave-valor
		foreach($var as $key => $val){
			$placeholder = $key;
			$modelo = modelo_var_troca_tudo($modelo, $placeholder, $val);
		}
		return $modelo;
	} else {
		// Comportamento original: substitui uma única variável
		// Utiliza regex para substituir todas as ocorrências (case-insensitive)
		return preg_replace('/'.preg_quote($var).'/i',$valor,$modelo);
	}
}

/**
 * Insere um valor antes da variável mantendo a variável no modelo.
 *
 * Localiza a primeira ocorrência da variável e insere o valor antes dela,
 * preservando a variável original no template. Útil para inserir conteúdo
 * antes de um placeholder sem removê-lo.
 *
 * @param string $modelo O template onde a inserção será feita.
 * @param string $var A variável/placeholder de referência.
 * @param string $valor O valor a ser inserido antes da variável.
 * @return string Retorna o modelo com o valor inserido antes da variável.
 */
function modelo_var_in($modelo,$var,$valor){
	// Localiza a posição inicial da variável
	$posInicial = strpos($modelo, $var);
	
	$notFound = false;
	if($posInicial === false)
		$notFound = true;
	
	// Se a variável foi encontrada, insere o valor antes dela
	if(!$notFound){
		$posFinal = $posInicial+strlen($var);
		
		// Divide o modelo em duas partes
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		// Reconstrói o modelo inserindo o valor antes da variável
		$modelo = $parteAnterior . $valor . $var . $partePosterior;
	}
	
	return $modelo;
}

/**
 * Extrai o conteúdo entre duas tags em um modelo.
 *
 * Busca e retorna o conteúdo localizado entre uma tag de abertura e uma tag de fechamento.
 * Se as tags não forem encontradas, retorna uma string vazia.
 *
 * @param string $modelo O template de onde o conteúdo será extraído.
 * @param string $tag_in A tag de abertura.
 * @param string $tag_out A tag de fechamento.
 * @return string Retorna o conteúdo entre as tags ou string vazia se não encontrado.
 */
function modelo_tag_val($modelo,$tag_in,$tag_out){
	// Localiza as posições das tags de abertura e fechamento
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	
	$notFound = false;
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	// Se ambas as tags foram encontradas, extrai o conteúdo entre elas
	if(!$notFound){
		$posInicial = $posInicial+strlen($tag_in);
		$len = $posFinal-$posInicial;
		
		$valor = substr($modelo,$posInicial,$len);
	}
	
	return (isset($valor) ? $valor : '');
}

/**
 * Substitui um bloco delimitado por tags incluindo as próprias tags.
 *
 * Remove completamente o bloco entre as tags (incluindo as tags) e o substitui
 * pelo valor fornecido. Útil para substituir seções inteiras de um template.
 *
 * @param string $modelo O template onde a substituição será feita.
 * @param string $tag_in A tag de abertura do bloco.
 * @param string $tag_out A tag de fechamento do bloco.
 * @param string $valor O valor que substituirá todo o bloco (tags + conteúdo).
 * @return string Retorna o modelo com o bloco substituído pelo valor.
 */
function modelo_tag_in($modelo,$tag_in,$tag_out,$valor){
	// Localiza as posições das tags de abertura e fechamento
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	$notFound = false;
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	// Se as tags foram encontradas, substitui todo o bloco
	if(!$notFound){
		$posFinal = $posFinal+strlen($tag_out);
		
		// Divide o modelo em partes antes e depois do bloco
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		// Reconstrói o modelo substituindo o bloco inteiro pelo valor
		$modelo = $parteAnterior . $valor . $partePosterior;
	}
	
	return $modelo;
}

/**
 * Remove um bloco delimitado por tags incluindo as próprias tags.
 *
 * Localiza e remove completamente o bloco entre as tags especificadas,
 * incluindo as tags de abertura e fechamento. Útil para remover seções
 * condicionais ou blocos temporários de templates.
 *
 * @param string $modelo O template de onde o bloco será removido.
 * @param string $tag_in A tag de abertura do bloco a ser removido.
 * @param string $tag_out A tag de fechamento do bloco a ser removido.
 * @return string Retorna o modelo sem o bloco especificado.
 */
function modelo_tag_del($modelo,$tag_in,$tag_out){
	// Localiza as posições das tags de abertura e fechamento
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	
	$notFound = false;
	if($posInicial === false || $posFinal === false){
		$notFound = true;
	}
	
	// Se as tags foram encontradas, remove todo o bloco
	if(!$notFound){
		$posFinal = $posFinal+strlen($tag_out);
		
		// Divide o modelo em partes antes e depois do bloco
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		// Reconstrói o modelo sem o bloco removido
		$modelo = $parteAnterior . $partePosterior;
	}
	
	return $modelo;
}

/**
 * Substitui apenas o conteúdo entre as tags, preservando as tags.
 *
 * Mantém as tags de abertura e fechamento intactas, substituindo apenas
 * o conteúdo entre elas. Ideal para atualizar o conteúdo de blocos marcados
 * sem perder os delimitadores.
 *
 * @param string $modelo O template onde a substituição será feita.
 * @param string $tag_in A tag de abertura (será preservada).
 * @param string $tag_out A tag de fechamento (será preservada).
 * @param string $valor O novo conteúdo que ficará entre as tags.
 * @return string Retorna o modelo com o conteúdo entre as tags substituído.
 */
function modelo_tag_troca_val($modelo,$tag_in,$tag_out,$valor){
	// Localiza as posições das tags de abertura e fechamento
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	
	$notFound = false;
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	// Se as tags foram encontradas, substitui apenas o conteúdo interno
	if(!$notFound){
		$posFinal = $posFinal+strlen($tag_out);
		
		// Preserva a tag de abertura na parte anterior
		$parteAnterior = substr($modelo,0,$posInicial+strlen($tag_in));
		// Preserva a tag de fechamento na parte posterior
		$partePosterior = substr($modelo,($posFinal-strlen($tag_out)),(strlen($modelo)-($posFinal-strlen($tag_out))));
		
		// Reconstrói o modelo mantendo as tags e substituindo apenas o conteúdo interno
		$modelo = $parteAnterior . $valor . $partePosterior;
	}
	
	return $modelo;
}

/**
 * Carrega um arquivo de template e remove blocos de exclusão.
 *
 * Abre um arquivo de template HTML e automaticamente remove todos os blocos
 * marcados com tags especiais de exclusão (<!--!#del#(#!--> e <!--!#del#)#!-->).
 * Esses blocos são úteis para incluir conteúdo apenas durante desenvolvimento.
 *
 * @param string $modelo_local Caminho do arquivo de template a ser carregado.
 * @return string Retorna o conteúdo do template com os blocos de exclusão removidos.
 */
function modelo_abrir($modelo_local){
	// Carrega o conteúdo do arquivo de template
	$modelo = file_get_contents($modelo_local);
	
	// Remove blocos marcados para exclusão (útil para comentários de desenvolvimento)
	$modelo = modelo_tag_del($modelo,"<!--!#del#(#!-->","<!--!#del#)#!-->");
	
	return $modelo;
}

?>