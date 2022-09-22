-- MySQL Script generated by MySQL Workbench
-- Thu Sep 22 10:47:00 2022
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema b2make-escalas
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `escalas_controle`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `escalas_controle` (
  `id_escalas_controle` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_escalas_controle` INT NULL DEFAULT NULL,
  `data` DATE NULL DEFAULT NULL,
  `total` INT NULL DEFAULT NULL,
  `status` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id_escalas_controle`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `escalas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `escalas` (
  `id_escalas` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_escalas` INT NULL DEFAULT NULL,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `mes` INT NULL DEFAULT NULL,
  `ano` INT NULL DEFAULT NULL,
  `status` VARCHAR(255) NULL DEFAULT NULL,
  `pubID` VARCHAR(255) NULL DEFAULT NULL,
  `versao` INT NULL DEFAULT NULL,
  `data_criacao` DATETIME NULL DEFAULT NULL,
  `data_modificacao` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_escalas`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `escalas_datas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `escalas_datas` (
  `id_escalas_datas` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_escalas_datas` INT NULL DEFAULT NULL,
  `id_hosts_escalas` INT NULL DEFAULT NULL,
  `data` DATE NULL DEFAULT NULL,
  `status` VARCHAR(255) NULL DEFAULT NULL,
  `selecionada` TINYINT NULL,
  `selecionada_inscricao` TINYINT NULL,
  `selecionada_confirmacao` TINYINT NULL,
  PRIMARY KEY (`id_escalas_datas`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `escalas_pesos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `escalas_pesos` (
  `id_escalas_pesos` INT NOT NULL AUTO_INCREMENT,
  `id_hosts_escalas_pesos` INT NULL DEFAULT NULL,
  `id_hosts_usuarios` INT NULL DEFAULT NULL,
  `peso` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id_escalas_pesos`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
