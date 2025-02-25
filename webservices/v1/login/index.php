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

$_VERSAO_MODULO				=	'1.0.0';
$_INCLUDE_MAILER			=	true;
$_PUBLICO					=	true;
$_LOCAL_ID					=	"ws-login";
$_CAMINHO_RELATIVO_RAIZ		=	"../../../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function strem_php(){
	$json = file_get_contents('php://input');
	$_REQUEST = json_decode($json, TRUE);
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	global $_WEBSERVICES;
	global $_REMOTE_ADDR;
	global $_PROJETO;
	
	$tempo_delay = 3;
	
	header("Access-Control-Allow-Origin: *");
	strem_php();
	
	banco_delete
	(
		"bad_list",
		"WHERE UNIX_TIMESTAMP(data_primeira_tentativa) < ".(time()-$_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']).""
	);
	
	$bad_list = banco_select_name
	(
		banco_campos_virgulas(Array(
			'num_tentativas_login',
		))
		,
		"bad_list",
		"WHERE ip='".$_REMOTE_ADDR."'"
	);
	
	if($bad_list[0]['num_tentativas_login'] < $_SYSTEM['LOGIN_MAX_TENTATIVAS'] - 1){
		if(!$bad_list){
			$numero_tentativas = 1;
			
			$campos = null;
			
			$campo_nome = "ip"; $campo_valor = $_REMOTE_ADDR; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "num_tentativas_login"; $campo_valor = 1; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_primeira_tentativa"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"bad_list"
			);
		} else {
			$numero_tentativas = ($bad_list[0]['num_tentativas_login'] + 1);
			
			$campo_tabela = "tabela";
			
			$campo_nome = "num_tentativas_login"; $campo_valor = $numero_tentativas; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";

			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"bad_list",
					"WHERE ip='".$_REMOTE_ADDR."'"
				);
			}
			$editar = false;$editar_sql = false;
		}
		
		if($_REQUEST['user'] && $_REQUEST['pass']){
			$permicao = false;
			$perfil_gestor = true;
			
			$user = $_REQUEST['user'];
			$pass = $_REQUEST['pass'];
			
			$usuarios = banco_select_name(
				"*",
				"usuario",
				"WHERE usuario='".$user."' AND status!='D'"
			);
			
			if($usuarios){
				if(crypt($pass, $usuarios[0]['senha']) == $usuarios[0]['senha']){
					if($usuarios[0]['status'] != "A"){
						$bloqueio_user = true;
					} else if($usuarios[0]['id_usuario_pai']){
						$bloqueio_user_pai = true;
						$id_usuario_perfil = $usuarios[0]['id_usuario_perfil'];
						
						$perfis_pdv = $_PROJETO['STORE_PERFIS_PDV'];
						$perfis_gestor = $_PROJETO['STORE_PERFIS_GESTOR'];
						
						
						if($perfis_pdv)
						foreach($perfis_pdv as $pdv_id){
							if($pdv_id == $id_usuario_perfil){
								$bloqueio_user_pai = false;
								$perfil_gestor = false;
								$permicao = true;
								break;
							}
						}
						
						if($perfis_gestor)
						foreach($perfis_gestor as $gestor_id){
							if($gestor_id == $id_usuario_perfil){
								$bloqueio_user_pai = false;
								$permicao = true;
								break;
							}
						}
					} else {
						$permicao = true;
					}
				}
			}
			
			if($permicao){
				$sessao_mobile = md5(crypt($usuarios[0]['senha']).mt_rand());
				$usuarios[0]['sessao_mobile'] = $sessao_mobile;
				
				banco_update
				(
					"sessao_mobile='".$sessao_mobile."',".
					"data_login=NOW()",
					"usuario",
					"WHERE id_usuario='".$usuarios[0]['id_usuario']."'"
				);
				banco_delete
				(
					"bad_list",
					"WHERE ip='".$_REMOTE_ADDR."'"
				);
				
				$_SESSION[$_SYSTEM['ID']."usuario_mobile"] = $usuarios[0];
				
				$loja = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_loja',
					))
					,
					"loja",
					"WHERE id_usuario='".($usuarios[0]['id_usuario_pai'] ? $usuarios[0]['id_usuario_pai'] : $usuarios[0]['id_usuario'])."'"
				);
				
				$saida = Array(
					'status' => 'Ok',
					'token' => $sessao_mobile,
					'perfil' => ($perfil_gestor ? 'gestor' : 'pdv'),
					'id_usuario' => $usuarios[0]['id_usuario'],
					'id_usuario_pai' => $usuarios[0]['id_usuario_pai'],
					'email' => $usuarios[0]['email'],
					'nome' => $usuarios[0]['nome'],
					'data_cadastro' => $usuarios[0]['data_cadastro'],
					'avatar' => $usuarios[0]['avatar'],
					'id_loja' => $loja[0]['id_loja'],
				);
			} else {
				sleep($tempo_delay);
				if($bloqueio_user){
					$saida = Array(
						'status' => 'UserBlockedOnSystem',
						'message' => 'Seu usuário está bloqueado no sistema. Entre em contato com o suporte através do email: <a href="mailto:b2make@b2make.com>b2make@b2make.com</a>.',
					);
				} else if($bloqueio_user_pai){
					$saida = Array(
						'status' => 'UserWithoutPermission',
						'message' => 'Seu usuário não tem permissão para acessar o aplicativo. Entre em contato com o suporte através do email: <a href="mailto:b2make@b2make.com>b2make@b2make.com</a> para saber como proceder.',
					);
				} else {
					$saida = Array(
						'status' => 'UserOrPassInvalid',
						'message' => 'Usuário e/ou Senha inválidos. É obrigatório informar ambos os campos de forma correta.',
					);
				}
			}
		} else {
			sleep($tempo_delay);
			$saida = Array(
				'status' => 'UserOrPassDontInformed',
				'message' => 'Usuário e/ou Senha não informados. É obrigatório informar ambos os campos de forma correta.',
			);
		}
	} else {
		sleep($tempo_delay);
		$saida = Array(
			'status' => 'UserBlockedForLotOfAttemptLogin',
			'message' => 'Por segurança, você foi bloqueado momentaneamente para tentar acessar o B2make. Você excedeu o limite de '.floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60.' tentativas de login num período de '.$_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS'].' minutos. Aguarde esse tempo e tente novamente.'),
		);
	}
	
	echo json_encode($saida);
}

main();

?>