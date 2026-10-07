<?php

/**
 * Páginas de Lousa (REQ-253): publica uma lousa do sistema (REQ-251) como página.
 *
 * Mistura de três módulos:
 *   - `publisher-pages`: o registro é uma página na tabela `paginas` (nome, caminho, layout, acesso, status,
 *     mapa do site), com uma tabela de apoio (`dashboard_pages`) que guarda o vínculo;
 *   - `publisher-index`: controles de exibição num esquema JSON e modelo escolhido no cadastro de modelos
 *     (alvo `dashboard-pages`), no lugar de publicador e campos variáveis;
 *   - `admin-paginas`: a página criada é uma página comum e continua editável lá.
 *
 * O editor HTML (REQ-254) guarda o HTML daquela página com o lugar da lousa e o lugar do título, como o
 * `publisher-pages` guarda o modelo com as variáveis. Escolher um modelo carrega o HTML e o CSS dele no
 * editor. A página publicada sai do HTML do editor mais os controles: o lugar da lousa vira o marcador do
 * widget "Lousa" (REQ-252), que recebe os controles como parâmetros.
 */

global $_GESTOR;

$_GESTOR['modulo-id']							=	'dashboard-pages';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	json_decode(file_get_contents(__DIR__ . '/dashboard-pages.json'), true);

// ===== Controles de exibição

/** Controles de uma página nova: tudo à mostra, modo da própria lousa. */
function dashboard_pages_schema_padrao(){
	return Array(
		'show_title' => true,
		'title' => '',
		'show_item_titles' => true,
		'show_frames' => true,
		'show_backgrounds' => true,
		'show_objects' => true,
		'mode' => 'auto',
	);
}

/** Só as chaves conhecidas, cada uma no tipo e na lista dela. Aceita o array ou a string JSON gravada. */
function dashboard_pages_schema_normalizar($entrada){
	if(is_string($entrada)) $entrada = json_decode($entrada, true);
	if(!is_array($entrada)) $entrada = Array();
	$saida = dashboard_pages_schema_padrao();
	foreach(Array('show_title', 'show_item_titles', 'show_frames', 'show_backgrounds', 'show_objects') as $chave){
		if(array_key_exists($chave, $entrada)) $saida[$chave] = filter_var($entrada[$chave], FILTER_VALIDATE_BOOLEAN);
	}
	$saida['title'] = mb_substr(trim((string)preg_replace('/\s+/u', ' ', (string)($entrada['title'] ?? ''))), 0, 160);
	$saida['mode'] = in_array((string)($entrada['mode'] ?? ''), Array('grade', 'lousa'), true) ? (string)$entrada['mode'] : 'auto';

	return $saida;
}

/** Controles vindos do formulário: chave marcada vem no pedido, desmarcada não vem. */
function dashboard_pages_schema_do_pedido($pedido){
	$entrada = Array('title' => $pedido['title'] ?? '', 'mode' => $pedido['mode'] ?? '');
	foreach(Array('show_title', 'show_item_titles', 'show_frames', 'show_backgrounds', 'show_objects') as $chave){
		$entrada[$chave] = !empty($pedido[$chave]);
	}

	return dashboard_pages_schema_normalizar($entrada);
}

// ===== Montagem da página

/** Caminho da página: minúsculas sem acento, segmentos de letras, números e hífen, com barra no fim. */
function dashboard_pages_caminho_normalizar($valor){
	$valor = strtr((string)$valor, Array(
		'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
		'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
		'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e', 'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
		'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o', 'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ç' => 'c', 'Ñ' => 'n',
	));
	$partes = Array();
	foreach(explode('/', strtolower($valor)) as $parte){
		$parte = trim((string)preg_replace('/[^a-z0-9]+/', '-', $parte), '-');
		if($parte !== '') $partes[] = $parte;
	}
	$caminho = implode('/', $partes);
	if($caminho === '' || strlen($caminho) > 200) return '';

	return $caminho.'/';
}

/** Marcador do widget "Lousa" com os controles como parâmetros; só vai o que foge do padrão. */
function dashboard_pages_marcador($lousa_id, $schema){
	$schema = dashboard_pages_schema_normalizar($schema);
	$params = Array('grupo_slug' => (string)$lousa_id, 'id' => (string)$lousa_id);
	foreach(Array('show_item_titles' => 'titulos', 'show_frames' => 'molduras', 'show_backgrounds' => 'fundos', 'show_objects' => 'objetos') as $chave => $parametro){
		if(!$schema[$chave]) $params[$parametro] = false;
	}
	if($schema['mode'] !== 'auto') $params['modo'] = $schema['mode'];
	$assinatura = 'widgets#dashboard->render('.json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).')';

	return '<!-- '.$assinatura.' < --><!-- '.$assinatura.' > -->';
}

/**
 * HTML da página a partir do HTML do modelo: o bloco do título fica ou sai conforme o controle, o
 * título entra escapado e o lugar da lousa recebe o marcador do widget. Modelo sem lugar para a lousa
 * ganha o marcador no fim.
 */
function dashboard_pages_html($modelo_html, $lousa_id, $schema, $nome){
	$schema = dashboard_pages_schema_normalizar($schema);
	$html = (string)$modelo_html;
	$bloco = '/<!--\s*lousa-titulo\s*<\s*-->([\s\S]*?)<!--\s*lousa-titulo\s*>\s*-->/';
	$html = $schema['show_title'] ? preg_replace($bloco, '$1', $html) : preg_replace($bloco, '', $html);
	$marcador = dashboard_pages_marcador($lousa_id, $schema);
	if(strpos($html, '[[lousa#widget]]') === false) $html .= "\n[[lousa#widget]]";
	$titulo = $schema['title'] !== '' ? $schema['title'] : trim((string)$nome);

	// Uma passada só: o título do autor não é lido de novo como marcador.
	return strtr($html, Array(
		'[[lousa#widget]]' => $marcador,
		'[[lousa#titulo]]' => htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'),
	));
}

/** Variável escrita no editor como `[[x]]` vai para o banco como `@[[x]]@`; a volta é para editar. */
function dashboard_pages_variaveis($texto, $guardar){
	global $_GESTOR;
	$v = ($_GESTOR['variavel-global'] ?? Array()) + Array('open' => '@[[', 'close' => ']]@', 'openText' => '[[', 'closeText' => ']]');
	$texto = (string)$texto;
	if($texto === '') return '';
	if($guardar){
		// Quem já está no formato do banco fica; só o texto solto é convertido.
		return preg_replace_callback('/(?<!@)'.preg_quote($v['openText'], '/').'(.+?)'.preg_quote($v['closeText'], '/').'(?!@)/', function($m) use ($v){ return $v['open'].strtolower($m[1]).$v['close']; }, $texto);
	}

	return preg_replace_callback('/'.preg_quote($v['open'], '/').'(.+?)'.preg_quote($v['close'], '/').'/', function($m) use ($v){ return $v['openText'].strtolower($m[1]).$v['closeText']; }, $texto);
}

/**
 * HTML, CSS e cabeçalho extra que valem para a página: os do editor quando vieram no pedido, os do modelo
 * quando o editor ainda não tinha iniciado.
 */
function dashboard_pages_recursos($pedido, $modelo){
	$editor = trim((string)($pedido['html'] ?? '')) !== '';
	$saida = Array('html_template' => $editor ? (string)$pedido['html'] : (string)($modelo['html'] ?? ''));
	foreach(Array('css', 'css_compiled', 'css_precompiled', 'html_extra_head') as $campo){
		$valor = ($editor && array_key_exists($campo, $pedido)) ? (string)$pedido[$campo] : (string)($modelo[$campo] ?? '');
		$saida[$campo] = dashboard_pages_variaveis($valor, true);
	}

	return $saida;
}

// ===== Consultas

function dashboard_pages_idioma(){
	global $_GESTOR;

	return banco_escape_field($_GESTOR['linguagem-codigo']);
}

function dashboard_pages_lousa($id){
	if(!preg_match('/^[a-z0-9-]{1,120}$/', (string)$id)) return null;

	return banco_select(Array(
		'unico' => true, 'tabela' => 'dashboard_boards', 'campos' => Array('id', 'name'),
		'extra' => "WHERE id='".banco_escape_field((string)$id)."' AND language='".dashboard_pages_idioma()."' AND status='A' LIMIT 1",
	)) ?: null;
}

function dashboard_pages_modelo($id){
	if(!preg_match('/^[a-zA-Z0-9_-]{1,200}$/', (string)$id)) return null;
	$campos = Array('id', 'nome', 'html', 'css', 'framework_css');
	foreach(Array('css_compiled', 'css_precompiled', 'html_extra_head') as $campo){
		if(banco_campo_existe($campo, 'templates')) $campos[] = $campo;
	}

	return banco_select(Array(
		'unico' => true, 'tabela' => 'templates', 'campos' => $campos,
		'extra' => "WHERE id='".banco_escape_field((string)$id)."' AND target='dashboard-pages' AND language='".dashboard_pages_idioma()."' AND status='A' LIMIT 1",
	)) ?: null;
}

function dashboard_pages_layout($id){
	if(!preg_match('/^[a-zA-Z0-9_-]{1,200}$/', (string)$id)) return null;

	return banco_select(Array(
		'unico' => true, 'tabela' => 'layouts', 'campos' => Array('id', 'nome', 'framework_css'),
		'extra' => "WHERE id='".banco_escape_field((string)$id)."' AND language='".dashboard_pages_idioma()."' AND status='A' LIMIT 1",
	)) ?: null;
}

/** Vínculo da página com a lousa, o modelo e os controles; nulo quando a página não é deste módulo. */
function dashboard_pages_vinculo($page_id){
	return banco_select(Array(
		'unico' => true, 'tabela' => 'dashboard_pages', 'campos' => Array('page_id', 'board_id', 'template_id', 'fields_schema', 'html_template'),
		'extra' => "WHERE page_id='".banco_escape_field((string)$page_id)."' AND language='".dashboard_pages_idioma()."' LIMIT 1",
	)) ?: null;
}

function dashboard_pages_caminho_em_uso($caminho, $menos_id = null){
	$extra = "WHERE caminho='".banco_escape_field($caminho)."' AND language='".dashboard_pages_idioma()."' AND status!='D'";
	if($menos_id !== null) $extra .= " AND id!='".banco_escape_field((string)$menos_id)."'";

	return (bool)banco_select(Array('unico' => true, 'tabela' => 'paginas', 'campos' => Array('id'), 'extra' => $extra.' LIMIT 1'));
}

// ===== Formulário

function dashboard_pages_texto($id){
	return gestor_variaveis(Array('modulo' => 'dashboard-pages', 'id' => $id));
}

function dashboard_pages_opcoes_html($linhas, $campo_id, $campo_nome, $selecionado){
	$html = '';
	foreach($linhas ?: Array() as $linha){
		$id = (string)$linha[$campo_id];
		$html .= '<option value="'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'"'.($id === (string)$selecionado ? ' selected' : '').'>'
			.htmlspecialchars((string)($linha[$campo_nome] !== '' ? $linha[$campo_nome] : $id), ENT_QUOTES, 'UTF-8').'</option>';
	}

	return $html;
}

/** Preenche as listas de lousa, modelo e layout e os controles na página do formulário. */
function dashboard_pages_formulario($valores){
	global $_GESTOR;

	$idioma = dashboard_pages_idioma();
	$lousas = banco_select(Array('tabela' => 'dashboard_boards', 'campos' => Array('id', 'name'), 'extra' => "WHERE language='".$idioma."' AND status='A' ORDER BY name ASC"));
	$modelos = banco_select(Array('tabela' => 'templates', 'campos' => Array('id', 'nome'), 'extra' => "WHERE target='dashboard-pages' AND language='".$idioma."' AND status='A' ORDER BY nome ASC"));
	// Layout de página de site: os administrativos e os de sistema não servem a uma página pública.
	$layouts = banco_select(Array('tabela' => 'layouts', 'campos' => Array('id', 'nome'), 'extra' => "WHERE language='".$idioma."' AND status='A' AND id NOT LIKE 'layout-administrativo%' ORDER BY nome ASC"));
	$schema = dashboard_pages_schema_normalizar($valores['schema'] ?? null);

	$trocas = Array(
		'#opcao-vazia#' => htmlspecialchars(dashboard_pages_texto('select-placeholder'), ENT_QUOTES, 'UTF-8'),
		'#nome#' => htmlspecialchars((string)($valores['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
		'#caminho#' => htmlspecialchars((string)($valores['caminho'] ?? ''), ENT_QUOTES, 'UTF-8'),
		'#lousas#' => dashboard_pages_opcoes_html($lousas, 'id', 'name', $valores['board_id'] ?? ''),
		'#modelos#' => dashboard_pages_opcoes_html($modelos, 'id', 'nome', $valores['template_id'] ?? ''),
		'#layouts#' => dashboard_pages_opcoes_html($layouts, 'id', 'nome', $valores['layout_id'] ?? ''),
		'#titulo#' => htmlspecialchars($schema['title'], ENT_QUOTES, 'UTF-8'),
		'#publica#' => !empty($valores['publica']) ? 'checked' : '',
		'#publica-travada#' => gestor_acesso('permissao-pagina') ? '' : 'disabled',
		'#endereco#' => htmlspecialchars(($_GESTOR['url-raiz'] ?? '/').ltrim((string)($valores['caminho'] ?? ''), '/'), ENT_QUOTES, 'UTF-8'),
		'#editar-pagina#' => htmlspecialchars(($_GESTOR['url-raiz'] ?? '/').'admin-paginas/editar/?id='.rawurlencode((string)($valores['id'] ?? '')), ENT_QUOTES, 'UTF-8'),
	);
	foreach(Array('show_title', 'show_item_titles', 'show_frames', 'show_backgrounds', 'show_objects') as $chave){
		$trocas['#'.$chave.'#'] = $schema[$chave] ? 'checked' : '';
	}
	foreach(Array('auto', 'grade', 'lousa') as $modo){
		$trocas['#mode-'.$modo.'#'] = $schema['mode'] === $modo ? 'selected' : '';
	}
	$_GESTOR['pagina'] = strtr($_GESTOR['pagina'], $trocas);
}

/** Recusa o pedido com a mensagem e volta ao formulário. */
function dashboard_pages_recusar($mensagem_id, $voltar){
	global $_GESTOR;

	interface_alerta(Array('redirect' => true, 'msg' => dashboard_pages_texto($mensagem_id)));
	gestor_redirecionar($_GESTOR['modulo-id'].'/'.$voltar);
}

/**
 * Lê e confere o que veio do formulário. Devolve os dados prontos para gravar ou, na primeira coisa
 * errada, a chave da mensagem em `erro`.
 */
function dashboard_pages_pedido($pedido, $menos_id = null){
	$nome = mb_substr(trim((string)preg_replace('/\s+/u', ' ', (string)($pedido['nome'] ?? ''))), 0, 200);
	if($nome === '') return Array('erro' => 'alert-name-required');
	$caminho = dashboard_pages_caminho_normalizar($pedido['caminho'] ?? '');
	if($caminho === '') return Array('erro' => 'alert-path-invalid');
	if(dashboard_pages_caminho_em_uso($caminho, $menos_id)) return Array('erro' => 'alert-path-exists');
	$lousa = dashboard_pages_lousa($pedido['board_id'] ?? '');
	if(!$lousa) return Array('erro' => 'alert-board-missing');
	$modelo = dashboard_pages_modelo($pedido['template_id'] ?? '');
	if(!$modelo) return Array('erro' => 'alert-template-missing');
	$layout = dashboard_pages_layout($pedido['layout_id'] ?? '');
	if(!$layout) return Array('erro' => 'alert-layout-missing');
	$schema = dashboard_pages_schema_do_pedido($pedido);
	$recursos = dashboard_pages_recursos($pedido, $modelo);

	return $recursos + Array(
		'nome' => $nome,
		'caminho' => $caminho,
		'lousa' => $lousa,
		'modelo' => $modelo,
		'layout' => $layout,
		'schema' => $schema,
		// Primeiro a lousa e o título entram nos lugares deles; só depois as outras variáveis vão para o formato do banco.
		'html' => dashboard_pages_variaveis(dashboard_pages_html($recursos['html_template'], $lousa['id'], $schema, $nome), true),
		'publica' => !empty($pedido['sem_permissao']),
	);
}

function dashboard_pages_sitemap($id, $remover = false, $caminho_antigo = null){
	gestor_incluir_biblioteca('sitemap');
	if(!function_exists('sitemap_sincronizar_por_id')) return;
	try {
		sitemap_sincronizar_por_id($id, $remover, $caminho_antigo);
	} catch (Throwable $e) {
		if(function_exists('log_disco')) log_disco('Falha ao sincronizar o sitemap: '.$e->getMessage(), 'sitemap');
	}
}

function dashboard_pages_sitemap_remover(){
	global $_GESTOR;
	if(!empty($_GESTOR['modulo-registro-id'])) dashboard_pages_sitemap($_GESTOR['modulo-registro-id'], true);
}

function dashboard_pages_sitemap_atualizar(){
	global $_GESTOR;
	if(!empty($_GESTOR['modulo-registro-id'])) dashboard_pages_sitemap($_GESTOR['modulo-registro-id']);
}

function dashboard_pages_validacao(){
	$campos = Array();
	foreach(Array('nome' => Array('texto-obrigatorio', 'form-name-label'), 'caminho' => Array('texto-obrigatorio', 'form-path-label'), 'board_id' => Array('selecao-obrigatorio', 'form-board-label'),
		'template_id' => Array('selecao-obrigatorio', 'form-template-label'), 'layout_id' => Array('selecao-obrigatorio', 'form-layout-label')) as $campo => $regra){
		$campos[] = Array('regra' => $regra[0], 'campo' => $campo, 'label' => dashboard_pages_texto($regra[1]), 'identificador' => $campo);
	}

	return $campos;
}

/** Põe o editor HTML na página, com o conteúdo que ele deve abrir. */
function dashboard_pages_editor($modo, $conteudo){
	global $_GESTOR;

	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#html-editor#', html_editor_componente(Array(
		$modo => true,
		'modulo' => $_GESTOR['modulo#'.$_GESTOR['modulo-id']],
		'alvo' => 'dashboard-pages',
		'alvos_modelos' => 'dashboard-pages',
		'layout_id' => (string)($conteudo['layout_id'] ?? ''),
	)));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-html#', (string)($conteudo['html'] ?? ''));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-css#', (string)($conteudo['css'] ?? ''));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-css-compiled#', dashboard_pages_variaveis($conteudo['css_compiled'] ?? '', false));
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '#pagina-html-extra-head#', dashboard_pages_variaveis($conteudo['html_extra_head'] ?? '', false));
}

/** O que o editor abre para uma página que já existe: o HTML guardado dela (ou o do modelo) e o estilo da página. */
function dashboard_pages_conteudo_da_pagina($page_id, $vinculo){
	$pagina = banco_select(Array(
		'unico' => true, 'tabela' => 'paginas', 'campos' => Array('layout_id', 'css', 'css_compiled', 'html_extra_head'),
		'extra' => "WHERE id='".banco_escape_field((string)$page_id)."' AND language='".dashboard_pages_idioma()."' LIMIT 1",
	)) ?: Array();
	$html = (string)($vinculo['html_template'] ?? '');
	if(trim($html) === ''){
		$modelo = dashboard_pages_modelo($vinculo['template_id'] ?? '');
		$html = (string)($modelo['html'] ?? '');
	}

	return Array('html' => $html) + $pagina;
}

/** AJAX: HTML e CSS de um modelo, para o editor. */
function dashboard_pages_ajax_template_load(){
	global $_GESTOR;

	$modelo = dashboard_pages_modelo($_REQUEST['template_id'] ?? '');
	if(!$modelo){
		$_GESTOR['ajax-json'] = Array('status' => 'Erro', 'message' => dashboard_pages_texto('alert-template-missing'));
		return;
	}
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'id' => $modelo['id'],
		'html' => (string)($modelo['html'] ?? ''),
		'css' => (string)($modelo['css'] ?? ''),
		'framework_css' => (string)($modelo['framework_css'] ?? ''),
	);
}

/** Grava a página e o vínculo dela; devolve o identificador. Usada por adicionar e por clonar. */
function dashboard_pages_inserir($dados){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$usuario = gestor_usuario();

	$id = banco_identificador(Array(
		'id' => banco_escape_field($dados['nome']),
		'tabela' => Array(
			'nome' => $modulo['tabela']['nome'],
			'campo' => $modulo['tabela']['id'],
			'id_nome' => $modulo['tabela']['id_numerico'],
			'where' => "language='".$_GESTOR['linguagem-codigo']."'",
		),
	));

	$campos = Array(
		Array('id_usuarios', (int)$usuario['id_usuarios'], true),
		Array('nome', banco_escape_field($dados['nome'])),
		Array('id', banco_escape_field($id)),
		Array('layout_id', banco_escape_field($dados['layout']['id'])),
		Array('tipo', 'pagina'),
		Array('framework_css', banco_escape_field((string)($dados['layout']['framework_css'] ?: 'tailwindcss'))),
		Array('caminho', banco_escape_field($dados['caminho'])),
		Array('html', banco_escape_field($dados['html'])),
		Array('language', banco_escape_field($_GESTOR['linguagem-codigo'])),
		Array($modulo['tabela']['status'], 'A'),
		Array($modulo['tabela']['versao'], '1', true),
		Array($modulo['tabela']['data_criacao'], 'NOW()', true),
		Array($modulo['tabela']['data_modificacao'], 'NOW()', true),
	);
	foreach(Array('css', 'css_compiled', 'css_precompiled', 'html_extra_head') as $campo){
		if($dados[$campo] !== '') $campos[] = Array($campo, banco_escape_field($dados[$campo]));
	}
	// Só quem tem a operação decide se a página abre sem login; sem ela, a página nasce restrita.
	if(gestor_acesso('permissao-pagina') && $dados['publica']) $campos[] = Array('sem_permissao', '1', true);

	banco_insert_name($campos, $modulo['tabela']['nome']);
	banco_insert_name(Array(
		Array('page_id', banco_escape_field($id)),
		Array('language', banco_escape_field($_GESTOR['linguagem-codigo'])),
		Array('board_id', banco_escape_field($dados['lousa']['id'])),
		Array('template_id', banco_escape_field($dados['modelo']['id'])),
		Array('fields_schema', banco_escape_field(json_encode($dados['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))),
		Array('html_template', banco_escape_field($dados['html_template'])),
	), 'dashboard_pages');

	dashboard_pages_sitemap($id);

	return $id;
}

// ===== Opções do módulo

function dashboard_pages_adicionar(){
	global $_GESTOR;
	$_GESTOR['tailwind-page-bundle'] = true;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	if(isset($_GESTOR['adicionar-banco'])){
		$dados = dashboard_pages_pedido($_REQUEST);
		if(isset($dados['erro'])) dashboard_pages_recusar($dados['erro'], 'adicionar/');
		$id = dashboard_pages_inserir($dados);
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	dashboard_pages_formulario(Array('publica' => true, 'schema' => dashboard_pages_schema_padrao()));
	dashboard_pages_editor('adicionarEditar', Array());
	gestor_pagina_javascript_incluir();

	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'formulario' => Array('validacao' => dashboard_pages_validacao()),
	);
}

/** Clonar: o formulário abre com os dados da página de origem, sem nome nem endereço; salvar cria outra página. */
function dashboard_pages_clonar(){
	global $_GESTOR;
	$_GESTOR['tailwind-page-bundle'] = true;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$id = $_GESTOR['modulo-registro-id'];
	$vinculo = dashboard_pages_vinculo($id);
	if(!$vinculo) gestor_redirecionar_raiz();

	if(isset($_GESTOR['adicionar-banco'])){
		$dados = dashboard_pages_pedido($_REQUEST);
		if(isset($dados['erro'])) dashboard_pages_recusar($dados['erro'], 'clonar/?'.$modulo['tabela']['id'].'='.rawurlencode((string)$id));
		$id_novo = dashboard_pages_inserir($dados);
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id_novo);
	}

	$origem = banco_select(Array(
		'unico' => true, 'tabela' => $modulo['tabela']['nome'], 'campos' => Array('layout_id', 'sem_permissao'),
		'extra' => "WHERE ".$modulo['tabela']['id']."='".banco_escape_field((string)$id)."' AND ".$modulo['tabela']['status']."!='D' AND language='".dashboard_pages_idioma()."' LIMIT 1",
	));
	if(!$origem) gestor_redirecionar_raiz();

	dashboard_pages_formulario(Array(
		'layout_id' => $origem['layout_id'] ?? '',
		'board_id' => $vinculo['board_id'],
		'template_id' => $vinculo['template_id'],
		'schema' => $vinculo['fields_schema'],
		'publica' => !empty($origem['sem_permissao']),
	));
	dashboard_pages_editor('adicionarEditar', dashboard_pages_conteudo_da_pagina($id, $vinculo));
	gestor_pagina_javascript_incluir();

	$_GESTOR['interface']['clonar']['finalizar'] = Array(
		'formulario' => Array('validacao' => dashboard_pages_validacao()),
	);
}

function dashboard_pages_editar(){
	global $_GESTOR;
	$_GESTOR['tailwind-page-bundle'] = true;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	$id = $_GESTOR['modulo-registro-id'];
	$onde = "WHERE ".$modulo['tabela']['id']."='".banco_escape_field((string)$id)."' AND ".$modulo['tabela']['status']."!='D' AND language='".$_GESTOR['linguagem-codigo']."'";

	// Página que não foi criada aqui não se edita aqui.
	$vinculo = dashboard_pages_vinculo($id);
	if(!$vinculo) gestor_redirecionar_raiz();

	$camposBanco = Array('id', 'nome', 'caminho', 'layout_id', 'sem_permissao', $modulo['tabela']['status'], $modulo['tabela']['versao'], $modulo['tabela']['data_criacao'], $modulo['tabela']['data_modificacao']);

	if(isset($_GESTOR['atualizar-banco'])){
		$antes = banco_select(Array('unico' => true, 'tabela' => $modulo['tabela']['nome'], 'campos' => Array('nome', 'caminho', 'sem_permissao'), 'extra' => $onde.' LIMIT 1'));
		if(!$antes) gestor_redirecionar_raiz();
		$dados = dashboard_pages_pedido($_REQUEST, $id);
		if(isset($dados['erro'])) dashboard_pages_recusar($dados['erro'], 'editar/?'.$modulo['tabela']['id'].'='.rawurlencode((string)$id));

		$editar = Array(
			"nome='".banco_escape_field($dados['nome'])."'",
			"caminho='".banco_escape_field($dados['caminho'])."'",
			"layout_id='".banco_escape_field($dados['layout']['id'])."'",
			"framework_css='".banco_escape_field((string)($dados['layout']['framework_css'] ?: 'tailwindcss'))."'",
			"html='".banco_escape_field($dados['html'])."'",
		);
		foreach(Array('css', 'css_compiled', 'css_precompiled', 'html_extra_head') as $campo){
			$editar[] = $campo.'='.($dados[$campo] === '' ? 'NULL' : "'".banco_escape_field($dados[$campo])."'");
		}
		if(gestor_acesso('permissao-pagina')) $editar[] = 'sem_permissao='.($dados['publica'] ? '1' : 'NULL');
		$editar[] = 'user_modified=1';
		$editar[] = $modulo['tabela']['versao'].'='.$modulo['tabela']['versao'].'+1';
		$editar[] = $modulo['tabela']['data_modificacao'].'=NOW()';
		banco_update(banco_campos_virgulas($editar), $modulo['tabela']['nome'], $onde);

		banco_update(banco_campos_virgulas(Array(
			"board_id='".banco_escape_field($dados['lousa']['id'])."'",
			"template_id='".banco_escape_field($dados['modelo']['id'])."'",
			"fields_schema='".banco_escape_field(json_encode($dados['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))."'",
			"html_template='".banco_escape_field($dados['html_template'])."'",
		)), 'dashboard_pages', "WHERE page_id='".banco_escape_field((string)$id)."' AND language='".dashboard_pages_idioma()."'");

		$alteracoes = Array();
		if($antes['nome'] !== $dados['nome']) $alteracoes[] = Array('campo' => 'form-name-label', 'valor_antes' => banco_escape_field($antes['nome']), 'valor_depois' => banco_escape_field($dados['nome']));
		if($antes['caminho'] !== $dados['caminho']) $alteracoes[] = Array('campo' => 'form-path-label', 'valor_antes' => banco_escape_field((string)$antes['caminho']), 'valor_depois' => banco_escape_field($dados['caminho']));
		if(!$alteracoes) $alteracoes[] = Array('campo' => 'form-controls-label');
		interface_historico_incluir(Array(
			'id' => $id,
			'tabela' => Array('nome' => $modulo['tabela']['nome'], 'id_numerico' => $modulo['tabela']['id_numerico'], 'versao' => $modulo['tabela']['versao']),
			'alteracoes' => $alteracoes,
		));

		dashboard_pages_sitemap($id, false, $antes['caminho'] !== $dados['caminho'] ? (string)$antes['caminho'] : null);
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}

	$retorno_bd = banco_select_editar(banco_campos_virgulas($camposBanco), $modulo['tabela']['nome'], $onde);
	if(!$_GESTOR['banco-resultado']) gestor_redirecionar_raiz();

	dashboard_pages_formulario(Array(
		'id' => $id,
		'nome' => $retorno_bd['nome'] ?? '',
		'caminho' => $retorno_bd['caminho'] ?? '',
		'layout_id' => $retorno_bd['layout_id'] ?? '',
		'board_id' => $vinculo['board_id'],
		'template_id' => $vinculo['template_id'],
		'schema' => $vinculo['fields_schema'],
		'publica' => !empty($retorno_bd['sem_permissao']),
	));
	dashboard_pages_editor('editar', dashboard_pages_conteudo_da_pagina($id, $vinculo));
	gestor_pagina_javascript_incluir();

	$status_atual = (string)($retorno_bd[$modulo['tabela']['status']] ?? '');
	$metaDados = Array();
	if(isset($retorno_bd[$modulo['tabela']['data_criacao']])) $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'field-date-start')), 'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora')));
	if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])) $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'field-date-modification')), 'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora')));
	if(isset($retorno_bd[$modulo['tabela']['versao']])) $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'field-version')), 'dado' => $retorno_bd[$modulo['tabela']['versao']]);
	if($status_atual !== '') $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'field-status')), 'dado' => interface_status_selo($status_atual));

	$raiz = $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/';
	$parametro = $modulo['tabela']['id'].'='.rawurlencode((string)$id);
	$ativa = $status_atual === 'A';
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
		'botoes' => Array(
			'adicionar' => Array(
				'url' => $raiz.'adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'clonar' => Array(
				'url' => $raiz.'clonar/?'.$parametro,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'label-button-clone')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-clone')),
				'icon' => 'clone',
				'cor' => 'teal',
			),
			'status' => Array(
				'url' => $raiz.'?opcao=status&'.$modulo['tabela']['status'].'='.($ativa ? 'I' : 'A').'&'.$parametro.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$parametro),
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => $ativa ? 'label-button-desactive' : 'label-button-active')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => $ativa ? 'tooltip-button-desactive' : 'tooltip-button-active')),
				'icon' => $ativa ? 'eye' : 'eye slash',
				'cor' => $ativa ? 'green' : 'brown',
			),
			'excluir' => Array(
				'url' => $raiz.'?opcao=excluir&'.$parametro,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'label-button-delete')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'cor' => 'red',
			),
		),
		'formulario' => Array('validacao' => dashboard_pages_validacao()),
	);
}

function dashboard_pages_interfaces_padroes(){
	global $_GESTOR;

	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	// Excluir e mudar status valem para a página; o mapa do site acompanha.
	$_GESTOR['interface']['status']['finalizar']['callbackFunction'] = 'dashboard_pages_sitemap_atualizar';
	$_GESTOR['interface']['excluir']['finalizar']['callbackFunction'] = 'dashboard_pages_sitemap_remover';

	if($_GESTOR['opcao'] !== 'listar') return;

	$_GESTOR['tailwind-page-bundle'] = true;
	$idioma = dashboard_pages_idioma();
	$_GESTOR['interface']['listar']['finalizar'] = Array(
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'campos' => Array('nome', 'caminho', $modulo['tabela']['data_modificacao']),
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
			// Só as páginas criadas por este módulo.
			'where' => "language='".$idioma."' AND id IN (SELECT page_id FROM dashboard_pages WHERE language='".$idioma."')",
		),
		'tabela' => Array(
			'colunas' => Array(
				Array('id' => 'nome', 'nome' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'field-name')), 'ordenar' => 'asc'),
				Array('id' => 'caminho', 'nome' => dashboard_pages_texto('form-path-label')),
				Array('id' => $modulo['tabela']['data_modificacao'], 'nome' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'field-date-modification')), 'formatar' => 'dataHora', 'nao_procurar' => true),
			),
		),
		'opcoes' => Array(
			'editar' => Array('url' => 'editar/', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-edit')), 'icon' => 'edit', 'cor' => 'basic blue'),
			'clonar' => Array('url' => 'clonar/', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-clone')), 'icon' => 'clone', 'cor' => 'basic teal'),
			'ativar' => Array('opcao' => 'status', 'status_atual' => 'I', 'status_mudar' => 'A', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-active')), 'icon' => 'eye slash', 'cor' => 'basic brown'),
			'desativar' => Array('opcao' => 'status', 'status_atual' => 'A', 'status_mudar' => 'I', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-desactive')), 'icon' => 'eye', 'cor' => 'basic green'),
			'excluir' => Array('opcao' => 'excluir', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-delete')), 'icon' => 'trash alternate', 'cor' => 'basic red'),
		),
		'botoes' => Array(
			'adicionar' => Array(
				'url' => 'adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface', 'id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
		),
	);
}

function dashboard_pages_start(){
	global $_GESTOR;

	gestor_incluir_bibliotecas();

	if($_GESTOR['ajax']){
		interface_ajax_iniciar();

		switch($_GESTOR['ajax-opcao']){
			case 'template-load': dashboard_pages_ajax_template_load(); break;
		}

		interface_ajax_finalizar();
	} else {
		dashboard_pages_interfaces_padroes();

		interface_iniciar();

		switch($_GESTOR['opcao']){
			case 'adicionar': dashboard_pages_adicionar(); break;
			case 'editar': dashboard_pages_editar(); break;
			case 'clonar': dashboard_pages_clonar(); break;
		}

		interface_finalizar();
	}
}

dashboard_pages_start();
