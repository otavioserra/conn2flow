<?php

global $_GESTOR;

$_GESTOR['modulo-id'] = 'admin-ia';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-ia.json'), true);

// ===== Incluir bibliotecas necessárias

gestor_incluir_biblioteca('interface');
gestor_incluir_biblioteca('autenticacao');

// ===== Interfaces Auxiliares

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
            $cel_servidores = modelo_var_troca($cel_servidores,'#status#',$servidor['status'] == 'A' ? 'Ativo' : 'Inativo');
            $cel_servidores = modelo_var_troca($cel_servidores,'#data-criacao#',date('d/m/Y H:i', strtotime($servidor['data_criacao'])));
            
            // ===== Processar células condicionais de tipo
            if($servidor['tipo'] == 'gemini'){
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-gemini -->',$cel['tipo-gemini']);
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-outro -->','');
            } else {
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-gemini -->','');
                $cel_servidores = modelo_var_troca($cel_servidores,'<!-- tipo-outro -->',$cel['tipo-outro']);
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
    
    // ===== A página já está carregada em $_GESTOR['pagina'], não precisamos fazer nada especial
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
        'extra' => 'WHERE id_servidores_ia = ' . $id,
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
    
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '[[id]]', $servidor['id_servidores_ia']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '[[nome]]', $servidor['nome']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '[[tipo]]', $servidor['tipo']);
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '[[chave-api]]', $chave_api_mascarada);
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
    
    if(empty($nome) || empty($tipo) || empty($chave_api)){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'Nome, tipo e chave API são obrigatórios.'
        );
        return;
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
    banco_insert_name_campo('chave_api',$chave_api_encriptada);
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
    
    if(empty($nome) || empty($tipo)){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'Nome e tipo são obrigatórios.'
        );
        return;
    }
    
    // ===== Preparar dados para atualização
    
    banco_update_campo('nome',$nome);
    banco_update_campo('tipo',$tipo);
    
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
        'extra' => 'WHERE id_servidores_ia = ' . $id,
        'unico' => true
    ]);
    
    if(!$servidor){
        $_GESTOR['ajax-json'] = Array(
            'status' => 'error',
            'message' => 'Servidor IA não encontrado.'
        );
        return;
    }
    
    // ===== Abrir chave pública e a senha da chave

    $keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';

    $fp = fopen($keyPublicPath,"r");
    $keyPublicString = fread($fp,8192);
    fclose($fp);

    // ===== Descriptografar chave API
    
    $chave_api = autenticacao_decriptar_chave_publica(Array(
        'criptografia' => $servidor['chave_api'],
        'chavePublica' => $keyPublicString,
    ));

    // ===== Pegar dados do modelo do servidor

    $gemini = $modulo["apis"]["gemini"];

    $gemini['urlGenerateContent'] = modelo_var_troca_tudo($gemini['urlGenerateContent'],'{API_KEY}',$chave_api);
    $gemini['urlGenerateContent'] = modelo_var_troca_tudo($gemini['urlGenerateContent'],'{MODEL}',$gemini['defaultModel']);

    // ===== Testar conexão baseado no tipo
    
    $resultado = false;
    $mensagem_erro = '';
    $tempo_inicio = microtime(true);
    
    switch($servidor['tipo']){
        case 'gemini':
            $resultado = admin_ia_testar_gemini($gemini['urlGenerateContent'], $mensagem_erro);
            break;
        default:
            $mensagem_erro = 'Tipo de servidor não suportado.';
    }
    
    $tempo_resposta = round(microtime(true) - $tempo_inicio, 2);

    // ===== Registrar log do teste
    
    banco_insert_name_campo('id_servidores_ia',$id);
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
        'extra' => 'WHERE id_servidores_ia = ' . $id . ' ORDER BY data_teste DESC LIMIT 20'
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
        'extra' => 'WHERE id_servidores_ia = ' . $id,
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

function admin_ia_testar_gemini($url_api, &$mensagem_erro){
    // ===== Preparar requisição de teste
     
    $data = [
        'contents' => [[
            'parts' => [[
                'text' => 'Olá, isso é um teste de conexão. Responda apenas "OK".'
            ]]
        ]]
    ];
    
    $jsonData = json_encode($data);
    
    // ===== Fazer requisição cURL
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    curl_close($ch);
    
    // ===== Verificar resposta
    
    if($curlError){
        $mensagem_erro = 'Erro de conexão: ' . $curlError;
        return false;
    }
    
    if($httpCode !== 200){
        $mensagem_erro = 'Erro HTTP ' . $httpCode;
        if($response){
            $responseData = json_decode($response, true);
            if(isset($responseData['error']['message'])){
                $mensagem_erro .= ': ' . $responseData['error']['message'];
            }
        }
        return false;
    }
    
    // ===== Verificar se resposta contém "OK"
    
    $responseData = json_decode($response, true);
    $texto_resposta = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    if(stripos($texto_resposta, 'OK') === false){
        $mensagem_erro = 'Resposta inesperada da IA';
        return false;
    }
    
    return true;
}

function admin_ia_ajax_opcao(){
    global $_GESTOR;

    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    // ===== Lógica

    $payload = [];

    // ===== Dados de Retorno

    if(true){
        $_GESTOR['ajax-json'] = Array(
            'payload' => $payload,
            'status' => 'Ok',
        );
    } else {
        $_GESTOR['ajax-json'] = Array(
            'error' => 'Error msg'
        );
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
            case 'opcao': admin_ia_ajax_opcao(); break;
        }

        interface_ajax_finalizar();
    } else {
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
