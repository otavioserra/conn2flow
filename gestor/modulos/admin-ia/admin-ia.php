<?php

global $_GESTOR;

$_GESTOR['modulo-id'] = 'admin-ia';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-ia.json'), true);

// ===== Incluir bibliotecas necessárias

gestor_incluir_biblioteca('interface');
gestor_incluir_biblioteca('autenticacao');

// ===== Interfaces Auxiliares

/** Nome do provedor para a tela, pelo tipo guardado no servidor. */
function admin_ia_tipo_rotulo($tipo){
    $ids = Array(
        'gemini' => 'ui-provider-gemini',
        'anthropic' => 'ui-provider-anthropic',
        'openai' => 'ui-provider-openai',
        'openai-compativel' => 'ui-provider-compatible',
    );

    return isset($ids[$tipo]) ? gestor_variaveis(Array('modulo' => 'admin-ia','id' => $ids[$tipo])) : (string)$tipo;
}

/** Padrões de cada provedor para a tela mostrar como sugestão nos campos (atributo HTML). */
function admin_ia_provedores_json(){
    gestor_incluir_biblioteca('ia-provedores');

    $saida = Array();
    foreach(ia_provedores() as $tipo => $dados){
        $saida[$tipo] = Array(
            'url_base' => $dados['url_base'],
            'modelo' => $dados['modelo'],
            'modelo_imagem' => $dados['modelo_imagem'],
        );
    }

    return htmlspecialchars((string)json_encode($saida, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
}

/**
 * Tipo, endereço base e modelos vindos do formulário, conferidos pelo provedor.
 * Devolve os campos prontos para gravar, ou `null` com a mensagem em `$erro`.
 */
function admin_ia_campos_provedor(&$erro){
    gestor_incluir_biblioteca('ia-provedores');

    $mensagem = function($id){ return gestor_variaveis(Array('modulo' => 'admin-ia','id' => $id)); };
    $tipo = (string)($_REQUEST['tipo'] ?? '');
    $dados = ia_provedor_dados($tipo);
    if(!$dados){
        $erro = $mensagem('msg-type-invalid');
        return null;
    }

    $campos = Array('tipo' => $tipo);
    foreach(Array('url_base','modelo','modelo_imagem') as $campo){
        $bruto = trim((string)($_REQUEST[$campo] ?? ''));
        $valor = $campo === 'url_base' ? ia_provedor_url_base_normalizar($bruto) : ia_provedor_modelo_normalizar($bruto);
        if($bruto !== '' && $valor === ''){
            $erro = $mensagem($campo === 'url_base' ? 'msg-base-url-invalid' : 'msg-model-invalid');
            return null;
        }
        $campos[$campo] = $valor;
    }

    // Provedor sem endereço ou sem modelo padrão precisa que o cadastro informe.
    if($dados['url_base'] === '' && $campos['url_base'] === ''){
        $erro = $mensagem('msg-base-url-required');
        return null;
    }
    if($dados['modelo'] === '' && $campos['modelo'] === ''){
        $erro = $mensagem('msg-model-required');
        return null;
    }

    return $campos;
}

function admin_ia_listar(){
    global $_GESTOR;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Inclusão Módulo JS
    gestor_pagina_javascript_incluir();
    
    // ===== Buscar servidores IA
    
    $servidores = banco_select([
        'tabela' => 'servidores_ia',
        'campos' => '*',
        'extra' => 'ORDER BY data_criacao DESC'
    ]);
    
    if(!is_array($servidores)){
        $servidores = [];
    }
    
    // ===== Verificar se há servidores
    
    if(is_array($servidores) && count($servidores) > 0){
        // ===== Pegar todas as células do template `com-servidores`
        $cel_nome = 'tipo-gemini';$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
        $cel_nome = 'tipo-outro';$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
        $cel_nome = 'status-ativo';$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
        $cel_nome = 'status-inativo';$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
        $cel_nome = 'servidores';$cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
        
        // ===== Processar servidores
        
        foreach($servidores as $servidor){
            $cel_servidores = $cel['servidores'];

            $cel_servidores = modelo_var_troca_tudo($cel_servidores,'#id#',$servidor['id_servidores_ia']);
            $cel_servidores = modelo_var_troca($cel_servidores,'#nome#',$servidor['nome']);
            $cel_servidores = modelo_var_troca($cel_servidores,'#tipo#',$servidor['tipo']);
            $cel_servidores = modelo_var_troca($cel_servidores,'#padrao#',($servidor['padrao'] == '1' ? '<span class="c2fc-selo c2fc-selo-ativo">'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-positive-label')).'</span>' : '<span class="c2fc-selo c2fc-selo-inativo">'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-negative-label')).'</span>'));
            $cel_servidores = modelo_var_troca($cel_servidores,'#status#',$servidor['status'] == 'A' ? 'Ativo' : 'Inativo');
            $cel_servidores = modelo_var_troca($cel_servidores,'#data-criacao#',date('d/m/Y H:i', strtotime($servidor['data_criacao'])));
            
            // ===== Processar células condicionais de tipo
            if($servidor['tipo'] == 'gemini'){
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-gemini -->',$cel['tipo-gemini']);
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-outro -->','');
            } else {
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-gemini -->','');
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-outro -->',modelo_var_troca($cel['tipo-outro'],'#tipo#',htmlspecialchars(admin_ia_tipo_rotulo($servidor['tipo']), ENT_QUOTES, 'UTF-8')));
            }
            
            // ===== Processar células condicionais de status
            if($servidor['status'] == 'A'){
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- status-ativo -->',$cel['status-ativo']);
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- status-inativo -->','');
            } else {
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- status-ativo -->','');
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- status-inativo -->',$cel['status-inativo']);
            }

            $_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- servidores -->',$cel_servidores);
        }
        
        // ===== Remover célula sem-servidores (já que há servidores)
        $cel_nome = 'sem-servidores';$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
    } else {
        // ===== Remover tabela de `com-servidores` e só mostrar célula `sem-servidores`
        $cel_nome = 'com-servidores';$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
    }
}

function admin_ia_adicionar(){
    global $_GESTOR;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Inclusão Módulo JS
    gestor_pagina_javascript_incluir();
    
    // ===== Sugestões de endereço e modelo de cada provedor

    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[provedores-json]]', admin_ia_provedores_json());
}

function admin_ia_editar(){
    global $_GESTOR;
    global $_CONFIG;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Validação do ID
    
    $id = $_REQUEST['id'] ?? null;
    if(!$id){
        gestor_redirecionar('admin-ia/listar/');
    }
    
    // ===== Buscar servidor IA
    
    $servidor = banco_select([
        'tabela' => 'servidores_ia',
        'campos' => '*',
        'extra' => 'WHERE id_servidores_ia = ' . (int)$id,
        'unico' => true
    ]);
    
    if(!$servidor){
        gestor_redirecionar('admin-ia/listar/');
    }
    
    // ===== Inclusão Módulo JS
    gestor_pagina_javascript_incluir();
    
    // ===== Abrir chave privada e a senha da chave
    
    $keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
    
    $fp = fopen($keyPrivatePath,"r");
    $keyPrivateString = fread($fp,8192);
    fclose($fp);
    
    $chavePrivadaSenha = $_CONFIG['openssl-password'];
    
    // ===== Descriptografar chave API para mascarar
    
    $chave_api_descriptografada = autenticacao_decriptar_chave_privada(Array(
        'criptografia' => $servidor['chave_api'],
        'chavePrivada' => $keyPrivateString,
        'chavePrivadaSenha' => $chavePrivadaSenha,
    ));
    
    $chave_api_mascarada = str_repeat('*', strlen($chave_api_descriptografada));
    
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[id]]', $servidor['id_servidores_ia']);
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[nome]]', $servidor['nome']);
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[tipo]]', $servidor['tipo']);
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[padrao]]', $servidor['padrao'] == '1' ? 'checked="checked"' : '');
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[chave-api]]', $chave_api_mascarada);

    // ===== Provedor do servidor: tipo marcado, endereço base e modelos

    foreach(Array('gemini' => 'sel-gemini','anthropic' => 'sel-anthropic','openai' => 'sel-openai','openai-compativel' => 'sel-compativel') as $tipo => $marcador){
        $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[['.$marcador.']]', $servidor['tipo'] == $tipo ? 'selected' : '');
    }
    foreach(Array('url_base' => 'url-base','modelo' => 'modelo','modelo_imagem' => 'modelo-imagem') as $coluna => $marcador){
        $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[['.$marcador.']]', htmlspecialchars((string)($servidor[$coluna] ?? ''), ENT_QUOTES, 'UTF-8'));
    }
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[provedores-json]]', admin_ia_provedores_json());

    // ===== Processar células condicionais de status
    if($servidor['status'] == 'A'){
        $cel_nome = 'ativar-cel';$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
    } else {
        $cel_nome = 'desativar-cel';$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
    }

    // ===== Modelos Disponíveis Globalmente (ia_user_models com id_usuarios=0)
    $modelosCheckboxesHtml = '';
    $modelosDataPath = $_GESTOR['ROOT_PATH'] . '/modulos/admin-ia/gemini/' . ($_GESTOR['linguagem-codigo'] ?? 'pt-br') . '/data.json';
    if (file_exists($modelosDataPath)) {
        $modelosData = json_decode(file_get_contents($modelosDataPath), true);
        if (!empty($modelosData['models']) && is_array($modelosData['models'])) {
            // Buscar configuração global atual (id_usuarios=0)
            $globalModels = [];
            try {
                $globalModelRows = banco_select([
                    'tabela' => 'ia_user_models',
                    'campos' => ['model_name', 'enabled'],
                    'extra'  => "WHERE id_usuarios = '0'",
                ]);
                if ($globalModelRows) {
                    foreach ($globalModelRows as $gm) {
                        $globalModels[$gm['model_name']] = (int)$gm['enabled'];
                    }
                }
            } catch (Exception $e) {
                // Tabela pode não existir
            }

            foreach ($modelosData['models'] as $modelo) {
                $modelName = htmlspecialchars($modelo['name'], ENT_QUOTES, 'UTF-8');
                $displayName = htmlspecialchars($modelo['displayName'], ENT_QUOTES, 'UTF-8');
                $description = htmlspecialchars($modelo['description'], ENT_QUOTES, 'UTF-8');
                // Se não há registros globais, todos habilitados por padrão
                $isEnabled = empty($globalModels) ? true : (($globalModels[$modelo['name']] ?? 0) === 1);
                $checked = $isEnabled ? 'checked="checked"' : '';

                $modelosCheckboxesHtml .= '<label class="c2fc-chave">'
                    . '<input type="checkbox" name="global_model[]" value="' . $modelName . '" ' . $checked . '>'
                    . '<span class="c2fc-chave-trilho"></span>'
                    . '<span class="c2fc-chave-rotulo"><strong>' . $displayName . '</strong> <small>(' . $modelName . ')</small>'
                    . '<br><small>' . $description . '</small></span>'
                    . '</label>';
            }
        }
    }
    if (empty($modelosCheckboxesHtml)) {
        $modelosCheckboxesHtml = '<p>'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'ui-models-empty')).'</p>';
    }
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '[[global_models_checkboxes]]', $modelosCheckboxesHtml);
}

// ===== Interfaces Principais

function admin_ia_raiz(){
    global $_GESTOR;

    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    // ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();

    // ===== Redirecionar para listar
    
    gestor_redirecionar('admin-ia/listar/');
}

function admin_ia_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function admin_ia_ajax_salvar(){
    global $_GESTOR;
    global $_CONFIG;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Validação dos dados
    
    $nome = $_REQUEST['nome'] ?? '';
    $tipo = $_REQUEST['tipo'] ?? '';
    $chave_api = $_REQUEST['chave_api'] ?? '';
    $padrao = isset($_REQUEST['padrao']) && $_REQUEST['padrao'] == 'on' ? 1 : 0;
    
    if(empty($nome) || empty($tipo) || empty($chave_api)){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'Nome, tipo e chave API são obrigatórios.'
        );
        return;
    }

    // ===== Provedor: tipo conhecido, endereço base e modelos

    $erro_provedor = '';
    $campos_provedor = admin_ia_campos_provedor($erro_provedor);
    if(!$campos_provedor){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => $erro_provedor
        );
        return;
    }

    // ===== Verificar se há outro servidor padrão para o mesmo tipo
    
    if($_REQUEST['padrao'] == 'on'){
        banco_update_campo('padrao', 0);
        banco_update_executar('servidores_ia', "WHERE tipo = '" . banco_escape_field($_REQUEST['tipo']) . "'");
    }
    
    // ===== Abrir chave privada e a senha da chave
    
    $keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
    
    $fp = fopen($keyPrivatePath,"r");
    $keyPrivateString = fread($fp,8192);
    fclose($fp);
    
    $chavePrivadaSenha = $_CONFIG['openssl-password'];
    
    // ===== Encriptar chave API
    
    $chave_api_encriptada = autenticacao_encriptar_chave_privada(Array(
        'valor' => $chave_api,
        'chavePrivada' => $keyPrivateString,
        'chavePrivadaSenha' => $chavePrivadaSenha,
    ));
    
    // ===== Salvar no banco
    
    banco_insert_name_campo('nome',$nome);
    banco_insert_name_campo('tipo',$tipo);
    banco_insert_name_campo('padrao',$padrao,true);
    banco_insert_name_campo('chave_api',$chave_api_encriptada);
    banco_insert_name_campo('url_base',$campos_provedor['url_base']);
    banco_insert_name_campo('modelo',$campos_provedor['modelo']);
    banco_insert_name_campo('modelo_imagem',$campos_provedor['modelo_imagem']);
    banco_insert_name_campo('status','A');
    
    banco_insert_name
    (
        banco_insert_name_campos(),
        "servidores_ia"
    );

    $id = banco_last_id();
    
    // ===== Dados de Retorno
    
    $_GESTOR['ajax-json'] = Array(
        'status' => 'success',
        'message' => 'Servidor IA adicionado com sucesso!',
        'id' => $id
    );
}

function admin_ia_ajax_editar(){
    global $_GESTOR;
    global $_CONFIG;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Validação do ID
    
    $id = $_REQUEST['id'] ?? null;
    if(!$id){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'ID do servidor não informado.'
        );
        return;
    }
    
    // ===== Validação dos dados
    
    $nome = $_REQUEST['nome'] ?? '';
    $tipo = $_REQUEST['tipo'] ?? '';
    $chave_api = $_REQUEST['chave_api'] ?? '';
    $padrao = isset($_REQUEST['padrao']) && $_REQUEST['padrao'] == 'on' ? 1 : 0;
    
    if(empty($nome) || empty($tipo)){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'Nome e tipo são obrigatórios.'
        );
        return;
    }

    // ===== Provedor: tipo conhecido, endereço base e modelos

    $erro_provedor = '';
    $campos_provedor = admin_ia_campos_provedor($erro_provedor);
    if(!$campos_provedor){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => $erro_provedor
        );
        return;
    }

    // ===== Verificar se há outro servidor padrão para o mesmo tipo
		
    if($_REQUEST['padrao'] == 'on' && banco_select_campos_antes('padrao') != '1'){
        banco_update_campo('padrao', '0');
        banco_update_executar('servidores_ia', "WHERE tipo = '" . banco_escape_field($_REQUEST['tipo']) . "'");
    }
    
    // ===== Preparar dados para atualização
    
    banco_update_campo('nome',$nome);
    banco_update_campo('tipo',$tipo);
    banco_update_campo('padrao',$padrao,true);
    banco_update_campo('url_base',$campos_provedor['url_base']);
    banco_update_campo('modelo',$campos_provedor['modelo']);
    banco_update_campo('modelo_imagem',$campos_provedor['modelo_imagem']);
    
    // ===== Encriptar chave API apenas se foi alterada
    
    if(!empty($chave_api) && strpos($chave_api, '***') === false){
        // ===== Abrir chave privada e a senha da chave
        
        $keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
        
        $fp = fopen($keyPrivatePath,"r");
        $keyPrivateString = fread($fp,8192);
        fclose($fp);
        
        $chavePrivadaSenha = $_CONFIG['openssl-password'];
        
        $chave_api_encriptada = autenticacao_encriptar_chave_privada(Array(
            'valor' => $chave_api,
            'chavePrivada' => $keyPrivateString,
            'chavePrivadaSenha' => $chavePrivadaSenha,
        ));
        
        banco_update_campo('chave_api',$chave_api_encriptada);
    }
    
    // ===== Atualizar no banco
    
    banco_update_executar('servidores_ia',"WHERE id_servidores_ia = '" . banco_escape_field($id) . "'");
    
    // ===== Dados de Retorno
    
    $_GESTOR['ajax-json'] = Array(
        'status' => 'success',
        'message' => 'Servidor IA atualizado com sucesso!'
    );
}

function admin_ia_ajax_testar_conexao(){
    global $_GESTOR;
    global $_CONFIG;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Validação do ID
    
    $id = $_REQUEST['id'] ?? null;
    if(!$id){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'ID do servidor não informado.'
        );
        return;
    }
    
    // ===== Buscar servidor IA
    
    $servidor = banco_select([
        'tabela' => 'servidores_ia',
        'campos' => '*',
        'extra' => 'WHERE id_servidores_ia = ' . (int)$id,
        'unico' => true
    ]);
    
    if(!$servidor){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'Servidor IA não encontrado.'
        );
        return;
    }
    
    // ===== Testar conexão pelo provedor do servidor (a chave é decifrada pela biblioteca e vai em cabeçalho)

    gestor_incluir_biblioteca('ia-provedores');

    $resultado = false;
    $mensagem_erro = '';
    $tempo_inicio = microtime(true);

    $servidor_ia = ia_provedor_servidor_do_banco($servidor);
    if(!$servidor_ia){
        $mensagem_erro = gestor_variaveis(Array('modulo' => 'admin-ia','id' => 'msg-key-missing'));
    } else {
        $teste = ia_provedor_testar($servidor_ia);
        $resultado = $teste['ok'];
        if(!$resultado) $mensagem_erro = $teste['mensagem'];
    }

    $tempo_resposta = round(microtime(true) - $tempo_inicio, 2);

    // ===== Registrar log do teste
    
    banco_insert_name_campo('id_servidores_ia',(int)$id);
    banco_insert_name_campo('sucesso',$resultado ? 1 : 0);
    banco_insert_name_campo('mensagem_erro',$mensagem_erro);
    banco_insert_name_campo('tempo_resposta',$tempo_resposta);
    
    banco_insert_name
    (
        banco_insert_name_campos(),
        "logs_testes_ia"
    );
    
    // ===== Dados de Retorno
    
    $_GESTOR['ajax-json'] = Array(
        'status' => $resultado ? 'success' : 'error',
        'message' => $resultado ? 'Conexão testada com sucesso!' : 'Erro na conexão: ' . $mensagem_erro,
        'tempo_resposta' => $tempo_resposta
    );
}

function admin_ia_ajax_historico_testes(){
    global $_GESTOR;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Validação do ID
    
    $id = $_REQUEST['id'] ?? null;
    if(!$id){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'ID do servidor não informado.'
        );
        return;
    }
    
    // ===== Buscar histórico de testes
    
    $historico = banco_select([
        'tabela' => 'logs_testes_ia',
        'campos' => '*',
        'extra' => 'WHERE id_servidores_ia = ' . (int)$id . ' ORDER BY data_teste DESC LIMIT 20'
    ]);
    
    if(!is_array($historico)){
        $historico = [];
    }
    
    // ===== Formatar dados para retorno
    
    $dados_formatados = [];
    foreach($historico as $teste){
        $dados_formatados[] = [
            'data' => date('d/m/Y H:i:s', strtotime($teste['data_teste'])),
            'sucesso' => $teste['sucesso'] == 1,
            'mensagem_erro' => $teste['mensagem_erro'] ?: '',
            'tempo_resposta' => $teste['tempo_resposta'] ? number_format($teste['tempo_resposta'], 2) . 's' : '-'
        ];
    }
    
    // ===== Dados de Retorno
    
    $_GESTOR['ajax-json'] = Array(
        'status' => 'success',
        'historico' => $dados_formatados
    );
}

function admin_ia_ajax_excluir(){
    global $_GESTOR;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    // ===== Validação do ID
    
    $id = $_REQUEST['id'] ?? null;
    if(!$id){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'ID do servidor não informado.'
        );
        return;
    }
    
    // ===== Verificar se servidor existe
    
    $servidor = banco_select([
        'tabela' => 'servidores_ia',
        'campos' => '*',
        'extra' => 'WHERE id_servidores_ia = ' . (int)$id,
        'unico' => true
    ]);
    
    if(!$servidor){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'Servidor IA não encontrado.'
        );
        return;
    }
    
    // ===== Excluir logs de testes relacionados
    
    banco_delete('logs_testes_ia', "WHERE id_servidores_ia = '" . banco_escape_field($id) . "'");
    
    // ===== Excluir servidor
    
    banco_delete('servidores_ia', "WHERE id_servidores_ia = '" . banco_escape_field($id) . "'");
    
    // ===== Dados de Retorno
    
    $_GESTOR['ajax-json'] = Array(
        'status' => 'success',
        'message' => 'Servidor IA excluído com sucesso!'
    );
}

function admin_ia_ajax_ativar(){
    global $_GESTOR;

    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    // Param ID
    $id = $_REQUEST['id'] ?? null;

    if(!$id){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'ID do servidor não informado.'
        );
        return;
    }

    // Ativar no banco
    banco_update_campo('status', 'A');
    banco_update_executar('servidores_ia', "WHERE id_servidores_ia = '" . banco_escape_field($id) . "'");

    // ===== Dados de Retorno

    $_GESTOR['ajax-json'] = Array(
        'status' => 'success',
        'message' => 'Servidor IA ativado com sucesso!',
    );
}

function admin_ia_ajax_desativar(){
    global $_GESTOR;

    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    // Param ID
    $id = $_REQUEST['id'] ?? null;

    if(!$id){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'ID do servidor não informado.'
        );
        return;
    }

    // Desativar no banco
    banco_update_campo('status', 'I');
    banco_update_executar('servidores_ia', "WHERE id_servidores_ia = '" . banco_escape_field($id) . "'");

    // ===== Dados de Retorno

    $_GESTOR['ajax-json'] = Array(
        'status' => 'success',
        'message' => 'Servidor IA desativado com sucesso!',
    );
}

/**
 * AJAX: Salvar configuração global de modelos (ia_user_models com id_usuarios=0).
 */
function admin_ia_ajax_salvar_modelos_globais(){
    global $_GESTOR;

    $enabledModels = $_REQUEST['enabled_models'] ?? [];
    if (is_string($enabledModels)) {
        $enabledModels = json_decode($enabledModels, true) ?: [];
    }

    // Carregar todos os modelos do data.json
    $modelosDataPath = $_GESTOR['ROOT_PATH'] . '/modulos/admin-ia/gemini/' . ($_GESTOR['linguagem-codigo'] ?? 'pt-br') . '/data.json';
    if (!file_exists($modelosDataPath)) {
        $_GESTOR['ajax-json'] = ['status' => 'error', 'message' => 'Arquivo de modelos não encontrado.'];
        return;
    }

    $modelosData = json_decode(file_get_contents($modelosDataPath), true);
    if (empty($modelosData['models'])) {
        $_GESTOR['ajax-json'] = ['status' => 'error', 'message' => 'Nenhum modelo encontrado no arquivo.'];
        return;
    }

    try {
        // Remover registros globais existentes
        banco_query("DELETE FROM ia_user_models WHERE id_usuarios = '0'");

        // Inserir novos registros para cada modelo
        foreach ($modelosData['models'] as $modelo) {
            $modelName = banco_escape_field($modelo['name']);
            $enabled = in_array($modelo['name'], $enabledModels) ? 1 : 0;

            banco_insert_name_campo('id_usuarios', '0');
            banco_insert_name_campo('model_name', $modelName);
            banco_insert_name_campo('enabled', $enabled, true);
            banco_insert_name(banco_insert_name_campos(), 'ia_user_models');
        }

        $_GESTOR['ajax-json'] = [
            'status' => 'success',
            'message' => 'Configuração de modelos salva com sucesso!',
        ];
    } catch (Exception $e) {
        $_GESTOR['ajax-json'] = [
            'status' => 'error',
            'message' => 'Erro ao salvar: ' . $e->getMessage(),
        ];
    }
}

// ==== Start

function admin_ia_start(){
    global $_GESTOR;

    if($_GESTOR['ajax']){
        interface_ajax_iniciar();

        switch($_GESTOR['ajax-opcao']){
            case 'salvar': admin_ia_ajax_salvar(); break;
            case 'editar': admin_ia_ajax_editar(); break;
            case 'testar_conexao': admin_ia_ajax_testar_conexao(); break;
            case 'historico_testes': admin_ia_ajax_historico_testes(); break;
            case 'excluir': admin_ia_ajax_excluir(); break;
            case 'ativar': admin_ia_ajax_ativar(); break;
            case 'desativar': admin_ia_ajax_desativar(); break;
            case 'salvar_modelos_globais': admin_ia_ajax_salvar_modelos_globais(); break;
        }

        interface_ajax_finalizar();
    } else {
                $_GESTOR['tailwind-page-bundle'] = true;
        admin_ia_interfaces_padroes();

        interface_iniciar();

        switch($_GESTOR['opcao']){
            case 'raiz': admin_ia_raiz(); break;
            case 'listar-servidores':
                admin_ia_listar();
            break;
            case 'adicionar-servidor':
                admin_ia_adicionar();
            break;
            case 'editar-servidor':
                admin_ia_editar();
            break;
            default: admin_ia_raiz(); break;
        }

        interface_finalizar();
    }
}

admin_ia_start();

?>
