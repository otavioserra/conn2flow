<?php

// =========================== Configuração Inicial

$_GESTOR										=	Array();
$_CRON											=	Array();

$_GESTOR['bibliotecas']							=	Array('banco','gestor');

// ===== Configurações pré-inclusão do config.

$debug = false;
$server = 'beta.entrey.com.br';
$plataforma_id = 'beta';

// ===== Parâmetros passados no command line.

for($i=1;$i<$argc;$i++){
	switch($argv[$i]){
		case 'debug': $debug = true; break;
	}
	
	if(preg_match('/'.preg_quote('server=').'/i', $argv[$i]) > 0){
		$server = preg_replace('/'.preg_quote('server=').'/i', '', $argv[$i]);
	}
	
	if(preg_match('/'.preg_quote('plataforma=').'/i', $argv[$i]) > 0){
		$plataforma_id = preg_replace('/'.preg_quote('plataforma=').'/i', '', $argv[$i]);
	}
}

// ===== Ativar debug por request.

if($debug){
	$_CRON['DEBUG'] = true;
} else {
	$_CRON['DEBUG'] = false;
}

// ===== Forçar variáveis globais SERVER.

$_CRON['SERVER_NAME'] = $server;
$_CRON['PLATAFORMA_ID'] = $plataforma_id;
$_CRON['ROOT_PATH'] = preg_replace('/'.preg_quote('cron.php').'/i', '', $_SERVER['SCRIPT_FILENAME']);

// ===== Inclusão da configuração principal.

require_once('config.php');

// ===== Funções auxiliares.

// ===== Erros e log.

function cron_error_handler($errno, $errstr, $errfile, $errline){
	switch($errno){
		case E_ERROR:				$errConstStr = 'E_ERROR'; break;
		case E_WARNING:				$errConstStr = 'E_WARNING'; break;
		case E_PARSE:				$errConstStr = 'E_PARSE'; break;
		case E_NOTICE:				$errConstStr = 'E_NOTICE'; break;
		case E_CORE_ERROR:			$errConstStr = 'E_CORE_ERROR'; break;
		case E_CORE_WARNING:		$errConstStr = 'E_CORE_WARNING'; break;
		case E_STRICT:				$errConstStr = 'E_STRICT'; break;
		case E_RECOVERABLE_ERROR:	$errConstStr = 'E_RECOVERABLE_ERROR'; break;
		case E_DEPRECATED:			$errConstStr = 'E_DEPRECATED'; break;
		case E_USER_DEPRECATED:		$errConstStr = 'E_USER_DEPRECATED'; break;
		case E_USER_ERROR:			$errConstStr = 'E_USER_ERROR'; break;
		case E_USER_WARNING: 		$errConstStr = 'E_USER_WARNING'; break;
		case E_USER_NOTICE: 		$errConstStr = 'E_USER_NOTICE'; break;
		case E_ALL: 				$errConstStr = 'E_ALL'; break;
		default:
			$errConstStr = 'UNKNOW';
    }
	
	cron_log('['.$errConstStr.'] '.$errfile.':'.$errline.' - '.$errstr);
	
    switch($errno){
		case E_USER_ERROR:
		case E_ERROR:
			exit(1);
		break;
		case E_USER_WARNING:
			
		break;
		case E_USER_NOTICE:
			
		break;
		default:
			
    }

    /* Don't execute PHP internal error handler */
    return true;
}

function cron_log($msg){
	global $_CRON;
	
	$msg = '['.date('D, d M Y H:i:s').'] '.$msg;
	
	if($_CRON['DEBUG']){
		echo $msg . "\n";
	} else {
		$myFile = $_CRON['ROOT_PATH'] . "logs/cron-".date('d-m-Y').".log";
		
		if(file_exists($myFile) && filesize($myFile) > 0){
			$file = file_get_contents($myFile);
		}
		
		file_put_contents($myFile,($file ? $file : '') . $msg . "\n");
	}
}

set_error_handler("cron_error_handler");

// ===== Interfaces.

function cron_carrinhos_abandonados(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Serviços dos carrinhos.
	
	$servicosCarrinhos = Array();
	$variacoesServicosCarrinhos = Array();
	
	// ===== Pegar carrinhos expirados de todos os hosts.
	
	$hosts_carrinho = banco_select(Array(
		'tabela' => 'hosts_carrinho',
		'campos' => Array(
			'id_hosts_carrinho',
			'id_hosts',
		),
		'extra' => 
			"WHERE UNIX_TIMESTAMP(`data_modificacao`) + ".$_CONFIG['session-lifetime']." < ".time()
	));
	
	if($hosts_carrinho)
	foreach($hosts_carrinho as $carrinho){
		// ===== Pegar quantidade de serviços do carrinho.
		
		$hosts_carrinho_servicos = banco_select(Array(
			'tabela' => 'hosts_carrinho_servicos',
			'campos' => Array(
				'id_hosts_servicos',
				'quantidade',
			),
			'extra' => 
				"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		));
		
		if($hosts_carrinho_servicos)
		foreach($hosts_carrinho_servicos as $carrSer){
			// ===== Se a quantidade for maior que zero, somar com a quantidade total de um serviço específico.
			
			if((int)$carrSer['quantidade'] > 0){
				// ===== Verificar se já foi definido valor, se sim incluir em quantidade para somar com o valor atual.
				
				if(isset($servicosCarrinhos[$carrSer['id_hosts_servicos']])){
					$quantidade = $servicosCarrinhos[$carrSer['id_hosts_servicos']];
				} else {
					$quantidade = 0;
				}
				
				$servicosCarrinhos[$carrSer['id_hosts_servicos']] = $quantidade + (int)$carrSer['quantidade'];
			}
		}
		
		// ===== Pegar quantidade de variações do serviços do carrinho.
		
		$hosts_carrinho_servico_variacoes = banco_select(Array(
			'tabela' => 'hosts_carrinho_servico_variacoes',
			'campos' => Array(
				'id_hosts_servicos_variacoes',
				'quantidade',
			),
			'extra' => 
				"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		));
		
		if($hosts_carrinho_servico_variacoes)
		foreach($hosts_carrinho_servico_variacoes as $carrSer){
			// ===== Se a quantidade for maior que zero, somar com a quantidade total de uma variação de serviço específico.
			
			if((int)$carrSer['quantidade'] > 0){
				// ===== Verificar se já foi definido valor, se sim incluir em quantidade para somar com o valor atual.
				
				if(isset($variacoesServicosCarrinhos[$carrSer['id_hosts_servicos_variacoes']])){
					$quantidade = $variacoesServicosCarrinhos[$carrSer['id_hosts_servicos_variacoes']];
				} else {
					$quantidade = 0;
				}
				
				$variacoesServicosCarrinhos[$carrSer['id_hosts_servicos_variacoes']] = $quantidade + (int)$carrSer['quantidade'];
			}
		}
		
		// ===== Excluir variações dos serviços dos carrinhos abandonados.
		
		banco_delete
		(
			"hosts_carrinho_servico_variacoes",
			"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		);
		
		// ===== Excluir serviços dos carrinhos abandonados.
		
		banco_delete
		(
			"hosts_carrinho_servicos",
			"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		);
		
		// ===== Excluir carrinhos abandonados.
		
		banco_delete
		(
			"hosts_carrinho",
			"WHERE id_hosts_carrinho='".$carrinho['id_hosts_carrinho']."'"
		);
	}
	
	// ===== Caso seja necessário, retornar valores ao estoque de cada serviço para todos os hosts.
	
	if(count($servicosCarrinhos) > 0){
		$hostServicos = Array();
		
		foreach($servicosCarrinhos as $id_hosts_servicos => $quantidadeEstoqueAlterar){
			if($quantidadeEstoqueAlterar > 0){
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'quantidade',
						'quantidade_carrinhos',
						'id_hosts',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				));
				
				// ===== Quantidade para alterar no estoque início.
				
				$estoqueQuantidade = (int)$hosts_servicos['quantidade'];
				$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
				$id_hosts = $hosts_servicos['id_hosts'];
				
				banco_update
				(
					"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
					."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
					"hosts_servicos",
					"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				);
				
				// ===== Valor para atualizar de cada serviço em cada host.
				
				$hostServicos[$id_hosts][$id_hosts_servicos] = $estoqueQuantidade + $quantidadeEstoqueAlterar;
			}
		}
		
		if(count($hostServicos) > 0){
			// ===== Incluir os dados no host de cada cliente.
			
			gestor_incluir_biblioteca('api-cliente');
			
			foreach($hostServicos as $id_hosts => $servicos){
				// ===== Conectar via API-Cliente em cada host e atualizar os estoques de todos os serviços.
				
				$retorno = api_cliente_cron_servicos(Array(
					'opcao' => 'quantidade',
					'id_hosts' => $id_hosts,
					'servicos' => $servicos,
				));
				
				// ===== Caso haja algum erro, incluir no log do cron.
				
				if(!$retorno['completed']){
					cron_log(
						'FUNCAO: cron_carrinhos_abandonados[servicos]'."\n".
						'ID-HOST: '.$id_hosts."\n".
						'ERROR-MSG: '."\n".
						$retorno['error-msg']
					);
				}
			}
		}
	}
	
	// ===== Caso seja necessário, retornar valores ao estoque de cada variação de serviço para todos os hosts.
	
	if(count($variacoesServicosCarrinhos) > 0){
		$hostVariacoesServicos = Array();
		
		foreach($variacoesServicosCarrinhos as $id_hosts_servicos_variacoes => $quantidadeEstoqueAlterar){
			if($quantidadeEstoqueAlterar > 0){
				$hosts_servicos_variacoes = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos_variacoes',
					'campos' => Array(
						'quantidade',
						'quantidade_carrinhos',
						'id_hosts',
						'id_hosts_servicos',
					),
					'extra' => 
						"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
				));
				
				// ===== Quantidade para alterar no estoque início.
				
				$estoqueQuantidade = (int)$hosts_servicos_variacoes['quantidade'];
				$carrinhosQuantidade = (int)$hosts_servicos_variacoes['quantidade_carrinhos'];
				$id_hosts = $hosts_servicos_variacoes['id_hosts'];
				
				banco_update
				(
					"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
					."quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
					"hosts_servicos_variacoes",
					"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
				);
				
				// ===== Valor para atualizar de cada serviço em cada host.
				
				$hostVariacoesServicos[$id_hosts][$id_hosts_servicos_variacoes] = $estoqueQuantidade + $quantidadeEstoqueAlterar;
				
				// ===== Pegar quantidade em carrinho do serviço.
				
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'quantidade_carrinhos',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$hosts_servicos_variacoes['id_hosts_servicos']."'"
				));
				
				$carrinhosQuantidade = (int)$hosts_servicos['quantidade_carrinhos'];
				
				banco_update
				(
					"quantidade_carrinhos=".$carrinhosQuantidade." - ".$quantidadeEstoqueAlterar,
					"hosts_servicos",
					"WHERE id_hosts_servicos='".$hosts_servicos_variacoes['id_hosts_servicos']."'"
				);
			}
		}
		
		if(count($hostVariacoesServicos) > 0){
			// ===== Incluir os dados no host de cada cliente.
			
			gestor_incluir_biblioteca('api-cliente');
			
			foreach($hostVariacoesServicos as $id_hosts => $variacaoServicos){
				// ===== Conectar via API-Cliente em cada host e atualizar os estoques de todos os serviços.
				
				$retorno = api_cliente_cron_servicos(Array(
					'opcao' => 'quantidadeVariacao',
					'id_hosts' => $id_hosts,
					'variacaoServicos' => $variacaoServicos,
				));
				
				// ===== Caso haja algum erro, incluir no log do cron.
				
				if(!$retorno['completed']){
					cron_log(
						'FUNCAO: cron_carrinhos_abandonados[servicos-variacoes]'."\n".
						'ID-HOST: '.$id_hosts."\n".
						'ERROR-MSG: '."\n".
						$retorno['error-msg']
					);
				}
			}
		}
	}
}

function cron_pedidos_abandonados(){
	global $_GESTOR;
	
	// ===== Serviços dos pedidos.
	
	$servicosPedidos = Array();
	$variacoesServicosPedidos = Array();
	$atualizarPedidos = Array();
	
	// ===== Pegar configurações do PayPal.
	
	$config = gestor_incluir_configuracao(Array(
		'id' => 'paypal.config',
	));
	
	$expiracaoPagamentoHoras = $config['expiracao-pagamento-horas'];
	
	// ===== Pegar pedidos expirados de todos os hosts.
	
	$hosts_pedidos = banco_select(Array(
		'tabela' => 'hosts_pedidos',
		'campos' => Array(
			'id_hosts_pedidos',
			'id_hosts',
		),
		'extra' => 
			"WHERE UNIX_TIMESTAMP(`data_criacao`) + ".(3600 * $expiracaoPagamentoHoras)." < ".time()
			." AND (status='novo' OR status='aguardando-pagamento')"
	));
	
	if($hosts_pedidos){
		foreach($hosts_pedidos as $pedido){
			// ===== Identificador do pedido e do host.
			
			$id_hosts = $pedido['id_hosts'];
			$id_hosts_pedidos = $pedido['id_hosts_pedidos'];
			
			// ===== Atualizar pedidos no host.
			
			$atualizarPedidos[$id_hosts][] = $id_hosts_pedidos;
			
			// ===== Pegar quantidade de serviços do pedido.
			
			$hosts_pedidos_servicos = banco_select(Array(
				'tabela' => 'hosts_pedidos_servicos',
				'campos' => Array(
					'id_hosts_servicos',
					'quantidade',
				),
				'extra' => 
					"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
			));
			
			if($hosts_pedidos_servicos)
			foreach($hosts_pedidos_servicos as $pedidoSer){
				// ===== Se a quantidade for maior que zero, somar com a quantidade total de um serviço específico.
				
				if((int)$pedidoSer['quantidade'] > 0){
					// ===== Verificar se já foi definido valor, se sim incluir em quantidade para somar com o valor atual.
					
					if(isset($servicosPedidos[$pedidoSer['id_hosts_servicos']])){
						$quantidade = $servicosPedidos[$pedidoSer['id_hosts_servicos']];
					} else {
						$quantidade = 0;
					}
					
					$servicosPedidos[$pedidoSer['id_hosts_servicos']] = $quantidade + (int)$pedidoSer['quantidade'];
				}
			}
			
			// ===== Pegar quantidade de variações dos serviços do pedido.
			
			$hosts_pedidos_servico_variacoes = banco_select(Array(
				'tabela' => 'hosts_pedidos_servico_variacoes',
				'campos' => Array(
					'id_hosts_servicos',
					'id_hosts_servicos_variacoes',
					'quantidade',
				),
				'extra' => 
					"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
			));
			
			if($hosts_pedidos_servico_variacoes)
			foreach($hosts_pedidos_servico_variacoes as $pedidoSer){
				// ===== Se a quantidade for maior que zero, somar com a quantidade total de uma variação de um serviço específico.
				
				if((int)$pedidoSer['quantidade'] > 0){
					// ===== Verificar se já foi definido valor, se sim incluir em quantidade para somar com o valor atual.
					
					if(isset($variacoesServicosPedidos[$pedidoSer['id_hosts_servicos_variacoes']])){
						$quantidade = $variacoesServicosPedidos[$pedidoSer['id_hosts_servicos_variacoes']];
					} else {
						$quantidade = 0;
					}
					
					$variacoesServicosPedidos[$pedidoSer['id_hosts_servicos_variacoes']] = $quantidade + (int)$pedidoSer['quantidade'];
				}
			}
			
			// ===== Atualizar estado do pedido.
			
			banco_update
			(
				"data_modificacao=NOW(),".
				"versao=versao+1,".
				"status='expirado'",
				"hosts_pedidos",
				"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
				." AND id_hosts='".$id_hosts."'"
			);
			
			// ===== Incluir o histórico da alteração no pedido.
			
			$statusTitulo = gestor_variaveis(Array('modulo' => 'gateways-de-pagamentos','id' => 'status-expired'));
			
			gestor_incluir_biblioteca('log');
			
			log_controladores(Array(
				'id_hosts' => $id_hosts,
				'controlador' => 'cron',
				'id' => $id_hosts_pedidos,
				'tabela' => Array(
					'nome' => 'hosts_pedidos',
					'versao' => 'versao',
					'id_numerico' => 'id_hosts_pedidos',
				),
				'alteracoes' => Array(
					Array(
						'modulo' => 'pedidos',
						'alteracao' => 'order-expired',
						'alteracao_txt' => 'Alteração do status para: <b>'.$statusTitulo.'</b>',
					)
				),
			));
		}
	}
	
	// ===== Caso seja necessário, retornar valores ao estoque de cada serviço para todos os hosts.
	
	if(count($servicosPedidos) > 0){
		$hostServicos = Array();
		
		foreach($servicosPedidos as $id_hosts_servicos => $quantidadeEstoqueAlterar){
			if($quantidadeEstoqueAlterar > 0){
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'quantidade',
						'quantidade_pedidos_pendentes',
						'id_hosts',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				));
				
				// ===== Quantidade para alterar no estoque início.
				
				$estoqueQuantidade = (int)$hosts_servicos['quantidade'];
				$pedidosPendentesQuantidade = (int)$hosts_servicos['quantidade_pedidos_pendentes'];
				$id_hosts = $hosts_servicos['id_hosts'];
				
				banco_update
				(
					"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
					."quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." - ".$quantidadeEstoqueAlterar,
					"hosts_servicos",
					"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				);
				
				// ===== Valor para atualizar de cada serviço em cada host.
				
				$hostServicos[$id_hosts][$id_hosts_servicos] = $estoqueQuantidade + $quantidadeEstoqueAlterar;
			}
		}
		
		if(count($hostServicos) > 0){
			// ===== Incluir os dados no host de cada cliente.
			
			gestor_incluir_biblioteca('api-cliente');
			
			foreach($hostServicos as $id_hosts => $servicos){
				// ===== Conectar via API-Cliente em cada host e atualizar os estoques de todos os serviços.
				
				$retorno = api_cliente_cron_servicos(Array(
					'opcao' => 'quantidade',
					'id_hosts' => $id_hosts,
					'servicos' => $servicos,
				));
				
				// ===== Caso haja algum erro, incluir no log do cron.
				
				if(!$retorno['completed']){
					cron_log(
						'FUNCAO: cron_pedidos_abandonados[servicos-pedidos]'."\n".
						'ID-HOST: '.$id_hosts."\n".
						'ERROR-MSG: '."\n".
						$retorno['error-msg']
					);
				}
			}
		}
	}
	
	// ===== Caso seja necessário, retornar valores ao estoque de cada variação de serviço para todos os hosts.
	
	if(count($variacoesServicosPedidos) > 0){
		$hostVariacoesServicos = Array();
		
		foreach($variacoesServicosPedidos as $id_hosts_servicos_variacoes => $quantidadeEstoqueAlterar){
			if($quantidadeEstoqueAlterar > 0){
				$hosts_servicos_variacoes = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos_variacoes',
					'campos' => Array(
						'quantidade',
						'quantidade_pedidos_pendentes',
						'id_hosts',
						'id_hosts_servicos',
					),
					'extra' => 
						"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
				));
				
				// ===== Quantidade para alterar no estoque início.
				
				$estoqueQuantidade = (int)$hosts_servicos_variacoes['quantidade'];
				$pedidosPendentesQuantidade = (int)$hosts_servicos_variacoes['quantidade_pedidos_pendentes'];
				$id_hosts = $hosts_servicos_variacoes['id_hosts'];
				
				banco_update
				(
					"quantidade=".$estoqueQuantidade." + ".$quantidadeEstoqueAlterar.","
					."quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." - ".$quantidadeEstoqueAlterar,
					"hosts_servicos_variacoes",
					"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
				);
				
				// ===== Valor para atualizar de cada serviço em cada host.
				
				$hostVariacoesServicos[$id_hosts][$id_hosts_servicos_variacoes] = $estoqueQuantidade + $quantidadeEstoqueAlterar;
				
				// ===== Pegar quantidade em carrinho do serviço.
				
				$hosts_servicos = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_servicos',
					'campos' => Array(
						'quantidade_pedidos_pendentes',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$hosts_servicos_variacoes['id_hosts_servicos']."'"
				));
				
				$pedidosPendentesQuantidade = (int)$hosts_servicos['quantidade_pedidos_pendentes'];
				
				banco_update
				(
					"quantidade_pedidos_pendentes=".$pedidosPendentesQuantidade." - ".$quantidadeEstoqueAlterar,
					"hosts_servicos",
					"WHERE id_hosts_servicos='".$hosts_servicos_variacoes['id_hosts_servicos']."'"
				);
			}
		}
		
		if(count($hostVariacoesServicos) > 0){
			// ===== Incluir os dados no host de cada cliente.
			
			gestor_incluir_biblioteca('api-cliente');
			
			foreach($hostVariacoesServicos as $id_hosts => $variacaoServicos){
				// ===== Conectar via API-Cliente em cada host e atualizar os estoques de todos os serviços.
				
				$retorno = api_cliente_cron_servicos(Array(
					'opcao' => 'quantidadeVariacao',
					'id_hosts' => $id_hosts,
					'variacaoServicos' => $variacaoServicos,
				));
				
				// ===== Caso haja algum erro, incluir no log do cron.
				
				if(!$retorno['completed']){
					cron_log(
						'FUNCAO: cron_pedidos_abandonados[servicos-pedidos-variacoes]'."\n".
						'ID-HOST: '.$id_hosts."\n".
						'ERROR-MSG: '."\n".
						$retorno['error-msg']
					);
				}
			}
		}
	}
	
	// ===== Atualizar pedidos nos hosts.
	
	if(count($atualizarPedidos) > 0){
		// ===== Incluir os dados no host de cada cliente.
		
		gestor_incluir_biblioteca('api-cliente');
		
		foreach($atualizarPedidos as $id_hosts => $pedidos){
			// ===== Conectar via API-Cliente em cada host e atualizar os pedidos.
			
			$retorno = api_cliente_cron_pedidos(Array(
				'opcao' => 'atualizar',
				'id_hosts' => $id_hosts,
				'pedidos' => $pedidos,
				'config' => gestor_incluir_configuracao(Array(
					'id' => 'pedidos.config',
				)),
			));
			
			// ===== Caso haja algum erro, incluir no log do cron.
			
			if(!$retorno['completed']){
				cron_log(
					'FUNCAO: cron_pedidos_abandonados[atualizar-pedidos]'."\n".
					'ID-HOST: '.$id_hosts."\n".
					'ERROR-MSG: '."\n".
					$retorno['error-msg']
				);
			}
		}
	}
}

function cron_vouchers_expirados(){
	global $_GESTOR;
	
	// ===== Pegar pedidos expirados de todos os hosts.
	
	$hosts_pedidos = banco_select(Array(
		'tabela' => 'hosts_pedidos',
		'campos' => Array(
			'id_hosts_pedidos',
			'id_hosts',
		),
		'extra' => 
			"WHERE jwt_bd_expirado IS NULL"
			." AND (status='pago')"
			." AND UNIX_TIMESTAMP(`jwt_bd_expiracao`) < ".time()
	));
	
	if($hosts_pedidos){
		foreach($hosts_pedidos as $pedido){
			// ===== Identificador do pedido e do host.
			
			$id_hosts = $pedido['id_hosts'];
			$id_hosts_pedidos = $pedido['id_hosts_pedidos'];
			
			// ===== Vouchers de cada pedido.
			
			$hosts_vouchers = banco_select(Array(
				'tabela' => 'hosts_vouchers',
				'campos' => Array(
					'id_hosts_vouchers',
					'status',
				),
				'extra' => 
					"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
					." AND id_hosts='".$id_hosts."'"
			));
			
			if($hosts_vouchers)
			foreach($hosts_vouchers as $voucher){
				// ===== Deletar o JWT do banco de dados.
				
				banco_update_campo('jwt_bd','NULL',true);
				
				// ===== Verificar o status do voucher e só mudar status de vouchers não utilizados.
			
				if($voucher['status'] == 'jwt-gerado'){
					banco_update_campo('status','jwt-bd-expirado');
				}
				
				banco_update_executar('hosts_vouchers',"WHERE id_hosts_vouchers='".$voucher['id_hosts_vouchers']."'");
			}
			
			// ===== Marcar jwt_bd_expirado no pedido.
			
			banco_update_campo('jwt_bd_expirado','1',true);
			
			banco_update_executar('hosts_pedidos',"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'");
		}
	}
}

function cron_plugins(){
	global $_GESTOR;
	global $_CRON;
	
	// ===== Verificar quais hosts têm plugin habilitado.
	
	$hosts_plugins = banco_select(Array(
		'tabela' => 'hosts_plugins',
		'campos' => Array(
			'id_hosts',
			'plugin',
		),
		'extra' => 
			"WHERE habilitado IS NOT NULL"
	));
	
	if($hosts_plugins){
		// ===== Varrer todos os plugins e ver se o cron está habilitado em cada plugin.
		
		foreach($hosts_plugins as $host_plugin){
			if(!isset($pluginCron[$host_plugin['plugin']])){
				$pluginCron[$host_plugin['plugin']] = Array();
				
				// ===== Pegar os dados de configuração do plugin.
				
				$pluginID = $host_plugin['plugin'];
				
				$pluginConfig = require($_GESTOR['plugins-path'].$pluginID.'/config.php');
				
				// ===== Verificar se o cron está ativo no plugin.
				
				if(isset($pluginConfig['cronAtivo'])){
					if($pluginConfig['cronAtivo']){
						$pluginCron[$host_plugin['plugin']]['cronAtivo'] = true;
					}
				}
			}
			
			// ===== Caso o cron esteja ativo, incluir cada host habilitado.
			
			if(isset($pluginCron[$host_plugin['plugin']]['cronAtivo'])){
				$pluginCron[$host_plugin['plugin']]['hostsIDs'][] = $host_plugin['id_hosts'];
			}
		}
		
		// ===== Executar o cron de cada plugin.
		
		if(isset($pluginCron)){
			foreach($pluginCron as $pluginID => $plugin){
				if(isset($plugin['cronAtivo'])){
					$_GESTOR['pluginHostsIDs'] = $plugin['hostsIDs'];
					$cronPlugin = require_once($_GESTOR['plugins-path'].$pluginID.'/local/cron.php');
				}
			}
		}
	}
}

// ===== Principal.

function cron_pipeline(){
	cron_carrinhos_abandonados();
	cron_pedidos_abandonados();
	cron_vouchers_expirados();
	cron_plugins();
}

function cron_start(){
	global $argv;
	global $argc;
	global $_CRON;
	
	// ===== Buffer para log ao invés de direto no console.
	
	if($_CRON['DEBUG']){
		$bufferLog = false;
	} else {
		$bufferLog = true;
	}
	
	// ===== Iniciar o buffer de saída.
	
	if($bufferLog){
		ob_start();
	}
	
	// ===== Pipeline.
	
	cron_pipeline();
	
	// ===== Finalizar o buffer e salvar no log caso haja saída.
	
	if($bufferLog){
		$saidaBuffer = ob_get_contents();
		ob_end_clean();
		
		if(strlen($saidaBuffer) > 0){
			cron_log($str);
		}
	} else {
		echo 'Done!'."\n";
	}
}

cron_start();

?>