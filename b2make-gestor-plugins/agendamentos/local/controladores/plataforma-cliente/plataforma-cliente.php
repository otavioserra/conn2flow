<?php

// ===== Plataforma responsável por receber solicitações do 'cliente'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-cliente-plugin';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'forcarDataHoje' => false,
	'dataHojeForcada' => '2023-01-18',
);

// =========================== Funções Auxiliares

function plataforma_cliente_plugin_data_permitida($data){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formato');
	
	// ===== Variáveis do módulo.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
	
	// ===== .
	
	$ano_inicio = date('Y');
	
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
	
	$primeiro_dia = strtotime(date("Y-m-d", mktime()) . " + 1 day");
	$ultimo_dia = strtotime(date("Y-m-d", mktime()) . " + ".$anos." year");
	
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
				$dia >= strtotime($hoje.' + '.($fase_sorteio[1]+1).' day') &&
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
		
		$dia += 86400;
	} while ($dia < $ultimo_dia);
	
	return false;
}

function plataforma_cliente_plugin_data_agendamento_confirmar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host.
	// id_hosts_agendamentos - Int - Obrigatório - Identificador do agendamento.
	// id_hosts_usuarios - Int - Obrigatório - Identificador do usuário.
	// data - String - Obrigatório - Data do agendamento.
	
	// ===== 
	
	// ===== Pegar os dados de configuração.
	
	gestor_incluir_biblioteca('configuracao');
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
	
	// ===== Pegar dados do agendamento.
	
	$hosts_agendamentos = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_agendamentos',
		'campos' => Array(
			'acompanhantes',
			'pubID',
			'status',
			'senha',
		),
		'extra' => 
			"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	$acompanhantes = (int)$hosts_agendamentos['acompanhantes'];
	$status = $hosts_agendamentos['status'];
	$senha = $hosts_agendamentos['senha'];
	
	// ===== Pegar os dados dos acompanhantes.
	
	$hosts_agendamentos_acompanhantes = banco_select(Array(
		'tabela' => 'hosts_agendamentos_acompanhantes',
		'campos' => Array(
			'nome',
		),
		'extra' => 
			"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
			." AND id_hosts='".$id_hosts."'"
			." AND id_hosts_agendamentos='".$id_hosts_agendamentos."'"
			." ORDER BY nome ASC"
	));
	
	if($hosts_agendamentos_acompanhantes)
	foreach($hosts_agendamentos_acompanhantes as $acompanhante){
		$acompanhantesNomes[] = $acompanhante['nome'];
	}
	
	// ===== Gerar o token de validação.
	
	gestor_incluir_biblioteca('autenticacao');
	
	$validacao = autenticacao_cliente_gerar_token_validacao(Array(
		'id_hosts' => $id_hosts,
		'pubID' => ($hosts_agendamentos['pubID'] ? $hosts_agendamentos['pubID'] : null),
	));
	
	$token = $validacao['token'];
	
	// ===== Verificar se já foi confirmado. Caso tenha sido confirmado, só alertar e enviar email ao usuário. Senão, fazer o procedimento de confirmação.
	
	if($status != 'confirmado'){
		// ===== Pegar a quantidade de vagas máxima.
		
		$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
		$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
		
		$count_dias = 0;
		if($dias_semana)
		foreach($dias_semana as $dia_semana){
			if($dia_semana == strtolower(date('D',strtotime($data)))){
				break;
			}
			$count_dias++;
		}
		
		if(count($dias_semana_maximo_vagas_arr) > 1){
			$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[$count_dias];
		} else {
			$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[0];
		}
		
		// ===== Verificar se há vagas suficientes para a data requerida. Caso não tenha, retornar mensagem de erro.
		
		$hosts_agendamentos_datas = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_agendamentos_datas',
			'campos' => Array(
				'id_hosts_agendamentos_datas',
				'total',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
				." AND data='".$data."'"
				." AND total + ".($acompanhantes+1)." <= ".$dias_semana_maximo_vagas
		));
		
		if(!$hosts_agendamentos_datas){
			$msgAgendamentoSemVagas = (existe($config['msg-agendamento-sem-vagas']) ? $config['msg-agendamento-sem-vagas'] : '');
			
			return Array(
				'confirmado' => false,
				'status' => 'AGENDAMENTO_SEM_VAGAS',
				'alerta' => $msgAgendamentoSemVagas,
			);
		}
		
		// ===== Atualizar a quantidade total de vagas utilizadas em agendamentos para a data em questão.
		
		banco_update_campo('total','total+'.($acompanhantes+1),true);
		
		banco_update_executar('hosts_agendamentos_datas',"WHERE id_hosts_agendamentos_datas='".$hosts_agendamentos_datas['id_hosts_agendamentos_datas']."'");
		
		// ===== Gerar senha do agendamento.
		
		gestor_incluir_biblioteca('formato');
		
		$senha = formato_colocar_char_meio_numero(formato_zero_a_esquerda(rand(1,99999),6));
		
		// ===== Atualizar agendamento.
		
		banco_update_campo('senha',$senha);
		banco_update_campo('status','confirmado');
		banco_update_campo('versao','versao+1',true);
		banco_update_campo('data_modificacao','NOW()',true);
		
		banco_update_executar('hosts_agendamentos',"WHERE id_hosts='".$id_hosts."' AND id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
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
	
	$agendamentoAssunto = (existe($config['agendamento-assunto']) ? $config['agendamento-assunto'] : '');
	$agendamentoMensagem = (existe($config['agendamento-mensagem']) ? $config['agendamento-mensagem'] : '');
	$msgConclusaoAgendamento = (existe($config['msg-conclusao-agendamento']) ? $config['msg-conclusao-agendamento'] : '');
	
	$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
	
	$email = $hosts_usuarios['email'];
	$nome = $hosts_usuarios['nome'];
	
	gestor_incluir_biblioteca('formato');
	
	$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_agendamentos,6);
	
	// ===== Formatar mensagem do email.
	
	gestor_incluir_biblioteca('host');
	
	$agendamentoAssunto = modelo_var_troca_tudo($agendamentoAssunto,"#codigo#",$codigo);
	
	$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#codigo#",$codigo);
	$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#titulo#",$tituloEstabelecimento);
	$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#data#",formato_dado_para('data',$data));
	$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#senha#",$senha);
	$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#url-cancelamento#",'<a target="agendamento" href="'.host_url(Array('opcao'=>'full')).'agendamentos-publico/?acao=cancelar&token='.$token.'" style="overflow-wrap: break-word;">'.host_url(Array('opcao'=>'full')).'agendamentos-publico/?acao=cancelar&token='.$token.'</a>');
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($agendamentoMensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $agendamentoMensagem = modelo_tag_in($agendamentoMensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$agendamentoMensagem = modelo_var_troca($agendamentoMensagem,"#seu-nome#",$nome);
	
	for($i=0;$i<(int)$acompanhantes;$i++){
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = modelo_var_troca($cel_aux,"#num#",($i+1));
		$cel_aux = modelo_var_troca($cel_aux,"#acompanhante#",$acompanhantesNomes[$i]);
		
		$agendamentoMensagem = modelo_var_in($agendamentoMensagem,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	$agendamentoMensagem = modelo_var_troca($agendamentoMensagem,'<!-- '.$cel_nome.' -->','');
	
	// ===== Formatar mensagem do alerta.
	
	$msgConclusaoAgendamento = modelo_var_troca_tudo($msgConclusaoAgendamento,"#data#",formato_dado_para('data',$data));
	$msgConclusaoAgendamento = modelo_var_troca_tudo($msgConclusaoAgendamento,"#senha#",$senha);
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($msgConclusaoAgendamento,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $msgConclusaoAgendamento = modelo_tag_in($msgConclusaoAgendamento,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$msgConclusaoAgendamento = modelo_var_troca($msgConclusaoAgendamento,"#seu-nome#",$nome);
	
	for($i=0;$i<(int)$acompanhantes;$i++){
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = modelo_var_troca($cel_aux,"#num#",($i+1));
		$cel_aux = modelo_var_troca($cel_aux,"#acompanhante#",$acompanhantesNomes[$i]);
		
		$msgConclusaoAgendamento = modelo_var_in($msgConclusaoAgendamento,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	$msgConclusaoAgendamento = modelo_var_troca($msgConclusaoAgendamento,'<!-- '.$cel_nome.' -->','');
	
	$msgAlerta = $msgConclusaoAgendamento;
	
	// ===== Enviar email com informações do agendamento.
	
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
			'assunto' => $agendamentoAssunto,
			'html' => $agendamentoMensagem,
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
	
	return Array(
		'confirmado' => true,
		'alerta' => $msgAlerta,
	);
}

function plataforma_cliente_plugin_data_agendamento_cancelar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host.
	// id_hosts_agendamentos - Int - Obrigatório - Identificador do agendamento.
	// id_hosts_usuarios - Int - Obrigatório - Identificador do usuário.
	// data - String - Obrigatório - Data do agendamento.
	
	// ===== 
	
	// ===== Pegar os dados de configuração.
	
	gestor_incluir_biblioteca('configuracao');
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
	
	// ===== Pegar dados do agendamento.
	
	$hosts_agendamentos = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_agendamentos',
		'campos' => Array(
			'acompanhantes',
			'status',
		),
		'extra' => 
			"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	$acompanhantes = (int)$hosts_agendamentos['acompanhantes'];
	$status = $hosts_agendamentos['status'];
	
	// ===== Verificar se já foi confirmado. Caso tenha sido confirmado, atualizar a quantidade total de vagas.
	
	if($status == 'confirmado'){
		// ===== Pegar o identificador do 'hosts_agendamentos_datas'.
		
		$hosts_agendamentos_datas = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_agendamentos_datas',
			'campos' => Array(
				'id_hosts_agendamentos_datas',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
				." AND data='".$data."'"
		));
		
		// ===== Atualizar a quantidade total de vagas utilizadas em agendamentos para a data em questão.
		
		if($hosts_agendamentos_datas){
			
			banco_update_campo('total','total-'.($acompanhantes+1),true);
			
			banco_update_executar('hosts_agendamentos_datas',"WHERE id_hosts_agendamentos_datas='".$hosts_agendamentos_datas['id_hosts_agendamentos_datas']."'");
		}
	}
	
	// ===== Atualizar agendamento.
	
	banco_update_campo('status','finalizado');
	banco_update_campo('versao','versao+1',true);
	banco_update_campo('data_modificacao','NOW()',true);
	
	banco_update_executar('hosts_agendamentos',"WHERE id_hosts='".$id_hosts."' AND id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
	
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
	
	$desagendamentoAssunto = (existe($config['desagendamento-assunto']) ? $config['desagendamento-assunto'] : '');
	$desagendamentoMensagem = (existe($config['desagendamento-mensagem']) ? $config['desagendamento-mensagem'] : '');
	$msgAgendamentoCancelado = (existe($config['msg-agendamento-cancelado']) ? $config['msg-agendamento-cancelado'] : '');
	
	$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
	
	$email = $hosts_usuarios['email'];
	$nome = $hosts_usuarios['nome'];
	
	gestor_incluir_biblioteca('formato');
	
	$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_agendamentos,6);
	
	// ===== Formatar mensagem do email.
	
	gestor_incluir_biblioteca('host');
	
	$desagendamentoAssunto = modelo_var_troca_tudo($desagendamentoAssunto,"#codigo#",$codigo);
	
	$desagendamentoMensagem = modelo_var_troca_tudo($desagendamentoMensagem,"#codigo#",$codigo);
	$desagendamentoMensagem = modelo_var_troca_tudo($desagendamentoMensagem,"#titulo#",$tituloEstabelecimento);
	$desagendamentoMensagem = modelo_var_troca_tudo($desagendamentoMensagem,"#data#",formato_dado_para('data',$data));
	
	// ===== Formatar mensagem do alerta.
	
	$msgAlerta = $msgAgendamentoCancelado;
	
	// ===== Enviar email com informações do agendamento.
	
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
			'assunto' => $desagendamentoAssunto,
			'html' => $desagendamentoMensagem,
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
	
	return Array(
		'cancelado' => true,
		'alerta' => $msgAlerta,
	);
}

// =========================== Funções da Plataforma

function plataforma_cliente_plugin_agendamentos(){
	global $_GESTOR;
	
	// ===== Variáveis do módulo.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
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
				
				$id_hosts_usuarios = banco_escape_field($dados['usuarioID']);
				
				$agendamentoData = $dados['agendamentoData'];
				$acompanhantes = (int)$dados['acompanhantes'];
				$acompanhantesNomes = $dados['acompanhantesNomes'];
				
				for($i=0;$i<(int)$acompanhantes;$i++){
					$acompanhantesNomes[$i] = trim(ucwords(strtolower($acompanhantesNomes[$i])));
				}
				
				// ===== Verificar se a data enviada é permitida. Senão for retornar mensagem de erro.
				
				if(!plataforma_cliente_plugin_data_permitida($agendamentoData)){
					$msgAgendamentoDataNaoPermitida = (existe($config['msg-agendamento-data-nao-permitida']) ? $config['msg-agendamento-data-nao-permitida'] : '');
					
					return Array(
						'status' => 'AGENDAMENTO_DATA_NAO_PERMITIDA',
						'error-msg' => $msgAgendamentoDataNaoPermitida,
					);
				}
				
				// ===== Criar data no agendamento_datas caso não exista.
				
				$hosts_agendamentos_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos_datas',
					'campos' => Array(
						'id_hosts_agendamentos_datas',
					),
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND data='".$agendamentoData."'"
				));
				
				if(!$hosts_agendamentos_datas){
					banco_insert_name_campo('id_hosts',$id_hosts);
					banco_insert_name_campo('data',$agendamentoData);
					banco_insert_name_campo('total','0',true);
					banco_insert_name_campo('status','novo');
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_agendamentos_datas"
					);
				}
				
				// ===== Gerar o token de validação.
				
				gestor_incluir_biblioteca('autenticacao');
				
				$validacao = autenticacao_cliente_gerar_token_validacao(Array(
					'id_hosts' => $id_hosts,
				));
				
				$token = $validacao['token'];
				$pubID = $validacao['pubID'];
				
				// ===== Verificar agendamento do usuário para a data enviada.
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => Array(
						'id_hosts_agendamentos',
						'status',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
						." AND data='".$agendamentoData."'"
				));
				
				// ===== Verificar o cupom de prioridade.
				
				if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
				
				if(isset($dados['cupom']))
				if(existe($dados['cupom'])){
					$cupom = banco_escape_field($dados['cupom']);
					
					$hosts_cupons_prioridade = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_cupons_prioridade',
						'campos' => Array(
							'id_hosts_cupons_prioridade',
							'id_hosts_conjunto_cupons_prioridade',
							'id_hosts_agendamentos',
						),
						'extra' => 
							"WHERE id_hosts='".$id_hosts."'"
							." AND codigo='".$cupom."'"
					));
					
					// ===== Verificar se o cupom foi encontrado. Senão retornar erro de não encontrado.
					
					if($hosts_cupons_prioridade){
						$id_hosts_conjunto_cupons_prioridade = $hosts_cupons_prioridade['id_hosts_conjunto_cupons_prioridade'];
						$id_hosts_cupons_prioridade = $hosts_cupons_prioridade['id_hosts_cupons_prioridade'];
						$id_hosts_agendamentos_cupom_utilizado = $hosts_cupons_prioridade['id_hosts_agendamentos'];
						
						$hosts_conjunto_cupons_prioridade = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_conjunto_cupons_prioridade',
							'campos' => Array(
								'valido_de',
								'valido_ate',
								'status',
							),
							'extra' => 
								"WHERE id_hosts='".$id_hosts."'"
								." AND id_hosts_conjunto_cupons_prioridade='".$id_hosts_conjunto_cupons_prioridade."'"
						));
						
						if($hosts_conjunto_cupons_prioridade){
							// ===== Verificar se o cupom está ativo. Senão retornar erro cupom inativo.
							
							if($hosts_conjunto_cupons_prioridade['status'] != 'A'){
								$msgCupomPrioridadeInativo = (existe($config['msg-cupom-prioridade-inativo']) ? $config['msg-cupom-prioridade-inativo'] : '');
								
								$msgCupomPrioridadeInativo = modelo_var_troca_tudo($msgCupomPrioridadeInativo,"#cupom#",$cupom);

								return Array(
									'status' => 'CUPOM_PRIORIDADE_INATIVO',
									'error-msg' => $msgCupomPrioridadeInativo,
								);
							}
							
							// ===== Verificar se o cupom está dentro do prazo de validade. Senão retornar erro de vecimento.
							
							if(
								strtotime($hosts_conjunto_cupons_prioridade['valido_de']) <= strtotime($hoje) && 
								strtotime($hosts_conjunto_cupons_prioridade['valido_ate']) >= strtotime($hoje)
							){
								
							} else {
								gestor_incluir_biblioteca('formato');
								
								$valido_de = formato_data_from_datetime_to_text($hosts_conjunto_cupons_prioridade['valido_de']);
								$valido_ate = formato_data_from_datetime_to_text($hosts_conjunto_cupons_prioridade['valido_ate']);
								
								$msgCupomPrioridadeVencido = (existe($config['msg-cupom-prioridade-vencido']) ? $config['msg-cupom-prioridade-vencido'] : '');
								
								$msgCupomPrioridadeVencido = modelo_var_troca_tudo($msgCupomPrioridadeVencido,"#cupom#",$cupom);
								$msgCupomPrioridadeVencido = modelo_var_troca_tudo($msgCupomPrioridadeVencido,"#valido_de#",$valido_de);
								$msgCupomPrioridadeVencido = modelo_var_troca_tudo($msgCupomPrioridadeVencido,"#valido_ate#",$valido_ate);
								
								return Array(
									'status' => 'CUPOM_PRIORIDADE_VENCIDO',
									'error-msg' => $msgCupomPrioridadeVencido,
								);
							}
							
							// ===== Verificar se o cupom já foi usado em outro agendamento. Se for, retornar erro de cupom já utilizado.
							
							if($id_hosts_agendamentos_cupom_utilizado){
								$msgCupomPrioridadeJaUtilizado = (existe($config['msg-cupom-prioridade-ja-utilizado']) ? $config['msg-cupom-prioridade-ja-utilizado'] : '');
								
								$msgCupomPrioridadeJaUtilizado = modelo_var_troca_tudo($msgCupomPrioridadeJaUtilizado,"#cupom#",$cupom);
								
								return Array(
									'status' => 'CUPOM_PRIORIDADE_JA_UTILIZADO',
									'error-msg' => $msgCupomPrioridadeJaUtilizado,
								);
							}
							
							// ===== Cupom válido, marcar para incluir o cupom.
							
							$cupomValido = $id_hosts_cupons_prioridade;
							$agendamentoConfirmar = true;
						} else {
							$cupomNaoEncontrado = true;
						}
					} else {
						$cupomNaoEncontrado = true;
					}
				}
				
				if(isset($cupomNaoEncontrado)){
					$msgCupomPrioridadeNaoEncontrado = (existe($config['msg-cupom-prioridade-nao-encontrado']) ? $config['msg-cupom-prioridade-nao-encontrado'] : '');
					
					$msgCupomPrioridadeNaoEncontrado = modelo_var_troca_tudo($msgCupomPrioridadeNaoEncontrado,"#cupom#",$cupom);
					
					return Array(
						'status' => 'CUPOM_PRIORIDADE_NAO_ENCONTRADO',
						'error-msg' => $msgCupomPrioridadeNaoEncontrado,
					);
				}
				
				// ===== Verificar se está na fase residual ou pré-agendamento (fase de sorteio é tratada na função anterior 'data_permitida'). Tratar cada caso de forma diferente.
				
				$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
				
				if(strtotime($agendamentoData) <= strtotime($hoje.' + '.$fase_residual.' day')){
					$agendamentoConfirmar = true;
				}
				
				// ===== Confirmar agendamento ou criar pré-agendamento.
				
				if(isset($agendamentoConfirmar)){
					// ===== Verificar se já tem agendamento confirmado para esta data. Caso tenha, retornar erro e mensagem de permissão apenas de um agendamento por data.
					
					if($hosts_agendamentos){
						if($hosts_agendamentos['status'] == 'confirmado'){
							$msgAgendamentoJaExiste = (existe($config['msg-agendamento-ja-existe']) ? $config['msg-agendamento-ja-existe'] : '');
							
							return Array(
								'status' => 'AGENDAMENTO_MULTIPLO_NAO_PERMITIDO',
								'error-msg' => $msgAgendamentoJaExiste,
							);
						} else {
							$atualizarAgendamento = true;
						}
					}
					
					// ===== Pegar a quantidade de vagas máxima.
					
					$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
					$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
					
					$count_dias = 0;
					if($dias_semana)
					foreach($dias_semana as $dia_semana){
						if($dia_semana == strtolower(date('D',strtotime($agendamentoData)))){
							break;
						}
						$count_dias++;
					}
					
					if(count($dias_semana_maximo_vagas_arr) > 1){
						$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[$count_dias];
					} else {
						$dias_semana_maximo_vagas = $dias_semana_maximo_vagas_arr[0];
					}
					
					// ===== Verificar se há vagas suficientes para a data requerida. Caso não tenha, retornar mensagem de erro.
					
					$hosts_agendamentos_datas = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_agendamentos_datas',
						'campos' => Array(
							'id_hosts_agendamentos_datas',
							'total',
						),
						'extra' => 
							"WHERE id_hosts='".$id_hosts."'"
							." AND data='".$agendamentoData."'"
							." AND total + ".($acompanhantes+1)." <= ".$dias_semana_maximo_vagas
					));
					
					if(!$hosts_agendamentos_datas){
						// ===== Vagas disponíveis.
						
						$hosts_agendamentos_datas = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_agendamentos_datas',
							'campos' => Array(
								'total',
							),
							'extra' => 
								"WHERE id_hosts='".$id_hosts."'"
								." AND data='".$agendamentoData."'"
						));
						
						$vagas = (int)$dias_semana_maximo_vagas - (int)$hosts_agendamentos_datas['total'];
						if($vagas < 0) $vagas = 0;
						
						// ===== Alerta.
						
						$msgAgendamentoSemVagas = (existe($config['msg-agendamento-sem-vagas']) ? $config['msg-agendamento-sem-vagas'] : '');
						
						gestor_incluir_biblioteca('formato');
						
						$msgAgendamentoSemVagas = modelo_var_troca_tudo($msgAgendamentoSemVagas,"#data#",formato_dado_para('data',$agendamentoData));
						$msgAgendamentoSemVagas = modelo_var_troca_tudo($msgAgendamentoSemVagas,"#vagas#",$vagas);
						
						return Array(
							'status' => 'AGENDAMENTO_SEM_VAGAS',
							'error-msg' => $msgAgendamentoSemVagas,
						);
					}
					
					// ===== Atualizar a quantidade total de vagas utilizadas em agendamentos para a data em questão.
					
					banco_update_campo('total','total+'.($acompanhantes+1),true);
					
					banco_update_executar('hosts_agendamentos_datas',"WHERE id_hosts_agendamentos_datas='".$hosts_agendamentos_datas['id_hosts_agendamentos_datas']."'");
					
					$atualizarAgendamentosDatas = true;
					
					// ===== Gerar senha do agendamento.
					
					gestor_incluir_biblioteca('formato');
					
					$senha = formato_colocar_char_meio_numero(formato_zero_a_esquerda(rand(1,99999),6));
					
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
					
					// ===== Gerar o agendamento ou atualizar um já existente.
					
					if(isset($atualizarAgendamento)){
						$id_hosts_agendamentos = $hosts_agendamentos['id_hosts_agendamentos'];
						
						// ===== Substituir acompanhantes.
						
						banco_delete
						(
							"hosts_agendamentos_acompanhantes",
							"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
							." AND id_hosts='".$id_hosts."'"
						);
						
						for($i=0;$i<(int)$acompanhantes;$i++){
							banco_insert_name_campo('id_hosts',$id_hosts);
							banco_insert_name_campo('id_hosts_agendamentos',$id_hosts_agendamentos);
							banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
							banco_insert_name_campo('nome',$acompanhantesNomes[$i]);
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"hosts_agendamentos_acompanhantes"
							);
						}
						
						// ===== Atualizar agendamento.
						
						banco_update_campo('acompanhantes',$acompanhantes);
						banco_update_campo('senha',$senha);
						banco_update_campo('status','confirmado');
						banco_update_campo('pubID',$pubID);
						banco_update_campo('versao','versao+1',true);
						banco_update_campo('data_modificacao','NOW()',true);
						
						banco_update_executar('hosts_agendamentos',"WHERE id_hosts='".$id_hosts."' AND id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					} else {
						// ===== Criar novo agendamento.
						
						banco_insert_name_campo('id_hosts',$id_hosts);
						banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
						banco_insert_name_campo('data',$agendamentoData);
						banco_insert_name_campo('acompanhantes',$acompanhantes);
						banco_insert_name_campo('senha',$senha);
						banco_insert_name_campo('status','confirmado');
						banco_insert_name_campo('pubID',$pubID);
						banco_insert_name_campo('versao','1',true);
						banco_insert_name_campo('data_criacao','NOW()',true);
						banco_insert_name_campo('data_modificacao','NOW()',true);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"hosts_agendamentos"
						);
						
						$id_hosts_agendamentos = banco_last_id();
						
						// ===== Criar acompanhantes do agendamento caso houver.
						
						if((int)$acompanhantes > 0){
							for($i=0;$i<(int)$acompanhantes;$i++){
								banco_insert_name_campo('id_hosts',$id_hosts);
								banco_insert_name_campo('id_hosts_agendamentos',$id_hosts_agendamentos);
								banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
								banco_insert_name_campo('nome',$acompanhantesNomes[$i]);
								
								banco_insert_name
								(
									banco_insert_name_campos(),
									"hosts_agendamentos_acompanhantes"
								);
							}
						}
					}
					
					// ===== Verificar se houve uso de cupom. Se sim, marcar o cupom com o identificador do agendamento.
					
					if(isset($cupomValido)){
						$id_hosts_cupons_prioridade = $cupomValido;
						
						banco_update_campo('id_hosts_agendamentos',$id_hosts_agendamentos);
						
						banco_update_executar('hosts_cupons_prioridade',"WHERE id_hosts_cupons_prioridade='".$id_hosts_cupons_prioridade."' AND id_hosts='".$id_hosts."'");
					}
					
					// ===== Formatar dados do email.
					
					$agendamentoAssunto = (existe($config['agendamento-assunto']) ? $config['agendamento-assunto'] : '');
					$agendamentoMensagem = (existe($config['agendamento-mensagem']) ? $config['agendamento-mensagem'] : '');
					$msgConclusaoAgendamento = (existe($config['msg-conclusao-agendamento']) ? $config['msg-conclusao-agendamento'] : '');
					
					$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
					
					$email = $hosts_usuarios['email'];
					$nome = $hosts_usuarios['nome'];
					
					$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_agendamentos,6);
					
					// ===== Formatar mensagem do email.
					
					gestor_incluir_biblioteca('host');
					
					$agendamentoAssunto = modelo_var_troca_tudo($agendamentoAssunto,"#codigo#",$codigo);
					
					$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#codigo#",$codigo);
					$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#titulo#",$tituloEstabelecimento);
					$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#data#",formato_dado_para('data',$agendamentoData));
					$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#senha#",$senha);
					$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#url-cancelamento#",'<a target="agendamento" href="'.host_url(Array('opcao'=>'full')).'agendamentos-publico/?acao=cancelar&token='.$token.'" style="overflow-wrap: break-word;">'.host_url(Array('opcao'=>'full')).'agendamentos-publico/?acao=cancelar&token='.$token.'</a>');
					
					$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($agendamentoMensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $agendamentoMensagem = modelo_tag_in($agendamentoMensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					
					$agendamentoMensagem = modelo_var_troca($agendamentoMensagem,"#seu-nome#",$nome);
					
					for($i=0;$i<(int)$acompanhantes;$i++){
						$cel_aux = $cel[$cel_nome];
						
						$cel_aux = modelo_var_troca($cel_aux,"#num#",($i+1));
						$cel_aux = modelo_var_troca($cel_aux,"#acompanhante#",$acompanhantesNomes[$i]);
						
						$agendamentoMensagem = modelo_var_in($agendamentoMensagem,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
					$agendamentoMensagem = modelo_var_troca($agendamentoMensagem,'<!-- '.$cel_nome.' -->','');
					
					// ===== Formatar mensagem do alerta.
					
					$msgConclusaoAgendamento = modelo_var_troca_tudo($msgConclusaoAgendamento,"#data#",formato_dado_para('data',$agendamentoData));
					$msgConclusaoAgendamento = modelo_var_troca_tudo($msgConclusaoAgendamento,"#senha#",$senha);
					
					$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($msgConclusaoAgendamento,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $msgConclusaoAgendamento = modelo_tag_in($msgConclusaoAgendamento,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					
					$msgConclusaoAgendamento = modelo_var_troca($msgConclusaoAgendamento,"#seu-nome#",$nome);
					
					for($i=0;$i<(int)$acompanhantes;$i++){
						$cel_aux = $cel[$cel_nome];
						
						$cel_aux = modelo_var_troca($cel_aux,"#num#",($i+1));
						$cel_aux = modelo_var_troca($cel_aux,"#acompanhante#",$acompanhantesNomes[$i]);
						
						$msgConclusaoAgendamento = modelo_var_in($msgConclusaoAgendamento,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
					$msgConclusaoAgendamento = modelo_var_troca($msgConclusaoAgendamento,'<!-- '.$cel_nome.' -->','');
					
					$msgAlerta = $msgConclusaoAgendamento;
					
					// ===== Enviar email com informações do agendamento.
					
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
							'assunto' => $agendamentoAssunto,
							'html' => $agendamentoMensagem,
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
				} else {
					// ===== Verificar se já tem agendamento para esta data. Caso tenha, retornar erro e mensagem de permissão apenas de um agendamento por data.
					
					if($hosts_agendamentos){
						if($hosts_agendamentos['status'] != 'finalizado'){
							$msgAgendamentoJaExiste = (existe($config['msg-agendamento-ja-existe']) ? $config['msg-agendamento-ja-existe'] : '');
							
							return Array(
								'status' => 'AGENDAMENTO_MULTIPLO_NAO_PERMITIDO',
								'error-msg' => $msgAgendamentoJaExiste,
							);
						} else {
							$atualizarAgendamento = true;
						}
					}
					
					// ===== Gerar o agendamento ou atualizar um já existente.
					
					if(isset($atualizarAgendamento)){
						$id_hosts_agendamentos = $hosts_agendamentos['id_hosts_agendamentos'];
						
						// ===== Substituir acompanhantes.
						
						banco_delete
						(
							"hosts_agendamentos_acompanhantes",
							"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
							." AND id_hosts='".$id_hosts."'"
						);
						
						for($i=0;$i<(int)$acompanhantes;$i++){
							banco_insert_name_campo('id_hosts',$id_hosts);
							banco_insert_name_campo('id_hosts_agendamentos',$id_hosts_agendamentos);
							banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
							banco_insert_name_campo('nome',$acompanhantesNomes[$i]);
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"hosts_agendamentos_acompanhantes"
							);
						}
						
						// ===== Atualizar agendamento.
						
						banco_update_campo('acompanhantes',$acompanhantes);
						banco_update_campo('senha',$senha);
						banco_update_campo('status','novo');
						banco_update_campo('pubID',$pubID);
						banco_update_campo('versao','versao+1',true);
						banco_update_campo('data_modificacao','NOW()',true);
						
						banco_update_executar('hosts_agendamentos',"WHERE id_hosts='".$id_hosts."' AND id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					} else {
						// ===== Criar novo agendamento.
						
						banco_insert_name_campo('id_hosts',$id_hosts);
						banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
						banco_insert_name_campo('data',$agendamentoData);
						banco_insert_name_campo('acompanhantes',$acompanhantes);
						banco_insert_name_campo('status','novo');
						banco_insert_name_campo('pubID',$pubID);
						banco_insert_name_campo('versao','1',true);
						banco_insert_name_campo('data_criacao','NOW()',true);
						banco_insert_name_campo('data_modificacao','NOW()',true);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"hosts_agendamentos"
						);
						
						$id_hosts_agendamentos = banco_last_id();
						
						// ===== Criar acompanhantes do agendamento caso houver.
						
						if((int)$acompanhantes > 0){
							for($i=0;$i<(int)$acompanhantes;$i++){
								banco_insert_name_campo('id_hosts',$id_hosts);
								banco_insert_name_campo('id_hosts_agendamentos',$id_hosts_agendamentos);
								banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
								banco_insert_name_campo('nome',$acompanhantesNomes[$i]);
								
								banco_insert_name
								(
									banco_insert_name_campos(),
									"hosts_agendamentos_acompanhantes"
								);
							}
						}
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
					
					// ===== Formatar datas.
					
					$fase_escolha_livre = (existe($config['fase-escolha-livre']) ? (int)$config['fase-escolha-livre'] : 7);
					$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
					
					$data_sorteio = formato_dado_para('data',date('Y-m-d',strtotime($agendamentoData.' - '.$fase_escolha_livre.' day')));
					$data_confirmacao_1 = formato_dado_para('data',date('Y-m-d',strtotime($agendamentoData.' - '.$fase_sorteio[0].' day')));
					$data_confirmacao_2 = formato_dado_para('data',date('Y-m-d',strtotime($agendamentoData.' - '.$fase_sorteio[1].' day') - 1));
					
					// ===== Formatar dados do email.
					
					$preAgendamentoAssunto = (existe($config['pre-agendamento-assunto']) ? $config['pre-agendamento-assunto'] : '');
					$preAgendamentoMensagem = (existe($config['pre-agendamento-mensagem']) ? $config['pre-agendamento-mensagem'] : '');
					$msgConclusaoPreAgendamento = (existe($config['msg-conclusao-pre-agendamento']) ? $config['msg-conclusao-pre-agendamento'] : '');
					
					$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
					
					$email = $hosts_usuarios['email'];
					$nome = $hosts_usuarios['nome'];
					
					$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_agendamentos,6);
					
					// ===== Formatar mensagem do email.
					
					gestor_incluir_biblioteca('host');
					
					$preAgendamentoAssunto = modelo_var_troca_tudo($preAgendamentoAssunto,"#codigo#",$codigo);
					
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#codigo#",$codigo);
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#titulo#",$tituloEstabelecimento);
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#data#",formato_dado_para('data',$agendamentoData));
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#data_sorteio#",$data_sorteio);
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#data_confirmacao_1#",$data_confirmacao_1);
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#data_confirmacao_2#",$data_confirmacao_2);
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#url-cancelamento#",'<a target="agendamento" href="'.host_url(Array('opcao'=>'full')).'agendamentos-publico/?acao=cancelar&token='.$token.'" style="overflow-wrap: break-word;">'.host_url(Array('opcao'=>'full')).'agendamentos-publico/?acao=cancelar&token='.$token.'</a>');
					
					// ===== Formatar mensagem do alerta.
					
					$msgConclusaoPreAgendamento = modelo_var_troca_tudo($msgConclusaoPreAgendamento,"#data#",formato_dado_para('data',$agendamentoData));
					$msgConclusaoPreAgendamento = modelo_var_troca_tudo($msgConclusaoPreAgendamento,"#data_sorteio#",$data_sorteio);
					$msgConclusaoPreAgendamento = modelo_var_troca_tudo($msgConclusaoPreAgendamento,"#data_confirmacao_1#",$data_confirmacao_1);
					$msgConclusaoPreAgendamento = modelo_var_troca_tudo($msgConclusaoPreAgendamento,"#data_confirmacao_2#",$data_confirmacao_2);
					
					$msgAlerta = $msgConclusaoPreAgendamento;
					
					// ===== Enviar email com informações do pré-agendamento.
					
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
							'assunto' => $preAgendamentoAssunto,
							'html' => $preAgendamentoMensagem,
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
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_agendamentos='".$id_hosts_agendamentos."'"
				));
				
				unset($hosts_agendamentos['id_hosts']);
				
				$hosts_agendamentos_acompanhantes = banco_select(Array(
					'tabela' => 'hosts_agendamentos_acompanhantes',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_agendamentos='".$id_hosts_agendamentos."'"
				));
				
				$hosts_agendamentos_acompanhantes_proc = Array();
				
				if($hosts_agendamentos_acompanhantes)
				foreach($hosts_agendamentos_acompanhantes as $hosts_agendamentos_acompanhante){
					unset($hosts_agendamentos_acompanhante['id_hosts']);
					
					$hosts_agendamentos_acompanhantes_proc[] = $hosts_agendamentos_acompanhante;
				}
				
				$retornoDados = Array(
					'agendamentos' => $hosts_agendamentos,
					'agendamentos_acompanhantes' => $hosts_agendamentos_acompanhantes_proc,
					'alerta' => $msgAlerta,
				);
				
				// ===== Caso seja necessário atualizar agendamentos datas, incluir o mesmo nos dados de retorno.
				
				if(isset($atualizarAgendamentosDatas)){
					$hosts_agendamentos_datas = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_agendamentos_datas',
						'campos' => Array(
							'id_hosts_agendamentos_datas',
							'total',
						),
						'extra' => 
							"WHERE id_hosts='".$id_hosts."'"
							." AND data='".$agendamentoData."'"
					));
					
					$retornoDados['agendamentos_datas'] = $hosts_agendamentos_datas;
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

function plataforma_cliente_plugin_alteracao(){
	global $_GESTOR;
	
	// ===== Variáveis do módulo.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'confirmarPublico':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: pubId.
			
			if(isset($dados['pubId'])){
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
				
				$msgAgendamentoNaoEncontrado = (existe($config['msg-agendamento-nao-encontrado']) ? $config['msg-agendamento-nao-encontrado'] : '');
				
				// ===== Tratar os dados enviados.
				
				$pubId = banco_escape_field($dados['pubId']);
				$escolha = $dados['escolha'];
				
				// ===== Pegar o agendamento no banco de dados.
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => Array(
						'id_hosts_agendamentos',
						'id_hosts_usuarios',
						'status',
						'data',
					),
					'extra' => 
						"WHERE pubId='".$pubId."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Caso não exista, retorar erro.
				
				if(!$hosts_agendamentos){
					return Array(
						'status' => 'AGENDAMENTO_NAO_ENCONTRADO',
						'error-msg' => $msgAgendamentoNaoEncontrado,
					);
				}
				
				$id_hosts_agendamentos = $hosts_agendamentos['id_hosts_agendamentos'];
				$id_hosts_usuarios = $hosts_agendamentos['id_hosts_usuarios'];
				$data = $hosts_agendamentos['data'];
				
				// ===== Tratar cada escolha: 'confirmar' ou 'cancelar'.
				
				switch($escolha){
					case 'confirmar':
						// ===== Dados do agendamento.
						
						if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
						
						// ===== Configuração de fase de sorteio.
					
						$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
						
						// ===== Verificar se o status atual do agendamento permite confirmação.
						
						if(
							$hosts_agendamentos['status'] == 'confirmado' ||
							$hosts_agendamentos['status'] == 'qualificado' ||
							$hosts_agendamentos['status'] == 'email-enviado' ||
							$hosts_agendamentos['status'] == 'email-nao-enviado'
						){
							// ===== Verificar se está na fase de confirmação.
							
							if(
								strtotime($data) >= strtotime($hoje.' + '.($fase_sorteio[1]+1).' day') &&
								strtotime($data) < strtotime($hoje.' + '.($fase_sorteio[0]+1).' day')
							){
								// ===== Caso não tenha sido confirmado anteriormente, confirmar o agendamento.
								
								$retorno = plataforma_cliente_plugin_data_agendamento_confirmar(Array(
									'id_hosts' => $id_hosts,
									'id_hosts_agendamentos' => $id_hosts_agendamentos,
									'id_hosts_usuarios' => $id_hosts_usuarios,
									'data' => $data,
								));
								
								// ===== Verificar se a confirmação ocorreu corretamente.
								
								if(!$retorno['confirmado']){
									return Array(
										'status' => $retorno['status'],
										'error-msg' => $retorno['alerta'],
									);
								} else {
									// ===== Alerta de confirmação do agendamento.
									
									$retornoDados['alerta'] = $retorno['alerta'];
								}
							} else {
								// ===== Datas do período de confirmação.
								
								gestor_incluir_biblioteca('formato');
								
								$data_confirmacao_1 = formato_dado_para('data',date('Y-m-d',strtotime($hosts_agendamentos['data'].' - '.($fase_sorteio[0]).' day')));
								$data_confirmacao_2 = formato_dado_para('data',date('Y-m-d',strtotime($hosts_agendamentos['data'].' - '.($fase_sorteio[1]).' day') - 1));
								
								// ===== Retornar a mensagem de agendamento expirado.
								
								$msgAgendamentoExpirado = (existe($config['msg-agendamento-expirado']) ? $config['msg-agendamento-expirado'] : '');
								
								$msgAgendamentoExpirado = modelo_var_troca_tudo($msgAgendamentoExpirado,"#data_confirmacao_1#",$data_confirmacao_1);
								$msgAgendamentoExpirado = modelo_var_troca_tudo($msgAgendamentoExpirado,"#data_confirmacao_2#",$data_confirmacao_2);
								
								return Array(
									'status' => 'AGENDAMENTO_CONFIRMACAO_EXPIRADO',
									'error-msg' => $msgAgendamentoExpirado,
								);
							}
						} else {
							return Array(
								'status' => 'AGENDAMENTO_STATUS_NAO_PERMITIDO_CONFIRMACAO',
							);
						}
					break;
					default:
						// ===== Efetuar o cancelamento.
						
						$retorno = plataforma_cliente_plugin_data_agendamento_cancelar(Array(
							'id_hosts' => $id_hosts,
							'id_hosts_agendamentos' => $id_hosts_agendamentos,
							'id_hosts_usuarios' => $id_hosts_usuarios,
							'data' => $data,
						));
						
						// ===== Verificar se o cancelamento ocorreu corretamente.
						
						if(!$retorno['cancelado']){
							return Array(
								'status' => $retorno['status'],
								'error-msg' => $retorno['alerta'],
							);
						} else {
							// ===== Alerta do cancelamento do agendamento.
							
							$retornoDados['alerta'] = $retorno['alerta'];
						}
				}
				
				// ===== Formatar dados de retorno.
				
				$hosts_agendamentos_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos_datas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND data='".$data."'"
				));
				
				if($hosts_agendamentos_datas){
					unset($hosts_agendamentos_datas['id_hosts']);
					
					$retornoDados['agendamentos_datas'] = $hosts_agendamentos_datas;
				}
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
				));
				
				unset($hosts_agendamentos['id_hosts']);
				
				$retornoDados['agendamentos'] = $hosts_agendamentos;
				
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
		case 'cancelarPublico':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: pubId.
			
			if(isset($dados['pubId'])){
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
				
				$msgAgendamentoNaoEncontrado = (existe($config['msg-agendamento-nao-encontrado']) ? $config['msg-agendamento-nao-encontrado'] : '');
				
				// ===== Tratar os dados enviados.
				
				$pubId = banco_escape_field($dados['pubId']);
				
				// ===== Pegar o agendamento no banco de dados.
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => Array(
						'id_hosts_agendamentos',
						'id_hosts_usuarios',
						'status',
						'data',
					),
					'extra' => 
						"WHERE pubId='".$pubId."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Caso não exista, retorar erro.
				
				if(!$hosts_agendamentos){
					return Array(
						'status' => 'AGENDAMENTO_NAO_ENCONTRADO',
						'error-msg' => $msgAgendamentoNaoEncontrado,
					);
				}
				
				$id_hosts_agendamentos = $hosts_agendamentos['id_hosts_agendamentos'];
				$id_hosts_usuarios = $hosts_agendamentos['id_hosts_usuarios'];
				$data = $hosts_agendamentos['data'];
				
				// ===== Efetuar o cancelamento.
				
				$retorno = plataforma_cliente_plugin_data_agendamento_cancelar(Array(
					'id_hosts' => $id_hosts,
					'id_hosts_agendamentos' => $id_hosts_agendamentos,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'data' => $data,
				));
				
				// ===== Verificar se o cancelamento ocorreu corretamente.
				
				if(!$retorno['cancelado']){
					return Array(
						'status' => $retorno['status'],
						'error-msg' => $retorno['alerta'],
					);
				} else {
					// ===== Alerta do cancelamento do agendamento.
					
					$retornoDados['alerta'] = $retorno['alerta'];
				}
				
				// ===== Formatar dados de retorno.
				
				$hosts_agendamentos_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos_datas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND data='".$data."'"
				));
				
				if($hosts_agendamentos_datas){
					unset($hosts_agendamentos_datas['id_hosts']);
					
					$retornoDados['agendamentos_datas'] = $hosts_agendamentos_datas;
				}
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
				));
				
				unset($hosts_agendamentos['id_hosts']);
				
				$retornoDados['agendamentos'] = $hosts_agendamentos;
				
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
		case 'confirmar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: id_hosts_agendamentos e id_hosts_usuarios.
			
			if(isset($dados['id_hosts_agendamentos']) && isset($dados['id_hosts_usuarios'])){
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
				
				$msgAgendamentoNaoEncontrado = (existe($config['msg-agendamento-nao-encontrado']) ? $config['msg-agendamento-nao-encontrado'] : '');
				
				// ===== Tratar os dados enviados.
				
				$id_hosts_agendamentos = banco_escape_field($dados['id_hosts_agendamentos']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				$escolha = $dados['escolha'];
				
				// ===== Pegar o agendamento no banco de dados.
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => Array(
						'status',
						'data',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Caso não exista, retorar erro.
				
				if(!$hosts_agendamentos){
					return Array(
						'status' => 'AGENDAMENTO_NAO_ENCONTRADO',
						'error-msg' => $msgAgendamentoNaoEncontrado,
					);
				}
				
				$data = $hosts_agendamentos['data'];
				
				// ===== Tratar cada escolha: 'confirmar' ou 'cancelar'.
				
				switch($escolha){
					case 'confirmar':
						// ===== Dados do agendamento.
						
						if($modulo['forcarDataHoje']){ $hoje = $modulo['dataHojeForcada']; } else { $hoje = date('Y-m-d'); }
						
						// ===== Configuração de fase de sorteio.
					
						$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
						$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
						
						// ===== Verificar se o status atual do agendamento permite confirmação.
						
						if(
							$hosts_agendamentos['status'] == 'confirmado' ||
							$hosts_agendamentos['status'] == 'qualificado' ||
							$hosts_agendamentos['status'] == 'email-enviado' ||
							$hosts_agendamentos['status'] == 'email-nao-enviado'
						){
							// ===== Verificar se está na fase de confirmação.
							
							if(
								strtotime($data) >= strtotime($hoje.' + '.($fase_sorteio[1]+1).' day') &&
								strtotime($data) < strtotime($hoje.' + '.($fase_sorteio[0]+1).' day')
							){
								$confirmar = true;
							} else {
								// ===== Datas do período de confirmação.
								
								gestor_incluir_biblioteca('formato');
								
								$data_confirmacao_1 = formato_dado_para('data',date('Y-m-d',strtotime($hosts_agendamentos['data'].' - '.($fase_sorteio[0]).' day')));
								$data_confirmacao_2 = formato_dado_para('data',date('Y-m-d',strtotime($hosts_agendamentos['data'].' - '.($fase_sorteio[1]).' day') - 1));
								
								// ===== Retornar a mensagem de agendamento expirado.
								
								$msgAgendamentoExpirado = (existe($config['msg-agendamento-expirado']) ? $config['msg-agendamento-expirado'] : '');
								
								$msgAgendamentoExpirado = modelo_var_troca_tudo($msgAgendamentoExpirado,"#data_confirmacao_1#",$data_confirmacao_1);
								$msgAgendamentoExpirado = modelo_var_troca_tudo($msgAgendamentoExpirado,"#data_confirmacao_2#",$data_confirmacao_2);
								
								return Array(
									'status' => 'AGENDAMENTO_CONFIRMACAO_EXPIRADO',
									'error-msg' => $msgAgendamentoExpirado,
								);
							}
						} else {
							// ===== Confirmação de vagas residuais se permitido. Senão retornar erro.
							
							if(
								strtotime($hoje) >= strtotime($data.' - '.$fase_residual.' day') &&
								strtotime($hoje) <= strtotime($data.' - 1 day')
							){
								$confirmar = true;
							} else {
								return Array(
									'status' => 'AGENDAMENTO_STATUS_NAO_PERMITIDO_CONFIRMACAO',
								);
							}
						}
						
						// ===== Caso tenha permissão para poder confirmar, fazer as operações necessárias.
						
						if(isset($confirmar)){
							// ===== Caso não tenha sido confirmado anteriormente, confirmar o agendamento.
							
							$retorno = plataforma_cliente_plugin_data_agendamento_confirmar(Array(
								'id_hosts' => $id_hosts,
								'id_hosts_agendamentos' => $id_hosts_agendamentos,
								'id_hosts_usuarios' => $id_hosts_usuarios,
								'data' => $data,
							));
							
							// ===== Verificar se a confirmação ocorreu corretamente.
							
							if(!$retorno['confirmado']){
								return Array(
									'status' => $retorno['status'],
									'error-msg' => $retorno['alerta'],
								);
							} else {
								// ===== Alerta de confirmação do agendamento.
								
								$retornoDados['alerta'] = $retorno['alerta'];
							}
						}
					break;
					default:
						// ===== Efetuar o cancelamento.
						
						$retorno = plataforma_cliente_plugin_data_agendamento_cancelar(Array(
							'id_hosts' => $id_hosts,
							'id_hosts_agendamentos' => $id_hosts_agendamentos,
							'id_hosts_usuarios' => $id_hosts_usuarios,
							'data' => $data,
						));
						
						// ===== Verificar se o cancelamento ocorreu corretamente.
						
						if(!$retorno['cancelado']){
							return Array(
								'status' => $retorno['status'],
								'error-msg' => $retorno['alerta'],
							);
						} else {
							// ===== Alerta do cancelamento do agendamento.
							
							$retornoDados['alerta'] = $retorno['alerta'];
						}
				}
				
				// ===== Formatar dados de retorno.
				
				$hosts_agendamentos_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos_datas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND data='".$data."'"
				));
				
				if($hosts_agendamentos_datas){
					unset($hosts_agendamentos_datas['id_hosts']);
					
					$retornoDados['agendamentos_datas'] = $hosts_agendamentos_datas;
				}
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
				));
				
				unset($hosts_agendamentos['id_hosts']);
				
				$retornoDados['agendamentos'] = $hosts_agendamentos;
				
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
		case 'cancelar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verificar se os campos obrigatórios foram enviados: id_hosts_agendamentos e id_hosts_usuarios.
			
			if(isset($dados['id_hosts_agendamentos']) && isset($dados['id_hosts_usuarios'])){
				// ===== Pegar os dados de configuração.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
				
				$msgAgendamentoNaoEncontrado = (existe($config['msg-agendamento-nao-encontrado']) ? $config['msg-agendamento-nao-encontrado'] : '');
				
				// ===== Tratar os dados enviados.
				
				$id_hosts_agendamentos = banco_escape_field($dados['id_hosts_agendamentos']);
				$id_hosts_usuarios = banco_escape_field($dados['id_hosts_usuarios']);
				
				// ===== Pegar o agendamento no banco de dados.
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => Array(
						'status',
						'data',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				// ===== Caso não exista, retorar erro.
				
				if(!$hosts_agendamentos){
					return Array(
						'status' => 'AGENDAMENTO_NAO_ENCONTRADO',
						'error-msg' => $msgAgendamentoNaoEncontrado,
					);
				}
				
				$data = $hosts_agendamentos['data'];
				
				// ===== Efetuar o cancelamento.
				
				$retorno = plataforma_cliente_plugin_data_agendamento_cancelar(Array(
					'id_hosts' => $id_hosts,
					'id_hosts_agendamentos' => $id_hosts_agendamentos,
					'id_hosts_usuarios' => $id_hosts_usuarios,
					'data' => $data,
				));
				
				// ===== Verificar se o cancelamento ocorreu corretamente.
				
				if(!$retorno['cancelado']){
					return Array(
						'status' => $retorno['status'],
						'error-msg' => $retorno['alerta'],
					);
				} else {
					// ===== Alerta do cancelamento do agendamento.
					
					$retornoDados['alerta'] = $retorno['alerta'];
				}
				
				// ===== Formatar dados de retorno.
				
				$hosts_agendamentos_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos_datas',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND data='".$data."'"
				));
				
				if($hosts_agendamentos_datas){
					unset($hosts_agendamentos_datas['id_hosts']);
					
					$retornoDados['agendamentos_datas'] = $hosts_agendamentos_datas;
				}
				
				$hosts_agendamentos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_agendamentos',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
						." AND id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
				));
				
				unset($hosts_agendamentos['id_hosts']);
				
				$retornoDados['agendamentos'] = $hosts_agendamentos;
				
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
		case 'agendamentos': $dados = plataforma_cliente_plugin_agendamentos(); break;
		case 'alteracao': $dados = plataforma_cliente_plugin_alteracao(); break;
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