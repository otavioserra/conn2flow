<?php
/**
 * Handler/Helpers da API no lado CENTRAL (conn2flow.com) — Arquitetura de Módulos Distribuídos (req-005).
 *
 * Roda no mesmo core, na instalação central. Atende as requisições que a instalação
 * distribuída (site.com) emite para o canal `_api/modulo-distribuido/{slug}/{acao}`
 * relacionadas à AUTENTICAÇÃO/ATIVAÇÃO:
 *  - acao 'signin' : valida credenciais do usuário (via autenticacao.php) e devolve
 *                    os tokens de acesso e de renovação (OAuth2) para o distribuído.
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
	$secret = api_module_central_secret($slug);

	// A assinatura HMAC do canal autentica a instalação distribuída chamadora.
	if ($secret === '' || !modulo_distribuido_verificar_assinatura($corpo_cru, $assinatura, $secret)) {
		api_response_error('Assinatura HMAC inválida.', 401);
	}

	$payload = json_decode((string)$corpo_cru, true);
	if (!is_array($payload)) {
		$payload = [];
	}

	switch ($acao) {
		case 'signin':
			api_module_central_signin($payload, $slug);
			break;

		case 'refresh':
			api_module_central_refresh($payload);
			break;

		case 'permissao':
			api_module_central_permissao($payload, $slug);
			break;

		default:
			api_response_error('Ação central não suportada: ' . $acao, 404);
	}
}

/**
 * Autentica o usuário do módulo distribuído e devolve os tokens (acesso + renovação).
 *
 * Autenticação = IDENTIDADE: o token é emitido para QUALQUER usuário válido no sistema,
 * independentemente de permissão a este ou àquele módulo. O controle de permissão por
 * módulo NÃO ocorre aqui — é aplicado como middleware por requisição (endpoint 'permissao'
 * + api_module_central_permissao), permitindo mostrar a página de "sem permissão" no
 * ambiente distribuído sem impedir o login.
 *
 * @param array  $payload Deve conter 'usuario' e 'senha'.
 * @param string $slug    Slug do módulo alvo (da rota) — mantido por assinatura, não bloqueia.
 *
 * @return void
 */
function api_module_central_signin(array $payload, $slug = '') {
	$usuario = isset($payload['usuario']) ? (string)$payload['usuario'] : '';
	$senha   = isset($payload['senha']) ? (string)$payload['senha'] : '';

	if ($usuario === '' || $senha === '') {
		api_response_error('Usuário e senha são obrigatórios.', 400);
	}

	gestor_incluir_biblioteca('autenticacao');

	// Validação de credenciais (identidade).
	$validacao = autenticacao_distribuido_validar_credenciais($usuario, $senha);
	if (!$validacao['valido']) {
		api_response_error($validacao['mensagem'] ?? 'Credenciais inválidas.', 401);
	}

	// Emissão dos tokens de acesso e renovação (sempre que o usuário é válido).
	$tokens = autenticacao_distribuido_gerar_tokens($validacao['id_usuarios']);
	if (!$tokens) {
		api_response_error('Falha ao gerar tokens de acesso.', 500);
	}

	api_response_success($tokens, 'Autenticação distribuída bem-sucedida');
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
function api_module_central_refresh(array $payload) {
	$refresh = isset($payload['refresh_token']) ? (string)$payload['refresh_token'] : '';
	if ($refresh === '') {
		api_response_error('refresh_token é obrigatório.', 400);
	}

	gestor_incluir_biblioteca('oauth2');
	$novos = oauth2_renovar_token(['refresh_token' => $refresh]);
	if (!$novos) {
		api_response_error('Refresh token inválido ou expirado.', 401);
	}

	api_response_success($novos, 'Tokens renovados com sucesso');
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
