<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'testes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.3',
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

function plataforma_cliente_plugin_data_permitida($data){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formato');
	
	$ano_inicio = date('Y');
	$hoje = date('Y-m-d');
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array());
	$fase_escolha_livre = (existe($config['fase-escolha-livre']) ? (int)$config['fase-escolha-livre'] : 7);
	$calendario_limite_mes_a_frente = (existe($config['calendario-limite-mes-a-frente']) ? (int)$config['calendario-limite-mes-a-frente'] : false);
	$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
	$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
	$calendario_ferias_ate = (existe($config['calendario-ferias-ate']) ? trim($config['calendario-ferias-ate']) : '20 January');
	
	$ano_fim = (int)$ano_inicio + $anos;
	
	if($dias_semana)
	foreach($dias_semana as $dia_semana){
		if(!$flag){
			$primeiro_dia_semana = $dia_semana;
			$flag = true;
		}
	}
	
	$ultimo_dia_semana = $dia_semana;
	
	for($i=-1;$i<$anos+1;$i++){
		$periodo_ferias[] = Array(
			'inicio' => strtotime($calendario_ferias_de." ".($ano_inicio+$i)),
			'fim' => strtotime($calendario_ferias_ate." ".($ano_inicio+$i+1)),
		);
	}
	
	$primeiro_dia = strtotime("first ".$primeiro_dia_semana." of this month");
	$ultimo_dia = strtotime(date("Y-m-d", mktime()) . " + ".$anos." year");
	
	if($calendario_limite_mes_a_frente){
		$limitar_calendario = strtotime(date("Y-m",strtotime($hoje . " + ".$calendario_limite_mes_a_frente." month")).'-01');
	}
	
	$dia = $primeiro_dia - 14400;
	do {
		if($dia > mktime() + 72000){
			$flag = false;
			
			if($periodo_ferias){
				foreach($periodo_ferias as $periodo){
					if(
						$dia > $periodo['inicio'] &&
						$dia < $periodo['fim']
					){
						$flag = true;
						break;
					}
				}
			}
			
			if($datas_indisponiveis){
				foreach($datas_indisponiveis as $di){
					if(
						$dia > strtotime(formato_dado_para('date',$di).' 00:00:00') &&
						$dia < strtotime(formato_dado_para('date',$di).' 23:59:59')
					){
						$flag = true;
						break;
					}
				}
			}
			
			if($fase_sorteio){
				if(
					$dia > strtotime($hoje.' + '.($fase_sorteio[1]+1).' day') &&
					$dia < strtotime($hoje.' + '.($fase_sorteio[0]+1).' day')
				){
					$flag = true;
				}
			}
			
			if(!$flag){
				$flag2 = false;
				if($dias_semana)
				foreach($dias_semana as $dia_semana){
					if($dia_semana == strtolower(date('D',$dia))){
						$flag2 = true;
						break;
					}
				}
				
				if($flag2){
					if($data == date('Y-m-d', $dia)){
						return true;
					}
				}
			}
		}
		
		$dia += 86400;
		
		if($limitar_calendario){
			if($dia > $limitar_calendario){
				break;
			}
		}

	} while ($dia < $ultimo_dia);
	
	return false;
}

function testes_testes(){
	global $_GESTOR;
	
	$pagina = '';
	
	/* $_GESTOR['host-id'] = '13';
	
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('configuracao');
	
	if(plataforma_cliente_plugin_data_permitida('2022-06-16')){
		$pagina = 'sim';
	} else {
		$pagina = 'nao';
	} */
	
	// ===== Área de testes.
	
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