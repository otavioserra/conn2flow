<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'comunicacao-configuracoes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.15',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_variaveis',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_variaveis',
	),
);

// ===== Funções Auxiliares

// ===== Funções Principais

function configuracoes_disparador_emails(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Atraso de 5 segundos para evitar abusos.
		
		sleep('5');
		
		// ===== Envio do email teste com as novas configurações.
		
		$usuario = gestor_usuario();
		$assunto = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-tests-subject'));
		
		$assunto = modelo_var_troca($assunto,"#cod#",$cod);
		
		gestor_incluir_biblioteca('comunicacao');
		
		if(comunicacao_email(Array(
			'hostPersonalizacao' => true,
			'destinatarios' => Array(
				Array(
					'email' => $usuario['email'],
					'nome' => $usuario['nome'],
				),
			),
			'mensagem' => Array(
				'htmlAssinaturaAutomatica' => true,
				'assunto' => $assunto,
				'htmlLayoutID' => 'comunicacao-email-teste',
				'htmlVariaveis' => Array(),
			),
		))){
			// Email enviado com sucesso!
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-sent-alert'))
			));
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/disparador-emails/');
	}
	
	// ===== Incluir modal para confirmação de desinstalação.
	
	$modal = gestor_componente(Array(
		'id' => 'interface-modal-generico',
	));
	
	$modal = modelo_var_troca($modal,"#titulo#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-confirm-title')));
	$modal = modelo_var_troca($modal,"#mensagem#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-confirm-menssage')));
	$modal = modelo_var_troca($modal,"#botao-cancelar#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-confirm-button-cancel')));
	$modal = modelo_var_troca($modal,"#botao-confirmar#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'email-confirm-button-confirm')));
	
	$_GESTOR['pagina'] .= $modal;
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface simples finalizar opções
	
	$_GESTOR['interface']['simples']['finalizar'] = Array(
		'forcarSemID' => true,
		'botoes' => Array(
			'configuracoes' => Array(
				'url' => '../',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-config')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-config')),
				'icon' => 'grip vertical',
				'cor' => 'blue',
			),
		),
	);
}

function configuracoes_config(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Configurações das variáveis.
	
	$config = gestor_incluir_configuracao(Array(
		'id' => $_GESTOR['modulo-id'].'.config',
	));
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Interface de administração da configuração salvar.
		
		gestor_incluir_biblioteca('configuracao');
		
		$retorno = configuracao_hosts_salvar(Array(
			'modulo' => $_GESTOR['modulo-id'],
			'grupos' => Array(
				'padrao-host',
			),
			'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
			'tabela' => $modulo['tabela'],
		));
		
		if(isset($retorno['alterouVariavel'])){
			
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface de configurações do host.
	
	gestor_incluir_biblioteca('configuracao');
	
	configuracao_hosts(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'grupos' => Array(
			'padrao-host',
		),
		'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
		'marcador' => '<!-- comunicacao-configuracoes -->',
	));
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['config']['finalizar'] = Array(
		'botoes' => Array(
			'disparador' => Array(
				'url' => 'disparador-emails/',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-email')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-email')),
				'icon' => 'paper plane outline',
				'cor' => 'green',
			),
		),
	);
}

function configuracoes_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'disparador-emails':
			$_GESTOR['interface-opcao'] = 'simples';
		break;
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function configuracoes_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function configuracoes_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': configuracoes_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		configuracoes_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'config': configuracoes_config(); break;
			case 'disparador-emails': configuracoes_disparador_emails(); break;
		}
		
		interface_finalizar();
	}
}

configuracoes_start();

?>