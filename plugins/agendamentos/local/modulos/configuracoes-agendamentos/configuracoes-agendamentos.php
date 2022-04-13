<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'configuracoes-agendamentos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_variaveis',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_variaveis',
	),
);

// ===== Funções Auxiliares

// ===== Funções Principais

function configuracoes_config(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Definição dos campos do banco de dados para configurações.
	
	$camposBanco = Array(
		'id',
		'valor',
		'tipo',
	);
	
	$camposBancoAntes = $camposBanco;
	
	// ===== Configurações das variáveis.
	
	$config = gestor_incluir_configuracao(Array(
		'id' => $_GESTOR['modulo-id'].'.config',
		'plugin' => 'agendamentos',
	));
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		$resultado = banco_select(Array(
			'tabela' => $modulo['tabela']['nome'],
			'campos' => $camposBanco,
			'extra' => 
				"WHERE modulo='".$_GESTOR['modulo-id']."'"
				." AND id_hosts='".$_GESTOR['host-id']."'"
		));
		
		// ===== Incluir configurações do módulo loja.
		
		$variaveisLoja = banco_select(Array(
			'tabela' => 'variaveis',
			'campos' => Array(
				'id',
			),
			'extra' => 
				"WHERE modulo='loja'"
		));
		
		if($variaveisLoja){
			foreach($variaveisLoja as $valor){
				$config['campos'][$valor['id']] = Array();
			}
		}
		
		// ===== Atualizar ou criar os campos permitidos.
		
		$alterouDados = false;
		$criouDados = false;
		
		if($config)
		foreach($config['campos'] as $campo => $opcoes){
			$postName = (isset($opcoes['postName']) ? $opcoes['postName'] : $campo);
			
			$found = false;
			$campoAntes = '';
			if($resultado){
				foreach($resultado as $campoBD){
					if($campo == $campoBD['id']){
						$campoAntes = $campoBD['valor'];
						$found = true;
						break;
					}
				}
			}
			
			if(!$found){
				if(existe($_REQUEST[$postName])){
					$campos = null; $campo_sem_aspas_simples = null;
					
					$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "modulo"; $campo_valor = $_GESTOR['modulo-id']; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id"; $campo_valor = $campo; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "valor"; $campo_valor = banco_escape_field($_REQUEST[$postName]); 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "tipo"; $campo_valor = (isset($opcoes['tipo']) ? $opcoes['tipo'] : 'string'); 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						$modulo['tabela']['nome']
					);
					
					$criouDados = true;
				}
			} else {
				if(isset($_REQUEST[$postName])){
					$_REQUEST[$postName] = banco_escape_field($_REQUEST[$postName]);
				}
				
				if($campo == 'logomarca'){
					if($_REQUEST[$postName] == '-1'){
						$_REQUEST[$postName] = null;
					}
				}
				
				if($campoAntes != $_REQUEST[$postName]){
					$campo_tabela = $modulo['tabela']['nome'];
					$campo_tabela_extra = "WHERE modulo='".$_GESTOR['modulo-id']."' AND id='".$campo."'";
					
					switch($campo){
						case 'logomarca':
							$campo_nome = "valor"; $editar[$campo_tabela][] = (isset($_REQUEST[$postName]) ? $campo_nome."='" . $_REQUEST[$postName] . "'" : $campo_nome."=NULL");
						break;
						default:
							$campo_nome = "valor"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$postName] . "'";
					}
					
					$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
					
					if($editar_sql[$campo_tabela]){
						banco_update
						(
							$editar_sql[$campo_tabela],
							$campo_tabela,
							$campo_tabela_extra
						);
					}
					$editar = false;$editar_sql = false;
					
					// ===== Criar entrada no histórico.
					
					$alterouDados = true;
					
					switch($campo){
						case 'logomarca':
							$alteracoes[] = Array('campo' => 'label-'.$campo, 'valor_antes' => $campoAntes,'valor_depois' => $_REQUEST[$postName],'tabela' => Array(
								'nome' => 'hosts_arquivos',
								'campo' => 'nome',
								'id_numerico' => 'id_hosts_arquivos',
							));
						break;
						default:
							$alteracoes[] = Array('campo' => 'label-'.$campo, 'valor_antes' => $campoAntes,'valor_depois' => $_REQUEST[$postName]);
					}
					
					
				}
			}
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if($alterouDados){
			// ===== Criar / Atualizar versão desta configuração.
			
			$versao = '1';
			$found = false;
			if($resultado){
				foreach($resultado as $campoBD){
					if($campoBD['id'] == 'versao'){
						$versao = (int)$campoBD['valor'] + 1;
						$found = true;
						break;
					}
				}
			}
			
			if(!$found){
				$campos = null; $campo_sem_aspas_simples = null;
				
				$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "modulo"; $campo_valor = $_GESTOR['modulo-id']; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id"; $campo_valor = 'versao'; 																	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "valor"; $campo_valor = $versao; 																	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "tipo"; $campo_valor = 'int'; 																	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				
				banco_insert_name
				(
					$campos,
					$modulo['tabela']['nome']
				);
			} else {
				$campo_tabela = $modulo['tabela']['nome'];
				$campo_tabela_extra = "WHERE modulo='".$_GESTOR['modulo-id']."' AND id='versao'";
				
				$campo_nome = "valor"; $editar[$campo_tabela][] = $campo_nome."='" . $versao . "'";
				
				$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
				
				if($editar_sql[$campo_tabela]){
					banco_update
					(
						$editar_sql[$campo_tabela],
						$campo_tabela,
						$campo_tabela_extra
					);
				}
				$editar = false;$editar_sql = false;
			}
			
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
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			}
		}
		
		// ===== Interface de administração da configuração salvar.
		
		gestor_incluir_biblioteca('configuracao');
		
		configuracao_hosts_salvar(Array(
			'modulo' => $_GESTOR['modulo-id'],
			'grupo' => 'padrao-host',
			'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
			'tabela' => $modulo['tabela'],
		));
		
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	$resultado = banco_select(Array(
		'tabela' => $modulo['tabela']['nome'],
		'campos' => $camposBanco,
		'extra' => 
			"WHERE modulo='".$_GESTOR['modulo-id']."'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
	));
	
	// ===== Incluir configurações do módulo loja.
	
	$variaveisLoja = banco_select(Array(
		'tabela' => 'variaveis',
		'campos' => Array(
			'id',
			'valor',
		),
		'extra' => 
			"WHERE modulo='loja'"
	));
	
	if($variaveisLoja){
		gestor_incluir_biblioteca('pagina');
		
		$cel_nome = 'loja'; $cel[$cel_nome] = pagina_celula($cel_nome);
		
		foreach($variaveisLoja as $vLoja){
			// ===== Procurar se o usuário modificou alguma variável. Se sim, colocar o valor dela ao invés do valor do padrão.
			
			if($resultado){
				foreach($resultado as $campoBD){
					if($vLoja['id'] == $campoBD['id']){
						$vLoja['valor'] = $campoBD['valor'];
					}
				}
			}
			
			// ===== Montar os alertas.
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"label",$vLoja['id']);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"name",$vLoja['id']);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"placeholder",$vLoja['id']);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"value",$vLoja['valor']);
			
			pagina_celula_incluir($cel_nome,$cel_aux);
		}
		
		pagina_celula_incluir($cel_nome,'');
	}
	
	// ===== Montar os valores do que é permitido via config alterar.
	
	if($config)
	foreach($config['campos'] as $campo => $opcoes){
		$found = false;
		if($resultado){
			foreach($resultado as $campoBD){
				if($campo == $campoBD['id']){
					// ===== Trocar a variável pelo valor.
					
					switch($campo){
						case 'logomarca':
							$id_hosts_arquivos = $campoBD['valor'];
						break;
						default:
							$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#'.$campo.'#',$campoBD['valor']);
							$found = true;
							
					}
					
					if($found){
						break;
					}
				}
			}
		}
		
		if(!$found){
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#'.$campo.'#','');
		}
	}
	
	// ===== Interface de configurações do host.
	
	gestor_incluir_biblioteca('configuracao');
	
	configuracao_hosts(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'grupo' => 'padrao-host',
		'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
		'marcador' => '<!-- configuracao-hosts -->',
	));
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['config']['finalizar'] = Array(
		'formulario' => Array(
			'campos' => Array(
				
			),
		)
	);
}

function configuracoes_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
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
		}
		
		interface_finalizar();
	}
}

configuracoes_start();

?>