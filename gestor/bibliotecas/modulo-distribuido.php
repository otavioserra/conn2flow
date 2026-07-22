<?php
/**
 * Biblioteca de Módulos Distribuídos (Arquitetura Distribuída Conn2Flow).
 *
 * Fornece a infraestrutura genérica e reaproveitável que permite a um módulo
 * "central" (rodando em conn2flow.com) delegar a persistência de dados para uma
 * instalação "distribuída" (rodando no site.com do cliente), de forma transparente
 * para o código do módulo.
 *
 * Peças principais:
 * - Leitura do escopo (scope) do manifesto do módulo (central-module / distributed-module).
 * - Assinatura e verificação HMAC das requisições trocadas entre central e distribuído.
 * - Empacotamento das instruções SQL em payload JSON e cliente HTTP de envio.
 * - Executor local no lado distribuído (recebe o JSON e roda a SQL no banco do cliente).
 * - Objeto BancoResultadoRemoto que emula o resultado do mysqli para SELECTs remotos,
 *   tornando a leitura transparente para as funções nativas de banco.php.
 * - Parser de rota da API distribuída (api/v1/modulo-distribuido/{slug}/{acao}).
 *
 * Nenhuma regra específica de projeto vive aqui: esta biblioteca é núcleo genérico.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-modulo-distribuido'] = Array(
	'versao' => '1.0.0',
);

// =========================== Constantes de escopo

if (!defined('C2F_SCOPE_CENTRAL')) {
	define('C2F_SCOPE_CENTRAL', 'central-module');
}
if (!defined('C2F_SCOPE_DISTRIBUIDO')) {
	define('C2F_SCOPE_DISTRIBUIDO', 'distributed-module');
}

// =========================== Resultado remoto (emula mysqli_result)

/**
 * Encapsula o resultado de um SELECT executado remotamente no ambiente distribuído.
 *
 * Emula o comportamento mínimo de um mysqli_result consumido por banco.php:
 * contagem de linhas/colunas, nome das colunas e avanço linha-a-linha (fetch).
 * Com isso, funções como banco_select() operam sobre resultados remotos sem
 * qualquer alteração na sua lógica de montagem de arrays associativos.
 */
class BancoResultadoRemoto {
	/** @var string[] Nomes das colunas na ordem retornada. */
	public $fields = [];
	/** @var array[] Linhas, cada uma como array indexado numericamente na ordem de $fields. */
	public $rows = [];
	/** @var int Cursor interno de leitura. */
	private $cursor = 0;

	public function __construct(array $fields = [], array $rows = []) {
		$this->fields = array_values($fields);
		$this->rows = array_values($rows);
	}

	/** Número de linhas do resultado. */
	public function numRows(): int {
		return count($this->rows);
	}

	/** Número de colunas do resultado. */
	public function numFields(): int {
		return count($this->fields);
	}

	/** Nome da coluna no índice informado. */
	public function fieldName(int $i): ?string {
		return $this->fields[$i] ?? null;
	}

	/**
	 * Retorna a próxima linha como array associativo + indexado (equivalente a MYSQLI_BOTH).
	 *
	 * @return array|null Linha atual ou null quando o cursor chega ao fim.
	 */
	public function fetchArray(): ?array {
		if ($this->cursor >= count($this->rows)) {
			return null;
		}
		$linha = $this->rows[$this->cursor];
		$this->cursor++;

		$saida = [];
		foreach ($this->fields as $i => $nome) {
			$valor = $linha[$i] ?? null;
			$saida[$i] = $valor;      // acesso por índice numérico (usado por banco_select)
			$saida[$nome] = $valor;   // acesso por nome (usado por banco_fetch_assoc)
		}
		return $saida;
	}

	/** Retorna a próxima linha apenas como array indexado numericamente. */
	public function fetchRow(): ?array {
		$linha = $this->fetchArray();
		if ($linha === null) {
			return null;
		}
		$saida = [];
		foreach ($this->fields as $i => $nome) {
			$saida[$i] = $linha[$i] ?? null;
		}
		return $saida;
	}

	/** Retorna a próxima linha apenas como array associativo. */
	public function fetchAssoc(): ?array {
		$linha = $this->fetchArray();
		if ($linha === null) {
			return null;
		}
		$saida = [];
		foreach ($this->fields as $nome) {
			$saida[$nome] = $linha[$nome] ?? null;
		}
		return $saida;
	}
}

// =========================== Escopo do módulo (manifesto)

/**
 * Lê o valor da chave "scope" do manifesto <slug>.json de um módulo.
 *
 * Aceita tanto o array já decodificado do manifesto quanto o caminho do diretório
 * ou do arquivo JSON do módulo.
 *
 * @param array|string $modulo Manifesto decodificado, caminho do .json ou do diretório do módulo.
 * @param string|null  $slug   Slug do módulo (necessário quando $modulo é um diretório).
 *
 * @return string|null Valor de scope ('central-module' / 'distributed-module') ou null.
 */
function modulo_distribuido_scope($modulo, $slug = null) {
	$config = modulo_distribuido_resolver_manifesto($modulo, $slug);
	if (!is_array($config)) {
		return null;
	}
	return isset($config['scope']) && is_string($config['scope']) ? $config['scope'] : null;
}

/**
 * Indica se o scope informado é o de um módulo central.
 */
function modulo_distribuido_scope_central($scope): bool {
	return $scope === C2F_SCOPE_CENTRAL;
}

/**
 * Indica se o scope informado é o de um módulo distribuído.
 */
function modulo_distribuido_scope_distribuido($scope): bool {
	return $scope === C2F_SCOPE_DISTRIBUIDO;
}

/**
 * Resolve o manifesto (array) de um módulo a partir de array, caminho de .json ou diretório.
 *
 * @param array|string $modulo Manifesto decodificado, caminho do .json ou do diretório do módulo.
 * @param string|null  $slug   Slug do módulo (usado para achar <slug>.json quando $modulo é diretório).
 *
 * @return array|null Manifesto decodificado ou null.
 */
function modulo_distribuido_resolver_manifesto($modulo, $slug = null) {
	if (is_array($modulo)) {
		return $modulo;
	}
	if (!is_string($modulo) || $modulo === '') {
		return null;
	}

	$json_path = null;
	if (is_dir($modulo)) {
		$dir = rtrim($modulo, '/\\') . DIRECTORY_SEPARATOR;
		if ($slug === null) {
			$slug = basename(rtrim($modulo, '/\\'));
		}
		$json_path = $dir . $slug . '.json';
	} elseif (is_file($modulo)) {
		$json_path = $modulo;
	}

	if ($json_path === null || !is_file($json_path)) {
		return null;
	}

	$conteudo = file_get_contents($json_path);
	$config = json_decode((string)$conteudo, true);
	return (is_array($config) && json_last_error() === JSON_ERROR_NONE) ? $config : null;
}

// =========================== Assinatura HMAC

/**
 * Gera a assinatura HMAC de um corpo de requisição.
 *
 * @param string $corpo  Corpo cru (string JSON) a ser assinado.
 * @param string $secret Segredo compartilhado entre central e distribuído.
 * @param string $algo   Algoritmo de hash (padrão sha256).
 *
 * @return string Assinatura em hexadecimal.
 */
function modulo_distribuido_assinar($corpo, $secret, $algo = 'sha256') {
	return hash_hmac($algo, (string)$corpo, (string)$secret);
}

/**
 * Verifica, de forma resistente a ataques de timing, a assinatura HMAC de um corpo.
 *
 * @param string $corpo      Corpo cru recebido.
 * @param string $assinatura Assinatura recebida (header X-C2F-Signature).
 * @param string $secret     Segredo compartilhado.
 * @param string $algo       Algoritmo de hash (padrão sha256).
 *
 * @return bool true se a assinatura confere.
 */
function modulo_distribuido_verificar_assinatura($corpo, $assinatura, $secret, $algo = 'sha256') {
	if (!is_string($assinatura) || $assinatura === '' || !is_string($secret) || $secret === '') {
		return false;
	}
	$esperado = modulo_distribuido_assinar($corpo, $secret, $algo);
	return hash_equals($esperado, $assinatura);
}

// =========================== Empacotamento da instrução SQL

/**
 * Detecta o tipo de operação SQL a partir da instrução.
 *
 * @param string $sql Instrução SQL.
 *
 * @return string Um de: 'select', 'insert', 'update', 'delete', 'outro'.
 */
function modulo_distribuido_detectar_operacao($sql) {
	$limpo = ltrim((string)$sql);
	// Remove comentários de linha iniciais simples para não confundir a detecção.
	$limpo = preg_replace('/^\s*(--[^\n]*\n)+/', '', $limpo);
	if (preg_match('/^\s*select\b/i', $limpo)) return 'select';
	if (preg_match('/^\s*insert\b/i', $limpo)) return 'insert';
	if (preg_match('/^\s*update\b/i', $limpo)) return 'update';
	if (preg_match('/^\s*delete\b/i', $limpo)) return 'delete';
	return 'outro';
}

/**
 * Indica se a operação detectada é de leitura (SELECT).
 */
function modulo_distribuido_operacao_leitura($operacao): bool {
	return $operacao === 'select';
}

/**
 * Monta o payload (array) que empacota a instrução SQL para trânsito via API.
 *
 * @param string $sql     Instrução SQL já montada.
 * @param array  $opcoes  Opções: 'modulo' (slug), 'linguagem'.
 *
 * @return array Payload com sql, operacao, modulo, timestamp e nonce.
 */
function modulo_distribuido_montar_payload($sql, $opcoes = []) {
	$operacao = modulo_distribuido_detectar_operacao($sql);
	return [
		'versao'    => 1,
		'operacao'  => $operacao,
		'sql'       => (string)$sql,
		'modulo'    => isset($opcoes['modulo']) ? (string)$opcoes['modulo'] : null,
		'linguagem' => isset($opcoes['linguagem']) ? (string)$opcoes['linguagem'] : null,
		'timestamp' => time(),
		'nonce'     => bin2hex(random_bytes(8)),
	];
}

// =========================== Cliente HTTP (central -> distribuído)

/**
 * Envia um payload de banco distribuído para a instalação remota e decodifica a resposta.
 *
 * A configuração deve conter:
 * - 'endpoint' : URL base da API distribuída (ex.: https://site.com/_api/).
 * - 'slug'     : slug do módulo distribuído.
 * - 'secret'   : segredo HMAC compartilhado.
 * - 'token'    : (opcional) bearer token de acesso.
 * - 'timeout'  : (opcional) timeout em segundos (padrão 15).
 * - 'transporte': (opcional) callable(url, corpo, headers) => string para testes/mocks.
 *
 * @param array $payload Payload montado por modulo_distribuido_montar_payload().
 * @param array $config  Configuração do canal distribuído.
 *
 * @return array Resposta decodificada. Em erro retorna ['status' => 'error', 'message' => ...].
 */
function modulo_distribuido_enviar(array $payload, array $config) {
	$endpoint = isset($config['endpoint']) ? rtrim((string)$config['endpoint'], '/') : '';
	$slug     = isset($config['slug']) ? (string)$config['slug'] : ($payload['modulo'] ?? '');
	$secret   = isset($config['secret']) ? (string)$config['secret'] : '';
	$acao     = isset($config['acao']) ? (string)$config['acao'] : 'db';

	if ($endpoint === '' || $slug === '') {
		return ['status' => 'error', 'message' => 'Configuração distribuída incompleta (endpoint/slug).'];
	}

	$corpo = json_encode($payload, JSON_UNESCAPED_UNICODE);
	$assinatura = modulo_distribuido_assinar($corpo, $secret);

	$url = $endpoint . '/modulo-distribuido/' . rawurlencode($slug) . '/' . rawurlencode($acao);

	$headers = [
		'Content-Type: application/json',
		'X-C2F-Signature: ' . $assinatura,
		'X-C2F-Modulo: ' . $slug,
	];
	if (!empty($config['token'])) {
		$headers[] = 'Authorization: Bearer ' . $config['token'];
	}

	// Transporte injetável para testes (evita rede real).
	if (isset($config['transporte']) && is_callable($config['transporte'])) {
		$bruto = call_user_func($config['transporte'], $url, $corpo, $headers);
	} else {
		$bruto = modulo_distribuido_http_post($url, $corpo, $headers, $config['timeout'] ?? 15);
	}

	if ($bruto === false || $bruto === null || $bruto === '') {
		return ['status' => 'error', 'message' => 'Sem resposta da instalação distribuída.'];
	}

	$resposta = json_decode((string)$bruto, true);
	if (!is_array($resposta)) {
		return ['status' => 'error', 'message' => 'Resposta distribuída inválida (JSON).', 'raw' => substr((string)$bruto, 0, 500)];
	}
	return $resposta;
}

/**
 * POST HTTP simples via cURL. Isolado para permitir mock nos testes.
 *
 * @return string|false Corpo da resposta ou false em falha de transporte.
 */
function modulo_distribuido_http_post($url, $corpo, array $headers, $timeout = 15) {
	if (!function_exists('curl_init')) {
		return false;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$timeout);
	$resposta = curl_exec($ch);
	if ($resposta === false) {
		error_log('MODULO-DISTRIBUIDO: falha cURL para ' . $url . ': ' . curl_error($ch));
	}
	curl_close($ch);
	return $resposta;
}

/**
 * Converte a resposta remota em um resultado consumível por banco.php.
 *
 * Para SELECT retorna um BancoResultadoRemoto. Para operações de escrita retorna true
 * e registra insert_id / affected_rows em $_BANCO para banco_last_id()/consumo posterior.
 * Em erro retorna false.
 *
 * @param array $resposta Resposta decodificada da instalação distribuída.
 *
 * @return BancoResultadoRemoto|bool
 */
function modulo_distribuido_resposta_para_resultado(array $resposta) {
	global $_BANCO;

	$status = $resposta['status'] ?? 'error';
	if ($status !== 'ok') {
		if (isset($resposta['message'])) {
			error_log('MODULO-DISTRIBUIDO: erro remoto: ' . $resposta['message']);
		}
		return false;
	}

	$tipo = $resposta['tipo'] ?? 'write';
	if ($tipo === 'select') {
		$fields = isset($resposta['fields']) && is_array($resposta['fields']) ? $resposta['fields'] : [];
		$rows   = isset($resposta['rows']) && is_array($resposta['rows']) ? $resposta['rows'] : [];
		return new BancoResultadoRemoto($fields, $rows);
	}

	// Operação de escrita: guarda metadados para leitura posterior.
	$_BANCO['distribuido-insert-id'] = isset($resposta['insert_id']) ? (int)$resposta['insert_id'] : 0;
	$_BANCO['distribuido-affected-rows'] = isset($resposta['affected_rows']) ? (int)$resposta['affected_rows'] : 0;
	return true;
}

// =========================== Executor local (lado distribuído)

/**
 * Executa localmente, no banco do cliente, a instrução SQL recebida da central.
 *
 * Valida o tipo de operação (apenas SELECT/INSERT/UPDATE/DELETE) e rejeita múltiplas
 * instruções. Para SELECT retorna colunas + linhas; para escrita retorna affected_rows
 * e insert_id. Qualquer exceção é convertida em resposta de erro JSON-safe.
 *
 * @param array $payload Payload recebido (deve conter 'sql').
 * @param PDO   $pdo     Conexão PDO com o banco local do cliente.
 *
 * @return array Resposta pronta para json_encode: {status, tipo, fields, rows, ...}.
 */
function modulo_distribuido_executar_local(array $payload, PDO $pdo) {
	$sql = isset($payload['sql']) ? (string)$payload['sql'] : '';
	if (trim($sql) === '') {
		return ['status' => 'error', 'message' => 'Instrução SQL ausente.'];
	}

	if (!modulo_distribuido_sql_segura($sql)) {
		return ['status' => 'error', 'message' => 'Instrução SQL rejeitada por segurança (múltiplas instruções).'];
	}

	$operacao = modulo_distribuido_detectar_operacao($sql);
	if ($operacao === 'outro') {
		return ['status' => 'error', 'message' => 'Operação SQL não suportada no canal distribuído.'];
	}

	try {
		if (modulo_distribuido_operacao_leitura($operacao)) {
			$stmt = $pdo->query($sql);
			$fields = [];
			$colCount = $stmt->columnCount();
			for ($i = 0; $i < $colCount; $i++) {
				$meta = $stmt->getColumnMeta($i);
				$fields[] = isset($meta['name']) ? $meta['name'] : ('col' . $i);
			}
			$rows = [];
			while (($linha = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
				$rows[] = $linha;
			}
			return [
				'status' => 'ok',
				'tipo'   => 'select',
				'fields' => $fields,
				'rows'   => $rows,
			];
		}

		$affected = $pdo->exec($sql);
		$insertId = 0;
		if ($operacao === 'insert') {
			$insertId = (int)$pdo->lastInsertId();
		}
		return [
			'status'        => 'ok',
			'tipo'          => 'write',
			'affected_rows' => (int)$affected,
			'insert_id'     => $insertId,
		];
	} catch (\Throwable $e) {
		return ['status' => 'error', 'message' => 'Erro na execução local: ' . $e->getMessage()];
	}
}

/**
 * Guard básico contra múltiplas instruções empilhadas.
 *
 * O gestor monta uma única instrução por chamada; um ';' seguido de mais SQL
 * (fora de literais) é sinal de tentativa de empilhamento e deve ser rejeitado.
 *
 * @param string $sql Instrução SQL.
 *
 * @return bool true se aparenta ser uma instrução única e segura.
 */
function modulo_distribuido_sql_segura($sql) {
	$sql = (string)$sql;
	// Remove literais de string (aspas simples e duplas) para não confundir o ';'.
	$sem_literais = preg_replace('/\'(?:\\\\.|[^\'\\\\])*\'/s', "''", $sql);
	$sem_literais = preg_replace('/"(?:\\\\.|[^"\\\\])*"/s', '""', $sem_literais);
	$sem_literais = rtrim(trim($sem_literais), ';');
	// Após remover o ';' final opcional, não pode restar mais nenhum ';'.
	return strpos($sem_literais, ';') === false;
}

// =========================== Roteamento da API distribuída

/**
 * Faz o parse da rota da API distribuída: api/v1/modulo-distribuido/{slug}/{acao}.
 *
 * Recebe o array de segmentos do caminho (equivalente a $_GESTOR['caminho']) e localiza
 * o marcador 'modulo-distribuido', retornando o slug e a ação subsequentes.
 *
 * @param array $caminho Segmentos do caminho da URL.
 *
 * @return array|null ['slug' => ..., 'acao' => ..., 'resto' => [...]] ou null se não casar.
 */
function modulo_distribuido_parse_rota(array $caminho) {
	$idx = null;
	foreach ($caminho as $i => $seg) {
		if ($seg === 'modulo-distribuido') {
			$idx = $i;
			break;
		}
	}
	if ($idx === null) {
		return null;
	}

	$slug = isset($caminho[$idx + 1]) ? $caminho[$idx + 1] : null;
	if ($slug === null || $slug === '') {
		return null;
	}
	$slug = preg_replace('/[^a-z0-9\-_]/', '', strtolower($slug));
	if ($slug === '') {
		return null;
	}

	$acao = isset($caminho[$idx + 2]) ? $caminho[$idx + 2] : 'db';
	$acao = preg_replace('/[^a-z0-9\-_]/', '', strtolower($acao));
	if ($acao === '') {
		$acao = 'db';
	}

	$resto = array_slice($caminho, $idx + 3);

	return ['slug' => $slug, 'acao' => $acao, 'resto' => array_values($resto)];
}

// =========================== Fluxo de renderização (login / sem-permissão / iframe)
//
// PRINCÍPIO DE SEGURANÇA (autoridade central):
// As permissões são SEMPRE definidas e avaliadas no CENTRAL e apenas ACATADAS no
// distribuído. O ambiente distribuído nunca decide localmente se libera funcionalidades:
// ele consulta o middleware central e obedece à resposta. Toda ambiguidade/falha resolve
// para o estado mais restritivo ('login'), de modo que adulterar a biblioteca distribuída
// não concede acesso a dados do núcleo distribuído (fail-closed).

/**
 * Decide o estado de renderização do módulo distribuído a partir da validade do token.
 *
 * Considera apenas a autenticação (identidade). Para a decisão completa que inclui
 * o middleware de permissão por módulo, use modulo_distribuido_estado_renderizacao().
 *
 * @param bool $token_ativo Resultado de autenticacao_distribuido_token_ativo().
 *
 * @return string 'iframe' ou 'login'.
 */
function modulo_distribuido_estado_por_token_ativo(bool $token_ativo): string {
	return $token_ativo ? 'iframe' : 'login';
}

/**
 * Decide o estado de renderização considerando autenticação E permissão por módulo.
 *
 * Regras (req-005 + middleware de permissão):
 *  - token inválido/ausente  => 'login'          (tela de login/ativação);
 *  - autenticado sem permissão => 'sem-permissao' (página de falta de permissão);
 *  - autenticado e autorizado  => 'iframe'         (Iframe administrativo do central).
 *
 * Função pura para ser testável sem banco/OAuth/HTTP.
 *
 * @param bool $token_ativo   Usuário autenticado (token válido).
 * @param bool $tem_permissao Usuário autorizado ao módulo alvo.
 *
 * @return string 'login' | 'sem-permissao' | 'iframe'.
 */
function modulo_distribuido_estado_renderizacao(bool $token_ativo, bool $tem_permissao): string {
	if (!$token_ativo) {
		return 'login';
	}
	if (!$tem_permissao) {
		return 'sem-permissao';
	}
	return 'iframe';
}

/**
 * Traduz o 'estado' devolvido pelo middleware central (endpoint 'permissao') no estado
 * de renderização do ambiente distribuído.
 *
 * @param string $estado_central 'nao-autenticado' | 'sem-permissao' | 'permitido'.
 *
 * @return string 'login' | 'sem-permissao' | 'iframe'.
 */
function modulo_distribuido_estado_por_permissao_central($estado_central): string {
	switch ($estado_central) {
		case 'permitido':
			return 'iframe';
		case 'sem-permissao':
			return 'sem-permissao';
		default:
			return 'login';
	}
}

/**
 * Middleware de permissão (lado distribuído): consulta o central e devolve o estado.
 *
 * Faz uma chamada máquina-a-máquina (assinada por HMAC) ao endpoint central 'permissao',
 * enviando o token do usuário, e mapeia a resposta para o estado de renderização. Deve
 * ser aplicado a CADA requisição de qualquer módulo distribuído (comportamento de middleware).
 *
 * @param array       $config Config do canal (endpoint, slug, secret, transporte).
 * @param string      $token  Access token do usuário (obtido no signin).
 * @param string|null $slug   Slug do módulo alvo (default: $config['slug']).
 *
 * @return array ['estado' => 'login'|'sem-permissao'|'iframe', 'resposta' => array].
 */
function modulo_distribuido_middleware_permissao(array $config, $token, $slug = null) {
	$slug = $slug ?? ($config['slug'] ?? null);

	$payload = [
		'versao'    => 1,
		'token'     => (string)$token,
		'modulo'    => $slug,
		'timestamp' => time(),
		'nonce'     => bin2hex(random_bytes(8)),
	];

	$cfg = array_merge($config, ['acao' => 'permissao']);
	$resposta = modulo_distribuido_enviar($payload, $cfg);

	// api_response_success embrulha os dados em {status:'success', data:{estado:...}}.
	$estado_central = $resposta['data']['estado']
		?? $resposta['estado']
		?? 'nao-autenticado';

	return [
		'estado'   => modulo_distribuido_estado_por_permissao_central($estado_central),
		'resposta' => $resposta,
	];
}

/**
 * Guardião de módulo distribuído — fachada obrigatória de controle de acesso.
 *
 * Ponto ÚNICO que todo módulo distribuído invoca ANTES de liberar suas funcionalidades
 * na tela. Encapsula os controles gerais em uma só chamada:
 *   1) resolve o token do usuário;
 *   2) consulta o central (autenticação + permissão por módulo) via middleware HMAC;
 *   3) devolve o estado de renderização e, quando autorizado, a URL do Iframe do central.
 *
 * O módulo distribuído deve apenas ramificar pelo 'estado' retornado:
 *   - 'login'         => renderizar a tela de login/ativação;
 *   - 'sem-permissao' => renderizar a página de falta de permissão (instruções/suporte);
 *   - 'iframe'        => embutir o Iframe administrativo do central ('iframe_url').
 *
 * @param array $config Config do canal (endpoint, slug, secret, transporte, central-url).
 * @param array $opcoes 'token', 'central-url', 'opcao', 'params-iframe', 'slug'.
 *
 * @return array {
 *   estado: 'login'|'sem-permissao'|'iframe',
 *   token: string,
 *   iframe_url: string|null,
 *   resposta: array
 * }
 */
function modulo_distribuido_guardiao(array $config, array $opcoes = []) {
	$token = isset($opcoes['token']) ? (string)$opcoes['token'] : '';
	$slug  = $config['slug'] ?? ($opcoes['slug'] ?? null);

	$mw = modulo_distribuido_middleware_permissao($config, $token, $slug);
	$estado = $mw['estado'];

	$iframe_url = null;
	if ($estado === 'iframe') {
		$central_url = $opcoes['central-url']
			?? ($config['central-url'] ?? ($config['endpoint'] ?? ''));
		$iframe_url = modulo_distribuido_montar_url_iframe($central_url, $slug, [
			'opcao'  => $opcoes['opcao'] ?? null,
			'token'  => $token,
			'params' => $opcoes['params-iframe'] ?? [],
		]);
	}

	return [
		'estado'     => $estado,
		'token'      => $token,
		'iframe_url' => $iframe_url,
		'resposta'   => $mw['resposta'],
	];
}

/**
 * Autentica (ativa) o usuário no central e retorna os tokens — fluxo de login distribuído.
 *
 * Envia usuario/senha ao endpoint 'signin' do central (assinado por HMAC) e devolve os
 * dados de token quando as credenciais são válidas. O ambiente distribuído guarda o
 * access_token retornado (ex.: em sessão) para as requisições subsequentes.
 *
 * @param array  $config  Config do canal para o central (endpoint, secret, slug, transporte).
 * @param string $usuario Login do usuário.
 * @param string $senha   Senha em texto plano.
 *
 * @return array|false Dados dos tokens (access_token, refresh_token, ...) ou false.
 */
function modulo_distribuido_signin(array $config, $usuario, $senha) {
	$payload = [
		'versao'    => 1,
		'usuario'   => (string)$usuario,
		'senha'     => (string)$senha,
		'timestamp' => time(),
		'nonce'     => bin2hex(random_bytes(8)),
	];

	$cfg = array_merge($config, ['acao' => 'signin']);
	$resposta = modulo_distribuido_enviar($payload, $cfg);

	// api_response_success embrulha os dados em {status:'success', data:{access_token:...}}.
	$dados = $resposta['data'] ?? $resposta;
	if (is_array($dados) && !empty($dados['access_token'])) {
		return $dados;
	}
	return false;
}

// =========================== Middleware central (autoridade de permissão)

/**
 * Middleware central — avalia autenticação e permissão como AUTORIDADE do sistema.
 *
 * É a contraparte central do guardião distribuído: o distribuído apenas consulta e acata;
 * QUEM DECIDE é esta função, executada no central (onde vivem as tabelas de perfil/permissão).
 * O handler HTTP api_module_central_permissao() apenas valida o HMAC do canal e delega aqui.
 *
 * A resolução do usuário (a partir do token) e a verificação de permissão são injetáveis
 * (para testes), com defaults que usam a infraestrutura real (oauth2_validar_token e
 * autenticacao_distribuido_verificar_permissao_modulo). Falhas resolvem para o estado mais
 * restritivo ('nao-autenticado'), mantendo o comportamento fail-closed.
 *
 * @param string $token  Access token do usuário.
 * @param string $slug   Slug do módulo alvo.
 * @param array  $opcoes 'resolver_usuario' (callable(token):?int), 'verificar_permissao' (callable(id,slug):bool).
 *
 * @return array ['estado' => 'nao-autenticado'|'sem-permissao'|'permitido', 'id_usuarios' => int|null, 'modulo' => string].
 */
function modulo_distribuido_middleware_central($token, $slug, array $opcoes = []) {
	$resolver_usuario = $opcoes['resolver_usuario'] ?? function ($tk) {
		if (!function_exists('oauth2_validar_token')) {
			return null;
		}
		$dados = ((string)$tk !== '') ? oauth2_validar_token(['token' => (string)$tk]) : false;
		if (!is_array($dados) || empty($dados)) {
			return null;
		}
		return $dados['sub'] ?? ($dados['id_usuarios'] ?? null);
	};

	$verificar_permissao = $opcoes['verificar_permissao'] ?? function ($id, $mod) {
		return function_exists('autenticacao_distribuido_verificar_permissao_modulo')
			&& autenticacao_distribuido_verificar_permissao_modulo($id, $mod);
	};

	$id_usuarios = $resolver_usuario((string)$token);
	if (!$id_usuarios) {
		return ['estado' => 'nao-autenticado', 'id_usuarios' => null, 'modulo' => (string)$slug];
	}

	$permitido = (bool)$verificar_permissao((int)$id_usuarios, (string)$slug);

	return [
		'estado'      => $permitido ? 'permitido' : 'sem-permissao',
		'id_usuarios' => (int)$id_usuarios,
		'modulo'      => (string)$slug,
	];
}

/**
 * Monta a URL administrativa do módulo no central para renderização em Iframe.
 *
 * @param string $endpoint_central URL base do central (ex.: https://conn2flow.com/).
 * @param string $slug             Slug do módulo (central) a ser embutido.
 * @param array  $opcoes           'modulo' (override do slug na URL), 'opcao', 'token', 'params' (extra).
 *
 * @return string URL absoluta para o src do Iframe.
 */
function modulo_distribuido_montar_url_iframe($endpoint_central, $slug, array $opcoes = []) {
	$base = rtrim((string)$endpoint_central, '/');
	$modulo = !empty($opcoes['modulo']) ? (string)$opcoes['modulo'] : (string)$slug;

	$params = ['embed' => 1];
	if (!empty($opcoes['opcao'])) {
		$params['opcao'] = $opcoes['opcao'];
	}
	if (!empty($opcoes['token'])) {
		$params['token'] = $opcoes['token'];
	}
	if (!empty($opcoes['params']) && is_array($opcoes['params'])) {
		$params = array_merge($params, $opcoes['params']);
	}

	$query = http_build_query($params);
	return $base . '/' . rawurlencode($modulo) . '/' . ($query !== '' ? '?' . $query : '');
}

// =========================== Flag de contexto (banco)

/**
 * Ativa o modo distribuído do banco para as próximas operações.
 *
 * Enquanto ativo, banco_query() delega a execução para a instalação distribuída
 * configurada. Deve envolver apenas as operações de DADOS do módulo distribuído;
 * as demais operações do request (autenticação, variáveis, etc.) continuam locais.
 *
 * @param array $config Configuração do canal (endpoint, slug, secret, token, ...).
 *
 * @return void
 */
function banco_distribuido_iniciar(array $config) {
	global $_BANCO;
	$_BANCO['distribuido'] = $config;
}

/**
 * Desativa o modo distribuído do banco, retornando às operações locais.
 *
 * @return void
 */
function banco_distribuido_finalizar() {
	global $_BANCO;
	unset($_BANCO['distribuido']);
}

/**
 * Indica se o modo distribuído do banco está ativo no contexto atual.
 *
 * @return bool
 */
function banco_distribuido_ativo(): bool {
	global $_BANCO;
	return !empty($_BANCO['distribuido']) && is_array($_BANCO['distribuido']);
}

/**
 * Executa uma query no modo distribuído: empacota, envia e converte a resposta.
 *
 * Chamada por banco_query() quando o modo distribuído está ativo. Mantém a mesma
 * semântica de retorno de banco_query(): resultado (remoto) para SELECT, true para
 * escrita bem-sucedida, false em erro.
 *
 * @param string $sql Instrução SQL montada pelas funções de banco.php.
 *
 * @return BancoResultadoRemoto|bool
 */
function banco_distribuido_query($sql) {
	global $_BANCO, $_GESTOR;

	$config = isset($_BANCO['distribuido']) && is_array($_BANCO['distribuido']) ? $_BANCO['distribuido'] : [];

	$payload = modulo_distribuido_montar_payload($sql, [
		'modulo'    => $config['slug'] ?? ($_GESTOR['modulo-id'] ?? null),
		'linguagem' => $_GESTOR['linguagem-codigo'] ?? null,
	]);

	$resposta = modulo_distribuido_enviar($payload, $config);

	return modulo_distribuido_resposta_para_resultado($resposta);
}

// =========================== Resolução de configuração e orquestração de módulo
//
// Estas funções concentram os controles GERAIS que antes se repetiam em cada módulo
// distribuído. Um módulo deve conter apenas o que é específico dele (sua tabela/CRUD);
// a resolução de canal, o wrapper de persistência, o token de sessão, o controle de
// acesso (guardião) e a renderização por estado ficam AQUI, na biblioteca obrigatória.

/**
 * Lê um valor de $_CONFIG por caminho em dot-notation (ex.: 'modulo-distribuido.secret').
 *
 * @param string $path    Caminho em dot-notation.
 * @param mixed  $default Valor padrão quando o caminho não existe.
 *
 * @return mixed
 */
function modulo_distribuido_config_get($path, $default = null) {
	global $_CONFIG;

	if (!is_string($path) || $path === '') {
		return $default;
	}
	$atual = $_CONFIG;
	foreach (explode('.', $path) as $parte) {
		if (is_array($atual) && array_key_exists($parte, $atual)) {
			$atual = $atual[$parte];
		} else {
			return $default;
		}
	}
	return $atual;
}

/**
 * Resolve a config do canal para um MÓDULO CENTRAL (que delega banco ao distribuído).
 *
 * Fontes (com override): manifesto `distributed` (target-slug, endpoint-config, secret-config)
 * → $_CONFIG (`modulo-distribuido.endpoints.<slug>` / `.endpoint`, `.secrets.<slug>` / `.secret`)
 * → variáveis de ambiente. Pronta para banco_distribuido_iniciar().
 *
 * @param array $modulo_config Manifesto do módulo central.
 * @param array $overrides     endpoint, secret, slug, token, timeout, transporte.
 *
 * @return array
 */
function modulo_distribuido_canal_central(array $modulo_config, array $overrides = []) {
	$dist = $modulo_config['distributed'] ?? [];
	$slug = $overrides['slug'] ?? ($dist['target-slug'] ?? null);

	$endpoint = $overrides['endpoint']
		?? (isset($dist['endpoint-config']) ? modulo_distribuido_config_get($dist['endpoint-config']) : null)
		?? ($slug ? modulo_distribuido_config_get('modulo-distribuido.endpoints.' . $slug) : null)
		?? modulo_distribuido_config_get('modulo-distribuido.endpoint');

	$secret = $overrides['secret']
		?? (isset($dist['secret-config']) ? modulo_distribuido_config_get($dist['secret-config']) : null)
		?? ($slug ? modulo_distribuido_config_get('modulo-distribuido.secrets.' . $slug) : null)
		?? modulo_distribuido_config_get('modulo-distribuido.secret')
		?? '';

	$config = [
		'slug'     => $slug,
		'endpoint' => $endpoint ? rtrim((string)$endpoint, '/') : '',
		'secret'   => (string)$secret,
		'acao'     => 'db',
	];
	if (!empty($overrides['token']))   { $config['token'] = (string)$overrides['token']; }
	if (!empty($overrides['timeout'])) { $config['timeout'] = (int)$overrides['timeout']; }
	if (isset($overrides['transporte']) && is_callable($overrides['transporte'])) {
		$config['transporte'] = $overrides['transporte'];
	}
	return $config;
}

/**
 * Resolve a config do canal para um MÓDULO DISTRIBUÍDO (que consulta o central).
 *
 * Fontes (com override): manifesto `distributed.central-slug` → $_CONFIG
 * (`modulo-distribuido.central-url`, `.secret`) → variáveis de ambiente.
 *
 * @param array $modulo_config Manifesto do módulo distribuído.
 * @param array $overrides     central-url, secret, slug, transporte.
 *
 * @return array Inclui 'endpoint' (central-url/_api) e 'central-url'.
 */
function modulo_distribuido_canal_distribuido(array $modulo_config, array $overrides = []) {
	$dist = $modulo_config['distributed'] ?? [];
	$slug = $overrides['slug'] ?? ($dist['central-slug'] ?? null);

	$central_url = $overrides['central-url']
		?? modulo_distribuido_config_get('modulo-distribuido.central-url')
		?? 'https://conn2flow.com/';

	$secret = $overrides['secret']
		?? ($slug ? modulo_distribuido_config_get('modulo-distribuido.secrets.' . $slug) : null)
		?? modulo_distribuido_config_get('modulo-distribuido.secret')
		?? '';

	$config = [
		'slug'        => $slug,
		'central-url' => (string)$central_url,
		'endpoint'    => rtrim((string)$central_url, '/') . '/_api',
		'secret'      => (string)$secret,
	];
	if (isset($overrides['transporte']) && is_callable($overrides['transporte'])) {
		$config['transporte'] = $overrides['transporte'];
	}
	return $config;
}

/**
 * Executa uma operação de dados dentro do canal distribuído, garantindo o fechamento.
 *
 * @param callable $operacao Função com as chamadas de banco (delegadas ao distribuído).
 * @param array    $config   Config do canal (de modulo_distribuido_canal_central()).
 *
 * @return mixed Retorno de $operacao.
 */
function modulo_distribuido_com_canal(callable $operacao, array $config) {
	banco_distribuido_iniciar($config);
	try {
		return $operacao();
	} finally {
		banco_distribuido_finalizar();
	}
}

/**
 * Lê, da sessão, o token de acesso ao central guardado por um módulo distribuído.
 *
 * @param string $chave Chave da variável de sessão.
 *
 * @return string Token ou string vazia.
 */
function modulo_distribuido_token_sessao($chave) {
	if (function_exists('gestor_sessao_variavel')) {
		$token = gestor_sessao_variavel($chave);
		if (is_string($token)) {
			return $token;
		}
	}
	return '';
}

/**
 * Persiste os tokens obtidos no login/ativação distribuído.
 *
 * Por padrão grava o access_token (e o refresh, quando houver) nas variáveis de sessão do
 * gestor. A persistência é injetável (callable) para permitir testes sem sessão/banco.
 *
 * @param string        $chave      Chave base da variável de sessão do token.
 * @param array         $tokens     Tokens retornados pelo central (access_token, refresh_token, ...).
 * @param callable|null $persistir  Persistência customizada: function($chave, array $tokens): void.
 *
 * @return void
 */
function modulo_distribuido_persistir_token($chave, array $tokens, $persistir = null) {
	if (is_callable($persistir)) {
		$persistir($chave, $tokens);
		return;
	}
	if (function_exists('gestor_sessao_variavel')) {
		if (!empty($tokens['access_token'])) {
			gestor_sessao_variavel($chave, $tokens['access_token']);
		}
		if (!empty($tokens['refresh_token'])) {
			gestor_sessao_variavel($chave . '-refresh', $tokens['refresh_token']);
		}
	}
}

/**
 * Dicionário de textos (i18n) do componente global de estados distribuídos.
 *
 * Como o componente `modulo-distribuido-app` é de sistema (reutilizável por qualquer
 * módulo), os textos são resolvidos aqui por idioma, com override por módulo via $overrides.
 *
 * @param string|null $lang      Idioma (default: $_GESTOR['linguagem-codigo']).
 * @param array       $overrides Sobrescritas de texto por chave (#c2f-md-*# sem as cercas).
 *
 * @return array Mapa chave => texto.
 */
function modulo_distribuido_textos($lang = null, array $overrides = []) {
	global $_GESTOR;
	$lang = $lang ?: ($_GESTOR['linguagem-codigo'] ?? 'pt-br');

	$dicionario = [
		'pt-br' => [
			'c2f-md-login-title'     => 'Ativar acesso',
			'c2f-md-login-subtitle'  => 'Entre com suas credenciais para ativar este módulo neste site.',
			'c2f-md-login-user'      => 'Usuário',
			'c2f-md-login-pass'      => 'Senha',
			'c2f-md-login-submit'    => 'Ativar e entrar',
			'c2f-md-login-error'     => '',
			'c2f-md-signin-action'   => '',
			'c2f-md-noperm-title'    => 'Sem permissão de acesso',
			'c2f-md-noperm-message'  => 'Seu usuário não tem permissão para acessar este módulo. Atualize o perfil de acesso ou entre em contato com o suporte caso já possua o plano correto.',
			'c2f-md-noperm-support'  => 'Falar com o suporte',
			'c2f-md-support-url'     => '#',
			'c2f-md-iframe-title'    => 'Painel administrativo',
			'c2f-md-login-invalido'  => 'Usuário ou senha inválidos.',
		],
		'en' => [
			'c2f-md-login-title'     => 'Activate access',
			'c2f-md-login-subtitle'  => 'Sign in with your credentials to activate this module on this site.',
			'c2f-md-login-user'      => 'User',
			'c2f-md-login-pass'      => 'Password',
			'c2f-md-login-submit'    => 'Activate and sign in',
			'c2f-md-login-error'     => '',
			'c2f-md-signin-action'   => '',
			'c2f-md-noperm-title'    => 'No access permission',
			'c2f-md-noperm-message'  => 'Your user does not have permission to access this module. Update your access profile or contact support if you already have the correct plan.',
			'c2f-md-noperm-support'  => 'Contact support',
			'c2f-md-support-url'     => '#',
			'c2f-md-iframe-title'    => 'Administrative panel',
			'c2f-md-login-invalido'  => 'Invalid user or password.',
		],
	];

	$textos = $dicionario[$lang] ?? $dicionario['pt-br'];
	return array_merge($textos, $overrides);
}

/**
 * Processa o HTML do componente de estados segundo o estado de acesso (função pura).
 *
 * Passos: preenche os textos (#c2f-md-*#), injeta o estado no data-attr (#c2f-md-state#),
 * substitui #iframe-src# (quando 'iframe') e mantém apenas o bloco do estado atual
 * (blocos delimitados por `<!-- estado < --> ... <!-- estado > -->`), removendo os demais.
 *
 * @param string      $html       HTML do componente (de gestor_componente()).
 * @param string      $estado     'login' | 'sem-permissao' | 'iframe'.
 * @param string|null $iframe_url URL do Iframe (quando 'iframe').
 * @param array       $textos     Mapa de textos (de modulo_distribuido_textos()).
 *
 * @return string HTML final pronto para injeção.
 */
function modulo_distribuido_render_componente($html, $estado, $iframe_url = null, array $textos = []) {
	$html = (string)$html;

	// Textos (i18n) e estado.
	foreach ($textos as $chave => $valor) {
		$html = str_replace('#' . $chave . '#', (string)$valor, $html);
	}
	$html = str_replace('#c2f-md-state#', (string)$estado, $html);

	if ($estado === 'iframe') {
		$html = str_replace('#iframe-src#', (string)$iframe_url, $html);
	}

	// Mantém apenas o bloco do estado atual; remove os demais.
	foreach (['login', 'sem-permissao', 'iframe'] as $e) {
		$tagIn  = '<!-- ' . $e . ' < -->';
		$tagOut = '<!-- ' . $e . ' > -->';
		if ($e === $estado) {
			$html = str_replace([$tagIn, $tagOut], '', $html);
		} elseif (function_exists('modelo_tag_del')) {
			$html = modelo_tag_del($html, $tagIn, $tagOut);
		} else {
			// Fallback sem a lib de modelo: remove o bloco por regex.
			$html = preg_replace(
				'/' . preg_quote($tagIn, '/') . '.*?' . preg_quote($tagOut, '/') . '/s',
				'', $html
			);
		}
	}

	return $html;
}

/**
 * Aplica a renderização por estado diretamente sobre a página do módulo ($_GESTOR['pagina']).
 *
 * Retrocompatível para módulos que embutem os blocos de estado na própria página.
 * O caminho recomendado é usar o componente global via modulo_distribuido_app().
 *
 * @param string      $estado     'login' | 'sem-permissao' | 'iframe'.
 * @param string|null $iframe_url URL do Iframe (quando 'iframe').
 *
 * @return string O próprio estado.
 */
function modulo_distribuido_render_estado($estado, $iframe_url = null) {
	global $_GESTOR;

	if (!isset($_GESTOR['javascript-vars']) || !is_array($_GESTOR['javascript-vars'])) {
		$_GESTOR['javascript-vars'] = [];
	}
	$_GESTOR['javascript-vars']['moduloDistribuidoEstado'] = $estado;

	if (isset($_GESTOR['pagina']) && is_string($_GESTOR['pagina'])) {
		$_GESTOR['pagina'] = modulo_distribuido_render_componente(
			$_GESTOR['pagina'], $estado, $iframe_url, modulo_distribuido_textos()
		);
	}
	return $estado;
}

/**
 * Orquestração completa do "app" de um módulo distribuído (fachada de alto nível).
 *
 * Ponto único invocado pela opção principal de qualquer módulo distribuído:
 *   1) resolve o token do usuário (sessão, salvo override);
 *   2) aplica o guardião (autoridade central: autenticação + permissão);
 *   3) renderiza o estado resultante (login / sem-permissão / iframe).
 *
 * Obtém o componente global `modulo-distribuido-app` (via gestor_componente), processa-o
 * para o estado resolvido e o injeta na página do módulo no lugar do placeholder
 * `#modulo-distribuido-app#`. Assim, todo módulo distribuído renderiza as três telas
 * (login / sem-permissão / iframe) com o mesmo componente de sistema, sem duplicar HTML.
 *
 * @param array $config Config do canal (de modulo_distribuido_canal_distribuido()).
 * @param array $opcoes 'token', 'token-chave', 'opcao', 'params-iframe', 'central-url',
 *                      'componente', 'placeholder', 'lang', 'textos', 'html' (override p/ testes).
 *
 * @return array Resultado do guardião + 'html' (componente renderizado).
 */
function modulo_distribuido_app(array $config, array $opcoes = []) {
	global $_GESTOR;

	$chave = $opcoes['token-chave'] ?? (($config['slug'] ?? 'modulo') . '-token');
	$lang = $opcoes['lang'] ?? null;

	// ===== Captura do submit de login/ativação (POST do form do componente).
	// Autentica no central, guarda o access_token na sessão e o usa na mesma requisição.
	$erro_login = '';
	if (isset($_REQUEST['c2f-md-signin']) && isset($_REQUEST['usuario'], $_REQUEST['senha'])) {
		$tokens = modulo_distribuido_signin($config, (string)$_REQUEST['usuario'], (string)$_REQUEST['senha']);
		if (is_array($tokens) && !empty($tokens['access_token'])) {
			$opcoes['token'] = $tokens['access_token'];
			modulo_distribuido_persistir_token($chave, $tokens, $opcoes['persistir-token'] ?? null);
		} else {
			$textosErro = modulo_distribuido_textos($lang);
			$erro_login = $opcoes['erro-login'] ?? $textosErro['c2f-md-login-invalido'];
		}
	}

	$token = $opcoes['token'] ?? modulo_distribuido_token_sessao($chave);

	$guarda = modulo_distribuido_guardiao($config, [
		'token'         => $token,
		'opcao'         => $opcoes['opcao'] ?? null,
		'central-url'   => $opcoes['central-url'] ?? null,
		'params-iframe' => $opcoes['params-iframe'] ?? [],
	]);
	$estado = $guarda['estado'];

	// Textos (i18n) com override do módulo; mensagem de erro de login quando houver.
	$overridesTexto = $opcoes['textos'] ?? [];
	if ($erro_login !== '') {
		$overridesTexto['c2f-md-login-error'] = $erro_login;
	}
	$textos = modulo_distribuido_textos($lang, $overridesTexto);

	// HTML do componente global (override direto em testes; senão via gestor_componente).
	$componente_id = $opcoes['componente'] ?? 'modulo-distribuido-app';
	if (isset($opcoes['html'])) {
		$html = (string)$opcoes['html'];
	} elseif (function_exists('gestor_componente')) {
		$html = (string)gestor_componente(['id' => $componente_id]);
	} else {
		$html = '';
	}

	$html = modulo_distribuido_render_componente($html, $estado, $guarda['iframe_url'], $textos);

	// Expõe o estado ao JS e injeta o componente na página do módulo.
	if (!isset($_GESTOR['javascript-vars']) || !is_array($_GESTOR['javascript-vars'])) {
		$_GESTOR['javascript-vars'] = [];
	}
	$_GESTOR['javascript-vars']['moduloDistribuidoEstado'] = $estado;

	$placeholder = $opcoes['placeholder'] ?? '#modulo-distribuido-app#';
	if (isset($_GESTOR['pagina']) && is_string($_GESTOR['pagina'])) {
		$_GESTOR['pagina'] = str_replace($placeholder, $html, $_GESTOR['pagina']);
	}

	$guarda['html'] = $html;
	return $guarda;
}
