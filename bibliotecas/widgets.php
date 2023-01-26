<?php

global $_GESTOR;

$_GESTOR['biblioteca-widgets']							=	Array(
	'versao' => '1.0.1',
	'widgets' => Array(
		'formulario-contato' => Array(
			'versao' => '1.0.0', // Versão do widget.
			'componenteID' => 'widgets-formulario-contato', // Identificador único do componente do widget.
			'jsCaminho' => 'widgets.js', // Caminho do JS controlador desse widget para ser inserido junto com o mesmo.
			'modulosExtras' => 'contatos', // Identificadores dos módulos separados por ',' que devem ser usados para trocar o valor das variáveis globais.
		),
	),
);

// ===== Funções auxiliares

// ===== Funções controladoras dos widgets

function widgets_formulario_contato($params = false){
	/**********
		Descrição: controlador dos widgets 'formulário contato'.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	// ===== Incluir a biblioteca formulario.
	
	gestor_incluir_biblioteca('formulario');
	
	// ===== Disparar regras de validação do formulário.
	
	$validacao = Array(
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'nome',
			'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
		),
	);
	
	formulario_validacao(Array(
		'formId' => '_widgets-form-contato',
		'validacao' => $validacao,
	));
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>');
}

function widgets_controller($params = false){
	/**********
		Descrição: controlador programático de todos os widgets.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador único do widget.
	
	// ===== 
	
	if(isset($id)){
		switch($id){
			case 'formulario-contato': widgets_formulario_contato(); break;
		}
	}
}

// ===== Funções principais

function widgets_search($params = false){
	/**********
		Descrição: busca por um widget e retorna o objeto de dados do mesmo.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador único do widget.
	
	// ===== 
	
	if(isset($id)){
		// ===== Pegar todos os dados da variável principal de widgets.
		
		$widgets = $_GESTOR['biblioteca-widgets']['widgets'];
		
		if($widgets)
		foreach($widgets as $widgetID => $objeto){
			if($widgetID == $id){
				return $objeto;
			}
		}
	}
	
	return null;
}

function widgets_get($params = false){
	/**********
		Descrição: processa um widget por ID e retorna o mesmo.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador único do widget.
	
	// ===== 
	
	if(isset($id)){
		// ===== procurar o widget, caso não encontre, retornar vazio.
		
		$widget = widgets_search(Array(
			'id' => $id,
		));
		
		if(isset($widget)){
			// ===== incluir o JS do widget.
			
			$incluirJS = false;
			
			if(!isset($_GESTOR['widgets-js'])){
				$_GESTOR['widgets-js'][$widget['jsCaminho']] = true;
				$incluirJS = true;
			} else if(!isset($_GESTOR['widgets-js'][$widget['jsCaminho']])){
				$_GESTOR['widgets-js'][$widget['jsCaminho']] = true;
				$incluirJS = true;
			}
			
			if($incluirJS){
				gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'widgets/'.$widget['jsCaminho'].'?v='.$widget['versao'].'"></script>');
			}
			
			// ===== pegar o componente do widget alvo.
			
			$widgetComponente = gestor_componente(Array(
				'id' => $widget['componenteID'],
				'return_css' => true,
			));
			
			// ===== Incluir o CSS do widget.
			
			if(isset($widgetComponente['css']))
			if(existe($widgetComponente['css'])){
				$incluirCSS = false;
				
				if(!isset($_GESTOR['widgets-css'])){
					$_GESTOR['widgets-css'][$id] = true;
					$incluirCSS = true;
				} else if(!isset($_GESTOR['widgets-css'][$id])){
					$_GESTOR['widgets-css'][$id] = true;
					$incluirCSS = true;
				}
				
				if($incluirCSS){
					$css = preg_replace("/(^|\n)/m", "\n        ", $widgetComponente['css']);
					
					$css = '<style>'."\n".
						$css."\n    ".
						'</style>'."\n";
					
					gestor_pagina_css_incluir($css);
				}
			}
			
			// ===== Módulos extras para trocar variáveis.
			
			if(isset($widget['modulosExtras'])){
				$modulosExtras = explode(',',$widget['modulosExtras']);
				
				if($modulosExtras)
				foreach($modulosExtras as $modulo){
					$_GESTOR['paginas-variaveis'][$modulo] = true;
				}
			}
			
			// ===== Disparar o controlador do widget caso haja.
			
			widgets_controller(Array(
				'id' => $id,
			));
			
			// ===== retornar o widget componente.
			
			return (isset($widgetComponente['html']) ? $widgetComponente['html'] : '');
		}
		
	}
	
	return '';
}

?>