<?php
/**
 * Widget renderer do módulo menus (req-015).
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando
 * a página contiver um wrapper:
 *   <!-- widgets#menus->render({"grupo_slug": "..."}) < -->
 *     ...mockup estático (preview p/ designer)...
 *   <!-- widgets#menus->render({"grupo_slug": "..."}) > -->
 *
 * O template real (`html` e `css`) vem EXCLUSIVAMENTE do banco. Se o registro não
 * existir ou o template estiver vazio, retornamos string vazia — o mockup do arquivo
 * NÃO é exibido em produção.
 *
 * Menus são livres de publicadores: os itens curados em `fields_schema->selected_items`
 * referenciam diretamente slugs de páginas do site (tabela `paginas`).
 */

function menus_render($params){
	global $_GESTOR;

	if(!is_array($params)) return '';

	$grupo_slug = $params['grupo_slug'] ?? null;
	if(empty($grupo_slug)) return '';

	// ===== Buscar o registro do menu pelo slug textual, na linguagem corrente.

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'menus',
		'campos' => Array(
			'id_menus',
			'id',
			'fields_schema',
			'html',
			'css',
		),
		'extra' =>
			"WHERE id='".banco_escape_field($grupo_slug)."'"
			." AND status='A'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
	));

	if(!$registro) return '';

	$html_template = isset($registro['html']) ? $registro['html'] : '';
	$css_custom   = isset($registro['css'])  ? $registro['css']  : '';

	// Sem fallback para o mockup do arquivo físico. Se o template do banco estiver vazio,
	// retornamos vazio — o menu precisa ser configurado no painel.
	if(trim($html_template) === '') return '';

	return menus_widget_render_inline([
		'html' => $html_template,
		'css' => $css_custom,
		'fields_schema' => $registro['fields_schema'] ?? '{}',
	]);
}

/**
 * Renderiza o widget de menu a partir de inputs crus (sem ler da tabela menus).
 * Usado pelo widget normal (após DB lookup) e pelo endpoint AJAX widget-preview do
 * painel de edição.
 *
 * @param array $params['html']           string template HTML com blocos item/no-item
 * @param array $params['css']            string CSS custom (opcional)
 * @param array $params['fields_schema']  JSON string com selected_items/template_id
 */
function menus_widget_render_inline($params){
	$html_template = (string)($params['html'] ?? '');
	$css_custom   = (string)($params['css'] ?? '');

	if(trim($html_template) === '') return '';

	$schema = $params['fields_schema'] ?? '{}';
	if(is_string($schema)) $schema = json_decode($schema, true);
	if(!is_array($schema)) $schema = [];

	$selected_items = isset($schema['selected_items']) && is_array($schema['selected_items']) ? $schema['selected_items'] : [];

	$itens = [];
	if(!empty($selected_items)){
		$itens = menus_widget_buscar_itens([
			'selected_items' => $selected_items,
		]);
	}

	$padraoItem   = '/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i';
	$padraoNoItem = '/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i';

	$temItemLoop   = preg_match($padraoItem, $html_template, $itemMatch);
	$temNoItemBloco = preg_match($padraoNoItem, $html_template, $noItemMatch);

	if(empty($itens)){
		if(!$temNoItemBloco) return '';

		$output = $temItemLoop ? preg_replace($padraoItem, '', $html_template, 1) : $html_template;
		$output = preg_replace($padraoNoItem, $noItemMatch[1], $output, 1);

		return menus_widget_montar_saida($output, $css_custom);
	}

	$html_template = $temNoItemBloco ? preg_replace($padraoNoItem, '', $html_template, 1) : $html_template;

	if(!$temItemLoop){
		return menus_widget_montar_saida($html_template, $css_custom);
	}

	$itemTemplate = $itemMatch[1];
	$itemsRendered = '';

	foreach($itens as $item){
		$itemRendered = preg_replace_callback('/\[\[item#([a-zA-Z0-9_\-]+)\]\]/', function($m) use ($item){
			$varName = $m[1];
			return isset($item[$varName]) ? (string)$item[$varName] : '';
		}, $itemTemplate);

		$itemsRendered .= $itemRendered;
	}

	$output = preg_replace($padraoItem, $itemsRendered, $html_template, 1);

	return menus_widget_montar_saida($output, $css_custom);
}

/**
 * Anexa o CSS customizado do menu como tag <style> antes do HTML renderizado.
 */
function menus_widget_montar_saida($html, $css){
	if(trim((string)$css) === '') return $html;
	return '<style>'.$css.'</style>'.$html;
}

/**
 * Busca as páginas curadas como itens do menu (slugs em selected_items), preservando
 * a ordem informada. Cada item retornado expõe os campos: `label` (nome da página),
 * `url` (caminho absoluto), `slug` (id da página).
 */
function menus_widget_buscar_itens($params){
	global $_GESTOR;

	$selected_items = isset($params['selected_items']) && is_array($params['selected_items']) ? $params['selected_items'] : [];
	if(empty($selected_items)) return [];

	$language = $_GESTOR['linguagem-codigo'];

	$ids_escaped = array_map(function($slug){
		return "'".banco_escape_field((string)$slug)."'";
	}, $selected_items);
	$ids_in = implode(',', $ids_escaped);

	$rows = banco_select(Array(
		'tabela' => 'paginas AS p',
		'campos' => Array(
			'p.id',
			'p.nome',
			'p.caminho',
		),
		'extra' =>
			"WHERE p.status='A'"
			." AND p.language='".banco_escape_field($language)."'"
			." AND p.id IN (".$ids_in.")"
	));

	if(!is_array($rows)) $rows = [];

	$mapa = [];
	foreach($rows as $row){
		$slug = $row['p.id'] ?? '';
		if($slug === '') continue;
		$mapa[$slug] = [
			'slug'  => $slug,
			'label' => $row['p.nome'] ?? '',
			'url'   => $row['p.caminho'] ? $_GESTOR['url-raiz'].$row['p.caminho'] : '',
		];
	}

	// Reordenar conforme a ordem informada em selected_items.
	$ordenados = [];
	foreach($selected_items as $slug){
		if(isset($mapa[$slug])) $ordenados[] = $mapa[$slug];
	}

	return $ordenados;
}
