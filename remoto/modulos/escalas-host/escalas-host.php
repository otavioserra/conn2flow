<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'escalas-host';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.28',
);

// ===== Funções Auxiliares

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
				
				if($mesVagasResiduais){
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
				
				// ===== .
				
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
	
	// ===== Retornar o valor das datas selecionadas.
	
	return '04/08/2022,11/08/2022,16/08/2022,18/08/2022';
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
		// ===== Remover a célula inativo e alteracoes.
		
		$cel_nome = 'inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Definição do mês e ano do calendário.
		
		$hoje = strtotime('21-07-2022');
		//$hoje = time();
		
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
		
		for($i=0;$i<$totalMeses;$i++){
			$mesesAnos[] = Array(
				'texto' => ($mesAtual+1).'_'.$anoAtual,
				'valor' => ($mesAtual+1).'_'.$anoAtual,
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
					//'limpar' => true,
					'valor_selecionado' => $mesAnoSelecionado,
					'placeholder' => '',
					'dados' => $mesesAnos,
				)
			)
		));
		
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
		
		// ===== Montagem do calendário.
		
		$datasSelecionadas = escalas_calendario(Array(
			'mes' => $mes,
			'ano' => $ano,
			'hoje' => $hoje,
			'inscricaoInicio' => $data_inscricao_inicio,
		));
		
		// ===== Colocar as datas selecionadas caso houver.
		
		pagina_trocar_variavel_valor('datas-selecionadas',$datasSelecionadas);
		
		// ===== Definir a data do início da confirmação.
		
		$definirConfirmacao = true;
		while($definirConfirmacao){
			if(!isset($anoInicioConfirmacao)){
				$anoInicioConfirmacao = $ano;
			}
			
			if(!isset($mesInicioConfirmacao)){
				$mesInicioConfirmacao = $mes - 1;
			} else {
				$mesInicioConfirmacao--;
			}
			
			if($mesInicioConfirmacao < 1){
				$mesInicioConfirmacao = 12;
				$anoInicioConfirmacao--;
			}
			
			$totalDiasMes = cal_days_in_month(CAL_GREGORIAN, $mesInicioConfirmacao, $anoInicioConfirmacao);
			
			$diaInicioConfirmacao = $totalDiasMes - ($diasInicioConfirmacao - (isset($diasInicioConfirmacaoContados) ? $diasInicioConfirmacaoContados : 0));
			
			if($diaInicioConfirmacao > 0){
				if($mesInicioConfirmacao < 10){
					$mesInicioConfirmacao = '0' . $mesInicioConfirmacao;
				}
				
				if($diaInicioConfirmacao < 10){
					$diaInicioConfirmacao = '0' . $diaInicioConfirmacao;
				}
				
				$data_confirmacao_inicio = $diaInicioConfirmacao . '/' . $mesInicioConfirmacao . '/' . $anoInicioConfirmacao;
				
				$definirConfirmacao = false;
			} else {
				if(!isset($diasInicioConfirmacaoContados)){
					$diasInicioConfirmacaoContados = $totalDiasMes;
				} else {
					$diasInicioConfirmacaoContados += $totalDiasMes;
				}
			}
		}
		
		// ===== Definir a data do fim da confirmação.
		
		$definirConfirmacao = true;
		while($definirConfirmacao){
			if(!isset($anoFimConfirmacao)){
				$anoFimConfirmacao = $ano;
			}
			
			if(!isset($mesFimConfirmacao)){
				$mesFimConfirmacao = $mes - 1;
			} else {
				$mesFimConfirmacao--;
			}
			
			if($mesFimConfirmacao < 1){
				$mesFimConfirmacao = 12;
				$anoFimConfirmacao--;
			}
			
			$totalDiasMes = cal_days_in_month(CAL_GREGORIAN, $mesFimConfirmacao, $anoFimConfirmacao);
			
			$diaFimConfirmacao = $totalDiasMes - ($diasFimConfirmacao - (isset($diasFimConfirmacaoContados) ? $diasFimConfirmacaoContados : 0));
			
			if($diaFimConfirmacao > 0){
				if($mesFimConfirmacao < 10){
					$mesFimConfirmacao = '0' . $mesFimConfirmacao;
				}
				
				if($diaFimConfirmacao < 10){
					$diaFimConfirmacao = '0' . $diaFimConfirmacao;
				}
				
				$data_confirmacao_fim = $diaFimConfirmacao . '/' . $mesFimConfirmacao . '/' . $anoFimConfirmacao;
				
				$definirConfirmacao = false;
			} else {
				if(!isset($diasFimConfirmacaoContados)){
					$diasFimConfirmacaoContados = $totalDiasMes;
				} else {
					$diasFimConfirmacaoContados += $totalDiasMes;
				}
			}
		}
		
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
		
		// ===== Definir as datas dos períodos de atualizações.
		
		pagina_trocar_variavel_valor('data-inscricao-inicio',$data_inscricao_inicio);
		pagina_trocar_variavel_valor('data-inscricao-fim',$data_inscricao_fim);
		
		pagina_trocar_variavel_valor('data-confirmacao-inicio',$data_confirmacao_inicio);
		pagina_trocar_variavel_valor('data-confirmacao-fim',$data_confirmacao_fim);
		
		pagina_trocar_variavel_valor('data-inicial-mes',$data_inicial_mes);
		
		// ===== Mostrar o periodoLimiteAlteracao.
		
		pagina_trocar_variavel_valor('periodo-limite-alteracao',$periodoLimiteAlteracao);
		
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
		
		// ===== Definir a interface gráfica de cada fase.
		
		switch($faseAtual){
			case 'pre-inscricao':
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'disponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'salvar-botao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			case 'inscricao':
				pagina_trocar_variavel_valor('inscricao-step','active',true);
				pagina_trocar_variavel_valor('confirmacao-step','disabled',true);
				pagina_trocar_variavel_valor('utilizacao-step','disabled',true);
				
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			case 'confirmacao':
				pagina_trocar_variavel_valor('inscricao-step','completed',true);
				pagina_trocar_variavel_valor('confirmacao-step','active',true);
				pagina_trocar_variavel_valor('utilizacao-step','disabled',true);
				
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			case 'utilizacao':
				pagina_trocar_variavel_valor('inscricao-step','completed',true);
				pagina_trocar_variavel_valor('confirmacao-step','completed',true);
				pagina_trocar_variavel_valor('utilizacao-step','active',true);
				
				$cel_nome = 'concluido'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			default:
				$cel_nome = 'indisponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'disponivel'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
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