<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'servicos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.33',
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
	}
	
	if($ajax){
		return Array(
			'status' => 'ERROR_UNKNOWN',
		);
	}
}

// ===== Funções Principais

function servicos_padrao(){
	global $_GESTOR;
	
	// ===== Identificador do serviço.
	
	$id_hosts_servicos = $_GESTOR['modulo_id_registro'];
	
	// ===== Verificar os dados do serviço.
	
	$servicos = banco_select(Array(
		'unico' => true,
		'tabela' => 'servicos',
		'campos' => Array(
			'id_servicos',
			'id_hosts_arquivos_Imagem',
			'nome',
			'descricao',
			'preco',
			'status',
			'quantidade',
			'lotesVariacoes',
			'gratuito',
		),
		'extra' => 
			"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
	));
	
	if($servicos){
		// ===== Caso o serviço estiver ativo 'A' continua.
		
		if($servicos['status'] == 'A'){
			// ===== Incluir bibliotecas.
			
			gestor_incluir_biblioteca('pagina');
			gestor_incluir_biblioteca('formato');
			gestor_incluir_biblioteca('html');
			gestor_incluir_biblioteca('interface');
			
			// ===== Montar a página do serviço.
			
			$caminho_mini = '';
			
			$id_hosts_arquivos = $servicos['id_hosts_arquivos_Imagem'];
			
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
			
			$servicos['imagem-caminho'] = '/' . $imgSrc;
			
			// ===== Trocar as variáveis pelos seus valores no html do template.
			
			pagina_trocar_variavel_valor('servico#nome',$servicos['nome']);
			pagina_trocar_variavel_valor('servico#descricao',$servicos['descricao']);
			pagina_trocar_variavel_valor('servico#imagem-caminho',$servicos['imagem-caminho']);
			
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
			
			// ===== Verificar se o serviço é do tipo simples ou lotes / variações.
			
			if($servicos['lotesVariacoes']){
				// ===== Apagar a célula 'simples'.
				
				$cel_nome = 'simples'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				
				// ===== Pegar as céluas dos lotes e variáveis.
				
				$cel_nome = 'cel-variacoes'; $cel[$cel_nome] = pagina_celula($cel_nome);
				$cel_nome = 'cel-lotes'; $cel[$cel_nome] = pagina_celula($cel_nome);
				
				// ===== Pegar os dados dos lotes e variações.
				
				$servicos_lotes = banco_select(Array(
					'tabela' => 'servicos_lotes',
					'campos' => Array(
						'id_hosts_servicos_lotes',
						'nome',
						'visibilidade',
						'dataInicio',
						'dataFim',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
						." ORDER BY id_servicos_lotes ASC"
				));
				
				$servicos_variacoes = banco_select(Array(
					'tabela' => 'servicos_variacoes',
					'campos' => Array(
						'id_hosts_servicos_variacoes',
						'id_hosts_servicos_lotes',
						'nome',
						'preco',
						'quantidade',
						'gratuito',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
						." ORDER BY id_servicos_variacoes ASC"
				));
				
				// ===== Sinalizador de disponibilidade de pelo menos um lote com uma variação.
				
				$disponivelLoteVariavel = false;
				
				// ===== Tempo agora.
				
				$tempoAgora = time();
				
				// ===== Varrer todos os lotes encontrados.
				
				$variacoes = Array();
				$total = 0;
				
				if($servicos_lotes)
				foreach($servicos_lotes as $lote){
					// ===== Dinalizador de disponibilidade do lote.
					
					$disponivel = false;
					
					// ===== Verificar baseado na visibilidade se o lote está ou não disponível.
					
					switch($lote['visibilidade']){
						case 'sempre':
							$disponivel = true;
						break;
						case 'datainicio':
							if(isset($lote['dataInicio'])){
								if(existe($lote['dataInicio'])){
									if($tempoAgora >= strtotime($lote['dataInicio'])){
										$disponivel = true;
									}
								} else {
									$disponivel = true;
								}
							} else {
								$disponivel = true;
							}
						break;
						case 'datafim':
							if(isset($lote['dataFim'])){
								if(existe($lote['dataFim'])){
									if($tempoAgora <= strtotime($lote['dataFim'])){
										$disponivel = true;
									}
								} else {
									$disponivel = true;
								}
							} else {
								$disponivel = true;
							}
						break;
						case 'periodo':
							$dataInicio = false;
							$dataFim = false;
							
							if(isset($lote['dataInicio'])){
								if(existe($lote['dataInicio'])){
									if($tempoAgora >= strtotime($lote['dataInicio'])){
										$dataInicio = true;
									}
								} else {
									$dataInicio = true;
								}
							} else {
								$dataInicio = true;
							}
							
							if(isset($lote['dataFim'])){
								if(existe($lote['dataFim'])){
									if($tempoAgora <= strtotime($lote['dataFim'])){
										$dataFim = true;
									}
								} else {
									$dataFim = true;
								}
							} else {
								$dataFim = true;
							}
							
							if($dataInicio && $dataFim){
								$disponivel = true;
							}
						break;
					}
					
					// ===== Caso esteja disponível, montar os dados do lote.
					
					if($disponivel){
						// ===== Pegar célula do lote.
						
						$celLote = $cel['cel-lotes'];
						
						// ===== Trocar dados do lote.
						
						$celLote = pagina_trocar_variavel_valor('lote-nome',$lote['nome'],false,$celLote);
						
						// ===== Caso exista carrinho, pegar todos os preços do carrinho das variações do serviço.
						
						if($carrinho){
							$carrinho_servico_variacoes = banco_select(Array(
								'tabela' => 'carrinho_servico_variacoes',
								'campos' => Array(
									'id_hosts_servicos_variacoes',
									'preco',
									'quantidade',
								),
								'extra' => 
									"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
							));
							
						}
						
						// ===== Varrer todos as variações deste lote.
						
						$cel_nome = 'cel-variacoes';
						if($servicos_variacoes){
							foreach($servicos_variacoes as $variacao){
								if($lote['id_hosts_servicos_lotes'] == $variacao['id_hosts_servicos_lotes']){
									if((int)$variacao['quantidade'] > 0){
										$quantidadeCarrinho = 0;
										
										// ===== Se for marcado gratuito, o valor será zerado.
										
										if($variacao['gratuito']){
											$variacao['preco'] = '0';
										}
										
										// ===== Caso exista carrinho, usar o preço do carrinho ofertado anteriormente ao invés do preço da variação do serviço atual.
										
										if($carrinho_servico_variacoes){
											foreach($carrinho_servico_variacoes as $carrinho_variacao){
												if($carrinho_variacao['id_hosts_servicos_variacoes'] == $variacao['id_hosts_servicos_variacoes']){
													$variacao['preco'] = $carrinho_variacao['preco'];
													$quantidadeCarrinho = $carrinho_variacao['quantidade'];
													break;
												}
											}
										}
										
										// ===== Criar dados da variável JS da variação.
										
										$variacoes[$variacao['id_hosts_servicos_variacoes']] = Array(
											'preco' => $variacao['preco'],
											'quantidade' => $quantidadeCarrinho,
										);
										
										// ===== Verificar o valor subtotal.
										
										$subtotal = $quantidadeCarrinho * $variacao['preco'];
										$total += $subtotal;
										$subtotal = formato_dado(Array('valor' => $subtotal,'tipo' => 'float-para-texto'));
										
										// ===== Preparar o preço de float para texto.
										
										$variacao['preco'] = formato_dado(Array('valor' => $variacao['preco'],'tipo' => 'float-para-texto'));
										
										// ===== Popular células.
										
										$celVariacao = $cel[$cel_nome];
										
										$celVariacao = pagina_trocar_variavel_valor('variacao-id',$variacao['id_hosts_servicos_variacoes'],false,$celVariacao);
										$celVariacao = pagina_trocar_variavel_valor('variacao-nome',$variacao['nome'],false,$celVariacao);
										$celVariacao = pagina_trocar_variavel_valor('variacao-preco',$variacao['preco'],false,$celVariacao);
										$celVariacao = pagina_trocar_variavel_valor('variacao-subtotal',$subtotal,false,$celVariacao);
										$celVariacao = pagina_trocar_variavel_valor('variacao-quantidade',$quantidadeCarrinho,false,$celVariacao);
										
										$celLote = modelo_var_in($celLote,'<!-- '.$cel_nome.' -->',$celVariacao);
										
										// ===== Sinalizar que está disponível pelo menos um lote com uma variável.
										
										$disponivelLoteVariavel = true;
									}
								}
							}
							
							// ===== Popular os preços na variável JS para atualizações.
							
							$JSservicos['variacoes'] = $variacoes;
						}
						
						$celLote = modelo_var_troca($celLote,'<!-- '.$cel_nome.' -->','');
						
						// ===== Incluir o lote na página.
						
						pagina_celula_incluir('cel-lotes',$celLote);
					}
				}
				
				pagina_celula_incluir('cel-lotes','');
				
				// ===== Total.
				
				$total = formato_dado(Array('valor' => $total,'tipo' => 'float-para-texto'));
				
				pagina_trocar_variavel_valor('total',$total);
				
				// ===== Se não estiver disponível pemo menos um lote com uma variação, marcar o botão como indisponível.
				
				if(!$disponivelLoteVariavel){
					// ===== Pegar o html da página.
					
					$html = $_GESTOR['pagina'];
					
					if((int)$servicos['quantidade'] == 0){
						html_iniciar(Array('valor' => $html));
						
						$disabledTitle = html_atributo(Array(
							'consulta' => 'gestor-lote-variacao-comprar',
							'atributo' => 'data-disabled-title',
							'opcao' => 'valor',
						));
						
						if(existe($disabledTitle)){
							html_valor(Array(
								'consulta' => 'gestor-lote-variacao-comprar',
								'opcao' => 'mudar',
								'valor' => $disabledTitle
							));
						}
						
						$html = html_finalizar();
					}
					
					// ===== Atualizar o html da página.
					
					$_GESTOR['pagina'] = $html;
				}
			} else {
				// ===== Apagar a célula 'lotes-variacoes'.
				
				$cel_nome = 'lotes-variacoes'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				
				// ===== Caso exista carrinho, usar o preço do carrinho ofertado anteriormente ao invés do preço do serviço atual.
				
				if($carrinho){
					$carrinho_servicos = banco_select(Array(
						'unico' => true,
						'tabela' => 'carrinho_servicos',
						'campos' => Array(
							'preco',
						),
						'extra' => 
							"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
							." AND id_hosts_servicos='".$id_hosts_servicos."'"
					));
					
					if($carrinho_servicos){
						$servicos['preco'] = $carrinho_servicos['preco'];
					}
				}
				
				// ===== Se for marcado gratuito, o valor será zerado.
				
				if($servicos['gratuito']){
					$servicos['preco'] = '0';
				}
				
				// ===== Preparar o preço de float para texto.
				
				$servicos['preco'] = formato_dado(Array('valor' => $servicos['preco'],'tipo' => 'float-para-texto'));
				
				// ===== Trocar os valores dos dados na página html.
				
				pagina_trocar_variavel_valor('servico#preco',$servicos['preco']);
				
				// ===== Se não houver quantidade suficiente, desabilitar o botão e trocar o título do mesmo caso haja a opção.
				
				// ===== Pegar o html da página.
				
				$html = $_GESTOR['pagina'];
				
				if((int)$servicos['quantidade'] == 0){
					html_iniciar(Array('valor' => $html));
					
					$disabledTitle = html_atributo(Array(
						'consulta' => 'gestor-servico-comprar',
						'atributo' => 'data-disabled-title',
						'opcao' => 'valor',
					));
					
					if(existe($disabledTitle)){
						html_valor(Array(
							'consulta' => 'gestor-servico-comprar',
							'opcao' => 'mudar',
							'valor' => $disabledTitle
						));
					}
					
					html_adicionar_classe(Array(
						'consulta' => 'gestor-servico-comprar',
						'classe' => 'disabled',
					));
					
					$html = html_finalizar();
				}
				
				// ===== Atualizar o html da página.
				
				$_GESTOR['pagina'] = $html;
			}
			
			// ===== ID do serviço.
			
			$JSservicos['id_servicos'] = $servicos['id_servicos'];
			$JSservicos['carrinho_url'] = 'carrinho/';
			
			// ===== Variáveis JS.
			
			$_GESTOR['javascript-vars']['servicos'] = $JSservicos;
			
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
			
			return;
		}
	}
	
	// ===== Senão encontrar ou outro motivo, redirecionar para 404.
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	gestor_roteador_301_ou_404(Array(
		'caminho' => $caminho,
	));
}

// ==== Ajax

function servicos_ajax_padrao(){
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
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => $retornoOperacoes['status'],
		'msg' => (isset($retornoOperacoes['msg']) ? $retornoOperacoes['msg'] : null),
	);
}

// ==== Start

function servicos_start(){
	global $_GESTOR;
	
	// ===== Id Externo relacionado. Senão existir, retornar sem executar.
	
	if(!isset($_GESTOR['modulo_id_registro'])){
		return;
	}
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': servicos_ajax_opcao(); break;
			default: servicos_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': servicos_opcao(); break;
			default: servicos_padrao();
		}
	}
}

servicos_start();

?>