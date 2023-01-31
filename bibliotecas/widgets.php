<?php

global $_GESTOR;

$_GESTOR['biblioteca-widgets']							=	Array(
	'versao' => '1.0.1',
	'widgets' => Array(
		'formulario-contato' => Array(
			'versao' => '1.0.2', // Versão do widget.
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
	global $_CONFIG;
	
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
		Array(
			'regra' => 'email',
			'campo' => 'email',
			'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-email')),
		),
		Array(
			'regra' => 'nao-vazio',
			'campo' => 'telefone',
			'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-tel')),
		),
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'mensagem',
			'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-message')),
		),
	);
	
	formulario_validacao(Array(
		'formId' => '_widgets-form-contato',
		'validacao' => $validacao,
	));
	
	// ===== Verificar a permissão do acesso.
	
	gestor_incluir_biblioteca('autenticacao');
	
	$acesso = autenticacao_acesso_verificar(['tipo' => 'formulario-contato']);
	
	// ===== Mostrar ou ocultar mensagem de bloqueio caso o IP esteja bloqueado.
	
	gestor_incluir_biblioteca('pagina');
	if($acesso['permitido']){	
		$cel_nome = 'bloqueado-mensagem'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	} else {
		$cel_nome = 'formulario'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
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
			// ===== pegar o componente do widget alvo.
			
			$widgetComponente = gestor_componente(Array(
				'id' => $widget['componenteID'],
				'return_css' => true,
			));
			
			// ===== Disparar o controlador do widget caso haja.
			
			widgets_controller(Array(
				'id' => $id,
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
			
			// ===== Módulos extras para trocar variáveis.
			
			if(isset($widget['modulosExtras'])){
				$modulosExtras = explode(',',$widget['modulosExtras']);
				
				if($modulosExtras)
				foreach($modulosExtras as $modulo){
					$_GESTOR['paginas-variaveis'][$modulo] = true;
				}
			}
			
			// ===== retornar o widget componente.
			
			return (isset($widgetComponente['html']) ? $widgetComponente['html'] : '');
		}
		
	}
	
	return '';
}

?>