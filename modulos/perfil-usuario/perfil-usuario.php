<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'perfil-usuario';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.1.6',
	'bibliotecas' => Array('interface','html','usuario'),
	'tabela' => Array(
		'nome' => 'usuarios',
		'id' => 'id',
		'id_numerico' => 'id_'.'usuarios',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
	'historico' => Array(
		'moduloIdExtra' => 'usuarios',
	),
	'interfaceNaoAplicarIdHost' => true,
);

function perfil_usuario_area_restrita(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_REQUEST['_gestor-restrict-area-atualizar'])){
		$usuario = gestor_usuario();
		
		// ===== Pegar a senha do banco de dados e comparar com a senha enviada.
		
		$senha = banco_escape_field($_REQUEST['senha']);
		$querystring = (isset($_REQUEST['_gestor-restrict-area-querystring']) ? banco_escape_field($_REQUEST['_gestor-restrict-area-querystring']) : '');
		
		$redirect = gestor_querystring_variavel($querystring,'redirect');
		$querystring = gestor_querystring_remover_variavel($querystring,'redirect');
		
		$usuarios = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios',
				'senha',
				'status',
			))
			,
			"usuarios",
			"WHERE id_usuarios='".$usuario['id_usuarios']."'"
			." AND status='A'"
		);
		
		$user_invalid = true;
		
		// ===== Rotinas de validação de usuário
		
		if($usuarios){
			$senha_hash = $usuarios[0]['senha'];
			
			if(password_verify($senha, $senha_hash)){
				$user_invalid = false;
				usuario_autorizacao_provisoria(Array('validar' => true));
				
				banco_update
				(
					"senha_incorreta_tentativas=NULL",
					"usuarios_tokens",
					"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
				);
				
				gestor_redirecionar($redirect,$querystring);
			}
		}
		
		if($user_invalid){
			$usuarios_tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'senha_incorreta_tentativas',
				))
				,
				"usuarios_tokens",
				"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
			);
			
			if(!$usuarios_tokens[0]['senha_incorreta_tentativas']){
				$tentativas = 1;
			} else {
				$tentativas = (int)$usuarios_tokens[0]['senha_incorreta_tentativas'] + 1;
			}
			
			$maximoSenhasInvalidas = $_CONFIG['usuario-maximo-senhas-invalidas'];
			
			sleep(3);
			
			if($tentativas < $maximoSenhasInvalidas){
				$msg = modelo_var_troca_tudo(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-password-invalid')),"#tentativas#",($maximoSenhasInvalidas - $tentativas));
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $msg
				));
				
				banco_update
				(
					"senha_incorreta_tentativas='".$tentativas."'",
					"usuarios_tokens",
					"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
				);
				
				gestor_redirecionar('restrict-area/?redirect='.urlencode($redirect).(existe($querystring) ? '&'.$querystring:''));
			} else {
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-password-invalid-finish'))
				));
				
				perfil_usuario_signout();
			}
		}
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Alterar dados do formulário de validação
	
	$queryString = gestor_querystring();
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-action#",$_GESTOR['url-raiz'].'restrict-area/');
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-querystring#",$queryString);
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	// ===== Validação do formulário
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
		),
	);
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'id_usuarios_perfis',
		'nome_conta',
		'nome',
		'email',
		'usuario',
		'primeiro_nome',
		'ultimo_nome',
		'nome_do_meio',
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;
	
	// ===== Mudar Campos parâmetros iniciais
	
	$mudarCampos = Array('nome','email','usuario','senha');
	$mudarCampo = false;
	$mudarCampoBanco = false;
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Verificar qual campo está sendo mudado
		
		foreach($mudarCampos as $mc){
			if(isset($_REQUEST['mudar-'.$mc.'-banco'])){
				$mudarCampoBanco = $mc;
			}
		}
		
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
		
		if($mudarCampoBanco){
			if(!usuario_autorizacao_provisoria(Array(
				'verificar' => true,
			))){
				$alerta = gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-authorization-provisory-not-defined'));
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
				
				gestor_redirecionar_raiz();
			}
		}
		
		// ===== Validação de campos obrigatórios
		
		switch($mudarCampoBanco){
			case 'nome':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-name-label')),
				);
			break;
			case 'email':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
				);
			break;
			case 'usuario':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-user-label')),
				);
			break;
			case 'senha':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
					'min' => 12,
				);
			break;
		}
		
		interface_validacao_campos_obrigatorios($campos_obrigatorios);
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		switch($mudarCampoBanco){
			case 'usuario':
				$exiteUsuario = interface_verificar_campos(Array(
					'campo' => 'usuario',
					'valor' => banco_escape_field($_REQUEST['usuario']),
				));
				
				if($exiteUsuario){
					$alerta = gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-there-is-a-field'));
					$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-user-label')));
					$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['usuario']));
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
				}
			break;
			case 'email':
				$exiteEmail = interface_verificar_campos(Array(
					'campo' => 'email',
					'valor' => banco_escape_field($_REQUEST['email']),
				));
				
				if($exiteEmail){
					$alerta = gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-there-is-a-field'));
					$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')));
					$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['email']));
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
				}
			break;
		}
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'",
		);
		
		switch($mudarCampoBanco){
			case 'nome':
				$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
			break;
		}
		
		// ===== Gerar hash da senha
		
		switch($mudarCampoBanco){
			case 'senha':
				$senha = banco_escape_field($_REQUEST['senha']);
				
				$senhaHash = password_hash($senha, PASSWORD_ARGON2I, ["cost" => 9]);
			break;
		}
		
		// ===== Separar os nomes (primeiro, do meio e último)
		
		switch($mudarCampoBanco){
			case 'nome':
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
			break;
		}
		
		// ===== Atualização dos demais campos.
		
		switch($mudarCampoBanco){
			case 'nome':
				$campo_nome = "primeiro_nome"; $request_name = $campo_nome; $alteracoes_name = 'first-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
				$campo_nome = "ultimo_nome"; $request_name = $campo_nome; $alteracoes_name = 'last-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
				$campo_nome = "nome_do_meio"; $request_name = $campo_nome; $alteracoes_name = 'middle-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
			break;
			case 'email':
				$campo_nome = "email"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
			break;
			case 'usuario':
				$campo_nome = "usuario"; $request_name = $campo_nome; $alteracoes_name = 'user'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
			break;
			case 'senha':
				$campo_nome = "senha"; $alteracoes_name = 'password'; $editar['dados'][] = $campo_nome."='" . $senhaHash . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');
			break;
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
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
			
			// ===== Pegar dados do usuário token atual.
			
			$tokenPubId = $_GESTOR['usuario-token-id'];
			
			$usuarios_tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					//'fingerprint',
					'expiration',
				))
				,
				"usuarios_tokens",
				"WHERE pubID='".$tokenPubId."'"
			);
			
			//$fingerprint = $usuarios_tokens[0]['fingerprint'];
			$expiration = $usuarios_tokens[0]['expiration'];
			
			// ===== Remover todos os usuários tokens do usuário atual.
			
			$usuarioAtual = gestor_usuario();
			$id_usuarios = $usuarioAtual['id_usuarios'];
			
			banco_delete
			(
				"usuarios_tokens",
				"WHERE id_usuarios='".$id_usuarios."'"
			);
			
			// ===== Renovar o usuário token deste usuário.
			
			if((int)$expiration > 0){
				usuario_gerar_token_autorizacao(Array(
					'id_usuarios' => $id_usuarios,
					//'fingerprint' => $fingerprint,
				));
			} else {
				usuario_gerar_token_autorizacao(Array(
					'id_usuarios' => $id_usuarios,
					//'fingerprint' => $fingerprint,
					'sessao' => true,
				));
			}
			
			// ===== Habilitar o browser fingerprint.
			
			//gestor_sessao_variavel('browser-fingerprint',true);
		}
		
		// ===== Invalidar a autorização provisória
		
		usuario_autorizacao_provisoria(Array('invalidar' => true));
		
		// ===== Reler URL.
		
		gestor_reload_url();
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
		$id_usuarios_perfis = (isset($retorno_bd['id_usuarios_perfis']) ? $retorno_bd['id_usuarios_perfis'] : '');
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$nome_conta = (isset($retorno_bd['nome_conta']) ? $retorno_bd['nome_conta'] : '');
		$email = (isset($retorno_bd['email']) ? $retorno_bd['email'] : '');
		$usuario = (isset($retorno_bd['usuario']) ? $retorno_bd['usuario'] : '');
		$primeiro_nome = (isset($retorno_bd['primeiro_nome']) ? $retorno_bd['primeiro_nome'] : '');
		$ultimo_nome = (isset($retorno_bd['ultimo_nome']) ? $retorno_bd['ultimo_nome'] : '');
		$nome_do_meio = (isset($retorno_bd['nome_do_meio']) ? $retorno_bd['nome_do_meio'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#user-profile#',interface_formatar_dado(Array(
			'dado' => $id_usuarios_perfis,
			'formato' => Array(
				'id' => 'outraTabela',
				'tabela' => Array(
					'nome' => 'usuarios_perfis',
					'campo_trocar' => 'nome',
					'campo_referencia' => 'id_usuarios_perfis',
				),
			),
		)));
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome_conta#',$nome_conta);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#email#',$email);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#usuario#',$usuario);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#primeiro_nome#',$primeiro_nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#ultimo_nome#',$ultimo_nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome_do_meio#',$nome_do_meio);
		
		// ===== Mudar ou não a senha
		
		foreach($mudarCampos as $mc){
			if(isset($_REQUEST['mudar-'.$mc])){
				$mudarCampo = $mc;
			} else {
				$cel_nome = $mc.'-campos'; $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			}
		}
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Gestor adicionar variáveis globais de outro módulo.
	
	gestor_pagina_variaveis_modulos(Array(
		'modulosExtra' => Array(
			'usuarios',
		),
	));
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'removerNaoAlterarId' => true,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		)
	);
	
	// ===== Remover botão editar senão precisar e obrigar fornecer senha
	
	if(!$mudarCampo){
		$_GESTOR['interface']['editar']['finalizar']['removerBotaoEditar'] = true;
	} else {
		usuario_autorizacao_provisoria(Array(
			'verificarModal' => Array(
				'cancelarUrl' => 'perfil-usuario/',
				'autorizadoUrl' => 'perfil-usuario/',
				'autorizadoUrlQuerystring' => 'mudar-'.$mudarCampo.'=sim',
			),
		));
	}
	
	// ===== Formulário validação conforme necessidade.
	
	switch($mudarCampo){
		case 'nome':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'nome',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-name-label')),
			);
		break;
		case 'usuario':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'texto-obrigatorio-verificar-campo',
				'campo' => 'usuario',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-user-label')),
				'regrasExtra' => Array(
					Array(
						'regra' => 'regexPermited',
						'regex' => '/^[a-z][a-z0-9]+(\.[a-z0-9]{2,})*([@]?([a-z0-9]{2,}\.)*[a-z0-9]{2,})*$/gi',
						'regexPermitedChars' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'user-permited-chars')),
					)
				),
			);
		break;
		case 'email':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'email-comparacao-verificar-campo',
				'campo' => 'email',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
				'identificador' => 'email',
				'comparcao' => Array(
					'id' => 'email-2',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-2-label')),
				)
			);
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'email-comparacao',
				'campo' => 'email-2',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-2-label')),
				'identificador' => 'email-2',
				'comparcao' => Array(
					'id' => 'email',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-2-label')),
				)
			);
		break;
		case 'senha':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'senha-comparacao',
				'campo' => 'senha',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
				'identificador' => 'senha',
				'comparcao' => Array(
					'id' => 'senha-2',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-2-label')),
				)
			);
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'senha-comparacao',
				'campo' => 'senha-2',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-2-label')),
				'identificador' => 'senha-2',
				'comparcao' => Array(
					'id' => 'senha',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-2-label')),
				)
			);
		break;
	}
}

function perfil_usuario_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$usuario = gestor_usuario();
	
	switch($_GESTOR['opcao']){
		case 'editar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['iniciar'] = Array(
				'forcarId' => $usuario['id'],
			);
		break;
	}
}

// ==== Sair do sistema

function perfil_usuario_signout(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_GESTOR['usuario-token-id'])){
		gestor_sessao_del();
		
		banco_delete
		(
			"usuarios_tokens",
			"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
		);
		
		setcookie($_CONFIG['cookie-authname'], "", [
			'expires' => time() - 3600,
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		unset($_COOKIE[$_CONFIG['cookie-authname']]);
	}
	
	gestor_redirecionar('signin/');
}

// ==== Acessos públicos

function perfil_usuario_signin(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Verificar a permissão do acesso.
	
	gestor_incluir_biblioteca('autenticacao');
	
	$acesso = autenticacao_acesso_verificar(['tipo' => 'login']);
	
	// ===== Tratar a função logar.
	
	if(isset($_REQUEST['_gestor-logar']) && $acesso['permitido']){
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
				),
			)
		));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_REQUEST['token'];
				$action = $_REQUEST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				echo '_REQUEST>> '.print_r($_REQUEST,true)."<br>";
				echo 'arrResponse>> '.print_r($arrResponse,true)."<br>";exit;
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		$user_invalid = true;
		
		if($recaptchaValido){
			// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
			
			$usuario = banco_escape_field($_REQUEST['usuario']);
			$senha = banco_escape_field($_REQUEST['senha']);
			$user_inactive = false;
			
			$usuarios = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuarios',
					'senha',
					'status',
				))
				,
				"usuarios",
				"WHERE usuario='".$usuario."'"
				." AND status!='D'"
			);
			
			// ===== Rotinas de validação de usuário
			
			if($usuarios){
				$senha_hash = $usuarios[0]['senha'];
				
				if(password_verify($senha, $senha_hash)){
					// ===== Verificar se o usuário já está logado, caso esteja, deletar token anterior no banco.
					
					if(gestor_permissao_token()){
						if(isset($_GESTOR['usuario-token-id'])){
							banco_delete
							(
								"usuarios_tokens",
								"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
							);
						}
					}
					
					// ===== Pegar dados do usuário.
					
					$status = $usuarios[0]['status'];
					$id_usuarios = $usuarios[0]['id_usuarios'];
					
					if($status == 'A'){
						$user_invalid = false;
						
						// ===== Caso o usuário escolher a opção para manter logado, gera token de autenticação com tempo de expiração, senão será expirado assim que o usuário fechar navegador
						
						if(isset($_REQUEST['permanecer-logado'])){
							usuario_gerar_token_autorizacao(Array(
								'id_usuarios' => $id_usuarios,
							));
						} else {
							usuario_gerar_token_autorizacao(Array(
								'id_usuarios' => $id_usuarios,
								'sessao' => true,
							));
						}
						
					} else {
						$user_inactive = true;
					}
				}
			}
		} else {
			// ===== Se o recaptcha for inválido, alertar o usuário.
			
			sleep(3);
			
			$botaoTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid-btn'));
			
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid'));
			
			$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('signin/');
		}
	
		// ===== Se o usuário for inválido, redirecionar signin.
		
		if($user_invalid){
			autenticacao_acesso_falha(['tipo' => 'login']);
			
			sleep(3);
			
			if($user_inactive){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-inactive'))
				));
			} else {
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-or-password-invalid'))
				));
			}
			
			gestor_redirecionar('signin/');
		}
		
		// ===== Se o usuário for válido, redirecionar para o local pretendido se houver, senão para dashboard.
		
		if(existe(gestor_sessao_variavel("redirecionar-local"))){
			gestor_redirecionar();
		} else {
			gestor_redirecionar('dashboard/');
		}
	}
	
	// ===== Mostrar ou ocultar mensagem de bloqueio caso o IP esteja bloqueado.
	
	if($acesso['permitido']){
		gestor_incluir_biblioteca('pagina');
		
		$cel_nome = 'bloqueado-mensagem'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	//gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'fingerprint3-3.1.0/fp.min.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Interface finalizar opções
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'usuario',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
		),
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
		),
	);
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_signup(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_REQUEST['_gestor-signup'])){
		$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
					'min' => 12,
				),
			)
		));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active'])){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_POST['token'];
				$action = $_POST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		if($recaptchaValido){
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
				
				gestor_redirecionar('signup/');
			}
			
			// ===== Independente do plano que o usuário escolher, sempre iniciar o mesmo com o perfil do usuário padrão.
			
			$usuarios_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_perfis',
				'campos' => Array(
					'id_usuarios_perfis',
				),
				'extra' => 
					"WHERE padrao IS NOT NULL"
			));
			
			if($usuarios_perfis['id_usuarios_perfis']){
				$id_usuarios_perfis = $usuarios_perfis['id_usuarios_perfis'];
			} else {
				$id_usuarios_perfis = $_CONFIG['usuario-perfil-id-padrao'];
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
			
			// ===== Campos gerais
			
			$campo_nome = "id_usuarios_perfis"; $campo_valor = $id_usuarios_perfis;			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			$campo_nome = "nome_conta"; $post_nome = "nome"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			$campo_nome = "usuario"; $post_nome = "email"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			$campo_nome = "senha"; $campo_valor = $senhaHash; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "email"; $post_nome = "email"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			
			$campo_nome = "primeiro_nome"; 					 								if(isset($primeiro_nome))		$campos[] = Array($campo_nome,$primeiro_nome);
			$campo_nome = "nome_do_meio"; 					 								if(isset($nome_do_meio))		$campos[] = Array($campo_nome,$nome_do_meio);
			$campo_nome = "ultimo_nome"; 					 								if(isset($ultimo_nome))		$campos[] = Array($campo_nome,$ultimo_nome);
			
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
			
			// ===== Criar um plano de usuário para o usuário em questão.
			
			if(isset($_REQUEST['plano'])){
				$id_usuarios_planos = banco_escape_field($_REQUEST['plano']);
				
				$campos = null; $campo_sem_aspas_simples = null;
				
				$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_usuarios_planos"; $campo_valor = $id_usuarios_planos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "status"; $campo_valor = 'P';					 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples); // P - Pendente
				$campo_nome = "data_criacao"; $campo_valor = 'NOW()';					 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				banco_insert_name
				(
					$campos,
					"usuarios_planos_usuarios"
				);
			}
			
			// ===== Criar pré host e vincular o usuário ao mesmo, bem como indicar o status 'I' que necessita de instalação
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = 'I';					 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples); // I - Pendente de Instalação
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()';						 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"hosts"
			);
			
			$id_hosts = banco_last_id();
			
			// ===== Vincular host aos usuários admins do host do mesmo
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "privilegios_admin"; $campo_valor = '1'; 								$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"usuarios_gestores_hosts"
			);
			
			// ===== Logar o usuário 
			
			usuario_gerar_token_autorizacao(Array(
				'id_usuarios' => $id_usuarios,
			));
			
			// ===== Criar o token e guardar o mesmo no banco
			
			$tokenPubId = md5(uniqid(rand(), true));
			$expiration = time() + $_CONFIG['token-lifetime'];

			$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = 'new-register'; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "pubID"; $campo_valor = $pubID; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 			$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"tokens"
			);
			
			$tokens_id = banco_last_id();
			
			// ===== Enviar o email confirmando o cadastro junto com a URL de confirmação do email.
			
			$nome = $_REQUEST['nome'];
			$email = $_REQUEST['email'];
			$numero = date('Ymd') . $tokens_id;
			
			$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'signup-mail-subject')),"#numero#",$numero);
			
			gestor_incluir_biblioteca('comunicacao');
			
			if(comunicacao_email(Array(
				'destinatarios' => Array(
					Array(
						'email' => $email,
						'nome' => $nome,
					),
				),
				'mensagem' => Array(
					'assunto' => $assunto,
					'htmlLayoutID' => 'layout-email-novo-cadastro',
					'htmlVariaveis' => Array(
						Array(
							'variavel' => '#nome#',
							'valor' => $nome,
						),
						Array(
							'variavel' => '#url-signin#',
							'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'signin/">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'signin/</a>',
						),
						Array(
							'variavel' => '#url-confirmacao#',
							'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'email-confirmation/?id='.$tokenPubId.'">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'email-confirmation/?id='.$tokenPubId.'</a>',
						),
						Array(
							'variavel' => '#assinatura#',
							'valor' => gestor_componente(Array(
								'id' => 'layout-emails-assinatura',
							)),
						),
					),
				),
			))){
				// Email de confirmação enviado com sucesso!
			}
		} else {
			// ===== Se o recaptcha for inválido, alertar o usuário.
			
			sleep(3);
			
			$botaoTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid-btn'));
			
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid'));
			
			$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('signup/');
		}
		
		// ===== Se o usuário for válido, redirecionar para o local pretendido se houver, senão para dashboard.
		
		if(existe(gestor_sessao_variavel("redirecionar-local"))){
			gestor_redirecionar();
		} else {
			gestor_redirecionar('dashboard/');
		}
	}
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active'])){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Planos
	
	$cel_nome = 'plano-cel'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_usuarios_planos',
			'nome',
		))
		,
		"usuarios_planos",
		"WHERE status='A'"
		." AND publico IS NOT NULL"
		." ORDER BY ordem ASC"
	);
	
	if($resultado){
		$checked = true;
		
		foreach($resultado as $res){
			$val = $res['id_usuarios_planos'];
			$nome = $res['nome'];
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,' #checked#=""',($checked ? ' checked="checked"':''));
			$cel_aux = modelo_var_troca($cel_aux,"#val#",$val);
			$cel_aux = modelo_var_troca($cel_aux,"#nome#",$nome);
			
			$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->',$cel_aux);
			
			$checked = false;
		}
	}
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->','');
	
	// ===== Interface finalizar opções
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'nome',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
		),
		Array(
			'regra' => 'email-comparacao',
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
	);
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_forgot_password(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_REQUEST['_gestor-forgot-password'])){
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
				),
			)
		));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active'])){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_POST['token'];
				$action = $_POST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		$user_invalid = true;
		
		if($recaptchaValido){
			// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
			
			$email = banco_escape_field($_REQUEST['email']);
			
			$user_inactive = false;
			
			$usuarios = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuarios',
					'nome',
					'email',
					'status',
				))
				,
				"usuarios",
				"WHERE email='".$email."'"
				." AND status!='D'"
			);
			
			// ===== Rotinas de validação de usuário
			
			if($usuarios){
				$status = $usuarios[0]['status'];
				$id_usuarios = $usuarios[0]['id_usuarios'];
				$email = $usuarios[0]['email'];
				$nome = $usuarios[0]['nome'];
				
				if($status == 'A'){
					// ===== Criar o token e guardar o mesmo no banco
					
					$tokenPubId = md5(uniqid(rand(), true));
					$expiration = time() + $_CONFIG['token-lifetime'];
		
					$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
					
					$campos = null; $campo_sem_aspas_simples = null;
					
					$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id"; $campo_valor = 'forgot-password'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "pubID"; $campo_valor = $pubID; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
					
					banco_insert_name
					(
						$campos,
						"tokens"
					);
					
					$tokens_id = banco_last_id();
					
					// ===== Enviar o email com as instruções para renovar a senha.
					
					$numero = date('Ymd') . $tokens_id;
					
					$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-mail-subject')),"#numero#",$numero);
					
					gestor_incluir_biblioteca('comunicacao');
					
					if(comunicacao_email(Array(
						'destinatarios' => Array(
							Array(
								'email' => $email,
								'nome' => $nome,
							),
						),
						'mensagem' => Array(
							'assunto' => $assunto,
							'htmlLayoutID' => 'layout-email-esqueceu-senha',
							'htmlVariaveis' => Array(
								Array(
									'variavel' => '#nome#',
									'valor' => $nome,
								),
								Array(
									'variavel' => '#url#',
									'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'redefine-password/?id='.$tokenPubId.'">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'redefine-password/?id='.$tokenPubId.'</a>',
								),
								Array(
									'variavel' => '#expiracao#',
									'valor' => $_CONFIG['token-lifetime'] / 3600,
								),
								Array(
									'variavel' => '#assinatura#',
									'valor' => gestor_componente(Array(
										'id' => 'layout-emails-assinatura',
									)),
								),
							),
						),
					))){
						$user_invalid = false;
					} else {
						$email_not_sent = true;
					}
				} else {
					$user_inactive = true;
				}
			}
		}
	
		// ===== Se o usuário for inválido, redirecionar forgot-password.
		
		if($user_invalid){
			sleep(3);
			
			if($user_inactive){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-inactive'))
				));
			} else if(isset($email_not_sent)){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-email-not-sent'))
				));
			} else {
				$msg = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-email-invalid')),"#email#",$email);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $msg
				));
			}
			
			gestor_redirecionar('forgot-password/');
		} else {
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.'forgot-password-confirmation'.'-'.'email',$email);
			gestor_redirecionar('forgot-password-confirmation/');
		}
	}
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active'])){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'email-comparacao',
			'campo' => 'email',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
			'identificador' => 'email',
			'comparcao' => Array(
				'id' => 'email-2',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-2-label')),
			)
		),
		Array(
			'regra' => 'email-comparacao',
			'campo' => 'email-2',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-2-label')),
			'identificador' => 'email-2',
			'comparcao' => Array(
				'id' => 'email',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-2-label')),
			)
		),
	);
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_forgot_password_confirmation(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Caso exista a variável devolva a página, senão redirecionar porque não se deve acessar essa página diretamente.
	
	if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email'))){
		$email = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email');
		gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email');
		
		$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-confirmation-message-content'));
		
		$message = modelo_var_troca_tudo($message,"#email#",$email);
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#message#",$message);
	} else {
		gestor_redirecionar('forgot-password/');
	}
}

function perfil_usuario_redefine_password(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_REQUEST['_gestor-redefine-password'])){
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'redirect' => (isset($_REQUEST['_gestor-redefine-password-token']) ? 'redefine-password/?id='. banco_escape_field($_REQUEST['_gestor-redefine-password-token']) : NULL),
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
				),
			)
		));
		
		// ===== Campo de validação da redefinição
		
		$autorizacaoRedefinicao = false;
		$id_usuarios = '';
		
		// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
		
		$tokenPubId = banco_escape_field($_REQUEST['_gestor-redefine-password-token']);
		
		$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
			$sessaoRedefinePassword = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			
			if($sessaoRedefinePassword['pubID'] == $pubID){
				$autorizacaoRedefinicao = true;
				$id_usuarios = $sessaoRedefinePassword['id'];
				$tokens_id = $sessaoRedefinePassword['tokenID'];
			} else {
				gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			}
		}
		
		// ===== Caso autorizado atualizar senha no banco, senão alertar o usuário e redirecionar para esqueceu senha novamente.
		
		if($autorizacaoRedefinicao){
			// ===== Gerar hash da senha
			
			$senha = banco_escape_field($_REQUEST['senha']);
			
			$senhaHash = password_hash($senha, PASSWORD_ARGON2I, ["cost" => 9]);
			
			// ===== Atualizar senha no banco da conta do usuário e redirecionar para a página de confirmação
			
			banco_update
			(
				"senha='".$senhaHash."',".
				"data_modificacao=NOW(),".
				"versao=versao+1",
				"usuarios",
				"WHERE id_usuarios='".$id_usuarios."'"
			);
			
			// ===== Pegar a referência do host do usuário para incluir no histórico caso seja um usuário de um host.
			
			$usuarios_gestores_hosts = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_gestores_hosts',
				'campos' => Array(
					'id_hosts',
				),
				'extra' => 
					"WHERE id_usuarios='".$id_usuarios."'"
			));
			
			if($usuarios_gestores_hosts){
				$id_hosts = $usuarios_gestores_hosts['id_hosts'];
			}
			
			// ===== Pegar o IP do usuário.
			
			gestor_incluir_biblioteca('ip');
			
			$ip = ip_get();
			
			// ===== Criar histórico de alterações.
			
			$resetPasswordTXT = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'reset-password'));
			
			$resetPasswordTXT = modelo_var_troca($resetPasswordTXT,"#ip#",$ip);
			$resetPasswordTXT = modelo_var_troca($resetPasswordTXT,"#user-agent#",$_SERVER['HTTP_USER_AGENT']);
			
			$alteracoes[] = Array('alteracao' => 'reset-password','alteracao_txt' => $resetPasswordTXT);
			
			interface_historico_incluir(Array(
				'id_numerico_manual' => $id_usuarios,
				'id_usuarios_manual' => $id_usuarios,
				'id_hosts_manual' => (isset($id_hosts) ? $id_hosts : null ),
				'alteracoes' => $alteracoes,
			));
			
			// ===== Pegar os dados do usuário que serão usados para informar o mesmo.
			
			$usuarios = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios',
				'campos' => Array(
					'nome',
					'email',
				),
				'extra' => 
					"WHERE id_usuarios='".$id_usuarios."'"
			));
			
			$nome = $usuarios['nome'];
			$email = $usuarios['email'];
			
			// ===== Enviar o email informando da alteração da senha com sucesso.
			
			$numero = date('Ymd') . $tokens_id;
			
			$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'password-redefined-mail-subject')),"#numero#",$numero);
			
			gestor_incluir_biblioteca('comunicacao');
			
			if(comunicacao_email(Array(
				'destinatarios' => Array(
					Array(
						'email' => $email,
						'nome' => $nome,
					),
				),
				'mensagem' => Array(
					'assunto' => $assunto,
					'htmlLayoutID' => 'layout-email-senha-redefinida',
					'htmlVariaveis' => Array(
						Array(
							'variavel' => '#nome#',
							'valor' => $nome,
						),
						Array(
							'variavel' => '#assinatura#',
							'valor' => gestor_componente(Array(
								'id' => 'layout-emails-assinatura',
							)),
						),
					),
				),
			))){
				$email_not_sent = false;
			} else {
				$email_not_sent = true;
			}
			
			// ===== Remover todos os acessos logados no sistema.
			
			banco_delete
			(
				"usuarios_tokens",
				"WHERE id_usuarios='".$id_usuarios."'"
			);
			
			// ===== Redirecionar para a página de confirmação.
			
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-confirmation',true);
			gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			gestor_redirecionar('redefine-password-confirmation/');
		} else {
			sleep(3);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-redefine-password-expiration-or-invalid'))
			));
			
			gestor_redirecionar('forgot-password/');
		}
	}
	
	// ===== Campo de validação dos dados.
	
	$autorizacao = false;
	
	// ===== Verifica se foi enviado um id.
	
	if(isset($_REQUEST['id'])){
		// ===== Remover todos os tokens expirados
		
		banco_delete
		(
			"tokens",
			"WHERE expiration < ".time()
		);
		
		// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
		
		$tokenPubId = banco_escape_field($_REQUEST['id']);
		
		$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
			$sessaoRedefinePassword = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			
			if($sessaoRedefinePassword['pubID'] == $pubID){
				$autorizacao = true;
			} else {
				gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			}
		}
		
		// ===== Verificar no banco de dados se existe o token
		
		if(!$autorizacao){
			$tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_tokens',
					'id_usuarios',
				))
				,
				"tokens",
				"WHERE pubID='".$pubID."'"
			);
			
			if($tokens){
				$autorizacao = true;
				
				gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'],Array(
					'id' => $tokens[0]['id_usuarios'],
					'tokenID' => $tokens[0]['id_tokens'],
					'pubID' => $pubID,
				));
				
				banco_delete
				(
					"tokens",
					"WHERE id_tokens='".$tokens[0]['id_tokens']."'"
				);
			}
		}
	}
	
	if(!$autorizacao){
		sleep(3);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-redefine-password-expiration-or-invalid'))
		));
		
		gestor_redirecionar('forgot-password/');
	}
	
	// ===== Alterar dados da página e incluir o token
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#token#",$tokenPubId);
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Interface finalizar opções
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
			'identificador' => 'senha',
			'comparcao' => Array(
				'id' => 'senha-2',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-2-label')),
			)
		),
		Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha-2',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-2-label')),
			'identificador' => 'senha-2',
			'comparcao' => Array(
				'id' => 'senha',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-2-label')),
			)
		),
	);
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_redefine_password_confirmation(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Caso exista a variável devolva a página, senão redirecionar porque não se deve acessar essa página diretamente.
	
	if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
		gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
		
		$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-confirmation-message-content'));
		
		$message = modelo_var_troca_tudo($message,"#url#",'<a href="'.$_GESTOR['url-raiz'].'signin/">'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-login-button')).'</a>');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#message#",$message);
	} else {
		gestor_redirecionar('forgot-password/');
	}
}

function perfil_usuario_validar_usuario(){
	global $_GESTOR;
	
	if(isset($_REQUEST['_gestor-validar-usuario'])){
		//$fingerprint = banco_escape_field($_REQUEST['_gestor-validar-usuario-fingerprint']);
		
		//if(existe($fingerprint)){
			if(gestor_permissao_token()){
				$tokenPubId = $_GESTOR['usuario-token-id'];
				
				$usuarios_tokens_verificar = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios_tokens',
					))
					,
					"usuarios_tokens",
					"WHERE pubID='".$tokenPubId."'"
					//." AND fingerprint='".$fingerprint."'"
				);
				
				if($usuarios_tokens_verificar){
					//gestor_sessao_variavel('browser-fingerprint',true);
					
					$querystring = banco_escape_field($_REQUEST['_gestor-validar-usuario-querystring']);
					
					if(existe(gestor_sessao_variavel("redirecionar-local"))){
						gestor_redirecionar(false,$querystring);
					} else {
						gestor_redirecionar('dashboard/');
					}
				}
			}
		//}
		
		perfil_usuario_signout();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	//gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'fingerprint3-3.1.0/fp.min.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Alterar dados do formulário de validação
	
	$queryString = gestor_querystring();
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-action#",$_GESTOR['url-raiz'].'validate-user/');
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-querystring#",$queryString);
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
}

function perfil_usuario_confirmacao_email(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Campo de validação dos dados
	
	$autorizacao = false;
	
	// ===== Verifica se foi enviado um id
	
	if(isset($_REQUEST['id'])){
		// ===== Remover todos os tokens expirados
		
		banco_delete
		(
			"tokens",
			"WHERE expiration < ".time()
		);
		
		// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
		
		$tokenPubId = banco_escape_field($_REQUEST['id']);
		
		$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
			$sessaoControle = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			
			if($sessaoControle['pubID'] == $pubID){
				$autorizacao = true;
			} else {
				gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			}
		}
		
		// ===== Verificar no banco de dados se existe o token
		
		if(!$autorizacao){
			$tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_tokens',
					'id_usuarios',
				))
				,
				"tokens",
				"WHERE pubID='".$pubID."'"
			);
			
			if($tokens){
				$autorizacao = true;
				
				gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'],Array(
					'id' => $tokens[0]['id_usuarios'],
					'pubID' => $pubID,
				));
				
				$sessaoControle = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
				
				banco_delete
				(
					"tokens",
					"WHERE id_tokens='".$tokens[0]['id_tokens']."'"
				);
			}
		}
	}
	
	if(!$autorizacao){
		sleep(3);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-redefine-password-expiration-or-invalid'))
		));
		
		gestor_redirecionar('dashboard/');
	}
	
	// ===== Inclusão Módulo JS
	
	if($autorizacao){
		if(isset($sessaoControle)){
			banco_update
			(
				"email_confirmado=1",
				"usuarios",
				"WHERE id_usuarios='".$sessaoControle['id']."'"
			);
		}
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Interface finalizar opções
}

// ==== Ajax

function perfil_usuario_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function perfil_usuario_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': perfil_usuario_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		perfil_usuario_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'editar': perfil_usuario_editar(); break;
			case 'signin': perfil_usuario_signin(); break;
			case 'signup': perfil_usuario_signup(); break;
			case 'forgot-password': perfil_usuario_forgot_password(); break;
			case 'forgot-password-confirmation': perfil_usuario_forgot_password_confirmation(); break;
			case 'redefine-password': perfil_usuario_redefine_password(); break;
			case 'redefine-password-confirmation': perfil_usuario_redefine_password_confirmation(); break;
			case 'signout': perfil_usuario_signout(); break;
			case 'area-restrita': perfil_usuario_area_restrita(); break;
			case 'validar-usuario': perfil_usuario_validar_usuario(); break;
			case 'confirmacao-email': perfil_usuario_confirmacao_email(); break;
		}
		
		interface_finalizar();
	}
}

perfil_usuario_start();

?>