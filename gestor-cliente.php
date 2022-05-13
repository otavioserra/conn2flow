<?php

// ===== Configuração Inicial do Gestor do Cliente

$_GESTOR										=	Array();

$_GESTOR['bibliotecas']							=	Array('banco','modelo');

require_once('config-cliente.php');

// ===== Configuração deste host

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
		'ext' => $_GESTOR['caminho-extensao'],
	);
}

if($_GESTOR['arquivo-estatico']){
	require_once($_INDEX['sistemas-dir'].'controladores/arquivo-estatico/arquivo-estatico.php');
	exit;
}

// =========================== Retornar plataforma servidor e finalizar gestor

if(isset($_GESTOR['caminho']))
if($_GESTOR['caminho'][0] == '_plataforma'){
	require_once($_INDEX['sistemas-dir'].'controladores/plataforma-servidor/plataforma-servidor.php');
	exit;
}

// =========================== Funções Auxiliares

function existe($dado = false){
	switch(gettype($dado)){
		case 'array':
			if(count($dado) > 0){
				return true;
			} else {
				return false;
			}
		break;
		case 'string':
			if(strlen($dado) > 0){
				return true;
			} else {
				return false;
			}
		break;
		default:
			if($dado){
				return true;
			} else {
				return false;
			}
	}
}

// =========================== Funções do Gestor

function gestor_componente($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// Obrigatórios
	
	// id - String|Array - Identificador(es) descritivo do componente. (É obrigatório OU id OU id_componentes)
	// id_componentes - Int - Identificador numérico do componente no banco de dados. (É obrigatório OU id OU id_componentes)

	// Opcionais
	
	// return_css - Bool - Se ativo retorna Array com HTML e CSS, senão retorna String com o HTML do componente.
	// modulosExtra - Array - Se definido, incluir módulos extras para procura automática de variáveis nestes módulos.
	
	// ===== 
	
	if(isset($modulosExtra)){
		gestor_pagina_variaveis_modulos(Array(
			'modulosExtra' => $modulosExtra,
		));
	}
	
	$componentes = false;
	
	if(isset($id_componentes)){
		$componentes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'css',
			))
			,
			"componentes",
			"WHERE id_componentes='".$id_componentes."'"
		);
	}
	
	if(isset($id)){
		switch(gettype($id)){
			case 'array':
				$return_array = true;
				$ids = '';
				foreach($id as $i){
					$ids .= (existe($ids) ? ' OR ' : '') .  "id='".$i."'";
				}
				
				if(existe($ids)){
					$componentes = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'html',
							'css',
						))
						,
						"componentes",
						"WHERE ".$ids
					);
				}
			break;
			default:
				$componentes = banco_select_name
				(
					banco_campos_virgulas(Array(
						'html',
						'css',
					))
					,
					"componentes",
					"WHERE id='".$id."'"
				);
		}
	}
	
	if(isset($return_array)){
		if($componentes){
			$return = Array();
			
			foreach($componentes as $componente){
				$id = $componente['id'];
				$html = $componente['html'];
				$css = $componente['css'];
				
				if(isset($return_css)){
					$return[$id] = Array(
						'html' => $html,
						'css' => $css,
					);
				} else {
					if(existe($css)){
						$css = preg_replace("/(^|\n)/m", "\n        ", $css);
						
						$_GESTOR['css'][] = '<style>'."\n";
						$_GESTOR['css'][] = $css."\n";
						$_GESTOR['css'][] = '</style>'."\n";
					}
					
					$return[$id] = Array(
						'html' => $html,
					);
				}
			}
			
			return $return;
		} else {
			return Array();
		}
	} else {
		if($componentes){
			$html = $componentes[0]['html'];
			$css = $componentes[0]['css'];
			
			if(isset($return_css)){
				return Array(
					'html' => $html,
					'css' => $css,
				);
			} else {
				if(existe($css)){
					$css = preg_replace("/(^|\n)/m", "\n        ", $css);
					
					$_GESTOR['css'][] = '<style>'."\n";
					$_GESTOR['css'][] = $css."\n";
					$_GESTOR['css'][] = '</style>'."\n";
				}
				
				return $html;
			}
		} else {
			return '';
		}
	}
}

function gestor_layout($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// Obrigatórios
	
	// id - String|Array - Identificador(es) descritivo do layout. (É obrigatório OU id OU id_hosts_layouts)
	// id_hosts_layouts - Int - Identificador numérico do layout no banco de dados. (É obrigatório OU id OU id_hosts_layouts)

	// Opcionais
	
	// return_css - Bool - Se ativo retorna Array com HTML e CSS, senão retorna String com o HTML do layout.
	// modulosExtra - Array - Se definido, incluir módulos extras para procura automática de variáveis nestes módulos.
	
	// ===== 
	
	if(isset($modulosExtra)){
		gestor_pagina_variaveis_modulos(Array(
			'modulosExtra' => $modulosExtra,
		));
	}
	
	$layouts = false;
	
	if(isset($id_hosts_layouts)){
		$layouts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'html',
				'css',
			))
			,
			"layouts",
			"WHERE id_hosts_layouts='".$id_hosts_layouts."'"
		);
	}
	
	if(isset($id)){
		switch(gettype($id)){
			case 'array':
				$return_array = true;
				$ids = '';
				foreach($id as $i){
					$ids .= (existe($ids) ? ' OR ' : '') .  "id='".$i."'";
				}
				
				if(existe($ids)){
					$layouts = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'html',
							'css',
						))
						,
						"layouts",
						"WHERE ".$ids
					);
				}
			break;
			default:
				$layouts = banco_select_name
				(
					banco_campos_virgulas(Array(
						'html',
						'css',
					))
					,
					"layouts",
					"WHERE id='".$id."'"
				);
		}
	}
	
	if(isset($return_array)){
		if($layouts){
			$return = Array();
			
			foreach($layouts as $layout){
				$id = $layout['id'];
				$html = $layout['html'];
				$css = $layout['css'];
				
				if(isset($return_css)){
					$return[$id] = Array(
						'html' => $html,
						'css' => $css,
					);
				} else {
					if(existe($css)){
						$css = preg_replace("/(^|\n)/m", "\n        ", $css);
						
						$_GESTOR['css'][] = '<style>'."\n";
						$_GESTOR['css'][] = $css."\n";
						$_GESTOR['css'][] = '</style>'."\n";
					}
					
					$return[$id] = Array(
						'html' => $html,
					);
				}
			}
			
			return $return;
		} else {
			return Array();
		}
	} else {
		if($layouts){
			$html = $layouts[0]['html'];
			$css = $layouts[0]['css'];
			
			if(isset($return_css)){
				return Array(
					'html' => $html,
					'css' => $css,
				);
			} else {
				if(existe($css)){
					$css = preg_replace("/(^|\n)/m", "\n        ", $css);
					
					$_GESTOR['css'][] = '<style>'."\n";
					$_GESTOR['css'][] = $css."\n";
					$_GESTOR['css'][] = '</style>'."\n";
				}
				
				return $html;
			}
		} else {
			return '';
		}
	}
}

function gestor_incluir_bibliotecas(){
	global $_GESTOR;
	
	$bibliotecas = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['bibliotecas'];
	
	if($bibliotecas){
		foreach($bibliotecas as $biblioteca){
			$_GESTOR['bibliotecas-inseridas'][$biblioteca] = true;
			$caminhos = $_GESTOR['bibliotecas-dados'][$biblioteca];
			
			if($caminhos)
			foreach($caminhos as $caminho){
				require_once($_GESTOR['modulos-path'].$caminho);
			}
		}
	}
}

function gestor_incluir_biblioteca($biblioteca){
	global $_GESTOR;
	
	if(isset($biblioteca)){
		switch(gettype($biblioteca)){
			case 'array':
				foreach($biblioteca as $bi){
					if(isset($_GESTOR['bibliotecas-inseridas'][$bi])){
						continue;
					}
					
					$caminhos = $_GESTOR['bibliotecas-dados'][$bi];
					
					if($caminhos){
						$_GESTOR['bibliotecas-inseridas'][$bi] = true;
						
						foreach($caminhos as $caminho){
							require_once($_GESTOR['modulos-caminho'].$caminho);
						}
					}
				}
			break;
			default:
				if(isset($_GESTOR['bibliotecas-inseridas'][$biblioteca])){
					return;
				}
				
				$caminhos = $_GESTOR['bibliotecas-dados'][$biblioteca];
				
				if($caminhos){
					$_GESTOR['bibliotecas-inseridas'][$biblioteca] = true;
					
					foreach($caminhos as $caminho){
						require_once($_GESTOR['modulos-caminho'].$caminho);
					}
				}
		}
	}
}

function gestor_variaveis($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulo - String - Obrigatório - Módulo do sistema do valor.
	// id - String - Obrigatório - Identificador único do valor.
	// conjunto - Bool - Opcional - Se definido retornar todos os valores do módulo.
	// padrao - String - Opcional - Só funciona se conjunto for definido. Se informado filtrar com esse valor que contêm nos ids das linguagens.
	
	// ===== 
	
	// ===== Procedimentos de inicialização
	
	if(!isset($_GESTOR['variaveis'])){
		$_GESTOR['variaveis'] = Array();
	}
	
	// ===== Buscar no banco de dados caso não tenha sido ainda lido na sessão.
	
	if(!isset($_GESTOR['variaveis'][$modulo])){
		$linguagem = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'valor',
			))
			,
			"variaveis",
			"WHERE modulo='".$modulo."'"
			//." AND linguagem_codigo='".$_GESTOR['linguagem-codigo']."'"
		);
		
		if($linguagem){
			foreach($linguagem as $li){
				$_GESTOR['variaveis'][$modulo][$li['id']] = $li['valor'];
			}
		}
	}
	
	// ===== Se conjunto definido filtrar se existir padrao e retornar o conjunto, senão retornar valor pontual.
	
	if(isset($conjunto)){
		if(isset($_GESTOR['variaveis'][$modulo])){
			if(isset($padrao)){
				$linguagens_aux = $_GESTOR['variaveis'][$modulo];
				$linguagens = Array();
				
				foreach($linguagens_aux as $id_aux => $linguagem_aux){
					if(preg_match('/'.preg_quote($padrao).'/i', $id_aux) > 0){
						$linguagens[$id_aux] = $linguagem_aux;
					}
				}
				
				return $linguagens;
			} else {
				return $_GESTOR['variaveis'][$modulo];
			}
		} else {
			return Array();
		}
	} else {
		return (isset($_GESTOR['variaveis'][$modulo][$id]) ? $_GESTOR['variaveis'][$modulo][$id] : '' );
	}
}

function gestor_variaveis_alterar($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulo - String - Obrigatório - Módulo do sistema da variável.
	// id - String - Obrigatório - Identificador único da variável.
	// tipo - String - Obrigatório - Tipo da variável.
	// valor - String|NULL - Opcional - Valor que deverá ser alterado.
	// linguagem - String - Opcional - Linguagem da variável que será alterada.
	
	// ===== 
	
	if(isset($modulo) && isset($id) && isset($tipo)){
		switch($tipo){
			case 'bool':
				banco_update
				(
					"valor=".($valor ? '1' : 'NULL'),
					"variaveis",
					"WHERE modulo='".$modulo."'"
					." AND id='".$id."'"
					." AND linguagem_codigo='".(isset($linguagem) ? $linguagem : $_GESTOR['linguagem-codigo'])."'"
				);
			break;
			default:
				banco_update
				(
					"valor='".$valor."'",
					"variaveis",
					"WHERE modulo='".$modulo."'"
					." AND id='".$id."'"
					." AND linguagem_codigo='".(isset($linguagem) ? $linguagem : $_GESTOR['linguagem-codigo'])."'"
				);
		}
	}
}

function gestor_redirecionar_raiz(){
	global $_GESTOR;
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas(Array(
			'caminho',
		))
		,
		"paginas",
		"WHERE modulo='".$_GESTOR['modulo']."'"
		." AND raiz IS NOT NULL"
	);
	
	if(isset($paginas)){
		gestor_redirecionar($paginas[0]['caminho']);
	} else {
		gestor_redirecionar('/');
	}
}

function gestor_reload_url(){
	global $_GESTOR;
	
	gestor_redirecionar($_GESTOR['caminho-total']);
}

function gestor_querystring_remover_variavel($queryString,$removerVariavel = ''){
	if(existe($queryString) && existe($removerVariavel)){
		parse_str($queryString, $variaveis);
		
		$queryStringProcessed = '';
		
		if(isset($variaveis)){
			foreach($variaveis as $var => $valor){
				if($removerVariavel != $var){
					$queryStringProcessed .= (existe($queryStringProcessed) ? '&':'') . $var . '=' . $valor;
				}
			}
		}
		
		return $queryStringProcessed;
	} else {
		return '';
	}
}

function gestor_querystring_variavel($queryString,$variavel = ''){
	if(existe($queryString) && existe($variavel)){
		parse_str($queryString, $variaveis);
		
		$queryStringProcessed = '';
		
		if(isset($variaveis)){
			foreach($variaveis as $var => $valor){
				if($variavel == $var){
					return $valor;
				}
			}
		}
	}
	
	return '';
}

function gestor_querystring($removerVariavel = ''){
	if(!isset($_SERVER['QUERY_STRING'])){
		return '';
	}
	
	$queryString = preg_replace('/'.preg_quote('_gestor-caminho=').'[^&.]*&/i', '', $_SERVER['QUERY_STRING']);
	if(existe($queryString)) $queryString = preg_replace('/'.preg_quote('_gestor-caminho=').'.*/i', '', $queryString);
	
	if(existe($removerVariavel)){
		$queryString = gestor_querystring_remover_variavel($queryString,$removerVariavel);
	}
	
	return (existe($queryString) ? $queryString : '');
}

function gestor_redirecionar($local = false,$queryString = ''){
	global $_GESTOR;
	
	if($local){
		$local = $_GESTOR['url-raiz'] . ($local == '/' ?'':$local);
	} else {
		if(existe(gestor_sessao_variavel("redirecionar-local"))){
			$local = gestor_sessao_variavel("redirecionar-local");
			$local = $_GESTOR['url-raiz'] . ($local == '/' ?'':$local);
			gestor_sessao_variavel_del("redirecionar-local");
		} else {
			$local = $_GESTOR['url-raiz'];
		}
	}
	
	if(isset($_GESTOR['pagina-alerta'])){
		if($_GESTOR['pagina-alerta']){
			gestor_sessao_variavel("alerta",$_GESTOR['pagina-alerta']);
		}
	}
	
	header("Location: ".$local.(existe($queryString) ? '?'.$queryString : ''));
	exit;
	
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
	
	// ===== Incluir o módulo layout caso haja identificação da variável global layout.
	
	if(preg_match('/'.preg_quote($open.'layout#').'/i', $layout) > 0){
		gestor_incluir_biblioteca('pagina');
		gestor_incluir_biblioteca('layout');
		
		layout_trocar_variavel_valor('layout#step','');
		layout_trocar_variavel_valor('layout#step-mobile','');
		
		layout_loja();
		
		$layout = $_GESTOR['layout'];
	}
	
	// ===== Variáveis do layout
	
	$layout = modelo_var_troca($layout,'<!-- pagina#titulo -->',($_GESTOR['pagina#titulo'] ? '<title>'.$_GESTOR['pagina#titulo'].(isset($_GESTOR['pagina#titulo-extra']) ? $_GESTOR['pagina#titulo-extra'] : '').'</title>' : ''));
	
	// ===== Página fundir layout + página
	
	$_GESTOR['pagina'] = modelo_var_troca($layout,$open.'pagina#corpo'.$close,$_GESTOR['pagina']);
	
	// ===== Página variáveis operações
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	// ===== Página variáveis trocar
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-raiz'.$close,$_GESTOR['url-raiz']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-full-http'.$close,$_GESTOR['url-full-http']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-caminho'.$close,$caminho);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#titulo'.$close,$_GESTOR['pagina#titulo']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#contato-url'.$close,$_GESTOR['pagina#contato-url']);
}

function gestor_pagina_variaveis_globais($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// html - String - Obrigatório - Html que será trocada as variáveis globais.
	
	// ===== 
	
	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	
	// ====== 
	
	if(isset($html)){
		if(isset($_GESTOR['modulo-id'])){
			$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
			preg_match_all($pattern, $html, $matches);
			
			if($matches)
			foreach($matches[1] as $match){
				$valor = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $match));
				
				if(existe($valor)){
					$html = modelo_var_troca_tudo($html,$open.$match.$close,$valor);
				}
			}
		}
		
		// ===== Módulos extras que devem ser lidos e colocados as variáveis nas páginas.
		
		if(isset($_GESTOR['paginas-variaveis'])){
			$modulosExtra = $_GESTOR['paginas-variaveis'];
			foreach($modulosExtra as $modulo => $valor){
				$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
				preg_match_all($pattern, $html, $matches);
				
				if($matches)
				foreach($matches[1] as $match){
					$valor = gestor_variaveis(Array('modulo' => $modulo,'id' => $match));
					
					if(existe($valor)){
						$html = modelo_var_troca_tudo($html,$open.$match.$close,$valor);
					}
				}
			}
		}
		
		// ===== Procurar variáveis globais restantes.
		
		$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
		preg_match_all($pattern, $html, $matches);
		
		if($matches)
		foreach($matches[1] as $match){
			$valor = '';
			
			switch($match){
				case 'pagina#url-raiz': 					$valor = $_GESTOR['url-raiz']; break;
				case 'pagina#url-full-http': 				$valor = $_GESTOR['url-full-http']; break;
				case 'pagina#titulo': 						$valor = $_GESTOR['pagina#titulo']; break;
				case 'pagina#contato-url': 					$valor = $_GESTOR['pagina#contato-url']; break;
				case 'pagina#url-caminho': 		
					$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
					$caminho = rtrim($caminho,'/').'/';
	
					$valor = $caminho; 
				break;
			}
			
			if(existe($valor)){
				$html = modelo_var_troca_tudo($html,$open.$match.$close,$valor);
			}
		}
		
		return $html;
	} else {
		return '';
	}
}

function gestor_pagina_css(){
	global $_GESTOR;
	
	$css_global = '';
	//$css_padrao[] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'semantic-UI/semantic.min.css" />';
	$css_padrao[] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'fomantic-UI@2.8.8/dist/semantic.min.css" />';
	
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
	//$js_padrao[] = '<script src="'.$_GESTOR['url-raiz'].'semantic-UI/semantic.min.js"></script>'; // Semantic-UI
	$js_padrao[] = '<script src="'.$_GESTOR['url-raiz'].'fomantic-UI@2.8.8/dist/semantic.min.js"></script>'; // Semantic-UI
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
		$_GESTOR['javascript-fim'][] = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/js.js?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'"></script>';
	} else {
		switch($js){
			case 'modulos':
				$js = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/'.'js.js?v='.$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['versao'].'"></script>';
			break;
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
	
	if(!isset($_COOKIE[$_GESTOR['session-authname']])){
		$sessionId = md5(uniqid(rand(), true));
		
		setcookie($_GESTOR['session-authname'], $sessionId, [
			'expires' => time() + $_GESTOR['session-lifetime'],
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		$_GESTOR['session-id'] = $sessionId;
	} else {
		$_GESTOR['session-id'] = $_COOKIE[$_GESTOR['session-authname']];
	}
}

function gestor_sessao_id(){
	global $_GESTOR;
	
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
				WHERE sess.acesso + ".$_GESTOR['session-lifetime']." < ".time()
				);
		}
		
		$_GESTOR['session-accessed-clean'] = true;
	}
	
	return $id_sessoes;
}

function gestor_sessao_del(){
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
	
	// ===== Verifica se cookie no navegador está ativo.
	
	if(!isset($_COOKIE[$_GESTOR['cookie-verify']])){
		// ===== Criar um cookie de verificação
		
		$cookieId = md5(uniqid(rand(), true));
		
		setcookie($_GESTOR['cookie-verify'], $cookieId, [
			'expires' => '0',
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		// ===== Redirecionar o usuário afim de conferir se está ativo numa nova conexão com a URL e queryString.
		
		$url = urlencode($_GESTOR['caminho-total']);
		$queryString = urlencode(gestor_querystring());
		
		header("Location: " . $_GESTOR['url-raiz'] . '_gestor-cookie-verify/'.$cookieId.'/?url='.$url.(existe($queryString) ? '&queryString='.$queryString : ''));
		exit;
	}
	
	// ===== Verifica se existe o cookie de autenticação gerado no login com sucesso.
	
	if(!isset($_COOKIE[$_GESTOR['cookie-authname']])){
		return false;
	}
	
	$JWTToken = $_COOKIE[$_GESTOR['cookie-authname']];
	
	if(!existe($JWTToken)){
		return false;
	}
	
	// ===== Abrir chave privada e a senha da chave
	
	$chavePrivada = $_GESTOR['seguranca']['chave-privada'];
	$chavePrivadaSenha = $_GESTOR['seguranca']['chave-privada-senha'];
	
	// ===== Verificar se o JWT é válido.
	
	$tokenPubId = gestor_permissao_validar_jwt(Array(
		'token' => $JWTToken,
		'chavePrivada' => $chavePrivada,
		'chavePrivadaSenha' => $chavePrivadaSenha,
	));
	
	if($tokenPubId){
		// ===== Verifica se o token está ativo. Senão estiver invalidar o cookie.
		
		$usuarios_tokens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios_tokens',
				'id_hosts_usuarios_tokens',
				'id_hosts_usuarios',
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
					." AND TIMESTAMPADD(SECOND,".$_GESTOR['session-garbagetime'].",data_criacao) < NOW()"
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
					
					if($time_criacao + $_GESTOR['session-garbagetime'] < time()){
						$expiracao_ok = false;
						
						$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
						
						banco_delete
						(
							"usuarios_tokens",
							"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
						);
					}
				} else {
					// ===== Se o tempo de expiração do token for maior que o tempo agora, é porque este token está vencido e deve ser deletado.
					
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
					$token_hash = hash_hmac($_GESTOR['seguranca']['hash-algo'], $tokenPubId, $_GESTOR['seguranca']['hash-senha']);
					
					if($bd_hash === $token_hash){
						$data_criacao = $usuarios_tokens[0]['data_criacao'];
						$id_hosts_usuarios = $usuarios_tokens[0]['id_hosts_usuarios'];
						$id_hosts_usuarios_tokens = $usuarios_tokens[0]['id_hosts_usuarios_tokens'];
						
						if(!$token_sessao){
							// ===== Verificar se precisa renovar JWTToken, se sim, apagar token anterior e criar um novo no lugar.
							
							$time_criacao = strtotime($data_criacao);
							
							if($time_criacao + $_GESTOR['cookie-renewtime'] < time()){
								gestor_incluir_biblioteca('usuario');
								
								$usuario_token = usuario_token_dados();
								
								usuario_gerar_token_autorizacao(Array(
									'id_hosts_usuarios' => $id_hosts_usuarios,
									'id_hosts_usuarios_tokens' => $id_hosts_usuarios_tokens,
									'usuario_token' => $usuario_token,
								));
								
								$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
								
								banco_delete
								(
									"usuarios_tokens",
									"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
								);
							}
						}
						
						$_GESTOR['usuario-id'] = $id_hosts_usuarios;
						$_GESTOR['usuario-token-id'] = $tokenPubId;
						
						return true;
					}
				}
			}
		}
	}
	
	// ===== Caso não valide, deletar cookie e retornar 'false'.
	
	setcookie($_GESTOR['cookie-authname'], "", [
		'expires' => time() - 3600,
		'path' => '/',
		'domain' => $_SERVER['SERVER_NAME'],
		'secure' => true,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	
	unset($_COOKIE[$_GESTOR['cookie-authname']]);
	
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
				'id_hosts_paginas',
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
				"WHERE id_hosts_paginas='".$paginas_301[0]['id_hosts_paginas']."'"
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
						gestor_redirecionar('identificacao/');
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

function gestor_roteador(){
	global $_GESTOR;
	global $_INDEX;
	
	// ===== Condições iniciais para definir o módulo e a página
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	$_GESTOR['ajax'] = (isset($_REQUEST['ajax']) ? true : false);
	$_GESTOR['ajaxPagina'] = (isset($_REQUEST['ajaxPagina']) ? true : false);
	$_GESTOR['ajax-opcao'] = (isset($_REQUEST['ajaxOpcao']) ? banco_escape_field($_REQUEST['ajaxOpcao']) : false);
	$_GESTOR['opcao'] = (isset($_REQUEST['opcao']) ? banco_escape_field($_REQUEST['opcao']) : false);
	$_GESTOR['paginaIframe'] = (isset($_REQUEST['paginaIframe']) ? true : false);
	
	$_GESTOR['modulo-registro-id'] = (isset($_REQUEST['ajaxRegistroId']) ? banco_escape_field($_REQUEST['ajaxRegistroId']) : NULL);
	
	// ===== Rotear URLs de sistema
	
	if(isset($_GESTOR['caminho']))
	switch($_GESTOR['caminho'][0]){
		case '_gestor-cookie-verify': 
			// ===== Verifica se é retorno de redirecionamento veio junto com o cookie. Se sim redirecionar usuário para a URL com queryString. Senão redireciona automaticamente para página informando a obrigatoriedade do uso de cookies para funcionar a página com permissão.
			
			if(!isset($_COOKIE[$_GESTOR['cookie-verify']])){
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
			'modulo_id_registro',
			'opcao',
		);
		
		// ===== Se válido pegar o html também.
		
		if($_GESTOR['ajaxPagina']){
			$campos[] = 'html';
		}
	} else if($_GESTOR['opcao']){
		$campos = Array(
			'modulo',
			'modulo_id_registro',
		);
	} else {
		$campos = Array(
			'id_hosts_layouts',
			'html',
			'css',
			'modulo',
			'modulo_id_registro',
			'opcao',
			'nome',
			'plugin',
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
	
	// ==== Verificar se existe a página, se sim popular modulo e o id_registro vinculado ao mesmo.
	
	if(isset($paginas)){
		$_GESTOR['modulo'] = $paginas[0]['modulo'];
		$_GESTOR['plugin'] = ($paginas[0]['plugin'] ? $paginas[0]['plugin'] : false);
		$_GESTOR['modulo_id_registro'] = (existe($paginas[0]['modulo_id_registro']) ? $paginas[0]['modulo_id_registro'] : null);
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
				if($_GESTOR['plugin']){
					require_once($_INDEX['sistemas-dir'].'plugins/'.$_GESTOR['plugin'].'/modulos/'.$modulo.'/'.$modulo.'.php');
				} else {
					require_once($_INDEX['sistemas-dir'].'modulos/'.$modulo.'/'.$modulo.'.php');
				}
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
			
			if(!isset($modulo)){
				if($_GESTOR['opcao']){
					if(existe($modulo)){
						if($_GESTOR['plugin']){
							require_once($_INDEX['sistemas-dir'].'plugins/'.$_GESTOR['plugin'].'/modulos/'.$modulo.'/'.$modulo.'.php');
						} else {
							require_once($_INDEX['sistemas-dir'].'modulos/'.$modulo.'/'.$modulo.'.php');
						}
					}
					
					gestor_redirecionar_raiz();
				}
			}
			
			// ===== Senão houver opção de alteração retornar a página alvo
			
			$nome = $paginas[0]['nome'];
			$html = $paginas[0]['html'];
			$css = $paginas[0]['css'];
			if(!$_GESTOR['opcao']) $_GESTOR['opcao'] = $paginas[0]['opcao'];
			
			if($paginas[0]['id_hosts_layouts']){
				if($_GESTOR['paginaIframe']){
					$layouts = gestor_layout(Array(
						'id' => 'layout-iframes',
						'return_css' => true,
					));
				} else {
					$layouts = gestor_layout(Array(
						'id_hosts_layouts' => $paginas[0]['id_hosts_layouts'],
						'return_css' => true,
					));
				}
				
				$layout = $layouts['html'];
				$layout_css = $layouts['css'];
			} else {
				$layout = '';
			}
			
			// ===== 
			
			$_GESTOR['pagina'] = $html;
			$_GESTOR['layout'] = $layout;
			$_GESTOR['pagina#titulo'] = $nome;
			
			// ===== Módulo alvo quando houver executar
			
			if(existe($modulo)){
				if($_GESTOR['plugin']){
					require_once($_INDEX['sistemas-dir'].'plugins/'.$_GESTOR['plugin'].'/modulos/'.$modulo.'/'.$modulo.'.php');
				} else {
					require_once($_INDEX['sistemas-dir'].'modulos/'.$modulo.'/'.$modulo.'.php');
				}
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
				'layout' => $_GESTOR['layout'],
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