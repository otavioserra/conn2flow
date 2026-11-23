<?php
/**
 * Fila, histórico e contrato multi-canal da comunicação (REQ-304 / BATCH-313).
 *
 * Uma única tabela, `comunicacao_mensagens`, guarda a fila e o histórico de TODOS os canais.
 * Cada canal (`email` hoje; `whatsapp`, `telegram`, `push` no futuro) é um DRIVER:
 *
 *   [
 *     'rotulo'       => 'E-mail',                               // nome para o painel (opcional)
 *     'enviar'       => callable(array $payload, array $contexto): array,
 *                       // devolve ['ok' => bool, 'erro' => string, 'destinatario' => string,
 *                       //          'destinatario_original' => ?string]
 *     'destinatario' => callable(array $payload): string,       // resumo para a listagem
 *     'assunto'      => callable(array $payload): string,       // resumo para a listagem
 *     'finalizar'    => callable(array $payload, bool $enviado): void, // limpeza (opcional)
 *   ]
 *
 * Extensão: `hook_apply_filters('comunicacao', 'drivers', $drivers)` acrescenta ou substitui
 * drivers — mesmo padrão plug-and-play de `pagamentos-gateways.php` (REQ-301).
 *
 * Modo híbrido (padrão): envio imediato abaixo do limite por minuto; se o SMTP falhar, a
 * mensagem fica na fila com retry exponencial. `fila => true`, modo `sempre_fila` ou limite do
 * minuto atingido levam a mensagem direto para a fila, sem travar a requisição HTTP.
 *
 * O worker (`comunicacao_fila_processar`) roda pela rotina `comunicacao-fila-processar` do
 * módulo admin-comunicacao. A reserva de cada linha é um UPDATE condicional conferido pelas
 * linhas afetadas (padrão D-040): duas execuções concorrentes nunca enviam a mesma mensagem.
 *
 * Todo SQL passa por `comunicacao_fila_db()`: em produção é o banco.php (mysqli); nos testes é
 * um executor PDO/SQLite injetado em `$_GESTOR['comunicacao-fila-db']`. Por isso as datas são
 * calculadas no PHP (`comunicacao_agora()`) e o SQL evita funções específicas do MySQL.
 *
 * Segurança: o `payload_json` nunca recebe credenciais. Envios com credenciais efêmeras (teste
 * de SMTP do admin-environment, `servidor` passado pelo chamador) são imediatos e sem retry,
 * porque a senha não pode ser persistida para uma nova tentativa.
 */

define('COMUNICACAO_FILA_TABELA', 'comunicacao_mensagens');
define('COMUNICACAO_FILA_STATUS', Array('pendente', 'enviando', 'enviado', 'falhou', 'cancelado'));
define('COMUNICACAO_FILA_PRIORIDADES', Array('alta' => 1, 'normal' => 2, 'baixa' => 3));
// Espera depois da 1ª, 2ª, 3ª e 4ª falha (segundos): 1 min, 5 min, 15 min, 60 min.
define('COMUNICACAO_FILA_BACKOFF', Array(60, 300, 900, 3600));
// Reserva mais antiga que isso é de um worker que morreu no meio do envio.
define('COMUNICACAO_FILA_RESERVA_EXPIRA', 600);

// ============================================================================
// CONFIGURAÇÃO, RELÓGIO E BANCO
// ============================================================================

/**
 * Configuração normalizada da comunicação.
 *
 * Fonte: `$_CONFIG['email']` montado pelo config.php a partir do `.env` (MAIL_REDIRECT_TO,
 * EMAIL_TIMEOUT, COMUNICACAO_*), com padrões seguros quando a chave falta.
 *
 * @return array{modo:string,rate_limit:int,max_tentativas:int,retencao_dias:int,timeout:int,redirect_to:string,redirect_permitir_producao:bool,ambiente:string}
 */
function comunicacao_config(){
	global $_CONFIG, $_GESTOR;

	$email = (isset($_CONFIG['email']) && is_array($_CONFIG['email'])) ? $_CONFIG['email'] : Array();
	$fila = (isset($email['fila']) && is_array($email['fila'])) ? $email['fila'] : Array();

	$modo = strtolower(trim((string)($fila['modo'] ?? 'hibrido')));
	if(!in_array($modo, Array('hibrido', 'sempre_fila'), true)) $modo = 'hibrido';

	$ambiente = strtolower(trim((string)($email['ambiente'] ?? '')));
	if($ambiente === ''){
		$ambiente = strtolower(trim((string)($_ENV['APP_ENV'] ?? '')));
	}
	if($ambiente === ''){
		// Sem APP_ENV, só um DEVELOPMENT_ENV explícito tira a instalação de produção: na dúvida,
		// o redirecionamento de teste fica travado.
		$ambiente = !empty($_GESTOR['development-env']) ? 'development' : 'production';
	}

	return Array(
		'modo' => $modo,
		'rate_limit' => max(1, (int)($fila['rate_limit'] ?? 30)),
		'max_tentativas' => max(1, (int)($fila['max_tentativas'] ?? 4)),
		'retencao_dias' => max(0, (int)($fila['retencao_dias'] ?? 90)),
		'timeout' => max(1, (int)($email['timeout'] ?? 15)),
		'redirect_to' => trim((string)($email['redirect_to'] ?? '')),
		'redirect_permitir_producao' => !empty($email['redirect_permitir_producao']),
		'ambiente' => $ambiente,
	);
}

/** Instante atual (timestamp). Os testes injetam um relógio em `$_GESTOR['comunicacao-relogio']`. */
function comunicacao_agora(){
	global $_GESTOR;
	if(isset($_GESTOR['comunicacao-relogio']) && is_callable($_GESTOR['comunicacao-relogio'])){
		return (int)call_user_func($_GESTOR['comunicacao-relogio']);
	}
	return time();
}

/** Data no formato DATETIME a partir de um timestamp. */
function comunicacao_data($timestamp){
	return date('Y-m-d H:i:s', (int)$timestamp);
}

/**
 * Executor SQL da fila.
 *
 * Contrato: `consultar(sql): array` (linhas associativas), `executar(sql): int|false` (linhas
 * afetadas), `inserir(sql): int|false` (id gerado), `escapar(valor): string`,
 * `disponivel(): bool` (a tabela existe). O executor deve manter a mesma conexão durante
 * GET_LOCK, a contagem, a reserva e RELEASE_LOCK.
 */
function comunicacao_fila_db(){
	global $_GESTOR;

	if(isset($_GESTOR['comunicacao-fila-db']) && is_array($_GESTOR['comunicacao-fila-db'])){
		return $_GESTOR['comunicacao-fila-db'];
	}

	return Array(
		'consultar' => function($sql){
			$res = banco_query($sql);
			$linhas = Array();
			if($res && $res !== true){
				while($linha = banco_fetch_assoc($res)) $linhas[] = $linha;
			}
			return $linhas;
		},
		'executar' => function($sql){
			$res = banco_query($sql);
			if($res === false) return false;
			$afetadas = banco_linhas_afetadas();
			return $afetadas === null ? 0 : (int)$afetadas;
		},
		'inserir' => function($sql){
			$res = banco_query($sql);
			if($res === false) return false;
			return (int)banco_last_id();
		},
		'escapar' => function($valor){
			return banco_escape_field((string)$valor);
		},
		'disponivel' => function(){
			$res = @banco_query("SHOW TABLES LIKE '".COMUNICACAO_FILA_TABELA."'");
			return $res && $res !== true && banco_num_rows($res) > 0;
		},
	);
}

/** Atalhos do executor. */
function comunicacao_fila_consultar($sql){ $db = comunicacao_fila_db(); return (array)call_user_func($db['consultar'], $sql); }
function comunicacao_fila_executar($sql){ $db = comunicacao_fila_db(); return call_user_func($db['executar'], $sql); }
function comunicacao_fila_inserir_sql($sql){ $db = comunicacao_fila_db(); return call_user_func($db['inserir'], $sql); }

/** Serializa apenas a admissão na fila de envios desta base, nunca a chamada ao driver. */
function comunicacao_fila_com_trava_minuto($operacao){
	$nome = "CONCAT('c2f.comunicacao.', SHA1(DATABASE()))";
	$trava = comunicacao_fila_consultar("SELECT GET_LOCK(".$nome.", 5) AS adquirida");
	if((int)($trava[0]['adquirida'] ?? 0) !== 1) return null;

	try {
		return $operacao(comunicacao_agora());
	} finally {
		comunicacao_fila_consultar("SELECT RELEASE_LOCK(".$nome.")");
	}
}

/** Valor SQL entre aspas, ou NULL. */
function comunicacao_fila_valor($valor){
	if($valor === null) return 'NULL';
	$db = comunicacao_fila_db();
	return "'".call_user_func($db['escapar'], (string)$valor)."'";
}

/**
 * A tabela da fila existe nesta instalação?
 *
 * Antes da migration, o envio segue o caminho síncrono antigo: atualizar o código sem rodar a
 * migration não pode derrubar e-mails.
 */
function comunicacao_fila_disponivel(){
	global $_GESTOR;

	if(isset($_GESTOR['comunicacao-fila-disponivel'])) return (bool)$_GESTOR['comunicacao-fila-disponivel'];

	$db = comunicacao_fila_db();
	$disponivel = false;
	try {
		$disponivel = isset($db['disponivel']) ? (bool)call_user_func($db['disponivel']) : true;
	} catch (Throwable $e) {
		$disponivel = false;
	}

	$_GESTOR['comunicacao-fila-disponivel'] = $disponivel;
	return $disponivel;
}

// ============================================================================
// SEGREDOS
// ============================================================================

/**
 * Substitui por `***` todo valor cuja CHAVE indique credencial (pass, senha, password, secret,
 * token, EMAIL_PASS...), em qualquer profundidade. Usado antes de logar ou persistir.
 */
function comunicacao_mascarar_segredos($dados){
	if(!is_array($dados)) return $dados;

	foreach($dados as $chave => $valor){
		if(is_string($chave) && preg_match('/(pass|senha|password|secret|token)/i', $chave)){
			$dados[$chave] = ($valor === null || $valor === '') ? $valor : '***';
		} else if(is_array($valor)){
			$dados[$chave] = comunicacao_mascarar_segredos($valor);
		}
	}

	return $dados;
}

// ============================================================================
// DRIVERS
// ============================================================================

/** Catálogo de drivers registrados (nativos + extensões por hook). */
function comunicacao_drivers(){
	$drivers = Array(
		'email' => Array(
			'rotulo' => 'E-mail',
			'enviar' => 'comunicacao_driver_email_enviar',
			'destinatario' => 'comunicacao_driver_email_destinatario',
			'assunto' => 'comunicacao_driver_email_assunto',
			'finalizar' => 'comunicacao_driver_email_finalizar',
		),
	);

	if(function_exists('hook_apply_filters')){
		$filtrados = hook_apply_filters('comunicacao', 'drivers', $drivers);
		if(is_array($filtrados)) $drivers = $filtrados;
	}

	$validos = Array();
	foreach($drivers as $canal => $driver){
		$canal = strtolower((string)$canal);
		if(!preg_match('/^[a-z0-9_-]{1,32}$/', $canal) || !is_array($driver)) continue;
		if(!isset($driver['enviar']) || !is_callable($driver['enviar'])) continue;
		$validos[$canal] = $driver;
	}
	return $validos;
}

/** Driver de um canal; null quando não registrado. */
function comunicacao_driver($canal){
	$drivers = comunicacao_drivers();
	$canal = strtolower((string)$canal);
	return $drivers[$canal] ?? null;
}

/** Chama uma operação opcional do driver; `$padrao` quando ela não existe. */
function comunicacao_driver_chamar($driver, $operacao, $padrao, ...$args){
	$fn = $driver[$operacao] ?? null;
	if(!is_callable($fn)) return $padrao;
	return $fn(...$args);
}

/** Resultado de envio normalizado. */
function comunicacao_resultado_normalizar($resultado){
	if($resultado === true) return Array('ok' => true, 'erro' => '');
	if(!is_array($resultado)) return Array('ok' => false, 'erro' => is_string($resultado) ? $resultado : 'Falha no envio');
	$resultado['ok'] = !empty($resultado['ok']);
	$resultado['erro'] = (string)($resultado['erro'] ?? '');
	return $resultado;
}

// ============================================================================
// FACHADA MULTI-CANAL
// ============================================================================

/**
 * Envia (ou enfileira) uma mensagem por um canal.
 *
 * @param string $canal 'email' ou um canal registrado por hook.
 * @param array $payload Conteúdo persistível da mensagem (SEM credenciais).
 * @param array $opcoes
 *   - fila (bool): força a fila, sem tentar agora.
 *   - prioridade ('alta'|'normal'|'baixa').
 *   - chave_idempotencia (string): uma segunda chamada com a mesma chave não gera novo envio.
 *   - id_hosts (int|null).
 *   - somente_imediato (bool): credencial efêmera — envia agora, grava o histórico, sem retry.
 *   - contexto (array): dados NÃO persistidos repassados ao driver (ex.: servidor SMTP de teste).
 *   - max_tentativas (int).
 * @return array ['aceito' => bool, 'status' => string, 'id' => ?int, 'erro' => string, 'duplicado' => bool]
 */
function comunicacao_enviar($canal, $payload, $opcoes = Array()){
	$canal = strtolower((string)$canal);
	$driver = comunicacao_driver($canal);
	if(!$driver){
		return Array('aceito' => false, 'status' => 'falhou', 'id' => null, 'erro' => 'Canal sem driver: '.$canal, 'duplicado' => false);
	}

	$payload = is_array($payload) ? $payload : Array();
	$contexto = (isset($opcoes['contexto']) && is_array($opcoes['contexto'])) ? $opcoes['contexto'] : Array();

	// Sem a tabela (migration ainda não aplicada), comportamento antigo: envio direto.
	if(!comunicacao_fila_disponivel()){
		$r = comunicacao_resultado_normalizar(call_user_func($driver['enviar'], $payload, $contexto));
		comunicacao_driver_chamar($driver, 'finalizar', null, $payload, $r['ok']);
		return Array('aceito' => $r['ok'], 'status' => $r['ok'] ? 'enviado' : 'falhou', 'id' => null, 'erro' => $r['erro'], 'duplicado' => false);
	}

	$config = comunicacao_config();
	$agora = comunicacao_agora();

	$chave = isset($opcoes['chave_idempotencia']) ? trim((string)$opcoes['chave_idempotencia']) : '';
	if($chave !== ''){
		$chave = substr($chave, 0, 128);
		$existente = comunicacao_fila_consultar(
			"SELECT id_comunicacao_mensagens, status FROM ".COMUNICACAO_FILA_TABELA
			." WHERE chave_idempotencia=".comunicacao_fila_valor($chave)." LIMIT 1"
		);
		if($existente){
			$status = (string)$existente[0]['status'];
			return Array('aceito' => $status !== 'falhou' && $status !== 'cancelado', 'status' => $status, 'id' => (int)$existente[0]['id_comunicacao_mensagens'], 'erro' => '', 'duplicado' => true);
		}
	}

	$prioridadeNome = strtolower((string)($opcoes['prioridade'] ?? 'normal'));
	$prioridade = COMUNICACAO_FILA_PRIORIDADES[$prioridadeNome] ?? COMUNICACAO_FILA_PRIORIDADES['normal'];
	$maxTentativas = isset($opcoes['max_tentativas']) ? max(1, (int)$opcoes['max_tentativas']) : $config['max_tentativas'];
	$somenteImediato = !empty($opcoes['somente_imediato']);

	$enfileirar = !$somenteImediato && (!empty($opcoes['fila']) || $config['modo'] === 'sempre_fila');

	$destinatario = (string)comunicacao_driver_chamar($driver, 'destinatario', '', $payload);
	$assunto = (string)comunicacao_driver_chamar($driver, 'assunto', '', $payload);
	$redirecionarPara = comunicacao_redirecionamento_destino(false);

	$dados = Array(
		'id_hosts' => isset($opcoes['id_hosts']) && $opcoes['id_hosts'] !== '' ? (int)$opcoes['id_hosts'] : null,
		'canal' => $canal,
		'prioridade' => $prioridade,
		'destinatario' => $redirecionarPara !== '' && $canal === 'email' ? $redirecionarPara : $destinatario,
		'destinatario_original' => $redirecionarPara !== '' && $canal === 'email' ? $destinatario : null,
		'assunto' => $assunto,
		'payload' => $payload,
		'chave_idempotencia' => $chave !== '' ? $chave : null,
		'max_tentativas' => $somenteImediato ? 1 : $maxTentativas,
	);
	$reserva = $enfileirar ? null : comunicacao_fila_com_trava_minuto(
		function($agora) use ($dados, $config){
			$usados = comunicacao_fila_envios_no_minuto($agora);
			if($usados === null || $usados >= $config['rate_limit']) return null;
			$reservadoEm = comunicacao_data($agora);
			return Array(
				'id' => comunicacao_fila_inserir($dados + Array(
					'status' => 'enviando', 'proxima_tentativa_em' => null,
					'reservado_em' => $reservadoEm, 'agora' => $agora,
				)),
				'reservado_em' => $reservadoEm,
			);
		}
	);
	$enfileirar = $reserva === null && !$somenteImediato;
	$id = $enfileirar
		? comunicacao_fila_inserir($dados + Array(
			'status' => 'pendente', 'proxima_tentativa_em' => comunicacao_data($agora),
			'reservado_em' => null, 'agora' => $agora,
		))
		: ($reserva['id'] ?? null);

	if(!$id && $chave !== ''){
		// Corrida entre duas chamadas com a mesma chave: o índice único barrou a segunda. Ela é
		// duplicata, nunca um motivo para enviar por fora da fila.
		$existente = comunicacao_fila_consultar(
			"SELECT id_comunicacao_mensagens, status FROM ".COMUNICACAO_FILA_TABELA
			." WHERE chave_idempotencia=".comunicacao_fila_valor($chave)." LIMIT 1"
		);
		if($existente){
			return Array('aceito' => true, 'status' => (string)$existente[0]['status'], 'id' => (int)$existente[0]['id_comunicacao_mensagens'], 'erro' => '', 'duplicado' => true);
		}
	}

	if(!$id){
		comunicacao_driver_chamar($driver, 'finalizar', null, $payload, false);
		$erro = $somenteImediato && $reserva === null ? 'Limite de envio indisponível.' : 'Não foi possível registrar a mensagem.';
		return Array('aceito' => false, 'status' => 'falhou', 'id' => null, 'erro' => $erro, 'duplicado' => false);
	}

	if($enfileirar){
		return Array('aceito' => true, 'status' => 'pendente', 'id' => $id, 'erro' => '', 'duplicado' => false);
	}

	$final = comunicacao_fila_tentar($id, $canal, $payload, 0, $somenteImediato ? 1 : $maxTentativas, $reserva['reservado_em'], $contexto);
	if($final === null){
		return Array('aceito' => false, 'status' => 'falhou', 'id' => $id, 'erro' => 'Reserva de envio perdida.', 'duplicado' => false);
	}

	return Array(
		'aceito' => $final['status'] !== 'falhou',
		'status' => $final['status'],
		'id' => $id,
		'erro' => $final['erro'],
		'duplicado' => false,
	);
}

/**
 * Grava uma nova linha na fila/histórico.
 *
 * @return int|false id gerado.
 */
function comunicacao_fila_inserir($dados){
	$agora = comunicacao_data($dados['agora'] ?? comunicacao_agora());

	$payload = comunicacao_mascarar_segredos(is_array($dados['payload'] ?? null) ? $dados['payload'] : Array());
	$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
	if($json === false) $json = '{}';

	$colunas = Array(
		'id_hosts' => $dados['id_hosts'] === null ? 'NULL' : (string)(int)$dados['id_hosts'],
		'canal' => comunicacao_fila_valor($dados['canal']),
		'status' => comunicacao_fila_valor($dados['status']),
		'prioridade' => (string)(int)$dados['prioridade'],
		'destinatario' => comunicacao_fila_valor(mb_substr((string)$dados['destinatario'], 0, 255)),
		'destinatario_original' => $dados['destinatario_original'] === null ? 'NULL' : comunicacao_fila_valor(mb_substr((string)$dados['destinatario_original'], 0, 255)),
		'assunto' => comunicacao_fila_valor(mb_substr((string)$dados['assunto'], 0, 255)),
		'payload_json' => comunicacao_fila_valor($json),
		'chave_idempotencia' => comunicacao_fila_valor($dados['chave_idempotencia']),
		'tentativas' => '0',
		'max_tentativas' => (string)(int)$dados['max_tentativas'],
		'proxima_tentativa_em' => comunicacao_fila_valor($dados['proxima_tentativa_em']),
		'reservado_em' => comunicacao_fila_valor($dados['reservado_em']),
		'data_criacao' => comunicacao_fila_valor($agora),
		'data_modificacao' => comunicacao_fila_valor($agora),
	);

	$sql = "INSERT INTO ".COMUNICACAO_FILA_TABELA." (".implode(',', array_keys($colunas)).") VALUES (".implode(',', $colunas).")";

	try {
		$id = comunicacao_fila_inserir_sql($sql);
	} catch (Throwable $e) {
		return false;
	}
	return $id ? (int)$id : false;
}

/**
 * Executa UMA tentativa de envio de uma linha já reservada (status 'enviando') e grava o
 * resultado: enviado, de volta para a fila com backoff, ou falhou ao esgotar as tentativas.
 *
 * @return array|null null se a reserva expirou e foi retomada por outra execução
 */
function comunicacao_fila_tentar($id, $canal, $payload, $tentativasAntes, $maxTentativas, $reservadoEm, $contexto = Array()){
	$driver = comunicacao_driver($canal);

	if(!$driver){
		$r = Array('ok' => false, 'erro' => 'Canal sem driver: '.$canal);
	} else {
		try {
			$r = comunicacao_resultado_normalizar(call_user_func($driver['enviar'], $payload, $contexto));
		} catch (Throwable $e) {
			$r = Array('ok' => false, 'erro' => get_class($e).': '.$e->getMessage());
		}
	}

	$agora = comunicacao_agora();
	$tentativas = (int)$tentativasAntes + 1;
	$sets = Array(
		"tentativas=".$tentativas,
		"data_modificacao=".comunicacao_fila_valor(comunicacao_data($agora)),
	);

	if(!empty($r['destinatario'])){
		$sets[] = "destinatario=".comunicacao_fila_valor(mb_substr((string)$r['destinatario'], 0, 255));
		$sets[] = "destinatario_original=".(empty($r['destinatario_original']) ? 'NULL' : comunicacao_fila_valor(mb_substr((string)$r['destinatario_original'], 0, 255)));
	}

	if($r['ok']){
		$status = 'enviado';
		$sets[] = "status='enviado'";
		$sets[] = "enviado_em=".comunicacao_fila_valor(comunicacao_data($agora));
		$sets[] = "proxima_tentativa_em=NULL";
		$sets[] = "erro_ultimo=NULL";
	} else if($tentativas >= (int)$maxTentativas){
		$status = 'falhou';
		$sets[] = "status='falhou'";
		$sets[] = "proxima_tentativa_em=NULL";
		$sets[] = "erro_ultimo=".comunicacao_fila_valor(mb_substr($r['erro'], 0, 2000));
	} else {
		$status = 'pendente';
		$sets[] = "status='pendente'";
		$sets[] = "proxima_tentativa_em=".comunicacao_fila_valor(comunicacao_data($agora + comunicacao_fila_backoff($tentativas)));
		$sets[] = "erro_ultimo=".comunicacao_fila_valor(mb_substr($r['erro'], 0, 2000));
	}

	$afetadas = comunicacao_fila_executar(
		"UPDATE ".COMUNICACAO_FILA_TABELA." SET ".implode(', ', $sets)
		." WHERE id_comunicacao_mensagens=".(int)$id." AND status='enviando'"
		." AND reservado_em=".comunicacao_fila_valor($reservadoEm)." AND tentativas=".(int)$tentativasAntes
	);
	if((int)$afetadas !== 1) return null;

	if($driver && $status !== 'pendente'){
		comunicacao_driver_chamar($driver, 'finalizar', null, $payload, $status === 'enviado');
	}

	return Array('status' => $status, 'erro' => $r['ok'] ? '' : $r['erro']);
}

/** Espera (segundos) antes da próxima tentativa depois de `$tentativas` falhas. */
function comunicacao_fila_backoff($tentativas){
	$tabela = COMUNICACAO_FILA_BACKOFF;
	$indice = max(1, (int)$tentativas) - 1;
	return $tabela[min($indice, count($tabela) - 1)];
}

/**
 * Tentativas de envio no minuto corrente (imediatos + worker). Toda tentativa grava
 * `reservado_em`, então a contagem cobre sucesso, falha e envio em andamento.
 * @return int|null null quando a contagem não está disponível
 */
function comunicacao_fila_envios_no_minuto($agora = null){
	$agora = $agora === null ? comunicacao_agora() : (int)$agora;
	$inicio = $agora - ($agora % 60);
	$linhas = comunicacao_fila_consultar(
		"SELECT COUNT(*) AS total FROM ".COMUNICACAO_FILA_TABELA
		." WHERE reservado_em >= ".comunicacao_fila_valor(comunicacao_data($inicio))
	);
	return isset($linhas[0]['total']) ? (int)$linhas[0]['total'] : null;
}

// ============================================================================
// WORKER
// ============================================================================

/**
 * Processa a fila: recupera travados, envia pendentes vencidos até o limite do minuto e aplica
 * a retenção. Chamado pela rotina agendada a cada minuto.
 *
 * @param array $opcoes ['limite' => int (teto extra por execução), 'retencao' => bool]
 * @return array contadores
 */
function comunicacao_fila_processar($opcoes = Array()){
	$resumo = Array('recuperadas' => 0, 'esgotadas' => 0, 'processadas' => 0, 'enviadas' => 0, 'reagendadas' => 0, 'falhas' => 0, 'ignoradas' => 0, 'removidas' => 0, 'limite_restante' => 0);

	if(!comunicacao_fila_disponivel()) return $resumo;

	$config = comunicacao_config();
	$agora = comunicacao_agora();

	$travados = comunicacao_fila_recuperar_travados($agora);
	$resumo['recuperadas'] = $travados['recuperadas'];
	$resumo['esgotadas'] = $travados['esgotadas'];

	$capacidade = $config['rate_limit'];
	if(isset($opcoes['limite'])) $capacidade = min($capacidade, (int)$opcoes['limite']);

	if($capacidade > 0){
		$candidatas = comunicacao_fila_consultar(
			"SELECT id_comunicacao_mensagens FROM ".COMUNICACAO_FILA_TABELA
			." WHERE status='pendente' AND (proxima_tentativa_em IS NULL OR proxima_tentativa_em <= ".comunicacao_fila_valor(comunicacao_data($agora)).")"
			." ORDER BY prioridade ASC, proxima_tentativa_em ASC, id_comunicacao_mensagens ASC"
			." LIMIT ".(int)$capacidade
		);

		foreach($candidatas as $c){
			$id = (int)$c['id_comunicacao_mensagens'];
			$r = comunicacao_fila_processar_mensagem($id);
			if($r === null){ $resumo['ignoradas']++; continue; }

			$resumo['processadas']++;
			if($r['status'] === 'enviado') $resumo['enviadas']++;
			else if($r['status'] === 'pendente') $resumo['reagendadas']++;
			else $resumo['falhas']++;
		}
	}

	$usados = comunicacao_fila_envios_no_minuto();
	$resumo['limite_restante'] = $usados === null ? 0 : max(0, $config['rate_limit'] - $usados);

	if(!isset($opcoes['retencao']) || $opcoes['retencao']){
		$resumo['removidas'] = comunicacao_fila_aplicar_retencao($agora, $config['retencao_dias']);
	}

	return $resumo;
}

/**
 * Reserva atômica e envio de uma linha pendente.
 *
 * @return array|null null quando outra execução já reservou a linha.
 */
function comunicacao_fila_processar_mensagem($id){
	$id = (int)$id;
	$limite = comunicacao_config()['rate_limit'];

	$linha = comunicacao_fila_com_trava_minuto(function($agora) use ($id, $limite){
		$usados = comunicacao_fila_envios_no_minuto($agora);
		if($usados === null || $usados >= $limite) return null;

		$agoraSql = comunicacao_fila_valor(comunicacao_data($agora));
		$afetadas = comunicacao_fila_executar(
			"UPDATE ".COMUNICACAO_FILA_TABELA
			." SET status='enviando', reservado_em=".$agoraSql.", data_modificacao=".$agoraSql
			." WHERE id_comunicacao_mensagens=".$id." AND status='pendente'"
			." AND (proxima_tentativa_em IS NULL OR proxima_tentativa_em <= ".$agoraSql.")"
		);
		return (int)$afetadas === 1 ? comunicacao_fila_obter($id) : null;
	});
	if(!$linha) return null;

	$payload = json_decode((string)$linha['payload_json'], true);
	if(!is_array($payload)) $payload = Array();

	return comunicacao_fila_tentar($id, (string)$linha['canal'], $payload, (int)$linha['tentativas'], (int)$linha['max_tentativas'], $linha['reservado_em']);
}

/**
 * Reservas expiradas (worker interrompido) voltam para a fila, contando a tentativa perdida;
 * sem tentativas restantes, viram falha.
 *
 * @return array ['recuperadas' => int, 'esgotadas' => int]
 */
function comunicacao_fila_recuperar_travados($agora = null){
	$agora = $agora === null ? comunicacao_agora() : (int)$agora;
	$limite = comunicacao_fila_valor(comunicacao_data($agora - COMUNICACAO_FILA_RESERVA_EXPIRA));
	$agoraSql = comunicacao_fila_valor(comunicacao_data($agora));
	$erro = comunicacao_fila_valor('Reserva expirada: o envio foi interrompido antes de terminar.');

	$esgotadas = comunicacao_fila_executar(
		"UPDATE ".COMUNICACAO_FILA_TABELA
		." SET status='falhou', tentativas=tentativas+1, proxima_tentativa_em=NULL, erro_ultimo=".$erro.", data_modificacao=".$agoraSql
		." WHERE status='enviando' AND reservado_em < ".$limite." AND tentativas+1 >= max_tentativas"
	);

	$recuperadas = comunicacao_fila_executar(
		"UPDATE ".COMUNICACAO_FILA_TABELA
		." SET status='pendente', tentativas=tentativas+1, proxima_tentativa_em=".$agoraSql.", erro_ultimo=".$erro.", data_modificacao=".$agoraSql
		." WHERE status='enviando' AND reservado_em < ".$limite
	);

	return Array('recuperadas' => (int)$recuperadas, 'esgotadas' => (int)$esgotadas);
}

/**
 * Remove do histórico mensagens enviadas/canceladas mais antigas que `$dias` (0 desliga).
 * Retenção pedida pela REQ-304: falhas e pendentes nunca são removidas automaticamente.
 */
function comunicacao_fila_aplicar_retencao($agora, $dias){
	$dias = (int)$dias;
	if($dias <= 0) return 0;

	$limite = comunicacao_fila_valor(comunicacao_data((int)$agora - $dias * 86400));
	$inicioMinuto = comunicacao_fila_valor(comunicacao_data($agora - ($agora % 60)));
	$removidas = comunicacao_fila_executar(
		"DELETE FROM ".COMUNICACAO_FILA_TABELA
		." WHERE status IN ('enviado','cancelado') AND data_criacao < ".$limite
		." AND (reservado_em IS NULL OR reservado_em < ".$inicioMinuto.")"
	);
	return (int)$removidas;
}

/** Saída da rotina agendada (contrato do cron: status + log). */
function comunicacao_fila_cron($parametros = Array()){
	if(!comunicacao_fila_disponivel()){
		return Array('status' => 'aviso', 'log' => 'Tabela comunicacao_mensagens ausente: rode a migration.');
	}

	$r = comunicacao_fila_processar(is_array($parametros) ? $parametros : Array());

	$partes = Array();
	foreach($r as $chave => $valor) $partes[] = $chave.'='.$valor;

	return Array(
		'status' => ($r['falhas'] > 0 || $r['esgotadas'] > 0) ? 'aviso' : 'sucesso',
		'log' => implode(' ', $partes),
	);
}

// ============================================================================
// CONSULTAS E AÇÕES DO PAINEL
// ============================================================================

/** Uma linha completa da fila. */
function comunicacao_fila_obter($id){
	$linhas = comunicacao_fila_consultar(
		"SELECT * FROM ".COMUNICACAO_FILA_TABELA." WHERE id_comunicacao_mensagens=".(int)$id." LIMIT 1"
	);
	return $linhas ? $linhas[0] : null;
}

/**
 * Listagem paginada com filtros.
 *
 * @param array $filtros ['status','canal','busca','data_de' (Y-m-d),'data_ate' (Y-m-d),'pagina','por_pagina']
 * @return array ['itens' => array, 'total' => int, 'pagina' => int, 'paginas' => int, 'por_pagina' => int]
 */
function comunicacao_fila_listar($filtros = Array()){
	$where = Array('1=1');

	$status = (string)($filtros['status'] ?? '');
	if($status !== '' && in_array($status, COMUNICACAO_FILA_STATUS, true)) $where[] = "status=".comunicacao_fila_valor($status);

	$canal = strtolower((string)($filtros['canal'] ?? ''));
	if($canal !== '' && preg_match('/^[a-z0-9_-]{1,32}$/', $canal)) $where[] = "canal=".comunicacao_fila_valor($canal);

	$busca = trim((string)($filtros['busca'] ?? ''));
	if($busca !== ''){
		$like = comunicacao_fila_valor('%'.str_replace(Array('%', '_'), Array('', ''), mb_substr($busca, 0, 100)).'%');
		$where[] = "(destinatario LIKE ".$like." OR destinatario_original LIKE ".$like." OR assunto LIKE ".$like.")";
	}

	foreach(Array('data_de' => '>=', 'data_ate' => '<=') as $campo => $op){
		$data = (string)($filtros[$campo] ?? '');
		if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)){
			$where[] = "data_criacao ".$op." ".comunicacao_fila_valor($data.($op === '>=' ? ' 00:00:00' : ' 23:59:59'));
		}
	}

	$porPagina = max(1, min(100, (int)($filtros['por_pagina'] ?? 20)));
	$pagina = max(1, (int)($filtros['pagina'] ?? 1));
	$condicao = implode(' AND ', $where);

	$total = comunicacao_fila_consultar("SELECT COUNT(*) AS total FROM ".COMUNICACAO_FILA_TABELA." WHERE ".$condicao);
	$total = $total ? (int)$total[0]['total'] : 0;
	$paginas = max(1, (int)ceil($total / $porPagina));
	$pagina = min($pagina, $paginas);

	$itens = comunicacao_fila_consultar(
		"SELECT id_comunicacao_mensagens, canal, status, prioridade, destinatario, destinatario_original, assunto,"
		." tentativas, max_tentativas, proxima_tentativa_em, erro_ultimo, enviado_em, data_criacao"
		." FROM ".COMUNICACAO_FILA_TABELA." WHERE ".$condicao
		." ORDER BY id_comunicacao_mensagens DESC LIMIT ".$porPagina." OFFSET ".(($pagina - 1) * $porPagina)
	);

	return Array('itens' => $itens, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas, 'por_pagina' => $porPagina);
}

/** Indicadores do painel. */
function comunicacao_fila_estatisticas(){
	$config = comunicacao_config();
	$agora = comunicacao_agora();
	$hoje = comunicacao_fila_valor(date('Y-m-d 00:00:00', $agora));

	$contar = function($condicao){
		$l = comunicacao_fila_consultar("SELECT COUNT(*) AS total FROM ".COMUNICACAO_FILA_TABELA." WHERE ".$condicao);
		return $l ? (int)$l[0]['total'] : 0;
	};

	return Array(
		'enviados_hoje' => $contar("status='enviado' AND enviado_em >= ".$hoje),
		'na_fila' => $contar("status IN ('pendente','enviando')"),
		'falhas' => $contar("status='falhou'"),
		'limite_minuto' => $config['rate_limit'],
		'usados_minuto' => comunicacao_fila_envios_no_minuto($agora) ?? 0,
	);
}

/** Cancela uma mensagem que ainda está na fila. */
function comunicacao_fila_cancelar($id){
	$agora = comunicacao_fila_valor(comunicacao_data(comunicacao_agora()));
	$afetadas = comunicacao_fila_executar(
		"UPDATE ".COMUNICACAO_FILA_TABELA." SET status='cancelado', proxima_tentativa_em=NULL, data_modificacao=".$agora
		." WHERE id_comunicacao_mensagens=".(int)$id." AND status='pendente'"
	);
	if((int)$afetadas === 1){
		$linha = comunicacao_fila_obter($id);
		$driver = $linha ? comunicacao_driver((string)$linha['canal']) : null;
		if($driver){
			$payload = json_decode((string)$linha['payload_json'], true);
			comunicacao_driver_chamar($driver, 'finalizar', null, is_array($payload) ? $payload : Array(), false);
		}
		return true;
	}
	return false;
}

/**
 * Reenvio manual de uma mensagem enviada ou com falha: cria uma NOVA linha com o mesmo
 * conteúdo (o histórico da original fica intacto) e tenta enviar agora, respeitando o limite.
 *
 * @return array resultado de comunicacao_enviar(), ou ['aceito' => false, 'erro' => ...]
 */
function comunicacao_fila_reenviar($id){
	$linha = comunicacao_fila_obter($id);
	if(!$linha || !in_array((string)$linha['status'], Array('falhou', 'enviado'), true)){
		return Array('aceito' => false, 'status' => '', 'id' => null, 'erro' => 'status', 'duplicado' => false);
	}

	$payload = json_decode((string)$linha['payload_json'], true);
	if(!is_array($payload)) $payload = Array();
	// O reenvio não apaga anexos temporários: eles já foram consumidos pelo envio original.
	unset($payload['tmp_files']);

	return comunicacao_enviar((string)$linha['canal'], $payload, Array(
		'prioridade' => 'alta',
		'id_hosts' => $linha['id_hosts'] ?? null,
	));
}

// ============================================================================
// DRIVER NATIVO: EMAIL
// ============================================================================

/** Envia o payload de e-mail com o servidor do contexto (efêmero) ou o configurado. */
function comunicacao_driver_email_enviar($payload, $contexto = Array()){
	if(isset($contexto['server']) && is_array($contexto['server'])){
		$server = $contexto['server'];
	} else {
		$resolvido = comunicacao_email_servidor($payload['id_hosts'] ?? null, !empty($payload['hostPersonalizacao']));
		$server = $resolvido['server'];
	}
	if(isset($payload['debug'])) $server['debug'] = (bool)$payload['debug'];

	return comunicacao_email_transmitir($payload, $server);
}

/** Destinatários (TO, CC, BCC) para a listagem. */
function comunicacao_driver_email_destinatario($payload){
	$emails = Array();
	foreach((array)($payload['recipients'] ?? Array()) as $r){
		if(!empty($r['email'])) $emails[] = (string)$r['email'];
	}
	return implode(', ', array_values(array_unique($emails)));
}

function comunicacao_driver_email_assunto($payload){
	return (string)($payload['message']['subject'] ?? '');
}

/** Apaga anexos/imagens temporários quando a mensagem chega a um estado final. */
function comunicacao_driver_email_finalizar($payload, $enviado){
	foreach((array)($payload['tmp_files'] ?? Array()) as $tmp){
		if(is_string($tmp) && $tmp !== '' && is_file($tmp)) @unlink($tmp);
	}
}

?>
