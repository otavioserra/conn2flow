<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'identificacao-esqueceu-senha';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function identificacao_esqueceu_senha_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'interface',
		'formulario',
	));
	
	if(isset($_REQUEST['esqueceu-senha'])){
		// ===== Validação de campos obrigatórios
		
		formulario_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
				),
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email-2',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-2-label')),
				),
			)
		));
		
		// ===== Gerar o token.
		
		$tokenPubId = md5(uniqid(rand(), true));
		
		// ===== API-Servidor para esqueceu senha.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_identificacao(Array(
			'opcao' => 'esqueceuSenha',
			'email' => banco_escape_field($_REQUEST['email']),
			'tokenPubId' => $tokenPubId,
			'token' => (isset($_REQUEST['token']) ? banco_escape_field($_REQUEST['token']) : null),
			'gRecaptchaResponse' => (isset($_REQUEST['g-recaptcha-response']) ? banco_escape_field($_REQUEST['g-recaptcha-response']) : null),
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				case 'EMAIL_NOT_SENT': 
				case 'USER_INACTIVE': 
				case 'THERE_IS_NO_USER': 
					$alerta = $retorno['error-msg'];
				break;
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
			
			// ===== Criar o token no banco de dados.
			
			$expiration = time() + $_GESTOR['token-lifetime'];

			$pubID = hash_hmac($_GESTOR['seguranca']['hash-algo'], $tokenPubId, $_GESTOR['seguranca']['hash-senha']);
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_hosts_usuarios"; $campo_valor = $dados['id_hosts_usuarios']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = 'forgot-password';				 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "pubID"; $campo_valor = $pubID; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "expiration"; $campo_valor = $expiration; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 								$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"tokens"
			);
			
			// ===== Colocar mensagem na página.
			
			pagina_trocar_variavel_valor('message',$dados['message']);
			
			// ===== Email enviado com sucesso.
			
			$emailEnviado = true;
		}
	}
	
	// ===== Variáveis iniciais.
	
	$JSidentificacao = Array();
	
	if(!isset($emailEnviado)){
		// ===== Incluir google reCAPTCHA caso ativo
		
		if(isset($_GESTOR['plataforma-cliente']['plataforma-recaptcha-active'])){
			if($_GESTOR['plataforma-cliente']['plataforma-recaptcha-active']){
				// ===== Verificar se o host tem reCAPTCHA próprio.
				
				$chaveSite = formulario_google_recaptcha();
				$recaptchaTipo = formulario_google_recaptcha_tipo();
				
				// ===== configurar o Google reCAPTCHA.
				
				$googleRecaptchaSite = (isset($chaveSite) ? $chaveSite : $_GESTOR['plataforma-cliente']['plataforma-recaptcha-site']);
				
				if(isset($recaptchaTipo)){
					switch($recaptchaTipo){
						case 'recaptcha-v2':
							$_GESTOR['javascript-vars']['googleRecaptchaActiveV2'] = true;
							$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $googleRecaptchaSite;
							gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render=explicit"></script>');
						break;
						case 'recaptcha-v3':
							$recaptchaV3 = true;
						break;
					}
				} else {
					$recaptchaV3 = true;
				}
				
				if(isset($recaptchaV3)){
					$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
					$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $googleRecaptchaSite;
					gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$googleRecaptchaSite.'"></script>');
				}
			}
		}
		
		// ===== Formulário validação.
		
		formulario_validacao(Array(
			'formId' => 'formEsqueceuSenha',
			'validacao' => Array(
				Array(
					'regra' => 'email-comparacao',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'forgot-password-email-label')),
					'identificador' => 'email',
					'comparcao' => Array(
						'id' => 'email-2',
						'campo-1' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'forgot-password-email-label')),
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
		
		// ===== Mudar o nome da janela para depois o target ser a mesma janela.
		
		$JSidentificacao['janelaNome'] = 'identificacao';
	} else {
		// ===== Remover célula de formulário.
		
		$cel_nome = 'formulario'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Células.
	
	$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	
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

function identificacao_esqueceu_senha_ajax_padrao(){
	global $_GESTOR;
	
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function identificacao_esqueceu_senha_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': identificacao_esqueceu_senha_ajax_opcao(); break;
			default: identificacao_esqueceu_senha_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': identificacao_esqueceu_senha_opcao(); break;
			default: identificacao_esqueceu_senha_padrao();
		}
	}
}

identificacao_esqueceu_senha_start();

?>