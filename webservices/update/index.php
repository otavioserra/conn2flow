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

$_VERSAO_MODULO				=	'1.0.1';
$_INCLUDE_MAILER			=	true;
$_PUBLICO					=	true;
$_LOCAL_ID					=	"update";
$_CAMINHO_RELATIVO_RAIZ		=	"../../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

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