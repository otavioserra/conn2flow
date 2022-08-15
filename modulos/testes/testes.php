<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'testes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.9',
	'bibliotecas' => Array('interface','html','pagina'),
	'tabela' => Array(
		'nome' => 'template',
		'id' => 'id',
		'id_numerico' => 'id_'.'template',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
);

function plataforma_cliente_plugin_data_dias_antes($mes = 0,$ano = 0, $diasPeriodo = 0, $dataInicial = NULL){
	// ===== Definir a data inicial caso a mesma não tenha sido definida.
	
	if(!isset($dataInicial)){
		$dataInicial = date('d/m/Y');
	}
	
	// ===== Pegar o mês e o ano da data inicial.
	
	$diaInicial = (int)date('j');
	$mesInicial = (int)date('n');
	$anoInicial = (int)date('Y');
	
	// ===== Filtrar os dados e colocarem eles como numérico.
	
	$mes = (int)$mes;
	$ano = (int)$ano;
	$diasPeriodo = (int)$diasPeriodo;
	
	// ===== Verifica se a data faz parte desse mês. Se sim, retorna a data procurada. Senão, remove a quantidade de dias do mês atual.
	
	if($diasPeriodo < $diaInicial){
		$diaFim = $diaInicial - $diasPeriodo;
		$mesFim = ($mesInicial < 10 ? '0':'') . $mesInicial;
		$anoFim = ($anoInicial < 10 ? '0':'') . $anoInicial;
		
		return $diaFim . '/' . $mesFim . '/' . $anoFim;
	} else {
		$diasPeriodo = $diasPeriodo - $diaInicial;
	}
	
	// ===== Variáveis de controle do loop.
	
	$limiteLoop = 1000;
	$countLoop = 0;
	
	// ===== Faz o loop e buscar a data.
	
	while(true){
		if(!isset($anoFim)){
			$anoFim = $ano;
		}
		
		if(!isset($mesFim)){
			$mesFim = $mes - 1;
		} else {
			$mesFim--;
		}
		
		if($mesFim < 1){
			$mesFim = 12;
			$anoFim--;
		}
		
		$totalDiasMes = cal_days_in_month(CAL_GREGORIAN, $mesFim, $anoFim);
		
		$diaFim = $totalDiasMes - ($diasPeriodo - (isset($diasFimContados) ? $diasFimContados : 0));
		
		if($diaFim > 0){
			$mesFim = ($mesFim < 10 ? '0':'') . $mesFim;
			$diaFim = ($diaFim < 10 ? '0':'') . $diaFim;
			
			return $diaFim . '/' . $mesFim . '/' . $anoFim;
		} else {
			if(!isset($diasFimContados)){
				$diasFimContados = $totalDiasMes;
			} else {
				$diasFimContados += $totalDiasMes;
			}
		}
		
		$countLoop++;
		
		if($countLoop > $limiteLoop){
			return '';
		}
	}
}

function plataforma_cliente_plugin_data_dias_depois($mes = 0,$ano = 0, $diasPeriodo = 0, $dataInicial = NULL){
	// ===== Definir a data inicial caso a mesma não tenha sido definida.
	
	if(!isset($dataInicial)){
		$dataInicial = date('d/m/Y');
	}
	
	// ===== Pegar o mês e o ano da data inicial.
	
	$numDiasMes = (int)date('t');
	
	$diaInicial = (int)date('j');
	$mesInicial = (int)date('n');
	$anoInicial = (int)date('Y');
	
	// ===== Filtrar os dados e colocarem eles como numérico.
	
	$mes = (int)$mes;
	$ano = (int)$ano;
	$diasPeriodo = (int)$diasPeriodo;
	
	// ===== Verifica se a data faz parte desse mês. Se sim, retorna a data procurada. Senão, remove a quantidade de dias do mês atual.
	
	if($diasPeriodo <= $numDiasMes - $diaInicial){
		$diaFim = $diaInicial + $diasPeriodo;
		$mesFim = ($mesInicial < 10 ? '0':'') . $mesInicial;
		$anoFim = ($anoInicial < 10 ? '0':'') . $anoInicial;
		
		return $diaFim . '/' . $mesFim . '/' . $anoFim;
	} else {
		$diasPeriodo = $diasPeriodo - ($numDiasMes - $diaInicial);
	}
	
	// ===== Variáveis de controle do loop.
	
	$limiteLoop = 1000;
	$countLoop = 0;
	
	// ===== Faz o loop e buscar a data.
	
	while(true){
		if(!isset($anoFim)){
			$anoFim = $ano;
		}
		
		if(!isset($mesFim)){
			$mesFim = $mes + 1;
		} else {
			$mesFim++;
		}
		
		if($mesFim > 12){
			$mesFim = 1;
			$anoFim++;
		}
		
		$totalDiasMes = cal_days_in_month(CAL_GREGORIAN, $mesFim, $anoFim);
		
		$diasNoMes = ($totalDiasMes - ($diasPeriodo - (isset($diasFimContados) ? $diasFimContados : 0)));
		
		if($diasNoMes <= $totalDiasMes && $diasNoMes > 0){
			$diaFim = 1 + ($diasPeriodo - (isset($diasFimContados) ? $diasFimContados : 0));
			$mesFim = ($mesFim < 10 ? '0':'') . $mesFim;
			$diaFim = ($diaFim < 10 ? '0':'') . $diaFim;
			
			return $diaFim . '/' . $mesFim . '/' . $anoFim;
		} else {
			if(!isset($diasFimContados)){
				$diasFimContados = $totalDiasMes;
			} else {
				$diasFimContados += $totalDiasMes;
			}
		}
		
		$countLoop++;
		
		if($countLoop > $limiteLoop){
			return '';
		}
	}
}

function testes_testes(){
	global $_GESTOR;
	
	$pagina = '';
	
	// ===== Área de testes.
	
	//echo plataforma_cliente_plugin_data_dias_antes(8,2022,46, $dataInicial = date('d/m/Y')).'<br>';
	echo plataforma_cliente_plugin_data_dias_depois(8,2022,77, $dataInicial = date('d/m/Y'));
	
	exit;
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Alteração da página.
	
	$_GESTOR['pagina'] .= $pagina;
	
}

function testes_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function testes_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function testes_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': testes_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		testes_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'testes': testes_testes(); break;
		}
		
		interface_finalizar();
	}
}

testes_start();

?>