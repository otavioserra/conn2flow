<?php

global $_GESTOR;

$_GESTOR['biblioteca-interface']							=	Array(
	'versao' => '1.0.6',
);

// ===== Funções auxiliares

function interface_alerta($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// msg - String - Opcional - Incluir uma mensagem para ser alertada na próxima tela do usuário.
	// redirect - Bool - Opcional - Só permitir imprimir depois do redirect.
	// imprimir - Bool - Opcional - Imprimir o alerta na tela.
	
	// ===== 
	
	if(isset($msg)){
		if(isset($redirect)){
			gestor_sessao_variavel('alerta',Array(
				'msg' => $msg,
			));
			
			$_GESTOR['interface-alerta-nao-imprimir'] = true;
		} else {
			$_GESTOR['pagina-alerta'] = Array(
				'msg' => $msg,
			);
		}
	}
	
	if(isset($imprimir)){
		if(!isset($_GESTOR['interface-alerta-nao-imprimir'])){
			if(!existe(gestor_sessao_variavel('alerta-redirect'))){
				if(existe(gestor_sessao_variavel('alerta'))){
					$alerta = gestor_sessao_variavel('alerta');
					gestor_sessao_variavel_del('alerta');
				} else if(isset($_GESTOR['pagina-alerta'])){
					$alerta = $_GESTOR['pagina-alerta'];
				}
				
				if(isset($alerta)){
					if(!isset($_GESTOR['javascript-vars']['interface'])){
						$_GESTOR['javascript-vars']['interface'] = Array();
					}
					
					$_GESTOR['javascript-vars']['interface']['alerta'] = $alerta;
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-alerta',
						)
					));
				}
			}
		} else {
			unset($_GESTOR['interface-alerta-nao-imprimir']);
		}
	}
}

function interface_componentes_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// componente - String|Array - Obrigatório - Incluir componente(s) na interface.
	
	// ===== 
	
	if(isset($componente)){
		switch(gettype($componente)){
			case 'array':
				if(count($componente) > 0){
					foreach($componente as $com){
						$_GESTOR['interface']['componentes'][$com] = true;
					}
				}
			break;
			default:
				$_GESTOR['interface']['componentes'][$componente] = true;
		}
	}
}

function interface_componentes($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	
	// ===== 
	
	if(isset($_GESTOR['interface'])){
		if(isset($_GESTOR['interface']['componentes'])){
			$componentes_layouts_ids = Array();
			$componentes = $_GESTOR['interface']['componentes'];
			
			if(count($componentes) > 0){
				foreach($componentes as $componente => $val){
					switch($componente){
						case 'modal-carregamento': $componentes_layouts_ids[] = 'hosts-interface-carregando-modal'; break;
						case 'modal-alerta': $componentes_layouts_ids[] = 'hosts-interface-alerta-modal'; break;
						case 'modal-informativo': $componentes_layouts_ids[] = 'hosts-interface-modal-informativo'; break;
					}
				}
			}
			
			// ===== Carregar layout de todos os componentes
			
			$layouts = false;
			
			if(count($componentes_layouts_ids) > 0){
				$layouts = gestor_componente(Array(
					'id' => $componentes_layouts_ids,
				));
			}
			
			if($layouts){
				$variables_js = Array();
				
				foreach($layouts as $id => $layout){
					$componente_html = '';
					
					switch($id){
						// ===== Modal de carregamento
						
						case 'hosts-interface-carregando-modal':
							$componente_html = $layout['html'];
						break;
						
						// ===== Modal de alerta
						
						case 'hosts-interface-alerta-modal':
							$componente_html = $layout['html'];
							
							$componente_html = modelo_var_troca($componente_html,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-title')));
							$componente_html = modelo_var_troca($componente_html,"#botao-ok#",gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-button-ok')));
							
							$variables_js['ajaxTimeoutMessage'] = gestor_variaveis(Array('modulo' => 'interface','id' => 'ajax-timeout-message'));
						break;
						
						// ===== Modal informativo
						
						case 'hosts-interface-modal-informativo':
							$componente_html = $layout['html'];
							
							$componente_html = modelo_var_troca($componente_html,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'inform-title')));
						break;
						
					}
					
					if(existe($componente_html)){
						$_GESTOR['pagina'] .= $componente_html;
					}
				}
				
				$_GESTOR['javascript-vars']['componentes'] = $variables_js;
			}
		}
	}
}

function interface_historico($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador do registro.
	// modulo - String - Obrigatório - Identificador do módulo.
	// pagina - String - Obrigatório - Página onde será implementado o histórico.
	
	// ===== 
	
	$max_dados_por_pagina = 10;
	$total = 0;
	$paginaAtual = 0;
	$totalPaginas = 0;
	
	$whereModulo = "modulo='".$modulo."'".(isset($id) ? " AND id='".$id."'" : '');
	
	// ===== Verificar o total de registros.
	
	$pre_historico = banco_select(Array(
		'tabela' => 'historico',
		'campos' => Array(
			'id_historico',
		),
		'extra' => 
			"WHERE "
			.$whereModulo
	));
	
	if(!isset($pre_historico)){
		$pre_historico = Array();
	}
	
	$total = count($pre_historico);
	
	// ===== Página atual
	
	if($_GESTOR['ajax']){
		if(isset($_REQUEST['pagina'])){
			$paginaAtual = (int)banco_escape_field($_REQUEST['pagina']);
		}
	} else {
		$totalPaginas = ($total % $max_dados_por_pagina > 0 ? 1 : 0) + floor($total / $max_dados_por_pagina);
		
		$_GESTOR['javascript-vars']['interface'] = Array(
			'id' => $id,
			'total' => $total,
			'totalPaginas' => $totalPaginas,
			'historico' => true,
		);
	}
	
	$historico = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_hosts_usuarios',
			'versao',
			'campo',
			'opcao',
			'filtro',
			'alteracao',
			'alteracao_txt',
			'valor_antes',
			'valor_depois',
			'tabela',
			'data',
			'controlador',
		))
		,
		"historico",
		"WHERE "
		.$whereModulo
		." ORDER BY versao DESC,id_historico DESC"
		." LIMIT ".($max_dados_por_pagina * $paginaAtual).",".$max_dados_por_pagina
	);
	
	if($historico){
		// ===== Caso haja registros do histórico, iniciar variáveis.
		
		$first_loop = true;
		$change_item = false;
		$versao_atual = 0;
		$id_hosts_usuarios = '0';
		$user_id = '';
		$user_primeiro_nome = '';
		
		if(!$_GESTOR['ajax']){
			$historico_linha = '<div class="ui middle aligned divided list">';
		} else {
			$historico_linha = '';
		}
		
		// ===== Varrer todos os registros do histórico
		
		foreach($historico as $item){
			if(existe($item['controlador'])){
				// ===== Caso tenha sido um histórico de controladores, incluir a referência do mesmo.
				
				switch($item['controlador']){
					case 'paypal-webhook':
						$autorName = 'PayPal Webhook';
					break;
					case 'cron':
						$autorName = 'Robo Controlador';
					break;
					default:
						$autorName = $item['controlador'];
				}
				// ===== Informar o autor do registro do histórico.
				
				$autor = '<a>' . $autorName . '</a>';
			} else {
				// ===== Buscar a referência do usuário do host que incluiu o registro.
				
				if($item['id_hosts_usuarios'] != $id_hosts_usuarios){
					$id_hosts_usuarios = $item['id_hosts_usuarios'];
					
					$usuarios = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'primeiro_nome',
						))
						,
						"usuarios",
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					);
					
					$user_id = $usuarios[0]['id'];
					$user_primeiro_nome = $usuarios[0]['primeiro_nome'];
				}
				
				// ===== Informar o autor do registro do histórico.
				
				$autor = '<a>' . $user_primeiro_nome . '</a>';
			}
			
			// Caso modifique a versão criar nova linha de registro.
			
			gestor_incluir_biblioteca('formato');
			
			if((int)$item['versao'] != $versao_atual){
				$versao_atual = (int)$item['versao'];
				
				$data = formato_dado(Array(
					'valor' => $item['data'],
					'tipo' => 'dataHora',
				));
				
				$historico_linha .= (!$first_loop ? '.</div></div></div>':'') . '<div class="item"><i class="info circle blue icon"></i><div class="content"><div class="header">' . $data . ' - '.$autor.'</div><div class="description first-letter-uppercase">';
				
				$change_item = true;
				$first_loop = false;
			}
			
			// ===== Iniciar variáveis de cada registro.
			
			$campo = $item['campo'];
			$opcao = $item['opcao'];
			$valor_antes = $item['valor_antes'];
			$valor_depois = $item['valor_depois'];
			$alteracao = $item['alteracao'];
			$alteracao_txt = $item['alteracao_txt'];
			$tabela = json_decode($item['tabela'],true);
			
			// ===== Aplicação de filtro de dados para cada caso.
			
			switch($item['filtro']){
				case 'checkbox': 
					if($valor_antes == '1'){
						$valor_antes = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-active'));
					} else {
						$valor_antes = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-inactive'));
					}
					
					if($valor_depois == '1'){
						$valor_depois = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-active'));
					} else {
						$valor_depois = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-inactive'));
					}
				break;
				case 'texto-para-float':
				case 'float-para-texto':
				case 'texto-para-int':
				case 'int-para-texto':
					$valor_antes = formato_dado(Array('valor' => $valor_antes,'tipo' => $item['filtro']));
					$valor_depois = formato_dado(Array('valor' => $valor_depois,'tipo' => $item['filtro']));
				break;
			}
			
			// ===== Definir o valor da variável principal
			
			$campo_texto = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $campo));
			
			if(!existe($campo_texto)){
				$campo_texto = gestor_variaveis(Array('modulo' => 'interface','id' => $campo));
			}
			
			// ===== 3 opções de registro de histórico: valor antes e depois, alteracao via id e somente mostrar campo que mudou.
			
			if(existe($valor_antes) || existe($valor_depois)){
				// ===== Verificar se tem tabela referente dos valores. Se sim, trocar valores com base nessa tabela.
				
				if($tabela){
					if(isset($tabela['id'])){
						if(isset($tabela['nome']) && isset($tabela['campo'])){
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									$tabela['campo'],
									$tabela['id'],
								))
								,
								$tabela['nome'],
								"WHERE ".$tabela['id']."='".$valor_antes."'"
								." OR ".$tabela['id']."='".$valor_depois."'"
							);
							
							if($resultado)
							foreach($resultado as $res){
								if($res[$tabela['id']] == $valor_antes){
									$valor_antes = $res[$tabela['campo']];
								}
								if($res[$tabela['id']] == $valor_depois){
									$valor_depois = $res[$tabela['campo']];
								}
							}
						}
					} else {
						if(isset($tabela['nome']) && isset($tabela['campo']) && isset($tabela['id_numerico'])){
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									$tabela['campo'],
									$tabela['id_numerico'],
								))
								,
								$tabela['nome'],
								"WHERE ".$tabela['id_numerico']."='".$valor_antes."'"
								." OR ".$tabela['id_numerico']."='".$valor_depois."'"
							);
							
							if($resultado)
							foreach($resultado as $res){
								if($res[$tabela['id_numerico']] == $valor_antes){
									$valor_antes = $res[$tabela['campo']];
								}
								if($res[$tabela['id_numerico']] == $valor_depois){
									$valor_depois = $res[$tabela['campo']];
								}
							}
						}
					}
				}
				
				switch($opcao){
					case 'usuarios-perfis':
						$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-change-users-profiles'));
					break;
					default:
						$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-change-field-after-before'));
				}
				
				// ===== Procurar variável padrão na interface
				
				$valor_antes_variavel = '';
				if(existe($valor_antes)){
					$valor_antes_variavel = gestor_variaveis(Array('modulo' => 'interface','id' => $valor_antes));
					if(existe($valor_antes_variavel)){
						$valor_antes = $valor_antes_variavel;
					}
				}
				
				$valor_depois_variavel = '';
				if(existe($valor_depois)){
					$valor_depois_variavel = gestor_variaveis(Array('modulo' => 'interface','id' => $valor_depois));
					if(existe($valor_depois_variavel)){
						$valor_depois = $valor_depois_variavel;
					}
				}
				
				// ===== Histórico ocorrência
				
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#campo#",$campo_texto);
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#valor_antes#",(existe($valor_antes) ? $valor_antes : gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-empty-value'))));
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#valor_depois#",(existe($valor_depois) ? $valor_depois : gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-empty-value'))));
			} else if(existe($alteracao)){
				$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => $alteracao));
				
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#campo#",$campo_texto);
				
				switch($alteracao){
					case 'historic-change-status': 
						$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#valor_depois#",gestor_variaveis(Array('modulo' => 'interface','id' => $valor_depois)));
					break;
				}
				
				if(existe($alteracao_txt)){
					$historico_ocorrencia .= $alteracao_txt;
				}
			} else {
				$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-change-field-only'));
				
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#campo#",$campo_texto);
			}
			
			// ===== Incluir todas as ocorrências de uma dada versão.
			
			$historico_linha .= (!$change_item ? ', ':'') . ' ' . $historico_ocorrencia;
			$change_item = false;
		}
		
		// ===== finalizar e incluir todos os registros no componente histórico.
	
		if(!$_GESTOR['ajax']){
			$botao_carregar_mais = '';
			
			if($total > $max_dados_por_pagina){
				$botao_carregar_mais = '<div class="ui grid"><div class="column center aligned"><button class="ui button blue" id="_gestor-interface-edit-historico-mais">'.gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-button-load-more')).'</button></div></div>';
			}
			
			$historico_linha .= '.</div></div></div>'.$botao_carregar_mais;
			$pagina = modelo_var_troca($pagina,"<td>#historico#</td>","<td>".$historico_linha."</td>");
		} else {
			$historico_linha .= '.</div></div></div>';
			$pagina = modelo_var_troca($pagina,"#historico#",$historico_linha);
		}
		
	} else {
		// ===== Remove o componente histórico caso não encontre nenhum registro no histórico.
		if(!$_GESTOR['ajax']){
			$cel_nome = 'historico'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		} else {
			$pagina = '';
		}
	}
	
	return $pagina;
}

// ===== Interfaces principais


// ===== Interfaces ajax

function interface_ajax_historico_mais_resultados(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'pagina' => interface_historico(Array(
			'id' => (isset($_REQUEST['id']) ? $_REQUEST['id'] : '' ),
			'modulo' => $_GESTOR['modulo-alvo-id'],
			'pagina' => '#historico#',
		))
	);
}

// ===== Interfaces padrões

function interface_ajax_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	
}

function interface_ajax_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'historico-mais-resultados': interface_ajax_historico_mais_resultados(); break;
		}
	}
}

function interface_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	
}

function interface_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Imprimir alerta
	
	interface_alerta(Array('imprimir' => true));
	
	// ===== Incluir Componentes na Página
	
	interface_componentes();
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Incluir o gestor listener.
	
	$_GESTOR['pagina'] .= "\n".'	<div id="gestor-listener"></div>';
}

?>