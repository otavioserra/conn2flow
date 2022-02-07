<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'modulos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.2',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'modulos',
		'id' => 'id',
		'id_numerico' => 'id_'.'modulos',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
	'selectDadosLinguagem' => Array(
		Array(
			'texto' => 'Português/Brasil',
			'valor' => 'pt-br',
		),
	)
);

// ===== Funções Administrativas de Sistema

function modulos_copiar_variaveis(){
	global $_GESTOR;
	
	// ===== Ativar / Desativar
	
	$ativar = false;
	
	if(!$ativar){
		$_GESTOR['pagina'] .= '<h2>COPIAR VARIÁVEIS - <span class="ui error text">DESATIVADO</span></h2>';
		return;
	}
	
	$_GESTOR['pagina'] .= '<h2>COPIAR VARIÁVEIS</h2>';
	
	// ===== Definir os módulos origem e destino
	
	$modulos = Array(
		'origem' => 'admin-componentes',
		'destino' => 'componentes',
	);
	
	// ===== Buscar no banco de dados 
	
	$variaveisOrigem = banco_select_name
	(
		'*'
		,
		"variaveis",
		"WHERE modulo='".$modulos['origem']."'"
	);
	
	$variaveisDestino = banco_select_name
	(
		'*'
		,
		"variaveis",
		"WHERE modulo='".$modulos['destino']."'"
	);
	
	$quantidade = 0;
	
	// ===== Varrer todos as variaveis e só incluir caso não exista no destino.
	
	if($variaveisOrigem){
		foreach($variaveisOrigem as $origem){
			// ===== Procurar no destino, se encontrar não incluir.
			
			$found = false;
			if($variaveisDestino){
				foreach($variaveisDestino as $destino){
					if($origem['id'] == $destino['id']){
						$found = true;
						break;
					}
				}
			}
			
			// ===== Senão encontrou no destino, incluir nos valores processados.
			
			if(!$found){
				$quantidade++;
				
				// ===== Remover o campo ID padrão e deixar a tabela criar o mesmo sozinho.
				
				unset($origem['id_variaveis']);
				
				// ===== Incluir tudo nos valoresProcessados e trocar o 'modulo' para o destino.
				
				foreach($origem as $id => $valor){
					if(!isset($valoresProcessados[$id])){
						$valoresProcessados[$id] = Array();
					}
					
					if($id == 'modulo'){
						$valoresProcessados[$id][] = $modulos['destino'];
					} else {
						$valoresProcessados[$id][] = banco_escape_field($valor);
					}
				}
			}
		}
		
		// ===== Criar o vetor 'campos' com o nome e os valores para cada campo.
		
		if(isset($valoresProcessados)){
			foreach($valoresProcessados as $nome => $valores){
				$campos[] = Array(
					'nome' => $nome,
					'valores' => $valores,
				);
			}
		}
		
		if(isset($campos)){
			banco_insert_name_varios(Array(
				'campos' => $campos,
				'tabela' => 'variaveis',
			));
		}
	}
	
	$_GESTOR['pagina'] .= '<p>Quantidade: <b>'.$quantidade.'</b></p>';
	$_GESTOR['pagina'] .= '<p>Finalizado!</p>';
}

function modulos_sincronizar_bancos(){
	global $_GESTOR;
	
	// ===== Ativar / Desativar
	
	$ativar = false;
	
	// ===== Forçar atualização dos campos de data modificada.
	
	$forcarAtualizacao = true;
	
	// ===== Definir origem 1 / destino 2
	
	$servers = Array(
		1 => 'beta.entrey.com.br',
		2 => 'entrey.com.br',
	);
	
	$serverOrigem = $servers[1];
	$serverDestino = $servers[2];
	
	// ===== Mostrar mensagem de desativado.
	
	if(!$ativar){
		$_GESTOR['pagina'] .= '<h2>SINCRONIZAR BANCOS - <span class="ui error text">DESATIVADO</span></h2>';
		return;
	}
	
	// ===== Mudar host caso necessário
	
	$_GESTOR['bancoDef']['beta.entrey.com.br']['host'] = 'beta.entrey.com.br';
	$_GESTOR['bancoDef']['entrey.com.br']['host'] = 'entrey.com.br';
	
	// ===== Head inicial
	
	$_GESTOR['pagina'] .= '<h2>SINCRONIZAR BANCOS</h2>';
	
	$tabelas = Array(
		'variaveis',
		'paginas',
		'layouts',
		'modulos_grupos',
		'modulos',
	);
	
	$atualizaSinal['variaveis'] = false;
	$atualizaSinal['paginas'] = false;
	$atualizaSinal['layouts'] = false;
	$atualizaSinal['modulos_grupos'] = false;
	$atualizaSinal['modulos'] = false;
	
	$dados['variaveis'] = Array(
		'tabela' => Array(
			'nome' => 'variaveis',
			'id_referencia' => 'id_variaveis',
			'campos' => Array(
				'id_variaveis',
				'id',
				'modulo',
				'linguagem_codigo',
				'valor',
				'tipo',
			),
			'camposComparacao' => Array(
				'id',
				'modulo',
			),
		),
		'print1' => '<span class="ui text info">Variáveis</span>: Atualizar Variáveis Destino',
		'print2' => '<span class="ui text info">Variáveis</span>: Atualizar Variáveis Destino',
	);
	
	$dados['paginas'] = Array(
		'tabela' => Array(
			'nome' => 'paginas',
			'id_referencia' => 'id_paginas',
			'campos' => Array(
				'id_paginas',
				'id_usuarios',
				'id_layouts',
				'nome',
				'id',
				'caminho',
				'tipo',
				'modulo',
				'opcao',
				'raiz',
				'html',
				'css',
				'status',
				'versao',
				'data_criacao',
				'data_modificacao',
			),
			'camposComparacao' => Array(
				'id',
				'modulo',
				'data_modificacao',
				'status',
			),
		),
		'print1' => '<span class="ui text info">Páginas</span>: Atualizar Páginas Destino',
		'print2' => '<span class="ui text info">Páginas</span>: Atualizar Páginas Destino',
		'print3' => '<span class="ui text info">Páginas</span>: Data Modificação Páginas Maior Origem',
	);
	
	$dados['layouts'] = Array(
		'tabela' => Array(
			'nome' => 'layouts',
			'id_referencia' => 'id_layouts',
			'campos' => Array(
				'id_layouts',
				'id_usuarios',
				'nome',
				'id',
				'html',
				'css',
				'status',
				'versao',
				'data_criacao',
				'data_modificacao',
			),
			'camposComparacao' => Array(
				'id',
				'data_modificacao',
				'status',
			),
		),
		'print1' => '<span class="ui text info">Layouts</span>: Atualizar Layouts Destino',
		'print2' => '<span class="ui text info">Layouts</span>: Atualizar Layouts Destino',
		'print3' => '<span class="ui text info">Layouts</span>: Data Modificação Layouts Maior Origem',
	);
	
	$dados['componentes'] = Array(
		'tabela' => Array(
			'nome' => 'componentes',
			'id_referencia' => 'id_componentes',
			'campos' => Array(
				'id_componentes',
				'id_usuarios',
				'nome',
				'modulo',
				'id',
				'html',
				'css',
				'status',
				'versao',
				'data_criacao',
				'data_modificacao',
			),
			'camposComparacao' => Array(
				'id',
				'data_modificacao',
				'status',
			),
		),
		'print1' => '<span class="ui text info">Componentes</span>: Atualizar Componentes Destino',
		'print2' => '<span class="ui text info">Componentes</span>: Atualizar Componentes Destino',
		'print3' => '<span class="ui text info">Componentes</span>: Data Modificação Componentes Maior Origem',
	);
	
	$dados['modulos_grupos'] = Array(
		'tabela' => Array(
			'nome' => 'modulos_grupos',
			'id_referencia' => 'id_modulos_grupos',
			'campos' => Array(
				'id_modulos_grupos',
				'id_usuarios',
				'nome',
				'id',
				'status',
				'versao',
				'data_criacao',
				'data_modificacao',
			),
			'camposComparacao' => Array(
				'id',
				'data_modificacao',
				'status',
			),
		),
		'print1' => '<span class="ui text info">Módulos Grupos</span>: Atualizar Módulos Grupos Destino',
		'print2' => '<span class="ui text info">Módulos Grupos</span>: Atualizar Módulos Grupos Destino',
		'print3' => '<span class="ui text info">Módulos Grupos</span>: Data Modificação Módulos Grupos Maior Origem',
	);
	
	$dados['modulos'] = Array(
		'tabela' => Array(
			'nome' => 'modulos',
			'id_referencia' => 'id_modulos',
			'campos' => Array(
				'id_modulos',
				'id_usuarios',
				'id_modulos_grupos',
				'nome',
				'titulo',
				'id',
				'icone',
				'icone2',
				'nao_menu_principal',
				'status',
				'versao',
				'data_criacao',
				'data_modificacao',
			),
			'camposComparacao' => Array(
				'id',
				'data_modificacao',
				'status',
			),
		),
		'print1' => '<span class="ui text info">Módulos</span>: Atualizar Módulos Destino',
		'print2' => '<span class="ui text info">Módulos</span>: Atualizar Módulos Destino',
		'print3' => '<span class="ui text info">Módulos</span>: Data Modificação Módulos Maior Origem',
	);
	
	// ===== Atualizar Tabela
	
	foreach($tabelas as $tabela){
		
		$idAtual = $tabela;
		$dadosDef = $dados[$idAtual];
		
		// ===== Origem
		
		unset($camposAtualizar);
		
		$_GESTOR['banco'] = $_GESTOR['bancoDef'][$serverOrigem];
		
		$tabelaOrigem = banco_select_name
		(
			banco_campos_virgulas($dadosDef['tabela']['campos'])
			,
			$dadosDef['tabela']['nome'],
			""
		);
		
		banco_fechar_conexao();
		
		// ===== Destino
		
		$_GESTOR['banco'] = $_GESTOR['bancoDef'][$serverDestino];
		
		$tabelaDestino = banco_select_name
		(
			banco_campos_virgulas($dadosDef['tabela']['camposComparacao'])
			,
			$dadosDef['tabela']['nome'],
			""
		);
		
		if($tabelaOrigem){
			foreach($tabelaOrigem as $to){
				$found = false;
				
				if($tabelaDestino){
					foreach($tabelaDestino as $td){
						if(
							$idAtual == 'modulos_grupos' ||
							$idAtual == 'modulos' ||
							$idAtual == 'componentes' ||
							$idAtual == 'layouts'
						){
							if(
								$to['id'] == $td['id']
							){
								$found = true;
								break;
							}
						} else {
							if(
								$to['id'] == $td['id'] &&
								$to['modulo'] == $td['modulo']
							){
								$found = true;
								break;
							}
						}
					}
				}
				
				if(!$found){
					if(!$atualizaSinal[$idAtual]){
						$_GESTOR['pagina'] .= '<b>'.$dadosDef['print1'].':</b><br><br>';
					}
					
					$atualizaSinal[$idAtual] = true;
					$_GESTOR['pagina'] .= print_r($to,true).'<br>';
					$camposAtualizar[] = $to;
				}
			}
		}
		
		if($atualizaSinal[$idAtual]){
			$_GESTOR['pagina'] .= '<br><b>'.$dadosDef['print2'].':</b><br><br>';
		}
		
		if(isset($camposAtualizar)){
			foreach($camposAtualizar as $ca){
				$campos = null; $campo_sem_aspas_simples = null;
				
				foreach($ca as $key => $val){
					if(existe($val)){
						$campo_nome = $key; $campo_valor = addslashes($val); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					} else {
						$campo_nome = $key; $campo_valor = 'NULL'; 		$campos[] = Array($campo_nome,$campo_valor,true);
					}
				}
				
				$_GESTOR['pagina'] .= print_r($campos,true).'<br>';
				
				banco_insert_name
				(
					$campos,
					$dadosDef['tabela']['nome']
				);
			}
		}
	}
	
	// ===== Comparar tabelas
	
	foreach($tabelas as $tabela){
		
		if($tabela == 'variaveis'){
			continue;
		}
		
		$idAtual = $tabela;
		$dadosDef = $dados[$idAtual];
		
		// ===== Origem
		
		unset($camposAtualizar);
		
		$_GESTOR['banco'] = $_GESTOR['bancoDef'][$serverOrigem];
		
		$tabelaOrigem = banco_select_name
		(
			banco_campos_virgulas($dadosDef['tabela']['campos'])
			,
			$dadosDef['tabela']['nome'],
			""
		);
		
		banco_fechar_conexao();
		
		// ===== Destino
		
		$_GESTOR['banco'] = $_GESTOR['bancoDef'][$serverDestino];
		
		$tabelaDestino = banco_select_name
		(
			banco_campos_virgulas($dadosDef['tabela']['camposComparacao'])
			,
			$dadosDef['tabela']['nome'],
			""
		);
		
		if($tabelaOrigem){
			foreach($tabelaOrigem as $to){
				if($to['status'] == 'D'){
					continue;
				}
				
				$found = false;
				
				if($tabelaDestino){
					foreach($tabelaDestino as $td){
						if(isset($td['status']))
						if($td['status'] == 'D'){
							continue;
						}
						
						if(
							$idAtual == 'modulos_grupos' ||
							$idAtual == 'modulos' ||
							$idAtual == 'componentes' ||
							$idAtual == 'layouts'
						){
							if(
								$to['id'] == $td['id'] &&
								strtotime($to['data_modificacao']) > strtotime($td['data_modificacao'])
							){
								$found = true;
								break;
							}
						} else {
							if(
								$to['id'] == $td['id'] &&
								$to['modulo'] == $td['modulo'] &&
								strtotime($to['data_modificacao']) > strtotime($td['data_modificacao'])
							){
								$found = true;
								break;
							}
						}
					}
				}
				
				if($found){
					if(!$atualizaSinal[$idAtual]){
						$_GESTOR['pagina'] .= '<br><p><b>'.$dadosDef['print3'].':</b></p>';
					}
					
					// ===== Mostrar qual atualizar.
					
					$atualizaSinal[$idAtual] = true;
					//$_GESTOR['pagina'] .= print_r($to,true).'<br>';
					$_GESTOR['pagina'] .= $to['data_modificacao']. ' > ' . $td['data_modificacao'] . ' - ' . $to['status'] . ' - ' . $to['id'] . ' - '.( $forcarAtualizacao ? '<span class="ui text green">ATUALIZADO</span>' : '').'<br>';
					$camposAtualizar[] = $to;
				}
			}
		}
		
		// ===== Atualizar dados destino se definida opção.
		
		if($forcarAtualizacao){
			if(isset($camposAtualizar)){
				foreach($camposAtualizar as $ca){
					$campos = null; $campo_sem_aspas_simples = null;
					$id_referencia = '';
					
					foreach($ca as $key => $val){
						if('id' == $key){
							$id_referencia = $val;
							continue;
						}
						
						if(existe($val)){
							banco_update_campo($key,$val);
						} else {
							banco_update_campo($key,'NULL',true);
						}
					}
					
					banco_update_executar($dadosDef['tabela']['nome'],"WHERE id='".$id_referencia."' AND status!='D'");
				}
			}
		}
	}
}

// ===== Funções Principais

function modulos_variaveis(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Banco antes de atualizar.
		
		$banco_antes = Array();
		
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_variaveis',
				'id',
				'valor',
			))
			,
			"variaveis",
			"WHERE linguagem_codigo='".$_GESTOR['linguagem-codigo']."'"
			." AND modulo='".$id."'"
			." AND tipo='string'"
			." ORDER BY id ASC"
		);
		
		if($variaveis){
			foreach($variaveis as $ling){
				$banco_antes[$ling['id_variaveis']] = Array(
					'id' => $ling['id'],
					'valor' => $ling['valor'],
				);
			}
		}
		
		// ===== Varrer todos os inputs enviados
		
		$numCampos = (int)banco_escape_field($_REQUEST['num-campos']);
		$linguagemCodigo = banco_escape_field($_REQUEST['linguagem']);
		
		for($i=1;$i<$numCampos;$i++){
			$campo_id = banco_escape_field($_REQUEST['id-'.$i]);
			$valor = banco_escape_field($_REQUEST['valor-'.$i]);
			$ref = banco_escape_field($_REQUEST['ref-'.$i]);
			
			if(isset($banco_antes[$ref])){
				$banco_antes[$ref]['verificado'] = true;
				if($banco_antes[$ref]['id'] != $campo_id || $banco_antes[$ref]['valor'] != $valor){
					banco_update
					(
						"id='".$campo_id."',".
						"valor='".$valor."'",
						"variaveis",
						"WHERE id_variaveis='".$ref."'"
					);
					
					$alterouLinguagem = true;
				}
			} else if(existe($campo_id) && existe($valor)){
				$campos = null; $campo_sem_aspas_simples = null;
				
				$campo_nome = "linguagem_codigo"; $campo_valor = $linguagemCodigo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "modulo"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id"; $campo_valor = $campo_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "valor"; $campo_valor = $valor; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "tipo"; $campo_valor = 'string'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				
				banco_insert_name
				(
					$campos,
					"variaveis"
				);
				
				$alterouLinguagem = true;
			}
		}
		
		foreach($banco_antes as $ref => $campo){
			if(!isset($campo['verificado'])){
				banco_delete
				(
					"variaveis",
					"WHERE id_variaveis='".$ref."'"
				);
				
				$alterouLinguagem = true;
			}
		}
		
		// ===== Atualização dos demais campos.
		
		if(isset($alterouLinguagem)){
			$campo_nome = "language"; $alteracoes_name = $campo_nome; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');
			
			// ===== Alterar versão e data.
			
			$editar = Array(
				'tabela' => $modulo['tabela']['nome'],
				'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'",
			);
			
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
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/variaveis/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas(Array(
			'nome',
			$modulo['tabela']['status'],
			$modulo['tabela']['versao'],
			$modulo['tabela']['data_criacao'],
			$modulo['tabela']['data_modificacao'],
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
		
		// ===== Variáveis
		
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_variaveis',
				'id',
				'valor',
			))
			,
			"variaveis",
			"WHERE linguagem_codigo='".$_GESTOR['linguagem-codigo']."'"
			." AND modulo='".$id."'"
			." AND tipo='string'"
			." ORDER BY id ASC"
		);
		
		$pagina = $_GESTOR['pagina'];
		
		$cel_nome = 'items'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		$pagina = modelo_var_troca($pagina,"#fields-name#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'fields-name')));
		
		$id_label = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-id-label'));
		$id_placeholder = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-id-placeholder'));
		$value_label = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-value-label'));
		$value_placeholder = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-value-placeholder'));
		
		$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#form-id-label#",$id_label);
		$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#form-id-placeholder#",$id_placeholder);
		$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#form-value-label#",$value_label);
		$cel[$cel_nome] = modelo_var_troca($cel[$cel_nome],"#form-value-placeholder#",$value_placeholder);
		
		$count = 1;
		
		if($variaveis){
			foreach($variaveis as $ling){
				$cel_aux = $cel[$cel_nome];
		
				$cel_aux = modelo_var_troca($cel_aux,"#campo#",gestor_variaveis(Array('restart' => $_GESTOR['modulo-id'],'modulo' => $_GESTOR['modulo-id'],'id' => 'field-name')).' '.$count);
				$cel_aux = modelo_var_troca($cel_aux,"#id-num#",$count);
				$cel_aux = modelo_var_troca($cel_aux,"#value-num#",$count);
				$cel_aux = modelo_var_troca($cel_aux,"#ref-num#",$count);
				$cel_aux = modelo_var_troca($cel_aux,"#id-valor#",$ling['id']);
				$cel_aux = modelo_var_troca($cel_aux,"#value-valor#",htmlspecialchars($ling['valor']));
				$cel_aux = modelo_var_troca($cel_aux,"#ref-valor#",$ling['id_variaveis']);
				
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
				$count++;
			}
		}
		
		$pagina = modelo_var_troca($pagina,"#num-campos#",$count);
		$pagina = modelo_var_troca($pagina,"#campo-modelo#",$cel[$cel_nome]);
		
		$_GESTOR['pagina'] = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Interface alteracoes finalizar opções
	
	$_GESTOR['interface'][$_GESTOR['interface-opcao']]['finalizar'] = Array(
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
			'editar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-edit')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')),
				'icon' => 'edit',
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
					'regra' => 'selecao-obrigatorio',
					'campo' => 'linguagem',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-language-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'ids-obrigatorios',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-id-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'valores-obrigatorios',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-value-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'language',
					'nome' => 'linguagem',
					'procurar' => true,
					//'limpar' => true,
					'valor_selecionado' => $_GESTOR['linguagem-codigo'],
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-language-placeholder')),
					'dados' => $modulo['selectDadosLinguagem'],
				)
			)
		)
	);
}

function modulos_adicionar(){
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
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "titulo"; $post_nome = "titulo"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id_modulos_grupos"; $post_nome = "grupo"; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "icone"; $post_nome = "icone"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "icone2"; $post_nome = "icone2"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "nao_menu_principal"; $post_nome = "menu"; 						if($_REQUEST[$post_nome] == 'nao')		$campos[] = Array($campo_nome,'1',true);
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
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'grupo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'grup',
					'nome' => 'grupo',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos_grupos',
						'campo' => 'nome',
						'id_numerico' => 'id_modulos_grupos',
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'menu',
					'nome' => 'menu',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-menu-placeholder')),
					'dados' => Array(
						Array(
							'texto' => 'Sim',
							'valor' => 'sim',
						),
						Array(
							'texto' => 'Não',
							'valor' => 'nao',
						),
					),
				)
			)
		)
	);
}

function modulos_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'id_modulos_grupos',
		'icone',
		'icone2',
		'nao_menu_principal',
		'titulo',
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
		
		$campo_nome = "id_modulos_grupos"; $request_name = 'grupo'; $alteracoes_name = 'grup'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]),'tabela' => Array(
				'nome' => 'modulos_grupos',
				'campo' => 'nome',
				'id_numerico' => 'id_modulos_grupos',
			));}
		
		$campo_nome = "titulo"; $request_name = $campo_nome; $alteracoes_name = 'title'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "icone"; $request_name = $campo_nome; $alteracoes_name = 'icon'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "icone2"; $request_name = $campo_nome; $alteracoes_name = 'icon-2'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		$campo_nome = "nao_menu_principal"; $request_name = 'menu'; $alteracoes_name = 'menu'; if((banco_select_campos_antes($campo_nome) ? 'nao' : 'sim' ) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."=" . (banco_escape_field($_REQUEST[$request_name]) == 'nao' ? '1' : 'NULL' ); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => (banco_select_campos_antes($campo_nome) ? 'Não' : 'Sim'),'valor_depois' => (banco_escape_field($_REQUEST[$request_name])) == 'sim' ? 'Sim' : 'Não');}
		
		
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
		$icone = (isset($retorno_bd['icone']) ? $retorno_bd['icone'] : '');
		$icone2 = (isset($retorno_bd['icone2']) ? $retorno_bd['icone2'] : '');
		$menu_principal = (isset($retorno_bd['nao_menu_principal']) ? 'nao' : 'sim');
		$id_modulos_grupos = (isset($retorno_bd['id_modulos_grupos']) ? $retorno_bd['id_modulos_grupos'] : '');
		$titulo = (isset($retorno_bd['titulo']) ? $retorno_bd['titulo'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#icone#',$icone);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#icone2#',$icone2);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#titulo#',$titulo);
		
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
			'variaveis' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/variaveis/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-variable')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-variable')),
				'icon' => 'book',
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
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'grupo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'grup',
					'nome' => 'grupo',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos_grupos',
						'campo' => 'nome',
						'id_numerico' => 'id_modulos_grupos',
						'id_selecionado' => $id_modulos_grupos,
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'menu',
					'nome' => 'menu',
					'procurar' => true,
					'limpar' => true,
					'valor_selecionado' => $menu_principal,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-menu-placeholder')),
					'dados' => Array(
						Array(
							'texto' => 'Sim',
							'valor' => 'sim',
						),
						Array(
							'texto' => 'Não',
							'valor' => 'nao',
						),
					),
				)
			)
		)
	);
}

function modulos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'variaveis': 
			$_GESTOR['interface-opcao'] = 'alteracoes';
		break;
		case 'sincronizar-bancos': 
			$_GESTOR['interface-nao-aplicar'] = true;
		break;
	}
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'id_modulos_grupos',
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
							'id' => 'id_modulos_grupos',
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'form-grup-label')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'modulos_grupos',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id_modulos_grupos',
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
					'variaveis' => Array(
						'url' => 'variaveis/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-variable')),
						'icon' => 'book',
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

// ==== Start

function modulos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': modulos_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		modulos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': modulos_adicionar(); break;
			case 'editar': modulos_editar(); break;
			case 'variaveis': modulos_variaveis(); break;
			case 'sincronizar-bancos': modulos_sincronizar_bancos(); break;
			case 'copiar-variaveis': modulos_copiar_variaveis(); break;
		}
		
		interface_finalizar();
	}
}

modulos_start();

?>