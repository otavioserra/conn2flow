<?php
/**
 * Biblioteca de operações FTP.
 *
 * Fornece funções para conexão, upload e download de arquivos via FTP
 * e FTP sobre SSL (FTPS). Gerencia conexões FTP de forma segura e eficiente.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-ftp']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Estabelece conexão com servidor FTP.
 *
 * Cria uma conexão FTP ou FTPS (FTP sobre SSL) e realiza autenticação.
 * A conexão fica armazenada em $_GESTOR['ftp-conexao'] para uso posterior.
 * Por padrão, ativa modo passivo a menos que especificado contrário.
 *
 * @global array $_GESTOR Sistema global onde a conexão FTP é armazenada.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['usuario'] Usuário da conta FTP (obrigatório).
 * @param string $params['senha'] Senha da conta FTP (obrigatório).
 * @param string $params['host'] Host/servidor FTP (obrigatório).
 * @param bool $params['secure'] Se true, tenta conexão via FTP SSL (opcional).
 * 
 * @return bool True se conexão estabelecida com sucesso, false caso contrário.
 */
function ftp_conectar($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($usuario) && isset($senha) && isset($host)){
		// Tenta conexão segura ou normal conforme solicitado
		if(isset($secure) && $secure === true && function_exists('ftp_ssl_connect')) {
			$_GESTOR['ftp-conexao'] = ftp_ssl_connect($host);
		} else {
			$_GESTOR['ftp-conexao'] = ftp_connect($host);
		}

		// Verifica se conexão foi estabelecida
		if($_GESTOR['ftp-conexao']){
			// Tenta autenticação
			if(!ftp_login($_GESTOR['ftp-conexao'], $usuario, $senha)){
				$_GESTOR['ftp-erro'] = '[ftp_conectar]'." ERRO FTP: Usuario e/ou Senha do FTP invalido(s)!";
				ftp_fechar_conexao();
				
				return false;
			} else {
				// Ativa modo passivo se não especificado contrário
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

/**
 * Fecha a conexão FTP ativa.
 *
 * Encerra a conexão FTP armazenada em $_GESTOR['ftp-conexao']
 * e limpa a variável global.
 *
 * @global array $_GESTOR Sistema global onde a conexão FTP está armazenada.
 * 
 * @param array|false $params Parâmetros da função (não utilizado atualmente).
 * 
 * @return bool|void False se não houver conexão ativa, void após fechar.
 */
function ftp_fechar_conexao($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Verifica se existe conexão
	if(!isset($_GESTOR['ftp-conexao'])){
		return false;
	}
	
	if(!$_GESTOR['ftp-conexao']){
		return false;
	}
	
	// Fecha conexão e limpa variável global
	ftp_close($_GESTOR['ftp-conexao']);
	$_GESTOR['ftp-conexao'] = false;
}

/**
 * Envia um arquivo para o servidor FTP.
 *
 * Faz upload de um arquivo local para o servidor FTP usando a conexão
 * armazenada em $_GESTOR['ftp-conexao'].
 *
 * @global array $_GESTOR Sistema global onde a conexão FTP está armazenada.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['remoto'] Caminho/nome do arquivo no servidor remoto (obrigatório).
 * @param string $params['local'] Caminho/nome do arquivo local (obrigatório).
 * @param int $params['modoFTP'] Modo de transferência: FTP_ASCII ou FTP_BINARY (opcional, padrão: FTP_BINARY).
 * 
 * @return bool|void True se upload bem-sucedido, false caso contrário.
 */
function ftp_colocar_arquivo($params = false){
	global $_GESTOR;
	
	// Define modo padrão como binário
	$modoFTP = FTP_BINARY;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($remoto) && isset($local)){
		// Verifica se existe conexão ativa
		if(!isset($_GESTOR['ftp-conexao'])){
			return false;
		}
		
		if(!$_GESTOR['ftp-conexao'])){
			return false;
		}
		
		// Realiza upload do arquivo
		if(ftp_put($_GESTOR['ftp-conexao'],$remoto,$local,$modoFTP)) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Baixa um arquivo do servidor FTP.
 *
 * Faz download de um arquivo do servidor FTP para o sistema local usando
 * a conexão armazenada em $_GESTOR['ftp-conexao'].
 *
 * @global array $_GESTOR Sistema global onde a conexão FTP está armazenada.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['remoto'] Caminho/nome do arquivo no servidor remoto (obrigatório).
 * @param string $params['local'] Caminho/nome onde salvar o arquivo localmente (obrigatório).
 * @param int $params['modoFTP'] Modo de transferência: FTP_ASCII ou FTP_BINARY (opcional, padrão: FTP_BINARY).
 * 
 * @return bool|void True se download bem-sucedido, false caso contrário.
 */
function ftp_pegar_arquivo($params = false){
	global $_GESTOR;
	
	// Define modo padrão como binário
	$modoFTP = FTP_BINARY;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($remoto) && isset($local)){
		// Verifica se existe conexão ativa
		if(!isset($_GESTOR['ftp-conexao'])){
			return false;
		}
		
		if(!$_GESTOR['ftp-conexao']){
			return false;
		}
		
		// Realiza download do arquivo
		if(ftp_get($_GESTOR['ftp-conexao'], $local, $remoto, $modoFTP)) {
			return true;
		} else {
			return false;
		}
	}
}

?>