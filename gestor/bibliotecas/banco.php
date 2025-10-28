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

function banco_select_campos_antes_iniciar($campos,$tabela,$extra){
	global $_GESTOR;
	
	if($extra)
		$sql = "SELECT " . $campos . " FROM " . $tabela . " " . $extra;
	else
		$sql = "SELECT " . $campos . " FROM " . $tabela;
	
	$res = banco_query($sql);
	
	if(banco_num_rows($res)){
		if($campos != '*'){
			$campos_name = explode(',',$campos);
		} else {
			$num_fields = banco_num_fields($res);
			
			for($i=0;$i<$num_fields;$i++){
				$campos_name[] = banco_field_name($res,$i);
			}
		}
		
		$rows_aux = banco_row_array($res);
		$count=0;
		
		foreach($campos_name as $campo_name){
			if(isset($rows_aux[$count])){
				$rows_out[$campo_name] = (!is_numeric($rows_aux[$count]) ? banco_smartstripslashes($rows_aux[$count]) : $rows_aux[$count]);
			}
			
			$count++;
		}
		
		$rows_out = (isset($rows_out) ? $rows_out : null);
		
		$_GESTOR['banco-antes'] = $rows_out;
		
		return true;
	} else {
		return false;
	}
}

function banco_select_campos_antes($campo){
	global $_GESTOR;
	
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

function banco_update($campos,$tabela,$extra){
	if($extra)
		$sql = "UPDATE " . $tabela . " SET " . $campos . " " . $extra;
	else
		$sql = "UPDATE " . $tabela . " SET " . $campos;
	
	$res = banco_query($sql);
}

function banco_update_campo($nome,$valor,$sem_aspas_simples = false,$escape_field = true){
	global $_BANCO;
	
	// ===== Criar campos caso não exista.
	
	if(!isset($_BANCO['update-campos'])){
		$_BANCO['update-campos'] = Array();
	}
	
	// ===== Escapar campo.
	
	if($escape_field){
		$valor = banco_escape_field($valor);
	}
	
	// ===== Adicionar campo ao array.
	
	$_BANCO['update-campos'][] = $nome.($sem_aspas_simples ? "=" . $valor : "='" . $valor . "'");
}

function banco_update_executar($tabela,$extra = ''){
	global $_BANCO;
	
	// ===== Pegar os campos.
	
	if(isset($_BANCO['update-campos'])){
		$campos = $_BANCO['update-campos'];
		unset($_BANCO['update-campos']);
	} else {
		$campos = Array();
	}
	
	// ===== Executar a atualização.
	
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

function banco_update_varios($campos,$tabela,$campo_nome,$id_nome){
	if($campos){
		$sql = "UPDATE `".$tabela."` SET `".$campo_nome."` = CASE `".$id_nome."`\n";
		$sql_fechar .= "ELSE `".$campo_nome."`\n";
		$sql_fechar .= "END";
		
		foreach($campos as $campo){
			$sql .= "WHEN '".$campo[0]."' THEN '".$campo[1]."'\n";
			$flag = false;
			
			if(strlen($sql)+strlen($sql_fechar) > 1000000){
				$flag = true;
				$res = banco_query($sql.$sql_fechar);
				$sql = "UPDATE `".$tabela."` SET `".$campo_nome."` = CASE `".$id_nome."`\n";
			}
		}
		
		if(!$flag)
			$res = banco_query($sql.$sql_fechar);
	}
}

function banco_insert($campos,$tabela){
	$sql = "INSERT INTO " . $tabela . " VALUES('0'," . $campos . ")";
	$res = banco_query($sql);
}

function banco_insert_name($dados,$tabela){
	$nomes = '';
	$campos = '';

	foreach($dados as $dado){
		if(isset($dado[1])){
			if(!isset($dado[2])){
				$dado[2] = false;
			}
			
			$nomes .= (strlen($nomes) > 0 ? ',' : '') . $dado[0];
			$campos .= (strlen($campos) > 0 ? ',' : '') . ( $dado[2] ? $dado[1] : "'" . $dado[1] . "'" );
		}
	}
	
	$sql = "INSERT INTO " . $tabela . " (" . $nomes . ") VALUES (" . $campos . ")";
	$res = banco_query($sql);
}

function banco_insert_name_campo($nome,$valor,$sem_aspas_simples = false,$escape_field = true){
	global $_BANCO;
	
	// ===== Criar campos caso não exista.
	
	if(!isset($_BANCO['insert-name-campos'])){
		$_BANCO['insert-name-campos'] = Array();
	}
	
	// ===== Escapar campo.
	
	if($escape_field){
		$valor = banco_escape_field($valor);
	}
	
	// ===== Adicionar campo ao array.
	
	$_BANCO['insert-name-campos'][] = Array($nome,$valor,$sem_aspas_simples);
}

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

function banco_insert_name_varios($params = false){
	/**********
		Descrição: Insere vários registros de uma única vez.
	**********/

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// tabela - String - Obrigatório - Nome da tabela do banco de dados.
	// campos - Array - Obrigatório - Conjunto com todos os campos, valores e opções.
		// nome - String - Obrigatório - Nome do campo.
		// valores - Array - Obrigatório - Conjunto com todos os valores.
		// sem_aspas_simples - String - Opcional - Se o campo não usa aspas simples.
	
	// ===== 
	
	if(isset($campos) && isset($tabela)){
		$coluna = 0;
		
		foreach($campos as $campo){
			
			
			// ===== Montar o campo nomes
			
			if(!isset($nomes)){
				$nomes = '';
			}
			
			$nomes .= (existe($nomes) ? ',' : '') . $campo['nome'];
			
			// ===== Se houver a opção 'sem_aspas_simples'
			
			if(isset($campo['sem_aspas_simples'])){ $sem_aspas_simples = true; } else { $sem_aspas_simples = false; }
			
			// ===== Montar o conjunto dos valores
			
			if(isset($campo['valores'])){
				$linha = 0;
				foreach($campo['valores'] as $valor){
					if(!isset($camposProcessados[$linha])){
						$camposProcessados[$linha] = '';
					}
					
					$camposProcessados[$linha] .= ($coluna > 0 ? ',' : '(')
						.( isset($valor) ? ($sem_aspas_simples ? '' : "'") . $valor . ($sem_aspas_simples ? '' : "'") : 'NULL' )
						.($coluna < count($campos) - 1 ? '' : ')');
					$linha++;
				}
			}
			
			$coluna++;
		}
		
		if(isset($camposProcessados))
		foreach($camposProcessados as $campo){
			if(!isset($conjunto_campos)){
				$conjunto_campos = '';
			}
			
			$conjunto_campos .= (existe($conjunto_campos) ? ",\n" : '') . $campo;
		}
		
		if(isset($conjunto_campos)){
			$sql = "INSERT INTO " . $tabela . " (" . $nomes . ") VALUES \n" . $conjunto_campos;
			$res = banco_query($sql);
		}
	}
	
}

function banco_insert_varios($campos,$tabela){
	$total = count($campos);
	$ultimo = $total - 1;
	
	for($i=0;$i<$total;$i++){
		if($i < $ultimo)
			$conjunto_campos .= "('0'," . $campos[$i] . "),";
		else
			$conjunto_campos .= "('0'," . $campos[$i] . ")";
	}
	
	$sql = "INSERT INTO " . $tabela . " VALUES " . $conjunto_campos;
	$res = banco_query($sql);
}

function banco_insert_varios_tudo($campos,$tabela){
	$total = count($campos);
	$ultimo = $total - 1;
	
	for($i=0;$i<$total;$i++){
		if($i < $ultimo)
			$conjunto_campos .= "(" . $campos[$i] . "),";
		else
			$conjunto_campos .= "(" . $campos[$i] . ")";
	}
	
	$sql = "INSERT INTO " . $tabela . " VALUES " . $conjunto_campos;
	$res = banco_query($sql);
}

function banco_insert_id($campos,$tabela){
	$sql = "INSERT INTO " . $tabela . " VALUES(" . $campos . ")";
	$res = banco_query($sql);
}

function banco_insert_tudo($campos,$tabela){
	$sql = "INSERT INTO " . $tabela . " VALUES(" . $campos . ")";
	$res = banco_query($sql);
}

function banco_last_id(){
	global $_BANCO;

	if($_BANCO['tipo'] == "mysqli")
		return mysqli_insert_id($_BANCO['conexao']);
}

function banco_delete($tabela,$extra){
	$sql = "DELETE FROM " . $tabela . " " . $extra;
	$res = banco_query($sql);
}

function banco_delete_varios($tabela,$campo_ids,$array_ids){
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

function banco_campos_virgulas($campos){
	$count = 0;
	$string = '';
	
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

function banco_total_rows($tabela,$extra = null){
	if(isset($extra)){
		$sql = "SELECT count(*) as total_record FROM " . $tabela . " " . $extra;
	} else {
		$sql = "SELECT count(*) as total_record FROM " . $tabela;
	}
	
	$resultado = banco_sql($sql);
	
	return $resultado[0][0];
}

function banco_campos_nomes($tabela){
	$rows = banco_sql("SHOW COLUMNS FROM ".$tabela);

	if($rows){
		foreach($rows as $row){
			$campos_params = false;
			foreach($row as $chave => $valor){
				if(!is_numeric($chave)){
					$campos_params[$chave] = $valor;
				}
			}
			$campos[] = $campos_params;
		}
	}
	
	return $campos;
}

function banco_retirar_acentos($var,$retirar_espaco = true) {
	$var = strtolower($var);
	
	$unwanted_array = array(    
		'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ª'=>'a', 'ç'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'º'=>'o',
		'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
	);
	$var = strtr( $var, $unwanted_array );
	
	$var = preg_replace("/[\.\\\\,:;<>\/:\?\|_!`~@#\$%\^&\*\"'\+=]/","",$var);
	$var = preg_replace("/[\(\)\{\}\[\]]/","-",$var);
	if($retirar_espaco)$var = str_replace(" ","-",$var);
	$var = preg_replace('/\-+/','-', $var);
	$var = preg_replace("/[^a-z^A-Z^0-9^-]/","",$var);
	$var = preg_replace("/\-{2,}/","-",$var);
	
	return $var;
}

function banco_identificador_unico($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
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
	
	if($resultado){
		return banco_identificador_unico(Array(
			'id' => $id,
			'num' => $num + 1,
			'tabela' => $tabela,
			'sem_traco' => (isset($sem_traco) ? $sem_traco : null ),
		));
	} else {
		return ( isset($sem_traco) ? str_replace("-","",($num > 0 ? $id.'-'.$num : $id)) : ($num > 0 ? $id.'-'.$num : $id) );
	}
}

function banco_identificador($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$count = 0;
	$pre_id = '';
	$tam_max_id = 90;
	$id = banco_retirar_acentos(trim($id));
	
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
	
	$id_aux = explode('-',$id);
	$count = 0;
	if(count($id_aux) > 1 && is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		return banco_identificador_unico(Array(
			'id' => $id,
			'num' => $num,
			'tabela' => $tabela,
			'sem_traco' => (isset($sem_traco) ? $sem_traco : null ),
		));
	} else {
		return banco_identificador_unico(Array(
			'id' => $id,
			'num' => 0,
			'tabela' => $tabela,
			'sem_traco' => (isset($sem_traco) ? $sem_traco : null ),
		));
	}
}

function banco_insert_update($params = false){
	/**********
		Descrição: inserir ou atualizar um registro.
	**********/
	
	global $_PADRAO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// dados - Array - Obrigatório - Dados a serem inseridos ou atualizados na tabela alvo.
	// tabela - Array - Obrigatório - Valores referenciais da tabela alvo.
		// nome - String - Obrigatório - Nome da tabela alvo.
		// id - String - Obrigatório - Identificador da tabela alvo.
		// extra - String - Opcional - Identificador da tabela alvo.
	// dadosTipo - Array - Opcional - Dados que tem tipo espcífico na tabela alvo.
	
	// ===== 
	
	if(isset($tabela) && isset($dados)){
		// ===== Busca no banco de dados o ID referido.
		
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
		
		// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
		
		if($resultado){
			unset($dados[$tabela['id']]);
			
			$campo_tabela = $tabela['nome'];
			$campo_tabela_extra = "WHERE ".$tabela['id']."='".$id_ref."'" .(isset($tabela['extra']) ? ' '.$tabela['extra'] : '');
			
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
			$campos = null; $campo_sem_aspas_simples = null;
			
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
			
			banco_insert_name
			(
				$campos,
				$tabela['nome']
			);
		}
	}
}

function banco_tabelas_lista(){
	global $_BANCO;
	
	if($_BANCO['tipo'] == "mysqli"){
		$tabelaLista = Array();
		
		$res = banco_query("SHOW TABLES");
		
		while($cRow = mysqli_fetch_array($res)){
			$tabelaLista[] = $cRow[0];
		}
		
		return $tabelaLista;
	}
}

?>