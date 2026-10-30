<?php
/**
 * REQ-297 — funções reais de hierarquia de usuarios sobre um SQLite em memória.
 *
 * Só a E/S do banco é simulada; banco_select, banco_update, banco_insert_name e banco_identificador
 * vêm do banco.php e as funções de hierarquia vêm do usuario.php, pelo nome. Assim o arquivo inteiro
 * da biblioteca não é carregado, e o teste roda sem chaves, sessão ou config.
 */

namespace {

$raiz = dirname(__DIR__, 2) . '/gestor/';

$_GESTOR = [];
$GLOBALS['sqls'] = [];

$GLOBALS['pdo'] = new PDO('sqlite::memory:');
$GLOBALS['pdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$GLOBALS['pdo']->exec("CREATE TABLE usuarios_perfis (id_usuarios_perfis INTEGER PRIMARY KEY, id TEXT, language TEXT, max_sub_users INTEGER NOT NULL DEFAULT 0);
CREATE TABLE usuarios (id_usuarios INTEGER PRIMARY KEY AUTOINCREMENT, id_usuarios_pai INTEGER, id_usuarios_perfis INTEGER, id_hosts INTEGER, nome_conta TEXT, nome TEXT, id TEXT, usuario TEXT, senha TEXT, email TEXT, primeiro_nome TEXT, nome_do_meio TEXT, ultimo_nome TEXT, status TEXT, versao INTEGER, data_criacao TEXT, data_modificacao TEXT);");

// Trava de conexão: o cenário decide se a próxima tentativa consegue.
$GLOBALS['pdo']->sqliteCreateFunction('GET_LOCK', function ($nome, $espera) {
    $GLOBALS['travas'][] = $nome;
    return empty($GLOBALS['trava_ocupada']) ? 1 : 0;
});
$GLOBALS['pdo']->sqliteCreateFunction('RELEASE_LOCK', function ($nome) {
    $GLOBALS['liberadas'][] = $nome;
    return 1;
});

final class HierarquiaResultado {
    public array $linhas;
    public array $colunas = [];
    public int $pos = 0;
    public function __construct(array $linhas) { $this->linhas = $linhas; }
}

function banco_escape_field($v) { return str_replace("'", "''", (string)$v); }
function banco_query($sql) {
    $GLOBALS['sqls'][] = $sql;
    $sql = str_replace('NOW()', "datetime('now')", $sql);
    try {
        $stmt = $GLOBALS['pdo']->query($sql);
    } catch (PDOException $e) {
        $GLOBALS['erros_sql'][] = $e->getMessage();
        return false;
    }
    if ($stmt->columnCount() === 0) return true;
    $colunas = [];
    for ($i = 0; $i < $stmt->columnCount(); $i++) $colunas[] = $stmt->getColumnMeta($i)['name'];
    $r = new HierarquiaResultado($stmt->fetchAll(PDO::FETCH_NUM));
    $r->colunas = $colunas;
    return $r;
}
function banco_num_rows($r) { return $r instanceof HierarquiaResultado ? count($r->linhas) : 0; }
function banco_num_fields($r) { return count($r->colunas); }
function banco_field_name($r, $i) { return $r->colunas[$i]; }
function banco_row_array($r) { return $r->linhas[$r->pos++] ?? null; }
function banco_last_id() { return $GLOBALS['pdo']->lastInsertId(); }

function extrair_funcoes(string $arquivo, array $nomes): void {
    $fonte = str_replace("\r\n", "\n", (string)file_get_contents($arquivo));
    foreach ($nomes as $nome) {
        $inicio = strpos($fonte, "\nfunction {$nome}(");
        $fim = strpos($fonte, "\n}\n", (int)$inicio);
        if ($inicio === false || $fim === false) throw new RuntimeException('Função não encontrada em ' . basename($arquivo) . ': ' . $nome);
        eval(substr($fonte, $inicio, $fim - $inicio + 2));
    }
}

extrair_funcoes($raiz . 'bibliotecas/banco.php', ['banco_select', 'banco_sql', 'banco_campos_virgulas', 'banco_update', 'banco_insert_name', 'banco_retirar_acentos', 'banco_identificador_unico', 'banco_identificador']);
extrair_funcoes($raiz . 'bibliotecas/usuario.php', ['usuario_organizacao_id', 'usuario_hierarquia_bloqueado', 'usuario_equipe_titular', 'usuario_equipe_membros', 'usuario_equipe_cota', 'usuario_equipe_habilitada', 'usuario_equipe_travar', 'usuario_equipe_destravar', 'usuario_equipe_membro_criar', 'usuario_equipe_membro_status']);

}
