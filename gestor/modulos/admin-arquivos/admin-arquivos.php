<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-arquivos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-arquivos.json'), true);

// =====================================================================
// BATCH-090 (req-090): Gerenciador de arquivos baseado em ÁRVORE FÍSICA
// de diretórios sob $_GESTOR['contents-path']. A tabela `arquivos` deixa de
// ser lida/gravada para arquivos individuais; a associação arquivo↔categoria
// usa o CAMINHO RELATIVO (indexado por MD5) na tabela `arquivos_disco_categorias`.
// Helpers puros de segurança (path traversal, extensão perigosa, sanitização
// de nomes) vivem na biblioteca `bibliotecas/arquivo.php`.
// =====================================================================

// ===== Interfaces Auxiliares

/**
 * Retorna a raiz absoluta de conteúdos, sempre com barra final normalizada.
 */
function admin_arquivos_base(){
	global $_GESTOR;
	return rtrim(str_replace('\\', '/', $_GESTOR['contents-path']), '/') . '/';
}

/**
 * Cria um diretório herdando a permissão do pai (recursivamente).
 */
function admin_arquivos_criar_dir_herdando_permissao($dir) {
	if (!is_dir($dir)) {
		$pai = dirname($dir);
		$permissao_pai = is_dir($pai) ? fileperms($pai) & 0777 : 0755;
		@mkdir($dir, $permissao_pai, true);
	}
}

/**
 * Monta o breadcrumb (lista de segmentos) a partir de um caminho relativo seguro.
 *
 * @param string $rel Caminho relativo canônico (ex.: "files/2026").
 * @return array Lista [{nome, caminho}] começando pela raiz.
 */
function admin_arquivos_breadcrumb($rel){
	$itens = Array(Array('nome' => '', 'caminho' => '', 'raiz' => true));
	if ($rel === '') return $itens;

	$acc = '';
	foreach (explode('/', $rel) as $seg) {
		$acc = $acc === '' ? $seg : $acc . '/' . $seg;
		$itens[] = Array('nome' => $seg, 'caminho' => $acc);
	}
	return $itens;
}

/**
 * Formata bytes em unidade legível (mesma lógica do frontend).
 */
function admin_arquivos_formatar_bytes($bytes){
	$bytes = (float)$bytes;
	if ($bytes <= 0) return '0 Bytes';
	$un = Array('Bytes','KB','MB','GB','TB','PB');
	$i = (int)floor(log($bytes) / log(1024));
	if ($i >= count($un)) $i = count($un) - 1;
	return round($bytes / pow(1024, $i), 2) . ' ' . $un[$i];
}

/**
 * Imagem de referência (ícone) por tipo, quando não há miniatura.
 */
function admin_arquivos_icone_tipo($tipo){
	global $_GESTOR;
	switch ($tipo) {
		case 'image': return $_GESTOR['url-full'] . 'images/imagem-padrao.png';
		case 'video': return $_GESTOR['url-full'] . 'images/video-padrao.png';
		case 'audio': return $_GESTOR['url-full'] . 'images/audio-padrao.png';
		default: return $_GESTOR['url-full'] . 'images/file-padrao.png';
	}
}

/**
 * Indica se o GD deste ambiente consegue ler+gravar a extensão da imagem para
 * gerar miniatura. Formatos vetoriais/não-suportados (svg, ico, tiff) e raster
 * sem suporte compilado (ex.: webp sem `--with-webp`) retornam false.
 *
 * @param string $nome Nome do arquivo (com extensão).
 * @return bool
 */
function admin_arquivos_pode_gerar_miniatura($nome){
	if (!function_exists('imagecreatetruecolor')) return false; // GD ausente
	$ext = strtolower(pathinfo((string)$nome, PATHINFO_EXTENSION));
	switch ($ext) {
		case 'jpg':
		case 'jpeg': return function_exists('imagecreatefromjpeg') && function_exists('imagejpeg');
		case 'png':  return function_exists('imagecreatefrompng') && function_exists('imagepng');
		case 'gif':  return function_exists('imagecreatefromgif') && function_exists('imagegif');
		case 'webp': return function_exists('imagecreatefromwebp') && function_exists('imagewebp');
		case 'bmp':  return function_exists('imagecreatefrombmp') && function_exists('imagebmp');
		case 'avif': return function_exists('imagecreatefromavif') && function_exists('imageavif');
		default:     return false; // svg, ico, tiff, etc. — o GD não rasteriza
	}
}

/**
 * Gera a miniatura de uma imagem (subpasta física `mini/`) usando SimpleImage.
 *
 * @param string $absArquivo Caminho absoluto do arquivo original.
 * @param string $relArquivo Caminho relativo do arquivo original.
 * @return string|false Caminho relativo da miniatura gerada, ou false em erro/formato não suportado.
 */
function admin_arquivos_gerar_miniatura($absArquivo, $relArquivo){
	global $_GESTOR;

	// Só tenta quando o GD deste ambiente suporta o formato (evita \Error fatal
	// em leitura, ex.: imagecreatefromwebp indefinido → 500).
	if (!admin_arquivos_pode_gerar_miniatura($relArquivo)) {
		return false;
	}

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$larguraMini = isset($modulo['imagem']['mini_width']) ? (int)$modulo['imagem']['mini_width'] : 200;

	$relMini = arquivo_mini_caminho_relativo($relArquivo);
	$absMini = admin_arquivos_base() . $relMini;

	admin_arquivos_criar_dir_herdando_permissao(dirname($absMini));

	try {
		require_once $_GESTOR['bibliotecas-path'].'SimpleImage/src/claviska/SimpleImage.php';
		$image = new \claviska\SimpleImage();
		$image->fromFile($absArquivo);
		if ($image->getWidth() > $larguraMini) {
			$image->resize($larguraMini);
		}
		$image->toFile($absMini);
		return $relMini;
	} catch (\Throwable $err) {
		// Qualquer falha (Exception OU Error, incl. função GD ausente) degrada sem 500.
		return false;
	}
}

/**
 * Monta os metadados de exibição de um arquivo físico.
 *
 * @param string $rel Caminho relativo do arquivo.
 * @param string $abs Caminho absoluto do arquivo.
 * @return array Estrutura consumida pelo frontend/picker.
 */
function admin_arquivos_arquivo_dados($rel, $abs){
	global $_GESTOR;

	$nome = basename($rel);
	$tipo = arquivo_tipo_por_extensao($nome);
	$mtime = @filemtime($abs);
	if ($mtime === false) $mtime = time();
	$size = @filesize($abs);
	if ($size === false) $size = 0;

	// Miniatura física, se existir.
	$relMini = arquivo_mini_caminho_relativo($rel);
	$absMini = admin_arquivos_base() . $relMini;
	$temMini = ($tipo === 'image') && is_file($absMini);
	$podeMini = ($tipo === 'image') && admin_arquivos_pode_gerar_miniatura($nome);

	if ($temMini) {
		// Miniatura gerada — usar com cache-bust pelo timestamp da origem.
		$imgSrc = $_GESTOR['url-full'] . $relMini . '?t=' . (@filemtime($absMini) ?: $mtime);
	} elseif ($tipo === 'image' && !$podeMini) {
		// Imagem que o GD não thumbnailiza (ex.: webp sem suporte, svg) — o navegador
		// renderiza o próprio arquivo; não haverá miniatura.
		$imgSrc = $_GESTOR['url-full'] . $rel . '?t=' . $mtime;
	} else {
		// Imagem thumbnaisável ainda sem mini: ícone genérico; o lazy substitui pela mini.
		$imgSrc = admin_arquivos_icone_tipo($tipo);
	}

	return Array(
		'nome' => $nome,
		'caminho' => $rel,
		'url' => $_GESTOR['url-full'] . $rel,
		'tipo' => $tipo,
		'mime' => $tipo . '/' . strtolower(pathinfo($nome, PATHINFO_EXTENSION)),
		'mtime' => $mtime,
		'data' => interface_formatar_dado(Array('dado' => date('Y-m-d H:i:s', $mtime), 'formato' => 'dataHora')),
		'size' => $size,
		'sizeFmt' => admin_arquivos_formatar_bytes($size),
		'imgSrc' => $imgSrc,
		'ehImagem' => ($tipo === 'image'),
		'temMini' => $temMini,
		'podeMini' => $podeMini,
	);
}

// ===== Categorias por caminho físico (tabela arquivos_disco_categorias) =====

/**
 * Retorna os ids de categorias associados a um caminho relativo.
 */
function admin_arquivos_categorias_do_caminho($rel){
	$hash = md5($rel);
	$rows = banco_select_name(
		banco_campos_virgulas(Array('id_categorias')),
		'arquivos_disco_categorias',
		"WHERE caminho_hash='".$hash."'"
	);
	$ids = Array();
	if ($rows) foreach ($rows as $r) $ids[] = (int)$r['id_categorias'];
	return $ids;
}

/**
 * Substitui todas as associações de categoria de um caminho pelos ids informados.
 */
function admin_arquivos_categorias_definir($rel, $categorias){
	$hash = md5($rel);
	banco_delete('arquivos_disco_categorias', "WHERE caminho_hash='".$hash."'");

	if ($categorias) foreach ($categorias as $cat) {
		$cat = (int)$cat;
		if ($cat <= 0) continue;
		banco_insert_name(
			Array(
				Array('caminho', banco_escape_field($rel)),
				Array('caminho_hash', $hash),
				Array('id_categorias', $cat),
				Array('data_criacao', 'NOW()', true),
			),
			'arquivos_disco_categorias'
		);
	}
}

/**
 * Remove as associações de categoria de um caminho (ou de todos sob um prefixo de pasta).
 */
function admin_arquivos_categorias_remover($rel, $comoPasta = false){
	if ($comoPasta) {
		banco_delete('arquivos_disco_categorias', "WHERE caminho='".banco_escape_field($rel)."' OR caminho LIKE '".banco_escape_field($rel)."/%'");
	} else {
		banco_delete('arquivos_disco_categorias', "WHERE caminho_hash='".md5($rel)."'");
	}
}

/**
 * Move as associações de categoria de um caminho antigo para um novo (arquivo ou pasta inteira).
 */
function admin_arquivos_categorias_mover($relAntigo, $relNovo, $comoPasta = false){
	if ($comoPasta) {
		$rows = banco_select_name(
			banco_campos_virgulas(Array('id_arquivos_disco_categorias', 'caminho', 'id_categorias')),
			'arquivos_disco_categorias',
			"WHERE caminho='".banco_escape_field($relAntigo)."' OR caminho LIKE '".banco_escape_field($relAntigo)."/%'"
		);
		if ($rows) foreach ($rows as $r) {
			$novo = $relNovo . substr($r['caminho'], strlen($relAntigo));
			banco_update(
				banco_campos_virgulas(Array(
					"caminho='".banco_escape_field($novo)."'",
					"caminho_hash='".md5($novo)."'",
				)),
				'arquivos_disco_categorias',
				"WHERE id_arquivos_disco_categorias='".$r['id_arquivos_disco_categorias']."'"
			);
		}
	} else {
		banco_update(
			banco_campos_virgulas(Array(
				"caminho='".banco_escape_field($relNovo)."'",
				"caminho_hash='".md5($relNovo)."'",
			)),
			'arquivos_disco_categorias',
			"WHERE caminho_hash='".md5($relAntigo)."'"
		);
	}
}

// ===== Varredura física de uma pasta =====

/**
 * Lê o conteúdo físico de uma pasta e retorna pastas + arquivos filtrados/ordenados/paginados.
 */
function admin_arquivos_ler_pasta($dirRel, $paginaAtual, $filtros){
	global $_GESTOR;

	$base = admin_arquivos_base();
	$abs = arquivo_caminho_resolver($base, $dirRel);

	// Pasta inválida/inexistente (ex.: cache de última pasta que foi excluída):
	// cai graciosamente para a raiz de conteúdos em vez de listar vazio.
	if ($abs === false || !is_dir($abs)) {
		$dirRel = '';
		$abs = arquivo_caminho_resolver($base, '');
	}

	$dirSeguro = arquivo_caminho_relativo_seguro($dirRel);
	if ($dirSeguro === false) $dirSeguro = '';

	if ($abs === false || !is_dir($abs)) {
		// Nem a raiz existe — retorna vazio de forma segura.
		return Array(
			'ok' => true,
			'dir' => '',
			'breadcrumb' => admin_arquivos_breadcrumb(''),
			'pastas' => Array(),
			'arquivos' => Array(),
			'total' => 0,
			'totalPaginas' => 0,
			'paginaAtual' => 0,
			'thumbsMissing' => Array(),
		);
	}

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$maxPorPagina = isset($modulo['lista']['max_por_pagina']) ? (int)$modulo['lista']['max_por_pagina'] : 60;

	// ===== Filtros
	$order = isset($filtros['order']) ? $filtros['order'] : 'alphabetical-asc';
	$dataDe = isset($filtros['dataDe']) && $filtros['dataDe'] ? strtotime(date('Y-m-d 00:00:00', strtotime($filtros['dataDe']))) : null;
	$dataAte = isset($filtros['dataAte']) && $filtros['dataAte'] ? strtotime(date('Y-m-t 23:59:59', strtotime($filtros['dataAte']))) : null;
	$categoriasFiltro = (isset($filtros['categorias']) && is_array($filtros['categorias'])) ? array_map('intval', $filtros['categorias']) : Array();

	$pastas = Array();
	$arquivos = Array();

	$itens = @scandir($abs);
	if ($itens === false) $itens = Array();

	foreach ($itens as $nome) {
		if ($nome === '.' || $nome === '..') continue;
		if ($nome === 'mini') continue;              // subpasta reservada de miniaturas
		if ($nome[0] === '.') continue;              // arquivos ocultos

		$absItem = $abs . DIRECTORY_SEPARATOR . $nome;
		$relItem = $dirSeguro === '' ? $nome : $dirSeguro . '/' . $nome;
		$mtime = @filemtime($absItem);
		if ($mtime === false) $mtime = 0;

		if (is_dir($absItem)) {
			$pastas[] = Array(
				'nome' => $nome,
				'caminho' => $relItem,
				'mtime' => $mtime,
				'data' => interface_formatar_dado(Array('dado' => date('Y-m-d H:i:s', $mtime), 'formato' => 'dataHora')),
			);
		} else {
			// Filtro por data de modificação.
			if ($dataDe !== null && $mtime < $dataDe) continue;
			if ($dataAte !== null && $mtime > $dataAte) continue;

			$arquivos[] = admin_arquivos_arquivo_dados($relItem, $absItem);
		}
	}

	// ===== Filtro por categorias (cruza associações do banco com arquivos físicos).
	if ($categoriasFiltro) {
		$permitidos = Array();
		// Busca todos os caminhos desta pasta que possuem alguma das categorias.
		$hashes = Array();
		foreach ($arquivos as $a) $hashes[md5($a['caminho'])] = $a['caminho'];
		if ($hashes) {
			$inHash = "'".implode("','", array_keys($hashes))."'";
			$inCat = implode(',', $categoriasFiltro);
			$rows = banco_select_name(
				banco_campos_virgulas(Array('caminho_hash')),
				'arquivos_disco_categorias',
				"WHERE caminho_hash IN (".$inHash.") AND id_categorias IN (".$inCat.")"
			);
			if ($rows) foreach ($rows as $r) $permitidos[$r['caminho_hash']] = true;
		}
		$arquivos = array_values(array_filter($arquivos, function($a) use ($permitidos){
			return isset($permitidos[md5($a['caminho'])]);
		}));
	}

	// ===== Ordenação (pastas sempre primeiro, ordenadas por nome/data também).
	$cmpNomeAsc = function($a, $b){ return strcasecmp($a['nome'], $b['nome']); };
	$cmpNomeDesc = function($a, $b){ return strcasecmp($b['nome'], $a['nome']); };
	$cmpDataAsc = function($a, $b){ return $a['mtime'] <=> $b['mtime']; };
	$cmpDataDesc = function($a, $b){ return $b['mtime'] <=> $a['mtime']; };

	switch ($order) {
		case 'alphabetical-desc': $cmp = $cmpNomeDesc; break;
		case 'order-date-asc': $cmp = $cmpDataAsc; break;
		case 'order-date-desc': $cmp = $cmpDataDesc; break;
		default: $cmp = $cmpNomeAsc;
	}
	usort($pastas, $cmp);
	usort($arquivos, $cmp);

	// ===== Paginação (aplicada aos ARQUIVOS; pastas aparecem só na 1ª página).
	$total = count($arquivos);
	$totalPaginas = $maxPorPagina > 0 ? (int)ceil($total / $maxPorPagina) : 1;
	$paginaAtual = max(0, (int)$paginaAtual);
	$arquivosPagina = array_slice($arquivos, $paginaAtual * $maxPorPagina, $maxPorPagina);

	// ===== Miniaturas faltantes (só imagens thumbnaisáveis por este GD, sem mini).
	$thumbsMissing = Array();
	foreach ($arquivosPagina as $a) {
		if ($a['ehImagem'] && !$a['temMini'] && !empty($a['podeMini'])) {
			$thumbsMissing[] = $a['caminho'];
		}
	}

	return Array(
		'ok' => true,
		'dir' => $dirSeguro,
		'breadcrumb' => admin_arquivos_breadcrumb($dirSeguro),
		'pastas' => $paginaAtual === 0 ? $pastas : Array(),
		'arquivos' => $arquivosPagina,
		'total' => $total,
		'totalPaginas' => $totalPaginas,
		'paginaAtual' => $paginaAtual,
		'thumbsMissing' => $thumbsMissing,
	);
}

/**
 * Mapa de textos i18n do módulo entregue ao frontend.
 */
function admin_arquivos_i18n(){
	global $_GESTOR;
	$ids = Array(
		'add-file-progress-processing','add-file-progress-finish','add-file-progress-error',
		'list-button-copy','list-button-del','list-button-select','list-not-categorised',
		'folder-new','folder-rename','folder-delete','folder-name-placeholder',
		'folder-not-empty-confirm','delete-confirm','delete-selected','select-all',
		'view-large','view-list','view-small','view-medium','breadcrumb-root',
		'thumbs-processing','col-name','col-date','col-type','col-size','empty-folder',
		'upload-error-size','upload-error-extension','copied','preview-size',
		'destination-folder','create-here',
	);
	$map = Array();
	foreach ($ids as $id) {
		$map[$id] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => $id));
	}
	return $map;
}

// ===== Interfaces Principais

function admin_arquivos_listar_arquivos(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	// ===== Inclusão Interface
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';

	if (!isset($_GESTOR['javascript-vars']['interface'])) {
		$_GESTOR['javascript-vars']['interface'] = Array();
	}

	// ===== Componentes de interface (modais)
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-delecao',
			'modal-alerta',
		)
	));

	gestor_pagina_javascript_incluir();

	// ===== Variáveis JS do gerenciador
	$maxPorPagina = isset($modulo['lista']['max_por_pagina']) ? (int)$modulo['lista']['max_por_pagina'] : 60;

	$_GESTOR['javascript-vars']['adminArquivos'] = Array(
		'contentsUrl' => $_GESTOR['url-full'],
		'dirInicial' => '',
		'maxPorPagina' => $maxPorPagina,
		'loteMiniaturas' => isset($modulo['lista']['lote_miniaturas']) ? (int)$modulo['lista']['lote_miniaturas'] : 5,
		'paginaIframe' => $_GESTOR['paginaIframe'] ? true : false,
		'i18n' => admin_arquivos_i18n(),
	);

	// ===== Estados vazios
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#without-results-cont#",gestor_componente(Array(
		'id' => 'interface-listar-arquivos-sem-registros',
	)));

	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#paginaIframe#",($_GESTOR['paginaIframe'] ? '?paginaIframe=sim' : ''));

	// ===== No modo picker (iframe) ocultar as ferramentas de gestão de pastas/upload
	if ($_GESTOR['paginaIframe']) {
		$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- normal-tools < -->','<!-- normal-tools > -->','');
	}

	// ===== Filtro por categorias e ordenação (selects Fomantic server-side)
	interface_formulario_campos(Array(
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'categories',
				'nome' => 'categorias',
				'procurar' => true,
				'limpar' => true,
				'multiple' => true,
				'fluid' => true,
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'categories-placeholder')),
				'tabela' => Array(
					'nome' => 'categorias',
					'campo' => 'nome',
					'id_numerico' => 'id_categorias',
					'where' => "id_modulos='11' OR id_modulos IS NULL",
				),
			),
			Array(
				'tipo' => 'select',
				'id' => 'order',
				'nome' => 'ordenar',
				'menu' => true,
				'procurar' => true,
				'fluid' => true,
				'valor_selecionado' => 'alphabetical-asc',
				'valor_selecionado_texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-alphabetical-asc')),
				'valor_selecionado_icone' => 'sort alphabet down',
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-placeholder')),
				'dados' => Array(
					Array('texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-alphabetical-asc')), 'valor' => 'alphabetical-asc', 'icone' => 'sort alphabet down'),
					Array('texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-alphabetical-desc')), 'valor' => 'alphabetical-desc', 'icone' => 'sort alphabet up alternate'),
					Array('texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-date-asc')), 'valor' => 'order-date-asc', 'icone' => 'sort amount down alternate'),
					Array('texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-date-desc')), 'valor' => 'order-date-desc', 'icone' => 'sort amount up'),
				),
			),
		)
	));
}

function admin_arquivos_upload(){
	global $_GESTOR;

	// ===== Inclusão Interface (para o picker de categorias e modais)
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';

	if (!isset($_GESTOR['javascript-vars']['interface'])) {
		$_GESTOR['javascript-vars']['interface'] = Array();
	}

	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));

	// ===== jQuery File Upload (drag & drop + upload assíncrono)
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/css/jquery.fileupload.css">');
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/jquery.ui.widget.js"></script>');
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/jquery.iframe-transport.js"></script>');
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/jquery.fileupload.js"></script>');

	gestor_pagina_javascript_incluir();

	// ===== Trocar o botão voltar / URL conforme picker
	if ($_GESTOR['paginaIframe']) {
		$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- botao-voltar < -->','<!-- botao-voltar > -->','');
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#url#",$_GESTOR['url-full'] . 'admin-arquivos/?paginaIframe=sim');
	} else {
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#url#",$_GESTOR['url-full'] . 'admin-arquivos/');
	}
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#paginaIframe#",($_GESTOR['paginaIframe'] ? '?paginaIframe=sim' : ''));

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	// ===== Extrair o template da célula de arquivo (consumido pelo JS na pré-visualização)
	$cel_nome = 'files-cel';
	$filesCel = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');

	if ($_GESTOR['paginaIframe']) {
		$filesCel = modelo_tag_in($filesCel,'<!-- btn-copy < -->','<!-- btn-copy > -->','');
	} else {
		$filesCel = modelo_tag_in($filesCel,'<!-- btn-select < -->','<!-- btn-select > -->','');
	}

	$_GESTOR['javascript-vars']['arquivosCel'] = gestor_pagina_variaveis_globais(Array('html' => $filesCel));
	$_GESTOR['javascript-vars']['arquivosConcluido'] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-file-progress-finish'));
	$_GESTOR['javascript-vars']['arquivosProcessando'] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-file-progress-processing'));
	$_GESTOR['javascript-vars']['arquivosErro'] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-file-progress-error'));

	// `dirExplicito` sinaliza que a pasta veio pela URL (botão "Adicionar" da listagem);
	// quando ausente, o frontend usa a última pasta em cache (localStorage).
	$dirExplicito = isset($_REQUEST['dir']);

	$_GESTOR['javascript-vars']['adminArquivos'] = Array(
		'contentsUrl' => $_GESTOR['url-full'],
		'dirExplicito' => $dirExplicito,
		'dirInicial' => ($dirExplicito ? (arquivo_caminho_relativo_seguro($_REQUEST['dir']) ?: '') : ''),
		'maxUploadBytes' => isset($modulo['upload']['max_bytes']) ? (int)$modulo['upload']['max_bytes'] : 10000000,
		'paginaIframe' => $_GESTOR['paginaIframe'] ? true : false,
		'i18n' => admin_arquivos_i18n(),
	);

	// ===== Categorias (opcional) para vincular no upload
	interface_formulario_campos(Array(
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'categories',
				'nome' => 'categorias',
				'procurar' => true,
				'limpar' => true,
				'multiple' => true,
				'fluid' => true,
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-categories-placeholder')),
				'tabela' => Array(
					'nome' => 'categorias',
					'campo' => 'nome',
					'id_numerico' => 'id_categorias',
					'where' => "id_modulos='11' OR id_modulos IS NULL",
				),
			)
		)
	));
}

function admin_arquivos_interfaces_padroes(){
	global $_GESTOR;
}

// ==== Ajax

/**
 * Lê e ordena o conteúdo físico de uma pasta e retorna JSON para o frontend.
 */
function admin_arquivos_ajax_listar(){
	global $_GESTOR;

	$dir = isset($_REQUEST['dir']) ? $_REQUEST['dir'] : '';
	$pagina = isset($_REQUEST['pagina']) ? (int)$_REQUEST['pagina'] : 0;

	$filtros = Array();
	if (isset($_REQUEST['filtros'])) {
		$decodificado = json_decode(stripslashes($_REQUEST['filtros']), true);
		if (is_array($decodificado)) $filtros = $decodificado;
	}

	$resultado = admin_arquivos_ler_pasta($dir, $pagina, $filtros);

	$_GESTOR['ajax-json'] = array_merge(Array('status' => 'Ok'), $resultado);
}

/**
 * Recebe upload de arquivo, higieniza o nome, bloqueia extensões perigosas e
 * grava na pasta física de destino (sob contents-path). NÃO grava na tabela `arquivos`.
 */
function admin_arquivos_ajax_upload_file(){
	global $_GESTOR;

	if (!isset($_FILES['files'])) {
		$_GESTOR['ajax-json'] = Array('error' => 'Error - File not found in request', 'status' => 'Error');
		return;
	}

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$maxBytes = isset($modulo['upload']['max_bytes']) ? (int)$modulo['upload']['max_bytes'] : 10000000;

	$nomeOriginal = basename($_FILES['files']['name'][0]);
	$tmp = $_FILES['files']['tmp_name'][0];
	$size = $_FILES['files']['size'][0];

	// ===== Bloqueio de extensões perigosas (defesa server-side).
	if (arquivo_extensao_perigosa($nomeOriginal)) {
		@unlink($tmp);
		$_GESTOR['ajax-json'] = Array(
			'error' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'upload-error-extension')),
			'status' => 'Error',
		);
		return;
	}

	// ===== Limite de tamanho.
	if ($size > $maxBytes) {
		@unlink($tmp);
		$_GESTOR['ajax-json'] = Array(
			'error' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'upload-error-size')),
			'status' => 'Error',
		);
		return;
	}

	// ===== Pasta de destino (validada contra path traversal).
	$base = admin_arquivos_base();
	$dirRel = isset($_REQUEST['dir']) ? (arquivo_caminho_relativo_seguro($_REQUEST['dir']) ?: '') : '';
	$absDir = arquivo_caminho_resolver($base, $dirRel);
	if ($absDir === false) {
		@unlink($tmp);
		$_GESTOR['ajax-json'] = Array('error' => 'Invalid destination', 'status' => 'Error');
		return;
	}
	admin_arquivos_criar_dir_herdando_permissao($absDir);

	// ===== Nome higienizado + resolução de colisão.
	$nomeSeguro = arquivo_nome_sanitizar($nomeOriginal);
	if ($nomeSeguro === '') $nomeSeguro = 'arquivo';

	$ext = pathinfo($nomeSeguro, PATHINFO_EXTENSION);
	$nomeBase = $ext !== '' ? substr($nomeSeguro, 0, -(strlen($ext) + 1)) : $nomeSeguro;

	$nomeFinal = $nomeSeguro;
	$i = 1;
	while (is_file($absDir . DIRECTORY_SEPARATOR . $nomeFinal)) {
		$nomeFinal = $nomeBase . ' (' . $i . ')' . ($ext !== '' ? '.' . $ext : '');
		$i++;
	}

	$absArquivo = $absDir . DIRECTORY_SEPARATOR . $nomeFinal;
	$relArquivo = ($dirRel === '' ? '' : $dirRel . '/') . $nomeFinal;

	if (!move_uploaded_file($tmp, $absArquivo)) {
		@unlink($tmp);
		$_GESTOR['ajax-json'] = Array('error' => 'Error - '.$_FILES['files']['error'][0], 'status' => 'Error');
		return;
	}

	// ===== Miniatura imediata (best-effort) para imagens.
	if (arquivo_tipo_por_extensao($nomeFinal) === 'image') {
		admin_arquivos_gerar_miniatura($absArquivo, $relArquivo);
	}

	// ===== Vínculo de categorias por caminho (tabela nova, não a legada).
	if (isset($_REQUEST['categorias']) && $_REQUEST['categorias'] !== '') {
		$categorias = explode(',', banco_escape_field($_REQUEST['categorias']));
		admin_arquivos_categorias_definir($relArquivo, $categorias);
	}

	$dados = admin_arquivos_arquivo_dados($relArquivo, $absArquivo);
	$dados['status'] = 'Ok';
	$_GESTOR['ajax-json'] = $dados;
}

/**
 * Gera miniaturas em lote para uma lista de caminhos relativos de imagens.
 */
function admin_arquivos_ajax_miniaturas(){
	global $_GESTOR;

	$base = admin_arquivos_base();

	$lista = Array();
	if (isset($_REQUEST['arquivos'])) {
		$decodificado = json_decode(stripslashes($_REQUEST['arquivos']), true);
		if (is_array($decodificado)) $lista = $decodificado;
	}

	$resultados = Array();
	foreach ($lista as $rel) {
		$relSeguro = arquivo_caminho_relativo_seguro($rel);
		if ($relSeguro === false) { $resultados[] = Array('caminho' => $rel, 'ok' => false); continue; }

		$abs = arquivo_caminho_resolver($base, $relSeguro);
		if ($abs === false || !is_file($abs)) { $resultados[] = Array('caminho' => $relSeguro, 'ok' => false); continue; }
		if (arquivo_tipo_por_extensao($relSeguro) !== 'image') { $resultados[] = Array('caminho' => $relSeguro, 'ok' => false); continue; }
		if (!admin_arquivos_pode_gerar_miniatura($relSeguro)) { $resultados[] = Array('caminho' => $relSeguro, 'ok' => false); continue; }

		$relMini = admin_arquivos_gerar_miniatura($abs, $relSeguro);
		if ($relMini === false) { $resultados[] = Array('caminho' => $relSeguro, 'ok' => false); continue; }

		$absMini = $base . $relMini;
		$resultados[] = Array(
			'caminho' => $relSeguro,
			'ok' => true,
			'miniUrl' => $_GESTOR['url-full'] . $relMini . '?t=' . (@filemtime($absMini) ?: time()),
		);
	}

	$_GESTOR['ajax-json'] = Array('status' => 'Ok', 'resultados' => $resultados);
}

/**
 * Exclui arquivos e/ou pastas (em lote). Pastas não-vazias exigem recursivo=true.
 */
function admin_arquivos_ajax_excluir(){
	global $_GESTOR;

	$base = admin_arquivos_base();
	$recursivo = isset($_REQUEST['recursivo']) && ($_REQUEST['recursivo'] === 'true' || $_REQUEST['recursivo'] === '1' || $_REQUEST['recursivo'] === true);

	$itens = Array();
	if (isset($_REQUEST['itens'])) {
		$decodificado = json_decode(stripslashes($_REQUEST['itens']), true);
		if (is_array($decodificado)) $itens = $decodificado;
	}

	$resultados = Array();
	foreach ($itens as $item) {
		$rel = isset($item['caminho']) ? $item['caminho'] : '';
		$tipo = isset($item['tipo']) ? $item['tipo'] : 'arquivo';

		$relSeguro = arquivo_caminho_relativo_seguro($rel);
		if ($relSeguro === false || $relSeguro === '') { $resultados[] = Array('caminho' => $rel, 'status' => 'Invalid'); continue; }

		$abs = arquivo_caminho_resolver($base, $relSeguro);
		if ($abs === false) { $resultados[] = Array('caminho' => $relSeguro, 'status' => 'Invalid'); continue; }

		if ($tipo === 'pasta' || is_dir($abs)) {
			$status = admin_arquivos_excluir_pasta($abs, $relSeguro, $recursivo);
			$resultados[] = Array('caminho' => $relSeguro, 'tipo' => 'pasta', 'status' => $status);
		} else {
			$status = admin_arquivos_excluir_arquivo_fisico($abs, $relSeguro);
			$resultados[] = Array('caminho' => $relSeguro, 'tipo' => 'arquivo', 'status' => $status);
		}
	}

	$_GESTOR['ajax-json'] = Array('status' => 'Ok', 'resultados' => $resultados);
}

/**
 * Remove um arquivo físico, sua miniatura e as associações de categoria.
 */
function admin_arquivos_excluir_arquivo_fisico($abs, $rel){
	if (!is_file($abs)) return 'NotFound';

	@unlink($abs);

	// Miniatura correspondente.
	$absMini = admin_arquivos_base() . arquivo_mini_caminho_relativo($rel);
	if (is_file($absMini)) @unlink($absMini);

	admin_arquivos_categorias_remover($rel, false);
	return 'Ok';
}

/**
 * Remove uma pasta. Se não vazia (ignorando `mini/`), exige recursivo.
 */
function admin_arquivos_excluir_pasta($abs, $rel, $recursivo){
	if (!is_dir($abs)) return 'NotFound';

	$conteudo = array_diff(@scandir($abs) ?: Array(), Array('.', '..', 'mini'));

	if (!empty($conteudo) && !$recursivo) {
		return 'NotEmpty';
	}

	admin_arquivos_remover_dir_recursivo($abs);
	admin_arquivos_categorias_remover($rel, true);
	return 'Ok';
}

/**
 * Apaga recursivamente um diretório do disco.
 */
function admin_arquivos_remover_dir_recursivo($abs){
	if (!is_dir($abs)) { @unlink($abs); return; }
	foreach (array_diff(@scandir($abs) ?: Array(), Array('.', '..')) as $item) {
		$filho = $abs . DIRECTORY_SEPARATOR . $item;
		if (is_dir($filho)) admin_arquivos_remover_dir_recursivo($filho);
		else @unlink($filho);
	}
	@rmdir($abs);
}

/**
 * Cria uma nova pasta sob um diretório de destino.
 */
function admin_arquivos_ajax_pasta_criar(){
	global $_GESTOR;

	$base = admin_arquivos_base();
	$dirRel = isset($_REQUEST['dir']) ? (arquivo_caminho_relativo_seguro($_REQUEST['dir']) ?: '') : '';
	$nome = arquivo_nome_sanitizar(isset($_REQUEST['nome']) ? $_REQUEST['nome'] : '');

	if ($nome === '') {
		$_GESTOR['ajax-json'] = Array('status' => 'InvalidName');
		return;
	}

	$relNova = ($dirRel === '' ? '' : $dirRel . '/') . $nome;
	$absNova = arquivo_caminho_resolver($base, $relNova);
	if ($absNova === false) {
		$_GESTOR['ajax-json'] = Array('status' => 'Invalid');
		return;
	}

	if (is_dir($absNova)) {
		$_GESTOR['ajax-json'] = Array('status' => 'Exists');
		return;
	}

	admin_arquivos_criar_dir_herdando_permissao($absNova);

	$mtime = @filemtime($absNova) ?: time();
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'pasta' => Array(
			'nome' => $nome,
			'caminho' => $relNova,
			'mtime' => $mtime,
			'data' => interface_formatar_dado(Array('dado' => date('Y-m-d H:i:s', $mtime), 'formato' => 'dataHora')),
		),
	);
}

/**
 * Renomeia uma pasta ou arquivo, movendo as associações de categoria.
 */
function admin_arquivos_ajax_renomear(){
	global $_GESTOR;

	$base = admin_arquivos_base();
	$relSeguro = arquivo_caminho_relativo_seguro(isset($_REQUEST['caminho']) ? $_REQUEST['caminho'] : '');
	$nome = arquivo_nome_sanitizar(isset($_REQUEST['nome']) ? $_REQUEST['nome'] : '');

	if ($relSeguro === false || $relSeguro === '' || $nome === '') {
		$_GESTOR['ajax-json'] = Array('status' => 'Invalid');
		return;
	}

	$abs = arquivo_caminho_resolver($base, $relSeguro);
	if ($abs === false || !file_exists($abs)) {
		$_GESTOR['ajax-json'] = Array('status' => 'NotFound');
		return;
	}

	$ehPasta = is_dir($abs);

	// Preserva a extensão de arquivos quando o novo nome não a trouxer.
	if (!$ehPasta) {
		$extAntiga = pathinfo($relSeguro, PATHINFO_EXTENSION);
		if ($extAntiga !== '' && strtolower(pathinfo($nome, PATHINFO_EXTENSION)) !== strtolower($extAntiga)) {
			$nome .= '.' . $extAntiga;
		}
		if (arquivo_extensao_perigosa($nome)) {
			$_GESTOR['ajax-json'] = Array('status' => 'Invalid');
			return;
		}
	}

	$dirPai = dirname($relSeguro);
	$relNovo = ($dirPai === '.' || $dirPai === '') ? $nome : $dirPai . '/' . $nome;
	$absNovo = arquivo_caminho_resolver($base, $relNovo);
	if ($absNovo === false) {
		$_GESTOR['ajax-json'] = Array('status' => 'Invalid');
		return;
	}
	if (file_exists($absNovo)) {
		$_GESTOR['ajax-json'] = Array('status' => 'Exists');
		return;
	}

	if (!@rename($abs, $absNovo)) {
		$_GESTOR['ajax-json'] = Array('status' => 'Error');
		return;
	}

	// Renomeia a miniatura de um arquivo de imagem, se houver.
	if (!$ehPasta && arquivo_tipo_por_extensao($relSeguro) === 'image') {
		$absMiniAntiga = $base . arquivo_mini_caminho_relativo($relSeguro);
		$absMiniNova = $base . arquivo_mini_caminho_relativo($relNovo);
		if (is_file($absMiniAntiga)) {
			admin_arquivos_criar_dir_herdando_permissao(dirname($absMiniNova));
			@rename($absMiniAntiga, $absMiniNova);
		}
	}

	admin_arquivos_categorias_mover($relSeguro, $relNovo, $ehPasta);

	$dados = $ehPasta
		? Array('nome' => $nome, 'caminho' => $relNovo)
		: admin_arquivos_arquivo_dados($relNovo, $absNovo);

	$_GESTOR['ajax-json'] = array_merge(Array('status' => 'Ok', 'tipo' => $ehPasta ? 'pasta' : 'arquivo'), Array('item' => $dados));
}

/**
 * Lê ou define as categorias de um caminho (arquivo).
 */
function admin_arquivos_ajax_categorias_arquivo(){
	global $_GESTOR;

	$relSeguro = arquivo_caminho_relativo_seguro(isset($_REQUEST['caminho']) ? $_REQUEST['caminho'] : '');
	if ($relSeguro === false || $relSeguro === '') {
		$_GESTOR['ajax-json'] = Array('status' => 'Invalid');
		return;
	}

	if (isset($_REQUEST['categorias'])) {
		$categorias = $_REQUEST['categorias'] === '' ? Array() : explode(',', banco_escape_field($_REQUEST['categorias']));
		admin_arquivos_categorias_definir($relSeguro, $categorias);
		$_GESTOR['ajax-json'] = Array('status' => 'Ok');
	} else {
		$_GESTOR['ajax-json'] = Array('status' => 'Ok', 'categorias' => admin_arquivos_categorias_do_caminho($relSeguro));
	}
}

// ==== Start

function admin_arquivos_start(){
	global $_GESTOR;

	gestor_incluir_bibliotecas();

	if($_GESTOR['ajax']){
		interface_ajax_iniciar();

		switch($_GESTOR['ajax-opcao']){
			// 'navegar' (não 'listar'): 'listar' é reservado por interface_ajax_finalizar() (CRUD genérico).
			case 'navegar': admin_arquivos_ajax_listar(); break;
			case 'uploadFile': admin_arquivos_ajax_upload_file(); break;
			case 'miniaturas': admin_arquivos_ajax_miniaturas(); break;
			case 'excluir': admin_arquivos_ajax_excluir(); break;
			case 'pasta-criar': admin_arquivos_ajax_pasta_criar(); break;
			case 'renomear': admin_arquivos_ajax_renomear(); break;
			case 'categorias-arquivo': admin_arquivos_ajax_categorias_arquivo(); break;
		}

		interface_ajax_finalizar();
	} else {
		admin_arquivos_interfaces_padroes();

		interface_iniciar();

		switch($_GESTOR['opcao']){
			case 'upload': admin_arquivos_upload(); break;
			case 'listar-arquivos': admin_arquivos_listar_arquivos(); break;
		}

		interface_finalizar();
	}
}

admin_arquivos_start();

?>
