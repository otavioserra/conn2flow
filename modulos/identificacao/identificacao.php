<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'identificacao';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.16',
);

// ===== Funções Auxiliares

function identificacao_area_restrita(){
	
}

function identificacao_signout(){
	global $_GESTOR;
	
	if(isset($_GESTOR['usuario-token-id'])){
		// ===== Dados do usuário token.
		
		gestor_incluir_biblioteca('usuario');
		
		$usuario_token = usuario_token_dados(Array(
			'sessao' => isset($_REQUEST['permanecer-logado']) ? null : true
		));
		
		// ===== Pegar o id_hosts_usuarios_tokens atual.
		
		$usuarios_tokens = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_tokens',
			'campos' => Array(
				'id_hosts_usuarios_tokens',
			),
			'extra' => 
				"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
				." AND id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
		));
		
		// ===== API-Servidor para logar.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_identificacao(Array(
			'opcao' => 'sair',
			'usuarioID' => $_GESTOR['usuario-id'],
			'usuarioTokenID' => $usuarios_tokens['id_hosts_usuarios_tokens'],
		));
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
			
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
			
			// ===== Deletar usuário token local.
			
			gestor_sessao_del();
			
			banco_delete
			(
				"usuarios_tokens",
				"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
			);
			
			setcookie($_GESTOR['cookie-authname'], "", [
				'expires' => time() - 3600,
				'path' => '/',
				'domain' => $_SERVER['SERVER_NAME'],
				'secure' => true,
				'httponly' => true,
				'samesite' => 'Lax',
			]);
			
			unset($_COOKIE[$_GESTOR['cookie-authname']]);
	
			gestor_redirecionar('identificacao/');
		}
		
	}
}

// ===== Funções Principais

function identificacao_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'interface',
		'formulario',
	));
	
	// ===== Tentativa de sair do gestor.
	
	if(isset($_REQUEST['sair'])){
		if(gestor_permissao_token()){
			identificacao_signout();
		} else {
			gestor_reload_url();
		}
	}
	
	// ===== Área restrita ativação.
	
	if(isset($_REQUEST['area-restrita'])){
		if(gestor_permissao_token()){
			identificacao_area_restrita();
		} else {
			gestor_reload_url();
		}
	}
	
	// ===== Tentativa de logar no gestor.
	
	if(isset($_REQUEST['logar'])){
		// ===== Validação de campos obrigatórios
		
		formulario_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
				),
			)
		));
		
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
		
		// ===== Dados do usuário token.
		
		gestor_incluir_biblioteca('usuario');
		
		$usuario_token = usuario_token_dados(Array(
			'sessao' => isset($_REQUEST['permanecer-logado']) ? null : true
		));
		
		// ===== API-Servidor para logar.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_identificacao(Array(
			'opcao' => 'logar',
			'usuario_token' => $usuario_token,
			'email' => banco_escape_field($_REQUEST['email']),
			'senha' => banco_escape_field($_REQUEST['senha']),
			'token' => (isset($_REQUEST['token']) ? banco_escape_field($_REQUEST['token']) : null),
			'usuarioTokenID' => (isset($_GESTOR['usuario-token-id']) ? $_GESTOR['usuario-token-id'] : null),
		));
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
			
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
			
			// ===== Caso o usuário escolher a opção para manter logado, gera token de autenticação com tempo de expiração, senão será expirado assim que o usuário fechar navegador
			
			usuario_gerar_token_autorizacao(Array(
				'id_hosts_usuarios' => $dados['id_hosts_usuarios'],
				'id_hosts_usuarios_tokens' => $dados['id_hosts_usuarios_tokens'],
				'usuario_token' => $usuario_token,
				'sessao' => isset($_REQUEST['permanecer-logado']) ? null : true,
			));
			
			// ===== Se o usuário for válido, redirecionar para o local pretendido se houver, senão para minha-conta.
			
			if(existe(gestor_sessao_variavel("redirecionar-local"))){
				gestor_redirecionar();
			} else if(existe(gestor_sessao_variavel('carrinho-prosseguir'))){
				gestor_sessao_variavel_del('carrinho-prosseguir');
				gestor_redirecionar('emissao/?criar-pedido=sim');
			} else {
				gestor_redirecionar('minha-conta/');
			}
		}
	}
	
	if(isset($_REQUEST['criar-conta'])){
		// ===== Validação de campos obrigatórios
		
		formulario_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
				),
			)
		));
		
		// ===== API-Servidor para criar conta.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_identificacao(Array(
			'opcao' => 'criarConta',
			'email' => banco_escape_field($_REQUEST['email']),
			'token' => (isset($_REQUEST['token']) ? banco_escape_field($_REQUEST['token']) : null),
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				case 'THERE_IS_USER': $alerta = $retorno['error-msg']; break;
				default:
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
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
			
			// ===== Incluir numa sessão o email validado.
			
			gestor_sessao_variavel('identificacao-cadastro',Array(
				'email' => $dados['email'],
				'valido' => true,
			));
			
			// ===== Redirecionar para o cadastro.
			
			gestor_redirecionar('identificacao-cadastro/');
		}
	}
	
	if(isset($_REQUEST['identidadeConfirmar'])){
		// ===== Redirecionar para o local pretendido se houver, senão para minha-conta.
		
		if(existe(gestor_sessao_variavel("redirecionar-local"))){
			gestor_redirecionar();
		} else if(existe(gestor_sessao_variavel('carrinho-prosseguir'))){
			gestor_sessao_variavel_del('carrinho-prosseguir');
			gestor_redirecionar('emissao/?criar-pedido=sim');
		} else {
			gestor_redirecionar('minha-conta/');
		}
	}
	
	// ===== Variáveis iniciais.
	
	$JSidentificacao = Array();
	
	// ===== Se já estiver logado, oferecer opção de confirmar o usuário atual. Senão mostrar opções de identificação.
	
	if(!isset($_REQUEST['identidadeOutraConta'])){
		if(gestor_permissao_token()){
			// ===== Pegar o nome do usuário.
			
			gestor_incluir_biblioteca('usuario');
			
			$nome = usuario_dados(Array('campo' => 'nome'));
			$email = usuario_ofuscar_email(usuario_dados(Array('campo' => 'email')));
			
			pagina_trocar_variavel_valor('nome',$nome);
			pagina_trocar_variavel_valor('email',$email);
			
			// ===== Remover célula de identificação.
			
			$cel_nome = 'identificacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			
			// ===== URL Identidade Confirmar e Outra Conta.
			
			$JSidentificacao['identidadeConfirmar'] = '/identificacao/?identidadeConfirmar=sim';
			$JSidentificacao['identidadeOutraConta'] = '/identificacao/?identidadeOutraConta=sim';
			
			// ===== Desativar formulários de identificação.
			
			$desativarFormularios = true;
		}
	}
	
	if(!isset($desativarFormularios)){
		// ===== Incluir google reCAPTCHA caso ativo
		
		if(isset($_GESTOR['plataforma-cliente']['plataforma-recaptcha-active'])){
			if($_GESTOR['plataforma-cliente']['plataforma-recaptcha-active']){
				// ===== Verificar se o host tem reCAPTCHA próprio.
				
				$chaveSite = formulario_google_recaptcha();
				
				// ===== configurar o Google reCAPTCHA.
				
				$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
				$_GESTOR['javascript-vars']['googleRecaptchaSite'] = (isset($chaveSite) ? $chaveSite : $_GESTOR['plataforma-cliente']['plataforma-recaptcha-site']);
				
				gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_GESTOR['plataforma-cliente']['plataforma-recaptcha-site'].'"></script>');
			}
		}
		
		// ===== Formulário validação.
		
		formulario_validacao(Array(
			'formId' => 'formLogin',
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
				),
				Array(
					'regra' => 'senha',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
				),
			)
		));
		
		formulario_validacao(Array(
			'formId' => 'formCriarConta',
			'validacao' => Array(
				Array(
					'regra' => 'email',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
				),
			)
		));
		
		// ===== Incluir componentes.
		
		interface_componentes_incluir(Array(
			'componente' => Array(
				'modal-carregamento',
				'modal-alerta',
			)
		));
		
		// ===== Remover célula de confirmação.
		
		$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Células.
	
	$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	
	// ===== Marcar 'carrinho-prosseguir' para depois redirecionar continuidade do checkout.
	
	if(isset($_REQUEST['carrinho'])){
		gestor_sessao_variavel('carrinho-prosseguir','true');
	}
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	if(existe(gestor_sessao_variavel('carrinho-prosseguir'))){
		layout_trocar_variavel_valor('layout#step',$cel['step']);
		layout_trocar_variavel_valor('layout#step-mobile',$cel['step-mobile']);
	} else {
		layout_trocar_variavel_valor('layout#step','');
		layout_trocar_variavel_valor('layout#step-mobile','');
	}
	
	// ===== Finalizar o layout com as variáveis padrões.
	
	layout_loja();
	
	// ===== Finalizar interface.
	
	interface_finalizar();
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('modulos');
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['identificacao'] = $JSidentificacao;
}

// ==== Ajax

function identificacao_ajax_padrao(){
	global $_GESTOR;
	
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function identificacao_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': identificacao_ajax_opcao(); break;
			default: identificacao_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': identificacao_opcao(); break;
			default: identificacao_padrao();
		}
	}
}

identificacao_start();

?>