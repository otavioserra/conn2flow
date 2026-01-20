<?php

// ===== Força charset UTF-8 em todo o sistema

ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// =========================== Configuração Inicial

require_once(__DIR__ . '/config.php');

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
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
			'modulo_grupo_id', // campo textual
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
		." AND language='".$_GESTOR['linguagem-codigo']."'"
		." ORDER BY nome ASC"
	);

	$modulos_grupos = banco_select(Array(
		'tabela' => 'modulos_grupos',
		'campos' => Array(
			'id', // campo textual
			'nome',
			'ordemMenu',
		),
		'extra' => 
			"WHERE language='".$_GESTOR['linguagem-codigo']."' ORDER BY ordemMenu ASC, nome ASC"
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
			
			if(!isset($grupos[$modulo['modulo_grupo_id']])){
				$achouGrupo = false;
				$nomeGrupo = '';
				if($modulos_grupos)
				foreach($modulos_grupos as $modulo_grupo){
					if($modulo_grupo['id'] == $modulo['modulo_grupo_id']){
						$achouGrupo = true;
						$nomeGrupo = $modulo_grupo['nome'];
						break;
					}
				}
                
				if($achouGrupo){
					$grupos[$modulo['modulo_grupo_id']] = $cel['categoria'];
                    
					$grupos[$modulo['modulo_grupo_id']] = modelo_var_troca($grupos[$modulo['modulo_grupo_id']],'#categoria-nome#',$nomeGrupo);
				} else {
					continue;
				}
			}
            
			$grupos[$modulo['modulo_grupo_id']] = modelo_var_in($grupos[$modulo['modulo_grupo_id']],'<!-- itemMenu -->',$cel_aux);
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
		if(isset($grupos[$modulo_grupo['id']])){
			$cel_conteiner = $cel['itemContCel'];
			$cel_conteiner = modelo_var_troca($cel_conteiner,"#itemCont#",$grupos[$modulo_grupo['id']]);
            
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
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'gestor#versao'.$close,$_GESTOR['versao']);
	
	// ===== Dados do usuário
	
	$usuario = gestor_usuario();
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'usuario#nome'.$close,$usuario['nome']);
	
	if(isset($_GESTOR['modulo-id'])) $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#modulo-id'.$close,$_GESTOR['modulo-id']);
	if(isset($_GESTOR['modulo-registro-id'])) $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#registro-id'.$close,$_GESTOR['modulo-registro-id']);
	

	$variaveisEncontradas = Array();

	// ===== Variáveis globais.

	$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
	preg_match_all($pattern, $_GESTOR['pagina'], $matches);
	
	if($matches)
	foreach($matches[1] as $match){
		// ===== Pegar o valor da variável
		$valor = gestor_variaveis_globais(Array('id' => $match));

		if(isset($valor)){
			$variaveisEncontradas[] = $match;
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$match.$close,existe($valor) ? $valor : '');
		}
	}

	// ===== Variáveis do módulo atual.

	if(isset($_GESTOR['modulo-id'])){
		$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
		preg_match_all($pattern, $_GESTOR['pagina'], $matches);
		
		if($matches)
		foreach($matches[1] as $match){
			if(in_array($match,$variaveisEncontradas)) continue;
			$variaveisEncontradas[] = $match;
			// ===== Pegar o valor da variável
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
				if(in_array($match,$variaveisEncontradas)) continue;
				$variaveisEncontradas[] = $match;
				// ===== Pegar o valor da variável
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

	// ===== Inclusão de bibliotecas CSS Fomantic-UI

	$fomantic_ui_included = false;
	if(isset($_GESTOR['layout#framework_css']))
	if($_GESTOR['layout#framework_css'] == 'fomantic-ui' || $_GESTOR['pagina#framework_css'] == 'fomantic-ui'){
		$fomantic_ui_included = true;
	}
	
	$css_global = '';

	$css_padrao = Array();
	if($fomantic_ui_included) $css_padrao[] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.css" />';

	if(!isset($_GESTOR['css-compiled'])) $_GESTOR['css-compiled'] = Array();
	if(!isset($_GESTOR['css'])) $_GESTOR['css'] = Array();
	if(!isset($_GESTOR['css-fim'])) $_GESTOR['css-fim'] = Array();

	$csss = array_merge($css_padrao,$_GESTOR['css-compiled'],$_GESTOR['css'],$_GESTOR['css-fim']);

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
		$_GESTOR['css-fim'][] = $css = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/css.css?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'">';
		
		// ===== Verifica se já foi adicionado este css, se sim, remover o último que foi adicionado.
		if(!isset($_GESTOR['css-fim-adicionados'])){
			$_GESTOR['css-fim-adicionados'] = Array();
		} else {
			if(in_array($css,$_GESTOR['css-fim-adicionados'])){
				array_pop($_GESTOR['css-fim']);
				return;
			}
		}
	
		$_GESTOR['css-fim-adicionados'][] = $css;
	} else {
		$_GESTOR['javascript-fim'][] = $css;
		
		// ===== Verifica se já foi adicionado este css, se sim, remover o último que foi adicionado.
		if(!isset($_GESTOR['javascript-fim-adicionados'])){
			$_GESTOR['javascript-fim-adicionados'] = Array();
		} else {
			if(in_array($css,$_GESTOR['javascript-fim-adicionados'])){
				array_pop($_GESTOR['javascript-fim']);
				return;
			}
		}
	
		$_GESTOR['javascript-fim-adicionados'][] = $css;
	}
}

function gestor_pagina_extra_head_e_javascript(){
	global $_GESTOR;
	global $_CONFIG;

	// ===== Inclusão de bibliotecas CSS Fomantic-UI

	$fomantic_ui_included = false;
	if(isset($_GESTOR['layout#framework_css']))
	if($_GESTOR['layout#framework_css'] == 'fomantic-ui' || $_GESTOR['pagina#framework_css'] == 'fomantic-ui'){
		$fomantic_ui_included = true;
	}
	
	// ===== Inclusão de bibliotecas javascript
	
	$js_global_includes = '';
	$js_padrao[] = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>'; // jQuery

	if($fomantic_ui_included) $js_padrao[] = '<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.js"></script>'; // Semantic-UI

	$js_padrao[] = '<script src="'.$_GESTOR['url-raiz'].'global/global.js?v='.$_GESTOR['versao'].'"></script>'; // Global JS
	
	if(!isset($_GESTOR['html-extra-head'])) $_GESTOR['html-extra-head'] = Array();
	if(!isset($_GESTOR['javascript'])) $_GESTOR['javascript'] = Array();
	if(!isset($_GESTOR['javascript-fim'])) $_GESTOR['javascript-fim'] = Array();
	
	$jss = array_merge($js_padrao,$_GESTOR['html-extra-head'],$_GESTOR['javascript'],$_GESTOR['javascript-fim']);
	
	if($jss)
	foreach($jss as $js){
		$js_global_includes .= "	" . $js . "\n";
	}
	
	// ===== Inclusão de variáveis javascript
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	$variaveis_js = Array(
		'raiz' => $_GESTOR['url-raiz'],
		'raizSemLang' => $_GESTOR['url-raiz-sem-lang'],
		'language' => $_GESTOR['linguagem-codigo'],
		'languageSystem' => $_GESTOR['linguagem-padrao'],
		'languageCookie' => $_CONFIG['cookie-language'],
		'moduloId' => (isset($_GESTOR['modulo-id']) ? $_GESTOR['modulo-id'] : false ),
		'moduloOpcao' => (isset($_GESTOR['opcao']) ? $_GESTOR['opcao'] : false ),
		'moduloCaminho' => $caminho,
	);
	
	if($_GESTOR['paginaIframe']) $variaveis_js['paginaIframe'] = true;
	
	$js_global_vars = '<script>
		var gestor = '.json_encode((isset($_GESTOR['javascript-vars']) ? array_merge($variaveis_js, $_GESTOR['javascript-vars']):$variaveis_js), JSON_UNESCAPED_UNICODE).';
	</script>'."\n";
	
	// ===== Inclusão na página
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- pagina#js -->',$js_global_vars.$js_global_includes);
}

function gestor_pagina_javascript_incluir($js = false,$id = false, $retornar = false){
	global $_GESTOR;

	$js_script = '';
	
	if(!$js){
		if(isset($_GESTOR['modulo#'.$_GESTOR['modulo-id']]['plugin'])){
			$js_script = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['plugin'].'/'.$_GESTOR['modulo-id'].'/js.js?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'"></script>';
		} else {
			$js_script = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/js.js?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'"></script>';
		}
	} else {
		switch($js){
			case 'biblioteca':
				$js = '<script src="'.$_GESTOR['url-raiz'].'interface/'.$id.'.js?v='.$_GESTOR['biblioteca-'.$id]['versao'].'"></script>';
			break;
		}
		
		$js_script = $js;
	}

	// ===== Se for para retornar o javascript, retornar.
	if($retornar){
		return $js_script;
	} else {
		$_GESTOR['javascript-fim'][] = $js_script;
	}

	// ===== Verifica se já foi adicionado este javascript, se sim, remover o último que foi adicionado.
	if(!isset($_GESTOR['javascript-fim-adicionados'])){
		$_GESTOR['javascript-fim-adicionados'] = Array();
	} else {
		if(in_array($js,$_GESTOR['javascript-fim-adicionados'])){
			array_pop($_GESTOR['javascript-fim']);
			return;
		}
	}

	$_GESTOR['javascript-fim-adicionados'][] = $js;
}

function gestor_pagina_ultimas_operacoes(){
	global $_GESTOR;
	
	$_GESTOR['pagina'] = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $_GESTOR['pagina']);
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

function gestor_cookie_verificacao(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Verifica se cookie no navegador está ativo.
	
	if(!isset($_COOKIE[$_CONFIG['cookie-verify']]) && !isset($_COOKIE[$_CONFIG['cookie-authname']])){
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
		
		$url = urlencode($_GESTOR['caminho-total']);
		$queryString = urlencode(gestor_querystring());
		
		header("Location: " . $_GESTOR['url-raiz'] . '_gestor-cookie-verify/'.$cookieId.'/?url='.$url.(existe($queryString) ? '&queryString='.$queryString : ''));
		exit;
	}
}

function gestor_permissao_token(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Verifica se cookie no navegador está ativo.
	
	gestor_cookie_verificacao();
	
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
		." AND language='".$_GESTOR['linguagem-codigo']."'"
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
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($modulos){
		$modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
			))
			,
			"modulos_operacoes",
			"WHERE operacao='".$operacao."'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
			." AND modulo_id='".$modulo."'"
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
				." AND language='".$_GESTOR['linguagem-codigo']."'"
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

	$lang = $_GESTOR['linguagem-codigo'];
	
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
			'layout_id',
			'html',
			'html_extra_head',
			'css',
			'css_compiled',
			'modulo',
			'opcao',
			'sem_permissao',
			'nome',
			'framework_css',
		);
	}

	// ===== Pegar o id também em ambiente de desenvolvimento afim de buscar o resource por id.

	if($_GESTOR['development-env']){
		$campos[] = 'id';
	}

	// ===== Buscar no banco de dados o alvo da requisição
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas($campos)
		,
		"paginas",
		"WHERE caminho='".banco_escape_field($caminho)."'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
		." AND (tipo='sistema' OR tipo='pagina')"
		." AND status='A'"
	);
	
	// ==== Verificar se a página tem permissão, se houver e o usuário não estiver logado, deve redirecionar para a página de login e finalizar a requisição.
	
	if(isset($paginas)){
		$_GESTOR['modulo'] = $paginas[0]['modulo'];
		
		if(!existe($paginas[0]['sem_permissao'])){
			gestor_permissao();
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
	
	// ===== Disparar o módulo caso houver e devolver a página ou dados ajax ou alterar opções e redirecionar para a raiz do módulo.
	
	if($_GESTOR['ajax']){
		if(isset($paginas)){
			$modulo = $_GESTOR['modulo'];
			if(!$_GESTOR['opcao']) $_GESTOR['opcao'] = $paginas[0]['opcao'];
			
			// ===== Incluir html da página.
			
			if($_GESTOR['ajaxPagina']){
				if($_GESTOR['development-env']){
					$id = $paginas[0]['id'];

					if(existe($modulo)){
						if($modulos['plugin']){
							$html_path = $_GESTOR['plugins-path'].$modulos['plugin'].'/modules/'.$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						} else {
							$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						}
					} else {
						$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
					}

					$html = (file_exists($html_path)) ? file_get_contents($html_path) : (existe($paginas[0]['html']) ? $paginas[0]['html'] : '');
				} else {
					$html = $paginas[0]['html'];
				}
				
				$_GESTOR['pagina'] = $html;
			}
			
			// ===== Módulo alvo quando houver executar
			
			if(existe($modulo)){
				if($modulos['plugin']){
					require_once($_GESTOR['plugins-path'].$modulos['plugin'].'/modules/'.$modulo.'/'.$modulo.'.php');
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
						require_once($_GESTOR['plugins-path'].$modulos['plugin'].'/modules/'.$modulo.'/'.$modulo.'.php');
					} else {
						require_once($_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.php');
					}
				}
				
				gestor_redirecionar_raiz();
			}
			
			// ===== Senão houver opção de alteração retornar a página alvo
			
			$nome = $paginas[0]['nome'];

			if($_GESTOR['development-env']){
				$id = $paginas[0]['id'];

				if(existe($modulo)){
					if($modulos['plugin']){
						$html_path = $_GESTOR['plugins-path'].$modulos['plugin'].'/modules/'.$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['plugins-path'].$modulos['plugin'].'/modules/'.$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.css';
					} else {
						$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.css';
					}
				} else {
					$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.css';
				}

				$html = (file_exists($html_path)) ? file_get_contents($html_path) : (existe($paginas[0]['html']) ? $paginas[0]['html'] : '');
				$css = (file_exists($css_path)) ? file_get_contents($css_path) : (existe($paginas[0]['css']) ? $paginas[0]['css'] : '');
			} else {
				$html = $paginas[0]['html'];
				$css = $paginas[0]['css'];
			}

			$html_extra_head = $paginas[0]['html_extra_head'];
			$css_compiled = $paginas[0]['css_compiled'];
			$framework_css = $paginas[0]['framework_css'];

			if(!$_GESTOR['opcao']) $_GESTOR['opcao'] = $paginas[0]['opcao'];
			
			// ===== 
			
			$_GESTOR['pagina'] = $html;
			$_GESTOR['pagina#titulo'] = $nome;
			$_GESTOR['pagina#framework_css'] = $framework_css;

			// ===== Módulo alvo quando houver executar
			
			if(existe($modulo)){
				if($modulos['plugin']){
					require_once($_GESTOR['plugins-path'].$modulos['plugin'].'/modules/'.$modulo.'/'.$modulo.'.php');
				} else {
					require_once($_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.php');
				}
			} else if($_GESTOR['opcao']){
				require_once($_GESTOR['modulos-path'].'global.php');
			}

			// ===== Incluir componentes na página.

			gestor_componentes_incluir_pagina();
			
			// ===== Incluir um layout específico, ou padrão ou nenhum.
			
			if(isset($_GESTOR['layout'])){
				$layout = (isset($_GESTOR['layout']['html']) ? $_GESTOR['layout']['html'] : '');
				$layout_css = (isset($_GESTOR['layout']['css']) ? $_GESTOR['layout']['css'] : '');
			} else if($paginas[0]['layout_id']){
				if($_GESTOR['paginaIframe']){
					$layouts = gestor_layout(Array(
						'id' => 'layout-iframes',
						'return_css' => true,
					));
				} else {
					$layouts = gestor_layout(Array(
						'id' => $paginas[0]['layout_id'],
						'return_css' => true,
					));
				}
				
				$layout = $layouts['html'];
				$layout_css = $layouts['css'];
				$layout_css_compiled = $layouts['css_compiled'];
			} else {
				$layout = '';
				$layout_css = '';
				$layout_css_compiled = '';
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

			if(existe($html_extra_head)){
				$html_extra_head = preg_replace("/(^|\n)/m", "\n    ", $html_extra_head);
				
				$_GESTOR['html-extra-head'][] = $html_extra_head."\n";
			}

			if(existe($layout_css_compiled)){
				$layout_css_compiled = preg_replace("/(^|\n)/m", "\n        ", $layout_css_compiled);

				$_GESTOR['css-compiled'][] = '<style>'."\n";
				$_GESTOR['css-compiled'][] = $layout_css_compiled."\n";
				$_GESTOR['css-compiled'][] = '</style>'."\n";
			}

			if(existe($css_compiled)){
				$css_compiled = preg_replace("/(^|\n)/m", "\n        ", $css_compiled);
				
				$_GESTOR['css-compiled'][] = '<style>'."\n";
				$_GESTOR['css-compiled'][] = $css_compiled."\n";
				$_GESTOR['css-compiled'][] = '</style>'."\n";
			}
			
			// ===== Inclusão de variáveis de linguagem para o widget e detecção automática

			$widgetActive = (isset($_CONFIG['language']['widget-active']) && $_CONFIG['language']['widget-active'] ? true : false);
			$autoDetect = (isset($_CONFIG['language']['auto-detect']) && $_CONFIG['language']['auto-detect'] ? true : false);

			if($widgetActive || $autoDetect){
				gestor_incluir_biblioteca('variaveis');
				
				$languages = Array();
				foreach($_GESTOR['languages'] as $lang){
					$label = gestor_variaveis(Array('id' => 'language-label-' . $lang));
					$languages[] = Array(
						'codigo' => $lang,
						'nome' => ($label ? $label : $lang),
					);
				}

				if(!isset($_GESTOR['javascript-vars'])){
					$_GESTOR['javascript-vars'] = Array();
				}

				$_GESTOR['javascript-vars']['languages'] = Array(
					'codigos' => $languages,
					'widgetActive' => $widgetActive,
					'autoDetect' => $autoDetect
				);
			}

			// ===== Inclusão de variáveis globais de uma página
			
			gestor_pagina_variaveis(Array(
				'layout' => $layout,
			));
			
			// ===== Inclusão de bibliotecas globais de uma página
			
			gestor_pagina_css();
			gestor_pagina_extra_head_e_javascript();
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

function gestor_config(){
	global $_GESTOR;
	global $_CONFIG;
	
	// =========================== Definição do Caminho da Página

	if(isset($_REQUEST['_gestor-caminho'])){
		$_GESTOR['caminho-total'] = $_REQUEST['_gestor-caminho'];
		$_GESTOR['caminho'] = explode('/',strtolower($_GESTOR['caminho-total']));

		// Verificar se o primeiro segmento é uma linguagem válida, se sim definir a linguagem e remover do array de caminho.
		if(!empty($_GESTOR['caminho'][0]) && in_array($_GESTOR['caminho'][0], $_GESTOR['languages'])){
			$_GESTOR['linguagem-codigo'] = $_GESTOR['caminho'][0];
			array_shift($_GESTOR['caminho']);
			$_GESTOR['caminho-total'] = substr($_GESTOR['caminho-total'], strlen($_GESTOR['linguagem-codigo']) + 1);
			$_GESTOR['language-in-url'] = true;

			// Ajustar URL Raiz para incluir a linguagem
			$_GESTOR['url-raiz'] = $_GESTOR['url-raiz'] . $_GESTOR['linguagem-codigo'].'/';
			$_GESTOR['url-full'] =	$_GESTOR['url-full'] . $_GESTOR['linguagem-codigo'].'/';
			$_GESTOR['url-full-http'] =	$_GESTOR['url-full-http'] . $_GESTOR['linguagem-codigo'].'/';
		}
		
		// Remover último segmento caso seja nulo (barra no final da URL)
		if($_GESTOR['caminho'][count($_GESTOR['caminho'])-1] == NULL){
			array_pop($_GESTOR['caminho']);
		}
		
		$_GESTOR['caminho-extensao'] = pathinfo($_GESTOR['caminho-total'], PATHINFO_EXTENSION);
	}

	// Se não tem linguagem na URL verifique o cookie '$_CONFIG['cookie-language']' e defina $_GESTOR['linguagem-codigo'] se válido
	if(isset($_COOKIE[$_CONFIG['cookie-language']]) && !isset($_GESTOR['language-in-url'])){
		$cookieLang = $_COOKIE[$_CONFIG['cookie-language']];
		if(in_array($cookieLang, $_GESTOR['languages'])){
			$_GESTOR['linguagem-codigo'] = $cookieLang;
		}
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
		case '_gateways':
			require_once($_GESTOR['controladores-path'].'plataforma-gateways/plataforma-gateways.php'); exit;
		break;
		case '_api':
			require_once($_GESTOR['controladores-path'].'api/api.php'); exit;
		break;
	}
}

function gestor_start(){
	gestor_config();
	gestor_sessao_iniciar();
	gestor_roteador();
}

// =========================== Inciar Gestor 

gestor_start();

?>