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

$_VERSAO_MODULO				=	'1.3.0';
$_LOCAL_ID					=	"config";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_LOJA				=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_PUBLISHER			=	true;
$_INCLUDE_SITE				=	true;
$_TINYMCE_NOVO				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
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

$_HTML['titulo'] 						= 	$_HTML['titulo']."Configurações.";
$_HTML['variaveis']['titulo-modulo']	=	'Configurações';	

$_HTML['js'] = 
$_JS['menu'].
$_JS['tinyMce'].
$_JS['maskedInput'].
$_JS['CodeMirror5'].
'	<link rel="stylesheet" type="text/css" href="jpicker/css/jPicker-1.1.6.css?v='.$_VERSAO.'" />'."\n".
'	<link rel="stylesheet" type="text/css" href="jpicker/jPicker.css?v='.$_VERSAO.'" />'."\n".
'	<script src="jpicker/jpicker-1.1.6.min.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n".
"	<script src=\"https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js\"></script>\n".
"	<script type=\"text/javascript\" src=\"https://cdnjs.cloudflare.com/ajax/libs/jquery.easytabs/3.2.0/jquery.easytabs.min.js\"></script>\n";

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'loja';
$_LISTA['tabela']['campo']		=	'nome';
$_LISTA['tabela']['id']			=	'id_loja';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Configurações';
$_LISTA['ferramenta_unidade']	=	'a configuração';

$_HTML['separador']			=	$_CAMINHO_RELATIVO_RAIZ;

// Funções do Sistema

function redirecionar($local = false,$sem_root = false){
	global $_SYSTEM;
	global $_AJAX_PAGE;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PROJETO;
	global $_REDIRECT_PAGE;
	global $_ALERTA;
	
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

function loja_pagina_mestre_reinstall(){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($usuario['id_usuario_pai']) $usuario['id_usuario'] = $usuario['id_usuario_pai'];
	
	$html = '';
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_host',
			'id_site',
		))
		,
		"site",
		"WHERE id_site_pai IS NULL"
		." AND id_usuario='".$usuario['id_usuario']."'"
	);
	
	$id_host = $resultado[0]['id_host'];
	$id_site_pai = $resultado[0]['id_site'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND id='01-modelos-de-paginas'"
	);
	
	if(!$resultado){
		$campos = null;
		
		$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_pai"; $campo_valor = $id_site_pai; 		if($id_site_pai)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = '01 - Modelos de Páginas'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = '01-modelos-de-paginas'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"site"
		);
		
		$id_site_modelo = banco_last_id();
	} else {
		$id_site_modelo = $resultado[0]['id_site'];
	}
	// Criar Páginas de Serviços para o usuário poder modificar o layout da página de serviços via Site Builder
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_site',
		))
		,
		"site",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND id='pagina-de-servicos'"
	);
	
	if(!$resultado){
		$html = modelo_abrir($_SYSTEM['PATH'].'store'.$_SYSTEM['SEPARADOR'].'pagina-servicos.html');
		
		$campos = null;
		
		$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_pai"; $campo_valor = $id_site_modelo; 		if($id_site_modelo)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = 'Página de Serviços'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = 'pagina-de-servicos'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		$id_site_modelo = banco_last_id();
		
		banco_insert_name
		(
			$campos,
			"site"
		);
	}
}

function identificador_unico($id,$num,$id_loja){
	$conteudo = banco_select
	(
		"id_loja"
		,
		"loja",
		"WHERE "."id"."='".($num ? $id.'-'.$num : $id)."'"
		.($id_loja?" AND id_loja!='".$id_loja."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return identificador_unico($id,$num + 1,$id_loja);
	} else {
		return ($num ? $id.'-'.$num : $id);
	}
}

function criar_identificador($id,$id_loja = false){
	global $_ESERVICE;
	
	$tam_max_id = 90;
	$id = retirar_acentos(trim($id));
	
	if($id == $_ESERVICE['minha-loja-id']){
		$id = $_ESERVICE['minha-loja-id'].'-'.$id_loja;
	}
	
	if($_ESERVICE['store-ids-proibidos'])
	foreach($_ESERVICE['store-ids-proibidos'] as $ids_proibidos){
		if($ids_proibidos == $id){
			$id = $id.'-1';
		}
	}
	
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
	if(count($id_aux) > 1 && is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		return identificador_unico($id,$num,$id_loja);
	} else {
		return identificador_unico($id,0,$id_loja);
	}
}

function config_publish_all_library(){
	site_library_update(Array(
		'widget' => 'formularios',
		'nao_desconectar_ftp' => true,
	));
	
	site_library_update(Array(
		'widget' => 'posts-filter',
	));
}

function utf8_ansi_without_escape($valor='') {
    $utf8_ansi2 = array(
    "u00b0" =>"°",
    "u00b2" =>"²",
    "u00b7" =>"·",
    "u00ba" =>"º",
    "u00bb" =>"»",
    "u00c0" =>"À",
    "u00c1" =>"Á",
    "u00c2" =>"Â",
    "u00c3" =>"Ã",
    "u00c4" =>"Ä",
    "u00c5" =>"Å",
    "u00c6" =>"Æ",
    "u00c7" =>"Ç",
    "u00c8" =>"È",
    "u00c9" =>"É",
    "u00ca" =>"Ê",
    "u00cb" =>"Ë",
    "u00cc" =>"Ì",
    "u00cd" =>"Í",
    "u00ce" =>"Î",
    "u00cf" =>"Ï",
    "u00d1" =>"Ñ",
    "u00d2" =>"Ò",
    "u00d3" =>"Ó",
    "u00d4" =>"Ô",
    "u00d5" =>"Õ",
    "u00d6" =>"Ö",
    "u00d8" =>"Ø",
    "u00d9" =>"Ù",
    "u00da" =>"Ú",
    "u00db" =>"Û",
    "u00dc" =>"Ü",
    "u00dd" =>"Ý",
    "u00df" =>"ß",
    "u00e0" =>"à",
    "ï¿½" =>"á",
    "u00e1" =>"á",
    "u00e2" =>"â",
    "u00e3" =>"ã",
    "u00e4" =>"ä",
    "u00e5" =>"å",
    "u00e6" =>"æ",
    "u00e7" =>"ç",
    "u00e8" =>"è",
    "u00e9" =>"é",
    "u00ea" =>"ê",
    "u00eb" =>"ë",
    "u00ec" =>"ì",
    "u00ed" =>"í",
    "u00ee" =>"î",
    "u00ef" =>"ï",
    "u00f0" =>"ð",
    "u00f1" =>"ñ",
    "u00f2" =>"ò",
    "u00f3" =>"ó",
    "u00f4" =>"ô",
    "u00f5" =>"õ",
    "u00f6" =>"ö",
    "u00f8" =>"ø",
    "u00f9" =>"ù",
    "u00fa" =>"ú",
    "u00fb" =>"û",
    "u00fc" =>"ü",
    "u00fd" =>"ý",
    "u00ff" =>"ÿ");

    return strtr($valor, $utf8_ansi2);
}

// ========================== PayPal Plus ===========================

function eservice_paypal_plus_token_generate($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$ch = curl_init();
	$clientId = $paypal_app_code;
	$secret = $paypal_app_secret;

	curl_setopt($ch, CURLOPT_URL, "https://api.".($paypal_app_live ? "" : "sandbox.")."paypal.com/v1/oauth2/token");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept: application/json',
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

	$result = curl_exec($ch);

	if(empty($result)){
		$saida = Array(
			'erro' => 1,
			'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar o token do PayPal Plus devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.",
		);
	} else {
		$json = json_decode($result);
		
		if($json->error){
			$saida = Array(
				'erro' => 2,
				'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar o token do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.",
			);
		} else {
			$access_token = $json->access_token;
			$expires_in = $json->expires_in;
			
			$editar[$campo_tabela][] = "paypal_app_".($paypal_app_live ? "" : "sandbox_")."token='" . $access_token . "'";
			$editar[$campo_tabela][] = "paypal_app_".($paypal_app_live ? "" : "sandbox_")."token_time='" . time() . "'";
			$editar[$campo_tabela][] = "paypal_app_".($paypal_app_live ? "" : "sandbox_")."expires_in='" . $expires_in . "'";
			
			$campo_tabela = "loja";
			$campo_tabela_extra = "WHERE id_loja='".$id_loja."'";
			
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
			
			$saida = Array(
				'token' => $access_token,
				'status' => 'Ok',
			);
		}
	}
	
	curl_close($ch);
	
	return $saida;
}

function b2make_paypal_plus_token_generate($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_PROJETO;
	
	$ch = curl_init();
	
	if($paypal_app_live){
		$clientId = $_PROJETO['PAYPAL_B2MAKE_LIVE_ID'];
		$secret = $_PROJETO['PAYPAL_B2MAKE_LIVE_SECRET'];
	} else {
		$clientId = $_PROJETO['PAYPAL_B2MAKE_SANDBOX_ID'];
		$secret = $_PROJETO['PAYPAL_B2MAKE_SANDBOX_SECRET'];
	}
	
	$variavel_global = banco_select_name
	(
		banco_campos_virgulas(Array(
			'variavel',
			'valor',
		))
		,
		"variavel_global",
		"WHERE grupo='paypal'"
	);
	
	if($variavel_global)
	foreach($variavel_global as $vg){
		$paypal_b2make[$vg['variavel']] = $vg['valor'];
	}
	
	$gerar_token = false;
	
	if($paypal_app_live){
		if($paypal_b2make['b2make-live-token']){
			if((int)$paypal_b2make['b2make-live-token-time']+(int)$paypal_b2make['b2make-live-expires-in'] < time()){
				$gerar_token = true;
			}
		} else {
			$gerar_token = true;
		}
		
		if(!$gerar_token){
			$saida = Array(
				'token' => $paypal_b2make['b2make-live-token'],
				'status' => 'Ok',
			);
			
			return $saida;
		}
	} else {
		if($paypal_b2make['b2make-sandbox-token']){
			if((int)$paypal_b2make['b2make-sandbox-token-time']+(int)$paypal_b2make['b2make-sandbox-expires-in'] < time()){
				$gerar_token = true;
			}
		} else {
			$gerar_token = true;
		}
		
		if(!$gerar_token){
			$saida = Array(
				'token' => $paypal_b2make['b2make-sandbox-token'],
				'status' => 'Ok',
			);
			
			return $saida;
		}
	}
	
	
	curl_setopt($ch, CURLOPT_URL, "https://api.".($paypal_app_live ? "" : "sandbox.")."paypal.com/v1/oauth2/token");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept: application/json',
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

	$result = curl_exec($ch);

	if(empty($result)){
		$saida = Array(
			'erro' => 1,
			'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar o token do PayPal Plus devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.",
		);
	} else {
		$json = json_decode($result);
		
		if($json->error){
			$saida = Array(
				'erro' => 2,
				'erro_msg' => "N&atilde;o foi poss&iacute;vel gerar o token do PayPal Plus devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.",
			);
		} else {
			$access_token = $json->access_token;
			$expires_in = $json->expires_in;
			
			$editar['b2make-'.($paypal_app_live ? 'live' : 'sandbox').'-token'] = $access_token;
			$editar['b2make-'.($paypal_app_live ? 'live' : 'sandbox').'-token-time'] = time();
			$editar['b2make-'.($paypal_app_live ? 'live' : 'sandbox').'-expires-in'] = $expires_in;
			
			if($editar)
			foreach($editar as $variavel => $valor){
				banco_update
				(
					"valor='".$valor."'",
					"variavel_global",
					"WHERE grupo='paypal'"
					." AND variavel='".$variavel."'"
				);
			}
			
			$saida = Array(
				'token' => $access_token,
				'status' => 'Ok',
			);
		}
	}
	
	curl_close($ch);
	
	return $saida;
}

// ========================== PagSeguro ===========================

function pagseguro_autorizar(){
	global $_PROJETO;
	global $_LOCAL_ID;
	global $_ALERTA;
	global $_SYSTEM;
	global $_B2MAKE_URL;
	global $_PAGSEGURO_SANDBOX;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$reference = 'U'.($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
	
	$sandbox = '';
	
	if($_PAGSEGURO_SANDBOX){
		$appId = $_PROJETO['PAGSEGURO_SANDBOX_APP_ID'];
		$appKey = $_PROJETO['PAGSEGURO_SANDBOX_APP_KEY'];
		$sandbox = 'sandbox.';
	} else {
		$appId = $_PROJETO['PAGSEGURO_APP_ID'];
		$appKey = $_PROJETO['PAGSEGURO_APP_KEY'];
	}
	
	$urlRequest = 'https://ws.'.$sandbox.'pagseguro.uol.com.br/v2/authorizations/request?appId='.$appId.'&appKey='.$appKey;
	
	$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<authorizationRequest>
	<reference>'.$reference.'</reference>
	<permissions>
		<code>CREATE_CHECKOUTS</code>
		<code>RECEIVE_TRANSACTION_NOTIFICATIONS</code>
		<code>SEARCH_TRANSACTIONS</code>
		<code>MANAGE_PAYMENT_PRE_APPROVALS</code>
		<code>DIRECT_PAYMENT</code>
	</permissions>
	<redirectURL>'.$_B2MAKE_URL.'config/?opcao=pagseguro-autorizar-return</redirectURL>
	<notificationURL>'.$_B2MAKE_URL.'e-services/pagseguro-app-notifications/</notificationURL>
</authorizationRequest>';
	
	//Executando a operação
    $curl = curl_init();
  
    curl_setopt($curl, CURLOPT_URL, $urlRequest);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($curl, CURLOPT_HTTPHEADER, Array('Content-Type: application/xml; charset=UTF-8'));
  
    $response = curl_exec($curl);
  
    curl_close($curl);
	
	libxml_use_internal_errors(true);
	$obj_xml = simplexml_load_string($response);
	
	if(!$obj_xml){
		log_banco(Array(
			'id_referencia' => ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),
			'grupo' => 'pagseguro_autorizar',
			'valor' => 'XML inválido',
		));
		
		$_ALERTA = 'Hove um erro com a sua autorização no PagSeguro, tente novamente mais tarde ou entre em contato com o suporte e informe: '.$_LOCAL_ID.': XML inválido';
		$retornar = true;
	}

	if(count($obj_xml->error) > 0 && !$retornar){
		foreach($obj_xml->error as $error){
			$count++;
			$erros .= ($erros?' | ':'').$count . ': [' . $error->code . '] '.$error->message;
			$codigos .= '[' . $error->code . ']';
		}
		
		log_banco(Array(
			'id_referencia' => ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']),
			'grupo' => 'pagseguro_autorizar',
			'valor' => $erros,
		));
		
		$_ALERTA = 'Hove um erro com a sua autorização no PagSeguro, tente novamente mais tarde ou entre em contato com o suporte e informe: '.$_LOCAL_ID.': '.$codigos;
		$retornar = true;
	}
	
	if(!$retornar){
		header('Location: https://'.$sandbox.'pagseguro.uol.com.br/userapplication/v2/authorization/request.jhtml?code='.$obj_xml->code);
		exit;
	}
	
	return (operacao('editar') ? editar() : editar('ver'));
}

function pagseguro_autorizar_return(){
	global $_PROJETO;
	global $_LOCAL_ID;
	global $_ALERTA;
	global $_SYSTEM;
	global $_B2MAKE_URL;
	global $_ESERVICES;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$_ESERVICES['apenas_incluir'] = true;
	require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'eservices/index.php');
	
	$notificationCode = $_REQUEST['notificationCode'];
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'pagseguro_app_code',
		))
		,
		"loja",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
	);
	
	if(!$loja[0]['pagseguro_app_code'])eservice_pagseguro_app_autorization(Array('code' => $notificationCode));
	
	return (operacao('editar') ? editar() : editar('ver'));
}

// =====================================================

function site_ftp_manual_password(){
	global $_SYSTEM;
	
	$saida .= '<div style="margin:120px 0px 0px 30px;font-size:20px;">';
	$saida .= 'FTP Manual Password<br><br>';
	$saida .= 'Site<br><br>';
	$saida .= 'HOST: '.$_SYSTEM['SITE']['ftp-site-host'].'<br>';
	$saida .= 'USER: '.$_SYSTEM['SITE']['ftp-site-user'].'<br>';
	$saida .= 'PASS: '.$_SYSTEM['SITE']['ftp-site-pass'];
	$saida .= '<br><br>Files<br><br>';
	$saida .= 'HOST: '.$_SYSTEM['SITE']['ftp-files-host'].'<br>';
	$saida .= 'USER: '.$_SYSTEM['SITE']['ftp-files-user'].'<br>';
	$saida .= 'PASS: '.$_SYSTEM['SITE']['ftp-files-pass'];
	$saida .= '</div>';
	
	return $saida;
}

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_CAMINHO_MODULO_RAIZ;
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = 'status';
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	$tabela_campos[] = 'email';
	$tabela_campos[] = 'data';
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	//if($_INTERFACE_OPCAO == 'editar'){
		$informacao_id = $_INTERFACE['informacao_id'];
		
		/* $menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => '#', // link da opção
			'title' => 'Excluir o(a) ' . $_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'db_remove.png', // caminho da imagem
			'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		);
		$menu_principal[] = Array( // Opção: Bloquear
			'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
			'title' => 'Ativar/Desativar o(a) '.$_LISTA['ferramenta'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo_grande_2.png', // caminho da imagem
			'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado_grande_2.png', // caminho da imagem
			'bloquear' => true, // Se eh botão de bloqueio
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=grupos&id=#id', // link da opção
			'title' => 'Grupo d'.$_LISTA['ferramenta_unidade'], // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'grupo_big.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		);
		$menu_principal[] = Array( // array com todos os campos das opções do menu
			'url' => $_URL . '?opcao=newsletter_buscar&id=#id', // link da opção
			'title' => 'Enviar Newsletter', // título da opção
			'img' => $_HTML['separador'].$_HTML['ICONS'] . 'email_big.png', // caminho da imagem
			'width' => '40', // OPCIONAL - tamanho x da imagem
			'height' => '40', // OPCIONAL - y da imagem
		); */
		
	//}
	
	$menu_opcoes[] = Array( // Opção: Conteúdo
		'url' => $_URL . '?opcao=newsletter_buscar&id=#id', // link da opção
		'title' => 'Enviar Newsletter', // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'email.png', // caminho da imagem
	);
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => $_URL . '?opcao=grupos&id=#id', // link da opção
		'title' => 'Grupo d'.$_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'grupo.png', // caminho da imagem
	);
	$menu_opcoes[] = Array( // Opção: Bloquear
		'url' => $_URL . '?opcao=bloqueio&tipo=#tipo&id=#id', // link da opção
		'title' => 'Ativar/Desativar '.$_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'ativo.png', // caminho da imagem
		'img_src2' => $_HTML['separador'].$_HTML['ICONS'] . 'bloqueado.png', // caminho da imagem
		'bloquear' => true, // Se eh botão de bloqueio
	);
	$menu_opcoes[] = Array( // Opção: Editar
		'url' => $_URL . '?opcao=editar&id=#id', // link da opção
		'title' => 'Editar ' . $_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'editar.png', // caminho da imagem
	);
	$menu_opcoes[] = Array( // Opção: Excluir
		'url' => '#', // link da opção
		'title' => 'Excluir ' . $_LISTA['ferramenta_unidade'], // título da opção
		'img_src' => $_HTML['separador'].$_HTML['ICONS'] . 'excluir.png', // caminho da imagem
		'link_extra' => " onclick=\"excluir('" . $_URL . "','#id','excluir')\"", // OPCIONAL - parâmetros extras no link
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Nome', // Valor do campo
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'E-mail', // Valor do campo
	);
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Data', // Valor do campo
		'align' => 'center',
		'width' => '120',
	);
	
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'data_hora' => true, // OPCIONAL - mostrar dados formatados para data
		'align' => 'center',
	);
	
	$sessao = $_REQUEST['sessao'];
	
	switch($sessao){
		case 'site': $informacao_titulo = 'Site'; break;
		case 'store': $informacao_titulo = 'Loja'; break;
		case 'paypal': $informacao_titulo = 'PayPal'; break;
		case 'voucher': $informacao_titulo = 'Voucher'; break;
	}
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => false, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_nao_connect' => true, // Se deve ou não conectar na tabela de referência
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
		'layout_pagina' => true,
		'layout_tag1' => '<!-- layout_pagina_2 < -->',
		'layout_tag2' => '<!-- layout_pagina_2 > -->',
		
	);
	
	return $parametros;
}

function editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	global $_B2MAKE_URL;
	global $_PROJETO;
	global $_B2MAKE_FTP_SITE_HOST;
	
	$sessao = $_REQUEST['sessao'];
	
	switch($sessao){
		case 'site': $grupo_sessao = 'site'; break;
		case 'store': $grupo_sessao = 'loja'; break;
		case 'paypal': $grupo_sessao = 'paypal-plus'; break;
		case 'voucher': $grupo_sessao = 'voucher'; break;
	}
	
	$modelo = modelo_abrir('html.html');
	$pagina = modelo_tag_val($modelo,'<!-- form < -->','<!-- form > -->');
	$opcoes = modelo_tag_val($modelo,'<!-- opcoes < -->','<!-- opcoes > -->');
	
	$cel_nome = 'categoria'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$cel_nome = 'image'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'static'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'text'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'string'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'int'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'float'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'bool'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'status'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'tinymce'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'codemirror-js'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'codemirror-js-mini'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'codemirror-css'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'codemirror-html'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'select'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$cel_nome = 'money'; $cel[$cel_nome] = modelo_tag_val($opcoes,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	
	banco_conectar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id',
			'email',
			'descricao',
			'email_assunto',
			'email_assinatura',
			'cpf',
			'cnpj',
			'pagseguro_email',
			'pagseguro_token',
			'paypal_email',
			'paypal_user',
			'paypal_pass',
			'paypal_signature',
			'logomarca',
			'endereco',
			'numero',
			'complemento',
			'bairro',
			'cidade',
			'uf',
			'pais',
			'telefone',
			'versao',
			'pagseguro_app_code',
			'voucher_sem_para_presente',
			'identificacao_voucher',
			'voucher_sem_escolha_tema',
			'pagseguro_parcelas_sem_juros',
			'url_continuar_comprando',
			'paypal_app_installed',
			'paypal_app_active',
			'paypal_plus_inactive',
			'paypal_app_live',
			'paypal_reference_installed',
			'paypal_plus_inactive',
			'widget_loja',
			'esquema_cores',
			'fontes',
			'observacao_servicos',
			'descricao_servicos',
			'expiracao_pagamento',
			'parcelamento',
			'parcelamento_modelo_informativo',
			'parcelamento_maximo_parcelas',
			'parcelamento_valor_minimo',
		))
		,
		"loja",
		"WHERE id_loja='".$usuario['id_loja']."'"
		." AND status='A'"
	);
	
	$host = banco_select_name
	(
		banco_campos_virgulas(Array(
			'google_analytic',
			'google_site_verification',
			'meta_global',
			'javascript_global',
			'css_global',
			'body_global',
			'global_version',
			'url',
			'url_mobile',
			'mobile',
			'dominio_proprio',
			'https',
			'services_list',
			'user_cpanel',
			'mobile_redirect_server',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	$loja[0] = array_merge($loja[0],$host[0]);
	
	$campos_extra = Array(
		'paypal_app_active' => $loja[0]['paypal_app_active'],
		'paypal_plus_inactive' => $loja[0]['paypal_plus_inactive'],
		'paypal_app_live' => $loja[0]['paypal_app_live'],
		'global_version' => $host[0]['global_version'],
	);
	
	$meta_dados = Array(
		'dominio_padrao' => Array(
			'definicao' => 'URL padrão do seu site.',
			'tipo' => 'static',
			'grupo' => 'site',
			'titulo' => 'Domínio Padrão URL',
			'size' => 50,
			'tabela' => 'host',
		),
		'dominio_proprio' => Array(
			'definicao' => 'Defina aqui qual &eacute; a URL principal do seu site.',
			'tipo' => 'string',
			'grupo' => 'site',
			'titulo' => 'Domínio Próprio URL',
			'size' => 50,
			'tabela' => 'host',
		),
		'mobile' => Array(
			'definicao' => 'Ativar/desativar as p&aacute;ginas mobile. Com essa op&ccedil;&atilde;o o sistema trabalha com 2 vers&otilde;es de p&aacute;ginas do seu site para o usu&aacute;rio de mobile tenha uma vers&atilde;o preparada para telas menores.',
			'tipo' => 'bool',
			'grupo' => 'site',
			'titulo' => 'Mobile',
			'size' => 50,
			'tabela' => 'host',
		),
		'mobile_redirect_server' => Array(
			'definicao' => 'Ativar/desativar redirecionamento autom&aacute;tico via servidor para vers&atilde;o mobile.',
			'tipo' => 'bool',
			'grupo' => 'site',
			'titulo' => 'Redirecionamento Automático para Mobile',
			'size' => 50,
			'tabela' => 'host',
		),
		'url_mobile' => Array(
			'definicao' => 'Por padr&atilde;o a URL do mobile &eacute; a URL principal do site precedida de uma letra m. Ent&atilde;o se o dom&iacute;nio principal &eacute; http://dominio , a URL mobile ser&aacute; http://m.dominio.',
			'tipo' => 'static',
			'grupo' => 'site',
			'titulo' => 'Mobile URL',
			'size' => 50,
			'tabela' => 'host',
		),
		'https' => Array(
			'definicao' => 'Ativar/desativar as p&aacute;ginas seguras SSL. IMPORTANTE: S&oacute; ative essa op&ccedil;&atilde;o caso o SSL do dom&iacute;nio atual esteja ativo. Caso ative essa op&ccedil;&atilde;o e apresente falhas visuais durante o uso da ferramenta, favor entrar em contato com suporte para saber como solucionar o problema.',
			'tipo' => 'bool',
			'grupo' => 'site',
			'titulo' => 'HTTPS',
			'size' => 50,
			'tabela' => 'host',
		),
		'google_analytic' => Array(
			'definicao' => 'Defini&ccedil;&atilde;o do Google Analytics do seu site.',
			'tipo' => 'codemirror-js-mini',
			'grupo' => 'site',
			'titulo' => 'Google Analytics',
			'size' => 50,
			'tabela' => 'host',
			'publish' => true,
		),
		'google_site_verification' => Array(
			'definicao' => 'Defini&ccedil;&atilde;o do Google Site Verification do seu site.',
			'tipo' => 'codemirror-js-mini',
			'grupo' => 'site',
			'titulo' => 'Google Site Verification',
			'size' => 50,
			'tabela' => 'host',
			'publish' => true,
		),
		'meta_global' => Array(
			'definicao' => 'Defini&ccedil;&atilde;o do Head Global do seu site.',
			'tipo' => 'codemirror-html',
			'grupo' => 'site',
			'titulo' => 'Head Global',
			'size' => 50,
			'tabela' => 'host',
			'publish' => true,
		),
		'javascript_global' => Array(
			'definicao' => 'Defini&ccedil;&atilde;o do JavaScript Global do seu site.',
			'tipo' => 'codemirror-js',
			'grupo' => 'site',
			'titulo' => 'JavaScript Global',
			'size' => 50,
			'tabela' => 'host',
			'publish' => true,
		),
		'css_global' => Array(
			'definicao' => 'Defini&ccedil;&atilde;o do CSS Global do seu site.',
			'tipo' => 'codemirror-css',
			'grupo' => 'site',
			'titulo' => 'CSS Global',
			'size' => 100,
			'tabela' => 'host',
			'publish' => true,
		),
		'body_global' => Array(
			'definicao' => 'Defini&ccedil;&atilde;o do Body Global do seu site.',
			'tipo' => 'codemirror-html',
			'grupo' => 'site',
			'titulo' => 'Body Global',
			'size' => 100,
			'tabela' => 'host',
			'publish' => true,
		),
		'nome' => Array(
			'definicao' => 'É uma satisfação ter você aqui. Fique à vontade.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Nome da sua loja',
			'size' => 50,
			'categoria' => '0',
		),
		'url_continuar_comprando' => Array(
			'definicao' => 'Defina para qual página o usuário será direcionado quando clicar no botão "continuar comprando". Basta colocar a URL desejada.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'URL Continuar Comprando',
			'categoria' => '0',
		),
		'descricao' => Array(
			'definicao' => 'Um texto explicativo do que &eacute; a loja.',
			'tipo' => 'tinymce',
			'grupo' => 'loja',
			'titulo' => 'Descrição',
			'categoria' => '0',
		),
		'widget_loja' => Array(
			'definicao' => 'Ativar ou desativar Widget Loja para que nas p&aacute;ginas mostre um menu com as op&ccedil;&otilde;es da sua loja na p&aacute;gina final.',
			'tipo' => 'bool',
			'grupo' => 'loja',
			'titulo' => 'Widget Loja',
			'categoria' => '0',
		),
		'expiracao_pagamento' => Array(
			'definicao' => 'Defina em horas o tempo que um pedido pode aguardar parta efetuar o pagamento. Após este tempo o pedido é automaticamente alterado para: Pagamento Expirado.',
			'tipo' => 'int',
			'grupo' => 'loja',
			'titulo' => 'Expiração de Pagamento (horas)',
			'categoria' => '0',
			'valor_padrao' => '48',
		),
		'parcelamento' => Array(
			'definicao' => 'Ativar ou desativar o parcelamento para mostrar na página de cada serviço o máximo de vezes que pode parcelar um serviço bem como o valor da parcela.',
			'tipo' => 'bool',
			'grupo' => 'loja',
			'titulo' => 'Parcelamento',
			'categoria' => '4',
		),
		'parcelamento_valor_minimo' => Array(
			'definicao' => 'O valor mínimo do parcelamento para calcular o valor da parcela de cada serviço a venda.',
			'tipo' => 'money',
			'class' => 'money',
			'grupo' => 'loja',
			'titulo' => 'Valor Mínimo do Parcelamento (R$)',
			'categoria' => '4',
		),
		'parcelamento_maximo_parcelas' => Array(
			'definicao' => 'A quantidade máxima de parcelas permitidas para calcular o valor da parcela de cada serviço a venda.',
			'tipo' => 'int',
			'class' => 'parcelamento_maximo_parcelas',
			'extra-attr' => ' min="0" max="24"',
			'grupo' => 'loja',
			'titulo' => 'Quantidade Máxima de Parcelas',
			'categoria' => '4',
		),
		'parcelamento_modelo_informativo' => Array(
			'definicao' => 'O modelo informativo das parcelas é como aparecerá na página de todos os serviços a quantidade de vezes que pode parcelar uma compra, bem como o valor em reais de cada parcela. Esse valor é calculado levando em conta o número máximo de parcelas e o valor mínimo que uma parcela pode ter. #QUANT# será subistituído pela quantidade de parcelas e o #VALOR# será subistituído pelo valor da parcela. Os demais textos aparecerão conforme a definição feita aqui.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Modelo Informativo das Parcelas',
			'categoria' => '4',
		),
		'esquema_cores' => Array(
			'definicao' => 'Deseperte o artista em você e divirta-se com a paleta de cores.',
			'tipo' => 'static',
			'grupo' => 'loja',
			'titulo' => 'Esquema de Cores',
			'categoria' => '2',
		),
		'fontes' => Array(
			'definicao' => 'Escolha a fonte que mais lhe agrada. Pode caprichar na estética do texto.',
			'tipo' => 'static',
			'grupo' => 'loja',
			'titulo' => 'Fonte',
			'categoria' => '2',
		),
		'id' => Array(
			'definicao' => 'É o endereço virtual da sua loja. Ele vai aparecer na barra de endereços do navegador.',
			'tipo' => 'static',
			'grupo' => 'loja',
			'titulo' => 'URL',
			'categoria' => '2',
		),
		'logomarca' => Array(
			'definicao' => 'Insira aqui o arquivo para completar a identidade da sua loja.',
			'tipo' => 'image',
			'grupo' => 'loja',
			'titulo' => 'Logo',
			'categoria' => '2',
		),
		'observacao_servicos' => Array(
			'definicao' => 'Observação Padrão de Serviços é um texto de observação que será selecionado na criação / edição de serviços sem a necessidade de preencher sempre o mesmo valor.',
			'tipo' => 'text',
			'grupo' => 'loja',
			'titulo' => 'Observação Padrão de Serviços',
			'categoria' => '2',
		),
		'descricao_servicos' => Array(
			'definicao' => 'Descrição Padrão de Serviços é um texto de descrição que será selecionado na criação / edição de serviços sem a necessidade de preencher sempre o mesmo valor.',
			'tipo' => 'text',
			'grupo' => 'loja',
			'titulo' => 'Descrição Padrão de Serviços',
			'categoria' => '2',
		),
		'email' => Array(
			'definicao' => 'Onde deseja receber sua correspondência eletrônica?',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Sua conta de e-mail',
			'size' => 50,
			'categoria' => '1',
		),
		'email_assunto' => Array(
			'definicao' => 'Como você deseja nomear o assunto dos e-mails automáticos enviados para os novos clientes?',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Assunto dos E-mails',
			'categoria' => '1',
		),
		'email_assinatura' => Array(
			'definicao' => 'Já sabe como você quer assinar os e-mails?',
			'tipo' => 'tinymce',
			'grupo' => 'loja',
			'titulo' => 'Assinatura dos E-mails',
			'categoria' => '1',
		),
		'cnpj' => Array(
			'definicao' => 'CNPJ da sua loja. Informação: pela lei nova é necessário informar o CNPJ ou o CPF do dono da loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'CNPJ',
			'size' => 30,
			'class' => 'cnpj',
			'categoria' => '3',
		),
		'cpf' => Array(
			'definicao' => 'CPF do dono da loja senão houver CNPJ. Se informar o CNPJ não é necessário informar o CPF. Informação: pela lei nova é necessário informar o CNPJ ou o CPF do dono da loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'CPF',
			'size' => 30,
			'class' => 'cpf',
			'categoria' => '3',
		),
		'endereco' => Array(
			'definicao' => 'Endereço da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Endereço',
			'size' => 50,
			'categoria' => '3',
		),
		'numero' => Array(
			'definicao' => 'Número da sua loja.',
			'tipo' => 'int',
			'grupo' => 'loja',
			'titulo' => 'Número',
			'categoria' => '3',
		),
		'complemento' => Array(
			'definicao' => 'Complemento da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Complemento',
			'categoria' => '3',
		),
		'bairro' => Array(
			'definicao' => 'Bairro da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Bairro',
			'size' => 50,
			'categoria' => '3',
		),
		'cidade' => Array(
			'definicao' => 'Cidade da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Cidade',
			'size' => 50,
			'categoria' => '3',
		),
		'uf' => Array(
			'definicao' => 'UF da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'UF',
			'size' => 4,
			'maxlength' => 2,
			'class' => 'uppercase',
			'categoria' => '3',
		),
		'pais' => Array(
			'definicao' => 'País da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'País',
			'size' => 50,
			'categoria' => '3',
		),
		'telefone' => Array(
			'definicao' => 'Telefone da sua loja.',
			'tipo' => 'string',
			'grupo' => 'loja',
			'titulo' => 'Telefone',
			'size' => 50,
			'class' => 'telefone',
			'categoria' => '3',
		),
		/* 'pagseguro_notification_url' => Array(
			'definicao' => 'Caso ainda não esteja <span id="pagseguro-auth-label-1">AUTORIZADO</span>, clique no botão AUTORIZAR para o B2Make interagir em seu nome com o PagSeguro nas suas vendas. Feito esta ação, o B2make irá te direcionar para uma página de autorização do PagSeguro. Dê todas as permissões e em seguida o sistema retornará a essa tela com a autorização do PagSeguro. Assim sua loja irá automaticamente interagir futuramente com o PagSeguro sem a necessidade de sua interação.',
			'tipo' => 'static',
			'grupo' => 'pagseguro',
			'titulo' => 'Autorização',
		),
		'pagseguro_parcelas_sem_juros' => Array(
			'definicao' => 'Ative essa op&ccedil;&atilde;o para fornecer parcelas do pagamento a prazo sem juros para os compradores dos servi&ccedil;os. Escolhendo essa op&ccedil;&atilde;o o dono da loja concorda em ele pagar o juros do parcelamento ao inv&eacute;s do seu cliente seguindo as pol&iacute;ticas pr&oacute;prias do PagSeguro. Acesse <a href="https://pagseguro.uol.com.br/taxas-e-tarifas.jhtml#rmcl" target="_blank">aqui</a> para maiores informa&ccedil;&otilde;es sobre taxas em parcelamentos.',
			'tipo' => 'select',
			'grupo' => 'pagseguro',
			'titulo' => 'Parcelas Sem Juros',
			'subtipo' => 'inteiro',
			'valor_inicial' => 0,
			'valor_final' => $_PROJETO['PAGSEGURO_PARCELAS_SEM_JUROS_MAX'],
		),
		'paypal_email' => Array(
			'definicao' => 'O paypal_email do PayPal &eacute; o seu email cadastrado no PayPal.',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Email no PayPal',
		),
		'paypal_user' => Array(
			'definicao' => 'O paypal_user do PayPal &eacute; o usu&aacute;rio da credencial do PayPal.',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Usuário do API',
		),
		'paypal_pass' => Array(
			'definicao' => 'O paypal_pass do PayPal &eacute; a senha da credencial do PayPal. <b>IMPORTANTE: Por uma questão de segurança este campo ficará oculto!</b>',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Senha do API',
		),
		'paypal_signature' => Array(
			'definicao' => 'O paypal_signature do PayPal &eacute; a assinatura da credencial do PayPal. <b>IMPORTANTE: Por uma questão de segurança este campo ficará oculto!</b>',
			'tipo' => 'string',
			'grupo' => 'paypal',
			'titulo' => 'Assinatura do API',
		),
		'paypal_notification_url' => Array(
			'definicao' => 'Endereço que deverá ser fornecido ao PayPal para o mesmo notificar o nosso sistema quando houver uma modificação de estado dos pagamentos.',
			'tipo' => 'static',
			'grupo' => 'paypal',
			'titulo' => 'URL de Notificações',
		), */
		'voucher_sem_para_presente' => Array(
			'definicao' => 'Ative essa op&ccedil;&atilde;o para n&atilde;o permitir que o voucher d&ecirc; op&ccedil;&atilde;o de &quot;Para Presente&quot; e sendo assim s&oacute; poder&aacute; ser usado apenas mediante a identifica&ccedil;&atilde;o pessoal.',
			'tipo' => 'bool',
			'grupo' => 'voucher',
			'titulo' => 'Desabilitar Para Presente',
		),
		/* 'identificacao_voucher' => Array(
			'definicao' => 'Ative essa op&ccedil;&atilde;o para obrigar a identifica&ccedil;&atilde;o pessoal dos vouchers bem como habilitar o sistema para criar uma nova etapa na forma&ccedil;&atilde;o do voucher que &eacute; o cadastro da identifica&ccedil;&atilde;o pessoal.',
			'tipo' => 'bool',
			'grupo' => 'voucher',
			'titulo' => 'Identificação Pessoal',
		), */
		'voucher_sem_escolha_tema' => Array(
			'definicao' => 'Ative essa op&ccedil;&atilde;o para n&atilde;o permitir que o voucher d&ecirc; op&ccedil;&atilde;o de &quot;Tema Modificar&quot; e sendo assim n&atilde;o ser&aacute; mais poss&iacute;vel modificar o tema do voucher.',
			'tipo' => 'bool',
			'grupo' => 'voucher',
			'titulo' => 'Desabilitar Escolha Tema',
		),
		'paypal_app_installed' => Array(
			'definicao' => 'Escolha a op&ccedil;&atilde;o desejada antes de prosseguir.',
			'tipo' => 'static',
			'grupo' => 'paypal-plus',
			'titulo' => 'Configuração',
		),
		'publisher_all_pages' => Array(
			'definicao' => 'Selecione para que todas as p&aacute;ginas publicadas sejam republicadas no servidor de FTP.',
			'tipo' => 'static',
			'grupo' => 'site',
			'titulo' => 'Republicar Todas as Páginas',
		),
	);
	
	$meta_categorias = Array(
		Array(
			'grupo' => 'site',
			'categoria' => '0',
			'ordem' => '0',
			'titulo' => 'Definições Gerais',
			'descricao' => 'Defina aqui todos os dados da sua hospedagem de site.',
		),
		Array(
			'grupo' => 'loja',
			'categoria' => '0',
			'ordem' => '0',
			'titulo' => 'Sua conta',
			'descricao' => 'É aqui que você se apresenta para o mundo. Insira as informações principais a serem compartilhadas com seus clientes.',
		),
		Array(
			'grupo' => 'loja',
			'categoria' => '4',
			'ordem' => '1',
			'titulo' => 'Parcelamento',
			'descricao' => 'Defina as opções de parcelamento para calcular automaticamente os valores das parcelas de cada serviço.',
		),
		Array(
			'grupo' => 'loja',
			'categoria' => '1',
			'ordem' => '2',
			'titulo' => 'Comunicações de E-mail',
			'descricao' => 'Estabeleça diálogo direto com seus clientes. Preencha os campos e deixe a comunicação acontecer.',
		),
		Array(
			'grupo' => 'loja',
			'categoria' => '2',
			'ordem' => '3',
			'titulo' => 'Personalização',
			'descricao' => 'Deixe a página de check-out com a cara da sua loja. Escolha as cores que preferir, a fonte dos textos e a URL. Adicione também o logo. Nós valorizamos sua identidade.',
		),
		Array(
			'grupo' => 'loja',
			'categoria' => '3',
			'ordem' => '4',
			'titulo' => 'Dados',
			'descricao' => 'Defina aqui os dados gerais para concluir o seu cadastro.',
		),
		Array(
			'grupo' => 'voucher',
			'categoria' => '0',
			'ordem' => '0',
			'titulo' => 'Configurações',
			'descricao' => 'Defina aqui as configurações do voucher da sua loja.',
		),
		Array(
			'grupo' => 'paypal-plus',
			'categoria' => '0',
			'ordem' => '0',
			'titulo' => 'Configurações',
			'descricao' => 'Defina aqui as configurações da sua loja com o PayPal.',
		),
	);

	if($loja[0]['paypal_app_installed']){
		if($loja[0]['paypal_reference_installed']){
		$loja[0]['paypal_app_installed'] = '
	<div id="paypal-app-config">
		<div id="paypal-app-desinstall-cont" class="b2make-check-box" data-request-field="paypal-app-desinstall" data-callback="#paypal-app-desinstall-cont">
			<div data-val="sim" title="Clique para desinstalar o meio de pagamento PayPal">DESINSTALAR</div>
		</div>
		<div id="paypal-app-active-cont" class="b2make-check-box" data-request-field="paypal-app-active" data-checked-num="'.($loja[0]['paypal_app_active'] ? '1' : '2').'">
			<div data-val="sim" title="Clique para ativar o meio de pagamento PayPal">ATIVO</div>
			<div data-val="nao" title="Clique para desativar o meio de pagamento PayPal">INATIVO</div>
		</div>
		<div id="paypal-app-live-cont" class="b2make-check-box" data-request-field="paypal-app-live" data-checked-num="'.($loja[0]['paypal_app_live'] ? '1' : '2').'">
			<div data-val="sim" title="Clique para ativar o ambiente real do PayPal">LIVE</div>
			<div data-val="nao" title="Clique para ativar o ambiente de testes do PayPal">SANDBOX</div>
		</div>
		<div id="paypal-app-inactive-cont" class="b2make-check-box" data-request-field="paypal-plus-inactive" data-checked-num="'.($loja[0]['paypal_plus_inactive'] ? '2' : '1').'">
			<div data-val="sim" title="Clique para ativar o PayPal Plus">PAYPAL PLUS ATIVO</div>
			<div data-val="nao" title="Clique para inativar o PayPal Plus">PAYPAL PLUS INATIVO</div>
		</div>
	</div>';
		} else {
		$loja[0]['paypal_app_installed'] = '
	<div id="paypal-app-config">
		<div id="paypal-app-desinstall-cont" class="b2make-check-box" data-request-field="paypal-app-desinstall" data-callback="#paypal-app-desinstall-cont">
			<div data-val="sim" title="Clique para desinstalar o meio de pagamento PayPal">DESINSTALAR</div>
		</div>
		<div id="paypal-app-desinstall-cont" class="b2make-check-box" data-request-field="paypal-heference-install" data-callback="#paypal-app-desinstall-cont">
			<div data-val="sim" title="Clique para habilitar PayPal Reference">HABILITAR REFERENCE</div>
		</div>
	</div>';
		}
	} else {
		$loja[0]['paypal_app_installed'] = '
	<div id="paypal-app-config">
		<input type="button" id="paypal-app-install-btn" value="INSTALAR">
		<div id="paypal-app-install-cont">
			<div class="paypal-app-title"><b>SANDBOX API CREDENTIALS</b></div>
			<div class="opcao string">
				<div class="input">
					<label>Client ID</label>
					<input name="paypal-app-sandbox-code" id="paypal-app-sandbox-code" size="80" maxlength="100" type="text">
				</div>
				<div class="info infotexto">
					Preencha o Client ID fornecido pelo aplicativo do PayPal no modo SANDBOX.
				</div>
				<div class="clear"></div>
			</div>
			<div class="opcao string">
				<div class="input">
					<label>Secret</label>
					<input name="paypal-app-sandbox-secret" id="paypal-app-sandbox-secret" size="80" maxlength="100" type="text">
				</div>
				<div class="info infotexto">
					Preencha o Secret fornecido pelo aplicativo do PayPal no modo SANDBOX.
				</div>
				<div class="clear"></div>
			</div>
			<div class="paypal-app-title"><b>LIVE API CREDENTIALS</b></div>
			<div class="opcao string">
				<div class="input">
					<label>Client ID</label>
					<input name="paypal-app-code" id="paypal-app-code" size="80" maxlength="100" type="text">
				</div>
				<div class="info infotexto">
					Preencha o Client ID fornecido pelo aplicativo do PayPal no modo LIVE.
				</div>
				<div class="clear"></div>
			</div>
			<div class="opcao string">
				<div class="input">
					<label>Secret</label>
					<input name="paypal-app-secret" id="paypal-app-secret" size="80" maxlength="100" type="text">
				</div>
				<div class="info infotexto">
					Preencha o Secret fornecido pelo aplicativo do PayPal no modo LIVE.
				</div>
				<div class="clear"></div>
			</div>
		</div>
	</div>';
	}
	
	$loja[0]['publisher_all_pages'] = '
	<input name="publisher_all_pages" id="publisher_all_pages" value="1" type="checkbox" class="publisher_all_pages">';
	
	if($loja[0]['pagseguro_app_code']) $loja[0]['pagseguro_notification_url'] = '<div id="pagseguro-autorizado">AUTORIZADO</div>'; else $loja[0]['pagseguro_notification_url'] = '<input type="button" id="b2make-store-pagseguro-auth" value="AUTORIZAR">';
	$loja[0]['paypal_notification_url'] = $_B2MAKE_URL . 'e-services/paypal-notifications/' . $usuario['pub_id'] . '/';
	
	if($host[0]['dominio_proprio'])$loja[0]['dominio_proprio'] = $host[0]['dominio_proprio']; else $loja[0]['dominio_proprio'] = '<!--dominio_proprio-->';
	$loja[0]['dominio_padrao'] = 'http'.($host[0]['https'] ? 's':'').'://'.$host[0]['user_cpanel'].'.'.$_B2MAKE_FTP_SITE_HOST.'/';
	
	$esquema_cores = $loja[0]['esquema_cores'];
	
	$cont_cores = '<div id="b2make-esquema-cor-1" class="b2make-jpicker b2make-tooltip b2make-esquema-cor" data-position="middle" data-obj-callback="#b2make-esquema-cor-1" title="Clique para mudar a cor do objeto desejado"></div>';
	$cont_cores2 = '<div id="b2make-esquema-cor-2" class="b2make-jpicker b2make-tooltip b2make-esquema-cor" data-position="middle" data-obj-callback="#b2make-esquema-cor-2" title="Clique para mudar a cor do objeto desejado"></div>';
	$cont_cores3 = '<div id="b2make-esquema-cor-3" class="b2make-jpicker b2make-tooltip b2make-esquema-cor" data-position="middle" data-obj-callback="#b2make-esquema-cor-3" title="Clique para mudar a cor do objeto desejado"></div>';
	
	$loja[0]['esquema_cores'] = '<div id="b2make-esquema-cor-cont">'.$cont_cores.$cont_cores2.$cont_cores3.'</div><input name="esquema_cores" id="b2make-esquema-cores-input" value="'.$esquema_cores.'" type="hidden">';
	
	$cont_fontes = '<div id="b2make-fontes-select" class="b2make-fonts-instance" data-options="font-select"></div>';
	
	$fontes = $loja[0]['fontes'];
	
	$loja[0]['fontes'] = '<div id="b2make-fontes-cont">'.$cont_fontes.'</div><input name="fontes" id="b2make-fontes-input" value="'.$fontes.'" type="hidden">';
	
	if($loja){
		foreach($meta_dados as $key => $val){
			if($grupo_sessao != $val['grupo']) continue;
			foreach($loja[0] as $var => $valor){
				if($var != $key) continue;
				$campos_nome[] = $var;
				if($meta_dados[$var]['tabela'])$campos_tabela[$var] = $meta_dados[$var]['tabela'];
				
				if($val['valor_padrao'] && !$valor){
					$valor = $val['valor_padrao'];
				}
				
				$campos_guardar[$var] = $valor;
				
				$cel_aux = $cel[$meta_dados[$var]['tipo']];
				
				$cel_aux = modelo_var_troca($cel_aux,'#titulo','<b>'.$meta_dados[$var]['titulo'].'</b>');
				$cel_aux = modelo_var_troca_tudo($cel_aux,'#variavel',$var);
				
				if($var == 'dominio_proprio' && $valor == '<!--dominio_proprio-->'){
					$valor = '';
				}
				
				if($var == 'parcelamento_valor_minimo'){
					if(!$valor){
						$valor = '10,00';
					} else {
						$valor = preparar_float_4_texto($valor);
					}
				}
				
				if($var == 'parcelamento_maximo_parcelas' && !$valor){
					$valor = '12';
				}
				
				if($var == 'parcelamento_modelo_informativo' && !$valor){
					$valor = '#QUANT#x de R$ #VALOR# s/ juros no cartão de crédito';
				}
				
				if($var == 'email_assunto' && !$valor){
					$valor = 'Seu pedido #codigo# foi atualizado';
				}
				
				if($var == 'email_assinatura' && !$valor){
					$valor = '<p>Atenciosamente.</p><h3>#loja-nome#</h3>';
				}
				
				if(
					$var == 'paypal_pass' ||
					$var == 'paypal_signature'
				)
					$valor = '';
				
				if($var == 'meta_global')
					$valor = htmlspecialchars($valor,ENT_QUOTES,'UTF-8');
				
				if($meta_dados[$var]['tipo'] == 'bool')
					if($valor)
						$valor = ' checked="checked"';
					
				if($var == 'id'){
					if($valor){
						$valor = $host[0]['url'] . 'cart/';
						$valor = '<a href="'.$valor.'" target="b2make-store">'.$valor.'</a>';
					}
				}	
				
				if($var == 'logomarca'){
					if($loja[0]['versao'])$versao = '?v=' . $loja[0]['versao'];
					if($valor)$valor = '<img src="/'.$_SYSTEM['ROOT'].$valor.$versao.'"><a href="?opcao=remover_item&item=logomarca" class="deletar"></a>';
				}
				
				$class = $var;
				$size = 40;
				$maxlength = 100;
				if($meta_dados[$var]['tipo'] == 'int' || $meta_dados[$var]['tipo'] == 'float'){
					$size = 20;
					$maxlength = 20;
				}
				
				if($meta_dados[$var]['size'])$size = $meta_dados[$var]['size'];
				if($meta_dados[$var]['maxlength'])$maxlength = $meta_dados[$var]['maxlength'];
				if($meta_dados[$var]['class'])$class = $meta_dados[$var]['class'];
				if($meta_dados[$var]['extra-attr'])$extra_attr = $meta_dados[$var]['extra-attr'];
				
				$cel_aux = modelo_var_troca($cel_aux,'#class',$class);
				$cel_aux = modelo_var_troca($cel_aux,'#extra-attr#',$extra_attr);
				
				if($meta_dados[$var]['tipo'] == 'select'){
					switch($meta_dados[$var]['subtipo']){
						case 'inteiro':
							$cel_nome = 'options'; $cel_options = modelo_tag_val($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
							$cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
							
							for($i=$meta_dados[$var]['valor_inicial'];$i<=$meta_dados[$var]['valor_final'];$i++){
								if($valor && (int)$valor == $i){
									$selected = ' selected="selected"';
								} else {
									$selected = '';
								}
								
								$cel_aux2 = $cel_options;
								
								$cel_aux2 = modelo_var_troca($cel_aux2,"#valor",$i);
								$cel_aux2 = modelo_var_troca($cel_aux2,"#label",$i);
								$cel_aux2 = modelo_var_troca($cel_aux2,"#selected#",$selected);
								
								$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_nome.' -->',$cel_aux2);
							}
						break;
					}
				} else {
					$cel_aux = modelo_var_troca($cel_aux,'#valor',$valor);
					$cel_aux = modelo_var_troca($cel_aux,'#size',$size);
					$cel_aux = modelo_var_troca($cel_aux,'#maxlength',$maxlength);
				}
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,'#descricao',$meta_dados[$var]['definicao']);
				
				$campos_html[] = Array(
					'grupo' => $meta_dados[$var]['grupo'],
					'categoria' => ($meta_dados[$var]['categoria'] ? $meta_dados[$var]['categoria'] : '0'),
					'html' => $cel_aux,
				);
			}
		}
	}
	
	
	if($campos_html)
	foreach($campos_html as $campo_html){
		if($campo_html['grupo'] == $grupo_sessao){
			foreach($meta_categorias as $meta_categoria){
				if($campo_html['grupo'] == $meta_categoria['grupo'] && $campo_html['categoria'] == $meta_categoria['categoria']){
					$categoria = $meta_categoria['categoria'];
					
					if(!$categorias_html[$categoria]){
						
						$cel_nome = 'categoria';
						$cel_aux = $cel[$cel_nome];
						
						$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$meta_categoria['titulo']);
						$cel_aux = modelo_var_troca($cel_aux,"#descricao#",$meta_categoria['descricao']);
						
						
						$categorias_html[$categoria] = $cel_aux;
					}
					
					$categorias_html[$categoria] = modelo_var_in($categorias_html[$categoria],'<!-- dados -->',$campo_html['html']);
				}
			}
		}
	}
	
	if($categorias_html)
	foreach($categorias_html as $categoria_html){
		$pagina = modelo_var_in($pagina,'<!-- categoria -->',$categoria_html);
	}
	
	$pagina = modelo_var_troca($pagina,'#site$','');
	$pagina = modelo_var_troca($pagina,'#loja$','');
	$pagina = modelo_var_troca($pagina,'#pagseguro$','');
	$pagina = modelo_var_troca($pagina,'#paypal$','');
	$pagina = modelo_var_troca($pagina,'#paypal-plus$','');
	$pagina = modelo_var_troca($pagina,'#voucher$','');
	
	$pagina = modelo_var_troca_tudo($pagina,'<!-- dados -->','');
	
	$campos_guardar['campos_nome'] = $campos_nome;
	$campos_guardar['campos_tabela'] = $campos_tabela;
	$campos_guardar['meta_dados'] = $meta_dados;
	$campos_guardar['campos_extra'] = $campos_extra;
	
	$sessoes = Array('paypal','voucher','store','site');
	
	foreach($sessoes as $s){
		if($s != $sessao){
			$cel_nome = 'cel-'.$s; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		}
	}
	
	if($_REQUEST['sessao'])$_SESSION[$_SYSTEM['ID']."config-sessao-atual"] = $sessao;
	
	// ======================================================================================
	
	banco_fechar_conexao();
	
	campos_antes_guardar($campos_guardar);
	
	$in_titulo = $param ? "Visualizar" : "Modificar";
	$botao = "Salvar";
	$opcao = "editar_base";
	
	$pagina = modelo_var_troca($pagina,"#form_url",$_LOCAL_ID);
	$pagina = modelo_var_troca($pagina,"#botao",$botao);
	$pagina = modelo_var_troca($pagina,"#opcao",$opcao);
	$pagina = modelo_var_troca($pagina,"#id",$id);
	
	if(!operacao('editar') || !$loja)$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	$_INTERFACE_OPCAO = 'editar'; 
	$_INTERFACE['informacao_titulo'] = "Lista";
	$_INTERFACE['informacao_id'] = $id;
	$_INTERFACE['inclusao'] = $pagina;

	return interface_layout(parametros_interface());
}

function editar_base(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_ALERTA;
	global $_ESERVICE;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_CPANEL;
	global $_CPANEL_USER;
	global $_B2MAKE_FTP_SITE_HOST;
	global $_B2MAKE_URL;
	global $_ALERTA_PROBLEMA;
	global $_B2MAKE_FTP_SITE_ROOT;
	global $_B2MAKE_FTP_FILES_ROOT;
	global $_PROJETO;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$sessao = $_SESSION[$_SYSTEM['ID']."config-sessao-atual"];
	
	$campos_antes = campos_antes_recuperar();
	
	banco_conectar();
	
	// ================================= Local de Edição ===============================
	// Altere os campos da tabela e POST aqui, e modifique o UPDATE
	$campo_tabela = "loja";
	$campo_tabela_extra = "WHERE id_loja='".$usuario['id_loja']."'"
	." AND status='A'";
	
	$campos_nome = $campos_antes['campos_nome'];
	$campos_tabela = $campos_antes['campos_tabela'];
	$meta_dados = $campos_antes['meta_dados'];
	$campos_extra = $campos_antes['campos_extra'];
	
	foreach($campos_nome as $campo_nome){
		if(
			$campo_nome != 'publisher_all_pages' &&
			$campo_nome != 'paypal_app_installed' &&
			$campo_nome != 'paypal_app_active' &&
			$campo_nome != 'paypal_plus_inactive' &&
			$campo_nome != 'paypal_app_live' &&
			$campo_nome != 'logomarca' &&
			$campo_nome != 'pagseguro_notification_url' &&
			$campo_nome != 'pagseguro_app_code' &&
			$campo_nome != 'paypal_notification_url' 
		){
			if(
				$campo_nome == 'paypal_pass' ||
				$campo_nome == 'paypal_signature' ||
				$campo_nome == 'pagseguro_token' 
			){
				if($_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";}
			} else {
				if(
					$campo_nome != 'https' && 
					$campo_nome != 'url' && 
					$campo_nome != 'url_mobile' && 
					$campo_nome != 'dominio_proprio' && 
					$campo_nome != 'dominio_padrao' && 
					$campo_nome != 'mobile' && 
					$campo_nome != 'versao' && 
					$campo_nome != 'global_version' && 
					$campo_nome != 'id'
				){
					if(
						$campo_nome == 'parcelamento' || 
						$campo_nome == 'widget_loja' || 
						$campo_nome == 'voucher_sem_escolha_tema' || 
						$campo_nome == 'voucher_sem_para_presente' || 
						$campo_nome == 'identificacao_voucher'
					){
						if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = ($_REQUEST[$campo_nome] ? $campo_nome."=1" : $campo_nome."=NULL" ); $loja_publicar_configuracoes_paginas = true;}
						
					} else {
						if($campo_nome == 'mobile_redirect_server'){
							if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar['host'][] = ($_REQUEST[$campo_nome] ? $campo_nome."=1" : $campo_nome."=NULL" );}
						} else {
							switch($campo_nome){
								case 'parcelamento_valor_minimo':
									$request_val = preparar_texto_4_float($_REQUEST[$campo_nome]);
									$loja_publicar_configuracoes_paginas = true;
								break;
								default:
									$request_val = $_REQUEST[$campo_nome];
							}
							
							if($sessao == 'store'){
								$loja_publicar_configuracoes_paginas = true;
							}
							
							if($campos_tabela[$campo_nome]){	
								if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$meta_dados[$campo_nome]['tabela']][] = $campo_nome."='" . $request_val . "'";}
							} else {
								if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $request_val . "'";}
							}
						}
						
						if($meta_dados[$campo_nome]['publish'] && addslashes($campos_antes[$campo_nome]) != $_REQUEST[$campo_nome]){
							$publish[$campo_nome] = true;
						}
					}
				} else if(
					$campo_nome == 'https'
				){
					if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){
						$host = banco_select_name
						(
							banco_campos_virgulas(Array(
								'url',
								'url_files',
							))
							,
							"host",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND atual IS TRUE"
						);
						
						$url = $host[0]['url'];
						$url_files = $host[0]['url_files'];
						
						$url = preg_replace('/^https?/i', '', $url);
						$url_files = preg_replace('/^https?/i', '', $url_files);
						
						if($_REQUEST[$campo_nome]){
							publisher_block_time(Array(
								'block_time' => 600
							));
							
							$url = 'https' . $url;
							$url_files = 'https' . $url_files;
							
							$_SESSION[$_SYSTEM['ID']."b2make-site"]['https'] = true;
						} else {
							$url = 'http' . $url;
							$url_files = 'http' . $url_files;
							
							$_SESSION[$_SYSTEM['ID']."b2make-site"]['https'] = false;
						}
						
						banco_update
						(
							"url='".$url."',".
							"url_files='".$url_files."'",
							"host",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND atual IS TRUE"
						);
						
						$editar['host'][] = ($_REQUEST[$campo_nome] ? $campo_nome."=1" : $campo_nome."=NULL" );
					}
				}
			}
			
			if($campo_nome == 'dominio_proprio'){
				if(($_REQUEST[$campo_nome] && $campos_antes[$campo_nome] == '<!--dominio_proprio-->') || (!$_REQUEST[$campo_nome] && $campos_antes[$campo_nome] != '<!--dominio_proprio-->') || ($_REQUEST[$campo_nome] != $campos_antes[$campo_nome] && $campos_antes[$campo_nome] != '<!--dominio_proprio-->')){
					if($_REQUEST['https']){
						$dominio_https = true;
					}
					
					$host = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_host',
							'user_cpanel',
							'url_mobile',
							'user_cpanel',
							'plano',
							'ftp_site_host',
						))
						,
						"host",
						"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
						." AND atual IS TRUE"
					);
					
					$dominio_padrao = $host[0]['user_cpanel'].'.'.$_B2MAKE_FTP_SITE_HOST;
					$url_mobile = $host[0]['url_mobile'];
					$user_cpanel = $host[0]['user_cpanel'];
					$id_host = $host[0]['id_host'];
					
					if($_REQUEST[$campo_nome]){
						if($host[0]['plano'] == '1'){
							$_ALERTA_PROBLEMA = '<p>Seu plano <b>'.$_SYSTEM['B2MAKE_PLANOS']['1']['nome'].'</b> é limitado e não fornece suporte para criar domínios próprios. Favor fazer UPGRADE so seu plano para poder liberar este recurso.</p>';
						} else {
							$dominio_proprio = $_REQUEST[$campo_nome];
							
							$url = parse_url($dominio_proprio);
							
							if(preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,10}$/', $url['host']) > 0 || preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,10}$/', $dominio_proprio) > 0){
								$dominio_proprio = ($url['host'] ? $url['host'] : $dominio_proprio);
								
								$mudar_dominio = $dominio_proprio;
								
								if($_SERVER['SERVER_NAME'] != "localhost"){
									$_CPANEL_USER = $user_cpanel;
									
									$_CPANEL['ACCT'] = Array(
										'user' => $user_cpanel,
										'domain_owner' => $dominio_proprio,
										'domain_park' => $dominio_padrao,
										'url_mobile' => $url_mobile,
									);
									
									require($_SYSTEM['SITE']['cpanel-xmlapi-path-3'].'b2make-xmlapi/cpanel-domain-owner-add.php');
									
									$ftp_site_user = 'b2make_site' . $id_host . '@' . $mudar_dominio;
									$ftp_files_user = 'b2make_files' . $id_host . '@' . $mudar_dominio;
									$ftp_site_host = $mudar_dominio;
									$ftp_files_host = $mudar_dominio;
									
									$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-user'] = $ftp_site_user;
									$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-user'] = $ftp_files_user;
									$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-host'] = $ftp_site_host;
									$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-host'] = $ftp_files_host;
									
									banco_update
									(
										"ftp_site_user='".$ftp_site_user."',".
										"ftp_files_user='".$ftp_files_user."',".
										"ftp_site_host='".$ftp_site_host."',".
										"ftp_files_host='".$ftp_files_host."',".
										"dominio_proprio_instalado=1,".
										"url='http".($dominio_https ? 's' : '')."://".$dominio_proprio."/',".
										"url_files='http".($dominio_https ? 's' : '')."://".$dominio_proprio."/files/',".
										"url_mobile='".'m.'.$dominio_proprio."',".
										"dominio_proprio='".$dominio_proprio."'",
										"host",
										"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
										." AND atual IS TRUE"
									);
									
									if($_CPANEL['ERROR']){
										$_CPANEL_ERRORS .= '<li>'.$_CPANEL['ERROR'].'</li>';
									}
									
									publisher_block_time(Array(
										'block_time' => 180
									));
								} else {
									$ftp_site_user = 'b2make_site' . $id_host . '@' . $mudar_dominio;
									$ftp_files_user = 'b2make_files' . $id_host . '@' . $mudar_dominio;
									$ftp_site_host = $mudar_dominio;
									$ftp_files_host = $mudar_dominio;
									
									banco_update
									(
										"ftp_site_user='".$ftp_site_user."',".
										"ftp_files_user='".$ftp_files_user."',".
										"ftp_site_host='".$ftp_site_host."',".
										"ftp_files_host='".$ftp_files_host."',".
										"dominio_proprio_instalado=1,".
										"url='http".($dominio_https ? 's' : '')."://".$dominio_proprio."/',".
										"url_files='http".($dominio_https ? 's' : '')."://".$dominio_proprio."/files/',".
										"url_mobile='".'m.'.$dominio_proprio."',".
										"dominio_proprio='".$dominio_proprio."'",
										"host",
										"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
										." AND atual IS TRUE"
									);
								}
							} else {
								$_ALERTA_PROBLEMA = '<p>Este domínio próprio informado não é válido. Favor preencher um domínio válido e tentar novamente.</p>';
							}
						}
					} else {
						$mudar_dominio = $dominio_padrao;
						$dominio_proprio = '';
						
						if($_SERVER['SERVER_NAME'] != "localhost"){
							$user_cpanel = $host[0]['user_cpanel'];
							$url_mobile = $host[0]['url_mobile'];
							
							$_CPANEL_USER = $user_cpanel;
							
							$_CPANEL['ACCT'] = Array(
								'user' => $user_cpanel,
								'domain_owner' => $mudar_dominio,
								'domain_park' => $mudar_dominio,
								'url_mobile' => $url_mobile,
							);
							
							require($_SYSTEM['SITE']['cpanel-xmlapi-path-3'].'b2make-xmlapi/cpanel-domain-owner-del.php');
							
							$ftp_site_user = 'b2make_site' . $id_host . '@' . $mudar_dominio;
							$ftp_files_user = 'b2make_files' . $id_host . '@' . $mudar_dominio;
							$ftp_site_host = $mudar_dominio;
							$ftp_files_host = $mudar_dominio;
							
							$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-user'] = $ftp_site_user;
							$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-user'] = $ftp_files_user;
							$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-host'] = $ftp_site_host;
							$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-host'] = $ftp_files_host;
							
							banco_update
							(
								"ftp_site_user='".$ftp_site_user."',".
								"ftp_files_user='".$ftp_files_user."',".
								"ftp_site_host='".$ftp_site_host."',".
								"ftp_files_host='".$ftp_files_host."',".
								"dominio_proprio_instalado=NULL,".
								"url='http".($dominio_https ? 's' : '')."://".$dominio_padrao."/',".
								"url_files='http".($dominio_https ? 's' : '')."://".$dominio_padrao."/files/',".
								"url_mobile='".'m.'.$dominio_padrao."/',".
								"dominio_proprio='".$dominio_proprio."'",
								"host",
								"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
								." AND atual IS TRUE"
							);
							
							if($_CPANEL['ERROR']){
								$_CPANEL_ERRORS .= '<li>'.$_CPANEL['ERROR'].'</li>';
							}
							
							publisher_block_time(Array(
								'block_time' => 180
							));
						} else {
							$ftp_site_user = 'b2make_site' . $id_host . '@' . $mudar_dominio;
							$ftp_files_user = 'b2make_files' . $id_host . '@' . $mudar_dominio;
							$ftp_site_host = $mudar_dominio;
							$ftp_files_host = $mudar_dominio;
							
							banco_update
							(
								"ftp_site_user='".$ftp_site_user."',".
								"ftp_files_user='".$ftp_files_user."',".
								"ftp_site_host='".$ftp_site_host."',".
								"ftp_files_host='".$ftp_files_host."',".
								"dominio_proprio_instalado=NULL,".
								"url='http".($dominio_https ? 's' : '')."://".$dominio_padrao."/',".
								"url_files='http".($dominio_https ? 's' : '')."://".$dominio_padrao."/files/',".
								"url_mobile='".'m.'.$dominio_padrao."/',".
								"dominio_proprio='".$dominio_proprio."'",
								"host",
								"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
								." AND atual IS TRUE"
							);
						}
					}
					
					$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-user'] = $ftp_site_user;
					$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-user'] = $ftp_files_user;
					$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-host'] = $ftp_site_host;
					$_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-files-host'] = $ftp_files_host;
					$_SESSION[$_SYSTEM['ID']."b2make-site"]['url'] = 'http'.($dominio_https ? 's' : '').'://'.$mudar_dominio.'/';
					$_SESSION[$_SYSTEM['ID']."b2make-site"]['url-files'] = 'http'.($dominio_https ? 's' : '').'://'.$mudar_dominio.'/files/';
				}
			}
			
			if($campo_nome == 'mobile'){
				if($_REQUEST[$campo_nome] != $campos_antes[$campo_nome]){
					if($_REQUEST[$campo_nome]){
						$host = banco_select_name
						(
							banco_campos_virgulas(Array(
								'dominio_proprio',
								'url',
								'url_mobile',
								'user_cpanel',
								'plano',
							))
							,
							"host",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND atual IS TRUE"
						);
						
						if($_SERVER['SERVER_NAME'] != "localhost"){
							$user_cpanel = $host[0]['user_cpanel'];
							
							if($host[0]['dominio_proprio']){
								$dominio = $host[0]['dominio_proprio'];
							} else {
								$parse = parse_url($host[0]['url']);
								$dominio = $parse['host'];
							}
							
							$_CPANEL['ACCT'] = Array(
								'user' => $user_cpanel,
								'domain_owner' => $dominio,
							);
							
							require($_SYSTEM['SITE']['cpanel-xmlapi-path-3'].'b2make-xmlapi/cpanel-domain-mobile-add.php');
							
							banco_update
							(
								"url_mobile='".'m.'.$dominio."'",
								"host",
								"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
								." AND atual IS TRUE"
							);
							
							if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
								'manual' => true,
								'host' => $_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-host'],
								'user' => $_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-user'],
								'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
							));
							
							if($_CONEXAO_FTP){
								$htaccess = '<IfModule mod_headers.c>
	Header set Access-Control-Allow-Origin "*"
</IfModule>';
								
								if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/files')) {
									ftp_mkdir($_CONEXAO_FTP,'files'); // create directories that do not yet exist
								}
								
								ftp_chdir($_CONEXAO_FTP,'files');
								
								$nome_file = '.htaccess';
								$tmp_file = $_SYSTEM['TMP'].'.htaccess-tmp'.session_id();
								file_put_contents($tmp_file, $htaccess);
								
								ftp_put_file($nome_file, $tmp_file);
								
								ftp_cdup($_CONEXAO_FTP);
								
								if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/'.$_SYSTEM['SITE']['ftp-b2make-store-path'])) {
									ftp_mkdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-b2make-store-path']); // create directories that do not yet exist
								}
								
								ftp_chdir($_CONEXAO_FTP,$_SYSTEM['SITE']['ftp-b2make-store-path']);
								
								ftp_put_file($nome_file, $tmp_file);
								
								unlink($tmp_file);
							}
							
							publisher_block_time(Array(
								'block_time' => 180
							));
						}
						
						banco_update
						(
							"mobile=1",
							"host",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND atual IS TRUE"
						);
						
						$_SESSION[$_SYSTEM['ID']."host_mobile"] = true;
					} else {
						banco_update
						(
							"mobile=NULL",
							"host",
							"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
							." AND atual IS TRUE"
						);
						
						$_SESSION[$_SYSTEM['ID']."host_mobile"] = false;
					}
				}
			}
			
			if($campo_nome == 'nome'){
				if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){
					if(!$_REQUEST[$campo_nome]){
						$id = $_ESERVICE['minha-loja-id'];
					} else {
						$id = $_REQUEST[$campo_nome];
					}
					
					$id_old = $campos_antes['id'];
					
					$id = criar_identificador($id,$usuario['id_loja']);
					$editar[$campo_tabela][] = "id='" . $id . "'";
					
					if($id != $id_old){
						$path = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'stores'.$_SYSTEM['SEPARADOR'];
						
						$oldname = $path . $id_old;
						$newname = $path . $id;
						
						rename($oldname, $newname);
						$_SESSION[$_SYSTEM['ID']."pdw-user-path"] = false;
					}
					
					$loja_publicar_configuracoes_paginas = true;
				}
			}
			
			if($campo_nome == 'url_continuar_comprando'){
				if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){ $url_continuar_comprando = true; }
			}
			
			if($campo_nome == 'mobile_redirect_server'){
				if($campos_antes[$campo_nome] != $_REQUEST[$campo_nome]){
					if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
						'manual' => true,
						'host' => $_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-host'],
						'user' => $_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-user'],
						'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
					));
					
					if($_CONEXAO_FTP){
						$tmp_file = $_SYSTEM['TMP'].'httacces-tmp'.session_id();
						$modelo = modelo_abrir($_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'projeto'.$_SYSTEM['SEPARADOR'].'template.htaccess.txt');
						
						if(ftp_get_file('.htaccess',$tmp_file)) {
							$htaccess = file_get_contents($tmp_file);
							$htaccess_exists = true;
							unlink($tmp_file);
						}
						
						if($_REQUEST[$campo_nome]){
							if(preg_match('/'.preg_quote('### b2make_htaccess ').'/i', $htaccess) != 0){
								$htaccess = modelo_tag_in($htaccess,'### b2make_htaccess <','### b2make_htaccess >','');
							}
							
							$host = banco_select_name
							(
								banco_campos_virgulas(Array(
									'url_mobile',
									'https',
									'url',
								))
								,
								"host",
								"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
								." AND atual IS TRUE"
							);
							
							$hostname_desktop = preg_replace("(^https?://)", "", rtrim($host[0]['url'],'/'));
							$url_mobile = 'http'.($host[0]['https'] ? 's' : '').'://'.rtrim($host[0]['url_mobile'],'/');
							
							$modelo = modelo_var_troca($modelo,"#hostname_desktop#",$hostname_desktop);
							$modelo = modelo_var_troca($modelo,"#url_mobile#",$url_mobile);
							
							if($htaccess_exists){
								$htaccess = $modelo . "\n" . $htaccess;
							} else {
								$htaccess .= $modelo;
							}
						} else {
							$htaccess = modelo_tag_in($htaccess,'### b2make_htaccess <','### b2make_htaccess >','');
						}
						
						$htaccess = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $htaccess);
						
						$nome_file = '.htaccess';
						$tmp_file = $_SYSTEM['TMP'].'httacces-2-tmp'.session_id();
						file_put_contents($tmp_file, $htaccess);
						
						ftp_put_file($nome_file, $tmp_file);
						
						unlink($tmp_file);
					}
				}
			}
		} else {
			if($campo_nome == 'paypal_app_installed'){
				if(
					$_REQUEST['paypal-app-code'] &&
					$_REQUEST['paypal-app-secret'] &&
					$_REQUEST['paypal-app-sandbox-code'] &&
					$_REQUEST['paypal-app-sandbox-secret']
				){
					if($_REQUEST['paypal-app-code'] && $_REQUEST['paypal-app-secret']){
						$ch = curl_init();
						$clientId = $_REQUEST['paypal-app-code'];
						$secret = $_REQUEST['paypal-app-secret'];

						curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/oauth2/token");
						curl_setopt($ch, CURLOPT_HEADER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
						curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Accept: application/json',
						));
						curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

						$result = curl_exec($ch);

						if(empty($result)) $_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Live devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.";
						else
						{
							$json = json_decode($result);
							
							if($json->error){
								$_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Live devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.";
							} else {
								$access_token = $json->access_token;
								$expires_in = $json->expires_in;
								
								$editar[$campo_tabela][] = "paypal_app_code='" . $clientId . "'";
								$editar[$campo_tabela][] = "paypal_app_secret='" . $secret . "'";
								$editar[$campo_tabela][] = "paypal_app_token='" . $access_token . "'";
								$editar[$campo_tabela][] = "paypal_app_token_time='" . time() . "'";
								$editar[$campo_tabela][] = "paypal_app_expires_in='" . $expires_in . "'";
								
								$ppplus_live_installed = true;
							}
						}

						curl_close($ch);
						
						if($ppplus_live_installed){
							$obj['url'] = $_B2MAKE_URL.'e-services/ppplus-webhooks/live/'.$usuario['pub_id'].'/';
							$obj['event_types'] = Array(
								Array('name' => 'PAYMENT.SALE.COMPLETED'),
								Array('name' => 'PAYMENT.SALE.DENIED'),
								Array('name' => 'PAYMENT.SALE.PENDING'),
								Array('name' => 'PAYMENT.SALE.REFUNDED'),
								Array('name' => 'RISK.DISPUTE.CREATED'),
								Array('name' => 'CUSTOMER.DISPUTE.CREATED'),
							);
							
							$json = json_encode($obj);
							
							$ch = curl_init();

							curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/notifications/webhooks");
							curl_setopt($ch, CURLOPT_HEADER, false);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array(
								'Content-Type: application/json',
								'Authorization: Bearer '.$access_token,
							));
							curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

							$result = curl_exec($ch);
							
							if(empty($result)) $_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Live Notificador (WebHooks) devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.";
							else
							{
								$json = json_decode($result);
								
								if($json->error){
									$_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Live Notificador (WebHooks) devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.";
								} else {
									$webhook_id = $json->id;
									
									$editar[$campo_tabela][] = "paypal_app_webhook_id='" . $webhook_id . "'";
									
									$ppplus_live_webhook_installed = true;
								}
							}

							curl_close($ch);
						}
					} else {
						$_ALERTA_PROBLEMA = '&Eacute; obrigat&oacute;rio preencher o campo Client ID e o campo Secret no modo LIVE para poder instalar o aplicativo PayPal Plus.';
					}
					
					if($_REQUEST['paypal-app-sandbox-code'] && $_REQUEST['paypal-app-sandbox-secret']){
						$ch = curl_init();
						$clientId = $_REQUEST['paypal-app-sandbox-code'];
						$secret = $_REQUEST['paypal-app-sandbox-secret'];

						curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/oauth2/token");
						curl_setopt($ch, CURLOPT_HEADER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
						curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Accept: application/json',
						));
						curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

						$result = curl_exec($ch);

						if(empty($result)) $_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Sandbox devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.";
						else
						{
							$json = json_decode($result);
							
							if($json->error){
								$_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Sandbox devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.";
							} else {
								$access_token = $json->access_token;
								$expires_in = $json->expires_in;
								
								$editar[$campo_tabela][] = "paypal_app_sandbox_code='" . $clientId . "'";
								$editar[$campo_tabela][] = "paypal_app_sandbox_secret='" . $secret . "'";
								$editar[$campo_tabela][] = "paypal_app_sandbox_token='" . $access_token . "'";
								$editar[$campo_tabela][] = "paypal_app_sandbox_token_time='" . time() . "'";
								$editar[$campo_tabela][] = "paypal_app_sandbox_expires_in='" . $expires_in . "'";
								
								$ppplus_sandbox_installed = true;
							}
						}

						curl_close($ch);
						
						if($ppplus_sandbox_installed){
							$obj['url'] = $_B2MAKE_URL.'e-services/ppplus-webhooks/sandbox/'.$usuario['pub_id'].'/';
							$obj['event_types'] = Array(
								Array('name' => 'PAYMENT.SALE.COMPLETED'),
								Array('name' => 'PAYMENT.SALE.DENIED'),
								Array('name' => 'PAYMENT.SALE.PENDING'),
								Array('name' => 'PAYMENT.SALE.REFUNDED'),
								Array('name' => 'RISK.DISPUTE.CREATED'),
								Array('name' => 'CUSTOMER.DISPUTE.CREATED'),
							);
							
							$json = json_encode($obj);
							
							$ch = curl_init();

							curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/notifications/webhooks");
							curl_setopt($ch, CURLOPT_HEADER, false);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array(
								'Content-Type: application/json',
								'Authorization: Bearer '.$access_token,
							));
							curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

							$result = curl_exec($ch);
							
							if(empty($result)) $_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Sandbox Notificador (WebHooks) devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.";
							else
							{
								$json = json_decode($result);
								
								if($json->error){
									$_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel instalar o PayPal Plus Sandbox Notificador (WebHooks) devido o servidor do PayPal retornar o seguinte erro: PayPal Plus: <b>".$json->error." - ".$json->error_description."</b>.";
								} else {
									$webhook_id = $json->id;
									
									$editar[$campo_tabela][] = "paypal_app_sandbox_webhook_id='" . $webhook_id . "'";
									
									$ppplus_sandbox_webhook_installed = true;
								}
							}

							curl_close($ch);
						}
					} else {
						$_ALERTA_PROBLEMA = '&Eacute; obrigat&oacute;rio preencher o campo Client ID e o campo Secret no modo SANDBOX para poder instalar o aplicativo PayPal Plus.';
					}
				}
				
				if(
					$ppplus_sandbox_webhook_installed && 
					$ppplus_live_webhook_installed && 
					$ppplus_live_installed && 
					$ppplus_sandbox_installed
				){
					$editar[$campo_tabela][] = "paypal_app_active=1";
					$editar[$campo_tabela][] = "paypal_app_installed=1";
				}
			}
		}
	}
	
	if($_REQUEST['paypal-app-desinstall']){
		$id_loja = $usuario['id_loja'];
		
		$loja = banco_select_name
		(
			banco_campos_virgulas(Array(
				'paypal_app_code',
				'paypal_app_secret',
				'paypal_app_token',
				'paypal_app_token_time',
				'paypal_app_expires_in',
				'paypal_app_webhook_id',
				'paypal_app_sandbox_code',
				'paypal_app_sandbox_secret',
				'paypal_app_sandbox_token',
				'paypal_app_sandbox_token_time',
				'paypal_app_sandbox_expires_in',
				'paypal_app_sandbox_webhook_id',
			))
			,
			$campo_tabela,
			$campo_tabela_extra
		);
		
		if($loja[0]['paypal_app_webhook_id']){
			$gerar_token = false;
			
			if($loja[0]['paypal_app_token']){
				if((int)$loja[0]['paypal_app_token_time']+(int)$loja[0]['paypal_app_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
			
			if($gerar_token){
				$retorno = eservice_paypal_plus_token_generate(Array(
					'paypal_app_code' => $loja[0]['paypal_app_code'],
					'paypal_app_secret' => $loja[0]['paypal_app_secret'],
					'paypal_app_live' => true,
					'id_loja' => $id_loja,
				));
				
				if(!$retorno['erro']){
					$loja[0]['paypal_app_token'] = $retorno['token'];
				}
			}
			
			$token = $loja[0]['paypal_app_token'];
			$id = $loja[0]['paypal_app_webhook_id'];
			
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/notifications/webhooks/".$id);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Authorization: Bearer '.$token,
			));

			$result = curl_exec($ch);

			curl_close($ch);
		}
		
		if($loja[0]['paypal_app_sandbox_webhook_id']){
			$gerar_token = false;
			
			if($loja[0]['paypal_app_sandbox_token']){
				if((int)$loja[0]['paypal_app_sandbox_token_time']+(int)$loja[0]['paypal_app_sandbox_expires_in'] < time()){
					$gerar_token = true;
				}
			} else {
				$gerar_token = true;
			}
			
			if($gerar_token){
				$retorno = eservice_paypal_plus_token_generate(Array(
					'paypal_app_code' => $loja[0]['paypal_app_sandbox_code'],
					'paypal_app_secret' => $loja[0]['paypal_app_sandbox_secret'],
					'paypal_app_live' => false,
					'id_loja' => $id_loja,
				));
				
				if(!$retorno['erro']){
					$loja[0]['paypal_app_sandbox_token'] = $retorno['token'];
				}
			}
			
			$token = $loja[0]['paypal_app_sandbox_token'];
			$id = $loja[0]['paypal_app_sandbox_webhook_id'];
			
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/notifications/webhooks/".$id);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Authorization: Bearer '.$token,
			));

			$result = curl_exec($ch);

			curl_close($ch);
		}
		
		$editar[$campo_tabela][] = "paypal_app_code=NULL";
		$editar[$campo_tabela][] = "paypal_app_secret=NULL";
		$editar[$campo_tabela][] = "paypal_app_token=NULL";
		$editar[$campo_tabela][] = "paypal_app_token_time=NULL";
		$editar[$campo_tabela][] = "paypal_app_expires_in=NULL";
		$editar[$campo_tabela][] = "paypal_app_webhook_id=NULL";
		$editar[$campo_tabela][] = "paypal_app_sandbox_code=NULL";
		$editar[$campo_tabela][] = "paypal_app_sandbox_secret=NULL";
		$editar[$campo_tabela][] = "paypal_app_sandbox_token=NULL";
		$editar[$campo_tabela][] = "paypal_app_sandbox_token_time=NULL";
		$editar[$campo_tabela][] = "paypal_app_sandbox_expires_in=NULL";
		$editar[$campo_tabela][] = "paypal_app_sandbox_webhook_id=NULL";
		$editar[$campo_tabela][] = "paypal_app_active=NULL";
		$editar[$campo_tabela][] = "paypal_app_installed=NULL";
		$editar[$campo_tabela][] = "paypal_app_live=NULL";
		$editar[$campo_tabela][] = "paypal_reference_installed=NULL";
		$editar[$campo_tabela][] = "paypal_reference_id=NULL";
		$editar[$campo_tabela][] = "paypal_reference_cancel_url=NULL";
	}
	
	if($_REQUEST['paypal-heference-install'] == 'sim'){
		$id_loja = $usuario['id_loja'];
		
		paypal_reference_token(Array(
			
		));
	}
	
	if($_REQUEST['paypal-plus-inactive'] == 'nao' && !$campos_extra['paypal_plus_inactive']){
		$editar[$campo_tabela][] = "paypal_plus_inactive=1";
	}
	
	if($_REQUEST['paypal-plus-inactive'] == 'sim' && $campos_extra['paypal_plus_inactive']){
		$editar[$campo_tabela][] = "paypal_plus_inactive=NULL";
	}
	
	if($_REQUEST['paypal-app-active'] == 'sim' && !$campos_extra['paypal_app_active']){
		$editar[$campo_tabela][] = "paypal_app_active=1";
	}
	
	if($_REQUEST['paypal-app-active'] == 'nao' && $campos_extra['paypal_app_active']){
		$editar[$campo_tabela][] = "paypal_app_active=NULL";
	}
	
	if($_REQUEST['paypal-app-live'] == 'sim' && !$campos_extra['paypal_app_live']){
		$editar[$campo_tabela][] = "paypal_app_live=1";
	}
	
	if($_REQUEST['paypal-app-live'] == 'nao' && $campos_extra['paypal_app_live']){
		$editar[$campo_tabela][] = "paypal_app_live=NULL";
	}
	
	if(!$campos_antes['versao'])$campos_antes['versao'] = 0;
	
	(int)$campos_antes['versao']++;
	$editar[$campo_tabela][] = "versao='" . $campos_antes['versao'] . "'";
	
	if($publish)
	foreach($publish as $key => $val){
		switch($key){
			case 'javascript_global':
			case 'css_global':
				if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
					'manual' => true,
					'host' => $_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-host'],
					'user' => $_SESSION[$_SYSTEM['ID']."b2make-site"]['ftp-site-user'],
					'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
				));
				
				if($_CONEXAO_FTP){					
					if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-files-user'].':'.$_SYSTEM['SITE']['ftp-files-pass'].'@'.$_SYSTEM['SITE']['ftp-files-host'].'/files')) {
						ftp_mkdir($_CONEXAO_FTP,'files'); // create directories that do not yet exist
					}
					
					ftp_chdir($_CONEXAO_FTP,'files');
					
					$javascript_global = stripslashes($_REQUEST['javascript_global']);
					$css_global = stripslashes($_REQUEST['css_global']);
					
					$nome_file = 'global.js';
					$tmp_file = $_SYSTEM['TMP'].'global.js-tmp'.session_id();
					file_put_contents($tmp_file, $javascript_global);
					
					ftp_put_file($nome_file, $tmp_file);
					
					unlink($tmp_file);
					
					$nome_file = 'global.css';
					$tmp_file = $_SYSTEM['TMP'].'global.css-tmp'.session_id();
					file_put_contents($tmp_file, $css_global);
					
					ftp_put_file($nome_file, $tmp_file);
					
					unlink($tmp_file);
					
					if(!$campos_extra['global_version'])$campos_extra['global_version'] = 0;
					
					(int)$campos_extra['global_version']++;
					
					$editar['host'][] = "javascript_global_published=1";
					$editar['host'][] = "css_global_published=1";
					$editar['host'][] = "global_version='".$campos_extra['global_version']."'";
				}
			break;
		}
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
	
	$editar_sql['host'] = banco_campos_virgulas($editar['host']);
	
	if($editar_sql['host']){
		banco_update
		(
			$editar_sql['host'],
			'host',
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
	}
	
	$editar = false;$editar_sql = false;
		
	if($publish)
	foreach($publish as $key => $val){
		switch($key){
			case 'google_analytic':
			case 'google_site_verification':
			case 'meta_global':
			case 'javascript_global':
			case 'css_global':
			case 'body_global':
				publisher_all_pages();
			break;
		}
	}
	
	if($_REQUEST['publisher_all_pages']){
		publisher_all_pages();
		publisher_sitemaps();
		config_publish_all_library();
	}
	
	if(
		$url_continuar_comprando ||
		$loja_publicar_configuracoes_paginas
	){
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$url = $host[0]['url'] . 'platform/atualizar';
		curl_post_async($url);
	}
	
	if($_FILES['logomarca']['size'] != 0)		{guardar_arquivo($_FILES['logomarca'],'imagem','logomarca',$usuario['id_loja']);}
	if($_ALERTA_PROBLEMA){
		$_ALERTA = $_ALERTA_PROBLEMA;
	} else if($_CPANEL_ERRORS) {
		$_ALERTA = '<p>Houveram alguns problemas com o sistema gerenciador da sua hospedagem!</p><p>INFORMAÇÃO TÉCNICA: <ul>'.$_CPANEL_ERRORS.'</ul></p>';
	} else {
		$_ALERTA = 'Campos atualizados com sucesso!';
	}
	
	// ======================================================================================
	
	$_SESSION[$_SYSTEM['ID']."b2make-loja-atual"] = false;
	
	if($_CONEXAO_FTP)ftp_fechar_conexao();
	banco_fechar_conexao();
	
	redirecionar('config/?sessao='.$_SESSION[$_SYSTEM['ID']."config-sessao-atual"]);
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_ESERVICE;
	
	$caminho_fisico 			=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."stores".$_SYSTEM['SEPARADOR'];
	$caminho_internet 			= 	"files/stores/";
	
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
					$uploaded['type'] == mime_types("swf") ||
					$uploaded['type'] == mime_types("gif")
				){
					$cadastrar = true;
				}
			break;
			case 'musica':
				if
				(
					$uploaded['type'] == mime_types("mp3") ||
					$uploaded['type'] == mime_types("mp3_2")
				){
					$cadastrar = true;
				}
			break;
			case 'video':
				if
				(
					$uploaded['type'] == mime_types("flv") ||
					$uploaded['type'] == mime_types("mp4")
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
		} else if
		(
			$uploaded['type'] == mime_types("swf")
		){
			$extensao = "swf";
		} else if
		(
			$uploaded['type'] == mime_types("mp3") ||
			$uploaded['type'] == mime_types("mp3_2")
		){
			$extensao = "mp3";
		} else if
		(
			$uploaded['type'] == mime_types("flv")
		){
			$extensao = "flv";
		}  else if
		(
			$uploaded['type'] == mime_types("mp4")
		){
			$extensao = "mp4";
		} 
		
		$nome_arquivo = $campo . $id_tabela . "." . $extensao;
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			$original = $caminho_fisico . $nome_arquivo;
			
			if($_ESERVICE['store-logomarca-width']) $new_width = $_ESERVICE['store-logomarca-width'];
			if($_ESERVICE['store-logomarca-height']) $new_height = $_ESERVICE['store-logomarca-height'];
			if($_ESERVICE['store-logomarca-recorte-y']) $_RESIZE_IMAGE_Y_ZERO = true;
			
			resize_image($original, $original, $new_width, $new_height,false,false,true);
			
			banco_update
			(
				$campo."='".$caminho_internet.$nome_arquivo."'",
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id_tabela."'"
			);
		}
	}
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

function atualizar_areas_global(){
	global $_SYSTEM;
	global $_ALERTA;
	
	publisher_all_areas_global();
	
	$_ALERTA = 'Áreas atualizadas com sucesso!';
	
	$saida = editar('ver');$_SESSION[$_SYSTEM['ID'].'active_tab'] = false;
	
	return $saida;
}

function atualizar_dominio_paginas(){
	global $_SYSTEM;
	global $_ALERTA;
	
	$dominio_antes = $_REQUEST['dominio_antes'];
	$dominio_depois = $_REQUEST['dominio_depois'];
	
	if($dominio_antes && $dominio_depois){
		$dominio_antes = preg_quote($dominio_antes);
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_site',
				'html',
				'html_mobile',
			))
			,
			"site",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		if($resultado)
		foreach($resultado as $res){
			$html = $res['html'];
			$html_mobile = $res['html_mobile'];
			
			$html = preg_replace('/\/\/'.$dominio_antes.'/i', '//'.$dominio_depois, $html);
			$html_mobile = preg_replace('/\/\/'.$dominio_antes.'/i', '//'.$dominio_depois, $html_mobile);
			
			banco_update
			(
				"html='".addslashes($html)."',".
				"html_mobile='".addslashes($html_mobile)."'",
				"site",
				"WHERE id_site='".$res['id_site']."'"
				." AND id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
		}
		
		publisher_all_pages();
		
		$_ALERTA = 'Domínios das páginas atualizadas com sucesso!';
	}
	
	$saida = editar('ver');$_SESSION[$_SYSTEM['ID'].'active_tab'] = false;
	
	return $saida;
}

function testes(){
	global $_SYSTEM;
	global $_CPANEL;
	
}

function paypal_reference_token($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_B2MAKE_URL;
	global $_PROJETO;
	global $_ALERTA_PROBLEMA;
	global $_SYSTEM;
	
	$paypal_app_live = true;
	
	$retorno = b2make_paypal_plus_token_generate(Array(
		'paypal_app_live' => $paypal_app_live,
	));
	
	if($retorno['erro']){
		$_ALERTA_PROBLEMA = $retorno['erro_msg'];
		return;
	} else {
		$token = $retorno['token'];
	}
	
	$obj['description'] = 'Autorizo a B2make efetuar a cobrança de '.preparar_float_4_texto($_PROJETO['PAGSEGURO_APP_TAXA'].'% por transação dos valores de venda dos serviços/produtos da minha loja na B2make.');
	$obj['payer'] = Array(
		'payment_method' => 'PAYPAL',
	);
	$obj['plan'] = Array(
		'type' => 'MERCHANT_INITIATED_BILLING',
		'merchant_preferences' => Array(
			'cancel_url' => $_B2MAKE_URL.'config/?opcao=paypal-reference&opcao2=cancel',
			'return_url' => $_B2MAKE_URL.'config/?opcao=paypal-reference&opcao2=return',
			'accepted_pymt_type' => 'Instant',
		),
	);
	
	$json = json_encode($obj);
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, "https://api.".($paypal_app_live ? "" : "sandbox.")."paypal.com/v1/billing-agreements/agreement-tokens");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer '.$token,
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

	$result = curl_exec($ch);

	if(empty($result)) $_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel habilitar o reference do PayPal devido o servidor do PayPal retornar um resultado vazio. Favor tentar novamente mais tarde.";
	else
	{
		$json = json_decode($result);
		
		if($json->name){
			$_ALERTA_PROBLEMA = "N&atilde;o foi poss&iacute;vel habilitar o reference do PayPal devido o servidor do PayPal retornar o seguinte erro: PayPal: <b>[".$json->name."] - [".$json->details[0]->name."] - ".$json->details[0]->message."</b>.";
		} else {
			$href = $json->links[0]->href;
			$token_ba_id = $json->token_id;
			
			$_SESSION[$_SYSTEM['ID']."paypal_token_ba_id"] = $token_ba_id;
			
			redirecionar($href,true);
		}
	}

	curl_close($ch);
}

function paypal_reference_id($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_B2MAKE_URL;
	global $_PROJETO;
	global $_ALERTA;
	global $_SYSTEM;
	
	$paypal_app_live = true;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$campo_tabela = "loja";
	$campo_tabela_extra = "WHERE id_loja='".$usuario['id_loja']."'"
	." AND status='A'";
	
	if($_REQUEST['opcao2'] == 'cancel'){
		$_ALERTA = 'Foi optado pelo cancelamento da habilita&ccedil;&atilde;o do PayPal Reference. Todo procedimento anterior foi cancelado.';
		
		return editar();
	} else if($_REQUEST['opcao2'] == 'return'){
		if($_SESSION[$_SYSTEM['ID']."paypal_token_ba_id"] != $_REQUEST['ba_token']){
			$_ALERTA = 'O c&oacute;digo de confer&ecirc;ncia token BA armazenado &eacute; diferente do retornado pelo PayPal. &Eacute; necess&aacute;rio refazer a opera&ccedil;&atilde;o.';
			
			return editar();
		}
		
		$retorno = b2make_paypal_plus_token_generate(Array(
			'paypal_app_live' => $paypal_app_live,
		));
		
		if($retorno['erro']){
			$_ALERTA = $retorno['erro_msg'];
			return;
		} else {
			$token = $retorno['token'];
		}
		
		$ba_token = $_SESSION[$_SYSTEM['ID']."paypal_token_ba_id"];
		
		$obj['agreement_token'] = $ba_token;
		
		$json = json_encode($obj);
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "https://api.".($paypal_app_live ? "" : "sandbox.")."paypal.com/v1/billing-agreements/".$ba_token."/agreements");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$token,
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		$result = curl_exec($ch);

		if(empty($result)) $_ALERTA = "N&atilde;o foi poss&iacute;vel habilitar o reference do PayPal devido o servidor do PayPal retornar um resultado vazio na finaliza&ccedil;&atilde;o da habilita&ccedil;&atilde;o do PayPal Reference. Favor tentar novamente mais tarde.";
		else
		{
			$json = json_decode($result);
			
			if($json->error){
				$_ALERTA = "N&atilde;o foi poss&iacute;vel finalizar a habilita&ccedil;&atilde;o do PayPal Reference devido o servidor do PayPal retornar o seguinte erro: PayPal: <b>".$json->error." - ".$json->error_description."</b>.";
			} else {
				$paypal_reference_cancel_url = $json->links[0]->href;
				$paypal_reference_id = $json->id;
				
				$campo_nome = "paypal_reference_installed"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
				$campo_nome = "paypal_reference_id"; $campo_valor = $paypal_reference_id; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "paypal_reference_cancel_url"; $campo_valor = $paypal_reference_cancel_url; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				
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
				
				$_ALERTA = 'PayPal Reference habilitado com sucesso.';
				
				$habilitado = true;
			}
		}

		curl_close($ch);
	}
	
	if($habilitado){
		redirecionar('config/?sessao='.$_SESSION[$_SYSTEM['ID']."config-sessao-atual"]);
	}
	
	return editar();
}

function upgrade(){
	/* $loja_bd = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'id_usuario',
		))
		,
		"loja",
		""
	);
	$usuario_bd = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_usuario',
			'id_usuario_pai',
			'nome',
			'ultimo_nome',
			'email',
			'senha',
			'telefone',
			'cpf',
			'cnpj',
			'cnpj_selecionado',
			'versao_voucher',
			'ppp_remembered_card_hash',
			'data_cadastro',
			'data_login',
			'status',
		))
		,
		"usuario",
		"WHERE id_loja_usuarios IS NULL"
	);
	
	$count = 0;
	
	if($usuario_bd)
	foreach($usuario_bd as $usuario){
		$id_usuario_pai = ($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario']);
		$id_loja = false;
		
		if($loja_bd)
		foreach($loja_bd as $loja){
			if($id_usuario_pai == $loja['id_usuario']){
				$id_loja = $loja['id_loja'];
				break;
			}
		}
		
		if($id_loja){
			$campos = null;
			
			$campo_nome = "id_loja"; $campo_valor = $id_loja; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = $usuario['status']; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			if($usuario['nome']){$campo_nome = "nome"; $campo_valor = $usuario['nome']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['ultimo_nome']){$campo_nome = "ultimo_nome"; $campo_valor = $usuario['ultimo_nome']; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['email']){$campo_nome = "email"; $campo_valor = $usuario['email']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['senha']){$campo_nome = "senha"; $campo_valor = $usuario['senha']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['telefone']){$campo_nome = "telefone"; $campo_valor = $usuario['telefone']; 															$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['cpf']){$campo_nome = "cpf"; $campo_valor = $usuario['cpf']; 																			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['cnpj']){$campo_nome = "cnpj"; $campo_valor = $usuario['cnpj']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['versao_voucher']){$campo_nome = "versao_voucher"; $campo_valor = $usuario['versao_voucher']; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['data_cadastro']){$campo_nome = "data_cadastro"; $campo_valor = $usuario['data_cadastro']; 												$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['data_login']){$campo_nome = "data_login"; $campo_valor = $usuario['data_login']; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['ppp_remembered_card_hash']){$campo_nome = "ppp_remembered_card_hash"; $campo_valor = $usuario['ppp_remembered_card_hash']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['cnpj_selecionado']){$campo_nome = "cnpj_selecionado"; $campo_valor = '1'; 																$campos[] = Array($campo_nome,$campo_valor,true);}
			
			banco_insert_name
			(
				$campos,
				"loja_usuarios"
			);
			
			$id_loja_usuarios = banco_last_id();
			
			banco_update
			(
				"id_loja_usuarios='".$id_loja_usuarios."'",
				"usuario",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
			);
			
			$count++;
			
			echo $usuario['nome'] . ' ' . $usuario['email'] . '<br>';
		}
	}
	
	echo 'Done! Total: '.$count; exit; */
}

function upgrade2(){
	/* $pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
			'id_loja',
		))
		,
		"pedidos",
		""
	);
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
		))
		,
		"loja",
		""
	);
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
			'id_usuario',
			'pedido_atual',
		))
		,
		"usuario_pedidos",
		""
	);
	$usuario_bd = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_usuario',
			'id_loja_usuarios',
			'nome',
			'ultimo_nome',
			'email',
			'senha',
			'telefone',
			'cpf',
			'cnpj',
			'cnpj_selecionado',
			'versao_voucher',
			'ppp_remembered_card_hash',
			'data_cadastro',
			'data_login',
			'status',
		))
		,
		"usuario",
		""
	);
	
	$loja_usuarios = Array();
	$loja_usuarios_atualizados = Array();
	
	if($pedidos)
	foreach($pedidos as $pedido){
		$achou_loja = false;
		
		if($loja)
		foreach($loja as $loja_dado){
			if($pedido['id_loja'] == $loja_dado['id_loja']){
				$achou_loja = true;
			}
		}
		
		if(!$achou_loja){
			continue;
		}
		
		if($usuario_pedidos)
		foreach($usuario_pedidos as $usuario_pedido){
			if($pedido['id_pedidos'] == $usuario_pedido['id_pedidos']){
				if($usuario_pedido['id_usuario']){
					if(!$loja_usuarios[$pedido['id_loja']]){
						$loja_usuarios[$pedido['id_loja']] = Array();
					}
					
					if(!$loja_usuarios[$pedido['id_loja']][$usuario_pedido['id_usuario']]){
						if($usuario_bd)
						foreach($usuario_bd as $usuario){
							if($usuario['id_usuario'] == $usuario_pedido['id_usuario']){
								if(!$usuario['id_loja_usuarios']){
									$campos = null;
									
									$campo_nome = "id_loja"; $campo_valor = $pedido['id_loja']; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									$campo_nome = "status"; $campo_valor = $usuario['status']; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
									
									if($usuario['nome']){$campo_nome = "nome"; $campo_valor = $usuario['nome']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['ultimo_nome']){$campo_nome = "ultimo_nome"; $campo_valor = $usuario['ultimo_nome']; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['email']){$campo_nome = "email"; $campo_valor = $usuario['email']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['senha']){$campo_nome = "senha"; $campo_valor = $usuario['senha']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['telefone']){$campo_nome = "telefone"; $campo_valor = $usuario['telefone']; 															$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['cpf']){$campo_nome = "cpf"; $campo_valor = $usuario['cpf']; 																			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['cnpj']){$campo_nome = "cnpj"; $campo_valor = $usuario['cnpj']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['versao_voucher']){$campo_nome = "versao_voucher"; $campo_valor = $usuario['versao_voucher']; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['data_cadastro']){$campo_nome = "data_cadastro"; $campo_valor = $usuario['data_cadastro']; 												$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['data_login']){$campo_nome = "data_login"; $campo_valor = $usuario['data_login']; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['ppp_remembered_card_hash']){$campo_nome = "ppp_remembered_card_hash"; $campo_valor = $usuario['ppp_remembered_card_hash']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
									if($usuario['cnpj_selecionado']){$campo_nome = "cnpj_selecionado"; $campo_valor = '1'; 																$campos[] = Array($campo_nome,$campo_valor,true);}
									
									banco_insert_name
									(
										$campos,
										"loja_usuarios"
									);
									
									$loja_usuarios[$pedido['id_loja']][$usuario_pedido['id_usuario']] = banco_last_id();
									
									if(!$loja_usuarios_atualizados[$usuario_pedido['id_usuario']]){
										banco_update
										(
											"id_loja_usuarios='".$loja_usuarios[$pedido['id_loja']][$usuario_pedido['id_usuario']]."'",
											"usuario",
											"WHERE id_usuario='".$usuario['id_usuario']."'"
										);
										$loja_usuarios_atualizados[$usuario_pedido['id_usuario']] = true;
									}
								} else {
									$loja_usuarios[$pedido['id_loja']][$usuario_pedido['id_usuario']] = $usuario['id_loja_usuarios'];
								}
								
								break;
							}
						}
					}
					
					$id_loja_usuarios = $loja_usuarios[$pedido['id_loja']][$usuario_pedido['id_usuario']];
					
					$campos = null;
					
					$campo_nome = "id_loja_usuarios"; $campo_valor = $id_loja_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_loja"; $campo_valor = $pedido['id_loja']; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id_pedidos"; $campo_valor = $pedido['id_pedidos']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					if($usuario_pedido['pedido_atual']){$campo_nome = "pedido_atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);}
					
					banco_insert_name
					(
						$campos,
						"loja_usuarios_pedidos"
					);
					
					break;
				}
			}
		}
	}
	
	echo 'Done!'; */
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
	global $_BANCO_PREFIXO;
	global $_LISTA;
	global $_LISTA_2;
	
	return $saida;
}

function start(){	
	global $_LOCAL_ID;
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_HTML;
	global $_VARIAVEIS_JS;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if($_REQUEST['bm_loja_pagina_mestre_reinstall'])loja_pagina_mestre_reinstall();
	
	if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'atualizar-areas-global':				$saida = atualizar_areas_global();break;
			case 'atualizar-dominio-paginas':			$saida = atualizar_dominio_paginas();break;
			case 'loja-pagina-mestre-reinstall':		$saida = loja_pagina_mestre_reinstall();break;
			case 'testes':								$saida = testes();break;
			case 'paypal-reference':					$saida = paypal_reference_id();break;
			case 'pagseguro-autorizar':					$saida = pagseguro_autorizar();break;
			case 'pagseguro-autorizar-return':			$saida = pagseguro_autorizar_return();break;
			case 'editar':								$saida = (operacao('editar') ? editar() : editar('ver'));break;
			case 'editar_base':							$saida = (operacao('editar') ? editar_base() : editar('ver'));break;
			case 'remover_item':						$saida = (operacao('editar') ? remover_item() : editar('ver'));break;
			case 'ftp-manual-password':					$saida = site_ftp_manual_password();break;
			case 'upgrade':								$saida = upgrade();break;
			default: 	
				if(!$_REQUEST['sessao']){
					if(!$_SESSION[$_SYSTEM['ID']."config-sessao-atual"]){
						$_SESSION[$_SYSTEM['ID']."config-sessao-atual"] = 'site';
					}
					redirecionar('config/?sessao='.$_SESSION[$_SYSTEM['ID']."config-sessao-atual"]);
				}
				$saida = editar('ver');$_SESSION[$_SYSTEM['ID'].'active_tab'] = false;
		}
		
		
		$_VARIAVEIS_JS['active_tab'] = $_SESSION[$_SYSTEM['ID'].'active_tab'];
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

start();

?>