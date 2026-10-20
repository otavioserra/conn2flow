<?php
// REV-002: getter real de variáveis + semente compilada + handler real; somente SQLite em memória.
$root = dirname(__DIR__, 3);
putenv('API_CRUD_ACESSO_REAL=1');
$harness = file_get_contents($root . '/tests/Fixtures/ApiCrudHarness.php');
$harness = str_replace("\$raiz = dirname(__DIR__, 2) . '/gestor/';", '$raiz = ' . var_export($root . '/gestor/', true) . ';', $harness);
$harness = str_replace('function gestor_variaveis(', 'function fixture_variaveis(', $harness);
eval(substr($harness, 5));
extrair_funcoes($root . '/gestor/bibliotecas/gestor.php', ['gestor_variaveis']);
function check_review($ok, $label) { if (!$ok) throw new RuntimeException($label); }
function out_review($value) { echo json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL; }
$pdo = $GLOBALS['pdo'];
$pdo->exec('CREATE TABLE variaveis (id TEXT, valor TEXT, language TEXT, modulo TEXT)');
$insert = $pdo->prepare('INSERT INTO variaveis VALUES (?, ?, ?, ?)');
$compiled = [];
foreach (json_decode(file_get_contents($root . '/gestor/db/data/VariaveisData.json'), true, 512, JSON_THROW_ON_ERROR) as $v) {
    if (!str_starts_with($v['id'], 'api-crud-')) continue;
    $key = $v['language'] . ':' . $v['id'];
    check_review(!isset($compiled[$key]), 'duplicate compiled variable: ' . $key);
    $compiled[$key] = $v;
    $insert->execute([$v['id'], $v['valor'], $v['language'], $v['modulo']]);
}
$checked = 0;
foreach (['pt-br', 'en'] as $lang) {
    $GLOBALS['_GESTOR']['linguagem-codigo'] = $lang;
    unset($GLOBALS['_GESTOR']['variaveis']); // Uma requisição nova para cada idioma.
    foreach (json_decode(file_get_contents($root . '/gestor/resources/' . $lang . '/variables.json'), true, 512, JSON_THROW_ON_ERROR) as $v) {
        if (!str_starts_with($v['id'], 'api-crud-')) continue;
        $c = $compiled[$lang . ':' . $v['id']] ?? null;
        check_review($c && $c['modulo'] === null && $c['valor'] === $v['value'], 'seed mismatch: ' . $v['id']);
        check_review(gestor_variaveis(['id' => $v['id']]) === $v['value'], 'real getter mismatch: ' . $v['id']);
        $checked++;
    }
    $expected = $lang === 'en' ? 'Record not found: missing' : 'Registro não encontrado: missing';
    check_review(api_crud_msg('api-crud-registro-nao-encontrado', ['ref' => 'missing']) === $expected, 'marker replacement');
}
check_review($checked === 52 && count($compiled) === 52, 'expected 26 variables per language');
check_review(api_crud_msg('api-crud-nao-cadastrada') === 'api-crud-nao-cadastrada', 'missing variable fallback');
out_review(['probe' => 'real gestor_variaveis + compiled seed + markers + missing fallback', 'variables_checked' => $checked, 'languages' => ['pt-br', 'en']]);

extrair_funcoes($root . '/gestor/controladores/api/api.php', ['api_handle_modulo']);
function api_authenticate($required) { return $GLOBALS['USUARIO']; }
function api_get_request_body() { return $GLOBALS['review_body']; }
function api_disparar_hook($action, $data, $module) { return $GLOBALS['review_hook']; }
function gestor_incluir_biblioteca($lib) { $GLOBALS['review_loaded'][] = $lib; }
function api_response_success($data = null, $message = 'OK', $code = 200) { throw new RuntimeException(json_encode(['code' => $code, 'message' => $message, 'data' => $data])); }
function api_response_error($message, $code = 500, $details = null) { throw new RuntimeException(json_encode(['code' => $code, 'message' => $message])); }
$pdo->exec("INSERT INTO usuarios_perfis VALUES (3, 'restrito'); INSERT INTO usuarios_perfis_modulos (modulo, perfil) VALUES ('admin-categorias', 'restrito')");
req('POST', '', [], ['nome' => 'Base']);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];
foreach ([['code' => 202, 'dados' => ['legacy' => true]], ['code' => 403, 'erro' => true, 'mensagem' => 'hook'], null] as $hook) {
    $GLOBALS['review_body'] = [];
    $GLOBALS['review_hook'] = $hook;
    $GLOBALS['review_loaded'] = [];
    try { api_handle_modulo('admin-categorias', 'base'); } catch (RuntimeException $e) {
        $r = json_decode($e->getMessage(), true, 512, JSON_THROW_ON_ERROR);
        check_review($r['code'] === ($hook['code'] ?? 200), 'handler status');
        check_review($GLOBALS['review_loaded'] === ($hook === null ? ['api-crud'] : []), 'legacy priority');
        out_review(['probe' => 'actual handler, controlled auth/transport/hook', 'code' => $r['code'], 'loaded' => $GLOBALS['review_loaded']]);
    }
}
foreach (['pt-br', 'en'] as $lang) {
    $GLOBALS['_GESTOR']['linguagem-codigo'] = $lang;
    unset($GLOBALS['_GESTOR']['variaveis']);
    $GLOBALS['review_body'] = 'scalar';
    $GLOBALS['review_loaded'] = [];
    try { api_handle_modulo('admin-categorias', 'base'); } catch (RuntimeException $e) {
        $r = json_decode($e->getMessage(), true, 512, JSON_THROW_ON_ERROR);
        check_review($r['code'] === 400 && $r['message'] === $compiled[$lang . ':api-crud-corpo-invalido']['valor'], 'controller i18n');
        check_review($GLOBALS['review_loaded'] === [], 'invalid body stops before engine');
        out_review(['probe' => 'actual handler invalid scalar body', 'language' => $lang, 'response' => $r]);
    }
}
check_review($GLOBALS['avisos'] === [], 'PHP warnings');
out_review(['warnings' => $GLOBALS['avisos']]);
