<?php

global $_GESTOR;

$_GESTOR['biblioteca-host']							=	Array(
	'versao' => '1.0.2',
);

// ===== Funções auxiliares

// ===== Funções principais

function host_url($params = false){
	/**********
		Descrição: devolver URL do host
	**********/
	
	global $_GESTOR;
	global $_HOST;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Opcional - Opção de retorno de URL.
	// id_hosts - Int - Opcional - Identificador do host manual.
	
	// ===== 
	
	// ===== Identificador do host.
	
	if(!isset($id_hosts)){
		if(!isset($_GESTOR['host-id'])){
			return false;
		}
		
		$id_hosts = $_GESTOR['host-id'];
	}
	
	// ===== Opção escolhida.
	
	if(!isset($_HOST['dominio'])){
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'dominio',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		$_HOST['dominio'] = $hosts[0]['dominio'];
	}
	
	$dominio = $_HOST['dominio'];
	
	switch($opcao){
		case 'full':
			$url = 'https://'.$dominio.'/';
		break;
		default:
			$url = $dominio;
	}
	
	return $url;
}

function host_pub_id($params = false){
	/**********
		Descrição: devolver pubID do host
	**********/
	
	global $_GESTOR;
	global $_HOST;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Opcional - Identificador do host manual.
	
	// ===== 
	
	// ===== Identificador do host.
	
	if(!isset($id_hosts)){
		if(!isset($_GESTOR['host-id'])){
			return false;
		}
		
		$id_hosts = $_GESTOR['host-id'];
	}
	
	// ===== Senão existe pubID, pegar do banco de dados.
	
	if(!isset($_HOST['pubID'])){
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'pub_id',
			))
			,
			"hosts",
			"WHERE id_hosts='".$_GESTOR['host-id']."'"
		);
		
		$_HOST['pubID'] = $hosts[0]['pub_id'];
	}
	
	$pubID = $_HOST['pubID'];
	
	return $pubID;
}

function host_loja_nome($params = false){
	/**********
		Descrição: devolver Loja Nome do host
	**********/
	
	global $_GESTOR;
	global $_HOST;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Opcional - Identificador do host manual.
	
	// ===== 
	
	// ===== Identificador do host.
	
	if(!isset($id_hosts)){
		if(!isset($_GESTOR['host-id'])){
			return false;
		}
		
		$id_hosts = $_GESTOR['host-id'];
	}
	
	// ===== Senão existe pubID, pegar do banco de dados.
	
	if(!isset($_HOST['lojaNome'])){
		$hosts_variaveis = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts_variaveis',
			'campos' => Array(
				'valor',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
				." AND id='nome'"
				." AND modulo='loja-configuracoes'"
		));
		
		if($hosts_variaveis){
			$_HOST['lojaNome'] = $hosts_variaveis['valor'];
		} else {
			$_HOST['lojaNome'] = 'Minha Loja '.$id_hosts;
		}
	}
	
	$lojaNome = $_HOST['lojaNome'];
	
	return $lojaNome;
}

?>