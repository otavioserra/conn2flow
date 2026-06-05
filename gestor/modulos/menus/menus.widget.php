<?php
/**
 * Widget renderer do módulo menus (req-015 + req-016).
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
 * req-016: os itens curados em `fields_schema->selected_items` formam uma ÁRVORE de
 * objetos tipados (ver DEC-023). Cada nó tem `type`, `label`, `url`, `css_classes`,
 * `children` e — para o tipo `pagina` — `page_id` (slug de uma página do site).
 * O template suporta três delimitadores:
 *   - <!-- no-item < --> ... <!-- no-item > -->      (menu vazio)
 *   - <!-- item < --> ... <!-- item > -->            (folha, item sem filhos)
 *   - <!-- item-parent < --> ... <!-- item-parent > --> (item com filhos; usa [[item#children]])
 * Variáveis expostas por item: [[item#label]], [[item#url]], [[item#slug]],
 * [[item#css_classes]] e [[item#children]] (renderização recursiva dos filhos).
 */

// ===== Funções Auxiliares

function menus_get_version(){
	global $_GESTOR;

    $modulo = json_decode(file_get_contents(__DIR__ . '/menus.json'), true);

	return isset($modulo['versao']) ? $modulo['versao'] : '1.0.0';
}

// ===== Funções Principais

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

	// Adicionar JS do widget para comportamentos interativos.
	gestor_pagina_javascript_incluir(array(
		'tipo' => 'widget',
		'modulo_id' => 'menus',
		'versao' => menus_get_version(),
	));

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
 * @param array $params['html']           string template HTML com blocos item/item-parent/no-item
 * @param array $params['css']            string CSS custom (opcional)
 * @param array $params['fields_schema']  JSON string com selected_items (árvore)/template_id
 */
function menus_widget_render_inline($params){
	$html_template = (string)($params['html'] ?? '');
	$css_custom   = (string)($params['css'] ?? '');

	if(trim($html_template) === '') return '';

	$schema = $params['fields_schema'] ?? '{}';
	if(is_string($schema)) $schema = json_decode($schema, true);
	if(!is_array($schema)) $schema = [];

	$selected_items = (isset($schema['selected_items']) && is_array($schema['selected_items'])) ? $schema['selected_items'] : [];

	// Normaliza para a árvore tipada (retrocompat: lista de slugs do BATCH-015).
	$arvore = menus_widget_normalizar_itens($selected_items);

	// req-018 / DEC-025: expande os nós `publicador`, injetando as publicações do publicador
	// selecionado como filhos `pagina` (limitadas por `count` e ordenadas por `order_by`).
	$arvore = menus_widget_expandir_publicadores($arvore);

	// Modelos de célula extraídos do template (conteúdo interno de cada delimitador).
	$templates = menus_widget_extrair_blocos($html_template);

	// ===== Menu vazio: usar o bloco no-item (ou retornar vazio se não existir).

	if(empty($arvore)){
		if($templates['no_item'] === null) return '';
		$output = menus_widget_montar_base($html_template, '');
		return menus_widget_montar_saida($output, $css_custom);
	}

	// ===== Resolver páginas (tipo 'pagina') em lote -> label/url canônicos do banco.

	$page_ids = [];
	menus_widget_coletar_page_ids($arvore, $page_ids);
	$paginasMap = menus_widget_carregar_paginas($page_ids);

	// ===== Renderizar a árvore recursivamente e injetar no template.

	$itensRendered = menus_render_level($arvore, $templates, $paginasMap);

	$output = menus_widget_montar_base($html_template, $itensRendered);

	return menus_widget_montar_saida($output, $css_custom);
}

/**
 * Renderiza recursivamente um nível da árvore de itens do menu.
 *
 * - Item folha (sem filhos) usa o modelo `item`.
 * - Item com filhos usa o modelo `item-parent`, renderizando os filhos via recursão
 *   e injetando o resultado no placeholder [[item#children]].
 * - Se o template não definir `item-parent`, a árvore é achatada (DFS) sobre o modelo
 *   `item`, garantindo que nenhum item seja perdido (retrocompat com templates antigos).
 *
 * @param array $itens      Nós do nível atual (já normalizados).
 * @param array $templates  ['item' => ?string, 'item_parent' => ?string, 'no_item' => ?string].
 * @param array $paginasMap Mapa slug => ['label' => ..., 'url' => ...] das páginas resolvidas.
 * @return string HTML concatenado do nível.
 */
function menus_render_level($itens, $templates, $paginasMap){
	$out = '';

	foreach($itens as $item){
		if(!is_array($item)) continue;

		$children = (isset($item['children']) && is_array($item['children'])) ? $item['children'] : [];
		$temFilhos = !empty($children);
		$vars = menus_widget_resolver_item_vars($item, $paginasMap);

		if($temFilhos && $templates['item_parent'] !== null){
			$childrenHtml = menus_render_level($children, $templates, $paginasMap);

			$bloco = $templates['item_parent'];
			$bloco = menus_widget_injetar_children($bloco, $childrenHtml);
			$bloco = menus_widget_aplicar_vars($bloco, $vars);

			$out .= $bloco;
			continue;
		}

		if($templates['item'] !== null){
			$bloco = $templates['item'];
			$bloco = menus_widget_aplicar_vars($bloco, $vars);
			$out .= $bloco;
		}

		// Template sem `item-parent`: achatar os filhos no mesmo nível para não perdê-los.
		if($temFilhos){
			$out .= menus_render_level($children, $templates, $paginasMap);
		}
	}

	return $out;
}

/**
 * Resolve as variáveis renderizáveis de um item conforme o seu tipo.
 *
 * - `pagina`     : link canônico (url) e rótulo resolvidos do banco pelo `page_id`
 *                  (o rótulo do schema, se informado, prevalece — permite customizar).
 * - `link-custom`: usa label/url do schema.
 * - `cabecalho`  : apenas rótulo; url cai para '#' quando vazia.
 * - `link-action`: usa label/url/css_classes do schema.
 * - `separador`  : sem rótulo nem url.
 *
 * @return array ['label' => ..., 'url' => ..., 'slug' => ..., 'css_classes' => ...]
 */
function menus_widget_resolver_item_vars($item, $paginasMap){
	$type  = isset($item['type']) ? (string)$item['type'] : 'pagina';
	$label = isset($item['label']) ? (string)$item['label'] : '';
	$url   = isset($item['url']) ? (string)$item['url'] : '';
	$css   = isset($item['css_classes']) ? (string)$item['css_classes'] : '';
	$slug  = '';

	switch($type){
		case 'pagina':
			$slug = isset($item['page_id']) ? (string)$item['page_id'] : '';
			if($slug !== '' && isset($paginasMap[$slug])){
				$url = $paginasMap[$slug]['url'];                 // link canônico sempre atualizado
				if($label === '') $label = $paginasMap[$slug]['label'];
			}
			break;

		case 'separador':
			$label = '';
			$url = '';
			break;

		case 'cabecalho':
		case 'publicador':
			// O publicador agrupa as publicações injetadas (req-018): comporta-se como um
			// cabeçalho com filhos. Mantém o rótulo do schema e cai para '#' quando sem URL.
			$url = ($url !== '') ? $url : '#';
			break;

		// 'link-custom' e 'link-action' usam label/url/css_classes do schema diretamente.
	}

	return [
		'label'       => $label,
		'url'         => $url,
		'slug'        => $slug,
		'css_classes' => $css,
	];
}

/**
 * Substitui as variáveis [[item#X]] de um bloco pelos valores resolvidos.
 * Tolera o cerco de arrobas do banco (@[[item#X]]@): consome as arrobas adjacentes,
 * evitando que sobre @valor@ no HTML final.
 */
function menus_widget_aplicar_vars($bloco, $vars){
	return preg_replace_callback('/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($vars){
		$name = $m[1];
		if($name === 'children') return ''; // já tratado em menus_widget_injetar_children
		return isset($vars[$name]) ? (string)$vars[$name] : '';
	}, $bloco);
}

/**
 * Injeta o HTML renderizado dos filhos no placeholder [[item#children]] (com ou sem arrobas).
 * Usa str_replace (não preg_replace) para não interpretar $1/\1 que possam existir no HTML.
 */
function menus_widget_injetar_children($bloco, $childrenHtml){
	return str_replace(['@[[item#children]]@', '[[item#children]]'], $childrenHtml, $bloco);
}

/**
 * Extrai o conteúdo interno dos três delimitadores de célula do template.
 * Retorna null para o bloco ausente.
 */
function menus_widget_extrair_blocos($html_template){
	$blocos = ['item' => null, 'item_parent' => null, 'no_item' => null];

	if(preg_match('/<!--\s*item-parent\s*<\s*-->([\s\S]*?)<!--\s*item-parent\s*>\s*-->/i', $html_template, $m)){
		$blocos['item_parent'] = $m[1];
	}
	if(preg_match('/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i', $html_template, $m)){
		$blocos['item'] = $m[1];
	}
	if(preg_match('/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i', $html_template, $m)){
		$blocos['no_item'] = $m[1];
	}

	return $blocos;
}

/**
 * Monta o HTML base substituindo a região dos modelos de célula pelo conteúdo renderizado.
 *
 * - Com itens: a primeira âncora (`item`, ou `item-parent` se não houver `item`) recebe o
 *   HTML renderizado; os demais blocos-modelo e o `no-item` são removidos.
 * - Sem itens: os modelos `item`/`item-parent` são removidos e o conteúdo do `no-item` é exposto.
 */
function menus_widget_montar_base($html_template, $itensRendered){
	$padraoItem       = '/<!--\s*item\s*<\s*-->[\s\S]*?<!--\s*item\s*>\s*-->/i';
	$padraoItemParent = '/<!--\s*item-parent\s*<\s*-->[\s\S]*?<!--\s*item-parent\s*>\s*-->/i';
	$padraoNoItem     = '/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i';

	$out = $html_template;

	if($itensRendered === ''){
		$out = preg_replace($padraoItemParent, '', $out, 1);
		$out = preg_replace($padraoItem, '', $out, 1);
		if(preg_match($padraoNoItem, $out, $m)){
			$out = menus_widget_preg_replace_literal($padraoNoItem, $m[1], $out, 1);
		}
		return $out;
	}

	if(preg_match($padraoItem, $out)){
		$out = menus_widget_preg_replace_literal($padraoItem, $itensRendered, $out, 1);
		$out = preg_replace($padraoItemParent, '', $out, 1);
	} else {
		$out = menus_widget_preg_replace_literal($padraoItemParent, $itensRendered, $out, 1);
	}

	$out = preg_replace($padraoNoItem, '', $out, 1);

	return $out;
}

/**
 * preg_replace com replacement literal (callback): evita a interpretação de
 * backreferences ($1, \1) caso o HTML renderizado contenha esses caracteres.
 */
function menus_widget_preg_replace_literal($pattern, $replacement, $subject, $limit = -1){
	return preg_replace_callback($pattern, function() use ($replacement){
		return $replacement;
	}, $subject, $limit);
}

/**
 * Normaliza `selected_items` para a árvore tipada.
 * Retrocompatibilidade: uma entrada string (formato BATCH-015) vira um nó `pagina` raiz.
 */
function menus_widget_normalizar_itens($selected_items){
	$out = [];

	foreach($selected_items as $item){
		if(is_string($item)){
			$out[] = [
				'type'     => 'pagina',
				'page_id'  => $item,
				'label'    => '',
				'url'      => '',
				'children' => [],
			];
			continue;
		}

		if(!is_array($item)) continue;

		$node = [
			'type'        => isset($item['type']) ? $item['type'] : 'pagina',
			'label'       => isset($item['label']) ? $item['label'] : '',
			'url'         => isset($item['url']) ? $item['url'] : '',
			'css_classes' => isset($item['css_classes']) ? $item['css_classes'] : '',
		];
		if(isset($item['page_id'])) $node['page_id'] = $item['page_id'];

		// req-018: preservar a parametrização do tipo `publicador` para a expansão runtime.
		if(($node['type'] === 'publicador')){
			$node['publisher_id'] = isset($item['publisher_id']) ? $item['publisher_id'] : '';
			$node['count']        = isset($item['count']) ? $item['count'] : 5;
			$node['order_by']     = isset($item['order_by']) ? $item['order_by'] : 'date_desc';
		}

		$children = (isset($item['children']) && is_array($item['children'])) ? $item['children'] : [];
		$node['children'] = menus_widget_normalizar_itens($children);

		$out[] = $node;
	}

	return $out;
}

/**
 * Expande (em profundidade) os nós do tipo `publicador`: para cada um, busca as publicações
 * ativas do publicador selecionado (no idioma corrente), limitadas por `count` e ordenadas
 * por `order_by`, e as injeta como filhos `type = 'pagina'` (req-018 / DEC-025).
 *
 * Os filhos já vêm com label/url canônicos resolvidos diretamente da query. A expansão é
 * recursiva para suportar um publicador aninhado sob um cabeçalho. As publicações dinâmicas
 * substituem quaisquer filhos manuais do nó (que a UI já impede de criar).
 */
function menus_widget_expandir_publicadores($itens){
	$out = [];

	foreach($itens as $item){
		if(!is_array($item)){ $out[] = $item; continue; }

		$type = isset($item['type']) ? $item['type'] : 'pagina';

		if(!empty($item['children']) && is_array($item['children'])){
			$item['children'] = menus_widget_expandir_publicadores($item['children']);
		}

		if($type === 'publicador'){
			$item['children'] = menus_widget_buscar_publicacoes_publicador($item);
		}

		$out[] = $item;
	}

	return $out;
}

/**
 * Busca as publicações de um nó `publicador` e as devolve como nós `pagina` prontos para
 * renderização (label/url já resolvidos). Espelha a lógica de ordenação do
 * `publisher-highlights` (DEC-017): `order_by` controla a ordenação; `count` o limite.
 *
 * @param array $item Nó publicador com `publisher_id`, `count` e `order_by`.
 * @return array Lista de nós filhos `type = 'pagina'`.
 */
function menus_widget_buscar_publicacoes_publicador($item){
	global $_GESTOR;

	$publisher_id = isset($item['publisher_id']) ? (string)$item['publisher_id'] : '';
	if($publisher_id === '') return [];

	$count = (int)($item['count'] ?? 5);
	if($count < 1) $count = 5;

	$order_by_key = isset($item['order_by']) ? (string)$item['order_by'] : 'date_desc';
	$order_map = [
		'title_asc'  => ' ORDER BY p.nome ASC',
		'title_desc' => ' ORDER BY p.nome DESC',
		'date_asc'   => ' ORDER BY p.data_modificacao ASC',
		'date_desc'  => ' ORDER BY p.data_modificacao DESC',
	];
	$order_by = $order_map[$order_by_key] ?? $order_map['date_desc'];

	$rows = banco_select(Array(
		'tabela' => 'paginas AS p',
		'campos' => Array(
			'p.id',
			'p.nome',
			'p.caminho',
		),
		'extra' =>
			"WHERE p.publisher_id='".banco_escape_field($publisher_id)."'"
			." AND p.status='A'"
			." AND p.language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'"
			.$order_by
			." LIMIT ".$count
	));

	if(!is_array($rows)) $rows = [];

	$filhos = [];
	foreach($rows as $row){
		$slug = $row['p.id'] ?? '';
		if($slug === '') continue;
		$filhos[] = [
			'type'        => 'pagina',
			'page_id'     => $slug,
			'label'       => $row['p.nome'] ?? '',
			'url'         => (isset($row['p.caminho']) && $row['p.caminho']) ? $_GESTOR['url-raiz'].$row['p.caminho'] : '',
			'css_classes' => '',
			'children'    => [],
		];
	}

	return $filhos;
}

/**
 * Coleta (DFS) os `page_id` de todos os nós tipo `pagina` da árvore, deduplicados
 * como chaves do array `$ids` passado por referência.
 */
function menus_widget_coletar_page_ids($itens, &$ids){
	foreach($itens as $item){
		if(!is_array($item)) continue;

		$type = isset($item['type']) ? $item['type'] : 'pagina';
		if($type === 'pagina' && !empty($item['page_id'])){
			$ids[(string)$item['page_id']] = true;
		}

		if(!empty($item['children']) && is_array($item['children'])){
			menus_widget_coletar_page_ids($item['children'], $ids);
		}
	}
}

/**
 * Carrega, em uma única query, as páginas referenciadas (slug => label/url canônicos),
 * na linguagem corrente.
 *
 * @param array $ids Mapa slug => true (saída de menus_widget_coletar_page_ids).
 */
function menus_widget_carregar_paginas($ids){
	global $_GESTOR;

	$slugs = array_keys($ids);
	if(empty($slugs)) return [];

	$ids_escaped = array_map(function($slug){
		return "'".banco_escape_field((string)$slug)."'";
	}, $slugs);
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
			." AND p.language='".banco_escape_field($_GESTOR['linguagem-codigo'])."'"
			." AND p.id IN (".$ids_in.")"
	));

	if(!is_array($rows)) $rows = [];

	$mapa = [];
	foreach($rows as $row){
		$slug = $row['p.id'] ?? '';
		if($slug === '') continue;
		$mapa[$slug] = [
			'label' => $row['p.nome'] ?? '',
			'url'   => $row['p.caminho'] ? $_GESTOR['url-raiz'].$row['p.caminho'] : '',
		];
	}

	return $mapa;
}

/**
 * Anexa o CSS customizado do menu como tag <style> antes do HTML renderizado.
 */
function menus_widget_montar_saida($html, $css){
	if(trim((string)$css) === '') return $html;
	return '<style>'.$css.'</style>'.$html;
}
