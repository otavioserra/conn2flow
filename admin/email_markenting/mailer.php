<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/

$_VERSAO_MODULO				=	'1.0.1';
$_LOCAL_ID					=	"mailer";
$_PERMISSAO					=	false;
$_INCLUDE_MAILER			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";

$_NO_SESSION				=	true;
$_DEBUG						=	false;

include($_CAMINHO_RELATIVO_RAIZ."config.php");

// Funções de assistência

// ======================================================================================

function start(){
	global $_SYSTEM;
	global $_SYSTEM_ID;
	global $_BANCO_RECONECT;
	global $_MENS;
	global $_SERVER_RAIZ;
	global $_SERVER_NAME;
	global $_ERRO;
	
	if(!$_SYSTEM['MAILER_INTERACOES'])$_SYSTEM['MAILER_INTERACOES'] = 50;
	
	$nao_enviou = 0;
	$nao_enviados = 0;
	$num_tentativas = 3;
	$enviados = 1;
	$num_email = 1;
	$interacao = 0;
	$sleep_time = 50;
	$loop = true;
	$enviar = true;
	
	$dominio = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER_RAIZ;
	
	set_time_limit(0);
	ignore_user_abort(true);
	
	banco_conectar();
	$variavel_global = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor',
		))
		,
		"variavel_global",
		"WHERE variavel='MAILER'"
	);
	
	if($variavel_global[0]['valor'] == 'A'){
		$MAILER_NEWSLETTER = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"variavel_global",
			"WHERE variavel='MAILER_NEWSLETTER'"
		);
		
		if($MAILER_NEWSLETTER){
			$id = $MAILER_NEWSLETTER[0]['valor'];
			
			if($id){
				$MAILER_NUM_EMAIL = banco_select_name
				(
					banco_campos_virgulas(Array(
						'valor',
					))
					,
					"variavel_global",
					"WHERE variavel='MAILER_NUM_EMAIL'"
				);
				
				$num_email += $MAILER_NUM_EMAIL[0]['valor'];
				
				$email_markenting = banco_select_name
				(
					banco_campos_virgulas(Array(
						'assunto',
						'imagem_tabela',
						'texto',
					))
					,
					"email_markenting",
					"WHERE id_email_markenting='".$id."'"
				);
				$assinatura = banco_select_name
				(
					banco_campos_virgulas(Array(
						'valor',
					))
					,
					"variavel_global",
					"WHERE variavel='MAILER_ASSINATURA'"
				);
				
				$modelo = modelo_abrir('html.html');
				$modelo = modelo_tag_val($modelo,'<!-- unsubscribe < -->','<!-- unsubscribe > -->');
				
				$mensagem = $email_markenting[0]['imagem_tabela'] . "\n" . $email_markenting[0]['texto'];
				$assinatura = $assinatura[0]['valor'];
				
				$email_params['from_name'] = $_SYSTEM['EMAIL_NOME'];
				$email_params['from'] = $_SYSTEM['EMAIL_ENDERECO'];
				
				$email_params['subject'] = $email_markenting[0]['assunto'];
			} else {
				$loop = false;
			}
		} else {
			$loop = false;
		}
	} else {
		$loop = false;
	}
	
	while($loop){
		
		// ========= Verificação dos BOTS estarem rodando concorrentemente ========================================
		
		$variavel_global = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"variavel_global",
			"WHERE variavel='MAILER'"
		);
		
		if($variavel_global[0]['valor'] == 'B'){
			$exit2 = true;
			$enviar = false;
			break;
		}
		
		// ========= Envio dos E-mails ========================================
		
		if($enviar){
			$emails = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_emails',
					'nome',
					'email',
				))
				,
				"emails",
				"WHERE status='A'"
				." LIMIT ".($num_email-1).",1"
			);
			
			if(!$emails){
				$exit = true;
				$enviar = false;
				break;
			}
			
			$link = 'http://' . $_SERVER_NAME . '/'.$_SYSTEM['ROOT'].'unsubscribe.php?email='.$emails[0]['email'].'&cod='.crypt($emails[0]['email']).'&id='.$emails[0]['id_emails'];
			
			$pagina = $modelo;
			
			$pagina = modelo_var_troca($pagina,"#link",$link);
			$pagina = modelo_var_troca($pagina,"#assinatura",$assinatura);
			
			$email_params['mensagem'] = $mensagem . $pagina;
			
			$email_params['email_name'] = $emails[0]['nome'];
			$email_params['email'] = $emails[0]['email'];
			
			if(!$_SYSTEM['EMAIL']){
				banco_update
				(
					"valor='".$num_email."'",
					"variavel_global",
					"WHERE variavel='MAILER_NUM_EMAIL'"
				);
				$enviados++;
				$num_email++;
			} else {
				if(enviar_mail($email_params)){
					banco_update
					(
						"valor='".$num_email."'",
						"variavel_global",
						"WHERE variavel='MAILER_NUM_EMAIL'"
					);
					$enviados++;
					$num_email++;
				} else {
					$nao_enviou++;
				}
			}
			
			$mailer_mensagem = 'M='.$_ERRO.' E='.$enviados.' NE='.$nao_enviados.' I='.$interacao;
		
			banco_update
			(
				"valor='".$mailer_mensagem."'",
				"variavel_global",
				"WHERE variavel='MAILER_MENSAGEM'"
			);
			
			if($nao_enviou >= $num_tentativas){
				$nao_enviados++;
				$num_email++;
				$nao_enviou = 0;
			}
		}
		
		$interacao++;
		
		if($enviados > (int)$_SYSTEM['MAILER_INTERACOES']){
			$exit2 = true;
			$enviar = false;
			break;
		}
		
		if($interacao >= (int)$_SYSTEM['MAILER_INTERACOES']){
			$exit2 = true;
			$enviar = false;
			break;
		}
		
		usleep($sleep_time);
	}
	
	if($exit || $exit2){
		if($exit){
			banco_update
			(
				"valor='B'",
				"variavel_global",
				"WHERE variavel='MAILER'"
			);
			banco_update
			(
				"valor='0'",
				"variavel_global",
				"WHERE variavel='MAILER_NUM_EMAIL'"
			);
		}
	}
	
	banco_fechar_conexao();
}

start();

?>