<?php

global $_GESTOR;

$_GESTOR['biblioteca-cpanel']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function cpanel_domain_owner_add($params = false){
	/**********
		Descrição: Criar domínio próprio de um host.
	**********/
	
	global $_GESTOR;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// user - String - Obrigatório - Usuário do cPanel.
	// domain_owner - String - Obrigatório - Domínio próprio que será o domínio principal da conta cPanel.
	// domain_default - String - Obrigatório - Domínio padrão que será estacionado.
	
	// ===== 
	
	if(isset($user) && isset($domain_owner) && isset($domain_default)){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
			'domain_owner' => $domain_owner,
			'domain_park' => $domain_default,
		);
		
		require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-domain-owner-add.php');
	}
}

function cpanel_domain_owner_del($params = false){
	/**********
		Descrição: Remover domínio próprio de um host.
	**********/
	
	global $_GESTOR;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// user - String - Obrigatório - Usuário do cPanel.
	// domain_default - String - Obrigatório - Domínio padrão que será o domínio principal da conta cPanel.
	
	// ===== 
	
	if(isset($user) && isset($domain_default)){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
			'domain_owner' => $domain_default,
			'domain_park' => $domain_default,
		);
		
		require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-domain-owner-del.php');
	}
}

function cpanel_changepackage($params = false){
	/**********
		Descrição: Mudar o plano de uma conta do cPanel.
	**********/
	
	global $_GESTOR;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// user - String - Obrigatório - Usuário do cPanel.
	// plan - String - Obrigatório - Plano novo do cPanel.
	
	// ===== 
	
	if(isset($user) && isset($plan)){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
			'plan' => $plan,
		);
		
		require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-changepackage.php');
	}
}

function cpanel_suspendacct($params = false){
	/**********
		Descrição: Suspender uma conta do cPanel.
	**********/
	
	global $_GESTOR;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// user - String - Obrigatório - Usuário do cPanel.
	
	// ===== 
	
	if(isset($user)){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
		);
		
		require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-suspendacct.php');
	}
}

function cpanel_unsuspendacct($params = false){
	/**********
		Descrição: Remover suspensão de uma conta do cPanel.
	**********/
	
	global $_GESTOR;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// user - String - Obrigatório - Usuário do cPanel.
	
	// ===== 
	
	if(isset($user)){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
		);
		
		require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-unsuspendacct.php');
	}
}

function cpanel_removeacct($params = false){
	/**********
		Descrição: Remover uma conta do cPanel.
	**********/
	
	global $_GESTOR;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// user - String - Obrigatório - Usuário do cPanel.
	
	// ===== 
	
	if(isset($user)){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
		);
		
		require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-removeacct.php');
	}
}

?>