<?php
/**
 * Widget renderer do módulo galleries (req-018 / DEC-026; req-019 / DEC-029, DEC-031).
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
 * com `id`, `caminho`, `imgSrc`, `nome` e `legenda`. O template suporta os delimitadores:
 *   - <!-- no-item < --> ... <!-- no-item > -->                 (galeria vazia)
 *   - <!-- item < --> ... <!-- item > -->                       (cada imagem)
 *   - <!-- controls-arrows < --> ... <!-- controls-arrows > --> (setas; só se show_arrows)
 *   - <!-- controls-dots < --> ... <!-- controls-dots > -->     (pontinhos; só se show_dots)
 *   - <!-- dot-item < --> ... <!-- dot-item > -->               (um pontinho por imagem)
 * Variáveis por imagem: [[item#img-src]], [[item#caminho]], [[item#nome]], [[item#legenda]].
 * Variáveis por pontinho: [[dot#index]] (0,1,2,...) e [[dot#active-class]] (ativa no índice 0).
 * Variáveis globais de controle: [[show_arrows]], [[show_dots]], [[autoplay]],
 * [[autoplay_speed]], [[loop]] (resolvidas no HTML final como 'true'/'false'/inteiro).
 */

// ===== Funções Auxiliares

function galleries_get_version(){
	global $_GESTOR;

    $modulo = json_decode(file_get_contents(__DIR__ . '/galleries.json'), true);

	return isset($modulo['versao']) ? $modulo['versao'] : '1.0.0';
}

/**
 * Lê um valor de controle booleano do schema, tolerando bool/int/string ('true'/'1'/'on').
 */
function galleries_widget_bool($schema, $key, $default){
	if(!is_array($schema) || !array_key_exists($key, $schema)) return $default;
	$v = $schema[$key];
	if(is_bool($v)) return $v;
	if(is_int($v)) return $v !== 0;
	$v = strtolower(trim((string)$v));
	return ($v === 'true' || $v === '1' || $v === 'yes' || $v === 'on');
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
 * @param array $params['html']           string template HTML com blocos item/no-item/controles
 * @param array $params['css']            string CSS custom (opcional)
 * @param array $params['fields_schema']  JSON string com selected_items + controles/template_id
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
		// Sem imagens: não há slides — remover os blocos de controle.
		$output = galleries_widget_processar_controles($output, false, false, []);
		$output = galleries_widget_resolver_globais($output, $schema);
		return galleries_widget_montar_saida($output, $css_custom);
	}

	// req-019: controles de exibição (defaults: setas/dots/loop ativos; autoplay inativo).
	$show_arrows = galleries_widget_bool($schema, 'show_arrows', true);
	$show_dots   = galleries_widget_bool($schema, 'show_dots', true);

	if($templates['item'] === null){
		// Template sem bloco `item`: nada a repetir, mas ainda processa controles/globais.
		$output = galleries_widget_montar_base($html_template, '');
		$output = galleries_widget_processar_controles($output, $show_arrows, $show_dots, $selected_items);
		$output = galleries_widget_resolver_globais($output, $schema);
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

	// req-019: processar os blocos de controle (setas, pontinhos e um dot por imagem).
	$output = galleries_widget_processar_controles($output, $show_arrows, $show_dots, $selected_items);

	// req-019 / DEC-031: resolver as variáveis globais de controle no HTML final.
	$output = galleries_widget_resolver_globais($output, $schema);

	return galleries_widget_montar_saida($output, $css_custom);
}

/**
 * Resolve as variáveis renderizáveis de uma imagem.
 *
 * - `img-src` : URL pública para a tag <img src>. req-019 / DEC-029: prioriza o `caminho`
 *               (arquivo original) sobre o `imgSrc` (thumbnail do painel). Prefixa a url-raiz
 *               quando relativo.
 * - `caminho` : caminho relativo do arquivo original.
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

	// img-src: prioriza o caminho original; cai para o imgSrc. Prefixa url-raiz quando relativo.
	$src = $caminho !== '' ? $caminho : $imgSrc;
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
 * req-019 / DEC-029: processa os blocos de controle dinâmicos do carrossel/slider.
 *
 * - controls-arrows: desembrulhado quando `show_arrows`; removido caso contrário.
 * - controls-dots:   desembrulhado quando `show_dots` (com um dot por imagem); removido caso contrário.
 * - dot-item:        repetido para cada imagem dentro de controls-dots, injetando [[dot#index]]
 *                    (0,1,2,...) e [[dot#active-class]] (classe ativa só no índice 0).
 *
 * @param string $html        HTML já com itens/no-item montados.
 * @param bool   $show_arrows Exibir setas.
 * @param bool   $show_dots   Exibir pontinhos.
 * @param array  $items       Lista de imagens (define a quantidade de dots).
 */
function galleries_widget_processar_controles($html, $show_arrows, $show_dots, $items){
	$padraoArrows = '/<!--\s*controls-arrows\s*<\s*-->([\s\S]*?)<!--\s*controls-arrows\s*>\s*-->/i';
	if($show_arrows){
		$html = preg_replace_callback($padraoArrows, function($m){ return $m[1]; }, $html);
	} else {
		$html = preg_replace($padraoArrows, '', $html);
	}

	$padraoDots = '/<!--\s*controls-dots\s*<\s*-->([\s\S]*?)<!--\s*controls-dots\s*>\s*-->/i';
	if($show_dots){
		$count = is_array($items) ? count($items) : 0;
		$html = preg_replace_callback($padraoDots, function($m) use ($count){
			return galleries_widget_render_dots($m[1], $count);
		}, $html);
	} else {
		$html = preg_replace($padraoDots, '', $html);
	}

	return $html;
}

/**
 * Repete o bloco dot-item `$count` vezes, injetando o índice e a classe ativa (índice 0).
 * Se o conteúdo de controls-dots não contiver um bloco dot-item, é devolvido inalterado.
 */
function galleries_widget_render_dots($inner, $count){
	$padraoDotItem = '/<!--\s*dot-item\s*<\s*-->([\s\S]*?)<!--\s*dot-item\s*>\s*-->/i';
	if(!preg_match($padraoDotItem, $inner, $m)) return $inner;

	$tpl = $m[1];
	$dots = '';
	for($i = 0; $i < $count; $i++){
		$active = ($i === 0) ? 'gallery-dot-active' : '';
		$d = str_replace(['@[[dot#index]]@', '[[dot#index]]'], (string)$i, $tpl);
		$d = str_replace(['@[[dot#active-class]]@', '[[dot#active-class]]'], $active, $d);
		$dots .= $d;
	}

	return galleries_widget_preg_replace_literal($padraoDotItem, $dots, $inner, 1);
}

/**
 * req-019 / DEC-031: resolve as variáveis GLOBAIS de controle no HTML final, com e sem arrobas.
 * O regex só casa `[[nome]]` (sem `#`), preservando as variáveis de item/dot (`[[item#..]]`,
 * `[[dot#..]]`).
 */
function galleries_widget_resolver_globais($html, $schema){
	$map = [
		'show_arrows'    => galleries_widget_bool($schema, 'show_arrows', true) ? 'true' : 'false',
		'show_dots'      => galleries_widget_bool($schema, 'show_dots', true) ? 'true' : 'false',
		'autoplay'       => galleries_widget_bool($schema, 'autoplay', false) ? 'true' : 'false',
		'autoplay_speed' => (string)(int)($schema['autoplay_speed'] ?? 3000),
		'loop'           => galleries_widget_bool($schema, 'loop', true) ? 'true' : 'false',
	];

	return preg_replace_callback('/@?\[\[([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($map){
		$name = $m[1];
		return array_key_exists($name, $map) ? $map[$name] : $m[0];
	}, $html);
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
