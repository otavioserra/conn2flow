<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'usuarios-gestores-perfis';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html','usuario'),
	'tabela' => Array(
		'nome' => 'usuarios_gestores_perfis',
		'id' => 'id',
		'id_numerico' => 'id_'.'usuarios_gestores_perfis',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
		'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
	),
	'modulo_biblioteca_id' => '3'
);

function usuarios_perfis_adicionar(){
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
		
		// ===== Campos gerais
		
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
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
		
		// ===== IDs dos módulos e módulos operações.
		
		$modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos',
				'id',
			))
			,
			"modulos",
			"WHERE status='A'"
			." AND id_modulos_grupos!='".$modulo_biblioteca_id."'"
		);
		
		$modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos_operacoes',
				'id',
			))
			,
			"modulos_operacoes",
			"WHERE status='A'"
		);
		
		// ===== Módulos
		
		$totalModulos = (int)gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'total-modulos');
		$count = 1;
		
		for($i=1;$i<$totalModulos;$i++){
			if($_REQUEST['modulo-'.$count]){
				// ===== Procurar o módulo referido.
				
				$encontrou = false;
				$modulo_id = '';
				
				if($modulos){
					foreach($modulos as $mod){
						if($mod['id_modulos'] == $_REQUEST['modulo-'.$count]){
							$encontrou = true;
							$modulo_id = $mod['id'];
							break;
						}
					}
				}
				
				// ===== Caso tenha encontrado, inserir o mesmo como referência.
				
				if($encontrou){
					banco_insert_name_campo('perfil',$id);
					banco_insert_name_campo('modulo',$modulo_id);
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"usuarios_perfis_modulos"
					);
				}
			}
			
			$count++;
		}
		
		// ===== Operações
		
		$totalModulosOperacoes = (int)gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'total-modulos-operacoes');
		$count = 1;
		
		for($i=1;$i<$totalModulosOperacoes;$i++){
			if($_REQUEST['operacao-'.$count]){
				// ===== Procurar a operação referido.
				
				$encontrou = false;
				$operacao_id = '';
				
				if($modulos_operacoes){
					foreach($modulos_operacoes as $operacao){
						if($operacao['id_modulos_operacoes'] == $_REQUEST['operacao-'.$count]){
							$encontrou = true;
							$operacao_id = $operacao['id'];
							break;
						}
					}
				}
				
				// ===== Caso tenha encontrado, inserir o mesmo como referência.
				
				if($encontrou){
					banco_insert_name_campo('perfil',$id);
					banco_insert_name_campo('operacao',$operacao_id);
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"usuarios_perfis_modulos_operacoes"
					);
				}
			}
			
			$count++;
		}
		
		// ===== Redirecionar
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface alterações na página
	
	$pagina = $_GESTOR['pagina'];
	
	$cel_nome = 'operacoes-items'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'operacoes'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'items'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'grupo'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$pagina = modelo_var_troca($pagina,"#modules-name#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'modules-name')));
	
	$cel_nome = 'grupo';
	$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#module-select-all#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'module-select-all')));
	$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#module-unselect-all#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'module-unselect-all')));
	
	$cel_nome = 'operacoes';
	$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#operacoes-nome#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'operations-modules-name')));
	
	// ===== Buscar o id_usuario proprietário do host.
	
	$hosts = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts',
		'campos' => Array(
			'id_usuarios',
		),
		'extra' => 
			"WHERE id_hosts='".$_GESTOR['host-id']."'"
	));
	
	// ===== Pegar o identificador do perfil do usuário proprietário do host.
	
	$usuarios = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array(
			'id_usuarios_perfis',
		),
		'extra' => 
			"WHERE id_usuarios='".$hosts['id_usuarios']."'"
	));
	
	$usuarios_perfis = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios_perfis',
		'campos' => Array(
			'id',
		),
		'extra' => 
			"WHERE id_usuarios_perfis='".$usuarios['id_usuarios_perfis']."'"
	));
	
	$perfil = $usuarios_perfis['id'];
	
	// ===== Pegar todos os perfis_modulos e operações do usuário proprietário.
	
	$usuarios_perfis_modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'modulo',
		))
		,
		"usuarios_perfis_modulos",
		"WHERE perfil='".$perfil."'"
	);
	
	$usuarios_perfis_modulos_operacoes = banco_select_name
	(
		banco_campos_virgulas(Array(
			'operacao',
		))
		,
		"usuarios_perfis_modulos_operacoes",
		"WHERE perfil='".$perfil."'"
	);
	
	// ===== Buscar no banco módulos / grupo de módulos
	
	$modulo_biblioteca_id = $modulo['modulo_biblioteca_id'];
	
	$modulos_grupos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos_grupos',
			'nome',
		))
		,
		"modulos_grupos",
		"WHERE status='A'"
		." AND id_modulos_grupos!='".$modulo_biblioteca_id."'"
		." ORDER BY nome ASC"
	);
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
			'id_modulos_grupos',
			'nome',
			'id',
		))
		,
		"modulos",
		"WHERE status='A'"
		." AND id_modulos_grupos!='".$modulo_biblioteca_id."'"
		." ORDER BY nome ASC"
	);
	
	$modulos_operacoes = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos_operacoes',
			'id_modulos',
			'nome',
			'id',
		))
		,
		"modulos_operacoes",
		"WHERE status='A'"
		." ORDER BY nome ASC"
	);
	
	// ===== Caso encontre, monte o html com todos os módulos em seus grupos
	
	if($modulos_grupos){
		$count = 1;
		$count_operacoes = 1;
		$cel_nome = 'grupo';
		$cel_nome_2 = 'items';
		$cel_nome_3 = 'operacoes';
		$cel_nome_4 = 'operacoes-items';
		
		foreach($modulos_grupos as $mg){
			$id_modulos_grupos = $mg['id_modulos_grupos'];
			$found = false;
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#module-grup-name#",$mg['nome']);
			
			if($modulos){
				foreach($modulos as $m){
					// ===== Verificar se o módulo faz parte do perfil do proprietário do host, senão continuar.
					
					if($usuarios_perfis_modulos){
						$mFound = false;
						
						foreach($usuarios_perfis_modulos as $pm){
							if($pm['modulo'] == $m['id']){
								$mFound = true;
								break;
							}
						}
						
						if(!$mFound){
							continue;
						}
					}
					
					if($m['id_modulos_grupos'] == $id_modulos_grupos){
						$cel_aux_2 = $cel[$cel_nome_2];
						
						// ===== Operações
						
						$found_operacao = false;
						$cel_aux_op = $cel[$cel_nome_3];
						
						if($modulos_operacoes){
							foreach($modulos_operacoes as $mo){
								// ===== Verificar se o módulo operação faz parte do perfil do proprietário do host, senão continuar.
								
								if($usuarios_perfis_modulos_operacoes){
									$mOFound = false;
									
									foreach($usuarios_perfis_modulos_operacoes as $pmo){
										if($pmo['operacao'] == $mo['id']){
											$mOFound = true;
											break;
										}
									}
									
									if(!$mOFound){
										continue;
									}
								}
								
								if($mo['id_modulos'] == $m['id_modulos']){
									$cel_aux_op_2 = $cel[$cel_nome_4];
					
									$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-label#",$mo['nome']);
									$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-id#",$mo['id_modulos_operacoes']);
									$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-num#",$count_operacoes);
									$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-checked#",'');
									
									$cel_aux_op = modelo_var_in($cel_aux_op,'<!-- '.$cel_nome_4.' -->',$cel_aux_op_2);
									
									$count_operacoes++;
									$found_operacao = true;
								}
							}
						}
						
						if($found_operacao){
							$cel_aux_2 = modelo_var_in($cel_aux_2,'<!-- '.$cel_nome_3.' -->',$cel_aux_op);
						}
						
						// ===== Módulo
		
						$cel_aux_2 = modelo_var_troca($cel_aux_2,"#module-label#",$m['nome']);
						$cel_aux_2 = modelo_var_troca($cel_aux_2,"#id#",$m['id_modulos']);
						$cel_aux_2 = modelo_var_troca($cel_aux_2,"#num#",$count);
						$cel_aux_2 = modelo_var_troca($cel_aux_2,"#checked#",'');
						
						$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_nome_2.' -->',$cel_aux_2);
						
						$count++;
						$found = true;
					}
				}
			}
			
			$cel_aux = modelo_var_troca($cel_aux,'<!-- '.$cel_nome_2.' -->','');
			
			if($found){
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
		}
		
		gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'total-modulos',$count);
		gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'total-modulos-operacoes',$count_operacoes);
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	}
	
	// ===== Atualizar página
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
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

function usuarios_perfis_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
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
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'",
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
				),
			));
			
			$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Atualização dos demais campos.
		
		$retorno_bd = banco_select_editar
		(
			banco_campos_virgulas(Array(
				$modulo['tabela']['id_numerico'],
			))
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
		);
		
		$id_numerico = (isset($retorno_bd[$modulo['tabela']['id_numerico']]) ? $retorno_bd[$modulo['tabela']['id_numerico']] : '');
		
		$usuarios_perfis_modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'modulo',
			))
			,
			"usuarios_perfis_modulos",
			"WHERE perfil='".$id."'"
		);
		
		$usuarios_perfis_modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'operacao',
			))
			,
			"usuarios_perfis_modulos_operacoes",
			"WHERE perfil='".$id."'"
		);
		
		// ===== IDs dos módulos e módulos operações.
		
		$modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos',
				'id',
				'nome',
			))
			,
			"modulos",
			"WHERE status='A'"
			." AND id_modulos_grupos!='".$modulo_biblioteca_id."'"
		);
		
		$modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos',
				'id_modulos_operacoes',
				'id',
				'nome',
			))
			,
			"modulos_operacoes",
			"WHERE status='A'"
		);
		
		// ===== Varrer todos os inputs enviados
		
		$numModulos = (int)gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'#'.$id.'#'.'total-modulos');
		$numModulosOperacoes = (int)gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'#'.$id.'#'.'total-modulos-operacoes');
		$modulosAtivos = Array();
		$modulosOperacoesAtivos = Array();
		$modulosIncluidos = Array();
		$modulosRemovidos = Array();
		$modulosOperacoesIncluidos = Array();
		$modulosOperacoesRemovidos = Array();
		
		// ===== Módulos
		
		for($i=1;$i<$numModulos;$i++){
			if($_REQUEST['modulo-'.$i]){
				// ===== Procurar o módulo referido e marcar como ativo.
				
				$encontrou = false;
				$modulo_id = '';
				
				if($modulos){
					foreach($modulos as $mod){
						if($mod['id_modulos'] == $_REQUEST['modulo-'.$i]){
							$encontrou = true;
							$modulo_id = $mod['id'];
							$modulosAtivos[$modulo_id] = true;
							break;
						}
					}
				}
				
				if($encontrou){
					// ===== Verificar se o módulo já estava ativo.
					
					$jaEstavaAtivo = false;
					
					if($usuarios_perfis_modulos)
					foreach($usuarios_perfis_modulos as $upm){
						if($upm['modulo'] == $modulo_id){
							$jaEstavaAtivo = true;
							break;
						}
					}
					
					if(!$jaEstavaAtivo){
						// ===== Caso tenha encontrado e não estiver ativo, inserir o mesmo como referência.
						
						banco_insert_name_campo('perfil',$id);
						banco_insert_name_campo('modulo',$modulo_id);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"usuarios_perfis_modulos"
						);
						
						$modulosIncluidos[$modulo_id] = true;
						$alterouModulos = true;
					}
				}
			}
		}
		
		if($usuarios_perfis_modulos)
		foreach($usuarios_perfis_modulos as $upm){
			// ===== Procurar os módulos que devem ser excluídos.
			
			$encontrou = false;
			
			foreach($modulosAtivos as $modulo_id => $val){
				if($upm['modulo'] == $modulo_id){
					$encontrou = true;
					break;
				}
			}
			
			// ===== Caso não tenha encontrado, excluir o mesmo como referência.
			
			if(!$encontrou){
				banco_delete
				(
					"usuarios_perfis_modulos",
					"WHERE perfil='".$id."'"
					." AND modulo='".$upm['modulo']."'"
				);
				
				$alterouModulos = true;
				$modulosRemovidos[$upm['modulo']] = true;
			}
		}
		
		// ===== Operações
		
		for($i=1;$i<$numModulosOperacoes;$i++){
			if($_REQUEST['operacao-'.$i]){
				// ===== Procurar a operação referida e marcar como ativo.
				
				$encontrou = false;
				$operacao_id = '';
				
				if($modulos_operacoes){
					foreach($modulos_operacoes as $modulo_operacao){
						if($modulo_operacao['id_modulos_operacoes'] == $_REQUEST['operacao-'.$i]){
							$encontrou = true;
							$operacao_id = $modulo_operacao['id'];
							$modulosOperacoesAtivos[$operacao_id] = true;
							break;
						}
					}
				}
				
				if($encontrou){
					// ===== Verificar se a operação já estava ativa.
					
					$jaEstavaAtivo = false;
					
					if($usuarios_perfis_modulos_operacoes)
					foreach($usuarios_perfis_modulos_operacoes as $upmo){
						if($upmo['operacao'] == $operacao_id){
							$jaEstavaAtivo = true;
							break;
						}
					}
					
					if(!$jaEstavaAtivo){
						// ===== Caso tenha encontrado, inserir o mesmo como referência.
						
						banco_insert_name_campo('perfil',$id);
						banco_insert_name_campo('operacao',$operacao_id);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"usuarios_perfis_modulos_operacoes"
						);
						
						$modulosOperacoesIncluidos[$operacao_id] = true;
						$alterouModulosOperacoes = true;
					}
				}
			}
		}
		
		if($usuarios_perfis_modulos_operacoes)
		foreach($usuarios_perfis_modulos_operacoes as $upmo){
			$encontrou = false;
			
			foreach($modulosOperacoesAtivos as $operacao_id => $val){
				if($upmo['operacao'] == $operacao_id){
					$encontrou = true;
					break;
				}
			}
			
			if(!$encontrou){
				banco_delete
				(
					"usuarios_perfis_modulos_operacoes",
					"WHERE perfil='".$id."'"
					." AND operacao='".$upmo['operacao']."'"
				);
				
				$alterouModulosOperacoes = true;
				$modulosOperacoesRemovidos[$upmo['operacao']] = true;
			}
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados']) || isset($alterouModulos) || isset($alterouModulosOperacoes)){
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
			
			// ===== Alterou os módulos, adicionar no histórico quais
			
			if(isset($alterouModulos) || isset($alterouModulosOperacoes)){
				// ===== Variáveis de controle.
				
				$historicoModulosIncluidos = Array();
				$historicoModulosOperacoesIncluidos = Array();
				$historicoModulosRemovidos = Array();
				$historicoModulosOperacoesRemovidos = Array();
				
				// ====== Histórico dos módulos operações incluídos.
				
				if(count($modulosOperacoesIncluidos) > 0){
					foreach($modulosOperacoesIncluidos as $operacao_id => $val){
						if($modulos_operacoes)
						foreach($modulos_operacoes as $mo){
							if($operacao_id == $mo['id']){
								$found = false;
								
								if($modulos)
								foreach($modulos as $m){
									if($m['id_modulos'] == $mo['id_modulos']){
										$found = true;
										break;
									}
								}
								
								if($found){
									if(!$historicoModulosOperacoesIncluidos[$m['id']]){
										$historicoModulosOperacoesIncluidos[$m['id']]['nome'] = $m['nome'];
									}
									
									$historicoModulosOperacoesIncluidos[$m['id']]['operacoes'][] = Array(
										'nome' => $mo['nome'],
									);
								}
								
								break;
							}
						}
					}
				}
				
				// ====== Histórico dos módulos incluídos.
				
				if(count($modulosIncluidos) > 0){
					foreach($modulosIncluidos as $modulo_id => $val){
						if($modulos)
						foreach($modulos as $m){
							if($modulo_id == $m['id']){
								if(!$historicoModulosIncluidos[$m['id']]){
									$historicoModulosIncluidos[$m['id']]['nome'] = $m['nome'];
								}
							}
						}
					}
				}
				
				// ====== Histórico dos módulos operações removidos.
				
				if(count($modulosOperacoesRemovidos) > 0){
					foreach($modulosOperacoesRemovidos as $operacao_id => $val){
						if($modulos_operacoes)
						foreach($modulos_operacoes as $mo){
							if($operacao_id == $mo['id']){
								$found = false;
								
								if($modulos)
								foreach($modulos as $m){
									if($m['id_modulos'] == $mo['id_modulos']){
										$found = true;
										break;
									}
								}
								
								if($found){
									if(!$historicoModulosOperacoesRemovidos[$m['id']]){
										$historicoModulosOperacoesRemovidos[$m['id']]['nome'] = $m['nome'];
									}
									
									$historicoModulosOperacoesRemovidos[$m['id']]['operacoes'][] = Array(
										'nome' => $mo['nome'],
									);
								}
								
								break;
							}
						}
					}
				}
				
				// ====== Histórico dos módulos removidos.
				
				if(count($modulosRemovidos) > 0){
					foreach($modulosRemovidos as $modulo_id => $val){
						if($modulos)
						foreach($modulos as $m){
							if($modulo_id == $m['id']){
								if(!$historicoModulosRemovidos[$m['id']]){
									$historicoModulosRemovidos[$m['id']]['nome'] = $m['nome'];
								}
							}
						}
					}
				}
				
				// ===== Montar valor_antes do histórico
				
				$modulosAntes = '';
				$operacoesRemovidosFlag = false;
				$modulosRemovidosFlag = false;
				
				if(count($historicoModulosOperacoesRemovidos) > 0){
					$modulosAntes = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'historic-change-modules-layout'));
					
					foreach($historicoModulosOperacoesRemovidos as $modulo_id => $mo){
						$operacoesTxt = '';
						
						if(isset($mo['operacoes'])){
							$operacoesTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'historic-change-operations'));
							$operacoesFlag = false;
							
							foreach($mo['operacoes'] as $op){
								$operacoesTxt = modelo_var_in($operacoesTxt,'#operacoes#',($operacoesFlag ? ', ':'').$op['nome']);
								$operacoesFlag = true;
							}
							
							$operacoesTxt = modelo_var_troca($operacoesTxt,'#operacoes#','');
						}
						
						$modulosAntes = modelo_var_in($modulosAntes,'#modulos-operacoes#',($operacoesRemovidosFlag ? ', ':'') . $mo['nome'] . (strlen($operacoesTxt) > 0 ? ' ':'') . $operacoesTxt);
						$operacoesRemovidosFlag = true;
					}
				}
				
				if(count($historicoModulosRemovidos) > 0){
					if(strlen($modulosAntes) == 0){
						$modulosAntes = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'historic-change-modules-layout'));
					}
					
					foreach($historicoModulosRemovidos as $modulo_id => $mo){
						$modulosAntes = modelo_var_in($modulosAntes,'#modulos#',($modulosRemovidosFlag ? ', ':'') . $mo['nome']);
						$modulosRemovidosFlag = true;
					}
				}
				
				if($operacoesRemovidosFlag){
					$modulosAntes = modelo_var_troca($modulosAntes,'#modulos-operacoes#','');
				} else {
					$modulosAntes = modelo_var_troca($modulosAntes,'#modulos-operacoes#',gestor_variaveis(Array('modulo' => 'interface','id' => 'no-occurrence')));
				}
				
				if($modulosRemovidosFlag){
					$modulosAntes = modelo_var_troca($modulosAntes,'#modulos#','');
				} else {
					$modulosAntes = modelo_var_troca($modulosAntes,'#modulos#',gestor_variaveis(Array('modulo' => 'interface','id' => 'no-occurrence')));
				}
				
				// ===== Montar valor_depois do hostórico
				
				$modulosDepois = '';
				$operacoesIncluidosFlag = false;
				$modulosIncluidosFlag = false;
				
				if(count($historicoModulosOperacoesIncluidos) > 0){
					$modulosDepois = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'historic-change-modules-layout'));
					
					foreach($historicoModulosOperacoesIncluidos as $modulo_id => $mo){
						$operacoesTxt = '';
						
						if(isset($mo['operacoes'])){
							$operacoesTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'historic-change-operations'));
							$operacoesFlag = false;
							
							foreach($mo['operacoes'] as $op){
								$operacoesTxt = modelo_var_in($operacoesTxt,'#operacoes#',($operacoesFlag ? ', ':'').$op['nome']);
								$operacoesFlag = true;
							}
							
							$operacoesTxt = modelo_var_troca($operacoesTxt,'#operacoes#','');
						}
						
						$modulosDepois = modelo_var_in($modulosDepois,'#modulos-operacoes#',($operacoesIncluidosFlag ? ', ':'') . $mo['nome'] . (strlen($operacoesTxt) > 0 ? ' ':'') . $operacoesTxt);
						$operacoesIncluidosFlag = true;
					}
				}
				
				if(count($historicoModulosIncluidos) > 0){
					if(strlen($modulosDepois) == 0){
						$modulosDepois = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'historic-change-modules-layout'));
					}
					
					foreach($historicoModulosIncluidos as $modulo_id => $mo){
						$modulosDepois = modelo_var_in($modulosDepois,'#modulos#',($modulosIncluidosFlag ? ', ':'') . $mo['nome']);
						$modulosIncluidosFlag = true;
					}
				}
				
				if($operacoesIncluidosFlag){
					$modulosDepois = modelo_var_troca($modulosDepois,'#modulos-operacoes#','');
				} else {
					$modulosDepois = modelo_var_troca($modulosDepois,'#modulos-operacoes#',gestor_variaveis(Array('modulo' => 'interface','id' => 'no-occurrence')));
				}
				
				if($modulosIncluidosFlag){
					$modulosDepois = modelo_var_troca($modulosDepois,'#modulos#','');
				} else {
					$modulosDepois = modelo_var_troca($modulosDepois,'#modulos#',gestor_variaveis(Array('modulo' => 'interface','id' => 'no-occurrence')));
				}
				
				// ===== Finalizar montagem do histórico
				
				if(strlen($modulosDepois) == 0){
					$modulosDepois = gestor_variaveis(Array('modulo' => 'interface','id' => 'no-occurrence'));
				}
				
				if(strlen($modulosAntes) == 0){
					$modulosAntes = gestor_variaveis(Array('modulo' => 'interface','id' => 'no-occurrence'));
				}
				
				$alteracoes[] = Array('opcao' => 'usuarios-perfis','campo' => 'modules-name','valor_antes' => $modulosAntes,'valor_depois' => $modulosDepois);
			}
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
			
			// ===== Se alterou o id, atualizar as referências dos mesmos nas tabelas.
			
			if(isset($id_novo)){
				banco_update_campo('perfil',$id_novo);
				banco_update_executar('usuarios_perfis_modulos',"WHERE perfil='".$id."'");
				
				banco_update_campo('perfil',$id_novo);
				banco_update_executar('usuarios_perfis_modulos_operacoes',"WHERE perfil='".$id."'");
			}
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
		$id_numerico = (isset($retorno_bd[$modulo['tabela']['id_numerico']]) ? $retorno_bd[$modulo['tabela']['id_numerico']] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
		
		// ===== Interface alterações na página
		
		$pagina = $_GESTOR['pagina'];
		
		$cel_nome = 'operacoes-items'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		$cel_nome = 'operacoes'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		$cel_nome = 'items'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		$cel_nome = 'grupo'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		$pagina = modelo_var_troca($pagina,"#modules-name#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'modules-name')));
		
		$cel_nome = 'grupo';
		$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#module-select-all#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'module-select-all')));
		$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#module-unselect-all#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'module-unselect-all')));
		
		$cel_nome = 'operacoes';
		$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#operacoes-nome#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'operations-modules-name')));
		
		// ===== Buscar no banco módulos / grupo de módulos
		
		$modulo_biblioteca_id = $modulo['modulo_biblioteca_id'];
		
		$modulos_grupos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos_grupos',
				'nome',
			))
			,
			"modulos_grupos",
			"WHERE status='A'"
			." AND id_modulos_grupos!='".$modulo_biblioteca_id."'"
			." ORDER BY nome ASC"
		);
		
		$modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos',
				'id_modulos_grupos',
				'nome',
				'id',
			))
			,
			"modulos",
			"WHERE status='A'"
			." AND id_modulos_grupos!='".$modulo_biblioteca_id."'"
			." ORDER BY nome ASC"
		);
		
		$modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos_operacoes',
				'id_modulos',
				'nome',
				'id',
			))
			,
			"modulos_operacoes",
			"WHERE status='A'"
			." ORDER BY nome ASC"
		);
		
		$usuarios_perfis_modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'modulo',
			))
			,
			"usuarios_perfis_modulos",
			"WHERE perfil='".$id."'"
		);
		
		$usuarios_perfis_modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'operacao',
			))
			,
			"usuarios_perfis_modulos_operacoes",
			"WHERE perfil='".$id."'"
		);
		
		// ===== Caso encontre, monte o html com todos os módulos em seus grupos
		
		if($modulos_grupos){
			$count = 1;
			$count_operacoes = 1;
			$cel_nome = 'grupo';
			$cel_nome_2 = 'items';
			$cel_nome_3 = 'operacoes';
			$cel_nome_4 = 'operacoes-items';
			$modulosAntes = '';
			$modulosOperacoesAntes = '';
			
			foreach($modulos_grupos as $mg){
				$id_modulos_grupos = $mg['id_modulos_grupos'];
				$found = false;
				
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#module-grup-name#",$mg['nome']);
				
				if($modulos){
					foreach($modulos as $m){
						if($m['id_modulos_grupos'] == $id_modulos_grupos){
							$cel_aux_2 = $cel[$cel_nome_2];
			
							// ===== Operações
							
							$found_operacao = false;
							$cel_aux_op = $cel[$cel_nome_3];
							
							if($modulos_operacoes){
								foreach($modulos_operacoes as $mo){
									if($mo['id_modulos'] == $m['id_modulos']){
										$cel_aux_op_2 = $cel[$cel_nome_4];
						
										$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-label#",$mo['nome']);
										$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-id#",$mo['id_modulos_operacoes']);
										$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-num#",$count_operacoes);
										
										$operacaoChecked = '';
										
										if($usuarios_perfis_modulos_operacoes)
										foreach($usuarios_perfis_modulos_operacoes as $upmo){
											if($upmo['operacao'] == $mo['id']){
												$operacaoChecked = 'checked';
												break;
											}
										}
										
										$cel_aux_op_2 = modelo_var_troca($cel_aux_op_2,"#operacao-checked#",$operacaoChecked);
										
										$cel_aux_op = modelo_var_in($cel_aux_op,'<!-- '.$cel_nome_4.' -->',$cel_aux_op_2);
										
										$count_operacoes++;
										$found_operacao = true;
									}
								}
							}
							
							if($found_operacao){
								$cel_aux_2 = modelo_var_in($cel_aux_2,'<!-- '.$cel_nome_3.' -->',$cel_aux_op);
							}
							
							// ===== Módulo
			
							$cel_aux_2 = modelo_var_troca($cel_aux_2,"#module-label#",$m['nome']);
							$cel_aux_2 = modelo_var_troca($cel_aux_2,"#id#",$m['id_modulos']);
							$cel_aux_2 = modelo_var_troca($cel_aux_2,"#num#",$count);
							
							$checked = '';
							
							if($usuarios_perfis_modulos)
							foreach($usuarios_perfis_modulos as $upm){
								if($upm['modulo'] == $m['id']){
									$checked = 'checked';
									break;
								}
							}
							
							$cel_aux_2 = modelo_var_troca($cel_aux_2,"#checked#",$checked);
							
							$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_nome_2.' -->',$cel_aux_2);
							
							$count++;
							$found = true;
						}
					}
				}
				
				$cel_aux = modelo_var_troca($cel_aux,'<!-- '.$cel_nome_2.' -->','');
				
				if($found){
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
			}
			
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
			
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'#'.$id.'#'.'total-modulos',$count);
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'#'.$id.'#'.'total-modulos-operacoes',$count_operacoes);
		}
		
		// ===== Atualizar página
		
		$_GESTOR['pagina'] = $pagina;
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
			)
		)
	);
}

function usuarios_perfis_interfaces_padroes(){
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

// ==== Start

function usuarios_perfis_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': usuarios_perfis_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		usuarios_perfis_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': usuarios_perfis_adicionar(); break;
			case 'editar': usuarios_perfis_editar(); break;
		}
		
		interface_finalizar();
	}
}

usuarios_perfis_start();

?>