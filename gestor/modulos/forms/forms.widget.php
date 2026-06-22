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

	if (!$registro || trim($registro['html'] ?? '') === '') return '';

	gestor_pagina_javascript_incluir([
		'tipo' => 'widget',
		'modulo_id' => 'forms',
		'versao' => forms_get_version(),
	]);

	if (function_exists('gestor_pagina_recursos_incluir')) {
		gestor_pagina_recursos_incluir([
			'css' => $registro['css'] ?? '',
			'css_compiled' => $registro['css_compiled'] ?? '',
			'html_extra_head' => $registro['html_extra_head'] ?? '',
		]);
	}

	return forms_widget_render_inline([
		'form_id' => $registro['id'],
		'html' => $registro['html'],
		'fields_schema' => $registro['fields_schema'] ?? '{}',
	]);
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
