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

// Funções Internas

global $_VERSAO;
global $_SCRIPTS_JS;$_SCRIPTS_JS[] = 'includes/autenticar/js.js?v='.$_VERSAO;
global $_STYLESHEETS;$_STYLESHEETS[] = 'includes/autenticar/css.css?v='.$_VERSAO;

function autenticar_perfil_select(){
	global $_SYSTEM;
	
	$nome = 'perfil';
	$id = $nome . '_id';
	
	$tabela = banco_select
	(
		"nome",
		"usuario_perfil",
		"WHERE id_usuario_perfil='".$_SESSION[$_SYSTEM['ID'].$id]."'"
	);
	
	return $tabela[0][0];
}

function autenticar_minha_conta_historico($params){
	/* 
	$select = opcao_select_change(Array(
		'nome' => 'select',
		'tabela_campos' => Array(
			'id' => 'id',
			'nome' => 'nome',
		),
		'tabela_nome' => 'tabela_nome',
		'tabela_extra' => false,
		'tabela_order' => 'campo DESC',
		'opcao_inicial' => 'Selecione',
		'link_extra' => 'opcao=valor',
		'url' => false,
		'onchange' => true,
		'extra_select' => false,
		'option_value_igual_nome' => false,
	));
	*/
	
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	$nome = $params['nome'];
	$tabela_campos = $params['tabela_campos'];
	$tabela_nome = $params['tabela_nome'];
	$tabela_extra = $params['tabela_extra'];
	$tabela_order = $params['tabela_order'];
	$opcao_inicial = $params['opcao_inicial'];
	$opcao_inicial_id = $params['opcao_inicial_id'];
	$link_extra = $params['link_extra'];
	$onchange = $params['onchange'];
	$option_value_igual_nome = $params['option_value_igual_nome'];
	$extra_select = $params['extra_select'];
	$url = $params['url'];
	$id = $nome . '_id';
	
	$_SESSION[$_SYSTEM['ID'].$id] = $_SESSION[$_SYSTEM['ID'].'historico_id'];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultados = banco_select_name
	(
		banco_campos_virgulas(Array(
			$tabela_campos['id'],
			$tabela_campos['nome'],
			$tabela_campos['moderacao'],
		))
		,
		$tabela_nome,
		$tabela_extra
		.($tabela_order ? " ORDER BY " . $tabela_order : "")
	);
	if($connect_db)banco_fechar_conexao();
	
	if($opcao_inicial){
		$options[] = $opcao_inicial;
		$optionsValue[] = ($opcao_inicial_id?$opcao_inicial_id:"-1");
	}
	
	$cont = 0;
	if($resultados){
		foreach($resultados as $resultado){
			if($resultado[$tabela_campos['moderacao']])$options[] = 'Aguardando Moderação'; else $options[] = data_hora_from_datetime_to_text($resultado[$tabela_campos['nome']]);
			$optionsValue[] = ($option_value_igual_nome ? $resultado[$tabela_campos['nome']] : $resultado[$tabela_campos['id']]);
			
			$cont++;
			
			if($_SESSION[$_SYSTEM['ID'].$id] == $resultado[$tabela_campos['id']]){
				$optionSelected = $cont;
				if(!$opcao_inicial)$optionSelected--;
			}
		}
		
		if($link_extra)$link_extra .= '&';
		$url .= '?';
		$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,($onchange ? 'onchange=window.open("'.$url.$link_extra.$id.'="+this.value,"_self")' : '') . ($extra_select ? ($onchange ? ' ' : '') . $extra_select : ''));
		
		return $select;
	} else {
		return false;
	}
}

function autenticar_minha_conta(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_CONEXAO_BANCO;
	global $_OPCAO;
	
	$id = $_SESSION[$_SYSTEM['ID']."usuario"]["id_usuario"];
	
	if($id){
		if($_PROJETO['autenticar']){
			if($_PROJETO['autenticar']['minha_conta']){
				$pagina = $_PROJETO['ecommerce']['minha_conta'];
			}
		}
		
		if(!$pagina){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- minha-conta < -->','<!-- minha-conta > -->');
		}
		
		$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
		$_HTML_DADOS['titulo'] = $titulo . 'Minha Conta.';
		
		$_HTML_DADOS['description'] = 'Página para visualizar e/ou modificar todos os dados do meu cadastro.';
		$_HTML_DADOS['keywords'] = 'meus dados,minha conta,conta pessoal,usuario,senha,email';
		
		$num_cols = 3;
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		if($_SESSION[$_SYSTEM['ID'].'historico_id']){
			if($_SESSION[$_SYSTEM['ID'].'historico_id'] != $id){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuario',
					))
					,
					'usuario',
					"WHERE id_usuario='".$_SESSION[$_SYSTEM['ID'].'historico_id']."'"
					." AND id_usuario_original='".$id."'"
				);
			} else {
				$resultado = true;
			}
		} else {
			$resultado = true;
		}
		
		if($resultado){
			// ================================= Local de Edição ===============================
			// Altere os campos da interface com os valores iniciais
			
			$cel1 = modelo_tag_val($pagina,'<!-- cel1 < -->','<!-- cel1 > -->');
			$pagina = modelo_tag_in($pagina,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');
			$cel2 = modelo_tag_val($pagina,'<!-- cel2 < -->','<!-- cel2 > -->');
			$pagina = modelo_tag_in($pagina,'<!-- cel2 < -->','<!-- cel2 > -->','<!-- cel2 -->');
			$pagina = modelo_tag_in($pagina,'<!-- grupo_cel < -->','<!-- grupo_cel > -->','<!-- grupo_cel -->');
			
			$hitorico = autenticar_minha_conta_historico(Array(
				'nome' => '_minha-conta-historico',
				'tabela_campos' => Array(
					'nome' => 'data_cadastro',
					'id' => 'id_usuario',
					'moderacao' => 'moderacao',
				),
				'tabela_nome' => 'usuario',
				'tabela_nome' => 'usuario',
				'tabela_extra' => "WHERE id_usuario_original='".$id."'",
				'tabela_order' => "data_cadastro DESC",
				'opcao_inicial' => 'Versão Atual',
				'opcao_inicial_id' => $id,
			));
			
			if($hitorico){
				$hitorico = html(Array(
					'tag' => 'h2',
					'val' => 'Histórico de Modificações: '.$hitorico,
					'attr' => false
				));
			}
			
			$pagina = paginaTrocaVarValor($pagina,'#historico#',$hitorico);
			
			$pagina = modelo_var_troca($pagina,"#grupos_num",count($grupos));
		
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuario_perfil',
					'usuario',
					'senha',
					'nome',
					'email',
					'sobrenome',
					'cep',
					'endereco',
					'numero',
					'complemento',
					'bairro',
					'cidade',
					'uf',
					'celular',
					'telefone',
				))
				,
				'usuario',
				($_SESSION[$_SYSTEM['ID'].'historico_id'] && $_SESSION[$_SYSTEM['ID'].'historico_id'] != $id ?"WHERE id_usuario='".$_SESSION[$_SYSTEM['ID'].'historico_id']."'":"WHERE id_usuario='".$id."'")
			);
			
			$campos_guardar = Array(
				'usuario' => $usuario[0]['usuario'],
				'senha' => $usuario[0]['senha'],
				'id_usuario_perfil' => $usuario[0]['id_usuario_perfil'],
				'email' => $usuario[0]['email'],
				'nome' => $usuario[0]['nome'],
				'sobrenome' => $usuario[0]['sobrenome'],
				'cep' => $usuario[0]['cep'],
				'endereco' => $usuario[0]['endereco'],
				'numero' => $usuario[0]['numero'],
				'complemento' => $usuario[0]['complemento'],
				'bairro' => $usuario[0]['bairro'],
				'cidade' => $usuario[0]['cidade'],
				'uf' => $usuario[0]['uf'],
				'celular' => $usuario[0]['celular'],
				'telefone' => $usuario[0]['telefone'],
				'id_grupo' => $id_grupo,
			);
			
			$_SESSION[$_SYSTEM['ID'].'perfil_id'] = $usuario[0]['id_usuario_perfil'];
			
			$pagina = paginaTrocaVarValor($pagina,'#usuario',$usuario[0]['usuario']);
			$pagina = paginaTrocaVarValor($pagina,'#edit_usuario',$usuario[0]['usuario']);
			$pagina = paginaTrocaVarValor($pagina,'#senha','');
			$pagina = paginaTrocaVarValor($pagina,'#senha2','');
			$pagina = paginaTrocaVarValor($pagina,'#perfil',autenticar_perfil_select());
			$pagina = paginaTrocaVarValor($pagina,'#email',$usuario[0]['email']);
			$pagina = paginaTrocaVarValor($pagina,'#edit_email',$usuario[0]['email']);
			$pagina = paginaTrocaVarValor($pagina,'#nome',$usuario[0]['nome']);
			$pagina = paginaTrocaVarValor($pagina,'#sobrenome',$usuario[0]['sobrenome']);
			$pagina = paginaTrocaVarValor($pagina,'#cep',$usuario[0]['cep']);
			$pagina = paginaTrocaVarValor($pagina,'#endereco',$usuario[0]['endereco']);
			$pagina = paginaTrocaVarValor($pagina,'#numero',$usuario[0]['numero']);
			$pagina = paginaTrocaVarValor($pagina,'#complemento',$usuario[0]['complemento']);
			$pagina = paginaTrocaVarValor($pagina,'#bairro',$usuario[0]['bairro']);
			$pagina = paginaTrocaVarValor($pagina,'#cidade',$usuario[0]['cidade']);
			$pagina = paginaTrocaVarValor($pagina,'#uf',$usuario[0]['uf']);
			$pagina = paginaTrocaVarValor($pagina,'#tel',$usuario[0]['telefone']);
			$pagina = paginaTrocaVarValor($pagina,'#cel',$usuario[0]['celular']);
			
			// ======================================================================================
			
			campos_antes_guardar($campos_guardar);
			
			$in_titulo = $param ? "Visualizar" : "Modificar";
			$botao = "Gravar";
			$opcao = "minha-conta-banco";
			
			$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
			$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
			$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
			$pagina = paginaTrocaVarValor($pagina,"#id",$id);
			$pagina = paginaTrocaVarValor($pagina,"#cep_search",($_SYSTEM['CEP_SEARCH']?'1':''));
		
			return $pagina;
		} else {
			alerta('<p>Você não tem permissão para acessar essa área! ERRO: minha-conta 1</p>');
			redirecionar('autenticar');
		}
	} else {
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = $_OPCAO;
		redirecionar('autenticar');
	}
}

function autenticar_minha_conta_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_ALERTA;
	
	if($_POST["id"]){
		$id = $_POST["id"];
		
		$id_usuario = $_SESSION[$_SYSTEM['ID']."usuario"]["id_usuario"];
		
	
		if($id != $id_usuario){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuario',
				))
				,
				'usuario',
				"WHERE id_usuario='".$id."'"
				." AND id_usuario_original='".$id_usuario."'"
			);
		} else {
			$resultado = true;
		}
		
		if($resultado){
			$campos_antes = campos_antes_recuperar();
			
			$campos_editaveis = Array(
				'usuario',
				'senha',
				'email',
				'nome',
				'sobrenome',
				'endereco',
				'numero',
				'complemento',
				'bairro',
				'cidade',
				'uf',
				'cep',
				'telefone',
				'celular',
			);
			
			banco_conectar();
			
			// ================================= Guardar histórico =============================
			
			foreach($campos_editaveis as $campo){
				$campo_nome = $campo; $post_nome = $campo_nome; 
				
				switch($campo){
					case 'senha':
						if($_POST[$campo_nome])$mudou_senha = true;
					break;
					default:
						if($campos_antes[$campo_nome] != $_POST[$campo_nome])$mudou_campos = true;
				}
			}
			
			// ================================= Local de Edição ===============================
			
			$campo_tabela = "usuario";
			
			foreach($campos_editaveis as $campo){
				$campo_nome = $campo; $post_nome = $campo_nome; 
				
				switch($campo){
					case 'senha':
						if($_POST['senha'] == $_POST['senha2'])
							if($_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . crypt(strip_tags($_POST[$campo_nome])) . "'";}
					break;
					default:
						if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_POST[$campo_nome]) . "'";}
				}
			}
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					'usuario',
					"WHERE id_usuario='".$id."'"
				);
			}
			
			$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'D');
			$campo_nome = "id_usuario_original"; $post_nome = $campo_nome; 	$campos[] = Array($campo_nome,$id);
			$campo_nome = "id_usuario_perfil"; $post_nome = $campo_nome;	$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
			
			foreach($campos_editaveis as $campo){
				$campo_nome = $campo; $post_nome = $campo_nome;  			$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
			}
			
			$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
			
			banco_insert_name($campos,'usuario');
			
			banco_fechar_conexao();
			
			$_SESSION[$_SYSTEM['ID'].'historico_id'] = $id;
		}
	}
	
	redirecionar('minha-conta');;
}

function autenticar_login_facebook(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ECOMMERCE;
	
	try{
		$hybridauth_path = $_SYSTEM['PATH'] . 'includes'.$_SYSTEM['SEPARADOR'].'php'.$_SYSTEM['SEPARADOR'].'hybridauth'.$_SYSTEM['SEPARADOR'];
		
		$config_file_path = $hybridauth_path.'config.php';
		require_once( $hybridauth_path."Hybrid".$_SYSTEM['SEPARADOR']."Auth.php" );
		
		$hybridauth = new Hybrid_Auth( $config_file_path );
		
		$adapter = $hybridauth->authenticate( "Facebook" );
		
		$user_profile = $adapter->getUserProfile();
		
		global $_CONEXAO_BANCO;
	
		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$usuarios = banco_select_name
		(
			"*"
			,
			"usuario",
			"WHERE facebook_id='" . $user_profile->identifier . "'"
		);
		
		if(!$_SESSION[$_SYSTEM['ID'].'logar-local']){
			$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'meus-pedidos';
		}
		
		if(!$usuarios){
			//if($_SESSION[$_SYSTEM['ID'].'autenticar-id_usuario_perfil']){
			//	$id_usuario_perfil = $_SESSION[$_SYSTEM['ID'].'autenticar-id_usuario_perfil'];
			//	$_SESSION[$_SYSTEM['ID'].'autenticar-id_usuario_perfil'] = false;
			//}
			
			$id_usuario_perfil = $_ECOMMERCE['permissao_usuario'];
			
			$campo_nome = "id_usuario_perfil"; 								$campos[] = Array($campo_nome,($id_usuario_perfil?$id_usuario_perfil:'2'));
			$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
			$campo_nome = "usuario"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$user_profile->email);
			$campo_nome = "facebook_id"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$user_profile->identifier);
			$campo_nome = "senha"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,crypt(senha_gerar(10)));
			$campo_nome = "email"; $post_nome = $campo_nome; 				if($user_profile->email)$campos[] = Array($campo_nome,$user_profile->email);
			$campo_nome = "nome"; $post_nome = $campo_nome; 				if($user_profile->displayName)$campos[] = Array($campo_nome,$user_profile->displayName);
			$campo_nome = "endereco"; $post_nome = $campo_nome; 			if($user_profile->address)$campos[] = Array($campo_nome,$user_profile->address);
			$campo_nome = "cep"; $post_nome = $campo_nome; 					if($user_profile->zip)$campos[] = Array($campo_nome,$user_profile->zip);
			$campo_nome = "telefone"; $post_nome = $campo_nome; 			if($user_profile->phone)$campos[] = Array($campo_nome,$user_profile->phone);
			$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
			
			banco_insert_name
			(
				$campos,
				"usuario"
			);
			
			$id_usuario = banco_last_id();
			
			$url = $user_profile->photoURL;
			$headers = get_headers($url);
			if(strpos($headers[0], 'Found') || strpos($headers[0], '200')) {
				if(strpos($headers[0], 'Found'))$url =  preg_replace('/Location: /i', '',$headers[5]);
				
				list($width, $height, $type, $attr) = getimagesize($url);
				
				switch($type){
					case 1: $ext = 'gif';$flag_img = true;break;
					case 2: $ext = 'jpg';$flag_img = true;break;
					case 3: $ext = 'png';$flag_img = true;break;
				}
				
				if($flag_img){
					$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'].'avatares'.$_SYSTEM['SEPARADOR'];
					$caminho_internet 		=	"/files/avatares/";
					
					$img_original = 'avatar'.$id_usuario.'.'.$ext;
					
					$original = $caminho_fisico . $img_original;
					$internet = $caminho_internet . $img_original;
					
					file_put_contents($original, file_get_contents($url));
					chmod($original , 0777);
					resize_image($original, $original, 25, 23,false,false,true);
					
					banco_update
					(
						"avatar='".$internet."'",
						"usuario",
						"WHERE id_usuario='".$id_usuario."'"
					);
				}
			}
			
			$usuarios = banco_select_name
			(
				"*"
				,
				"usuario",
				"WHERE facebook_id='" . $user_profile->identifier . "'"
			);
		}
		
		banco_update
		(
			"data_login=NOW()",
			"usuario",
			"WHERE usuario='".$usuarios[0]['usuario']."'"
		);
		
		$_SESSION[$_SYSTEM['ID']."usuario"] = $usuarios[0];
		
		$_SESSION[$_SYSTEM['ID']."permissao"] = true;
		$_SESSION[$_SYSTEM['ID']."permissao_id"] = $usuarios[0]['id_usuario_perfil'];
		
		
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
		
		if($_SESSION[$_SYSTEM['ID'].'ecommerce-itens']){
			$_SESSION[$_SYSTEM['ID'].'ecommerce-id_usuario'] = $id_usuario;
			require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/cadastrar-pedido.php');
		}
		
		if($_SESSION[$_SYSTEM['ID'].'logar-local']){
			$local = $_SESSION[$_SYSTEM['ID'].'logar-local'];
			$_SESSION[$_SYSTEM['ID'].'logar-local'] = false;
		} else if($_SESSION[$_SYSTEM['ID'].'autenticar-local']){
			$local = $_SESSION[$_SYSTEM['ID'].'autenticar-local'];
			$_SESSION[$_SYSTEM['ID'].'autenticar-local'] = false;
		} else {
			$local = 'admin';
		}
		
		redirecionar($local);
	} catch( Exception $e ){
		switch($e){
			case 0: $mens_erro = 'Erro não especificado'; break;
			case 1: $mens_erro = 'Hybriauth com erro de configuração'; break;
			case 2: $mens_erro = 'Provedor não configurado corretamente'; break;
			case 3: $mens_erro = 'Provedor não conhecido ou desabilitado'; break;
			case 4: $mens_erro = 'Perda de credenciais de aplicação do provedor (Sua ID de aplicação, chave ou senha)'; break;
			case 5: $mens_erro = 'Autenticação falhou'; break;
			case 6: $mens_erro = 'Requisição de Perfil de usuário falhou'; break;
			case 7: $mens_erro = 'Usuário não conectado ao provedor'; break;
			case 8: $mens_erro = 'Provedor não suporta essa requisição'; break;
		
		}
		
		alerta($mens_erro);
		
		redirecionar('autenticar');
	}
}

function autenticar_cadastro_user_validar(){
	global $_CONTEUDO_ID_AUX;
	global $_VARS;
	global $_SYSTEM;
	global $_PROJETO;
	global $_ECOMMERCE;
	
	if($_PROJETO['autenticar']){
		if($_PROJETO['autenticar']['alerta_invalido']){
			$alerta_invalido = $_PROJETO['autenticar']['alerta_invalido'];
		}
	}
	if($_PROJETO['autenticar']){
		if($_PROJETO['autenticar']['alerta_sucesso2']){
			$alerta_sucesso2 = $_PROJETO['autenticar']['alerta_sucesso2'];
		}
	}
	
	if(!$alerta_invalido){
		if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- alerta_invalido < -->','<!-- alerta_invalido > -->');
		
		$alerta_invalido = $pagina;
	}
	
	$alerta_invalido = modelo_var_troca_tudo($alerta_invalido,"#email#",$_SYSTEM['CONTATO_EMAIL']);
	$alerta_invalido = modelo_var_troca_tudo($alerta_invalido,"#nome#",$_SYSTEM['CONTATO_NOME']);
	
	$cod = $_REQUEST['cod'];
	$key = $_REQUEST['key'];
	
	if($cod && $key){
		$cod = substr($cod,8);
		$cod = zero_a_esquerda_retirar($cod);
		
		global $_CONEXAO_BANCO;
	
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		$usuarios = banco_select_name
		(
			banco_campos_virgulas(Array(
				'cadastro_key',
			))
			,
			"usuario",
			"WHERE id_usuario='".$cod."'"
		);
		
		if($key == $usuarios[0]['cadastro_key']){
			if($_SYSTEM['SITE']){
				if($_SYSTEM['SITE']['autenticar_validacao_bloqueio']){
					$validacao_bloqueio = true;
				}
			}
			
			if(!$validacao_bloqueio){
				if(!$alerta_sucesso2){
					if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
					$pagina = modelo_tag_val($modelo,'<!-- alerta_sucesso2 < -->','<!-- alerta_sucesso2 > -->');
					
					$alerta_sucesso2 = $pagina;
				}
				
				banco_update
				(
					"status='A',"
					."cadastro_key=NULL",
					"usuario",
					"WHERE id_usuario='".$cod."'"
				);
				
				$_SESSION[$_SYSTEM['ID'].'logar-local'] = $_REQUEST['local'];
				
				alerta($alerta_sucesso2);
			} else {
				if(!$alerta_sucesso3){
					if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
					$pagina = modelo_tag_val($modelo,'<!-- alerta_sucesso3 < -->','<!-- alerta_sucesso3 > -->');
					
					$alerta_sucesso3 = $pagina;
				}
				
				banco_update
				(
					"status='B',"
					."cadastro_key=NULL",
					"usuario",
					"WHERE id_usuario='".$cod."'"
				);
				
				$_SESSION[$_SYSTEM['ID'].'logar-local'] = $_REQUEST['local'];
				
				alerta($alerta_sucesso3);
			}
		} else {
			alerta($alerta_invalido);
		}
	} else {
		alerta($alerta_invalido);
	}
	
	redirecionar('autenticar');
}

function autenticar_cadastro_user_banco(){
	global $_SYSTEM;
	global $_HTML;
	global $_VARS;
	global $_CONTEUDO_ID_AUX;
	global $_REMOTE_ADDR;
	global $_PROJETO;
	global $_ECOMMERCE;
	
	$resultado = banco_select
	(
		"id_usuario",
		'usuario',
		"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
	);
	
	if($resultado){
		alerta('<p>E-mail já está em uso!<p></p>Escolha outro!</p>');
		redirecionar('autenticar');
	}
	
	if(recaptcha_verify()){
		banco_conectar();
		
		if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
		
		$key = crypt(rand().$_REQUEST["email"]);
		$key = preg_replace('/[\$\.\/]/i', '', $key);
		
		if($_SYSTEM['SITE']){
			$local = $_SYSTEM['SITE']['permissao_local_inicial'];
			$id_usuario_perfil = $_SYSTEM['SITE']['permissao_usuario'];
		}
		
		$campo_nome = "cadastro_key"; $post_nome = $campo_nome; 		$campos[] = Array($campo_nome,($_PROJETO['autenticar_nao_validar']?'':$key));
		$campo_nome = "id_usuario_perfil"; 								$campos[] = Array($campo_nome,($id_usuario_perfil?$id_usuario_perfil:'2'));
		$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,($_PROJETO['autenticar_nao_validar']?'A':$_SYSTEM['CADASTRO_STATUS']));
		
		$campo_nome = "usuario"; $post_nome = "email"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "senha"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,crypt($_REQUEST[$post_nome]));
		$campo_nome = "nome"; $post_nome = $campo_nome;		 			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "email"; $post_nome = "email"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
		
		banco_insert_name
		(
			$campos,
			"usuario"
		);
		
		$id_usuario = banco_last_id();
		
		$codigo = date('dmY').zero_a_esquerda($id_usuario,6);
		
		$url = html(Array(
			'tag' => 'a',
			'val' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=cadastro-validar&cod='.$codigo.'&key='.$key.($local?'&local='.$local:''),
			'attr' => Array(
				'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=cadastro-validar&cod='.$codigo.'&key='.$key.($local?'&local='.$local:''),
			)
		));
		
		$url2 = html(Array(
			'tag' => 'a',
			'val' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'autenticar',
			'attr' => Array(
				'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'autenticar',
			)
		));
		
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['cadastro_assunto']){
				$email_assunto = $_VARS['autenticar']['cadastro_assunto'];
			}
		}
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['cadastro_mensagem']){
				$email_mensagem = $_VARS['autenticar']['cadastro_mensagem'];
			}
		}
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['cadastro_alerta_sucesso']){
				$alerta_sucesso = $_VARS['autenticar']['cadastro_alerta_sucesso'];
			}
		}
		
		if(!$email_assunto){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- email_assunto < -->','<!-- email_assunto > -->');
			
			$email_assunto = $pagina;
		}
		if(!$email_mensagem){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- email_mensagem < -->','<!-- email_mensagem > -->');
			
			$email_mensagem = $pagina;
		}
		if(!$alerta_sucesso){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- alerta_sucesso < -->','<!-- alerta_sucesso > -->');
			
			$alerta_sucesso = $pagina;
		}
		
		$email_assunto = modelo_var_troca_tudo($email_assunto,"#cod#",$codigo);
		$email_assunto = modelo_var_troca_tudo($email_assunto,"#titulo#",$_HTML['TITULO']);
		
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#titulo#",$_HTML['TITULO']);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#codigo#",$codigo);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#usuario#",$_REQUEST['email']);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#senha#",$_REQUEST['senha']);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",strip_tags($_REQUEST["nome"]));
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url#",$url);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url2#",$url2);
		
		$alerta_sucesso = modelo_var_troca_tudo($alerta_sucesso,"#assunto#",$email_assunto);
		$alerta_sucesso = modelo_var_troca_tudo($alerta_sucesso,"#email#",$_SYSTEM['CONTATO_EMAIL']);
		$alerta_sucesso = modelo_var_troca_tudo($alerta_sucesso,"#nome#",$_SYSTEM['CONTATO_NOME']);
		
		$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
		
		$parametros['from_name'] = $_HTML['TITULO'];
		$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
		
		$parametros['email_name'] = strip_tags($_REQUEST["nome"]);
		$parametros['email'] = strip_tags($_REQUEST["email"]);
		
		$parametros['subject'] = $email_assunto;
		$parametros['mensagem'] = $email_mensagem;
		$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
		
		if($parametros['enviar_mail'])enviar_mail($parametros);
		
		if(!$_PROJETO['autenticar_nao_validar'])alerta($alerta_sucesso);
		
		if($_SESSION[$_SYSTEM['ID'].'ecommerce-itens']){
			$_SESSION[$_SYSTEM['ID'].'ecommerce-id_usuario'] = $id_usuario;
			require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/cadastrar-pedido.php');
		}
		
		if($_PROJETO['autenticar_nao_validar'])autenticar_cadastro_user_logar($id_usuario);
	} else {
		alerta('<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código recaptcha especificado é inválido!</p>');
	}
	
	redirecionar('autenticar');
}

function autenticar_cadastro_user_logar($id_usuario){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ECOMMERCE;
	
	$usuarios = banco_select_name
	(
		"*"
		,
		"usuario",
		"WHERE id_usuario='" . $id_usuario . "'"
	);
	
	if(!$_SESSION[$_SYSTEM['ID'].'logar-local']){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'meus-pedidos';
	}
	
	banco_update
	(
		"data_login=NOW()",
		"usuario",
		"WHERE usuario='".$usuarios[0]['usuario']."'"
	);
	
	$_SESSION[$_SYSTEM['ID']."usuario"] = $usuarios[0];
	
	$_SESSION[$_SYSTEM['ID']."permissao"] = true;
	$_SESSION[$_SYSTEM['ID']."permissao_id"] = $usuarios[0]['id_usuario_perfil'];
	
	
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
	
	if($_SESSION[$_SYSTEM['ID'].'logar-local']){
		$local = $_SESSION[$_SYSTEM['ID'].'logar-local'];
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = false;
	} else if($_SESSION[$_SYSTEM['ID'].'autenticar-local']){
		$local = $_SESSION[$_SYSTEM['ID'].'autenticar-local'];
		$_SESSION[$_SYSTEM['ID'].'autenticar-local'] = false;
	} else {
		$local = 'admin';
	}
	
	redirecionar($local);
}

function autenticar_form_autenticar(){
	global $_SYSTEM;
	global $_ECOMMERCE;
	
	if($_REQUEST['ecommerce-itens']){
		$_SESSION[$_SYSTEM['ID'].'ecommerce-itens'] = $_REQUEST['ecommerce-itens'];
		$_SESSION[$_SYSTEM['ID'].'ecommerce-cupom'] = $_REQUEST['ecommerce-cupom'];
		$_SESSION[$_SYSTEM['ID'].'ecommerce-cep'] = $_REQUEST['ecommerce-cep'];
		$_SESSION[$_SYSTEM['ID'].'ecommerce-frete_codigo'] = $_REQUEST['ecommerce-frete_codigo'];
		$_SESSION[$_SYSTEM['ID'].'autenticar-local'] = 'pagamento';
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'pagamento';
		$_SESSION[$_SYSTEM['ID'].'autenticar-id_usuario_perfil'] = ($_ECOMMERCE['permissao_usuario']?$_ECOMMERCE['permissao_usuario']:'3');
	} else {
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'meus-pedidos';
	}

	redirecionar('autenticar');
}

function autenticar(){
	global $_PROJETO;
	global $_SYSTEM;
	global $_HTML;
	global $_HTML_DADOS;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_ECOMMERCE;
	
	$_SESSION[$_SYSTEM['ID']."usuario"] = false;
	$_SESSION[$_SYSTEM['ID']."permissao"] = false;
	$_SESSION[$_SYSTEM['ID']."permissao_id"] = false;
	$_SESSION[$_SYSTEM['ID']."admin"] = false;
	$_SESSION[$_SYSTEM['ID']."modulos"] = false;
	$_SESSION[$_SYSTEM['ID']."modulos_operacao"] = false;
	
	if($_PROJETO['autenticar']){
		if($_PROJETO['autenticar']['autenticar_layout']){
			$layout = $_PROJETO['autenticar']['autenticar_layout'];
		}
	}
	
	if($_PROJETO['autenticar']){
		if($_PROJETO['autenticar']['login_facebook']){
			$login_facebook = '<a href="/'.$_SYSTEM['ROOT'].'login-facebook" class="_ajax_nao" id="_autenticar-login-facebook"></a>';
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- autenticar < -->','<!-- autenticar > -->');
		
		$layout = $pagina;
	}
	
	$layout = modelo_var_troca($layout,"#login-facebook#",$login_facebook);
	
	if(!$_SESSION[$_SYSTEM['ID'].'ecommerce-itens']){
		$cel_nome = 'ecommerce'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Autenticar Usuário.';
	
	$_HTML_DADOS['description'] = 'Página para autenticar e/ou cadastrar usuários do sistema.';
	$_HTML_DADOS['keywords'] = 'cadastrar,autenticar,login,senha,entrar,entrar conta';
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	return $layout;
}

// Funções B2Make

function signup(){
	global $_PROJETO;
	global $_SYSTEM;
	global $_HTML;
	global $_HTML_DADOS;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_ECOMMERCE;
	global $_B2MAKE_SERVER_ALIAS;
	global $_CAMINHO;
	
	$_SESSION[$_SYSTEM['ID']."usuario"] = false;
	$_SESSION[$_SYSTEM['ID']."permissao"] = false;
	$_SESSION[$_SYSTEM['ID']."permissao_id"] = false;
	$_SESSION[$_SYSTEM['ID']."admin"] = false;
	$_SESSION[$_SYSTEM['ID']."modulos"] = false;
	$_SESSION[$_SYSTEM['ID']."modulos_operacao"] = false;
	
	// Verificação de Segurança B2Make
	
	$ip = $_SERVER["REMOTE_ADDR"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_cadastro_ips',
		))
		,
		"cadastro_ips",
		"WHERE UNIX_TIMESTAMP(data) < ".(time()-$_PROJETO['CADASTRO_IPS_PERIODO_SEGUNDOS']).""
	);
	
	if($resultado){
		banco_delete
		(
			"cadastro_ips",
			"WHERE UNIX_TIMESTAMP(data) < ".(time()-$_PROJETO['CADASTRO_IPS_PERIODO_SEGUNDOS']).""
		);
	}
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'tentativas',
		))
		,
		"cadastro_ips",
		"WHERE ip='".$ip."'"
	);
	
	if($resultado){
		if($_PROJETO['CADASTRO_IPS_TENTATIVAS_MAX'] <= (int)$resultado[0]['tentativas']){
			banco_update
			(
				"tentativas=tentativas+1",
				"cadastro_ips",
				"WHERE ip='".$ip."'"
			);
			
			$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = false;
		} else {
			$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = true;
		}
	} else {
		$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = true;
	}
	
	$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"] = false;
	
	// =============
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- signup < -->','<!-- signup > -->');
		
		$layout = $pagina;
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Cadastro.';
	
	$_HTML_DADOS['description'] = 'Página para cadastrar usuários no B2make.';
	$_HTML_DADOS['keywords'] = 'cadastrar,cadastro,cadastrar conta, planos, cadastro conta';
	
	if(!$_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"]){
		$_VARIAVEIS_JS['recaptcha_enable'] = 'sim';
		$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	}
	
	$_VARIAVEIS_JS['server_alias'] = $_B2MAKE_SERVER_ALIAS;
	
	$planos = projeto_planos(Array(
		'widget' => true
	));
	
	$templates = projeto_modelos(Array(
		'widget' => true,
		'modulo' => true
	));
	
	$layout = modelo_var_troca($layout,"#planos#",$planos);
	$layout = modelo_var_troca($layout,"#templates#",$templates);
	
	return $layout;
}

function signup_bd(){
	global $_SYSTEM;
	global $_HTML;
	global $_VARS;
	global $_CONTEUDO_ID_AUX;
	global $_REMOTE_ADDR;
	global $_PROJETO;
	global $_ECOMMERCE;
	
	
	if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
		alerta('<p>Este E-mail não é válido! Escolha outro!</p>');
		redirecionar('signup');
	}
	
	$resultado = banco_select
	(
		"id_usuario",
		'usuario',
		"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
	);
	
	if($resultado){
		alerta('<p>E-mail já está em uso! Escolha outro!</p>');
		redirecionar('signup');
	}
	
	if($_SESSION[$_SYSTEM['ID']."cadastro_sem_recaptcha"]){
		$validado = true;
	} else {
		$validado = recaptcha_verify();
	}
	
	if($validado){
		ignore_user_abort(1); // run script in background 
		set_time_limit(20); // run script forever 
		banco_conectar();
		
		$ip = $_SERVER["REMOTE_ADDR"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'tentativas',
			))
			,
			"cadastro_ips",
			"WHERE ip='".$ip."'"
		);
		
		if($resultado){
			banco_update
			(
				"tentativas=tentativas+1",
				"cadastro_ips",
				"WHERE ip='".$ip."'"
			);
		} else {
			$campos = null;
			
			$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "tentativas"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "ip"; $campo_valor = $ip; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"cadastro_ips"
			);
			$campos = null;
		}
		
		if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
		
		$key = crypt(rand().$_REQUEST["email"]);
		$key = preg_replace('/[\$\.\/]/i', '', $key);
		
		if($_SYSTEM['SITE']){
			$local = $_SYSTEM['SITE']['permissao_local_inicial'];
			$id_usuario_perfil = $_SYSTEM['SITE']['permissao_usuario'];
		}
		
		$campo_nome = "cadastro_key"; $post_nome = $campo_nome; 		$campos[] = Array($campo_nome,($_PROJETO['autenticar_nao_validar']?'':$key));
		$campo_nome = "id_usuario_perfil"; 								$campos[] = Array($campo_nome,($id_usuario_perfil?$id_usuario_perfil:'2'));
		$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,($_PROJETO['autenticar_nao_validar']?'A':$_SYSTEM['CADASTRO_STATUS']));
		
		$campo_nome = "usuario"; $post_nome = "email"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "senha"; $post_nome = $campo_nome; 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,crypt($_REQUEST[$post_nome]));
		$campo_nome = "nome"; $post_nome = "email";		 				if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "email"; $post_nome = "email"; 					if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,strip_tags($_REQUEST[$post_nome]));
		$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
		
		banco_insert_name
		(
			$campos,
			"usuario"
		);
		
		$id_usuario = banco_last_id();
		
		$codigo = date('dmY').zero_a_esquerda($id_usuario,6);
		
		$url = html(Array(
			'tag' => 'a',
			'val' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=cadastro-validar&cod='.$codigo.'&key='.$key.($local?'&local='.$local:''),
			'attr' => Array(
				'href' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=cadastro-validar&cod='.$codigo.'&key='.$key.($local?'&local='.$local:''),
			)
		));
		
		$url2 = html(Array(
			'tag' => 'a',
			'val' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'signin',
			'attr' => Array(
				'href' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'signin',
			)
		));
		
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['cadastro_assunto']){
				$email_assunto = $_VARS['autenticar']['cadastro_assunto'];
			}
		}
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['cadastro_mensagem']){
				$email_mensagem = $_VARS['autenticar']['cadastro_mensagem'];
			}
		}
		if($_VARS['autenticar']){
			if($_VARS['autenticar']['cadastro_alerta_sucesso']){
				$alerta_sucesso = $_VARS['autenticar']['cadastro_alerta_sucesso'];
			}
		}
		
		if(!$email_assunto){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- email_assunto < -->','<!-- email_assunto > -->');
			
			$email_assunto = $pagina;
		}
		if(!$email_mensagem){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- email_mensagem < -->','<!-- email_mensagem > -->');
			
			$email_mensagem = $pagina;
		}
		if(!$alerta_sucesso){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- alerta_sucesso < -->','<!-- alerta_sucesso > -->');
			
			$alerta_sucesso = $pagina;
		}
		
		$email_assunto = modelo_var_troca_tudo($email_assunto,"#cod#",$codigo);
		$email_assunto = modelo_var_troca_tudo($email_assunto,"#titulo#",$_HTML['TITULO']);
		
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#titulo#",$_HTML['TITULO']);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#codigo#",$codigo);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#usuario#",$_REQUEST['email']);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#senha#",$_REQUEST['senha']);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",'Usuário');
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url#",$url);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url2#",$url2);
		
		$alerta_sucesso = modelo_var_troca_tudo($alerta_sucesso,"#assunto#",$email_assunto);
		$alerta_sucesso = modelo_var_troca_tudo($alerta_sucesso,"#email#",$_SYSTEM['CONTATO_EMAIL']);
		$alerta_sucesso = modelo_var_troca_tudo($alerta_sucesso,"#nome#",$_SYSTEM['CONTATO_NOME']);
		
		$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
		
		$parametros['from_name'] = $_HTML['TITULO'];
		$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
		
		$parametros['email_name'] = strip_tags($_REQUEST["nome"]);
		$parametros['email'] = strip_tags($_REQUEST["email"]);
		
		$parametros['subject'] = $email_assunto;
		$parametros['mensagem'] = $email_mensagem;
		$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
		
		if($parametros['enviar_mail'])enviar_mail($parametros);
		
		if(!$_PROJETO['autenticar_nao_validar'])alerta($alerta_sucesso);
		
		if($_SESSION[$_SYSTEM['ID'].'ecommerce-itens']){
			$_SESSION[$_SYSTEM['ID'].'ecommerce-id_usuario'] = $id_usuario;
			require_once($_SYSTEM['PATH'].$_SYSTEM['INCLUDE_PATH'].'ecommerce/cadastrar-pedido.php');
		}
		
		if($_PROJETO['autenticar_nao_validar'])signup_cadastro_user_logar($id_usuario);
	} else {
		alerta('<p>Código de validação <b style="color:red;">inválido</b>!<br>O código recaptcha especificado é inválido!</p>');
	}
	
	redirecionar('signup');
}

function signup_cadastro_user_logar($id_usuario){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ECOMMERCE;
	global $_REMOTE_ADDR;
	global $_DEBUG;
	
	$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
	
	$usuarios = banco_select_name
	(
		"*"
		,
		"usuario",
		"WHERE id_usuario='" . $id_usuario . "'"
	);
	
	$senha_sessao = md5(crypt($usuarios[0]['senha']).mt_rand());
	$usuarios[0]['senha_sessao'] = $senha_sessao;
	
	banco_update
	(
		"senha_sessao='".$senha_sessao."',".
		"data_login=NOW()",
		"usuario",
		"WHERE usuario='".$usuarios[0]['usuario']."' AND status!='D'"
	);
	banco_delete
	(
		"bad_list",
		"WHERE ip='".$_REMOTE_ADDR."'"
	);
	
	$usuarios[0]['pub_id'] = md5(uniqid(rand(), true));
	
	banco_update
	(
		"pub_id='".$usuarios[0]['pub_id']."'",
		"usuario",
		"WHERE usuario='".$usuarios[0]['usuario']."' AND status!='D'"
	);
	
	$_SESSION[$_SYSTEM['ID']."usuario"] = $usuarios[0];
	$_SESSION[$_SYSTEM['ID']."usuario_senha"] = $_REQUEST['senha'];
	
	$_SESSION[$_SYSTEM['ID']."permissao"] = true;
	$_SESSION[$_SYSTEM['ID']."permissao_id"] = $usuarios[0]['id_usuario_perfil'];
	
	
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
	
	if($usuarios_perfils_modulos_operacao)
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
	
	signup_definir_host();
	
	signature_account_diskstats();
	
	if($_REQUEST['plano'] != '1'){
		$local = 'payment';
	} else {
		$local = 'signup-success';
		$_SESSION[$_SYSTEM['ID']."b2make-analytics-cadastro-sucesso"] = true;
	}
	
	global $_REDIRECT_PAGE;
	$_REDIRECT_PAGE = true;
	
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
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND atual IS TRUE"
	);
	
	if($resultado){
		$_SESSION[$_SYSTEM['ID']."b2make-host"] = true;
		$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
		
		$id_host = $resultado[0]['id_host'];
		$url = $resultado[0]['url'];
		$user_host = $resultado[0]['user_host'];
		$url_files = $resultado[0]['url_files'];
		$ftp_site_host = $resultado[0]['ftp_site_host'];
		$ftp_site_user = $resultado[0]['ftp_site_user'];
		$ftp_site_pass = hashPassword($senha,$resultado[0]['ftp_site_pass']);
		$ftp_site_path = $resultado[0]['ftp_site_path'];
		$ftp_files_host = $resultado[0]['ftp_files_host'];
		$ftp_files_user = $resultado[0]['ftp_files_user'];
		$ftp_files_pass = hashPassword($senha,$resultado[0]['ftp_files_pass']);
		$ftp_files_path = $resultado[0]['ftp_files_path'];
		$https = ($resultado[0]['https'] ? true : false);
		
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
	}
	
	if(!$_DEBUG)redirecionar($local);
}

function signup_definir_host(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_FTP_SITE_HOST;
	global $_B2MAKE_FTP_FILES_HOST;
	global $_B2MAKE_FTP_SITE_PATH;
	global $_B2MAKE_FTP_FILES_PATH;
	global $_B2MAKE_FTP_SITE_ROOT;
	global $_B2MAKE_FTP_FILES_ROOT;
	global $_B2MAKE_FTP_SITE_QUOTA;
	global $_B2MAKE_FTP_FILES_QUOTA;
	global $_B2MAKE_FTP_SITE_LOCALHOST;
	global $_B2MAKE_SERVER_ALIAS;
	global $_B2MAKE_PLAN_FREE;
	global $_DEBUG;
	global $_CPANEL;
	
	if($_DEBUG)echo print_r($_REQUEST,true);
	
	$num_total_rows = banco_total_rows
	(
		"server_users",
		"WHERE server='".$_B2MAKE_SERVER_ALIAS."'"
	);
	
	$server = preg_replace('/erver/i', '', $_B2MAKE_SERVER_ALIAS) . 'b';
	
	$user_cpanel = $server.($num_total_rows+50);
	$user_host = $user_cpanel;
	
	$ftp_site_host = $user_host.'.'.$_B2MAKE_FTP_SITE_HOST;
	$ftp_files_host = $user_host.'.'.$_B2MAKE_FTP_FILES_HOST;
	$ftp_site_path = $_B2MAKE_FTP_SITE_PATH;
	$ftp_files_path = $_B2MAKE_FTP_FILES_PATH;
	$ftp_site_root = $_B2MAKE_FTP_SITE_ROOT;
	$ftp_files_root = $_B2MAKE_FTP_FILES_ROOT;
	$ftp_localhost = $_B2MAKE_FTP_SITE_LOCALHOST;
	$ftp_site_quota = $_B2MAKE_FTP_SITE_QUOTA;
	$ftp_files_quota = $_B2MAKE_FTP_FILES_QUOTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
	$id_usuario = $usuario['id_usuario'];
	
	$url = 'https://'.$ftp_site_host.'/';
	$url_files = 'https://'.$ftp_files_host.'/files/';
	$url_mobile = 'm.'.$ftp_site_host;
	
	$ftp_site_pass = ($_SERVER['SERVER_NAME'] == "localhost" ? md5(rand()) : getToken() );
	$ftp_files_pass = ($_SERVER['SERVER_NAME'] == "localhost" ? md5(rand()) : getToken() );
	
	$campos = null;
	
	if($_REQUEST['modelo']){
		$plano = $_REQUEST['plano'];
		$modelo = $_REQUEST['modelo'];
	} else {
		$plano = $_SESSION[$_SYSTEM['ID']."signup_plano"];
		$modelo = $_SESSION[$_SYSTEM['ID']."signup_modelo"];
		
		$_SESSION[$_SYSTEM['ID']."signup_dominio_proprio"] = false;
		$_SESSION[$_SYSTEM['ID']."signup_modelo"] = false;
	}
	
	if($layout){ if($layout == 1) $layout = true; else $layout = false;}
	
	if($plano){$campo_nome = "plano"; $campo_valor = $plano; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
	$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "user_host"; $campo_valor = $user_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "url"; $campo_valor = $url; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "url_mobile"; $campo_valor = $url_mobile; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "url_files"; $campo_valor = $url_files; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "ftp_site_host"; $campo_valor = $ftp_site_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "ftp_files_host"; $campo_valor = $ftp_files_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "ftp_site_pass"; $campo_valor = $ftp_site_pass; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "ftp_files_pass"; $campo_valor = $ftp_files_pass; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "server"; $campo_valor = $_B2MAKE_SERVER_ALIAS; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "https"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "mobile"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "atual"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "site_cache"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	
	banco_insert_name
	(
		$campos,
		"host"
	);
	
	$id_host = banco_last_id();
	$id_site_templates = $modelo;
	
	$campos = null;
	
	$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "server"; $campo_valor = $_B2MAKE_SERVER_ALIAS; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "user"; $campo_valor = $user_cpanel; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	
	banco_insert_name
	(
		$campos,
		"server_users"
	);
	
	banco_update
	(
		"user_cpanel='".$user_cpanel."'",
		"host",
		"WHERE id_host='".$id_host."'"
	);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'html',
		))
		,
		"site_templates",
		"WHERE id_site_templates='".$id_site_templates."'"
	);

	$campos = null;
	
	$campo_nome = "id_site_templates"; $campo_valor = $id_site_templates; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "html"; $campo_valor = $resultado[0]['html']; 		if($campo_valor)			$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "atual"; $campo_valor = 'TRUE'; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
	$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
	
	banco_insert_name
	(
		$campos,
		"site"
	);
	
	$ftp_site_user = 'b2make_site'.$id_host;
	$ftp_files_user = 'b2make_files'.$id_host;
	$senha_hash_site = hashPassword($senha,$ftp_site_pass);
	$senha_hash_files = hashPassword($senha,$ftp_files_pass);
	
	$_CPANEL['ACCT']['domain'] = $ftp_domain_account = $ftp_site_host;
	
	$_CPANEL['FTP_LOCAL'] = $_B2MAKE_SERVER_ALIAS;
	$_CPANEL['ACCT']['user'] = $user_cpanel;
	$_CPANEL['ACCT']['host'] = $user_host;
	$_CPANEL['ACCT']['pass'] = $senha_hash_site;
	$_CPANEL['ACCT']['plan'] = $_B2MAKE_PLAN_FREE;
	$_CPANEL['ACCT']['email'] = $usuario['email'];
	
	banco_update
	(
		"ftp_site_user='".$ftp_site_user.'@'.$ftp_domain_account."',".
		"ftp_files_user='".$ftp_files_user.'@'.$ftp_domain_account."'",
		"host",
		"WHERE id_host='".$id_host."'"
	);
	
	if($_SERVER['SERVER_NAME'] != "localhost"){
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-createacct-v2.0.php');

		$_CPANEL['FTP_ADD'] = Array(
			'user' => $ftp_site_user,
			'pass' => $senha_hash_site,
			'homedir' => $ftp_site_root,
			'quota' => '0',
		);
		
		$_CPANEL['CPANEL_USER'] = $user_cpanel;
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-add.php');
		
		$_CPANEL['FTP_ADD'] = Array(
			'user' => $ftp_files_user,
			'pass' => $senha_hash_files,
			'homedir' => $ftp_files_root,
			'quota' => '0',
		);
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-add.php');
		
		$parse = parse_url($url);
		$dominio = $parse['host'];
		
		$_CPANEL['ACCT'] = Array(
			'user' => $user_cpanel,
			'domain_owner' => $dominio,
		);
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-domain-mobile-add.php');
	}
	
	$_SESSION[$_SYSTEM['ID']."b2make-host"] = true;
	
	$_SESSION[$_SYSTEM['ID']."b2make-site"] =  Array(
		'url' => $url,
		'url-files' => $url_files,
		'ftp-site-host' => $ftp_site_host,
		'ftp-site-user' => $ftp_site_user.'@'.$ftp_domain_account,
		'ftp-site-pass' => $senha_hash_site,
		'ftp-files-host' => $ftp_files_host,
		'ftp-files-user' => $ftp_files_user.'@'.$ftp_domain_account,
		'ftp-files-pass' => $senha_hash_files,
	);
	
	publisher_block_time(Array(
		'block_time' => 600
	));
}

function signup_facebook(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ECOMMERCE;
	global $_REMOTE_ADDR;
	global $_VARS;
	global $_HTML;
	
	try{
		ignore_user_abort(1); // run script in background 
		set_time_limit(20); // run script forever 
		
		$hybridauth_path = $_SYSTEM['PATH'] . 'includes'.$_SYSTEM['SEPARADOR'].'php'.$_SYSTEM['SEPARADOR'].'hybridauth'.$_SYSTEM['SEPARADOR'];
		
		$config_file_path = $hybridauth_path.'config.php';
		
		require_once( $hybridauth_path."Hybrid".$_SYSTEM['SEPARADOR']."Auth.php" );
		require_once( $hybridauth_path."Hybrid".$_SYSTEM['SEPARADOR']."thirdparty".$_SYSTEM['SEPARADOR']."Facebook".$_SYSTEM['SEPARADOR']."autoload.php" );
		
		$hybridauth = new Hybrid_Auth( $config_file_path );
		
		$adapter = $hybridauth->authenticate( "Facebook" );
		
		$user_profile = $adapter->getUserProfile();
		
		global $_CONEXAO_BANCO;
	
		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$usuarios = banco_select_name
		(
			"*"
			,
			"usuario",
			"WHERE facebook_id='" . $user_profile->identifier . "' AND status!='D'"
		);
		
		if($usuarios[0]['status'] == 'B'){
			alerta('<p>Sua conta está bloqueada!</p><p>Favor entrar em contato com o suporte para poder saber como proceder!</p>');
			redirecionar('signin');
		}
		
		if(!$usuarios){
			$novo_cadastro = true;
			
			$resultado = banco_select
			(
				"id_usuario",
				'usuario',
				"WHERE email='" . $user_profile->email . "' AND status!='D'"
			);
			
			if($resultado){
				$campo_tabela = "usuario";
				$campo_tabela_extra = "WHERE id_usuario='".$resultado[0]['id_usuario']."'";
				
				$campo_nome = "facebook_id"; $campo_valor = $user_profile->identifier; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "senha"; $campo_valor = crypt(md5($user_profile->identifier . $user_profile->email)); $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "nome"; $campo_valor = $user_profile->displayName; if($campo_valor)$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "endereco"; $campo_valor = $user_profile->address; if($campo_valor)$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "cep"; $campo_valor = $user_profile->zip; if($campo_valor)$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				$campo_nome = "telefone"; $campo_valor = $user_profile->phone; if($campo_valor)$editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
				
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
				
				$id_usuario = $resultado[0]['id_usuario'];
			} else {
				$id_usuario_perfil = ($_SYSTEM['SITE']['permissao_usuario'] ? $_SYSTEM['SITE']['permissao_usuario'] : $_ECOMMERCE['permissao_usuario']);
				
				$campo_nome = "id_usuario_perfil"; 								$campos[] = Array($campo_nome,($id_usuario_perfil?$id_usuario_perfil:'2'));
				$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'A');
				$campo_nome = "usuario"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,$user_profile->email);
				$campo_nome = "facebook_id"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,$user_profile->identifier);
				$campo_nome = "senha"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,crypt(md5($user_profile->identifier . $user_profile->email)));
				$campo_nome = "email"; $post_nome = $campo_nome; 				if($user_profile->email)$campos[] = Array($campo_nome,$user_profile->email);
				$campo_nome = "nome"; $post_nome = $campo_nome; 				if($user_profile->displayName)$campos[] = Array($campo_nome,$user_profile->displayName);
				$campo_nome = "endereco"; $post_nome = $campo_nome; 			if($user_profile->address)$campos[] = Array($campo_nome,$user_profile->address);
				$campo_nome = "cep"; $post_nome = $campo_nome; 					if($user_profile->zip)$campos[] = Array($campo_nome,$user_profile->zip);
				$campo_nome = "telefone"; $post_nome = $campo_nome; 			if($user_profile->phone)$campos[] = Array($campo_nome,$user_profile->phone);
				$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
				
				banco_insert_name
				(
					$campos,
					"usuario"
				);
				
				$id_usuario = banco_last_id();
			}
			
			$url = $user_profile->photoURL;
			$status_code = getstatus($url);
			
			if($status_code == 200 || $status_code == 302 || $status_code == 301) {
				list($width, $height, $type, $attr) = getimagesize($url);
				
				switch($type){
					case 1: $ext = 'gif';$flag_img = true;break;
					case 2: $ext = 'jpg';$flag_img = true;break;
					case 3: $ext = 'png';$flag_img = true;break;
				}
				
				if($flag_img){
					$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'].'avatares'.$_SYSTEM['SEPARADOR'];
					$caminho_internet 		=	"/files/avatares/";
					
					$img_original = 'avatar'.$id_usuario.'.'.$ext;
					
					$original = $caminho_fisico . $img_original;
					$internet = $caminho_internet . $img_original;
					
					file_put_contents($original, file_get_contents($url));
					chmod($original , 0777);
					resize_image($original, $original, 150, 150,false,false,true);
					
					banco_update
					(
						"avatar='".$internet."'",
						"usuario",
						"WHERE id_usuario='".$id_usuario."'"
					);
				}
			}
			
			$usuarios = banco_select_name
			(
				"*"
				,
				"usuario",
				"WHERE facebook_id='" . $user_profile->identifier . "' AND status!='D'"
			);
			
			// ================== Enviar email
			
			if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
			
			$codigo = date('dmY').zero_a_esquerda($id_usuario,6);
			
			$url = html(Array(
				'tag' => 'a',
				'val' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=cadastro-validar&cod='.$codigo.'&key='.$key.($local?'&local='.$local:''),
				'attr' => Array(
					'href' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'?opcao=cadastro-validar&cod='.$codigo.'&key='.$key.($local?'&local='.$local:''),
				)
			));
			
			$url2 = html(Array(
				'tag' => 'a',
				'val' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'signin',
				'attr' => Array(
					'href' => 'https://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'signin',
				)
			));
			
			if($_VARS['autenticar']){
				if($_VARS['autenticar']['cadastro_assunto']){
					$email_assunto = $_VARS['autenticar']['cadastro_assunto'];
				}
			}
			if($_VARS['autenticar']){
				if($_VARS['autenticar']['cadastro_mensagem']){
					$email_mensagem = $_VARS['autenticar']['cadastro_mensagem'];
				}
			}
			
			if(!$email_assunto){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- email_assunto < -->','<!-- email_assunto > -->');
				
				$email_assunto = $pagina;
			}
			if(!$email_mensagem){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- email_mensagem < -->','<!-- email_mensagem > -->');
				
				$email_mensagem = $pagina;
			}
			
			$email_assunto = modelo_var_troca_tudo($email_assunto,"#cod#",$codigo);
			$email_assunto = modelo_var_troca_tudo($email_assunto,"#titulo#",$_HTML['TITULO']);
			
			$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#titulo#",$_HTML['TITULO']);
			$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#codigo#",$codigo);
			$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#usuario#",$user_profile->email);
			$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#senha#",'N/A');
			$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",strip_tags(($user_profile->displayName ? $user_profile->displayName : $user_profile->email)));
			$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url#",$url);
			$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url2#",$url2);
			
			$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
			
			$parametros['from_name'] = $_HTML['TITULO'];
			$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
			
			$parametros['email_name'] = strip_tags(($user_profile->displayName ? $user_profile->displayName : $user_profile->email));
			$parametros['email'] = strip_tags($user_profile->email);
			
			$parametros['subject'] = $email_assunto;
			$parametros['mensagem'] = $email_mensagem;
			$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
			
			if($parametros['enviar_mail'])enviar_mail($parametros);
		}
	
		$senha_sessao = md5(crypt($usuarios[0]['senha']).mt_rand());
		$usuarios[0]['senha_sessao'] = $senha_sessao;
		
		banco_update
		(
			"senha_sessao='".$senha_sessao."',".
			"data_login=NOW()",
			"usuario",
			"WHERE usuario='".$usuarios[0]['usuario']."' AND status!='D'"
		);
		banco_delete
		(
			"bad_list",
			"WHERE ip='".$_REMOTE_ADDR."'"
		);
		
		$usuarios[0]['pub_id'] = md5(uniqid(rand(), true));
		
		banco_update
		(
			"pub_id='".$usuarios[0]['pub_id']."'",
			"usuario",
			"WHERE usuario='".$usuarios[0]['usuario']."' AND status!='D'"
		);
		
		$_SESSION[$_SYSTEM['ID']."usuario"] = $usuarios[0];
		$_SESSION[$_SYSTEM['ID']."usuario_senha"] = md5($user_profile->identifier . $user_profile->email);
		
		$_SESSION[$_SYSTEM['ID']."permissao"] = true;
		$_SESSION[$_SYSTEM['ID']."permissao_id"] = $usuarios[0]['id_usuario_perfil'];
		
		
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
		$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
		
		if($novo_cadastro){
			signup_definir_host();
			
			signature_account_diskstats();
			
			if($_SESSION[$_SYSTEM['ID']."signup_plano"] != '1'){
				$local = 'payment';
			} else {
				$local = 'signup-success';
				$_SESSION[$_SYSTEM['ID']."b2make-analytics-cadastro-sucesso"] = true;
			}
			
			$_SESSION[$_SYSTEM['ID']."signup_plano"] = false;
		} else {
			$local = $_SYSTEM['SITE']['permissao_local_inicial'];
		}
		
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
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		if($resultado){
			$_SESSION[$_SYSTEM['ID']."b2make-host"] = true;
			$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
			
			$id_host = $resultado[0]['id_host'];
			$url = $resultado[0]['url'];
			$user_host = $resultado[0]['user_host'];
			$url_files = $resultado[0]['url_files'];
			$ftp_site_host = $resultado[0]['ftp_site_host'];
			$ftp_site_user = $resultado[0]['ftp_site_user'];
			$ftp_site_pass = hashPassword($senha,$resultado[0]['ftp_site_pass']);
			$ftp_site_path = $resultado[0]['ftp_site_path'];
			$ftp_files_host = $resultado[0]['ftp_files_host'];
			$ftp_files_user = $resultado[0]['ftp_files_user'];
			$ftp_files_pass = hashPassword($senha,$resultado[0]['ftp_files_pass']);
			$ftp_files_path = $resultado[0]['ftp_files_path'];
			
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
			);
		}
		
		redirecionar($local);
	} catch( Exception $e ){
		switch($e){
			case 0: $mens_erro = 'Erro não especificado'; break;
			case 1: $mens_erro = 'Hybriauth com erro de configuração'; break;
			case 2: $mens_erro = 'Provedor não configurado corretamente'; break;
			case 3: $mens_erro = 'Provedor não conhecido ou desabilitado'; break;
			case 4: $mens_erro = 'Perda de credenciais de aplicação do provedor (Sua ID de aplicação, chave ou senha)'; break;
			case 5: $mens_erro = 'Autenticação falhou'; break;
			case 6: $mens_erro = 'Requisição de Perfil de usuário falhou'; break;
			case 7: $mens_erro = 'Usuário não conectado ao provedor'; break;
			case 8: $mens_erro = 'Provedor não suporta essa requisição'; break;
		
		}
		
		alerta($mens_erro);
		
		redirecionar('signup');
	}
}

function signin(){
	global $_PROJETO;
	global $_SYSTEM;
	global $_HTML;
	global $_HTML_DADOS;
	global $_VARIAVEIS_JS;
	global $_VARS;
	global $_ECOMMERCE;
	
	if($_SESSION[$_SYSTEM['ID']."usuario"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		/* if($usuario['id_usuario']){
			banco_delete
			(
				"upload_permissao",
				"WHERE usuario='".$usuario['usuario']."'"
			);
		} */
	}
	
	if($_PROJETO['autenticar']){
		if($_PROJETO['autenticar']['autenticar_layout']){
			$layout = $_PROJETO['autenticar']['autenticar_layout'];
		}
	}
	
	if($_PROJETO['autenticar']){
		if($_PROJETO['autenticar']['login_facebook']){
			$login_facebook = '<a href="/'.$_SYSTEM['ROOT'].'login-facebook" class="_ajax_nao" id="_autenticar-login-facebook"></a>';
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- signin < -->','<!-- signin > -->');
		
		$layout = $pagina;
	}
	
	$layout = modelo_var_troca($layout,"#login-facebook#",$login_facebook);
	
	if(!$_SESSION[$_SYSTEM['ID'].'ecommerce-itens']){
		$cel_nome = 'ecommerce'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Login.';
	
	$_HTML_DADOS['description'] = 'Página para entrar com usuários no sistema.';
	$_HTML_DADOS['keywords'] = 'login,senha,entrar,entrar conta';
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	return $layout;
}

function signin_bd(){
	global $_LOGAR_REDIRECT_LOGIN;
	
	$_LOGAR_REDIRECT_LOGIN = 'signin';
	
	return logar();
}

function signin_facebook(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_ECOMMERCE;
	global $_REMOTE_ADDR;
	global $_PROJETO;
	
	try{
		$hybridauth_path = $_SYSTEM['PATH'] . 'includes'.$_SYSTEM['SEPARADOR'].'php'.$_SYSTEM['SEPARADOR'].'hybridauth'.$_SYSTEM['SEPARADOR'];
		
		$config_file_path = $hybridauth_path.'config.php';
		require_once( $hybridauth_path."Hybrid".$_SYSTEM['SEPARADOR']."Auth.php" );
		
		$hybridauth = new Hybrid_Auth( $config_file_path );
		
		$adapter = $hybridauth->authenticate( "Facebook" );
		
		$user_profile = $adapter->getUserProfile();
		
		global $_CONEXAO_BANCO;
	
		if(!$_CONEXAO_BANCO)banco_conectar();
		
		$usuarios = banco_select_name
		(
			"*"
			,
			"usuario",
			"WHERE facebook_id='" . $user_profile->identifier . "' AND status!='D'"
		);
		
		if(!$usuarios){
			alerta('<p>Você ainda não efetuou o cadastro!<br>Favor clicar no Acessar com o Facebook na página do Cadastro e seguir as instruções</p>');
			redirecionar('signup');
		}
		
		if($usuarios[0]['status'] == 'B'){
			alerta('<p>Sua conta está bloqueada!</p><p>Favor entrar em contato com o suporte para poder saber como proceder!</p>');
			redirecionar('signin');
		}
		
		$senha_sessao = md5(crypt($usuarios[0]['senha']).mt_rand());
		$usuarios[0]['senha_sessao'] = $senha_sessao;
		
		banco_update
		(
			"senha_sessao='".$senha_sessao."',".
			"data_login=NOW()",
			"usuario",
			"WHERE usuario='".$usuarios[0]['usuario']."' AND status!='D'"
		);
		banco_delete
		(
			"bad_list",
			"WHERE ip='".$_REMOTE_ADDR."'"
		);
		
		$_SESSION[$_SYSTEM['ID']."usuario"] = $usuarios[0];
		$_SESSION[$_SYSTEM['ID']."usuario_senha"] = md5($user_profile->identifier . $user_profile->email);
		
		$_SESSION[$_SYSTEM['ID']."permissao"] = true;
		$_SESSION[$_SYSTEM['ID']."permissao_id"] = $usuarios[0]['id_usuario_perfil'];
		
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
		
		signature_account_diskstats();
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
							$ftp_files_user = $usuario['ftp_site_user'];
							$ftp_files_pass = hashPassword($senha,$usuario['ftp_site_pass']);
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
					}
				}
			}
		
		redirecionar($local);
	} catch( Exception $e ){
		switch($e){
			case 0: $mens_erro = 'Erro não especificado'; break;
			case 1: $mens_erro = 'Hybriauth com erro de configuração'; break;
			case 2: $mens_erro = 'Provedor não configurado corretamente'; break;
			case 3: $mens_erro = 'Provedor não conhecido ou desabilitado'; break;
			case 4: $mens_erro = 'Perda de credenciais de aplicação do provedor (Sua ID de aplicação, chave ou senha)'; break;
			case 5: $mens_erro = 'Autenticação falhou'; break;
			case 6: $mens_erro = 'Requisição de Perfil de usuário falhou'; break;
			case 7: $mens_erro = 'Usuário não conectado ao provedor'; break;
			case 8: $mens_erro = 'Provedor não suporta essa requisição'; break;
		
		}
		
		alerta($mens_erro);
		
		redirecionar('signin');
	}
}

function b2make_pagseguro_assinatura_api_oficial(){
	global $_SYSTEM;
	
	include_once($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."pagseguro-2.5".$_SYSTEM['SEPARADOR']."source".$_SYSTEM['SEPARADOR']."PagSeguroLibrary".$_SYSTEM['SEPARADOR']."PagSeguroLibrary.php");
	
	$count = 0;
	$maxTries = 10;
	while(true) {
		try {
			$assinatura['referencia'] = 'codigoCliente'; // Código local de referencia
			$assinatura['nome'] = 'Pro'; // Nome do Plano de Assinatura
			$assinatura['detalhes'] = 'Detalhes'; // Detalhes do Plano de Assinatura
			$assinatura['valor'] = 1.00; // Formato 1234.00
			$assinatura['valorTotal'] = 24.00; // Formato 1234.00
			$assinatura['peridiocidade'] = 'MONTHLY'; //  WEEKLY, MONTHLY, BIMONTHLY, TRIMONTHLY, SEMIANNUALLY, YEARLY
			$assinatura['diaMes'] = 5; // Dia do mês a cobrança
			
			$now = new DateTime();
			$initDate = $now;
			$assinatura['dataInicio'] = $initDate->format(DATE_W3C);
			
			$preApprovalFinalDate = clone $initDate;
			$intervalo = new DateInterval( "P2Y" ); // 2 Anos
			$preApprovalFinalDate->add( $intervalo ); 
			$preApprovalFinalDate = $preApprovalFinalDate->format(DATE_W3C);
			
			$assinatura['dataExpiracao'] = $preApprovalFinalDate;
			
			$paymentRequest = new PagSeguroPreApprovalRequest();
		
			$paymentRequest->setPreApprovalCharge('auto');
			$paymentRequest->setPreApprovalName($assinatura['nome']);
			$paymentRequest->setPreApprovalDetails($assinatura['detalhes']);
			$paymentRequest->setPreApprovalAmountPerPayment($assinatura['valor']);
			$paymentRequest->setPreApprovalMaxTotalAmount($assinatura['valorTotal']);
			$paymentRequest->setPreApprovalPeriod($assinatura['peridiocidade']);
			$paymentRequest->setPreApprovalDayOfMonth($assinatura['diaMes']);
			$paymentRequest->setPreApprovalInitialDate($assinatura['dataInicio']);
			$paymentRequest->setPreApprovalFinalDate($assinatura['dataExpiracao']);
			$paymentRequest->setReference($assinatura['referencia']);
			
			$paymentRequest->setCurrency("BRL");
			
			$credentials = PagSeguroConfig::getAccountCredentials();
			
			$preApproval = $paymentRequest->doPreApproval($credentials);
			
			$url = $preApproval['checkoutUrl'];
			$code = $preApproval['code'];
			
			/* log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>PagSeguro:</b> usuário redirecionado para o pagseguro',
			)); */
			
			//header("Location: ".$url);
			
			echo print_r($preApproval,true);
			
			break;
		} catch (Exception $e) {
			$count++;
			if($count >= $maxTries){
				alerta('<p>Houve um problema com o PagSeguro.</p><p>Por favor, tente novamente pagar com essa opção.</p>');
				redirecionar('pagamento');
				break;
			}
		}
		usleep(400);
	}
}

function b2make_pagseguro_assinatura(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_ALERTA;
	global $_B2MAKE_URL;
	
	$conteudo_perfil = true;
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $id){
		if($id == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
			$conteudo_perfil = false;
			break;
		}
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'payment';
		redirecionar('signin');
	} else {
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'plano',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		if(!$resultado){
			$_ALERTA = '<p>Não existe site definido para esta conta de usuário</p>';
			redirecionar('/');
		}
		
		if(!$resultado[0]['plano']){
			$_ALERTA = '<p>Não há plano definido para esse usuário. Favor fazer upgrade do seu plano.</p>';
			redirecionar('account-upgrade');
		}
		
		if($resultado[0]['plano'] == '1'){
			$_ALERTA = '<p>Este plano é gratuito e portanto não é possível fazer uma assinatura. Favor fazer upgrade do seu plano.</p>';
			redirecionar('account-upgrade');
		}
		
		$plano = $resultado[0]['plano'];
		$plano_arr = $_SYSTEM['B2MAKE_PLANOS'][$plano];
		
		if(!$plano_arr){
			$_ALERTA = '<p>Este plano não existe. Favor entrar em contato com o suporte.</p>';
			redirecionar('/');
		}
		
		$assinatura_variaveis = banco_campos_virgulas(Array(
			'pagseguro_referencia',
			'pagseguro_request_code',
			'pagseguro_assinatura_code',
			'status_pagseguro',
			'status_paypal',
		));
		
		$resultado = banco_select_name
		(
			$assinatura_variaveis
			,
			"assinaturas",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND plano='".$plano."'"
		);
		
		if(!$resultado){
			banco_update
			(
				"atual=NULL",
				"assinaturas",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
			);
			
			$campos = null;
			
			$campo_nome = "atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "plano"; $campo_valor = $plano; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_inicio"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"assinaturas"
			);
			
			$id_assinaturas = banco_last_id();
			$referencia = 'PAG' . zero_a_esquerda($id_assinaturas,5);
			
			banco_update
			(
				"pagseguro_referencia='".$referencia."'",
				"assinaturas",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND plano='".$plano."'"
			);
			
			$resultado = banco_select_name
			(
				$assinatura_variaveis
				,
				"assinaturas",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND plano='".$plano."'"
			);
		}
		
		$status_pagseguro = $resultado[0]['status_pagseguro'];
		$status_paypal = $resultado[0]['status_paypal'];
		$pagseguro_assinatura_code = $resultado[0]['pagseguro_assinatura_code'];
		$pagseguro_request_code = $resultado[0]['pagseguro_request_code'];
		$referencia = $resultado[0]['pagseguro_referencia'];
		
		if($status_pagseguro || $status_paypal){
			$_ALERTA = '<p>Seu plano já tem uma assinatura e não é possível pagar novamente. Os pagamentos são automatizados via operadora de pagamento (Pagseguro ou PayPal). Qualquer dúvidas entre em contato com o suporte.</p>';
			redirecionar('/');
		}
		
		if($pagseguro_request_code){
			log_banco(Array(
				'id_referencia' => $usuario['id_usuario'],
				'grupo' => 'assinaturas',
				'valor' => '<b>PagSeguro:</b> usuário redirecionado para o pagseguro',
			));
			header('Location: https://pagseguro.uol.com.br/v2/pre-approvals/request.html?code=' . $pagseguro_request_code);
		}
		
		$count = 0;
		$maxTries = 10;
		while(true) {
			try {
				$now = new DateTime();
				$initDate = $now;
				$assinatura['dataInicio'] = $initDate->format(DATE_W3C);
				
				$preApprovalFinalDate = clone $initDate;
				$intervalo = new DateInterval( "P2Y" ); // 2 Anos
				$preApprovalFinalDate->add( $intervalo ); 
				$preApprovalFinalDate = $preApprovalFinalDate->format(DATE_W3C);
				
				$assinatura['dataExpiracao'] = $preApprovalFinalDate;
				
				$url = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/request';
				
				$data['email'] = $_PROJETO['PAGSEGURO_EMAIL'];
				$data['token'] = $_PROJETO['PAGSEGURO_TOKEN'];
				$data['redirectURL'] = $_B2MAKE_URL.'pagseguro-return';
				$data['reference'] = $referencia;
				$data['currency'] = 'BRL';
				$data['preApprovalCharge'] = 'auto';
				$data['preApprovalName'] = $plano_arr['nome'];
				$data['preApprovalDetails'] = $plano_arr['detalhes'];
				$data['preApprovalAmountPerPayment'] = $plano_arr['valor']; // Formato 1234.00
				$data['preApprovalPeriod'] = 'MONTHLY'; //  WEEKLY, MONTHLY, BIMONTHLY, TRIMONTHLY, SEMIANNUALLY, YEARLY
				//$data['preApprovalDayOfMonth'] = 1; // Nesse campo você pode enviar um valor inteiro entre 1 e 28 (Okay sua cobrança nunca poderá ocorrer dias 29, 30 ou 31. Não insista rs )
				//$data['preApprovalInitialDate' ] = $assinatura['dataInicio'];
				$data['preApprovalFinalDate' ] = $assinatura['dataExpiracao'];
				//$data['preApprovalMaxAmountPerPeriod'] = '1.50'; // Formato 1234.00
				$data['preApprovalMaxTotalAmount'] = $plano_arr['valorTotal']; // Formato 1234.00
				// $data['reviewURL'] = 'http://sounoob.com.br/produto1'; // “Assinatura – alterar”
				
				$data = http_build_query($data);
				$curl = curl_init($url);

				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				$xml = curl_exec($curl);
				
				if($xml == 'Unauthorized'){
					//Insira seu código de prevenção a erros

					gravar_log('PagSeguro: Unauthorized');
					alerta('<p>Houve um problema com o PagSeguro. Tente outra opção de pagamento.</p>');
					redirecionar('payment');
					exit;
				}
				
				curl_close($curl);
				
				libxml_use_internal_errors(true);
				$obj_xml = simplexml_load_string($xml);
				
				if(!$obj_xml){
					gravar_log('PagSeguro: XML inválido');
					alerta('<p>Houve um problema com o PagSeguro. Tente outra opção de pagamento.</p>');
					redirecionar('payment');
					exit;
				}
				
				if(count($obj_xml->error) > 0){
					gravar_log('PagSeguro: Dados inválidos: '.$xml);
					alerta('<p>Houve um problema com o PagSeguro. Tente outra opção de pagamento.</p>');
					redirecionar('payment');
					exit;
				}
				
				banco_update
				(
					"pagseguro_request_code='".$obj_xml->code."'",
					"assinaturas",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND plano='".$plano."'"
				);
				
				log_banco(Array(
					'id_referencia' => $usuario['id_usuario'],
					'grupo' => 'assinaturas',
					'valor' => '<b>PagSeguro:</b> usuário redirecionado para o pagseguro',
				));
				
				header('Location: https://pagseguro.uol.com.br/v2/pre-approvals/request.html?code=' . $obj_xml->code);
				
				break;
			} catch (Exception $e) {
				$count++;
				if($count >= $maxTries){
					alerta('<p>Houve um problema com o PagSeguro. Favor tentar novamente ou tentar novamente mais tarde. Ou então escolha outro meio de pagamento.</p>');
					redirecionar('payment');
					break;
				}
			}
			usleep(400);
		}
	}
}

function b2make_pagseguro_notificacao(){
	global $_SYSTEM;
	global $_PROJETO;
	
	/* Tipo de notificação recebida */  
	$type = $_POST['notificationType'];
	
	/* Código da notificação recebida */
	$code = $_POST['notificationCode'];
	
	if($_PROJETO['b2make_notification_logs'])
		gravar_log(print_r($_POST,true));
	
	/* Verificando tipo de notificação recebida */
	if($type === 'preApproval') {
		$email = $_PROJETO['PAGSEGURO_EMAIL'];
		$token = $_PROJETO['PAGSEGURO_TOKEN'];
		
		$url = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/notifications/' . $code . '?email=' . $email . '&token=' . $token;
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$preApproval = curl_exec($curl);
		curl_close($curl);
		
		if($preApproval == 'Unauthorized'){
			gravar_log('PagSeguro Notificacao: Unauthorized');
			exit;//Mantenha essa linha
		}
		
		libxml_use_internal_errors(true);
		$preApproval = simplexml_load_string($preApproval);
		if(!$preApproval){
			gravar_log('PagSeguro Notificacao: XML inválido');
			exit;
		}
		
		if(count($preApproval->error) > 0){
			gravar_log('PagSeguro Notificacao: Dados inválidos');
			exit;
		}
		
		$assinatura_variaveis = banco_campos_virgulas(Array(
			'pagseguro_referencia',
			'status_pagseguro',
			'id_usuario',
			'plano',
			'atual_pago',
		));
		
		$resultado = banco_select_name
		(
			$assinatura_variaveis
			,
			"assinaturas",
			"WHERE pagseguro_referencia='".$preApproval->reference."'"
		);
		
		banco_update
		(
			"pagseguro_assinatura_code='".$preApproval->code."',".
			"status_pagseguro='".$preApproval->status."'",
			"assinaturas",
			"WHERE pagseguro_referencia='".$preApproval->reference."'"
		);
		
		switch($preApproval->status){
			case 'ACTIVE':
				if($resultado[0]['status_pagseguro'] != 'ACTIVE'){
					$resultado2 = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_host',
							'server',
							'user_cpanel',
							'upgrading_plan',
						))
						,
						"host",
						"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
						." AND atual IS TRUE"
					);
					
					$resultado3 = banco_select_name
					(
						banco_campos_virgulas(Array(
							'pagseguro_assinatura_code',
						))
						,
						"assinaturas",
						"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
						." AND atual_pago IS TRUE"
					);
					
					banco_update
					(
						"atual_pago=NULL",
						"assinaturas",
						"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
					);
					
					banco_update
					(
						"atual_pago=1",
						"assinaturas",
						"WHERE pagseguro_referencia='".$preApproval->reference."'"
					);
					
					host_modificar_plan(Array(
						'plano' => $_SYSTEM['B2MAKE_PLANOS'][$resultado[0]['plano']]['nome'],
						'user' => $resultado2[0]['user_cpanel'],
						'server' => $resultado2[0]['server'],
					));
					
					host_instalar_dominio(Array(
						'id_host' => $resultado2[0]['id_host'],
						'user' => $resultado2[0]['user_cpanel'],
						'server' => $resultado2[0]['server'],
					));
					
					banco_update
					(
						"diskchanged=NULL",
						"host",
						"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
						." AND atual IS TRUE"
					);
					
					if($resultado2[0]['upgrading_plan']){
						banco_update
						(
							"upgrading_plan=NULL",
							"host",
							"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
							." AND atual IS TRUE"
						);
					}
					
					if($resultado3[0]['pagseguro_assinatura_code']){
						$code = $resultado3[0]['pagseguro_assinatura_code'];
						
						b2make_pagseguro_assinatura_cancelar($code);
					}
				}
			break;
			case 'CANCELLED_BY_RECEIVER':
			case 'CANCELLED_BY_SENDER':
				$resultado2 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'server',
						'user_cpanel',
					))
					,
					"host",
					"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
					." AND atual IS TRUE"
				);
				
				if($resultado[0]['atual_pago']){
					host_suspender_conta(Array(
						'id_usuario' => $resultado[0]['id_usuario'],
						'user' => $resultado2[0]['user_cpanel'],
						'server' => $resultado2[0]['server'],
					));
				}
			break;
		}
		
		log_banco(Array(
			'id_referencia' => $resultado[0]['id_usuario'],
			'grupo' => 'assinaturas',
			'valor' => '<b>PagSeguro:</b> alterou o status para: <b>'. $preApproval->status .'</b> | preApprovalCode: '.$preApproval->code,
		));
	}
	
	if($type === 'transaction') {
		$email = $_PROJETO['PAGSEGURO_EMAIL'];
		$token = $_PROJETO['PAGSEGURO_TOKEN'];
		
		$url = 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/' . $code . '?email=' . $email . '&token=' . $token;
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$transaction = curl_exec($curl);
		curl_close($curl);
		
		if($transaction == 'Unauthorized'){
			gravar_log('PagSeguro Notificacao: Unauthorized');
			exit;//Mantenha essa linha
		}
		
		libxml_use_internal_errors(true);
		$transaction = simplexml_load_string($transaction);
		if(!$transaction){
			gravar_log('PagSeguro Notificacao: XML inválido');
			exit;
		}
		
		if(count($transaction->error) > 0){
			gravar_log('PagSeguro Notificacao: Dados inválidos');
			exit;
		}
		
		$assinatura_variaveis = banco_campos_virgulas(Array(
			'id_usuario',
		));
		
		$resultado = banco_select_name
		(
			$assinatura_variaveis
			,
			"assinaturas",
			"WHERE pagseguro_referencia='".$transaction->reference."'"
		);
		
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_assinaturas',
			))
			,
			"assinaturas",
			"WHERE pagseguro_referencia='".$transaction->reference."'"
		);
		
		$id_assinaturas = $resultado2[0]['id_assinaturas'];
		$id_usuario = $resultado[0]['id_usuario'];
		
		switch($transaction->status){
			case 1: $titulo = "Aguardando pagamento"; break;
			case 2: $titulo = "Em análise"; break;
			case 3: $titulo = "Pago"; break;
			case 4: $titulo = "Finalização da Transação"; break;
			case 5: $titulo = "Em disputa"; break;
			case 6: $titulo = "Dinheiro Devolvido"; break;
			case 7: $titulo = "Cancelado"; break;
		}
		
		$campos = null;
		
		$campo_nome = "id_assinaturas"; $campo_valor = $id_assinaturas; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pagseguro_status"; $campo_valor = $titulo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "pagseguro_notification_code"; $campo_valor = $transaction->code; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "valor"; $campo_valor = (float)$transaction->grossAmount; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		
		banco_insert_name
		(
			$campos,
			"pagamentos"
		);
		
		log_banco(Array(
			'id_referencia' => $resultado[0]['id_usuario'],
			'grupo' => 'pagamentos',
			'valor' => '<b>PagSeguro:</b> alterou o status para: <b>'.$titulo.'</b> | referenceAssinatura: '.$transaction->reference,
		));
	}
}

function b2make_pagseguro_retorno(){
	global $_PROJETO;
	global $_SYSTEM;
	
	$conteudo_perfil = true;
	if($_PROJETO['b2make_permissao_id'] == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
		$conteudo_perfil = false;
	}
	
	if($conteudo_perfil){
		alerta('<p>Seu usuário não tem permissão de acessar esta área.</p>');
		redirecionar('/');
	} else {
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$assinatura_variaveis = banco_campos_virgulas(Array(
			'status_pagseguro',
			'pagseguro_referencia',
			'plano',
		));
		
		$resultado = banco_select_name
		(
			$assinatura_variaveis
			,
			"assinaturas",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		if(!$resultado){
			alerta('<p>Você não tem nenhuma assinatura associada. Favor fazer um pagamento para poder acessar está área.</p>');
			redirecionar('payment');
		}
		
		if($resultado[0]['status_pagseguro'] == 'ACTIVE'){
			$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'complete';
			redirecionar('payment-complete');
		}
		
		$count = 0;
		$maxTries = 10;
		while(true) {
			try {
				$url = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/notifications';
				
				$data['email'] = $_PROJETO['PAGSEGURO_EMAIL'];
				$data['token'] = $_PROJETO['PAGSEGURO_TOKEN'];
				$data['interval'] = '1';
				
				$data = http_build_query($data);
				$url .= '?'.$data;
				$curl = curl_init($url);

				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				//curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				$xml = curl_exec($curl);
				
				if($xml == 'Unauthorized'){
					//Insira seu código de prevenção a erros

					gravar_log('PagSeguro Retorno: Unauthorized');
					$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pagseguro-erro';
					redirecionar('payment');
					exit;
				}
				
				curl_close($curl);
				
				libxml_use_internal_errors(true);
				$obj_xml = simplexml_load_string($xml);
				
				if(!$obj_xml){
					gravar_log('PagSeguro Retorno: XML inválido');
					$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pagseguro-erro';
					redirecionar('payment');
					exit;
				}
				
				if(count($obj_xml->error) > 0){
					gravar_log('PagSeguro Retorno: Dados inválidos: '.$xml);
					$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pagseguro-erro';
					redirecionar('payment');
					exit;
				}
				
				if((int)$obj_xml->resultsInThisPage > 0){
					if($obj_xml->preApprovals)
					foreach($obj_xml->preApprovals->preApproval as $var){
						if($resultado[0]['pagseguro_referencia'] == $var->reference){
							banco_update
							(
								"pagseguro_assinatura_code='".$var->code."',".
								"status_pagseguro='".$var->status."'",
								"assinaturas",
								"WHERE id_usuario='".$usuario['id_usuario']."'"
								." AND atual IS TRUE"
							);
							
							log_banco(Array(
								'id_referencia' => $usuario['id_usuario'],
								'grupo' => 'assinaturas',
								'valor' => '<b>PagSeguro:</b> alterou o status para: <b>'. $var->status .'</b> | preApprovalCode: '.$var->code,
							));
							
							switch($var->status){
								case 'ACTIVE':
									$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'complete';
									
									if($resultado[0]['status_pagseguro'] != 'ACTIVE'){
										$resultado2 = banco_select_name
										(
											banco_campos_virgulas(Array(
												'id_host',
												'server',
												'user_cpanel',
												'upgrading_plan',
											))
											,
											"host",
											"WHERE id_usuario='".$usuario['id_usuario']."'"
											." AND atual IS TRUE"
										);
										
										$resultado3 = banco_select_name
										(
											banco_campos_virgulas(Array(
												'pagseguro_assinatura_code',
											))
											,
											"assinaturas",
											"WHERE id_usuario='".$usuario['id_usuario']."'"
											." AND atual_pago IS TRUE"
										);
										
										banco_update
										(
											"atual_pago=NULL",
											"assinaturas",
											"WHERE id_usuario='".$usuario['id_usuario']."'"
										);
										
										banco_update
										(
											"atual_pago=1",
											"assinaturas",
											"WHERE pagseguro_referencia='".$var->reference."'"
										);
										
										host_modificar_plan(Array(
											'plano' => $_SYSTEM['B2MAKE_PLANOS'][$resultado[0]['plano']]['nome'],
											'user' => $resultado2[0]['user_cpanel'],
											'server' => $resultado2[0]['server'],
										));
										
										host_instalar_dominio(Array(
											'id_host' => $resultado2[0]['id_host'],
											'user' => $resultado2[0]['user_cpanel'],
											'server' => $resultado2[0]['server'],
										));
										
										$plano = $resultado[0]['plano'];
										$plano_arr = $_SYSTEM['B2MAKE_PLANOS'][$plano];
										$valor_total = number_format((float)$plano_arr['valor'], 2, '.', '');
										$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-dados'] = Array(
											'pedido_id' => $resultado[0]['pagseguro_referencia'],
											'item_id' => $plano,
											'item_titulo' => $plano_arr['nome'],
											'item_preco' => $valor_total,
										);
										
										banco_update
										(
											"diskchanged=NULL",
											"host",
											"WHERE id_usuario='".$usuario['id_usuario']."'"
											." AND atual IS TRUE"
										);
										
										if($resultado2[0]['upgrading_plan']){
											banco_update
											(
												"upgrading_plan=NULL",
												"host",
												"WHERE id_usuario='".$usuario['id_usuario']."'"
												." AND atual IS TRUE"
											);
											$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-upgrade'] = true;
										}
										
										if($resultado3[0]['pagseguro_assinatura_code']){
											$code = $resultado3[0]['pagseguro_assinatura_code'];
											
											b2make_pagseguro_assinatura_cancelar($code);
										}
									}
								break;
								default:
									$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pending';
							}
						}
					}
				} else {
					$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pending';
				}
				
				redirecionar('payment-complete');
				
				break;
			} catch (Exception $e) {
				$count++;
				if($count >= $maxTries){
					$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pagseguro-erro';
					gravar_log('PagSeguro Retorno: Exception');
					redirecionar('payment-complete');
					break;
				}
			}
			usleep(400);
		}
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

function b2make_pagamento_completo(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	
	$status = $_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'];$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = false;
	$dados = $_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-dados'];$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-dados'] = false;
	$upgrade = $_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-upgrade'];$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-upgrade'] = false;
	
	/* $upgrade = true;
	$dados = Array(
		'pedido_id' => 'P00980',
		'item_id' => '2',
		'item_titulo' => 'Plano Pro',
		'item_preco' => '19.90',
	); */
	
	if($dados){
		if($upgrade){
			$dados['item_titulo'] = 'UPGRADE ' . $dados['item_titulo'];
		}
		$dados['item_titulo'] = $dados['item_titulo'];
	} else {
		
	}
	
	switch($status){
		case 'complete':
			$mesage = 'Seu pagamento foi efetuado com sucesso.';
		break;
		case 'pending':
			$mesage = 'Aguardando a libera&ccedil;&atilde;o da bandeira. <br>
Mas n&atilde;o se preocupe, j&aacute; liberamos a ferramenta para voc&ecirc; utilizar.';
		break;
		case 'pagseguro-erro':
			$mesage = 'Houve um problema com o PagSeguro.<br>
 Independente disso voc&ecirc; pode acessar nossa ferramente. Em breve um de nosso atendentes resolver&aacute; o problema.';
		break;
		default:
			$mesage = 'N&atilde;o foi detectado nenhum pagamento feito';
	}
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- payment-complete < -->','<!-- payment-complete > -->');
	
	$pagina = modelo_var_troca($pagina,"#mesage#",$mesage);
	
	if($dados){
		$_VARIAVEIS_JS['b2make_analytics_dados'] = $dados;
	}
	
	$layout = $pagina;
	
	return $layout;
}

function b2make_paypal_assinatura(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_ALERTA;
	global $_B2MAKE_URL;
	global $_PAYPAL_SANDBOX;
	global $_PAYPAL;
	
	$conteudo_perfil = true;
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $id){
		if($id == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
			$conteudo_perfil = false;
			break;
		}
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'payment';
		redirecionar('signin');
	} else {
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'plano',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		if(!$resultado){
			$_ALERTA = '<p>Não existe site definido para esta conta de usuário</p>';
			redirecionar('/');
		}
		
		if(!$resultado[0]['plano']){
			$_ALERTA = '<p>Não há plano definido para esse usuário. Favor fazer upgrade do seu plano.</p>';
			redirecionar('account-upgrade');
		}
		
		if($resultado[0]['plano'] == '1'){
			$_ALERTA = '<p>Este plano é gratuito e portanto não é possível fazer uma assinatura. Favor fazer upgrade do seu plano.</p>';
			redirecionar('account-upgrade');
		}
		
		$plano = $resultado[0]['plano'];
		$plano_arr = $_SYSTEM['B2MAKE_PLANOS'][$plano];
		
		if(!$plano_arr){
			$_ALERTA = '<p>Este plano não existe. Favor entrar em contato com o suporte.</p>';
			redirecionar('/');
		}
		
		$assinatura_variaveis = banco_campos_virgulas(Array(
			'paypal_referencia',
			'paypal_request_code',
			'pagseguro_assinatura_code',
			'status_pagseguro',
			'status_paypal',
		));
		
		$resultado = banco_select_name
		(
			$assinatura_variaveis
			,
			"assinaturas",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND plano='".$plano."'"
		);
		
		if(!$resultado){
			banco_update
			(
				"atual=NULL",
				"assinaturas",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
			);
			
			$campos = null;
			
			$campo_nome = "atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "plano"; $campo_valor = $plano; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_inicio"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"assinaturas"
			);
			
			$id_assinaturas = banco_last_id();
			$referencia = 'PAY' . zero_a_esquerda($id_assinaturas,5);
			
			banco_update
			(
				"paypal_referencia='".$referencia."'",
				"assinaturas",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND plano='".$plano."'"
			);
			
			$resultado = banco_select_name
			(
				$assinatura_variaveis
				,
				"assinaturas",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND plano='".$plano."'"
			);
		}
		
		$status_pagseguro = $resultado[0]['status_pagseguro'];
		$status_paypal = $resultado[0]['status_paypal'];
		$paypal_request_code = $resultado[0]['paypal_request_code'];
		$referencia = $resultado[0]['paypal_referencia'];
		
		if($status_pagseguro || $status_paypal){
			$_ALERTA = '<p>Seu plano já tem uma assinatura e não é possível pagar novamente. Os pagamentos são automatizados via operadora de pagamento (Pagseguro ou PayPal). Qualquer dúvidas entre em contato com o suporte.</p>';
			redirecionar('/');
		}
		
		if ($_PAYPAL_SANDBOX) {
			//credenciais da API para o Sandbox
			$user = 'otavioserra-facilitator_api1.gmail.com';
			$pswd = '1400005808';
			$signature = 'A86WhSbRNk-zAyk1sEPIVYSkWyQKAsA5L7BkQ.hesbYmUaAfnhg45vvO';
		  
			//URL da PayPal para redirecionamento, não deve ser modificada
			$paypalURLNVP = 'https://api-3t.sandbox.paypal.com/nvp';
			$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		} else {
			//credenciais da API para produção
			$user = $_PROJETO['PAYPAL_USER'];
			$pswd = $_PROJETO['PAYPAL_PASS'];
			$signature = $_PROJETO['PAYPAL_SIGNATURE'];
		  
			//URL da PayPal para redirecionamento, não deve ser modificada
			$paypalURLNVP = 'https://api-3t.paypal.com/nvp';
			$paypalURL = 'https://www.paypal.com/cgi-bin/webscr';
		}
		
		if($paypal_request_code){
			$query = array(
				'cmd'    => '_express-checkout',
				'token'  => $paypal_request_code
			);
			
			log_banco(Array(
				'id_referencia' => $usuario['id_usuario'],
				'grupo' => 'assinaturas',
				'valor' => '<b>PayPal:</b> usuário redirecionado para o paypal',
			));
			
			header('Location: ' . $paypalURL . '?' . http_build_query($query));
		}
		
		$count = 0;
		$maxTries = 10;
		while(true) {
			try {
				$valor_total = number_format((float)$plano_arr['valor'], 2, '.', '');

				$curl = curl_init();
				
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_URL, $paypalURLNVP);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
					'USER' => $user,
					'PWD' => $pswd,
					'SIGNATURE' => $signature,
				  
					'METHOD' => 'SetExpressCheckout',
					'VERSION' => '108',
					'LOCALECODE' => 'pt_BR',
					'CUSTOM'=> $referencia,
					'INITAMT'=> $valor_total,
				  
					'PAYMENTREQUEST_0_AMT' => $valor_total,
					'PAYMENTREQUEST_0_CURRENCYCODE' => 'BRL',
					'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
					'PAYMENTREQUEST_0_ITEMAMT' => $valor_total,
					'PAYMENTREQUEST_0_INVNUM' => $referencia,
				  
					'L_PAYMENTREQUEST_0_NAME0' => $plano_arr['nome'],
					'L_PAYMENTREQUEST_0_DESC0' => limite_texto($plano_arr['detalhes'],120),
					'L_PAYMENTREQUEST_0_QTY0' => 1,
					'L_PAYMENTREQUEST_0_AMT0' => $valor_total,
					'L_PAYMENTREQUEST_0_ITEMCATEGORY0' => 'Digital',
				  
					'L_BILLINGTYPE0' => 'RecurringPayments',
					'L_BILLINGAGREEMENTDESCRIPTION0' => $plano_arr['nome'],
				  
					'RETURNURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-return',
					'CANCELURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-cancel'
				)));
				
				$response = curl_exec($curl);
				
				curl_close($curl);
				
				$nvp = array();
				
				if(preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
					foreach ($matches['name'] as $offset => $name) {
						$nvp[$name] = urldecode($matches['value'][$offset]);
					}
				}
				
				if(isset($nvp['ACK']) && $nvp['ACK'] == 'Success') {
					$query = array(
						'cmd'    => '_express-checkout',
						'token'  => $nvp['TOKEN']
					);
					
					banco_update
					(
						"paypal_request_code='".$nvp['TOKEN']."'",
						"assinaturas",
						"WHERE id_usuario='".$usuario['id_usuario']."'"
						." AND plano='".$plano."'"
					);
					
					log_banco(Array(
						'id_referencia' => $usuario['id_usuario'],
						'grupo' => 'assinaturas',
						'valor' => '<b>PayPal:</b> usuário redirecionado para o paypal',
					));
					
					header('Location: ' . $paypalURL . '?' . http_build_query($query));
				} else {
					gravar_log('PayPal Signature: Erro ACK'.' RESPONSE: '.$response);
					alerta('<p>Houve um problema com o PayPal. Tente outra opção de pagamento.</p>');
					redirecionar('payment');
					exit;
				}
				
				break;
			} catch (Exception $e) {
				$count++;
				if($count >= $maxTries){
					alerta('<p>Houve um problema com o PayPal. Favor tentar novamente ou tentar novamente mais tarde. Ou então escolha outro meio de pagamento.</p>');
					redirecionar('payment');
					break;
				}
			}
			usleep(400);
		}
	}
}

function b2make_paypal_isIPNValid(array $message){
    $endpoint = 'https://www.paypal.com';
  
    if (isset($message['test_ipn']) && $message['test_ipn'] == '1') {
        $endpoint = 'https://www.sandbox.paypal.com';
    }
  
    $endpoint .= '/cgi-bin/webscr?cmd=_notify-validate';
  
    $curl = curl_init();
  
    curl_setopt($curl, CURLOPT_URL, $endpoint);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($message));
   
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $errno = curl_errno($curl);
  
    curl_close($curl);
   
    return empty($error) && $errno == 0 && $response == 'VERIFIED';
}

function b2make_paypal_notificacao(){
	global $_SYSTEM;
	global $_PROJETO;
	
	// Send an empty HTTP 200 OK response to acknowledge receipt of the notification 
	header('HTTP/1.1 200 OK');
	
	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		//Antes de trabalhar com a notificação, precisamos verificar se ela
		//é válida e, se não for, descartar.
		if (!b2make_paypal_isIPNValid($_REQUEST)) {
			return;
		}
		
		if($_PROJETO['b2make_notification_logs'])
			gravar_log(print_r($_REQUEST,true));
		
		foreach($_REQUEST as $var => $val)$$var = $val;
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario',
				'paypal_assinatura_code',
				'plano',
				'atual_pago',
				'status_paypal',
			))
			,
			"assinaturas",
			"WHERE paypal_referencia='".$rp_invoice_id."'"
		);
		
		switch($txn_type){
			case 'recurring_payment_profile_created':
				if(!$resultado[0]['paypal_assinatura_code']){
					banco_update
					(
						"paypal_assinatura_code='".$recurring_payment_id."'",
						"assinaturas",
						"WHERE paypal_referencia='".$rp_invoice_id."'"
					);
				}
				
				switch($profile_status){
					case 'Active':
						$resultado2 = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_host',
								'server',
								'user_cpanel',
								'upgrading_plan',
							))
							,
							"host",
							"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
							." AND atual IS TRUE"
						);
						
						if($resultado[0]['status_paypal'] != 'ActiveProfile' && $resultado[0]['status_paypal'] != 'Active'){
							$resultado3 = banco_select_name
							(
								banco_campos_virgulas(Array(
									'paypal_assinatura_code',
								))
								,
								"assinaturas",
								"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
								." AND atual_pago IS TRUE"
							);
						}
						
						banco_update
						(
							"atual_pago=NULL",
							"assinaturas",
							"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
						);
						
						banco_update
						(
							"atual_pago=1",
							"assinaturas",
							"WHERE paypal_referencia='".$rp_invoice_id."'"
						);
						
						if($resultado[0]['status_paypal'] != 'ActiveProfile' && $resultado[0]['status_paypal'] != 'Active'){
							host_modificar_plan(Array(
								'plano' => $_SYSTEM['B2MAKE_PLANOS'][$resultado[0]['plano']]['nome'],
								'user' => $resultado2[0]['user_cpanel'],
								'server' => $resultado2[0]['server'],
							));
							
							host_instalar_dominio(Array(
								'id_host' => $resultado2[0]['id_host'],
								'user' => $resultado2[0]['user_cpanel'],
								'server' => $resultado2[0]['server'],
							));
							
							banco_update
							(
								"diskchanged=NULL",
								"host",
								"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
								." AND atual IS TRUE"
							);
						}
						
						if($resultado2[0]['upgrading_plan']){
							banco_update
							(
								"upgrading_plan=NULL",
								"host",
								"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
								." AND atual IS TRUE"
							);
						}
						
						if($resultado3[0]['paypal_assinatura_code']){
							$code = $resultado3[0]['paypal_assinatura_code'];
							
							b2make_paypal_assinatura_cancelar($code,'Usuário fez Upgrade de Plano e por esse motivo uma nova assitura foi feita e esta cancelada.');
						}
					break;
				}
				
				banco_update
				(
					"status_paypal='".$profile_status."'",
					"assinaturas",
					"WHERE paypal_referencia='".$rp_invoice_id."'"
				);
				
				log_banco(Array(
					'id_referencia' => $resultado[0]['id_usuario'],
					'grupo' => 'assinaturas',
					'valor' => '<b>PayPal:</b> criou profile e alterou o status para: <b>'.$profile_status.'</b> | profileID: '.$recurring_payment_id,
				));
			break;
			case 'recurring_payment_profile_cancel':
				banco_update
				(
					"status_paypal='".$profile_status."'",
					"assinaturas",
					"WHERE paypal_referencia='".$rp_invoice_id."'"
				);
				
				$resultado2 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'server',
						'user_cpanel',
					))
					,
					"host",
					"WHERE id_usuario='".$resultado[0]['id_usuario']."'"
					." AND atual IS TRUE"
				);
				
				if($resultado[0]['atual_pago']){
					host_suspender_conta(Array(
						'id_usuario' => $resultado[0]['id_usuario'],
						'user' => $resultado2[0]['user_cpanel'],
						'server' => $resultado2[0]['server'],
					));
				}
				
				log_banco(Array(
					'id_referencia' => $resultado[0]['id_usuario'],
					'grupo' => 'assinaturas',
					'valor' => '<b>PayPal:</b> cancelou profile e alterou o status para: <b>'.$profile_status.'</b> | profileID: '.$recurring_payment_id,
				));
			break;
			case 'recurring_payment':
				$resultado2 = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_assinaturas',
					))
					,
					"assinaturas",
					"WHERE paypal_referencia='".$rp_invoice_id."'"
				);
				
				$id_assinaturas = $resultado2[0]['id_assinaturas'];
				$id_usuario = $resultado[0]['id_usuario'];
				
				$campos = null;
				
				$campo_nome = "id_assinaturas"; $campo_valor = $id_assinaturas; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "paypal_status"; $campo_valor = $profile_status; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "paypal_notification_code"; $campo_valor = $txn_id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "valor"; $campo_valor = (float)$amount; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				banco_insert_name
				(
					$campos,
					"pagamentos"
				);
				
				log_banco(Array(
					'id_referencia' => $resultado[0]['id_usuario'],
					'grupo' => 'pagamentos',
					'valor' => '<b>PayPal:</b> recebeu um pagamento com status: <b>'.$payment_status.'</b> | profileID: '.$recurring_payment_id,
				));
			break;
			
		}
		
		/* $email_subject = 'OK';
		$email_mensagem = print_r($_REQUEST,true);
		
		if($_SYSTEM['DOMINIO'] != 'localhost') enviar_mail(Array(
			'email_name' => $email_name,
			'email' => $email_email,
			'subject' => $email_subject,
			'mensagem' => $email_mensagem . $_SYSTEM['MAILER_ASSINATURA'],
		)); */
	}
	
	exit(0);
}

function b2make_paypal_retorno(){
	global $_PROJETO;
	global $_SYSTEM;
	global $_PAYPAL_SANDBOX;
	
	$conteudo_perfil = true;
	if($_PROJETO['b2make_permissao_id'] == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
		$conteudo_perfil = false;
	}
	
	if($conteudo_perfil){
		alerta('<p>Seu usuário não tem permissão de acessar esta área.</p>');
		redirecionar('/');
	} else {
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$assinatura_variaveis = banco_campos_virgulas(Array(
			'status_paypal',
			'paypal_referencia',
			'plano',
		));
		
		$resultado = banco_select_name
		(
			$assinatura_variaveis
			,
			"assinaturas",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		if(!$resultado){
			alerta('<p>Você não tem nenhuma assinatura associada. Favor fazer um pagamento para poder acessar está área.</p>');
			redirecionar('payment');
		}
		
		if($resultado[0]['status_paypal'] == 'ActiveProfile' || $resultado[0]['status_paypal'] == 'Active'){
			$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'complete';
			redirecionar('payment-complete');
		}
		
		$token = $_REQUEST['token'];
		$PayerID = $_REQUEST['PayerID'];
		
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
				$now = new DateTime();
				$initDate = $now;
				$start_date = $initDate->format('Y-m-d\TH:m:s\Z');
				
				$plano = $resultado[0]['plano'];
				$plano_arr = $_SYSTEM['B2MAKE_PLANOS'][$plano];
				
				$valor_total = number_format((float)$plano_arr['valor'], 2, '.', '');
				
				$curl = curl_init();
				
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_URL, $paypalURLNVP);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
					'USER' => $user,
					'PWD' => $pswd,
					'SIGNATURE' => $signature,
					
					'METHOD' => 'CreateRecurringPaymentsProfile',
					'VERSION' => '108',
					'LOCALECODE' => 'pt_BR',
				  
					'TOKEN' => $token,
					'PayerID' => $PayerID,
				  
					'PROFILESTARTDATE' => $start_date,
					'PROFILEREFERENCE' => $resultado[0]['paypal_referencia'],
					'DESC' => $plano_arr['nome'],
					'BILLINGPERIOD' => 'Month',
					'BILLINGFREQUENCY' => '1',
					'AMT' => $valor_total,
					'CURRENCYCODE' => 'BRL',
					'COUNTRYCODE' => 'BR',
					'MAXFAILEDPAYMENTS' => 3
				)));
				
				$response = curl_exec($curl);
				
				curl_close($curl);
				
				$nvp = array();
				
				if(preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
					foreach ($matches['name'] as $offset => $name) {
						$nvp[$name] = urldecode($matches['value'][$offset]);
					}
				}
				
				if(isset($nvp['ACK']) && $nvp['ACK'] == 'Success') {
					banco_update
					(
						"paypal_assinatura_code='".$nvp['PROFILEID']."',".
						"status_paypal='".$nvp['PROFILESTATUS']."'",
						"assinaturas",
						"WHERE id_usuario='".$usuario['id_usuario']."'"
						." AND atual IS TRUE"
					);
					
					switch($nvp['PROFILESTATUS']){
						case 'ActiveProfile':
							$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'complete';
							
							$resultado2 = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_host',
									'server',
									'user_cpanel',
									'upgrading_plan',
								))
								,
								"host",
								"WHERE id_usuario='".$usuario['id_usuario']."'"
								." AND atual IS TRUE"
							);
							
							$resultado3 = banco_select_name
							(
								banco_campos_virgulas(Array(
									'paypal_assinatura_code',
								))
								,
								"assinaturas",
								"WHERE id_usuario='".$usuario['id_usuario']."'"
								." AND atual_pago IS TRUE"
							);
							
							banco_update
							(
								"atual_pago=NULL",
								"assinaturas",
								"WHERE id_usuario='".$usuario['id_usuario']."'"
							);
							
							banco_update
							(
								"atual_pago=1",
								"assinaturas",
								"WHERE paypal_referencia='".$resultado[0]['paypal_referencia']."'"
							);
							
							host_modificar_plan(Array(
								'plano' => $_SYSTEM['B2MAKE_PLANOS'][$resultado[0]['plano']]['nome'],
								'user' => $resultado2[0]['user_cpanel'],
								'server' => $resultado2[0]['server'],
							));
							
							host_instalar_dominio(Array(
								'id_host' => $resultado2[0]['id_host'],
								'user' => $resultado2[0]['user_cpanel'],
								'server' => $resultado2[0]['server'],
							));
							
							$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-dados'] = Array(
								'pedido_id' => $resultado[0]['paypal_referencia'],
								'item_id' => $plano,
								'item_titulo' => $plano_arr['nome'],
								'item_preco' => $valor_total,
							);
							
							banco_update
							(
								"diskchanged=NULL",
								"host",
								"WHERE id_usuario='".$usuario['id_usuario']."'"
								." AND atual IS TRUE"
							);
							
							if($resultado2[0]['upgrading_plan']){
								banco_update
								(
									"upgrading_plan=NULL",
									"host",
									"WHERE id_usuario='".$usuario['id_usuario']."'"
									." AND atual IS TRUE"
								);
								$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-upgrade'] = true;
							}
							
							if($resultado3[0]['paypal_assinatura_code']){
								$code = $resultado3[0]['paypal_assinatura_code'];
								
								b2make_paypal_assinatura_cancelar($code,'Usuário fez Upgrade de Plano e por esse motivo uma nova assitura foi feita e esta cancelada.');
							}
						break;
						default:
							$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pending';
					}
					
					log_banco(Array(
						'id_referencia' => $usuario['id_usuario'],
						'grupo' => 'assinaturas',
						'valor' => '<b>PayPal:</b> criou profile e alterou o status para: <b>'.$nvp['PROFILESTATUS'].'</b> | profileID: '.$nvp['PROFILEID'],
					));
				} else {
					gravar_log('PayPal Return: Erro ACK'.$response);
					alerta('<p>Houve um problema com o PayPal. Tente outra opção de pagamento.</p>');
					redirecionar('payment');
					exit;
				}
				
				redirecionar('payment-complete');
				
				break;
			} catch (Exception $e) {
				$count++;
				if($count >= $maxTries){
					$_SESSION[$_SYSTEM['ID'].'b2make-payment-complete-status'] = 'pagseguro-erro';
					gravar_log('PagSeguro Retorno: Exception');
					redirecionar('payment-complete');
					break;
				}
			}
			usleep(400);
		}
	}
}

function b2make_paypal_cancelamento(){
	alerta('Paypal Cancelado');
	
	redirecionar('payment');
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

function b2make_cadastro_sucesso(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	
	if($_SESSION[$_SYSTEM['ID']."b2make-analytics-cadastro-sucesso"]){
		$_SESSION[$_SYSTEM['ID']."b2make-analytics-cadastro-sucesso"] = false;
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_host',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		$referencia = 'FREE' . zero_a_esquerda($resultado[0]['id_host'],5);
		
		$dados = Array(
			'pedido_id' => $referencia,
			'item_id' => '1',
			'item_titulo' => 'Plano Free',
			'item_preco' => '0.00',
		);
		
		$_VARIAVEIS_JS['b2make_analytics_dados'] = $dados;
	}
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- signup-success < -->','<!-- signup-success > -->');

	$layout = $pagina;
	
	return $layout;
}

function b2make_pagamento(){
	global $_SYSTEM;
	global $_PROJETO;
	
	$conteudo_perfil = true;
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $id){
		if($id == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
			$conteudo_perfil = false;
			break;
		}
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'payment';
		redirecionar('signin');
	} else {
		if($_PROJETO['payment']){
			if($_PROJETO['payment']['layout']){
				$layout = $_PROJETO['payment']['layout'];
			}
		}
		
		if(!$layout){
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- payment < -->','<!-- payment > -->');
			
			$layout = $pagina;
		}
		
		return $layout;
	}
}

function host_instalar_dominio($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_CPANEL;
	global $_B2MAKE_HOST;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($user && $server){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'dominio_proprio',
				'ftp_site_user',
				'ftp_files_user',
				'user_host',
			))
			,
			"host",
			"WHERE id_host='".$id_host."'"
			." AND dominio_proprio_instalado IS NULL"
			." AND dominio_proprio IS NOT NULL"
		);
		
		if($resultado){
			$domain = $resultado[0]['dominio_proprio'];
			$ftp_site_user = $resultado[0]['ftp_site_user'];
			$ftp_files_user = $resultado[0]['ftp_files_user'];
			$park = $resultado[0]['user_host'] . '.' . $_B2MAKE_HOST;
			
			$ftp_site_user_arr = explode('@',$ftp_site_user);
			$ftp_files_user_arr = explode('@',$ftp_files_user);
			
			$_CPANEL['ACCT'] = Array(
				'user' => $user,
				'domain' => $domain,
				'park' => $park,
			);
			$_CPANEL['FTP_LOCAL'] = $server;
			
			require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-domain-install.php');
			
			banco_update
			(
				"ftp_site_user='".($ftp_site_user_arr[0] . '@' . $domain)."',".
				"ftp_files_user='".($ftp_files_user_arr[0] . '@' . $domain)."',".
				"dominio_proprio_instalado=1",
				"host",
				"WHERE id_host='".$id_host."'"
			);
		}
	}
}

function host_suspender_conta($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($id_usuario){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'status',
			))
			,
			"usuario",
			"WHERE id_usuario='".$id_usuario."'"
		);
		
		if($resultado[0]['status'] == 'D')
			return;
		
		banco_update
		(
			"status='B'",
			"usuario",
			"WHERE id_usuario='".$id_usuario."'"
		);
	}
	
	if($user && $server){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
		);
		$_CPANEL['FTP_LOCAL'] = $server;
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-suspendacct.php');
	}
}

function host_dessuspender_conta($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($id_usuario){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'status',
			))
			,
			"usuario",
			"WHERE id_usuario='".$id_usuario."'"
		);
		
		if($resultado[0]['status'] == 'D')
			return;
		
		banco_update
		(
			"status='A'",
			"usuario",
			"WHERE id_usuario='".$id_usuario."'"
		);
	}
	
	if($user && $server){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
		);
		$_CPANEL['FTP_LOCAL'] = $server;
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-unsuspendacct.php');
	}
}

function host_excluir_conta($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_B2MAKE_PAGINA_LOCAL;
	
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
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-removeacct.php');
	}
}

function host_conta_sumario($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($user && $server){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
		);
		$_CPANEL['FTP_LOCAL'] = $server;
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-accountsummary.php');
		
		return $_CPANEL_RESULT;
	} else {
		return false;
	}
}

function host_modificar_plan($params = false){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	global $_CPANEL;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($plano && $user && $server){
		$_CPANEL['ACCT'] = Array(
			'user' => $user,
			'plan' => $plano,
		);
		$_CPANEL['FTP_LOCAL'] = $server;
		
		require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-changepackage.php');
	}
}

function pagseguro_teste(){
	global $_SYSTEM;
	global $_PROJETO;
	
	$reference = 'REF1234';
	
	$count = 0;
	$maxTries = 10;
	while(true) {
		try {
			$url = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/notifications';
			
			$data['email'] = $_PROJETO['PAGSEGURO_EMAIL'];
			$data['token'] = $_PROJETO['PAGSEGURO_TOKEN'];
			$data['interval'] = '7';
			
			$data = http_build_query($data);
			$url .= '?'.$data;
			$curl = curl_init($url);

			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			$xml = curl_exec($curl);
			
			if($xml == 'Unauthorized'){
				//Insira seu código de prevenção a erros

				echo('PagSeguro Retorno: Unauthorized');
				
				exit;
			}
			
			curl_close($curl);
			
			libxml_use_internal_errors(true);
			$obj_xml = simplexml_load_string($xml);
			
			if(!$obj_xml){
				echo('PagSeguro Retorno: XML inválido');
				exit;
			}
			
			if(count($obj_xml->error) > 0){
				echo('PagSeguro Retorno: Dados inválidos: '.$xml);
				exit;
			}
			
			if((int)$obj_xml->resultsInThisPage > 0){
				if($obj_xml->preApprovals)
				foreach($obj_xml->preApprovals->preApproval as $var){
					if($var->reference == $reference){
						echo $var->code . ' ' . $var->status;
					}
				}
			} else {
				
			}
			
			break;
		} catch (Exception $e) {
			$count++;
			if($count >= $maxTries){
				echo('PagSeguro Retorno: Exception');
				break;
			}
		}
		usleep(400);
	}
}

function b2make_my_profile(){
	global $_SYSTEM;
	global $_PROJETO;
	
	$conteudo_perfil = true;
	if($_SESSION[$_SYSTEM['ID']."permissao_id"]){
		$conteudo_perfil = false;
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'my-profile';
		redirecionar('signin');
	} else {
		if($_PROJETO['my-profile']){
			if($_PROJETO['my-profile']['layout']){
				$layout = $_PROJETO['my-profile']['layout'];
			}
		}
		
		if(!$layout){
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- my-profile < -->','<!-- my-profile > -->');
			
			$layout = $pagina;
		}
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$layout = modelo_var_troca($layout,"#email#",$usuario['email']);
		$layout = modelo_var_troca($layout,"#nome#",$usuario['nome']);
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
}

function b2make_my_profile_bd(){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_REQUEST['nome']){
		alerta('Não foi definido o seu nome. Defina seu nome e tente novamente'); return;
	}
	
	if($_REQUEST['senha'])
	if($_SESSION[$_SYSTEM['ID']."usuario_senha"] != $_REQUEST['senha']){
		alerta('Sua senha atual NÃO confere com a senha registrada no sistema. Senão lembra a sua senha clique em <a href="/'.$_SYSTEM['ROOT'].'esqueceu-sua-senha" class="alert-close">Esqueceu Sua Senha</a>.'); return;
	}
	
	if($_REQUEST['senha'])
	if(!$_REQUEST['senha2'] || !$_REQUEST['senha3']){
		alerta('É necessário definer o campo SENHA NOVA e REDIGITE SENHA NOVA se quiser mudar sua senha. Senão quiser mudar sua senha, deixe os 3 campos de senha em branco.'); return;
	}
	
	if($_REQUEST['senha'])
	if($_REQUEST['senha2'] != $_REQUEST['senha3']){
		alerta('O campo SENHA NOVA e REDIGITE SENHA NOVA são diferentes. É necessário que ambos sejam iguais. Senão quiser mudar sua senha, deixe os 3 campos de senha em branco.'); return;
	}
	
	if($_REQUEST['senha'])
	if(strlen($_REQUEST['senha2']) < 3 || strlen($_REQUEST['senha2']) > 20){
		alerta('A sua SENHA NOVA tem que ter no mínimo 3 caracterese e no máximo 20.'); return;
	}
	
	$campos = null;
	
	$campo_nome = "id_usuario"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "nome"; $campo_valor = $usuario[$campo_nome]; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
	
	redirecionar('my-profile');
}

function b2make_my_profile_ftp_passwd(){
	global $_SYSTEM;
	
	$senha = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
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
	
	$user_arr = explode('@',$ftp_site_user);
	$_CPANEL['FTP_PASSWD'] = Array(
		'user' => $user_arr[0],
		'pass' => hashPassword($senha,$ftp_site_pass),
	);
	
	if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-passwd.php');
	
	$user_arr = explode('@',$ftp_files_user);
	$_CPANEL['FTP_PASSWD'] = Array(
		'user' => $user_arr[0],
		'pass' => hashPassword($senha,$ftp_files_pass),
	);
	
	if($_SERVER['SERVER_NAME'] != "localhost")require($_SYSTEM['SITE']['cpanel-xmlapi-path'].'b2make-xmlapi/cpanel-ftp-passwd.php');
}

function b2make_upgrade_plan(){
	global $_SYSTEM;
	global $_PROJETO;
	
	$conteudo_perfil = true;
	if($_SESSION[$_SYSTEM['ID']."permissao_id"]){
		$conteudo_perfil = false;
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'upgrade-plan';
		redirecionar('signin');
	} else {
		if($_PROJETO['upgrade-plan']){
			if($_PROJETO['upgrade-plan']['layout']){
				$layout = $_PROJETO['upgrade-plan']['layout'];
			}
		}
		
		if(!$layout){
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- upgrade-plan < -->','<!-- upgrade-plan > -->');
			
			$layout = $pagina;
		}
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'plano',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		$layout = modelo_var_troca($layout,"#plano-atual#",$resultado[0]['plano']);
		
		return $layout;
	}
}

function b2make_upgrade_plan_bd(){
	global $_SYSTEM;
	
	if(!$_REQUEST['plano']){
		alerta('Plano não definido!'); return;
	}
	
	$conteudo_perfil = true;
	if($_SESSION[$_SYSTEM['ID']."permissao_id"]){
		$conteudo_perfil = false;
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'upgrade-plan';
		redirecionar('signin');
	} else {
		$plano = $_REQUEST['plano'];
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'plano',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		$plano_atual = $resultado[0]['plano'];
		
		if((int)$plano <= (int)$plano_atual){
			alerta('Não é possível fazer DOWNGRADE da sua conta no momento!'); return;
		}
		
		banco_update
		(
			"upgrading_plan=1,".
			"plano='".$plano."'",
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		redirecionar('payment');
	}
}

function signature_cancel(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_OPCAO;
	
	$conteudo_perfil = true;
	if($_SESSION[$_SYSTEM['ID']."permissao_id"]){
		$conteudo_perfil = false;
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = $_OPCAO;
		redirecionar('signin');
	} else {
		if($_PROJETO[$_OPCAO]){
			if($_PROJETO[$_OPCAO]['layout']){
				$layout = $_PROJETO[$_OPCAO]['layout'];
			}
		}
		
		if(!$layout){
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- '.$_OPCAO.' < -->','<!-- '.$_OPCAO.' > -->');
			
			$layout = $pagina;
		}
		
		$_SESSION[$_SYSTEM['ID']."signature_cancel"] = true;
		
		return $layout;
	}
}

function signature_cancel_confirm(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_OPCAO;
	global $_PAYPAL_SANDBOX;
	global $_B2MAKE_PAGINA_LOCAL;
	
	$conteudo_perfil = true;
	if($_SESSION[$_SYSTEM['ID']."permissao_id"]){
		$conteudo_perfil = false;
	}
	
	if($conteudo_perfil){
		$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'site';
		redirecionar('signin');
	} else {
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
			
			redirecionar('signature-canceled');
		} else {
			redirecionar('signin');
		}
	}
}

function signature_canceled(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_OPCAO;
	
	if($_SESSION[$_SYSTEM['ID']."signature_canceled"]){
		$_SESSION[$_SYSTEM['ID']."signature_canceled"] = false;
		
		if($_PROJETO[$_OPCAO]){
			if($_PROJETO[$_OPCAO]['layout']){
				$layout = $_PROJETO[$_OPCAO]['layout'];
			}
		}
		
		if(!$layout){
			$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- '.$_OPCAO.' < -->','<!-- '.$_OPCAO.' > -->');
			
			$layout = $pagina;
		}
		
		$delay = $_SESSION[$_SYSTEM['ID']."delay"];
		
		session_unset();
		
		$_SESSION[$_SYSTEM['ID']."delay"] = $delay;
		
		return $layout;
	} else {
		redirecionar('signin');
	}
}

function signature_account_diskstats(){
	global $_SYSTEM;
	global $_PROJETO;
	
	$id_usuario = $_SESSION[$_SYSTEM['ID']."usuario"]["id_usuario"];
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'server',
			'user_cpanel',
			'diskchanged',
		))
		,
		"host",
		"WHERE id_usuario='".$id_usuario."'"
		." AND atual IS TRUE"
	);
	
	$diskchanged = $resultado[0]['diskchanged'];
	
	if(!$diskchanged){
		$user = $resultado[0]['user_cpanel'];
		$server = $resultado[0]['server'];
		
		$result = host_conta_sumario(Array(
			'user' => $user,
			'server' => $server,
		));
		
		if($result){
			$campo_tabela = "host";
			$campo_tabela_extra = "WHERE id_usuario='".$id_usuario."'"
								 ." AND atual IS TRUE";
			
			$campo_nome = "disklimit"; $campo_valor = $result[0]->disklimit; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			$campo_nome = "diskused"; $campo_valor = $result[0]->diskused; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			$campo_nome = "diskchanged"; $campo_valor = '1'; $editar[$campo_tabela][] = $campo_nome."=" . $campo_valor;
			
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
}

function b2make_teste_autenticar(){
	
	
}

// Funções Locais

function site_host_dicionario($host){
	global $_PALAVRAS_RESERVADAS;
	
	$vars_proibidas = $_PALAVRAS_RESERVADAS;
	
	$vars_proibidas[] = 'mail';
	$vars_proibidas[] = 'www';
	$vars_proibidas[] = 'ftp';
	$vars_proibidas[] = 'cpanel';
	$vars_proibidas[] = 'webdisk';
	$vars_proibidas[] = 'whm';
	$vars_proibidas[] = 'webmail';
	$vars_proibidas[] = 'autoconfig';
	$vars_proibidas[] = 'autodiscover';
	$vars_proibidas[] = 'cpcontacts';
	$vars_proibidas[] = 'ns1';
	$vars_proibidas[] = 'ns2';
	$vars_proibidas[] = '';
	
	foreach($vars_proibidas as $var){
		if($var == $host){
			return false;
		}
	}
	
	if(preg_match('/server/i', $host) > 0){
		if($host == 'server'){
			return false;
		}
		
		for($i=0;$i<1000;$i++){
			if($host == 'server'.$i){
				return false;
			}
		}
	}
	
	return true;
}

function ajax_help_texto(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST['id']){
		$id = $_REQUEST['id'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'titulo',
				'texto',
			))
			,
			"conteudo",
			"WHERE identificador_auxiliar='".$id."'"
			." AND status!='D'"
		);
		
		if($resultado){
			$saida = Array(
				'status' => 'Ok',
				'texto' => $resultado[0]['texto'],
				'titulo' => $resultado[0]['titulo'],
			);
		} else {
			$saida = Array(
				'status' => 'ThisHelpIdNotDefined',
			);
		}
	} else {
		$saida = Array(
			'status' => 'RequestIdNotDefined',
		);
	}
	
	return $saida;
}

function ajax_site_host(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	seguranca_delay(Array('local' => 'meu_website'));
	
	if($_REQUEST["value"]){
		$value = $_REQUEST["value"];
	
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_host',
			))
			,
			"host",
			"WHERE user_host='".$value."'"
			." AND status !='D'"
		);
		
		if(!site_host_dicionario($value)){
			$saida = Array(
				'value' => $value,
				'status' => 'JaExiste',
			);
		} else if($resultado){
			$saida = Array(
				'value' => $value,
				'status' => 'JaExiste',
			);
		} else {
			$saida = Array(
				'value' => $value,
				'status' => 'Ok',
			);
		}
	} else {
		$saida = Array(
			'status' => 'ValorNaoInformado'
		);
	}
	
	return $saida;
}

function ajax_signup_facebook_vars(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_AJAX_OUT_VARS;
	
	if($_REQUEST["modelo"]){
		$_SESSION[$_SYSTEM['ID']."signup_modelo"] = $_REQUEST["modelo"];
		$_SESSION[$_SYSTEM['ID']."signup_plano"] = $_REQUEST['plano'];
		
		$saida = Array(
			'status' => 'Ok',
		);
	} else {
		$saida = Array(
			'status' => 'ValorNaoInformado'
		);
	}
	
	return $saida;
}

function autenticar_ajax(){
	global $_OPCAO;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_AJAX_OUT_VARS;
	
	switch($_REQUEST["opcao"]){
		case 'site-host': $saida = ajax_site_host(); $b2make = true; break;
		case 'signup-bd': $saida = signup_bd(); $b2make = true; break;
		case 'signup_facebook_vars': $saida = ajax_signup_facebook_vars(); $b2make = true; break;
		case 'help-texto': $saida = ajax_help_texto(); $b2make = true; break;
	}
	
	if($b2make)return (!$_AJAX_OUT_VARS['not-json-encode'] ? json_encode($saida) : $saida);
	
	if($_OPCAO == 'minha-conta-historico'){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$id = $_REQUEST['id'];
		$id_usuario = $_SESSION[$_SYSTEM['ID']."usuario"]["id_usuario"];
		
		if($id){
			if($id_usuario != $id){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuario',
					))
					,
					'usuario',
					"WHERE id_usuario='".$id."'"
					." AND id_usuario_original='".$id_usuario."'"
				);
			} else {
				$resultado = true;
			}
			
			if($resultado){
				$_SESSION[$_SYSTEM['ID'].'historico_id'] = $id;
			}
		}
	}
	
	if($_OPCAO == 'minha-conta-usuario'){
		if($_REQUEST['usuario']){
			if($_REQUEST['usuario'] != $_REQUEST['edit_usuario']){
				banco_conectar();
				
				$resultado = banco_select
				(
					"id_usuario",
					'usuario',
					"WHERE usuario='" . $_REQUEST['usuario'] . "' AND status!='D'"
				);
				
				banco_fechar_conexao();

				if($resultado){
					$saida = "sim";
				} else {
					$saida = "nao";
				}
			} else {
				$saida = "nao";
			}
		}
	}
	
	if($_OPCAO == 'minha-conta-email'){
		if($_REQUEST['email']){
			if($_REQUEST['email'] != $_REQUEST['edit_email']){
				banco_conectar();
				
				$resultado = banco_select
				(
					"id_usuario",
					'usuario',
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
			} else {
				$saida = "nao";
			}
		}
	}
	
	return $saida;
}

function autenticar_xml(){

}

function autenticar_main(){
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
	
	if($_REQUEST['xml'])				$xml = $_REQUEST['xml'];
	if($_REQUEST['ajax'])				$ajax = $_REQUEST['ajax'];
	$opcao = $_OPCAO;
	
	if(!$xml){
		if(!$ajax){
			switch($opcao){
				case 'minha-conta-banco':	$saida = autenticar_minha_conta_base(); break;
				case 'minha-conta':	$saida = autenticar_minha_conta(); break;
				//case 'login-facebook':	$saida = autenticar_login_facebook(); break;
				case 'cadastro-validar':	$saida = autenticar_cadastro_user_validar(); break;
				case 'autenticar-cadastro':	$saida = autenticar_cadastro_user_banco(); break;
				case 'form-autenticar':	$saida = autenticar_form_autenticar(); break;
				case 'autenticar':	redirecionar('signin'); $saida = autenticar(); break;
				case 'signup':	$saida = signup(); break;
				case 'signup-facebook':	$saida = signup_facebook(); break;
				case 'signin-facebook':	$saida = signin_facebook(); break;
				case 'signin':	$saida = signin(); break;
				case 'signup-success':	$saida = b2make_cadastro_sucesso(); break;
				case 'signup-bd':	$saida = signup_bd(); break;
				case 'signin-bd':	$saida = signin_bd(); break;
				case 'pagseguro-subscription':	$saida = b2make_pagseguro_assinatura(); break;
				case 'pagseguro-notification':	$saida = b2make_pagseguro_notificacao(); break;
				case 'pagseguro-return':	$saida = b2make_pagseguro_retorno(); break;
				case 'paypal-subscription':	$saida = b2make_paypal_assinatura(); break;
				case 'paypal-notification':	$saida = b2make_paypal_notificacao(); break;
				case 'paypal-return':	$saida = b2make_paypal_retorno(); break;
				case 'paypal-cancel':	$saida = b2make_paypal_cancelamento(); break;
				case 'payment':	$saida = b2make_pagamento(); break;
				case 'payment-complete':	$saida = b2make_pagamento_completo(); break;
				case 'my-profile':	$saida = b2make_my_profile(); break;
				case 'my-profile-bd':	$saida = b2make_my_profile_bd(); break;
				case 'upgrade-plan':	$saida = b2make_upgrade_plan(); break;
				case 'upgrade-plan-bd':	$saida = b2make_upgrade_plan_bd(); break;
				case 'signature-cancel':	$saida = signature_cancel(); break;
				case 'signature-cancel-confirm':	$saida = signature_cancel_confirm(); break;
				case 'signature-canceled':	$saida = signature_canceled(); break;
				case 'b2make_teste_autenticar':	$saida = b2make_teste_autenticar(); break;
			}
			
			return $saida;
		} else {
			return autenticar_ajax();
		}
	} else {
		return autenticar_xml();
	}
}

return autenticar_main();

?>