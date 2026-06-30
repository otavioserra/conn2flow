<?php
/**
 * Widget renderer público do módulo Forms.
 *
 * O widget usa o HTML/CSS salvo no banco, repete o bloco `item` para cada campo
 * declarado em `fields_schema.fields[]` e resolve condicionais por tipo de campo.
 */

function forms_get_version() {
	$modulo = json_decode(file_get_contents(__DIR__ . '/forms.json'), true);
	return isset($modulo['versao']) ? $modulo['versao'] : '1.0.0';
}

function forms_widget_bool($value) {
	if (is_bool($value)) return $value;
	if (is_int($value)) return $value !== 0;
	$value = strtolower(trim((string)$value));
	return ($value === 'true' || $value === '1' || $value === 'yes' || $value === 'on' || $value === 'required');
}

function forms_widget_schema($fields_schema) {
	$schema = json_decode($fields_schema ?: '{}', true);
	if (!is_array($schema)) $schema = [];
	if (!isset($schema['fields']) || !is_array($schema['fields'])) $schema['fields'] = [];
	return $schema;
}

function forms_widget_replace_var($html, $name, $value) {
	return preg_replace('/@?\[\['.preg_quote($name, '/').'\]\]@?/', (string)$value, $html);
}

function forms_widget_block($html, $name, $keep) {
	$pattern = '/<!--\s*'.preg_quote($name, '/').'\s*<\s*-->([\s\S]*?)<!--\s*'.preg_quote($name, '/').'\s*>\s*-->/i';
	return preg_replace($pattern, $keep ? '$1' : '', $html);
}

function forms_widget_options_html($field) {
	$options = $field['options'] ?? [];
	$type = $field['type'] ?? 'select';
	$name = $field['name'] ?? '';
	$required = forms_widget_bool($field['required'] ?? false) ? 'required' : '';

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

function forms_widget_render_field($template, $field) {
	$type = $field['type'] ?? 'text';
	$name = $field['name'] ?? '';
	$label = $field['label'] ?? $name;
	$placeholder = $field['placeholder'] ?? '';
	$required = forms_widget_bool($field['required'] ?? false) ? 'required' : '';

	$html = $template;
	$html = forms_widget_replace_var($html, 'item#label', htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#name', htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#placeholder', htmlspecialchars((string)$placeholder, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#type', htmlspecialchars(($type === 'textarea' || $type === 'select' || $type === 'radio' || $type === 'checkbox') ? 'text' : (string)$type, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#required', $required);
	$html = forms_widget_replace_var($html, 'item#options', forms_widget_options_html($field));

	$html = forms_widget_block($html, 'type-select', $type === 'select');
	$html = forms_widget_block($html, 'type-textarea', $type === 'textarea');
	$html = forms_widget_block($html, 'type-radio', $type === 'radio');
	$html = forms_widget_block($html, 'type-checkbox', $type === 'checkbox');
	$html = forms_widget_block($html, 'type-input', $type !== 'select' && $type !== 'textarea' && $type !== 'radio' && $type !== 'checkbox');

	return $html;
}

function forms_render($params) {
	global $_GESTOR;

	if (!is_array($params)) return '';

	$form_id = $params['form_id'] ?? ($params['grupo_slug'] ?? ($params['id'] ?? null));
	if (empty($form_id)) return '';

	$registro = banco_select([
		'unico' => true,
		'tabela' => 'forms',
		'campos' => ['id_forms', 'id', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head'],
		'extra' =>
			"WHERE id='".banco_escape_field($form_id)."'"
			." AND status='A'"
			." AND language='".$_GESTOR['linguagem-codigo']."'",
	]);

	if (!$registro) return '';

	$schema = forms_widget_schema($registro['fields_schema'] ?? '{}');

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
					." AND target='forms'"
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

	// Delega o fluxo de eventos e submissão AJAX para o controlador padrão da biblioteca de
	// formulários (honeypot, reCAPTCHA v2/v3, rate limiting, validações e mensagens do backend),
	// substituindo o script legado forms.widget.js. A config é injetada em gestor.form[id].
	gestor_incluir_biblioteca('formulario');
	formulario_controlador([
		'formId' => $registro['id'],
	]);

	if (function_exists('gestor_pagina_recursos_incluir')) {
		gestor_pagina_recursos_incluir([
			'css' => $css,
			'css_compiled' => $css_compiled,
			'html_extra_head' => $html_extra_head,
		]);
	}

	return forms_widget_render_inline([
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
function forms_render_editor_html($params) {
	if (!is_array($params)) return null;

	$form_id = $params['form_id'] ?? ($params['formId'] ?? ($params['id'] ?? null));
	if (empty($form_id)) return null;

	gestor_incluir_biblioteca('formulario');
	if (!function_exists('formulario_montar_js_vars')) return null;

	$forms_js_vars = formulario_montar_js_vars([$form_id]);
	if (!is_array($forms_js_vars) || !isset($forms_js_vars[$form_id])) return null;

	return $forms_js_vars[$form_id];
}

function forms_widget_render_inline($params) {
	$form_id = $params['form_id'] ?? ($params['grupo_slug'] ?? 'formulario');
	$html = $params['html'] ?? '';
	$schema = forms_widget_schema($params['fields_schema'] ?? '{}');
	$fields = $schema['fields'];

	$itemRegex = '/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i';
	if (preg_match($itemRegex, $html, $match)) {
		$itemTemplate = $match[1];
		$rendered = '';
		foreach ($fields as $field) {
			if (is_array($field)) $rendered .= forms_widget_render_field($itemTemplate, $field);
		}
		$html = preg_replace($itemRegex, $rendered, $html, 1);
	}

	$html = forms_widget_replace_var($html, 'form_id', htmlspecialchars((string)$form_id, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'form_action', htmlspecialchars((string)($schema['form_action'] ?? ''), ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'force_recaptcha', !empty($schema['force_recaptcha']) ? 'true' : 'false');

	return $html;
}
