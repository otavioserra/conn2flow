<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'voucher';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.1.5',
);

// ===== Funções Auxiliares

// ===== Funções Principais

function voucher_padrao(){
	global $_GESTOR;
	
	// ===== Identificação do pedido.
	
	if(!isset($_REQUEST['pedido'])){
		$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-informar-pedido'));
		
		gestor_incluir_biblioteca('interface');
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
		
		gestor_redirecionar('minha-conta/');
	} else {
		$codigo = $_REQUEST['pedido'];
	}
	
	// ===== Iniciar as bibliotecas necessárias.
	
	gestor_incluir_biblioteca('pagina');
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('interface');
	
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
	
	// ===== Montar vouchers.
	
	if($pedidos){
		// ===== Conferir se o pedido foi pago.
		
		if($pedidos['status'] != 'pago'){
			$alerta = gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'alerta-pedido-nao-pago'));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('minha-conta/');
		}
		
		// ===== Verificar se vai haver reemissão.
		
		if(existe(gestor_sessao_variavel('voucher-reemitir'))){
			gestor_sessao_variavel_del('voucher-reemitir');
			$reemitir = 'sim';
		} else {
			$reemitir = 'nao';
		}
		
		// ===== Caso tenha sido selecionado a opção de reemissão.
		
		if(isset($_REQUEST['reemitir'])){
			gestor_sessao_variavel('voucher-reemitir','sim');
			gestor_redirecionar('voucher/?pedido='.$codigo);
		}
		
		// ===== Chamada da API-Servidor para pegar dados do servidor.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_voucher(Array(
			'opcao' => 'todos',
			'codigo' => $codigo,
			'reemitir' => $reemitir,
			'id_hosts_usuarios' => $_GESTOR['usuario-id'],
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				default:
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
			}
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('minha-conta/');
		} else {
			// ===== Dados do voucher retornado.
			
			$dataVouchers = $retorno['data']['vouchers'];
			$reemitiuPeloMenosUm = ($retorno['data']['reemitiuPeloMenosUm'] == 'sim' ? true : false);
			
			// ===== Atualizar os dados localmente dos vouchers.
			
			if($dataVouchers)
			foreach($dataVouchers as $id_hosts_vouchers => $voucher){
				banco_update_campo('status',$voucher['status']);
				if(existe($voucher['data_uso']))banco_update_campo('data_uso',$voucher['data_uso']);
				
				banco_update_executar('vouchers',"WHERE id_hosts_pedidos='".$pedidos['id_hosts_pedidos']."' AND id_hosts_vouchers='".$id_hosts_vouchers."'");
			}
			
			// ===== Variável dos vouchers JS.
			
			$vouchersDados = Array();
			
			// ===== Células.
			
			$cel_nome = 'vazio'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
			$cel_nome = 'expirado'; $cel[$cel_nome] = pagina_celula($cel_nome,true,true);
			
			$cel_nome = 'cel-voucher'; $cel[$cel_nome] = pagina_celula($cel_nome);
			$cel_nome = 'cel-voucher-static'; $cel[$cel_nome] = pagina_celula($cel_nome);
			
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
					'id_hosts_vouchers',
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
					"WHERE id_hosts_pedidos='".$pedidos['id_hosts_pedidos']."'"
			));
			
			// ===== Verificar se está expirado.
			
			if($retorno['data']['expirados']){
				$cel_nome = 'cont-vouchers'; $cel[$cel_nome] = pagina_celula($cel_nome);
				
				pagina_trocar_variavel_valor('<!-- cont-vouchers -->',$cel['expirado'],true);
				pagina_trocar_variavel_valor('pedido',$codigo);
			} else {
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
						
						// ===== Montar todos os vouchers deste serviço.
						
						if($vouchers)
						foreach($vouchers as $voucher){
							if($voucher['id_hosts_servicos'] == $pedServ['id_hosts_servicos'] && !$voucher['loteVariacao']){
								if($voucher['status'] == 'jwt-gerado'){
									// ===== Montar a célula do voucher.
									
									$cel_nome = 'cel-voucher';
									
									$cel_aux = $cel[$cel_nome];
									
									// ===== Dados do voucher.
									
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-id",$voucher['codigo']);
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$pedServ['imagem']);
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",'Voucher #'.$voucher['codigo'].': '.$pedServ['nome']);
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-qr-code",$dataVouchers[$voucher['id_hosts_vouchers']]['qrCodeImagem']);
									
									// ===== Dados de identidade.
									
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-nome",(existe($voucher['nome']) ? $voucher['nome'] : $usuario['nome']));
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-documento",(existe($voucher['documento']) ? $voucher['documento'] : $usuario['documento']));
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-telefone",(existe($voucher['telefone']) ? $voucher['telefone'] : $usuario['telefone']));
									
									pagina_celula_incluir($cel_nome,$cel_aux);
									
									// ===== Valores passados ao JS.
									
									$vouchersDados[$voucher['codigo']] = Array(
										'titulo' => 'Voucher #'.$voucher['codigo'].': '.$pedServ['nome'],
										'loteVariacao' => false,
									);
								}
							}
						}
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
						
						// ===== Montar todos os vouchers deste serviço.
						
						if($vouchers)
						foreach($vouchers as $voucher){
							if($voucher['id_hosts_servicos_variacoes'] == $pedServ['id_hosts_servicos_variacoes'] && $voucher['loteVariacao']){
								if($voucher['status'] == 'jwt-gerado'){
									// ===== Montar a célula do voucher.
									
									$cel_nome = 'cel-voucher';
									
									$cel_aux = $cel[$cel_nome];
									
									// ===== Dados do voucher.
									
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-id",$voucher['codigo']);
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-imagem",$pedServ['imagem']);
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"servico-nome",'Voucher #'.$voucher['codigo'].': '.$pedServ['nome_servico'].'<div class="sub header">'.$pedServ['nome_lote'].' - '.$pedServ['nome_variacao'].'</div>');
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-qr-code",$dataVouchers[$voucher['id_hosts_vouchers']]['qrCodeImagem']);
									
									// ===== Dados de identidade.
									
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-nome",(existe($voucher['nome']) ? $voucher['nome'] : $usuario['nome']));
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-documento",(existe($voucher['documento']) ? $voucher['documento'] : $usuario['documento']));
									$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"identificacao-telefone",(existe($voucher['telefone']) ? $voucher['telefone'] : $usuario['telefone']));
									
									pagina_celula_incluir($cel_nome,$cel_aux);
									
									// ===== Valores passados ao JS.
									
									$vouchersDados[$voucher['codigo']] = Array(
										'titulo' => 'Voucher #'.$voucher['codigo'].': '.$pedServ['nome_servico'],
										'subtitulo' => $pedServ['nome_lote'].' - '.$pedServ['nome_variacao'],
										'loteVariacao' => true,
									);
								}
							}
						}
					}
					
					// ===== Remover marcadores.
					
					$cel_nome = 'cel-voucher'; pagina_celula_incluir($cel_nome,'');
					
					pagina_trocar_variavel_valor('<!-- cel-voucher -->','',true);
					pagina_trocar_variavel_valor('<!-- cont-vouchers < -->','',true);
					pagina_trocar_variavel_valor('<!-- cont-vouchers > -->','',true);
				} else {
					pagina_trocar_variavel_valor('<!-- cel-voucher -->',$cel['vazio'],true);
				}
				
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
				
				formulario_validacao(Array(
					'formId' => 'formEnviarEmail',
					'validacao' => Array(
						Array(
							'regra' => 'email',
							'campo' => 'email',
							'label' => gestor_variaveis(Array('modulo' => 'loja-configuracoes','id' => 'identificacao-email-label')),
						),
					)
				));
			}
			
			// ===== Montar os vouchers sem ações.
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
				
				// ===== Montar todos os vouchers deste serviço.
				
				if($vouchers)
				foreach($vouchers as $voucher){
					if($voucher['id_hosts_servicos'] == $pedServ['id_hosts_servicos'] && !$voucher['loteVariacao']){
						if($voucher['status'] != 'jwt-gerado'){
							// ===== Montar a célula do voucher.
							
							$cel_nome = 'cel-voucher-static';
							
							$cel_aux = $cel[$cel_nome];
							
							// ===== Modificar o status do voucher.
							
							switch($voucher['status']){
								case 'jwt-bd-expirado':
									$voucherStatus = gestor_variaveis(Array('modulo' => 'pedidos-voucher-status','id' => 'disponivel'));
								break;
								case 'usado':
									$voucherStatus = gestor_variaveis(Array('modulo' => 'pedidos-voucher-status','id' => $voucher['status']));
									
									gestor_incluir_biblioteca('formato');
									
									$data_uso = formato_dado(Array(
										'valor' => $voucher['data_uso'],
										'tipo' => 'dataHora',
									));
									
									$voucherStatus .= '<span class="ui grey label">Data da baixa: '.$data_uso.'</span>';
								break;
								default:
									$voucherStatus = gestor_variaveis(Array('modulo' => 'pedidos-voucher-status','id' => $voucher['status']));
							}
							
							$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-status",$voucherStatus);
							
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
				}
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
				
				// ===== Montar todos os vouchers deste serviço.
				
				if($vouchers)
				foreach($vouchers as $voucher){
					if($voucher['id_hosts_servicos_variacoes'] == $pedServ['id_hosts_servicos_variacoes'] && $voucher['loteVariacao']){
						if($voucher['status'] != 'jwt-gerado'){
							// ===== Montar a célula do voucher.
							
							$cel_nome = 'cel-voucher-static';
							
							$cel_aux = $cel[$cel_nome];
							
							// ===== Modificar o status do voucher.
							
							switch($voucher['status']){
								case 'jwt-bd-expirado':
									$voucherStatus = gestor_variaveis(Array('modulo' => 'pedidos-voucher-status','id' => 'disponivel'));
								break;
								case 'usado':
									$voucherStatus = gestor_variaveis(Array('modulo' => 'pedidos-voucher-status','id' => $voucher['status']));
									
									gestor_incluir_biblioteca('formato');
									
									$data_uso = formato_dado(Array(
										'valor' => $voucher['data_uso'],
										'tipo' => 'dataHora',
									));
									
									$voucherStatus .= '<span class="ui grey label">Data da baixa: '.$data_uso.'</span>';
								break;
								default:
									$voucherStatus = gestor_variaveis(Array('modulo' => 'pedidos-voucher-status','id' => $voucher['status']));
							}
							
							$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"voucher-status",$voucherStatus);
							
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
				}
			}
			
			// ===== Remover marcadores.
			
			if(!$reemitiuPeloMenosUm){
				pagina_trocar_variavel_valor('<!-- cel-voucher -->','',true);
			}
			
			pagina_trocar_variavel_valor('<!-- cel-voucher-static -->','',true);
			
			// ===== Identificador do pedido.
			
			$JSvoucher['codigo'] = $codigo;
			$JSvoucher['dados'] = $vouchersDados;
		}
	}
	
	// ===== Alterações no layout da página.
	
	gestor_incluir_biblioteca('layout');
	
	layout_trocar_variavel_valor('layout#step','');
	layout_trocar_variavel_valor('layout#step-mobile','');
	
	// ===== Finalizar o layout com as variáveis padrões.
	
	layout_loja();
	
	// ===== Finalizar interface.
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-alerta',
			'modal-informativo',
			'modal-carregamento',
		)
	));
	
	interface_finalizar();
	
	// ===== Incluir o JS.
	
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>');
	gestor_pagina_javascript_incluir('modulos');
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['voucher'] = $JSvoucher;
}

// ==== Ajax

function voucher_ajax_alterar_identificacao(){
	global $_GESTOR;
	
	$codigo = banco_escape_field($_REQUEST['codigo']);
	$voucherID = banco_escape_field($_REQUEST['voucherID']);
	$nome = banco_escape_field($_REQUEST['nome']);
	$documento = banco_escape_field($_REQUEST['documento']);
	$telefone = banco_escape_field($_REQUEST['telefone']);
	
	// ===== Chamada da API-Servidor para atualizar dados no servidor.
	
	gestor_incluir_biblioteca('api-servidor');
	
	$retorno = api_servidor_voucher(Array(
		'opcao' => 'alterar-identificacao',
		'id_hosts_usuarios' => $_GESTOR['usuario-id'],
		'codigo' => $codigo,
		'voucherID' => $voucherID,
		'nome' => $nome,
		'documento' => $documento,
		'telefone' => $telefone,
	));
	
	if(!$retorno['completed']){
		switch($retorno['status']){
			case 'JWT_EXPIRED': 
				$status = $retorno['status'];
				
				$alerta = '';
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
		// ===== Atualizar o voucher localmente.
		
		if($retorno['data']['voucher']){
			$vouchers = banco_select(Array(
				'unico' => true,
				'tabela' => 'vouchers',
				'campos' => Array(
					'id_vouchers',
				),
				'extra' => 
					"WHERE id_hosts_vouchers='".$retorno['data']['voucher']['id_hosts_vouchers']."'"
			));
			
			// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
			
			if($vouchers){
				$voucher = $retorno['data']['voucher'];
				
				foreach($voucher as $campo => $valor){
					switch($campo){
						default:
							banco_update_campo($campo,$valor);
					}
				}
				
				banco_update_executar('vouchers',"WHERE id_hosts_vouchers='".$retorno['data']['voucher']['id_hosts_vouchers']."'");
			}
		}
		
		// ===== Retornar ok.
		
		$_GESTOR['ajax-json'] = Array(
			'status' => 'OK',
			'msg' => $retorno['data']['msg'],
		);
	}
}

function voucher_ajax_enviar_email(){
	global $_GESTOR;
	
	$codigo = banco_escape_field($_REQUEST['codigo']);
	$voucherID = banco_escape_field($_REQUEST['voucherID']);
	$email = banco_escape_field($_REQUEST['email']);
	
	// ===== Chamada da API-Servidor para atualizar dados no servidor.
	
	gestor_incluir_biblioteca('api-servidor');
	
	$retorno = api_servidor_voucher(Array(
		'opcao' => 'enviar-email',
		'id_hosts_usuarios' => $_GESTOR['usuario-id'],
		'codigo' => $codigo,
		'voucherID' => $voucherID,
		'email' => $email,
	));
	
	if(!$retorno['completed']){
		switch($retorno['status']){
			default:
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-servidor-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",(existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status'] ));
		}
		
		$_GESTOR['ajax-json'] = Array(
			'status' => (isset($status) ? $status : 'API_ERROR'),
			'msg' => $alerta,
		);
	} else {
		// ===== Retornar ok e o ppplus.
		
		$_GESTOR['ajax-json'] = Array(
			'status' => 'OK',
			'msg' => $retorno['data']['msg'],
		);
	}
}

function voucher_ajax_padrao(){
	global $_GESTOR;
	
	// ===== Retorno do AJAX.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function voucher_start(){
	global $_GESTOR;
	
	// ===== Verificar se o usuário está logado.
	
	gestor_permissao();
	
	// ===== Opções da interface, senão executar padrão.
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'alterar-identificacao': voucher_ajax_alterar_identificacao(); break;
			case 'enviar-email': voucher_ajax_enviar_email(); break;
			default: voucher_ajax_padrao();
		}
	} else {
		switch($_GESTOR['opcao']){
			//case 'opcao': voucher_opcao(); break;
			default: voucher_padrao();
		}
	}
}

voucher_start();

?>