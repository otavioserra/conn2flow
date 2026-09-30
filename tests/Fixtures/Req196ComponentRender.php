<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

function modelo_var_troca($texto, $marcador, $valor) { return str_replace($marcador, $valor, $texto); }
function modelo_var_troca_tudo($texto, $marcador, $valor) { return str_replace($marcador, $valor, $texto); }
function banco_escape_field($valor) { return addslashes((string)$valor); }
function banco_campos_virgulas($campos) { return implode(',', $campos); }
function banco_select_name($campos, $tabela, $extra) {
    if ($tabela === 'layouts') return [['id' => 'portal', 'nome' => 'Portal'], ['id' => 'admin', 'nome' => 'Admin']];
    if ($tabela === 'usuarios_perfis') return [['id' => 'cliente', 'nome' => 'Cliente'], ['id' => 'equipe', 'nome' => 'Equipe']];
    if ($tabela === 'paginas') {
        $GLOBALS['req196_page_queries'][] = $extra;
        return [['id' => 'portal', 'nome' => 'Portal', 'caminho' => 'portal/']];
    }
    throw new RuntimeException('Unexpected table: ' . $tabela);
}
function gestor_variaveis($dados) { return $dados['id']; }
function gestor_componente($dados) {
    global $root, $_GESTOR;
    $id = $dados['id'];
    $arquivo = $root . '/gestor/modulos/' . $dados['modulo'] . '/resources/'
        . $_GESTOR['linguagem-codigo'] . '/components/' . $id . '/' . $id . '.html';
    $html = file_get_contents($arquivo);
    if ($html === false) throw new RuntimeException('Component missing: ' . $arquivo);
    return $html;
}

$interface = (string)file_get_contents($root . '/gestor/bibliotecas/interface.php');
if (!preg_match('/function interface_formulario_campos\([^)]*\)\{.*?^\}/ms', $interface, $funcao)) {
    throw new RuntimeException('Fomantic select generator missing');
}
eval($funcao[0]);
$biblioteca = (string)file_get_contents($root . '/gestor/bibliotecas/paginas-layouts-perfis.php');
if (preg_match('/<(?:div|select|option|button|label|input)\b/i', $biblioteca)) {
    throw new RuntimeException('Visual markup must live in components');
}
require $root . '/gestor/bibliotecas/paginas-layouts-perfis.php';

foreach (['admin-paginas', 'publisher-pages'] as $modulo) {
    foreach (['pt-br', 'en'] as $idioma) {
        $_GESTOR = [
            'linguagem-codigo' => $idioma,
            'modulo-id' => $modulo,
            'modulo#' . $modulo => ['tabela' => ['id' => 'id', 'status' => 'status']],
        ];
        $html = paginas_layouts_perfis_formulario('#layout-profile-mapping#', '{"portal":"cliente"}', $modulo);
        if (substr_count($html, '<select') !== 4
            || !str_contains($html, 'class="ui search clearable dropdown"')
            || !str_contains($html, '<option value="portal" selected>')
            || !str_contains($html, '<option value="cliente" selected>')
            || str_contains($html, '#mapping-') || str_contains($html, '#select-')) {
            throw new RuntimeException('Invalid component render: ' . $modulo . ' ' . $idioma);
        }
    }
}

$perfis = (string)file_get_contents($root . '/gestor/modulos/usuarios-perfis/usuarios-perfis.php');
foreach (['usuarios_perfis_rotulo_pagina', 'usuarios_perfis_componente_pagina_inicial', 'usuarios_perfis_ajax_buscar_pagina_inicial'] as $nome) {
    if (!preg_match('/function ' . $nome . '\([^)]*\)\{.*?^\}/ms', $perfis, $funcao)) {
        throw new RuntimeException('Profile home function missing: ' . $nome);
    }
    eval($funcao[0]);
}
foreach (['pt-br', 'en'] as $idioma) {
    $_GESTOR = ['linguagem-codigo' => $idioma, 'modulo-id' => 'usuarios-perfis'];
    $html = usuarios_perfis_componente_pagina_inicial('portal/');
    if (!str_contains($html, 'name="pagina_inicial" value="portal/"')
        || !str_contains($html, 'Portal (portal')
        || !str_contains($html, 'id="pagina-inicial-busca"')
        || str_contains($html, '<select')
        || str_contains($html, '#home-page-')) {
        throw new RuntimeException('Invalid lazy profile home component: ' . $idioma);
    }
    $_REQUEST = ['q' => 'por'];
    usuarios_perfis_ajax_buscar_pagina_inicial();
    if ($_GESTOR['ajax-json']['status'] !== 'Ok'
        || $_GESTOR['ajax-json']['results'][0]['value'] !== 'portal/') {
        throw new RuntimeException('Invalid page search response: ' . $idioma);
    }
}
foreach ($GLOBALS['req196_page_queries'] as $query) {
    if (!str_contains($query, 'LIMIT ') || !str_contains($query, "status='A'")) {
        throw new RuntimeException('Page lookup must be filtered and bounded');
    }
}
