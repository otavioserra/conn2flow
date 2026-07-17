<?php
/**
 * Widget renderer público do módulo Forms.
 *
 * O widget usa o HTML/CSS salvo no banco, repete o bloco `item` para cada campo
 * declarado em `fields_schema.fields[]` e resolve condicionais por tipo de campo.
 */

function forms_search_get_version() {
	$modulo = json_decode(file_get_contents(__DIR__ . '/forms-search.json'), true);
	return isset($modulo['versao']) ? $modulo['versao'] : '1.0.0';
}

function forms_search_widget_bool($value) {
	if (is_bool($value)) return $value;
	if (is_int($value)) return $value !== 0;
	$value = strtolower(trim((string)$value));
	return ($value === 'true' || $value === '1' || $value === 'yes' || $value === 'on' || $value === 'required');
}

function forms_search_widget_schema($fields_schema) {
	$schema = json_decode($fields_schema ?: '{}', true);
	if (!is_array($schema)) $schema = [];
	if (!isset($schema['fields']) || !is_array($schema['fields'])) $schema['fields'] = [];
	return $schema;
}

function forms_search_widget_replace_var($html, $name, $value) {
	return preg_replace('/@?\[\['.preg_quote($name, '/').'\]\]@?/', (string)$value, $html);
}

function forms_search_widget_block($html, $name, $keep) {
	$pattern = '/<!--\s*'.preg_quote($name, '/').'\s*<\s*-->([\s\S]*?)<!--\s*'.preg_quote($name, '/').'\s*>\s*-->/i';
	return preg_replace($pattern, $keep ? '$1' : '', $html);
}

function forms_search_widget_options_html($field) {
	$options = $field['options'] ?? [];
	$type = $field['type'] ?? 'select';
	$name = $field['name'] ?? '';
	$required = forms_search_widget_bool($field['required'] ?? false) ? 'required' : '';

	if (is_string($options)) {
		$options = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $options)));
	}
	if (!is_array($options)) return '';

	$html = '';
	foreach ($options as $option) {
		if (is_array($option)) {
			$value = $option['value'] ?? ($option['label'] ?? '');
			$label = $option['label'] ?? $value;
		} else {
			if (strpos($option, '|') !== false) {
				$parts = explode('|', $option, 2);
				$value = trim($parts[0]);
				$label = trim($parts[1]);
			} else if (strpos($option, ':') !== false) {
				$parts = explode(':', $option, 2);
				$value = trim($parts[0]);
				$label = trim($parts[1]);
			} else {
				$value = $option;
				$label = $option;
			}
		}

		if ($type === 'radio' || $type === 'checkbox') {
			$input_name = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8').($type === 'checkbox' ? '[]' : '');
			$html .= '<label class="inline-flex items-center mr-4 mb-2 cursor-pointer">'
				.'<input type="'.$type.'" name="'.$input_name.'" value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" '.$required.'>'
				.'<span class="ml-2 text-sm text-gray-700">'.htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8').'</span>'
				.'</label>';
		} else {
			$html .= '<option value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8').'</option>';
		}
	}
	return $html;
}

/**
 * Extrai as diretivas de limite (min/max/step) das linhas do campo "Opções".
 * Aceita string (multilinha) ou array de linhas. Linhas no formato `min:X`, `max:Y`, `step:Z`.
 *
 * @param mixed $options Conteúdo do campo options.
 * @return array ['min'=>string|null, 'max'=>string|null, 'step'=>string|null]
 */
function forms_search_widget_parse_limits($options) {
	$res = ['min' => null, 'max' => null, 'step' => null];
	if (is_string($options)) $options = preg_split('/\r\n|\r|\n/', $options);
	if (!is_array($options)) return $res;
	foreach ($options as $line) {
		if (!is_string($line)) continue;
		if (preg_match('/^\s*(min|max|step)\s*:\s*(.+?)\s*$/i', $line, $m)) {
			$res[strtolower($m[1])] = $m[2];
		}
	}
	return $res;
}

/**
 * Resolve o valor padrão de um campo `hidden` a partir do campo "Opções".
 * O texto digitado nas opções representa o valor constante enviado pelo campo oculto.
 */
function forms_search_widget_hidden_default($options) {
	if (is_array($options)) {
		foreach ($options as $o) {
			$o = trim((string)$o);
			if ($o !== '') return $o;
		}
		return '';
	}
	return trim((string)$options);
}

/**
 * Injeta atributos extras na primeira tag de abertura informada (`input`/`textarea`),
 * preservando os atributos existentes. Usado para min/max/step/minlength/maxlength.
 */
function forms_search_widget_inject_tag_attrs($html, $tag, $attrs) {
	if (trim($attrs) === '') return $html;
	return preg_replace_callback('/<'.$tag.'\b([^>]*?)(\/?)>/i', function ($m) use ($tag, $attrs) {
		return '<'.$tag.rtrim($m[1]).' '.trim($attrs).$m[2].'>';
	}, $html, 1);
}

/**
 * Injeta `value="..."` na primeira tag `<input>` quando ainda não houver atributo value.
 * Usado para o valor padrão do campo `hidden` em templates legados sem o placeholder.
 */
function forms_search_widget_inject_value_if_absent($html, $value) {
	return preg_replace_callback('/<input\b([^>]*?)>/i', function ($m) use ($value) {
		if (preg_match('/\bvalue\s*=/i', $m[1])) return $m[0];
		return '<input'.rtrim($m[1]).' value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'">';
	}, $html, 1);
}

/**
 * Acrescenta uma classe CSS à primeira tag de abertura informada (preserva classes existentes).
 */
function forms_search_widget_add_tag_class($html, $tag, $class) {
	return preg_replace_callback('/<'.$tag.'\b([^>]*?)(\/?)>/i', function ($m) use ($tag, $class) {
		$attrs = $m[1];
		if (preg_match('/\bclass\s*=\s*"([^"]*)"/i', $attrs)) {
			$attrs = preg_replace('/\bclass\s*=\s*"([^"]*)"/i', 'class="$1 '.$class.'"', $attrs, 1);
		} else {
			$attrs = rtrim($attrs).' class="'.$class.'"';
		}
		return '<'.$tag.$attrs.$m[2].'>';
	}, $html, 1);
}

/**
 * Envolve a primeira tag `<input>` numa estrutura com o botão de alternar visibilidade da senha.
 * O botão usa a classe `.forms-search-password-toggle` e o ícone `eye` (Fomantic) com estilos inline
 * para funcionar também em páginas Tailwind / CSS Vanilla sem depender do framework ativo.
 */
function forms_search_widget_wrap_password($html) {
	return preg_replace_callback('/<input\b[^>]*>/i', function ($m) {
		$input = $m[0];
		$iconEye = '<svg class="forms-search-password-icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"></path><circle cx="12" cy="12" r="3.25"></circle></svg>';
		$iconEyeSlash = '<svg class="forms-search-password-icon-eye-slash" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;"><path d="M3 3l18 18"></path><path d="M10.58 10.58A2 2 0 0 0 10 12a2 2 0 0 0 3.42 1.42"></path><path d="M9.88 5.09A10.94 10.94 0 0 1 12 4.88c6 0 9.75 7.12 9.75 7.12a20.3 20.3 0 0 1-4.04 4.95"></path><path d="M6.61 6.61A20.78 20.78 0 0 0 2.25 12S6 19.12 12 19.12c1.76 0 3.3-.38 4.65-.97"></path></svg>';
		$toggle = '<button type="button" class="forms-search-password-toggle" aria-label="Mostrar/ocultar senha" tabindex="-1"'
			.' style="position:absolute;top:50%;right:0.75rem;transform:translateY(-50%);background:transparent;border:0;cursor:pointer;padding:0;line-height:1;color:#6b7280;">'
			.$iconEye.$iconEyeSlash.'</button>';
		return '<div class="forms-search-password-wrapper" style="position:relative;">'.$input.$toggle.'</div>';
	}, $html, 1);
}

/**
 * Extrai apenas a primeira tag `<input>` do HTML (descarta label/wrapper).
 * Usado para campos `hidden`, que não devem exibir rótulo ou ocupar espaço visual.
 */
function forms_search_widget_extract_input($html) {
	if (preg_match('/<input\b[^>]*>/i', $html, $m)) return $m[0];
	return $html;
}

function forms_search_widget_render_field($template, $field) {
	$type = $field['type'] ?? 'text';
	$name = $field['name'] ?? '';
	$label = $field['label'] ?? $name;
	$placeholder = $field['placeholder'] ?? '';
	$required = forms_search_widget_bool($field['required'] ?? false) ? 'required' : '';

	// Tipos nativos que reaproveitam o bloco de input simples (type-input).
	$inputType = ($type === 'textarea' || $type === 'select' || $type === 'radio' || $type === 'checkbox') ? 'text' : (string)$type;

	// Valor padrão do campo (constante para hidden; vazio para os demais).
	$value = ($type === 'hidden') ? forms_search_widget_hidden_default($field['options'] ?? []) : '';

	$html = $template;
	$html = forms_search_widget_replace_var($html, 'item#label', htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'));
	$html = forms_search_widget_replace_var($html, 'item#name', htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8'));
	$html = forms_search_widget_replace_var($html, 'item#placeholder', htmlspecialchars((string)$placeholder, ENT_QUOTES, 'UTF-8'));
	$html = forms_search_widget_replace_var($html, 'item#type', htmlspecialchars($inputType, ENT_QUOTES, 'UTF-8'));
	$html = forms_search_widget_replace_var($html, 'item#required', $required);
	$html = forms_search_widget_replace_var($html, 'item#value', htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
	$html = forms_search_widget_replace_var($html, 'item#options', forms_search_widget_options_html($field));

	$html = forms_search_widget_block($html, 'type-select', $type === 'select');
	$html = forms_search_widget_block($html, 'type-textarea', $type === 'textarea');
	$html = forms_search_widget_block($html, 'type-radio', $type === 'radio');
	$html = forms_search_widget_block($html, 'type-checkbox', $type === 'checkbox');
	$html = forms_search_widget_block($html, 'type-input', $type !== 'select' && $type !== 'textarea' && $type !== 'radio' && $type !== 'checkbox');

	// ===== Atributos de limite/validação derivados do campo "Opções".
	$limits = forms_search_widget_parse_limits($field['options'] ?? []);
	if ($type === 'text' || $type === 'textarea') {
		$tag = ($type === 'textarea') ? 'textarea' : 'input';
		$attrs = '';
		if ($limits['min'] !== null && $limits['min'] !== '') $attrs .= ' minlength="'.htmlspecialchars($limits['min'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['max'] !== null && $limits['max'] !== '') $attrs .= ' maxlength="'.htmlspecialchars($limits['max'], ENT_QUOTES, 'UTF-8').'"';
		$html = forms_search_widget_inject_tag_attrs($html, $tag, $attrs);
	} elseif ($type === 'number') {
		$attrs = '';
		if ($limits['min'] !== null && $limits['min'] !== '') $attrs .= ' min="'.htmlspecialchars($limits['min'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['max'] !== null && $limits['max'] !== '') $attrs .= ' max="'.htmlspecialchars($limits['max'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['step'] !== null && $limits['step'] !== '') $attrs .= ' step="'.htmlspecialchars($limits['step'], ENT_QUOTES, 'UTF-8').'"';
		$html = forms_search_widget_inject_tag_attrs($html, 'input', $attrs);
	} elseif ($type === 'date') {
		$attrs = '';
		if ($limits['min'] !== null && $limits['min'] !== '') $attrs .= ' min="'.htmlspecialchars($limits['min'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['max'] !== null && $limits['max'] !== '') $attrs .= ' max="'.htmlspecialchars($limits['max'], ENT_QUOTES, 'UTF-8').'"';
		$html = forms_search_widget_inject_tag_attrs($html, 'input', $attrs);
		// Marca para a melhoria progressiva (date picker Fomantic quando disponível).
		$html = forms_search_widget_add_tag_class($html, 'input', 'forms-search-date-picker');
	}

	// ===== Tratamentos específicos por tipo.
	if ($type === 'hidden') {
		$html = forms_search_widget_inject_value_if_absent($html, $value);
		$html = forms_search_widget_extract_input($html);
	} elseif ($type === 'password') {
		$html = forms_search_widget_wrap_password($html);
	}

	return $html;
}

function forms_search_render($params) {
	global $_GESTOR;

	if (!is_array($params)) return '';

	$form_id = $params['form_id'] ?? ($params['grupo_slug'] ?? ($params['id'] ?? null));
	if (empty($form_id)) return '';

	$registro = banco_select([
		'unico' => true,
		'tabela' => 'forms_search',
		'campos' => ['id_forms_search', 'id', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head'],
		'extra' =>
			"WHERE id='".banco_escape_field($form_id)."'"
			." AND status='A'"
			." AND language='".$_GESTOR['linguagem-codigo']."'",
	]);

	if (!$registro) return '';

	$schema = forms_search_widget_schema($registro['fields_schema'] ?? '{}');

	$html = (string)($registro['html'] ?? '');
	$css = (string)($registro['css'] ?? '');
	$css_compiled = (string)($registro['css_compiled'] ?? '');
	$html_extra_head = (string)($registro['html_extra_head'] ?? '');

	// Fallback de template: formulários que usam o modelo padrão (sem customização salva) gravam
	// os campos html/css vazios no banco. Nesse caso, carregar o conteúdo do template referenciado
	// em fields_schema.template_id para que o widget continue renderizando o formulário.
	if (trim($html) === '') {
		$template_id = (string)($schema['template_id'] ?? '');
		if ($template_id !== '') {
			$template = banco_select([
				'unico' => true,
				'tabela' => 'templates',
				'campos' => ['html', 'css', 'css_compiled', 'html_extra_head'],
				'extra' =>
					"WHERE id='".banco_escape_field($template_id)."'"
					." AND target='forms-search'"
					." AND status='A'"
					." AND language='".$_GESTOR['linguagem-codigo']."'",
			]);
			if ($template) {
				$html = (string)($template['html'] ?? '');
				if (trim($css) === '') $css = (string)($template['css'] ?? '');
				if (trim($css_compiled) === '') $css_compiled = (string)($template['css_compiled'] ?? '');
				if (trim($html_extra_head) === '') $html_extra_head = (string)($template['html_extra_head'] ?? '');
			}
		}
	}

	if (trim($html) === '') return '';

	// req-088 §1: formulário de BUSCA — usa GET nativo para a página alvo (que contém o widget
	// pages-index). NÃO delega ao controlador de submissão AJAX/reCAPTCHA do forms (esse é para
	// POST); o submit é uma navegação do navegador para {url-raiz}{form_action}?search=...
	if (function_exists('gestor_pagina_recursos_incluir')) {
		gestor_pagina_recursos_incluir([
			'css' => $css,
			'css_compiled' => $css_compiled,
			'html_extra_head' => $html_extra_head,
		]);
	}

	return forms_search_widget_render_inline([
		'form_id' => $registro['id'],
		'html' => $html,
		'fields_schema' => $registro['fields_schema'] ?? '{}',
	]);
}

/**
 * req-070 §2.2: obtém a configuração JS dinâmica (gestor.form[id]) de um formulário para o
 * preview do Editor HTML. Reutiliza o builder da biblioteca de formulários (formulario_montar_js_vars)
 * para devolver exatamente a mesma estrutura que formulario_controlador injeta no site publicado
 * (fields, reCAPTCHA v2/v3, redirects, prompts, componentes Fomantic/Tailwind), sem renderizar a página.
 *
 * @param array $params ['form_id' => slug] (aceita 'formId'/'id' como aliases).
 * @return array|null Configuração indexada pelo formulário ou null quando inexistente.
 */
function forms_search_render_editor_html($params) {
	if (!is_array($params)) return null;

	$form_id = $params['form_id'] ?? ($params['formId'] ?? ($params['id'] ?? null));
	if (empty($form_id)) return null;

	gestor_incluir_biblioteca('formulario');
	if (!function_exists('formulario_montar_js_vars')) return null;

	$forms_search_js_vars = formulario_montar_js_vars([$form_id]);
	if (!is_array($forms_search_js_vars) || !isset($forms_search_js_vars[$form_id])) return null;

	return $forms_search_js_vars[$form_id];
}

function forms_search_widget_render_inline($params) {
	$form_id = $params['form_id'] ?? ($params['grupo_slug'] ?? 'busca');
	$html = $params['html'] ?? '';
	$schema = forms_search_widget_schema($params['fields_schema'] ?? '{}');
	$fields = $schema['fields'];

	// Renderiza os campos dinâmicos (mesmo loop de itens herdado do forms).
	$itemRegex = '/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i';
	if (preg_match($itemRegex, $html, $match)) {
		$itemTemplate = $match[1];
		$rendered = '';
		foreach ($fields as $field) {
			if (is_array($field)) $rendered .= forms_search_widget_render_field($itemTemplate, $field);
		}
		$html = preg_replace($itemRegex, $rendered, $html, 1);
	}

	// req-088 §1: alvo do action = página que contém o widget pages-index (form_action do schema).
	$action = forms_search_resolver_action((string)($schema['form_action'] ?? ''));

	$html = forms_search_widget_replace_var($html, 'form_id', htmlspecialchars((string)$form_id, ENT_QUOTES, 'UTF-8'));
	$html = forms_search_widget_replace_var($html, 'form_action', htmlspecialchars($action, ENT_QUOTES, 'UTF-8'));
	$html = forms_search_widget_replace_var($html, 'force_recaptcha', 'false');

	// req-088 §1: força GET + action no <form> e garante um campo name="search" na saída.
	$html = forms_search_widget_forcar_get($html, $action);
	$html = forms_search_widget_garantir_campo_search($html);

	return $html;
}

/**
 * req-088 §1: resolve o caminho alvo do action. Vazio → destino padrão de buscas (`pages-index/`).
 * URLs absolutas (http/https/protocol-relative) passam intactas; caminhos relativos recebem a url-raiz.
 */
function forms_search_resolver_action($raw) {
	global $_GESTOR;

	$raw = trim((string)$raw);
	$raiz = (string)($_GESTOR['url-raiz'] ?? '');

	if ($raw === '') return $raiz.'busca/';
	if (preg_match('#^(https?:)?//#i', $raw)) return $raw;

	// Remove barra inicial redundante para não duplicar com a url-raiz (que termina em /).
	if ($raiz !== '' && substr($raiz, -1) === '/' && substr($raw, 0, 1) === '/') {
		return $raiz.ltrim($raw, '/');
	}
	return $raiz.$raw;
}

/**
 * req-088 §1: força o primeiro <form> a usar method="get" e o action informado, removendo
 * eventuais method/action herdados do template (que vinha com method="post").
 */
function forms_search_widget_forcar_get($html, $action) {
	return preg_replace_callback('/<form\b([^>]*)>/i', function ($m) use ($action) {
		$attrs = $m[1];
		$attrs = preg_replace('/\s+method\s*=\s*"[^"]*"/i', '', $attrs);
		$attrs = preg_replace('/\s+action\s*=\s*"[^"]*"/i', '', $attrs);
		$attrs = rtrim($attrs);
		return '<form'.$attrs.' method="get" action="'.htmlspecialchars((string)$action, ENT_QUOTES, 'UTF-8').'">';
	}, $html, 1);
}

/**
 * req-088 §1: garante que a saída contenha um campo `name="search"` (a variável enviada na URL).
 * Se nenhum campo já usar esse name, converte o primeiro <input type=text|search> para name="search",
 * dando robustez a templates herdados do forms cujo campo não se chama "search".
 */
function forms_search_widget_garantir_campo_search($html) {
	if (preg_match('/name\s*=\s*"search"/i', $html)) return $html;

	$done = false;
	$html = preg_replace_callback('/<input\b([^>]*)>/i', function ($m) use (&$done) {
		if ($done) return $m[0];
		$attrs = $m[1];
		if (!preg_match('/type\s*=\s*"(text|search)"/i', $attrs)) return $m[0];
		$done = true;
		if (preg_match('/name\s*=\s*"[^"]*"/i', $attrs)) {
			$attrs = preg_replace('/name\s*=\s*"[^"]*"/i', 'name="search"', $attrs, 1);
		} else {
			$attrs = rtrim($attrs).' name="search"';
		}
		return '<input'.$attrs.'>';
	}, $html);

	return $html;
}

/**
 * req-088 §1 (analytics): registra um termo pesquisado na tabela `forms_search_submissions`.
 * Invocado pelo widget `pages-index` sempre que uma busca é processada (page load com ?search=
 * e AJAX de nova busca). Função pública, dona da tabela — carregada sob demanda pelo pages-index.
 *
 * @param string $termo       Termo pesquisado (não vazio).
 * @param string $grupo_slug  Slug do índice/destino que originou a busca (gravado em form_id).
 */
function forms_search_registrar_busca($termo, $grupo_slug = '') {
	global $_GESTOR;

	$termo = trim((string)$termo);
	if ($termo === '') return;
	if (!function_exists('banco_insert_name') || !function_exists('banco_escape_field')) return;

	$lang = $_GESTOR['linguagem-codigo'] ?? 'pt-br';
	$form_id = ((string)$grupo_slug !== '') ? (string)$grupo_slug : 'pages-index';

	// Limites das colunas (form_id/id: 100; name: 255).
	$form_id = substr($form_id, 0, 100);
	$nome = mb_substr($termo, 0, 255, 'UTF-8');
	$uid = substr('busca-'.date('YmdHis').'-'.md5($termo.microtime(true).mt_rand()), 0, 100);
	$fields_values = json_encode([['id' => 'search', 'value' => $termo]], JSON_UNESCAPED_UNICODE);

	$campos = [];
	$campos[] = ['form_id', banco_escape_field($form_id)];
	$campos[] = ['name', banco_escape_field($nome)];
	$campos[] = ['id', banco_escape_field($uid)];
	$campos[] = ['fields_values', banco_escape_field($fields_values)];
	$campos[] = ['language ', $lang, false];
	$campos[] = ['status', 'A', false];
	$campos[] = ['version', '1', false];
	$campos[] = ['created_at', 'NOW()', true];
	$campos[] = ['updated_at', 'NOW()', true];

	banco_insert_name($campos, 'forms_search_submissions');
}
