<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'escalas';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.1.0',
	'plugin' => 'escalas',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_escalas',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_escalas',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
);

// ===== Funções Auxiliares

function escalas_calendario(){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('configuracao');
	
	$ano_inicio = date('Y');
	$hoje = date('Y-m-d');
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas'));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array()); else $datas_indisponiveis = Array();
	if(existe($config['datas-extras-disponiveis'])) $datas_extras_disponiveis = (existe($config['datas-extras-disponiveis-valores']) ? explode('|',$config['datas-extras-disponiveis-valores']) : Array()); else $datas_extras_disponiveis = Array();$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
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
		$dataFormatada = date('d/m/Y', $dia);
		$flag = false;
		$data_extra_permitida = false;
		$data_extra_posicao = 0;
		
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

function escalas_administrar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Adicionar o calendário.
	
	escalas_calendario();
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['simples']['finalizar'] = Array(
		
	);
}

function escalas_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'administrar':
			$_GESTOR['interface-opcao'] = 'simples';
		break;
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						$modulo['tabela']['data_criacao'],
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
				),
				'tabela' => Array(
					'rodape' => true,
					'colunas' => Array(
						Array(
							'id' => 'nome',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => $modulo['tabela']['data_criacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
						Array(
							'id' => $modulo['tabela']['data_modificacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
					),
				),
				'opcoes' => Array(
					'editar' => Array(
						'url' => 'editar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')),
						'icon' => 'edit',
						'cor' => 'basic blue',
					),
					'ativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'I',
						'status_mudar' => 'A',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active')),
						'icon' => 'eye slash',
						'cor' => 'basic brown',
					),
					'desativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'A',
						'status_mudar' => 'I',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')),
						'icon' => 'eye',
						'cor' => 'basic green',
					),
					'excluir' => Array(
						'opcao' => 'excluir',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
						'icon' => 'trash alternate',
						'cor' => 'basic red',
					),
				),
				'botoes' => Array(
					'adicionar' => Array(
						'url' => 'adicionar/',
						'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
						'icon' => 'plus circle',
						'cor' => 'blue',
					),
				),
			);
		break;
	}
}

// ==== Ajax

function escalas_ajax_atualizar(){
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
	
	$cel_nome = 'th-email'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	$cel_nome = 'enviado'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'nao-enviado'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'td-email'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	$cel_nome = 'cel-escala'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	// ===== Pegar a tabela da página.
	
	$cel_nome = 'tabela-pessoas'; $tabela = pagina_celula($cel_nome,false);
	
	// ===== Verificar o mês / ano alvo.
	
	$mes = date('n',strtotime($data));
	$ano = date('Y',strtotime($data));
	
	// ===== Passar o mês e o ano para inteiro.
	
	$mes = (int)$mes;
	$ano = (int)$ano;
	
	// ===== Tratar cada status enviado.
	
	switch($status){
		case 'novo':
			// ===== Pegar as hosts_escalas do mês / ano alvo.
			
			$hosts_escalas = banco_select(Array(
				'tabela' => 'hosts_escalas',
				'campos' => Array(
					'id_hosts_escalas',
					'id_hosts_usuarios',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND mes='".$mes."'"
					." AND ano='".$ano."'"
					." AND status='novo'"
			));
			
			// ===== Pegar as hosts_escalas_datas do dados do banco.
			
			$hosts_escalas_datas = banco_select(Array(
				'tabela' => 'hosts_escalas_datas',
				'campos' => Array(
					'id_hosts_escalas',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='novo'"
					." AND selecionada IS NOT NULL"
			));
			
			// ===== Varrer todas as hosts_escalas_datas.
			
			if($hosts_escalas_datas)
			foreach($hosts_escalas_datas as $escala_data){
				// ===== Pegar os dados da escala_data.
				
				$id_hosts_escalas = $escala_data['id_hosts_escalas'];
				$id_hosts_usuarios = '';
				
				// ===== Pegar o identificador do usuário da escala_data.
				
				if($hosts_escalas)
				foreach($hosts_escalas as $escala){
					if($id_hosts_escalas == $escala['id_hosts_escalas']){
						$id_hosts_usuarios = $escala['id_hosts_usuarios'];
						break;
					}
				}
				
				// ===== Pegar os dados do usuário da escala.
				
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
				
				$escalaAux = Array(
					'nome' => $hosts_usuarios['nome'],
				);
				
				// ===== Atualizar o total de pessoas escaladas.
				
				$total += 1;
				
				// ===== Incluir os dados da escala no array escalas.
				
				$escalas[] = $escalaAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($escalas, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($escalas){
				$cel_nome = 'cel-escala';
				
				foreach($escalas as $escala){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir o nome.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$escala['nome']);
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
		break;
		case 'aguardando':
			// ===== Pegar as hosts_escalas do mês / ano alvo.
			
			$hosts_escalas = banco_select(Array(
				'tabela' => 'hosts_escalas',
				'campos' => Array(
					'id_hosts_escalas',
					'id_hosts_usuarios',
					'status',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND mes='".$mes."'"
					." AND ano='".$ano."'"
					." AND (status='qualificado' OR status='email-enviado' OR status='email-nao-enviado')"
			));
			
			// ===== Pegar as hosts_escalas_datas do dados do banco.
			
			$hosts_escalas_datas = banco_select(Array(
				'tabela' => 'hosts_escalas_datas',
				'campos' => Array(
					'id_hosts_escalas',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='qualificado'"
					." AND selecionada IS NOT NULL"
			));
			
			// ===== Varrer todas as hosts_escalas_datas.
			
			if($hosts_escalas_datas)
			foreach($hosts_escalas_datas as $escala_data){
				// ===== Pegar os dados da escala_data.
				
				$id_hosts_escalas = $escala_data['id_hosts_escalas'];
				$id_hosts_usuarios = '';
				
				// ===== Pegar o identificador do usuário da escala_data.
				
				$escalaStatus = '';
				if($hosts_escalas)
				foreach($hosts_escalas as $escala){
					if($id_hosts_escalas == $escala['id_hosts_escalas']){
						$id_hosts_usuarios = $escala['id_hosts_usuarios'];
						$escalaStatus = $escala['status'];
						break;
					}
				}
				
				// ===== Pegar os dados do usuário da escala.
				
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
				
				$escalaAux = Array(
					'nome' => $hosts_usuarios['nome'],
					'status' => $escalaStatus,
				);
				
				// ===== Atualizar o total de pessoas escaladas.
				
				$total += 1;
				
				// ===== Incluir os dados da escala no array escalas.
				
				$escalas[] = $escalaAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($escalas, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($escalas){
				$cel_nome = 'th-email'; $tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->',$cel[$cel_nome]);
				
				$cel_nome = 'cel-escala';
				
				foreach($escalas as $escala){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir o status de enviado ou não enviado.
					
					$cel_aux = modelo_var_troca($cel_aux,"<!-- td-email -->",$cel['td-email']);
					
					if($escala['status'] == 'email-enviado'){
						$cel_aux = modelo_var_troca($cel_aux,"<!-- enviado -->",$cel['enviado']);
					} else {
						$cel_aux = modelo_var_troca($cel_aux,"<!-- nao-enviado -->",$cel['nao-enviado']);
					}
					
					// ===== Incluir o nome.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$escala['nome']);
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
		break;
		case 'confirmados':
			// ===== Pegar as hosts_escalas do mês / ano alvo.
			
			$hosts_escalas = banco_select(Array(
				'tabela' => 'hosts_escalas',
				'campos' => Array(
					'id_hosts_escalas',
					'id_hosts_usuarios',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND mes='".$mes."'"
					." AND ano='".$ano."'"
					." AND (status='confirmado' OR status='vaga-residual')"
			));
			
			// ===== Pegar as hosts_escalas_datas do dados do banco.
			
			$hosts_escalas_datas = banco_select(Array(
				'tabela' => 'hosts_escalas_datas',
				'campos' => Array(
					'id_hosts_escalas',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND (status='confirmado' OR status='vaga-residual')"
					." AND selecionada IS NOT NULL"
			));
			
			// ===== Varrer todas as hosts_escalas_datas.
			
			if($hosts_escalas_datas)
			foreach($hosts_escalas_datas as $escala_data){
				// ===== Pegar os dados da escala_data.
				
				$id_hosts_escalas = $escala_data['id_hosts_escalas'];
				$id_hosts_usuarios = '';
				
				// ===== Pegar o identificador do usuário da escala_data.
				
				if($hosts_escalas)
				foreach($hosts_escalas as $escala){
					if($id_hosts_escalas == $escala['id_hosts_escalas']){
						$id_hosts_usuarios = $escala['id_hosts_usuarios'];
						break;
					}
				}
				
				// ===== Pegar os dados do usuário da escala.
				
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
				
				$escalaAux = Array(
					'nome' => $hosts_usuarios['nome'],
				);
				
				// ===== Atualizar o total de pessoas escaladas.
				
				$total += 1;
				
				// ===== Incluir os dados da escala no array escalas.
				
				$escalas[] = $escalaAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($escalas, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($escalas){
				$cel_nome = 'cel-escala';
				
				foreach($escalas as $escala){
					$cel_aux = $cel[$cel_nome];
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$escala['nome']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"visto",'');
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
			
			// ===== Impressão opções.
			
			if($total > 0){
				// ===== Pegar as configurações das escalas.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-escalas'));
				
				// ===== Pegar o layout de impressão.
				
				$layoutImpressao = $config['escalas-impressao'];
				
				// ===== Montar tabela de impressão.
				
				if($escalas){
					$tabelaImpressao = $layoutImpressao;
					
					$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $tabelaImpressao = modelo_tag_in($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					
					$cel_nome = 'cel';
					
					foreach($escalas as $escala){
						$cel_aux = $cel[$cel_nome];
						
						$cel_aux = modelo_var_troca($cel_aux,"#nome#",$escala['nome']);
						
						// ===== Incluir o nome.
						
						$tabelaImpressao = modelo_var_in($tabelaImpressao,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
					
					$tabelaImpressao = modelo_var_troca($tabelaImpressao,'<!-- '.$cel_nome.' -->','');
				} else {
					$tabelaImpressao = '';
				}
				
				// ===== Variáveis padrões de impressão.
				
				$imprimir = true;
				
				$tabelaAux = $tabelaImpressao;
				
				// ===== Incluir a tabela no buffer de impressão.
				
				gestor_incluir_biblioteca(Array(
					'formato',
					'comunicacao',
				));
				
				// ===== Formatar data.
				
				$dataStr = formato_dado_para('data',$data);
				
				// ===== Pegar o componente 'impressao-cabecalho'.
				
				$impressaoCabecalho = gestor_componente(Array(
					'id' => 'impressao-cabecalho-escalas',
				));
				
				$impressaoCabecalho = modelo_var_troca($impressaoCabecalho,"#data#",$dataStr);
				$impressaoCabecalho = modelo_var_troca($impressaoCabecalho,"#total#",$total);
				
				$tabelaAux = $impressaoCabecalho . $tabelaAux;
				
				// ===== Incluir a tabela no buffer de impressão.
				
				comunicacao_impressao(Array(
					'titulo' => 'Escalas Confirmadas / Vagas Residuais - '.$dataStr,
					'pagina' => $tabelaAux,
				));
			}
		break;
		case 'nao-confirmados':
			// ===== Pegar as hosts_escalas do mês / ano alvo.
			
			$hosts_escalas = banco_select(Array(
				'tabela' => 'hosts_escalas',
				'campos' => Array(
					'id_hosts_escalas',
					'id_hosts_usuarios',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND mes='".$mes."'"
					." AND ano='".$ano."'"
					." AND status='nao-confirmados'"
			));
			
			// ===== Pegar as hosts_escalas_datas do dados do banco.
			
			$hosts_escalas_datas = banco_select(Array(
				'tabela' => 'hosts_escalas_datas',
				'campos' => Array(
					'id_hosts_escalas',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='nao-confirmados'"
					." AND selecionada IS NOT NULL"
			));
			
			// ===== Varrer todas as hosts_escalas_datas.
			
			if($hosts_escalas_datas)
			foreach($hosts_escalas_datas as $escala_data){
				// ===== Pegar os dados da escala_data.
				
				$id_hosts_escalas = $escala_data['id_hosts_escalas'];
				$id_hosts_usuarios = '';
				
				// ===== Pegar o identificador do usuário da escala_data.
				
				if($hosts_escalas)
				foreach($hosts_escalas as $escala){
					if($id_hosts_escalas == $escala['id_hosts_escalas']){
						$id_hosts_usuarios = $escala['id_hosts_usuarios'];
						break;
					}
				}
				
				// ===== Pegar os dados do usuário da escala.
				
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
				
				$escalaAux = Array(
					'nome' => $hosts_usuarios['nome'],
				);
				
				// ===== Atualizar o total de pessoas escaladas.
				
				$total += 1;
				
				// ===== Incluir os dados da escala no array escalas.
				
				$escalas[] = $escalaAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($escalas, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($escalas){
				$cel_nome = 'cel-escala';
				
				foreach($escalas as $escala){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir o nome.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$escala['nome']);
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
		break;
		case 'cancelados':
			// ===== Pegar as hosts_escalas do mês / ano alvo.
			
			$hosts_escalas = banco_select(Array(
				'tabela' => 'hosts_escalas',
				'campos' => Array(
					'id_hosts_escalas',
					'id_hosts_usuarios',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND mes='".$mes."'"
					." AND ano='".$ano."'"
					." AND status='cancelado'"
			));
			
			// ===== Pegar as hosts_escalas_datas do dados do banco.
			
			$hosts_escalas_datas = banco_select(Array(
				'tabela' => 'hosts_escalas_datas',
				'campos' => Array(
					'id_hosts_escalas',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='cancelado'"
					." AND selecionada IS NOT NULL"
			));
			
			// ===== Varrer todas as hosts_escalas_datas.
			
			if($hosts_escalas_datas)
			foreach($hosts_escalas_datas as $escala_data){
				// ===== Pegar os dados da escala_data.
				
				$id_hosts_escalas = $escala_data['id_hosts_escalas'];
				$id_hosts_usuarios = '';
				
				// ===== Pegar o identificador do usuário da escala_data.
				
				if($hosts_escalas)
				foreach($hosts_escalas as $escala){
					if($id_hosts_escalas == $escala['id_hosts_escalas']){
						$id_hosts_usuarios = $escala['id_hosts_usuarios'];
						break;
					}
				}
				
				// ===== Pegar os dados do usuário da escala.
				
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
				
				$escalaAux = Array(
					'nome' => $hosts_usuarios['nome'],
				);
				
				// ===== Atualizar o total de pessoas escaladas.
				
				$total += 1;
				
				// ===== Incluir os dados da escala no array escalas.
				
				$escalas[] = $escalaAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($escalas, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($escalas){
				$cel_nome = 'cel-escala';
				
				foreach($escalas as $escala){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir o nome.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$escala['nome']);
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
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

function escalas_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'atualizar': escalas_ajax_atualizar(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		escalas_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'administrar': escalas_administrar(); break;
		}
		
		interface_finalizar();
	}
}

escalas_start();

?>