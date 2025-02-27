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

$_VERSAO_MODULO_INCLUDE				=	'1.0.5';

function seguranca_alerta($n){
	switch ($n) 
	{
		case 1:
			$a = "Local restrito!\\n\\nNota: é necessário usuário e senha para ter acesso!";
			break;
		default:
			$a = "Houve algum problema!";
	}
			
	echo
	"
	<script language=JavaScript>
	alert(\"".$a."\");
	</script>
	";
}

function seguranca_redirecionar_old($url){
	echo
	"
	<script language=JavaScript>
	window.open(\"".$url."\", \"_self\");
	</script>
	";
}

function seguranca_redirecionar($local = false,$sem_root = false){
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

function seguranca_iniciar_vars(){
	global $_SYSTEM;
	
	$_SESSION[$_SYSTEM['ID']."usuario"] = Array(
		'id' => '0',
		'usuario' => 'anonimo',
		'nome' => 'anonimo',
	);
	
	$_SESSION[$_SYSTEM['ID']."permissao"] = false;
}

function seguranca(){
	global $_PERMISSAO;
	global $_SYSTEM;
	global $_HTML;
	global $_PROJETO;
	global $_AJAX_PAGE;
	global $_AMBIENTE_TESTE;
	global $_AMBIENTE_TESTE_USER;
	global $_AMBIENTE_TESTE_PASS;
	global $_REDIRECT_PAGE;
	
	if($_AMBIENTE_TESTE){
		if($_REQUEST['usuario'] && $_REQUEST['senha']){
			if(
				$_REQUEST['usuario'] == $_AMBIENTE_TESTE_USER &&
				$_REQUEST['senha'] == $_AMBIENTE_TESTE_PASS
			){
				$_SESSION[$_SYSTEM['ID']."ambiente_teste"] = true;
			}
		}
		if(!$_SESSION[$_SYSTEM['ID']."ambiente_teste"]){
			if($_AJAX_PAGE){
				seguranca_redirecionar($_SERVER["SCRIPT_NAME"]);
			} else {
			echo '<html>
<head>
	<title>Ambiente de Testes</title>
</head>
<body>
<h1>Acesso negado!</h1>
<p>Entre com o usuário e a senha para poder acessar:</p>
<form action="." method="post">
<label for="usuario">Usuário</label>
<input type="text" name="usuario" id="usuario">
<br>
<label for="senha">Senha</label>
<input type="password" name="senha" id="senha">
<br>
<input type="submit" name="submit" id="submit" value="Enviar">
</form>
</body>
</html>';
			}
			exit();
		}
	}
	
	if($_REQUEST["page"] == "logout"){
		session_unset();
	}
	
	if(!$_SYSTEM['ATIVO']){
		include($_SYSTEM['TEMA_PATH'] . "inativo.html");
		
		die();
	}
	
	if(!$_SESSION[$_SYSTEM['ID']."usuario"])
		seguranca_iniciar_vars();
	
	if($_PERMISSAO){
		if(!$_SESSION[$_SYSTEM['ID']."permissao"]){
			if($_SYSTEM['ROOT'])$url = preg_replace('/^\/'.str_replace('/','\/',$_SYSTEM['ROOT']).'/i', '',$_SERVER["REQUEST_URI"]); else $url = preg_replace('/^\//i', '',$_SERVER["REQUEST_URI"]);
			$_SESSION[$_SYSTEM['ID'].'logar-local'] = $url;
			seguranca_redirecionar("autenticar");
			die();
		}
	}
	
	if($_AJAX_PAGE && $_PERMISSAO){
		$_REDIRECT_PAGE = true;
		if($_SYSTEM['ROOT'])$url = preg_replace('/^\/'.str_replace('/','\/',$_SYSTEM['ROOT']).'/i', '',$_SERVER["REQUEST_URI"]); else $url = preg_replace('/^\//i', '',$_SERVER["REQUEST_URI"]);
		seguranca_redirecionar($url);
		die();
	}
	
	if(!phpAntiSqlInjection()){
		header("Location: http://" . $_SERVER["SERVER_NAME"] . "/" . $_SYSTEM['ROOT']);
		
		die();
	}
}

function phpAntiSqlInjection($debug = false){
	if($_REQUEST)
	foreach($_REQUEST as $key => $value){
		$_REQUEST[$key] = addslashes($value);
		if($debug) echo "REQUEST: ".$key . " " . $value . " DEPOIS: " . $_REQUEST[$key] . "<br />";
	}
	
	if($_POST)
	foreach($_POST as $key => $value){
		$_POST[$key] = addslashes($value);
		if($debug) echo "_POST: ".$key . " " . $value . "<br />";
	}
	
	if($_COOKIE)
	foreach($_COOKIE as $key => $value){
		$_COOKIE[$key] = addslashes($value);
		if($debug) echo "_COOKIE: ".$key . " " . $value . "<br />";
	}
	
	if($_GET)
	foreach($_GET as $key => $value){
		$_GET[$key] = addslashes($value);
		if($debug) echo "_GET: ".$key . " " . $value . "<br />";
	}
	
	if($_FILES)
	foreach($_FILES as $key => $value){
		if($value['name']){
			$_FILES[$key]['name'] = addslashes($value['name']);
			if($debug) echo $key . " " . $value['name'] . "<br />";
		}
	}
	
	return true;
}

function operacao($operacao){
	global $_SYSTEM;
	global $_LOCAL_ID;
	
	if($_SESSION[$_SYSTEM['ID']."admin"] OR $_SYSTEM['INSTALL'])return true;
	
	$modulos_operacao = $_SESSION[$_SYSTEM['ID']."modulos_operacao"];
	
	return $modulos_operacao[$_LOCAL_ID][$operacao] ? true : false;
}

function seguranca_codigo_validacao($codigo_imagem){
	if(strtolower($_SESSION['captchaText']) == strtolower(utf8_decode($codigo_imagem)))
		return true;
	else
		return false;
}

function seguranca_delay(){
	global $_SYSTEM;
	
	
}

?>