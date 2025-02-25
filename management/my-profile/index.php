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

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"my-profile";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_LOJA				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
$_MENU_LATERAL_GESTOR		=	true;
$_HTML['LAYOUT']			=	$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

$_HTML['titulo'] 						= 	$_HTML['titulo']."Meus Dados.";
$_HTML['variaveis']['titulo-modulo']	=	'Meus Dados';	

$_HTML['js'] .= 
$_JS['menu'].
$_JS['alphaNumeric'].
$_JS['maskedInput'].
$_JS['tinyMce'];

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']			=	'usuario';
$_LISTA['tabela']['campo']			=	'nome';
$_LISTA['tabela']['id']				=	'id_'.'usuario';
$_LISTA['tabela']['status']			=	'status';
$_LISTA['ferramenta']				=	'Seu Perfil';
$_LISTA['ferramenta_unidade']		=	'essa Entrada';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// B2make

function b2make_my_profile(){
	global $_SYSTEM;
	global $_PROJETO;
	
	if($_PROJETO['my-profile']){
		if($_PROJETO['my-profile']['layout']){
			$layout = $_PROJETO['my-profile']['layout'];
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir('html.html');
		$pagina = modelo_tag_val($modelo,'<!-- form < -->','<!-- form > -->');
		
		$layout = $pagina;
	}
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$layout = modelo_var_troca($layout,"#email#",$usuario['email']);
	$layout = modelo_var_troca($layout,"#nome#",$usuario['nome']);
	$layout = modelo_var_troca($layout,"#sobrenome#",$usuario['sobrenome']);
	$layout = modelo_var_troca($layout,"#cep#",$usuario['cep']);
	$layout = modelo_var_troca($layout,"#endereco#",$usuario['endereco']);
	$layout = modelo_var_troca($layout,"#numero#",$usuario['numero']);
	$layout = modelo_var_troca($layout,"#complemento#",$usuario['complemento']);
	$layout = modelo_var_troca($layout,"#bairro#",$usuario['bairro']);
	$layout = modelo_var_troca($layout,"#cidade#",$usuario['cidade']);
	$layout = modelo_var_troca($layout,"#uf#",$usuario['uf']);
	$layout = modelo_var_troca($layout,"#telefone#",$usuario['telefone']);
	$layout = modelo_var_troca($layout,"#celular#",$usuario['celular']);
	
	return $layout;
}

function b2make_my_profile_bd(){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_REQUEST['nome']){
		alerta('Não foi definido o seu nome. Defina seu nome e tente novamente'); return editar();
	}
	
	if($_REQUEST['senha'])
	if($_SESSION[$_SYSTEM['ID']."usuario_senha"] != $_REQUEST['senha']){
		alerta('Sua senha atual NÃO confere com a senha registrada no sistema. Senão lembra a sua senha clique em <a href="/'.$_SYSTEM['ROOT'].'esqueceu-sua-senha" class="alert-close">Esqueceu Sua Senha</a>.'); return editar();
	}
	
	if($_REQUEST['senha'])
	if(!$_REQUEST['senha2'] || !$_REQUEST['senha3']){
		alerta('É necessário definer o campo SENHA NOVA e REDIGITE SENHA NOVA se quiser mudar sua senha. Senão quiser mudar sua senha, deixe os 3 campos de senha em branco.'); return editar();
	}
	
	if($_REQUEST['senha'])
	if($_REQUEST['senha2'] != $_REQUEST['senha3']){
		alerta('O campo SENHA NOVA e REDIGITE SENHA NOVA são diferentes. É necessário que ambos sejam iguais. Senão quiser mudar sua senha, deixe os 3 campos de senha em branco.'); return editar();
	}
	
	if($_REQUEST['senha'])
	if(strlen($_REQUEST['senha2']) < 3 || strlen($_REQUEST['senha2']) > 20){
		alerta('A sua SENHA NOVA tem que ter no mínimo 3 caracterese e no máximo 20.'); return editar();
	}
	
	$campos = null;
	
	$campo_nome = "id_usuario"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "nome"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "sobrenome"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "cep"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "endereco"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "numero"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "complemento"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "bairro"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "cidade"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "uf"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "telefone"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "celular"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
	
	banco_insert_name
	(
		$campos,
		"usuario_old"
	);
	
	$campo_tabela = "usuario";
	$campo_tabela_extra = "WHERE id_usuario='".$usuario['id_usuario']."'";
	
	$campo_nome = "nome"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "sobrenome"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "cep"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "endereco"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "numero"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "complemento"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "bairro"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "cidade"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "uf"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "telefone"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	$campo_nome = "celular"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'"; $usuario[$campo_nome] = $_REQUEST[$campo_nome];
	
	if($_REQUEST['senha'])
	if($_REQUEST['senha2']){
		$campo_nome = "senha2"; $editar[$campo_tabela][] = "senha='" . crypt($_REQUEST[$campo_nome]) . "'";
		$_SESSION[$_SYSTEM['ID']."usuario_senha"] = $_REQUEST['senha2'];
		$change_ftp_pass = true;
	
		if($usuario['versao_voucher']){
			$versao_voucher = (int)$usuario['versao_voucher'] + 1;
		} else {
			$versao_voucher = 1;
		}
		
		$campo_nome = "versao_voucher"; $editar[$campo_tabela][] = $campo_nome."='" . $versao_voucher . "'"; $usuario[$campo_nome] = $versao_voucher;
		
		if($usuario['id_loja_usuarios']){
			$_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"] = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
			banco_update
			(
				"versao_voucher='".$versao_voucher."',".
				"senha='".crypt($_REQUEST['senha2'])."'",
				"loja_usuarios",
				"WHERE id_loja_usuarios='".$usuario['id_loja_usuarios']."'"
			);
			
			$_SESSION[$_SYSTEM['ID']."loja_usuarios"]['versao_voucher'] = $versao_voucher;
		}
		
		$usuario['versao_voucher'] = $versao_voucher;
	}
	
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
	
	$_SESSION[$_SYSTEM['ID']."usuario"] = $usuario;
	
	if($change_ftp_pass)b2make_my_profile_ftp_passwd();
	
	if($_FILES['avatar']['size'] != 0)		{guardar_arquivo($_FILES['avatar'],'imagem','avatar',$usuario['id_usuario']);}
	
	return editar();
}

function b2make_my_profile_ftp_passwd(){
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
			))
			,
			"usuario",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
		);
		
		$ftp_site_pass = $resultado[0]['ftp_site_pass'];
		$ftp_site_user = $resultado[0]['ftp_site_user'];
		
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
		if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path-2'].'b2make-xmlapi/cpanel-ftp-passwd.php');
		
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-pass'] = $ftp_site_pass;
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-pass'] = $ftp_site_pass;
	} else {
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'user_cpanel',
				'server',
				'ftp_site_user',
				'ftp_files_user',
				'ftp_site_pass',
				'ftp_files_pass',
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
		$ftp_site_pass = $resultado[0]['ftp_site_pass'];
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
		
		if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path-2'].'b2make-xmlapi/cpanel-ftp-passwd.php');
		
		$ftp_files_pass = hashPassword($senha,$ftp_files_pass);
		
		$user_arr = explode('@',$ftp_files_user);
		$_CPANEL['FTP_PASSWD'] = Array(
			'user' => $user_arr[0],
			'pass' => $ftp_files_pass,
		);
		
		if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path-2'].'b2make-xmlapi/cpanel-ftp-passwd.php');
		
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-pass'] = $ftp_site_pass;
		$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-pass'] = $ftp_files_pass;
	}
}

function signature_cancel(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_OPCAO;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;

	if($_PROJETO[$_OPCAO]){
		if($_PROJETO[$_OPCAO]['layout']){
			$layout = $_PROJETO[$_OPCAO]['layout'];
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir('html.html');
		$pagina = modelo_tag_val($modelo,'<!-- '.$_OPCAO.' < -->','<!-- '.$_OPCAO.' > -->');
	}
	
	$_SESSION[$_SYSTEM['ID']."signature_cancel"] = true;
	
	//if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$_INTERFACE_OPCAO = 'editar';
	$_INTERFACE['local'] = 'conteudo';
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_tipo'] = $tipo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($usuario['id_usuario_pai']){
		return editar();
	}

	return interface_layout(parametros_interface());
}

function signature_cancel_confirm(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_OPCAO;
	global $_PAYPAL_SANDBOX;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($usuario['id_usuario_pai']){
		return editar();
	}
	
	if($_SESSION[$_SYSTEM['ID']."signature_cancel"]){
		$_SESSION[$_SYSTEM['ID']."signature_cancel"] = false;
		$_SESSION[$_SYSTEM['ID']."signature_canceled"] = true;
		
		$id_usuario = $_SESSION[$_SYSTEM['ID']."usuario"]["id_usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'server',
				'user_host',
				'user_cpanel',
			))
			,
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS TRUE"
		);
		
		$user = $resultado[0]['user_cpanel'];
		$user_host = $resultado[0]['user_host'];
		$server = $resultado[0]['server'];
		
		host_excluir_conta(Array(
			'id_usuario' => $id_usuario,
			'user' => $user,
			'user_host' => $user_host,
			'server' => $server,
		));
		
		if(!$_B2MAKE_PAGINA_LOCAL){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'pagseguro_assinatura_code',
					'paypal_assinatura_code',
				))
				,
				"assinaturas",
				"WHERE id_usuario='".$id_usuario."'"
				." AND atual_pago IS TRUE"
			);
			
			if($resultado[0]['pagseguro_assinatura_code']){
				$code = $resultado[0]['pagseguro_assinatura_code'];
				
				b2make_pagseguro_assinatura_cancelar($code);
			} else if($resultado[0]['paypal_assinatura_code']){
				$code = $resultado[0]['paypal_assinatura_code'];
				
				b2make_paypal_assinatura_cancelar($code,'Usuário excluiu sua conta de usuário do B2Make');
			}
		}
		
		return signature_canceled();
	} else {
		return signature_cancel();
	}
}

function signature_canceled(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_OPCAO;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	if($_SESSION[$_SYSTEM['ID']."signature_canceled"]){
		$_SESSION[$_SYSTEM['ID']."signature_canceled"] = false;
		
		if($_PROJETO[$_OPCAO]){
			if($_PROJETO[$_OPCAO]['layout']){
				$layout = $_PROJETO[$_OPCAO]['layout'];
			}
		}
		
		if(!$layout){
			$modelo = modelo_abrir('html.html');
			$pagina = modelo_tag_val($modelo,'<!-- signature-canceled < -->','<!-- signature-canceled > -->');
		}
		
		$delay = $_SESSION[$_SYSTEM['ID']."delay"];
		
		session_unset();
		
		$_SESSION[$_SYSTEM['ID']."delay"] = $delay;
		
		$_INTERFACE_OPCAO = 'editar';
		$_INTERFACE['local'] = 'conteudo';
		$_INTERFACE['informacao_titulo'] = $in_titulo;
		$_INTERFACE['informacao_tipo'] = $tipo;
		$_INTERFACE['informacao_id'] = $id;
		$_INTERFACE['inclusao'] = $pagina;

		return interface_layout(parametros_interface());
	} else {
		return signature_cancel();
	}
}

function host_excluir_conta($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($id_usuario){
		banco_update
		(
			"status='D'",
			"host",
			"WHERE id_usuario='".$id_usuario."'"
		);
		banco_update
		(
			"status='D'",
			"usuario",
			"WHERE id_usuario='".$id_usuario."'"
		);
	}
	
	if(!$_B2MAKE_PAGINA_LOCAL)
	if($user && $server){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
			'host' => $user_host,
		);
		$_CPANEL['FTP_LOCAL'] = $server;
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path-2'].'b2make-xmlapi/cpanel-removeacct.php');
	}
}

function b2make_pagseguro_assinatura_cancelar($code){
	global $_PROJETO;
	
	$email = $_PROJETO['PAGSEGURO_EMAIL'];
	$token = $_PROJETO['PAGSEGURO_TOKEN'];
	
	$url = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/cancel/' . $code . '?email=' . $email . '&token=' . $token;
	
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$preApproval = curl_exec($curl);
	curl_close($curl);
	
	if($preApproval == 'Unauthorized'){
		gravar_log('PagSeguro Cancelamento: Unauthorized');
	} else {
		libxml_use_internal_errors(true);
		$preApproval = simplexml_load_string($preApproval);
		if(!$preApproval){
			gravar_log('PagSeguro Cancelamento: XML inválido');
		} else {
			if(count($preApproval->error) > 0){
				gravar_log('PagSeguro Cancelamento: Dados inválidos');
			}
		}
	}
}

function b2make_paypal_assinatura_cancelar($code,$note){
	global $_PROJETO;
	global $_PAYPAL_SANDBOX;
	
	if ($_PAYPAL_SANDBOX) {
		//credenciais da API para o Sandbox
		$user = 'otavioserra-facilitator_api1.gmail.com';
		$pswd = '1400005808';
		$signature = 'A86WhSbRNk-zAyk1sEPIVYSkWyQKAsA5L7BkQ.hesbYmUaAfnhg45vvO';
	  
		//URL da PayPal para redirecionamento, não deve ser modificada
		$paypalURLNVP = 'https://api-3t.sandbox.paypal.com/nvp';
	} else {
		//credenciais da API para produção
		$user = $_PROJETO['PAYPAL_USER'];
		$pswd = $_PROJETO['PAYPAL_PASS'];
		$signature = $_PROJETO['PAYPAL_SIGNATURE'];
	  
		//URL da PayPal para redirecionamento, não deve ser modificada
		$paypalURLNVP = 'https://api-3t.paypal.com/nvp';
	}
	
	$count = 0;
	$maxTries = 10;
	while(true) {
		try {
			$curl = curl_init();
			
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_URL, $paypalURLNVP);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
				'USER' => $user,
				'PWD' => $pswd,
				'SIGNATURE' => $signature,
			  
				'METHOD' => 'ManageRecurringPaymentsProfileStatus',
				'VERSION' => '108',
				'PROFILEID'=> $code,
			  
				'ACTION' => 'Cancel',
				'NOTE' => $note,
			)));
			
			$response = curl_exec($curl);
			
			curl_close($curl);
			
			$nvp = array();
			
			if(preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
				foreach ($matches['name'] as $offset => $name) {
					$nvp[$name] = urldecode($matches['value'][$offset]);
				}
			}
			
			if(isset($nvp['ACK']) && $nvp['ACK'] != 'Success') {
				gravar_log('PayPal Cancel: Erro ACK'.' RESPONSE: '.$response);
			}
			
			break;
		} catch (Exception $e) {
			$count++;
			if($count >= $maxTries){
				gravar_log('PayPal Cancel: maxTries Reached'.' RESPONSE: '.$e);
				break;
			}
		}
		usleep(400);
	}
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_ESERVICE;
	global $_HTML;
	
	$caminho_fisico 			=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."avatares".$_SYSTEM['SEPARADOR'];
	$caminho_internet 			= 	"files/avatares/";
	
	if(!is_dir($caminho_fisico)){
		mkdir($caminho_fisico);
		chmod($caminho_fisico , 0777);
	}
	
	if(
		$uploaded['size'] != 0
	){
		switch($tipo){
			case 'imagem':
				if
				(
					$uploaded['type'] == mime_types("jpe") ||
					$uploaded['type'] == mime_types("jpeg") ||
					$uploaded['type'] == mime_types("jpg") ||
					$uploaded['type'] == mime_types("pjpeg") ||
					$uploaded['type'] == mime_types("png") ||
					$uploaded['type'] == mime_types("x-png") ||
					$uploaded['type'] == mime_types("gif")
				){
					$cadastrar = true;
				}
			break;
		}
	}
	
	if($cadastrar){
		if
		(
			$uploaded['type'] == mime_types("jpe") ||
			$uploaded['type'] == mime_types("jpeg") ||
			$uploaded['type'] == mime_types("pjpeg") ||
			$uploaded['type'] == mime_types("jpg")
		){
			$extensao = "jpg";
		} else if
		(
			$uploaded['type'] == mime_types("png") ||
			$uploaded['type'] == mime_types("x-png") 
		){
			$extensao = "png";
		} else if
		(
			$uploaded['type'] == mime_types("gif")
		){
			$extensao = "gif";
		}
		
		$nome_arquivo = $campo . $id_tabela . "." . $extensao;
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			$original = $caminho_fisico . $nome_arquivo;
			
			$new_width = $_PROJETO['new_width_256'];
			$new_height = $_PROJETO['new_height_256'];
			
			resize_image($original, $original, $new_width, $new_height,false,false,true);
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'versao',
				))
				,
				"usuario",
				"WHERE id_usuario='".$id_tabela."'"
			);
			
			banco_update
			(
				($resultado[0]['versao'] ? 'versao=versao+1,' : 'versao=1,').
				$campo."='".$caminho_internet.$nome_arquivo."'",
				"usuario",
				"WHERE id_usuario='".$id_tabela."'"
			);
			
			$_SESSION[$_SYSTEM['ID'].'usuario']['avatar'] = $caminho_internet.$nome_arquivo;
			$_SESSION[$_SYSTEM['ID'].'usuario']['versao'] = ($resultado[0]['versao'] ? $resultado[0]['versao']+1 : '1');
			$_HTML['gestor_avatar'] = ' style="background-image:url(/' . $_SYSTEM['ROOT'] . $_SESSION[$_SYSTEM['ID'].'usuario']['avatar'] . '?v=' . $_SESSION[$_SYSTEM['ID'].'usuario']['versao'].');"';
		}
	}
}

// Funções do Sistema

function alerta($nAlerta){ // v 2
	global $_ALERT_DADOS;
	global $_ALERTA;
	
	switch ($nAlerta){
		//case 1:		$mensSaida	=	"";break;
		default:	$mensSaida	=	$nAlerta;
	}

	$_ALERTA = $mensSaida;
}

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_LISTA_2;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? '' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => '../../dashboard/',// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img_coluna' => 1, // Coluna background image
		'img_linha' => 1, // Linha background image
		'name' => 'Início', // Nome do menu
	);
	
	// ------------------------------ Campos -------------------------
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'width' => $width, // OPCIONAL - Tamanho horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Nome', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	// ------------------------------ Parâmetros -------------------------
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => ' ' . $_LISTA['ferramenta'] , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => operacao('buscar'), // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 1, // Posicao do id
		'tabela_status_posicao' => 0, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D'", // Tabela extra
		'tabela_order' => $tabela_order, // Ordenação da tabela
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => $menu_principal,
		'menu_opcoes' => $menu_opcoes,
		'header_campos' => $header_campos,
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => 'Ação', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 22), // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'campos' => $campos,
		'outra_tabela' => $outra_tabela,
		'informacao_abaixo' => $informacao_abaixo,
		'informacao_acima' => $informacao_acima,
		'layout_pagina' => true,
		'layout_tag1' => '<!-- layout_pagina_2 < -->',
		'layout_tag2' => '<!-- layout_pagina_2 > -->',
		
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface());
}

function editar($param = false){
	global $_SYSTEM;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$pagina = b2make_my_profile();
	
	// ======================================================================================
	
	$in_titulo = $param ? "Visualizar" : "Modificar";
	$botao = "Gravar";
	$opcao = "my-profile-bd";
	
	if($_REQUEST['site']){
		$more_options = 'widget_id='.$_REQUEST['widget_id'];
	}
	
	$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
	$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
	$pagina = paginaTrocaVarValor($pagina,"#id",$id);
	$pagina = paginaTrocaVarValor($pagina,"#more_options",$more_options);
	
	//if(!operacao('editar'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	if($usuario['id_usuario_pai']){
		$cel_nome = 'excluir-cont'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$_INTERFACE_OPCAO = 'editar';
	$_INTERFACE['local'] = 'conteudo';
	$_INTERFACE['informacao_titulo'] = $in_titulo;
	$_INTERFACE['informacao_tipo'] = $tipo;
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function my_profile_teste(){
	global $_SYSTEM;
	
}

function remover_item(){
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	global $_LISTA;
	global $_ALERTA;
	
	$item = $_REQUEST['item'];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id = $usuario['id_loja'];
	
	$caminho_fisico 			=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."stores".$_SYSTEM['SEPARADOR'];
	$caminho_internet 			= 	"files/stores/";
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($item && $id){
		if(
			$item == 'logomarca'
		){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$item,
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
			);
			
			if($resultado){
				banco_update
				(
					$item."=NULL",
					$_LISTA['tabela']['nome'],
					"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
				);
			
				$resultado[0][$item] = str_replace($caminho_internet,$caminho_fisico,$resultado[0][$item]);
				if(is_file($resultado[0][$item]))unlink($resultado[0][$item]);
				$_ALERTA = "Ítem removido com sucesso!";
			} else {
				$_ALERTA = "Não é possível remover, essa imagem não faz parte do seu usuário!";
			}
		}
		
		return editar();
	}
}

// ======================================================================================

function xml(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_BANCO_PREFIXO;
	global $_OPCAO;
	global $_HTML;
	
	if($_REQUEST['mp3_player']){
		$id = $_SESSION[$_SYSTEM['ID']."mp3_id"];
		$categoria_id = 3;
		
		banco_conectar();
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'titulo',
				'sub_titulo',
				'musica',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		banco_fechar_conexao();
		
		$dom = new DOMDocument("1.0", "UTF-8");
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		
		$mp3player = $dom->appendChild(new DOMElement('mp3player'));
		
		$mp3 = $mp3player->appendChild(new DOMElement('mp3'));
		$attr = $mp3->setAttributeNode(new DOMAttr('id', 1));
		
		$title = $mp3->appendChild(new DOMElement('title',$conteudo[0]['titulo']));
		$artist = $mp3->appendChild(new DOMElement('artist',$conteudo[0]['sub_titulo']));
		$url = $mp3->appendChild(new DOMElement('url',$_HTML['separador'].$conteudo[0]['musica']));
		
		header("Content-Type: text/xml");
		echo $dom->saveXML();
	}
}

function ajax(){
	global $_SYSTEM;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = $_REQUEST["query"];
		if(!$query) return;
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D'"
			." AND id_loja='".$usuario['id_loja']."'"
		);
		
		banco_fechar_conexao();

		for($i=0;$i<count($resultado);$i++){
			$saida[] = Array(
				'value' => $resultado[$i][1],
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	return $saida;
}

function start(){
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_LISTA;
	global $_HTML;
	global $_OPCAO;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_OPCAO = $_PAGINA_OPCAO = $opcoes;
	
	if($_REQUEST[xml]){
		xml();
	} else if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'signature-cancel':			$saida = signature_cancel(); break;
			case 'signature-cancel-confirm':	$saida = signature_cancel_confirm(); break;
			case 'signature-canceled':			$saida = signature_canceled(); break;
			case 'my-profile-bd':				$saida = b2make_my_profile_bd();break;
			case 'teste':						$saida = my_profile_teste();break;
			case 'remover_item':				$saida = remover_item(); break;
			default: 							$saida = editar();
		}
		
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

start();

?>