<?php
/**
 * Widget do módulo presentations (req-208): apresentação em slides.
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando a página contém:
 *   <!-- widgets#presentations->render({"grupo_slug": "..."}) < -->
 *     ...mockup estático...
 *   <!-- widgets#presentations->render({"grupo_slug": "..."}) > -->
 *
 * O autor escreve só os slides: cada `<section data-slide>` do HTML do registro é um slide. O que
 * depende da quantidade de slides ou das opções sai daqui:
 *   - <!-- controls-arrows < --> ... <!-- controls-arrows > -->         (setas; pode aparecer mais de uma vez)
 *   - <!-- controls-dots < --> ... <!-- controls-dots > -->             (pontos)
 *   - <!-- dot-item < --> ... <!-- dot-item > -->                       (um ponto por slide)
 *   - <!-- controls-counter < --> ... <!-- controls-counter > -->       (contador "3 / 8")
 *   - <!-- controls-progress < --> ... <!-- controls-progress > -->     (barra de progresso)
 *   - <!-- controls-fullscreen < --> ... <!-- controls-fullscreen > --> (botão de tela cheia)
 * Variáveis por ponto: [[dot#index]] (0, 1, 2…) e [[dot#number]] (1, 2, 3…).
 * Variáveis globais: [[total]], [[mode]], [[height]], [[transition]], [[loop]], [[keyboard]], [[touch]],
 * [[hash]], [[autoplay]], [[autoplay_speed]] e [[preview]].
 *
 * O comportamento (navegação, teclado, toque, tela cheia, escala, impressão) é do controlador
 * público `presentations.widget.js`, que lê as opções dos atributos `data-*` do contêiner.
 */

// ===== Funções Auxiliares

function presentations_get_version(){
	$modulo = json_decode(file_get_contents(__DIR__ . '/presentations.json'), true);

	return $modulo['asset_version'] ?? $modulo['versao'] ?? '1.0.0';
}

function presentations_transitions(){
	return ['random', 'fade', 'zoom', 'slide-up', 'slide-horizontal'];
}

function presentations_schema_default(){
	return [
		'template_id' => '',
		// fullscreen: ocupa a janela inteira. embedded: bloco com a altura de `height`, dentro da página.
		'mode' => 'fullscreen',
		'height' => 600,
		'transition' => 'random',
		'loop' => false,
		'keyboard' => true,
		'touch' => true,
		// Guarda o slide atual no endereço (#slide-3), para apontar um slide de fora.
		'hash' => true,
		'show_arrows' => true,
		'show_dots' => true,
		'show_counter' => true,
		'show_progress' => true,
		'show_fullscreen' => true,
		'autoplay' => false,
		'autoplay_speed' => 8000,
	];
}

function presentations_widget_bool($valor){
	if(is_bool($valor)) return $valor;
	if(is_int($valor)) return $valor !== 0;
	$valor = strtolower(trim((string)$valor));
	return ($valor === 'true' || $valor === '1' || $valor === 'yes' || $valor === 'on');
}

/**
 * Normaliza o `fields_schema`: completa com os padrões, descarta chave desconhecida e valor fora da
 * faixa. Aceita a string JSON do banco ou o array já decodificado.
 */
function presentations_schema_decode($fields_schema, $template_id = ''){
	$schema = is_array($fields_schema) ? $fields_schema : json_decode($fields_schema ?: '{}', true);
	if(!is_array($schema)) $schema = [];

	$padrao = presentations_schema_default();
	$saida = [];
	foreach($padrao as $chave => $valorPadrao){
		$valor = array_key_exists($chave, $schema) ? $schema[$chave] : $valorPadrao;
		if(is_bool($valorPadrao)) $valor = presentations_widget_bool($valor);
		elseif(is_int($valorPadrao)) $valor = (int)$valor;
		else $valor = (string)$valor;
		$saida[$chave] = $valor;
	}

	if(!in_array($saida['mode'], ['fullscreen', 'embedded'], true)) $saida['mode'] = $padrao['mode'];
	if(!in_array($saida['transition'], presentations_transitions(), true)) $saida['transition'] = $padrao['transition'];
	if($saida['height'] < 200) $saida['height'] = $padrao['height'];
	if($saida['autoplay_speed'] < 1000) $saida['autoplay_speed'] = $padrao['autoplay_speed'];
	if($saida['template_id'] === '' && $template_id !== '') $saida['template_id'] = (string)$template_id;

	return $saida;
}

/**
 * Quantidade de slides: seções com o atributo `data-slide`. Comentários saem da conta, porque um
 * comentário que cita `<section data-slide>` não é slide (e o navegador também não o conta).
 */
function presentations_widget_contar_slides($html){
	$html = preg_replace('/<!--[\s\S]*?-->/', '', (string)$html);

	return (int)preg_match_all('/<section\b[^>]*\sdata-slide(?=[\s=>\/])/i', (string)$html);
}

/** preg_replace com substituição literal: o HTML pode conter `$1` ou `\1`. */
function presentations_widget_preg_replace_literal($pattern, $replacement, $subject, $limit = -1){
	return preg_replace_callback($pattern, function() use ($replacement){
		return $replacement;
	}, $subject, $limit);
}

/** Mantém o conteúdo do bloco quando `$manter`; senão remove o bloco inteiro. Vale para todas as ocorrências. */
function presentations_widget_bloco($html, $nome, $manter){
	$padrao = '/<!--\s*'.preg_quote($nome, '/').'\s*<\s*-->([\s\S]*?)<!--\s*'.preg_quote($nome, '/').'\s*>\s*-->/i';
	if($manter){
		return preg_replace_callback($padrao, function($m){ return $m[1]; }, $html);
	}
	return preg_replace($padrao, '', $html);
}

/** Repete o bloco `dot-item` uma vez por slide. */
function presentations_widget_pontos($html, $total){
	$padrao = '/<!--\s*dot-item\s*<\s*-->([\s\S]*?)<!--\s*dot-item\s*>\s*-->/i';

	return preg_replace_callback($padrao, function($m) use ($total){
		$pontos = '';
		for($i = 0; $i < $total; $i++){
			$ponto = str_replace(['@[[dot#index]]@', '[[dot#index]]'], (string)$i, $m[1]);
			$ponto = str_replace(['@[[dot#number]]@', '[[dot#number]]'], (string)($i + 1), $ponto);
			$pontos .= $ponto;
		}
		return $pontos;
	}, $html);
}

/** Resolve as variáveis globais `[[nome]]`, com ou sem as arrobas do banco. Desconhecidas ficam como estão. */
function presentations_widget_resolver_globais($html, $schema, $total, $preview){
	$bool = function($chave) use ($schema){ return !empty($schema[$chave]) ? 'true' : 'false'; };

	$map = [
		'total' => (string)$total,
		'mode' => $schema['mode'],
		'height' => (string)$schema['height'],
		'transition' => $schema['transition'],
		'loop' => $bool('loop'),
		'keyboard' => $bool('keyboard'),
		'touch' => $bool('touch'),
		'hash' => $bool('hash'),
		'autoplay' => $bool('autoplay'),
		'autoplay_speed' => (string)$schema['autoplay_speed'],
		'preview' => $preview ? 'true' : 'false',
	];

	return preg_replace_callback('/@?\[\[([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($map){
		return array_key_exists($m[1], $map) ? $map[$m[1]] : $m[0];
	}, $html);
}

// ===== Funções Principais

function presentations_render($params){
	global $_GESTOR;

	if(!is_array($params)) return '';

	$grupo_slug = $params['grupo_slug'] ?? null;
	if(empty($grupo_slug)) return '';

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'presentations',
		'campos' => Array('id_presentations', 'id', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head'),
		'extra' =>
			"WHERE id='".banco_escape_field($grupo_slug)."'"
			." AND status='A'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
	));

	// O mockup do arquivo da página não vale como conteúdo: sem registro, nada é exibido.
	if(!$registro || trim((string)($registro['html'] ?? '')) === '') return '';

	gestor_pagina_javascript_incluir(Array(
		'tipo' => 'widget',
		'modulo_id' => 'presentations',
		'versao' => presentations_get_version(),
	));

	return presentations_widget_render_inline([
		'html' => $registro['html'],
		'css' => $registro['css'] ?? '',
		'css_compiled' => $registro['css_compiled'] ?? '',
		'html_extra_head' => $registro['html_extra_head'] ?? '',
		'fields_schema' => $registro['fields_schema'] ?? '{}',
	]);
}

/**
 * Renderiza a apresentação a partir de entradas cruas, sem ler a tabela. Usado pelo widget (depois
 * da consulta) e pela pré-visualização do painel.
 *
 * @param array $params ['html', 'css', 'css_compiled', 'html_extra_head', 'fields_schema', 'preview']
 * @return string HTML do deck, ou vazio sem HTML.
 */
function presentations_widget_render_inline($params){
	$html = (string)($params['html'] ?? '');
	if(trim($html) === '') return '';

	$schema = presentations_schema_decode($params['fields_schema'] ?? '{}');
	$total = presentations_widget_contar_slides($html);
	$preview = !empty($params['preview']);

	// Com um slide só não há para onde navegar: os controles de navegação saem.
	$navegavel = $total > 1;
	$html = presentations_widget_bloco($html, 'controls-arrows', $navegavel && $schema['show_arrows']);
	$html = presentations_widget_bloco($html, 'controls-counter', $navegavel && $schema['show_counter']);
	$html = presentations_widget_bloco($html, 'controls-progress', $navegavel && $schema['show_progress']);
	$html = presentations_widget_bloco($html, 'controls-fullscreen', $schema['show_fullscreen']);

	$mostrarPontos = $navegavel && $schema['show_dots'];
	if($mostrarPontos) $html = presentations_widget_pontos($html, $total);
	$html = presentations_widget_bloco($html, 'controls-dots', $mostrarPontos);

	$html = presentations_widget_resolver_globais($html, $schema, $total, $preview);

	if(function_exists('gestor_pagina_recursos_incluir')){
		gestor_pagina_recursos_incluir(Array(
			'css' => (string)($params['css'] ?? ''),
			'css_compiled' => (string)($params['css_compiled'] ?? ''),
			'html_extra_head' => (string)($params['html_extra_head'] ?? ''),
		));
	}

	return $html;
}
