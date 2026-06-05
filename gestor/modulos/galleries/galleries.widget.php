<?php
/**
 * Widget renderer do módulo galleries (req-018 / DEC-026).
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando a página
 * contiver um wrapper:
 *   <!-- widgets#galleries->render({"grupo_slug": "..."}) < -->
 *     ...mockup estático (preview p/ designer)...
 *   <!-- widgets#galleries->render({"grupo_slug": "..."}) > -->
 *
 * O template real (`html` e `css`) vem EXCLUSIVAMENTE do banco. Se o registro não existir ou
 * o template estiver vazio, retornamos string vazia — o mockup do arquivo NÃO é exibido em
 * produção.
 *
 * As imagens curadas em `fields_schema->selected_items` formam uma lista ordenada de objetos
 * com `id`, `caminho`, `imgSrc`, `nome` e `legenda`. O template suporta dois delimitadores:
 *   - <!-- no-item < --> ... <!-- no-item > -->   (galeria vazia)
 *   - <!-- item < --> ... <!-- item > -->         (cada imagem)
 * Variáveis expostas por imagem: [[item#img-src]], [[item#caminho]], [[item#nome]], [[item#legenda]].
 */

// ===== Funções Auxiliares

function galleries_get_version(){
	global $_GESTOR;

    $modulo = json_decode(file_get_contents(__DIR__ . '/galleries.json'), true);

	return isset($modulo['versao']) ? $modulo['versao'] : '1.0.0';
}

// ===== Funções Principais

function galleries_render($params){
	global $_GESTOR;

	if(!is_array($params)) return '';

	$grupo_slug = $params['grupo_slug'] ?? null;
	if(empty($grupo_slug)) return '';

	// ===== Buscar o registro da galeria pelo slug textual, na linguagem corrente.

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'galleries',
		'campos' => Array(
			'id_galleries',
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
	// retornamos vazio — a galeria precisa ser configurada no painel.
	if(trim($html_template) === '') return '';

	// Adicionar JS do widget para comportamentos interativos.
	gestor_pagina_javascript_incluir(array(
		'tipo' => 'widget',
		'modulo_id' => 'galleries',
		'versao' => galleries_get_version(),
	));

	return galleries_widget_render_inline([
		'html' => $html_template,
		'css' => $css_custom,
		'fields_schema' => $registro['fields_schema'] ?? '{}',
	]);
}

/**
 * Renderiza o widget de galeria a partir de inputs crus (sem ler da tabela galleries).
 * Usado pelo widget normal (após DB lookup) e pelo endpoint AJAX widget-preview do painel.
 *
 * @param array $params['html']           string template HTML com blocos item/no-item
 * @param array $params['css']            string CSS custom (opcional)
 * @param array $params['fields_schema']  JSON string com selected_items (lista de imagens)/template_id
 */
function galleries_widget_render_inline($params){
	global $_GESTOR;

	$html_template = (string)($params['html'] ?? '');
	$css_custom   = (string)($params['css'] ?? '');

	if(trim($html_template) === '') return '';

	$schema = $params['fields_schema'] ?? '{}';
	if(is_string($schema)) $schema = json_decode($schema, true);
	if(!is_array($schema)) $schema = [];

	$selected_items = (isset($schema['selected_items']) && is_array($schema['selected_items'])) ? $schema['selected_items'] : [];

	// Modelos de célula extraídos do template (conteúdo interno de cada delimitador).
	$templates = galleries_widget_extrair_blocos($html_template);

	// ===== Galeria vazia: usar o bloco no-item (ou retornar vazio se não existir).

	if(empty($selected_items)){
		if($templates['no_item'] === null) return '';
		$output = galleries_widget_montar_base($html_template, '');
		return galleries_widget_montar_saida($output, $css_custom);
	}

	if($templates['item'] === null){
		// Template sem bloco `item`: nada a repetir.
		$output = galleries_widget_montar_base($html_template, '');
		return galleries_widget_montar_saida($output, $css_custom);
	}

	// ===== Renderizar cada imagem no bloco `item`.

	$itensRendered = '';
	foreach($selected_items as $item){
		if(!is_array($item)) continue;
		$vars = galleries_widget_resolver_item_vars($item);
		$itensRendered .= galleries_widget_aplicar_vars($templates['item'], $vars);
	}

	$output = galleries_widget_montar_base($html_template, $itensRendered);

	return galleries_widget_montar_saida($output, $css_custom);
}

/**
 * Resolve as variáveis renderizáveis de uma imagem.
 *
 * - `img-src` : URL completa para a tag <img src>. Usa `imgSrc` salvo; se vier um caminho
 *               relativo, prefixa com a url-raiz do site.
 * - `caminho` : caminho relativo do arquivo.
 * - `nome`    : nome original do arquivo.
 * - `legenda` : legenda personalizada.
 *
 * @return array ['img-src' => ..., 'caminho' => ..., 'nome' => ..., 'legenda' => ...]
 */
function galleries_widget_resolver_item_vars($item){
	global $_GESTOR;

	$caminho = isset($item['caminho']) ? (string)$item['caminho'] : '';
	$imgSrc  = isset($item['imgSrc']) ? (string)$item['imgSrc'] : '';
	$nome    = isset($item['nome']) ? (string)$item['nome'] : '';
	$legenda = isset($item['legenda']) ? (string)$item['legenda'] : '';

	// img-src: prioriza imgSrc; cai para o caminho. Prefixa url-raiz quando relativo.
	$src = $imgSrc !== '' ? $imgSrc : $caminho;
	if($src !== '' && !preg_match('#^(https?:)?//#', $src) && strpos($src, 'data:') !== 0){
		$src = $_GESTOR['url-raiz'].ltrim($src, '/');
	}

	return [
		'img-src' => $src,
		'caminho' => $caminho,
		'nome'    => $nome,
		'legenda' => $legenda,
	];
}

/**
 * Substitui as variáveis [[item#X]] de um bloco pelos valores resolvidos.
 * Tolera o cerco de arrobas do banco (@[[item#X]]@): consome as arrobas adjacentes.
 */
function galleries_widget_aplicar_vars($bloco, $vars){
	return preg_replace_callback('/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($vars){
		$name = $m[1];
		return isset($vars[$name]) ? (string)$vars[$name] : '';
	}, $bloco);
}

/**
 * Extrai o conteúdo interno dos dois delimitadores de célula do template.
 * Retorna null para o bloco ausente.
 */
function galleries_widget_extrair_blocos($html_template){
	$blocos = ['item' => null, 'no_item' => null];

	if(preg_match('/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i', $html_template, $m)){
		$blocos['item'] = $m[1];
	}
	if(preg_match('/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i', $html_template, $m)){
		$blocos['no_item'] = $m[1];
	}

	return $blocos;
}

/**
 * Monta o HTML base substituindo a região do modelo de célula pelo conteúdo renderizado.
 *
 * - Com itens: a âncora `item` recebe o HTML renderizado; o `no-item` é removido.
 * - Sem itens: o modelo `item` é removido e o conteúdo do `no-item` é exposto.
 */
function galleries_widget_montar_base($html_template, $itensRendered){
	$padraoItem   = '/<!--\s*item\s*<\s*-->[\s\S]*?<!--\s*item\s*>\s*-->/i';
	$padraoNoItem = '/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i';

	$out = $html_template;

	if($itensRendered === ''){
		$out = preg_replace($padraoItem, '', $out, 1);
		if(preg_match($padraoNoItem, $out, $m)){
			$out = galleries_widget_preg_replace_literal($padraoNoItem, $m[1], $out, 1);
		}
		return $out;
	}

	$out = galleries_widget_preg_replace_literal($padraoItem, $itensRendered, $out, 1);
	$out = preg_replace($padraoNoItem, '', $out, 1);

	return $out;
}

/**
 * preg_replace com replacement literal (callback): evita a interpretação de backreferences
 * ($1, \1) caso o HTML renderizado contenha esses caracteres.
 */
function galleries_widget_preg_replace_literal($pattern, $replacement, $subject, $limit = -1){
	return preg_replace_callback($pattern, function() use ($replacement){
		return $replacement;
	}, $subject, $limit);
}

/**
 * Anexa o CSS customizado da galeria como tag <style> antes do HTML renderizado.
 */
function galleries_widget_montar_saida($html, $css){
	if(trim((string)$css) === '') return $html;
	return '<style>'.$css.'</style>'.$html;
}
