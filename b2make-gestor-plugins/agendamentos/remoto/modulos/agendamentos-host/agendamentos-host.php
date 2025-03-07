<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'agendamentos-host';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.1.4',
	'numRegistrosPorPagina' => 20,
	'forcarDataHoje' => false,
	'dataHojeForcada' => '2023-01-19',
);

// ===== Funções Auxiliares

function agendamentos_calendario($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	gestor_incluir_biblioteca('formato');
	
	$ano_inicio = date('Y');
	
	// ===== Variáveis do módulo.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
	
	// ===== .
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','conjunto' => true));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array()); else $datas_indisponiveis = Array();
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
	
	for($i=-1;$i<$anos+1;$i++){
		$periodo_ferias[] = Array(
			'inicio' => strtotime($calendario_ferias_de." ".($ano_inicio+$i)),
			'fim' => strtotime($calendario_ferias_ate." ".($ano_inicio+$i+1)),
		);
	}
	
	$primeiro_dia = strtotime(date("Y-m-d", time()) . " + 1 day");
	$ultimo_dia = strtotime(date("Y-m-d", time()) . " + ".$anos." year");
	
	if($calendario_limite_mes_a_frente){
		$limitar_calendario = strtotime(date("Y-m",strtotime($hoje . " + ".$calendario_limite_mes_a_frente." month")).'-01');
	}
	
	$dia = $primeiro_dia;
	do {
		if($limitar_calendario){
			if($dia >= $limitar_calendario){
				break;
			}
		}
		
		$dataFormatada = date('d/m/Y', $dia);
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
				if($dataFormatada == $di){
					$flag = true;
					break;
				}
			}
		}
		
		if($fase_sorteio){
			if(
				$dia >= strtotime($hoje.' + '.($fase_sorteio[1]+1).' day') &&
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
		
		$dia += 86400;
	} while ($dia < $ultimo_dia);
	
	$JScalendario['datas_disponiveis'] = $datas;
	$JScalendario['ano_inicio'] = $ano_inicio;
	$JScalendario['ano_fim'] = $ano_fim;
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['calendario'] = $JScalendario;
}

// ===== Funções Principais

function agendamentos_confirmacao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
		'formulario',
		'api-servidor',
	));
	
	// ===== Validar o agendamento_id enviado.
	
	$id_hosts_agendamentos = (isset($_REQUEST['agendamento_id']) ? banco_escape_field($_REQUEST['agendamento_id']) : '');
	
	$agendamentos = banco_select(Array(
		'unico' => true,
		'tabela' => 'agendamentos',
		'campos' => Array(
			'data',
			'status',
		),
		'extra' => 
			"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
			." AND id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
	));
	
	if(!$agendamentos){
		// ===== Ativação da expiradoOuNaoEncontrado.
		
		$_GESTOR['javascript-vars']['expiradoOuNaoEncontrado'] = true;
	} else {
		// ===== Variáveis do módulo.
		
		$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
		
		if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
		
		// ===== Verificar se a confirmação está no período válido.
		
		// ===== Dados do agendamento.
		
		$data = $agendamentos['data'];
		$status = $agendamentos['status'];
		
		// ===== Configuração de fase de sorteio.
		
		$config = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','conjunto' => true));
		
		$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
		$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
		
		// ===== Verificar se o status atual do agendamento permite confirmação.
		
		if(
			$status == 'confirmado' ||
			$status == 'qualificado' ||
			$status == 'email-enviado' ||
			$status == 'email-nao-enviado'
		){
			// ===== Verificar se está na fase de confirmação.
			
			if(
				strtotime($data) >= strtotime($hoje.' + '.($fase_sorteio[1]+1).' day') &&
				strtotime($data) < strtotime($hoje.' + '.($fase_sorteio[0]+1).' day')
			){
				
			} else {
				// ===== Datas do período de confirmação.
				
				gestor_incluir_biblioteca('formato');
				
				$data_confirmacao_1 = formato_dado_para('data',date('Y-m-d',strtotime($data.' - '.($fase_sorteio[0]).' day')));
				$data_confirmacao_2 = formato_dado_para('data',date('Y-m-d',strtotime($data.' - '.($fase_sorteio[1]).' day') - 1));
				
				// ===== Retornar a mensagem de agendamento expirado.
				
				$msgAgendamentoExpirado = (existe($config['msg-agendamento-expirado']) ? $config['msg-agendamento-expirado'] : '');
				
				$msgAgendamentoExpirado = modelo_var_troca_tudo($msgAgendamentoExpirado,"#data_confirmacao_1#",$data_confirmacao_1);
				$msgAgendamentoExpirado = modelo_var_troca_tudo($msgAgendamentoExpirado,"#data_confirmacao_2#",$data_confirmacao_2);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $msgAgendamentoExpirado
				));
				
				gestor_redirecionar('agendamentos/?tela=agendamentos-anteriores');
			}
		} else {
			if(
				strtotime($hoje) >= strtotime($data.' - '.$fase_residual.' day') &&
				strtotime($hoje) <= strtotime($data.' - 1 day')
			){
				
			} else {
				interface_alerta(Array(
					'redirect' => true,
					'msg' => 'AGENDAMENTO_STATUS_NAO_PERMITIDO_CONFIRMACAO'
				));
				
				gestor_redirecionar('agendamentos/?tela=agendamentos-anteriores');
			}
		}
		
		// ===== Solicitação de confirmação do agendamento.
		
		if(isset($_REQUEST['efetuar_confirmacao'])){
			// ===== Pegar a escolha de alteração.
			
			$escolha = $_REQUEST['escolha'];
			
			// ===== API-Servidor para efetuar confirmação.
			
			$retorno = api_servidor_interface(Array(
				'interface' => 'alteracao',
				'plugin' => 'agendamentos',
				'opcao' => 'confirmar',
				'dados' => Array(
					'escolha' => ($escolha == 'confirmar' ? 'confirmar':'cancelar'),
					'id_hosts_agendamentos' => $id_hosts_agendamentos,
					'id_hosts_usuarios' => $_GESTOR['usuario-id'],
				),
			));
			
			if(!$retorno['completed']){
				switch($retorno['status']){
					case 'AGENDAMENTO_NAO_ENCONTRADO':
					case 'AGENDAMENTO_CONFIRMACAO_EXPIRADO':
					case 'AGENDAMENTO_SEM_VAGAS':
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
				
				// ===== Caso houve atualização do agendamentos datas, alterar os dados localmente.
			
				if(isset($dados['agendamentos_datas'])){
					$id_hosts_agendamentos_datas = $dados['agendamentos_datas']['id_hosts_agendamentos_datas'];
					$total = $dados['agendamentos_datas']['total'];
					
					$agendamentos_datas = banco_select(Array(
						'unico' => true,
						'tabela' => 'agendamentos_datas',
						'campos' => Array(
							'id_hosts_agendamentos_datas',
						),
						'extra' => 
							"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'"
					));
					
					if($agendamentos_datas){
						banco_update_campo('total',$total);
						
						banco_update_executar('agendamentos_datas',"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'");
					} else {
						$data = $dados['agendamentos_datas']['data'];
						$status = $dados['agendamentos_datas']['status'];
						
						banco_insert_name_campo('id_hosts_agendamentos_datas',$id_hosts_agendamentos_datas);
						banco_insert_name_campo('data',$data);
						banco_insert_name_campo('total',$total);
						banco_insert_name_campo('status',$status);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"agendamentos_datas"
						);
					}
				}
				
				// ===== Gerar o agendamento ou atualizar um já existente.
				
				$id_hosts_usuarios = $dados['agendamentos']['id_hosts_usuarios'];
				$id_hosts_agendamentos = $dados['agendamentos']['id_hosts_agendamentos'];
				
				$agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'agendamentos',
					'campos' => Array(
						'id_hosts_agendamentos',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
				));
				
				if($agendamentos){
					// ===== Atualizar agendamento.
					
					if(isset($dados['agendamentos'])){
						$agendamentos = $dados['agendamentos'];
						
						foreach($agendamentos as $key => $valor){
							switch($key){
								case 'acompanhantes':
								case 'versao':
									banco_update_campo($key,($valor ? $valor : '0'),true);
								break;
								default:
									banco_update_campo($key,$valor);
							}
						}
						
						banco_update_executar('agendamentos',"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					}
				}
				
				// ===== Alertar o usuário.
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $dados['alerta']
				));
			}
			
			// ===== Ler a listagem dos agendamentos.
			
			gestor_redirecionar('agendamentos/?tela=agendamentos-anteriores');
		}
		
		// ===== Ativação da confirmação.
		
		$_GESTOR['javascript-vars']['confirmar'] = true;
	}
	
	// ===== Remover a célula ativo e inativo.
	
	$cel_nome = 'ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	$cel_nome = 'inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	
	// ===== Incluir o token no formulário.
	
	pagina_trocar_variavel_valor('confirmacao-data',($agendamentos ? formato_dado_para('data',$agendamentos['data']) : ''));
	pagina_trocar_variavel_valor('confirmacao-agendamento-id',$id_hosts_agendamentos);
	
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

function agendamentos_cancelamento(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
		'formulario',
		'api-servidor',
	));
	
	// ===== Validar o agendamento_id enviado.
	
	$id_hosts_agendamentos = (isset($_REQUEST['agendamento_id']) ? banco_escape_field($_REQUEST['agendamento_id']) : '');
	
	$agendamentos = banco_select(Array(
		'unico' => true,
		'tabela' => 'agendamentos',
		'campos' => Array(
			'data',
			'status',
		),
		'extra' => 
			"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
			." AND id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
	));
	
	if(!$agendamentos){
		// ===== Ativação da expiradoOuNaoEncontrado.
		
		$_GESTOR['javascript-vars']['expiradoOuNaoEncontrado'] = true;
	} else {
		
		// ===== Solicitação de confirmação do cancelamento.
		
		if(isset($_REQUEST['efetuar_cancelamento'])){
			// ===== API-Servidor para efetuar confirmação.
			
			$retorno = api_servidor_interface(Array(
				'interface' => 'alteracao',
				'plugin' => 'agendamentos',
				'opcao' => 'cancelar',
				'dados' => Array(
					'id_hosts_agendamentos' => $id_hosts_agendamentos,
					'id_hosts_usuarios' => $_GESTOR['usuario-id'],
				),
			));
			
			if(!$retorno['completed']){
				switch($retorno['status']){
					case 'AGENDAMENTO_NAO_ENCONTRADO':
					case 'AGENDAMENTO_CONFIRMACAO_EXPIRADO':
					case 'AGENDAMENTO_SEM_VAGAS':
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
				
				// ===== Caso houve atualização do agendamentos datas, alterar os dados localmente.
			
				if(isset($dados['agendamentos_datas'])){
					$id_hosts_agendamentos_datas = $dados['agendamentos_datas']['id_hosts_agendamentos_datas'];
					$total = $dados['agendamentos_datas']['total'];
					
					$agendamentos_datas = banco_select(Array(
						'unico' => true,
						'tabela' => 'agendamentos_datas',
						'campos' => Array(
							'id_hosts_agendamentos_datas',
						),
						'extra' => 
							"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'"
					));
					
					if($agendamentos_datas){
						banco_update_campo('total',$total);
						
						banco_update_executar('agendamentos_datas',"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'");
					}
				}
				
				// ===== Gerar o agendamento ou atualizar um já existente.
				
				$id_hosts_usuarios = $dados['agendamentos']['id_hosts_usuarios'];
				$id_hosts_agendamentos = $dados['agendamentos']['id_hosts_agendamentos'];
				
				$agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'agendamentos',
					'campos' => Array(
						'id_hosts_agendamentos',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
				));
				
				if($agendamentos){
					// ===== Atualizar agendamento.
					
					if(isset($dados['agendamentos'])){
						$agendamentos = $dados['agendamentos'];
						
						foreach($agendamentos as $key => $valor){
							switch($key){
								case 'acompanhantes':
								case 'versao':
									banco_update_campo($key,($valor ? $valor : '0'),true);
								break;
								default:
									banco_update_campo($key,$valor);
							}
						}
						
						banco_update_executar('agendamentos',"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					}
				}
				
				// ===== Alertar o usuário.
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $dados['alerta']
				));
			}
			
			// ===== Ler a listagem dos agendamentos.
			
			gestor_redirecionar('agendamentos/?tela=agendamentos-anteriores');
		}
		
		// ===== Ativação do cancelamento.
		
		$_GESTOR['javascript-vars']['cancelar'] = true;
	}
	
	// ===== Remover a célula ativo e inativo.
	
	$cel_nome = 'ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	$cel_nome = 'inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	
	// ===== Incluir o token no formulário.
	
	pagina_trocar_variavel_valor('cancelamento-data',($agendamentos ? formato_dado_para('data',$agendamentos['data']) : ''));
	pagina_trocar_variavel_valor('cancelamento-agendamento-id',$id_hosts_agendamentos);
	
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

function agendamentos_padrao(){
	global $_GESTOR;
	
	// ===== Variáveis do módulo.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Ação disparada.
	
	switch($_REQUEST['acao']){
		case 'confirmar':
			agendamentos_confirmacao();
			return;
		break;
		case 'cancelar':
			agendamentos_cancelamento();
			return;
		break;
	}
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
		'formulario',
	));
	
	// ===== Solicitação de criar agendamento.
	
	if(isset($_REQUEST['agendar'])){
		// ===== Tratar dados enviados.
		
		$agendamentoData = banco_escape_field(formato_dado_para('date',$_REQUEST['data']));
		$acompanhantes = banco_escape_field($_REQUEST['acompanhantes']);
		$cupom = (isset($_REQUEST['cupom']) ? banco_escape_field($_REQUEST['cupom']) : NULL);
		
		for($i=1;$i<=(int)$acompanhantes;$i++){
			$acompanhantesNomes[] = banco_escape_field($_REQUEST['acompanhante-'.$i]);
		}
		
		// ===== API-Servidor para agendar.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'agendamentos',
			'plugin' => 'agendamentos',
			'opcao' => 'agendar',
			'dados' => Array(
				'usuarioID' => $_GESTOR['usuario-id'],
				'agendamentoData' => $agendamentoData,
				'acompanhantes' => $acompanhantes,
				'acompanhantesNomes' => $acompanhantesNomes,
				'cupom' => $cupom,
			),
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				case 'AGENDAMENTO_INATIVO':
				case 'AGENDAMENTO_DATA_NAO_PERMITIDA':
				case 'AGENDAMENTO_MULTIPLO_NAO_PERMITIDO':
				case 'AGENDAMENTO_SEM_VAGAS':
				case 'CUPOM_PRIORIDADE_INATIVO':
				case 'CUPOM_PRIORIDADE_VENCIDO':
				case 'CUPOM_PRIORIDADE_JA_UTILIZADO':
				case 'CUPOM_PRIORIDADE_NAO_ENCONTRADO':
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
			
			// ===== Caso houve atualização do agendamentos datas, alterar os dados localmente.
			
			if(isset($dados['agendamentos_datas'])){
				$id_hosts_agendamentos_datas = $dados['agendamentos_datas']['id_hosts_agendamentos_datas'];
				$total = $dados['agendamentos_datas']['total'];
				
				$agendamentos_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'agendamentos_datas',
					'campos' => Array(
						'id_hosts_agendamentos_datas',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'"
				));
				
				if($agendamentos_datas){
					banco_update_campo('total',$total);
					
					banco_update_executar('agendamentos_datas',"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'");
				} else {
					banco_insert_name_campo('id_hosts_agendamentos_datas',$id_hosts_agendamentos_datas);
					banco_insert_name_campo('data',$agendamentoData);
					banco_insert_name_campo('total',$total);
					banco_insert_name_campo('status','novo');
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"agendamentos_datas"
					);
				}
			}
			
			// ===== Gerar o agendamento ou atualizar um já existente.
			
			$id_hosts_usuarios = $_GESTOR['usuario-id'];
			$id_hosts_agendamentos = $dados['agendamentos']['id_hosts_agendamentos'];
			
			$agendamentos = banco_select(Array(
				'unico' => true,
				'tabela' => 'agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
				),
				'extra' => 
					"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
			));
			
			if($agendamentos){
				// ===== Atualizar agendamento.
				
				if(isset($dados['agendamentos'])){
					$agendamentos = $dados['agendamentos'];
					
					foreach($agendamentos as $key => $valor){
						switch($key){
							case 'acompanhantes':
							case 'versao':
								banco_update_campo($key,($valor ? $valor : '0'),true);
							break;
							default:
								banco_update_campo($key,$valor);
						}
					}
					
					banco_update_executar('agendamentos',"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
				}
				
				// ===== Substituir acompanhantes.
				
				banco_delete
				(
					"agendamentos_acompanhantes",
					"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
				);
				
				if(isset($dados['agendamentos_acompanhantes']))
				foreach($dados['agendamentos_acompanhantes'] as $agendamentos_acompanhantes){
					foreach($agendamentos_acompanhantes as $key => $valor){
						switch($key){
							default:
								banco_insert_name_campo($key,$valor);
						}
					}
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"agendamentos_acompanhantes"
					);
				}
			} else {
				// ===== Criar novo agendamento.
				
				if(isset($dados['agendamentos'])){
					$agendamentos = $dados['agendamentos'];
					
					foreach($agendamentos as $key => $valor){
						switch($key){
							case 'acompanhantes':
							case 'versao':
								banco_insert_name_campo($key,($valor ? $valor : '0'),true);
							break;
							default:
								banco_insert_name_campo($key,$valor);
						}
					}
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"agendamentos"
					);
				}
				
				// ===== Criar acompanhantes do agendamento caso houver.
				
				if(isset($dados['agendamentos_acompanhantes']))
				foreach($dados['agendamentos_acompanhantes'] as $agendamentos_acompanhantes){
					foreach($agendamentos_acompanhantes as $key => $valor){
						switch($key){
							default:
								banco_insert_name_campo($key,$valor);
						}
					}
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"agendamentos_acompanhantes"
					);
				}
			}
			
			// ===== Alertar o usuário.
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $dados['alerta']
			));
			
			gestor_redirecionar('agendamentos/?tela=agendamentos-anteriores');
		}
		
		// ===== Reler a página.
		
		gestor_reload_url();
	}
	
	// ===== Tratar o estado do agendamento.
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','conjunto' => true));
	
	$agendamento_ativacao = (existe($config['agendamento-ativacao']) ? true : false);
	$msgAgendamentoSuspenso = (existe($config['msg-agendamento-suspenso']) ? $config['msg-agendamento-suspenso'] : '');
	
	if($agendamento_ativacao){
		// ===== Dados de configuração.
		
		$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
		$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
		$fase_escolha_livre = (existe($config['fase-escolha-livre']) ? (int)$config['fase-escolha-livre'] : 7);
		$calendario_limite_mes_a_frente = (existe($config['calendario-limite-mes-a-frente']) ? (int)$config['calendario-limite-mes-a-frente'] : false);
		$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
		$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
		
		// ===== Remover a célula inativo e alteracoes.
		
		$cel_nome = 'inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		$cel_nome = 'alteracoes'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Pegar células dos agendamentos.
		
		$cel_nome = 'cel-pre'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
		$cel_nome = 'cel-agendamentos'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
		$cel_nome = 'cel-antigos'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
		
		$cel_nome = 'carregar-mais-pre'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
		$cel_nome = 'carregar-mais-agendamentos'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
		$cel_nome = 'carregar-mais-antigos'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
		
		$cel_nome = 'pre-agendamentos'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		$cel_nome = 'agendamentos'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		$cel_nome = 'agendamentos-antigos'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Montagem do calendário.
		
		agendamentos_calendario();
		
		// ===== Valor time do dia de hoje.
		
		if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
		
		// ===== Pegar agendamento do usuário no banco de dados.
		
		$BDPreAgendamentos = banco_select(Array(
			'tabela' => 'agendamentos',
			'campos' => Array(
				'id_hosts_agendamentos',
				'data',
				'acompanhantes',
				'status',
				'data_modificacao',
			),
			'extra' => 
				"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
				." AND status!='confirmado'"
				." AND status!='finalizado'"
				." AND data >='".$hoje."'"
				." ORDER BY data ASC"
		));
		
		$BDAgendamentos = banco_select(Array(
			'tabela' => 'agendamentos',
			'campos' => Array(
				'id_hosts_agendamentos',
				'data',
				'acompanhantes',
				'senha',
				'status',
				'data_modificacao',
			),
			'extra' => 
				"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
				." AND status='confirmado'"
				." AND data >='".$hoje."'"
				." ORDER BY data ASC"
		));
		
		$BDAntigos = banco_select(Array(
			'tabela' => 'agendamentos',
			'campos' => Array(
				'id_hosts_agendamentos',
				'data',
				'acompanhantes',
				'status',
				'data_modificacao',
			),
			'extra' => 
				"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
				." AND (
					data <'".$hoje."' OR
					status='finalizado'
				)"
				." ORDER BY data ASC"
		));
		
		// ===== Quantidade de registros por página.
		
		$numRegistrosPorPagina = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['numRegistrosPorPagina'];
		
		// ===== Verificar se o usuário tem agendamentos.
		
		if($BDPreAgendamentos || $BDAgendamentos || $BDAntigos){
			// ===== Status de agendamento.
			
			$statusAgendamentoIDs = Array(
				'status-confirmado',
				'status-finalizado',
				'status-nao-qualificado',
				'status-novo',
				'status-qualificado',
				'status-sem-vagas-residuais',
				'status-vagas-residuais',
			);
			
			if($statusAgendamentoIDs)
			foreach($statusAgendamentoIDs as $statusID){
				$statusAgendamento[$statusID] = (existe($config[$statusID]) ? $config[$statusID] : '');
			}
			
			// ===== Verificar os pré-agendamentos.
			
			if($BDPreAgendamentos){
				// ===== Quantidade máxima de registros, contador de registros.
				
				$numRegistros = count($BDPreAgendamentos);
				$contador = 0;
				
				// ===== Varrer todos os pré-agendamentos.
				
				foreach($BDPreAgendamentos as $agendamento){
					// ===== Definir o status.
					
					$confirmar = false;
					
					if(strtotime($agendamento['data']) > strtotime($hoje.' + '.$fase_escolha_livre.' day')){
						$agendamento['status'] = $statusAgendamento['status-novo'];
						$atualizacao = formato_dado_para('data',date('Y-m-d',strtotime($agendamento['data'].' - '.($fase_sorteio[0]).' day')));
					} else if(strtotime($agendamento['data']) > strtotime($hoje.' + '.$fase_sorteio[1].' day')){
						if($agendamento['status'] == 'qualificado' || $agendamento['status'] == 'email-enviado' || $agendamento['status'] == 'email-nao-enviado'){
							$confirmar = true;
							$agendamento['status'] = $statusAgendamento['status-qualificado'];
						} else {
							$agendamento['status'] = $statusAgendamento['status-nao-qualificado'];
						}
						
						$atualizacao = formato_dado_para('data',date('Y-m-d',strtotime($agendamento['data'].' - '.($fase_residual).' day')));
					} else {
						if($hoje == $agendamento['data']){
							$agendamento['status'] = $statusAgendamento['status-finalizado'];
						} else {
							$count_dias = 0;
							if($dias_semana)
							foreach($dias_semana as $dia_semana){
								if($dia_semana == strtolower(date('D',strtotime($agendamento['data'])))){
									break;
								}
								$count_dias++;
							}
							
							if(count($dias_semana_maximo_vagas_arr) > 1){
								$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[$count_dias];
							} else {
								$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[0];
							}
							
							$agendamentos_datas = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_agendamentos_datas',
								))
								,
								"agendamentos_datas",
								"WHERE data='".$agendamento['data']."'"
								." AND total + ".((int)$agendamento['acompanhantes']+1)." <= ".$dias_semana_maximo_vagas
							);
							
							if($agendamentos_datas){
								$confirmar = true;
								$agendamento['status'] = $statusAgendamento['status-vagas-residuais'];
							} else {
								$agendamento['status'] = $statusAgendamento['status-sem-vagas-residuais'];
							}
						}
						
						$atualizacao = formato_dado_para('data',$agendamento['data']);
					}
					
					// ===== Pegar a célula do tipo do agendamento.
					
					$cel_nome = 'cel-pre';
					
					if(!isset($pre_agendamentos_flag)){
						$pre_agendamentos = $cel['pre-agendamentos'];
						$pre_agendamentos_flag = true;
					}
					
					// ===== Montar a célula do agendamento.
					
					$cel_aux = $cel[$cel_nome];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"agendamento_id",$agendamento['id_hosts_agendamentos']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data",formato_dado_para('data',$agendamento['data']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pessoas",(1 + (int)$agendamento['acompanhantes']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",$agendamento['status']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_modificacao",formato_dado_para('dataHora',$agendamento['data_modificacao']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"atualizacao",$atualizacao);
					
					// ===== Manter ou remover o botão de confirmação para cada caso.
					
					if(!$confirmar){
						$cel_nome = 'confirmar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					// ===== Remover botões de alteração caso a data do agendamento seja hoje.
					
					if($hoje == $agendamento['data']){
						$cel_nome = 'cancelar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
						$cel_nome = 'confirmar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					// ===== Incluir a célula no seu tipo.
					
					$pre_agendamentos = modelo_var_in($pre_agendamentos,'<!-- cel-pre -->',$cel_aux);
					
					// ===== Quebrar o looping quando alcançar o limite da página.
					
					$contador++;
					if($contador >= $numRegistrosPorPagina){
						break;
					}
				}
				
				// ===== Criar o botão 'Carregar Mais' caso haja mais registros do que o máximo por página.
				
				if($numRegistros / $numRegistrosPorPagina > 1){
					$cel_aux = $cel['carregar-mais-pre'];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"numPaginas",ceil(($numRegistros / $numRegistrosPorPagina)));
					
					$pre_agendamentos = modelo_var_in($pre_agendamentos,'<!-- carregar-mais-pre -->',$cel_aux);
				}
			}
			
			// ===== Verificar os agendamentos.
			
			if($BDAgendamentos){
				// ===== Quantidade máxima de registros.
				
				$numRegistros = count($BDAgendamentos);
				$contador = 0;
				
				// ===== Varrer todos os agendamentos.
				
				foreach($BDAgendamentos as $agendamento){
					// ===== Definir o status.
					
					$agendamento['status'] = $statusAgendamento['status-confirmado'];
					
					// ===== Pegar a célula do tipo do agendamento.
					
					$cel_nome = 'cel-agendamentos';
					
					if(!isset($agendamentos_confirmados_flag)){
						$agendamentos_confirmados = $cel['agendamentos'];
						$agendamentos_confirmados_flag = true;
					}
					
					// ===== Montar a célula do agendamento.
					
					$cel_aux = $cel[$cel_nome];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"agendamento_id",$agendamento['id_hosts_agendamentos']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data",formato_dado_para('data',$agendamento['data']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"senha",$agendamento['senha']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pessoas",(1 + (int)$agendamento['acompanhantes']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",$agendamento['status']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_modificacao",formato_dado_para('dataHora',$agendamento['data_modificacao']));
					
					// ===== Remover botões de alteração caso a data do agendamento seja hoje.
					
					if($hoje == $agendamento['data']){
						$cel_nome = 'cancelar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					// ===== Incluir a célula no seu tipo.
					
					$agendamentos_confirmados = modelo_var_in($agendamentos_confirmados,'<!-- cel-agendamentos -->',$cel_aux);
					
					// ===== Quebrar o looping quando alcançar o limite da página.
					
					$contador++;
					if($contador >= $numRegistrosPorPagina){
						break;
					}
				}
				
				// ===== Criar o botão 'Carregar Mais' caso haja mais registros do que o máximo por página.
				
				if($numRegistros / $numRegistrosPorPagina > 1){
					$cel_aux = $cel['carregar-mais-agendamentos'];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"numPaginas",ceil(($numRegistros / $numRegistrosPorPagina)));
					
					$agendamentos_confirmados = modelo_var_in($agendamentos_confirmados,'<!-- carregar-mais-agendamentos -->',$cel_aux);
				}
			}
			
			// ===== Verificar os agendamentos antigos.
			
			if($BDAntigos){
				// ===== Quantidade máxima de registros.
				
				$numRegistros = count($BDAntigos);
				$contador = 0;
				
				// ===== Varrer todos os agendamentos antigos.
				
				foreach($BDAntigos as $agendamento){
					// ===== Definir o status.
					
					$agendamento['status'] = $statusAgendamento['status-finalizado'];
					
					// ===== Pegar a célula do tipo do agendamento.
					
					$cel_nome = 'cel-antigos';
					
					if(!isset($agendamentos_antigos_flag)){
						$agendamentos_antigos = $cel['agendamentos-antigos'];
						$agendamentos_antigos_flag = true;
					}
					
					// ===== Montar a célula do agendamento.
					
					$cel_aux = $cel[$cel_nome];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"agendamento_id",$agendamento['id_hosts_agendamentos']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data",formato_dado_para('data',$agendamento['data']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pessoas",(1 + (int)$agendamento['acompanhantes']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",$agendamento['status']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_modificacao",formato_dado_para('dataHora',$agendamento['data_modificacao']));
					
					// ===== Incluir a célula no seu tipo.
					
					$agendamentos_antigos = modelo_var_in($agendamentos_antigos,'<!-- cel-antigos -->',$cel_aux);
					
					// ===== Quebrar o looping quando alcançar o limite da página.
					
					$contador++;
					if($contador >= $numRegistrosPorPagina){
						break;
					}
				}
				
				// ===== Criar o botão 'Carregar Mais' caso haja mais registros do que o máximo por página.
				
				if($numRegistros / $numRegistrosPorPagina > 1){
					$cel_aux = $cel['carregar-mais-antigos'];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"numPaginas",ceil(($numRegistros / $numRegistrosPorPagina)));
					
					$agendamentos_confirmados = modelo_var_in($agendamentos_confirmados,'<!-- carregar-mais-antigos -->',$cel_aux);
				}
			}
		}
		
		// ===== Modal para mostrar pagador.
		
		$modal = gestor_componente(Array(
			'id' => 'hosts-interface-modal-informativo',
		));
		
		$modal = modelo_var_troca($modal,"#titulo#",'Agendamento Dados');
		
		$_GESTOR['pagina'] .= $modal;
		
		// ===== Montar agendamentos na página.
		
		$cel_nome = 'sem-registro'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		pagina_trocar_variavel_valor('#agendamentos_confirmados#',(isset($agendamentos_confirmados_flag) ? $agendamentos_confirmados : $cel['sem-registro'] ),true);
		pagina_trocar_variavel_valor('#pre_agendamentos#',(isset($pre_agendamentos_flag) ? $pre_agendamentos : $cel['sem-registro'] ),true);
		pagina_trocar_variavel_valor('#agendamentos-antigos#',(isset($agendamentos_antigos_flag) ? $agendamentos_antigos : $cel['sem-registro'] ),true);
		
		pagina_trocar_variavel_valor('#data_sorteio#',$fase_escolha_livre,true);
		pagina_trocar_variavel_valor('#data_confirmacao_1#',$fase_sorteio[0],true);
		pagina_trocar_variavel_valor('#data_confirmacao_2#',$fase_sorteio[1],true);
		
		// ===== Formulário validação definição padrão.
		
		$validacao = Array(
			Array(
				'regra' => 'manual',
				'campo' => 'data',
				'regrasManuais' => Array(
					Array(
						'type' => 'empty',
						'prompt' => gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','id' => 'form-msg-data')),
					),
				),
			)
		);
		
		// ===== Acompanhantes montar.
		
		$maxAcompanhantes = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','id' => 'max-acompanhantes'));
		$cel_nome = 'acompanhantes'; $cel[$cel_nome] = pagina_celula($cel_nome);
		
		for($i=0;$i<=(int)$maxAcompanhantes;$i++){
			if($i>0){
				$validacao[] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'acompanhante'.$i,
					'label' => 'Acompanhante '.$i,
				);
			}
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#num#",$i,true);
			
			pagina_celula_incluir($cel_nome,$cel_aux);
		}
		
		pagina_celula_incluir($cel_nome,'');
		
		// ===== Remover célula 'dados-agendamento'.

		$cel_nome = 'dados-agendamento'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);

		// ===== Formulário validação montar.
		
		formulario_validacao(Array(
			'formId' => 'formAgendamentos',
			'validacao' => $validacao
		));
	} else {
		// ===== Remover a célula ativo e alteracoes.
		
		$cel_nome = 'ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		$cel_nome = 'alteracoes'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		pagina_trocar_variavel_valor('msg-agendamento-suspenso',$msgAgendamentoSuspenso);
	}
	
	// ===== Tratamento de telas.
	
	if(isset($_REQUEST['tela'])){
		$_GESTOR['javascript-vars']['tela'] = $_REQUEST['tela'];
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
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('plugin');
}

// ==== Ajax

function agendamentos_ajax_dados_do_agendamento(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
	));
	
	// ===== Identificador do usuário e dados enviados na requisição.
	
	$id_hosts_usuarios = $_GESTOR['usuario-id'];
	
	$tipo = $_REQUEST['tipo'];
	$agendamento_id = $_REQUEST['agendamento_id'];
	
	// ===== Pegar células dos dados.
	
	$cel_nome = 'cel-dados'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'dados-agendamento'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	$dadosAgendamentos = $cel['dados-agendamento'];
	
	// ===== Pegar o nome completo do usuário.
	
	$usuarios = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array(
			'nome',
		),
		'extra' => 
			"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
	));
	
	$dadosAgendamentos = pagina_celula_trocar_variavel_valor($dadosAgendamentos,"seu-nome",$usuarios['nome']);
	
	// ===== Dados dos acompanhantes.
	
	$agendamentos_acompanhantes = banco_select(Array(
		'tabela' => 'agendamentos_acompanhantes',
		'campos' => Array(
			'nome',
		),
		'extra' => 
			"WHERE id_hosts_agendamentos='".$agendamento_id."'"
			." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
	));
	
	// ===== Montar a célula dos acompanhantes.
	
	$num = 0;
	
	if($agendamentos_acompanhantes){
		foreach($agendamentos_acompanhantes as $acompanhante){
			$num++;

			$cel_aux = $cel['cel-dados'];
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"num",$num);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"acompanhante",$acompanhante['nome']);
			
			$dadosAgendamentos = modelo_var_in($dadosAgendamentos,'<!-- cel-dados -->',$cel_aux);
		}
	}
	
	$_GESTOR['ajax-json'] = Array(
		'dadosAgendamentos' => $dadosAgendamentos,
		'status' => 'OK',
	);
}

function agendamentos_ajax_mais_resultados(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
	));
	
	// ===== Identificador do usuário e dados enviados na requisição.
	
	$id_hosts_usuarios = $_GESTOR['usuario-id'];
	
	$tipo = $_REQUEST['tipo'];
	$paginaAtual = banco_escape_field($_REQUEST['paginaAtual']);
	
	// ===== Quantidade de registros por página.
	
	$numRegistrosPorPagina = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['numRegistrosPorPagina'];
	
	// ===== Variáveis do módulo.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Valor time do dia de hoje.
	
	if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
	
	// ===== Status de agendamento.
	
	$config = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','conjunto' => true));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
	$fase_escolha_livre = (existe($config['fase-escolha-livre']) ? (int)$config['fase-escolha-livre'] : 7);
	$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
	$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
	
	$statusAgendamentoIDs = Array(
		'status-confirmado',
		'status-finalizado',
		'status-nao-qualificado',
		'status-novo',
		'status-qualificado',
		'status-sem-vagas-residuais',
		'status-vagas-residuais',
	);
	
	if($statusAgendamentoIDs)
	foreach($statusAgendamentoIDs as $statusID){
		$statusAgendamento[$statusID] = (existe($config[$statusID]) ? $config[$statusID] : '');
	}
	
	// ===== Verificar o tipo de agendamento.
	
	switch($tipo){
		case 'carregarMaisPre':
			$cel_nome = 'cel-pre'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
			
			$BDPreAgendamentos = banco_select(Array(
				'tabela' => 'agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'data',
					'acompanhantes',
					'status',
					'data_modificacao',
				),
				'extra' => 
					"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
					." AND status!='confirmado'"
					." AND status!='finalizado'"
					." AND data >='".$hoje."'"
					." ORDER BY data ASC"
					." LIMIT ".((int)$paginaAtual * $numRegistrosPorPagina).','.$numRegistrosPorPagina
			));
			
			if($BDPreAgendamentos){
				// ===== Registros formatação.
				
				$registros = '<!-- cel -->';
				
				// ===== Varrer todos os pré-agendamentos.
				
				foreach($BDPreAgendamentos as $agendamento){
					// ===== Definir o status.
					
					$confirmar = false;
					
					if(strtotime($agendamento['data']) > strtotime($hoje.' + '.$fase_escolha_livre.' day')){
						$agendamento['status'] = $statusAgendamento['status-novo'];
						$atualizacao = formato_dado_para('data',date('Y-m-d',strtotime($agendamento['data'].' - '.($fase_sorteio[0]).' day')));
					} else if(strtotime($agendamento['data']) > strtotime($hoje.' + '.$fase_sorteio[1].' day')){
						if($agendamento['status'] == 'qualificado' || $agendamento['status'] == 'email-enviado' || $agendamento['status'] == 'email-nao-enviado'){
							$confirmar = true;
							$agendamento['status'] = $statusAgendamento['status-qualificado'];
						} else {
							$agendamento['status'] = $statusAgendamento['status-nao-qualificado'];
						}
						
						$atualizacao = formato_dado_para('data',date('Y-m-d',strtotime($agendamento['data'].' - '.($fase_residual).' day')));
					} else {
						if($hoje == $agendamento['data']){
							$agendamento['status'] = $statusAgendamento['status-finalizado'];
						} else {
							$count_dias = 0;
							if($dias_semana)
							foreach($dias_semana as $dia_semana){
								if($dia_semana == strtolower(date('D',strtotime($agendamento['data'])))){
									break;
								}
								$count_dias++;
							}
							
							if(count($dias_semana_maximo_vagas_arr) > 1){
								$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[$count_dias];
							} else {
								$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[0];
							}
							
							$agendamentos_datas = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_agendamentos_datas',
								))
								,
								"agendamentos_datas",
								"WHERE data='".$agendamento['data']."'"
								." AND total + ".((int)$agendamento['acompanhantes']+1)." <= ".$dias_semana_maximo_vagas
							);
							
							if($agendamentos_datas){
								$confirmar = true;
								$agendamento['status'] = $statusAgendamento['status-vagas-residuais'];
							} else {
								$agendamento['status'] = $statusAgendamento['status-sem-vagas-residuais'];
							}
						}
						
						$atualizacao = formato_dado_para('data',$agendamento['data']);
					}
					
					// ===== Montar a célula do agendamento.
					
					$cel_aux = $cel['cel-pre'];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"agendamento_id",$agendamento['id_hosts_agendamentos']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data",formato_dado_para('data',$agendamento['data']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pessoas",(1 + (int)$agendamento['acompanhantes']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",$agendamento['status']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_modificacao",formato_dado_para('dataHora',$agendamento['data_modificacao']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"atualizacao",$atualizacao);
					
					// ===== Manter ou remover o botão de confirmação para cada caso.
					
					if(!$confirmar){
						$cel_nome = 'confirmar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					// ===== Remover botões de alteração caso a data do agendamento seja hoje.
					
					if($hoje == $agendamento['data']){
						$cel_nome = 'cancelar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
						$cel_nome = 'confirmar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					// ===== Incluir a célula no seu tipo.
					
					$registros = modelo_var_in($registros,'<!-- cel -->',$cel_aux);
				}
			}
		break;
		case 'carregarMaisAgendamentos':
			$cel_nome = 'cel-agendamentos'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
			
			$BDAgendamentos = banco_select(Array(
				'tabela' => 'agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'data',
					'acompanhantes',
					'senha',
					'status',
					'data_modificacao',
				),
				'extra' => 
					"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
					." AND status='confirmado'"
					." AND data >='".$hoje."'"
					." ORDER BY data ASC"
					." LIMIT ".((int)$paginaAtual * $numRegistrosPorPagina).','.$numRegistrosPorPagina
			));
			
			if($BDAgendamentos){
				// ===== Registros formatação.
				
				$registros = '<!-- cel -->';
				
				// ===== Varrer todos os agendamentos.
				
				foreach($BDAgendamentos as $agendamento){
					// ===== Definir o status.
					
					$agendamento['status'] = $statusAgendamento['status-confirmado'];
					
					// ===== Montar a célula do agendamento.
					
					$cel_aux = $cel['cel-agendamentos'];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"agendamento_id",$agendamento['id_hosts_agendamentos']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data",formato_dado_para('data',$agendamento['data']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"senha",$agendamento['senha']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pessoas",(1 + (int)$agendamento['acompanhantes']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",$agendamento['status']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_modificacao",formato_dado_para('dataHora',$agendamento['data_modificacao']));
					
					// ===== Remover botões de alteração caso a data do agendamento seja hoje.
					
					if($hoje == $agendamento['data']){
						$cel_nome = 'cancelar-btn'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					// ===== Incluir a célula no seu tipo.
					
					$registros = modelo_var_in($registros,'<!-- cel -->',$cel_aux);
				}
			}
		break;
		case 'carregarMaisAntigos':
			$cel_nome = 'cel-antigos'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
			
			$BDAntigos = banco_select(Array(
				'tabela' => 'agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'data',
					'acompanhantes',
					'status',
					'data_modificacao',
				),
				'extra' => 
					"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
					." AND (
						data <'".$hoje."' OR
						status='finalizado'
					)"
					." ORDER BY data ASC"
					." LIMIT ".((int)$paginaAtual * $numRegistrosPorPagina).','.$numRegistrosPorPagina
			));
			
			if($BDAntigos){
				// ===== Registros formatação.
				
				$registros = '<!-- cel -->';
				
				// ===== Varrer todos os agendamentos antigos.
				
				foreach($BDAntigos as $agendamento){
					// ===== Definir o status.
					
					$agendamento['status'] = $statusAgendamento['status-finalizado'];
					
					// ===== Montar a célula do agendamento.
					
					$cel_aux = $cel['cel-antigos'];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"agendamento_id",$agendamento['id_hosts_agendamentos']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data",formato_dado_para('data',$agendamento['data']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pessoas",(1 + (int)$agendamento['acompanhantes']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",$agendamento['status']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_modificacao",formato_dado_para('dataHora',$agendamento['data_modificacao']));
					
					// ===== Incluir a célula no seu tipo.
					
					$registros = modelo_var_in($registros,'<!-- cel -->',$cel_aux);
				}
			}
		break;
	}
	
	// ===== Retornar registros.
	
	$_GESTOR['ajax-json'] = Array(
		'registros' => (isset($registros) ? $registros : ''),
		'status' => 'OK',
	);
}

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

	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': agendamentos_ajax_opcao(); break;
			case 'dados-do-agendamento': agendamentos_ajax_dados_do_agendamento(); break;
			case 'mais-resultados': agendamentos_ajax_mais_resultados(); break;
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