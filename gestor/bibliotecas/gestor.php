<?php

global $_GESTOR;

$_GESTOR['biblioteca-gestor']							=	Array(
	'versao' => '1.0.0',
);

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
	
	// modulo - String - Identificador descritivo do módulo.
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
				'id',
				'html',
				'css',
				'modulo',
			))
			,
			"componentes",
			"WHERE id_componentes='".$id_componentes."'"
			.(isset($modulo) ? " AND modulo='".$modulo."'" : "")
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
							'modulo',
						))
						,
						"componentes",
						"WHERE ".$ids
						.(isset($modulo) ? " AND modulo='".$modulo."'" : "")
					);
				}
			break;
			default:
				$componentes = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id',
						'html',
						'css',
						'modulo',
					))
					,
					"componentes",
					"WHERE id='".$id."'"
					.(isset($modulo) ? " AND modulo='".$modulo."'" : "")
				);
		}
	}
	
	if(isset($return_array)){
		if($componentes){
			$return = Array();
			
			foreach($componentes as $componente){
				$id = $componente['id'];
				$modulo = $componente['modulo'];

				if($_GESTOR['development-env']){
					$lang = $_GESTOR['linguagem-codigo'];

					if(existe($modulo)){
						$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
					} else {
						$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
					}

					$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
					$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
				} else {
					$html = $componente['html'];
					$css = $componente['css'];
				}
				
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
			$id = $componentes[0]['id'];
			$modulo = $componentes[0]['modulo'];

			if($_GESTOR['development-env']){
				$lang = $_GESTOR['linguagem-codigo'];
				
				if(existe($modulo)){
					$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
				} else {
					$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
				}

				$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
				$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
			} else {
				$html = $componentes[0]['html'];
				$css = $componentes[0]['css'];
			}
			
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
	
	// id - String|Array - Identificador(es) descritivo do layout. (É obrigatório OU id OU id_layouts)
	// id_layouts - Int - Identificador numérico do layout no banco de dados. (É obrigatório OU id OU id_layouts)

	// Opcionais
	
	// return_css - Bool - Se ativo retorna Array com HTML e CSS, senão retorna String com o HTML do layout.
	// modulosExtra - Array - Se definido, incluir módulos extras para procura automática de variáveis nestes módulos.
	
	// ===== 

	$layoutHTMLIfNoExists = '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    @[[pagina#corpo]]@
</body>
</html>';
	
	if(isset($modulosExtra)){
		gestor_pagina_variaveis_modulos(Array(
			'modulosExtra' => $modulosExtra,
		));
	}
	
	$layouts = false;
	
	if(isset($id_layouts)){
		$layouts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'html',
				'css',
				'framework_css',
			))
			,
			"layouts",
			"WHERE id_layouts='".$id_layouts."'"
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
							'framework_css',
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
						'id',
						'html',
						'css',
						'framework_css',
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

				if($_GESTOR['development-env']){
					$lang = $_GESTOR['linguagem-codigo'];

					$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';

					$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
					$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
				} else {
					$html = $layout['html'];
					$css = $layout['css'];
				}

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
			$id = $layouts[0]['id'];

			if($_GESTOR['development-env']){
				$lang = $_GESTOR['linguagem-codigo'];

				$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
				$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';

				$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
				$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
			} else {
				$html = $layouts[0]['html'];
				$css = $layouts[0]['css'];
			}

			$framework_css = $layouts[0]['framework_css'];

			$_GESTOR['layout#framework_css'] = $framework_css;
			
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
			if(isset($return_css)){
				return Array(
					'html' => $layoutHTMLIfNoExists,
					'css' => '',
				);
			} else {
				return $layoutHTMLIfNoExists;
			}
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
				require_once($_GESTOR['bibliotecas-path'].$caminho);
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
							require_once($_GESTOR['bibliotecas-path'].$caminho);
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
						require_once($_GESTOR['bibliotecas-path'].$caminho);
					}
				}
		}
	}
}

function gestor_variaveis($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulo - String - Opcional - Módulo do sistema do valor.
	// id - String - Obrigatório - Identificador único do valor.
	// conjunto - Bool - Opcional - Se definido retornar todos os valores do módulo.
	// padrao - String - Opcional - Só funciona se conjunto for definido. Se informado filtrar com esse valor que contêm nos ids das linguagens.
	// reset - Bool - Opcional - Reler banco de dados.
	
	// ===== 
	
	// ===== Procedimentos de inicialização
	
	if(!isset($_GESTOR['variaveis'])){
		$_GESTOR['variaveis'] = Array();
	}

	// ===== Definir módulo padrão se não informado para global.

	if(!isset($modulo)){
		$modulo = '_global_';
	}
	
	// ===== Buscar no banco de dados caso não tenha sido ainda lido na sessão.
	
	if(!isset($_GESTOR['variaveis'][$modulo]) || isset($reset)){
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'valor',
			))
			,
			"variaveis",
			"WHERE linguagem_codigo='".$_GESTOR['linguagem-codigo']."'"
			.($modulo == '_global_' ? " AND modulo IS NULL" : " AND modulo='".$modulo."'")
		);
		
		if($variaveis){
			foreach($variaveis as $li){
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

function gestor_variaveis_globais($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador único do valor.
	// reset - Bool - Opcional - Reler banco de dados.
	
	// ===== 
	
	// ===== Procedimentos de inicialização
	
	if(!isset($_GESTOR['variaveis'])){
		$_GESTOR['variaveis'] = Array();
	}
	
	// ===== Buscar no banco de dados caso não tenha sido ainda lido na sessão.
	
	if(!isset($_GESTOR['variaveis']['_global_'][$id]) || isset($reset)){
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"variaveis",
			"WHERE linguagem_codigo='".$_GESTOR['linguagem-codigo']."'"
			." AND id='".$id."'"
		);
		
		if($variaveis){
			$_GESTOR['variaveis']['_global_'][$id] = $variaveis[0]['valor'];
		}
	}
	
	// ===== Retornar valor pontual.

	return (isset($_GESTOR['variaveis']['_global_'][$id]) ? $_GESTOR['variaveis']['_global_'][$id] : NULL );
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

function gestor_redirecionar($local = false,$queryString = '',$externo = false){
	global $_GESTOR;
	
	if(!$externo){
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
	}
	
	header("Location: ".$local.(existe($queryString) ? '?'.$queryString : ''));
	exit;
	
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

function gestor_sessao_del_all(){
	banco_delete
	(
		"sessoes_variaveis",
		""
	);
	
	banco_delete
	(
		"sessoes",
		""
	);
}

?>