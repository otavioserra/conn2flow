<?php
/**
 * Biblioteca de operações com banco de dados.
 *
 * Fornece funções para manipulação de dados no banco de dados MySQL
 * usando MySQLi, incluindo operações de SELECT, INSERT, UPDATE e DELETE,
 * além de utilitários para escape de dados e gerenciamento de conexões.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.2.0
 */

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-banco']							=	Array(
	'versao' => '1.2.0',
);

/**
 * Escapa um valor para uso seguro em queries SQL.
 *
 * Utiliza mysqli_real_escape_string para prevenir SQL injection,
 * garantindo que valores inseridos em queries sejam seguros.
 *
 * @param string $field O valor a ser escapado.
 * 
 * @return string O valor escapado e seguro para uso em queries SQL.
 */
function banco_escape_field($field){
	global $_BANCO;
	
	// Verifica se precisa conectar ao banco
	$connect_db = false;
	if(!isset($_BANCO['conexao']))$connect_db = true;
	if($connect_db)banco_conectar();
	
	// Escapa o valor usando mysqli
	if($_BANCO['tipo'] == "mysqli"){
		return mysqli_real_escape_string($_BANCO['conexao'],$field);
	}
}

/**
 * Remove barras de escape de uma string de forma inteligente.
 *
 * Esta função retorna a string convertida para string sem processar
 * a remoção de barras invertidas, apenas garantindo que seja uma string.
 *
 * @param mixed $str O valor a ser processado.
 * 
 * @return string A string processada.
 */
function banco_smartstripslashes($str){
	/* $cd1 = substr_count($str, "\"");
	$cd2 = substr_count($str, "\\\"");
	$cs1 = substr_count($str, "'");
	$cs2 = substr_count($str, "\\'");
	$tmp = strtr($str, array("\\\"" => "", "\\'" => ""));
	$cb1 = substr_count($tmp, "\\");
	$cb2 = substr_count($tmp, "\\\\");
	
	if ($cd1 == $cd2 && $cs1 == $cs2 && $cb1 == 2 * $cb2) {
		return strtr($str, array("\\\"" => "\"", "\\'" => "'", "\\\\" => "\\"));
	} */
	
	return (string)$str;
}

/**
 * Gera informações de debug do backtrace para erros de banco.
 *
 * Retorna uma string HTML formatada com informações sobre a pilha
 * de chamadas, útil para debugging de erros de banco de dados.
 *
 * @return string HTML formatado com informações de debug.
 */
function banco_erro_debug(){
	// Obtém a pilha de chamadas
	$bt = debug_backtrace();
	
	// Monta o HTML de retorno com informações de debug
	if($bt){
		$ret = '<br><br><b>Debug:</b><br>';
		foreach($bt as $in){
			$ret .= '<br>'.$in['file'].':'.$in['line'].' => '.$in['function'];
		}
	}
	
	return $ret;
}

/**
 * Estabelece conexão com o banco de dados MySQL.
 *
 * Cria uma nova conexão MySQLi usando as credenciais armazenadas
 * na variável global $_BANCO. Configura o charset para UTF-8 e
 * habilita relatórios de erro estritos.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @return void
 */
function banco_conectar(){
	global $_BANCO;
	
	// Configura conexão MySQLi
 	if($_BANCO['tipo'] == "mysqli"){
		// Habilita relatórios de erro estritos
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		
		// Estabelece a conexão com o banco
 		$_BANCO['conexao'] = mysqli_connect($_BANCO['host'],$_BANCO['usuario'],$_BANCO['senha'],$_BANCO['nome']) or die("<p><b>ERRO BANCO:</b> Conexão com o banco de dados não realizada!</p><p><b>Erro MySQL:</b> ".mysqli_error($_BANCO['conexao']).'</p>'.banco_erro_debug());
		
		// Define charset UTF-8 para a conexão
		mysqli_set_charset($_BANCO['conexao'], "utf8");
	}
}

/**
 * Verifica se a conexão com o banco está ativa.
 *
 * Usa mysqli_ping para testar a conexão. Se a conexão estiver
 * inativa, incrementa o contador de reconexões.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @return void
 */
function banco_ping(){
	global $_BANCO;
	
 	if($_BANCO['tipo'] == "mysqli"){
		// Testa a conexão e incrementa contador se inativa
		if(!mysqli_ping($_BANCO['conexao'])){
			$_BANCO['RECONECT']++;
		}
	}
}

/**
 * Fecha a conexão ativa com o banco de dados.
 *
 * Encerra a conexão MySQLi ativa e limpa a variável de conexão
 * da variável global $_BANCO.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @return void
 */
function banco_fechar_conexao(){
	global $_BANCO;
	
	// Fecha a conexão MySQLi
	if($_BANCO['tipo'] == "mysqli")
 		$_BANCO['conexao'] = mysqli_close($_BANCO['conexao']) or die("<p><b>ERRO BANCO:</b> Impossível fechar conexão com o banco de dados!</p><p><b>Erro MySQL:</b> ".mysqli_error($_BANCO['conexao']).'</p>'.banco_erro_debug());
	
	// Remove a variável de conexão
	unset($_BANCO['conexao']);
}

/**
 * Executa uma query SQL no banco de dados.
 *
 * Executa uma query SQL usando MySQLi. Conecta automaticamente ao
 * banco se não houver conexão ativa. Trata exceções e registra erros.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param string $query A query SQL a ser executada.
 * 
 * @return mysqli_result|bool O resultado da query ou false em caso de erro.
 */
function banco_query($query){
    global $_BANCO;

    // Conecta ao banco se necessário
    $connect_db = false;
    if(!isset($_BANCO['conexao']))$connect_db = true;
    if($connect_db)banco_conectar();

    // Executa a query com tratamento de exceções
    if($_BANCO['tipo'] == "mysqli"){
        try {
            $result = mysqli_query($_BANCO['conexao'],$query);
            return $result;
        } catch (mysqli_sql_exception $e) {
            // Registra o erro no log
            error_log("ERRO BANCO: Consulta Inválida!\nConsulta: $query\nErro Mysql: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Retorna o número de linhas de um resultado de query.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param mysqli_result $result O resultado da query.
 * 
 * @return int O número de linhas no resultado.
 */
function banco_num_rows($result){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli")
		return mysqli_num_rows($result);
}

/**
 * Retorna o número de campos/colunas em um resultado de query.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param mysqli_result $result O resultado da query.
 * 
 * @return int O número de campos no resultado.
 */
function banco_num_fields($result){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli")
		return mysqli_num_fields($result);
}

/**
 * Retorna o nome de um campo específico do resultado.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param mysqli_result $result O resultado da query.
 * @param int $num_field O índice do campo (começando em 0).
 * 
 * @return string O nome do campo.
 */
function banco_field_name($result,$num_field){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli")
		return mysqli_fetch_field_direct($result,$num_field)->name;
}

/**
 * Retorna um array com os nomes de todos os campos de uma tabela.
 *
 * Executa uma query SELECT limitada e extrai os nomes de todos
 * os campos da tabela especificada.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param string $table Nome da tabela.
 * 
 * @return array|null Array com os nomes dos campos ou NULL se a tabela estiver vazia.
 */
function banco_fields_names($table){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli"){
		// Busca um registro para obter os nomes dos campos
		$res = banco_query("select * from ".$table." limit 1");
		
		// Extrai os nomes de todos os campos
		if(banco_num_fields($res)){
			$max = banco_num_fields($res);
			
			for($i=0;$i<$max;$i++){
				$rows[$i] = banco_field_name($res,$i);
			}
			
			return $rows;
		}
		else
			return NULL;
	}
}

/**
 * Retorna uma linha do resultado como array indexado.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param mysqli_result $result O resultado da query.
 * 
 * @return array|null Array com os valores da linha ou NULL se não houver mais linhas.
 */
function banco_row($result){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli")
		return mysqli_fetch_row($result);
}

/**
 * Retorna uma linha do resultado como array associativo e indexado.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param mysqli_result $result O resultado da query.
 * 
 * @return array|null Array com os valores da linha ou NULL se não houver mais linhas.
 */
function banco_row_array($result){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli")
		return mysqli_fetch_array($result);
}

/**
 * Retorna a próxima linha como array associativo.
 *
 * @global array $_BANCO Configurações de conexão do banco.
 * 
 * @param mysqli_result $result O resultado da query.
 * 
 * @return array|null Array associativo com os valores da linha ou NULL.
 */
function banco_fetch_assoc($result){
	global $_BANCO;
	// Retorna próxima linha como array associativo ou null.
	if($_BANCO['tipo'] == "mysqli"){
		return mysqli_fetch_assoc($result);
	}
	return null;
}

/**
 * Executa uma query SQL e retorna todos os resultados.
 *
 * @param string $sql A query SQL a ser executada.
 * 
 * @return array|null Array com todas as linhas do resultado ou NULL se não houver resultados.
 */
function banco_sql($sql){
	$res = banco_query($sql);
	
	// Processa todos os resultados se houver linhas
	if(banco_num_rows($res))
	{
		$max = banco_num_rows($res);
		
		for($i=0;$i<$max;$i++)
			$rows[$i] = banco_row_array($res);
		
		return $rows;
	}
	else
		return NULL;
}

/**
 * Executa uma query SQL e retorna resultados com nomes de campos.
 *
 * Similar a banco_sql, mas processa os resultados para retornar arrays
 * associativos com os nomes dos campos especificados.
 *
 * @param string $sql A query SQL a ser executada.
 * @param string $campos Lista de campos separados por vírgula ou '*' para todos.
 * 
 * @return array|null Array de arrays associativos ou NULL se não houver resultados.
 */
function banco_sql_names($sql,$campos){
	$res = banco_query($sql);
	
	// Processa resultados com nomes de campos
	if(banco_num_rows($res)){
		// Determina os nomes dos campos
		if($campos != '*'){
			$campos_name = explode(',',$campos);
		} else {
			$num_fields = banco_num_fields($res);
			
			for($i=0;$i<$num_fields;$i++){
				$campos_name[] = banco_field_name($res,$i);
			}
		}
		
		// Processa cada linha do resultado
		$max = banco_num_rows($res);
		
		for($i=0;$i<$max;$i++){
			$rows_aux = banco_row_array($res);
			$count=0;
			
			// Monta array associativo para cada campo
			foreach($campos_name as $campo_name){
				$rows_out[$campo_name] = ( !is_numeric($rows_aux[$count]) ? banco_smartstripslashes($rows_aux[$count]) : $rows_aux[$count]);
				$count++;
			}
			$rows[] = $rows_out;
		}
		
		return $rows;
	}
	else
		return NULL;
}

/**
 * Seleciona dados do banco de dados de forma estruturada.
 *
 * Função principal para seleção de dados que aceita parâmetros em array,
 * monta a query SQL automaticamente e retorna resultados formatados.
 * Suporta seleção de campos específicos, condições WHERE e retorno único.
 *
 * @param array|false $params Parâmetros da função.
 * @param array $params['campos'] Lista com todos os campos a serem selecionados (obrigatório).
 * @param string $params['tabela'] Nome da tabela do banco (obrigatório).
 * @param string $params['extra'] Valores extras (WHERE, ORDER BY, LIMIT, etc.) (opcional).
 * @param bool $params['unico'] Se true, retorna array unidimensional ao invés de bidimensional (opcional).
 * 
 * @return array|null Array com os resultados ou NULL se não houver dados.
 */
function banco_select($params = false){
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($campos) && isset($tabela)){
		// ===== Montar os campos com separação em vírgula.
		$camposVirgulas = ($campos == '*' ? $campos : banco_campos_virgulas($campos));
		
		// ===== Montar SQL com ou sem condições extras
		if(isset($extra)){
			$sql = "SELECT " . $camposVirgulas . " FROM " . $tabela . " " . $extra;
		} else {
			$sql = "SELECT " . $camposVirgulas . " FROM " . $tabela;
		}
		
		// ===== Executar a query no banco de dados
		$res = banco_query($sql);
		
		// ===== Processar resultados se existirem
		if(banco_num_rows($res)){
			// ===== Obter lista com todos os nomes dos campos
			if($camposVirgulas != '*'){
				$campos_name = explode(',',$camposVirgulas);
			} else {
				$num_fields = banco_num_fields($res);
				
				for($i=0;$i<$num_fields;$i++){
					$campos_name[] = banco_field_name($res,$i);
				}
			}
			
			// ===== Processar e retornar todos os resultados
			$max = banco_num_rows($res);
			
			for($i=0;$i<$max;$i++){
				$rows_aux = banco_row_array($res);
				$count = 0;
				
				// Montar array associativo com nomes dos campos
				foreach($campos_name as $campo_name){
					$rows_out[$campo_name] = $rows_aux[$count];
					$count++;
				}
				
				// Retorna único registro se solicitado
				if(!isset($unico)){
					$rows[] = $rows_out;
				} else {
					return $rows_out;
				}
			}
			
			return $rows;
		}
	}
	
	return NULL;
}

/**
 * Seleciona dados do banco retornando arrays associativos com stripslashes.
 *
 * Função legada para seleção de dados. Similar a banco_select mas
 * aplica banco_smartstripslashes aos valores não-numéricos.
 *
 * @param string $campos Lista de campos separados por vírgula ou '*'.
 * @param string $tabela Nome da tabela.
 * @param string $extra Condições extras da query (WHERE, ORDER BY, etc.).
 * 
 * @return array|null Array com os resultados ou NULL se não houver dados.
 */
function banco_select_name($campos,$tabela,$extra){
	// Monta a query SQL
	if($extra)
		$sql = "SELECT " . $campos . " FROM " . $tabela . " " . $extra;
	else
		$sql = "SELECT " . $campos . " FROM " . $tabela;
		
	$res = banco_query($sql);
	
	// Processa resultados se existirem
	if(banco_num_rows($res)){
		// Determina nomes dos campos
		if($campos != '*'){
			$campos_name = explode(',',$campos);
		} else {
			$num_fields = banco_num_fields($res);
			
			for($i=0;$i<$num_fields;$i++){
				$campos_name[] = banco_field_name($res,$i);
			}
		}
		
		// Processa cada linha aplicando stripslashes
		$max = banco_num_rows($res);
		
		for($i=0;$i<$max;$i++){
			$rows_aux = banco_row_array($res);
			$count=0;
			
			foreach($campos_name as $campo_name){
				// Aplica stripslashes apenas para valores não-numéricos
				$rows_out[$campo_name] = ( !is_numeric($rows_aux[$count]) ? banco_smartstripslashes($rows_aux[$count]) : $rows_aux[$count]);
				$count++;
			}
			$rows[] = $rows_out;
		}
		
		return $rows;
	}
	else
		return NULL;
}

/**
 * Seleciona um único registro para edição.
 *
 * Similar a banco_select_name mas retorna apenas o primeiro registro
 * e define uma flag global indicando se houve resultado.
 *
 * @global array $_GESTOR Sistema global onde $_GESTOR['banco-resultado'] é definido.
 * 
 * @param string $campos Lista de campos separados por vírgula ou '*'.
 * @param string $tabela Nome da tabela.
 * @param string $extra Condições extras da query (WHERE, ORDER BY, etc.).
 * 
 * @return array|null Array associativo com o registro ou NULL se não houver dados.
 */
function banco_select_editar($campos,$tabela,$extra){
	global $_GESTOR;
	
	// Monta a query SQL
	if($extra)
		$sql = "SELECT " . $campos . " FROM " . $tabela . " " . $extra;
	else
		$sql = "SELECT " . $campos . " FROM " . $tabela;
	
	$res = banco_query($sql);
	
	// Processa apenas o primeiro registro
	if(banco_num_rows($res)){
		// Determina nomes dos campos
		if($campos != '*'){
			$campos_name = explode(',',$campos);
		} else {
			$num_fields = banco_num_fields($res);
			
			for($i=0;$i<$num_fields;$i++){
				$campos_name[] = banco_field_name($res,$i);
			}
		}
		
		// Processa primeira linha
		$rows_aux = banco_row_array($res);
		$count=0;
		
		foreach($campos_name as $campo_name){
			if(isset($rows_aux[$count])){
				$rows_out[$campo_name] = (!is_numeric($rows_aux[$count]) ? banco_smartstripslashes($rows_aux[$count]) : $rows_aux[$count]);
			}
			
			$count++;
		}
		
		$rows_out = (isset($rows_out) ? $rows_out : null);
		
		// Define flag de sucesso
		$_GESTOR['banco-resultado'] = true;
		
		return $rows_out;
	} else {
		// Define flag de falha
		$_GESTOR['banco-resultado'] = false;
		
		return NULL;
	}
}

/**
 * Seleciona e armazena campos anteriores para comparação.
 *
 * Função utilitária para guardar estado anterior de registros antes de
 * atualizações. Armazena os valores em $_GESTOR['banco-antes'] para
 * posterior comparação ou auditoria.
 *
 * @global array $_GESTOR Sistema global onde os dados anteriores são armazenados.
 * 
 * @param string $campos Lista de campos separados por vírgula ou '*'.
 * @param string $tabela Nome da tabela.
 * @param string $extra Condições extras da query (WHERE, etc.).
 * 
 * @return bool True se encontrou e armazenou dados, false caso contrário.
 */
function banco_select_campos_antes_iniciar($campos,$tabela,$extra){
	global $_GESTOR;
	
	// Monta query SQL
	if($extra)
		$sql = "SELECT " . $campos . " FROM " . $tabela . " " . $extra;
	else
		$sql = "SELECT " . $campos . " FROM " . $tabela;
	
	$res = banco_query($sql);
	
	// Processa resultado se houver
	if(banco_num_rows($res)){
		// Determina nomes dos campos
		if($campos != '*'){
			$campos_name = explode(',',$campos);
		} else {
			$num_fields = banco_num_fields($res);
			
			for($i=0;$i<$num_fields;$i++){
				$campos_name[] = banco_field_name($res,$i);
			}
		}
		
		// Processa primeira linha
		$rows_aux = banco_row_array($res);
		$count=0;
		
		// Monta array associativo
		foreach($campos_name as $campo_name){
			if(isset($rows_aux[$count])){
				$rows_out[$campo_name] = (!is_numeric($rows_aux[$count]) ? banco_smartstripslashes($rows_aux[$count]) : $rows_aux[$count]);
			}
			
			$count++;
		}
		
		$rows_out = (isset($rows_out) ? $rows_out : null);
		
		// Armazena em global para acesso posterior
		$_GESTOR['banco-antes'] = $rows_out;
		
		return true;
	} else {
		return false;
	}
}

/**
 * Retorna valor anterior de um campo específico.
 *
 * Recupera valor de campo armazenado por banco_select_campos_antes_iniciar.
 * Útil para comparar valores antes e depois de atualizações.
 *
 * @global array $_GESTOR Sistema global onde os dados anteriores estão armazenados.
 * 
 * @param string $campo Nome do campo a recuperar.
 * 
 * @return mixed|null Valor do campo ou NULL se não encontrado.
 */
function banco_select_campos_antes($campo){
	global $_GESTOR;
	
	// Verifica se há dados anteriores armazenados
	if(isset($_GESTOR['banco-antes'])){
		if(isset($_GESTOR['banco-antes'][$campo])){
			return $_GESTOR['banco-antes'][$campo];
		} else {
			return NULL;
		}
	} else {
		return NULL;
	}
}

/**
 * Executa query UPDATE no banco de dados.
 *
 * Função simples para executar atualizações SQL.
 *
 * @param string $campos Lista de campos e valores no formato "campo='valor'".
 * @param string $tabela Nome da tabela.
 * @param string $extra Condições extras (WHERE, etc.).
 * 
 * @return void
 */
function banco_update($campos,$tabela,$extra){
	// Monta SQL UPDATE
	if($extra)
		$sql = "UPDATE " . $tabela . " SET " . $campos . " " . $extra;
	else
		$sql = "UPDATE " . $tabela . " SET " . $campos;
	
	// Executa a query
	$res = banco_query($sql);
}

/**
 * Adiciona campo para atualização em lote.
 *
 * Acumula campos para posterior execução em batch via banco_update_executar.
 * Permite construir updates complexos de forma incremental.
 *
 * @global array $_BANCO Armazena campos acumulados em $_BANCO['update-campos'].
 * 
 * @param string $nome Nome do campo.
 * @param string $valor Valor a ser atribuído.
 * @param bool $sem_aspas_simples Se true, não envolve valor em aspas (padrão: false).
 * @param bool $escape_field Se true, escapa o valor (padrão: true).
 * 
 * @return void
 */
function banco_update_campo($nome,$valor,$sem_aspas_simples = false,$escape_field = true){
	global $_BANCO;
	
	// ===== Inicializa array se não existir
	if(!isset($_BANCO['update-campos'])){
		$_BANCO['update-campos'] = Array();
	}
	
	// ===== Escapa valor se solicitado
	if($escape_field){
		$valor = banco_escape_field($valor);
	}
	
	// ===== Adiciona campo ao array de updates
	$_BANCO['update-campos'][] = $nome.($sem_aspas_simples ? "=" . $valor : "='" . $valor . "'");
}

/**
 * Executa update em lote com campos acumulados.
 *
 * Executa UPDATE usando campos adicionados via banco_update_campo.
 * Limpa o array de campos após execução.
 *
 * @global array $_BANCO Contém campos acumulados em $_BANCO['update-campos'].
 * 
 * @param string $tabela Nome da tabela.
 * @param string $extra Condições extras como WHERE (padrão: '').
 * 
 * @return void
 */
function banco_update_executar($tabela,$extra = ''){
	global $_BANCO;
	
	// ===== Recupera campos acumulados
	if(isset($_BANCO['update-campos'])){
		$campos = $_BANCO['update-campos'];
		// Limpa array após pegar
		unset($_BANCO['update-campos']);
	} else {
		$campos = Array();
	}
	
	// ===== Monta SQL e executa atualização
	$editar_sql = banco_campos_virgulas($campos);
	
	if($editar_sql){
		banco_update
		(
			$editar_sql,
			$tabela,
			$extra
		);
	}
}

/**
 * Atualiza múltiplos registros em massa usando CASE.
 *
 * Executa UPDATE em batch para múltiplos registros de forma eficiente
 * usando cláusula CASE. Divide automaticamente em múltiplas queries se
 * SQL ficar muito grande (>1MB).
 *
 * @param array $campos Array de arrays [id, valor] para atualizar.
 * @param string $tabela Nome da tabela.
 * @param string $campo_nome Nome do campo a ser atualizado.
 * @param string $id_nome Nome do campo ID usado na cláusula CASE.
 * 
 * @return void
 */
function banco_update_varios($campos,$tabela,$campo_nome,$id_nome){
	if($campos){
		// ===== Inicia SQL com cláusula CASE
		$sql = "UPDATE `".$tabela."` SET `".$campo_nome."` = CASE `".$id_nome."`\n";
		$sql_fechar .= "ELSE `".$campo_nome."`\n";
		$sql_fechar .= "END";
		
		// ===== Adiciona cada WHEN/THEN
		foreach($campos as $campo){
			$sql .= "WHEN '".$campo[0]."' THEN '".$campo[1]."'\n";
			$flag = false;
			
			// Se SQL ficou muito grande (>1MB), executa e reinicia
			if(strlen($sql)+strlen($sql_fechar) > 1000000){
				$flag = true;
				$res = banco_query($sql.$sql_fechar);
				$sql = "UPDATE `".$tabela."` SET `".$campo_nome."` = CASE `".$id_nome."`\n";
			}
		}
		
		// Executa SQL final se não foi executado no loop
		if(!$flag)
			$res = banco_query($sql.$sql_fechar);
	}
}

/**
 * Insere registro com ID auto-incrementado.
 *
 * Função legada para INSERT simples. Adiciona '0' como primeiro valor
 * para ID auto-increment.
 *
 * @param string $campos Valores separados por vírgula.
 * @param string $tabela Nome da tabela.
 * 
 * @return void
 */
function banco_insert($campos,$tabela){
	$sql = "INSERT INTO " . $tabela . " VALUES('0'," . $campos . ")";
	$res = banco_query($sql);
}

/**
 * Insere registro com nomes de campos especificados.
 *
 * Insere dados usando array de [nome, valor, sem_aspas] para cada campo.
 * Permite controle preciso sobre quais campos inserir.
 *
 * @param array $dados Array de arrays [nome, valor, sem_aspas_simples].
 * @param string $tabela Nome da tabela.
 * 
 * @return void
 */
function banco_insert_name($dados,$tabela){
	$nomes = '';
	$campos = '';

	// ===== Monta strings de nomes e valores
	foreach($dados as $dado){
		if(isset($dado[1])){
			// Define padrão para sem_aspas se não especificado
			if(!isset($dado[2])){
				$dado[2] = false;
			}
			
			// Adiciona nome do campo
			$nomes .= (strlen($nomes) > 0 ? ',' : '') . $dado[0];
			// Adiciona valor com ou sem aspas
			$campos .= (strlen($campos) > 0 ? ',' : '') . ( $dado[2] ? $dado[1] : "'" . $dado[1] . "'" );
		}
	}
	
	// Executa INSERT
	$sql = "INSERT INTO " . $tabela . " (" . $nomes . ") VALUES (" . $campos . ")";
	$res = banco_query($sql);
}

/**
 * Adiciona campo para inserção em lote.
 *
 * Acumula campos para posterior execução via banco_insert_name.
 * Permite construir inserts complexos incrementalmente.
 *
 * @global array $_BANCO Armazena campos em $_BANCO['insert-name-campos'].
 * 
 * @param string $nome Nome do campo.
 * @param string $valor Valor do campo.
 * @param bool $sem_aspas_simples Se true, não envolve em aspas (padrão: false).
 * @param bool $escape_field Se true, escapa o valor (padrão: true).
 * 
 * @return void
 */
function banco_insert_name_campo($nome,$valor,$sem_aspas_simples = false,$escape_field = true){
	global $_BANCO;
	
	// ===== Inicializa array se não existir
	if(!isset($_BANCO['insert-name-campos'])){
		$_BANCO['insert-name-campos'] = Array();
	}
	
	// ===== Escapa valor se solicitado
	if($escape_field){
		$valor = banco_escape_field($valor);
	}
	
	// ===== Adiciona ao array de campos
	$_BANCO['insert-name-campos'][] = Array($nome,$valor,$sem_aspas_simples);
}

/**
 * Retorna e limpa campos acumulados para inserção.
 *
 * Recupera campos adicionados via banco_insert_name_campo e limpa o array.
 * Usado em conjunto com banco_insert_name.
 *
 * @global array $_BANCO Contém campos em $_BANCO['insert-name-campos'].
 * 
 * @return array Array de campos ou array vazio.
 */
function banco_insert_name_campos(){
	global $_BANCO;
	
	if(isset($_BANCO['insert-name-campos'])){
		$campos = $_BANCO['insert-name-campos'];
		unset($_BANCO['insert-name-campos']);
		return $campos;
	} else {
		return Array();
	}
}

/**
 * Insere múltiplos registros de uma única vez.
 *
 * Executa INSERT em massa com múltiplos VALUES em uma única query.
 * Otimizado para inserção de grande volume de dados com mesma estrutura.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['tabela'] Nome da tabela (obrigatório).
 * @param array $params['campos'] Configuração dos campos (obrigatório).
 * @param string $params['campos'][]['nome'] Nome do campo (obrigatório).
 * @param array $params['campos'][]['valores'] Array de valores para este campo (obrigatório).
 * @param bool $params['campos'][]['sem_aspas_simples'] Se true, não usa aspas (opcional).
 * 
 * @return void
 */
function banco_insert_name_varios($params = false){
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($campos) && isset($tabela)){
		$coluna = 0;
		
		// ===== Processa cada campo
		foreach($campos as $campo){
			
			// ===== Monta lista de nomes de campos
			if(!isset($nomes)){
				$nomes = '';
			}
			
			$nomes .= (existe($nomes) ? ',' : '') . $campo['nome'];
			
			// ===== Verifica se campo usa aspas
			if(isset($campo['sem_aspas_simples'])){ $sem_aspas_simples = true; } else { $sem_aspas_simples = false; }
			
			// ===== Monta conjunto de valores para este campo
			if(isset($campo['valores'])){
				$linha = 0;
				foreach($campo['valores'] as $valor){
					// Inicializa linha se não existir
					if(!isset($camposProcessados[$linha])){
						$camposProcessados[$linha] = '';
					}
					
					// Adiciona valor à linha com formatação apropriada
					$camposProcessados[$linha] .= ($coluna > 0 ? ',' : '(')
						.( isset($valor) ? ($sem_aspas_simples ? '' : "'") . $valor . ($sem_aspas_simples ? '' : "'") : 'NULL' )
						.($coluna < count($campos) - 1 ? '' : ')');
					$linha++;
				}
			}
			
			$coluna++;
		}
		
		// ===== Monta string final com todos os VALUES
		if(isset($camposProcessados))
		foreach($camposProcessados as $campo){
			if(!isset($conjunto_campos)){
				$conjunto_campos = '';
			}
			
			$conjunto_campos .= (existe($conjunto_campos) ? ",\n" : '') . $campo;
		}
		
		// ===== Executa INSERT se há dados
		if(isset($conjunto_campos)){
			$sql = "INSERT INTO " . $tabela . " (" . $nomes . ") VALUES \n" . $conjunto_campos;
			$res = banco_query($sql);
		}
	}
	
}

/**
 * Insere vários registros com ID auto-increment.
 *
 * Versão em lote de banco_insert para múltiplos registros.
 *
 * @param array $campos Array de strings com valores para cada registro.
 * @param string $tabela Nome da tabela.
 * 
 * @return void
 */
function banco_insert_varios($campos,$tabela){
	$total = count($campos);
	$ultimo = $total - 1;
	
	// Monta VALUES separados por vírgula
	for($i=0;$i<$total;$i++){
		if($i < $ultimo)
			$conjunto_campos .= "('0'," . $campos[$i] . "),";
		else
			$conjunto_campos .= "('0'," . $campos[$i] . ")";
	}
	
	// Executa INSERT em massa
	$sql = "INSERT INTO " . $tabela . " VALUES " . $conjunto_campos;
	$res = banco_query($sql);
}

/**
 * Insere vários registros com todos os valores especificados.
 *
 * Similar a banco_insert_varios mas sem adicionar '0' para ID.
 *
 * @param array $campos Array de strings com valores para cada registro.
 * @param string $tabela Nome da tabela.
 * 
 * @return void
 */
function banco_insert_varios_tudo($campos,$tabela){
	$total = count($campos);
	$ultimo = $total - 1;
	
	// Monta VALUES separados por vírgula
	for($i=0;$i<$total;$i++){
		if($i < $ultimo)
			$conjunto_campos .= "(" . $campos[$i] . "),";
		else
			$conjunto_campos .= "(" . $campos[$i] . ")";
	}
	
	// Executa INSERT em massa
	$sql = "INSERT INTO " . $tabela . " VALUES " . $conjunto_campos;
	$res = banco_query($sql);
}

/**
 * Insere registro com ID especificado.
 *
 * Função legada similar a banco_insert_tudo.
 *
 * @param string $campos Valores separados por vírgula.
 * @param string $tabela Nome da tabela.
 * 
 * @return void
 */
function banco_insert_id($campos,$tabela){
	$sql = "INSERT INTO " . $tabela . " VALUES(" . $campos . ")";
	$res = banco_query($sql);
}

/**
 * Insere registro com todos os valores especificados.
 *
 * INSERT simples sem adicionar ID automático.
 *
 * @param string $campos Valores separados por vírgula.
 * @param string $tabela Nome da tabela.
 * 
 * @return void
 */
function banco_insert_tudo($campos,$tabela){
	$sql = "INSERT INTO " . $tabela . " VALUES(" . $campos . ")";
	$res = banco_query($sql);
}

/**
 * Retorna o último ID inserido.
 *
 * Obtém o ID auto-increment gerado pela última inserção.
 *
 * @global array $_BANCO Conexão do banco.
 * 
 * @return int|null O último ID inserido ou NULL.
 */
function banco_last_id(){
	global $_BANCO;

	if($_BANCO['tipo'] == "mysqli")
		return mysqli_insert_id($_BANCO['conexao']);
}

/**
 * Executa DELETE no banco de dados.
 *
 * @param string $tabela Nome da tabela.
 * @param string $extra Condições WHERE e outras cláusulas.
 * 
 * @return void
 */
function banco_delete($tabela,$extra){
	$sql = "DELETE FROM " . $tabela . " " . $extra;
	$res = banco_query($sql);
}

/**
 * Deleta múltiplos registros usando IN.
 *
 * Executa DELETE em massa usando cláusula IN com múltiplos IDs.
 * Suporta múltiplos campos de ID combinados com AND.
 *
 * @param string $tabela Nome da tabela.
 * @param array|string $campo_ids Nome(s) do(s) campo(s) ID.
 * @param array $array_ids Array de IDs para deletar.
 * 
 * @return void
 */
function banco_delete_varios($tabela,$campo_ids,$array_ids){
	// Se múltiplos campos ID
	if(count($campo_ids)>1){
		if($campo_ids)
		foreach($campo_ids as $campo){
			$ids_str = '';
			$count = 0;
			if($array_ids[$campo])
			foreach($array_ids[$campo] as $ids){
				$count++;
				$ids_str .= $ids . (count($array_ids[$campo]) > $count? ',':'');
			}
			
			$count2++;
			$extra .= $campo . " IN (".$ids_str.")" . (count($campo_ids) > $count2? ' AND ':'');
		}
		$sql = "DELETE FROM " . $tabela . " WHERE " . $extra;
		$res = banco_query($sql);
	} else {
		if($array_ids)
		foreach($array_ids as $ids){
			$count++;
			$ids_str .= $ids . (count($array_ids) > $count? ',':'');
		}
		
		$sql = "DELETE FROM " . $tabela . " WHERE " . $campo_ids . " IN (".$ids_str.")";
		$res = banco_query($sql);
	}
}

/**
 * Converte array de campos em string separada por vírgulas.
 *
 * Função utilitária para juntar campos de array em string SQL.
 *
 * @param array $campos Array de nomes de campos.
 * 
 * @return string Campos separados por vírgulas.
 */
function banco_campos_virgulas($campos){
	$count = 0;
	$string = '';
	
	// Concatena cada campo com vírgula
	if($campos)
	foreach($campos as $campo){
		$count++;
		$string .= $campo;
		if($count < count($campos)){
			$string .= ',';
		}
		
	}
	
	return $string;
}

/**
 * Retorna total de linhas em uma tabela.
 *
 * Executa COUNT(*) para contar registros.
 *
 * @param string $tabela Nome da tabela.
 * @param string|null $extra Condições WHERE opcionais (padrão: null).
 * 
 * @return int Número total de registros.
 */
function banco_total_rows($tabela,$extra = null){
	// Monta query com ou sem WHERE
	if(isset($extra)){
		$sql = "SELECT count(*) as total_record FROM " . $tabela . " " . $extra;
	} else {
		$sql = "SELECT count(*) as total_record FROM " . $tabela;
	}
	
	$resultado = banco_sql($sql);
	
	return $resultado[0][0];
}

/**
 * Retorna informações sobre colunas de uma tabela.
 *
 * Executa SHOW COLUMNS e retorna metadados das colunas.
 *
 * @param string $tabela Nome da tabela.
 * 
 * @return array Array com informações de cada coluna.
 */
function banco_campos_nomes($tabela){
	$rows = banco_sql("SHOW COLUMNS FROM ".$tabela);

	// Processa resultados removendo chaves numéricas
	if($rows){
		foreach($rows as $row){
			$campos_params = false;
			foreach($row as $chave => $valor){
				// Mantém apenas chaves não-numéricas
				if(!is_numeric($chave)){
					$campos_params[$chave] = $valor;
				}
			}
			$campos[] = $campos_params;
		}
	}
	
	return $campos;
}

/**
 * Verifica se um campo específico existe em uma tabela.
 *
 * Consulta a estrutura da tabela usando SHOW COLUMNS e verifica se o campo
 * especificado existe entre os campos retornados. Retorna true se o campo
 * existir, false caso contrário.
 *
 * @param string $campo Nome do campo a ser verificado.
 * @param string $tabela Nome da tabela onde verificar o campo.
 *
 * @return bool True se o campo existir na tabela, false caso contrário.
 */
function banco_campo_existe($campo, $tabela){
	// Obtém todos os campos da tabela
	$campos = banco_campos_nomes($tabela);

	// Verifica se encontrou campos na tabela
	if($campos && is_array($campos)){
		// Percorre os campos procurando pelo campo especificado
		foreach($campos as $campo_info){
			if(isset($campo_info['Field']) && $campo_info['Field'] === $campo){
				return true;
			}
		}
	}

	return false;
}

/**
 * Remove acentos e caracteres especiais de uma string.
 *
 * Normaliza strings para uso em URLs, IDs, etc. Converte para minúsculas,
 * remove acentos, caracteres especiais e opcionalmente espaços.
 *
 * @param string $var String a ser processada.
 * @param bool $retirar_espaco Se true, substitui espaços por hífens (padrão: true).
 * 
 * @return string String normalizada.
 */
function banco_retirar_acentos($var,$retirar_espaco = true) {
	// Converte para minúsculas
	$var = strtolower($var);
	
	// Mapa de caracteres acentuados para ASCII
	$unwanted_array = array(    
		'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ª'=>'a', 'ç'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'º'=>'o',
		'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
	);
	// Substitui acentos por ASCII
	$var = strtr( $var, $unwanted_array );
	
	// Remove caracteres especiais e normaliza hífens
	$var = preg_replace("/[\.\\\\,:;<>\/:\?\|_!`~@#\$%\^&\*\"'\+=]/","",$var);
	$var = preg_replace("/[\(\)\{\}\[\]]/","-",$var);
	// Substitui espaços por hífens se solicitado
	if($retirar_espaco)$var = str_replace(" ","-",$var);
	// Normaliza múltiplos hífens em um único
	$var = preg_replace('/\-+/','-', $var);
	// Remove caracteres que não sejam letras, números ou hífens
	$var = preg_replace("/[^a-z^A-Z^0-9^-]/","",$var);
	// Remove hífens duplicados
	$var = preg_replace("/\-{2,}/","-",$var);
	
	return $var;
}

/**
 * Gera identificador único recursivamente.
 *
 * Função recursiva auxiliar para banco_identificador que verifica
 * unicidade e adiciona sufixo numérico se necessário.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] ID base.
 * @param int $params['num'] Número do sufixo atual.
 * @param array $params['tabela'] Configuração da tabela.
 * @param bool $params['sem_traco'] Se true, remove hífens do resultado.
 * 
 * @return string ID único gerado.
 */
function banco_identificador_unico($params = false){
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Verifica se ID já existe no banco
	if($tabela){
		$resultado = banco_select(Array(
			'tabela' => $tabela['nome'],
			'campos' => Array(
				$tabela['id_nome'],
			),
			'extra' => 
				"WHERE ".$tabela['campo']."='".($num > 0 ? $id.'-'.$num : $id)."'"
				.(isset($tabela['id_valor']) ? " AND ".$tabela['id_nome']."!='".$tabela['id_valor']."'":"")
				.(isset($tabela['sem_status']) ? '' : " AND ".(isset($tabela['status']) ? $tabela['status'] : "status" )."!='D'")
				.(isset($tabela['where']) ? " AND (".$tabela['where'].")" : '')
		));
	}
	
	// Se ID já existe, tenta próximo número recursivamente
	if($resultado){
		return banco_identificador_unico(Array(
			'id' => $id,
			'num' => $num + 1,
			'tabela' => $tabela,
			'sem_traco' => (isset($sem_traco) ? $sem_traco : null ),
		));
	} else {
		// Retorna ID único, com ou sem hífens
		return ( isset($sem_traco) ? str_replace("-","",($num > 0 ? $id.'-'.$num : $id)) : ($num > 0 ? $id.'-'.$num : $id) );
	}
}

/**
 * Gera identificador único a partir de string.
 *
 * Cria ID único normalizado, verificando existência no banco e
 * adicionando sufixo numérico se necessário. Limita tamanho a 90 caracteres.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] String base para gerar ID.
 * @param array $params['tabela'] Configuração da tabela.
 * @param bool $params['sem_traco'] Se true, remove hífens do resultado.
 * 
 * @return string ID único gerado e validado.
 */
function banco_identificador($params = false){
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$count = 0;
	$pre_id = '';
	$tam_max_id = 90;
	
	// Normaliza ID removendo acentos
	$id = banco_retirar_acentos(trim($id));
	
	// Limita tamanho do ID a 90 caracteres
	$pre_id_aux = explode('-',$id);
	
	if($pre_id_aux)
	foreach($pre_id_aux as $pre){
		$count++;
		if($pre){
			$pre_id .= $pre;
			
			if(strlen($pre_id) > $tam_max_id){
				break;
			} else {
				$pre_id .= (count($pre_id_aux) > $count ? '-' : '');
			}
		}
	}
	
	$id = $pre_id;
	
	// Verifica se ID já termina com número (sufixo)
	$id_aux = explode('-',$id);
	$count = 0;
	if(count($id_aux) > 1 && is_numeric($id_aux[count($id_aux)-1])){
		// ID já tem sufixo numérico, separa base do número
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		// Verifica unicidade começando do número existente
		return banco_identificador_unico(Array(
			'id' => $id,
			'num' => $num,
			'tabela' => $tabela,
			'sem_traco' => (isset($sem_traco) ? $sem_traco : null ),
		));
	} else {
		// ID não tem sufixo, verifica unicidade começando do zero
		return banco_identificador_unico(Array(
			'id' => $id,
			'num' => 0,
			'tabela' => $tabela,
			'sem_traco' => (isset($sem_traco) ? $sem_traco : null ),
		));
	}
}

/**
 * Insere ou atualiza registro baseado em existência.
 *
 * Verifica se registro existe baseado em ID. Se existe, atualiza;
 * se não existe, insere. Suporta tipagem de dados (bool, int, string).
 *
 * @param array|false $params Parâmetros da função.
 * @param array $params['tabela'] Configuração da tabela (obrigatório).
 * @param string $params['tabela']['nome'] Nome da tabela (obrigatório).
 * @param string $params['tabela']['id'] Nome do campo ID (obrigatório).
 * @param string $params['tabela']['extra'] Condições extras para UPDATE (opcional).
 * @param array $params['dados'] Dados a inserir/atualizar (obrigatório).
 * @param array $params['dadosTipo'] Tipos dos campos (opcional).
 * 
 * @return void
 */
function banco_insert_update($params = false){
	global $_PADRAO;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($tabela) && isset($dados)){
		// ===== Busca registro existente pelo ID
		$id_ref = banco_escape_field($dados[$tabela['id']]);
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				$tabela['id'],
			))
			,
			$tabela['nome'],
			"WHERE ".$tabela['id']."='".$id_ref."'"
		);
		
		// ===== Se registro existe, atualiza; senão, insere
		if($resultado){
			// Registro existe: UPDATE
			unset($dados[$tabela['id']]);
			
			$campo_tabela = $tabela['nome'];
			$campo_tabela_extra = "WHERE ".$tabela['id']."='".$id_ref."'" .(isset($tabela['extra']) ? ' '.$tabela['extra'] : '');
			
			// Monta campos para UPDATE baseado no tipo
			foreach($dados as $chave => $dado){
				if(isset($dadosTipo)){
					switch($dadosTipo[$chave]){
						case 'bool':
							$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
						break;
						case 'int':
							$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
						break;
						default:
							$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
					}
				} else {
					$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
				}
				
			}
			
			// Executa UPDATE
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
		} else {
			// Registro não existe: INSERT
			$campos = null; $campo_sem_aspas_simples = null;
			
			// Monta campos para INSERT baseado no tipo
			foreach($dados as $chave => $dado){
				if(isset($dadosTipo)){
					switch($dadosTipo[$chave]){
						case 'bool':
							$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
						break;
						case 'int':
							$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
						break;
						default:
							$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					}
				} else {
					$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				}
			}
			
			// Executa INSERT
			banco_insert_name
			(
				$campos,
				$tabela['nome']
			);
		}
	}
}

/**
 * Retorna lista de todas as tabelas do banco de dados.
 *
 * Executa SHOW TABLES e retorna array com nomes de todas as tabelas.
 *
 * @global array $_BANCO Conexão do banco.
 * 
 * @return array Array com nomes das tabelas.
 */
function banco_tabelas_lista(){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli"){
		$tabelaLista = Array();
		
		// Executa SHOW TABLES
		$res = banco_query("SHOW TABLES");
		
		// Processa cada tabela
		while($cRow = mysqli_fetch_array($res)){
			$tabelaLista[] = $cRow[0];
		}
		
		return $tabelaLista;
	}
}

?>