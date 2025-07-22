<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'postagens';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.7',
	'bibliotecas' => Array('interface','html','pagina','formato'),
	'tabela' => Array(
		'nome' => 'hosts_postagens',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_postagens',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
		'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
	),
	'verificarCamposOutraTabela' => Array(
		'caminho' => Array(
			'nome' => 'hosts_paginas',
			'id_numerico' => 'id_'.'hosts_paginas',
			'id' => 'id',
			'status' => 'status',
			'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
		),
	)
);

// ===== Funções auxiliares

function postagens_pagina($params = false){
	/**********
		Descrição: adicionar/editar a página do postagem.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção para saber se vai adicionar ou editar uma página.
	// id_postagens - String - Obrigatório - Identificador numérico do postagem.
	
	// Se opcao = 'editar'
	
	// nomeMudou - Bool - Opcional - Alterar o nome da página.
	// caminhoMudou - Bool - Opcional - Alterar o caminho da página.
	
	// Se opcao = 'status'
	
	// status - String - Obrigatório - Status atual do postagem.
	
	// ===== 
	
	if(isset($opcao) && isset($id_postagens)){
		// ===== Modulo dados.
		
		$tabela = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela'];
		
		// ===== Opção operações.
		
		switch($opcao){
			case 'adicionar':
			case 'editar':
				// ===== Buscar dados do postagem.
				
				$postagens = banco_select_name(
					banco_campos_virgulas(Array(
						'template_id',
						'template_tipo',
						'id_hosts_arquivos_Imagem',
						'id_hosts_paginas',
						'nome',
						'caminho',
						'id',
						'descricao',
					)),
					$tabela['nome'],
					"WHERE ".$tabela['id_numerico']."='".$id_postagens."'"
				);
				
				$postagens = $postagens[0];
				
				// ===== Buscar dados do template.
				
				switch($postagens['template_tipo']){
					case 'gestor':
						$templates = banco_select_name(
							banco_campos_virgulas(Array(
								'html',
								'css',
								'versao',
							)),
							"templates",
							"WHERE id='".$postagens['template_id']."'"
							." AND status!='D'"
						); $templates = $templates[0];
						
						$html = $templates['html'];
						$css = $templates['css'];
						$template_versao = $templates['versao'];
					break;
					case 'hosts':
						$templates = banco_select_name(
							banco_campos_virgulas(Array(
								'html',
								'css',
								'versao',
							)),
							"hosts_templates",
							"WHERE id='".$postagens['template_id']."'"
							." AND status!='D'"
							." AND id_hosts='".$_GESTOR['host-id']."'"
						); $templates = $templates[0];
						
						$html = $templates['html'];
						$css = $templates['css'];
						$template_versao = $templates['versao'];
						
						// ===== Adicionar o prefixo hosts para diferenciar o template id com o do gestor. É necessário pois o template_id pode ser o mesmo em templates diferentes.
						
						$postagens['template_id'] = 'hosts_'.$postagens['template_id'];
					break;
					default:
						$html = '';
						$css = '';
						$template_versao = '';
				}
				
				// ===== Desmascarar o HTML e CSS.
			
				$html = pagina_variaveis_globais_desmascarar(Array('valor' => $html));
				$css = pagina_variaveis_globais_desmascarar(Array('valor' => $css));
				
				// ===== Pegar o ID do layout.
				
				$config = gestor_incluir_configuracao(Array(
					'id' => 'templates.config',
				));
				
				$layoutsIds = $config['layouts']['postagens'];
				
				$hosts_layouts = banco_select_name(
					banco_campos_virgulas(Array(
						'id_hosts_layouts',
					)),
					"hosts_layouts",
					"WHERE id='".$layoutsIds."'"
					." AND status!='D'"
					." AND id_hosts='".$_GESTOR['host-id']."'"
				); $hosts_layouts = $hosts_layouts[0];
				
				$id_hosts_layouts = $hosts_layouts['id_hosts_layouts'];
				
				// ===== Verificar se a página não foi excluída, se for criar uma nova.
				
				$paginaOpcao = $opcao;
				
				if(existe($postagens['id_hosts_paginas'])){
					$hosts_paginas = banco_select_name(
						banco_campos_virgulas(Array(
							'id_hosts_paginas',
						)),
						"hosts_paginas",
						"WHERE id_hosts_paginas='".$postagens['id_hosts_paginas']."'"
						." AND status!='D'"
					);
					
					if(!$hosts_paginas){
						$paginaOpcao = 'adicionar';
					}
				}
				
				// ===== Criar a nova página ou atualizar a página atual com do postagem.
				
				switch($paginaOpcao){
					case 'adicionar':
						// ===== Adicionar página do postagem.
						
						$id_hosts_paginas = pagina_adicionar(Array(
							'dados' => Array(
								'nome' => $postagens['nome'],
								'id_hosts_layouts' => $id_hosts_layouts,
								'caminho' => $postagens['caminho'],
								'tipo' => 'pagina',
								'html' => $html,
								'css' => $css,
								'modulo' => 'postagens-host',
								'sem_permissao' => '1',
								'modulo_id_registro' => $id_postagens,
								'template_padrao' => '1',
								'template_categoria' => 'postagens',
								'template_id' => $postagens['template_id'],
								'template_versao' => $template_versao,
							),
						));
						
						// ===== Atualizar o postagem e adicionar o id_hosts_paginas.
						
						banco_update
						(
							"id_hosts_paginas='".$id_hosts_paginas."'",
							$tabela['nome'],
							"WHERE ".$tabela['id_numerico']."='".$id_postagens."'"
						);
					break;
					case 'editar':
						// ===== Editar página do postagem.
						
						pagina_editar(Array(
							'id_hosts_paginas' => $postagens['id_hosts_paginas'],
							'dados' => Array(
								'nome' => (isset($nomeMudou) ? $postagens['nome'] : null),
								'caminho' => (isset($caminhoMudou) ? $postagens['caminho'] : null),
								'html' => $html,
								'css' => $css,
								'sem_permissao' => '1',
								'template_padrao' => '1',
								'template_categoria' => 'postagens',
								'template_id' => $postagens['template_id'],
								'template_versao' => $template_versao,
							),
						));
					break;
				}
			break;
			case 'status':
				if(isset($status)){
					// ===== Buscar dados do postagem.
					
					$postagens = banco_select(Array(
						'unico' => true,
						'tabela' => $tabela['nome'],
						'campos' => Array(
							'id_hosts_paginas',
						),
						'extra' => 
							"WHERE ".$tabela['id_numerico']."='".$id_postagens."'"
					));
					
					// ===== Alterar status da página.
					
					pagina_status(Array(
						'id_hosts_paginas' => $postagens['id_hosts_paginas'],
						'status' => $status,
					));
				}
			break;
			case 'excluir':
				// ===== Buscar dados do postagem.
				
				$postagens = banco_select(Array(
					'unico' => true,
					'tabela' => $tabela['nome'],
					'campos' => Array(
						'id_hosts_paginas',
					),
					'extra' => 
						"WHERE ".$tabela['id_numerico']."='".$id_postagens."'"
				));
				
				// ===== Alterar status da página.
				
				pagina_excluir(Array(
					'id_hosts_paginas' => $postagens['id_hosts_paginas'],
				));
			break;
		}
	}
}

// ===== Funções principais

function postagens_adicionar(){
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
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				)
			)
		));
		
		// ===== Definição do identificador do postagem.
		
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
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_hosts_arquivos_Imagem"; $post_nome = 'imagem'; 				if($_REQUEST[$post_nome] && $_REQUEST[$post_nome] != '-1')		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		$campo_nome = "descricao"; $post_nome = $campo_nome; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field(pagina_variaveis_globais_mascarar(Array('valor' => $_REQUEST[$post_nome]))));
		
		$campo_nome = "template_id"; $post_nome = "template_id"; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "template_tipo"; $post_nome = "template_tipo"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "caminho"; $post_nome = 'paginaCaminho'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
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
		
		$id_postagens = banco_last_id();
		
		// ===== Criar a página do postagem baseado no template escolhido.
		
		postagens_pagina(Array(
			'opcao' => 'adicionar',
			'id_postagens' => $id_postagens,
		));
		
		// ===== Incluir os dados no host do cliente.
		
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_postagens(Array(
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
	
	// ===== Inclusão do TinyMCE
	
	$_GESTOR['javascript'][] = '<script src="https://cdn.tiny.cloud/1/puqfgloszrueuf7nkzrlzxqbc0qihojtiq46oikukhty0jw9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>';
	
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
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
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
			),
			'campos' => Array(
				Array(
					'tipo' => 'imagepick-hosts',
					'id' => 'thumbnail',
					'nome' => 'imagem',
				),
				Array(
					'tipo' => 'templates-hosts',
					'id' => 'template',
					'nome' => 'template',
					'categoria_id' => 'postagens',
				)
			),
		)
	);
}

function postagens_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'id_hosts_arquivos_Imagem',
		'descricao',
		'template_id',
		'template_tipo',
		'caminho',
		'id_hosts_paginas',
		$modulo['tabela']['id_numerico'],
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
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'"
				." AND id_hosts='".$_GESTOR['host-id']."'",
		);
		
		$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name])); $nomeMudou = true;}
		
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
		
		// ===== Atualização dos demais campos.
		
		$campo_nome = "id_hosts_arquivos_Imagem"; $request_name = 'imagem'; $alteracoes_name = 'thumbnail'; if($_REQUEST[$request_name] == '-1'){$_REQUEST[$request_name] = NULL;} if(banco_select_campos_antes($campo_nome) != $_REQUEST[$request_name]){$editar['dados'][] = $campo_nome."=".(isset($_REQUEST[$request_name]) ? "'" . banco_escape_field($_REQUEST[$request_name]) . "'" : "NULL"); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => (isset($_REQUEST[$request_name]) ? banco_escape_field($_REQUEST[$request_name]) : NULL),'tabela' => Array(
				'nome' => 'hosts_arquivos',
				'campo' => 'nome',
				'id_numerico' => 'id_hosts_arquivos',
			));}
			
		$campo_nome = "descricao"; $request_name = $campo_nome; $alteracoes_name = 'description'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field(pagina_variaveis_globais_mascarar(Array('valor' => $_REQUEST[$request_name]))) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}}
		
		$campo_nome = "template_id"; $request_name = $campo_nome; $alteracoes_name = 'template'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');}
		$campo_nome = "template_tipo"; $request_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'";}
		
		$campo_nome = "caminho"; $request_name = 'paginaCaminho'; $alteracoes_name = 'path'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name])); $caminhoMudou = banco_select_campos_antes($campo_nome);}
		
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
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
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
			
			// ===== Se mudou o caminho, criar página 301 do caminho
			
			if(isset($caminhoMudou)){
				$id_hosts_paginas_301 = pagina_301(Array(
					'caminho' => $caminhoMudou,
					'id_hosts_paginas' => banco_select_campos_antes('id_hosts_paginas'),
				));
			}
			
			// ===== Modificar a página do postagem baseado no template escolhido.
		
			postagens_pagina(Array(
				'opcao' => 'editar',
				'id_postagens' => banco_select_campos_antes($modulo['tabela']['id_numerico']),
				'nomeMudou' => (isset($nomeMudou) ? true : null),
				'caminhoMudou' => (isset($caminhoMudou) ? true : null),
			));
			
			// ===== Incluir os dados no host do cliente.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_postagens(Array(
				'opcao' => 'editar',
				'id' => $id,
				'caminhoMudou' => (isset($caminhoMudou) ? Array( 
					'id_hosts_paginas_301' => $id_hosts_paginas_301,
					'caminho' => $caminhoMudou,
				) : NULL),
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
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão do TinyMCE
	
	$_GESTOR['javascript'][] = '<script src="https://cdn.tiny.cloud/1/puqfgloszrueuf7nkzrlzxqbc0qihojtiq46oikukhty0jw9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>';
	
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
		$id_hosts_arquivos = (isset($retorno_bd['id_hosts_arquivos_Imagem']) ? $retorno_bd['id_hosts_arquivos_Imagem'] : '');
		$preco = (isset($retorno_bd['preco']) ? $retorno_bd['preco'] : '');
		$descricao = (isset($retorno_bd['descricao']) ? $retorno_bd['descricao'] : '');
		$template_id = (isset($retorno_bd['template_id']) ? $retorno_bd['template_id'] : '');
		$template_tipo = (isset($retorno_bd['template_tipo']) ? $retorno_bd['template_tipo'] : '');
		$caminho = (isset($retorno_bd['caminho']) ? $retorno_bd['caminho'] : '');
		
		// ===== URL completa no caminho.
		
		gestor_incluir_biblioteca('host');
		$urlFull = host_url(Array(
			'opcao' => 'full',
		));
		
		// ===== Desmascarar e trocar depois para as variáveis globais não serem subistituídas no gestor.
		
		$descricao = pagina_variaveis_globais_desmascarar(Array('valor' => $descricao));
		
		$variaveisTrocarDepois['descricao'] = $descricao;
		
		// ===== Subistituir as variáveis na página.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#caminho#',$caminho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#url#',$urlFull.$caminho);
		
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
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
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
			),
			'campos' => Array(
				Array(
					'tipo' => 'imagepick-hosts',
					'id' => 'thumbnail',
					'nome' => 'imagem',
					'id_hosts_arquivos' => $id_hosts_arquivos,
				),
				Array(
					'tipo' => 'templates-hosts',
					'id' => 'template',
					'nome' => 'template',
					'categoria_id' => 'postagens',
					'template_id' => $template_id,
					'template_tipo' => $template_tipo,
				)
			),
		)
	);
}

function postagens_status(){
	global $_GESTOR;
	
	$id = $_GESTOR['modulo-registro-id'];
	$status = $_GESTOR['modulo-registro-status'];
	
	$tabela = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela'];
	
	$resultado = banco_select(Array(
		'unico' => true,
		'tabela' => $tabela['nome'],
		'campos' => Array(
			$tabela['id_numerico'],
		),
		'extra' => 
			"WHERE ".$tabela['id']."='".$id."'"
			." AND ".$tabela['status']."!='D'"
	));
	
	postagens_pagina(Array(
		'opcao' => 'status',
		'id_postagens' => $resultado[$tabela['id_numerico']],
		'status' => $status,
	));
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_postagens(Array(
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

function postagens_excluir(){
	global $_GESTOR;
	
	$id_numerico = $_GESTOR['modulo-registro-id-numerico'];
	
	postagens_pagina(Array(
		'opcao' => 'excluir',
		'id_postagens' => $id_numerico,
	));
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_postagens(Array(
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

function postagens_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'status':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'postagens_status',
			);
		break;
		case 'excluir':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'postagens_excluir',
			);
		break;
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
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
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

function postagens_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function postagens_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': postagens_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		postagens_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': postagens_adicionar(); break;
			case 'editar': postagens_editar(); break;
		}
		
		interface_finalizar();
	}
}

postagens_start();

?>