<?php

global $_GESTOR;

$_GESTOR['biblioteca-layout']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function layout_trocar_variavel_valor($variavel,$valor){
	/**********
		Descrição: trocar variável do layout por um valor.
	**********/
	
	global $_GESTOR;
	
	if(isset($variavel) && isset($valor)){
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		
		$_GESTOR['layout'] = modelo_var_troca_tudo($_GESTOR['layout'],$open.$variavel.$close,$valor);
	}
}

function layout_loja($params = false){
	/**********
		Descrição: Modificação das variáveis do layout da loja.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variavel - Tipo - Obrigatório|Opcional - Descrição.
	
	// ===== 
	
	// ===== Ler variáveis da loja.
	
	$resultado = banco_select(Array(
		'tabela' => 'variaveis',
		'campos' => Array(
			'id',
			'valor',
		),
		'extra' => 
			"WHERE modulo='loja-configuracoes'"
	));
	
	// ===== Varrer todas as variáveis.
	
	if($resultado)
	foreach($resultado as $res){
		switch($res['id']){
			case 'nome':
			case 'cnpj':
			case 'cpf':
			case 'endereco':
			case 'numero':
			case 'complemento':
			case 'bairro':
			case 'cidade':
			case 'uf':
			case 'pais':
			case 'cep':
			case 'telefone':
				$lojaDados[$res['id']] = $res['valor'];
			break;
			case 'logomarca':
				if(existe($res['valor'])) $logomarca = $res['valor'];
			break;
			default:
				
		}
	}
	
	// ===== Logo principal.
	
	if(isset($logomarca)){
		$resultado = banco_select(Array(
			'unico' => true,
			'tabela' => 'arquivos',
			'campos' => Array(
				'caminho',
			),
			'extra' => 
				"WHERE id_hosts_arquivos='".$logomarca."'"
		));
		
		$logo_principal = '/' . $resultado['caminho'];
	} else {
		$logo_principal = '/images/logo-principal.png';
	}
	
	$_GESTOR['layout'] = pagina_trocar_variavel(Array('variavel' => 'layout#logo-principal',			'valor' => $logo_principal,		'codigo' => $_GESTOR['layout']));
	
	// ===== Montar assinatura da loja.
	
	if(isset($lojaDados)){
		$loja_dados .= 
			(isset($lojaDados['nome']) ? $lojaDados['nome'] . ' - ' : '' )
			.(isset($lojaDados['cnpj']) ? 
				'CNPJ: ' . $lojaDados['cnpj'] . ' - ' : 
				(isset($lojaDados['cpf']) ? 'CPF: ' . $lojaDados['cpf'] . ' - ' : '')
			)
			.(isset($lojaDados['endereco']) ? $lojaDados['endereco'] . ' ' : '' )
			.(isset($lojaDados['numero']) ? $lojaDados['numero'] . ', ' : '' )
			.(isset($lojaDados['complemento']) ? $lojaDados['complemento'] . ' - ' : '' )
			.(isset($lojaDados['bairro']) ? $lojaDados['bairro'] . ' - ' : '' )
			.(isset($lojaDados['cidade']) ? $lojaDados['cidade'] . ' - ' : '' )
			.(isset($lojaDados['uf']) ? $lojaDados['uf'] . ' - ' : '' )
			.(isset($lojaDados['pais']) ? $lojaDados['pais'] . ' - ' : '' )
			.(isset($lojaDados['cep']) ? $lojaDados['cep'] . ' - ' : '' )
			."Todos os direitos reservados - ".date('Y')
		;
		
	} else {
		$loja_dados = '';
	}
	
	$_GESTOR['layout'] = pagina_trocar_variavel(Array('variavel' => 'layout#loja-dados',					'valor' => $loja_dados,				'codigo' => $_GESTOR['layout']));
	
	// ===== Formas de Pagamento.
	
	$formas_pagamento = '/images/formas-de-pagamento.png';
	
	$_GESTOR['layout'] = pagina_trocar_variavel(Array('variavel' => 'layout#formas-de-pagamento',			'valor' => $formas_pagamento,		'codigo' => $_GESTOR['layout']));
}

?>