<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'postagens';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function postagens_padrao(){
	global $_GESTOR;
	
	// ===== Identificador do postagem.
	
	$id_postagens = $_GESTOR['modulo_id_registro'];
	
	// ===== Verificar os dados do postagem.
	
	$postagens = banco_select(Array(
		'unico' => true,
		'tabela' => 'postagens',
		'campos' => Array(
			'id_postagens',
			'id_hosts_arquivos_Imagem',
			'nome',
			'descricao',
			'status',
		),
		'extra' => 
			"WHERE id_hosts_postagens='".$id_postagens."'"
	));
	
	if($postagens){
		// ===== Caso o postagem estiver ativo 'A' continua.
		
		if($postagens['status'] == 'A'){
			// ===== Pegar o html da página.
			
			$html = $_GESTOR['pagina'];
			
			// ===== Incluir bibliotecas.
			
			gestor_incluir_biblioteca('pagina');
			gestor_incluir_biblioteca('html');
			
			// ===== Montar a página do postagem.
			
			$caminho_mini = '';
			
			$id_hosts_arquivos = $postagens['id_hosts_arquivos_Imagem'];
			
			if(isset($id_hosts_arquivos)){
				$resultado = banco_select_name(
					banco_campos_virgulas(Array(
						'caminho_mini',
					)),
					"arquivos",
					"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
				);
				
				if($resultado){
					if(existe($resultado[0]['caminho_mini'])){
						$caminho_mini = $resultado[0]['caminho_mini'];
					}
				}
			}
			
			// ===== Imagem Mini ou Imagem Referência do postagem.
			
			if(existe($caminho_mini)){
				$imgSrc = $caminho_mini;
			} else {
				$imgSrc = 'images/imagem-padrao.png';
			}
			
			$postagens['imagem-caminho'] = '/' . $imgSrc;
			
			// ===== Trocar as variáveis pelos seus valores no html do template.
			
			$html = pagina_trocar_variavel(Array('variavel' => 'postagem#nome',						'valor' => $postagens['nome'],					'codigo' => $html));
			$html = pagina_trocar_variavel(Array('variavel' => 'postagem#descricao',					'valor' => $postagens['descricao'],				'codigo' => $html));
			$html = pagina_trocar_variavel(Array('variavel' => 'postagem#imagem-caminho',			'valor' => $postagens['imagem-caminho'],			'codigo' => $html));
			
			// ===== Atualizar o html da página.
			
			$_GESTOR['pagina'] = $html;
			
			// ===== Incluir o JS.
			
			gestor_pagina_javascript_incluir('modulos');
			
			return;
		}
	}
	
	// ===== Senão encontrar ou outro motivo, redirecionar para 404.
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	gestor_roteador_301_ou_404(Array(
		'caminho' => $caminho,
	));
}

// ==== Ajax

function postagens_ajax_padrao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function postagens_start(){
	global $_GESTOR;
	
	// ===== Id Externo relacionado. Senão existir, retornar sem executar.
	
	if(!isset($_GESTOR['modulo_id_registro'])){
		return;
	}
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': postagens_ajax_opcao(); break;
			default: postagens_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': postagens_opcao(); break;
			default: postagens_padrao();
		}
	}
}

postagens_start();

?>