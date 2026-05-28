<?php
/**
 * Widget renderer do módulo publisher-highlights (BATCH-009).
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando
 * a página contiver um wrapper:
 *   <!-- widgets#publisher-highlights->render({"grupo_slug": "..."}) < -->
 *     ...mockup estático (preview p/ designer)...
 *   <!-- widgets#publisher-highlights->render({"grupo_slug": "..."}) > -->
 *
 * Conforme D-023 (sem fallback para arquivo físico): o template real (`html` e
 * `css`) vem EXCLUSIVAMENTE do banco. Se o registro não existir ou o template
 * estiver vazio, retornamos string vazia — o mockup do arquivo NÃO é exibido
 * em produção.
 */

function publisher_highlights_render($params){
	global $_GESTOR;

	if(!is_array($params)) return '';

	$grupo_slug = $params['grupo_slug'] ?? null;
	if(empty($grupo_slug)) return '';

	// ===== 1) Buscar o registro do bloco de destaques (publisher_highlights)
	//        pelo slug textual, na linguagem corrente.

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'publisher_highlights',
		'campos' => Array(
			'id_publisher_highlights',
			'id',
			'publisher_id',
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

	// D-023: sem fallback para o mockup do arquivo físico. Se o template do banco
	// estiver vazio, retornamos vazio — o bloco precisa ser configurado no painel.
	if(trim($html_template) === '') return '';

	return publisher_highlights_widget_render_inline([
		'html' => $html_template,
		'css' => $css_custom,
		'publisher_id' => $registro['publisher_id'] ?? '',
		'fields_schema' => $registro['fields_schema'] ?? '{}',
	]);
}

/**
 * Renderiza o widget a partir de inputs crus (sem ler da tabela publisher_highlights).
 * Usado pelo widget normal (após DB lookup) e pelo endpoint AJAX widget-preview do
 * painel de edição (req-007 item 4).
 *
 * @param array $params['html']           string template HTML com blocos item/no-item
 * @param array $params['css']            string CSS custom (opcional)
 * @param array $params['publisher_id']   slug do publisher
 * @param array $params['fields_schema']  JSON string com rule/count/order_by/selected_items/variable_mapping
 */
function publisher_highlights_widget_render_inline($params){
	$html_template = (string)($params['html'] ?? '');
	$css_custom   = (string)($params['css'] ?? '');
	$publisher_id = (string)($params['publisher_id'] ?? '');

	if(trim($html_template) === '') return '';

	$schema = $params['fields_schema'] ?? '{}';
	if(is_string($schema)) $schema = json_decode($schema, true);
	if(!is_array($schema)) $schema = [];

	$rule             = $schema['rule']             ?? 'latest';
	$count            = (int)($schema['count']      ?? 4);
	$order_by         = $schema['order_by']         ?? 'date_desc';
	$selected_items   = $schema['selected_items']   ?? [];
	$variable_mapping = $schema['variable_mapping'] ?? [];

	if($count < 1) $count = 1;

	$publicacoes = [];
	if(!empty($publisher_id)){
		$publicacoes = publisher_highlights_widget_buscar_publicacoes([
			'publisher_id'   => $publisher_id,
			'rule'           => $rule,
			'count'          => $count,
			'order_by'       => $order_by,
			'selected_items' => $selected_items,
		]);
	}

	$padraoItem   = '/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i';
	$padraoNoItem = '/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i';

	$temItemLoop   = preg_match($padraoItem, $html_template, $itemMatch);
	$temNoItemBloco = preg_match($padraoNoItem, $html_template, $noItemMatch);

	if(empty($publicacoes)){
		if(!$temNoItemBloco) return '';

		$output = $temItemLoop ? preg_replace($padraoItem, '', $html_template, 1) : $html_template;
		$output = preg_replace($padraoNoItem, $noItemMatch[1], $output, 1);

		return publisher_highlights_widget_montar_saida($output, $css_custom);
	}

	$html_template = $temNoItemBloco ? preg_replace($padraoNoItem, '', $html_template, 1) : $html_template;

	if(!$temItemLoop){
		return publisher_highlights_widget_montar_saida($html_template, $css_custom);
	}

	$itemTemplate = $itemMatch[1];
	$itemsRendered = '';

	foreach($publicacoes as $pub){
		$itemRendered = preg_replace_callback('/@\[\[item#([a-zA-Z0-9_\-]+)\]\]@/', function($m) use ($pub, $variable_mapping){
			$varName = $m[1];
			$publisherField = isset($variable_mapping[$varName]) ? $variable_mapping[$varName] : $varName;
			return isset($pub[$publisherField]) ? (string)$pub[$publisherField] : '';
		}, $itemTemplate);

		$itemsRendered .= $itemRendered;
	}

	$output = preg_replace($padraoItem, $itemsRendered, $html_template, 1);

	return publisher_highlights_widget_montar_saida($output, $css_custom);
}

/**
 * Anexa o CSS customizado do bloco como tag <style> antes do HTML renderizado.
 */
function publisher_highlights_widget_montar_saida($html, $css){
	if(trim((string)$css) === '') return $html;
	return '<style>'.$css.'</style>'.$html;
}

/**
 * Busca as publicações vinculadas ao publisher_id (slug textual) aplicando
 * a regra de curadoria.
 *
 * - rule = "latest": últimas N publicações ativas, ordenadas conforme `order_by`
 *                    (`date_desc` padrão, `date_asc`, `title_asc`, `title_desc`). Ver DEC-017.
 * - rule = "manual": filtra por `paginas.id IN (selected_items)` preservando
 *                    a ordem informada em selected_items.
 *
 * Cada item retornado inclui campos padrão (titulo, url, data, page_id) +
 * todos os campos custom presentes em `publisher_pages.fields_values`.
 */
function publisher_highlights_widget_buscar_publicacoes($params){
	global $_GESTOR;

	$publisher_id   = $params['publisher_id'];
	$rule           = $params['rule'] ?? 'latest';
	$count          = (int)($params['count'] ?? 4);
	$order_by_key   = $params['order_by'] ?? 'date_desc';
	$selected_items = isset($params['selected_items']) && is_array($params['selected_items']) ? $params['selected_items'] : [];

	$language = $_GESTOR['linguagem-codigo'];

	// Filtro base: paginas pertencentes ao publisher, ativas, no idioma corrente.
	$where_paginas =
		"WHERE p.publisher_id='".banco_escape_field($publisher_id)."'"
		." AND p.status='A'"
		." AND p.language='".banco_escape_field($language)."'";

	if($rule === 'manual'){
		if(empty($selected_items)) return [];

		$selected_escaped = array_map(function($slug){
			return "'".banco_escape_field((string)$slug)."'";
		}, $selected_items);
		$selected_in = implode(',', $selected_escaped);

		$where_paginas .= " AND p.id IN (".$selected_in.")";

		$order_by = ""; // ordenação será aplicada manualmente abaixo
		$limit = "";
	} else {
		// rule = "latest" — `order_by` controla a ordenação (DEC-017)
		$order_map = [
			'title_asc'  => ' ORDER BY p.nome ASC',
			'title_desc' => ' ORDER BY p.nome DESC',
			'date_asc'   => ' ORDER BY p.data_modificacao ASC',
			'date_desc'  => ' ORDER BY p.data_modificacao DESC',
		];
		$order_by = $order_map[$order_by_key] ?? $order_map['date_desc'];
		$limit = " LIMIT ".$count;
	}

	$rows = banco_select(Array(
		'tabela' => 'paginas AS p LEFT JOIN publisher_pages AS pp ON pp.page_id = p.id AND pp.language = p.language',
		'campos' => Array(
			'p.id',
			'p.nome',
			'p.caminho',
			'p.data_modificacao',
			'pp.fields_values',
		),
		'extra' => $where_paginas.$order_by.$limit
	));

	if(!is_array($rows)) $rows = [];

	// Incluir biblioteca de formatação para eventual formatação de campos (ex: data) no futuro.
	gestor_incluir_biblioteca('formato');

	$itens = [];
	foreach($rows as $row){
		// req-010 item 2: `publisher_pages.fields_values` é gravado como array de objetos
		// `[{"id": "campo", "value": "valor"}, ...]` (vide publisher-pages). Para que o
		// array_merge consiga sobrescrever campos pelo nome (ex: subtitulo, conteudo),
		// normalizar para dicionário associativo antes de mesclar.
		$campos_originais = json_decode($row['pp.fields_values'] ?? '[]', true);
		$campos_publisher = [];
		if(is_array($campos_originais)){
			foreach($campos_originais as $item_field){
				if(is_array($item_field) && isset($item_field['id'])){
					$campos_publisher[$item_field['id']] = $item_field['value'] ?? '';
				}
			}
		}

		// Campos padrões + custom. Os customs sobrescrevem somente se a chave coincidir.
		$itens[$row['p.id']] = array_merge([
			'page_id' => $row['p.id'],
			'titulo'  => $row['p.nome'] ?? '',
			'url'     => $row['p.caminho'] ?? '',
			'data'    => $row['p.data_modificacao'] ? formato_data_hora_from_datetime_to_text($row['p.data_modificacao']) : '',
		], $campos_publisher);
	}

	// Para regra manual, reordenar conforme a ordem informada em selected_items.
	if($rule === 'manual'){
		$ordenados = [];
		foreach($selected_items as $slug){
			if(isset($itens[$slug])) $ordenados[] = $itens[$slug];
			if(count($ordenados) >= $count) break;
		}
		return $ordenados;
	}

	return array_values($itens);
}
