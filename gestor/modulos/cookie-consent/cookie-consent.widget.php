<?php
/**
 * Widget do módulo cookie-consent (req-208): aviso e preferências de cookies.
 *
 * Acionado por gestor.php > gestor_pagina_widgets() > widgets_get() quando a página ou o layout contém:
 *   <!-- widgets#cookie-consent->render({"grupo_slug": "..."}) < -->
 *   <!-- widgets#cookie-consent->render({"grupo_slug": "..."}) > -->
 *
 * O modelo (HTML e CSS do registro) desenha três peças: o aviso, o cartão de preferências e o botão
 * que reabre o cartão. Blocos e variáveis que o widget resolve:
 *   - <!-- category-item < --> ... <!-- category-item > -->          (uma vez por categoria)
 *       [[category#id]], [[category#name]], [[category#description]], [[category#checked]],
 *       [[category#disabled]], e dentro dele:
 *       <!-- category-required < --> ... <!-- category-required > --> (só na categoria obrigatória)
 *       <!-- category-optional < --> ... <!-- category-optional > --> (só na opcional)
 *   - <!-- policy-link < --> ... <!-- policy-link > -->              (só com `policy_url`)
 *   - <!-- terms-link < --> ... <!-- terms-link > -->                (só com `terms_url`)
 *   - <!-- floating-button < --> ... <!-- floating-button > -->      (só com `show_floating_button`)
 *   - [[text#chave]] para cada texto, e as globais [[version]], [[expiry_days]], [[position]],
 *     [[floating_position]], [[consent_mode]], [[policy_url]], [[terms_url]] e [[preview]].
 *
 * A decisão do visitante fica num cookie do próprio site (`c2f_consent`) e é aplicada pelo controlador
 * público `cookie-consent.widget.js`. Este arquivo não grava nada no servidor.
 */

// ===== Funções Auxiliares

function cookie_consent_get_version(){
	$modulo = json_decode(file_get_contents(__DIR__ . '/cookie-consent.json'), true);

	return $modulo['asset_version'] ?? $modulo['versao'] ?? '1.0.0';
}

/** Textos do cartão, na ordem em que aparecem na tela de edição. */
function cookie_consent_text_keys(){
	return ['title', 'message', 'accept_all', 'reject_all', 'customize', 'preferences_title', 'preferences_intro', 'save', 'always_active', 'policy_label', 'terms_label', 'floating_label', 'close'];
}

/** Categorias de fábrica. `necessary` é a única obrigatória. */
function cookie_consent_category_ids(){
	return ['necessary', 'preferences', 'analytics', 'marketing'];
}

function cookie_consent_variavel($id){
	if(!function_exists('gestor_variaveis')) return '';
	return (string)gestor_variaveis(['modulo' => 'cookie-consent', 'id' => $id]);
}

/** Padrões do registro. Os textos vêm das variáveis do módulo, no idioma corrente. */
function cookie_consent_schema_default(){
	$textos = [];
	foreach(cookie_consent_text_keys() as $chave){
		$textos[$chave] = cookie_consent_variavel('default-text-'.str_replace('_', '-', $chave));
	}

	$categorias = [];
	foreach(cookie_consent_category_ids() as $id){
		$categorias[] = [
			'id' => $id,
			'name' => cookie_consent_variavel('default-category-'.$id.'-name'),
			'description' => cookie_consent_variavel('default-category-'.$id.'-description'),
			'required' => $id === 'necessary',
		];
	}

	return [
		'template_id' => '',
		// Trocar a versão faz o aviso voltar para quem já tinha decidido.
		'version' => '1',
		'expiry_days' => 180,
		'position' => 'bottom-left',
		'show_floating_button' => true,
		'floating_position' => 'left',
		'policy_url' => '',
		'terms_url' => '',
		// Envia os sinais do Google Consent Mode v2 ao dataLayer.
		'consent_mode' => true,
		'texts' => $textos,
		'categories' => $categorias,
	];
}

function cookie_consent_widget_bool($valor){
	if(is_bool($valor)) return $valor;
	if(is_int($valor)) return $valor !== 0;
	$valor = strtolower(trim((string)$valor));
	return ($valor === 'true' || $valor === '1' || $valor === 'yes' || $valor === 'on');
}

/** Identificador de categoria: minúsculas, números, hífen e sublinhado. */
function cookie_consent_slug($valor){
	$valor = strtolower(trim((string)$valor));
	$valor = preg_replace('/[^a-z0-9_-]+/', '-', $valor);
	return trim((string)$valor, '-');
}

/**
 * Normaliza o `fields_schema`. Texto vazio cai no padrão; lista de categorias vazia cai nas de
 * fábrica; categoria sem identificador é descartada; duas com o mesmo identificador, só a primeira vale.
 */
function cookie_consent_schema_decode($fields_schema, $template_id = ''){
	$schema = is_array($fields_schema) ? $fields_schema : json_decode($fields_schema ?: '{}', true);
	if(!is_array($schema)) $schema = [];

	$padrao = cookie_consent_schema_default();
	$saida = [];

	foreach(['template_id', 'version', 'position', 'floating_position', 'policy_url', 'terms_url'] as $chave){
		$saida[$chave] = isset($schema[$chave]) ? trim((string)$schema[$chave]) : $padrao[$chave];
	}
	$saida['expiry_days'] = isset($schema['expiry_days']) ? (int)$schema['expiry_days'] : $padrao['expiry_days'];
	foreach(['show_floating_button', 'consent_mode'] as $chave){
		$saida[$chave] = array_key_exists($chave, $schema) ? cookie_consent_widget_bool($schema[$chave]) : $padrao[$chave];
	}

	if($saida['version'] === '') $saida['version'] = $padrao['version'];
	if($saida['expiry_days'] < 1 || $saida['expiry_days'] > 395) $saida['expiry_days'] = $padrao['expiry_days'];
	if(!in_array($saida['position'], ['bottom-left', 'bottom-right', 'bottom', 'center'], true)) $saida['position'] = $padrao['position'];
	if(!in_array($saida['floating_position'], ['left', 'right'], true)) $saida['floating_position'] = $padrao['floating_position'];
	if($saida['template_id'] === '' && $template_id !== '') $saida['template_id'] = (string)$template_id;

	$textos = (isset($schema['texts']) && is_array($schema['texts'])) ? $schema['texts'] : [];
	$saida['texts'] = [];
	foreach(cookie_consent_text_keys() as $chave){
		$valor = isset($textos[$chave]) ? trim((string)$textos[$chave]) : '';
		$saida['texts'][$chave] = $valor !== '' ? $valor : $padrao['texts'][$chave];
	}

	$saida['categories'] = [];
	$vistos = [];
	foreach((isset($schema['categories']) && is_array($schema['categories'])) ? $schema['categories'] : [] as $categoria){
		if(!is_array($categoria)) continue;
		$id = cookie_consent_slug($categoria['id'] ?? '');
		if($id === '' || isset($vistos[$id])) continue;
		$vistos[$id] = true;
		$saida['categories'][] = [
			'id' => $id,
			'name' => trim((string)($categoria['name'] ?? '')) !== '' ? trim((string)$categoria['name']) : $id,
			'description' => trim((string)($categoria['description'] ?? '')),
			'required' => cookie_consent_widget_bool($categoria['required'] ?? false),
		];
	}
	if(empty($saida['categories'])) $saida['categories'] = $padrao['categories'];

	return $saida;
}

/** Endereço informado no painel: absoluto fica como está; caminho do site ganha a raiz. */
function cookie_consent_widget_url($url){
	global $_GESTOR;

	$url = trim((string)$url);
	if($url === '') return '';
	if(preg_match('#^(https?:)?//#i', $url) || $url[0] === '#' || stripos($url, 'mailto:') === 0) return $url;
	// Só http(s), âncora, mailto e caminho do site: `javascript:` e afins não viram link.
	if(preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) return '';

	return ($_GESTOR['url-raiz'] ?? '/').ltrim($url, '/');
}

function cookie_consent_widget_escape($valor){
	return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

/** Mantém o conteúdo do bloco quando `$manter`; senão remove o bloco inteiro. */
function cookie_consent_widget_bloco($html, $nome, $manter){
	$padrao = '/<!--\s*'.preg_quote($nome, '/').'\s*<\s*-->([\s\S]*?)<!--\s*'.preg_quote($nome, '/').'\s*>\s*-->/i';
	if($manter){
		return preg_replace_callback($padrao, function($m){ return $m[1]; }, $html);
	}
	return preg_replace($padrao, '', $html);
}

/** Repete o bloco `category-item` uma vez por categoria. */
function cookie_consent_widget_categorias($html, $categorias){
	$padrao = '/<!--\s*category-item\s*<\s*-->([\s\S]*?)<!--\s*category-item\s*>\s*-->/i';

	return preg_replace_callback($padrao, function($m) use ($categorias){
		$saida = '';
		foreach($categorias as $categoria){
			$obrigatoria = !empty($categoria['required']);
			$item = cookie_consent_widget_bloco($m[1], 'category-required', $obrigatoria);
			$item = cookie_consent_widget_bloco($item, 'category-optional', !$obrigatoria);
			$vars = [
				'id' => cookie_consent_widget_escape($categoria['id']),
				'name' => cookie_consent_widget_escape($categoria['name']),
				'description' => cookie_consent_widget_escape($categoria['description']),
				'checked' => $obrigatoria ? 'checked' : '',
				'disabled' => $obrigatoria ? 'disabled' : '',
			];
			$saida .= preg_replace_callback('/@?\[\[category#([a-zA-Z0-9_\-]+)\]\]@?/', function($v) use ($vars){
				return array_key_exists($v[1], $vars) ? $vars[$v[1]] : '';
			}, $item);
		}
		return $saida;
	}, $html);
}

// ===== Funções Principais

function cookie_consent_render($params){
	global $_GESTOR;

	if(!is_array($params)) return '';

	$grupo_slug = $params['grupo_slug'] ?? null;
	if(empty($grupo_slug)) return '';

	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => 'cookie_consent',
		'campos' => Array('id_cookie_consent', 'id', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head'),
		'extra' =>
			"WHERE id='".banco_escape_field($grupo_slug)."'"
			." AND status='A'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
	));

	if(!$registro || trim((string)($registro['html'] ?? '')) === '') return '';

	gestor_pagina_javascript_incluir(Array(
		'tipo' => 'widget',
		'modulo_id' => 'cookie-consent',
		'versao' => cookie_consent_get_version(),
	));

	return cookie_consent_widget_render_inline([
		'html' => $registro['html'],
		'css' => $registro['css'] ?? '',
		'css_compiled' => $registro['css_compiled'] ?? '',
		'html_extra_head' => $registro['html_extra_head'] ?? '',
		'fields_schema' => $registro['fields_schema'] ?? '{}',
	]);
}

/**
 * Renderiza o aviso a partir de entradas cruas, sem ler a tabela. Usado pelo widget (depois da
 * consulta) e pela pré-visualização do painel, que passa `preview` para o aviso aparecer sempre e
 * nada ser gravado no navegador.
 *
 * @param array $params ['html', 'css', 'css_compiled', 'html_extra_head', 'fields_schema', 'preview']
 * @return string
 */
function cookie_consent_widget_render_inline($params){
	$html = (string)($params['html'] ?? '');
	if(trim($html) === '') return '';

	$schema = cookie_consent_schema_decode($params['fields_schema'] ?? '{}');
	$preview = !empty($params['preview']);

	$policy = cookie_consent_widget_url($schema['policy_url']);
	$terms = cookie_consent_widget_url($schema['terms_url']);

	$html = cookie_consent_widget_categorias($html, $schema['categories']);
	$html = cookie_consent_widget_bloco($html, 'policy-link', $policy !== '');
	$html = cookie_consent_widget_bloco($html, 'terms-link', $terms !== '');
	$html = cookie_consent_widget_bloco($html, 'floating-button', $schema['show_floating_button']);

	$textos = $schema['texts'];
	$html = preg_replace_callback('/@?\[\[text#([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($textos){
		return array_key_exists($m[1], $textos) ? cookie_consent_widget_escape($textos[$m[1]]) : '';
	}, $html);

	$map = [
		'version' => cookie_consent_widget_escape($schema['version']),
		'expiry_days' => (string)$schema['expiry_days'],
		'position' => $schema['position'],
		'floating_position' => $schema['floating_position'],
		'consent_mode' => $schema['consent_mode'] ? 'true' : 'false',
		'policy_url' => cookie_consent_widget_escape($policy),
		'terms_url' => cookie_consent_widget_escape($terms),
		'preview' => $preview ? 'true' : 'false',
	];
	$html = preg_replace_callback('/@?\[\[([a-zA-Z0-9_\-]+)\]\]@?/', function($m) use ($map){
		return array_key_exists($m[1], $map) ? $map[$m[1]] : $m[0];
	}, $html);

	if(function_exists('gestor_pagina_recursos_incluir')){
		gestor_pagina_recursos_incluir(Array(
			'css' => (string)($params['css'] ?? ''),
			'css_compiled' => (string)($params['css_compiled'] ?? ''),
			'html_extra_head' => (string)($params['html_extra_head'] ?? ''),
		));
	}

	return $html;
}
