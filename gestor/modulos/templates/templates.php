<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'templates';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.17',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_templates',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_templates',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
		'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
	),
	'modelos' => Array(
		'layouts' => Array(
			'id_modulos' => '13',
			'id_categorias_pai' => '17',
		),
		'paginas' => Array(
			'id_modulos' => '13',
			'id_categorias_pai' => '18',
		),
		'componentes' => Array(
			'id_modulos' => '13',
			'id_categorias_pai' => '29',
		),
	),
	'resources' => [
		'pt-br' => [
			'layouts' => [],
			'pages' => [
			    [
			        'name' => 'Templates',
			        'id' => 'templates',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'templates/',
			        'type' => 'system',
			        'option' => 'listar',
			        'root' => true,
			        'version' => '0',
			        'checksum' => [
			            'html' => '',
			            'css' => '',
			        ],
			    ],
			    [
			        'name' => 'Templates - Adicionar',
			        'id' => 'templates-adicionar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'templates/adicionar/',
			        'type' => 'system',
			        'option' => 'adicionar',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => '82278fec463f44090e17ff3ba91c8d66',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			        ],
			    ],
			    [
			        'name' => 'Templates - Editar',
			        'id' => 'templates-editar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'templates/editar/',
			        'type' => 'system',
			        'option' => 'editar',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => 'dd982492f3ddae7e851b21b30178e074',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			        ],
			    ],
			    [
			        'name' => 'Templates - Editar Índice',
			        'id' => 'templates-editar-Indice',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'templates/editar-indice/',
			        'type' => 'system',
			        'option' => 'editar-indice',
			        'version' => '0',
			        'checksum' => [
			            'html' => '',
			            'css' => '',
			        ],
			    ],
			    [
			        'name' => 'Templates - Seletores',
			        'id' => 'templates-seletores',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'templates/seletores/',
			        'type' => 'system',
			        'option' => 'seletores-listar',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => 'd0ff2b1d359c05ae090514b054ad4b2c',
			            'css' => '019c4b82a45f71000961e23d3e958779',
			        ],
			    ],
			    [
			        'name' => 'Templates - Atualizações',
			        'id' => 'templates-atualizacoes',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'templates/atualizacoes/',
			        'type' => 'system',
			        'option' => 'atualizacoes',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => 'cddd25ebffeb9cf4ed02edb027b2d152',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			        ],
			    ],
			    [
			        'name' => 'Templates - Ativar',
			        'id' => 'templates-ativar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'templates/ativar/',
			        'type' => 'system',
			        'option' => 'ativar',
			        'version' => '0',
			        'checksum' => [
			            'html' => '',
			            'css' => '',
			        ],
			    ],
			    [
			        'name' => 'Templates - Pré-Visualização',
			        'id' => 'templates-pre-visualizacao',
			        'layout' => 'layout-pagina-padrao',
			        'path' => 'templates/preview/',
			        'type' => 'system',
			        'option' => 'preview',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => '7d56049c47c89d1530a57c128b7240f5',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			        ],
			    ],
			],
			'components' => [],
		],
	],
);

// ===== Funções Auxiliares

function templates_seletores_lista($params = false){
	global $_GESTOR;
	
	// ===== Variáveis Padrões
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$padrao = false;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// pagina - String - Obrigatório - Página onde será implementada lista de templates.
	// modelo - String - Obrigatório - Modelo de templates.
	// categoria_id - String - Obrigatório - Identificador da categoria de templates.
	
	// ===== 
	
	// ===== Opções conforme necessidade.
	
	if(isset($_REQUEST['padrao'])){
		$padrao = true;
	}
	
	$categorias = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
			'id_categorias',
		))
		,
		"categorias",
		"WHERE status!='D'"
		." AND id='".$categoria_id."'"
	);
	
	if(!$categorias){
		return $pagina;
	}
	
	$id_categorias = $categorias[0]['id_categorias'];
	
	// ===== Pegar templates da categoria_id do templates e hosts_templates conforme opção 'padrao'.
	
	$templates_proc = Array();
	
	$templates = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_templates',
			'id',
			'nome',
			'id_arquivos_Imagem',
			'data_modificacao',
			'padrao',
		))
		,
		"templates",
		"WHERE status!='D'"
		." AND id_categorias='".$id_categorias."'"
		." ORDER BY padrao DESC,id_templates DESC"
	);
	
	if(!$padrao){
		$hosts_templates = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_hosts_templates',
				'id',
				'nome',
				'id_hosts_arquivos_Imagem',
				'data_modificacao',
				'padrao',
				'ativo',
			))
			,
			"hosts_templates",
			"WHERE status!='D'"
			." AND id_categorias='".$id_categorias."'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
			." ORDER BY padrao DESC,id_hosts_templates DESC"
		);
		
		if($hosts_templates){
			$templates_proc = array_merge($templates,$hosts_templates);
		} else {
			$templates_proc = $templates;
		}
	} else {
		$templates_proc = $templates;
	}
	
	// ===== Verificar se um hosts_templates está ativo.
	
	if($hosts_templates)
	foreach($hosts_templates as $hosts_template){
		if($hosts_template['ativo']){
			$templateAtivoID = $hosts_template['id_hosts_templates'];
		}
	}
	
	// ===== Processar templates.
	
	if(count($templates_proc) > 0){
		// ===== Caso haja templates, iniciar variáveis.
		
		$layout = gestor_componente(Array(
			'id' => 'templates-seletores-lista',
		));
		
		// ===== Trocar os controles caso seja página normal ou iframe.
		
		if($_GESTOR['paginaIframe']){
			$cel_nome = 'btn-copy'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			$cel_nome = 'btn-active'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			$cel_nome = 'btn-activate'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		} else {
			$cel_nome = 'btn-select'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			switch($categoria_id){
				case 'postagens':
				case 'servicos':
					$cel_nome = 'btn-active'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					$cel_nome = 'btn-activate'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				break;
			}
		}
		
		// ===== Célula do card.
		
		$cel_nome = 'card'; $cel[$cel_nome] = modelo_tag_val($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		// ===== Varrer todos os registros do arquivo
		
		foreach($templates_proc as $item){
			// ===== Popular célula do arquivo
			
			$cel_aux = $cel[$cel_nome];
			
			// ===== Iniciar variáveis de cada registro.
			
			$caminho_mini = '';
			
			if(existe($item['id_templates'])){
				$templateIdNumerico = $item['id_templates'];
				$templateTipo = 'gestor';
				$id_arquivos = $item['id_arquivos_Imagem'];
				$tipo = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'template-gestor-label'));
				
				// ===== Se existir pegar o caminho do arquivo mini.
				
				if(existe($id_arquivos)){
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'caminho_mini',
						)),
						"arquivos",
						"WHERE id_arquivos='".$id_arquivos."'"
					);
					
					if($resultado){
						if(existe($resultado[0]['caminho_mini'])){
							$caminho_mini = $resultado[0]['caminho_mini'];
						}
					}
				}
				
				// ===== Domínio completo do Gestor.
				
				$dominio = $_GESTOR['url-full'];
				
				// ===== Se existir algum hosts_templates ativo, é porque este não está ativo.
				
				if(isset($templateAtivoID)){
					$cel_nome_2 = 'btn-active'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
				} else {
					$cel_nome_2 = 'btn-activate'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
				}
			} else {
				$templateIdNumerico = $item['id_hosts_templates'];
				$templateTipo = 'hosts';
				$id_hosts_arquivos = $item['id_hosts_arquivos_Imagem'];
				$tipo = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'template-custom-label'));
				
				// ===== Se existir pegar o caminho do arquivo mini.
				
				if(existe($id_hosts_arquivos)){
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'caminho_mini',
						)),
						"hosts_arquivos",
						"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
					);
					
					if($resultado){
						if(existe($resultado[0]['caminho_mini'])){
							$caminho_mini = $resultado[0]['caminho_mini'];
						}
					}
				}
				
				// ===== Carregar domínio do host.
				
				gestor_incluir_biblioteca('host');
				$dominio = host_url(Array(
					'opcao' => 'full',
				));
				
				// ===== Se existir algum hosts_templates ativo, verificar se é este e colocar o botão correto.
				
				if(isset($templateAtivoID)){
					if($templateAtivoID == $templateIdNumerico){
						$cel_nome_2 = 'btn-activate'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
					} else {
						$cel_nome_2 = 'btn-active'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
					}
				} else {
					$cel_nome_2 = 'btn-active'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
				}
			}
			
			$templateId = $item['id'];
			$nome = $item['nome'];
			$data_modificacao = $item['data_modificacao'];
			$padraoItem = $item['padrao'];
			
			// ===== Imagem Mini ou Imagem Referência
			
			if(existe($caminho_mini)){
				$imgSrc = $dominio . $caminho_mini;
			} else {
				$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
			}
			
			$data = interface_formatar_dado(Array('dado' => $data_modificacao, 'formato' => 'dataHora'));
			
			// ===== 
			
			if(!existe($padraoItem)){
				$cel_nome_2 = 'padrao'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome_2.' < -->','<!-- '.$cel_nome_2.' > -->','');
			} else {
				$cel_aux = modelo_var_troca($cel_aux,"#padrao-texto#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'default-text')));
			}
			
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#template-id#",$templateId);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#template-tipo#",$templateTipo);
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#modelo#",$modelo);
			$cel_aux = modelo_var_troca($cel_aux,"#img-src#",$imgSrc);
			$cel_aux = modelo_var_troca($cel_aux,"#nome#",$nome);
			$cel_aux = modelo_var_troca($cel_aux,"#data#",$data);
			$cel_aux = modelo_var_troca($cel_aux,"#tipo#",$tipo);
			
			if($_GESTOR['paginaIframe']){
				$cel_aux = modelo_var_troca($cel_aux,"#template-data#",htmlentities(json_encode(Array(
					'templateId' => $templateId,
					'templateTipo' => $templateTipo,
					'imgSrc' => $imgSrc,
					'nome' => $nome,
					'data' => $data,
					'tipo' => $tipo,
				)), ENT_QUOTES, 'UTF-8'));
			}
			
			$layout = modelo_var_in($layout,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		$layout = modelo_var_troca($layout,'<!-- '.$cel_nome.' -->','');
		
		// ===== finalizar e incluir todos os registros no layout.
	
		$pagina = modelo_var_troca($pagina,"#seletores-lista#",(isset($layout) ? $layout : ''));
	}
	
	return $pagina;

}

// ===== Funções Principais

function templates_adicionar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificar modelo.
	
	$modelo = banco_escape_field($_REQUEST['modelo']);
	
	// ===== Senão for enviado o modelo, alertar e redirecionar raiz do módulo.
	
	if(!isset($modulo['modelos'][$modelo])){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-mandatory-model'))
		));
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Gravar registro no Banco
	
	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'categoria',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-label')),
				)
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
			),
		));
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_categorias_pai"; $campo_valor = $modulo['modelos'][$modelo]['id_categorias_pai']; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$campo_nome = "id_categorias"; $post_nome = 'categoria'; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id_hosts_arquivos_Imagem"; $post_nome = 'imagem'; 				if($_REQUEST[$post_nome] && $_REQUEST[$post_nome] != '-1')		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "padrao"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome]){		$campos[] = Array($campo_nome,'1',true); $padraoFlag = true; }
		$campo_nome = "html"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css"; $post_nome = $campo_nome; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		// ===== Se definido o campo padrão, remover o padrão da mesma categoria se houver de outro template.
		
		if(isset($padraoFlag)){
			banco_update
			(
				"padrao=NULL",
				$modulo['tabela']['nome'],
				"WHERE id_categorias='".banco_escape_field($_REQUEST['categoria'])."'"
				." AND id_hosts='".$_GESTOR['host-id']."'"
			);
		}
		
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
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id . '&modelo='.$modelo);
	}
	
	// ===== Alterar título da página.
	
	$categorias = banco_select_name(
		banco_campos_virgulas(Array(
			'nome',
		)),
		"categorias",
		"WHERE id_categorias='".$modulo['modelos'][$modelo]['id_categorias_pai']."'"
	);
	
	$_GESTOR['pagina#titulo'] .= ' ' . $categorias[0]['nome'];
	
	// ===== Alterar modelo no formulário.
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#modelo#",$modelo);
	
	// ===== Copiar dados de outro template.
	
	$css = '';
	$html = '';
	
	if(isset($_REQUEST['tipo']) && isset($_REQUEST['id'])){
		$tipo = banco_escape_field($_REQUEST['tipo']);
		$id = banco_escape_field($_REQUEST['id']);
		
		switch($tipo){
			case 'gestor':
				$templates = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_categorias',
						'html',
						'css',
					))
					,
					"templates",
					"WHERE status='A'"
					." AND id='".$id."'"
				);
			break;
			case 'hosts':
				$templates = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_categorias',
						'html',
						'css',
					))
					,
					"hosts_templates",
					"WHERE status='A'"
					." AND id='".$id."'"
					." AND id_hosts='".$_GESTOR['host-id']."'"
				);
			break;
		}
		
		if(isset($templates)){
			if($templates){
				$html = $templates[0]['html'];
				$css = $templates[0]['css'];
				$id_categorias = $templates[0]['id_categorias'];
			}
		}
	}
	
	// ===== Variaveis globais alterar.
	
	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];
	
	$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
	$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
	
	$variaveisTrocarDepois['pagina-css'] = $css;
	$variaveisTrocarDepois['pagina-html'] = $html;
	
	// ===== Inclusão do CodeMirror
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/theme/tomorrow-night-bright.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.css" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/selection/active-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/searchcursor.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/search.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/scroll/annotatescrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/jump-to-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/edit/matchbrackets.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/xml/xml.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/css/css.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/htmlmixed/htmlmixed.js"></script>';
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'variaveisTrocarDepois' => $variaveisTrocarDepois,
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'categoria',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'templates_layouts',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-template-layout-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'category',
					'nome' => 'categoria',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'three column',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-placeholder')),
					'tabela' => Array(
						'nome' => 'categorias',
						'campo' => 'nome',
						'id_numerico' => 'id_categorias',
						'where' => "id_modulos='".$modulo['modelos'][$modelo]['id_modulos']."' AND id_categorias_pai='".$modulo['modelos'][$modelo]['id_categorias_pai']."'",
						'id_selecionado' => (isset($id_categorias) ? $id_categorias : null),
					),
				),
				Array(
					'tipo' => 'imagepick-hosts',
					'id' => 'thumbnail',
					'nome' => 'imagem',
				)
			),
		)
	);
}

function templates_editar_indice(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do registro
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Selecionar dados do banco de dados e verificar se é um template de página ou template de layout.
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas(Array(
			'id_categorias_pai',
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND id_hosts='".$_GESTOR['host-id']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		if(isset($retorno_bd['id_categorias_pai'])){
			foreach($modulo['modelos'] as $key => $model){
				if($model['id_categorias_pai'] == $retorno_bd['id_categorias_pai']){
					$modelo = $key;
					break;
				}
			}
		}
		
		// ===== Senão for enviado o modelo, alertar e redirecionar raiz do módulo.
		
		if(isset($modelo)){
			gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id . '&modelo='.$modelo);
		} else {
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-mandatory-model'))
			));
		}
	}
	
	gestor_redirecionar_raiz();
}

function templates_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Identificar modelo.
	
	$modelo = banco_escape_field($_REQUEST['modelo']);
	
	// ===== Senão for enviado o modelo, alertar e redirecionar raiz do módulo.
	
	if(!isset($modulo['modelos'][$modelo])){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-mandatory-model'))
		));
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'id_categorias',
		'html',
		'css',
		'id_hosts_arquivos_Imagem',
		'padrao',
		'ativo',
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'categoria',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-label')),
				)
			)
		));
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'"
				." AND id_hosts='".$_GESTOR['host-id']."'",
		);
		
		$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se mudar o nome, mudar o identificador do registro
		
		if(isset($alterar_id)){
			$id_novo = banco_identificador(Array(
				'id' => banco_escape_field($_REQUEST["nome"]),
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campo' => $modulo['tabela']['id'],
					'id_nome' => $modulo['tabela']['id_numerico'],
					'id_valor' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
				),
			));
			
			$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		
		// ===== Atualização dos demais campos.
		
		$campo_nome = "id_categorias"; $request_name = 'categoria'; $alteracoes_name = 'category'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]),'tabela' => Array(
				'nome' => 'categorias',
				'campo' => 'nome',
				'id_numerico' => 'id_categorias',
			));}
		
		$campo_nome = "id_hosts_arquivos_Imagem"; $request_name = 'imagem'; $alteracoes_name = 'thumbnail'; if($_REQUEST[$request_name] == '-1'){$_REQUEST[$request_name] = NULL;} if(banco_select_campos_antes($campo_nome) != $_REQUEST[$request_name]){$editar['dados'][] = $campo_nome."=".(isset($_REQUEST[$request_name]) ? "'" . banco_escape_field($_REQUEST[$request_name]) . "'" : "NULL"); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => (isset($_REQUEST[$request_name]) ? banco_escape_field($_REQUEST[$request_name]) : NULL),'tabela' => Array(
				'nome' => 'arquivos',
				'campo' => 'nome',
				'id_numerico' => 'id_arquivos',
			));}
			
		$campo_nome = "padrao"; $request_name = $campo_nome; $alteracoes_name = 'default'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? '1' : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($_REQUEST[$request_name]) ? '1' : 'NULL'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'filtro' => 'checkbox','valor_antes' => (banco_select_campos_antes($campo_nome) ? '1' : '0'),'valor_depois' => (isset($_REQUEST[$request_name]) ? '1' : '0')); $padraoFlag = true; }
		
		$campo_nome = "html"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label'); if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => banco_select_campos_antes($campo_nome));}}
		$campo_nome = "css"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label'); if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => banco_select_campos_antes($campo_nome));}}
		
		// ===== Se definido o campo padrão, remover o padrão da mesma categoria se houver de outro template.
		
		if(isset($padraoFlag)){
			banco_update
			(
				"padrao=NULL",
				$modulo['tabela']['nome'],
				"WHERE id_categorias='".banco_escape_field($_REQUEST['categoria'])."'"
				." AND id_hosts='".$_GESTOR['host-id']."'"
			);
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
			
			// ===== Incluir no backup os campos.
			
			if(isset($backups)){
				foreach($backups as $backup){
					interface_backup_campo_incluir(Array(
						'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
						'versao' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['versao'])),
						'campo' => $backup['campo'],
						'valor' => $backup['valor'],
					));
				}
			}
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id) . '&modelo='.$modelo);
	}
	
	// ===== Inclusão do CodeMirror
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/theme/tomorrow-night-bright.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.css" />';
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.css" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/selection/active-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/searchcursor.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/search.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/scroll/annotatescrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/jump-to-line.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/edit/matchbrackets.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/xml/xml.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/css/css.js"></script>';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/htmlmixed/htmlmixed.js"></script>';
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Alterar título da página.
	
	$categorias = banco_select_name(
		banco_campos_virgulas(Array(
			'nome',
		)),
		"categorias",
		"WHERE id_categorias='".$modulo['modelos'][$modelo]['id_categorias_pai']."'"
	);
	
	$modeloTitulo = $categorias[0]['nome'];
	
	$_GESTOR['pagina#titulo'] .= ' ' . $modeloTitulo;
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND id_hosts='".$_GESTOR['host-id']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$versao = (isset($retorno_bd['versao']) ? $retorno_bd['versao'] : '');
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$id_categorias = (isset($retorno_bd['id_categorias']) ? $retorno_bd['id_categorias'] : '');
		$id_hosts_arquivos = (isset($retorno_bd['id_hosts_arquivos_Imagem']) ? $retorno_bd['id_hosts_arquivos_Imagem'] : '');
		$padrao = (isset($retorno_bd['padrao']) ? true : false);
		$ativo = (isset($retorno_bd['ativo']) ? true : false);
		$html = (isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#modelo#',$modelo);
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#padrao#',($padrao ? 'checked' : ''));
		
		// ===== Id da categoria.
		
		$categorias = banco_select(Array(
			'unico' => true,
			'tabela' => 'categorias',
			'campos' => Array(
				'id',
			),
			'extra' => 
				"WHERE status='A'"
				." AND id_categorias='".$id_categorias."'"
		));
		
		$categoria_id = $categorias['id'];
		
		// ===== Verificar se o template é do tipo para ativação.
		
		gestor_incluir_biblioteca('pagina');
		
		switch($categoria_id){
			case 'postagens':
			case 'servicos':
				$cel_nome = 'template-ativacao'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			break;
			default:
				$ativacao = true;
		}
		
		// ===== Verificar estado da ativação e mudar botões.
		
		if(isset($ativacao)){
			if($ativo){
				// ===== Remover a célula com o botão ativar.
				
				$cel_nome = 'btn-activate'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				
				switch($modelo){
					case 'layouts':
						// ===== Selecionar o hosts_layouts para verificar se está atualizada a versão.
						
						$hosts_layouts = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_layouts',
							'campos' => Array(
								'template_versao',
							),
							'extra' => 
								"WHERE template_id='".'hosts_'.$id."'"
								." AND template_padrao IS NOT NULL"
								." AND id_hosts='".$_GESTOR['host-id']."'"
						));
						
						// ===== Comparar versões e mostrar botão de atualizado ou atualizar.
						
						if((int)$hosts_layouts['template_versao'] < (int)$versao){
							$cel_nome = 'btn-updated'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
						} else {
							$cel_nome = 'btn-update'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
						}
					break;
					case 'paginas':
						// ===== Selecionar o hosts_paginas para verificar se está atualizada a versão.
						
						$hosts_paginas = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_paginas',
							'campos' => Array(
								'template_versao',
							),
							'extra' => 
								"WHERE template_id='".'hosts_'.$id."'"
								." AND template_padrao IS NOT NULL"
								." AND id_hosts='".$_GESTOR['host-id']."'"
						));
						
						// ===== Comparar versões e mostrar botão de atualizado ou atualizar.
						
						if((int)$hosts_paginas['template_versao'] < (int)$versao){
							$cel_nome = 'btn-updated'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
						} else {
							$cel_nome = 'btn-update'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
						}
					break;
					case 'componentes':
						// ===== Selecionar o hosts_componentes para verificar se está atualizada a versão.
						
						$hosts_componentes = banco_select(Array(
							'unico' => true,
							'tabela' => 'hosts_componentes',
							'campos' => Array(
								'template_versao',
							),
							'extra' => 
								"WHERE template_id='".'hosts_'.$id."'"
								." AND template_padrao IS NOT NULL"
								." AND id_hosts='".$_GESTOR['host-id']."'"
						));
						
						// ===== Comparar versões e mostrar botão de atualizado ou atualizar.
						
						if((int)$hosts_componentes['template_versao'] < (int)$versao){
							$cel_nome = 'btn-updated'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
						} else {
							$cel_nome = 'btn-update'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
						}
					break;
				}
			} else {
				// ===== Remover a célula com o botão ativo.
				
				$cel_nome = 'btn-active'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'btn-updated'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
				$cel_nome = 'btn-update'; $cel[$cel_nome] = pagina_celula($cel_nome,false,true);
			}
			
			// ===== Colocar a categoria_id, o template id e tipo no link ativar ou atualizar.
			
			pagina_trocar_variavel_valor('#template-tipo#','hosts',true);
			pagina_trocar_variavel_valor('#template-id#',$id,true);
			pagina_trocar_variavel_valor('#categoria-id#',$categoria_id,true);
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
		$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
		
		$variaveisTrocarDepois['pagina-css'] = $css;
		$variaveisTrocarDepois['pagina-html'] = $html;
		
		// ===== Dropdown com todos os backups
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-html-backup#',interface_backup_campo_select(Array(
			'campo' => 'html',
			'callback' => 'templatesBackupCampo',
			'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
		)));
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#pagina-css-backup#',interface_backup_campo_select(Array(
			'campo' => 'css',
			'callback' => 'templatesBackupCampo',
			'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
		)));
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'variaveisTrocarDepois' => $variaveisTrocarDepois,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
		'botoes' => Array(
			'adicionar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/?modelo='.$modelo,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')) . ' ' . $modeloTitulo,
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => ($modelo == 'paginas' ? 'tooltip-button-insert-page' : 'tooltip-button-insert-layout'))),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'preview' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/preview/?modelo='.$modelo.'&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-preview')) . ' ' . $modeloTitulo,
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => ($modelo == 'paginas' ? 'tooltip-button-preview-page' : 'tooltip-button-preview-layout'))),
				'icon' => 'external alternate',
				'cor' => 'orange',
				'target' => 'preview',
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id . '&modelo='.$modelo),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash' ),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown' ),
			),
			'excluir' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-delete')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'cor' => 'red',
			),
		),
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'categoria',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'category',
					'nome' => 'categoria',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'three column',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-placeholder')),
					'tabela' => Array(
						'nome' => 'categorias',
						'campo' => 'nome',
						'id_numerico' => 'id_categorias',
						'where' => "id_modulos='".$modulo['modelos'][$modelo]['id_modulos']."' AND id_categorias_pai='".$modulo['modelos'][$modelo]['id_categorias_pai']."'",
						'id_selecionado' => $id_categorias,
					),
				),
				Array(
					'tipo' => 'imagepick-hosts',
					'id' => 'thumbnail',
					'nome' => 'imagem',
					'id_hosts_arquivos' => $id_hosts_arquivos,
				)
			),
		)
	);
}

function templates_seletores_listar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$JSVars = Array();
	
	// ===== Identificar modelo.
	
	$modelo = banco_escape_field($_REQUEST['modelo']);
	$JSVars['modelo'] = $modelo;
	
	// ===== Senão for enviado o modelo, alertar e redirecionar raiz do módulo.
	
	if(!isset($modulo['modelos'][$modelo])){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-mandatory-model'))
		));
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Incluir javascript
	
	gestor_pagina_javascript_incluir();
	
	// ===== Se for somente padrão
	
	if(isset($_REQUEST['padrao'])){
		$padrao = true;
		$JSVars['padrao'] = true;
	}
	
	// ===== Se for iframe não mostrar categoria select.
	
	if($_GESTOR['paginaIframe']){
		
	} else {
		$JSVars['optionsContShow'] = true;
	}
	
	// ===== Categoria atual
	
	if(isset($_REQUEST['categoria_id'])){
		$categoria_id = banco_escape_field($_REQUEST['categoria_id']);
	}
	
	// ===== Interface finalizar opções
	
	if(isset($categoria_id)){
		$_GESTOR['pagina'] = templates_seletores_lista(Array(
			'pagina' => $_GESTOR['pagina'],
			'modelo' => $modelo,
			'categoria_id' => $categoria_id,
		));
	}
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#without-results-cont#",gestor_componente(Array(
		'id' => 'interface-listar-sem-registros',
	)));
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#paginaIframe#",($_GESTOR['paginaIframe'] ? '?paginaIframe=sim' : ''));
	
	// ===== Alterar título da página.
	
	$categorias = banco_select_name(
		banco_campos_virgulas(Array(
			'nome',
		)),
		"categorias",
		"WHERE id_categorias='".$modulo['modelos'][$modelo]['id_categorias_pai']."'"
	);
	
	$modeloTitulo = $categorias[0]['nome'];
	
	$_GESTOR['pagina#titulo'] .= ($padrao ? ' ' . gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'default-2-text')) : '').' ' . $modeloTitulo;
	
	// ===== 
	
	$_GESTOR['javascript-vars']['templates'] = $JSVars;
	
	// ===== Filtrar plugins inativos da conta.
	
	$pluginsInvativosSQL = '';
	
	$hosts_plugins = banco_select(Array(
		'tabela' => 'hosts_plugins',
		'campos' => Array(
			'id_hosts_plugins',
			'plugin',
			'habilitado',
			'versao_num',
		),
		'extra' => 
			"WHERE id_hosts='".$_GESTOR['host-id']."'"
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
	
	if($plugins){
		foreach($plugins as $plugin){
			$naoHabilitado = true;
			
			if($hosts_plugins){
				foreach($hosts_plugins as $hosts_plugin){
					if(
						$plugin['id'] == $hosts_plugin['plugin'] &&
						$hosts_plugin['habilitado']
					){
						$naoHabilitado = false;
						break;
					}
				}
			}
			
			if($naoHabilitado){
				$pluginsInvativosSQL .= (existe($pluginsInvativosSQL) ? ' AND ':'')."NOT (plugin <=> '".$plugin['id']."')";
			}
		}
		
		if(existe($pluginsInvativosSQL)){
			$pluginsInvativosSQL = ' AND ('.$pluginsInvativosSQL.')';
		}
	}
	
	// ===== Interface finalizar opções
	
	interface_formulario_campos(Array(
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'category',
				'nome' => 'categoria',
				'selectClass' => 'three column',
				'fluid' => true,
				'procurar' => true,
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-placeholder')),
				'tabela' => Array(
					'id' => true,
					'nome' => 'categorias',
					'campo' => 'nome',
					'where' => "id_modulos='".$modulo['modelos'][$modelo]['id_modulos']."' AND id_categorias_pai='".$modulo['modelos'][$modelo]['id_categorias_pai']."'".$pluginsInvativosSQL,
					'id_selecionado' => (isset($categoria_id) ? $categoria_id : null),
				),
			),
		)
	));
}

function templates_atualizacoes(){
	global $_GESTOR;
	
	if(isset($_REQUEST['atualizar'])){
		// ===== Atualizar templates no host do cliente.

		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_templates_atualizar(Array(
			'opcao' => 'update',
			'categoriaAtualizarID' => (isset($_REQUEST['categoria_id']) ? $_REQUEST['categoria_id'] : null ),
			'forceUpdate' => (isset($_REQUEST['categoria_id']) ? true : null ),
		));
		
		// ===== Depois de atualizar alertar na tela sucesso ou erro e, por fim, redirecionar raiz.
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
		} else {
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-update-ok'))
			));
		}
		
		if($_REQUEST['editar']){
			gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?id='.$_REQUEST['id'].'&modelo='.$_REQUEST['modelo']);
		} else {
			gestor_redirecionar_raiz();
		}
	}
}

function templates_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'editar-indice':
			$_GESTOR['interface-opcao'] = 'editar-incomum';
		break;
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'padrao',
						'id_categorias',
						'id_categorias_pai',
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
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
							'id' => 'padrao',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-default-label')),
							'className' => 'dt-head-center',
							'formatar' => Array(
								'id' => 'outroConjunto',
								'valor_senao_existe' => '<b><span class="ui info text">'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'list-default-no')).'</span></b>',
								'conjunto' => Array(
									Array(
										'alvo' => '1',
										'troca' => '<b><span class="ui success text">'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'list-default-yes')).'</span></b>',
									),
								),
							)
						),
						Array(
							'id' => 'id_categorias',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-label')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'categorias',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id_categorias',
								),
							)
						),
						Array(
							'id' => 'id_categorias_pai',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-category-model-label')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'categorias',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id_categorias',
								),
							)
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
						'url' => 'editar-indice/',
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
					'seletores-paginas' => Array(
						'url' => 'seletores/?modelo=paginas',
						'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-default-page')),
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-default-page')),
						'icon' => 'file image outline',
						'cor' => 'blue',
					),
					'seletores-layouts' => Array(
						'url' => 'seletores/?modelo=layouts',
						'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-default-layout')),
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-default-layout')),
						'icon' => 'images outline',
						'cor' => 'orange',
					),
					'seletores-componentes' => Array(
						'url' => 'seletores/?modelo=componentes',
						'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-default-component')),
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-default-component')),
						'icon' => 'images outline',
						'cor' => 'violet',
					),
					'atualizações' => Array(
						'url' => 'atualizacoes/',
						'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-updates')),
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-updates')),
						'icon' => 'cloud download alternate',
						'cor' => 'green',
					),
				),
			);
		break;
	}
}

function templates_ativar(){
	global $_GESTOR;
	
	// ===== Módulo do sistema.
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificar modelo.
	
	$modelo = banco_escape_field($_REQUEST['modelo']);
	
	// ===== Senão for enviado o modelo, alertar e redirecionar raiz do módulo.
	
	if(!isset($modulo['modelos'][$modelo])){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-mandatory-model'))
		));
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Verificar se foi enviado as variáveis de controle.
	
	if(!isset($_REQUEST['tipo']) || !isset($_REQUEST['id'])){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-mandatory-fields'))
		));
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Pegar os valores do tipo e id.
	
	$tipo = banco_escape_field($_REQUEST['tipo']);
	$id = banco_escape_field($_REQUEST['id']);
	
	// ===== Mudar o status ativo conforme necessidade.
	
	switch($tipo){
		case 'gestor':
			// ===== Pegar id da categoria.
			
			$templates = banco_select(Array(
				'unico' => true,
				'tabela' => 'templates',
				'campos' => Array(
					'id_categorias',
				),
				'extra' => 
					"WHERE status='A'"
					." AND id='".$id."'"
			));	
			
			// ===== Inativar todos os hosts_templates.
		
			banco_update_campo('ativo','NULL',true);
			
			banco_update_executar('hosts_templates',"WHERE id_categorias='".$templates['id_categorias']."' AND id_hosts='".$_GESTOR['host-id']."'");
			
			// ===== Id da categoria.
			
			$categorias = banco_select(Array(
				'unico' => true,
				'tabela' => 'categorias',
				'campos' => Array(
					'id',
				),
				'extra' => 
					"WHERE status='A'"
					." AND id_categorias='".$templates['id_categorias']."'"
			));
			
			// ===== Categoria ID.
			
			$categoria_id = '&categoria_id='.$categorias['id'];
			$categoriaAtualizarID = $categorias['id'];
		break;
		case 'hosts':
			// ===== Pegar id da categoria.
			
			$hosts_templates = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_templates',
				'campos' => Array(
					'id_hosts_templates',
					'id_categorias',
				),
				'extra' => 
					"WHERE status='A'"
					." AND id='".$id."'"
					." AND id_hosts='".$_GESTOR['host-id']."'"
			));
			
			// ===== Inativar todos os hosts_templates.
			
			banco_update_campo('ativo','NULL',true);
			
			banco_update_executar('hosts_templates',"WHERE id_categorias='".$hosts_templates['id_categorias']."' AND id_hosts='".$_GESTOR['host-id']."'");
			
			// ===== Ativar o hosts_templates.
			
			banco_update_campo('ativo','1',true);
			
			banco_update_executar('hosts_templates',"WHERE id_hosts_templates='".$hosts_templates['id_hosts_templates']."' AND id_hosts='".$_GESTOR['host-id']."'");
			
			// ===== Id da categoria.
			
			$categorias = banco_select(Array(
				'unico' => true,
				'tabela' => 'categorias',
				'campos' => Array(
					'id',
				),
				'extra' => 
					"WHERE status='A'"
					." AND id_categorias='".$hosts_templates['id_categorias']."'"
			));
			
			// ===== Categoria ID.
			
			$categoria_id = '&categoria_id='.$categorias['id'];
			$categoriaAtualizarID = $categorias['id'];
		break;
		default:
			$categoria_id = '';
	}
	
	// ===== Atualizar template no host do cliente.

	if(isset($categoriaAtualizarID)){
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_templates_atualizar(Array(
			'opcao' => 'update',
			'categoriaAtualizarID' => $categoriaAtualizarID,
		));
		
		// ===== Depois de atualizar alertar na tela sucesso ou erro e, por fim, redirecionar raiz.
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
		} else {
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-update-ok'))
			));
		}
	}
	
	// ===== Redirecionar devolta.
	
	if($_REQUEST['editar']){
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?id='.$id.'&modelo='.$modelo);
	} else {
		gestor_redirecionar($_GESTOR['modulo-id'].'/seletores/?modelo='.$modelo.$categoria_id);
	}
}

function templates_preview(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificar do template.
	
	$id = banco_escape_field($_REQUEST['id']);
	
	// ===== Identificar modelo.
	
	$modelo = banco_escape_field($_REQUEST['modelo']);
	
	// ===== Senão for enviado o modelo, alertar e redirecionar raiz do módulo.
	
	if(!isset($modulo['modelos'][$modelo])){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-mandatory-model'))
		));
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Alterar título da página.
	
	$categorias = banco_select_name(
		banco_campos_virgulas(Array(
			'nome',
		)),
		"categorias",
		"WHERE id_categorias='".$modulo['modelos'][$modelo]['id_categorias_pai']."'"
	);
	
	$modeloTitulo = $categorias[0]['nome'];
	
	$_GESTOR['pagina#titulo'] .= ' ' . $modeloTitulo;
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas(Array(
			'id_categorias',
			'nome',
			'html',
			'css',
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND id_hosts='".$_GESTOR['host-id']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$id_categorias = (isset($retorno_bd['id_categorias']) ? $retorno_bd['id_categorias'] : '');
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$html = (isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		
		// ===== Incluir o nome do registro na página título.
		
		$_GESTOR['pagina#titulo-extra'] = $nome.' - ';
		
		// ===== Se for modelo página.
		
		if($modelo == 'paginas' || $modelo == 'componentes'){
			$_GESTOR['pagina'] = html_entity_decode($html);
			
			if(existe($css)){
				$css = preg_replace("/(^|\n)/m", "\n        ", $css);
				
				$_GESTOR['css'][] = '<style>'."\n";
				$_GESTOR['css'][] = $css."\n";
				$_GESTOR['css'][] = '</style>'."\n";
			}
		}
		
		// ===== Se for modelo de layout.
		
		if($modelo == 'layouts'){
			$_GESTOR['layout'] = Array(
				'html' => html_entity_decode($html),
				'css' => $css,
			);
		}
		
		// ===== Módulos extras para trocar variáveis.
		
		if(isset($_REQUEST['modulosExtras'])){
			$modulosExtras = explode(',',$_REQUEST['modulosExtras']);
			
			if($modulosExtras)
			foreach($modulosExtras as $modulo){
				$_GESTOR['paginas-variaveis'][$modulo] = true;
			}
		}
	} else {
		gestor_redirecionar_raiz();
	}
}

// ==== Ajax

function templates_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function templates_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': templates_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		templates_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': templates_adicionar(); break;
			case 'editar-indice': templates_editar_indice(); break;
			case 'editar': templates_editar(); break;
			case 'seletores-listar': templates_seletores_listar(); break;
			case 'atualizacoes': templates_atualizacoes(); break;
			case 'ativar': templates_ativar(); break;
			case 'preview': templates_preview(); break;
		}
		
		interface_finalizar();
	}
}

templates_start();

?>