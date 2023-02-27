<?php

// =========================== Configuração Inicial

$_GESTOR										=	Array();

$_GESTOR['bibliotecas']							=	Array('banco','gestor','modelo');

require_once('config.php');

// =========================== Definição do Caminho da Página

if(isset($_REQUEST['_gestor-caminho'])){
	$_GESTOR['caminho-total'] = $_REQUEST['_gestor-caminho'];
	$_GESTOR['caminho'] = explode('/',strtolower($_GESTOR['caminho-total']));
	
	if($_GESTOR['caminho'][count($_GESTOR['caminho'])-1] == NULL){
		array_pop($_GESTOR['caminho']);
	}
	
	$_GESTOR['caminho-extensao'] = pathinfo($_GESTOR['caminho-total'], PATHINFO_EXTENSION);
}

// =========================== Retornar arquivo estático caso exista e finalizar gestor

$_GESTOR['arquivo-estatico'] = false;

if((isset($_GESTOR['caminho-extensao']) ? $_GESTOR['caminho-extensao'] : null)){
	$_GESTOR['arquivo-estatico'] = Array(
		'alvo' => (isset($_GESTOR['caminho'][0]) ? $_GESTOR['caminho'][0] : null),
		'alvo2' => (isset($_GESTOR['caminho'][1]) ? $_GESTOR['caminho'][1] : null),
		'ext' => $_GESTOR['caminho-extensao'],
	);
}

if($_GESTOR['arquivo-estatico']){
	require_once($_GESTOR['controladores-path'].'arquivo-estatico/arquivo-estatico.php');
	exit;
}

// =========================== Controladores

if(isset($_GESTOR['caminho']))
switch($_GESTOR['caminho'][0]){
	case '_plataforma':
		require_once($_GESTOR['controladores-path'].'plataforma-cliente/plataforma-cliente.php'); exit;
	break;
	case '_gateways':
		require_once($_GESTOR['controladores-path'].'plataforma-gateways/plataforma-gateways.php'); exit;
	break;
	case '_app':
		require_once($_GESTOR['controladores-path'].'plataforma-app/plataforma-app.php'); exit;
	break;
}

// =========================== Funções de Montagem da Página

function gestor_pagina_menu($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	if(!isset($_GESTOR['usuario-token-id'])){
		return '';
	}
	
	// ===== Layout do menu
	
	$menu = gestor_componente(Array(
		'id' => 'menu-principal-sistema',
	));
	
	$cel_nome = 'icon'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'icon-2'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'categoria'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'simples'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'itemContCel'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'conteiner'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	// ===== Verificar quais módulos o usuário pode acessar
	
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
			'titulo',
			'plugin',
		))
		,
		"modulos",
		"WHERE nao_menu_principal IS NULL"
		." AND status='A'"
		." ORDER BY nome ASC"
	);
	
	$modulos_grupos = banco_select(Array(
		'tabela' => 'modulos_grupos',
		'campos' => Array(
			'id_modulos_grupos',
			'nome',
			'ordemMenu',
		),
		'extra' => 
			" ORDER BY ordemMenu ASC, nome ASC"
	));
	
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
	
	// ===== Montar o menu conforme permissão
	
	$dashboard = '';
	$grupos = Array();
	
	if($modulos)
	foreach($modulos as $modulo){
		// ===== Se o módulo tiver permissão de acesso incluir
		$modulo_perfil = false;
		
		if($modulo['id'] == 'dashboard'){
			$modulo_perfil = true;
		} else {
			if($usuarios_perfis_modulos)
			foreach($usuarios_perfis_modulos as $upm){
				if($upm['modulo'] == $modulo['id']){
					$modulo_perfil = true;
					break;
				}
			}
		}
		
		if(!$modulo_perfil){
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
		
		// ===== Montar ítem do menu do módulo
		
		$cel_nome = 'item';
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = modelo_var_troca($cel_aux,"#nome#",(existe($modulo['titulo']) ? $modulo['titulo'] : $modulo['nome']));
		$cel_aux = modelo_var_troca($cel_aux,"#class#",($modulo['id'] == $_GESTOR['modulo-id'] ? ' active':''));
		
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
		
		// ===== Caso seja dashboard incluir depois primeiro, senão colocar em ordem alfabética
		
		if($modulo['id'] == 'dashboard'){
			$dashboard = $cel_aux;
		} else {
			// ===== Incluir o item no módulo grupo.
			
			if(!isset($grupos[$modulo['id_modulos_grupos']])){
				$achouGrupo = false;
				$nomeGrupo = '';
				if($modulos_grupos)
				foreach($modulos_grupos as $modulo_grupo){
					if($modulo_grupo['id_modulos_grupos'] == $modulo['id_modulos_grupos']){
						$achouGrupo = true;
						$nomeGrupo = $modulo_grupo['nome'];
						break;
					}
				}
				
				if($achouGrupo){
					$grupos[$modulo['id_modulos_grupos']] = $cel['categoria'];
					
					$grupos[$modulo['id_modulos_grupos']] = modelo_var_troca($grupos[$modulo['id_modulos_grupos']],'#categoria-nome#',$nomeGrupo);
				} else {
					continue;
				}
			}
			
			$grupos[$modulo['id_modulos_grupos']] = modelo_var_in($grupos[$modulo['id_modulos_grupos']],'<!-- itemMenu -->',$cel_aux);
		}
	}
	
	// ===== Montar o conteiner do menu.
	
	$menuConteiner = $cel['conteiner'];
	
	// ===== Incluir dashboard no conteiner
	
	$cel_simples = $cel['simples'];
	$cel_simples = modelo_var_troca($cel_simples,"<!-- itemMenu -->",$dashboard);
	
	$cel_conteiner = $cel['itemContCel'];
	$cel_conteiner = modelo_var_troca($cel_conteiner,"#itemCont#",$cel_simples);
	
	$menuConteiner = modelo_var_in($menuConteiner,'<!-- itemContCel -->',$cel_conteiner);
	
	// ===== Incluir grupos no conteiner.
	
	$menuConteinerSemPrioridade = '';
	
	if($modulos_grupos)
	foreach($modulos_grupos as $modulo_grupo){
		if(isset($grupos[$modulo_grupo['id_modulos_grupos']])){
			$cel_conteiner = $cel['itemContCel'];
			$cel_conteiner = modelo_var_troca($cel_conteiner,"#itemCont#",$grupos[$modulo_grupo['id_modulos_grupos']]);
			
			if($modulo_grupo['ordemMenu']){
				$menuConteiner = modelo_var_in($menuConteiner,'<!-- itemContCel -->',$cel_conteiner);
			} else {
				$menuConteinerSemPrioridade .= $cel_conteiner;
			}
		}
	}
	
	if(existe($menuConteinerSemPrioridade)){
		$menuConteiner = modelo_var_in($menuConteiner,'<!-- itemContCel -->',$menuConteinerSemPrioridade);
	}
	
	// ===== Incluir sair no conteiner
	
	$cel_nome = 'item';
	$cel_aux = $cel[$cel_nome];
	
	$cel_aux = modelo_var_troca($cel_aux,"#nome#",gestor_variaveis(Array('id' => 'logout-label','modulo' => 'dashboard')));
	$cel_aux = modelo_var_troca($cel_aux,"#class#",'');
	
	$cel_nome_icon = 'icon';
	$cel_icon = $cel[$cel_nome_icon];
	
	$cel_icon = modelo_var_troca($cel_icon,"#icon#",'sign out alternate');
	
	$cel_aux = modelo_var_troca($cel_aux,"<!-- icon -->",$cel_icon);
	
	$cel_aux = modelo_var_troca_tudo($cel_aux,"#link#",$_GESTOR['url-raiz'].'signout/');
	
	// ===== Incluir sair no conteiner
	
	$cel_simples = $cel['simples'];
	$cel_simples = modelo_var_troca($cel_simples,"<!-- itemMenu -->",$cel_aux);
	
	$cel_conteiner = $cel['itemContCel'];
	$cel_conteiner = modelo_var_troca($cel_conteiner,"#itemCont#",$cel_simples);
	
	$menuConteiner = modelo_var_in($menuConteiner,'<!-- itemContCel -->',$cel_conteiner);
	
	// ===== Remover celulas inúteis
	
	$menuConteiner = modelo_var_troca($menuConteiner,'<!-- itemContCel -->','');
	$menuConteiner = modelo_var_troca($menuConteiner,'<!-- itemMenu -->','');
	
	// ===== Retornar o conteiner.
	
	return $menuConteiner;
}

function gestor_pagina_variaveis_modulos($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulosExtra - Conjunto - Opcional - Conjunto de móulos extras para ler variáveis globais quando necessário.
	
	// ===== 
	
	if(isset($modulosExtra)){
		foreach($modulosExtra as $modulo){
			$_GESTOR['paginas-variaveis'][$modulo] = true;
		}
	}
	
}

function gestor_pagina_variaveis($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// layout - String - Obrigatório - Layout principal onde a página será incluída.
	
	// ===== 
	
	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	
	// ===== Variáveis do layout
	
	$layout = modelo_var_troca($layout,'<!-- pagina#titulo -->',($_GESTOR['pagina#titulo'] ? '<title>'.(isset($_GESTOR['pagina#titulo-extra']) ? $_GESTOR['pagina#titulo-extra'] : '').$_GESTOR['pagina#titulo'].'</title>' : ''));
	
	// ===== Página fundir layout + página
	
	$_GESTOR['pagina'] = modelo_var_troca($layout,$open.'pagina#corpo'.$close,$_GESTOR['pagina']);
	
	// ===== Página variáveis operações
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	// ===== Busca por widgets na página.
	
	$pattern = "/".preg_quote($open)."widgets#(.+?)".preg_quote($close)."/i";
	preg_match_all($pattern, $_GESTOR['pagina'], $matchesWidgets);
	
	if($matchesWidgets){
		// ===== Incluir a biblioteca dos widgets e disparar a função de iniciação dos mesmos.
		
		gestor_incluir_biblioteca('widgets');
		
		// ===== Varrer todos os matchs e trocar os marcadores por seus widgets.
		
		foreach($matchesWidgets[1] as $match){
			$widget = widgets_get(Array(
				'id' => $match,
			));
			
			if(existe($widget)){
				$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open."widgets#".$match.$close,$widget);
			}
		}
	}
	
	// ===== Página variáveis trocar
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#menu'.$close,gestor_pagina_menu());
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-raiz'.$close,$_GESTOR['url-raiz']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-full-http'.$close,$_GESTOR['url-full-http']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-caminho'.$close,$caminho);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#titulo'.$close,$_GESTOR['pagina#titulo']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#contato-url'.$close,$_GESTOR['pagina#contato-url']);
	
	// ===== Dados do usuário
	
	$usuario = gestor_usuario();
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'usuario#nome'.$close,$usuario['nome']);
	
	if(isset($_GESTOR['modulo-id'])) $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#modulo-id'.$close,$_GESTOR['modulo-id']);
	if(isset($_GESTOR['modulo-registro-id'])) $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#registro-id'.$close,$_GESTOR['modulo-registro-id']);
	
	if(isset($_GESTOR['modulo-id'])){
		$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
		preg_match_all($pattern, $_GESTOR['pagina'], $matches);
		
		if($matches)
		foreach($matches[1] as $match){
			$valor = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $match));
			
			if(existe($valor)){
				$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$match.$close,$valor);
			}
		}
	}
	
	// ===== Módulos extras que devem ser lidos e colocados as variáveis nas páginas.
	
	if(isset($_GESTOR['paginas-variaveis'])){
		$modulosExtra = $_GESTOR['paginas-variaveis'];
		
		foreach($modulosExtra as $modulo => $valor){
			$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
			preg_match_all($pattern, $_GESTOR['pagina'], $matches);
			
			if($matches)
			foreach($matches[1] as $match){
				$valor = gestor_variaveis(Array('modulo' => $modulo,'id' => $match));
				
				if(existe($valor)){
					$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$match.$close,$valor);
				}
			}
		}
	}
}

function gestor_pagina_css(){
	global $_GESTOR;
	
	$css_global = '';
	$css_padrao[] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].$_GESTOR['fomantic-ui-folder'].'/dist/semantic.min.css" />';
	
	if(!isset($_GESTOR['css'])) $_GESTOR['css'] = Array();
	if(!isset($_GESTOR['css-fim'])) $_GESTOR['css-fim'] = Array();
	
	$csss = array_merge($css_padrao,$_GESTOR['css'],$_GESTOR['css-fim']);
	
	if($csss)
	foreach($csss as $css){
		if(existe($css_global)){
			$css_global .= "	" . $css . "\n";
		} else {
			$css_global .= $css . "\n";
		}
	}
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- pagina#css -->',$css_global);
}

function gestor_pagina_css_incluir($css = false){
	global $_GESTOR;
	
	if(!$css){
		$_GESTOR['css-fim'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/css.css?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'">';
	} else {
		$_GESTOR['javascript-fim'][] = $css;
	}
}

function gestor_pagina_javascript(){
	global $_GESTOR;
	
	// ===== Inclusão de bibliotecas javascript
	
	$js_global_includes = '';
	$js_padrao[] = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>'; // jQuery
	$js_padrao[] = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['fomantic-ui-folder'].'/dist/semantic.min.js"></script>'; // Semantic-UI
	$js_padrao[] = '<script src="'.$_GESTOR['url-raiz'].'global/global.js?v='.$_GESTOR['versao'].'"></script>'; // Global JS
	
	if(!isset($_GESTOR['javascript'])) $_GESTOR['javascript'] = Array();
	if(!isset($_GESTOR['javascript-fim'])) $_GESTOR['javascript-fim'] = Array();
	
	$jss = array_merge($js_padrao,$_GESTOR['javascript'],$_GESTOR['javascript-fim']);
	
	if($jss)
	foreach($jss as $js){
		$js_global_includes .= "	" . $js . "\n";
	}
	
	// ===== Inclusão de variáveis javascript
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	$variaveis_js = Array(
		'raiz' => $_GESTOR['url-raiz'],
		'moduloId' => (isset($_GESTOR['modulo-id']) ? $_GESTOR['modulo-id'] : false ),
		'moduloOpcao' => (isset($_GESTOR['opcao']) ? $_GESTOR['opcao'] : false ),
		'moduloCaminho' => $caminho,
	);
	
	if($_GESTOR['paginaIframe']) $variaveis_js['paginaIframe'] = true;
	
	$js_global_vars = '<script>
		var gestor = '.json_encode((isset($_GESTOR['javascript-vars']) ? array_merge($variaveis_js, $_GESTOR['javascript-vars']):$variaveis_js)).';
	</script>'."\n";
	
	// ===== Inclusão na página
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- pagina#js -->',$js_global_vars.$js_global_includes);
}

function gestor_pagina_javascript_incluir($js = false,$id = false){
	global $_GESTOR;
	
	if(!$js){
		if(isset($_GESTOR['modulo#'.$_GESTOR['modulo-id']]['plugin'])){
			$_GESTOR['javascript-fim'][] = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['plugin'].'/'.$_GESTOR['modulo-id'].'/js.js?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'"></script>';
		} else {
			$_GESTOR['javascript-fim'][] = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/js.js?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'"></script>';
		}
	} else {
		switch($js){
			case 'biblioteca':
				$js = '<script src="'.$_GESTOR['url-raiz'].'interface/'.$id.'.js?v='.$_GESTOR['biblioteca-'.$id]['versao'].'"></script>';
			break;
		}
		
		$_GESTOR['javascript-fim'][] = $js;
	}
}

function gestor_pagina_ultimas_operacoes(){
	global $_GESTOR;
	
	$_GESTOR['pagina'] = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $_GESTOR['pagina']);
}

// =========================== Funções de Sessões e Cookies

function gestor_sessao_iniciar(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(!isset($_COOKIE[$_CONFIG['session-authname']])){
		$sessionId = md5(uniqid(rand(), true));
		
		setcookie($_CONFIG['session-authname'], $sessionId, [
			'expires' => time() + $_CONFIG['session-lifetime'],
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		$_GESTOR['session-id'] = $sessionId;
	} else {
		$_GESTOR['session-id'] = $_COOKIE[$_CONFIG['session-authname']];
	}
}

function gestor_sessao_id(){
	global $_GESTOR;
	global $_CONFIG;
	
	$sessoes = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_sessoes',
		))
		,
		"sessoes",
		"WHERE id='".banco_escape_field($_GESTOR['session-id'])."'"
	);
	
	if($sessoes){
		$id_sessoes = $sessoes[0]['id_sessoes'];
		
		// ===== Caso não tenha sido acessado ainda, atualizar o tempo de acesso.
		
		if(!isset($_GESTOR['session-accessed'])){
			banco_update
			(
				"acesso='".time()."'",
				"sessoes",
				"WHERE id_sessoes='".$id_sessoes."'"
			);
			
			$_GESTOR['session-accessed'] = true;
		}
	} else {
		// ===== Senão existir, criar nova sessão no banco.
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id"; $campo_valor = banco_escape_field($_GESTOR['session-id']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "acesso"; $campo_valor = time(); 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"sessoes"
		);
		
		$id_sessoes = banco_last_id();
	}
	
	// =====  Remover sessões antigas aleatoriamente para não fazer isso toda vez.
	
	if(!isset($_GESTOR['session-accessed-clean'])){
		if(rand(0,50) == 0){
			$res = banco_query(
				"DELETE sess,sess_v 
				FROM sessoes AS sess 
					LEFT JOIN sessoes_variaveis AS sess_v 
						ON sess.id_sessoes=sess_v.id_sessoes 
				WHERE sess.acesso + ".$_CONFIG['session-lifetime']." < ".time()
				);
		}
		
		$_GESTOR['session-accessed-clean'] = true;
	}
	
	return $id_sessoes;
}

function gestor_sessao_del(){
	global $_CONFIG;
	
	$id_sessoes = gestor_sessao_id();
	
	$sessoes_variaveis = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_sessoes_variaveis',
		))
		,
		"sessoes_variaveis",
		"WHERE id_sessoes='".$id_sessoes."'"
	);
	
	if($sessoes_variaveis){
		banco_delete
		(
			"sessoes_variaveis",
			"WHERE id_sessoes='".$id_sessoes."'"
		);
	}
	
	banco_delete
	(
		"sessoes",
		"WHERE id_sessoes='".$id_sessoes."'"
	);
	
	setcookie($_CONFIG['session-authname'], "", [
		'expires' => time() - 3600,
		'path' => '/',
		'domain' => $_SERVER['SERVER_NAME'],
		'secure' => true,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
}

function gestor_sessao_variavel($variavel,$valor = NULL){
	global $_GESTOR;
	
	$id_sessoes = gestor_sessao_id();
	
	if(isset($valor)){
		$sessoes_variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"sessoes_variaveis",
			"WHERE id_sessoes='".$id_sessoes."'"
			." AND variavel='".$variavel."'"
		);
		
		if($sessoes_variaveis){
			banco_update
			(
				"valor='".addslashes(json_encode($valor))."'",
				"sessoes_variaveis",
				"WHERE id_sessoes='".$id_sessoes."'"
				." AND variavel='".$variavel."'"
			);
		} else {
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_sessoes"; $campo_valor = $id_sessoes; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "variavel"; $campo_valor = $variavel; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "valor"; $campo_valor = addslashes(json_encode($valor)); 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"sessoes_variaveis"
			);
		}
	} else {
		$sessoes_variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"sessoes_variaveis",
			"WHERE id_sessoes='".$id_sessoes."'"
			." AND variavel='".$variavel."'"
		);
		
		if($sessoes_variaveis){
			return ($sessoes_variaveis[0]['valor'] ? json_decode($sessoes_variaveis[0]['valor'],true) : '');
		} else {
			return '';
		}
	}
}

function gestor_sessao_variavel_del($variavel){
	global $_GESTOR;
	
	$id_sessoes = gestor_sessao_id();
	
	$sessoes_variaveis = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_sessoes_variaveis',
		))
		,
		"sessoes_variaveis",
		"WHERE id_sessoes='".$id_sessoes."'"
		." AND variavel='".$variavel."'"
	);
	
	if($sessoes_variaveis){
		banco_delete
		(
			"sessoes_variaveis",
			"WHERE id_sessoes_variaveis='".$sessoes_variaveis[0]['id_sessoes_variaveis']."'"
		);
	}
}

// =========================== Funções de Autenticação de Usuário

function gestor_permissao_validar_jwt($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - Token JWT de verificação.
	// chavePrivada - String - Obrigatório - Chave privada para conferir a assinatura do token.
	// chavePrivadaSenha - String - Obrigatório - Senha da chave privada.
	
	// ===== 
	
	if(isset($chavePrivada) && isset($chavePrivadaSenha)){
		// ===== Quebra o token em header, payload e signature
		
		$part = explode(".",$token);
		
		if(gettype($part) != 'array'){
			return false;
		}
		
		$header = $part[0];
		$payload = $part[1];
		$signature = $part[2];

		$encodedData = $signature;
		
		// ===== Abrir chave privada com a senha
		
		$resPrivateKey = openssl_get_privatekey($chavePrivada,$chavePrivadaSenha);
		
		// ===== Decode base64 to reaveal dots (Dots are used in JWT syntaxe)

		$encodedData = base64_decode($encodedData);

		// ===== Decrypt data in parts if necessary. Using dots as split separator.

		$rawEncodedData = $encodedData;

		$countCrypt = 0;
		$partialDecodedData = '';
		$decodedData = '';
		$split2 = explode('.',$rawEncodedData);
		foreach($split2 as $part2){
			$part2 = base64_decode($part2);
			
			openssl_private_decrypt($part2, $partialDecodedData, $resPrivateKey);
			$decodedData .= $partialDecodedData;
		}

		// ===== Validate JWT

		if($header.".".$payload === $decodedData){
			$payload = base64_decode($payload);
			$payload = json_decode($payload,true);
			
			// ===== Verifica se as variáveis existem, senão foi formatado errado e não deve aceitar.
			
			if(!isset($payload['exp']) || !isset($payload['sub'])){
				return false;
			}
			
			$expiracao_ok = false;
			
			// ===== Se a expiração for igual a 0 é sessão, senão tem que comparar tempo.
			
			if((int)$payload['exp'] === 0){
				$expiracao_ok = true;
			} else {
				// ===== Se o tempo de expiração do token for menor que o tempo agora, é porque este token está vencido.
				
				if((int)$payload['exp'] > time()){
					$expiracao_ok = true;
				}
			}
			
			if($expiracao_ok){
				// Se tudo estiver válido, retorna o pubID do token.
				
				return $payload['sub'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function gestor_permissao_token(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Verifica se cookie no navegador está ativo.
	
	if(!isset($_COOKIE[$_CONFIG['cookie-verify']])){
		// ===== Criar um cookie de verificação
		
		$cookieId = md5(uniqid(rand(), true));
		
		setcookie($_CONFIG['cookie-verify'], $cookieId, [
			'expires' => '0',
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		// ===== Redirecionar o usuário afim de conferir se está ativo numa nova conexão com a URL e queryString caso o mesmo não tenha sido logado de outra forma.
		
		if(!isset($_COOKIE[$_CONFIG['cookie-authname']])){
			$url = urlencode($_GESTOR['caminho-total']);
			$queryString = urlencode(gestor_querystring());
			
			header("Location: " . $_GESTOR['url-raiz'] . '_gestor-cookie-verify/'.$cookieId.'/?url='.$url.(existe($queryString) ? '&queryString='.$queryString : ''));
			exit;
		}
	}
	
	// ===== Verifica se existe o cookie de autenticação gerado no login com sucesso.
	
	if(!isset($_COOKIE[$_CONFIG['cookie-authname']])){
		return false;
	}
	
	$JWTToken = $_COOKIE[$_CONFIG['cookie-authname']];
	
	if(!existe($JWTToken)){
		return false;
	}
	
	// ===== Abrir chave privada e a senha da chave
	
	$keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
	
	$fp = fopen($keyPrivatePath,"r");
	$keyPrivateString = fread($fp,8192);
	fclose($fp);
	
	$chavePrivadaSenha = $_CONFIG['openssl-password'];
	
	// ===== Verificar se o JWT é válido.
	
	$tokenPubId = gestor_permissao_validar_jwt(Array(
		'token' => $JWTToken,
		'chavePrivada' => $keyPrivateString,
		'chavePrivadaSenha' => $chavePrivadaSenha,
	));
	
	if($tokenPubId){
		// ===== Verifica se o token está ativo. Senão estiver invalidar o cookie.
		
		$usuarios_tokens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios_tokens',
				'id_usuarios',
				'pubIDValidation',
				'data_criacao',
				'expiration',
			))
			,
			"usuarios_tokens",
			"WHERE pubID='".$tokenPubId."'"
		);
		
		if($usuarios_tokens){
			// ===== Limpeza dos tokens mais antigos no banco de dados.
			
			$invalidar_token = false;
			
			if(!existe(gestor_sessao_variavel('usuario-tokens-limpeza'))){
				// ===== Deletar todos os tokens de sessão (expiration == 0) quando as datas de criação mais o tempo de limpeza dos mesmos forem menor que o tempo agora.
				
				banco_delete
				(
					"usuarios_tokens",
					"WHERE expiration=0"
					." AND TIMESTAMPADD(SECOND,".$_CONFIG['session-garbagetime'].",data_criacao) < NOW()"
				);
				
				// ===== Deletar todos os tokens persistentes (expiration != 0) quando o tempo de expiração mais o tempo de vida dos tokens forem menor que o tempo agora.
				
				banco_delete
				(
					"usuarios_tokens",
					"WHERE expiration!=0"
					." AND expiration < ".time()
				);
				
				gestor_sessao_variavel('usuario-tokens-limpeza',true);
				
				// ===== Verificar se um dos tokens excluídos é o token atual. Se sim, invalidar token.
				
				$usuarios_tokens_verificar = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios_tokens',
					))
					,
					"usuarios_tokens",
					"WHERE pubID='".$tokenPubId."'"
				);
				
				if(!$usuarios_tokens_verificar){
					$invalidar_token = true;
				}
			}
			
			if(!$invalidar_token){
				// ===== Verificar se o token não expirou.
				
				$expiration = $usuarios_tokens[0]['expiration'];
				
				$expiracao_ok = false;
				$token_sessao = false;
				
				// ===== Se a expiração for igual a 0 é sessão, senão tem que comparar tempo de expiração.
				
				if((int)$expiration === 0){
					$expiracao_ok = true;
					$token_sessao = true;
					
					// ===== Caso o tempo de criação deste token for maior que o tempo de limpeza, deve ser deletado e não aceito.
					
					$data_criacao = $usuarios_tokens[0]['data_criacao'];
					
					$time_criacao = strtotime($data_criacao);
					
					if($time_criacao + $_CONFIG['session-garbagetime'] < time()){
						$expiracao_ok = false;
						
						$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
						
						banco_delete
						(
							"usuarios_tokens",
							"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
						);
					}
				} else {
					// ===== Se o tempo de expiração do token for maior que o tempo agora, é porque este token está ativo. Senão está vencido e deve ser deletado.
					
					if((int)$expiration > time()){
						$expiracao_ok = true;
					} else {
						$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
						
						banco_delete
						(
							"usuarios_tokens",
							"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
						);
					}
				}
				
				if($expiracao_ok){
					// ===== Validar o token com o hash de validação para evitar geração de token por hacker caso ocorra roubo da tabela 'usuarios_tokens'.
					
					$bd_hash = $usuarios_tokens[0]['pubIDValidation'];
					$token_hash = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
					
					if($bd_hash === $token_hash){
						$data_criacao = $usuarios_tokens[0]['data_criacao'];
						$id_usuarios = $usuarios_tokens[0]['id_usuarios'];
						
						if(!$token_sessao){
							// ===== Verificar se precisa renovar JWTToken, se sim, apagar token anterior e criar um novo no lugar.
							
							$time_criacao = strtotime($data_criacao);
							
							if($time_criacao + $_CONFIG['cookie-renewtime'] < time()){
								gestor_incluir_biblioteca('usuario');
								
								usuario_gerar_token_autorizacao(Array(
									'id_usuarios' => $id_usuarios,
								));
								
								$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
								
								banco_delete
								(
									"usuarios_tokens",
									"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
								);
							}
						}
						
						$_GESTOR['usuario-id'] = $id_usuarios;
						$_GESTOR['usuario-token-id'] = $tokenPubId;
						
						return true;
					}
				}
			}
		}
	}
	
	// ===== Caso não valide, deletar cookie e retornar 'false'.
	
	setcookie($_CONFIG['cookie-authname'], "", [
		'expires' => time() - 3600,
		'path' => '/',
		'domain' => $_SERVER['SERVER_NAME'],
		'secure' => true,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	
	unset($_COOKIE[$_CONFIG['cookie-authname']]);
	
	return false;
}

function gestor_permissao_fingerprint(){
	global $_GESTOR;
	
	// =====
	
	if(existe(gestor_sessao_variavel('browser-fingerprint'))){
		return true;
	} else {
		return false;
	}
}

function gestor_permissao_modulo(){
	global $_GESTOR;
	
	$usuario = gestor_usuario();
	$modulo = $_GESTOR['modulo'];
	
	if(!existe($modulo)){
		return true;
	}
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
		))
		,
		"modulos",
		"WHERE id='".$modulo."'"
		." AND status='A'"
	);
	
	if($modulos){
		// ===== Verificar se o usuário é filho de um host ou não.
		
		if(existe($usuario['id_hosts'])){
			// ===== Verificar se o usuário tem um perfil de gestor ativo.
			
			if(existe($usuario['gestor_perfil'])){
				$gestor_perfil = $usuario['gestor_perfil'];
				
				// ===== Verificar se o módulo alvo tem permissão no perfil.
				
				$usuarios_gestores_perfis_modulos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios_gestores_perfis_modulos',
					))
					,
					"usuarios_gestores_perfis_modulos",
					"WHERE perfil='".$gestor_perfil."'"
					." AND modulo='".$modulo."'"
					." AND id_hosts='".$usuario['id_hosts']."'"
				);
				
				// ===== Caso tenha permissão retornar true.
				
				if($usuarios_gestores_perfis_modulos){
					return true;
				}
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
						'id_usuarios_perfis_modulos',
					))
					,
					"usuarios_perfis_modulos",
					"WHERE perfil='".$perfil."'"
					." AND modulo='".$modulo."'"
				);
				
				// ===== Caso tenha permissão retornar true.
				
				if($usuarios_perfis_modulos){
					return true;
				}
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
					'id_usuarios_perfis_modulos',
				))
				,
				"usuarios_perfis_modulos",
				"WHERE perfil='".$perfil."'"
				." AND modulo='".$modulo."'"
			);
			
			// ===== Caso tenha permissão retornar true.
			
			if($usuarios_perfis_modulos){
				return true;
			}
		}
	}
	
	gestor_incluir_biblioteca('interface');
	
	interface_alerta(Array(
		'redirect' => true,
		'msg' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-without-permission'))
	));
	
	return false;
}

function gestor_permissao(){
	global $_GESTOR;
	
	if(!gestor_permissao_token()){
		if($_GESTOR['ajax']){
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'ajax' => $_GESTOR['ajax'],
			));
		} else {
			$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
			$caminho = rtrim($caminho,'/').'/';
			
			gestor_sessao_variavel("redirecionar-local",$caminho);
			
			gestor_roteador_erro(Array(
				'codigo' => 401,
			));
		}
	}
	
	/* if(!gestor_permissao_fingerprint()){
		if($_GESTOR['ajax']){
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'ajax' => $_GESTOR['ajax'],
			));
		} else {
			$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
			$caminho = rtrim($caminho,'/').'/';
			
			gestor_sessao_variavel("redirecionar-local",$caminho);
			
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'redirect' => 'validate-user/',
				'querystring' => true,
			));
		}
	} */
	
	if(!gestor_permissao_modulo()){
		if($_GESTOR['ajax']){
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'ajax' => $_GESTOR['ajax'],
				'redirect' => 'dashboard/',
			));
		} else {
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'redirect' => 'dashboard/',
			));
		}
	}
}

function gestor_usuario(){
	global $_GESTOR;
	
	if(isset($_GESTOR['usuario-id'])){
		if(!isset($_GESTOR['usuario'])){
			$usuarios = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_hosts',
					'id_usuarios',
					'id_usuarios_perfis',
					'id',
					'usuario',
					'nome',
					'email',
					'gestor',
					'gestor_perfil',
				))
				,
				"usuarios",
				"WHERE id_usuarios='".$_GESTOR['usuario-id']."'"
			);
			
			$_GESTOR['usuario'] = $usuarios[0];
		}
		
		return $_GESTOR['usuario'];
	} else {
		return Array(
			'id_hosts' => '',
			'id_usuarios' => '0',
			'id_usuarios_perfis' => '0',
			'id' => '_anonimo',
			'gestor' => '',
			'gestor_perfil' => '',
			'usuario' => '_anonimo',
			'nome' => 'Anônimo',
		);
	}
}

function gestor_acesso($operacao = false,$modulo = false){
	global $_GESTOR;
	
	// ===== Parâmetros
	
	// operacao - String - Obrigatório - operação do módulo atual.
	// modulo - String - Opcional - foçar um módulo diferente do atual.
	
	// ===== 
	
	if(!$operacao){
		return false;
	}
	
	$usuario = gestor_usuario();
	
	if(!$modulo){
		$modulo = $_GESTOR['modulo'];
	}
	
	if(!existe($modulo)){
		return false;
	}
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
		))
		,
		"modulos",
		"WHERE id='".$modulo."'"
		." AND status='A'"
	);
	
	if($modulos){
		$id_modulos = $modulos[0]['id_modulos'];
		
		$modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos_operacoes',
				'id',
			))
			,
			"modulos_operacoes",
			"WHERE operacao='".$operacao."'"
			." AND id_modulos='".$id_modulos."'"
			." AND status='A'"
		);
		
		if($modulos_operacoes){
			$operacao_id = $modulos_operacoes[0]['id'];
			
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
			
			$usuarios_perfis_modulos_operacoes = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuarios_perfis_modulos_operacoes',
				))
				,
				"usuarios_perfis_modulos_operacoes",
				"WHERE operacao='".$operacao_id."'"
				." AND perfil='".$perfil."'"
			);
			
			if($usuarios_perfis_modulos_operacoes){
				return true;
			}
		}
	}
	
	return false;
}

// =========================== Funções do Host

function gestor_host_configuracao(){
	global $_GESTOR;
	
	if(!existe(gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']))){
		
		$usuario = gestor_usuario();
		$host_verificacao = Array();
		
		// ===== Verificar se usuário é admin do host
		
		$usuarios_gestores_hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_hosts',
				'privilegios_admin',
			))
			,
			"usuarios_gestores_hosts",
			"WHERE id_usuarios='".$usuario['id_usuarios']."'"
		);
		
		if($usuarios_gestores_hosts){
			$id_hosts = $usuarios_gestores_hosts[0]['id_hosts'];
			
			// ===== Vincular id_hosts ao usuário.
			
			$host_verificacao['id_hosts'] = $id_hosts;
			
			// ===== Verificar se este administrador do host tem privilégios para atualizar o host.
			
			$privilegios_admin = false;
			
			if($usuarios_gestores_hosts[0]['privilegios_admin']){
				$privilegios_admin = true;
			}
			
			// ===== Verificar se o host já foi instalado, configurado e/ou é necessário atualizar caso o usuário tenha privilégio para isso.
			
			if($privilegios_admin){
				$hosts = banco_select_name
				(
					banco_campos_virgulas(Array(
						'instalado',
						'configurado',
						'atualizar',
					))
					,
					"hosts",
					"WHERE id_hosts='".$id_hosts."'"
				);
				
				if($hosts){
					// ===== Buscar o ID do perfil do usuário.
					
					$usuarios_perfis = banco_select(Array(
						'unico' => true,
						'tabela' => 'usuarios_perfis',
						'campos' => Array(
							'id',
						),
						'extra' => 
							"WHERE id_usuarios_perfis='".$usuario['id_usuarios_perfis']."'"
					));
					
					// ===== Verificar se o módulo de configuração do host faz parte do usuário
					
					$usuarios_perfis_modulos = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_usuarios_perfis_modulos',
						))
						,
						"usuarios_perfis_modulos",
						"WHERE perfil='".$usuarios_perfis['id']."'"
						." AND modulo='".$_GESTOR['host-configuracao-id-modulo']."'"
					);
					
					if($usuarios_perfis_modulos){
						// ===== Incluir o id do host e dar permissão de admin do host ao usuário
						
						$host_verificacao['privilegios_admin'] = true;
						
						// ===== Caso o host ainda não tenha sido instalado ou configurado, ativar a flag instalar para desencadear o processo de instalação do host ou configurar para configurar o host.
						
						if(!$hosts[0]['instalado']){
							$host_verificacao['instalar'] = true;
						}
						
						if(!$hosts[0]['configurado']){
							$host_verificacao['configurar'] = true;
						}
						
						if($hosts[0]['atualizar']){
							$host_verificacao['atualizar'] = true;
						}
					}
				}
			}
		}
		
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
	}
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Caso esta conta seja de um host, criar o host-id
	
	if(isset($host_verificacao['id_hosts'])){
		$_GESTOR['host-id'] = $host_verificacao['id_hosts'];
	}
	
	// ===== Caso seja necessário fazer a instalação, redirecionar sempre o usuário para a página de instalação
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	switch($caminho){
		case 'host-install/':
		case 'host-config/':
		case 'host-update/':
		case 'host-config-forgot-password/':
		case 'host-config-forgot-password-confirmation/':
		case 'host-config-redefine-password/':
		case 'host-config-redefine-password-confirmation/':
		case 'signout/':
		case 'email-confirmation/':
			// Não fazer nada
		break;
		default:
			// ===== Redirecionar em ordem de prioridade para os seguintes locais: instalação, configuração e forçar atualização
			
			// ===== Instalação
			
			if(isset($host_verificacao['instalar'])){
				$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
				$caminho = rtrim($caminho,'/').'/';
				
				gestor_sessao_variavel("redirecionar-local",$caminho);
				
				gestor_redirecionar('host-install/');
			}
			
			// ===== Configuração
			
			if(isset($host_verificacao['configurar'])){
				$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
				$caminho = rtrim($caminho,'/').'/';
				
				gestor_sessao_variavel("redirecionar-local",$caminho);
				
				gestor_redirecionar('host-config/');
			}
			
			// ===== Atualização
			
			if(isset($host_verificacao['atualizar'])){
				$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
				$caminho = rtrim($caminho,'/').'/';
				
				gestor_sessao_variavel("redirecionar-local",$caminho);
				
				gestor_redirecionar('host-update/');
			}
	}
}

function gestor_host_variaveis($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulo - String - Obrigatório - Módulo do sistema do valor.
	// id - String - Opcional - Identificador único do valor.
	// global - Bool - Opcional - Se definido a variável será de todo o host, senão será variável do usuário.
	// conjunto - Bool - Opcional - Se definido retornar todos os valores do módulo.
	// padrao - String - Opcional - Só funciona se conjunto for definido. Se informado filtrar com esse valor que contêm nos ids das variáveis.
	// alterar - Bool - Opcional - Para criar ou modificar uma variável.
		// valor - String - Opcional - Valor da variável.
		// tipo - Bool - Opcional - Tipo da variável.
		
	// ===== 
	
	// ===== Procedimentos de inicialização
	
	if(!isset($_GESTOR['host-variaveis'])){
		$_GESTOR['host-variaveis'] = Array();
	}
	
	// ===== Caso queira alterar o valor, senão devolver o valor
	
	$usuario = gestor_usuario();
	
	if(isset($alterar)){
		if(!isset($tipo)){ $tipo = 'string'; }
		if(!isset($valor)){ $valor = ''; }
		
		$hosts_variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
			))
			,
			"hosts_variaveis",
			"WHERE modulo='".$modulo."'"
			." AND id='".$id."'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
			.(isset($global) ? '' : " AND id_usuarios='".$usuario['id_usuarios']."'")
		);
		
		if($hosts_variaveis){
			switch($tipo){
				case 'bool':
					banco_update
					(
						"valor=".($valor ? '1' : 'NULL'),
						"hosts_variaveis",
						"WHERE modulo='".$modulo."'"
						." AND id='".$id."'"
						." AND id_hosts='".$_GESTOR['host-id']."'"
						.(isset($global) ? '' : " AND id_usuarios='".$usuario['id_usuarios']."'")
					);
				break;
				default:
					banco_update
					(
						"valor='".$valor."'",
						"hosts_variaveis",
						"WHERE modulo='".$modulo."'"
						." AND id='".$id."'"
						." AND id_hosts='".$_GESTOR['host-id']."'"
						.(isset($global) ? '' : " AND id_usuarios='".$usuario['id_usuarios']."'")
					);
			}
		} else {
			switch($tipo){
				case 'bool':
					$valor_sem_aspas_simples = true;
				break;
				default:
					$valor_sem_aspas_simples = false;
			}
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "modulo"; $campo_valor = $modulo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "valor"; $campo_valor = $valor; 		$campos[] = Array($campo_nome,$campo_valor,$valor_sem_aspas_simples);
			$campo_nome = "tipo"; $campo_valor = $tipo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			if(!isset($global)){$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			banco_insert_name
			(
				$campos,
				"hosts_variaveis"
			);
		}
	} else {
		// ===== Buscar no banco de dados caso não tenha sido ainda lido na sessão.
		
		if(!isset($_GESTOR['host-variaveis'][$modulo])){
			$hosts_variaveis = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id',
					'valor',
				))
				,
				"hosts_variaveis",
				"WHERE modulo='".$modulo."'"
				." AND id_hosts='".$_GESTOR['host-id']."'"
				.(isset($global) ? '' : " AND id_usuarios='".$usuario['id_usuarios']."'")
			);
			
			if($hosts_variaveis){
				foreach($hosts_variaveis as $hv){
					$_GESTOR['host-variaveis'][$modulo][$hv['id']] = $hv['valor'];
				}
			}
		}
		
		// ===== Se conjunto definido filtrar se existir padrao e retornar o conjunto, senão retornar valor pontual.
		
		if(isset($conjunto)){
			if(isset($_GESTOR['host-variaveis'][$modulo])){
				if(isset($padrao)){
					$hosts_variaveis_aux = $_GESTOR['host-variaveis'][$modulo];
					$hosts_variaveis = Array();
					
					foreach($hosts_variaveis_aux as $id_aux => $hv_aux){
						if(preg_match('/'.preg_quote($padrao).'/i', $id_aux) > 0){
							$hosts_variaveis[$id_aux] = $hv_aux;
						}
					}
					
					return $hosts_variaveis;
				} else {
					return $_GESTOR['host-variaveis'][$modulo];
				}
			} else {
				return Array();
			}
		} else {
			return (isset($_GESTOR['host-variaveis'][$modulo][$id]) ? $_GESTOR['host-variaveis'][$modulo][$id] : '' );
		}
	}
}

// =========================== Funções de Acesso

function gestor_roteador_301_ou_404($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// caminho - String - Obrigatório - Caminho para verificar se tem alguma página 301, senão gera erro 404.
	
	// ===== 
	
	if(isset($caminho)){
		$paginas_301 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_paginas',
			))
			,
			"paginas_301",
			"WHERE caminho='".banco_escape_field($caminho)."'"
		);
		
		if($paginas_301){
			$paginas = banco_select_name
			(
				banco_campos_virgulas(Array(
					'caminho',
				))
				,
				"paginas",
				"WHERE id_paginas='".$paginas_301[0]['id_paginas']."'"
				." AND status='A'"
			);
			
			if($paginas){
				gestor_roteador_erro(Array(
					'codigo' => 301,
					'redirect' => $paginas[0]['caminho'],
				));
			}
		}
	}
	
	gestor_roteador_erro(Array(
		'codigo' => 404,
	));
}

function gestor_roteador_erro($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// codigo - Int - Obrigatório - Código do erro HTTP.
	// ajax - Bool - Opcional - Indicar se é uma conexão AJAX ou normal.
	// redirect - String - Opcional - Redirecionar para um local específico.
	// querystring - Bool - Opcional - Incluir a querystring no redirecionamento.
	
	// ===== 
	
	if(isset($codigo)){
		http_response_code($codigo);
		
		if(isset($ajax)){
			if(isset($_GESTOR['pagina-alerta'])) if($_GESTOR['pagina-alerta']) gestor_sessao_variavel("alerta",$_GESTOR['pagina-alerta']);
			
			header("Content-Type: application/json; charset: UTF-8");
			
			if(isset($redirect)){
				echo json_encode(Array(
					'error' => $codigo,
					'info' => 'JSON unauthorized',
					'redirect' => $redirect,
				));
			} else {
				switch($codigo){
					case 401:
						echo json_encode(Array(
							'error' => '401',
							'info' => 'JSON unauthorized',
						));
					break;
					case 404:
						echo json_encode(Array(
							'error' => '404',
							'info' => 'JSON not found',
						));
					break;
				}
			}
		} else {
			if(isset($redirect)){
				if(isset($querystring)){
					gestor_redirecionar($redirect,gestor_querystring());
				} else {
					gestor_redirecionar($redirect);
				}
			} else {
				switch($codigo){
					case 401:
						gestor_redirecionar('signin');
					break;
					case 404:
						gestor_redirecionar('404');
					break;
				}
			}
		}
		
		exit;
	}
}

function gestor_hotfix(){
	
	
	echo '<p>Hotfix Done!</p>';
	
	exit;
}

function gestor_roteador(){
	global $_GESTOR;
	global $_INDEX;
	global $_CONFIG;
	
	$modulos = Array();
	
	// ===== Condições iniciais para definir o módulo e a página
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	$_GESTOR['ajax'] = (isset($_REQUEST['ajax']) ? true : false);
	$_GESTOR['ajaxPagina'] = (isset($_REQUEST['ajaxPagina']) ? true : false);
	$_GESTOR['ajax-opcao'] = (isset($_REQUEST['ajaxOpcao']) ? banco_escape_field($_REQUEST['ajaxOpcao']) : false);
	$_GESTOR['opcao'] = (isset($_REQUEST['opcao']) ? banco_escape_field($_REQUEST['opcao']) : false);
	$_GESTOR['paginaIframe'] = (isset($_REQUEST['paginaIframe']) ? true : false);
	$_GESTOR['hotfix'] = (isset($_REQUEST['hotfix']) ? true : false);
	
	$_GESTOR['modulo-registro-id'] = (isset($_REQUEST['ajaxRegistroId']) ? banco_escape_field($_REQUEST['ajaxRegistroId']) : NULL);
	
	// ===== Implementação de um hotfix.
	
	if($_GESTOR['hotfix']){
		gestor_hotfix();
	}
	
	// ===== Rotear URLs de sistema
	
	if(isset($_GESTOR['caminho']))
	switch($_GESTOR['caminho'][0]){
		case '_gestor-cookie-verify': 
			// ===== Verifica se é retorno de redirecionamento veio junto com o cookie. Se sim redirecionar usuário para a URL com queryString. Senão redireciona automaticamente para página informando a obrigatoriedade do uso de cookies para funcionar a página com permissão.
			
			if(!isset($_COOKIE[$_CONFIG['cookie-verify']])){
				header("Location: " . $_GESTOR['url-raiz'] . 'cookies-is-mandatory/'); exit;
			} else {
				$url = urldecode(banco_escape_field($_REQUEST['url']));
				$queryString = urldecode(banco_escape_field($_REQUEST['queryString']));
				
				header("Location: " . $_GESTOR['url-raiz'] . $url .(existe($queryString) ? '?'.$queryString : '')); exit;
			}
		break;
	}
	
	// ===== Definição dos campos necessários para retornar os dados da página
	
	if($_GESTOR['ajax']){
		$campos = Array(
			'modulo',
			'sem_permissao',
			'opcao',
		);
		
		// ===== Se válido pegar o html também.
		
		if($_GESTOR['ajaxPagina']){
			$campos[] = 'html';
		}
	} else if($_GESTOR['opcao']){
		$campos = Array(
			'modulo',
			'sem_permissao',
		);
	} else {
		$campos = Array(
			'id_layouts',
			'html',
			'css',
			'modulo',
			'opcao',
			'sem_permissao',
			'nome',
		);
	}
	
	// ===== Buscar no banco de dados o alvo da requisição
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas($campos)
		,
		"paginas",
		"WHERE caminho='".banco_escape_field($caminho)."'"
		." AND (tipo='sistema' OR tipo='pagina')"
		." AND status='A'"
	);
	
	// ==== Verificar se a página tem permissão, se houver e o usuário não estiver logado, deve redirecionar para a página de login e finalizar a requisição.
	
	if(isset($paginas)){
		$_GESTOR['modulo'] = $paginas[0]['modulo'];
		
		if(existe($_GESTOR['modulo'])){
			if(!existe($paginas[0]['sem_permissao'])){
				gestor_permissao();
				gestor_host_configuracao();
			}
			
			// ===== Verificar se o módulo faz parte de um plugin ou não. Caso faça parte, acessar o local do módulo dentro da pasta do plugin específico, senão no diretório padrão de módulos.
			
			$modulos = banco_select(Array(
				'unico' => true,
				'tabela' => 'modulos',
				'campos' => Array(
					'plugin',
				),
				'extra' => 
					"WHERE id='".$_GESTOR['modulo']."'"
					." AND status='A'"
			));
		}
	}
	
	// ===== Disparar o módulo caso houver e devolver a página ou dados ajax ou alterar opções e redirecionar para a raiz do módulo.
	
	if($_GESTOR['ajax']){
		if(isset($paginas)){
			$modulo = $_GESTOR['modulo'];
			if(!$_GESTOR['opcao']) $_GESTOR['opcao'] = $paginas[0]['opcao'];
			
			// ===== Incluir html da página.
			
			if($_GESTOR['ajaxPagina']){
				$html = $paginas[0]['html'];
				$_GESTOR['pagina'] = $html;
			}
			
			// ===== Módulo alvo quando houver executar
			
			if(existe($modulo)){
				if($modulos['plugin']){
					require_once($_GESTOR['plugins-path'].$modulos['plugin'].'/local/modulos/'.$modulo.'/'.$modulo.'.php');
				} else {
					require_once($_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.php');
				}
			} else if($_GESTOR['opcao']){
				require_once($_GESTOR['modulos-path'].'global.php');
			}
			
			// ===== Retornar a página formatada para o cliente
			
			if(isset($_GESTOR['ajax-json'])){
				header("Content-Type: application/json; charset: UTF-8");
				echo json_encode($_GESTOR['ajax-json']);
				exit;				
			} else {
				gestor_roteador_erro(Array(
					'codigo' => 404,
					'ajax' => $_GESTOR['ajax'],
				));
			}
		} else {
			gestor_roteador_erro(Array(
				'codigo' => 404,
				'ajax' => $_GESTOR['ajax'],
			));
		}
	} else {
		if(isset($paginas)){
			$modulo = $_GESTOR['modulo'];
			
			// ===== Caso haja necessidade, alterar opção no módulo e redirecionar para a raiz do módulo
			
			if($_GESTOR['opcao']){
				if(existe($modulo)){
					if($modulos['plugin']){
						require_once($_GESTOR['plugins-path'].$modulos['plugin'].'/local/modulos/'.$modulo.'/'.$modulo.'.php');
					} else {
						require_once($_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.php');
					}
				}
				
				gestor_redirecionar_raiz();
			}
			
			// ===== Senão houver opção de alteração retornar a página alvo
			
			$nome = $paginas[0]['nome'];
			$html = $paginas[0]['html'];
			$css = $paginas[0]['css'];
			if(!$_GESTOR['opcao']) $_GESTOR['opcao'] = $paginas[0]['opcao'];
			
			// ===== 
			
			$_GESTOR['pagina'] = $html;
			$_GESTOR['pagina#titulo'] = $nome;
			
			// ===== Módulo alvo quando houver executar
			
			if(existe($modulo)){
				if($modulos['plugin']){
					require_once($_GESTOR['plugins-path'].$modulos['plugin'].'/local/modulos/'.$modulo.'/'.$modulo.'.php');
				} else {
					require_once($_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.php');
				}
			} else if($_GESTOR['opcao']){
				require_once($_GESTOR['modulos-path'].'global.php');
			}
			
			// ===== Incluir um layout específico, ou padrão ou nenhum.
			
			if(isset($_GESTOR['layout'])){
				$layout = (isset($_GESTOR['layout']['html']) ? $_GESTOR['layout']['html'] : '');
				$layout_css = (isset($_GESTOR['layout']['css']) ? $_GESTOR['layout']['css'] : '');
			} else if($paginas[0]['id_layouts']){
				if($_GESTOR['paginaIframe']){
					$layouts = gestor_layout(Array(
						'id' => 'layout-iframes',
						'return_css' => true,
					));
				} else {
					$layouts = gestor_layout(Array(
						'id_layouts' => $paginas[0]['id_layouts'],
						'return_css' => true,
					));
				}
				
				$layout = $layouts['html'];
				$layout_css = $layouts['css'];
			} else {
				$layout = '';
				$layout_css = '';
			}
			
			// ===== Montar página html final depois das mudanças pelo módulo.
			
			if(existe($layout_css)){
				$layout_css = preg_replace("/(^|\n)/m", "\n        ", $layout_css);
				
				$_GESTOR['css'][] = '<style>'."\n";
				$_GESTOR['css'][] = $layout_css."\n";
				$_GESTOR['css'][] = '</style>'."\n";
			}
			
			if(existe($css)){
				$css = preg_replace("/(^|\n)/m", "\n        ", $css);
				
				$_GESTOR['css'][] = '<style>'."\n";
				$_GESTOR['css'][] = $css."\n";
				$_GESTOR['css'][] = '</style>'."\n";
			}
			
			// ===== Inclusão de variáveis globais de uma página
			
			gestor_pagina_variaveis(Array(
				'layout' => $layout,
			));
			
			// ===== Inclusão de bibliotecas globais de uma página
			
			gestor_pagina_css();
			gestor_pagina_javascript();
			gestor_pagina_ultimas_operacoes();
			
			// ===== Retornar a página formatada para o cliente
			
			header("Content-Type: text/html; charset: UTF-8");
			echo $_GESTOR['pagina'];
			exit;
		} else {
			gestor_roteador_301_ou_404(Array(
				'caminho' => $caminho,
			));
		}
	}
}

function gestor_start(){
	gestor_sessao_iniciar();
	gestor_roteador();
}

// =========================== Inciar Gestor 

gestor_start();

?>