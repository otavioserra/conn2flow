<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-hosts';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.1',
	'bibliotecas' => Array('interface','html','pagina'),
	'tabela' => Array(
		'nome' => 'hosts',
		'nome_especifico' => 'dominio',
		'id' => 'user_cpanel',
		'id_numerico' => 'id_'.'hosts',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
);

function admin_hosts_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'id_usuarios',
		'dominio',
		'user_cpanel',
		'user_ftp',
		'user_db',
		'gestor_cliente_versao',
		'gestor_cliente_versao_num',
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Pegar 'id_usuarios' do host.
		
		$hosts = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts',
			'campos' => Array(
				'id_usuarios',
			),
			'extra' => 
				"WHERE user_cpanel='".$id."'"
		));
		
		// ===== Pegar dados do usuário proprietário do host.
		
		$usuarios_planos_usuarios = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_planos_usuarios',
			'campos' => Array(
				'id_usuarios_planos',
			),
			'extra' => 
				"WHERE id_usuarios='".$hosts['id_usuarios']."'"
		));
		
		// ===== Verificar se foi modificado o plano.
		
		$campo_nome = "id_usuarios_planos"; $request_name = 'usuario-plano'; $alteracoes_name = 'user-plan'; if($usuarios_planos_usuarios[$campo_nome] != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){
				$editar = true;
				$mudar_plano = true;
				$id_usuarios_planos = $_REQUEST[$request_name];
				$id_usuarios = $hosts['id_usuarios'];
				
				// ===== Alterar no usuários o 'id_usuarios_planos'.
				
				banco_update_campo('versao',"versao + 1",true);
				banco_update_campo('data_modificacao','NOW()',true);
				
				banco_update_executar('usuarios',"WHERE id_usuarios='".$id_usuarios."'");
				
				// ===== Incluir no histórico do módulo usuários a alteração.
				
				interface_historico_incluir(Array(
					'alteracoes' => Array(
						Array(
							'campo' => 'form-'.$alteracoes_name.'-label',
							'valor_antes' => $usuarios_planos_usuarios[$campo_nome],
							'valor_depois' => banco_escape_field($_REQUEST[$request_name]),
							'tabela' => Array(
								'nome' => 'usuarios_planos',
								'campo' => 'nome',
								'id_numerico' => 'id_usuarios_planos',
							)
						)
					),
					'id_numerico_manual' => $id_usuarios,
					'modulo_id' => 'usuarios',
					'tabela' => Array(
						'nome' => 'usuarios',
						'versao' => 'versao',
						'id_numerico' => 'id_usuarios',
					),
				));
				
				// ===== Fazer incluir histórico alterações no host.
			
				$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => $usuarios_planos_usuarios[$campo_nome],'valor_depois' => banco_escape_field($_REQUEST[$request_name]),'tabela' => Array(
					'nome' => 'usuarios_planos',
					'campo' => 'nome',
					'id_numerico' => 'id_usuarios_planos',
				));
			}
			
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar)){
			banco_update_campo($modulo['tabela']['versao'],$modulo['tabela']['versao']." + 1",true);
			banco_update_campo($modulo['tabela']['data_modificacao'],'NOW()',true);
			
			banco_update_executar($modulo['tabela']['nome'],"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'");
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Caso tenha sido mudado o plano, executar mudança no cPanel.
		
		if(isset($mudar_plano)){
			// ===== Pegar o nome do plano novo.
			
			$usuarios_planos = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_planos',
				'campos' => Array(
					'cpanel_plano',
				),
				'extra' => 
					"WHERE id_usuarios_planos='".$id_usuarios_planos."'"
			));
			
			$plano = $usuarios_planos['cpanel_plano'];
			
			// ===== Executar a mudança de plano no cPanel.
			
			gestor_incluir_biblioteca('cpanel');
			
			cpanel_changepackage(Array(
				'user' => $id,
				'plan' => $plano,
			));
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?id='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão Módulo JS
	
	//gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
	);
	
	if($_GESTOR['banco-resultado']){
		// ===== Pegar dados do usuário proprietário do host.
		
		$usuarios = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios',
			'campos' => Array(
				'id_usuarios_perfis',
				'id',
				'nome',
				'usuario',
				'email',
			),
			'extra' => 
				"WHERE id_usuarios='".$retorno_bd['id_usuarios']."'"
		));
		
		// ===== Pegar o plano atual do usuário.
		
		$usuarios_planos_usuarios = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_planos_usuarios',
			'campos' => Array(
				'id_usuarios_planos',
			),
			'extra' => 
				"WHERE id_usuarios='".$retorno_bd['id_usuarios']."'"
		));
		
		$id_usuarios_planos = $usuarios_planos_usuarios['id_usuarios_planos'];
		
		// ===== Dados do usuário.
		
		pagina_trocar_variavel_valor('usuario-nome','<a href="/usuarios/editar/?id='.$usuarios['id'].'">'.$usuarios['nome'].'</a>');
		pagina_trocar_variavel_valor('usuario-email',$usuarios['email']);
		pagina_trocar_variavel_valor('usuario-acesso',$usuarios['usuario']);
		
		// ===== Dados do domínio.
		
		pagina_trocar_variavel_valor('dominio',$retorno_bd['dominio']);
		pagina_trocar_variavel_valor('user_cpanel',$retorno_bd['user_cpanel']);
		pagina_trocar_variavel_valor('user_ftp',$retorno_bd['user_ftp']);
		pagina_trocar_variavel_valor('user_db',$retorno_bd['user_db']);
		
		// ===== Tratar versão do gestor.
		
		if($_GESTOR['gestor-cliente']['versao_num'] > (int)$retorno_bd['gestor_cliente_versao_num']){
			$retorno_bd['gestor_cliente_versao'] = '<span class="ui red text">' . $retorno_bd['gestor_cliente_versao'] . '</span> (Versão Atual do Gestor Cliente: <span class="ui info text">'.$_GESTOR['gestor-cliente']['versao'].'</span>)';
		} else {
			$retorno_bd['gestor_cliente_versao'] = '<span class="ui success text">' . $retorno_bd['gestor_cliente_versao'] . '</span>';
		}
		
		pagina_trocar_variavel_valor('gestor_cliente_versao',$retorno_bd['gestor_cliente_versao']);
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'removerNaoAlterarId' => true,
		'metaDados' => $metaDados,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
		'botoes' => Array(
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&id='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?id='.$id),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash' ),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown' ),
			),
			'excluir' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&id='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-delete')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'cor' => 'red',
			),
		),
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'user-profile',
					'nome' => 'usuario-plano',
					'placeholder' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-user-profile-placeholder')),
					'tabela' => Array(
						'nome' => 'usuarios_planos',
						'campo' => 'nome',
						'id_numerico' => 'id_usuarios_planos',
						'id_selecionado' => (isset($id_usuarios_planos) ? $id_usuarios_planos : null ),
					),
				)
			)
		)
	);
}

function admin_hosts_status(){
	global $_GESTOR;
	
	$id = $_GESTOR['modulo-registro-id'];
	$status = $_GESTOR['modulo-registro-status'];
	
	// ===== Pegar o identificador do usuário dono do host.
	
	$hosts = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts',
		'campos' => Array(
			'id_usuarios',
		),
		'extra' => 
			"WHERE user_cpanel='".$id."'"
	));
	
	$id_usuarios = $hosts['id_usuarios'];
	
	// ===== Suspender o usuário dono do host também.
	
	banco_update_campo('status',$status);
	banco_update_campo('versao',"versao + 1",true);
	banco_update_campo('data_modificacao','NOW()',true);
	
	banco_update_executar('usuarios',"WHERE id_usuarios='".$id_usuarios."'");
	
	// ===== Incluir no histórico do módulo usuários o bloqueio.
	
	if($status == 'A'){
		$valor_depois = 'field-status-active';
	} else {
		$valor_depois = 'field-status-inactive';
	}
	
	if($status == 'A'){
		$valor_antes = 'field-status-inactive';
	} else {
		$valor_antes = 'field-status-active';
	}
	
	interface_historico_incluir(Array(
		'alteracoes' => Array(
			Array(
				'campo' => 'field-status',
				'alteracao' => 'historic-change-status',
				'valor_antes' => $valor_antes,
				'valor_depois' => $valor_depois,
			)
		),
		'id_numerico_manual' => $id_usuarios,
		'modulo_id' => 'usuarios',
		'tabela' => Array(
			'nome' => 'usuarios',
			'versao' => 'versao',
			'id_numerico' => 'id_usuarios',
		),
	));
	
	// ===== Remover tokens do usuário.
	
	if($status == 'I'){
		banco_delete
		(
			"usuarios_tokens",
			"WHERE id_usuarios='".$id_usuarios."'"
		);
	}
	
	// ===== Executar suspensão ou remover suspensão do host no cPanel.
	
	gestor_incluir_biblioteca('cpanel');
	
	if($status == 'A'){
		cpanel_unsuspendacct(Array(
			'user' => $id,
		));
	} else {
		cpanel_suspendacct(Array(
			'user' => $id,
		));
	}
}

function admin_hosts_excluir(){
	global $_GESTOR;
	
	$id_numerico = $_GESTOR['modulo-registro-id-numerico'];
	
	// ===== Pegar os dados do host.
	
	$hosts = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts',
		'campos' => Array(
			'id_usuarios',
			'user_cpanel',
		),
		'extra' => 
			"WHERE id_hosts='".$id_numerico."'"
	));
	
	$user_cpanel = $hosts['user_cpanel'];
	$id_usuarios = $hosts['id_usuarios'];
	
	// ===== Alterar o status do usuário para 'D' - Deletado
	
	banco_update_campo('status','D');
	banco_update_campo('versao',"versao + 1",true);
	banco_update_campo('data_modificacao','NOW()',true);
	
	// ===== Incluir no histórico as alterações.
	
	interface_historico_incluir(Array(
		'alteracoes' => Array(
			Array(
				'alteracao' => 'historic-delete',
			)
		),
		'deletar' => true,
		'id_numerico_manual' => $id_usuarios,
		'modulo_id' => 'usuarios',
		'tabela' => Array(
			'nome' => 'usuarios',
			'versao' => 'versao',
			'id_numerico' => 'id_usuarios',
		),
	));
	
	// Executar deleção
	
	banco_update_executar('usuarios',"WHERE id_usuarios='".$id_usuarios."'");
	
	// ===== Remover tokens do usuário
	
	banco_delete
	(
		"usuarios_tokens",
		"WHERE id_usuarios='".$id_usuarios."'"
	);
	
	// ===== Executar remoção do host no cPanel.
	
	gestor_incluir_biblioteca('cpanel');
	
	cpanel_removeacct(Array(
		'user' => $user_cpanel,
	));

}

function admin_hosts_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'status':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'admin_hosts_status',
			);
		break;
		case 'excluir':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'admin_hosts_excluir',
			);
		break;
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'id_usuarios',
						'dominio',
						'user_cpanel',
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
							'id' => 'id_usuarios',
							'ordenar' => 'desc',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'usuarios',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id_usuarios',
								),
							)
						),
						Array(
							'id' => 'user_cpanel',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-user-cpanel')),
						),
						Array(
							'id' => 'dominio',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-domain')),
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
			);
		break;
	}
}

// ==== Ajax

function admin_hosts_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function admin_hosts_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': admin_hosts_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		admin_hosts_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': admin_hosts_adicionar(); break;
			case 'editar': admin_hosts_editar(); break;
		}
		
		interface_finalizar();
	}
}

admin_hosts_start();

?>