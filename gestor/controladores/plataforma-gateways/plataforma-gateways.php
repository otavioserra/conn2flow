<?php

// ===== Plataforma responsável por receber solicitações de 'gateways de pagamentos'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-gateways';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

function plataforma_historico_incluir($params = false){
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
		3 - alteracao, alteracao_txt [campo] - o histórico mostra um valor pré-definido, caso necessário informar um valor a mais, basta informar a 'alteracao_txt' e se quiser também o 'campo'. E caso o valor do 'alteracao' tenha marcação #campo# , o sistema subistituirá esse valor com o nome do 'campo'.
	*/
	// ===== 
	
	if(isset($alteracoes)){
		$usuario = gestor_usuario();
		
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
			$campos = null; $campo_sem_aspas_simples = null;
			
			if(isset($_GESTOR['host-id'])){ $campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 												$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "modulo"; $campo_valor = (isset($modulo_id) ? $modulo_id : $_GESTOR['modulo-id']); 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			if(!isset($sem_id)){
				$campo_nome = "id"; $campo_valor = $id_numerico; 																$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			}
			
			$campo_nome = "versao"; $campo_valor = (isset($deletar) ? 1 : 0) + (int)$versao_bd; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			$campo_nome = "campo"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "opcao"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "filtro"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "alteracao_txt"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "valor_antes"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "valor_depois"; if(isset($alteracao[$campo_nome])){$campo_valor = $alteracao[$campo_nome];			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			$campo_nome = "tabela"; if(isset($alteracao[$campo_nome])){$campo_valor = json_encode($alteracao[$campo_nome]);		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			
			$campo_nome = "data"; $campo_valor = 'NOW()'; 				$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"historico"
			);
		}
	}
}

// =========================== Funções da Plataforma

function plataforma_opcao(){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('log');
	
	switch($_REQUEST['action']){
		case 'cancel':
		case 'return':
			log_disco('[plataforma_opcao] - action: '.$_REQUEST['action']);
		break;
		default:
			log_disco('[plataforma_opcao] - no-action');
	}
}

// =========================== Funções de Acesso

function plataforma_gateways_404(){
	http_response_code(404);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '404',
		'info' => 'JSON not found',
	));
	exit;
}

function plataforma_gateways_200(){
	http_response_code(200);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'OK',
	));
	exit;
}

function plataforma_gateways_start(){
	global $_GESTOR;
	global $_INDEX;
	
	// ===== Verifica a opção, executa interface caso encontrado e retorna os dados
	
	switch($_GESTOR['caminho'][1]){
		// case 'opcao': $dados = plataforma_opcao(); break;
	}
	
	// ===== Caso haja dados criados por alguma opção, retornar JSON e finalizar. Senão retornar JSON 404.
	
	if(isset($dados)){
		header("Content-Type: application/json; charset: UTF-8");
		echo json_encode($dados);
		exit;
	}
	
	plataforma_gateways_404();
}

// =========================== Inciar Plataforma

plataforma_gateways_start();

?>