<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@age1.com.br
	
	B2Make
	
	Copyright (c) 2014 AgeOne Digital Marketing

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

// Funções de Iniciação do sistema B2make

$_VERSAO_MODULO				=	'1.1.0';
$_INCLUDE_MAILER			=	true;
$_PUBLICO					=	true;
$_LOCAL_ID					=	"formulario-contato";
$_CAMINHO_RELATIVO_RAIZ		=	"../../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function enviar_mail_2($parametros){
	global $_SYSTEM;
	global $_ERRO;
	global $_EMAILS;
	global $_DEBUG;
	global $_DEBUG_GRAVAR_LOG;
	global $_HTML;
	
	if($_SYSTEM['EMAIL'] || $_SYSTEM['EMAIL_TESTE']){
		$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
	
		if(!$parametros['smtp_host'])$parametros['smtp_host'] = ($_SYSTEM['SMTP_FORCE_HOST']?$_SYSTEM['SMTP_FORCE_HOST']:'smtp.'.$dominio_sem_www);
		if(!$parametros['smtp_porta'])$parametros['smtp_porta'] = $_SYSTEM['MAILER_PORT'];
		if(!$parametros['smtp_usuario'])$parametros['smtp_usuario'] = $_SYSTEM['SMTP_USER'].'@'.($_SYSTEM['SMTP_FORCE_HOST_EMAIL']?$_SYSTEM['SMTP_FORCE_HOST_EMAIL']:$dominio_sem_www);
		if(!$parametros['smtp_senha'])$parametros['smtp_senha'] = $_SYSTEM['SMTP_PASS'];
		
		if(!$parametros["texto"] && !$parametros["html_sem_modelo"]){
			$mensagem = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'email.html');
			
			$mensagem = modelo_var_troca($mensagem,"#email#titulo#",$parametros["from_name"]);
			$mensagem = modelo_var_troca($mensagem,"#email#css#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$_SYSTEM['PADRAO_CSS']);
			$mensagem = modelo_var_troca($mensagem,"#email#body#",$parametros["mensagem"]);
			$mensagem = modelo_var_troca($mensagem,"#email#body_style#",$parametros["body_style"]);
		} else {
			$mensagem = $parametros["mensagem"];
		}
		
		#preparação para o envio do email
		$mail = new PHPMailer\PHPMailer\PHPMailer();
		
		$mail->SetLanguage("br");
		$mail->IsSMTP(true);
		if(!$parametros["texto"]){
			$mail->IsHTML(true);
			//$mail->AltBody = "Para ver a mensagem, por favor use um programa de emails compatível com HTML!";
		}
		
		#dados da conta
		$mail->Host = $parametros["smtp_host"];
		$mail->Port = $parametros["smtp_porta"];
		$mail->SMTPAuth = true;
		$mail->Username = $parametros["smtp_usuario"];
		$mail->Password = $parametros["smtp_senha"];

		#dados do disparo
		$mail->FromName = ($parametros["from_name"] ? $parametros["from_name"] : "Sistema ".$_HTML['TITULO']);
		$mail->From = ($parametros["from"] ? $parametros["from"] : $parametros["smtp_usuario"]);
		
		if($parametros["email"] && $parametros["email_name"])
			$mail->AddAddress($parametros["email"],$parametros["email_name"]);
		else
			$mail->AddAddress($parametros["email"]);
		
		if($parametros["cc"]){
			$ccs = $parametros["cc"];
			foreach($ccs as $cc){
				$mail->AddCC($cc['email']);
			}
		}
		
		if($parametros["bcc"]){
			$bccs = $parametros["bcc"];
			debug($bccs,false,true);
			foreach($bccs as $bcc){
				debug($bcc['email']);
				$mail->AddBCC($bcc['email']);
			}
		}
		
		$mail->msgHTML($mensagem);
		
		if($parametros["embedded_imgs"]){
			$embedded_imgs = $parametros["embedded_imgs"];
			
			foreach($embedded_imgs as $imgs){
				$filename = $imgs['src'];
				$cid = $imgs['cid'];
				$tmp_image = $imgs['tmp_image'];
				$name = ($imgs['name']?$imgs['name']:$imgs['src']);
				
				$mail->AddEmbeddedImage($filename, $cid, $name);
				
				if($tmp_image){
					unlink($tmp_image);
				}
			}
		}
		
		$mail->Subject = $parametros["subject"];
		
		if($_DEBUG || $_DEBUG_GRAVAR_LOG){
			$mail->SMTPDebug  = 2;
			
			if($_DEBUG_GRAVAR_LOG){
				$mail->Debugoutput = function($str, $level){ gravar_log("debug level $level; message: $str");};
			}
		}
		
		debug($parametros,true);
		
		if(!$mail->Send()){
			$_ERRO = 'Problema com o envio do e-mail.';
			$mail->ClearAddresses();
			return false;
		} else {
			$_ERRO = 'Enviado!';
			$mail->ClearAddresses();
			return true;
		}
	} else {
		$_ERRO = 'ENVIO DE E-MAIL DESABILITADO.';
		
		debug($parametros,true);
		
		if($_DEBUG)
			return true;
		else
			return false;
	}
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	
	header("Access-Control-Allow-Origin: *");
	
	if($_REQUEST['pub_id']){
		if(!$_SESSION[$_SYSTEM['ID'].'webservices-pub-id']){
			if(!$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado']){
				$pub_id = $_SESSION[$_SYSTEM['ID'].'webservices-pub-id'] = $_REQUEST['pub_id'];
				
				sleep(2);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuario',
						'usuario',
						'nome',
						'email',
					))
					,
					"usuario",
					"WHERE pub_id='".$pub_id."'"
					." AND status='A'"
				);
				
				if($resultado){
					if($_REQUEST['_b2make-form-id']){
						$id_site_formularios = $_REQUEST['_b2make-form-id'];
						$id_usuario = $resultado[0]['id_usuario'];
						
						$resultado2 = banco_select_name
						(
							banco_campos_virgulas(Array(
								'assunto',
								'email',
							))
							,
							"site_formularios",
							"WHERE id_site_formularios='".$id_site_formularios."'"
							." AND id_usuario='".$id_usuario."'"
						);
						
						if($resultado2){
							$email = $resultado[0]['email'];
							$nome = ($resultado[0]['nome'] ? $resultado[0]['nome'] : $resultado[0]['usuario']);
							
							$assunto = $resultado2[0]['assunto'];
							
							$_SESSION[$_SYSTEM['ID'].'webservices-pub-id'] = false;
							$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado'] = false;
							$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num'] = 0;
							
							$email = ($resultado2[0]['email'] ? $resultado2[0]['email'] : $email);
							
							$campo_nome = "data"; $campo_valor = "NOW()"; 							$campos[] = Array($campo_nome,$campo_valor,true);
							$campo_nome = "status"; $campo_valor = "A"; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							
							banco_insert_name
							(
								$campos,
								"site_contatos"
							);
							
							$id_site_contatos = banco_last_id();
							
							$resultado3 = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_site_formularios_campos',
									'campo',
									'title',
									'tipo',
								))
								,
								"site_formularios_campos",
								"WHERE id_site_formularios='".$id_site_formularios."'"
							);
							
							/* foreach($_REQUEST as $key => $value){
								$_REQUEST[$key] = $_REQUEST[$key];
								
								$id_site_formularios_campos = false;
								
								if($resultado3)
								foreach($resultado3 as $res){
									if($res['campo'] == $key){
										$id_site_formularios_campos = $res['id_site_formularios_campos'];
										$title = $res['title'];
										break;
									}
								}
								
								if($id_site_formularios_campos){
									$campos = null;
									
									$campo_nome = "id_site_contatos"; $campo_valor = $id_site_contatos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "id_site_formularios_campos"; $campo_valor = $id_site_formularios_campos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "valor"; $campo_valor = $_REQUEST[$key]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									
									banco_insert_name
									(
										$campos,
										"site_contatos_formularios_campos"
									);
									
									$parametros['mensagem'] .= '<b>'.$title.':</b> '.$_REQUEST[$key].'<br>';
								}
							} */
							
							if($resultado3){
								foreach($resultado3 as $res){
									$campo = $res['campo'];
									$tipo = $res['tipo'];
									$title = $res['title'];
									$id_site_formularios_campos = $res['id_site_formularios_campos'];
									$found = false;
									$campo_valor_aux = '';
									
									switch($tipo){
										case 'text':
										case 'textarea':
											if($_REQUEST)
											foreach($_REQUEST as $key => $value){
												if($campo == $key){
													$found = true;
													
													if($tipo == 'textarea'){
														$_REQUEST[$key] = nl2br($_REQUEST[$key]);
													}
													
													$campo_valor_aux = $_REQUEST[$key];
													break;
												}
											}
										break;
										case 'select':
											if($_REQUEST)
											foreach($_REQUEST as $key => $value){
												if($campo == $key){
													if($_REQUEST[$key] != '-1'){
														$found = true;
														$campo_id = $_REQUEST[$key];
														
														$resultado4 = banco_select_name
														(
															banco_campos_virgulas(Array(
																'nome',
															))
															,
															"site_formularios_campos_opcoes",
															"WHERE id_site_formularios_campos_opcoes='".$campo_id."'"
															." AND id_site_formularios_campos='".$id_site_formularios_campos."'"
														);
														
														$campo_valor_aux = $resultado4[0]['nome'];
														
														break;
													}
												}
											}
										break;
										case 'checkbox':
											$count = 1;
											
											$resultado4 = banco_select_name
											(
												banco_campos_virgulas(Array(
													'nome',
													'id_site_formularios_campos_opcoes',
												))
												,
												"site_formularios_campos_opcoes",
												"WHERE id_site_formularios_campos='".$id_site_formularios_campos."'"
												." ORDER BY nome ASC"
											);
											
											if($resultado4)
											foreach($resultado4 as $res4){
												$campo_aux = $campo.'_'.$count;
												foreach($resultado4 as $res5){
													if($_REQUEST[$campo_aux] == $res5['id_site_formularios_campos_opcoes']){
														$found = true;
														
														$campo_valor_aux .= ($campo_valor_aux?', ':'').$res5['nome'];
														
														break;
													}
												}
												
												$count++;
											}
										break;
									}
									
									if($found){
										$campos = null;
										
										$campo_nome = "id_site_contatos"; 				$campo_valor = $id_site_contatos; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
										$campo_nome = "id_site_formularios_campos"; 	$campo_valor = $id_site_formularios_campos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
										$campo_nome = "valor"; 							$campo_valor = $campo_valor_aux; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
										
										banco_insert_name
										(
											$campos,
											"site_contatos_formularios_campos"
										);
										
										$parametros['mensagem'] .= '<b>'.$title.':</b> '.$campo_valor_aux.'<br>';
									}
								}
							}
							
							$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
							
							$parametros['from_name'] = $_HTML['TITULO'];
							$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
							
							$parametros['email_name'] = $nome;
							$parametros['email'] = $email;
							
							$parametros['subject'] = ($assunto ? $assunto : 'Contato - Site '.$_HTML['TITULO']);
							
							enviar_mail_2($parametros);
							
							$saida = Array(
								'status' => 'Ok'
							);
						} else {
							$saida = Array(
								'status' => 'FormIdFromOtherUser'
							);
						}
					} else {
						if(
							$_REQUEST['form_contato-nome'] &&
							$_REQUEST['form_contato-email'] &&
							$_REQUEST['form_contato-comentario']
						){
							$id_usuario = $resultado[0]['id_usuario'];
							$email = $resultado[0]['email'];
							$nome = ($resultado[0]['nome'] ? $resultado[0]['nome'] : $resultado[0]['usuario']);
							
							$_SESSION[$_SYSTEM['ID'].'webservices-pub-id'] = false;
							$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado'] = false;
							$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num'] = 0;
							
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									'form_contato_email',
								))
								,
								"site",
								"WHERE id_usuario='".$id_usuario."'"
								." AND atual IS TRUE"
							);
							
							$email = ($resultado[0]['form_contato_email'] ? $resultado[0]['form_contato_email'] : $email);
							
							$_REQUEST["form_contato-nome"] = $_REQUEST["form_contato-nome"];
							$_REQUEST["form_contato-email"] = $_REQUEST["form_contato-email"];
							$_REQUEST["form_contato-comentario"] = $_REQUEST["form_contato-comentario"];
							
							$campo_nome = "nome"; $post_nome = "form_contato-nome"; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
							$campo_nome = "email"; $post_nome = "form_contato-email"; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
							$campo_nome = "mensagem"; $post_nome = "form_contato-comentario"; 		if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
							$campo_nome = "data"; $campo_valor = "NOW()"; 							$campos[] = Array($campo_nome,$campo_valor,true);
							$campo_nome = "status"; $campo_valor = "A"; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							
							banco_insert_name
							(
								$campos,
								"site_contatos"
							);
							
							$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
							
							$parametros['from_name'] = $_HTML['TITULO'];
							$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
							
							$parametros['email_name'] = $nome;
							$parametros['email'] = $email;
							
							$parametros['subject'] = 'Contato - Site '.$_HTML['TITULO'];
							
							$parametros['mensagem'] = "<h1>Contato Feito no seu Site</h1>\n";
							$parametros['mensagem'] .= "<p>Nome: ".$_REQUEST['form_contato-nome']."<br />\n";
							$parametros['mensagem'] .= "Email: <a href=\"mailto:".$_REQUEST['form_contato-email']."\">".$_REQUEST['form_contato-email']."</a></p>\n";
							$parametros['mensagem'] .= "<p>Comentário: ".$_REQUEST['form_contato-comentario']."</p>\n";
							
							enviar_mail_2($parametros);
							
							$saida = Array(
								'status' => 'Ok'
							);
						}
					}
				} else {
					if(!$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num']) $_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num'] = 1; else $_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num']++;
					
					$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado'] = true;
					sleep($_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num']);
					
					$saida = Array(
						'status' => 'PubIdDontExist'
					);
				}
			} else {
				$_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num']++;
				sleep($_SESSION[$_SYSTEM['ID'].'webservices-pub-id-errado-num']);
				
				$saida = Array(
					'status' => 'PubIdDontExist'
				);
			}
		} else {
			$saida = Array(
				'status' => 'MuchRequestsPerTime'
			);
		}
	} else {
		$saida = Array(
			'status' => 'PubIdError'
		);
	}
	
	echo json_encode($saida);
}

main();

?>