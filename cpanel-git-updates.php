<?php

require 'cpanel-config.php';
include_once 'cpanel-functions.php';

global $_CPANEL;

function aplicarCor($texto,$corNome = 'noColor'){
	// ===== referência das cores: https://stackoverflow.com/questions/5947742/how-to-change-the-output-color-of-echo-in-linux/28938235#28938235.
	
	/*
	Black        0;30     Dark Gray     1;30
	Red          0;31     Light Red     1;31
	Green        0;32     Light Green   1;32
	Brown/Orange 0;33     Yellow        1;33
	Blue         0;34     Light Blue    1;34
	Purple       0;35     Light Purple  1;35
	Cyan         0;36     Light Cyan    1;36
	Light Gray   0;37     White         1;37
	*/
	
	// ===== Array com todas as cores disponíveis.
	
	$noColor = '\\033[0m';
	$colors = Array(
		'black' => '0;30',
		'red' => '0;31',
		'green' => '0;32',
		'orange' => '0;33',
		'blue' => '0;34',
		'purple' => '0;35',
		'cyan' => '0;36',
		'gray' => '0;37',
		'gray2' => '1;30',
		'red2' => '1;31',
		'green2' => '1;32',
		'yellow' => '1;33',
		'blue2' => '1;34',
		'purple2' => '1;35',
		'cyan2' => '1;36',
		'white' => '1;37',
	);
	
	// ===== Aplicar a cor se encontrado, senão aplica sem cor.
	
	if(isset($colors[$corNome])){
		$texto = '\\033['.$colors[$corNome].'m' . $texto . $noColor;
	} else {
		$texto = $noColor . $texto;
	}
	
	return $texto;
}

$erro = aplicarCor('ERRO: ','red');
$info = aplicarCor('é necessário mudar o seguinte ID: #582jdo459wk','yellow');
$titulo = aplicarCor('Conclusão da atualização','green');

echo "\n".$titulo."\n";
echo $erro . $info."\n";

?>