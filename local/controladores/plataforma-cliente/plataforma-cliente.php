<?php

// ===== Plataforma responsável por receber solicitações do 'cliente'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-cliente-plugin';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

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
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas'));
				
				$escala_ativacao = (existe($config['escala-ativacao']) ? true : false);
				$msg_escala_suspenso = (existe($config['msg-escala-suspenso']) ? $config['msg-escala-suspenso'] : '');
				$diaInicioInscricao = (existe($config['dia-inicio-inscricao']) ? $config['dia-inicio-inscricao'] : '');
				$mesInicioInscricao = (existe($config['mes-inicio-inscricao']) ? $config['mes-inicio-inscricao'] : '');
				
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
				
				//$hoje = strtotime('21-07-2022');
				$hoje = time();
				
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
						if(!plataforma_cliente_plugin_data_permitida(Array(
							'data' => $data,
							'mes' => $mes,
							'ano' => $ano,
							'hoje' => $hoje,
							'inscricaoInicio' => $data_inscricao_inicio,
						))){
							$algumaDataNaoPermitida = true;
							$datasNaoPermitidas .= (existe($datasNaoPermitidas) ? ', ','') . $datasNaoPermitidas;
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
				
				// ===== Criar ou pegar os dados da escala do mês / ano requisitados.
				
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
					banco_insert_name_campo('data_criacao','NOW()');
					banco_insert_name_campo('data_modificacao','NOW()');
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_escalas"
					);
					
					$id_hosts_escalas = banco_last_id();
				}
				
				// ===== Criar ou pegar as datas da escala do mês / ano.
				
				$hosts_escalas_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_escalas_datas',
					'campos' => Array(
						'id_hosts_escalas_datas',
						'data',
						'status',
					),
					'extra' => 
						"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Varrer todas as datas enviadas e atualizar no banco conforme necessidade.
				
				if(existe($datasStr)){
					$datas = explode(',',$datasStr);
					
					for($i=0;$i<count($datas);$i++){
						$dataFound = false;
						
						if($hosts_escalas_datas)
						foreach($hosts_escalas_datas as $hed){
							if(formato_dado_para('date',$datas[$i]) == $hed['data']){
								$dataFound = true;
								break;
							}
						}
						
						if(!$dataFound){
							banco_insert_name_campo('id_hosts',$id_hosts);
							banco_insert_name_campo('id_hosts_escalas',$id_hosts_escalas);
							banco_insert_name_campo('data',$data);
							banco_insert_name_campo('id_hosts_escalas',$id_hosts_escalas);
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"hosts_escalas_datas"
							);
						}
					}
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