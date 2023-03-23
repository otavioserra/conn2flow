<?php

// ===== Cron responsável por tratar dados do escalas.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'cron-escalas';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'maxEmailsPerCycle' => 50,
	'dataFormatadaDebugAtivo' => true,
	'dataFormatadaDebug' => '22/04/2023',
);

// =========================== Funções Auxiliares

function cron_montar_calendario($mes,$ano,$diasComEventos = Array(),$diasSemEventos = Array()){
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

function cron_data_dias_antes($mes = 0,$ano = 0, $diasPeriodo = 0, $dataInicial = NULL){
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

// =========================== Funções do Cron

function cron_escalas_sorteio(){
	global $_GESTOR;
	global $_CRON;
	
	// ===== Incluir bibliotecas necessárias.
	
	gestor_incluir_biblioteca('formato');
	
	// ===== Módulo variáveis.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificadores dos Hosts.
	
	$hostsIDs = $_GESTOR['pluginHostsIDs'];
	
	// ===== Variáveis de controle valores iniciais.
	
	if($modulo['dataFormatadaDebugAtivo']){
		$hojeDataFormatada = $modulo['dataFormatadaDebug'];
	} else {
		$hojeDataFormatada = date('d/m/Y');
	}
	
	$mesAtual = (int)date('n');
	$anoAtual = (int)date('Y');
	
	// ===== O mês alvo é sempre um mês a frente do mês atual.
	
	$mesAlvo = $mesAtual + 1;
	$anoAlvo = $anoAtual;
	
	if($mesAlvo > 12){
		$mesAlvo = 1;
		$anoAlvo += 1;
	}
	
	$mesAlvoFormatado = formato_zero_a_esquerda($mesAlvo,2);
	
	// ===== Varrer todos hosts.
	
	if($hostsIDs)
	foreach($hostsIDs as $id_hosts){
		// ===== Pegar os dados de configuração do host atual.
		
		gestor_incluir_biblioteca('configuracao');
		
		$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas', 'id_hosts' => $id_hosts));
		
		$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
		$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
		$datas_extras_dias_semana_maximo_vagas_arr = (existe($config['datas-extras-dias-semana-maximo-vagas']) ? explode(',',$config['datas-extras-dias-semana-maximo-vagas']) : Array());
		if(existe($config['datas-extras-disponiveis'])) $datas_extras_disponiveis = (existe($config['datas-extras-disponiveis-valores']) ? explode('|',$config['datas-extras-disponiveis-valores']) : Array()); else $datas_extras_disponiveis = Array();
		$diasInicioConfirmacao = (existe($config['dias-inicio-confirmacao']) ? $config['dias-inicio-confirmacao'] : '');
		$diasFimConfirmacao = (existe($config['dias-fim-confirmacao']) ? $config['dias-fim-confirmacao'] : '');
		
		// ===== Definir a data do início da confirmação.
		
		$data_confirmacao_inicio = cron_data_dias_antes($mesAlvo,$anoAlvo,$diasInicioConfirmacao,'01/'. $mesAlvoFormatado . '/' . $anoAlvo);
		$data_confirmacao_fim = cron_data_dias_antes($mesAlvo,$anoAlvo,$diasFimConfirmacao,'01/'. $mesAlvoFormatado . '/' . $anoAlvo);
		
		// ===== Verificar se é dia de sorteio. Se for dar prosseguimento, senão continuar o loop.
		
		if($data_confirmacao_inicio != $hojeDataFormatada){
			continue;
		}
		
		// ===== Definir a data de início e final do mês alvo.
		
		$data_inicial_mes = $anoAlvo.'-'.$mesAlvoFormatado.'-01';
		$data_final_mes = $anoAlvo.'-'.$mesAlvoFormatado.'-'.date('t',strtotime($data_inicial_mes));
		
		// ===== Verificar se o escalas cron existe no banco de dados. Senão criar a referência controladora do sorteio do mês / ano atual.
		
		$hosts_escalas_cron = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_escalas_cron',
			'campos' => Array(
				'status',
			),
			'extra' => 
				"WHERE mes='".$mesAlvo."'"
				." AND ano='".$anoAlvo."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		// ===== Criar 'hosts_escalas_cron' caso não exista.
		
		if(!$hosts_escalas_cron){
			banco_insert_name_campo('id_hosts',$id_hosts);
			banco_insert_name_campo('mes',$mesAlvo);
			banco_insert_name_campo('ano',$anoAlvo);
			banco_insert_name_campo('status','novo');
			banco_insert_name_campo('versao','1',true);
			banco_insert_name_campo('data_criacao','NOW()',true);
			banco_insert_name_campo('data_modificacao','NOW()',true);
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"hosts_escalas_cron"
			);
			$statusProcessoSorteio = 'novo';
		} else {
			$statusProcessoSorteio = ($hosts_escalas_cron['status'] ? $hosts_escalas_cron['status'] : 'novo');
		}
		
		// ===== Variáveis de contagem e controle.
		
		$escalaDatas = Array();
		$sorteadosEmPeloMenosUmaData = Array();
		$novaQualificacao = false;
		$enviarEmails = false;
		$outroHost = false;
		
		// ===== Verificar o status do processo do sorteio. Se for 'novo', fazer uma nova tentativa de qualificação, senão continuar loop e ir para outro host.
		
		switch($statusProcessoSorteio){
			case 'novo':
				// ===== Pegar as escalas no banco de dados para mês / ano desejado.
				
				$hosts_escalas = banco_select(Array(
					'tabela' => 'hosts_escalas',
					'campos' => Array(
						'id_hosts_usuarios',
						'id_hosts_escalas',
					),
					'extra' => 
						"WHERE mes='".$mesAlvo."'"
						." AND ano='".$anoAlvo."'"
						." AND id_hosts='".$id_hosts."'"
						." AND status='novo'"
				));
				
				// ===== Verificar as escalas datas no banco de dados do mês / ano desejado.
				
				$hosts_escalas_datas = banco_select(Array(
					'tabela' => 'hosts_escalas_datas',
					'campos' => Array(
						'data',
						'id_hosts_escalas',
						'id_hosts_escalas_datas',
					),
					'extra' => 
						"WHERE data>='".$data_inicial_mes."'"
						." AND data<='".$data_final_mes."'"
						." AND id_hosts='".$id_hosts."'"
						." AND selecionada IS NOT NULL"
				));
				
				// ===== Definir o status atual do processo de sorteio caso exista escalamento 'novo' não processado.
				
				if($hosts_escalas){
					foreach($hosts_escalas as $hosts_escala){
						if($hosts_escalas_datas)
						foreach($hosts_escalas_datas as $hosts_escala_data){
							if($hosts_escala['id_hosts_escalas'] == $hosts_escala_data['id_hosts_escalas']){
								if(!isset($escalaDatas[$hosts_escala_data['data']])){
									$escalaDatas[$hosts_escala_data['data']] = Array(
										'total' => 1,
										'status' => 'novo',
										'ids' => Array(
											Array(
												'id_hosts_usuarios' => $hosts_escala['id_hosts_usuarios'],
												'id_hosts_escalas' => $hosts_escala['id_hosts_escalas'],
												'id_hosts_escalas_datas' => $hosts_escala_data['id_hosts_escalas_datas'],
											)
										),
									);
								} else {
									$escalaDatas[$hosts_escala_data['data']]['total'] += 1;
									$escalaDatas[$hosts_escala_data['data']]['ids'][] = Array(
										'id_hosts_usuarios' => $hosts_escala['id_hosts_usuarios'],
										'id_hosts_escalas' => $hosts_escala['id_hosts_escalas'],
										'id_hosts_escalas_datas' => $hosts_escala_data['id_hosts_escalas_datas'],
									);
								}
							}
						}
					}
					
					$statusProcessoSorteio = 'qualificar';
					$novaQualificacao = true;
					$enviarEmails = true;
				} else {
					$statusProcessoSorteio = 'sem-escalas';
					$outroHost = true;
				}
				
				// ===== Atualizar o status da tabela cron que gerencia o sorteio.
				
				banco_update_campo('status',$statusProcessoSorteio);
				banco_update_campo('versao','versao+1',true);
				banco_update_campo('data_modificacao','NOW()',true);
				
				banco_update_executar('hosts_escalas_cron',
					"WHERE mes='".$mesAlvo."'"
					." AND ano='".$anoAlvo."'"
					." AND id_hosts='".$id_hosts."'"
				);
			break;
			case 'enviar-emails':
				$enviarEmails = true;
			break;
			case 'confirmacoes-enviadas':
			case 'sem-escalas':
				$outroHost = true;
			break;
		}
		
		// ===== Continuar loop em outro host.
		
		if($outroHost){
			continue;
		}
		
		// ===== Sortear ou qualificar escalas para confirmação.
		
		if($novaQualificacao){
			if($escalaDatas)
			foreach($escalaDatas as $data => $escalaDados){
				// ===== Variáveis de controle do sorteio.
				
				$sortear = false;
				$bilhetes = Array();
				
				// ===== Dados da escala.
				
				$totalescalas = $escalaDados['total'];
				$dataFormatada = formato_dado_para('data',$data);
				
				// ===== Definir o total de vagas de escala para a data atual.
				
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
						if($dia_semana == strtolower(date('D',strtotime($data)))){
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
				
				// ===== Verificar se precisa ou não de sorteio baseado no máximo de vagas na escala para cada data.
				
				if($totalescalas > $totalVagas){
					$sortear = true;
				}
				
				// ===== Sortear caso o total de escalas for maior que o máximo de vagas. Senão qualificar todas as escalas diretamente.
				
				if($sortear){
					// ===== Preparação de bilhetes com aplicação de pesos.
					
					foreach($escalaDados['ids'] as $escala){
						$bilhete = Array(
							'id_hosts_escalas_datas' => $escala['id_hosts_escalas_datas'],
							'id_hosts_escalas' => $escala['id_hosts_escalas'],
							'id_hosts_usuarios' => $escala['id_hosts_usuarios'],
						);
						
						$id_hosts_usuarios = $escala['id_hosts_usuarios'];
						
						// ===== Pegar o peso do usuário.
						
						if(!isset($hosts_escalas_pesos[$id_hosts_usuarios])){
							$hosts_escalas_pesos_banco = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_escalas_pesos',
								'campos' => Array(
									'peso',
								),
								'extra' => 
									"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
									." AND id_hosts='".$id_hosts."'"
							));
							
							if($hosts_escalas_pesos_banco){
								$hosts_escalas_pesos[$id_hosts_usuarios] = Array(
									'peso' => (int)$hosts_escalas_pesos_banco['peso'],
									'banco' => true,
								);
							} else {
								$hosts_escalas_pesos[$id_hosts_usuarios] = Array(
									'peso' => 0,
								);
							}
						}
						
						// ===== Montar a quantidade de bilhetes que um usuário tem baseado no seu peso.
						
						$peso = $hosts_escalas_pesos[$id_hosts_usuarios]['peso'];
						if($peso > 0){
							for($i=0;$i<$peso+1;$i++){
								$bilhetes[] = $bilhete;
							}
						} else {
							$bilhetes[] = $bilhete;
						}
					}
					
					// ===== Sortear os bilhetes.
					
					$sorteados = Array();
					$bilhetes_aux = $bilhetes;
					$vagas_sorteadas = 0;
					
					while($vagas_sorteadas < $totalVagas){
						$na = count($bilhetes_aux) - 1;
						$indice = rand(0,$na);
						
						$id_hosts_escalas = $bilhetes_aux[$indice]['id_hosts_escalas'];
						$sorteados[] = $bilhetes_aux[$indice];
						
						// ===== Marcar o usuário como sorteado em pelo menos uma data.
						
						$sorteadosEmPeloMenosUmaData[$id_hosts_escalas] = true;
						
						$vagas_sorteadas += 1;
						
						$bilhetes_aux2 = Array();
						foreach($bilhetes_aux as $bilhete){
							if($bilhete['id_hosts_escalas'] != $id_hosts_escalas){
								$bilhetes_aux2[] = $bilhete;
							}
						}
						
						$bilhetes_aux = $bilhetes_aux2;
						
						if(count($bilhetes_aux) == 0){
							break;
						}
					}
					
					// ===== Qualificar escalas sorteados para confirmação.
					
					if(count($sorteados) > 0)
					foreach($sorteados as $sorteado){
						banco_update_campo('status','qualificado');
						
						banco_update_executar('hosts_escalas_datas',"WHERE id_hosts_escalas_datas='".$sorteado['id_hosts_escalas_datas']."'");
					}
					
					// ===== Escalas NÃO sorteadas atualizar pesos.
					
					if(count($sorteados) > 0){
						if(count($bilhetes) > 0)
						foreach($bilhetes as $bilhete){
							$id_hosts_usuarios = $bilhete['id_hosts_usuarios'];
							$id_hosts_escalas = $bilhete['id_hosts_escalas'];
							
							// ===== Verificar se a escala foi sorteada ou não.
							
							$sorteadoFlag = false;
							foreach($sorteados as $sorteado){
								if($id_hosts_escalas == $sorteado['id_hosts_escalas']){
									$sorteadoFlag = true;
									break;
								}
							}
						
							// ===== Aumentar o peso dos usuários não sorteados em cada data afim de aumentar em 100% de chance a próxima vez que passará por um sorteio. Para os sorteados, diminuir o peso para ter menos chance no próximo sorteio.
							
							if(!isset($hosts_escalas_pesos[$id_hosts_usuarios])){
								$hosts_escalas_pesos[$id_hosts_usuarios]['peso'] = 0;
							}
							
							if(!$sorteadoFlag){
								$hosts_escalas_pesos[$id_hosts_usuarios]['peso'] += 1;
							} else {
								$hosts_escalas_pesos[$id_hosts_usuarios]['peso'] -= 1;
								
								if($hosts_escalas_pesos[$id_hosts_usuarios]['peso'] < 0){
									$hosts_escalas_pesos[$id_hosts_usuarios]['peso'] = 0;
								}
							}
						}
					}
				} else {
					// ===== Escalas qualificar todos para confirmação.
					
					foreach($escalaDados['ids'] as $escala){
						if(
							isset($escala['id_hosts_escalas']) &&
							isset($escala['id_hosts_escalas_datas'])
						){
							$id_hosts_escalas = $escala['id_hosts_escalas'];
							$id_hosts_escalas_datas = $escala['id_hosts_escalas_datas'];
							
							// ===== Marcar o usuário como sorteado em pelo menos uma data.
							
							$sorteadosEmPeloMenosUmaData[$id_hosts_escalas] = true;
							
							// ===== Alterar estado no banco de dados para qualificado.
							
							banco_update_campo('status','qualificado');
							
							banco_update_executar('hosts_escalas_datas',"WHERE id_hosts_escalas_datas='".$id_hosts_escalas_datas."'");
						}
						
					}
				}
			}
			
			// ===== Atualizar ou criar novo registro no banco de dados com os pesos atualizados de cada usuário.
			
			if(isset($hosts_escalas_pesos))
			foreach($hosts_escalas_pesos as $id_hosts_usuarios => $escala_peso){
				if(isset($escala_peso['banco'])){
					banco_update_campo('peso',$escala_peso['peso'],true);
					
					banco_update_executar('hosts_escalas_pesos',"WHERE id_hosts_usuarios='".$id_hosts_usuarios."' AND id_hosts='".$id_hosts."'");
				} else {
					banco_insert_name_campo('id_hosts',$id_hosts);
					banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
					banco_insert_name_campo('peso',$escala_peso['peso'],true);
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_escalas_pesos"
					);
				}				
			}
			
			// ===== Atualizar o estado da escala de todos os usuários que foram qualificados.
			
			if(count($sorteadosEmPeloMenosUmaData) > 0){
				foreach($sorteadosEmPeloMenosUmaData as $id_hosts_escalas => $valor){
					banco_update_campo('status','qualificado');
					banco_update_campo('versao','versao+1',true);
					banco_update_campo('data_modificacao','NOW()',true);
					
					banco_update_executar('hosts_escalas',"WHERE id_hosts_escalas='".$id_hosts_escalas."' AND id_hosts='".$id_hosts."'");
				}
			}
			
			// ===== Atualizar processo para enviar os emails de confirmação.
			
			banco_update_campo('status','enviar-emails');
			banco_update_campo('versao','versao+1',true);
			banco_update_campo('data_modificacao','NOW()',true);
			
			banco_update_executar('hosts_escalas_cron',
				"WHERE mes='".$mesAlvo."'"
				." AND ano='".$anoAlvo."'"
				." AND id_hosts='".$id_hosts."'"
			);
		}
		
		// ===== Enviar email de confirmação das escalas para cada usuário.
		
		if($enviarEmails){
			// ===== Pegar os dados das escalas qualificadas no banco de dados.
			
			$hosts_escalas = banco_select(Array(
				'tabela' => 'hosts_escalas',
				'campos' => Array(
					'id_hosts_usuarios',
					'id_hosts_escalas',
				),
				'extra' => 
					"WHERE mes='".$mesAlvo."'"
					." AND ano='".$anoAlvo."'"
					." AND id_hosts='".$id_hosts."'"
					." AND status='qualificado'"
			));
			
			// ===== Verificar as escalas datas no banco de dados do mês / ano desejado.
			
			$hosts_escalas_datas = banco_select(Array(
				'tabela' => 'hosts_escalas_datas',
				'campos' => Array(
					'data',
					'id_hosts_escalas',
					'id_hosts_escalas_datas',
				),
				'extra' => 
					"WHERE data>='".$data_inicial_mes."'"
					." AND data<='".$data_final_mes."'"
					." AND id_hosts='".$id_hosts."'"
					." AND selecionada IS NOT NULL"
					." AND status='qualificado'"
			));
			
			// ===== Caso exista, enviar emails para cada usuário com a opção de confirmar ou cancelar.
			
			if($hosts_escalas){
				
				// ===== Pegar a mensagem e assunto dos emails, bem como o título do estabelecimento.
				
				$emailConfirmacaoAssunto = (existe($config['email-confirmacao-assunto']) ? $config['email-confirmacao-assunto'] : '');
				$emailConfirmacaoMensagem = (existe($config['email-confirmacao-mensagem']) ? $config['email-confirmacao-mensagem'] : '');
				$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
				
				// ===== Pegar url completa do host, bem como incluir as bibliotecas necessárias.
				
				gestor_incluir_biblioteca('host');
				gestor_incluir_biblioteca('comunicacao');
				gestor_incluir_biblioteca('modelo');
				
				$hostUrl = host_url(Array('opcao'=>'full','id_hosts' => $id_hosts));
				
				// ===== Varrer todos os escalas.
				
				$emails_enviados = 0;
				foreach($hosts_escalas as $escala){
					
					$id_hosts_usuarios = $escala['id_hosts_usuarios'];
					$id_hosts_escalas = $escala['id_hosts_escalas'];
					
					// ===== Buscar todas os dias escalados afim de montar o calendário.
					
					$diasEscalados = Array();
					
					if($hosts_escalas_datas)
					foreach($hosts_escalas_datas as $hosts_escala_data){
						if($id_hosts_escalas == $hosts_escala_data['id_hosts_escalas']){
							$diaDaData = date('j',strtotime($hosts_escala_data['data']));
							$diasEscalados[] = $diaDaData;
						}
					}
					
					// ===== Montar o calendário das datas da escala.
					
					$calendario = cron_montar_calendario($mesAlvo,$anoAlvo,$diasEscalados);
					
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
					
					$email = $hosts_usuarios['email'];
					$nome = $hosts_usuarios['nome'];
					
					$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_escalas,6);
					
					$mesEAno = formato_zero_a_esquerda($mesAlvo,2) . '/' . $anoAlvo;
					
					// ===== Formatar mensagem do email.
					
					$emailConfirmacaoAssuntoAux = $emailConfirmacaoAssunto;
					$emailConfirmacaoMensagemAux = $emailConfirmacaoMensagem;
					
					$emailConfirmacaoAssuntoAux = modelo_var_troca_tudo($emailConfirmacaoAssuntoAux,"#codigo#",$codigo);
					$emailConfirmacaoAssuntoAux = modelo_var_troca_tudo($emailConfirmacaoAssuntoAux,"#mes#",$mesEAno);
					
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#calendario#",$calendario);
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#mes#",$mesEAno);					
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#data1#",$data_confirmacao_inicio);						
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#data2#",$data_confirmacao_fim);
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#codigo#",$codigo);
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#titulo#",$tituloEstabelecimento);
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#url-escalamento#",'<a target="escalamento" href="'.host_url(Array('opcao'=>'full','id_hosts' => $id_hosts)).'escalas/" style="overflow-wrap: break-word;">'.host_url(Array('opcao'=>'full','id_hosts' => $id_hosts)).'escalas/</a>');
					
					// ===== Enviar email ao usuário solicitando a confirmação ou cancelamento da escala.
					
					if(comunicacao_email(Array(
						'hostPersonalizacao' => true,
						'id_hosts' => $id_hosts,
						'destinatarios' => Array(
							Array(
								'email' => $email,
								'nome' => $nome,
							),
						),
						'mensagem' => Array(
							'assunto' => $emailConfirmacaoAssuntoAux,
							'html' => $emailConfirmacaoMensagemAux,
							'htmlAssinaturaAutomatica' => true,
							'htmlVariaveis' => Array(
								Array(
									'variavel' => '[[url]]',
									'valor' => $hostUrl,
								),
							),
						),
					))){
						$status_escalamento = 'email-enviado';
					} else {
						$status_escalamento = 'email-nao-enviado';
					}
					
					// ===== Atualizar a escala no banco de dados.
					
					banco_update_campo('status',$status_escalamento);
					banco_update_campo('versao','versao+1',true);
					banco_update_campo('data_modificacao','NOW()',true);
					
					banco_update_executar('hosts_escalas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas='".$id_hosts_escalas."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					
					// ===== Controle de limite de envio de emails por requisição do cron. Caso chegue no limite, retornar a função e finalizar.
					
					$emails_enviados++;
					
					if($emails_enviados >= $modulo['maxEmailsPerCycle']){
						return;
					}
				}
			}
		}
		
		// ===== Alterar o status do hosts_escalas_cron para 'confirmacoes-enviadas'.
		
		banco_update_campo('status','confirmacoes-enviadas');
		banco_update_campo('versao','versao+1',true);
		banco_update_campo('data_modificacao','NOW()',true);
		
		banco_update_executar('hosts_escalas_cron',
			"WHERE mes='".$mesAlvo."'"
			." AND ano='".$anoAlvo."'"
			." AND id_hosts='".$id_hosts."'"
		);
		
		// ===== Pegar os dados das escalas qualificadas no banco de dados.
		
		$hosts_escalas = banco_select(Array(
			'tabela' => 'hosts_escalas',
			'campos' => '*',
			'extra' => 
				"WHERE mes='".$mesAlvo."'"
				." AND ano='".$anoAlvo."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		if($hosts_escalas)
		foreach($hosts_escalas as $escala){
			unset($escala['id_hosts']);
			
			$hosts_escalas_proc[] = $escala;
		}
		
		// ===== Pegar os dados das escalas datas qualificadas no banco de dados.
		
		$hosts_escalas_datas = banco_select(Array(
			'tabela' => 'hosts_escalas_datas',
			'campos' => '*',
			'extra' => 
				"WHERE data>='".$data_inicial_mes."'"
				." AND data<='".$data_final_mes."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		if($hosts_escalas_datas)
		foreach($hosts_escalas_datas as $escala_data){
			unset($escala_data['id_hosts']);
			
			$hosts_escalas_datas_proc[] = $escala_data;
		}
		
		// ===== Incluir os dados no host de cada cliente.
		
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'cron-escalas',
			'plugin' => 'escalas',
			'id_hosts' => $id_hosts,
			'opcao' => 'atualizar',
			'dados' => Array(
				'escalas' => (isset($hosts_escalas_proc) ? $hosts_escalas_proc : Array()),
				'escalas_datas' => (isset($hosts_escalas_datas_proc) ? $hosts_escalas_datas_proc : Array()),
			),
		));
		
		// ===== Caso haja algum erro, incluir no log do cron.
		
		if(!$retorno['completed']){
			cron_log(
				'FUNCAO: cron-escalas[atualizar]'."\n".
				'ID-HOST: '.$id_hosts."\n".
				'ERROR-MSG: '."\n".
				$retorno['error-msg']
			);
		}
	}
}

function cron_escalas_expiracao_fase_confirmacao(){
	global $_GESTOR;
	
	// ===== Incluir bibliotecas necessárias.
	
	gestor_incluir_biblioteca('formato');
	
	// ===== Módulo variáveis.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificadores dos Hosts.
	
	$hostsIDs = $_GESTOR['pluginHostsIDs'];
	
	// ===== Variáveis de controle valores iniciais.
	
	if($modulo['dataFormatadaDebugAtivo']){
		$hojeDataFormatada = $modulo['dataFormatadaDebug'];
	} else {
		$hojeDataFormatada = date('d/m/Y');
	}
	
	$mesAtual = (int)date('n');
	$anoAtual = (int)date('Y');
	$mesAtualFormatado = date('m');
	
	// ===== Varrer todos hosts.
	
	if($hostsIDs)
	foreach($hostsIDs as $id_hosts){
		// ===== Variáveis de controle.
		
		$outroHost = false;
		$escalaControleAtualizada = true;
		
		// ===== Pegar os dados de configuração do host atual.
		
		gestor_incluir_biblioteca('configuracao');
		
		$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas', 'id_hosts' => $id_hosts));
		
		$diasFimConfirmacao = (existe($config['dias-fim-confirmacao']) ? $config['dias-fim-confirmacao'] : '');
		
		// ===== Definir a data do fim da confirmação.
		
		$data_confirmacao_fim = cron_data_dias_antes($mesAtual,$anoAtual,$diasFimConfirmacao,'01/'. $mesAtualFormatado . '/' . $anoAtual);
		
		$data_expiracao_confirmacao = date('d/m/Y', strtotime(str_replace('/', '-', $data_confirmacao_fim) . ' +1 day'));
		
		// ===== Definir a data de início e final do mês alvo.
		
		$data_inicial_mes = $anoAtual.'-'.$mesAtualFormatado.'-01';
		$data_final_mes = $anoAtual.'-'.$mesAtualFormatado.'-'.date('t',strtotime($data_inicial_mes));
		
		// ===== Verificar se hoje é o dia da expiração da confirmação. Caso positivo, expirar todas as escalas com status 'confirmacoes-enviadas'. E devolver vagas das datas confirmadas mas deselecionadas.
		
		if($data_expiracao_confirmacao == $hojeDataFormatada){
			$hosts_escalas_cron = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_escalas_cron',
				'campos' => Array(
					'status',
				),
				'extra' => 
					"WHERE mes='".$mesAtual."'"
					." AND ano='".$anoAtual."'"
					." AND id_hosts='".$id_hosts."'"
					." AND status='confirmacoes-enviadas'"
			));
			
			if($hosts_escalas_cron){
				// ===== Pegar os dados das escalas 'qualificadas' no banco de dados. Ou seja, escalas que foram sorteadas e não confirmadas.
				
				$hosts_escalas = banco_select(Array(
					'tabela' => 'hosts_escalas',
					'campos' => Array(
						'id_hosts_escalas',
					),
					'extra' => 
						"WHERE mes='".$mesAtual."'"
						." AND ano='".$anoAtual."'"
						." AND id_hosts='".$id_hosts."'"
						." AND (status='qualificado' OR status='email-enviado' OR status='email-nao-enviado')"
				));
				
				// ===== Verificar se foi encontrado.
				
				if($hosts_escalas){
					// ===== Baixar as escalas datas no banco de dados do mês / ano desejado.
					
					$hosts_escalas_datas = banco_select(Array(
						'tabela' => 'hosts_escalas_datas',
						'campos' => Array(
							'id_hosts_escalas',
							'id_hosts_escalas_datas',
						),
						'extra' => 
							"WHERE data>='".$data_inicial_mes."'"
							." AND data<='".$data_final_mes."'"
							." AND id_hosts='".$id_hosts."'"
							." AND status='qualificado'"
					));
					
					// ===== Varrer todas as escalas.
					
					foreach($hosts_escalas as $hosts_escala){
						
						// ===== Varrer todas as escalas datas e trocar o status para 'nao-confirmado'.
						
						if($hosts_escalas_datas){
							foreach($hosts_escalas_datas as $hosts_escala_data){
								if($hosts_escala['id_hosts_escalas'] == $hosts_escala_data['id_hosts_escalas']){
									banco_update_campo('status','nao-confirmado');
									banco_update_campo('selecionada','NULL',true);
									
									banco_update_executar('hosts_escalas_datas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas_datas='".$hosts_escala_data['id_hosts_escalas_datas']."'");
								}
							}
						}
						
						// ===== Trocar o status de todas as escalas para 'nao-confirmado'.
						
						banco_update_campo('status','nao-confirmado');
						banco_update_campo('versao','versao+1',true);
						banco_update_campo('data_modificacao','NOW()',true);
						
						banco_update_executar('hosts_escalas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas='".$hosts_escala['id_hosts_escalas']."'");
					}	
				}
				
				// ===== Pegar a quantidade de vagas totais do mês.
		
				$hosts_escalas_controle = banco_select(Array(
					'tabela' => 'hosts_escalas_controle',
					'campos' => Array(
						'id_hosts_escalas_controle',
						'data',
					),
					'extra' => 
						"WHERE data >= '".$data_inicial_mes."'"
						." AND data <= '".$data_final_mes."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Pegar os dados das escalas 'confirmadas' no banco de dados. Ou seja, escalas que foram sorteadas e confirmadas.
				
				$hosts_escalas = banco_select(Array(
					'tabela' => 'hosts_escalas',
					'campos' => Array(
						'id_hosts_escalas',
					),
					'extra' => 
						"WHERE mes='".$mesAtual."'"
						." AND ano='".$anoAtual."'"
						." AND id_hosts='".$id_hosts."'"
						." AND status='confirmado'"
				));
				
				// ===== Verificar se foi encontrado.
				
				if($hosts_escalas){
					// ===== Expirar as escalas datas no banco de dados do mês / ano desejado não selecionadas. Ou seja, as datas que foram confirmadas, mas o usuário deselecionou as mesmas em algum momento do período de confirmação.
					
					$hosts_escalas_datas = banco_select(Array(
						'tabela' => 'hosts_escalas_datas',
						'campos' => Array(
							'data',
							'id_hosts_escalas',
							'id_hosts_escalas_datas',
						),
						'extra' => 
							"WHERE data>='".$data_inicial_mes."'"
							." AND data<='".$data_final_mes."'"
							." AND id_hosts='".$id_hosts."'"
							." AND selecionada IS NULL"
							." AND status='confirmado'"
					));
					
					// ===== Varrer todas as escalas.
					
					foreach($hosts_escalas as $hosts_escala){
						
						// ===== Varrer todas as escalas datas e trocar o status para 'expirado'.
						
						if($hosts_escalas_datas){
							foreach($hosts_escalas_datas as $hosts_escala_data){
								if($hosts_escala['id_hosts_escalas'] == $hosts_escala_data['id_hosts_escalas']){
									$dataTipoDate = $hosts_escala_data['data'];
									
									// ===== Devolver a vaga reservada para o sistema.
									
									if($hosts_escalas_controle)
									foreach($hosts_escalas_controle as $hec){
										if($dataTipoDate == $hec['data']){
											banco_update_campo('total','total-1',true);
											
											banco_update_executar('hosts_escalas_controle',"WHERE id_hosts_escalas_controle='".$hec['id_hosts_escalas_controle']."' AND id_hosts='".$id_hosts."'");
											
											$escalaControleAtualizada = true;
											
											break;
										}
									}
									
									// ===== Alterar o status para 'expirado' de cada data.
									
									banco_update_campo('status','expirado');
									
									banco_update_executar('hosts_escalas_datas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas_datas='".$hosts_escala_data['id_hosts_escalas_datas']."'");
								}
							}
						}
						
						// ===== Trocar o status de todas as escalas para 'utilizacao'.
						
						banco_update_campo('versao','versao+1',true);
						banco_update_campo('data_modificacao','NOW()',true);
						
						banco_update_executar('hosts_escalas',"WHERE id_hosts='".$id_hosts."' AND id_hosts_escalas='".$hosts_escala['id_hosts_escalas']."'");
					}	
				}
			} else {
				$outroHost = true;
			}
		} else {
			$outroHost = true;
		}
		
		// ===== Continuar loop em outro host.
		
		if($outroHost){
			continue;
		}
		
		// ===== Alterar o status do hosts_escalas_cron para 'finalizado'.
		
		banco_update_campo('status','finalizado');
		banco_update_campo('versao','versao+1',true);
		banco_update_campo('data_modificacao','NOW()',true);
		
		banco_update_executar('hosts_escalas_cron',
			"WHERE mes='".$mesAtual."'"
			." AND ano='".$anoAtual."'"
			." AND id_hosts='".$id_hosts."'"
		);
		
		// ===== Pegar os dados das escalas 'confirmado', 'nao-confirmado' no banco de dados.
		
		$hosts_escalas = banco_select(Array(
			'tabela' => 'hosts_escalas',
			'campos' => '*',
			'extra' => 
				"WHERE mes='".$mesAtual."'"
				." AND ano='".$anoAtual."'"
				." AND id_hosts='".$id_hosts."'"
				." AND (status='confirmado' OR status='nao-confirmado')"
		));
		
		if($hosts_escalas)
		foreach($hosts_escalas as $escala){
			unset($escala['id_hosts']);
			
			$hosts_escalas_proc[] = $escala;
		}
		
		// ===== Pegar os dados das escalas datas 'expirado', 'nao-confirmado' no banco de dados.
		
		$hosts_escalas_datas = banco_select(Array(
			'tabela' => 'hosts_escalas_datas',
			'campos' => '*',
			'extra' => 
				"WHERE data>='".$data_inicial_mes."'"
				." AND data<='".$data_final_mes."'"
				." AND id_hosts='".$id_hosts."'"
				." AND (status='expirado' OR status='nao-confirmado')"
		));
		
		if($hosts_escalas_datas)
		foreach($hosts_escalas_datas as $escala_data){
			unset($escala_data['id_hosts']);
			
			$hosts_escalas_datas_proc[] = $escala_data;
		}
		
		$enviarDados = Array(
			'escalas' => (isset($hosts_escalas_proc) ? $hosts_escalas_proc : Array()),
			'escalas_datas' => (isset($hosts_escalas_datas_proc) ? $hosts_escalas_datas_proc : Array()),
		);
		
		// ===== Caso tenha sido alterada o 'hosts_escalas_controle', enviar os dados atualizados.
		
		if($escalaControleAtualizada){
			$hosts_escalas_controle = banco_select(Array(
				'tabela' => 'hosts_escalas_controle',
				'campos' => '*',
				'extra' => 
					"WHERE data >= '".$data_inicial_mes."'"
					." AND data <= '".$data_final_mes."'"
					." AND id_hosts='".$id_hosts."'"
			));
			
			if($hosts_escalas_controle)
			foreach($hosts_escalas_controle as $escala_controle){
				unset($escala_controle['id_hosts']);
				
				$hosts_escalas_controle_proc[] = $escala_controle;
			}
		}
		
		if(isset($hosts_escalas_controle_proc)){
			$enviarDados['escalas_controle'] = Array(
				'tabela' => $hosts_escalas_controle_proc,
				'dateInicio' => $data_inicial_mes,
				'dateFim' => $data_final_mes,
			);
		}
		
		// ===== Incluir os dados no host de cada cliente.
		
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'cron-escalas',
			'plugin' => 'escalas',
			'id_hosts' => $id_hosts,
			'opcao' => 'atualizar',
			'dados' => $enviarDados,
		));
		
		// ===== Caso haja algum erro, incluir no log do cron.
		
		if(!$retorno['completed']){
			cron_log(
				'FUNCAO: cron-escalas[atualizar]'."\n".
				'ID-HOST: '.$id_hosts."\n".
				'ERROR-MSG: '."\n".
				$retorno['error-msg']
			);
		}
	}
}

// =========================== Funções de Acesso

function cron_escalas_start(){
	global $_GESTOR;
	
	/**********
		Parâmetros passados pelo módulo cron principal:
		
		$_GESTOR['pluginHostsIDs'] - Array - Todos os identificadores dos hosts que têm escalas habilitados
	**********/
	
	// ===== Pipeline de execução do cron.
	
	cron_escalas_sorteio();
	cron_escalas_expiracao_fase_confirmacao();

	// ===== Retorno padrão.
	
	return true;
}

// ===== Retornar plataforma.

return cron_escalas_start();

?>