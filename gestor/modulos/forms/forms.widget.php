<?php
/**
 * Widget renderer público do módulo Forms.
 *
 * O widget usa o HTML/CSS salvo no banco, repete o bloco `item` para cada campo
 * declarado em `fields_schema.fields[]` e resolve condicionais por tipo de campo.
 */

function forms_get_version() {
	$modulo = json_decode(file_get_contents(__DIR__ . '/forms.json'), true);
	return $modulo['asset_version'] ?? $modulo['versao'] ?? '1.0.0';
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

function forms_widget_form_action($form_action, $url_raiz = '/') {
	$url_raiz = is_scalar($url_raiz) ? rtrim(trim((string)$url_raiz), '/') . '/' : '/';
	$form_action = is_scalar($form_action) ? trim((string)$form_action) : '';
	if ($form_action === '') $form_action = 'forms-submissions-process/';

	return $url_raiz . ltrim($form_action, '/');
}

function forms_widget_replace_var($html, $name, $value) {
	return preg_replace('/@?\[\['.preg_quote($name, '/').'\]\]@?/', (string)$value, $html);
}

function forms_widget_block($html, $name, $keep) {
	$pattern = '/<!--\s*'.preg_quote($name, '/').'\s*<\s*-->([\s\S]*?)<!--\s*'.preg_quote($name, '/').'\s*>\s*-->/i';
	return preg_replace($pattern, $keep ? '$1' : '', $html);
}

function forms_widget_options_html($field, $template = '') {
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

		// req-141: o markup da opção vem do TEMPLATE, não daqui. Classe escrita em PHP é invisível
		// para o compilador Tailwind, que varre recursos — e o campo renderiza sem estilo no site.
		if ($type === 'radio' || $type === 'checkbox') {
			$input_name = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8').($type === 'checkbox' ? '[]' : '');
			$modelo = trim((string)modelo_tag_val($template, '<!-- option-choice < -->', '<!-- option-choice > -->'));

			// Template gravado antes desta mudança não tem o marcador. Sem este fallback, todo campo
			// radio/checkbox de projeto existente renderizaria VAZIO — regressão silenciosa no site.
			if ($modelo === '') {
				$modelo = '<label class="inline-flex items-center mr-4 mb-2 cursor-pointer">'
					.'<input type="@[[option#type]]@" name="@[[option#name]]@" value="@[[option#value]]@"'
					.' class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @[[option#required]]@>'
					.'<span class="ml-2 text-sm text-gray-700">@[[option#label]]@</span></label>';
			}

			$item = str_replace(
				Array('@[[option#type]]@', '@[[option#name]]@', '@[[option#value]]@', '@[[option#required]]@', '@[[option#label]]@'),
				Array($type, $input_name, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $required, htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8')),
				$modelo
			);

			$html .= $item;
		} else {
			$modelo = trim((string)modelo_tag_val($template, '<!-- option-select < -->', '<!-- option-select > -->'));
			if ($modelo === '') {
				$modelo = '<option value="@[[option#value]]@">@[[option#label]]@</option>';
			}

			$html .= str_replace(
				Array('@[[option#value]]@', '@[[option#label]]@'),
				Array(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8')),
				$modelo
			);
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
function forms_widget_parse_limits($options) {
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
function forms_widget_hidden_default($options) {
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
function forms_widget_inject_tag_attrs($html, $tag, $attrs) {
	if (trim($attrs) === '') return $html;
	return preg_replace_callback('/<'.$tag.'\b([^>]*?)(\/?)>/i', function ($m) use ($tag, $attrs) {
		return '<'.$tag.rtrim($m[1]).' '.trim($attrs).$m[2].'>';
	}, $html, 1);
}

/**
 * Injeta `value="..."` na primeira tag `<input>` quando ainda não houver atributo value.
 * Usado para o valor padrão do campo `hidden` em templates legados sem o placeholder.
 */
function forms_widget_inject_value_if_absent($html, $value) {
	return preg_replace_callback('/<input\b([^>]*?)>/i', function ($m) use ($value) {
		if (preg_match('/\bvalue\s*=/i', $m[1])) return $m[0];
		return '<input'.rtrim($m[1]).' value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'">';
	}, $html, 1);
}

/**
 * Acrescenta uma classe CSS à primeira tag de abertura informada (preserva classes existentes).
 */
function forms_widget_add_tag_class($html, $tag, $class) {
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
 * O botão usa a classe `.forms-password-toggle` e o ícone `eye` (Fomantic) com estilos inline
 * para funcionar também em páginas Tailwind / CSS Vanilla sem depender do framework ativo.
 */
function forms_widget_wrap_password($html, $template = '') {
	// req-141: o markup do botão vem do TEMPLATE. Mesmo sem utility Tailwind aqui, HTML em PHP é o
	// que a norma proíbe — e é por esse caminho que classe some do alcance do compilador.
	$modelo = trim((string)modelo_tag_val($template, '<!-- password-toggle < -->', '<!-- password-toggle > -->'));

	// Template anterior a esta mudança não declara o bloco: sem o fallback, o campo de senha perde o
	// botão de mostrar/ocultar em todo projeto já existente. As classes aqui são hooks de JS, não
	// utilities — o compilador Tailwind não precisa enxergá-las.
	if ($modelo === '') {
		$modelo = '<div class="forms-password-wrapper" style="position:relative;">@[[password#input]]@'
			.'<button type="button" class="forms-password-toggle" aria-label="@[[password#aria]]@" tabindex="-1"'
			.' style="position:absolute;top:50%;right:0.75rem;transform:translateY(-50%);background:transparent;'
			.'border:0;cursor:pointer;padding:0;line-height:1;color:#6b7280;">'
			.'<svg class="forms-password-icon-eye" viewBox="0 0 24 24" width="18" height="18" fill="none"'
			.' stroke="currentColor" stroke-width="1.8" aria-hidden="true">'
			.'<path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"></path>'
			.'<circle cx="12" cy="12" r="3.25"></circle></svg>'
			.'<svg class="forms-password-icon-eye-slash" viewBox="0 0 24 24" width="18" height="18" fill="none"'
			.' stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="display:none;">'
			.'<path d="M3 3l18 18"></path>'
			.'<path d="M10.58 10.58A2 2 0 0 0 10 12a2 2 0 0 0 3.42 1.42"></path>'
			.'<path d="M9.88 5.09A10.94 10.94 0 0 1 12 4.88c6 0 9.75 7.12 9.75 7.12a20.3 20.3 0 0 1-4.04 4.95"></path>'
			.'<path d="M6.61 6.61A20.78 20.78 0 0 0 2.25 12S6 19.12 12 19.12c1.76 0 3.3-.38 4.65-.97"></path></svg>'
			.'</button></div>';
	}

	return preg_replace_callback('/<input\b[^>]*>/i', function ($m) use ($modelo) {
		$aria = function_exists('gestor_variaveis')
			? (string)gestor_variaveis(Array('modulo' => 'forms', 'id' => 'password-toggle-aria'))
			: '';

		return str_replace(
			Array('@[[password#input]]@', '@[[password#aria]]@'),
			Array($m[0], $aria),
			$modelo
		);
	}, $html, 1);
}

/**
 * Extrai apenas a primeira tag `<input>` do HTML (descarta label/wrapper).
 * Usado para campos `hidden`, que não devem exibir rótulo ou ocupar espaço visual.
 */
function forms_widget_extract_input($html) {
	if (preg_match('/<input\b[^>]*>/i', $html, $m)) return $m[0];
	return $html;
}

function forms_widget_render_field($template, $field) {
	$type = $field['type'] ?? 'text';
	$name = $field['name'] ?? '';
	$label = $field['label'] ?? $name;
	$placeholder = $field['placeholder'] ?? '';
	$required = forms_widget_bool($field['required'] ?? false) ? 'required' : '';

	// Tipos nativos que reaproveitam o bloco de input simples (type-input).
	$inputType = ($type === 'textarea' || $type === 'select' || $type === 'radio' || $type === 'checkbox') ? 'text' : (string)$type;

	// Valor padrão do campo (constante para hidden; vazio para os demais).
	$value = ($type === 'hidden') ? forms_widget_hidden_default($field['options'] ?? []) : '';

	$html = $template;
	$html = forms_widget_replace_var($html, 'item#label', htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#name', htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#placeholder', htmlspecialchars((string)$placeholder, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#type', htmlspecialchars($inputType, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#required', $required);
	$html = forms_widget_replace_var($html, 'item#value', htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'item#options', forms_widget_options_html($field, $template));

	$html = forms_widget_block($html, 'type-select', $type === 'select');
	$html = forms_widget_block($html, 'type-textarea', $type === 'textarea');
	$html = forms_widget_block($html, 'type-radio', $type === 'radio');
	$html = forms_widget_block($html, 'type-checkbox', $type === 'checkbox');
	$html = forms_widget_block($html, 'type-input', $type !== 'select' && $type !== 'textarea' && $type !== 'radio' && $type !== 'checkbox');

	// ===== Atributos de limite/validação derivados do campo "Opções".
	$limits = forms_widget_parse_limits($field['options'] ?? []);
	if ($type === 'text' || $type === 'textarea') {
		$tag = ($type === 'textarea') ? 'textarea' : 'input';
		$attrs = '';
		if ($limits['min'] !== null && $limits['min'] !== '') $attrs .= ' minlength="'.htmlspecialchars($limits['min'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['max'] !== null && $limits['max'] !== '') $attrs .= ' maxlength="'.htmlspecialchars($limits['max'], ENT_QUOTES, 'UTF-8').'"';
		$html = forms_widget_inject_tag_attrs($html, $tag, $attrs);
	} elseif ($type === 'number') {
		$attrs = '';
		if ($limits['min'] !== null && $limits['min'] !== '') $attrs .= ' min="'.htmlspecialchars($limits['min'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['max'] !== null && $limits['max'] !== '') $attrs .= ' max="'.htmlspecialchars($limits['max'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['step'] !== null && $limits['step'] !== '') $attrs .= ' step="'.htmlspecialchars($limits['step'], ENT_QUOTES, 'UTF-8').'"';
		$html = forms_widget_inject_tag_attrs($html, 'input', $attrs);
	} elseif ($type === 'date') {
		$attrs = '';
		if ($limits['min'] !== null && $limits['min'] !== '') $attrs .= ' min="'.htmlspecialchars($limits['min'], ENT_QUOTES, 'UTF-8').'"';
		if ($limits['max'] !== null && $limits['max'] !== '') $attrs .= ' max="'.htmlspecialchars($limits['max'], ENT_QUOTES, 'UTF-8').'"';
		$html = forms_widget_inject_tag_attrs($html, 'input', $attrs);
		// Marca para a melhoria progressiva (date picker Fomantic quando disponível).
		$html = forms_widget_add_tag_class($html, 'input', 'forms-date-picker');
	}

	// ===== Tratamentos específicos por tipo.
	if ($type === 'hidden') {
		$html = forms_widget_inject_value_if_absent($html, $value);
		$html = forms_widget_extract_input($html);
	} elseif ($type === 'password') {
		// Sem `autocomplete`, o Chrome alerta no console e pode sugerir a senha salva do site num
		// campo de cadastro. `new-password` é o valor correto para criação/confirmação de senha.
		$html = forms_widget_inject_tag_attrs($html, 'input', ' autocomplete="new-password"');
		$html = forms_widget_wrap_password($html, $template);
	}

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
		// req-077 §2: `css_precompiled` carrega TODAS as utilities Tailwind do formulário desde a
		// pré-compilação por recurso; sem ele o widget renderiza com classes que não existem em CSS
		// nenhum e os campos aparecem sem padding, borda ou arredondamento.
		// A coluna existe em `templates`, `componentes`, `paginas` e
		// `layouts`, mas NÃO em `forms` — a migração da pré-compilação por recurso não
		// alcançou esta tabela. Projetar a coluna sem guard derruba a consulta inteira (HTTP 500
		// na rota pública). Enquanto a coluna não existir, o formulário customizado no editor
		// não tem sidecar próprio e o visual vem do template referenciado, logo abaixo.
		'campos' => banco_campo_existe('css_precompiled', 'forms')
			? ['id_forms', 'id', 'fields_schema', 'html', 'css', 'css_precompiled', 'css_compiled', 'html_extra_head']
			: ['id_forms', 'id', 'fields_schema', 'html', 'css', 'css_compiled', 'html_extra_head'],
		'extra' =>
			"WHERE id='".banco_escape_field($form_id)."'"
			." AND status='A'"
			." AND language='".$_GESTOR['linguagem-codigo']."'",
	]);

	if (!$registro) return '';

	$schema = forms_widget_schema($registro['fields_schema'] ?? '{}');

	$html = (string)($registro['html'] ?? '');
	$css = (string)($registro['css'] ?? '');
	$css_precompiled = (string)($registro['css_precompiled'] ?? '');
	$css_compiled = (string)($registro['css_compiled'] ?? '');
	$html_extra_head = (string)($registro['html_extra_head'] ?? '');

	// Fallback de template, por dois motivos distintos:
	//  1. HTML vazio — formulário que usa o modelo padrão (sem customização salva) grava html/css
	//     vazios no banco e todo o conteúdo vem do template referenciado em `template_id`;
	//  2. req-077 §2: SEM `css_precompiled` próprio — o HTML customizado no editor nasce como CÓPIA
	//     do template, então são as utilities Tailwind DELE que vestem o formulário. Como a tabela
	//     `forms` não tem a coluna, esta é a única origem possível hoje; sem isso um formulário
	//     customizado renderiza os campos sem padding, borda nem arredondamento.
	$template_id = (string)($schema['template_id'] ?? '');
	if ($template_id !== '' && (trim($html) === '' || trim($css_precompiled) === '')) {
		{
			$template = banco_select([
				'unico' => true,
				'tabela' => 'templates',
				// req-077 §2: o formulário que usa o modelo padrão grava html/css vazios e todo o
				// visual vem daqui — inclusive as utilities pré-compiladas.
				'campos' => ['html', 'css', 'css_precompiled', 'css_compiled', 'html_extra_head'],
				'extra' =>
					"WHERE id='".banco_escape_field($template_id)."'"
					." AND target='forms'"
					." AND status='A'"
					." AND language='".$_GESTOR['linguagem-codigo']."'",
			]);
			if ($template) {
				// Conteúdo só é substituído quando o formulário não tem HTML próprio; o CSS
				// pré-compilado é herdado em qualquer caso, porque é dele que vêm as utilities.
				if (trim($html) === '') {
					$html = (string)($template['html'] ?? '');
					if (trim($css) === '') $css = (string)($template['css'] ?? '');
					if (trim($css_compiled) === '') $css_compiled = (string)($template['css_compiled'] ?? '');
					if (trim($html_extra_head) === '') $html_extra_head = (string)($template['html_extra_head'] ?? '');
				}
				if (trim($css_precompiled) === '') $css_precompiled = (string)($template['css_precompiled'] ?? '');
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
			'css_precompiled' => $css_precompiled,
			'css_precompiled_role' => 'dependency-precompiled',
			'css_compiled' => $css_compiled,
			'html_extra_head' => $html_extra_head,
		]);
	}

	// Script controlador do widget. Ele acompanha o formulário em todos os contextos (preview do
	// Editor HTML, widget embutido numa página qualquer e páginas de fluxo), porque o widget é a
	// unidade que módulos consumidores podem interceptar.
	//
	// No site publicado a config já foi injetada acima por `formulario_controlador`; o próprio
	// script detecta isso (guarda por `gestor.form[formId]`) e não busca config nem recarrega
	// bibliotecas. Sem essa guarda, a página pública chamava a rota administrativa `/forms/` (401)
	// e carregava `interface.js` por cima do layout público.
	gestor_pagina_javascript_incluir(Array(
		'tipo' => 'widget',
		'modulo_id' => 'forms',
		'versao' => forms_get_version(),
	));

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
	global $_GESTOR;

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
	$form_action = forms_widget_form_action($schema['form_action'] ?? '', $_GESTOR['url-raiz'] ?? '/');
	$html = forms_widget_replace_var($html, 'form_action', htmlspecialchars($form_action, ENT_QUOTES, 'UTF-8'));
	$html = forms_widget_replace_var($html, 'force_recaptcha', !empty($schema['force_recaptcha']) ? 'true' : 'false');

	return $html;
}
