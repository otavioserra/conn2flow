<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'agendamentos-host-publico';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// ===== Funções Auxiliares


// ===== Funções Principais

function agendamentos_confirmacao_publico(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
		'formulario',
		'api-servidor',
	));
	
	// ===== Validar o token enviado.
	
	$token = $_REQUEST['token'];
	
	$pubId = api_servidor_validar_token_validacao(Array(
		'token' => $token,
	));
	
	if(!$pubId){
		// ===== Ativação da expiradoOuNaoEncontrado.
		
		$_GESTOR['javascript-vars']['expiradoOuNaoEncontrado'] = true;
	} else {
		// ===== Verificar se a confirmação está no período válido.
		
		$agendamentos = banco_select(Array(
			'unico' => true,
			'tabela' => 'agendamentos',
			'campos' => Array(
				'data',
				'status',
			),
			'extra' => 
				"WHERE pubId='".$pubId."'"
		));
		
		// ===== Dados do agendamento.
		
		$hoje = date('Y-m-d');
		$data = $agendamentos['data'];
		$status = $agendamentos['status'];
		
		// ===== Configuração de fase de sorteio.
		
		$config = gestor_variaveis(Array('modulo' => 'configuracoes-agendamentos','conjunto' => true));
		
		$fase_sorteio = (existe($config['fase-sorteio']) ? explode(',',$config['fase-sorteio']) : Array(7,5));
		
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
			interface_alerta(Array(
				'redirect' => true,
				'msg' => 'AGENDAMENTO_STATUS_NAO_PERMITIDO_CONFIRMACAO'
			));
			
			gestor_redirecionar('agendamentos/?tela=agendamentos-anteriores');
		}
		
		// ===== Solicitação de confirmação do agendamento.
		
		if(isset($_REQUEST['efetuar_confirmacao_publico'])){
			// ===== Pegar a escolha de alteração.
			
			$escolha = $_REQUEST['escolha'];
			
			// ===== API-Servidor para efetuar confirmação.
			
			$retorno = api_servidor_interface(Array(
				'interface' => 'alteracao',
				'plugin' => 'agendamentos',
				'opcao' => 'confirmarPublico',
				'dados' => Array(
					'escolha' => ($escolha == 'confirmar' ? 'confirmar':'cancelar'),
					'pubId' => $pubId,
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
		
		$_GESTOR['javascript-vars']['confirmarPublico'] = true;
	}
	
	// ===== Incluir o token no formulário.
	
	pagina_trocar_variavel_valor('token',$_REQUEST['token']);
	
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

function agendamentos_cancelamento_publico(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
		'formulario',
		'api-servidor',
	));
	
	// ===== Validar o token enviado.
	
	$token = $_REQUEST['token'];
	
	$pubId = api_servidor_validar_token_validacao(Array(
		'token' => $token,
	));
	
	if(!$pubId){
		// ===== Ativação da expiradoOuNaoEncontrado.
		
		$_GESTOR['javascript-vars']['expiradoOuNaoEncontrado'] = true;
	} else {
		
		// ===== Solicitação de confirmação do cancelamento.
		
		if(isset($_REQUEST['efetuar_cancelamento_publico'])){
			// ===== API-Servidor para efetuar confirmação.
			
			$retorno = api_servidor_interface(Array(
				'interface' => 'alteracao',
				'plugin' => 'agendamentos',
				'opcao' => 'cancelarPublico',
				'dados' => Array(
					'pubId' => $pubId,
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
		
		$_GESTOR['javascript-vars']['cancelarPublico'] = true;
	}
	
	// ===== Incluir o token no formulário.
	
	pagina_trocar_variavel_valor('token',$_REQUEST['token']);
	
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
	
	// ===== Acessos público.
	
	$acao = (isset($_REQUEST['acao']) ? $_REQUEST['acao'] : '');
	
	switch($acao){
		case 'cancelar': agendamentos_cancelamento_publico(); break;
		default: agendamentos_confirmacao_publico();
	}
	
}

agendamentos_start();

?>