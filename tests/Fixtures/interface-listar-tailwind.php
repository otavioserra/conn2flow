<?php
// Processo isolado: nunca conecta a banco real.
error_reporting(E_ALL);
$_GESTOR = ['modulo' => 'piloto', 'opcao' => 'listar', 'usuario-id' => 1, 'pagina#titulo' => 'Piloto', 'caminho-total' => 'piloto'];
$sql = [];
$sessao = [];
function gestor_asset_version() { return '1'; }
function gestor_sessao_variavel($k, $v = null) { global $sessao; if ($v !== null) $sessao = $v; return $sessao; }
function banco_escape_field($v) { return addslashes((string)$v); }
function banco_campos_virgulas($v) { return implode(',', $v); }
function banco_select_name($c, $t, $e) { global $sql; $sql[] = $e; return []; }
function banco_select($p) { return []; }
function gestor_framework_css_atual() { return ['modo' => 'tailwindcss']; }
function gestor_componente($p) { return '<div data-id="'.$p['id'].'">#titulo# #resumo#</div>'; }
function gestor_variaveis($p) { return $p['id']; }
function modelo_var_troca($p, $de, $para) { return str_replace($de, $para, $p); }
function gestor_incluir_biblioteca() {}
function recursos_tag_js($p, $v) { return $p; }
require dirname(__DIR__, 2).'/gestor/bibliotecas/interface.php';
require dirname(__DIR__, 2).'/gestor/bibliotecas/interface-listar-tailwind.php';
$params = [
    'banco' => ['nome' => 'teste', 'campos' => ['nome'], 'id' => 'slug', 'status' => 'status', 'where' => "language='en'"],
    'tabela' => ['colunas' => [
        ['id' => 'nome', 'nome' => 'Nome', 'ordenar' => 'desc'],
        ['id' => 'oculta', 'nome' => 'Oculta', 'nao_visivel' => true, 'nao_ordenar' => true, 'nao_procurar' => true],
    ]],
];
$config = interface_listar_tailwind_configurar($params, ['registroInicial' => -2, 'registrosPorPagina' => -1]);
interface_listar_tailwind_finalizar($params);
$_REQUEST = ['draw' => 5, 'start' => -10, 'length' => '1e100', 'search' => ['value' => 'zz'],
    'columns' => [['data' => 'senha']], 'columnsExtraSearch' => ['senha'], 'order' => [['column' => 2, 'dir' => 'asc']]];
$result = interface_listar_ajax();
echo json_encode(['config' => $config, 'result' => $result, 'sql' => $sql, 'session' => $sessao, 'gestor' => $_GESTOR]);
