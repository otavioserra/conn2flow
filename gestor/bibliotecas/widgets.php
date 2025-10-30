<?php
/**
 * Biblioteca de widgets do sistema.
 *
 * Gerencia widgets reutilizáveis do Conn2Flow, incluindo componentes
 * como formulários de contato. Fornece sistema de registro, busca e
 * renderização de widgets com suporte a CSS, JavaScript e módulos extras.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.1
 */

global $_GESTOR;

// Registro da versão da biblioteca e configurações de widgets
$_GESTOR['biblioteca-widgets']							=	Array(
	'versao' => '1.0.1',
	'widgets' => Array(
		'formulario-contato' => Array(
			'versao' => '1.0.2', // Versão do widget
			'componenteID' => 'widgets-formulario-contato', // ID único do componente
			'jsCaminho' => 'widgets.js', // Caminho do JS controlador
			'modulosExtras' => 'contatos', // Módulos para variáveis globais (separados por vírgula)
		),
	),
);

// ===== Funções auxiliares

// ===== Funções controladoras dos widgets

/**
 * Controlador do widget de formulário de contato.
 *
 * Processa o widget de formulário de contato incluindo validação de campos,
 * verificação de acesso (bloqueio de IP), integração com reCAPTCHA e
 * inclusão de bibliotecas necessárias (jQuery Mask Plugin).
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * @global array $_CONFIG Configurações específicas do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['html'] HTML do widget (obrigatório).
 * 
 * @return string HTML processado do widget ou string vazia se inválido.
 */
function widgets_formulario_contato($params = false){
	global $_GESTOR;
	global $_CONFIG;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida HTML fornecido
	if(isset($html)){
		
		// ===== Incluir biblioteca de formulário para validação
		gestor_incluir_biblioteca('formulario');
		
		// ===== Definir regras de validação do formulário
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
		
		// Aplicar validação ao formulário
		formulario_validacao(Array(
			'formId' => '_widgets-form-contato',
			'validacao' => $validacao,
		));
		
		// ===== Verificar permissão de acesso (bloqueio por IP)
		gestor_incluir_biblioteca('autenticacao');
		
		$acesso = autenticacao_acesso_verificar(['tipo' => 'formulario-contato']);
		
		// ===== Mostrar/ocultar conteúdo baseado no status de bloqueio
		if($acesso['permitido']){	
			// Acesso permitido: oculta mensagem de bloqueio
			$cel_nome = 'bloqueado-mensagem'; $html = modelo_tag_in($html,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		} else {
			// Acesso bloqueado: oculta formulário
			$cel_nome = 'formulario'; $html = modelo_tag_in($html,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		}
		
		// ===== Incluir Google reCAPTCHA se ativo
		if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
			if($_CONFIG['usuario-recaptcha-active']){
				// Define variáveis JavaScript para reCAPTCHA
				$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
				$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
				
				// Inclui script do Google reCAPTCHA
				gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
			}
		}
		
		// ===== Incluir jQuery Mask Plugin para máscaras de input
		gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>');
		
		return $html;
	} else {
		return '';
	}
}

/**
 * Controlador programático central de todos os widgets.
 *
 * Função despachadora que roteia chamadas de widgets para seus
 * controladores específicos baseado no ID fornecido.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador único do widget (obrigatório).
 * @param string $params['html'] HTML do widget (obrigatório).
 * 
 * @return string HTML processado do widget ou string vazia se parâmetros inválidos.
 */
function widgets_controller($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($id) && isset($html)){
		// Despacha para controlador específico baseado no ID
		switch($id){
			case 'formulario-contato': $html = widgets_formulario_contato(Array(
				'html' => $html,
			)); break;
		}
		
		return $html;
	} else {
		return '';
	}
}

// ===== Funções principais

/**
 * Busca um widget por ID e retorna seus dados de configuração.
 *
 * Procura no registro de widgets e retorna o objeto de configuração
 * completo do widget incluindo versão, ID do componente, caminho JS
 * e módulos extras.
 *
 * @global array $_GESTOR Configurações globais, incluindo registro de widgets.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador único do widget (obrigatório).
 * 
 * @return array|null Objeto de dados do widget ou null se não encontrado.
 */
function widgets_search($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida ID fornecido
	if(isset($id)){
		// ===== Obtém todos os widgets registrados
		$widgets = $_GESTOR['biblioteca-widgets']['widgets'];
		
		// Busca widget pelo ID
		if($widgets)
		foreach($widgets as $widgetID => $objeto){
			if($widgetID == $id){
				return $objeto;
			}
		}
	}
	
	return null;
}

/**
 * Processa e renderiza um widget completo por ID.
 *
 * Função principal para obter widgets. Busca o widget, carrega seu componente,
 * executa seu controlador, inclui CSS e JavaScript necessários, e configura
 * módulos extras para substituição de variáveis.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador único do widget (obrigatório).
 * 
 * @return string HTML processado e completo do widget ou string vazia se não encontrado.
 */
function widgets_get($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida ID fornecido
	if(isset($id)){
		// ===== Procura widget no registro
		$widget = widgets_search(Array(
			'id' => $id,
		));
		
		// Processa widget se encontrado
		if(isset($widget)){
			// ===== Carrega componente do widget com CSS
			$widgetComponente = gestor_componente(Array(
				'id' => $widget['componenteID'],
				'return_css' => true,
			));
			
			// ===== Executa controlador do widget
			$html = widgets_controller(Array(
				'id' => $id,
				'html' => (isset($widgetComponente['html']) ? $widgetComponente['html'] : ''),
			));
			
			// ===== Inclui CSS do widget (apenas uma vez por widget)
			if(isset($widgetComponente['css']))
			if(existe($widgetComponente['css'])){
				$incluirCSS = false;
				
				// Verifica se CSS já foi incluído
				if(!isset($_GESTOR['widgets-css'])){
					$_GESTOR['widgets-css'][$id] = true;
					$incluirCSS = true;
				} else if(!isset($_GESTOR['widgets-css'][$id])){
					$_GESTOR['widgets-css'][$id] = true;
					$incluirCSS = true;
				}
				
				// Formata e inclui CSS se necessário
				if($incluirCSS){
					$css = preg_replace("/(^|\n)/m", "\n        ", $widgetComponente['css']);
					
					$css = '<style>'."\n".
						$css."\n    ".
						'</style>'."\n";
					
					gestor_pagina_css_incluir($css);
				}
			}
			
			// ===== Inclui JavaScript do widget (apenas uma vez por arquivo)
			$incluirJS = false;
			
			// Verifica se JS já foi incluído
			if(!isset($_GESTOR['widgets-js'])){
				$_GESTOR['widgets-js'][$widget['jsCaminho']] = true;
				$incluirJS = true;
			} else if(!isset($_GESTOR['widgets-js'][$widget['jsCaminho']])){
				$_GESTOR['widgets-js'][$widget['jsCaminho']] = true;
				$incluirJS = true;
			}
			
			// Inclui script se necessário
			if($incluirJS){
				gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'widgets/'.$widget['jsCaminho'].'?v='.$widget['versao'].'"></script>');
			}
			
			// ===== Configura módulos extras para substituição de variáveis
			if(isset($widget['modulosExtras'])){
				$modulosExtras = explode(',',$widget['modulosExtras']);
				
				// Marca cada módulo extra como ativo para processamento
				if($modulosExtras)
				foreach($modulosExtras as $modulo){
					$_GESTOR['paginas-variaveis'][$modulo] = true;
				}
			}
			
			// ===== Retorna HTML processado do widget
			return $html;
		}
		
	}
	
	return '';
}

?>