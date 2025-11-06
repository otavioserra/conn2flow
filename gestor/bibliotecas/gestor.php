<?php
/**
 * Biblioteca Central do Gestor Conn2Flow
 *
 * Sistema de gerenciamento central que coordena:
 * - Componentes e layouts dinâmicos
 * - Variáveis globais do sistema
 * - Sessões e autenticação
 * - Redirecionamentos e navegação
 * - Inclusão de bibliotecas e módulos
 *
 * @package Conn2Flow
 * @subpackage Gestor
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-gestor']							=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

/**
 * Verifica se um dado existe e não está vazio.
 *
 * Testa diferentes tipos de dados:
 * - Array: verifica se tem elementos (count > 0)
 * - String: verifica se tem caracteres (strlen > 0)
 * - Outros tipos: verifica se é truthy
 *
 * @param mixed $dado Dado a ser verificado.
 * @return bool True se existe e não está vazio, false caso contrário.
 */
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

/**
 * Renderiza um componente HTML/CSS dinâmico.
 *
 * Busca e processa componentes do banco de dados com suporte a:
 * - Substituição de variáveis do sistema
 * - CSS compilado e inline
 * - HTML extra para <head>
 * - Componentes únicos ou múltiplos (array de IDs)
 * - Módulos extras para variáveis
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] ID descritivo do componente (ou array de IDs).
 * @param int $params['id_componentes'] ID numérico do componente (alternativa ao 'id').
 * @param string $params['modulo'] Módulo específico (opcional).
 * @param bool $params['return_css'] Se true, retorna array ['html' => ..., 'css' => ...], senão string HTML.
 * @param array $params['modulosExtra'] Módulos extras para busca de variáveis.
 *
 * @return string|array|false HTML do componente ou array com HTML+CSS, ou false se não encontrado.
 */
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
				'css_compiled',
				'html_extra_head',
				'modulo',
			))
			,
			"componentes",
			"WHERE id_componentes='".$id_componentes."'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
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
							'css_compiled',
							'html_extra_head',
							'modulo',
						))
						,
						"componentes",
						"WHERE ".$ids
						." AND language='".$_GESTOR['linguagem-codigo']."'"
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
						'css_compiled',
						'html_extra_head',
						'modulo',
					))
					,
					"componentes",
					"WHERE id='".$id."'"
					." AND language='".$_GESTOR['linguagem-codigo']."'"
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
				
				$html_extra_head = $componente['html_extra_head'];
				$css_compiled = $componente['css_compiled'];
				
				if(isset($return_css)){
					$return[$id] = Array(
						'html' => $html,
						'html_extra_head' => $html_extra_head,
						'css' => $css,
						'css_compiled' => $css_compiled,
					);
				} else {
					if(existe($html_extra_head)){
						$html_extra_head = preg_replace("/(^|\n)/m", "\n    ", $html_extra_head);
						
						$_GESTOR['html-extra-head'][] = $html_extra_head."\n";
					}

					if(existe($css)){
						$css = preg_replace("/(^|\n)/m", "\n        ", $css);
						
						$_GESTOR['css'][] = '<style>'."\n";
						$_GESTOR['css'][] = $css."\n";
						$_GESTOR['css'][] = '</style>'."\n";
					}

					if(existe($css_compiled)){
						$css_compiled = preg_replace("/(^|\n)/m", "\n        ", $css_compiled);
						
						$_GESTOR['css-compiled'][] = '<style>'."\n";
						$_GESTOR['css-compiled'][] = $css_compiled."\n";
						$_GESTOR['css-compiled'][] = '</style>'."\n";
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
			
			$css_compiled = $componentes[0]['css_compiled'];
			$html_extra_head = $componentes[0]['html_extra_head'];

			if(isset($return_css)){
				return Array(
					'html' => $html,
					'css' => $css,
					'html_extra_head' => $html_extra_head,
					'css_compiled' => $css_compiled,
				);
			} else {
				if(existe($html_extra_head)){
					$html_extra_head = preg_replace("/(^|\n)/m", "\n    ", $html_extra_head);
					
					$_GESTOR['html-extra-head'][] = $html_extra_head."\n";
				}

				if(existe($css)){
					$css = preg_replace("/(^|\n)/m", "\n        ", $css);
					
					$_GESTOR['css'][] = '<style>'."\n";
					$_GESTOR['css'][] = $css."\n";
					$_GESTOR['css'][] = '</style>'."\n";
				}

				if(existe($css_compiled)){
					$css_compiled = preg_replace("/(^|\n)/m", "\n        ", $css_compiled);
					
					$_GESTOR['css-compiled'][] = '<style>'."\n";
					$_GESTOR['css-compiled'][] = $css_compiled."\n";
					$_GESTOR['css-compiled'][] = '</style>'."\n";
				}
				
				return $html;
			}
		} else {
			return '';
		}
	}
}

/**
 * Renderiza um layout HTML/CSS completo da página.
 *
 * Busca e processa layouts do banco de dados com suporte a:
 * - Estrutura HTML completa (<!DOCTYPE>, <html>, <head>, <body>)
 * - CSS compilado e frameworks CSS
 * - Substituição de variáveis do sistema
 * - Layout padrão se não encontrado
 * - Layouts únicos ou múltiplos (array de IDs)
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] ID descritivo do layout (ou array de IDs).
 * @param int $params['id_layouts'] ID numérico do layout (alternativa ao 'id').
 * @param bool $params['return_css'] Se true, retorna array ['html' => ..., 'css' => ...], senão string HTML.
 * @param array $params['modulosExtra'] Módulos extras para busca de variáveis.
 *
 * @return string|array|false HTML do layout ou array com HTML+CSS, ou false se não encontrado.
 */
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
				'css_compiled',
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
							'css_compiled',
							'framework_css',
						))
						,
						"layouts",
						"WHERE ".$ids
						." AND language='".$_GESTOR['linguagem-codigo']."'"
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
						'css_compiled',
						'framework_css',
					))
					,
					"layouts",
					"WHERE id='".$id."'"
					." AND language='".$_GESTOR['linguagem-codigo']."'"
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
				
				$css_compiled = $layout['css_compiled'];

				if(isset($return_css)){
					$return[$id] = Array(
						'html' => $html,
						'css' => $css,
						'css_compiled' => $css_compiled,
					);
				} else {
					if(existe($css_compiled)){
						$css_compiled = preg_replace("/(^|\n)/m", "\n        ", $css_compiled);
						
						$_GESTOR['css-compiled'][] = '<style>'."\n";
						$_GESTOR['css-compiled'][] = $css_compiled."\n";
						$_GESTOR['css-compiled'][] = '</style>'."\n";
					}
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
			
			$css_compiled = $layouts[0]['css_compiled'];
			$framework_css = $layouts[0]['framework_css'];

			$_GESTOR['layout#framework_css'] = $framework_css;
			
			if(isset($return_css)){
				return Array(
					'html' => $html,
					'css' => $css,
					'css_compiled' => $css_compiled,
				);
			} else {
				if(existe($css_compiled)){
					$css_compiled = preg_replace("/(^|\n)/m", "\n        ", $css_compiled);
					
					$_GESTOR['css-compiled'][] = '<style>'."\n";
					$_GESTOR['css-compiled'][] = $css_compiled."\n";
					$_GESTOR['css-compiled'][] = '</style>'."\n";
				}
				
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

/**
 * Inclui todas as bibliotecas do sistema.
 *
 * Carrega automaticamente todos os arquivos PHP da pasta bibliotecas,
 * exceto o próprio arquivo gestor.php para evitar recursão.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @return void
 */
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

/**
 * Inclui uma biblioteca específica do sistema.
 *
 * Carrega arquivo PHP da pasta bibliotecas usando require_once
 * para evitar inclusões duplicadas.
 *
 * @param string $biblioteca Nome do arquivo da biblioteca (sem .php).
 * @return void
 */
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

/**
 * Obtém variáveis do sistema por módulo e idioma.
 *
 * Busca variáveis armazenadas no banco de dados com cache em memória.
 * Suporta busca individual ou conjunto completo de variáveis de um módulo.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['modulo'] Módulo do sistema (padrão: '_global_').
 * @param string $params['id'] Identificador único da variável (obrigatório se não usar 'conjunto').
 * @param bool $params['conjunto'] Se true, retorna todas as variáveis do módulo.
 * @param string $params['padrao'] Filtro de padrão regex para IDs (requer 'conjunto').
 * @param bool $params['reset'] Se true, força releitura do banco de dados.
 *
 * @return string|array Valor da variável, array de variáveis (se conjunto), ou string vazia.
 */
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

/**
 * Obtém uma variável global específica do sistema.
 *
 * Busca rápida de variável global por ID com cache em memória.
 * Otimizado para variáveis frequentemente acessadas.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador único da variável (obrigatório).
 * @param bool $params['reset'] Se true, força releitura do banco de dados.
 *
 * @return string|null Valor da variável ou NULL se não encontrada.
 */
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

/**
 * Altera o valor de uma variável no banco de dados.
 *
 * Atualiza variável existente com suporte a diferentes tipos de dados.
 * Usado principalmente para configurações dinâmicas do sistema.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['modulo'] Módulo do sistema da variável (obrigatório).
 * @param string $params['id'] Identificador único da variável (obrigatório).
 * @param string $params['tipo'] Tipo da variável: 'bool' ou outros (obrigatório).
 * @param string|null $params['valor'] Valor que deverá ser alterado.
 * @param string $params['linguagem'] Código do idioma (padrão: idioma atual).
 *
 * @return void
 */
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

/**
 * Redireciona para a página raiz do módulo atual.
 *
 * Busca a página marcada como raiz no banco e redireciona.
 * Útil para retornar à página inicial do módulo após operações.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @return void
 */
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
		." AND language='".$_GESTOR['linguagem-codigo']."'"
		." AND raiz IS NOT NULL"
	);
	
	if(isset($paginas)){
		gestor_redirecionar($paginas[0]['caminho']);
	} else {
		gestor_redirecionar('/');
	}
}

/**
 * Recarrega a URL atual.
 *
 * Redireciona para o caminho atual, útil para atualizar a página
 * após operações que modificam o estado.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @return void
 */
function gestor_reload_url(){
	global $_GESTOR;
	
	gestor_redirecionar($_GESTOR['caminho-total']);
}

/**
 * Remove uma variável específica da query string.
 *
 * Filtra uma query string removendo uma variável específica,
 * mantendo as demais.
 *
 * @param string $queryString Query string completa (formato: var1=val1&var2=val2).
 * @param string $removerVariavel Nome da variável a ser removida.
 * @return string Query string processada sem a variável removida.
 */
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

/**
 * Obtém o valor de uma variável específica da query string.
 *
 * Extrai e retorna o valor de uma variável da query string.
 *
 * @param string $queryString Query string completa.
 * @param string $variavel Nome da variável a buscar.
 * @return string Valor da variável ou string vazia se não encontrada.
 */
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

/**
 * Obtém a query string atual da requisição.
 *
 * Retorna a query string removendo parâmetros internos do gestor
 * (_gestor-caminho) e opcionalmente outras variáveis.
 *
 * @param string $removerVariavel Nome da variável adicional a remover (opcional).
 * @return string Query string processada.
 */
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

/**
 * Redireciona para um local específico.
 *
 * Realiza redirecionamento HTTP com suporte a:
 * - URLs internas (relativas à raiz do sistema)
 * - URLs externas (absolutas)
 * - Query strings personalizadas
 * - Alertas de sessão (mantidos após redirecionamento)
 * - Local armazenado em sessão
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string|false $local Caminho de destino (false = usar sessão ou raiz).
 * @param string $queryString Query string adicional.
 * @param bool $externo Se true, trata como URL externa (não adiciona url-raiz).
 * @return void (executa exit após redirecionar)
 */
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

/**
 * Substitui variáveis globais em HTML.
 *
 * Processa template HTML substituindo variáveis delimitadas por marcadores
 * especiais. Suporta:
 * - Variáveis do módulo atual
 * - Variáveis de módulos extras
 * - Variáveis do sistema (pagina#titulo, pagina#url-raiz, etc.)
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['html'] HTML que será processado (obrigatório).
 *
 * @return string HTML com variáveis substituídas.
 */
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

/**
 * Inclui uma variável JavaScript global na página.
 *
 * Adiciona variável ao array de variáveis JS que serão renderizadas
 * no HTML como objeto JavaScript acessível globalmente.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string $variavel Nome da variável JavaScript.
 * @param mixed $valor Valor da variável (será convertido para JSON).
 * @return void
 */
function gestor_js_variavel_incluir($variavel,$valor){
	global $_GESTOR;

	if(!isset($variavel) || !isset($valor)){
		return;
	}

	$_GESTOR['javascript-vars'][$variavel] = $valor;
}


/**
 * Marca componentes para inclusão na página.
 *
 * Adiciona componente(s) à lista de componentes que serão renderizados.
 * Suporta inclusão individual ou em lote via array.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] ID do componente individual.
 * @param array $params['componentes'] Array de IDs de componentes.
 * @return void
 */
function gestor_componentes_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Opcional - ID do componente que será incluso no gestor.
	// componentes - Array - Opcional - IDs dos componentes que serão incluidos no gestor.
	
	// ===== 
	
	if(isset($componentes)){
		switch(gettype($componentes)){
			case 'array':
				if(count($componentes) > 0){
					foreach($componentes as $com){
						$_GESTOR['componentes'][$com] = true;
					}
				}
			break;
		}
	}

	if(isset($id)){
		$_GESTOR['componentes'][$id] = true;
	}
}


/**
 * Renderiza componentes marcados na página.
 *
 * Processa todos os componentes marcados para inclusão e adiciona
 * seu HTML ao conteúdo da página.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função (atualmente não utilizados).
 * @return void
 */
function gestor_componentes_incluir_pagina($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	
	// ===== 
	
	if(isset($_GESTOR['componentes'])){
		$componentes = $_GESTOR['componentes'];
		
		foreach($componentes as $componente => $valor){
			if(!$valor) continue;
			
			$componente_html = gestor_componente(Array(
				'id' => $componente,
			));
			
			if(existe($componente_html)){
				$_GESTOR['pagina'] .= $componente_html;
			}
		}
		
	}
}

// =========================== Funções de Sessões e Cookies

/**
 * Inicia uma nova sessão ou recupera sessão existente.
 *
 * Cria cookie seguro com ID de sessão usando:
 * - HttpOnly (proteção contra XSS)
 * - Secure (apenas HTTPS)
 * - SameSite=Lax (proteção CSRF)
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @global array $_CONFIG Configurações do sistema.
 * @return void
 */
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

/**
 * Obtém ou cria o ID numérico da sessão no banco de dados.
 *
 * Busca sessão existente pelo ID do cookie ou cria nova entrada.
 * Realiza limpeza aleatória de sessões expiradas (1/50 requisições).
 * Atualiza timestamp de acesso automaticamente.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @global array $_CONFIG Configurações do sistema.
 * @return int ID numérico da sessão no banco de dados.
 */
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

/**
 * Deleta a sessão atual do usuário.
 *
 * Remove todos os dados da sessão:
 * - Variáveis de sessão do banco
 * - Registro de sessão do banco
 * - Cookie do navegador
 *
 * @global array $_CONFIG Configurações do sistema.
 * @return void
 */
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

/**
 * Obtém ou define uma variável de sessão.
 *
 * Gerencia variáveis de sessão armazenadas no banco de dados.
 * Dados são serializados em JSON para suportar tipos complexos.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string $variavel Nome da variável de sessão.
 * @param mixed $valor Valor a ser armazenado (NULL para apenas leitura).
 * @return mixed Valor da variável (em modo leitura) ou void (em modo escrita).
 */
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

/**
 * Remove uma variável específica da sessão.
 *
 * Deleta permanentemente uma variável de sessão do banco de dados.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string $variavel Nome da variável a ser removida.
 * @return void
 */
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

/**
 * Remove TODAS as sessões do sistema.
 *
 * **ATENÇÃO**: Deleta todas as sessões e variáveis de todos os usuários.
 * Usar apenas para manutenção ou reset completo do sistema.
 *
 * @return void
 */
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


/**
 * Pega os dados de um módulo.
 *
 * Lê o arquivo JSON do módulo e retorna os dados como array.
 * 
 * @param string $modulo_id ID do módulo.
 * @return array|null Dados do módulo ou null se não encontrado.
 */
function gestor_modulos_dados($modulo_id = ''){
	global $_GESTOR;
	
	$modulo_dados = json_decode(file_get_contents($_GESTOR['modulos-path'] .$modulo_id. '/'.$modulo_id.'.json'), true);

	if($modulo_dados) {
		return $modulo_dados;
	} else {
		return null;
	}
}

?>