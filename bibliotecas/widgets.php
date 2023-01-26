<?php

global $_GESTOR;

$_GESTOR['biblioteca-widgets']							=	Array(
	'versao' => '1.0.0',
	'widgets' => Array(
		'formulario-contato' => Array(
			'versao' => '1.0.0', // Versão do widget.
			'componenteID' => 'formulario-contato', // Identificador único do componente do widget.
			'jsCaminho' => 'widgets.js', // Caminho do JS controlador desse widget para ser inserido junto com o mesmo.
		),
	),
);

// ===== Funções auxiliares

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
			));
			
			return $widgetComponente;
		}
		
	}
	
	return '';
}

?>