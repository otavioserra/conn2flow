<?php
/**
 * Widget "Lousa" (REQ-252): renderiza uma lousa do sistema (REQ-251) dentro de uma página.
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando a página contiver:
 *   <!-- widgets#dashboard->render({"grupo_slug": "..."}) < --> ... <!-- widgets#dashboard->render({"grupo_slug": "..."}) > -->
 *
 * Cada widget da lousa é renderizado pelo sistema de widgets da própria página, sem iframe, e os
 * objetos livres (texto, forma, imagem, ícone, botão) saem dos moldes do componente
 * `dashboard-lousa-widget`. A página é só leitura: quem edita a lousa continua no Dashboard.
 *
 * O que o autor escreveu entra como texto escapado; cor, imagem, destino, fonte e ícone passam de novo
 * pela normalização de `dashboard-layout.php` antes de chegar a um atributo.
 */

require_once __DIR__.'/dashboard-layout.php';

function dashboard_widget_lousa_versao(){
	$modulo = json_decode((string)file_get_contents(__DIR__.'/dashboard.json'), true);

	return $modulo['asset_version'] ?? $modulo['versao'] ?? '1.0.0';
}

/** Conteúdo entre `<!-- nome < -->` e `<!-- nome > -->` no HTML do componente; vazio se não houver. */
function dashboard_widget_lousa_bloco($html, $nome){
	$abre = '<!-- '.$nome.' < -->';
	$fecha = '<!-- '.$nome.' > -->';
	$inicio = strpos($html, $abre);
	$fim = $inicio === false ? false : strpos($html, $fecha, $inicio);
	if($inicio === false || $fim === false) return '';

	return substr($html, $inicio + strlen($abre), $fim - $inicio - strlen($abre));
}

/** Folha do Google Fonts com as famílias em uso. Bebas Neue só tem um peso: pedir 700 invalida a folha inteira. */
function dashboard_widget_lousa_fontes_url($fontes){
	$fontes = array_values(array_intersect(dashboard_widgets_fontes(), array_keys($fontes)));
	if(!$fontes) return '';
	sort($fontes);
	$familias = Array();
	foreach($fontes as $fonte){
		$familias[] = 'family='.str_replace('%20', '+', rawurlencode($fonte)).($fonte === 'Bebas Neue' ? '' : ':wght@400;700');
	}

	return 'https://fonts.googleapis.com/css2?'.implode('&', $familias).'&display=swap';
}

/** Declarações de estilo a partir de valores já validados; o resultado ainda é escapado como atributo. */
function dashboard_widget_lousa_estilo($pares){
	$saida = '';
	foreach($pares as $propriedade => $valor){
		if($valor === '' || $valor === null) continue;
		$saida .= $propriedade.':'.$valor.';';
	}

	return htmlspecialchars($saida, ENT_QUOTES, 'UTF-8');
}

function dashboard_widget_lousa_familia($fonte){
	return $fonte !== '' ? "'".$fonte."',sans-serif" : '';
}

/**
 * HTML de um objeto livre a partir do molde do tipo. Devolve vazio quando não há o que mostrar
 * (texto ou botão sem texto, imagem sem arquivo): na página não existe o aviso de objeto vazio.
 */
function dashboard_widget_lousa_objeto($objeto, $moldes, &$fontes, &$icones){
	$o = dashboard_widgets_objeto_normalizar($objeto);
	$texto = htmlspecialchars($o['text'], ENT_QUOTES, 'UTF-8');
	if($o['font'] !== '' && in_array($o['type'], Array('text', 'button'), true)) $fontes[$o['font']] = true;
	$letra = Array('font-family' => dashboard_widget_lousa_familia($o['font']), 'font-size' => $o['size'].'px', 'font-weight' => $o['weight']);

	switch($o['type']){
		case 'text':
			if(trim($o['text']) === '') return '';
			return strtr(dashboard_widget_lousa_bloco($moldes, 'objeto-texto'), Array(
				'#estilo#' => dashboard_widget_lousa_estilo($letra + Array('text-align' => $o['align'], 'color' => $o['color'])),
				'#texto#' => $texto,
			));
		case 'shape':
			return strtr(dashboard_widget_lousa_bloco($moldes, 'objeto-forma'), Array(
				'#forma#' => $o['shape'],
				'#estilo#' => dashboard_widget_lousa_estilo(Array('background-color' => $o['fill'])),
			));
		case 'image':
			if($o['src'] === '') return '';
			return strtr(dashboard_widget_lousa_bloco($moldes, 'objeto-imagem'), Array(
				'#src#' => htmlspecialchars($o['src'], ENT_QUOTES, 'UTF-8'),
				'#alt#' => htmlspecialchars($o['alt'], ENT_QUOTES, 'UTF-8'),
				'#estilo#' => dashboard_widget_lousa_estilo(Array('object-fit' => $o['fit'])),
			));
		case 'icon':
			$icones = true;
			return strtr(dashboard_widget_lousa_bloco($moldes, 'objeto-icone'), Array(
				'#icone#' => $o['icon'],
				'#estilo#' => dashboard_widget_lousa_estilo(Array('color' => $o['color'])),
			));
		default:
			if(trim($o['text']) === '') return '';
			$estilo = dashboard_widget_lousa_estilo($letra + Array('background-color' => $o['fill'], 'color' => $o['color']));
			// Botão sem destino aparece como rótulo, sem levar a lugar nenhum.
			if($o['href'] === '') return strtr(dashboard_widget_lousa_bloco($moldes, 'objeto-rotulo'), Array('#estilo#' => $estilo, '#texto#' => $texto));
			return strtr(dashboard_widget_lousa_bloco($moldes, 'objeto-botao'), Array(
				'#href#' => htmlspecialchars($o['href'], ENT_QUOTES, 'UTF-8'),
				'#alvo#' => $o['newTab'] ? '_blank' : '_self',
				'#rel#' => $o['newTab'] ? 'noopener' : '',
				'#estilo#' => $estilo,
				'#texto#' => $texto,
			));
	}
}

/** Largura na faixa intermediária (grade de 6 colunas), como no Dashboard: 2 e 3 → 2; 4 e 5 → 3; de 6 em diante → 6. */
function dashboard_widget_lousa_largura_media($largura){
	return $largura <= 3 ? 2 : ($largura <= 5 ? 3 : 6);
}

function dashboard_render($params){
	global $_GESTOR;
	// Lousa dentro de lousa não renderiza: evita laço e página sem fim.
	static $dentro = false;

	if($dentro || !is_array($params)) return '';
	$id = (string)($params['grupo_slug'] ?? $params['id'] ?? '');
	if(!preg_match('/^[a-z0-9-]{1,120}$/', $id)) return '';

	$idioma = banco_escape_field($_GESTOR['linguagem-codigo']);
	$lousa = banco_select(Array(
		'unico' => true, 'tabela' => 'dashboard_boards', 'campos' => Array('id', 'mode', 'layout'),
		'extra' => "WHERE id='".banco_escape_field($id)."' AND language='".$idioma."' AND status='A' LIMIT 1",
	));
	if(!$lousa) return '';
	$itens = dashboard_widgets_layout_normalizar($lousa['layout'] ?? null);
	if(!$itens) return '';

	// Só entra widget do cadastro: a assinatura chama `<módulo>_render`, e o módulo vem do layout gravado.
	$cadastrados = Array();
	foreach(banco_select(Array('tabela' => 'widgets', 'campos' => Array('id'), 'extra' => "WHERE status='A' AND language='".$idioma."'")) ?: Array() as $widget){
		$cadastrados[(string)$widget['id']] = true;
	}

	$componente = (string)gestor_componente(Array('id' => 'dashboard-lousa-widget', 'modulo' => 'dashboard'));
	$molde = dashboard_widget_lousa_bloco($componente, 'item');
	$moldeTitulo = dashboard_widget_lousa_bloco($molde, 'titulo');
	$moldes = dashboard_widget_lousa_bloco($componente, 'objetos');
	if($molde === '') return '';
	// Uma passada só por item: o que entra no lugar de um marcador não é lido de novo, então texto do
	// autor ou HTML de widget que contenha um marcador sai como está.
	$celula = str_replace('<!-- titulo < -->'.$moldeTitulo.'<!-- titulo > -->', '#bloco-titulo#', $molde);

	gestor_incluir_biblioteca('widgets');
	$fontes = Array();
	$icones = false;
	$saida = '';
	foreach($itens as $item){
		$o = $item['options'];
		$objeto = $item['id'] === 'objeto';
		if($objeto){
			$conteudo = dashboard_widget_lousa_objeto($item['object'] ?? null, $moldes, $fontes, $icones);
		} else {
			if($item['id'] === 'dashboard' || !isset($cadastrados[$item['id']]) || $item['registro_id'] === '') continue;
			$assinatura = $item['id'].'->render('.json_encode(Array('grupo_slug' => $item['registro_id'], 'id' => $item['registro_id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).')';
			$dentro = true;
			try {
				$conteudo = (string)widgets_get(Array('id' => $assinatura));
			} finally {
				$dentro = false;
			}
		}
		if(trim($conteudo) === '') continue;

		// Na página o título só aparece quando o autor escreveu um; o nome do widget é coisa do painel.
		$titulo = '';
		if($o['header'] && $o['title'] !== ''){
			if($o['titleFont'] !== '') $fontes[$o['titleFont']] = true;
			$titulo = strtr($moldeTitulo, Array(
				'#titulo-estilo#' => dashboard_widget_lousa_estilo(Array('font-family' => dashboard_widget_lousa_familia($o['titleFont']))),
				'#titulo#' => htmlspecialchars($o['title'], ENT_QUOTES, 'UTF-8'),
			));
		}

		$escuro = false;
		if($o['background'] !== ''){
			$rgb = hexdec(substr($o['background'], 1));
			$escuro = (0.299 * ($rgb >> 16) + 0.587 * (($rgb >> 8) & 255) + 0.114 * ($rgb & 255)) < 140;
		}
		$largura = (int)$item['width'];
		$estilo = Array(
			'--c2f-w' => min(12, $largura),
			'--c2f-wt' => dashboard_widget_lousa_largura_media($largura),
			'--c2f-h' => (int)$item['height_px'].'px',
			'background-color' => $o['background'],
		);
		if($o['bgImage'] !== ''){
			// O caminho já passou pelo filtro de imagem: só letras, números e `_ - . / % ~`.
			$estilo['--c2f-fundo'] = 'url("'.$o['bgImage'].'")';
			$estilo['--c2f-fundo-opacidade'] = round($o['bgOpacity'] / 100, 2);
			$estilo['--c2f-fundo-tamanho'] = $o['bgFit'] === 'repeat' ? 'auto' : $o['bgFit'];
			$estilo['--c2f-fundo-repetir'] = $o['bgFit'] === 'repeat' ? 'repeat' : 'no-repeat';
		}
		$classes = ($o['frame'] ? '' : ' is-frameless').($o['bgImage'] !== '' ? ' has-bg-image' : '').($objeto ? ' is-objeto' : '');

		$saida .= strtr($celula, Array(
			'#bloco-titulo#' => $titulo,
			'#classes#' => $classes,
			'#w#' => $largura,
			'#h#' => (int)$item['height_px'],
			'#x#' => $item['x'] === null ? '' : (int)$item['x'],
			'#y#' => $item['y'] === null ? '' : (int)$item['y'],
			'#esconder#' => $o['hide'],
			'#tom#' => $escuro ? 'dark' : 'light',
			'#estilo#' => dashboard_widget_lousa_estilo($estilo),
			'#recuo#' => $o['padding'],
			'#conteudo#' => $conteudo,
		));
	}
	if($saida === '') return '';

	$fontesUrl = dashboard_widget_lousa_fontes_url($fontes);
	if($fontesUrl !== ''){
		gestor_pagina_recursos_incluir(Array('html_extra_head' => '<link rel="stylesheet" href="'.htmlspecialchars($fontesUrl, ENT_QUOTES, 'UTF-8').'">'));
	}
	// O ícone é desenhado pelo Lucide; o controlador do widget carrega a biblioteca se a página ainda não tiver.
	$lucide = '';
	if($icones){
		gestor_incluir_biblioteca('assets-externos');
		$externos = function_exists('assets_externos_urls_js') ? assets_externos_urls_js(Array('lucide')) : Array();
		$lucide = (string)($externos['lucide']['lucide.min.js'] ?? '');
	}
	gestor_pagina_javascript_incluir(Array(
		'tipo' => 'widget',
		'modulo_id' => 'dashboard',
		'versao' => dashboard_widget_lousa_versao(),
	));

	// A casca recebe modo e endereço do Lucide antes de os itens entrarem.
	$casca = strtr(str_replace('<!-- objetos < -->'.$moldes.'<!-- objetos > -->', '', $componente), Array(
		'#modo#' => (string)($lousa['mode'] ?? '') === 'lousa' ? 'lousa' : 'grade',
		'#lucide#' => htmlspecialchars($lucide, ENT_QUOTES, 'UTF-8'),
	));
	$partes = explode('<!-- item < -->'.$molde.'<!-- item > -->', $casca, 2);
	if(count($partes) !== 2) return '';

	return $partes[0].$saida.$partes[1];
}
