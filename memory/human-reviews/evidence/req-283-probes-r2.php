<?php
// Sondas da REV-001 refeitas pelo Executor (rodada 2, BATCH-292) afirmando o comportamento corrigido.
// Mesmos cenários de req-283-probes.php; SQLite em memória, gestor_acesso() real, sem banco real.
// Uso: php memory/human-reviews/evidence/req-283-probes-r2.php > memory/human-reviews/evidence/req-283-probes-r2.jsonl
$root = dirname(__DIR__, 3);
putenv('API_CRUD_ACESSO_REAL=1');
require $root . '/tests/Fixtures/ApiCrudHarness.php';
function probe_check($condition, $label) {
    if (!$condition) throw new RuntimeException('Correction not observed: ' . $label);
}
function probe_out(array $data) { echo json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL; }

$pdo = $GLOBALS['pdo'];
$pdo->exec("INSERT INTO usuarios_perfis VALUES (3, 'restrito');
INSERT INTO modulos_operacoes VALUES ('editar-categorias', 'editar', 'pt-br', 'admin-categorias', 'A');
INSERT INTO usuarios_perfis_modulos_operacoes (operacao, perfil) VALUES ('editar-categorias', 'restrito');");
$cfg = api_crud_config('admin-categorias');
api_crud_inserir($cfg, ['nome' => 'Base'], $USUARIO, 'pt-br');

// F1: sem vínculo ao módulo, a permissão residual de editar não autoriza.
$read = req('GET', 'base');
$write = req('PATCH', 'base', [], ['nome' => 'Alterado sem acesso ao modulo']);
probe_check($read['code'] === 403 && $write['code'] === 403 && linha('SELECT nome FROM categorias')['nome'] === 'Base', 'F1 RBAC');
probe_out(['probe' => 'F1 RBAC real sem vinculo ao modulo, com permissao editar', 'module_links' => linha('SELECT COUNT(*) AS n FROM usuarios_perfis_modulos')['n'], 'get_code' => $read['code'], 'patch_code' => $write['code'], 'patch_message' => $write['mensagem'], 'stored_name' => linha('SELECT nome FROM categorias')['nome']]);

// F3: interleaving determinístico — snapshot v1; outro escritor grava v2; escrita condicional v1 perde.
$snapshot = api_crud_buscar($cfg, 'base', 'pt-br');
$pdo->exec("UPDATE categorias SET nome='Outro escritor', versao=versao+1 WHERE id='base'");
$result = api_crud_gravar($cfg, $snapshot, ['nome' => 'Minha escrita'], (int)$snapshot['versao']);
probe_check(!empty($result['erro']) && $result['code'] === 409 && linha('SELECT nome FROM categorias')['nome'] === 'Outro escritor', 'F3 concurrent write');
probe_out(['probe' => 'F3 Concorrencia apos snapshot (primitiva)', 'expected_version' => $snapshot['versao'], 'code' => $result['code'], 'detalhes' => $result['detalhes'] ?? null, 'stored_name' => linha('SELECT nome FROM categorias')['nome']]);

// F3 pela rota: pré-teste passa, o concorrente grava entre o pré-teste e o UPDATE.
$pdo->exec("INSERT INTO usuarios_perfis_modulos (modulo, perfil) VALUES ('admin-categorias', 'restrito')");
$GLOBALS['antes_da_escrita'] = function () { $GLOBALS['pdo']->exec("UPDATE categorias SET nome='Terceiro', versao=versao+1 WHERE id='base'"); };
$route = req('PATCH', 'base', [], ['nome' => 'Minha escrita', 'versao' => 2]);
probe_check($route['code'] === 409 && linha('SELECT nome FROM categorias')['nome'] === 'Terceiro', 'F3 route');
probe_out(['probe' => 'F3 Concorrencia apos pre-teste (rota PATCH)', 'code' => $route['code'], 'message' => $route['mensagem'], 'detalhes' => $route['detalhes'] ?? null]);

// F2/F3: UPDATE sem versão que afeta zero linhas não é sucesso.
$pdo->exec("CREATE TRIGGER reject_patch BEFORE UPDATE ON categorias BEGIN SELECT RAISE(IGNORE); END");
$current = api_crud_buscar($cfg, 'base', 'pt-br');
$result = api_crud_atualizar($cfg, $current, ['nome' => 'Nao persistiu'], $USUARIO, false, null);
probe_check($result['code'] === 409 && !isset($result['dados']), 'zero affected rows');
probe_out(['probe' => 'Update zero linhas sem versao', 'code' => $result['code'], 'message' => $result['mensagem']]);
$pdo->exec('DROP TRIGGER reject_patch');

// F2: escritas devolvem false (contrato do banco_query real em erro).
$GLOBALS['falhar_escritas'] = true;
$create = req('POST', '', [], ['nome' => 'Novo que falha']);
$update = req('PATCH', 'base', [], ['nome' => 'Update que falha']);
$delete = req('DELETE', 'base');
$status = req('POST', 'base/status');
$GLOBALS['falhar_escritas'] = false;
$stored = linha('SELECT COUNT(*) AS n, id, nome, status FROM categorias');
probe_check($create['code'] === 500 && !isset($create['dados']) && $update['code'] === 500 && $delete['code'] === 500 && $status['code'] === 500 && $stored['status'] === 'A' && (int)$stored['n'] === 1, 'F2 failed persistence');
probe_out(['probe' => 'F2 Falha de escrita retorna false como no banco_query mysqli', 'create_code' => $create['code'], 'create_has_data' => isset($create['dados']), 'update_code' => $update['code'], 'delete_code' => $delete['code'], 'status_code' => $status['code'], 'message' => $create['mensagem'], 'stored' => $stored]);

// F4: mesma mensagem em en.
$GLOBALS['_GESTOR']['linguagem-codigo'] = 'en';
$en = req('GET', 'nao-existe');
$GLOBALS['_GESTOR']['linguagem-codigo'] = 'pt-br';
$pt = req('GET', 'nao-existe');
probe_check($en['mensagem'] === 'Record not found: nao-existe' && $pt['mensagem'] === 'Registro não encontrado: nao-existe', 'F4 i18n');
probe_out(['probe' => 'F4 Mensagem por variavel pt-br/en', 'pt-br' => $pt['mensagem'], 'en' => $en['mensagem']]);

probe_check($GLOBALS['avisos'] === [], 'PHP warnings');
probe_out(['warnings' => $GLOBALS['avisos'], 'server_log_lines' => count(logs())]);
