<?php

global $_GESTOR;

$_GESTOR['biblioteca-interface']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções formatação

function interface_data_hora_from_datetime_to_text($data_hora, $format = false){
	$formato_padrao = 'D/ME/A HhMI';
	
	if($data_hora){
		$data_hora = explode(" ",$data_hora);
		$data_aux = explode("-",$data_hora[0]);
		
		if($format){
			$hora_aux = explode(":",$data_hora[1]);
			$format = preg_replace('/D/', $data_aux[2], $format);
			$format = preg_replace('/ME/', $data_aux[1], $format);
			$format = preg_replace('/A/', $data_aux[0], $format);
			$format = preg_replace('/H/', $hora_aux[0], $format);
			$format = preg_replace('/MI/', $hora_aux[1], $format);
			$format = preg_replace('/S/', $hora_aux[2], $format);
			
			return $format;
		} else if($formato_padrao){
			$format = $formato_padrao;
			$hora_aux = explode(":",$data_hora[1]);
			$format = preg_replace('/D/', $data_aux[2], $format);
			$format = preg_replace('/ME/', $data_aux[1], $format);
			$format = preg_replace('/A/', $data_aux[0], $format);
			$format = preg_replace('/H/', $hora_aux[0], $format);
			$format = preg_replace('/MI/', $hora_aux[1], $format);
			$format = preg_replace('/S/', $hora_aux[2], $format);
			
			return $format;
		} else {
			$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
			$hora = $data_hora[1];
			
			return $data . " " . $hora;
		}
	} else {
		return "";
	}
}

function interface_data_from_datetime_to_text($data_hora){
	$data_hora = explode(" ",$data_hora);
	$data_aux = explode("-",$data_hora[0]);
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	
	return $data;
}

function interface_trocar_valor_outra_tabela($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// tabela - Array - Obrigatório - Tabela que será usada para trocar valores.
		// where - Tipo - Opcional - Valor extra para aplicar ao campo where.
	
	// ===== 
	
	if(isset($tabela)){
		if(isset($tabela['nome']) && isset($tabela['campo_trocar']) && isset($tabela['campo_referencia'])){
			if(isset($_GESTOR['interface_trocar_valor_outra_tabela'])){
				if(isset($_GESTOR['interface_trocar_valor_outra_tabela'][$tabela['nome']])){
					$outraTabela = $_GESTOR['interface_trocar_valor_outra_tabela'][$tabela['nome']];
				}
			}
			
			if(isset($outraTabela)){
				foreach($outraTabela as $campo_referencia => $campo){
					if($campo_referencia == $tabela['campo_referencia'] && $campo['antes'] == $dado){
						return $campo['depois'];
					}
				}
			}
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$tabela['campo_trocar'],
				))
				,
				$tabela['nome'],
				"WHERE ".$tabela['campo_referencia']."='".$dado."'"
				.(isset($tabela['where']) ? ' AND ('.$tabela['where'].')' : '')
			);
			
			if($resultado){
				$_GESTOR['interface_trocar_valor_outra_tabela'][$tabela['nome']][$tabela['campo_referencia']] = Array(
					'antes' => $dado,
					'depois' => $resultado[0][$tabela['campo_trocar']],
				);
				
				return $resultado[0][$tabela['campo_trocar']];
			}
		}
	}
	
	return $dado;
}

function interface_trocar_valor_outro_conjunto($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	if(isset($conjunto)){
		foreach($conjunto as $campo){
			if($campo['alvo'] == $dado){
				return $campo['troca'];
			}
		}
	}
	
	return $dado;
}

function interface_formatar_dado($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// dado - String - Opcional - Dado que será formatado.
	// formato - String|Array - Opcional - Formato a ser aplicado ou conjunto de dados para formatar dado.
	// valor_senao_existe - String - Opcional - Senão houver valor, este será aplicado.
	
	// Se formato - Array
	
	// id - String - Obrigatório - Identificador do formato a ser aplicado.
	
	// Se formato == 'outraTabela'
	
	// tabela - Array - Obrigatório - Dados da tabela que será aplicada a formatação.
		// nome - String - Obrigatório - nome da tabela do banco de dados.
		// campo_trocar - String - Obrigatório - Nome do campo que será usado para subistituir o valor.
		// campo_referencia - String - Obrigatório - Nome do campo identificador do valor que será comparado afim de trocar pelo valor do banco caso exista.
		
	// Se formato == 'outroConjunto'
	
	// conjunto - Array - Obrigatório - Dados do conjunto que será aplicado na formatação.
		// alvo - String - Obrigatório - Valor do alvo da troca.
		// troca - String - Obrigatório - Valor da troca.
		
	// ===== 
	
	$formatoId = null;
	
	if(isset($formato)){
		switch(gettype($formato)){
			case 'array':
				if(isset($formato['id'])) $formatoId = $formato['id'];
			break;
			default:
				$formatoId = $formato;
		}
	}
	
	if(strlen($dado) > 0){
		switch($formatoId){
			case 'dinheiroReais':
				gestor_incluir_biblioteca('formato');
				
				$dado = 'R$ '.formato_dado(Array('valor' => $dado,'tipo' => 'float-para-texto'));
			break;
			case 'data': $dado = interface_data_from_datetime_to_text($dado); break;
			case 'dataHora': $dado = interface_data_hora_from_datetime_to_text($dado); break;
			case 'outraTabela': $dado = interface_trocar_valor_outra_tabela(Array('dado' => $dado,'tabela' => $formato['tabela'])); break;
			case 'outroConjunto': $dado = interface_trocar_valor_outro_conjunto(Array('dado' => $dado,'conjunto' => $formato['conjunto'])); break;
		}
		
		return $dado;
	} else {
		return (isset($formato['valor_senao_existe']) ? $formato['valor_senao_existe'] : '');
	}
}

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

function interface_historico_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// alteracoes - Array - Opcional - Conjunto de dados relativos a alteração que foi feita num dados registro.
		// campo - String - Opcional - Identificador do campo que foi alterado caso necessário. Sistema buscará o valor na linguagem: código do módulo/id do campo.
		// opcao - String - Opcional - Opção extra necessária para desparar pequenos hacks no histórico que não segue um padrão.
		// filtro - String - Opcional - Filtro necessário para formatar os dados.
		// alteracao - String - Opcional - Identificador da alteração. Sistema buscará o valor na linguagem: interface/id do campo.
		// alteracao_txt - String - Opcional - Caso necessário completar uma alteração, este campo pode ser passado com o valor literal da alteração.
		// valor_antes - String - Opcional - Valor antes da alteração.
		// valor_depois - String - Opcional - Valor após a alteração.
		// tabela - Array - Opcional - Tabela que será comparada com os valores antes e depois caso definido para trocar ids por nomes.
			// nome - String - Obrigatório - nome da tabela do banco de dados.
			// campo - String - Obrigatório - campo da tabela que será retornado como valor textual dos ids.
			// id_numerico - String - Obrigatório - identificador numérico dos dados da tabela.
	// deletar - Bool - Opcional - Caso definido, incrementar em 1 a versão, pois deletar a inclusão de histórico é anterior a atualização final do registro para status='D'.
	// id_numerico_manual - Int - Opcional - Caso definido, o id_numerico do registro será manualmente definido.
	// id_usuarios_manual - Int - Opcional - Caso definido, o id_usuarios do registro será manualmente definido.
	// id_hosts_manual - Int - Opcional - Caso definido, o id_hosts do registro será manualmente definido.
	// modulo_id - String - Opcional - Caso definido, vinculará o registro manualmente neste módulo.
	// sem_id - Bool - Opcional - Caso definido, não vinculará nenhum ID ao histórico.
		// versao - Int - Opcional - Definir manualmente a versão do registro.
	// tabela - Array - Opcional - Tabela que será usada ao invés da tabela principal do módulo.
		// nome - String - Obrigatório - nome da tabela do banco de dados.
		// versao - String - Obrigatório - Campo versao da tabela do banco de dados.
		// id_numerico - String - Obrigatório - identificador numérico dos dados da tabela.
		// id - Bool - Opcional - Caso definido, vai usar o campo id como campo referencial e não o id_numerico.
	
	// ===== Possibilidades
	/*
		Na inclusão há 3 possibilidades de passagem por parâmetros:
		
		1 - campo - o histórico só mostrará o nome do campo que foi alterado.
		2 - campo, valor_antes e valor_depois - o histórico mostra o valor antes e depois de uma alteração.
		3 - alteracao, alteracao_txt [campo] - o histórico mostra um valor pré-definido, caso necessário informar um valor a mais, basta informar a 'alteracao_txt' e se quiser também o 'campo'. E caso o valor do 'alteracao' tenha marcação #campo# , o sistema subistituirá esse valor com o nome do 'campo'.
	*/
	// ===== 
	
	if(isset($alteracoes)){
		if(isset($id_usuarios_manual)){
			$usuario['id_usuarios'] = $id_usuarios_manual;
		} else {	
			$usuario = gestor_usuario();
		}
		
		if(!isset($tabela)){
			$tabela = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela'];
		}
		
		if(!isset($sem_id)){
			if(isset($id_numerico_manual)){
				$id_numerico = $id_numerico_manual;
			} else {
				$id_numerico = interface_modulo_variavel_valor(Array('variavel' => $tabela['id_numerico']));
			}

			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$tabela['versao'],
				))
				,
				$tabela['nome'],
				"WHERE ".$tabela['id_numerico']."='".$id_numerico."'"
			);
			
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		
		foreach($alteracoes as $alteracao){
			if(isset($id_hosts_manual)){ 
				banco_insert_name_campo('id_hosts',$id_hosts_manual);
			} else {
				if(isset($_GESTOR['host-id'])){ banco_insert_name_campo('id_hosts',$_GESTOR['host-id']); }
			}
			
			banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
			banco_insert_name_campo('modulo',(isset($modulo_id) ? $modulo_id : $_GESTOR['modulo-id']));
			
			if(!isset($sem_id)){ banco_insert_name_campo('id',$id_numerico); }
			
			banco_insert_name_campo('versao',(isset($deletar) ? 1 : 0) + (int)$versao_bd,true);
			banco_insert_name_campo('data','NOW()',true);
			
			if(isset($alteracao['campo'])){ banco_insert_name_campo('campo',$alteracao['campo']); }
			if(isset($alteracao['opcao'])){ banco_insert_name_campo('opcao',$alteracao['opcao']); }
			if(isset($alteracao['filtro'])){ banco_insert_name_campo('filtro',$alteracao['filtro']); }
			if(isset($alteracao['alteracao'])){ banco_insert_name_campo('alteracao',$alteracao['alteracao']); }
			if(isset($alteracao['alteracao_txt'])){ banco_insert_name_campo('alteracao_txt',$alteracao['alteracao_txt']); }
			if(isset($alteracao['valor_antes'])){ banco_insert_name_campo('valor_antes',$alteracao['valor_antes']); }
			if(isset($alteracao['valor_depois'])){ banco_insert_name_campo('valor_depois',$alteracao['valor_depois']); }
			if(isset($alteracao['tabela'])){ banco_insert_name_campo('tabela',json_encode($alteracao['tabela'])); }
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"historico"
			);
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
	// sem_id - Bool - Opcional - Caso definido, não filtrará od ID no histórico.
	
	// moduloVars - Módulo - Opcional - Variável definida na variável global modulo.
		// historico - Conjunto - Opcional - Conjunto de configurações do histórico.
			// moduloIdExtra - String - Opcional - Módulo Id Extra para trocar os labels.
	
	// ===== 
	
	$moduloVars = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$max_dados_por_pagina = 10;
	$total = 0;
	$paginaAtual = 0;
	$totalPaginas = 0;
	
	$whereModulo = "modulo='".$modulo."'";
	
	if(isset($moduloVars['historico'])){
		if(isset($moduloVars['historico']['moduloIdExtra'])){
			$whereModulo = "(modulo='".$modulo."' OR modulo='".$moduloVars['historico']['moduloIdExtra']."')";
		}
	}
	
	// ===== Verificar o total de registros.
	
	$pre_historico = banco_select(Array(
		'tabela' => 'historico',
		'campos' => Array(
			'id_historico',
		),
		'extra' => 
			"WHERE "
			.$whereModulo
			.(!isset($sem_id) ? " AND id='".interface_modulo_variavel_valor(Array('variavel' => $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela']['id_numerico']))."'" : "")
			.(isset($_GESTOR['host-id']) ? " AND id_hosts='".$_GESTOR['host-id']."'":"")
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
		);
	}
	
	$historico = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_usuarios',
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
		.(!isset($sem_id) ? " AND id='".interface_modulo_variavel_valor(Array('variavel' => $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela']['id_numerico']))."'" : '')
		.(isset($_GESTOR['host-id']) ? " AND id_hosts='".$_GESTOR['host-id']."'":'')
		." ORDER BY versao DESC,id_historico DESC"
		." LIMIT ".($max_dados_por_pagina * $paginaAtual).",".$max_dados_por_pagina
	);
	
	if($historico){
		// ===== Caso haja registros do histórico, iniciar variáveis.
		
		$first_loop = true;
		$change_item = false;
		$versao_atual = 0;
		$id_usuarios = '0';
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
			} else if(existe($item['id_hosts_usuarios'])){
				// ===== Buscar a referência do usuário do host que incluiu o registro.
				
				if($item['id_hosts_usuarios'] != $id_hosts_usuarios){
					$id_hosts_usuarios = $item['id_hosts_usuarios'];
					
					$hosts_usuarios = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'primeiro_nome',
						))
						,
						"hosts_usuarios",
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					);
					
					$user_id = $hosts_usuarios[0]['id'];
					$user_primeiro_nome = $hosts_usuarios[0]['primeiro_nome'];
				}
				
				// ===== Informar o autor do registro do histórico.
				
				$autor = '<a href="'.$_GESTOR['url-raiz'].'hosts-usuarios/editar/?id='.$user_id.'">' . $user_primeiro_nome . '</a>';
			} else {
				// ===== Buscar a referência do usuário do sistema que incluiu o registro.
				
				if($item['id_usuarios'] != $id_usuarios){
					$id_usuarios = $item['id_usuarios'];
					
					$usuarios = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'primeiro_nome',
						))
						,
						"usuarios",
						"WHERE id_usuarios='".$id_usuarios."'"
					);
					
					$user_id = $usuarios[0]['id'];
					$user_primeiro_nome = $usuarios[0]['primeiro_nome'];
				}
				
				// ===== Informar o autor do registro do histórico.
				
				$autor = '<a href="'.$_GESTOR['url-raiz'].'usuarios/editar/?id='.$user_id.'">' . $user_primeiro_nome . '</a>';
			}
			
			// Caso modifique a versão criar nova linha de registro.
			
			if((int)$item['versao'] != $versao_atual){
				$versao_atual = (int)$item['versao'];
				
				$data = interface_formatar_dado(Array(
					'dado' => $item['data'],
					'formato' => 'dataHora',
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
					gestor_incluir_biblioteca('formato');
					$valor_antes = formato_dado(Array('valor' => $valor_antes,'tipo' => $item['filtro']));
					$valor_depois = formato_dado(Array('valor' => $valor_depois,'tipo' => $item['filtro']));
				break;
			}
			
			// ===== Definir o valor da variável principal
			
			$campo_texto = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $campo));
			
			if(!existe($campo_texto)){
				$campo_texto = gestor_variaveis(Array('modulo' => 'interface','id' => $campo));
			}
			
			if(!existe($campo_texto)){
				if(isset($moduloVars['historico'])){
					if(isset($moduloVars['historico']['moduloIdExtra'])){
						$campo_texto = gestor_variaveis(Array('modulo' => $moduloVars['historico']['moduloIdExtra'],'id' => $campo));
					}
				}
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
			
			$historico_linha .= '.</div></div></div>'.$botao_carregar_mais.'</div>';
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
						case 'modal-carregamento': $componentes_layouts_ids[] = 'interface-carregando-modal'; break;
						case 'modal-delecao': $componentes_layouts_ids[] = 'interface-delecao-modal'; break;
						case 'modal-alerta': $componentes_layouts_ids[] = 'interface-alerta-modal'; break;
						case 'modal-iframe': $componentes_layouts_ids[] = 'interface-iframe-modal'; break;
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
						
						case 'interface-carregando-modal':
							$componente_html = $layout['html'];
						break;
						
						// ===== Modal de deleção
						
						case 'interface-delecao-modal':
							$componente_html = $layout['html'];
							
							$componente_html = modelo_var_troca($componente_html,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-title')));
							$componente_html = modelo_var_troca($componente_html,"#mensagem#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-menssage')));
							$componente_html = modelo_var_troca($componente_html,"#botao-cancelar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-button-cancel')));
							$componente_html = modelo_var_troca($componente_html,"#botao-confirmar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-button-confirm')));
						break;
						
						// ===== Modal de alerta
						
						case 'interface-alerta-modal':
							$componente_html = $layout['html'];
							
							$componente_html = modelo_var_troca($componente_html,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-title')));
							$componente_html = modelo_var_troca($componente_html,"#botao-ok#",gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-button-ok')));
							
							$variables_js['ajaxTimeoutMessage'] = gestor_variaveis(Array('modulo' => 'interface','id' => 'ajax-timeout-message'));
						break;
						
						// ===== Modal iframe
						
						case 'interface-iframe-modal':
							$componente_html = $layout['html'];
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

function interface_formulario_campos($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// pagina - String - Opcional - Fornecer a página onde será incluído o campo ao invés de colocar na página padrão.
	// campos - Array - Opcional - Conjunto de todos os campos.
		// tipo - String - Obrigatório - Identificador do tipo do campo.
		// id - String - Obrigatório - Identificador do campo.
		// nome - String - Obrigatório - Nome do campo.
		
		// ##### Caso tipo = 'select'
		
		// menu - Bool - Opcional - Se habilitado, o select terá mais opções visuais.
		// procurar - Bool - Opcional - Se habilitado, é possível procurar um valor no select digitando no teclado.
		// limpar - Bool - Opcional - Se habilitado, o select tem uma opção de deselecionar a opção selecionada.
		// multiple - Bool - Opcional - Se habilitado, o select tem uma opção de selecionar várias opções ao mesmo tempo.
		// fluid - Bool - Opcional - Se habilitado, o select será do tipo fluído tentando completar toda a tela.
		// placeholder - String - Opcional - Se definido o select terá uma opção coringa para mostrar na caixa quando não houver valor selecionado.
		// selectClass - String - Opcional - Se definido será incluída a classe ou classes no final do parâmetro class do select.
		
		// Ou tabela Ou dados
		
		// tabela - Array - Opcional - Conjunto com dados que virão de uma tabela no banco de dados
			// id - Bool - Opcional - Caso definido, vai usar o campo id como campo referencial e não o id_numerico.
			// nome - String - Obrigatório - nome da tabela do banco de dados.
			// campo - String - Obrigatório - campo da tabela que será retornado como valor textual das opções.
			// id_numerico - String - Obrigatório - identificador numérico da dos dados da tabela.
			// id_selecionado - Int - Opcional - Caso definido, deixar a opção com o valor do id selecionado.
			// where - String - Opcional - Caso definido, usar o where como filtro na tabela do banco de dados.
		
		// valor_selecionado - String - Opcional - Caso definido, deixar a opção com o valor do id selecionado.
		// valor_selecionado_icone - String - Opcional - Caso definido, mostrar ícone na versão menu (não funciona no tipo select/option).
		// placeholder_icone - String - Opcional - Caso definido, mostrar ícone no 'text default' na versão menu (não funciona no tipo select/option).
		// dados - Array - Opcional - Conjunto com dados que virão de um array
			// texto - String - Obrigatório - Texto de cada opção no select.
			// valor - String - Obrigatório - Valor de cada opção no select.
			// icone - String - Opcional - Ícone que deverá ser aplicado a cada dado.
		
		// ##### Caso tipo = 'imagepick'
		
		// id_arquivos - Int - Opcional - Id referencial do arquivo.
		
		// ##### Caso tipo = 'imagepick-hosts'
		
		// id_hosts_arquivos - Int - Opcional - Id referencial do arquivo no host.
		
		// ##### Caso tipo = 'templates-hosts'
		
		// categoria_id - String - Obrigatório - Identificador da categoria de templates.
		// template_id - String - Opcional - Id do template.
		// template_tipo - String - Opcional - Tipo do template (gestor ou hosts).
	
	// ===== 
	
	if(isset($campos)){
		foreach($campos as $campo){
			switch($campo['tipo']){
				case 'select':
					if(isset($campo['menu'])){
						$campo_saida = "	".'<div id="'.$campo['id'].'" class="ui '.(isset($campo['fluid']) ? 'fluid ':'').(isset($campo['procurar']) ? 'search ':'').(isset($campo['limpar']) ? 'clearable ':'').(isset($campo['multiple']) ? 'multiple ':'').'selection dropdown'.(isset($campo['selectClass']) ? ' '.$campo['selectClass'] : '').'">'."\n";
						$campo_saida .= "		".'<input type="hidden" name="'.$campo['nome'].'"#selectedValue#>'."\n";
						$campo_saida .= "		".'<i class="dropdown icon"></i>'."\n";
					} else {
						$campo_saida = "	".'<select id="'.$campo['id'].'" class="ui '.(isset($campo['fluid']) ? 'fluid ':'').(isset($campo['procurar']) ? 'search ':'').(isset($campo['limpar']) ? 'clearable ':'').'dropdown'.(isset($campo['selectClass']) ? ' '.$campo['selectClass'] : '').'" name="'.$campo['nome'].'"'.(isset($campo['multiple']) ? ' multiple':'').'>'."\n";
					}
					
					if(isset($campo['placeholder'])){
						if(isset($campo['menu'])){
							if(isset($campo['valor_selecionado_texto'])){
								$campo_saida .= "		".'<div class="text">'.(isset($campo['valor_selecionado_icone']) ? '<i class="'.$campo['valor_selecionado_icone'].' icon"></i>' : '').$campo['valor_selecionado_texto'].'</div>'."\n";
							} else {
								$campo_saida .= "		".'<div class="default text">'.(isset($campo['placeholder_icone']) ? '<i class="'.$campo['placeholder_icone'].' icon"></i>' : '').$campo['placeholder'].'</div>'."\n";
							}
						
							$campo_saida .= "		".'<div class="menu">'."\n";
						} else {
							$campo_saida .= "		".'<option value="">'.$campo['placeholder'].'</option>'."\n";
						}
					}
					
					if(isset($campo['tabela'])){
						$tabela = $campo['tabela'];
						
						if(isset($tabela['id'])){
							if(isset($tabela['nome']) && isset($tabela['campo'])){
								$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
								
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										$tabela['campo'],
										$modulo['tabela']['id']
									))
									,
									$tabela['nome'],
									"WHERE ".$modulo['tabela']['status']."='A'"
									.(isset($tabela['where']) ? ' AND ('.$tabela['where'].')' : '' )
									." ORDER BY ".$tabela['campo']." ASC"
								);
								
								if($resultado){
									foreach($resultado as $res){
										if(isset($campo['menu'])){
											$campo_saida .= "			".'<div class="item '.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$modulo['tabela']['id']] ? 'active selected' : '' ) : '' ).'" data-value="'.$res[$modulo['tabela']['id']].'">'.$res[$tabela['campo']].'</div>'."\n";
										} else {
											$campo_saida .= "		".'<option value="'.$res[$modulo['tabela']['id']].'"'.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$modulo['tabela']['id']] ? ' selected' : '' ) : '' ).'>'.$res[$tabela['campo']].'</option>'."\n";
										}
									}
								}
							}
						} else {
							if(isset($tabela['nome']) && isset($tabela['campo']) && isset($tabela['id_numerico'])){
								$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
								
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										$tabela['campo'],
										$tabela['id_numerico']
									))
									,
									$tabela['nome'],
									"WHERE ".$modulo['tabela']['status']."='A'"
									.(isset($tabela['where']) ? ' AND ('.$tabela['where'].')' : '' )
									." ORDER BY ".$tabela['campo']." ASC"
								);
								
								if($resultado){
									foreach($resultado as $res){
										if(isset($campo['menu'])){
											$campo_saida .= "			".'<div class="item '.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$tabela['id_numerico']] ? 'active selected' : '' ) : '' ).'" data-value="'.$res[$tabela['id_numerico']].'">'.$res[$tabela['campo']].'</div>'."\n";
										} else {
											$campo_saida .= "		".'<option value="'.$res[$tabela['id_numerico']].'"'.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$tabela['id_numerico']] ? ' selected' : '' ) : '' ).'>'.$res[$tabela['campo']].'</option>'."\n";
										}
									}
								}
							}
						}
					} else if(isset($campo['dados'])){
						$dados = $campo['dados'];
						
						if(isset($campo['valor_selecionado'])){
							$valor_selecionado = $campo['valor_selecionado'];
						}
						
						foreach($dados as $dado){
							if(isset($dado['texto']) && isset($dado['valor'])){
								if(isset($campo['menu'])){
									$campo_saida .= "			".'<div class="item '.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? 'active selected' : '' ) : '' ).'" data-value="'.$dado['valor'].'">'.(isset($dado['icone']) ? '<i class="'.$dado['icone'].' icon"></i>' : '').$dado['texto'].'</div>'."\n";
									
									if(isset($valor_selecionado)){
										if($valor_selecionado == $dado['valor']){
											$campo_saida = modelo_var_troca($campo_saida,"#selectedValue#",' value="'.$dado['valor'].'"');
										}
									}
								} else {
									$campo_saida .= "		".'<option value="'.$dado['valor'].'"'.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? ' selected' : '' ) : '' ).'>'.$dado['texto'].'</option>'."\n";
								}
							}
						}
					}
					
					// ===== Finalizar a montagem do select
					
					if(isset($campo['menu'])){
						$campo_saida .= "		".'</div>'."\n";
						$campo_saida .= "	".'</div>'."\n";
						
						$campo_saida = modelo_var_troca($campo_saida,"#selectedValue#",'');
					} else {
						$campo_saida .= "	".'</select>'."\n";
					}
					
					// ===== Incluir o select na página
					
					if(isset($pagina)){
						return modelo_var_troca($pagina,'<span>#select-'.$campo['id'].'#</span>',$campo_saida);
					} else {
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#select-'.$campo['id'].'#</span>',$campo_saida);
					}
				break;
				case 'imagepick':
					// ===== Ler o layout do image pick
					
					$imagepick = gestor_componente(Array(
						'id' => 'widget-imagem',
						'modulosExtra' => Array(
							'interface',
						),
					));
					
					// ===== Definir valores padrões
					
					$imagepickJS['padroes'] = Array(
						'fileId' => '-1',
						'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-name')),
						'data' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-date')),
						'tipo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-type')),
						'imgSrc' => $_GESTOR['url-full'] . 'images/imagem-padrao.png',
					);
					
					$imagepickJS['modal'] = Array(
						'head' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-head')),
						'cancel' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-cancel')),
						'url' => $_GESTOR['url-full'] . 'admin-arquivos/?paginaIframe=sim',
					);
					
					$imagepickJS['alertas'] = Array(
						'naoImagem' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-alert-not-image')),
					);
					
					// ===== Se existe o arquivo, baixar dados do banco, senão definir valores padrões
					
					$found = false;
					if(isset($campo['id_arquivos'])){
						$id_arquivos = $campo['id_arquivos'];
						
						$arquivos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'tipo',
								'nome',
								'data_criacao',
								'caminho',
								'caminho_mini',
							))
							,
							"arquivos",
							"WHERE id_arquivos='".$id_arquivos."'"
						);
						
						if($arquivos){
							$found = true;
							
							if($arquivos[0]['caminho_mini']){
								$imgSrc = $_GESTOR['url-full'] . $arquivos[0]['caminho_mini'];
							} else {
								$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
							}
							
							$data = interface_formatar_dado(Array('dado' => $arquivos[0]['data_criacao'], 'formato' => 'dataHora'));
							$nome = $arquivos[0]['nome'];
							$tipo = $arquivos[0]['tipo'];
							
							$fileId = $id_arquivos;
						}
					}
					
					if(!$found){
						$fileId = $imagepickJS['padroes']['fileId'];
						$nome = $imagepickJS['padroes']['nome'];
						$data = $imagepickJS['padroes']['data'];
						$tipo = $imagepickJS['padroes']['tipo'];
						$imgSrc = $imagepickJS['padroes']['imgSrc'];
					}
					
					// ===== Alterar os dados do widget
					
					$imagepick = modelo_var_troca($imagepick,"#cont-id#",$campo['id']);
					$imagepick = modelo_var_troca($imagepick,"#campo-nome#",$campo['nome']);
					
					$imagepick = modelo_var_troca_tudo($imagepick,"#file-id#",$fileId);
					$imagepick = modelo_var_troca($imagepick,"#nome#",$nome);
					$imagepick = modelo_var_troca($imagepick,"#tipo#",$tipo);
					$imagepick = modelo_var_troca($imagepick,"#data#",$data);
					$imagepick = modelo_var_troca($imagepick,"#img-src#",$imgSrc);
					
					// ===== Incluir o imagepick na página
					
					if(isset($pagina)){
						$pagina = modelo_var_troca($pagina,'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					} else {
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					}
					
					// ===== Atualizar variável javascript
					
					$_GESTOR['javascript-vars']['interface']['imagepick'] = $imagepickJS;
					
					// ===== Incluir o componente iframe modal
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-iframe',
							'modal-alerta',
						)
					));
					
					// ===== Se precisar retornar a página.
				
					if(isset($pagina)){
						return $pagina;
					}
				break;
				case 'imagepick-hosts':
					// ===== Ler o layout do image pick
					
					$imagepick = gestor_componente(Array(
						'id' => 'widget-imagem',
						'modulosExtra' => Array(
							'interface',
						),
					));
					
					// ===== Definir valores padrões
					
					$imagepickJS['padroes'] = Array(
						'fileId' => '-1',
						'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-name')),
						'data' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-date')),
						'tipo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-type')),
						'imgSrc' => $_GESTOR['url-full'] . 'images/imagem-padrao.png',
					);
					
					$imagepickJS['modal'] = Array(
						'head' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-head')),
						'cancel' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-cancel')),
						'url' => $_GESTOR['url-full'] . 'arquivos/?paginaIframe=sim',
					);
					
					$imagepickJS['alertas'] = Array(
						'naoImagem' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-alert-not-image')),
					);
					
					// ===== Se existe o arquivo, baixar dados do banco, senão definir valores padrões
					
					$found = false;
					if(isset($campo['id_hosts_arquivos'])){
						$id_hosts_arquivos = $campo['id_hosts_arquivos'];
						
						$arquivos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'tipo',
								'nome',
								'data_criacao',
								'caminho',
								'caminho_mini',
							))
							,
							"hosts_arquivos",
							"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
						);
						
						if($arquivos){
							$found = true;
							
							if($arquivos[0]['caminho_mini']){
								// ===== Carregar domínio do host.
								
								gestor_incluir_biblioteca('host');
								$dominio = host_url(Array(
									'opcao' => 'full',
								));
								
								// ===== Montar imgSrc.
								
								$imgSrc = $dominio . $arquivos[0]['caminho_mini'];
							} else {
								$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
							}
							
							$data = interface_formatar_dado(Array('dado' => $arquivos[0]['data_criacao'], 'formato' => 'dataHora'));
							$nome = $arquivos[0]['nome'];
							$tipo = $arquivos[0]['tipo'];
							
							$fileId = $id_hosts_arquivos;
						}
					}
					
					if(!$found){
						$fileId = $imagepickJS['padroes']['fileId'];
						$nome = $imagepickJS['padroes']['nome'];
						$data = $imagepickJS['padroes']['data'];
						$tipo = $imagepickJS['padroes']['tipo'];
						$imgSrc = $imagepickJS['padroes']['imgSrc'];
					}
					
					// ===== Alterar os dados do widget
					
					$imagepick = modelo_var_troca($imagepick,"#cont-id#",$campo['id']);
					$imagepick = modelo_var_troca($imagepick,"#campo-nome#",$campo['nome']);
					
					$imagepick = modelo_var_troca_tudo($imagepick,"#file-id#",$fileId);
					$imagepick = modelo_var_troca($imagepick,"#nome#",$nome);
					$imagepick = modelo_var_troca($imagepick,"#tipo#",$tipo);
					$imagepick = modelo_var_troca($imagepick,"#data#",$data);
					$imagepick = modelo_var_troca($imagepick,"#img-src#",$imgSrc);
					
					// ===== Incluir o imagepick na página
					
					if(isset($pagina)){
						$pagina = modelo_var_troca($pagina,'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					} else {
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					}
					
					// ===== Atualizar variável javascript
					
					$_GESTOR['javascript-vars']['interface']['imagepick'] = $imagepickJS;
					
					// ===== Incluir o componente iframe modal
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-iframe',
							'modal-alerta',
						)
					));
					
					// ===== Se precisar retornar a página.
				
					if(isset($pagina)){
						return $pagina;
					}
				break;
				case 'templates-hosts':
					// ===== Ler o layout do template pick
					
					$templatePick = gestor_componente(Array(
						'id' => 'widget-template',
						'modulosExtra' => Array(
							'interface',
						),
					));
					
					// ===== Pegar a categoria
					
					$categorias = banco_select_name
					(
						banco_campos_virgulas(Array(
							'nome',
							'id_categorias',
						))
						,
						"categorias",
						"WHERE status!='D'"
						." AND id='".$campo['categoria_id']."'"
					);
					
					if(!$categorias){
						$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #1');
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
						return;
					}
					
					$id_categorias = $categorias[0]['id_categorias'];
					
					// ===== Definir valores padrões
					
					$templatesJS['modal'] = Array(
						'head' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-modal-head')),
						'cancel' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-modal-cancel')),
						'url' => $_GESTOR['url-full'] . 'templates/seletores/?paginaIframe=sim&modelo=paginas&categoria_id='.$campo['categoria_id'],
					);
					
					// ===== Se template_id e template_tipo foram enviados, baixar dados do banco, senão definir valores padrões.
					
					$found = false;
					if(isset($campo['template_id']) && isset($campo['template_tipo'])){
						// ===== Verificar se o tipo enviado é o correto, senão devolver erro.
						
						switch($campo['template_tipo']){
							case 'gestor':
								$templates = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id',
										'nome',
										'id_arquivos_Imagem',
										'data_modificacao',
									))
									,
									"templates",
									"WHERE status!='D'"
									." AND id='".$campo['template_id']."'"
								);
								
								$tipoLabelID = 'template-gestor-label';
							break;
							case 'hosts':
								$templates = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id',
										'nome',
										'id_hosts_arquivos_Imagem',
										'data_modificacao',
									))
									,
									"hosts_templates",
									"WHERE status!='D'"
									." AND id='".$campo['template_id']."'"
								);
								
								$tipoLabelID = 'template-custom-label';
							break;
							default:
								$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #3');
								$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
								return;
						}
						
						if(!$templates){
							$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #4');
							$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
							return;
						}
						
						// ===== Formatar os campos principais.
						
						$templateId = $templates[0]['id'];
						$templateTipo = $campo['template_tipo'];
						
						$nome = $templates[0]['nome'];
						$tipo = gestor_variaveis(Array('modulo' => 'templates','id' => $tipoLabelID));
						$data = interface_formatar_dado(Array('dado' => $templates[0]['data_modificacao'], 'formato' => 'dataHora'));
						
						// ===== Se existir pegar o caminho do arquivo mini.
						
						switch($campo['template_tipo']){
							case 'gestor':
								$id_arquivos = $templates[0]['id_arquivos_Imagem'];
								
								if(existe($id_arquivos)){
									$resultado = banco_select_name(
										banco_campos_virgulas(Array(
											'caminho_mini',
										)),
										"arquivos",
										"WHERE id_arquivos='".$id_arquivos."'"
									);
									
									if($resultado){
										if(existe($resultado[0]['caminho_mini'])){
											$caminho_mini = $resultado[0]['caminho_mini'];
										}
									}
								}
								
								// ===== Domínio completo do Gestor.
						
								$dominio = $_GESTOR['url-full'];
							break;
							case 'hosts':
								$id_hosts_arquivos = $templates[0]['id_hosts_arquivos_Imagem'];
								
								if(existe($id_hosts_arquivos)){
									$resultado = banco_select_name(
										banco_campos_virgulas(Array(
											'caminho_mini',
										)),
										"hosts_arquivos",
										"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
									);
									
									if($resultado){
										if(existe($resultado[0]['caminho_mini'])){
											$caminho_mini = $resultado[0]['caminho_mini'];
										}
									}
								}
								
								// ===== Carregar domínio do host.
								
								gestor_incluir_biblioteca('host');
								$dominio = host_url(Array(
									'opcao' => 'full',
								));
						}
					} else {
						// ===== Pegar template 'padrao' da categoria_id.
						
						$templates = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id',
								'nome',
								'id_arquivos_Imagem',
								'data_modificacao',
							))
							,
							"templates",
							"WHERE status!='D'"
							." AND id_categorias='".$id_categorias."'"
							." AND padrao IS NOT NULL"
						);
						
						if(!$templates){
							$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #2');
							$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
							return;
						}
						
						// ===== Formatar os campos principais.
						
						$templateId = $templates[0]['id'];
						$templateTipo = 'gestor';
						
						$nome = $templates[0]['nome'];
						$tipo = gestor_variaveis(Array('modulo' => 'templates','id' => 'template-gestor-label'));
						$data = interface_formatar_dado(Array('dado' => $templates[0]['data_modificacao'], 'formato' => 'dataHora'));
						
						// ===== Se existir pegar o caminho do arquivo mini.
						
						$id_arquivos = $templates[0]['id_arquivos_Imagem'];
						
						if(existe($id_arquivos)){
							$resultado = banco_select_name(
								banco_campos_virgulas(Array(
									'caminho_mini',
								)),
								"arquivos",
								"WHERE id_arquivos='".$id_arquivos."'"
							);
							
							if($resultado){
								if(existe($resultado[0]['caminho_mini'])){
									$caminho_mini = $resultado[0]['caminho_mini'];
								}
							}
						}
						
						// ===== Domínio completo do Gestor.
				
						$dominio = $_GESTOR['url-full'];
					}
					
					// ===== Imagem Mini padrão ou Imagem Referência.
					
					if(existe($caminho_mini)){
						$imgSrc = $dominio . $caminho_mini;
					} else {
						$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
					}
					
					// ===== Alterar os dados do widget
					
					$templatePick = modelo_var_troca($templatePick,"#cont-id#",$campo['id']);
					$templatePick = modelo_var_troca($templatePick,"#campo-nome#",$campo['nome'].'_id');
					$templatePick = modelo_var_troca($templatePick,"#campo-tipo#",$campo['nome'].'_tipo');
					
					$templatePick = modelo_var_troca($templatePick,"#template-id#",$templateId);
					$templatePick = modelo_var_troca($templatePick,"#template-tipo#",$templateTipo);
					$templatePick = modelo_var_troca($templatePick,"#nome#",$nome);
					$templatePick = modelo_var_troca($templatePick,"#tipo#",$tipo);
					$templatePick = modelo_var_troca($templatePick,"#data#",$data);
					$templatePick = modelo_var_troca($templatePick,"#img-src#",$imgSrc);
					
					// ===== Incluir o templatePick na página
					
					$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$templatePick);
					
					// ===== Atualizar variável javascript
					
					$_GESTOR['javascript-vars']['interface']['templates'] = $templatesJS;
					
					// ===== Incluir o componente iframe modal
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-iframe',
						)
					));
				break;
			}
		}
	}
}

function interface_formulario_validacao($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// validacao - Array - Opcional - Conjunto de todas as validações com o campo e regra necessária para aplicação.
		// regra - String - Obrigatório - Identificador da regra que será implementada na validação do formulário.
		// campo - String - Obrigatório - Nome do campo do formulário onde a regra será aplicada.
		// label - String - Obrigatório - Label do campo do formulário onde a regra será aplicada.
		// identificador - String - Opcional - Identificador do campo caso seja necessário referenciar o nome diferente do campo.
		// removerRegra - Array - Opcional - Conjunto de todas as regras que deseja remover das regras padrões.
		
	// Se regra = 'email-comparacao'
		
		// comparcao - Array - Obrigatório - Conjunto de todos os dados de comparação.
			// id - String - Obrigatório - Identificador do alvo da comparação.
			// campo-1 - String - Obrigatório - Label do campo 1 para mostrar o erro caso houver.
			// campo-2 - String - Obrigatório - Label do campo 2 para mostrar o erro caso houver.
		
	// regrasExtra - Array - Opcional - Conjunto de todas as regras extras além das padrões.
		// regra - String - Obrigatório - Identificador da regra que será implementada na validação do formulário.
	
	// Se regra = 'regexPermited'	
		
		// regex - String - Obrigatório - Regex que será usado pelo validador de formulário.
		// regexPermitedChars - String - Obrigatório - Caracteres permitidos que será mostrado junto com a mensagem de erro.
	
	// Se regra = 'regexNecessary'	
		
		// regex - String - Obrigatório - Regex que será usado pelo validador de formulário.
		// regexNecessaryChars - String - Obrigatório - Caracteres necessários que será mostrado junto com a mensagem de erro.
	
	// ===== 
	
	if(isset($validacao)){
		foreach($validacao as $regra){
			switch($regra['regra']){
				case 'maior-ou-igual-a-zero':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
						)
					);
				break;
				case 'nao-vazio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
						)
					);
				break;
				case 'texto-obrigatorio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[3]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
						)
					);
				break;
				case 'texto-obrigatorio-verificar-campo':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-verify-field'));
					$prompt[4] = modelo_var_troca_tudo($prompt[4],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[3]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
						)
					);
					
					if(isset($regra['identificador'])){
						$validarCampos[$regra['identificador']] = Array(
							'prompt' => $prompt[4],
							'campo' => $regra['campo'],
						);
					} else {
						$validarCampos[$regra['campo']] = Array(
							'prompt' => $prompt[4],
						);
					}
				break;
				case 'selecao-obrigatorio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-select'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
						)
					);
				break;
				case 'email':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'email',
								'prompt' => $prompt[2],
							),
						)
					);
				break;
				case 'senha':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length-password'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-password-chars'));
					$prompt[4] = modelo_var_troca($prompt[4],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[12]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
							Array(
								'type' => 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])/]',
								'prompt' => $prompt[4],
							),
						)
					);
				break;
				case 'dominio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-domain'));
					$prompt[4] = modelo_var_troca($prompt[4],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'empty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[3]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[255]',
								'prompt' => $prompt[3],
							),
							Array(
								'type' => 'regExp[/^((?:(?:(?:\w[\.\-\+]?)*)\w)+)((?:(?:(?:\w[\.\-\+]?){0,62})\w)+)\.(\w{2,6})$/]',
								'prompt' => $prompt[4],
							),
						)
					);
				break;
				case 'email-comparacao':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'empty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'email',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[3],
									),
								)
							);
						}
					}
				break;
				case 'senha-comparacao':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length-password'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
							$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
							
							$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[4] = modelo_var_troca($prompt[4],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[4] = modelo_var_troca($prompt[4],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$prompt[5] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-password-chars'));
							$prompt[5] = modelo_var_troca($prompt[5],"#label#",$regra['label']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'empty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'minLength[12]',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'maxLength[100]',
										'prompt' => $prompt[3],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[4],
									),
									Array(
										'type' => 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])/]',
										'prompt' => $prompt[5],
									),
								)
							);
						}
					}
				break;
				case 'email-comparacao-verificar-campo':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-verify-field'));
							$prompt[4] = modelo_var_troca_tudo($prompt[4],"#label#",$regra['label']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'empty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'email',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[3],
									),
								)
							);
							
							if(isset($regra['identificador'])){
								$validarCampos[$regra['identificador']] = Array(
									'prompt' => $prompt[4],
									'campo' => $regra['campo'],
								);
							} else {
								$validarCampos[$regra['campo']] = Array(
									'prompt' => $prompt[4],
								);
							}
						}
					}
				break;
			}
			
			if(isset($regra['regrasExtra'])){
				$regrasExtra = $regra['regrasExtra'];
				foreach($regrasExtra as $regraExtra){
					switch($regraExtra['regra']){
						case 'regexPermited':
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-regex-permited-chars'));
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#label#",$regra['label']);
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#permited-chars#",$regraExtra['regexPermitedChars']);
							
							$regras_validacao[$regra['campo']]['rules'][] = Array(
								'type' => 'regExp['.$regraExtra['regex'].']',
								'prompt' => $prompt[1],
							);
						break;
						case 'regexNecessary':
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-regex-necessary-chars'));
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#label#",$regra['label']);
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#necessary-chars#",$regraExtra['regexNecessaryChars']);
							
							$regras_validacao[$regra['campo']]['rules'][] = Array(
								'type' => 'regExp['.$regraExtra['regex'].']',
								'prompt' => $prompt[1],
							);
						break;
					}
				}
			}
			
			if(isset($regra['removerRegra'])){
				$rules = $regras_validacao[$regra['campo']]['rules'];
				unset($rulesAux);
				
				foreach($rules as $rule){
					$removeuRegra = false;
					foreach($regra['removerRegra'] as $removerRegra){
						if($rule['type'] == $removerRegra){
							$removeuRegra = true;
							break;
						}
					}
					
					if(!$removeuRegra){
						$rulesAux[] = $rule;
					}
				}
				
				if(isset($rulesAux)){
					$regras_validacao[$regra['campo']]['rules'] = $rulesAux;
				}
			}
			
			if(isset($regra['identificador'])){
				$regras_validacao[$regra['campo']]['identifier'] = $regra['identificador'];
			}
		}
		
		// ===== Inclui as regras de validação no javascript
		
		if(isset($regras_validacao)){
			if(!isset($_GESTOR['javascript-vars']['interface'])){
				$_GESTOR['javascript-vars']['interface'] = Array();
			}
		
			$_GESTOR['javascript-vars']['interface']['regrasValidacao'] = $regras_validacao;
		}
		
		if(isset($validarCampos)){
			if(!isset($_GESTOR['javascript-vars']['interface'])){
				$_GESTOR['javascript-vars']['interface'] = Array();
			}
		
			$_GESTOR['javascript-vars']['interface']['validarCampos'] = $validarCampos;
		}
	}
}

function interface_validacao_campos_obrigatorios($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// redirect - String - Opcional - URL que será redirecionada caso necessário alterar a forma padrão de redirecionamento.
	// campos - Array - Opcional - Conjunto de todos os campos obrigatórios.
		// regra - String - Obrigatório - Identificador da regra que será implementada na validação do campo.
		// campo - String - Obrigatório - Nome do campo do onde a regra será aplicada.
		// label - String - Obrigatório - Label do campo do onde a regra será aplicada.
		
	// Se regra = 'texto-obrigatorio'
		// min - Int - Opcional - Mínimo de caracteres.
		// max - Int - Opcional - Máximo de caracteres.
	
	// ===== 
	
	if(isset($campos)){
		foreach($campos as $campo){
			switch($campo['regra']){
				case 'texto-obrigatorio':
					$min = (isset($campo['min']) ? $campo['min'] : 3);
					$max = (isset($campo['max']) ? $campo['max'] : 100);
					
					$len = strlen($_REQUEST[$campo['campo']]);
					
					if($len < $min){
						$naoValidou = true;
					} else if($len > $max){
						$naoValidou = true;
					}
					
					if(isset($naoValidou)){
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-max-length'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#min#",$min);
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#max#",$max);
					}
				break;
				case 'selecao-obrigatorio':
					if(!existe($_REQUEST[$campo['campo']])){
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-select'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
						
						$naoValidou = true;
					}
				break;
				case 'email-obrigatorio':
					$email = $_REQUEST[$campo['campo']];
					$regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@([a-z0-9-]{2,})+(\.[a-z0-9-]{2,})*$/';
					
					if(!preg_match($regex, $email)){
						$naoValidou = true;
						
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
					}
				break;
			}
			
			if(isset($naoValidou)){
				break;
			}
		}
	}
	
	if(isset($naoValidouMsgAlerta)){
		interface_alerta(Array('msg' => $naoValidouMsgAlerta));
	}
	
	if(isset($naoValidou)){
		if(isset($redirect)){
			gestor_redirecionar($redirect);
		} else {
			gestor_reload_url();
		}
	}
}

function interface_modulo_variavel_valor($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variavel - String - Obrigatório - Nome da variável que se pretende pegar valor.
	
	// ===== 
	
	if(!isset($variavel)){
		interface_alerta(Array('msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'problem-get-variable'))));
		gestor_redirecionar_raiz();
	}
	
	if(!isset($_GESTOR['modulo-registro-id'])){
		interface_alerta(Array('msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'problem-get-variable'))));
		gestor_redirecionar_raiz();
	}
	
	if(!isset($_GESTOR['modulo#'.$_GESTOR['modulo-id']])){
		interface_alerta(Array('msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'problem-get-variable'))));
		gestor_redirecionar_raiz();
	}
	
	if(isset($_GESTOR['modulo-registro-'.$variavel])){
		return $_GESTOR['modulo-registro-'.$variavel];
	}
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			$variavel,
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$_GESTOR['modulo-registro-id']."'"
		." AND ".(isset($modulo['tabela']['status'])?$modulo['tabela']['status']:'status')."!='D'"
		.(isset($_GESTOR['host-id']) && !isset($modulo['interfaceNaoAplicarIdHost']) ? " AND id_hosts='".$_GESTOR['host-id']."'":'')
	);
	
	if($resultado){
		$_GESTOR['modulo-registro-'.$variavel] = $resultado[0][$variavel];
	}
	
	if(isset($_GESTOR['modulo-registro-'.$variavel])){
		return $_GESTOR['modulo-registro-'.$variavel];
	} else {
		interface_alerta(Array('msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'problem-get-variable'))));
		gestor_redirecionar_raiz();
	}
}

function interface_backup_campo_incluir($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// campo - String - Obrigatório - Nome do campo.
	// id_numerico - Int - Obrigatório - Identificador numérico.
	// versao - Int - Obrigatório - Número da versão do campo.
	// valor - String - Obrigatório - Valor do campo a ser guardado.
	// modulo - String - Opcional - Definir o nome do módulo caso o mesmo seja diferente do módulo atual.
	// maxCopias - Int - Opcional - Definir o máximo de cópias do mesmo valor ficará retido no banco. Se houver mais recursos o mais antigo será removido.
	
	// ===== 
	
	if(isset($campo) && isset($id_numerico) && isset($versao) && isset($valor)){
		// ===== Definição de valores padrões
		
		if(!isset($modulo)){
			$modulo = $_GESTOR['modulo-id'];
		}
		
		if(!isset($maxCopias)){
			$maxCopias = 20;
		}
		
		// ===== Incluir o backup
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "modulo"; $campo_valor = $modulo; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = $id_numerico; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "versao"; $campo_valor = $versao; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "campo"; $campo_valor = $campo; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "valor"; $campo_valor = banco_escape_field($valor); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$campo_nome = 'data'; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"backup_campos"
		);
		
		// ===== Remover cópias antigas maior que o maxCopias
		
		$backup_campos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_backup_campos',
			))
			,
			"backup_campos",
			"WHERE id='".$id_numerico."'"
			." AND modulo='".$modulo."'"
			." AND campo='".$campo."'"
			." ORDER BY data ASC"
		);
		
		if($backup_campos){
			$total = count($backup_campos);
			
			if($total > $maxCopias){
				banco_delete
				(
					"backup_campos",
					"WHERE id_backup_campos='".$backup_campos[0]['id_backup_campos']."'"
				);
			}
		}
	}
}

function interface_backup_campo_select($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// campo - String - Obrigatório - Nome do campo.
	// campo_form - String - Opcional - Nome do campo do formulario caso o mesmo seja diferente do campo no banco de dados.
	// callback - String - Obrigatório - Nome do evento callback que será disparado em caso de sucesso.
	// id_numerico - Int - Obrigatório - Identificador numérico.
	// modulo - String - Opcional - Definir o nome do módulo caso o mesmo seja diferente do módulo atual.
	
	// ===== 
	
	if(isset($campo) && isset($id_numerico)){
		// ===== Definição de valores padrões
		
		if(!isset($modulo)){
			$modulo = $_GESTOR['modulo-id'];
		}
		
		if(!isset($campo_form)){
			$campo_form = $campo;
		}
		
		if(!isset($callback)){
			$callback = 'callBackNotSet';
		}
		
		// ===== Buscar todos os backups de um campo
		
		$backup_campos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_backup_campos',
				'versao',
				'data',
			))
			,
			"backup_campos",
			"WHERE id='".$id_numerico."'"
			." AND modulo='".$modulo."'"
			." AND campo='".$campo."'"
			." ORDER BY data DESC"
		);
		
		if($backup_campos){
			$dropdown = gestor_componente(Array(
				'id' => 'interface-backup-dropdown',
			));
			
			$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($dropdown,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $dropdown = modelo_tag_in($dropdown,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			$dropdown = modelo_var_troca($dropdown,"#id-numerico#",$id_numerico);
			$dropdown = modelo_var_troca($dropdown,"#campo#",$campo);
			$dropdown = modelo_var_troca($dropdown,"#campo_form#",$campo_form);
			$dropdown = modelo_var_troca($dropdown,"#callback#",$callback);
			
			// ===== Versão atual sendo primeiro opção
			
			$dropdown = modelo_var_troca($dropdown,"#versao-atual-label#",'Versão Atual Selecionada');
			$dropdown = modelo_var_troca($dropdown,"#versao-atual-description#",'Versão '.interface_modulo_variavel_valor(Array('variavel' => $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela']['versao'])));
			$dropdown = modelo_var_troca($dropdown,"#versao-atual-icon#",'file alternate');
			
			// ===== Versão atual opção para aparecer devido limitação do dropdown
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#id#",'');
			$cel_aux = modelo_var_troca($cel_aux,"#data#",'Versão Atual Selecionada');
			$cel_aux = modelo_var_troca($cel_aux,"#versao#",'Versão '.interface_modulo_variavel_valor(Array('variavel' => $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela']['versao'])));
			$cel_aux = modelo_var_troca($cel_aux,"#icon#",'file alternate');
			
			$dropdown = modelo_var_in($dropdown,'<!-- '.$cel_nome.' -->',$cel_aux);
			
			// ===== Todos os backups disponíveis
			
			foreach($backup_campos as $backup){
				$data = interface_formatar_dado(Array(
					'dado' => $backup['data'],
					'formato' => 'dataHora',
				));
				
				$versao = ((int)$backup['versao'] - 1);
				$versao = 'Versão '.$versao;
				
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#id#",$backup['id_backup_campos']);
				$cel_aux = modelo_var_troca($cel_aux,"#data#",$data);
				$cel_aux = modelo_var_troca($cel_aux,"#versao#",$versao);
				$cel_aux = modelo_var_troca($cel_aux,"#icon#",'file alternate outline');
				
				$dropdown = modelo_var_in($dropdown,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$dropdown = modelo_var_troca($dropdown,'<!-- '.$cel_nome.' -->','');
			
			return $dropdown;
		} else {
			return '';
		}
	} else {
		return '';
	}
}

function interface_verificar_campos($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Parâmetros
	
	// campo - String - Obrigatório - Nome do campo do banco de dados.
	// valor - String - Obrigatório - Valor do campo do banco de dados.
	
	// ===== 
	
	if(isset($campo) && isset($valor)){
		// ===== Se houver verificarCamposOutraTabela pegar os dados da tabela deste valor, senão o padrão.
		
		$outraTabela = false;
		if(isset($modulo['verificarCamposOutraTabela'])){
			if(isset($modulo['verificarCamposOutraTabela'][$campo])){
				$tabela = $modulo['verificarCamposOutraTabela'][$campo];
				$outraTabela = true;
			}
		}
		
		if(!$outraTabela){
			$tabela = $modulo['tabela'];
		}
		
		// ===== Verificar na tabela indicada, se existe o campo.
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				$tabela['id_numerico'],
			))
			,
			$tabela['nome'],
			"WHERE ".$campo."='".banco_escape_field($valor)."'"
			.(isset($tabela['where']) ? " AND ".$tabela['where'] : "" )
			." AND ".$tabela['status']."!='D'"
			.($_GESTOR['opcao'] == 'editar' ? ' AND '.$tabela['id']."!='".$_GESTOR['modulo-registro-id']."'" : '')
		);
		
		if($resultado){
			return true;
		}
	}
	
	return false;
}

function interface_botoes_cabecalho($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$botoes_html = '';
	
	foreach($botoes as $id => $botao){
		switch($id){
			case 'excluir':
				$botoes_html .= '
		<div class="ui button excluir '.$botao['cor'].'" data-href="'.$botao['url'].'" data-content="'.$botao['tooltip'].'" data-id="'.$id.'">
			<i class="'.$botao['icon'].' icon"></i>
			'.$botao['rotulo'].'
		</div>';
			break;
			default:
				if(isset($botao['callback'])){
					$botoes_html .= '
			<div class="ui button '.$botao['callback'].' '.$botao['cor'].'" data-content="'.$botao['tooltip'].'" data-id="'.$id.'">
				<i class="'.$botao['icon'].' icon"></i>
				'.$botao['rotulo'].'
			</div>';
				} else {
					$botoes_html .= '
			<a class="ui button '.$botao['cor'].'" href="'.$botao['url'].'" data-content="'.$botao['tooltip'].'" data-id="'.$id.'"'.(isset($botao['target']) ? ' target="'.$botao['target'].'"':'').'>
				'.(isset($botao['icon2']) ? '<i class="icons"><i class="'.$botao['icon'].' icon"></i><i class="'.$botao['icon2'].' icon"></i></i>' : '<i class="'.$botao['icon'].' icon"></i>').'
				'.$botao['rotulo'].'
			</a>';
			}
		}
	}
	
	return $botoes_html;
}

// ===== Interfaces ajax

function interface_ajax_backup_campo($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// campo - String - Obrigatório - Nome do campo.
	// id_numerico - Int - Obrigatório - Identificador numérico.
	// modulo - String - Opcional - Definir o nome do módulo caso o mesmo seja diferente do módulo atual.
	
	// ===== 
	
	if(isset($_REQUEST['id'])) $id = banco_escape_field($_REQUEST['id']);
	if(isset($_REQUEST['id_numerico'])) $id_numerico = banco_escape_field($_REQUEST['id_numerico']);
	if(isset($_REQUEST['campo'])) $campo = banco_escape_field($_REQUEST['campo']);
	
	if(isset($campo) && isset($id_numerico)){
		// ===== Definição de valores padrões
		
		if(!isset($modulo)){
			$modulo = $_GESTOR['modulo-id'];
		}
		
		// ===== Se id definido, retorna o campo do backup, senão retorna o valor do campo atual.
		
		if(existe($id)){
			$backup_campos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'valor',
				))
				,
				"backup_campos",
				"WHERE id='".$id_numerico."'"
				." AND modulo='".$modulo."'"
				." AND campo='".$campo."'"
				." AND id_backup_campos='".$id."'"
			);
			
			$valor = $backup_campos[0]['valor'];
		} else {
			if(!isset($_GESTOR['modulo-registro-id'])){
				$_GESTOR['ajax-json'] = Array(
					'status' => 'idRecordNotFound'
				);
			} else {
				$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						$campo,
					))
					,
					$modulo['tabela']['nome'],
					"WHERE ".$modulo['tabela']['id']."='".$_GESTOR['modulo-registro-id']."'"
					." AND ".$modulo['tabela']['status']."!='D'"
				);
			}
			
			$valor = $resultado[0][$campo];
		}
		
		// ===== Variaveis globais alterar.
		
		if(isset($valor)){
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			$openText = $_GESTOR['variavel-global']['openText'];
			$closeText = $_GESTOR['variavel-global']['closeText'];
			
			$valor = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $valor);
		}
		
		// ===== Retornar dados.
		
		$_GESTOR['ajax-json'] = Array(
			'status' => 'Ok',
			'valor' => (isset($valor) ? stripslashes($valor) : ''),
		);
	} else {
		$_GESTOR['ajax-json'] = Array(
			'status' => 'mandatoryFieldsNotSent'
		);
	}
}

function interface_ajax_historico_mais_resultados(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'pagina' => interface_historico(Array(
			'sem_id' => (isset($_REQUEST['sem_id']) ? true : null ),
			'id' => (isset($_REQUEST['id']) ? $_REQUEST['id'] : '' ),
			'modulo' => $_GESTOR['modulo-id'],
			'pagina' => '#historico#',
		))
	);
}

function interface_ajax_listar(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = interface_listar_ajax();
}

function interface_ajax_verificar_campo(){
	global $_GESTOR;
	
	if(!isset($_GESTOR['usuario-token-id'])){
		gestor_roteador_erro(Array(
			'codigo' => 401,
			'ajax' => $_GESTOR['ajax'],
		));
	}
	
	$campoExiste = interface_verificar_campos(Array(
		'campo' => banco_escape_field($_REQUEST['campo']),
		'valor' => banco_escape_field($_REQUEST['valor']),
	));
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'campoExiste' => $campoExiste,
	);
}

// ===== Interfaces principais

function interface_excluir_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['id']);
	}
	
	if(!isset($_GESTOR['modulo-registro-id'])){
		gestor_redirecionar_raiz();
	}
}

function interface_excluir_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// banco - Array - Opcional - Conjunto de dados para a formação dos dados vindos do banco de dados caso queira ser diferente do módulo.
		// nome - String - Obrigatório - Nome da tabela dos dados no banco de dados.
		// id - String - Obrigatório - Nome do identificador principal da tabela dos dados no banco de dados.
		// status - String - Obrigatório - Nome do campo de status do banco de dados para ativar/desativar registro.
		// where - String - Opcional - Conjunto de condicionais da clausúla WHERE do SQL da consulta ao banco de dados.
	// historico - Bool - Opcional - Desativar inclusão no histórico na deleção.
	// callbackFunction - String - Opcional - Nome da função callback que será disparada depois da deleção.
	
	// ===== 
	
	// ===== Identificador do registro
	
	$id = $_GESTOR['modulo-registro-id'];
	
	if(!isset($banco)){
		$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
		
		$banco = $modulo['tabela'];
	}
	
	// ===== Guardar o identificador númerico do dado antes da deleção para posterior uso no 'callbackFunction' como referência única do registro.
	
	if(isset($callbackFunction)){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				$banco['id_numerico'],
			))
			,
			$banco['nome'],
			"WHERE ".$banco['id']."='".$id."'"
			." AND ".$banco['status']."!='D'"
			.(isset($banco['where']) ? " AND ".$banco['where'] : "" )
		);
		
		$_GESTOR['modulo-registro-id-numerico'] = $resultado[0][$banco['id_numerico']];
	}
	
	// ===== Alterar o status do registro para 'D' - Deletado
	
	$campo_tabela = $banco['nome'];
	$campo_tabela_extra = "WHERE ".$banco['id']."='".$id."'".(isset($banco['where']) ? " AND ".$banco['where'] : "" )." AND ".$banco['status']."!='D'";
	
	$campo_nome = $banco['status']; $editar[$campo_tabela][] = $campo_nome."='D'";
	
	$campo_nome = "versao"; $editar[$campo_tabela][] = $campo_nome." = ".$campo_nome." + 1";
	$campo_nome = "data_modificacao"; $editar[$campo_tabela][] = $campo_nome."=NOW()";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	// ===== Incluir no histórico as alterações.
	
	if(!isset($historico)){
		interface_historico_incluir(Array(
			'alteracoes' => Array(
				Array(
					'alteracao' => 'historic-delete',
				)
			),
			'deletar' => true,
		));
	}
	
	// Executar deleção
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	// ===== Se a função callback for definida, executar a função específica.
	
	if(isset($callbackFunction)){
		call_user_func($callbackFunction);
	}
	
	// ===== Redireciona local específico ou então para a raiz do módulo
	
	if(isset($_REQUEST['redirect'])){
		gestor_redirecionar($_REQUEST['redirect']);
	} else {
		gestor_redirecionar_raiz();
	}
}

function interface_status_iniciar($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['id']);
	}
	
	if(isset($_REQUEST['status']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-status'] = banco_escape_field($_REQUEST['status']);
	}
	
	if(!isset($_GESTOR['modulo-registro-id']) || !isset($_GESTOR['modulo-registro-status'])){
		gestor_redirecionar_raiz();
	}
}

function interface_status_finalizar($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// banco - Array - Opcional - Conjunto de dados para a formação dos dados vindos do banco de dados caso queira ser diferente do módulo.
		// nome - String - Obrigatório - Nome da tabela dos dados no banco de dados.
		// id - String - Obrigatório - Nome do identificador principal da tabela dos dados no banco de dados.
		// status - String - Obrigatório - Nome do campo de status do banco de dados para ativar/desativar registro.
		// where - String - Opcional - Conjunto de condicionais da clausúla WHERE do SQL da consulta ao banco de dados.
	// historico - Bool - Opcional - Desativar inclusão no histórico na mudança de status.
	// callbackFunction - String - Opcional - Nome da função callback que será disparada depois da mudança de status.
	
	// ===== 
	
	$id = $_GESTOR['modulo-registro-id'];
	$mudar_status = $_GESTOR['modulo-registro-status'];
	
	if(!isset($banco)){
		$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
		
		$banco = $modulo['tabela'];
	}
	
	// ===== Alterar o status do registro conforme o 'mudar_status'.
	
	$campo_tabela = $banco['nome'];
	$campo_tabela_extra = "WHERE ".$banco['id']."='".$id."'".(isset($banco['where']) ? " AND ".$banco['where'] : "" )." AND ".$banco['status']."!='D'";
	
	$campo_nome = $banco['status']; $editar[$campo_tabela][] = $campo_nome."='" . $mudar_status . "'";
	
	$campo_nome = "versao"; $editar[$campo_tabela][] = $campo_nome." = ".$campo_nome." + 1";
	$campo_nome = "data_modificacao"; $editar[$campo_tabela][] = $campo_nome."=NOW()";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	// ===== Incluir no histórico as alterações.
	
	if(!isset($historico)){
		if($mudar_status == 'A'){
			$valor_depois = 'field-status-active';
		} else {
			$valor_depois = 'field-status-inactive';
		}
		
		if($mudar_status == 'A'){
			$valor_antes = 'field-status-inactive';
		} else {
			$valor_antes = 'field-status-active';
		}
		
		interface_historico_incluir(Array(
			'alteracoes' => Array(
				Array(
					'campo' => 'field-status',
					'alteracao' => 'historic-change-status',
					'valor_antes' => $valor_antes,
					'valor_depois' => $valor_depois,
				)
			),
		));
	}
	
	// ===== Se a função callback for definida, executar a função específica.
	
	if(isset($callbackFunction)){
		call_user_func($callbackFunction);
	}
	
	// ===== Redireciona local específico caso necessário.
	
	if(isset($_REQUEST['redirect'])){
		gestor_redirecionar($_REQUEST['redirect']);
	}
}

function interface_adicionar_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(isset($_REQUEST['_gestor-adicionar'])){
		$_GESTOR['adicionar-banco'] = true;
	}
}

function interface_adicionar_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-formulario-inclusao',
	));
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	$pagina = modelo_var_troca($pagina,"#form-id#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-name#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-action#",$_GESTOR['url-raiz'].$_GESTOR['caminho-total']);
	$pagina = modelo_var_troca($pagina,"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$pagina = modelo_var_troca($pagina,"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#form-page#",$_GESTOR['pagina']);
	
	// ===== Alterar linguagem das variáveis dos formulários
	
	$form_variaveis = gestor_variaveis(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'conjunto' => true,
		'padrao' => 'form',
	));
	
	if(isset($form_variaveis)){
		foreach($form_variaveis as $form_id => $form_val){
			$pagina = modelo_var_troca($pagina,"#".$form_id."#",$form_val);
		}
	}
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Formulários
	
	if(isset($formulario)){
		// ===== Formulário Validações
		
		if(isset($formulario['validacao'])){
			interface_formulario_validacao($formulario);
		}
		// ===== Formulário Campos
		
		if(isset($formulario['campos'])){
			interface_formulario_campos($formulario);
		}
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
}

function interface_adicionar_incomum_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(isset($_REQUEST['_gestor-adicionar'])){
		$_GESTOR['adicionar-banco'] = true;
	}
}

function interface_adicionar_incomum_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-formulario-inclusao-incomum',
	));
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	$pagina = modelo_var_troca($pagina,"#form-id#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-name#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-action#",$_GESTOR['url-raiz'].$_GESTOR['caminho-total']);
	$pagina = modelo_var_troca($pagina,"#form-opcao#",$_GESTOR['opcao']);
	$pagina = modelo_var_troca($pagina,"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$pagina = modelo_var_troca($pagina,"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#form-page#",$_GESTOR['pagina']);
	
	// ===== Alterar linguagem das variáveis dos formulários
	
	$form_variaveis = gestor_variaveis(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'conjunto' => true,
		'padrao' => 'form',
	));
	
	if(isset($form_variaveis)){
		foreach($form_variaveis as $form_id => $form_val){
			$pagina = modelo_var_troca($pagina,"#".$form_id."#",$form_val);
		}
	}
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Formulários
	
	if(isset($formulario)){
		// ===== Formulário Validações
		
		if(isset($formulario['validacao'])){
			interface_formulario_validacao($formulario);
		}
		// ===== Formulário Campos
		
		if(isset($formulario['campos'])){
			interface_formulario_campos($formulario);
		}
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
}

function interface_editar_incomum_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// forcarId - String - Opcional - Caso queira usar a opção editar diretamente passando o id do dado.
	
	// ===== 
	
	if(isset($_REQUEST['_gestor-registro-id'])){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['_gestor-registro-id']);
	}
	
	if(isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['id']);
	}
	
	if(isset($forcarId) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = $forcarId;
	}
	
	if(!isset($_GESTOR['modulo-registro-id'])){
		gestor_redirecionar_raiz();
	}
	
	if(isset($_REQUEST['_gestor-atualizar'])){
		$_GESTOR['atualizar-banco'] = true;
	}
}

function interface_editar_incomum_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	// metaDados - Array - Opcional - Conjunto de meta dados de um registro.
		// titulo - String - Obrigatório - Título informativo do meta dado.
		// dado - String - Obrigatório - A informação meta dado.
		
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	
	// removerNaoAlterarId - Bool - Opcional - Remover o checkbox de não alterar id.
	// removerBotaoEditar - Bool - Opcional - Remover o botão editar quando não convir usar o mesmo.
	
	// ===== 
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-delecao',
			'modal-alerta',
		)
	));
	
	// ===== Formulário de edição
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-formulario-edicao-incomum',
	));
	
	// ===== Remover não alterar id
	
	if(isset($removerNaoAlterarId)){
		$cel_nome = 'nao-alterar-id'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Popular toda as variáveis do layout.
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$_GESTOR['pagina#titulo-extra'] = interface_modulo_variavel_valor(Array('variavel' => (isset($modulo['tabela']['nome_especifico']) ? $modulo['tabela']['nome_especifico'] : 'nome'))).' - ';
	
	$pagina = modelo_var_troca($pagina,"#form-id#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-name#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-action#",$_GESTOR['url-raiz'].$_GESTOR['caminho-total']);
	$pagina = modelo_var_troca($pagina,"#form-registro-id#",$_GESTOR['modulo-registro-id']);
	$pagina = modelo_var_troca($pagina,"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$pagina = modelo_var_troca($pagina,"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	$pagina = modelo_var_troca($pagina,"#form-nao-alterar-id-label#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-nao-alterar-id-label')));
	
	// ===== Remover/Manter botão editar
	
	if(isset($removerBotaoEditar)){
		$cel_nome = 'botao-editar'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Botões principais
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#form-page#",$_GESTOR['pagina']);
	
	// ===== Alterar meta dados caso houver
	
	if(isset($metaDados)){
		$cel_nome = 'cel-th'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		$cel_nome = 'cel-td'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		foreach($metaDados as $meta){
			$cel_nome = 'cel-th'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-titulo#",$meta['titulo']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			
			$cel_nome = 'cel-td'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-dado#",$meta['dado']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$cel_nome = 'cel-th'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		$cel_nome = 'cel-td'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		$cel_nome = 'meta-dados'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Alterar linguagem das variáveis dos formulários
	
	$form_variaveis = gestor_variaveis(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'conjunto' => true,
		'padrao' => 'form',
	));
	
	if(isset($form_variaveis)){
		foreach($form_variaveis as $form_id => $form_val){
			$pagina = modelo_var_troca($pagina,"#".$form_id."#",$form_val);
		}
	}
	
	// ===== Mostrar histórico na página caso houver.
	
	$pagina = interface_historico(Array(
		'id' => $id,
		'modulo' => $_GESTOR['modulo-id'],
		'pagina' => $pagina,
	));
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Formulários
	
	if(isset($formulario)){
		// ===== Formulário Validações
		
		if(isset($formulario['validacao'])){
			interface_formulario_validacao($formulario);
		}
		// ===== Formulário Campos
		
		if(isset($formulario['campos'])){
			interface_formulario_campos($formulario);
		}
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
	
	$_GESTOR['javascript-vars']['moduloRegistroId'] = $_GESTOR['modulo-registro-id'];
}

function interface_editar_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// forcarId - String - Opcional - Caso queira usar a opção editar diretamente passando o id do dado.
	
	// ===== 
	
	if(isset($_REQUEST['_gestor-registro-id'])){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['_gestor-registro-id']);
	}
	
	if(isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['id']);
	}
	
	if(isset($forcarId) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = $forcarId;
	}
	
	if(!isset($_GESTOR['modulo-registro-id'])){
		gestor_redirecionar_raiz();
	}
	
	if(isset($_REQUEST['_gestor-atualizar'])){
		$_GESTOR['atualizar-banco'] = true;
	}
}

function interface_editar_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	// metaDados - Array - Opcional - Conjunto de meta dados de um registro.
		// titulo - String - Obrigatório - Título informativo do meta dado.
		// dado - String - Obrigatório - A informação meta dado.
		
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	
	// removerNaoAlterarId - Bool - Opcional - Remover o checkbox de não alterar id.
	// removerBotaoEditar - Bool - Opcional - Remover o botão editar quando não convir usar o mesmo.
	
	// ===== 
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-delecao',
			'modal-alerta',
		)
	));
	
	// ===== Formulário de edição
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-formulario-edicao',
	));
	
	// ===== Remover não alterar id
	
	if(isset($removerNaoAlterarId)){
		$cel_nome = 'nao-alterar-id'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Popular toda as variáveis do layout.
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	$_GESTOR['pagina#titulo-extra'] = interface_modulo_variavel_valor(Array('variavel' => (isset($modulo['tabela']['nome_especifico']) ? $modulo['tabela']['nome_especifico'] : 'nome'))).' - ';
	
	$pagina = modelo_var_troca($pagina,"#form-id#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-name#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-action#",$_GESTOR['url-raiz'].$_GESTOR['caminho-total']);
	$pagina = modelo_var_troca($pagina,"#form-registro-id#",$_GESTOR['modulo-registro-id']);
	$pagina = modelo_var_troca($pagina,"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$pagina = modelo_var_troca($pagina,"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	$pagina = modelo_var_troca($pagina,"#form-nao-alterar-id-label#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-nao-alterar-id-label')));
	
	// ===== Remover/Manter botão editar
	
	if(isset($removerBotaoEditar)){
		$cel_nome = 'botao-editar'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Botões principais
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#form-page#",$_GESTOR['pagina']);
	
	// ===== Alterar meta dados caso houver
	
	if(isset($metaDados)){
		$cel_nome = 'cel-th'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		$cel_nome = 'cel-td'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		foreach($metaDados as $meta){
			$cel_nome = 'cel-th'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-titulo#",$meta['titulo']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			
			$cel_nome = 'cel-td'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-dado#",$meta['dado']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$cel_nome = 'cel-th'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		$cel_nome = 'cel-td'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		$cel_nome = 'meta-dados'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Alterar linguagem das variáveis dos formulários
	
	$form_variaveis = gestor_variaveis(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'conjunto' => true,
		'padrao' => 'form',
	));
	
	if(isset($form_variaveis)){
		foreach($form_variaveis as $form_id => $form_val){
			$pagina = modelo_var_troca($pagina,"#".$form_id."#",$form_val);
		}
	}
	
	// ===== Mostrar histórico na página caso houver.
	
	$pagina = interface_historico(Array(
		'id' => $id,
		'modulo' => $_GESTOR['modulo-id'],
		'pagina' => $pagina,
	));
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Formulários
	
	if(isset($formulario)){
		// ===== Formulário Validações
		
		if(isset($formulario['validacao'])){
			interface_formulario_validacao($formulario);
		}
		// ===== Formulário Campos
		
		if(isset($formulario['campos'])){
			interface_formulario_campos($formulario);
		}
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
	
	$_GESTOR['javascript-vars']['moduloRegistroId'] = $_GESTOR['modulo-registro-id'];
}

function interface_visualizar_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// forcarId - String - Opcional - Caso queira usar a opção editar diretamente passando o id do dado.
	
	// ===== 
	
	if(isset($_REQUEST['_gestor-registro-id'])){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['_gestor-registro-id']);
	}
	
	if(isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['id']);
	}
	
	if(isset($forcarId) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = $forcarId;
	}
	
	if(!isset($_GESTOR['modulo-registro-id'])){
		gestor_redirecionar_raiz();
	}
}

function interface_visualizar_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// forcarSemID - Bool - Opcional - Caso definido, não utilizar ID do dado.
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	// metaDados - Array - Opcional - Conjunto de meta dados de um registro.
		// titulo - String - Obrigatório - Título informativo do meta dado.
		// dado - String - Obrigatório - A informação meta dado.
		
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	// campoTitulo - String - Opcional - Campo do título da página para referenciar o registro.
	
	// ===== 
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-delecao',
			'modal-alerta',
		)
	));
	
	// ===== Formulário de visualização
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-formulario-visualizacao',
	));
	
	// ===== Popular toda as variáveis do layout.
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	if(!isset($forcarSemID)){
		if(!isset($campoTitulo)){
			$campoTitulo = 'nome';
		}
		
		$_GESTOR['pagina#titulo-extra'] = interface_modulo_variavel_valor(Array('variavel' => $campoTitulo)).' - ';
	}
	
	// ===== Botões principais
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#page#",$_GESTOR['pagina']);
	
	// ===== Alterar meta dados caso houver
	
	if(isset($metaDados)){
		$cel_nome = 'cel-th'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		$cel_nome = 'cel-td'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		foreach($metaDados as $meta){
			$cel_nome = 'cel-th'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-titulo#",$meta['titulo']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			
			$cel_nome = 'cel-td'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-dado#",$meta['dado']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$cel_nome = 'cel-th'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		$cel_nome = 'cel-td'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		$cel_nome = 'meta-dados'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Mostrar histórico na página caso houver.
	
	if(!isset($forcarSemID)){
		$pagina = interface_historico(Array(
			'id' => $id,
			'modulo' => $_GESTOR['modulo-id'],
			'pagina' => $pagina,
		));
	}
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
	
	$_GESTOR['javascript-vars']['moduloRegistroId'] = $_GESTOR['modulo-registro-id'];
}

function interface_config_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	if(isset($_REQUEST['_gestor-atualizar'])){
		$_GESTOR['atualizar-banco'] = true;
	}
}

function interface_config_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	
	// removerBotaoEditar - Bool - Opcional - Remover o botão editar quando não convir usar o mesmo.
	
	// ===== 
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-delecao',
			'modal-alerta',
		)
	));
	
	// ===== Formulário de edição
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-formulario-configuracoes',
	));
	
	// ===== Popular toda as variáveis do layout.
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	$pagina = modelo_var_troca($pagina,"#form-id#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-name#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-action#",$_GESTOR['url-raiz'].$_GESTOR['caminho-total']);
	$pagina = modelo_var_troca($pagina,"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$pagina = modelo_var_troca($pagina,"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	
	// ===== Remover/Manter botão editar
	
	if(isset($removerBotaoEditar)){
		$cel_nome = 'botao-editar'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Botões principais
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#form-page#",$_GESTOR['pagina']);
	
	// ===== Mostrar histórico na página caso houver.
	
	$pagina = interface_historico(Array(
		'id' => $_GESTOR['modulo-id'],
		'modulo' => $_GESTOR['modulo-id'],
		'pagina' => $pagina,
		'sem_id' => true,
	));
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Formulários
	
	if(isset($formulario)){
		// ===== Formulário Validações
		
		if(isset($formulario['validacao'])){
			interface_formulario_validacao($formulario);
		}
		// ===== Formulário Campos
		
		if(isset($formulario['campos'])){
			interface_formulario_campos($formulario);
		}
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
}

function interface_alteracoes_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(isset($_REQUEST['_gestor-registro-id'])){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['_gestor-registro-id']);
	}
	
	if(isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		$_GESTOR['modulo-registro-id'] = banco_escape_field($_REQUEST['id']);
	}
	
	if(!isset($_GESTOR['modulo-registro-id'])){
		gestor_redirecionar_raiz();
	}
	
	if(isset($_REQUEST['_gestor-atualizar'])){
		$_GESTOR['atualizar-banco'] = true;
	}
}

function interface_alteracoes_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	// metaDados - Array - Opcional - Conjunto de meta dados de um registro.
		// titulo - String - Obrigatório - Título informativo do meta dado.
		// dado - String - Obrigatório - A informação meta dado.
		
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
		
	// ===== 
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-delecao',
			'modal-alerta',
		)
	));
	
	// ===== Formulário de edição
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-formulario-edicao',
	));
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	$_GESTOR['pagina#titulo-extra'] = interface_modulo_variavel_valor(Array('variavel' => 'nome')).' - ';
	
	$pagina = modelo_var_troca($pagina,"#form-id#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-name#",$_GESTOR['modulo']);
	$pagina = modelo_var_troca($pagina,"#form-action#",$_GESTOR['url-raiz'].$_GESTOR['caminho-total']);
	$pagina = modelo_var_troca($pagina,"#form-registro-id#",$_GESTOR['modulo-registro-id']);
	$pagina = modelo_var_troca($pagina,"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$pagina = modelo_var_troca($pagina,"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	
	$cel_nome = 'nao-alterar-id'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#form-page#",$_GESTOR['pagina']);
	
	// ===== Alterar meta dados caso houver
	
	if(isset($metaDados)){
		$cel_nome = 'cel-th'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		$cel_nome = 'cel-td'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		foreach($metaDados as $meta){
			$cel_nome = 'cel-th'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-titulo#",$meta['titulo']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
			
			$cel_nome = 'cel-td'; $cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#meta-dado#",$meta['dado']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$cel_nome = 'cel-th'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		$cel_nome = 'cel-td'; $pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		$cel_nome = 'meta-dados'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Alterar linguagem das variáveis dos formulários
	
	$form_variaveis = gestor_variaveis(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'conjunto' => true,
		'padrao' => 'form',
	));
	
	if(isset($form_variaveis)){
		foreach($form_variaveis as $form_id => $form_val){
			$pagina = modelo_var_troca($pagina,"#".$form_id."#",$form_val);
		}
	}
	
	// ===== Mostrar histórico na página caso houver.
	
	$pagina = interface_historico(Array(
		'id' => $id,
		'modulo' => $_GESTOR['modulo-id'],
		'pagina' => $pagina,
	));
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Formulários
	
	if(isset($formulario)){
		// ===== Formulário Validações
		
		if(isset($formulario['validacao'])){
			interface_formulario_validacao($formulario);
		}
		// ===== Formulário Campos
		
		if(isset($formulario['campos'])){
			interface_formulario_campos($formulario);
		}
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
	
	$_GESTOR['javascript-vars']['moduloRegistroId'] = $_GESTOR['modulo-registro-id'];
}

function interface_simples_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	if(isset($_REQUEST['_gestor-atualizar'])){
		$_GESTOR['atualizar-banco'] = true;
	}
}

function interface_simples_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// variaveisTrocarDepois - Array - Opcional - Conjunto variáveis que serão trocadas depois de todas as alterações.
	
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	
	// ===== 
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	// ===== Formulário de edição
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-simples',
	));
	
	// ===== Popular toda as variáveis do layout.
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	
	// ===== Botões principais
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$pagina = modelo_var_troca($pagina,"#form-page#",$_GESTOR['pagina']);
	
	// ===== Mostrar histórico na página caso houver.
	
	$pagina = interface_historico(Array(
		'id' => $_GESTOR['modulo-id'],
		'modulo' => $_GESTOR['modulo-id'],
		'pagina' => $pagina,
		'sem_id' => true,
	));
	
	// ===== Variáveis trocar depois
	
	if(isset($variaveisTrocarDepois)){
		foreach($variaveisTrocarDepois as $variavel => $valor){
			$pagina = modelo_var_troca($pagina,"#".$variavel."#",$valor);
		}
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = $pagina;
	
	// ===== Formulários
	
	if(isset($formulario)){
		// ===== Formulário Validações
		
		if(isset($formulario['validacao'])){
			interface_formulario_validacao($formulario);
		}
		// ===== Formulário Campos
		
		if(isset($formulario['campos'])){
			interface_formulario_campos($formulario);
		}
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
}

function interface_listar_ajax($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Dados da Sessão
	
	$interface = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'interface'.'-'.$_GESTOR['usuario-id']);
	
	$banco = $interface['banco'];
	$tabela = $interface['tabela'];
	$procurar = '';

	// ===== Request variables
	
	if(isset($_REQUEST['draw'])){
		$draw = $_REQUEST['draw'];
		if(!is_numeric($draw)){
			$draw = '1';
		}
		
		$jsonObj['draw'] = $draw;
	}
	
	if(isset($_REQUEST['start'])){
		$start = $_REQUEST['start'];
		if(!is_numeric($start)){
			$start = '0';
		}
		
		$interface['registroInicial'] = ($start != '0' ? ltrim($start, "0") : $start);
	}
	
	if(isset($_REQUEST['length'])){
		$length = $_REQUEST['length'];
		if(!is_numeric($length)){
			$length = '25';
		}
		
		$interface['registrosPorPagina'] = $length;
	}
	
	if(isset($_REQUEST['columns'])){
		$columns = $_REQUEST['columns'];
	}
	
	if(isset($_REQUEST['order'])){
		$orderBanco = '';
		$order = $_REQUEST['order'];
		
		foreach($order as $o){
			$col = $o['column'];
			$dir = $o['dir'];
			
			if(!is_numeric($col)){
				$col = '0';
			}
			
			if($dir != 'asc'){
				$dir = 'desc';
			}
			
			$orderBanco .= (strlen($orderBanco) > 0 ? ',':'').$columns[$col]['data'].' '.$dir;
		}
		
		$banco['order'] = ' ORDER BY '.$orderBanco;
	}
	
	if(isset($_REQUEST['search'])){
		if(isset($_REQUEST['search']['value'])){
			$search = $_REQUEST['search']['value'];
			
			if(strlen($search) > 0){
				foreach($columns as $col){
					if($col['searchable'] == "true"){
						$procurar .= (strlen($procurar) > 0 ? ' OR ':'')."UCASE(".$col['data'].") LIKE UCASE('%".$search."%')";
					}
				}
			}
		}
	}
	
	// ===== Dados do Banco
	
	if(isset($banco['status'])){
		$campos = array_merge($banco['campos'],Array($banco['id'],$banco['status']));
	} else {
		$campos = array_merge($banco['campos'],Array($banco['id']));
	}
	
	if(strlen($procurar) > 0){
		$pre_tabela_bd = banco_select_name
		(
			$banco['id'],
			$banco['nome'],
			"WHERE ".$banco['status']."!='D'".(isset($banco['where']) ? " AND ".$banco['where'] : "").
			" AND (".$procurar.")"
		);
		
		$tabela_bd = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$banco['nome'],
			"WHERE ".$banco['status']."!='D'".(isset($banco['where']) ? " AND ".$banco['where'] : "").
			" AND (".$procurar.")".
			$banco['order'].
			" LIMIT ".$interface['registroInicial'].','.$interface['registrosPorPagina']
		);
	} else {
		$tabela_bd = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$banco['nome'],
			"WHERE ".$banco['status']."!='D'".(isset($banco['where']) ? " AND ".$banco['where'] : "").
			$banco['order'].
			" LIMIT ".$interface['registroInicial'].','.$interface['registrosPorPagina']
		);
	}
	
	// ===== Popular registros no JSON
	
	if($tabela_bd){
		if(strlen($procurar) > 0){
			$jsonObj['recordsTotal'] = $interface['totalRegistros'];
			$jsonObj['recordsFiltered'] = count($pre_tabela_bd);
		} else {
			$jsonObj['recordsTotal'] = $interface['totalRegistros'];
			$jsonObj['recordsFiltered'] = $interface['totalRegistros'];
		}
		
		foreach($tabela_bd as $dado){
			if($tabela){
				$row = Array();
				foreach($tabela['colunas'] as $coluna){
					if(isset($coluna['formatar'])){
						$dado[$coluna['id']] = interface_formatar_dado(Array(
							'formato' => $coluna['formatar'],
							'dado' => $dado[$coluna['id']],
						));
					}
					
					$row[$coluna['id']] = $dado[$coluna['id']];
				}
				
				if(isset($banco['status'])){
					$row[$banco['status']] = $dado[$banco['status']];
				}
				
				$row[$banco['id']] = $dado[$banco['id']];
				
				$data[] = $row;
			}
		}
		
		$jsonObj['data'] = $data;
	}
	
	// ===== Salvar Sessão
	
	gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'interface'.'-'.$_GESTOR['usuario-id'],$interface);
	
	return $jsonObj;
}

function interface_listar_tabela($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Dados da Sessão
	
	if(!existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'interface'.'-'.$_GESTOR['usuario-id']))){
		gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'interface'.'-'.$_GESTOR['usuario-id'],Array(
			'totalRegistros' => 0,
			'registrosPorPagina' => 25,
			'registroInicial' => 0,
		));
	}
	
	$interface = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'interface'.'-'.$_GESTOR['usuario-id']);
	
	// ===== Total no Banco
	
	$pre_tabela_bd = banco_select(Array(
		'tabela' => $banco['nome'],
		'campos' => Array(
			$banco['id'],
		),
		'extra' => 
			"WHERE ".$banco['status']."!='D'".(isset($banco['where']) ? " AND ".$banco['where'] : "")
	));
	
	if($pre_tabela_bd){
		// ===== Definir parâmetros iniciais
		
		if($interface['totalRegistros'] != count($pre_tabela_bd)){
			$interface['totalRegistros'] = count($pre_tabela_bd);
			$interface['registroInicial'] = 0;
		}
		
		// ===== Layout Tabela
		
		$lista_tabela = '<table id="_gestor-interface-lista-tabela" class="ui celled table responsive nowrap unstackable">#rows#
		</table>';
		
		$tabela_cabecalho = '
		<thead>
			<tr>#rows#
			</tr>
		</thead>';

		$tabela_rodape = '
		<tfoot>
			<tr>#rows#
			</tr>
		</tfoot>';
		
		// ===== Montar cabeçalho e rodapé da tabela. Também definir dados iniciais das colunas.
		
		if($tabela){
			$interface['columns'] = false;
			$order = false;
			$orderBanco = '';
			$orderDefault = false;
			$count = 0;
			
			foreach($tabela['colunas'] as $coluna){
				$row = '
				<th>'.$coluna['nome'].'</th>';
				
				$tabela_cabecalho = modelo_var_in($tabela_cabecalho,"#rows#",$row);
				if($tabela['rodape']) $tabela_rodape = modelo_var_in($tabela_rodape,"#rows#",$row);
				
				$columns = Array(
					'data' => $coluna['id'],
					'name' => $coluna['nome'],
				);
				
				if(isset($coluna['nao_ordenar'])) $columns['orderable'] = false;
				if(isset($coluna['nao_procurar'])) $columns['searchable'] = false;
				if(isset($coluna['nao_visivel'])) $columns['visible'] = false;
				if(isset($coluna['className'])) $columns['className'] = $coluna['className'];
				if(isset($coluna['ordenar'])){
					if($coluna['ordenar'] == 'asc'){
						$ordem = 'asc';
					} else {
						$ordem = 'desc';
					}
					
					$order[] = Array($count,$ordem);
					$orderBanco .= (strlen($orderBanco) > 0 ? ',':'').$coluna['id'].' '.$ordem;
				}
				
				if(!$orderDefault){
					$orderDefault = $coluna['id'];
				}
				
				$interface['columns'][] = $columns;
				$count++;
			}
			
			// ===== Coluna status caso precise ativar/desativar registro 
			
			if(isset($banco['status'])){
				$row = '
				<th>Status</th>';
				
				$tabela_cabecalho = modelo_var_in($tabela_cabecalho,"#rows#",$row);
				if($tabela['rodape']) $tabela_rodape = modelo_var_in($tabela_rodape,"#rows#",$row);
				
				$interface['columns'][] = Array(
					'data' => $banco['status'],
					'name' => 'status',
					'orderable' => false,
					'searchable' => false,
					'visible' => false,
				);
			}
			
			// ===== Coluna opções com os botões de ações 
			
			$row = '
				<th>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'list-column-options')).'</th>';
			
			$tabela_cabecalho = modelo_var_in($tabela_cabecalho,"#rows#",$row);
			if($tabela['rodape']) $tabela_rodape = modelo_var_in($tabela_rodape,"#rows#",$row);
			
			$interface['columns'][] = Array(
				'data' => $banco['id'],
				'name' => gestor_variaveis(Array('modulo' => 'interface','id' => 'list-column-options')),
				'orderable' => false,
				'searchable' => false,
			);
			
			// =====
			
			if($order){
				$interface['order'] = $order;
				
				$banco['order'] = ' ORDER BY '.$orderBanco;
			} else {
				$interface['order'] = Array(Array(
					0,'asc'
				));
				
				$banco['order'] = ' ORDER BY '.$orderDefault.' asc';
			}
			
			$tabela_cabecalho = modelo_var_troca($tabela_cabecalho,"#rows#",'');
			if($tabela['rodape']) $tabela_rodape = modelo_var_troca($tabela_rodape,"#rows#",'');
		}
		
		// ===== Dados do Banco
		
		if(isset($banco['status'])){
			$campos = array_merge($banco['campos'],Array($banco['id'],$banco['status']));
		} else {
			$campos = array_merge($banco['campos'],Array($banco['id']));
		}
		
		$tabela_bd = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			$banco['nome'],
			"WHERE ".$banco['status']."!='D'".(isset($banco['where']) ? " AND ".$banco['where'] : "").
			$banco['order'].
			" LIMIT ".$interface['registroInicial'].','.$interface['registrosPorPagina']
		);
		
		// ===== Montar miolo da tabela
		
		$count = 0;
		
		if($tabela_bd){
			$tabela_dados = '
		<tbody>#cols#
		</tbody>';
			
			foreach($tabela_bd as $dado){
				$col = '
			<tr#extra#>#rows#
			</tr>';
				
				$col_params = ' class="'.($count % 2 == 0 ? 'odd' : 'even').'"';
				
				$col = modelo_var_troca($col,"#extra#",$col_params);
				
				if($tabela){
					foreach($tabela['colunas'] as $coluna){
						if(isset($coluna['formatar'])){
							$dado[$coluna['id']] = interface_formatar_dado(Array(
								'formato' => $coluna['formatar'],
								'dado' => $dado[$coluna['id']],
							));
						}
						
						$row = '
				<td>'.$dado[$coluna['id']].'</td>';
						
						$col = modelo_var_in($col,"#rows#",$row);
					}
					
					if(isset($banco['status'])){
						$row = '
				<td>'.$dado[$banco['status']].'</td>';
					
						$col = modelo_var_in($col,"#rows#",$row);
					}
					
					$row = '
				<td>'.$dado[$banco['id']].'</td>';
					
					$col = modelo_var_in($col,"#rows#",$row);
				}
				
				$col = modelo_var_troca($col,"#rows#",'');
				$tabela_dados = modelo_var_in($tabela_dados,"#cols#",$col);
				
				$count++;
			}
			
			$tabela_dados = modelo_var_troca($tabela_dados,"#cols#",'');
		}
		
		// ===== Finalizar tabela
		
		$lista_tabela = modelo_var_troca($lista_tabela,"#rows#",$tabela_cabecalho.$tabela_dados.($tabela['rodape'] ? $tabela_rodape : '' ));
		
		// ===== Interface Javascript Vars
		
		$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
		$caminho = rtrim($caminho,'/').'/';
		
		$_GESTOR['javascript-vars']['interface'] = Array(
			'lista' => Array(
				'url' => $caminho,
				'id' => $banco['id'],
				'status' => (isset($banco['status']) ? $banco['status'] : false),
				'deferLoading' => $interface['totalRegistros'],
				'pageLength' => $interface['registrosPorPagina'],
				'displayStart' => (int)$interface['registroInicial'],
				'columns' => $interface['columns'],
				'order' => $interface['order'],
				'opcoes' => (isset($opcoes) ? $opcoes : null),
			),
		);
		
		// ===== Interface guardar tabela e banco
		
		$interface['banco'] = $banco;
		$interface['tabela'] = $tabela;
	} else {
		$lista_tabela = gestor_componente(Array(
			'id' => 'interface-listar-sem-registros',
		));
	}
	
	// ===== Salvar Sessão
	
	gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'interface'.'-'.$_GESTOR['usuario-id'],$interface);
	
	return $lista_tabela;
}

function interface_listar_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
}

function interface_listar_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// banco - Array - Obrigatório - Conjunto de dados para a formação dos dados vindos do banco de dados.
		// nome - String - Obrigatório - Nome da tabela dos dados no banco de dados.
		// campos - Array - Obrigatório - Lista com todos os campos do banco de dados.
		// id - String - Obrigatório - Nome do identificador principal da tabela dos dados no banco de dados.
		// where - String - Opcional - Conjunto de condicionais da clausúla WHERE do SQL da consulta ao banco de dados.
		// status - String - Opcional - Nome do campo de status do banco de dados para ativar/desativar registro. IMPORTANTE: deve definir os campos 'ativar' e 'desativar' dentro de 'opcoes'
		
	// tabela - Array - Obrigatório - Conjunto de dados para a formação da tabela com a lista dos dados.
		// rodape - Bool - Obrigatório - Habilitar/Desabilitar o menu do rodapé da tabela.
		// colunas - Array - Obrigatório - Conjunto com todos as colunas da tabela e suas configurações
			// id - String - Obrigatório - Identificador da coluna identifica o campo do banco de dados.
			// nome - String - Obrigatório - Nome da coluna que aparece tanto no rodapé quanto no cabeçalho da tabela.
			// nao_ordenar - Bool - Opcional - Esta entrada não será ordenável.
			// nao_procurar - Bool - Opcional - Esta entrada não será procurável.
			// ordenar - String - Opcional - Direção da ordenação: 'asc' - Acendente; 'desc' - Descendente.
			// nao_visivel - Bool - Opcional - Esta entrada não será visível.
			
	// opcoes - Array - Opcional - Conjunto de opções de ação que um registro dispõe.
		// url - String - Obrigatório Ou url Ou opcao - URL de acesso para disparar uma ação.
		// opcao - String Obrigatório Ou url Ou opcao - Opção que o botão vai disparar quando selecionado caso não haja URL.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação da opção.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
		// status_atual - String - Opcional - Valor do status atual. (Importante: só é utilizada quando a 'opcao' for definido para 'status').
		// status_mudar - String - Opcional - Valor do status que se deve alterar quando acionada essa opção. (Importante: só é utilizada quando a 'opcao' for definido para 'status').
		
	// botoes - Array - Opcional - Conjunto de botões principais de acesso a funcionalidades.
		// url - String - Obrigatório - URL de acesso para disparar com o botão.
		// rotulo - String - Obrigatório - Pequeno texto rótulo do botão.
		// tooltip - String - Obrigatório - Pequeno texto descritivo da ação do botão.
		// icon - String - Obrigatório - Ícone do botão.
		// cor - String - Obrigatório - Cor do botão.
	
	// ===== 
	
	// ===== Modal de confirmação de deleção
	
	$modal_delecao = gestor_componente(Array(
		'id' => 'interface-delecao-modal',
	));
	
	$modal_delecao = modelo_var_troca($modal_delecao,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-title')));
	$modal_delecao = modelo_var_troca($modal_delecao,"#mensagem#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-menssage')));
	$modal_delecao = modelo_var_troca($modal_delecao,"#botao-cancelar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-button-cancel')));
	$modal_delecao = modelo_var_troca($modal_delecao,"#botao-confirmar#",gestor_variaveis(Array('modulo' => 'interface','id' => 'delete-confirm-button-confirm')));
	
	// ===== Layout da lista
	
	$pagina = gestor_componente(Array(
		'id' => 'interface-listar',
	));
	
	$lista_tabela = interface_listar_tabela($params);
	
	$pagina = modelo_var_troca($pagina,"#titulo#",$_GESTOR['pagina#titulo']);
	$pagina = modelo_var_troca($pagina,"#lista#",$lista_tabela.$modal_delecao);
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	// ===== Incluir Página no gestor
	
	$_GESTOR['pagina'] = (isset($_GESTOR['pagina']) ? $_GESTOR['pagina'].$pagina : $pagina);
	
	// ===== Inclusão Data Table
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'datatables/datatables.min.css" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'datatables/datatables.min.js"></script>';
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
}

// ===== Interfaces conectoras dos módulos.

function interface_ajax_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	
}

function interface_ajax_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'backup-campos-mudou': interface_ajax_backup_campo(); break;
			case 'historico-mais-resultados': interface_ajax_historico_mais_resultados(); break;
			case 'listar': interface_ajax_listar(); break;
			case 'verificar-campo': interface_ajax_verificar_campo(); break;
		}
	}
}

function interface_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$parametros = false;
	
	if(isset($_GESTOR['interface-nao-aplicar'])){
		return;
	}
	
	if(isset($_GESTOR['interface'])){
		if(isset($_GESTOR['interface'][$_GESTOR['opcao']])){
			if(isset($_GESTOR['interface'][$_GESTOR['opcao']]['iniciar'])){
				$parametros = $_GESTOR['interface'][$_GESTOR['opcao']]['iniciar'];
			}
		}
	}
	
	switch($_GESTOR['opcao']){
		case 'adicionar': interface_adicionar_iniciar($parametros); break;
		case 'editar': interface_editar_iniciar($parametros); break;
		case 'status': interface_status_iniciar($parametros); break;
		case 'excluir': interface_excluir_iniciar($parametros); break;
		case 'listar': interface_listar_iniciar($parametros); break;
		case 'config': interface_config_iniciar($parametros); break;
		case 'visualizar': interface_visualizar_iniciar($parametros); break;
	}
	
	if(isset($_GESTOR['interface'])){
		if(isset($_GESTOR['interface-opcao'])){
			if(isset($_GESTOR['interface'][$_GESTOR['interface-opcao']])){
				if(isset($_GESTOR['interface'][$_GESTOR['interface-opcao']]['iniciar'])){
					$parametros = $_GESTOR['interface'][$_GESTOR['interface-opcao']]['iniciar'];
				}
			}
		}
	}
	
	if(isset($_GESTOR['interface-opcao'])){
		switch($_GESTOR['interface-opcao']){
			case 'alteracoes': interface_alteracoes_iniciar($parametros); break;
			case 'adicionar-incomum': interface_adicionar_incomum_iniciar($parametros); break;
			case 'editar-incomum': interface_editar_incomum_iniciar($parametros); break;
			case 'simples': interface_simples_iniciar($parametros); break;
		}
	}
}

function interface_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$parametros = false;
	
	if(isset($_GESTOR['interface-nao-aplicar'])){
		return;
	}
	
	if(isset($_GESTOR['interface'])){
		if(isset($_GESTOR['interface'][$_GESTOR['opcao']])){
			if(isset($_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'])){
				$parametros = $_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'];
			}
		}
	}
	
	switch($_GESTOR['opcao']){
		case 'adicionar': interface_adicionar_finalizar($parametros); break;
		case 'editar': interface_editar_finalizar($parametros); break;
		case 'status': interface_status_finalizar($parametros); break;
		case 'excluir': interface_excluir_finalizar($parametros); break;
		case 'listar': interface_listar_finalizar($parametros); break;
		case 'config': interface_config_finalizar($parametros); break;
		case 'visualizar': interface_visualizar_finalizar($parametros); break;
	}
	
	if(isset($_GESTOR['interface'])){
		if(isset($_GESTOR['interface-opcao'])){
			if(isset($_GESTOR['interface'][$_GESTOR['interface-opcao']])){
				if(isset($_GESTOR['interface'][$_GESTOR['interface-opcao']]['finalizar'])){
					$parametros = $_GESTOR['interface'][$_GESTOR['interface-opcao']]['finalizar'];
				}
			}
		}
	}
	
	if(isset($_GESTOR['interface-opcao'])){
		switch($_GESTOR['interface-opcao']){
			case 'alteracoes': interface_alteracoes_finalizar($parametros); break;
			case 'adicionar-incomum': interface_adicionar_incomum_finalizar($parametros); break;
			case 'editar-incomum': interface_editar_incomum_finalizar($parametros); break;
			case 'simples': interface_simples_finalizar($parametros); break;
		}
	}
	
	// ===== Imprimir alerta
	
	interface_alerta(Array('imprimir' => true));
	
	// ===== Incluir Componentes na Página
	
	interface_componentes();
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
}

?>