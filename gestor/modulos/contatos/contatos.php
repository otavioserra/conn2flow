<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'contatos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/contatos.json'), true);

// ==== Start

function contatos_submit(){
	global $_GESTOR;
	
	// ===== Incluir as bibliotecas do módulo.
	
	gestor_incluir_bibliotecas();
	
	// ===== Verificar a permissão do acesso.
	
	gestor_incluir_biblioteca('autenticacao');
	
	$acesso = autenticacao_acesso_verificar(['tipo' => 'formulario-contato']);
	
	// ===== Tentativa de enviar um contato.
	
	if(isset($_REQUEST['_widgets-enviar-contato']) && $acesso['permitido']){
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'nome',
				'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
			),
			Array(
				'regra' => 'email',
				'campo' => 'email',
				'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-email')),
			),
			Array(
				'regra' => 'nao-vazio',
				'campo' => 'telefone',
				'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-tel')),
			),
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'mensagem',
				'label' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-message')),
			),
			)
		));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_REQUEST['token'];
				$action = $_REQUEST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		// ===== caso esteja tudo validado, guardar contato no banco e enviar os dados também no email de contato.
		
		if($recaptchaValido){
			// ===== Filtrar o campo mensagem e incluir <br> no fim de cada linha.
			
			gestor_incluir_biblioteca('geral');
			
			$mensagemBR = geral_nl2br($_REQUEST['mensagem']);
			
			// ===== guardar contato no banco.
			
			banco_insert_name_campo('tipo','contato');
			if(isset($_REQUEST['nome'])){ banco_insert_name_campo('nome',$_REQUEST['nome']); }
			if(isset($_REQUEST['email'])){ banco_insert_name_campo('email',$_REQUEST['email']); }
			if(isset($_REQUEST['telefone'])){ banco_insert_name_campo('telefone',$_REQUEST['telefone']); }
			if(isset($_REQUEST['mensagem'])){ banco_insert_name_campo('mensagem',$_REQUEST['mensagem']); }
			banco_insert_name_campo('status','A');
			banco_insert_name_campo('versao','1',true);
			banco_insert_name_campo('data_criacao','NOW()',true);
			banco_insert_name_campo('data_modificacao','NOW()',true);
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"formularios"
			);
			
			// ===== Incluir acesso do tipo 'formulario-contato' para evitar SPAM de cadastros.
			
			autenticacao_acesso_cadastrar(['tipo' => 'formulario-contato','antispam' => true]);
			
			// ===== destinatários dos emails.
			
			$destinatariosTXT = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'contacts-delivery-emails'));
			
			$destinatarios = explode(';',trim($destinatariosTXT));

			if($destinatarios)
			foreach($destinatarios as $destinatario){
				$destinatario = trim($destinatario);
				
				if(preg_match('/</', $destinatario) > 0){
					$arrAux = explode('<',trim($destinatario));
					$nomeAux = $arrAux[0];
					$emailAux = rtrim($arrAux[1], '>');
					
					$destinatatiosArr[] = Array(
						'email' => $emailAux,
						'nome' => $nomeAux,
					);
				} else {
					$destinatatiosArr[] = Array(
						'email' => $destinatario,
					);
				}
			}
			
			// ===== formatar o contato por email.
			
			if(isset($_REQUEST['nome'])){ $nome = $_REQUEST['nome']; } else { $nome = ''; }
			if(isset($_REQUEST['email'])){ $email = $_REQUEST['email']; } else { $email = ''; }
			if(isset($_REQUEST['telefone'])){ $telefone = $_REQUEST['telefone']; } else { $telefone = ''; }
			if(isset($_REQUEST['mensagem'])){ $mensagemTXT = $_REQUEST['mensagem']; } else { $mensagemTXT = ''; }
			
			$numero = date('Ymd') . $tokens_id;
			
			$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'contacts-subject-emails')),"#cod#",$numero);
			$mensagem = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'contacts-message-emails'));
			
			$mensagem = modelo_var_troca($mensagem,"#codigo#",$numero);
			$mensagem = modelo_var_troca($mensagem,"#nome#",$nome);
			$mensagem = modelo_var_troca($mensagem,"#email#",$email);
			$mensagem = modelo_var_troca($mensagem,"#telefone#",$telefone);
			$mensagem = modelo_var_troca($mensagem,"#mensagem#",$mensagemTXT);
			
			// ===== enviar o email.
			
			gestor_incluir_biblioteca('comunicacao');
			
			if(comunicacao_email(Array(
				'destinatarios' => $destinatatiosArr,
				'remetente' => Array(
					'responderPara' => $email,
					'responderParaNome' => $nome,
				),
				'mensagem' => Array(
					'assunto' => $assunto,
					'html' => $mensagem,
					'htmlAssinaturaAutomatica' => true,
				),
			))){
				
			}
			
			// ===== redirecionar caso tudo ocorra normalmente sem problemas para a página de sucesso.
			
			gestor_redirecionar('contato-sucesso/');
		} else {
			// ===== Se o recaptcha for inválido, alertar o usuário.
			
			sleep(3);
			
			$botaoTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid-btn'));
			
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid'));
			
			$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('contato/');
		}
	}
	
}

function contatos_page(){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formulario');

	// Incluir o controlador do formulário para processar o envio do contato.
	formulario_controlador([
		'formId' => 'form-contact',
	]);
}

function contatos_start(){
    global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			// case 'ajaxOption': forms_submissions_ajax_option(); break;
		}
	} else {
		switch($_GESTOR['opcao']){
		    case 'contact': contatos_page(); break;
		}
	}
}

contatos_start();

?>