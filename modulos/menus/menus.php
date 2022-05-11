<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'menus';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.13',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'modelo',
		'id' => 'id',
		'id_numerico' => 'id_'.'modelo',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
);

function menus_config(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Definição dos campos do banco de dados para configurações.
	
	$camposBanco = Array(
		'campo',
	);
	
	$camposBancoAntes = $camposBanco;
	
	// ===== Configurações das variáveis.
	
	$config = gestor_incluir_configuracao(Array(
		'id' => $_GESTOR['modulo-id'].'.config',
	));
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Decodificar o 'dadosServidor'.
		
		$dadosServidor = Array();
		
		if(isset($_REQUEST['dadosServidor'])){
			$dadosServidor = json_decode($_REQUEST['dadosServidor'],true);
		}
		
		
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		/* $resultado = banco_select(Array(
			'tabela' => $modulo['tabela']['nome'],
			'campos' => $camposBanco,
			'extra' => 
				"WHERE modulo='".$_GESTOR['modulo-id']."'"
				." AND id_hosts='".$_GESTOR['host-id']."'"
		)); */
		
		// ===== Atualizar ou criar os campos permitidos.
		
		$alterouDados = false;
		$criouDados = false;
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if($alterouDados){
			// ===== Criar / Atualizar versão desta configuração.
			
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
				'sem_id' => true,
				'versao' => $versao,
			));
		}
		
		if($alterouDados || $criouDados){
			// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_variaveis(Array(
				'opcao' => 'editar',
				'modulo' => $_GESTOR['modulo-id'],
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = menus_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			}
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	/* $resultado = banco_select(Array(
		'tabela' => $modulo['tabela']['nome'],
		'campos' => $camposBanco,
		'extra' => 
			"WHERE modulo='".$_GESTOR['modulo-id']."'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
	)); */
	
	// ===== Montar os valores do que é permitido via config alterar.
	
	// ===== Criar o 'dadosServidor'.
	
	$variaveisMenu = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'identificador','conjunto' => true));
	
	if($config['menusPadroes'])
	foreach($config['menusPadroes'] as $id => $menusPadroes){
		if(!isset($menusPadroes['inativo'])){
			$menusPadroesAux = Array();
			
			foreach($menusPadroes as $key => $item){
				if($variaveisMenu)
				foreach($variaveisMenu as $key2 => $variavel){
					if($item['label'] == $key2){
						$item['titulo'] = $variavel;
						break;
					}
				}
				
				$menusPadroesAux[$key] = $item;
			}
			
			$dadosServidor[$id] = $menusPadroesAux;
		}
	}
	
	$dadosServidor = htmlentities(json_encode($dadosServidor));
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#dadosServidor#",$dadosServidor);
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['config']['finalizar'] = Array(
		'formulario' => Array(
			'campos' => Array(
				
			),
		)
	);
}

function menus_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'opcao':
			
		break;
	}
}

// ==== Ajax

function menus_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function menus_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': menus_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		menus_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'config': menus_config(); break;
		}
		
		interface_finalizar();
	}
}

menus_start();

?>