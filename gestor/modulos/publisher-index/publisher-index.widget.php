<?php
/**
 * Widget renderer do módulo publisher-index (Publicador Índice, req-028 / DEC-041).
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando a página
 * contiver um wrapper:
 *   <!-- widgets#publisher-index->render({"grupo_slug": "..."}) < -->
 *     ...mockup estático (preview p/ designer)...
 *   <!-- widgets#publisher-index->render({"grupo_slug": "..."}) > -->
 *
 * Diferente do publisher-highlights, o índice é paginado e interativo: a primeira página
 * é renderizada no servidor (page load) e as páginas seguintes / busca / ordenação são
 * resolvidas via AJAX por publisher_index_render_ajax() (acionada por gestor_pagina_widgets_ajax()
 * quando a requisição traz ajax + ajaxWidgets). O template real (html/css) vem EXCLUSIVAMENTE
 * do banco (D-023); se vazio, retornamos string vazia.
 *
 * Blocos suportados no template HTML:
 *   <!-- item < --> ... <!-- item > -->            bloco repetido por publicação ([[item#campo]])
 *   <!-- no-item < --> ... <!-- no-item > -->       exibido quando não há publicações
 *   <!-- search-input < --> ... <!-- search-input > -->  contêiner da busca (condicional show_search_input)
 *   <!-- sort-select < --> ... <!-- sort-select > -->    contêiner da ordenação (condicional show_sorting_select)
 *   <!-- load-more < --> ... <!-- load-more > -->        botão "carregar mais" (condicional show_load_more_btn && tem_mais)
 *   <!-- metrics < --> ... <!-- metrics > -->            métricas de paginação (condicional show_metrics)
 *
 * Variáveis globais resolvidas no contêiner (formato [[var]] ou @[[var]]@):
 *   [[grupo_slug]] [[publisher_id]] [[items_per_page]] [[ordenacao]]
 *   [[show_search_input]] [[show_sorting_select]] [[show_load_more_btn]] [[show_metrics]]
 *   [[page_count]] [[page_total]]   (req-041 §1.4: métricas "Exibindo X de Y")
 */

function publisher_index_get_version(){
	global $_GESTOR;

	$modulo = json_decode(file_get_contents(__DIR__ . '/publisher-index.json'), true);

	return isset($modulo['versao']) ? $modulo['versao'] : '1.0.0';
}

/**
 * Normaliza um valor de schema (bool/int/string) para booleano. Defaults preservam
 * retrocompatibilidade com registros sem a chave.
 */
function publisher_index_widget_bool($schema, $key, $default){
	if(!is_array($schema) || !array_key_exists($key, $schema)) return $default;
	$v = $schema[$key];
	if(is_bool($v)) return $v;
	if(is_int($v)) return $v !== 0;
	$v = strtolower(trim((string)$v));
	return ($v === 'true' || $v === '1' || $v === 'yes' || $v === 'on');
}

/**
 * Page load: busca o registro do índice pelo slug, injeta recursos e renderiza a
 * primeira página de publicações (com os controles de busca/ordenação/load-more).
 */
function publisher_index_render($params){
	global $_GESTOR;

	if(!is_array($params)) return '';

	$grupo_slug = $params['grupo_slug'] ?? null;
	if(empty($grupo_slug)) return '';

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'publisher_index',
		'campos' => Array(
			'id_publisher_index',
			'id',
			'publisher_id',
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

	// D-023: sem fallback para o mockup do arquivo físico.
	if(trim($html_template) === '') return '';

	// Script público do widget (busca/ordenação/carregar mais).
	gestor_pagina_javascript_incluir(Array(
		'tipo' => 'widget',
		'modulo_id' => 'publisher-index',
		'versao' => publisher_index_get_version(),
	));

	return publisher_index_widget_render_inline([
		'html' => $html_template,
		'css' => $registro['css'] ?? '',
		'css_compiled' => $registro['css_compiled'] ?? '',
		'html_extra_head' => $registro['html_extra_head'] ?? '',
		'publisher_id' => $registro['publisher_id'] ?? '',
		'fields_schema' => $registro['fields_schema'] ?? '{}',
		'grupo_slug' => $grupo_slug,
	]);
}

/**
 * Renderiza o índice a partir de inputs crus (sem ler da tabela). Usado pelo widget
 * normal (após DB lookup) e pelo endpoint AJAX widget-preview do painel de edição.
 */
function publisher_index_widget_render_inline($params){
	$html_template   = (string)($params['html'] ?? '');
	$css_custom      = (string)($params['css'] ?? '');
	$css_compiled    = (string)($params['css_compiled'] ?? '');
	$html_extra_head = (string)($params['html_extra_head'] ?? '');
	$publisher_id    = (string)($params['publisher_id'] ?? '');
	$grupo_slug      = (string)($params['grupo_slug'] ?? '');

	if(trim($html_template) === '') return '';

	$schema = $params['fields_schema'] ?? '{}';
	if(is_string($schema)) $schema = json_decode($schema, true);
	if(!is_array($schema)) $schema = [];

	$items_per_page   = (int)($schema['items_per_page'] ?? 10);
	if($items_per_page < 1) $items_per_page = 10;
	$order_by         = $schema['order_by'] ?? 'date_desc';
	$variable_mapping = $schema['variable_mapping'] ?? [];
	$show_search      = publisher_index_widget_bool($schema, 'show_search_input', true);
	$show_sort        = publisher_index_widget_bool($schema, 'show_sorting_select', true);
	$show_load_more   = publisher_index_widget_bool($schema, 'show_load_more_btn', true);
	$show_metrics     = publisher_index_widget_bool($schema, 'show_metrics', true);

	// Primeira página + 1 item extra para detectar se há próxima página.
	$publicacoes = [];
	$tem_mais = false;
	$total = 0;
	if(!empty($publisher_id)){
		$publicacoes = publisher_index_widget_buscar_publicacoes([
			'publisher_id' => $publisher_id,
			'busca'        => '',
			'offset'       => 0,
			'limit'        => $items_per_page + 1,
			'order_by'     => $order_by,
		]);
		if(count($publicacoes) > $items_per_page){
			$tem_mais = true;
			array_pop($publicacoes);
		}
		// req-041 §1.4: total de publicações casadas (sem paginação) para as métricas.
		$total = publisher_index_widget_contar_publicacoes([
			'publisher_id' => $publisher_id,
			'busca'        => '',
		]);
	}
	// req-041 §1.4: itens efetivamente exibidos nesta primeira página (= min(items_per_page, total)).
	$page_count = count($publicacoes);

	$padraoItem   = '/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i';
	$padraoNoItem = '/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i';

	$temItem   = preg_match($padraoItem, $html_template, $itemMatch);
	$temNoItem = preg_match($padraoNoItem, $html_template, $noItemMatch);

	$output = $html_template;

	if(empty($publicacoes)){
		// Sem publicações: remove o bloco item e mantém o no-item.
		if($temItem)   $output = preg_replace($padraoItem, '', $output, 1);
		if($temNoItem) $output = publisher_index_widget_substituir_bloco($output, $padraoNoItem, $noItemMatch[1]);
	} else {
		$itensRendered = $temItem ? publisher_index_widget_render_itens($itemMatch[1], $publicacoes, $variable_mapping) : '';
		if($temItem)   $output = publisher_index_widget_substituir_bloco($output, $padraoItem, $itensRendered);
		if($temNoItem) $output = preg_replace($padraoNoItem, '', $output, 1);
	}

	// Blocos condicionais de controle.
	$output = publisher_index_widget_bloco_condicional($output, 'search-input', $show_search);
	$output = publisher_index_widget_bloco_condicional($output, 'sort-select', $show_sort);
	$output = publisher_index_widget_bloco_condicional($output, 'metrics', $show_metrics);
	$output = publisher_index_widget_bloco_condicional($output, 'load-more', $show_load_more && $tem_mais);

	// Variáveis globais (data-attributes do contêiner).
	$output = publisher_index_widget_resolver_globais($output, Array(
		'grupo_slug'          => $grupo_slug,
		'publisher_id'        => $publisher_id,
		'items_per_page'      => (string)$items_per_page,
		'ordenacao'           => $order_by,
		'show_search_input'   => $show_search ? 'true' : 'false',
		'show_sorting_select' => $show_sort ? 'true' : 'false',
		'show_load_more_btn'  => $show_load_more ? 'true' : 'false',
		'show_metrics'        => $show_metrics ? 'true' : 'false',
		// req-041 §1.4: métricas de paginação ("Exibindo [[page_count]] de [[page_total]]").
		'page_count'          => (string)$page_count,
		'page_total'          => (string)$total,
	));

	return publisher_index_widget_montar_saida($output, $css_custom, $css_compiled, $html_extra_head);
}

/**
 * Roteamento AJAX público (acionado por gestor_pagina_widgets_ajax via ajaxWidgets).
 * Consulta a página solicitada aplicando busca textual e ordenação, e devolve apenas
 * o HTML dos itens em $_GESTOR['ajax-json']. Retorna string vazia: qualquer retorno
 * não-vazio é tratado como erro 500 por gestor_pagina_widgets_ajax().
 */
function publisher_index_render_ajax($params){
	global $_GESTOR;

	if(!is_array($params)) $params = [];

	// O slug do grupo chega via JSON do wrapper (params) e, por robustez, também aceita
	// o parâmetro explícito ajaxRegistroId enviado pelo script público.
	$grupo_slug = $params['grupo_slug'] ?? ($_REQUEST['ajaxRegistroId'] ?? '');

	$req       = isset($_REQUEST['params']) && is_array($_REQUEST['params']) ? $_REQUEST['params'] : [];
	$busca     = isset($req['busca']) ? (string)$req['busca'] : '';
	$ordenacao = isset($req['ordenacao']) ? (string)$req['ordenacao'] : '';
	$pagina    = (int)($req['pagina'] ?? 1);
	if($pagina < 1) $pagina = 1;

	if(empty($grupo_slug)) return '';

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'publisher_index',
		'campos' => Array(
			'id',
			'publisher_id',
			'fields_schema',
			'html',
		),
		'extra' =>
			"WHERE id='".banco_escape_field($grupo_slug)."'"
			." AND status='A'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
	));

	if(!$registro){
		$_GESTOR['ajax-json'] = Array('status' => 'Ok', 'html' => '', 'tem_mais' => false, 'total' => 0);
		return '';
	}

	$schema = json_decode($registro['fields_schema'] ?? '{}', true);
	if(!is_array($schema)) $schema = [];

	$items_per_page = (int)($schema['items_per_page'] ?? 10);
	if($items_per_page < 1) $items_per_page = 10;

	$order_by = $ordenacao !== '' ? $ordenacao : ($schema['order_by'] ?? 'date_desc');
	if(!in_array($order_by, ['date_desc','date_asc','title_asc','title_desc'], true)) $order_by = 'date_desc';

	$variable_mapping = $schema['variable_mapping'] ?? [];
	$publisher_id     = $registro['publisher_id'] ?? '';
	$offset           = ($pagina - 1) * $items_per_page;

	$publicacoes = [];
	$tem_mais = false;
	$total = 0;
	if(!empty($publisher_id)){
		$publicacoes = publisher_index_widget_buscar_publicacoes([
			'publisher_id' => $publisher_id,
			'busca'        => $busca,
			'offset'       => $offset,
			'limit'        => $items_per_page + 1,
			'order_by'     => $order_by,
		]);
		if(count($publicacoes) > $items_per_page){
			$tem_mais = true;
			array_pop($publicacoes);
		}
		// req-041 §1.4: total de publicações casadas com a busca atual (para "Exibindo X de Y").
		$total = publisher_index_widget_contar_publicacoes([
			'publisher_id' => $publisher_id,
			'busca'        => $busca,
		]);
	}

	$itemTemplate = publisher_index_widget_extrair_item_template($registro['html'] ?? '');
	$html_itens   = publisher_index_widget_render_itens($itemTemplate, $publicacoes, $variable_mapping);

	// Primeira página sem resultados (ex.: busca sem correspondências): devolve o bloco
	// no-item para que o script público apenas substitua o conteúdo da lista.
	if($html_itens === '' && $pagina === 1){
		if(preg_match('/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i', (string)($registro['html'] ?? ''), $nm)){
			$html_itens = $nm[1];
		}
	}

	$_GESTOR['ajax-json'] = Array(
		'status'   => 'Ok',
		'html'     => $html_itens,
		'tem_mais' => $tem_mais,
		'total'    => $total,
	);

	return '';
}

/**
 * Extrai o conteúdo interno do bloco `item` do template (usado no AJAX para repetir
 * apenas os itens, sem o restante do contêiner).
 */
function publisher_index_widget_extrair_item_template($html){
	if(preg_match('/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i', (string)$html, $m)){
		return $m[1];
	}
	return '';
}

/**
 * Repete o template de item para cada publicação, substituindo as variáveis
 * [[item#X]] (tolerante ao cerco de arrobas @...@ do banco — ver BATCH-016).
 */
function publisher_index_widget_render_itens($itemTemplate, $publicacoes, $variable_mapping){
	if(trim((string)$itemTemplate) === '') return '';

	$out = '';
	foreach($publicacoes as $pub){
		$out .= preg_replace_callback('/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($pub, $variable_mapping){
			$varName = $m[1];
			$field = isset($variable_mapping[$varName]) ? $variable_mapping[$varName] : $varName;
			return isset($pub[$field]) ? (string)$pub[$field] : '';
		}, $itemTemplate);
	}
	return $out;
}

/**
 * Substitui um bloco delimitado por seu conteúdo renderizado sem que `$`/`\` do
 * conteúdo sejam interpretados como backreferences pelo preg_replace.
 */
function publisher_index_widget_substituir_bloco($html, $padrao, $conteudo){
	return preg_replace_callback($padrao, function() use ($conteudo){ return $conteudo; }, $html, 1);
}

/**
 * Mantém (removendo apenas os marcadores) ou remove integralmente um bloco condicional.
 */
function publisher_index_widget_bloco_condicional($html, $nome, $manter){
	$padrao = '/<!--\s*'.preg_quote($nome, '/').'\s*<\s*-->([\s\S]*?)<!--\s*'.preg_quote($nome, '/').'\s*>\s*-->/i';
	if($manter){
		return preg_replace_callback($padrao, function($m){ return $m[1]; }, $html);
	}
	return preg_replace($padrao, '', $html);
}

/**
 * Resolve as variáveis globais [[chave]] (ou @[[chave]]@) pelos valores informados.
 */
function publisher_index_widget_resolver_globais($html, $valores){
	foreach($valores as $chave => $valor){
		$padrao = '/@?\[\['.preg_quote($chave, '/').'\]\]@?/';
		$html = preg_replace_callback($padrao, function() use ($valor){ return (string)$valor; }, $html);
	}
	return $html;
}

/**
 * Injeta os recursos do índice (CSS, CSS compilado e HTML extra head) no pipeline
 * global, de forma desduplicada (req-028 / DEC-041), e retorna o HTML estrutural limpo.
 */
function publisher_index_widget_montar_saida($html, $css, $css_compiled = '', $html_extra_head = ''){
	gestor_pagina_recursos_incluir(Array(
		'css' => $css,
		'css_compiled' => $css_compiled,
		'html_extra_head' => $html_extra_head,
	));
	return $html;
}

/**
 * req-041 §1.1: converte os caracteres não-ASCII (acentos) de um termo de busca para a sua
 * forma de escape Unicode literal (`u00xx`) — com ou sem a barra invertida (`\u00xx`).
 * Permite casar registros cujo nome foi gravado com Unicode corrompido no banco
 * (ex.: "Título" salvo como "Tu00edtulo" ou "Título").
 */
function publisher_index_widget_unicode_escape($termo, $com_barra = false){
	$termo = (string)$termo;
	if($termo === '') return '';

	$out = '';
	$len = mb_strlen($termo, 'UTF-8');
	for($i = 0; $i < $len; $i++){
		$char = mb_substr($termo, $i, 1, 'UTF-8');
		$code = function_exists('mb_ord') ? mb_ord($char, 'UTF-8') : ord($char);
		if($code === false || $code < 128){
			$out .= $char;
		} else {
			$out .= ($com_barra ? '\\u' : 'u').sprintf('%04x', $code);
		}
	}
	return $out;
}

/**
 * req-041 §1.2: decodifica padrões de escape Unicode literais (`u00xx` ou `\u00xx`) de volta
 * para o caractere UTF-8 nativo. Corrige nomes/campos gravados de forma corrompida no banco.
 */
function publisher_index_widget_corrigir_unicode($str){
	if(!is_string($str) || $str === '') return $str;

	return preg_replace_callback('/\\\\?u([0-9a-fA-F]{4})/i', function($m){
		return mb_convert_encoding(pack('N', hexdec($m[1])), 'UTF-8', 'UCS-4BE');
	}, $str);
}

/**
 * req-041 §1.1: monta a cláusula SQL de busca textual disjuntiva (termo literal + variantes
 * de escape Unicode com e sem barra invertida) ou string vazia quando não há termo.
 */
function publisher_index_widget_clausula_busca($busca){
	$busca = trim((string)$busca);
	if($busca === '') return '';

	$literal = banco_escape_field($busca);
	$uni     = banco_escape_field(publisher_index_widget_unicode_escape($busca, false));
	// Em LIKE do MySQL, "\" também escapa o próximo caractere do padrão. Para buscar
	// uma barra literal gravada no banco, o padrão precisa receber "\\" antes do escape SQL.
	$uniBar  = banco_escape_field(str_replace('\\', '\\\\', publisher_index_widget_unicode_escape($busca, true)));

	return " AND (p.nome LIKE '%".$literal."%'"
		." OR p.nome LIKE '%".$uni."%'"
		." OR p.nome LIKE '%".$uniBar."%')";
}

/**
 * req-041 §1.4: conta o total de publicações que casam com o publicador + busca (sem LIMIT),
 * usando o mesmo INNER JOIN restritivo da listagem (§1.3).
 */
function publisher_index_widget_contar_publicacoes($params){
	global $_GESTOR;

	$publisher_id = $params['publisher_id'] ?? '';
	if(empty($publisher_id)) return 0;

	$language = $_GESTOR['linguagem-codigo'];

	$where =
		"WHERE p.publisher_id='".banco_escape_field($publisher_id)."'"
		." AND p.status='A'"
		." AND p.language='".banco_escape_field($language)."'";
	$where .= publisher_index_widget_clausula_busca($params['busca'] ?? '');

	$row = banco_select(Array(
		'unico' => true,
		'tabela' => 'paginas AS p INNER JOIN publisher_pages AS pp ON pp.page_id = p.id AND pp.language = p.language',
		'campos' => Array('COUNT(*) AS total'),
		'extra' => $where
	));

	// banco_select mapeia a chave do retorno pela string literal do campo; lemos o 1º valor.
	return is_array($row) ? (int)reset($row) : 0;
}

/**
 * Busca publicações do publicador aplicando busca textual (LIKE no nome), paginação
 * (offset/limit) e ordenação (date_desc|date_asc|title_asc|title_desc).
 *
 * Cada item inclui campos padrão (page_id, titulo, url, data) + os campos custom
 * presentes em publisher_pages.fields_values.
 */
function publisher_index_widget_buscar_publicacoes($params){
	global $_GESTOR;

	$publisher_id = $params['publisher_id'] ?? '';
	$busca        = trim((string)($params['busca'] ?? ''));
	$offset       = max(0, (int)($params['offset'] ?? 0));
	$limit        = max(1, (int)($params['limit'] ?? 10));
	$order_by_key = $params['order_by'] ?? 'date_desc';

	if(empty($publisher_id)) return [];

	$language = $_GESTOR['linguagem-codigo'];

	$where =
		"WHERE p.publisher_id='".banco_escape_field($publisher_id)."'"
		." AND p.status='A'"
		." AND p.language='".banco_escape_field($language)."'";

	$where .= publisher_index_widget_clausula_busca($busca);

	$order_map = [
		'title_asc'  => ' ORDER BY p.nome ASC',
		'title_desc' => ' ORDER BY p.nome DESC',
		'date_asc'   => ' ORDER BY p.data_modificacao ASC',
		'date_desc'  => ' ORDER BY p.data_modificacao DESC',
	];
	$order = $order_map[$order_by_key] ?? $order_map['date_desc'];
	$limitSql = ' LIMIT '.$offset.', '.$limit;

	// req-041 §1.3: INNER JOIN garante que apenas publicações reais (com correspondência em
	// publisher_pages) entrem na listagem — elimina a própria página de índice e páginas comuns
	// que compartilham o mesmo publisher_id mas não são publicações cadastradas.
	$rows = banco_select(Array(
		'tabela' => 'paginas AS p INNER JOIN publisher_pages AS pp ON pp.page_id = p.id AND pp.language = p.language',
		'campos' => Array(
			'p.id',
			'p.nome',
			'p.caminho',
			'p.data_modificacao',
			'pp.fields_values',
		),
		'extra' => $where.$order.$limitSql
	));

	if(!is_array($rows)) $rows = [];

	gestor_incluir_biblioteca('formato');

	$itens = [];
	foreach($rows as $row){
		$campos_originais = json_decode($row['pp.fields_values'] ?? '[]', true);
		$campos_publisher = [];
		if(is_array($campos_originais)){
			foreach($campos_originais as $item_field){
				if(is_array($item_field) && isset($item_field['id'])){
					// req-041 §1.2: decodifica Unicode corrompido nos campos custom da publicação.
					$campos_publisher[$item_field['id']] = publisher_index_widget_corrigir_unicode((string)($item_field['value'] ?? ''));
				}
			}
		}

		$itens[] = array_merge([
			'page_id' => $row['p.id'],
			// req-041 §1.2: decodifica Unicode corrompido no título antes de devolver para render.
			'titulo'  => publisher_index_widget_corrigir_unicode($row['p.nome'] ?? ''),
			'url'     => $row['p.caminho'] ? $_GESTOR['url-raiz'].$row['p.caminho'] : '',
			'data'    => $row['p.data_modificacao'] ? formato_data_hora_from_datetime_to_text($row['p.data_modificacao']) : '',
		], $campos_publisher);
	}

	return $itens;
}
