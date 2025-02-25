<?php

include('config.php');
include('geral.php');
include('banco.php');

$_LOCAL_ID								=		"update";

$_BANCO['TYPE']							=		"mysqli";
$_BANCO['USUARIO']						=		$_B2MAKE['bd-user'];
$_BANCO['SENHA']						=		$_B2MAKE['bd-pass'];
$_BANCO['NOME']							=		$_B2MAKE['bd-user'];
$_BANCO['HOST']							=		"127.0.0.1";
$_BANCO['UTF8']							=		true;

function start(){
	global $_LOCAL_ID;
	global $_UPGRADE_RETURN;
	
	$num = banco_num_rows(banco_query("SHOW TABLES LIKE 'variavel_global'"));
	
	if($num == 0){
		$sql_inicial = 'CREATE TABLE IF NOT EXISTS `variavel_global` (
		  `id_variavel_global` INT NOT NULL AUTO_INCREMENT,
		  `grupo` VARCHAR(100) NULL,
		  `variavel` VARCHAR(100) NULL,
		  `valor` TEXT NULL,
		  `tipo` VARCHAR(10) NULL,
		  `descricao` TEXT NULL,
		  `status` CHAR(1) NULL,
		  PRIMARY KEY (`id_variavel_global`))
		ENGINE = InnoDB';
		
		banco_query($sql_inicial);
		
		$campos = null;
		
		$campo_nome = "grupo"; $campo_valor = 'b2make'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "variavel"; $campo_valor = 'url'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "valor"; $campo_valor = 'https://platform.b2make.com/updates/'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "tipo"; $campo_valor = 'string'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "descricao"; $campo_valor = 'Url de acesso do atualizador do B2Make'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"variavel_global"
		);
		
		$campos = null;
		
		$campo_nome = "grupo"; $campo_valor = 'b2make'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "variavel"; $campo_valor = 'version'; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "valor"; $campo_valor = '1'; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "tipo"; $campo_valor = 'int'; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "descricao"; $campo_valor = 'B2Make version'; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"variavel_global"
		);
		
		$campos = null;
		
		$campo_nome = "grupo"; $campo_valor = 'b2make'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "variavel"; $campo_valor = 'pub_id'; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "valor"; $campo_valor = $_REQUEST['pub_id']; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "tipo"; $campo_valor = 'string'; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "descricao"; $campo_valor = 'B2Make Pub Id'; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "status"; $campo_valor = 'A'; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"variavel_global"
		);
		
		$install = true;
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'variavel',
			'valor',
		))
		,
		"variavel_global",
		"WHERE status='A'"
		." AND grupo='b2make'"
	);
	
	if($resultado){
		foreach($resultado as $res){
			switch($res['variavel']){
				case 'url': $url = $res['valor']; break;
				case 'version': $version = $res['valor']; break;
			}
		}
		
		$data['version'] = ($install ? '0' : $version);
		
		$data = http_build_query($data);
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$xml = curl_exec($curl);
		
		curl_close($curl);
		
		libxml_use_internal_errors(true);
		$obj_xml = simplexml_load_string($xml);
		
		if(!$obj_xml){
			return Array(
				'error' => $_LOCAL_ID.': XML inválido'
			);
		}
		
		if(count($obj_xml->error) > 0){
			return Array(
				'error' => $_LOCAL_ID.': XML Dados inválidos: '.$xml
			);
		}
		
		$root = $_SERVER["DOCUMENT_ROOT"].'/b2make/';
		
		if(!is_file($root.'updates/'.$obj_xml->file) || $obj_xml->force_download){
			$newUpdate = file_get_contents($obj_xml->url);
			if(!is_dir($root.'updates/')) mkdir($root.'updates/');
			$dlHandler = fopen($root.'updates/'.$obj_xml->file, 'w');
			if(!fwrite($dlHandler,$newUpdate)){
				return Array(
					'error' => $_LOCAL_ID.': Não é possível salvar o download'
				);
			}
			fclose($dlHandler);
			$downloaded = true;
		}			
		
		if($install || $downloaded || $obj_xml->force_install){
			$zip = new ZipArchive;
			$res = $zip->open($root.'updates/'.$obj_xml->file);
			
			if($res === TRUE){
				$zip->extractTo($root);
				$zip->close();
				
				if(is_file($root.'upgrade.php')){
					include($root.'upgrade.php');
					unlink($root.'upgrade.php');
				}
				
				return Array(
					'status' => 'Ok',
					'version' => $obj_xml->version,
					'upgradeReturn' => $_UPGRADE_RETURN,
				);
			} else {
				return Array(
					'error' => $_LOCAL_ID.': Não é possível descompactar o arquivo'
				);
			}
		} else {
			return Array(
				'status' => 'JaAtualizado',
			);
		}
	} else {
		return Array(
			'error' => $_LOCAL_ID.': Não existe "variavel_global"'
		);
	}
}

function main(){
	$saida = start();
	
	echo formatar_xml($saida);
}

main();

?>