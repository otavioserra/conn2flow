<?php

// ===== Plataforma responsável por receber solicitações do 'cliente'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-cliente-plugin';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

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

// =========================== Funções da Plataforma

function plataforma_cliente_plugin_agendamentos(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'agendar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: usuarioID e agendamentoData.
			
			if(isset($dados['usuarioID']) && isset($dados['agendamentoData'])){
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
				
				$agendamento_ativacao = (existe($config['agendamento-ativacao']) ? true : false);
				$msgAgendamentoSuspenso = (existe($config['msg-agendamento-suspenso']) ? $config['msg-agendamento-suspenso'] : '');
				
				// ===== Caso o agendamento estiver inativo, retornar mensagem de inatividade.
				
				if(!$agendamento_ativacao){
					return Array(
						'status' => 'AGENDAMENTO_INATIVO',
						'error-msg' => $msgAgendamentoSuspenso,
					);
				}
				
				// ===== Tratar os dados enviados.
				
				$usuarioID = $dados['usuarioID'];
				$agendamentoData = $dados['agendamentoData'];
				$acompanhantes = (int)$dados['acompanhantes'];
				$acompanhantesNomes = $dados['acompanhantesNomes'];
				
				// ===== Verificar se a data enviada é permitida. Senão for retornar mensagem de erro.
				
				if(!plataforma_cliente_plugin_data_permitida($agendamentoData)){
					$msgAgendamentoDataNaoPermitida = (existe($config['msg-agendamento-data-nao-permitida']) ? $config['msg-agendamento-data-nao-permitida'] : '');
					
					return Array(
						'status' => 'AGENDAMENTO_DATA_NAO_PERMITIDA',
						'error-msg' => $msgAgendamentoDataNaoPermitida,
					);
				}
				
				// ===== Verificar se está na fase residual ou antes do sorteio (fase de sorteio é tratada na função anterior de 'data_permitida'). Tratar cada caso de forma diferente.
				
				$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
				
				$hoje = date('Y-m-d');
				
				if(strtotime($agendamentoData) <= strtotime($hoje.' + '.$fase_residual.' day')){
					
				} else {
					
				}
				
				// ===== Retornar dados.
				
				return Array(
					'status' => 'OK',
					'data' => Array(
						//'dado' => $dado,
					),
				);
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		default:
			return Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
}

// =========================== Funções de Acesso

function plataforma_cliente_plugin_start(){
	global $_GESTOR;
	
	// ===== Verifica a opção, executa interface caso encontrado e retorna os dados
	
	switch($_GESTOR['caminho'][1]){
		case 'agendamentos': $dados = plataforma_cliente_plugin_agendamentos(); break;
	}

	// ===== Caso haja dados criados por alguma opção, retornar os dados. Senão retornar NULL.
	
	if(isset($dados)){
		return $dados;
	} else {
		return NULL;
	}
}

// ===== Retornar plataforma.

return plataforma_cliente_plugin_start();

?>