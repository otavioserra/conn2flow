<?php
/**
 * Biblioteca de logs e histórico do sistema.
 *
 * Gerencia o sistema de logs e histórico de alterações do Conn2Flow.
 * Suporta logs em disco, logs de debug, histórico de controladores,
 * usuários e hosts. Mantém rastreabilidade completa das operações.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.1.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-log']							=	Array(
	'versao' => '1.1.0',
);

// ===== Defaults para evitar avisos de índices não definidos
// Inicializa configurações de debug se não existir
if (!isset($_GESTOR['debug'])) { $_GESTOR['debug'] = false; }

// Define caminho padrão para logs se não configurado
if (!isset($_GESTOR['logs-path'])) {
	$defaultLogs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
	// Cria diretório de logs se não existir
	if ($defaultLogs && !is_dir($defaultLogs)) @mkdir($defaultLogs, 0775, true);
	$_GESTOR['logs-path'] = $defaultLogs ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR;
}

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Registra alterações para debug no histórico.
 *
 * Cria registros de histórico com informações de alterações realizadas
 * por usuários autenticados. Usado principalmente para debugging e
 * auditoria de mudanças no sistema.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param array $params['alteracoes'] Conjunto de alterações realizadas (opcional).
 * @param string $params['alteracoes'][]['alteracao'] ID da alteração (opcional).
 * @param string $params['alteracoes'][]['alteracao_txt'] Texto literal da alteração (opcional).
 * @param string $params['alteracoes'][]['modulo'] Módulo de origem (opcional).
 * @param string $params['alteracoes'][]['id'] ID numérico do registro (opcional).
 * 
 * @return void
 */
function log_debugar($params = false){
	global $_GESTOR;

	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Processa cada alteração se fornecidas
	if(isset($alteracoes)){
		// Obtém dados do usuário logado
		$usuario = gestor_usuario();
		
		// Registra cada alteração no histórico
		foreach($alteracoes as $alteracao){
			$campos = null; $campo_sem_aspas_simples = null;
			
			// Campos obrigatórios
			$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			// Campos opcionais conforme disponibilidade
			$campo_nome = "id"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "modulo"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao_txt"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			// Timestamp da alteração
			$campo_nome = "data"; $campo_valor = 'NOW()'; 				$campos[] = Array($campo_nome,$campo_valor,true);
			
			// Insere no banco de dados
			banco_insert_name
			(
				$campos,
				"historico"
			);
		}
	}
}

/**
 * Registra log de alterações realizadas por controladores.
 *
 * Cria registros de histórico vinculados a controladores específicos,
 * incluindo versionamento automático e rastreamento de alterações.
 * Utilizado principalmente para auditoria de operações de controladores.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param int $params['id_hosts'] ID do host (obrigatório).
 * @param string $params['controlador'] ID do controlador (obrigatório).
 * @param int $params['id'] ID numérico do registro (obrigatório).
 * @param array $params['alteracoes'] Conjunto de alterações (obrigatório).
 * @param string $params['alteracoes'][]['alteracao'] ID da alteração (obrigatório).
 * @param string $params['alteracoes'][]['alteracao_txt'] Texto da alteração (obrigatório).
 * @param string $params['alteracoes'][]['modulo'] Módulo de origem (opcional).
 * @param array $params['tabela'] Configuração da tabela (obrigatório).
 * @param string $params['tabela']['nome'] Nome da tabela (obrigatório).
 * @param string $params['tabela']['versao'] Campo de versão (obrigatório).
 * @param string $params['tabela']['id_numerico'] Campo ID numérico (obrigatório).
 * @param bool $params['sem_id'] Se true, não vincula ID ao histórico (opcional).
 * @param int $params['versao'] Versão manual do registro (opcional).
 * 
 * @return void
 */
function log_controladores($params = false){
	global $_GESTOR;

	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($id_hosts) && isset($controlador) && isset($id) && isset($alteracoes) && isset($tabela)){
		// ===== Buscar versão do registro no banco de dados
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
			
			// Versão vem do banco
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			// Versão manual ou padrão
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		// ===== Registrar cada alteração no histórico
		foreach($alteracoes as $alteracao){
			// Campos obrigatórios
			banco_insert_name_campo('id_hosts',$id_hosts);
			banco_insert_name_campo('controlador',$controlador);
			banco_insert_name_campo('versao',$versao_bd);
			
			// Campos condicionais
			if(!isset($sem_id)){ banco_insert_name_campo('id',$id); }
			if(isset($alteracao['modulo'])){ banco_insert_name_campo('modulo',$alteracao['modulo']); }
			if(isset($alteracao['alteracao'])){ banco_insert_name_campo('alteracao',$alteracao['alteracao']); }
			if(isset($alteracao['alteracao_txt'])){ banco_insert_name_campo('alteracao_txt',$alteracao['alteracao_txt']); }
			
			// Timestamp da alteração
			banco_insert_name_campo('data','NOW()',true);
			
			// Insere registro no histórico
			banco_insert_name
			(
				banco_insert_name_campos(),
				"historico"
			);
		}
	}
}

/**
 * Registra log de alterações realizadas por usuários do sistema.
 *
 * Cria registros de histórico vinculados a usuários específicos do sistema,
 * incluindo versionamento automático e rastreamento de alterações.
 * Similar a log_controladores mas focado em ações de usuários.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param int $params['id_hosts'] ID do host (obrigatório).
 * @param int $params['id_usuarios'] ID do usuário do sistema (obrigatório).
 * @param int $params['id'] ID numérico do registro (obrigatório).
 * @param array $params['alteracoes'] Conjunto de alterações (obrigatório).
 * @param string $params['alteracoes'][]['alteracao'] ID da alteração (obrigatório).
 * @param string $params['alteracoes'][]['alteracao_txt'] Texto da alteração (obrigatório).
 * @param string $params['alteracoes'][]['modulo'] Módulo de origem (opcional).
 * @param array $params['tabela'] Configuração da tabela (obrigatório).
 * @param string $params['tabela']['nome'] Nome da tabela (obrigatório).
 * @param string $params['tabela']['versao'] Campo de versão (obrigatório).
 * @param string $params['tabela']['id_numerico'] Campo ID numérico (obrigatório).
 * @param bool $params['sem_id'] Se true, não vincula ID ao histórico (opcional).
 * @param int $params['versao'] Versão manual do registro (opcional).
 * 
 * @return void
 */
function log_usuarios($params = false){
	global $_GESTOR;

	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($id_hosts) && isset($id_usuarios) && isset($id) && isset($alteracoes) && isset($tabela)){
		// ===== Buscar versão do registro no banco de dados
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
			
			// Versão vem do banco
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			// Versão manual ou padrão
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		// ===== Registrar cada alteração no histórico
		foreach($alteracoes as $alteracao){
			// Campos obrigatórios
			banco_insert_name_campo('id_hosts',$id_hosts);
			banco_insert_name_campo('id_usuarios',$id_usuarios);
			banco_insert_name_campo('versao',$versao_bd);
			
			// Campos condicionais
			if(!isset($sem_id)){ banco_insert_name_campo('id',$id); }
			if(isset($alteracao['modulo'])){ banco_insert_name_campo('modulo',$alteracao['modulo']); }
			if(isset($alteracao['alteracao'])){ banco_insert_name_campo('alteracao',$alteracao['alteracao']); }
			if(isset($alteracao['alteracao_txt'])){ banco_insert_name_campo('alteracao_txt',$alteracao['alteracao_txt']); }
			
			// Timestamp da alteração
			banco_insert_name_campo('data','NOW()',true);
			
			// Insere registro no histórico
			banco_insert_name
			(
				banco_insert_name_campos(),
				"historico"
			);
		}
	}
}

/**
 * Registra log de alterações realizadas por usuários de hosts.
 *
 * Cria registros de histórico vinculados a usuários específicos de hosts,
 * incluindo versionamento automático e rastreamento de alterações.
 * Usado para rastrear ações de usuários vinculados a hosts específicos.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param int $params['id_hosts'] ID do host (obrigatório).
 * @param int $params['id_hosts_usuarios'] ID do usuário do host (obrigatório).
 * @param int $params['id'] ID numérico do registro (obrigatório).
 * @param array $params['alteracoes'] Conjunto de alterações (obrigatório).
 * @param string $params['alteracoes'][]['alteracao'] ID da alteração (obrigatório).
 * @param string $params['alteracoes'][]['alteracao_txt'] Texto da alteração (obrigatório).
 * @param string $params['alteracoes'][]['modulo'] Módulo de origem (opcional).
 * @param array $params['tabela'] Configuração da tabela (obrigatório).
 * @param string $params['tabela']['nome'] Nome da tabela (obrigatório).
 * @param string $params['tabela']['versao'] Campo de versão (obrigatório).
 * @param string $params['tabela']['id_numerico'] Campo ID numérico (obrigatório).
 * @param bool $params['sem_id'] Se true, não vincula ID ao histórico (opcional).
 * @param int $params['versao'] Versão manual do registro (opcional).
 * 
 * @return void
 */
function log_hosts_usuarios($params = false){
	global $_GESTOR;

	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($id_hosts) && isset($id_hosts_usuarios) && isset($id) && isset($alteracoes) && isset($tabela)){
		// ===== Buscar versão do registro no banco de dados
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
			
			// Versão vem do banco
			$versao_bd = $resultado[0][$tabela['versao']];
		} else {
			// Versão manual ou padrão
			$versao_bd = (isset($versao) ? $versao : '1');
		}
		
		// ===== Registrar cada alteração no histórico
		foreach($alteracoes as $alteracao){
			// Campos obrigatórios
			banco_insert_name_campo('id_hosts',$id_hosts);
			banco_insert_name_campo('id_hosts_usuarios',$id_hosts_usuarios);
			banco_insert_name_campo('versao',$versao_bd);
			
			// Campos condicionais
			if(!isset($sem_id)){ banco_insert_name_campo('id',$id); }
			if(isset($alteracao['modulo'])){ banco_insert_name_campo('modulo',$alteracao['modulo']); }
			if(isset($alteracao['alteracao'])){ banco_insert_name_campo('alteracao',$alteracao['alteracao']); }
			if(isset($alteracao['alteracao_txt'])){ banco_insert_name_campo('alteracao_txt',$alteracao['alteracao_txt']); }
			
			// Timestamp da alteração
			banco_insert_name_campo('data','NOW()',true);
			
			// Insere registro no histórico
			banco_insert_name
			(
				banco_insert_name_campos(),
				"historico"
			);
		}
	}
}

/**
 * Grava mensagens de log em arquivo de disco.
 *
 * Registra mensagens em arquivos de log organizados por data.
 * Suporta modo debug (saída em tela) e gravação em disco.
 * Ideal para rastreamento de operações e debugging.
 *
 * @global array $_GESTOR Configurações globais, incluindo debug e logs-path.
 * 
 * @param string $msg Mensagem a ser gravada no log (obrigatório).
 * @param string $logFilename Nome base do arquivo de log sem extensão (padrão: "gestor").
 * @param bool $deleteFileAfter Se true, exclui o arquivo antes de gravar (padrão: false).
 * 
 * @return void
 */
function log_disco($msg, $logFilename = "gestor", $deleteFileAfter = false){
	global $_GESTOR;
	
	// ===== Formata mensagem com timestamp
	$msg = '['.date('Y-m-d H:i:s').'] '.$msg;
	
	// ===== Modo debug: exibe em tela ao invés de gravar
	if (!empty($_GESTOR['debug'])) { echo $msg . "\n"; return; }
	
	// ===== Determina caminho para os logs
	$path = $_GESTOR['logs-path'] ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR;
	// Cria diretório se não existir
	if (!is_dir($path)) @mkdir($path, 0775, true);
	
	// ===== Define nome do arquivo com data atual
	$myFile = $path . $logFilename.'-'.date('Y-m-d').".log";
	
	// ===== Opcionalmente exclui arquivo existente
	if ($deleteFileAfter && is_file($myFile)) {
		@unlink($myFile);
	}
	
	// ===== Preserva conteúdo existente e adiciona nova mensagem
	$existing = (is_file($myFile) && filesize($myFile) > 0) ? file_get_contents($myFile) : '';
	file_put_contents($myFile, $existing . $msg . "\n");
}

?>