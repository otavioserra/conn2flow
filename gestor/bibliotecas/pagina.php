<?php

global $_GESTOR;

$_GESTOR['biblioteca-pagina']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function pagina_celula($nome,$comentario = false,$apagar = false){
	/**********
		Descrição: trocar variável da página por um valor.
	**********/
	
	global $_GESTOR;
	
	if(isset($nome)){
		if($comentario){
			$celula = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$nome.' [[',']] '.$nome.' -->'); 
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$nome.' [[',']] '.$nome.' -->',($apagar ? '' : '<!-- [['.$nome.']] -->'));
		} else {
			$celula = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$nome.' < -->','<!-- '.$nome.' > -->');
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$nome.' < -->','<!-- '.$nome.' > -->',($apagar ? '' : '<!-- '.$nome.' -->'));
		}
		
		return $celula;
	}
	
	return '';
}

function pagina_celula_trocar_variavel_valor($celula,$variavel,$valor,$variavelEspecifica = false){
	/**********
		Descrição: trocar variável de uma célula específica por um valor.
	**********/
	
	global $_GESTOR;
	
	if(isset($celula) && isset($variavel)){
		if($variavelEspecifica){
			return modelo_var_troca_tudo($celula,$variavel,(isset($valor) ? $valor : ''));
		} else {
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			
			return modelo_var_troca_tudo($celula,$open.$variavel.$close,(isset($valor) ? $valor : ''));
		}
	} else {
		return $celula;
	}
}

function pagina_celula_incluir($celula,$valor){
	/**********
		Descrição: incluir célula na página.
	**********/
	
	global $_GESTOR;
	
	if(isset($celula) && isset($valor)){
		$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$celula.' -->',(isset($valor)) ? $valor : '');
	}
}

function pagina_trocar_variavel_valor($variavel,$valor,$variavelEspecifica = false){
	/**********
		Descrição: trocar variável da página por um valor.
	**********/
	
	global $_GESTOR;
	
	if(isset($variavel) && isset($valor)){
		if($variavelEspecifica){
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$variavel,$valor);
		} else {
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$variavel.$close,$valor);
		}
	}
}

function pagina_trocar_variavel($params = false){
	/**********
		Descrição: trocar variável por um valor de um código qualquer.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// codigo - String - Obrigatório - Código com a variável.
	// variavel - String - Obrigatório - Variável a ser procurada.
	// valor - String - Obrigatório - Valor a ser trocado.
	
	// ===== 
	
	if(isset($codigo) && isset($variavel) && isset($valor)){
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		
		return modelo_var_troca_tudo($codigo,$open.$variavel.$close,$valor);
	}
}

function pagina_variaveis_globais_mascarar($params = false){
	/**********
		Descrição: mascarar todas as variáveis globais de [[variavel-nome]] para @[[variavel-nome]]@ afim de incluir no banco de dados o resultado.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - Valor a ser mascarado para guardar no banco de dados.
	
	// ===== 
	
	if(isset($valor)){
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		return preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $valor);
	}
	
	return '';
}

function pagina_variaveis_globais_desmascarar($params = false){
	/**********
		Descrição: desmascarar todas as variáveis globais de @[[variavel-nome]]@ para [[variavel-nome]] vinda do banco de dados para usar livremente o texto.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// valor - String - Obrigatório - Valor a ser desmascarado vindo do banco de dados.
	
	// ===== 
	
	if(isset($valor)){
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		return preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $valor);
	}
	
	return '';
}

function pagina_adicionar($params = false){
	/**********
		Descrição: adicionar uma página (api-cliente não aplicada).
	**********/
	
	global $_GESTOR;
	
	$config = gestor_incluir_configuracao(Array(
		'id' => 'pagina.config',
	));
	
	$tabelaPadrao = $config['padrao'];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// dados - Array - Obrigatório - Conjunto com todos os dados a serem adicionados na página.
		// nome - String - Obrigatório - Nome da página.
		// id_hosts_layouts - Int - Obrigatório - Identificador do layout da página.
		// caminho - String - Obrigatório - Caminho da página.
		// tipo - String - Opcional - Tipo da página.
		// html - String - Opcional - HTML da página.
		// css - String - Opcional - CSS da página.
		// modulo - String - Opcional - Módulo afim de referenciar outra informação como serviços, postagens, etc.
		// modulo_id_registro - Int - Opcional - ID do registro do módulo afim de referenciar outra informação como serviços, postagens, etc.
		
	// tabela - Array - Opcional - Conjunto com todos os dados da tabela da página.
		// nome - String - Opcional - Nome da tabela.
		// id - String - Opcional - Nome do Identificador do registro.
		// id_numerico - String - Opcional - Nome do Identificador numérico do registro.
	
	// ===== 
	
	if(!isset($tabela)){
		$tabela = $tabelaPadrao;
	} else {
		foreach($tabelaPadrao as $key => $valor){
			if(!isset($tabela[$key])){ 
				$tabela[$key] = $valor;
			}
		}
	}
	
	if(isset($dados)){
		if(isset($dados["nome"]) && isset($dados["id_hosts_layouts"])){
			// ===== Pegar dados do usuário.
			
			$usuario = gestor_usuario();
			
			// ===== Definição do identificador.
			
			$campos = null;
			$campo_sem_aspas_simples = false;
			
			$id = banco_identificador(Array(
				'id' => banco_escape_field($dados["nome"]),
				'tabela' => Array(
					'nome' => $tabela['nome'],
					'campo' => $tabela['id'],
					'id_nome' => $tabela['id_numerico'],
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
				),
			));
			
			// ===== Campos gerais
			
			$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			foreach($dados as $key => $valor){
				switch($key){
					case 'html': 
					case 'css': 
						$valor = pagina_variaveis_globais_mascarar(Array('valor' => $valor));
					break;
				}
				
				$campo_nome = $key; $campo_valor = banco_escape_field($valor); 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			}
			
			// ===== Campos comuns
			
			$campo_nome = $tabela['status']; $campo_valor = 'A'; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = $tabela['versao']; $campo_valor = '1'; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = $tabela['data_criacao']; $campo_valor = 'NOW()'; 					$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = $tabela['data_modificacao']; $campo_valor = 'NOW()';			 	$campos[] = Array($campo_nome,$campo_valor,true);
		
			banco_insert_name
			(
				$campos,
				$tabela['nome']
			);
			
			// ===== Pegar o identificador númerico da página criada.
			
			$id_paginas = banco_last_id();
			
			// ===== Retornar o identificador numérico da página.
			
			return $id_paginas;
		}
	}
	
	return null;
}

function pagina_editar($params = false){
	/**********
		Descrição: editar uma página (api-cliente não aplicada).
	**********/
	
	global $_GESTOR;
	
	$config = gestor_incluir_configuracao(Array(
		'id' => 'pagina.config',
	));
	
	$tabelaPadrao = $config['padrao'];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts_paginas - String - Obrigatório - Identificador numérico da página a ser editada.
	// dados - Array - Obrigatório - Conjunto com todos os dados a serem editados na página.
	
	// ===== 
	
	if(isset($id_hosts_paginas) && isset($dados)){
		$tabela = $tabelaPadrao;
		
		// ===== Valores antes da edição.
		
		foreach($dados as $key => $valor){
			if(isset($valor)){
				$campos[] = $key;
			}
		}
		
		$dadosAntes = banco_select(Array(
			'unico' => true,
			'tabela' => $tabela['nome'],
			'campos' => $campos,
			'extra' => 
				"WHERE ".$tabela['id_numerico']."='".$id_hosts_paginas."'"
		));
		
		// ===== Valores padrões da tabela e regras para o campo nome.
		
		$editar = Array(
			'tabela' => $tabela['nome'],
			'extra' => "WHERE ".$tabela['id_numerico']."='".$id_hosts_paginas."' AND id_hosts='".$_GESTOR['host-id']."'",
		);
		
		foreach($dados as $key => $valor){
			// ===== Se existe valor altere.
			
			if(isset($valor)){
				$alteracoes_name = $key;
				
				switch($key){
					case 'html':
					case 'css': 
						$valor = pagina_variaveis_globais_mascarar(Array('valor' => $valor));
					break;
					case 'nome':
						$alteracoes_name = 'name';
					break;
					case 'caminho':
						$alteracoes_name = 'path';
					break;
				}
				
				switch($key){
					case 'html':
					case 'css': 
						$campo_nome = $key; $editar['dados'][] = $campo_nome."='" . banco_escape_field($valor) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');
					break;
					default:
						$campo_nome = $key; $editar['dados'][] = $campo_nome."='" . banco_escape_field($valor) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => $dadosAntes[$campo_nome],'valor_depois' => banco_escape_field($valor));
				}
			}
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
			
			// ===== Editar campos padrões
			
			$campo_nome = $tabela['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = $tabela['data_modificacao']; $editar['dados'][] = $campo_nome."=NOW()";
			
			$editar['sql'] = banco_campos_virgulas($editar['dados']);
			
			if($editar['sql']){
				banco_update
				(
					$editar['sql'],
					$editar['tabela'],
					$editar['extra']
				);
			}
			$editar = false;
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'id_numerico_manual' => $id_hosts_paginas,
				'modulo_id' => 'paginas',
				'tabela' => $tabela,
				'alteracoes' => $alteracoes,
			));
		}	
	}
}

function pagina_status($params = false){
	/**********
		Descrição: status uma página (api-cliente não aplicada).
	**********/
	
	global $_GESTOR;
	
	$config = gestor_incluir_configuracao(Array(
		'id' => 'pagina.config',
	));
	
	$tabelaPadrao = $config['padrao'];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts_paginas - String - Obrigatório - Identificador numérico da página a ser editada.
	// status - String - Obrigatório - Status da página a ser editada.
	
	// ===== 
	
	if(isset($id_hosts_paginas) && isset($status)){
		$tabela = $tabelaPadrao;
		
		// ===== Alterar o status do registro para 'status'.
		
		$campo_tabela = $tabela['nome'];
		$campo_tabela_extra = "WHERE ".$tabela['id_numerico']."='".$id_hosts_paginas."'";
		
		$campo_nome = $tabela['status']; $editar[$campo_tabela][] = $campo_nome."='" . $status . "'";
		
		$campo_nome = "versao"; $editar[$campo_tabela][] = $campo_nome." = ".$campo_nome." + 1";
		$campo_nome = "data_modificacao"; $editar[$campo_tabela][] = $campo_nome."=NOW()";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		// ===== Incluir no histórico as alterações.
		
		if($status == 'A'){
			$valor_depois = 'field-status-active';
		} else {
			$valor_depois = 'field-status-inactive';
		}
		
		if($status == 'A'){
			$valor_antes = 'field-status-inactive';
		} else {
			$valor_antes = 'field-status-active';
		}
		
		interface_historico_incluir(Array(
			'id_numerico_manual' => $id_hosts_paginas,
			'modulo_id' => 'paginas',
			'tabela' => $tabela,
			'alteracoes' => Array(
				Array(
					'campo' => 'field-status',
					'alteracao' => 'historic-change-status',
					'valor_antes' => $valor_antes,
					'valor_depois' => $valor_depois,
				)
			),
		));
		
		// Executar mudança de status.
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
		$editar = false;$editar_sql = false;
	}
}

function pagina_excluir($params = false){
	/**********
		Descrição: excluir uma página (api-cliente não aplicada).
	**********/
	
	global $_GESTOR;
	
	$config = gestor_incluir_configuracao(Array(
		'id' => 'pagina.config',
	));
	
	$tabelaPadrao = $config['padrao'];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts_paginas - String - Obrigatório - Identificador numérico da página a ser editada.
	
	// ===== 
	
	if(isset($id_hosts_paginas)){
		$tabela = $tabelaPadrao;
		
		// ===== Alterar o status do registro para 'D' - Deletado
		
		$campo_tabela = $tabela['nome'];
		$campo_tabela_extra = "WHERE ".$tabela['id_numerico']."='".$id_hosts_paginas."'";
		
		$campo_nome = $tabela['status']; $editar[$campo_tabela][] = $campo_nome."='D'";
		
		$campo_nome = "versao"; $editar[$campo_tabela][] = $campo_nome." = ".$campo_nome." + 1";
		$campo_nome = "data_modificacao"; $editar[$campo_tabela][] = $campo_nome."=NOW()";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		// ===== Incluir no histórico as alterações.
		
		interface_historico_incluir(Array(
			'id_numerico_manual' => $id_hosts_paginas,
			'modulo_id' => 'paginas',
			'tabela' => $tabela,
			'alteracoes' => Array(
				Array(
					'alteracao' => 'historic-delete',
				)
			),
			'deletar' => true,
		));
		
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
	}
}

function pagina_301($params = false){
	/**********
		Descrição: criar uma entrada 301 quando mudar o caminho de uma página.
		Retorno: identificador numérico referencial 'id_hosts_paginas_301'.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// caminho - String - Obrigatório - Caminho da página que foi mudada.
	// id_hosts_paginas - String - Obrigatório - Identificador numérico da página mudada.
	
	// ===== 
	
	if(isset($caminho) && isset($id_hosts_paginas)){
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id_hosts_paginas"; $campo_valor = $id_hosts_paginas; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "caminho"; $campo_valor = $caminho; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 									$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"hosts_paginas_301"
		);
		
		$id_hosts_paginas_301 = banco_last_id();
		
		return $id_hosts_paginas_301;
	}
	
	return '';
}

?>