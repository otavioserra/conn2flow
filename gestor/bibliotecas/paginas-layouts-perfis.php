<?php

function paginas_layouts_perfis_validar_layout_padrao($dados, $modulo){
    global $_GESTOR;
    if(empty($dados['mapear_layouts_perfis'])) return;
    $layout = $dados['layout'] ?? '';
    $layout = is_scalar($layout) ? trim((string)$layout) : '';
    if($layout !== ''){
        $valido = banco_select(['unico' => true, 'tabela' => 'layouts', 'campos' => ['id'],
            'extra' => "WHERE id='".banco_escape_field($layout)."' AND status='A' AND language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'"]);
        if($valido) return;
    }
    $_REQUEST['layout'] = '';
    interface_validacao_campos_obrigatorios(['campos' => [[
        'regra' => 'selecao-obrigatorio', 'campo' => 'layout',
        'label' => gestor_variaveis(['modulo' => $modulo, 'id' => 'form-layout-label']),
    ]]]);
}

function paginas_layouts_perfis_json($dados){
    global $_GESTOR;
    if(empty($dados['mapear_layouts_perfis'])) return null;
    $layouts = $dados['layout_profile_layout'] ?? [];
    $perfis = $dados['layout_profile_profile'] ?? [];
    if(!is_array($layouts) || !is_array($perfis)) return null;
    $mapa = [];
    foreach($layouts as $indice => $layout){
        if(!is_scalar($layout) || !is_scalar($perfis[$indice] ?? '')) continue;
        $layout = trim((string)$layout);
        $perfil = trim((string)($perfis[$indice] ?? ''));
        if($layout === '' || $perfil === '') continue;
        $layoutValido = banco_select(['unico' => true, 'tabela' => 'layouts', 'campos' => ['id'],
            'extra' => "WHERE id='".banco_escape_field($layout)."' AND status='A' AND language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'"]);
        $perfilValido = banco_select(['unico' => true, 'tabela' => 'usuarios_perfis', 'campos' => ['id'],
            'extra' => "WHERE id='".banco_escape_field($perfil)."' AND status='A' AND language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'"]);
        // Indexado por perfil: o mesmo layout pode servir a vários perfis. Perfil repetido no
        // formulário fica com a primeira linha.
        if($layoutValido && $perfilValido && !isset($mapa[$perfil])) $mapa[$perfil] = $layout;
    }
    return $mapa ? json_encode($mapa, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
}

// req-219: variante Tailwind do componente quando a página é Tailwind (o interface pode não estar carregado).
function paginas_layouts_perfis_variante($id){
    return function_exists('interface_componente_variante') ? interface_componente_variante($id) : $id;
}

function paginas_layouts_perfis_linha($modulo, $indice, $layout, $perfil){
    global $_GESTOR;
    $linha = gestor_componente(['id' => paginas_layouts_perfis_variante('layout-profile-row'), 'modulo' => $modulo]);
    $linha = modelo_var_troca_tudo($linha, '#row-index#', (string)$indice);
    foreach([
        '#mapping-layout-label#' => 'form-layout-label',
        '#mapping-profile-label#' => 'profile-mapping-profile',
        '#mapping-remove-label#' => 'profile-mapping-remove',
    ] as $marcador => $variavel){
        $rotulo = gestor_variaveis(['modulo' => $modulo, 'id' => $variavel]);
        $linha = modelo_var_troca_tudo($linha, $marcador, htmlspecialchars((string)$rotulo, ENT_QUOTES, 'UTF-8'));
    }
    $idioma = banco_escape_field($_GESTOR['linguagem-codigo']);
    $campos = [
        [
            'tipo' => 'select', 'id' => 'layout-profile-layout-'.$indice,
            'nome' => 'layout_profile_layout[]', 'procurar' => true, 'limpar' => true,
            'placeholder' => gestor_variaveis(['modulo' => $modulo, 'id' => 'form-layout-placeholder']),
            'tabela' => ['id' => true, 'nome' => 'layouts', 'campo' => 'nome',
                'id_selecionado' => $layout, 'where' => "language='".$idioma."'"],
        ],
        [
            'tipo' => 'select', 'id' => 'layout-profile-profile-'.$indice,
            'nome' => 'layout_profile_profile[]', 'procurar' => true, 'limpar' => true,
            'placeholder' => gestor_variaveis(['modulo' => $modulo, 'id' => 'profile-mapping-profile']),
            'tabela' => ['id' => true, 'nome' => 'usuarios_perfis', 'campo' => 'nome',
                'id_selecionado' => $perfil, 'where' => "language='".$idioma."'"],
        ],
    ];
    foreach($campos as $campo) $linha = interface_formulario_campos(['pagina' => $linha, 'campos' => [$campo]]);
    return $linha;
}

function paginas_layouts_perfis_formulario($pagina, $json, $modulo){
    $mapa = gestor_layouts_perfis_mapa($json);
    $componente = gestor_componente(['id' => paginas_layouts_perfis_variante('layout-profile-mapping'), 'modulo' => $modulo]);
    $componente = modelo_var_troca_tudo($componente, '#mapping-checked#', $mapa ? 'checked' : '');
    $componente = modelo_var_troca_tudo($componente, '#mapping-hidden#', $mapa ? '' : 'hidden');
    foreach([
        '#mapping-toggle-label#' => 'profile-mapping-toggle',
        '#mapping-add-label#' => 'profile-mapping-add',
    ] as $marcador => $variavel){
        $rotulo = gestor_variaveis(['modulo' => $modulo, 'id' => $variavel]);
        $componente = modelo_var_troca_tudo($componente, $marcador, htmlspecialchars((string)$rotulo, ENT_QUOTES, 'UTF-8'));
    }
    $linhas = '';
    $indice = 0;
    foreach($mapa as $perfil => $layout){
        $linhas .= paginas_layouts_perfis_linha($modulo, $indice++, $layout, (string)$perfil);
    }
    $componente = modelo_var_troca_tudo($componente, '#mapping-rows#', $linhas);
    $componente = modelo_var_troca_tudo($componente, '#mapping-template#', paginas_layouts_perfis_linha($modulo, 'template', '', ''));
    return modelo_var_troca_tudo($pagina, '#layout-profile-mapping#', $componente);
}
