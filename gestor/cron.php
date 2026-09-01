<?php
/**
 * Engine de rotinas automáticas do Conn2Flow (REQ-032 / BATCH-026).
 *
 * Uso:
 *   php cron.php frequencia=minutario
 *   php cron.php frequencia=diario server=meudominio.com
 *   php cron.php tarefa=expiracao-trials debug
 *   php cron.php listar
 *
 * O agendador do sistema operacional (v-add-cron-job no HestiaCP) registra UM tick por
 * frequência. Cada tick consulta `cron_tarefas` e executa apenas as tarefas ativas daquela
 * janela, gravando duração, status e saída de volta na linha.
 */

// =========================== Configuração Inicial

$_GESTOR										=	Array();
$_CRON											=	Array();

$_GESTOR['bibliotecas']							=	Array('banco','gestor','modelo','hooks');

// ===== Configurações pré-inclusão do config.

$debug = false;
$frequencia = null;
$tarefaUnica = null;
$listar = false;
$server = null;
$plataforma_id = null;

$_CRON['ROOT_PATH'] = rtrim(__DIR__, DIRECTORY_SEPARATOR . '/') . '/';

// ===== Parâmetros passados no command line.

for($i=1;$i<$argc;$i++){
	switch($argv[$i]){
		case 'debug': $debug = true; break;
		case 'listar': $listar = true; break;
	}

	if(preg_match('/^server=/i', $argv[$i])){
		$server = preg_replace('/^server=/i', '', $argv[$i]);
	}

	if(preg_match('/^plataforma=/i', $argv[$i])){
		$plataforma_id = preg_replace('/^plataforma=/i', '', $argv[$i]);
	}

	if(preg_match('/^frequencia=/i', $argv[$i])){
		$frequencia = strtolower(trim(preg_replace('/^frequencia=/i', '', $argv[$i])));
	}

	if(preg_match('/^tarefa=/i', $argv[$i])){
		$tarefaUnica = trim(preg_replace('/^tarefa=/i', '', $argv[$i]));
	}
}

// ===== Ativar debug por request.

$_CRON['DEBUG'] = $debug;

// ===== Resolução do host.

/**
 * Descobre o domínio da instalação quando `server=` não é informado.
 *
 * A pasta `autenticacoes/<dominio>/.env` é a fonte de verdade do host em toda a esteira do
 * Gestor. Com uma única instalação (o caso normal de um tenant), a detecção é determinística;
 * com várias, o agendamento precisa dizer qual, senão a rotina rodaria contra o banco errado.
 *
 * @return array{host: ?string, erro: ?string}
 */
function cron_detectar_host($rootPath){
	$authPath = $rootPath . 'autenticacoes/';
	if(!is_dir($authPath)){
		return ['host' => null, 'erro' => 'Diretorio de autenticacoes nao encontrado em ' . $authPath];
	}

	$candidatos = [];
	foreach((glob($authPath . '*', GLOB_ONLYDIR) ?: []) as $dir){
		if(file_exists(rtrim($dir, '/') . '/.env')) $candidatos[] = basename($dir);
	}

	if(count($candidatos) === 1) return ['host' => $candidatos[0], 'erro' => null];

	if(count($candidatos) === 0){
		return ['host' => null, 'erro' => 'Nenhuma instalacao com .env encontrada em ' . $authPath . '. Informe server=<dominio>.'];
	}

	return [
		'host' => null,
		'erro' => 'Multiplas instalacoes encontradas (' . implode(', ', $candidatos) . '). Informe server=<dominio>.',
	];
}

if($server === null || $server === ''){
	$deteccao = cron_detectar_host($_CRON['ROOT_PATH']);
	if($deteccao['host'] === null){
		fwrite(STDERR, '[cron] ' . $deteccao['erro'] . PHP_EOL);
		exit(1);
	}
	$server = $deteccao['host'];
}

// ===== Forçar variáveis globais SERVER.

$_CRON['SERVER_NAME'] = $server;
$_CRON['PLATAFORMA_ID'] = $plataforma_id;

// ===== Inclusão da configuração principal.

require_once($_CRON['ROOT_PATH'] . 'config.php');

// O config carrega apenas as bibliotecas principais (banco, gestor, modelo, hooks) e reescreve
// $_GESTOR['bibliotecas'] no caminho; a de cron entra logo depois, com caminho absoluto.
require_once($_GESTOR['bibliotecas-path'] . 'cron.php');

// A plataforma só é fixada quando o agendamento a informa; caso contrário vale a do config.
if($_CRON['PLATAFORMA_ID'] === null || $_CRON['PLATAFORMA_ID'] === ''){
	$_CRON['PLATAFORMA_ID'] = isset($_GESTOR['plataforma-id']) ? $_GESTOR['plataforma-id'] : null;
}

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

		$file = '';
		if(file_exists($myFile) && filesize($myFile) > 0){
			$file = file_get_contents($myFile);
		}

		file_put_contents($myFile, $file . $msg . "\n");
	}
}

set_error_handler("cron_error_handler");

// ===== Interfaces.

// O vocabulario de frequencias, a validacao de expressoes, a resolucao do callback, o executor
// instrumentado e a gravacao das metricas vivem em bibliotecas/cron.php, incluida pelo config.php
// junto das demais bibliotecas principais. O painel admin-cron consome exatamente as mesmas
// funcoes, entao "Disparar agora" e o tick agendado percorrem o mesmo caminho.

// ===== Principal.

function cron_pipeline(){
	global $_CRON;
	global $frequencia;
	global $tarefaUnica;
	global $listar;

	// As bibliotecas principais (banco, gestor, modelo, hooks) já foram incluídas pelo config.php.
	// gestor_incluir_bibliotecas() NÃO serve aqui: ela lê $_GESTOR['modulo#'.modulo-id], que só
	// existe dentro do ciclo de vida de um módulo.

	// Modo listagem: inventário do que está agendado, sem executar nada.
	if($listar){
		$tarefas = cron_tarefas_carregar(null, null, true);
		cron_log('Tarefas registradas: '.count($tarefas));
		foreach($tarefas as $t){
			cron_log(sprintf(
				'  [%s] %s | modulo=%s | frequencia=%s | expressao=%s | ativo=%s | callback=%s',
				$t['id'], $t['nome'], $t['modulo'], $t['frequencia'], $t['expressao_cron'],
				(!empty($t['ativo']) ? 'sim' : 'nao'), $t['funcao_callback']
			));
		}
		return;
	}

	if($tarefaUnica === null && ($frequencia === null || $frequencia === '')){
		cron_log('ERRO: informe frequencia=<'.implode('|', cron_frequencias_validas()).'>, tarefa=<id> ou listar.');
		return;
	}

	if($tarefaUnica === null && !in_array($frequencia, cron_frequencias_validas(), true)){
		cron_log('ERRO: frequencia invalida: '.$frequencia);
		return;
	}

	$tarefas = cron_tarefas_carregar($frequencia, $tarefaUnica);

	$alvo = ($tarefaUnica !== null) ? ('tarefa '.$tarefaUnica) : ('frequencia '.$frequencia);
	cron_log('Iniciando '.$alvo.' em '.$_CRON['SERVER_NAME'].': '.count($tarefas).' tarefa(s).');

	foreach($tarefas as $tarefa){
		$resultado = cron_tarefa_executar($tarefa);

		cron_tarefa_registrar($tarefa['id'], $resultado['status'], $resultado['duracao'], $resultado['log']);

		cron_log(sprintf(
			'  %s [%s] %dms%s',
			$tarefa['id'],
			$resultado['status'],
			$resultado['duracao'],
			($resultado['log'] !== '' ? ' :: '.str_replace("\n", ' ', $resultado['log']) : '')
		));
	}

	// Retrocompatibilidade: módulos que ainda reagem ao hook de cron continuam sendo
	// notificados, agora com a janela correta em vez do 'diario' fixo anterior.
	if($tarefaUnica === null){
		hook_do_action('cron', $frequencia);
	}

	cron_log('Concluido '.$alvo.'.');
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
			cron_log($saidaBuffer);
		}
	} else {
		echo 'Done!'."\n";
	}
}

cron_start();

?>
