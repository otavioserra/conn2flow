<?php
/**
 * Widget renderer do módulo pages-index (Páginas Índice, req-088 / BATCH-088).
 *
 * Clone adaptado do publisher-index: em vez de listar publicações via JOIN em
 * publisher_pages, consulta DIRETAMENTE a tabela `paginas`, filtrando apenas páginas
 * públicas e ativas do site:
 *   status='A' AND language='$language' AND tipo='pagina' AND sem_permissao=1
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando a página
 * contiver um wrapper:
 *   <!-- widgets#pages-index->render({"grupo_slug": "..."}) < -->
 *     ...mockup estático (preview p/ designer)...
 *   <!-- widgets#pages-index->render({"grupo_slug": "..."}) > -->
 *
 * A primeira página é renderizada no servidor (page load) e as páginas seguintes / busca /
 * ordenação são resolvidas via AJAX por pages_index_render_ajax() (acionada por
 * gestor_pagina_widgets_ajax() quando a requisição traz ajax + ajaxWidgets). O template real
 * (html/css) vem EXCLUSIVAMENTE do banco (D-023); se vazio, retornamos string vazia.
 *
 * Blocos suportados no template HTML:
 *   <!-- item < --> ... <!-- item > -->            bloco repetido por página ([[item#campo]])
 *   <!-- no-item < --> ... <!-- no-item > -->       exibido quando não há páginas
 *   <!-- search-input < --> ... <!-- search-input > -->  contêiner da busca (condicional show_search_input)
 *   <!-- sort-select < --> ... <!-- sort-select > -->    contêiner da ordenação (condicional show_sorting_select)
 *   <!-- load-more < --> ... <!-- load-more > -->        botão "carregar mais" (condicional show_load_more_btn && tem_mais)
 *   <!-- metrics < --> ... <!-- metrics > -->            métricas de paginação (condicional show_metrics)
 *
 * Variáveis de item fixas (req-088 §2): [[item#title]] (paginas.nome),
 *   [[item#summary]] (strip_tags de paginas.html truncado ~200), [[item#url]] (url-raiz+caminho).
 *   [[item#date]] (paginas.data_modificacao formatada) também é disponibilizada como conveniência.
 *
 * Variáveis globais resolvidas no contêiner (formato [[var]] ou @[[var]]@):
 *   [[grupo_slug]] [[items_per_page]] [[ordenacao]] [[search]]
 *   [[show_search_input]] [[show_sorting_select]] [[show_load_more_btn]] [[show_metrics]]
 *   [[page_count]] [[page_total]]
 */

function pages_index_get_version(){
	$modulo = json_decode(file_get_contents(__DIR__ . '/pages-index.json'), true);
	return isset($modulo['versao']) ? $modulo['versao'] : '1.0.0';
}

/**
 * Comprimento (em caracteres) do resumo textual gerado a partir do html da página.
 */
if(!defined('PAGES_INDEX_SUMMARY_LEN')) define('PAGES_INDEX_SUMMARY_LEN', 200);

/**
 * Normaliza um valor de schema (bool/int/string) para booleano. Defaults preservam
 * retrocompatibilidade com registros sem a chave.
 */
function pages_index_widget_bool($schema, $key, $default){
	if(!is_array($schema) || !array_key_exists($key, $schema)) return $default;
	$v = $schema[$key];
	if(is_bool($v)) return $v;
	if(is_int($v)) return $v !== 0;
	$v = strtolower(trim((string)$v));
	return ($v === 'true' || $v === '1' || $v === 'yes' || $v === 'on');
}

/**
 * Registra o termo pesquisado na tabela de log do forms-search (analytics), delegando ao
 * helper do módulo forms-search (dono da tabela) — carregado sob demanda e chamado apenas se
 * existir (desacoplamento: sem o forms-search, a listagem segue funcionando).
 */
function pages_index_registrar_busca($termo, $grupo_slug = ''){
	$termo = trim((string)$termo);
	if($termo === '') return;

	if(!function_exists('forms_search_registrar_busca')){
		$widget = __DIR__.'/../forms-search/forms-search.widget.php';
		if(is_file($widget)) require_once($widget);
	}
	if(function_exists('forms_search_registrar_busca')){
		forms_search_registrar_busca($termo, $grupo_slug);
	}
}

/**
 * Page load: busca o registro do índice pelo slug, injeta recursos e renderiza a primeira
 * página de resultados. Intercepta a variável GET `search` para preencher a busca inicial
 * (destino padrão de buscas globais disparadas pelo widget forms-search) e registra o termo.
 */
function pages_index_render($params){
	global $_GESTOR;

	if(!is_array($params)) return '';

	$grupo_slug = $params['grupo_slug'] ?? null;
	if(empty($grupo_slug)) return '';

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'pages_index',
		'campos' => Array(
			'id_pages_index',
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

	// D-023: sem fallback para o mockup do arquivo físico.
	if(trim($html_template) === '') return '';

	// Busca inicial vinda da URL (?search=termo) enviada pelo widget forms-search (GET).
	$busca_inicial = isset($_REQUEST['search']) ? trim((string)$_REQUEST['search']) : '';

	// req-088 §1: registra a busca no log a cada busca disparada no destino.
	if($busca_inicial !== '') pages_index_registrar_busca($busca_inicial, (string)$grupo_slug);

	// Script público do widget (busca/ordenação/carregar mais).
	gestor_pagina_javascript_incluir(Array(
		'tipo' => 'widget',
		'modulo_id' => 'pages-index',
		'versao' => pages_index_get_version(),
	));

	return pages_index_widget_render_inline([
		'html' => $html_template,
		'css' => $registro['css'] ?? '',
		'css_compiled' => $registro['css_compiled'] ?? '',
		'html_extra_head' => $registro['html_extra_head'] ?? '',
		'fields_schema' => $registro['fields_schema'] ?? '{}',
		'grupo_slug' => $grupo_slug,
		'busca' => $busca_inicial,
	]);
}

/**
 * Renderiza o índice a partir de inputs crus (sem ler da tabela). Usado pelo widget normal
 * (após DB lookup) e pelo endpoint AJAX widget-preview do painel de edição.
 */
function pages_index_widget_render_inline($params){
	$html_template   = (string)($params['html'] ?? '');
	$css_custom      = (string)($params['css'] ?? '');
	$css_compiled    = (string)($params['css_compiled'] ?? '');
	$html_extra_head = (string)($params['html_extra_head'] ?? '');
	$grupo_slug      = (string)($params['grupo_slug'] ?? '');
	$busca           = trim((string)($params['busca'] ?? ''));

	if(trim($html_template) === '') return '';

	$schema = $params['fields_schema'] ?? '{}';
	if(is_string($schema)) $schema = json_decode($schema, true);
	if(!is_array($schema)) $schema = [];

	$items_per_page   = (int)($schema['items_per_page'] ?? 10);
	if($items_per_page < 0) $items_per_page = 10;
	if(!isset($schema['items_per_page']) || $schema['items_per_page'] === '') $items_per_page = 10;
	$order_by         = $schema['order_by'] ?? 'date_desc';
	$variable_mapping = $schema['variable_mapping'] ?? [];
	$show_search      = pages_index_widget_bool($schema, 'show_search_input', true);
	$show_sort        = pages_index_widget_bool($schema, 'show_sorting_select', true);
	$show_load_more   = pages_index_widget_bool($schema, 'show_load_more_btn', true);
	$show_metrics     = pages_index_widget_bool($schema, 'show_metrics', true);

	// Primeira página + 1 item extra para detectar se há próxima página.
	$paginas = [];
	$tem_mais = false;
	$total = 0;
	if($items_per_page > 0){
		$paginas = pages_index_widget_buscar_paginas([
			'busca'    => $busca,
			'offset'   => 0,
			'limit'    => $items_per_page + 1,
			'order_by' => $order_by,
		]);
		if(count($paginas) > $items_per_page){
			$tem_mais = true;
			array_pop($paginas);
		}
		$total = pages_index_widget_contar_paginas(['busca' => $busca]);
	}
	$page_count = count($paginas);

	$padraoItem   = '/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i';
	$padraoNoItem = '/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i';

	$temItem   = preg_match($padraoItem, $html_template, $itemMatch);
	$temNoItem = preg_match($padraoNoItem, $html_template, $noItemMatch);

	$output = $html_template;

	if(empty($paginas)){
		if($temItem)   $output = preg_replace($padraoItem, '', $output, 1);
		if($temNoItem) $output = pages_index_widget_substituir_bloco($output, $padraoNoItem, $noItemMatch[1]);
	} else {
		$itensRendered = $temItem ? pages_index_widget_render_itens($itemMatch[1], $paginas, $variable_mapping) : '';
		if($temItem)   $output = pages_index_widget_substituir_bloco($output, $padraoItem, $itensRendered);
		if($temNoItem) $output = preg_replace($padraoNoItem, '', $output, 1);
	}

	// Blocos condicionais de controle.
	$output = pages_index_widget_bloco_condicional($output, 'search-input', $show_search);
	$output = pages_index_widget_bloco_condicional($output, 'sort-select', $show_sort);
	$output = pages_index_widget_bloco_condicional($output, 'metrics', $show_metrics);
	$output = pages_index_widget_bloco_condicional($output, 'load-more', $show_load_more && $tem_mais);

	// Variáveis globais (data-attributes do contêiner).
	$output = pages_index_widget_resolver_globais($output, Array(
		'grupo_slug'          => $grupo_slug,
		'items_per_page'      => (string)$items_per_page,
		'ordenacao'           => $order_by,
		'search'              => htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'),
		'show_search_input'   => $show_search ? 'true' : 'false',
		'show_sorting_select' => $show_sort ? 'true' : 'false',
		'show_load_more_btn'  => $show_load_more ? 'true' : 'false',
		'show_metrics'        => $show_metrics ? 'true' : 'false',
		'page_count'          => (string)$page_count,
		'page_total'          => (string)$total,
	));

	return pages_index_widget_montar_saida($output, $css_custom, $css_compiled, $html_extra_head);
}

/**
 * Roteamento AJAX público (acionado por gestor_pagina_widgets_ajax via ajaxWidgets).
 * Consulta a página solicitada aplicando busca textual e ordenação, e devolve apenas o HTML
 * dos itens em $_GESTOR['ajax-json']. Retorna string vazia: qualquer retorno não-vazio é
 * tratado como erro 500 por gestor_pagina_widgets_ajax().
 */
function pages_index_render_ajax($params){
	global $_GESTOR;

	if(!is_array($params)) $params = [];

	$grupo_slug = $_REQUEST['ajaxRegistroId'] ?? ($params['grupo_slug'] ?? '');

	$req       = isset($_REQUEST['params']) && is_array($_REQUEST['params']) ? $_REQUEST['params'] : [];
	$busca     = isset($req['busca']) ? trim((string)$req['busca']) : '';
	$ordenacao = isset($req['ordenacao']) ? (string)$req['ordenacao'] : '';
	$pagina    = (int)($req['pagina'] ?? 1);
	if($pagina < 1) $pagina = 1;

	if(empty($grupo_slug)) return '';

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'pages_index',
		'campos' => Array(
			'id',
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
	if($items_per_page < 0) $items_per_page = 10;
	if(!isset($schema['items_per_page']) || $schema['items_per_page'] === '') $items_per_page = 10;

	$order_by = $ordenacao !== '' ? $ordenacao : ($schema['order_by'] ?? 'date_desc');
	if(!in_array($order_by, ['date_desc','date_asc','title_asc','title_desc'], true)) $order_by = 'date_desc';

	$variable_mapping = $schema['variable_mapping'] ?? [];
	$offset           = ($pagina - 1) * $items_per_page;

	// req-088 §1: registra a busca no log a cada NOVA busca (página 1) com termo não-vazio,
	// evitando inflar o log com paginação ("carregar mais", página > 1).
	if($pagina === 1 && $busca !== '') pages_index_registrar_busca($busca, (string)$grupo_slug);

	$paginas = [];
	$tem_mais = false;
	$total = 0;
	if($items_per_page > 0){
		$paginas = pages_index_widget_buscar_paginas([
			'busca'    => $busca,
			'offset'   => $offset,
			'limit'    => $items_per_page + 1,
			'order_by' => $order_by,
		]);
		if(count($paginas) > $items_per_page){
			$tem_mais = true;
			array_pop($paginas);
		}
		$total = pages_index_widget_contar_paginas(['busca' => $busca]);
	}

	$itemTemplate = pages_index_widget_extrair_item_template($registro['html'] ?? '');
	$html_itens   = pages_index_widget_render_itens($itemTemplate, $paginas, $variable_mapping);

	// Primeira página sem resultados: devolve o bloco no-item para o script apenas substituir a lista.
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
 * Extrai o conteúdo interno do bloco `item` do template (usado no AJAX para repetir apenas
 * os itens, sem o restante do contêiner).
 */
function pages_index_widget_extrair_item_template($html){
	if(preg_match('/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i', (string)$html, $m)){
		return $m[1];
	}
	return '';
}

/**
 * Repete o template de item para cada página, substituindo as variáveis [[item#X]]
 * (tolerante ao cerco de arrobas @...@ do banco — ver BATCH-016).
 */
function pages_index_widget_render_itens($itemTemplate, $paginas, $variable_mapping){
	if(trim((string)$itemTemplate) === '') return '';

	$out = '';
	foreach($paginas as $pag){
		$out .= preg_replace_callback('/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($pag, $variable_mapping){
			$varName = $m[1];
			$field = isset($variable_mapping[$varName]) ? $variable_mapping[$varName] : $varName;
			return isset($pag[$field]) ? (string)$pag[$field] : '';
		}, $itemTemplate);
	}
	return $out;
}

/**
 * Substitui um bloco delimitado por seu conteúdo renderizado sem que `$`/`\` do conteúdo
 * sejam interpretados como backreferences pelo preg_replace.
 */
function pages_index_widget_substituir_bloco($html, $padrao, $conteudo){
	return preg_replace_callback($padrao, function() use ($conteudo){ return $conteudo; }, $html, 1);
}

/**
 * Mantém (removendo apenas os marcadores) ou remove integralmente um bloco condicional.
 */
function pages_index_widget_bloco_condicional($html, $nome, $manter){
	$padrao = '/<!--\s*'.preg_quote($nome, '/').'\s*<\s*-->([\s\S]*?)<!--\s*'.preg_quote($nome, '/').'\s*>\s*-->/i';
	if($manter){
		return preg_replace_callback($padrao, function($m){ return $m[1]; }, $html);
	}
	return preg_replace($padrao, '', $html);
}

/**
 * Resolve as variáveis globais [[chave]] (ou @[[chave]]@) pelos valores informados.
 */
function pages_index_widget_resolver_globais($html, $valores){
	foreach($valores as $chave => $valor){
		$padrao = '/@?\[\['.preg_quote($chave, '/').'\]\]@?/';
		$html = preg_replace_callback($padrao, function() use ($valor){ return (string)$valor; }, $html);
	}
	return $html;
}

/**
 * Injeta os recursos do índice (CSS, CSS compilado e HTML extra head) no pipeline global,
 * de forma desduplicada (req-028 / DEC-041), e retorna o HTML estrutural limpo.
 */
function pages_index_widget_montar_saida($html, $css, $css_compiled = '', $html_extra_head = ''){
	gestor_pagina_recursos_incluir(Array(
		'css' => $css,
		'css_compiled' => $css_compiled,
		'html_extra_head' => $html_extra_head,
	));
	return $html;
}

/**
 * req-088 §2: monta a cláusula SQL de busca textual sobre os campos `nome` (título) e `html`
 * (conteúdo) da página, ou string vazia quando não há termo.
 */
function pages_index_widget_clausula_busca($busca){
	$busca = trim((string)$busca);
	if($busca === '') return '';

	$literal = banco_escape_field($busca);
	return " AND (nome LIKE '%".$literal."%' OR html LIKE '%".$literal."%')";
}

/**
 * req-088 §2: cláusula WHERE base — apenas páginas públicas e ativas do site.
 */
function pages_index_widget_where_base(){
	global $_GESTOR;
	$language = banco_escape_field($_GESTOR['linguagem-codigo']);
	return "WHERE status='A'"
		." AND language='".$language."'"
		." AND tipo='pagina'"
		." AND sem_permissao=1";
}

/**
 * req-088 §2: conta o total de páginas que casam com a busca atual (sem LIMIT), usando o mesmo
 * filtro base da listagem, para as métricas "Exibindo X de Y".
 */
function pages_index_widget_contar_paginas($params){
	$where = pages_index_widget_where_base();
	$where .= pages_index_widget_clausula_busca($params['busca'] ?? '');

	$row = banco_select(Array(
		'unico' => true,
		'tabela' => 'paginas',
		'campos' => Array('COUNT(*) AS total'),
		'extra' => $where
	));

	return is_array($row) ? (int)reset($row) : 0;
}

/**
 * req-088 §2: busca páginas públicas e ativas aplicando busca textual (nome/html), paginação
 * (offset/limit) e ordenação (date_desc|date_asc|title_asc|title_desc; data = data_modificacao).
 */
function pages_index_widget_buscar_paginas($params){
	$busca        = trim((string)($params['busca'] ?? ''));
	$offset       = max(0, (int)($params['offset'] ?? 0));
	$limit        = max(1, (int)($params['limit'] ?? 10));
	$order_by_key = $params['order_by'] ?? 'date_desc';

	$where = pages_index_widget_where_base();
	$where .= pages_index_widget_clausula_busca($busca);

	$order_map = [
		'title_asc'  => ' ORDER BY nome ASC',
		'title_desc' => ' ORDER BY nome DESC',
		'date_asc'   => ' ORDER BY data_modificacao ASC',
		'date_desc'  => ' ORDER BY data_modificacao DESC',
	];
	$order = $order_map[$order_by_key] ?? $order_map['date_desc'];
	$limitSql = ' LIMIT '.$offset.', '.$limit;

	$rows = banco_select(Array(
		'tabela' => 'paginas',
		'campos' => Array(
			'id',
			'nome',
			'caminho',
			'html',
			'data_modificacao',
		),
		'extra' => $where.$order.$limitSql
	));

	return pages_index_widget_montar_itens($rows);
}

/**
 * req-088 §2: converte as linhas cruas da consulta em itens prontos para o render, com as três
 * variáveis fixas obrigatórias — title (nome), summary (strip_tags do html truncado ~200) e url
 * (url-raiz + caminho) — mais date (data_modificacao formatada) como conveniência.
 */
function pages_index_widget_montar_itens($rows){
	global $_GESTOR;

	if(!is_array($rows)) return [];

	gestor_incluir_biblioteca('formato');

	$itens = [];
	foreach($rows as $row){
		$itens[] = [
			'title'   => (string)($row['nome'] ?? ''),
			'summary' => pages_index_widget_resumo($row['html'] ?? ''),
			'url'     => !empty($row['caminho']) ? $_GESTOR['url-raiz'].$row['caminho'] : '',
			'date'    => !empty($row['data_modificacao']) ? formato_data_hora_from_datetime_to_text($row['data_modificacao']) : '',
		];
	}

	return $itens;
}

/**
 * req-088 §2: gera um resumo textual da página a partir do html — remove tags, normaliza espaços
 * e trunca em ~PAGES_INDEX_SUMMARY_LEN caracteres (com reticências quando cortado).
 */
function pages_index_widget_resumo($html){
	$texto = trim((string)$html);
	if($texto === '') return '';

	$texto = strip_tags($texto);
	// Normaliza espaços/quebras em um único espaço.
	$texto = preg_replace('/\s+/u', ' ', $texto);
	$texto = trim(html_entity_decode($texto, ENT_QUOTES, 'UTF-8'));

	if(function_exists('mb_strlen')){
		if(mb_strlen($texto, 'UTF-8') > PAGES_INDEX_SUMMARY_LEN){
			$texto = rtrim(mb_substr($texto, 0, PAGES_INDEX_SUMMARY_LEN, 'UTF-8')).'…';
		}
	} else if(strlen($texto) > PAGES_INDEX_SUMMARY_LEN){
		$texto = rtrim(substr($texto, 0, PAGES_INDEX_SUMMARY_LEN)).'…';
	}

	return $texto;
}
