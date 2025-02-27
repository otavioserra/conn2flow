<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/

$_VERSAO_MODULO_INCLUDE				=	'1.0.2';

function modelo_input_in($modelo,$name_input_in,$name_input_out,$valor){
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_name',$name_input_out);
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_id',$name_input_out);
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_val',$valor);
	
	return $modelo;
}

function modelo_var_troca($modelo,$var,$valor){
	$posInicial = strpos($modelo, $var);
	
	if($posInicial === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posInicial+strlen($var);
		
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		$modelo = $parteAnterior . $valor . $partePosterior;
	}
	
	return $modelo;
}

function modelo_var_troca_tudo($modelo,$var,$valor){
	return preg_replace('/'.$var.'/i',$valor,$modelo);
}

function modelo_var_in($modelo,$var,$valor){
	$posInicial = strpos($modelo, $var);
	
	if($posInicial === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posInicial+strlen($var);
		
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		$modelo = $parteAnterior . $valor . $partePosterior;
		
		$modelo = $parteAnterior . $valor . $var . $partePosterior;
	}
	
	return $modelo;
}

function modelo_tag_val($modelo,$tag_in,$tag_out){
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posInicial = $posInicial+strlen($tag_in);
		$len = $posFinal-$posInicial;
		
		$valor = substr($modelo,$posInicial,$len);
	}
	
	return $valor;
}

function modelo_tag_in($modelo,$tag_in,$tag_out,$valor){
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posFinal+strlen($tag_out);
		
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		$modelo = $parteAnterior . $valor . $partePosterior;
	}
	
	return $modelo;
}

function modelo_tag_del($modelo,$tag_in,$tag_out){
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posFinal+strlen($tag_out);
		
		$parteAnterior = substr($modelo,0,$posInicial);
		$partePosterior = substr($modelo,$posFinal,(strlen($modelo)-$posFinal));
		
		$modelo = $parteAnterior . $partePosterior;
	}
	
	return $modelo;
}

function modelo_tag_troca_val($modelo,$tag_in,$tag_out,$valor){
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posFinal = $posFinal+strlen($tag_out);
		
		$parteAnterior = substr($modelo,0,$posInicial+strlen($tag_in));
		$partePosterior = substr($modelo,($posFinal-strlen($tag_out)),(strlen($modelo)-($posFinal-strlen($tag_out))));
		
		$modelo = $parteAnterior . $valor . $partePosterior;
	}
	
	return $modelo;
}

function modelo_abrir($modelo_local){
	$modelo = file_get_contents($modelo_local);
	
	$modelo = modelo_tag_del($modelo,"<!--!#del#(#!-->","<!--!#del#)#!-->");
	
	return $modelo;
}

?>