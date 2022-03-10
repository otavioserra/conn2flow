<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'pedidos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.10',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_pedidos',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_pedidos',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
		'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
	),
);

function pedidos_visualizar(){
	global $_GESTOR;
	
	// ===== Pegar o identificador do pedido.
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Módulo dados.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('host');
	
	// ===== Selecionar dados do banco de dados.
	
	$hosts_pedidos = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_pedidos',
		'campos' => Array(
			'id_hosts_pedidos',
			'id_hosts_usuarios',
			'codigo',
			'total',
			'live',
			$modulo['tabela']['status'],
			$modulo['tabela']['versao'],
			$modulo['tabela']['data_criacao'],
			$modulo['tabela']['data_modificacao'],
		),
		'extra' => 
			"WHERE id_hosts='".$id_hosts."'"
			." AND id='".$id."'"
	));
	
	if(!$hosts_pedidos){
		gestor_redirecionar_raiz();
	}
	
	// ===== Dados dos serviços do pedido.
	
	$hosts_pedidos_servicos = banco_select(Array(
		'tabela' => 'hosts_pedidos_servicos',
		'campos' => Array(
			'id_hosts_servicos',
			'id_hosts_arquivos_Imagem',
			'nome',
			'preco',
			'quantidade',
		),
		'extra' => 
			"WHERE id_hosts_pedidos='".$hosts_pedidos['id_hosts_pedidos']."'"
			." AND id_hosts='".$id_hosts."'"
			." ORDER BY nome ASC"
	));
	
	// ===== Dados das variações dos serviços do pedido.
	
	$hosts_pedidos_servico_variacoes = banco_select(Array(
		'tabela' => 'hosts_pedidos_servico_variacoes',
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
			"WHERE id_hosts_pedidos='".$hosts_pedidos['id_hosts_pedidos']."'"
			." AND id_hosts='".$id_hosts."'"
			." ORDER BY nome_servico ASC"
	));
	
	// ===== Dados dos vouchers.
	
	$hosts_vouchers = banco_select(Array(
		'tabela' => 'hosts_vouchers',
		'campos' => Array(
			'id_hosts_servicos',
			'id_hosts_servicos_variacoes',
			'codigo',
			'nome',
			'documento',
			'telefone',
			'loteVariacao',
			'status',
			'data_uso',
		),
		'extra' => 
			"WHERE id_hosts_pedidos='".$hosts_pedidos['id_hosts_pedidos']."'"
			." AND id_hosts='".$id_hosts."'"
			." ORDER BY codigo ASC"
	));
	
	$hosts_vouchers_status = banco_select(Array(
		'tabela' => 'variaveis',
		'campos' => Array(
			'valor',
			'id',
		),
		'extra' => 
			"WHERE modulo='_sistema'"
			." AND grupo='pedidos-voucher-status'"
	));
	
	// ===== Montar células de resumo e dos vouchers.
	
	$cel_nome = 'cel-servico'; $cel[$cel_nome] = pagina_celula($cel_nome);
	$cel_nome = 'cel-voucher'; $cel[$cel_nome] = pagina_celula($cel_nome);
	$cel_nome = 'cel-pagamentos'; $cel[$cel_nome] = pagina_celula($cel_nome);
	
	// ===== Varrer os serviços do pedido.
	
	if($hosts_pedidos_servicos)
	foreach($hosts_pedidos_servicos as $pedido_servico){
		$subtotal = (int)$pedido_servico['quantidade'] * (float)$pedido_servico['preco'];
		
		$cel_nome = 'cel-servico';
		
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$pedido_servico['nome']);
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"quantidade",formato_dado_para('int-para-texto',$pedido_servico['quantidade']));
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"preco",'R$ '.formato_dado_para('float-para-texto',$pedido_servico['preco']));
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"subtotal",'R$ '.formato_dado_para('float-para-texto',$subtotal));
		
		pagina_celula_incluir($cel_nome,$cel_aux);
		
		// ===== Buscar a imagem mini.
		
		$caminho_mini = '';
		
		$id_hosts_arquivos = $pedido_servico['id_hosts_arquivos_Imagem'];
		
		if(isset($id_hosts_arquivos)){
			$hosts_arquivos = banco_select_name(
				banco_campos_virgulas(Array(
					'caminho_mini',
				)),
				"hosts_arquivos",
				"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
			);
			
			if($hosts_arquivos){
				if(existe($hosts_arquivos[0]['caminho_mini'])){
					$caminho_mini = $hosts_arquivos[0]['caminho_mini'];
				}
			}
		}
		
		// ===== Imagem Mini ou Imagem Referência do serviço.
		
		if(existe($caminho_mini)){
			$imgSrc = $caminho_mini;
		} else {
			$imgSrc = 'images/imagem-padrao.png';
		}
		
		$pedido_servico['imagem'] = host_url(Array('opcao' => 'full')) . $imgSrc;
		
		// ===== Montar todos os vouchers deste serviço.
		
		if($hosts_vouchers){
			foreach($hosts_vouchers as $voucher){
				if($voucher['id_hosts_servicos'] == $pedido_servico['id_hosts_servicos'] && !$voucher['loteVariacao']){
					// ===== Montar a célula do voucher.
					
					$cel_nome = 'cel-voucher';
					
					$cel_aux = $cel[$cel_nome];
					
					// ===== Dados do voucher.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-id",$voucher['codigo']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$pedido_servico['imagem']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",'Voucher #'.$voucher['codigo'].': '.$pedido_servico['nome']);
					
					// ===== Dados de identidade.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-nome",(existe($voucher['nome']) ? $voucher['nome'] : $usuario['nome']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-documento",(existe($voucher['documento']) ? $voucher['documento'] : $usuario['documento']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-telefone",(existe($voucher['telefone']) ? $voucher['telefone'] : $usuario['telefone']));
					
					// ===== Estado do voucher.
					
					$voucherStatus = '';
					
					if($hosts_vouchers_status)
					foreach($hosts_vouchers_status as $status){
						$found = false;
						switch($status['id']){
							case 'disponivel':
								if($voucher['status'] == 'jwt-bd-expirado' || $voucher['status'] == 'jwt-gerado'){
									$found = true;
								}
							break;
							default:
								if($voucher['status'] == $status['id']){
									$found = true;
								}
						}
						
						if($found){
							$voucherStatus = $status['valor'];
							
							if($voucher['status'] == 'usado'){
								gestor_incluir_biblioteca('formato');
								
								$data_uso = formato_dado(Array(
									'valor' => $voucher['data_uso'],
									'tipo' => 'dataHora',
								));
								
								$dataDaBaixa = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'orders-finish-date'));
								$dataDaBaixa = modelo_var_troca_tudo($dataDaBaixa,"#data#",$data_uso);
								
								$voucherStatus .= $dataDaBaixa;
							}
							break;
						}
					}
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-status",$voucherStatus);
					
					// ===== Incluir na célula.
					
					pagina_celula_incluir($cel_nome,$cel_aux);
				}
			}
		} else {
			$sem_vouchers = true;
		}
	}
	
	// ===== Varrer as variações dos serviços do pedido.
	
	if($hosts_pedidos_servico_variacoes)
	foreach($hosts_pedidos_servico_variacoes as $pedido_servico){
		$subtotal = (int)$pedido_servico['quantidade'] * (float)$pedido_servico['preco'];
		
		$cel_nome = 'cel-servico';
		
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$pedido_servico['nome_servico'].' | '.$pedido_servico['nome_lote'].' | '.$pedido_servico['nome_variacao']);
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"quantidade",formato_dado_para('int-para-texto',$pedido_servico['quantidade']));
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"preco",'R$ '.formato_dado_para('float-para-texto',$pedido_servico['preco']));
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"subtotal",'R$ '.formato_dado_para('float-para-texto',$subtotal));
		
		pagina_celula_incluir($cel_nome,$cel_aux);
		
		// ===== Buscar a imagem mini.
		
		$caminho_mini = '';
		
		$id_hosts_arquivos = $pedido_servico['id_hosts_arquivos_Imagem'];
		
		if(isset($id_hosts_arquivos)){
			$hosts_arquivos = banco_select_name(
				banco_campos_virgulas(Array(
					'caminho_mini',
				)),
				"hosts_arquivos",
				"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
			);
			
			if($hosts_arquivos){
				if(existe($hosts_arquivos[0]['caminho_mini'])){
					$caminho_mini = $hosts_arquivos[0]['caminho_mini'];
				}
			}
		}
		
		// ===== Imagem Mini ou Imagem Referência do serviço.
		
		if(existe($caminho_mini)){
			$imgSrc = $caminho_mini;
		} else {
			$imgSrc = 'images/imagem-padrao.png';
		}
		
		$pedido_servico['imagem'] = host_url(Array('opcao' => 'full')) . $imgSrc;
		
		// ===== Montar todos os vouchers deste serviço.
		
		if($hosts_vouchers){
			foreach($hosts_vouchers as $voucher){
				if($voucher['id_hosts_servicos_variacoes'] == $pedido_servico['id_hosts_servicos_variacoes'] && $voucher['loteVariacao']){
					// ===== Montar a célula do voucher.
					
					$cel_nome = 'cel-voucher';
					
					$cel_aux = $cel[$cel_nome];
					
					// ===== Dados do voucher.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-id",$voucher['codigo']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$pedido_servico['imagem']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",'Voucher #'.$voucher['codigo'].': '.$pedido_servico['nome_servico'].'<div class="sub header">'.$pedido_servico['nome_lote'].' - '.$pedido_servico['nome_variacao'].'</div>');
					
					// ===== Dados de identidade.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-nome",(existe($voucher['nome']) ? $voucher['nome'] : $usuario['nome']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-documento",(existe($voucher['documento']) ? $voucher['documento'] : $usuario['documento']));
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-telefone",(existe($voucher['telefone']) ? $voucher['telefone'] : $usuario['telefone']));
					
					// ===== Estado do voucher.
					
					$voucherStatus = '';
					
					if($hosts_vouchers_status)
					foreach($hosts_vouchers_status as $status){
						$found = false;
						switch($status['id']){
							case 'disponivel':
								if($voucher['status'] == 'jwt-bd-expirado' || $voucher['status'] == 'jwt-gerado'){
									$found = true;
								}
							break;
							default:
								if($voucher['status'] == $status['id']){
									$found = true;
								}
						}
						
						if($found){
							$voucherStatus = $status['valor'];
							
							if($voucher['status'] == 'usado'){
								gestor_incluir_biblioteca('formato');
								
								$data_uso = formato_dado(Array(
									'valor' => $voucher['data_uso'],
									'tipo' => 'dataHora',
								));
								
								$dataDaBaixa = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'orders-finish-date'));
								$dataDaBaixa = modelo_var_troca_tudo($dataDaBaixa,"#data#",$data_uso);
								
								$voucherStatus .= $dataDaBaixa;
							}
							break;
						}
					}
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-status",$voucherStatus);
					
					// ===== Incluir na célula.
					
					pagina_celula_incluir($cel_nome,$cel_aux);
				}
			}
		} else {
			$sem_vouchers = true;
		}
	}
	
	if(isset($sem_vouchers)){
		$cel_nome = 'comp-vouchers'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	$cel_nome = 'cel-voucher'; pagina_celula_incluir($cel_nome,'');
	$cel_nome = 'cel-servico'; pagina_celula_incluir($cel_nome,'');
	
	// ===== Dados do cliente.
	
	$hosts_usuarios = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_usuarios',
		'campos' => Array(
			'nome',
			'email',
			'telefone',
			'cpf',
			'cnpj',
		),
		'extra' => 
			"WHERE id_hosts_usuarios='".$hosts_pedidos['id_hosts_usuarios']."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	pagina_trocar_variavel_valor('cliente-nome',$hosts_usuarios['nome']);
	pagina_trocar_variavel_valor('cliente-email',$hosts_usuarios['email']);
	pagina_trocar_variavel_valor('cliente-telefone',$hosts_usuarios['telefone']);
	pagina_trocar_variavel_valor('cliente-cpf',$hosts_usuarios['cpf']);
	pagina_trocar_variavel_valor('cliente-cnpj',$hosts_usuarios['cnpj']);
	
	// ===== Dados das requisições de pagamento.
	
	$hosts_paypal_pagamentos = banco_select(Array(
		'tabela' => 'hosts_paypal_pagamentos',
		'campos' => Array(
			'id_hosts_paypal_pagamentos',
			'pay_id',
			'final_id',
			'data_criacao',
			'data_modificacao',
			'status',
			'parcelas',
		),
		'extra' => 
			"WHERE id_hosts_pedidos='".$hosts_pedidos['id_hosts_pedidos']."'"
			." AND id_hosts='".$id_hosts."'"
			." ORDER BY id_hosts_paypal_pagamentos DESC"
	));
	
	// ===== Montar células das requisições.
	
	if($hosts_paypal_pagamentos){
		foreach($hosts_paypal_pagamentos as $pagamento){
			$cel_nome = 'cel-pagamentos';
			
			$cel_aux = $cel[$cel_nome];
			
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pay_id",$pagamento['pay_id']);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"final_id",($pagamento['final_id'] ? $pagamento['final_id'] : ''));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_criacao",formato_dado_para('dataHora',$pagamento['data_criacao']));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"data_modificacao",formato_dado_para('dataHora',$pagamento['data_modificacao']));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"status",($pagamento['status'] ? $pagamento['status'] : ''));
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"pagamento_id",$pagamento['id_hosts_paypal_pagamentos']);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"parcelas",($pagamento['parcelas'] ? $pagamento['parcelas'] : ''));
			
			pagina_celula_incluir($cel_nome,$cel_aux);
		}
	} else {
		$cel_nome = 'comp-pagamentos'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Modal para mostrar pagador.
	
	$modal .= gestor_componente(Array(
		'id' => 'modal-simples',
	));
	
	$modal = modelo_var_troca($modal,"#titulo#",'Pagador Dados');
	
	$_GESTOR['pagina'] .= $modal;
	
	// ===== Popular os metaDados.
	
	$retorno_bd = $hosts_pedidos;
	
	// ===== Mostrar código do pedido.
	
	$metaDados[] = Array(
		'titulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-key')),
		'dado' => $retorno_bd['codigo']
	);
	
	// ===== Mostrar valor total do pedido.
	
	$metaDados[] = Array(
		'titulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-total')),
		'dado' => 'R$ '.formato_dado_para('float-para-texto',$retorno_bd['total'])
	);

	// ===== Filtrar status.
	
	$statusFiltrado = banco_select(Array(
		'unico' => true,
		'tabela' => 'variaveis',
		'campos' => Array(
			'valor',
		),
		'extra' => 
			"WHERE modulo='_sistema'"
			." AND grupo='pedidos-status'"
			." AND id='".$retorno_bd[$modulo['tabela']['status']]."'"
	));
	
	if(isset($retorno_bd[$modulo['tabela']['status']])){
		$metaDados[] = Array(
			'titulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-status')),
			'dado' => $statusFiltrado['valor']
		);
	}
	
	// ===== Filtrar ambiente.
	
	$ambiente = banco_select(Array(
		'unico' => true,
		'tabela' => 'variaveis',
		'campos' => Array(
			'valor',
		),
		'extra' => 
			"WHERE modulo='_sistema'"
			." AND grupo='pedidos-ambiente'"
			." AND id='".($hosts_pedidos['live'] ? 'live' : 'sandbox' )."'"
	));
	
	// ===== Informar se for ambiente live ou sandbox.
	
	$metaDados[] = Array(
		'titulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-environment')),
		'dado' => $ambiente['valor']
	);
	
	// ===== Demais meta dados.
	
	if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
	if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface visualizar finalizar opções
	
	$_GESTOR['interface']['visualizar']['finalizar'] = Array(
		'campoTitulo' => 'codigo',
		'metaDados' => $metaDados,
	);
}

function pedidos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'codigo',
						'id_hosts_usuarios',
						'total',
						'status',
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
							'id' => 'codigo',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-key')),
							'ordenar' => 'desc',
						),
						Array(
							'id' => 'id_hosts_usuarios',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-client')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'hosts_usuarios',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id_hosts_usuarios',
								),
							)
						),
						Array(
							'id' => 'total',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-total')),
							'formatar' => 'dinheiroReais',
							'nao_procurar' => true,
						),
						Array(
							'id' => 'status',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'field-status')),
							'className' => 'dt-head-center',
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'variaveis',
									'campo_trocar' => 'valor',
									'campo_referencia' => 'id',
									'where' => "modulo='_sistema' AND grupo='pedidos-status'",
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
					'visualizar' => Array(
						'url' => 'visualizar/',
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-to-view')),
						'icon' => 'file alternate outline',
						'icon2' => 'search',
						'cor' => 'basic green',
					),
				),
				'botoes' => Array(
				),
			);
		break;
	}
}

// ==== Ajax

function pedidos_ajax_dados_do_comprador(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	$pagamento_id = banco_escape_field($_REQUEST['pagamento_id']);
	
	// ===== Dados do Pagador.
	
	$hosts_paypal_pagamentos = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_paypal_pagamentos',
		'campos' => Array(
			'pagador_primeiro_nome',
			'pagador_ultimo_nome',
			'pagador_email',
			'pagador_telefone',
			'pagador_cpf',
			'pagador_cnpj',
		),
		'extra' => 
			"WHERE id_hosts_paypal_pagamentos='".$pagamento_id."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	if($hosts_paypal_pagamentos){
		$layout = gestor_componente(Array(
			'id' => 'pedidos-dados-do-pagador',
		));
		
		$layout = modelo_var_troca($layout,"#pagador-nome#",$hosts_paypal_pagamentos['pagador_primeiro_nome'].' '.$hosts_paypal_pagamentos['pagador_ultimo_nome']);
		$layout = modelo_var_troca($layout,"#pagador-email#",$hosts_paypal_pagamentos['pagador_email']);
		$layout = modelo_var_troca($layout,"#pagador-telefone#",$hosts_paypal_pagamentos['pagador_telefone']);
		$layout = modelo_var_troca($layout,"#pagador-cpf#",($hosts_paypal_pagamentos['pagador_cpf'] ? $hosts_paypal_pagamentos['pagador_cpf'] : ''));
		$layout = modelo_var_troca($layout,"#pagador-cnpj#",($hosts_paypal_pagamentos['pagador_cnpj'] ? $hosts_paypal_pagamentos['pagador_cnpj'] : ''));
		
		$_GESTOR['ajax-json'] = Array(
			'pagador' => $layout,
			'status' => 'OK',
		);
	} else {
		$_GESTOR['ajax-json'] = Array(
			'msg' => 'Dados não encontrados!',
			'status' => 'ERROR',
		);
	}
}

// ==== Start

function pedidos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'dados-do-comprador': pedidos_ajax_dados_do_comprador(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		pedidos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'visualizar': pedidos_visualizar(); break;
			case 'excluir':
			case 'status':
				gestor_redirecionar_raiz();
			break;
		}
		
		interface_finalizar();
	}
}

pedidos_start();

?>