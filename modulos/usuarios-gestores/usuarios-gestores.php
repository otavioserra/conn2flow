<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'usuarios-gestores';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html','usuario'),
	'tabela' => Array(
		'nome' => 'usuarios',
		'id' => 'id',
		'id_numerico' => 'id_'.'usuarios',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
		'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
	),
	'historico' => Array(
		'moduloIdExtra' => 'perfil-usuario',
	),
);

function usuarios_adicionar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Módulo extra para variáveis globais.
	
	gestor_pagina_variaveis_modulos(Array(
		'modulosExtra' => Array(
			'usuarios',
		),
	));
	
	// ===== Gravar registro no Banco
	
	if(isset($_GESTOR['adicionar-banco'])){
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'usuario-perfil',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-profile-label')),
				),
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
					'min' => 12,
				),
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
			),
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteUsuario = interface_verificar_campos(Array(
			'campo' => 'usuario',
			'valor' => banco_escape_field($_REQUEST['usuario']),
		));
		
		if($exiteUsuario){
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['usuario']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar/');
		}
		
		$exiteEmail = interface_verificar_campos(Array(
			'campo' => 'email',
			'valor' => banco_escape_field($_REQUEST['email']),
		));
		
		if($exiteEmail){
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['email']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar/');
		}
		
		// ===== Gerar hash da senha
		
		$senha = banco_escape_field($_REQUEST['senha']);
		
		$senhaHash = password_hash($senha, PASSWORD_ARGON2I, ["cost" => 9]);
		
		// ===== Separar os nomes (primeiro, do meio e último)
		
		$nome = banco_escape_field($_REQUEST['nome']);
		
		$nomes = explode(' ',$nome);
		
		if(count($nomes) > 2){
			for($i=0;$i<count($nomes);$i++){
				if($i==0){
					$primeiro_nome = $nomes[$i];
				} else if($i==count($nomes) - 1){
					$ultimo_nome = $nomes[$i];
				} else {
					$nome_do_meio .= (isset($nome_do_meio) ? ' ':'') . $nomes[$i];
				}
			}
		} else if(count($nomes) > 1){
			$primeiro_nome = $nomes[0];
			$ultimo_nome = $nomes[1];
		} else {
			$primeiro_nome = $nomes[0];
		}
		
		// ===== Pegar o identificador do usuário proprietário do host.
		
		$id_hosts = $_GESTOR['host-id'];
		
		$hosts = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts',
			'campos' => Array(
				'id_usuarios',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
		));
		
		$IDUsuarioProprietario = $hosts['id_usuarios'];
		
		// ===== Pegar o identificador do perfil do usuário proprietário do host.
		
		$usuarios = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios',
			'campos' => Array(
				'id_usuarios_perfis',
			),
			'extra' => 
				"WHERE id_usuarios='".$IDUsuarioProprietario."'"
		));
		
		$id_usuarios_perfis = $usuarios['id_usuarios_perfis'];
		
		// ===== Verificar se o perfil enviado é herança de privilégios ou se é um perfil criado pelo usuário. Caso for um perfil próprio, vincular este perfil ao usuário.
		
		if($_REQUEST['usuario-perfil'] != 'pai'){
			$IDPerfilUsuarioGestor = banco_escape_field($_REQUEST['usuario-perfil']);
			
			$usuarios_gestores_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_gestores_perfis',
				'campos' => Array(
					'id',
				),
				'extra' => 
					"WHERE id_usuarios_gestores_perfis='".$IDPerfilUsuarioGestor."'"
					." AND id_hosts='".$_GESTOR['host-id']."'"
			));
			
			if($usuarios_gestores_perfis){
				$gestor_perfil = $usuarios_gestores_perfis['id'];
			}
		}
		
		// ===== Campos gerais
		
		$campo_nome = "id_hosts"; $campo_valor = $id_hosts;								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuarios_perfis"; $campo_valor = $id_usuarios_perfis;			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "nome_conta"; $post_nome = "nome"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$campo_nome = "usuario"; $post_nome = "usuario"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "senha"; $campo_valor = $senhaHash; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "email"; $post_nome = "email"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		$campo_nome = "primeiro_nome"; 					 								if(isset($primeiro_nome))		$campos[] = Array($campo_nome,$primeiro_nome);
		$campo_nome = "nome_do_meio"; 					 								if(isset($nome_do_meio))		$campos[] = Array($campo_nome,$nome_do_meio);
		$campo_nome = "ultimo_nome"; 					 								if(isset($ultimo_nome))		$campos[] = Array($campo_nome,$ultimo_nome);
		
		// ===== Dados do gestor.
		
		$campo_nome = "gestor"; $campo_valor = '1';										$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "gestor_perfil"; 					 								if(isset($gestor_perfil))		$campos[] = Array($campo_nome,$gestor_perfil);
		
		// ===== Campos comuns
		
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		$id_usuarios = banco_last_id();
		
		// ===== Verificar se o usuário tem privilégios de administração. Se sim, marcar para habilitar esta opção.
		
		if($_REQUEST['privilegios_admin'] == 'on'){
			$privilegios_admin = true;
		}
		
		// ===== Incluir o usuário na tabela de gestores do host.
		
		banco_insert_name_campo('id_hosts',$id_hosts);
		banco_insert_name_campo('id_usuarios',$id_usuarios);
		
		if(isset($privilegios_admin)){ banco_insert_name_campo('privilegios_admin','1',true); }
		
		banco_insert_name
		(
			banco_insert_name_campos(),
			"usuarios_gestores_hosts"
		);
		
		// ===== Redirecionar para a página de edição.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'usuario-perfil',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-profile-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexPermited',
							'regex' => '/^[a-z][a-z0-9]+(\.[a-z0-9]{2,})*([@]?([a-z0-9]{2,}\.)*[a-z0-9]{2,})*$/gi',
							'regexPermitedChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'user-permited-chars')),
						)
					),
				),
				Array(
					'regra' => 'email-comparacao-verificar-campo',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
					'identificador' => 'email',
					'comparcao' => Array(
						'id' => 'email-2',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
					)
				),
				Array(
					'regra' => 'email-comparacao',
					'campo' => 'email-2',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
					'identificador' => 'email-2',
					'comparcao' => Array(
						'id' => 'email',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
					)
				),
				Array(
					'regra' => 'senha-comparacao',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
					'identificador' => 'senha',
					'comparcao' => Array(
						'id' => 'senha-2',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
					)
				),
				Array(
					'regra' => 'senha-comparacao',
					'campo' => 'senha-2',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
					'identificador' => 'senha-2',
					'comparcao' => Array(
						'id' => 'senha',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
					)
				),
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'user-profile',
					'nome' => 'usuario-perfil',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-manager-profile-placeholder')),
					'tabela' => Array(
						'nome' => 'usuarios_gestores_perfis',
						'campo' => 'nome',
						'id_numerico' => 'id_usuarios_gestores_perfis',
					),
					'dadosAntes' => Array(
						Array(
							'texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'option-parent-label')),
							'valor' => 'pai',
						),
					),
				)
			)
		)
	);
}

function usuarios_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Módulo extra para variáveis globais.
	
	gestor_pagina_variaveis_modulos(Array(
		'modulosExtra' => Array(
			'usuarios',
		),
	));
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'id_usuarios',
		'id_usuarios_perfis',
		'nome',
		'nome_conta',
		'email',
		'usuario',
		'primeiro_nome',
		'ultimo_nome',
		'nome_do_meio',
		'gestor_perfil',
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
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios
		
		$campos_obrigatorios = Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-user-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome_conta',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-account-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'usuario-perfil',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-profile-label')),
				),
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
				),
			)
		);
		
		if(isset($_REQUEST['senha-atualizar'])){
			$campos_obrigatorios['campos'][] = Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'senha',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
				'min' => 12,
			);
		}
		
		interface_validacao_campos_obrigatorios($campos_obrigatorios);
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteUsuario = interface_verificar_campos(Array(
			'campo' => 'usuario',
			'valor' => banco_escape_field($_REQUEST['usuario']),
		));
		
		if($exiteUsuario){
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['usuario']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
		}
		
		$exiteEmail = interface_verificar_campos(Array(
			'campo' => 'email',
			'valor' => banco_escape_field($_REQUEST['email']),
		));
		
		if($exiteEmail){
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['email']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
		}
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'",
		);
		
		$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se mudar o nome, mudar o identificador do registro
		
		if(isset($alterar_id)){
			$id_novo = banco_identificador(Array(
				'id' => banco_escape_field($_REQUEST["nome"]),
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campo' => $modulo['tabela']['id'],
					'id_nome' => $modulo['tabela']['id_numerico'],
					'id_valor' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
				),
			));
			
			$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Gerar hash da senha
		
		if(isset($_REQUEST['senha-atualizar'])){
			$senha = banco_escape_field($_REQUEST['senha']);
			
			$senhaHash = password_hash($senha, PASSWORD_ARGON2I, ["cost" => 9]);
		}
		
		// ===== Separar os nomes (primeiro, do meio e último)
		
		$nome = banco_escape_field($_REQUEST['nome']);
		
		$nomes = explode(' ',$nome);
		
		if(count($nomes) > 2){
			for($i=0;$i<count($nomes);$i++){
				if($i==0){
					$primeiro_nome = $nomes[$i];
				} else if($i==count($nomes) - 1){
					$ultimo_nome = $nomes[$i];
				} else {
					$nome_do_meio .= (isset($nome_do_meio) ? ' ':'') . $nomes[$i];
				}
			}
		} else if(count($nomes) > 1){
			$primeiro_nome = $nomes[0];
			$ultimo_nome = $nomes[1];
		} else {
			$primeiro_nome = $nomes[0];
		}
		
		// ===== Verificar se o perfil enviado é herança de privilégios ou se é um perfil criado pelo usuário. Caso for um perfil próprio, vincular este perfil ao usuário.
		
		$id_hosts = $_GESTOR['host-id'];
		
		if($_REQUEST['usuario-perfil'] != 'pai'){
			$id_usuarios_gestores_perfis = banco_escape_field($_REQUEST['usuario-perfil']);
			
			$usuarios_gestores_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_gestores_perfis',
				'campos' => Array(
					'nome',
					'id',
				),
				'extra' => 
					"WHERE id_usuarios_gestores_perfis='".$id_usuarios_gestores_perfis."'"
					." AND id_hosts='".$id_hosts."'"
			));
			
			$usuarioPerfilGestor = $usuarios_gestores_perfis['id'];
		} else {
			$usuarioPerfilGestor = NULL;
		}
		
		$campo_nome = "gestor_perfil"; $request_name = 'usuario-perfil'; $alteracoes_name = 'user-manager-profile'; if(banco_select_campos_antes($campo_nome) != $usuarioPerfilGestor){
			
			// ===== Valor antes da mudança.
			
			if(banco_select_campos_antes($campo_nome)){
				$usuarios_gestores_perfis_antes = banco_select(Array(
					'unico' => true,
					'tabela' => 'usuarios_gestores_perfis',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id='".banco_select_campos_antes($campo_nome)."'"
						." AND status!='D'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$usuarioPerfilValorAntes = $usuarios_gestores_perfis_antes['nome'];
			} else {
				$usuarioPerfilValorAntes = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'option-parent-label'));
			}
			
			// ===== Atualizar conforme o perfil é do pai ou não.
			
			if($_REQUEST['usuario-perfil'] != 'pai'){
				if($usuarios_gestores_perfis){
					$gestor_perfil = $usuarios_gestores_perfis['id'];
					$usuarioPerfilValorDepois = $usuarios_gestores_perfis['nome'];
					
					$editar['dados'][] = $campo_nome."='" . $gestor_perfil . "'";
				} else {
					$editar['dados'][] = $campo_nome."=NULL";
					$usuarioPerfilValorDepois = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'option-parent-label'));
				}
			} else {
				$editar['dados'][] = $campo_nome."=NULL";
				$usuarioPerfilValorDepois = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'option-parent-label'));
			}
			
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => $usuarioPerfilValorAntes,'valor_depois' => $usuarioPerfilValorDepois);
		}
		
		// ===== Atualização dos demais campos.
		
		$campo_nome = "nome_conta"; $request_name = $campo_nome; $alteracoes_name = 'name-account'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		$campo_nome = "usuario"; $request_name = $campo_nome; $alteracoes_name = 'user'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		if(isset($_REQUEST['senha-atualizar'])){
			$campo_nome = "senha"; $alteracoes_name = 'password'; $editar['dados'][] = $campo_nome."='" . $senhaHash . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');
		}
		
		$campo_nome = "email"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		$campo_nome = "primeiro_nome"; $request_name = $campo_nome; $alteracoes_name = 'first-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
		$campo_nome = "ultimo_nome"; $request_name = $campo_nome; $alteracoes_name = 'last-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
		$campo_nome = "nome_do_meio"; $request_name = $campo_nome; $alteracoes_name = 'middle-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
		
		
		// ===== Verificar se houve alteração do privilégio de administração.
		
		$id_usuarios = interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico']));
		
		$usuarios_gestores_hosts = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_gestores_hosts',
			'campos' => Array(
				'privilegios_admin',
			),
			'extra' => 
				"WHERE id_usuarios='".$id_usuarios."'"
				." AND id_hosts='".$id_hosts."'"
		));
		
		if($usuarios_gestores_hosts['privilegios_admin']){
			$privilegios_admin_antes = true;
		} else {
			$privilegios_admin_antes = false;
		}
		
		if($_REQUEST['privilegios_admin'] == 'on'){
			$privilegios_admin_depois = true;
		} else {
			$privilegios_admin_depois = false;
		}
		
		if($privilegios_admin_antes != $privilegios_admin_depois){
			$privilegios_admin_mudou = true;
			
			banco_update_campo('privilegios_admin',($privilegios_admin_depois ? '1' : 'NULL'),true);
			
			banco_update_executar(
				'usuarios_gestores_hosts',
				"WHERE id_usuarios='".$id_usuarios."'"
				." AND id_hosts='".$id_hosts."'"
			);
			
			$alteracoes_name = 'permission';
			
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'filtro' => 'checkbox','valor_antes' => ($privilegios_admin_antes ? '1' : '0'),'valor_depois' => ($privilegios_admin_depois ? '1' : '0'));
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados']) || isset($privilegios_admin_mudou)){
			$campo_nome = $modulo['tabela']['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = $modulo['tabela']['data_modificacao']; $editar['dados'][] = $campo_nome."=NOW()";
			
			$editar['sql'] = banco_campos_virgulas($editar['dados']);
			
			if($editar['sql']){
				banco_update
				(
					$editar['sql'],
					$editar['tabela'],
					$editar['extra']
				);
			}
			$editar = false;
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
			
			// ===== Remover tokens do usuário
			
			$id_usuarios = interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico']));
			
			banco_delete
			(
				"usuarios_tokens",
				"WHERE id_usuarios='".$id_usuarios."'"
			);
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
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
		$id_usuarios = (isset($retorno_bd['id_usuarios']) ? $retorno_bd['id_usuarios'] : '');
		$gestor_perfil = (isset($retorno_bd['gestor_perfil']) ? $retorno_bd['gestor_perfil'] : '');
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$nome_conta = (isset($retorno_bd['nome_conta']) ? $retorno_bd['nome_conta'] : '');
		$email = (isset($retorno_bd['email']) ? $retorno_bd['email'] : '');
		$usuario = (isset($retorno_bd['usuario']) ? $retorno_bd['usuario'] : '');
		$primeiro_nome = (isset($retorno_bd['primeiro_nome']) ? $retorno_bd['primeiro_nome'] : '');
		$ultimo_nome = (isset($retorno_bd['ultimo_nome']) ? $retorno_bd['ultimo_nome'] : '');
		$nome_do_meio = (isset($retorno_bd['nome_do_meio']) ? $retorno_bd['nome_do_meio'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome_conta#',$nome_conta);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#email#',$email);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#usuario#',$usuario);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#primeiro_nome#',$primeiro_nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#ultimo_nome#',$ultimo_nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome_do_meio#',$nome_do_meio);
		
		// ===== Mudar ou não a senha
		
		if(isset($_REQUEST['password-button'])){
			$cel_nome = 'senha-botao'; $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		} else {
			$cel_nome = 'senha-campos'; $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		}
		
		// ===== Pegar id numérico do perfil gestor do usuário.
		
		$id_hosts = $_GESTOR['host-id'];
		
		if(existe($gestor_perfil)){
			$usuarios_gestores_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_gestores_perfis',
				'campos' => Array(
					'id_usuarios_gestores_perfis',
				),
				'extra' => 
					"WHERE id='".$gestor_perfil."'"
					." AND id_hosts='".$id_hosts."'"
					." AND status!='D'"
			));
			
			if($usuarios_gestores_perfis){
				$id_usuarios_gestores_perfis = $usuarios_gestores_perfis['id_usuarios_gestores_perfis'];
			} else {
				$id_usuarios_gestores_perfis = 'pai';
			}
		} else {
			$id_usuarios_gestores_perfis = 'pai';
		}
		
		// ===== Verificar se o usuário tem permissão administrativa.
		
		$usuarios_gestores_hosts = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_gestores_hosts',
			'campos' => Array(
				'privilegios_admin',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
				." AND id_usuarios='".$id_usuarios."'"
		));
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#checked#',($usuarios_gestores_hosts['privilegios_admin'] ? 'checked' : ''));
		
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
		'metaDados' => $metaDados,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
		'botoes' => Array(
			'adicionar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash' ),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown' ),
			),
			'excluir' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id,
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
					'campo' => 'nome_conta',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-account-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-user-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'usuario-perfil',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-profile-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexPermited',
							'regex' => '/^[a-z][a-z0-9]+(\.[a-z0-9]{2,})*([@]?([a-z0-9]{2,}\.)*[a-z0-9]{2,})*$/gi',
							'regexPermitedChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'user-permited-chars')),
						)
					),
				),
				Array(
					'regra' => 'email-comparacao-verificar-campo',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
					'identificador' => 'email',
					'comparcao' => Array(
						'id' => 'email-2',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
					)
				),
				Array(
					'regra' => 'email-comparacao',
					'campo' => 'email-2',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
					'identificador' => 'email-2',
					'comparcao' => Array(
						'id' => 'email',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
					)
				),
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'user-profile',
					'nome' => 'usuario-perfil',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-manager-profile-placeholder')),
					'tabela' => Array(
						'nome' => 'usuarios_gestores_perfis',
						'campo' => 'nome',
						'id_numerico' => 'id_usuarios_gestores_perfis',
						'id_selecionado' => $id_usuarios_gestores_perfis,
					),
					'valor_selecionado' => $id_usuarios_gestores_perfis,
					'dadosAntes' => Array(
						Array(
							'texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'option-parent-label')),
							'valor' => 'pai',
						),
					),
				)
			)
		)
	);
	
	// ===== 
	
	if(isset($_REQUEST['password-button'])){
		$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
			'identificador' => 'senha',
			'comparcao' => Array(
				'id' => 'senha-2',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
			)
		);
		$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha-2',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
			'identificador' => 'senha-2',
			'comparcao' => Array(
				'id' => 'senha',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
			)
		);
	}
}

function usuarios_status(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Remover tokens do usuário
	
	$mudar_status = $_GESTOR['modulo-registro-status'];
	
	if($mudar_status == 'I'){
		$id_usuarios = interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico']));
		
		banco_delete
		(
			"usuarios_tokens",
			"WHERE id_usuarios='".$id_usuarios."'"
		);
	}
}

function usuarios_excluir(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Remover tokens do usuário
	
	$id_usuarios = interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico']));
	
	banco_delete
	(
		"usuarios_tokens",
		"WHERE id_usuarios='".$id_usuarios."'"
	);
}

function usuarios_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'nome_conta',
						'gestor_perfil',
						$modulo['tabela']['data_criacao'],
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
				),
				'tabela' => Array(
					'rodape' => true,
					'colunas' => Array(
						Array(
							'id' => 'nome',
							'nome' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-name-user-label')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'nome_conta',
							'nome' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-name-account-label')),
						),
						Array(
							'id' => 'gestor_perfil',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-manager-profile-label')),
							'valor_senao_existe' => '<span class="ui text info">'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'list-parent-label')).'</span>',
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'usuarios_gestores_perfis',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
									'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
								),
							)
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

function usuarios_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function usuarios_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': usuarios_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		usuarios_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': usuarios_adicionar(); break;
			case 'editar': usuarios_editar(); break;
			case 'status': usuarios_status(); break;
			case 'excluir': usuarios_excluir(); break;
		}
		
		interface_finalizar();
	}
}

usuarios_start();

?>