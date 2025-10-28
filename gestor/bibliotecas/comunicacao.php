<?php
/**
 * Biblioteca de comunicação do sistema.
 *
 * Gerencia envio de emails via SMTP (PHPMailer) e impressão de páginas.
 * Suporta configurações personalizadas por host, templates HTML, anexos,
 * imagens embarcadas e múltiplos destinatários.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.1.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
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

/**
 * Prepara página para impressão armazenando dados na sessão.
 *
 * Armazena o conteúdo HTML e título da página que será impressa,
 * salvando em variável de sessão para posterior recuperação.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CRON Dados de execução cron.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['pagina'] Conteúdo HTML da página a ser impressa (obrigatório).
 * @param string $params['titulo'] Título da página de impressão (opcional).
 * 
 * @return void
 */
function comunicacao_impressao($params = false){
	global $_GESTOR;
	global $_CRON;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetro obrigatório e armazena dados de impressão
	if(isset($pagina)){
		$impressao = Array(
			'pagina' => $pagina,
		);
		
		if(isset($titulo)){
			$impressao['titulo'] = $titulo;
		}
		
		// Salva dados de impressão na sessão
		gestor_sessao_variavel('impressao',$impressao);
	}
}

/**
 * Envia emails via SMTP usando PHPMailer.
 *
 * Função completa de envio de emails com suporte a:
 * - Configurações SMTP personalizadas por host
 * - Templates HTML com variáveis substituíveis
 * - Múltiplos destinatários (TO, CC, BCC)
 * - Anexos de arquivos
 * - Imagens embarcadas (inline)
 * - Assinatura HTML automática
 * - Modo de teste com configurações em runtime
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CONFIG Configurações do sistema.
 * @global array $_CRON Dados de execução cron.
 * 
 * @param array|false $params Parâmetros da função.
 * 
 * @param bool $params['hostPersonalizacao'] Permite configuração via módulo Comunicação (opcional).
 * @param int $params['id_hosts'] ID do host para comunicação (opcional).
 * 
 * @param array $params['servidor'] Configurações do servidor SMTP (opcional).
 * @param bool $params['servidor']['debug'] Ativar debug PHPMailer (opcional).
 * @param string $params['servidor']['hospedeiro'] Host SMTP (opcional).
 * @param string $params['servidor']['usuario'] Usuário SMTP (opcional).
 * @param string $params['servidor']['senha'] Senha SMTP (opcional).
 * @param bool $params['servidor']['seguro'] Usar SSL/TLS (opcional).
 * @param int $params['servidor']['porta'] Porta SMTP (opcional).
 * 
 * @param array $params['remetente'] Dados do remetente (opcional).
 * @param string $params['remetente']['de'] Email de origem (opcional).
 * @param string $params['remetente']['deNome'] Nome do remetente (opcional).
 * @param string $params['remetente']['responderPara'] Email para respostas (opcional).
 * @param string $params['remetente']['responderParaNome'] Nome para respostas (opcional).
 * 
 * @param array $params['destinatarios'] Array de destinatários (opcional).
 * @param string $params['destinatarios'][]['email'] Email do destinatário (opcional).
 * @param string $params['destinatarios'][]['nome'] Nome do destinatário (opcional).
 * @param string $params['destinatarios'][]['tipo'] Tipo: 'cc' ou 'bcc' (opcional, padrão TO).
 * 
 * @param array $params['mensagem'] Conteúdo da mensagem (opcional).
 * @param string $params['mensagem']['assunto'] Assunto do email (opcional).
 * @param string $params['mensagem']['html'] Corpo HTML da mensagem (opcional).
 * @param bool $params['mensagem']['htmlAssinaturaAutomatica'] Incluir assinatura automática (opcional).
 * @param string $params['mensagem']['htmlLayoutID'] ID do layout HTML (opcional).
 * @param string $params['mensagem']['htmlTitulo'] Título HTML (opcional, usa assunto se omitido).
 * @param array $params['mensagem']['htmlVariaveis'] Variáveis para substituição no HTML (opcional).
 * @param array $params['mensagem']['imagens'] Imagens embarcadas (opcional).
 * @param array $params['mensagem']['anexos'] Arquivos anexos (opcional).
 * 
 * @param bool $params['EMAIL_TESTS'] Modo teste com configs em runtime (opcional).
 * @param bool $params['EMAIL_DEBUG'] Debug em modo teste (opcional).
 * @param string $params['EMAIL_HOST'] Host SMTP em modo teste (opcional).
 * @param string $params['EMAIL_USER'] Usuário SMTP em modo teste (opcional).
 * @param string $params['EMAIL_PASS'] Senha SMTP em modo teste (opcional).
 * @param bool $params['EMAIL_SECURE'] SSL em modo teste (opcional).
 * @param int $params['EMAIL_PORT'] Porta em modo teste (opcional).
 * @param string $params['EMAIL_FROM'] Email origem em modo teste (opcional).
 * @param string $params['EMAIL_FROM_NAME'] Nome origem em modo teste (opcional).
 * @param string $params['EMAIL_REPLY_TO'] Email resposta em modo teste (opcional).
 * @param string $params['EMAIL_REPLY_TO_NAME'] Nome resposta em modo teste (opcional).
 * 
 * @return bool|string True se enviado com sucesso, string com erro se falhar, false se email desativado.
 */
function comunicacao_email($params = false){
	global $_GESTOR;
	global $_CONFIG;
	global $_CRON;
	
	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val; 

	if(isset($_CONFIG['email']) || isset($EMAIL_TESTS)){
		if($_CONFIG['email']['ativo'] || isset($EMAIL_TESTS)){
			// ===== Definição se é ou não uma comunicação para um host.
			
			if(isset($_GESTOR['host-id']) && !isset($id_hosts)){
				$id_hosts = $_GESTOR['host-id'];
			}
			
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
			
			if(isset($_CONFIG['email'])){
				if(isset($_CONFIG['email']['server'])){
					if(isset($_CONFIG['email']['server']['debug'])){ $server['debug'] = $_CONFIG['email']['server']['debug']; }
					if(isset($_CONFIG['email']['server']['host'])){ $server['host'] = $_CONFIG['email']['server']['host']; }
					if(isset($_CONFIG['email']['server']['user'])){ $server['user'] = $_CONFIG['email']['server']['user']; }
					if(isset($_CONFIG['email']['server']['pass'])){ $server['pass'] = $_CONFIG['email']['server']['pass']; }
					if(isset($_CONFIG['email']['server']['port'])){ $server['port'] = $_CONFIG['email']['server']['port']; }
					
					if(isset($_CONFIG['email']['server']['secure'])){ $server['secure'] = PHPMailer::ENCRYPTION_SMTPS; }
				}
			}
			
			if(isset($_CONFIG['email'])){
				if(isset($_CONFIG['email']['sender'])){
					if(isset($_CONFIG['email']['sender']['from'])){ $sender['from'] = $_CONFIG['email']['sender']['from']; }
					if(isset($_CONFIG['email']['sender']['fromName'])){ $sender['fromName'] = $_CONFIG['email']['sender']['fromName']; }
					if(isset($_CONFIG['email']['sender']['replyTo'])){ $sender['replyTo'] = $_CONFIG['email']['sender']['replyTo']; }
					if(isset($_CONFIG['email']['sender']['replyToName'])){ $sender['replyToName'] = $_CONFIG['email']['sender']['replyToName']; }
				}
			}
			
			// ===== Variáveis definidas pelo usuário em comunicação configurações em um host específico.
			
			if(isset($id_hosts) && isset($hostPersonalizacao)){
				gestor_incluir_biblioteca('configuracao');
				
				$comunicacaoConfiguracoes = configuracao_hosts_variaveis(Array(
					'id_hosts' => $id_hosts,
					'modulo' => 'comunicacao-configuracoes',
					'grupos' => Array(
						'host-admin',
					),
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

			// ===== Variáveis definidas em tempo de execução, para testes.

			if(isset($EMAIL_TESTS)){
				if(isset($EMAIL_DEBUG)){$server['debug'] = $EMAIL_DEBUG;}
				if(isset($EMAIL_HOST)){$server['host'] = $EMAIL_HOST;}
				if(isset($EMAIL_USER)){$server['user'] = $EMAIL_USER;}
				if(isset($EMAIL_PASS)){$server['pass'] = $EMAIL_PASS;}
				if(isset($EMAIL_SECURE)){if($EMAIL_SECURE){$server['secure'] = PHPMailer::ENCRYPTION_SMTPS;}}
				if(isset($EMAIL_PORT)){$server['port'] = $EMAIL_PORT;}
				if(isset($EMAIL_FROM)){$sender['from'] = $EMAIL_FROM;}
				if(isset($EMAIL_FROM_NAME)){$sender['fromName'] = $EMAIL_FROM_NAME;}
				if(isset($EMAIL_REPLY_TO)){$sender['replyTo'] = $EMAIL_REPLY_TO;}
				if(isset($EMAIL_REPLY_TO_NAME)){$sender['replyToName'] = $EMAIL_REPLY_TO_NAME;}
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

				$layoutCSSCompiled = $mailHTML['css_compiled'];

				if(existe($layoutCSSCompiled)){
					$layoutCSSCompiled = preg_replace("/(^|\n)/m", "\n        ", $layoutCSSCompiled);

					$mailCSS .= '<style>'."\n";
					$mailCSS .= $layoutCSSCompiled."\n";
					$mailCSS .= '</style>'."\n";
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
				
				// ===== Incluir automaticamente assinatura caso a opção esteja ativada.
				
				if(isset($mensagem['htmlAssinaturaAutomatica'])){
					$assinaturaPadrao = false;
					
					if(!isset($htmlAssinatura)){
						$assinaturaPadrao = true;
					} else {
						if(!existe($htmlAssinatura)){
							$assinaturaPadrao = true;
						}
					}
					
					if($assinaturaPadrao){
						if(isset($id_hosts)){
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
					
					$mailCorpo .= $htmlAssinatura;
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
					
					gestor_incluir_biblioteca('log');
					
					log_disco('Error retorno: '.$mail->ErrorInfo.' - '.$e->getMessage() . ' - Server: ' . print_r($server,true),'email');
				}
			}
		}
	}
	
	return false;
}

?>