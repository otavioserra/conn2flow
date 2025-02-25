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

$_VERSAO_MODULO				=	'3.8.1';
$_INCLUDE_MAILER			=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_PROCURAR			=	true;
$_INCLUDE_CONTEUDO			=	true;
$_INCLUDE_MOBILE_INTERFACE	=	false;
$_INCLUDE_MOBILE_INTERFACE2	=	true;
$_INCLUDE_HISTORY			=	true;
$_INCLUDE_PUBLISHER			=	true;
$_PUBLICO					=	true;
$_LOCAL_ID					=	"index";
$_CAMINHO_RELATIVO_RAIZ		=	"";
$_MODULOS_TAGS = Array(
	'#formulario_contato#',
	'#galerias#',
	'#formulario_usuario#',
);

include($_CAMINHO_RELATIVO_RAIZ."config.php");
include($_SYSTEM['INCLUDE_PROJETO_PATH']."projeto.php");

$_SYSTEM['USER_NOME_NO_DEFAULT'] = true;

if($_SESSION[$_SYSTEM['ID']."usuario"]){
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	if(!isset($usuario['avatar'])) $usuario['avatar'] = false;
	if($usuario['avatar'])$_VARIAVEIS_JS['avatar'] = $_B2MAKE_URL_SEM_SLASH . $usuario['avatar'];
}

$_HTML['LAYOUT']			=	$_SYSTEM['TEMA_PATH']."layout.html";
$_HTML['MOBILE']			=	$_SYSTEM['TEMA_PATH']."mobile.html";
$_HTML['titulo'] 			= 	$_HTML['titulo'].$_HTML['sub_titulo'];

$_MENU_ID['menu_noticias'] = 'noticias';
$_MENU_ID['menu_blog'] = 'blog';

if(isset($_PROJETO['mobile']))
if($_PROJETO['mobile']['layout'])
	$_LAYOUT_MOBILE = $_PROJETO['mobile']['layout'];
	
if(!isset($_LAYOUT_MOBILE)){
	$_LAYOUT_MOBILE = '
	<div data-role="page" data-add-back-btn="true" data-back-btn-text="Voltar"><div data-role="header" data-theme="a"> 
		<h1>#title#</h1>
		<a href="!#caminho_raiz#!" class="ui-btn-right" data-icon="home" data-iconpos="notext" data-direction="reverse">Home</a> 
	</div>
	<div data-role="content" data-theme="c">
	#body#
	</div>
	<div data-role="footer" class="footer-docs" data-theme="c" style="text-align: center;">
		<a href="/'.$_SYSTEM['ROOT'].'?mobile-versao-web=sim" rel="external" data-role="button" data-inline="true" class="ui-link" style="text-align: center;  margin: 0 auto;">Acessar versão web</a>
	</div>';
}

// Funções de assistência

function alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	global $_MENSAGEM_ERRO;
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_MOBILE;
	global $_VARIAVEIS_JS;
	global $_ESERVICE;
	
	if($_ESERVICE['iframe']){
		$_SESSION[$_SYSTEM['ID'].'iframe-redirect'] = true;
		$_SESSION[$_SYSTEM['ID']."alerta-proximo"] = $nAlerta;
	} else {
		if(!$_DESATIVAR_PADRAO['login']){
			switch ($nAlerta){
				case 1:		$mensSaida	=	"<p>Mensagem Enviada com sucesso!</p><p>Em breve um de nossos atendentes entrará em contato!</p>";break;
				case 2:		$mensSaida	=	"<p>Usuário inexistente!</p><p>Nota: Favor preencher corretamente o nome de usuário do sistema.</p><p>".$_MENSAGEM_ERRO."</p>";																			break;
				case 3: 	$mensSaida	=	"<p>Usuário inativado no sistema!</p><p>Nota: Favor entrar em contato com administrador do sistema para reativa-lo!</p><p>Contato: " . $_SYSTEM['ADMIN_EMAIL'] . "</p><p>".$_MENSAGEM_ERRO."</p>";				break;
				case 4: 	$mensSaida	=	"<p>Você atingiu a quantidade limite de tentativas de login nesse período!</p><p>Nota: Por motivos de segurança você deve aguardar ".floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60)." minuto(s) antes de tentar novamente!</p><p>Qualquer dúvida entre em contato pelo e-mail: <a href=\"mailto:".$_SYSTEM['ADMIN_EMAIL']."\">" . $_SYSTEM['ADMIN_EMAIL'] . "</a></p>";			break;
				case 5:		$mensSaida	=	"<p>E-MAIL DESCADASTRADO COM SUCESSO!</p><p>Você não receberá mais nossos e-mails!</p>";break;
				case 6:		$mensSaida	=	"<p>Senha Incorreta!</p><p>Nota: Favor preencher corretamente a senha.</p><p>".$_MENSAGEM_ERRO."</p>";																			break;
				//case 1:		$mensSaida	=	"";break;
				default:	$mensSaida	=	$nAlerta;
			}

			$_ALERTA = $mensSaida;
			
			if($_MOBILE){
				$_VARIAVEIS_JS['alerta'] = $_ALERTA;
			}
		}
	}
}

// Funções b2make

function b2make_preview(){
	global $_CAMINHO;
	global $_SYSTEM;
	global $_PAGINA_SEM_PROCESSAMENTO;
	global $_B2MAKE_INC;
	global $_PROJETO_VERSAO;
	
	$id = $_CAMINHO[1];
	
	if(!$id){
		redirecionar('/');
	} else {
		$pagina = modelo_abrir($_SYSTEM['PATH'].'design'.$_SYSTEM['SEPARADOR'].'layout-site.html');
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'html',
			))
			,
			"site_templates",
			"WHERE id='".$id."'"
			." AND status='A'"
		);
		
		if($resultado){
			if($resultado[0]['nome'])$pagina_titulo = 'B2make Modelo - '.$resultado[0]['nome'];
			if($resultado[0]['html'])$html = $resultado[0]['html'];
			
			$preview_js = '
	<script type="text/javascript" src="'.$_B2MAKE_INC.'b2make-site-preview.js?v='.$_PROJETO_VERSAO.'"></script>'."\n".
"	<script src=\"https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js\"></script>\n";
			
			$pagina = modelo_var_troca($pagina,"#b2make-head-title#",($pagina_titulo?$pagina_titulo:'Título da Página'));
			$pagina = modelo_var_troca($pagina,"<!-- b2make-meta -->",$meta);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-css -->",$favicon.$_SYSTEM['SITE']['b2make-css']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-jquery -->",$_SYSTEM['SITE']['jquery']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-js -->",$_SYSTEM['SITE']['b2make-js']);
			$pagina = modelo_var_troca($pagina,"<!-- b2make-js-extra -->",$_SYSTEM['SITE']['js-extra'] . $preview_js);
			$pagina = modelo_var_troca($pagina,"#b2make-body#",$html);
			
			$_PAGINA_SEM_PROCESSAMENTO = true;
			
			$preview = $pagina;
		} else {
			alerta('Modelo não encontrado!');
			redirecionar('/');
		}
		
		return $preview;
	}
}

function instagram_authorization(){
	global $_PROJETO;
	global $_SYSTEM;
	global $_PAGINA_SEM_PROCESSAMENTO;
	
	$_PAGINA_SEM_PROCESSAMENTO = true;
	
	if($_REQUEST['trocar_conta']){
		$_SESSION[$_SYSTEM['ID']."instagram_trocar_conta"] = true;
	}
	
	header('Location: https://api.instagram.com/oauth/authorize/?app_id='.$_PROJETO['INSTAGRAM_CLIENT_ID'].'&redirect_uri='.urlencode('https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'instagram-redirect').'&response_type=code&scope=user_profile,user_media');
}

function instagram_authorized(){
	global $_PROJETO;
	global $_SYSTEM;
	global $_PAGINA_SEM_PROCESSAMENTO;
	
	$_PAGINA_SEM_PROCESSAMENTO = true;
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$usuario){
		echo 'Sem permissão de acesso';
		exit;
	}
	
	if($_REQUEST['code']){
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_URL, 'https://api.instagram.com/oauth/access_token');
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
			'app_id' => $_PROJETO['INSTAGRAM_CLIENT_ID'],
			'app_secret' => $_PROJETO['INSTAGRAM_CLIENT_SECRET'],
			'grant_type' => 'authorization_code',
			'redirect_uri' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'instagram-redirect',
			'code' => $_REQUEST['code'],
		)));
		
		$response = curl_exec($curl);
		curl_close($curl);
		
		$res = json_decode($response);
		
		if($_SESSION[$_SYSTEM['ID']."instagram_trocar_conta"]){
			$_SESSION[$_SYSTEM['ID']."instagram_trocar_conta"] = false;
			
			if($_SESSION[$_SYSTEM['ID']."site"]['instagram_token'] == $res->access_token){
				$mesmo_usuario = true;
			}
		}

		$modelo = modelo_abrir($_SYSTEM['PATH'].'design/instagram-autorizar.html');
		
		if(!$mesmo_usuario){
			banco_update
			(
				"instagram_token='".$res->access_token."'",
				"site",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND atual IS NOT NULL"
			);
			
			$_SESSION[$_SYSTEM['ID']."site"]['instagram_token'] = $res->access_token;
			
			$outra_conta = 'sim';
		} else {
			$outra_conta = 'nao';
		}
		
		$modelo = modelo_var_troca($modelo,"#outra_conta#",$outra_conta);
		$modelo = modelo_var_troca($modelo,"#instagram_token#",$res->access_token);
		
		echo $modelo;		
	} else if($_REQUEST['error']){
		echo 'Erro: '.$_REQUEST['error'];
	} else {
		echo 'Erro nao definido';
	}
	
}

function instagram_redirect(){
	return 'Ok';
}

function b2make_my_profile_ftp_passwd_2(){
	global $_SYSTEM;
	global $_CPANEL;
	
	$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($usuario['id_usuario_pai']){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'user_cpanel',
				'server',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario_pai']."'"
			." AND atual IS TRUE"
		);
		
		$user_cpanel = $resultado[0]['user_cpanel'];
		$server = $resultado[0]['server'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'ftp_site_user',
				'ftp_site_pass',
				'ftp_files_user',
				'ftp_files_pass',
			))
			,
			"usuario",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
		);
		
		$ftp_site_user = $resultado[0]['ftp_site_user'];
		$ftp_site_pass = $resultado[0]['ftp_site_pass'];
		$ftp_files_user = $resultado[0]['ftp_files_user'];
		$ftp_files_pass = $resultado[0]['ftp_files_pass'];
		
		$_SESSION[$_SYSTEM['ID']."b2make-host"] = false;

		$_CPANEL['CPANEL_USER'] = $user_cpanel;
		$_CPANEL['FTP_LOCAL'] = $server;
		
		$ftp_site_pass = hashPassword($senha,$ftp_site_pass);
		$user_arr = explode('@',$ftp_site_user);
		$_CPANEL['FTP_PASSWD'] = Array(
			'user' => $user_arr[0],
			'pass' => $ftp_site_pass,
		);
		
		$_CPANEL['CPANEL_USER'] = $user_cpanel;
		if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-passwd.php');
		
		$ftp_files_pass = hashPassword($senha,$ftp_files_pass);
		$user_arr = explode('@',$ftp_files_user);
		$_CPANEL['FTP_PASSWD'] = Array(
			'user' => $user_arr[0],
			'pass' => $ftp_files_pass,
		);
		
		$_CPANEL['CPANEL_USER'] = $user_cpanel;
		if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-passwd.php');
		
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-pass'] = $ftp_site_pass;
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-pass'] = $ftp_files_pass;
	} else {
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'user_cpanel',
				'server',
				'ftp_site_user',
				'ftp_files_user',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		$server = $resultado[0]['server'];
		$user_cpanel = $resultado[0]['user_cpanel'];
		$ftp_site_user = $resultado[0]['ftp_site_user'];
		$ftp_files_user = $resultado[0]['ftp_files_user'];
		$ftp_site_pass = ($_SERVER['SERVER_NAME'] == "localhost" ? md5(rand()) : getToken() );
		$ftp_files_pass = ($_SERVER['SERVER_NAME'] == "localhost" ? md5(rand()) : getToken() );
		
		banco_update
		(
			"ftp_site_pass='".$ftp_site_pass."',".
			"ftp_files_pass='".$ftp_files_pass."'",
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		$_SESSION[$_SYSTEM['ID']."b2make-host"] = false;
		
		$_CPANEL['CPANEL_USER'] = $user_cpanel;
		$_CPANEL['FTP_LOCAL'] = $server;
		
		$ftp_site_pass = hashPassword($senha,$ftp_site_pass);
		
		$user_arr = explode('@',$ftp_site_user);
		$_CPANEL['FTP_PASSWD'] = Array(
			'user' => $user_arr[0],
			'pass' => $ftp_site_pass,
		);
		
		if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-passwd.php');
		
		$ftp_files_pass = hashPassword($senha,$ftp_files_pass);
		
		$user_arr = explode('@',$ftp_files_user);
		$_CPANEL['FTP_PASSWD'] = Array(
			'user' => $user_arr[0],
			'pass' => $ftp_files_pass,
		);
		
		if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-passwd.php');
		
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-pass'] = $ftp_site_pass;
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-pass'] = $ftp_files_pass;
	}
}

// Funções de Logar no sistema

function login(){
	global $_MENSAGEM_ERRO;
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_PROJETO;
	global $_HTML_DADOS;
	global $_MOBILE;
	global $_LAYOUT_MOBILE;
	global $_REDIRECT_PAGE;
	
	if(!$_DESATIVAR_PADRAO['login']){
		if($_SESSION[$_SYSTEM['ID']."permissao"]){
			$_SESSION[$_SYSTEM['ID']."redirecionar"] = true;
			
			// ============================== Ecommerce
			
			if($_SESSION[$_SYSTEM['ID'].'ecommerce-itens'])	require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/cadastrar-pedido.php');
			
			// ============================== Mudar Local Padrão de logar
			
			if($_SESSION[$_SYSTEM['ID'].'logar-local']){
				$local = $_SESSION[$_SYSTEM['ID'].'logar-local'];
				$_SESSION[$_SYSTEM['ID'].'logar-local'] = false;
			}
			
			$_REDIRECT_PAGE = true;
			redirecionar($local);
		} else {
			$_HTML_DADOS['noindexNofollow'] = true;
			
			$login_path = 'includes'.$_SYSTEM['SEPARADOR'].'index.html';
			
			if($_PROJETO['index']){
				if($_PROJETO['index']['login_path']){
					$login_path = $_PROJETO['index']['login_path'];
				}
			}
			
			$pagina = paginaModelo($_SYSTEM['PATH'].$login_path);
			$pagina = paginaTagValor($pagina,'<!-- login < -->','<!-- login > -->');
			$pagina = paginaTrocaVarValor($pagina,'#titulo',"Autenticação");
			$pagina = paginaTrocaVarValor($pagina,'#letreiro',$_MENSAGEM_ERRO);
			
			if($_MOBILE){
				$layout = $_LAYOUT_MOBILE;
				
				$layout = modelo_var_troca($layout,"#title#",'Login');
				$layout = modelo_var_troca($layout,"#body#",$pagina);
				
				return $layout;
			} else {
				return $pagina.pagina_inicial();
			}
		}
	}
}

function logar(){
	global $_SYSTEM;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_HTML;
	global $_LOCAL_ID;
	global $_MENSAGEM_ERRO;
	global $_REMOTE_ADDR;
	global $_DESATIVAR_PADRAO;
	global $_OPCAO;
	global $_REDIRECT_PAGE;
	global $_ECOMMERCE;
	global $_PROJETO;
	global $_LOGAR_REDIRECT_LOGIN;
	global $_B2MAKE_PAGINA_LOCAL;
	
	if(!$_DESATIVAR_PADRAO['logar']){
		$_SESSION[$_SYSTEM['ID']."usuario"] = false;
		$_SESSION[$_SYSTEM['ID']."permissao"] = false;
		$_SESSION[$_SYSTEM['ID']."permissao_id"] = false;
		$_SESSION[$_SYSTEM['ID']."admin"] = false;
		$_SESSION[$_SYSTEM['ID']."modulos"] = false;
		$_SESSION[$_SYSTEM['ID']."modulos_operacao"] = false;
		$_SESSION[$_SYSTEM['ID']."upload_permissao"] = false;
		
		$usuario	=	$_REQUEST["usuario"];
		$senha		=	$_REQUEST["senha"];
		
		banco_conectar();
		
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
			$usuarios = banco_select_name(
				"*",
				"usuario",
				"WHERE usuario='".$usuario."' AND status!='D'"
			);
			
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
			
			$_MENSAGEM_ERRO = 'Você pode tentar mais <b>'.($_SYSTEM['LOGIN_MAX_TENTATIVAS'] - $numero_tentativas).'</b> vezes antes que sua conta seja bloqueada por '.floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60).' minutos(s).';
			$_OPCAO = 'login';
			//$pagina = login();
			
			if($_LOGAR_REDIRECT_LOGIN)$redirect_login = $_LOGAR_REDIRECT_LOGIN; else $redirect_login = 'autenticar';
			
			if($usuarios){
				if(crypt($senha, $usuarios[0]['senha']) == $usuarios[0]['senha']){
					if($usuarios[0]['status'] != "A"){
						seguranca_delay();
						alerta(3);
					} else {
						$senha_sessao = md5(password_hash($usuarios[0]['senha'], PASSWORD_DEFAULT).mt_rand());
						$usuarios[0]['senha_sessao'] = $senha_sessao;
						
						banco_update
						(
							"senha_sessao='".$senha_sessao."',".
							"data_login=NOW()",
							"usuario",
							"WHERE usuario='".$usuario."' AND status!='D'"
						);
						banco_delete
						(
							"bad_list",
							"WHERE ip='".$_REMOTE_ADDR."'"
						);
						
						if(!$usuarios[0]['pub_id']){
							$usuarios[0]['pub_id'] = md5(uniqid(rand(), true));
							
							banco_update
							(
								"pub_id='".$usuarios[0]['pub_id']."'",
								"usuario",
								"WHERE usuario='".$usuario."' AND status!='D'"
							);
						}
						
						$_SESSION[$_SYSTEM['ID']."usuario"] = $usuarios[0];
						$_SESSION[$_SYSTEM['ID']."usuario_senha"] = $senha;
						
						$_SESSION[$_SYSTEM['ID']."redirecionar"] = true;
						$_SESSION[$_SYSTEM['ID']."permissao"] = true;
						$_SESSION[$_SYSTEM['ID']."permissao_id"] = $usuarios[0]['id_usuario_perfil'];
						
						if($usuarios[0]['id_usuario_perfil'] == 1){
							$_SESSION[$_SYSTEM['ID']."admin"] = true;
						} else {
							
							// ================================= Definição dos Módulos ===============================
							
							$usuario_perfil_modulo = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_modulo',
								))
								,
								"usuario_perfil_modulo",
								"WHERE id_usuario_perfil='".$usuarios[0]['id_usuario_perfil']."'"
							);
							
							$modulos = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_modulo',
									'caminho',
								))
								,
								"modulo",
								""
							);
							
							if($usuario_perfil_modulo)
							foreach($usuario_perfil_modulo as $perfil_modulo){
								foreach($modulos as $modulo){
									if($perfil_modulo['id_modulo'] == $modulo['id_modulo']){
										$permissao_modulos[$modulo['caminho']] = true;
									}
								}
							}
							
							$_SESSION[$_SYSTEM['ID']."modulos"] = $permissao_modulos;
							
							// ================================= Definição das Operações nos Módulos ===============================
							
							$usuarios_perfils_modulos_operacao = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_modulo_operacao',
								))
								,
								"usuario_perfil_modulo_operacao",
								"WHERE id_usuario_perfil='".$usuarios[0]['id_usuario_perfil']."'"
							);
							
							$modulos_operacao = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_modulo_operacao',
									'id_modulo',
									'caminho',
								))
								,
								"modulo_operacao",
								""
							);
							
							if($usuarios_perfils_modulos_operacao && $modulos_operacao)
							foreach($usuarios_perfils_modulos_operacao as $usuario_perfil_modulo_operacao){
								foreach($modulos_operacao as $modulo_operacao){
									if($usuario_perfil_modulo_operacao['id_modulo_operacao'] == $modulo_operacao['id_modulo_operacao']){
										foreach($modulos as $modulo){
											if($modulo_operacao['id_modulo'] == $modulo['id_modulo']){
												$permissao_modulos_operacao[$modulo['caminho']][$modulo_operacao['caminho']] = true;
												break;
											}
										}
									}
								}
							}
							
							$_SESSION[$_SYSTEM['ID']."modulos_operacao"] = $permissao_modulos_operacao;
						}
						
						// ============================== Ecommerce
						
						if($_SESSION[$_SYSTEM['ID'].'ecommerce-itens'])	require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/cadastrar-pedido.php');

						// ============================== B2Make
						
						if(function_exists('signature_account_diskstats') && !$_B2MAKE_PAGINA_LOCAL)call_user_func('signature_account_diskstats');

						$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
						
						if(!$_SESSION[$_SYSTEM['ID']."b2make-segmentos"]){
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_site_templates',
								))
								,
								"site",
								"WHERE id_usuario='".$usuarios[0]['id_usuario']."'"
								." AND atual IS TRUE"
							);
							
							if($resultado){
								$resultado2 = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id_site_segmentos',
									))
									,
									"site_templates",
									"WHERE id_site_templates='".$resultado[0]['id_site_templates']."'"
								);
								
								$_SESSION[$_SYSTEM['ID']."b2make-templates"] = $resultado[0]['id_site_templates'];
								$_SESSION[$_SYSTEM['ID']."b2make-segmentos"] = $resultado2[0]['id_site_segmentos'];
							}
						} else {
							if(!$_SESSION[$_SYSTEM['ID']."b2make-templates"]){
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id_site_templates',
									))
									,
									"site",
									"WHERE id_usuario='".$usuarios[0]['id_usuario']."'"
									." AND atual IS TRUE"
								);
								
								if($resultado){
									$resultado2 = banco_select_name
									(
										banco_campos_virgulas(Array(
											'id_site_segmentos',
										))
										,
										"site_templates",
										"WHERE id_site_templates='".$resultado[0]['id_site_templates']."'"
									);
									
									$_SESSION[$_SYSTEM['ID']."b2make-templates"] = $resultado[0]['id_site_templates'];
									$_SESSION[$_SYSTEM['ID']."b2make-segmentos"] = $resultado2[0]['id_site_segmentos'];
								}
							}
						}
						
						if($_PROJETO['b2make_permissao_id'])
						foreach($_PROJETO['b2make_permissao_id'] as $permissao){
							if($permissao == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
								$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
								
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id_host',
										'url',
										'user_host',
										'url_files',
										'ftp_site_host',
										'ftp_site_user',
										'ftp_site_pass',
										'ftp_site_path',
										'ftp_files_host',
										'ftp_files_user',
										'ftp_files_pass',
										'ftp_files_path',
										'https',
									))
									,
									"host",
									"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
									." AND atual IS TRUE"
								);
								
								$resultado2 = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id_host',
										'id_site',
									))
									,
									"site",
									"WHERE id_site_pai IS NULL"
									." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
								);
								
								if($resultado){
									$_SESSION[$_SYSTEM['ID']."b2make-host"] = true;
									$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
									
									$id_host = $resultado[0]['id_host'];
									$url = $resultado[0]['url'];
									$user_host = $resultado[0]['user_host'];
									$url_files = $resultado[0]['url_files'];
									$ftp_site_host = $resultado[0]['ftp_site_host'];
									$ftp_site_path = $resultado[0]['ftp_site_path'];
									$ftp_files_host = $resultado[0]['ftp_files_host'];
									$ftp_files_path = $resultado[0]['ftp_files_path'];
									$https = ($resultado[0]['https'] ? true : false);
									
									if($usuario['id_usuario_pai']){
										$ftp_site_user = $usuario['ftp_site_user'];
										$ftp_site_pass = hashPassword($senha,$usuario['ftp_site_pass']);
										$ftp_files_user = $usuario['ftp_files_user'];
										$ftp_files_pass = hashPassword($senha,$usuario['ftp_files_pass']);
									} else {
										$ftp_site_user = $resultado[0]['ftp_site_user'];
										$ftp_site_pass = hashPassword($senha,$resultado[0]['ftp_site_pass']);
										$ftp_files_user = $resultado[0]['ftp_files_user'];
										$ftp_files_pass = hashPassword($senha,$resultado[0]['ftp_files_pass']);
									}
									
									$_SESSION[$_SYSTEM['ID']."b2make-site"] =  Array(
										'id_host' => $id_host,
										'url' => $url,
										'user_host' => $user_host,
										'url-files' => $url_files,
										'ftp-site-host' => $ftp_site_host,
										'ftp-site-user' => $ftp_site_user,
										'ftp-site-pass' => $ftp_site_pass,
										'ftp-site-path' => $ftp_site_path,
										'ftp-files-host' => $ftp_files_host,
										'ftp-files-user' => $ftp_files_user,
										'ftp-files-pass' => $ftp_files_pass,
										'ftp-files-path' => $ftp_files_path,
										'https' => $https,
									);
									
									$_SESSION["b2make-tinymce-filemanager"] =  Array(
										'url-files' => $url_files,
										'ftp-files-host' => $ftp_files_host,
										'ftp-files-user' => $ftp_files_user,
										'ftp-files-pass' => $ftp_files_pass,
										'ftp-files-path' => $ftp_files_path,
										'https' => $https,
									);
								}
							}
						}

						// ============================== Logar Usuário Loja
						
						if($usuarios[0]['id_loja_usuarios']){
							$_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"] = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
							
							$id_loja_usuarios = $usuarios[0]['id_loja_usuarios'];
							
							$loja_usuarios = banco_select_name
							(
								"*"
								,
								"loja_usuarios",
								"WHERE id_loja_usuarios='" . $id_loja_usuarios . "'"
							);
							
							$senha_sessao = sha1(password_hash($loja_usuarios[0]['senha'], PASSWORD_DEFAULT).mt_rand());
							$loja_usuarios[0]['senha_sessao'] = $senha_sessao;
							
							banco_update
							(
								"senha_sessao='".$senha_sessao."',".
								"data_login=NOW()",
								"loja_usuarios",
								"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
							);
							
							$_SESSION[$_SYSTEM['ID']."loja_usuarios"] = $loja_usuarios[0];
							
							$_SESSION[$_SYSTEM['ID']."loja-permissao"] = true;
						}
						
						// ============================== Pub ID - Verificar se o usuário é filho de um outro usuário, se for, trocar o PUB_ID do usuário pelo PUB_ID do usuário pai
						
						if($usuarios[0]['id_usuario_pai']){
							$resultado3 = banco_select_name
							(
								banco_campos_virgulas(Array(
									'pub_id',
								))
								,
								"usuario",
								"WHERE id_usuario='".$usuarios[0]['id_usuario_pai']."'"
							);
							
							$_SESSION[$_SYSTEM['ID']."usuario"]['pub_id'] = $resultado3[0]['pub_id'];
						}
						
						// ============================== Mudar Local Padrão de logar
						
						if($_SESSION[$_SYSTEM['ID'].'logar-local']){
							$local = $_SESSION[$_SYSTEM['ID'].'logar-local'];
							$_SESSION[$_SYSTEM['ID'].'logar-local'] = false;
						} else {
							if($_ECOMMERCE['permissao_usuario'] == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
								$local = $_ECOMMERCE['pagina_padrao'];
							}
							
							if($_PROJETO['b2make_permissao_id'])
							foreach($_PROJETO['b2make_permissao_id'] as $id){
								if($id == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
									$local = $_SYSTEM['SITE']['permissao_local_inicial'];
									break;
								}
							}
						}
						
						$_REDIRECT_PAGE = true;
						redirecionar($local);
					}
				} else {
					seguranca_delay();
					alerta(6);
					redirecionar($redirect_login);
				}
			} else {
				seguranca_delay();
				alerta(2);
				redirecionar($redirect_login);
			}
		} else {
			seguranca_delay();
			$_MENSAGEM_ERRO = 'Você atingiu a quantidade limite de tentativas de login nesse período. Por motivos de segurança você deve aguardar '.floor($_SYSTEM['LOGIN_BAD_LIST_PERIODO_SEGUNDOS']/60).' minutos(s) antes de tentar novamente. Qualquer dúvida entre em contato pelo e-mail: '.$_SYSTEM['ADMIN_EMAIL_HTML'].'.';
			alerta(4);
			redirecionar($redirect_login);
		}
		
		banco_fechar_conexao();
		
		if($_REQUEST['ecommerce']){
			redirecionar($redirect_login);
		}
		
		return $pagina;
	}
}

function logado(){
	redirecionar();
}

function redirecionar($local = false,$sem_root = false){
	global $_SYSTEM;
	global $_AJAX_PAGE;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PROJETO;
	global $_REDIRECT_PAGE;
	global $_ALERTA;
	global $_ESERVICE;
	global $_CAMINHO;
	
	if($_ESERVICE['iframe'] && !$_ESERVICE['nao-redirecionar']){
		$url = $_SESSION[$_SYSTEM['ID']."b2make-loja-atual-url"];
		$id = $_CAMINHO[1];
		
		$local = preg_replace('/e\-services\/'.$id.'\//i', '', $local);
		
		if($_AJAX_PAGE){
			$_VARIAVEIS_JS['b2make_loja_url_atual'] = $url . $local . '/';
			$_VARIAVEIS_JS['b2make_loja_iframe_redirect'] = true;
			
			echo pagina_variaveis_js();
		} else {
			$pagina = modelo_abrir($_SYSTEM['TEMA_PATH'].'iframe-redirect.html');
			
			$pagina = modelo_var_troca($pagina,"#url#",$url . $local . '/');
			
			echo $pagina;
		}
		exit(0);
	} else {
		if($local){
			$local = ($sem_root?'':'/' . $_SYSTEM['ROOT']) . ($local == '/' ?'':$local);
		} else {
			switch($_SESSION[$_SYSTEM['ID']."permissao_id"]){
				//case '2': $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = $_CAMINHO_RELATIVO_RAIZ.$_HTML['ADMIN']; break;
				default: $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $_HTML['ADMIN'];
			}
			
			if($_PROJETO['redirecionar']){
				$permissao_id = $_SESSION[$_SYSTEM['ID']."permissao_id"];
				
				if($_PROJETO['redirecionar']['permissao_id']){
					$dados = $_PROJETO['redirecionar']['permissao_id'];
					foreach($dados as $dado){
						if($dado['id'] == $permissao_id) $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $dado['local'];
					}
				}	
			}
			
			$local = $_SESSION[$_SYSTEM['ID']."redirecionar_local"];
		}
		
		if($_AJAX_PAGE){
			if($_REDIRECT_PAGE){
				$_VARIAVEIS_JS['redirecionar'] = $local;
				$_REDIRECT_PAGE = false;
			} else {
				$_VARIAVEIS_JS['redirecionar_ajax'] = $local;
			}
			echo pagina();
			exit(0);
		} else {
			if($_ALERTA)$_SESSION[$_SYSTEM['ID']."alerta"] = $_ALERTA;
			header("Location: ".$local);
			exit(0);
		}
	}
}

function logout(){
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_HTML_DADOS;
	global $_REDIRECT_PAGE;
	
	if(!$_DESATIVAR_PADRAO['logout']){
		$delay = $_SESSION[$_SYSTEM['ID']."delay"];
		
		session_unset();
		
		$_SESSION[$_SYSTEM['ID']."delay"] = $delay;
		
		$_REDIRECT_PAGE = true;
		redirecionar('/');
	}
}

function esqueceu_senha(){
	global $_PROJETO;
	global $_DESATIVAR_PADRAO;
	global $_SYSTEM;
	global $_HTML;
	global $_HTML_DADOS;
	global $_VARIAVEIS_JS;
	global $_VARS;
	
	if(!$_DESATIVAR_PADRAO['esqueceu_senha']){
		$esqueceu_senha_path = 'includes'.$_SYSTEM['SEPARADOR'].'index.html';
		
		if($_PROJETO['index']){
			if($_PROJETO['index']['esqueceu_senha_path']){
				$esqueceu_senha_path = $_PROJETO['index']['esqueceu_senha_path'];
			}
		}
		
		$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
		$_HTML_DADOS['titulo'] = $titulo . 'Esqueceu sua Senha.';
		
		$_HTML_DADOS['description'] = 'Página para recuperação de senha das contas de usuários do sistema.';
		$_HTML_DADOS['keywords'] = 'esqueceu senha,esqueceu,senha,recuperação,recuperacao,recuperação senha';
		
		$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
		
		$pagina = paginaModelo($_SYSTEM['PATH'].$esqueceu_senha_path);
		$pagina = paginaTagValor($pagina,'<!-- esqueceu_senha < -->','<!-- esqueceu_senha > -->');
		
		return $pagina;
	}
}

function esqueceu_senha_banco(){
	global $_SYSTEM;
	global $_HTML;
	global $_ALERTA;
	global $_VARS;
	global $_CONTEUDO_ID_AUX;
	global $_REMOTE_ADDR;
	
	if(recaptcha_verify()){
		banco_conectar();
		
		if($_REQUEST['esqueceu_senha-email']){
			$email = $_REQUEST['esqueceu_senha-email'];
			
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuario',
					'nome',
				))
				,
				"usuario",
				"WHERE email='".$email."' AND status!='D'"
			);
			
			if($usuario){
				if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
				
				$key = crypt(rand().$_REQUEST["email"]);
				$key = preg_replace('/[\$\.\/]/i', '', $key);
				
				banco_update
				(
					"cadastro_key='".$key."'",
					"usuario",
					"WHERE email='".$email."' AND status!='D'"
				);
				
				$id_usuario = $usuario[0]['id_usuario'];
				$nome = $usuario[0]['nome'];
				
				$codigo = date('dmY').zero_a_esquerda($id_usuario,6);
				
				$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
				
				$url = html(Array(
					'tag' => 'a',
					'val' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=gerar-nova-senha&cod='.$codigo.'&key='.$key,
					'attr' => Array(
						'href' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=gerar-nova-senha&cod='.$codigo.'&key='.$key,
					)
				));
				
				$parametros['from_name'] = $_HTML['TITULO'];
				$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
				
				$parametros['email_name'] = strip_tags($nome);
				$parametros['email'] = strip_tags($email);
				
				if($_VARS['autenticar']){
					if($_VARS['autenticar']['esqueceu_senha_assunto']){
						$email_assunto = $_VARS['autenticar']['esqueceu_senha_assunto'];
					}
				}
				if($_VARS['autenticar']){
					if($_VARS['autenticar']['esqueceu_senha_mensagem']){
						$email_mensagem = $_VARS['autenticar']['esqueceu_senha_mensagem'];
					}
				}
				
				if(!$email_assunto){
					$email_assunto = 'Recuperação de senha nº #cod#';
				}
				
				if(!$email_mensagem){
					$email_mensagem = '<h1>Recuperação de senha nº #codigo#</h1>';
					$email_mensagem .= '<p>Para recuperar a sua senha acesse esse link: #url#</p>';
					$email_mensagem .= '<p>Se você não tentou recuperar a sua senha desconsidere esse email automático.</p>';
				}
				
				$parametros['subject'] = $email_assunto;
				$parametros['mensagem'] = $email_mensagem;
				
				$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#cod#",$codigo);
				
				$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$codigo);
				$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#nome#",$nome);
				$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#url#",$url);
				$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
				
				$campos = Array(
					'codigo' => $codigo,
					'nome' => $nome,
					'url' => $url,
				);
				
				$params = Array(
					'id' => 'esqueceu_senha_banco',
					'campos' => $campos,
					'parametros' => $parametros,
				);
				
				$saida = projeto_modificar_campos($params);
				
				$campos = $saida['campos'];
				$parametros = $saida['parametros'];
				
				if($parametros['enviar_mail'])enviar_mail($parametros);
				
				alerta('<p>Foi enviada uma mensagem para o email fornecido. Entre no seu programa de email e siga os passos definidos na mensagem enviada.</p>');
				redirecionar('/');
			} else {
				$_ALERTA = '<p>Email informado <b style="color:red;">inexistente</b>!<p></p>Preencha o email corretamente e envie novamente!</p>';
				alerta($_ALERTA);
				redirecionar('esqueceu-sua-senha');
			}
		} else {
			$_ALERTA = '<p>Email informado <b style="color:red;">inexistente</b>!<p></p>Preencha o email corretamente e envie novamente!</p>';
			alerta($_ALERTA);
			redirecionar('esqueceu-sua-senha');
		}
	} else {
		$_ALERTA = '<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código recaptcha especificado é inválido!</p>';
		alerta($_ALERTA);
		redirecionar('esqueceu-sua-senha');
	}
}

function gerar_nova_senha(){
	global $_DESATIVAR_PADRAO;
	global $_VARS;
	global $_SYSTEM;
	global $_HTML_DADOS;
	global $_HTML;
	
	if(!$_DESATIVAR_PADRAO['gerar_nova_senha']){
		$cod = $_REQUEST['cod'];
		$key = $_REQUEST['key'];
		$alerta_invalido = '<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código de validação especificado é inválido ou já foi usado! Favor entrar em contato pelo email para saber como proceder reportando o ocorrido: <a href="mailto:'.$_SYSTEM['CONTATO_EMAIL'].'">'.$_SYSTEM['CONTATO_NOME'].'</a></p>';
		
		if($cod && $key){
			$cod_original = $cod;
			$cod = substr($cod,8);
			$cod = zero_a_esquerda_retirar($cod);
			
			global $_CONEXAO_BANCO;
		
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$usuarios = banco_select_name
			(
				banco_campos_virgulas(Array(
					'cadastro_key',
					'usuario',
				))
				,
				"usuario",
				"WHERE id_usuario='".$cod."'"
			);
			
			if($key == $usuarios[0]['cadastro_key']){
				$key = crypt(rand().$cod);
				$key = preg_replace('/[\$\.\/]/i', '', $key);
				
				$usuario = $usuarios[0]['usuario'];
		
				banco_update
				(
					"cadastro_key='".$key."'",
					"usuario",
					"WHERE id_usuario='".$cod."'"
				);
				
				$gerar_nova_senha_path = 'includes'.$_SYSTEM['SEPARADOR'].'index.html';
				
				if($_PROJETO['index']){
					if($_PROJETO['index']['gerar_nova_senha_path']){
						$gerar_nova_senha_path = $_PROJETO['index']['gerar_nova_senha_path'];
					}
				}
				
				$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
				$_HTML_DADOS['titulo'] = $titulo . 'Gerar nova Senha.';
				
				$_HTML_DADOS['description'] = 'Página para geração de nova senha das contas de usuários do sistema.';
				$_HTML_DADOS['keywords'] = 'redefinir senha,redefinir,senha,redefinir,redefinição,redefinicao';
				
				$pagina = paginaModelo($_SYSTEM['PATH'].$gerar_nova_senha_path);
				$pagina = paginaTagValor($pagina,'<!-- redefinir_senha < -->','<!-- redefinir_senha > -->');
				
				$pagina = modelo_var_troca($pagina,"#usuario#",$usuario);
				$pagina = modelo_var_troca($pagina,"#key#",$key);
				$pagina = modelo_var_troca($pagina,"#cod#",$cod_original);
				
				return $pagina;
			} else {
				alerta($alerta_invalido);
				redirecionar('esqueceu-sua-senha');
			}
		} else {
			alerta($alerta_invalido);
			redirecionar('esqueceu-sua-senha');
		}
	}
}

function redefinir_senha_banco(){
	global $_DESATIVAR_PADRAO;
	global $_VARS;
	global $_SYSTEM;
	global $_HTML_DADOS;
	global $_HTML;
	global $_PROJETO;
	
	if(!$_DESATIVAR_PADRAO['redefinir_senha_banco']){
		$cod = $_REQUEST['cod'];
		$key = $_REQUEST['key'];
		$alerta_invalido = '<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código de validação especificado é inválido ou já foi usado! Favor entrar em contato pelo email para saber como proceder reportando o ocorrido: <a href="mailto:'.$_SYSTEM['CONTATO_EMAIL'].'">'.$_SYSTEM['CONTATO_NOME'].'</a></p>';
		
		if($cod && $key){
			$cod = substr($cod,8);
			$cod = zero_a_esquerda_retirar($cod);
			
			global $_CONEXAO_BANCO;
		
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$usuarios = banco_select_name
			(
				'*'
				,
				"usuario",
				"WHERE id_usuario='".$cod."'"
			);
			
			if($key == $usuarios[0]['cadastro_key']){
				session_unset();
				
				$usuario = $usuarios[0];
				
				if($usuario['versao_voucher']){
					$versao_voucher = (int)$usuario['versao_voucher'] + 1;
				} else {
					$versao_voucher = 1;
				}
				
				banco_update
				(
					"versao_voucher='".$versao_voucher."',".
					"senha='".crypt($_REQUEST['senha'])."',"
					."cadastro_key=NULL",
					"usuario",
					"WHERE id_usuario='".$cod."'"
				);
				
				$conteudo_perfil = false;
				if($_PROJETO['b2make_permissao_id'])
				foreach($_PROJETO['b2make_permissao_id'] as $id){
					if($id == $usuario['id_usuario_perfil']){
						$conteudo_perfil = true;
						break;
					}
				}
				
				if($conteudo_perfil){
					$_SESSION[$_SYSTEM['ID']."usuario_senha"] = $_REQUEST['senha'];
					$_SESSION[$_SYSTEM['ID']."usuario"] = $usuario;
					
					if($usuario['id_loja_usuarios']){
						banco_update
						(
							"versao_voucher='".$versao_voucher."',".
							"senha='".crypt($_REQUEST['senha'])."',"
							."cadastro_key=NULL",
							"loja_usuarios",
							"WHERE id_loja_usuarios='".$usuario['id_loja_usuarios']."'"
						);
					}
					
					b2make_my_profile_ftp_passwd_2();
				}
				
				alerta('<p>Senha redefinida com sucesso!</p>');
				redirecionar('signin');
			} else {
				alerta($alerta_invalido);
				redirecionar('esqueceu-sua-senha');
			}
		} else {
			alerta($alerta_invalido);
			redirecionar('esqueceu-sua-senha');
		}
	}
}

// Cadastro usuário

function recaptcha_verify(){
	global $_SYSTEM;
	global $_VARS;
	
	if($_REQUEST['g-recaptcha-response']){
		$urlRequest = 'https://www.google.com/recaptcha/api/siteverify?secret='.$_VARS['recaptcha']['PRIVATE_KEY'].'&response='.$_REQUEST['g-recaptcha-response'].'&remoteip='.$_SERVER["REMOTE_ADDR"];
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $urlRequest);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl);
		
		curl_close($curl);
		
		$res = json_decode($response);
		
		if($res->success){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function cadastro_banco(){
	global $_HTML;
	global $_SYSTEM;
	
	if(!$_DESATIVAR_PADRAO['cadastro_banco']){
		if(recaptcha_verify()){
			banco_conectar();
			
			$usuarios = banco_select_name
			(
				"id_usuario"
				,
				"usuario",
				"WHERE email='" . $_REQUEST['email'] . "'"
			);
			
			if($usuarios){
				seguranca_delay();
				alerta('<p><b>Não é possível cadastrar!</b></p><p>Esse email já está cadastrado no sistema!</p>');
				redirecionar('autenticar');
			} else {
				$parametros['enviar_mail'] = true;
				
				$campo_nome = "id_usuario_perfil"; 								$campos[] = Array($campo_nome,'2');
				$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$_SYSTEM['CADASTRO_STATUS']);
				$campo_nome = "usuario"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "senha"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,crypt($_REQUEST[$post_nome]));
				$campo_nome = "email"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "sobrenome"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "endereco"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "numero"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "complemento"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "bairro"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "cidade"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "uf"; $post_nome = $campo_nome; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "cep"; $post_nome = $campo_nome; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "telefone"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "celular"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
				$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
				
				$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
				
				$parametros['from_name'] = $_HTML['TITULO'];
				$parametros['from'] = 'noreplay@'.$dominio_sem_www;
				
				$parametros['email_name'] = $_SYSTEM['CONTATO_NOME'];
				$parametros['email'] = $_SYSTEM['CONTATO_EMAIL'];
				
				$parametros['subject'] = 'Cadastro - Portal '.$_HTML['TITULO'];
				
				$parametros['mensagem'] = "<h1>Cadastro Feito no Portal</h1>\n";
				$parametros['mensagem'] .= "<p>O usuário <b>".$_REQUEST['nome']."</b> de email <b><a href=\"mailto:".$_REQUEST['email']."\">".$_REQUEST['email']."</a></b> se cadastrou no portal e está aguardando ser liberado para acessar.</p>\n";
				$parametros['mensagem'] .= "<p>".$_HTML['TITULO']."</p>\n";
				
				$params = Array(
					'id' => 'cadastro_banco',
					'campos' => $campos,
					'parametros' => $parametros,
				);
				
				$saida = projeto_modificar_campos($params);
				
				$campos = $saida['campos'];
				$parametros = $saida['parametros'];
				
				banco_insert_name
				(
					$campos,
					"usuario"
				);
				
				banco_fechar_conexao();
				
				if($parametros['enviar_mail'])enviar_mail($parametros);
				
				alerta('<p>Cadastro Efetuado Com <b style="color:green;">Sucesso</b>!</p><p>Em breve um de nossos atendentes entrará em contato!</p>');
			}
		} else {
			alerta('<p>Código de validação <b style="color:red;">inválido</b>!<p></p>Favor preencher o código de validação novamente!</p>');	
		}
	}
	
	return pagina_inicial();
}

function contato_banco(){
	global $_HTML;
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_VARIAVEIS_JS;
	global $_PROJETO;
	
	if(!$_DESATIVAR_PADRAO['contato_banco']){
		$_REQUEST['mensagem'] = preg_replace('/\\n/', '<br />', $_REQUEST['mensagem']);
		
		if($_REQUEST['_mobile']){
			$_REQUEST['nome'] = $_REQUEST['nome'];
			$_REQUEST['mensagem'] = $_REQUEST['mensagem'];
		}
		
		if($_PROJETO['contato_banco_script']){
			$_VARIAVEIS_JS['alerta_appendto_body'] = $_PROJETO['contato_banco_script'];
		}
		
		banco_conectar();
		
		$parametros['enviar_mail'] = true;
		
		$campo_nome = "nome"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
		$campo_nome = "email"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
		$campo_nome = "mensagem"; $post_nome = $campo_nome; 		if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
		$campo_nome = "data"; $campo_valor = "NOW()"; 				$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "status"; $campo_valor = "A"; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$parametros['email_name'] = $_SYSTEM['CONTATO_NOME'];
		$parametros['email'] = $_SYSTEM['CONTATO_EMAIL'];
		
		$parametros['subject'] = 'Contato - Portal '.$_HTML['TITULO'];
		
		$parametros['mensagem'] = "<h1>Contato Feito no Portal</h1>\n";
		$parametros['mensagem'] .= "<p>Nome: ".$_REQUEST['nome']."<br />\n";
		$parametros['mensagem'] .= "Email: <a href=\"mailto:".$_REQUEST['email']."\">".$_REQUEST['email']."</a></p>\n";
		$parametros['mensagem'] .= "<p>Mensagem: ".$_REQUEST['mensagem']."</p>\n";
		
		$params = Array(
			'id' => 'contato_banco',
			'campos' => $campos,
			'parametros' => $parametros,
		);
		
		$saida = projeto_modificar_campos($params);
		
		$campos = $saida['campos'];
		$parametros = $saida['parametros'];
		
		banco_insert_name
		(
			$campos,
			"contatos"
		);
		
		banco_fechar_conexao();
		
		if($parametros['enviar_mail'])enviar_mail($parametros);
		
		alerta(1);
		return pagina_inicial();
	}
}

function cadastrar_email(){
	global $_DESATIVAR_PADRAO;
	global $_PROJETO;
	global $_SYSTEM;
	
	if($_REQUEST['_mobile']){
		$_REQUEST['nome'] = $_REQUEST['nome'];
	}
	
	if(!$_DESATIVAR_PADRAO['cadastrar_email']){
		banco_conectar();
		$emails = banco_select
		(
			"id_emails",
			"emails",
			"WHERE email='".$_REQUEST["email"]."'"
		);
		
		if(!$emails){
			$mensagem_saida = "Cadastro de e-mail executado com sucesso!<br /><br />Em breve você receberá o nosso E-mail Marketing.";
			
			$campo_nome = "status"; $campo_valor = "A"; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "opt_in"; $campo_valor = "NOW()"; 				$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "nome"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "email"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			
			$params = Array(
				'id' => 'cadastrar_email',
				'campos' => $campos,
				'parametros' => Array(
					'mensagem_saida' => $mensagem_saida,
				),
			);
			
			$saida = projeto_modificar_campos($params);
			
			$campos = $saida['campos'];
			$mensagem_saida = $saida['parametros']['mensagem_saida'];
			
			banco_insert_name
			(
				$campos,
				"emails"
			);
			
			if($_PROJETO['mail2easy']){
				$url = 'http://cache.mail2easy.com.br/integracao';
				
				$myvars = 
					'CON_ID=' . $_PROJETO['mail2easy']
					.'&DESTINO=http://' . $_DOMINIO . '/' . $_SYSTEM['ROOT']
					.'&GRUPOS_CADASTRAR=1'
					.'&SMT_nome=' . $_REQUEST['nome']
					.'&SMT_email=' . $_REQUEST['email']
					.'&SMT_RECEBER=1'
					;

				$ch = curl_init( $url );
				curl_setopt( $ch, CURLOPT_POST, 1);
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt( $ch, CURLOPT_HEADER, 0);
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

				$response = curl_exec( $ch );
			}
			
			alerta($mensagem_saida);
		} else {
			alerta("Já existe esse e-mail CADASTRADO no sistema, escolha outro!");
		}
		
		banco_fechar_conexao();
		
		return pagina_inicial();
	}
}

function comentarios_banco(){
	global $_HTML;
	global $_SYSTEM;
	
	if(!$_DESATIVAR_PADRAO['comentarios_banco']){
		if(recaptcha_verify()){
			banco_conectar();
			
			$parametros['enviar_mail'] = true;
			
			$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$_SYSTEM['CADASTRO_STATUS']);
			$campo_nome = "id_conteudo"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "pai"; $post_nome = $campo_nome; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "conteudo"; $post_nome = $campo_nome; 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "autor"; $post_nome = "nome"; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "autor_email"; $post_nome = "email"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,$_REQUEST[$post_nome]);
			$campo_nome = "data";											$campos[] = Array($campo_nome,'NOW()',true);
			
			$parametros['email_name'] = $_SYSTEM['CONTATO_NOME'];
			$parametros['email'] = $_SYSTEM['CONTATO_EMAIL'];
			
			$parametros['subject'] = 'Comentário - Portal '.$_HTML['TITULO'];
			
			$parametros['mensagem'] = "<h1>Comentário Feito no Portal</h1>\n";
			$parametros['mensagem'] .= "<p>É necessário publicar o comentário para o mesmo ser visível no portal. Entre no painel administrativo / Cometários e clique no botão de desbloqueio</p>\n";
			$parametros['mensagem'] .= "<p>Nome: ".$_REQUEST['nome']."<br />\n";
			$parametros['mensagem'] .= "Email: <a href=\"mailto:".$_REQUEST['email']."\">".$_REQUEST['email']."</a></p>\n";
			$parametros['mensagem'] .= "<p>Comentário: ".$_REQUEST['conteudo']."</p>\n";
			
			$params = Array(
				'id' => 'comentarios_banco',
				'campos' => $campos,
				'parametros' => $parametros,
			);
			
			$saida = projeto_modificar_campos($params);
			
			$campos = $saida['campos'];
			$parametros = $saida['parametros'];
			
			banco_insert_name
			(
				$campos,
				"comentarios"
			);
			
			banco_fechar_conexao();
			
			if($parametros['enviar_mail'])enviar_mail($parametros);
			
			alerta('<p>Comentário Cadastrado Com <b style="color:green;">Sucesso</b>!</p><p>Em breve um de nossos atendentes analisará o seu comentário<br />e se não houver problemas o mesmo será publicado.!</p>');
		} else {
			alerta('<p>Código de validação <b style="color:red;">inválido</b>!<p></p>Favor preencher o código de validação novamente!</p>');	
		}
	}
	
	return pagina_inicial();
}

// Funções de instalação

function install(){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_HTML;
	global $_SYSTEM;

	$pagina = paginaModelo($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'index.html');
	$pagina = paginaTagValor($pagina,'<!-- install < -->','<!-- install > -->');
	
	if($_SERVER['SERVER_NAME'] == "localhost"){
		$maquina_testes = '<h2>Instalação Básica</h2><p>Caso não queira modificar os parâmetros iniciais e queria uma instalação básica para máquinas testes <a href="!#caminho_raiz#!_maquina_testes">clique aqui</a></p><h2>Instalação Completa</h2>';
	}
	
	$pagina = modelo_var_troca($pagina,"#maquina_testes#",$maquina_testes);
	
	banco_conectar();
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulo',
			'id_modulo_pai',
			'nome',
			'caminho',
			'titulo',
			'imagem',
			'status',
		))
		,
		"modulo",
		""//"WHERE status='A'"
		." ORDER BY id_modulo_pai ASC, ordem ASC, nome ASC"
	);
	banco_fechar_conexao();
	
	$checked = ' checked="checked"';
	
	$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'cat'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	foreach($modulos as $modulo){
		if($modulo['id_modulo_pai']){
			if($modulo['nome'] != 'Sair'){
				$filhos_count[$modulo['id_modulo_pai']]++;
			}
		} else {
			$cel_nome = 'cat';
			$cel_aux[$modulo['id_modulo']] = $cel[$cel_nome];
			$cel_aux[$modulo['id_modulo']] = modelo_var_troca($cel_aux[$modulo['id_modulo']],"#cat_titulo",$modulo['nome']);
		}
	}
	
	foreach($modulos as $modulo){
		if($modulo['id_modulo_pai']){
			if($modulo['nome'] != 'Sair'){
				$cel_nome = 'item';
				$cel_aux2 = $cel[$cel_nome];
				
				$img_arr = explode(',',$modulo['imagem']);
				
				$img_position = '-' . (20*((int)$img_arr[1]-1)) . 'px -' .  (20*((int)$img_arr[0]-1)) . 'px';
				
				$cel_aux2 = modelo_var_troca($cel_aux2,"#root#",'/'.$_SYSTEM['ROOT']);
				$cel_aux2 = modelo_var_troca($cel_aux2,"#item_href",$modulo['caminho']);
				$cel_aux2 = modelo_var_troca($cel_aux2,"#item_img",$img_position);
				$cel_aux2 = modelo_var_troca_tudo($cel_aux2,"#item_nome",$modulo['nome']);
				$cel_aux2 = modelo_var_troca_tudo($cel_aux2,"#item_titulo",$modulo['titulo']);
				$cel_aux2 = modelo_var_troca_tudo($cel_aux2,"#id_modulo#",$modulo['id_modulo']);
				$cel_aux2 = modelo_var_troca_tudo($cel_aux2,"#checked#",($modulo['status'] == 'A' ? $checked : '' ));
				
				$cel_aux[$modulo['id_modulo_pai']] = modelo_var_in($cel_aux[$modulo['id_modulo_pai']],'<!-- '.$cel_nome.' -->',$cel_aux2.($filhos_count_aux[$modulo['id_modulo_pai']] < $filhos_count[$modulo['id_modulo_pai']] - 1?'<div class="ini_barra_lateral"></div>':''));
				
				$filhos[$modulo['id_modulo_pai']] = true;
				$filhos_count_aux[$modulo['id_modulo_pai']]++;
			}
		}
	}
	
	// =========================== ORDEM MANUAL ================
	
	$ordem_manual[1] = true;
	$ordem_manual[2] = true;
	
	if($filhos[2])$pagina = modelo_var_in($pagina,'<!-- cat -->',$cel_aux[2]);
	if($filhos[1])$pagina = modelo_var_in($pagina,'<!-- cat -->',$cel_aux[1]);
	
	//============================================================
	
	foreach($cel_aux as $id_modulo =>$cel_val){
		$cel_nome = 'cat';
		if($filhos[$id_modulo] && !$ordem_manual[$id_modulo])
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_val);
	}
	
	return $pagina;
}

function install_salvar(){
	global $_SYSTEM;
	
	$erros_template = "<p>Porém os seguintes módulos não foram ATIVADOS:</p>";
	$erros_template_base = "<p>Contate o desenvolvedor para maiores esclarecimentos e reporte o ocorrido: <a href=\"mailto:".$_SYSTEM['ADMIN_EMAIL']."\">".$_SYSTEM['ADMIN_EMAIL']."</a></p>";
	
	banco_conectar();
	$campo_tabela = "usuario";
	$campo_tabela_extra = "WHERE id_usuario='1'";
	
	$campo_nome = "usuario"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
	$campo_nome = "senha"; $editar[$campo_tabela][] = $campo_nome."='" . crypt($_REQUEST[$campo_nome]) . "'";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	$campo_tabela = "variavel_global";
	$campo_tabela_extra = "WHERE variavel='PRIMEIRA_EXECUCAO'";
	
	$campo_nome = "valor"; $editar[$campo_tabela][] = $campo_nome."=NULL";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	$file = file_get_contents($_SYSTEM['PATH'].'robots.txt');
	
	$dominio = $_REQUEST['dominio'];
	$dominio = strtolower($dominio);
	$dominio = preg_replace('/http:\/\//i', '', $dominio);
	
	$file = modelo_var_troca($file,"www.dominio.com.br",$dominio);
	file_put_contents($_SYSTEM['PATH'].'robots.txt',$file);
	
	// =============================== Módulos ================================
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulo',
			'id_modulo_pai',
			'nome',
			'status',
			'caminho',
		))
		,
		"modulo",
		""
	);
	
	if($modulos)
	foreach($modulos as $modulo){
		if($modulo['id_modulo_pai'] && $modulo['nome'] != 'Sair'){
			if($_REQUEST['modulo_'.$modulo['id_modulo']]){
				if($modulo['status'] == 'I'){
					if(!is_dir($_SYSTEM['PATH'].'admin'.$_SYSTEM['SEPARADOR'].$modulo['caminho'])){
						if(!$erros){
							$erros = $erros_template;
						}
						
						$erros .= '<p>Módulo: <b>'.$modulo['nome'].'</b> não existe fisicamente!</p>';
					} else {
						banco_update
						(
							"status='A'",
							"modulo",
							"WHERE id_modulo='".$modulo['id_modulo']."'"
						);
					}
				}
			} else {
				if($modulo['status'] == 'A'){
					banco_update
					(
						"status='I'",
						"modulo",
						"WHERE id_modulo='".$modulo['id_modulo']."'"
					);
				}
				
				if($_REQUEST['forcar_exclusao'] == 'Sim'){
					removeDirectory($_SYSTEM['PATH'].'admin'.$_SYSTEM['SEPARADOR'].$modulo['caminho']);
				}
			}
		}
	}
	
	if($erros){
		$erros .= $erros_template_base;
	}
	
	// =======================================================================
	
	banco_fechar_conexao();
	
	$_SYSTEM['PRIMEIRA_EXECUCAO'] = false;
	
	alerta("<p>Instalação bem sucessedida!</p>".$erros);
}

function maquina_testes(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_OPCAO;
	
	banco_conectar();
	$campo_tabela = "usuario";
	$campo_tabela_extra = "WHERE id_usuario='1'";
	
	$campo_nome = "usuario"; $editar[$campo_tabela][] = $campo_nome."='admin'";
	$campo_nome = "senha"; $editar[$campo_tabela][] = $campo_nome."='" . crypt("1234") . "'";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	$campo_tabela = "variavel_global";
	$campo_tabela_extra = "WHERE variavel='PRIMEIRA_EXECUCAO'";
	
	$campo_nome = "valor"; $editar[$campo_tabela][] = $campo_nome."=NULL";
	
	$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
	
	if($editar_sql[$campo_tabela]){
		banco_update
		(
			$editar_sql[$campo_tabela],
			$campo_tabela,
			$campo_tabela_extra
		);
	}
	$editar = false;$editar_sql = false;
	
	banco_update
	(
		"status='A'",
		"modulo",
		""
	);
	
	banco_fechar_conexao();
	
	$_SYSTEM['PRIMEIRA_EXECUCAO'] = false;
	
	alerta("Instalação bem sucessedida!");
	
	$_OPCAO = 'login';
	return login();
}

function testes(){
	global $_SYSTEM;
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'eservices'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- testes < -->','<!-- testes > -->');
	
	return $pagina;
}

// Funções do sistema

function newsletter(){
	global $_HTML_DADOS;
	global $_CONEXAO_BANCO;
	global $_CAMINHO;
	global $_SYSTEM;
	global $_VARS;
	global $_ECOMMERCE;
	
	$_HTML_DADOS['news_layout'] = true;
	
	$newsletter_produtos = $_VARS['newsletter']['newsletter-produtos'];
	
	if($newsletter_produtos){
		$_ECOMMERCE['apenas_incluir'] = true;
		require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/index.php');
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$newsletter = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_newsletter_layout',
			'id_newsletter',
			'imagem_topo',
			'imagem_rodape',
			'versao',
		))
		,
		"newsletter",
		"WHERE identificador='".$_CAMINHO[1]."'"
	);
	
	$newsletter_layout = banco_select_name
	(
		banco_campos_virgulas(Array(
			'layout',
		))
		,
		"newsletter_layout",
		"WHERE id_newsletter_layout='".$newsletter[0]['id_newsletter_layout']."'"
	);
	$newsletter_conteudo = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.imagem',
			't2.titulo',
			't2.sub_titulo',
			't2.caminho_raiz',
			't2.identificador',
			't2.versao',
			't2.id_conteudo',
		))
		,
		"newsletter_conteudo as t1,conteudo as t2",
		"WHERE t1.id_newsletter='".$newsletter[0]['id_newsletter']."'"
		." AND t1.id_conteudo=t2.id_conteudo"
		." AND t2.status='A'"
	);
	
	$layout = $newsletter_layout[0]['layout'];
	
	$_HTML_DADOS['titulo'] = $newsletter[0]['nome'];
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$layout = modelo_var_troca_tudo($layout,"#imagem_topo_src#",($newsletter[0]['imagem_topo'] ? 'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . $newsletter[0]['imagem_topo'] . '?v=' . $newsletter[0]['versao'] : ''));
	$layout = modelo_var_troca_tudo($layout,"#imagem_rodape_src#",($newsletter[0]['imagem_rodape'] ? 'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . $newsletter[0]['imagem_rodape'] . '?v=' . $newsletter[0]['versao'] : ''));
	
	if($newsletter_conteudo)
	foreach($newsletter_conteudo as $res){
		if($newsletter_produtos){
			$cel_aux = call_user_func('ecommerce_newsletter_produtos_servicos',$res['t2.id_conteudo']);
		} else {
			$cel_aux = false;
		}

		if(!$cel_aux){
			$cel_nome = 'cel';
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#conteudo_url#",'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . $res['t2.caminho_raiz'] . $res['t2.identificador']);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#conteudo_imagem_src#",($res['t1.imagem'] ? 'http://' . $_SYSTEM['DOMINIO'] . '/' . $_SYSTEM['ROOT'] . $res['t1.imagem'] . '?v=' . $res['t2.versao'] : ''));
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#conteudo_titulo#",$res['t2.titulo']);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#conteudo_sub_titulo#",$res['t2.sub_titulo']);
		}
		
		$layout = modelo_var_in($layout,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	$layout = modelo_var_troca($layout,'<!-- '.$cel_nome.' -->','');
	
	return $layout;
}

function menu_conteudo_filhos($cod,$id,$raiz = '',$nivel = 1,$cod_pai_raiz = false){
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PROJETO;
	
	if($_PROJETO['menu_conteudo']){
		if($_PROJETO['menu_conteudo']['so_tipo_lista'])$so_tipo_lista = $_PROJETO['menu_conteudo']['so_tipo_lista'];
		if($_PROJETO['menu_conteudo']['nivel_maximo'])$nivel_maximo = $_PROJETO['menu_conteudo']['nivel_maximo'];
		if($_PROJETO['menu_conteudo']['nivel_maximo_id'][$id])$nivel_maximo = $_PROJETO['menu_conteudo']['nivel_maximo_id'][$id];
		if($_PROJETO['menu_conteudo']['ordenacao'])$ordenacao = $_PROJETO['menu_conteudo']['ordenacao'];
	}
	
	if(!$raiz) $raiz = $_CAMINHO_RELATIVO_RAIZ;
	if(!$cod_pai_raiz) $cod_pai_raiz = $cod;
	
	$tabela = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
			'titulo',
			'identificador',
			'tipo',
			'caminho_raiz',
		))
		,
		"conteudo",
		"WHERE id_conteudo_pai='".$cod."'"
		." AND status='A'"
		." ORDER BY ".($ordenacao?$ordenacao:"ordem ASC,titulo ASC")
	);
	
	$conteudos = Array();
	
	if($tabela){
		foreach($tabela as $conteudo){
			if($so_tipo_lista){
				if($conteudo['tipo'] == 'L'){
					$conteudos[] = Array(
						'titulo' => $conteudo['titulo'],
						'nivel' => $nivel,
						'url' => $raiz.$conteudo['caminho_raiz'].$conteudo['identificador'],
						'categoria' => ($conteudo['tipo'] == 'L'?true:false),
						'cod_pai_raiz' => $cod_pai_raiz,
					);
				}
			} else {
				$conteudos[] = Array(
					'titulo' => $conteudo['titulo'],
					'nivel' => $nivel,
					'url' => $raiz.$conteudo['caminho_raiz'].$conteudo['identificador'],
					'categoria' => ($conteudo['tipo'] == 'L'?true:false),
					'cod_pai_raiz' => $cod_pai_raiz,
				);
			}
			
			$filhos = false;
			if($conteudo['tipo'] == 'L'){
				if($nivel_maximo)
				if($nivel_maximo < $nivel+1){
					$nao_executar = true;
				}
				
				if(!$nao_executar)$filhos = menu_conteudo_filhos($conteudo['id_conteudo'],false,$raiz,$nivel+1,$cod_pai_raiz);
			}
			
			if($filhos){
				foreach($filhos as $filho){
					if($so_tipo_lista){
						if($filho['categoria']){
							$conteudos[] = Array(
								'titulo' => $filho['titulo'],
								'nivel' => $filho['nivel'],
								'url' => $filho['url'],
								'categoria' => $filho['categoria'],
								'cod_pai_raiz' => $filho['cod_pai_raiz'],
							);
						}
					} else {
						$conteudos[] = Array(
							'titulo' => $filho['titulo'],
							'nivel' => $filho['nivel'],
							'url' => $filho['url'],
							'categoria' => $filho['categoria'],
							'cod_pai_raiz' => $filho['cod_pai_raiz'],
						);
					}
					
				}
			}
		}
	}
	
	return $conteudos;
}

function menu_layout(){
	global $_CONEXAO_BANCO;
	global $_VARIAVEIS_JS;
	global $_PROJETO;
	
	$tab = "	";
	$ul = "<ul>\n";
	$bul = "</ul>\n";
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$conteudo_permissao = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
		))
		,
		"conteudo_permissao",
		"WHERE menu_principal=1"
		." AND tipo='C'"
	);
	
	if($conteudo_permissao)
	foreach($conteudo_permissao as $cont_perm){
		$cod = $cont_perm['id_conteudo'];
		
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'identificador',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$cod."'"
		);
		
		$id = $conteudo[0]['identificador'];
		$conteudos = menu_conteudo_filhos($cod,$id);
		$num_links = count($conteudos);
		$count = 0;
		$nivel_atual = 1;
		
		if($conteudos){
			$menu_principal_ids .= $id . ',';
			foreach($conteudos as $cont){
				if(!$count){
					$menu = $ul;
					$tabs = ""; for($i=0;$i<$nivel_atual;$i++) $tabs .= $tab;
				}
				
				if($nivel_atual != $cont['nivel']){
					if($nivel_atual < $cont['nivel']){
						$nivel_atual = $cont['nivel'];
						$tabs = ""; for($i=0;$i<$nivel_atual;$i++) $tabs .= $tab;
						$menu .= $tabs.$ul;
						$tabs .= $tab;
					} else {
						$tabs = ""; for($i=0;$i<$nivel_atual;$i++){
							if($i == $nivel_atual - 1){
								$tab_aux = $tabs;
							}
							$tabs .= $tab;
						}
						$nivel_atual = $cont['nivel'];
						$menu .= $tabs.$bul;
						$tabs = $tab_aux;
					}
				}
				
				$a = html(Array(
					'tag' => 'a',
					'val' => $cont['titulo'],
					'attr' => Array(
						'href' => $cont['url'],
						'class' => '_link_menu',
						'rel' => 'menu-'.$id,
					)
				));
				
				if(!$_PROJETO['menu_layout']['desativar_categoria_h2'])if($cont['categoria']){
					$a = html(Array(
						'tag' => 'h'.($cont['nivel']%3+1),
						'val' => $a,
						'attr' => Array()
					));
				}
				
				$li = html(Array(
					'tag' => 'li',
					'val' => $a,
					'attr' => Array()
				));
				
				$menu .= $tabs.$li."\n";
				
				if($count == $num_links - 1){
					for($n=1;$n<$nivel_atual;$n++){
						$tabs = ""; for($i=1;$i<$nivel_atual;$i++) $tabs .= $tab;
						$menu .= $tabs.$bul;
					}
					
					$menu .= $bul;
				}
				
				if($count == $num_links - 1){
					$menus = html(Array(
						'tag' => 'div',
						'val' => $menu,
						'attr' => Array(
							'class' => 'menu_div',
							'id' => '_menu_'.$id,
						)
					));
				}
				
				$count++;
			}
			
			$count2++;
			$menus_todos .= $menus;
		}
	}
	
	if($connect_db)banco_fechar_conexao();
	
	$_VARIAVEIS_JS['menu_principal_ids'] = $menu_principal_ids;
	
	$menus_todos = html(Array(
		'tag' => 'div',
		'val' => $menus_todos,
		'attr' => Array(
			'id' => 'menu-secundario-mask',
		)
	));
	
	$menus_todos = html(Array(
		'tag' => 'div',
		'val' => $menus_todos,
		'attr' => Array(
			'id' => 'menu-secundario',
		)
	));
	
	return $menus_todos;
}

function menu_layout_colunas($params){
	global $_CONEXAO_BANCO;
	global $_VARIAVEIS_JS;
	global $_PROJETO;
	
	$colunas = 4;
	$setinha = true;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$conteudo_permissao = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
		))
		,
		"conteudo_permissao",
		"WHERE menu_principal=1"
		." AND tipo='C'"
	);
	
	if($conteudo_permissao)
	foreach($conteudo_permissao as $cont_perm){
		$cod = $cont_perm['id_conteudo'];
		
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'identificador',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$cod."'"
		);
		
		$id = $conteudo[0]['identificador'];
		$conteudos = menu_conteudo_filhos($cod,$id);
		
		$num_links = count($conteudos);
		$count = 0;
		$coluna = 0;
		$menus_li = Array();
		$menus = '';
		$divs = '';
		
		if($conteudos){
			$menu_principal_ids .= $id . ',';
			
			$total = count($conteudos);
			
			if($total >= $colunas){
				$dados_por_coluna = floor($total/$colunas) + 1;
				$corte_coluna = $total%$colunas;
			} else {
				$dados_por_coluna = 1;
			}
			
			foreach($conteudos as $cont){
				$a = html(Array(
					'tag' => 'a',
					'tabs' => 3,
					'nl2' => false,
					'nl1' => false,
					'val' => $cont['titulo'],
					'attr' => Array(
						'href' => $cont['url'],
						'class' => '_link_menu',
						'rel' => 'menu-'.$id,
					)
				));
				
				if($cont['categoria']){
					$a = html(Array(
						'tag' => 'h'.($cont['nivel']%3+1),
						'val' => $a,
						'tabs' => 3,
						'nl2' => false,
						'nl1' => false,
						'attr' => Array()
					));
				}
				
				$li = html(Array(
					'tag' => 'li',
					'val' => $a,
					'tabs' => 2,
					'nl2' => false,
					'attr' => Array()
				));
				
				$menus_li[$coluna] .= $li;
				
				$count++;
				
				if($count >= $dados_por_coluna){
					$count = 0;
					$coluna++;
					if($corte_coluna == $coluna){
						$dados_por_coluna--;
					}
				}
			}
			
			$count = 0;
			if($menus_li){
				foreach($menus_li as $menu){
					$count++;
					
					$ul = html(Array(
						'tag' => 'ul',
						'val' => $menu,
						'tabs' => 1,
						'attr' => Array(
							'class' => 'menu_coluna'.(count($menus_li) > $count ? ($sem_barra?'':' menu_coluna_barra') : '')
						)
					));
					
					$menus .= $ul;
				}
			}
			
			$menus = html(Array(
				'tag' => 'div',
				'val' => $menus.$clear,
				'attr' => Array(
					'class' => 'menu_div',
					'id' => '_menu_'.$id,
				),
			));
		}
		
		$menus_todos .= $menus;
	}
	
	if($connect_db)banco_fechar_conexao();
	
	$_VARIAVEIS_JS['menu_principal_ids'] = $menu_principal_ids;
	$_VARIAVEIS_JS['menu_principal_margem'] = $margem;
	$_VARIAVEIS_JS['menu_principal_colunas'] = true;
	if($setinha)$_VARIAVEIS_JS['menu_setinha'] = true;
	
	$menus_todos = html(Array(
		'tag' => 'div',
		'val' => $menus_todos,
		'attr' => Array(
			'id' => 'menu-secundario-mask',
		)
	));
	
	$menus_todos = html(Array(
		'tag' => 'div',
		'val' => $menus_todos,
		'attr' => Array(
			'id' => 'menu-secundario',
		)
	));
	
	if($setinha)$menus_todos = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'id' => 'menu-setinha',
		)
	)).$menus_todos;
	
	return $menus_todos;
}

function menu_sitemap_id_pai($params){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$conteudo_permissao = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
		))
		,
		"conteudo_permissao",
		"WHERE menu_sitemap=1"
		." AND (tipo='C' || tipo='L' || tipo='".($nivel+1)."')"
		." AND id_conteudo='".$id_conteudo_pai."'"
	);
	
	if($conteudo_permissao){
		return true;
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
				'id_conteudo_pai',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id_conteudo_pai."'"
		);
		
		if($conteudo){
			$id_conteudo_pai = $conteudo[0]['id_conteudo_pai'];
			
			if($id_conteudo_pai > 0){
				return menu_sitemap_id_pai(Array(
					'id_conteudo_pai' => $id_conteudo_pai,
					'nivel' => $nivel+1,
				));
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

function menu_sitemap_ids($params){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$conteudo_permissao = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.identificador',
			't1.id_conteudo',
			't1.id_conteudo_pai',
		))
		,
		"conteudo as t1,conteudo_permissao as t2",
		"WHERE t2.menu_sitemap=1"
		." AND t1.id_conteudo=t2.id_conteudo"
		." AND t1.status='A'"
		.($order?$order:" ORDER BY t1.titulo ASC")
	);
	
	if($conteudo_permissao){
		foreach($conteudo_permissao as $cont){
			$id_real = '';
			if($cont['t1.id_conteudo_pai'] > 0){
				if(!menu_sitemap_id_pai(Array(
					'id_conteudo_pai' => $cont['t1.id_conteudo'],
				))){
					$id_real = $cont['t1.identificador'];
				}
			} else {
				$id_real = $cont['t1.identificador'];
			}
			
			if($id_real)
			if($ids){
				$flag = false;
				foreach($ids as $id){
					if($id == $id_real){
						$flag = true;
						break;
					}	
				}
				if(!$flag){
					$ids[] = $id_real;
				}
			} else {
				$ids[] = $id_real;
			}
		}
	}
	
	return $ids;
}

function menu_sitemap_layout($params){
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	
	$tab = "	";
	$ul = "<ul style=\"margin:0px;padding:0px;list-style:none;\" class=\"menu_ul\">\n";
	$bul = "</ul>\n";
	$borda = "solid 2px #CCC";
	$display = "block";
	$coluna = 1;
	$nivel_atual = 1;
	$width = 230;
	$margin = 10;
	$h1 = "<h1 style=\"color: #000; margin-top:10px; margin-bottom:10px;font-size: 14pt; display:block;width: ".$width."px;\">\n";
	$bh1 = "</h1>\n";
	$entrada_por_coluna = 10;
	$colunas = 4;
	$pai_link = true;
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$ids = menu_sitemap_ids($sitemap_ids);
	
	$conteudos = Array();
	
	if($conteudos_antes)$conteudos = $conteudos_antes;
	
	if($ids)
	foreach($ids as $id){
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
				'titulo',
				'caminho_raiz',
				'tipo',
			))
			,
			"conteudo",
			"WHERE identificador='".$id."'"
		);
		
		if(!$cod_pai_raiz) $cod_pai_raiz = $conteudo[0]['id_conteudo'];
		
		$pais_titulo[$conteudo[0]['id_conteudo']] = $conteudo[0]['titulo'];
		
		$conteudo_filhos = menu_conteudo_filhos($conteudo[0]['id_conteudo'],$id);
		
		if($pai_link){
			$conteudo_lay = false;
			$conteudo_lay[] = Array(
				'titulo' => $conteudo[0]['titulo'],
				'nivel' => 0,
				'url' => $_CAMINHO_RELATIVO_RAIZ . $conteudo[0]['caminho_raiz'] . $id,
				'categoria' => ($conteudo[0]['tipo'] == 'L'?true:false),
				'cod_pai_raiz' => $conteudo[0]['id_conteudo'],
			);
			
			$conteudos = array_merge($conteudos,$conteudo_lay);
		}
		
		$conteudos = array_merge($conteudos,$conteudo_filhos);
	}
	
	if($conteudos_apos)$conteudos = array_merge($conteudos,$conteudos_apos);
	
	$id = 'site_map';
	
	if($connect_db)banco_fechar_conexao();
	
	$num_links = count($conteudos);
	$count = 0;
	
	if($num_links <= $entrada_por_coluna * $colunas){
		$entrada_por_coluna2 = $entrada_por_coluna;
	} else {
		$entrada_por_coluna2 = round($num_links/$colunas);
	}	
	
	if($conteudos)
	foreach($conteudos as $cont){
		if(!$count){
			if($pai_link){
				$menu = $ul;
				$tabs = ""; for($i=0;$i<$nivel_atual;$i++) $tabs .= $tab;
			} else {
				$menu = $h1.$pais_titulo[$cod_pai_raiz].$bh1;
				$menu .= $ul;
				$tabs = ""; for($i=0;$i<$nivel_atual;$i++) $tabs .= $tab;
			}
		} else {
			if($cont['cod_pai_raiz'] != $cod_pai_raiz){
				if(!$pai_link){
					$menu .= $h1.$pais_titulo[$cod_pai_raiz].$bh1;
				}
				
				$cod_pai_raiz = $cont['cod_pai_raiz'];
			}
		}
		
		if($nivel_atual != $cont['nivel']){
			if($nivel_atual < $cont['nivel']){
				$nivel_atual = $cont['nivel'];
				$tabs = ""; for($i=0;$i<$nivel_atual;$i++) $tabs .= $tab;
				$menu .= $tabs.$ul;
				$tabs .= $tab;
			} else {
				$tabs = ""; for($i=0;$i<$nivel_atual;$i++){
					if($i == $nivel_atual - 1){
						$tab_aux = $tabs;
					}
					$tabs .= $tab;
				}
				$nivel_atual = $cont['nivel'];
				$menu .= $tabs.$bul;
				$tabs = $tab_aux;
			}
		}
		
		$a = html(Array(
			'tag' => 'a',
			'val' => ($setinha[($cont['nivel']%3+1)]?$setinha[($cont['nivel']%3+1)]:'').$cont['titulo'],
			'attr' => Array(
				'href' => $cont['url'],
			)
		));
		
		if($cont['categoria']){
			$a = html(Array(
				'tag' => 'h'.($cont['nivel']%3+1),
				'val' => $a,
				'attr' => Array()
			));
		}
		
		/* $li_attr = Array(
			'style' => 'width: '.$width.'px;',
		); */
		
		if($class_li)$li_attr['class'] = $class_li.($cont['nivel']%3+1);
		
		$li = html(Array(
			'tag' => 'li',
			'val' => $a,
			'attr' => $li_attr
		));
		
		$menu .= $tabs.$li."\n";
		
		if($count == $num_links - 1){
			for($n=1;$n<$nivel_atual;$n++){
				$tabs = ""; for($i=1;$i<$nivel_atual;$i++) $tabs .= $tab;
				$menu .= $tabs.$bul;
			}
			
			$menu .= $bul;
		}
		
		if($num_links <= $entrada_por_coluna){
			if($count == $num_links - 1){
				$menus = html(Array(
					'tag' => 'div',
					'val' => $menu,
					'attr' => Array(
						'style' => 'margin: '.$margin.'px; width: '.$width.'px;display:'.$display.';',
						'class' => 'menu_div',
						'id' => '_menu_'.$id,
					)
				));
			}
		} else {
			if($count % $entrada_por_coluna2 == 0 && $count > 0){
				for($n=0;$n<$nivel_atual;$n++){
					$tabs = ""; for($i=1;$i<$n;$i++) $tabs .= $tab;
					$menu .= $tabs.$bul;
				}
				
				$divs .= html(Array(
					'tag' => 'div',
					'val' => $menu,
					'attr' => Array(
						'style' => 'width: '.$width.'px; float: left;'.($coluna != $colunas ? 'border-right: '.$borda.';padding-right: '.$margin.'px;' : '').($coluna > 1 ? 'padding-left: '.$margin.'px;' : ''),
						'class' => 'menu_coluna',
					)
				));
				
				$menu = false;
				for($n=0;$n<$nivel_atual;$n++){
					$tabs = ""; for($i=1;$i<$n;$i++) $tabs .= $tab;
					$menu .= $tabs.$ul;
				}
				
				$fechou_tags = true;
				$coluna++;
			}
			
			if($count == $num_links - 1){
				if(!$fechou_tags){
					for($n=1;$n<$nivel_atual;$n++){
						$tabs = ""; for($i=1;$i<$nivel_atual;$i++) $tabs .= $tab;
						$menu .= $tabs.$bul;
					}
					
					$divs .= html(Array(
						'tag' => 'div',
						'val' => $menu,
						'attr' => Array(
							'style' => 'width: '.$width.'px; float: left;padding-left: '.$margin.'px;',
							'class' => 'menu_coluna2',
						)
					));
				}
				
				$menus = html(Array(
					'tag' => 'div',
					'val' => $divs.$clear,
					'attr' => Array(
						'style' => 'width: '.($colunas*$width+(2*$margin*($colunas-1)+$margin)).'px;display:'.$display.';',
						'class' => 'menu_div2',
						'id' => '_menu_'.$id,
					)
				));
			}
			
			$fechou_tags = false;
		}
		
		$count++;
	}
	
	return $menus;
}

function galerias(){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_CAMINHO;
	global $_MENU_DINAMICO;
	global $_MENU_NAO_MUDAR;
	global $_OPCAO;
	global $_DESATIVAR_PADRAO;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	
	if(!$_DESATIVAR_PADRAO['galerias']){
		
		$_MENU_NAO_MUDAR = true;
		
		$id = $_CAMINHO[1];
		
		$replacement = '';
		$pattern = '/galerias-imagens_/i';
		if(preg_match($pattern, $id) > 0){
			$id = preg_replace($pattern, $replacement, $id);
		} else {
			$_INTERFACE['forcar_inicio'] = true;
		}
		
		//$_MENU_DINAMICO = $_OPCAO = $id;
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$galerias = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_galerias',
				'nome',
			))
			,
			"galerias",
			"WHERE identificador='".$id."'"
		);
		if($connect_db)banco_fechar_conexao();
		
		$imagens = interface_layout(Array(
			'opcao' => 'galerias_imagens_pretty_photo',
			'ferramenta' => 'galerias_imagens_pretty_photo',
			'frame_width' => false,
			'frame_margin' => '6',
			'tabela_nome' => 'imagens',
			'tabela_extra' => "WHERE status='A' AND id_galerias='".$galerias[0]['id_galerias']."'",
			'tabela_order' => "id_imagens DESC" . ($limite ? " LIMIT ".$limite : ""),
			'tabela_limit' => true,
			'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
			'tabela_id_posicao' => 0,
			'tabela_campos' => Array(
				'descricao',
				'local_mini',
				'local_media',
				'local_grande',
				'local_original',
			),
			'imagem_pequena' => 'local_mini',
			'imagem_grande' => 'local_original',
			'menu_paginas_id' => 'galerias_'.$id,
			'num_colunas' => 10,
			'link_externo' => true,
			'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias/#id/',
			'link_target' => '_self',
			'menu_pagina_embaixo' => true,
			'titulo_class' => 'fotos-galerias-titulo',
			'link_class' => 'fotos-imagens-link',
			'link_class_ajuste_margin' => 4,
			'menu_dont_show' => true, // Título da Informação
			'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		));
		
		$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
		$pagina = modelo_tag_val($modelo,'<!-- galerias < -->','<!-- galerias > -->');
		
		$pagina = modelo_var_troca($pagina,"#nome#",$galerias[0]['nome']);
		$pagina = modelo_var_troca($pagina,"#imagens#",$imagens);
		
		if($_PROJETO['galerias'])
		if($_PROJETO['galerias']['conteiner_posicao']){
			$_VARIAVEIS_JS['conteiner_posicao'] = $_PROJETO['galerias']['conteiner_posicao'];
			$_VARIAVEIS_JS['conteiner_posicao_x'] = $_PROJETO['galerias']['conteiner_posicao_x'];
			$_VARIAVEIS_JS['conteiner_posicao_y'] = $_PROJETO['galerias']['conteiner_posicao_y'];
			$_VARIAVEIS_JS['conteiner_posicao_efeito'] = $_PROJETO['galerias']['conteiner_posicao_efeito'];
			$_VARIAVEIS_JS['conteiner_posicao_tempo'] = $_PROJETO['galerias']['conteiner_posicao_tempo'];
		}
		
		return layout($pagina);
	}
}

function galerias_videos(){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_CAMINHO;
	global $_MENU_DINAMICO;
	global $_MENU_NAO_MUDAR;
	global $_OPCAO;
	global $_DESATIVAR_PADRAO;
	
	if(!$_DESATIVAR_PADRAO['galerias_videos']){
		
		$_MENU_NAO_MUDAR = true;
		
		$id = $_CAMINHO[1];
		
		$replacement = '';
		$pattern = '/galerias_videos_/i';
		if(preg_match($pattern, $id) > 0){
			$id = preg_replace($pattern, $replacement, $id);
		} else {
			$_INTERFACE['forcar_inicio'] = true;
		}
		
		//$_MENU_DINAMICO = $_OPCAO = $id;
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$galerias = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_galerias_videos',
				'nome',
			))
			,
			"galerias_videos",
			"WHERE identificador='".$id."'"
		);
		if($connect_db)banco_fechar_conexao();
		
		$videos = interface_layout(Array(
			'opcao' => 'galerias_videos_youtube_pretty_photo',
			'ferramenta' => 'vídeos dessa galeria',
			'frame_width' => false,
			'frame_margin' => 6,
			'tabela_nome' => 'videos',
			'tabela_extra' => "WHERE status='A' AND id_galerias_videos='".$galerias[0]['id_galerias_videos']."'",
			'tabela_order' => "id_videos DESC" . ($limite ? " LIMIT ".$limite : ""),
			'tabela_limit' => true,
			'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
			'tabela_id_posicao' => 0,
			'tabela_campos' => Array(
				'descricao',
				'imagem_mini',
				'codigo',
			),
			'imagem_pequena' => 'imagem_mini',
			'codigo' => 'codigo',
			'menu_paginas_id' => 'galerias_videos_'.$id,
			'num_colunas' => 8,
			'link_externo' => true,
			'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias-videos/#id/',
			'link_target' => '_self',
			'menu_pagina_embaixo' => true,
			'titulo_class' => 'fotos-galerias-videos-titulo',
			'link_class' => 'fotos-videos-link',
			'link_class_ajuste_margin' => 4,
			'menu_dont_show' => true, // Título da Informação
			'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		));
		
		$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'] . 'projeto.html');
		$pagina = modelo_tag_val($modelo,'<!-- galerias_videos < -->','<!-- galerias_videos > -->');
		
		$pagina = modelo_var_troca($pagina,"#nome#",$galerias[0]['nome']);
		$pagina = modelo_var_troca($pagina,"#videos#",$videos);
		
		return layout($pagina);
	}
}

function galeria_facebook(){
	global $_CAMINHO;
	global $_SYSTEM;
	global $_PROJETO;
	global $_CONEXAO_BANCO;
	
	$margin = '6';
	$arr = explode('_',$_CAMINHO[1]);
	$arr2 = explode('.',$arr[3]);
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$galerias = banco_select_name
	(
		banco_campos_virgulas(Array(
			'identificador',
			'nome',
			'descricao',
		))
		,
		"galerias",
		"WHERE id_galerias='".$arr[1]."'"
	);
	$imagens = banco_select_name
	(
		banco_campos_virgulas(Array(
			'descricao',
		))
		,
		"imagens",
		"WHERE id_imagens='".$arr2[0]."'"
	);

	$imagens_galeria = interface_layout(Array(
		'opcao' => 'galerias_imagens_pretty_photo',
		'ferramenta' => 'galerias_imagens_pretty_photo',
		'frame_width' => false,
		'frame_margin' => $margin,
		'tabela_nome' => 'imagens',
		'tabela_extra' => "WHERE status='A' AND id_galerias='".$arr[1]."'",
		'tabela_order' => "id_imagens DESC" . ($limite ? " LIMIT ".$limite : ""),
		'tabela_limit' => true,
		'menu_limit' => ($limite ? " LIMIT ".$limite : ""),
		'tabela_id_posicao' => 0,
		'tabela_campos' => Array(
			'descricao',
			'local_mini',
			'local_media',
			'local_grande',
			'local_original',
		),
		'imagem_pequena' => 'local_mini',
		'imagem_grande' => 'local_original',
		'menu_paginas_id' => 'galerias_'.$id,
		'num_colunas' => 10,
		'link_externo' => true,
		'link_unico' => '/'.$_SYSTEM['ROOT'].'galerias/#id/',
		'link_target' => '_self',
		'menu_pagina_embaixo' => true,
		'titulo_class' => 'fotos-galerias-titulo',
		'link_class' => 'fotos-imagens-link',
		'link_class_ajuste_margin' => 4,
		'menu_dont_show' => true, // Título da Informação
		'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
	));
	
	if($connect_db)banco_fechar_conexao();
	
	$_PROJETO['layout-head-in'] = '
	<meta name="description" content="'.($imagens[0]['descricao']?$imagens[0]['descricao']:$galerias[0]['descricao']).'" />
	<meta property="og:title" content="'.$galerias[0]['nome'].'" />
	<meta property="og:description" content="'.($imagens[0]['descricao']?$imagens[0]['descricao']:$galerias[0]['descricao']).'" />
	<meta property="og:image" content="http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'files/galeria/'.$_CAMINHO[1].'"/>';
	
	return layout('<h1>'.$galerias[0]['nome'].'</h1><img src="/'.$_SYSTEM['ROOT'].'files/galeria/'.$_CAMINHO[1].'" style="margin:'.$margin.'px;" />'.$imagens_galeria);
}

function criar_identificador($id){
	$tam_max_id = 80;
	$id = retirar_acentos(trim($id));
	
	$pre_id_aux = explode('-',$id);
	
	if($pre_id_aux)
	foreach($pre_id_aux as $pre){
		$count++;
		if($pre){
			$pre_id .= $pre;
			
			if(strlen($pre_id) > $tam_max_id){
				break;
			} else {
				$pre_id .= (count($pre_id_aux) > $count ? '-' : '');
			}
		}
	}
	
	$id = $pre_id;
	
	$id_aux = explode('-',$id);
	$count = 0;
	if(is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
	}
	
	return $id;
}

function procurar_parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_LISTA_2;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_OPCAO;
	global $_ALERTA;
	global $_CAMINHO;
	global $_IDENTIFICADOR;
	global $_HTML_DADOS;
	global $_LAYOUT_BASICO;
	global $_PESQUISA_BROWSER;
	global $_PROJETO;
	
	$informacao_titulo = '';
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => 'procurar', // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo, // Título da Informação
		'menu_dont_show' => true, // Título da Informação
		'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		'menu_pagina_acima' => false, // Colocar o menu em cima
		'menu_pagina_embaixo' => true, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_procurar", // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'informacao_abaixo' => $informacao_abaixo,
		'informacao_acima' => $informacao_acima,
		'css' => Array(
			'tabela_lista' => 'tabela_lista2',
			'lista_header' => 'lista_header2',
			'lista_cel' => 'lista_cel2',
		),
	);
	
	if($_PROJETO['procurar']){
		foreach($_PROJETO['procurar'] as $chave => $valor){
			$parametros[$chave] = $valor;
		}
	}
	
	$params = Array(
		'id' => 'procurar',
		'parametros' => $parametros,
	);
	
	$saida = projeto_modificar_campos($params);
	
	$parametros = $saida['parametros'];
	
	return $parametros;
}

function procurar(){
	global $_INTERFACE_OPCAO;
	global $_DESATIVAR_PADRAO;
	
	if($_REQUEST['_mobile']){
		$_REQUEST['pesquisa'] = $_REQUEST['pesquisa'];
	}
	
	if(!$_DESATIVAR_PADRAO['procurar']){
		$_INTERFACE_OPCAO = 'lista';
		
		return layout(procurar_layout(procurar_parametros_interface()));
	}
}

function modulos_add_tags($tags){
	global $_MODULOS_TAGS;
	
	if($tags)
		$_MODULOS_TAGS = array_merge($tags, $_MODULOS_TAGS);
}

function modulos($params){
	global $_GALERIA_LOCAL_URL;
	global $_SYSTEM;
	global $_MOBILE;
	global $_OPCAO;
	global $_INTERFACE;
	global $_DESATIVAR_PADRAO;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_MODULO_EXTERNO;
	
	$modulo_tag = $params['modulo_tag'];
	
	if(!$_DESATIVAR_PADRAO['modulos']){
		switch($modulo_tag){
			case '#formulario_contato#': 
				if($_MOBILE){
					$modulo = '
	<div class="ui-body ui-body-c">
		<h3>Formulário de Contato</h3>
		<p>Preencha os campos e em seguida clique no botão enviar.</p>
		<form id="form_contato" name="form_contato" method="post" action="!#caminho_raiz#!" data-transition="flip">
			<div data-role="fieldcontain">
				<label for="nome">Nome:</label>
				<input type="text" name="nome" id="nome" value="" />
				<label for="email">Email:</label>
				<input type="email" name="email" id="email" value="" />
				<label for="mensagem">Mensagem:</label>
				<textarea name="mensagem" id="mensagem"></textarea>
			</div>
			<input name="opcao" type="hidden" value="contato_banco" />
			<input type="submit" name="submit" id="email" value="Enviar" data-inline="true" />
		</form>
	</div>';
				} else {
					$modelo = paginaModelo($_SYSTEM['TEMA_PATH'].'projeto.html');
					$modulo = modelo_tag_val($modelo,'<!-- #modulos'.$modulo_tag.' < -->','<!-- #modulos'.$modulo_tag.' > -->');
				}
			break;
			case '#formulario_usuario#':
				$modelo = paginaModelo($_SYSTEM['TEMA_PATH'].'projeto.html');
				$modulo = modelo_tag_val($modelo,'<!-- #modulos'.$modulo_tag.' < -->','<!-- #modulos'.$modulo_tag.' > -->');
				
				$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
			break;
			case '#galerias#':
				$url_partes = explode('/',$_SERVER["REQUEST_URI"]);
				$opcoes_aux = $url_partes[count($url_partes)-1];
				$opcoes_arr = explode('&galeria_select_id',$opcoes_aux);
				$opcoes = $opcoes_arr[0];
				$modulo = pagina_galeria(Array(
					'imagem_inicial_id' => $_REQUEST['cod_img'],
					'local_url' => ($_GALERIA_LOCAL_URL ? $_GALERIA_LOCAL_URL : $opcoes),
				));
			break;
		}
	}
	
	$params = Array(
		'modulo_tag' => $modulo_tag,
		'modulo' => $modulo,
	);
	
	$modulo = projeto_modulos($params);
	
	return $modulo;

}

function conteudo(){
	global $_SYSTEM;
	global $_HTML_DADOS;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_OPCAO;
	global $_GALERIA_LOCAL_URL;
	global $_CAMINHO;
	global $_MOBILE;
	global $_MENU_ID;
	global $_MODULOS_TAGS;
	global $_ALERTA;
	global $_DESATIVAR_PADRAO;
	global $_PROJETO;
	global $_LAYOUT_MOBILE;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_MODULO_B2MAKE;
	
	if($_REQUEST['caminho'] == '404.shtml') return;
	
	if(!$_MODULO_B2MAKE){
		switch($_CAMINHO[0]){
			case 'autenticar':
			case 'pagamento':
			case 'e-services':
			case 'platform':
				// Nada a fazer
			break;
			default:
				redirecionar('signin');
		}
	}
	
	if($_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho']){
		$carrinho_id = $_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho'];
		$_VARIAVEIS_JS['ecommerce_limpar_carrinho'] = true;
		$_VARIAVEIS_JS['ecommerce_id_pedido'] = ($carrinho_id[0] == "E" ? $carrinho_id : '0');
		$_VARIAVEIS_JS['ecommerce_vendedor_nome'] = $_HTML['TITULO'];
		$_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho'] = false;
	}
	
	if(!$_DESATIVAR_PADRAO['conteudo']){
		$id = $_CAMINHO[0];
		
		if($_MENU_ID[$_OPCAO]) $forcar_id = $id = $_MENU_ID[$_OPCAO];
		
		if($_PROJETO['conteudo']){
			if($_PROJETO['conteudo'][$id]){
				$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
				$modulo = modelo_tag_val($modelo,'<!-- '.$id.' < -->','<!-- '.$id.' > -->');
				$modulo = modelo_var_troca($modulo,"#opcao",$id);
			}
		}
		
		banco_conectar();
		
		if(!$_DESATIVAR_PADRAO['conteudo_conteiners']){
			switch($id){
				case "noticias":	
					$modulo = noticias_lista();
					if($_CAMINHO[1]){
						$forcar_h1 = 'Notícias';
						$forcar_h1_to_h2 = true;
					} else {
						$nao_mostrar_filhos = true;
					}
					$modulo_noticias = true;
				break;
				case "blog":		
					$modulo = blog(Array());
				break;
				
			}
		}
		
		banco_fechar_conexao();
		
		$parametros = Array(
			'modulos_tags' => $_MODULOS_TAGS,
			'forcar_id' => $forcar_id,
			'info_abaixo' => $modulo,
			'forcar_h1' => $forcar_h1,
			'forcar_h1_to_h2' => $forcar_h1_to_h2,
			'forcar_sub_to_tit' => $forcar_sub_to_tit,
			'nao_mostrar_filhos' => $nao_mostrar_filhos,
			'modulo_noticias' => $modulo_noticias,
		);
		
		$params = Array(
			'id' => 'conteudo',
			'parametros' => $parametros,
		);
		
		$saida = projeto_modificar_campos($params);
		
		$parametros = $saida['parametros'];
		
		$retorno = conteudo_mostrar($parametros);
		
		if($retorno['erro']){
			
			if($id)
				return pagina_nao_encontrada();
			else
				return pagina_inicial();
		} else {
			if($_MOBILE){
				$layout = $_LAYOUT_MOBILE;
				
				$layout = modelo_var_troca($layout,"#title#",$retorno['variaveis']['titulo']);
				$layout = modelo_var_troca($layout,"#body#",$retorno['pagina']);
				
				return $layout;
			} else {
				return layout($retorno['pagina']);
			}
		}
	}
}

function blog_dinamico($params){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_MOBILE;
	global $_OPCAO;
	global $_INTERFACE;
	global $_MENU_DINAMICO;
	global $_HTML;
	
	$id = $params['id'];
	$limite = $params['limite'];
	$nao_mostrar_menu = $params['nao_mostrar_menu'];
	
	if($_MOBILE){
		$layout_conteudo = '<div data-role="collapsible" data-collapsed="true" data-theme="c"> 
	<h3>#titulo#</h3> 
	<p>
		<div style="float:right;">#data#</div>
		<h3>#titulo#</h3>
		#texto#
		<a href="/'.$_SYSTEM['ROOT'].$id.'/#identificador#/">Leia mais...</a>
	</p> 
</div>';
		/* $modelo_conteiner = '
<div data-role="content" data-theme="a">
#conteudo#
</div>
';	 */
	} else {
		$layout_conteudo = '<div>
	<div style="float:right;">#data#</div>
	<h1><a href="/'.$_SYSTEM['ROOT'].$id.'/#identificador#/">#titulo#</a></h1>
	#texto#
</div>';
		$layout_separador = '<hr style="margin-top: 20px; margin-bottom: 20px; border-top: 1px #FFF solid; border-left: none; border-right: none; border-bottom: none;" />';
	}
	
	if(!$_CAMINHO[1]){
		if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
		$parametros = Array(
			'opcao' => 'conteudos',
			'ferramenta' => 'conteudos',
			'tabela_nome' => 'conteudo as t1,conteudo as t2',
			'tabela_extra' => "WHERE t2.identificador='".$id."' AND t1.id_conteudo_pai=t2.id_conteudo AND t1.status='A'",
			'tabela_order' => "t1.ordem ASC,t1.tipo DESC,t1.id_conteudo DESC" . ($limite ? " LIMIT ".$limite : ""),
			'tabela_limit' => false,
			'tabela_id' => 'id_conteudo',
			'tabela_identificador' => 'identificador',
			'tabela_id_posicao' => 0,
			'tabela_campos' => Array(
				't1.titulo',
				't1.data',
				't1.identificador',
				't1.texto',
			),
			'menu_paginas_id' => 'menu_'.$id,
			'menu_pagina_embaixo' => true,
			'menu_paginas_inicial' => true,
			'data' => true,
			'nao_mostrar_menu' => $nao_mostrar_menu,
			'texto_limitar' => 500,
			'layout_separador' => $layout_separador,
			'layout_conteudo' => $layout_conteudo,
			'modelo_conteiner' => $modelo_conteiner,
			'texto_limitar_sep' => '<!-- sep -->',
			'menu_dont_show' => true, // Título da Informação
			'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		);
		
		$_MENU_DINAMICO = 'menu_'.$id;
		
		$params = Array(
			'id' => 'blog_dinamico',
			'parametros' => $parametros,
		);
		
		$saida = projeto_modificar_campos($params);
		
		$parametros = $saida['parametros'];
		
		return interface_layout($parametros);
	} else {
		return '';
	}
}

function blog($params){
	global $_SYSTEM;
	global $_CAMINHO;
	global $_MOBILE;
	global $_OPCAO;
	global $_INTERFACE;
	global $_DESATIVAR_PADRAO;
	global $_PROJETO;
	
	if(!$_DESATIVAR_PADRAO['blog']){
		$limite = $params['limite'];
		$nao_mostrar_menu = $params['nao_mostrar_menu'];
		
		if($_MOBILE){
			$layout_conteudo = '<div data-role="collapsible" data-collapsed="true" data-theme="c"> 
		<h3>#titulo#</h3> 
		<p>
			<div style="float:right;">#data#</div>
			<h3>#titulo#</h3>
			#texto#
			<a href="/'.$_SYSTEM['ROOT'].'blog/#identificador#/">Leia mais...</a>
		</p> 
	</div>';
			/* $modelo_conteiner = '
	<div data-role="content" data-theme="a">
	#conteudo#
	</div>
	';	 */
		} else {
			$layout_conteudo = '<a class="blog-conteiner" href="/'.$_SYSTEM['ROOT'].'blog/#identificador#/">
		<div style="float:right;" class="blog-data">#data#</div>
		<div class="blog-imagem" style="background-image:url(#imagem_pequena#);"></div>
		<div class="blog-titulo">#titulo#</div>
		<div class="blog-texto">#texto#</div>
	</a>';
			$layout_separador = '<hr class="b2make-separador"></hr>';
			
			if($_PROJETO['blog']){
				if($_PROJETO['blog']['layout_conteudo']){
					$layout_conteudo = $_PROJETO['blog']['layout_conteudo'];
				}
			}
			
			if($_PROJETO['blog']){
				if($_PROJETO['blog']['layout_separador']){
					$layout_separador = $_PROJETO['blog']['layout_separador'];
				}
			}
		}
		
		$tabela_campos = Array(
			't1.titulo',
			't1.imagem_pequena',
			't1.data',
			't1.identificador',
			't1.texto',
		);
		
		if($_PROJETO['blog']){
			if($_PROJETO['blog']['tabela_campos']){
				$tabela_campos = array_merge($tabela_campos,$_PROJETO['blog']['tabela_campos']);
			}
		}
		
		if(!$_CAMINHO[1]){
			if($_OPCAO == 'blog')$_INTERFACE['forcar_inicio'] = true;
			$parametros = Array(
				'opcao' => 'conteudos',
				'ferramenta' => 'conteudos',
				'tabela_nome' => 'conteudo as t1,conteudo as t2',
				'tabela_extra' => "WHERE t2.identificador='blog' AND t1.id_conteudo_pai=t2.id_conteudo AND t1.status='A'",
				'tabela_order' => "t1.ordem ASC,t1.tipo DESC,t1.id_conteudo DESC" . ($limite ? " LIMIT ".$limite : ""),
				'tabela_limit' => false,
				'tabela_id' => 'id_conteudo',
				'tabela_identificador' => 'identificador',
				'tabela_id_posicao' => 0,
				'tabela_campos' => $tabela_campos,
				'menu_paginas_id' => 'menu_blog',
				'menu_pagina_embaixo' => true,
				'menu_paginas_inicial' => true,
				'layout' => Array('imagem_pequena'=>'#dados#'),
				'no_defaults' => Array('imagem_pequena'=>true),
				'data' => true,
				'nao_mostrar_menu' => $nao_mostrar_menu,
				'texto_limitar' => 500,
				'layout_separador' => $layout_separador,
				'layout_conteudo' => $layout_conteudo,
				'modelo_conteiner' => $modelo_conteiner,
				'texto_limitar_sep' => '<!-- sep -->',
				'menu_dont_show' => true, // Título da Informação
				'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
			);
			
			$retorno = interface_layout($parametros);
			
			if($_PROJETO['blog']){
				if($_PROJETO['blog']['conteiner']){
					$retorno = modelo_var_troca($_PROJETO['blog']['conteiner'],"#blog#",$retorno);
				}
			}
			
			if($_OPCAO == 'menu_blog') $layout_separador . $retorno;
			
			return $retorno;
		} else {
			return '';
		}
	}
}

function noticias_lista_dinamico($params){
	global $_SYSTEM;
	global $_INTERFACE;
	global $_OPCAO;
	global $_MENU_DINAMICO;
	global $_HTML;
	
	$id = $params['id'];
	$limite = $params['limite'];
	$nao_mostrar_menu = $params['nao_mostrar_menu'];
	
	if($_OPCAO == $id)$_INTERFACE['forcar_inicio'] = true;
	
	$parametros = Array(
		'opcao' => 'noticias',
		'ferramenta' => 'noticias',
		'tabela_nome' => 'conteudo as t1,conteudo as t2',
		'tabela_extra' => "WHERE t2.identificador='".$id."' AND t1.id_conteudo_pai=t2.id_conteudo AND t1.status='A'",
		'tabela_order' => "t1.ordem ASC,t1.tipo DESC,t1.data DESC".($limite?" LIMIT ".$limite:""),
		'tabela_limit' => ($limite?$limite:false),
		'tabela_id' => 'id_conteudo',
		'tabela_identificador' => 'identificador',
		'tabela_tipo' => 'tipo',
		'tabela_id_posicao' => 0,
		'tabela_campos' => Array(
			't1.id_conteudo',
			't1.tipo',
			't1.titulo',
			't1.data',
			't1.identificador',
		),
		'menu_paginas_id' => 'menu_'.$id,
		'menu_pagina_embaixo' => true,
		'nao_mostrar_menu' => $nao_mostrar_menu,
		'noticia_link' => '/'.$_SYSTEM['ROOT'].$id.'/#id/',
		'categoria_link' => '/'.$_SYSTEM['ROOT'].$id.'/#id/',
		'menu_dont_show' => true, // Título da Informação
		'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
	);
	
	$_MENU_DINAMICO = 'menu_'.$id;
	
	$params = Array(
		'id' => 'noticias_lista_dinamico',
		'parametros' => $parametros,
	);
	
	$saida = projeto_modificar_campos($params);
	
	$parametros = $saida['parametros'];
	
	return interface_layout($parametros);
}

function noticias_lista($params = false){
	global $_SYSTEM;
	global $_INTERFACE;
	global $_OPCAO;
	global $_DESATIVAR_PADRAO;
	global $_PROJETO;
	
	if(!$_DESATIVAR_PADRAO['noticias_lista']){
		$limite = $params['limite'];
		$nao_mostrar_menu = $params['nao_mostrar_menu'];
		
		if($_OPCAO == 'noticias')$_INTERFACE['forcar_inicio'] = true;
		
		$parametros = Array(
			'opcao' => 'noticias',
			'ferramenta' => 'noticias',
			'tabela_nome' => 'conteudo as t1,conteudo as t2',
			'tabela_extra' => "WHERE t2.identificador='noticias' AND t1.id_conteudo_pai=t2.id_conteudo AND t1.status='A'",
			'tabela_order' => "t1.ordem ASC,t1.tipo DESC,t1.data DESC".($limite?" LIMIT ".$limite:""),
			'tabela_limit' => ($limite?$limite:false),
			'tabela_id' => 'id_conteudo',
			'tabela_identificador' => 'identificador',
			'tabela_tipo' => 'tipo',
			'tabela_id_posicao' => 0,
			'tabela_campos' => Array(
				't1.id_conteudo',
				't1.tipo',
				't1.titulo',
				't1.data',
				't1.identificador',
			),
			'menu_paginas_id' => 'menu_noticias',
			'menu_pagina_embaixo' => true,
			'nao_mostrar_menu' => $nao_mostrar_menu,
			'noticia_link' => '/'.$_SYSTEM['ROOT'].'noticias/#id/',
			'categoria_link' => '/'.$_SYSTEM['ROOT'].'noticias/#id/',
			'menu_dont_show' => true, // Título da Informação
			'forcar_inicio' => $_INTERFACE['forcar_inicio'], // Reiniciar do menu
		);
		
		if($_PROJETO['noticias_lista'])
		foreach($_PROJETO['noticias_lista'] as $key => $valor){
			$parametros[$key] = $valor;
		}
		
		return interface_layout($parametros);
	}
}

function layout($pagina){
	global $_SYSTEM;
	global $_LAYOUT_BASICO;
	global $_DESATIVAR_PADRAO;
	global $_MOBILE;
	global $_LAYOUT_MOBILE;
	global $_HTML;
	
	if(!$_DESATIVAR_PADRAO['pagina_layout']){
		if(!$_LAYOUT_BASICO){
			$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
			$layout = modelo_tag_val($modelo,'<!-- layout < -->','<!-- layout > -->');
			
			$pagina = modelo_var_troca($layout,"#body",$pagina);
		}
	}
	
	$params = Array(
		'pagina' => $pagina,
	);
	
	$pagina = projeto_pagina_layout($params);
	
	if($_MOBILE){
		$layout = $_LAYOUT_MOBILE;
		
		$layout = modelo_var_troca($layout,"#title#",$_HTML['titulo']);
		$layout = modelo_var_troca($layout,"#body#",$pagina);
		
		return $layout;
	} else {
		return $pagina;
	}
}

function pagina_inicial(){
	global $_HTML;
	global $_MOBILE;
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_LAYOUT_MOBILE;
	
	if(!$_DESATIVAR_PADRAO['pagina_inicial']){
		if($_MOBILE){
			$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
			$pagina = paginaTagValor($modelo,'<!-- inicio < -->','<!-- inicio > -->');
			
			$layout = $_LAYOUT_MOBILE;
			
			$layout = modelo_var_troca($layout,"#title#",$_HTML['titulo']);
			$layout = modelo_var_troca($layout,"#body#",$pagina);
			
			$pagina = $layout;
		} else {	
			$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
			$pagina = paginaTagValor($modelo,'<!-- inicio < -->','<!-- inicio > -->');
		}
	}
	
	$params = Array(
		'pagina' => $pagina,
	);
	
	$pagina = projeto_pagina_inicial($params);
	
	return $pagina;
}

function pagina_nao_encontrada(){
	global $_SYSTEM;
	global $_HTML;
	global $_DESATIVAR_PADRAO;
	global $_HTML_DADOS;
	global $_LAYOUT_MOBILE;
	global $_MOBILE;
	
	$_HTML_DADOS['404'] = true;
	
	if(!$_DESATIVAR_PADRAO['pagina_nao_encontrada']){
		if($_MOBILE){
			$modelo = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
			$pagina = paginaTagValor($modelo,'<!-- pagina_nao_encontrada < -->','<!-- pagina_nao_encontrada > -->');
			
			$pagina = modelo_var_troca($pagina,"#imagem#",'/'.$_SYSTEM['ROOT'].'images/icons/pag-nao-encontrada-'.($_HTML['PAGINA_404_BRANCA']?'branco':'preto').'.png');
			
			$layout = $_LAYOUT_MOBILE;
			
			$layout = modelo_var_troca($layout,"#title#",$_HTML['titulo']);
			$layout = modelo_var_troca($layout,"#body#",$pagina);
			
			$pagina = $layout;
		} else {
			$pagina = modelo_abrir($_SYSTEM['TEMA_PATH'].'projeto.html');
			$pagina = paginaTagValor($pagina,'<!-- pagina_nao_encontrada < -->','<!-- pagina_nao_encontrada > -->');
			
			$pagina = modelo_var_troca($pagina,"#imagem#",'/'.$_SYSTEM['ROOT'].'images/icons/pag-nao-encontrada-'.($_HTML['PAGINA_404_BRANCA']?'branco':'preto').'.png');
		}
	}
	
	$params = Array(
		'pagina' => $pagina,
	);
	
	$pagina = projeto_pagina_nao_encontrada($params);
	
	return $pagina;
}

function xml(){
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	
	if(!$_DESATIVAR_PADRAO['xml']){
		
	}
	
	$params = Array(
		'entrada' => $entrada,
	);
	
	$saida = projeto_xml($params);
	
	return $saida;
}

function ajax(){
	global $_SYSTEM;
	global $_DESATIVAR_PADRAO;
	global $_OPCAO;
	global $_CAMINHO;
	
	if(!$_DESATIVAR_PADRAO['ajax']){
		switch($_REQUEST['ajax_option']){
			case 'e-services':						$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'eservices/index.php'); $flag_nao_entrar2 = true; break;
		}
		
		if(!$flag_nao_entrar2){
			$caminho = explode('/',strtolower($_OPCAO));
			
			$_CAMINHO = $caminho;
			if($_CAMINHO[count($_CAMINHO)-1] == NULL){
				array_pop($_CAMINHO);
			}
			
			$_OPCAO = $_CAMINHO[0];
			
			switch($_OPCAO){
				case 'calcular-frete':
				case 'ecommerce-cupom':
				case 'pagar-pedidos':
				case 'meus-pedidos':
				case 'loja-online':
				case 'voucher-concluir':
				case 'voucher-temas':
				case 'voucher-enviar-email':
				case 'voucher-pedidos':
				case 'voucher-presente':				$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/index.php'); $flag_nao_entrar = true; break;
				case 'signup_facebook_vars':
				case 'help-texto':
				case 'site-host':
				case 'minha-conta-usuario':
				case 'minha-conta-email':
				case 'minha-conta-historico':			$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'autenticar/index.php'); $flag_nao_entrar = true; break;
				case 'e-services':						$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'eservices/index.php'); $flag_nao_entrar = true; break;
				case 'platform':						$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'platform/index.php'); $flag_nao_entrar = true; break;
			}
		}
		
		if(!$flag_nao_entrar && !$flag_nao_entrar2){
			$_LISTA['tabela']['nome']		=	'usuario';
			$_LISTA['tabela']['campo']		=	'usuario';
			$_LISTA['tabela']['id']			=	'id_usuario';
			$_LISTA['tabela']['status']		=	'status';
			$_LISTA['ferramenta']			=	'Usuário';
			$_LISTA['ferramenta_unidade']	=	'o usuário';

			if($_REQUEST['usuario']){
				seguranca_delay();
				
				banco_conectar();
				
				$resultado = banco_select
				(
					$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
					$_LISTA['tabela']['nome'],
					"WHERE ".$_LISTA['tabela']['campo']."='" . $_REQUEST['usuario'] . "' AND status!='D'"
				);
				
				banco_fechar_conexao();

				if($resultado){
					$saida = "sim";
				} else {
					$saida = "nao";
				}
			}
			
			if($_REQUEST['email']){
				seguranca_delay();
				
				banco_conectar();
				
				$resultado = banco_select
				(
					$_LISTA['tabela']['id'],
					$_LISTA['tabela']['nome'],
					"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
				);
				$resultado2 = banco_select
				(
					"id_emails",
					"emails",
					"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
				);
				
				banco_fechar_conexao();

				if($resultado || $resultado2){
					$saida = "sim";
				} else {
					$saida = "nao";
				}
			}
			
			if($_REQUEST['email_usuario']){
				seguranca_delay();
				
				banco_conectar();
				
				$resultado = banco_select
				(
					$_LISTA['tabela']['id'],
					$_LISTA['tabela']['nome'],
					"WHERE email='" . strtolower($_REQUEST['email_usuario']) . "' AND status!='D'"
				);
				
				banco_fechar_conexao();

				if($resultado){
					$saida = "sim";
				} else {
					$saida = "nao";
				}
			}
			
			if($_REQUEST['glossario']){
				banco_conectar();
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'palavra',
						'significado',
					))
					,
					"conteudo_glossario",
					"WHERE status='A'"
				);
				
				if($resultado)
				foreach($resultado as $res){
					$saida[] = Array(
						'term' => $res['palavra'],
						'type' => "0",
						'definition' => $res['significado'],
					);
				}
				
				$saida = json_encode($saida);
			}
			
			if($_REQUEST['recaptcha']){
				if(!recaptcha_verify()){
					$saida = 'nao';
				} else {
					$saida = 'sim';
				}
			}
			
			if($_REQUEST['mobile_variaveis_js']){
				$saida = $_SESSION[$_SYSTEM['ID'].'mobile_variaveis_js'];
			}
			
			if($_REQUEST['opcao'] == 'loja-online'){
				$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/index.php');
			}
		}
	}
	
	$params = Array(
		'entrada' => $entrada,
		'saida' => $saida,
	);
	
	$saida = projeto_ajax($params);
	
	return $saida;
}

function opcao_nao_econtrada(){
	if(function_exists('projeto_main_opcao')){
		return projeto_main_opcao();
	} else {
		return conteudo();
	}
}

function main(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_HTML;
	global $_OPCAO_ANTERIOR;
	global $_ID_ANTERIOR;
	global $_OPCAO;
	global $_CAMINHO;
	global $_AJAX_PAGE;
	global $_DESATIVAR_PADRAO;
	global $_VARIAVEIS_JS;
	global $_PROJETO;
	global $_DEBUG;
	
	if(isset($_REQUEST['xml']))			$xml = $_REQUEST['xml'];
	if(isset($_REQUEST['_b2make_debug']))	$_SESSION[$_SYSTEM['ID'].$_LOCAL_ID."b2make_debug"] = true;
	if(isset($_REQUEST['opcao']))			$_OPCAO = $opcao = $_REQUEST['opcao'];
	if(isset($_REQUEST['id']))				$id = $_REQUEST['id'];
	
	if(isset($_SESSION[$_SYSTEM['ID'].$_LOCAL_ID."b2make_debug"])) $_DEBUG = true;
	
	if(!isset($xml)){
		if(!isset($_REQUEST['ajax'])){
			if(!isset($_DESATIVAR_PADRAO['main'])){
				if(substr($_SERVER['SERVER_NAME'], 0, 4) === 'www.'){
					$protocol = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
					$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
					
					$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
					
					Header( "HTTP/1.1 301 Moved Permanently" );
					Header( "Location: ". $protocol . $dominio_sem_www.'/'.$_SYSTEM['ROOT'] . $_REQUEST['caminho']);
				}
				
				if($_SYSTEM['URL_301'] && !$_AJAX_PAGE){
					$urls = explode(';',trim($_SYSTEM['URL_301']));
					
					if($urls)
					foreach($urls as $url){
						if($url){
							$url_partes = explode(',',$url);
							if($url_partes[0])
							if($_REQUEST['caminho'] == $url_partes[0]){
								Header( "HTTP/1.1 301 Moved Permanently" ); 
								Header( "Location: http://".$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$url_partes[1] );
							}
							if("http://".$_SERVER['HTTP_HOST'].$_REQUEST['caminho'] == $url_partes[0]){
								Header( "HTTP/1.1 301 Moved Permanently" ); 
								Header( "Location: ".$url_partes[1] );
							}
							if("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] == $url_partes[0]){
								Header( "HTTP/1.1 301 Moved Permanently" );
								Header( "Location: ".$url_partes[1] );
							}
							if("https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] == $url_partes[0]){
								Header( "HTTP/1.1 301 Moved Permanently" );
								Header( "Location: ".$url_partes[1] );
							}
						}
					}
				}
				
				if(!$opcao){
					$opcao = $_OPCAO = $_CAMINHO[0];
				} else {
					$caminho = explode('/',strtolower($opcao));
					
					$_CAMINHO = $caminho;
					if($_CAMINHO[count($_CAMINHO)-1] == NULL){
						array_pop($_CAMINHO);
					}
					
					$opcao = $_CAMINHO[0];
				}
				
				$_OPCAO_ANTERIOR = $_SESSION[$_SYSTEM['ID'].$_LOCAL_ID."opcao_anterior"];
				$_ID_ANTERIOR = $_SESSION[$_SYSTEM['ID'].$_LOCAL_ID."id_anterior"];
				$_SESSION[$_SYSTEM['ID'].$_LOCAL_ID."opcao_anterior"] = $opcao;
				if(isset($id)) $_SESSION[$_SYSTEM['ID'].$_LOCAL_ID."id_anterior"] = $id;
				
				if($_SYSTEM['PRIMEIRA_EXECUCAO']){
					switch($opcao){
						case 'install-salvar':			$saida = install_salvar(); break;
						case '_maquina_testes':			$saida = maquina_testes(); break;
						default: 						$saida = install();
					}
				} else {
					if(isset($_PROJETO['MAIN_OPCAO'][$opcao])){
						$saida = call_user_func($_PROJETO['MAIN_OPCAO'][$opcao]['function'],$_PROJETO['MAIN_OPCAO'][$opcao]['params']);
					} else {
						switch($opcao){
							case 'entrar':
							case 'logout':					$saida = logout();break;
							//case 'login':					$saida = login();break;
							case 'logar':					$saida = logar();break;
							case 'emarkenting':				$saida = cadastrar_email();break;
							case 'menu_procurar':
							case 'procurar':				$saida = procurar(); break;
							case 'contato_banco':			$saida = contato_banco(); break;
							case 'comentarios_banco':		$saida = comentarios_banco(); break;
							case 'menu_blog':				$saida = blog(Array()); break;
							case 'menu_noticias': 			$saida = noticias_lista(Array()); break;
							case 'conteudo':				$saida = conteudo(); break;
							case 'galerias-imagens':		$saida = galerias(); break;
							case 'galerias-videos':			$saida = galerias_videos(); break;
							case 'cadastro_banco':			$saida = cadastro_banco(); break;
							case 'galeria-img-facebook':	$saida = galeria_facebook(); break;
							case 'nl':						$saida = newsletter(); break;
							case 'esqueceu-sua-senha':		$saida = esqueceu_senha(); break;
							case 'esqueceu_senha_banco':	$saida = esqueceu_senha_banco(); break;
							case 'gerar-nova-senha':		$saida = gerar_nova_senha(); break;
							case 'preview':					$saida = b2make_preview(); break;
							case 'instagram-authorization':	$saida = instagram_authorization(); break;
							case 'instagram-redirect':		$saida = instagram_authorized(); break;
							case 'redefinir_senha_banco':	$saida = redefinir_senha_banco(); break;
							case 'testes':					$saida = testes(); break;
							case 'b2make_teste_autenticar':
							case 'signature-cancel':
							case 'signature-cancel-confirm':
							case 'signature-canceled':
							case 'upgrade-plan-bd':
							case 'upgrade-plan':
							case 'my-profile':
							case 'payment':
							case 'payment-complete':
							case 'pagseguro-subscription':
							case 'pagseguro-notification':
							case 'pagseguro-return':
							case 'paypal-subscription':
							case 'paypal-notification':
							case 'paypal-return':
							case 'paypal-cancel':
							case 'signin-facebook':
							case 'signup-facebook':
							case 'signup':
							case 'signin':
							case 'signup-success':
							case 'signup-bd':
							case 'signin-bd':				
							case 'my-profile-bd':				
							case 'minha-conta':				
							case 'minha-conta-banco':				
							case 'login-facebook':				
							case 'cadastro-validar':				
							case 'autenticar-cadastro':				
							case 'form-autenticar':				
							case 'autenticar':				$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'autenticar/index.php'); $_MODULO_B2MAKE = true; break;
							case 'ecommerce-indisponivel':
							case 'indique':
							case 'indique-enviar':
							case 'duvidas':
							case 'duvidas-enviar':
							case 'meus-pedidos':
							case 'loja-online':
							case 'carrinho':
							case 'voucher':
							case 'voucher-form-presente':
							case 'pagseguro-notificacoes':
							case 'pagseguro-pagar':
							case 'pagseguro-retorno':
							case 'paypal-notificacoes':
							case 'paypal-pagar':
							case 'paypal-retorno':
							case 'paypal-cancelado':
							case 'paypal-returnurl':
							case 'paypal-cancelurl':
							case 'endereco-entrega':
							case 'endereco-entrega-salvar':
							case 'paginas':				$saida = require_once('../b2make-gestor/index.php'); $_MODULO_B2MAKE = true; break;
							case 'pagamento':				$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/index.php'); $_MODULO_B2MAKE = true; break;
							case 'e-services':				$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'eservices/index.php'); $_MODULO_B2MAKE = true; break;
							case 'platform':				$saida = require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'platform/index.php'); $_MODULO_B2MAKE = true; break;
							default: 						$saida = opcao_nao_econtrada();
						}
					}
				}
			}
			
			$params = Array(
				'entrada' => $saida,
			);
			
			$saida = projeto_main($params);
			
			if(!$_DESATIVAR_PADRAO['main_saida']){
				if($_SESSION[$_SYSTEM['ID']."alerta"]){
					alerta($_SESSION[$_SYSTEM['ID']."alerta"]);
					$_SESSION[$_SYSTEM['ID']."alerta"] = false;
				}
				
				if($_SESSION[$_SYSTEM['ID']."alerta-proximo"]){
					$_SESSION[$_SYSTEM['ID']."alerta"] = $_SESSION[$_SYSTEM['ID']."alerta-proximo"];
					$_SESSION[$_SYSTEM['ID']."alerta-proximo"] = false;
				}
				
				global $_LAYOUT_NUM,$_MOBILE,$_JS,$_PROJETO_JS,$_CAMINHO_RELATIVO_RAIZ,$_VERSAO_MODULO,$_PROJETO_VERSAO;
				
				if($_LAYOUT_NUM)$_HTML['LAYOUT']			=	$_SYSTEM['TEMA_PATH']."layout".$_LAYOUT_NUM.".html";
				
				if(!$_MOBILE){
					$_HTML['js'] .= 
					$_JS['jquery_ui_effects'].
					$_JS['imageCycle'].
					$_JS['jquery.center'].
					$_JS['prettyPhoto'].
					$_JS['alphaNumeric'].
					$_JS['jPlayer'].
					$_JS['recaptcha'].
					$_JS['maskedInput'].
					$_JS['jQueryPassStrengthMeter'].
					//$_JS['jquery.elevatezoom'].
					$_JS['colorbox'].
					$_PROJETO_JS.
					"	<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."includes/js/index.js?v=".$_VERSAO_MODULO."\"></script>\n".
					"	<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/projeto".($_LAYOUT_NUM ? $_LAYOUT_NUM : '').".js?v=".$_PROJETO_VERSAO."\"></script>\n".
					'	<link href="'.$_SYSTEM['TEMA_ROOT'].'layout'.($_LAYOUT_NUM ? $_LAYOUT_NUM : '').($_REQUEST['_layouts_teste']?'-temp':'').'.css?v='.($_REQUEST['_layouts_versao']?$_REQUEST['_layouts_versao']:$_PROJETO_VERSAO).'" rel="stylesheet" type="text/css" />'."\n";
					
					$_HTML['css'] .= "	<link href=\"".$_CAMINHO_RELATIVO_RAIZ."includes/css/index.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
				}
				
				$_HTML['body'] = $saida;
				
				echo pagina();
			}
		} else {
			echo ajax();
		}
	} else {
		echo xml();
	}
}

main();

?>