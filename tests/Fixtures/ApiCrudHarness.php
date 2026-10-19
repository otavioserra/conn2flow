<?php
/**
 * REQ-283 — arnês do motor de CRUD da API, executado num processo à parte.
 *
 * Só a camada de E/S do banco é simulada (query, linhas, escape, último id) sobre um SQLite em
 * memória; banco_select, banco_insert_name, banco_update, banco_delete e banco_identificador são
 * extraídos do próprio banco.php, então o SQL montado pelo motor roda de verdade.
 *
 * Variáveis de ambiente:
 *  - API_CRUD_PLUGINS: pasta de plugins de teste (módulo com hooks e exclusão definitiva).
 *  - API_CRUD_NEGADOS: JSON com operações que o perfil não tem ('*' = sem acesso ao módulo).
 *  - API_CRUD_ACESSO_REAL: 1 usa o gestor_acesso() real do gestor.php sobre as tabelas de perfil.
 *
 * Globais de injeção (definidas pelo cenário):
 *  - $GLOBALS['falhar_escritas']: INSERT/UPDATE/DELETE devolvem false, como o banco_query real em erro.
 *  - $GLOBALS['antes_da_escrita']: callable executado uma vez imediatamente antes da próxima escrita
 *    (escritor concorrente entre o pré-teste do motor e o UPDATE condicional).
 */

$raiz = dirname(__DIR__, 2) . '/gestor/';

$_GESTOR = [
    'linguagem-codigo' => 'pt-br',
    'modulos-path' => $raiz . 'modulos/',
    'plugins-path' => rtrim((string)getenv('API_CRUD_PLUGINS'), '/\\') . '/',
];
$GLOBALS['avisos'] = [];
$GLOBALS['sqls'] = [];
$GLOBALS['erros_sql'] = [];
$GLOBALS['acessos'] = [];
$GLOBALS['linhas_afetadas'] = null;
$GLOBALS['negados'] = json_decode(getenv('API_CRUD_NEGADOS') ?: '[]', true);

// error_log do motor vai para arquivo, para não misturar com a saída JSON do cenário.
$GLOBALS['log_arquivo'] = tempnam(sys_get_temp_dir(), 'req283-log-');
ini_set('error_log', $GLOBALS['log_arquivo']);
register_shutdown_function(function () { @unlink($GLOBALS['log_arquivo']); });
function logs(): array {
    return array_values(array_filter(array_map('trim', file($GLOBALS['log_arquivo']) ?: [])));
}

set_error_handler(function ($nivel, $mensagem, $arquivo, $linha) {
    $GLOBALS['avisos'][] = $mensagem . ' @ ' . basename($arquivo) . ':' . $linha;
    return true;
});

$GLOBALS['pdo'] = new PDO('sqlite::memory:');
$GLOBALS['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$GLOBALS['pdo']->exec("CREATE TABLE modulos (id_modulos INTEGER PRIMARY KEY AUTOINCREMENT, id TEXT, plugin TEXT, status TEXT, language TEXT)");
$GLOBALS['pdo']->exec("CREATE TABLE categorias (id_categorias INTEGER PRIMARY KEY AUTOINCREMENT, id_usuarios INTEGER DEFAULT 1, id_modulos INTEGER, id_categorias_pai INTEGER, nome TEXT, id TEXT, plugin TEXT, status TEXT, versao INTEGER, data_criacao TEXT, data_modificacao TEXT)");
$GLOBALS['pdo']->exec("CREATE TABLE itens_teste (id_itens_teste INTEGER PRIMARY KEY AUTOINCREMENT, id TEXT, titulo TEXT, segredo TEXT, language TEXT, status TEXT, versao INTEGER, data_criacao TEXT, data_modificacao TEXT)");
foreach ([['admin-categorias', null, 'A'], ['admin-layouts', null, 'A'], ['modulo-inativo', null, 'I'], ['itens-teste', 'plug-teste', 'A']] as $m) {
    foreach (['pt-br', 'en'] as $lang) {
        $GLOBALS['pdo']->exec("INSERT INTO modulos (id, plugin, status, language) VALUES ('{$m[0]}', " . ($m[1] ? "'{$m[1]}'" : 'NULL') . ", '{$m[2]}', '{$lang}')");
    }
}

final class ApiCrudResultado {
    public array $linhas;
    public array $colunas = [];
    public int $pos = 0;
    public function __construct(array $linhas) { $this->linhas = $linhas; }
}

function banco_escape_field($v) { return str_replace("'", "''", (string)$v); }
function banco_query($sql) {
    $GLOBALS['sqls'][] = $sql;
    $GLOBALS['linhas_afetadas'] = null;
    $escrita = preg_match('/^(INSERT|UPDATE|DELETE) /i', $sql) === 1;
    if ($escrita && !empty($GLOBALS['falhar_escritas'])) return false;
    if ($escrita && isset($GLOBALS['antes_da_escrita'])) {
        $concorrente = $GLOBALS['antes_da_escrita'];
        unset($GLOBALS['antes_da_escrita']);
        $concorrente($sql);
    }
    $sql = str_replace('NOW()', "datetime('now')", $sql);
    // Mesmo contrato do banco_query real: erro do banco vira false (o real registra no log).
    try {
        $stmt = $GLOBALS['pdo']->query($sql);
    } catch (PDOException $e) {
        $GLOBALS['erros_sql'][] = $e->getMessage();
        return false;
    }
    if ($stmt->columnCount() === 0) {
        $GLOBALS['linhas_afetadas'] = $stmt->rowCount();
        return true;
    }
    $colunas = [];
    for ($i = 0; $i < $stmt->columnCount(); $i++) $colunas[] = $stmt->getColumnMeta($i)['name'];
    $r = new ApiCrudResultado($stmt->fetchAll(PDO::FETCH_NUM));
    $r->colunas = $colunas;
    return $r;
}
function banco_num_rows($r) { return $r instanceof ApiCrudResultado ? count($r->linhas) : 0; }
function banco_num_fields($r) { return count($r->colunas); }
function banco_field_name($r, $i) { return $r->colunas[$i]; }
function banco_row_array($r) { return $r->linhas[$r->pos++] ?? null; }
function banco_last_id() { return $GLOBALS['pdo']->lastInsertId(); }
function banco_linhas_afetadas() { return $GLOBALS['linhas_afetadas']; }

/**
 * Variáveis globais do idioma corrente lidas dos variables.json reais (o que o deploy grava no banco).
 */
function gestor_variaveis($params = false) {
    static $cache = [];
    $idioma = $GLOBALS['_GESTOR']['linguagem-codigo'];
    if (!isset($cache[$idioma])) {
        $cache[$idioma] = [];
        foreach (json_decode((string)file_get_contents($GLOBALS['raiz'] . 'resources/' . $idioma . '/variables.json'), true) as $v) {
            if (empty($v['modulo'])) $cache[$idioma][$v['id']] = $v['value'];
        }
    }
    return $cache[$idioma][$params['id'] ?? ''] ?? '';
}

/**
 * Extrai e define funções reais de um arquivo do gestor pelo nome.
 */
function extrair_funcoes(string $arquivo, array $nomes): void {
    $fonte = str_replace("\r\n", "\n", (string)file_get_contents($arquivo));
    foreach ($nomes as $nome) {
        $inicio = strpos($fonte, "\nfunction {$nome}(");
        $fim = strpos($fonte, "\n}\n", (int)$inicio);
        if ($inicio === false || $fim === false) throw new RuntimeException('Função não encontrada em ' . basename($arquivo) . ': ' . $nome);
        eval(substr($fonte, $inicio, $fim - $inicio + 2));
    }
}

if (!getenv('API_CRUD_ACESSO_REAL')) {
function gestor_acesso($operacao = false, $modulo = false, $usuario = false) {
    $GLOBALS['acessos'][] = [$operacao, $modulo, $usuario['id_usuarios_perfis'] ?? null];
    return !in_array($operacao === false ? '*' : $operacao, $GLOBALS['negados'], true);
}
}

// Funções reais do banco.php, extraídas pelo nome.
extrair_funcoes($raiz . 'bibliotecas/banco.php', ['banco_select', 'banco_campos_virgulas', 'banco_update', 'banco_insert_name', 'banco_delete', 'banco_retirar_acentos', 'banco_identificador_unico', 'banco_identificador']);

if (getenv('API_CRUD_ACESSO_REAL')) {
    // RBAC real: gestor_acesso() do gestor.php e suas consultas, sobre as tabelas de perfil.
    function existe($valor) { return isset($valor) && $valor !== ''; }
    extrair_funcoes($raiz . 'bibliotecas/banco.php', ['banco_select_name']);
    extrair_funcoes($raiz . 'gestor.php', ['gestor_acesso']);
    $GLOBALS['pdo']->exec("CREATE TABLE usuarios_perfis (id_usuarios_perfis INTEGER, id TEXT);
CREATE TABLE usuarios_perfis_modulos (id_usuarios_perfis_modulos INTEGER PRIMARY KEY AUTOINCREMENT, modulo TEXT, perfil TEXT);
CREATE TABLE modulos_operacoes (id TEXT, operacao TEXT, language TEXT, modulo_id TEXT, status TEXT);
CREATE TABLE usuarios_perfis_modulos_operacoes (id_usuarios_perfis_modulos_operacoes INTEGER PRIMARY KEY AUTOINCREMENT, operacao TEXT, perfil TEXT);");
}

require $raiz . 'bibliotecas/api-crud.php';

$USUARIO = ['id_usuarios' => 7, 'id_usuarios_perfis' => 3, 'scope' => 'read write'];

/**
 * Atalho: despacha no módulo informado (padrão admin-categorias).
 */
function req(string $metodo, string $acao = '', array $query = [], array $corpo = [], ?array $usuario = null, ?string $if_match = null, string $modulo = 'admin-categorias') {
    return api_crud_despachar($modulo, $acao, $metodo, $query, $corpo, $usuario ?? $GLOBALS['USUARIO'], $if_match);
}

function linha(string $sql) {
    return $GLOBALS['pdo']->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
}
