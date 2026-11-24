# Controller: gravação, edição e histórico — receita completa

Subskill de `c2f-module-crud-scaffolding`. Leia ao implementar `_adicionar()`, `_editar()` ou `_clonar()` de um módulo. As regras críticas (validação no servidor, chave natural, snapshot e histórico) também estão na skill-mãe.

## 3.2. Função `_adicionar()`
```php
function meu_modulo_adicionar(){
    global $_GESTOR;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // 1. Gravar registro no Banco
    if(isset($_GESTOR['adicionar-banco'])){
        $usuario = gestor_usuario();
        
        // Validação obrigatória
        interface_validacao_campos_obrigatorios(Array(
            'campos' => Array(
                Array(
                    'regra' => 'texto-obrigatorio',
                    'campo' => 'nome',
                    'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'form-name-label')),
                )
            )
        ));
        
        // Geração da Chave Natural
        $id = banco_identificador(Array(
            'id' => banco_escape_field($_REQUEST["nome"]),
            'tabela' => Array(
                'nome' => $modulo['tabela']['nome'],
                'campo' => $modulo['tabela']['id'],
                'id_nome' => $modulo['tabela']['id_numerico'],
                'where' => "language='".$_GESTOR['linguagem-codigo']."'",
            ),
        ));
        
        // Montagem dos Campos
        $campos = null;
        $campo_sem_aspas_simples = false;
        
        $campos[] = Array("id_usuarios", $usuario['id_usuarios'], $campo_sem_aspas_simples);
        $campos[] = Array("nome", banco_escape_field($_REQUEST["nome"]), $campo_sem_aspas_simples);
        $campos[] = Array("id", $id, $campo_sem_aspas_simples);
        
        // Campos de Controle Padrão
        $campos[] = Array('language', $_GESTOR['linguagem-codigo'], $campo_sem_aspas_simples);
        $campos[] = Array($modulo['tabela']['status'], 'A', $campo_sem_aspas_simples);
        $campos[] = Array($modulo['tabela']['versao'], '1', $campo_sem_aspas_simples);
        $campos[] = Array($modulo['tabela']['data_criacao'], 'NOW()', true);
        $campos[] = Array($modulo['tabela']['data_modificacao'], 'NOW()', true);
        
        banco_insert_name($campos, $modulo['tabela']['nome']);
        
        gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
    }
    
    // 2. Inclusão de JS e Validações no Frontend
    gestor_pagina_javascript_incluir();
    
    $_GESTOR['interface']['adicionar']['finalizar'] = Array(
        'formulario' => Array(
            'validacao' => Array(
                Array(
                    'regra' => 'texto-obrigatorio',
                    'campo' => 'nome',
                    'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'form-name-label')),
                )
            )
        )
    );
}
```

## 3.3. Função `_editar()`
```php
function meu_modulo_editar(){
    global $_GESTOR;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    $id = $_GESTOR['modulo-registro-id'];
    
    $camposBanco = Array('nome');
    $camposBancoPadrao = Array(
        $modulo['tabela']['status'],
        $modulo['tabela']['versao'],
        $modulo['tabela']['data_criacao'],
        $modulo['tabela']['data_modificacao'],
    );
    $camposBancoEditar = array_merge($camposBanco, $camposBancoPadrao);
    $camposBancoAntes = $camposBanco;
    
    // 1. Gravar Atualizações no Banco
    if(isset($_GESTOR['atualizar-banco'])){
        // Snapshot de dados anteriores
        if(!banco_select_campos_antes_iniciar(
            banco_campos_virgulas($camposBancoAntes),
            $modulo['tabela']['nome'],
            "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'"
        )){
            interface_alerta(Array(
                'redirect' => true,
                'msg' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'alert-database-field-before-error'))
            ));
            gestor_redirecionar_raiz();
        }
        
        // Validação obrigatória
        interface_validacao_campos_obrigatorios(Array(
            'campos' => Array(
                Array(
                    'regra' => 'texto-obrigatorio',
                    'campo' => 'nome',
                    'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'form-name-label')),
                )
            )
        ));
        
        $editar = Array(
            'tabela' => $modulo['tabela']['nome'],
            'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'",
        );
        $alteracoes = [];
        
        // Comparação e Atualização de Campos
        if(banco_select_campos_antes('nome') != (isset($_REQUEST['nome']) ? $_REQUEST['nome'] : NULL)){
            $editar['dados'][] = "nome='" . banco_escape_field($_REQUEST['nome']) . "'";
            if(!isset($_REQUEST['_gestor-nao-alterar-id'])){ $alterar_id = true; }
            $alteracoes[] = Array('campo' => 'form-name-label', 'valor_antes' => banco_select_campos_antes('nome'), 'valor_depois' => banco_escape_field($_REQUEST['nome']));
        }
        
        // Se mudou o nome, atualizar slug identificador natural
        if(isset($alterar_id)){
            $id_novo = banco_identificador(Array(
                'id' => banco_escape_field($_REQUEST["nome"]),
                'tabela' => Array(
                    'nome' => $modulo['tabela']['nome'],
                    'campo' => $modulo['tabela']['id'],
                    'id_nome' => $modulo['tabela']['id_numerico'],
                    'id_valor' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
                    'where' => "language='".$_GESTOR['linguagem-codigo']."'",
                ),
            ));
            $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id, 'valor_depois' => $id_novo);
            $editar['dados'][] = $modulo['tabela']['id']."='" . $id_novo . "'";
            $_GESTOR['modulo-registro-id'] = $id_novo;
        }
        
        // Executar Update e Salvar Histórico
        if(isset($editar['dados'])){
            $editar['dados'][] = $modulo['tabela']['versao']." = ".$modulo['tabela']['versao']." + 1";
            $editar['dados'][] = $modulo['tabela']['data_modificacao']."=NOW()";
            
            banco_update(banco_campos_virgulas($editar['dados']), $editar['tabela'], $editar['extra']);
            interface_historico_incluir(Array('alteracoes' => $alteracoes));
        }
        
        gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
    }
    
    // 2. Renderização da Interface
    gestor_pagina_javascript_incluir();
    
    $retorno_bd = banco_select_editar(
        banco_campos_virgulas($camposBancoEditar),
        $modulo['tabela']['nome'],
        "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'"
    );
    
    if($_GESTOR['banco-resultado']){
        $nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
        $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#nome#', $nome);
        
        // Metadados Laterais
        $status_atual = $retorno_bd[$modulo['tabela']['status']];
        if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
        if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
        if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
        if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($status_atual == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>')); }
    } else {
        gestor_redirecionar_raiz();
    }
    
    // Finalizar Interface Editar
    $_GESTOR['interface']['editar']['finalizar'] = Array(
        'id' => $id,
        'metaDados' => $metaDados,
        'banco' => Array(
            'nome' => $modulo['tabela']['nome'],
            'id' => $modulo['tabela']['id'],
            'status' => $modulo['tabela']['status'],
        ),
        'botoes' => Array(
            'adicionar' => Array('url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/', 'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')), 'icon' => 'plus circle', 'cor' => 'blue'),
            'clonar' => Array('url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/clonar/?'.$modulo['tabela']['id'].'='.$id, 'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-clone')), 'icon' => 'clone', 'cor' => 'teal'),
            'status' => Array('url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A').'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id), 'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active'))), 'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash'), 'cor' => ($status_atual == 'A' ? 'green' : 'brown')),
            'excluir' => Array('url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id, 'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-delete')), 'icon' => 'trash alternate', 'cor' => 'red'),
        ),
        'formulario' => Array(
            'validacao' => Array(
                Array('regra' => 'texto-obrigatorio', 'campo' => 'nome', 'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label'))),
            )
        )
    );
}
```

## 3.4. Função `_clonar()`
Segue a mesma lógica de `_adicionar()`, recuperando os valores do registro original via `banco_select_editar()` e populando os campos no formulário.
