<?php

// ===== Plataforma responsável por receber solicitações do 'servidor'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-servidor';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.1',
);

// =========================== Funções Auxiliares

function plataforma_servidor_gerar_jwt($params = false){
	$cryptMaxCharsValue = 245; // There are char limitations on openssl_private_encrypt() and in the url below are explained how define this value based on openssl key format: https://www.php.net/manual/en/function.openssl-private-encrypt.php#119810
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// host - String - Obrigatório - Host de acesso do JWT.
	// expiration - Int - Obrigatório - Expiração do JWT.
	// pubID - String - Obrigatório - ID público do token para referência.
	// chavePrivada - String - Obrigatório - Chave privada para gerar a assinatura do token.
	// chavePrivadaSenha - String - Obrigatório - Senha da chave privada.
	
	// ===== 
	
	if(isset($host) && isset($expiration) && isset($pubID) && isset($chavePrivada) && isset($chavePrivadaSenha)){
		// ===== Header

		$header = [
		   'alg' => 'RSA',
		   'typ' => 'JWT'
		];

		$header = json_encode($header);
		$header = base64_encode($header);

		// ===== Payload

		$payload = [
			'iss' => $host, // The issuer of the token
			'exp' => $expiration, // This will define the expiration in NumericDate value. The expiration MUST be after the current date/time.
			'sub' => $pubID, // ID público do totken
		];

		$payload = json_encode($payload);
		$payload = base64_encode($payload);

		// ===== Unir header com payload para gerar assinatura

		$rawDataSource = $header.".".$payload;
		
		// ===== Assinar usando RSA SSL
		
		$resPrivateKey = openssl_get_privatekey($chavePrivada,$chavePrivadaSenha);
		
		$partialData = '';
		$encodedData = '';
		$split = str_split($rawDataSource , $cryptMaxCharsValue);
		foreach($split as $part){
			openssl_private_encrypt($part, $partialData, $resPrivateKey);
			$encodedData .= (strlen($encodedData) > 0 ? '.':'') . base64_encode($partialData);
		}
		
		$encodedData = base64_encode($encodedData);
		
		$signature = $encodedData;
		
		// ===== Finalizar e devolver o JWT token

		$JWTToken = $header.".".$payload.".".$signature;
		
		return $JWTToken;
	} else {
		return false;
	}
}

function plataforma_servidor_validar_jwt($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - Token JWT de verificação.
	// chavePrivada - String - Obrigatório - Chave privada para conferir a assinatura do token.
	// chavePrivadaSenha - String - Obrigatório - Senha da chave privada.
	
	// ===== 
	
	if(isset($token) && isset($chavePrivada) && isset($chavePrivadaSenha)){
		// ===== Quebra o token em header, payload e signature
		
		$part = explode(".",$token);
		
		if(gettype($part) != 'array'){
			return false;
		}
		
		$header = $part[0];
		$payload = $part[1];
		$signature = $part[2];

		$encodedData = $signature;
		
		// ===== Abrir chave privada com a senha
		
		$resPrivateKey = openssl_get_privatekey($chavePrivada,$chavePrivadaSenha);
		
		// ===== Decode base64 to reaveal dots (Dots are used in JWT syntaxe)

		$encodedData = base64_decode($encodedData);

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

		// ===== Validate JWT

		if($header.".".$payload === $decodedData){
			$payload = base64_decode($payload);
			$payload = json_decode($payload,true);
			
			// ===== Verifica se as variáveis existem, senão foi formatado errado e não deve aceitar.
			
			if(!isset($payload['exp']) || !isset($payload['sub'])){
				return false;
			}
			
			$expiracao_ok = false;
			
			// ===== Se o tempo de expiração do token for menor que o tempo agora, é porque este token está vencido.
			
			if((int)$payload['exp'] > time()){
				$expiracao_ok = true;
			}
			
			if($expiracao_ok){
				// Se tudo estiver válido, retorna o pubID do token.
				
				return $payload['sub'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function plataforma_servidor_validar_token_autorizacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - JWT gerado pelo servidor.
	
	// ===== 
	
	if(isset($token)){
		// ===== Verifica se existe o token.
		
		$JWTToken = $token;
		
		if(!existe($JWTToken)){
			return false;
		}
		
		// ===== Abrir chave privada e a senha da chave
		
		$chavePrivada = $_GESTOR['plataforma-cliente']['chave-seguranca']['chave'];
		$chavePrivadaSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['senha'];
		$hashAlgo = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-algo'];
		$hashSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-senha'];
		
		// ===== Verificar se o JWT é válido.
		
		$tokenPubId = plataforma_servidor_validar_jwt(Array(
			'token' => $JWTToken,
			'chavePrivada' => $chavePrivada,
			'chavePrivadaSenha' => $chavePrivadaSenha,
		));
		
		return $tokenPubId;
	}
	
	return false;
}

function plataforma_servidor_gerar_token_autorizacao($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// serverHost - String - Obrigatório - Host do servidor.
	
	// ===== 
	
	if(isset($serverHost)){
	
		// ===== Definir variáveis para gerar o JWT
		
		$expiration = time() + $_GESTOR['platform-lifetime'];
		
		// ===== Abrir chave privada e a senha da chave
		
		$chavePrivada = $_GESTOR['plataforma-cliente']['chave-seguranca']['chave'];
		$chavePrivadaSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['senha'];
		
		// ===== Gerar ID do Token
		
		$tokenPubId = md5(uniqid(rand(), true));
		
		// ===== Gerar o token JWT
		
		$token = plataforma_servidor_gerar_jwt(Array(
			'host' => $serverHost,
			'expiration' => $expiration,
			'pubID' => $tokenPubId,
			'chavePrivada' => $chavePrivada,
			'chavePrivadaSenha' => $chavePrivadaSenha,
		));
		
		return $token;
	}
	
	return false;
}

// =========================== Funções da Plataforma

function plataforma_servidor_api_testes(){
	
	gestor_incluir_biblioteca('api-servidor');
	
	$retorno = api_servidor_interface(Array(
		'interface' => 'opcao',
	));
	
	$data = '[api-servidor]: '.print_r($retorno,true);
	
	$dados = Array(
		'status' => 'OK',
		'data' => $data,
	);
	
	return $dados;
}

function plataforma_servidor_paginas(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'adicionar':
		case 'editar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['registro']['id_hosts_paginas'])){
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_paginas = banco_escape_field($dados['registro']['id_hosts_paginas']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_paginas',
					))
					,
					"paginas",
					"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['registro']['id_hosts_paginas']);
					
					$campo_tabela = "paginas";
					$campo_tabela_extra = "WHERE id_hosts_paginas='".$id_hosts_paginas."'";
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
							break;
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
					
					// ===== Se mudou o caminho, criar página 301 do caminho.
					
					if(isset($dados['caminhoMudou'])){
						$caminho = banco_escape_field($dados['caminhoMudou']['caminho']);
						$id_hosts_paginas_301 = banco_escape_field($dados['caminhoMudou']['id_hosts_paginas_301']);
						
						$campos = null; $campo_sem_aspas_simples = null;
						
						$campo_nome = "id_hosts_paginas_301"; $campo_valor = $id_hosts_paginas_301; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_hosts_paginas"; $campo_valor = $id_hosts_paginas; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "caminho"; $campo_valor = $caminho; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						banco_insert_name
						(
							$campos,
							"paginas_301"
						);
					}
				} else {
					$campos = null; $campo_sem_aspas_simples = null;
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							default:
								$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
					}
					
					banco_insert_name
					(
						$campos,
						"paginas"
					);
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		case 'status':
		case 'excluir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['registro']['id_hosts_paginas'])){
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_paginas = banco_escape_field($dados['registro']['id_hosts_paginas']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_paginas',
					))
					,
					"paginas",
					"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['registro']['id_hosts_paginas']);
					
					$campo_tabela = "paginas";
					$campo_tabela_extra = "WHERE id_hosts_paginas='".$id_hosts_paginas."'";
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
					
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'ID_NOT_FOUNDED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_layouts(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'adicionar':
		case 'editar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['registro']['id_hosts_layouts'])){
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_layouts = banco_escape_field($dados['registro']['id_hosts_layouts']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_layouts',
					))
					,
					"layouts",
					"WHERE id_hosts_layouts='".$id_hosts_layouts."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['registro']['id_hosts_layouts']);
					
					$campo_tabela = "layouts";
					$campo_tabela_extra = "WHERE id_hosts_layouts='".$id_hosts_layouts."'";
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
							break;
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				} else {
					$campos = null; $campo_sem_aspas_simples = null;
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							default:
								$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
					}
					
					banco_insert_name
					(
						$campos,
						"layouts"
					);
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		case 'status':
		case 'excluir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['registro']['id_hosts_layouts'])){
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_layouts = banco_escape_field($dados['registro']['id_hosts_layouts']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_layouts',
					))
					,
					"layouts",
					"WHERE id_hosts_layouts='".$id_hosts_layouts."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['registro']['id_hosts_layouts']);
					
					$campo_tabela = "layouts";
					$campo_tabela_extra = "WHERE id_hosts_layouts='".$id_hosts_layouts."'";
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
					
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'ID_NOT_FOUNDED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_componentes(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'adicionar':
		case 'editar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['registro']['id_hosts_componentes'])){
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_componentes = banco_escape_field($dados['registro']['id_hosts_componentes']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_componentes',
					))
					,
					"componentes",
					"WHERE id_hosts_componentes='".$id_hosts_componentes."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['registro']['id_hosts_componentes']);
					
					$campo_tabela = "componentes";
					$campo_tabela_extra = "WHERE id_hosts_componentes='".$id_hosts_componentes."'";
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				} else {
					$campos = null; $campo_sem_aspas_simples = null;
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
					}
					
					banco_insert_name
					(
						$campos,
						"componentes"
					);
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		case 'status':
		case 'excluir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['registro']['id_hosts_componentes'])){
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_layouts = banco_escape_field($dados['registro']['id_hosts_componentes']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_componentes',
					))
					,
					"componentes",
					"WHERE id_hosts_componentes='".$id_hosts_componentes."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['registro']['id_hosts_componentes']);
					
					$campo_tabela = "componentes";
					$campo_tabela_extra = "WHERE id_hosts_componentes='".$id_hosts_componentes."'";
					
					foreach($dados['registro'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
					
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'ID_NOT_FOUNDED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_templates_atualizar(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'update':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do layouts.
			
			if($dados['layouts']){
				$registros = $dados['layouts'];
				
				foreach($registros as $registro){
					if(isset($registro['id_hosts_layouts'])){
						// ===== Busca no banco de dados o ID referido.
						
						$id_hosts_layouts = banco_escape_field($registro['id_hosts_layouts']);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_layouts',
							))
							,
							"layouts",
							"WHERE id_hosts_layouts='".$id_hosts_layouts."'"
						);
						
						// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo layout com os dados enviados.
						
						if($resultado){
							unset($registro['id_hosts_layouts']);
							
							$campo_tabela = "layouts";
							$campo_tabela_extra = "WHERE id_hosts_layouts='".$id_hosts_layouts."'";
							
							foreach($registro as $chave => $dado){
								switch($chave){
									case 'template_padrao':
									case 'template_modificado':
										$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
									break;
									case 'template_versao':
									case 'modulo_id_registro':
										$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
									break;
									default:
										$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
							$editar = false;$editar_sql = false;
						} else {
							$campos = null; $campo_sem_aspas_simples = null;
							
							foreach($registro as $chave => $dado){
								switch($chave){
									case 'template_padrao':
									case 'template_modificado':
										$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
									break;
									case 'template_versao':
									case 'modulo_id_registro':
										$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
									break;
									default:
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								}
							}
							
							banco_insert_name
							(
								$campos,
								"layouts"
							);
						}
					}
				}
			}
			
			// ===== Verifica o ID referencial do paginas.
			
			if($dados['paginas']){
				$registros = $dados['paginas'];
				
				foreach($registros as $registro){
					if(isset($registro['id_hosts_layouts'])){
						// ===== Busca no banco de dados o ID referido.
						
						$id_hosts_paginas = banco_escape_field($registro['id_hosts_paginas']);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_paginas',
							))
							,
							"paginas",
							"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
						);
						
						// ===== Se existir atualiza a tabela com os dados enviados, senão cria uma nova pagina com os dados enviados.
						
						if($resultado){
							unset($registro['id_hosts_paginas']);
							
							$campo_tabela = "paginas";
							$campo_tabela_extra = "WHERE id_hosts_paginas='".$id_hosts_paginas."'";
							
							foreach($registro as $chave => $dado){
								switch($chave){
									case 'template_padrao':
									case 'template_modificado':
										$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
									break;
									case 'template_versao':
									case 'modulo_id_registro':
										$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
									break;
									default:
										$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
							$editar = false;$editar_sql = false;
						} else {
							$campos = null; $campo_sem_aspas_simples = null;
							
							foreach($registro as $chave => $dado){
								switch($chave){
									case 'template_padrao':
									case 'template_modificado':
										$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
									break;
									case 'template_versao':
									case 'modulo_id_registro':
										$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
									break;
									default:
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								}
							}
							
							banco_insert_name
							(
								$campos,
								"paginas"
							);
						}
					}
				}
			}
			
			// ===== Verifica o ID referencial do componentes.
			
			if($dados['componentes']){
				$registros = $dados['componentes'];
				
				foreach($registros as $registro){
					if(isset($registro['id_hosts_componentes'])){
						// ===== Busca no banco de dados o ID referido.
						
						$id_hosts_componentes = banco_escape_field($registro['id_hosts_componentes']);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_componentes',
							))
							,
							"componentes",
							"WHERE id_hosts_componentes='".$id_hosts_componentes."'"
						);
						
						// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo layout com os dados enviados.
						
						if($resultado){
							unset($registro['id_hosts_componentes']);
							
							$campo_tabela = "componentes";
							$campo_tabela_extra = "WHERE id_hosts_componentes='".$id_hosts_componentes."'";
							
							foreach($registro as $chave => $dado){
								switch($chave){
									case 'template_padrao':
									case 'template_modificado':
										$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
									break;
									case 'template_versao':
									case 'modulo_id_registro':
										$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
									break;
									default:
										$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
							$editar = false;$editar_sql = false;
						} else {
							$campos = null; $campo_sem_aspas_simples = null;
							
							foreach($registro as $chave => $dado){
								switch($chave){
									case 'template_padrao':
									case 'template_modificado':
										$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
									break;
									case 'template_versao':
									case 'modulo_id_registro':
										$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
									break;
									default:
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								}
							}
							
							banco_insert_name
							(
								$campos,
								"componentes"
							);
						}
					}
				}
			}
			
			$retorno = Array(
				'status' => 'OK',
			);
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
	
	return $retorno;
}

function plataforma_servidor_arquivos(){
	/**********
		Descrição: Plataforma de manipulação dos arquivos do cliente.
	**********/
	
	global $_GESTOR;
	
	// ===== Parâmetros padrões
	
	
	
	// ===== 
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'adicionar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Os campos 'anoDir' e 'mesDir' são obrigatórios
			
			if(isset($dados['registro']['anoDir']) && isset($dados['registro']['mesDir'])){
				// ===== Diretório conteúdo principal e caminhos padrões de arquivo.
				
				$contentsPath = $_GESTOR['contents-path'];
				$basedir = $_GESTOR['contents-basedir'];
				$thumbnail = $_GESTOR['contents-thumbnail'];
				
				// ===== Mês e ano diretórios.
				
				$anoDir = $dados['registro']['anoDir'];
				$mesDir = $dados['registro']['mesDir'];
				
				// ===== Senão existir criar todos os diretórios para guardar o arquivo.
				
				if(!is_dir($contentsPath)){
					mkdir($contentsPath, 0755);
				}
				
				if(!is_dir($contentsPath.'/'.$basedir)){
					mkdir($contentsPath.'/'.$basedir, 0755);
				}
				
				if(!is_dir($contentsPath.'/'.$basedir.'/'.$anoDir)){
					mkdir($contentsPath.'/'.$basedir.'/'.$anoDir, 0755);
				}
				
				if(!is_dir($contentsPath.'/'.$basedir.'/'.$anoDir.'/'.$mesDir)){
					mkdir($contentsPath.'/'.$basedir.'/'.$anoDir.'/'.$mesDir, 0755);
				}
				
				if(!is_dir($contentsPath.'/'.$basedir.'/'.$anoDir.'/'.$mesDir.'/'.$thumbnail)){
					mkdir($contentsPath.'/'.$basedir.'/'.$anoDir.'/'.$mesDir.'/'.$thumbnail, 0755);
				}
				
				// ===== Campo 'nomeExtensao' é obrigatório
				
				if(isset($dados['registro']['nomeExtensao'])){
					// ===== Nome com extensão do arquivo.
					
					$nome_extensao = $dados['registro']['nomeExtensao'];
					
					// ===== Caminhos completos dos arquivos.
					
					$caminho_arquivo = $contentsPath.'/'.$basedir.'/'.$anoDir.'/'.$mesDir.'/'.$nome_extensao;
					$caminho_arquivo_mini = $contentsPath.'/'.$basedir.'/'.$anoDir.'/'.$mesDir.'/'.$thumbnail.'/'.$nome_extensao;
					
					$url_arquivo = $basedir.'/'.$anoDir.'/'.$mesDir.'/'.$nome_extensao;
					$url_arquivo_mini = $basedir.'/'.$anoDir.'/'.$mesDir.'/'.$thumbnail.'/'.$nome_extensao;
					
					$data = Array();
					
					// ===== Mover o arquivo enviado para o caminho definitivo.
					
					if(move_uploaded_file($_FILES['file']['tmp_name'][0], $caminho_arquivo)){
						$files_moved = true;
						$data['url_arquivo'] = $url_arquivo;
						$dados['tabela']['caminho'] = $url_arquivo;
					} else {
						$files_moved = false;
					}
					
					// ===== Conferir se foi enviado arquivo mini e movê-lo para o caminho definitivo.
					
					if(isset($dados['registro']['arquivoMini'])){
						if(move_uploaded_file($_FILES['file']['tmp_name'][1], $caminho_arquivo_mini)){
							$files_moved = true;
							$data['url_arquivo_mini'] = $url_arquivo_mini;
							$dados['tabela']['caminho_mini'] = $url_arquivo_mini;
						} else {
							$files_moved = false;
						}
					}
					
					// ===== Verifica o ID referencial do registro.
			
					if(isset($dados['tabela']['id_hosts_arquivos'])){
						// ===== Busca no banco de dados o ID referido.
						
						$id_hosts_arquivos = banco_escape_field($dados['tabela']['id_hosts_arquivos']);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_arquivos',
							))
							,
							"arquivos",
							"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
						);
						
						// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
						
						if($resultado){
							unset($dados['tabela']['id_hosts_arquivos']);
							
							$campo_tabela = "arquivos";
							$campo_tabela_extra = "WHERE id_hosts_arquivos='".$id_hosts_arquivos."'";
							
							foreach($dados['tabela'] as $chave => $dado){
								switch($chave){
									case 'permissao':
										$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
									break;
									default:
										$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
							$editar = false;$editar_sql = false;
						} else {
							$campos = null; $campo_sem_aspas_simples = null;
							
							foreach($dados['tabela'] as $chave => $dado){
								switch($chave){
									case 'permissao':
										$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
									break;
									default:
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								}
							}
							
							banco_insert_name
							(
								$campos,
								"arquivos"
							);
						}
					}
					
					// ===== Retornar os dados ou então erro senão conseguir mover o arquivo e arquivo mini para seus diretórios.
					
					if($files_moved){
						$retorno = Array(
							'status' => 'OK',
							'data' => $data,
						);
					} else {
						$retorno = Array(
							'status' => 'FILE_UPLOADED_WASNT_MOVED',
						);
					}
				} else {
					$retorno = Array(
						'status' => 'FILENAME_AND_EXTENTION_IS_MANDATORY',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'YEAR_AND_MONTH_IS_MANDATORY',
				);
			}
		break;
		case 'excluir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== O campo 'caminhoArquivo' é obrigatório.
			
			if(isset($dados['registro']['caminhoArquivo'])){
				// ===== Diretório conteúdo principal.
				
				$contentsPath = $_GESTOR['contents-path'];
				
				// ===== Excluir o arquivo do disco.
				
				if(is_file($contentsPath.'/'.$dados['registro']['caminhoArquivo'])){
					unlink($contentsPath.'/'.$dados['registro']['caminhoArquivo']);
					$files_deleted = true;
				} else {
					$files_deleted = false;
				}
				
				// ===== Conferir se foi enviado opção de excluir arquivo mini e exluí-lo do disco.
				
				if(isset($dados['registro']['caminhoArquivoMini'])){
					if(is_file($contentsPath.'/'.$dados['registro']['caminhoArquivoMini'])){
						unlink($contentsPath.'/'.$dados['registro']['caminhoArquivoMini']);
						$files_deleted = true;
					} else {
						$files_deleted = false;
					}
				}
				
				// ===== Busca no banco de dados o ID referido.
				
				if(isset($dados['tabela']['id_hosts_arquivos'])){
					
					$id_hosts_arquivos = banco_escape_field($dados['tabela']['id_hosts_arquivos']);
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_arquivos',
						))
						,
						"arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
					);
					
					// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
					
					if($resultado){
						unset($dados['tabela']['id_hosts_arquivos']);
						
						$campo_tabela = "arquivos";
						$campo_tabela_extra = "WHERE id_hosts_arquivos='".$id_hosts_arquivos."'";
						
						foreach($dados['tabela'] as $chave => $dado){
							switch($chave){
								default:
									$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
						$editar = false;$editar_sql = false;
					}
				}
				
				// ===== Retornar os dados ou então erro senão conseguir mover o arquivo e arquivo mini para seus diretórios.
				
				if($files_deleted){
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'FILES_WASNT_DELETED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'FILEPATH_IS_MANDATORY',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_servicos(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'adicionar':
		case 'editar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['servicos']['id_hosts_servicos']) && isset($dados['paginas']['id_hosts_paginas'])){
				// ===== SERVIÇO.
				
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_servicos = banco_escape_field($dados['servicos']['id_hosts_servicos']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_servicos',
					))
					,
					"servicos",
					"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					foreach($dados['servicos'] as $chave => $dado){
						switch($chave){
							case 'id_hosts_arquivos_Imagem':
							case 'preco':
							case 'quantidade':
							case 'lotesVariacoes':
							case 'gratuito':
								if(existe($dado)){ banco_update_campo($chave,$dado); } else { banco_update_campo($chave,'NULL',true); }
							break;
							default:
								banco_update_campo($chave,$dado);
						}
					}
					
					banco_update_executar('servicos',"WHERE id_hosts_servicos='".$id_hosts_servicos."'");
				} else {
					foreach($dados['servicos'] as $chave => $dado){
						switch($chave){
							case 'id_hosts_arquivos_Imagem':
							case 'preco':
							case 'quantidade':
							case 'lotesVariacoes':
							case 'gratuito':
								if(existe($dado)){ banco_insert_name_campo($chave,$dado); } else { banco_insert_name_campo($chave,'NULL',true); }
							break;
							default:
								banco_insert_name_campo($chave,$dado);
						}
					}
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"servicos"
					);
				}
				
				// ===== PÁGINA.
				
				// ===== Busca no banco de dados o ID de páginas.
				
				$id_hosts_paginas = banco_escape_field($dados['paginas']['id_hosts_paginas']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_paginas',
					))
					,
					"paginas",
					"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['paginas']['id_hosts_paginas']);
					
					$campo_tabela = "paginas";
					$campo_tabela_extra = "WHERE id_hosts_paginas='".$id_hosts_paginas."'";
					
					foreach($dados['paginas'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
							break;
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
					
					// ===== Se mudou o caminho, criar página 301 do caminho.
					
					if(isset($dados['caminhoMudou'])){
						$caminho = banco_escape_field($dados['caminhoMudou']['caminho']);
						$id_hosts_paginas_301 = banco_escape_field($dados['caminhoMudou']['id_hosts_paginas_301']);
						
						$campos = null; $campo_sem_aspas_simples = null;
						
						$campo_nome = "id_hosts_paginas_301"; $campo_valor = $id_hosts_paginas_301; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_hosts_paginas"; $campo_valor = $id_hosts_paginas; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "caminho"; $campo_valor = $caminho; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						banco_insert_name
						(
							$campos,
							"paginas_301"
						);
					}
				} else {
					$campos = null; $campo_sem_aspas_simples = null;
					
					foreach($dados['paginas'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							default:
								$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
					}
					
					banco_insert_name
					(
						$campos,
						"paginas"
					);
				}
				
				// ===== LOTES.
				
				if(count($dados['lotes']) > 0){
					foreach($dados['lotes'] as $lote){
						// ===== Busca no banco de dados o ID referido.
						
						$id_hosts_servicos_lotes = banco_escape_field($lote['id_hosts_servicos_lotes']);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_servicos_lotes',
							))
							,
							"servicos_lotes",
							"WHERE id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
						);
						
						// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
						
						if($resultado){
							foreach($lote as $chave => $dado){
								switch($chave){
									case 'dataInicio':
									case 'dataFim':
										if(existe($dado)){ banco_update_campo($chave,$dado); } else { banco_update_campo($chave,'NULL',true); }
									break;
									default:
										banco_update_campo($chave,$dado);
								}
							}
							
							banco_update_executar('servicos_lotes',"WHERE id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'");
						} else {
							foreach($lote as $chave => $dado){
								switch($chave){
									case 'dataInicio':
									case 'dataFim':
										if(existe($dado)){ banco_insert_name_campo($chave,$dado); } else { banco_insert_name_campo($chave,'NULL',true); }
									break;
									default:
										banco_insert_name_campo($chave,$dado);
								}
							}
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"servicos_lotes"
							);
						}
					}
				}
				
				// ===== Deletar lotes removidos no servidor.
				
				$servicos_lotes = banco_select(Array(
					'tabela' => 'servicos_lotes',
					'campos' => Array(
						'id_hosts_servicos_lotes',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				));
				
				if($servicos_lotes){
					foreach($servicos_lotes as $loteLocal){
						$excluir = true;
						$id_hosts_servicos_lotes = $loteLocal['id_hosts_servicos_lotes'];
						
						if(count($dados['lotes']) > 0)
						foreach($dados['lotes'] as $loteEnviado){
							if($loteLocal['id_hosts_servicos_lotes'] == $loteEnviado['id_hosts_servicos_lotes']){
								$excluir = false;
								break;
							}
						}
						
						if($excluir){
							banco_delete
							(
								"servicos_variacoes",
								"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
								." AND id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
							);
							banco_delete
							(
								"servicos_lotes",
								"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
								." AND id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
							);
						}
					}
				}
				
				// ===== VARIAÇÕES.
				
				if(count($dados['variacoes']) > 0){
					foreach($dados['variacoes'] as $variacao){
						// ===== Busca no banco de dados o ID referido.
						
						$id_hosts_servicos_variacoes = banco_escape_field($variacao['id_hosts_servicos_variacoes']);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_servicos_variacoes',
							))
							,
							"servicos_variacoes",
							"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
						);
						
						// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
						
						if($resultado){
							foreach($variacao as $chave => $dado){
								switch($chave){
									case 'gratuito':
										if(existe($dado)){ banco_update_campo($chave,$dado); } else { banco_update_campo($chave,'NULL',true); }
									break;
									default:
										banco_update_campo($chave,$dado);
								}
							}
							
							banco_update_executar('servicos_variacoes',"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'");
						} else {
							foreach($variacao as $chave => $dado){
								switch($chave){
									case 'gratuito':
										if(existe($dado)){ banco_insert_name_campo($chave,$dado); } else { banco_insert_name_campo($chave,'NULL',true); }
									break;
									default:
										banco_insert_name_campo($chave,$dado);
								}
							}
							
							banco_insert_name
							(
								banco_insert_name_campos(),
								"servicos_variacoes"
							);
						}
					}
				}
				
				// ===== Deletar variações removidos no servidor.
				
				$servicos_variacoes = banco_select(Array(
					'tabela' => 'servicos_variacoes',
					'campos' => Array(
						'id_hosts_servicos_variacoes',
					),
					'extra' => 
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				));
				
				if($servicos_variacoes){
					foreach($servicos_variacoes as $variacaoLocal){
						$excluir = true;
						$id_hosts_servicos_variacoes = $variacaoLocal['id_hosts_servicos_variacoes'];
						
						if(count($dados['variacoes']) > 0)
						foreach($dados['variacoes'] as $variacaoEnviado){
							if($variacaoLocal['id_hosts_servicos_variacoes'] == $variacaoEnviado['id_hosts_servicos_variacoes']){
								$excluir = false;
								break;
							}
						}
						
						if($excluir){
							banco_delete
							(
								"servicos_variacoes",
								"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
								." AND id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
							);
						}
					}
				}
				
				// ===== Retornar.
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		case 'status':
		case 'excluir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['servicos']['id_hosts_servicos']) && isset($dados['paginas']['id_hosts_paginas'])){
				// ===== SERVIÇO.
				
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_servicos = banco_escape_field($dados['servicos']['id_hosts_servicos']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_servicos',
					))
					,
					"servicos",
					"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['servicos']['id_hosts_servicos']);
					
					$campo_tabela = "servicos";
					$campo_tabela_extra = "WHERE id_hosts_servicos='".$id_hosts_servicos."'";
					
					foreach($dados['servicos'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				}
				
				// ===== PÁGINA.
				
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_paginas = banco_escape_field($dados['paginas']['id_hosts_paginas']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_paginas',
					))
					,
					"paginas",
					"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['paginas']['id_hosts_paginas']);
					
					$campo_tabela = "paginas";
					$campo_tabela_extra = "WHERE id_hosts_paginas='".$id_hosts_paginas."'";
					
					foreach($dados['paginas'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_vouchers(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'atualizar-status':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['vouchers']['id_hosts_vouchers'])){
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_vouchers = banco_escape_field($dados['vouchers']['id_hosts_vouchers']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_vouchers',
					))
					,
					"vouchers",
					"WHERE id_hosts_vouchers='".$id_hosts_vouchers."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['vouchers']['id_hosts_vouchers']);
					
					$campo_tabela = "vouchers";
					$campo_tabela_extra = "WHERE id_hosts_vouchers='".$id_hosts_vouchers."'";
					
					foreach($dados['vouchers'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_variaveis(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'editar':
		case 'gestor':
		case 'plugin':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			$registros = $dados['registros'];
			
			if(isset($registros)){
				// ===== Controle dos registros.
				
				$todos_ok = true;
				
				// ===== Varrer todos os registros.
				
				foreach($registros as $registro){
					if(
						isset($registro['modulo']) &&
						isset($registro['id'])
					){
						// ===== Busca no banco de dados o ID referido.
						
						$modulo = banco_escape_field($registro['modulo']);
						$id = banco_escape_field($registro['id']);
						
						$resultado = banco_select(Array(
							'unico' => true,
							'tabela' => 'variaveis',
							'campos' => Array(
								'id_variaveis',
								'id_hosts_variaveis',
							),
							'extra' => 
								"WHERE modulo='".$modulo."'"
								." AND id='".$id."'"
						));
						
						// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
						
						if($resultado){
							// ===== Se for atualização de variável padrão, verificar se não foi alterada pelo usuário. Se for, não atualizar e manter a versão atualizada pelo usuário via loja configurações.
							
							if(isset($dados['padroes'])){
								if(isset($resultado['id_hosts_variaveis'])){
									continue;
								}
							}
							
							$campo_tabela = "variaveis";
							$campo_tabela_extra = "WHERE modulo='".$modulo."'"
								." AND id='".$id."'";
							
							foreach($registro as $chave => $dado){
								switch($chave){
									default:
										$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
							$editar = false;$editar_sql = false;
						} else {
							$campos = null; $campo_sem_aspas_simples = null;
							
							foreach($registro as $chave => $dado){
								switch($chave){
									default:
										$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
								}
							}
							
							banco_insert_name
							(
								$campos,
								"variaveis"
							);
						}
					} else {
						$todos_ok = false;
					}
				}
				
				// ===== Caso algum tenha dado erro, retornar o erro.
				
				if($todos_ok){
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'ID_NOT_DEFINED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'EMPTY_RECORDS',
				);
			}
		break;
		case 'paypal':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			$registros = $dados['registros'];
			
			if(isset($registros)){
				// ===== Controle dos registros.
				
				$todos_ok = true;
				
				// ===== Varrer todos os registros.
				
				foreach($registros as $id => $valor){
					// ===== Busca no banco de dados o ID referido.
					
					$resultado = banco_select(Array(
						'unico' => true,
						'tabela' => 'variaveis',
						'campos' => Array(
							'id_variaveis',
						),
						'extra' => 
							"WHERE modulo='paypal'"
							." AND id='".$id."'"
					));
					
					// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
					
					if($resultado){
						banco_update_campo('valor',($valor == '1' ? '1' : 'NULL'),true);
						
						banco_update_executar('variaveis',"WHERE modulo='paypal' AND id='".$id."'");
					} else {
						banco_insert_name_campo('modulo','paypal');
						banco_insert_name_campo('id',$id);
						banco_insert_name_campo('valor',($valor == '1' ? '1' : 'NULL'),true);
						banco_insert_name_campo('tipo','bool');
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"variaveis"
						);
					}
				}
				
				// ===== Caso algum tenha dado erro, retornar o erro.
				
				if($todos_ok){
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'ID_NOT_DEFINED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'EMPTY_RECORDS',
				);
			}
		break;
		case 'google-recaptcha':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			$registros = $dados['registros'];
			
			if(isset($registros)){
				// ===== Controle dos registros.
				
				$todos_ok = true;
				
				// ===== Varrer todos os registros.
				
				foreach($registros as $id => $valor){
					// ===== Busca no banco de dados o ID referido.
					
					$resultado = banco_select(Array(
						'unico' => true,
						'tabela' => 'variaveis',
						'campos' => Array(
							'id_variaveis',
						),
						'extra' => 
							"WHERE modulo='google-recaptcha'"
							." AND id='".$id."'"
					));
					
					// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
					
					if($resultado){
						switch($id){
							case 'ativo':								
								banco_update_campo('valor',($valor == '1' ? '1' : 'NULL'),true);
							break;
							default:
								if(existe($valor)){
									banco_update_campo('valor',$valor);
								} else {
									banco_update_campo('valor','NULL',true);
								}
								
						}
						
						
						banco_update_executar('variaveis',"WHERE modulo='google-recaptcha' AND id='".$id."'");
					} else {
						banco_insert_name_campo('modulo','google-recaptcha');
						banco_insert_name_campo('id',$id);
						
						switch($id){
							case 'ativo':								
								banco_insert_name_campo('valor',($valor == '1' ? '1' : 'NULL'),true);
								banco_insert_name_campo('tipo','bool');
							break;
							default:
								if(existe($valor)){
									banco_insert_name_campo('valor',$valor);
								} else {
									banco_update_campo('valor','NULL',true);
								}
								
								banco_insert_name_campo('tipo','string');
								
						}
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"variaveis"
						);
					}
				}
				
				// ===== Caso algum tenha dado erro, retornar o erro.
				
				if($todos_ok){
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'ID_NOT_DEFINED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'EMPTY_RECORDS',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_pedidos(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'atualizar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['pedidos'])){
				foreach($dados['pedidos'] as $id_hosts_pedidos => $pedido){
					// ===== Busca no banco de dados o ID referido.
					
					$pedidos = banco_select(Array(
						'unico' => true,
						'tabela' => 'pedidos',
						'campos' => Array(
							'id_pedidos',
						),
						'extra' => 
							"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
					));
					
					// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
					
					if($pedidos){
						foreach($pedido as $campo => $valor){
							switch($campo){
								case 'live':
									banco_update_campo($campo,(existe($valor) ? $valor : 'NULL'),true);
								break;
								default:
									banco_update_campo($campo,$valor);
							}
						}
						
						banco_update_executar('pedidos',"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'");
					}
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'SERVICES_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_cron_servicos(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'quantidade':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['servicos'])){
				foreach($dados['servicos'] as $id_hosts_servicos => $quantidadeEstoqueAlterar){
					// ===== Busca no banco de dados o ID referido.
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_servicos',
						))
						,
						"servicos",
						"WHERE id_hosts_servicos='".$id_hosts_servicos."'"
					);
					
					// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
					
					if($resultado){
						$campo_tabela = "servicos";
						$campo_tabela_extra = "WHERE id_hosts_servicos='".$id_hosts_servicos."'";
						
						$campo_nome = 'quantidade'; $editar[$campo_tabela][] = (existe($quantidadeEstoqueAlterar) ? $campo_nome."=".$quantidadeEstoqueAlterar : $campo_nome."=NULL");
						
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
					}
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'SERVICES_NOT_DEFINED',
				);
			}
		break;
		case 'quantidadeVariacao':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['variacaoServicos'])){
				foreach($dados['variacaoServicos'] as $id_hosts_servicos_variacoes => $quantidadeEstoqueAlterar){
					// ===== Busca no banco de dados o ID referido.
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_servicos_variacoes',
						))
						,
						"servicos_variacoes",
						"WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
					);
					
					// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
					
					if($resultado){
						$campo_tabela = "servicos_variacoes";
						$campo_tabela_extra = "WHERE id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'";
						
						$campo_nome = 'quantidade'; $editar[$campo_tabela][] = (existe($quantidadeEstoqueAlterar) ? $campo_nome."=".$quantidadeEstoqueAlterar : $campo_nome."=NULL");
						
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
					}
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'SERVICES_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_cron_pedidos(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'atualizar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['pedidos'])){
				foreach($dados['pedidos'] as $id_hosts_pedidos => $pedido){
					// ===== Busca no banco de dados o ID referido.
					
					$pedidos = banco_select(Array(
						'unico' => true,
						'tabela' => 'pedidos',
						'campos' => Array(
							'id_pedidos',
						),
						'extra' => 
							"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'"
					));
					
					// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
					
					if($pedidos){
						foreach($pedido as $campo => $valor){
							switch($campo){
								case 'live':
									banco_update_campo($campo,(existe($valor) ? $valor : 'NULL'),true);
								break;
								default:
									banco_update_campo($campo,$valor);
							}
						}
						
						banco_update_executar('pedidos',"WHERE id_hosts_pedidos='".$id_hosts_pedidos."'");
					}
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ORDERS_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_postagens(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'adicionar':
		case 'editar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['postagens']['id_hosts_postagens']) && isset($dados['paginas']['id_hosts_paginas'])){
				// ===== POSTAGEM.
				
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_postagens = banco_escape_field($dados['postagens']['id_hosts_postagens']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_postagens',
					))
					,
					"postagens",
					"WHERE id_hosts_postagens='".$id_hosts_postagens."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['postagens']['id_hosts_postagens']);
					
					$campo_tabela = "postagens";
					$campo_tabela_extra = "WHERE id_hosts_postagens='".$id_hosts_postagens."'";
					
					foreach($dados['postagens'] as $chave => $dado){
						switch($chave){
							case 'id_hosts_arquivos_Imagem':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
							break;
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				} else {
					$campos = null; $campo_sem_aspas_simples = null;
					
					foreach($dados['postagens'] as $chave => $dado){
						switch($chave){
							case 'id_hosts_arquivos_Imagem':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							default:
								$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
					}
					
					banco_insert_name
					(
						$campos,
						"postagens"
					);
				}
				
				// ===== PÁGINA.
				
				// ===== Busca no banco de dados o ID de páginas.
				
				$id_hosts_paginas = banco_escape_field($dados['paginas']['id_hosts_paginas']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_paginas',
					))
					,
					"paginas",
					"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['paginas']['id_hosts_paginas']);
					
					$campo_tabela = "paginas";
					$campo_tabela_extra = "WHERE id_hosts_paginas='".$id_hosts_paginas."'";
					
					foreach($dados['paginas'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=1" : $campo_nome."=NULL");
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $editar[$campo_tabela][] = (existe($dado) ? $campo_nome."=".$dado : $campo_nome."=NULL");
							break;
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
					
					// ===== Se mudou o caminho, criar página 301 do caminho.
					
					if(isset($dados['caminhoMudou'])){
						$caminho = banco_escape_field($dados['caminhoMudou']['caminho']);
						$id_hosts_paginas_301 = banco_escape_field($dados['caminhoMudou']['id_hosts_paginas_301']);
						
						$campos = null; $campo_sem_aspas_simples = null;
						
						$campo_nome = "id_hosts_paginas_301"; $campo_valor = $id_hosts_paginas_301; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "id_hosts_paginas"; $campo_valor = $id_hosts_paginas; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "caminho"; $campo_valor = $caminho; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						banco_insert_name
						(
							$campos,
							"paginas_301"
						);
					}
				} else {
					$campos = null; $campo_sem_aspas_simples = null;
					
					foreach($dados['paginas'] as $chave => $dado){
						switch($chave){
							case 'template_padrao':
							case 'template_modificado':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? "1" : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							case 'template_versao':
							case 'modulo_id_registro':
								$campo_nome = $chave; $campo_valor = (existe($dado) ? $dado : "NULL"); 	$campos[] = Array($campo_nome,$campo_valor,true);
							break;
							default:
								$campo_nome = $chave; $campo_valor = banco_escape_field($dado); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
						}
					}
					
					banco_insert_name
					(
						$campos,
						"paginas"
					);
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		case 'status':
		case 'excluir':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			if(isset($dados['postagens']['id_hosts_postagens']) && isset($dados['paginas']['id_hosts_paginas'])){
				// ===== POSTAGEM.
				
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_postagens = banco_escape_field($dados['postagens']['id_hosts_postagens']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_postagens',
					))
					,
					"postagens",
					"WHERE id_hosts_postagens='".$id_hosts_postagens."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['postagens']['id_hosts_postagens']);
					
					$campo_tabela = "postagens";
					$campo_tabela_extra = "WHERE id_hosts_postagens='".$id_hosts_postagens."'";
					
					foreach($dados['postagens'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				}
				
				// ===== PÁGINA.
				
				// ===== Busca no banco de dados o ID referido.
				
				$id_hosts_paginas = banco_escape_field($dados['paginas']['id_hosts_paginas']);
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_paginas',
					))
					,
					"paginas",
					"WHERE id_hosts_paginas='".$id_hosts_paginas."'"
				);
				
				// ===== Se existir atualiza a tabela com os dados enviados, senão cria um novo registro com os dados enviados.
				
				if($resultado){
					unset($dados['paginas']['id_hosts_paginas']);
					
					$campo_tabela = "paginas";
					$campo_tabela_extra = "WHERE id_hosts_paginas='".$id_hosts_paginas."'";
					
					foreach($dados['paginas'] as $chave => $dado){
						switch($chave){
							default:
								$campo_nome = $chave; $editar[$campo_tabela][] = $campo_nome."='" . banco_escape_field($dado) . "'";
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
					$editar = false;$editar_sql = false;
				}
				
				$retorno = Array(
					'status' => 'OK',
				);
			} else {
				$retorno = Array(
					'status' => 'ID_NOT_DEFINED',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

function plataforma_servidor_menus(){
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'atualizar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Verifica o ID referencial do registro.
			
			$registros = $dados['registros'];
			
			if(isset($registros)){
				// ===== Controle dos registros.
				
				$todos_ok = true;
				
				// ===== Pegar os dados atuais do banco de dados.
				
				$menus_itens = banco_select(Array(
					'tabela' => 'menus_itens',
					'campos' => '*',
					'extra' => 
						""
				));
				
				// ===== Varrer todos os registros.
				
				foreach($registros as $item){
					$menu_id = $item['menu_id'];
					$id = $item['id'];
					
					$found = false;
					
					if($menus_itens)
					foreach($menus_itens as $key => $menu_item){
						if(
							$menu_item['menu_id'] == $menu_id &&
							$menu_item['id'] == $id
						){
							$menus_itens[$key]['verificado'] = true;
							$found = true;
							break;
						}
					}
					
					// ===== Incluir ou atualizar o banco de dados.
					
					if($found){
						if($item)
						foreach($item as $key => $valor){
							switch($key){
								case 'inativo':								
									banco_update_campo($key,($valor == '1' ? '1' : 'NULL'),true);
								break;
								case 'versao':								
									banco_update_campo($key,(existe($valor) ? $valor : 'NULL'),true);
								break;
								default:
									if(existe($valor)){
										banco_update_campo($key,$valor);
									} else {
										banco_update_campo($key,'NULL',true);
									}
									
							}
						}
						
						banco_update_executar('menus_itens',"WHERE menu_id='".$menu_id."' AND id='".$id."'");
					} else {
						if($item)
						foreach($item as $key => $valor){
							if(existe($valor)){
								switch($key){
									case 'inativo':	
									case 'versao':							
										banco_insert_name_campo($key,$valor,true);
									break;
									default:
										banco_insert_name_campo($key,$valor);
								}
							}
						}
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"menus_itens"
						);
					}
				}
				
				// ===== Excluir do banco de dados itens removidos.
				
				if($menus_itens)
				foreach($menus_itens as $menu_item){
					if(!isset($menu_item['verificado'])){
						banco_delete
						(
							"menus_itens",
							"WHERE menu_id='".$menu_item['menu_id']."'"
							." AND id='".$menu_item['id']."'"
						);
					}
				}
				
				// ===== Caso algum tenha dado erro, retornar o erro.
				
				if($todos_ok){
					$retorno = Array(
						'status' => 'OK',
					);
				} else {
					$retorno = Array(
						'status' => 'ID_NOT_DEFINED',
					);
				}
			} else {
				$retorno = Array(
					'status' => 'EMPTY_RECORDS',
				);
			}
		break;
		default:
			$retorno = Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}

	return $retorno;
}

// =========================== Funções de Acesso

function plataforma_servidor_autenticar_cliente(){
	global $_GESTOR;
	
	if(isset($_REQUEST['token']) && isset($_REQUEST['token_validacao_id'])){
		$token = $_REQUEST['token'];
		$token_validacao_id = banco_escape_field($_REQUEST['token_validacao_id']);
		
		// ===== Verifica se existe o token.
		
		$JWTToken = $token;
		
		if(!existe($JWTToken)){
			return false;
		}
		
		// ===== Abrir chave privada e a senha da chave
		
		$chavePrivada = $_GESTOR['plataforma-cliente']['chave-seguranca']['chave'];
		$chavePrivadaSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['senha'];
		$hashAlgo = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-algo'];
		$hashSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-senha'];
		
		// ===== Verificar se o JWT é válido.
		
		$tokenPubId = plataforma_servidor_validar_jwt(Array(
			'token' => $JWTToken,
			'chavePrivada' => $chavePrivada,
			'chavePrivadaSenha' => $chavePrivadaSenha,
		));
		
		if($tokenPubId){
			// ===== Verifica se o token está ativo. Senão estiver invalidar o token.
			
			$plataforma_tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'pubIDValidation',
				))
				,
				"plataforma_tokens",
				"WHERE pubID='".$token_validacao_id."'"
			);
			
			if($plataforma_tokens){
				// ===== Limpeza dos tokens mais antigos no banco de dados.
				
				$invalidar_token = false;
				
				// ===== Deletar todos os tokens que atingiram o tempo de expiração.
				
				banco_delete
				(
					"plataforma_tokens",
					"WHERE expiration < ".time()
				);
				
				// ===== Verificar se um dos tokens excluídos é o token atual. Se sim, invalidar token.
				
				$plataforma_tokens_verificar = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_plataforma_tokens',
					))
					,
					"plataforma_tokens",
					"WHERE pubID='".$token_validacao_id."'"
				);
				
				if(!$plataforma_tokens_verificar){
					$invalidar_token = true;
				}
				
				if(!$invalidar_token){
					// ===== Validar o token com o hash de validação para evitar geração de token por hacker caso ocorra roubo da tabela 'plataforma_tokens'.
					
					$bd_hash = $plataforma_tokens[0]['pubIDValidation'];
					$token_hash = hash_hmac($hashAlgo, $token_validacao_id, $hashSenha);
					
					if($bd_hash === $token_hash){
						$_GESTOR['plataforma-token-id'] = $token_validacao_id;
						
						return true;
					}
				}
			}
		}
	}
	
	return false;
}

function plataforma_servidor_autenticacao(){
	global $_GESTOR;
	
	if(isset($_REQUEST['token'])){
		
		$token_validacao_id = plataforma_servidor_validar_token_autorizacao(Array(
			'token' => $_REQUEST['token'],
		));
		
		if($token_validacao_id){
			// ===== Procurar no config os hosts do servidor e o pub id.
			
			$pubId = $_GESTOR['plataforma-cliente']['id'];
			$hostsServidor = $_GESTOR['plataforma-cliente']['hosts'];
			$hashAlgo = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-algo'];
			$hashSenha = $_GESTOR['plataforma-cliente']['chave-seguranca']['hash-senha'];
			
			foreach($hostsServidor as $host){
				if($host['id'] == $_REQUEST['plataforma-id']){
					$dominioServidor = $host['host'];
					break;
				}
			}
			
			if(isset($dominioServidor)){
				$retorno['error-msg'] = '';
				$retorno['error'] = false;
				$retorno['completed'] = false;
				
				$token = plataforma_servidor_gerar_token_autorizacao(Array(
					'serverHost' => $dominioServidor,
				));
				
				// ===== Deletar todos os tokens que atingiram o tempo de expiração.
				
				banco_delete
				(
					"plataforma_tokens",
					"WHERE expiration < ".time()
				);
				
				// ===== Verificar pubID do Token remoto.
				
				$plataforma_tokens = banco_select(Array(
					'unico' => true,
					'tabela' => 'plataforma_tokens',
					'campos' => Array(
						'pubID',
					),
					'extra' => 
						"WHERE remoto IS NOT NULL"
				));
				
				if($plataforma_tokens){
					$tokenPubId = $plataforma_tokens['pubID'];
					
					if($tokenPubId == $token_validacao_id){
						return true;
					}
				}
				
				// ===== Conectar na plataforma do servidor na interface 'autenticar'.
				
				$url = $dominioServidor . '/_plataforma/autenticar/';
				
				$data = false;
				
				$data['token'] = $token;
				$data['token_validacao_id'] = $token_validacao_id;
				$data['id'] = $pubId;
				
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
				
				$plataformaRetorno = json_decode($json,true);
				
				// ===== Tratar os erros de retorno da plataforma caso haja ou então devolver o retorno para a requisição com o status ok e/ou os dados.
				
				if(!$plataformaRetorno){
					$retorno['error-msg'] = '[no-json] '.$json; $retorno['error'] = true;
				} else if($plataformaRetorno['error']){
					$retorno['error-msg'] = '[error] '.$plataformaRetorno['error'].' '.$plataformaRetorno['error_msg']; $retorno['error'] = true;
				} else if($plataformaRetorno['status'] != 'OK'){
					$retorno['error-msg'] = '[not-OK] '.$plataformaRetorno['status']; $retorno['error'] = true;
				} else {
					if($plataformaRetorno['data']) $retorno['data'] = $plataformaRetorno['data'];
					$retorno['completed'] = true;
					
					// ===== Armazenar token remoto.
					
					$tokenPubId = $token_validacao_id;
					
					$pubIDValidation = hash_hmac($hashAlgo, $tokenPubId, $hashSenha);
					$expiration = time() + $_GESTOR['platform-lifetime'];
					
					// ====== Salvar token no banco
					
					$campos = null; $campo_sem_aspas_simples = null;
					
					$campo_nome = "pubID"; $campo_valor = $tokenPubId; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "expiration"; $campo_valor = $expiration; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 					$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "remoto"; $campo_valor = '1'; 							$campos[] = Array($campo_nome,$campo_valor,true);
					
					banco_insert_name
					(
						$campos,
						"plataforma_tokens"
					);
				}
				
				return $retorno['completed'];
			}
		}
	}
	
	return false;
}

function plataforma_servidor_404(){
	http_response_code(404);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '404',
		'info' => 'JSON not found',
	));
	exit;
}

function plataforma_servidor_401(){
	http_response_code(401);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'ERROR',
		'statusCode' => '401',
		'info' => 'JSON unauthorized',
	));
	exit;
}

function plataforma_servidor_200(){
	http_response_code(200);
	
	header("Content-Type: application/json; charset: UTF-8");
	echo json_encode(Array(
		'status' => 'OK',
		'statusCode' => '200',
	));
	exit;
}

function plataforma_servidor_start(){
	global $_GESTOR;
	global $_INDEX;
	
	// ===== Caso seja uma operação de autenticar o servidor no cliente
	
	switch($_GESTOR['caminho'][1]){
		case 'autenticar':
			if(!plataforma_servidor_autenticar_cliente()){
				plataforma_servidor_401();
			} else {
				plataforma_servidor_200();
			}
		break;
	}
	
	// ===== Verifica se o servidor tem autorização para acessar a plataforma. Senão retornar JSON 401.
	
	if(!plataforma_servidor_autenticacao()){
		plataforma_servidor_401();
	}
	
	// ===== Verifica a opção, executa interface caso encontrado e retorna os dados
	
	switch($_GESTOR['caminho'][1]){
		case 'api-testes':					 $dados = plataforma_servidor_api_testes(); break;
		case 'paginas':						 $dados = plataforma_servidor_paginas(); break;
		case 'layouts':						 $dados = plataforma_servidor_layouts(); break;
		case 'componentes':					 $dados = plataforma_servidor_componentes(); break;
		case 'templates-atualizar':			 $dados = plataforma_servidor_templates_atualizar(); break;
		case 'arquivos':					 $dados = plataforma_servidor_arquivos(); break;
		case 'servicos':					 $dados = plataforma_servidor_servicos(); break;
		case 'variaveis':					 $dados = plataforma_servidor_variaveis(); break;
		case 'pedidos':						 $dados = plataforma_servidor_pedidos(); break;
		case 'cron-servicos':				 $dados = plataforma_servidor_cron_servicos(); break;
		case 'cron-pedidos':				 $dados = plataforma_servidor_cron_pedidos(); break;
		case 'postagens':					 $dados = plataforma_servidor_postagens(); break;
		case 'vouchers':					 $dados = plataforma_servidor_vouchers(); break;
		case 'menus':						 $dados = plataforma_servidor_menus(); break;
	}
	
	// ===== Caso haja dados criados por alguma opção, retornar JSON e finalizar. Senão retornar JSON 404.
	
	if(isset($dados)){
		// ===== Se o status for diferente de Ok. Então incluir o caminho no retorno.
		
		if($dados['status'] != 'OK'){
			$dados['caminho'] = $_GESTOR['caminho'][1];
		}
		
		// ===== Retornar o JSON formatado.
		
		header("Content-Type: application/json; charset: UTF-8");
		echo json_encode($dados);
		exit;
	}
	
	plataforma_servidor_404();
}

// =========================== Inciar Plataforma

plataforma_servidor_start();

?>