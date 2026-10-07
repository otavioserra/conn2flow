<?php

/**
 * Layout da área de widgets do Dashboard: normalização de itens, opções de aparência e objetos livres.
 * Usado pelo painel (`dashboard.php`) e pelo widget público que renderiza uma lousa numa página (`dashboard.widget.php`).
 * Só funções puras: nada aqui consulta o banco nem depende de sessão.
 */

/** Famílias do Google Fonts oferecidas na área de widgets (REQ-250). A mesma lista está em `dashboard.js`. */
function dashboard_widgets_fontes(){
	return Array('Inter', 'Roboto', 'Open Sans', 'Montserrat', 'Poppins', 'Lato', 'Raleway', 'Playfair Display', 'Merriweather', 'Oswald', 'Bebas Neue', 'Dancing Script', 'Roboto Mono');
}

/** Imagem escolhida no gerenciador: só caminho do próprio painel, sem `..`, com extensão de imagem. */
function dashboard_widgets_imagem($valor){
	$valor = (string)$valor;
	return (preg_match('#^/[A-Za-z0-9_\-./%~]+\.(png|jpe?g|gif|webp|avif|svg)$#i', $valor) && strpos($valor, '..') === false && strpos($valor, '//') === false) ? $valor : '';
}

/** Objeto livre da lousa (REQ-250): texto, forma, imagem, ícone ou botão, só com atributos conhecidos. */
function dashboard_widgets_objeto_normalizar($objeto){
	if(!is_array($objeto)) $objeto = Array();
	$cor = function($valor, $padrao){ $valor = strtolower((string)$valor); return preg_match('/^#[0-9a-f]{6}$/', $valor) ? $valor : $padrao; };
	$em = function($valor, $lista, $padrao){ return in_array((string)$valor, $lista, true) ? (string)$valor : $padrao; };
	$tamanho = (int)round((float)($objeto['size'] ?? 0));
	$destino = trim((string)($objeto['href'] ?? ''));
	return Array(
		'type' => $em($objeto['type'] ?? '', Array('text', 'shape', 'image', 'icon', 'button'), 'text'),
		'text' => mb_substr((string)($objeto['text'] ?? ''), 0, 2000),
		'font' => $em($objeto['font'] ?? '', dashboard_widgets_fontes(), ''),
		'size' => ($tamanho >= 10 && $tamanho <= 160) ? $tamanho : 28,
		'weight' => (int)($objeto['weight'] ?? 700) === 400 ? 400 : 700,
		'align' => $em($objeto['align'] ?? '', Array('left', 'center', 'right'), 'center'),
		'color' => $cor($objeto['color'] ?? '', '#0f172a'),
		'fill' => $cor($objeto['fill'] ?? '', '#0ea5e9'),
		'shape' => $em($objeto['shape'] ?? '', Array('rect', 'rounded', 'circle', 'line'), 'rounded'),
		'src' => dashboard_widgets_imagem($objeto['src'] ?? ''),
		'fit' => ($objeto['fit'] ?? '') === 'contain' ? 'contain' : 'cover',
		'alt' => mb_substr((string)($objeto['alt'] ?? ''), 0, 160),
		'icon' => preg_match('/^[a-z0-9-]{1,40}$/', (string)($objeto['icon'] ?? '')) ? (string)$objeto['icon'] : 'star',
		// Destino: http(s) ou caminho do painel; `javascript:` e `//host` ficam de fora.
		'href' => preg_match('#^(https?://|/(?!/))[^\s"\'<>\\\\]*$#i', $destino) ? mb_substr($destino, 0, 500) : '',
		'newTab' => ($objeto['newTab'] ?? false) === true,
	);
}

/** Opções de aparência de um widget: só as chaves conhecidas, com valor dentro da lista. */
function dashboard_widgets_opcoes_normalizar($opcoes){
	if(!is_array($opcoes)) $opcoes = Array();
	$fundo = strtolower((string)($opcoes['background'] ?? ''));
	$recuo = (string)($opcoes['padding'] ?? 'none');
	$atualizar = (int)($opcoes['refresh'] ?? 0);
	return Array(
		'header' => ($opcoes['header'] ?? true) !== false,
		'frame' => ($opcoes['frame'] ?? true) !== false,
		'title' => mb_substr(trim((string)($opcoes['title'] ?? '')), 0, 80),
		'background' => preg_match('/^#[0-9a-f]{6}$/', $fundo) ? $fundo : '',
		'padding' => in_array($recuo, Array('none', 'small', 'medium', 'large'), true) ? $recuo : 'none',
		'refresh' => in_array($atualizar, Array(0, 60, 300, 900), true) ? $atualizar : 0,
		// REQ-250
		'hide' => in_array((string)($opcoes['hide'] ?? ''), Array('sm', 'md', 'lg'), true) ? (string)$opcoes['hide'] : '',
		'bgImage' => dashboard_widgets_imagem($opcoes['bgImage'] ?? ''),
		'bgOpacity' => is_numeric($opcoes['bgOpacity'] ?? null) ? max(0, min(100, (int)round((float)$opcoes['bgOpacity']))) : 100,
		'bgFit' => in_array((string)($opcoes['bgFit'] ?? ''), Array('cover', 'contain', 'repeat'), true) ? (string)$opcoes['bgFit'] : 'cover',
		'titleFont' => in_array((string)($opcoes['titleFont'] ?? ''), dashboard_widgets_fontes(), true) ? (string)$opcoes['titleFont'] : '',
	);
}

/** Coluna ou linha da lousa: inteiro de 0 ao limite, ou nulo quando o widget ainda não tem posição. */
function dashboard_widgets_celula($valor, $limite){
	if($valor === null || $valor === '' || !is_numeric($valor) || (int)$valor != $valor) return null;
	$valor = (int)$valor;
	return ($valor >= 0 && $valor <= $limite) ? $valor : null;
}

/**
 * Layout publicado para outros usuários: aceita a string JSON ou o array, descarta item sem widget
 * válido e chave desconhecida, e prende largura (2 a 24 células), altura (120 a 960 px) e a posição
 * na lousa (`x` coluna, `y` linha de 20 px; sem posição, o cliente distribui) na faixa.
 */
function dashboard_widgets_layout_normalizar($layout){
	if(is_string($layout)) $layout = json_decode($layout, true);
	if(!is_array($layout)) return Array();
	$saida = Array();
	foreach($layout as $item){
		if(!is_array($item) || count($saida) >= 40) continue;
		$id = (string)($item['id'] ?? '');
		if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $id)) continue;
		$instancia = substr(preg_replace('/[^a-zA-Z0-9_.-]/', '', (string)($item['instance_id'] ?? '')), 0, 80);
		$params = Array();
		foreach((is_array($item['params'] ?? null) ? $item['params'] : Array()) as $chave => $valor){
			if(is_scalar($valor) && count($params) < 10) $params[mb_substr((string)$chave, 0, 60)] = mb_substr((string)$valor, 0, 200);
		}
		$saida[] = Array(
			'id' => $id,
			'name' => mb_substr((string)($item['name'] ?? $id), 0, 200),
			'registro_id' => mb_substr((string)($item['registro_id'] ?? ''), 0, 200),
			'instance_id' => $instancia !== '' ? $instancia : 'perfil-'.count($saida),
			'width' => max(2, min(24, (int)($item['width'] ?? 4))),
			'height' => (int)($item['height'] ?? 1) === 2 ? 2 : 1,
			'height_px' => max(120, min(960, (int)($item['height_px'] ?? 220))),
			'params' => $params,
			'options' => dashboard_widgets_opcoes_normalizar($item['options'] ?? null),
			'x' => dashboard_widgets_celula($item['x'] ?? null, 23),
			'y' => dashboard_widgets_celula($item['y'] ?? null, 4000),
		);
		// REQ-250: objeto livre usa o identificador reservado `objeto` e leva os atributos dele.
		if($id === 'objeto'){
			$ultimo = count($saida) - 1;
			$saida[$ultimo]['registro_id'] = '';
			$saida[$ultimo]['params'] = Array();
			$saida[$ultimo]['object'] = dashboard_widgets_objeto_normalizar($item['object'] ?? null);
		}
	}
	return $saida;
}

/**
 * Lê o que está gravado em `dashboard_layouts.layout`: o modo da área (`grade` ou `lousa`, REQ-248) e os
 * widgets. Registro anterior à REQ-248 é só a lista de widgets e vale como grade.
 */
function dashboard_widgets_layout_ler($json){
	$dados = is_string($json) ? json_decode($json, true) : $json;
	if(!is_array($dados)) $dados = Array();
	$embrulhado = array_key_exists('widgets', $dados);
	return Array(
		'modo' => ($embrulhado && ($dados['modo'] ?? '') === 'lousa') ? 'lousa' : 'grade',
		// REQ-249: a aba de widgets vem antes da de módulos.
		'primeiro' => $embrulhado && !empty($dados['primeiro']),
		'widgets' => dashboard_widgets_layout_normalizar($embrulhado ? $dados['widgets'] : $dados),
	);
}
