<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'agendamentos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.16',
	'plugin' => 'agendamentos',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_agendamentos',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_agendamentos',
	),
);

// ===== Funções Auxiliares

function agendamentos_calendario(){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formato');
	
	$ano_inicio = date('Y');
	$hoje = date('Y-m-d');
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','conjunto' => true));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array()); else $datas_indisponiveis = Array();
	$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
	$calendario_ferias_ate = (existe($config['calendario-ferias-ate']) ? trim($config['calendario-ferias-ate']) : '20 January');
	
	$ano_fim = (int)$ano_inicio + $anos;
	
	for($i=-1;$i<$anos+1;$i++){
		$periodo_ferias[] = Array(
			'inicio' => strtotime($calendario_ferias_de." ".($ano_inicio+$i)),
			'fim' => strtotime($calendario_ferias_ate." ".($ano_inicio+$i+1)),
		);
	}
	
	$primeiro_dia = strtotime(date("Y-m-d", time()) . " - 60 day");
	$ultimo_dia = strtotime(date("Y-m-d", time()) . " + ".$anos." year");
	
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
				$datas[$data] = 1;
			}
		}
		
		$dia += 86400;
	} while ($dia < $ultimo_dia);
	
	$JScalendario['datas_disponiveis'] = $datas;
	$JScalendario['ano_inicio'] = $ano_inicio;
	$JScalendario['ano_fim'] = $ano_fim;
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['calendario'] = $JScalendario;
}

// ===== Funções Principais

function agendamentos_administrar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Adicionar o calendário.
	
	agendamentos_calendario();
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['simples']['finalizar'] = Array(
		'botoes' => Array(
			'administrar' => Array(
				'url' => '',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-admin')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-admin')),
				'icon' => 'calendar alternate',
				'cor' => 'blue',
			),
			'cupons' => Array(
				'url' => 'cupons-de-prioridade/',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-coupon')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-coupon')),
				'icon' => 'certificate',
				'cor' => 'green',
			),
		),
	);
}

function agendamentos_cupons_de_prioridade(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['simples']['finalizar'] = Array(
		'botoes' => Array(
			'administrar' => Array(
				'url' => '../',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-admin')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-admin')),
				'icon' => 'calendar alternate',
				'cor' => 'blue',
			),
			'cupons' => Array(
				'url' => '',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-coupon')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-coupon')),
				'icon' => 'certificate',
				'cor' => 'green',
			),
		),
	);
}

function agendamentos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'administrar':
		case 'cupons':
			$_GESTOR['interface-opcao'] = 'simples';
		break;
	}
}

// ==== Ajax

function agendamentos_ajax_atualizar(){
	global $_GESTOR;
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
	));
	
	// ===== Variáveis padrões iniciais.
	
	$total = 0;
	$imprimir = false;
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Pegar dados de requisição.
	
	$data = banco_escape_field($_REQUEST['data']);
	$status = banco_escape_field($_REQUEST['status']);
	
	// ===== Pegar células da tabela.
	
	$cel_nome = 'th-senha'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	$cel_nome = 'th-visto'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	$cel_nome = 'th-email'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	
	$cel_nome = 'cel-acompanhante'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	$cel_nome = 'td-senha'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	
	$cel_nome = 'td-visto'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	
	$cel_nome = 'enviado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	$cel_nome = 'nao-enviado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	$cel_nome = 'td-email'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	
	$cel_nome = 'cel-agendamento'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	
	// ===== Pegar a tabela da página.
	
	$cel_nome = 'tabela-pessoas'; $tabela = pagina_celula($cel_nome,false,true);
	
	// ===== Tratar cada status enviado.
	
	switch($status){
		case 'confirmados':
			// ===== Pegar os dados do banco.
			
			$hosts_agendamentos = banco_select(Array(
				'tabela' => 'hosts_agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'id_hosts_usuarios',
					'acompanhantes',
					'senha',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='confirmado'"
			));
			
			// ===== Varrer todos os agendamentos.
			
			if($hosts_agendamentos)
			foreach($hosts_agendamentos as $agendamento){
				// ===== Pegar os dados do agendamento.
				
				$id_hosts_agendamentos = $agendamento['id_hosts_agendamentos'];
				$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
				$acompanhantes = (int)$agendamento['acompanhantes'];
				$senha = $agendamento['senha'];
				
				// ===== Pegar os dados do usuário do agendamento.
				
				$hosts_usuarios = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_usuarios',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux = Array(
					'nome' => $hosts_usuarios['nome'],
					'senha' => $senha,
					'acompanhantes' => $acompanhantes,
				);
				
				// ===== Pegar os dados dos acompanhantes.
				
				$hosts_agendamentos_acompanhantes = banco_select(Array(
					'tabela' => 'hosts_agendamentos_acompanhantes',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux['acompanhantesDados'] = $hosts_agendamentos_acompanhantes;
				
				// ===== Atualizar o total de pessoas agendadas.
				
				$total += 1+$acompanhantes;
				
				// ===== Incluir os dados do agendamento no array agendamentos.
				
				$agendamentos[] = $agendamentosAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($agendamentos, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($agendamentos){
				$cel_nome = 'th-senha'; $tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->',$cel[$cel_nome]);
				
				foreach($agendamentos as $agendamento){
					$cel_nome = 'cel_nome'; $cel_aux = $cel[$cel_nome];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"campo",$valor['id']);
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}

			// ===== Impressão opções.
			
			$imprimir = true;
		break;
		default:
			$tabela = '';
	}
	
	// ===== Retornar os dados para atualização no cliente.
	
	$_GESTOR['ajax-json'] = Array(
		'tabela' => $tabela,
		'total' => $total,
		'imprimir' => $imprimir,
		'status' => 'OK',
	);
}

// ==== Start

function agendamentos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'atualizar': agendamentos_ajax_atualizar(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		agendamentos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'administrar': agendamentos_administrar(); break;
			case 'cupons': agendamentos_cupons_de_prioridade(); break;
		}
		
		interface_finalizar();
	}
}

agendamentos_start();

?>