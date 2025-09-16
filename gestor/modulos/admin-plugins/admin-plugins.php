<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-plugins';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-plugins.json'), true);

// ===== Inicialização para execução via CLI
if (PHP_SAPI === 'cli') {
    // Bibliotecas necessárias
    require_once __DIR__ . '/../../bibliotecas/lang.php';
    require_once __DIR__ . '/../../bibliotecas/log.php';
    require_once __DIR__ . '/../../bibliotecas/banco.php';
    require_once __DIR__ . '/../../bibliotecas/gestor.php';
    
    // Inicializar $_GESTOR básico
    if (!isset($_GESTOR)) $_GESTOR = [];
    $_GESTOR['logs-path'] = __DIR__ . '/../../logs/plugins/';
    if (!is_dir($_GESTOR['logs-path'])) @mkdir($_GESTOR['logs-path'], 0775, true);
    set_lang('pt-br');
    
    // Configuração básica do banco
    $configPath = __DIR__ . '/../../config.php';
    if (file_exists($configPath)) {
        require $configPath;
        global $_BANCO;
        if (isset($_BANCO)) {
            $_GESTOR['banco-config'] = $_BANCO;
        }
    }
}

function admin_plugins_adicionar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar registro no Banco
	
	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
			),
		));
		
		// ===== Processar origem do plugin
		$origem_dados = admin_plugins_processar_origem($_REQUEST);
		if($origem_dados === false) {
			// Erro já tratado na função auxiliar
			return;
		}
		
		// ===== Campos gerais
		
		banco_insert_name_campo('id',$id);
		if(isset($_REQUEST['nome'])){ banco_insert_name_campo('nome',$_REQUEST['nome']); }
		
		// Campos de origem processados
		if($origem_dados['origem_tipo'] !== '') {
			banco_insert_name_campo('origem_tipo', $origem_dados['origem_tipo']);
		}
		if($origem_dados['origem_referencia'] !== '') {
			banco_insert_name_campo('origem_referencia', $origem_dados['origem_referencia']);
		}
		if($origem_dados['origem_branch_tag'] !== '') {
			banco_insert_name_campo('origem_branch_tag', $origem_dados['origem_branch_tag']);
		}
		if($origem_dados['origem_credencial_ref'] !== '') {
			banco_insert_name_campo('origem_credencial_ref', $origem_dados['origem_credencial_ref']);
		}
		
		// ===== Campos comuns
		
		banco_insert_name_campo($modulo['tabela']['status'],'A');
		banco_insert_name_campo($modulo['tabela']['versao'],'1');
		banco_insert_name_campo($modulo['tabela']['data_criacao'],'NOW()',true);
		banco_insert_name_campo($modulo['tabela']['data_modificacao'],'NOW()',true);
		
		banco_insert_name
		(
			banco_insert_name_campos(),
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			),
			// Campos extras para interface (renderização custom - sem validação obrigatória aqui)
			'campos_extras' => Array(
				'origem_selecionada','arquivo_zip','repo_publico_url','repo_privado_url','repo_privado_token'
			)
		)
	);
}

function admin_plugins_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome','origem_tipo','origem_referencia','origem_branch_tag','origem_credencial_ref'
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		));
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$campo = 'nome'; $request = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo) != (isset($_REQUEST[$request]) ? $_REQUEST[$request] : NULL)){
			$editar = true;
			banco_update_campo($campo,$_REQUEST[$request],false,true);
			if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;}
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo),'valor_depois' => banco_escape_field($_REQUEST[$request]));
		}
		// Demais campos simples
		foreach(['origem_tipo','origem_referencia','origem_branch_tag','origem_credencial_ref'] as $c){
			if(isset($_REQUEST[$c]) && banco_select_campos_antes($c) != $_REQUEST[$c]){
				$editar = true; banco_update_campo($c,$_REQUEST[$c]);
				if($c == 'origem_credencial_ref') {
					$_REQUEST[$c] = 'SECRET';
				}
				$alteracoes[] = Array('campo'=>$c,'valor_antes'=>banco_select_campos_antes($c),'valor_depois'=>$_REQUEST[$c]);
			}
		}
		
		// ===== Se mudar o nome, mudar o identificador do registro
		
		if(isset($alterar_id)){
			$id_novo = banco_identificador(Array(
				'id' => banco_escape_field($_REQUEST["nome"]),
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campo' => $modulo['tabela']['id'],
					'id_nome' => $modulo['tabela']['id_numerico'],
					'id_valor' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
				),
			));
			
			$alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			banco_update_campo($modulo['tabela']['id'],$id_novo);
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Processar origem do plugin se houver mudanças
		if(isset($_REQUEST['origem_selecionada'])) {
			$origem_dados = admin_plugins_processar_origem($_REQUEST);
			if($origem_dados === false) {
				// Erro já tratado na função auxiliar
				return;
			}
			
			// Verificar se houve mudanças nos campos de origem
			$campos_origem = [
				'origem_tipo' => $origem_dados['origem_tipo'],
				'origem_referencia' => $origem_dados['origem_referencia'],
				'origem_branch_tag' => $origem_dados['origem_branch_tag'],
				'origem_credencial_ref' => $origem_dados['origem_credencial_ref']
			];
			
			foreach($campos_origem as $campo => $valor) {
				if(banco_select_campos_antes($campo) != $valor) {
					$editar = true;
					banco_update_campo($campo, $valor);
					$alteracoes[] = Array(
						'campo' => $campo,
						'valor_antes' => banco_select_campos_antes($campo),
						'valor_depois' => banco_escape_field($valor)
					);
				}
			}
		}
		
		
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar)){
			banco_update_campo($modulo['tabela']['versao'],$modulo['tabela']['versao']." + 1",true);
			banco_update_campo($modulo['tabela']['data_modificacao'],'NOW()',true);
			
			banco_update_executar($modulo['tabela']['nome'],"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'");
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		
		// Definir classes CSS para tabs ativas
		$origem_tipo = isset($retorno_bd['origem_tipo']) ? $retorno_bd['origem_tipo'] : 'arquivo';
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#origem_tipo#',$origem_tipo);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#arquivo_active#',($origem_tipo == 'arquivo' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#publico_active#',($origem_tipo == 'github_publico' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#privado_active#',($origem_tipo == 'github_privado' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#arquivo_segment#',($origem_tipo == 'arquivo' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#publico_segment#',($origem_tipo == 'github_publico' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#privado_segment#',($origem_tipo == 'github_privado' ? 'active' : ''));
		
		// Substituir valores dos campos de origem
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#repo_publico_url#',(isset($retorno_bd['origem_referencia']) && $retorno_bd['origem_tipo'] == 'github_publico' ? 'https://github.com/' . $retorno_bd['origem_referencia'] : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#repo_privado_url#',(isset($retorno_bd['origem_referencia']) && $retorno_bd['origem_tipo'] == 'github_privado' ? 'https://github.com/' . $retorno_bd['origem_referencia'] : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#repo_privado_token#',(isset($retorno_bd['origem_credencial_ref']) ? $retorno_bd['origem_credencial_ref'] : ''));
		
		// ===== Popular os metaDados

		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');

		// ===== Metadados extras do plugin (Fase 1)
		
		$camposExtra = [
			'origem_tipo'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-origin-type')),
			'origem_referencia'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-origin-reference')),
			'origem_branch_tag'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-origin-branch-tag')),
			'origem_credencial_ref'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-origin-credential-ref')),
			'versao_instalada'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-installed-version')),
			'checksum_pacote'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-package-checksum')),
			'status_execucao'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-execution-status')),
			'data_ultima_atualizacao'=>gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-last-update'))
		];
		foreach($camposExtra as $c=>$label){
			if(isset($retorno_bd[$c])){
				if($c == 'origem_credencial_ref') {
					existe($retorno_bd[$c]) ? $retorno_bd[$c] = 'SECRET' : null;
				}
				$metaDados[] = Array('titulo'=>$label,'dado'=>htmlentities($retorno_bd[$c]));
			}
		}
		// Manifest prettified
		if(!empty($retorno_bd['manifest_json'])){
			$manifestPretty = json_decode($retorno_bd['manifest_json'],true);
			if(is_array($manifestPretty)){
				$manifestHtml = '<pre style="max-height:300px;overflow:auto;background:#f8f8f8;border:1px solid #ddd;padding:8px;">'.htmlentities(json_encode($manifestPretty, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)).'</pre>';
				$metaDados[] = Array('titulo'=>'Manifest','dado'=>$manifestHtml);
			}
		}
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
		'botoes' => Array(
			'adicionar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'executar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/executar/?id='.$id,
				'rotulo' => 'Executar Ações',
				'tooltip' => 'Ir para página de execução de ações do plugin',
				'icon' => 'play',
				'cor' => 'brown',
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash' ),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown' ),
			),
			'excluir' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-delete')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'cor' => 'red',
			),
		),
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		)
	);
}

function admin_plugins_executar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do plugin
	
	$id = $_REQUEST['id'] ?? '';
	if(empty($id)){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => 'ID do plugin não informado'
		));
		gestor_redirecionar($_GESTOR['modulo-id'].'/');
	}
	
	// ===== Buscar dados do plugin
	
	$retorno_bd = banco_select_editar(
		banco_campos_virgulas(['id','nome','origem_tipo','origem_referencia','status'])
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
	);
	
	if(!$_GESTOR['banco-resultado']){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => 'Plugin não encontrado'
		));
		gestor_redirecionar($_GESTOR['modulo-id'].'/');
	}
	
	$plugin_data = $retorno_bd;
	
	// ===== Incluir CodeMirror assets
	
	// ===== Inclusão do CodeMirror
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/theme/tomorrow-night-bright.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.css" />';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/selection/active-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/searchcursor.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/search.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/scroll/annotatescrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/jump-to-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/edit/matchbrackets.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/xml/xml.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/css/css.js"></script>';
	$_GESTOR['javascript'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/htmlmixed/htmlmixed.js"></script>';
	
	// ===== Incluir Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Incluir componente de execução
	
	$comp = gestor_componente([
		'id' => 'plugins-exec',
		'modulo' => $_GESTOR['modulo-id'],
	]);

	// ===== Popular template com dados do plugin
	
	$comp = modelo_var_troca_tudo($comp,'#id#',$plugin_data['id']);
	$comp = modelo_var_troca_tudo($comp,'#nome#',$plugin_data['nome']);
	$comp = modelo_var_troca_tudo($comp,'#origem_tipo#',$plugin_data['origem_tipo']);
	$comp = modelo_var_troca_tudo($comp,'#status#',$plugin_data['status']);

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#dynamic-content#',$comp);
}

function admin_plugins_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'origem_referencia',
						'origem_tipo',
						'origem_branch_tag',
						$modulo['tabela']['data_criacao'],
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
				),
				'tabela' => Array(
					'rodape' => true,
					'colunas' => Array(
						Array(
							'id' => 'nome',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'origem_referencia',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-origin-reference')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'origem_tipo',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-origin-type')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'origem_branch_tag',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'metadata-origin-branch-tag')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => $modulo['tabela']['data_criacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
						Array(
							'id' => $modulo['tabela']['data_modificacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
					),
				),
				'opcoes' => Array(
					'editar' => Array(
						'url' => 'editar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')),
						'icon' => 'edit',
						'cor' => 'basic blue',
					),
					'executar' => Array(
						'url' => 'executar/',
						'tooltip' => 'Executar ações do plugin',
						'icon' => 'play',
						'cor' => 'basic brown',
					),
					'ativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'I',
						'status_mudar' => 'A',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active')),
						'icon' => 'eye slash',
						'cor' => 'basic brown',
					),
					'desativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'A',
						'status_mudar' => 'I',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')),
						'icon' => 'eye',
						'cor' => 'basic green',
					),
					'excluir' => Array(
						'opcao' => 'excluir',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
						'icon' => 'trash alternate',
						'cor' => 'basic red',
					),
				),
				'botoes' => Array(
					'adicionar' => Array(
						'url' => 'adicionar/',
						'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
						'icon' => 'plus circle',
						'cor' => 'blue',
					),
				),
			);
		break;
	}
}

// ==== Ajax

function admin_plugins_ajax_opcao(){
	global $_GESTOR;
	
	$acao = isset($_REQUEST['acao']) ? $_REQUEST['acao'] : null;
	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
	if($acao && $id){
		$res = admin_plugins_executar_acao($acao,$id,true);
		$_GESTOR['ajax-json'] = $res;
	} else {
		$_GESTOR['ajax-json'] = Array('status'=>'Ok');
	}
}

function admin_plugins_ajax_update(){
	global $_GESTOR;
	
	$params = $_POST['params'] ?? $_GET['params'] ?? [];
	if(!is_array($params)) $params = [];
	
	$acao = $params['acao'] ?? '';
	$id = $params['id'] ?? '';
	
	$resp = [];
	try {
		switch($acao){
			case 'instalar':
			case 'atualizar':
			case 'reprocessar':
				$resp = admin_plugins_executar_acao($acao, $id, true);
				break;
			case 'status':
				// Buscar status atual do plugin
				$row = banco_select(Array(
					'tabela'=>'plugins',
					'campos'=>['id','status','status_execucao','data_ultima_atualizacao'],
					'extra'=>"WHERE id='".banco_escape_field($id)."'"
				));
				if($row && count($row)>0){
					$resp = [
						'id' => $row[0]['id'],
						'status' => $row[0]['status'],
						'status_execucao' => $row[0]['status_execucao'],
						'ultima_atualizacao' => $row[0]['data_ultima_atualizacao']
					];
				} else {
					$resp = ['error' => 'Plugin não encontrado'];
				}
				break;
			default:
				$resp = ['error' => 'Ação AJAX desconhecida'];
		}
		
		if(isset($resp['error'])) {
			$_GESTOR['ajax-json'] = ['status'=>'erro','erro'=>$resp['error'],'data'=>$resp];
		} else {
			$_GESTOR['ajax-json'] = ['status'=>'ok','data'=>$resp];
		}
	} catch(Throwable $e){
		$_GESTOR['ajax-json'] = ['status'=>'erro','erro'=>$e->getMessage()];
	}
}

function admin_plugins_executar_acao($acao,$id,$retornarArray=false){
	global $_GESTOR;

	$permitidas = ['instalar','atualizar','reprocessar'];
	if(!in_array($acao,$permitidas,true)) return ['status'=>'erro','msg'=>'Ação inválida'];

	// Buscar registro do plugin
	$row = banco_select(Array(
		'tabela'=>'plugins',
		'campos'=>['id','origem_tipo','origem_referencia','origem_branch_tag','origem_credencial_ref'],
		'extra'=>"WHERE id='".banco_escape_field($id)."'"
	));
	if(!$row) return ['status'=>'erro','msg'=>'Plugin não encontrado'];
	$row = $row[0];

	gestor_incluir_biblioteca([
		'plugins-consts',
		'plugins-installer',
	]);

	// ===== INCLUIR SCRIPT DIRETAMENTE (em vez de shell_exec por questões de segurança)
	// Preparar parâmetros para o script de banco de dados
	$GLOBALS['CLI_OPTS'] = [
		'plugin' => $row['id']
	];
	require_once __DIR__.'/../../controladores/plugins/atualizacao-plugin.php';

        // Preparar parâmetros para chamada direta
        $params = [
                'slug' => $row['id'],
                'origem_tipo' => $row['origem_tipo'] === 'arquivo' ? 'upload' : $row['origem_tipo'], // Mapear "arquivo" para "upload"
                'referencia' => $row['origem_referencia'], // Preservar a referencia original
                'arquivo' => null,
                'owner' => null,
                'repo' => null,
                'ref' => null,
                'cred_ref' => null,
                'local_path' => null,
                'reprocessar' => ($acao === 'reprocessar'),
                'dry_run' => false,
                'no_resources' => false,
                'no_migrations' => false,
                'only_migrations' => false,
                'only_resources' => false,
        ];	// Para plugins adicionados via arquivo ZIP, definir o caminho do arquivo
	if(!empty($row['origem_referencia']) && strpos($row['origem_referencia'], '.zip') !== false) {
			$plugins_dir = $_GESTOR['ROOT_PATH'] . 'contents/plugins/';
			$params['arquivo'] = $plugins_dir . $row['origem_referencia'];
	}	// Interpretar origem_referencia (para github: owner/repo) ou local path
	
	if($row['origem_tipo']==='github_publico' || $row['origem_tipo']==='github_privado'){
		if(strpos($row['origem_referencia'],'/')!==false){
			list($params['owner'],$params['repo'])=explode('/', $row['origem_referencia'],2);
		}
		
		// Para ação "atualizar", verificar se há uma versão mais nova disponível
		if($acao === 'atualizar'){
			try {
				$repo_url = "https://github.com/{$params['owner']}/{$params['repo']}";
				$token = ($row['origem_tipo']==='github_privado' && !empty($row['origem_credencial_ref'])) ? $row['origem_credencial_ref'] : null;
				
				error_log("[ATUALIZAR] Verificando se há atualizações disponíveis para: {$repo_url}");
				$latest_release = admin_plugins_descobrir_ultima_tag_plugin($repo_url, $row['id'], $token);
				
				// Comparar versões
				$current_version = $row['origem_branch_tag'] ?? '';
				$latest_version = $latest_release['tag'];
				
				error_log("[ATUALIZAR] Versão atual: {$current_version}");
				error_log("[ATUALIZAR] Última versão: {$latest_version}");
				
				if($current_version === $latest_version){
					// Já está na versão mais recente
					error_log("[ATUALIZAR] Plugin já está na versão mais recente: {$current_version}");
					return [
						'status' => 'ok',
						'acao' => $acao,
						'codigo_saida' => PLG_EXIT_OK,
						'log' => "[ATUALIZAR] Plugin já está na versão mais recente: {$current_version}\n[ATUALIZAR] Nenhuma atualização necessária.",
						'mensagem' => 'Plugin já está atualizado para a versão mais recente',
					];
				} else {
					// Há uma versão mais nova disponível
					error_log("[ATUALIZAR] Nova versão encontrada: {$latest_version} (atual: {$current_version})");
					error_log("[ATUALIZAR] Iniciando atualização para: {$latest_version}");
					
					// Usar os assets da nova versão
					$params['download_url'] = $latest_release['download_url'];
					$params['sha256_url'] = $latest_release['sha256_url'];
					$params['ref'] = $latest_version; // Atualizar para a nova versão
					
					error_log("[ATUALIZAR] Assets descobertos: ZIP={$params['download_url']}, SHA256={$params['sha256_url']}");
				}
			} catch (Exception $e) {
				error_log("[ATUALIZAR] ERRO ao verificar atualizações: " . $e->getMessage());
				// Em caso de erro na descoberta, tentar usar a tag salva (comportamento antigo)
				error_log("[ATUALIZAR] Fallback: tentando usar tag salva: {$row['origem_branch_tag']}");
				if(!empty($row['origem_branch_tag'])){
					try {
						$repo_url = "https://github.com/{$params['owner']}/{$params['repo']}";
						$token = ($row['origem_tipo']==='github_privado' && !empty($row['origem_credencial_ref'])) ? $row['origem_credencial_ref'] : null;
						
						$tag_info = admin_plugins_descobrir_tag_especifica_plugin($repo_url, $row['origem_branch_tag'], $token);
						
						$params['download_url'] = $tag_info['download_url'];
						$params['sha256_url'] = $tag_info['sha256_url'];
						$params['ref'] = $row['origem_branch_tag'];
						
						error_log("[ATUALIZAR] Fallback bem-sucedido - usando assets da tag salva");
					} catch (Exception $fallback_e) {
						error_log("[ATUALIZAR] ERRO no fallback: " . $fallback_e->getMessage());
						return [
							'status' => 'erro',
							'acao' => $acao,
							'codigo_saida' => PLG_EXIT_DOWNLOAD,
							'log' => "[ATUALIZAR] ERRO: " . $e->getMessage() . "\n[ATUALIZAR] Fallback também falhou: " . $fallback_e->getMessage(),
							'mensagem' => 'Erro ao verificar atualizações: ' . $e->getMessage(),
						];
					}
				} else {
					return [
						'status' => 'erro',
						'acao' => $acao,
						'codigo_saida' => PLG_EXIT_DOWNLOAD,
						'log' => "[ATUALIZAR] ERRO ao verificar atualizações: " . $e->getMessage() . "\n[ATUALIZAR] Tag não encontrada no banco de dados",
						'mensagem' => 'Erro ao verificar atualizações: ' . $e->getMessage(),
					];
				}
			}
		} elseif(!empty($row['origem_branch_tag'])){
			// Para outras ações (reprocessar), se há uma tag específica salva, descobrir os assets dessa tag
			try {
				$repo_url = "https://github.com/{$params['owner']}/{$params['repo']}";
				$token = ($row['origem_tipo']==='github_privado' && !empty($row['origem_credencial_ref'])) ? $row['origem_credencial_ref'] : null;
				
				error_log("[EXECUTAR] Descobrindo assets da tag específica: {$row['origem_branch_tag']}");
				$tag_info = admin_plugins_descobrir_tag_especifica_plugin($repo_url, $row['origem_branch_tag'], $token);
				
				// Passar as URLs dos assets diretamente em vez de apenas a tag
				$params['download_url'] = $tag_info['download_url'];
				$params['sha256_url'] = $tag_info['sha256_url'];
				$params['ref'] = $row['origem_branch_tag']; // Manter para compatibilidade
				
				error_log("[EXECUTAR] Assets descobertos: ZIP={$params['download_url']}, SHA256={$params['sha256_url']}");
			} catch (Exception $e) {
				error_log("[EXECUTAR] ERRO ao descobrir assets da tag {$row['origem_branch_tag']}: " . $e->getMessage());
				// Fallback: usar apenas a tag (comportamento antigo)
				$params['ref'] = $row['origem_branch_tag'];
			}
		}
		
		if($row['origem_tipo']==='github_privado' && !empty($row['origem_credencial_ref'])) $params['cred_ref'] = $row['origem_credencial_ref'];
	} elseif($row['origem_tipo']==='local_path') {
		if(!empty($row['origem_referencia'])) $params['local_path'] = $row['origem_referencia'];
	}

	// Detecta se já existe registro para diferenciar instalar vs atualizar
	$existe = false;
	$res = @banco_select([
		'tabela' => 'plugins',
		'campos' => ['id'],
		'extra' => "WHERE id='".banco_escape_field($row['id'])."'"
	]);
	if($res && count($res)>0) $existe = true;

	// Marcar status inicial conforme fase
	if(function_exists('plugin_mark_status')) plugin_mark_status($row['id'], $existe?PLG_STATUS_ATUALIZANDO:PLG_STATUS_INSTALANDO);

	// Executar processamento diretamente
	$code = plugin_process_cli($params);

	// Ajustar status final
	if($code===PLG_EXIT_OK){
		plugin_mark_status($row['id'], PLG_STATUS_OK);
	} else {
		plugin_mark_status($row['id'], PLG_STATUS_ERRO);
	}

	// Capturar saída do log (últimas linhas específicas do plugin)
	$logFile = dirname(__DIR__,2).'/logs/plugins/installer.log';
	$logTail = '';
	if(file_exists($logFile)){
		$lines = @file($logFile);
		if($lines){
			$filtered = array_values(array_filter($lines,function($l) use ($id){ return strpos($l,'[PLUGIN:'.$id.']')!==false; }));
			$logTail = implode('', array_slice($filtered,-15));
		}
	}

	$resultado = [
		'status' => ($code === PLG_EXIT_OK) ? 'ok' : 'erro',
		'acao' => $acao,
		'codigo_saida' => $code,
		'log' => $logTail,
		'mensagem' => ($code === PLG_EXIT_OK) ? 'Ação executada com sucesso' : 'Erro na execução da ação',
	];

	if($retornarArray) return $resultado;
	interface_alerta(Array('msg'=>'Ação '.$acao.' executada.'));
	return $resultado;
}

// ===== Funções para descoberta automática de releases de plugins
function admin_plugins_descobrir_ultima_tag_plugin(string $repo_url, string $plugin_id = null, string $token = null): array {
    // Extrair owner/repo da URL do GitHub
    if (preg_match('#github\.com/([^/]+)/([^/]+)#', $repo_url, $matches)) {
        $owner = $matches[1];
        $repo = $matches[2];
        
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases";
        $ch = curl_init();
        
        $headers = [
            'User-Agent: Conn2Flow-Plugin-Manager/1.0',
            'Accept: application/vnd.github+json'
        ];
        if (!empty($token)) {
            $headers[] = 'Authorization: token ' . $token;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Conn2Flow-Plugin-Manager/1.0',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        // Log da descoberta
        $log_prefix = $plugin_id ? "[PLUGIN:{$plugin_id}]" : "[DISCOVERY]";
        error_log("{$log_prefix} [DISCOVERY] Buscando releases em {$repo_url}");
        if (!empty($token)) {
            error_log("{$log_prefix} [DISCOVERY] Usando autenticação com token");
        }
        
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($code !== 200 || !$resp) {
            error_log("{$log_prefix} [DISCOVERY] ERRO HTTP {$code}: {$err}");
            throw new Exception('Falha ao buscar releases do GitHub HTTP ' . $code . ' ' . $err);
        }
        
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            error_log("{$log_prefix} [DISCOVERY] ERRO: JSON de releases inválido");
            throw new Exception('JSON de releases inválido');
        }
        
        error_log("{$log_prefix} [DISCOVERY] Encontrados " . count($data) . " releases");

        // Procurar pela tag mais recente do plugin
        // Tentar múltiplos padrões de prefixo para maior flexibilidade
        $prefixes = ['plugin-'];
        if ($plugin_id) {
            // Adicionar padrões mais específicos primeiro
            $prefixes = array_merge([
                'plugin-' . $plugin_id . '-v',  // plugin-plugin_id-v
            ], $prefixes);
        }

        $latest_release = null;
        foreach ($prefixes as $prefix) {
            error_log("{$log_prefix} [DISCOVERY] Tentando prefixo: '{$prefix}'");
            foreach ($data as $release) {
                if (!empty($release['tag_name']) && strpos($release['tag_name'], $prefix) === 0) {
                    if (!$latest_release || strtotime($release['published_at']) > strtotime($latest_release['published_at'])) {
                        $latest_release = $release;
                        error_log("{$log_prefix} [DISCOVERY] Tag candidata encontrada: {$release['tag_name']} com prefixo '{$prefix}'");
                    }
                }
            }
            // Se encontrou uma tag com este prefixo, parar de tentar outros
            if ($latest_release) {
                break;
            }
        }        if ($latest_release) {
            error_log("{$log_prefix} [DISCOVERY] Tag encontrada: {$latest_release['tag_name']} ({$latest_release['published_at']})");
            
            // Procurar pelos assets gestor-plugin.zip e gestor-plugin.zip.sha256
            $zip_asset_url = null;
            $sha256_asset_url = null;
            $is_private_repo = !empty($token);
            
            if (!empty($latest_release['assets']) && is_array($latest_release['assets'])) {
                error_log("{$log_prefix} [DISCOVERY] Verificando " . count($latest_release['assets']) . " assets");
                
                foreach ($latest_release['assets'] as $asset) {
                    if ($asset['name'] === 'gestor-plugin.zip') {
                        $zip_asset_url = $asset['url']; // URL da API do asset
                        error_log("{$log_prefix} [DISCOVERY] Asset ZIP encontrado: {$asset['name']} (ID: {$asset['id']}, URL: {$zip_asset_url})");
                    } elseif ($asset['name'] === 'gestor-plugin.zip.sha256') {
                        $sha256_asset_url = $asset['url']; // URL da API do asset
                        error_log("{$log_prefix} [DISCOVERY] Asset SHA256 encontrado: {$asset['name']} (ID: {$asset['id']}, URL: {$sha256_asset_url})");
                    }
                }
            }
            
            // Verificar se encontrou os assets necessários
            if (!$zip_asset_url) {
                if ($is_private_repo) {
                    // Para repositórios privados, SEMPRE deve ter o asset gestor-plugin.zip
                    error_log("{$log_prefix} [DISCOVERY] ERRO: Asset 'gestor-plugin.zip' não encontrado na release {$latest_release['tag_name']} do repositório privado");
                    error_log("{$log_prefix} [DISCOVERY] Para repositórios privados, você deve criar um asset chamado 'gestor-plugin.zip' na release");
                    throw new Exception("Asset 'gestor-plugin.zip' não encontrado na release. Para repositórios privados, é obrigatório ter este asset na release do GitHub.");
                } else {
                    // Para repositórios públicos, usar URL direta como fallback
                    $zip_asset_url = "https://github.com/{$owner}/{$repo}/releases/download/{$latest_release['tag_name']}/gestor-plugin.zip";
                    error_log("{$log_prefix} [DISCOVERY] Asset ZIP não encontrado, usando URL direta (público): {$zip_asset_url}");
                }
            } else {
                error_log("{$log_prefix} [DISCOVERY] Usando URL de asset ZIP: {$zip_asset_url}");
            }
            
            // Para repositórios privados, verificar se tem SHA256 (recomendado mas não obrigatório)
            if ($is_private_repo && !$sha256_asset_url) {
                error_log("{$log_prefix} [DISCOVERY] AVISO: Asset 'gestor-plugin.zip.sha256' não encontrado na release {$latest_release['tag_name']}");
                error_log("{$log_prefix} [DISCOVERY] Recomenda-se criar um asset SHA256 para verificação de integridade");
            } elseif ($is_private_repo && $sha256_asset_url) {
                error_log("{$log_prefix} [DISCOVERY] Asset SHA256 encontrado - verificação de integridade disponível");
            }
            
            return [
                'tag' => $latest_release['tag_name'],
                'published_at' => $latest_release['published_at'],
                'download_url' => $zip_asset_url,
                'sha256_url' => $sha256_asset_url // Novo campo retornado
            ];
        }
        
        error_log("{$log_prefix} [DISCOVERY] ERRO: Nenhuma tag de plugin encontrada com prefixo '{$prefix}'");
        throw new Exception('Nenhuma tag de plugin encontrada no repositório');
    } else {
        error_log("[DISCOVERY] ERRO: URL do GitHub inválida: {$repo_url}");
        throw new Exception('URL do GitHub inválida para descoberta automática');
    }
}

// ===== Função para descobrir assets de uma tag específica
function admin_plugins_descobrir_tag_especifica_plugin(string $repo_url, string $tag_name, string $token = null): array {
    // Extrair owner/repo da URL do GitHub
    if (preg_match('#github\.com/([^/]+)/([^/]+)#', $repo_url, $matches)) {
        $owner = $matches[1];
        $repo = $matches[2];
        
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases/tags/{$tag_name}";
        $ch = curl_init();
        
        $headers = [
            'User-Agent: Conn2Flow-Plugin-Manager/1.0',
            'Accept: application/vnd.github+json'
        ];
        if (!empty($token)) {
            $headers[] = 'Authorization: token ' . $token;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Conn2Flow-Plugin-Manager/1.0',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        // Log da descoberta
        $log_prefix = "[DISCOVERY-TAG:{$tag_name}]";
        error_log("{$log_prefix} Buscando release específica: {$repo_url} tag {$tag_name}");
        if (!empty($token)) {
            error_log("{$log_prefix} Usando autenticação com token");
        }
        
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($code !== 200 || !$resp) {
            error_log("{$log_prefix} ERRO HTTP {$code}: {$err}");
            throw new Exception('Falha ao buscar release específico do GitHub HTTP ' . $code . ' ' . $err);
        }
        
        $release = json_decode($resp, true);
        if (!is_array($release) || !isset($release['tag_name'])) {
            error_log("{$log_prefix} ERRO: JSON de release inválido ou tag não encontrada");
            throw new Exception('Release não encontrado ou JSON inválido');
        }
        
        error_log("{$log_prefix} Release encontrado: {$release['tag_name']} ({$release['published_at']})");
        
        // Procurar pelos assets gestor-plugin.zip e gestor-plugin.zip.sha256
        $zip_asset_url = null;
        $sha256_asset_url = null;
        $is_private_repo = !empty($token);
        
        if (!empty($release['assets']) && is_array($release['assets'])) {
            error_log("{$log_prefix} Verificando " . count($release['assets']) . " assets");
            
            foreach ($release['assets'] as $asset) {
                if ($asset['name'] === 'gestor-plugin.zip') {
                    $zip_asset_url = $asset['url']; // URL da API do asset
                    error_log("{$log_prefix} Asset ZIP encontrado: {$asset['name']} (ID: {$asset['id']}, URL: {$zip_asset_url})");
                } elseif ($asset['name'] === 'gestor-plugin.zip.sha256') {
                    $sha256_asset_url = $asset['url']; // URL da API do asset
                    error_log("{$log_prefix} Asset SHA256 encontrado: {$asset['name']} (ID: {$asset['id']}, URL: {$sha256_asset_url})");
                }
            }
        }
        
        // Verificar se encontrou os assets necessários
        if (!$zip_asset_url) {
            if ($is_private_repo) {
                // Para repositórios privados, SEMPRE deve ter o asset gestor-plugin.zip
                error_log("{$log_prefix} ERRO: Asset 'gestor-plugin.zip' não encontrado na release {$release['tag_name']} do repositório privado");
                error_log("{$log_prefix} Para repositórios privados, você deve criar um asset chamado 'gestor-plugin.zip' na release");
                throw new Exception("Asset 'gestor-plugin.zip' não encontrado na release. Para repositórios privados, é obrigatório ter este asset na release do GitHub.");
            } else {
                // Para repositórios públicos, usar URL direta como fallback
                $zip_asset_url = "https://github.com/{$owner}/{$repo}/releases/download/{$release['tag_name']}/gestor-plugin.zip";
                error_log("{$log_prefix} Asset ZIP não encontrado, usando URL direta (público): {$zip_asset_url}");
            }
        } else {
            error_log("{$log_prefix} Usando URL de asset ZIP: {$zip_asset_url}");
        }
        
        // Para repositórios privados, verificar se tem SHA256 (recomendado mas não obrigatório)
        if ($is_private_repo && !$sha256_asset_url) {
            error_log("{$log_prefix} AVISO: Asset 'gestor-plugin.zip.sha256' não encontrado na release {$release['tag_name']}");
            error_log("{$log_prefix} Recomenda-se criar um asset SHA256 para verificação de integridade");
        } elseif ($is_private_repo && $sha256_asset_url) {
            error_log("{$log_prefix} Asset SHA256 encontrado - verificação de integridade disponível");
        }
        
        return [
            'tag' => $release['tag_name'],
            'published_at' => $release['published_at'],
            'download_url' => $zip_asset_url,
            'sha256_url' => $sha256_asset_url
        ];
    } else {
        error_log("[DISCOVERY-TAG] ERRO: URL do GitHub inválida: {$repo_url}");
        throw new Exception('URL do GitHub inválida para descoberta de tag específica');
    }
}

function admin_plugins_download_release_plugin(string $download_url, string $dest_dir, string $token = null, string $sha256_url = null): string {
    $zip_path = rtrim($dest_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugin.zip';
    $sha256_path = rtrim($dest_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugin.zip.sha256';

    // Detectar se é um repositório privado (pela presença de token)
    $is_private_repo = !empty($token);

    if ($is_private_repo) {
        error_log("[DOWNLOAD] Repositório privado detectado - baixando ambos os arquivos (ZIP + SHA256)");

        // Para repositórios privados, precisamos baixar ambos os arquivos
        // Primeiro, vamos identificar as URLs dos dois assets

        if (!empty($sha256_url)) {
            // URLs fornecidas diretamente pela descoberta
            $zip_url = $download_url;
            $final_sha256_url = $sha256_url;
            
            error_log("[DOWNLOAD] URLs fornecidas pela descoberta:");
            error_log("[DOWNLOAD] ZIP: {$zip_url}");
            error_log("[DOWNLOAD] SHA256: {$final_sha256_url}");
        } elseif (strpos($download_url, '/releases/assets/') !== false) {
            // URL é do asset da API - tentar descobrir a URL do SHA256 fazendo uma nova busca
            error_log("[DOWNLOAD] URL do asset fornecida, mas SHA256 não - tentando descobrir ambos os assets");
            
            // Extrair owner/repo da URL do asset
            if (preg_match('#github\.com/([^/]+)/([^/]+)/releases/assets/#', $download_url, $matches)) {
                $owner = $matches[1];
                $repo = $matches[2];
                
                try {
                    // Fazer uma nova busca para encontrar ambos os assets
                    $release_info = admin_plugins_descobrir_ultima_tag_plugin("https://github.com/{$owner}/{$repo}", null, $token);
                    $zip_url = $release_info['download_url'];
                    $final_sha256_url = $release_info['sha256_url'];
                    
                    error_log("[DOWNLOAD] Assets descobertos:");
                    error_log("[DOWNLOAD] ZIP: {$zip_url}");
                    error_log("[DOWNLOAD] SHA256: {$final_sha256_url}");
                } catch (Exception $e) {
                    error_log("[DOWNLOAD] ERRO ao descobrir assets: " . $e->getMessage());
                    throw new Exception('Falha ao descobrir URLs dos assets: ' . $e->getMessage());
                }
            } else {
                error_log("[DOWNLOAD] ERRO: URL de asset inválida: {$download_url}");
                throw new Exception('URL de asset inválida para descoberta');
            }
        } elseif (strpos($download_url, '/releases/download/') !== false) {
            // URL já é de download direto - construir URLs baseadas nela
            $zip_url = $download_url;
            $final_sha256_url = str_replace('.zip', '.zip.sha256', $download_url);

            error_log("[DOWNLOAD] URLs baseadas na URL de download direto:");
            error_log("[DOWNLOAD] ZIP: {$zip_url}");
            error_log("[DOWNLOAD] SHA256: {$final_sha256_url}");
        } else {
            // URL é apenas do repositório - isso não deveria acontecer se descoberta funcionou
            error_log("[DOWNLOAD] ERRO: URL fornecida não é válida para download: {$download_url}");
            throw new Exception('URL de download inválida. Use a descoberta automática primeiro.');
        }

        // Baixar arquivo ZIP
        error_log("[DOWNLOAD] Baixando arquivo ZIP...");
        $zip_path_temp = admin_plugins_download_file($zip_url, $zip_path, $token);

        // Baixar arquivo SHA256
        error_log("[DOWNLOAD] Baixando arquivo SHA256...");
        try {
            $sha256_path_temp = admin_plugins_download_file($final_sha256_url, $sha256_path, $token);
        } catch (Exception $e) {
            error_log("[DOWNLOAD] AVISO: Arquivo SHA256 não encontrado: " . $e->getMessage());
            error_log("[DOWNLOAD] Continuando sem verificação de checksum (não recomendado para produção)");
            // Para compatibilidade, continua sem SHA256, mas registra o aviso
            return $zip_path_temp;
        }

        // Verificar checksum se ambos os arquivos foram baixados
        if (file_exists($sha256_path_temp) && file_exists($zip_path_temp)) {
            $checksum_verificado = admin_plugins_verificar_checksum($zip_path_temp, $sha256_path_temp);
            if (!$checksum_verificado) {
                // Checksum não confere - remover arquivos e abortar
                unlink($zip_path_temp);
                unlink($sha256_path_temp);
                throw new Exception('Verificação de checksum falhou! O arquivo pode ter sido comprometido. Download abortado.');
            }
            error_log("[DOWNLOAD] ✓ Checksum verificado com sucesso");

            // Remover arquivo SHA256 após verificação bem-sucedida
            unlink($sha256_path_temp);
        }

        return $zip_path_temp;

    } else {
        // Para repositórios públicos, manter comportamento antigo (apenas ZIP)
        error_log("[DOWNLOAD] Repositório público - baixando apenas ZIP");
        return admin_plugins_download_file($download_url, $zip_path, $token);
    }
}

// ===== Função auxiliar para download de arquivo único
function admin_plugins_download_file(string $url, string $dest_path, string $token = null): string {
    $ch = curl_init($url);
    $fp = fopen($dest_path, 'wb');

    $headers = ['User-Agent: Conn2Flow-Plugin-Manager/1.0'];

    // Se for URL de asset (repositório privado), usar Accept correto
    if (strpos($url, '/releases/assets/') !== false) {
        $headers[] = 'Accept: application/octet-stream';
    }

    if (!empty($token)) {
        $headers[] = 'Authorization: token ' . $token;
    }

    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => $headers
    ]);

    error_log("[DOWNLOAD] Iniciando download de: {$url}");
    error_log("[DOWNLOAD] Salvando em: {$dest_path}");

    $success = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if (!$success || $http_code !== 200) {
        error_log("[DOWNLOAD] ERRO HTTP {$http_code}: {$err}");
        if (file_exists($dest_path)) {
            unlink($dest_path);
        }
        throw new Exception('Falha ao baixar arquivo HTTP ' . $http_code . ' ' . $err);
    }

    $file_size = filesize($dest_path);
    if ($file_size === 0) {
        error_log("[DOWNLOAD] ERRO: Arquivo baixado está vazio");
        unlink($dest_path);
        throw new Exception('Arquivo baixado está vazio');
    }

    $file_size_mb = round($file_size / 1024 / 1024, 2);
    error_log("[DOWNLOAD] Download concluído: {$file_size} bytes ({$file_size_mb} MB)");

    return $dest_path;
}

// ===== Função para verificar checksum SHA256
function admin_plugins_verificar_checksum(string $arquivo_path, string $sha256_path): bool {
    // Ler checksum esperado do arquivo .sha256
    $checksum_esperado = trim(file_get_contents($sha256_path));

    // Calcular checksum real do arquivo ZIP
    $checksum_real = hash_file('sha256', $arquivo_path);

    error_log("[CHECKSUM] Checksum esperado: {$checksum_esperado}");
    error_log("[CHECKSUM] Checksum calculado: {$checksum_real}");

    // Comparar checksums
    $resultado = hash_equals($checksum_esperado, $checksum_real);

    if ($resultado) {
        error_log("[CHECKSUM] ✓ Checksums conferem");
    } else {
        error_log("[CHECKSUM] ✗ Checksums NÃO conferem - possível ataque man-in-the-middle!");
    }

    return $resultado;
}

// ===== Função auxiliar para processar origem do plugin
function admin_plugins_processar_origem($dados) {
	global $_GESTOR;
	
	$origem_tipo = '';
	$origem_referencia = '';
	$origem_branch_tag = '';
	$origem_credencial_ref = '';
	$arquivo_path = '';
	
	// Pasta para armazenar arquivos
	$plugins_dir = $_GESTOR['ROOT_PATH'] . 'contents/plugins/';
	if(!is_dir($plugins_dir)) {
		mkdir($plugins_dir, 0755, true);
	}
	
	// Processar baseado na origem selecionada
	if(isset($_REQUEST['origem_selecionada'])) {
		switch($_REQUEST['origem_selecionada']) {
			case 'arquivo':
				$origem_tipo = 'arquivo';
				if(isset($_FILES['arquivo_zip']) && $_FILES['arquivo_zip']['error'] === UPLOAD_ERR_OK) {
					$temp_name = $_FILES['arquivo_zip']['tmp_name'];
					$original_name = $_FILES['arquivo_zip']['name'];
					
					// Validar extensão
					$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
					if($ext !== 'zip') {
						interface_alerta(Array(
							'redirect' => false,
							'msg' => 'Apenas arquivos .zip são permitidos.'
						));
						return false;
					}
					
					// Gerar nome único
					$unique_name = uniqid('plugin_') . '.zip';
					$target_path = $plugins_dir . $unique_name;
					
					if(move_uploaded_file($temp_name, $target_path)) {
						$origem_referencia = $unique_name;
						$arquivo_path = $target_path;
					} else {
						interface_alerta(Array(
							'redirect' => false,
							'msg' => 'Erro ao salvar o arquivo.'
						));
						return false;
					}
				} else {
					interface_alerta(Array(
						'redirect' => false,
						'msg' => 'Arquivo ZIP é obrigatório para esta opção.'
					));
					return false;
				}
				break;
				
			case 'publico':
				$origem_tipo = 'github_publico';
				if(isset($_REQUEST['repo_publico_url']) && !empty($_REQUEST['repo_publico_url'])) {
					$url = $_REQUEST['repo_publico_url'];
					
					// Verificar se é uma URL direta de download ou URL do repositório
					if (strpos($url, '/download/') !== false || strpos($url, '.zip') !== false) {
						// URL direta de download
						$download_url = $url;
						$origem_referencia = $url;
					} else {
						// URL do repositório - tentar descoberta automática
						try {
							$release_info = admin_plugins_descobrir_ultima_tag_plugin($url);
							$download_url = $release_info['download_url'];
							$sha256_url = $release_info['sha256_url'] ?? null;
							// Extrair owner/repo da URL para salvar como referência
							if (preg_match('#github\.com/([^/]+)/([^/]+)#', $url, $matches)) {
								$origem_referencia = $matches[1] . '/' . $matches[2]; // Salvar como owner/repo
							} else {
								$origem_referencia = $url; // Fallback para URL completa se regex falhar
							}
							$origem_branch_tag = $release_info['tag']; // Guardar tag descoberta
						} catch (Exception $e) {
							interface_alerta(Array(
								'redirect' => false,
								'msg' => 'Erro na descoberta automática: ' . $e->getMessage()
							));
							return false;
						}
					}
					
					// Download do arquivo
					$unique_name = uniqid('plugin_repo_') . '.zip';
					$target_path = $plugins_dir . $unique_name;
					
					try {
						$downloaded_path = admin_plugins_download_release_plugin($download_url, $plugins_dir, null, $sha256_url);
						rename($downloaded_path, $target_path);
						$arquivo_path = $target_path;
					} catch (Exception $e) {
						interface_alerta(Array(
							'redirect' => false,
							'msg' => 'Erro ao baixar o plugin: ' . $e->getMessage()
						));
						return false;
					}
				} else {
					interface_alerta(Array(
						'redirect' => false,
						'msg' => 'URL do repositório é obrigatória.'
					));
					return false;
				}
				break;
				
			case 'privado':
				$origem_tipo = 'github_privado';
				if(isset($_REQUEST['repo_privado_url']) && !empty($_REQUEST['repo_privado_url'])) {
					$url = $_REQUEST['repo_privado_url'];
					$token = isset($_REQUEST['repo_privado_token']) ? $_REQUEST['repo_privado_token'] : '';
					
					error_log("[PROCESS] Iniciando processamento de repositório privado: {$url}");
					
					// Verificar se é uma URL direta de download ou URL do repositório
					if (strpos($url, '/download/') !== false || strpos($url, '.zip') !== false) {
						// URL direta de download
						$download_url = $url;
						$origem_referencia = $url;
						error_log("[PROCESS] Usando URL direta de download: {$download_url}");
					} else {
						// URL do repositório - tentar descoberta automática
						try {
							error_log("[PROCESS] Iniciando descoberta automática com token");
							$release_info = admin_plugins_descobrir_ultima_tag_plugin($url, null, $token);
							$download_url = $release_info['download_url'];
							$sha256_url = $release_info['sha256_url'] ?? null;
							// Extrair owner/repo da URL para salvar como referência
							if (preg_match('#github\.com/([^/]+)/([^/]+)#', $url, $matches)) {
								$origem_referencia = $matches[1] . '/' . $matches[2]; // Salvar como owner/repo
							} else {
								$origem_referencia = $url; // Fallback para URL completa se regex falhar
							}
							$origem_branch_tag = $release_info['tag']; // Guardar tag descoberta
							error_log("[PROCESS] Descoberta concluída: {$origem_branch_tag} -> {$download_url}");
						} catch (Exception $e) {
							error_log("[PROCESS] ERRO na descoberta: " . $e->getMessage());
							interface_alerta(Array(
								'redirect' => false,
								'msg' => 'Erro na descoberta automática: ' . $e->getMessage()
							));
							return false;
						}
					}
					
					// Download com autenticação
					$unique_name = uniqid('plugin_priv_') . '.zip';
					$target_path = $plugins_dir . $unique_name;
					
					try {
						error_log("[PROCESS] Iniciando download para: {$target_path}");
						$downloaded_path = admin_plugins_download_release_plugin($download_url, $plugins_dir, $token, $sha256_url);
						rename($downloaded_path, $target_path);
						$arquivo_path = $target_path;
						$origem_credencial_ref = $token; // Guardar token
						error_log("[PROCESS] Download concluído: {$target_path}");
					} catch (Exception $e) {
						error_log("[PROCESS] ERRO no download: " . $e->getMessage());
						interface_alerta(Array(
							'redirect' => false,
							'msg' => 'Erro ao baixar o plugin: ' . $e->getMessage()
						));
						return false;
					}
				} else {
					error_log("[PROCESS] ERRO: URL do repositório privado não informada");
					interface_alerta(Array(
						'redirect' => false,
						'msg' => 'URL do repositório é obrigatória.'
					));
					return false;
				}
				break;
		}
	}
	
	return [
		'origem_tipo' => $origem_tipo,
		'origem_referencia' => $origem_referencia,
		'origem_branch_tag' => $origem_branch_tag,
		'origem_credencial_ref' => $origem_credencial_ref,
		'arquivo_path' => $arquivo_path
	];
}

// ===== Função de teste do sistema de descoberta automática
function admin_plugins_teste(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	// ===== Processar ações de teste
	$resultado_descoberta = '';
	$resultado_download = '';
	$resultado_processamento = '';

	if(isset($_REQUEST['acao'])){
		switch($_REQUEST['acao']){
			case 'testar_descoberta':
				if(isset($_REQUEST['teste_repo_url']) && !empty($_REQUEST['teste_repo_url'])){
					try {
						$repo_url = $_REQUEST['teste_repo_url'];
						$resultado = admin_plugins_descobrir_ultima_tag_plugin($repo_url);

						$resultado_descoberta = '
						<div class="ui success message">
							<div class="header">Descoberta realizada com sucesso!</div>
							<ul class="list">
								<li><strong>Tag:</strong> ' . $resultado['tag'] . '</li>
								<li><strong>Data de publicação:</strong> ' . $resultado['published_at'] . '</li>
								<li><strong>URL do Download:</strong> ' . $resultado['download_url'] . '</li>
							</ul>
						</div>';
					} catch (Exception $e) {
						$resultado_descoberta = '
						<div class="ui error message">
							<div class="header">Erro na descoberta</div>
							<p>' . $e->getMessage() . '</p>
						</div>';
					}
				} else {
					$resultado_descoberta = '
					<div class="ui warning message">
						<div class="header">URL do repositório é obrigatória</div>
					</div>';
				}
				break;

			case 'testar_download':
				if(isset($_REQUEST['teste_zip_url']) && !empty($_REQUEST['teste_zip_url'])){
					try {
						$zip_url = $_REQUEST['teste_zip_url'];
						$token = isset($_REQUEST['teste_token']) ? $_REQUEST['teste_token'] : '';

						// Pasta temporária para teste
						$temp_dir = sys_get_temp_dir() . '/plugin_test_' . uniqid();
						mkdir($temp_dir, 0755, true);

						$downloaded_path = admin_plugins_download_release_plugin($zip_url, $temp_dir, $token);

						$file_size = filesize($downloaded_path);
						$file_size_mb = round($file_size / 1024 / 1024, 2);

						$resultado_download = '
						<div class="ui success message">
							<div class="header">Download realizado com sucesso!</div>
							<ul class="list">
								<li><strong>URL:</strong> ' . $zip_url . '</li>
								<li><strong>Caminho do arquivo:</strong> ' . $downloaded_path . '</li>
								<li><strong>Tamanho:</strong> ' . $file_size_mb . ' MB</li>
							</ul>
						</div>';

						// Limpar arquivo de teste
						unlink($downloaded_path);
						rmdir($temp_dir);

					} catch (Exception $e) {
						$resultado_download = '
						<div class="ui error message">
							<div class="header">Erro no download</div>
							<p>' . $e->getMessage() . '</p>
						</div>';
					}
				} else {
					$resultado_download = '
					<div class="ui warning message">
						<div class="header">URL do ZIP é obrigatória</div>
					</div>';
				}
				break;

			case 'testar_processamento':
				if(isset($_REQUEST['teste_origem_url']) && !empty($_REQUEST['teste_origem_url'])){
					try {
						$origem_url = $_REQUEST['teste_origem_url'];

						// Simular $_REQUEST para a função
						$_REQUEST['origem_selecionada'] = 'publico';
						$_REQUEST['repo_publico_url'] = $origem_url;

						$resultado = admin_plugins_processar_origem($_REQUEST);

						if($resultado === false){
							$resultado_processamento = '
							<div class="ui error message">
								<div class="header">Erro no processamento</div>
								<p>Verifique os logs para mais detalhes.</p>
							</div>';
						} else {
							$resultado_processamento = '
							<div class="ui success message">
								<div class="header">Processamento realizado com sucesso!</div>
								<ul class="list">
									<li><strong>Tipo de origem:</strong> ' . $resultado['origem_tipo'] . '</li>
									<li><strong>Referência:</strong> ' . $resultado['origem_referencia'] . '</li>
									<li><strong>Branch/Tag:</strong> ' . ($resultado['origem_branch_tag'] ?: 'N/A') . '</li>
									<li><strong>Arquivo:</strong> ' . ($resultado['arquivo_path'] ?: 'N/A') . '</li>
								</ul>
							</div>';
						}
					} catch (Exception $e) {
						$resultado_processamento = '
						<div class="ui error message">
							<div class="header">Erro no processamento</div>
							<p>' . $e->getMessage() . '</p>
						</div>';
					}
				} else {
					$resultado_processamento = '
					<div class="ui warning message">
						<div class="header">URL de origem é obrigatória</div>
					</div>';
				}
				break;
		}
	}

	// ===== Incluir componentes

	// ===== Variáveis do módulo

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#resultado_descoberta#',$resultado_descoberta);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#resultado_download#',$resultado_download);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#resultado_processamento#',$resultado_processamento);
}

// ==== Start

function admin_plugins_start(){
	global $_GESTOR;
	
	// ===== Processamento CLI
	if (PHP_SAPI === 'cli') {
		// Parse argumentos CLI simples
		$args = [];
		$argv_cli = $_SERVER['argv'] ?? [];
		foreach ($argv_cli as $arg) {
			if (preg_match('/^--([^=]+)=(.+)$/', $arg, $matches)) {
				$args[$matches[1]] = $matches[2];
			} elseif (preg_match('/^--(.+)$/', $arg, $matches)) {
				$args[$matches[1]] = true;
			}
		}
		
		// Debug: mostrar argumentos parseados
		error_log("CLI ARGS: " . json_encode($args));
		error_log("RAW ARGV: " . json_encode($argv_cli));
		
		// Processar ação CLI
		if (isset($args['action']) && isset($args['plugin'])) {
			$action = $args['action'];
			$plugin = $args['plugin'];
			$force = isset($args['force']);
			
			switch ($action) {
				case 'install':
					$result = admin_plugins_executar_acao('instalar', $plugin, true);
					echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
					break;
				case 'update':
					$result = admin_plugins_executar_acao('atualizar', $plugin, true);
					echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
					break;
				case 'reprocess':
					$result = admin_plugins_executar_acao('reprocessar', $plugin, true);
					echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
					break;
				default:
					echo json_encode(['status' => 'erro', 'msg' => 'Ação CLI desconhecida: ' . $action], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
			}
		} else {
			echo json_encode(['status' => 'erro', 'msg' => 'Parâmetros insuficientes. Use: --action=install|update|reprocess --plugin=nome-plugin [--force]'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
		}
		
		exit;
	}
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'update': admin_plugins_ajax_update(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		admin_plugins_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': admin_plugins_adicionar(); break;
			case 'editar': admin_plugins_editar(); break;
			case 'executar': admin_plugins_executar(); break;
			case 'acao': admin_plugins_acao(); break;
			case 'teste': admin_plugins_teste(); break;
		}
		
		interface_finalizar();
	}
}

admin_plugins_start();

?>