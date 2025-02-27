<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'carrinho';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.25',
);

// ===== Funções Auxiliares

function carrinho_operacoes($params = false){
	/**********
		Descrição: operações de modificação do carrinho.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção de modificação do carrinho.
	// ajax - Bool - Opcional - Comunicação AJAX ativada.
	
	// opcao == 'adicionar' || 'diminuir'
		// id || id_alt - Int - Obrigatório - Identificador do serviço no banco de dados.
		// quantidade - Int - Opcional - Quantidade de serviço a ser modificado no carrinho.
		// variacao_id - Int - Opcional - Identificador da variação do serviço no banco de dados.
		
	// opcao == 'excluir'
		// id || id_alt - Int - Obrigatório - Identificador do serviço no banco de dados.
		// variacao_id - Int - Opcional - Identificador da variação do serviço no banco de dados.
		
	// ===== 
	
	if(isset($ajax)){
		$ajax = true;
	} else {
		$ajax = false;
	}
	
	switch($opcao){
		case 'adicionar':
		case 'diminuir':
			if((isset($id) || isset($id_alt))){
				// ===== Incluir bibliotecas.
				
				gestor_incluir_biblioteca('interface');
				
				// ===== Baixar dados do serviço do banco de dados.
				
				if(isset($id)){
					$where = "WHERE id_servicos='".$id."'";
				} else if(isset($id_alt)){
					$where = "WHERE id_hosts_servicos='".$id_alt."'";
				}
				
				$servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'servicos',
					'campos' => Array(
						'id_hosts_servicos',
						'quantidade',
						'status',
					),
					'extra' => 
						$where
				));
				
				if($servicos){
					// ===== Caso o serviço estiver ativo 'A' continua.
					
					if($servicos['status'] == 'A'){
						// ===== Verificar se é uma variação de um lote.
						
						if(isset($variacao_id)){
							$variacao = true;
							
							// ===== Baixar do banco de dados os dados da variação.
							
							$servicos_variacoes = banco_select(Array(
								'unico' => true,
								'tabela' => 'servicos_variacoes',
								'campos' => Array(
									'id_hosts_servicos_variacoes',
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_servicos='".$servicos['id_hosts_servicos']."'"
									." AND id_hosts_servicos_variacoes='".$variacao_id."'"
							));
						}
						
						// ===== Caso tenha enviado a quantidade necessária, senão atribuir o valor 1.
						
						$quantidade = (isset($quantidade) ? (int)$quantidade : 1);
						
						// ===== Verificar quantidade disponível em estoque.
						
						if($opcao == 'adicionar'){
							// ===== Tratar a quantidade baseado na variação.
							
							if($variacao){
								$quantidadeEstoque = (int)$servicos_variacoes['quantidade'];
							} else {
								$quantidadeEstoque = (int)$servicos['quantidade'];
							}
							
							if($quantidadeEstoque < $quantidade){
								$msg = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-quantidade-indisponivel'));
								
								if($ajax){
									return Array(
										'status' => 'QUANTIDADE_INDISPONIVEL',
										'msg' => $msg,
									);
								} else {
									interface_alerta(Array(
										'redirect' => true,
										'msg' => $msg
									));
									
									gestor_reload_url();
								}
							}
						}
						
						// ===== API-Servidor adicionar ou diminuir no carrinho.
						
						gestor_incluir_biblioteca('api-servidor');
						
						$retorno = api_servidor_carrinho(Array(
							'opcao' => ($opcao == 'adicionar' ? 'adicionar' : 'diminuir' ),
							'id_hosts_servicos' => $servicos['id_hosts_servicos'],
							'quantidade' => $quantidade,
							'variacao_id' => ($variacao ? $variacao_id : null ),
						));
						
						if(!$retorno['completed']){
							$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
							
							$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
							
							if($ajax){
								return Array(
									'status' => 'API_ERROR',
									'msg' => $alerta,
								);
							} else {
								interface_alerta(Array(
									'redirect' => true,
									'msg' => $alerta
								));
							}
						} else {
							// ===== Dados de retorno.
							
							$dados = Array();
							if(isset($retorno['data'])){
								$dados = $retorno['data'];
							}
							
							// ===== Criar ou atualizar o carrinho localmente.
							
							if(isset($dados['carrinho']['id_hosts_carrinho'])){
								banco_insert_update(Array(
									'dados' => $dados['carrinho'],
									'tabela' => Array(
										'nome' => 'carrinho',
										'id' => 'id_hosts_carrinho',
									),
								));
							}
							
							// ===== Verificar se é variação ou não.
							
							if(isset($dados['variacao'])){
								
								// ===== Criar ou atualizar o carrinho serviço variação localmente.
								
								if(isset($dados['carrinho_servico_variacoes']['id_hosts_carrinho_servico_variacoes'])){
									banco_insert_update(Array(
										'dados' => $dados['carrinho_servico_variacoes'],
										'tabela' => Array(
											'nome' => 'carrinho_servico_variacoes',
											'id' => 'id_hosts_carrinho_servico_variacoes',
										),
										'dadosTipo' => Array(
											'quantidade' => 'int',
											'preco' => 'int',
											'gratuito' => 'bool',
										),
									));
								}
								
								// ===== Atualizar estoque da variação do serviço.
								
								banco_update
								(
									"quantidade=".(isset($dados['servico-variacao-quantidade']) ? $dados['servico-variacao-quantidade'] : '0'),
									"servicos_variacoes",
									"WHERE id_hosts_servicos='".$servicos['id_hosts_servicos']."'"
									." AND id_hosts_servicos_variacoes='".$variacao_id."'"
								);
							} else {
								
								// ===== Criar ou atualizar o carrinho serviço localmente.
								
								if(isset($dados['carrinho_servicos']['id_hosts_carrinho_servicos'])){
									banco_insert_update(Array(
										'dados' => $dados['carrinho_servicos'],
										'tabela' => Array(
											'nome' => 'carrinho_servicos',
											'id' => 'id_hosts_carrinho_servicos',
										),
										'dadosTipo' => Array(
											'quantidade' => 'int',
											'preco' => 'int',
											'gratuito' => 'bool',
										),
									));
								}
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade=".(isset($dados['servico-quantidade']) ? $dados['servico-quantidade'] : '0'),
									"servicos",
									$where
								);
							}
							
							// ===== Retornar ok se for AJAX.
							
							if($ajax){
								return Array(
									'status' => 'OK',
								);
							}
						}
					} else {
						if($ajax){
							$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-servico-indisponivel'));
							
							return Array(
								'status' => 'SERVICO_INDISPONIVEL',
								'msg' => $alerta,
							);
						} else {
							interface_alerta(Array(
								'redirect' => true,
								'msg' => $alerta
							));
						}
					}
				} else {
					if($ajax){
						$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-servico-indisponivel'));
						
						return Array(
							'status' => 'SERVICO_INDISPONIVEL',
							'msg' => $alerta,
						);
					} else {
						interface_alerta(Array(
							'redirect' => true,
							'msg' => $alerta
						));
					}
				}
			}
		break;
		case 'excluir':
			if((isset($id) || isset($id_alt))){
				// ===== Incluir bibliotecas.
				
				gestor_incluir_biblioteca('interface');
				
				// ===== Baixar dados do serviço do banco de dados.
				
				if(isset($id)){
					$where = "WHERE id_servicos='".$id."'";
				} else if(isset($id_alt)){
					$where = "WHERE id_hosts_servicos='".$id_alt."'";
				}
				
				$servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'servicos',
					'campos' => Array(
						'id_hosts_servicos',
						'quantidade',
						'status',
					),
					'extra' => 
						$where
				));
				
				if($servicos){
					// ===== Caso o serviço estiver ativo 'A' continua.
					
					if($servicos['status'] == 'A'){
						// ===== API-Servidor adicionar no carrinho.
						
						gestor_incluir_biblioteca('api-servidor');
						
						$retorno = api_servidor_carrinho(Array(
							'opcao' => 'excluir',
							'id_hosts_servicos' => $servicos['id_hosts_servicos'],
							'variacao_id' => (isset($variacao_id) ? $variacao_id : null ),
						));
						
						if(!$retorno['completed']){
							$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
							
							$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
							
							if($ajax){
								return Array(
									'status' => 'API_ERROR',
									'msg' => $alerta,
								);
							} else {
								interface_alerta(Array(
									'redirect' => true,
									'msg' => $alerta
								));
							}
						} else {
							// ===== Dados de retorno.
							
							$dados = Array();
							if(isset($retorno['data'])){
								$dados = $retorno['data'];
							}
							
							// ===== Carrinho dados.
							
							$carrinho = banco_select(Array(
								'unico' => true,
								'tabela' => 'carrinho',
								'campos' => Array(
									'id_hosts_carrinho',
								),
								'extra' => 
									"WHERE sessao_id='".$_GESTOR['session-id']."'"
							));
							
							if(isset($variacao_id)){
								// ===== Excluir a variação do serviço do carrinho local.
								
								banco_delete
								(
									"carrinho_servico_variacoes",
									"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
									." AND id_hosts_servicos='".$servicos['id_hosts_servicos']."'"
									." AND id_hosts_servicos_variacoes='".$variacao_id."'"
								);
								
								// ===== Atualizar estoque da variação do serviço.
								
								banco_update
								(
									"quantidade=".(isset($dados['servico-variacao-quantidade']) ? $dados['servico-variacao-quantidade'] : '0'),
									"servicos_variacoes",
									"WHERE id_hosts_servicos='".$servicos['id_hosts_servicos']."'"
									." AND id_hosts_servicos_variacoes='".$variacao_id."'"
								);
							} else {
								// ===== Excluir o serviço do carrinho local.
								
								banco_delete
								(
									"carrinho_servicos",
									"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
									." AND id_hosts_servicos='".$servicos['id_hosts_servicos']."'"
								);
								
								// ===== Atualizar estoque do serviço.
								
								banco_update
								(
									"quantidade=".(isset($dados['servico-quantidade']) ? $dados['servico-quantidade'] : '0'),
									"servicos",
									$where
								);
							}
							
							// ===== Atualizar data modificação do carrinho.
							
							banco_update
							(
								"data_modificacao=NOW()",
								"carrinho",
								"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
							);
							
							// ===== Retornar ok se for AJAX.
							
							if($ajax){
								return Array(
									'status' => 'OK',
								);
							}
						}
					} else {
						if($ajax){
							$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-servico-indisponivel'));
							
							return Array(
								'status' => 'SERVICO_INDISPONIVEL',
								'msg' => $alerta,
							);
						} else {
							interface_alerta(Array(
								'redirect' => true,
								'msg' => $alerta
							));
						}
					}
				} else {
					if($ajax){
						$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-servico-indisponivel'));
						
						return Array(
							'status' => 'SERVICO_INDISPONIVEL',
							'msg' => $alerta,
						);
					} else {
						interface_alerta(Array(
							'redirect' => true,
							'msg' => $alerta
						));
					}
				}
			}
		break;
	}
	
	if($ajax){
		return Array(
			'status' => 'ERROR_UNKNOWN',
		);
	}
}

function carrinho_abandonados(){
	global $_GESTOR;
	
	$carrinhos = banco_select(Array(
		'tabela' => 'carrinho',
		'campos' => Array(
			'id_hosts_carrinho',
		),
		'extra' => 
			"WHERE UNIX_TIMESTAMP(`data_modificacao`) + ".$_GESTOR['session-lifetime']." < ".time()
	));
	
	if($carrinhos)
	foreach($carrinhos as $carrinho){
		// ===== Excluir variações dos serviços dos carrinhos abandonados.
		
		banco_delete
		(
			"carrinho_servico_variacoes",
			"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		);
		
		// ===== Excluir serviços dos carrinhos abandonados.
		
		banco_delete
		(
			"carrinho_servicos",
			"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		);
		
		// ===== Excluir carrinhos abandonados.
		
		banco_delete
		(
			"carrinho",
			"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		);
	}
}

// ===== Funções Principais

function carrinho_padrao(){
	global $_GESTOR;
	
	// ===== Adicionar ou diminuir uma quantidade de um serviço.
	
	if(isset($_REQUEST['adicionar']) || isset($_REQUEST['diminuir'])){
		// ===== Verificar se foi enviado o id do serviço.
		
		if(isset($_REQUEST['id']) || isset($_REQUEST['id_alt'])){
			carrinho_operacoes(Array(
				'opcao' => (isset($_REQUEST['adicionar']) ? 'adicionar' : 'diminuir'),
				'id' => (isset($_REQUEST['id']) ? banco_escape_field($_REQUEST['id']) : null),
				'id_alt' => (isset($_REQUEST['id_alt']) ? banco_escape_field($_REQUEST['id_alt']) : null),
				'quantidade' => (isset($_REQUEST['quantidade']) ? $_REQUEST['quantidade'] : null),
			));
		}
		
		// ===== Redirecionar para a página carrinho.
		
		gestor_reload_url();
	}
	
	// ===== Excluir um serviço.
	
	if(isset($_REQUEST['excluir'])){
		// ===== Verificar se foi enviado o id do serviço.
		
		if(isset($_REQUEST['id']) || isset($_REQUEST['id_alt'])){
			carrinho_operacoes(Array(
				'opcao' => 'excluir',
				'id' => (isset($_REQUEST['id']) ? banco_escape_field($_REQUEST['id']) : null),
				'id_alt' => (isset($_REQUEST['id_alt']) ? banco_escape_field($_REQUEST['id_alt']) : null),
			));
		}
		
		// ===== Redirecionar para a página carrinho.
		
		gestor_reload_url();
	}
	
	// ===== Carrinhos abandonados limpeza.
	
	carrinho_abandonados();
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('interface');
	
	// ===== Valores principais.
	
	$subtotal = 0;
	$descontos = 0;
	$total = 0;
	$quantidadeTotal = 0;
	$JScarrinho = Array();
	
	// ===== Células.
	
	$cel_nome = 'vazio'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	
	$cel_nome = 'cel-servicos'; $cel[$cel_nome] = pagina_celula($cel_nome);
	$cel_nome = 'cel-resumo'; $cel[$cel_nome] = pagina_celula($cel_nome);
	
	// ===== Carrinho no banco de dados.
	
	$carrinho = banco_select(Array(
		'unico' => true,
		'tabela' => 'carrinho',
		'campos' => Array(
			'id_hosts_carrinho',
		),
		'extra' => 
			"WHERE sessao_id='".$_GESTOR['session-id']."'"
	));
	
	// ===== Montar carrinho.
	
	if(isset($carrinho)){
		$carrinho_servicos = banco_select(Array(
			'tabela' => 'carrinho_servicos',
			'campos' => Array(
				'id_hosts_servicos',
				'id_hosts_arquivos_Imagem',
				'nome',
				'preco',
				'quantidade',
			),
			'extra' => 
				"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		));
		
		$carrinho_servico_variacoes = banco_select(Array(
			'tabela' => 'carrinho_servico_variacoes',
			'campos' => Array(
				'id_hosts_servicos',
				'id_hosts_servicos_variacoes',
				'id_hosts_arquivos_Imagem',
				'nome_servico',
				'nome_lote',
				'nome_variacao',
				'preco',
				'quantidade',
			),
			'extra' => 
				"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		));
		
		if($carrinho_servicos || $carrinho_servico_variacoes){
			// ===== Caso exista serviço nos carrinhos, montar células.
			
			if($carrinho_servicos)
			foreach($carrinho_servicos as $carServ){
				// ===== Buscar a imagem mini.
				
				$caminho_mini = '';
				
				$id_hosts_arquivos = $carServ['id_hosts_arquivos_Imagem'];
				
				if(isset($id_hosts_arquivos)){
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'caminho_mini',
						)),
						"arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
					);
					
					if($resultado){
						if(existe($resultado[0]['caminho_mini'])){
							$caminho_mini = $resultado[0]['caminho_mini'];
						}
					}
				}
				
				// ===== Imagem Mini ou Imagem Referência do serviço.
				
				if(existe($caminho_mini)){
					$imgSrc = $caminho_mini;
				} else {
					$imgSrc = 'images/imagem-padrao.png';
				}
				
				$carServ['imagem'] = '/' . $imgSrc;
				
				// ===== Calcular subtotal.
				
				$quantidadeTotal += (int)$carServ['quantidade'];
				$carServ['subtotal'] = (float)$carServ['preco'] * (int)$carServ['quantidade'];
				$subtotal += $carServ['subtotal'];
				
				// ===== Montar a célula do serviço.
				
				$cel_nome = 'cel-servicos';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-id",$carServ['id_hosts_servicos']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,' data-id-varicao="@[[varicao-id]]@"','',true);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$carServ['imagem']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['nome']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-preco",formato_dado_para('float-para-texto',$carServ['preco']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-quantidade",formato_dado_para('int-para-texto',$carServ['quantidade']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
				
				// ===== Montar a célula do resumo.
				
				$cel_nome = 'cel-resumo';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['quantidade'].'x '.$carServ['nome']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
			}
			
			// ===== Caso exista variações do serviço nos carrinhos, montar células.
			
			if($carrinho_servico_variacoes)
			foreach($carrinho_servico_variacoes as $carServ){
				// ===== Buscar a imagem mini.
				
				$caminho_mini = '';
				
				$id_hosts_arquivos = $carServ['id_hosts_arquivos_Imagem'];
				
				if(isset($id_hosts_arquivos)){
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'caminho_mini',
						)),
						"arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
					);
					
					if($resultado){
						if(existe($resultado[0]['caminho_mini'])){
							$caminho_mini = $resultado[0]['caminho_mini'];
						}
					}
				}
				
				// ===== Imagem Mini ou Imagem Referência do serviço.
				
				if(existe($caminho_mini)){
					$imgSrc = $caminho_mini;
				} else {
					$imgSrc = 'images/imagem-padrao.png';
				}
				
				$carServ['imagem'] = '/' . $imgSrc;
				
				// ===== Calcular subtotal.
				
				$quantidadeTotal += (int)$carServ['quantidade'];
				$carServ['subtotal'] = (float)$carServ['preco'] * (int)$carServ['quantidade'];
				$subtotal += $carServ['subtotal'];
				
				// ===== Montar a célula do serviço.
				
				$cel_nome = 'cel-servicos';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-id",$carServ['id_hosts_servicos']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"varicao-id",$carServ['id_hosts_servicos_variacoes']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$carServ['imagem']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['nome_servico'].'<div class="sub header">'.$carServ['nome_lote'].' - '.$carServ['nome_variacao'].'</div>');
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-preco",formato_dado_para('float-para-texto',$carServ['preco']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-quantidade",formato_dado_para('int-para-texto',$carServ['quantidade']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
				
				// ===== Montar a célula do resumo.
				
				$cel_nome = 'cel-resumo';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['quantidade'].'x '.$carServ['nome_servico'].'<div class="sub header">'.$carServ['nome_lote'].' - '.$carServ['nome_variacao'].'</div>');
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
			}
			
			// ===== Remover marcadores.
			
			$cel_nome = 'cel-servicos'; pagina_celula_incluir($cel_nome,'');
			$cel_nome = 'cel-resumo'; pagina_celula_incluir($cel_nome,'');
			
			pagina_trocar_variavel_valor('<!-- cel-servicos -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-servicos < -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-servicos > -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-resumo < -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-resumo > -->','',true);
		} else {
			pagina_trocar_variavel_valor('<!-- cel-servicos -->',$cel['vazio'],true);
		}
	} else {
		pagina_trocar_variavel_valor('<!-- cel-servicos -->',$cel['vazio'],true);
		$JScarrinho['vazio'] = true;
	}
	
	// ===== Finalizar resumo.
	
	$total = $subtotal - $descontos;
	
	$subtotalStr = formato_dado_para('float-para-texto',$subtotal);
	$descontosStr = formato_dado_para('float-para-texto',$descontos);
	$totalStr = formato_dado_para('float-para-texto',$total);
	
	pagina_trocar_variavel_valor('subtotal',$subtotalStr);
	pagina_trocar_variavel_valor('descontos',$descontosStr);
	pagina_trocar_variavel_valor('total',$totalStr);
	
	// ===== Caso a quantidadeTotal seja zerado, não permitir prosseguir.
	
	if($quantidadeTotal == 0){
		$JScarrinho['vazio'] = true;
	}
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step',$cel['step']);
	layout_trocar_variavel_valor('layout#step-mobile',$cel['step-mobile']);
	
	// ===== URL Continuar Comprando.
	
	$JScarrinho['botaoProximo'] = '/identificacao/';
	
	// ===== URL Continuar Comprando.
	
	$continuarComprando = banco_select(Array(
		'unico' => true,
		'tabela' => 'variaveis',
		'campos' => Array(
			'valor',
		),
		'extra' => 
			"WHERE modulo='loja-configuracoes'"
			." AND id='continuarComprando'"
	));
	
	if($continuarComprando){
		$JScarrinho['continuarComprando'] = $continuarComprando['valor'];
	} else {
		$JScarrinho['continuarComprando'] = '/';
	}
	
	// ===== Finalizar o layout com as variáveis padrões.
	
	layout_loja();
	
	// ===== Finalizar interface.
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	interface_finalizar();
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('modulos');
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['carrinho'] = $JScarrinho;
}

// ==== Ajax

function carrinho_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Adicionar ou diminuir uma quantidade de um serviço.
	
	if(isset($_REQUEST['adicionar']) || isset($_REQUEST['diminuir'])){
		// ===== Verificar se foi enviado o id do serviço.
		
		if(isset($_REQUEST['id']) || isset($_REQUEST['id_alt'])){
			$retornoOperacoes = carrinho_operacoes(Array(
				'ajax' => true,
				'opcao' => (isset($_REQUEST['adicionar']) ? 'adicionar' : 'diminuir'),
				'id' => (isset($_REQUEST['id']) ? banco_escape_field($_REQUEST['id']) : null),
				'id_alt' => (isset($_REQUEST['id_alt']) ? banco_escape_field($_REQUEST['id_alt']) : null),
				'variacao_id' => (isset($_REQUEST['variacao_id']) ? banco_escape_field($_REQUEST['variacao_id']) : null),
				'quantidade' => (isset($_REQUEST['quantidade']) ? $_REQUEST['quantidade'] : null),
			));
		}
	}
	
	// ===== Excluir um serviço.
	
	if(isset($_REQUEST['excluir'])){
		// ===== Verificar se foi enviado o id do serviço.
		
		if(isset($_REQUEST['id']) || isset($_REQUEST['id_alt'])){
			$retornoOperacoes = carrinho_operacoes(Array(
				'ajax' => true,
				'opcao' => 'excluir',
				'id' => (isset($_REQUEST['id']) ? banco_escape_field($_REQUEST['id']) : null),
				'id_alt' => (isset($_REQUEST['id_alt']) ? banco_escape_field($_REQUEST['id_alt']) : null),
				'variacao_id' => (isset($_REQUEST['variacao_id']) ? banco_escape_field($_REQUEST['variacao_id']) : null),
			));
		}
	}
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	
	// ===== Valores principais.
	
	$subtotal = 0;
	$descontos = 0;
	$total = 0;
	$quantidadeTotal = 0;
	
	// ===== Células.
	
	$cel_nome = 'vazio'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
	$cel_nome = 'cel-servicos'; $cel[$cel_nome] = pagina_celula($cel_nome);
	$cel_nome = 'cel-resumo'; $cel[$cel_nome] = pagina_celula($cel_nome);
	
	// ===== Carrinho no banco de dados.
	
	$carrinho = banco_select(Array(
		'unico' => true,
		'tabela' => 'carrinho',
		'campos' => Array(
			'id_hosts_carrinho',
		),
		'extra' => 
			"WHERE sessao_id='".$_GESTOR['session-id']."'"
	));
	
	// ===== Montar carrinho.
	
	if(isset($carrinho)){
		$carrinho_servicos = banco_select(Array(
			'tabela' => 'carrinho_servicos',
			'campos' => Array(
				'id_hosts_servicos',
				'id_hosts_arquivos_Imagem',
				'nome',
				'preco',
				'quantidade',
			),
			'extra' => 
				"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		));
		
		$carrinho_servico_variacoes = banco_select(Array(
			'tabela' => 'carrinho_servico_variacoes',
			'campos' => Array(
				'id_hosts_servicos',
				'id_hosts_servicos_variacoes',
				'id_hosts_arquivos_Imagem',
				'nome_servico',
				'nome_lote',
				'nome_variacao',
				'preco',
				'quantidade',
			),
			'extra' => 
				"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		));
		
		if($carrinho_servicos || $carrinho_servico_variacoes){
			// ===== Caso exista serviço nos carrinhos, montar células.
			
			if($carrinho_servicos)
			foreach($carrinho_servicos as $carServ){
				// ===== Buscar a imagem mini.
				
				$caminho_mini = '';
				
				$id_hosts_arquivos = $carServ['id_hosts_arquivos_Imagem'];
				
				if(isset($id_hosts_arquivos)){
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'caminho_mini',
						)),
						"arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
					);
					
					if($resultado){
						if(existe($resultado[0]['caminho_mini'])){
							$caminho_mini = $resultado[0]['caminho_mini'];
						}
					}
				}
				
				// ===== Imagem Mini ou Imagem Referência do serviço.
				
				if(existe($caminho_mini)){
					$imgSrc = $caminho_mini;
				} else {
					$imgSrc = 'images/imagem-padrao.png';
				}
				
				$carServ['imagem'] = '/' . $imgSrc;
				
				// ===== Calcular subtotal.
				
				$quantidadeTotal += (int)$carServ['quantidade'];
				$carServ['subtotal'] = (float)$carServ['preco'] * (int)$carServ['quantidade'];
				$subtotal += $carServ['subtotal'];
				
				// ===== Montar a célula do serviço.
				
				$cel_nome = 'cel-servicos';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-id",$carServ['id_hosts_servicos']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,' data-id-varicao="@[[varicao-id]]@"','',true);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$carServ['imagem']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['nome']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-preco",formato_dado_para('float-para-texto',$carServ['preco']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-quantidade",formato_dado_para('int-para-texto',$carServ['quantidade']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
				
				// ===== Montar a célula do resumo.
				
				$cel_nome = 'cel-resumo';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['quantidade'].'x '.$carServ['nome']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
			}
			
			// ===== Caso exista variações do serviço nos carrinhos, montar células.
			
			if($carrinho_servico_variacoes)
			foreach($carrinho_servico_variacoes as $carServ){
				// ===== Buscar a imagem mini.
				
				$caminho_mini = '';
				
				$id_hosts_arquivos = $carServ['id_hosts_arquivos_Imagem'];
				
				if(isset($id_hosts_arquivos)){
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'caminho_mini',
						)),
						"arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
					);
					
					if($resultado){
						if(existe($resultado[0]['caminho_mini'])){
							$caminho_mini = $resultado[0]['caminho_mini'];
						}
					}
				}
				
				// ===== Imagem Mini ou Imagem Referência do serviço.
				
				if(existe($caminho_mini)){
					$imgSrc = $caminho_mini;
				} else {
					$imgSrc = 'images/imagem-padrao.png';
				}
				
				$carServ['imagem'] = '/' . $imgSrc;
				
				// ===== Calcular subtotal.
				
				$quantidadeTotal += (int)$carServ['quantidade'];
				$carServ['subtotal'] = (float)$carServ['preco'] * (int)$carServ['quantidade'];
				$subtotal += $carServ['subtotal'];
				
				// ===== Montar a célula do serviço.
				
				$cel_nome = 'cel-servicos';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-id",$carServ['id_hosts_servicos']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"varicao-id",$carServ['id_hosts_servicos_variacoes']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$carServ['imagem']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['nome_servico'].'<div class="sub header">'.$carServ['nome_lote'].' - '.$carServ['nome_variacao'].'</div>');
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-preco",formato_dado_para('float-para-texto',$carServ['preco']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-quantidade",formato_dado_para('int-para-texto',$carServ['quantidade']));
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
				
				// ===== Montar a célula do resumo.
				
				$cel_nome = 'cel-resumo';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$carServ['quantidade'].'x '.$carServ['nome_servico'].'<div class="sub header">'.$carServ['nome_lote'].' - '.$carServ['nome_variacao'].'</div>');
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$carServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
			}
			
			$cel_nome = 'cel-servicos'; pagina_celula_incluir($cel_nome,'');
			$cel_nome = 'cel-resumo'; pagina_celula_incluir($cel_nome,'');
		} else {
			pagina_trocar_variavel_valor('<!-- cel-servicos -->',$cel['vazio'],true);
		}
	} else {
		pagina_trocar_variavel_valor('<!-- cel-servicos -->',$cel['vazio'],true);
		$JScarrinho['vazio'] = true;
	}
	
	// ===== Finalizar resumo.
	
	$total = $subtotal - $descontos;
	
	$subtotalStr = formato_dado_para('float-para-texto',$subtotal);
	$descontosStr = formato_dado_para('float-para-texto',$descontos);
	$totalStr = formato_dado_para('float-para-texto',$total);
	
	pagina_trocar_variavel_valor('subtotal',$subtotalStr);
	pagina_trocar_variavel_valor('descontos',$descontosStr);
	pagina_trocar_variavel_valor('total',$totalStr);
	
	// ===== Montar servicos e resumo.
	
	$cel_nome = 'cont-servicos'; $cel[$cel_nome] = pagina_celula($cel_nome); $JScarrinho['servicos'] = $cel[$cel_nome];
	$cel_nome = 'cont-resumo'; $cel[$cel_nome] = pagina_celula($cel_nome); $JScarrinho['resumo'] = $cel[$cel_nome];
	
	// ===== Caso a quantidadeTotal seja zerado, não permitir prosseguir.
	
	if($quantidadeTotal == 0){
		$JScarrinho['vazio'] = true;
	}
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => $retornoOperacoes['status'],
		'msg' => (isset($retornoOperacoes['msg']) ? $retornoOperacoes['msg'] : null),
	);
	
	if(isset($JScarrinho)){
		$_GESTOR['ajax-json'] = array_merge($_GESTOR['ajax-json'],$JScarrinho);
	}
}

// ==== Start

function carrinho_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': carrinho_ajax_opcao(); break;
			default: carrinho_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': carrinho_opcao(); break;
			default: carrinho_padrao();
		}
	}
}

carrinho_start();

?>