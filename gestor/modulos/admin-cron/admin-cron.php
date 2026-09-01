<?php
/**
 * Módulo administrativo de rotinas automáticas (REQ-032 / BATCH-026).
 *
 * Painel Tailwind sobre a tabela `cron_tarefas`. As tarefas chegam de duas origens:
 *  - 'modulo': declaradas na chave "cron" da raiz de `<modulo>.json` e trazidas pela
 *    sincronização (aqui, pelo botão do painel; na esteira, por `c2f resources:sync`);
 *  - 'manual': criadas neste painel, sem contraparte em disco.
 *
 * Regra de propriedade dos campos (D-038): numa tarefa de módulo, `nome`, `descricao` e
 * `funcao_callback` pertencem ao arquivo versionado e são somente leitura na interface —
 * reescrevê-los aqui produziria divergência que a próxima sincronização desfaria. Já
 * `ativo`, `expressao_cron` e `parametros` são estado operacional: podem ser ajustados no
 * painel e sobrevivem à sincronização por estarem em `preserve_on_user_modified` (D-036).
 */

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-cron';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	json_decode(file_get_contents(__DIR__ . '/admin-cron.json'), true);

// ==== Auxiliares

/**
 * Atalho para as variáveis de interface do módulo.
 */
function admin_cron_var($id){
	return gestor_variaveis(Array('modulo' => 'admin-cron', 'id' => $id));
}

/**
 * Colunas expostas ao painel. Inclui o estado de execução, que o despacho não precisa ler.
 */
function admin_cron_campos(){
	return Array(
		'id','nome','descricao','modulo','frequencia','expressao_cron','funcao_callback',
		'parametros','ativo','ultimo_disparo','ultima_duracao_ms','ultimo_status','ultimo_log',
		'origem','user_modified'
	);
}

/**
 * Lê todas as tarefas visíveis (inclusive pausadas) já normalizadas para o JSON do painel.
 */
function admin_cron_tarefas(){
	$registros = cron_tarefas_carregar(null, null, true, admin_cron_campos());

	$tarefas = Array();
	foreach($registros as $r){
		$tarefas[] = Array(
			'id' => $r['id'],
			'nome' => $r['nome'],
			'descricao' => isset($r['descricao']) ? $r['descricao'] : '',
			'modulo' => isset($r['modulo']) ? $r['modulo'] : '',
			'frequencia' => $r['frequencia'],
			'expressao_cron' => isset($r['expressao_cron']) ? $r['expressao_cron'] : '',
			'funcao_callback' => $r['funcao_callback'],
			'parametros' => isset($r['parametros']) ? $r['parametros'] : '',
			'ativo' => !empty($r['ativo']) ? 1 : 0,
			'ultimo_disparo' => isset($r['ultimo_disparo']) ? $r['ultimo_disparo'] : '',
			'ultima_duracao_ms' => isset($r['ultima_duracao_ms']) ? (int)$r['ultima_duracao_ms'] : null,
			'ultimo_status' => isset($r['ultimo_status']) ? $r['ultimo_status'] : '',
			'ultimo_log' => isset($r['ultimo_log']) ? $r['ultimo_log'] : '',
			'origem' => isset($r['origem']) ? $r['origem'] : 'modulo',
		);
	}

	return $tarefas;
}

/**
 * Localiza uma tarefa pelo identificador lógico.
 *
 * @return array|null
 */
function admin_cron_tarefa($id){
	$registros = cron_tarefas_carregar(null, $id, true, admin_cron_campos());
	return $registros ? $registros[0] : null;
}

/**
 * Resumo exibido nos cards do topo.
 *
 * O "agendador detectado" é uma inferência por EVIDÊNCIA, não uma leitura do crontab: o PHP do
 * painel não tem acesso confiável ao agendador do sistema (e no HestiaCP o crontab pertence ao
 * usuário Linux do tenant). Se alguma tarefa ativa disparou nas últimas 24h, algo externo está
 * chamando a engine — que é exatamente o que o card precisa responder.
 */
function admin_cron_estatisticas($tarefas){
	$total = count($tarefas);
	$ativas = 0;
	$execucoes = 0;
	$limite = time() - 86400;

	foreach($tarefas as $t){
		if(!empty($t['ativo'])) $ativas++;
		if(!empty($t['ultimo_disparo']) && strtotime($t['ultimo_disparo']) >= $limite) $execucoes++;
	}

	return Array(
		'total' => $total,
		'ativas' => $ativas,
		'execucoes' => $execucoes,
		'agendador' => $execucoes > 0,
	);
}

/**
 * Normaliza e valida um payload de tarefa vindo do formulário.
 *
 * @param array $dados Campos crus da requisição.
 * @return array{ok: bool, erro: ?string, dados: array}
 */
function admin_cron_validar($dados){
	$id = isset($dados['id']) ? trim((string)$dados['id']) : '';
	$nome = isset($dados['nome']) ? trim((string)$dados['nome']) : '';
	$callback = isset($dados['funcao_callback']) ? trim((string)$dados['funcao_callback']) : '';

	if($id === '' || $nome === '' || $callback === ''){
		return Array('ok' => false, 'erro' => admin_cron_var('api-error-required-fields'), 'dados' => Array());
	}

	if(!preg_match('/^[a-z0-9][a-z0-9-]*$/', $id)){
		return Array('ok' => false, 'erro' => admin_cron_var('api-error-invalid-id'), 'dados' => Array());
	}

	if(!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $callback)){
		return Array('ok' => false, 'erro' => admin_cron_var('api-error-invalid-callback'), 'dados' => Array());
	}

	$frequencia = strtolower(trim((string)(isset($dados['frequencia']) ? $dados['frequencia'] : 'diario')));
	if(!in_array($frequencia, cron_frequencias_validas(), true)){
		return Array('ok' => false, 'erro' => admin_cron_var('api-error-invalid-frequency'), 'dados' => Array());
	}

	$expressao = isset($dados['expressao_cron']) ? trim((string)$dados['expressao_cron']) : '';
	if($expressao === ''){
		$expressao = cron_expressao_padrao($frequencia);
		// 'customizado' não tem padrão derivável: sem expressão explícita a tarefa é inválida.
		if($expressao === null){
			return Array('ok' => false, 'erro' => admin_cron_var('api-error-invalid-cron'), 'dados' => Array());
		}
	} else if(!cron_expressao_valida($expressao)){
		return Array('ok' => false, 'erro' => admin_cron_var('api-error-invalid-cron'), 'dados' => Array());
	}

	$parametros = isset($dados['parametros']) ? trim((string)$dados['parametros']) : '';
	if($parametros !== ''){
		$decodificado = json_decode($parametros, true);
		if(json_last_error() !== JSON_ERROR_NONE || !is_array($decodificado)){
			return Array('ok' => false, 'erro' => admin_cron_var('api-error-invalid-parameters'), 'dados' => Array());
		}
		$parametros = json_encode($decodificado, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	}

	$modulo = isset($dados['modulo']) ? trim((string)$dados['modulo']) : '';
	if($modulo !== '' && !preg_match('/^[a-z0-9][a-z0-9-]*$/', $modulo)) $modulo = '';

	return Array(
		'ok' => true,
		'erro' => null,
		'dados' => Array(
			'id' => $id,
			'nome' => $nome,
			'descricao' => isset($dados['descricao']) ? trim((string)$dados['descricao']) : '',
			'modulo' => $modulo,
			'frequencia' => $frequencia,
			'expressao_cron' => $expressao,
			'funcao_callback' => $callback,
			'parametros' => $parametros,
			'ativo' => !empty($dados['ativo']) ? 1 : 0,
		),
	);
}

// ==== Página

function admin_cron_painel(){
	global $_GESTOR;

	gestor_pagina_javascript_incluir();

	$tarefas = admin_cron_tarefas();
	$stats = admin_cron_estatisticas($tarefas);

	$modulos = Array();
	foreach($tarefas as $t){
		if($t['modulo'] !== '' && !in_array($t['modulo'], $modulos, true)) $modulos[] = $t['modulo'];
	}
	sort($modulos);

	// O dataset vai para atributos data-*; htmlspecialchars com ENT_QUOTES escapa aspas duplas E
	// simples, impedindo que um nome de tarefa com aspas encerre o atributo mais cedo.
	$tarefasJson = htmlspecialchars(
		json_encode($tarefas, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
		ENT_QUOTES, 'UTF-8'
	);
	$modulosJson = htmlspecialchars(
		json_encode($modulos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
		ENT_QUOTES, 'UTF-8'
	);

	$agendador = $stats['agendador']
		? admin_cron_var('scheduler-detected')
		: admin_cron_var('scheduler-not-detected');

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#tarefas_json#', $tarefasJson);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#modulos_json#', $modulosJson);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#stat_total#', (string)$stats['total']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#stat_ativas#', (string)$stats['ativas']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#stat_execucoes#', (string)$stats['execucoes']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#stat_agendador#', $agendador);
}

// ==== AJAX

/**
 * Devolve a lista completa e as estatísticas, para o painel se redesenhar após uma ação.
 */
function admin_cron_ajax_tarefas(){
	global $_GESTOR;

	$tarefas = admin_cron_tarefas();

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'data' => Array(
			'tarefas' => $tarefas,
			'estatisticas' => admin_cron_estatisticas($tarefas),
		),
	);
}

/**
 * Varre os manifestos dos módulos e reconcilia a tabela com o que está declarado em disco.
 *
 * Espelha o coletor do compilador de recursos, mas grava direto no banco: o operador precisa
 * ver uma tarefa recém-declarada sem esperar o próximo deploy. Campos de estado operacional só
 * são sobrescritos quando `user_modified` = 0, para não desfazer uma pausa feita no painel.
 */
function admin_cron_ajax_sincronizar(){
	global $_GESTOR;

	$declaradas = Array();
	$modulosDir = $_GESTOR['modulos-path'];

	foreach((glob($modulosDir.'*', GLOB_ONLYDIR) ?: []) as $modPath){
		$modId = basename($modPath);
		$jsonFile = $modPath.DIRECTORY_SEPARATOR.$modId.'.json';
		if(!file_exists($jsonFile)) continue;

		$dados = json_decode(file_get_contents($jsonFile), true);
		if(!is_array($dados) || !isset($dados['cron']) || !is_array($dados['cron'])) continue;

		foreach($dados['cron'] as $tarefa){
			if(!is_array($tarefa)) continue;

			$tid = isset($tarefa['id']) ? trim((string)$tarefa['id']) : '';
			$callback = isset($tarefa['funcao']) ? trim((string)$tarefa['funcao']) : '';
			if($tid === '' || $callback === '' || isset($declaradas[$tid])) continue;

			$frequencia = strtolower(trim((string)(isset($tarefa['frequencia']) ? $tarefa['frequencia'] : 'diario')));
			if(!in_array($frequencia, cron_frequencias_validas(), true)) continue;

			$expressao = cron_expressao_declarada($tarefa, $frequencia);
			if($expressao === null) continue;

			$declaradas[$tid] = Array(
				'id' => $tid,
				'nome' => (string)(isset($tarefa['nome']) ? $tarefa['nome'] : $tid),
				'descricao' => isset($tarefa['descricao']) ? (string)$tarefa['descricao'] : '',
				'modulo' => $modId,
				'frequencia' => $frequencia,
				'expressao_cron' => $expressao,
				'funcao_callback' => $callback,
				'parametros' => (isset($tarefa['parametros']) && is_array($tarefa['parametros']))
					? json_encode($tarefa['parametros'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
					: '',
				'ativo' => (isset($tarefa['ativo']) && !$tarefa['ativo']) ? 0 : 1,
			);
		}
	}

	$existentes = Array();
	foreach(cron_tarefas_carregar(null, null, true, Array('id','origem','user_modified')) as $r){
		$existentes[$r['id']] = $r;
	}

	$inseridas = 0;
	$atualizadas = 0;

	foreach($declaradas as $tid => $t){
		if(isset($existentes[$tid])){
			// Campos de AUTORIA do módulo: sempre alinhados ao arquivo versionado.
			banco_update_campo('nome', $t['nome']);
			banco_update_campo('descricao', $t['descricao']);
			banco_update_campo('modulo', $t['modulo']);
			banco_update_campo('funcao_callback', $t['funcao_callback']);
			banco_update_campo('origem', 'modulo');
			banco_update_campo('status', 'A');

			// Estado OPERACIONAL: só volta ao valor do arquivo se ninguém o ajustou no painel.
			if(empty($existentes[$tid]['user_modified'])){
				banco_update_campo('frequencia', $t['frequencia']);
				banco_update_campo('expressao_cron', $t['expressao_cron']);
				banco_update_campo('parametros', $t['parametros']);
				banco_update_campo('ativo', (int)$t['ativo'], true, false);
			}

			banco_update_campo('data_modificacao', 'NOW()', true, false);
			banco_update_executar('cron_tarefas', "WHERE id='".banco_escape_field($tid)."'");
			$atualizadas++;
			continue;
		}

		$campos = null;
		$campos[] = Array('id', banco_escape_field($t['id']), false);
		$campos[] = Array('nome', banco_escape_field($t['nome']), false);
		$campos[] = Array('descricao', banco_escape_field($t['descricao']), false);
		$campos[] = Array('modulo', banco_escape_field($t['modulo']), false);
		$campos[] = Array('frequencia', banco_escape_field($t['frequencia']), false);
		$campos[] = Array('expressao_cron', banco_escape_field($t['expressao_cron']), false);
		$campos[] = Array('funcao_callback', banco_escape_field($t['funcao_callback']), false);
		$campos[] = Array('parametros', banco_escape_field($t['parametros']), false);
		$campos[] = Array('ativo', (int)$t['ativo'], true);
		$campos[] = Array('origem', 'modulo', false);
		$campos[] = Array('status', 'A', false);
		$campos[] = Array('versao', '1', true);
		$campos[] = Array('user_modified', '0', true);
		$campos[] = Array('data_criacao', 'NOW()', true);
		$campos[] = Array('data_modificacao', 'NOW()', true);

		banco_insert_name($campos, 'cron_tarefas');
		$inseridas++;
	}

	// Tarefas de módulo que sumiram do disco saem da listagem. As manuais são preservadas:
	// elas não têm contraparte em arquivo e não deveriam desaparecer numa sincronização.
	$removidas = 0;
	foreach($existentes as $tid => $r){
		if(($r['origem'] ?? 'modulo') !== 'modulo') continue;
		if(isset($declaradas[$tid])) continue;

		banco_update_campo('status', 'D');
		banco_update_campo('ativo', 0, true, false);
		banco_update_campo('data_modificacao', 'NOW()', true, false);
		banco_update_executar('cron_tarefas', "WHERE id='".banco_escape_field($tid)."'");
		$removidas++;
	}

	$tarefas = admin_cron_tarefas();

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'message' => admin_cron_var('msg-sync-success'),
		'data' => Array(
			'inseridas' => $inseridas,
			'atualizadas' => $atualizadas,
			'removidas' => $removidas,
			'tarefas' => $tarefas,
			'estatisticas' => admin_cron_estatisticas($tarefas),
		),
	);
}

/**
 * Executa uma tarefa imediatamente, pelo mesmo executor usado pela engine agendada.
 */
function admin_cron_ajax_disparar(){
	global $_GESTOR;

	$id = isset($_REQUEST['id']) ? trim((string)$_REQUEST['id']) : '';
	$tarefa = admin_cron_tarefa($id);

	if(!$tarefa){
		$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => admin_cron_var('api-error-task-not-found'));
		return;
	}

	$resultado = cron_tarefa_executar($tarefa);
	cron_tarefa_registrar($tarefa['id'], $resultado['status'], $resultado['duracao'], $resultado['log']);

	$tarefas = admin_cron_tarefas();

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'message' => admin_cron_var('msg-run-success'),
		'data' => Array(
			'resultado' => $resultado,
			'tarefas' => $tarefas,
			'estatisticas' => admin_cron_estatisticas($tarefas),
		),
	);
}

/**
 * Pausa ou reativa uma tarefa.
 *
 * Marca `user_modified` para que a sincronização seguinte não reverta a decisão do operador.
 */
function admin_cron_ajax_alternar(){
	global $_GESTOR;

	$id = isset($_REQUEST['id']) ? trim((string)$_REQUEST['id']) : '';
	$tarefa = admin_cron_tarefa($id);

	if(!$tarefa){
		$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => admin_cron_var('api-error-task-not-found'));
		return;
	}

	$novo = empty($tarefa['ativo']) ? 1 : 0;

	banco_update_campo('ativo', $novo, true, false);
	banco_update_campo('user_modified', 1, true, false);
	banco_update_campo('data_modificacao', 'NOW()', true, false);
	banco_update_executar('cron_tarefas', "WHERE id='".banco_escape_field($tarefa['id'])."'");

	$tarefas = admin_cron_tarefas();

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'message' => admin_cron_var('msg-toggle-success'),
		'data' => Array(
			'ativo' => $novo,
			'tarefas' => $tarefas,
			'estatisticas' => admin_cron_estatisticas($tarefas),
		),
	);
}

/**
 * Cria uma tarefa manual ou atualiza uma existente.
 */
function admin_cron_ajax_salvar(){
	global $_GESTOR;

	$original = isset($_REQUEST['id_original']) ? trim((string)$_REQUEST['id_original']) : '';
	$existente = $original !== '' ? admin_cron_tarefa($original) : null;
	$novo = ($existente === null);

	$validacao = admin_cron_validar($_REQUEST);
	if(!$validacao['ok']){
		$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => $validacao['erro']);
		return;
	}
	$dados = $validacao['dados'];

	if($novo){
		// O identificador é a chave natural da tabela: colisão precisa de erro explícito, não de
		// um INSERT que o índice único rejeitaria com mensagem de driver.
		if(admin_cron_tarefa($dados['id']) !== null){
			$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => admin_cron_var('api-error-duplicate-id'));
			return;
		}

		$campos = null;
		$campos[] = Array('id', banco_escape_field($dados['id']), false);
		$campos[] = Array('nome', banco_escape_field($dados['nome']), false);
		$campos[] = Array('descricao', banco_escape_field($dados['descricao']), false);
		$campos[] = Array('modulo', banco_escape_field($dados['modulo']), false);
		$campos[] = Array('frequencia', banco_escape_field($dados['frequencia']), false);
		$campos[] = Array('expressao_cron', banco_escape_field($dados['expressao_cron']), false);
		$campos[] = Array('funcao_callback', banco_escape_field($dados['funcao_callback']), false);
		$campos[] = Array('parametros', banco_escape_field($dados['parametros']), false);
		$campos[] = Array('ativo', (int)$dados['ativo'], true);
		$campos[] = Array('origem', 'manual', false);
		$campos[] = Array('status', 'A', false);
		$campos[] = Array('versao', '1', true);
		$campos[] = Array('user_modified', '1', true);
		$campos[] = Array('data_criacao', 'NOW()', true);
		$campos[] = Array('data_modificacao', 'NOW()', true);

		banco_insert_name($campos, 'cron_tarefas');
	} else {
		$origemModulo = (($existente['origem'] ?? 'modulo') === 'modulo');

		// Renomear o identificador é permitido em tarefa manual, mas ele é a chave natural da
		// tabela: sem esta checagem o INSERT/UPDATE bateria no índice único e o operador veria
		// uma mensagem de driver em vez do erro de negócio.
		if(!$origemModulo && $dados['id'] !== $existente['id'] && admin_cron_tarefa($dados['id']) !== null){
			$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => admin_cron_var('api-error-duplicate-id'));
			return;
		}

		// Numa tarefa de módulo, autoria pertence ao arquivo versionado (D-038). Só o estado
		// operacional é gravado; o identificador também não muda, pois é a chave do arquivo.
		if(!$origemModulo){
			banco_update_campo('id', $dados['id']);
			banco_update_campo('nome', $dados['nome']);
			banco_update_campo('descricao', $dados['descricao']);
			banco_update_campo('modulo', $dados['modulo']);
			banco_update_campo('funcao_callback', $dados['funcao_callback']);
		}

		banco_update_campo('frequencia', $dados['frequencia']);
		banco_update_campo('expressao_cron', $dados['expressao_cron']);
		banco_update_campo('parametros', $dados['parametros']);
		banco_update_campo('ativo', (int)$dados['ativo'], true, false);
		banco_update_campo('user_modified', 1, true, false);
		banco_update_campo('versao', 'versao + 1', true, false);
		banco_update_campo('data_modificacao', 'NOW()', true, false);
		banco_update_executar('cron_tarefas', "WHERE id='".banco_escape_field($existente['id'])."'");
	}

	$tarefas = admin_cron_tarefas();

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'message' => admin_cron_var('msg-save-success'),
		'data' => Array(
			'tarefas' => $tarefas,
			'estatisticas' => admin_cron_estatisticas($tarefas),
		),
	);
}

/**
 * Exclui uma tarefa manual. Tarefas de módulo só somem removendo a declaração do arquivo.
 */
function admin_cron_ajax_excluir(){
	global $_GESTOR;

	$id = isset($_REQUEST['id']) ? trim((string)$_REQUEST['id']) : '';
	$tarefa = admin_cron_tarefa($id);

	if(!$tarefa){
		$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => admin_cron_var('api-error-task-not-found'));
		return;
	}

	if(($tarefa['origem'] ?? 'modulo') === 'modulo'){
		$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => admin_cron_var('api-error-module-task-readonly'));
		return;
	}

	banco_update_campo('status', 'D');
	banco_update_campo('ativo', 0, true, false);
	banco_update_campo('data_modificacao', 'NOW()', true, false);
	banco_update_executar('cron_tarefas', "WHERE id='".banco_escape_field($tarefa['id'])."'");

	$tarefas = admin_cron_tarefas();

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'message' => admin_cron_var('msg-delete-success'),
		'data' => Array(
			'tarefas' => $tarefas,
			'estatisticas' => admin_cron_estatisticas($tarefas),
		),
	);
}

// ==== Start

function admin_cron_start(){
	global $_GESTOR;

	gestor_incluir_bibliotecas();

	if($_GESTOR['ajax']){
		interface_ajax_iniciar();

		switch($_GESTOR['ajax-opcao']){
			case 'tarefas': admin_cron_ajax_tarefas(); break;
			case 'sincronizar': admin_cron_ajax_sincronizar(); break;
			case 'disparar': admin_cron_ajax_disparar(); break;
			case 'alternar': admin_cron_ajax_alternar(); break;
			case 'salvar': admin_cron_ajax_salvar(); break;
			case 'excluir': admin_cron_ajax_excluir(); break;
		}

		interface_ajax_finalizar();
	} else {
		interface_iniciar();

		switch($_GESTOR['opcao']){
			case 'painel': admin_cron_painel(); break;
		}

		interface_finalizar();
	}
}

admin_cron_start();

?>
