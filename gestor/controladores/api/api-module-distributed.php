<?php
/**
 * Handler da API no lado DISTRIBUÍDO (site.com) — Arquitetura de Módulos Distribuídos (req-005).
 *
 * Roda dentro do mesmo core do Conn2Flow, porém na instalação distribuída do cliente.
 * Recebe as requisições emitidas pela instalação central (conn2flow.com) para o canal
 * `_api/modulo-distribuido/{slug}/{acao}` e:
 *  - valida a assinatura HMAC do corpo (X-C2F-Signature);
 *  - executa localmente a instrução SQL empacotada (acao 'db') no banco do cliente;
 *  - responde 'ping' (acao 'ping') para verificação de conectividade do canal.
 *
 * Este arquivo é auxiliar de controladores/api/api.php e não deve ser acessado direto.
 * As funções de execução/segurança vivem na biblioteca bibliotecas/modulo-distribuido.php.
 */

if (!function_exists('api_response_error')) {
	// Proteção contra include direto fora do contexto do api.php.
	http_response_code(400);
	echo json_encode(['status' => 'error', 'message' => 'Contexto de API inválido.']);
	exit;
}

/**
 * Resolve o segredo HMAC compartilhado do canal distribuído neste ambiente.
 *
 * Ordem de resolução: config do gestor ($_CONFIG['modulo-distribuido']['secret']) →
 * variável de ambiente C2F_DISTRIBUIDO_SECRET.
 *
 * @param string|null $slug Slug do módulo (permite segredo por módulo no futuro).
 *
 * @return string Segredo HMAC ou string vazia quando não configurado.
 */
function api_module_distributed_secret($slug = null) {
	global $_CONFIG;

	// Segredo específico por módulo tem precedência, se existir.
	if ($slug && isset($_CONFIG['modulo-distribuido']['secrets'][$slug])) {
		return (string)$_CONFIG['modulo-distribuido']['secrets'][$slug];
	}
	if (isset($_CONFIG['modulo-distribuido']['secret'])) {
		return (string)$_CONFIG['modulo-distribuido']['secret'];
	}
	return '';
}

/**
 * Ponto de entrada do handler distribuído.
 *
 * @param array $rota Resultado de modulo_distribuido_parse_rota(): ['slug','acao','resto'].
 *
 * @return void Sempre encerra a requisição via api_response_*.
 */
function api_module_distributed_handle(array $rota) {
	$slug = $rota['slug'] ?? '';
	$acao = $rota['acao'] ?? 'db';

	// Corpo cru para validação de assinatura (não usar php://input duas vezes).
	$corpo_cru = file_get_contents('php://input');

	// Verificação de assinatura HMAC do canal.
	$assinatura = $_SERVER['HTTP_X_C2F_SIGNATURE'] ?? '';
	$secret = api_module_distributed_secret($slug);

	if ($secret === '') {
		api_response_error('Canal distribuído não configurado (segredo ausente).', 500);
	}
	if (!modulo_distribuido_verificar_assinatura($corpo_cru, $assinatura, $secret)) {
		api_response_error('Assinatura HMAC inválida.', 401);
	}

	switch ($acao) {
		case 'ping':
			api_response_success(['pong' => true, 'modulo' => $slug], 'Canal distribuído operacional');
			break;

		case 'db':
			api_module_distributed_db($slug, $corpo_cru);
			break;

		default:
			api_response_error('Ação distribuída não suportada: ' . $acao, 404);
	}
}

/**
 * Executa a operação de banco recebida da central no banco local do cliente.
 *
 * @param string $slug      Slug do módulo distribuído.
 * @param string $corpo_cru Corpo cru JSON já validado por HMAC.
 *
 * @return void
 */
function api_module_distributed_db($slug, $corpo_cru) {
	global $_GESTOR;

	$payload = json_decode((string)$corpo_cru, true);
	if (!is_array($payload) || !isset($payload['sql'])) {
		api_response_error('Payload distribuído inválido.', 400);
	}

	// Conexão PDO com o banco local (mesmo helper usado pelo pipeline de atualização).
	$pdo = api_module_distributed_pdo();
	if (!$pdo instanceof PDO) {
		api_response_error('Falha ao conectar ao banco local do cliente.', 500);
	}

	$resultado = modulo_distribuido_executar_local($payload, $pdo);

	// Resposta padronizada JSON (o executor já retorna status ok/error).
	while (ob_get_level() > 0) { ob_end_clean(); }
	http_response_code($resultado['status'] === 'ok' ? 200 : 400);
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
	exit;
}

/**
 * Obtém uma conexão PDO com o banco local, reaproveitando o helper db() do core.
 *
 * @return PDO|null
 */
function api_module_distributed_pdo() {
	global $_GESTOR, $_BANCO;

	if (function_exists('db')) {
		try {
			return db();
		} catch (\Throwable $e) {
			error_log('MODULO-DISTRIBUIDO: db() falhou: ' . $e->getMessage());
		}
	}

	// Fallback: monta PDO a partir de $_BANCO diretamente.
	try {
		$host = $_BANCO['host'] ?? 'localhost';
		$nome = $_BANCO['nome'] ?? '';
		$user = $_BANCO['usuario'] ?? '';
		$pass = $_BANCO['senha'] ?? '';
		$dsn = "mysql:host={$host};dbname={$nome};charset=utf8mb4";
		return new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
	} catch (\Throwable $e) {
		error_log('MODULO-DISTRIBUIDO: fallback PDO falhou: ' . $e->getMessage());
		return null;
	}
}
