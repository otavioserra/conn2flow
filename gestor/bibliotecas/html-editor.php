<?php

global $_GESTOR;

$_GESTOR['biblioteca-html-editor']							=	Array(
	'versao' => '1.0.1',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Componente Editor HTML.
 *
 * Renderiza o componente Editor HTML e suas dependências.
 *
 * @param string $params['editar'] caso seja edição.
 * @param array $params['modulo'] dados do módulo atual.
 * @param array $params['alvo'] alvo de modelos e ia.
 * 
 */
function html_editor_componente($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// ===== Verificar parâmetros

	$alvo = isset($alvo)? $alvo : 'paginas';

    // ===== Inclusão do CodeMirror

	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/theme/tomorrow-night-bright.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.css" />');
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.css" />');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/selection/active-line.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/searchcursor.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/search.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/scroll/annotatescrollbar.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/jump-to-line.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/edit/matchbrackets.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/xml/xml.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/css/css.js"></script>');
	gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/htmlmixed/htmlmixed.js"></script>');

	// ===== Inclusão Componentes

	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-alerta',
		)
	));

	// ===== HTML Editor Componente

	$html_editor = gestor_componente(Array(
		'id' => 'html-editor',
	));

	// ===== Modificações do Editor HTML

	switch($alvo){
		case 'publisher':
			$html_editor_publisher_simulation = gestor_componente(Array(
				'id' => 'html-editor-publisher-simulation',
			));

			$html_editor = modelo_var_troca($html_editor,'#html-editor-publisher-simulation#',$html_editor_publisher_simulation);
		break;
		default:
			$cel_nome = 'publisher-html-editor-btns'; $html_editor = modelo_tag_del($html_editor,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$cel_nome = 'publisher-html-editor-variables-menu'; $html_editor = modelo_tag_del($html_editor,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$cel_nome = 'publisher-html-editor-variables-tab'; $html_editor = modelo_tag_del($html_editor,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$html_editor = modelo_var_troca($html_editor,'#html-editor-publisher-simulation#','');
	}

    // ===== Editor HTML visual
    $html_editor = modelo_var_troca($html_editor,'#html-editor-modal#',html_editor_include([
        'js_vars' => Array(
            'alvo' => $alvo,
        )
    ]));

	// ===== Pré-Visualização

	$modalPagina = gestor_componente(Array(
		'id' => 'html-editor-visual-modal',
	));

	$modalPagina = modelo_var_troca($modalPagina,'#title#',gestor_variaveis(Array('id' => 'html-editor-modal-title-preview')));
	$modalPagina = modelo_var_troca($modalPagina,'#desktop#',gestor_variaveis(Array('id' => 'html-editor-modal-desktop-preview')));
	$modalPagina = modelo_var_troca($modalPagina,'#tablet#',gestor_variaveis(Array('id' => 'html-editor-modal-tablet-preview')));
	$modalPagina = modelo_var_troca($modalPagina,'#mobile#',gestor_variaveis(Array('id' => 'html-editor-modal-mobile-preview')));
	$modalPagina = modelo_var_troca($modalPagina,'#button-tooltip#',gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
	$modalPagina = modelo_var_troca($modalPagina,'#button-value#',gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));
	$modalPagina = modelo_var_troca($modalPagina,'#button-back-tooltip#',gestor_variaveis(Array('id' => 'html-editor-button-back-tooltip')));
	$modalPagina = modelo_var_troca($modalPagina,'#button-back-value#',gestor_variaveis(Array('id' => 'html-editor-button-back-value')));

	$html_editor = modelo_var_troca($html_editor,'#html-editor-visual-modal#',$modalPagina);

	// ===== Modelos de Páginas

	$modelosPaginas = gestor_componente(Array(
		'id' => 'html-editor-modelos',
	));

	$html_editor = modelo_var_troca($html_editor,'<!-- modelos-componente -->',$modelosPaginas);

	// ===== Assistente IA

    gestor_incluir_biblioteca('ia');

	$html_editor = modelo_var_troca($html_editor,'<!-- ia-componente -->',ia_renderizar_prompt(
		Array(
			'alvo' => $alvo,
			'prompt_controls' => '<div class="menu-pagina-conteudo" data-id="assistente-ia"></div>'
		)
	));

	// ===== Modificações de página

	$html_editor_page_modification = gestor_componente(Array(
		'id' => 'html-editor-page-modification',
	));

	$selectPaginaConteudo = [
        [
            "texto" => gestor_variaveis(Array('id' => 'html-editor-select-all')),
            "valor" => "tudo"
        ],
        [
            "texto" => gestor_variaveis(Array('id' => 'html-editor-select-section')),
            "valor" => "sessao"
        ]
    ];

	$select_modification_page = '';
	if($selectPaginaConteudo){
		foreach($selectPaginaConteudo as $option){
			$select_modification_page .= '<option value="'.$option['valor'].'"'.($option['valor'] == 'tudo'? ' selected="selected"':'').'>'.htmlspecialchars($option['texto']).'</option>';
		}
	}

	$html_editor_page_modification = modelo_var_troca_tudo($html_editor_page_modification,'#select-modification-page#',$select_modification_page);

	$html_editor = modelo_var_troca($html_editor,'#html-editor-page-modification#',$html_editor_page_modification);

	// ===== Dropdown com todos os backups e conteúdo HTML/CSS
    if(isset($editar)){
        $html_editor = modelo_var_troca($html_editor,'#pagina-editor-html-backup#',interface_backup_campo_select(Array(
            'campo' => 'html',
            'callback' => 'adminPaginasBackupCampo',
            'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
        )));
        
        $html_editor = modelo_var_troca_fim($html_editor,'#pagina-html-backup#',interface_backup_campo_select(Array(
            'campo' => 'html',
            'callback' => 'adminPaginasBackupCampo',
            'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
        )));
        
        $html_editor = modelo_var_troca_fim($html_editor,'#pagina-css-backup#',interface_backup_campo_select(Array(
            'campo' => 'css',
            'callback' => 'adminPaginasBackupCampo',
            'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
        )));
        
        $html_editor = modelo_var_troca_fim($html_editor,'#pagina-css-compiled-backup#',interface_backup_campo_select(Array(
            'campo' => 'css_compiled',
            'callback' => 'adminPaginasBackupCampo',
            'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
        )));
        
        $html_editor = modelo_var_troca_fim($html_editor,'#pagina-html-extra-head-backup#',interface_backup_campo_select(Array(
            'campo' => 'html_extra_head',
            'callback' => 'adminPaginasBackupCampo',
            'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
        )));
    } else {
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-html#','');
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-html-extra-head#','');
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-css#','');
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-css-compiled#','');

        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-editor-html-backup#','');
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-html-backup#','');
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-css-backup#','');
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-css-compiled-backup#','');
        $html_editor = modelo_var_troca_tudo($html_editor,'#pagina-html-extra-head-backup#','');
    }

    // ===== Incluir script JS Interface do HTML Editor que conecta o mesmo com o Gestor/Modulos
	gestor_pagina_javascript_incluir('biblioteca','html-editor-interface');

    // ===== Retornar componente
    return $html_editor;
}

/**
 * Incluir o editor HTML visual.
 *
 * Renderiza o editor HTML visual e suas dependências.
 *
 * @param string $params['js_vars'] variáveis JS a serem incluídas.
 * 
 */
function html_editor_include($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

    // Incluir variáveis no HTML Editor
    $overlay_title = gestor_variaveis(Array('id' => 'html-editor-overlay-title'));

	// Configurar ambiente JS do HTML Editor
	$js_script = gestor_pagina_javascript_incluir('biblioteca','html-editor',true);

    // Definir variáveis JS padrão do HTML Editor e demais passadas por parâmetro
    $js_vars_default = Array(
        'script' => $js_script,
        'overlay_title' => $overlay_title,
    );

    // Mesclar variáveis padrão com as passadas por parâmetro
    $js_vars_final = isset($js_vars)? array_merge($js_vars_default,$js_vars) : $js_vars_default;

    // Incluir variáveis JS do HTML Editor
    gestor_js_variavel_incluir('html_editor',$js_vars_final);

    // Incluir script JS Helper do HTML Editor que conecta o mesmo com o Gestor/Modulos
	gestor_pagina_javascript_incluir('biblioteca','html-editor-helper');

    // Incluir componentes do HTML Editor

    $html_editor_modal = gestor_componente(Array(
		'id' => 'html-editor-modal'
	));

    return '<div class="html-editor-container hidden">'.$html_editor_modal.'</div>';
}


/**
 * AJAX Interface.
 *
 * Descrição
 *
 * @param array $params Parâmetros da função.
 */
function html_editor_ajax_interface($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

    gestor_incluir_biblioteca('ia');

	ia_ajax_interface();

    switch($_GESTOR['ajax-opcao']){
        case 'html-editor-ia-requests': html_editor_ajax_ia_requests(); break;
        case 'html-editor-templates-load': html_editor_ajax_templates_load(); break;
	}
}

/**
 * AJAX Templates.
 *
 * Retorna um template via AJAX para o frontend.
 *
 */
function html_editor_ajax_templates_load(){
	global $_GESTOR;

	// Pegar parâmetros de paginação
	$pagina = (int)($_REQUEST['params']['pagina'] ?? 1);
	$limite = (int)($_REQUEST['params']['limite'] ?? 10);
	$framework_css = ($_REQUEST['params']['framework_css'] ?? 'fomantic-ui');
	$alvo = ($_REQUEST['params']['alvo'] ?? 'paginas');
	$offset = ($pagina - 1) * $limite;

	// Pegar idioma atual
	$idioma = $_GESTOR['linguagem-codigo'];

	// Buscar templates no banco de dados
	$retorno_bd = banco_select([
		'tabela' => 'templates',
		'campos' => [
			'id',
			'nome',
			'thumbnail',
			'target',
			'language',
			'html',
			'html_extra_head',
			'css',
			'css_compiled',
			'framework_css',
		],
		'extra' => 
			"WHERE status = 'A' AND framework_css = '" . banco_escape_field($framework_css) . "' AND language = '" . banco_escape_field($idioma) . "' AND target = '" . banco_escape_field($alvo) . "'"
			." ORDER BY data_modificacao DESC"
			." LIMIT " . $limite . " OFFSET " . $offset
	]);

	$modelos = [];
	if($retorno_bd){
		foreach($retorno_bd as $modelo){
			$modelos[] = [
				'id' => $modelo['id'],
				'nome' => $modelo['nome'],
				'thumbnail' => $_GESTOR['url-raiz'] . ($modelo['thumbnail'] ?: 'images/imagem-padrao.png'),
				'target' => $modelo['target'],
				'language' => $modelo['language'],
				'html' => $modelo['html'],
				'html_extra_head' => $modelo['html_extra_head'],
				'css' => $modelo['css'],
				'css_compiled' => $modelo['css_compiled'],
				'framework_css' => $modelo['framework_css']
			];
		}
	}

	// Verificar se há mais modelos
	$total_modelos = banco_select([
		'tabela' => 'templates',
		'campos' => ['COUNT(*) as total'],
		'extra' => 
			"WHERE status = 'A' AND framework_css = '" . banco_escape_field($framework_css) . "' AND language = '" . banco_escape_field($idioma) . "' AND target = '" . banco_escape_field($alvo) . "'"
	]);

	$tem_mais = false;
	if($total_modelos && $total_modelos[0]['COUNT(*) as total'] > ($offset + count($modelos))){
		$tem_mais = true;
	}

	// Retorno do AJAX
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'data' => [
			'modelos' => $modelos,
			'tem_mais' => $tem_mais,
			'pagina' => $pagina,
			'total_carregados' => count($modelos)
		]
	);
}

/**
 * AJAX IA Requests.
 *
 * Retorna uma requisição IA via AJAX para o frontend.
 *
 */
function html_editor_ajax_ia_requests(){
	global $_GESTOR;

	// Pegar dados anterior da página
	$html = $_REQUEST['data']['html'] ?? '';
	$css = $_REQUEST['data']['css'] ?? '';
	$framework_css = $_REQUEST['data']['framework_css'] ?? '';
	$sessao_id = $_REQUEST['data']['sessao_id'] ?? '';
	$sessao_opcao = $_REQUEST['data']['sessao_opcao'] ?? '';

	// Modificar o modo IA antes de enviar
	$modo = $_REQUEST['mode'] ?? '';

	$modo = modelo_var_troca_tudo($modo,'{{html}}',$html);
	$modo = modelo_var_troca_tudo($modo,'{{css}}',$css);
	$modo = modelo_var_troca_tudo($modo,'{{framework_css}}',$framework_css);

	// Preparar prompt completo
	$prompt = $_REQUEST['prompt'] ?? '';
	$prompt = $modo . "\n\n" . $prompt;

	// Pegar o modelo e servidor id
	$server_id = $_REQUEST['server_id'] ?? '';
	$model = $_REQUEST['model'] ?? null;

	// Enviar request para o servidor de IA
	$retorno = ia_enviar_prompt([
		'servidor_id' => $server_id,
		'prompt' => $prompt,
		'modelo' => $model,
	]);

	// Pegar HTML e CSS do retorno
	$html_gerado = '';
	$css_gerado = '';
	
	if($retorno['status'] === 'success' && isset($retorno['data']['texto_gerado'])){
		$texto_resposta = $retorno['data']['texto_gerado'];
		
		// Extrair HTML entre ```html e ```
		if(preg_match('/```html\s*(.*?)\s*```/s', $texto_resposta, $matches_html)){
			$html_gerado = trim($matches_html[1]);
		}
		
		// Extrair CSS entre ```css e ```
		if(preg_match('/```css\s*(.*?)\s*```/s', $texto_resposta, $matches_css)){
			$css_gerado = trim($matches_css[1]);
		}
	} else {
		// Em caso de erro, retornar mensagem
		$_GESTOR['ajax-json'] = Array(
			'status' => 'error',
			'message' => $retorno['message'] ?? gestor_variaveis(Array('modulo' => 'admin-ia','id' => 'requests-error-message')),
		);
		return;
	}

	// Incluir HTML e CSS gerado no retorno
	$retorno['data']['html_gerado'] = $html_gerado;
	$retorno['data']['css_gerado'] = $css_gerado;
	$retorno['data']['sessao_id'] = $sessao_id;
	$retorno['data']['sessao_opcao'] = $sessao_opcao;

	// Retorno do AJAX
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'data' => $retorno['data'],
	);
}

?>