<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'componentes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_componentes',
		'id' => 'id',
		'id_numerico' => 'id_hosts_componentes',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
		'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
	),
);

function componentes_adicionar(){
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
				'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
			),
		));
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "modulo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css"; $post_nome = $campo_nome; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		// ===== Campos comuns
		
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		// ===== Incluir os dados no host do cliente.
		
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_componentes(Array(
			'opcao' => 'adicionar',
			'id' => $id,
		));
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
		}
		
		// ===== Redirecionar para o registro incluído.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Inclusão do CodeMirror
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/theme/tomorrow-night-bright.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.css" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/selection/active-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/searchcursor.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/search.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/scroll/annotatescrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/jump-to-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/edit/matchbrackets.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/xml/xml.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/css/css.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/htmlmixed/htmlmixed.js"></script>';
	
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
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'three column',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'where' => "id_modulos='30'",
					),
				),
			),
		)
	);
}

function componentes_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'html',
		'css',
		'modulo',
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
			." AND id_hosts='".$_GESTOR['host-id']."'"
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
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND id_hosts='".$_GESTOR['host-id']."'",
		);
		
		$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se mudar o nome, mudar o identificador do registro
		
		if(isset($alterar_id)){
			$id_novo = banco_identificador(Array(
				'id' => banco_escape_field($_REQUEST["nome"]),
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campo' => $modulo['tabela']['id'],
					'id_nome' => $modulo['tabela']['id_numerico'],
					'id_valor' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
				),
			));
			
			$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		
		// ===== Atualização dos demais campos.
		
		$campo_nome = "html"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label'); if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => banco_select_campos_antes($campo_nome));}}
		$campo_nome = "css"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label'); if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => banco_select_campos_antes($campo_nome));}}
		$campo_nome = "modulo"; $request_name = $campo_nome; $alteracoes_name = 'module'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
			
			// ===== Se a página é proveniente de um template, então marcar como modificada para não ser automaticamente alterada nas atualizações e manter a mudança personalizada.
			
			$template_padrao = interface_modulo_variavel_valor(Array('variavel' => 'template_padrao'));
			
			if($template_padrao){
				$campo_nome = 'template_modificado'; $editar['dados'][] = $campo_nome."=1";
			}
			
			// ===== Editar campos padrões
			
			$campo_nome = $modulo['tabela']['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = $modulo['tabela']['data_modificacao']; $editar['dados'][] = $campo_nome."=NOW()";
			
			$editar['sql'] = banco_campos_virgulas($editar['dados']);
			
			if($editar['sql']){
				banco_update
				(
					$editar['sql'],
					$editar['tabela'],
					$editar['extra']
				);
			}
			$editar = false;
			
			// ===== Incluir no backup os campos.
			
			if(isset($backups)){
				foreach($backups as $backup){
					interface_backup_campo_incluir(Array(
						'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
						'versao' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['versao'])),
						'campo' => $backup['campo'],
						'valor' => $backup['valor'],
					));
				}
			}
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Incluir os dados no host do cliente.
		
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_componentes(Array(
			'opcao' => 'editar',
			'id' => $id,
		));
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão do CodeMirror
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/theme/tomorrow-night-bright.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.css" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/selection/active-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/searchcursor.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/search.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/scroll/annotatescrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/jump-to-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/edit/matchbrackets.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/xml/xml.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/css/css.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/htmlmixed/htmlmixed.js"></script>';
	
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
		." AND id_hosts='".$_GESTOR['host-id']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$html = (isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		$bd_modulo = (isset($retorno_bd['modulo']) ? $retorno_bd['modulo'] : '');
		$css_gestor = '';
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
		$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
		
		// ===== Alterar demais variáveis.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-id#',$id);
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-css#',$css);
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-css-gestor#',$css_gestor);
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-html#',$html);
		
		// ===== Dropdown com todos os backups
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-html-backup#',interface_backup_campo_select(Array(
			'campo' => 'html',
			'callback' => 'adminLayoutsBackupCampo',
			'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
		)));
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-css-backup#',interface_backup_campo_select(Array(
			'campo' => 'css',
			'callback' => 'adminLayoutsBackupCampo',
			'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
		)));
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
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
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'three column',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $bd_modulo,
						'where' => "id_modulos='30'",
					),
				),
			),
		)
	);
}

function componentes_status(){
	global $_GESTOR;
	
	$id = $_GESTOR['modulo-registro-id'];
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_componentes(Array(
		'opcao' => 'status',
		'id' => $id,
	));
	
	if(!$retorno['completed']){
		$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
		
		$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
	}
}

function componentes_excluir(){
	global $_GESTOR;
	
	$id_numerico = $_GESTOR['modulo-registro-id-numerico'];
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_componentes(Array(
		'opcao' => 'excluir',
		'id_numerico' => $id_numerico,
	));
	
	if(!$retorno['completed']){
		$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
		
		$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
	}
}

function componentes_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'modulo',
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
							'id' => 'modulo',
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'module-name')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">N/A</span>',
								'tabela' => Array(
									'nome' => 'modulos',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
								),
							)
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
		case 'status':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'componentes_status',
			);
		break;
		case 'excluir':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'componentes_excluir',
			);
		break;
	}
}

// ==== Start

function componentes_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': componentes_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		componentes_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': componentes_adicionar(); break;
			case 'editar': componentes_editar(); break;
		}
		
		interface_finalizar();
	}
}

componentes_start();

?>