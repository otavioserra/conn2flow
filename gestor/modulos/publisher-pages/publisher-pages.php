<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'publisher-pages';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/publisher-pages.json'), true);

// ===== Interfaces Auxiliares

function publisher_pages_publisher($publisher_id = null){
	global $_GESTOR;

	if(isset($publisher_id)){
		$publisher_item = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
			))
			,
			'publisher',
			"WHERE id='".$publisher_id."'"
			." AND language='".$_GESTOR['linguagem-codigo']."' AND status!='D'"
		);

		if($publisher_item){
			gestor_js_variavel_incluir('current_publisher_id',$publisher_item[0]['id']);
			return $publisher_item[0]['id'];
		}
	} else if(isset($_REQUEST['publisher_id'])){
		$publisher = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
			))
			,
			'publisher',
			"WHERE language='".$_GESTOR['linguagem-codigo']."' AND status!='D'"
		);

		if($publisher){
			foreach($publisher as $publisher_item){
				if($publisher_item['id'] == $_REQUEST['publisher_id']){
					$publisher = $publisher_item['id'];
					gestor_js_variavel_incluir('current_publisher_id',$publisher);
					break;
				}
			}
		}
	}

	return isset($publisher)? $publisher : null;
}

function publisher_pages_normalize_array($array) {
    if (is_array($array)) {
        ksort($array); // Ordena chaves
        foreach ($array as $key => $value) {
            $array[$key] = publisher_pages_normalize_array($value); // Recursivo para subarrays
        }
    }
    return $array;
}

// ===== Interfaces Principais

function publisher_pages_adicionar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar registro no Banco
	
	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'pagina-nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'publisher',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher-label')),
				)
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["pagina-nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			),
		));

		$page_id = $id;
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		));
		
		if($exiteCampo){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['paginaCaminho']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar/');
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		// Pegar HTML com valores das variáveis já preenchidos
		$_REQUEST['htmlWithValues'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['htmlWithValues']);

		// Demais.
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled']);
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head']);
		
		// ===== Definir o publisher_id

		$publisher_id = $_REQUEST['publisher'];

		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "pagina-nome"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $page_id; 									$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "publisher_id"; $campo_valor = $publisher_id;						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		$campo_nome = "layout_id"; $post_nome = 'layout';		 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "tipo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "framework_css"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "modulo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "opcao"; $post_nome = 'pagina-opcao'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "caminho"; $post_nome = 'paginaCaminho'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		$campo_nome = "html"; $post_nome = 'htmlWithValues'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css"; $post_nome = $campo_nome; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css_compiled"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "html_extra_head"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		$campo_nome = "raiz"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		
		if(gestor_acesso('permissao-pagina')){
			$campo_nome = "sem_permissao"; $post_nome = $campo_nome; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		}
		
		// ===== Campos comuns
		
		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);

		// ===== Guardar variáveis do Publisher Page

		$campos = array();

		$fields_schema_decoded = json_decode($_REQUEST['publisher_fields_schema'] ?? '', true) ?: null;

		if($fields_schema_decoded && isset($fields_schema_decoded['fields'])){
			$fields = isset($fields_schema_decoded['fields']) ? $fields_schema_decoded['fields'] : null;
			$template_map = isset($fields_schema_decoded['template_map']) ? $fields_schema_decoded['template_map'] : null;

			if($fields){
				foreach($fields as $field){
					
					$id = $field['id'];
					$post_nome = 'field_'.$id; 

					switch($field['type']){
						case 'image':
							$post_nome .= '-caminho';
							break;
					}

					if($_REQUEST[$post_nome]){
						$fields_values[] = Array(
							'id' => $id,
							'value' => $_REQUEST[$post_nome]
						);
					}
				}

				if(isset($fields_values)){
					$campo_nome = "fields_values"; 
					$campo_valor = banco_escape_field(json_encode($fields_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
					$campos[] = Array($campo_nome,$campo_valor);
				}
			}
		}

		$campo_nome = "page_id"; $campo_valor = $page_id; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "publisher_id"; $campo_valor = $publisher_id; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		
		$campo_nome = "html_template"; $post_nome = 'html'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		// ===== Campos comuns
		
		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			'publisher_pages'
		);

		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$page_id);
	}

	// ===== Permissão de páginas
	
	if(!gestor_acesso('permissao-pagina')){
		$cel_nome = 'permissao-pagina'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	}	
	
	// Incluir o Componente Editor HTML na página

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente([
		'adicionarEditar' => true,
		'publisherPage' => true,
		'alvo' => 'paginas',
	]));

	// ===== Publisher

	$variaveisTrocarDepois['pagina-css'] = '';
	$variaveisTrocarDepois['pagina-css-compiled'] = '';
	$variaveisTrocarDepois['pagina-html'] = '';
	$variaveisTrocarDepois['pagina-html-extra-head'] = '';

	$publisher_id = publisher_pages_publisher();

	if($publisher_id){
		$publisher = banco_select(Array(
			'unico' => true,
			'tabela' => 'publisher',
			'campos' => Array(
				'fields_schema',
				'template_id',
				'path_prefix',
			),
			'extra' => 
				"WHERE status='A' AND id='".$publisher_id."'"
				.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
		));

		if($publisher){
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			$openText = $_GESTOR['variavel-global']['openText'];
			$closeText = $_GESTOR['variavel-global']['closeText'];
			
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#publisher-id#', '?id='.$publisher_id);

			$fields_schema = $publisher['fields_schema'];
			$path_prefix = $publisher['path_prefix'] ?? '';

			gestor_js_variavel_incluir('publisherPathPrefix',$path_prefix);

			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#publisher-fields-schema#', $fields_schema);

			$fields_schema_decoded = json_decode($fields_schema, true) ?: null;

			if($fields_schema_decoded && isset($fields_schema_decoded['fields'])){
				$fields = isset($fields_schema_decoded['fields']) ? $fields_schema_decoded['fields'] : null;
				$template_map = isset($fields_schema_decoded['template_map']) ? $fields_schema_decoded['template_map'] : null;

				if($fields){
					$publisher_fields = gestor_componente(Array(
						'id' => 'publisher-fields',
						'modulo' => $_GESTOR['modulo-id'],
					));

					// Incluir traduções do Quill no JS
					$cel_nome = 'publisher-quill-translation'; gestor_js_variavel_incluir('quillTranslation',modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'));

					// Incluir o campo show do Quill no JS
					$cel_nome = 'publisher-field-show-html'; gestor_js_variavel_incluir('quillShowContainer',modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'));

					// Pegar as células do template de fields
					$cel_nome = 'publisher-field'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					$cel_nome = 'publisher-field-controller-text'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					$cel_nome = 'publisher-field-controller-textarea'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					$cel_nome = 'publisher-field-controller-html'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					$cel_nome = 'publisher-field-controller-image'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

					foreach($fields as $field){
						foreach($template_map as $field_map){
							if($field_map['id'] == $field['id']){
								$field['variable'] = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $field_map['variable']);
								break;
							}
						}

						if(!isset($field['variable'])){
							$field['variable'] = '[[publisher#'.$field['type'].'#'.$field['id'].']]';
						}

						$cel_nome = 'publisher-field-controller-'.$field['type']; 
						$cel_field_type = $cel[$cel_nome];

						$cel_field = $cel['publisher-field'];
						
						// Campos específicos por tipo

						switch($field['type']){
							case 'image':
								$cel_field_type = modelo_var_troca($cel_field_type, '[[field-name]]', '#imagepick-field_'.$field['id'].'#');

								$camposInterfacePersonalizado[] = Array(
									'tipo' => 'imagepick',
									'id' => 'field_'.$field['id'],
									'nome' => 'field_'.$field['id'],
								);

								break;
							default:
								$cel_field_type = modelo_var_troca($cel_field_type, '[[field-name]]', 'field_'.$field['id']);
						}
						
						if(isset($field['description']) && $field['description'] != ''){
							$cel_field_type = modelo_var_troca($cel_field_type, '[[field-description]]', $field['description']);
						} else {
							$labelPadrao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-field-description-'.$field['type']));

							$labelPadrao = modelo_var_troca($labelPadrao, '#label#', $field['label']);
							
							$cel_field_type = modelo_var_troca($cel_field_type, '[[field-description]]', $labelPadrao);
						}
					
						$cel_field_type = modelo_var_troca($cel_field_type, '[[field-value]]', '');

						$cel_field = modelo_var_troca($cel_field, '[[field-type-controller]]', $cel_field_type);
						// Campos padrão para todos os tipos
						$cel_field = modelo_var_troca($cel_field, '[[field-label]]', $field['label']);
						$cel_field = modelo_var_troca($cel_field, '[[field-variable]]', $field['variable']);
						$cel_field = modelo_var_troca($cel_field, '[[field-id]]', $field['id']);

						$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- publisher-fields -->',$cel_field);
					}
				}
			}

			// Pegar os dados de HTML/CSS do template e substituir na página.

			if($publisher['template_id']){
				$template = banco_select(Array(
					'unico' => true,
					'tabela' => 'templates',
					'campos' => Array(
						'html',
						'css',
						'css_compiled',
						'html_extra_head',
						'framework_css',
					),
					'extra' => 
						"WHERE status='A' AND id='".$publisher['template_id']."'"
						.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
				));

				if($template){
					$html = (isset($template['html']) ? htmlentities($template['html']) : '');
					$css = (isset($template['css']) ? $template['css'] : '');
					$css_compiled = (isset($template['css_compiled']) ? $template['css_compiled'] : '');
					$html_extra_head = (isset($template['html_extra_head']) ? $template['html_extra_head'] : '');
					$framework_css = (isset($template['framework_css']) ? $template['framework_css'] : '');
					
					// ===== Variaveis globais alterar.
		
					$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
					$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
					$css_compiled = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css_compiled);
					$html_extra_head = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html_extra_head);

					$variaveisTrocarDepois['pagina-css'] = $css;
					$variaveisTrocarDepois['pagina-css-compiled'] = $css_compiled;
					$variaveisTrocarDepois['pagina-html'] = $html;
					$variaveisTrocarDepois['pagina-html-extra-head'] = $html_extra_head;
				}
			}
		}
	} else {
		// Remover a seção de campos do publicador
		$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- publisher-options < -->','<!-- publisher-options > -->');
		$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- publisher-fields-container < -->','<!-- publisher-fields-container > -->');
	}

	// ===== Inclusão Quill

	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" />');
	gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.min.js"></script>');

	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Campos da Interface

	$camposInterfacePadrao = Array(
		Array(
			'tipo' => 'select',
			'id' => 'layout',
			'nome' => 'layout',
			'procurar' => true,
			'limpar' => true,
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-placeholder')),
			'tabela' => Array(
				'nome' => 'layouts',
				'campo' => 'nome',
				'id_numerico' => 'id',
				'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
			),
		),
		Array(
			'tipo' => 'select',
			'id' => 'module',
			'nome' => 'modulo',
			'procurar' => true,
			'limpar' => true,
			'selectClass' => 'gestorModule',
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
			'tabela' => Array(
				'nome' => 'modulos',
				'campo' => 'nome',
				'id_numerico' => 'id',
				'where' => "modulo_grupo_id!='bibliotecas' AND language='".$_GESTOR['linguagem-codigo']."'",
			),
		),
		Array(
			'tipo' => 'select',
			'id' => 'type',
			'nome' => 'tipo',
			'selectClass' => 'pagina-tipo',
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-placeholder')),
			'dados' => $modulo['resources'][$_GESTOR['linguagem-codigo']]['selectDadosTipo'],
			'valor_selecionado' => 'pagina',
		),
		Array(
			'tipo' => 'select',
			'id' => 'framework-css',
			'nome' => 'framework_css',
			'selectClass' => 'frameworkCSS',
			'valor_selecionado' => isset($framework_css)? $framework_css : 'tailwindcss',
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
			'dados' => $modulo['selectDadosFrameworkCSS'],
		),
		Array(
			'tipo' => 'select',
			'id' => 'publisher',
			'nome' => 'publisher',
			'procurar' => true,
			'limpar' => true,
			'selectClass' => 'publisherDropdown',
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher-placeholder')),
			'tabela' => Array(
				'nome' => 'publisher',
				'campo' => 'name',
				'id_numerico' => 'id',
				'id_selecionado' => isset($publisher_id)? $publisher_id : null,
				'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
			),
		),
	);

	$camposInterface = array_merge($camposInterfacePadrao, $camposInterfacePersonalizado ?? Array());

	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'variaveisTrocarDepois' => $variaveisTrocarDepois,
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
					'identificador' => 'pagina-nome',
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'language' => true,
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'layout',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-label')),
					'identificador' => 'layout',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'tipo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
					'identificador' => 'tipo',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'framework_css',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
					'identificador' => 'framework_css',
				)
			),
			'campos' => $camposInterface
		)
	);
}

function publisher_pages_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do registro
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'caminho',
		'publisher_id',
		'layout_id', // substitui id_layouts
		'modulo',
		'tipo',
		'opcao',
		'raiz',
		'sem_permissao',
		'html',
		'css',
		'css_compiled', // Novo campo
		'html_extra_head', // Novo campo
		'framework_css',
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;

	$camposBancoAntesPublisherPage = Array(
		'fields_values',
		'html_template',
	);
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'pagina-nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'publisher',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher-label')),
				)
			)
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		));
		
		if($exiteCampo){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['paginaCaminho']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
		}
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'",
		);
		
		$campo_nome = "nome"; $request_name = 'pagina-nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se mudar o nome, mudar o identificador do registro
		
		if(isset($alterar_id)){
			$layouts = banco_select_name
			(
				banco_campos_virgulas(Array(
					$modulo['tabela']['id_numerico'],
				))
				,
				$modulo['tabela']['nome'],
				"WHERE ".$modulo['tabela']['id']."='".$id."'"
			);
			
			if($layouts){
				$id_novo = banco_identificador(Array(
					'id' => banco_escape_field($_REQUEST["pagina-nome"]),
					'tabela' => Array(
						'nome' => $modulo['tabela']['nome'],
						'campo' => $modulo['tabela']['id'],
						'id_nome' => $modulo['tabela']['id_numerico'],
						'id_valor' => $layouts[0][$modulo['tabela']['id_numerico']],
						'where' => "language='".$_GESTOR['linguagem-codigo']."'",
					),
				));
				
				$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
				$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
				$_GESTOR['modulo-registro-id'] = $id_novo;
			}

			$page_id_novo = $id_novo;
		}
		
		$page_id = $id;

		// ===== Definir o publisher_id

		$publisher_id = $_REQUEST['publisher'];

		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		// Pegar HTML com valores das variáveis já preenchidos
		$_REQUEST['htmlWithValues'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['htmlWithValues']);

		// Demais.
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled']);
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head']);
		
		// ===== Atualização dos demais campos.

		$campo_nome = "layout_id"; $request_name = 'layout'; $alteracoes_name = 'layout'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "tipo"; $request_name = $campo_nome; $alteracoes_name = 'type'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "framework_css"; $request_name = $campo_nome; $alteracoes_name = 'framework-css'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "modulo"; $request_name = $campo_nome; $alteracoes_name = 'module'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "opcao"; $request_name = 'pagina-opcao'; $alteracoes_name = 'option'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "caminho"; $request_name = 'paginaCaminho'; $alteracoes_name = 'path'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name])); $caminhoMudou = banco_select_campos_antes($campo_nome);}
		
		$campo_nome = "raiz"; $request_name = $campo_nome; $alteracoes_name = 'root'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? '1' : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($_REQUEST[$request_name]) ? '1' : 'NULL'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'filtro' => 'checkbox','valor_antes' => (banco_select_campos_antes($campo_nome) ? '1' : '0'),'valor_depois' => (isset($_REQUEST[$request_name]) ? '1' : '0'));}
		
		if(gestor_acesso('permissao-pagina')){
			$campo_nome = "sem_permissao"; $request_name = $campo_nome; $alteracoes_name = 'permission'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? '1' : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($_REQUEST[$request_name]) ? '1' : 'NULL'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'filtro' => 'checkbox','valor_antes' => (banco_select_campos_antes($campo_nome) ? '1' : '0'),'valor_depois' => (isset($_REQUEST[$request_name]) ? '1' : '0'));}
		}
		
		$campo_nome = "html"; $request_name = 'htmlWithValues'; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}}
		$campo_nome = "css"; $request_name = $campo_nome; $alteracoes_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}}
		$campo_nome = "css_compiled"; $request_name = $campo_nome; $alteracoes_name = 'css-compiled'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}} // Novo campo
		$campo_nome = "html_extra_head"; $request_name = $campo_nome; $alteracoes_name = 'html-extra-head'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}} // Novo campo
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
			$campo_nome = 'user_modified'; $editar['dados'][] = $campo_nome." = 1";
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
			
			// ===== Se mudou o caminho, criar página 301 do caminho
			
			if(isset($caminhoMudou)){
				$campos = null; $campo_sem_aspas_simples = null;
				
				$campo_nome = "id_paginas"; $campo_valor = interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "caminho"; $campo_valor = $caminhoMudou; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				banco_insert_name
				(
					$campos,
					"paginas_301"
				);
			}
		}

		// ===== Atualizar campos do publisher pages

		$fields_schema_decoded = json_decode($_REQUEST['publisher_fields_schema'] ?? '', true) ?: null;

		if($fields_schema_decoded && isset($fields_schema_decoded['fields'])){
			$fields = isset($fields_schema_decoded['fields']) ? $fields_schema_decoded['fields'] : null;
			$template_map = isset($fields_schema_decoded['template_map']) ? $fields_schema_decoded['template_map'] : null;

			if($fields){
				// ===== Definição dos campos do banco de dados para editar.

				$editar_publisher_pages = Array(
					'tabela' => 'publisher_pages',
					'extra' => "WHERE page_id='".$page_id."' AND publisher_id='".$publisher_id."' AND language='".$_GESTOR['linguagem-codigo']."'",
				);

				// ===== Recuperar o estado dos dados do banco de dados antes de editar.
				
				if(!banco_select_campos_antes_iniciar(
					banco_campos_virgulas($camposBancoAntesPublisherPage)
					,
					'publisher_pages',
					"WHERE page_id='".$page_id."'"
					." AND publisher_id='".$publisher_id."'"
					." AND language='".$_GESTOR['linguagem-codigo']."'"
				)){
					interface_alerta(Array(
						'redirect' => true,
						'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-database-field-before-error'))
					));
					
					gestor_redirecionar_raiz();
				}
				
				foreach($fields as $field){
					
					$id = $field['id'];
					$post_nome = 'field_'.$id; 

					switch($field['type']){
						case 'image':
							$post_nome .= '-caminho';
							break;
					}

					if($_REQUEST[$post_nome]){
						$fields_values[] = Array(
							'id' => $id,
							'value' => $_REQUEST[$post_nome]
						);
					}
				}

				if(isset($fields_values)){
					$request_json = json_encode($fields_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				}

				// ===== Processar fields_schema para converter variáveis de frontend [[...]] para backend @[[...]]@

				$open = $_GESTOR['variavel-global']['open'];
				$close = $_GESTOR['variavel-global']['close'];
				$openText = $_GESTOR['variavel-global']['openText'];
				$closeText = $_GESTOR['variavel-global']['closeText'];

				$request_formatado = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), (isset($request_json) ? $request_json : ''));

				// ===== Normalizar e comparar o schema de campos personalizados

				$alteracoes_name = $campo_nome = 'fields_values';
				
				$valor_request = publisher_pages_normalize_array(json_decode($request_formatado, true));
				$valor_banco = publisher_pages_normalize_array(json_decode(banco_select_campos_antes($campo_nome), true));
				
				if ($valor_banco !== $valor_request) {
					// Lógica de atualização
					$editar_publisher_pages['dados'][] = $campo_nome . "='" . banco_escape_field($request_formatado) . "'";
					$alteracoes[] = Array('campo' => 'form-' . $alteracoes_name . '-label');
				}
			}
		}

		// ===== Html do template

		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);

		$campo_nome = "html_template"; $request_name = 'html'; $alteracoes_name = $request_name; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar_publisher_pages['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'";}

		// ===== Se alterou o id, atualizar também no publisher pages

		if(isset($page_id_novo)){
			$editar_publisher_pages['dados'][] = "page_id='" . $page_id_novo . "'";
		}

		// ===== Se houve alterações no publisher pages, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar_publisher_pages['dados'])){
			$editar_publisher_pages['sql'] = banco_campos_virgulas($editar_publisher_pages['dados']);
			
			if($editar_publisher_pages['sql']){
				banco_update
				(
					$editar_publisher_pages['sql'],
					$editar_publisher_pages['tabela'],
					$editar_publisher_pages['extra']
				);
			}
		}

		// ===== Incluir no histórico as alterações e backup dos campos

		if(isset($editar['dados']) || isset($editar_publisher_pages['dados'])){
			
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
				'id' => $page_id,
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'id_numerico' => $modulo['tabela']['id_numerico'],
					'versao' => $modulo['tabela']['versao'],
				),
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($page_id_novo) ? $page_id_novo : $page_id));
	}
	
	// ===== Permissão de páginas
	
	if(!gestor_acesso('permissao-pagina')){
		$cel_nome = 'permissao-pagina'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	}

	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$caminho = (isset($retorno_bd['caminho']) ? $retorno_bd['caminho'] : '');
		$publisher_id = (isset($retorno_bd['publisher_id']) ? $retorno_bd['publisher_id'] : '');
		$layout_id = (isset($retorno_bd['layout_id']) ? $retorno_bd['layout_id'] : '');
		$bd_modulo = (isset($retorno_bd['modulo']) ? $retorno_bd['modulo'] : '');
		$tipo = (isset($retorno_bd['tipo']) ? $retorno_bd['tipo'] : '');
		$framework_css = (isset($retorno_bd['framework_css']) ? $retorno_bd['framework_css'] : '');
		$opcao = (isset($retorno_bd['opcao']) ? $retorno_bd['opcao'] : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		$css_compiled = (isset($retorno_bd['css_compiled']) ? $retorno_bd['css_compiled'] : '');
		$html_extra_head = (isset($retorno_bd['html_extra_head']) ? $retorno_bd['html_extra_head'] : '');
		$raiz = (isset($retorno_bd['raiz']) ? true : false);
		$sem_permissao = (isset($retorno_bd['sem_permissao']) ? true : false);

		// ===== Page ID

		$page_id = $id;

		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];

		// ===== Publisher

		if($publisher_id){
			$publisher = banco_select(Array(
				'unico' => true,
				'tabela' => 'publisher',
				'campos' => Array(
					'fields_schema',
					'template_id',
					'path_prefix',
				),
				'extra' => 
					"WHERE status='A' AND id='".$publisher_id."'"
					.' AND language="'.$_GESTOR['linguagem-codigo'].'"'
			));

			if($publisher){
				$open = $_GESTOR['variavel-global']['open'];
				$close = $_GESTOR['variavel-global']['close'];
				$openText = $_GESTOR['variavel-global']['openText'];
				$closeText = $_GESTOR['variavel-global']['closeText'];
				
				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#publisher-id#', '?id='.$publisher_id);

				$fields_schema = $publisher['fields_schema'];
				$path_prefix = $publisher['path_prefix'] ?? '';

				gestor_js_variavel_incluir('publisherPathPrefix',$path_prefix);

				$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#publisher-fields-schema#', $fields_schema);

				$fields_schema_decoded = json_decode($fields_schema, true) ?: null;

				if($fields_schema_decoded && isset($fields_schema_decoded['fields'])){
					$fields = isset($fields_schema_decoded['fields']) ? $fields_schema_decoded['fields'] : null;
					$template_map = isset($fields_schema_decoded['template_map']) ? $fields_schema_decoded['template_map'] : null;

					if($fields){
						// Pegar os valores dos campos no Publihser Pages
						$publisher_pages = banco_select(Array(
							'unico' => true,
							'tabela' => 'publisher_pages',
							'campos' => Array(
								'fields_values',
								'html_template',
							),
							'extra' => 
								"WHERE page_id='".$page_id."'"
								." AND publisher_id='".$publisher_id."'"
								." AND language='".$_GESTOR['linguagem-codigo']."'"
						));

						if($publisher_pages && $publisher_pages['fields_values']){
							$fields_values_decoded = json_decode($publisher_pages['fields_values'], true) ?: null;
							$html_template = $publisher_pages['html_template'];
						}

						// Componente de fields do publisher
						$publisher_fields = gestor_componente(Array(
							'id' => 'publisher-fields',
							'modulo' => $_GESTOR['modulo-id'],
						));

						// Incluir traduções do Quill no JS
						$cel_nome = 'publisher-quill-translation'; gestor_js_variavel_incluir('quillTranslation',modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'));

						// Incluir o campo show do Quill no JS
						$cel_nome = 'publisher-field-show-html'; gestor_js_variavel_incluir('quillShowContainer',modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'));

						// Pegar as células do template de fields
						$cel_nome = 'publisher-field'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
						$cel_nome = 'publisher-field-controller-text'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
						$cel_nome = 'publisher-field-controller-textarea'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
						$cel_nome = 'publisher-field-controller-html'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
						$cel_nome = 'publisher-field-controller-image'; $cel[$cel_nome] = modelo_tag_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $publisher_fields = modelo_tag_troca_val($publisher_fields,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

						foreach($fields as $field){
							foreach($template_map as $field_map){
								if($field_map['id'] == $field['id']){
									$field['variable'] = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $field_map['variable']);
									break;
								}
							}

							if(!isset($field['variable'])){
								$field['variable'] = '[[publisher#'.$field['type'].'#'.$field['id'].']]';
							}

							$cel_nome = 'publisher-field-controller-'.$field['type']; 
							$cel_field_type = $cel[$cel_nome];

							$cel_field = $cel['publisher-field'];
							
							if(isset($field['description']) && $field['description'] != ''){
								$cel_field_type = modelo_var_troca($cel_field_type, '[[field-description]]', $field['description']);
							} else {
								$labelPadrao = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-field-description-'.$field['type']));

								$labelPadrao = modelo_var_troca($labelPadrao, '#label#', $field['label']);
								
								$cel_field_type = modelo_var_troca($cel_field_type, '[[field-description]]', $labelPadrao);
							}
						
							// Pegar o valor do campo
							$value_field = '';
							if(isset($fields_values_decoded)){
								foreach($fields_values_decoded as $field_value){
									if($field_value['id'] == $field['id']){
										$value_field = $field_value['value'];
										break;
									}
								}
							}

							// Campos específicos por tipo

							switch($field['type']){
								case 'image':
									$cel_field_type = modelo_var_troca($cel_field_type, '[[field-name]]', '#imagepick-field_'.$field['id'].'#');

									$camposInterfacePersonalizado[] = Array(
										'tipo' => 'imagepick',
										'id' => 'field_'.$field['id'],
										'nome' => 'field_'.$field['id'],
										'caminho' => existe($value_field) ? $value_field : null,
									);

									break;
								case 'textarea':
									$value_field = str_replace("\r\n", "<br>", $value_field);
									$value_field = str_replace("\r", "", $value_field);
									$value_field = preg_replace('/\n{2,}/', "\n\n", $value_field);

									$cel_field_type = modelo_var_troca($cel_field_type, '[[field-name]]', 'field_'.$field['id']);
									$cel_field_type = modelo_var_troca($cel_field_type, '[[field-value]]', $value_field);
									break;
								default:
									$cel_field_type = modelo_var_troca($cel_field_type, '[[field-name]]', 'field_'.$field['id']);
									$cel_field_type = modelo_var_troca($cel_field_type, '[[field-value]]', $value_field);
							}

							$cel_field = modelo_var_troca($cel_field, '[[field-type-controller]]', $cel_field_type);
							// Campos padrão para todos os tipos
							$cel_field = modelo_var_troca($cel_field, '[[field-label]]', $field['label']);
							$cel_field = modelo_var_troca($cel_field, '[[field-variable]]', $field['variable']);
							$cel_field = modelo_var_troca($cel_field, '[[field-id]]', $field['id']);

							$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- publisher-fields -->',$cel_field);
						}
					}
				}
			}
		} else {
			// Remover a seção de campos do publicador
			$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- publisher-options < -->','<!-- publisher-options > -->');
			$_GESTOR['pagina'] = modelo_tag_del($_GESTOR['pagina'],'<!-- publisher-fields-container < -->','<!-- publisher-fields-container > -->');
		}

		// Html do template
		$html = (isset($html_template) ? $html_template : '');
		
		$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
		$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
		$css_compiled = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css_compiled);
		$html_extra_head = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html_extra_head);
		
		// ===== Alterar demais variáveis.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#url#','http'.(isset($_SERVER['HTTPS']) ? 's':'').'://'.$_SERVER['SERVER_NAME'].$_GESTOR['url-raiz'].($caminho == '/' ? '' : $caminho));
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#pagina-nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#caminho#',$caminho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#opcao#',$opcao);
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#raiz#',($raiz ? 'checked' : ''));
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#sem_permissao#',($sem_permissao ? 'checked' : ''));

		$variaveisTrocarDepois['pagina-css'] = $css;
		$variaveisTrocarDepois['pagina-css-compiled'] = $css_compiled;
		$variaveisTrocarDepois['pagina-html'] = $html;
		$variaveisTrocarDepois['pagina-html-extra-head'] = $html_extra_head;

		// Incluir o Componente Editor HTML na página

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente([
			'editar' => true,
			'modulo' => $modulo,
			'alvo' => 'paginas',
			'publisherPage' => true,
		]));

		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}

	// ===== Inclusão Quill

	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" />');
	gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.min.js"></script>');
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Campos da Interface

	$camposInterfacePadrao = Array(
		Array(
			'tipo' => 'select',
			'id' => 'layout',
			'nome' => 'layout',
			'procurar' => true,
			'limpar' => true,
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-placeholder')),
			'tabela' => Array(
				'nome' => 'layouts',
				'campo' => 'nome',
				'id_numerico' => 'id',
				'id_selecionado' => $layout_id,
				'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
			),
		),
		Array(
			'tipo' => 'select',
			'id' => 'module',
			'nome' => 'modulo',
			'procurar' => true,
			'limpar' => true,
			'selectClass' => 'gestorModule',
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
			'tabela' => Array(
				'nome' => 'modulos',
				'campo' => 'nome',
				'id_numerico' => 'id',
				'id_selecionado' => $bd_modulo,
				'where' => "modulo_grupo_id!='bibliotecas' AND language='".$_GESTOR['linguagem-codigo']."'",
			),
		),
		Array(
			'tipo' => 'select',
			'id' => 'type',
			'nome' => 'tipo',
			'selectClass' => 'pagina-tipo',
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-placeholder')),
			'valor_selecionado' => $tipo,
			'dados' => $modulo['resources'][$_GESTOR['linguagem-codigo']]['selectDadosTipo'],
		),
		Array(
			'tipo' => 'select',
			'id' => 'framework-css',
			'nome' => 'framework_css',
			'selectClass' => 'frameworkCSS',
			'valor_selecionado' => $framework_css,
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
			'dados' => $modulo['selectDadosFrameworkCSS'],
		),
		Array(
			'tipo' => 'select',
			'id' => 'publisher',
			'nome' => 'publisher',
			'procurar' => true,
			'limpar' => true,
			'selectClass' => 'disabled publisherDropdown',
			'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher-placeholder')),
			'tabela' => Array(
				'nome' => 'publisher',
				'campo' => 'name',
				'id_numerico' => 'id',
				'id_selecionado' => isset($publisher_id)? $publisher_id : null,
				'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
			),
		),
	);

	$camposInterface = array_merge($camposInterfacePadrao, $camposInterfacePersonalizado ?? Array());
	
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
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'clonar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/clonar/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-clone')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-clone')),
				'icon' => 'clone',
				'cor' => 'teal',
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id),
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
					'identificador' => 'pagina-nome',
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'language' => true,
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'layout',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-label')),
					'identificador' => 'layout',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'tipo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
					'identificador' => 'tipo',
				)
			),
			'campos' => $camposInterface,
		)
	);
}

function publisher_pages_clonar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do registro a ser clonado.
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para clonar.

	$camposBanco = Array(
		'caminho',
		'layout_id', // substitui id_layouts
		'modulo',
		'tipo',
		'opcao',
		'raiz',
		'sem_permissao',
		'html',
		'css',
		'css_compiled', // Novo campo
		'html_extra_head', // Novo campo
		'framework_css',
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoClonar = array_merge($camposBanco,$camposBancoPadrao);
	
	// ===== Gravar registro no Banco

	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'pagina-nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				)
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["pagina-nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='".$_GESTOR['linguagem-codigo']."'",
			),
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		));
		
		if($exiteCampo){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['paginaCaminho']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar/');
		}
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$_REQUEST['html'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html']);
		$_REQUEST['css'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css']);
		$_REQUEST['css_compiled'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['css_compiled']);
		$_REQUEST['html_extra_head'] = preg_replace("/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", strtolower($open."$1".$close), $_REQUEST['html_extra_head']);
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "pagina-nome"; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		$campo_nome = "layout_id"; $post_nome = 'layout';		 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "tipo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "framework_css"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "modulo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "opcao"; $post_nome = 'pagina-opcao'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "caminho"; $post_nome = 'paginaCaminho'; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "html"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css"; $post_nome = $campo_nome; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "css_compiled"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "html_extra_head"; $post_nome = $campo_nome; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "raiz"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		
		if(gestor_acesso('permissao-pagina')){
			$campo_nome = "sem_permissao"; $post_nome = $campo_nome; 							if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,'1',true);
		}
		
		// ===== Campos comuns
		
		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	// ===== Permissão de páginas
	
	if(!gestor_acesso('permissao-pagina')){
		$cel_nome = 'permissao-pagina'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	}	
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoClonar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$caminho = (isset($retorno_bd['caminho']) ? $retorno_bd['caminho'] : '');
		$layout_id = (isset($retorno_bd['layout_id']) ? $retorno_bd['layout_id'] : '');
		$bd_modulo = (isset($retorno_bd['modulo']) ? $retorno_bd['modulo'] : '');
		$tipo = (isset($retorno_bd['tipo']) ? $retorno_bd['tipo'] : '');
		$framework_css = (isset($retorno_bd['framework_css']) ? $retorno_bd['framework_css'] : '');
		$opcao = (isset($retorno_bd['opcao']) ? $retorno_bd['opcao'] : '');
		$html = (isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '');
		$css = (isset($retorno_bd['css']) ? $retorno_bd['css'] : '');
		$css_compiled = (isset($retorno_bd['css_compiled']) ? $retorno_bd['css_compiled'] : '');
		$html_extra_head = (isset($retorno_bd['html_extra_head']) ? $retorno_bd['html_extra_head'] : '');
		$raiz = (isset($retorno_bd['raiz']) ? true : false);
		$sem_permissao = (isset($retorno_bd['sem_permissao']) ? true : false);
		
		// ===== Variaveis globais alterar.
		
		$open = $_GESTOR['variavel-global']['open'];
		$close = $_GESTOR['variavel-global']['close'];
		$openText = $_GESTOR['variavel-global']['openText'];
		$closeText = $_GESTOR['variavel-global']['closeText'];
		
		$html = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html);
		$css = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css);
		$css_compiled = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $css_compiled);
		$html_extra_head = preg_replace("/".preg_quote($open)."(.+?)".preg_quote($close)."/", strtolower($openText."$1".$closeText), $html_extra_head);
		
		// ===== Alterar demais variáveis.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#caminho#',$caminho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#opcao#',$opcao);
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#raiz#',($raiz ? 'checked' : ''));
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#sem_permissao#',($sem_permissao ? 'checked' : ''));

		$variaveisTrocarDepois['pagina-css'] = $css;
		$variaveisTrocarDepois['pagina-css-compiled'] = $css_compiled;
		$variaveisTrocarDepois['pagina-html'] = $html;
		$variaveisTrocarDepois['pagina-html-extra-head'] = $html_extra_head;

		// Incluir o Componente Editor HTML na página

		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'#html-editor#',html_editor_componente([
			'editar' => true,
			'modulo' => $modulo,
			'alvos' => 'paginas',
		]));
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Quill

	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" />');
	gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.min.js"></script>');
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface clonar finalizar opções
	
	$_GESTOR['interface']['clonar']['finalizar'] = Array(
		'variaveisTrocarDepois' => $variaveisTrocarDepois,
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
					'identificador' => 'pagina-nome',
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'language' => true,
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'layout',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-label')),
					'identificador' => 'layout',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'tipo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
					'identificador' => 'tipo',
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'layout',
					'nome' => 'layout',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-layout-placeholder')),
					'tabela' => Array(
						'nome' => 'layouts',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $layout_id,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'gestorModule',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $bd_modulo,
						'where' => "modulo_grupo_id!='bibliotecas' AND language='".$_GESTOR['linguagem-codigo']."'",
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'type',
					'nome' => 'tipo',
					'selectClass' => 'pagina-tipo',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-placeholder')),
					'valor_selecionado' => $tipo,
					'dados' => $modulo['resources'][$_GESTOR['linguagem-codigo']]['selectDadosTipo'],
				),
				Array(
					'tipo' => 'select',
					'id' => 'framework-css',
					'nome' => 'framework_css',
					'selectClass' => 'frameworkCSS',
					'valor_selecionado' => $framework_css,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
					'dados' => $modulo['selectDadosFrameworkCSS'],
				),
				Array(
					'tipo' => 'select',
					'id' => 'publisher',
					'nome' => 'publisher',
					'procurar' => true,
					'limpar' => true,
					'selectClass' => 'publisherDropdown',
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-publisher-placeholder')),
					'tabela' => Array(
						'nome' => 'publisher',
						'campo' => 'name',
						'id_numerico' => 'id',
						'id_selecionado' => isset($publisher_id)? $publisher_id : null,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					),
				),
			)
		)
	);
}

function publisher_pages_listar_cabecalho(){
	global $_GESTOR;

	// ===== Inclusão Módulo JS

	if ($_GESTOR['opcao'] == 'listar') {
		gestor_pagina_javascript_incluir();
	}

	// Requests recebidos

	$tipo = $_REQUEST['tipo'] ?? 'pagina';
	$module_id = $_REQUEST['module_id'] ?? '';
	$publisher_id = $_REQUEST['publisher_id'] ?? '';

	// Escapar inputs

	$tipo = banco_escape_field($tipo);
	$module_id = banco_escape_field($module_id);
	$publisher_id = banco_escape_field($publisher_id);

	// ===== Pegar o componente.

	$checked = ' checked="checked"';

	$componente_cabecalho = gestor_componente(Array(
		'id' => 'lista-pagina-ou-sistema-ou-publisher',
		'modulo' => $_GESTOR['modulo-id'],
	));

	// ===== Trocar variáveis do input de tipo.

	$componente_cabecalho = modelo_var_troca($componente_cabecalho,'#ambos#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-ambos-label')));
	$componente_cabecalho = modelo_var_troca($componente_cabecalho,'#pagina#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-pagina-label')));
	$componente_cabecalho = modelo_var_troca($componente_cabecalho,'#sistema#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-sistema-label')));
	$componente_cabecalho = modelo_var_troca($componente_cabecalho,'#tipo#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'lista-tipo-label')));

	$componente_cabecalho = modelo_var_troca($componente_cabecalho,'#checked_ambos#',$tipo == 'ambos' ? $checked : '');
	$componente_cabecalho = modelo_var_troca($componente_cabecalho,'#checked_pagina#',$tipo == 'pagina' ? $checked : '');
	$componente_cabecalho = modelo_var_troca($componente_cabecalho,'#checked_sistema#',$tipo == 'sistema' ? $checked : '');

	// ===== Trocar variáveis do input de módulo.

	if($tipo != 'pagina'){
		$modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'nome',
				'id',
			))
			,
			'modulos',
			"WHERE language='".$_GESTOR['linguagem-codigo']."' AND status!='D'"
		);

		if($modulos){
			$selected = ' selected="selected"';

			foreach($modulos as $modulo){
				$componente_cabecalho = modelo_var_in($componente_cabecalho,'<!-- modulos-opcoes -->','<option value="'.$modulo['id'].'"'.($modulo['id'] == $module_id ? $selected : '').'>'.$modulo['nome'].'</option>');
			}
		} else {
			$cel_nome = 'modulos-cel'; $componente_cabecalho = modelo_tag_del($componente_cabecalho,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		}
	} else {
		$module_id = '';
		$cel_nome = 'modulos-cel'; $componente_cabecalho = modelo_tag_del($componente_cabecalho,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	}

	// ===== Trocar variáveis do input de publisher.

	$publisher = banco_select_name
	(
		banco_campos_virgulas(Array(
			'name',
			'id',
		))
		,
		'publisher',
		"WHERE language='".$_GESTOR['linguagem-codigo']."' AND status!='D'"
	);

	if($publisher){
		$selected = ' selected="selected"';

		foreach($publisher as $publisher_item){
			$componente_cabecalho = modelo_var_in($componente_cabecalho,'<!-- publisher-opcoes -->','<option value="'.$publisher_item['id'].'"'.($publisher_item['id'] == $publisher_id ? $selected : '').'>'.$publisher_item['name'].'</option>');
		}
	} else {
		$cel_nome = 'publisher-cel'; $componente_cabecalho = modelo_tag_del($componente_cabecalho,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	}

	return [
		'cabecalho' => $componente_cabecalho,
		'tipo' => $tipo,
		'module_id' => $module_id,
		'publisher_id' => $publisher_id,
	];
}

function publisher_pages_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	$dados = publisher_pages_listar_cabecalho();

	$tipo_nao_visivel = $dados['tipo'] != 'ambos' ? true : null;

	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'tipo',
						'modulo',
						'caminho',
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "language='".$_GESTOR['linguagem-codigo']."' AND publisher_id IS NOT NULL" . ($dados['tipo'] != 'ambos' && $dados['tipo'] != '' ? " AND tipo='{$dados['tipo']}'" : '') . ($dados['module_id'] != '' ? " AND modulo='{$dados['module_id']}'" : '') . ($dados['publisher_id'] != '' ? " AND publisher_id='{$dados['publisher_id']}'" : ''),
				),
				'tabela' => Array(
					'cabecalho' => $dados['cabecalho'],
					'rodape' => true,
					'colunas' => Array(
						Array(
							'id' => 'nome',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'tipo',
							'nao_visivel' => $tipo_nao_visivel,
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-label')),
							'formatar' => Array(
								'id' => 'outroConjunto',
								'conjunto' => [
									[
										'alvo' => 'sistema',
										'troca' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-system')),
									],
									[
										'alvo' => 'pagina',
										'troca' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-type-page')),
									],
								],
							)
						),
						Array(
							'id' => 'modulo',
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'module-name')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">N/A</span>',
								'tabela' => Array(
									'nome' => 'modulos',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
								),
							)
						),
						Array(
							'id' => 'caminho',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-url-path')),
							'ordenar' => 'asc',
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
					'clonar' => Array(
						'url' => 'clonar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-clone')),
						'icon' => 'clone',
						'cor' => 'basic teal',
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

function publisher_pages_ajax_editor_html_switch(){
	global $_GESTOR;
	
	if($_REQUEST['editor_checked'] == 'sim'){
		$valor = true;
	} else {
		$valor = false;
	}
	
	gestor_variaveis_alterar(Array(
		'modulo' => $_GESTOR['modulo-id'],
		'id' => 'editor-html-switch',
		'tipo' => 'bool',
		'valor' => $valor,
	));
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function publisher_pages_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'editor-html-switch': publisher_pages_ajax_editor_html_switch(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		publisher_pages_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': publisher_pages_adicionar(); break;
			case 'editar': publisher_pages_editar(); break;
			case 'clonar': publisher_pages_clonar(); break;
		}
		
		interface_finalizar();
	}
}

publisher_pages_start();

?>