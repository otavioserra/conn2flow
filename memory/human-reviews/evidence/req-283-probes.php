<?php
// Independent review probes. In-memory SQLite only; no runtime/bootstrap or real DB.
$root = dirname(__DIR__, 3);
function review_check($condition, $label) {
    if (!$condition) throw new RuntimeException('Review reproduction changed: ' . $label);
}
$harness = file_get_contents($root . '/tests/Fixtures/ApiCrudHarness.php');
$harness = str_replace("\$raiz = dirname(__DIR__, 2) . '/gestor/';", '$raiz = ' . var_export($root . '/gestor/', true) . ';', $harness);
$harness = str_replace('function gestor_acesso(', 'function gestor_acesso_stub(', $harness);
$harness = str_replace("    \$GLOBALS['sqls'][] = \$sql;", "    \$GLOBALS['sqls'][] = \$sql;\n    if (!empty(\$GLOBALS['review_fail_writes']) && preg_match('/^(INSERT|UPDATE|DELETE) /i', \$sql)) return false;", $harness);
eval(substr($harness, 5));
function extract_review_function($file, $name) {
    $source = str_replace("\r\n", "\n", file_get_contents($file));
    $start = strpos($source, "\nfunction {$name}(");
    $end = strpos($source, "\n}\n", $start);
    if ($start === false || $end === false) throw new RuntimeException($name);
    eval(substr($source, $start, $end - $start + 2));
}
extract_review_function($root . '/gestor/bibliotecas/banco.php', 'banco_select_name');
extract_review_function($root . '/gestor/gestor.php', 'gestor_acesso');
function existe($value) { return isset($value) && $value !== ''; }
$pdo = $GLOBALS['pdo'];
$pdo->exec("CREATE TABLE usuarios_perfis (id_usuarios_perfis INTEGER, id TEXT);
CREATE TABLE usuarios_perfis_modulos (id_usuarios_perfis_modulos INTEGER, modulo TEXT, perfil TEXT);
CREATE TABLE modulos_operacoes (id TEXT, operacao TEXT, language TEXT, modulo_id TEXT, status TEXT);
CREATE TABLE usuarios_perfis_modulos_operacoes (id_usuarios_perfis_modulos_operacoes INTEGER, operacao TEXT, perfil TEXT);
INSERT INTO usuarios_perfis VALUES (3, 'restrito');
INSERT INTO modulos_operacoes VALUES ('editar-categorias', 'editar', 'pt-br', 'admin-categorias', 'A');
INSERT INTO usuarios_perfis_modulos_operacoes VALUES (1, 'editar-categorias', 'restrito');");
// Seed through the engine primitive, since this user deliberately has no module permission.
$cfg = api_crud_config('admin-categorias');
api_crud_inserir($cfg, ['nome' => 'Base'], $USUARIO, 'pt-br');
$read = req('GET', 'base');
$write = req('PATCH', 'base', [], ['nome' => 'Alterado sem acesso ao modulo']);
review_check($read['code'] === 403 && $write['code'] === 200 && linha('SELECT nome FROM categorias')['nome'] === 'Alterado sem acesso ao modulo', 'RBAC');
echo json_encode(['probe' => 'RBAC real sem vinculo ao modulo, com permissao editar', 'module_links' => linha('SELECT COUNT(*) AS n FROM usuarios_perfis_modulos')['n'], 'get_code' => $read['code'], 'patch_code' => $write['code'], 'stored_name' => linha('SELECT nome FROM categorias')['nome']], JSON_UNESCAPED_UNICODE) . PHP_EOL;
// Deterministic interleaving: capture v2; another writer commits v3; conditional write v2 affects zero rows.
$snapshot = api_crud_buscar($cfg, 'base', 'pt-br');
$pdo->exec("UPDATE categorias SET nome='Outro escritor', versao=versao+1 WHERE id='base'");
$result = api_crud_gravar($cfg, $snapshot, ['nome' => 'Minha escrita'], (int)$snapshot['versao']);
review_check($result !== null && $result['nome'] === 'Outro escritor', 'concurrent write');
echo json_encode(['probe' => 'Concorrencia apos snapshot', 'expected_version' => $snapshot['versao'], 'returned_non_null' => $result !== null, 'returned_name' => $result['nome'] ?? null, 'stored_name' => linha('SELECT nome FROM categorias')['nome']], JSON_UNESCAPED_UNICODE) . PHP_EOL;
// Force a zero-row update without changing the stored version.
$pdo->exec("CREATE TRIGGER reject_patch BEFORE UPDATE ON categorias BEGIN SELECT RAISE(IGNORE); END");
$current = api_crud_buscar($cfg, 'base', 'pt-br');
$result = api_crud_atualizar($cfg, $current, ['nome' => 'Nao persistiu'], $USUARIO, false, null);
review_check($result['code'] === 200 && $result['dados']['nome'] === 'Outro escritor', 'zero affected rows');
echo json_encode(['probe' => 'Update zero linhas sem versao', 'code' => $result['code'], 'returned_name' => $result['dados']['nome'] ?? null], JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo json_encode(['warnings' => $GLOBALS['avisos']], JSON_UNESCAPED_UNICODE) . PHP_EOL;
$pdo->exec('DROP TRIGGER reject_patch');
$pdo->exec("INSERT INTO usuarios_perfis_modulos VALUES (1, 'admin-categorias', 'restrito')");
$GLOBALS['review_fail_writes'] = true;
$create = req('POST', '', [], ['nome' => 'Novo que falha']);
$update = req('PATCH', 'base', [], ['nome' => 'Update que falha']);
$delete = req('DELETE', 'base');
review_check($create['code'] === 201 && $create['dados']['id'] === 'base' && $update['code'] === 200 && $delete['code'] === 200 && linha('SELECT status FROM categorias')['status'] === 'A', 'failed persistence');
echo json_encode(['probe' => 'Falha de escrita retorna false como no banco_query mysqli', 'create_code' => $create['code'], 'create_returned_id' => $create['dados']['id'] ?? null, 'update_code' => $update['code'], 'delete_code' => $delete['code'], 'stored' => linha('SELECT id, nome, status FROM categorias')], JSON_UNESCAPED_UNICODE) . PHP_EOL;
$GLOBALS['review_fail_writes'] = false;
// Execute actual controller handler; substitute only transport/auth and legacy module hook.
extract_review_function($root . '/gestor/controladores/api/api.php', 'api_handle_modulo');
function api_authenticate($required) { return $GLOBALS['USUARIO']; }
function api_get_request_body() { return []; }
function api_disparar_hook($action, $data, $module) { return $GLOBALS['review_hook_response']; }
function gestor_incluir_biblioteca($lib) { $GLOBALS['review_loaded'][] = $lib; }
function api_response_success($data = null, $message = 'OK', $code = 200) { throw new RuntimeException(json_encode(['data' => $data, 'message' => $message, 'code' => $code])); }
function api_response_error($message, $code = 500, $details = null) { throw new RuntimeException(json_encode(['message' => $message, 'code' => $code])); }
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];
foreach ([['dados' => ['legacy' => true], 'mensagem' => 'Legado', 'code' => 202], null, ['erro' => true, 'mensagem' => 'Negado pelo hook', 'code' => 403]] as $hookResponse) {
    $GLOBALS['review_hook_response'] = $hookResponse;
    $GLOBALS['review_loaded'] = [];
    try { api_handle_modulo('admin-categorias', 'base'); } catch (RuntimeException $e) {
        $response = json_decode($e->getMessage(), true);
        $expectedCode = $hookResponse === null ? 200 : $hookResponse['code'];
        review_check($response['code'] === $expectedCode && $GLOBALS['review_loaded'] === ($hookResponse === null ? ['api-crud'] : []), 'legacy handler priority');
        echo json_encode(['probe' => 'Handler real com hook ' . ($hookResponse === null ? 'null' : (isset($hookResponse['erro']) ? 'erro' : 'sucesso')), 'response' => json_decode($e->getMessage(), true), 'loaded' => $GLOBALS['review_loaded']], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
review_check($GLOBALS['avisos'] === [], 'PHP warnings');
