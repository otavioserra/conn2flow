<?php

global $_GESTOR;

$_GESTOR['biblioteca-ftp']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function ftp_conectar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// usuario - Tipo - Obrigatório - Usuário da conta FTP.
	// senha - Tipo - Obrigatório - Senha da conta FTP.
	// host - Tipo - Obrigatório - Host da conta FTP.
	// secure - Bool - Opcional - Se definido, tenta uma conexão segura via SSL.
	
	// =====
	
	if(isset($usuario) && isset($senha) && isset($host)){
		if(isset($secure) && $secure === true && function_exists('ftp_ssl_connect')) {
			$_GESTOR['ftp-conexao'] = ftp_ssl_connect($host);
		} else {
			$_GESTOR['ftp-conexao'] = ftp_connect($host);
		}

		if($_GESTOR['ftp-conexao']){
			if(!ftp_login($_GESTOR['ftp-conexao'], $usuario, $senha)){
				$_GESTOR['ftp-erro'] = '[ftp_conectar]'." ERRO FTP: Usuario e/ou Senha do FTP invalido(s)!";
				ftp_fechar_conexao();
				
				return false;
			} else {
				if(!isset($_GESTOR['ftp-conexao-nao-passiva']))ftp_pasv($_GESTOR['ftp-conexao'],true);
				return true;
			}
		} else {
			$_GESTOR['ftp-erro'] = '[ftp_conectar]'." ERRO FTP: Conexao com o Server FTP nao realizada!";
			return false;
		}
	}
	
	return false;
}

function ftp_fechar_conexao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// =====
	
	if(!isset($_GESTOR['ftp-conexao'])){
		return false;
	}
	
	if(!$_GESTOR['ftp-conexao']){
		return false;
	}
	
	ftp_close($_GESTOR['ftp-conexao']);
	$_GESTOR['ftp-conexao'] = false;
}

function ftp_colocar_arquivo($params = false){
	global $_GESTOR;
	
	$modoFTP = FTP_BINARY;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// remoto - Tipo - Obrigatório - Caminho para o arquivo remoto (nome ou nome com caminho).
	// local - Tipo - Obrigatório - Caminho para o arquivo local (nome ou nome com caminho).
	// modoFTP - Tipo - Opcional - Modo FTP a ser usado com 2 possibilidades: FTP_ASCII ou FTP_BINARY.
	
	// =====
	
	if(isset($remoto) && isset($local)){
		if(!isset($_GESTOR['ftp-conexao'])){
			return false;
		}
		
		if(!$_GESTOR['ftp-conexao']){
			return false;
		}
		
		if(ftp_put($_GESTOR['ftp-conexao'],$remoto,$local,$modoFTP)) {
			return true;
		} else {
			return false;
		}
	}
}

function ftp_pegar_arquivo($params = false){
	global $_GESTOR;
	
	$modoFTP = FTP_BINARY;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// remoto - Tipo - Obrigatório - Caminho para o arquivo remoto (nome ou nome com caminho).
	// local - Tipo - Obrigatório - Caminho para o arquivo local (nome ou nome com caminho).
	// modoFTP - Tipo - Opcional - Modo FTP a ser usado com 2 possibilidades: FTP_ASCII ou FTP_BINARY.
	
	// =====
	
	if(isset($remoto) && isset($local)){
		if(!isset($_GESTOR['ftp-conexao'])){
			return false;
		}
		
		if(!$_GESTOR['ftp-conexao']){
			return false;
		}
		
		if(ftp_get($_GESTOR['ftp-conexao'], $local, $remoto, $modoFTP)) {
			return true;
		} else {
			return false;
		}
	}
}

?>