<?php

// =========================== Configuração Inicial

$_GESTOR										=	Array();
$_CRON											=	Array();

$_GESTOR['bibliotecas']							=	Array('banco','gestor');

// ===== Configurações pré-inclusão do config.

$debug = false;
$server = 'beta.conn2flow.com';
$plataforma_id = 'beta';

// ===== Parâmetros passados no command line.

for($i=1;$i<$argc;$i++){
	switch($argv[$i]){
		case 'debug': $debug = true; break;
	}
	
	if(preg_match('/'.preg_quote('server=').'/i', $argv[$i]) > 0){
		$server = preg_replace('/'.preg_quote('server=').'/i', '', $argv[$i]);
	}
	
	if(preg_match('/'.preg_quote('plataforma=').'/i', $argv[$i]) > 0){
		$plataforma_id = preg_replace('/'.preg_quote('plataforma=').'/i', '', $argv[$i]);
	}
}

// ===== Ativar debug por request.

if($debug){
	$_CRON['DEBUG'] = true;
} else {
	$_CRON['DEBUG'] = false;
}

// ===== Forçar variáveis globais SERVER.

$_CRON['SERVER_NAME'] = $server;
$_CRON['PLATAFORMA_ID'] = $plataforma_id;
$_CRON['ROOT_PATH'] = preg_replace('/'.preg_quote('cron.php').'/i', '', $_SERVER['SCRIPT_FILENAME']);

// ===== Inclusão da configuração principal.

require_once('config.php');

// ===== Funções auxiliares.

// ===== Erros e log.

function cron_error_handler($errno, $errstr, $errfile, $errline){
	switch($errno){
		case E_ERROR:				$errConstStr = 'E_ERROR'; break;
		case E_WARNING:				$errConstStr = 'E_WARNING'; break;
		case E_PARSE:				$errConstStr = 'E_PARSE'; break;
		case E_NOTICE:				$errConstStr = 'E_NOTICE'; break;
		case E_CORE_ERROR:			$errConstStr = 'E_CORE_ERROR'; break;
		case E_CORE_WARNING:		$errConstStr = 'E_CORE_WARNING'; break;
		case E_STRICT:				$errConstStr = 'E_STRICT'; break;
		case E_RECOVERABLE_ERROR:	$errConstStr = 'E_RECOVERABLE_ERROR'; break;
		case E_DEPRECATED:			$errConstStr = 'E_DEPRECATED'; break;
		case E_USER_DEPRECATED:		$errConstStr = 'E_USER_DEPRECATED'; break;
		case E_USER_ERROR:			$errConstStr = 'E_USER_ERROR'; break;
		case E_USER_WARNING: 		$errConstStr = 'E_USER_WARNING'; break;
		case E_USER_NOTICE: 		$errConstStr = 'E_USER_NOTICE'; break;
		case E_ALL: 				$errConstStr = 'E_ALL'; break;
		default:
			$errConstStr = 'UNKNOW';
    }
	
	cron_log('['.$errConstStr.'] '.$errfile.':'.$errline.' - '.$errstr);
	
    switch($errno){
		case E_USER_ERROR:
		case E_ERROR:
			exit(1);
		break;
		case E_USER_WARNING:
			
		break;
		case E_USER_NOTICE:
			
		break;
		default:
			
    }

    /* Don't execute PHP internal error handler */
    return true;
}

function cron_log($msg){
	global $_CRON;
	
	$msg = '['.date('D, d M Y H:i:s').'] '.$msg;
	
	if($_CRON['DEBUG']){
		echo $msg . "\n";
	} else {
		$myFile = $_CRON['ROOT_PATH'] . "logs/cron-".date('d-m-Y').".log";
		
		if(file_exists($myFile) && filesize($myFile) > 0){
			$file = file_get_contents($myFile);
		}
		
		file_put_contents($myFile,($file ? $file : '') . $msg . "\n");
	}
}

set_error_handler("cron_error_handler");

// ===== Interfaces.


// ===== Principal.

function cron_pipeline(){
	
}

function cron_start(){
	global $argv;
	global $argc;
	global $_CRON;
	
	// ===== Buffer para log ao invés de direto no console.
	
	if($_CRON['DEBUG']){
		$bufferLog = false;
	} else {
		$bufferLog = true;
	}
	
	// ===== Iniciar o buffer de saída.
	
	if($bufferLog){
		ob_start();
	}
	
	// ===== Pipeline.
	
	cron_pipeline();
	
	// ===== Finalizar o buffer e salvar no log caso haja saída.
	
	if($bufferLog){
		$saidaBuffer = ob_get_contents();
		ob_end_clean();
		
		if(strlen($saidaBuffer) > 0){
			cron_log($str);
		}
	} else {
		echo 'Done!'."\n";
	}
}

cron_start();

?>