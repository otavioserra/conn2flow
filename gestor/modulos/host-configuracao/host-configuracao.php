<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'host-configuracao';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.3.15',
	'bibliotecas' => Array('interface','html','pagina'),
	'tabela' => Array(
		'nome' => 'hosts',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
	'tipoDominio' => Array(
		'sistema' => 'Sistema',
		'proprio' => 'Próprio',
	),
);

// ===== Funções Auxiliares

function host_configuracao_openssl_gerar_chaves($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// tipo - String - Obrigatório - Tipo da chave openssl que será gerada usando o algoritmo correto.
	// senha - String - Opcional - Senha para encriptar a chave privada.
	
	// ===== 
	
	$chaves = false;
	
	if(isset($tipo)){
		switch($tipo){
			case 'RSA':
				$config = array(
					"digest_alg" => "sha512",
					"private_key_bits" => 2048,
					"private_key_type" => OPENSSL_KEYTYPE_RSA,
				);
				
				$res = openssl_pkey_new($config);
				
				if(isset($senha)){
					openssl_pkey_export($res, $chavePrivada,$senha);
				} else {
					openssl_pkey_export($res, $chavePrivada);
				}
				
				$chavePrivadaDetalhes = openssl_pkey_get_details($res);
				$chavePublica = $chavePrivadaDetalhes["key"];
				
				return Array(
					'publica' => $chavePublica,
					'privada' => $chavePrivada,
				);
			break;
		}
	}
	
	return $chaves;
}

function host_configuracao_encriptar($valor){
	global $_GESTOR;
	
	$cryptMaxCharsValue = 245; // There are char limitations on openssl_private_encrypt() and in the url below are explained how define this value based on openssl key format: https://www.php.net/manual/en/function.openssl-private-encrypt.php#119810
	
	// ===== Ler chave pública
	
	$keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
	
	$fp = fopen($keyPublicPath,"r");
	$chavePublica = fread($fp,8192);
	fclose($fp);
	
	// ===== Unir header com payload para gerar assinatura

	$rawDataSource = $valor;
	
	// ===== Assinar usando RSA SSL
	
	$resPublicKey = openssl_get_publickey($chavePublica);

	$partialData = '';
	$encodedData = '';
	$split = str_split($rawDataSource , $cryptMaxCharsValue);
	foreach($split as $part){
		openssl_public_encrypt($part, $partialData, $resPublicKey);
		$encodedData .= (strlen($encodedData) > 0 ? '.':'') . base64_encode($partialData);
	}
	
	$encodedData = base64_encode($encodedData);
	
	$signature = $encodedData;
	
	return $signature;
}

function host_configuracao_decriptar($valor){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Abrir chave privada e a senha da chave
	
	$keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
	
	$fp = fopen($keyPrivatePath,"r");
	$chavePrivada = fread($fp,8192);
	fclose($fp);
	
	$chavePrivadaSenha = $_CONFIG['openssl-password'];
	
	// ===== Abrir chave privada com a senha
	
	$resPrivateKey = openssl_get_privatekey($chavePrivada,$chavePrivadaSenha);
	
	// ===== Decode base64 to reaveal dots (Dots are used in JWT syntaxe)

	$encodedData = base64_decode($valor);

	// ===== Decrypt data in parts if necessary. Using dots as split separator.

	$rawEncodedData = $encodedData;

	$countCrypt = 0;
	$partialDecodedData = '';
	$decodedData = '';
	$split2 = explode('.',$rawEncodedData);
	foreach($split2 as $part2){
		$part2 = base64_decode($part2);
		
		openssl_private_decrypt($part2, $partialDecodedData, $resPrivateKey);
		$decodedData .= $partialDecodedData;
	}
	
	return $decodedData;
}

function host_configuracao_crypto_rand_secure($min, $max) {
	$range = $max - $min;
	if ($range < 0) return $min; // not so random...
	$log = log($range, 2);
	$bytes = (int) ($log / 8) + 1; // length in bytes
	$bits = (int) $log + 1; // length in bits
	$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
	do {
		$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
		$rnd = $rnd & $filter; // discard irrelevant bits
	} while ($rnd >= $range);
	return $min + $rnd;
}

function host_configuracao_gerar_senha($length=32){
    $senha = "";$codeAlphabet = "";
	$count = 0;
	
    $code[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $code[] = "abcdefghijklmnopqrstuvwxyz";
    $code[] = "0123456789";
    $code[] = "!@#$%^&*";
	
	for($i=0;$i<count($code);$i++){
		$codeAlphabet .= $code[$i];
	}
	
    for($i=0;$i<$length;$i++){
		if($i == $length - count($code) + $count){
			$found = false;
			for($j=0;$j<strlen($code[$count]);$j++){
				if(strpos($senha, $code[$count][$j]) !== false){
					$found = true;
					break;
				}
			}
			
			if($found){
				$senha .= $codeAlphabet[host_configuracao_crypto_rand_secure(0,strlen($codeAlphabet))];
			} else {
				$senha .= $code[$count][host_configuracao_crypto_rand_secure(0,strlen($code[$count]))];
			}
			
			$count++;
		} else {
			$senha .= $codeAlphabet[host_configuracao_crypto_rand_secure(0,strlen($codeAlphabet))];
		}
    }
	
    return $senha;
}

function host_configuracao_pipeline_atualizacao($params = false){
	global $_GESTOR;
	global $_CONFIG;
	global $_INDEX;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção possíveis: instalar ou atualizar.
	// atualizarConfig - Bool - Opcional - Recriar o config do host.
	
	// ===== 
	
	if(isset($opcao)){
		$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
		
		// ===== Bloqueia que o cliente pare a execução do script caso feche a janela ou execute o reload da página.
		
		ignore_user_abort(1);
		
		// ===== Atualizar sessão e remover o status 'carregando'.
		
		unset($host_verificacao['carregando']);
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		$dadosInstalacao = $host_verificacao['dados-instalacao'];
		
		$id_hosts = $host_verificacao['id_hosts'];
		
		// ===== Carregar dados do host
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'user_ftp',
				'user_db',
				'dominio',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		// ===== Variáveis do host
		
		$user_ftp = $hosts[0]['user_ftp'];
		$user_db = $hosts[0]['user_db'];
		$dominio = $hosts[0]['dominio'];
		
		$senhaFtp = host_configuracao_decriptar($dadosInstalacao['senha-ftp']);
		$senhaDb = host_configuracao_decriptar($dadosInstalacao['senha-db']);
		
		// ===== Conectar FTP do cliente
		
		gestor_incluir_biblioteca('ftp');
		
		if(ftp_conectar(Array(
			'host' => $dominio,
			'usuario' => $user_ftp,
			'senha' => $senhaFtp,
			'secure' => true,
		))){
			// ===== Controle para instalar/atualizar o arquivo de configuração.
			
			$configAtualizar = false;
			
			switch($opcao){
				case 'atualizar': 
					if(isset($atualizarConfig)){
						$configAtualizar = true;
					}
				break;
				case 'instalar': $configAtualizar = true; break;
			}
			
			if($configAtualizar){
				// ===== Criar pubID para identificador o host e funções de hash para criptografia.
				
				$pubId = md5(uniqid(rand(), true));
				
				$hashAlgo = $_CONFIG['platform-hash-algo'];
				$hashPass = host_configuracao_gerar_senha();
				
				// ===== Criar chaves de segurança para a comunicação entre as plataformas CLIENTE <-> SERVIDOR
				
				$senhaChavePrivada = host_configuracao_gerar_senha();
				
				$chaves = host_configuracao_openssl_gerar_chaves(Array(
					'tipo' => 'RSA', 
					'senha' => $senhaChavePrivada, 
				));
				
				banco_update
				(
					"pub_id='".$pubId."',".
					"chave_publica='".$chaves['publica']."'",
					"hosts",
					"WHERE id_hosts='".$id_hosts."'"
				);
				
				// ===== Criar chaves de segurança para gerenciamento de segurança de usuários no host.
				
				$segurancaHashAlgo = $_CONFIG['platform-hash-algo'];
				$segurancaHashPass = host_configuracao_gerar_senha();
				
				$segurancaSenhaChavePrivada = host_configuracao_gerar_senha();
				
				$segurancaChaves = host_configuracao_openssl_gerar_chaves(Array(
					'tipo' => 'RSA', 
					'senha' => $segurancaSenhaChavePrivada, 
				));
				
				// ===== Criar arquivo de configuração e alterar dados do mesmo.
				
				$config = file_get_contents($_GESTOR['modulos-path'].$_GESTOR['modulo-id'].'/templates/config.template.php');
				
				$config = modelo_var_troca($config,"#bd-user#",$user_db);
				$config = modelo_var_troca($config,"#bd-name#",$user_db);
				$config = modelo_var_troca($config,"#bd-pass#",$senhaDb);
				$config = modelo_var_troca($config,"#ssl-key#",$chaves['privada']);
				$config = modelo_var_troca($config,"#ssl-pass#",$senhaChavePrivada);
				$config = modelo_var_troca($config,"#hash-algo#",$hashAlgo);
				$config = modelo_var_troca($config,"#hash-pass#",$hashPass);
				$config = modelo_var_troca($config,"#pub-id#",$pubId);
				$config = modelo_var_troca($config,"#plataforma-id#",$_GESTOR['plataforma-id']);
				$config = modelo_var_troca($config,"#plataforma-recaptcha-active#",($_CONFIG['platform-recaptcha-active'] ? 'true' : 'false'));
				$config = modelo_var_troca($config,"#plataforma-recaptcha-site#",$_CONFIG['platform-recaptcha-site']);
				$config = modelo_var_troca($config,"#host-production#",$_GESTOR['plataforma']['hosts']['producao']['host']);
				$config = modelo_var_troca($config,"#host-beta#",$_GESTOR['plataforma']['hosts']['beta']['host']);
				
				$config = modelo_var_troca($config,"#seguranca-chave-publica#",$segurancaChaves['publica']);
				$config = modelo_var_troca($config,"#seguranca-chave-privada#",$segurancaChaves['privada']);
				$config = modelo_var_troca($config,"#seguranca-chave-privada-senha#",$segurancaSenhaChavePrivada);
				$config = modelo_var_troca($config,"#seguranca-hash-algo#",$segurancaHashAlgo);
				$config = modelo_var_troca($config,"#seguranca-hash-senha#",$segurancaHashPass);
			}
			
			// ===== Criar .htaccess e index.php da plataforma do cliente.
			
			$index = file_get_contents($_GESTOR['modulos-path'].$_GESTOR['modulo-id'].'/templates/index.template.php');
			$modelo_htaccess = file_get_contents($_GESTOR['modulos-path'].$_GESTOR['modulo-id'].'/templates/template.htaccess');
			
			// ===== Definição dos caminhos do Gestor Cliente e Gestor Cliente Update
			
			$path_cliente = $_INDEX['sistemas-dir'].'b2make-gestor-cliente/';
			$path_cliente_update = $_INDEX['sistemas-dir'].'b2make-gestor-cliente-update/';
			$path_temp = sys_get_temp_dir().'/';
			$temp_id = '-'.md5(uniqid(rand(), true));
			
			// ===== Alteração do .htaccess da raiz do site com inclusão mod_write apontando para o index.php
			
			$htaccess = '';
			$htaccess_exists = false;
			$tmp_file = $path_temp.'httacces-tmp'.$temp_id;
			
			ftp_chdir($_GESTOR['ftp-conexao'],'/public_html');
			
			if(ftp_pegar_arquivo(Array('remoto' => '.htaccess','local' => $tmp_file))) {
				$htaccess = file_get_contents($tmp_file);
				$htaccess_exists = true;
				unlink($tmp_file);
			}
			
			if(preg_match('/'.preg_quote('### b2make_gestor_cliente ').'/i', $htaccess) != 0){
				$htaccess = modelo_tag_in($htaccess,'### b2make_gestor_cliente <','### b2make_gestor_cliente >','');
			}
			
			if($htaccess_exists){
				$htaccess = $htaccess . "\n" . '### b2make_gestor_cliente <' . "\n" . $modelo_htaccess . "\n" . '### b2make_gestor_cliente >';
			} else {
				$htaccess = '### b2make_gestor_cliente <' . "\n" . $modelo_htaccess . "\n" . '### b2make_gestor_cliente >';
			}
			
			$htaccess = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $htaccess);
			
			// ===== Enviar .htaccess para a /public_html do host do cliente
			
			$nome_file = '.htaccess';
			$tmp_file = $path_temp.'httacces-2-tmp'.$temp_id;
			file_put_contents($tmp_file, $htaccess);
			ftp_colocar_arquivo(Array('remoto' => $nome_file,'local' => $tmp_file));
			unlink($tmp_file);
			
			// ===== Enviar index.php para a /public_html do host do cliente
			
			$nome_file = 'index.php';
			$tmp_file = $path_temp.'index.php-tmp'.$temp_id;
			file_put_contents($tmp_file, $index);
			ftp_colocar_arquivo(Array('remoto' => $nome_file,'local' => $tmp_file));
			unlink($tmp_file);
			
			// ===== Criar pasta do 'gestor do cliente' caso o mesmo não exista no host do cliente e entrar dentro da pasta.
			
			$gestor_cliente_path = 'b2make-gestor-cliente';
			ftp_chdir($_GESTOR['ftp-conexao'],'/');
			
			if(!@ftp_chdir($_GESTOR['ftp-conexao'], $gestor_cliente_path)){
				ftp_mkdir($_GESTOR['ftp-conexao'], $gestor_cliente_path);
				ftp_chdir($_GESTOR['ftp-conexao'], $gestor_cliente_path);
			}
			
			// ===== Enviar config.php para a pasta do 'gestor do cliente' com as configurações específicas do host do cliente.
			
			if($configAtualizar){
				$nome_file = 'config.php';
				$tmp_file = $path_temp.'config.php-tmp'.$temp_id;
				file_put_contents($tmp_file, $config);
				ftp_colocar_arquivo(Array('remoto' => $nome_file,'local' => $tmp_file));
				unlink($tmp_file);
			}
			
			// ===== Enviar todos os arquivos do gestor do cliente local para 'gestor do cliente' do host do cliente
			
			$caminho_atual = false;
			$di = new RecursiveDirectoryIterator($path_cliente);
			foreach(new RecursiveIteratorIterator($di) as $filename => $file){
				if($file->getFilename() != '.' && $file->getFilename() != '..'){
					$caminho =  ltrim(str_replace($path_cliente,'',$file->getPath()),'/');
					$diretorios = explode('/',$caminho);
					
					if($caminho != $caminho_atual){
						$caminho_atual = $caminho;
						
						ftp_chdir($_GESTOR['ftp-conexao'],'/'.$gestor_cliente_path);
						
						if($diretorios[0]){
							if(count($diretorios) == 1){
								if(!@ftp_chdir($_GESTOR['ftp-conexao'], $caminho)){
									ftp_mkdir($_GESTOR['ftp-conexao'], $caminho);
									ftp_chdir($_GESTOR['ftp-conexao'], $caminho);
								}
							} else {
								foreach($diretorios as $diretorio){
									if(!@ftp_chdir($_GESTOR['ftp-conexao'], $diretorio)){
										ftp_mkdir($_GESTOR['ftp-conexao'], $diretorio);
										ftp_chdir($_GESTOR['ftp-conexao'], $diretorio);
									}
								}
							}
						}
					}
					
					ftp_colocar_arquivo(Array('remoto' => $file->getFilename(),'local' => $filename));
				}
			}
			
			// ===== Copiar script de atualização para o '/public_html' do host do cliente
			
			$update_sys = file_get_contents($path_cliente_update.'update-sys.php');
			
			ftp_chdir($_GESTOR['ftp-conexao'],'/public_html');
			
			$nome_file = 'update-sys.php';
			$tmp_file = $path_temp.'update-sys.php-tmp'.$temp_id;
			file_put_contents($tmp_file, $update_sys);
			ftp_colocar_arquivo(Array('remoto' => $nome_file,'local' => $tmp_file));
			unlink($tmp_file);
			
			// ===== Executar no cliente script de atualização
			
			$url = $dominio . '/update-sys.php';
			
			$data = false;
			
			$data['plataforma-id'] = $_GESTOR['plataforma-id'];
			
			$data = http_build_query($data);
			$curl = curl_init($url);

			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			$json = curl_exec($curl);
			
			curl_close($curl);
			
			$updateReturn = json_decode($json,true);
			
			$install_error_msg = '';$install_error = false;
			
			if(!$updateReturn){
				$install_error_msg = '[no-json] '.$json; $install_error = true;
			} else if($updateReturn['error']){
				$install_error_msg = '[error] '.$updateReturn['error'].' '.$updateReturn['error_msg']; $install_error = true;
			} else if($updateReturn['status'] != 'OK'){
				$install_error_msg = '[not-OK] '.$updateReturn['status']; $install_error = true;
			}
			
			if($install_error){
				$alert_id = '';
				switch($opcao){
					case 'atualizar': $alert_id = 'host-update-install-fatal-error'; break;
					case 'instalar': $alert_id = 'host-config-install-fatal-error'; break;
				}
				
				$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $alert_id));
				
				$alerta = modelo_var_troca($alerta,"#error#",$install_error_msg);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			}
			
			ftp_delete($_GESTOR['ftp-conexao'], 'update-sys.php');
			
			// ===== Atualizar os plugins do host.
			
			$retorno = host_configuracao_pipeline_atualizar_plugins(Array(
				'id_hosts' => $id_hosts,
				'dominio' => $dominio,
				'somenteUpdates' => true,
			));
			
			ftp_fechar_conexao();
			
			// ===== 5 segundos de pausa afim de evitar abusos.
			
			sleep(5);
			
			// ===== Atualizar sessão e remover o status 'atualizar' e atualizar o estado do host no banco de dados.
			
			$atualizar_valor = '';
			switch($opcao){
				case 'atualizar': 
					unset($host_verificacao['iniciar-atualizacao']);
					unset($host_verificacao['atualizar']);
					
					$atualizar_valor = 'NULL';
				break;
				case 'instalar':
					unset($host_verificacao['configurar']);
					
					$atualizar_valor = 'NULL';
				break;
			}
			
			banco_update
			(
				"gestor_cliente_versao='".$_GESTOR['gestor-cliente']['versao']."',".
				"gestor_cliente_versao_num=".$_GESTOR['gestor-cliente']['versao_num'].",".
				"atualizar=".$atualizar_valor,
				"hosts",
				"WHERE id_hosts='".$id_hosts."'"
			);
			
			// ===== Caso haja algum erro, é necessário tentar novamente a atualização. Para isso, mande o usuário para a página de atualização. Senão para dashboard.
			
			if($install_error){
				// ===== Modificar o status 'atualizar' para forçar o usuário ir para a página 'host-update'.
				
				$host_verificacao['atualizar'] = true;
				
				gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
				
				// ===== Atualizar no banco de dados o estado 'atualizar' do host. 
				
				banco_update
				(
					"atualizar=1",
					"hosts",
					"WHERE id_hosts='".$id_hosts."'"
				);
				
				// ===== Redirecionar o usuário para 'host-update/'.
				
				gestor_redirecionar('host-update/');
			} else {
				// ===== Se for instalação e tudo deu certo, marcar o host como 'configurado'.
				
				$instalarCamposUltimosDados = '';
				switch($opcao){
					case 'instalar':
						$instalarCamposUltimosDados = 
							"versao=1,".
							"status='A',".
							"configurado=1,";
					break;
					case 'atualizar':
						$instalarCamposUltimosDados = 
							"versao=versao+1,";
					break;
				}
				
				// ===== ALterar os dados depois.
				
				banco_update
				(
					$instalarCamposUltimosDados.
					"data_modificacao=NOW()",
					"hosts",
					"WHERE id_hosts='".$id_hosts."'"
				);
				
				// ===== Atualizar sessão e remover o 'dados-instalacao' para remover a senha.
				
				unset($host_verificacao['dados-instalacao']);
				unset($host_verificacao['atualizarConfig']);
				
				gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
				
				// ===== Variável de estado de finalização.
				
				$finalizacaoOK = true;
				
				// ===== Atualizar templates no host do cliente.
				
				gestor_incluir_biblioteca('api-cliente');
				
				$retorno = api_cliente_templates_atualizar(Array(
					'opcao' => 'update',
				));
				
				if(!$retorno['completed']){
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					$finalizacaoOK = false;
				}
				
				// ===== Atualizar variáveis no host do cliente.
				
				$retorno = api_cliente_variaveis_padroes(Array(
					'opcao' => 'gestor',
				));
				
				if(!$retorno['completed']){
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					$finalizacaoOK = false;
				}
				
				// ===== Atualizar menus no host do cliente.
				
				$retorno = api_cliente_menus(Array(
					'opcao' => 'atualizar',
				));
				
				if(!$retorno['completed']){
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					$finalizacaoOK = false;
				}
				
				// ===== Atualizar usuarios_perfis no host do cliente.
				
				$retorno = api_cliente_usuario_perfis(Array(
					'opcao' => 'atualizar',
				));
				
				if(!$retorno['completed']){
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					$finalizacaoOK = false;
				}
				
				// ===== Caso esteja tudo ok, guardar no histórico e redirecionar. Senão, redirecionar e alertar o usuário.
				
				if($finalizacaoOK){
					// ===== Alertar o usuário sobre sucesso na atualização após redirecionar o usuário para 'dashboard/'.
					
					switch($opcao){
						case 'atualizar': 
							$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-update-success'));
							$historicoTXT = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'history-update-success'));
							$historicoID = 'update-success';
						break;
						case 'instalar': 
							$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-install-success'));
							$historicoTXT = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'history-install-success'));
							$historicoID = 'install-success';
						break;
					}
					
					$alerta = modelo_var_troca($alerta,"#versao#",$_GESTOR['gestor-cliente']['versao']);
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					// ===== Incluir no histórico ocorrência de sucesso.
					
					$hosts = banco_select(Array(
						'unico' => true,
						'tabela' => 'hosts',
						'campos' => Array(
							'versao',
						),
						'extra' => 
							"WHERE id_hosts='".$id_hosts."'"
					));
					
					$historicoTXT = modelo_var_troca($historicoTXT,"#versao#",$_GESTOR['gestor-cliente']['versao']);
					
					$alteracoes[] = Array('alteracao' => $historicoID,'alteracao_txt' => $historicoTXT);
					
					interface_historico_incluir(Array(
						'alteracoes' => $alteracoes,
						'sem_id' => true,
						'versao' => (int)$hosts['versao'],
					));
				}
				
				// ===== Redirecionar o usuário para 'dashboard/' caso tenha sido instalação senão 'host-configuracao/configuracoes/'.
				
				switch($opcao){
					case 'atualizar': 
						gestor_redirecionar('host-configuracao/configuracoes/');
					break;
					case 'instalar': 
						gestor_redirecionar('dashboard/');
					break;
					default:
						gestor_redirecionar('host-configuracao/configuracoes/');
				}
				
			}
		} else {
			// ===== Senão conectar no FTP, remover os dados de instalação
			
			unset($host_verificacao['dados-instalacao']);
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Alertar o cliente sobre erro e recarregar a página
			
			$alerta = gestor_variaveis(Array('modulo' => 'ftp','id' => 'user-or-pass-invalid'));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_reload_url();
		}
	}
}

function host_configuracao_pipeline_atualizacao_plugins($params = false){
	global $_GESTOR;
	global $_INDEX;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Bloqueia que o cliente pare a execução do script caso feche a janela ou execute o reload da página.
	
	ignore_user_abort(1);
	
	// ===== Atualizar sessão e remover o status 'carregando'.
	
	unset($host_verificacao['carregando']);
	gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
	
	$dadosInstalacao = $host_verificacao['dados-instalacao-plugins'];
	
	$id_hosts = $host_verificacao['id_hosts'];
	
	// ===== Carregar dados do host
	
	$hosts = banco_select_name
	(
		banco_campos_virgulas(Array(
			'user_ftp',
			'user_db',
			'dominio',
		))
		,
		"hosts",
		"WHERE id_hosts='".$id_hosts."'"
	);
	
	// ===== Variáveis do host
	
	$user_ftp = $hosts[0]['user_ftp'];
	$user_db = $hosts[0]['user_db'];
	$dominio = $hosts[0]['dominio'];
	
	$senhaFtp = host_configuracao_decriptar($dadosInstalacao['senha-ftp']);
	$senhaDb = host_configuracao_decriptar($dadosInstalacao['senha-db']);
	
	// ===== Conectar FTP do cliente
	
	gestor_incluir_biblioteca('ftp');
	
	if(ftp_conectar(Array(
		'host' => $dominio,
		'usuario' => $user_ftp,
		'senha' => $senhaFtp,
	))){
		// ===== Atualizar os plugins.
		
		$retorno = host_configuracao_pipeline_atualizar_plugins(Array(
			'id_hosts' => $id_hosts,
			'dominio' => $dominio,
		));
		
		ftp_fechar_conexao();
		
		// ===== 5 segundos de pausa afim de evitar abusos.
		
		sleep(5);
		
		// ===== Atualizar sessão e remover o status 'atualizar' e atualizar o estado do host no banco de dados.
		
		unset($host_verificacao['iniciar-atualizacao-plugins']);
		
		// ===== Caso haja algum erro, é necessário tentar novamente a atualização. Para isso, mande o usuário para a página de atualização. Senão para dashboard.
		
		if(isset($retorno['install_error'])){
			// ===== Atualizar sessão e remover o 'dados-instalacao-plugins' para remover a senha.
			
			unset($host_verificacao['dados-instalacao-plugins']);
			
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Redirecionar o usuário para 'host-plugins/'.
			
			gestor_redirecionar('host-plugins/');
		} else {
			// ===== Plugins atualizados.
			
			if(count($retorno['plugins']) > 0){
				$pluginsAtualizados = '';
				foreach($retorno['plugins'] as $pluginID => $plugin){
					$pluginsAtualizarVariaveis[] = $pluginID;
					$pluginsAtualizados .= (existe($pluginsAtualizados) ? ', ':'').$plugin['nome'].' - '.$plugin['versao'];
				}
			} else {
				$pluginsAtualizados = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'history-update-plugins-success-none'));
			}
			
			// ===== Atualizar versão do host.
			
			banco_update
			(
				"versao=versao+1,".
				"data_modificacao=NOW()",
				"hosts",
				"WHERE id_hosts='".$id_hosts."'"
			);
			
			// ===== Atualizar sessão e remover o 'dados-instalacao-plugins' para remover a senha.
			
			unset($host_verificacao['dados-instalacao-plugins']);
			
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Variável de estado de finalização.
			
			$finalizacaoOK = true;
			
			// ===== Atualizar templates no host do cliente.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_templates_atualizar(Array(
				'opcao' => 'update',
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
				
				$finalizacaoOK = false;
			}
			
			// ===== Atualizar variáveis no host do cliente.
			
			if(isset($pluginsAtualizarVariaveis))
			foreach($pluginsAtualizarVariaveis as $pluginID){
				$retorno = api_cliente_variaveis_padroes(Array(
					'opcao' => 'plugin',
					'plugin' => $pluginID,
				));
				
				if(!$retorno['completed']){
					$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
					
					$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					$finalizacaoOK = false;
				}
			}
			
			// ===== Atualizar menus no host do cliente.
			
			$retorno = api_cliente_menus(Array(
				'opcao' => 'atualizar',
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
				
				$finalizacaoOK = false;
			}
			
			// ===== Caso esteja tudo ok, guardar no histórico e redirecionar. Senão, redirecionar e alertar o usuário.
			
			if($finalizacaoOK){
				// ===== Alertar o usuário sobre sucesso na atualização após redirecionar o usuário para 'dashboard/'.
				
				$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-update-plugins-success'));
				$historicoTXT = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'history-update-plugins-success'));
				$historicoID = 'update-plugins-success';
				
				$alerta = modelo_var_troca($alerta,"#plugins#",$pluginsAtualizados);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
				
				// ===== Incluir no histórico ocorrência de sucesso.
				
				$hosts = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts',
					'campos' => Array(
						'versao',
					),
					'extra' => 
						"WHERE id_hosts='".$id_hosts."'"
				));
				
				$historicoTXT = modelo_var_troca($historicoTXT,"#plugins#",$pluginsAtualizados);
				
				$alteracoes[] = Array('alteracao' => $historicoID,'alteracao_txt' => $historicoTXT);
				
				interface_historico_incluir(Array(
					'alteracoes' => $alteracoes,
					'sem_id' => true,
					'versao' => (int)$hosts['versao'],
				));
			}
			
			// ===== Redirecionar o usuário para 'host-configuracao/configuracoes/'.
			
			gestor_redirecionar('host-configuracao/configuracoes/');
		}
	} else {
		// ===== Senão conectar no FTP, remover os dados de instalação
		
		unset($host_verificacao['iniciar-atualizacao-plugins']);
		unset($host_verificacao['dados-instalacao-plugins']);
		
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		// ===== Alertar o cliente sobre erro e recarregar a página
		
		$alerta = gestor_variaveis(Array('modulo' => 'ftp','id' => 'user-or-pass-invalid'));
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
		
		gestor_reload_url();
	}
}

function host_configuracao_pipeline_atualizar_plugins($params = false){
	global $_GESTOR;
	global $_INDEX;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_hosts - Int - Obrigatório - Identificador do host.
	// dominio - String - Obrigatório - Domínio do host.
	// somenteUpdates - Bool - Opcional - Se definido somente atualizar caso não esteja habilçi.
	
	// ===== 
	
	$pluginsRetorno = Array();
	
	// ===== Verificar se os campos obrigatórios foram informados.
	
	if(!isset($id_hosts) || !isset($dominio)){
		return Array(
			'install_error' => true,
		);
	}
	
	// ===== Pegar os plugins e os plugins do host do banco de dados.
	
	$hosts_plugins = banco_select(Array(
		'tabela' => 'hosts_plugins',
		'campos' => Array(
			'id_hosts_plugins',
			'plugin',
			'habilitado',
			'versao_num',
		),
		'extra' => 
			"WHERE id_hosts='".$id_hosts."'"
	));
	
	$plugins = banco_select(Array(
		'tabela' => 'plugins',
		'campos' => Array(
			'nome',
			'id',
		),
		'extra' => 
			"WHERE status='A'"
			." ORDER BY nome ASC"
	));
	
	// ===== Criar pasta do 'gestor cliente' caso o mesmo não exista no host do cliente e entrar dentro da pasta.
	
	$gestor_cliente_path = 'b2make-gestor-cliente';
	ftp_chdir($_GESTOR['ftp-conexao'],'/');
	
	if(!@ftp_chdir($_GESTOR['ftp-conexao'], $gestor_cliente_path)){
		ftp_mkdir($_GESTOR['ftp-conexao'], $gestor_cliente_path);
		ftp_chdir($_GESTOR['ftp-conexao'], $gestor_cliente_path);
	}
	
	// ===== Criar pasta dos 'plugins' caso o mesmo não exista no host do cliente e entrar dentro da pasta.
	
	$gestor_plugins_path = 'plugins';
	
	if(!@ftp_chdir($_GESTOR['ftp-conexao'], $gestor_plugins_path)){
		ftp_mkdir($_GESTOR['ftp-conexao'], $gestor_plugins_path);
		ftp_chdir($_GESTOR['ftp-conexao'], $gestor_plugins_path);
	}
	
	// ===== Caminho padrão de qualquer plugin.
	
	$gestor_plugin_path_default = '/' . $gestor_cliente_path . '/' . $gestor_plugins_path;
	
	// ===== Varrer todos os plugins e atualizar cada caso.
	
	if($plugins){
		foreach($plugins as $plugin){
			if($hosts_plugins){
				foreach($hosts_plugins as $hosts_plugin){
					if($plugin['id'] == $hosts_plugin['plugin']){
						// ===== Dados do plugin.
						
						$pluginID = $plugin['id'];
						$pluginNome = $plugin['nome'];
						$habilitado = ($hosts_plugin['habilitado'] ? true : false);
						$versao_num = $hosts_plugin['versao_num'];
						$id_hosts_plugins = $hosts_plugin['id_hosts_plugins'];
						
						// ===== Sinalizador para mudança de pasta. Caso seja a segunda iteração, voltar a pasta para o plugins.
						
						if(!isset($pastaInicial)){
							$pastaInicial = true;
						} else {
							ftp_chdir($_GESTOR['ftp-conexao'],$gestor_plugin_path_default);
						}
						
						// ===== Pegar os dados de configuração do plugin.
						
						$pluginConfig = require($_GESTOR['plugins-path'].$pluginID.'/config.php');
						
						// ===== Caso tenha sido definido o somenteUpdates, apenas atualizar caso os plugins não tenham sido instalados e/ou a versão é mais nova.
						
						if(isset($somenteUpdates)){
							if(
								$habilitado &&
								(int)$versao_num == (int)$pluginConfig['versao_num']
							){
								continue;
							}
						}
						
						// ===== Definição dos caminhos do Plugin localmente
						
						$path_plugin = $_GESTOR['plugins-path'].$pluginID.'/remoto/';
						$path_plugin_update = $_GESTOR['plugins-path'].$pluginID.'/update/';
						$path_temp = sys_get_temp_dir().'/';
						$temp_id = '-'.md5(uniqid(rand(), true));
						
						// ===== Se não existir o diretório remoto, não continuar.
						
						if(!is_dir($path_plugin)){
							continue;
						}
						
						// ===== Criar a pasta do 'plugin' caso o mesmo não exista no host do cliente e entrar dentro da pasta.
						
						$gestor_plugin_path = $gestor_plugin_path_default . '/' . $pluginID;
						
						if(!@ftp_chdir($_GESTOR['ftp-conexao'], $gestor_plugin_path)){
							ftp_mkdir($_GESTOR['ftp-conexao'], $gestor_plugin_path);
							ftp_chdir($_GESTOR['ftp-conexao'], $gestor_plugin_path);
						}
						
						// ===== Enviar todos os arquivos do plugin para o host do cliente
						
						$caminho_atual = false;
						$di = new RecursiveDirectoryIterator($path_plugin);
						foreach(new RecursiveIteratorIterator($di) as $filename => $file){
							if($file->getFilename() != '.' && $file->getFilename() != '..'){
								$caminho =  ltrim(str_replace($path_plugin,'',$file->getPath()),'/');
								$diretorios = explode('/',$caminho);
								
								if($caminho != $caminho_atual){
									$caminho_atual = $caminho;
									
									ftp_chdir($_GESTOR['ftp-conexao'],$gestor_plugin_path);
									
									if($diretorios[0]){
										if(count($diretorios) == 1){
											if(!@ftp_chdir($_GESTOR['ftp-conexao'], $caminho)){
												ftp_mkdir($_GESTOR['ftp-conexao'], $caminho);
												ftp_chdir($_GESTOR['ftp-conexao'], $caminho);
											}
										} else {
											foreach($diretorios as $diretorio){
												if(!@ftp_chdir($_GESTOR['ftp-conexao'], $diretorio)){
													ftp_mkdir($_GESTOR['ftp-conexao'], $diretorio);
													ftp_chdir($_GESTOR['ftp-conexao'], $diretorio);
												}
											}
										}
									}
								}
								
								ftp_colocar_arquivo(Array('remoto' => $file->getFilename(),'local' => $filename));
							}
						}
						
						// ===== Copiar o SQL de atualização do plugin para o '/public_html' do host do cliente
						
						$update_sql = file_get_contents($path_plugin_update.'update.sql');
						
						ftp_chdir($_GESTOR['ftp-conexao'],'/public_html');
						
						$nome_file = 'update.sql';
						$tmp_file = $path_temp.$nome_file.'-tmp'.$temp_id;
						file_put_contents($tmp_file, $update_sql);
						ftp_colocar_arquivo(Array('remoto' => $nome_file,'local' => $tmp_file));
						unlink($tmp_file);
						
						// ===== Copiar script de atualização do plugin para o '/public_html' do host do cliente
						
						$update_sys = file_get_contents($path_plugin_update.'update-plugin.php');
						
						ftp_chdir($_GESTOR['ftp-conexao'],'/public_html');
						
						$nome_file = 'update-plugin.php';
						$tmp_file = $path_temp.'update-plugin.php-tmp'.$temp_id;
						file_put_contents($tmp_file, $update_sys);
						ftp_colocar_arquivo(Array('remoto' => $nome_file,'local' => $tmp_file));
						unlink($tmp_file);
						
						// ===== Executar no cliente script de atualização do plugin
						
						$url = $dominio . '/update-plugin.php';
						
						$data = false;
						
						$data['plataforma-id'] = $_GESTOR['plataforma-id'];
						
						$data = http_build_query($data);
						$curl = curl_init($url);

						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
						$json = curl_exec($curl);
						
						curl_close($curl);
						
						$updateReturn = json_decode($json,true);
						
						$install_error_msg = '';$install_error = false;
						
						if(!$updateReturn){
							$install_error_msg = '[no-json] '.$json; $install_error = true;
						} else if($updateReturn['error']){
							$install_error_msg = '[error] '.$updateReturn['error'].' '.$updateReturn['error_msg']; $install_error = true;
						} else if($updateReturn['status'] != 'OK'){
							$install_error_msg = '[not-OK] '.$updateReturn['status']; $install_error = true;
						}
						
						ftp_delete($_GESTOR['ftp-conexao'], 'update-plugin.php');
						ftp_delete($_GESTOR['ftp-conexao'], 'update.sql');
						
						if($install_error){
							$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-update-plugin-fatal-error'));
							
							$alerta = modelo_var_troca($alerta,"#plugin#",$pluginNome);
							$alerta = modelo_var_troca($alerta,"#error#",$install_error_msg);
							
							interface_alerta(Array(
								'redirect' => true,
								'msg' => $alerta
							));
							
							return Array(
								'install_error' => true,
							);
						}
						
						// ===== Marcar o plugin como habilitado no banco de dados. E atualizar a versão
						
						banco_update_campo('versao',$pluginConfig['versao']);
						banco_update_campo('versao_num',$pluginConfig['versao_num']);
						banco_update_campo('habilitado','1',true);
						banco_update_campo('data_modificacao','NOW()',true);
						
						banco_update_executar('hosts_plugins',"WHERE id_hosts_plugins='".$id_hosts_plugins."'");
						
						// ===== Adicionar plugin ao retorno.
						
						$pluginsRetorno[$pluginID] = Array(
							'nome' => $pluginNome,
							'versao' => $pluginConfig['versao'],
						);
					}
				}
			}
		}
	}
	
	// ===== Retornar os plugins com suas versões.
	
	return Array(
		'plugins' => $pluginsRetorno,
		'status' => 'OK',
	);
}

// ===== Funções de Plataforma

function host_configuracao_plataforma_testes(){
	global $_GESTOR;
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
}

// ===== Funções Principais

function host_configuracao_instalar(){
	global $_GESTOR;
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Verifica se está marcado para instalar
	
	if(isset($host_verificacao['instalar'])){
		if(!isset($host_verificacao['dados-instalacao']) && !isset($_REQUEST['senha-ftp'])){ 
			// ===== 1º Etapa: Pré-Configuração e criação do formulário com dados iniciais - Formulário com Dados de FTP e Banco de Dados. 
			
			$id_hosts = $host_verificacao['id_hosts'];
			
			// ===== Verificar se o host necessita ser pré-configurado
			
			$hosts = banco_select_name
			(
				banco_campos_virgulas(Array(
					'pre_configurado',
					'user_ftp',
					'user_db',
				))
				,
				"hosts",
				"WHERE id_hosts='".$id_hosts."'"
			);
			
			if(!$hosts[0]['pre_configurado']){
				// ===== Criar pré-configuração do host
				
				$num_total_rows = banco_total_rows
				(
					"hosts",
					""
				);
				
				$user_cpanel = $_GESTOR['hosts-server']['user-perfix'].$num_total_rows;
				
				$user_ftp = $user_cpanel.$_GESTOR['hosts-server']['ftp-user-sufix'];
				$user_db = $user_cpanel.$_GESTOR['hosts-server']['db-user-sufix'];
				$dominio = $user_cpanel.'.'.$_GESTOR['hosts-server']['dominio'];
				
				$user_ftp = $user_ftp . '@' . $dominio;
				
				// ===== Pré-configurar host no banco de dados
				
				$campo_tabela = "hosts";
				$campo_tabela_extra = "WHERE id_hosts='".$id_hosts."'";
				
				$campo_nome = "user_cpanel"; 				$campo_valor = $user_cpanel; 							$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "user_ftp"; 					$campo_valor = $user_ftp; 								$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "user_db"; 					$campo_valor = $user_db; 								$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "dominio"; 					$campo_valor = $dominio; 								$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "server"; 					$campo_valor = $_GESTOR['hosts-server']['server']; 		$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "pre_configurado"; 			$campo_valor = '1'; 									$editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
				
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
			} else {
				$user_ftp = $hosts[0]['user_ftp'];
				$user_db = $hosts[0]['user_db'];
			}
			
			// ===== Esconder os usuários por questão de segurança
			
			$user_ftp = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-user-secret'));
			$user_db = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-db-user-secret'));
			
			// ===== Inclusão Módulo JS
	
			gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
			gestor_pagina_javascript_incluir();
			
			// ===== Interface finalizar opções
			
			$formulario['validacao'] = Array(
				Array(
					'regra' => 'senha-comparacao',
					'campo' => 'senha-ftp',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-label')),
					'identificador' => 'senha-ftp',
					'comparcao' => Array(
						'id' => 'senha-ftp-2',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-2-label')),
					)
				),
				Array(
					'regra' => 'senha-comparacao',
					'campo' => 'senha-ftp-2',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-2-label')),
					'identificador' => 'senha-ftp-2',
					'comparcao' => Array(
						'id' => 'senha-ftp',
						'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-label')),
						'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-2-label')),
					)
				),
			);
			
			interface_formulario_validacao($formulario);
			
		} else if(isset($_REQUEST['senha-ftp'])){
			// ===== 2º Etapa: Armazenar provisoriamente as senhas do FTP e do Banco de Dados - Redirecionar para criar tela "carregando". 
			
			// ===== Dados Instalação
			
			$senhaFtp = banco_escape_field($_REQUEST['senha-ftp']);
			
			$host_verificacao['dados-instalacao'] = Array(
				'senha-ftp' => host_configuracao_encriptar($senhaFtp),
				'senha-db' => host_configuracao_encriptar(hash("sha256",$senhaFtp)),
			);
			
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Reload para iniciar o carregando
			
			gestor_redirecionar('host-install/');
		} else if(!isset($host_verificacao['carregando'])){
			// ===== 3º Etapa: Iniciar tela carregando e disparo da próxima etapa via JavaScript afim da tela carregando ficar visivel ao usuário. 
			
			// ===== Atualizar sessão e incluir o status 'carregando'.
			
			$host_verificacao['carregando'] = true;
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Informar o JS para mostrar recarregar a mesma página e iniciar o processo de instalação.
			
			$_GESTOR['javascript-vars']['hostCarregando'] = true;
			
			// ===== Inclusão Módulo JS
			
			gestor_pagina_javascript_incluir();
			
			// ===== Layout de carregamento da instalação
			
			$_GESTOR['pagina'] = gestor_componente(Array(
				'id' => 'host-install-carregando',
			));
		} else if(isset($host_verificacao['dados-instalacao'])){
			// ===== 4º Etapa: Instalação do novo host no cPanel.
			
			// ===== Bloqueia que o cliente pare a execução do script caso feche a janela ou execute o reload da página.
			
			ignore_user_abort(1);
			
			// ===== Atualizar sessão e remover o status 'carregando'.
			
			unset($host_verificacao['carregando']);
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			$dadosInstalacao = $host_verificacao['dados-instalacao'];
			
			// ===== Processamento da instalação
			
			if($_GESTOR['hosts-server']['ativo']){
				$id_hosts = $host_verificacao['id_hosts'];
				
				// ===== Carregar dados do host
				
				$hosts = banco_select_name
				(
					banco_campos_virgulas(Array(
						'dominio',
						'user_cpanel',
						'user_ftp',
						'user_db',
					))
					,
					"hosts",
					"WHERE id_hosts='".$id_hosts."'"
				);
				
				// ===== Variáveis do host
				
				$ftp_site_root = $_GESTOR['hosts-server']['ftp-root'];
				$usuario = gestor_usuario();
				
				$user_cpanel = $hosts[0]['user_cpanel'];
				$user_ftp = $hosts[0]['user_ftp'];
				$user_db = $hosts[0]['user_db'];
				$dominio = $hosts[0]['dominio'];
				
				$senhaFtp = host_configuracao_decriptar($dadosInstalacao['senha-ftp']);
				$senhaDb = host_configuracao_decriptar($dadosInstalacao['senha-db']);
				
				// ===== Executar cPanel API
				
				global $_CPANEL;
				
				$_CPANEL['FTP_LOCAL'] = $_GESTOR['hosts-server']['local'];
				$_CPANEL['ACCT']['user'] = $user_cpanel;
				$_CPANEL['ACCT']['pass'] = $senhaFtp;
				$_CPANEL['ACCT']['domain'] = $dominio;
				$_CPANEL['ACCT']['plan'] = $_GESTOR['hosts-server']['pacote-inicial'];
				$_CPANEL['ACCT']['email'] = $usuario['email'];
				
				require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-createacct.php');

				$_CPANEL['CPANEL_USER'] = $user_cpanel;
				$_CPANEL['FTP_ADD'] = Array(
					'user' => $user_ftp,
					'pass' => $senhaFtp,
					'homedir' => $ftp_site_root,
					'quota' => '0',
				);
				
				require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-ftp-add.php');
				
				$_CPANEL['FTP_LOCAL'] = $_GESTOR['hosts-server']['local'];
				$_CPANEL['DB_ADD']['cpuser'] = $user_cpanel;
				$_CPANEL['DB_ADD']['user'] = $user_db;
				$_CPANEL['DB_ADD']['name'] = $user_db;
				$_CPANEL['DB_ADD']['pass'] = $senhaDb;
				
				require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-createdb.php');
				
				// ===== Enviar no email do usuário as senhas
				
				$numero = date('dmY').$id_hosts;
				$usuario = gestor_usuario();
				
				$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-mail-subject')),"#numero#",$numero);
				
				// ===== Esconder os usuários por questão de segurança
				
				$user_ftp = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-user-secret'));
				$user_db = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-db-user-secret'));
				
				// ===== Enviar email
				
				gestor_incluir_biblioteca('comunicacao');
				
				if(comunicacao_email(Array(
					'destinatarios' => Array(
						Array(
							'email' => $usuario['email'],
							'nome' => $usuario['nome'],
						),
					),
					'mensagem' => Array(
						'assunto' => $assunto,
						'htmlLayoutID' => 'layout-email-host-instalacao',
						'htmlVariaveis' => Array(
							Array(
								'variavel' => '#nome#',
								'valor' => $usuario['nome'],
							),
							Array(
								'variavel' => '#ftp-user#',
								'valor' => $user_ftp,
							),
							Array(
								'variavel' => '#ftp-pass#',
								'valor' => $senhaFtp,
							),
							Array(
								'variavel' => '#db-user#',
								'valor' => $user_db,
							),
							Array(
								'variavel' => '#db-pass#',
								'valor' => $senhaDb,
							),
							Array(
								'variavel' => '#assinatura#',
								'valor' => gestor_componente(Array(
									'id' => 'layout-emails-assinatura',
								)),
							),
						),
					),
				))){
					// Email de confirmação enviado com sucesso!
				}
			}
			
			// ===== 20 segundos de pausa afim de evitar abusos e dar tempo para o domínio do host estar ativo.
			
			sleep(20);
			
			// ===== Atualizar sessão e remover o status 'instalar'.
			
			unset($host_verificacao['instalar']);
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			banco_update
			(
				"instalado=1",
				"hosts",
				"WHERE id_hosts='".$id_hosts."'"
			);
			
			// ===== Redirecionar o usuário para configuração do novo host.
			
			gestor_redirecionar('host-config/');
		} else {
			// ===== Se houver algum erro inesperado no pipeline de instalação, mostrar a tela com o seguinte erro a mostra:
			
			$_GESTOR['pagina'] = '[host-configuracao][install] Error not expected: data install not defined!';
		}
	} else {
		gestor_redirecionar('dashboard/');
	}
}

function host_configuracao_configurar(){
	global $_GESTOR;
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Verifica se está marcado para configurar
	
	if(isset($host_verificacao['configurar'])){
		if(!isset($host_verificacao['dados-instalacao']) && !isset($_REQUEST['senha-ftp'])){ 
			// ===== 1º Etapa: se não existe dados da instalação, executar form para pegar as senhas da conta FTP.
			
			// ===== Inclusão Módulo JS
	
			gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
			gestor_pagina_javascript_incluir();
			
			// ===== Interface finalizar opções
			
			$formulario['validacao'] = Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha-ftp',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-label')),
				),
			);
			
			interface_formulario_validacao($formulario);
			
			// ===== Guardar sessão para criar redirect caso esqueça senha
			
			gestor_sessao_variavel('host-configuracao-forgot-password-redirect','host-config');
		}  else if(isset($_REQUEST['senha-ftp'])){
			// ===== 2º Etapa: Armazenar provisoriamente as senhas do FTP e do Banco de Dados - Redirecionar para criar tela "carregando". 
			
			// ===== Dados Configuração
			
			$senhaFtp = banco_escape_field($_REQUEST['senha-ftp']);
			
			$host_verificacao['dados-instalacao'] = Array(
				'senha-ftp' => host_configuracao_encriptar($senhaFtp),
				'senha-db' => host_configuracao_encriptar(hash("sha256",$senhaFtp)),
			);
			
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Reload para iniciar o carregando
			
			gestor_redirecionar('host-config/');
		} else if(!isset($host_verificacao['carregando'])){
			// ===== 3º Etapa: Iniciar tela carregando e disparo da próxima etapa via JavaScript afim da tela carregando ficar visivel ao usuário. 
			
			// ===== Atualizar sessão e incluir o status 'carregando'.
			
			$host_verificacao['carregando'] = true;
			gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
			
			// ===== Informar o JS para mostrar recarregar a mesma página e iniciar o processo de configuração.
			
			$_GESTOR['javascript-vars']['hostConfigCarregando'] = true;
			
			// ===== Inclusão Módulo JS
			
			gestor_pagina_javascript_incluir();
			
			// ===== Layout de carregamento da instalação
			
			$_GESTOR['pagina'] = gestor_componente(Array(
				'id' => 'host-config-carregando',
			));
		} else if(isset($host_verificacao['dados-instalacao'])){
			// ===== 4º Etapa: Configurando o host do cliente.
			
			host_configuracao_pipeline_atualizacao(Array(
				'opcao' => 'instalar',
			));
		} else {
			// ===== Se houver algum erro inesperado no pipeline de configuração, mostrar a tela com o seguinte erro a mostra:
			
			$_GESTOR['pagina'] = '[host-configuracao][config] Error not expected: data config not defined!';
		}
	} else {
		gestor_redirecionar('dashboard/');
	}
}

function host_configuracao_atualizar(){
	global $_GESTOR;
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	if(!isset($host_verificacao['iniciar-atualizacao']) && !isset($_REQUEST['atualizar'])){ 
		// ===== 1º Etapa: mostrar versão atual e o botão para iniciar a atualização.
		
		// ===== Inclusão Módulo JS

		gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
		gestor_pagina_javascript_incluir();
		
		// ===== Remover célula 'conta-ftp'.
		
		$cel_nome = 'conta-ftp'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		// ===== Verificar versão do gestor cliente.
		
		$id_hosts = $host_verificacao['id_hosts'];
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'gestor_cliente_versao',
				'gestor_cliente_versao_num',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		$gestor_cliente_versao = $hosts[0]['gestor_cliente_versao'];
		$gestor_cliente_versao_num = $hosts[0]['gestor_cliente_versao_num'];
		
		// ===== Comparar versões e montar a interface. Ou é atualização normal, dado que há uma versão mais nova, ou então se quiser forçar a atualização afim de sobrescrever os dados no hospedeiro do cliente.
		
		if($_GESTOR['gestor-cliente']['versao_num'] > (int)$gestor_cliente_versao_num){
			$botaoAtualizacao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-update-do-button-label'));
			$mensagemAtualizacao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-update-do-msg-content'));
			
			$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-cliente#",$gestor_cliente_versao);
			$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-plaforma#",$_GESTOR['gestor-cliente']['versao']);
			
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#botao-atualizacao#",$botaoAtualizacao);
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#mensagem-atualizacao#",$mensagemAtualizacao);
		} else {
			$botaoAtualizacao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-update-force-button-label'));
			$mensagemAtualizacao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-update-force-msg-content'));
			
			$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-cliente#",$gestor_cliente_versao);
			$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-plaforma#",$_GESTOR['gestor-cliente']['versao']);
			
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#botao-atualizacao#",$botaoAtualizacao);
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#mensagem-atualizacao#",$mensagemAtualizacao);
		}
	} else if(isset($_REQUEST['atualizar'])){
		// ===== 2º Etapa: criar status 'iniciar-atualizacao' e redirecionar. 
		
		$host_verificacao['iniciar-atualizacao'] = true;
		
		// ===== Atualizar arquivo de configuração.
		
		if(isset($_REQUEST['atualizarConfig'])){
			$host_verificacao['atualizarConfig'] = true;
		}
		
		// ===== Guardar sessão.
		
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		// ===== Reload para iniciar o carregando
		
		gestor_redirecionar('host-update/');
	} else if(!isset($host_verificacao['dados-instalacao']) && !isset($_REQUEST['senha-ftp'])){ 
		// ===== 3º Etapa: se não existe dados da instalação, executar form para pegar as senhas da conta FTP.
		
		// ===== Inclusão Módulo JS

		gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
		gestor_pagina_javascript_incluir();
		
		// ===== Remover célula 'atualizacao'
		
		$cel_nome = 'atualizacao'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		// ===== Interface finalizar opções
		
		$formulario['validacao'] = Array(
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'senha-ftp',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-label')),
			),
		);
		
		interface_formulario_validacao($formulario);
		
		// ===== Guardar sessão para criar redirect caso esqueça senha.
		
		gestor_sessao_variavel('host-configuracao-forgot-password-redirect','host-update');
	} else if(isset($_REQUEST['senha-ftp'])){
		// ===== 4º Etapa: Armazenar provisoriamente as senhas do FTP e do Banco de Dados - Redirecionar para criar tela "carregando". 
		
		// ===== Dados Configuração
		
		$senhaFtp = banco_escape_field($_REQUEST['senha-ftp']);
		
		$host_verificacao['dados-instalacao'] = Array(
			'senha-ftp' => host_configuracao_encriptar($senhaFtp),
			'senha-db' => host_configuracao_encriptar(hash("sha256",$senhaFtp)),
		);
		
		// ===== Guardar a sessão.
		
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		// ===== Reload para iniciar o carregando
		
		gestor_redirecionar('host-update/');
	} else if(!isset($host_verificacao['carregando'])){
		// ===== 5º Etapa: Iniciar tela carregando e disparo da próxima etapa via JavaScript afim da tela carregando ficar visivel ao usuário. 
		
		// ===== Atualizar sessão e incluir o status 'carregando'.
		
		$host_verificacao['carregando'] = true;
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		// ===== Informar o JS para mostrar 'carregando'. Recarregar a mesma página e iniciar o processo de configuração.
		
		$_GESTOR['javascript-vars']['hostUpdateCarregando'] = true;
		
		// ===== Inclusão Módulo JS
		
		gestor_pagina_javascript_incluir();
		
		// ===== Layout de carregamento da instalação
		
		$_GESTOR['pagina'] = gestor_componente(Array(
			'id' => 'host-update-carregando',
		));
	} else if(isset($host_verificacao['dados-instalacao'])){
		// ===== 6º Etapa: Atualizar o host do cliente.
		
		host_configuracao_pipeline_atualizacao(Array(
			'opcao' => 'atualizar',
			'atualizarConfig' => (isset($host_verificacao['atualizarConfig']) ? true : null),
		));
		
	} else {
		// ===== Se houver algum erro inesperado no pipeline de configuração, mostrar a tela com o seguinte erro a mostra:
		
		$_GESTOR['pagina'] = '[host-configuracao][update] Error not expected: data config not defined!';
	}
}

function host_configuracao_plugins(){
	global $_GESTOR;
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	if(!isset($host_verificacao['iniciar-atualizacao-plugins']) && !isset($_REQUEST['atualizar'])){ 
		// ===== 1º Etapa: mostrar os plugins habilitados e dar opção de habilitar/desabilitar plugin.
		
		// ===== Inclusão Módulo JS

		gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
		gestor_pagina_javascript_incluir();
		
		// ===== Remover célula 'conta-ftp'.
		
		$cel_nome = 'conta-ftp'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		// ===== Verificar os plugins padrões e os habilitados no banco de dados.
		
		$id_hosts = $host_verificacao['id_hosts'];
		
		$hosts_plugins = banco_select(Array(
			'tabela' => 'hosts_plugins',
			'campos' => Array(
				'plugin',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
		));
		
		// ===== Caso exista plugins, listar os mesmos. Senão, mostrar mensagem de não haver plugins habilitados.
		
		$plugins = banco_select(Array(
			'tabela' => 'plugins',
			'campos' => Array(
				'nome',
				'id',
			),
			'extra' => 
				"WHERE status!='D'"
				." ORDER BY nome ASC"
		));
		
		// ===== Montar os plugins na tela para a escolha dos mesmos.
		
		$cel_nome = 'plugins'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		if($plugins){
			foreach($plugins as $plugin){
				$habilitado = false;
				
				if($hosts_plugins){
					foreach($hosts_plugins as $hosts_plugin){
						if($plugin['id'] == $hosts_plugin['plugin']){
							$habilitado = true;
							break;
						}
					}
				}
				
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$plugin['nome']);
				$cel_aux = modelo_var_troca($cel_aux,"#name#",$plugin['id']);
				$cel_aux = modelo_var_troca($cel_aux,"#checked#",($habilitado ? 'checked' : ''));
				
				$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->',$cel_aux);				
			}
		}
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->','');
	} else if(isset($_REQUEST['atualizar'])){
		// ===== 2º Etapa: criar status 'iniciar-atualizacao-plugins', habilitar os mesmos no banco e redirecionar.
		// ===== Guardar no banco de dados os plugins escolhidos.
		
		$id_hosts = $host_verificacao['id_hosts'];
		
		$hosts_plugins = banco_select(Array(
			'tabela' => 'hosts_plugins',
			'campos' => Array(
				'plugin',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
		));
		
		$plugins = banco_select(Array(
			'tabela' => 'plugins',
			'campos' => Array(
				'nome',
				'id',
			),
			'extra' => 
				"WHERE status!='D'"
				." ORDER BY nome ASC"
		));
		
		if($plugins){
			foreach($plugins as $plugin){
				$naoEstavaHabilitado = true;
				
				if($hosts_plugins){
					foreach($hosts_plugins as $hosts_plugin){
						if($plugin['id'] == $hosts_plugin['plugin']){
							$naoEstavaHabilitado = false;
							break;
						}
					}
				}
				
				if($naoEstavaHabilitado){
					if(existe($_REQUEST[$plugin['id']])){
						banco_insert_name_campo('plugin',$plugin['id']);
						banco_insert_name_campo('id_hosts',$id_hosts);
						banco_insert_name_campo('versao_config','1');
						banco_insert_name_campo('data_criacao','NOW()',true);
						banco_insert_name_campo('data_modificacao','NOW()',true);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"hosts_plugins"
						);
					}
				} else {
					if(!existe($_REQUEST[$plugin['id']])){
						banco_delete
						(
							"hosts_plugins",
							"WHERE id_hosts='".$id_hosts."'"
							." AND plugin='".$plugin['id']."'"
						);
					}
				}
			}
		}
		
		// ===== Atualizar a opção da sessão.
		
		$host_verificacao['iniciar-atualizacao-plugins'] = true;
		
		// ===== Guardar sessão.
		
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		// ===== Reload para iniciar o carregando
		
		gestor_redirecionar('host-plugins/');
	} else if(!isset($host_verificacao['dados-instalacao-plugins']) && !isset($_REQUEST['senha-ftp'])){ 
		// ===== 3º Etapa: se não existe dados da instalação, executar form para pegar as senhas da conta FTP.
		
		// ===== Inclusão Módulo JS

		gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
		gestor_pagina_javascript_incluir();
		
		// ===== Remover célula 'atualizacao'
		
		$cel_nome = 'atualizacao'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		// ===== Interface finalizar opções
		
		$formulario['validacao'] = Array(
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'senha-ftp',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-install-ftp-pass-label')),
			),
		);
		
		interface_formulario_validacao($formulario);
		
		// ===== Guardar sessão para criar redirect caso esqueça senha.
		
		gestor_sessao_variavel('host-configuracao-forgot-password-redirect','host-plugins');
	} else if(isset($_REQUEST['senha-ftp'])){
		// ===== 4º Etapa: Armazenar provisoriamente as senhas do FTP e do Banco de Dados - Redirecionar para criar tela "carregando". 
		
		// ===== Dados Configuração
		
		$senhaFtp = banco_escape_field($_REQUEST['senha-ftp']);
		
		$host_verificacao['dados-instalacao-plugins'] = Array(
			'senha-ftp' => host_configuracao_encriptar($senhaFtp),
			'senha-db' => host_configuracao_encriptar(hash("sha256",$senhaFtp)),
		);
		
		// ===== Guardar a sessão.
		
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		// ===== Reload para iniciar o carregando
		
		gestor_redirecionar('host-plugins/');
	} else if(!isset($host_verificacao['carregando'])){
		// ===== 5º Etapa: Iniciar tela carregando e disparo da próxima etapa via JavaScript afim da tela carregando ficar visivel ao usuário. 
		
		// ===== Atualizar sessão e incluir o status 'carregando'.
		
		$host_verificacao['carregando'] = true;
		gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
		
		// ===== Informar o JS para mostrar 'carregando'. Recarregar a mesma página e iniciar o processo de configuração.
		
		$_GESTOR['javascript-vars']['hostUpdateCarregando'] = true;
		
		// ===== Inclusão Módulo JS
		
		gestor_pagina_javascript_incluir();
		
		// ===== Layout de carregamento da atualização dos plugins
		
		$_GESTOR['pagina'] = gestor_componente(Array(
			'id' => 'host-plugins-update-carregando',
		));
	} else if(isset($host_verificacao['dados-instalacao-plugins'])){
		// ===== 6º Etapa: Atualizar o host do cliente.
		
		host_configuracao_pipeline_atualizacao_plugins(Array(
			//'opcao' => 'atualizar',
		));
		
	} else {
		// ===== Se houver algum erro inesperado no pipeline de configuração, mostrar a tela com o seguinte erro a mostra:
		
		$_GESTOR['pagina'] = '[host-configuracao][update] Error not expected: data config not defined!';
	}
}

function host_configuracao_configuracoes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Configurações das variáveis.
	
	$config = gestor_incluir_configuracao(Array(
		'id' => $_GESTOR['modulo-id'].'.config',
	));
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Pegar dados do banco.
	
		$id_hosts = $host_verificacao['id_hosts'];
		
		$hosts = banco_select(Array(
			'unico' => true,
			'tabela' => 'hosts',
			'campos' => Array(
				'versao',
				'dominio_proprio',
				'dominio_proprio_url',
				'google_recaptcha_tipo',
			),
			'extra' => 
				"WHERE id_hosts='".$id_hosts."'"
		));
		
		// ===== Verificar se houve alteração do domínio.
		
		switch($_REQUEST['tipo']){
			case 'sistema':
				if($hosts['dominio_proprio']){
					$alterou_dominio_proprio = true;
					$dominio_proprio = 'NULL';
					$tipoDominioAntes = 'proprio';
					$tipoDominioDepois = 'sistema';
				}
			break;
			case 'proprio':
				if(!$hosts['dominio_proprio']){
					$alterou_dominio_proprio = true;
					$dominio_proprio = '1';
					$tipoDominioAntes = 'sistema';
					$tipoDominioDepois = 'proprio';
				}
			break;
		}
		
		// ===== Controle do Google reCAPTCHA.
		
		if(isset($_REQUEST['google-recaptcha-comando'])){
			switch($_REQUEST['google-recaptcha-comando']){
				case 'instalar':
				case 'reinstalar':
					switch($_REQUEST['google-recaptcha-tipo']){
						case 'recaptcha-v3':
							if(existe($_REQUEST['google_recaptcha_site']) && existe($_REQUEST['google_recaptcha_secret'])){
								$campo = 'google_recaptcha_site'; $campo_valor = $_REQUEST[$campo]; banco_update_campo($campo,$campo_valor);
								$campo = 'google_recaptcha_secret'; $campo_valor = $_REQUEST[$campo]; banco_update_campo($campo,$campo_valor);
								banco_update_campo('google_recaptcha_ativo','1',true);
								
								$editar = true;
								$alteracoes[] = Array('campo' => 'google-recaptcha');
								$recaptchaAlterado = true;
							} else {
								interface_alerta(Array(
									'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-google-recaptcha-mandatory-fields'))
								));
							}
						break;
						case 'recaptcha-v2':
							if(existe($_REQUEST['google_recaptcha_v2_site']) && existe($_REQUEST['google_recaptcha_v2_secret'])){
								$campo = 'google_recaptcha_v2_site'; $campo_valor = $_REQUEST[$campo]; banco_update_campo($campo,$campo_valor);
								$campo = 'google_recaptcha_v2_secret'; $campo_valor = $_REQUEST[$campo]; banco_update_campo($campo,$campo_valor);
								banco_update_campo('google_recaptcha_v2_ativo','1',true);
								
								$editar = true;
								$alteracoes[] = Array('campo' => 'google-recaptcha');
								$recaptchaAlterado = true;
							} else {
								interface_alerta(Array(
									'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-google-recaptcha-mandatory-fields'))
								));
							}
						break;
					}
				break;
				case 'excluir':
					switch($_REQUEST['google-recaptcha-tipo']){
						case 'recaptcha-v3':
							banco_update_campo('google_recaptcha_site','NULL',true);
							banco_update_campo('google_recaptcha_secret','NULL',true);
							banco_update_campo('google_recaptcha_ativo','NULL',true);
							
							$editar = true;
							$alteracoes[] = Array('campo' => 'google-recaptcha');
							$recaptchaAlterado = true;
						break;
						case 'recaptcha-v2':
							banco_update_campo('google_recaptcha_v2_site','NULL',true);
							banco_update_campo('google_recaptcha_v2_secret','NULL',true);
							banco_update_campo('google_recaptcha_v2_ativo','NULL',true);
							
							$editar = true;
							$alteracoes[] = Array('campo' => 'google-recaptcha');
							$recaptchaAlterado = true;
						break;
					}
				break;
			}
		}
		
		// ===== Verificar se o google recaptcha tipo foi modificado.
		
		$mudouRecaptchaTipo = false;
		
		if($hosts['google_recaptcha_tipo']){
			if($hosts['google_recaptcha_tipo'] != $_REQUEST['recaptcha-tipo']){
				$mudouRecaptchaTipo = true;
			}
		} else {
			$mudouRecaptchaTipo = true;
		}
		
		$campo = 'google_recaptcha_tipo'; $alteracoes_name = 'recaptcha'; if($mudouRecaptchaTipo){
			$editar = true;
			$recaptchaAlterado = true;
			banco_update_campo($campo,$_REQUEST['recaptcha-tipo']);
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => ($hosts['google_recaptcha_tipo'] ? $hosts['google_recaptcha_tipo'] : 'nenhum' ),'valor_depois' => $_REQUEST['recaptcha-tipo']);
		}
		
		// ===== Caso tenha sido alterado o domínio próprio.
		
		$campo = 'dominio_proprio'; $alteracoes_name = 'type-domain'; if(isset($alterou_dominio_proprio)){
			$editar = true;
			$alterarDominio = true;
			banco_update_campo($campo,$dominio_proprio,true);
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => $modulo['tipoDominio'][$tipoDominioAntes],'valor_depois' => $modulo['tipoDominio'][$tipoDominioDepois]);
		}
		
		// ===== Caso domínio próprio URL tenha sido alterado.
		
		$campo = 'dominio_proprio_url'; $request = 'dominio_proprio_url'; $alteracoes_name = 'own-domain'; if($hosts[$campo] != (isset($_REQUEST[$request]) ? $_REQUEST[$request] : NULL)){
			$editar = true;
			$alterarDominio = true;
			banco_update_campo($campo,$_REQUEST[$request]);
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => $hosts[$campo],'valor_depois' => banco_escape_field($_REQUEST[$request]));
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar)){
			banco_update_campo($modulo['tabela']['versao'],$modulo['tabela']['versao']." + 1",true);
			banco_update_campo($modulo['tabela']['data_modificacao'],'NOW()',true);
			
			banco_update_executar($modulo['tabela']['nome'],"WHERE id_hosts='".$id_hosts."'");
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
				'sem_id' => true,
				'versao' => (int)$hosts['versao'] + 1,
			));
		}
		
		// ===== Alterar domínio no cPanel.
		
		if(isset($alterarDominio)){
			$hosts = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts',
				'campos' => Array(
					'user_cpanel',
					'dominio_proprio',
					'dominio_proprio_url',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
			));
			
			gestor_incluir_biblioteca('cpanel');
			
			if($hosts['dominio_proprio']){
				cpanel_domain_owner_add(Array(
					'user' => $hosts['user_cpanel'],
					'domain_owner' => $hosts['dominio_proprio_url'],
					'domain_default' => $hosts['user_cpanel'].'.'.$_GESTOR['hosts-server']['dominio'],
				));
				
				banco_update_campo('dominio',$hosts['dominio_proprio_url']);
				banco_update_campo('user_ftp',$hosts['user_cpanel'].$_GESTOR['hosts-server']['ftp-user-sufix'].'@'.$hosts['dominio_proprio_url']);
			} else {
				cpanel_domain_owner_del(Array(
					'user' => $hosts['user_cpanel'],
					'domain_default' => $hosts['user_cpanel'].'.'.$_GESTOR['hosts-server']['dominio'],
				));
				
				banco_update_campo('dominio',$hosts['user_cpanel'].'.'.$_GESTOR['hosts-server']['dominio']);
				banco_update_campo('user_ftp',$hosts['user_cpanel'].$_GESTOR['hosts-server']['ftp-user-sufix'].'@'.$hosts['user_cpanel'].'.'.$_GESTOR['hosts-server']['dominio']);
			}
			
			banco_update_executar($modulo['tabela']['nome'],"WHERE id_hosts='".$id_hosts."'");
			
		}
		
		// ===== Modificar no host o Google reCAPTCHA.
		
		if(isset($recaptchaAlterado)){
			// ===== Chamada da API-Cliente para atualizar dados no host do usuário.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_variaveis(Array(
				'opcao' => 'google-recaptcha',
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			}
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Pegar dados do banco.
	
	$id_hosts = $host_verificacao['id_hosts'];
	
	$hosts = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts',
		'campos' => Array(
			'server',
			'dominio_proprio',
			'dominio_proprio_url',
			'gestor_cliente_versao',
			'gestor_cliente_versao_num',
			'google_recaptcha_ativo',
			'google_recaptcha_v2_ativo',
			'google_recaptcha_tipo',
		),
		'extra' => 
			"WHERE id_hosts='".$id_hosts."'"
	));
	
	// ===== Verificar se é domínio próprio ou sistema.
	
	if($hosts['dominio_proprio']){
		$dominioProprio = true;
	}
	
	$dominio_proprio_url = ($hosts['dominio_proprio_url'] ? $hosts['dominio_proprio_url'] : '');
	$google_recaptcha_ativo = ($hosts['google_recaptcha_ativo'] ? true : null);
	$google_recaptcha_v2_ativo = ($hosts['google_recaptcha_v2_ativo'] ? true : null);
	
	// ===== Botão de selecionar o tipo próprio ou sistema.
	
	if(isset($dominioProprio)){
		pagina_trocar_variavel_valor('#tipo-proprio#','blue active',true);
		pagina_trocar_variavel_valor('#tipo-sistema#','',true);
		
		pagina_trocar_variavel_valor('#cont-proprio#','',true);
		pagina_trocar_variavel_valor('#cont-recaptcha#','',true);
	} else {
		pagina_trocar_variavel_valor('#tipo-proprio#','',true);
		pagina_trocar_variavel_valor('#tipo-sistema#','blue active',true);
		
		pagina_trocar_variavel_valor('#cont-proprio#','escondido',true);
		pagina_trocar_variavel_valor('#cont-recaptcha#','escondido',true);
	}
	
	pagina_trocar_variavel_valor('#dominio_proprio_url#',$dominio_proprio_url,true);
	
	// ===== verificar o tipo do google reCAPTCHA.
	
	if($hosts['google_recaptcha_tipo']){
		$googleRecaptchaTipo = $hosts['google_recaptcha_tipo'];
	}
	
	// ===== Servidores de DNS disponíveis para o host.
	
	$servidoresDNS = $config['servidores-dns'][$hosts['server']];
	
	$cel_nome = 'ns-cel'; $cel[$cel_nome] = pagina_celula($cel_nome);
	
	if($servidoresDNS)
	foreach($servidoresDNS as $chave => $valor){
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#ns#",$valor,true);
		
		pagina_celula_incluir($cel_nome,$cel_aux);
	}
	
	pagina_celula_incluir($cel_nome,'');
	
	// ===== Verificar versão do gestor cliente.
	
	$gestor_cliente_versao = $hosts['gestor_cliente_versao'];
	$gestor_cliente_versao_num = $hosts['gestor_cliente_versao_num'];
	
	// ===== Comparar versões e montar a interface. Ou é atualização normal, dado que há uma versão mais nova, ou então se quiser forçar a atualização afim de sobrescrever os dados no hospedeiro do cliente.
	
	if($_GESTOR['gestor-cliente']['versao_num'] > (int)$gestor_cliente_versao_num){
		$mensagemAtualizacao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-changes-update-do-msg-content'));
		
		$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-cliente#",$gestor_cliente_versao);
		$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-plaforma#",$_GESTOR['gestor-cliente']['versao']);
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#mensagem-atualizacao#",$mensagemAtualizacao);
	} else {
		$mensagemAtualizacao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-changes-update-force-msg-content'));
		
		$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-cliente#",$gestor_cliente_versao);
		$mensagemAtualizacao = modelo_var_troca($mensagemAtualizacao,"#versao-plaforma#",$_GESTOR['gestor-cliente']['versao']);
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#mensagem-atualizacao#",$mensagemAtualizacao);
	}
	
	// ===== Verificar se o host tem plugins habilitados.
	
	$hosts_plugins = banco_select(Array(
		'tabela' => 'hosts_plugins',
		'campos' => Array(
			'plugin',
			'habilitado',
			'versao',
		),
		'extra' => 
			"WHERE id_hosts='".$id_hosts."'"
	));
	
	// ===== Caso exista plugins, listar os mesmos. Senão, mostrar mensagem de não haver plugins habilitados.
	
	$pluginsHabilitados = false;
	
	if($hosts_plugins){
		$plugins = banco_select(Array(
			'tabela' => 'plugins',
			'campos' => Array(
				'nome',
				'id',
			),
			'extra' => 
				"WHERE status!='D'"
				." ORDER BY nome ASC"
		));
		
		$mensagemPlugins = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-plugins-msg-content'));
		
		$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($mensagemPlugins,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $mensagemPlugins = modelo_tag_in($mensagemPlugins,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
		if($plugins){
			foreach($plugins as $plugin){
				if($hosts_plugins){
					foreach($hosts_plugins as $hosts_plugin){
						if(
							$plugin['id'] == $hosts_plugin['plugin'] &&
							$hosts_plugin['habilitado']
						){
							$cel_aux = $cel[$cel_nome];
							
							$cel_aux = modelo_var_troca($cel_aux,"#plugin#",$plugin['nome'].' - Versão: '.$hosts_plugin['versao']);
							
							$mensagemPlugins = modelo_var_in($mensagemPlugins,'<!-- '.$cel_nome.' -->',$cel_aux);
							
							$pluginsHabilitados = true;
						}
					}
				}
				
			}
		}
		
		$mensagemPlugins = modelo_var_troca($mensagemPlugins,'<!-- '.$cel_nome.' -->','');
	}
	
	if(!$pluginsHabilitados){
		$mensagemPlugins = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'host-plugins-msg-not-enabled'));
	}
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#mensagem-plugins#",$mensagemPlugins);
	
	// ===== URL completa do site.
	
	gestor_incluir_biblioteca('host');
	$urlFull = host_url(Array(
		'opcao' => 'full',
	));
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#url-site#",$urlFull);
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Se é domínio próprio ou sistema, informar JS.
	
	$JShost['dominioProprio'] = (isset($dominioProprio) ? true : false);
	
	if(isset($google_recaptcha_ativo)){
		$JShost['googleRecaptchaInstalado'] = true;
	}
	
	if(isset($google_recaptcha_v2_ativo)){
		$JShost['googleRecaptchaV2Instalado'] = true;
	}
	
	if(isset($googleRecaptchaTipo)){
		$JShost['googleRecaptchaTipo'] = $googleRecaptchaTipo;
	} else {
		$JShost['googleRecaptchaTipo'] = 'nenhum';
	}
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['host'] = $JShost;
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['config']['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'dominio',
					'campo' => 'dominio_proprio_url',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-own-domain-label')),
				),
			),
		)
	);
}

function host_configuracao_forgot_password(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_REQUEST['forgot-password'])){
		// ===== Dados do Usuário
		
		$usuario = gestor_usuario();
		
		$id_usuarios = $usuario['id_usuarios'];
		$email = $usuario['email'];
		$nome = $usuario['nome'];
		
		// ===== Criar o token e guardar o mesmo no banco
		
		$tokenPubId = md5(uniqid(rand(), true));
		$expiration = time() + $_CONFIG['token-lifetime'];

		$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = 'host-config-forgot-password'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pubID"; $campo_valor = $pubID; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"tokens"
		);
		
		$tokens_id = banco_last_id();
		
		// ===== Caso definido o redirect, mandar junto do link o mesmo
		
		$redirect = gestor_sessao_variavel('host-configuracao-forgot-password-redirect');
		
		if(existe($redirect)){
			$redirectLocal = '';
			
			switch($redirect){
				case 'host-config': $redirectLocal = 'host-config/'; break;
				case 'host-update': $redirectLocal = 'host-update/'; break;
			}
			
			$redirect = '&redirect=' . urlencode($redirectLocal);
		}
		
		// ===== Enviar o email com as instruções para renovar a senha.
		
		$numero = date('Ymd') . $tokens_id;
		
		$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-mail-subject')),"#numero#",$numero);
		
		gestor_incluir_biblioteca('comunicacao');
		
		if(comunicacao_email(Array(
			'destinatarios' => Array(
				Array(
					'email' => $email,
					'nome' => $nome,
				),
			),
			'mensagem' => Array(
				'assunto' => $assunto,
				'htmlLayoutID' => 'layout-email-esqueceu-senha-da-conta-ftp',
				'htmlVariaveis' => Array(
					Array(
						'variavel' => '#nome#',
						'valor' => $nome,
					),
					Array(
						'variavel' => '#url#',
						'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'host-config-redefine-password/?id='.$tokenPubId.$redirect.'">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'host-config-redefine-password/?id='.$tokenPubId.$redirect.'</a>',
					),
					Array(
						'variavel' => '#expiracao#',
						'valor' => $_CONFIG['token-lifetime'] / 3600,
					),
					Array(
						'variavel' => '#assinatura#',
						'valor' => gestor_componente(Array(
							'id' => 'layout-emails-assinatura',
						)),
					),
				),
			),
		))){
			
		}
		
		// ===== Se o usuário for inválido, redirecionar forgot-password.
		
		gestor_sessao_variavel($_GESTOR['modulo'].'-'.'esqueceu-senha-confirmacao'.'-'.'email',$email);
		gestor_redirecionar('host-config-forgot-password-confirmation/');
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
}

function host_configuracao_forgot_password_confirmation(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Caso exista a variável devolva a página, senão redirecionar porque não se deve acessar essa página diretamente.
	
	if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email'))){
		$email = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email');
		gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email');
		
		$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-confirmation-message-content'));
		
		$message = modelo_var_troca_tudo($message,"#email#",$email);
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#message#",$message);
	} else {
		gestor_redirecionar('host-config-forgot-password/');
	}
}

function host_configuracao_redefine_password(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_REQUEST['_gestor-host-redefine-password'])){
		// ===== Verificar se tem o redirect e aplicar
		
		$redirectLocal = '';
		if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
			$sessaoRedefinePassword = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			$redirectLocal = $sessaoRedefinePassword['redirect'];
		}
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'redirect' => (isset($_REQUEST['_gestor-host-redefine-password-token']) ? 'host-config-redefine-password/?id='. banco_escape_field($_REQUEST['_gestor-host-redefine-password-token']).(existe($redirectLocal) ? '&redirect='.$redirectLocal:'') : NULL),
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
				),
			)
		));
		
		// ===== Campo de validação da redefinição
		
		$autorizacaoRedefinicao = false;
		
		// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
		
		$tokenPubId = banco_escape_field($_REQUEST['_gestor-host-redefine-password-token']);
		
		$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
			$sessaoRedefinePassword = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			
			if($sessaoRedefinePassword['pubID'] == $pubID){
				$autorizacaoRedefinicao = true;
				$tokens_id = $sessaoRedefinePassword['tokenID'];
				$urlCallBack = $sessaoRedefinePassword['redirect'];
			} else {
				gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			}
		}
		
		// ===== Caso autorizado atualizar senha no banco, senão alertar o usuário e redirecionar para esqueceu senha novamente.
		
		if($autorizacaoRedefinicao){
			// ===== Pegar nova senha
			
			$senha = $_REQUEST['senha'];
			
			// ===== Pegar dados do host
			
			$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
			$id_hosts = $host_verificacao['id_hosts'];
			$id_usuarios = $_GESTOR['usuario-id'];
			
			$hosts = banco_select_name
			(
				banco_campos_virgulas(Array(
					'user_cpanel',
					'user_ftp',
					'user_db',
					'versao',
				))
				,
				"hosts",
				"WHERE id_hosts='".$id_hosts."'"
			);
			
			$user_ftp = $hosts[0]['user_ftp'];
			$user_db = $hosts[0]['user_db'];
			$user_cpanel = $hosts[0]['user_cpanel'];
			
			// ===== Atualizar senha da conta FTP
			
			global $_CPANEL;
			
			$_CPANEL['CPANEL_USER'] = $user_cpanel;
			$_CPANEL['FTP_LOCAL'] = $_GESTOR['hosts-server']['local'];
			
			$_CPANEL['FTP_PASSWD'] = Array(
				'user' => $user_ftp,
				'pass' => $senha,
			);
			
			require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-ftp-passwd.php');
			
			$_CPANEL['DB_CHANGE_USER_PASS'] = Array(
				'user' => $user_db,
				'pass' => hash("sha256",$senha),
			);
			
			require($_GESTOR['hosts-server']['cpanel-root-path'].'cpanel-changedbuserpassword.php');
			
			// ===== Atualizar versão do host no banco.
			
			$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
			
			banco_update_campo($modulo['tabela']['versao'],$modulo['tabela']['versao']." + 1",true);
			banco_update_campo($modulo['tabela']['data_modificacao'],'NOW()',true);
			
			banco_update_executar($modulo['tabela']['nome'],"WHERE id_hosts='".$id_hosts."'");
			
			// ===== Pegar o IP do usuário.
			
			gestor_incluir_biblioteca('ip');
			
			$ip = ip_get();
			
			// ===== Criar histórico de alterações.
			
			$resetPasswordTXT = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'reset-password'));
			
			$resetPasswordTXT = modelo_var_troca($resetPasswordTXT,"#ip#",$ip);
			$resetPasswordTXT = modelo_var_troca($resetPasswordTXT,"#user-agent#",$_SERVER['HTTP_USER_AGENT']);
			
			$alteracoes[] = Array('alteracao' => 'reset-password','alteracao_txt' => $resetPasswordTXT);
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
				'sem_id' => true,
				'versao' => (int)$hosts[0]['versao'] + 1,
			));
			
			// ===== Pegar os dados do usuário que serão usados para informar o mesmo.
			
			$usuarios = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios',
				'campos' => Array(
					'nome',
					'email',
				),
				'extra' => 
					"WHERE id_usuarios='".$id_usuarios."'"
			));
			
			$nome = $usuarios['nome'];
			$email = $usuarios['email'];
			
			// ===== Enviar o email informando da alteração da senha com sucesso.
			
			$numero = date('Ymd') . $tokens_id;
			
			$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'password-redefined-mail-subject')),"#numero#",$numero);
			
			gestor_incluir_biblioteca('comunicacao');
			
			if(comunicacao_email(Array(
				'destinatarios' => Array(
					Array(
						'email' => $email,
						'nome' => $nome,
					),
				),
				'mensagem' => Array(
					'assunto' => $assunto,
					'htmlLayoutID' => 'layout-email-conta-ftp-senha-redefinida',
					'htmlVariaveis' => Array(
						Array(
							'variavel' => '#nome#',
							'valor' => $nome,
						),
						Array(
							'variavel' => '#assinatura#',
							'valor' => gestor_componente(Array(
								'id' => 'layout-emails-assinatura',
							)),
						),
					),
				),
			))){
				$email_not_sent = false;
			} else {
				$email_not_sent = true;
			}
			
			// ===== Forçar status 'atualizar' caso já esteja configurado afim de disparar atualização.
			
			$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
			
			if(!isset($host_verificacao['configurar'])){
				$host_verificacao['atualizar'] = true;
				gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id'],$host_verificacao);
				
				banco_update
				(
					"atualizar=1",
					"hosts",
					"WHERE id_hosts='".$id_hosts."'"
				);
			}
			
			// ===== Atualizar sessão e redirecionar
			
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-confirmacao',true);
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-confirmacao-url',(isset($urlCallBack) ? $urlCallBack : ''));
			
			gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			gestor_redirecionar('host-config-redefine-password-confirmation/');
		} else {
			sleep(3);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-redefine-password-expiration-or-invalid'))
			));
			
			gestor_redirecionar('host-config-forgot-password/');
		}
	}
	
	// ===== Campo de validação dos dados
	
	$autorizacao = false;
	
	// ===== Verifica se foi enviado um id
	
	if(isset($_REQUEST['id'])){
		// ===== Remover todos os tokens expirados
		
		banco_delete
		(
			"tokens",
			"WHERE expiration < ".time()
		);
		
		// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
		
		$tokenPubId = banco_escape_field($_REQUEST['id']);
		
		$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
			$sessaoRedefinePassword = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			
			if($sessaoRedefinePassword['pubID'] == $pubID){
				$autorizacao = true;
			} else {
				gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			}
		}
		
		// ===== Verificar no banco de dados se existe o token
		
		if(!$autorizacao){
			$tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_tokens',
				))
				,
				"tokens",
				"WHERE pubID='".$pubID."'"
			);
			
			if($tokens){
				$autorizacao = true;
				
				gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'],Array(
					'pubID' => $pubID,
					'tokenID' => $tokens[0]['id_tokens'],
					'redirect' => (isset($_REQUEST['redirect']) ? urldecode(banco_escape_field($_REQUEST['redirect'])) : '' ),
				));
				
				banco_delete
				(
					"tokens",
					"WHERE id_tokens='".$tokens[0]['id_tokens']."'"
				);
			}
		}
	}
	
	if(!$autorizacao){
		sleep(3);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-redefine-password-expiration-or-invalid'))
		));
		
		gestor_redirecionar('host-config-forgot-password/');
	}
	
	// ===== Alterar dados da página e incluir o token
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#token#",$tokenPubId);
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Interface finalizar opções
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
			'identificador' => 'senha',
			'comparcao' => Array(
				'id' => 'senha-2',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-2-label')),
			)
		),
		Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha-2',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-2-label')),
			'identificador' => 'senha-2',
			'comparcao' => Array(
				'id' => 'senha',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-pass-2-label')),
			)
		),
	);
	
	interface_formulario_validacao($formulario);
}

function host_configuracao_redefine_password_confirmation(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	gestor_pagina_javascript_incluir();
	
	// ===== Caso exista a variável devolva a página, senão redirecionar porque não se deve acessar essa página diretamente.
	
	if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
		gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
		
		$url = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-url');
		gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-url');
		
		$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-confirmation-message-content'));
		
		$message = modelo_var_troca_tudo($message,"#url#",'<a href="'.$_GESTOR['url-raiz'].$url.'">'.$_GESTOR['url-raiz'].$url.'</a>');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#message#",$message);
	} else {
		gestor_redirecionar('host-config-forgot-password/');
	}
}

function host_configuracao_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						$modulo['tabela']['data_criacao'],
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
				),
				'tabela' => Array(
					'rodape' => true,
					'colunas' => Array(
						Array(
							'id' => 'nome',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => $modulo['tabela']['data_criacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
						Array(
							'id' => $modulo['tabela']['data_modificacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
					),
				),
				'opcoes' => Array(
					'editar' => Array(
						'url' => 'editar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')),
						'icon' => 'edit',
						'cor' => 'basic blue',
					),
					'ativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'I',
						'status_mudar' => 'A',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active')),
						'icon' => 'eye slash',
						'cor' => 'basic brown',
					),
					'desativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'A',
						'status_mudar' => 'I',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')),
						'icon' => 'eye',
						'cor' => 'basic green',
					),
					'excluir' => Array(
						'opcao' => 'excluir',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
						'icon' => 'trash alternate',
						'cor' => 'basic red',
					),
				),
				'botoes' => Array(
					'adicionar' => Array(
						'url' => 'adicionar/',
						'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
						'icon' => 'plus circle',
						'cor' => 'blue',
					),
				),
			);
		break;
	}
}

// ==== Ajax

function host_configuracao_ajax_plataforma_testes(){
	global $_GESTOR;
	
	$habilitar = true;
	
	if($habilitar){
		gestor_incluir_biblioteca('api-cliente');
		
		$ocpao = 'templates-atualizar';
		
		switch($ocpao){
			case 'upload-file':
				$retorno = api_cliente_arquivos(Array(
					'opcao' => 'adicionar',
					'nomeExtensao' => 'fish.jpg',
					'caminhoArquivo' => $_GESTOR['contents-path'].'files/2021/06/fish.jpg',
					'tipoArquivo' => 'image/jpeg',
				));
			break;
			case 'templates-atualizar':
				$retorno = api_cliente_templates_atualizar(Array(
					'opcao' => 'update',
					'forceUpdate' => true,
				));
			break;
			case 'paginas':
				$retorno = api_cliente_paginas(Array(
					'opcao' => 'editar',
					'id' => 'teste-pagina',
				));
			break;
			default:
				$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
				
				$retorno = api_cliente_interface(Array(
					'interface' => 'api-testes',
					'id_hosts' => $host_verificacao['id_hosts'],
				));
		}
		
		$dados = 'Dados retornados: '.print_r($retorno,true);
	} else {
		$dados = 'Desabilitado!';
	}
	
	$_GESTOR['ajax-json'] = Array(
		'dados' => $dados,
		'status' => 'Ok',
	);
}

// ==== Start

function host_configuracao_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	// ===== Verifica se o usuário é admin do host, senão for redirecionar para dashboard e alertar.
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	if(!isset($host_verificacao['privilegios_admin'])){
		$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-not-admin-host'));
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
		
		gestor_redirecionar('dashboard/');
	}
	
	// ===== Interfaces principais.
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'plataforma-testes': host_configuracao_ajax_plataforma_testes(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		host_configuracao_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'config': host_configuracao_configuracoes(); break;
			case 'instalar': host_configuracao_instalar(); break;
			case 'configurar': host_configuracao_configurar(); break;
			case 'atualizar': host_configuracao_atualizar(); break;
			case 'esqueceu-senha': host_configuracao_forgot_password(); break;
			case 'esqueceu-senha-confirmacao': host_configuracao_forgot_password_confirmation(); break;
			case 'redefinir-senha': host_configuracao_redefine_password(); break;
			case 'redefinir-senha-confirmacao': host_configuracao_redefine_password_confirmation(); break;
			case 'plataforma-testes': host_configuracao_plataforma_testes(); break;
			case 'plugins': host_configuracao_plugins(); break;
		}
		
		interface_finalizar();
	}
}

host_configuracao_start();

?>