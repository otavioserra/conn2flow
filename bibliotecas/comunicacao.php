<?php

global $_GESTOR;

$_GESTOR['biblioteca-comunicacao']							=	Array(
	'versao' => '1.1.0',
);

// ===== Inclusão do PHPMailer

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once $_GESTOR['bibliotecas-path'].'PHPMailer/src/Exception.php';
require_once $_GESTOR['bibliotecas-path'].'PHPMailer/src/PHPMailer.php';
require_once $_GESTOR['bibliotecas-path'].'PHPMailer/src/SMTP.php';

// ===== Funções auxiliares

// ===== Funções principais

function comunicacao_email($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// hostPersonalizacao - Bool - Opcional - Permitir que a comunicação de email seja configurável pelo módulo Comunicação Configurações.
	// servidor - Array - Opcional - Conjunto com todas as variáveis do servidor de emails. Caso não definido, irá usar o padrão do sistema.
		// debug - Bool - Opcional - Debugar para encontrar erros.
		// hospedeiro - String - Opcional - Host do servidor de emails SMTP.
		// usuario - String - Opcional - Usuário do servidor de emails SMTP.
		// senha - String - Opcional - Senha do usuário do servidor de emails SMTP.
		// seguro - Bool - Opcional - Ativar uso de SSL em conexões com servidor de emails SMTP.
		// porta - Int - Opcional - Porta de acesso ao servidor de emails SMTP.
		
	// remetente - Array - Opcional - Conjunto com todas as variáveis do remetente de emails. Caso não definido, irá usar o padrão do sistema.
		// de - String - Opcional - Endereço de email de origem da mensagem.
		// deNome - String - Opcional - Nome pessoal do endereço de email de origem da mensagem.
		// responderPara - String - Opcional - Endereço de email que será usado quando o destinatário responder a mensagem.
		// responderParaNome - String - Opcional - Nome pessoal do endereço de email que será usado quando o destinatário responder a mensagem.
		
	// destinatarios - Array de Arrays - Opcional - Conjunto com todas as variáveis dos destinatários de emails.
		// email - String - Opcional - Endereço de email do(s) destinatário(s).
		// nome - String - Opcional - Nome do endereço de email do(s) destinatário(s).
		// tipo - String - Opcional - Tipo(s) do(s) destinatário(s). Senão definido, usar destinatário comum. Valores possíveis: 'cc' e 'bcc'.
	
	// mensagem - Array - Opcional - Conjunto com todas as variáveis da mensagem enviada.
		// assunto - String - Opcional - Assunto da mensagem.
		// html - String - Opcional - Código HTML da mensagem.
		// htmlAssinaturaAutomatica - Bool - Opcional - Inclusão automatiza de HTML da assinatura no final da mensagem.
		// htmlLayoutID - String - Opcional - ID do layout do código HTML que será usado ao invés do código HTML diretamente.
		// htmlTitulo - String - Opcional - Título do HTML da mensagem, senão informado coolocar o assunto.
		// htmlVariaveis - Array de Arrays - Opcional - Conjunto com todas as variáveis com seus valores que serão embutidas no código HTML.
			// variavel - String - Opcional - Nome da variável.
			// valor - String - Opcional - Valor da variável.
		// imagens - Array de Arrays - Opcional - Conjunto com todas as imagens que serão embutidas no código HTML.
			// caminho - String - Opcional - Caminho da imagem.
			// cid - String - Opcional - CID da imagem, referência dentro do HTML para a imagem.
			// nome - String - Opcional - Nome da imagem.
			// imagemTmpCaminho - String - Opcional - Se a imagem incluída é temporária, passar o caminho dela para ser removida no fim do processo.
		// anexos - Array de Arrays - Opcional - Conjunto com todos os anexos.
			// nome - String - Opcional - Nome do arquivo.
			// caminho - String - Opcional - Caminho do anexo.
			// tmpCaminho - String - Opcional - Se o arquivo incluído é temporária, passar o caminho dele para ser removida no fim do processo.
	
	// ===== 
	
	if(isset($_GESTOR['email'])){
		if($_GESTOR['email']['ativo']){
			// ===== Variáveis padrões
			
			$server = Array(
				'debug' => false,
				'host' => 'localhost',
				'user' => 'user@localhost',
				'pass' => 'password',
				'secure' => PHPMailer::ENCRYPTION_STARTTLS,
				'port' => 587,
				'altBody' => 'Esta mensagem usa HTML, para ver a mesma, por favor use um programa de emails compatível com HTML!',
			);
			
			$sender = Array(
				'from' => 'user@localhost',
			);
			
			$recipients = Array(
				Array(
					'email' => 'to@localhost',
				),
			);
			
			$message = Array(
				'subject' => 'Assunto Padrão',
				'body' => '<p>Corpo HTML Padrão</p>',
				'title' => 'Assunto Padrão',
			);
			
			// ===== Variáveis de configuração padrões
			
			if(isset($_GESTOR['email'])){
				if(isset($_GESTOR['email']['server'])){
					if(isset($_GESTOR['email']['server']['debug'])){ $server['debug'] = $_GESTOR['email']['server']['debug']; }
					if(isset($_GESTOR['email']['server']['host'])){ $server['host'] = $_GESTOR['email']['server']['host']; }
					if(isset($_GESTOR['email']['server']['user'])){ $server['user'] = $_GESTOR['email']['server']['user']; }
					if(isset($_GESTOR['email']['server']['pass'])){ $server['pass'] = $_GESTOR['email']['server']['pass']; }
					if(isset($_GESTOR['email']['server']['port'])){ $server['port'] = $_GESTOR['email']['server']['port']; }
					
					if(isset($_GESTOR['email']['server']['secure'])){ $server['secure'] = PHPMailer::ENCRYPTION_SMTPS; }
				}
			}
			
			if(isset($_GESTOR['email'])){
				if(isset($_GESTOR['email']['sender'])){
					if(isset($_GESTOR['email']['sender']['from'])){ $sender['from'] = $_GESTOR['email']['sender']['from']; }
					if(isset($_GESTOR['email']['sender']['fromName'])){ $sender['fromName'] = $_GESTOR['email']['sender']['fromName']; }
					if(isset($_GESTOR['email']['sender']['replyTo'])){ $sender['replyTo'] = $_GESTOR['email']['sender']['replyTo']; }
					if(isset($_GESTOR['email']['sender']['replyToName'])){ $sender['replyToName'] = $_GESTOR['email']['sender']['replyToName']; }
				}
			}
			
			// ===== Variáveis definidas pelo usuário em comunicação configurações em um host específico.
			
			if(isset($_GESTOR['host-id']) && isset($hostPersonalizacao)){
				$comunicacaoConfiguracoes = gestor_host_variaveis(Array(
					'modulo' => 'comunicacao-configuracoes',
					'conjunto' => true,
					'global' => true,
				));
				
				if($comunicacaoConfiguracoes)
				foreach($comunicacaoConfiguracoes as $id => $config){
					switch($id){
						case 'email-personalizado-ativo':
							$emailPersonalizadoAtivo = true;
						break;
					}
				}
				
				if(isset($emailPersonalizadoAtivo)){
					foreach($comunicacaoConfiguracoes as $id => $config){
						if(existe($config)){
							switch($id){
								case 'email-assinatura': $htmlAssinatura = $config; break;
								case 'servidor-host': $server['host'] = $config; break;
								case 'servidor-usuario': $server['user'] = $config; break;
								case 'servidor-senha': $server['pass'] = $config; break;
								case 'servidor-porta': $server['port'] = $config; break;
								case 'servidor-certificado-seguranca': $server['secure'] = PHPMailer::ENCRYPTION_SMTPS; break;
								case 'remetente-de': $sender['from'] = $config; break;
								case 'remetente-de-nome': $sender['fromName'] = $config; break;
								case 'remetente-responder-para': $sender['replyTo'] = $config; break;
								case 'remetente-responder-para-nome': $sender['replyToName'] = $config; break;
							}
						}
					}
				}
			}
			
			// ===== Variáveis recebidas pela interface
			
			if(isset($servidor)){
				if(isset($servidor['debug'])){ $server['debug'] = $servidor['debug']; }
				if(isset($servidor['hospedeiro'])){ $server['host'] = $servidor['hospedeiro']; }
				if(isset($servidor['usuario'])){ $server['user'] = $servidor['usuario']; }
				if(isset($servidor['senha'])){ $server['pass'] = $servidor['senha']; }
				if(isset($servidor['porta'])){ $server['port'] = $servidor['porta']; }
				
				if(isset($servidor['seguro'])){ if($servidor['seguro']){$server['secure'] = PHPMailer::ENCRYPTION_SMTPS; }}
			}
			
			if(isset($remetente)){
				if(isset($remetente['de'])){ $sender['from'] = $remetente['de']; }
				if(isset($remetente['deNome'])){ $sender['fromName'] = $remetente['deNome']; }
				if(isset($remetente['responderPara'])){ $sender['replyTo'] = $remetente['responderPara']; }
				if(isset($remetente['responderParaNome'])){ $sender['replyToName'] = $remetente['responderParaNome']; }
			}
			
			if(isset($destinatarios)){
				$recipients = $destinatarios;
			}
			
			if(isset($mensagem)){
				if(isset($mensagem['assunto'])){ $message['subject'] = $mensagem['assunto']; }
				if(isset($mensagem['html'])){ $message['body'] = $mensagem['html']; }
				if(isset($mensagem['htmlLayoutID'])){ $message['htmlLayoutID'] = $mensagem['htmlLayoutID']; }
				if(isset($mensagem['htmlVariaveis'])){ $message['htmlVariaveis'] = $mensagem['htmlVariaveis']; }
				if(isset($mensagem['htmlTitulo'])){ $message['title'] = $mensagem['htmlTitulo']; } else if(isset($mensagem['assunto'])){ $message['title'] = $mensagem['assunto']; }
				if(isset($mensagem['imagens'])){ $message['embeddedImgs'] = $mensagem['imagens']; }
				if(isset($mensagem['anexos'])){ $message['attachments'] = $mensagem['anexos']; }
			}
			
			// ===== Inserir o layout HTML caso exista.
			
			$mailHTML = gestor_layout(Array(
				'id' => 'layout-emails',
				'return_css' => true,
			));
			
			$layoutHTML = $mailHTML['html'];
			
			if(existe($layoutHTML)){
				$mailCSS = '';
				$mailJS = '';
				$mailCorpo = '';
				
				$layoutCSS = $mailHTML['css'];
				
				if(existe($layoutCSS)){
					$layoutCSS = preg_replace("/(^|\n)/m", "    ", $layoutCSS);
					
					$mailCSS .= '<style>'."\n";
					$mailCSS .= "    ".$layoutCSS."\n";
					$mailCSS .= "    ".'</style>';
				}
				
				$mailCorpo = $message['body'];
				
				// ===== Caso passe o layout ID para essa instância, aplicar o layout ao corpo do HTML do email.
				
				if(isset($message['htmlLayoutID'])){
					$mailBodyHTML = gestor_componente(Array(
						'id' => $message['htmlLayoutID'],
						'return_css' => true,
					));
					
					$layoutBodyHTML = $mailBodyHTML['html'];
					
					if(existe($layoutBodyHTML)){
						$layoutBodyCSS = $mailBodyHTML['css'];
						
						if(existe($layoutBodyCSS)){
							$layoutBodyCSS = preg_replace("/(^|\n)/m", "    ", $layoutBodyCSS);
							
							$mailCSS .= "\n    ".'<style>'."\n";
							$mailCSS .= "    ".$layoutBodyCSS."\n";
							$mailCSS .= "    ".'</style>';
						}
						
						$mailCorpo = $layoutBodyHTML;
					}
				}
				
				// ===== Modificar as variáveis padrão do layout principal.
				
				$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#titulo -->",'<title>'.$message['title'].'</title>');
				$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#css -->",$mailCSS);
				$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#js -->",$mailJS);
				$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#corpo -->",$mailCorpo);
				
				// ===== Alterar as variáveis auxiliares caso enviadas
				
				if(isset($message['htmlVariaveis'])){
					foreach($message['htmlVariaveis'] as $htmlVar){
						if(isset($htmlVar['variavel']) && isset($htmlVar['valor'])){
							$layoutHTML = modelo_var_troca_tudo($layoutHTML,$htmlVar['variavel'],$htmlVar['valor']);
						}
					}
				}
				
				// ===== Incluir automaticamente assinatura caso a opção esteja ativada.
				
				if(isset($htmlAssinaturaAutomatica)){
					$assinaturaPadrao = false;
					
					if(!isset($htmlAssinatura)){
						$assinaturaPadrao = true;
					} else {
						if(!existe($htmlAssinatura)){
							$assinaturaPadrao = true;
						}
					}
					
					if($assinaturaPadrao){
						if(isset($_GESTOR['host-id'])){
							$htmlAssinatura = gestor_componente(Array(
								'id' => 'hosts-layout-emails-assinatura',
							));
						} else {
							$htmlAssinatura = gestor_componente(Array(
								'id' => 'layout-emails-assinatura',
							));
						}
					}
					
					// ===== Incluir assinatura no final do corpo da mensagem.
					
					$layoutHTML .= $htmlAssinatura;
				}
				
				// ===== Substituir variáveis globais no HTML e incluir no corpo da mensagem.
				
				$layoutHTML = gestor_pagina_variaveis_globais(Array('html' => $layoutHTML));
				
				$message['body'] = $layoutHTML;
			}
			
			//Instantiation and passing `true` enables exceptions
			$mail = new PHPMailer(true);

			try {
				//Server settings
				
				if($server['debug']) $mail->SMTPDebug = SMTP::DEBUG_SERVER;                        //Enable verbose debug output
				
				$mail->isSMTP();                                           	  //Send using SMTP
				$mail->Host       	= $server['host'];                   	  //Set the SMTP server to send through
				$mail->SMTPAuth   	= true;                                   //Enable SMTP authentication
				$mail->Username   	= $server['user'];                 		  //SMTP username
				$mail->Password   	= $server['pass'];                        //SMTP password
				$mail->SMTPSecure 	= $server['secure'];   				      //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
				$mail->Port       	= $server['port'];                     	  //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
				$mail->CharSet 		= PHPMailer::CHARSET_UTF8;         		  //Charset default UTF-8

				//Sender
				
				if(isset($sender['fromName'])){ $mail->setFrom($sender['from'], $sender['fromName']); } else { $mail->setFrom($sender['from']); }
				if(isset($sender['replyToName'])){ $mail->addReplyTo($sender['replyTo'], $sender['replyToName']); } else { $mail->addReplyTo($sender['replyTo']); }
				
				//Recipients
				
				foreach($recipients as $recipient){
					$tipo = strtolower(isset($recipient['tipo']) ? $recipient['tipo'] : 'normal');
					
					switch($tipo){
						case 'normal':
							if(isset($recipient['nome'])){ $mail->addAddress($recipient['email'], $recipient['nome']); } else { $mail->addAddress($recipient['email']); }
						break;
						case 'cc':
							if(isset($recipient['nome'])){ $mail->addCC($recipient['email'], $recipient['nome']); } else { $mail->addCC($recipient['email']); }
						break;
						case 'bcc':
							if(isset($recipient['nome'])){ $mail->addBCC($recipient['email'], $recipient['nome']); } else { $mail->addBCC($recipient['email']); }
						break;
					}
				}

				// Attachments
				
				$tmp_files  = false;
				
				if(isset($message['attachments'])){
					$attachments = $message['attachments'];
					
					foreach($attachments as $attachment){
						if(isset($attachment['caminho'])){
							$mail->AddAttachment($attachment['caminho'], (isset($attachment['nome']) ? $attachment['nome'] : '' ));
							$tmp_file = (isset($attachment['tmpCaminho']) ? $attachment['tmpCaminho'] : false);
							
							if($tmp_file){
								$tmp_files[] = $tmp_file;
							}
						}
					}
				}

				// Embedded Images
				
				if(isset($message['embeddedImgs'])){
					$embedded_imgs = $message['embeddedImgs'];
					
					foreach($embedded_imgs as $img){
						$filename = $img['caminho'];
						$cid = $img['cid'];
						$tmp_image = (isset($img['imagemTmpCaminho']) ? $img['imagemTmpCaminho'] : false);
						$name = ($img['nome']?$img['nome']:$img['caminho']);
						
						$cid = preg_replace('/'.preg_quote('cid:').'/i', '', $cid);
						
						$mail->AddEmbeddedImage($filename, $cid, $name);
						
						if($tmp_image){
							$tmp_files[] = $tmp_image;
						}
					}
				}

				//Content
				
				$mail->isHTML(true);                                  //Set email format to HTML
				
				$mail->Subject = $message['subject'];
				$mail->Body    = $message['body'];
				
				$mail->AltBody = $server['altBody'];
				
				// ===== Enviar o email
				
				$mail->send();
				
				// ===== Rotinas de limpeza.
				
				if($tmp_files)
				foreach($tmp_files as $tmp){
					unlink($tmp);
				}
				
				return true;
			} catch (Exception $e) {
				if($server['debug']){
					// ===== Se houve algum erro e o debug estiver ativo, incluir no histórico o erro.
					
					gestor_incluir_biblioteca('log');
					
					log_debugar(Array(
						'alteracoes' => Array(
							Array(
								'alteracao' => 'email-error',
								'alteracao_txt' => $mail->ErrorInfo,
							)
						),
					));
				} else {
					// ===== Se der algum problema, criar log no disco.
					
					log_disco('Error retorno: '.$mail->ErrorInfo.' - '.$e->getMessage(),'email');
				}
			}
		}
	}
	
	return false;
}

?>