<?php
/**
 * Biblioteca de gerenciamento de hosts.
 *
 * Fornece funções para manipulação de informações de hosts/domínios
 * no sistema Conn2Flow, incluindo URLs, identificadores públicos e
 * configurações específicas de cada host.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.2
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-host']							=	Array(
	'versao' => '1.0.2',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Retorna a URL do host.
 *
 * Busca e retorna a URL do host baseado no identificador fornecido
 * ou no host atual do sistema. Pode retornar a URL completa com
 * protocolo HTTPS ou apenas o domínio.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['opcao'] Opção de retorno de URL ('full' para URL completa com https://).
 * @param int $params['id_hosts'] Identificador do host (opcional, usa o host atual se não fornecido).
 * 
 * @return string|false A URL do host no formato solicitado, ou false se o host não for encontrado.
 */
function host_url($params = false){
	global $_GESTOR;
	global $_HOST;
	
	// Extrair parâmetros do array para variáveis locais
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Identificador do host.
	// Se não foi fornecido um ID de host, usa o host atual do sistema
	if(!isset($id_hosts)){
		if(!isset($_GESTOR['host-id'])){
			return false;
		}
		
		$id_hosts = $_GESTOR['host-id'];
	}
	
	// ===== Buscar domínio do banco de dados se ainda não estiver em cache
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
		
		// Armazena o domínio em cache global para reutilização
		$_HOST['dominio'] = $hosts[0]['dominio'];
	}
	
	$dominio = $_HOST['dominio'];
	
	// ===== Formatar URL de acordo com a opção escolhida
	switch($opcao){
		case 'full':
			// Retorna URL completa com protocolo HTTPS
			$url = 'https://'.$dominio.'/';
		break;
		default:
			// Retorna apenas o domínio
			$url = $dominio;
	}
	
	return $url;
}

/**
 * Retorna o identificador público (pubID) do host.
 *
 * Busca e retorna o identificador público do host, que é usado
 * para identificação externa e integração com outros sistemas.
 *
 * @param array|false $params Parâmetros da função.
 * @param int $params['id_hosts'] Identificador do host (opcional, usa o host atual se não fornecido).
 * 
 * @return string|false O identificador público do host, ou false se o host não for encontrado.
 */
function host_pub_id($params = false){
	global $_GESTOR;
	global $_HOST;
	
	// Extrair parâmetros do array para variáveis locais
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Identificador do host.
	// Se não foi fornecido um ID de host, usa o host atual do sistema
	if(!isset($id_hosts)){
		if(!isset($_GESTOR['host-id'])){
			return false;
		}
		
		$id_hosts = $_GESTOR['host-id'];
	}
	
	// ===== Buscar pubID do banco de dados se ainda não estiver em cache
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
		
		// Armazena o pubID em cache global para reutilização
		$_HOST['pubID'] = $hosts[0]['pub_id'];
	}
	
	$pubID = $_HOST['pubID'];
	
	return $pubID;
}

/**
 * Retorna o nome da loja configurado para o host.
 *
 * Busca e retorna o nome da loja configurado nas variáveis do host.
 * Se não houver um nome configurado, retorna um nome padrão baseado
 * no ID do host.
 *
 * @param array|false $params Parâmetros da função.
 * @param int $params['id_hosts'] Identificador do host (opcional, usa o host atual se não fornecido).
 * 
 * @return string|false O nome da loja ou false se o host não for encontrado.
 */
function host_loja_nome($params = false){
	global $_GESTOR;
	global $_HOST;
	
	// Extrair parâmetros do array para variáveis locais
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Identificador do host.
	// Se não foi fornecido um ID de host, usa o host atual do sistema
	if(!isset($id_hosts)){
		if(!isset($_GESTOR['host-id'])){
			return false;
		}
		
		$id_hosts = $_GESTOR['host-id'];
	}
	
	// ===== Buscar nome da loja do banco de dados se ainda não estiver em cache
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
		
		// Se encontrou configuração, armazena; senão, cria nome padrão
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