<?php

// ===== API responsável por disparar solicitações ao 'cliente'.

global $_GESTOR;

$_GESTOR['biblioteca-api-cliente']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções de chamadas do app.

function api_cliente_app_vouchers($params = false){
	/**********
		Descrição: api responsável pela manipulação dos dados dos vouchers com a plataforma servidor.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id_hosts - Int - Obrigatório - Identificador do host.
	
	// Se opcao == 'atualizar-status':
	
	// id_hosts_vouchers - Int - Obrigatório - Identificador do registro no banco de dados.
	
	// ===== 
	
	if(isset($opcao) && isset($id_hosts)){
		$dados = Array();
		
		switch($opcao){
			case 'atualizar-status':
				$hosts_vouchers = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_vouchers',
					'campos' => Array(
						'id_hosts_vouchers',
						'status',
						'data_uso',
					),
					'extra' => 
						"WHERE id_hosts_vouchers='".$id_hosts_vouchers."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$dados['vouchers'] = $hosts_vouchers;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'vouchers',
			'id_hosts' => $id_hosts,
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

// ===== Funções de chamadas do cron.

function api_cliente_cron_servicos($params = false){
	/**********
		Descrição: api responsável pela manipulação dos dados de serviços com a plataforma servidor.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id_hosts - Int - Obrigatório - Identificador do host.
	
	// Se opcao == 'quantidade'
	
	// servicos - Array - Obrigatório - Conjunto com todos os serviços que serão atualizados os estoques.
	
	// Se opcao == 'quantidadeVariacao'
	
	// variacaoServicos - Array - Obrigatório - Conjunto com todos os serviços que serão atualizados os estoques.
	
	// ===== 
	
	if(isset($opcao) && isset($id_hosts)){
		$dados = Array();
		
		switch($opcao){
			case 'quantidade':
				$dados['servicos'] = $servicos;
			break;
			case 'quantidadeVariacao':
				$dados['variacaoServicos'] = $variacaoServicos;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'cron-servicos',
			'id_hosts' => $id_hosts,
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_cron_pedidos($params = false){
	/**********
		Descrição: api responsável pela manipulação dos dados de pedidos com a plataforma servidor.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id_hosts - Int - Obrigatório - Identificador do host.
	// pedidos - Array - Obrigatório - Conjunto com todos os pedidos que serão atualizados.
	// config - Array - Obrigatório - Configuração dos pedidos para poder saber quais campos atualizar.
	
	// ===== 
	
	if(isset($opcao) && isset($id_hosts) && isset($pedidos) && isset($config)){
		$dados = Array();
		
		switch($opcao){
			case 'atualizar':
				// ===== Pedidos no banco de dados.
				
				foreach($pedidos as $pedido){
					$hosts_pedidos = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts_pedidos',
						'campos' => $config['atualizarCampos'],
						'extra' => 
							"WHERE id_hosts_pedidos='".$pedido."'"
							." AND id_hosts='".$id_hosts."'"
					));
					
					$pedidos_proc[$pedido] = $hosts_pedidos;
				}
				
				$dados['pedidos'] = $pedidos_proc;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'cron-pedidos',
			'id_hosts' => $id_hosts,
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

// ===== Funções de chamadas do servidor.

function api_cliente_paginas($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id - String - Opcional - Identificador do registro no banco de dados.
	// id_numerico - String - Opcional - Identificador numérico do registro no banco de dados.
	// caminhoMudou - String - Opcional - Se mudou o caminho, criar a referência 301 para redirect automático.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'editar':
			case 'adicionar':
				$resultado = banco_select_name
				(
					"*"
					,
					"hosts_paginas",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				
				// ===== Remover ítens dos resultados de valores do servidor locais
				
				unset($resultado[0]['id_hosts']);
				unset($resultado[0]['id_usuarios']);
				
				$dados['registro'] = $resultado[0];
				
				if(isset($caminhoMudou)) $dados['caminhoMudou'] = $caminhoMudou;
			break;
			case 'status':
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					))
					,
					"hosts_paginas",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				$dados['registro'] = $resultado[0];
			break;
			case 'excluir':
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					))
					,
					"hosts_paginas",
					"WHERE id_hosts_paginas='".$id_numerico."'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				$dados['registro'] = $resultado[0];
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'paginas',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_layouts($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id - String - Opcional - Identificador do registro no banco de dados.
	// id_numerico - String - Opcional - Identificador numérico do registro no banco de dados.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'editar':
			case 'adicionar':
				$resultado = banco_select_name
				(
					"*"
					,
					"hosts_layouts",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				
				// ===== Remover ítens dos resultados de valores do servidor locais
				
				unset($resultado[0]['id_hosts']);
				unset($resultado[0]['id_usuarios']);
				
				$dados['registro'] = $resultado[0];
			break;
			case 'status':
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_hosts_layouts',
						'status',
						'versao',
						'data_modificacao',
					))
					,
					"hosts_layouts",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				$dados['registro'] = $resultado[0];
			break;
			case 'excluir':
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_hosts_layouts',
						'status',
						'versao',
						'data_modificacao',
					))
					,
					"hosts_layouts",
					"WHERE id_hosts_layouts='".$id_numerico."'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				$dados['registro'] = $resultado[0];
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'layouts',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_componentes($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id - String - Opcional - Identificador do registro no banco de dados.
	// id_numerico - String - Opcional - Identificador numérico do registro no banco de dados.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'editar':
			case 'adicionar':
				$resultado = banco_select_name
				(
					"*"
					,
					"hosts_componentes",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				
				// ===== Remover ítens dos resultados de valores do servidor locais
				
				unset($resultado[0]['id_hosts']);
				unset($resultado[0]['id_usuarios']);
				
				$dados['registro'] = $resultado[0];
			break;
			case 'status':
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_hosts_componentes',
						'status',
						'versao',
						'data_modificacao',
					))
					,
					"hosts_componentes",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				$dados['registro'] = $resultado[0];
			break;
			case 'excluir':
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_hosts_componentes',
						'status',
						'versao',
						'data_modificacao',
					))
					,
					"hosts_componentes",
					"WHERE id_hosts_componentes='".$id_numerico."'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				);
				
				$dados['registro'] = $resultado[0];
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'componentes',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_templates_atualizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// forceUpdate - Bool - Opcional - Forçar a atualização ao invés de atualizar apenas quando necessário.
	// categoriaAtualizarID - String - Opcional - Atualizar apenas este ID.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'update':
				$registros = Array();
				$layoutsProcessados = Array();
				$paginasProcessados = Array();
				
				// ===== Categorias que devem ter os templates instalados ou atualizados com suas configurações.
				
				$config = gestor_incluir_configuracao(Array(
					'id' => 'templates.config',
				));
				
				$categoriasIds = $config['atualizar'];
				
				// ===== Buscar na tabela templates todos os templates padrões da categoria "templates" de id_numerico == 13.
				
				$templates = banco_select_name
				(
					banco_campos_virgulas(Array(
						't1.id_categorias_pai',
						't1.id',
						't1.nome',
						't1.html',
						't1.css',
						't1.versao',
						't2.id',
						't2.id_categorias',
						't2.plugin',
					))
					,
					"templates AS t1,categorias AS t2",
					"WHERE t1.padrao IS NOT NULL"
					." AND t1.status!='D'"
					." AND t1.id_categorias=t2.id_categorias"
					." AND t2.status!='D'"
					." AND t2.id_modulos='13'"
					
					// ===== Atualizar apenas um único ID.
					
					.(isset($categoriaAtualizarID) ? " AND t2.id='".$categoriaAtualizarID."'" : '') 
				);
				
				// ===== Plugins do host.
				
				$hosts_plugins = banco_select(Array(
					'tabela' => 'hosts_plugins',
					'campos' => Array(
						'plugin',
					),
					'extra' => 
						"WHERE habilitado IS NOT NULL"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
				));
				
				// ===== Relacionar todos os resultados com as categorias ativas
				
				if($templates)
				foreach($templates as $template){
					// ===== Variáveis de verificação de atualização do template ou se é componente.
					
					$atualizarTemplate = false;
					$componenteTemplate = false;
					
					// ===== Verificar se o template é um componente. O id_numerico da categoria de componente é '29'.
					
					if(existe($template['t1.id_categorias_pai'])){
						if($template['t1.id_categorias_pai'] == '29'){
							unset($catDados);
							
							$categoriasId = $template['t2.id'];
							$catDados['modulo'] = 'interface-hosts';
							
							$componenteTemplate = true;
							$atualizarTemplate = true;
						}
					}
					
					// ===== Verificar se o template é página ou layout na config.
					
					if($categoriasIds && !$componenteTemplate)
					foreach($categoriasIds as $categoriasId => $catDados){
						if($template['t2.id'] == $categoriasId){
							// ===== Verificar se o plugin está ativo ou não. Senão atualizar o template.
							
							if($template['t2.plugin']){
								if($hosts_plugins)
								foreach($hosts_plugins as $hosts_plugin){
									if($template['t2.plugin'] == $hosts_plugin['plugin']){
										$atualizarTemplate = true;
										break;
									}
								}
							} else {
								$atualizarTemplate = true;
							}
							
							break;
						}
					}
					
					if($atualizarTemplate){
						// ===== Só incluir registros do tipo layouts, depois do tipo páginas e em seguida componentes. Os id_numericos da categoria deles são respectivamente '17', '18' e '29'.
						
						$templateId = '';
						$adicionarRegistro = false;
						
						if(existe($template['t1.id_categorias_pai'])){
							switch($template['t1.id_categorias_pai']){
								case '17': $templateId = 'templates_layouts'; $adicionarRegistro = true; break;
								case '18': $templateId = 'templates_paginas'; $adicionarRegistro = true; break;
								case '29': $templateId = 'templates_componentes'; $adicionarRegistro = true; break;
							}
						}
						
						if($adicionarRegistro){
							// ===== Verificar se um hosts_templates está ativo.
							
							$hosts_templates = banco_select(Array(
								'unico' => true,
								'tabela' => 'hosts_templates',
								'campos' => Array(
									'id',
									'nome',
									'html',
									'css',
									'versao',
								),
								'extra' => 
									"WHERE id_categorias='".$template['t2.id_categorias']."'"
									." AND ativo IS NOT NULL"
							));
							
							// ===== Se está ativo, subistituir ele no lugar do template. Senão usar o template padrão. É necessário colocar o prefixo 'hosts_' pois o template_id pode ser o mesmo em templates diferentes.
							
							if($hosts_templates){
								$registro = Array(
									'id' => 'hosts_'.$hosts_templates['id'],
									'categoria' => $categoriasId,
									'template-id' => $templateId,
									'nome' => $hosts_templates['nome'],
									'html' => $hosts_templates['html'],
									'css' => $hosts_templates['css'],
									'versao' => $hosts_templates['versao'],
								);
							} else {
								$registro = Array(
									'id' => $template['t1.id'],
									'categoria' => $categoriasId,
									'template-id' => $templateId,
									'nome' => $template['t1.nome'],
									'html' => $template['t1.html'],
									'css' => $template['t1.css'],
									'versao' => $template['t1.versao'],
								);
							}
							
							if(isset($catDados['caminho'])) $registro['caminho'] = $catDados['caminho'];
							if(isset($catDados['tipo'])) $registro['tipo'] = $catDados['tipo'];
							if(isset($catDados['layout-id'])) $registro['layout-id'] = $catDados['layout-id'];
							if(isset($catDados['modulo'])) $registro['modulo'] = $catDados['modulo'];
							if(isset($catDados['nao-adicionar'])) $registro['nao-adicionar'] = $catDados['nao-adicionar'];
							if(isset($catDados['multiplo'])) $registro['multiplo'] = $catDados['multiplo'];
							
							$registros[] = $registro;
						}
					}
				}
				
				
				if(isset($registros)){
					$templates_layouts = Array();
					
					// ===== Ordenar os registros pelo tipo primeiro 'templates_layouts' e depois 'templates_paginas' pois é uma página sempre necessita de um layout. Em seguida o 'templates_componentes'.
					
					$registros_templates_layouts = Array();
					$registros_templates_paginas = Array();
					$registros_templates_componentes = Array();
					
					foreach($registros as $registro){
						switch($registro['template-id']){
							case 'templates_layouts': $registros_templates_layouts[] = $registro; break;
							case 'templates_paginas': $registros_templates_paginas[] = $registro; break;
							case 'templates_componentes': $registros_templates_componentes[] = $registro; break;
						}
					}
					
					$registros = array_merge($registros_templates_layouts,$registros_templates_paginas,$registros_templates_componentes);
					
					// ===== Criar ou atualizar as páginas, layouts e componentes mestres no host do cliente.
					
					$hosts_paginas = banco_select_name
					(
						'*'
						,
						"hosts_paginas",
						"WHERE template_padrao IS NOT NULL"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
						." AND status!='D'"
					);
					
					$hosts_layouts = banco_select_name
					(
						'*'
						,
						"hosts_layouts",
						"WHERE template_padrao IS NOT NULL"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
						." AND status!='D'"
					);
					
					$hosts_componentes = banco_select_name
					(
						'*'
						,
						"hosts_componentes",
						"WHERE template_padrao IS NOT NULL"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
						." AND status!='D'"
					);
					
					// ===== Dados do usuário.
					
					$usuario = gestor_usuario();
					
					// ===== Processar todos os registros.
					
					foreach($registros as $registro){
						switch($registro['template-id']){
							case 'templates_layouts':
								// ===== Verificar a existência de um layout e se existir ver necessidade de atualizar.
								
								$found = false;
								$update = false;
								if($hosts_layouts){
									foreach($hosts_layouts as $hosts_layout){
										if($hosts_layout['template_categoria'] == $registro['categoria']){
											if($hosts_layout['template_id'] == $registro['id']){
												if(
													(int)$hosts_layout['template_versao'] < (int)$registro['versao'] &&
													!existe($hosts_layout['template_modificado'])
												){
													$update = true;
												}
											} else {
												$update = true;
											}
										
											$found = true;
											break;
										}
									}
								}
								
								// ===== Se não existir, criar o layout.
								
								if(!$found){
									// ===== Definição do identificador
		
									$id = banco_identificador(Array(
										'id' => $registro['nome'],
										'tabela' => Array(
											'nome' => 'hosts_layouts',
											'campo' => 'id',
											'id_nome' => 'id_hosts_layouts',
											'where' => 'id_hosts="'.$host_verificacao['id_hosts'].'"', // Somente acessar dados do host permitido.
										),
									));
									
									// ===== Campos gerais
									
									banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
									banco_insert_name_campo('id_hosts',$host_verificacao['id_hosts']);
									banco_insert_name_campo('nome',$registro['nome']);
									banco_insert_name_campo('id',$id);
									banco_insert_name_campo('html',$registro['html']);
									banco_insert_name_campo('css',$registro['css']);
									
									// ===== Campos comuns
									
									banco_insert_name_campo('status','A');
									banco_insert_name_campo('versao',$registro['versao']);
									banco_insert_name_campo('data_criacao','NOW()',true);
									banco_insert_name_campo('data_modificacao','NOW()',true);
									
									// ===== Campos do template
									
									banco_insert_name_campo('template_padrao','1',true);
									banco_insert_name_campo('template_categoria',$registro['categoria']);
									banco_insert_name_campo('template_id',$registro['id']);
									banco_insert_name_campo('template_versao',$registro['versao']);
									
									// ===== Executar inclusão.
									
									banco_insert_name
									(
										banco_insert_name_campos(),
										"hosts_layouts"
									);
									
									$id_hosts_layouts = banco_last_id();
									
									// ===== Carregar os novos dados para enviar ao host do cliente
									
									$hosts_layouts_processado = banco_select_name
									(
										'*'
										,
										"hosts_layouts",
										"WHERE id_hosts_layouts='".$id_hosts_layouts."'"
									);
								} else if($update || isset($forceUpdate)){
									// ===== Atualizar dados.
									
									banco_update_campo('html',$registro['html']);
									banco_update_campo('css',$registro['css']);
									banco_update_campo('template_versao',$registro['versao']);
									banco_update_campo('template_id',$registro['id']);
									
									// ===== Dados padrões.
									
									banco_update_campo('versao',$registro['versao']);
									banco_update_campo('data_modificacao','NOW()',true);
									
									// ===== Executar atualização.
									
									banco_update_executar('hosts_layouts',"WHERE id='".$hosts_layout['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'");
									
									// ===== Carregar os dados atualizados para enviar ao host do cliente.
									
									$hosts_layouts_processado = banco_select_name
									(
										'*'
										,
										"hosts_layouts",
										"WHERE id='".$hosts_layout['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'"
									);
									
									$id_hosts_layouts = $hosts_layouts_processado[0]['id_hosts_layouts'];
								}
								
								if(!$found || $update || isset($forceUpdate)){
									// ===== Remover campos internos do servidor.
									
									unset($hosts_layouts_processado[0]['id_hosts']);
									unset($hosts_layouts_processado[0]['id_usuarios']);
									
									// ===== Incluir no registro processado os dados atualizados.
									
									$layoutsProcessados[] = $hosts_layouts_processado[0];
								} else {
									// ===== Carregar os 'id_hosts_layouts' caso não haja nem novo registro, nem atualização.
									
									$hosts_layouts_processado = banco_select_name
									(
										banco_campos_virgulas(Array(
											'id_hosts_layouts',
										))
										,
										"hosts_layouts",
										"WHERE id='".$hosts_layout['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'"
									);
									
									$id_hosts_layouts = $hosts_layouts_processado[0]['id_hosts_layouts'];
								}
								
								// ===== Guardar os IDs do layouts para poder usar nos hosts_paginas quando criar ou atualizar registro da categoria 'templates_paginas'.
								
								$templates_layouts[$registro['categoria']] = $id_hosts_layouts;
							break;
							case 'templates_paginas':
								// ===== Verificar se é um template do tipo multiplo como 'servicos' e 'postagens'.
							
								if(isset($registro['multiplo'])){
									// ===== Verificar a existência de páginas e se existir ver necessidade de atualizar.
									
									if($hosts_paginas){
										foreach($hosts_paginas as $hosts_pagina){
											if($hosts_pagina['template_categoria'] == $registro['categoria']){
												$update = false;
												if($hosts_pagina['template_id'] == $registro['id']){
													if(
														(int)$hosts_pagina['template_versao'] < (int)$registro['versao'] &&
														!existe($hosts_pagina['template_modificado'])
													){
														$update = true;
													}
												} else {
													$update = true;
												}
												
												// ===== Atualizar se necesário.
												
												if($update || isset($forceUpdate)){
													// ===== Atualizar dados.
													
													banco_update_campo('html',$registro['html']);
													banco_update_campo('css',$registro['css']);
													banco_update_campo('template_versao',$registro['versao']);
													banco_update_campo('template_id',$registro['id']);
													
													// ===== Dados padrões.
													
													banco_update_campo('versao',$registro['versao']);
													banco_update_campo('data_modificacao','NOW()',true);
													
													// ===== Executar atualização.
													
													banco_update_executar('hosts_paginas',"WHERE id='".$hosts_pagina['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'");
													
													// ===== Carregar os dados atualizados para enviar ao host do cliente.
													
													$hosts_paginas_processado = banco_select_name
													(
														'*'
														,
														"hosts_paginas",
														"WHERE id='".$hosts_pagina['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'"
													);
													
													// ===== Remover campos internos do servidor.
													
													unset($hosts_paginas_processado[0]['id_hosts']);
													unset($hosts_paginas_processado[0]['id_usuarios']);
													
													// ===== Incluir no registro processado os dados atualizados.
										
													$paginasProcessados[] = $hosts_paginas_processado[0];
												}
											}
										}
									}
								} else {
									// ===== Verificar a existência de uma página e se existir ver necessidade de atualizar.
									
									$found = false;
									$update = false;
									if($hosts_paginas){
										foreach($hosts_paginas as $hosts_pagina){
											if($hosts_pagina['template_categoria'] == $registro['categoria']){
												if($hosts_pagina['template_id'] == $registro['id']){
													if(
														(int)$hosts_pagina['template_versao'] < (int)$registro['versao'] &&
														!existe($hosts_pagina['template_modificado'])
													){
														$update = true;
													}
												} else {
													$update = true;
												}
												
												$found = true;
												break;
											}
										}
									}
									
									// ===== Se não existir, criar a página.
									
									if(!$found){
										// ===== Definição do identificador
			
										$id = banco_identificador(Array(
											'id' => $registro['nome'],
											'tabela' => Array(
												'nome' => 'hosts_paginas',
												'campo' => 'id',
												'id_nome' => 'id_hosts_paginas',
												'where' => 'id_hosts="'.$host_verificacao['id_hosts'].'"', // Somente acessar dados do host permitido.
											),
										));
										
										// ===== Campos gerais
										
										banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
										banco_insert_name_campo('id_hosts',$host_verificacao['id_hosts']);
										banco_insert_name_campo('nome',$registro['nome']);
										banco_insert_name_campo('id',$id);
										banco_insert_name_campo('id_hosts_layouts',$templates_layouts[$registro['layout-id']]);
										banco_insert_name_campo('tipo',$registro['tipo']);
										banco_insert_name_campo('caminho',$registro['caminho']);
										banco_insert_name_campo('html',$registro['html']);
										banco_insert_name_campo('css',$registro['css']);
										
										// ===== Campos comuns
										
										banco_insert_name_campo('status','A');
										banco_insert_name_campo('versao',$registro['versao']);
										banco_insert_name_campo('data_criacao','NOW()',true);
										banco_insert_name_campo('data_modificacao','NOW()',true);
										
										// ===== Campos do template
										
										banco_insert_name_campo('template_padrao','1',true);
										banco_insert_name_campo('template_categoria',$registro['categoria']);
										banco_insert_name_campo('template_id',$registro['id']);
										banco_insert_name_campo('template_versao',$registro['versao']);
										
										// ===== Caso exista modulo, criar na tabela.
										
										if(isset($registro['modulo'])){ banco_insert_name_campo('modulo',$registro['modulo']); }
										
										// ===== Executar inclusão.
										
										banco_insert_name
										(
											banco_insert_name_campos(),
											"hosts_paginas"
										);
										
										$id_hosts_paginas = banco_last_id();
										
										// ===== Carregar os novos dados para enviar ao host do cliente
										
										$hosts_paginas_processado = banco_select_name
										(
											'*'
											,
											"hosts_paginas",
											"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
										);
									} else if($update || isset($forceUpdate)){
										// ===== Atualizar dados.
										
										banco_update_campo('html',$registro['html']);
										banco_update_campo('css',$registro['css']);
										banco_update_campo('template_versao',$registro['versao']);
										banco_update_campo('template_id',$registro['id']);
										
										// ===== Dados padrões.
										
										banco_update_campo('versao',$registro['versao']);
										banco_update_campo('data_modificacao','NOW()',true);
										
										// ===== Executar atualização.
										
										banco_update_executar('hosts_paginas',"WHERE id='".$hosts_pagina['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'");
										
										// ===== Carregar os dados atualizados para enviar ao host do cliente.
										
										$hosts_paginas_processado = banco_select_name
										(
											'*'
											,
											"hosts_paginas",
											"WHERE id='".$hosts_pagina['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'"
										);
									}
									
									if(!$found || $update || isset($forceUpdate)){
										// ===== Remover campos internos do servidor.
										
										unset($hosts_paginas_processado[0]['id_hosts']);
										unset($hosts_paginas_processado[0]['id_usuarios']);
										
										// ===== Incluir no registro processado os dados atualizados.
										
										$paginasProcessados[] = $hosts_paginas_processado[0];
									}
								}
							break;
							case 'templates_componentes':
								// ===== Verificar a existência de um componente e se existir ver necessidade de atualizar.
								
								$found = false;
								$update = false;
								if($hosts_componentes){
									foreach($hosts_componentes as $hosts_componente){
										if($hosts_componente['template_categoria'] == $registro['categoria']){
											if($hosts_componente['template_id'] == $registro['id']){
												if(
													(int)$hosts_componente['template_versao'] < (int)$registro['versao'] &&
													!existe($hosts_componente['template_modificado'])
												){
													$update = true;
												}
											} else {
												$update = true;
											}
										
											$found = true;
											break;
										}
									}
								}
								
								// ===== Se não existir, criar o componente.
								
								if(!$found){
									// ===== Definição do identificador
		
									$id = banco_identificador(Array(
										'id' => $registro['nome'],
										'tabela' => Array(
											'nome' => 'hosts_componentes',
											'campo' => 'id',
											'id_nome' => 'id_hosts_componentes',
											'where' => 'id_hosts="'.$host_verificacao['id_hosts'].'"', // Somente acessar dados do host permitido.
										),
									));
									
									// ===== Campos gerais
									
									banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
									banco_insert_name_campo('id_hosts',$host_verificacao['id_hosts']);
									banco_insert_name_campo('nome',$registro['nome']);
									banco_insert_name_campo('id',$id);
									banco_insert_name_campo('html',$registro['html']);
									banco_insert_name_campo('css',$registro['css']);
									
									// ===== Campos comuns
									
									banco_insert_name_campo('status','A');
									banco_insert_name_campo('versao',$registro['versao']);
									banco_insert_name_campo('data_criacao','NOW()',true);
									banco_insert_name_campo('data_modificacao','NOW()',true);
									
									// ===== Campos do template
									
									banco_insert_name_campo('template_padrao','1',true);
									banco_insert_name_campo('template_categoria',$registro['categoria']);
									banco_insert_name_campo('template_id',$registro['id']);
									banco_insert_name_campo('template_versao',$registro['versao']);
									
									// ===== Caso exista modulo, criar na tabela.
									
									if(isset($registro['modulo'])){ banco_insert_name_campo('modulo',$registro['modulo']); }
									
									// ===== Executar inclusão.
									
									banco_insert_name
									(
										banco_insert_name_campos(),
										"hosts_componentes"
									);
									
									$id_hosts_componentes = banco_last_id();
									
									// ===== Carregar os novos dados para enviar ao host do cliente
									
									$hosts_componentes_processado = banco_select_name
									(
										'*'
										,
										"hosts_componentes",
										"WHERE id_hosts_componentes='".$id_hosts_componentes."'"
									);
								} else if($update || isset($forceUpdate)){
									// ===== Atualizar dados.
									
									banco_update_campo('html',$registro['html']);
									banco_update_campo('css',$registro['css']);
									banco_update_campo('template_versao',$registro['versao']);
									banco_update_campo('template_id',$registro['id']);
									
									// ===== Dados padrões.
									
									banco_update_campo('versao',$registro['versao']);
									banco_update_campo('data_modificacao','NOW()',true);
									
									// ===== Executar atualização.
									
									banco_update_executar('hosts_componentes',"WHERE id='".$hosts_componente['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'");
									
									// ===== Carregar os dados atualizados para enviar ao host do cliente.
									
									$hosts_componentes_processado = banco_select_name
									(
										'*'
										,
										"hosts_componentes",
										"WHERE id='".$hosts_componente['id']."' AND status!='D' AND id_hosts='".$host_verificacao['id_hosts']."'"
									);
								}
								
								if(!$found || $update || isset($forceUpdate)){
									// ===== Remover campos internos do servidor.
									
									unset($hosts_componentes_processado[0]['id_hosts']);
									unset($hosts_componentes_processado[0]['id_usuarios']);
									
									// ===== Incluir no registro processado os dados atualizados.
									
									$componentesProcessados[] = $hosts_componentes_processado[0];
								}
							break;
						}
					}
				}
				
				$dados['layouts'] = $layoutsProcessados;
				$dados['paginas'] = $paginasProcessados;
				$dados['componentes'] = $componentesProcessados;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'templates-atualizar',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_arquivos($params = false){
	/**********
		Descrição: Interface de manipulção de arquivos do servidor para o cliente.
	**********/
	
	// ===== Parâmetros padrões
	
	$interface = 'arquivos'; // Interface de conexão padrão
	
	// ===== 
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// Se opcao = 'adicionar'
		// nomeExtensao - String - Obrigatório - Nome com extensão do arquivo.
		// caminhoArquivo - String - Obrigatório - Caminho do arquivo.
		// tipoArquivo - String - Obrigatório - Tipo do arquivo.
		// id_hosts_arquivos - Int - Obrigatório - Identificador numérico do arquivo.
		
		// caminhoArquivoMini - String - Opcional - Caminho do arquivo miniatura.
		// tipoArquivoMini - String - Opcional - Tipo do arquivo miniatura.
		
		// anoDir - String - Opcional - Diretório do ano.
		// mesDir - String - Opcional - Diretório do mês.
		
	// Se opcao = 'excluir'
		// caminhoArquivo - String - Obrigatório - Caminho do arquivo.
		// id_hosts_arquivos - Int - Obrigatório - Identificador numérico do arquivo.
		
		// caminhoArquivoMini - String - Opcional - Caminho do arquivo miniatura.
		
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'adicionar':
				$arquivos = Array();
				
				// ===== Campos obrigatórios conferência
				
				if(isset($nomeExtensao) && isset($caminhoArquivo) && isset($tipoArquivo) && isset($id_hosts_arquivos)){
					// ===== Criar 'anoDir' e 'mesDir' caso não tenham sido definidos.
					
					if(!isset($anoDir)) $anoDir = date('Y');
					if(!isset($mesDir)) $mesDir = date('m');
					
					// ===== Incluir arquivo
					
					$arquivos[] = Array(
						'caminho' => $caminhoArquivo,
						'tipo' => $tipoArquivo,
						'post' => 'file',
					);
					
					// ===== Incluir arquivo mini
					
					$arquivoMini = false;
					if(isset($caminhoArquivoMini) && isset($tipoArquivoMini)){
						$arquivos[] = Array(
							'caminho' => $caminhoArquivoMini,
							'tipo' => $tipoArquivoMini,
							'post' => 'file',
						);
						
						$arquivoMini = true;
					}
					
					$dados['registro'] = Array(
						'anoDir' => $anoDir,
						'mesDir' => $mesDir,
						'nomeExtensao' => $nomeExtensao,
					);
					
					if($arquivoMini) $dados['registro']['arquivoMini'] = true;
					
					// ===== Incluir informações do arquivo do banco de dados.
					
					$resultado = banco_select_name
					(
						"*"
						,
						"hosts_arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
					);
					
					
					// ===== Remover ítens dos resultados de valores do servidor locais
					
					unset($resultado[0]['id_hosts']);
					unset($resultado[0]['id_usuarios']);
					
					$dados['tabela'] = $resultado[0];
				} else {
					return api_cliente_retornar_erro(Array(
						'msg' => 'Params-mandatory: id_hosts_arquivos, caminhoArquivo, tipoArquivo e nomeExtensao',
					));
				}
			break;
			case 'excluir':
				$arquivos = null;
				
				// ===== Campos obrigatórios conferência
				
				if(isset($caminhoArquivo) && isset($id_hosts_arquivos)){
					
					$dados['registro'] = Array(
						'caminhoArquivo' => $caminhoArquivo,
					);
					
					if(isset($caminhoArquivoMini)){$dados['registro']['caminhoArquivoMini'] = $caminhoArquivoMini;}
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_hosts_arquivos',
							'status',
							'versao',
							'data_modificacao',
						))
						,
						"hosts_arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
					);
					
					$dados['tabela'] = $resultado[0];
				} else {
					return api_cliente_retornar_erro(Array(
						'msg' => 'Params-mandatory: id_hosts_arquivos e caminhoArquivo',
					));
				}
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => $interface,
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
			'arquivos' => $arquivos,
		));
		
		return $retorno;
	}
}

function api_cliente_servicos($params = false){
	/**********
		Descrição: api responsável pela manipulação dos dados de serviços com a plataforma servidor.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id - String - Opcional - Identificador do registro no banco de dados.
	// id_numerico - String - Opcional - Identificador numérico do registro no banco de dados.
	// caminhoMudou - String - Opcional - Se mudou o caminho, criar a referência 301 para redirect automático.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'editar':
			case 'adicionar':
				$hosts_servicos = banco_select_name
				(
					"*"
					,
					"hosts_servicos",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				); $hosts_servicos = $hosts_servicos[0];
				
				$hosts_paginas = banco_select_name
				(
					"*"
					,
					"hosts_paginas",
					"WHERE id_hosts_paginas='".$hosts_servicos['id_hosts_paginas']."'"
				); $hosts_paginas = $hosts_paginas[0];
				
				$hosts_servicos_lotes = banco_select(Array(
					'tabela' => 'hosts_servicos_lotes',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts_servicos='".$hosts_servicos['id_hosts_servicos']."'"
				));
				
				$hosts_servicos_variacoes = banco_select(Array(
					'tabela' => 'hosts_servicos_variacoes',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts_servicos='".$hosts_servicos['id_hosts_servicos']."'"
				));
				
				// ===== Remover ítens dos resultados de valores do servidor locais
				
				unset($hosts_servicos['id_hosts']);
				unset($hosts_servicos['id_usuarios']);
				unset($hosts_servicos['quantidade_carrinhos']);
				unset($hosts_servicos['quantidade_pedidos']);
				unset($hosts_servicos['quantidade_pedidos_pendentes']);
				
				unset($hosts_paginas['id_hosts']);
				unset($hosts_paginas['id_usuarios']);
				
				// ===== Processar lotes e variações.
				
				$lotesProcessados = Array();
				
				if($hosts_servicos_lotes)
				foreach($hosts_servicos_lotes as $hosts_servicos_lote){
					unset($hosts_servicos_lote['id_hosts']);
					unset($hosts_servicos_lote['id_usuarios']);
					
					$lotesProcessados[] = $hosts_servicos_lote;
				}
				
				$variacoesProcessadas = Array();
				
				if($hosts_servicos_variacoes)
				foreach($hosts_servicos_variacoes as $hosts_servicos_variacao){
					unset($hosts_servicos_variacao['id_hosts']);
					unset($hosts_servicos_variacao['id_usuarios']);
					unset($hosts_servicos_variacao['quantidade_carrinhos']);
					unset($hosts_servicos_variacao['quantidade_pedidos_pendentes']);
					unset($hosts_servicos_variacao['quantidade_pedidos']);
					
					$variacoesProcessadas[] = $hosts_servicos_variacao;
				}
				
				// ===== Incluir os resuldados no array dados.
			
				$dados['servicos'] = $hosts_servicos;
				$dados['paginas'] = $hosts_paginas;
				$dados['lotes'] = $lotesProcessados;
				$dados['variacoes'] = $variacoesProcessadas;
				
				if(isset($caminhoMudou)) $dados['caminhoMudou'] = $caminhoMudou;
			break;
			case 'status':
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'id_hosts_servicos',
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id='".$id."'"
						." AND status!='D'"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
				));
				$hosts_paginas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_paginas',
					'campos' => Array(
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id_hosts_paginas='".$hosts_servicos['id_hosts_paginas']."'"
				));
				
				unset($hosts_servicos['id_hosts_paginas']);
				
				$dados['servicos'] = $hosts_servicos;
				$dados['paginas'] = $hosts_paginas;
			break;
			case 'excluir':
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'id_hosts_servicos',
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_numerico."'"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
				));
				$hosts_paginas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_paginas',
					'campos' => Array(
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id_hosts_paginas='".$hosts_servicos['id_hosts_paginas']."'"
				));
				
				unset($hosts_servicos['id_hosts_paginas']);
				
				$dados['servicos'] = $hosts_servicos;
				$dados['paginas'] = $hosts_paginas;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'servicos',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_variaveis($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// modulo - String - Opcional - Identificador do módulo no banco de dados.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'editar':
				$resultados = banco_select_name
				(
					"*"
					,
					"hosts_variaveis",
					"WHERE id_hosts='".$host_verificacao['id_hosts']."'"
					.(isset($modulo) ? " AND modulo='".$modulo."'":'')
					." AND id_usuarios IS NULL"
				);
				
				// ===== Remover ítens dos resultados de valores do servidor locais
				
				if($resultados)
				foreach($resultados as $key => $res){
					unset($res['linguagem_codigo']);
					unset($res['id_hosts']);
					unset($res['id_usuarios']);
					
					$resultados2[] = $res;
				}
				
				// ===== Atualizar registros filtrados.
			
				if(isset($resultados2)){
					$resultados = $resultados2;
				}
				
				// ===== Enviar os registros.
			
				$dados['registros'] = $resultados;
			break;
			case 'paypal':
				$hosts_paypal = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_paypal',
					'campos' => Array(
						'app_installed',
						'app_active',
						'app_live',
						'paypal_plus_inactive',
					),
					'extra' => 
						"WHERE id_hosts='".$host_verificacao['id_hosts']."'"
				));
				
				// ===== Enviar os registros.
			
				$dados['registros'] = $hosts_paypal;
			break;
			case 'google-recaptcha':
				$hosts = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts',
					'campos' => Array(
						'google_recaptcha_ativo',
						'google_recaptcha_site',
					),
					'extra' => 
						"WHERE id_hosts='".$host_verificacao['id_hosts']."'"
				));
				
				// ===== Processamento dos valores locai para id remoto.
				
				$hosts_proc = Array(
					'ativo' => ($hosts['google_recaptcha_ativo'] ? $hosts['google_recaptcha_ativo'] : '0'),
					'chave-site' => ($hosts['google_recaptcha_site'] ? $hosts['google_recaptcha_site'] : ''),
				);
				
				// ===== Enviar os registros.
			
				$dados['registros'] = $hosts_proc;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'variaveis',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_variaveis_padroes($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// Se opcao == 'plugin'
	
	// plugin - String - Obrigatório - Plugin alvo.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'gestor':
				// ===== Baixar variáveis da loja.
			
				$resultados = banco_select(Array(
					'tabela' => 'variaveis',
					'campos' => Array(
						'modulo',
						'id',
						'valor',
						'tipo',
					),
					'extra' => 
						"WHERE modulo='loja'"
						." OR modulo='interface-hosts'"
						." OR modulo='formulario-hosts'"
				));
				
				// ===== Trocar módulo local por módulo remoto.
				
				if($resultados)
				foreach($resultados as $key => $res){
					switch($res['modulo']){
						case 'loja':
							$res['modulo'] = 'loja-configuracoes';
						break;
						case 'interface-hosts':
							$res['modulo'] = 'interface';
						break;
						case 'formulario-hosts':
							$res['modulo'] = 'formulario';
						break;
						default:
							
					}
					
					$resultados2[] = $res;
				}
				
				// ===== Atualizar registros filtrados.
			
				if(isset($resultados2)){
					$resultados = $resultados2;
				}
				
				// ===== Baixar variáveis de sistema.
			
				$resultadosNovo = banco_select(Array(
					'tabela' => 'variaveis',
					'campos' => Array(
						'modulo',
						'id',
						'valor',
						'tipo',
						'grupo',
					),
					'extra' => 
						"WHERE modulo='_sistema'"
						." AND (grupo='pedidos-status' OR grupo='pedidos-voucher-status')"
				));
				
				// ===== Trocar módulo local por módulo remoto.
				
				if($resultadosNovo)
				foreach($resultadosNovo as $key => $res){
					switch($res['grupo']){
						case 'pedidos-status':
							unset($res['grupo']);
							$res['modulo'] = 'pedidos-status';
						break;
						case 'pedidos-voucher-status':
							unset($res['grupo']);
							$res['modulo'] = 'pedidos-voucher-status';
						break;
						default:
							
					}
					
					$resultadosNovo2[] = $res;
				}
				
				// ===== Atualizar registros filtrados.
			
				if(isset($resultadosNovo2)){
					$resultados = array_merge($resultados,$resultadosNovo2);
				}
				
				// ===== Enviar os registros.
			
				$dados['registros'] = $resultados;
				$dados['padroes'] = true;
			break;
			case 'plugin':
				if(isset($plugin)){
					// ===== Pegar os dados de configuração do plugin.
					
					$pluginConfig = require($_GESTOR['plugins-path'].$plugin.'/'.$plugin.'.config.php');
					
					// ===== Pegar o módulo padrão de configurações.
					
					$modulo = $pluginConfig['moduloConfig'];
					
					// ===== Pegar variáveis do plugin no banco.
				
					$variaveis = banco_select(Array(
						'tabela' => 'variaveis',
						'campos' => Array(
							'id_variaveis',
							'id',
							'valor',
							'tipo',
							'grupo',
						),
						'extra' => 
							"WHERE linguagem_codigo='".$_GESTOR['linguagem-codigo']."'"
							." AND modulo='".$modulo."'"
							." AND grupo='padrao-host'"
							." ORDER BY id ASC"
					));
					
					$hosts_variaveis = banco_select(Array(
						'tabela' => 'hosts_variaveis',
						'campos' => Array(
							'id_hosts_variaveis',
							'id',
							'valor',
						),
						'extra' => 
							"WHERE linguagem_codigo='".$_GESTOR['linguagem-codigo']."'"
							." AND modulo='".$modulo."'"
							." AND grupo='padrao-host'"
							." AND id_hosts='".$_GESTOR['host-id']."'"
							." ORDER BY id ASC"
					));
					
					// ===== Varrer variáveis.
					
					$resultados = Array();
					
					if($variaveis){
						foreach($variaveis as $variavel){
							// ===== Padrão de id_hosts_variaveis não existente.
							
							$id_hosts_variaveis = null;
							
							// ===== Verificar se o valor padrão foi modificado por um valor específico do host e subistiuir o mesmo pelo valor específico.
							
							foreach($hosts_variaveis as $hosts_variavel){
								if(
									$variavel['id'] == $hosts_variavel['id']
								){
									$variavel['valor'] = $hosts_variavel['valor'];
									$id_hosts_variaveis = $hosts_variavel['id_hosts_variaveis'];
									break;
								}
							}
							
							// ===== Montar o array dos registros.
							
							$res = Array(
								'modulo' => $modulo,
								'id' => $variavel['id'],
								'valor' => $variavel['valor'],
								'tipo' => $variavel['tipo'],
								'grupo' => $variavel['grupo'],
							);
							
							if(isset($id_hosts_variaveis)){
								$res['id_hosts_variaveis'] = $id_hosts_variaveis;
							}
							
							$resultados[] = $res;
						}
					}
					
					// ===== Enviar os registros.
				
					$dados['registros'] = $resultados;
				} else {
					return api_cliente_retornar_erro(Array(
						'msg' => 'Params-mandatory: plugin',
					));
				}
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'variaveis',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_pedidos($params = false){
	/**********
		Descrição: api responsável pela manipulação dos dados de pedidos com a plataforma servidor.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id_hosts - Int - Obrigatório - Identificador do host.
	// pedidos - Array - Obrigatório - Conjunto com todos os pedidos que serão atualizados.
	
	// Se opcao == 'atualizar'
	
	// todos - Bool - Opcional - Se definido, atualizar todos os pedidos, senão o conjunto enviado através do array pedidos.
	
	// ===== 
	
	if(isset($opcao) && isset($id_hosts)){
		$dados = Array();
		
		switch($opcao){
			case 'atualizar':
				// ===== Campos para atualizar.
			
				$config = gestor_incluir_configuracao(Array(
					'id' => 'pedidos.config',
				));
				
				// ===== Caso enviado todos, baixar todos os pedidos do host, senão um grupo.
				
				if(isset($todos)){
					$hosts_pedidos = banco_select(Array(
						'tabela' => 'hosts_pedidos',
						'campos' => $config['atualizarCampos'],
						'extra' => 
							"WHERE id_hosts='".$id_hosts."'"
					));
					
					if($hosts_pedidos){
						foreach($hosts_pedidos as $hostPedido){
							$pedido = $hostPedido['id_hosts_pedidos'];
							
							$pedidos_proc[$pedido] = $hostPedido;
						}
						
						$dados['pedidos'] = $pedidos_proc;
					} else {
						return api_cliente_retornar_erro(Array(
							'msg' => 'Pedidos não encontrados'
						));
					}
				} else if(isset($pedidos)){
					foreach($pedidos as $pedido){
						$hosts_pedidos = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_pedidos',
							'campos' => $config['atualizarCampos'],
							'extra' => 
								"WHERE id_hosts_pedidos='".$pedido."'"
								." AND id_hosts='".$id_hosts."'"
						));
						
						$pedidos_proc[$pedido] = $hosts_pedidos;
					}
					
					$dados['pedidos'] = $pedidos_proc;
				} else {
					return api_cliente_retornar_erro(Array(
						'msg' => 'Pedidos não definidos'
					));
				}
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'pedidos',
			'id_hosts' => $id_hosts,
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_postagens($params = false){
	/**********
		Descrição: api responsável pela manipulação dos dados de postagens com a plataforma servidor.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	// id - String - Opcional - Identificador do registro no banco de dados.
	// id_numerico - String - Opcional - Identificador numérico do registro no banco de dados.
	// caminhoMudou - String - Opcional - Se mudou o caminho, criar a referência 301 para redirect automático.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'editar':
			case 'adicionar':
				$hosts_postagens = banco_select_name
				(
					"*"
					,
					"hosts_postagens",
					"WHERE id='".$id."'"
					." AND status!='D'"
					." AND id_hosts='".$host_verificacao['id_hosts']."'"
				); $hosts_postagens = $hosts_postagens[0];
				
				$hosts_paginas = banco_select_name
				(
					"*"
					,
					"hosts_paginas",
					"WHERE id_hosts_paginas='".$hosts_postagens['id_hosts_paginas']."'"
				); $hosts_paginas = $hosts_paginas[0];
				
				// ===== Remover ítens dos resultados de valores do servidor locais
				
				unset($hosts_postagens['id_hosts']);
				unset($hosts_postagens['id_usuarios']);
				
				unset($hosts_paginas['id_hosts']);
				unset($hosts_paginas['id_usuarios']);
				
				$dados['postagens'] = $hosts_postagens;
				$dados['paginas'] = $hosts_paginas;
				
				if(isset($caminhoMudou)) $dados['caminhoMudou'] = $caminhoMudou;
			break;
			case 'status':
				$hosts_postagens = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_postagens',
					'campos' => Array(
						'id_hosts_postagens',
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id='".$id."'"
						." AND status!='D'"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
				));
				$hosts_paginas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_paginas',
					'campos' => Array(
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id_hosts_paginas='".$hosts_postagens['id_hosts_paginas']."'"
				));
				
				unset($hosts_postagens['id_hosts']);
				unset($hosts_postagens['id_usuarios']);
				
				unset($hosts_paginas['id_hosts']);
				unset($hosts_paginas['id_usuarios']);
				
				$dados['postagens'] = $hosts_postagens;
				$dados['paginas'] = $hosts_paginas;
			break;
			case 'excluir':
				$hosts_postagens = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_postagens',
					'campos' => Array(
						'id_hosts_postagens',
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id_hosts_postagens='".$id_numerico."'"
						." AND id_hosts='".$host_verificacao['id_hosts']."'"
				));
				$hosts_paginas = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_paginas',
					'campos' => Array(
						'id_hosts_paginas',
						'status',
						'versao',
						'data_modificacao',
					),
					'extra' => 
						"WHERE id_hosts_paginas='".$hosts_postagens['id_hosts_paginas']."'"
				));
				
				unset($hosts_postagens['id_hosts']);
				unset($hosts_postagens['id_usuarios']);
				
				unset($hosts_paginas['id_hosts']);
				unset($hosts_paginas['id_usuarios']);
				
				$dados['postagens'] = $hosts_postagens;
				$dados['paginas'] = $hosts_paginas;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'postagens',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

function api_cliente_menus($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção almejada.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		$dados = Array();
		
		switch($opcao){
			case 'atualizar':
				$id_hosts = $host_verificacao['id_hosts'];
				
				// ===== Verificar se o host tem plugins habilitados.
				
				$hosts_plugins = banco_select(Array(
					'tabela' => 'hosts_plugins',
					'campos' => Array(
						'plugin',
						'habilitado',
					),
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
				));
				
				// ===== Pegar os menusPadroes das configurações dos menus.
				
				$config = gestor_incluir_configuracao(Array(
					'id' => 'menus.config',
				));
				
				$menusPadroes = $config['menusPadroes'];
				
				// ===== Menus itens de cada menu padrão.
				
				$menusItens = Array();
				$menusVersao = Array();
				
				if($menusPadroes)
				foreach($menusPadroes as $menu_id => $menu){
					$menusItens[$menu_id] = $menu['itens'];
					$menusVersao[$menu_id] = (int)$menu['versao'];
				}
			
				// ===== Pegar os menusPadroes de cada plugin.
				
				$debugArr = Array();
				
				if($hosts_plugins)
				foreach($hosts_plugins as $hosts_plugin){
					if($hosts_plugin['habilitado']){
						// ===== ID do plugin.
						
						$pluginID = $hosts_plugin['plugin'];
						
						// ===== Pegar os dados de configuração do plugin.
						
						$pluginConfig = require($_GESTOR['plugins-path'].$pluginID.'/'.$pluginID.'.config.php');
						
						// ===== Incluir os itens nos seus menus.
						
						$debugArr[] = $pluginConfig;
						
						if($pluginConfig['menusPadroes'])
						foreach($pluginConfig['menusPadroes'] as $menu_id => $menu){
							$itens = $menu['itens'];
							$versao = (int)$menu['versao'];
							$menusItens[$menu_id] = $itens + $menusItens[$menu_id];
							
							$debugArr[] = 'Entrou';
							
							if($menusVersao[$menu_id] < $versao){
								$menusVersao[$menu_id] = $versao;
							}
						}
					}
				}
				
				return api_cliente_retornar_erro(Array(
					'msg' => print_r($debugArr,true),
				));
				
				// ===== Verificar os menus itens no banco.
			
				$hosts_menus_itens = banco_select(Array(
					'tabela' => 'hosts_menus_itens',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
				));
				
				// ===== Varrer todos os menus itens e alterar o banco caso necessário.
				
				if($menusItens)
				foreach($menusItens as $menu_id => $itens){
					if($itens)
					foreach($itens as $id => $item){
						$found = false;
						
						if($hosts_menus_itens)
						foreach($hosts_menus_itens as $key => $host_menu_item){
							if(
								$host_menu_item['menu_id'] == $menu_id &&
								$host_menu_item['id'] == $id
							){
								$hosts_menus_itens[$key]['verificado'] = true;
								$found = true;
								break;
							}
						}
						
						// ===== Incluir ou atualizar o banco de dados.
						
						if($found){
							if($menusVersao[$menu_id] > (int)$host_menu_item['versao']){
								banco_update_campo('label',$item['label']);
								banco_update_campo('tipo',$item['tipo']);
								banco_update_campo('url',$item['url']);
								banco_update_campo('versao',$menusVersao[$menu_id]);
								
								banco_update_executar('hosts_menus_itens',"WHERE id_hosts='".$id_hosts."' AND menu_id='".$menu_id."' AND id='".$id."'");
							}
						} else {
							banco_insert_name_campo('id_hosts',$id_hosts);
							banco_insert_name_campo('menu_id',$menu_id);
							banco_insert_name_campo('id',$id);
							banco_insert_name_campo('label',$item['label']);
							banco_insert_name_campo('tipo',$item['tipo']);
							banco_insert_name_campo('url',$item['url']);
							banco_insert_name_campo('versao',$menusVersao[$menu_id]);
							
							if(isset($item['inativo'])){ banco_insert_name_campo('inativo','1',true); }
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"hosts_menus_itens"
							);
						}
					}
				}
				
				// ===== Excluir do banco itens removidos do padrão.
				
				if($hosts_menus_itens)
				foreach($hosts_menus_itens as $host_menu_item){
					if(!isset($host_menu_item['verificado'])){
						banco_delete
						(
							"hosts_menus_itens",
							"WHERE menu_id='".$host_menu_item['menu_id']."'"
							." AND id='".$host_menu_item['id']."'"
							." AND id_hosts='".$id_hosts."'"
						);
					}
				}
				
				// ===== Pegar os menus itens no banco.
			
				$hosts_menus_itens = banco_select(Array(
					'tabela' => 'hosts_menus_itens',
					'campos' => '*',
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
				));
				
				// ===== Trocar o label pelo valor textual.
				
				$variaveisMenu = gestor_variaveis(Array('modulo' => 'menus','id' => 'identificador','conjunto' => true));
				
				if($hosts_menus_itens)
				foreach($hosts_menus_itens as $key => $host_menu_item){
					unset($hosts_menus_itens[$key]['id_hosts']);
					$hosts_menus_itens[$key]['label'] = $variaveisMenu[$host_menu_item['label']];
				}
				
				// ===== Enviar os registros.
			
				$dados['registros'] = $hosts_menus_itens;
			break;
		}
		
		// ===== Acessar a interface no cliente e retornar objeto do retorno.
		
		$retorno = api_cliente_interface(Array(
			'interface' => 'menus',
			'id_hosts' => $host_verificacao['id_hosts'],
			'opcao' => $opcao,
			'dados' => $dados,
		));
		
		return $retorno;
	}
}

// ===== Funções auxiliares.

function api_cliente_retornar_erro($params = false){
	/**********
		Descrição: Retornar erro com mensagem caso exista
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// msg - String - Obrigatório - Mensagem de erro.
	
	// ===== 
	
	if(isset($msg)){
		$retorno['error-msg'] = $msg;
		$retorno['error'] = true;
		$retorno['completed'] = false;
		
		return $retorno;
	}
}

function api_cliente_gerar_jwt($params = false){
	$cryptMaxCharsValue = 245; // There are char limitations on openssl_private_encrypt() and in the url below are explained how define this value based on openssl key format: https://www.php.net/manual/en/function.openssl-private-encrypt.php#119810
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// host - String - Obrigatório - Host de acesso do JWT.
	// expiration - Int - Obrigatório - Expiração do JWT.
	// pubID - String - Obrigatório - ID público do token para referência.
	// chavePublica - String - Obrigatório - Chave pública para assinar o JWT.
	
	// ===== 
	
	if(isset($host) && isset($expiration) && isset($pubID) && isset($chavePublica)){
		// ===== Header

		$header = [
		   'alg' => 'RSA',
		   'typ' => 'JWT'
		];

		$header = json_encode($header);
		$header = base64_encode($header);

		// ===== Payload

		$payload = [
			'iss' => $host, // The issuer of the token
			'exp' => $expiration, // This will define the expiration in NumericDate value. The expiration MUST be after the current date/time.
			'sub' => $pubID, // ID público do totken
		];

		$payload = json_encode($payload);
		$payload = base64_encode($payload);

		// ===== Unir header com payload para gerar assinatura

		$rawDataSource = $header.".".$payload;
		
		// ===== Assinar usando RSA SSL
		
		$resPublicKey = openssl_get_publickey($chavePublica);

		$partialData = '';
		$encodedData = '';
		$split = str_split($rawDataSource , $cryptMaxCharsValue);
		foreach($split as $part){
			openssl_public_encrypt($part, $partialData, $resPublicKey);
			$encodedData .= (strlen($encodedData) > 0 ? '.':'') . base64_encode($partialData);
		}
		
		$encodedData = base64_encode($encodedData);
		
		$signature = $encodedData;
		
		// ===== Finalizar e devolver o JWT token

		$JWTToken = $header.".".$payload.".".$signature;
		
		return $JWTToken;
	} else {
		return false;
	}
}

function api_cliente_validar_jwt($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - Token JWT de verificação.
	// chavePublica - String - Obrigatório - Chave pública para conferir a assinatura do token.
	
	// ===== 
	
	if(isset($token) && isset($chavePublica)){
		// ===== Quebra o token em header, payload e signature
		
		$part = explode(".",$token);
		
		if(gettype($part) != 'array'){
			return false;
		}
		
		$header = $part[0];
		$payload = $part[1];
		$signature = $part[2];

		$encodedData = $signature;
		
		// ===== Abrir chave privada com a senha
		
		$resPublicKey = openssl_get_publickey($chavePublica);
		
		// ===== Decode base64 to reaveal dots (Dots are used in JWT syntaxe)

		$encodedData = base64_decode($encodedData);

		// ===== Decrypt data in parts if necessary. Using dots as split separator.

		$rawEncodedData = $encodedData;

		$countCrypt = 0;
		$partialDecodedData = '';
		$decodedData = '';
		$split2 = explode('.',$rawEncodedData);
		foreach($split2 as $part2){
			$part2 = base64_decode($part2);
			
			openssl_public_decrypt($part2, $partialDecodedData, $resPublicKey);
			$decodedData .= $partialDecodedData;
		}

		// ===== Validate JWT

		if($header.".".$payload === $decodedData){
			$payload = base64_decode($payload);
			$payload = json_decode($payload,true);
			
			// ===== Verifica se as variáveis existem, senão foi formatado errado e não deve aceitar.
			
			if(!isset($payload['exp']) || !isset($payload['sub'])){
				return false;
			}
			
			$expiracao_ok = false;
			
			// ===== Se o tempo de expiração do token for menor que o tempo agora, é porque este token está vencido.
			
			if((int)$payload['exp'] > time()){
				$expiracao_ok = true;
			}
			
			if($expiracao_ok){
				// Se tudo estiver válido, retorna o pubID do token.
				
				return $payload['sub'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// ===== Funções principais.

function api_cliente_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	global $_CRON;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host dentro do sistema.
	
	// ===== 
	
	if(isset($id_hosts)){
		// ===== Definir variáveis para gerar o JWT
		
		$expiration = time() + $_GESTOR['platform-lifetime'];
		
		// ===== Pegar a chave pública do host
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'chave_publica',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		if($hosts){
			$chavePublica = $hosts[0]['chave_publica'];
			
			// ===== Deletar todos os tokens que atingiram o tempo de expiração.
			
			banco_delete
			(
				"plataforma_tokens",
				"WHERE expiration < ".time()
			);
			
			// ===== Gerar ou pegar ID do Token.
			
			$plataforma_tokens = banco_select(Array(
				'unico' => true,
				'tabela' => 'plataforma_tokens',
				'campos' => Array(
					'pubID',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND remoto IS NULL"
			));
			
			if($plataforma_tokens){
				$tokenPubId = $plataforma_tokens['pubID'];
			} else {
				$tokenPubId = md5(uniqid(rand(), true));
				
				$pubIDValidation = hash_hmac($_GESTOR['platform-hash-algo'], $tokenPubId, $_GESTOR['platform-hash-password']);
				
				// ====== Salvar token no banco
				
				$campos = null; $campo_sem_aspas_simples = null;
				
				$campo_nome = "id_hosts"; $campo_valor = $id_hosts; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "pubID"; $campo_valor = $tokenPubId; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				banco_insert_name
				(
					$campos,
					"plataforma_tokens"
				);
			}
			
			// ===== Gerar o token JWT
			
			$token = api_cliente_gerar_jwt(Array(
				'host' => (isset($_CRON['SERVER_NAME']) ? $_CRON['SERVER_NAME'] : $_SERVER['SERVER_NAME']),
				'expiration' => $expiration,
				'chavePublica' => $chavePublica,
				'pubID' => $tokenPubId,
			));
			
			return $token;
		}
	}
	
	return false;
}

function api_cliente_interface($params = false){
	global $_GESTOR;
	global $_CRON;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// interface - String - Obrigatório - Identificador da interface que se deseja acessar no cliente.
	// id_hosts - Int - Obrigatório - Identificador do host dentro do sistema.
	// opcao - String - Opcional - Opção para acessar na interface do cliente.
	// dados - Array - Opcional - Dados necessários para enviar para o cliente.
	// arquivos - Array - Opcional - Arquivos necessários para enviar para o cliente.
		// caminho - String - Obrigatório - Caminho do arquivo que será enviado para o cliente.
		// tipo - String - Obrigatório - Mime-type do arquivo que será enviado para o cliente.
		// post - String - Obrigatório - Postname do arquivo no campo de upload que será enviado para o cliente.
	
	// ===== 
	
	// ===== Definição do retorno
	
	$retorno['error-msg'] = '';
	$retorno['error'] = false;
	$retorno['completed'] = false;
	
	if(isset($interface) && isset($id_hosts)){
		// ===== Gerar Token de autorização para conferência pelo cliente se a conexão provêm de fato do servidor e não de outro que se passa pelo servidor.
		
		$token = api_cliente_gerar_token_autorizacao(Array(
			'id_hosts' => $id_hosts,
		));
		
		// ===== Buscar no banco de dados os dados do host necessários.
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'dominio',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		if($hosts){
			$protocolo = 'https://';
			$dominio = $protocolo . $hosts[0]['dominio'];
			
			// ===== Conectar na plataforma do cliente na interface requisitada
			
			$url = $dominio . '/_plataforma/' . $interface;
			
			// ===== Montar o campo 'data' que será enviado ao cliente.
			
			$data = false;
			
			$data['token'] = $token;
			$data['plataforma-id'] = (isset($_CRON['PLATAFORMA_ID']) ? $_CRON['PLATAFORMA_ID'] : $_GESTOR['plataforma-id']);
			
			if(isset($opcao)) $data['opcao'] = $opcao;
			if(isset($dados)) $data['dados'] = json_encode($dados);
			
			// ===== Caso exista arquivos, montar o campo upload com os mesmos
			
			if(isset($arquivos)){
				$numArquivos = 0;
				
				foreach($arquivos as $arquivo){
					if(isset($arquivo['caminho']) && isset($arquivo['tipo']) && isset($arquivo['post'])){
						$data['file['.$numArquivos.']'] = new cURLFile($arquivo['caminho'], $arquivo['tipo'], $arquivo['post']);
						$numArquivos++;
					}
				}
			}
			
			// ===== Montar o cURL da conexão com todas as opções
			
			$curl = curl_init($url);

			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			$json = curl_exec($curl);
			
			curl_close($curl);
			
			$plataformaRetorno = json_decode($json,true);
			
			// ===== Tratar os erros de retorno da plataforma caso haja ou então devolver o retorno para a requisição com o status ok e/ou os dados.
			
			if(!$plataformaRetorno){
				$retorno['error-msg'] = '[no-json] '.$json; $retorno['error'] = true;
			} else if(isset($plataformaRetorno['error'])){
				$retorno['error-msg'] = '[error] '.$plataformaRetorno['error'].' '.$plataformaRetorno['error_msg']; $retorno['error'] = true;
			} else if($plataformaRetorno['status'] != 'OK'){
				$retorno['error-msg'] = '[not-OK] '.print_r($plataformaRetorno,true); $retorno['error'] = true;
			} else {
				if(isset($plataformaRetorno['data'])) $retorno['data'] = $plataformaRetorno['data'];
				$retorno['completed'] = true;
			}
			
			return $retorno;
		} else {
			// ===== Caso não exista o host no banco de dados retornar erro.
			
			$retorno['error-msg'] = 'id_hosts not found';
			$retorno['error'] = true;
			
			return $retorno;
		}
	} else {
		// ===== Caso não seja definida os parâmetros obrigatórios, retornar erro.
		
		$retorno['error-msg'] = 'interface, id_hosts is mandatory';
		$retorno['error'] = true;
		
		return $retorno;
	}
}

?>