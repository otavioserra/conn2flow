<?php

declare(strict_types=1);

// Processo isolado: a biblioteca consulta o banco para validar layout e perfil.
$root = dirname(__DIR__, 2);

function banco_escape_field($valor) { return addslashes((string)$valor); }
function banco_select($dados) {
    $validos = ['layouts' => ['portal', 'admin'], 'usuarios_perfis' => ['cliente', 'equipe']];
    foreach ($validos[$dados['tabela']] ?? [] as $id) {
        if (str_contains($dados['extra'], "id='" . $id . "'")) return ['id' => $id];
    }
    return null;
}

$_GESTOR = ['linguagem-codigo' => 'pt-br'];
require $root . '/gestor/bibliotecas/paginas-layouts-perfis.php';

// Dois perfis no mesmo layout, um perfil repetido (fica a primeira linha) e um par inválido.
echo paginas_layouts_perfis_json([
    'mapear_layouts_perfis' => '1',
    'layout_profile_layout' => ['portal', 'portal', 'admin', 'inexistente'],
    'layout_profile_profile' => ['cliente', 'equipe', 'cliente', 'equipe'],
]);
