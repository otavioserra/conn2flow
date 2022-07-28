<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'identificacao-area-restrita';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.5',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function identificacao_area_restrita_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
		'interface',
		'formulario',
	));
	
	// ===== Tentativa de gerar autorização.
	
	if(isset($_REQUEST['_gestor-restrict-area-atualizar'])){
		// ===== Valores do redirect e queryString.
		
		$querystring = (isset($_REQUEST['_gestor-restrict-area-querystring']) ? banco_escape_field($_REQUEST['_gestor-restrict-area-querystring']) : '');
		
		$redirect = gestor_querystring_variavel($querystring,'redirect');
		$querystring = gestor_querystring_remover_variavel($querystring,'redirect');
		
		// ===== Validação de campos obrigatórios
		
		formulario_validacao_campos_obrigatorios(Array(
			'redirect' => 'identificacao-area-restrita/?redirect='.urlencode($redirect).(existe($querystring) ? '&'.$querystring:''),
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
				),
			)
		));
		
		// ===== API-Servidor para área restrita.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'identificacao',
			'opcao' => 'areaRestrita',
			'dados' => Array(
				'senha' => banco_escape_field($_REQUEST['senha']),
				'usuarioID' => $_GESTOR['usuario-id'],
			),
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				case 'INACTIVE_USER':
				case 'USER_INVALID':
					$alerta = $retorno['error-msg'];
				break;
				default:
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
			}
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			// ===== Dados de retorno.
			
			$dados = Array();
			if(isset($retorno['data'])){
				$dados = $retorno['data'];
			}
			
			// ===== Permitir um máximo de erros.
			
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
			
			$maximoSenhasInvalidas = (int)$dados['usuarioMaxSenhaInvalidas'];
			
			// ===== Caso alcance uma quantidade máxima, deslogar o usuário. Senão tentar novamente.
			
			if($tentativas < $maximoSenhasInvalidas){
				banco_update
				(
					"senha_incorreta_tentativas='".$tentativas."'",
					"usuarios_tokens",
					"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
				);
				
				gestor_redirecionar('identificacao-area-restrita/?redirect='.urlencode($redirect).(existe($querystring) ? '&'.$querystring:''));
			} else {
				gestor_redirecionar('identificacao/?sair=sim');
			}
		} else {
			// ===== Validar usuário.
			
			gestor_incluir_biblioteca('usuario');
			
			usuario_autorizacao_provisoria(Array('validar' => true));
			
			// ===== Zerar tentativas de fornecer senha.
			
			banco_update
			(
				"senha_incorreta_tentativas=NULL",
				"usuarios_tokens",
				"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
			);
			
			// ===== Se estiver tudo ok.
			
			gestor_redirecionar($redirect,$querystring);
		}
	}
	
	// ===== Alterar dados do formulário de validação
	
	$queryString = gestor_querystring();
	
	pagina_trocar_variavel_valor('form-querystring',$queryString);
	
	// ===== Validação do formulário.
	
	formulario_validacao(Array(
		'formId' => 'restrictArea',
		'validacao' => Array(
			Array(
				'regra' => 'senha',
				'campo' => 'senha',
				'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-senha-label')),
			),
		)
	));
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step','');
	layout_trocar_variavel_valor('layout#step-mobile','');
	
	
	// ===== Finalizar o layout com as variáveis padrões.
	
	layout_loja();
	
	// ===== Finalizar interface.
	
	interface_finalizar();
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('modulos');
}

// ==== Ajax

function identificacao_area_restrita_ajax_padrao(){
	global $_GESTOR;
	
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function identificacao_area_restrita_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': identificacao_area_restrita_ajax_opcao(); break;
			default: identificacao_area_restrita_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': identificacao_area_restrita_opcao(); break;
			default: identificacao_area_restrita_padrao();
		}
	}
}

identificacao_area_restrita_start();

?>