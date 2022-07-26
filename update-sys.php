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

function atualizar_banco_de_dados_sql(){
	return "-- MySQL Script generated by MySQL Workbench
-- Tue Jul 26 10:06:22 2022
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema gestor_cliente
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `paginas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `paginas` (
  `id_paginas` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_paginas` INT NULL,
  `id_hosts_layouts` INT NULL,
  `nome` VARCHAR(255) NULL,
  `id` VARCHAR(255) NULL,
  `caminho` TEXT NULL,
  `tipo` VARCHAR(255) NULL,
  `modulo` VARCHAR(255) NULL,
  `modulo_id_registro` INT NULL,
  `opcao` VARCHAR(255) NULL,
  `raiz` TINYINT NULL,
  `html` MEDIUMTEXT NULL,
  `css` MEDIUMTEXT NULL,
  `status` CHAR(1) NULL,
  `versao` INT NULL,
  `data_criacao` DATETIME NULL,
  `data_modificacao` DATETIME NULL,
  `template_padrao` TINYINT NULL,
  `template_categoria` VARCHAR(255) NULL,
  `template_id` VARCHAR(255) NULL,
  `template_modificado` TINYINT NULL,
  `template_versao` INT NULL,
  `plugin` VARCHAR(255) NULL,
  PRIMARY KEY (`id_paginas`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `layouts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `layouts` (
  `id_layouts` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_layouts` INT NULL,
  `nome` VARCHAR(255) NULL,
  `id` VARCHAR(255) NULL,
  `categoria` VARCHAR(255) NULL,
  `fullpage` TINYINT NULL,
  `html` MEDIUMTEXT NULL,
  `css` MEDIUMTEXT NULL,
  `status` CHAR(1) NULL,
  `versao` INT NULL,
  `data_criacao` DATETIME NULL,
  `data_modificacao` DATETIME NULL,
  `template_padrao` TINYINT NULL,
  `template_categoria` VARCHAR(255) NULL,
  `template_id` VARCHAR(255) NULL,
  `template_modificado` TINYINT NULL,
  `template_versao` INT NULL,
  PRIMARY KEY (`id_layouts`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `paginas_301`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `paginas_301` (
  `id_paginas_301` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_paginas_301` INT NULL,
  `id_hosts_paginas` INT NULL,
  `caminho` TEXT NULL,
  `data_criacao` DATETIME NULL,
  PRIMARY KEY (`id_paginas_301`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `variaveis`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `variaveis` (
  `id_variaveis` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_variaveis` INT NULL,
  `modulo` VARCHAR(255) NULL,
  `id` VARCHAR(255) NULL,
  `valor` TEXT NULL,
  `tipo` VARCHAR(100) NULL,
  `grupo` VARCHAR(255) NULL,
  PRIMARY KEY (`id_variaveis`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sessoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessoes` (
  `id_sessoes` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id` VARCHAR(100) NULL,
  `acesso` INT UNSIGNED NULL,
  PRIMARY KEY (`id_sessoes`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sessoes_variaveis`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessoes_variaveis` (
  `id_sessoes_variaveis` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_sessoes` INT NULL DEFAULT NULL,
  `variavel` TEXT NULL DEFAULT NULL,
  `valor` MEDIUMTEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id_sessoes_variaveis`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `plataforma_tokens`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `plataforma_tokens` (
  `id_plataforma_tokens` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pubID` VARCHAR(100) NULL DEFAULT NULL,
  `pubIDValidation` VARCHAR(150) NULL DEFAULT NULL,
  `expiration` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `remoto` TINYINT NULL,
  PRIMARY KEY (`id_plataforma_tokens`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `servicos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `servicos` (
  `id_servicos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_servicos` INT NULL DEFAULT NULL,
  `id_hosts_paginas` INT NULL DEFAULT NULL,
  `id_hosts_arquivos_Imagem` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `status` CHAR(1) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  `template_id` VARCHAR(255) NULL DEFAULT NULL,
  `template_tipo` VARCHAR(255) NULL DEFAULT NULL,
  `preco` FLOAT NULL DEFAULT NULL,
  `quantidade` INT NULL DEFAULT NULL,
  `descricao` MEDIUMTEXT NULL DEFAULT NULL,
  `caminho` TEXT NULL,
  `lotesVariacoes` TINYINT NULL,
  `gratuito` TINYINT NULL,
  PRIMARY KEY (`id_servicos`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `arquivos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `arquivos` (
  `id_arquivos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_arquivos` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `tipo` VARCHAR(100) NULL DEFAULT NULL,
  `caminho` TEXT NULL DEFAULT NULL,
  `caminho_mini` TEXT NULL DEFAULT NULL,
  `permissao` TINYINT NULL DEFAULT NULL,
  `status` CHAR(1) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_arquivos`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `carrinho`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrinho` (
  `id_carrinho` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_carrinho` INT NULL DEFAULT NULL,
  `sessao_id` VARCHAR(255) NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_carrinho`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `carrinho_servicos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrinho_servicos` (
  `id_carrinho_servicos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_carrinho_servicos` INT NULL DEFAULT NULL,
  `id_hosts_carrinho` INT NULL DEFAULT NULL,
  `id_hosts_servicos` INT NULL DEFAULT NULL,
  `id_hosts_arquivos_Imagem` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `preco` FLOAT NULL DEFAULT NULL,
  `quantidade` INT NULL DEFAULT NULL,
  `gratuito` TINYINT NULL,
  PRIMARY KEY (`id_carrinho_servicos`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `pedidos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id_pedidos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_pedidos` INT NULL DEFAULT NULL,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL,
  `codigo` VARCHAR(255) NULL DEFAULT NULL,
  `total` FLOAT NULL DEFAULT NULL,
  `live` TINYINT NULL,
  `status` VARCHAR(255) NULL DEFAULT NULL,
  `versao` INT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL,
  PRIMARY KEY (`id_pedidos`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `pedidos_servicos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_servicos` (
  `id_pedidos_servicos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_pedidos_servicos` INT NULL DEFAULT NULL,
  `id_hosts_pedidos` INT NULL DEFAULT NULL,
  `id_hosts_servicos` INT NULL,
  `id_hosts_arquivos_Imagem` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `preco` FLOAT NULL DEFAULT NULL,
  `quantidade` INT NULL DEFAULT NULL,
  `gratuito` TINYINT NULL,
  PRIMARY KEY (`id_pedidos_servicos`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuarios_tokens`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios_tokens` (
  `id_usuarios_tokens` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_usuarios_tokens` INT NULL DEFAULT NULL,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `pubID` VARCHAR(100) NULL DEFAULT NULL,
  `pubIDValidation` VARCHAR(150) NULL DEFAULT NULL,
  `expiration` INT NULL DEFAULT NULL,
  `ip` VARCHAR(100) NULL DEFAULT NULL,
  `user_agent` TEXT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `senha_incorreta_tentativas` INT NULL,
  PRIMARY KEY (`id_usuarios_tokens`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuarios` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `id_hosts_usuarios_perfis` INT NULL,
  `nome_conta` VARCHAR(255) NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `usuario` VARCHAR(255) NULL DEFAULT NULL,
  `email` VARCHAR(255) NULL DEFAULT NULL,
  `telefone` VARCHAR(100) NULL,
  `primeiro_nome` VARCHAR(100) NULL DEFAULT NULL,
  `ultimo_nome` VARCHAR(100) NULL DEFAULT NULL,
  `nome_do_meio` VARCHAR(100) NULL DEFAULT NULL,
  `cnpj_ativo` TINYINT NULL,
  `cpf` VARCHAR(30) NULL,
  `cnpj` VARCHAR(30) NULL,
  `status` CHAR(1) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuarios`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tokens`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tokens` (
  `id_tokens` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `id` VARCHAR(100) NULL DEFAULT NULL,
  `pubID` VARCHAR(150) NULL DEFAULT NULL,
  `expiration` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_tokens`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `vouchers`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id_vouchers` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_vouchers` INT NULL DEFAULT NULL,
  `id_hosts_pedidos` INT NULL DEFAULT NULL,
  `id_hosts_servicos` INT NULL DEFAULT NULL,
  `id_hosts_servicos_variacoes` INT NULL DEFAULT NULL,
  `codigo` VARCHAR(255) NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `documento` VARCHAR(255) NULL DEFAULT NULL,
  `telefone` VARCHAR(255) NULL DEFAULT NULL,
  `status` VARCHAR(255) NULL DEFAULT NULL,
  `loteVariacao` TINYINT NULL DEFAULT NULL,
  `data_uso` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_vouchers`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `historico`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `historico` (
  `id_historico` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `modulo` VARCHAR(255) NULL DEFAULT NULL,
  `id` INT NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `campo` VARCHAR(255) NULL DEFAULT NULL,
  `opcao` VARCHAR(255) NULL DEFAULT NULL,
  `filtro` VARCHAR(255) NULL DEFAULT NULL,
  `alteracao` VARCHAR(255) NULL DEFAULT NULL,
  `alteracao_txt` TEXT NULL DEFAULT NULL,
  `valor_antes` TEXT NULL DEFAULT NULL,
  `valor_depois` TEXT NULL DEFAULT NULL,
  `tabela` TEXT NULL DEFAULT NULL,
  `data` DATETIME NULL DEFAULT NULL,
  `controlador` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id_historico`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `postagens`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `postagens` (
  `id_postagens` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_postagens` INT NULL DEFAULT NULL,
  `id_hosts_paginas` INT NULL DEFAULT NULL,
  `id_hosts_arquivos_Imagem` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `status` CHAR(1) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  `template_id` VARCHAR(255) NULL DEFAULT NULL,
  `template_tipo` VARCHAR(255) NULL DEFAULT NULL,
  `descricao` MEDIUMTEXT NULL DEFAULT NULL,
  `caminho` TEXT NULL,
  PRIMARY KEY (`id_postagens`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `componentes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `componentes` (
  `id_componentes` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_componentes` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `modulo` VARCHAR(255) NULL DEFAULT NULL,
  `html` MEDIUMTEXT NULL DEFAULT NULL,
  `css` MEDIUMTEXT NULL DEFAULT NULL,
  `status` CHAR(1) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  `template_padrao` TINYINT NULL,
  `template_categoria` VARCHAR(255) NULL,
  `template_id` VARCHAR(255) NULL,
  `template_modificado` TINYINT NULL,
  `template_versao` INT NULL,
  PRIMARY KEY (`id_componentes`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `servicos_lotes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `servicos_lotes` (
  `id_servicos_lotes` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_servicos_lotes` INT NULL DEFAULT NULL,
  `id_hosts_servicos` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `visibilidade` VARCHAR(20) NULL DEFAULT NULL,
  `dataInicio` DATETIME NULL DEFAULT NULL,
  `dataFim` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_servicos_lotes`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `servicos_variacoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `servicos_variacoes` (
  `id_servicos_variacoes` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_servicos_variacoes` INT NULL DEFAULT NULL,
  `id_hosts_servicos` INT NULL DEFAULT NULL,
  `id_hosts_servicos_lotes` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `preco` FLOAT NULL DEFAULT NULL,
  `quantidade` INT NULL DEFAULT NULL,
  `gratuito` TINYINT NULL DEFAULT NULL,
  PRIMARY KEY (`id_servicos_variacoes`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `carrinho_servico_variacoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrinho_servico_variacoes` (
  `id_carrinho_servico_variacoes` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_carrinho_servico_variacoes` INT NULL DEFAULT NULL,
  `id_hosts_carrinho` INT NULL DEFAULT NULL,
  `id_hosts_servicos` INT NULL DEFAULT NULL,
  `id_hosts_servicos_lotes` INT NULL DEFAULT NULL,
  `id_hosts_servicos_variacoes` INT NULL DEFAULT NULL,
  `id_hosts_arquivos_Imagem` INT NULL DEFAULT NULL,
  `nome_servico` VARCHAR(255) NULL DEFAULT NULL,
  `nome_lote` VARCHAR(255) NULL DEFAULT NULL,
  `nome_variacao` VARCHAR(255) NULL DEFAULT NULL,
  `preco` FLOAT NULL DEFAULT NULL,
  `quantidade` INT NULL DEFAULT NULL,
  `gratuito` TINYINT NULL,
  PRIMARY KEY (`id_carrinho_servico_variacoes`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `pedidos_servico_variacoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_servico_variacoes` (
  `id_pedidos_servico_variacoes` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_pedidos_servico_variacoes` INT NULL DEFAULT NULL,
  `id_hosts_pedidos` INT NULL DEFAULT NULL,
  `id_hosts_servicos` INT NULL DEFAULT NULL,
  `id_hosts_servicos_lotes` INT NULL DEFAULT NULL,
  `id_hosts_servicos_variacoes` INT NULL DEFAULT NULL,
  `id_hosts_arquivos_Imagem` INT NULL DEFAULT NULL,
  `nome_servico` VARCHAR(255) NULL DEFAULT NULL,
  `nome_lote` VARCHAR(255) NULL DEFAULT NULL,
  `nome_variacao` VARCHAR(255) NULL DEFAULT NULL,
  `preco` FLOAT NULL DEFAULT NULL,
  `quantidade` INT NULL DEFAULT NULL,
  `gratuito` TINYINT NULL,
  PRIMARY KEY (`id_pedidos_servico_variacoes`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `menus_itens`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `menus_itens` (
  `id_menus_itens` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_menus_itens` INT NULL DEFAULT NULL,
  `menu_id` VARCHAR(255) NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `label` VARCHAR(255) NULL DEFAULT NULL,
  `tipo` VARCHAR(255) NULL DEFAULT NULL,
  `url` VARCHAR(255) NULL DEFAULT NULL,
  `inativo` TINYINT NULL DEFAULT NULL,
  `versao` INT NULL,
  PRIMARY KEY (`id_menus_itens`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `agendamentos_datas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `agendamentos_datas` (
  `id_agendamentos_datas` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_agendamentos_datas` INT NULL,
  `data` DATE NULL,
  `total` INT NULL,
  `status` VARCHAR(255) NULL,
  PRIMARY KEY (`id_agendamentos_datas`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `agendamentos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id_agendamentos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_agendamentos` INT NULL DEFAULT NULL,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `data` DATE NULL DEFAULT NULL,
  `acompanhantes` INT NULL DEFAULT NULL,
  `senha` VARCHAR(100) NULL DEFAULT NULL,
  `status` VARCHAR(100) NULL DEFAULT NULL,
  `pubID` VARCHAR(255) NULL,
  `versao` INT NULL,
  `data_criacao` DATETIME NULL,
  `data_modificacao` DATETIME NULL,
  PRIMARY KEY (`id_agendamentos`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `agendamentos_acompanhantes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `agendamentos_acompanhantes` (
  `id_agendamentos_acompanhantes` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_agendamentos_acompanhantes` INT NULL DEFAULT NULL,
  `id_hosts_agendamentos` INT NULL DEFAULT NULL,
  `id_hosts_usuarios` INT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id_agendamentos_acompanhantes`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuarios_perfis`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios_perfis` (
  `id_usuarios_perfis` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_usuarios_perfis` INT NULL DEFAULT NULL,
  `nome` VARCHAR(255) NULL DEFAULT NULL,
  `id` VARCHAR(255) NULL DEFAULT NULL,
  `padrao` TINYINT NULL DEFAULT NULL,
  `status` CHAR(1) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuarios_perfis`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuarios_perfis_modulos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios_perfis_modulos` (
  `id_usuarios_perfis_modulos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_usuarios_perfis_modulos` INT NULL DEFAULT NULL,
  `perfil` VARCHAR(255) NULL DEFAULT NULL,
  `modulo` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuarios_perfis_modulos`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuarios_perfis_modulos_operacoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios_perfis_modulos_operacoes` (
  `id_usuarios_perfis_modulos_operacoes` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_usuarios_perfis_modulos_operacoes` INT NULL DEFAULT NULL,
  `perfil` VARCHAR(255) NULL DEFAULT NULL,
  `operacao` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuarios_perfis_modulos_operacoes`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
";
}

function atualizar_banco_de_dados(){
	// ===== Ler o SQL do banco de dados.
	
	$bancoSQL = atualizar_banco_de_dados_sql();
	
	// ===== Varrer todas as linhas do SQL cru.
	
	$tabelaAtual = '';
	$tabelaAberta = false;
	$tabelaFechada = false;
	$bancoDeDados['tabelas'] = Array();
	$tabelaAux = Array();
	
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $bancoSQL) as $lineSQL){
		$lineSQL = trim($lineSQL);
		
		if(preg_match('/'.preg_quote('CREATE TABLE IF NOT EXISTS').'/', $lineSQL) > 0){
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