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
			'css_compiled',
			'html_extra_head',
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
		'css_compiled' => $registro['css_compiled'] ?? '',
		'html_extra_head' => $registro['html_extra_head'] ?? '',
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
	$css_compiled    = (string)($params['css_compiled'] ?? '');
	$html_extra_head = (string)($params['html_extra_head'] ?? '');

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
		return galleries_widget_montar_saida($output, $css_custom, $css_compiled, $html_extra_head);
	}

	// req-019: controles de exibição (defaults: setas/dots/loop ativos; autoplay inativo).
	$show_arrows = galleries_widget_bool($schema, 'show_arrows', true);
	$show_dots   = galleries_widget_bool($schema, 'show_dots', true);

	if($templates['item'] === null){
		// Template sem bloco `item`: nada a repetir, mas ainda processa controles/globais.
		$output = galleries_widget_montar_base($html_template, '');
		$output = galleries_widget_processar_controles($output, $show_arrows, $show_dots, $selected_items);
		$output = galleries_widget_resolver_globais($output, $schema);
		return galleries_widget_montar_saida($output, $css_custom, $css_compiled, $html_extra_head);
	}

	// ===== Renderizar cada imagem no bloco `item`.

	// req-024 / DEC-037: resolver os links das imagens. As páginas são resolvidas em lote
	// (slug -> URL canônica); os publicadores são resolvidos sob demanda (publicação mais
	// recente) com cache por publisher_id+ordenação para evitar queries repetidas.
	$page_ids = [];
	foreach($selected_items as $item){
		if(is_array($item) && (($item['link_type'] ?? '') === 'pagina') && !empty($item['link_page_id'])){
			$page_ids[(string)$item['link_page_id']] = true;
		}
	}
	$paginasMap = galleries_widget_carregar_paginas(array_keys($page_ids));
	$publicadorCache = [];

	$itensRendered = '';
	foreach($selected_items as $item){
		if(!is_array($item)) continue;
		$vars = galleries_widget_resolver_item_vars($item, $paginasMap, $publicadorCache);
		$itensRendered .= galleries_widget_aplicar_vars($templates['item'], $vars);
	}

	$output = galleries_widget_montar_base($html_template, $itensRendered);

	// req-019: processar os blocos de controle (setas, pontinhos e um dot por imagem).
	$output = galleries_widget_processar_controles($output, $show_arrows, $show_dots, $selected_items);

	// req-019 / DEC-031: resolver as variáveis globais de controle no HTML final.
	$output = galleries_widget_resolver_globais($output, $schema);

	return galleries_widget_montar_saida($output, $css_custom, $css_compiled, $html_extra_head);
}

/**
 * Resolve as variáveis renderizáveis de uma imagem.
 *
 * - `img-src`          : URL pública para a tag <img src>. req-019 / DEC-029: prioriza o `caminho`
 *                        (arquivo original) sobre o `imgSrc` (thumbnail do painel). Prefixa a
 *                        url-raiz quando relativo.
 * - `caminho`          : caminho relativo do arquivo original.
 * - `nome`             : nome original do arquivo.
 * - `legenda`          : legenda personalizada.
 * - `link-url`         : req-024 / DEC-037. URL do link da imagem (ou `javascript:void(0);`).
 * - `link-target`      : alvo do link (`_self`/`_blank`).
 * - `link-css-classes` : classes CSS extras aplicadas à âncora.
 *
 * @param array $item            Item curado (imagem + metadados de link).
 * @param array $paginasMap      Mapa slug => ['url' => ...] das páginas resolvidas em lote.
 * @param array $publicadorCache Cache (por referência) das publicações mais recentes resolvidas.
 * @return array
 */
function galleries_widget_resolver_item_vars($item, $paginasMap = [], &$publicadorCache = []){
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

	// req-024 / DEC-037: link individual da imagem.
	$link = galleries_widget_resolver_link($item, $paginasMap, $publicadorCache);

	return [
		'img-src'          => $src,
		'caminho'          => $caminho,
		'nome'             => $nome,
		'legenda'          => $legenda,
		'link-url'         => $link['url'],
		'link-target'      => $link['target'],
		'link-css-classes' => $link['css_classes'],
	];
}

/**
 * req-024 / DEC-037: resolve o link de uma imagem da galeria conforme o `link_type`:
 *
 * - `nenhum` (e desconhecidos) : `javascript:void(0);` (imagem não clicável).
 * - `pagina`                   : URL canônica da página (`link_page_id`) vinda do mapa em lote.
 * - `link-custom`              : URL manual do schema (`link_url`).
 * - `link-css-classes`         : URL manual do schema + classe CSS própria (`link_css_classes`).
 * - `publicador`               : URL da publicação mais recente do publicador (`link_publisher_id`)
 *                                segundo `link_order_by`.
 *
 * @return array ['url' => ..., 'target' => '_self'|'_blank', 'css_classes' => ...]
 */
function galleries_widget_resolver_link($item, $paginasMap, &$publicadorCache){
	$type   = isset($item['link_type']) ? (string)$item['link_type'] : 'nenhum';
	$target = isset($item['link_target']) ? (string)$item['link_target'] : '_self';
	$css    = isset($item['link_css_classes']) ? (string)$item['link_css_classes'] : '';
	$url    = 'javascript:void(0);';

	switch($type){
		case 'pagina':
			$pid = isset($item['link_page_id']) ? (string)$item['link_page_id'] : '';
			if($pid !== '' && isset($paginasMap[$pid]) && $paginasMap[$pid]['url'] !== ''){
				$url = $paginasMap[$pid]['url'];
			}
			break;

		case 'link-custom':
		case 'link-css-classes':
		case 'link-action':
			$manual = isset($item['link_url']) ? trim((string)$item['link_url']) : '';
			if($manual !== '') $url = $manual;
			break;

		case 'publicador':
			$pubId   = isset($item['link_publisher_id']) ? (string)$item['link_publisher_id'] : '';
			$orderBy = isset($item['link_order_by']) ? (string)$item['link_order_by'] : 'date_desc';
			$resolved = galleries_widget_resolver_publicacao_recente($pubId, $orderBy, $publicadorCache);
			if($resolved !== '') $url = $resolved;
			break;
	}

	if($target === '') $target = '_self';

	// req-025 / DEC-038: imagem sem link configurado não deve parecer clicável. Adiciona as
	// classes utilitárias do Tailwind que desabilitam o clique e mantêm o cursor padrão na <a>.
	if($type === 'nenhum'){
		$css = trim($css.' pointer-events-none cursor-default');
	}

	return ['url' => $url, 'target' => $target, 'css_classes' => $css];
}

/**
 * req-024 / DEC-037: resolve a URL da publicação mais recente de um publicador, segundo a
 * ordenação informada. Espelha a lógica de ordenação de menus_widget_buscar_publicacoes_publicador
 * (DEC-017/DEC-025), com `LIMIT 1`. Resultados são memoizados em `$cache` (por referência).
 *
 * @param string $publisher_id  Slug/id do publicador.
 * @param string $order_by_key  date_desc | date_asc | title_asc | title_desc.
 * @param array  $cache         Cache por referência (chave: publisher_id|order_by).
 * @return string URL canônica resolvida (ou string vazia se nada encontrado).
 */
function galleries_widget_resolver_publicacao_recente($publisher_id, $order_by_key, &$cache){
	global $_GESTOR;

	$publisher_id = (string)$publisher_id;
	if($publisher_id === '') return '';

	$order_by_key = (string)$order_by_key;
	if($order_by_key === '') $order_by_key = 'date_desc';

	$cache_key = $publisher_id.'|'.$order_by_key;
	if(array_key_exists($cache_key, $cache)) return $cache[$cache_key];

	$order_map = [
		'title_asc'  => ' ORDER BY p.nome ASC',
		'title_desc' => ' ORDER BY p.nome DESC',
		'date_asc'   => ' ORDER BY p.data_modificacao ASC',
		'date_desc'  => ' ORDER BY p.data_modificacao DESC',
	];
	$order_by = $order_map[$order_by_key] ?? $order_map['date_desc'];

	$row = banco_select(Array(
		'unico' => true,
		'tabela' => 'paginas AS p',
		'campos' => Array('p.id', 'p.caminho'),
		'extra' =>
			"WHERE p.publisher_id='".banco_escape_field($publisher_id)."'"
			." AND p.status='A'"
			." AND p.language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'"
			.$order_by
			." LIMIT 1"
	));

	$url = '';
	if(is_array($row) && isset($row['p.caminho']) && $row['p.caminho'] !== ''){
		$url = $_GESTOR['url-raiz'].$row['p.caminho'];
	}

	$cache[$cache_key] = $url;
	return $url;
}

/**
 * req-024 / DEC-037: carrega, em uma única query, as páginas referenciadas pelos links tipo
 * `pagina` (slug => URL canônica), na linguagem corrente. Espelha menus_widget_carregar_paginas.
 *
 * @param array $slugs Lista de slugs (saída de array_keys do mapa de coleta).
 * @return array Mapa slug => ['url' => ...].
 */
function galleries_widget_carregar_paginas($slugs){
	global $_GESTOR;

	if(empty($slugs)) return [];

	$ids_escaped = array_map(function($slug){
		return "'".banco_escape_field((string)$slug)."'";
	}, $slugs);
	$ids_in = implode(',', $ids_escaped);

	$rows = banco_select(Array(
		'tabela' => 'paginas AS p',
		'campos' => Array('p.id', 'p.caminho'),
		'extra' =>
			"WHERE p.status='A'"
			." AND p.language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'"
			." AND p.id IN (".$ids_in.")"
	));

	if(!is_array($rows)) $rows = [];

	$mapa = [];
	foreach($rows as $row){
		$slug = $row['p.id'] ?? '';
		if($slug === '') continue;
		$mapa[$slug] = [
			'url' => (isset($row['p.caminho']) && $row['p.caminho']) ? $_GESTOR['url-raiz'].$row['p.caminho'] : '',
		];
	}

	return $mapa;
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
	// req-024 / DEC-037: altura do container (default 300) e margem lateral (default 0).
	$height = isset($schema['height']) ? (int)$schema['height'] : 300;
	if($height < 1) $height = 300;
	$margin_lateral = isset($schema['margin_lateral']) ? (int)$schema['margin_lateral'] : 0;
	if($margin_lateral < 0) $margin_lateral = 0;

	$map = [
		'show_arrows'    => galleries_widget_bool($schema, 'show_arrows', true) ? 'true' : 'false',
		'show_dots'      => galleries_widget_bool($schema, 'show_dots', true) ? 'true' : 'false',
		'autoplay'       => galleries_widget_bool($schema, 'autoplay', false) ? 'true' : 'false',
		'autoplay_speed' => (string)(int)($schema['autoplay_speed'] ?? 3000),
		'loop'           => galleries_widget_bool($schema, 'loop', true) ? 'true' : 'false',
		'height'         => (string)$height,
		'margin_lateral' => (string)$margin_lateral,
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
 * Injeta os recursos da galeria (CSS, CSS compilado e HTML extra head) no pipeline global,
 * de forma desduplicada (req-028 / DEC-041), e retorna apenas o HTML estrutural limpo.
 */
function galleries_widget_montar_saida($html, $css, $css_compiled = '', $html_extra_head = ''){
	gestor_pagina_recursos_incluir(Array(
		'css' => $css,
		'css_compiled' => $css_compiled,
		'html_extra_head' => $html_extra_head,
	));
	return $html;
}
