<?php
/**
 * Biblioteca de formatação de dados.
 *
 * Fornece funções para formatação e conversão de dados entre diferentes
 * formatos, incluindo datas, horas, números decimais e inteiros.
 * Suporta conversão de formato brasileiro para formato de banco de dados e vice-versa.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.1.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-formato']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares

/**
 * Converte uma data/hora no formato DATETIME ou DATE para array.
 *
 * Recebe uma string no formato 'YYYY-MM-DD HH:MM:SS' ou 'YYYY-MM-DD'
 * e retorna um array associativo com os componentes separados.
 *
 * @param string $data_hora_padrao_datetime_ou_padrao_date Data/hora no formato padrão do banco.
 * 
 * @return array Array com as chaves: dia, mes, ano, hora (opcional), min (opcional), seg (opcional).
 */
function formato_data_hora_array($data_hora_padrao_datetime_ou_padrao_date){
	// Separa data e hora
	$data_hora = explode(" ",$data_hora_padrao_datetime_ou_padrao_date);
	
	// Processa DATETIME (com hora)
	if(count($data_hora) > 1){
		$data_aux = explode("-",$data_hora[0]);
		$hora_aux = explode(":",$data_hora[1]);
		
		$data_hora_array = Array(
			'dia' => $data_aux[2],
			'mes' => $data_aux[1],
			'ano' => $data_aux[0],
			'hora' => $hora_aux[0],
			'min' => $hora_aux[1],
			'seg' => $hora_aux[2],
		);
	} else {
		// Processa DATE (apenas data)
		$data_aux = explode("-",$data_hora[0]);
		
		$data_hora_array = Array(
			'dia' => $data_aux[2],
			'mes' => $data_aux[1],
			'ano' => $data_aux[0],
		);
	}
	
	return $data_hora_array;
}

/**
 * Converte data/hora do formato brasileiro para formato DATETIME do MySQL.
 *
 * Recebe uma string no formato 'DD/MM/YYYY HH:MM' e converte para
 * 'YYYY-MM-DD HH:MM:00' adequado para armazenamento no banco.
 *
 * @param string $dataHora Data/hora no formato brasileiro (DD/MM/YYYY HH:MM).
 * @param bool $semHora Se true, retorna apenas a data no formato DATE (YYYY-MM-DD).
 * 
 * @return string Data/hora no formato DATETIME ou DATE do MySQL.
 */
function formato_data_hora_padrao_datetime($dataHora, $semHora = false){
	// Separa data e hora
	$dataHoraArray = explode(" ",$dataHora);
	$dataArray = explode("/",$dataHoraArray[0]);
	
	// Monta string no formato MySQL
	$datetime = $dataArray[2]."-".$dataArray[1]."-".$dataArray[0].($semHora ? '' : " ".$dataHoraArray[1].":00");
	
	return $datetime;
}

/**
 * Converte data/hora do formato DATETIME do MySQL para texto formatado.
 *
 * Recebe uma string no formato 'YYYY-MM-DD HH:MM:SS' e converte para
 * formato personalizado ou padrão 'DD/MM/AAAA HHhMI'.
 *
 * @param string $data_hora Data/hora no formato DATETIME do MySQL.
 * @param string|false $format Formato personalizado usando: D (dia), ME (mês), A (ano), H (hora), MI (minuto), S (segundo).
 * 
 * @return string Data/hora formatada ou string vazia se $data_hora for vazia.
 */
function formato_data_hora_from_datetime_to_text($data_hora, $format = false){
	$formato_padrao = 'D/ME/A HhMI';
	
	if($data_hora){
		// Separa data e hora
		$data_hora = explode(" ",$data_hora);
		$data_aux = explode("-",$data_hora[0]);
		
		// Aplica formato personalizado
		if($format){
			$hora_aux = explode(":",$data_hora[1]);
			$format = preg_replace('/D/', $data_aux[2], $format);
			$format = preg_replace('/ME/', $data_aux[1], $format);
			$format = preg_replace('/A/', $data_aux[0], $format);
			$format = preg_replace('/H/', $hora_aux[0], $format);
			$format = preg_replace('/MI/', $hora_aux[1], $format);
			$format = preg_replace('/S/', $hora_aux[2], $format);
			
			return $format;
		} else if($formato_padrao){
			// Aplica formato padrão
			$format = $formato_padrao;
			$hora_aux = explode(":",$data_hora[1]);
			$format = preg_replace('/D/', $data_aux[2], $format);
			$format = preg_replace('/ME/', $data_aux[1], $format);
			$format = preg_replace('/A/', $data_aux[0], $format);
			$format = preg_replace('/H/', $hora_aux[0], $format);
			$format = preg_replace('/MI/', $hora_aux[1], $format);
			$format = preg_replace('/S/', $hora_aux[2], $format);
			
			return $format;
		} else {
			// Formato simples DD/MM/AAAA HH:MM:SS
			$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
			$hora = $data_hora[1];
			
			return $data . " " . $hora;
		}
	} else {
		return "";
	}
}

/**
 * Converte data do formato DATETIME do MySQL para formato brasileiro.
 *
 * Extrai apenas a parte da data de uma string DATETIME e converte
 * para o formato DD/MM/AAAA.
 *
 * @param string $data_hora Data/hora no formato DATETIME do MySQL.
 * 
 * @return string Data no formato DD/MM/AAAA.
 */
function formato_data_from_datetime_to_text($data_hora){
	// Separa data e hora
	$data_hora = explode(" ",$data_hora);
	$data_aux = explode("-",$data_hora[0]);
	
	// Monta data no formato brasileiro
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	
	return $data;
}

/**
 * Formata um número float para o formato brasileiro.
 *
 * Converte um número decimal para o formato brasileiro com ponto
 * como separador de milhares e vírgula como separador decimal.
 * Formato: 1.234,56
 *
 * @param float $float O número a ser formatado.
 * @param bool $sem_descimal Parâmetro não utilizado atualmente.
 * 
 * @return string Número formatado no padrão brasileiro.
 */
function formato_float_para_texto($float,$sem_descimal = false){
	// Formato 00.000,00
	return number_format((float)$float, 2, ',', '.');
}

/**
 * Converte número do formato brasileiro para float.
 *
 * Converte uma string no formato brasileiro (1.234,56) para
 * formato float adequado para cálculos (1234.56).
 *
 * @param string $texto Número no formato brasileiro.
 * 
 * @return string Número no formato float (ponto como decimal).
 */
function formato_texto_para_float($texto){
	// Formato 00.000,00
	
	// Separa parte inteira da decimal
	$num_1_2 = explode(",",$texto);
	
	if($num_1_2){
		// Remove pontos de separação de milhares
		$num_aux = explode(".",$num_1_2[0]);
		$num_1 = '';
		
		if($num_aux){
			for($i=0;$i<count($num_aux);$i++){
				$num_1 .= $num_aux[$i];
			}
		} else
			$num_1 = $num_1_2[0];
		
		$num_2 = $num_1_2[1];
		
		// Retorna número com ponto decimal
		return ($num_1 . "." . $num_2);
	} else
		return $texto;
}

/**
 * Formata um número inteiro para o formato brasileiro.
 *
 * Adiciona separador de milhares (ponto) aos números inteiros.
 * Formato: 1.234.567
 *
 * @param int $int O número a ser formatado.
 * 
 * @return string Número formatado com separadores de milhares.
 */
function formato_int_para_texto($int){
	// Formato 00.000.000
	return number_format((float)$int, 0, '', '.');
}

/**
 * Remove formatação brasileira de um número inteiro.
 *
 * Remove os pontos separadores de milhares.
 *
 * @param string $texto Número formatado no padrão brasileiro.
 * 
 * @return string Número sem formatação.
 */
function formato_texto_para_int($texto){
	// Formato 00.000.000
	return str_replace(".", "", $texto);
}

/**
 * Adiciona zeros à esquerda de um número.
 *
 * Preenche um número com zeros à esquerda até atingir
 * o número especificado de dígitos.
 *
 * @param int|string $num O número a ser formatado.
 * @param int $dig Quantidade total de dígitos desejada.
 * 
 * @return string Número com zeros à esquerda.
 */
function formato_zero_a_esquerda($num,$dig){
	$len = strlen((string)$num);
	
	// Adiciona zeros se necessário
	if($len < $dig){
		$num2 = $num;
		
		for($i=0;$i<$dig - $len;$i++){
			$num2 = '0'.$num2;
		}
		
		return $num2;
	} else {
		return $num;
	}
}

/**
 * Insere um caractere no meio de um número.
 *
 * Divide um número ao meio e insere um caractere (padrão: hífen)
 * entre as duas metades.
 *
 * @param int|string $num O número a ser processado.
 * @param string $char O caractere a ser inserido (padrão: '-').
 * 
 * @return string Número com caractere inserido no meio.
 */
function formato_colocar_char_meio_numero($num,$char = '-'){
	$len = strlen((string)$num);
	
	// Divide número ao meio
	$numArr = str_split($num, floor($len/2));
	$numFinal = '';
	
	// Monta string com caractere no meio
	foreach($numArr as $n){
		$numFinal .= $n . (!isset($charColocado) ? $char : '');
		$charColocado = true;
	}
	
	return $numFinal;
}

// ===== Funções principais

/**
 * Aplica formatação a um dado conforme o tipo especificado.
 *
 * Função wrapper simplificada que chama formato_dado() internamente.
 *
 * @param string $tipo Tipo de formatação a ser aplicada.
 * @param mixed $valor Valor a ser formatado.
 * 
 * @return string Valor formatado ou string vazia se parâmetros inválidos.
 */
function formato_dado_para($tipo,$valor){
	global $_GESTOR;
	
	// Valida parâmetros e aplica formatação
	if(isset($valor) && isset($tipo)){
		$valor = formato_dado(Array(
			'valor' => $valor,
			'tipo' => $tipo,
		));
		
		return $valor;
	}
	
	return '';
}

/**
 * Formata um valor de acordo com o tipo especificado.
 *
 * Função principal de formatação que suporta vários tipos:
 * - float-para-texto: Converte float para formato brasileiro
 * - texto-para-float: Converte formato brasileiro para float
 * - int-para-texto: Formata inteiro com separadores de milhares
 * - texto-para-int: Remove formatação de inteiro
 * - data: Converte DATETIME para DD/MM/AAAA
 * - dataHora: Converte DATETIME para DD/MM/AAAA HHhMM
 * - datetime: Converte formato brasileiro para DATETIME MySQL
 * - date: Converte formato brasileiro para DATE MySQL
 *
 * @param array|false $params Parâmetros da função.
 * @param mixed $params['valor'] Valor a ser formatado (obrigatório).
 * @param string $params['tipo'] Tipo de formatação (obrigatório).
 * 
 * @return string Valor formatado ou string vazia se parâmetros inválidos.
 */
function formato_dado($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($valor) && isset($tipo)){
		// Aplica formatação conforme tipo especificado
		switch($tipo){
			case 'float-para-texto': $valor = formato_float_para_texto($valor); break;
			case 'texto-para-float': $valor = formato_texto_para_float($valor); break;
			case 'int-para-texto': $valor = formato_int_para_texto($valor); break;
			case 'texto-para-int': $valor = formato_texto_para_int($valor); break;
			case 'data': $valor = formato_data_from_datetime_to_text($valor); break;
			case 'dataHora': $valor = formato_data_hora_from_datetime_to_text($valor); break;
			case 'datetime': $valor = formato_data_hora_padrao_datetime($valor); break;
			case 'date': $valor = formato_data_hora_padrao_datetime($valor,true); break;
		}
		
		return $valor;
	}
	
	return '';
}

?>