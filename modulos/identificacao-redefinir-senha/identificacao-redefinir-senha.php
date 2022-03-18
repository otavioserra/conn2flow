<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'identificacao-redefinir-senha';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.1',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function identificacao_redefinir_senha_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'interface',
		'formulario',
	));
	
	// ===== Campo de validação dos dados.
	
	$autorizacao = false;
	
	// ===== Envio do formuário de redefinição da senha.
	
	
	if(isset($_REQUEST['redefinir-senha'])){
		// ===== Validação de campos obrigatórios
		
		formulario_validacao_campos_obrigatorios(Array(
			'redirect' => (isset($_REQUEST['redefinir-senha-token']) ? 'identificacao-redefinir-senha/?id='. banco_escape_field($_REQUEST['redefinir-senha-token']) : NULL),
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'redefine-password-pass-label')),
				),
			)
		));
		
		// ===== Campo de validação da redefinição
		
		$autorizacaoRedefinicao = false;
		$id_hosts_usuarios = '';
		
		// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
		
		$tokenPubId = banco_escape_field($_REQUEST['redefinir-senha-token']);
		
		$pubID = hash_hmac($_GESTOR['seguranca']['hash-algo'], $tokenPubId, $_GESTOR['seguranca']['hash-senha']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel('redefinir-senha'))){
			$sessaoRedefinePassword = gestor_sessao_variavel('redefinir-senha');
			
			if($sessaoRedefinePassword['pubID'] == $pubID){
				$autorizacaoRedefinicao = true;
				$tokens_id = $sessaoRedefinePassword['tokenID'];
				$id_hosts_usuarios = $sessaoRedefinePassword['id'];
			} else {
				gestor_sessao_variavel_del('redefinir-senha');
			}
		}
		
		// ===== Caso autorizado atualizar senha no banco, senão alertar o usuário e redirecionar para esqueceu senha novamente.
		
		if($autorizacaoRedefinicao){
			// ===== API-Servidor para esqueceu senha.
			
			gestor_incluir_biblioteca('api-servidor');
			
			$retorno = api_servidor_identificacao(Array(
				'opcao' => 'redefinirSenha',
				'senha' => banco_escape_field($_REQUEST['senha']),
				'tokenPubId' => $tokenPubId,
				'tokenID' => $tokens_id,
				'userIP' => $_SERVER['REMOTE_ADDR'],
				'userUserAgent' => $_SERVER['HTTP_USER_AGENT'],
			));
			
			if(!$retorno['completed']){
				switch($retorno['status']){
					case 'TOKEN_EXPIRATION_OR_INVALID':
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
				
				gestor_redirecionar((isset($_REQUEST['redefinir-senha-token']) ? 'identificacao-redefinir-senha/?id='. $_REQUEST['redefinir-senha-token'] : 'identificacao-esqueceu-senha/'));
			} else {
				// ===== Dados de retorno.
				
				$dados = Array();
				if(isset($retorno['data'])){
					$dados = $retorno['data'];
				}
				
				// ===== Incluir o histórico da alteração no usuarios.
				
				if($dados['alteracaoTxt']){
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
								'alteracao' => 'reset-password',
								'alteracao_txt' => $dados['alteracaoTxt'],
							)
						),
					));
				}
				
				// ===== Remover todos os acessos logados no sistema.
				
				banco_delete
				(
					"usuarios_tokens",
					"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
				);
				
				// ===== Colocar mensagem na página.
				
				pagina_trocar_variavel_valor('message',$dados['message']);
				
				// ===== Remover a sessão.
				
				gestor_sessao_variavel_del('redefinir-senha');
				
				// ===== Troca de senha realizada com sucesso.
				
				$confirmadoTrocaSenha = true;
				$autorizacao = true;
			}
		} else {
			sleep(3);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-redefine-password-expiration-or-invalid'))
			));
			
			gestor_redirecionar('identificacao-esqueceu-senha/');
		}
	}
	
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
		
		$pubID = hash_hmac($_GESTOR['seguranca']['hash-algo'], $tokenPubId, $_GESTOR['seguranca']['hash-senha']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel('redefinir-senha'))){
			$sessaoRedefinePassword = gestor_sessao_variavel('redefinir-senha');
			
			if($sessaoRedefinePassword['pubID'] == $pubID){
				$autorizacao = true;
			} else {
				gestor_sessao_variavel_del('redefinir-senha');
			}
		}
		
		// ===== Verificar no banco de dados se existe o token.
		
		if(!$autorizacao){
			$tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_tokens',
					'id_hosts_usuarios',
				))
				,
				"tokens",
				"WHERE pubID='".$pubID."'"
			);
			
			if($tokens){
				$autorizacao = true;
				
				gestor_sessao_variavel('redefinir-senha',Array(
					'id' => $tokens[0]['id_hosts_usuarios'],
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
	
	// ===== Senão autorizado, redirecionar identificação.
	
	if(!$autorizacao){
		sleep(3);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-redefine-password-expiration-or-invalid'))
		));
		
		gestor_redirecionar('identificacao-esqueceu-senha/');
	}
	
	// ===== Variáveis iniciais.
	
	$JSidentificacao = Array();
	
	if(!isset($confirmadoTrocaSenha)){
		// ===== Pegar o nome do usuário.
		
		gestor_incluir_biblioteca('usuario');
		
		$sessaoRedefinePassword = gestor_sessao_variavel('redefinir-senha');
		
		$nome = usuario_dados(Array('campo' => 'nome','id_hosts_usuarios' => $sessaoRedefinePassword['id']));
		$email = usuario_ofuscar_email(usuario_dados(Array('campo' => 'email','id_hosts_usuarios' => $sessaoRedefinePassword['id'])));
		
		pagina_trocar_variavel_valor('nome',$nome);
		pagina_trocar_variavel_valor('email',$email);
		
		// ===== Incluir o token no formulário.
		
		pagina_trocar_variavel_valor('token',$tokenPubId);
		
		// ===== Formulário validação.
		
		formulario_validacao(Array(
			'formId' => 'formRedefinirSenha',
			'validacao' => Array(
				Array(
					'regra' => 'senha-comparacao',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'redefine-password-pass-label')),
					'identificador' => 'senha',
					'comparcao' => Array(
						'id' => 'senha-2',
						'campo-1' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'redefine-password-pass-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'redefine-password-pass-2-label')),
					)
				),
				Array(
					'regra' => 'senha-comparacao',
					'campo' => 'senha-2',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'redefine-password-pass-2-label')),
					'identificador' => 'senha-2',
					'comparcao' => Array(
						'id' => 'senha',
						'campo-1' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'redefine-password-pass-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'redefine-password-pass-2-label')),
					)
				),
			)
		));
		
		// ===== Remover célula de confirmação.
		
		$cel_nome = 'confirmacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Incluir componentes.
		
		interface_componentes_incluir(Array(
			'componente' => Array(
				'modal-carregamento',
				'modal-alerta',
			)
		));
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

function identificacao_redefinir_senha_ajax_padrao(){
	global $_GESTOR;
	
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function identificacao_redefinir_senha_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': identificacao_redefinir_senha_ajax_opcao(); break;
			default: identificacao_redefinir_senha_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': identificacao_redefinir_senha_opcao(); break;
			default: identificacao_redefinir_senha_padrao();
		}
	}
}

identificacao_redefinir_senha_start();

?>