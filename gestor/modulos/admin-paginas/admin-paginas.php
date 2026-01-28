<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-paginas';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-paginas.json'), true);

function admin_paginas_adicionar(){
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
					'campo' => 'pagina-nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				)
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["pagina-nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			),
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		));
		
		if($exiteCampo){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['paginaCaminho']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar/');
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled']);
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head']);
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "pagina-nome"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		$campo_nome = "layout_id"; $post_nome = 'layout';		 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "tipo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "framework_css"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "modulo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "opcao"; $post_nome = 'pagina-opcao'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "caminho"; $post_nome = 'paginaCaminho'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "html"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css"; $post_nome = $campo_nome; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css_compiled"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "html_extra_head"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "raiz"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		
		if(gestor_acesso('permissao-pagina')){
			$campo_nome = "sem_permissao"; $post_nome = $campo_nome; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		}
		
		// ===== Campos comuns
		
		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	// ===== Permissão de páginas
	
	if(!gestor_acesso('permissao-pagina')){
		$cel_nome = 'permissao-pagina'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	}	
	
	// Incluir o Componente Editor HTML na página

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente([
		'alvos' => 'paginas',
	]));

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
					'identificador' => 'pagina-nome',
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'language' => true,
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'layout',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-label')),
					'identificador' => 'layout',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'tipo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
					'identificador' => 'tipo',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'framework_css',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
					'identificador' => 'framework_css',
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'layout',
					'nome' => 'layout',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-placeholder')),
					'tabela' => Array(
						'nome' => 'layouts',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'gestorModule',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'where' => "modulo_grupo_id!='bibliotecas' AND language='".$_GESTOR['linguagem-codigo']."'",
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'type',
					'nome' => 'tipo',
					'selectClass' => 'pagina-tipo',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-placeholder')),
					'dados' => $modulo['resources'][$_GESTOR['linguagem-codigo']]['selectDadosTipo'],
					'valor_selecionado' => 'pagina',
				),
				Array(
					'tipo' => 'select',
					'id' => 'framework-css',
					'nome' => 'framework_css',
					'selectClass' => 'frameworkCSS',
					'valor_selecionado' => 'fomantic-ui',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
					'dados' => $modulo['selectDadosFrameworkCSS'],
				),
			)
		)
	);
}

function admin_paginas_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do registro
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'caminho',
		'layout_id', // substitui id_layouts
		'modulo',
		'tipo',
		'opcao',
		'raiz',
		'sem_permissao',
		'html',
		'css',
		'css_compiled', // Novo campo
		'html_extra_head', // Novo campo
		'framework_css',
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
			." AND language='".$_GESTOR['linguagem-codigo']."'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'pagina-nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				)
			)
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		));
		
		if($exiteCampo){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['paginaCaminho']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
		}
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'",
		);
		
		$campo_nome = "nome"; $request_name = 'pagina-nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se mudar o nome, mudar o identificador do registro
		
		if(isset($alterar_id)){
			$layouts = banco_select_name
			(
				banco_campos_virgulas(Array(
					$modulo['tabela']['id_numerico'],
				))
				,
				$modulo['tabela']['nome'],
				"WHERE ".$modulo['tabela']['id']."='".$id."'"
			);
			
			if($layouts){
				$id_novo = banco_identificador(Array(
					'id' => banco_escape_field($_REQUEST["pagina-nome"]),
					'tabela' => Array(
						'nome' => $modulo['tabela']['nome'],
						'campo' => $modulo['tabela']['id'],
						'id_nome' => $modulo['tabela']['id_numerico'],
						'id_valor' => $layouts[0][$modulo['tabela']['id_numerico']],
						'where' => "language='".$_GESTOR['linguagem-codigo']."'",
					),
				));
				
				$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
				$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
				$_GESTOR['modulo-registro-id'] = $id_novo;
			}
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled']);
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head']);
		
		// ===== Atualização dos demais campos.

		$campo_nome = "layout_id"; $request_name = 'layout'; $alteracoes_name = 'layout'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "tipo"; $request_name = $campo_nome; $alteracoes_name = 'type'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "framework_css"; $request_name = $campo_nome; $alteracoes_name = 'framework-css'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "modulo"; $request_name = $campo_nome; $alteracoes_name = 'module'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "opcao"; $request_name = 'pagina-opcao'; $alteracoes_name = 'option'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "caminho"; $request_name = 'paginaCaminho'; $alteracoes_name = 'path'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name])); $caminhoMudou = banco_select_campos_antes($campo_nome);}
		
		$campo_nome = "raiz"; $request_name = $campo_nome; $alteracoes_name = 'root'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? '1' : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($_REQUEST[$request_name]) ? '1' : 'NULL'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'filtro' => 'checkbox','valor_antes' => (banco_select_campos_antes($campo_nome) ? '1' : '0'),'valor_depois' => (isset($_REQUEST[$request_name]) ? '1' : '0'));}
		
		if(gestor_acesso('permissao-pagina')){
			$campo_nome = "sem_permissao"; $request_name = $campo_nome; $alteracoes_name = 'permission'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? '1' : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($_REQUEST[$request_name]) ? '1' : 'NULL'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'filtro' => 'checkbox','valor_antes' => (banco_select_campos_antes($campo_nome) ? '1' : '0'),'valor_depois' => (isset($_REQUEST[$request_name]) ? '1' : '0'));}
		}
		
		$campo_nome = "html"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}}
		$campo_nome = "css"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}}
		$campo_nome = "css_compiled"; $request_name = $campo_nome; $alteracoes_name = 'css-compiled'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}} // Novo campo
		$campo_nome = "html_extra_head"; $request_name = $campo_nome; $alteracoes_name = 'html-extra-head'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}} // Novo campo
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
			$campo_nome = 'user_modified'; $editar['dados'][] = $campo_nome." = 1";
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
				'id' => $id,
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'id_numerico' => $modulo['tabela']['id_numerico'],
					'versao' => $modulo['tabela']['versao'],
				),
				'alteracoes' => $alteracoes,
			));
			
			// ===== Se mudou o caminho, criar página 301 do caminho
			
			if(isset($caminhoMudou)){
				$campos = null; $campo_sem_aspas_simples = null;
				
				$campo_nome = "id_paginas"; $campo_valor = interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "caminho"; $campo_valor = $caminhoMudou; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				banco_insert_name
				(
					$campos,
					"paginas_301"
				);
			}
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Permissão de páginas
	
	if(!gestor_acesso('permissao-pagina')){
		$cel_nome = 'permissao-pagina'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	}	
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$caminho = (isset($retorno_bd['caminho']) ? $retorno_bd['caminho'] : '');
		$layout_id = (isset($retorno_bd['layout_id']) ? $retorno_bd['layout_id'] : '');
		$bd_modulo = (isset($retorno_bd['modulo']) ? $retorno_bd['modulo'] : '');
		$tipo = (isset($retorno_bd['tipo']) ? $retorno_bd['tipo'] : '');
		$framework_css = (isset($retorno_bd['framework_css']) ? $retorno_bd['framework_css'] : '');
		$opcao = (isset($retorno_bd['opcao']) ? $retorno_bd['opcao'] : '');
		$html = (isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		$css_compiled = (isset($retorno_bd['css_compiled']) ? $retorno_bd['css_compiled'] : '');
		$html_extra_head = (isset($retorno_bd['html_extra_head']) ? $retorno_bd['html_extra_head'] : '');
		$raiz = (isset($retorno_bd['raiz']) ? true : false);
		$sem_permissao = (isset($retorno_bd['sem_permissao']) ? true : false);
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
		$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
		$css_compiled = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css_compiled);
		$html_extra_head = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html_extra_head);

		// ===== Detectar classe do editor Quill

		if (strpos($html, 'ql-container ql-snow') !== false) {
			gestor_js_variavel_incluir('publisherQuillClassDetected',true);
		}
		
		// ===== Alterar demais variáveis.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#url#','http'.(isset($_SERVER['HTTPS']) ? 's':'').'://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].($caminho == '/' ? '' : $caminho));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#caminho#',$caminho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#opcao#',$opcao);
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#raiz#',($raiz ? 'checked' : ''));
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#sem_permissao#',($sem_permissao ? 'checked' : ''));

		$variaveisTrocarDepois['pagina-css'] = $css;
		$variaveisTrocarDepois['pagina-css-compiled'] = $css_compiled;
		$variaveisTrocarDepois['pagina-html'] = $html;
		$variaveisTrocarDepois['pagina-html-extra-head'] = $html_extra_head;

		// Incluir o Componente Editor HTML na página

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente([
			'editar' => true,
			'modulo' => $modulo,
			'alvos' => 'paginas',
		]));

		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'variaveisTrocarDepois' => $variaveisTrocarDepois,
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
			'clonar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/clonar/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-clone')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-clone')),
				'icon' => 'clone',
				'cor' => 'teal',
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
					'identificador' => 'pagina-nome',
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'language' => true,
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'layout',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-label')),
					'identificador' => 'layout',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'tipo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
					'identificador' => 'tipo',
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'layout',
					'nome' => 'layout',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-placeholder')),
					'tabela' => Array(
						'nome' => 'layouts',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $layout_id,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'gestorModule',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $bd_modulo,
						'where' => "modulo_grupo_id!='bibliotecas' AND language='".$_GESTOR['linguagem-codigo']."'",
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'type',
					'nome' => 'tipo',
					'selectClass' => 'pagina-tipo',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-placeholder')),
					'valor_selecionado' => $tipo,
					'dados' => $modulo['resources'][$_GESTOR['linguagem-codigo']]['selectDadosTipo'],
				),
				Array(
					'tipo' => 'select',
					'id' => 'framework-css',
					'nome' => 'framework_css',
					'selectClass' => 'frameworkCSS',
					'valor_selecionado' => $framework_css,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
					'dados' => $modulo['selectDadosFrameworkCSS'],
				),
			)
		)
	);
}

function admin_paginas_clonar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do registro a ser clonado.
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para clonar.

	$camposBanco = Array(
		'caminho',
		'layout_id', // substitui id_layouts
		'modulo',
		'tipo',
		'opcao',
		'raiz',
		'sem_permissao',
		'html',
		'css',
		'css_compiled', // Novo campo
		'html_extra_head', // Novo campo
		'framework_css',
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoClonar = array_merge($camposBanco,$camposBancoPadrao);
	
	// ===== Gravar registro no Banco

	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'pagina-nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				)
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["pagina-nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			),
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		));
		
		if($exiteCampo){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['paginaCaminho']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar/');
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled']);
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head']);
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "pagina-nome"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		$campo_nome = "layout_id"; $post_nome = 'layout';		 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "tipo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "framework_css"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "modulo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "opcao"; $post_nome = 'pagina-opcao'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "caminho"; $post_nome = 'paginaCaminho'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "html"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css"; $post_nome = $campo_nome; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css_compiled"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "html_extra_head"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "raiz"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		
		if(gestor_acesso('permissao-pagina')){
			$campo_nome = "sem_permissao"; $post_nome = $campo_nome; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		}
		
		// ===== Campos comuns
		
		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	// ===== Permissão de páginas
	
	if(!gestor_acesso('permissao-pagina')){
		$cel_nome = 'permissao-pagina'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	}	
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoClonar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$caminho = (isset($retorno_bd['caminho']) ? $retorno_bd['caminho'] : '');
		$layout_id = (isset($retorno_bd['layout_id']) ? $retorno_bd['layout_id'] : '');
		$bd_modulo = (isset($retorno_bd['modulo']) ? $retorno_bd['modulo'] : '');
		$tipo = (isset($retorno_bd['tipo']) ? $retorno_bd['tipo'] : '');
		$framework_css = (isset($retorno_bd['framework_css']) ? $retorno_bd['framework_css'] : '');
		$opcao = (isset($retorno_bd['opcao']) ? $retorno_bd['opcao'] : '');
		$html = (isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		$css_compiled = (isset($retorno_bd['css_compiled']) ? $retorno_bd['css_compiled'] : '');
		$html_extra_head = (isset($retorno_bd['html_extra_head']) ? $retorno_bd['html_extra_head'] : '');
		$raiz = (isset($retorno_bd['raiz']) ? true : false);
		$sem_permissao = (isset($retorno_bd['sem_permissao']) ? true : false);
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
		$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
		$css_compiled = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css_compiled);
		$html_extra_head = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html_extra_head);

		// ===== Detectar classe do editor Quill

		if (strpos($html, 'ql-container ql-snow') !== false) {
			gestor_js_variavel_incluir('publisherQuillClassDetected',true);
		}
		
		// ===== Alterar demais variáveis.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#caminho#',$caminho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#opcao#',$opcao);
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#raiz#',($raiz ? 'checked' : ''));
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#sem_permissao#',($sem_permissao ? 'checked' : ''));

		$variaveisTrocarDepois['pagina-css'] = $css;
		$variaveisTrocarDepois['pagina-css-compiled'] = $css_compiled;
		$variaveisTrocarDepois['pagina-html'] = $html;
		$variaveisTrocarDepois['pagina-html-extra-head'] = $html_extra_head;

		// Incluir o Componente Editor HTML na página

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente([
			'editar' => true,
			'modulo' => $modulo,
			'alvos' => 'paginas',
		]));
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface clonar finalizar opções
	
	$_GESTOR['interface']['clonar']['finalizar'] = Array(
		'variaveisTrocarDepois' => $variaveisTrocarDepois,
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
					'identificador' => 'pagina-nome',
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'language' => true,
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'layout',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-label')),
					'identificador' => 'layout',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'tipo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
					'identificador' => 'tipo',
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'layout',
					'nome' => 'layout',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-placeholder')),
					'tabela' => Array(
						'nome' => 'layouts',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $layout_id,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'gestorModule',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $bd_modulo,
						'where' => "modulo_grupo_id!='bibliotecas' AND language='".$_GESTOR['linguagem-codigo']."'",
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'type',
					'nome' => 'tipo',
					'selectClass' => 'pagina-tipo',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-placeholder')),
					'valor_selecionado' => $tipo,
					'dados' => $modulo['resources'][$_GESTOR['linguagem-codigo']]['selectDadosTipo'],
				),
				Array(
					'tipo' => 'select',
					'id' => 'framework-css',
					'nome' => 'framework_css',
					'selectClass' => 'frameworkCSS',
					'valor_selecionado' => $framework_css,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
					'dados' => $modulo['selectDadosFrameworkCSS'],
				),
			)
		)
	);
}

function admin_paginas_listar_cabecalho(){
	global $_GESTOR;

	// ===== Inclusão Módulo JS

	if ($_GESTOR['opcao'] == 'listar') {
		gestor_pagina_javascript_incluir();
	}

	// ===== Pegar o componente.

	$tipo = $_REQUEST['tipo'] ?? 'pagina';
	$checked = ' checked="checked"';

	$lista_pagina_ou_sistema = gestor_componente(Array(
		'id' => 'lista-pagina-ou-sistema',
		'modulo' => $_GESTOR['modulo-id'],
	));

	$lista_pagina_ou_sistema = modelo_var_troca($lista_pagina_ou_sistema,'#ambos#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-ambos-label')));
	$lista_pagina_ou_sistema = modelo_var_troca($lista_pagina_ou_sistema,'#pagina#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-pagina-label')));
	$lista_pagina_ou_sistema = modelo_var_troca($lista_pagina_ou_sistema,'#sistema#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-sistema-label')));
	$lista_pagina_ou_sistema = modelo_var_troca($lista_pagina_ou_sistema,'#tipo#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-tipo-label')));

	$lista_pagina_ou_sistema = modelo_var_troca($lista_pagina_ou_sistema,'#checked_ambos#',$tipo == 'ambos' ? $checked : '');
	$lista_pagina_ou_sistema = modelo_var_troca($lista_pagina_ou_sistema,'#checked_pagina#',$tipo == 'pagina' ? $checked : '');
	$lista_pagina_ou_sistema = modelo_var_troca($lista_pagina_ou_sistema,'#checked_sistema#',$tipo == 'sistema' ? $checked : '');

	return [
		'cabecalho' => $lista_pagina_ou_sistema,
		'tipo' => $tipo,
	];
}

function admin_paginas_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	$dados = admin_paginas_listar_cabecalho();

	$tipo_nao_visivel = $dados['tipo'] != 'ambos' ? true : null;

	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'tipo',
						'modulo',
						'caminho',
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "language='".$_GESTOR['linguagem-codigo']."'" . ($dados['tipo'] != 'ambos' && $dados['tipo'] != '' ? " AND tipo='{$dados['tipo']}'" : ''),
				),
				'tabela' => Array(
					'cabecalho' => $dados['cabecalho'],
					'rodape' => true,
					'colunas' => Array(
						Array(
							'id' => 'nome',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'tipo',
							'nao_visivel' => $tipo_nao_visivel,
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
							'formatar' => Array(
								'id' => 'outroConjunto',
								'conjunto' => [
									[
										'alvo' => 'sistema',
										'troca' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-system')),
									],
									[
										'alvo' => 'pagina',
										'troca' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-page')),
									],
								],
							)
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
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
								),
							)
						),
						Array(
							'id' => 'caminho',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-url-path')),
							'ordenar' => 'asc',
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
					'clonar' => Array(
						'url' => 'clonar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-clone')),
						'icon' => 'clone',
						'cor' => 'basic teal',
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

function admin_paginas_ajax_editor_html_switch(){
	global $_GESTOR;
	
	if($_REQUEST['editor_checked'] == 'sim'){
		$valor = true;
	} else {
		$valor = false;
	}
	
	gestor_variaveis_alterar(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'id' => 'editor-html-switch',
		'tipo' => 'bool',
		'valor' => $valor,
	));
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function admin_paginas_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'editor-html-switch': admin_paginas_ajax_editor_html_switch(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		admin_paginas_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': admin_paginas_adicionar(); break;
			case 'editar': admin_paginas_editar(); break;
			case 'clonar': admin_paginas_clonar(); break;
		}
		
		interface_finalizar();
	}
}

admin_paginas_start();

?>