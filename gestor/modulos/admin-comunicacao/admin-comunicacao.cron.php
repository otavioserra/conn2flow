<?php
/**
 * Rotina automática do módulo admin-comunicacao — REQ-304 / BATCH-313.
 *
 * Declarada na chave `cron` do manifesto (`comunicacao-fila-processar`, a cada minuto) e
 * executada pela engine de rotinas (`gestor/cron.php`) ou pelo "Disparar agora" do admin-cron.
 * Não carrega o controlador do módulo: só a biblioteca de comunicação.
 */

/** Processa a fila de mensagens: travados, pendentes vencidos até o limite e retenção. */
function admin_comunicacao_cron_fila($parametros = Array()){
	global $_GESTOR;

	require_once $_GESTOR['bibliotecas-path'].'comunicacao.php';

	return comunicacao_fila_cron(is_array($parametros) ? $parametros : Array());
}
