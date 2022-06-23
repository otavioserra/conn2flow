<?php

// ===== Caminho inicial do Gestor do Cliente

$_INDEX											=	Array();

$_INDEX['sistemas-dir']							=	'../b2make-gestor-cliente/';

// ===== Configuração Inicial do Gestor do Cliente.

$_GESTOR										=	Array();

$_GESTOR['bibliotecas']							=	Array('banco','modelo');

require_once($_INDEX['sistemas-dir'] . 'config-cliente.php');

// ===== Configuração deste host

require_once($_INDEX['sistemas-dir'] . 'config.php');

$_GESTOR['modulo-id']							=	'update-sys';

// ===== Funções Principais da atualização

function atualizar_banco_de_dados(){
	// ===== Tabelas do banco de dados com seus SQLs.
	
	/*
	$bancoDeDados = Array(
		'tabelas' => Array(
			'tabela' => 
				'SQL'
			,
		)
	);
	*/
	
	/*
			
			'tabela' => 
				'SQL_CODE'
			,
	*/
	
	// ===== Varrear todas as tabelas.
	
	if(isset($bancoDeDados))
	foreach($bancoDeDados['tabelas'] as $tabela => $sql){
		// ===== Criar tabela caso não exista, senão atualizar campos.
		
		$num = banco_num_rows(banco_query("SHOW TABLES LIKE '".$tabela."'"));
		
		if($num == 0){
			banco_query($sql);
		} else {
			// ===== Pegar todos os campos da tabela.
			
			$campos = banco_fields_names($tabela);
			
			// ===== Varrer todas as linhas do SQL.
			
			$alterTableAfter = '';
			foreach(preg_split("/((\r?\n)|(\r\n?))/", $sql) as $lineSQL){
				$lineSQL = trim($lineSQL);
				$line_arr = explode(' ',$lineSQL);
				
				if($line_arr[0]){
					switch($line_arr[0]){
						case 'CREATE':
						case 'ENGINE':
						case 'PRIMARY':
							// Ignorar lineSQL
						break;
						default:
							// ===== Caso encontre o padrão `campoNome`. Verifica se todos os campos existem.
						
							preg_match('/`.*?`/', $lineSQL, $matches);
							
							if($matches[0]){
								$campo = ltrim(rtrim($matches[0],"`"),"`");
								
								$foundCampo = false;
								if(isset($campos))
								foreach($campos as $campoBD){
									if($campo == $campoBD){
										$foundCampo = true;
										break;
									}
								}
								
								// ===== Caso não encontre um campo, altera a tabela e inclui a linha.
								
								if(!$foundCampo){
									$campoDados = rtrim($lineSQL,",");
									banco_query('ALTER TABLE `'.$tabela.'` ADD '.$campoDados . $alterTableAfter);
								}
								
								// ===== After para colocar na sequência correta que vem do SQL.
								
								$alterTableAfter = ' AFTER `'.$campo.'`';
							}
							
					}
				}
			}
		}
	}
}

// ===== Interface principal

function main(){
	global $_GESTOR;
	
	// ===== Atualizar o banco de dados para a última versão.
	
	atualizar_banco_de_dados();
	
	// ===== Retornar o JSON com os status de retorno.
	
	$_GESTOR['json'] = Array(
		'status' => 'OK',
	);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode($_GESTOR['json']);
}

main();

?>