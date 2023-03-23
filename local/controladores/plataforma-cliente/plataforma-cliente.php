<?php

// ===== Plataforma responsável por receber solicitações do 'cliente'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-cliente-plugin';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'dataDebugAtivo' => true,
	'dataDebug' => '15-04-2023',
);

// =========================== Funções Auxiliares

function plataforma_cliente_plugin_montar_calendario($mes,$ano,$diasComEventos = Array(),$diasSemEventos = Array()){
	// Definição dos styles da tabela.
	
	$styles = '
	<style>
		.calendar{
			background-color: #d9edf7;
			font-size:16px;
		}
		.calendar caption{
			color: #204d74;
			font-weight: bold;
			line-height:30px;
			font-size:18px;
		}
		.day{
			text-align:center;
			min-width:35px;
			line-height:30px;
		}
		.event{
			text-align:center;
			min-width:35px;
			line-height:30px;
			background-color: #204d74;
			color: white;
			font-weight: bold;
		}
		.not-event{
			text-align:center;
			min-width:35px;
			line-height:30px;
			background-color: #db2828;
			color: white;
			font-weight: bold;
		}
	</style>
	';
	
	// Passar os estilos para o formato inline.
	
	$process = false;
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $styles) as $styleLine){
		$styleLine = trim($styleLine);
		$open = false;
		$close = false;
		
		if(preg_match('/'.preg_quote('{').'/', $styleLine) > 0){
			$idStyleNow = preg_replace('/\{/', '', $styleLine);
			$open = true;
			$process = true;
		}
		
		if(preg_match('/'.preg_quote('}').'/', $styleLine) > 0){
			$close = true;
			$process = false;
		}
		
		if($process && !$open){
			if(!isset($stylesInline[$idStyleNow])){
				$stylesInline[$idStyleNow] = '';
			}
			
			$stylesInline[$idStyleNow] .= $styleLine;
		}
	}
	
	// Create array containing abbreviations of days of week and mess names.
	$daysOfWeek = array('Dom','Seg','Ter','Qua','Qui','Sex','Sab');
	$messOfano = array('Nenhum','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro');

	// What is the first day of the mes in question?
	$firstDayOfmes = mktime(0,0,0,$mes,1,$ano);

	// How many days does this mes contain?
	$numberDays = date('t',$firstDayOfmes);

	// Retrieve some information about the first day of the
	// mes in question.
	$dateComponents = getdate($firstDayOfmes);

	// What is the name of the mes in question?
	$mesName = $messOfano[$mes];

	// What is the index value (0-6) of the first day of the
	// mes in question.
	$dayOfWeek = $dateComponents['wday'];

	// Create the table tag opener and day headers

	$calendar = "<table class='calendar' style='".$stylesInline['.calendar']."' cellspacing='5'>";
	$calendar .= "<caption style='".$stylesInline['.calendar caption']."'>$mesName $ano</caption>";
	$calendar .= "<tr>";

	// Create the calendar headers

	foreach($daysOfWeek as $day){
		$calendar .= "<th class='header'>$day</th>";
	} 

	// Create the rest of the calendar

	// Initiate the day counter, starting with the 1st.

	$currentDay = 1;

	$calendar .= "</tr><tr>";

	// The variable $dayOfWeek is used to
	// ensure that the calendar
	// display consists of exactly 7 columns.

	if($dayOfWeek > 0){ 
		$calendar .= "<td colspan='$dayOfWeek'>&nbsp;</td>"; 
	}

	$mes = str_pad($mes, 2, "0", STR_PAD_LEFT);

	while ($currentDay <= $numberDays) {

		// Seventh column (Saturday) reached. Start a new row.

		if ($dayOfWeek == 7) {
			$dayOfWeek = 0;
			$calendar .= "</tr><tr>";
		}

		$currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);

		$date = "$ano-$mes-$currentDayRel";
		
		$cssDay = '.day';
		
		// Marcar dias com eventos
		
		if($diasComEventos)
		foreach($diasComEventos as $day){
			if($day == $currentDay){
				$cssDay = '.event';
				break;
			}
		}
		
		// Marcar dias sem eventos
		
		if($diasSemEventos)
		foreach($diasSemEventos as $day){
			if($day == $currentDay){
				$cssDay = '.not-event';
				break;
			}
		}
		
		$calendar .= "<td class='day' style='".$stylesInline[$cssDay]."' rel='$date'>$currentDay</td>";

		// Increment counters

		$currentDay++;
		$dayOfWeek++;
	}

	// Complete the row of the last week in mes, if necessary

	if ($dayOfWeek != 7) { 
		$remainingDays = 7 - $dayOfWeek;
		$calendar .= "<td colspan='$remainingDays'>&nbsp;</td>"; 
	}

	$calendar .= "</tr>";

	$calendar .= "</table>";

	return $calendar;

}

function plataforma_cliente_plugin_data_permitida($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Inclusão de bibliotecas.
	
	gestor_incluir_biblioteca('formato');
	
	// ===== Padrões caso não seja definido via parâmetros.
	
	if(!isset($mes)){ $mes = date('n'); }
	if(!isset($ano)){ $ano = date('Y'); }
	if(!isset($hoje)){ $hoje = time(); }
	if(!isset($inscricaoInicio)){ $inscricaoInicio = ''; }
	
	// ===== Passar string para int para fazer contas.
	
	$mes = (int)$mes;
	$ano = (int)$ano;
	
	// ===== Definir a data do primeiro dia e do último dia do mês procurado.
	
	if($mes < 10){
		$mes = '0'.$mes;
	}
	
	$prmeiroDiaMes = date($ano.'-'.$mes.'-01');
	$ultimoDiaMes = date($ano.'-'.$mes.'-'.date('t',strtotime($prmeiroDiaMes)));
	
	// ===== Pegar as configurações das escalas.
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas'));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
	$datas_extras_dias_semana_maximo_vagas_arr = (existe($config['datas-extras-dias-semana-maximo-vagas']) ? explode(',',$config['datas-extras-dias-semana-maximo-vagas']) : Array());
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array()); else $datas_indisponiveis = Array();
	if(existe($config['datas-extras-disponiveis'])) $datas_extras_disponiveis = (existe($config['datas-extras-disponiveis-valores']) ? explode('|',$config['datas-extras-disponiveis-valores']) : Array()); else $datas_extras_disponiveis = Array();
	$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
	$calendario_ferias_ate = (existe($config['calendario-ferias-ate']) ? trim($config['calendario-ferias-ate']) : '20 January');
	$periodoLimiteAlteracao = (existe($config['periodo-limite-alteracao']) ? $config['periodo-limite-alteracao'] : '');
	
	// ===== Pegar o período de férias para remover do calendário do mês.
	
	for($i=-1;$i<$anos+1;$i++){
		$periodo_ferias[] = Array(
			'inicio' => strtotime($calendario_ferias_de." ".($ano+$i)),
			'fim' => strtotime($calendario_ferias_ate." ".($ano+$i+1)),
		);
	}
	
	// ===== Definição do primeiro e último dia deste calendário.
	
	$primeiro_dia = strtotime($prmeiroDiaMes);
	$ultimo_dia = strtotime($ultimoDiaMes);
	
	// ===== Verificar se hoje é antes do início da inscrição. Caso positivo, não permirtir selecionar as datas.
	
	$inscricaoInicioTempo = strtotime(str_replace('/', '-', $inscricaoInicio));
	
	$antesDaInscricao = false;
	
	if($hoje < $inscricaoInicioTempo){
		$antesDaInscricao = true;
	}
	
	// ===== Varrer todos os dias do mês.
	
	$diaLimiteAlteracao = strtotime(date("Y-m-d", $hoje) . " + ".$periodoLimiteAlteracao." day");
	$dia = $primeiro_dia;
	
	do {
		$data_extra_permitida = false;
		$data_extra_posicao = 0;
		$datasDestacada = false;
		$flag = false;
		$dataFormatada = date('d/m/Y', $dia);
		
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
		
		if($datas_extras_disponiveis){
			foreach($datas_extras_disponiveis as $ded){
				if($dataFormatada == $ded){
					$flag = false;
					$data_extra_permitida = true;
					break;
				}
				$data_extra_posicao++;
			}
		}
		
		if($datas_indisponiveis){
			foreach($datas_indisponiveis as $di){
				if($dataFormatada == $di){
					$flag = true;
					break;
				}
			}
		}
		
		if(!$flag){
			$flag2 = false;
			$count_dias = 0;
			
			if($data_extra_permitida){
				$flag2 = true;
				$count_dias = $data_extra_posicao;
			} else {
				if($dias_semana)
				foreach($dias_semana as $dia_semana){
					if($dia_semana == strtolower(date('D',$dia))){
						$flag2 = true;
						break;
					}
					$count_dias++;
				}
			}
			
			if($flag2){
				$dataAux = date('Y-m-d', $dia);
				$flag3 = false;
				
				// ===== Não permitir selecionar datas do passado mais o periodoLimiteAlteracao.
				
				if($dia < $diaLimiteAlteracao){
					$flag3 = true;
				}
				
				// ===== Não permitir selecionar datas antes da inscrição.
				
				if($antesDaInscricao){
					$flag3 = true;
				}
				
				// ===== Data permitida.
				
				if(!$flag3){
					if($data == date('d/m/Y', $dia)){
						return true;
					}
				}
			}
		}
		
		$dia += 86400;
	} while ($dia <= $ultimo_dia);
	
	return false;
}

function plataforma_cliente_plugin_data_dias_antes($mes = 0,$ano = 0, $diasPeriodo = 0, $dataInicial = NULL){
	// ===== Definir a data inicial caso a mesma não tenha sido definida.
	
	if(!isset($dataInicial)){
		$dataInicial = date('d-m-Y');
	}
	
	$dataInicial = str_replace('/', '-', $dataInicial);
	
	// ===== Pegar o mês e o ano da data inicial.
	
	$diaInicial = (int)date('j',strtotime($dataInicial));
	$mesInicial = (int)date('n',strtotime($dataInicial));
	$anoInicial = (int)date('Y',strtotime($dataInicial));
	
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
	
	$dataInicial = str_replace('/', '-', $dataInicial);
	
	// ===== Pegar o mês e o ano da data inicial.
	
	$numDiasMes = (int)date('t',strtotime($dataInicial));
	
	$diaInicial = (int)date('j',strtotime($dataInicial));
	$mesInicial = (int)date('n',strtotime($dataInicial));
	$anoInicial = (int)date('Y',strtotime($dataInicial));
	
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

// =========================== Funções da Plataforma

function plataforma_cliente_plugin_escalas(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Módulo variáveis.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'escalar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: usuarioID, mes e ano.
			
			if(isset($dados['usuarioID']) && isset($dados['mes']) && isset($dados['ano'])){
				// ===== Incluir bibliotecas padrões.
				
				gestor_incluir_biblioteca('formato');
				
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas'));
				
				$escala_ativacao = (existe($config['escala-ativacao']) ? true : false);
				$msg_escala_suspenso = (existe($config['msg-escala-suspenso']) ? $config['msg-escala-suspenso'] : '');
				$diaInicioInscricao = (existe($config['dia-inicio-inscricao']) ? $config['dia-inicio-inscricao'] : '');
				$mesInicioInscricao = (existe($config['mes-inicio-inscricao']) ? $config['mes-inicio-inscricao'] : '');
				$periodoLimiteAlteracao = (existe($config['periodo-limite-alteracao']) ? $config['periodo-limite-alteracao'] : '');
	
				// ===== Caso o agendamento estiver inativo, retornar mensagem de inatividade.
				
				if(!$escala_ativacao){
					return Array(
						'status' => 'ESCALAMENTO_INATIVO',
						'error-msg' => $msg_escala_suspenso,
					);
				}
				
				// ===== Tratar os dados enviados.
				
				$id_hosts_usuarios = banco_escape_field($dados['usuarioID']);
				$mes = $dados['mes'];
				$ano = $dados['ano'];
				$datasStr = $dados['datas'];
				
				// ===== Definição do tempo do dia de agora.
				
				if($modulo['dataDebugAtivo']){
					$hoje = strtotime($modulo['dataDebug']);
				} else {
					$hoje = time();
				}
				
				// ===== Definir o dia limite de alteração para poder ignorar datas do passado.
				
				$tempoLimiteAlteracao = strtotime(date("Y-m-d", $hoje) . " + ".$periodoLimiteAlteracao." day");
				
				// ===== Passar o mês e o ano para inteiro.
				
				$mes = (int)$mes;
				$ano = (int)$ano;
				
				// ===== Definir o início da inscrição.
				
				$mesInicioInscricaoAux = $mes - (int)$mesInicioInscricao;
				$diaInicioInscricaoAux = $diaInicioInscricao;
				$anoInicioInscricaoAux = $ano;
				
				if($mesInicioInscricaoAux < 1){
					$mesInicioInscricaoAux = 12;
					$anoInicioInscricaoAux--; 
				}
				
				if($mesInicioInscricaoAux < 10){
					$mesInicioInscricaoAux = '0' . $mesInicioInscricaoAux;
				}
				
				if($diaInicioInscricaoAux < 10){
					$diaInicioInscricaoAux = '0' . $diaInicioInscricaoAux;
				}
				
				$data_inscricao_inicio = $diaInicioInscricaoAux . '/' . $mesInicioInscricaoAux . '/' . $anoInicioInscricaoAux;
				
				// ===== Definir a data do início e fim da confirmação.
				
				$mesAtualFormatado = ($mes < 10 ? '0':'') . $mes;
				
				$diasInicioConfirmacao = (existe($config['dias-inicio-confirmacao']) ? $config['dias-inicio-confirmacao'] : '');
				$diasFimConfirmacao = (existe($config['dias-fim-confirmacao']) ? $config['dias-fim-confirmacao'] : '');
				
				$data_confirmacao_inicio = plataforma_cliente_plugin_data_dias_antes($mes,$ano,$diasInicioConfirmacao,'01/'. $mesAtualFormatado . '/' . $ano);
				$data_confirmacao_fim = plataforma_cliente_plugin_data_dias_antes($mes,$ano,$diasFimConfirmacao,'01/'. $mesAtualFormatado . '/' . $ano);
				
				// ===== Definir a data de inscrição fim.
				
				$data_inscricao_fim = date('d/m/Y', strtotime(str_replace('/', '-', $data_confirmacao_inicio) . ' -1 day'));
				
				// ===== Definir a data inicial e final do mês.
				
				$data_inicial_mes = date('d/m/Y', strtotime(str_replace('/', '-', $data_confirmacao_fim) . ' +1 day'));
				
				$mesInicio = $mes;
				$anoInicio = $ano;
				
				if($mesInicio < 10){
					$mesInicio = '0' . $mesInicio;
				}
				
				$prmeiroDiaMes = date($anoInicio.'-'.$mesInicio.'-01');
				$data_final_mes = date('t',strtotime($prmeiroDiaMes)) . '/' . $mesInicio . '/' . $anoInicio;
				
				// ===== Verificar se as datas enviadas são permitidas. Senão for alguma ou várias, retornar mensagem de erro com a listagem das datas não permitidas.
				
				if(existe($datasStr)){
					$algumaDataNaoPermitida = false;
					$datasNaoPermitidas = '';
					$datas = explode(',',$datasStr);
					
					if($datas)
					foreach($datas as $data){
						// ===== Ignorar datas do passado para manter histórico de datas selecionadas no passado.
						
						$dataTime = strtotime(str_replace('/', '-', $data));
						
						if($dataTime < $tempoLimiteAlteracao){
							continue;
						}
						
						// ===== Verificar se a data é permitida.
						
						if(!plataforma_cliente_plugin_data_permitida(Array(
							'data' => $data,
							'mes' => $mes,
							'ano' => $ano,
							'hoje' => $hoje,
							'inscricaoInicio' => $data_inscricao_inicio,
						))){
							$algumaDataNaoPermitida = true;
							$datasNaoPermitidas .= (existe($datasNaoPermitidas) ? ', ':'') . $data;
						}
					}
					
					if($algumaDataNaoPermitida){
						$msgEscalamentoDataNaoPermitida = (existe($config['msg-escalamento-data-nao-permitida']) ? $config['msg-escalamento-data-nao-permitida'] : '');
						
						$msgEscalamentoDataNaoPermitida = modelo_var_troca_tudo($msgEscalamentoDataNaoPermitida,"#datas#",$datasNaoPermitidas);
						
						return Array(
							'status' => 'ESCALAMENTO_DATA_NAO_PERMITIDA',
							'error-msg' => $msgEscalamentoDataNaoPermitida,
						);
					}
				} else {
					$datas = Array();
				}
				
				// ===== Verificar qual fase a escala se encontra.
				
				$faseAtual = '';
				
				if(
					$hoje >= strtotime(str_replace('/', '-', $data_inscricao_inicio)) && 
					$hoje < strtotime(str_replace('/', '-', $data_confirmacao_inicio)) - 1
				){
					$faseAtual = 'inscricao';
				} else if(
					$hoje >= strtotime(str_replace('/', '-', $data_confirmacao_inicio)) && 
					$hoje < strtotime(str_replace('/', '-', $data_inicial_mes)) - 1
				){
					$faseAtual = 'confirmacao';
				} else if(
					$hoje >= strtotime(str_replace('/', '-', $data_inicial_mes))
				){
					$faseAtual = 'utilizacao';
				}
				
				// ===== Pegar a quantidade de vagas totais do mês.
				
				switch($faseAtual){
					case 'utilizacao':
						$dateInicio = formato_dado_para('date',$data_inicial_mes);
						$dateFim = formato_dado_para('date',$data_final_mes);
						
						$hosts_escalas_controle = banco_select(Array(
							'tabela' => 'hosts_escalas_controle',
							'campos' => Array(
								'id_hosts_escalas_controle',
								'data',
								'total',
							),
							'extra' => 
								"WHERE data >= '".$dateInicio."'"
								." AND data <= '".$dateFim."'"
								." AND id_hosts='".$id_hosts."'"
						));
					break;
				}
				
				// ===== Criar ou pegar os dados da escala do mês / ano requisitados.
				
				$hosts_escalas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_escalas',
					'campos' => Array(
						'id_hosts_escalas',
						'pubID',
						'status',
					),
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND mes='".$mes."'"
						." AND ano='".$ano."'"
				));
				
				$novaEscala = false;
				
				if($hosts_escalas){
					$id_hosts_escalas = $hosts_escalas['id_hosts_escalas'];
					$pubID = $hosts_escalas['pubID'];
					$escalaStatus = $hosts_escalas['status'];
				} else {
					// ===== Gerar o token de validação.
					
					gestor_incluir_biblioteca('autenticacao');
					
					$validacao = autenticacao_cliente_gerar_token_validacao(Array(
						'id_hosts' => $id_hosts,
					));
					
					$pubID = $validacao['pubID'];
					$token = $validacao['token'];
					
					// ===== Inserir no banco de dados a escala do mês.
					
					banco_insert_name_campo('id_hosts',$id_hosts);
					banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
					banco_insert_name_campo('mes',$mes);
					banco_insert_name_campo('ano',$ano);
					banco_insert_name_campo('status','novo');
					banco_insert_name_campo('pubID',$pubID);
					banco_insert_name_campo('versao','1');
					banco_insert_name_campo('data_criacao','NOW()',true);
					banco_insert_name_campo('data_modificacao','NOW()',true);
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_escalas"
					);
					
					$id_hosts_escalas = banco_last_id();
					$escalaStatus = 'novo';
					
					$novaEscala = true;
				}
				
				// ===== Criar ou pegar as datas da escala do mês / ano.
				
				$hosts_escalas_datas = banco_select(Array(
					'tabela' => 'hosts_escalas_datas',
					'campos' => Array(
						'id_hosts_escalas_datas',
						'data',
						'status',
						'selecionada',
					),
					'extra' => 
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Pegar os dados para cálculo de total de vagas.
				
				$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
				$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
				$datas_extras_dias_semana_maximo_vagas_arr = (existe($config['datas-extras-dias-semana-maximo-vagas']) ? explode(',',$config['datas-extras-dias-semana-maximo-vagas']) : Array());
				if(existe($config['datas-extras-disponiveis'])) $datas_extras_disponiveis = (existe($config['datas-extras-disponiveis-valores']) ? explode('|',$config['datas-extras-disponiveis-valores']) : Array()); else $datas_extras_disponiveis = Array();
				
				// ===== Varrer todas as datas enviadas e atualizar no banco conforme necessidade.
				
				$datasProcessadasIDs = Array();
				$datasSemVagas = Array();
				$diasEscalados = Array();
				$datasSemVagasFound = false;
				$escalaAtualizada = false;
				$escalaUtilizacao = false;
				$escalaControleAtualizada = false;
				
				if(existe($datasStr)){
					$datas = explode(',',$datasStr);
					
					for($i=0;$i<count($datas);$i++){
						$dataFormatada = $datas[$i];
						$dataTipoDate = formato_dado_para('date',$dataFormatada);
						$dataTempo = strtotime(str_replace('/', '-', $dataFormatada));
						$diaDaData = date('j',$dataTempo);
						$dataFound = false;
						$dataSelecionada = false;
						$id_hosts_escalas_datas = '';
						$status = '';
						
						// ===== Ignorar datas do passado para manter histórico de datas selecionadas no passado.
						
						if($dataTempo < $tempoLimiteAlteracao){
							continue;
						}
						
						// ===== Procurar a data se já foi definida anteriormente.
						
						if($hosts_escalas_datas)
						foreach($hosts_escalas_datas as $hed){
							if($dataTipoDate == $hed['data']){
								$dataFound = true;
								$id_hosts_escalas_datas = $hed['id_hosts_escalas_datas'];
								$status = $hed['status'];
								
								if($hed['selecionada']){
									$dataSelecionada = true;
								}
								
								break;
							}
						}
						
						// ===== Tratar cada fase caso tenha encontrado ou não o registo.
						
						if(!$dataFound){
							switch($faseAtual){
								case 'inscricao':
									// ===== Incluir o registro novo no banco de dados.
									
									banco_insert_name_campo('id_hosts',$id_hosts);
									banco_insert_name_campo('id_hosts_escalas',$id_hosts_escalas);
									banco_insert_name_campo('data',$dataTipoDate);
									banco_insert_name_campo('status','novo');
									banco_insert_name_campo('selecionada','1',true);
									banco_insert_name_campo('selecionada_inscricao','1',true);
									
									banco_insert_name
									(
										banco_insert_name_campos(),
										"hosts_escalas_datas"
									);
									
									$id_hosts_escalas_datas = banco_last_id();
									
									$diasEscalados[] = $diaDaData;
								break;
								case 'confirmacao':
									continue;
								break;
								case 'utilizacao':
									// ===== Definir o total de vagas.
									
									$data_extra_permitida = false;
									$data_extra_posicao = 0;
									$count_dias = 0;
									
									if($datas_extras_disponiveis){
										foreach($datas_extras_disponiveis as $ded){
											if($dataFormatada == $ded){
												$data_extra_permitida = true;
												break;
											}
											$data_extra_posicao++;
										}
									}
									
									if($data_extra_permitida){
										$count_dias = $data_extra_posicao;
									} else {
										if($dias_semana)
										foreach($dias_semana as $dia_semana){
											if($dia_semana == strtolower(date('D',$dataTempo))){
												break;
											}
											$count_dias++;
										}
									}
									
									if($data_extra_permitida){
										if(count($datas_extras_dias_semana_maximo_vagas_arr) > 1){
											$totalVagas = $datas_extras_dias_semana_maximo_vagas_arr[$count_dias];
										} else {
											$totalVagas = $datas_extras_dias_semana_maximo_vagas_arr[0];
										}
									} else {
										if(count($dias_semana_maximo_vagas_arr) > 1){
											$totalVagas = $dias_semana_maximo_vagas_arr[$count_dias];
										} else {
											$totalVagas = $dias_semana_maximo_vagas_arr[0];
										}
									}
									
									// ===== Verificar se há vagas disponíveis na fase de 'utilização'. Se sim, diminuir uma vaga no total na escala controle da data.
									
									$dataSemVagaFound = false;
									$dataEscalaControleEncontrada = false;
									
									if($hosts_escalas_controle)
									foreach($hosts_escalas_controle as $hec){
										if($dataTipoDate == $hec['data']){
											$dataEscalaControleEncontrada = true;
											
											if((int)$hec['total'] + 1 <= $totalVagas){
												banco_update_campo('total','total+1',true);
												
												banco_update_executar('hosts_escalas_controle',"WHERE id_hosts_escalas_controle='".$hec['id_hosts_escalas_controle']."' AND id_hosts='".$id_hosts."'");
												$escalaControleAtualizada = true;
											} else {
												$datasSemVagasFound = true;
												$dataSemVagaFound = true;
											}
											break;
										}
									}
									
									if(!$dataEscalaControleEncontrada){
										if(1 <= $totalVagas){
											banco_insert_name_campo('id_hosts',$id_hosts);
											banco_insert_name_campo('data',$dataTipoDate);
											banco_insert_name_campo('total','1',true);
											banco_insert_name_campo('status','utilizacao');
											
											banco_insert_name
											(
												banco_insert_name_campos(),
												"hosts_escalas_controle"
											);
											$escalaControleAtualizada = true;
										}
									}
									
									// ===== Caso não tenha vaga, não incluir no banco de dados e continuar o loop. Além disso, guarda a data para informar o usuário do problema.
									
									if($dataSemVagaFound){
										$datasSemVagas[] = $dataFormatada;
										continue;
									}
									
									// ===== Senão incluir o registro com status 'vaga-residual' no banco de dados.
									
									banco_insert_name_campo('id_hosts',$id_hosts);
									banco_insert_name_campo('id_hosts_escalas',$id_hosts_escalas);
									banco_insert_name_campo('data',$dataTipoDate);
									banco_insert_name_campo('status','vaga-residual');
									banco_insert_name_campo('selecionada','1',true);
									
									banco_insert_name
									(
										banco_insert_name_campos(),
										"hosts_escalas_datas"
									);
									
									$id_hosts_escalas_datas = banco_last_id();
									
									$diasEscalados[] = $diaDaData;
									$escalaUtilizacao = true;
								break;
							}
						} else {
							// ===== Verificar se o estado selecionado já está definido. Se sim, não há necessidade de atualizações.
							
							if(!$dataSelecionada){
								// ===== Caso contrário, aplicar em cada fase a atualização necessária.
								
								switch($faseAtual){
									case 'inscricao':
										banco_update_campo('selecionada','1',true);
										banco_update_campo('selecionada_inscricao','1',true);
										
										banco_update_executar(
											'hosts_escalas_datas',
											"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
											." AND id_hosts='".$id_hosts."'"
											." AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'"
										);
										
										$diasEscalados[] = $diaDaData;
										$escalaAtualizada = true;
									break;
									case 'confirmacao':
										if(
											$status == 'confirmado' ||
											$status == 'qualificado' ||
											$status == 'email-enviado' ||
											$status == 'email-nao-enviado'
										){
											banco_update_campo('selecionada','1',true);
											banco_update_campo('selecionada_confirmacao','1',true);
											
											banco_update_executar(
												'hosts_escalas_datas',
												"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
												." AND id_hosts='".$id_hosts."'"
												." AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'"
											);
											
											$diasEscalados[] = $diaDaData;
											$escalaAtualizada = true;
										}
									break;
									case 'utilizacao':
										// ===== Definir o total de vagas.
										
										$data_extra_permitida = false;
										$data_extra_posicao = 0;
										$count_dias = 0;
										
										if($datas_extras_disponiveis){
											foreach($datas_extras_disponiveis as $ded){
												if($dataFormatada == $ded){
													$data_extra_permitida = true;
													break;
												}
												$data_extra_posicao++;
											}
										}
										
										if($data_extra_permitida){
											$count_dias = $data_extra_posicao;
										} else {
											if($dias_semana)
											foreach($dias_semana as $dia_semana){
												if($dia_semana == strtolower(date('D',$dataTempo))){
													break;
												}
												$count_dias++;
											}
										}
										
										if($data_extra_permitida){
											if(count($datas_extras_dias_semana_maximo_vagas_arr) > 1){
												$totalVagas = $datas_extras_dias_semana_maximo_vagas_arr[$count_dias];
											} else {
												$totalVagas = $datas_extras_dias_semana_maximo_vagas_arr[0];
											}
										} else {
											if(count($dias_semana_maximo_vagas_arr) > 1){
												$totalVagas = $dias_semana_maximo_vagas_arr[$count_dias];
											} else {
												$totalVagas = $dias_semana_maximo_vagas_arr[0];
											}
										}
										
										// ===== Verificar se há vagas disponíveis na fase de 'utilização'. Se sim, diminuir uma vaga no total na escala controle da data.
										
										$dataSemVagaFound = false;
										$dataEscalaControleEncontrada = false;
										
										if($hosts_escalas_controle)
										foreach($hosts_escalas_controle as $hec){
											if($dataTipoDate == $hec['data']){
												$dataEscalaControleEncontrada = true;
												
												if((int)$hec['total'] + 1 <= $totalVagas){
													banco_update_campo('total','total+1',true);
													
													banco_update_executar('hosts_escalas_controle',"WHERE id_hosts_escalas_controle='".$hec['id_hosts_escalas_controle']."' AND id_hosts='".$id_hosts."'");
													$escalaControleAtualizada = true;
												} else {
													$datasSemVagasFound = true;
													$dataSemVagaFound = true;
												}
												break;
											}
										}
										
										if(!$dataEscalaControleEncontrada){
											if(1 <= $totalVagas){
												banco_insert_name_campo('id_hosts',$id_hosts);
												banco_insert_name_campo('data',$dataTipoDate);
												banco_insert_name_campo('total','1',true);
												banco_insert_name_campo('status','utilizacao');
												
												banco_insert_name
												(
													banco_insert_name_campos(),
													"hosts_escalas_controle"
												);
												$escalaControleAtualizada = true;
											}
										}
										
										// ===== Caso não tenha vaga, não atualizar no banco de dados e continuar o loop. Além disso, guarda a data para informar o usuário do problema.
										
										if($dataSemVagaFound){
											$datasSemVagas[] = $dataFormatada;
											continue;
										}
										
										// ===== Senão atualizar o registro com status 'vaga-residual' no banco de dados.
										
										banco_update_campo('selecionada','1',true);
										banco_update_campo('status','vaga-residual');
										
										banco_update_executar(
											'hosts_escalas_datas',
											"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
											." AND id_hosts='".$id_hosts."'"
											." AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'"
										);
										
										$diasEscalados[] = $diaDaData;
										$escalaAtualizada = true;
										$escalaUtilizacao = true;
									break;
								}
							}
						}
						
						$datasProcessadasIDs[] = $id_hosts_escalas_datas;
					}
				}
				
				// ===== Apagar os registros não processados. Ou então, dependendo da fase, somente marcar como não selecionado.
				
				$hosts_escalas_datas = banco_select(Array(
					'tabela' => 'hosts_escalas_datas',
					'campos' => Array(
						'id_hosts_escalas_datas',
						'status',
						'data',
						'selecionada',
					),
					'extra' => 
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				if($hosts_escalas_datas){
					foreach($hosts_escalas_datas as $hed){
						$foundDataProcessada = false;
						$id_hosts_escalas_datas = $hed['id_hosts_escalas_datas'];
						$status = $hed['status'];
						$dataTipoDate = $hed['data'];
						$dataTempo = strtotime($dataTipoDate);
						$dataSelecionada = false;
						
						// ===== Ignorar datas do passado para manter histórico de datas selecionadas no passado.
						
						if($dataTempo < $tempoLimiteAlteracao){
							continue;
						}
						
						// ===== Verificar se a data está selecionada.
						
						if($hed['selecionada']){
							$dataSelecionada = true;
						}
						
						// ===== Procurar se a data foi processada.
						
						if($datasProcessadasIDs){
							foreach($datasProcessadasIDs as $dpID){
								if($hed['id_hosts_escalas_datas'] == $dpID){
									$foundDataProcessada = true;
									break;
								}
							}
						}
						
						if(!$foundDataProcessada && $dataSelecionada){
							switch($faseAtual){
								case 'inscricao':
									banco_update_campo('selecionada','NULL',true);
									banco_update_campo('selecionada_inscricao','NULL',true);
									
									banco_update_executar(
										'hosts_escalas_datas',
										"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
										." AND id_hosts='".$id_hosts."'"
										." AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'"
									);
									
									$escalaAtualizada = true;
								break;
								case 'confirmacao':
									if(
										$status == 'confirmado' ||
										$status == 'qualificado' ||
										$status == 'email-enviado' ||
										$status == 'email-nao-enviado'
									){
										banco_update_campo('selecionada','NULL',true);
										banco_update_campo('selecionada_confirmacao','NULL',true);
										
										banco_update_executar(
											'hosts_escalas_datas',
											"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
											." AND id_hosts='".$id_hosts."'"
											." AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'"
										);
										
										$escalaAtualizada = true;
									}
								break;
								case 'utilizacao':
									banco_update_campo('selecionada','NULL',true);
									
									banco_update_executar(
										'hosts_escalas_datas',
										"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
										." AND id_hosts='".$id_hosts."'"
										." AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'"
									);
									
									$escalaAtualizada = true;
									
									if($hosts_escalas_controle)
									foreach($hosts_escalas_controle as $hec){
										if($dataTipoDate == $hec['data']){
											banco_update_campo('total','total-1',true);
											
											banco_update_executar('hosts_escalas_controle',"WHERE id_hosts_escalas_controle='".$hec['id_hosts_escalas_controle']."' AND id_hosts='".$id_hosts."'");
											$escalaControleAtualizada = true;

											break;
										}
									}
								break;
							}
						}
					}
				}
				
				// ===== Atualizar a data de modificação da tabela escala caso necessário.
				
				if($escalaAtualizada){
					banco_update_campo('data_modificacao','NOW()',true);
					banco_update_campo('versao','versao=versao+1',true);
					
					banco_update_executar(
						'hosts_escalas',
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
					);
				}
				
				// ===== Atualizar status da escala caso seja uma 'vaga-residual' e não é do tipo 'confirmado' ou o próprio. Motivo: ignorar os confirmados afim de manter histórico. Por isso esse novo status apenas para escalas criadas com vagas residuais.
				
				if($escalaUtilizacao){
					if($escalaStatus != 'confirmado' && $escalaStatus != 'vaga-residual'){
						banco_update_campo('status','vaga-residual');
						
						banco_update_executar(
							'hosts_escalas',
							"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
							." AND id_hosts='".$id_hosts."'"
						);
					}
				}
				
				// ===== Montar o calendário das datas da escala.
				
				$calendario = plataforma_cliente_plugin_montar_calendario($mes,$ano,$diasEscalados);
				
				// ===== Caso seja um novo registro, enviar um email com a confirmação da criação da escala em cada fase diferente. Senão somente retornar uma mensagem de conclusão.
				
				$msgExtra = '';
				
				if($novaEscala){
					switch($faseAtual){
						case 'inscricao':
						case 'utilizacao':
							// ===== Pegar dados do usuário.
							
							$hosts_usuarios = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_usuarios',
								'campos' => Array(
									'nome',
									'email',
								),
								'extra' => 
									"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							// ===== Formatar dados do email.
							
							$escalamentoAssunto = (existe($config['escalamento-assunto']) ? $config['escalamento-assunto'] : '');
							$escalamentoMensagem = (existe($config['escalamento-mensagem']) ? $config['escalamento-mensagem'] : '');
							$msgConclusaoEscalamento = (existe($config['msg-conclusao-escalamento']) ? $config['msg-conclusao-escalamento'] : '');
							
							$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
							
							$email = $hosts_usuarios['email'];
							$nome = $hosts_usuarios['nome'];
							
							$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_escalas,6);
						break;
					}
					
					// ===== Definição da mensagem extra de cada fase.
					
					switch($faseAtual){
						case 'inscricao':
							$msgExtra = (existe($config['msg-extra-conclusao-inscricao']) ? $config['msg-extra-conclusao-inscricao'] : '');
							
							$msgExtra = modelo_var_troca_tudo($msgExtra,"#data1#",$data_confirmacao_inicio);						
							$msgExtra = modelo_var_troca_tudo($msgExtra,"#data2#",$data_confirmacao_fim);
						break;
						case 'utilizacao':
							$msgExtra = (existe($config['msg-extra-conclusao-utilizacao']) ? $config['msg-extra-conclusao-utilizacao'] : '');
						break;
					}
					
					// ===== Formatar mensagem do email.
					
					switch($faseAtual){
						case 'inscricao':
						case 'utilizacao':
							gestor_incluir_biblioteca('host');
							
							$mesEAno = formato_zero_a_esquerda($mes,2) . '/' . $ano;
							
							$escalamentoAssunto = modelo_var_troca_tudo($escalamentoAssunto,"#codigo#",$codigo);
							$escalamentoAssunto = modelo_var_troca_tudo($escalamentoAssunto,"#mes#",$mesEAno);
							
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#codigo#",$codigo);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#titulo#",$tituloEstabelecimento);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#mes#",$mesEAno);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#calendario#",$calendario);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#data1#",$data_confirmacao_inicio);						
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#data2#",$data_confirmacao_fim);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#extra#",$msgExtra);						
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#url-escalamento#",'<a target="escalamento" href="'.host_url(Array('opcao'=>'full')).'escalas/" style="overflow-wrap: break-word;">'.host_url(Array('opcao'=>'full')).'escalas/</a>');
							
							// ===== Enviar email com a notificação de criação da escala.
							
							gestor_incluir_biblioteca(Array('comunicacao','host'));
							
							if(comunicacao_email(Array(
								'hostPersonalizacao' => true,
								'destinatarios' => Array(
									Array(
										'email' => $email,
										'nome' => $nome,
									),
								),
								'mensagem' => Array(
									'assunto' => $escalamentoAssunto,
									'html' => $escalamentoMensagem,
									'htmlAssinaturaAutomatica' => true,
									'htmlVariaveis' => Array(
										Array(
											'variavel' => '[[url]]',
											'valor' => host_url(Array('opcao'=>'full')),
										),
									),
								),
							))){
								
							}
						break;
						case 'utilizacao':
							$msgConclusaoEscalamento = (existe($config['msg-conclusao-escalamento']) ? $config['msg-conclusao-escalamento'] : '');
						break;
					}
				} else {
					$msgConclusaoEscalamento = (existe($config['msg-conclusao-escalamento']) ? $config['msg-conclusao-escalamento'] : '');
				}
				
				// ===== Caso haja alguma data sem vagas, incluir a mensagem na msgExtra.
				
				if(count($datasSemVagas) > 0){
					$msgDatasSemVagas = (existe($config['msg-datas-sem-vagas']) ? $config['msg-datas-sem-vagas'] : '');
					
					$datasSemVagasStr = '';
					foreach($datasSemVagas as $dataSemVaga){
						$datasSemVagasStr .= (existe($datasSemVagasStr) ? ', ':'' ) . $dataSemVaga;
					}
					
					$msgDatasSemVagas = modelo_var_troca_tudo($msgDatasSemVagas,"#datas#",$datasSemVagasStr);
					
					$msgExtra .= $msgDatasSemVagas;
				}
				
				// ===== Colocar a mensagem extra na mensagem de retorno caso necessário. Senão remover o marcador extra da mensagem de retorno.
				
				if(isset($msgDebug)){
					$msgExtra .= $msgDebug;
				}
				
				if(existe($msgExtra)){
					$msgConclusaoEscalamento = modelo_var_troca_tudo($msgConclusaoEscalamento,"#extra#",$msgExtra);
				} else {
					$cel_nome = 'extra'; $msgConclusaoEscalamento = modelo_tag_in($msgConclusaoEscalamento,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				}
				
				// ===== Caso tenha sido alterada o 'hosts_escalas_controle', enviar os dados atualizados.
				
				if($escalaControleAtualizada){
					$dateInicio = formato_dado_para('date',$data_inicial_mes);
					$dateFim = formato_dado_para('date',$data_final_mes);
					
					$hosts_escalas_controle = banco_select(Array(
						'tabela' => 'hosts_escalas_controle',
						'campos' => '*',
						'extra' => 
							"WHERE data >= '".$dateInicio."'"
							." AND data <= '".$dateFim."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					if($hosts_escalas_controle)
					foreach($hosts_escalas_controle as $escala_controle){
						unset($escala_controle['id_hosts']);
						
						$hosts_escalas_controle_proc[] = $escala_controle;
					}
				}
				
				// ===== Tratar dados de retorno.
				
				$hosts_escalas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_escalas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				unset($hosts_escalas['id_hosts']);
				
				$hosts_escalas_datas = banco_select(Array(
					'tabela' => 'hosts_escalas_datas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$hosts_escalas_datas_proc = Array();
				
				if($hosts_escalas_datas)
				foreach($hosts_escalas_datas as $hosts_escala_data){
					unset($hosts_escala_data['id_hosts']);
					
					$hosts_escalas_datas_proc[] = $hosts_escala_data;
				}
				
				$retornoDados = Array(
					'escalas' => $hosts_escalas,
					'escalas_datas' => $hosts_escalas_datas_proc,
					'alerta' => $msgConclusaoEscalamento,
				);
				
				if(isset($hosts_escalas_controle_proc)){
					$retornoDados['escalas_controle'] = Array(
						'tabela' => $hosts_escalas_controle_proc,
						'dateInicio' => $dateInicio,
						'dateFim' => $dateFim,
					);
				}
				
				// ===== Retornar dados.
				
				return Array(
					'status' => 'OK',
					'data' => $retornoDados,
				);
			} else {
				return Array(
					'status' => 'MANDATORY_FIELDS_NOT_INFORMED',
				);
			}
		break;
		case 'confirmacao':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: usuarioID, escolha, mes e ano.
			
			if(isset($dados['usuarioID']) && isset($dados['escolha']) && isset($dados['mes']) && isset($dados['ano'])){
				// ===== Incluir bibliotecas padrões.
				
				gestor_incluir_biblioteca('formato');
				
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas'));
				
				$escala_ativacao = (existe($config['escala-ativacao']) ? true : false);
				$msg_escala_suspenso = (existe($config['msg-escala-suspenso']) ? $config['msg-escala-suspenso'] : '');
				
				// ===== Caso o agendamento estiver inativo, retornar mensagem de inatividade.
				
				if(!$escala_ativacao){
					return Array(
						'status' => 'ESCALAMENTO_INATIVO',
						'error-msg' => $msg_escala_suspenso,
					);
				}
				
				// ===== Tratar os dados enviados.
				
				$id_hosts_usuarios = banco_escape_field($dados['usuarioID']);
				$mes = $dados['mes'];
				$ano = $dados['ano'];
				$escolha = $dados['escolha'];
				
				// ===== Definição do tempo do dia de agora.
				
				if($modulo['dataDebugAtivo']){
					$hoje = strtotime($modulo['dataDebug']);
				} else {
					$hoje = time();
				}
				
				// ===== Passar o mês e o ano para inteiro.
				
				$mes = (int)$mes;
				$ano = (int)$ano;
				
				// ===== Definir a data do início e fim da confirmação.
				
				$mesAtualFormatado = ($mes < 10 ? '0':'') . $mes;
				
				$diasInicioConfirmacao = (existe($config['dias-inicio-confirmacao']) ? $config['dias-inicio-confirmacao'] : '');
				$diasFimConfirmacao = (existe($config['dias-fim-confirmacao']) ? $config['dias-fim-confirmacao'] : '');
				
				$data_confirmacao_inicio = plataforma_cliente_plugin_data_dias_antes($mes,$ano,$diasInicioConfirmacao,'01/'. $mesAtualFormatado . '/' . $ano);
				$data_confirmacao_fim = plataforma_cliente_plugin_data_dias_antes($mes,$ano,$diasFimConfirmacao,'01/'. $mesAtualFormatado . '/' . $ano);
				
				// ===== Definir a data inicial e final do mês.
				
				$data_inicial_mes = date('d/m/Y', strtotime(str_replace('/', '-', $data_confirmacao_fim) . ' +1 day'));
				
				$mesInicio = $mes;
				$anoInicio = $ano;
				
				if($mesInicio < 10){
					$mesInicio = '0' . $mesInicio;
				}
				
				$prmeiroDiaMes = date($anoInicio.'-'.$mesInicio.'-01');
				$data_final_mes = date('t',strtotime($prmeiroDiaMes)) . '/' . $mesInicio . '/' . $anoInicio;
				
				// ===== Definir se é fase de confirmação.
				
				$faseConfirmacao = false;
				
				if(
					$hoje >= strtotime(str_replace('/', '-', $data_confirmacao_inicio)) && 
					$hoje < strtotime(str_replace('/', '-', $data_inicial_mes)) - 1
				){
					$faseConfirmacao = true;
				}
				
				// ===== Verificar se é fase de confirmação. Caso não seja, retornar mensagem de erro alertando e finalizar.
				
				if(!$faseConfirmacao){
					$msgConfirmacaoForaDoPrazo = (existe($config['msg-confirmacao-fora-do-prazo']) ? $config['msg-confirmacao-fora-do-prazo'] : '');
					
					return Array(
						'status' => 'CONFIRMACAO_FORA_DO_PRAZO',
						'error-msg' => $msgConfirmacaoForaDoPrazo,
					);
				}
				
				// ===== Pegar a quantidade de vagas totais do mês.
		
				$dateInicio = formato_dado_para('date',$data_inicial_mes);
				$dateFim = formato_dado_para('date',$data_final_mes);
				
				$hosts_escalas_controle = banco_select(Array(
					'tabela' => 'hosts_escalas_controle',
					'campos' => Array(
						'id_hosts_escalas_controle',
						'data',
						'total',
					),
					'extra' => 
						"WHERE data >= '".$dateInicio."'"
						." AND data <= '".$dateFim."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Pegar os dados da escala do mês / ano requisitados.
				
				$hosts_escalas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_escalas',
					'campos' => Array(
						'id_hosts_escalas',
					),
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND mes='".$mes."'"
						." AND ano='".$ano."'"
				));
				
				if($hosts_escalas){
					$id_hosts_escalas = $hosts_escalas['id_hosts_escalas'];
					
					// ===== Pegar as datas da escala do mês / ano.
					
					$hosts_escalas_datas = banco_select(Array(
						'tabela' => 'hosts_escalas_datas',
						'campos' => Array(
							'id_hosts_escalas_datas',
							'data',
						),
						'extra' => 
							"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
							." AND id_hosts='".$id_hosts."'"
							." AND status='qualificado'"
					));
					
					// ===== Variáveis de controle.
					
					$escalaControleAtualizada = false;
					$diasEscalados = Array();
					
					// ===== Verificar a escolha e aplicar as modificações necessárias.
					
					switch($escolha){
						case 'confirmar':
							// ===== Reservar as vagas para cada data atualizando 'hosts_escalas_controle'.
							
							if($hosts_escalas_datas)
							foreach($hosts_escalas_datas as $escala_data){
								$dataTipoDate = $escala_data['data'];
								$diaDaData = date('j',strtotime($dataTipoDate));
								
								$diasEscalados[] = $diaDaData;
								
								$dataEscalaControleEncontrada = false;
								
								if($hosts_escalas_controle)
								foreach($hosts_escalas_controle as $hec){
									if($dataTipoDate == $hec['data']){
										$dataEscalaControleEncontrada = true;
										
										banco_update_campo('total','total+1',true);
										
										banco_update_executar('hosts_escalas_controle',"WHERE id_hosts_escalas_controle='".$hec['id_hosts_escalas_controle']."' AND id_hosts='".$id_hosts."'");
										break;
									}
								}
								
								if(!$dataEscalaControleEncontrada){
									banco_insert_name_campo('id_hosts',$id_hosts);
									banco_insert_name_campo('data',$dataTipoDate);
									banco_insert_name_campo('total','1',true);
									banco_insert_name_campo('status','confirmacao');
									
									banco_insert_name
									(
										banco_insert_name_campos(),
										"hosts_escalas_controle"
									);
								}
								
								$escalaControleAtualizada = true;
							}
							
							// ===== Modificar o status para 'confirmado' das datas e da escala.
							
							if($hosts_escalas_datas)
							foreach($hosts_escalas_datas as $escala_data){
								$id_hosts_escalas_datas = $escala_data['id_hosts_escalas_datas'];
								
								banco_update_campo('status','confirmado');
								
								banco_update_executar('hosts_escalas_datas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas='".$id_hosts_escalas."' AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'");
							}
							
							banco_update_campo('status','confirmado');
							banco_update_campo('data_confirmacao','NOW()',true);
							banco_update_campo('data_modificacao','NOW()',true);
							banco_update_campo('versao','versao=versao+1',true);
							
							banco_update_executar('hosts_escalas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas='".$id_hosts_escalas."'");
							
							// ===== Montar o calendário das datas da escala.
							
							$calendario = plataforma_cliente_plugin_montar_calendario($mes,$ano,$diasEscalados);
						break;
						case 'cancelar':
							// ===== Modificar o status para 'cancelado' das datas e da escala.
							
							if($hosts_escalas_datas)
							foreach($hosts_escalas_datas as $escala_data){
								$id_hosts_escalas_datas = $escala_data['id_hosts_escalas_datas'];
								
								banco_update_campo('status','cancelado');
								
								banco_update_executar('hosts_escalas_datas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas='".$id_hosts_escalas."' AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'");
							}
							
							banco_update_campo('status','cancelado');
							banco_update_campo('data_modificacao','NOW()',true);
							banco_update_campo('versao','versao=versao+1',true);
							
							banco_update_executar('hosts_escalas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas='".$id_hosts_escalas."'");
						break;
					}
					
					// ===== Pegar dados do usuário.
					
					$hosts_usuarios = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios',
						'campos' => Array(
							'nome',
							'email',
						),
						'extra' => 
							"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Formatar dados do email.
					
					switch($escolha){
						case 'confirmar':
							$escalamentoAssunto = (existe($config['escalamento-confirmar-assunto']) ? $config['escalamento-confirmar-assunto'] : '');
							$escalamentoMensagem = (existe($config['escalamento-confirmar-mensagem']) ? $config['escalamento-confirmar-mensagem'] : '');
							$msgConfirmacaoEscalamento = (existe($config['msg-conclusao-confirmar-escalamento']) ? $config['msg-conclusao-confirmar-escalamento'] : '');
						break;
						case 'cancelar':
							$escalamentoAssunto = (existe($config['escalamento-cancelar-assunto']) ? $config['escalamento-cancelar-assunto'] : '');
							$escalamentoMensagem = (existe($config['escalamento-cancelar-mensagem']) ? $config['escalamento-cancelar-mensagem'] : '');
							$msgConfirmacaoEscalamento = (existe($config['msg-conclusao-cancelar-escalamento']) ? $config['msg-conclusao-cancelar-escalamento'] : '');
						break;
					}
					
					$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
					
					$email = $hosts_usuarios['email'];
					$nome = $hosts_usuarios['nome'];
					
					$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_escalas,6);
					
					// ===== Formatar mensagem do email.
			
					gestor_incluir_biblioteca('host');
					
					$mesEAno = formato_zero_a_esquerda($mes,2) . '/' . $ano;
					
					switch($escolha){
						case 'confirmar':
							$escalamentoAssunto = modelo_var_troca_tudo($escalamentoAssunto,"#codigo#",$codigo);
							$escalamentoAssunto = modelo_var_troca_tudo($escalamentoAssunto,"#mes#",$mesEAno);
							
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#codigo#",$codigo);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#titulo#",$tituloEstabelecimento);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#mes#",$mesEAno);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#calendario#",$calendario);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#url-escalamento#",'<a target="escalamento" href="'.host_url(Array('opcao'=>'full')).'escalas/" style="overflow-wrap: break-word;">'.host_url(Array('opcao'=>'full')).'escalas/</a>');
						break;
						case 'cancelar':
							$escalamentoAssunto = modelo_var_troca_tudo($escalamentoAssunto,"#codigo#",$codigo);
							$escalamentoAssunto = modelo_var_troca_tudo($escalamentoAssunto,"#mes#",$mesEAno);
							
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#codigo#",$codigo);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#titulo#",$tituloEstabelecimento);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#mes#",$mesEAno);
							$escalamentoMensagem = modelo_var_troca_tudo($escalamentoMensagem,"#url-escalamento#",'<a target="escalamento" href="'.host_url(Array('opcao'=>'full')).'escalas/" style="overflow-wrap: break-word;">'.host_url(Array('opcao'=>'full')).'escalas/</a>');
						break;
					}
					
					// ===== Enviar email com a notificação da confirmação/cancelamento da escala.
					
					gestor_incluir_biblioteca(Array('comunicacao','host'));
					
					if(comunicacao_email(Array(
						'hostPersonalizacao' => true,
						'destinatarios' => Array(
							Array(
								'email' => $email,
								'nome' => $nome,
							),
						),
						'mensagem' => Array(
							'assunto' => $escalamentoAssunto,
							'html' => $escalamentoMensagem,
							'htmlAssinaturaAutomatica' => true,
							'htmlVariaveis' => Array(
								Array(
									'variavel' => '[[url]]',
									'valor' => host_url(Array('opcao'=>'full')),
								),
							),
						),
					))){
						
					}
				}
				
				// ===== Tratar dados de retorno.
				
				$hosts_escalas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_escalas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				unset($hosts_escalas['id_hosts']);
				
				$hosts_escalas_datas = banco_select(Array(
					'tabela' => 'hosts_escalas_datas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$hosts_escalas_datas_proc = Array();
				
				if($hosts_escalas_datas)
				foreach($hosts_escalas_datas as $hosts_escala_data){
					unset($hosts_escala_data['id_hosts']);
					
					$hosts_escalas_datas_proc[] = $hosts_escala_data;
				}
				
				$retornoDados = Array(
					'escalas' => $hosts_escalas,
					'escalas_datas' => $hosts_escalas_datas_proc,
					'alerta' => $msgConfirmacaoEscalamento,
				);
				
				// ===== Caso tenha sido alterada o 'hosts_escalas_controle', enviar os dados atualizados.
				
				if($escalaControleAtualizada){
					$hosts_escalas_controle = banco_select(Array(
						'tabela' => 'hosts_escalas_controle',
						'campos' => '*',
						'extra' => 
							"WHERE data >= '".$dateInicio."'"
							." AND data <= '".$dateFim."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					if($hosts_escalas_controle)
					foreach($hosts_escalas_controle as $escala_controle){
						unset($escala_controle['id_hosts']);
						
						$hosts_escalas_controle_proc[] = $escala_controle;
					}
				}
				
				if(isset($hosts_escalas_controle_proc)){
					$retornoDados['escalas_controle'] = Array(
						'tabela' => $hosts_escalas_controle_proc,
						'dateInicio' => $dateInicio,
						'dateFim' => $dateFim,
					);
				}
				
				// ===== Retornar dados.
				
				return Array(
					'status' => 'OK',
					'data' => $retornoDados,
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
		case 'escalas': $dados = plataforma_cliente_plugin_escalas(); break;
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