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
 *  - API_CRUD_NEGADOS: JSON com operações que o perfil não tem.
 */

$raiz = dirname(__DIR__, 2) . '/gestor/';

$_GESTOR = [
    'linguagem-codigo' => 'pt-br',
    'modulos-path' => $raiz . 'modulos/',
    'plugins-path' => rtrim((string)getenv('API_CRUD_PLUGINS'), '/\\') . '/',
];
$GLOBALS['avisos'] = [];
$GLOBALS['sqls'] = [];
$GLOBALS['acessos'] = [];
$GLOBALS['negados'] = json_decode(getenv('API_CRUD_NEGADOS') ?: '[]', true);

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
    $sql = str_replace('NOW()', "datetime('now')", $sql);
    $stmt = $GLOBALS['pdo']->query($sql);
    if ($stmt->columnCount() === 0) return true;
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
function gestor_acesso($operacao = false, $modulo = false, $usuario = false) {
    $GLOBALS['acessos'][] = [$operacao, $modulo, $usuario['id_usuarios_perfis'] ?? null];
    return !in_array($operacao, $GLOBALS['negados'], true);
}

// Funções reais do banco.php, extraídas pelo nome.
$fonte = str_replace("\r\n", "\n", (string)file_get_contents($raiz . 'bibliotecas/banco.php'));
foreach (['banco_select', 'banco_campos_virgulas', 'banco_update', 'banco_insert_name', 'banco_delete', 'banco_retirar_acentos', 'banco_identificador_unico', 'banco_identificador'] as $nome) {
    $inicio = strpos($fonte, "\nfunction {$nome}(");
    $fim = strpos($fonte, "\n}\n", (int)$inicio);
    if ($inicio === false || $fim === false) throw new RuntimeException('Função não encontrada no banco.php: ' . $nome);
    eval(substr($fonte, $inicio, $fim - $inicio + 2));
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
