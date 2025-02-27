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

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"updates_robo";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
$_HTML['LAYOUT']			=	"../layout.html";

$GLOBALS['extended_ins'] = true;

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

$_UPDATE_PASSOS[] = Array('titulo' => 'Descompactação do Instalador');
$_UPDATE_PASSOS[] = Array('titulo' => 'Atualização Módulo Update');
$_UPDATE_PASSOS[] = Array('titulo' => 'Backup Arquivos');
$_UPDATE_PASSOS[] = Array('titulo' => 'Backup do Banco de Dados');
$_UPDATE_PASSOS[] = Array('titulo' => 'Atualização do Banco de Dados');
$_UPDATE_PASSOS[] = Array('titulo' => 'Atualização Arquivos');
$_UPDATE_PASSOS[] = Array('titulo' => 'Limpeza Arquivos Temporários');

$_UPDATE_TMP = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'tmp'.$_SYSTEM['SEPARADOR'];
$_UPDATE_FILE = $_UPDATE_TMP.'update.zip';

// Funções do Sistema

function sqlAddslashes($a_string = '', $is_like = FALSE) {
  if ($is_like) {
    $a_string = str_replace('\\', '\\\\\\\\', $a_string);
  } else {
    $a_string = str_replace('\\', '\\\\', $a_string);
  }
  $a_string = str_replace('\'', '\\\'', $a_string);
 
  return $a_string;
} // end of the 'sqlAddslashes()' function
 
function backquote($a_name, $do_it = TRUE) {
  if ($do_it && PMA_MYSQL_INT_VERSION >= 32306 && !empty($a_name)
      && $a_name != '*') {
 
     if (is_array($a_name)) {
        $result = array();
        reset($a_name);
        while(list($key, $val) = each($a_name)) {
           $result[$key] = '`' . $val . '`';
        }
        return $result;
     } else {
        return '`' . $a_name . '`';
     }
  } else {
     return $a_name;
  }
} // end of the 'backquote()' function

function getTableContentFast($db, $table, $add_query = '', $handler) {
   global $use_backquotes;
   global $rows_cnt;
   global $current_row;
   global $con;
   global $fp;
 
  $local_query = 'SELECT * FROM ' . backquote($db) . '.' . backquote($table)
  . $add_query;
 
  $result = mysql_query($local_query,$con);
  if ($result != FALSE) {
     $fields_cnt = mysql_num_fields($result);
     $rows_cnt   = mysql_num_rows($result);
 
     // Checks whether the field is an integer or not
     for ($j = 0; $j < $fields_cnt; $j++) {
         $field_set[$j] = backquote(mysql_field_name($result, $j), $use_backquotes);
         $type = mysql_field_type($result, $j);
         if ($type == 'tinyint' || $type == 'smallint' ||
             $type == 'mediumint' || $type == 'int' ||
             $type == 'bigint'  ||$type == 'timestamp') {
             $field_num[$j] = TRUE;
         } else {
             $field_num[$j] = FALSE;
         }
     } // end for
 
     // Sets the scheme
     if (isset($GLOBALS['showcolumns'])) {
         $fields = implode(', ', $field_set);
         $schema_insert = 'INSERT INTO ' . backquote($table)
         . ' (' . $fields . ') VALUES (';
     } else {
         $schema_insert = 'INSERT INTO ' .
         backquote($table) . ' VALUES (';
     }
 
     $search = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
     $replace      = array('{FONTE}', '\n', '\r', '\Z');
     $current_row  = 0;
 
     @set_time_limit($GLOBALS['cfg']['ExecTimeLimit']);
 
     // loic1: send a fake header to bypass browser timeout if data
     //        are bufferized - part 1
     if (!empty($GLOBALS['ob_mode']) || (isset($GLOBALS['zip'])
         || isset($GLOBALS['bzip']) || isset($GLOBALS['gzip']))) {
         $time0 = time();
     }
 
     while ($row = mysql_fetch_row($result)) {
         $current_row++;
         for ($j = 0; $j < $fields_cnt; $j++) {
            if (!isset($row[$j])) {
                 $values[] = 'NULL';
            } else if ($row[$j] == '0' || $row[$j] != '') {
                 // a number
                 if ($field_num[$j]) {
                     $values[] = $row[$j];
                 } else {
                    // a string
                    $values[] = "'" . str_replace($search, $replace,
                    sqlAddslashes($row[$j])) . "'";
                 }
           } else {
              $values[] = "''";
           } // end if
        } // end for
 
        // Extended inserts case
        if (isset($GLOBALS['extended_ins'])) {
            if ($current_row == 1) {
               $insert_line  = $schema_insert . implode(', ', $values) . ')'.($rows_cnt == $current_row? ';':',');
            } else {
               $insert_line  = '(' . implode(', ', $values) . ')'.($rows_cnt == $current_row? ';':',');
            }
        } else {
        // Other inserts case
           $insert_line = $schema_insert . implode(', ', $values) . ');';
        }
        unset($values);
 
        // Call the handler
        $inserts .= $insert_line . "\n";
 
        // loic1: send a fake header to bypass browser timeout if data
        //        are bufferized - part 2
        if (isset($time0)) {
            $time1 = time();
            if ($time1 >= $time0 + 30) {
               $time0 = $time1;
               header('X-pmaPing: Pong');
            }
        } // end if
     } // end while
  } // end if ($result != FALSE)
  mysql_free_result($result);
 
  return $inserts;
} // end of the 'getTableContentFast()' function

function zipar($source, $destination){
    if(!extension_loaded('zip') || !file_exists($source)){
        return false;
    }

    $zip = new ZipArchive();
    if(!$zip->open($destination, ZIPARCHIVE::OVERWRITE)){
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if(is_dir($source) === true){
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
		
		foreach ($files as $file){
			$file = str_replace('\\', '/', realpath($file));

			if(is_dir($file) === true){
				$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
			} else if (is_file($file) === true){
				$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
			}
        }
    } else if (is_file($source) === true){
        $zip->addFromString(basename($source), file_get_contents($source));
    }
	
	return $zip->close();
}

function descompactar_installer(){
	global $_UPDATE_TMP;
	global $_SYSTEM;
	global $_ROOT_FTP;
	global $_CONEXAO_FTP;
	global $_ROBO_UPDATE;
	
	$path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'tmp'.$_SYSTEM['SEPARADOR'];
	
	ftp_conectar();
	ftp_definir_root();
	
	$ftp_dir = $_ROOT_FTP.'files/tmp/'.'update';
	
	$local_zip = $path.'update.zip';
	$local_dir = $path.'update-tmp';
	
	$zip = new ZipArchive;
	$res = $zip->open($local_zip);
	if ($res === TRUE) {
		$zip->extractTo($local_dir);
		$zip->close();
		$mensagem = 'Descompactado';
		$incremento = true;
	} else {
		$mensagem = 'Falhou';
		$erro = 1;
	}
	
	ftp_put_recursive($local_dir, $ftp_dir);
	removeDirectory($local_dir);
	
	ftp_fechar_conexao();
	
	return Array(
		'erro' => $erro,
		'incremento' => $incremento,
		'mensagem' => $mensagem,
	);
}

function atualizar_updater(){
	global $_UPDATE_TMP;
	global $_SYSTEM;
	global $_ROOT_FTP;
	global $_CONEXAO_FTP;
	global $_ROBO_UPDATE;
	
	$path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'tmp'.$_SYSTEM['SEPARADOR'];
	
	ftp_conectar();
	ftp_definir_root();
	
	$ftp_dir = $_ROOT_FTP.'admin/update/';
	$local_dir = $path.'update'.$_SYSTEM['SEPARADOR'].'admin'.$_SYSTEM['SEPARADOR'].'update'.$_SYSTEM['SEPARADOR'];
	
	ftp_put_recursive($local_dir, $ftp_dir);
	ftp_fechar_conexao();
	
	$mensagem = 'Atualizado';
	$incremento = true;
	
	return Array(
		'erro' => $erro,
		'incremento' => $incremento,
		'mensagem' => $mensagem,
		'atualizacao_update' => 1,
	);
}

function backup_arquivos(){
	global $_UPDATE_TMP;
	global $_SYSTEM;
	global $_ROOT_FTP;
	global $_CONEXAO_FTP;
	global $_ROBO_UPDATE;
	
	ftp_conectar();
	ftp_definir_root();
	
	$ftp_backup = $_ROOT_FTP.'files/backup/';
	$ftp_update = $_ROOT_FTP.'files/backup/update1/';
	
	$local_backup = $_SYSTEM['PATH'].'files/backup/';
	$local_update = $_SYSTEM['PATH'].'files/backup/update1/';
	
	switch($_ROBO_UPDATE['sub_passo']){
		case 0:
			if(!ftp_is_dir($ftp_backup)){
				ftp_mkdir($_CONEXAO_FTP,$ftp_backup);
				ftp_chmod($_CONEXAO_FTP,0777,$ftp_backup);
			}
			if(!ftp_is_dir($ftp_update)){
				ftp_mkdir($_CONEXAO_FTP,$ftp_update);
			}
			
			$files = Array(
				'config.php',
				'favicon.ico',
				'index.php',
				'robo.php',
				'unsubscribe.php',
			);
			
			foreach($files as $file){
				ftp_put($_CONEXAO_FTP, $ftp_update.$file, $_SYSTEM['PATH'].$file, FTP_BINARY);
			}
			
			$mensagem = 'Arquivos da raiz copiados';
		break;
		case 1:
			$dir = 'admin';
			$mensagem = 'Admin copiados';
		break;
		case 2:
			$dir = 'includes';
			$mensagem = 'Includes copiados';
		break;
		case 3:
			$dir = 'images';
			$mensagem = 'Images copiados';
		break;
		
	}
	
	if($dir){
		$local_dir = $_SYSTEM['PATH'].$dir;
		$ftp_dir = $ftp_update.$dir;
		
		ftp_put_recursive($local_dir, $ftp_dir);
	}
	
	if($_ROBO_UPDATE['sub_passo'] == 4){
		zipar($local_update, $local_backup.'backup-arquivos.zip');
		chmod($local_backup.'backup-arquivos.zip', 0777);
		$mensagem = 'Compactado';
	}
	
	if($_ROBO_UPDATE['sub_passo'] == 5){
		ftp_recursive_delete($ftp_update);
		$incremento = true;
		$mensagem = 'Temporários removidos';
	}
	
	ftp_fechar_conexao();
	
	return Array(
		'incremento' => $incremento,
		'mensagem' => $mensagem,
	);
}

function backup_banco(){
	global $_UPDATE_TMP;
	global $_SYSTEM;
	global $_ROOT_FTP;
	global $_CONEXAO_FTP;
	global $_ROBO_UPDATE;
	global $_BANCO;
	global $_CONEXAO_BANCO;
	global $con;
	global $fp;
	global $rows_cnt;
	
	$tempdir = $_SYSTEM['PATH'].'files/backup/';
	$filename = 'backup-banco.sql';
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	$con = $_CONEXAO_BANCO;
	
	if($_ROBO_UPDATE['sub_passo'] == 0){
		$result = mysql_query('SHOW TABLES',$con);
		while ($row = mysql_fetch_row($result)) {
			$tabelas[] = $row[0];
		}
		
		$file = "SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';\n\n";
		
		for($x=0; $x<count($tabelas); $x++){
		   $saida = '';
		   $result2 = mysql_query('SHOW CREATE TABLE '.$tabelas[$x],$con);
			while ( $table_def= mysql_fetch_row($result2) ) 
				{ 
					for ($i=1; $i<count($table_def); $i++) 
					{ 
						
					   $saida = $table_def[$i]; 
					} 
				} 
			$s_p = explode("\n",$saida);
			
			$saida_aux = '';
			$count = 0;
			if($s_p){
				foreach($s_p as $v){
					$count++;
					$saida_aux .= preg_replace('/^  KEY/','  INDEX',$v) . (count($s_p) > $count ? "\n" : "");
				}
				$saida = $saida_aux;
			}
			
			$file .= $saida.';'."\n\n";
		}
		
		file_put_contents($tempdir.$filename,$file);
		
		$mensagem = 'Esquemas de tabelas salvos';
	} else {
		$result = mysql_query('SHOW TABLES',$con);
		while ($row = mysql_fetch_row($result)) {
			$tabelas[] = $row[0];
		}
		
		for($x=0; $x<count($tabelas); $x++){
			$file .= getTableContentFast($_BANCO['NOME'], $tabelas[$x], '', '');
			if($rows_cnt > 0)$file .= "\n";
		}
		
		file_put_contents($tempdir.$filename,$file,FILE_APPEND);
		
		$incremento = true;
		$mensagem = 'Dados salvos';
	}
	
	if($_CONEXAO_BANCO)banco_fechar_conexao();
	
	return Array(
		'incremento' => $incremento,
		'mensagem' => $mensagem,
	);
}

function atualizar_banco(){
	global $_SYSTEM;
	global $_BANCO;
	
	$s = $_SYSTEM['SEPARADOR'];
	$path = $_SYSTEM['PATH'].'files'.$s.'tmp'.$s.'update'.$s;
	
	$mapa_banco_txt = file_get_contents($path.'bd'.$s.'mapa_banco.txt');
	
	$mapa_banco = json_decode($mapa_banco_txt, true);
	
	banco_conectar();
	
	if(
		$mapa_banco['tabelas_sequencia'] &&
		$mapa_banco['tabelas_chaves_estrangeiras'] &&
		$mapa_banco['tabelas_campos']
	){
		$res = banco_query("SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0");
		$res = banco_query("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0");
		$res = banco_query("SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL'");
		
		$tabelas_bd = banco_sql("SHOW TABLES FROM ".$_BANCO['NOME']);
		
		foreach($mapa_banco['tabelas_sequencia'] as $tabela_up){
			$tabela_found = false;
			if($tabelas_bd)
			foreach($tabelas_bd as $tabela){
				if($tabela[0] == $tabela_up){
					$tabela_found = true;
					if($mapa_banco['tabelas_campos'][$tabela_up]){
						$campos_bd = banco_sql("SHOW COLUMNS FROM ".$tabela_up);
						
						foreach($mapa_banco['tabelas_campos'][$tabela_up] as $campos_up){
							$campo_found = false;
							if($campos_bd)
							foreach($campos_bd as $campos){
								if($campos[0] == $campos_up['Field']){
									$campo_found = true;
									break;
								}
							}
							
							if($campos_up['Key'] == 'PRI'){
								$primary_keys[$tabela_up] .= ($primary_keys[$tabela_up] ? ',':'') . $campos_up['Field'];
							}
							
							if(!$campo_found){
								$column_def = 
									$campos_up['Type']
									.($campos_up['Null'] == 'YES'? " " : " NOT ")."NULL"
									.($campos_up['Default'] ? " DEFAULT ".$campos_up['Default'] : "")
									.($campos_up['Extra'] == 'auto_increment' ? " AUTO_INCREMENT" : "")
								;
								
								$res = banco_query("ALTER TABLE ".$tabela_up." ADD COLUMN ".$campos_up['Field']." ".$column_def);
								
								if($campos_up['Key'] == 'PRI'){
									$res = banco_query("ALTER TABLE ".$tabela_up." DROP PRIMARY KEY, ADD PRIMARY KEY (".$primary_keys[$tabela_up].")");
									if($mapa_banco['tabelas_chaves_estrangeiras'][$tabela_up])
									foreach($mapa_banco['tabelas_chaves_estrangeiras'][$tabela_up] as $chaves_estrangeira){
										if($chaves_estrangeira['coluna'] == $campos_up['Field']){
											$res = banco_query("ALTER TABLE ".$tabela_up." ADD INDEX fk_".$tabela_up."_".$chaves_estrangeira['tabela_externa']." (".$campos_up['Field'].")");
											$res = banco_query("ALTER TABLE `".$tabela_up."` ADD CONSTRAINT `fk_".$tabela_up."_".$chaves_estrangeira['tabela_externa']."` FOREIGN KEY (`".$campos_up['Field']."`) REFERENCES `".$chaves_estrangeira['tabela_externa']."`(`".$chaves_estrangeira['coluna_externa']."`) ON DELETE NO ACTION ON UPDATE NO ACTION");
											break;
										}
									}
								}
							}
						}
					}
					break;
				}
			}
			
			if(!$tabela_found){
				$create_table = true;
				foreach($mapa_banco['tabelas_campos'][$tabela_up] as $campos_up){
					$column_def = 
						$campos_up['Type']
						.($campos_up['Null'] == 'YES'? " " : " NOT ")."NULL"
						.($campos_up['Default'] ? " DEFAULT ".$campos_up['Default'] : "")
						.($campos_up['Extra'] == 'auto_increment' ? " AUTO_INCREMENT" : "")
					;
					
					if($campos_up['Key'] == 'PRI'){
						$primary_keys[$tabela_up] .= ($primary_keys[$tabela_up] ? ',':'') . $campos_up['Field'];
					}
					
					if($create_table){
						$res = banco_query("CREATE TABLE ".$tabela_up."(".$campos_up['Field']." ".$column_def.", PRIMARY KEY (".$campos_up['Field'].")) ENGINE=InnoDB DEFAULT CHARSET=utf8");
						$create_table = false;
					} else {
						$res = banco_query("ALTER TABLE ".$tabela_up." ADD COLUMN ".$campos_up['Field']." ".$column_def);
						
						if($campos_up['Key'] == 'PRI'){
							$res = banco_query("ALTER TABLE ".$tabela_up." DROP PRIMARY KEY, ADD PRIMARY KEY (".$primary_keys[$tabela_up].")");
						}
					}
					
					if($campos_up['Key'] == 'PRI'){
						if($mapa_banco['tabelas_chaves_estrangeiras'][$tabela_up])
						foreach($mapa_banco['tabelas_chaves_estrangeiras'][$tabela_up] as $chaves_estrangeira){
							if($chaves_estrangeira['coluna'] == $campos_up['Field']){
								$res = banco_query("ALTER TABLE ".$tabela_up." ADD INDEX fk_".$tabela_up."_".$chaves_estrangeira['tabela_externa']." (".$campos_up['Field'].")");
								$res = banco_query("ALTER TABLE `".$tabela_up."` ADD CONSTRAINT `fk_".$tabela_up."_".$chaves_estrangeira['tabela_externa']."` FOREIGN KEY (`".$campos_up['Field']."`) REFERENCES `".$chaves_estrangeira['tabela_externa']."`(`".$chaves_estrangeira['coluna_externa']."`) ON DELETE NO ACTION ON UPDATE NO ACTION");
								break;
							}
						}
					}
				}
			}
		}
		
		$res = banco_query("SET SQL_MODE=@OLD_SQL_MODE");
		$res = banco_query("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS");
		$res = banco_query("SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS");
		
		
		// ================================= Popular Tabelas ==========================================
		
		// ================================= Variável Global ==========================================
		
		$count = 0;
		$dados_campos = false;
		if($mapa_banco['variavel_global']){
			foreach($mapa_banco['variavel_global'] as $campos){
				$dados_campos['id_variavel_global'][] = $campos['id_variavel_global'];
				$dados_campos['grupo'][] = $campos['grupo'];
				$dados_campos['variavel'][] = $campos['variavel'];
				$dados_campos['valor'][] = utf8_decode($campos['valor']);
				$dados_campos['tipo'][] = $campos['tipo'];
				$dados_campos['descricao'][] = utf8_decode($campos['descricao']);
				$dados_campos['status'][] = $campos['status'];
				
				$count++;
			}
			
			$dados = Array(
				Array('id_variavel_global',$dados_campos['id_variavel_global']),
				Array('grupo',$dados_campos['grupo']),
				Array('variavel',$dados_campos['variavel']),
				Array('valor',$dados_campos['valor']),
				Array('tipo',$dados_campos['tipo']),
				Array('descricao',$dados_campos['descricao']),
				Array('status',$dados_campos['status']),
			);
			
			banco_insert_name_varios
			(
				$dados,
				"variavel_global"
			);
		}
		
		// ================================= Módulo ==========================================
		
		$count = 0;
		$dados_campos = false;
		if($mapa_banco['modulo']){
			foreach($mapa_banco['modulo'] as $campos){
				$dados_campos['id_modulo'][] = $campos['id_modulo'];
				$dados_campos['status'][] = $campos['status'];
				$dados_campos['id_modulo_pai'][] = $campos['id_modulo_pai'];
				$dados_campos['nome'][] = utf8_decode($campos['nome']);
				$dados_campos['caminho'][] = $campos['caminho'];
				$dados_campos['titulo'][] = utf8_decode($campos['titulo']);
				$dados_campos['imagem'][] = $campos['imagem'];
				$dados_campos['ordem'][] = $campos['ordem'];
				$dados_campos['descricao'][] = utf8_decode($campos['descricao']);
				
				$count++;
			}
			
			$dados = Array(
				Array('id_modulo',$dados_campos['id_modulo']),
				Array('status',$dados_campos['status']),
				Array('id_modulo_pai',$dados_campos['id_modulo_pai']),
				Array('nome',$dados_campos['nome']),
				Array('caminho',$dados_campos['caminho']),
				Array('titulo',$dados_campos['titulo']),
				Array('imagem',$dados_campos['imagem']),
				Array('ordem',$dados_campos['ordem']),
				Array('descricao',$dados_campos['descricao']),
			);
			
			banco_insert_name_varios
			(
				$dados,
				"modulo"
			);
		}
		
		// ================================= Módulo Operação ==========================================
		
		$count = 0;
		$dados_campos = false;
		if($mapa_banco['modulo_operacao']){
			foreach($mapa_banco['modulo_operacao'] as $campos){
				$dados_campos['id_modulo_operacao'][] = $campos['id_modulo_operacao'];
				$dados_campos['id_modulo'][] = $campos['id_modulo'];
				$dados_campos['nome'][] = utf8_decode($campos['nome']);
				$dados_campos['caminho'][] = $campos['caminho'];
				$dados_campos['descricao'][] = utf8_decode($campos['descricao']);
				
				$count++;
			}
			
			$dados = Array(
				Array('id_modulo_operacao',$dados_campos['id_modulo_operacao']),
				Array('id_modulo',$dados_campos['id_modulo']),
				Array('nome',$dados_campos['nome']),
				Array('caminho',$dados_campos['caminho']),
				Array('descricao',$dados_campos['descricao']),
			);
			
			banco_insert_name_varios
			(
				$dados,
				"modulo_operacao"
			);
		}
		
		$mensagem = 'Atualizado';
	} else {
		$mensagem = 'NÃO ATUALIZADO! Erro no mapa do banco de dados!';
	}
	
	$incremento = true;
	
	return Array(
		'erro' => $erro,
		'incremento' => $incremento,
		'mensagem' => $mensagem,
	);
}

function atualizar_arquivos(){
	global $_UPDATE_TMP;
	global $_SYSTEM;
	global $_ROOT_FTP;
	global $_CONEXAO_FTP;
	global $_ROBO_UPDATE;
	
	ftp_conectar();
	ftp_definir_root();
	
	$ftp_update = $_ROOT_FTP;
	$local_path = $_SYSTEM['PATH'].'files/tmp/update/';
	
	switch($_ROBO_UPDATE['sub_passo']){
		case 0:
			$dir = 'images';
			$mensagem = 'Images atualizados';
		break;
		case 1:
			$dir = 'admin';
			$mensagem = 'Admin atualizados';
		break;
		case 2:
			$dir = 'includes';
			$mensagem = 'Includes atualizados';
		break;
		case 3:
			$files = Array(
				'config.php',
				'favicon.ico',
				'index.php',
				'robo.php',
				'unsubscribe.php',
			);
			
			foreach($files as $file){
				ftp_put($_CONEXAO_FTP, $ftp_update.$file, $local_path.$file, FTP_BINARY);
			}
			
			$mensagem = 'Arquivos da raiz atualizados';
			
			$incremento = true;
		break;
		
	}
	
	if($dir){
		$local_dir = $local_path.$dir;
		$ftp_dir = $ftp_update.$dir;
		
		ftp_put_recursive($local_dir, $ftp_dir);
	}
	
	ftp_fechar_conexao();
	
	return Array(
		'incremento' => $incremento,
		'mensagem' => $mensagem,
	);
}

function limpeza(){
	global $_UPDATE_TMP;
	global $_SYSTEM;
	global $_ROOT_FTP;
	global $_CONEXAO_FTP;
	global $_ROBO_UPDATE;
	
	ftp_conectar();
	ftp_definir_root();
	
	$path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'tmp'.$_SYSTEM['SEPARADOR'];
	$ftp_dir = $_ROOT_FTP.'files/tmp/'.'update';
	
	if(ftp_is_dir($ftp_dir)){
		ftp_recursive_delete($ftp_dir);
	}
	
	unlink($path.'update.zip');
	
	ftp_fechar_conexao();
	
	$mensagem = 'Limpo';
	$incremento = true;
	
	return Array(
		'erro' => $erro,
		'incremento' => $incremento,
		'mensagem' => $mensagem,
	);
}

function iniciacao(){
	global $_UPDATE_PASSOS;
	
	foreach($_UPDATE_PASSOS as $passo){
		$passo['titulo'] = utf8_encode($passo['titulo']);
		$passos[] = $passo;
	}
	
	$saida = Array(
		'passos' => $passos,
	);
	
	return json_encode($saida);
}

function inicio(){
	global $_SYSTEM;
	global $_ROBO_UPDATE;
	
	$passo = $_ROBO_UPDATE['passo'] = (int)$_REQUEST["passo"];
	$_ROBO_UPDATE['sub_passo'] = (int)$_REQUEST["sub_passo"];
	
	switch($passo){
		case 0:
			$retorno = descompactar_installer();
			
			$incremento = $retorno['incremento'];
			$mensagem = $retorno['mensagem'];
			$erro = $retorno['erro'];
		break;
		case 1:
			$retorno = atualizar_updater();
			
			$incremento = $retorno['incremento'];
			$mensagem = $retorno['mensagem'];
			$erro = $retorno['erro'];
			$atualizacao_update = $retorno['atualizacao_update'];
		break;
		case 2:
			$retorno = backup_arquivos();
			
			$incremento = $retorno['incremento'];
			$mensagem = $retorno['mensagem'];
			$erro = $retorno['erro'];
		break;
		case 3:
			$retorno = backup_banco();
			
			$incremento = $retorno['incremento'];
			$mensagem = $retorno['mensagem'];
			$erro = $retorno['erro'];
		break;
		case 4:
			$retorno = atualizar_banco();
			
			$incremento = $retorno['incremento'];
			$mensagem = $retorno['mensagem'];
			$erro = $retorno['erro'];
		break;
		case 5:
			$retorno = atualizar_arquivos();
			
			$incremento = $retorno['incremento'];
			$mensagem = $retorno['mensagem'];
			$erro = $retorno['erro'];
		break;
		case 6:
			$retorno = limpeza();
			
			$incremento = $retorno['incremento'];
			$mensagem = $retorno['mensagem'];
			$erro = $retorno['erro'];
		break;
		
	}
	
	//sleep(1);
	
	$saida = Array(
		'atualizacao_update' => $atualizacao_update,
		'erro' => $erro,
		'mensagem' => utf8_encode($mensagem),
		'passo' => $passo+($incremento?1:0),
	);
	
	return json_encode($saida);
}

// ======================================================================================

function start(){	
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_HTML;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	
	switch($opcoes){
		case 'iniciacao':					$saida = iniciacao('ver');break;
		default: 							$saida = inicio();
	}
	
	echo utf8_encode($saida);
}

start();

?>