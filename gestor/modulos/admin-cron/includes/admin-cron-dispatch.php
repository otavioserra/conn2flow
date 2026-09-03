<?php
/**
 * Despacho de tarefas do painel Admin Cron (REQ-039 / BATCH-166).
 *
 * Arquivo SEM efeito colateral: só define funções. `admin-cron.php` termina em
 * `admin_cron_start()`, que abre a interface — carregá-lo para chegar a estas funções dispararia
 * a renderização do painel. Foi o mesmo motivo que levou o Host Manager a extrair o domínio dele
 * no BATCH-028, e é o que permite testá-las e reutilizá-las fora do ciclo de vida do módulo.
 *
 * O que vive aqui: decidir se uma tarefa precisa rodar fora do processo web e colocá-la para
 * rodar como processo CLI independente do pool PHP-FPM.
 *
 * @package Conn2Flow
 * @subpackage Admin Cron
 */

/**
 * Registra, sem interromper o disparo, por que o caminho desacoplado nao pode ser usado.
 *
 * @param string $id
 * @param string $erro
 * @return void
 */
function cron_log_admin_fallback($id, $erro){
	if(function_exists('gestor_log')){
		gestor_log('[admin-cron] disparo desacoplado indisponivel para "'.$id.'": '.$erro);
	}
}

/**
 * Diz se a tarefa precisa rodar fora do processo web.
 *
 * A declaracao vive na propria tarefa (`parametros.execucao = "desacoplada"`), e nao numa lista
 * fixa dentro do nucleo: quem sabe que uma rotina reinicia servico e o modulo dono dela. O
 * nucleo nao deve conhecer o `host-manager` pelo nome. A chave de configuracao
 * `cron_tarefas_desacopladas` fica como escape do operador para uma tarefa ja publicada.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return bool
 */
function admin_cron_tarefa_desacoplada($tarefa){
	global $_GESTOR;

	$parametros = Array();
	if(!empty($tarefa['parametros'])){
		$decodificado = json_decode((string)$tarefa['parametros'], true);
		if(is_array($decodificado)) $parametros = $decodificado;
	}

	if(admin_cron_parametros_pedem_desacoplamento($parametros)) return true;

	// O `parametros` do banco pode estar DESATUALIZADO: a sincronizacao so o reescreve quando
	// `user_modified` esta vazio, e basta o operador ter pausado a tarefa uma vez para congelar
	// a versao antiga. Como a declaracao aqui e de SEGURANCA (rotina que reinicia servico nao
	// pode rodar no worker web), o manifesto do modulo tambem vale como fonte.
	if(admin_cron_parametros_pedem_desacoplamento(admin_cron_parametros_do_manifesto($tarefa))) return true;

	$lista = isset($_GESTOR['config']['cron_tarefas_desacopladas']) ? $_GESTOR['config']['cron_tarefas_desacopladas'] : '';
	if(!is_array($lista)) $lista = array_filter(array_map('trim', explode(',', (string)$lista)));

	return in_array((string)$tarefa['id'], $lista, true);
}

/**
 * Le a declaracao de desacoplamento de um conjunto de parametros.
 *
 * @param array $parametros
 * @return bool
 */
function admin_cron_parametros_pedem_desacoplamento($parametros){
	if(!is_array($parametros)) return false;
	if(isset($parametros['execucao']) && strtolower(trim((string)$parametros['execucao'])) === 'desacoplada') return true;
	return !empty($parametros['background']);
}

/**
 * Recupera os parametros DECLARADOS da tarefa no manifesto do modulo dono.
 *
 * Leitura pontual pelo campo `modulo` da propria linha — nao varre `modulos/`, ao contrario da
 * sincronizacao, porque aqui interessa uma tarefa so.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return array Parametros declarados; vazio quando o manifesto nao traz a tarefa.
 */
function admin_cron_parametros_do_manifesto($tarefa){
	global $_GESTOR;

	$modulo = isset($tarefa['modulo']) ? trim((string)$tarefa['modulo']) : '';
	if($modulo === '' || !preg_match('/^[a-z0-9][a-z0-9-]*$/i', $modulo)) return Array();
	if(!isset($_GESTOR['modulos-path'])) return Array();

	$arquivo = $_GESTOR['modulos-path'].$modulo.DIRECTORY_SEPARATOR.$modulo.'.json';
	if(!file_exists($arquivo)) return Array();

	$dados = json_decode((string)file_get_contents($arquivo), true);
	if(!is_array($dados) || !isset($dados['cron']) || !is_array($dados['cron'])) return Array();

	$id = (string)$tarefa['id'];

	foreach($dados['cron'] as $declarada){
		if(!is_array($declarada)) continue;
		if(trim((string)(isset($declarada['id']) ? $declarada['id'] : '')) !== $id) continue;

		return (isset($declarada['parametros']) && is_array($declarada['parametros']))
			? $declarada['parametros']
			: Array();
	}

	return Array();
}

/**
 * Resolve o binario do PHP CLI para o disparo desacoplado.
 *
 * `PHP_BINARY` NAO serve: sob PHP-FPM ele aponta para o binario do pool (`php-fpm`), e o cron
 * seria executado sob o SAPI errado.
 *
 * @return string
 */
function admin_cron_php_binario(){
	global $_GESTOR;

	$configurado = isset($_GESTOR['config']['cron_php_binary']) ? trim((string)$_GESTOR['config']['cron_php_binary']) : '';
	if($configurado !== '') return $configurado;

	if(defined('PHP_BINDIR')){
		$candidato = PHP_BINDIR . '/php';
		if(@is_executable($candidato)) return $candidato;
	}

	if(PHP_SAPI === 'cli' && defined('PHP_BINARY') && PHP_BINARY !== '') return PHP_BINARY;

	return 'php';
}

/**
 * Dispara a tarefa como processo CLI independente do worker web.
 *
 * `setsid` e a peca central: sem uma sessao nova o filho continua no grupo de processos do pool
 * PHP-FPM e o `systemctl restart php8.5-fpm` o mata junto com o pai — exatamente o que este
 * caminho existe para evitar. O `&` faz o `sh -c` retornar de imediato, entao `proc_close()` nao
 * bloqueia a resposta ao navegador.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return array{ok: bool, erro: string, comando: string}
 */
function admin_cron_disparar_em_background($tarefa){
	global $_GESTOR;

	if(DIRECTORY_SEPARATOR !== '/'){
		return Array('ok' => false, 'erro' => 'Disparo desacoplado disponivel apenas em ambientes POSIX.', 'comando' => '');
	}

	if(!function_exists('proc_open')){
		return Array('ok' => false, 'erro' => 'proc_open indisponivel neste pool PHP.', 'comando' => '');
	}

	$raiz = isset($_GESTOR['ROOT_PATH']) ? (string)$_GESTOR['ROOT_PATH'] : '';
	$script = $raiz . 'cron.php';

	if($raiz === '' || !file_exists($script)){
		return Array('ok' => false, 'erro' => 'Entrada cron.php nao encontrada em "'.$raiz.'".', 'comando' => '');
	}

	$argumentos = Array(admin_cron_php_binario(), $script, 'tarefa='.$tarefa['id']);

	// `cron.php` detecta o host sozinho quando ha UMA instalacao; com varias ele aborta pedindo
	// server=. Informar o dominio da requisicao corrente resolve os dois casos.
	$servidor = isset($_SERVER['SERVER_NAME']) ? trim((string)$_SERVER['SERVER_NAME']) : '';
	if($servidor !== '') $argumentos[] = 'server='.$servidor;

	if(!empty($_GESTOR['plataforma-id'])) $argumentos[] = 'plataforma='.$_GESTOR['plataforma-id'];

	$comando = implode(' ', array_map('escapeshellarg', $argumentos));
	$linha = 'setsid ' . $comando . ' < /dev/null > /dev/null 2>&1 &';

	$descritores = Array(
		0 => Array('file', '/dev/null', 'r'),
		1 => Array('file', '/dev/null', 'w'),
		2 => Array('file', '/dev/null', 'w'),
	);

	$pipes = Array();
	$processo = @proc_open($linha, $descritores, $pipes);

	if(!is_resource($processo)){
		return Array('ok' => false, 'erro' => 'Nao foi possivel iniciar o processo CLI.', 'comando' => $linha);
	}

	proc_close($processo);

	return Array('ok' => true, 'erro' => '', 'comando' => $linha);
}
