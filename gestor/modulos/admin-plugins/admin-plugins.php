<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-plugins';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-plugins.json'), true);

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
				$alteracoes[] = Array('campo'=>$c,'valor_antes'=>banco_select_campos_antes($c),'valor_depois'=>banco_escape_field($_REQUEST[$c]));
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
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#publico_active#',($origem_tipo == 'publico' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#privado_active#',($origem_tipo == 'privado' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#arquivo_segment#',($origem_tipo == 'arquivo' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#publico_segment#',($origem_tipo == 'publico' ? 'active' : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#privado_segment#',($origem_tipo == 'privado' ? 'active' : ''));
		
		// Substituir valores dos campos de origem
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#repo_publico_url#',(isset($retorno_bd['origem_referencia']) && $retorno_bd['origem_tipo'] == 'publico' ? $retorno_bd['origem_referencia'] : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#repo_privado_url#',(isset($retorno_bd['origem_referencia']) && $retorno_bd['origem_tipo'] == 'privado' ? $retorno_bd['origem_referencia'] : ''));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#repo_privado_token#',(isset($retorno_bd['origem_credencial_ref']) ? $retorno_bd['origem_credencial_ref'] : ''));
		
		// ===== Popular os metaDados

		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');

		// ===== Metadados extras do plugin (Fase 1)
		$camposExtra = [
			'origem_tipo'=>'Origem',
			'origem_referencia'=>'Referência',
			'origem_branch_tag'=>'Branch/Tag',
			'origem_credencial_ref'=>'Credencial Ref',
			'versao_instalada'=>'Versão Instalada',
			'checksum_pacote'=>'Checksum',
			'status_execucao'=>'Status Execução',
			'data_ultima_atualizacao'=>'Última Atualização'
		];
		foreach($camposExtra as $c=>$label){
			if(isset($retorno_bd[$c])){
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
				'cor' => 'blue',
			),
			'atualizar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=acao&acao=atualizar&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => 'Atualizar',
				'tooltip' => 'Atualizar plugin (se checksum diferente)',
				'icon' => 'sync',
				'cor' => 'olive',
			),
			'reprocessar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=acao&acao=reprocessar&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => 'Reprocessar',
				'tooltip' => 'Forçar reprocessamento (ignora checksum)',
				'icon' => 'redo',
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
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/theme/tomorrow-night-bright.css" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/selection/active-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/edit/matchbrackets.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/javascript/javascript.js"></script>';
	
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
						'cor' => 'basic blue',
					),
					'atualizar' => Array(
						'opcao' => 'acao',
						'acao' => 'atualizar',
						'tooltip' => 'Atualizar',
						'icon' => 'sync',
						'cor' => 'basic olive',
					),
					'reprocessar' => Array(
						'opcao' => 'acao',
						'acao' => 'reprocessar',
						'tooltip' => 'Forçar Reprocessar',
						'icon' => 'redo',
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
	require_once __DIR__.'/../../controladores/plugins/atualizacao-plugin.php';

	// Preparar parâmetros para chamada direta
	$params = [
		'slug' => $row['id'],
		'origem_tipo' => $row['origem_tipo'],
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
	];

	// Interpretar origem_referencia (para github: owner/repo) ou local path
	if($row['origem_tipo']==='github_publico' || $row['origem_tipo']==='github_privado'){
		if(strpos($row['origem_referencia'],'/')!==false){
			list($params['owner'],$params['repo'])=explode('/', $row['origem_referencia'],2);
		}
		if(!empty($row['origem_branch_tag'])) $params['ref'] = $row['origem_branch_tag'];
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
function admin_plugins_descobrir_ultima_tag_plugin(string $repo_url, string $plugin_id = null): array {
    // Extrair owner/repo da URL do GitHub
    if (preg_match('#github\.com/([^/]+)/([^/]+)#', $repo_url, $matches)) {
        $owner = $matches[1];
        $repo = $matches[2];
        
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Conn2Flow-Plugin-Manager/1.0',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json']
        ]);
        
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($code !== 200 || !$resp) {
            throw new Exception('Falha ao buscar releases do GitHub HTTP ' . $code . ' ' . $err);
        }
        
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            throw new Exception('JSON de releases inválido');
        }
        
        // Procurar pela tag mais recente do plugin
        $prefix = 'plugin-';
        if ($plugin_id) {
            $prefix = 'plugin-' . $plugin_id . '-';
        }
        
        $latest_release = null;
        foreach ($data as $release) {
            if (!empty($release['tag_name']) && strpos($release['tag_name'], $prefix) === 0) {
                if (!$latest_release || strtotime($release['published_at']) > strtotime($latest_release['published_at'])) {
                    $latest_release = $release;
                }
            }
        }
        
        if ($latest_release) {
            return [
                'tag' => $latest_release['tag_name'],
                'published_at' => $latest_release['published_at'],
                'zip_url' => "https://github.com/{$owner}/{$repo}/releases/download/{$latest_release['tag_name']}/gestor-plugin.zip"
            ];
        }
        
        throw new Exception('Nenhuma tag de plugin encontrada no repositório');
    } else {
        throw new Exception('URL do GitHub inválida para descoberta automática');
    }
}

function admin_plugins_download_release_plugin(string $zip_url, string $dest_dir, string $token = null): string {
    $zip_path = rtrim($dest_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugin.zip';
    
    $ch = curl_init($zip_url);
    $fp = fopen($zip_path, 'wb');
    
    $headers = ['User-Agent: Conn2Flow-Plugin-Manager/1.0'];
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
    
    $success = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    
    if (!$success || $http_code !== 200) {
        unlink($zip_path);
        throw new Exception('Falha ao baixar plugin HTTP ' . $http_code . ' ' . $err);
    }
    
    if (filesize($zip_path) === 0) {
        unlink($zip_path);
        throw new Exception('Arquivo baixado está vazio');
    }
    
    return $zip_path;
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
	$plugins_dir = $_GESTOR['raiz'] . 'contents/plugins/';
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
				$origem_tipo = 'publico';
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
							$download_url = $release_info['zip_url'];
							$origem_referencia = $url; // Guardar URL do repo
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
						$downloaded_path = admin_plugins_download_release_plugin($download_url, $plugins_dir);
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
				$origem_tipo = 'privado';
				if(isset($_REQUEST['repo_privado_url']) && !empty($_REQUEST['repo_privado_url'])) {
					$url = $_REQUEST['repo_privado_url'];
					$token = isset($_REQUEST['repo_privado_token']) ? $_REQUEST['repo_privado_token'] : '';
					
					// Verificar se é uma URL direta de download ou URL do repositório
					if (strpos($url, '/download/') !== false || strpos($url, '.zip') !== false) {
						// URL direta de download
						$download_url = $url;
						$origem_referencia = $url;
					} else {
						// URL do repositório - tentar descoberta automática
						try {
							$release_info = admin_plugins_descobrir_ultima_tag_plugin($url);
							$download_url = $release_info['zip_url'];
							$origem_referencia = $url; // Guardar URL do repo
							$origem_branch_tag = $release_info['tag']; // Guardar tag descoberta
						} catch (Exception $e) {
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
						$downloaded_path = admin_plugins_download_release_plugin($download_url, $plugins_dir, $token);
						rename($downloaded_path, $target_path);
						$arquivo_path = $target_path;
						$origem_credencial_ref = $token; // Guardar token
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
								<li><strong>URL do ZIP:</strong> ' . $resultado['zip_url'] . '</li>
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