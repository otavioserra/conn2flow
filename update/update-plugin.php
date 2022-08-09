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
	// ===== Ler o SQL do banco de dados.
	
	$bancoSQL = file_get_contents('update.sql');
	
	// ===== Varrer todas as linhas do SQL cru.
	
	$tabelaAtual = '';
	$tabelaAberta = false;
	$tabelaFechada = false;
	$bancoDeDados['tabelas'] = Array();
	$tabelaAux = Array();
	
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $bancoSQL) as $lineSQL){
		$lineSQL = trim($lineSQL);
		
		if(preg_match('/'.preg_quote('CREATE TABLE IF NOT EXISTS').'/', $lineSQL) > 0){
			$lineSQL = preg_replace('/`.*?`\./', '', $lineSQL);
			
			preg_match('/`.*?`/', $lineSQL, $matches);
			
			if($matches[0]){
				$tabelaAtual = ltrim(rtrim($matches[0],"`"),"`");
				$tabelaAberta = true;
				$tabelaAux[$tabelaAtual] = '';
			}
		}
		
		if($tabelaAberta){
			$tabelaAux[$tabelaAtual] .= $lineSQL."\r\n";
		}
		
		if(preg_match('/'.preg_quote('ENGINE').'/', $lineSQL) > 0){
			if($tabelaAberta){
				$tabelaAberta = false;
				$bancoDeDados['tabelas'][$tabelaAtual] = $tabelaAux[$tabelaAtual];
			}
		}
	}
	
	// ===== Controlador para conferir se todas as tabelas foram processados.
	
	$tabelasProcessadas = Array();
	
	// ===== Varrear todas as tabelas.
	
	if(isset($bancoDeDados))
	foreach($bancoDeDados['tabelas'] as $tabela => $sql){
		// ===== Incluir a tabela no controlador 'tabelasProcessadas'.
		
		$tabelasProcessadas[] = $tabela;
		
		// ===== Criar tabela caso não exista, senão atualizar campos.
		
		$num = banco_num_rows(banco_query("SHOW TABLES LIKE '".$tabela."'"));
		
		if($num == 0){
			banco_query($sql);
		} else {
			// ===== Controlador para conferir se todos os campos foram processados.
			
			$camposProcessados = Array();
			
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
								
								// ===== Incluir o campo no controlador 'camposProcessados'.
								
								$camposProcessados[] = $campo;

								// ===== After para colocar na sequência correta que vem do SQL.
								
								$alterTableAfter = ' AFTER `'.$campo.'`';
								
							}
							
					}
				}
			}
			
			// ===== Remover os campos não processados. Ou seja, que já foram excluídos do SQL.
			
			if(isset($campos))
			foreach($campos as $campoBD){
				$foundCampo = false;
				
				if(count($camposProcessados) > 0)
				foreach($camposProcessados as $campoProc){
					if($campoBD == $campoProc){
						$foundCampo = true;
						break;
					}
				}
				
				if(!$foundCampo){
					banco_query('ALTER TABLE `'.$tabela.'` DROP COLUMN '.$campoBD);
				}
			}
		}
	}
	
	// ===== Remover as tabelas não processadas. Ou seja, que já foram excluídos do SQL.
	
	// IMPORTANTE!!!! Plugins deletam tabelas que não são do plugin. Encontrar outra forma de excluir.
	
	/* $tabelasBD = banco_tabelas_lista();
	
	if(count($tabelasBD) > 0)
	foreach($tabelasBD as $tabelaBD){
		$foundTabela = false;
		
		if(count($tabelasProcessadas) > 0)
		foreach($tabelasProcessadas as $tabelaProc){
			if($tabelaBD == $tabelaProc){
				$foundTabela = true;
				break;
			}
		}
		
		if(!$foundTabela){
			banco_query('DROP TABLE IF EXISTS `'.$tabelaBD.'`');
		}
	} */
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