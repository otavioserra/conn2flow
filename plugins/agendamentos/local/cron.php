<?php

// ===== Cron responsável por tratar dados dos agendamentos.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'cron-agendamentos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'maxEmailsPerCycle' => 50,
);

// =========================== Funções Auxiliares

// =========================== Funções do Cron

function cron_agendamentos_sorteio(){
	global $_GESTOR;
	
	// ===== Módulo variáveis.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificadores dos Hosts.
	
	$hostsIDs = $_GESTOR['pluginHostsIDs'];
	
	// ===== Variáveis de controle valores iniciais.
	
	$hoje_dia_semana = strtolower(date('D'));
	$hoje = date('Y-m-d');
	
	// ===== Varrer todos hosts.
	
	if($hostsIDs)
	foreach($hostsIDs as $id_hosts){
		// ===== Pegar os dados de configuração do host atual.
		
		cron_incluir_biblioteca('configuracao');
		
		$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos', 'id_hosts' => $id_hosts));
		
		$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
		$dias_semana_maximo_vagas_arr = (existe($config['dias-semana-maximo-vagas']) ? explode(',',$config['dias-semana-maximo-vagas']) : Array());
		$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
		
		// ===== Data do sorteio.
		
		$data = date('Y-m-d',strtotime($hoje.' + '.($fase_sorteio[0]).' day'));
		
		// ===== Pegar os agendamentos no banco de dados para a data específica.
		
		$hosts_agendamentos = banco_select(Array(
			'tabela' => 'hosts_agendamentos',
			'campos' => Array(
				'id_hosts_usuarios',
				'id_hosts_agendamentos',
				'acompanhantes',
			),
			'extra' => 
				"WHERE data='".$data."'"
				." AND id_hosts='".$id_hosts."'"
				." AND status='novo'"
		));
		
		// ===== Definir ações no processo do sorteio e pegar o total de agendamentos.
		
		$total_agendamentos = 0;
		$nova_qualificacao = false;
		$enviar_emails = false;
		$outroHost = false;
		
		if($hosts_agendamentos){
			// ===== Verificar os agendamentos datas no banco de dados.
			
			$hosts_agendamentos_datas = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_agendamentos_datas',
				'campos' => Array(
					'total',
					'status',
				),
				'extra' => 
					"WHERE data='".$data."'"
					." AND id_hosts='".$id_hosts."'"
			));
			
			// ===== Definir o status atual do processo de sorteio.
			
			if(!$hosts_agendamentos_datas){
				// ===== Criar data no agendamento_datas caso não exista.
				
				banco_insert_name_campo('id_hosts',$id_hosts);
				banco_insert_name_campo('data',$data);
				banco_insert_name_campo('total','0',true);
				banco_insert_name_campo('status','novo');
				
				banco_insert_name
				(
					banco_insert_name_campos(),
					"hosts_agendamentos_datas"
				);
				
				$statusProcessoSorteio = 'novo';
			} else {
				$statusProcessoSorteio = ($hosts_agendamentos_datas['status'] ? $hosts_agendamentos_datas['status'] : 'novo');
			}
			
			// ===== Verificar o status do processo do sorteio. Caso o status seja diferente de 'sem-agendamentos' ou 'confirmacoes-enviadas', senão continuar loop e ir para outro host.
			
			switch($statusProcessoSorteio){
				case 'confirmacoes-enviadas':
				case 'sem-agendamentos':
					$outroHost = true;
				break;
			}
			
			if($outroHost){
				continue;
			}
			
			// ===== Trabalhar cada status.
			
			if(isset($statusProcessoSorteio))
			switch($statusProcessoSorteio){
				case 'novo':
					// ===== Pegar o total de agendamentos, senão atualizar status do processo para 'sem-agendamentos' e continuar loop indo para outro host.
				
					if($hosts_agendamentos){
						foreach($hosts_agendamentos as $agendamento){
							$total_agendamentos += 1 + (int)$agendamento['acompanhantes'];
						}
					} else {
						banco_update_campo('status','sem-agendamentos');
		
						banco_update_executar('hosts_agendamentos_datas',"WHERE data='".$data."' AND id_hosts='".$id_hosts."'");
						
						$outroHost = true;
					}
					
					// ===== Ativar nova qualificação e envio de emails de confirmação.
					
					$nova_qualificacao = true;
					$enviar_emails = true;
				break;
				case 'enviar-emails':
					// ===== Ativar envio de emails de confirmação.
					
					$enviar_emails = true;
				break;
			}
		} else {
			$outroHost = true;
		}
		
		if($outroHost){
			continue;
		}
		
		// ===== Sortear ou qualificar agendamentos para confirmação.
		
		if($nova_qualificacao){
			// ===== Definir o máximo de vagas para o dia da semana em questão.
			
			$max_vagas = 0;
			$count = 0;
			if($dias_semana)
			foreach($dias_semana as $dia_semana){
				if(strtolower($dia_semana) == $hoje_dia_semana){
					if(count($dias_semana_maximo_vagas_arr) > 1){
						$max_vagas = (int)$dias_semana_maximo_vagas_arr[$count];
					} else {
						$max_vagas = (int)$dias_semana_maximo_vagas_arr[0];
					}
					
					if($max_vagas < 0) $max_vagas = 0;
					break;
				}
				
				$count++;
			}
			
			// ===== Verificar se precisa ou não de sorteio baseado no máximo de vagas de atendimento.
			
			if($total_agendamentos > $max_vagas){
				$sortear = true;
			}
			
			// ===== Sortear caso o total de atendimentos for maior que o máximo de vagas. Senão qualificar todos os agendamentos diretamente.
			
			if(isset($sortear)){
				// ===== Preparação de bilhetes com aplicação de pesos.
				
				foreach($hosts_agendamentos as $num => $agendamento){
					$bilhete = Array(
						'id_hosts_agendamentos' => $agendamento['id_hosts_agendamentos'],
						'id_hosts_usuarios' => $agendamento['id_hosts_usuarios'],
						'acompanhantes' => (int)$agendamento['acompanhantes'],
					);
					
					// ===== Pegar os peso do usuário.
					
					$hosts_agendamentos_pesos = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_agendamentos_pesos',
						'campos' => Array(
							'peso',
						),
						'extra' => 
							"WHERE id_hosts_usuarios='".$agendamento['id_hosts_usuarios']."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					// ===== Montar a quantidade de bilhetes que um usuário tem baseado no seu peso.
					
					if($hosts_agendamentos_pesos){
						$peso = (int)$hosts_agendamentos_pesos['peso'];
						if($peso > 0){
							for($i=0;$i<$peso+1;$i++){
								$bilhetes[] = $bilhete;
							}
						} else {
							$bilhetes[] = $bilhete;
						}
						
						// ===== Marcar a existência do peso no banco de dados.
						
						$hosts_agendamentos[$num]['banco_peso'] = true;
					} else {
						$peso = 0;
						$bilhetes[] = $bilhete;
					}
					
					// ===== Atualizar array dos agendamentos e colocar o peso.
					
					$hosts_agendamentos[$num]['peso'] = $peso;
				}
				
				// ===== Sortear os bilhetes.
				
				$sorteados = Array();
				$bilhetes_aux = $bilhetes;
				$vagas_sorteadas = 0;
				
				while($vagas_sorteadas < $max_vagas){
					$na = count($bilhetes_aux) - 1;
					$indice = rand(0,$na);
					
					$id_hosts_agendamentos = $bilhetes_aux[$indice]['id_hosts_agendamentos'];
					$sorteados[] = $bilhetes_aux[$indice];
					
					$vagas_sorteadas += 1 + $bilhetes_aux[$indice]['acompanhantes'];
					
					$bilhetes_aux2 = Array();
					foreach($bilhetes_aux as $bilhete){
						if($bilhete['id_hosts_agendamentos'] != $id_hosts_agendamentos){
							$vagas_restantes = $max_vagas - $vagas_sorteadas;
							
							if($vagas_restantes >= 3){
								$bilhetes_aux2[] = $bilhete;
							} else if($vagas_restantes == 2 && $bilhete['acompanhantes'] <= 1){
								$bilhetes_aux2[] = $bilhete;
							} else if($vagas_restantes == 1 && $bilhete['acompanhantes'] == 0){
								$bilhetes_aux2[] = $bilhete;
							}
						}
					}
					
					$bilhetes_aux = $bilhetes_aux2;
					
					if(count($bilhetes_aux) == 0){
						break;
					}
				}
				
				// ===== Qualificar agendamentos sorteados para confirmação.
				
				if($sorteados)
				foreach($sorteados as $sorteado){
					banco_update
					(
						"status='qualificado'",
						"hosts_agendamentos",
						"WHERE id_hosts_agendamentos='".$sorteado['id_hosts_agendamentos']."'"
					);
				}
				
				// ===== Agendamentos NÃO sorteados atualizar pesos.
				
				if($sorteados){
					unset($computado);
					
					if($hosts_agendamentos)
					foreach($hosts_agendamentos as $agendamento){
						$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
						
						// ===== Verificar se o agendamento foi sorteado ou não.
						
						$sorteadoFlag = false;
						foreach($sorteados as $sorteado){
							if($agendamento['id_hosts_agendamentos'] == $sorteado['id_hosts_agendamentos']){
								$sorteadoFlag = true;
								break;
							}
						}
						
						// ===== Verificar se já foi computado o peso novo do usuário. Caso negativo, atualizar peso no banco de dados.
						
						if(!isset($computado[$id_hosts_usuarios])){
							
							// ===== Verificar se o usuário já tem peso cadastrado no banco.
							
							if(isset($agendamento['banco_peso'])){
								$banco_peso = true;
							} else {
								$banco_peso = false;
							}
							
							// ===== Aumentar o peso dos usuários não sorteados afim de aumentar em 100% de chance a próxima vez que passará por um sorteio. Para os sorteados, zerar o peso para ter uma única chance no próximo sorteio.
							
							if(!$sorteadoFlag){
								$peso = (int)$agendamento['peso'] + 1;
							} else {
								$peso = '0';
							}
							
							// ===== Atualizar ou criar novo registro no banco de dados com o peso atualizado do usuário.
							
							if($banco_peso){
								banco_update
								(
									"peso=".$peso,
									"hosts_agendamentos_pesos",
									"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
									." AND id_hosts='".$id_hosts."'"
								);
							} else {
								banco_insert_name_campo('id_hosts',$id_hosts);
								banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
								banco_insert_name_campo('peso',$peso);
								
								banco_insert_name
								(
									banco_insert_name_campos(),
									"hosts_agendamentos_pesos"
								);
							}
							
							// ===== Marcar o usuário como computado pois um mesmo usuário pode ter mais de um bilhete de sorteio.
							
							$computado[$id_hosts_usuarios] = true;
						}
					}
				}
			} else {
				// ===== Agendamentos qualificar para confirmação.
				
				if($hosts_agendamentos)
				foreach($hosts_agendamentos as $agendamento){
					banco_update
					(
						"status='qualificado'",
						"hosts_agendamentos",
						"WHERE id_hosts_agendamentos='".$agendamento['id_hosts_agendamentos']."'"
					);
				}
			}
			
			// ===== Atualizar processo para enviar os emails de confirmação.
			
			banco_update
			(
				"status='enviar-emails'",
				"hosts_agendamentos_datas",
				"WHERE data='".$data."'"
				." AND id_hosts='".$id_hosts."'"
			);
		}
		
		// ===== Enviar email de confirmação de agendamento para cada usuário em cada agendamento.
		
		if($enviar_emails){
			// ===== Pegar os dados dos agendamentos qualificados no banco de dados.
			
			$hosts_agendamentos = banco_select(Array(
				'tabela' => 'hosts_agendamentos',
				'campos' => Array(
					'id_hosts_usuarios',
					'id_hosts_agendamentos',
					'pubID',
				),
				'extra' => 
					"WHERE data='".$data."'"
					." AND id_hosts='".$id_hosts."'"
					." AND status='qualificado'"
			));
			
			cron_log('hosts_agendamentos: '.print_r($hosts_agendamentos,true));
			
			// ===== Caso exista, enviar emails para cada usuário com a opção de confirmar ou cancelar.
			
			if($hosts_agendamentos){
				
				// ===== Pegar a mensagem e assunto dos emails, bem como o título do estabelecimento.
				
				$emailConfirmacaoAssunto = (existe($config['email-confirmacao-assunto']) ? $config['email-confirmacao-assunto'] : '');
				$emailConfirmacaoMensagem = (existe($config['email-confirmacao-mensagem']) ? $config['email-confirmacao-mensagem'] : '');
				$tituloEstabelecimento = (existe($config['titulo-estabelecimento']) ? $config['titulo-estabelecimento'] : '');
				
				// ===== Formatar a data em questão para o formado brasileiro e pegar url completa do host, bem como incluir as bibliotecas necessárias.
				
				cron_incluir_biblioteca('formato');
				cron_incluir_biblioteca('host');
				cron_incluir_biblioteca('comunicacao');
				cron_incluir_biblioteca('autenticacao');
				
				$data_str = formato_dado_para('data',$data);
				$hostUrl = host_url(Array('opcao'=>'full','id_hosts' => $id_hosts));
				
				// ===== Varrer todos os agendamentos.
				
				$emails_enviados = 0;
				foreach($hosts_agendamentos as $agendamento){
					$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
					$id_hosts_agendamentos = $agendamento['id_hosts_agendamentos'];
					$pubID = $agendamento['pubID'];
					
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
					
					$codigo = date('dmY').formato_zero_a_esquerda($id_hosts_agendamentos,6);
					
					// ===== Gerar o token de validação.
					
					$validacao = autenticacao_cliente_gerar_token_validacao(Array(
						'id_hosts' => $id_hosts,
						'pubID' => $pubID,
					));

					$token = $validacao['token'];
					
					// ===== Formatar mensagem do email.
					
					$emailConfirmacaoAssuntoAux = $emailConfirmacaoAssunto;
					$emailConfirmacaoMensagemAux = $emailConfirmacaoMensagem;
					
					$emailConfirmacaoAssuntoAux = modelo_var_troca_tudo($emailConfirmacaoAssuntoAux,"#codigo#",$codigo);
					
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#codigo#",$codigo);
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#titulo#",$tituloEstabelecimento);
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#data#",$data_str);
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#url-confirmacao#",'<a target="agendamento" href="'.$hostUrl.'agendamentos/?acao=confirmar&token='.$token.'" style="overflow-wrap: break-word;">'.$hostUrl.'agendamentos/?acao=confirmar&token='.$token.'</a>');
					$emailConfirmacaoMensagemAux = modelo_var_troca_tudo($emailConfirmacaoMensagemAux,"#url-cancelamento#",'<a target="agendamento" href="'.$hostUrl.'agendamentos/?acao=cancelar&token='.$token.'" style="overflow-wrap: break-word;">'.$hostUrl.'agendamentos/?acao=cancelar&token='.$token.'</a>');
					
					// ===== Enviar email ao usuário solicitando a confirmação ou cancelamento do agendamento.
					
					if(comunicacao_email(Array(
						'hostPersonalizacao' => true,
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
						$status_agendamento = 'email-enviado';
					} else {
						$status_agendamento = 'email-nao-enviado';
					}
					
					// ===== Atualizar o agendamento no banco de dados.
					
					banco_update_campo('status',$status_agendamento);
					banco_update_campo('versao','versao+1',true);
					banco_update_campo('data_modificacao','NOW()',true);
					
					banco_update_executar('hosts_agendamentos',"WHERE id_hosts='".$id_hosts."' AND id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					
					// ===== Controle de limite de envio de emails por requisição do cron. Caso chegue no limite, retornar a função e finalizar.
					
					$emails_enviados++;
					
					if($emails_enviados >= $modulo['maxEmailsPerCycle']){
						return;
					}
				}
			}
		}
		
		// ===== Alterar o status do agendamento datas para 'confirmacoes-enviadas'.
		
		banco_update_campo('status','confirmacoes-enviadas');
		
		banco_update_executar('hosts_agendamentos_datas',"WHERE data='".$data."' AND id_hosts='".$id_hosts."'");
	}
}

// =========================== Funções de Acesso

function cron_agendamentos_start(){
	global $_GESTOR;
	
	/**********
		Parâmetros passados pelo módulo cron principal:
		
		$_GESTOR['pluginHostsIDs'] - Array - Todos os identificadores dos hosts que têm plugin habilitados
	**********/
	
	// ===== Pipeline de execução do cron.
	
	cron_agendamentos_sorteio();

	// ===== Retorno padrão.
	
	return true;
}

// ===== Retornar plataforma.

return cron_agendamentos_start();

?>