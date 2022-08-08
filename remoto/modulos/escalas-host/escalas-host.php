<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'escalas-host';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.21',
);

// ===== Funções Auxiliares

function escalas_calendario($params = false){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formato');
	
	$ano = date('Y');
	$mes = (int)date('n');
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Verificar se o mês atual é o mesmo do mês procurado. Se for, então é um calendário com possibilidade de vagas residuais.
	
	$mesAtual = (int)date('n');
	
	if($mesAtual == $mes){
		$mesVagasResiduais = true;
	} else {
		$mesVagasResiduais = false;
	}
	
	// ===== Definir a data do primeiro dia e do último dia do mês procurado.
	
	if($mes < 10){
		$mes = '0'.$mes;
	}
	
	$prmeiroDiaMes = date($ano.'-'.$mes.'-01');
	$ultimoDiaMes = date($ano.'-'.$mes.'-'.date('t',strtotime($prmeiroDiaMes)));
	
	// ===== Pegar as configurações das escalas.
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-escalas','conjunto' => true));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array()); else $datas_indisponiveis = Array();
	$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
	$calendario_ferias_ate = (existe($config['calendario-ferias-ate']) ? trim($config['calendario-ferias-ate']) : '20 January');
	
	// ===== Verificar as datas na tabela controle de datas e total de vagas.
	
	$escalas_controle = banco_select_name
	(
		banco_campos_virgulas(Array(
			'data',
			'total',
		))
		,
		"escalas_controle",
		"WHERE data >= ".$prmeiroDiaMes
		" AND data <= ".$ultimoDiaMes
	);
	
	// ===== Pegar o período de férias para remover do calendário do mês.
	
	for($i=-1;$i<$anos+1;$i++){
		$periodo_ferias[] = Array(
			'inicio' => strtotime($calendario_ferias_de." ".($ano+$i)),
			'fim' => strtotime($calendario_ferias_ate." ".($ano+$i+1)),
		);
	}
	
	// ===== Definição do primeiro e último dia deste calendário.
	
	if(strtotime($prmeiroDiaMes) >= strtotime(date("Y-m-d", time()) . " + 1 day")){
		$primeiro_dia = strtotime($prmeiroDiaMes);
	} else {
		$primeiro_dia = strtotime(date("Y-m-d", time()) . " + 1 day");
	}
	
	$ultimo_dia = strtotime($ultimoDiaMes);
	
	// ===== Data início e data final do calendário.
	
	$dataInicio = date('d/m/Y',$primeiro_dia);
	$dataFim = date('d/m/Y',$ultimo_dia);
	
	// ===== Definição das datas passadas ao widget no JS.
	
	$datasDesabilitadas = '';
	$datasDestacadas = '';
	
	// ===== Varrer todos os dias do mês.
	
	$dia = $primeiro_dia;
	do {
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
				$dataFormatada = date('d/m/Y', $dia);
				$flag3 = false;
				
				if($mesVagasResiduais){
					if($escalas_controle){
						foreach($escalas_controle as $escalas_data){
							if($data == $escalas_data['data']){
								if(count($dias_semana_maximo_vagas_arr) > 1){
									$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[$count_dias];
								} else {
									$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[0];
								}
								
								if((int)$dias_semana_maximo_vagas < (int)$escalas_data['total']){
									$flag3 = true;
								}
								
								break;
							}
						}
					}
				}
				
				if(!$flag3){
					$datasDestacadas .= (existe($datasDestacadas) ? ',','') . $dataFormatada;
				} else {
					$datasDesabilitadas .= (existe($datasDesabilitadas) ? ',','') . $dataFormatada;
				}
			}
		}
		
		$dia += 86400;
	} while ($dia < $ultimo_dia);
	
	$JScalendario['dataInicio'] = $dataInicio;
	$JScalendario['dataFim'] = $dataFim;
	$JScalendario['datasDesabilitadas'] = $datasDesabilitadas;
	$JScalendario['datasDestacadas'] = $datasDestacadas;
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['escalas'] = $JScalendario;
}

// ===== Funções Principais

function escalas_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
	));
	
	// ===== Codificação do plugin.
	
	// ===== Tratar o estado da escala.
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-escalas','conjunto' => true));
	
	$escala_ativacao = (existe($config['escala-ativacao']) ? true : false);
	$msgEscalaSuspenso = (existe($config['msg-escala-suspenso']) ? $config['msg-escala-suspenso'] : '');
	
	// ===== Verificar se o sistema de escala está ativo ou não e tratar cada caso.
	
	if($escala_ativacao){
		// ===== Dados de configuração.
		
		// ===== Remover a célula inativo e alteracoes.
		
		$cel_nome = 'inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Montagem do calendário.
		
		escalas_calendario();
		
		
	} else {
		// ===== Remover a célula ativo.
		
		$cel_nome = 'ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		pagina_trocar_variavel_valor('msg-escala-suspenso',$msgEscalaSuspenso);
	}
	
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
	
	// ===== Incluir bibliotecas.
	
	gestor_pagina_javascript_incluir('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.standalone.min.css" integrity="sha512-p4vIrJ1mDmOVghNMM4YsWxm0ELMJ/T0IkdEvrkNHIcgFsSzDi/fV7YxzTzb3mnMvFPawuIyIrHcpxClauEfpQg==" crossorigin="anonymous" referrerpolicy="no-referrer" />');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha512-T/tUfKSV1bihCnd+MxKD0Hm1uBBroVYBOYSk1knyvQ9VyZJpc/ALb4P0r6ubwVPSGB2GvjeoMAJJImBG12TiaQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.pt-BR.min.js"></script>');
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('plugin');
}

// ==== Ajax

function escalas_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function escalas_start(){
	global $_GESTOR;
	
	// ===== Verificar se o usuário está logado.
	
	gestor_permissao();
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': escalas_ajax_opcao(); break;
			default: escalas_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': escalas_opcao(); break;
			default: escalas_padrao();
		}
	}
}

escalas_start();

?>