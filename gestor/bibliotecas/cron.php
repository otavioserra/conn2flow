<?php
/**
 * Biblioteca de rotinas automáticas (REQ-032 / BATCH-026).
 *
 * Concentra o que a engine de linha de comando (gestor/cron.php) e o painel administrativo
 * (modulos/admin-cron) precisam compartilhar: vocabulário de frequências, validação de
 * expressões, resolução do callback, execução instrumentada e gravação das métricas.
 *
 * A engine é um script standalone que termina chamando cron_start(); incluí-la a partir do
 * módulo dispararia um tick inteiro só para reaproveitar uma função. Por isso o executor vive
 * aqui, e ambos os consumidores chamam o mesmo código.
 *
 * @package Conn2Flow
 * @subpackage Cron
 * @version 1.0.0
 */

/**
 * Frequências aceitas no despacho e na chave "cron" dos manifestos de módulo.
 *
 * Espelha cronFrequenciasValidas() do compilador de recursos: uma frequência fora desta lista
 * vira órfã na compilação e nunca chega ao banco.
 */
function cron_frequencias_validas(){
	return Array('minutario','horario','diario','mensal','customizado');
}

/**
 * Resultados possíveis de uma execução.
 */
function cron_status_validos(){
	return Array('sucesso','erro','aviso');
}

/**
 * Valida a FORMA de uma expressão cron de 5 campos.
 *
 * Checagem estrutural, não semântica: garante 5 campos compostos apenas de dígitos e dos
 * operadores * , - / — suficiente para impedir que texto arbitrário vindo do painel chegue ao
 * `v-add-cron-job` do HestiaCP ou ao crontab.
 *
 * @param string $expressao
 * @return bool
 */
function cron_expressao_valida($expressao){
	$campos = preg_split('#\s+#', trim((string)$expressao));
	if(!is_array($campos) || count($campos) !== 5) return false;
	foreach($campos as $campo){
		if($campo === '' || !preg_match('#^[-0-9*,/]+$#', $campo)) return false;
	}
	return true;
}

/**
 * Expressão cron padrão de cada janela, usada quando a tarefa não declara uma.
 *
 * @param string $frequencia
 * @return string|null null para 'customizado', que não tem forma derivável.
 */
function cron_expressao_padrao($frequencia){
	switch($frequencia){
		case 'minutario': return '*/10 * * * *';
		case 'horario': return '0 * * * *';
		case 'diario': return '0 3 * * *';
		case 'mensal': return '0 4 1 * *';
	}
	return null;
}

/**
 * Resolve a expressão cron de 5 campos de uma tarefa declarada.
 *
 * Precedência: `expressao_cron` explícita vence. Sem ela, a expressão é derivada da frequência
 * mais os campos opcionais `hora` ("HH:MM") e `dia` (1-31, apenas para 'mensal'). A frequência
 * 'customizado' não tem forma derivável — sem `expressao_cron` a declaração é inválida, em vez
 * de virar silenciosamente um agendamento que ninguém pediu.
 *
 * Consumida pelo compilador de recursos (chave "cron" dos manifestos) e pelo painel
 * administrativo, para que disco e interface derivem a mesma expressão.
 *
 * @param array $tarefa
 * @param string $frequencia
 * @return string|null null quando a declaração é inválida.
 */
function cron_expressao_declarada($tarefa, $frequencia){
	$explicita = isset($tarefa['expressao_cron']) ? trim((string)$tarefa['expressao_cron']) : '';
	if($explicita !== ''){
		return cron_expressao_valida($explicita) ? $explicita : null;
	}

	if($frequencia === 'customizado') return null;
	if($frequencia === 'minutario') return cron_expressao_padrao('minutario');

	// "hora": "HH:MM". Ausente, cada janela tem um horário padrão fora do pico de acesso.
	$padraoHora = ($frequencia === 'mensal') ? '04:00' : '03:00';
	$hora = trim((string)(isset($tarefa['hora']) ? $tarefa['hora'] : $padraoHora));
	if($hora === '') $hora = $padraoHora;
	if(!preg_match('#^([01][0-9]|2[0-3]):([0-5][0-9])$#', $hora, $m)) return null;
	$hh = (int)$m[1];
	$mm = (int)$m[2];

	if($frequencia === 'horario') return $mm.' * * * *';
	if($frequencia === 'diario')  return $mm.' '.$hh.' * * *';

	if($frequencia === 'mensal'){
		$dia = (int)(isset($tarefa['dia']) ? $tarefa['dia'] : 1);
		if($dia < 1 || $dia > 31) return null;
		return $mm.' '.$hh.' '.$dia.' * *';
	}

	return null;
}

/**
 * Garante que o callback declarado exista antes de invocá-lo.
 *
 * A função pode já ter sido definida por uma biblioteca carregada no bootstrap. Quando não,
 * inclui `modulos/<modulo>/<modulo>.cron.php` — mesma convenção do `<modulo>.hooks.php` já
 * usado pelo sistema de hooks. O controlador principal do módulo (`<modulo>.php`) NUNCA é
 * incluído aqui: ele termina chamando `<modulo>_start()`, que dispararia a renderização de
 * interface dentro de uma rotina de fundo.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return string|null Mensagem de erro, ou null quando o callback está disponível.
 */
function cron_callback_preparar($tarefa){
	global $_GESTOR;

	$callback = isset($tarefa['funcao_callback']) ? trim((string)$tarefa['funcao_callback']) : '';

	if($callback === ''){
		return 'Tarefa sem funcao de callback declarada.';
	}

	if(!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $callback)){
		return 'Nome de callback invalido: '.$callback;
	}

	if(function_exists($callback)) return null;

	$modulo = isset($tarefa['modulo']) ? trim((string)$tarefa['modulo']) : '';
	if($modulo !== '' && preg_match('/^[a-z0-9][a-z0-9-]*$/i', $modulo) && isset($_GESTOR['modulos-path'])){
		$arquivo = $_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.cron.php';
		if(file_exists($arquivo)) require_once($arquivo);
	}

	if(function_exists($callback)) return null;

	return 'Callback nao encontrado: '.$callback.' (esperado em modulos/'.$modulo.'/'.$modulo.'.cron.php).';
}

/**
 * Executa uma tarefa e devolve o resultado normalizado.
 *
 * A saída impressa pelo callback é capturada e vira o log da execução. Exceções e erros são
 * convertidos em status 'erro' para que UMA tarefa quebrada não interrompa as demais do tick.
 *
 * Contrato opcional do callback: devolver ['status' => 'sucesso|erro|aviso', 'log' => '...']
 * para sinalizar um aviso sem lançar exceção. Um retorno `false` também vira erro.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return array{status: string, duracao: int, log: string}
 */
function cron_tarefa_executar($tarefa){
	$inicio = microtime(true);

	$erro = cron_callback_preparar($tarefa);
	if($erro !== null){
		return Array('status' => 'erro', 'duracao' => (int)round((microtime(true)-$inicio)*1000), 'log' => $erro);
	}

	$callback = $tarefa['funcao_callback'];
	$parametros = Array();
	if(!empty($tarefa['parametros'])){
		$decodificado = json_decode((string)$tarefa['parametros'], true);
		if(is_array($decodificado)) $parametros = $decodificado;
	}

	$status = 'sucesso';
	$log = '';

	ob_start();
	try {
		$retorno = call_user_func($callback, $parametros);

		if(is_array($retorno)){
			if(isset($retorno['status']) && in_array($retorno['status'], cron_status_validos(), true)){
				$status = $retorno['status'];
			}
			if(isset($retorno['log'])) $log .= (string)$retorno['log'];
		} else if($retorno === false){
			$status = 'erro';
		}
	} catch (Throwable $e) {
		$status = 'erro';
		$log .= '[' . get_class($e) . '] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine();
	}
	$saida = ob_get_contents();
	ob_end_clean();

	if($saida !== '' && $saida !== false) $log = $saida . ($log !== '' ? "\n".$log : '');

	return Array(
		'status' => $status,
		'duracao' => (int)round((microtime(true)-$inicio)*1000),
		'log' => $log,
	);
}

/**
 * Persiste o resultado da execução na própria linha da tarefa.
 *
 * O log é truncado para não transformar a tabela de agendamento em depósito de saída: o
 * histórico completo continua no arquivo de log diário da engine.
 *
 * @param string $id
 * @param string $status
 * @param int $duracaoMs
 * @param string $log
 * @return void
 */
function cron_tarefa_registrar($id, $status, $duracaoMs, $log){
	$log = (string)$log;
	if(strlen($log) > 4000) $log = substr($log, 0, 4000).' [...]';

	// banco_update_campo() já escapa por padrão; escapar antes geraria escape duplo. Os campos
	// com $sem_aspas_simples=true recebem $escape_field=false por serem função SQL ou inteiro.
	banco_update_campo('ultimo_disparo', 'NOW()', true, false);
	banco_update_campo('ultima_duracao_ms', (int)$duracaoMs, true, false);
	banco_update_campo('ultimo_status', $status);
	banco_update_campo('ultimo_log', $log);
	banco_update_campo('data_modificacao', 'NOW()', true, false);
	banco_update_executar('cron_tarefas', "WHERE id='".banco_escape_field($id)."'");
}

/**
 * Carrega as tarefas elegíveis do banco.
 *
 * @param string|null $frequencia Janela do tick; null quando o alvo é uma tarefa específica.
 * @param string|null $tarefaId   Disparo avulso, que ignora a janela mas não o estado ativo.
 * @param bool $todas             Listagem administrativa: traz inclusive as pausadas.
 * @param array $campos           Colunas desejadas; o padrão cobre o despacho.
 * @return array
 */
function cron_tarefas_carregar($frequencia = null, $tarefaId = null, $todas = false, $campos = null){
	if(!is_array($campos) || !$campos){
		$campos = Array('id','nome','modulo','frequencia','expressao_cron','funcao_callback','parametros','ativo','origem');
	}

	$condicoes = Array("status!='D'");

	if(!$todas) $condicoes[] = "ativo=1";

	if($tarefaId !== null && $tarefaId !== ''){
		$condicoes[] = "id='".banco_escape_field($tarefaId)."'";
	} else if($frequencia !== null && $frequencia !== ''){
		$condicoes[] = "frequencia='".banco_escape_field($frequencia)."'";
	}

	$registros = banco_select_name(
		banco_campos_virgulas($campos),
		'cron_tarefas',
		'WHERE '.implode(' AND ', $condicoes).' ORDER BY modulo ASC, id ASC'
	);

	return is_array($registros) ? $registros : Array();
}

?>
