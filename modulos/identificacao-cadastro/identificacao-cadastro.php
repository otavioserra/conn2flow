<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'identificacao-cadastro';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.14',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function identificacao_cadastro_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'interface',
		'formulario',
	));
	
	if(isset($_REQUEST['cadastrar'])){
		// ===== Validação de campos obrigatórios
		
		formulario_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-nome-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'telefone',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-telefone-label')),
				),
			)
		));
		
		// ===== Verificar se já foi informado o email. Senão for, redirecionar para a página 'identificacao/'.
		
		$informarEmail = true;
		
		$identificacaoCadastro = gestor_sessao_variavel('identificacao-cadastro');
		if(existe($identificacaoCadastro)){
			if(isset($identificacaoCadastro['valido'])){
				$informarEmail = false;
			}
		}
		
		if($informarEmail){
			$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-informar-email'));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('identificacao/');
		}
		
		// ===== Dados do usuário token.
		
		gestor_incluir_biblioteca('usuario');
		
		$usuario_token = usuario_token_dados(Array(
			'sessao' => isset($_REQUEST['permanecer-logado']) ? null : true
		));
		
		// ===== API-Servidor novo cadastro.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_identificacao(Array(
			'opcao' => 'cadastrar',
			'usuario_token' => $usuario_token,
			'email' => $identificacaoCadastro['email'],
			'nome' => banco_escape_field($_REQUEST['nome']),
			'senha' => banco_escape_field($_REQUEST['senha']),
			'telefone' => banco_escape_field($_REQUEST['telefone']),
			'cnpj_ativo' => ($_REQUEST['cnpj_ativo'] == 'sim' ? 'sim' : 'nao'),
			'cpf' => (isset($_REQUEST['cpf']) ? banco_escape_field($_REQUEST['cpf']) : null),
			'cnpj' => (isset($_REQUEST['cnpj']) ? banco_escape_field($_REQUEST['cnpj']) : null),
			'token' => (isset($_REQUEST['token']) ? banco_escape_field($_REQUEST['token']) : null),
		));
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			
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
			
			// ====== Criar usuário no banco.
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			foreach($dados['usuarios'] as $chave => $dado){
				switch($chave){
					case 'cnpj_ativo':
						$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
					break;
					/* case 'int':
						$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
					break; */
					default:
						if(existe($dado)){
							$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
				}
			}
			
			banco_insert_name
			(
				$campos,
				"usuarios"
			);
			
			// ===== Caso o usuário escolher a opção para manter logado, gera token de autenticação com tempo de expiração, senão será expirado assim que o usuário fechar navegador
			
			usuario_gerar_token_autorizacao(Array(
				'id_hosts_usuarios' => $dados['id_hosts_usuarios'],
				'id_hosts_usuarios_tokens' => $dados['id_hosts_usuarios_tokens'],
				'usuario_token' => $usuario_token,
				'sessao' => isset($_REQUEST['permanecer-logado']) ? null : true,
			));
			
			// ===== Se o usuário for válido, redirecionar para o local pretendido se houver, senão para minha-conta ou emissão caso seja prosseguimento de carrinho.
			
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
	
	// ===== Verificar se já foi informado o email. Senão for, redirecionar para a página 'identificacao/'.
	
	$informarEmail = true;
	
	$identificacaoCadastro = gestor_sessao_variavel('identificacao-cadastro');
	if(existe($identificacaoCadastro)){
		if(isset($identificacaoCadastro['valido'])){
			$informarEmail = false;
		}
	}
	
	if($informarEmail){
		$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-informar-email'));
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
		
		gestor_redirecionar('identificacao/');
	}
	
	// ===== Preencher o email informado no formulário.
	
	pagina_trocar_variavel_valor('email',$identificacaoCadastro['email']);
	
	// ===== Variáveis iniciais.
	
	$JSidentificacao = Array();
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_GESTOR['plataforma-cliente']['plataforma-recaptcha-active'])){
		if($_GESTOR['plataforma-cliente']['plataforma-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_GESTOR['plataforma-cliente']['plataforma-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_GESTOR['plataforma-cliente']['plataforma-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Formulário validação.
	
	formulario_validacao(Array(
		'formId' => 'formCadastrar',
		'validacao' => Array(
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'nome',
				'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-nome-label')),
			),
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
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'telefone',
				'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-telefone-label')),
			),
		)
	));
	
	// ===== Células.
	
	$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	if(isset($_REQUEST['carrinho'])){
		layout_trocar_variavel_valor('layout#step',$cel['step']);
		layout_trocar_variavel_valor('layout#step-mobile',$cel['step-mobile']);
	} else {
		layout_trocar_variavel_valor('layout#step','');
		layout_trocar_variavel_valor('layout#step-mobile','');
	}
	
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
	
	$_GESTOR['javascript-vars']['identificacao'] = $JSidentificacao;
}

// ==== Ajax

function identificacao_cadastro_ajax_padrao(){
	global $_GESTOR;
	
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function identificacao_cadastro_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': identificacao_cadastro_ajax_opcao(); break;
			default: identificacao_cadastro_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': identificacao_cadastro_opcao(); break;
			default: identificacao_cadastro_padrao();
		}
	}
}

identificacao_cadastro_start();

?>