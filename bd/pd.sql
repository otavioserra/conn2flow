SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `conteudo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `conteudo` (
  `id_conteudo` INT NOT NULL AUTO_INCREMENT ,
  `id_conteudo_pai` INT NULL ,
  `tipo` CHAR(1) NULL ,
  `identificador` VARCHAR(255) NULL ,
  `titulo` VARCHAR(255) NULL ,
  `titulo_img` VARCHAR(255) NULL ,
  `sub_titulo` VARCHAR(255) NULL ,
  `texto` TEXT NULL ,
  `imagem_pequena` VARCHAR(255) NULL ,
  `imagem_grande` VARCHAR(255) NULL ,
  `musica` VARCHAR(255) NULL ,
  `link_externo` VARCHAR(255) NULL ,
  `data` DATETIME NULL ,
  `hits` INT NULL ,
  `ordem` INT NULL ,
  `status` CHAR(1) NULL ,
  `keywords` VARCHAR(255) NULL ,
  `versao` INT NULL ,
  `sitemap` TINYINT(1)  NULL ,
  `caminho_raiz` VARCHAR(255) NULL ,
  `data_automatica` TINYINT(1)  NULL ,
  `description` TINYTEXT NULL ,
  `titulo_img_name` VARCHAR(100) NULL ,
  `titulo_img_title` VARCHAR(100) NULL ,
  `titulo_img_alt` VARCHAR(100) NULL ,
  `imagem_pequena_name` VARCHAR(100) NULL ,
  `imagem_pequena_title` VARCHAR(100) NULL ,
  `imagem_pequena_alt` VARCHAR(100) NULL ,
  `imagem_grande_name` VARCHAR(100) NULL ,
  `imagem_grande_title` VARCHAR(100) NULL ,
  `imagem_grande_alt` VARCHAR(100) NULL ,
  `galeria` INT NULL ,
  `parametros` INT NULL ,
  `videos_youtube` VARCHAR(255) NULL ,
  `rss` TINYINT(1)  NULL ,
  `rss_redes` TINYINT(1)  NULL ,
  `redes_titulo` VARCHAR(100) NULL ,
  `redes_subtitulo` TINYTEXT NULL ,
  `identificador_auxiliar` VARCHAR(255) NULL ,
  `galeria_grupo` INT NULL ,
  `videos` INT NULL ,
  `videos_grupo` INT NULL ,
  `texto2` TEXT NULL ,
  `conteiner_posicao_x` VARCHAR(10) NULL ,
  `conteiner_posicao_y` VARCHAR(10) NULL ,
  PRIMARY KEY (`id_conteudo`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `conteudo_permissao`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `conteudo_permissao` (
  `id_conteudo_permissao` INT NOT NULL AUTO_INCREMENT ,
  `id_conteudo` INT NULL ,
  `tipo` CHAR(1) NULL ,
  `titulo` TINYINT(1)  NULL ,
  `titulo_img` TINYINT(1)  NULL ,
  `sub_titulo` TINYINT(1)  NULL ,
  `texto` TINYINT(1)  NULL ,
  `imagem_pequena` TINYINT(1)  NULL ,
  `imagem_grande` TINYINT(1)  NULL ,
  `musica` TINYINT(1)  NULL ,
  `link_externo` TINYINT(1)  NULL ,
  `data` TINYINT(1)  NULL ,
  `identificador` TINYINT(1)  NULL ,
  `keywords` TINYINT(1)  NULL ,
  `addthis` TINYINT(1)  NULL ,
  `no_robots` TINYINT(1)  NULL ,
  `no_search` TINYINT(1)  NULL ,
  `galeria` TINYINT(1)  NULL ,
  `parametros` TINYINT(1)  NULL ,
  `layout_status` TINYINT(1)  NULL ,
  `layout` TEXT NULL ,
  `no_layout` TINYINT(1)  NULL ,
  `galeria_todas` TINYINT(1)  NULL ,
  `videos_youtube` TINYINT(1)  NULL ,
  `conteudos_relacionados` TINYINT(1)  NULL ,
  `menu_principal` TINYINT(1)  NULL ,
  `identificador_auxiliar` TINYINT(1)  NULL ,
  `galeria_grupo` TINYINT(1)  NULL ,
  `videos` TINYINT(1)  NULL ,
  `videos_grupo` TINYINT(1)  NULL ,
  `videos_todas` TINYINT(1)  NULL ,
  `texto2` TINYINT(1)  NULL ,
  `titulo_img_width` VARCHAR(5) NULL ,
  `titulo_img_height` VARCHAR(5) NULL ,
  `titulo_img_recorte_y` TINYINT(1)  NULL ,
  `titulo_img_filters` VARCHAR(255) NULL ,
  `titulo_img_mask` VARCHAR(100) NULL ,
  `imagem_pequena_width` VARCHAR(5) NULL ,
  `imagem_pequena_height` VARCHAR(5) NULL ,
  `imagem_pequena_recorte_y` TINYINT(1)  NULL ,
  `imagem_pequena_filters` VARCHAR(255) NULL ,
  `imagem_pequena_mask` VARCHAR(100) NULL ,
  `imagem_grande_width` VARCHAR(5) NULL ,
  `imagem_grande_height` VARCHAR(5) NULL ,
  `imagem_grande_recorte_y` TINYINT(1)  NULL ,
  `imagem_grande_filters` VARCHAR(255) NULL ,
  `imagem_grande_mask` VARCHAR(100) NULL ,
  `conteiner_posicao` TINYINT(1)  NULL ,
  `conteiner_posicao_efeito` VARCHAR(30) NULL ,
  `conteiner_posicao_tempo` VARCHAR(10) NULL ,
  `navegacao` VARCHAR(100) NULL ,
  PRIMARY KEY (`id_conteudo_permissao`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuario_perfil`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `usuario_perfil` (
  `id_usuario_perfil` INT NOT NULL AUTO_INCREMENT ,
  `status` CHAR(1) NULL ,
  `nome` VARCHAR(100) NULL ,
  PRIMARY KEY (`id_usuario_perfil`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuario`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `usuario` (
  `id_usuario` INT NOT NULL AUTO_INCREMENT ,
  `id_usuario_perfil` INT NOT NULL ,
  `status` CHAR(1) NULL ,
  `usuario` VARCHAR(100) NULL ,
  `senha` VARCHAR(100) NULL ,
  `email` VARCHAR(255) NULL ,
  `nome` VARCHAR(20) NULL ,
  `sobrenome` VARCHAR(100) NULL ,
  `cep` VARCHAR(10) NULL ,
  `endereco` VARCHAR(100) NULL ,
  `numero` VARCHAR(6) NULL ,
  `complemento` VARCHAR(30) NULL ,
  `bairro` VARCHAR(50) NULL ,
  `cidade` VARCHAR(50) NULL ,
  `uf` CHAR(2) NULL ,
  `telefone` VARCHAR(14) NULL ,
  `celular` VARCHAR(14) NULL ,
  `data_cadastro` DATETIME NULL ,
  `data_login` DATETIME NULL ,
  PRIMARY KEY (`id_usuario`, `id_usuario_perfil`) ,
  INDEX `fk_usuario_usuario_perfil` (`id_usuario_perfil` ASC) ,
  CONSTRAINT `fk_usuario_usuario_perfil`
    FOREIGN KEY (`id_usuario_perfil` )
    REFERENCES `usuario_perfil` (`id_usuario_perfil` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `variavel_global`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `variavel_global` (
  `id_variavel_global` INT NOT NULL AUTO_INCREMENT ,
  `grupo` VARCHAR(100) NULL ,
  `variavel` VARCHAR(100) NULL ,
  `valor` TEXT NULL ,
  `tipo` VARCHAR(10) NULL ,
  `descricao` TEXT NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_variavel_global`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `modulo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `modulo` (
  `id_modulo` INT NOT NULL AUTO_INCREMENT ,
  `status` CHAR(1) NULL ,
  `id_modulo_pai` INT NULL ,
  `nome` VARCHAR(100) NULL ,
  `caminho` VARCHAR(100) NULL ,
  `titulo` VARCHAR(100) NULL ,
  `imagem` VARCHAR(100) NULL ,
  `ordem` INT NULL ,
  `descricao` TEXT NULL ,
  PRIMARY KEY (`id_modulo`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuario_perfil_modulo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `usuario_perfil_modulo` (
  `id_usuario_perfil` INT NOT NULL ,
  `id_modulo` INT NOT NULL ,
  PRIMARY KEY (`id_usuario_perfil`, `id_modulo`) ,
  INDEX `fk_usuario_perfil_modulo_usuario_perfil1` (`id_usuario_perfil` ASC) ,
  INDEX `fk_usuario_perfil_modulo_modulo1` (`id_modulo` ASC) ,
  CONSTRAINT `fk_usuario_perfil_modulo_usuario_perfil1`
    FOREIGN KEY (`id_usuario_perfil` )
    REFERENCES `usuario_perfil` (`id_usuario_perfil` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_perfil_modulo_modulo1`
    FOREIGN KEY (`id_modulo` )
    REFERENCES `modulo` (`id_modulo` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `modulo_operacao`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `modulo_operacao` (
  `id_modulo_operacao` INT NOT NULL AUTO_INCREMENT ,
  `id_modulo` INT NOT NULL ,
  `nome` VARCHAR(100) NULL ,
  `caminho` VARCHAR(100) NULL ,
  `descricao` TEXT NULL ,
  PRIMARY KEY (`id_modulo_operacao`, `id_modulo`) ,
  INDEX `fk_modulo_operacao_modulo1` (`id_modulo` ASC) ,
  CONSTRAINT `fk_modulo_operacao_modulo1`
    FOREIGN KEY (`id_modulo` )
    REFERENCES `modulo` (`id_modulo` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuario_perfil_modulo_operacao`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `usuario_perfil_modulo_operacao` (
  `id_usuario_perfil` INT NOT NULL ,
  `id_modulo_operacao` INT NOT NULL ,
  `id_modulo` INT NOT NULL ,
  PRIMARY KEY (`id_usuario_perfil`, `id_modulo_operacao`, `id_modulo`) ,
  INDEX `fk_usuario_perfil_modulo_operacao_usuario_perfil1` (`id_usuario_perfil` ASC) ,
  INDEX `fk_usuario_perfil_modulo_operacao_modulo_operacao1` (`id_modulo_operacao` ASC, `id_modulo` ASC) ,
  CONSTRAINT `fk_usuario_perfil_modulo_operacao_usuario_perfil1`
    FOREIGN KEY (`id_usuario_perfil` )
    REFERENCES `usuario_perfil` (`id_usuario_perfil` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_perfil_modulo_operacao_modulo_operacao1`
    FOREIGN KEY (`id_modulo_operacao` , `id_modulo` )
    REFERENCES `modulo_operacao` (`id_modulo_operacao` , `id_modulo` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `bad_list`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `bad_list` (
  `id_bad_list` INT NOT NULL AUTO_INCREMENT ,
  `ip` VARCHAR(15) NULL ,
  `num_tentativas_login` INT NULL ,
  `data_primeira_tentativa` DATETIME NULL ,
  PRIMARY KEY (`id_bad_list`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `grupo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `grupo` (
  `id_grupo` INT NOT NULL AUTO_INCREMENT ,
  `status` CHAR(1) NULL ,
  `nome` VARCHAR(100) NULL ,
  `descricao` TEXT NULL ,
  PRIMARY KEY (`id_grupo`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuario_grupo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `usuario_grupo` (
  `id_usuario` INT NOT NULL ,
  `id_usuario_perfil` INT NOT NULL ,
  `id_grupo` INT NOT NULL ,
  PRIMARY KEY (`id_usuario`, `id_usuario_perfil`, `id_grupo`) ,
  INDEX `fk_usuario_grupo_usuario1` (`id_usuario` ASC, `id_usuario_perfil` ASC) ,
  INDEX `fk_usuario_grupo_grupo1` (`id_grupo` ASC) ,
  CONSTRAINT `fk_usuario_grupo_usuario1`
    FOREIGN KEY (`id_usuario` , `id_usuario_perfil` )
    REFERENCES `usuario` (`id_usuario` , `id_usuario_perfil` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_grupo_grupo1`
    FOREIGN KEY (`id_grupo` )
    REFERENCES `grupo` (`id_grupo` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `galerias`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `galerias` (
  `id_galerias` INT NOT NULL AUTO_INCREMENT ,
  `nome` VARCHAR(100) NULL ,
  `descricao` TEXT NULL ,
  `data` DATETIME NULL ,
  `status` CHAR(1) NULL ,
  `grupo` INT NULL ,
  `identificador` VARCHAR(100) NULL ,
  PRIMARY KEY (`id_galerias`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `imagens`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `imagens` (
  `id_imagens` INT NOT NULL AUTO_INCREMENT ,
  `id_galerias` INT NOT NULL ,
  `local_original` VARCHAR(100) NULL ,
  `local_grande` VARCHAR(100) NULL ,
  `local_media` VARCHAR(100) NULL ,
  `local_mini` VARCHAR(100) NULL ,
  `descricao` TEXT NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_imagens`, `id_galerias`) ,
  INDEX `fk_imagens_galerias1` (`id_galerias` ASC) ,
  CONSTRAINT `fk_imagens_galerias1`
    FOREIGN KEY (`id_galerias` )
    REFERENCES `galerias` (`id_galerias` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `upload_permissao`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `upload_permissao` (
  `id_upload_permissao` INT NOT NULL AUTO_INCREMENT ,
  `usuario` VARCHAR(100) NULL ,
  `session_id` VARCHAR(100) NULL ,
  `data` DATETIME NULL ,
  PRIMARY KEY (`id_upload_permissao`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `contatos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `contatos` (
  `id_contatos` INT NOT NULL AUTO_INCREMENT ,
  `nome` VARCHAR(100) NULL ,
  `email` VARCHAR(100) NULL ,
  `mensagem` TEXT NULL ,
  `data` DATETIME NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_contatos`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `emails`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `emails` (
  `id_emails` INT NOT NULL AUTO_INCREMENT ,
  `nome` VARCHAR(100) NULL ,
  `email` VARCHAR(100) NULL ,
  `opt_in` DATETIME NULL ,
  `opt_out` DATETIME NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_emails`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `email_markenting`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `email_markenting` (
  `id_email_markenting` INT NOT NULL AUTO_INCREMENT ,
  `assunto` VARCHAR(255) NULL ,
  `imagem` VARCHAR(255) NULL ,
  `imagem_url` VARCHAR(255) NULL ,
  `imagem_tabela` TEXT NULL ,
  `texto` TEXT NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_email_markenting`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `parametros`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parametros` (
  `id_parametros` INT NOT NULL AUTO_INCREMENT ,
  `local` VARCHAR(100) NULL ,
  `grupo` VARCHAR(20) NULL ,
  `param` VARCHAR(100) NULL ,
  `valor` TEXT NULL ,
  `tipo` VARCHAR(10) NULL ,
  `descricao` TEXT NULL ,
  PRIMARY KEY (`id_parametros`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `galerias_grupos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `galerias_grupos` (
  `id_galerias_grupos` INT NOT NULL AUTO_INCREMENT ,
  `grupo` VARCHAR(100) NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_galerias_grupos`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `pastas_usuarios`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `pastas_usuarios` (
  `id_pastas_usuarios` INT NOT NULL AUTO_INCREMENT ,
  `nome` VARCHAR(100) NULL ,
  `caminho` VARCHAR(255) NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_pastas_usuarios`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `usuario_pasta`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `usuario_pasta` (
  `id_usuario_pasta` INT NOT NULL AUTO_INCREMENT ,
  `id_usuario` INT NOT NULL ,
  `id_pastas_usuarios` INT NOT NULL ,
  PRIMARY KEY (`id_usuario_pasta`, `id_usuario`, `id_pastas_usuarios`) ,
  INDEX `fk_usuario_pasta_usuario1` (`id_usuario` ASC) ,
  INDEX `fk_usuario_pasta_pastas_usuarios1` (`id_pastas_usuarios` ASC) ,
  CONSTRAINT `fk_usuario_pasta_usuario1`
    FOREIGN KEY (`id_usuario` )
    REFERENCES `usuario` (`id_usuario` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_pasta_pastas_usuarios1`
    FOREIGN KEY (`id_pastas_usuarios` )
    REFERENCES `pastas_usuarios` (`id_pastas_usuarios` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mensagens`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mensagens` (
  `id_mensagens` INT NOT NULL AUTO_INCREMENT ,
  `id_usuario` INT NOT NULL ,
  `para` VARCHAR(100) NULL ,
  `assunto` VARCHAR(255) NULL ,
  `mensagem` TEXT NULL ,
  `data` DATETIME NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_mensagens`, `id_usuario`) ,
  INDEX `fk_mensagens_usuario1` (`id_usuario` ASC) ,
  CONSTRAINT `fk_mensagens_usuario1`
    FOREIGN KEY (`id_usuario` )
    REFERENCES `usuario` (`id_usuario` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mensagens_grupo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mensagens_grupo` (
  `id_mensagens` INT NOT NULL ,
  `id_grupo` INT NOT NULL ,
  PRIMARY KEY (`id_mensagens`, `id_grupo`) ,
  INDEX `fk_mensagens_grupo_grupo1` (`id_grupo` ASC) ,
  INDEX `fk_mensagens_grupo_mensagens1` (`id_mensagens` ASC) ,
  CONSTRAINT `fk_mensagens_grupo_mensagens1`
    FOREIGN KEY (`id_mensagens` )
    REFERENCES `mensagens` (`id_mensagens` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_mensagens_grupo_grupo1`
    FOREIGN KEY (`id_grupo` )
    REFERENCES `grupo` (`id_grupo` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mensagens_usuario`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mensagens_usuario` (
  `id_mensagens` INT NOT NULL ,
  `id_usuario` INT NOT NULL ,
  PRIMARY KEY (`id_mensagens`, `id_usuario`) ,
  INDEX `fk_mensagens_usuario_usuario1` (`id_usuario` ASC) ,
  INDEX `fk_mensagens_usuario_mensagens1` (`id_mensagens` ASC) ,
  CONSTRAINT `fk_mensagens_usuario_mensagens1`
    FOREIGN KEY (`id_mensagens` )
    REFERENCES `mensagens` (`id_mensagens` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_mensagens_usuario_usuario1`
    FOREIGN KEY (`id_usuario` )
    REFERENCES `usuario` (`id_usuario` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `grupo_pasta`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `grupo_pasta` (
  `id_grupo_pasta` INT NOT NULL AUTO_INCREMENT ,
  `id_grupo` INT NOT NULL ,
  `id_pastas_usuarios` INT NOT NULL ,
  PRIMARY KEY (`id_grupo_pasta`, `id_grupo`, `id_pastas_usuarios`) ,
  INDEX `fk_grupo_pastas_usuarios_pastas_usuarios1` (`id_pastas_usuarios` ASC) ,
  INDEX `fk_grupo_pastas_usuarios_grupo1` (`id_grupo` ASC) ,
  CONSTRAINT `fk_grupo_pastas_usuarios_grupo1`
    FOREIGN KEY (`id_grupo` )
    REFERENCES `grupo` (`id_grupo` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_grupo_pastas_usuarios_pastas_usuarios1`
    FOREIGN KEY (`id_pastas_usuarios` )
    REFERENCES `pastas_usuarios` (`id_pastas_usuarios` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `videos_grupos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `videos_grupos` (
  `id_videos_grupos` INT NOT NULL AUTO_INCREMENT ,
  `grupo` VARCHAR(100) NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_videos_grupos`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `galerias_videos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `galerias_videos` (
  `id_galerias_videos` INT NOT NULL AUTO_INCREMENT ,
  `nome` VARCHAR(100) NULL ,
  `descricao` TEXT NULL ,
  `data` DATETIME NULL ,
  `status` CHAR(1) NULL ,
  `grupo` INT NULL ,
  `identificador` VARCHAR(100) NULL ,
  PRIMARY KEY (`id_galerias_videos`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `videos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `videos` (
  `id_videos` INT NOT NULL AUTO_INCREMENT ,
  `id_galerias_videos` INT NOT NULL ,
  `descricao` TEXT NULL ,
  `codigo` VARCHAR(30) NULL ,
  `imagem_original` VARCHAR(100) NULL ,
  `imagem_grande` VARCHAR(100) NULL ,
  `imagem_media` VARCHAR(100) NULL ,
  `imagem_mini` VARCHAR(100) NULL ,
  `status` CHAR(1) NULL ,
  PRIMARY KEY (`id_videos`, `id_galerias_videos`) ,
  INDEX `fk_videos_galerias_videos1` (`id_galerias_videos` ASC) ,
  CONSTRAINT `fk_videos_galerias_videos1`
    FOREIGN KEY (`id_galerias_videos` )
    REFERENCES `galerias_videos` (`id_galerias_videos` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `usuario_perfil`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO usuario_perfil (`id_usuario_perfil`, `status`, `nome`) VALUES (1, NULL, 'Administrador');
INSERT INTO usuario_perfil (`id_usuario_perfil`, `status`, `nome`) VALUES (2, 'A', 'Cadastrados no Portal');

COMMIT;

-- -----------------------------------------------------
-- Data for table `usuario`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO usuario (`id_usuario`, `id_usuario_perfil`, `status`, `usuario`, `senha`, `email`, `nome`, `sobrenome`, `cep`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `telefone`, `celular`, `data_cadastro`, `data_login`) VALUES (1, 1, 'A', 'admin', '$1$c//./m3.$rWu0Nb2vV/j.ydedtUCnM.', NULL, 'Administrador', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

COMMIT;

-- -----------------------------------------------------
-- Data for table `variavel_global`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'keywords', NULL, 'text', 'Conjunto de palavras separadas por vírgulas que definem quais palavras ou frases os buscadores deve atribuir ao conteúdo geral do portal.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'description', NULL, 'text', 'Descrição geral do portal de internet, é o que aparece logo a seguir do endereço de internet nos buscadores.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'ATIVO', '1', 'bool', 'Ativar ou desativar o sistema como um todo. Mudando essa opção para inativo, o sistema não mais funcionará.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'PASS', '$1$xp..mF..$vwYNfLISBOcypiJHhxWGK0', 'string', 'Senha de acesso master ao sistema. Através dessa senha é possível reativar o sistema desativado por acidente.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'EMAIL', NULL, 'bool', 'Ativar ou desativar o envio de e-mails pelo sistema.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'ID', 'id', 'string', 'Nome do sistema.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'SESSION_TIME_OUT', '180', 'int', 'Tempo que a sessão dos usuários ficará ativa. Tempo máximo que um usuário pode ficar inativo no sistema.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'ADMIN_EMAIL', 'webmaster@prontofatto.com.br', 'string', 'E-mail do contato técnico.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'USUARIO_STATUS', 'A', 'status', 'Cadastro do usuário no painel administrativo. -  Opções: - B = Bloqueado - A = Ativo.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'EMAIL_NOME', 'nome email', 'string', 'Nome do contato do remetente de e-mail do sistema.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'EMAIL_ENDERECO', 'mailing@dominio', 'string', 'Endereço de e-mail do contato remetente de e-mail do sistema.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'EMAIL_POR_HORA', '300', 'int', 'Quantidade de e-mail permitidos por hora por conta de e-mail.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'LOGIN_MAX_TENTATIVAS', '10', 'int', 'Número de vezes que um usuário pode tentar acessar o sistema sem que sua conta seja bloqueada por um período de tempo.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'LOGIN_BAD_LIST_PERIODO_SEGUNDOS', '600', 'int', 'Tempo em segundos que um conta fica desativada no sistema quando ultrapassa o limite de tentativas de login.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'CADASTRO_STATUS', 'B', 'status', 'Status da conta dos usuários cadastrados no sistema. -  Opções: - B = Bloqueado - A = Ativo.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'CEP_SEARCH', NULL, 'bool', 'Ativação ou desativação da opção automática do endereço com a entrada do CEP. PS.: É necessário a instalação da base de dados dos muníicípios do Brasil.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'ICONS', 'images/icons/', 'string', 'Caminho relativo a raiz do sistema onde estão localizados os ícones do sistema.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'PADRAO_CSS', 'includes/css/padrao.css', 'string', 'Caminho relativo a raiz do sistema onde está localizado o CSS padrão.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'LAYOUT_CSS', 'files/projeto/layout-padrao.css', 'string', 'Caminho relativo a raiz do sistema onde está localizado o CSS layout.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'PADRAO_CSS_ADMIN', 'includes/css/padrao2.css', 'string', 'Caminho relativo a raiz do sistema onde está localizado o CSS padrão da área restrita.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'LAYOUT_CSS_ADMIN', 'includes/css/layout2.css', 'string', 'Caminho relativo a raiz do sistema onde está localizado o CSS layout da área restrita.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'PADRAO_JS', 'includes/js/padrao.js', 'string', 'Caminho relativo a raiz do sistema onde está localizado o Java Script padrão do sistema.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'JQUERY', 'includes/js/jQuery/jquery.min.js', 'string', 'Caminho relativo a raiz do sistema onde está localizado o JQUERY padrão do sistema.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'TITULO', 'Título', 'string', 'Título principal de todas as páginas.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'SUB_TITULO', 'Sub-Título', 'string', 'Sub-título da página inicial do sistema.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'TITULO_SEPARADOR', ' - ', 'string', 'Separador entre o Título, Sub-título de todas as páginas.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'MENU_NUM_PAGINAS', '20', 'int', 'Número de itens por página das listagens.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'CSS_PATH', 'includes/css/', 'string', 'Caminho relativo a raiz do sistema onde está localizado os CSSs.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'JS_PATH', 'includes/js/', 'string', 'Caminho relativo a raiz do sistema onde está localizado os JSs.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'INSTALL', '1', 'bool', 'Ativar ou Desativar a instalação do sistema', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'ADMIN_WIDTH', '1000', 'int', 'Largura do layout do admin.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'SMTP_USER', 'sistema', 'string', 'Email usado pelo sistema para enviar mensagens do sistema. É necessário cadastrar a conta sistema@dominio no servidor para que o sistema de envio automático de e-mails funcione.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'SMTP_PASS', NULL, 'string', 'Senha do email usado pelo sistema. É necessário cadastrar a conta sistema@dominio no servidor para que o sistema de envio automático de e-mails funcione.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'CONTATO_NOME', 'Contato', 'string', 'Nome do contato que receberá uma cópia dos contatos efetuados no portal', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'CONTATO_EMAIL', 'contato@mail.com', 'string', 'Email do contato que receberá uma cópia dos contatos efetuados no portal', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'PERMISSAO_ARQUIVOS', '0777', 'int', 'Permissão usada para gravar os arquivos no sistema', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'EMAIL_MARKENTING_IMG_WIDTH', '800', 'int', 'Valor máximo da largura das imagens dos E-mail Markentings', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'MAILER', 'B', 'status', 'Verifica se o sistema de newsletter está ou não enviando os e-mails.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'MAILER_PORT', '587', 'int', 'Porta de SMTP do sistema de newsletter.', 'B');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'MAILER_NUM_EMAIL', '0', 'int', 'Contador do número de e-mails enviados pelo sistema de email markenting', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'MAILER_NEWSLETTER', '0', 'int', 'Identificador da newsletter sendo enviada pelo mailer.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'MAILER_ASSINATURA', NULL, 'tinymce', 'Assinatura que vai no final de cada e-mail enviado pelo sistema.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'author', 'AgeOne Digital Marketing, www.ageone.com.br', 'string', 'Define o author do portal.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'Content-Language', 'pt-br', 'string', 'Sigla da linguagem padrão. Para português do Brasil: pt-br', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'geo.position', '-21.1776;-47.8063', 'string', 'Posição global do local, latitude e longitude, exemplo para Ribeirão Preto: -21.1776;-47.8063', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'geo.country', 'BR', 'string', 'Sigla do país, Brasil fica: BR', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'geo.region', 'BR-SP', 'string', 'Sigla da região, São Paulo por exemplo fica: BR-SP', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'geo.placename', 'Ribeirao Preto', 'string', 'Nome da cidade por extenso.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'google-site-verification', NULL, 'string', 'Código de verificação do google. Exemplo: q05Dxd6ttFnLXzXUqx6KIQ8O1lIp3WJTihS13U-uNiY', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'Language', 'portuguese', 'string', 'Linguagem do portal.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html_meta', 'robots', 'index, follow', 'string', 'Regra para os robots de pesquisa.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'google-analytics', NULL, 'string', 'Código do site no google analytics. Exemplo: UA-17582349-1', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'EMAIL_MARKENTING_EXCLUIR_IMG', NULL, 'bool', 'Definir se exclui ou não as imagens do E-mail Markenting quando os mesmos são excluídos do sistema. Se excluir as imagens, quaisquer e-mail markenting enviados as imagens que forem excluídas não aparecerão mais.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'DEBUG', NULL, 'bool', 'Define se o sistema está em modo DEBUG ou não.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'IMG_MINI_WIDTH', '75', 'int', 'Define a largura em pixels das imagens pequenas geradas automaticamente no módulo de galeria.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'IMG_MINI_HEIGHT', '75', 'int', 'Define a altura em pixels das imagens pequenas geradas automaticamente no módulo de galeria.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'IMG_MEDIA_WIDTH', '350', 'int', 'Define a largura em pixels das imagens médias geradas automaticamente no módulo de galeria.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'IMG_MEDIA_HEIGHT', '200', 'int', 'Define a altura em pixels das imagens médias geradas automaticamente no módulo de galeria.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'IMG_GRANDE_WIDTH', '550', 'int', 'Define a largura em pixels das imagens grandes geradas automaticamente no módulo de galeria.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'IMG_GRANDE_HEIGHT', '502', 'int', 'Define a altura em pixels das imagens grandes geradas automaticamente no módulo de galeria.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'TEMA', 'padrao', 'string', 'Define o nome do tema que será usado, esse nome é o nome do diretório dentro da pasta de temas que será carregado pelo sistema como tema padrão das páginas públicas.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'ADD-THIS', '<!-- AddThis Button BEGIN -->\n<div class=\"addthis_toolbox addthis_default_style \">\n<a class=\"addthis_button_facebook_like\" fb:like:layout=\"button_count\"></a>\n<a class=\"addthis_button_tweet\"></a>\n<a class=\"addthis_button_google_plusone\" g:plusone:size=\"medium\"></a>\n</div>\n<!-- AddThis Button END -->', 'text', 'Código do Add-this que será usado para compartilhar as páginas dos conteúdos.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'MAILER_INTERACOES', '25', 'int', 'Quantidade de e-mails enviados a cada 15 minutos pelo eMarkenting', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'MAILER_MENSAGEM', NULL, 'string', 'Mensagens geradas pelo Mailer', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'JQUERY-MOBILE', 'includes/js/jquery.mobile/jquery.mobile-1.0b1.js', 'string', 'Local do jQuery Mobile', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'JQUERY-MOBILE-CSS', 'includes/js/jquery.mobile/jquery.mobile-1.0b1.css', 'string', 'Local do jQuery Mobile Css', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'PADRAO_JS_MOBILE', 'includes/js/padrao-mobile.js', 'string', 'Local do JS Padrao do Mobile', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'PROCURAR_IMG_WIDTH', '150', 'int', 'Define o tamanho da largura das mini imagens que aparecem nas procuras quando o conteúdo tem imagem.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'PROCURAR_IMG_HEIGHT', '150', 'int', 'Define o tamanho da altura das mini imagens que aparecem nas procuras quando o conteúdo tem imagem.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'layouts', 'LAYOUTS_VERSAO', '1', 'int', 'Versão do layout', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'html', 'PAGINA_404_BRANCA', NULL, 'bool', 'Escolher se a fonte da imagem da página não encontrada séra branca. O padrão é preto.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'DATA_FORMAT_PADRAO', 'D/ME/A HhMI', 'string', 'Formato padrão das datas / hora do sistema.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'ROBO_INT', '24H,30M,30M', 'string', 'Intervalo de tempo em que o robo repetirá a execução respectivamente do sitemap, rss e rss de redes sociais separados por vírgulas. Aceito M - minutos, H - horas e D - dias.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'ROBO_ULT', NULL, 'string', 'Última execução de cada um dos processos que o robo executou', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'LAYOUT_PRINCIPAL', NULL, 'bool', 'Ativar o layout principal do portal. Essa opção é desativada por padrão e quando está nesse estado um layout provisório é utilizado no lugar do layout do projeto.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'system', 'PRIMEIRA_EXECUCAO', '1', 'bool', 'Condição para que o CMS reconheça que está sendo executado pela primeira vez e execute as tarefas preliminares.', 'O');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'recaptcha', 'PUBLIC_KEY', '6Ld11c4SAAAAAHGLWunAMq9_ObhdmRO0WDf13-qc', 'string', 'Chave pública do reCAPTCHA', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'recaptcha', 'PRIVATE_KEY', '6Ld11c4SAAAAADlpmGSu570o41KIQ5O5UGh1LvhF', 'string', 'Chave privada do reCAPTCHA', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'videos', 'IMG_MINI_WIDTH', '75', 'int', 'Define a largura em pixels das imagens pequenas geradas automaticamente no módulo de vídeos.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'videos', 'IMG_MINI_HEIGHT', '75', 'int', 'Define a altura em pixels das imagens pequenas geradas automaticamente no módulo de vídeos.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'videos', 'IMG_MEDIA_WIDTH', '350', 'int', 'Define a largura em pixels das imagens médias geradas automaticamente no módulo de vídeos.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'videos', 'IMG_MEDIA_HEIGHT', '200', 'int', 'Define a altura em pixels das imagens médias geradas automaticamente no módulo de vídeos.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'videos', 'IMG_GRANDE_WIDTH', '550', 'int', 'Define a largura em pixels das imagens grandes geradas automaticamente no módulo de vídeos.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'videos', 'IMG_GRANDE_HEIGHT', '420', 'int', 'Define a altura em pixels das imagens grandes geradas automaticamente no módulo de vídeos.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'ftp', 'host', 'localhost', 'string', 'Define o host do Servidor FTP.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'ftp', 'usuario', NULL, 'string', 'Define o usuário do Servidor FTP.', 'A');
INSERT INTO variavel_global (`id_variavel_global`, `grupo`, `variavel`, `valor`, `tipo`, `descricao`, `status`) VALUES (NULL, 'ftp', 'senha', NULL, 'pass', 'Define a senha do Servidor FTP.', 'A');

COMMIT;

-- -----------------------------------------------------
-- Data for table `modulo`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (1, 'A', NULL, 'Configurações', '', 'Configuração do Sistema', NULL, NULL, 'Grupo de módulos do sistema relacionados com operações internas do sistema. Como por exemplo a criação de usuários do sistema, a definição de parâmetros gerais do sistema, etc.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (5, 'A', 2, 'Conteúdos', 'conteudo', 'Conteúdos do Portal', 'textos_inicio.png', 1, 'Móddulo do responsável por modificar os conteúdos de páginas em geral.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (3, 'A', 1, 'Usuários', 'usuarios', 'Usuários do Sistema', 'usuarios.png', 1, 'Módulo do sistema responsável por gerenciar os usuários do sistema.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (4, 'A', 1, 'Preferências', 'preferencias', 'Preferências do Sistema', 'preferencias.png', 4, 'Módulo do sistema responsável por gerenciar as preferências do sistema. Através desse módulo é possível definir os parâmetros gerais tanto do sistema, quanto das páginas html.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (2, 'A', NULL, 'Ferramentas', '', 'Ferramentas do Sistema', NULL, NULL, 'Grupo de Módulos do sistema relacionados com o portal de internet. Nesse grupo estão os módulos responsáveis por informações do portal. Como por exemplo há o módulo conteúdos, no qual pode-se definir o conteúdo das páginas html.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (6, 'A', 1, 'Sair', '../logout/', 'Sair do Sistema', 'exit.png', 999, 'Botão de Sair');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (7, 'A', 1, 'Perfis', 'perfis', 'Perfis de Usuários', 'perfis.png', 3, 'Módulo do sistema que define os perfis de usuários do sistema. Nesse módulo que se define em quais lugares do sistema um usuário e/ou um grupo de usuários podem acessar, bem como quais operações podem ser feitas pelos usuários.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (8, 'A', 2, 'Galerias de Imagens', 'galerias', 'Galerias de Imagens', 'portfolio.png', 2, 'Módulo do sistema que permite a criação de galerias de imagens, bem como o envio e gerenciamento de imagens para o servidor.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (9, 'A', 2, 'Contatos', 'contatos', 'Contatos Efetuados no Portal', 'contato.png', 5, 'Módulo do sistema que permite o acesso a todos os contatos feitos no portal.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (10, 'I', 2, 'eMarketing', 'email_markenting', 'Sistema de Email Marketing', 'email_markenting.png', 8, 'Módulo do sistema responsável por gerenciar a criação de e-mail marketing, bem como o envio e acompanhamento dos envios dos e-mails marketings.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (11, 'A', 2, 'Arquivos', 'uploads', 'Gerenciador de Arquivos', 'uploads.png', 6, 'Módulo do sistema responsável pelo gerenciamento de arquivos no servidor. É possível enviar arquivos, localizar arquivos, bem como deletar os arquivos do sistema.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (12, 'A', 1, 'Grupos', 'grupos', 'Grupos de Usuários', 'grupos.png', 2, 'Módulo do sistema responsável pelo gerenciamento dos grupos de usuários do sistema.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (13, 'A', 2, 'E-mails', 'emails', 'Lista de E-mails', 'emails.png', 9, 'Módulo do sistema responsável pelo gerenciamento dos e-mails do sistema.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (14, 'A', 2, 'Downloads', 'downloads', 'Downloads', 'downloads.png', 7, 'Módulo do sistema para downloads de arquivos. A pasta padrão deve ser definida pelo administrador.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (15, 'I', 2, 'Mensagens', 'mensagens', 'Mensagens', 'mensagens.png', 11, 'Módulo do sistema para envio e recebimento de mensagens entre os usuários do sistema.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (16, 'A', 2, 'Layouts', 'layouts', 'Layouts', 'layouts.png', 4, 'Módulo do sistema para modificação das páginas de layout do sistema juntamente com o CSS.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (17, 'I', 2, 'Parâmetros', 'parametros', 'Parâmetros de Conteúdos', 'parametros.png', 10, 'Módulo do sistema para criação de parâmetros extras de conteúdos.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (18, 'I', 1, 'Update', 'update', 'Update do Sistema', 'update.png', 5, 'Módulo do sistema para atualizar manualmente todo o sistema.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (19, 'A', 2, 'Addthis', 'addthis', 'Addthis', 'addthis.png', 12, 'Módulo de modificação do Addthis no sistema');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (20, 'I', 2, 'reCAPTCHA', 'recaptcha', 'reCAPTCHA', 'recaptcha.png', 13, 'Móduloo de modificação do reCAPTCHA');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (21, 'A', 2, 'Galerias de Vídeos', 'videos', 'Galerias de Vídeos', 'videos.png', 3, 'Módulo do sistema que permite a criação de galerias de vídeos do Youtube.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (22, 'A', 1, 'Servidor FTP', 'ftp', 'Servidor FTP', 'ftp.png', 6, 'Módulo do sistema de configuração do Servidor de FTP.');
INSERT INTO modulo (`id_modulo`, `status`, `id_modulo_pai`, `nome`, `caminho`, `titulo`, `imagem`, `ordem`, `descricao`) VALUES (23, 'I', 2, 'Redes Sociais', 'redes_sociais', 'Redes Sociais nas Postagens dos Conteúdos', 'redes_sociais.png', 14, 'Módulo do sistema que permite a publicação dos conteúdos nas redes sociais.');

COMMIT;

-- -----------------------------------------------------
-- Data for table `modulo_operacao`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (1, 3, 'Adicionar', 'adicionar', 'Permissão para poder adicionar novos usuários ao sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (2, 3, 'Editar', 'editar', 'Permissão para editar os usuários cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (3, 3, 'Excluir', 'excluir', 'Permissão para excluir os usuários cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (4, 3, 'Buscar', 'buscar', 'Permissão para poder buscar usuários cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (5, 3, 'Ver', 'ver', 'Permissão para visualização dos usuários cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (6, 4, 'Ver', 'ver', 'Permissão para visualizar as preferências do sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (7, 4, 'Editar', 'editar', 'Permissão para editar as preferências do sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (8, 4, 'Mostrar Todas', 'mostrar_todas', 'Permissão para mostrar todos os parâmetros do sistema, inclusive os ocultos.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (9, 7, 'Adicionar', 'adicionar', 'Permissão para adicionar novos perfis no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (10, 7, 'Editar', 'editar', 'Permissão para editar perfis cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (11, 7, 'Excluir', 'excluir', 'Permissão para excluir perfis cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (12, 7, 'Buscar', 'buscar', 'Permissão para buscar perfis cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (13, 7, 'Ver', 'ver', 'Permissão para poder visualizar os perfis cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (14, 3, 'Bloquear', 'bloquear', 'Permissão para ativar/desativar usuários cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (15, 7, 'Bloquear', 'bloquear', 'Permissão para ativar/desativar perfis cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (16, 3, 'Grupos', 'grupos', 'Permissão para poder modificar os grupos dos usuários do sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (17, 12, 'Adicionar', 'adicionar', 'Permissão para adicionar novos grupos no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (18, 12, 'Editar', 'editar', 'Permissão para editar grupos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (19, 12, 'Excluir', 'excluir', 'Permissão para excluir grupos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (20, 12, 'Buscar', 'buscar', 'Permissão para buscar grupos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (21, 12, 'Ver', 'ver', 'Permissão para ver grupos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (22, 12, 'Bloquear', 'bloquear', 'Permissão para ativar/desativar grupos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (23, 9, 'Ver', 'ver', 'Permissão para ver contatos feitos no portal.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (24, 9, 'Excluir', 'excluir', 'Permissão para excluir contatos feitos no portal.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (25, 9, 'Buscar', 'buscar', 'Permissão para buscar contatos feitos no portal.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (26, 5, 'Adicionar', 'adicionar', 'Permissão para adicionar novos conteúdos no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (27, 5, 'Editar', 'editar', 'Permissão para editar conteúdos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (28, 5, 'Excluir', 'excluir', 'Permissão para excluir conteúdos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (29, 5, 'Buscar', 'buscar', 'Permissão para buscar conteúdos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (30, 5, 'Ver', 'ver', 'Permissão para ver conteúdos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (31, 5, 'Bloquear', 'bloquear', 'Permissão para ativar/desativar conteúdos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (32, 5, 'Configuração', 'configuracao', 'Permissão para configurar os conteúdos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (33, 5, 'Modificar Raiz', 'modificar_raiz', 'Permissão para modificar os conteúdos da raiz de conteúdos do sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (34, 13, 'Adicionar', 'adicionar', 'Permissão para adicionar novos e-mails no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (35, 13, 'Editar', 'editar', 'Permissão para editar e-mails cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (36, 13, 'Excluir', 'excluir', 'Permissão para excluir e-mails cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (37, 13, 'Buscar', 'buscar', 'Permissão para buscar e-mails cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (38, 13, 'Ver', 'ver', 'Permissão para ver e-mails cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (39, 13, 'Bloquear', 'bloquear', 'Permissão para ativar/desativar e-mails cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (40, 10, 'Adicionar', 'adicionar', 'Permissão para adicionar novos e-mails markentings no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (41, 10, 'Editar', 'editar', 'Permissão para editar e-mails markentings cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (42, 10, 'Excluir', 'excluir', 'Permissão para excluir e-mails markentings cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (43, 10, 'Buscar', 'buscar', 'Permissão para buscar e-mails markentings cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (44, 10, 'Ver', 'ver', 'Permissão para ver e-mails markentings cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (45, 10, 'Enviar E-mail Markenting', 'enviar_emails', 'Permissão para enviar os e-mails markentings cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (46, 8, 'Adicionar', 'adicionar', 'Permissão para adicionar novas galerias no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (47, 8, 'Editar', 'editar', 'Permissão para editar galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (48, 8, 'Buscar', 'buscar', 'Permissão para buscar galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (49, 8, 'Ver', 'ver', 'Permissão para ver galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (50, 8, 'Bloquear', 'bloquear', 'Permissão para ativar/desativar galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (51, 8, 'Excluir', 'excluir', 'Permissão para excluir galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (52, 8, 'Imagens Ver Galerias', 'imagens_ver', 'Permissão para ver as imagens das galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (53, 8, 'Imagens Uploads', 'imagens_uploads', 'Permissão para enviar imagens das galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (54, 8, 'Imagens Editar Descrição', 'imagens_editar', 'Permissão para editar as descrições das imagens das galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (55, 8, 'Imagens Excluir', 'imagens_excluir', 'Permissão para excluir as imagens das galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (56, 11, 'Adicionar', 'adicionar', 'Permissão para adicionar novas galerias no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (57, 11, 'Editar', 'editar', 'Permissão para editar galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (58, 11, 'Excluir', 'excluir', 'Permissão para excluir galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (59, 11, 'Ver', 'ver', 'Permissão para ver galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (60, 11, 'Uploads', 'uploads', 'Permissão para enviar arquivos para o sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (61, 11, 'Downloads', 'download', 'Permissão para baixar arquivos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (62, 11, 'Caminho dos Arquivos', 'url', 'Permissão para poder ver o caminho dos arquivos cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (63, 11, 'Criar Modelos de Pasta de Usuário', 'pasta_usuario', 'Permissão para criar modelos de pasta de usuários, bem como visualizar a lista dos modelos.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (64, 11, 'Excluir Modelos de Pasta de Usuário', 'pasta_usuario_excluir', 'Permissão para poder excluir modelos de pasta de usuários.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (65, 14, 'Downloads de Arquivos', 'download', 'Permissão para poder baixar os arquivos.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (66, 14, 'Ver', 'ver', 'Permissão para poder ver os arquivos.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (67, 15, 'Ver', 'ver', 'Permissão para ver as mensagens.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (68, 15, 'Enviar', 'enviar', 'Permissão para enviar mensagens.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (69, 3, 'Pasta Usuário', 'pasta_usuario', 'Permissão para poder vincular pasta aos usuários.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (70, 12, 'Pasta Grupo', 'pasta_grupo', 'Permissão para poder vincular pasta aos grupos.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (71, 16, 'Editar', 'editar', 'Permissão para editar os layouts no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (72, 16, 'Ver', 'ver', 'Permissão para ver os layouts.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (73, 19, 'Editar', 'editar', 'Permissão para mudar os parâmetros do addthis.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (74, 8, 'Grupos', 'grupos', 'Permissão para poder criar/alterar grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (75, 8, 'Grupos Adicionar', 'grupos_adicionar', 'Permissão para adicionar grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (76, 8, 'Grupos Ver', 'grupos_ver', 'Permissão para ver grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (77, 8, 'Grupos Editar', 'grupos_editar', 'Permissão para editar grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (78, 8, 'Grupos Bloquear', 'grupos_bloquear', 'Permissão para bloquear grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (79, 8, 'Grupos Excluir', 'grupos_excluir', 'Permissão para excluir grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (80, 21, 'Adicionar', 'adicionar', 'Permissão para adicionar novas galerias no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (81, 21, 'Editar', 'editar', 'Permissão para editar galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (82, 21, 'Buscar', 'buscar', 'Permissão para buscar galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (83, 21, 'Ver', 'ver', 'Permissão para ver galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (84, 21, 'Bloquear', 'bloquear', 'Permissão para ativar/desativar galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (85, 21, 'Excluir', 'excluir', 'Permissão para excluir galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (86, 21, 'Vídeos Ver', 'videos_ver', 'Permissão para ver os vídeos das galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (87, 21, 'Vídeos Enviar', 'videos_enviar', 'Permissão para enviar vídeos para galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (88, 21, 'Vídeos Editar Descrição', 'videos_editar', 'Permissão para editar as descrições dos vídeos das galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (89, 21, 'Vídeos Excluir', 'videos_excluir', 'Permissão para excluir as imagens das galerias cadastrados no sistema.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (90, 21, 'Grupos', 'grupos', 'Permissão para poder criar/alterar grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (91, 21, 'Grupos Adicionar', 'grupos_adicionar', 'Permissão para adicionar grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (92, 21, 'Grupos Ver', 'grupos_ver', 'Permissão para ver grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (93, 21, 'Grupos Editar', 'grupos_editar', 'Permissão para editar grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (94, 21, 'Grupos Bloquear', 'grupos_bloquear', 'Permissão para bloquear grupos de galerias.');
INSERT INTO modulo_operacao (`id_modulo_operacao`, `id_modulo`, `nome`, `caminho`, `descricao`) VALUES (95, 21, 'Grupos Excluir', 'grupos_excluir', 'Permissão para excluir grupos de galerias.');

COMMIT;

-- -----------------------------------------------------
-- Data for table `grupo`
-- -----------------------------------------------------
SET AUTOCOMMIT=0;
INSERT INTO grupo (`id_grupo`, `status`, `nome`, `descricao`) VALUES (1, 'A', 'Grupo1', 'Descrição do Grupo 1');

COMMIT;
