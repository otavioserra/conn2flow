<?php
/**
 * REQ-286 — arnês da biblioteca de choques (superados e decisão em lote), executado num processo à parte.
 *
 * Só a E/S do banco é simulada sobre um SQLite em memória (`SHOW TABLES` e `NOW()` traduzidos); o motor
 * (`instalacao-manifesto.php`) e a biblioteca (`atualizacoes-choques.php`) rodam de verdade sobre uma instalação
 * temporária. Imprime um JSON com o resultado de cada passo.
 */

$raiz = dirname(__DIR__, 2) . '/gestor/';
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE atualizacoes_choques (id_atualizacoes_choques INTEGER PRIMARY KEY AUTOINCREMENT, execucao TEXT, origem TEXT, camada TEXT, versao TEXT,
    caminho TEXT, tipo TEXT DEFAULT 'arquivo', motivo TEXT, camada_dona TEXT, hash_disco TEXT, hash_novo TEXT, copia TEXT, diff TEXT, resolucao TEXT,
    resolvido_em TEXT, resolvido_por TEXT, data_criacao TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec('CREATE TABLE paginas (id TEXT, language TEXT, status TEXT)');
$GLOBALS['pdo'] = $pdo;

final class ChoquesResultado { public array $linhas; public int $pos = 0; public function __construct(array $l) { $this->linhas = $l; } }
function banco_escape_field($v) { return str_replace("'", "''", (string)$v); }
function banco_query($sql) {
    $sql = (string)$sql;
    if (preg_match("/^SHOW TABLES LIKE '([a-z_]+)'$/", $sql, $m)) $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $m[1] . "'";
    if (preg_match('/^SHOW COLUMNS FROM `([a-z_]+)`$/', $sql, $m)) $sql = "SELECT name AS Field FROM pragma_table_info('" . $m[1] . "')";
    $sql = str_replace('NOW()', 'CURRENT_TIMESTAMP', $sql);
    try { $st = $GLOBALS['pdo']->query($sql); } catch (Throwable $e) { $GLOBALS['erros'][] = $e->getMessage() . ' | ' . $sql; return false; }
    return stripos(ltrim($sql), 'SELECT') === 0 ? new ChoquesResultado($st->fetchAll(PDO::FETCH_ASSOC)) : true;
}
function banco_fetch_assoc($r) { return $r instanceof ChoquesResultado ? ($r->linhas[$r->pos++] ?? null) : null; }
function banco_num_rows($r) { return $r instanceof ChoquesResultado ? count($r->linhas) : 0; }

require $raiz . 'bibliotecas/instalacao-manifesto.php';
require $raiz . 'bibliotecas/atualizacoes-choques.php';

$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-choques-' . uniqid() . DIRECTORY_SEPARATOR;
$escrever = function (string $rel, string $c) use ($base) { @mkdir(dirname($base . $rel), 0777, true); file_put_contents($base . $rel, $c); };
$escrever('a.php', 'novo');          // editado, disco já igual ao novo → superado
$escrever('b.php', 'b-servidor');    // editado de verdade → fica
$escrever('c.php', 'c-v3');          // choque antigo (v2), disco igual à última entrega (v3) → superado
// d.php: retirada cujo arquivo já não existe → superado
$escrever('e.php', 'projeto');       // sobreposto: disco é da camada de cima → fica
$escrever('vendor/x.php', 'v-sync'); // editado (sobra de antes da REQ-286) → fica para o lote
instalacao_manifesto_gravar($base, 'core', ['a.php' => hash('sha256', 'novo'), 'b.php' => hash('sha256', 'b-v2'), 'c.php' => hash('sha256', 'c-v3'), 'e.php' => hash('sha256', 'core'), 'vendor/x.php' => hash('sha256', 'v-2')], 'v3');

$ins = function (array $c) { $cols = implode(',', array_keys($c)); $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : "'" . banco_escape_field($v) . "'", $c)); banco_query("INSERT INTO atualizacoes_choques ($cols) VALUES ($vals)"); };
$ins(['origem' => 'update-system', 'camada' => 'core', 'versao' => 'v3', 'caminho' => 'a.php', 'tipo' => 'arquivo', 'motivo' => 'editado', 'hash_novo' => hash('sha256', 'novo')]);
$ins(['origem' => 'update-system', 'camada' => 'core', 'versao' => 'v3', 'caminho' => 'b.php', 'tipo' => 'arquivo', 'motivo' => 'editado', 'hash_novo' => hash('sha256', 'b-v2')]);
$ins(['origem' => 'update-system', 'camada' => 'core', 'versao' => 'v2', 'caminho' => 'c.php', 'tipo' => 'arquivo', 'motivo' => 'editado', 'hash_novo' => hash('sha256', 'c-v2')]);
$ins(['origem' => 'update-system', 'camada' => 'core', 'versao' => 'v3', 'caminho' => 'd.php', 'tipo' => 'retirada', 'motivo' => 'retirado']);
$ins(['origem' => 'update-system', 'camada' => 'core', 'versao' => 'v3', 'caminho' => 'e.php', 'tipo' => 'arquivo', 'motivo' => 'sobreposto', 'camada_dona' => 'projeto', 'hash_novo' => hash('sha256', 'core')]);
$ins(['origem' => 'update-system', 'camada' => 'core', 'versao' => 'v3', 'caminho' => 'vendor/x.php', 'tipo' => 'arquivo', 'motivo' => 'editado', 'hash_novo' => hash('sha256', 'v-2'), 'copia' => 'backups/overrides/v3/vendor/x.php']);
$escrever('backups/overrides/v3/vendor/x.php', 'v-2');
$ins(['origem' => 'db-sync', 'camada' => 'projeto', 'versao' => 'p9', 'caminho' => 'db:paginas?id=home&language=pt-br', 'tipo' => 'registro', 'motivo' => 'retirado']);
$pdo->exec("INSERT INTO paginas VALUES ('home','pt-br','A')");

$out = [];
$out['superados'] = atualizacoes_choques_resolver_superados($base);
$out['apos_superados'] = $pdo->query("SELECT caminho, IFNULL(resolucao,'') AS r FROM atualizacoes_choques ORDER BY id_atualizacoes_choques")->fetchAll(PDO::FETCH_KEY_PAIR);
$out['entregas'] = atualizacoes_choques_entregas_pendentes();
$out['lote_invalido'] = atualizacoes_choques_resolver_lote($base, [], 'mesclar', 'teste');
// Sobrescrever na entrega v3 do core: b.php e vendor/x.php aceitam; e.php (sobreposto) não aceita e fica.
$out['lote_core'] = atualizacoes_choques_resolver_lote($base, ['versao' => 'v3', 'origem' => 'update-system', 'camada' => 'core'], 'sobrescrever', 'teste');
$out['disco_vendor'] = file_get_contents($base . 'vendor/x.php');
// Manter em todos os pendentes que sobraram (o registro do projeto e o sobreposto).
$out['lote_resto'] = atualizacoes_choques_resolver_lote($base, [], 'manter', 'teste');
$out['final'] = $pdo->query("SELECT caminho, IFNULL(resolucao,'') AS r FROM atualizacoes_choques ORDER BY id_atualizacoes_choques")->fetchAll(PDO::FETCH_KEY_PAIR);
$out['pagina_status'] = $pdo->query("SELECT status FROM paginas WHERE id='home'")->fetchColumn();
$out['erros'] = $GLOBALS['erros'] ?? [];
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
