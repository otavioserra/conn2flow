<?php

global $_GESTOR;

// Módulo com ID diferente para não conflitar com o original
$_GESTOR['modulo-id']							=	'host-configuracao-manual';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/host-configuracao-manual.json'), true);

// Incluindo as funções do módulo original que ainda serão úteis (geração de senhas, criptografia, etc)
require_once(dirname(__FILE__).'/../host-configuracao/host-configuracao.php');


function host_configuracao_manual_instalar(){
	global $_GESTOR;
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Verifica se está marcado para instalar
	
	if(isset($host_verificacao['instalar'])){
		if(!isset($host_verificacao['dados-instalacao']) && !isset($_REQUEST['host-ftp'])){ 
			// ===== 1º Etapa: Formulário para o usuário inserir os dados manuais de FTP.
			
			// Inclusão de JS para validação
			gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
			gestor_pagina_javascript_incluir();
			
			// Validação dos novos campos
			$formulario['validacao'] = Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'host-ftp',
					'label' => 'Host/Domínio',
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'usuario-ftp',
					'label' => 'Usuário FTP',
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha-ftp',
					'label' => 'Senha FTP',
				),
			);
			
			interface_formulario_validacao($formulario);
			
		} else if(isset($_REQUEST['host-ftp'])){
			// ===== 2º Etapa: Armazenar provisoriamente os dados manuais e redirecionar para a tela de "carregando". 
			
			$hostFtp = banco_escape_field($_REQUEST['host-ftp']);
			$usuarioFtp = banco_escape_field($_REQUEST['usuario-ftp']);
			$senhaFtp = banco_escape_field($_REQUEST['senha-ftp']);
			
			// Armazena os dados de forma segura na sessão
			$host_verificacao['dados-instalacao'] = Array(
				'host-ftp' => host_configuracao_encriptar($hostFtp),
				'usuario-ftp' => host_configuracao_encriptar($usuarioFtp),
				'senha-ftp' => host_configuracao_encriptar($senhaFtp),
				'senha-db' => host_configuracao_encriptar(hash("sha256",$senhaFtp)), // Mantive a lógica de senha do DB, podemos ajustar depois.
			);
			
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// Recarrega a página para mostrar a tela de "carregando"
			gestor_redirecionar('host-manual-install/');

		} else if(!isset($host_verificacao['carregando'])){
			// ===== 3º Etapa: Iniciar tela carregando e disparar a próxima etapa via JavaScript.
			
			$host_verificacao['carregando'] = true;
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			$_GESTOR['javascript-vars']['hostCarregando'] = true;
			
			gestor_pagina_javascript_incluir();
			
			$_GESTOR['pagina'] = gestor_componente(Array(
				'id' => 'host-install-carregando',
			));

		} else if(isset($host_verificacao['dados-instalacao'])){
			// ===== 4º Etapa: Instalação manual dos arquivos via FTP.
			
			// Bloqueia que o cliente pare a execução do script
			ignore_user_abort(1);
			
			unset($host_verificacao['carregando']);
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			$dadosInstalacao = $host_verificacao['dados-instalacao'];
			
			// ==================================================================
			// REMOÇÃO DA LÓGICA DO CPANEL
			// Aqui é o ponto principal da mudança. Em vez de chamar a API do cPanel,
			// vamos usar diretamente os dados que o usuário forneceu.
			// ==================================================================

			$id_hosts = $host_verificacao['id_hosts'];

			// Decripta os dados da sessão para uso
			$dominio = host_configuracao_decriptar($dadosInstalacao['host-ftp']);
			$user_ftp = host_configuracao_decriptar($dadosInstalacao['usuario-ftp']);
			$senhaFtp = host_configuracao_decriptar($dadosInstalacao['senha-ftp']);

			// Atualiza o registro do host no banco com os dados manuais
			banco_update(
				"dominio='".$dominio."', ".
				"user_ftp='".$user_ftp."', ".
				"pre_configurado=1, ". // Marca como pré-configurado
				"instalado=1", // Marca como instalado (pulando a etapa do cPanel)
				"hosts",
				"WHERE id_hosts='".$id_hosts."'"
			);
			
			// A partir daqui, o fluxo é muito parecido com o original, mas usando os dados manuais.
			// A função `host_configuracao_pipeline_atualizacao` já é chamada na etapa de configuração.
			// Vamos pular a instalação via cPanel e ir direto para a configuração.

			// 20 segundos de pausa para garantir que o servidor FTP esteja pronto.
			sleep(20);
			
			// ===== Atualizar sessão e remover o status 'instalar'.
			
			unset($host_verificacao['instalar']);
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Redirecionar o usuário para a configuração do novo host.
			// O fluxo de `host-config` vai pegar os dados da sessão e usar o `host_configuracao_pipeline_atualizacao`
			// para enviar os arquivos.
			gestor_redirecionar('host-config/');

		} else {
			$_GESTOR['pagina'] = '[host-configuracao-manual][install] Erro inesperado: dados de instalação não definidos!';
		}
	} else {
		gestor_redirecionar('dashboard/');
	}
}


function host_configuracao_manual_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	// ===== Verifica se o usuário é admin do host
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	if(!isset($host_verificacao['privilegios_admin'])){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => 'host-configuracao','id' => 'alert-not-admin-host'))
		));
		gestor_redirecionar('dashboard/');
	}
	
	// ===== Interfaces principais.
	
	if($_GESTOR['ajax']){
		// Lógica AJAX se necessária no futuro
	} else {
		// Carrega a interface de edição para podermos usar o formulário
		$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
		$_GESTOR['interface-opcao'] = 'adicionar-incomum';
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'instalar': host_configuracao_manual_instalar(); break;
		}
		
		// Carrega o template do formulário
		$_GESTOR['pagina'] = gestor_componente(Array(
			'id' => 'host-manual-install-form',
		));

		interface_finalizar();
	}
}

host_configuracao_manual_start();

?>