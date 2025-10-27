<?php

/**
 * Biblioteca de IP - Funções para validação e obtenção de endereços IP
 *
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-ip']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares


// ===== Funções principais

/**
 * Valida um endereço IP.
 *
 * Verifica se um IP é válido e pode opcionalmente permitir ou bloquear
 * IPs privados. Também pode excluir IPs de proxy confiáveis da validação.
 * 
 * Bloqueia por padrão:
 * - IPs reservados
 * - IPs privados (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
 * - Loopback (127.0.0.1)
 *
 * @param string $ip O endereço IP a ser validado.
 * @param bool $allow_private Se true, permite IPs de redes privadas como válidos. Padrão: false.
 * @param array $proxy_ip Array de IPs de proxy confiáveis que devem ser excluídos da validação.
 * @return bool Retorna true se o IP for válido, false caso contrário.
 */
function ip_check($ip, $allow_private = false, $proxy_ip = []){
	// Verifica se o IP é string e não está na lista de proxies confiáveis
	if(!is_string($ip) || is_array($proxy_ip) && in_array($ip, $proxy_ip)) return false;
	
	// Define flag para bloquear IPs reservados
	$filter_flag = FILTER_FLAG_NO_RES_RANGE;

	if(!$allow_private){
		// Bloqueia loopback (127.x.x.x) que não é filtrado por FILTER_FLAG_NO_PRIV_RANGE
		// Referência: https://www.php.net/manual/en/filter.filters.validate.php
		if(preg_match('/^127\.$/', $ip)) return false;
		
		// Adiciona flag para bloquear IPs privados
		$filter_flag |= FILTER_FLAG_NO_PRIV_RANGE;
	}

	// Valida o IP usando as flags configuradas
	return filter_var($ip, FILTER_VALIDATE_IP, $filter_flag) !== false;
}

/**
 * Obtém o endereço IP real do cliente.
 *
 * Detecta o IP do cliente considerando proxies reversos confiáveis.
 * Verifica primeiro REMOTE_ADDR e depois headers de proxy como X-Forwarded-For.
 * 
 * IMPORTANTE: Configure corretamente os IPs de proxy confiáveis para evitar spoofing.
 *
 * @param bool $allow_private Se true, permite IPs privados como válidos. Padrão: false.
 * @return string|null Retorna o IP do cliente ou null se nenhum IP válido for encontrado.
 */
function ip_get($allow_private = false){
	// Lista de IPs de servidores proxy confiáveis (configure conforme sua infraestrutura)
	$proxy_ip = ['127.0.0.1'];

	// Header a ser verificado (ajuste conforme seu proxy reverso)
	// Opções: HTTP_CLIENT_IP, HTTP_X_FORWARDED, HTTP_FORWARDED_FOR, HTTP_FORWARDED
	$header = 'HTTP_X_FORWARDED_FOR';

	// Se REMOTE_ADDR contém um IP válido de cliente, usa ele diretamente
	if(ip_check($_SERVER['REMOTE_ADDR'], $allow_private, $proxy_ip)) return $_SERVER['REMOTE_ADDR'];

	// Verifica o header de proxy se existir
	if(isset($_SERVER[$header])){
		// Separa IPs separados por vírgula e percorre a cadeia de proxies de trás para frente
		// Referência: https://en.wikipedia.org/wiki/X-Forwarded-For#Format
		$chain = array_reverse(preg_split('/\s*,\s*/', $_SERVER[$header]));
		
		// Retorna o primeiro IP válido encontrado na cadeia
		foreach($chain as $ip) if(ip_check($ip, $allow_private, $proxy_ip)) return $ip;
	}

	// Nenhum IP válido foi encontrado
	return null;
}

?>