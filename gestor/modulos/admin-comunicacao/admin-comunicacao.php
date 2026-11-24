<?php
/**
 * Módulo administrativo de comunicação (REQ-304 / BATCH-313).
 *
 * Painel Tailwind sobre `comunicacao_mensagens`: fila e histórico de todos os canais, preview
 * isolado, reenvio e cancelamento, configurações da fila e o redirecionamento de teste
 * (`MAIL_REDIRECT_TO`). As regras de fila vivem em `bibliotecas/comunicacao-fila.php`; este
 * controlador só traduz requisições do painel para elas.
 *
 * Fonte única de configuração: o `.env` da instalação. `MAIL_REDIRECT_TO` é o MESMO valor
 * editado em admin-environment — os dois painéis leem e gravam a mesma chave.
 */

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-comunicacao';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	json_decode(file_get_contents(__DIR__ . '/admin-comunicacao.json'), true);

// ==== Auxiliares

/** Atalho para as variáveis de interface do módulo. */
function admin_comunicacao_var($id){
	return gestor_variaveis(Array('modulo' => 'admin-comunicacao', 'id' => $id));
}

/** Resposta AJAX de erro com mensagem de variável. */
function admin_comunicacao_erro($idVariavel){
	global $_GESTOR;
	$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => admin_comunicacao_var($idVariavel));
}

/** Configuração exibida/salva no painel. */
function admin_comunicacao_config_painel(){
	$config = comunicacao_config();
	$redirect = comunicacao_redirecionamento_status();

	return Array(
		'modo' => $config['modo'],
		'rate_limit' => $config['rate_limit'],
		'max_tentativas' => $config['max_tentativas'],
		'retencao_dias' => $config['retencao_dias'],
		'mail_redirect_to' => $redirect['configurado'],
		'redirect_ativo' => $redirect['ativo'],
		'redirect_bloqueado' => $redirect['bloqueado'],
		'ambiente' => $redirect['ambiente'],
	);
}

/** Canais registrados (nativos + hook), para o filtro. */
function admin_comunicacao_canais(){
	$canais = Array();
	foreach(comunicacao_drivers() as $canal => $driver){
		$canais[] = Array('id' => $canal, 'rotulo' => (string)($driver['rotulo'] ?? $canal));
	}
	return $canais;
}

/**
 * Grava chaves no `.env` da instalação, preservando o restante do arquivo.
 *
 * Mesmo formato do admin-environment: a chave existente é reescrita na própria linha; a nova
 * vai para o fim do arquivo. Também atualiza `$_ENV`/`$_CONFIG` da requisição corrente.
 */
function admin_comunicacao_env_gravar($dados){
	global $_GESTOR, $_CONFIG;

	$envPath = $_GESTOR['AUTH_PATH_SERVER'] . '.env';
	if(!file_exists($envPath) || !is_writable($envPath)) return false;

	$linhas = explode("\n", (string)file_get_contents($envPath));
	$pendentes = $dados;

	foreach($linhas as $i => $linha){
		$limpa = trim($linha);
		if($limpa === '' || strpos($limpa, '#') === 0) continue;
		foreach($pendentes as $chave => $valor){
			if(strpos($limpa, $chave.'=') === 0){
				$linhas[$i] = $chave.'='.admin_comunicacao_env_valor($valor).(substr($linha, -1) === "\r" ? "\r" : '');
				unset($pendentes[$chave]);
				break;
			}
		}
	}

	if($pendentes){
		$linhas[] = '';
		$linhas[] = '# ===== Comunicação (admin-comunicacao / req-304)';
		foreach($pendentes as $chave => $valor) $linhas[] = $chave.'='.admin_comunicacao_env_valor($valor);
	}

	if(file_put_contents($envPath, implode("\n", $linhas)) === false) return false;

	foreach($dados as $chave => $valor) $_ENV[$chave] = (string)$valor;

	$_CONFIG['email']['redirect_to'] = (string)($dados['MAIL_REDIRECT_TO'] ?? ($_CONFIG['email']['redirect_to'] ?? ''));
	if(isset($dados['COMUNICACAO_MODO'])) $_CONFIG['email']['fila']['modo'] = $dados['COMUNICACAO_MODO'];
	if(isset($dados['COMUNICACAO_RATE_LIMIT'])) $_CONFIG['email']['fila']['rate_limit'] = (int)$dados['COMUNICACAO_RATE_LIMIT'];
	if(isset($dados['COMUNICACAO_MAX_TENTATIVAS'])) $_CONFIG['email']['fila']['max_tentativas'] = (int)$dados['COMUNICACAO_MAX_TENTATIVAS'];
	if(isset($dados['COMUNICACAO_RETENCAO_DIAS'])) $_CONFIG['email']['fila']['retencao_dias'] = (int)$dados['COMUNICACAO_RETENCAO_DIAS'];

	return true;
}

/** Valor para o `.env`: aspas quando há espaço ou caractere especial. */
function admin_comunicacao_env_valor($valor){
	$valor = (string)$valor;
	if(preg_match('/[\s\'"\\\\#]/', $valor)) return '"'.str_replace('"', '\\"', $valor).'"';
	return $valor;
}

/** Linha da fila no formato do painel (sem payload). */
function admin_comunicacao_item($linha){
	return Array(
		'id' => (int)$linha['id_comunicacao_mensagens'],
		'canal' => (string)$linha['canal'],
		'status' => (string)$linha['status'],
		'prioridade' => (int)$linha['prioridade'],
		'destinatario' => (string)($linha['destinatario'] ?? ''),
		'destinatario_original' => (string)($linha['destinatario_original'] ?? ''),
		'assunto' => (string)($linha['assunto'] ?? ''),
		'tentativas' => (int)$linha['tentativas'],
		'max_tentativas' => (int)$linha['max_tentativas'],
		'proxima_tentativa_em' => (string)($linha['proxima_tentativa_em'] ?? ''),
		'erro_ultimo' => mb_substr((string)($linha['erro_ultimo'] ?? ''), 0, 300),
		'enviado_em' => (string)($linha['enviado_em'] ?? ''),
		'data_criacao' => (string)($linha['data_criacao'] ?? ''),
	);
}

/** Id numérico vindo do painel. */
function admin_comunicacao_id(){
	$dados = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? $_POST : $_GET;
	return isset($dados['id']) ? max(0, (int)$dados['id']) : 0;
}

/** Resposta padrão depois de uma ação: estatísticas atualizadas. */
function admin_comunicacao_ok($mensagem, $dados = Array()){
	global $_GESTOR;
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'message' => $mensagem,
		'data' => array_merge(Array('estatisticas' => comunicacao_fila_estatisticas()), $dados),
	);
}

// ==== Interface

function admin_comunicacao_painel(){
	global $_GESTOR;

	gestor_pagina_javascript_incluir();

	$disponivel = comunicacao_fila_disponivel();
	$stats = $disponivel ? comunicacao_fila_estatisticas() : Array('enviados_hoje' => 0, 'na_fila' => 0, 'falhas' => 0, 'limite_minuto' => comunicacao_config()['rate_limit'], 'usados_minuto' => 0);
	$config = admin_comunicacao_config_painel();

	$json = function($valor){
		return htmlspecialchars(json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
	};

	$banner = '';
	$bannerClasse = 'hidden';
	if($config['redirect_ativo']){
		$banner = str_replace('#destino#', $config['mail_redirect_to'], admin_comunicacao_var('banner-redirect-active'));
		$bannerClasse = 'border-amber-300 bg-amber-50 text-amber-900';
	} else if($config['redirect_bloqueado']){
		$banner = admin_comunicacao_var('banner-redirect-blocked');
		$bannerClasse = 'border-rose-300 bg-rose-50 text-rose-900';
	}

	$p = &$_GESTOR['pagina'];
	$p = modelo_var_troca_tudo($p, '#config_json#', $json($config));
	$p = modelo_var_troca_tudo($p, '#canais_json#', $json(admin_comunicacao_canais()));
	$p = modelo_var_troca_tudo($p, '#tabela_disponivel#', $disponivel ? '1' : '0');
	$p = modelo_var_troca_tudo($p, '#tabela_aviso_classe#', $disponivel ? 'hidden' : '');
	$p = modelo_var_troca_tudo($p, '#banner_classe#', $bannerClasse);
	$p = modelo_var_troca_tudo($p, '#banner_texto#', htmlspecialchars($banner, ENT_QUOTES, 'UTF-8'));
	$p = modelo_var_troca_tudo($p, '#stat_enviados_hoje#', (string)$stats['enviados_hoje']);
	$p = modelo_var_troca_tudo($p, '#stat_na_fila#', (string)$stats['na_fila']);
	$p = modelo_var_troca_tudo($p, '#stat_falhas#', (string)$stats['falhas']);
	$p = modelo_var_troca_tudo($p, '#stat_limite#', (string)$stats['usados_minuto'].' / '.(string)$stats['limite_minuto']);
	$p = modelo_var_troca_tudo($p, '#teste_destino#', htmlspecialchars((string)($GLOBALS['_CONFIG']['email']['sender']['from'] ?? ''), ENT_QUOTES, 'UTF-8'));
}

// ==== AJAX

function admin_comunicacao_ajax_listar(){
	global $_GESTOR;

	if(!comunicacao_fila_disponivel()){ admin_comunicacao_erro('msg-table-missing'); return; }

	$lista = comunicacao_fila_listar(Array(
		'status' => (string)($_REQUEST['status'] ?? ''),
		'canal' => (string)($_REQUEST['canal'] ?? ''),
		'busca' => (string)($_REQUEST['busca'] ?? ''),
		'data_de' => (string)($_REQUEST['data_de'] ?? ''),
		'data_ate' => (string)($_REQUEST['data_ate'] ?? ''),
		'pagina' => (int)($_REQUEST['pagina'] ?? 1),
		'por_pagina' => 20,
	));

	$lista['itens'] = array_map('admin_comunicacao_item', $lista['itens']);

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'data' => array_merge($lista, Array('estatisticas' => comunicacao_fila_estatisticas())),
	);
}

function admin_comunicacao_ajax_detalhe(){
	global $_GESTOR;

	$linha = comunicacao_fila_disponivel() ? comunicacao_fila_obter(admin_comunicacao_id()) : null;
	if(!$linha){ admin_comunicacao_erro('msg-not-found'); return; }

	$payload = json_decode((string)$linha['payload_json'], true);
	if(!is_array($payload)) $payload = Array();
	$payload = comunicacao_mascarar_segredos($payload);

	// O preview vai para um iframe `sandbox` sem scripts nem mesma origem; aqui só se escolhe O QUE mostrar.
	if($linha['canal'] === 'email'){
		$html = comunicacao_email_preview_html((string)($payload['message']['body'] ?? ''), (array)($payload['message']['embeddedImgs'] ?? Array()));
		$anexos = Array();
		foreach((array)($payload['message']['attachments'] ?? Array()) as $a){
			$anexos[] = (string)($a['nome'] ?? basename((string)($a['caminho'] ?? '')));
		}
		$remetente = trim((string)($payload['sender']['fromName'] ?? '').' <'.(string)($payload['sender']['from'] ?? '').'>');
	} else {
		unset($payload['tmp_files']);
		$html = '<pre style="white-space:pre-wrap;font:13px monospace">'.htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8').'</pre>';
		$anexos = Array();
		$remetente = '';
	}

	$item = admin_comunicacao_item($linha);
	$item['erro_ultimo'] = (string)($linha['erro_ultimo'] ?? '');
	$item['remetente'] = $remetente;
	$item['anexos'] = $anexos;
	$item['html'] = $html;

	$_GESTOR['ajax-json'] = Array('status' => 'Ok', 'data' => $item);
}

function admin_comunicacao_ajax_cancelar(){
	if(!comunicacao_fila_disponivel()){ admin_comunicacao_erro('msg-table-missing'); return; }

	if(!comunicacao_fila_cancelar(admin_comunicacao_id())){ admin_comunicacao_erro('msg-cancel-invalid'); return; }

	admin_comunicacao_ok(admin_comunicacao_var('msg-cancel-success'));
}

function admin_comunicacao_ajax_reenviar(){
	if(!comunicacao_fila_disponivel()){ admin_comunicacao_erro('msg-table-missing'); return; }

	$r = comunicacao_fila_reenviar(admin_comunicacao_id());
	if(empty($r['id']) && empty($r['aceito'])){ admin_comunicacao_erro('msg-resend-invalid'); return; }

	$mensagem = $r['status'] === 'enviado'
		? admin_comunicacao_var('msg-resend-sent')
		: ($r['status'] === 'pendente' ? admin_comunicacao_var('msg-resend-queued') : admin_comunicacao_var('msg-resend-failed'));

	admin_comunicacao_ok($mensagem, Array('resultado' => Array('id' => $r['id'], 'status' => $r['status'])));
}

function admin_comunicacao_ajax_salvar(){
	global $_GESTOR;

	$modo = (string)($_POST['modo'] ?? '');
	$rate = filter_var($_POST['rate_limit'] ?? null, FILTER_VALIDATE_INT, Array('options' => Array('min_range' => 1, 'max_range' => 1000)));
	$tentativas = filter_var($_POST['max_tentativas'] ?? null, FILTER_VALIDATE_INT, Array('options' => Array('min_range' => 1, 'max_range' => 10)));
	$retencao = filter_var($_POST['retencao_dias'] ?? null, FILTER_VALIDATE_INT, Array('options' => Array('min_range' => 0, 'max_range' => 3650)));
	$redirect = trim((string)($_POST['mail_redirect_to'] ?? ''));

	if(!in_array($modo, Array('hibrido', 'sempre_fila'), true) || $rate === false || $tentativas === false || $retencao === false){
		admin_comunicacao_erro('msg-config-invalid');
		return;
	}
	if($redirect !== '' && filter_var($redirect, FILTER_VALIDATE_EMAIL) === false){
		admin_comunicacao_erro('msg-redirect-invalid');
		return;
	}

	$ok = admin_comunicacao_env_gravar(Array(
		'COMUNICACAO_MODO' => $modo,
		'COMUNICACAO_RATE_LIMIT' => (string)$rate,
		'COMUNICACAO_MAX_TENTATIVAS' => (string)$tentativas,
		'COMUNICACAO_RETENCAO_DIAS' => (string)$retencao,
		'MAIL_REDIRECT_TO' => $redirect,
	));

	if(!$ok){ admin_comunicacao_erro('msg-config-save-error'); return; }

	$config = admin_comunicacao_config_painel();
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'message' => admin_comunicacao_var('msg-config-saved'),
		'data' => Array(
			'config' => $config,
			'banner' => $config['redirect_ativo']
				? str_replace('#destino#', $config['mail_redirect_to'], admin_comunicacao_var('banner-redirect-active'))
				: ($config['redirect_bloqueado'] ? admin_comunicacao_var('banner-redirect-blocked') : ''),
			'estatisticas' => comunicacao_fila_disponivel() ? comunicacao_fila_estatisticas() : null,
		),
	);
}

function admin_comunicacao_ajax_testar(){
	$destino = trim((string)($_POST['destino'] ?? ''));
	if(filter_var($destino, FILTER_VALIDATE_EMAIL) === false){ admin_comunicacao_erro('msg-test-invalid'); return; }

	$preparado = comunicacao_email_preparar(Array(
		'destinatarios' => Array(Array('email' => $destino)),
		'mensagem' => Array(
			'assunto' => admin_comunicacao_var('test-email-subject').' '.date('Y-m-d H:i:s'),
			'html' => '<h1>'.htmlspecialchars(admin_comunicacao_var('test-email-title'), ENT_QUOTES, 'UTF-8').'</h1>'
				.'<p>'.htmlspecialchars(admin_comunicacao_var('test-email-body'), ENT_QUOTES, 'UTF-8').'</p>'
				.'<p>'.date('Y-m-d H:i:s').'</p>',
		),
		'prioridade' => 'alta',
	));

	if(!$preparado){ admin_comunicacao_erro('msg-test-disabled'); return; }

	$r = comunicacao_enviar('email', $preparado['payload'], $preparado['opcoes']);

	if($r['status'] === 'enviado') $mensagem = admin_comunicacao_var('msg-test-sent');
	else if($r['status'] === 'pendente') $mensagem = admin_comunicacao_var('msg-test-queued');
	else { admin_comunicacao_erro('msg-test-failed'); return; }

	admin_comunicacao_ok($mensagem, Array('resultado' => Array('id' => $r['id'], 'status' => $r['status'])));
}

function admin_comunicacao_start(){
	global $_GESTOR;

	gestor_incluir_bibliotecas();
	gestor_incluir_biblioteca('comunicacao');

	if($_GESTOR['ajax']){
		interface_ajax_iniciar();

		$mutacoes = Array('cancelar', 'reenviar', 'salvar', 'testar');
		if(in_array($_GESTOR['ajax-opcao'], $mutacoes, true) && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'){
			http_response_code(405);
			header('Allow: POST');
			admin_comunicacao_erro('msg-post-required');
			interface_ajax_finalizar();
			return;
		}

		switch($_GESTOR['ajax-opcao']){
			// `listar` é reservado: interface_ajax_finalizar() o despacha para a listagem genérica.
			case 'mensagens': admin_comunicacao_ajax_listar(); break;
			case 'detalhe': admin_comunicacao_ajax_detalhe(); break;
			case 'cancelar': admin_comunicacao_ajax_cancelar(); break;
			case 'reenviar': admin_comunicacao_ajax_reenviar(); break;
			case 'salvar': admin_comunicacao_ajax_salvar(); break;
			case 'testar': admin_comunicacao_ajax_testar(); break;
		}

		interface_ajax_finalizar();
	} else {
		interface_iniciar();

		switch($_GESTOR['opcao']){
			case 'painel': admin_comunicacao_painel(); break;
		}

		interface_finalizar();
	}
}

admin_comunicacao_start();

?>
