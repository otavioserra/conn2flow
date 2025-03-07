<?php

global $_GESTOR;

$_GESTOR['biblioteca-formato']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares

function formato_data_hora_array($data_hora_padrao_datetime_ou_padrao_date){
	$data_hora = explode(" ",$data_hora_padrao_datetime_ou_padrao_date);
	
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
		$data_aux = explode("-",$data_hora[0]);
		
		$data_hora_array = Array(
			'dia' => $data_aux[2],
			'mes' => $data_aux[1],
			'ano' => $data_aux[0],
		);
	}
	
	return $data_hora_array;
}

function formato_data_hora_padrao_datetime($dataHora, $semHora = false){
	$dataHoraArray = explode(" ",$dataHora);
	$dataArray = explode("/",$dataHoraArray[0]);
	$datetime = $dataArray[2]."-".$dataArray[1]."-".$dataArray[0].($semHora ? '' : " ".$dataHoraArray[1].":00");
	
	return $datetime;
}

function formato_data_hora_from_datetime_to_text($data_hora, $format = false){
	$formato_padrao = 'D/ME/A HhMI';
	
	if($data_hora){
		$data_hora = explode(" ",$data_hora);
		$data_aux = explode("-",$data_hora[0]);
		
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
			$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
			$hora = $data_hora[1];
			
			return $data . " " . $hora;
		}
	} else {
		return "";
	}
}

function formato_data_from_datetime_to_text($data_hora){
	$data_hora = explode(" ",$data_hora);
	$data_aux = explode("-",$data_hora[0]);
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	
	return $data;
}

function formato_float_para_texto($float,$sem_descimal = false){
	// Formato 00.000,00
	
	return number_format((float)$float, 2, ',', '.');
}

function formato_texto_para_float($texto){
	// Formato 00.000,00
	
	$num_1_2 = explode(",",$texto);
	
	if($num_1_2){
		$num_aux = explode(".",$num_1_2[0]);
		$num_1 = '';
		
		if($num_aux){
			for($i=0;$i<count($num_aux);$i++){
				$num_1 .= $num_aux[$i];
			}
		} else
			$num_1 = $num_1_2[0];
		
		$num_2 = $num_1_2[1];
		
		return ($num_1 . "." . $num_2);
	} else
		return $texto;
}

function formato_int_para_texto($int){
	// Formato 00.000.000
	
	return number_format((float)$int, 0, '', '.');
}

function formato_texto_para_int($texto){
	// Formato 00.000.000
	
	return str_replace(".", "", $texto);
}

function formato_zero_a_esquerda($num,$dig){
	$len = strlen((string)$num);
	
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

function formato_colocar_char_meio_numero($num,$char = '-'){
	$len = strlen((string)$num);
	
	$numArr = str_split($num, floor($len/2));
	$numFinal = '';
	
	foreach($numArr as $n){
		$numFinal .= $n . (!isset($charColocado) ? $char : '');
		$charColocado = true;
	}
	
	return $numFinal;
}

// ===== Funções principais

function formato_dado_para($tipo,$valor){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - Valor do dado a ser formatado.
	// tipo - String - Obrigatório - Tipo de formatação.
	
	// ===== 
	
	if(isset($valor) && isset($tipo)){
		$valor = formato_dado(Array(
			'valor' => $valor,
			'tipo' => $tipo,
		));
		
		return $valor;
	}
	
	return '';
}

function formato_dado($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - Valor do dado a ser formatado.
	// tipo - String - Obrigatório - Tipo de formatação.
	
	// ===== 
	
	if(isset($valor) && isset($tipo)){
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