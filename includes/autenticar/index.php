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
						if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . strip_tags(utf8_decode($_POST[$campo_nome])) . "'";}
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
			"WHERE facebook_id='" . $user_profile->identifier . "'"
		);
		
		if(!$_SESSION[$_SYSTEM['ID'].'logar-local']){
			if($_PROJETO['ecommerce']['pagina_padrao']){
				$_SESSION[$_SYSTEM['ID'].'logar-local'] = $_PROJETO['ecommerce']['pagina_padrao'];
			} else {
				$_SESSION[$_SYSTEM['ID'].'logar-local'] = 'meus-pedidos';
			}
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
			$campo_nome = "nome"; $post_nome = $campo_nome; 				if($user_profile->displayName)$campos[] = Array($campo_nome,utf8_decode($user_profile->displayName));
			$campo_nome = "endereco"; $post_nome = $campo_nome; 			if($user_profile->address)$campos[] = Array($campo_nome,utf8_decode($user_profile->address));
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
	
	if(!$alerta_sucesso2){
		if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'autenticar'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- alerta_sucesso2 < -->','<!-- alerta_sucesso2 > -->');
		
		$alerta_sucesso2 = $pagina;
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
	
	if(recaptcha_verify()){
		banco_conectar();
		
		if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
		
		$key = crypt(rand().$_REQUEST["email"]);
		$key = preg_replace('/[\$\.\/]/i', '', $key);
		
		/* if($_SESSION[$_SYSTEM['ID'].'autenticar-id_usuario_perfil']){
			$id_usuario_perfil = $_SESSION[$_SYSTEM['ID'].'autenticar-id_usuario_perfil'];
			$_SESSION[$_SYSTEM['ID'].'autenticar-id_usuario_perfil'] = false;
		} */
		
		/* if($_SESSION[$_SYSTEM['ID'].'autenticar-local']){
			$local = $_SESSION[$_SYSTEM['ID'].'autenticar-local'];
			$_SESSION[$_SYSTEM['ID'].'autenticar-local'] = false;
		} */
		
		$local = 'meus-pedidos';
		$id_usuario_perfil = $_ECOMMERCE['permissao_usuario'];
		
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

// Funções Locais

function autenticar_ajax(){
	global $_OPCAO;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
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
	
	if($_REQUEST[xml])				$xml = $_REQUEST[xml];
	if($_REQUEST[ajax])				$ajax = $_REQUEST[ajax];
	$opcao = $_OPCAO;
	
	if(!$xml){
		if(!$ajax){
			switch($opcao){
				case 'minha-conta-banco':	$saida = autenticar_minha_conta_base(); break;
				case 'minha-conta':	$saida = autenticar_minha_conta(); break;
				case 'login-facebook':	$saida = autenticar_login_facebook(); break;
				case 'cadastro-validar':	$saida = autenticar_cadastro_user_validar(); break;
				case 'autenticar-cadastro':	$saida = autenticar_cadastro_user_banco(); break;
				case 'form-autenticar':	$saida = autenticar_form_autenticar(); break;
				case 'autenticar':	$saida = autenticar(); break;
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