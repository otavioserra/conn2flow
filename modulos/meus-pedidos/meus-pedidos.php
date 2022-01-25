<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'meus-pedidos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.7',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function meus_pedidos_padrao($ajax = false){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('interface');
	
	// ===== Variáveis estado inicial.
	
	$max_dados_por_pagina = 20;
	$total = 0;
	$paginaAtual = 0;
	$totalPaginas = 0;
	
	if(!$ajax){
		// ===== Verificar o total de registros.
		
		$pre_pedidos = banco_select(Array(
			'tabela' => 'pedidos',
			'campos' => Array(
				'id_pedidos',
			),
			'extra' => 
				"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
		));
		
		if(!isset($pre_pedidos)){
			$pre_pedidos = Array();
		}
		
		$total = count($pre_pedidos);
	}
	
	// ===== Página atual
	
	if($ajax){
		if(isset($_REQUEST['pagina'])){
			$paginaAtual = (int)banco_escape_field($_REQUEST['pagina']);
		}
		
		$ajaxRetorno = '';
	} else {
		$totalPaginas = ($total % $max_dados_por_pagina > 0 ? 1 : 0) + floor($total / $max_dados_por_pagina);
		
		$_GESTOR['javascript-vars']['pedidos'] = Array(
			'total' => $total,
			'totalPaginas' => $totalPaginas,
		);
	}
	
	// ===== Buscar pedidos no banco de dados.
	
	$pedidos = banco_select(Array(
		'tabela' => 'pedidos',
		'campos' => '*',
		'extra' => 
			"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
			." ORDER BY codigo DESC"
			." LIMIT ".($max_dados_por_pagina * $paginaAtual).",".$max_dados_por_pagina
	));
	
	// ===== Células.
	
	$cel_nome = 'sem-pedidos'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	
	// ===== Verificar se tem ou não pedidos.
	
	if($pedidos){
		$cel_nome = 'pedido-cel'; $cel[$cel_nome] = pagina_celula($cel_nome);
		
		foreach($pedidos as $pedido){
			// ===== Verificar o estado para mostrar botões.
			
			switch($pedido['status']){
				case 'pago':
					$opcoes = '<a class="ui button" href="/voucher/?pedido='.$pedido['codigo'].'"><i class="receipt icon"></i>Voucher</a>';
				break;
				case 'novo':
					$opcoes = '<a class="ui button" href="/emissao/?pedido='.$pedido['codigo'].'"><i class="address card outline icon"></i>Emissão</a>';
				break;
				case 'aguardando-pagamento':
					$opcoes = '<a class="ui button" href="/pagamento/?pedido='.$pedido['codigo'].'"><i class="dollar sign icon"></i>Pagamento</a>';
				break;
				default:
					$opcoes = '';
			}
			
			// ===== Montar célula do pedido.
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"codigo",$pedido['codigo']);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"valor",'R$ '.formato_dado_para('float-para-texto',$pedido['total']));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",gestor_variaveis(Array('modulo' => 'pedidos-status','id' => $pedido['status'])));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data-criacao",formato_dado_para('dataHora',$pedido['data_criacao']));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data-modificacao",formato_dado_para('dataHora',$pedido['data_modificacao']));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"opcoes",$opcoes);
			
			if($ajax){
				$ajaxRetorno .= $cel_aux;
			} else {
				pagina_celula_incluir($cel_nome,$cel_aux);
			}
		}
		
		if($ajax){
			return $ajaxRetorno;
		} else {
			pagina_celula_incluir($cel_nome,'');
			
			if($totalPaginas < 2){
				$cel_nome = 'carregar-mais'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			}
		}
	} else {
		if($ajax){
			return $ajaxRetorno;
		} else {
			$cel_nome = 'pedidos'; $cel[$cel_nome] = pagina_celula($cel_nome);
			pagina_trocar_variavel_valor('<!-- pedidos -->',$cel['sem-pedidos'],true);
		}
	}
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step','');
	layout_trocar_variavel_valor('layout#step-mobile','');
	
	// ===== Finalizar o layout com as variáveis padrões.
	
	layout_loja();
	
	// ===== Finalizar interface.
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	interface_finalizar();
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('modulos');
}

// ==== Ajax

function meus_pedidos_mais_resultados(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'resultados' => meus_pedidos_padrao(true),
		'status' => 'OK',
	);
}

function meus_pedidos_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function meus_pedidos_start(){
	global $_GESTOR;
	
	// ===== Verificar se o usuário está logado.
	
	gestor_permissao();
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'mais-resultados': meus_pedidos_mais_resultados(); break;
			default: meus_pedidos_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': meus_pedidos_opcao(); break;
			default: meus_pedidos_padrao();
		}
	}
}

meus_pedidos_start();

?>