<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/

$_VERSAO_MODULO_INCLUDE				=	'1.3.1';

function banco_conectar(){
	global $_BANCO;
	global $_CONEXAO_BANCO;
	global $_MOBILE;
	
 	if($_BANCO['TYPE'] == "mysql"){
 		$_CONEXAO_BANCO = mysql_connect($_BANCO['HOST'],$_BANCO['USUARIO'],$_BANCO['SENHA']) or die(($_MOBILE?'<div data-role="page">'.'ERRO BANCO: Conexão com o banco de dados não realizada!'.'</div>' : "ERRO BANCO: Conexão com o banco de dados não realizada!"));
 		mysql_select_db($_BANCO['NOME']) or die(($_MOBILE?'<div data-role="page">'."ERRO BANCO: Impossível selecionar o banco de dados: ". mysql_error().'</div>' : "ERRO BANCO: Impossível selecionar o banco de dados: ". mysql_error()));
	}
}

function banco_ping(){
	global $_BANCO;
	global $_CONEXAO_BANCO;
 
 	if($_BANCO['TYPE'] == "mysql"){
		if(!mysql_ping($_CONEXAO_BANCO)){
			$_BANCO['RECONECT']++;
		}
	}
}

function banco_fechar_conexao(){
	global $_BANCO;
	global $_CONEXAO_BANCO;
	global $_MOBILE;
	
	if($_BANCO['TYPE'] == "mysql")
 		$_CONEXAO_BANCO = mysql_close($_CONEXAO_BANCO) or die(($_MOBILE?'<div data-role="page">'.'ERRO BANCO: Impossível fechar conexao com o banco de dados!'.'</div>' : "ERRO BANCO: Impossível fechar conexao com o banco de dados!"));
	
	$_CONEXAO_BANCO = false;
}

function banco_query($query){
	global $_BANCO;
	global $_MOBILE;
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	if($_BANCO['TYPE'] == "mysql"){
		if($_BANCO["UTF8"])$query = utf8_encode($query);
		$result = mysql_query($query);
		
		if(!$result)
			die(($_MOBILE?'<div data-role="page">'.'ERRO BANCO: Consulta Inválida:<br />' . $query . '<br /><br />Erro Mysql -> ' .  mysql_error().'</div>' : 'ERRO BANCO: Consulta Inválida:<br />' . $query . '<br /><br />Erro Mysql -> ' .  mysql_error()) );
		else 
			return $result;
	}

}

function banco_num_rows($result){
	global $_BANCO;
	
	if($_BANCO['TYPE'] == "mysql")
		return mysql_num_rows($result);
}

function banco_num_fields($result){
	global $_BANCO;
	
	if($_BANCO['TYPE'] == "mysql")
		return mysql_num_fields($result);
}

function banco_field_name($result,$num_field){
	global $_BANCO;
	
	if($_BANCO['TYPE'] == "mysql")
		return mysql_field_name($result,$num_field);
}

function banco_fields_names($table){
	global $_BANCO;
	
	if($_BANCO['TYPE'] == "mysql"){
		$res = banco_query("select * from ".$table." limit 1");
		
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

function banco_row($result){
	global $_BANCO;
	
	if($_BANCO['TYPE'] == "mysql")
		return mysql_fetch_row($result);
}

function banco_row_array($result){
	global $_BANCO;
	
	if($_BANCO['TYPE'] == "mysql")
		return mysql_fetch_array($result);
}

function banco_sql($sql){
	global $_SYSTEM;
	
	if($_SYSTEM["BANCO_UTF8"])$sql = utf8_encode($sql);
	
	$res = banco_query($sql);
	
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

function banco_sql_names($sql,$campos){
	global $_SYSTEM;
	global $_BANCO;
	
	if($_SYSTEM["BANCO_UTF8"])$sql = utf8_encode($sql);
	
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
		
		$max = banco_num_rows($res);
		
		for($i=0;$i<$max;$i++){
			$rows_aux = banco_row_array($res);
			$count=0;
			
			foreach($campos_name as $campo_name){
				$rows_out[$campo_name] = ( !is_numeric($rows_aux[$count]) ? smartstripslashes($rows_aux[$count]) : $rows_aux[$count]);
				if($_BANCO["UTF8"])$rows_out[$campo_name] = utf8_decode($rows_out[$campo_name]);
				$count++;
			}
			$rows[] = $rows_out;
		}
		
		return $rows;
	}
	else
		return NULL;
}

function banco_select($campos,$tabela,$extra){
	global $_BANCO;
	
	if($extra)
		$sql = "SELECT " . $campos . " FROM " . $tabela . " " . $extra;
	else
		$sql = "SELECT " . $campos . " FROM " . $tabela;
	
	$res = banco_query($sql);
	
	if(banco_num_rows($res))
	{
		$max = banco_num_rows($res);
		
		for($i=0;$i<$max;$i++){
			$rows[$i] = banco_row_array($res);
			
			if($rows[$i])
			foreach($rows[$i] as $k => $val){
				if(!is_numeric($val)){
					$rows[$i][$k] = smartstripslashes($val);
					if($_BANCO["UTF8"])$rows[$i][$k] = utf8_decode($rows[$i][$k]);
				}
			}
		}
		
		return $rows;
	}
	else
		return NULL;
}

function banco_select_name($campos,$tabela,$extra){
	global $_BANCO;
	
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
		
		$max = banco_num_rows($res);
		
		for($i=0;$i<$max;$i++){
			$rows_aux = banco_row_array($res);
			$count=0;
			
			foreach($campos_name as $campo_name){
				$rows_out[$campo_name] = ( !is_numeric($rows_aux[$count]) ? smartstripslashes($rows_aux[$count]) : $rows_aux[$count]);
				if($_BANCO["UTF8"])$rows_out[$campo_name] = utf8_decode($rows_out[$campo_name]);
				$count++;
			}
			$rows[] = $rows_out;
		}
		
		return $rows;
	}
	else
		return NULL;
}

function banco_update($campos,$tabela,$extra){
	if($extra)
		$sql = "UPDATE " . $tabela . " SET " . $campos . " " . $extra;
	else
		$sql = "UPDATE " . $tabela . " SET " . $campos;
	
	$res = banco_query($sql);
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
	foreach($dados as $dado){
		$count++;
		$nomes .= $dado[0];
		$campos .= ( $dado[2] ? $dado[1] : "'" . $dado[1] . "'" );
		if($count < count($dados)){
			$nomes .= ',';
			$campos .= ',';
		}
		
	}
	
	$sql = "INSERT INTO " . $tabela . " (" . $nomes . ") VALUES (" . $campos . ")";
	$res = banco_query($sql);
}

function banco_insert_name_varios($dados,$tabela){
	$count = 0;
	if($dados)
	foreach($dados as $dado){
		$count2 = 0;
		$nomes .= $dado[0];
		
		if($count < count($dados) - 1){
			$nomes .= ',';
		}
		
		if($dado[1])
		foreach($dado[1] as $dado2){
			if($count == 0){
				$campos[$count2] = "(";
			}
			
			$campos[$count2] .= ( $dado[2] ? $dado2 : "'" . $dado2 . "'" );
			
			if($count < count($dados) - 1){
				$campos[$count2] .= ',';
			} else {
				$campos[$count2] .= ")";
			}
			
			$count2++;
		}
		$count++;
	}
	
	$count = 0;
	if($campos)
	foreach($campos as $campo){
		$conjunto_campos .= $campo . ($count < count($campos) - 1 ? ",\n":"");
		$count++;
	}
	
	if($conjunto_campos){
		$sql = "INSERT IGNORE INTO " . $tabela . " (" . $nomes . ") VALUES " . $conjunto_campos;
		$res = banco_query($sql);
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

	if($_BANCO['TYPE'] == "mysql")
		return mysql_insert_id();
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

function banco_total_rows($tabela,$extra){
	global $_SYSTEM;

	if($_SYSTEM["BANCO_UTF8"]){
		$tabela = utf8_encode($tabela);
		$extra = utf8_encode($extra);
	}
	
	$resultado = banco_select
	(
		"count(*) as total_record",
		$tabela,
		$extra
	);
	
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

?>