<?php
/**
 * Biblioteca de Interface Administrativa
 * 
 * Fornece funções para construção e manipulação de interfaces administrativas,
 * incluindo formatação de dados, troca de valores entre tabelas, renderização
 * de campos de formulário, geração de tabelas, menus, e outros componentes UI.
 * 
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.1.5
 */

global $_GESTOR;

$_GESTOR['biblioteca-interface']							=	Array(
	'versao' => '1.1.5',
);

// ===== Funções formatação

/**
 * Converte data/hora do formato datetime (YYYY-MM-DD HH:MM:SS) para texto formatado.
 * 
 * Permite formatação personalizada usando marcadores: D (dia), ME (mês), A (ano),
 * H (hora), MI (minutos), S (segundos). Formato padrão: "D/ME/A HhMI".
 *
 * @param string $data_hora Data/hora no formato datetime (YYYY-MM-DD HH:MM:SS)
 * @param string|false $format Formato personalizado usando marcadores ou false para formato padrão
 * @return string Data/hora formatada ou string vazia se não houver data
 */
function interface_data_hora_from_datetime_to_text($data_hora, $format = false){
	$formato_padrao = 'D/ME/A HhMI';
	
	// Verifica se há data/hora válida
	if($data_hora){
		// Separa data e hora
		$data_hora = explode(" ",$data_hora);
		$data_aux = explode("-",$data_hora[0]);
		
		// Aplica formato personalizado se fornecido
		if($format){
			$hora_aux = explode(":",$data_hora[1]);
			// Substitui marcadores pelos valores correspondentes
			$format = preg_replace('/D/', $data_aux[2], $format);
			$format = preg_replace('/ME/', $data_aux[1], $format);
			$format = preg_replace('/A/', $data_aux[0], $format);
			$format = preg_replace('/H/', $hora_aux[0], $format);
			$format = preg_replace('/MI/', $hora_aux[1], $format);
			$format = preg_replace('/S/', $hora_aux[2], $format);
			
			return $format;
		} else if($formato_padrao){
			// Usa formato padrão (D/ME/A HhMI)
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
			// Formato brasileiro básico (DD/MM/YYYY HH:MM:SS)
			$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
			$hora = $data_hora[1];
			
			return $data . " " . $hora;
		}
	} else {
		return "";
	}
}

/**
 * Converte data do formato datetime (YYYY-MM-DD) para texto no formato brasileiro (DD/MM/YYYY).
 * 
 * Extrai apenas a parte da data ignorando o horário.
 *
 * @param string $data_hora Data/hora no formato datetime (YYYY-MM-DD HH:MM:SS)
 * @return string Data formatada (DD/MM/YYYY)
 */
function interface_data_from_datetime_to_text($data_hora){
	// Separa data e hora, extrai apenas a data
	$data_hora = explode(" ",$data_hora);
	$data_aux = explode("-",$data_hora[0]);
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	
	return $data;
}

/**
 * Busca e substitui valores de um campo usando referência de outra tabela.
 * 
 * Realiza lookup em uma tabela secundária para trocar valores de referência
 * por valores descritivos. Suporta cache de consultas, campos extras e 
 * encapsulamento de resultados em templates.
 *
 * @global array $_GESTOR Sistema global de configurações e cache
 * 
 * @param array|false $params Parâmetros da função:
 * @param array $params['tabela'] Configuração da tabela principal (obrigatório):
 * @param string $params['tabela']['nome'] Nome da tabela
 * @param string $params['tabela']['campo_referencia'] Campo usado como chave de busca
 * @param string $params['tabela']['campo_trocar'] Campo cujo valor será retornado
 * @param array $params['tabela']['camposExtras'] Campos adicionais a retornar (opcional)
 * @param string $params['tabela']['where'] Condição WHERE adicional (opcional)
 * @param array $params['tabela2'] Configuração de tabela secundária (opcional)
 * @param mixed $params['dado'] Valor a ser buscado
 * @param string $params['encapsular'] Template para encapsular resultado (opcional)
 * 
 * @return string|array Valor trocado, array de valores (se camposExtras) ou resultado encapsulado
 */
function interface_trocar_valor_outra_tabela($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// tabela - Array - Obrigatório - Tabela que será usada para trocar valores.
		// nome - String - Obrigatório - Nome da tabela.
		// campo_referencia - String - Obrigatório - Referência do campo na tabela.
		// campo_trocar - String - Obrigatório - Nome do campo que o valor será trocado.
		// camposExtras - Array - Opcional - Conjunto com os campos extras que devem ser lidos do banco.
		// where - Tipo - Opcional - Valor extra para aplicar ao campo where.
	// tabela2 - Array - Opcional - Tabela que será usada para trocar valores.
	// encapsular - String - Opcional - Retornar valor referente e de troca encapsulado num texto padrão com marcadores referentes.
	
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
			
			// ===== Incluir campos extras na consulta com o banco de dados quando necessário.
			
			$camposBD = Array(
				$tabela['campo_trocar'],
			);
			
			if(isset($tabela['camposExtras'])){
				$camposBD = array_merge($camposBD,$tabela['camposExtras']);
			}
			
			// ===== Buscar no banco de dados o dado para trocar.
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas($camposBD)
				,
				$tabela['nome'],
				"WHERE ".$tabela['campo_referencia']."='".$dado."'"
				.(isset($tabela['where']) ? ' AND ('.$tabela['where'].')' : '')
			);
			
			if($resultado){
				if(isset($tabela['encapsular'])){
					$encapsular = $tabela['encapsular'];
					
					$encapsular = modelo_var_troca_tudo($encapsular,"#campo_referencia#",$dado);
					
					foreach($camposBD as $campoBD){
						$encapsular = modelo_var_troca_tudo($encapsular,"#".$campoBD."#",$resultado[0][$campoBD]);
					}
					
					$depois = $encapsular;
				} else {
					$depois = $resultado[0][$tabela['campo_trocar']];
				}
				
				$_GESTOR['interface_trocar_valor_outra_tabela'][$tabela['nome']][$tabela['campo_referencia']] = Array(
					'antes' => $dado,
					'depois' => $depois,
				);
				
				// ===== Caso exista uma tabela2, trocar o resultado da tabela pela o valor da tabela2.
				
				if(isset($tabela2)){
					// ===== Incluir campos extras na consulta com o banco de dados quando necessário.
					
					$camposBD2 = Array(
						$tabela2['campo_trocar'],
					);
					
					if(isset($tabela2['camposExtras'])){
						$camposBD2 = array_merge($camposBD2,$tabela2['camposExtras']);
					}
					
					// ===== Buscar no banco de dados o dado para trocar.
					
					$resultado2 = banco_select_name
					(
						banco_campos_virgulas($camposBD2)
						,
						$tabela2['nome'],
						"WHERE ".$tabela2['campo_referencia']."='".$depois."'"
						.(isset($tabela2['where']) ? ' AND ('.$tabela2['where'].')' : '')
					);
					
					if($resultado2){
						if(isset($tabela2['encapsular'])){
							$encapsular = $tabela2['encapsular'];
							
							$encapsular = modelo_var_troca_tudo($encapsular,"#campo_referencia#",$depois);
							
							foreach($camposBD2 as $campoBD){
								$encapsular = modelo_var_troca_tudo($encapsular,"#".$campoBD."#",$resultado2[0][$campoBD]);
							}
							
							$depois = $encapsular;
						} else {
							$depois = $resultado2[0][$tabela2['campo_trocar']];
						}
					}
				}
				
				return $depois;
			}
		}
	}
	
	return $dado;
}

/**
 * Troca um valor por outro baseado em um conjunto de mapeamentos.
 *
 * Percorre um conjunto de mapeamentos alvo/troca e retorna o valor de troca
 * quando encontra correspondência com o dado fornecido.
 *
 * @global array $_GESTOR Sistema global do gestor.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['dado'] Dado que será verificado e potencialmente trocado.
 * @param array $params['conjunto'] Conjunto de mapeamentos com estrutura [['alvo' => 'valor1', 'troca' => 'novoValor1'], ...].
 *
 * @return string O valor trocado se encontrado no conjunto, ou o dado original caso contrário.
 */
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

/**
 * Troca um valor por outro baseado em array de valores com campos específicos.
 *
 * Busca em um array de valores e retorna o valor do campo alvo quando
 * encontra correspondência no campo de troca.
 *
 * @global array $_GESTOR Sistema global do gestor.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['dado'] Dado que será verificado e potencialmente trocado.
 * @param array $params['valores'] Array de valores onde cada elemento contém os campos de troca e alvo.
 * @param string $params['campo_troca'] Nome do campo usado para comparação com o dado.
 * @param string $params['campo_alvo'] Nome do campo cujo valor será retornado em caso de correspondência.
 *
 * @return string O valor do campo alvo se encontrado, ou o dado original caso contrário.
 */
function interface_trocar_valor_outro_array($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	if(isset($valores) && isset($campo_troca) && isset($campo_alvo)){
		foreach($valores as $campo){
			if($campo[$campo_troca] == $dado){
				return $campo[$campo_alvo];
			}
		}
	}

	
	return $dado;
}

/**
 * Encapsula um valor dentro de uma cápsula de texto.
 *
 * Substitui uma variável dentro de uma cápsula pelo valor fornecido.
 *
 * @global array $_GESTOR Sistema global do gestor.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['dado'] Dado que será verificado e potencialmente trocado.
 * @param string $params['capsula'] Cápsula de texto onde a variável será substituída.
 * @param string $params['variavel'] Variável dentro da cápsula que será substituída pelo dado.
 *
 * @return string O valor encapsulado se encontrado, ou o dado original caso contrário.
 */
function interface_encapsular_valor($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	if(isset($capsula) && isset($variavel)){
		return str_replace($variavel, $dado, $capsula);
	}
	
	return $dado;
}

/**
 * Formata um número de telefone para exibição.
 *
 * Suporta formato brasileiro: +(CC) DD XXXXX-XXXX (celular) ou +(CC) DD XXXX-XXXX (fixo).
 * Para outros formatos internacionais, retorna com prefixo + e agrupamento genérico.
 *
 * @param string $telefone Número de telefone (pode conter +, espaços, hífens, parênteses).
 * @return string Telefone formatado ou valor original se não for possível formatar.
 */
function interface_formatar_telefone($telefone){
	$digits = preg_replace('/\D/', '', $telefone);
	
	if(empty($digits)) return $telefone;
	
	// Formato brasileiro: +55 + DDD (2 dígitos) + número (8 ou 9 dígitos)
	if(strlen($digits) >= 12 && substr($digits, 0, 2) === '55'){
		$cc = '55';
		$rest = substr($digits, 2);
		$ddd = substr($rest, 0, 2);
		$number = substr($rest, 2);
		
		if(strlen($number) === 9){
			return '+(' . $cc . ') ' . $ddd . ' ' . substr($number, 0, 5) . '-' . substr($number, 5);
		} elseif(strlen($number) === 8){
			return '+(' . $cc . ') ' . $ddd . ' ' . substr($number, 0, 4) . '-' . substr($number, 4);
		}
		
		// Fallback brasileiro com DDD
		return '+(' . $cc . ') ' . $ddd . ' ' . $number;
	}
	
	// Formato brasileiro sem código de país: DDD (2 dígitos) + número (8 ou 9 dígitos)
	// Celular: 11 dígitos (DDD + 9xxxx-xxxx) — o 3° dígito é sempre 9
	// Fixo: 10 dígitos (DDD + xxxx-xxxx)
	if(strlen($digits) === 11 && substr($digits, 2, 1) === '9'){
		$ddd = substr($digits, 0, 2);
		$number = substr($digits, 2);
		return '(' . $ddd . ') ' . substr($number, 0, 5) . '-' . substr($number, 5);
	}
	
	if(strlen($digits) === 10){
		$ddd = substr($digits, 0, 2);
		$number = substr($digits, 2);
		return '(' . $ddd . ') ' . substr($number, 0, 4) . '-' . substr($number, 4);
	}
	
	// Formato internacional genérico (US +1, etc): +(CC) XXXXXXXXXX
	if(strlen($digits) > 10){
		// Tentar detectar código de país (1-3 dígitos)
		if(substr($digits, 0, 1) === '1' && strlen($digits) === 11){
			// US/Canadá: +1 (XXX) XXX-XXXX
			$cc = '1';
			$area = substr($digits, 1, 3);
			$prefix = substr($digits, 4, 3);
			$line = substr($digits, 7);
			return '+' . $cc . ' (' . $area . ') ' . $prefix . '-' . $line;
		}
		
		return '+' . $digits;
	}
	
	// Número curto sem código de país
	return $telefone;
}

/**
 * Formata um dado de acordo com o formato especificado.
 *
 * Aplica diferentes tipos de formatação aos dados, incluindo conversão de datas,
 * formatação monetária, troca por valores de outras tabelas ou arrays, e substituição
 * por rótulos customizados.
 *
 * @global array $_GESTOR Sistema global do gestor.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['dado'] Dado que será formatado.
 * @param string|array $params['formato'] Formato a ser aplicado. Pode ser string ('dinheiroReais', 'data', 'dataHora')
 *                                        ou array com 'id' e parâmetros específicos ('outraTabela', 'outroConjunto', 'outroArray').
 * @param string $params['formato']['id'] Identificador do formato (quando formato é array).
 * @param array $params['formato']['tabela'] Dados da tabela para formato 'outraTabela'.
 * @param array $params['formato']['tabela2'] Segunda tabela opcional para formato 'outraTabela'.
 * @param array $params['formato']['conjunto'] Conjunto de mapeamentos para formato 'outroConjunto'.
 * @param array $params['formato']['valores'] Array de valores para formato 'outroArray'.
 * @param string $params['formato']['campo_troca'] Campo de troca para formato 'outroArray'.
 * @param string $params['formato']['campo_alvo'] Campo alvo para formato 'outroArray'.
 * @param array $params['formato']['valor_substituir_por_rotulo'] Array de substituições [['valor' => '1', 'rotulo' => 'Sim'], ...].
 * @param string $params['formato']['valor_senao_existe'] Valor a retornar quando dado está vazio.
 *
 * @return string O dado formatado de acordo com as especificações, ou valor padrão se dado estiver vazio.
 */
function interface_formatar_dado($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// dado - String - Opcional - Dado que será formatado.
	// formato - String|Array - Opcional - Formato a ser aplicado ou conjunto de dados para formatar dado.
	// valor_senao_existe - String - Opcional - Senão houver valor, este será aplicado.
	// valor_substituir_por_rotulo - Array - Opcional - Conjunto de valores com os rótulos que serão substituídos. Exemplo, quando o dado tiver valor '1', trocar por 'Sim'.
	
	// Se formato - Array
	
	// id - String - Obrigatório - Identificador do formato a ser aplicado.
	
	// Se formato == 'outraTabela'
	
	// tabela - Array - Obrigatório - Dados da tabela que será aplicada a formatação.
		// nome - String - Obrigatório - nome da tabela do banco de dados.
		// campo_trocar - String - Obrigatório - Nome do campo que será usado para subistituir o valor.
		// campo_referencia - String - Obrigatório - Nome do campo identificador do valor que será comparado afim de trocar pelo valor do banco caso exista.
	// tabela2 - Array - Opcional - Dados da tabela que será aplicada na formatação da tabela1.
	
	// Se formato == 'outroConjunto'
	
	// conjunto - Array - Obrigatório - Dados do conjunto que será aplicado na formatação.
		// alvo - String - Obrigatório - Valor do alvo da troca.
		// troca - String - Obrigatório - Valor da troca.
		
	// Se formato == 'outroArray'

	// valores - Array - Obrigatório - Dados do conjunto que será aplicado na formatação.
	// campo_troca - String - Obrigatório - Nome do campo que será usado para substituir o valor.
	// campo_alvo - String - Obrigatório - Nome do campo que será usado como alvo da troca.
		
	// Se formato == 'encapsular'

	// capsula - String - Obrigatório - String com o que o dado será encapsulado.
	// variavel - String - Obrigatório - Marcador que será substituído pelo dado dentro da cápsula.
		
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
			case 'dinheiroUSD':
				$dado = '$ ' . number_format((float)$dado, 2, '.', ',');
			break;
			case 'telefone':
				$dado = interface_formatar_telefone($dado);
			break;
			case 'data': $dado = interface_data_from_datetime_to_text($dado); break;
			case 'dataHora': $dado = interface_data_hora_from_datetime_to_text($dado); break;
			case 'outraTabela': $dado = interface_trocar_valor_outra_tabela(Array('dado' => $dado,'tabela' => $formato['tabela'],'tabela2' => (isset($formato['tabela2']) ? $formato['tabela2'] : NULL),)); break;
			case 'outroConjunto': $dado = interface_trocar_valor_outro_conjunto(Array('dado' => $dado,'conjunto' => $formato['conjunto'])); break;
			case 'outroArray': $dado = interface_trocar_valor_outro_array(Array('dado' => $dado,'valores' => $formato['valores'],'campo_troca' => $formato['campo_troca'],'campo_alvo' => $formato['campo_alvo'])); break;
			case 'encapsular': $dado = interface_encapsular_valor(Array('dado' => $dado,'capsula' => $formato['capsula'],'variavel' => $formato['variavel'])); break;
		}
		
		// ===== Verificar se é necessário substituir valores por rótulos.
		
		if(isset($formato['valor_substituir_por_rotulo'])){
			$valor_substituir_por_rotulo = $formato['valor_substituir_por_rotulo'];
			
			foreach($valor_substituir_por_rotulo as $valorRotulo){
				if($valorRotulo['valor'] == $dado){
					$dado = $valorRotulo['rotulo'];
					break;
				}
			}
		}
		
		
		return $dado;
	} else {
		return (isset($formato['valor_senao_existe']) ? $formato['valor_senao_existe'] : '');
	}
}

/**
 * Gerencia alertas de mensagens para o usuário na interface.
 *
 * Permite definir e exibir mensagens de alerta na interface do usuário.
 * Suporta alertas imediatos ou pós-redirecionamento via sessão.
 *
 * @global array $_GESTOR Sistema global do gestor.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['msg'] Mensagem a ser exibida como alerta.
 * @param bool $params['redirect'] Se true, salva o alerta na sessão para exibição após redirecionamento.
 * @param bool $params['imprimir'] Se true, imprime o HTML do alerta na tela.
 *
 * @return void|string Retorna HTML do alerta se $imprimir for true, caso contrário não retorna nada.
 */
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
					
					$_GESTOR['javascript-vars']['interface']['alert'] = $alerta;
					
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

/**
 * Inclui registros no histórico de alterações do sistema.
 *
 * Registra alterações feitas em registros do banco de dados no histórico,
 * incluindo informações sobre campo alterado, valores antes e depois,
 * usuário responsável e versionamento.
 *
 * @global array $_GESTOR Sistema global do gestor.
 *
 * @param array|false $params Parâmetros da função.
 * @param array $params['alteracoes'] Conjunto de alterações a serem registradas no histórico.
 * @param string $params['alteracoes'][]['campo'] Identificador do campo alterado (código do módulo/id do campo).
 * @param string $params['alteracoes'][]['opcao'] Opção extra para hacks específicos de histórico.
 * @param string $params['alteracoes'][]['filtro'] Filtro para formatação dos dados.
 * @param string $params['alteracoes'][]['alteracao'] Identificador da alteração (interface/id do campo).
 * @param string $params['alteracoes'][]['alteracao_txt'] Valor literal da alteração.
 * @param string $params['alteracoes'][]['valor_antes'] Valor antes da alteração.
 * @param string $params['alteracoes'][]['valor_depois'] Valor após a alteração.
 * @param array $params['alteracoes'][]['tabela'] Tabela para conversão de IDs em nomes textuais.
 * @param bool $params['deletar'] Se true, incrementa versão para registro de deleção.
 * @param int $params['id_numerico_manual'] ID numérico manual do registro.
 * @param int $params['id_usuarios_manual'] ID do usuário manual.
 * @param int $params['id_hosts_manual'] ID do host manual.
 * @param string $params['modulo_id'] ID do módulo a vincular manualmente.
 * @param bool $params['sem_id'] Se true, não vincula ID ao histórico.
 * @param int $params['sem_id']['versao'] Versão manual do registro quando sem_id está definido.
 * @param array $params['tabela'] Tabela personalizada ao invés da tabela principal do módulo.
 * @param string $params['tabela']['nome'] Nome da tabela do banco de dados.
 * @param string $params['tabela']['versao'] Campo versão da tabela.
 * @param string $params['tabela']['id_numerico'] Identificador numérico da tabela.
 * @param bool $params['tabela']['id'] Se true, usa campo id ao invés de id_numerico.
 *
 * @return void
 */
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
		3 - alteracao, alteracao_txt [campo] - o histórico mostra um valor pré-definido, caso necessário informar um valor a mais, basta informar a 'alteracao_txt' e se quiser também o 'campo'. E caso o valor do 'alteracao' tenha marcação #campo# , o sistema substituirá esse valor com o nome do 'campo'.
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

/**
 * Exibe o histórico de alterações de um registro do sistema.
 *
 * Renderiza uma lista paginada com todas as alterações registradas em um determinado
 * registro, incluindo informações sobre quem fez a alteração, quando e quais valores
 * foram modificados.
 *
 * @global array $_GESTOR Sistema global do gestor.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador do registro a consultar o histórico.
 * @param string $params['modulo'] Identificador do módulo do registro.
 * @param string $params['pagina'] Página onde o histórico será implementado.
 * @param bool $params['sem_id'] Se true, não filtra por ID no histórico.
 * @param array $params['moduloVars']['historico'] Configurações adicionais do histórico.
 * @param string $params['moduloVars']['historico']['moduloIdExtra'] Módulo ID extra para trocar labels.
 *
 * @return void Exibe o HTML do histórico diretamente.
 */
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
				
				$autor = '<a href="'.$_GESTOR['url-raiz'].'usuarios-hospedeiro/editar/?id='.$user_id.'">' . $user_primeiro_nome . '</a>';
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

/**
 * Marca componentes para inclusão na interface.
 *
 * Registra um ou mais componentes para serem incluídos posteriormente
 * na interface através da função interface_componentes(). Os componentes
 * são armazenados globalmente e renderizados quando necessário.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string|array $params['componente'] Componente ou array de componentes a incluir
 *                                           (ex: 'modal-carregamento', 'modal-delecao', 'modal-alerta', 'modal-iframe').
 * 
 * @return void
 */
function interface_componentes_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// componente - String|Array - Obrigatório - Incluir componente(s) na interface.
	
	// ===== 
	
	// Verifica se há componentes para incluir
	if(isset($componente)){
		switch(gettype($componente)){
			case 'array':
				// Se for array, percorre e marca cada componente
				if(count($componente) > 0){
					foreach($componente as $com){
						$_GESTOR['interface']['componentes'][$com] = true;
					}
				}
			break;
			default:
				// Se for string, marca componente único
				$_GESTOR['interface']['componentes'][$componente] = true;
		}
	}
}

/**
 * Renderiza componentes marcados para inclusão na interface.
 *
 * Processa todos os componentes previamente marcados via interface_componentes_incluir(),
 * carrega seus layouts, substitui variáveis e adiciona à página global. Suporta
 * modais de carregamento, deleção, alerta e iframe.
 *
 * @global array $_GESTOR Sistema global de gerenciamento e página.
 * 
 * @param array|false $params Parâmetros da função (não utilizado).
 * 
 * @return void
 */
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

/**
 * Gera campos de formulário dinamicamente para a interface.
 *
 * Cria campos de formulário completos com suporte a diversos tipos (select, imagepick,
 * templates, etc.). Processa configurações, carrega dados de tabelas do banco,
 * aplica estilos e validações. Suporta integração com Semantic UI e funcionalidades
 * avançadas como múltipla seleção, busca, ícones e placeholders.
 *
 * @global array $_GESTOR Sistema global de gerenciamento e variáveis.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['pagina'] Página onde será incluído o campo (opcional).
 * @param array $params['campos'] Array de configurações de campos a serem gerados.
 *                                Cada campo pode ter: tipo, id, nome, disabled, menu, procurar,
 *                                limpar, multiple, fluid, placeholder, tabela, dados, valor_selecionado, etc.
 * 
 * @return void
 */
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
		
		// disabled - Bool - Opcional - Se habilitado, o select ficará desabilitado e só poderá ser visto.
		// menu - Bool - Opcional - Se habilitado, o select terá mais opções visuais.
		// procurar - Bool - Opcional - Se habilitado, é possível procurar um valor no select digitando no teclado.
		// limpar - Bool - Opcional - Se habilitado, o select tem uma opção de deselecionar a opção selecionada.
		// multiple - Bool - Opcional - Se habilitado, o select tem uma opção de selecionar várias opções ao mesmo tempo.
		// fluid - Bool - Opcional - Se habilitado, o select será do tipo fluído tentando completar toda a tela.
		// placeholder - String - Opcional - Se definido o select terá uma opção coringa para mostrar na caixa quando não houver valor selecionado.
		// selectClass - String - Opcional - Se definido será incluída a classe ou classes no final do parâmetro class do select.
		
		// Ou tabela Ou dados Ou dadosAntes Ou dadosDepois
		
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
		// dados, dadosAntes, dadosDepois - Array - Opcional - Conjunto com dados que virão de um array
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
						$campo_saida = "	".'<div id="'.$campo['id'].'" class="ui '.(isset($campo['disabled']) ? 'disabled ':'').(isset($campo['fluid']) ? 'fluid ':'').(isset($campo['procurar']) ? 'search ':'').(isset($campo['limpar']) ? 'clearable ':'').(isset($campo['multiple']) ? 'multiple ':'').'selection dropdown'.(isset($campo['selectClass']) ? ' '.$campo['selectClass'] : '').'">'."\n";
						$campo_saida .= "		".'<input type="hidden" name="'.$campo['nome'].'"#selectedValue#>'."\n";
						$campo_saida .= "		".'<i class="dropdown icon"></i>'."\n";
					} else {
						$campo_saida = "	".'<select id="'.$campo['id'].'" class="ui '.(isset($campo['disabled']) ? 'disabled ':'').(isset($campo['fluid']) ? 'fluid ':'').(isset($campo['procurar']) ? 'search ':'').(isset($campo['limpar']) ? 'clearable ':'').'dropdown'.(isset($campo['selectClass']) ? ' '.$campo['selectClass'] : '').'" name="'.$campo['nome'].'"'.(isset($campo['multiple']) ? ' multiple':'').'>'."\n";
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
					
					// ===== Dados antes ou dados depois.
					
					if(isset($campo['dadosAntes']) || isset($campo['dadosDepois'])){
						$dadosAntes = (isset($campo['dadosAntes']) ? $campo['dadosAntes'] : Array());
						$dadosDepois = (isset($campo['dadosDepois']) ? $campo['dadosDepois'] : Array());
						
						$camposAntes = '';
						$camposDepois = '';
						
						if(isset($campo['valor_selecionado'])){
							$valor_selecionado = $campo['valor_selecionado'];
						}
						
						foreach($dadosAntes as $dado){
							if(isset($dado['texto']) && isset($dado['valor'])){
								if(isset($campo['menu'])){
									$camposAntes .= "			".'<div class="item '.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? 'active selected' : '' ) : '' ).'" data-value="'.$dado['valor'].'">'.(isset($dado['icone']) ? '<i class="'.$dado['icone'].' icon"></i>' : '').$dado['texto'].'</div>'."\n";
									
									if(isset($valor_selecionado)){
										if($valor_selecionado == $dado['valor']){
											$camposAntes = modelo_var_troca($camposAntes,"#selectedValue#",' value="'.$dado['valor'].'"');
										}
									}
								} else {
									$camposAntes .= "		".'<option value="'.$dado['valor'].'"'.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? ' selected' : '' ) : '' ).'>'.$dado['texto'].'</option>'."\n";
								}
							}
						}
						
						foreach($dadosDepois as $dado){
							if(isset($dado['texto']) && isset($dado['valor'])){
								if(isset($campo['menu'])){
									$camposDepois .= "			".'<div class="item '.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? 'active selected' : '' ) : '' ).'" data-value="'.$dado['valor'].'">'.(isset($dado['icone']) ? '<i class="'.$dado['icone'].' icon"></i>' : '').$dado['texto'].'</div>'."\n";
									
									if(isset($valor_selecionado)){
										if($valor_selecionado == $dado['valor']){
											$camposDepois = modelo_var_troca($camposDepois,"#selectedValue#",' value="'.$dado['valor'].'"');
										}
									}
								} else {
									$camposDepois .= "		".'<option value="'.$dado['valor'].'"'.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? ' selected' : '' ) : '' ).'>'.$dado['texto'].'</option>'."\n";
								}
							}
						}
					}
					
					// ===== Incluir os campos antes.
					
					if(isset($camposAntes)){
						$campo_saida .= $camposAntes;
					}
					
					// ===== Dados da tabela ou dados específicos.
					
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
					
					// ===== Incluir os campos antes.
					
					if(isset($camposDepois)){
						$campo_saida .= $camposDepois;
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
						'caminho' => '',
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
							$caminho = $arquivos[0]['caminho'] ?? '';
						}
					}

					if(isset($campo['caminho']) && existe($campo['caminho']) && !$found){
						$arquivos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_arquivos',
								'tipo',
								'nome',
								'data_criacao',
								'caminho',
								'caminho_mini',
							))
							,
							"arquivos",
							"WHERE caminho='".$campo['caminho']."'"
						);
						
						if($arquivos){
							if($arquivos[0]['caminho_mini']){
								$imgSrc = $_GESTOR['url-full'] . $arquivos[0]['caminho_mini'];
							} else {
								$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
							}
							
							$data = interface_formatar_dado(Array('dado' => $arquivos[0]['data_criacao'], 'formato' => 'dataHora'));
							$nome = $arquivos[0]['nome'];
							$tipo = $arquivos[0]['tipo'];
							
							$fileId = $arquivos[0]['id_arquivos'];
						} else {
							$imgSrc = $_GESTOR['url-full'] . $campo['caminho'];

							// TODO: Verificar se o arquivo existe no sistema de arquivos e pegar a data de criação correta.
							$file_path = $_GESTOR['contents-path'] . $campo['caminho'];
							if (file_exists($file_path)) {
								$file_mod_time = filemtime($file_path);
								$file = [
									'data_modificacao' => date('Y-m-d H:i:s', $file_mod_time),
									'nome' => basename($file_path),
									'mime_type' => mime_content_type($file_path),
								];
							} else {
								$file = [
									'data_modificacao' => date('Y-m-d H:i:s', time()),
									'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'file-name-unknown')),
									'mime_type' => gestor_variaveis(Array('modulo' => 'interface','id' => 'file-mime-type-unknown')),
								];
							}

							$data = interface_formatar_dado(Array('dado' => $file['data_modificacao'], 'formato' => 'dataHora'));
							$nome = $file['nome'];
							$tipo = $file['mime_type'];
						}

						$caminho = $campo['caminho'];
						$found = true;
					}
					
					if(!$found){
						$fileId = $imagepickJS['padroes']['fileId'];
						$nome = $imagepickJS['padroes']['nome'];
						$data = $imagepickJS['padroes']['data'];
						$tipo = $imagepickJS['padroes']['tipo'];
						$imgSrc = $imagepickJS['padroes']['imgSrc'];
						$caminho = $imagepickJS['padroes']['caminho'];
					}
					
					// ===== Alterar os dados do widget
					
					$imagepick = modelo_var_troca($imagepick,"#cont-id#",$campo['id']);
					$imagepick = modelo_var_troca_tudo($imagepick,"#campo-nome#",$campo['nome']);
					
					$imagepick = modelo_var_troca_tudo($imagepick,"#file-id#",$fileId);
					$imagepick = modelo_var_troca_tudo($imagepick,"#file-caminho#",$caminho);
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

/**
 * Configura validações de formulário usando Semantic UI.
 *
 * Define regras de validação client-side para campos de formulário, incluindo
 * validações de obrigatoriedade, email, número, vazio, etc. Integra com o
 * sistema de validação do Semantic UI e permite configurações personalizadas
 * por campo.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['pagina'] Página onde aplicar a validação (opcional).
 * @param array $params['campos'] Array de campos com suas regras de validação.
 *                                Cada campo pode ter: type (empty, email, number, etc.),
 *                                identifier (ID do campo), rules (regras personalizadas).
 * 
 * @return void
 */
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
								'type' => 'notEmpty',
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
								'type' => 'notEmpty',
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
								'type' => 'notEmpty',
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
								'type' => 'notEmpty',
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

						if(isset($regra['language'])){
							$validarCampos[$regra['identificador']]['language'] = true;
						}
					} else {
						$validarCampos[$regra['campo']] = Array(
							'prompt' => $prompt[4],
						);

						if(isset($regra['language'])){
							$validarCampos[$regra['campo']]['language'] = true;
						}
					}
				break;
				case 'selecao-obrigatorio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-select'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
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
								'type' => 'notEmpty',
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
								'type' => 'notEmpty',
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
								'type' => 'notEmpty',
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
										'type' => 'notEmpty',
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
										'type' => 'notEmpty',
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
										'type' => 'notEmpty',
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

								if(isset($regra['language'])){
									$validarCampos[$regra['identificador']]['language'] = true;
								}
							} else {
								$validarCampos[$regra['campo']] = Array(
									'prompt' => $prompt[4],
								);

								if(isset($regra['language'])){
									$validarCampos[$regra['campo']]['language'] = true;
								}
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

/**
 * Valida campos obrigatórios server-side.
 *
 * Executa validação server-side de campos obrigatórios conforme regras definidas
 * (texto, seleção, email). Se houver falha na validação, exibe alerta e
 * redireciona o usuário. Complementa a validação client-side.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['redirect'] URL de redirecionamento em caso de erro (opcional).
 * @param array $params['campos'] Array de campos a validar com suas regras
 *                                (texto-obrigatorio, selecao-obrigatorio, email-obrigatorio).
 * 
 * @return void
 */
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
	
	// Valida cada campo conforme suas regras
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
	
	// Exibe alerta se houver erro de validação
	if(isset($naoValidouMsgAlerta)){
		interface_alerta(Array('msg' => $naoValidouMsgAlerta));
	}
	
	// Redireciona em caso de falha na validação
	if(isset($naoValidou)){
		if(isset($redirect)){
			gestor_redirecionar($redirect);
		} else {
			gestor_reload_url();
		}
	}
}

/**
 * Obtém valor de variável do registro atual do módulo.
 *
 * Recupera o valor de uma variável/campo do registro que está sendo
 * visualizado/editado no módulo atual. Consulta o banco de dados se
 * necessário e armazena em cache para performance.
 *
 * @global array $_GESTOR Sistema global com dados do módulo e registro.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['variavel'] Nome da variável/campo a obter (obrigatório).
 * 
 * @return mixed Valor da variável solicitada.
 */
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
	
	// Verifica se o campo `language` existe na tabela alvo
	if(banco_campo_existe('language', $modulo['tabela']['nome'])){
		// Define filtro de idioma se o campo existir
		$filtro_idioma = " AND language='".$_GESTOR['linguagem-codigo']."'";
	} else {
		$filtro_idioma = "";
	}

	// Obtém o valor da variável
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			$variavel,
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$_GESTOR['modulo-registro-id']."'"
		.$filtro_idioma
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

/**
 * Registra backup de campo no banco de dados.
 *
 * Armazena versões antigas de valores de campos para permitir recuperação.
 * Mantém histórico limitado conforme parâmetro maxCopias, removendo backups
 * mais antigos automaticamente.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['campo'] Nome do campo a fazer backup (obrigatório).
 * @param int $params['id_numerico'] ID numérico do registro (obrigatório).
 * @param int $params['versao'] Número da versão do backup (obrigatório).
 * @param string $params['valor'] Valor do campo a ser guardado (obrigatório).
 * @param string $params['modulo'] Nome do módulo (opcional, usa módulo atual se não fornecido).
 * @param int $params['maxCopias'] Máximo de cópias a manter (opcional, padrão 20).
 * 
 * @return void
 */
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

/**
 * Renderiza dropdown de seleção de versões de backup de um campo.
 * 
 * Gera um elemento select HTML com todas as versões de backup disponíveis
 * para um campo específico, permitindo restaurar valores anteriores.
 * Dispara callback JavaScript após seleção.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['campo'] Nome do campo no banco de dados (obrigatório).
 * @param string $params['campo_form'] Nome do campo no formulário (opcional, usa 'campo' se não fornecido).
 * @param string $params['callback'] Nome do evento callback JavaScript para sucesso (obrigatório).
 * @param int $params['id_numerico'] Identificador numérico do registro (obrigatório).
 * @param string $params['modulo'] Nome do módulo (opcional, usa módulo atual se não fornecido).
 * 
 * @return void Renderiza HTML do dropdown diretamente.
 */
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

/**
 * Verifica alterações em campos comparando valores atuais com valores anteriores.
 * 
 * Compara os valores de campos específicos entre o estado atual e um backup
 * anterior, retornando quais campos foram modificados. Útil para detectar
 * mudanças antes de salvar formulários.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param array $params['campos'] Lista de campos a verificar (obrigatório).
 * @param array $params['valores_atuais'] Valores atuais dos campos (obrigatório).
 * @param array $params['valores_anteriores'] Valores anteriores dos campos para comparação (obrigatório).
 * 
 * @return array Lista de campos que foram alterados.
 */
function interface_verificar_campos($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Parâmetros
	
	// campo - String - Obrigatório - Nome do campo do banco de dados.
	// valor - String - Obrigatório - Valor do campo do banco de dados.
	// language - Boolean - Opcional - Indica se a verificação deve considerar o idioma.
	
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
			.(isset($language) ? " AND language='".$_GESTOR['linguagem-codigo']."'" : "" )
			." AND ".$tabela['status']."!='D'"
			.($_GESTOR['opcao'] == 'editar' ? ' AND '.$tabela['id']."!='".$_GESTOR['modulo-registro-id']."'" : '')
		);
		
		if($resultado){
			return true;
		}
	}
	
	return false;
}

/**
 * Renderiza botões de ação no cabeçalho da interface administrativa.
 * 
 * Gera HTML para botões de ações (excluir, salvar, etc.) que aparecem
 * no cabeçalho das páginas de edição/visualização. Suporta tooltips,
 * ícones, cores e callbacks personalizados.
 *
 * @param array|false $params Parâmetros da função.
 * @param array $params['botoes'] Array de botões a renderizar (obrigatório).
 *                                Cada botão contém: cor, icon, rotulo, tooltip, url, callback.
 * 
 * @return void Renderiza HTML dos botões diretamente.
 */
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

/**
 * Renderiza botões de ação no rodapé da interface administrativa.
 * 
 * Gera HTML para botões de ações (excluir, salvar, cancelar, etc.) que aparecem
 * no rodapé das páginas de edição/visualização. Estrutura e funcionalidade
 * similares aos botões do cabeçalho.
 *
 * @param array|false $params Parâmetros da função.
 * @param array $params['botoes_rodape'] Array de botões a renderizar no rodapé (obrigatório).
 *                                        Cada botão contém: cor, icon, rotulo, tooltip, url, callback.
 * 
 * @return string HTML dos botões do rodapé.
 */
function interface_botoes_rodape($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$botoes_html = '';
	
	foreach($botoes_rodape as $id => $botao){
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

/**
 * Processa requisição AJAX para restaurar backup de campo.
 * 
 * Retorna o valor de um campo a partir de um backup específico (se ID fornecido)
 * ou o valor atual do campo (se ID não fornecido). Usado para restaurar
 * versões anteriores de campos via interface administrativa.
 *
 * @global array $_GESTOR Configurações globais do sistema e resposta AJAX.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['campo'] Nome do campo (via $_REQUEST ou parâmetro).
 * @param int $params['id_numerico'] ID numérico do registro (via $_REQUEST ou parâmetro).
 * @param string $params['modulo'] Nome do módulo (opcional, usa módulo atual se não fornecido).
 * 
 * @return void Define $_GESTOR['ajax-json'] com o valor do campo.
 */
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

/**
 * Processa requisição AJAX para carregar mais resultados do histórico.
 * 
 * Retorna uma nova página de resultados do histórico de alterações,
 * usado para paginação infinita ou "carregar mais" na interface.
 * Delega para interface_historico() com parâmetros de paginação.
 *
 * @global array $_GESTOR Configurações globais do sistema e resposta AJAX.
 *                        Recebe: $_REQUEST['id'], $_REQUEST['sem_id'].
 *                        Define: $_GESTOR['ajax-json'] com status e HTML da página.
 * 
 * @return void Define $_GESTOR['ajax-json'] com a próxima página do histórico.
 */
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

/**
 * Processa requisição AJAX para listagem dinâmica de registros.
 * 
 * Retorna HTML de uma listagem de registros baseado em filtros, ordenação
 * e paginação via AJAX. Usado para atualizar tabelas de listagem sem
 * recarregar a página completa.
 *
 * @global array $_GESTOR Configurações globais do sistema e resposta AJAX.
 *                        Recebe parâmetros via $_REQUEST (filtros, ordem, página).
 *                        Define: $_GESTOR['ajax-json'] com status e HTML da listagem.
 * 
 * @return void Define $_GESTOR['ajax-json'] com o HTML da listagem atualizada.
 */
function interface_ajax_listar(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = interface_listar_ajax();
}

/**
 * Processa requisição AJAX para verificar existência de valor em campo.
 * 
 * Verifica se um determinado valor já existe em um campo específico da
 * tabela, útil para validações de unicidade (ex: email, CPF) em tempo
 * real durante preenchimento de formulários.
 *
 * @global array $_GESTOR Configurações globais do sistema e resposta AJAX.
 *                        Recebe: $_REQUEST['campo'], $_REQUEST['valor'], $_REQUEST['language'].
 *                        Define: $_GESTOR['ajax-json'] com status e resultado da verificação.
 * 
 * @return void Define $_GESTOR['ajax-json'] indicando se campo existe (true/false).
 */
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
		'language' => $_REQUEST['language'] === 'true' ? true : null,
	));
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'campoExiste' => $campoExiste,
	);
}

// ===== Interfaces principais

/**
 * Inicializa a interface de exclusão de registro.
 * 
 * Prepara o sistema para a exclusão de um registro, validando o ID
 * fornecido via GET e armazenando em $_GESTOR['modulo-registro-id'].
 * Redireciona para raiz se ID não fornecido.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 *                        Recebe: $_REQUEST['id'].
 *                        Define: $_GESTOR['modulo-registro-id'].
 * 
 * @param array|false $params Parâmetros da função (não utilizado nesta função).
 * 
 * @return void Prepara $_GESTOR para exclusão ou redireciona.
 */
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

/**
 * Finaliza a interface de exclusão de registro (exclusão lógica).
 * 
 * Executa a exclusão lógica do registro (marca status como 'D' - deletado)
 * no banco de dados. Suporta personalização da tabela, inclusão de histórico,
 * e callback após exclusão. Redireciona para listagem após sucesso.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 *                        Usa: $_GESTOR['modulo-registro-id'], $_GESTOR['modulo-id'].
 *                        
 * @param array|false $params Parâmetros da função.
 * @param array $params['banco'] Dados da tabela customizada (nome, id, status, where).
 * @param bool $params['historico'] Se false, desativa inclusão no histórico (padrão: ativa).
 * @param string $params['callbackFunction'] Função callback a executar após exclusão.
 * 
 * @return void Executa exclusão e redireciona para listagem.
 */
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
	
	$campo_nome = $banco['versao']; $editar[$campo_tabela][] = $campo_nome." = ".$campo_nome." + 1";
	$campo_nome = $banco['data_modificacao']; $editar[$campo_tabela][] = $campo_nome."=NOW()";
	
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

/**
 * Inicializa a interface de alteração de status de registro.
 * 
 * Prepara o sistema para alterar o status de um registro (ativar/desativar),
 * validando o ID e status fornecidos via GET e armazenando em variáveis globais.
 * Redireciona para raiz se ID ou status não fornecidos.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 *                        Recebe: $_REQUEST['id'], $_REQUEST['status'].
 *                        Define: $_GESTOR['modulo-registro-id'], $_GESTOR['modulo-registro-status'].
 * 
 * @param array|false $params Parâmetros da função (não utilizado nesta função).
 * 
 * @return void Prepara $_GESTOR para alteração de status ou redireciona.
 */
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

/**
 * Finaliza a interface de alteração de status de registro.
 * 
 * Executa a alteração de status do registro (ex: A=Ativo, I=Inativo) no banco
 * de dados. Suporta personalização da tabela, inclusão de histórico, e callback
 * após alteração. Redireciona para listagem após sucesso.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 *                        Usa: $_GESTOR['modulo-registro-id'], $_GESTOR['modulo-registro-status'].
 *                        
 * @param array|false $params Parâmetros da função.
 * @param array $params['banco'] Dados da tabela customizada (nome, id, status, where).
 * @param bool $params['historico'] Se false, desativa inclusão no histórico (padrão: ativa).
 * @param string $params['callbackFunction'] Função callback a executar após alteração.
 * 
 * @return void Executa alteração de status e redireciona para listagem.
 */
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
	
	$campo_nome = $banco['versao']; $editar[$campo_tabela][] = $campo_nome." = ".$campo_nome." + 1";
	$campo_nome = $banco['data_modificacao']; $editar[$campo_tabela][] = $campo_nome."=NOW()";
	
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

function interface_clonar_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(isset($_REQUEST['_gestor-adicionar'])){
		$_GESTOR['adicionar-banco'] = true;
	} else {
		// ===== Parâmetros
	
		// forcarId - String - Opcional - Caso queira usar a opção editar diretamente passando o id do dado.
		
		// ===== 
		
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
	// sem_botao_padrao - Bool - Opcional - Se deve ou não incluir o botão padrão.
	// botoes_rodape - Array - Opcional - Conjunto de botões no rodapé do formnulário para ações extras.
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

	$form_opcao = 'adicionar';
	if(isset($formulario)){
		// ===== Formulário Opção
		
		if(isset($formulario['opcao']) && $formulario['opcao'] != ''){
			$form_opcao = $formulario['opcao'];
		}
	}

	$pagina = modelo_var_troca($pagina,"#form-opcao#",$form_opcao);

	if(isset($sem_botao_padrao) && $sem_botao_padrao === true){
		$pagina = modelo_tag_del($pagina,'<!-- botao-padrao < -->','<!-- botao-padrao > -->','');
	}

	$pagina = modelo_var_troca($pagina,"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_del($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}

	if(isset($botoes_rodape)){
		$botoes_html = interface_botoes_rodape($params);
		$pagina = modelo_var_troca($pagina,"#botoes-rodape#",$botoes_html);
	} else {
		$cel_nome = 'botoes-rodape'; $pagina = modelo_tag_del($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
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
	
	// sem_botao_padrao - Bool - Opcional - Se deve ou não incluir o botão padrão.
	// botoes_rodape - Array - Opcional - Conjunto de botões no rodapé do formnulário para ações extras.
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

	if(isset($sem_botao_padrao) && $sem_botao_padrao === true){
		$pagina = modelo_tag_in($pagina,'<!-- botao-padrao < -->','<!-- botao-padrao > -->','');
	}
	
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

	if(isset($botoes_rodape)){
		$botoes_html = interface_botoes_rodape($params);
		$pagina = modelo_var_troca($pagina,"#botoes-rodape#",$botoes_html);
	} else {
		$cel_nome = 'botoes-rodape'; $pagina = modelo_tag_del($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
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
			'id' => NULL,
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
	
	if(isset($_GESTOR['modulo-registro-padrao-id']) && !isset($_GESTOR['modulo-registro-id'])){
		$_GESTOR['modulo-registro-id'] = $_GESTOR['modulo-registro-padrao-id'];
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
	
	// sem_botao_padrao - Bool - Opcional - Se deve ou não incluir o botão padrão.
	// botoes_rodape - Array - Opcional - Conjunto de botões no rodapé do formnulário para ações extras.
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

	if(isset($sem_botao_padrao) && $sem_botao_padrao === true){
		$pagina = modelo_tag_in($pagina,'<!-- botao-padrao < -->','<!-- botao-padrao > -->','');
	}
	
	if(isset($botoes)){
		$botoes_html = interface_botoes_cabecalho($params);
		$pagina = modelo_var_troca($pagina,"#botoes#",$botoes_html);
	} else {
		$cel_nome = 'botoes'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}

	if(isset($botoes_rodape)){
		$botoes_html = interface_botoes_rodape($params);
		$pagina = modelo_var_troca($pagina,"#botoes-rodape#",$botoes_html);
	} else {
		$cel_nome = 'botoes-rodape'; $pagina = modelo_tag_del($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
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
	
	if(isset($_REQUEST['columnsExtraSearch'])){
		$columnsExtraSearch = $_REQUEST['columnsExtraSearch'];
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
			
			if(isset($columnsExtraSearch)){
				if(strlen($search) > 0){
					foreach($columnsExtraSearch as $col){
						$procurar .= (strlen($procurar) > 0 ? ' OR ':'')."UCASE(".$col.") LIKE UCASE('%".$search."%')";
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
				if(isset($tabela['rodape'])) $tabela_rodape = modelo_var_in($tabela_rodape,"#rows#",$row);
				
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
			
			// ===== incluir colunas extras apenas para busca.
			
			if(!isset($interface['columnsExtraSearch'])){
				$interface['columnsExtraSearch'][] = 'id';
			}
			
			// ===== Coluna status caso precise ativar/desativar registro 
			
			if(isset($banco['status'])){
				$row = '
				<th>Status</th>';
				
				$tabela_cabecalho = modelo_var_in($tabela_cabecalho,"#rows#",$row);
				if(isset($tabela['rodape'])) $tabela_rodape = modelo_var_in($tabela_rodape,"#rows#",$row);
				
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
			if(isset($tabela['rodape'])) $tabela_rodape = modelo_var_in($tabela_rodape,"#rows#",$row);
			
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
			if(isset($tabela['rodape'])) $tabela_rodape = modelo_var_troca($tabela_rodape,"#rows#",'');
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
		
		$lista_tabela = modelo_var_troca($lista_tabela,"#rows#",$tabela_cabecalho.$tabela_dados.(isset($tabela['rodape']) ? $tabela_rodape : '' ));
		
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
				'columnsExtraSearch' => $interface['columnsExtraSearch'],
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
		// cabecalho - String - Opcional - Incluir opções extra no cabeçalho da tabela.
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

	if(isset($tabela['cabecalho'])){
		$pagina = modelo_var_troca($pagina,"#cabecalho#",$tabela['cabecalho']);
	} else {
		$cel_nome = 'cabecalho'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
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

		// ===== Incluir AJAX interface de bibliotecas

		$bibliotecas = Array();

		if(isset($_GESTOR['bibliotecas'])){
			$bibliotecas = array_merge($bibliotecas,$_GESTOR['bibliotecas']);
		}

		if(isset($_GESTOR['modulo#'.$_GESTOR['modulo-id']]['bibliotecas'])){
			$bibliotecas = array_merge($bibliotecas,$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['bibliotecas']);
		}

		foreach($bibliotecas as $biblioteca){
			switch($biblioteca){
				case 'html-editor': html_editor_ajax_interface(); break;
			}
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
		case 'clonar': interface_clonar_iniciar($parametros); break;
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
			case 'adicionar': interface_adicionar_iniciar($parametros); break;
			case 'clonar': interface_clonar_iniciar($parametros); break;
			case 'editar': interface_editar_iniciar($parametros); break;
			case 'status': interface_status_iniciar($parametros); break;
			case 'excluir': interface_excluir_iniciar($parametros); break;
			case 'listar': interface_listar_iniciar($parametros); break;
			case 'config': interface_config_iniciar($parametros); break;
			case 'visualizar': interface_visualizar_iniciar($parametros); break;
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
		case 'adicionar':
		case 'clonar':
			interface_adicionar_finalizar($parametros);
		break;
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
			case 'adicionar':
			case 'clonar':
				interface_adicionar_finalizar($parametros);
			break;
			case 'editar': interface_editar_finalizar($parametros); break;
			case 'status': interface_status_finalizar($parametros); break;
			case 'excluir': interface_excluir_finalizar($parametros); break;
			case 'listar': interface_listar_finalizar($parametros); break;
			case 'config': interface_config_finalizar($parametros); break;
			case 'visualizar': interface_visualizar_finalizar($parametros); break;
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