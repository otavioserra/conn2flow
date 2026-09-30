<?php

// ===== Controlador da API do Conn2Flow

global $_GESTOR;

$_GESTOR['modulo-id']							=	'api';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Headers CORS e Configurações

function api_cors_configurar() {
    global $_CONFIG;

    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    $permitidas = $_CONFIG['api']['cors-origins'] ?? [];
    if ($origin !== '' && in_array($origin, $permitidas, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    return $origin === '' || in_array($origin, $permitidas, true);
}

$corsPermitido = api_cors_configurar();
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// ===== Resposta para preflight OPTIONS
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code($corsPermitido ? 204 : 403);
    exit;
}

// =========================== Rate Limiting Básico

function api_bearer_token() {
    $authorization = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($authorization === '' && function_exists('getallheaders')) {
        foreach ((array)getallheaders() as $nome => $valor) {
            if (strcasecmp((string)$nome, 'Authorization') === 0) {
                $authorization = (string)$valor;
                break;
            }
        }
    }

    return preg_match('/^Bearer\s+([^\s]+)$/i', trim($authorization), $matches) ? $matches[1] : null;
}

function api_token_validar_memoizado($token) {
    global $_GESTOR;

    $chave = hash('sha256', (string)$token);
    if (array_key_exists($chave, $_GESTOR['api-auth-cache'] ?? [])) return $_GESTOR['api-auth-cache'][$chave];

    // req-119: Personal Access Token e token OAuth 2.0 chegam pelo MESMO `Authorization: Bearer`.
    // O desempate é pelo formato (`c2f_pat_`), antes de qualquer validação: sem ele todo PAT passaria
    // pelo validador de JWT, falharia na decodificação e o usuário receberia "token inválido" sem
    // nenhuma pista do motivo. Os dois validadores devolvem o MESMO contrato, então nenhum endpoint
    // precisou aprender um segundo formato.
    gestor_incluir_biblioteca('usuario');

    if (function_exists('usuario_api_token_formato') && usuario_api_token_formato($token)) {
        $resultado = usuario_api_token_validar($token);
    } else {
        gestor_incluir_biblioteca('oauth2');
        $resultado = oauth2_validar_token(['token' => $token]);
    }

    $_GESTOR['api-auth-cache'][$chave] = is_array($resultado) ? $resultado : false;

    return $_GESTOR['api-auth-cache'][$chave];
}

function api_rate_limit_subject() {
    $token = api_bearer_token();
    if ($token !== null) {
        $usuario = api_token_validar_memoizado($token);
        if (is_array($usuario) && isset($usuario['id_usuarios'])) return 'user:' . (int)$usuario['id_usuarios'];
    }

    return 'ip:' . (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/**
 * Contabiliza a requisição na janela corrente e devolve o total acumulado.
 *
 * req-108: esta linha do core (2.x) roda em PHP anterior ao 8.5 e usa exclusivamente as
 * bibliotecas antigas — aqui, `banco.php`, que já vem carregada em toda requisição
 * (`config.php`, `$_GESTOR['bibliotecas']`). As bibliotecas `*-v2` pertencem à linha 3.0.x,
 * que exige PHP 8.5, e não existem nesta branch. Chamá-las aqui foi o defeito original: o
 * `require` lançava `ParseError`, que é `Throwable`, era engolido pelo catch desta função e
 * virava um falso "rate limit excedido".
 *
 * @return int|null Total de requisições na janela, ou null se a contagem não pôde ser lida.
 */
function api_rate_limit_contabilizar($route, $subject, $windowStart) {
    $routeEscapado   = banco_escape_field($route);
    $subjectEscapado = banco_escape_field($subject);
    $janela          = (int)$windowStart;

    $insercao = banco_query(
        "INSERT INTO api_rate_limits (route, subject, window_start, request_count, updated_at) "
        . "VALUES ('".$routeEscapado."', '".$subjectEscapado."', ".$janela.", 1, NOW()) "
        . "ON DUPLICATE KEY UPDATE request_count=request_count+1, updated_at=NOW()"
    );

    // banco_query() devolve false quando a instrução falha (tabela ausente, conexão caída…).
    if ($insercao === false) return null;

    $linhas = banco_sql(
        "SELECT request_count FROM api_rate_limits"
        . " WHERE route='".$routeEscapado."'"
        . " AND subject='".$subjectEscapado."'"
        . " AND window_start=".$janela
        . " LIMIT 1"
    );

    if (!isset($linhas[0]['request_count'])) return null;

    return (int)$linhas[0]['request_count'];
}

/**
 * Avalia o rate limit da rota.
 *
 * req-108: os três desfechos são distintos de propósito. Antes, qualquer falha de
 * infraestrutura era convertida em `false` e apresentada ao operador como "Rate limit
 * excedido", escondendo a causa real (um erro de sintaxe) atrás de uma mensagem plausível.
 *
 * @return bool|null true = dentro do limite; false = limite excedido;
 *                   null = não foi possível avaliar (falha de infraestrutura).
 */
function api_rate_limit_check($endpoint = 'default') {
    global $_CONFIG;

    $maxRequests = (int)($_CONFIG['api']['rate-limit-max'] ?? 100);
    $window = (int)($_CONFIG['api']['rate-limit-window'] ?? 3600);
    $windowStart = intdiv(time(), $window) * $window;
    $route = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) . ':' . (string)$endpoint;
    $subject = api_rate_limit_subject();

    try {
        $total = api_rate_limit_contabilizar($route, $subject, $windowStart);

        // Sem contagem legível não há como afirmar que o limite foi excedido.
        if ($total === null) return null;

        return $total <= $maxRequests;
    } catch (Throwable $e) {
        error_log(
            'Falha ao avaliar o rate limit da API (' . get_class($e) . '): ' . $e->getMessage()
            . ' em ' . $e->getFile() . ':' . $e->getLine()
        );
        return null;
    }
}

// =========================== Autenticação

function api_authenticate($require_auth = false) {
    global $_GESTOR;

    if (!$require_auth) {
        return true; // Endpoint público
    }

    // Verificar token de autenticação
    $token = api_bearer_token();

    if (!$token) {
        api_response_error('Token de autenticação não fornecido', 401);
    }

    // Validação real do token OAuth 2.0
    $token_validacao = api_token_validar_memoizado($token);
    if(!$token_validacao || !is_array($token_validacao)){
        api_response_error('Token de autenticação inválido ou expirado', 401);
    }

    return $token_validacao;
}

// =========================== Funções de Resposta

function api_response_success($data = null, $message = 'OK', $code = 200) {
    http_response_code($code);

    $response = [
        'status' => 'success',
        'message' => $message,
        'timestamp' => date('c')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_response_error($message = 'Erro interno do servidor', $code = 500, $details = null) {
    http_response_code($code);

    $response = [
        'status' => 'error',
        'message' => $message,
        'timestamp' => date('c')
    ];

    if ($details !== null) {
        $response['details'] = $details;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// =========================== Parse do Corpo da Requisição

function api_get_request_body() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        api_response_error('JSON inválido no corpo da requisição', 400);
    }

    return $data;
}

// =========================== Handlers de Endpoint PROJECT

function api_handle_project() {
    global $_GESTOR;

    // Verificar sub-endpoint
    $sub_endpoint = isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null;

    switch ($sub_endpoint) {
        case 'update':
            api_project_update();
            break;

        case 'recover':
            api_project_recover();
            break;

        case 'rollback':
            api_project_rollback();
            break;

        case 'conflicts':
            api_project_conflicts();
            break;

        case 'resolve':
            api_project_resolve();
            break;

        default:
            api_response_error('Sub-endpoint PROJECT não encontrado: ' . $sub_endpoint, 404);
    }
}

function api_project_update() {
    global $_GESTOR;

    // Requer autenticação
    api_authenticate(true);

    // Obter project ID do header
    $project_id = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    // Verificar se é multipart/form-data
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($content_type, 'multipart/form-data') === false) {
        api_response_error('Content-Type deve ser multipart/form-data', 400);
    }

    // Verificar se arquivo foi enviado
    if (!isset($_FILES['project_zip']) || $_FILES['project_zip']['error'] !== UPLOAD_ERR_OK) {
        api_response_error('Arquivo project_zip não foi enviado ou houve erro no upload', 400);
    }

    $uploaded_file = $_FILES['project_zip'];
    $temp_path = $uploaded_file['tmp_name'];
    $original_name = $uploaded_file['name'];

    // Validar extensão do arquivo
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if ($file_extension !== 'zip') {
        api_response_error('Apenas arquivos ZIP são permitidos', 400);
    }

    // Verificar tamanho do arquivo (máximo 100MB)
    $max_size = 100 * 1024 * 1024; // 100MB
    if ($uploaded_file['size'] > $max_size) {
        api_response_error('Arquivo muito grande. Máximo permitido: 100MB', 400);
    }

    // req-197: trava de deploy do ambiente — a mesma da atualização do sistema (`temp/deploy.lock`).
    // Com outro deploy rodando, recusa com 409 dizendo quem é. A resposta sai por `exit` (dentro de
    // api_response_*), que não executa `finally`: a liberação fica num shutdown function.
    require_once $_GESTOR['bibliotecas-path'] . 'deploy-lock.php';
    $trava_arquivo = rtrim($_GESTOR['ROOT_PATH'], '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'deploy.lock';
    $trava = deploy_lock_acquire($trava_arquivo, ['owner' => 'api-project-update', 'detail' => (string)$project_id]);
    if (!$trava['ok']) {
        api_response_error('Outro deploy está em execução neste ambiente: ' . deploy_lock_describe($trava['holder'] ?? null), 409);
    }
    register_shutdown_function(function () use ($trava_arquivo, $trava) {
        deploy_lock_release($trava_arquivo, $trava['token']);
    });

    // req-198 / BATCH-204: posição do log de erros antes da entrega (a verificação procura fatais novos).
    require_once $_GESTOR['bibliotecas-path'] . 'instalacao-manifesto.php';
    $saude_offset = instalacao_saude_log_offset($_GESTOR['ROOT_PATH']);

    // Criar diretório temporário para processamento
    $temp_dir = $_GESTOR['logs-path'] . 'temp_projects/';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }

    // Diretório temporário único para este upload
    $extract_dir = $temp_dir . 'upload_' . time() . '_' . uniqid() . '/';
    mkdir($extract_dir, 0755, true);

    try {
        // Mover arquivo para local temporário seguro
        $zip_path = $extract_dir . 'project.zip';
        if (!move_uploaded_file($temp_path, $zip_path)) {
            throw new Exception('Falha ao salvar arquivo temporário');
        }

        // Descompactar ZIP
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            throw new Exception('Falha ao abrir arquivo ZIP');
        }

        $zip->extractTo($extract_dir);
        $zip->close();

        // Usar a raiz do sistema como destino
        $project_path = $_GESTOR['ROOT_PATH'];

        // Encontrar o diretório do conteúdo do projeto (pode ser diretamente no extract_dir ou em um subdiretório)
        $project_content_dir = $extract_dir;
        
        // Verificar se há um único diretório dentro do extract_dir (caso comum de ZIP com diretório raiz)
        $extracted_items = array_diff(scandir($extract_dir), ['.', '..']);
        if (count($extracted_items) === 1 && is_dir($extract_dir . DIRECTORY_SEPARATOR . $extracted_items[0])) {
            $project_content_dir = $extract_dir . DIRECTORY_SEPARATOR . $extracted_items[0];
        }

        // req-194: migrações obsoletas do projeto saem antes da cópia (a cópia só sobrescreve e uma
        // migração renomeada deixava a antiga, travando o Phinx em todos os deploys seguintes).
        $migracoes = api_project_migracoes_limpar($project_content_dir, $project_path);

        // req-198: arquivos pela camada `projeto` do manifesto (precedência sobre o core, originais do core
        // guardadas, o que o projeto deixou de entregar sai ou devolve a original do core).
        $instalacao = api_project_aplicar_arquivos($project_content_dir, $project_path, (string)$project_id);
        $snapshot_dir = instalacao_snapshot_dir($project_path, $instalacao['snapshot']);

        // req-198 / BATCH-204: dump do banco no snapshot, antes da etapa de banco.
        $dump = instalacao_banco_dump($snapshot_dir, (array)($GLOBALS['_BANCO'] ?? []));
        $instalacao['banco_dump'] = $dump['ok'] ? ['ok' => true, 'mb' => $dump['mb']] : ['ok' => false, 'erro' => $dump['erro']];

        // Parâmetro opcional full_log: quando ativo, retorna o log completo de debug do banco.
        $full_log = isset($_POST['full_log']) && filter_var($_POST['full_log'], FILTER_VALIDATE_BOOLEAN);

        // Executar atualização de banco de dados do projeto (inline, sem shell_exec)
        $db_logs = api_executar_atualizacao_banco($project_path, $project_id, $full_log);

        // req-198: choques pendentes vão para a tabela depois do banco (a migração pode ter acabado de criá-la).
        $instalacao['choques_gravados'] = api_project_choques_gravar($project_path);

        // Sincronizar hooks do projeto após atualização do banco
        require_once $_GESTOR['controladores-path'] . 'atualizacoes/atualizacoes-hooks.php';
        atualizacoes_hooks_sincronizar();

        // req-188: o sitemap só era mantido pelas edições do painel; páginas que chegam pelo deploy
        // ficavam de fora. Regenera depois de todas as páginas atualizadas.
        $sitemap = api_project_sitemap_regenerar();

        // Limpar arquivos temporários
        api_remove_directory($extract_dir);

        // req-198 / BATCH-204: verificação depois da entrega; falhou, os arquivos voltam do snapshot.
        $saude = null;
        if (!api_post_bool('no_health')) {
            $saude = instalacao_saude_verificar($project_path, $saude_offset, '', api_project_saude_opcoes());
            if (!$saude['ok'] && !api_post_bool('no_rollback')) {
                $r = instalacao_snapshot_restaurar($project_path, $snapshot_dir);
                $depois = instalacao_saude_verificar($project_path, instalacao_saude_log_offset($project_path), '', api_project_saude_opcoes());
                api_response_error('Verificação pós-deploy falhou; arquivos voltaram ao estado anterior. O banco não foi revertido: use o rollback com com_banco.', 500, [
                    'status' => 'rolled_back',
                    'snapshot' => $instalacao['snapshot'],
                    'saude' => $saude,
                    'rollback' => ['restaurados' => $r['restaurados'] ?? 0, 'removidos_novos' => $r['removidos_novos'] ?? 0, 'falhas' => $r['falhas'] ?? []],
                    'depois_do_rollback' => $depois,
                    'installation' => $instalacao,
                    'db_logs' => $db_logs,
                ]);
            }
        }

        // Resposta de sucesso
        $response_data = [
            'file_size' => $uploaded_file['size'],
            'updated_at' => date('c'),
            'status' => 'updated',
            'db_logs' => $db_logs,
            'full_log' => $full_log,
            'sitemap' => $sitemap,
            'migrations' => $migracoes,
            'installation' => $instalacao,
            'snapshot' => $instalacao['snapshot'],
            'health' => $saude,
        ];

        api_response_success($response_data, 'Projeto atualizado com sucesso');

    } catch (Exception $e) {
        // Limpar arquivos temporários em caso de erro
        if (isset($extract_dir) && is_dir($extract_dir)) {
            api_remove_directory($extract_dir);
        }

        api_response_error('Erro durante atualização do projeto: ' . $e->getMessage(), 500);
    }
}

/**
 * Limpa as migrações obsoletas do projeto no servidor antes da cópia do pacote — req-194.
 *
 * A lista completa vem do manifesto que o `deploy-project-v2.sh` põe no pacote
 * (`db/.c2f-migrations-projeto.json`); sem ele (pacote antigo ou parcial), só a cópia antiga de uma
 * migração renomeada sai. O manifesto do pacote é copiado junto e vira a referência do próximo deploy.
 *
 * @return array ['removidos' => [...], 'choques' => [...], 'log' => [...]]
 */
function api_project_migracoes_limpar(string $pacote, string $raiz): array {
    global $_GESTOR;
    $pacote = rtrim($pacote, '/\\') . DIRECTORY_SEPARATOR;
    require_once $_GESTOR['controladores-path'] . 'atualizacoes/atualizacoes-migracoes.php';
    $chegando = atualizacoes_migracoes_listar($pacote . 'db' . DIRECTORY_SEPARATOR . 'migrations');
    $completa = null;
    $manifesto = $pacote . 'db' . DIRECTORY_SEPARATOR . '.c2f-migrations-projeto.json';
    if (is_file($manifesto)) {
        $json = json_decode((string)file_get_contents($manifesto), true);
        if (is_array($json['files'] ?? null)) $completa = $json['files'];
    }
    if (!$chegando && $completa === null) return ['removidos' => [], 'choques' => [], 'log' => []];
    $r = atualizacoes_migracoes_limpar(rtrim($raiz, '/\\') . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'migrations', 'projeto', $completa, $chegando, false);
    return ['removidos' => $r['removidos'], 'choques' => $r['choques'], 'log' => atualizacoes_migracoes_log($r, 'projeto')];
}

/**
 * Regenera o `sitemap.xml` (e o `robots.txt`) a partir das páginas públicas do banco — req-188.
 *
 * Roda no contexto HTTP do deploy, em que `$_GESTOR['url-full-http']` já tem o domínio do site:
 * gerar pelo CLI gravaria URLs `https://localhost/...`. Falha no sitemap não invalida o deploy.
 *
 * @return string 'updated', 'failed' ou 'error: <mensagem>'.
 */
function api_project_sitemap_regenerar(): string {
    try {
        gestor_incluir_biblioteca('sitemap');
        if (!function_exists('sitemap_gerar_completo')) {
            return 'failed';
        }
        return sitemap_gerar_completo() ? 'updated' : 'failed';
    } catch (Throwable $e) {
        return 'error: ' . $e->getMessage();
    }
}

/**
 * Endpoint de Recuperação (Pull System) — req-058 / BATCH-058.
 *
 * Extrai um dump bruto (raw) das tabelas selecionadas do banco do gestor em arquivos
 * <PascalCase>Data.json e os devolve como pacote ZIP para download. É a via inversa do
 * deploy: o cliente extrai o ZIP localmente e o descompilador
 * (controladores/agents/arquitetura/recuperacao-dados-recursos.php) reconstrói os arquivos
 * físicos (HTML/CSS/MD) e metadados do repositório.
 *
 * Requisitos: POST autenticado por OAuth (api_authenticate(true)). O corpo pode informar as
 * tabelas via JSON {"tables":[...]} ou POST `tables` (lista separada por vírgulas); quando
 * omitido, exporta todas as tabelas registradas em db/data/schema-metadata.json.
 */
function api_project_recover() {
    global $_GESTOR;

    // Requer autenticação OAuth válida.
    api_authenticate(true);

    // ID do projeto (contexto futuro) via header X-Project-ID.
    $project_id = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    // Resolver a lista de tabelas a exportar: corpo JSON {"tables":[...]} ou POST `tables` (CSV).
    $tabelas = [];
    $body = json_decode(file_get_contents('php://input'), true);
    if (is_array($body) && isset($body['tables']) && is_array($body['tables'])) {
        $tabelas = $body['tables'];
    } elseif (isset($_POST['tables']) && is_string($_POST['tables']) && $_POST['tables'] !== '') {
        $tabelas = explode(',', $_POST['tables']);
    }
    $recover_contents = false;
    if (is_array($body) && array_key_exists('recover_contents', $body)) {
        $recover_contents = filter_var($body['recover_contents'], FILTER_VALIDATE_BOOLEAN);
    } elseif (isset($_POST['recover_contents'])) {
        $recover_contents = filter_var($_POST['recover_contents'], FILTER_VALIDATE_BOOLEAN);
    }
    $tabelas = array_values(array_filter(array_map(function ($t) {
        return preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string)$t)));
    }, $tabelas)));

    // Incluir o script de atualizações de banco apenas para reusar suas funções
    // (reverseExport/db/schemaMetadata) sem disparar o fluxo de deploy.
    if (!defined('SDD_NO_AUTORUN')) {
        define('SDD_NO_AUTORUN', true);
    }
    $script = $_GESTOR['ROOT_PATH'] . 'controladores/atualizacoes/atualizacoes-banco-de-dados.php';
    if (!file_exists($script)) {
        api_response_error('Script de atualização de banco não encontrado.', 500);
    }
    require_once $script;

    // Sem tabelas informadas: todas as registradas no contrato de sincronização.
    if (empty($tabelas)) {
        $meta = schemaMetadata();
        $tabelas = array_values(array_unique(array_merge(
            array_keys($meta['tables'] ?? []),
            api_project_schema_metadata_tables($_GESTOR['ROOT_PATH'] . 'project-schema-metadata.json')
        )));
    }
    if (empty($tabelas)) {
        api_response_error('Nenhuma tabela disponível para recuperação.', 400);
    }

    // Diretório temporário único para o dump bruto.
    $temp_base = $_GESTOR['logs-path'] . 'temp_recover_' . time() . '_' . uniqid() . '/';
    if (!is_dir($temp_base)) {
        mkdir($temp_base, 0755, true);
    }
    $zip_path = rtrim($_GESTOR['logs-path'], '/\\') . DIRECTORY_SEPARATOR
        . 'project-recover_' . time() . '_' . uniqid() . '.zip';

    try {
        // Conexão PDO com o banco do gestor (credenciais de $_BANCO via helper db()).
        $pdo = db();

        // Dump bruto das tabelas selecionadas em <PascalCase>Data.json no diretório temporário.
        reverseExport($pdo, $tabelas, $temp_base);

        // Compactar todos os *Data.json gerados.
        if (!class_exists('ZipArchive')) {
            throw new Exception('Extensão ZipArchive indisponível no servidor');
        }
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Falha ao criar arquivo ZIP de recuperação');
        }
        $jsonFiles = glob($temp_base . '*Data.json') ?: [];
        foreach ($jsonFiles as $jf) {
            $zip->addFile($jf, basename($jf));
        }
        if ($recover_contents) {
            api_zip_add_directory($zip, $_GESTOR['ROOT_PATH'] . 'contents', 'contents');
        }
        $zip->close();

        if (!is_file($zip_path)) {
            throw new Exception('Pacote ZIP de recuperação não foi gerado');
        }

        // Stream do ZIP como download. Limpa buffers e sobrescreve o Content-Type JSON do topo.
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(200);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="project-recover.zip"');
        header('Content-Length: ' . filesize($zip_path));
        readfile($zip_path);

        // Limpeza imediata em disco.
        @unlink($zip_path);
        api_remove_directory($temp_base);
        exit;

    } catch (Throwable $e) {
        if (is_file($zip_path)) { @unlink($zip_path); }
        if (is_dir($temp_base)) { api_remove_directory($temp_base); }
        api_response_error('Erro durante a recuperação do projeto: ' . $e->getMessage(), 500);
    }
}

/**
 * Lê o manifesto transitório de projeto gerado pelo compilador local e retorna tabelas válidas.
 */
function api_project_schema_metadata_tables(string $manifestPath): array {
    if (!is_file($manifestPath)) return [];
    $raw = file_get_contents($manifestPath);
    $data = json_decode((string)$raw, true);
    if (!is_array($data) || !isset($data['tabelas']) || !is_array($data['tabelas'])) return [];
    return array_values(array_filter(array_map(function ($t) {
        return preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string)$t)));
    }, array_keys($data['tabelas']))));
}

/**
 * Adiciona recursivamente uma pasta ao ZIP preservando timestamps dos arquivos.
 */
function api_zip_add_directory(ZipArchive $zip, string $sourceDir, string $zipBase): void {
    if (!is_dir($sourceDir)) return;
    $sourceDir = rtrim($sourceDir, '/\\');
    $zipBase = trim(str_replace('\\', '/', $zipBase), '/');
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $path = $item->getPathname();
        $rel = ltrim(str_replace('\\', '/', substr($path, strlen($sourceDir))), '/');
        if ($rel === '') continue;
        $zipName = $zipBase . '/' . $rel;
        if ($item->isDir()) {
            $zip->addEmptyDir($zipName);
            continue;
        }
        if ($item->isFile()) {
            $zip->addFile($path, $zipName);
            if (method_exists($zip, 'setMtimeName')) {
                $zip->setMtimeName($zipName, $item->getMTime());
            }
        }
    }
}

// =========================== Handlers de Endpoint SYSTEM

function api_handle_system() {
    global $_GESTOR;

    $sub_endpoint = isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null;

    switch ($sub_endpoint) {
        case 'update':
            api_system_update();
            break;

        case 'rollback':
            // req-201: o mesmo rollback do deploy de projeto (aceita `exec-<id>` da atualização do sistema).
            api_project_rollback();
            break;

        default:
            api_response_error('Sub-endpoint SYSTEM não encontrado: ' . $sub_endpoint, 404);
    }
}

/**
 * `action=run` (req-201 / BATCH-209): dispara a atualização completa do sistema em segundo plano, pelo mesmo
 * atualizador do CLI (trava de deploy, snapshot, dump, verificação e volta automática). Opções em
 * `opcoes` (ou no próprio corpo): tag, only_files, only_db, no_db, dry_run, backup, no_verify, no_health,
 * no_rollback, health_url, health_ip, force_all, tables, logs_retention_days, local_artifact, debug. Sem
 * `tag`, o atualizador busca a última release do GitHub. Responde 202 com o id da execução.
 */
function api_system_run(array $corpo) {
    global $_GESTOR;
    $auth = api_authenticate(true);
    require_once $_GESTOR['bibliotecas-path'] . 'atualizacoes-execucao.php';
    require_once $_GESTOR['bibliotecas-path'] . 'deploy-lock.php';
    $base = $_GESTOR['ROOT_PATH'];

    $trava = deploy_lock_read(rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'deploy.lock');
    if ($trava && !deploy_lock_expired($trava)) api_response_error('Outro deploy está em execução neste ambiente: ' . deploy_lock_describe($trava), 409);

    $opcoes = is_array($corpo['opcoes'] ?? null) ? $corpo['opcoes'] : array_diff_key($corpo, ['action' => 1]);
    $dominio = (string)($_SERVER['SERVER_NAME'] ?? '');
    if ($dominio === '') api_response_error('Domínio da instalação indefinido (SERVER_NAME).', 500);
    $a = atualizacoes_execucao_argv($opcoes, $dominio);
    if ($a['recusadas']) api_response_error('Opção inválida ou desconhecida: ' . implode(', ', $a['recusadas']), 400, ['aceitas' => array_keys(ATUALIZACOES_EXECUCAO_OPCOES)]);

    $id = atualizacoes_execucao_novo_id();
    $php = atualizacoes_execucao_php_cli((string)($_ENV['ATUALIZACOES_PHP_CLI'] ?? ''));
    $quem = 'api:' . (is_array($auth) ? (string)($auth['email'] ?? ($auth['id_usuarios'] ?? '?')) : '?');
    $r = atualizacoes_execucao_disparar($base, $id, $a['argv'], ['quem' => $quem, 'opcoes' => $opcoes, 'php' => $php], $php);
    if (!$r['ok']) api_response_error('Não foi possível disparar a atualização: ' . $r['erro'], 500);
    api_response_success(['run' => $id, 'status' => 'running', 'argv' => $a['argv'], 'php' => $php], 'Atualização disparada em segundo plano', 202);
}

/** `action=run-status` (req-201): estado de uma execução disparada por `run`. */
function api_system_run_status(string $id) {
    global $_GESTOR;
    api_authenticate(true);
    require_once $_GESTOR['bibliotecas-path'] . 'atualizacoes-execucao.php';
    if (!atualizacoes_execucao_id_valido($id)) api_response_error('Parâmetro "run" inválido.', 400);
    $pasta = atualizacoes_execucao_pasta($_GESTOR['ROOT_PATH']);
    if (!is_file($pasta . $id . '.json')) api_response_error('Execução não encontrada: ' . $id, 404);
    $meta = json_decode((string)file_get_contents($pasta . $id . '.json'), true) ?: [];
    $log = is_file($pasta . $id . '.log') ? (string)file_get_contents($pasta . $id . '.log') : '';
    $exit = is_file($pasta . $id . '.exit') ? (string)file_get_contents($pasta . $id . '.exit') : null;
    $estado = atualizacoes_execucao_estado($log, $exit);
    api_response_success(['run' => $id, 'iniciado_em' => $meta['iniciado_em'] ?? null, 'quem' => $meta['quem'] ?? null, 'opcoes' => $meta['opcoes'] ?? []] + $estado, 'Execução ' . $id . ': ' . $estado['status']);
}

/** `action=runs` (req-201): últimas execuções disparadas pela API, com o estado. */
function api_system_runs() {
    global $_GESTOR;
    api_authenticate(true);
    require_once $_GESTOR['bibliotecas-path'] . 'atualizacoes-execucao.php';
    $pasta = atualizacoes_execucao_pasta($_GESTOR['ROOT_PATH']);
    $lista = [];
    $arquivos = glob($pasta . 'run-*.json') ?: [];
    rsort($arquivos);
    foreach (array_slice($arquivos, 0, 20) as $f) {
        $id = basename($f, '.json');
        $exit = is_file($pasta . $id . '.exit') ? (string)file_get_contents($pasta . $id . '.exit') : null;
        $e = atualizacoes_execucao_estado(is_file($pasta . $id . '.log') ? (string)file_get_contents($pasta . $id . '.log') : '', $exit);
        $meta = json_decode((string)file_get_contents($f), true) ?: [];
        $lista[] = ['run' => $id, 'iniciado_em' => $meta['iniciado_em'] ?? null, 'status' => $e['status'], 'codigo' => $e['codigo'], 'snapshot' => $e['snapshot']];
    }
    api_response_success(['total' => count($lista), 'runs' => $lista], count($lista) . ' execução(ões)');
}

function api_system_update() {
    global $_GESTOR;

    // Requer autenticação
    api_authenticate(true);

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    // Obter ação do POST ou da query string (ou do corpo JSON, nas ações da req-201)
    $corpo = api_corpo_requisicao();
    $action = $_POST['action'] ?? $_REQUEST['action'] ?? ($corpo['action'] ?? null);
    if (!$action) {
        api_response_error('Parâmetro "action" é obrigatório. Ações válidas: run, run-status, runs, start, deploy, db, finalize, status, cancel', 400);
    }

    // req-201 / BATCH-209: atualização completa em segundo plano (o atualizador do CLI).
    if ($action === 'run') api_system_run($corpo);
    if ($action === 'run-status') api_system_run_status((string)($corpo['run'] ?? ''));
    if ($action === 'runs') api_system_runs();

    $valid_actions = ['start', 'deploy', 'db', 'finalize', 'status', 'cancel'];
    if (!in_array($action, $valid_actions)) {
        api_response_error('Ação inválida: ' . $action . '. Válidas: ' . implode(', ', $valid_actions), 400);
    }

    // Construir parâmetros para o sistema de atualização
    $params = ['action' => $action];

    // Para ação start, repassar configurações adicionais
    if ($action === 'start') {
        $param_keys = [
            'domain', 'tag', 'only_files', 'only_db', 'dry_run', 'local',
            'debug', 'no_db', 'force_all', 'log_diff', 'backup', 'no_verify',
            'download_only', 'skip_download', 'tables', 'clean_temp', 'logs_retention_days',
            'no_health', 'no_rollback', 'health_url', 'health_ip', // req-201
        ];
        foreach ($param_keys as $key) {
            $val = $_POST[$key] ?? $_REQUEST[$key] ?? null;
            if ($val !== null) {
                $params[$key] = $val;
            }
        }
        // Domínio padrão
        if (empty($params['domain'])) {
            $params['domain'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        }
    }

    // Para ações que operam em sessão, exigir sid
    if (in_array($action, ['deploy', 'db', 'finalize', 'status', 'cancel'])) {
        $sid = $_POST['sid'] ?? $_REQUEST['sid'] ?? null;
        if (!$sid) {
            api_response_error('Parâmetro "sid" (session ID) é obrigatório para a ação: ' . $action, 400);
        }
        $params['sid'] = $sid;
    }

    // Executar atualização via include do script (mesma abordagem de admin-atualizacoes)
    $result = api_call_system_update($params);

    if (isset($result['error'])) {
        $http_code = 500;
        // Erros de sessão são 400 (bad request)
        if (strpos($result['error'], 'Sessão') !== false || strpos($result['error'], 'inválida') !== false) {
            $http_code = 400;
        }
        api_response_error($result['error'], $http_code, $result);
    }

    api_response_success($result, 'Ação "' . $action . '" executada com sucesso');
}

/**
 * Chama o script atualizacoes-sistema.php simulando uma requisição web.
 * Mesma técnica utilizada por admin_atualizacoes_call_system().
 */
function api_call_system_update(array $params): array {
    global $_GESTOR;

    $script = $_GESTOR['ROOT_PATH'] . 'controladores/atualizacoes/atualizacoes-sistema.php';

    if (!is_file($script)) {
        return ['error' => 'Script de atualização não encontrado: ' . $script];
    }

    // Construir query string a partir dos parâmetros
    $query = http_build_query($params);

    // Salvar estado atual de $_GET e $_REQUEST
    $saved_get = $_GET;
    $saved_request = $_REQUEST;

    // Simular requisição web (mesma abordagem de admin-atualizacoes)
    $_GET = $_REQUEST = [];
    parse_str($query, $_GET);
    $_REQUEST = $_GET;

    // Incluir script e capturar saída JSON
    ob_start();
    try {
        include $script;
    } catch (Throwable $e) {
        ob_end_clean();
        $_GET = $saved_get;
        $_REQUEST = $saved_request;
        return ['error' => 'Exceção durante atualização: ' . $e->getMessage()];
    }
    $raw = ob_get_clean();

    // Restaurar estado
    $_GET = $saved_get;
    $_REQUEST = $saved_request;

    // Decodificar resposta JSON
    $json = json_decode($raw, true);
    if ($json === null) {
        return ['error' => 'Resposta inválida do sistema de atualização', 'raw' => substr($raw, 0, 2000)];
    }

    return $json;
}

// =========================== Funções Auxiliares para Manipulação de Arquivos

/**
 * Eleva o `memory_limit` até o mínimo pedido; nunca reduz (e `-1`, ilimitado, fica como está).
 *
 * @param string $minimo Valor no formato do php.ini (ex.: '1024M').
 * @return string O limite em vigor depois da chamada.
 */
function api_memoria_minima($minimo) {
    $bytes = function ($valor) {
        $valor = trim((string)$valor);
        if ($valor === '' || $valor === '-1') return -1;
        $numero = (int)$valor;
        switch (strtoupper(substr($valor, -1))) {
            case 'G': return $numero * 1024 * 1024 * 1024;
            case 'M': return $numero * 1024 * 1024;
            case 'K': return $numero * 1024;
        }
        return $numero;
    };

    $atual = (string)ini_get('memory_limit');
    $atualBytes = $bytes($atual);
    if ($atualBytes !== -1 && $atualBytes < $bytes($minimo)) {
        @ini_set('memory_limit', $minimo);
    }
    return (string)ini_get('memory_limit');
}

function api_executar_atualizacao_banco($project_path, $project_id = null, $full_log = false) {
    global $_GESTOR, $_BANCO;

    // Caminho para o script de atualização de banco
    $script = $_GESTOR['ROOT_PATH'] . 'controladores/atualizacoes/atualizacoes-banco-de-dados.php';

    if (!file_exists($script)) {
        throw new Exception('Script de atualização de banco não encontrado: ' . $script);
    }

    // req-191: a sincronização carrega cada tabela inteira (`SELECT *` + `fetchAll`). Pelo CLI o limite
    // de memória é folgado; aqui roda na requisição web (128 MB típico) e `paginas`, com HTML e CSS
    // compilado de centenas de docs, estourou o limite (HTTP 500 no deploy do conn2flow-site).
    api_memoria_minima('1024M');

    // Configurar opções CLI para execução inline
    $cli = [
        'env-dir' => $_SERVER['SERVER_NAME'] ?? 'localhost', // domínio padrão
        'db' => [
            'host' => $_BANCO['host'],
            'name' => $_BANCO['nome'],
            'user' => $_BANCO['usuario'],
            'pass' => $_BANCO['senha'] ?? '',
        ],
        'debug' => (bool)$full_log,
        'force-all' => false,
        'tables' => null,
        'log-diff' => (bool)$full_log,
        'dry-run' => false,
        'project' => $project_id ?? null,
    ];

    // Definir opções globais para o script
    $GLOBALS['CLI_OPTS'] = $cli;

    // Capturar os logs do banco por referência durante a execução inline (sem processo externo).
    $dbLogs = [];
    $loggerAnterior = $GLOBALS['EXTERNAL_LOGGER'] ?? null;
    $GLOBALS['EXTERNAL_LOGGER'] = &$dbLogs;
    $erro = null;
    try {
        require $script;
    } catch (Throwable $e) {
        $erro = $e;
    }
    // Restaura o logger externo anterior (preserva o array capturado em $dbLogs).
    unset($GLOBALS['EXTERNAL_LOGGER']);
    if ($loggerAnterior !== null) $GLOBALS['EXTERNAL_LOGGER'] = $loggerAnterior;

    if ($erro !== null) {
        throw new Exception('Falha na atualização de banco de dados: ' . $erro->getMessage());
    }

    return api_filtrar_db_logs($dbLogs, (bool)$full_log);
}

/**
 * Filtra os logs do banco para a resposta da API.
 * full_log=true  => log completo de debug.
 * full_log=false => versão resumida (relatório final + linhas de warning/erro/rollback).
 */
function api_filtrar_db_logs(array $dbLogs, bool $full_log): array {
    if ($full_log) return array_values($dbLogs);
    $resumo = [];
    foreach ($dbLogs as $line) {
        $s = (string)$line;
        $u = strtoupper($s);
        if (strpos($u, 'WARN') !== false || strpos($u, 'ERRO') !== false || strpos($u, 'ERROR') !== false
            || strpos($u, 'ROLLBACK') !== false || strpos($u, 'TOTAL') !== false
            || strpos($s, '📦') !== false || strpos($s, '📝') !== false || strpos($s, 'Σ') !== false) {
            $resumo[] = $line;
        }
    }
    return array_values($resumo);
}

/**
 * Aplica os arquivos do pacote pela camada `projeto` do manifesto de instalação — req-198 (BL-028 A/B.1).
 *
 * O pacote traz `.c2f-manifest-projeto.json` com TODOS os arquivos do projeto (também no gitDeploy, que
 * só manda o que mudou): com ele, o que o projeto deixou de entregar sai do servidor ou devolve a
 * original do core. Sem ele (pacote antigo), só acrescenta e atualiza. As pastas fora do manifesto
 * (`contents/` e afins) continuam sendo copiadas como antes.
 *
 * @return array Resumo para a resposta: versao, primeira, escritos, preservados, retirados,
 *               restaurados, choques (sem o diff), lista_completa.
 */
function api_project_aplicar_arquivos(string $pacote, string $raiz, string $projectId): array {
    global $_GESTOR;
    require_once $_GESTOR['bibliotecas-path'] . 'instalacao-manifesto.php';
    $pacote = rtrim($pacote, '/\\') . DIRECTORY_SEPARATOR;
    $lista = null;
    $manifestoPacote = $pacote . '.c2f-manifest-projeto.json';
    if (is_file($manifestoPacote)) {
        $d = json_decode((string)file_get_contents($manifestoPacote), true);
        if (is_array($d) && is_array($d['arquivos'] ?? null)) $lista = $d;
        @unlink($manifestoPacote);
    }
    $versao = (string)($lista['versao'] ?? '');
    if ($versao === '') $versao = date('Ymd-His');

    $plano = instalacao_planejar($raiz, 'projeto', instalacao_mapa($pacote), false, null, $lista['arquivos'] ?? null);

    // req-198 / BATCH-204: snapshot seletivo antes de aplicar (o que vai ser sobrescrito ou removido,
    // a lista dos novos e os manifestos), como na atualização do sistema. Ficam os 5 mais recentes.
    $snapshot = 'api-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2));
    $snapDir = instalacao_snapshot_dir($raiz, $snapshot);
    instalacao_snapshot_podar(dirname(rtrim($snapDir, '/\\')), 4);
    $snap = instalacao_snapshot_criar($raiz, $plano, $snapDir, ['versao' => $versao, 'origem' => 'api-project-update', 'projeto' => $projectId]);

    $r = instalacao_aplicar($raiz, $pacote, 'projeto', $plano, $versao, 'copiar');

    // Pastas que o manifesto não cobre seguem a cópia de sempre (menos `installation/`, que é do servidor).
    foreach (INSTALACAO_PASTAS_FORA as $pasta) {
        if ($pasta === 'installation' || !is_dir($pacote . $pasta)) continue;
        api_copy_directory($pacote . $pasta, rtrim($raiz, '/\\') . DIRECTORY_SEPARATOR . $pasta);
    }

    instalacao_choques_registrar($raiz, 'api-project-update', 'projeto', $versao, $projectId !== '' ? $projectId . '@' . date('c') : null, $r['choques']);
    return [
        'snapshot' => $snapshot,
        'snapshot_itens' => $snap,
        'versao' => $versao,
        'primeira' => $plano['primeira'],
        'lista_completa' => $lista !== null,
        'escritos' => $r['escritos'],
        'preservados' => $r['preservados'],
        'retirados' => $r['retirados'],
        'restaurados' => $r['restaurados'],
        'choques' => array_map(function ($c) {
            return ['caminho' => $c['caminho'], 'motivo' => $c['motivo'], 'camada_dona' => $c['camada_dona'], 'copia' => $c['copia']];
        }, $r['choques']),
    ];
}

/** Campo POST booleano (`1`, `true`, `on`…). */
function api_post_bool(string $campo): bool {
    return isset($_POST[$campo]) && filter_var($_POST[$campo], FILTER_VALIDATE_BOOLEAN);
}

/**
 * Opções da verificação pós-deploy (req-198 / BATCH-204): URL da raiz do site pelo host da própria
 * requisição, ou `health_url` / `health_ip` do POST, ou `ATUALIZACOES_SAUDE_URL` / `ATUALIZACOES_SAUDE_IP`.
 */
function api_project_saude_opcoes(): array {
    $url = (string)($_POST['health_url'] ?? ($_ENV['ATUALIZACOES_SAUDE_URL'] ?? ''));
    if ($url === '' && !empty($_SERVER['HTTP_HOST'])) {
        $raiz = '/' . trim((string)($_ENV['URL_RAIZ'] ?? '/'), '/');
        $url = 'https://' . preg_replace('/[^A-Za-z0-9.:\[\]-]/', '', (string)$_SERVER['HTTP_HOST']) . ($raiz === '/' ? '/' : $raiz . '/');
    }
    return array_filter(['url' => $url, 'ip' => (string)($_POST['health_ip'] ?? ($_ENV['ATUALIZACOES_SAUDE_IP'] ?? ''))]);
}

/**
 * `POST /_api/project/rollback` (req-198 / BATCH-204): volta uma entrega pelo snapshot. Corpo JSON
 * `{"snapshot":"api-…|exec-…","com_banco":false}` (ou os mesmos campos no POST). Arquivos sempre; banco só
 * com `com_banco`. Usa a trava de deploy do ambiente.
 */
function api_project_rollback() {
    global $_GESTOR;
    api_authenticate(true);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response_error('Método não permitido. Use POST.', 405);
    $body = json_decode((string)file_get_contents('php://input'), true);
    $body = is_array($body) ? $body : $_POST;
    $id = (string)($body['snapshot'] ?? '');
    $comBanco = filter_var($body['com_banco'] ?? false, FILTER_VALIDATE_BOOLEAN);

    require_once $_GESTOR['bibliotecas-path'] . 'instalacao-manifesto.php';
    require_once $_GESTOR['bibliotecas-path'] . 'deploy-lock.php';
    $raiz = $_GESTOR['ROOT_PATH'];
    $dir = instalacao_snapshot_dir($raiz, $id);
    if (!$dir || !is_file($dir . 'snapshot.json')) api_response_error('Snapshot não encontrado: ' . $id, 404);

    $trava_arquivo = rtrim($raiz, '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'deploy.lock';
    $trava = deploy_lock_acquire($trava_arquivo, ['owner' => 'api-project-rollback', 'detail' => basename(rtrim($dir, '/\\'))]);
    if (!$trava['ok']) api_response_error('Outro deploy está em execução neste ambiente: ' . deploy_lock_describe($trava['holder'] ?? null), 409);
    register_shutdown_function(function () use ($trava_arquivo, $trava) { deploy_lock_release($trava_arquivo, $trava['token']); });

    $r = instalacao_snapshot_restaurar($raiz, $dir);
    if (isset($r['erro'])) api_response_error('Rollback: ' . $r['erro'], 500);
    $banco = null;
    if ($comBanco) {
        $banco = instalacao_banco_restaurar($dir . 'banco.sql.gz', (array)($GLOBALS['_BANCO'] ?? []));
        if (!$banco['ok']) api_response_error('Arquivos restaurados, mas o banco falhou: ' . $banco['erro'], 500, ['rollback' => $r]);
    }
    api_response_success([
        'snapshot' => basename(rtrim($dir, '/\\')),
        'restaurados' => $r['restaurados'],
        'removidos_novos' => $r['removidos_novos'],
        'falhas' => $r['falhas'],
        'banco' => $comBanco ? 'restaurado' : 'não tocado',
    ], 'Rollback concluído');
}

/** Corpo JSON da requisição, ou o POST/GET quando não é JSON. */
function api_corpo_requisicao(): array {
    $body = json_decode((string)file_get_contents('php://input'), true);
    return is_array($body) ? $body : array_merge($_GET, $_POST);
}

/**
 * `/_api/project/conflicts` (req-199 / BATCH-205): choques das entregas desta instalação.
 * - sem `id`: lista (`todos=1` inclui os resolvidos; `limite`, até 500);
 * - com `id`: detalhe com as duas versões (`no_ar`, `nova`; binário em base64) e o diff.
 * Aceita GET ou POST (JSON ou formulário).
 */
function api_project_conflicts() {
    global $_GESTOR;
    api_authenticate(true);
    $corpo = api_corpo_requisicao();
    require_once $_GESTOR['bibliotecas-path'] . 'atualizacoes-choques.php';
    if (!atualizacoes_choques_disponivel()) api_response_error('Registro de choques indisponível (migração da req-198 não aplicada).', 409);
    if (!empty($corpo['id'])) {
        $d = atualizacoes_choques_detalhe($_GESTOR['ROOT_PATH'], (int)$corpo['id']);
        if (!$d) api_response_error('Choque não encontrado: ' . (int)$corpo['id'], 404);
        api_response_success($d, 'Choque ' . (int)$corpo['id']);
    }
    $todos = filter_var($corpo['todos'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $lista = atualizacoes_choques_listar(!$todos, (int)($corpo['limite'] ?? 100));
    api_response_success(['total' => count($lista), 'choques' => $lista], count($lista) . ' choque(s)');
}

/**
 * `POST /_api/project/resolve` (req-199 / BATCH-205): decisão sobre um choque.
 * Corpo: `{"id":12,"acao":"sobrescrever|manter|mesclar","conteudo":"…","codificacao":"texto|base64"}`
 * (`conteudo` só no `mesclar`). Roda sob a trava de deploy. Quem decidiu fica como `api:<e-mail do token>`.
 */
function api_project_resolve() {
    global $_GESTOR;
    $auth = api_authenticate(true);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response_error('Método não permitido. Use POST.', 405);
    $corpo = api_corpo_requisicao();
    $id = (int)($corpo['id'] ?? 0);
    $acao = (string)($corpo['acao'] ?? '');
    $conteudo = isset($corpo['conteudo']) ? (string)$corpo['conteudo'] : null;
    if ($conteudo !== null && ($corpo['codificacao'] ?? 'texto') === 'base64') {
        $conteudo = base64_decode($conteudo, true);
        if ($conteudo === false) api_response_error('conteudo em base64 inválido', 400);
    }
    if ($id <= 0 || $acao === '') api_response_error('Informe id e acao (sobrescrever, manter ou mesclar).', 400);

    require_once $_GESTOR['bibliotecas-path'] . 'atualizacoes-choques.php';
    require_once $_GESTOR['bibliotecas-path'] . 'deploy-lock.php';
    if (!atualizacoes_choques_disponivel()) api_response_error('Registro de choques indisponível (migração da req-198 não aplicada).', 409);
    $trava_arquivo = rtrim($_GESTOR['ROOT_PATH'], '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'deploy.lock';
    $trava = deploy_lock_acquire($trava_arquivo, ['owner' => 'api-project-resolve', 'detail' => 'choque ' . $id]);
    if (!$trava['ok']) api_response_error('Outro deploy está em execução neste ambiente: ' . deploy_lock_describe($trava['holder'] ?? null), 409);
    register_shutdown_function(function () use ($trava_arquivo, $trava) { deploy_lock_release($trava_arquivo, $trava['token']); });

    $quem = 'api:' . (is_array($auth) ? (string)($auth['email'] ?? ($auth['id_usuarios'] ?? '?')) : '?');
    $r = atualizacoes_choques_resolver($_GESTOR['ROOT_PATH'], $id, $acao, $conteudo, $quem);
    if (!$r['ok']) api_response_error('Resolução recusada: ' . $r['erro'], $r['erro'] === 'choque não encontrado' ? 404 : 422);
    api_response_success(['id' => $id, 'acao' => $r['acao'], 'resolvidos' => $r['resolvidos']], 'Choque resolvido');
}

/** Grava na tabela `atualizacoes_choques` os choques pendentes da instalação (req-198). */
function api_project_choques_gravar(string $raiz): int {
    if (!function_exists('instalacao_choques_gravar_pendentes')) return 0;
    $existe = banco_query("SHOW TABLES LIKE 'atualizacoes_choques'");
    if (!$existe || !banco_num_rows($existe)) return 0;
    return instalacao_choques_gravar_pendentes($raiz, function (array $linha) {
        // O mesmo choque ainda pendente não vira outra linha.
        $ja = banco_query('SELECT 1 FROM atualizacoes_choques WHERE ' . instalacao_choque_pendente_filtro($linha, 'banco_escape_field') . ' LIMIT 1');
        if ($ja && banco_num_rows($ja)) return true;
        $campos = [];
        foreach ($linha as $k => $v) $campos[] = [$k, $v === null ? 'NULL' : banco_escape_field((string)$v), $v === null];
        banco_insert_name($campos, 'atualizacoes_choques');
        return true;
    });
}

function api_copy_directory($source, $destination) {
    if (!is_dir($source)) {
        return false;
    }

    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
        $destinationPath = $destination . DIRECTORY_SEPARATOR . $file;

        if (is_dir($sourcePath)) {
            api_copy_directory($sourcePath, $destinationPath);
        } else {
            copy($sourcePath, $destinationPath);
        }
    }
    closedir($dir);
    return true;
}

function api_remove_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            api_remove_directory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

// =========================== Handler OAuth Refresh

function api_oauth_refresh() {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    $data = api_get_request_body();

    // Validar refresh_token
    if (!isset($data['refresh_token']) || empty($data['refresh_token'])) {
        api_response_error('Campo "refresh_token" é obrigatório', 400);
    }

    // Incluir biblioteca OAuth2
    gestor_incluir_biblioteca('oauth2');

    // Tentar renovar tokens
    $novos_tokens = oauth2_renovar_token(Array(
        'refresh_token' => $data['refresh_token']
    ));

    if (!$novos_tokens) {
        api_response_error('Refresh token inválido ou expirado', 401);
    }

    // Retornar novos tokens
    api_response_success([
        'access_token' => $novos_tokens['access_token'],
        'token_type' => $novos_tokens['token_type'],
        'expires_in' => $novos_tokens['expires_in'],
        'refresh_token' => $novos_tokens['refresh_token'],
        'scope' => $novos_tokens['scope']
    ], 'Tokens renovados com sucesso');
}

// =========================== Hook de Módulos para a API

/**
 * Carrega o arquivo de hook de um módulo para o contexto da API.
 *
 * Lê o JSON do módulo, encontra o arquivo configurado em hooks.api,
 * inclui o arquivo e retorna o nome da função de callback esperada.
 *
 * Convenção de nome de função: {modulo_id_underscores}_api
 * Ex: módulo "host-manager" → função "host_manager_api"
 *
 * @param string      $modulo_id ID do módulo (ex: 'host-manager').
 * @param string|null $plugin_id ID do plugin, ou null para módulo padrão.
 *
 * @return string|null Nome da função do hook ou null se não encontrado.
 */
function api_carregar_hook($modulo_id, $plugin_id = null) {
    global $_GESTOR;

    // Resolver diretório do módulo
    if (!empty($plugin_id)) {
        $modulo_dir = $_GESTOR['plugins-path'] . $plugin_id . '/modules/' . $modulo_id . '/';
    } else {
        $modulo_dir = $_GESTOR['modulos-path'] . $modulo_id . '/';
    }

    if (!is_dir($modulo_dir)) {
        return null;
    }

    $json_path = $modulo_dir . $modulo_id . '.json';

    if (!file_exists($json_path)) {
        return null;
    }

    $modulo_config = json_decode(file_get_contents($json_path), true);

    if (!$modulo_config || json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    $hook_file = $modulo_config['hooks']['api'] ?? null;

    if (empty($hook_file)) {
        return null;
    }

    $hook_path = $modulo_dir . $hook_file;

    if (!file_exists($hook_path)) {
        return null;
    }

    include_once($hook_path);

    // Convenção: {modulo_id_com_underscores}_api
    $funcao_id = str_replace('-', '_', $modulo_id);
    $funcao    = $funcao_id . '_api';

    // Fallback: module IDs starting with digit generate invalid PHP function names
    // Try with underscore prefix: 3d_catalog_api → _3d_catalog_api
    if (!function_exists($funcao) && preg_match('/^\d/', $funcao_id)) {
        $funcao_alt = '_' . $funcao;
        if (function_exists($funcao_alt)) {
            $funcao = $funcao_alt;
        }
    }

    return function_exists($funcao) ? $funcao : null;
}

/**
 * Dispara o hook 'api' para módulos registrados.
 *
 * Segue o mesmo padrão de plataforma_gateways_disparar_hook():
 * - Busca módulos com hooks IS NOT NULL no banco
 * - Quando $modulo_id_alvo é especificado, despacha apenas para ele
 * - Caso contrário, dispara broadcast para todos os módulos com hook 'api'
 *
 * A função de hook do módulo recebe:
 *   ['modulo_id' => string, 'action' => string, 'data' => array, 'method' => string]
 *
 * @param string      $action         Ação/sub-endpoint (ex: 'list-accounts', 'create-account').
 * @param array       $data           Dados da requisição (body + query params).
 * @param string|null $modulo_id_alvo ID do módulo alvo (null = broadcast).
 *
 * @return array|null Resultado do processamento ou null.
 */
function api_disparar_hook($action, $data = [], $modulo_id_alvo = null) {
    global $_GESTOR;

    $resultado = null;

    if (!empty($modulo_id_alvo)) {
        $modulos = banco_select([
            'tabela' => 'modulos',
            'campos' => ['id', 'plugin'],
            'extra'  => "WHERE id = '" . banco_escape_field($modulo_id_alvo) . "' AND hooks IS NOT NULL AND status != 'D'",
        ]);
    } else {
        $modulos = banco_select([
            'tabela' => 'modulos',
            'campos' => ['id', 'plugin'],
            'extra'  => "WHERE hooks IS NOT NULL AND status != 'D'",
        ]);
    }

    if (!$modulos) {
        return null;
    }

    foreach ($modulos as $modulo) {
        $modulo_id = $modulo['id'];

        $funcao = api_carregar_hook($modulo_id, $modulo['plugin'] ?? null);

        if (!$funcao) {
            continue;
        }

        try {
            $resultado_modulo = $funcao([
                'modulo_id' => $modulo_id,
                'action'    => $action,
                'data'      => $data,
                'method'    => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            ]);

            if ($resultado_modulo !== null) {
                $resultado = $resultado_modulo;
            }

            // Se o módulo marcou como processado, parar loop
            if (isset($resultado_modulo['processed']) && $resultado_modulo['processed']) {
                break;
            }
        } catch (Exception $e) {
            error_log('API Hook Error [' . $modulo_id . ']: ' . $e->getMessage());
        }
    }

    return $resultado;
}

/**
 * Trata requisições direcionadas a módulos via /_api/{modulo-id}/{action}.
 *
 * @param string $modulo_id ID do módulo da URL.
 * @param string $action    Ação/sub-endpoint da URL.
 *
 * @return void
 */
function api_handle_modulo($modulo_id, $action) {
    // Sanitizar modulo_id
    $modulo_id = preg_replace('/[^a-z0-9\-_]/', '', strtolower($modulo_id));

    if (empty($modulo_id)) {
        api_response_error('Módulo inválido', 400);
    }

    // Requer autenticação
    api_authenticate(true);

    $data = array_merge(
        $_GET ?? [],
        api_get_request_body()
    );

    $resultado = api_disparar_hook($action ?? '', $data, $modulo_id);

    if ($resultado === null) {
        api_response_error('Módulo não encontrado ou sem suporte ao hook API: ' . $modulo_id, 404);
    }

    if (isset($resultado['erro']) && $resultado['erro']) {
        $code = $resultado['code'] ?? 500;
        api_response_error($resultado['mensagem'] ?? 'Erro no módulo', $code, $resultado['detalhes'] ?? null);
    }

    api_response_success(
        $resultado['dados'] ?? $resultado,
        $resultado['mensagem'] ?? 'OK',
        $resultado['code'] ?? 200
    );
}

// =========================== Canal de Módulos Distribuídos (req-005)

/**
 * Despacha as requisições do canal de módulos distribuídos.
 *
 * Rota: api[/v1]/modulo-distribuido/{slug}/{acao}. A ação determina o lado:
 *  - 'db' / 'ping'          => lado DISTRIBUÍDO (executa a operação no banco local).
 *  - 'signin' / 'refresh'   => lado CENTRAL (autenticação/ativação, devolve tokens).
 *
 * A autenticação do canal é feita por assinatura HMAC (verificada em cada handler),
 * dispensando o OAuth para as operações máquina-a-máquina entre central e distribuído.
 *
 * @return void
 */
function api_handle_modulo_distribuido() {
    global $_GESTOR;

    gestor_incluir_biblioteca('modulo-distribuido');

    $rota = modulo_distribuido_parse_rota($_GESTOR['caminho']);
    if ($rota === null) {
        api_response_error('Rota de módulo distribuído inválida. Use modulo-distribuido/{slug}/{acao}.', 400);
    }

    $acao = $rota['acao'];

    // Ações de autenticação/ativação e o middleware de permissão são atendidos pelo central.
    if (in_array($acao, ['signin', 'refresh', 'ativar', 'permissao'], true)) {
        require_once $_GESTOR['ROOT_PATH'] . 'controladores/api/api-module-central.php';
        api_module_central_handle($rota);
        return;
    }

    // Demais ações (db, ping) são atendidas pelo lado distribuído (executa no banco local).
    require_once $_GESTOR['ROOT_PATH'] . 'controladores/api/api-module-distributed.php';
    api_module_distributed_handle($rota);
}

// =========================== Roteamento da API

function api_route_request() {
    global $_GESTOR;

    // Verificar se há caminho suficiente
    if (!isset($_GESTOR['caminho'][1])) {
        api_response_error('Endpoint não especificado', 400);
    }

    $endpoint = $_GESTOR['caminho'][1];
    $method = $_SERVER['REQUEST_METHOD'];

    // Rate limiting (req-108: falha de infraestrutura não pode se disfarçar de limite excedido)
    $limite = api_rate_limit_check($endpoint);
    if ($limite === false) {
        api_response_error('Rate limit excedido. Tente novamente mais tarde.', 429);
    }
    if ($limite === null) {
        api_response_error('Não foi possível avaliar o limite de requisições. Consulte o log do servidor.', 503);
    }

    // Roteamento baseado no endpoint
    switch ($endpoint) {
        case 'auth':
            // Autenticação mobile (BATCH-008 conn2flow-app): login, logout, me, modules.
            require_once $_GESTOR['ROOT_PATH'] . 'controladores/api/api-auth.php';
            api_auth_handle(isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null);
            break;

        case 'oauth':
            // Verificar sub-endpoint OAuth
            $sub_endpoint = isset($_GESTOR['caminho'][2]) ? $_GESTOR['caminho'][2] : null;

            switch ($sub_endpoint) {
                case 'refresh':
                    api_oauth_refresh();
                    break;
                default:
                    // Redirecionar para o endpoint OAuth existente
                    header('Location: ' . $_GESTOR['url-raiz'] . 'oauth-authenticate/');
                    exit;
            }
            break;

        case 'status':
            api_response_success(['status' => 'API operacional', 'version' => '1.0.0']);
            break;

        case 'health':
            api_response_success(['status' => 'healthy', 'timestamp' => time()]);
            break;

        case 'project':
            api_handle_project();
            break;

        case 'system':
            api_handle_system();
            break;

        case 'v1':
        case 'modulo-distribuido':
            // Canal de módulos distribuídos (req-005): api[/v1]/modulo-distribuido/{slug}/{acao}.
            api_handle_modulo_distribuido();
            break;

        default:
            // Tentar despachar para módulo via hook 'api'
            // Rota: /_api/{modulo-id}[/{action}[/{sub-action}...]]
            $action = isset($_GESTOR['caminho'][2]) ? implode('/', array_slice($_GESTOR['caminho'], 2)) : '';
            api_handle_modulo($endpoint, $action);
    }
}

// =========================== Inicialização da API

if(!defined('SDD_NO_AUTORUN')){
    try {
        api_route_request();
    } catch (Exception $e) {
        error_log('API Error: ' . $e->getMessage());
        api_response_error('Erro interno do servidor', 500);
    }
}

?>
