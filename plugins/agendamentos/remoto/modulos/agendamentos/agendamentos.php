<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'agendamentos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.60',
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
		'formulario',
	));
	
	// ===== Solicitação de criar agendamento.
	
	if(isset($_REQUEST['agendar'])){
		// ===== Tratar dados enviados.
		
		$agendamentoData = banco_escape_field(formato_dado_para('date',$_REQUEST['data']));
		$acompanhantes = banco_escape_field($_REQUEST['acompanhantes']);
		
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
			),
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				case 'AGENDAMENTO_INATIVO':
				case 'AGENDAMENTO_DATA_NAO_PERMITIDA':
				case 'AGENDAMENTO_MULTIPLO_NAO_PERMITIDO':
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
		// ===== Remover a célula inativo.
		
		$cel_nome = 'inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Montagem do calendário.
		
		agendamentos_calendario();
		
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
		
		// ===== Formulário validação montar.
		
		formulario_validacao(Array(
			'formId' => 'formAgendamentos',
			'validacao' => $validacao
		));
	} else {
		// ===== Remover a célula ativo.
		
		$cel_nome = 'ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
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