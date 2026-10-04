<?php

/** Listagem Tailwind (req-220): mesmo contrato AJAX e allowlist do servidor. */
function interface_listar_tailwind_configurar($params, $anterior = Array()){
    $banco = $params['banco'];
    $columns = Array(Array('data' => INTERFACE_COLUNA_ACOES, 'orderable' => false, 'searchable' => false));
    $order = Array();
    $orderBanco = Array();
    foreach($params['tabela']['colunas'] as $coluna){
        $columns[] = Array(
            'data' => $coluna['id'], 'name' => $coluna['nome'],
            'orderable' => !isset($coluna['nao_ordenar']) && interface_listar_coluna_segura($coluna['id']),
            'searchable' => !isset($coluna['nao_procurar']),
            'visible' => !isset($coluna['nao_visivel']),
            // Apenas HTML produzido por um formatador declarado no servidor pode ser interpretado.
            'html' => isset($coluna['formatar']) && is_array($coluna['formatar']),
        );
        if(isset($coluna['ordenar']) && end($columns)['orderable']){
            $dir = $coluna['ordenar'] === 'asc' ? 'asc' : 'desc';
            $order[] = Array(count($columns) - 1, $dir);
            $orderBanco[] = $coluna['id'].' '.$dir;
        }
    }
    if(!$order){
        foreach($columns as $i => $coluna){
            if($coluna['orderable']){
                $order[] = Array($i, 'asc');
                $orderBanco[] = $coluna['data'].' asc';
                break;
            }
        }
    }
    $banco['order'] = $orderBanco ? ' ORDER BY '.implode(',', $orderBanco) : '';
    return Array(
        'banco' => $banco, 'tabela' => $params['tabela'], 'columns' => $columns,
        'columnsExtraSearch' => Array($banco['id']), 'order' => $order,
        'totalRegistros' => 0, 'registroInicial' => max(0, (int)($anterior['registroInicial'] ?? 0)),
        'registrosPorPagina' => in_array((int)($anterior['registrosPorPagina'] ?? 25), Array(10,25,50,100), true)
            ? (int)($anterior['registrosPorPagina'] ?? 25) : 25,
        'tailwind' => true,
    );
}

function interface_listar_tailwind_finalizar($params){
    global $_GESTOR;
    $chave = $_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-interface-'.$_GESTOR['usuario-id'];
    $interface = interface_listar_tailwind_configurar($params, gestor_sessao_variavel($chave) ?: Array());
    $banco = $interface['banco'];
    $registros = banco_select(Array(
        'tabela' => $banco['nome'], 'campos' => Array($banco['id']),
        'extra' => "WHERE ".$banco['status']."!='D'".(isset($banco['where']) ? ' AND '.$banco['where'] : ''),
    ));
    $interface['totalRegistros'] = count($registros ?: Array());
    if($interface['registroInicial'] >= $interface['totalRegistros']) $interface['registroInicial'] = 0;
    gestor_sessao_variavel($chave, $interface);
    $opcoes = $params['opcoes'] ?? Array();
    foreach($opcoes as &$opcao) $opcao['lucide'] = interface_botao_tailwind_icone($opcao['icon'] ?? '');
    unset($opcao);
    $_GESTOR['javascript-vars']['interface']['lista'] = Array(
        'url' => rtrim($_GESTOR['caminho-total'] ?? '', '/').'/',
        'id' => $banco['id'], 'acoesId' => INTERFACE_COLUNA_ACOES, 'status' => $banco['status'] ?? false,
        'columns' => $interface['columns'], 'order' => $interface['order'],
        'pageLength' => $interface['registrosPorPagina'], 'displayStart' => $interface['registroInicial'],
        'opcoes' => $opcoes,
    );
    $pagina = gestor_componente(Array('id' => interface_componente_variante('interface-listar')));
    $textos = Array(
        'busca' => 'list-tailwind-search', 'quantidade' => 'list-tailwind-length',
        'anterior' => 'list-tailwind-previous', 'proxima' => 'list-tailwind-next',
        'vazia' => 'list-tailwind-empty', 'carregando' => 'list-tailwind-loading',
        'erro' => 'list-tailwind-error', 'resumo' => 'list-tailwind-summary',
        'opcoes' => 'list-column-options', 'status' => 'field-status',
        'titulo-excluir' => 'delete-confirm-title', 'mensagem-excluir' => 'delete-confirm-menssage',
        'cancelar' => 'delete-confirm-button-cancel', 'confirmar' => 'delete-confirm-button-confirm',
    );
    foreach($textos as $marcador => $id){
        $pagina = modelo_var_troca($pagina, '#'.$marcador.'#', htmlspecialchars(gestor_variaveis(Array('modulo' => 'interface', 'id' => $id)), ENT_QUOTES, 'UTF-8'));
    }
    $pagina = modelo_var_troca($pagina, '#titulo#', htmlspecialchars($_GESTOR['pagina#titulo'], ENT_QUOTES, 'UTF-8'));
    $pagina = modelo_var_troca($pagina, '#botoes#', isset($params['botoes']) ? interface_botoes_cabecalho($params) : '');
    $pagina = modelo_var_troca($pagina, '#cabecalho#', $params['tabela']['cabecalho'] ?? '');
    $_GESTOR['pagina'] = ($_GESTOR['pagina'] ?? '').$pagina;
    interface_assets_incluir();
    $_GESTOR['javascript'][] = recursos_tag_js('interface/interface-listar-tailwind.js', $_GESTOR['biblioteca-interface']['versao']);
}
