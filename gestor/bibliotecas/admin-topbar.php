<?php

/** REQ-228: cabeçalho administrativo. Nenhum identificador de usuário vem do cliente. */
function admin_topbar_escape($valor){
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_topbar_catalogo(){
    global $_GESTOR;
    $language = banco_escape_field($_GESTOR['linguagem-codigo']);
    $iconeCampo = banco_campo_existe('icone_tailwind', 'modulos') ? 'm.icone_tailwind' : "'star' AS icone_tailwind";
    $paginas = banco_sql("SELECT p.id, p.nome, p.caminho, p.modulo, p.opcao, p.raiz, ".$iconeCampo
        . " FROM paginas p INNER JOIN modulos m ON m.id=p.modulo AND m.language=p.language AND m.status='A'"
        . " WHERE p.status='A' AND p.language='".$language."' AND COALESCE(p.sem_permissao,0)=0"
        . " AND (p.raiz IS NOT NULL OR p.opcao='adicionar' OR p.modulo IN ('dashboard','perfil-usuario'))"
        . " ORDER BY p.nome, p.id");
    $catalogo = [];
    $permissoes = [];
    foreach((array)$paginas as $pagina){
        $modulo = $pagina['modulo'];
        if(!isset($permissoes[$modulo])) $permissoes[$modulo] = gestor_permissao_modulo(false, $modulo);
        if(!$permissoes[$modulo] || !admin_topbar_caminho_seguro($pagina['caminho'])) continue;
        // Favoritos são navegação, nunca gatilhos de alteração ou telas com identificador dinâmico.
        if(!in_array($pagina['opcao'], ['', null, 'listar', 'adicionar', 'inicio'], true)
            && !($modulo === 'perfil-usuario' && $pagina['caminho'] === 'perfil-usuario/')) continue;
        if($pagina['opcao'] === 'adicionar' && !gestor_acesso('adicionar', $modulo)) continue;
        $icone = (string)($pagina['icone_tailwind'] ?? '');
        $catalogo[$pagina['id']] = [
            'id' => $pagina['id'], 'nome' => $pagina['nome'],
            'url' => $_GESTOR['url-raiz'].ltrim($pagina['caminho'], '/'),
            'caminho' => $pagina['caminho'],
            'icone' => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $icone) ? $icone : 'star',
        ];
    }
    return $catalogo;
}

function admin_topbar_caminho_seguro($caminho){
    return is_string($caminho) && $caminho !== ''
        && !preg_match('/[\\\\?#<>@\[\]\x00-\x20]|(^|\/)\.\.?(\/|$)|:|^\/\//', $caminho);
}

function admin_topbar_favoritos($catalogo){
    global $_GESTOR;
    $ordem = banco_campo_existe('ordem', 'usuarios_topbar_favoritos') ? 'ordem ASC, ' : '';
    $linhas = banco_sql('SELECT pagina_id FROM usuarios_topbar_favoritos WHERE id_usuarios='
        .(int)$_GESTOR['usuario-id'].' ORDER BY '.$ordem.'id_usuarios_topbar_favoritos');
    $favoritos = [];
    foreach((array)$linhas as $linha){
        if(isset($catalogo[$linha['pagina_id']])) $favoritos[] = $catalogo[$linha['pagina_id']];
    }
    return $favoritos;
}

function admin_topbar_largura_conteudo($usuarioId){
    if(!banco_campo_existe('chave', 'usuarios_preferencias')
        || !banco_campo_existe('valor', 'usuarios_preferencias')) return 'normal';
    $preferencias = banco_sql("SELECT valor FROM usuarios_preferencias WHERE id_usuarios=".(int)$usuarioId
        ." AND chave='admin_content_width' LIMIT 1");
    $largura = (string)($preferencias[0]['valor'] ?? 'normal');
    return in_array($largura, ['normal', 'expanded', 'full'], true) ? $largura : 'normal';
}

function admin_topbar_renderizar(){
    global $_GESTOR;
    $usuario = gestor_usuario();
    if(empty($usuario['id_usuarios'])) return '';
    $html = gestor_componente(['id' => 'admin-topbar-tailwind']);
    $catalogo = admin_topbar_catalogo();
    $disponivel = banco_campo_existe('pagina_id', 'usuarios_topbar_favoritos');
    $favoritos = $disponivel ? admin_topbar_favoritos($catalogo) : [];
    $atual = null;
    $caminho = rtrim((string)($_GESTOR['caminho-total'] ?? ''), '/').'/';
    foreach($catalogo as $item){
        if(ltrim($item['caminho'], '/') === ltrim($caminho, '/')) $atual = $item['id'];
    }
    $html = strtr($html, [
        '#topbar-nome#' => admin_topbar_escape($usuario['nome'] ?? ''),
        '#topbar-email#' => admin_topbar_escape($usuario['email'] ?? ''),
        '#topbar-perfil#' => admin_topbar_escape($usuario['perfil_nome'] ?? ''),
    ]);
    $estado = [
        'catalogo' => array_values($catalogo), 'favoritos' => $favoritos,
        'atual' => $atual, 'disponivel' => $disponivel,
        'larguraConteudo' => admin_topbar_largura_conteudo((int)$usuario['id_usuarios']),
    ];
    $html = str_replace('#topbar-state#', admin_topbar_escape(json_encode($estado)), $html);
    gestor_pagina_javascript_incluir(recursos_tag_js('global/admin-topbar.js', gestor_asset_version()));
    return $html;
}

function admin_topbar_ajax(){
    global $_GESTOR;
    gestor_incluir_biblioteca('interface');
    interface_ajax_iniciar();
    $erro = function($codigo){
        global $_GESTOR;
        http_response_code($codigo);
        $_GESTOR['ajax-json'] = ['status' => 'Erro', 'message' => gestor_variaveis(['id' => 'topbar-save-error'])];
    };
    if(empty($_GESTOR['usuario-id'])){ $erro(401); return; }
    if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'){ $erro(405); return; }
    if($_GESTOR['ajax-opcao'] === 'admin-topbar-largura'){
        $usuarioId = (int)$_GESTOR['usuario-id'];
        $largura = $_POST['largura'] ?? null;
        if(!is_string($largura) || !in_array($largura, ['normal', 'expanded', 'full'], true)){
            $erro(400); return;
        }
        if(!banco_campo_existe('chave', 'usuarios_preferencias')
            || !banco_campo_existe('valor', 'usuarios_preferencias')){ $erro(503); return; }
        $larguraSql = banco_escape_field($largura);
        $ok = banco_query("INSERT INTO usuarios_preferencias (id_usuarios, chave, valor) VALUES ("
            .$usuarioId.", 'admin_content_width', '".$larguraSql."') ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
        if($ok === false){ $erro(500); return; }
        $_GESTOR['ajax-json'] = ['status' => 'Ok', 'data' => ['larguraConteudo' => $largura]];
        interface_ajax_finalizar();
        return;
    }
    if(!banco_campo_existe('pagina_id', 'usuarios_topbar_favoritos')){ $erro(503); return; }
    $catalogo = admin_topbar_catalogo();
    $acao = $_GESTOR['ajax-opcao'];
    $usuarioId = (int)$_GESTOR['usuario-id'];
    if($acao === 'admin-topbar-ordenar'){
        if(!banco_campo_existe('ordem', 'usuarios_topbar_favoritos')){ $erro(503); return; }
        $paginaIds = json_decode((string)($_POST['paginaIds'] ?? ''), true);
        if(!is_array($paginaIds)){ $erro(400); return; }
        $favoritosAtuais = array_column(admin_topbar_favoritos($catalogo), 'id');
        $idsUnicos = array_unique($paginaIds);
        if(count($paginaIds) !== count($favoritosAtuais) || count($idsUnicos) !== count($paginaIds)
            || array_filter($paginaIds, function($id) use ($catalogo){ return !is_string($id) || !isset($catalogo[$id]); })){
            $erro(400); return;
        }
        $idsEsperados = $favoritosAtuais;
        $idsRecebidos = $paginaIds;
        sort($idsEsperados, SORT_STRING);
        sort($idsRecebidos, SORT_STRING);
        if($idsEsperados !== $idsRecebidos){ $erro(400); return; }
        if($paginaIds){
            $casos = [];
            $idsSql = [];
            foreach($paginaIds as $indice => $id){
                $idSql = banco_escape_field($id);
                $casos[] = "WHEN '".$idSql."' THEN ".($indice + 1);
                $idsSql[] = "'".$idSql."'";
            }
            $ok = banco_query('UPDATE usuarios_topbar_favoritos SET ordem = CASE pagina_id '
                .implode(' ', $casos).' ELSE ordem END WHERE id_usuarios='.$usuarioId
                .' AND pagina_id IN ('.implode(',', $idsSql).')');
        } else {
            $ok = true;
        }
    } else {
        $id = $_POST['paginaId'] ?? null;
        if(!in_array($acao, ['admin-topbar-adicionar', 'admin-topbar-remover'], true)
            || !is_string($id) || $id === '' || strlen($id) > 255){ $erro(400); return; }
        $paginaId = banco_escape_field($id);
        if($acao === 'admin-topbar-adicionar'){
            if(!isset($catalogo[$id])){ $erro(403); return; }
            if(banco_campo_existe('ordem', 'usuarios_topbar_favoritos')){
                $proximaOrdem = banco_sql('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima_ordem FROM usuarios_topbar_favoritos WHERE id_usuarios='.$usuarioId);
                $ordem = (int)($proximaOrdem[0]['proxima_ordem'] ?? 1);
                $ok = banco_query("INSERT INTO usuarios_topbar_favoritos (id_usuarios, pagina_id, ordem) VALUES ("
                    .$usuarioId.", '".$paginaId."', ".$ordem.") ON DUPLICATE KEY UPDATE pagina_id=VALUES(pagina_id)");
            } else {
                $ok = banco_query("INSERT INTO usuarios_topbar_favoritos (id_usuarios, pagina_id) VALUES ("
                    .$usuarioId.", '".$paginaId."') ON DUPLICATE KEY UPDATE pagina_id=VALUES(pagina_id)");
            }
        } else {
            $ok = banco_query("DELETE FROM usuarios_topbar_favoritos WHERE id_usuarios=".$usuarioId." AND pagina_id='".$paginaId."'");
        }
    }
    if($ok === false){ $erro(500); return; }
    $_GESTOR['ajax-json'] = ['status' => 'Ok', 'data' => ['favoritos' => admin_topbar_favoritos($catalogo)]];
    interface_ajax_finalizar();
}
