<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'escalas-host';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.1.0',
);

// ===== Funções Auxiliares

function escalas_data_dias_antes($mes = 0,$ano = 0, $diasPeriodo = 0, $dataInicial = NULL){
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

function escalas_data_dias_depois($mes = 0,$ano = 0, $diasPeriodo = 0, $dataInicial = NULL){
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

function escalas_calendario($params = false){
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
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-escalas','conjunto' => true));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
	$datas_extras_dias_semana_maximo_vagas_arr = (existe($config['datas-extras-dias-semana-maximo-vagas']) ? explode(',',$config['datas-extras-dias-semana-maximo-vagas']) : Array());
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array()); else $datas_indisponiveis = Array();
	if(existe($config['datas-extras-disponiveis'])) $datas_extras_disponiveis = (existe($config['datas-extras-disponiveis-valores']) ? explode('|',$config['datas-extras-disponiveis-valores']) : Array()); else $datas_extras_disponiveis = Array();
	$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
	$calendario_ferias_ate = (existe($config['calendario-ferias-ate']) ? trim($config['calendario-ferias-ate']) : '20 January');
	$periodoLimiteAlteracao = (existe($config['periodo-limite-alteracao']) ? $config['periodo-limite-alteracao'] : '');
	
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
		." AND data <= ".$ultimoDiaMes
	);
	
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
	
	// ===== Data início e data final do calendário.
	
	$dataInicio = date('d/m/Y',$primeiro_dia);
	$dataFim = date('d/m/Y',$ultimo_dia);
	
	// ===== Definição das datas passadas ao widget no JS.
	
	$datasDesabilitadas = Array();
	$datasDestacadas = Array();
	
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
				$data = date('Y-m-d', $dia);
				$flag3 = false;
				
				// ===== Verificar se é fase de utilização, caso positivo verificar se há vagas disponíveis para datas não 'confirmado' ou 'vaga-residual'. Para estas, simplesmente permitir.
				
				if($faseUtilizacao){
					$dataUtilizavelNaoEncontrada = true;
					
					// ===== Caso haja pelo menos uma data permitida, verificar se a mesma já foi selecionada previamente. Caso positivo, apenas continuar. Senão, verificar se há vagas disponíveis para permitir.
					
					if(count($datasUtilizacao) > 0){
						foreach($datasUtilizacao as $dataUtilizacao){
							if($data == $dataUtilizacao){
								$dataUtilizavelNaoEncontrada = false;
								break;
							}
						}
					}
					
					// ===== Verificar se há vagas disponíveis para uma data específica, somente se a data não é utilizada.
					
					if($dataUtilizavelNaoEncontrada)
					if($escalas_controle){
						foreach($escalas_controle as $escalas_data){
							if($data == $escalas_data['data']){
								if($data_extra_permitida){
									if(count($datas_extras_dias_semana_maximo_vagas_arr) > 1){
										$dias_semana_maximo_vagas = $datas_extras_dias_semana_maximo_vagas_arr[$count_dias];
									} else {
										$dias_semana_maximo_vagas = $datas_extras_dias_semana_maximo_vagas_arr[0];
									}
								} else {
									if(count($dias_semana_maximo_vagas_arr) > 1){
										$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[$count_dias];
									} else {
										$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[0];
									}
								}
								
								if((int)$dias_semana_maximo_vagas < (int)$escalas_data['total']){
									$flag3 = true;
								}
								
								break;
							}
						}
					}
				}
				
				// ===== Não permitir selecionar datas do passado mais o periodoLimiteAlteracao.
				
				if($dia < $diaLimiteAlteracao){
					$flag3 = true;
				}
				
				// ===== Não permitir selecionar datas antes da inscrição.
				
				if($antesDaInscricao){
					$flag3 = true;
				}
				
				// ===== Não permitir selecionar datas de não qualificados na confirmação.
				
				if($naoQualificado){
					$flag3 = true;
				}
				
				// ===== Verificar se é fase de confirmação, caso positivo somente permitir modificar datas qualificadas.
				
				if($faseConfirmacao){
					if(count($datasQualificadas) > 0){
						$dataQualificadaEncontrada = false;
						foreach($datasQualificadas as $dataQualificada){
							if($data == $dataQualificada){
								$dataQualificadaEncontrada = true;
								break;
							}
						}
						
						if(!$dataQualificadaEncontrada){
							$flag3 = true;
						}
					}
				}
				
				// ===== Data permitida.
				
				if(!$flag3){
					$datasDestacadas[] = $dataFormatada;
					$datasDestacada = true;
				}
			}
		}
		
		if(!$datasDestacada){
			$datasDesabilitadas[] = $dataFormatada;
		}
		
		$dia += 86400;
	} while ($dia <= $ultimo_dia);
	
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
	$diaInicioInscricao = (existe($config['dia-inicio-inscricao']) ? $config['dia-inicio-inscricao'] : '');
	$mesInicioInscricao = (existe($config['mes-inicio-inscricao']) ? $config['mes-inicio-inscricao'] : '');
	$diasInicioConfirmacao = (existe($config['dias-inicio-confirmacao']) ? $config['dias-inicio-confirmacao'] : '');
	$diasFimConfirmacao = (existe($config['dias-fim-confirmacao']) ? $config['dias-fim-confirmacao'] : '');
	$periodoLimiteAlteracao = (existe($config['periodo-limite-alteracao']) ? $config['periodo-limite-alteracao'] : '');
	$calendarioInicio = (existe($config['calendario-inicio']) ? $config['calendario-inicio'] : '');
	$calendarioFim = (existe($config['calendario-fim']) ? $config['calendario-fim'] : '');
	
	// ===== Verificar se o sistema de escala está ativo ou não e tratar cada caso.
	
	if($escala_ativacao){
		// ===== Caso o usuário tenha ativado o botão salvar escala, tratar o salvamento da escala.
		
		if(isset($_REQUEST['escalar'])){
			if(
				isset($_REQUEST['mes']) && 
				isset($_REQUEST['ano'])
			){
				// ===== Pegar os valores enviados e escapar os dados.
				
				$mes = banco_escape_field($_REQUEST['mes']);
				$ano = banco_escape_field($_REQUEST['ano']);
				$datas = banco_escape_field(isset($_REQUEST['datas']) ? $_REQUEST['datas'] : '');
				
				// ===== API-Servidor para escalar.
				
				gestor_incluir_biblioteca('api-servidor');
				
				$retorno = api_servidor_interface(Array(
					'interface' => 'escalas',
					'plugin' => 'escalas',
					'opcao' => 'escalar',
					'dados' => Array(
						'usuarioID' => $_GESTOR['usuario-id'],
						'mes' => $mes,
						'ano' => $ano,
						'datas' => $datas,
					),
				));
				
				if(!$retorno['completed']){
					switch($retorno['status']){
						case 'ESCALAMENTO_INATIVO':
						case 'ESCALAMENTO_DATA_NAO_PERMITIDA':
							$alerta = (existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status']);
						break;
						default:
							$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
							
							$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
					}
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
				} else {
					// ===== Dados de retorno.
					
					$dados = Array();
					if(isset($retorno['data'])){
						$dados = $retorno['data'];
					}
					
					// ===== Criar ou atualizar a escala localmente.
					
					if(isset($dados['escalas'])){
						$escalas = $dados['escalas'];
						
						$id_hosts_escalas = $escalas['id_hosts_escalas'];
						
						$escalasLocal = banco_select(Array(
							'unico' => true,
							'tabela' => 'escalas',
							'campos' => Array(
								'id_hosts_escalas',
							),
							'extra' => 
								"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						));
						
						if($escalasLocal){
							foreach($escalas as $key => $valor){
								switch($key){
									case 'ano':
									case 'mes':
									case 'versao':
										banco_update_campo($key,($valor ? $valor : '0'),true);
									break;
									case 'data_confirmacao':
										banco_update_campo($key,($valor ? $valor : 'NULL'),true);
									break;
									default:
										banco_update_campo($key,$valor);
								}
							}
							
							banco_update_executar('escalas',"WHERE id_hosts_escalas='".$id_hosts_escalas."'");
						} else {
							foreach($escalas as $key => $valor){
								switch($key){
									case 'ano':
									case 'mes':
									case 'versao':
										banco_insert_name_campo($key,($valor ? $valor : '0'),true);
									break;
									case 'data_confirmacao':
										banco_insert_name_campo($key,($valor ? $valor : 'NULL'),true);
									break;
									default:
										banco_insert_name_campo($key,$valor);
								}
							}
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"escalas"
							);
						}
						
						// ===== Criar ou atualizar as escalas datas localmente.
						
						if(isset($dados['escalas_datas'])){
							$escalas_datas = $dados['escalas_datas'];
							$escalasDatasProcessadas = Array();
							
							$escalasDatasLocal = banco_select(Array(
								'tabela' => 'escalas_datas',
								'campos' => Array(
									'id_hosts_escalas_datas',
								),
								'extra' => 
									"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
							));
							
							foreach($escalas_datas as $escala_data){
								$id_hosts_escalas_datas = $escala_data['id_hosts_escalas_datas'];
								$foundDataLocal = false;
								
								if($escalasDatasLocal){
									foreach($escalasDatasLocal as $escalaDataLocal){
										if($id_hosts_escalas_datas == $escalaDataLocal['id_hosts_escalas_datas']){
											$foundDataLocal = true;
											break;
										}
									}
								}
								
								if($foundDataLocal){
									foreach($escala_data as $key => $valor){
										switch($key){
											case 'selecionada':
											case 'selecionada_inscricao':
											case 'selecionada_confirmacao':
												banco_update_campo($key,($valor ? $valor : 'NULL'),true);
											break;
											default:
												banco_update_campo($key,$valor);
										}
									}
									
									banco_update_executar('escalas_datas',"WHERE id_hosts_escalas_datas='".$id_hosts_escalas_datas."'");
								} else {
									foreach($escala_data as $key => $valor){
										switch($key){
											case 'selecionada':
											case 'selecionada_inscricao':
											case 'selecionada_confirmacao':
												banco_insert_name_campo($key,($valor ? $valor : 'NULL'),true);
											break;
											default:
												banco_insert_name_campo($key,$valor);
										}
									}
									
									banco_insert_name
									(
										banco_insert_name_campos(),
										"escalas_datas"
									);
								}
								
								$escalasDatasProcessadas[] = $id_hosts_escalas_datas;
							}
							
							// ===== Deleter as escalas datas não encontradas, ou seja, que foram excluídas na operação.
							
							if($escalasDatasLocal){
								foreach($escalasDatasLocal as $escalaDataLocal){
									$foundDataLocal = false;
									
									if($escalasDatasProcessadas)
									foreach($escalasDatasProcessadas as $escalaDataProcessada){
										if($escalaDataProcessada == $escalaDataLocal['id_hosts_escalas_datas']){
											$foundDataLocal = true;
											break;
										}
									}
									
									if(!$foundDataLocal){
										banco_delete
										(
											"escalas_datas",
											"WHERE id_hosts_escalas_datas='".$escalaDataLocal['id_hosts_escalas_datas']."'"
										);
									}
								}
							}
						}
					}
					
					// ===== Atualizar 'escalas_controle' caso necessário.
					
					if(isset($dados['escalas_controle'])){
						$escalas_controle = $dados['escalas_controle']['tabela'];
						$dateInicio = $dados['escalas_controle']['dateInicio'];
						$dateFim = $dados['escalas_controle']['dateFim'];
						
						$escalasControleLocal = banco_select(Array(
							'tabela' => 'escalas_controle',
							'campos' => Array(
								'id_hosts_escalas_controle',
							),
							'extra' => 
								"WHERE data >= '".$dateInicio."'"
								." AND data <= '".$dateFim."'"
						));
						
						foreach($escalas_controle as $escala_controle){
							$id_hosts_escalas_controle = $escala_controle['id_hosts_escalas_controle'];
							$foundEscalaControleLocal = false;
							
							if($escalasControleLocal){
								foreach($escalasControleLocal as $escalaControleLocal){
									if($id_hosts_escalas_controle == $escalaControleLocal['id_hosts_escalas_controle']){
										$foundEscalaControleLocal = true;
										break;
									}
								}
							}
							
							if($foundEscalaControleLocal){
								foreach($escala_controle as $key => $valor){
									switch($key){
										case 'total':
											banco_update_campo($key,($valor ? $valor : '0'),true);
										break;
										default:
											banco_update_campo($key,$valor);
									}
								}
								
								banco_update_executar('escalas_controle',"WHERE id_hosts_escalas_controle='".$id_hosts_escalas_controle."'");
							} else {
								foreach($escala_controle as $key => $valor){
									switch($key){
										case 'total':
											banco_insert_name_campo($key,($valor ? $valor : '0'),true);
										break;
										default:
											banco_insert_name_campo($key,$valor);
									}
								}
								
								banco_insert_name
								(
									banco_insert_name_campos(),
									"escalas_controle"
								);
							}
						}
					}
					
					// ===== Alertar o usuário.
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $dados['alerta']
					));
				}
				
				gestor_redirecionar('escalas/?mes='.((int)$mes < 10 ? '0': '').$mes.'&ano='.$ano);
			}
		}
		
		// ===== Caso o usuário tenha ativado a opção de confirmação.
		
		if(isset($_REQUEST['confirmacao'])){
			if(
				isset($_REQUEST['escolha']) && 
				isset($_REQUEST['mes']) && 
				isset($_REQUEST['ano'])
			){
				// ===== Pegar os valores enviados e escapar os dados.
				
				$escolha = banco_escape_field($_REQUEST['escolha']);
				$mes = banco_escape_field($_REQUEST['mes']);
				$ano = banco_escape_field($_REQUEST['ano']);
				
				// ===== API-Servidor para confirmacao.
				
				gestor_incluir_biblioteca('api-servidor');
				
				$retorno = api_servidor_interface(Array(
					'interface' => 'escalas',
					'plugin' => 'escalas',
					'opcao' => 'confirmacao',
					'dados' => Array(
						'usuarioID' => $_GESTOR['usuario-id'],
						'mes' => $mes,
						'ano' => $ano,
						'escolha' => $escolha,
					),
				));
				
				if(!$retorno['completed']){
					switch($retorno['status']){
						case 'ESCALAMENTO_INATIVO':
						case 'CONFIRMACAO_FORA_DO_PRAZO':
							$alerta = (existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status']);
						break;
						default:
							$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
							
							$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
					}
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
				} else {
					// ===== Dados de retorno.
					
					$dados = Array();
					if(isset($retorno['data'])){
						$dados = $retorno['data'];
					}
					
					// ===== Atualizar a escala localmente.
					
					if(isset($dados['escalas'])){
						$escalas = $dados['escalas'];
						
						$id_hosts_escalas = $escalas['id_hosts_escalas'];
						
						$escalasLocal = banco_select(Array(
							'unico' => true,
							'tabela' => 'escalas',
							'campos' => Array(
								'id_hosts_escalas',
							),
							'extra' => 
								"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						));
						
						if($escalasLocal){
							foreach($escalas as $key => $valor){
								switch($key){
									case 'ano':
									case 'mes':
									case 'versao':
										banco_update_campo($key,($valor ? $valor : '0'),true);
									break;
									default:
										banco_update_campo($key,$valor);
								}
							}
							
							banco_update_executar('escalas',"WHERE id_hosts_escalas='".$id_hosts_escalas."'");
						}
						
						// ===== Atualizar as escalas datas localmente.
						
						if(isset($dados['escalas_datas'])){
							$escalas_datas = $dados['escalas_datas'];
							$escalasDatasProcessadas = Array();
							
							$escalasDatasLocal = banco_select(Array(
								'tabela' => 'escalas_datas',
								'campos' => Array(
									'id_hosts_escalas_datas',
								),
								'extra' => 
									"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
							));
							
							foreach($escalas_datas as $escala_data){
								$id_hosts_escalas_datas = $escala_data['id_hosts_escalas_datas'];
								$foundDataLocal = false;
								
								if($escalasDatasLocal){
									foreach($escalasDatasLocal as $escalaDataLocal){
										if($id_hosts_escalas_datas == $escalaDataLocal['id_hosts_escalas_datas']){
											$foundDataLocal = true;
											break;
										}
									}
								}
								
								if($foundDataLocal){
									foreach($escala_data as $key => $valor){
										switch($key){
											case 'selecionada':
											case 'selecionada_inscricao':
											case 'selecionada_confirmacao':
												banco_update_campo($key,($valor ? $valor : 'NULL'),true);
											break;
											default:
												banco_update_campo($key,$valor);
										}
									}
									
									banco_update_executar('escalas_datas',"WHERE id_hosts_escalas_datas='".$id_hosts_escalas_datas."'");
								}
							}
						}
					}
					
					// ===== Atualizar 'escalas_controle' caso necessário.
					
					if(isset($dados['escalas_controle'])){
						$escalas_controle = $dados['escalas_controle']['tabela'];
						$dateInicio = $dados['escalas_controle']['dateInicio'];
						$dateFim = $dados['escalas_controle']['dateFim'];
						
						$escalasControleLocal = banco_select(Array(
							'tabela' => 'escalas_controle',
							'campos' => Array(
								'id_hosts_escalas_controle',
							),
							'extra' => 
								"WHERE data >= '".$dateInicio."'"
								." AND data <= '".$dateFim."'"
						));
						
						foreach($escalas_controle as $escala_controle){
							$id_hosts_escalas_controle = $escala_controle['id_hosts_escalas_controle'];
							$foundEscalaControleLocal = false;
							
							if($escalasControleLocal){
								foreach($escalasControleLocal as $escalaControleLocal){
									if($id_hosts_escalas_controle == $escalaControleLocal['id_hosts_escalas_controle']){
										$foundEscalaControleLocal = true;
										break;
									}
								}
							}
							
							if($foundEscalaControleLocal){
								foreach($escala_controle as $key => $valor){
									switch($key){
										case 'total':
											banco_update_campo($key,($valor ? $valor : '0'),true);
										break;
										default:
											banco_update_campo($key,$valor);
									}
								}
								
								banco_update_executar('escalas_controle',"WHERE id_hosts_escalas_controle='".$id_hosts_escalas_controle."'");
							} else {
								foreach($escala_controle as $key => $valor){
									switch($key){
										case 'total':
											banco_insert_name_campo($key,($valor ? $valor : '0'),true);
										break;
										default:
											banco_insert_name_campo($key,$valor);
									}
								}
								
								banco_insert_name
								(
									banco_insert_name_campos(),
									"escalas_controle"
								);
							}
						}
					}
					
					// ===== Alertar o usuário.
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $dados['alerta']
					));
				}
				
				gestor_redirecionar('escalas/?mes='.((int)$mes < 10 ? '0': '').$mes.'&ano='.$ano);
			}
		}
		
		// ===== Remover a célula inativo e alteracoes.
		
		$cel_nome = 'inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Definição do mês e ano do calendário.
		
		//$hoje = strtotime('21-09-2022');
		$hoje = time();
		
		$mes = (isset($_REQUEST['mes']) ? $_REQUEST['mes'] : date('m') );
		$ano = (isset($_REQUEST['ano']) ? $_REQUEST['ano'] : date('Y') );
		
		$mesAnoSelecionado = $mes.'_'.$ano;
		
		// ===== Passar o mês e o ano para inteiro.
		
		$mes = (int)$mes;
		$ano = (int)$ano;
		
		// ===== Montagem do selecionador de meses.
		
		$mesesAnos = Array();
		
		$mesesAntes = (int)$calendarioInicio + 1;
		$mesesDepois = (int)$calendarioFim;
		$totalMeses = $mesesAntes + $mesesDepois;
		
		$mesHoje = (int)date('m');
		$anoHoje = (int)date('Y');
		
		$mesesAtras = $mesesAntes % 12;
		$anosAtras = floor($mesesAntes / 12);
		$mesesAFrente = $mesesDepois % 12;
		
		$mesInicio = $mesHoje - $mesesAtras;
		$mesFim = $mesHoje + $mesesAFrente;
		$anoInicio = $anoHoje - $anosAtras;
		
		$mesAtual = $mesInicio;
		$anoAtual = $anoInicio;
		
		$mesesNomes = Array(
			1 => 'Janeiro',
			2 => 'Fevereiro',
			3 => 'Março',
			4 => 'Abril',
			5 => 'Maio',
			6 => 'Junho',
			7 => 'Julho',
			8 => 'Agosto',
			9 => 'Setembro',
			10 => 'Outubro',
			11 => 'Novembro',
			12 => 'Dezembro',
		);
		
		for($i=0;$i<$totalMeses;$i++){
			$mesAtualFormatado = ($mesAtual+1);
			
			$dataTexto = $mesesNomes[$mesAtualFormatado] . ' / ' . $anoAtual;
			
			if($mesAtualFormatado < 10){
				$mesAtualFormatado = '0'.$mesAtualFormatado;
			}
			
			$mesesAnos[] = Array(
				'texto' => $dataTexto,
				'valor' => $mesAtualFormatado.'_'.$anoAtual,
			);
			
			$mesAtual++;
			
			if($mesAtual > 11){
				$mesAtual = 0;
				$anoAtual++;
			}
		}
		
		interface_formulario_campos(Array(
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'meses',
					'nome' => 'meses',
					'procurar' => true,
					'valor_selecionado' => $mesAnoSelecionado,
					'placeholder' => '',
					'dados' => $mesesAnos,
				)
			)
		));
		
		// ===== Colocar as datas selecionadas caso houver, mês e ano atual.
		
		$mesAtual = $mes;
		$anoAtual = $ano;
		
		$mesAtualFormatado = ($mesAtual < 10 ? '0':'') . $mesAtual;
		
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
		
		$data_confirmacao_inicio = escalas_data_dias_antes($mes,$ano,$diasInicioConfirmacao,'01/'. $mesAtualFormatado . '/' . $anoAtual);
		$data_confirmacao_fim = escalas_data_dias_antes($mes,$ano,$diasFimConfirmacao,'01/'. $mesAtualFormatado . '/' . $anoAtual);
		
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
		
		// ===== Passar todas as datas para o formato 'time'.
		
		$tempo['inscricao-inicio'] = strtotime(str_replace('/', '-', $data_inscricao_inicio));
		$tempo['inscricao-fim'] = strtotime(str_replace('/', '-', $data_inscricao_fim)) + 86399;
		$tempo['confirmacao-inicio'] = strtotime(str_replace('/', '-', $data_confirmacao_inicio));
		$tempo['confirmacao-fim'] = strtotime(str_replace('/', '-', $data_confirmacao_fim)) + 86399;
		$tempo['utilizacao-inicio'] = $tempo['confirmacao-fim'];
		$tempo['utilizacao-fim'] = strtotime(str_replace('/', '-', $data_final_mes)) + 86399;
		
		// ===== Definir a fase atual da escala.
		
		$faseAtual = 'indisponivel';
		
		if($hoje < $tempo['inscricao-inicio']){
			$faseAtual = 'pre-inscricao';
		}
		
		if(
			$hoje >= $tempo['inscricao-inicio'] && 
			$hoje <= $tempo['inscricao-fim']
		){
			$faseAtual = 'inscricao';
		}
		
		if(
			$hoje >= $tempo['confirmacao-inicio'] && 
			$hoje <= $tempo['confirmacao-fim']
		){
			$faseAtual = 'confirmacao';
		}
		
		if(
			$hoje > $tempo['utilizacao-inicio'] && 
			$hoje <= $tempo['utilizacao-fim']
		){
			$faseAtual = 'utilizacao';
		}
		
		// ===== Pegar a escala do mês do usuário.
		
		$status = '';
		$data_confirmacao = '';
		
		$escalas = banco_select(Array(
			'unico' => true,
			'tabela' => 'escalas',
			'campos' => Array(
				'id_hosts_escalas',
				'status',
				'data_confirmacao',
			),
			'extra' => 
				"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
				." AND mes='".$mes."'"
				." AND ano='".$ano."'"
		));
		
		if($escalas){
			$status = $escalas['status'];
			$data_confirmacao = $escalas['data_confirmacao'];
		}
		
		// ===== Variáveis de controle para montagem do calendário.
		
		$datasSelecionadas = '';
		$naoQualificado = false;
		$confirmado = false;
		$faseConfirmacao = false;
		$datasQualificadas = Array();
		$faseUtilizacao = false;
		$datasUtilizacao = Array();
		
		// ===== Verificar cada fase da escala e definir as variáveis de controle do calendário.
		
		switch($faseAtual){
			
			// ===== Permitir modificações na escala de forma livre.
			
			case 'inscricao':
				// ===== Pegar todas as datas selecionadas para filtrar o calendário.
				
				if($escalas){
					$escalas_datas = banco_select(Array(
						'tabela' => 'escalas_datas',
						'campos' => Array(
							'data',
						),
						'extra' => 
							"WHERE id_hosts_escalas='".$escalas['id_hosts_escalas']."'"
							." AND selecionada IS NOT NULL"
					));
					
					if($escalas_datas)
					foreach($escalas_datas as $escala_data){
						$datasSelecionadas .= (existe($datasSelecionadas) ? ',' : '') . formato_dado_para('data',$escala_data['data']);
					}
				}
			break;
			
			// ===== Somente permitir modificações em escala com estado 'qualificado', 'email-enviado', 'email-nao-enviado' ou 'confirmado'.
			
			case 'confirmacao':
				if(
					$status != 'qualificado' &&
					$status != 'email-enviado' &&
					$status != 'email-nao-enviado' &&
					$status != 'confirmado'
				){
					$naoQualificado = true;
				}
				
				if($status == 'confirmado'){
					$confirmado = true;
				}
				
				$faseConfirmacao = true;
				
				// ===== Pegar todas as datas qualificadas para filtrar o calendário.
				
				if($escalas){
					$escalas_datas = banco_select(Array(
						'tabela' => 'escalas_datas',
						'campos' => Array(
							'data',
							'selecionada',
						),
						'extra' => 
							"WHERE id_hosts_escalas='".$escalas['id_hosts_escalas']."'"
							." AND status='qualificado'"
					));
					
					if($escalas_datas)
					foreach($escalas_datas as $escala_data){
						$datasQualificadas[] = $escala_data['data'];
						
						// ===== Pegar todas as datas selecionadas para filtrar o calendário.
						
						if($escala_data['selecionada']){
							$datasSelecionadas .= (existe($datasSelecionadas) ? ',' : '') . formato_dado_para('data',$escala_data['data']);
						}
					}
				}
			break;
			
			// ===== Somente permitir modificações em escala com estado 'confirmado' ou 'vaga-residual'. Senão, para formar o calendário será necessário verificar a quantidade de vagas.
			
			case 'utilizacao':
				$faseUtilizacao = true;
				
				// ===== Pegar todas as datas com status 'confirmado' ou 'vaga-residual' para filtrar o calendário.
				
				if($escalas){
					$escalas_datas = banco_select(Array(
						'tabela' => 'escalas_datas',
						'campos' => Array(
							'data',
						),
						'extra' => 
							"WHERE id_hosts_escalas='".$escalas['id_hosts_escalas']."'"
							." AND (status='confirmado' OR status='vaga-residual')"
							." AND selecionada IS NOT NULL"
					));
					
					if($escalas_datas)
					foreach($escalas_datas as $escala_data){
						$datasUtilizacao[] = $escala_data['data'];
						$datasSelecionadas .= (existe($datasSelecionadas) ? ',' : '') . formato_dado_para('data',$escala_data['data']);
					}
				}
			break;
		}
		
		// ===== Montagem do calendário.
		
		escalas_calendario(Array(
			'mes' => $mes,
			'ano' => $ano,
			'hoje' => $hoje,
			'inscricaoInicio' => $data_inscricao_inicio,
			'naoQualificado' => $naoQualificado,
			'faseConfirmacao' => $faseConfirmacao,
			'datasQualificadas' => $datasQualificadas,
			'faseUtilizacao' => $faseUtilizacao,
			'datasUtilizacao' => $datasUtilizacao,
		));
		
		// ===== Definir as datas dos períodos de atualizações.
		
		pagina_trocar_variavel_valor('datas-selecionadas',$datasSelecionadas);
		pagina_trocar_variavel_valor('mes-atual',$mesAtual);
		pagina_trocar_variavel_valor('ano-atual',$anoAtual);
		
		pagina_trocar_variavel_valor('data-inscricao-inicio',$data_inscricao_inicio);
		pagina_trocar_variavel_valor('data-inscricao-fim',$data_inscricao_fim);
		
		pagina_trocar_variavel_valor('data-confirmacao-inicio',$data_confirmacao_inicio);
		pagina_trocar_variavel_valor('data-confirmacao-fim',$data_confirmacao_fim);
		
		pagina_trocar_variavel_valor('data-inicial-mes',$data_inicial_mes);
		
		// ===== Mostrar o periodoLimiteAlteracao.
		
		pagina_trocar_variavel_valor('periodo-limite-alteracao',$periodoLimiteAlteracao);
		
		// ===== Definir a data_confirmacao caso necessário.
		
		if($confirmado){
			pagina_trocar_variavel_valor('data-da-confirmacao',$data_confirmacao);
		}
		
		// ===== Definir a interface gráfica de cada fase.
		
		switch($faseAtual){
			case 'pre-inscricao':
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'disponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'salvar-botao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'nao-qualificado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			case 'inscricao':
				pagina_trocar_variavel_valor('inscricao-step','active',true);
				pagina_trocar_variavel_valor('confirmacao-step','disabled',true);
				pagina_trocar_variavel_valor('utilizacao-step','disabled',true);
				
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'nao-qualificado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			case 'confirmacao':
				pagina_trocar_variavel_valor('inscricao-step','completed',true);
				pagina_trocar_variavel_valor('confirmacao-step','active',true);
				pagina_trocar_variavel_valor('utilizacao-step','disabled',true);
				
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				
				if($naoQualificado){
					$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
					$cel_nome = 'salvar-botao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				} else {
					$cel_nome = 'nao-qualificado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				}
				
				if($confirmado){
					$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				} else {
					$cel_nome = 'confirmado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				}
			break;
			case 'utilizacao':
				pagina_trocar_variavel_valor('inscricao-step','completed',true);
				pagina_trocar_variavel_valor('confirmacao-step','completed',true);
				pagina_trocar_variavel_valor('utilizacao-step','active',true);
				
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'nao-qualificado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			default:
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'disponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'nao-qualificado'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'salvar-botao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		}
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