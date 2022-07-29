<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'meus-dados';
$_GESTOR['modulo-alvo-id']							=	'usuarios';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.5',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function meus_dados_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'formato',
		'interface',
		'formulario',
		'usuario',
	));
	
	// ===== Mudar Campos parâmetros iniciais
	
	$mudarCampos = Array('nome','email','usuario','senha','telefone','documento');
	$mudarCampo = false;
	$mudarCampoBanco = false;
	
	// ===== Atualização dos dados.
	
	if(isset($_REQUEST['atualizar-campos'])){
		// ===== Verificar qual campo está sendo mudado
		
		foreach($mudarCampos as $mc){
			if(isset($_REQUEST['mudar-'.$mc.'-banco'])){
				$mudarCampoBanco = $mc;
			}
		}
		
		// ===== Senão encontrou opção, dar reload na página.
		
		if(!$mudarCampoBanco){
			gestor_reload_url();
		}
		
		// ===== Validação de campos obrigatórios
		
		if($mudarCampoBanco){
			if(!usuario_autorizacao_provisoria(Array(
				'verificar' => true,
			))){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-authorization-provisory-not-defined'));
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
				
				gestor_reload_url();
			}
		}
		
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		$usuariosAntes = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios',
			'campos' => '*',
			'extra' => 
				"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
		));
		
		// ===== Validação de campos obrigatórios
		
		switch($mudarCampoBanco){
			case 'nome':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-nome-label')),
				);
			break;
			case 'email':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
				);
			break;
			case 'usuario':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-usuario-label')),
				);
			break;
			case 'senha':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
					'min' => 12,
				);
			break;
			case 'telefone':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'telefone',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-telefone-label')),
				);
			break;
			case 'documento':
				if($_REQUEST['cnpj_ativo'] == 'nao'){
					$campos_obrigatorios['campos'][] = Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'cpf',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-cpf-label')),
					);
				} else {
					$campos_obrigatorios['campos'][] = Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'cnpj',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-cnpj-label')),
					);
				}
			break;
		}
		
		formulario_validacao_campos_obrigatorios($campos_obrigatorios);
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		switch($mudarCampoBanco){
			case 'usuario':
				$exiteUsuario = banco_select(Array(
					'unico' => true,
					'tabela' => 'usuarios',
					'campos' => Array(
						'id_usuarios',
					),
					'extra' => 
						"WHERE id_hosts_usuarios!='".$_GESTOR['usuario-id']."'"
						." AND usuario='".banco_escape_field($_REQUEST['usuario'])."'"
						." AND status!='D'"
				));
				
				if($exiteUsuario){
					$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alert-there-is-a-field'));
					$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-usuario-label')));
					$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['usuario']));
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					gestor_reload_url();
				}
			break;
			case 'email':
				$exiteEmail = banco_select(Array(
					'unico' => true,
					'tabela' => 'usuarios',
					'campos' => Array(
						'id_usuarios',
					),
					'extra' => 
						"WHERE id_hosts_usuarios!='".$_GESTOR['usuario-id']."'"
						." AND email='".banco_escape_field($_REQUEST['email'])."'"
						." AND status!='D'"
				));
				
				if($exiteEmail){
					$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alert-there-is-a-field'));
					$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')));
					$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['email']));
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					gestor_reload_url();
				}
			break;
		}
		
		// ===== API-Servidor alteração dos dados.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_usuario(Array(
			'opcao' => 'editar',
			'usuarioID' => $_GESTOR['usuario-id'],
			'campo' => $mudarCampoBanco,
			'usuario' => (isset($_REQUEST['usuario']) ? banco_escape_field($_REQUEST['usuario']) : null),
			'email' => (isset($_REQUEST['email']) ? banco_escape_field($_REQUEST['email']) : null),
			'nome' => (isset($_REQUEST['nome']) ? banco_escape_field($_REQUEST['nome']) : null),
			'senha' => (isset($_REQUEST['senha']) ? banco_escape_field($_REQUEST['senha']) : null),
			'telefone' => (isset($_REQUEST['telefone']) ? banco_escape_field($_REQUEST['telefone']) : null),
			'cnpj_ativo' => (isset($_REQUEST['cnpj_ativo']) ? ($_REQUEST['cnpj_ativo'] == 'sim' ? 'sim' : 'nao') : null),
			'cpf' => (isset($_REQUEST['cpf']) ? banco_escape_field($_REQUEST['cpf']) : null),
			'cnpj' => (isset($_REQUEST['cnpj']) ? banco_escape_field($_REQUEST['cnpj']) : null),
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				//case 'OPCAO': $alerta = $retorno['error-msg']; break;
				default:
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			}
			
			if($ajax){
				return Array(
					'status' => 'API_ERROR',
					'msg' => $alerta,
				);
			} else {
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			}
			
			// ===== Reler a página.
			
			gestor_reload_url();
		} else {
			// ===== Dados de retorno.
			
			$dados = Array();
			if(isset($retorno['data'])){
				$dados = $retorno['data'];
			}
			
			// ===== Atualizar o usuário localmente.
			
			if($retorno['data']['usuario']){
				$usuario = $retorno['data']['usuario'];
				
				foreach($usuario as $campo => $valor){
					switch($campo){
						case 'cnpj_ativo':
							banco_update_campo($campo,(existe($valor) ? $valor : 'NULL'),true);
						break;
						default:
							banco_update_campo($campo,$valor);
					}
				}
				
				banco_update_executar('usuarios',"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'");
			}
			
			// ===== Incluir o histórico da alteração no usuarios.
			
			if($retorno['data']['alteracaoTxt']){
				gestor_incluir_biblioteca('log');
				
				log_usuarios(Array(
					'id_hosts_usuarios' => $_GESTOR['usuario-id'],
					'id' => $_GESTOR['usuario-id'],
					'tabela' => Array(
						'nome' => 'usuarios',
						'versao' => 'versao',
						'id_numerico' => 'id_hosts_usuarios',
					),
					'alteracoes' => Array(
						Array(
							'modulo' => 'usuarios',
							'alteracao' => 'update-data',
							'alteracao_txt' => $retorno['data']['alteracaoTxt'],
						)
					),
				));
			}
			
			// ===== Verificar se precisa deleter tokens e criar um novo.
			
			switch($mudarCampoBanco){
				case 'usuario':
				case 'senha':
					// ===== Pegar dados do usuário token atual.
					
					$tokenPubId = $_GESTOR['usuario-token-id'];
					
					$usuarios_tokens = banco_select(Array(
						'unico' => true,
						'tabela' => 'usuarios_tokens',
						'campos' => Array(
							'expiration',
							'id_hosts_usuarios_tokens',
						),
						'extra' => 
							"WHERE pubID='".$tokenPubId."'"
					));
					
					$expiration = $usuarios_tokens['expiration'];
					$id_hosts_usuarios_tokens = $usuarios_tokens['id_hosts_usuarios_tokens'];
					
					// ===== Remover todos os usuários tokens do usuário atual.
					
					$id_hosts_usuarios = $_GESTOR['usuario-id'];
					
					banco_delete
					(
						"usuarios_tokens",
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					);
					
					// ===== Renovar o usuário token deste usuário.
					
					gestor_incluir_biblioteca('usuario');
					
					if((int)$expiration > 0){
						$usuario_token = usuario_token_dados();
					} else {
						$usuario_token = usuario_token_dados(Array(
							'sessao' => true,
						));
					}
					
					usuario_gerar_token_autorizacao(Array(
						'id_hosts_usuarios' => $id_hosts_usuarios,
						'id_hosts_usuarios_tokens' => $id_hosts_usuarios_tokens,
						'usuario_token' => $usuario_token,
					));
				break;
			}
			
			// ===== Invalidar a autorização provisória
			
			//usuario_autorizacao_provisoria(Array('invalidar' => true));
			
			// ===== Alertar sucesso.
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $retorno['data']['alerta']
			));
			
			// ===== Reler URL.
			
			gestor_reload_url();
		}
	}
	
	// ===== Variáveis iniciais.
	
	$JSmeusDados = Array();
	
	// ===== Dados do Usuário.
	
	$usuarios = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => '*',
		'extra' => 
			"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
	));
	
	// ===== Usuário Perfil Host.
	
	$usuarios_perfis = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios_perfis',
		'campos' => Array(
			'nome',
		),
		'extra' => 
			"WHERE id_hosts_usuarios_perfis='".$usuarios['id_hosts_usuarios_perfis']."'"
	));
	
	// ===== Incluir dados na página.
	
	pagina_trocar_variavel_valor('conta-nome',$usuarios['nome_conta']);
	pagina_trocar_variavel_valor('perfil-usuario',$usuarios_perfis['nome']);
	pagina_trocar_variavel_valor('email',$usuarios['email']);
	pagina_trocar_variavel_valor('usuario',$usuarios['usuario']);
	pagina_trocar_variavel_valor('nome',$usuarios['nome']);
	pagina_trocar_variavel_valor('telefone',$usuarios['telefone']);
	pagina_trocar_variavel_valor('documento-tipo',($usuarios['cnpj_ativo'] ? 'CNPJ' : 'CPF'));
	pagina_trocar_variavel_valor('documento',($usuarios['cnpj_ativo'] ? $usuarios['cnpj'] : $usuarios['cpf']));
	
	// ===== Mudar ou não um campo
	
	foreach($mudarCampos as $mc){
		if(isset($_REQUEST['mudar-'.$mc])){
			$mudarCampo = $mc;
		} else {
			$cel_nome = $mc.'-campos'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		}
	}
	
	// ===== Dados do formulário.
	
	switch($mudarCampo){
		case 'nome':
			pagina_trocar_variavel_valor('form-nome',$usuarios['nome']);
		break;
		case 'usuario':
			pagina_trocar_variavel_valor('form-usuario',$usuarios['usuario']);
		break;
		case 'email':
			pagina_trocar_variavel_valor('form-email',$usuarios['email']);
			pagina_trocar_variavel_valor('form-email-2',$usuarios['email']);
		break;
		case 'telefone':
			pagina_trocar_variavel_valor('form-telefone',$usuarios['telefone']);
		break;
		case 'documento':
			pagina_trocar_variavel_valor('form-cnpj',$usuarios['cnpj']);
			pagina_trocar_variavel_valor('form-cpf',$usuarios['cpf']);
			
			if($usuarios['cnpj_ativo']){
				pagina_trocar_variavel_valor('cnpj_ativo','sim');
				
				pagina_trocar_variavel_valor('cpfControle','',true);
				pagina_trocar_variavel_valor('cnpjControle','active',true);
				
				pagina_trocar_variavel_valor('cpfCampo','escondido',true);
				pagina_trocar_variavel_valor('cnpjCampo','',true);
				
				$JSmeusDados['cnpj_ativo'] = 'sim';
			} else {
				pagina_trocar_variavel_valor('cnpj_ativo','nao');
				
				pagina_trocar_variavel_valor('cpfControle','active',true);
				pagina_trocar_variavel_valor('cnpjControle','',true);
				
				pagina_trocar_variavel_valor('cpfCampo','',true);
				pagina_trocar_variavel_valor('cnpjCampo','escondido',true);
				
				$JSmeusDados['cnpj_ativo'] = 'nao';
			}
		break;
	}
	
	// ===== Formulário validação conforme necessidade.
	
	if($mudarCampo){
		usuario_autorizacao_provisoria(Array(
			'verificarModal' => Array(
				'cancelarUrl' => 'meus-dados/',
				'autorizadoUrl' => 'meus-dados/',
				'autorizadoUrlQuerystring' => 'mudar-'.$mudarCampo.'=sim#formulario-alteracoes',
			),
		));
		
		switch($mudarCampo){
			case 'nome':
				$validacao = Array(
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'nome',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-nome-label')),
					),
				);
			break;
			case 'email':
				$validacao = Array(
					Array(
						'regra' => 'email-comparacao-verificar-campo',
						'campo' => 'email',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
						'identificador' => 'email',
						'comparcao' => Array(
							'id' => 'email-2',
							'campo-1' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
							'campo-2' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-2-label')),
						)
					),
					Array(
						'regra' => 'email-comparacao',
						'campo' => 'email-2',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-2-label')),
						'identificador' => 'email-2',
						'comparcao' => Array(
							'id' => 'email',
							'campo-1' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
							'campo-2' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-2-label')),
						)
					)
				);
			break;
			case 'usuario':
				$validacao = Array(
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'usuario',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-usuario-label')),
					),
				);
			break;
			case 'senha':
				$validacao = Array(
					Array(
						'regra' => 'senha-comparacao',
						'campo' => 'senha',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
						'identificador' => 'senha',
						'comparcao' => Array(
							'id' => 'senha-2',
							'campo-1' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
							'campo-2' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-2-label')),
						)
					),
					Array(
						'regra' => 'senha-comparacao',
						'campo' => 'senha-2',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-2-label')),
						'identificador' => 'senha-2',
						'comparcao' => Array(
							'id' => 'senha',
							'campo-1' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
							'campo-2' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-2-label')),
						)
					),
				);
			break;
			case 'telefone':
				$validacao = Array(
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'telefone',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-telefone-label')),
					),
				);
			break;
			case 'documento':
				$validacao = Array(
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'cpf',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-cpf-label')),
					),
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'cnpj',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-cnpj-label')),
					),
				);
			break;
		}
		
		formulario_validacao(Array(
			'formId' => 'formMeusDadosAlterar',
			'validacao' => $validacao,
		));
	} else {
		$cel_nome = 'formulario'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Mostrar histórico na página caso houver.
	
	$_GESTOR['pagina'] = interface_historico(Array(
		'id' => $_GESTOR['usuario-id'],
		'modulo' => $_GESTOR['modulo-alvo-id'],
		'pagina' => $_GESTOR['pagina'],
	));
	
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
	
	gestor_pagina_javascript_incluir('modulos');
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['meusDados'] = $JSmeusDados;
}

// ==== Ajax

function meus_dados_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'historico-mais-resultados': 
				if(!isset($_REQUEST['id'])) $_REQUEST['id'] = '';
				
				if($_GESTOR['usuario-id'] != $_REQUEST['id']){
					$_GESTOR['ajax-json'] = Array(
						'status' => 'USER_INVALID',
					);
					return;
				}
				
				gestor_incluir_biblioteca(Array(
					'interface',
				));
				
				interface_ajax_finalizar();
			break;
		}
	}
}

// ==== Start

function meus_dados_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': meus_dados_ajax_opcao(); break;
			default: meus_dados_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': meus_dados_opcao(); break;
			default: meus_dados_padrao();
		}
	}
}

meus_dados_start();

?>