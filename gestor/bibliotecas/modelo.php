<?php

$_GESTOR['biblioteca-modelo']							=	Array(
	'versao' => '1.0.0',
);

function modelo_input_in($modelo,$name_input_in,$name_input_out,$valor){
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_name',$name_input_out);
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_id',$name_input_out);
	$modelo = paginaTrocaVarValor($modelo,$name_input_in.'_val',$valor);
	
	return $modelo;
}

function modelo_var_troca($modelo,$var,$valor){
	$posInicial = strpos($modelo, $var);
	
	$notFound = false;
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

function modelo_var_troca_fim($modelo,$var,$valor){
	$posInicial = strripos($modelo, $var);
	
	$notFound = false;
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
	return preg_replace('/'.preg_quote($var).'/i',$valor,$modelo);
}

function modelo_var_in($modelo,$var,$valor){
	$posInicial = strpos($modelo, $var);
	
	$notFound = false;
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
	
	$notFound = false;
	if($posInicial === false || $posFinal === false)
		$notFound = true;
	
	if(!$notFound){
		$posInicial = $posInicial+strlen($tag_in);
		$len = $posFinal-$posInicial;
		
		$valor = substr($modelo,$posInicial,$len);
	}
	
	return (isset($valor) ? $valor : '');
}

function modelo_tag_in($modelo,$tag_in,$tag_out,$valor){
	$posInicial = strpos($modelo, $tag_in);
	$posFinal = strpos($modelo, $tag_out);
	$notFound = false;
	
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
	
	$notFound = false;
	if($posInicial === false || $posFinal === false){
		$notFound = true;
	}
	
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
	
	$notFound = false;
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