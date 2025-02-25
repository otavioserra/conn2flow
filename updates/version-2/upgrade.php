<?php

function upgrade_start(){
	$num = banco_num_rows(banco_query("SHOW TABLES LIKE 'servicos'"));
	
	if($num == 0){
		$sql = 'CREATE TABLE IF NOT EXISTS `servicos` (
  `id_servicos_local` INT NOT NULL AUTO_INCREMENT,
  `id_servicos` INT NULL DEFAULT NULL,
  `id_loja` INT NULL DEFAULT NULL,
  `id_site` INT NULL DEFAULT NULL,
  `nome` VARCHAR(100) NULL DEFAULT NULL,
  `descricao` TEXT NULL DEFAULT NULL,
  `imagem_path` VARCHAR(100) NULL DEFAULT NULL,
  `imagem_path_mini` VARCHAR(100) NULL DEFAULT NULL,
  `imagem_biblioteca` TINYINT(1) NULL DEFAULT NULL,
  `imagem_biblioteca_id` INT NULL DEFAULT NULL,
  `quantidade` INT NULL DEFAULT NULL,
  `preco` FLOAT NULL DEFAULT NULL,
  `visivel_de` DATE NULL DEFAULT NULL,
  `visivel_ate` DATE NULL DEFAULT NULL,
  `validade` INT NULL DEFAULT NULL,
  `validade_data` DATETIME NULL DEFAULT NULL,
  `validade_tipo` CHAR(1) NULL DEFAULT NULL,
  `observacao` TEXT NULL DEFAULT NULL,
  `desconto` INT NULL DEFAULT NULL,
  `desconto_de` DATE NULL DEFAULT NULL,
  `desconto_ate` DATE NULL DEFAULT NULL,
  `status` CHAR(1) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `publicar_id_usuario` INT NULL DEFAULT NULL,
  `publicar_status` CHAR(1) NULL DEFAULT NULL,
  `publicar_data` DATETIME NULL DEFAULT NULL,
  `publicar_mobile` TINYINT(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id_servicos_local`))
ENGINE = InnoDB';
		
		banco_query($sql);
		
		banco_update
		(
			"valor='2'",
			"variavel_global",
			"WHERE status='A'"
			." AND grupo='b2make'"
			." AND variavel='version'"
		);
	}
	
}

function upgrade_main(){
	global $_UPGRADE_RETURN;
	
	$saida = upgrade_start();
	
	$_UPGRADE_RETURN = formatar_xml($saida);
}

upgrade_main();

?>