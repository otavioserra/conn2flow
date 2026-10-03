<?php
/**
 * Handler/Helpers da API no lado CENTRAL (conn2flow.com) — Arquitetura de Módulos Distribuídos (req-005).
 *
 * Roda no mesmo core, na instalação central. Atende as requisições que a instalação
 * distribuída (site.com) emite para o canal `_api/modulo-distribuido/{slug}/{acao}`
 * relacionadas à AUTENTICAÇÃO/ATIVAÇÃO:
 *  - acao 'exchange': troca o código de uso único emitido pelo login padrão.
 *  - acao 'iframe-ticket': autoriza a abertura do módulo original no iframe.
 *  - acao 'refresh': renova os tokens a partir de um refresh token válido.
 *
 * Também expõe helpers usados pelos MÓDULOS CENTRAIS para montar a configuração do
 * canal de banco distribuído (endpoint do site.com + segredo + token) a ser passada
 * para banco_distribuido_iniciar().
 *
 * Este arquivo é auxiliar de controladores/api/api.php e não deve ser acessado direto.
 */

if (!function_exists('api_response_error')) {
	http_response_code(400);
	echo json_encode(['status' => 'error', 'message' => 'Contexto de API inválido.']);
	exit;
}

/**
 * Ponto de entrada do handler central (autenticação distribuída).
 *
 * @param array $rota Resultado de modulo_distribuido_parse_rota(): ['slug','acao','resto'].
 *
 * @return void
 */
function api_module_central_handle(array $rota) {
	$slug = $rota['slug'] ?? '';
	$acao = $rota['acao'] ?? '';

	// Corpo cru para validação de assinatura do canal.
	$corpo_cru = file_get_contents('php://input');
	$assinatura = $_SERVER['HTTP_X_C2F_SIGNATURE'] ?? '';
	$payload = json_decode((string)$corpo_cru, true);
	$instalacao = is_array($payload) ? modulo_distribuido_instalacao($payload['app_id'] ?? '', $slug) : false;
	// req-213: quem se identifica por `app_id` é julgado pelo cadastro. Instalação desconhecida,
	// desativada ou sem o módulo não cai no segredo global legado.
	if (is_array($payload) && isset($payload['app_id']) && $payload['app_id'] !== '' && !$instalacao) {
		api_response_error('distributed-installation-invalid', 403);
	}
	$secret = $instalacao ? $instalacao['secret'] : api_module_central_secret($slug);

	// req-217: abrir sessão (o Central liga de volta para o endereço cadastrado da instalação) e
	// confirmar um desafio que o próprio Central criou. Só para instalação cadastrada.
	if (in_array($acao, MODULO_DISTRIBUIDO_ACOES_ABERTURA, true)) {
		if (!$instalacao || $slug !== MODULO_DISTRIBUIDO_SLUG_CONTA) api_response_error('distributed-installation-invalid', 403);
		$dados = modulo_distribuido_validar_envelope($corpo_cru, $assinatura, $secret, $slug);
		if (!$dados) api_response_error('Assinatura HMAC inválida.', 401);
		if ($acao === 'confirmar') {
			api_response_success(modulo_distribuido_confirmar_origem($dados, $secret, $payload['app_id']));
		}
		$sessao = modulo_distribuido_sessao_conceder($dados, $secret, $payload['app_id'],
			['endpoint' => $instalacao['url'] . '/_api', 'secret' => $secret, 'peer' => $payload['app_id']]);
		if (!$sessao) api_response_error('distributed-origin-unconfirmed', 401);
		api_response_success($sessao);
	}

	// A assinatura HMAC do canal autentica a instalação distribuída chamadora (req-217: com a chave da
	// sessão aberta pela confirmação de origem).
	$sessao = $_SERVER['HTTP_X_C2F_SESSION'] ?? '';
	if ($secret === '' || !modulo_distribuido_receber($corpo_cru, $assinatura, $sessao, $secret, $slug, $instalacao ? $payload['app_id'] : '')) {
		api_response_error($sessao !== '' ? 'distributed-session-invalid' : (modulo_distribuido_origem_ativa() ? 'distributed-session-required' : 'Assinatura HMAC inválida.'), 401);
	}

	$payload = json_decode((string)$corpo_cru, true);
	if (!is_array($payload)) {
		$payload = [];
	}

	switch ($acao) {
		case 'estado':
			// req-216: account state and the plan's modules, signed for the customer's site.
			if (!$instalacao || $slug !== MODULO_DISTRIBUIDO_SLUG_CONTA) api_response_error('distributed-installation-invalid', 403);
			$conta = modulo_distribuido_conta($payload['app_id']);
			$catalogo = modulo_distribuido_catalogo_local()['modules'];
			$dados = ['estado' => $conta['estado'], 'modulos' => $conta['modulos'] ?? $catalogo, 'destino' => $conta['destino'],
				'modulo' => $slug, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
			$body = json_encode($dados, JSON_UNESCAPED_SLASHES);
			api_response_success(['body' => base64_encode($body), 'signature' => modulo_distribuido_assinar($body, $secret)]);
			break;
		case 'exchange':
			if (!$instalacao) api_response_error('distributed-installation-invalid', 403);
			$registro = modulo_distribuido_registro_consumir($payload['code'] ?? '', 'login', $secret);
			if (!$registro || ($registro['contexto']['app_id'] ?? '') !== $payload['app_id']
				|| ($registro['contexto']['modulo'] ?? '') !== $slug
				|| !is_string($payload['state'] ?? null)
				|| !hash_equals($registro['contexto']['state'], $payload['state'])) api_response_error('distributed-exchange-invalid', 401);
			$dados = array_merge($registro['tokens'], ['modulo' => $slug, 'state' => $payload['state'],
				'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))]);
			$body = json_encode($dados, JSON_UNESCAPED_SLASHES);
			api_response_success(['body' => base64_encode($body), 'signature' => modulo_distribuido_assinar($body, $secret)]);
			break;
		case 'iframe-ticket':
			if (!$instalacao) api_response_error('distributed-installation-invalid', 403);
			gestor_incluir_biblioteca('oauth2');
			gestor_incluir_biblioteca('autenticacao');
			$permissao = modulo_distribuido_middleware_central($payload['token'] ?? '', $slug);
			$route = $payload['route'] ?? '';
			if ($permissao['estado'] !== 'permitido' || !is_string($route)
				|| !preg_match('~^' . preg_quote($slug, '~') . '/[a-zA-Z0-9_/-]*$~D', $route)
				|| strpos($route, '//') !== false) api_response_error('distributed-route-denied', 403);
			// req-215: os parâmetros do endereço seguem para o iframe, reconstruídos aqui e não copiados.
			$query = '';
			if (is_string($payload['query'] ?? null) && $payload['query'] !== '') {
				parse_str($payload['query'], $parametros);
				$query = modulo_distribuido_consulta_canonica($parametros);
			}
			$ticket = modulo_distribuido_registro_emitir('iframe', ['id_usuarios' => $permissao['id_usuarios'],
				'app_id' => $payload['app_id'], 'modulo' => $slug, 'route' => $route, 'query' => $query, 'token' => $payload['token']], $secret, 60);
			api_response_success(['ticket' => $ticket]);
			break;
		case 'refresh':
			api_module_central_refresh($payload, $secret, $slug);
			break;

		case 'permissao':
			api_module_central_permissao($payload, $slug);
			break;

		default:
			api_response_error('Ação central não suportada: ' . $acao, 404);
	}
}

/**
 * Middleware de permissão por requisição (lado central).
 *
 * Chamado a cada acesso a um módulo distribuído: valida o token do usuário e verifica,
 * pelo mesmo controle por perfil de gestor_permissao_modulo, se o usuário pode acessar
 * o módulo alvo. Responde com um dos estados abaixo, para o ambiente distribuído decidir
 * a renderização (login / página de sem-permissão / iframe):
 *  - 'nao-autenticado' : token ausente/inválido/expirado.
 *  - 'sem-permissao'   : autenticado, porém sem vínculo de perfil com o módulo.
 *  - 'permitido'       : autenticado e autorizado.
 *
 * @param array $payload Deve conter 'token'; opcional 'modulo' (override do alvo).
 * @param string $slug   Slug do módulo alvo (da rota).
 *
 * @return void
 */
function api_module_central_permissao(array $payload, $slug = '') {
	$token = isset($payload['token']) ? (string)$payload['token'] : '';
	$modulo_alvo = !empty($payload['modulo']) ? (string)$payload['modulo'] : (string)$slug;

	gestor_incluir_biblioteca('modulo-distribuido');
	gestor_incluir_biblioteca('oauth2');
	gestor_incluir_biblioteca('autenticacao');

	// A autoridade de decisão é o middleware central (avalia token + permissão).
	$resultado = modulo_distribuido_middleware_central($token, $modulo_alvo);

	// req-216: conta encerrada fecha o painel; sem permissão ou encerrada, o usuário vai para a tela de
	// assinatura do projeto (o destino vem do provedor da conta).
	$conta = modulo_distribuido_conta($payload['app_id'] ?? '');
	if ($resultado['estado'] === 'permitido' && $conta['estado'] === 'encerrado') $resultado['estado'] = 'sem-permissao';
	if ($resultado['estado'] === 'sem-permissao' && $conta['destino']) $resultado['destino'] = $conta['destino'];
	$resultado['conta'] = $conta['estado'];

	$mensagens = [
		'permitido'       => 'Acesso autorizado',
		'sem-permissao'   => 'Sem permissão de acesso ao módulo',
		'nao-autenticado' => 'Token inválido ou ausente',
	];
	$msg = $mensagens[$resultado['estado']] ?? 'Estado de acesso avaliado';

	api_response_success($resultado, $msg);
}

/**
 * Renova os tokens do canal distribuído a partir de um refresh token.
 *
 * @param array $payload Deve conter 'refresh_token'.
 *
 * @return void
 */
function api_module_central_refresh(array $payload, $secret, $slug) {
	global $_GESTOR;
	$refresh = isset($payload['refresh_token']) ? (string)$payload['refresh_token'] : '';
	if ($refresh === '') {
		api_response_error('refresh_token é obrigatório.', 400);
	}

	gestor_incluir_biblioteca('oauth2');
	gestor_incluir_biblioteca('autenticacao');
	$publicKey = @file_get_contents($_GESTOR['openssl-path'] . 'publica.key');
	$claims = $publicKey ? autenticacao_validar_jwt_chave_publica(['token' => $refresh,
		'chavePublica' => $publicKey, 'retornarPayloadCompleto' => true]) : false;
	if (!$claims || ($claims['token_type'] ?? '') !== 'refresh' || ($claims['scope'] ?? '') !== 'distributed') {
		api_response_error('distributed-refresh-scope-invalid', 401);
	}
	try {
		$stmt = modulo_distribuido_pdo()->prepare('INSERT INTO distributed_exchanges (id,kind,payload,expires_at,consumed) VALUES (?,?,?,?,1)');
		$stmt->execute([hash_hmac('sha256', 'refresh:' . $refresh, $secret), 'refresh', '', (int)$claims['exp']]);
	} catch (\Throwable $e) {
		api_response_error('distributed-refresh-used', 401);
	}
	$novos = oauth2_renovar_token(['refresh_token' => $refresh, 'scope' => 'distributed']);
	if (!$novos) {
		api_response_error('Refresh token inválido ou expirado.', 401);
	}

	$novos['modulo'] = $slug;
	$novos['timestamp'] = time();
	$novos['nonce'] = bin2hex(random_bytes(16));
	$body = json_encode($novos, JSON_UNESCAPED_SLASHES);
	api_response_success(['body' => base64_encode($body), 'signature' => modulo_distribuido_assinar($body, $secret)]);
}

/**
 * Resolve o segredo HMAC do canal distribuído no lado central.
 *
 * @param string|null $slug Slug do módulo distribuído.
 *
 * @return string
 */
function api_module_central_secret($slug = null) {
	global $_CONFIG;

	if ($slug && isset($_CONFIG['modulo-distribuido']['secrets'][$slug])) {
		return (string)$_CONFIG['modulo-distribuido']['secrets'][$slug];
	}
	if (isset($_CONFIG['modulo-distribuido']['secret'])) {
		return (string)$_CONFIG['modulo-distribuido']['secret'];
	}
	return '';
}

/**
 * Monta a configuração do canal de banco distribuído para um módulo central.
 *
 * O módulo central usa esta configuração em banco_distribuido_iniciar() antes de
 * executar as operações de dados que devem persistir no site.com.
 *
 * Fontes de resolução (com override por argumento):
 *  - endpoint: $config['endpoint'] | manifesto['distributed']['endpoint'] | $_CONFIG | env.
 *  - secret  : api_module_central_secret($slug).
 *  - token   : $config['token'] (bearer opcional).
 *
 * @param string $slug      Slug do módulo distribuído.
 * @param array  $overrides Sobrescritas explícitas (endpoint, secret, token, timeout).
 *
 * @return array Configuração pronta para banco_distribuido_iniciar().
 */
function api_module_central_config($slug, array $overrides = []) {
	global $_CONFIG;

	$endpoint = $overrides['endpoint']
		?? ($_CONFIG['modulo-distribuido']['endpoints'][$slug] ?? null)
		?? ($_CONFIG['modulo-distribuido']['endpoint'] ?? null);

	$secret = $overrides['secret'] ?? api_module_central_secret($slug);

	$config = [
		'slug'     => $slug,
		'endpoint' => $endpoint ? rtrim((string)$endpoint, '/') : '',
		'secret'   => (string)$secret,
		'acao'     => 'db',
	];
	if (!empty($overrides['token'])) {
		$config['token'] = (string)$overrides['token'];
	}
	if (!empty($overrides['timeout'])) {
		$config['timeout'] = (int)$overrides['timeout'];
	}
	if (isset($overrides['transporte']) && is_callable($overrides['transporte'])) {
		$config['transporte'] = $overrides['transporte'];
	}
	return $config;
}
