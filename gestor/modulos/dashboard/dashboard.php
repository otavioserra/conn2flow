<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'dashboard';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html'),
	'toasts' => Array(
		'troca_time' => 5000,
		'updateNotShowToastTime' => 60*24*7,
		'opcoes_padroes' => Array(
			'displayTime' => 10000,
			'class' => 'black',
		),
	),
);

function dashboard_toast($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador.
	// opcoes - Array - Obrigatório - Opções do toast.
	// botoes - Array - Opcional - Botões do toast.
	// regra - String - Opcional - Regra caso seja necessário disparar alguma opção específica.
	
	// ===== 
	
	if(isset($id) && isset($opcoes)){
		// ===== Criar variável toast caso a mesma não tenha sido criada antes.
		
		if(!isset($_GESTOR['javascript-vars']['toasts'])){
			$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
			
			$_GESTOR['javascript-vars']['toasts'] = Array();
			$_GESTOR['javascript-vars']['toasts_options'] = Array(
				'troca_time' => $modulo['toasts']['troca_time'],
				'updateNotShowToastTime' => $modulo['toasts']['updateNotShowToastTime'],
				'opcoes_padroes' => $modulo['toasts']['opcoes_padroes'],
			);
		}
		
		// ===== Criar o array do toast
		
		$toast = Array();
		
		// ===== Incluir opções no toast
		
		foreach($opcoes as $chave => $valor){
			$toast['opcoes'][$chave] = $valor;
		}
		
		// ===== Incluir opções no toast
		
		foreach($botoes as $chave => $valor){
			$toast['botoes'][$chave] = $valor;
		}
		
		// ===== Incluir regra no toast
		
		if(isset($regra)){ $toast['regra'] = $regra; }
		
		// ===== Inserir o toast no array de toasts.
		
		$_GESTOR['javascript-vars']['toasts'][$id] = $toast;
	}
}

function dashboard_toast_atualizacoes(){
	global $_GESTOR;
	
	// ===== Verificação de atualização
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Verifica se o usuário é admin do host para poder ter acesso a atualizações.
	
	if(isset($host_verificacao['privilegios_admin'])){
		// ===== Verificar versão do gestor cliente.
		
		$id_hosts = $host_verificacao['id_hosts'];
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'gestor_cliente_versao_num',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		$gestor_cliente_versao_num = $hosts[0]['gestor_cliente_versao_num'];
		
		// ===== Comparar versões e montar a interface. Ou é atualização normal, dado que há uma versão mais nova, ou então se quiser forçar a atualização afim de sobrescrever os dados no hospedeiro do cliente.
		
		if($_GESTOR['gestor-cliente']['versao_num'] > (int)$gestor_cliente_versao_num){
			$botaoNegativeMessageLayout = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-negative-message'));
			
			$botaoNegativeMessageLayout = modelo_var_troca($botaoNegativeMessageLayout,"#url#",'<a href="'.$_GESTOR['url-raiz'].'host-update/">'.$_GESTOR['url-raiz'].'host-update/</a>');
			
			dashboard_toast(Array(
				'id' => 'update',
				'regra' => 'update',
				'opcoes' => Array(
					'title' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-title')),
					'message' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-message')),
				),
				'botoes' => Array(
					'update-positivo' => Array(
						'text' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-positive-label')),
						'icon' => 'check',
						'class' => 'green',
						'click' => Array(
							'message' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-positive-message')),
							'showProgress' => 'bottom',
							'class' => 'success',
							'displayTime' => 4000,
						),
					),
					'update-negativo' => Array(
						'text' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-negative-label')),
						'icon' => 'ban',
						'class' => 'icon red',
						'click' => Array(
							'displayTime' => 6000,
							'showProgress' => 'bottom',
							'message' => $botaoNegativeMessageLayout,
						),
					),
				),
			));
		}
	}
}

function dashboard_menu(){
	global $_GESTOR;
	
	// ===== Campos padrões
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#titulo#",$_GESTOR['pagina#titulo']);
	
	// ===== Menu de módulos
	
	$usuario = gestor_usuario();
	
	// ===== Verificar se o usuário é filho de um host ou não.
	
	if(existe($usuario['id_hosts'])){
		// ===== Verificar se o usuário tem um perfil de gestor ativo.
		
		if(existe($usuario['gestor_perfil'])){
			$gestor_perfil = $usuario['gestor_perfil'];
			
			// ===== Verificar se o módulo alvo tem permissão no perfil.
			
			$usuarios_perfis_modulos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'modulo',
				))
				,
				"usuarios_gestores_perfis_modulos",
				"WHERE perfil='".$gestor_perfil."'"
				." AND id_hosts='".$usuario['id_hosts']."'"
			);
		} else {
			// ===== Pegar o usuário pai do usuário em questão.
			
			$hosts = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts',
				'campos' => Array(
					'id_usuarios',
				),
				'extra' => 
					"WHERE id_hosts='".$usuario['id_hosts']."'"
			));
			
			// ===== Pegar o identificador do perfil do pai do usuário.
			
			$usuarios = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios',
				'campos' => Array(
					'id_usuarios_perfis',
				),
				'extra' => 
					"WHERE id_usuarios='".$hosts['id_usuarios']."'"
			));
			
			// ===== Pegar o perfil do usuário.
			
			$usuarios_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_perfis',
				'campos' => Array(
					'id',
				),
				'extra' => 
					"WHERE id_usuarios_perfis='".$usuarios['id_usuarios_perfis']."'"
			));
			
			$perfil = $usuarios_perfis['id'];
			
			// ===== Verificar se o módulo alvo tem permissão no perfil.
			
			$usuarios_perfis_modulos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'modulo',
				))
				,
				"usuarios_perfis_modulos",
				"WHERE perfil='".$perfil."'"
			);
		}
	} else {
		// ===== Pegar o perfil do usuário.
		
		$usuarios_perfis = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_perfis',
			'campos' => Array(
				'id',
			),
			'extra' => 
				"WHERE id_usuarios_perfis='".$usuario['id_usuarios_perfis']."'"
		));
		
		$perfil = $usuarios_perfis['id'];
		
		// ===== Verificar se o módulo alvo tem permissão no perfil.
		
		$usuarios_perfis_modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'modulo',
			))
			,
			"usuarios_perfis_modulos",
			"WHERE perfil='".$perfil."'"
		);
	}
	
	// ===== Pegar dados de páginas e módulos
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas(Array(
			'modulo',
			'caminho',
		))
		,
		"paginas",
		"WHERE raiz IS NOT NULL"
	);
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
			'id_modulos_grupos',
			'id',
			'nome',
			'icone',
			'icone2',
			'plugin',
		))
		,
		"modulos",
		"ORDER BY nome ASC"
	);
	
	$modulos_grupos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos_grupos',
			'nome',
		))
		,
		"modulos_grupos",
		"WHERE id!='bibliotecas'".
		"ORDER BY nome ASC"
	);
	
	$cel_nome = 'menu-item'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'icon'; $cel[$cel_nome] = modelo_tag_val($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $cel['menu-item'] = modelo_tag_in($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'icon-2'; $cel[$cel_nome] = modelo_tag_val($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $cel['menu-item'] = modelo_tag_in($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'card'; $cel[$cel_nome] = modelo_tag_val($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $cel['menu-item'] = modelo_tag_in($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	
	// ===== Verifica se o usuário é admin do host para mostrar no menu o Host Configurações ou não.
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	$privilegios_admin = false;
	if(isset($host_verificacao['privilegios_admin'])){
		$privilegios_admin = true;
	}
	
	// ===== Verificar se o usuário faz parte de um host. Se sim, baixar os plugins do host.
	
	if(isset($_GESTOR['host-id'])){
		$hosts_plugins = banco_select(Array(
			'tabela' => 'hosts_plugins',
			'campos' => Array(
				'plugin',
				'habilitado',
			),
			'extra' => 
				"WHERE id_hosts='".$_GESTOR['host-id']."'"
		));
	}
	
	if($modulos_grupos)
	foreach($modulos_grupos as $mg){
		$found_grup = false;
		
		if($modulos)
		foreach($modulos as $modulo){
			if($mg['id_modulos_grupos'] == $modulo['id_modulos_grupos']){
				$modulo_perfil = false;
				
				if($usuarios_perfis_modulos)
				foreach($usuarios_perfis_modulos as $upm){
					if($upm['modulo'] == $modulo['id']){
						$modulo_perfil = true;
						break;
					}
				}
				
				if($modulo['id'] == 'dashboard' || !$modulo_perfil){
					continue;
				}
				
				// ===== Verificar se o usuário faz parte de um host. Se sim, verificar os plugins do usuario e ver se esse faz parte de um plugin habilitado.
				
				if(isset($_GESTOR['host-id'])){
					if($modulo['plugin']){
						$habilitado = false;
						
						if($hosts_plugins)
						foreach($hosts_plugins as $hosts_plugin){
							if(
								$hosts_plugin['plugin'] == $modulo['plugin'] &&
								$hosts_plugin['habilitado']
							){
								$habilitado = true;
							}
						}
						
						if(!$habilitado){
							continue;
						}
					}
				}
				
				// ===== Se for o host configurações e não tiver privilégio, não mostrar no menu.
				
				if($modulo['id'] == 'host-configuracao' && !$privilegios_admin && isset($_GESTOR['host-id'])){
					continue;
				}
				
				if(!$found_grup){
					$grupo_pagina = $cel['menu-item'];
				}
				
				$cel_nome = 'card';
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#nome#",$modulo['nome']);
				
				if($modulo['icone2']){
					$cel_nome_icon = 'icon-2';
					$cel_icon = $cel[$cel_nome_icon];
					
					$cel_icon = modelo_var_troca($cel_icon,"#icon-2#",($modulo['icone2'] ? $modulo['icone2'] : 'question circle outline'));
				} else {
					$cel_nome_icon = 'icon';
					$cel_icon = $cel[$cel_nome_icon];
				}
				
				$cel_icon = modelo_var_troca($cel_icon,"#icon#",($modulo['icone'] ? $modulo['icone'] : 'question circle outline'));
				
				$cel_aux = modelo_var_troca($cel_aux,"<!-- icon -->",$cel_icon);
				
				// ===== Se existe a página padrão, senão o link será para a raiz.
				
				$pagina_found = false;
				
				if($paginas)
				foreach($paginas as $pagina){
					if($modulo['id'] == $pagina['modulo']){
						$cel_aux = modelo_var_troca_tudo($cel_aux,"#link#",$_GESTOR['url-raiz'].$pagina['caminho']);
						$pagina_found = true;
						break;
					}
				}
				
				if(!$pagina_found){
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#link#",$_GESTOR['url-raiz'].'dashboard/');
				}
				
				// ===== Adicionar ao grupo da página.
				
				$grupo_pagina = modelo_var_in($grupo_pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
				$found_grup = true;
			}
		}
		
		if($found_grup){
			$cel_nome = 'card';
			$grupo_pagina = modelo_var_troca($grupo_pagina,'<!-- '.$cel_nome.' -->','');
			
			$grupo_pagina = modelo_var_troca($grupo_pagina,"#grupo#",$mg['nome']);
			
			$cel_nome = 'menu-item';
			$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->',$grupo_pagina);
		}
	}
	
	$cel_nome = 'menu-item';
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->','');
}

function dashboard_remover_pagina_instalacao_sucesso(){
	global $_GESTOR;
	
	try {
		// ===== Verificar se existe a página de instalação-sucesso
		
		$paginas = banco_select(Array(
			'unico' => true,
			'tabela' => 'paginas',
			'campos' => Array(
				'id_paginas',
				'nome',
			),
			'extra' => "WHERE caminho = 'instalacao-sucesso' AND status = 'A'"
		));
		
		if($paginas && isset($paginas['id_paginas'])){
			// ===== Remover a página da base de dados
			
			banco_delete(Array(
				'tabela' => 'paginas',
				'extra' => "WHERE id_paginas = '".$paginas['id_paginas']."'"
			));
			
			// ===== Exibir toast informativo
			
			dashboard_toast(Array(
				'id' => 'instalacao-sucesso-removida',
				'opcoes' => Array(
					'title' => 'Página de Instalação Removida',
					'message' => 'A página de instalação foi removida automaticamente após o primeiro acesso ao painel.',
					'class' => 'success',
					'displayTime' => 8000,
					'showProgress' => 'bottom'
				),
				'botoes' => Array()
			));
		}
		
	} catch (Exception $e) {
		// ===== Em caso de erro, não interromper o carregamento do dashboard
		// Apenas log interno se necessário
	}
}

function dashboard_pagina_inicial(){
	global $_GESTOR;
	
	// ===== Remover página de instalação-sucesso se existir
	
	dashboard_remover_pagina_instalacao_sucesso();
	
	// ===== Inclusão de Componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Menu inicial
	
	dashboard_menu();
	
	// ===== Toasts
	
	dashboard_toast_atualizacoes();
}

function dashboard_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Start

function dashboard_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': dashboard_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		dashboard_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'inicio': dashboard_pagina_inicial(); break;
		}
		
		interface_finalizar();
	}
}

dashboard_start();

?>