<?php

global $_GESTOR;

$_GESTOR['biblioteca-html-editor']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Incluir o editor HTML.
 *
 * Renderiza o componente de editor HTML.
 *
 */
function html_editor_include($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

    // Incluir variáveis
    $overlay_title = gestor_variaveis(Array('id' => 'html-editor-overlay-title'));

	// Incluir script JS
	$js_script = gestor_pagina_javascript_incluir('biblioteca','html-editor',true);

    gestor_js_variavel_incluir('html_editor',Array(
        'script' => $js_script,
        'overlay_title' => $overlay_title,
    ));

    // Incluir componentes
    $html_editor_modal = gestor_componente(Array(
		'id' => 'html-editor-modal'
	));

    $_GESTOR['pagina'] .= '<div class="html-editor-container hidden">'.$html_editor_modal.'</div>';
}

?>