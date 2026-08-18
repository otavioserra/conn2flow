<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'perfil-usuario';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/perfil-usuario.json'), true);

function perfil_usuario_area_restrita(){
	global $_GESTOR;
	global $_CONFIG;

	// req-120: a tela roda dentro do painel administrativo Tailwind. Sem o bundle, o compilador
	// avisa que o layout emite `display` sob variante responsiva e a concatenação de sidecars pode
	// inverter desktop/mobile (finding F3 do review de 2026-08-15).
	$_GESTOR['tailwind-page-bundle'] = true;

	if(isset($_REQUEST['_gestor-restrict-area-atualizar'])){
		$usuario = gestor_usuario();
		
		// ===== Pegar a senha do banco de dados e comparar com a senha enviada.
		
		$senha = banco_escape_field($_REQUEST['senha']);
		$querystring = (isset($_REQUEST['_gestor-restrict-area-querystring']) ? banco_escape_field($_REQUEST['_gestor-restrict-area-querystring']) : '');
		
		$redirect = gestor_querystring_variavel($querystring,'redirect');
		$querystring = gestor_querystring_remover_variavel($querystring,'redirect');
		
		$usuarios = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios',
				'senha',
				'status',
			))
			,
			"usuarios",
			"WHERE id_usuarios='".$usuario['id_usuarios']."'"
			." AND status='A'"
		);
		
		$user_invalid = true;
		
		// ===== Rotinas de validação de usuário
		
		if($usuarios){
			$senha_hash = $usuarios[0]['senha'];
			
			if(password_verify($senha, $senha_hash)){
				$user_invalid = false;
				usuario_autorizacao_provisoria(Array('validar' => true));
				
				banco_update
				(
					"senha_incorreta_tentativas=NULL",
					"usuarios_tokens",
					"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
				);
				
				gestor_redirecionar($redirect,$querystring);
			}
		}
		
		if($user_invalid){
			$usuarios_tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					'senha_incorreta_tentativas',
				))
				,
				"usuarios_tokens",
				"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
			);
			
			if(!$usuarios_tokens[0]['senha_incorreta_tentativas']){
				$tentativas = 1;
			} else {
				$tentativas = (int)$usuarios_tokens[0]['senha_incorreta_tentativas'] + 1;
			}
			
			$maximoSenhasInvalidas = $_CONFIG['usuario-maximo-senhas-invalidas'];
			
			sleep(3);
			
			if($tentativas < $maximoSenhasInvalidas){
				$msg = modelo_var_troca_tudo(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-password-invalid')),"#tentativas#",($maximoSenhasInvalidas - $tentativas));
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $msg
				));
				
				banco_update
				(
					"senha_incorreta_tentativas='".$tentativas."'",
					"usuarios_tokens",
					"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
				);
				
				gestor_redirecionar('restrict-area/?redirect='.urlencode($redirect).(existe($querystring) ? '&'.$querystring:''));
			} else {
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-password-invalid-finish'))
				));
				
				perfil_usuario_signout();
			}
		}
	}
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Alterar dados do formulário de validação
	
	$queryString = gestor_querystring();
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-action#",$_GESTOR['url-raiz'].'restrict-area/');
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-querystring#",$queryString);
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-button-title#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-button-value#",gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));

	// ===== Validação do formulário
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
		),
	'resources' => [
		'pt-br' => [
			'layouts' => [],
			'pages' => [
			    [
			        'name' => 'Acessar Sistema',
			        'id' => 'acessar-sistema',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'signin/',
			        'type' => 'system',
			        'option' => 'signin',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '1a28df4eb15690a1a94815ac85e7d7b2',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '1a28df4eb15690a1a94815ac85e7d7b2',
			        ],
			    ],
			    [
			        'name' => 'Sair Sistema',
			        'id' => 'sair-sistema',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'signout/',
			        'type' => 'system',
			        'option' => 'signout',
			        'version' => '1.0',
			        'checksum' => [
			            'html' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => 'd41d8cd98f00b204e9800998ecf8427e',
			        ],
			    ],
			    [
			        'name' => 'Perfil Usuário',
			        'id' => 'perfil-usuario',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'perfil-usuario/',
			        'type' => 'system',
			        'option' => 'editar',
			        'root' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '409c497b763a304093568e3c7f7e417a',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '409c497b763a304093568e3c7f7e417a',
			        ],
			    ],
			    [
			        'name' => 'Área Restrita',
			        'id' => 'Area-restrita',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'restrict-area/',
			        'type' => 'system',
			        'option' => 'area-restrita',
			        'version' => '1.0',
			        'checksum' => [
			            'html' => 'b106ef13fa11754052fb9a31122c643c',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => 'b106ef13fa11754052fb9a31122c643c',
			        ],
			    ],
			    [
			        'name' => 'Validar Usuário',
			        'id' => 'validar-usuario',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'validate-user/',
			        'type' => 'system',
			        'option' => 'validar-usuario',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => 'df58b7e5c554fb44a338c46b5bc2f8a6',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => 'df58b7e5c554fb44a338c46b5bc2f8a6',
			        ],
			    ],
			    [
			        'name' => 'Esqueceu a Senha',
			        'id' => 'esqueceu-a-senha',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'forgot-password/',
			        'type' => 'system',
			        'option' => 'forgot-password',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '36252affc726b0a003349db772ffe552',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '36252affc726b0a003349db772ffe552',
			        ],
			    ],
			    [
			        'name' => 'Esqueceu a Senha Email Enviado',
			        'id' => 'esqueceu-a-senha-email-enviado',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'forgot-password-confirmation/',
			        'type' => 'system',
			        'option' => 'forgot-password-confirmation',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '100a56701ff0b6d915e3cf9e3f2791c8',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '100a56701ff0b6d915e3cf9e3f2791c8',
			        ],
			    ],
			    [
			        'name' => 'Redefinir Senha',
			        'id' => 'redefinir-senha',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'redefine-password/',
			        'type' => 'system',
			        'option' => 'redefine-password',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '920fbd11ee6d54345028e33ac3d7599c',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '920fbd11ee6d54345028e33ac3d7599c',
			        ],
			    ],
			    [
			        'name' => 'Redefinir Senha Confirmação',
			        'id' => 'redefinir-senha-confirmacao',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'redefine-password-confirmation/',
			        'type' => 'system',
			        'option' => 'redefine-password-confirmation',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '1e674e0f0326986bb2b77ec4130490ae',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '1e674e0f0326986bb2b77ec4130490ae',
			        ],
			    ],
			    [
			        'name' => 'Cadastrar no Sistema',
			        'id' => 'cadastrar-no-sistema',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'signup/',
			        'type' => 'system',
			        'option' => 'signup',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '62ae3b0260aed09055dbd0cd3ff7f33e',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '62ae3b0260aed09055dbd0cd3ff7f33e',
			        ],
			    ],
			    [
			        'name' => 'Confirmação de Email',
			        'id' => 'confirmacao-de-email',
			        'layout' => 'layout-pagina-sem-permissao',
			        'path' => 'email-confirmation/',
			        'type' => 'system',
			        'option' => 'confirmacao-email',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '589314427de8eec6b99dcffb571ce7cb',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '589314427de8eec6b99dcffb571ce7cb',
			        ],
			    ],
			],
			'components' => [],
		],
	],
	);
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'id_usuarios_perfis',
		'nome_conta',
		'nome',
		'email',
		'usuario',
		'primeiro_nome',
		'ultimo_nome',
		'nome_do_meio',
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;
	
	// ===== Mudar Campos parâmetros iniciais
	
	$mudarCampos = Array('nome','email','usuario','senha');
	$mudarCampo = false;
	$mudarCampoBanco = false;
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Verificar qual campo está sendo mudado
		
		foreach($mudarCampos as $mc){
			if(isset($_REQUEST['mudar-'.$mc.'-banco'])){
				$mudarCampoBanco = $mc;
			}
		}
		
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios
		
		if($mudarCampoBanco){
			if(!usuario_autorizacao_provisoria(Array(
				'verificar' => true,
			))){
				$alerta = gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-authorization-provisory-not-defined'));
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
				
				gestor_redirecionar_raiz();
			}
		}
		
		// ===== Validação de campos obrigatórios
		
		switch($mudarCampoBanco){
			case 'nome':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-name-label')),
				);
			break;
			case 'email':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
				);
			break;
			case 'usuario':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'usuario',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-user-label')),
				);
			break;
			case 'senha':
				$campos_obrigatorios['campos'][] = Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
					'min' => 12,
				);
			break;
		}
		
		interface_validacao_campos_obrigatorios($campos_obrigatorios);
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		switch($mudarCampoBanco){
			case 'usuario':
				$exiteUsuario = interface_verificar_campos(Array(
					'campo' => 'usuario',
					'valor' => banco_escape_field($_REQUEST['usuario']),
				));
				
				if($exiteUsuario){
					$alerta = gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-there-is-a-field'));
					$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-user-label')));
					$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['usuario']));
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
				}
			break;
			case 'email':
				$exiteEmail = interface_verificar_campos(Array(
					'campo' => 'email',
					'valor' => banco_escape_field($_REQUEST['email']),
				));
				
				if($exiteEmail){
					$alerta = gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-there-is-a-field'));
					$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')));
					$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['email']));
					
					interface_alerta(Array(
						'redirect' => true,
						'msg' => $alerta
					));
					
					gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
				}
			break;
		}
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'",
		);
		
		switch($mudarCampoBanco){
			case 'nome':
				$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
			break;
		}
		
		// ===== Gerar hash da senha
		
		switch($mudarCampoBanco){
			case 'senha':
				$senha = banco_escape_field($_REQUEST['senha']);
				
				$senhaHash = password_hash($senha, PASSWORD_ARGON2ID, [
					'memory_cost' => 65536,
					'time_cost' => 4,
					'threads' => 2,
				]);
			break;
		}
		
		// ===== Separar os nomes (primeiro, do meio e último)
		
		switch($mudarCampoBanco){
			case 'nome':
				$nome = banco_escape_field($_REQUEST['nome']);
				
				$nomes = explode(' ',$nome);
				
				if(count($nomes) > 2){
					for($i=0;$i<count($nomes);$i++){
						if($i==0){
							$primeiro_nome = $nomes[$i];
						} else if($i==count($nomes) - 1){
							$ultimo_nome = $nomes[$i];
						} else {
							$nome_do_meio .= (isset($nome_do_meio) ? ' ':'') . $nomes[$i];
						}
					}
				} else if(count($nomes) > 1){
					$primeiro_nome = $nomes[0];
					$ultimo_nome = $nomes[1];
				} else {
					$primeiro_nome = $nomes[0];
				}
			break;
		}
		
		// ===== Atualização dos demais campos.
		
		switch($mudarCampoBanco){
			case 'nome':
				$campo_nome = "primeiro_nome"; $request_name = $campo_nome; $alteracoes_name = 'first-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
				$campo_nome = "ultimo_nome"; $request_name = $campo_nome; $alteracoes_name = 'last-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
				$campo_nome = "nome_do_meio"; $request_name = $campo_nome; $alteracoes_name = 'middle-name'; if(banco_select_campos_antes($campo_nome) != (isset($$campo_nome) ? $$campo_nome : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($$campo_nome) ? "'".$$campo_nome."'" : 'NULL');}
			break;
			case 'email':
				$campo_nome = "email"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
			break;
			case 'usuario':
				$campo_nome = "usuario"; $request_name = $campo_nome; $alteracoes_name = 'user'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
			break;
			case 'senha':
				$campo_nome = "senha"; $alteracoes_name = 'password'; $editar['dados'][] = $campo_nome."='" . $senhaHash . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');
			break;
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
			$campo_nome = $modulo['tabela']['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = $modulo['tabela']['data_modificacao']; $editar['dados'][] = $campo_nome."=NOW()";
			
			$editar['sql'] = banco_campos_virgulas($editar['dados']);
			
			if($editar['sql']){
				banco_update
				(
					$editar['sql'],
					$editar['tabela'],
					$editar['extra']
				);
			}
			$editar = false;
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
			
			// ===== Pegar dados do usuário token atual.
			
			$tokenPubId = $_GESTOR['usuario-token-id'];
			
			$usuarios_tokens = banco_select_name
			(
				banco_campos_virgulas(Array(
					//'fingerprint',
					'expiration',
				))
				,
				"usuarios_tokens",
				"WHERE pubID='".$tokenPubId."'"
			);
			
			//$fingerprint = $usuarios_tokens[0]['fingerprint'];
			$expiration = $usuarios_tokens[0]['expiration'];
			
			// ===== Remover todos os usuários tokens do usuário atual.
			
			$usuarioAtual = gestor_usuario();
			$id_usuarios = $usuarioAtual['id_usuarios'];
			
			banco_delete
			(
				"usuarios_tokens",
				"WHERE id_usuarios='".$id_usuarios."'"
			);
			
			// ===== Renovar o usuário token deste usuário.
			
			if((int)$expiration > 0){
				usuario_gerar_token_autorizacao(Array(
					'id_usuarios' => $id_usuarios,
					//'fingerprint' => $fingerprint,
				));
			} else {
				usuario_gerar_token_autorizacao(Array(
					'id_usuarios' => $id_usuarios,
					//'fingerprint' => $fingerprint,
					'sessao' => true,
				));
			}
			
			// ===== Habilitar o browser fingerprint.
			
			//gestor_sessao_variavel('browser-fingerprint',true);
		}
		
		// ===== Invalidar a autorização provisória
		
		usuario_autorizacao_provisoria(Array('invalidar' => true));
		
		// ===== Reler URL.
		
		gestor_reload_url();
	}
	
	// ===== Inclusão Módulo JS

	gestor_pagina_javascript_incluir();

	// ===== Bundle canônico do Tailwind (req-118)
	//
	// O layout emite `display` sob variante responsiva (`lg:block`, `lg:hidden`, `sm:inline`) e o
	// compilador avisa que concatenar sidecars nesse cenário pode inverter desktop/mobile — é o
	// finding F3 do review de 2026-08-15. Com o bundle, layout, componentes e página compilam num
	// CSS só e a ordem global das utilities fica preservada.

	$_GESTOR['tailwind-page-bundle'] = true;

	// ===== Selecionar dados do banco de dados

	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
	);
	
	if($_GESTOR['banco-resultado']){
		$id_usuarios_perfis = (isset($retorno_bd['id_usuarios_perfis']) ? $retorno_bd['id_usuarios_perfis'] : '');
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$nome_conta = (isset($retorno_bd['nome_conta']) ? $retorno_bd['nome_conta'] : '');
		$email = (isset($retorno_bd['email']) ? $retorno_bd['email'] : '');
		$usuario = (isset($retorno_bd['usuario']) ? $retorno_bd['usuario'] : '');
		$primeiro_nome = (isset($retorno_bd['primeiro_nome']) ? $retorno_bd['primeiro_nome'] : '');
		$ultimo_nome = (isset($retorno_bd['ultimo_nome']) ? $retorno_bd['ultimo_nome'] : '');
		$nome_do_meio = (isset($retorno_bd['nome_do_meio']) ? $retorno_bd['nome_do_meio'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#user-profile#',interface_formatar_dado(Array(
			'dado' => $id_usuarios_perfis,
			'formato' => Array(
				'id' => 'outraTabela',
				'tabela' => Array(
					'nome' => 'usuarios_perfis',
					'campo_trocar' => 'nome',
					'campo_referencia' => 'id_usuarios_perfis',
				),
			),
		)));
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome_conta#',$nome_conta);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#email#',$email);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#usuario#',$usuario);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#primeiro_nome#',$primeiro_nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#ultimo_nome#',$ultimo_nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome_do_meio#',$nome_do_meio);
		
		// ===== Mudar ou não a senha
		
		foreach($mudarCampos as $mc){
			if(isset($_REQUEST['mudar-'.$mc])){
				$mudarCampo = $mc;
			} else {
				$cel_nome = $mc.'-campos'; $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			}
		}

		// ===== Aba Segurança (req-030): 2FA e contas sociais

		perfil_usuario_editar_seguranca();

		// ===== Aba Sessões & Dispositivos (req-118)

		perfil_usuario_editar_sessoes();

		// ===== Aba Chaves de API (req-119)

		perfil_usuario_editar_api_tokens();

		// A aba só aparece para quem pode usar a API; sem isso o usuário veria uma aba que não
		// mostra nada e não explica por quê.
		if(empty($_GESTOR['perfil-api-tokens-visivel'])){
			$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- aba-api-tokens < -->','<!-- aba-api-tokens > -->');
		} else {
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'<!-- aba-api-tokens < -->','');
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'<!-- aba-api-tokens > -->','');
		}

		// ===== Dicionário do runtime do painel (req-118)
		//
		// Só os textos que o JS escreve sozinho (força de senha e confirmações de revogação); tudo
		// que o PHP já renderiza continua vindo pronto no HTML.

		$_GESTOR['javascript-vars']['perfilUsuario'] = Array(
			'password-strength-empty' => perfil_usuario_seguranca_var('password-strength-empty'),
			'password-strength-weak' => perfil_usuario_seguranca_var('password-strength-weak'),
			'password-strength-fair' => perfil_usuario_seguranca_var('password-strength-fair'),
			'password-strength-good' => perfil_usuario_seguranca_var('password-strength-good'),
			'password-strength-strong' => perfil_usuario_seguranca_var('password-strength-strong'),
			'sessions-revoke-confirm' => perfil_usuario_seguranca_var('sessions-revoke-confirm'),
			'sessions-revoke-others-confirm' => perfil_usuario_seguranca_var('sessions-revoke-others-confirm'),
			'sessions-revoke-error' => perfil_usuario_seguranca_var('sessions-revoke-error'),
			'api-tokens-name-required' => perfil_usuario_seguranca_var('api-tokens-name-required'),
			'api-tokens-create-error' => perfil_usuario_seguranca_var('api-tokens-create-error'),
			'api-tokens-revoke-confirm' => perfil_usuario_seguranca_var('api-tokens-revoke-confirm'),
			'api-tokens-revoke-error' => perfil_usuario_seguranca_var('api-tokens-revoke-error'),
			'api-tokens-copied' => perfil_usuario_seguranca_var('api-tokens-copied'),
			'api-tokens-status-revogado' => perfil_usuario_seguranca_var('api-tokens-status-revogado'),
			'recovery-codes-title' => perfil_usuario_seguranca_var('recovery-codes-title'),
			'recovery-codes-help' => perfil_usuario_seguranca_var('recovery-codes-help'),
		);

		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		// req-120: o painel roda em Tailwind puro — a etiqueta de status precisa sair no vocabulário
		// dele, senão vira texto sem estilo dentro da tabela de metadados.
		if(isset($retorno_bd[$modulo['tabela']['status']])){
			$statusAtual = $retorno_bd[$modulo['tabela']['status']];
			$statusHtml = '';

			if($statusAtual == 'A'){
				$statusHtml = perfil_usuario_etiqueta_status('field-status-active','bg-emerald-50 text-emerald-700');
			} else if($statusAtual == 'I'){
				$statusHtml = perfil_usuario_etiqueta_status('field-status-inactive','bg-amber-50 text-amber-700');
			}

			$metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => $statusHtml);
		}
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Gestor adicionar variáveis globais de outro módulo.
	
	gestor_pagina_variaveis_modulos(Array(
		'modulosExtra' => Array(
			'usuarios',
		),
	));
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'removerNaoAlterarId' => true,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		)
	);
	
	// ===== Remover botão editar senão precisar e obrigar fornecer senha
	
	if(!$mudarCampo){
		$_GESTOR['interface']['editar']['finalizar']['removerBotaoEditar'] = true;
	} else {
		usuario_autorizacao_provisoria(Array(
			'verificarModal' => Array(
				'cancelarUrl' => 'perfil-usuario/',
				'autorizadoUrl' => 'perfil-usuario/',
				'autorizadoUrlQuerystring' => 'mudar-'.$mudarCampo.'=sim',
			),
		));
	}
	
	// ===== Formulário validação conforme necessidade.
	
	switch($mudarCampo){
		case 'nome':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'nome',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-name-label')),
			);
		break;
		case 'usuario':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'texto-obrigatorio-verificar-campo',
				'campo' => 'usuario',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-user-label')),
				'regrasExtra' => Array(
					Array(
						'regra' => 'regexPermited',
						'regex' => '/^[a-z][a-z0-9]+(\.[a-z0-9]{2,})*([@]?([a-z0-9]{2,}\.)*[a-z0-9]{2,})*$/gi',
						'regexPermitedChars' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'user-permited-chars')),
					)
				),
			);
		break;
		case 'email':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'email-comparacao-verificar-campo',
				'campo' => 'email',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
				'identificador' => 'email',
				'comparcao' => Array(
					'id' => 'email-2',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-2-label')),
				)
			);
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'email-comparacao',
				'campo' => 'email-2',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-2-label')),
				'identificador' => 'email-2',
				'comparcao' => Array(
					'id' => 'email',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-email-2-label')),
				)
			);
		break;
		case 'senha':
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'senha-comparacao',
				'campo' => 'senha',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
				'identificador' => 'senha',
				'comparcao' => Array(
					'id' => 'senha-2',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-2-label')),
				)
			);
			$_GESTOR['interface']['editar']['finalizar']['formulario']['validacao'][] = Array(
				'regra' => 'senha-comparacao',
				'campo' => 'senha-2',
				'label' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-2-label')),
				'identificador' => 'senha-2',
				'comparcao' => Array(
					'id' => 'senha',
					'campo-1' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-label')),
					'campo-2' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'form-password-2-label')),
				)
			);
		break;
	}
}

// ===== Rota de Segurança (req-030) =====

/**
 * Atalho para variáveis de idioma do módulo perfil-usuario.
 */
function perfil_usuario_seguranca_var($id){
	global $_GESTOR;
	return gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => $id));
}

// ===== Blocos de interface do módulo (req-118/119/120) =====
//
// NENHUM HTML é escrito aqui. Todo markup vive no Sistema de Recursos, em
// `resources/<lang>/components/perfil-usuario-*`, e as classes utilitárias vivem nas VARIÁVEIS do
// sistema (`perfil-usuario.json`), resolvidas como `@[[classe-…]]@` dentro do próprio componente.
// Isso mantém o visual editável por instalação (componente e variável são dados de banco) e mantém
// o compilador Tailwind enxergando tudo — o JSON do módulo é declarado em `tailwind_sources`.
//
// O papel do PHP passou a ser só decidir QUAIS blocos entram e com que valores.

/**
 * Carrega um componente do próprio módulo.
 *
 * @param string $id Id do componente.
 *
 * @return string HTML do componente, ou vazio quando ele ainda não foi sincronizado.
 */
function perfil_usuario_componente($id){
	global $_GESTOR;

	$html = gestor_componente(Array(
		'id' => $id,
		'modulo' => $_GESTOR['modulo-id'],
	));

	return is_string($html) ? $html : '';
}

/**
 * Extrai um bloco nomeado de um componente.
 *
 * O componente guarda TODAS as variantes de uma tela e o PHP escolhe qual usar. Extrair em vez de
 * concatenar é o que permite ao operador editar qualquer variante sem tocar em código.
 *
 * @param string $html HTML do componente.
 * @param string $bloco Nome do bloco delimitado por `<!-- nome < -->` e `<!-- nome > -->`.
 *
 * @return string Conteúdo do bloco.
 */
function perfil_usuario_bloco($html,$bloco){
	return modelo_tag_val($html,'<!-- '.$bloco.' < -->','<!-- '.$bloco.' > -->');
}

/** Remove um bloco nomeado, deixando o restante intacto. */
function perfil_usuario_bloco_remover($html,$bloco){
	return modelo_tag_in($html,'<!-- '.$bloco.' < -->','<!-- '.$bloco.' > -->','');
}

/** Mantém um bloco nomeado, removendo apenas os marcadores. */
function perfil_usuario_bloco_manter($html,$bloco){
	return modelo_tag_in($html,'<!-- '.$bloco.' < -->','<!-- '.$bloco.' > -->',perfil_usuario_bloco($html,$bloco));
}

/**
 * Monta o alternador entre login por senha e login por código de e-mail (req-120).
 *
 * O contrato com o JS não mudou: `.login-method-toggle`, `data-method` e a classe `active` continuam
 * sendo o que o `perfil-usuario.js` lê para trocar de modo.
 */
function perfil_usuario_login_method_switch($labelSenha,$labelEmail){
	$bloco = perfil_usuario_bloco(perfil_usuario_componente('perfil-usuario-login-metodos'),'switch');

	$bloco = modelo_var_troca($bloco,'#label-senha#',$labelSenha);
	$bloco = modelo_var_troca($bloco,'#label-email#',$labelEmail);

	return $bloco;
}

/**
 * Monta o bloco de login social (req-120).
 *
 * O ícone é uma letra, não uma webfont: as telas públicas não carregam o Fomantic e puxar uma folha
 * de ícones inteira para dois glifos custaria mais que o botão.
 *
 * @param array $provedores Mapa `provider => Array(sigla, rótulo)`.
 */
function perfil_usuario_login_social($provedores){
	global $_GESTOR;

	if(!$provedores) return '';

	$componente = perfil_usuario_componente('perfil-usuario-login-metodos');
	$conteiner = perfil_usuario_bloco($componente,'social-conteiner');
	$modelo = perfil_usuario_bloco($componente,'social-botao');

	foreach($provedores as $provider => $dados){
		$botao = $modelo;
		$botao = modelo_var_troca($botao,'#url#',$_GESTOR['url-raiz'].'social-login/?provider='.$provider);
		$botao = modelo_var_troca($botao,'#cor#',($provider === 'google' ? 'bg-rose-600' : 'bg-sky-700'));
		$botao = modelo_var_troca($botao,'#sigla#',$dados[0]);
		$botao = modelo_var_troca($botao,'#rotulo#',$dados[1]);

		$conteiner = modelo_var_in($conteiner,'<!-- social-botao -->',$botao);
	}

	return modelo_var_troca($conteiner,'<!-- social-botao -->','');
}

/**
 * Monta a etiqueta de status exibida nos metadados do perfil (req-120).
 *
 * Usa o mesmo bloco de etiqueta dos demais painéis, para o status não destoar do resto da tela.
 *
 * @param string $variavelInterface Id da variável de idioma no módulo `interface`.
 * @param string $cor Classes de cor da etiqueta.
 */
function perfil_usuario_etiqueta_status($variavelInterface,$cor){
	$etiqueta = perfil_usuario_bloco(perfil_usuario_componente('perfil-usuario-sessoes'),'etiqueta');

	$etiqueta = modelo_var_troca($etiqueta,'#cor#',$cor);
	$etiqueta = modelo_var_troca($etiqueta,'#texto#',gestor_variaveis(Array('modulo' => 'interface','id' => $variavelInterface)));

	return $etiqueta;
}

/** Devolve um bloco do componente de campos de 2FA das telas públicas (req-120). */
function perfil_usuario_2fa_bloco($bloco){
	return perfil_usuario_bloco(perfil_usuario_componente('perfil-usuario-2fa-campos'),$bloco);
}

/** Campo de código das telas de 2FA. */
function perfil_usuario_2fa_campo_codigo(){
	return perfil_usuario_2fa_bloco('campo-codigo');
}

/** Botão de reenvio do código por e-mail. */
function perfil_usuario_2fa_reenviar_email($rota){
	global $_GESTOR;

	return modelo_var_troca(perfil_usuario_2fa_bloco('reenviar-email'),'#url#',$_GESTOR['url-raiz'].$rota.'?enviar-email=sim');
}

/** Parágrafo de ajuda das telas de 2FA. */
function perfil_usuario_2fa_ajuda($texto){
	return modelo_var_troca(perfil_usuario_2fa_bloco('ajuda'),'#texto#',$texto);
}

/**
 * Monta as opções de método de 2FA a partir do bloco de opção do componente informado.
 *
 * @param string $componente Id do componente que traz o bloco de opção.
 * @param string $blocoOpcao Nome do bloco de uma opção.
 * @param bool $metodoApp Método por aplicativo habilitado.
 * @param bool $metodoEmail Método por e-mail habilitado.
 */
function perfil_usuario_2fa_opcoes($componente,$blocoOpcao,$metodoApp,$metodoEmail){
	$modelo = perfil_usuario_bloco(perfil_usuario_componente($componente),$blocoOpcao);
	$opcoes = '';

	if($metodoApp){
		$opcao = modelo_var_troca($modelo,'#valor#','app');
		$opcoes .= modelo_var_troca($opcao,'#rotulo#',perfil_usuario_seguranca_var('security-2fa-app-option'));
	}

	if($metodoEmail){
		$opcao = modelo_var_troca($modelo,'#valor#','email');
		$opcoes .= modelo_var_troca($opcao,'#rotulo#',perfil_usuario_seguranca_var('security-2fa-email-option'));
	}

	return $opcoes;
}

/**
 * Renderiza a seção de Segurança (2FA + contas sociais) no bloco `seguranca-campos`.
 *
 * A aba passa a existir SEMPRE (req-118), mas o QR Code e a chave manual continuam materializados
 * apenas sob `?configurar-seguranca=sim`: exibir o segredo TOTP em toda carga do perfil ampliaria a
 * superfície aprovada no req-030 sem que ninguém tivesse pedido.
 */
function perfil_usuario_editar_seguranca(){
	global $_GESTOR;

	$bloco = 'seguranca-campos';

	gestor_incluir_biblioteca('2fa');

	$configurando = (isset($_REQUEST['configurar-seguranca']) && $_REQUEST['configurar-seguranca'] === 'sim');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$email = isset($usuario['email']) ? $usuario['email'] : '';

	$dados2fa = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('two_factor_enabled', 'two_factor_type', 'two_factor_secret'),
		'extra' => "WHERE id_usuarios='".$id_usuarios."'",
	));

	$ativo = $dados2fa && !empty($dados2fa['two_factor_enabled']);
	$tipo = ($dados2fa && isset($dados2fa['two_factor_type'])) ? $dados2fa['two_factor_type'] : '';

	$metodoApp = (($_ENV['AUTH_2FA_METHOD_APP'] ?? 'true') === 'true');
	$metodoEmail = (($_ENV['AUTH_2FA_METHOD_EMAIL'] ?? 'true') === 'true');
	$googleAtivo = (($_ENV['AUTH_METHOD_GOOGLE_ACTIVE'] ?? 'false') === 'true');
	$metaAtivo = (($_ENV['AUTH_METHOD_META_ACTIVE'] ?? 'false') === 'true');

	$componente = perfil_usuario_componente('perfil-usuario-seguranca');
	$qrCodeNecessario = false;

	// ===== Bloco 2FA

	if($ativo){
		$bloco2fa = modelo_var_troca(perfil_usuario_bloco($componente,'2fa-ativo'),'#tipo#',htmlspecialchars(strtoupper((string)$tipo)));
	} else if(!$metodoApp && !$metodoEmail){
		$bloco2fa = perfil_usuario_bloco($componente,'2fa-sem-metodo');
	} else if(!$configurando){
		$bloco2fa = modelo_var_troca(
			perfil_usuario_bloco($componente,'2fa-inativo'),
			'#url-configurar#',
			$_GESTOR['url-raiz'].'perfil-usuario/?configurar-seguranca=sim#seguranca'
		);
	} else {
		// Persistir um secret (enabled=0) para o método app, reutilizado entre recargas.
		$secret = ($dados2fa && !empty($dados2fa['two_factor_secret'])) ? $dados2fa['two_factor_secret'] : '';

		if($metodoApp && $secret === ''){
			$secret = two_factor_generate_secret();
			banco_update_campo('two_factor_secret', $secret);
			banco_update_executar('usuarios', "WHERE id_usuarios='".$id_usuarios."'");
		}

		$bloco2fa = perfil_usuario_bloco($componente,'2fa-configurar');
		$bloco2fa = modelo_var_troca($bloco2fa,'#opcoes#',perfil_usuario_2fa_opcoes('perfil-usuario-seguranca','2fa-metodo-opcao',$metodoApp,$metodoEmail));

		if($metodoApp){
			$qrCodeNecessario = true;
			$bloco2fa = perfil_usuario_bloco_manter($bloco2fa,'app');
			$bloco2fa = modelo_var_troca($bloco2fa,'#otpauth#',htmlspecialchars(two_factor_get_qr_code($email,$secret), ENT_QUOTES));
			$bloco2fa = modelo_var_troca($bloco2fa,'#secret#',htmlspecialchars((string)$secret));
		} else {
			$bloco2fa = perfil_usuario_bloco_remover($bloco2fa,'app');
		}

		$bloco2fa = $metodoEmail
			? perfil_usuario_bloco_manter($bloco2fa,'email')
			: perfil_usuario_bloco_remover($bloco2fa,'email');
	}

	// ===== Bloco de contas sociais

	if(!$googleAtivo && !$metaAtivo){
		$blocoSocial = perfil_usuario_bloco($componente,'social-vazio');
	} else {
		$vinculos = banco_select(Array(
			'tabela' => 'usuarios_provedores',
			'campos' => Array('provider_name', 'provider_uid'),
			'extra' => "WHERE usuario_id='".$id_usuarios."'",
		));

		$mapa = Array();
		if(is_array($vinculos)){
			foreach($vinculos as $v){ if(isset($v['provider_name'])) $mapa[$v['provider_name']] = isset($v['provider_uid']) ? $v['provider_uid'] : ''; }
		}

		$provedores = Array();
		if($googleAtivo) $provedores['google'] = 'Google';
		if($metaAtivo) $provedores['meta'] = 'Meta';

		$blocoSocial = perfil_usuario_bloco($componente,'social-lista');
		$modeloItem = perfil_usuario_bloco($componente,'social-item');

		foreach($provedores as $pid => $pnome){
			if(isset($mapa[$pid])){
				$acao = modelo_var_troca(perfil_usuario_bloco($componente,'social-vinculado'),'#uid#',htmlspecialchars((string)$mapa[$pid]));
			} else {
				$acao = perfil_usuario_bloco($componente,'social-desvinculado');
			}

			$item = modelo_var_troca($modeloItem,'#acao#',$acao);
			$item = modelo_var_troca_tudo($item,'#provedor-nome#',$pnome);
			$item = modelo_var_troca_tudo($item,'#provedor#',$pid);

			$blocoSocial = modelo_var_in($blocoSocial,'<!-- social-item -->',$item);
		}

		$blocoSocial = modelo_var_troca($blocoSocial,'<!-- social-item -->','');
	}

	$html = perfil_usuario_bloco($componente,'conteiner');
	$html = modelo_var_troca($html,'#bloco-2fa#',$bloco2fa);
	$html = modelo_var_troca($html,'#bloco-social#',$blocoSocial);

	$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- '.$bloco.' < -->', '<!-- '.$bloco.' > -->', $html);

	// QR Code renderizado no cliente (o secret não é enviado a terceiros). A biblioteca externa só
	// entra na página que de fato desenha o QR.
	if($qrCodeNecessario){
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>');
	}
}

/**
 * Renderiza a seção "Chaves de API" no bloco `api-tokens-campos` da página de perfil (req-119).
 *
 * A aba só existe para perfis autorizados a usar a API: emitir token para quem não pode consumi-la
 * entregaria uma credencial inútil e daria a impressão de que a política foi contornada.
 */
function perfil_usuario_editar_api_tokens(){
	global $_GESTOR;

	$bloco = 'api-tokens-campos';

	gestor_incluir_biblioteca('usuario');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;

	// O gate de schema entra junto do de permissão: enquanto a migração não tiver rodado nesta
	// instalação, a aba inteira não existe — em vez de erro na primeira consulta (BATCH-122).
	if(!$id_usuarios || !perfil_usuario_api_tokens_habilitado($usuario) || !usuario_api_tokens_disponivel()){
		$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- '.$bloco.' < -->', '<!-- '.$bloco.' > -->', '');
		$_GESTOR['perfil-api-tokens-visivel'] = false;
		return;
	}

	$_GESTOR['perfil-api-tokens-visivel'] = true;

	$componente = perfil_usuario_componente('perfil-usuario-api-tokens');
	$tokens = usuario_api_tokens_listar($id_usuarios);
	$nunca = perfil_usuario_seguranca_var('api-tokens-never-used');

	$html = perfil_usuario_bloco($componente,'conteiner');

	// ===== Escopos oferecidos na criação

	$modeloEscopo = perfil_usuario_bloco($componente,'escopo');

	foreach(Array('read','write','deploy') as $escopo){
		$item = modelo_var_troca($modeloEscopo,'#valor#',$escopo);
		$item = modelo_var_troca($item,'#rotulo#',perfil_usuario_seguranca_var('api-tokens-scope-'.$escopo));
		$item = modelo_var_troca($item,'#checked#',($escopo === 'read' ? 'checked' : ''));

		$html = modelo_var_in($html,'<!-- escopo -->',$item);
	}

	$html = modelo_var_troca($html,'<!-- escopo -->','');

	// ===== Listagem

	if(!$tokens){
		$html = perfil_usuario_bloco_remover($html,'tabela');
		$html = perfil_usuario_bloco_manter($html,'vazio');
	} else {
		$html = perfil_usuario_bloco_remover($html,'vazio');
		$html = perfil_usuario_bloco_manter($html,'tabela');

		$modeloLinha = perfil_usuario_bloco($componente,'linha');

		foreach($tokens as $token){
			$ativo = ($token['situacao'] === 'ativo');
			$cor = $ativo
				? 'bg-emerald-50 text-emerald-700'
				: ($token['situacao'] === 'expirado' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600');

			$linha = $modeloLinha;

			// Token já revogado ou expirado não tem o que revogar — o botão só existiria para falhar.
			$linha = $ativo
				? perfil_usuario_bloco_manter($linha,'revogar')
				: perfil_usuario_bloco_remover($linha,'revogar');

			$linha = modelo_var_troca($linha,'#nome#',htmlspecialchars((string)$token['nome']));
			$linha = modelo_var_troca($linha,'#prefixo#',htmlspecialchars((string)$token['token_prefix']));
			$linha = modelo_var_troca($linha,'#criacao#',htmlspecialchars(interface_formatar_dado(Array('dado' => $token['data_criacao'],'formato' => 'dataHora'))));
			$linha = modelo_var_troca($linha,'#ultimo-uso#',htmlspecialchars(!empty($token['ultimo_uso']) ? interface_formatar_dado(Array('dado' => $token['ultimo_uso'],'formato' => 'dataHora')) : $nunca));
			$linha = modelo_var_troca($linha,'#cor-situacao#',$cor);
			$linha = modelo_var_troca($linha,'#situacao#',perfil_usuario_seguranca_var('api-tokens-status-'.$token['situacao']));
			$linha = modelo_var_troca_tudo($linha,'#id#',(string)(int)$token['id_usuarios_api_tokens']);

			$html = modelo_var_in($html,'<!-- linha -->',$linha);
		}

		$html = modelo_var_troca($html,'<!-- linha -->','');
	}

	$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- '.$bloco.' < -->', '<!-- '.$bloco.' > -->', $html);
}

/**
 * Renderiza a seção "Sessões & Dispositivos" no bloco `sessoes-campos` da página de perfil.
 *
 * A sessão da requisição corrente recebe a etiqueta "Este dispositivo" e não tem botão de revogar:
 * revogar a própria sessão pelo painel derrubaria o usuário no meio da operação — para isso existe
 * o "Sair" do menu.
 */
function perfil_usuario_editar_sessoes(){
	global $_GESTOR;

	$bloco = 'sessoes-campos';

	gestor_incluir_biblioteca('usuario');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$tokenAtual = isset($_GESTOR['usuario-token-id']) ? $_GESTOR['usuario-token-id'] : null;

	$sessoes = usuario_sessoes_listar($id_usuarios,$tokenAtual);
	$desconhecido = perfil_usuario_seguranca_var('sessions-unknown');

	$componente = perfil_usuario_componente('perfil-usuario-sessoes');
	$html = perfil_usuario_bloco($componente,'conteiner');

	$outras = 0;
	foreach($sessoes as $sessao){ if(!$sessao['atual']) $outras++; }

	$html = ($outras > 0)
		? perfil_usuario_bloco_manter($html,'revogar-outras')
		: perfil_usuario_bloco_remover($html,'revogar-outras');

	if(!$sessoes){
		$html = perfil_usuario_bloco_remover($html,'lista');
		$html = perfil_usuario_bloco_manter($html,'vazio');
	} else {
		$html = perfil_usuario_bloco_remover($html,'vazio');
		$html = perfil_usuario_bloco_manter($html,'lista');

		$modeloCartao = perfil_usuario_bloco($componente,'cartao');

		foreach($sessoes as $sessao){
			$navegador = $sessao['navegador'] !== '' ? $sessao['navegador'] : $desconhecido;
			$sistema = $sessao['sistema'] !== '' ? $sessao['sistema'] : $desconhecido;
			$icone = ($sessao['dispositivo'] === 'mobile' ? '📱' : ($sessao['dispositivo'] === 'tablet' ? '📲' : '🖥️'));

			$titulo = modelo_var_troca_tudo(
				modelo_var_troca_tudo(perfil_usuario_seguranca_var('sessions-device-title'),'#browser#',$navegador),
				'#system#',
				$sistema
			);

			$cartao = $modeloCartao;

			$cartao = $sessao['atual']
				? perfil_usuario_bloco_manter($cartao,'atual')
				: perfil_usuario_bloco_remover($cartao,'atual');

			// A sessão atual não ganha botão de revogar: derrubaria o usuário no meio da operação.
			$cartao = $sessao['atual']
				? perfil_usuario_bloco_remover($cartao,'revogar')
				: perfil_usuario_bloco_manter($cartao,'revogar');

			$cartao = ($sessao['origem'] !== '')
				? modelo_var_troca(perfil_usuario_bloco_manter($cartao,'origem'),'#origem#',htmlspecialchars($sessao['origem']))
				: perfil_usuario_bloco_remover($cartao,'origem');

			$cartao = modelo_var_troca($cartao,'#destaque#',($sessao['atual'] ? ' ring-2 ring-emerald-500' : ''));
			$cartao = modelo_var_troca($cartao,'#icone#',$icone);
			$cartao = modelo_var_troca($cartao,'#titulo#',htmlspecialchars($titulo));
			$cartao = modelo_var_troca($cartao,'#ip#',htmlspecialchars($sessao['ip'] !== '' ? $sessao['ip'] : $desconhecido));
			$cartao = modelo_var_troca($cartao,'#data#',htmlspecialchars($sessao['data_criacao'] !== '' ? interface_formatar_dado(Array('dado' => $sessao['data_criacao'],'formato' => 'dataHora')) : $desconhecido));
			$cartao = modelo_var_troca($cartao,'#tipo#',perfil_usuario_seguranca_var($sessao['sessao'] ? 'sessions-type-browser' : 'sessions-type-persistent'));
			$cartao = modelo_var_troca_tudo($cartao,'#pubid#',htmlspecialchars($sessao['pubID'], ENT_QUOTES));

			$html = modelo_var_in($html,'<!-- cartao -->',$cartao);
		}

		$html = modelo_var_troca($html,'<!-- cartao -->','');
	}

	$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- '.$bloco.' < -->', '<!-- '.$bloco.' > -->', $html);
}

function perfil_usuario_interfaces_padroes(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	$usuario = gestor_usuario();

	switch($_GESTOR['opcao']){
		case 'editar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['iniciar'] = Array(
				'forcarId' => $usuario['id'],
			);
		break;
	}
}

// ==== Sair do sistema

function perfil_usuario_signout(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_GESTOR['usuario-token-id'])){
		gestor_sessao_del();
		
		banco_delete
		(
			"usuarios_tokens",
			"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
		);
		
		setcookie($_CONFIG['cookie-authname'], "", [
			'expires' => time() - 3600,
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		unset($_COOKIE[$_CONFIG['cookie-authname']]);
		
		setcookie($_CONFIG['cookie-authprofile'], "", [
			'expires' => time() - 3600,
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		unset($_COOKIE[$_CONFIG['cookie-authprofile']]);
	}
	
	gestor_redirecionar('signin/');
}

// ==== Acessos públicos

function perfil_usuario_api_perfis_permitidos(){
	$lista = isset($_ENV['AUTH_API_ALLOWED_PROFILES']) ? $_ENV['AUTH_API_ALLOWED_PROFILES'] : '1';
	$perfis = Array();
	foreach(explode(',', (string)$lista) as $perfil){
		$perfil = trim($perfil);
		if($perfil !== ''){
			$perfis[] = $perfil;
		}
	}
	return $perfis;
}

function perfil_usuario_api_perfil_permitido($id_usuarios_perfis){
	$perfis = perfil_usuario_api_perfis_permitidos();
	return in_array((string)$id_usuarios_perfis, $perfis, true);
}

function perfil_usuario_oauth_entregar_tokens($tokens, $url_redirect = ''){
	if($url_redirect){
		$query_params = http_build_query($tokens);
		$redirect_url = $url_redirect . (strpos($url_redirect, '?') !== false ? '&' : '?') . $query_params;
		header('Location: ' . $redirect_url);
		exit;
	}

	header('Content-Type: application/json');
	echo json_encode($tokens);
	exit;
}

function perfil_usuario_oauth_limpar_sessao(){
	gestor_sessao_variavel_del('pending_oauth_tokens');
	gestor_sessao_variavel_del('pending_oauth_user');
	gestor_sessao_variavel_del('pending_oauth_mode');
	gestor_sessao_variavel_del('pending_oauth_type');
	gestor_sessao_variavel_del('pending_oauth_url_redirect');
	gestor_sessao_variavel_del('pending_oauth_grant_type');
	gestor_sessao_variavel_del('pending_oauth_scope');
}

function perfil_usuario_oauth_gerar_tokens($id_usuarios, $grant_type, $scope, $url_redirect){
	gestor_incluir_biblioteca('oauth2');

	return oauth2_gerar_token_client_credentials(Array(
		'id_usuarios' => (int)$id_usuarios,
		'grant_type' => $grant_type,
		'scope' => $scope,
		'url_redirect' => $url_redirect
	));
}

function perfil_usuario_oauth_2fa_interceptar($id_usuarios, $tokens, $url_redirect = ''){
	global $_GESTOR;

	gestor_incluir_biblioteca('2fa');

	$id_usuarios = (int)$id_usuarios;
	$requerGlobal = (($_ENV['AUTH_API_2FA_REQUIRED'] ?? 'false') === 'true');
	$metodoApp = (($_ENV['AUTH_API_2FA_METHOD_APP'] ?? 'true') === 'true');
	$metodoEmail = (($_ENV['AUTH_API_2FA_METHOD_EMAIL'] ?? 'true') === 'true');

	$dados = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('two_factor_enabled', 'two_factor_type', 'email'),
		'extra' => "WHERE id_usuarios='".$id_usuarios."'",
	));

	$habilitado = $dados && !empty($dados['two_factor_enabled']);
	$tipo = ($habilitado && isset($dados['two_factor_type'])) ? $dados['two_factor_type'] : '';

	if(!$habilitado && !$requerGlobal){
		return false;
	}

	if($tipo === 'app' && !$metodoApp){
		$tipo = '';
	}
	if($tipo === 'email' && !$metodoEmail){
		$tipo = '';
	}
	if($tipo === ''){
		if($metodoEmail && $dados && !empty($dados['email'])){
			$tipo = 'email';
		} elseif($metodoApp){
			$tipo = 'app';
		}
	}

	if($tipo === ''){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-without-permission'))
		));
		gestor_redirecionar('oauth-authenticate/');
		return true;
	}

	gestor_sessao_variavel('pending_oauth_tokens', $tokens);
	gestor_sessao_variavel('pending_oauth_user', $id_usuarios);
	gestor_sessao_variavel('pending_oauth_mode', 'verify');
	gestor_sessao_variavel('pending_oauth_type', $tipo);
	gestor_sessao_variavel('pending_oauth_url_redirect', $url_redirect);

	if($tipo === 'email' && $dados && !empty($dados['email'])){
		two_factor_email_send_code($id_usuarios, $dados['email']);
	}

	gestor_redirecionar('oauth-authenticate-2fa/');
	return true;
}

function perfil_usuario_oauth_authenticate(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Verificar a permissão do acesso.
	
	gestor_incluir_biblioteca('autenticacao');
	
	$acesso = autenticacao_acesso_verificar(['tipo' => 'oauth2']);
	$apiSenhaAtiva = (($_ENV['AUTH_API_METHOD_PASSWORD_ACTIVE'] ?? 'true') === 'true');
	$apiEmailAtivo = (($_ENV['AUTH_API_METHOD_EMAIL_ACTIVE'] ?? 'false') === 'true');
	
	// ===== Tratar a função autenticate.

	if(isset($_REQUEST['_gestor-autenticate']) && $acesso['permitido']){
		$loginMetodo = isset($_REQUEST['login_method']) ? strtolower($_REQUEST['login_method']) : 'password';
		$loginMetodo = ($loginMetodo === 'email' && $apiEmailAtivo) ? 'email' : 'password';

		// ===== Validação de campos obrigatórios
		
		$camposObrigatorios = Array(
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'usuario',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
			),
		);

		if($loginMetodo === 'password'){
			$camposObrigatorios[] = Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'senha',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
			);
		}

		interface_validacao_campos_obrigatorios(Array('campos' => $camposObrigatorios));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_REQUEST['token'];
				$action = $_REQUEST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		$user_invalid = true;
		
		if($recaptchaValido){
			// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
			
			$usuario = banco_escape_field($_REQUEST['usuario']);
			$grant_type = isset($_REQUEST['grant_type']) ? $_REQUEST['grant_type'] : '';
			$scope = isset($_REQUEST['scope']) ? $_REQUEST['scope'] : 'read';
			$url_redirect = isset($_REQUEST['url_redirect']) ? $_REQUEST['url_redirect'] : '';
			$user_inactive = false;
			$user_without_permission = false;
			$metodo_indisponivel = false;

			if($loginMetodo === 'email' && $apiEmailAtivo){
				$usuarios = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios',
						'id_usuarios_perfis',
						'email',
						'status',
					))
					,
					"usuarios",
					"WHERE (usuario='".$usuario."' OR email='".$usuario."')"
					." AND status!='D'"
				);

				if($usuarios){
					if(!gestor_acesso('acesso-api', 'admin-environment',$usuarios[0]) || !perfil_usuario_api_perfil_permitido($usuarios[0]['id_usuarios_perfis'])){
						$user_without_permission = true;
					} elseif($usuarios[0]['status'] !== 'A'){
						$user_inactive = true;
					} elseif(!empty($usuarios[0]['email'])){
						$id_usuarios = (int)$usuarios[0]['id_usuarios'];
						$user_invalid = false;

						autenticacao_acesso_confirmar(['tipo' => 'oauth2']);

						gestor_incluir_biblioteca('2fa');
						two_factor_email_send_code($id_usuarios, $usuarios[0]['email']);

						perfil_usuario_oauth_limpar_sessao();
						gestor_sessao_variavel('pending_oauth_user', $id_usuarios);
						gestor_sessao_variavel('pending_oauth_mode', 'email-login');
						gestor_sessao_variavel('pending_oauth_type', 'email');
						gestor_sessao_variavel('pending_oauth_grant_type', $grant_type);
						gestor_sessao_variavel('pending_oauth_scope', $scope);
						gestor_sessao_variavel('pending_oauth_url_redirect', $url_redirect);

						gestor_redirecionar('oauth-authenticate-2fa/');
						return;
					}
				}
			} elseif($loginMetodo === 'password' && $apiSenhaAtiva){
				$senha = banco_escape_field($_REQUEST['senha']);

				$usuarios = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios',
						'id_usuarios_perfis',
						'senha',
						'status',
					))
					,
					"usuarios",
					"WHERE usuario='".$usuario."'"
					." AND status!='D'"
				);

				if($usuarios){
					if(!gestor_acesso('acesso-api', 'admin-environment',$usuarios[0]) || !perfil_usuario_api_perfil_permitido($usuarios[0]['id_usuarios_perfis'])){
						$user_without_permission = true;
					} else {
						$senha_hash = $usuarios[0]['senha'];

						if(password_verify($senha, $senha_hash)){
							$status = $usuarios[0]['status'];
							$id_usuarios = $usuarios[0]['id_usuarios'];

							if($status == 'A'){
								$user_invalid = false;

								autenticacao_acesso_confirmar(['tipo' => 'oauth2']);

								$tokens = perfil_usuario_oauth_gerar_tokens($id_usuarios, $grant_type, $scope, $url_redirect);

								if($tokens && perfil_usuario_oauth_2fa_interceptar($id_usuarios, $tokens, $url_redirect)){
									return;
								}
							} else {
								$user_inactive = true;
							}
						}
					}
				}
			} else {
				$metodo_indisponivel = true;
			}
		} else {
			// ===== Se o recaptcha for inválido, alertar o usuário.
			
			sleep(3);
			
			$botaoTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid-btn'));
			
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid'));
			
			$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('oauth-authenticate/');
		}
	
		// ===== Se o usuário for inválido, redirecionar oauth-authenticate.
		
		if($user_invalid){
			autenticacao_acesso_falha(['tipo' => 'oauth2']);
			
			sleep(3);
			
			if(isset($metodo_indisponivel) && $metodo_indisponivel){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-without-permission'))
				));
			} elseif($user_without_permission){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-without-permission'))
				));
			} elseif($user_inactive){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-inactive'))
				));
			} else {
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-or-password-invalid'))
				));
			}
			
			gestor_redirecionar('oauth-authenticate/');
		}

		// ===== Se o usuário for válido e gerou o token corretamente, redirecionar para o local pretendido se houver, senão retornar JSON.

		if(isset($tokens) && $tokens){
			perfil_usuario_oauth_entregar_tokens($tokens, $url_redirect);
		} else {
			// ===== Erro de autenticação
			
			$error_response = Array(
				'error' => 'invalid_grant',
				'error_description' => 'The provided authorization grant is invalid, expired, revoked, or was issued to another client.'
			);
			
			if($url_redirect){
				$query_params = http_build_query($error_response);
				$redirect_url = $url_redirect . (strpos($url_redirect, '?') !== false ? '&' : '?') . $query_params;
				header('Location: ' . $redirect_url);
				exit;
			} else {
				header('Content-Type: application/json');
				http_response_code(400);
				echo json_encode($error_response);
				exit;
			}
		}
	}
	
	// ===== Se não é POST, exibir formulário (GET)
	
	// ===== Mostrar formulário de autenticação OAuth
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], "#titulo#", "Autenticação OAuth 2.0");
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], "#form-action#", $_GESTOR['url-raiz'] . 'oauth-authenticate/');
	
	// ===== Query string para manter parâmetros
	
	$queryString = gestor_querystring();
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], "#querystring#", $queryString);

	// ===== Mostrar ou ocultar mensagem de bloqueio caso o IP esteja bloqueado.
	
	gestor_incluir_biblioteca('pagina');
	if($acesso['permitido']){	
		$cel_nome = 'bloqueado-mensagem'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	} else {
		$cel_nome = 'formulario'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}

	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));

	// ===== Renderização dinâmica dos métodos de autenticação da API

	if(!$apiSenhaAtiva && !$apiEmailAtivo){
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-switch#', '');
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-default#', 'password');
		$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- login-senha < -->', '<!-- login-senha > -->', '');
	} else {
		$loginMethodDefault = $apiSenhaAtiva ? 'password' : 'email';
		$loginMethodSwitch = '';

		if($apiSenhaAtiva && $apiEmailAtivo){
			$labelSenha = ($_GESTOR['linguagem-codigo'] === 'en') ? 'Use Password' : 'Usar Senha';
			$labelEmail = ($_GESTOR['linguagem-codigo'] === 'en') ? 'Use Email Code' : 'Usar Código por E-mail';
			$loginMethodSwitch = perfil_usuario_login_method_switch($labelSenha,$labelEmail);
		}

		if(!$apiSenhaAtiva){
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- login-senha < -->', '<!-- login-senha > -->', '');
		}

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-switch#', $loginMethodSwitch);
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-default#', $loginMethodDefault);
	}
	
	// ===== Validação do formulário
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'usuario',
			'label' => 'Usuário',
		),
	);

	if($apiSenhaAtiva && !$apiEmailAtivo){
		$formulario['validacao'][] = Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'senha',
			'label' => 'Senha',
		);
	}
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_oauth_authenticate_2fa(){
	global $_GESTOR;

	gestor_incluir_biblioteca('2fa');

	$id_usuarios = (int)gestor_sessao_variavel('pending_oauth_user');
	if(!$id_usuarios){
		gestor_redirecionar('oauth-authenticate/');
		return;
	}

	$mode = gestor_sessao_variavel('pending_oauth_mode');
	if(!existe($mode)) $mode = 'verify';

	$tipo = gestor_sessao_variavel('pending_oauth_type');
	if(!existe($tipo)) $tipo = 'email';

	$url_redirect = gestor_sessao_variavel('pending_oauth_url_redirect');

	if(isset($_REQUEST['_gestor-oauth-2fa'])){
		$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';
		$valido = false;

		if($tipo === 'email'){
			$valido = two_factor_email_validate($id_usuarios, $codigo);
		} else {
			$d = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios',
				'campos' => Array('two_factor_secret'),
				'extra' => "WHERE id_usuarios='".$id_usuarios."'",
			));
			$secret = ($d && isset($d['two_factor_secret'])) ? $d['two_factor_secret'] : '';
			$valido = ($secret !== '') && two_factor_validate_code($secret, $codigo);
		}

		if($valido){
			if($mode === 'email-login'){
				$grant_type = gestor_sessao_variavel('pending_oauth_grant_type');
				$scope = gestor_sessao_variavel('pending_oauth_scope');
				if(!existe($scope)) $scope = 'read';

				$tokens = perfil_usuario_oauth_gerar_tokens($id_usuarios, $grant_type, $scope, $url_redirect);

				perfil_usuario_oauth_limpar_sessao();

				if($tokens && perfil_usuario_oauth_2fa_interceptar($id_usuarios, $tokens, $url_redirect)){
					return;
				}

				if($tokens){
					perfil_usuario_oauth_entregar_tokens($tokens, $url_redirect);
					return;
				}
			} else {
				$tokens = gestor_sessao_variavel('pending_oauth_tokens');
				perfil_usuario_oauth_limpar_sessao();

				if($tokens){
					perfil_usuario_oauth_entregar_tokens($tokens, $url_redirect);
					return;
				}
			}
		}

		interface_alerta(Array('redirect' => true, 'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'security-2fa-code-invalid'))));
		gestor_redirecionar('oauth-authenticate-2fa/');
		return;
	}

	$html = '';

	if(isset($_REQUEST['enviar-email']) && $tipo === 'email'){
		$d = banco_select(Array('unico' => true, 'tabela' => 'usuarios', 'campos' => Array('email'), 'extra' => "WHERE id_usuarios='".$id_usuarios."'"));
		if($d && !empty($d['email'])){ two_factor_email_send_code($id_usuarios, $d['email']); }
		$html .= perfil_usuario_2fa_bloco('email-enviado');
	}

	if($mode === 'email-login'){
		$html .= perfil_usuario_2fa_ajuda(perfil_usuario_seguranca_var('oauth-2fa-email-help'));
	} else {
		$chave = ($tipo === 'email') ? 'login-2fa-verify-email-help' : 'login-2fa-verify-app-help';
		$html .= perfil_usuario_2fa_ajuda(perfil_usuario_seguranca_var($chave));
	}

	if($tipo === 'email'){
		$html .= perfil_usuario_2fa_reenviar_email('oauth-authenticate-2fa/');
	}

	$html .= perfil_usuario_2fa_campo_codigo();

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#conteudo-2fa#', $html);

	gestor_pagina_javascript_incluir();
}

function perfil_usuario_signin(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Verificar a permissão do acesso.
	
	gestor_incluir_biblioteca('autenticacao');
	
	$acesso = autenticacao_acesso_verificar(['tipo' => 'login']);
	
	// ===== Tratar a função logar.
	
	if(isset($_REQUEST['_gestor-logar']) && $acesso['permitido']){
		$emailAtivo = (($_ENV['AUTH_METHOD_EMAIL_ACTIVE'] ?? 'false') === 'true');
		$senhaAtiva = (($_ENV['AUTH_METHOD_PASSWORD_ACTIVE'] ?? 'true') === 'true');
		$loginMetodo = isset($_REQUEST['login_method']) ? strtolower($_REQUEST['login_method']) : 'password';
		$loginMetodo = ($loginMetodo === 'email' && $emailAtivo) ? 'email' : 'password';

		// ===== Validação de campos obrigatórios

		$camposObrigatorios = Array(
			Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'usuario',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
			),
		);

		if($loginMetodo === 'password'){
			$camposObrigatorios[] = Array(
				'regra' => 'texto-obrigatorio',
				'campo' => 'senha',
				'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
			);
		}

		interface_validacao_campos_obrigatorios(Array(
			'campos' => $camposObrigatorios
		));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_REQUEST['token'];
				$action = $_REQUEST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		$user_invalid = true;
		
		if($recaptchaValido){
			// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
			
			$usuario = banco_escape_field($_REQUEST['usuario']);
			$user_inactive = false;

			if($loginMetodo === 'email'){
				$usuarios = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios',
						'email',
						'status',
					))
					,
					"usuarios",
					"WHERE (usuario='".$usuario."' OR email='".$usuario."')"
					." AND status!='D'"
				);

				if($usuarios && isset($usuarios[0]['status']) && $usuarios[0]['status'] === 'A'){
					$id_usuarios = (int)$usuarios[0]['id_usuarios'];
					$emailUsuario = isset($usuarios[0]['email']) ? $usuarios[0]['email'] : '';

					if($emailUsuario !== ''){
						$user_invalid = false;

						autenticacao_acesso_confirmar(['tipo' => 'login']);

						gestor_incluir_biblioteca('2fa');
						two_factor_email_send_code($id_usuarios, $emailUsuario);

						gestor_sessao_variavel('pending_2fa_user', $id_usuarios);
						gestor_sessao_variavel('pending_2fa_keep', 0);
						gestor_sessao_variavel('pending_2fa_mode', 'verify');
						gestor_sessao_variavel('pending_2fa_type', 'email');

						gestor_redirecionar('signin-2fa/');
						return;
					}
				}
			} elseif($senhaAtiva){
				$senha = banco_escape_field($_REQUEST['senha']);

				$usuarios = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios',
						'senha',
						'status',
					))
					,
					"usuarios",
					"WHERE usuario='".$usuario."'"
					." AND status!='D'"
				);

				// ===== Rotinas de validação de usuário

				if($usuarios){
					$senha_hash = $usuarios[0]['senha'];

					if(password_verify($senha, $senha_hash)){
						// ===== Verificar se o usuário já está logado, caso esteja, deletar token anterior no banco.

						if(gestor_permissao_token()){
							if(isset($_GESTOR['usuario-token-id'])){
								banco_delete
								(
									"usuarios_tokens",
									"WHERE pubID='".$_GESTOR['usuario-token-id']."'"
								);
							}
						}

						// ===== Pegar dados do usuário.

						$status = $usuarios[0]['status'];
						$id_usuarios = $usuarios[0]['id_usuarios'];

						if($status == 'A'){
							$user_invalid = false;

							// ===== Incluir a confirmação do acesso para poder remover qualquer limitação de acesso do tipo específico.

							autenticacao_acesso_confirmar(['tipo' => 'login']);

							// ===== Interceptador 2FA (req-030): se o 2FA se aplicar, interrompe o login até a verificação do segundo fator.

							if(perfil_usuario_2fa_interceptar($id_usuarios, isset($_REQUEST['permanecer-logado']))){
								return;
							}

							// ===== Caso o usuário escolher a opção para manter logado, gera token de autenticação com tempo de expiração, senão será expirado assim que o usuário fechar navegador

							if(isset($_REQUEST['permanecer-logado'])){
								usuario_gerar_token_autorizacao(Array(
									'id_usuarios' => $id_usuarios,
								));
							} else {
								usuario_gerar_token_autorizacao(Array(
									'id_usuarios' => $id_usuarios,
									'sessao' => true,
								));
							}

							// ===== Registrar marcadores de Session Hijacking (req-030)

							gestor_incluir_biblioteca('seguranca');
							seguranca_sessao_registrar();

						} else {
							$user_inactive = true;
						}
					}
				}
			}
		} else {
			// ===== Se o recaptcha for inválido, alertar o usuário.
			
			sleep(3);
			
			$botaoTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid-btn'));
			
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid'));
			
			$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('signin/');
		}
	
		// ===== Se o usuário for inválido, redirecionar signin.
		
		if($user_invalid){
			autenticacao_acesso_falha(['tipo' => 'login']);
			
			sleep(3);
			
			if($user_inactive){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-inactive'))
				));
			} else {
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-or-password-invalid'))
				));
			}
			
			gestor_redirecionar('signin/');
		}
		
		// ===== Se o usuário for válido, redirecionar para o local pretendido se houver, senão para dashboard.
		
		if(existe(gestor_sessao_variavel("redirecionar-local"))){
			gestor_redirecionar();
		} else {
			gestor_redirecionar('dashboard/');
		}
	}
	
	// ===== Verifica se o cookie está ativo no navegador do usuário.
	// req-109: fluxo de autenticação exige sessão por cookie — mantém o round-trip de verificação.

	gestor_cookie_verificacao(true);
	
	// ===== Mostrar ou ocultar mensagem de bloqueio caso o IP esteja bloqueado.
	
	gestor_incluir_biblioteca('pagina');
	if($acesso['permitido']){	
		$cel_nome = 'bloqueado-mensagem'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	} else {
		$cel_nome = 'formulario'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Renderização dinâmica dos métodos de login (req-030)

	$senhaAtiva = (($_ENV['AUTH_METHOD_PASSWORD_ACTIVE'] ?? 'true') === 'true');
	$emailAtivo = (($_ENV['AUTH_METHOD_EMAIL_ACTIVE'] ?? 'false') === 'true');
	$googleAtivo = (($_ENV['AUTH_METHOD_GOOGLE_ACTIVE'] ?? 'false') === 'true');
	$metaAtivo = (($_ENV['AUTH_METHOD_META_ACTIVE'] ?? 'false') === 'true');

	if(!$senhaAtiva && !$emailAtivo){
		$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- login-local < -->', '<!-- login-local > -->', '');
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-switch#', '');
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-default#', 'password');
	} else {
		$loginMethodDefault = $senhaAtiva ? 'password' : 'email';
		$loginMethodSwitch = '';

		if($senhaAtiva && $emailAtivo){
			$labelSenha = ($_GESTOR['linguagem-codigo'] === 'en') ? 'Sign in with Password' : 'Entrar com Senha';
			$labelEmail = ($_GESTOR['linguagem-codigo'] === 'en') ? 'Sign in with Email Code' : 'Entrar com Código por E-mail';
			$loginMethodSwitch = perfil_usuario_login_method_switch($labelSenha,$labelEmail);
		}

		if(!$senhaAtiva){
			$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- login-senha < -->', '<!-- login-senha > -->', '');
		}

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-switch#', $loginMethodSwitch);
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-method-default#', $loginMethodDefault);
	}

	$provedores = Array();

	if($googleAtivo){
		$provedores['google'] = Array('G',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'login-with-google')));
	}
	if($metaAtivo){
		$provedores['meta'] = Array('f',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'login-with-meta')));
	}

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#login-social#', perfil_usuario_login_social($provedores));

	// ===== Interface finalizar opções

	$formulario['validacao'] = Array();
	if($senhaAtiva || $emailAtivo){
		$formulario['validacao'][] = Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'usuario',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-user-label')),
		);
	}
	if($senhaAtiva && !$emailAtivo){
		$formulario['validacao'][] = Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
		);
	}

	interface_formulario_validacao($formulario);
}

// ===== Helpers de Login com 2FA e Social (req-030) =====

/**
 * Limpa o estado de 2FA pendente da sessão.
 */
function perfil_usuario_2fa_limpar_sessao(){
	gestor_sessao_variavel_del('pending_2fa_user');
	gestor_sessao_variavel_del('pending_2fa_keep');
	gestor_sessao_variavel_del('pending_2fa_mode');
	gestor_sessao_variavel_del('pending_2fa_type');
}

/**
 * Conclui o login gerando o token de autorização e redirecionando ao destino.
 */
function perfil_usuario_finalizar_login($id_usuarios, $permanecerLogado){
	$id_usuarios = (int)$id_usuarios;

	if($permanecerLogado){
		usuario_gerar_token_autorizacao(Array('id_usuarios' => $id_usuarios));
	} else {
		usuario_gerar_token_autorizacao(Array('id_usuarios' => $id_usuarios, 'sessao' => true));
	}

	// ===== Registrar marcadores de Session Hijacking (req-030)

	gestor_incluir_biblioteca('seguranca');
	seguranca_sessao_registrar();

	if(existe(gestor_sessao_variavel('redirecionar-local'))){
		gestor_redirecionar();
	} else {
		gestor_redirecionar('dashboard/');
	}
}

/**
 * Interceptador 2FA pós-credenciais. Retorna true (e redireciona) quando o 2FA
 * se aplica ao usuário (já habilitado, ou obrigatório globalmente). Caso contrário
 * retorna false e o login segue normalmente.
 */
function perfil_usuario_2fa_interceptar($id_usuarios, $permanecerLogado){
	global $_GESTOR;

	gestor_incluir_biblioteca('2fa');

	$id_usuarios = (int)$id_usuarios;
	$requerGlobal = (($_ENV['AUTH_2FA_REQUIRED'] ?? 'false') === 'true');

	$dados = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('two_factor_enabled', 'two_factor_type', 'email'),
		'extra' => "WHERE id_usuarios='".$id_usuarios."'",
	));

	$habilitado = $dados && !empty($dados['two_factor_enabled']);
	$tipo = ($dados && isset($dados['two_factor_type'])) ? $dados['two_factor_type'] : '';

	// 2FA não se aplica: login normal.
	if(!$habilitado && !$requerGlobal){
		return false;
	}

	gestor_sessao_variavel('pending_2fa_user', $id_usuarios);
	gestor_sessao_variavel('pending_2fa_keep', $permanecerLogado ? 1 : 0);

	if($habilitado){
		gestor_sessao_variavel('pending_2fa_mode', 'verify');
		gestor_sessao_variavel('pending_2fa_type', $tipo);

		// Método e-mail: dispara o código imediatamente.
		if($tipo === 'email' && $dados && !empty($dados['email'])){
			two_factor_email_send_code($id_usuarios, $dados['email']);
		}
	} else {
		gestor_sessao_variavel('pending_2fa_mode', 'setup');
	}

	gestor_redirecionar('signin-2fa/');
	return true;
}

/**
 * Inicia o fluxo de login social redirecionando ao provedor.
 */
function perfil_usuario_social_login(){
	global $_GESTOR;

	gestor_incluir_biblioteca('oauth');

	$provider = isset($_REQUEST['provider']) ? strtolower($_REQUEST['provider']) : '';
	$url = oauth_redirect_url($provider);

	if($url){
		gestor_sessao_variavel('oauth_action', 'login');
		header('Location: '.$url);
		exit;
	}

	gestor_redirecionar('signin/');
}

/**
 * Callback OAuth (login ou vínculo de conta social).
 */
function perfil_usuario_oauth_callback(){
	global $_GESTOR;

	gestor_incluir_biblioteca('oauth');

	$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
	$state = isset($_REQUEST['state']) ? $_REQUEST['state'] : '';
	$provider = gestor_sessao_variavel('oauth_provider');
	if(!existe($provider)) $provider = isset($_REQUEST['provider']) ? strtolower($_REQUEST['provider']) : '';
	$action = gestor_sessao_variavel('oauth_action');
	if(!existe($action)) $action = 'login';

	// Proteção CSRF via state.
	if($code === '' || !oauth_validate_state($state)){
		interface_alerta(Array('redirect' => true, 'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'alert-user-or-password-invalid'))));
		gestor_redirecionar('signin/');
		return;
	}

	$perfil = oauth_authenticate_code($provider, $code);

	if(!$perfil || empty($perfil['uid'])){
		interface_alerta(Array('redirect' => true, 'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'alert-user-or-password-invalid'))));
		gestor_redirecionar('signin/');
		return;
	}

	$provider = $perfil['provider'];
	$uid = $perfil['uid'];
	$email = isset($perfil['email']) ? $perfil['email'] : '';

	// ===== Vínculo de conta (a partir da rota de Segurança)
	if($action === 'link'){
		$id_usuarios = (int)gestor_sessao_variavel('oauth_link_user');
		gestor_sessao_variavel_del('oauth_action');
		gestor_sessao_variavel_del('oauth_link_user');

		if($id_usuarios){
			$jaExiste = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_provedores',
				'campos' => Array('id_usuarios_provedores'),
				'extra' => "WHERE provider_name='".banco_escape_field($provider)."' AND provider_uid='".banco_escape_field($uid)."'",
			));

			if(!$jaExiste){
				banco_insert_name_campo('usuario_id', $id_usuarios);
				banco_insert_name_campo('provider_name', $provider);
				banco_insert_name_campo('provider_uid', $uid);
				banco_insert_name_campo('created_at', date('Y-m-d H:i:s'));
				banco_insert_name(banco_insert_name_campos(), 'usuarios_provedores');
			}
		}

		gestor_redirecionar('perfil-usuario/?configurar-seguranca=sim');
		return;
	}

	// ===== Login social
	gestor_sessao_variavel_del('oauth_action');

	$id_usuarios = 0;
	$vinculo = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios_provedores',
		'campos' => Array('usuario_id'),
		'extra' => "WHERE provider_name='".banco_escape_field($provider)."' AND provider_uid='".banco_escape_field($uid)."'",
	));

	if($vinculo && !empty($vinculo['usuario_id'])){
		$id_usuarios = (int)$vinculo['usuario_id'];
	} else if($email !== ''){
		// Sem vínculo: tenta casar pelo e-mail de um usuário existente e ativo.
		$usuarios = banco_select_name(
			banco_campos_virgulas(Array('id_usuarios', 'status')),
			"usuarios",
			"WHERE email='".banco_escape_field($email)."' AND status!='D'"
		);

		if($usuarios && isset($usuarios[0]['status']) && $usuarios[0]['status'] === 'A'){
			$id_usuarios = (int)$usuarios[0]['id_usuarios'];
			banco_insert_name_campo('usuario_id', $id_usuarios);
			banco_insert_name_campo('provider_name', $provider);
			banco_insert_name_campo('provider_uid', $uid);
			banco_insert_name_campo('created_at', date('Y-m-d H:i:s'));
			banco_insert_name(banco_insert_name_campos(), 'usuarios_provedores');
		}
	}

	if(!$id_usuarios){
		interface_alerta(Array('redirect' => true, 'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'alert-user-or-password-invalid'))));
		gestor_redirecionar('signin/');
		return;
	}

	// Interceptador 2FA também no login social.
	if(perfil_usuario_2fa_interceptar($id_usuarios, true)){
		return;
	}

	perfil_usuario_finalizar_login($id_usuarios, true);
}

/**
 * Tela intermediária de 2FA (verificação ou configuração obrigatória) pós-credenciais.
 */
function perfil_usuario_signin_2fa(){
	global $_GESTOR;

	gestor_incluir_biblioteca('2fa');

	$id_usuarios = (int)gestor_sessao_variavel('pending_2fa_user');
	if(!$id_usuarios){
		gestor_redirecionar('signin/');
		return;
	}

	$mode = gestor_sessao_variavel('pending_2fa_mode');
	if(!existe($mode)) $mode = 'verify';
	$permanecer = !empty(gestor_sessao_variavel('pending_2fa_keep'));

	$metodoApp = (($_ENV['AUTH_2FA_METHOD_APP'] ?? 'true') === 'true');
	$metodoEmail = (($_ENV['AUTH_2FA_METHOD_EMAIL'] ?? 'true') === 'true');

	// ===== POST: validar e concluir
	if(isset($_REQUEST['_gestor-2fa'])){
		$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';
		$valido = false;

		if($mode === 'setup'){
			$metodo = isset($_REQUEST['metodo']) ? strtolower($_REQUEST['metodo']) : 'app';

			if($metodo === 'email'){
				$valido = two_factor_email_validate($id_usuarios, $codigo);
			} else {
				$metodo = 'app';
				$d = banco_select(Array('unico' => true, 'tabela' => 'usuarios', 'campos' => Array('two_factor_secret'), 'extra' => "WHERE id_usuarios='".$id_usuarios."'"));
				$secret = ($d && isset($d['two_factor_secret'])) ? $d['two_factor_secret'] : '';
				$valido = ($secret !== '') && two_factor_validate_code($secret, $codigo);
			}

			if($valido){
				banco_update_campo('two_factor_enabled', '1');
				banco_update_campo('two_factor_type', $metodo);
				banco_update_executar('usuarios', "WHERE id_usuarios='".$id_usuarios."'");
			}
		} else {
			$tipo = gestor_sessao_variavel('pending_2fa_type');

			if($tipo === 'email'){
				$valido = two_factor_email_validate($id_usuarios, $codigo);
			} else {
				$d = banco_select(Array('unico' => true, 'tabela' => 'usuarios', 'campos' => Array('two_factor_secret'), 'extra' => "WHERE id_usuarios='".$id_usuarios."'"));
				$secret = ($d && isset($d['two_factor_secret'])) ? $d['two_factor_secret'] : '';
				$valido = ($secret !== '') && two_factor_validate_code($secret, $codigo);
			}

			// ===== Código de recuperação (req-119)
			//
			// Só é tentado quando o segundo fator normal já falhou: é o caminho de resgate de quem
			// perdeu o aplicativo autenticador, e cada código vale UMA vez. Tentar antes gastaria um
			// código a cada digitação errada do TOTP.

			if(!$valido){
				$valido = perfil_usuario_recovery_code_consumir($id_usuarios,$codigo);
			}
		}

		if($valido){
			perfil_usuario_2fa_limpar_sessao();
			perfil_usuario_finalizar_login($id_usuarios, $permanecer);
			return;
		}

		interface_alerta(Array('redirect' => true, 'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'security-2fa-code-invalid'))));
		gestor_redirecionar('signin-2fa/');
		return;
	}

	// ===== GET: montar o conteúdo da tela
	$html = '';

	// Disparo/reenvio de código por e-mail (sem usuário logado, via link GET).
	if(isset($_REQUEST['enviar-email'])){
		$d = banco_select(Array('unico' => true, 'tabela' => 'usuarios', 'campos' => Array('email'), 'extra' => "WHERE id_usuarios='".$id_usuarios."'"));
		if($d && !empty($d['email'])){ two_factor_email_send_code($id_usuarios, $d['email']); }
		$html .= perfil_usuario_2fa_bloco('email-enviado');
	}

	$qrCodeNecessario = false;

	if($mode === 'setup'){
		$html .= perfil_usuario_2fa_ajuda(perfil_usuario_seguranca_var('login-2fa-setup-help'));

		$html .= modelo_var_troca(
			perfil_usuario_2fa_bloco('metodo'),
			'#opcoes#',
			perfil_usuario_2fa_opcoes('perfil-usuario-2fa-campos','metodo-opcao',$metodoApp,$metodoEmail)
		);

		if($metodoApp){
			$qrCodeNecessario = true;
			$dados = banco_select(Array('unico' => true, 'tabela' => 'usuarios', 'campos' => Array('two_factor_secret', 'email'), 'extra' => "WHERE id_usuarios='".$id_usuarios."'"));
			$secret = ($dados && !empty($dados['two_factor_secret'])) ? $dados['two_factor_secret'] : '';
			if($secret === ''){
				$secret = two_factor_generate_secret();
				banco_update_campo('two_factor_secret', $secret);
				banco_update_executar('usuarios', "WHERE id_usuarios='".$id_usuarios."'");
			}
			$emailUsuario = ($dados && isset($dados['email'])) ? $dados['email'] : '';

			$qr = perfil_usuario_2fa_bloco('qr');
			$qr = modelo_var_troca($qr,'#otpauth#',htmlspecialchars(two_factor_get_qr_code($emailUsuario,$secret), ENT_QUOTES));
			$qr = modelo_var_troca($qr,'#secret#',htmlspecialchars($secret));

			$html .= $qr;
		}

		if($metodoEmail){
			$html .= modelo_var_troca(perfil_usuario_2fa_bloco('email-bloco'),'#botao#',perfil_usuario_2fa_reenviar_email('signin-2fa/'));
		}
	} else {
		$tipo = gestor_sessao_variavel('pending_2fa_type');
		$chave = ($tipo === 'email') ? 'login-2fa-verify-email-help' : 'login-2fa-verify-app-help';
		$html .= perfil_usuario_2fa_ajuda(perfil_usuario_seguranca_var($chave));
		if($tipo === 'email'){
			$html .= perfil_usuario_2fa_reenviar_email('signin-2fa/');
		}
	}

	$html .= perfil_usuario_2fa_campo_codigo();

	// req-119: o mesmo campo aceita um código de recuperação; o backend só o tenta depois de o
	// segundo fator normal falhar, então basta dizer isso ao usuário.
	if($mode !== 'setup' && usuario_recovery_codes_disponivel()){
		$html .= perfil_usuario_2fa_bloco('recovery-aviso');
	}

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#conteudo-2fa#', $html);

	// QR Code renderizado no cliente, só na tela que de fato o desenha.
	if($qrCodeNecessario){
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>');
	}

	gestor_pagina_javascript_incluir();
}

function perfil_usuario_signup(){
	global $_GESTOR;
	global $_CONFIG;

	// ===== Hook start

	hook_do_action($_GESTOR['modulo-id'], 'signup.start');

	// ===== Verificar a permissão do acesso.

	gestor_incluir_biblioteca('autenticacao');

	$acesso = autenticacao_acesso_verificar(['tipo' => 'signup']);
	
	if(isset($_REQUEST['_gestor-signup']) && $acesso['permitido']){
		$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'senha',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
					'min' => 12,
				),
			)
		));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_POST['token'];
				$action = $_POST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		if($recaptchaValido){
			// ===== Definição do identificador
		
			$campos = null;
			$campo_sem_aspas_simples = false;
			
			$id = banco_identificador(Array(
				'id' => banco_escape_field($_REQUEST["nome"]),
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campo' => $modulo['tabela']['id'],
					'id_nome' => $modulo['tabela']['id_numerico'],
				),
			));
			
			// ===== Verificar se os campos enviados não existem no banco de dados
			
			$exiteEmail = interface_verificar_campos(Array(
				'campo' => 'email',
				'valor' => banco_escape_field($_REQUEST['email']),
			));
			
			if($exiteEmail){
				$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-there-is-a-field'));
				$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')));
				$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['email']));
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
				
				gestor_redirecionar('signup/',gestor_querystring_before_submit());
			}
			
			// ===== Iniciar o usuário com o perfil de usuário padrão.
			
			$usuarios_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_perfis',
				'campos' => Array(
					'id_usuarios_perfis',
				),
				'extra' => 
					"WHERE padrao IS NOT NULL AND language='".$_GESTOR['linguagem-codigo']."'"
			));
			
			if($usuarios_perfis['id_usuarios_perfis']){
				$id_usuarios_perfis = $usuarios_perfis['id_usuarios_perfis'];
			} else {
				$id_usuarios_perfis = $_CONFIG['usuario-perfil-id-padrao'];
			}
			
			// ===== Se o plano foi informado, buscar o perfil de usuário correspondente ao plano.
			
			if(!empty($_REQUEST['plano'])){
				$perfil_plano = banco_select(Array(
					'unico' => true,
					'tabela' => 'usuarios_perfis',
					'campos' => Array(
						'id_usuarios_perfis',
					),
					'extra' => 
						"WHERE id='".banco_escape_field($_REQUEST['plano'])."' AND language='".$_GESTOR['linguagem-codigo']."'"
				));
				
				if($perfil_plano && $perfil_plano['id_usuarios_perfis']){
					$id_usuarios_perfis = $perfil_plano['id_usuarios_perfis'];
				}
			}
			
			// ===== Gerar hash da senha
			
			$senha = banco_escape_field($_REQUEST['senha']);
			
			$senhaHash = password_hash($senha, PASSWORD_ARGON2ID, [
				'memory_cost' => 65536,
				'time_cost' => 4,
				'threads' => 2,
			]);
			
			// ===== Separar os nomes (primeiro, do meio e último)
			
			$nome = banco_escape_field($_REQUEST['nome']);
			
			$nomes = explode(' ',$nome);
			
			if(count($nomes) > 2){
				for($i=0;$i<count($nomes);$i++){
					if($i==0){
						$primeiro_nome = $nomes[$i];
					} else if($i==count($nomes) - 1){
						$ultimo_nome = $nomes[$i];
					} else {
						$nome_do_meio .= (isset($nome_do_meio) ? ' ':'') . $nomes[$i];
					}
				}
			} else if(count($nomes) > 1){
				$primeiro_nome = $nomes[0];
				$ultimo_nome = $nomes[1];
			} else {
				$primeiro_nome = $nomes[0];
			}
			
			// ===== Campos gerais
			
			$campo_nome = "id_usuarios_perfis"; $campo_valor = $id_usuarios_perfis;			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			$campo_nome = "nome_conta"; $post_nome = "nome"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			$campo_nome = "usuario"; $post_nome = "email"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			$campo_nome = "senha"; $campo_valor = $senhaHash; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "email"; $post_nome = "email"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
			
			$campo_nome = "primeiro_nome"; 					 								if(isset($primeiro_nome))		$campos[] = Array($campo_nome,$primeiro_nome);
			$campo_nome = "nome_do_meio"; 					 								if(isset($nome_do_meio))		$campos[] = Array($campo_nome,$nome_do_meio);
			$campo_nome = "ultimo_nome"; 					 								if(isset($ultimo_nome))		$campos[] = Array($campo_nome,$ultimo_nome);
			
			// ===== Campos comuns
			
			$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
		
			banco_insert_name
			(
				$campos,
				$modulo['tabela']['nome']
			);
			
			$id_usuarios = banco_last_id();
			
			// ===== Hook: signup.banco — permite integração de módulos externos no cadastro de novos usuários.
			
			gestor_incluir_biblioteca('hooks');
			
			hook_do_action('perfil-usuario', 'signup.banco', $id_usuarios, [
				'nome'   => $nome,
				'email'  => banco_escape_field($_REQUEST['email']),
				'id'     => $id,
				'plano'  => $_REQUEST['plano'] ?? null,
				'domain' => $_REQUEST['domain'] ?? null,
			]);
			
			// ===== Logar o usuário 
			
			usuario_gerar_token_autorizacao(Array(
				'id_usuarios' => $id_usuarios,
			));
			
			// ===== Criar o token e guardar o mesmo no banco
			
			gestor_incluir_biblioteca('seguranca');
			$tokenPubId = seguranca_token_aleatorio(32);
			$expiration = time() + $_CONFIG['token-lifetime'];

			$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
			
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = 'new-register'; 				$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "pubID"; $campo_valor = $pubID; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 			$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"tokens"
			);
			
			$tokens_id = banco_last_id();
			
			// ===== Incluir acesso do tipo signup para evitar SPAM de cadastros.
			
			autenticacao_acesso_cadastrar(['tipo' => 'signup']);
			
			// ===== Enviar o email confirmando o cadastro junto com a URL de confirmação do email.
			
			// ===== Hook: signup.email — permite módulos filtrarem o envio do email automático de cadastro.
			
			$enviar_email_signup = hook_apply_filters('perfil-usuario', 'signup.email', true, $id_usuarios, [
				'nome'   => $_REQUEST['nome'],
				'email'  => $_REQUEST['email'],
				'plano'  => $_REQUEST['plano'] ?? null,
				'domain' => $_REQUEST['domain'] ?? null,
				'tokenPubId' => $tokenPubId,
			]);
			
			if($enviar_email_signup){
			
				$nome = $_REQUEST['nome'];
				$email = $_REQUEST['email'];
				$numero = date('Ymd') . $tokens_id;
				
				$assunto = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'signup-mail-subject')),"#numero#",$numero);
				
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
						'htmlLayoutID' => 'layout-email-novo-cadastro',
						'htmlVariaveis' => Array(
							Array(
								'variavel' => '#nome#',
								'valor' => $nome,
							),
							Array(
								'variavel' => '#url-signin#',
								'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'signin/">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'signin/</a>',
							),
							Array(
								'variavel' => '#url-confirmacao#',
								'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'email-confirmation/?id='.$tokenPubId.'">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'email-confirmation/?id='.$tokenPubId.'</a>',
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
			
			} // fim do if($enviar_email_signup)
			
		} else {
			// ===== Se o recaptcha for inválido, alertar o usuário.
			
			sleep(3);
			
			$botaoTxt = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid-btn'));
			
			$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-recaptcha-invalid'));
			
			$alerta = modelo_var_troca_tudo($alerta,"#url#",'<a href="'.$_GESTOR['url-raiz'] . $_GESTOR['pagina#contato-url'].'">'.$botaoTxt.'</a>');
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar('signup/',gestor_querystring_before_submit());
		}
		
		// ===== Hook: signup.redirect — permite módulos redirecionarem o usuário após cadastro.
		
		$signup_redirect = 'dashboard/';
		
		if(existe(gestor_sessao_variavel("redirecionar-local"))){
			$signup_redirect = gestor_sessao_variavel("redirecionar-local");
			gestor_sessao_variavel_del("redirecionar-local");
		}
		
		$signup_redirect = hook_apply_filters('perfil-usuario', 'signup.redirect', $signup_redirect, $id_usuarios);
		
		gestor_redirecionar($signup_redirect);
	}

	// ===== Hook pos_banco

	hook_do_action($_GESTOR['modulo-id'], 'signup.pos_banco');
	
	// ===== Verifica se o cookie está ativo no navegador do usuário.
	// req-109: fluxo de autenticação exige sessão por cookie — mantém o round-trip de verificação.

	gestor_cookie_verificacao(true);
	
	// ===== Mostrar ou ocultar mensagem de bloqueio caso o IP esteja bloqueado.
	
	gestor_incluir_biblioteca('pagina');
	if($acesso['permitido']){	
		$cel_nome = 'bloqueado-mensagem'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	} else {
		$cel_nome = 'formulario'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Planos: DESCONTINUADO! Será removido daqui.

	$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- plano-cont < -->','<!-- plano-cont > -->','');

	// ===== Interface finalizar opções
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'texto-obrigatorio',
			'campo' => 'nome',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
		),
		Array(
			'regra' => 'email-comparacao',
			'campo' => 'email',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
			'identificador' => 'email',
			'comparcao' => Array(
				'id' => 'email-2',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
			)
		),
		Array(
			'regra' => 'email-comparacao',
			'campo' => 'email-2',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
			'identificador' => 'email-2',
			'comparcao' => Array(
				'id' => 'email',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-email-2-label')),
			)
		),
		Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
			'identificador' => 'senha',
			'comparcao' => Array(
				'id' => 'senha-2',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
			)
		),
		Array(
			'regra' => 'senha-comparacao',
			'campo' => 'senha-2',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
			'identificador' => 'senha-2',
			'comparcao' => Array(
				'id' => 'senha',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-password-2-label')),
			)
		),
	);
	
	interface_formulario_validacao($formulario);

	// ===== Hook end

	hook_do_action($_GESTOR['modulo-id'], 'signup.end');
}

function perfil_usuario_forgot_password(){
	global $_GESTOR;
	global $_CONFIG;
	
	// ===== Verificar a permissão do acesso.
	
	gestor_incluir_biblioteca('autenticacao');
	
	$acesso = autenticacao_acesso_verificar(['tipo' => 'forgot-password']);
	
	if(isset($_REQUEST['_gestor-forgot-password']) && $acesso['permitido']){
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'email-obrigatorio',
					'campo' => 'email',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
				),
			)
		));
		
		// ===== Google reCAPTCHA v3
		
		$recaptchaValido = false;
		
		if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
			if($_CONFIG['usuario-recaptcha-active']){
				// ===== Variáveis de comparação do reCAPTCHA
				
				$recaptchaSecretKey = $_CONFIG['usuario-recaptcha-server'];
				
				$token = $_POST['token'];
				$action = $_POST['action'];
				
				// ===== Chamada ao servidor do Google reCAPTCHA para conferência se o token enviado no formulário é válido.
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptchaSecretKey, 'response' => $token)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$arrResponse = json_decode($response, true);
				
				// ===== Verificar se o retorno do servidor é válido, senão não validar o reCAPTCHA
				
				if($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
					$recaptchaValido = true;
				}
			} else {
				$recaptchaValido = true;
			}
		} else {
			$recaptchaValido = true;
		}
		
		$user_invalid = true;
		
		if($recaptchaValido){
			// ===== Verificar se os dados enviados batem com algum usuário dentro do sistema
			
			$email = banco_escape_field($_REQUEST['email']);
			
			$user_inactive = false;
			
			$usuarios = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuarios',
					'nome',
					'email',
					'status',
				))
				,
				"usuarios",
				"WHERE email='".$email."'"
				." AND status!='D'"
			);
			
			// ===== Rotinas de validação de usuário
			
			if($usuarios){
				$status = $usuarios[0]['status'];
				$id_usuarios = $usuarios[0]['id_usuarios'];
				$email = $usuarios[0]['email'];
				$nome = $usuarios[0]['nome'];
				
				if($status == 'A'){
					// ===== Criar o token e guardar o mesmo no banco
					
					gestor_incluir_biblioteca('seguranca');
					$tokenPubId = seguranca_token_aleatorio(32);
					$expiration = time() + $_CONFIG['token-lifetime'];
		
					$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
					
					$campos = null; $campo_sem_aspas_simples = null;
					
					$campo_nome = "id_usuarios"; $campo_valor = $id_usuarios; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "id"; $campo_valor = 'forgot-password'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "pubID"; $campo_valor = $pubID; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "expiration"; $campo_valor = $expiration; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
					
					banco_insert_name
					(
						$campos,
						"tokens"
					);
					
					$tokens_id = banco_last_id();
					
					// ===== Incluir a confirmação do acesso para poder remover qualquer limitação de acesso do tipo específico.
					
					autenticacao_acesso_confirmar(['tipo' => 'forgot-password']);
					
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
							'htmlLayoutID' => 'layout-email-esqueceu-senha',
							'htmlVariaveis' => Array(
								Array(
									'variavel' => '#nome#',
									'valor' => $nome,
								),
								Array(
									'variavel' => '#url#',
									'valor' => '<a href="https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'redefine-password/?id='.$tokenPubId.'">https://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].'redefine-password/?id='.$tokenPubId.'</a>',
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
						$user_invalid = false;
					} else {
						$email_not_sent = true;
					}
				} else {
					$user_inactive = true;
				}
			}
		}
	
		// ===== Se o usuário for inválido, redirecionar forgot-password.
		
		if($user_invalid){
			autenticacao_acesso_falha(['tipo' => 'forgot-password']);
			
			sleep(3);
			
			if($user_inactive){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-inactive'))
				));
			} else if(isset($email_not_sent)){
				interface_alerta(Array(
					'redirect' => true,
					'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-email-not-sent'))
				));
			} else {
				$msg = modelo_var_troca(gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-user-email-invalid')),"#email#",$email);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $msg
				));
			}
			
			gestor_redirecionar('forgot-password/');
		} else {
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.'forgot-password-confirmation'.'-'.'email',$email);
			gestor_redirecionar('forgot-password-confirmation/');
		}
	}
	
	// ===== Mostrar ou ocultar mensagem de bloqueio caso o IP esteja bloqueado.
	
	gestor_incluir_biblioteca('pagina');
	if($acesso['permitido']){	
		$cel_nome = 'bloqueado-mensagem'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	} else {
		$cel_nome = 'formulario'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
	}
	
	// ===== Incluir google reCAPTCHA caso ativo
	
	if(isset($_CONFIG['usuario-recaptcha-active']) && $acesso['status'] != 'livre'){
		if($_CONFIG['usuario-recaptcha-active']){
			$_GESTOR['javascript-vars']['googleRecaptchaActive'] = true;
			$_GESTOR['javascript-vars']['googleRecaptchaSite'] = $_CONFIG['usuario-recaptcha-site'];
			
			gestor_pagina_javascript_incluir('<script src="https://www.google.com/recaptcha/api.js?render='.$_CONFIG['usuario-recaptcha-site'].'"></script>');
		}
	}
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$formulario['validacao'] = Array(
		Array(
			'regra' => 'email-comparacao',
			'campo' => 'email',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
			'identificador' => 'email',
			'comparcao' => Array(
				'id' => 'email-2',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-2-label')),
			)
		),
		Array(
			'regra' => 'email-comparacao',
			'campo' => 'email-2',
			'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-2-label')),
			'identificador' => 'email-2',
			'comparcao' => Array(
				'id' => 'email',
				'campo-1' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-label')),
				'campo-2' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-email-2-label')),
			)
		),
	);
	
	interface_formulario_validacao($formulario);
}

function perfil_usuario_forgot_password_confirmation(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Caso exista a variável devolva a página, senão redirecionar porque não se deve acessar essa página diretamente.
	
	if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email'))){
		$email = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email');
		gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-'.'email');
		
		$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-confirmation-message-content'));
		
		$message = modelo_var_troca_tudo($message,"#email#",$email);
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#message#",$message);
	} else {
		gestor_redirecionar('forgot-password/');
	}
}

function perfil_usuario_redefine_password(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(isset($_REQUEST['_gestor-redefine-password'])){
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'redirect' => (isset($_REQUEST['_gestor-redefine-password-token']) ? 'redefine-password/?id='. banco_escape_field($_REQUEST['_gestor-redefine-password-token']) : NULL),
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
		$id_usuarios = '';
		
		// ===== Hash do token enviado e comparar com os tokens do banco de dados para ver se existem.
		
		$tokenPubId = banco_escape_field($_REQUEST['_gestor-redefine-password-token']);
		
		$pubID = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
		
		// ===== Verificar se já houve validação do campo e criação da sessão
		
		if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
			$sessaoRedefinePassword = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			
			if($sessaoRedefinePassword['pubID'] == $pubID){
				$autorizacaoRedefinicao = true;
				$id_usuarios = $sessaoRedefinePassword['id'];
				$tokens_id = $sessaoRedefinePassword['tokenID'];
			} else {
				gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			}
		}
		
		// ===== Caso autorizado atualizar senha no banco, senão alertar o usuário e redirecionar para esqueceu senha novamente.
		
		if($autorizacaoRedefinicao){
			// ===== Gerar hash da senha
			
			$senha = banco_escape_field($_REQUEST['senha']);
			
			$senhaHash = password_hash($senha, PASSWORD_ARGON2ID, [
				'memory_cost' => 65536,
				'time_cost' => 4,
				'threads' => 2,
			]);
			
			// ===== Atualizar senha no banco da conta do usuário e redirecionar para a página de confirmação
			
			banco_update
			(
				"senha='".$senhaHash."',".
				"data_modificacao=NOW(),".
				"versao=versao+1",
				"usuarios",
				"WHERE id_usuarios='".$id_usuarios."'"
			);
			
			// ===== Pegar a referência do host do usuário para incluir no histórico caso seja um usuário de um host.
			
			$usuarios_gestores_hosts = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_gestores_hosts',
				'campos' => Array(
					'id_hosts',
				),
				'extra' => 
					"WHERE id_usuarios='".$id_usuarios."'"
			));
			
			if($usuarios_gestores_hosts){
				$id_hosts = $usuarios_gestores_hosts['id_hosts'];
			}
			
			// ===== Pegar o IP do usuário.
			
			gestor_incluir_biblioteca('ip');
			
			$ip = ip_get();
			
			// ===== Criar histórico de alterações.
			
			$resetPasswordTXT = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'reset-password'));
			
			$resetPasswordTXT = modelo_var_troca($resetPasswordTXT,"#ip#",$ip);
			$resetPasswordTXT = modelo_var_troca($resetPasswordTXT,"#user-agent#",$_SERVER['HTTP_USER_AGENT']);
			
			$alteracoes[] = Array('alteracao' => 'reset-password','alteracao_txt' => $resetPasswordTXT);
			
			interface_historico_incluir(Array(
				'id_numerico_manual' => $id_usuarios,
				'id_usuarios_manual' => $id_usuarios,
				'id_hosts_manual' => (isset($id_hosts) ? $id_hosts : null ),
				'alteracoes' => $alteracoes,
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
					'htmlLayoutID' => 'layout-email-senha-redefinida',
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
			
			// ===== Remover todos os acessos logados no sistema.
			
			banco_delete
			(
				"usuarios_tokens",
				"WHERE id_usuarios='".$id_usuarios."'"
			);
			
			// ===== Redirecionar para a página de confirmação.
			
			gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'-confirmation',true);
			gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			gestor_redirecionar('redefine-password-confirmation/');
		} else {
			sleep(3);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-redefine-password-expiration-or-invalid'))
			));
			
			gestor_redirecionar('forgot-password/');
		}
	}
	
	// ===== Campo de validação dos dados.
	
	$autorizacao = false;
	
	// ===== Verifica se foi enviado um id.
	
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
					'id_usuarios',
				))
				,
				"tokens",
				"WHERE pubID='".$pubID."'"
			);
			
			if($tokens){
				$autorizacao = true;
				
				gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'],Array(
					'id' => $tokens[0]['id_usuarios'],
					'tokenID' => $tokens[0]['id_tokens'],
					'pubID' => $pubID,
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
		
		gestor_redirecionar('forgot-password/');
	}
	
	// ===== Alterar dados da página e incluir o token
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#token#",$tokenPubId);
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
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

function perfil_usuario_redefine_password_confirmation(){
	global $_GESTOR;
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Caso exista a variável devolva a página, senão redirecionar porque não se deve acessar essa página diretamente.
	
	if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']))){
		gestor_sessao_variavel_del($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
		
		$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'redefine-password-confirmation-message-content'));
		
		$message = modelo_var_troca_tudo($message,"#url#",'<a href="'.$_GESTOR['url-raiz'].'signin/">'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'forgot-password-login-button')).'</a>');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#message#",$message);
	} else {
		gestor_redirecionar('forgot-password/');
	}
}

function perfil_usuario_validar_usuario(){
	global $_GESTOR;
	
	if(isset($_REQUEST['_gestor-validar-usuario'])){
		//$fingerprint = banco_escape_field($_REQUEST['_gestor-validar-usuario-fingerprint']);
		
		//if(existe($fingerprint)){
			if(gestor_permissao_token()){
				$tokenPubId = $_GESTOR['usuario-token-id'];
				
				$usuarios_tokens_verificar = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios_tokens',
					))
					,
					"usuarios_tokens",
					"WHERE pubID='".$tokenPubId."'"
					//." AND fingerprint='".$fingerprint."'"
				);
				
				if($usuarios_tokens_verificar){
					//gestor_sessao_variavel('browser-fingerprint',true);
					
					$querystring = banco_escape_field($_REQUEST['_gestor-validar-usuario-querystring']);
					
					if(existe(gestor_sessao_variavel("redirecionar-local"))){
						gestor_redirecionar(false,$querystring);
					} else {
						gestor_redirecionar('dashboard/');
					}
				}
			}
		//}
		
		perfil_usuario_signout();
	}
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Alterar dados do formulário de validação
	
	$queryString = gestor_querystring();
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-action#",$_GESTOR['url-raiz'].'validate-user/');
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#form-querystring#",$queryString);
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
}

function perfil_usuario_confirmacao_email(){
	global $_GESTOR;
	global $_CONFIG;
	
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
			$sessaoControle = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
			
			if($sessaoControle['pubID'] == $pubID){
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
					'id_usuarios',
				))
				,
				"tokens",
				"WHERE pubID='".$pubID."'"
			);
			
			if($tokens){
				$autorizacao = true;
				
				gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'],Array(
					'id' => $tokens[0]['id_usuarios'],
					'pubID' => $pubID,
				));
				
				$sessaoControle = gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao']);
				
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
		
		gestor_redirecionar('dashboard/');
	}
	
	// ===== Inclusão Módulo JS
	
	if($autorizacao){
		if(isset($sessaoControle)){
			banco_update
			(
				"email_confirmado=1",
				"usuarios",
				"WHERE id_usuarios='".$sessaoControle['id']."'"
			);
		}
	}
	
	// ===== Inclusão Módulo JS
	
	// req-120: o runtime da interface é escolhido pelo framework da requisição — as telas públicas
	// migradas rodam em Tailwind puro e o `interface.js` legado quebraria nelas (depende do Fomantic).
	interface_assets_incluir();
	gestor_pagina_javascript_incluir();
	
	// ===== Interface finalizar opções
}

// ==== Ajax

function perfil_usuario_ajax_opcao(){
	global $_GESTOR;

	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ===== AJAX da Rota de Segurança (req-030)

function perfil_usuario_ajax_seguranca_2fa_email_enviar(){
	global $_GESTOR;

	gestor_incluir_biblioteca('2fa');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$email = isset($usuario['email']) ? $usuario['email'] : '';

	if(!$id_usuarios || $email === ''){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-2fa-email-error'));
		return;
	}

	$ok = two_factor_email_send_code($id_usuarios, $email);

	$_GESTOR['ajax-json'] = Array(
		'status' => $ok ? 'success' : 'error',
		'message' => perfil_usuario_seguranca_var($ok ? 'security-2fa-email-sent' : 'security-2fa-email-error'),
	);
}

function perfil_usuario_ajax_seguranca_2fa_ativar(){
	global $_GESTOR;

	gestor_incluir_biblioteca('2fa');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$metodo = isset($_REQUEST['metodo']) ? strtolower($_REQUEST['metodo']) : 'app';
	$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';

	if(!$id_usuarios){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-2fa-code-invalid'));
		return;
	}

	$valido = false;
	if($metodo === 'email'){
		$valido = two_factor_email_validate($id_usuarios, $codigo);
	} else {
		$metodo = 'app';
		$dados = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios',
			'campos' => Array('two_factor_secret'),
			'extra' => "WHERE id_usuarios='".$id_usuarios."'",
		));
		$secret = ($dados && isset($dados['two_factor_secret'])) ? $dados['two_factor_secret'] : '';
		$valido = ($secret !== '') && two_factor_validate_code($secret, $codigo);
	}

	if(!$valido){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-2fa-code-invalid'));
		return;
	}

	// ===== Códigos de recuperação (req-119)
	//
	// Gerados NA ATIVAÇÃO e devolvidos em texto puro uma única vez. É a única janela em que isso
	// acontece: o banco guarda apenas hashes, e sem esses códigos a perda do aplicativo autenticador
	// significa perda irreversível da conta.

	gestor_incluir_biblioteca('usuario');

	// Sem a coluna (migração ainda não aplicada nesta instalação), o 2FA é ativado do mesmo jeito e
	// apenas não há códigos de recuperação — perder o 2FA inteiro por uma coluna seria muito pior.
	$recoveryCodes = usuario_recovery_codes_disponivel() ? usuario_recovery_codes_gerar(10) : Array();

	banco_update_campo('two_factor_enabled', '1');
	banco_update_campo('two_factor_type', $metodo);

	if($recoveryCodes){
		banco_update_campo('two_factor_recovery_codes', json_encode(array_map('usuario_recovery_code_hash',$recoveryCodes)));
	}

	banco_update_executar('usuarios', "WHERE id_usuarios='".$id_usuarios."'");

	$_GESTOR['ajax-json'] = Array(
		'status' => 'success',
		'message' => perfil_usuario_seguranca_var('security-2fa-enabled-ok'),
		'recovery_codes' => $recoveryCodes,
		'recovery_title' => perfil_usuario_seguranca_var('recovery-codes-title'),
		'recovery_help' => perfil_usuario_seguranca_var('recovery-codes-help'),
	);
}

function perfil_usuario_ajax_seguranca_2fa_desativar(){
	global $_GESTOR;

	gestor_incluir_biblioteca('2fa');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$senha = isset($_REQUEST['senha']) ? $_REQUEST['senha'] : '';
	$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';

	if(!$id_usuarios){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-2fa-disable-invalid'));
		return;
	}

	$dados = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('senha', 'two_factor_type', 'two_factor_secret'),
		'extra' => "WHERE id_usuarios='".$id_usuarios."'",
	));

	if(!$dados){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-2fa-disable-invalid'));
		return;
	}

	$senhaOk = isset($dados['senha']) && $dados['senha'] !== '' && password_verify($senha, $dados['senha']);

	$tipo = isset($dados['two_factor_type']) ? $dados['two_factor_type'] : '';
	if($tipo === 'email'){
		$codigoOk = two_factor_email_validate($id_usuarios, $codigo);
	} else {
		$secret = isset($dados['two_factor_secret']) ? $dados['two_factor_secret'] : '';
		$codigoOk = ($secret !== '') && two_factor_validate_code($secret, $codigo);
	}

	if(!$senhaOk || !$codigoOk){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-2fa-disable-invalid'));
		return;
	}

	banco_update_campo('two_factor_enabled', '0');
	banco_update_campo('two_factor_secret', '');
	banco_update_campo('two_factor_type', '');
	banco_update_executar('usuarios', "WHERE id_usuarios='".$id_usuarios."'");

	$_GESTOR['ajax-json'] = Array('status' => 'success', 'message' => perfil_usuario_seguranca_var('security-2fa-disabled-ok'));
}

function perfil_usuario_ajax_seguranca_social_vincular(){
	global $_GESTOR;

	gestor_incluir_biblioteca('oauth');

	$provider = isset($_REQUEST['provider']) ? strtolower($_REQUEST['provider']) : '';
	if($provider !== 'google' && $provider !== 'meta'){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-social-not-configured'));
		return;
	}

	$url = oauth_redirect_url($provider);
	if(!$url){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('security-social-not-configured'));
		return;
	}

	// Marcar na sessão que este fluxo OAuth é de VÍNCULO (não login) do usuário atual.
	$usuario = gestor_usuario();
	gestor_sessao_variavel('oauth_action', 'link');
	gestor_sessao_variavel('oauth_link_user', isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0);

	$_GESTOR['ajax-json'] = Array('status' => 'success', 'redirect' => $url);
}

function perfil_usuario_ajax_seguranca_social_desvincular(){
	global $_GESTOR;

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$provider = isset($_REQUEST['provider']) ? strtolower($_REQUEST['provider']) : '';

	if(!$id_usuarios || ($provider !== 'google' && $provider !== 'meta')){
		$_GESTOR['ajax-json'] = Array('status' => 'error');
		return;
	}

	banco_delete('usuarios_provedores', "WHERE usuario_id='".$id_usuarios."' AND provider_name='".banco_escape_field($provider)."'");

	$_GESTOR['ajax-json'] = Array('status' => 'success', 'message' => perfil_usuario_seguranca_var('security-social-unlinked-ok'));
}

// ==== Códigos de recuperação do 2FA (req-119) ====

/**
 * Tenta consumir um código de recuperação do usuário.
 *
 * A lista é regravada SEM o código usado antes de o login prosseguir: um código de uso único que
 * sobrevive ao uso não é código de recuperação, é uma segunda senha permanente.
 *
 * @param int $id_usuarios Usuário em processo de login.
 * @param string $codigo Código digitado.
 *
 * @return bool True quando o código era válido e foi consumido.
 */
function perfil_usuario_recovery_code_consumir($id_usuarios,$codigo){
	gestor_incluir_biblioteca('usuario');

	$id_usuarios = (int)$id_usuarios;

	if($id_usuarios <= 0 || trim((string)$codigo) === '') return false;
	if(!usuario_recovery_codes_disponivel()) return false;

	$dados = banco_select(Array(
		'unico' => true,
		'tabela' => 'usuarios',
		'campos' => Array('two_factor_recovery_codes'),
		'extra' => "WHERE id_usuarios='".$id_usuarios."'",
	));

	if(!$dados || empty($dados['two_factor_recovery_codes'])) return false;

	$hashes = json_decode((string)$dados['two_factor_recovery_codes'],true);

	if(!is_array($hashes)) return false;

	$resultado = usuario_recovery_code_consumir($codigo,$hashes);

	if(!$resultado['valido']) return false;

	banco_update_campo('two_factor_recovery_codes',json_encode(array_values($resultado['restantes'])));
	banco_update_executar('usuarios',"WHERE id_usuarios='".$id_usuarios."'");

	return true;
}

// ==== Chaves de API pessoais (req-119) ====

/**
 * Diz se o usuário corrente pode emitir Personal Access Tokens.
 *
 * A política é a MESMA da API (`AUTH_API_ALLOWED_PROFILES`), e não uma permissão nova: um token
 * emitido por quem não pode usar a API seria uma credencial inútil — ou, pior, um caminho lateral
 * para contornar a política.
 */
function perfil_usuario_api_tokens_habilitado($usuario){
	$perfil = isset($usuario['id_usuarios_perfis']) ? $usuario['id_usuarios_perfis'] : null;

	return ($perfil !== null) && perfil_usuario_api_perfil_permitido($perfil);
}

function perfil_usuario_ajax_api_token_gerar(){
	global $_GESTOR;

	gestor_incluir_biblioteca('usuario');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;

	if(!$id_usuarios || !perfil_usuario_api_tokens_habilitado($usuario) || !usuario_api_tokens_disponivel()){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('api-tokens-not-allowed'));
		return;
	}

	$nome = isset($_REQUEST['nome']) ? trim((string)$_REQUEST['nome']) : '';

	if($nome === ''){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('api-tokens-name-required'));
		return;
	}

	$dias = isset($_REQUEST['expiracao']) ? (int)$_REQUEST['expiracao'] : 0;
	$escopos = isset($_REQUEST['escopos']) ? (array)$_REQUEST['escopos'] : Array();

	$resultado = usuario_api_token_gerar($id_usuarios,$nome,$escopos,($dias > 0 ? $dias : null));

	if(!$resultado){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('api-tokens-create-error'));
		return;
	}

	// O token em texto puro trafega UMA única vez, aqui. Não há endpoint que o recupere.
	$_GESTOR['ajax-json'] = Array(
		'status' => 'success',
		'message' => perfil_usuario_seguranca_var('api-tokens-created-ok'),
		'token' => $resultado['token'],
		'prefixo' => $resultado['prefixo'],
	);
}

function perfil_usuario_ajax_api_token_revogar(){
	global $_GESTOR;

	gestor_incluir_biblioteca('usuario');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$id_token = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

	if(!$id_usuarios || !$id_token || !usuario_api_token_revogar($id_token,$id_usuarios)){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('api-tokens-revoke-error'));
		return;
	}

	$_GESTOR['ajax-json'] = Array('status' => 'success', 'message' => perfil_usuario_seguranca_var('api-tokens-revoked-ok'));
}

// ==== Sessões ativas (req-118) ====

/**
 * Revoga uma sessão específica do usuário autenticado.
 *
 * O `pubID` chega do cliente, então a remoção é sempre escopada ao usuário da sessão corrente
 * (`usuario_sessao_revogar` exige o `id_usuarios` no WHERE). Revogar o PRÓPRIO token é recusado
 * aqui: derrubaria o usuário no meio da operação, e para sair existe o `signout`.
 */
function perfil_usuario_ajax_sessoes_revogar(){
	global $_GESTOR;

	gestor_incluir_biblioteca('usuario');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$pubID = isset($_REQUEST['pubID']) ? trim((string)$_REQUEST['pubID']) : '';
	$tokenAtual = isset($_GESTOR['usuario-token-id']) ? (string)$_GESTOR['usuario-token-id'] : '';

	if(!$id_usuarios || $pubID === '' || ($tokenAtual !== '' && hash_equals($tokenAtual,$pubID))){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('sessions-revoke-error'));
		return;
	}

	if(!usuario_sessao_revogar($pubID,$id_usuarios)){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('sessions-revoke-error'));
		return;
	}

	$_GESTOR['ajax-json'] = Array('status' => 'success', 'message' => perfil_usuario_seguranca_var('sessions-revoked-ok'));
}

/**
 * Encerra todas as sessões do usuário autenticado, exceto a da requisição corrente.
 */
function perfil_usuario_ajax_sessoes_revogar_outras(){
	global $_GESTOR;

	gestor_incluir_biblioteca('usuario');

	$usuario = gestor_usuario();
	$id_usuarios = isset($usuario['id_usuarios']) ? (int)$usuario['id_usuarios'] : 0;
	$tokenAtual = isset($_GESTOR['usuario-token-id']) ? (string)$_GESTOR['usuario-token-id'] : '';

	if(!$id_usuarios || !usuario_sessoes_revogar_outras($tokenAtual,$id_usuarios)){
		$_GESTOR['ajax-json'] = Array('status' => 'error', 'message' => perfil_usuario_seguranca_var('sessions-revoke-error'));
		return;
	}

	$_GESTOR['ajax-json'] = Array('status' => 'success', 'message' => perfil_usuario_seguranca_var('sessions-revoked-others-ok'));
}

// ==== Start

function perfil_usuario_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': perfil_usuario_ajax_opcao(); break;
			case 'seguranca-2fa-email-enviar': perfil_usuario_ajax_seguranca_2fa_email_enviar(); break;
			case 'seguranca-2fa-ativar': perfil_usuario_ajax_seguranca_2fa_ativar(); break;
			case 'seguranca-2fa-desativar': perfil_usuario_ajax_seguranca_2fa_desativar(); break;
			case 'seguranca-social-vincular': perfil_usuario_ajax_seguranca_social_vincular(); break;
			case 'seguranca-social-desvincular': perfil_usuario_ajax_seguranca_social_desvincular(); break;
			case 'sessoes-revogar': perfil_usuario_ajax_sessoes_revogar(); break;
			case 'sessoes-revogar-outras': perfil_usuario_ajax_sessoes_revogar_outras(); break;
			case 'api-token-gerar': perfil_usuario_ajax_api_token_gerar(); break;
			case 'api-token-revogar': perfil_usuario_ajax_api_token_revogar(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		perfil_usuario_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'oauth-authenticate': perfil_usuario_oauth_authenticate(); break;
			case 'oauth-authenticate-2fa': perfil_usuario_oauth_authenticate_2fa(); break;
			case 'signin': perfil_usuario_signin(); break;
			case 'signin-2fa': perfil_usuario_signin_2fa(); break;
			case 'social-login': perfil_usuario_social_login(); break;
			case 'oauth-callback': perfil_usuario_oauth_callback(); break;
			case 'signup': perfil_usuario_signup(); break;
			case 'editar': perfil_usuario_editar(); break;
			case 'forgot-password': perfil_usuario_forgot_password(); break;
			case 'forgot-password-confirmation': perfil_usuario_forgot_password_confirmation(); break;
			case 'redefine-password': perfil_usuario_redefine_password(); break;
			case 'redefine-password-confirmation': perfil_usuario_redefine_password_confirmation(); break;
			case 'signout': perfil_usuario_signout(); break;
			case 'area-restrita': perfil_usuario_area_restrita(); break;
			case 'validar-usuario': perfil_usuario_validar_usuario(); break;
			case 'confirmacao-email': perfil_usuario_confirmacao_email(); break;
		}
		
		interface_finalizar();
	}
}

perfil_usuario_start();

?>
