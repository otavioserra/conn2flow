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
	$schema['fields'] = array_values(array_filter($schema['fields'], function ($field) {
		return !is_array($field) || strtolower(trim((string)($field['name'] ?? ''))) !== 'search';
	}));
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
		if (preg_match('/\bclass\s*=\s*"([^"]*)"/i', $attrs, $class_match)) {
			if (preg_match('/(?:^|\s)'.preg_quote($class, '/').'(?:\s|$)/', $class_match[1])) return $m[0];
			$attrs = preg_replace('/\bclass\s*=\s*"([^"]*)"/i', 'class="$1 '.$class.'"', $attrs, 1);
		} else {
			$attrs = rtrim($attrs).' class="'.$class.'"';
		}
		return '<'.$tag.$attrs.$m[2].'>';
	}, $html, 1);
}

/**
 * Define ou substitui um atributo na primeira tag recebida, aceitando atributos com aspas
 * simples, duplas ou sem aspas. O valor sempre volta escapado e entre aspas duplas.
 */
function forms_search_widget_set_attribute($tag, $name, $value) {
	$escaped = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	$pattern = '/\s+'.preg_quote($name, '/').'\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i';
	$attribute = ' '.$name.'="'.$escaped.'"';
	if (preg_match($pattern, $tag)) return preg_replace($pattern, $attribute, $tag, 1);
	return preg_replace('/\s*\/?\s*>$/', $attribute.'>', $tag, 1);
}

function forms_search_widget_add_boolean_attribute($tag, $name) {
	if (preg_match('/\s+'.preg_quote($name, '/').'(?:\s|=|>|\/)/i', $tag)) return $tag;
	return preg_replace('/\s*\/?\s*>$/', ' '.$name.'>', $tag, 1);
}

function forms_search_widget_normalize_search_input($input, $form_id, $results_id) {
	$input = forms_search_widget_set_attribute($input, 'type', 'search');
	$input = forms_search_widget_set_attribute($input, 'name', 'search');
	$input = forms_search_widget_set_attribute($input, 'id', (string)$form_id.'-search');
	$input = forms_search_widget_set_attribute($input, 'autocomplete', 'off');
	$input = forms_search_widget_set_attribute($input, 'role', 'combobox');
	$input = forms_search_widget_set_attribute($input, 'aria-autocomplete', 'list');
	$input = forms_search_widget_set_attribute($input, 'aria-controls', $results_id);
	$input = forms_search_widget_set_attribute($input, 'aria-expanded', 'false');
	return forms_search_widget_add_boolean_attribute($input, 'required');
}

/**
 * Renderiza a célula intrínseca de busca. O desenho do input continua pertencendo ao template,
 * mas seus atributos funcionais são controlados pelo módulo e não pelo schema de campos extras.
 */
function forms_search_widget_render_search_cell($html, $form_id) {
	$results_id = (string)$form_id.'-autocomplete-results';
	$pattern = '/<!--\s*input-search\s*<\s*-->([\s\S]*?)<!--\s*input-search\s*>\s*-->/i';
	$found = false;
	$html = preg_replace_callback($pattern, function ($m) use ($form_id, $results_id, &$found) {
		$found = true;
		$content = $m[1];
		$input_found = false;
		$content = preg_replace_callback('/<input\b[^>]*>/i', function ($input_match) use ($form_id, $results_id, &$input_found) {
			if ($input_found) return $input_match[0];
			$input_found = true;
			return forms_search_widget_normalize_search_input($input_match[0], $form_id, $results_id);
		}, $content, 1);

		if (!$input_found) {
			$content .= '<input type="search" name="search" id="'.htmlspecialchars((string)$form_id, ENT_QUOTES, 'UTF-8').'-search"'
				.' class="forms-search-input" autocomplete="off" role="combobox" aria-autocomplete="list"'
				.' aria-controls="'.htmlspecialchars($results_id, ENT_QUOTES, 'UTF-8').'" aria-expanded="false" required>';
		}
		return $content;
	}, $html, 1);

	if (!$found) {
		$normalized = false;
		$html = preg_replace_callback('/<input\b[^>]*>/i', function ($input_match) use ($form_id, $results_id, &$normalized) {
			if ($normalized || !preg_match('/\sname\s*=\s*(["\'])search\1/i', $input_match[0])) return $input_match[0];
			$normalized = true;
			return forms_search_widget_normalize_search_input($input_match[0], $form_id, $results_id);
		}, $html);
		if (!$normalized) {
			$fallback = '<input type="search" name="search" id="'.htmlspecialchars((string)$form_id, ENT_QUOTES, 'UTF-8').'-search"'
				.' class="forms-search-input" autocomplete="off" role="combobox" aria-autocomplete="list"'
				.' aria-controls="'.htmlspecialchars($results_id, ENT_QUOTES, 'UTF-8').'" aria-expanded="false" required>';
			$html = preg_replace('/<\/form>/i', $fallback.'</form>', $html, 1);
		}
	}
	return $html;
}

/** Garante a caixa flutuante que receberá resultados, inclusive em templates legados. */
function forms_search_widget_render_results_cell($html, $form_id) {
	$results_id = (string)$form_id.'-autocomplete-results';
	$pattern = '/<!--\s*results-box\s*<\s*-->([\s\S]*?)<!--\s*results-box\s*>\s*-->/i';
	$found = false;
	$html = preg_replace_callback($pattern, function ($m) use ($results_id, &$found) {
		$found = true;
		$content = trim($m[1]);
		if ($content === '') $content = '<div class="forms-search-results"></div>';
		$content = forms_search_widget_add_tag_class($content, 'div', 'forms-search-results');
		$content = preg_replace_callback('/<div\b[^>]*>/i', function ($tag_match) use ($results_id) {
			$tag = forms_search_widget_set_attribute($tag_match[0], 'id', $results_id);
			$tag = forms_search_widget_set_attribute($tag, 'role', 'listbox');
			return forms_search_widget_set_attribute($tag, 'aria-live', 'polite');
		}, $content, 1);
		return $content;
	}, $html, 1);

	if (!$found) {
		$fallback = '<div class="forms-search-results" id="'.htmlspecialchars($results_id, ENT_QUOTES, 'UTF-8').'" role="listbox" aria-live="polite"></div>';
		$html = preg_replace('/<\/form>/i', $fallback.'</form>', $html, 1);
	}
	return $html;
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

/** Renderiza HTML informado pelo CRUD ou pelo widget público usando o mesmo contrato. */
function forms_search_widget_render_inline($params) {
	$form_id = $params['form_id'] ?? ($params['grupo_slug'] ?? 'busca');
	$form_id = forms_search_widget_normalize_form_id($form_id);
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

	// req-088 §1: força GET + action no <form> e garante um campo name="search" na saída.
	$html = forms_search_widget_forcar_get($html, $action, $form_id);
	$html = forms_search_widget_render_search_cell($html, $form_id);
	$html = forms_search_widget_render_results_cell($html, $form_id);

	return $html;
}

/**
 * Impede que placeholders usados na documentacao virem ids reais no DOM do preview.
 * Registros publicados sempre recebem o slug persistido; o fallback existe para inclusao/preview.
 */
function forms_search_widget_normalize_form_id($form_id) {
	$form_id = trim((string)$form_id);
	if ($form_id === '' || preg_match('/^\[[^\]]+\]$/', $form_id)) return 'forms-search-preview';
	return $form_id;
}

/**
 * req-088 §1: resolve o caminho alvo do action. Vazio → destino padrão de buscas (`pages-index-search/`).
 * URLs absolutas (http/https/protocol-relative) passam intactas; caminhos relativos recebem a url-raiz.
 */
function forms_search_resolver_action($raw) {
	global $_GESTOR;

	$raw = trim((string)$raw);
	$raiz = (string)($_GESTOR['url-raiz'] ?? '');

	if ($raw === '') return $raiz.'pages-index-search/';
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
function forms_search_widget_forcar_get($html, $action, $form_id = '') {
	return preg_replace_callback('/<form\b([^>]*)>/i', function ($m) use ($action, $form_id) {
		$form = forms_search_widget_set_attribute($m[0], 'method', 'get');
		$form = forms_search_widget_set_attribute($form, 'action', $action);
		$form = forms_search_widget_set_attribute($form, 'data-form-id', $form_id);
		return forms_search_widget_add_tag_class($form, 'form', 'conn2flow-search-form');
	}, $html, 1);
}

function forms_search_autocomplete_summary($html, $limit = 180) {
	$text = preg_replace('#<(script|style)\b[^>]*>[\s\S]*?</\1>#i', ' ', (string)$html);
	$text = preg_replace('/<[^>]+>/', ' ', $text);
	$text = trim(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
	$text = preg_replace('/\s+/u', ' ', $text);
	if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
		return rtrim(mb_substr($text, 0, $limit, 'UTF-8')).'…';
	}
	if (!function_exists('mb_strlen') && strlen($text) > $limit) {
		return rtrim(substr($text, 0, $limit)).'…';
	}
	return $text;
}

/** Consulta pública paginada usada pelo autocomplete (30 resultados por página). */
function forms_search_autocomplete_response($params) {
	global $_GESTOR;

	if (!is_array($params)) $params = [];
	$search = trim((string)($params['search'] ?? ($params['busca'] ?? '')));
	$page = max(1, (int)($params['page'] ?? ($params['pagina'] ?? 1)));
	$length = function_exists('mb_strlen') ? mb_strlen($search, 'UTF-8') : strlen($search);
	if ($length < 3) {
		return ['status' => 'Ok', 'results' => [], 'tem_mais' => false, 'pagina' => $page];
	}

	$limit = 30;
	$offset = ($page - 1) * $limit;
	$literal = banco_escape_field($search);
	$language = banco_escape_field($_GESTOR['linguagem-codigo'] ?? 'pt-br');
	$rows = banco_select([
		'tabela' => 'paginas',
		'campos' => ['nome', 'caminho', 'html'],
		'extra' => "WHERE status='A' AND language='".$language."' AND tipo='pagina' AND sem_permissao=1"
			." AND (nome LIKE '%".$literal."%' OR html LIKE '%".$literal."%')"
			." ORDER BY nome ASC LIMIT ".$offset.", ".($limit + 1),
	]);

	$rows = is_array($rows) ? $rows : [];
	$has_more = count($rows) > $limit;
	if ($has_more) array_pop($rows);
	$root = (string)($_GESTOR['url-raiz'] ?? '');
	$results = [];
	foreach ($rows as $row) {
		$path = (string)($row['caminho'] ?? '');
		$results[] = [
			'title' => (string)($row['nome'] ?? ''),
			'summary' => forms_search_autocomplete_summary($row['html'] ?? ''),
			'url' => $path !== '' ? $root.ltrim($path, '/') : '',
		];
	}

	return [
		'status' => 'Ok',
		'results' => $results,
		'tem_mais' => $has_more,
		'pagina' => $page,
	];
}

/** Callback do roteador AJAX público de widgets. */
function forms_search_render_ajax($params) {
	global $_GESTOR;

	if (!is_array($params)) $params = [];
	if (($_GESTOR['ajax-opcao'] ?? '') !== 'forms-search-autocomplete') return '';
	$expected_id = (string)($params['form_id'] ?? '');
	$requested_id = (string)($_REQUEST['ajaxRegistroId'] ?? '');
	if ($expected_id !== '' && $requested_id !== '' && $expected_id !== $requested_id) return '';

	$_GESTOR['ajax-json'] = forms_search_autocomplete_response($_REQUEST['params'] ?? []);
	return '';
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
