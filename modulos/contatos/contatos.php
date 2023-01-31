<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'contatos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'formularios',
		'id' => 'id',
		'id_numerico' => 'id_'.'formularios',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
);

// ==== Start

function contatos_start(){
	global $_GESTOR;
	
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
			if(isset($_REQUEST['nome'])){ banco_insert_name_campo('nome',$_REQUEST['nome'],true); }
			if(isset($_REQUEST['email'])){ banco_insert_name_campo('email',$_REQUEST['email'],true); }
			if(isset($_REQUEST['telefone'])){ banco_insert_name_campo('telefone',$_REQUEST['telefone'],true); }
			if(isset($_REQUEST['mensagem'])){ banco_insert_name_campo('mensagem',$_REQUEST['mensagem'],true); }
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
			
			autenticacao_acesso_cadastrar(['tipo' => 'formulario-contato']);
			
			// ===== Enviar o contato por email.
			
			$nome = $_REQUEST['nome'];
			$email = $_REQUEST['email'];
			$numero = date('Ymd') . $tokens_id;
			
			$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'signup-mail-subject')),"#numero#",$numero);
			
			gestor_incluir_biblioteca('comunicacao');
			
			if(comunicacao_email(Array(
				'destinatarios' => Array(
					Array(
						'email' => $email,
						'nome' => $nome,
					),
				),
				'mensagem' => Array(
					'assunto' => $assunto,
					'htmlLayoutID' => 'layout-email-novo-cadastro',
					'htmlVariaveis' => Array(
						Array(
							'variavel' => '#nome#',
							'valor' => $nome,
						),
						Array(
							'variavel' => '#url-signin#',
							'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'signin/">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'signin/</a>',
						),
						Array(
							'variavel' => '#url-confirmacao#',
							'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'email-confirmation/?id='.$tokenPubId.'">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'email-confirmation/?id='.$tokenPubId.'</a>',
						),
						Array(
							'variavel' => '#assinatura#',
							'valor' => gestor_componente(Array(
								'id' => 'layout-emails-assinatura',
							)),
						),
					),
				),
			))){
				// Email de confirmação enviado com sucesso!
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

contatos_start();

?>