<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'pagamento';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.1.2',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function pagamento_padrao(){
	global $_GESTOR;
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('interface');
	gestor_incluir_biblioteca('formulario');
	
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
	
	// ===== Valores iniciais.
	
	$JSpagamento = Array();
	
	// ===== Verificar se o pedido é pagamento gratuito.
	
	if(isset($_REQUEST['pedidoGratuito'])){
		// ===== Células.
		
		$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
		$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
		
		$cel_nome = 'pagamento-ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		$cel_nome = 'pagamento-inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		
		// ===== Variáveis JS.
		
		$JSpagamento['pedidoGratuito'] = true;
		$JSpagamento['codigo'] = $codigo;
	} else {
		// ===== Variáveis do PayPal.
		
		$app_installed = (existe(gestor_variaveis(Array('modulo' => 'paypal','id' => 'app_installed'))) ? true : false);
		$app_active = (existe(gestor_variaveis(Array('modulo' => 'paypal','id' => 'app_active'))) ? true : false);
		$app_live = (existe(gestor_variaveis(Array('modulo' => 'paypal','id' => 'app_live'))) ? true : false);
		$paypal_plus_inactive = (existe(gestor_variaveis(Array('modulo' => 'paypal','id' => 'paypal_plus_inactive'))) ? true : false);
		
		// ===== Verificar se os meios de pagamento estão ativos.
		
		if($app_installed && $app_active){
			// ===== Valores principais.
			
			$subtotal = 0;
			$descontos = 0;
			$total = 0;
			$quantidadeTotal = 0;
			
			// ===== Pedidos no banco de dados.
			
			$pedidos = banco_select(Array(
				'unico' => true,
				'tabela' => 'pedidos',
				'campos' => Array(
					'id_hosts_pedidos',
				),
				'extra' => 
					"WHERE id_hosts_usuarios='".$_GESTOR['usuario-id']."'"
					." AND codigo='".$codigo."'"
			));
			
			// ===== Montar pedidos.
			
			if($pedidos){
				// ===== Células.
				
				$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
				$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
				
				$cel_nome = 'cel-resumo'; $cel[$cel_nome] = pagina_celula($cel_nome);
				
				$cel_nome = 'pagamento-gratuito'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'pagamento-inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				
				if($paypal_plus_inactive){
					$cel_nome = 'menu-ppp-ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
					$cel_nome = 'btns-ppp-ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				} else {
					$cel_nome = 'menu-ppp-inativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				}
				
				// ===== Pedido serviços.
				
				$pedidos_servicos = banco_select(Array(
					'tabela' => 'pedidos_servicos',
					'campos' => Array(
						'nome',
						'preco',
						'quantidade',
					),
					'extra' => 
						"WHERE id_hosts_pedidos='".$pedidos['id_hosts_pedidos']."'"
				));
				
				if($pedidos_servicos){
					foreach($pedidos_servicos as $pedServ){
						// ===== Calcular subtotal.
						
						$quantidadeTotal += (int)$pedServ['quantidade'];
						$pedServ['subtotal'] = (float)$pedServ['preco'] * (int)$pedServ['quantidade'];
						$subtotal += $pedServ['subtotal'];
						
						// ===== Montar a célula do resumo.
						
						$cel_nome = 'cel-resumo';
						
						$cel_aux = $cel[$cel_nome];

						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$pedServ['quantidade'].'x '.$pedServ['nome']);
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$pedServ['subtotal']));
						
						pagina_celula_incluir($cel_nome,$cel_aux);
					}
					
					// ===== Remover marcadores.
					
					$cel_nome = 'cel-resumo'; pagina_celula_incluir($cel_nome,'');
					
					pagina_trocar_variavel_valor('<!-- cont-resumo < -->','',true);
					pagina_trocar_variavel_valor('<!-- cont-resumo > -->','',true);
				}
				
				// ===== Pedido variação de serviços.
				
				$pedidos_servico_variacoes = banco_select(Array(
					'tabela' => 'pedidos_servico_variacoes',
					'campos' => Array(
						'nome_servico',
						'nome_lote',
						'nome_variacao',
						'preco',
						'quantidade',
					),
					'extra' => 
						"WHERE id_hosts_pedidos='".$pedidos['id_hosts_pedidos']."'"
				));
				
				if($pedidos_servico_variacoes){
					foreach($pedidos_servico_variacoes as $pedServ){
						// ===== Calcular subtotal.
						
						$quantidadeTotal += (int)$pedServ['quantidade'];
						$pedServ['subtotal'] = (float)$pedServ['preco'] * (int)$pedServ['quantidade'];
						$subtotal += $pedServ['subtotal'];
						
						// ===== Montar a célula do resumo.
						
						$cel_nome = 'cel-resumo';
						
						$cel_aux = $cel[$cel_nome];

						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",$pedServ['quantidade'].'x '.$pedServ['nome_servico'].'<div class="sub header">'.$pedServ['nome_lote'].' - '.$pedServ['nome_variacao'].'</div>');
						$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-subtotal",formato_dado_para('float-para-texto',$pedServ['subtotal']));
						
						pagina_celula_incluir($cel_nome,$cel_aux);
					}
					
					// ===== Remover marcadores.
					
					$cel_nome = 'cel-resumo'; pagina_celula_incluir($cel_nome,'');
					
					pagina_trocar_variavel_valor('<!-- cont-resumo < -->','',true);
					pagina_trocar_variavel_valor('<!-- cont-resumo > -->','',true);
				}
				
				$cel_nome = 'cel-resumo'; $cel[$cel_nome] = pagina_celula($cel_nome);
				
				// ===== Identificador do pedido.
				
				$JSpagamento['codigo'] = $codigo;
				
				if($app_installed){ 			$JSpagamento['app_installed'] = true; }
				if($app_active){ 				$JSpagamento['app_active'] = true; }
				if($app_live){ 					$JSpagamento['app_live'] = true; }
				if($paypal_plus_inactive){ 		$JSpagamento['paypal_plus_inactive'] = true; }
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
			
			// ===== Formulário validação.
			
			formulario_validacao(Array(
				'formId' => 'formOutroPagador',
				'validacao' => Array(
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'nome',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-nome-label')),
					),
					Array(
						'regra' => 'email',
						'campo' => 'email',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
					),
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'cpf',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-cpf-label')),
					),
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'cnpj',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-cnpj-label')),
					),
					Array(
						'regra' => 'texto-obrigatorio',
						'campo' => 'telefone',
						'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-telefone-label')),
					),
				)
			));
		} else {
			// ===== Células.
			
			$cel_nome = 'step'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
			$cel_nome = 'step-mobile'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
			
			$cel_nome = 'pagamento-gratuito'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			$cel_nome = 'pagamento-ativo'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
		}
	}
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step',$cel['step']);
	layout_trocar_variavel_valor('layout#step-mobile',$cel['step-mobile']);
	
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
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('modulos');
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['pagamento'] = $JSpagamento;
}

// ==== Ajax

function pagamento_ajax_ppplus_log(){
	global $_GESTOR;
	
	$codigo = banco_escape_field($_REQUEST['codigo']);
	$msg = banco_escape_field($_REQUEST['msg']);
	$erro = $_REQUEST['erro'];
	
	// ===== Chamada da API-Servidor para atualizar dados no servidor.
	
	gestor_incluir_biblioteca('api-servidor');
	
	$retorno = api_servidor_pagamento(Array(
		'opcao' => 'log',
		'codigo' => $codigo,
		'msg' => $msg,
		'erro' => $erro,
		'id_hosts_usuarios' => $_GESTOR['usuario-id'],
	));
	
	if(!$retorno['completed']){
		switch($retorno['status']){
			//case 'OPCAO': $alerta = $retorno['error-msg']; break;
			default:
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
		}
		
		$_GESTOR['ajax-json'] = Array(
			'status' => 'API_ERROR',
			'msg' => $alerta,
		);
	} else {
		$_GESTOR['ajax-json'] = Array(
			'status' => 'OK',
		);
	}
}

function pagamento_ajax_ppplus_criar_pagamento(){
	global $_GESTOR;
	
	$codigo = banco_escape_field($_REQUEST['codigo']);
	
	// ===== Chamada da API-Servidor para atualizar dados no servidor.
	
	gestor_incluir_biblioteca('api-servidor');
	
	$retorno = api_servidor_pagamento(Array(
		'opcao' => 'paypalplus-criar',
		'codigo' => $codigo,
		'outroPagador' => (isset($_REQUEST['outroPagador']) ? $_REQUEST['outroPagador'] : 'nao'),
		'botao' => (isset($_REQUEST['botao']) ? 'sim' : 'nao'),
		'id_hosts_usuarios' => $_GESTOR['usuario-id'],
	));
	
	if(!$retorno['completed']){
		switch($retorno['status']){
			case 'STATUS_INVALID': 
				$status = $retorno['status'];
				
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
				
				gestor_incluir_biblioteca('interface');
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			break;
			default:
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
		}
		
		$_GESTOR['ajax-json'] = Array(
			'status' => (isset($status) ? $status : 'API_ERROR'),
			'msg' => $alerta,
		);
	} else {
		// ===== Atualizar o pedido localmente.
		
		if($retorno['data']['pedido']){
			$pedidos = banco_select(Array(
				'unico' => true,
				'tabela' => 'pedidos',
				'campos' => Array(
					'id_pedidos',
				),
				'extra' => 
					"WHERE id_hosts_pedidos='".$retorno['data']['pedido']['id_hosts_pedidos']."'"
			));
			
			// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
			
			if($pedidos){
				$pedido = $retorno['data']['pedido'];
				
				foreach($pedido as $campo => $valor){
					switch($campo){
						case 'live':
							banco_update_campo($campo,(existe($valor) ? $valor : 'NULL'),true);
						break;
						default:
							banco_update_campo($campo,$valor);
					}
				}
				
				banco_update_executar('pedidos',"WHERE id_hosts_pedidos='".$retorno['data']['pedido']['id_hosts_pedidos']."'");
			}
		}
		
		// ===== Retornar ok e o ppplus.
		
		$_GESTOR['ajax-json'] = Array(
			'ppplus' => $retorno['data']['ppplus'],
			'status' => 'OK',
		);
	}
}

function pagamento_ajax_ppplus_pagar(){
	global $_GESTOR;
	
	$codigo = banco_escape_field($_REQUEST['codigo']);
	$pay_id = banco_escape_field($_REQUEST['pay_id']);
	$payerID = banco_escape_field($_REQUEST['payerID']);
	$rememberedCard = banco_escape_field($_REQUEST['rememberedCard']);
	$installmentsValue = banco_escape_field($_REQUEST['installmentsValue']);
	
	// ===== Chamada da API-Servidor para atualizar dados no servidor.
	
	gestor_incluir_biblioteca('api-servidor');
	
	$retorno = api_servidor_pagamento(Array(
		'opcao' => 'paypalplus-pagar',
		'codigo' => $codigo,
		'pay_id' => $pay_id,
		'payerID' => $payerID,
		'rememberedCard' => $rememberedCard,
		'installmentsValue' => $installmentsValue,
		'paypalButton' => (isset($_REQUEST['paypalButton']) ? $_REQUEST['paypalButton'] : 'nao'),
		'outroPagador' => (isset($_REQUEST['outroPagador']) ? $_REQUEST['outroPagador'] : 'nao'),
		'id_hosts_usuarios' => $_GESTOR['usuario-id'],
	));
	
	if(!$retorno['completed']){
		switch($retorno['status']){
			//case 'OPCAO': $alerta = $retorno['error-msg']; break;
			default:
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
		}
		
		$_GESTOR['ajax-json'] = Array(
			'status' => 'API_ERROR',
			'msg' => $alerta,
		);
	} else {
		// ===== Atualizar o pedido localmente.
		
		if($retorno['data']['pedido']){
			$pedidos = banco_select(Array(
				'unico' => true,
				'tabela' => 'pedidos',
				'campos' => Array(
					'id_pedidos',
				),
				'extra' => 
					"WHERE id_hosts_pedidos='".$retorno['data']['pedido']['id_hosts_pedidos']."'"
			));
			
			// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
			
			if($pedidos){
				$pedido = $retorno['data']['pedido'];
				
				foreach($pedido as $campo => $valor){
					switch($campo){
						case 'live':
							banco_update_campo($campo,(existe($valor) ? $valor : 'NULL'),true);
						break;
						default:
							banco_update_campo($campo,$valor);
					}
				}
				
				banco_update_executar('pedidos',"WHERE id_hosts_pedidos='".$retorno['data']['pedido']['id_hosts_pedidos']."'");
			}
		}
		
		// ===== Retornar ok e alertar.
		
		gestor_incluir_biblioteca('interface');
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $retorno['data']['alerta']
		));
		
		$_GESTOR['ajax-json'] = Array(
			'pending' => $retorno['data']['pending'],
			'status' => 'OK',
		);
	}
}

function pagamento_ajax_pedido_gratuito_processar(){
	global $_GESTOR;
	
	$codigo = banco_escape_field($_REQUEST['codigo']);
	
	// ===== Chamada da API-Servidor para atualizar dados no servidor.
	
	gestor_incluir_biblioteca('api-servidor');
	
	$retorno = api_servidor_pagamento(Array(
		'opcao' => 'pedido-gratuito-processar',
		'codigo' => $codigo,
		'id_hosts_usuarios' => $_GESTOR['usuario-id'],
	));
	
	if(!$retorno['completed']){
		switch($retorno['status']){
			case 'STATUS_INVALID': 
				$status = $retorno['status'];
				
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
				
				gestor_incluir_biblioteca('interface');
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			break;
			default:
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
		}
		
		$_GESTOR['ajax-json'] = Array(
			'status' => (isset($status) ? $status : 'API_ERROR'),
			'msg' => $alerta,
		);
	} else {
		// ===== Atualizar o pedido localmente.
		
		if($retorno['data']['pedido']){
			$pedidos = banco_select(Array(
				'unico' => true,
				'tabela' => 'pedidos',
				'campos' => Array(
					'id_pedidos',
				),
				'extra' => 
					"WHERE id_hosts_pedidos='".$retorno['data']['pedido']['id_hosts_pedidos']."'"
			));
			
			// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
			
			if($pedidos){
				$pedido = $retorno['data']['pedido'];
				
				foreach($pedido as $campo => $valor){
					switch($campo){
						case 'live':
							banco_update_campo($campo,(existe($valor) ? $valor : 'NULL'),true);
						break;
						default:
							banco_update_campo($campo,$valor);
					}
				}
				
				banco_update_executar('pedidos',"WHERE id_hosts_pedidos='".$retorno['data']['pedido']['id_hosts_pedidos']."'");
			}
		}
		
		// ===== Retornar ok e alertar.
		
		gestor_incluir_biblioteca('interface');
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $retorno['data']['alerta']
		));
		
		$_GESTOR['ajax-json'] = Array(
			'status' => 'OK',
		);
	}
}

function pagamento_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function pagamento_start(){
	global $_GESTOR;
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'ppplus-log': pagamento_ajax_ppplus_log(); break;
			case 'ppplus-criar-pagamento': pagamento_ajax_ppplus_criar_pagamento(); break;
			case 'ppplus-pagar': pagamento_ajax_ppplus_pagar(); break;
			case 'pedido-gratuito-processar': pagamento_ajax_pedido_gratuito_processar(); break;
			default: pagamento_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': pagamento_opcao(); break;
			default: pagamento_padrao();
		}
	}
}

pagamento_start();

?>