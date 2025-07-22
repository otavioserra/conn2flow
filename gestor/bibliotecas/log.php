<?php

global $_GESTOR;

$_GESTOR['biblioteca-log']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function log_debugar($params = false){
	/**********
		Descrição: log para debugar.
	**********/
	
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// alteracoes - Array - Opcional - Conjunto de dados relativos a alteração que foi feita num dados registro.
		// alteracao - String - Opcional - Identificador da alteração. Sistema buscará o valor na linguagem: interface/id do campo.
		// alteracao_txt - String - Opcional - Caso necessário completar uma alteração, este campo pode ser passado com o valor literal da alteração.
		// modulo - String - Opcional - Caso necessário, incluir o módulo de onde veio a requisição.
		// id - String - Opcional - Caso necessário, incluir id numérico do registro do módulo de onde veio a requisição.
	
	// =====
	
	if(isset($alteracoes)){
		$usuario = gestor_usuario();
		
		foreach($alteracoes as $alteracao){
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "modulo"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao_txt"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			$campo_nome = "data"; $campo_valor = 'NOW()'; 				$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"historico"
			);
		}
	}
}

function log_controladores($params = false){
	/**********
		Descrição: log dos controladores.
	**********/
	
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host que disparou o registro de histórico.
	// controlador - String - Obrigatório - Identificador do controlador que disparou o registro de histórico.
	// id - Int - Obrigatório - ID númerico do registro que será manualmente definido.
	// alteracoes - Array - Obrigatório - Conjunto de dados relativos a alteração que foi feita num dados registro.
		// alteracao - String - Obrigatório - Identificador da alteração. Sistema buscará o valor na linguagem: interface/id do campo.
		// alteracao_txt - String - Obrigatório - Caso necessário completar uma alteração, este campo pode ser passado com o valor literal da alteração.
		// modulo - String - Opcional - Caso necessário, incluir o módulo de onde veio a requisição.
	// tabela - Array - Obrigatório - Tabela que será usada ao invés da tabela principal do módulo.
		// nome - String - Obrigatório - nome da tabela do banco de dados.
		// versao - String - Obrigatório - Campo versao da tabela do banco de dados.
		// id_numerico - String - Obrigatório - identificador numérico dos dados da tabela.
	// sem_id - Bool - Opcional - Caso definido, não vinculará nenhum ID ao histórico.
		// versao - Int - Opcional - Definir manualmente a versão do registro.
	
	// =====
	
	if(isset($id_hosts) && isset($controlador) && isset($id) && isset($alteracoes) && isset($tabela)){
		// ===== Pegar versão.
		
		if(!isset($sem_id)){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$tabela['versao'],
				))
				,
				$tabela['nome'],
				"WHERE ".$tabela['id_numerico']."='".$id."'"
			);
			
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		// ===== Incluir histórico no banco de dados.
		
		foreach($alteracoes as $alteracao){
			banco_insert_name_campo('id_hosts',$id_hosts);
			banco_insert_name_campo('controlador',$controlador);
			banco_insert_name_campo('versao',$versao_bd);
			if(!isset($sem_id)){ banco_insert_name_campo('id',$id); }
			if(isset($alteracao['modulo'])){ banco_insert_name_campo('modulo',$alteracao['modulo']); }
			if(isset($alteracao['alteracao'])){ banco_insert_name_campo('alteracao',$alteracao['alteracao']); }
			if(isset($alteracao['alteracao_txt'])){ banco_insert_name_campo('alteracao_txt',$alteracao['alteracao_txt']); }
			banco_insert_name_campo('data','NOW()',true);
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"historico"
			);
		}
	}
}

function log_usuarios($params = false){
	/**********
		Descrição: log dos usuários.
	**********/
	
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host que disparou o registro de histórico.
	// id_usuarios - Int - Obrigatório - Identificador do usuário que disparou o registro de histórico.
	// id - Int - Obrigatório - ID númerico do registro que será manualmente definido.
	// alteracoes - Array - Obrigatório - Conjunto de dados relativos a alteração que foi feita num dados registro.
		// alteracao - String - Obrigatório - Identificador da alteração. Sistema buscará o valor na linguagem: interface/id do campo.
		// alteracao_txt - String - Obrigatório - Caso necessário completar uma alteração, este campo pode ser passado com o valor literal da alteração.
		// modulo - String - Opcional - Caso necessário, incluir o módulo de onde veio a requisição.
	// tabela - Array - Obrigatório - Tabela que será usada ao invés da tabela principal do módulo.
		// nome - String - Obrigatório - nome da tabela do banco de dados.
		// versao - String - Obrigatório - Campo versao da tabela do banco de dados.
		// id_numerico - String - Obrigatório - identificador numérico dos dados da tabela.
	// sem_id - Bool - Opcional - Caso definido, não vinculará nenhum ID ao histórico.
		// versao - Int - Opcional - Definir manualmente a versão do registro.
	
	// =====
	
	if(isset($id_hosts) && isset($id_usuarios) && isset($id) && isset($alteracoes) && isset($tabela)){
		// ===== Pegar versão.
		
		if(!isset($sem_id)){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$tabela['versao'],
				))
				,
				$tabela['nome'],
				"WHERE ".$tabela['id_numerico']."='".$id."'"
			);
			
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		// ===== Incluir histórico no banco de dados.
		
		foreach($alteracoes as $alteracao){
			banco_insert_name_campo('id_hosts',$id_hosts);
			banco_insert_name_campo('id_usuarios',$id_usuarios);
			banco_insert_name_campo('versao',$versao_bd);
			if(!isset($sem_id)){ banco_insert_name_campo('id',$id); }
			if(isset($alteracao['modulo'])){ banco_insert_name_campo('modulo',$alteracao['modulo']); }
			if(isset($alteracao['alteracao'])){ banco_insert_name_campo('alteracao',$alteracao['alteracao']); }
			if(isset($alteracao['alteracao_txt'])){ banco_insert_name_campo('alteracao_txt',$alteracao['alteracao_txt']); }
			banco_insert_name_campo('data','NOW()',true);
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"historico"
			);
		}
	}
}

function log_hosts_usuarios($params = false){
	/**********
		Descrição: log dos hosts usuários.
	**********/
	
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host que disparou o registro de histórico.
	// id_hosts_usuarios - Int - Obrigatório - Identificador do usuário do host que disparou o registro de histórico.
	// id - Int - Obrigatório - ID númerico do registro que será manualmente definido.
	// alteracoes - Array - Obrigatório - Conjunto de dados relativos a alteração que foi feita num dados registro.
		// alteracao - String - Obrigatório - Identificador da alteração. Sistema buscará o valor na linguagem: interface/id do campo.
		// alteracao_txt - String - Obrigatório - Caso necessário completar uma alteração, este campo pode ser passado com o valor literal da alteração.
		// modulo - String - Opcional - Caso necessário, incluir o módulo de onde veio a requisição.
	// tabela - Array - Obrigatório - Tabela que será usada ao invés da tabela principal do módulo.
		// nome - String - Obrigatório - nome da tabela do banco de dados.
		// versao - String - Obrigatório - Campo versao da tabela do banco de dados.
		// id_numerico - String - Obrigatório - identificador numérico dos dados da tabela.
	// sem_id - Bool - Opcional - Caso definido, não vinculará nenhum ID ao histórico.
		// versao - Int - Opcional - Definir manualmente a versão do registro.
	
	// =====
	
	if(isset($id_hosts) && isset($id_hosts_usuarios) && isset($id) && isset($alteracoes) && isset($tabela)){
		// ===== Pegar versão.
		
		if(!isset($sem_id)){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$tabela['versao'],
				))
				,
				$tabela['nome'],
				"WHERE ".$tabela['id_numerico']."='".$id."'"
			);
			
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		// ===== Incluir histórico no banco de dados.
		
		foreach($alteracoes as $alteracao){
			banco_insert_name_campo('id_hosts',$id_hosts);
			banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
			banco_insert_name_campo('versao',$versao_bd);
			if(!isset($sem_id)){ banco_insert_name_campo('id',$id); }
			if(isset($alteracao['modulo'])){ banco_insert_name_campo('modulo',$alteracao['modulo']); }
			if(isset($alteracao['alteracao'])){ banco_insert_name_campo('alteracao',$alteracao['alteracao']); }
			if(isset($alteracao['alteracao_txt'])){ banco_insert_name_campo('alteracao_txt',$alteracao['alteracao_txt']); }
			banco_insert_name_campo('data','NOW()',true);
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"historico"
			);
		}
	}
}

function log_disco($msg, $logFilename = "gestor"){
	/**********
		Descrição: log de mensagens em disco.
	**********/
	
	global $_GESTOR;
	
	// ===== Parâmetros
	
	// msg - String - Obrigatório - Mensagem que será gravada em disco.
	
	// ===== 
	
	$msg = '['.date('D, d M Y H:i:s').'] '.$msg;
	
	if($_GESTOR['debug']){
		echo $msg . "\n";
	} else {
		$path = $_GESTOR['logs-path'];
		
		$myFile = $path . $logFilename.'-'.date('d-m-Y').".log";
		
		if(file_exists($myFile) && filesize($myFile) > 0){
			$file = file_get_contents($myFile);
		}
		
		file_put_contents($myFile,($file ? $file : '') . $msg . "\n");
	}
}

?>