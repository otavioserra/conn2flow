<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'emissao';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.31',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function emissao_criar_pedido(){
	global $_GESTOR;
	
	// ===== Verificar se existe sessão para criar pedido.
	
	if(existe(gestor_sessao_variavel('criar-pedido'))){
		
		gestor_incluir_biblioteca('interface');
		
		// ===== Apagar a sessão quando tudo finalizar.
		
		gestor_sessao_variavel_del('criar-pedido');
		
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
		
		if($carrinho){
			// ===== API-Servidor para criar conta.
			
			gestor_incluir_biblioteca(Array('api-servidor','usuario'));
			
			$retorno = api_servidor_emissao(Array(
				'opcao' => 'criarPedido',
				'sessao_id' => $_GESTOR['session-id'],
				'id_hosts_usuarios' => usuario_dados(Array('campo'=>'id_hosts_usuarios')),
				'id_hosts_carrinho' => $carrinho['id_hosts_carrinho'],
			));
			
			if(!$retorno['completed']){
				switch($retorno['status']){
					case 'CART_NOT_FOUND':
					case 'ORDER_NOT_COMPLETED': 
						$alerta = $retorno['error-msg']; break;
					default:
						$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
						
						$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
				}
				
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
				
				// ===== Redirecionar para o cadastro.
				
				gestor_redirecionar('carrinho/');
			} else {
				// ===== Dados de retorno.
				
				$dados = Array();
				if(isset($retorno['data'])){
					$dados = $retorno['data'];
				}
				
				// ===== Criar o pedido localmente.
				
				$pedidos = $dados['hosts_pedidos'];
				$pedidos_servicos = $dados['hosts_pedidos_servicos'];
				$pedidos_servico_variacoes = $dados['hosts_pedidos_servico_variacoes'];
				$vouchers = $dados['hosts_vouchers'];
				
				// ====== Criar o pedido no banco.
				
				$campos = null; $campo_sem_aspas_simples = null;
				
				foreach($pedidos as $chave => $dado){
					switch($chave){
						/* case 'bool':
							$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
						break;
						case 'int':
							$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
						break; */
						default:
							if(existe($dado)){
								$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							}
					}
				}
				
				banco_insert_name
				(
					$campos,
					"pedidos"
				);
				
				// ====== Criar os serviços do pedido no banco.
				
				if($pedidos_servicos){
					foreach($pedidos_servicos as $pedido_servico){
						$campos = null; $campo_sem_aspas_simples = null;
						
						foreach($pedido_servico as $chave => $dado){
							switch($chave){
								case 'gratuito':
									$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
								break;
								/* case 'int':
									$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
								break; */
								default:
									if(existe($dado)){
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									}
							}
						}
						
						banco_insert_name
						(
							$campos,
							"pedidos_servicos"
						);
					}
				}
				
				// ====== Criar as variações dos serviços do pedido no banco.
				
				if($pedidos_servico_variacoes){
					foreach($pedidos_servico_variacoes as $pedido_servico){
						$campos = null; $campo_sem_aspas_simples = null;
						
						foreach($pedido_servico as $chave => $dado){
							switch($chave){
								case 'gratuito':
									$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
								break;
								/* case 'int':
									$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
								break; */
								default:
									if(existe($dado)){
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									}
							}
						}
						
						banco_insert_name
						(
							$campos,
							"pedidos_servico_variacoes"
						);
					}
				}
				
				// ====== Criar os vouchers do pedido no banco.
				
				if($vouchers){
					foreach($vouchers as $voucher){
						$campos = null; $campo_sem_aspas_simples = null;
						
						foreach($voucher as $chave => $dado){
							switch($chave){
								case 'loteVariacao':
									$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
								break;
								case 'id_hosts_servicos_variacoes':
									$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
								break;
								default:
									if(existe($dado)){
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									}
							}
						}
						
						banco_insert_name
						(
							$campos,
							"vouchers"
						);
					}
				}
				
				// ===== Remover os serviços do carrinho.
				
				banco_delete
				(
					"carrinho_servicos",
					"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
				);
				
				// ===== Remover as variações dos serviços do carrinho.
				
				banco_delete
				(
					"carrinho_servico_variacoes",
					"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
				);
				
				// ===== Remover carrinho.	
				
				banco_delete
				(
					"carrinho",
					"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
				);
				
				// ===== Redirecionar para a emissao deste pedido.
				
				gestor_redirecionar('emissao/?pedido='.$pedidos['codigo']);
			}
		}
	}
}

function emissao_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('interface');
	
	// ===== Identificação do pedido.
	
	if(!isset($_REQUEST['pedido'])){
		$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-informar-pedido'));
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
		
		gestor_redirecionar('minha-conta/');
	} else {
		$codigo = $_REQUEST['pedido'];
	}
	
	// ===== Valores principais.
	
	$subtotal = 0;
	$descontos = 0;
	$total = 0;
	$quantidadeTotal = 0;
	$JSemissao = Array();
	
	// ===== Pedidos no banco de dados.
	
	$pedidos = banco_select(Array(
		'unico' => true,
		'tabela' => 'pedidos',
		'campos' => Array(
			'id_hosts_pedidos',
			'status',
		),
		'extra' => 
			"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
			." AND codigo='".$codigo."'"
	));
	
	// ===== Montar pedidos.
	
	if($pedidos){
		// ===== Conferir se o pedido foi pago.
		
		if($pedidos['status'] != 'novo'){
			$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-pedido-nao-novo'));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('minha-conta/');
		}
		
		// ===== Criar emissão e redirecionar pagamento caso botão prosseguir foi acionado.
		
		if(isset($_REQUEST['emissao-salvar'])){
			// ===== Caso haja alguma mudança de identidade, criar vetor com os novos valores.
			
			if(isset($_REQUEST['vouchers'])){
				$vouchersArr = explode(',',$_REQUEST['vouchers']);
				
				for($i=0; $i<count($vouchersArr); $i++){
					$voucherID = $vouchersArr[$i];
					
					$campos = Array('nome','documento','telefone');
					
					$voucherCampos = Array();
					
					foreach($campos as $campo){
						if(isset($_REQUEST[$voucherID . '_' . $campo])){
							$voucherCampos[$campo] = banco_escape_field($_REQUEST[$voucherID . '_' . $campo]);
						} else {
							$voucherCampos[$campo] = '';
						}
					}
					
					$vouchersAlterados[$voucherID] = $voucherCampos;
				}
			}
			
			// ===== API-Servidor salvar identidades.
			
			gestor_incluir_biblioteca('api-servidor');
			
			$retorno = api_servidor_emissao(Array(
				'opcao' => 'salvarIdentidades',
				'codigo' => $codigo,
				'id_hosts_usuarios' => $_GESTOR['usuario-id'],
				'vouchersAlterados' => $vouchersAlterados,
			));
			
			if(api_servidor_retorno_verificacao(Array(
				'retorno' => $retorno,
				'redirecionar' => 'emissao/?pedido='.$codigo
			))){
				// ===== Dados de retorno.
				
				$dados = api_servidor_retorno_dados(Array('retorno' => $retorno));
				
				// ===== Atualizar os vouchers.
				
				if(isset($dados['hosts_vouchers'])){
					foreach($dados['hosts_vouchers'] as $voucher){
						$id_hosts_vouchers = $voucher['id_hosts_vouchers'];
						unset($voucher['id_hosts_vouchers']);
						
						foreach($voucher as $chave => $dado){
							switch($chave){
								/* case 'bool':
									banco_update_campo($chave,(existe($dado) ? "1" : "NULL"),true,true);
								break;
								case 'int':
									banco_update_campo($chave,(existe($dado) ? $dado : "NULL"),true,true);
								break; */
								default:
									if(existe($dado)){
										banco_update_campo($chave,$dado,false,true);
									}
							}
						}
						
						banco_update_executar('vouchers',"WHERE id_hosts_vouchers='".$id_hosts_vouchers."'");
					}
				}
				
				// ===== Redirecionar para a página de pagamento.
				
				if(isset($_REQUEST['pedidoGratuito'])){
					gestor_redirecionar('pagamento/?pedido='.$codigo.'&pedidoGratuito=1');
				} else {
					gestor_redirecionar('pagamento/?pedido='.$codigo);
				}
			}
		}
		
		// ===== Pegar dados do usuário para colocar no voucher.
		
		gestor_incluir_biblioteca('usuario');
		
		$usuario['nome'] = usuario_dados(Array('campo'=>'nome'));
		$usuario['documento'] = (existe(usuario_dados(Array('campo'=>'cnpj_ativo'))) ? usuario_dados(Array('campo'=>'cnpj')) : usuario_dados(Array('campo'=>'cpf')));
		$usuario['telefone'] = usuario_dados(Array('campo'=>'telefone'));
		
		// ===== Células.
		
		$cel_nome = 'vazio'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
		$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
		$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
		
		$cel_nome = 'cel-voucher'; $cel[$cel_nome] = pagina_celula($cel_nome);
		$cel_nome = 'cel-resumo'; $cel[$cel_nome] = pagina_celula($cel_nome);
		
		// ===== Pedido serviços.
		
		$pedidos_servicos = banco_select(Array(
			'tabela' => 'pedidos_servicos',
			'campos' => Array(
				'id_hosts_servicos',
				'id_hosts_arquivos_Imagem',
				'nome',
				'preco',
				'quantidade',
			),
			'extra' => 
				"WHERE id_hosts_pedidos='".$pedidos['id_hosts_pedidos']."'"
		));
		
		// ===== Pedido serviços variações.
		
		$pedidos_servico_variacoes = banco_select(Array(
			'tabela' => 'pedidos_servico_variacoes',
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
				"WHERE id_hosts_pedidos='".$pedidos['id_hosts_pedidos']."'"
		));
		
		// ===== Pedido vouchers.
		
		$vouchers = banco_select(Array(
			'tabela' => 'vouchers',
			'campos' => Array(
				'id_hosts_servicos',
				'id_hosts_servicos_variacoes',
				'codigo',
				'nome',
				'documento',
				'telefone',
				'loteVariacao',
			),
			'extra' => 
				"WHERE id_hosts_pedidos='".$pedidos['id_hosts_pedidos']."'"
		));
		
		if($pedidos_servicos || $pedidos_servico_variacoes){
			// ===== Varrer todos os serviços.
			
			foreach($pedidos_servicos as $pedServ){
				// ===== Buscar a imagem mini.
				
				$caminho_mini = '';
				
				$id_hosts_arquivos = $pedServ['id_hosts_arquivos_Imagem'];
				
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
				
				$pedServ['imagem'] = '/' . $imgSrc;
				
				// ===== Calcular subtotal.
				
				$quantidadeTotal += (int)$pedServ['quantidade'];
				$pedServ['subtotal'] = (float)$pedServ['preco'] * (int)$pedServ['quantidade'];
				$subtotal += $pedServ['subtotal'];
				
				// ===== Montar todos os vouchers deste serviço.
				
				if($vouchers)
				foreach($vouchers as $voucher){
					if($voucher['id_hosts_servicos'] == $pedServ['id_hosts_servicos'] && !$voucher['loteVariacao']){
						// ===== Montar a célula do voucher.
						
						$cel_nome = 'cel-voucher';
						
						$cel_aux = $cel[$cel_nome];
						
						// ===== Dados do voucher.
						
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-id",$voucher['codigo']);
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$pedServ['imagem']);
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",'Voucher #'.$voucher['codigo'].': '.$pedServ['nome']);
						
						// ===== Dados de identidade.
						
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-nome",(existe($voucher['nome']) ? $voucher['nome'] : $usuario['nome']));
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-documento",(existe($voucher['documento']) ? $voucher['documento'] : $usuario['documento']));
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-telefone",(existe($voucher['telefone']) ? $voucher['telefone'] : $usuario['telefone']));
						
						pagina_celula_incluir($cel_nome,$cel_aux);
					}
				}
				
				// ===== Montar a célula do resumo.
				
				$cel_nome = 'cel-resumo';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$pedServ['quantidade'].'x '.$pedServ['nome']);
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$pedServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
			}
			
			// ===== Varrer todas as variações dos serviços.
			
			foreach($pedidos_servico_variacoes as $pedServ){
				// ===== Buscar a imagem mini.
				
				$caminho_mini = '';
				
				$id_hosts_arquivos = $pedServ['id_hosts_arquivos_Imagem'];
				
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
				
				$pedServ['imagem'] = '/' . $imgSrc;
				
				// ===== Calcular subtotal.
				
				$quantidadeTotal += (int)$pedServ['quantidade'];
				$pedServ['subtotal'] = (float)$pedServ['preco'] * (int)$pedServ['quantidade'];
				$subtotal += $pedServ['subtotal'];
				
				// ===== Montar todos os vouchers deste serviço.
				
				if($vouchers)
				foreach($vouchers as $voucher){
					if($voucher['id_hosts_servicos_variacoes'] == $pedServ['id_hosts_servicos_variacoes'] && $voucher['loteVariacao']){
						// ===== Montar a célula do voucher.
						
						$cel_nome = 'cel-voucher';
						
						$cel_aux = $cel[$cel_nome];
						
						// ===== Dados do voucher.
						
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-id",$voucher['codigo']);
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$pedServ['imagem']);
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",'Voucher #'.$voucher['codigo'].': '.$pedServ['nome_servico'].'<div class="sub header">'.$pedServ['nome_lote'].' - '.$pedServ['nome_variacao'].'</div>');
						
						// ===== Dados de identidade.
						
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-nome",(existe($voucher['nome']) ? $voucher['nome'] : $usuario['nome']));
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-documento",(existe($voucher['documento']) ? $voucher['documento'] : $usuario['documento']));
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-telefone",(existe($voucher['telefone']) ? $voucher['telefone'] : $usuario['telefone']));
						
						pagina_celula_incluir($cel_nome,$cel_aux);
					}
				}
				
				// ===== Montar a célula do resumo.
				
				$cel_nome = 'cel-resumo';
				
				$cel_aux = $cel[$cel_nome];

				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$pedServ['quantidade'].'x '.$pedServ['nome_servico'].'<div class="sub header">'.$pedServ['nome_lote'].' - '.$pedServ['nome_variacao'].'</div>');
				$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$pedServ['subtotal']));
				
				pagina_celula_incluir($cel_nome,$cel_aux);
			}
			
			// ===== Remover marcadores.
			
			$cel_nome = 'cel-voucher'; pagina_celula_incluir($cel_nome,'');
			$cel_nome = 'cel-resumo'; pagina_celula_incluir($cel_nome,'');
			
			pagina_trocar_variavel_valor('<!-- cel-voucher -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-vouchers < -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-vouchers > -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-resumo < -->','',true);
			pagina_trocar_variavel_valor('<!-- cont-resumo > -->','',true);
		} else {
			pagina_trocar_variavel_valor('<!-- cel-voucher -->',$cel['vazio'],true);
		}
		
		// ===== Identificador do pedido.
		
		$JSemissao['codigo'] = $codigo;
		$JSemissao['formUrl'] = '/emissao/';
	} else {
		$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-pedido-nao-encontrado'));
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
		
		gestor_redirecionar('minha-conta/');
	}
	
	// ===== Finalizar resumo.
	
	$total = $subtotal - $descontos;
	
	$subtotalStr = formato_dado_para('float-para-texto',$subtotal);
	$descontosStr = formato_dado_para('float-para-texto',$descontos);
	$totalStr = formato_dado_para('float-para-texto',$total);
	
	pagina_trocar_variavel_valor('subtotal',$subtotalStr);
	pagina_trocar_variavel_valor('descontos',$descontosStr);
	pagina_trocar_variavel_valor('total',$totalStr);
	pagina_trocar_variavel_valor('codigo','#'.$codigo);
	
	// ===== Se o valor total for zerado, botão direto para o voucher, senão para o pagamento.
	
	gestor_incluir_biblioteca('html');
	
	html_iniciar(Array('gestor' => true));
	
	if($total == 0){
		html_elemento(Array(
			'consulta' => 'botaoProximo',
			'opcao' => 'excluir',
		));
	} else {
		html_elemento(Array(
			'consulta' => 'botaoProximoGratuito',
			'opcao' => 'excluir',
		));
	}
	
	html_finalizar(Array('gestor' => true));
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step',$cel['step']);
	layout_trocar_variavel_valor('layout#step-mobile',$cel['step-mobile']);
	
	// ===== Formulário validação.
	
	gestor_incluir_biblioteca('formulario');
	
	formulario_validacao(Array(
		'formId' => 'formAlterarIdentificacao',
		'validacao' => Array(
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'nome',
				'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'emissao-nome-label')),
			),
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'documento',
				'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'emissao-documento-label')),
			),
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'telefone',
				'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'emissao-telefone-label')),
			),
		)
	));
	
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
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>');
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('modulos');
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['emissao'] = $JSemissao;
}

// ==== Ajax

function emissao_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function emissao_start(){
	global $_GESTOR;
	
	// ===== Ativar criação de pedido.
	
	if(isset($_REQUEST['criar-pedido'])){
		gestor_sessao_variavel('criar-pedido','true');
	}
	
	// ===== Criar pedido.
	
	emissao_criar_pedido();
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': emissao_ajax_opcao(); break;
			default: emissao_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': emissao_opcao(); break;
			default: emissao_padrao();
		}
	}
}

emissao_start();

?>