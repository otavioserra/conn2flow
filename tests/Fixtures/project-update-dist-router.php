<?php

// Router para exercitar o controlador real via HTTP, com dublês de autenticação e banco.
$instalacao = getcwd() . '/gestor/';
$core = dirname(__DIR__, 2) . '/gestor/';
$_GESTOR = [
    'ROOT_PATH' => $instalacao,
    'bibliotecas-path' => $core . 'bibliotecas/',
    'controladores-path' => $core . 'controladores/',
    'modulos-path' => $instalacao . 'modulos/',
    'plugins-path' => $instalacao . 'plugins/',
    'logs-path' => $instalacao . 'logs/',
    'dist-path' => getcwd() . '/public_html/dist/',
    'caminho' => explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')),
];
$_BANCO = ['host' => '', 'nome' => '', 'usuario' => ''];

function gestor_incluir_biblioteca($biblioteca) {}
function oauth2_validar_token($dados) {
    return $dados['token'] === 'fixture-deploy' ? ['id_usuarios' => 1] : false;
}
function banco_escape_field($valor) { return $valor; }
function banco_query($sql) { return !str_starts_with($sql, 'SHOW TABLES'); }
function banco_sql($sql) { return [['request_count' => 1]]; }
function hooks_registrar_projeto() { return 0; }
function sitemap_gerar_completo() { return true; }

if ($_GESTOR['caminho'] === ['fixture', 'core-finalize']) {
    $_GESTOR['dist-path'] = getcwd() . '/public_core/dist/';
    require $core . 'controladores/atualizacoes/atualizacoes-sistema.php';
    saveWebState('fixture', [
        'opts' => ['no-health' => true],
        'exec_id' => null,
        'finished' => false,
        'progress' => ['deploy_files' => ['done' => true]],
    ]);
    jsonResponse(webFinalize('fixture'));
    return;
}

require $core . 'controladores/api/api.php';
