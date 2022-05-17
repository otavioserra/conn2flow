<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'agendamentos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.2',
);

// ===== Funções Auxiliares

function agendamentos_calendario($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	gestor_incluir_biblioteca('formato');
	
	$ano_inicio = date('Y');
	$hoje = date('Y-m-d');
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','conjunto' => true));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array());
	$fase_escolha_livre = (existe($config['fase-escolha-livre']) ? (int)$config['fase-escolha-livre'] : 7);
	$calendario_limite_mes_a_frente = (existe($config['calendario-limite-mes-a-frente']) ? (int)$config['calendario-limite-mes-a-frente'] : false);
	$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
	$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
	$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
	$calendario_ferias_ate = (existe($config['calendario-ferias-ate']) ? trim($config['calendario-ferias-ate']) : '20 January');
	
	$ano_fim = (int)$ano_inicio + $anos;
	
	$agendamentos_datas = banco_select_name
	(
		banco_campos_virgulas(Array(
			'data',
			'total',
		))
		,
		"agendamentos_datas",
		"WHERE data >= ".$hoje
	);
	
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
				$count_dias = 0;
				if($dias_semana)
				foreach($dias_semana as $dia_semana){
					if($dia_semana == strtolower(date('D',$dia))){
						$flag2 = true;
						break;
					}
					$count_dias++;
				}
				
				if($flag2){
					$data = date('Y-m-d', $dia);
					$flag3 = false;
					
					if($dia < strtotime($hoje.' + '.$fase_residual.' day')){
						if($agendamentos_datas){
							foreach($agendamentos_datas as $agendamentos_data){
								if($data == $agendamentos_data['data']){
									if(count($dias_semana_maximo_vagas_arr) > 1){
										$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[$count_dias];
									} else {
										$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[0];
									}
									
									if((int)$dias_semana_maximo_vagas <= (int)$agendamentos_data['total']){
										$flag3 = true;
									}
									
									break;
								}
							}
						}
					}
					
					if(!$flag3){
						$datas[$data] = 1;
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
	
	$JScalendario['datas_disponiveis'] = $datas;
	$JScalendario['ano_inicio'] = $ano_inicio;
	$JScalendario['ano_fim'] = $ano_fim;
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['calendario'] = $JScalendario;
}

// ===== Funções Principais

function agendamentos_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
	));
	
	// ===== Codificação do plugin.
	
	agendamentos_calendario();
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step','');
	layout_trocar_variavel_valor('layout#step-mobile','');
	
	// ===== Finalizar o layout com as variáveis padrões.
	
	layout_loja();
	
	// ===== Finalizar interface.
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	interface_finalizar();
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('plugin');
}

// ==== Ajax

function agendamentos_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function agendamentos_start(){
	global $_GESTOR;
	
	// ===== Verificar se o usuário está logado.
	
	gestor_permissao();
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': agendamentos_ajax_opcao(); break;
			default: agendamentos_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': agendamentos_opcao(); break;
			default: agendamentos_padrao();
		}
	}
}

agendamentos_start();

?>