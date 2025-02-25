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

$_VERSAO_MODULO				=	'1.4.0';
$_LOCAL_ID					=	"dashboard";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_INCLUDE_LOJA				=	true;
$_INCLUDE_PUBLISHER			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	".";
$_JS_TOOLTIP_INICIO			=	true;
$_MENU_LATERAL_GESTOR		=	true;
$_INCLUDE_SITE				=	true;
$_HTML['LAYOUT']			=	$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.html";


include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

$_HTML['titulo'] 						= 	$_HTML['titulo']."DASHBOARD.";
$_HTML['variaveis']['titulo-modulo']	=	'E-Service';	

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"../includes/js/chart/Chart.bundle.min.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"../files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"../files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"../files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"css-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

// Funções do sistema

function config_publish_all_library(){
	site_library_update(Array(
		'widget' => 'formularios',
		'nao_desconectar_ftp' => true,
	));
	
	site_library_update(Array(
		'widget' => 'posts-filter',
	));
}

function atualizar_planos_banco(){
	global $_SYSTEM;
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_host',
			'plano',
		))
		,
		"host",
		""
	);
	
	$planos = $_SYSTEM['B2MAKE_PLANOS'];
	
	if($resultado)
	foreach($resultado as $res){
		$disklimit = $planos[$res['plano']]['quota'].'M';
		
		banco_update
		(
			"disklimit='".$disklimit."'",
			"host",
			"WHERE id_host='".$res['id_host']."'"
		);
	}
	
	echo 'BD Atualizado';
}

function upgrade_host_try_install_again(){
	global $_SYSTEM;
	
	$pagina = modelo_abrir($_SYSTEM['PATH'].'dashboard'.$_SYSTEM['SEPARADOR'].'instalacao.html');
	
	echo $pagina; exit;
}

function upgrade_host_install(){
	global $_SYSTEM;
	global $_CPANEL;
	global $_B2MAKE_SERVER_ALIAS;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_ALERTA;
	global $_B2MAKE_BETA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
	
	if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'user_host',
			'url',
			'ftp_site_pass',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	if(!$resultado)return;
	
	$senha = md5($senha);
	$user = $resultado[0]['user_host'];
	$url = $resultado[0]['url'];
	$ftp_site_pass = $resultado[0]['ftp_site_pass'];
	
	$token_local = getToken();
	$token_remoto = getToken();
	
	$user_bd = substr($user,0,8);
	
	$token_verificacao_local = md5(md5($user_bd . '_b2make' . $senha) . $token_local);
	$token_verificacao_remoto = md5(md5($ftp_site_pass) . $token_remoto);
	
	banco_update
	(
		"token='".$token_local."',".
		"token_verificacao='".$token_verificacao_local."'",
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	$config .= "\$_B2MAKE['bd-user'] = '" . $user_bd . "_b2make';\n";
	$config .= "\$_B2MAKE['bd-pass'] = '" . $senha . "';\n";
	$config .= "\$_B2MAKE['token'] = '" . $token_remoto . "';\n";
	$config .= "\$_B2MAKE['token_verificacao'] = '" . $token_verificacao_remoto . "';\n";
	
	$config = "<?php\n" . $config . "?>";
	
	$_CPANEL['FTP_LOCAL'] = $_B2MAKE_SERVER_ALIAS;
	$_CPANEL['DB_ADD']['cpuser'] = $user;
	$_CPANEL['DB_ADD']['user'] = $user_bd;
	$_CPANEL['DB_ADD']['name'] = 'b2make';
	$_CPANEL['DB_ADD']['pass'] = $senha;
	//$_CPANEL['LOG'] = true;
	
	if($_SERVER['SERVER_NAME'] != "localhost"){
		require('../'.$_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-createdb.php');
	}
	
	if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
		'manual' => true,
		'host' => $_SYSTEM['SITE']['ftp-site-host'],
		'user' => $_SYSTEM['SITE']['ftp-site-user'],
		'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
	));
	
	if($_CONEXAO_FTP){
		// Definição dos caminhos dos arquivos
		$path = $_SYSTEM['PATH_PARENT'].$_SYSTEM['SEPARADOR'].'b2make-cliente';
		$path_update = $_SYSTEM['PATH_PARENT'].$_SYSTEM['SEPARADOR'].'b2make-cliente-update';
		
		// Alteração do .htaccess da raiz do site com inclusão redirect_mobile
		$tmp_file = $_SYSTEM['TMP'].'httacces-tmp'.session_id();
		$modelo = modelo_abrir($_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'projeto'.$_SYSTEM['SEPARADOR'].'template.htaccess.txt');
		
		ftp_chdir($_CONEXAO_FTP,'~');
		if(ftp_get_file('.htaccess',$tmp_file)) {
			$htaccess = file_get_contents($tmp_file);
			$htaccess_exists = true;
			unlink($tmp_file);
		}
		
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
		
		$htaccess = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $htaccess);
		
		$nome_file = '.htaccess';
		$tmp_file = $_SYSTEM['TMP'].'httacces-2-tmp'.session_id();
		file_put_contents($tmp_file, $htaccess);
		
		ftp_put_file($nome_file, $tmp_file);
		
		unlink($tmp_file);
		
		// Alteração do .htaccess da raiz do site com inclusão mod_write apontando para o index.php
		$tmp_file = $_SYSTEM['TMP'].'httacces-tmp'.session_id();
		$modelo = file_get_contents($path.$_SYSTEM['SEPARADOR'].'.htaccess');
		
		$htaccess_exists = false;
		ftp_chdir($_CONEXAO_FTP,'~');
		if(ftp_get_file('.htaccess',$tmp_file)) {
			$htaccess = file_get_contents($tmp_file);
			$htaccess_exists = true;
			unlink($tmp_file);
		}
		
		if(preg_match('/'.preg_quote('### b2make_htaccess_store ').'/i', $htaccess) != 0){
			$htaccess = modelo_tag_in($htaccess,'### b2make_htaccess_store <','### b2make_htaccess_store >','');
		}
		
		if($htaccess_exists){
			$htaccess = $htaccess . "\n" . '### b2make_htaccess_store <' . "\n" . $modelo . "\n" . '### b2make_htaccess_store >';
		} else {
			$htaccess = '### b2make_htaccess_store <' . "\n" . $modelo . "\n" . '### b2make_htaccess_store >';
		}
		
		$htaccess = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $htaccess);
		
		$nome_file = '.htaccess';
		$tmp_file = $_SYSTEM['TMP'].'httacces-2-tmp'.session_id();
		file_put_contents($tmp_file, $htaccess);
		ftp_put_file($nome_file, $tmp_file);
		unlink($tmp_file);
		
		// Copiar todos os arquivos do b2make-cliente para o host do cliente na pasta raiz (versão desktop)
		$caminho_atual = false;
		$di = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
			if($file->getFilename() != '.' && $file->getFilename() != '..' && $file->getFilename() != '.htaccess'){
				$caminho =  ltrim(str_replace($path,'',$file->getPath()),$_SYSTEM['SEPARADOR']);
				$diretorios = explode($_SYSTEM['SEPARADOR'],$caminho);
				
				if($caminho != $caminho_atual){
					$caminho_atual = $caminho;
					
					ftp_chdir($_CONEXAO_FTP,'~');
					
					if($diretorios[0]){
						if(count($diretorios) == 1){
							if(!@ftp_chdir($_CONEXAO_FTP, $caminho)){
								ftp_mkdir($_CONEXAO_FTP, $caminho);
								ftp_chdir($_CONEXAO_FTP, $caminho);
							}
						} else {
							foreach($diretorios as $diretorio){
								if(!@ftp_chdir($_CONEXAO_FTP, $diretorio)){
									ftp_mkdir($_CONEXAO_FTP, $diretorio);
									ftp_chdir($_CONEXAO_FTP, $diretorio);
								}
							}
						}
					}
				}
				
				ftp_put_file($file->getFilename(), $filename);
			}
			
		}
		
		// Instalar arquivos de configuração para o host do cliente na pasta raiz (versão desktop)
		ftp_chdir($_CONEXAO_FTP,'~/b2make');
		
		$tmp_file = $_SYSTEM['TMP'].'config.php'.session_id();
		$config_exists = false;
		if(ftp_get_file('config.php',$tmp_file)) {
			$config_old = file_get_contents($tmp_file);
			$config_exists = true;
			unlink($tmp_file);
		}
		
		if(!$config_exists){
			$tmp_file = $_SYSTEM['TMP'].'b2make-tmp'.session_id().'.php';
			file_put_contents($tmp_file,$config);
			ftp_put_file('config.php', $tmp_file);
			unlink($tmp_file);
		}
		
		ftp_put_file('banco.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'banco.php');
		ftp_put_file('geral.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'geral.php');
		
		// Instalar a pasta mobile caso não exista
		ftp_chdir($_CONEXAO_FTP,'~');
		if(!is_dir('ftp://'.$_SYSTEM['SITE']['ftp-site-user'].':'.$_SYSTEM['SITE']['ftp-site-pass'].'@'.$_SYSTEM['SITE']['ftp-site-host'].'/'.$_SYSTEM['SITE']['ftp-site-path'].$_SYSTEM['SITE']['ftp-mobile-path'])) {
			ftp_mkdir($_CONEXAO_FTP, $_SYSTEM['SITE']['ftp-mobile-path']);
		}
		
		// Copiar todos os arquivos do b2make-cliente para o host do cliente na pasta mobile (versão mobile)
		$caminho_atual = false;
		$di = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
			if($file->getFilename() != '.' && $file->getFilename() != '..'){
				$caminho =  ltrim(str_replace($path,'',$file->getPath()),$_SYSTEM['SEPARADOR']);
				$diretorios = explode($_SYSTEM['SEPARADOR'],$caminho);
				
				if($caminho != $caminho_atual){
					$caminho_atual = $caminho;
					
					ftp_chdir($_CONEXAO_FTP,'~/mobile');
					
					if($diretorios[0]){
						if(count($diretorios) == 1){
							if(!@ftp_chdir($_CONEXAO_FTP, $caminho)){
								ftp_mkdir($_CONEXAO_FTP, $caminho);
								ftp_chdir($_CONEXAO_FTP, $caminho);
							}
						} else {
							foreach($diretorios as $diretorio){
								if(!@ftp_chdir($_CONEXAO_FTP, $diretorio)){
									ftp_mkdir($_CONEXAO_FTP, $diretorio);
									ftp_chdir($_CONEXAO_FTP, $diretorio);
								}
							}
						}
					}
				}
				
				ftp_put_file($file->getFilename(), $filename);
			}
		}
		
		// Instalar arquivos de configuração para o host do cliente na pasta mobile (versão mobile)
		ftp_chdir($_CONEXAO_FTP,'~/mobile/b2make');
		
		if(!$config_exists){
			$tmp_file = $_SYSTEM['TMP'].'b2make-tmp'.session_id().'.php';
			file_put_contents($tmp_file,$config);
			ftp_put_file('config.php', $tmp_file);
			unlink($tmp_file);
		} else {
			$tmp_file = $_SYSTEM['TMP'].'b2make-tmp'.session_id().'.php';
			file_put_contents($tmp_file,$config_old);
			ftp_put_file('config.php', $tmp_file);
			unlink($tmp_file);
		}
		
		ftp_put_file('banco.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'banco.php');
		ftp_put_file('geral.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'geral.php');
		
		// Criar arquivos de configuração inicial
		site_library_update(Array(
			'widget' => 'formularios',
			'nao_desconectar_ftp' => true,
		));
		
		site_library_update(Array(
			'widget' => 'posts-filter',
			'nao_desconectar_ftp' => true,
		));
		
		// Atualizar o sistema no host do cliente com as alterações necessárias no banco de dados e demais opções
		$update_sys = file_get_contents($path_update.$_SYSTEM['SEPARADOR'].'update-sys.php');
		
		ftp_chdir($_CONEXAO_FTP,'~/b2make');
		
		$nome_file = 'update-sys.php';
		$tmp_file = $_SYSTEM['TMP'].'update-sys.php-tmp'.session_id();
		file_put_contents($tmp_file, $update_sys);
		ftp_put_file($nome_file, $tmp_file);
		unlink($tmp_file);
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		);
		
		$url = parse_url($host[0]['url'], PHP_URL_HOST);
		
		$url = $url . '/b2make/update-sys.php';
		
		$data = false;
		$data['pub_id'] = $usuario['pub_id'];
		
		if($_B2MAKE_BETA) $data['beta'] = 'sim';
		
		$data = http_build_query($data);
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$xml = curl_exec($curl);
		
		curl_close($curl);
		
		libxml_use_internal_errors(true);
		$obj_xml = simplexml_load_string($xml);
		
		if(!$obj_xml){
			$erro_update_sys = 'Erro: houve algum problema na atualiza&ccedil;&atilde;o o host do site. Erro retornado: '.$xml;
		} else if($obj_xml->error){
			$erro_update_sys = 'Erro: houve algum problema na atualiza&ccedil;&atilde;o o host do site. Erro retornado: '.$obj_xml->error;
		} else if($obj_xml->status != 'OK'){
			$erro_update_sys = 'Erro: houve algum problema na atualiza&ccedil;&atilde;o o host do site. Erro retornado: '.$obj_xml->status;
		}
		
		ftp_delete($_CONEXAO_FTP, 'update-sys.php');
		
		ftp_fechar_conexao();
		
		if($erro_update_sys){
			$_ALERTA = $erro_update_sys;
		} else {
			banco_update
			(
				"mobile_redirect_server=1,".
				"version='2'",
				"host",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			banco_update
			(
				"widget_loja=1,".
				"loja_url_cliente=1",
				"loja",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			);
			
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
			
			header("Location: /dashboard");
			exit(0);
		}
	}
}

function upgrade_host_update($version){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_B2MAKE_PAGINA_LOCAL;
	global $_ALERTA;
	global $_HOST_VERSION;
	global $_B2MAKE_BETA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if($_SESSION[$_SYSTEM['ID']."b2make-site"]){ $_SYSTEM['SITE'] = array_merge($_SESSION[$_SYSTEM['ID']."b2make-site"], $_SYSTEM['SITE']); }
	
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
	
	if(!$host)return;
	
	if(!$_CONEXAO_FTP && !$_B2MAKE_PAGINA_LOCAL)ftp_conectar(Array(
		'manual' => true,
		'host' => $_SYSTEM['SITE']['ftp-site-host'],
		'user' => $_SYSTEM['SITE']['ftp-site-user'],
		'pass' => $_SYSTEM['SITE']['ftp-site-pass'],
	));
	
	if($_CONEXAO_FTP){
		// Definição dos caminhos dos arquivos
		$path = $_SYSTEM['PATH_PARENT'].$_SYSTEM['SEPARADOR'].'b2make-cliente';
		$path_update = $_SYSTEM['PATH_PARENT'].$_SYSTEM['SEPARADOR'].'b2make-cliente-update';
		
		// Alteração do .htaccess da raiz do site com inclusão redirect_mobile
		$tmp_file = $_SYSTEM['TMP'].'httacces-tmp'.session_id();
		$modelo = modelo_abrir($_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'projeto'.$_SYSTEM['SEPARADOR'].'template.htaccess.txt');
		
		ftp_chdir($_CONEXAO_FTP,'~');
		if(ftp_get_file('.htaccess',$tmp_file)) {
			$htaccess = file_get_contents($tmp_file);
			$htaccess_exists = true;
			unlink($tmp_file);
		}
		
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
		
		$htaccess = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $htaccess);
		
		$nome_file = '.htaccess';
		$tmp_file = $_SYSTEM['TMP'].'httacces-2-tmp'.session_id();
		file_put_contents($tmp_file, $htaccess);
		
		ftp_put_file($nome_file, $tmp_file);
		
		unlink($tmp_file);
		
		// Alteração do .htaccess da raiz do site com inclusão mod_write apontando para o index.php
		$tmp_file = $_SYSTEM['TMP'].'httacces-tmp'.session_id();
		$modelo = file_get_contents($path.$_SYSTEM['SEPARADOR'].'.htaccess');
		
		$htaccess_exists = false;
		ftp_chdir($_CONEXAO_FTP,'~');
		if(ftp_get_file('.htaccess',$tmp_file)) {
			$htaccess = file_get_contents($tmp_file);
			$htaccess_exists = true;
			unlink($tmp_file);
		}
		
		if(preg_match('/'.preg_quote('### b2make_htaccess_store ').'/i', $htaccess) != 0){
			$htaccess = modelo_tag_in($htaccess,'### b2make_htaccess_store <','### b2make_htaccess_store >','');
		}
		
		if($htaccess_exists){
			$htaccess = $htaccess . "\n" . '### b2make_htaccess_store <' . "\n" . $modelo . "\n" . '### b2make_htaccess_store >';
		} else {
			$htaccess = '### b2make_htaccess_store <' . "\n" . $modelo . "\n" . '### b2make_htaccess_store >';
		}
		
		$htaccess = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $htaccess);
		
		$nome_file = '.htaccess';
		$tmp_file = $_SYSTEM['TMP'].'httacces-2-tmp'.session_id();
		file_put_contents($tmp_file, $htaccess);
		ftp_put_file($nome_file, $tmp_file);
		unlink($tmp_file);
		
		// Copiar todos os arquivos do b2make-cliente para o host do cliente na pasta raiz (versão desktop)
		$caminho_atual = false;
		$di = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
			if($file->getFilename() != '.' && $file->getFilename() != '..' && $file->getFilename() != '.htaccess'){
				$caminho =  ltrim(str_replace($path,'',$file->getPath()),$_SYSTEM['SEPARADOR']);
				$diretorios = explode($_SYSTEM['SEPARADOR'],$caminho);
				
				if($caminho != $caminho_atual){
					$caminho_atual = $caminho;
					
					ftp_chdir($_CONEXAO_FTP,'~');
					
					if($diretorios[0]){
						if(count($diretorios) == 1){
							if(!@ftp_chdir($_CONEXAO_FTP, $caminho)){
								ftp_mkdir($_CONEXAO_FTP, $caminho);
								ftp_chdir($_CONEXAO_FTP, $caminho);
							}
						} else {
							foreach($diretorios as $diretorio){
								if(!@ftp_chdir($_CONEXAO_FTP, $diretorio)){
									ftp_mkdir($_CONEXAO_FTP, $diretorio);
									ftp_chdir($_CONEXAO_FTP, $diretorio);
								}
							}
						}
					}
				}
				
				ftp_put_file($file->getFilename(), $filename);
			}
		}
		
		// Atualizar bibliotecas básicas (versão desktop).
		ftp_chdir($_CONEXAO_FTP,'~/b2make');
		
		ftp_put_file('banco.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'banco.php');
		ftp_put_file('geral.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'geral.php');
		
		// Copiar todos os arquivos do b2make-cliente para o host do cliente na pasta mobile (versão mobile)
		$caminho_atual = false;
		$di = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
			if($file->getFilename() != '.' && $file->getFilename() != '..'){
				$caminho =  ltrim(str_replace($path,'',$file->getPath()),$_SYSTEM['SEPARADOR']);
				$diretorios = explode($_SYSTEM['SEPARADOR'],$caminho);
				
				if($caminho != $caminho_atual){
					$caminho_atual = $caminho;
					
					ftp_chdir($_CONEXAO_FTP,'~/mobile');
					
					if($diretorios[0]){
						if(count($diretorios) == 1){
							if(!@ftp_chdir($_CONEXAO_FTP, $caminho)){
								ftp_mkdir($_CONEXAO_FTP, $caminho);
								ftp_chdir($_CONEXAO_FTP, $caminho);
							}
						} else {
							foreach($diretorios as $diretorio){
								if(!@ftp_chdir($_CONEXAO_FTP, $diretorio)){
									ftp_mkdir($_CONEXAO_FTP, $diretorio);
									ftp_chdir($_CONEXAO_FTP, $diretorio);
								}
							}
						}
					}
				}
				
				ftp_put_file($file->getFilename(), $filename);
			}
		}
		
		// Atualizar bibliotecas básicas (versão mobile).
		ftp_chdir($_CONEXAO_FTP,'~/mobile/b2make');
		
		ftp_put_file('banco.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'banco.php');
		ftp_put_file('geral.php', $_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'geral.php');
		
		// Atualizar o sistema no host do cliente com as alterações necessárias no banco de dados e demais opções
		$update_sys = file_get_contents($path_update.$_SYSTEM['SEPARADOR'].'update-sys.php');
		
		ftp_chdir($_CONEXAO_FTP,'~/b2make');
		
		$nome_file = 'update-sys.php';
		$tmp_file = $_SYSTEM['TMP'].'update-sys.php-tmp'.session_id();
		file_put_contents($tmp_file, $update_sys);
		ftp_put_file($nome_file, $tmp_file);
		unlink($tmp_file);
		
		$url = parse_url($host[0]['url'], PHP_URL_HOST);
		
		$url = $url . '/b2make/update-sys.php';
		
		$data = false;
		$data['pub_id'] = $usuario['pub_id'];
		$data['version'] = $version;
		
		if($_B2MAKE_BETA) $data['beta'] = 'sim';
		
		$data = http_build_query($data);
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$xml = curl_exec($curl);
		
		curl_close($curl);
		
		libxml_use_internal_errors(true);
		$obj_xml = simplexml_load_string($xml);
		
		if(!$obj_xml){
			$erro_update_sys = 'Erro: houve algum problema na atualiza&ccedil;&atilde;o o host do site. Erro retornado: '.$xml;
		} else if($obj_xml->error){
			$erro_update_sys = 'Erro: houve algum problema na atualiza&ccedil;&atilde;o o host do site. Erro retornado: '.$obj_xml->error;
		} else if($obj_xml->status != 'OK'){
			$erro_update_sys = 'Erro: houve algum problema na atualiza&ccedil;&atilde;o o host do site. Erro retornado: '.$obj_xml->status;
		}
		
		ftp_delete($_CONEXAO_FTP, 'update-sys.php');
		
		ftp_fechar_conexao();
		
		if($version == 'all'){
			$version = $_HOST_VERSION;
			
			$url = $host[0]['url'] . 'platform/conteudos';
			curl_post_async($url);
			$url = $host[0]['url'] . 'platform/library';
			curl_post_async($url);
			$url = $host[0]['url'] . 'platform/sitemaps';
			curl_post_async($url);
			$url = $host[0]['url'] . 'platform/site-version';
			curl_post_async($url);
			
			if($version == 7){
				publisher_all_pages();
				publisher_sitemaps();
				config_publish_all_library();
			}
		} else {
			switch($version){
				case 3:
					$url = $host[0]['url'] . 'platform/conteudos';
					curl_post_async($url);
				break;
				case 4:
					$url = $host[0]['url'] . 'platform/library';
					curl_post_async($url);
				break;
				case 5:
					$url = $host[0]['url'] . 'platform/sitemaps';
					curl_post_async($url);
					$url = $host[0]['url'] . 'platform/site-version';
					curl_post_async($url);
				break;
				case 7:
					publisher_all_pages();
					publisher_sitemaps();
					config_publish_all_library();
				break;
			}
		}
		
		if($erro_update_sys){
			$_ALERTA = $erro_update_sys;
		} else {
			banco_update
			(
				"version='".$version."'",
				"host",
				"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
				." AND atual IS TRUE"
			);
			
			$url = $host[0]['url'] . 'platform/atualizar';
			curl_post_async($url);
			
			$_SESSION[$_SYSTEM['ID']."alerta"] = 'Sistema atualizado com sucesso no hospedeiro da sua conta para a vers&atilde;o: <b>'.$version.'</b>';
			
			header("Location: /dashboard");
			exit(0);
		}
	}
}

function upgrade_host(){
	global $_SYSTEM;
	global $_HOST_VERSION;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$force_update = $_REQUEST['force-update'];
	$force_update_all = $_REQUEST['force-update-all'];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'version',
			'id_usuario',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	if($resultado){
		if(!$resultado[0]['version']){
			upgrade_host_install();
		} else if($_HOST_VERSION != (int)$resultado[0]['version'] || $force_update){
			switch($_HOST_VERSION){
				case 2: upgrade_host_install(); break;
				default:
					if($_HOST_VERSION > (int)$resultado[0]['version'] + 1){
						upgrade_host_update('all');
					} else {
						upgrade_host_update($_HOST_VERSION);
					}
			}
		} else if($force_update_all){
			upgrade_host_update('all');
		}
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'version',
			'id_usuario',
		))
		,
		"host",
		"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
		." AND atual IS TRUE"
	);
	
	if(!$_SESSION[$_SYSTEM['ID']."tentativas-instalacao"]){
		$_SESSION[$_SYSTEM['ID']."tentativas-instalacao"] = 0;
	}
	
	if(!$resultado[0]['version'] && $_SESSION[$_SYSTEM['ID']."tentativas-instalacao"] < 6){
		$_SESSION[$_SYSTEM['ID']."tentativas-instalacao"]++;
		upgrade_host_try_install_again();
	}
}

function change_log_summary($limite = 2){
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	global $_VERSAO;
	
	$linhas = file($_SYSTEM['PATH'].'changelog-summary');
	
	if($linhas){
		
		foreach($linhas as $num => $linha){
			if(!$start && preg_match('/^## /', $linha) > 0){
				$start = true;
				$primeiro = true;
			}
			
			if($start){
				$linha = $linha;
				
				if(preg_match('/^## /', $linha) > 0){
					if(!$primeiro){
						$cabecalho = '';
						$summary = '';
					}
					
					$linha_arr = explode(' - ',$linha);
					$data = preg_replace('/\./i', '/', trim($linha_arr[1]));
					$versao = modelo_tag_val($linha,'[',']');
					
					$cabecalho = '<div class="news-version">Versão: '.$versao . '</div><div class="news-date">' . $data.'</div>';
					
					$primeiro = false;
				}
				
				if(preg_match('/^### /', $linha) > 0){
					$count++;
					
					$summary = '<div class="news-summary">'.trim(preg_replace('/^### /i', '', $linha)).'</div>';
					$pagina .= '<div class="news-cel">'.$cabecalho . '<div class="clear"></div>' . $summary .'</div>'."\n";
					
					if($count == $limite){
						break;
					}
				}
			}
		}
		
	}
	
	return $pagina;
}

function paginaInicial(){
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	global $_VERSAO;
	global $_VARIAVEIS_JS;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'dashboard'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- inicio < -->','<!-- inicio > -->');
	
	$pagina = modelo_var_troca($pagina,"#usuario_nome#",$usuario['nome']);
	
	$cel_nome = 'services-cel'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	if($usuario['id_usuario_pai']){
		$cel_nome = 'financeiro'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		$cel_nome = 'data'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		$pagina = modelo_var_troca_tudo($pagina,"#class-user-custom#",' conteiner-card-user');
		$limite_servicos = 4;
		$limite_novidades = 3;
		$data_especifica = 'CURRENT_DATE()';
		
		$_VARIAVEIS_JS['financeiro_off'] = true;
	} else {
		// ========================== Filtro <
		
		$filtro = $_SESSION[$_SYSTEM['ID']."dashboard-filtro"];
		
		if($_REQUEST['filtro-data']) $filtro['filtro-data'] = $_REQUEST['filtro-data'];
		
		$meses = 20;
		$mes_atual = (int)date('n');
		$ano_atual = (int)date('Y');
		$meses_nomes = Array(
			12 => 'Dezembro',
			11 => 'Novembro',
			10 => 'Outubro',
			9 => 'Setembro',
			8 => 'Agosto',
			7 => 'Julho',
			6 => 'Junho',
			5 => 'Maio',
			4 => 'Abril',
			3 => 'Março',
			2 => 'Fevereiro',
			1 => 'Janeiro',
		);
		
		$data_selected_value = $mes_atual.'-'.$ano_atual;
		$data_selected_text = $meses_nomes[$mes_atual].'/'.$ano_atual;
		
		for($i=0;$i<$meses;$i++){
			if($filtro['filtro-data'] && $filtro['filtro-data'] == $mes_atual.'-'.$ano_atual){
				$data_selected_value = $mes_atual.'-'.$ano_atual;
				$data_selected_text = $meses_nomes[$mes_atual].'/'.$ano_atual;
			}
			
			$data_options[] = Array(
				'value' => $mes_atual.'-'.$ano_atual,
				'text' => $meses_nomes[$mes_atual].'/'.$ano_atual,
			);
			
			$mes_atual--;
			
			if($mes_atual < 1){
				$mes_atual = 12;
				$ano_atual--;
			}
		}
		
		$select_data = componentes_select(Array(
			'input_name' => 'filtro-data',
			'selected_value' => $data_selected_value,
			'selected_text' => $data_selected_text,
			'unselected_value' => $data_selected_value,
			'unselected_text' => $data_selected_text,
			'options' => $data_options,
			'cont_callback' => 'data_filtro',
			'cont_class_extra' => ' filtros-cont',
		));
		
		$pagina = modelo_var_troca_tudo($pagina,"#select-data#",$select_data);
		
		$_SESSION[$_SYSTEM['ID']."dashboard-filtro"] = $filtro;
		
		if($filtro['filtro-data']){
			$data = explode('-',$filtro['filtro-data']);
			$mes_selecionado = $data[0];
			$ano_selecionado = $data[1];
			$data_especifica = "'".$data[1].'-'.((int)$data[0] < 10 ? '0' : '').$data[0].'-01'."'";
		} else {
			$mes_selecionado = date('n');
			$ano_selecionado = date('Y');
			$data_especifica = 'CURRENT_DATE()';
		}
		
		// ========================== Filtro >
		
		$pagina = modelo_var_troca_tudo($pagina,"#class-user-custom#",'');
		$limite_servicos = 3;
		$limite_novidades = 2;
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't3.id_loja_usuarios',
			))
			,
			"pedidos as t1, loja_usuarios_pedidos as t2, loja_usuarios as t3",
			"WHERE t1.id_pedidos=t2.id_pedidos"
			." AND t2.id_loja_usuarios=t3.id_loja_usuarios"
			." AND t1.id_loja='".$usuario['id_loja']."'"
			." AND MONTH(t3.data_cadastro) = MONTH(".$data_especifica.") AND YEAR(t3.data_cadastro) = YEAR(".$data_especifica.")"
			." GROUP BY t3.id_loja_usuarios"
		);
		
		$pagina = modelo_var_troca($pagina,"#new-clients#",(isset($resultado) ? count($resultado) : 0));
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_pedidos_servicos',
			))
			,
			"pedidos_servicos as t1, pedidos as t2",
			"WHERE t1.id_pedidos=t2.id_pedidos"
			." AND MONTH(t2.data) = MONTH(".$data_especifica.") AND YEAR(t2.data) = YEAR(".$data_especifica.")"
			." AND (t1.status='A' OR t1.status='F')"
			." AND t2.id_loja='".$usuario['id_loja']."'"
			." GROUP BY t1.id_pedidos_servicos"
		);

		$pagina = modelo_var_troca($pagina,"#month-sales#",(isset($resultado) ? count($resultado) : 0));
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.valor_total',
				't2.id_pedidos',
			))
			,
			"pedidos_servicos as t1, pedidos as t2",
			"WHERE t1.id_pedidos=t2.id_pedidos"
			." AND MONTH(t2.data) = MONTH(".$data_especifica.") AND YEAR(t2.data) = YEAR(".$data_especifica.")"
			." AND (t2.status='A' OR t2.status='F')"
			." AND t2.id_loja='".$usuario['id_loja']."'"
			//." GROUP BY t2.valor_total"
		);
		
		$valor_total = 0;
		
		if($resultado){
			foreach($resultado as $res){
				$valor_total += (float)$res['t2.valor_total'];
			}
		}
		
		$pagina = modelo_var_troca($pagina,"#month-total#",'R$ '.preparar_float_4_texto($valor_total));
		
		/* $resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_pedidos',
				't2.valor_total',
			))
			,
			"pedidos_servicos as t1, pedidos as t2",
			"WHERE t1.id_pedidos=t2.id_pedidos"
			." AND DATE(t2.data) = CURDATE()"
			." AND (t1.status='A' OR t1.status='F')"
			." AND t2.id_loja='".$usuario['id_loja']."'"
			." GROUP BY t2.valor_total"
		);
		
		$valor_total = 0;
		
		if($resultado){
			foreach($resultado as $res){
				$valor_total += (float)$res['t2.valor_total'];
			}
		} */
		
		$pagina = modelo_var_troca($pagina,"#day-total#",'R$ '.preparar_float_4_texto(($valor_total/((isset($resultado) ? count($resultado) : 1)))));
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.id_pedidos_servicos',
				't2.status',
			))
			,
			"pedidos as t1, pedidos_servicos as t2",
			"WHERE MONTH(t1.data) = MONTH(".$data_especifica.") AND YEAR(t1.data) = YEAR(".$data_especifica.")"
			." AND t1.id_pedidos=t2.id_pedidos"
			." AND t1.id_loja='".$usuario['id_loja']."'"
			." GROUP BY t2.id_pedidos_servicos"
		);
		
		$concluidos = 0; $aguardando = 0; $negados = 0;
		
		if($resultado){
			foreach($resultado as $res){
				switch($res['t2.status']){
					case 'A':
					case 'F':
						$concluidos++;
					break;
					case 'N':
					case 'P':
						$aguardando++;
					break;
					default:
						$negados++;
				}
			}
		}
		
		$_VARIAVEIS_JS['recent_requests_labels'] = Array('Concluídos', 'Aguardando', 'Expirados ou Negados');
		$_VARIAVEIS_JS['recent_requests_colors'] = Array(($concluidos == 0 ? '#E1E1E1' : '#59C75E'),'#FFC246','#EA5454','#1E8FFF');
		$_VARIAVEIS_JS['recent_requests_data'] = Array($concluidos,$aguardando,$negados);
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.id_pedidos_servicos',
				't2.status',
				't1.data',
			))
			,
			"pedidos as t1, pedidos_servicos as t2",
			"WHERE MONTH(t1.data) = MONTH(".$data_especifica.") AND YEAR(t1.data) = YEAR(".$data_especifica.")"
			." AND t1.id_pedidos=t2.id_pedidos"
			." AND (t2.status='A' OR t2.status='F')"
			." AND t1.id_loja='".$usuario['id_loja']."'"
			." GROUP BY t2.id_pedidos_servicos"
		);
		
		$numero = cal_days_in_month(CAL_GREGORIAN, $mes_selecionado, $ano_selecionado);
		
		for($i=0;$i<$numero;$i++){
			$quantidade = 0;
			
			if($resultado){
				foreach($resultado as $res){
					$data = data_hora_array($res['t1.data']);
					$dia = ltrim($data['dia'],'0');
					
					if($dia == $i+1){
						$quantidade++;
					}
				}
			}
			
			$dias_do_mes_label[] = $i+1;
			$dias_do_mes_data[] = $quantidade;
			$dias_cores[] = '#1E8FFF';
		}
		
		$_VARIAVEIS_JS['monthly_summary_label'] = 'Vendas no Dia';
		$_VARIAVEIS_JS['monthly_summary_labels'] = $dias_do_mes_label;
		$_VARIAVEIS_JS['monthly_summary_colors'] = $dias_cores;
		$_VARIAVEIS_JS['monthly_summary_data'] = $dias_do_mes_data;
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'status',
			'quantidade',
			'id_servicos',
		))
		,
		"servicos",
		"WHERE id_loja='".$usuario['id_loja']."'"
		." AND status!='D'"
		." ORDER BY id_servicos DESC"
		." LIMIT ".$limite_servicos
	);
	
	$cel_nome = 'services-cel';
	
	if($resultado){
		foreach($resultado as $res){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.id_pedidos_servicos',
				))
				,
				"pedidos_servicos as t1, pedidos as t2",
				"WHERE t1.id_servicos='".$res['id_servicos']."'"
				." AND t1.id_pedidos=t2.id_pedidos"
				." AND MONTH(t2.data) = MONTH(".$data_especifica.") AND YEAR(t2.data) = YEAR(".$data_especifica.")"
				." AND t2.id_loja='".$usuario['id_loja']."'"
				." GROUP BY t1.id_pedidos_servicos"
			);
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca($cel_aux,"#icon-state#",' data-status="'.$res['status'].'"');
			$cel_aux = modelo_var_troca($cel_aux,"#state#",($res['status'] == 'A' ? 'Serviço Ativo':'Serviço Inativo'));
			$cel_aux = modelo_var_troca($cel_aux,"#label#",$res['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#stock#",preparar_float_4_texto($res['quantidade'],true).' em estoque');
			$cel_aux = modelo_var_troca($cel_aux,"#sales#",preparar_float_4_texto((isset($resultado2) ? count($resultado2) : 0),true).' vendidos');
			$cel_aux = modelo_var_troca($cel_aux,"#url#",'../store/services/?opcao=editar&id='.$res['id_servicos']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','<div id="services-dont-exists">Não há serviços cadastrados na sua conta.</div>');
	}
	
	$pagina = modelo_var_troca($pagina,"#change-log-summary#",change_log_summary($limite_novidades));

	return $pagina;
}

function teste(){
	
}

// ======================================= Ajax Chamadas ===============================================

function ajax_dark_mode_change(){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	switch($_REQUEST['mode']){
		case 'dark':
			$dark = true;
			$_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode'] = true;
		break;
		default:
			$dark = false;
			$_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode'] = false;
	}
	
	banco_update
	(
		"dark_mode=".($dark ? '1' : '0'),
		"usuario",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
	);
	
	$saida = Array(
		'status' => 'Ok',
	);
	
	return $saida;
}

// ======================================================================================

function ajax(){
	global $_PROJETO;
	global $_SYSTEM;
	
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $permissao){
		if($permissao == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
			$permissao_flag = true;
			break;
		}
	}
	
	if(!$permissao_flag){
		$saida = Array(
			'status' => 'SemPermissao',
		);
		
		return json_encode($saida);
	}
	
	switch($_REQUEST["opcao"]){
		case 'dark-mode-change': $saida = ajax_dark_mode_change(); break;
	}
	
	return (!$_AJAX_OUT_VARS['not-json-encode'] ? json_encode($saida) : $saida);
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	
	if($_GET['opcao'])			$opcao = $_GET['opcao'];
	if($_POST['opcao'])			$opcao = $_POST['opcao'];
	
	upgrade_host();
	
	if(!$_REQUEST['ajax']){
		switch($opcao){
			case 'teste':					$saida = teste();break;
			default: 						$saida = paginaInicial();
		}

		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

main();

?>