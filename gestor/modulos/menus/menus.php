<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'menus';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.27',
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
	'resources' => [
		'pt-br' => [
			'layouts' => [],
			'pages' => [
			    [
			        'name' => 'Menus',
			        'id' => 'menus',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'menus/',
			        'type' => 'system',
			        'option' => 'config',
			        'root' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '70796b66e14abbd59ee603d3554fa1dd',
			            'css' => '1d885955ff2c8740f53e2eabc0b5a09b',
			            'combined' => '19aa9bb758b3061cacb33e3343f46aeb',
			        ],
			    ],
			],
			'components' => [],
		],
	],
);

function menus_config(){
	global $_GESTOR;
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== ID do host.
		
		$id_hosts = $_GESTOR['host-id'];
		
		// ===== Decodificar o 'dadosServidor'.
		
		$dadosServidor = Array();
		
		if(isset($_REQUEST['dadosServidor'])){
			$dadosServidor = json_decode($_REQUEST['dadosServidor'],true);
		}
		
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		$hosts_menus_itens = banco_select(Array(
			'tabela' => 'hosts_menus_itens',
			'campos' => '*',
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
		));
		
		// ===== Atualizar ou criar os campos permitidos.
		
		$alterouDados = false;
		$criouDados = false;
		
		// ===== Varrear os dados enviados e atualizar o banco de dados.
		
		if($dadosServidor)
		foreach($dadosServidor as $menu_id => $menus){
			$itens = $menus['itens'];
			
			if($itens)
			foreach($itens as $id => $item){
				$found = false;
				
				if($hosts_menus_itens)
				foreach($hosts_menus_itens as $host_menu_item){
					if(
						$host_menu_item['menu_id'] == $menu_id &&
						$host_menu_item['id'] == $id
					){
						//$hosts_menus_itens[$key]['verificado'] = true;
						$found = true;
						break;
					}
				}
				
				if($found){
					$inativoBanco = false;
					if($host_menu_item['inativo']){
						$inativoBanco = true;
					}
					
					$inativoEnvidado = false;
					if(isset($item['inativo'])){
						$inativoEnvidado = true;
					}
					
					if($inativoBanco != $inativoEnvidado){
						if($inativoEnvidado){
							banco_update_campo('inativo','1',true);
						} else {
							banco_update_campo('inativo','NULL',true);
						}
						
						banco_update_executar('hosts_menus_itens',"WHERE id_hosts='".$id_hosts."' AND menu_id='".$menu_id."' AND id='".$id."'");
						
						$alterouDados = true;
						
						$alteracao_txt .= (existe($alteracao_txt) ? ', ':'') . $id;
					}
				}
			}
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if($alterouDados){
			// ===== Alterações txt.
			
			$changeMenu = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'historic-change-menu'));
			
			// ===== Alterações txt.
			
			$alteracao_txt = $changeMenu . ' <b>' . $alteracao_txt . '</b>';
			
			// ===== Alteções vetor.
			
			$alteracoes[] = Array(
				'alteracao' => 'change-menu',
				'alteracao_txt' => $alteracao_txt,
			);
			
			// ===== Alterar versão e data.
			
			$hosts_configuracoes = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_configuracoes',
				'campos' => Array(
					'versao',
				),
				'extra' => 
					"WHERE id_hosts='".$_GESTOR['host-id']."'"
					." AND modulo='".$_GESTOR['modulo-id']."'"
			));
			
			if($hosts_configuracoes){
				banco_update_campo('versao','versao+1',true);
				banco_update_campo('data_modificacao','NOW()',true);
				
				banco_update_executar('hosts_configuracoes',"WHERE id_hosts='".$_GESTOR['host-id']."' AND modulo='".$_GESTOR['modulo-id']."'");
				
				$versao_config = (int)$hosts_configuracoes['versao'] + 1;
			} else {
				banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
				banco_insert_name_campo('modulo',$_GESTOR['modulo-id']);
				banco_insert_name_campo('versao','1',true);
				banco_insert_name_campo('data_modificacao','NOW()',true);
				
				banco_insert_name
				(
					banco_insert_name_campos(),
					"hosts_configuracoes"
				);
				
				$versao_config = '1';
			}
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
				'sem_id' => true,
				'versao' => $versao_config,
			));
		}
		
		if($alterouDados || $criouDados){
			// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_menus(Array(
				'opcao' => 'atualizar',
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
	
	// ===== Pegar os menus do banco de dados.
	
	$hosts_menus_itens = banco_select(Array(
		'tabela' => 'hosts_menus_itens',
		'campos' => '*',
		'extra' => 
			"WHERE id_hosts='".$_GESTOR['host-id']."'"
	));
	
	// ===== Criar o 'dadosServidor'.
	
	$variaveisMenu = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'conjunto' => true));
	
	$dadosServidor = Array();
	
	if($hosts_menus_itens)
	foreach($hosts_menus_itens as $item){
		if($variaveisMenu)
		foreach($variaveisMenu as $key => $variavel){
			if($item['label'] == $key){
				$item['titulo'] = $variavel;
				break;
			}
		}
		
		if(!$item['inativo']){
			unset($item['inativo']);
		}
		
		$dadosServidor[$item['menu_id']]['itens'][$item['id']] = $item;
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