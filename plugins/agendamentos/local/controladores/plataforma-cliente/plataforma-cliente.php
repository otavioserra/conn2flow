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
				
				$id_hosts_usuarios = banco_escape_field($dados['usuarioID']);
				
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
				
				return Array(
					'status' => 'TESTES',
					'error-msg' => print_r($dados,true).' ### '. print_r($acompanhantesNomes,true) . ' ### '.$acompanhantes,
				);
				
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
				
				// ===== Verificar se está na fase residual ou em pré-agendamento (fase de sorteio é tratada na função anterior 'data_permitida'). Tratar cada caso de forma diferente.
				
				$fase_residual = (existe($config['fase-residual']) ? (int)$config['fase-residual'] : 5);
				
				$hoje = date('Y-m-d');
				
				if(strtotime($agendamentoData) <= strtotime($hoje.' + '.$fase_residual.' day')){
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
						$msgAgendamentoSemVagas = (existe($config['msg-agendamento-sem-vagas']) ? $config['msg-agendamento-sem-vagas'] : '');
						
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
					
					// ===== Pegar dados do usuário e gerar o pub_hash.
					
					$hosts_usuarios = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_usuarios',
						'campos' => Array(
							'senha',
							'nome',
							'email',
						),
						'extra' => 
							"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					$pub_hash = hash('sha256',$hosts_usuarios['senha'].$agendamentoData.$senha);
					
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
						banco_update_campo('pub_hash',$pub_hash);
						
						banco_update_executar('hosts_agendamentos',"WHERE id_hosts='".$id_hosts."' AND id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					} else {
						// ===== Criar novo agendamento.
						
						banco_insert_name_campo('id_hosts',$id_hosts);
						banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
						banco_insert_name_campo('data',$agendamentoData);
						banco_insert_name_campo('acompanhantes',$acompanhantes);
						banco_insert_name_campo('senha',$senha);
						banco_insert_name_campo('status','confirmado');
						banco_insert_name_campo('pub_hash',$pub_hash);
						
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
								banco_insert_name_campo('nome',$acompanhantesNomes[$i]);
								
								banco_insert_name
								(
									banco_insert_name_campos(),
									"hosts_agendamentos_acompanhantes"
								);
							}
						}
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
					$agendamentoMensagem = modelo_var_troca_tudo($agendamentoMensagem,"#url-cancelamento#",'<a target="agendamento" href="'.host_url(Array('opcao'=>'full')).'agendamentos/?opcao=cancelar&pub_hash='.$pub_hash.'">'.host_url(Array('opcao'=>'full')).'agendamentos/?opcao=cancelar&pub_hash='.$pub_hash.'</a>');
					
					$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($agendamentoMensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $agendamentoMensagem = modelo_tag_in($agendamentoMensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					
					for($i=0;$i<(int)$acompanhantes;$i++){
						$cel_aux = $cel[$cel_nome];
						
						$cel_aux = modelo_var_troca($cel_aux,"#nome#",$acompanhantesNomes[$i]);
						
						$agendamentoMensagem = modelo_var_in($agendamentoMensagem,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
					$agendamentoMensagem = modelo_var_troca($agendamentoMensagem,'<!-- '.$cel_nome.' -->','');
					
					// ===== Formatar mensagem do alerta.
					
					$msgConclusaoAgendamento = modelo_var_troca_tudo($msgConclusaoAgendamento,"#data#",formato_dado_para('data',$agendamentoData));
					$msgConclusaoAgendamento = modelo_var_troca_tudo($msgConclusaoAgendamento,"#senha#",$senha);
					
					$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($msgConclusaoAgendamento,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $msgConclusaoAgendamento = modelo_tag_in($msgConclusaoAgendamento,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					
					for($i=0;$i<(int)$acompanhantes;$i++){
						$cel_aux = $cel[$cel_nome];
						
						$cel_aux = modelo_var_troca($cel_aux,"#nome#",$acompanhantesNomes[$i]);
						
						$msgConclusaoAgendamento = modelo_var_in($msgConclusaoAgendamento,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
					$msgConclusaoAgendamento = modelo_var_troca($msgConclusaoAgendamento,'<!-- '.$cel_nome.' -->','');
					
					$msgAlerta = $msgConclusaoAgendamento;
					
					// ===== Enviar email com informações do agendamento.
					
					gestor_incluir_biblioteca(Array('comunicacao','host'));
					
					if(comunicacao_email(Array(
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
						),
					))){
						
					}
				} else {
					// ===== Verificar se já tem agendamento para esta data. Caso tenha, retornar erro e mensagem de permissão apenas de um agendamento por data.
					
					if($hosts_agendamentos){
						$msgAgendamentoJaExiste = (existe($config['msg-agendamento-ja-existe']) ? $config['msg-agendamento-ja-existe'] : '');
						
						return Array(
							'status' => 'AGENDAMENTO_MULTIPLO_NAO_PERMITIDO',
							'error-msg' => $msgAgendamentoJaExiste,
						);
					}
					
					// ===== Criar novo agendamento.
					
					banco_insert_name_campo('id_hosts',$id_hosts);
					banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
					banco_insert_name_campo('data',$agendamentoData);
					banco_insert_name_campo('acompanhantes',$acompanhantes);
					banco_insert_name_campo('status','novo');
					
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
							banco_insert_name_campo('nome',$acompanhantesNomes[$i]);
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"hosts_agendamentos_acompanhantes"
							);
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
					
					$preAgendamentoAssunto = modelo_var_troca_tudo($preAgendamentoAssunto,"#codigo#",$codigo);
					
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#codigo#",$codigo);
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#titulo#",$tituloEstabelecimento);
					$preAgendamentoMensagem = modelo_var_troca_tudo($preAgendamentoMensagem,"#data#",formato_dado_para('data',$agendamentoData));
					
					// ===== Formatar mensagem do alerta.
					
					$msgConclusaoPreAgendamento = modelo_var_troca_tudo($msgConclusaoPreAgendamento,"#data_sorteio#",$data_sorteio);
					$msgConclusaoPreAgendamento = modelo_var_troca_tudo($msgConclusaoPreAgendamento,"#data_confirmacao_1#",$data_confirmacao_1);
					$msgConclusaoPreAgendamento = modelo_var_troca_tudo($msgConclusaoPreAgendamento,"#data_confirmacao_2#",$data_confirmacao_2);
					
					$msgAlerta = $msgConclusaoPreAgendamento;
					
					// ===== Enviar email com informações do pré-agendamento.
					
					gestor_incluir_biblioteca(Array('comunicacao','host'));
					
					if(comunicacao_email(Array(
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