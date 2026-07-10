/**
 * dashboard.toolbar.js
 *
 * Executa na PÁGINA HOSPEDEIRA (site público) quando o Dashboard Site Toolbar
 * é injetado para um usuário logado com permissão de edição.
 *
 * Responsabilidades (BATCH-075):
 *  - Meta 2: offset dinâmico da página + redimensionamento do iframe da toolbar
 *    (dropdown de módulos / barra de edição) — estratégia do seletor de linguagens.
 *  - Meta 3 (edição visual in-place REAL): ao "Editar Página", busca o conteúdo
 *    ORIGINAL renderizado com caixas de destaque nas variáveis/widgets
 *    (`site-toolbar-render`), troca em `#c2f-page-content` e ativa o **editor visual
 *    real** (`html-editor.js`) ESCOPADO a essa região (`contentRoot`) — com as caixas
 *    tracejadas de seleção, floating toolbar, styler de classes e DnD. As caixas
 *    (`.c2f-dyn-box`, `contenteditable=false`) são blocos atômicos protegidos. Ao
 *    "Salvar", extrai o HTML limpo (`getCleanHtml`), reconstrói os marcadores
 *    (`@[[var]]@` / `<!-- widgets#... -->`) das caixas e persiste via
 *    `site-toolbar-save`, recarregando a página.
 */
(function () {
	'use strict';

	var TOOLBAR_HEIGHT = 30;
	var TOOLBAR_ID = 'c2f-site-toolbar';
	var CONTENT_ID = 'c2f-page-content';
	var LAYOUT_ROOT_ID = 'c2f-layout-root';

	var c2fEditor = null;   // instância do html-editor escopada ao layout+conteúdo.
	var editLayoutId = '';  // layout_id da página em edição (para salvar em layouts).

	// ===== Offset / redimensionamento

	function setPageOffset(offset) {
		var el = document.documentElement;
		if (!el) { return; }
		var o = parseInt(offset, 10);
		if (isNaN(o) || o < TOOLBAR_HEIGHT) { o = TOOLBAR_HEIGHT; }
		el.style.setProperty('margin-top', o + 'px', 'important');
	}

	function applyOffset() { setPageOffset(TOOLBAR_HEIGHT); }

	function setToolbarHeight(height) {
		var tb = document.getElementById(TOOLBAR_ID);
		if (!tb) { return; }
		var h = parseInt(height, 10);
		if (isNaN(h) || h < TOOLBAR_HEIGHT) { h = TOOLBAR_HEIGHT; }
		tb.style.height = h + 'px';
	}

	// ===== Base URL (raiz) derivada do src do iframe da toolbar

	function getRaiz() {
		var tb = document.getElementById(TOOLBAR_ID);
		if (tb && tb.src) {
			var marker = 'dashboard-site-toolbar/';
			var idx = tb.src.indexOf(marker);
			if (idx !== -1) { return tb.src.substring(0, idx); }
		}
		if (window.gestor && window.gestor.raiz) { return window.gestor.raiz; }
		return '/';
	}

	function dashboardAjaxUrl() { return getRaiz() + 'dashboard/'; }

	// ===== Carregamento de dependências (jQuery + html-editor.js)

	function loadScriptOnce(src, id, cb) {
		if (id && document.getElementById(id)) { cb(); return; }
		var s = document.createElement('script');
		if (id) { s.id = id; }
		s.src = src;
		s.onload = function () { cb(); };
		s.onerror = function () { cb(new Error('Falha ao carregar ' + src)); };
		document.head.appendChild(s);
	}

	function ensureJQuery(cb) {
		if (window.jQuery) { cb(); return; }
		loadScriptOnce('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', 'c2f-he-jq', cb);
	}

	function loadCssOnce(href, id) {
		if (id && document.getElementById(id)) { return; }
		var l = document.createElement('link');
		if (id) { l.id = id; }
		l.rel = 'stylesheet';
		l.href = href;
		document.head.appendChild(l);
	}

	function loadScriptsSeq(urls, cb) {
		var i = 0;
		(function next() {
			if (i >= urls.length) { cb(); return; }
			loadScriptOnce(urls[i], 'c2f-cm-' + i, function () { i++; next(); });
		})();
	}

	// Carrega o CodeMirror (mesma versão/tema do editor admin) para o campo de código
	// do modal do editor visual funcionar in-place (senão fica um textarea puro).
	function ensureCodeMirror(cb) {
		if (window.CodeMirror) { cb(); return; }
		var base = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/';
		loadCssOnce(base + 'codemirror.min.css', 'c2f-cm-css');
		loadCssOnce(base + 'theme/tomorrow-night-bright.css', 'c2f-cm-theme');
		loadScriptsSeq([
			base + 'codemirror.min.js',
			base + 'addon/selection/active-line.js',
			base + 'addon/edit/matchbrackets.js',
			base + 'mode/xml/xml.js',
			base + 'mode/css/css.js',
			base + 'mode/htmlmixed/htmlmixed.js'
		], cb);
	}

	// Inicializa o CodeMirror no #element-code (criado pelo modal fallback do html-editor).
	// O openEditModal do html-editor.js já dá refresh ao exibir (o campo nasce oculto).
	function initCodeMirrorField() {
		if (window.CodeMirrorHtmlEditor || !window.CodeMirror) { return; }
		var el = document.getElementById('element-code');
		if (!el) { return; }
		window.CodeMirrorHtmlEditor = window.CodeMirror.fromTextArea(el, {
			lineNumbers: true,
			lineWrapping: true,
			styleActiveLine: true,
			matchBrackets: true,
			mode: 'htmlmixed',
			htmlMode: true,
			indentUnit: 4,
			theme: 'tomorrow-night-bright'
		});
		window.CodeMirrorHtmlEditor.setSize('100%', 400);
	}

	function instantiateEditor(content, tries) {
		tries = tries || 0;
		if (window.HtmlEditorClass) {
			try {
				c2fEditor = new window.HtmlEditorClass({ contentRoot: content });
				ensureCodeMirror(initCodeMirrorField);
			} catch (e) {
				window.console && console.error('Editor in-place:', e);
			}
			return;
		}
		if (tries < 60) {
			setTimeout(function () { instantiateEditor(content, tries + 1); }, 50);
		}
	}

	function activateEditor(content) {
		ensureJQuery(function () {
			// Impede o auto-init sobre document.body; instanciamos escopado ao conteúdo.
			window.__c2fHtmlEditorNoAutoInit = true;
			loadScriptOnce(getRaiz() + 'interface/html-editor.js?v=c2f1', 'c2f-he-script', function () {
				instantiateEditor(content, 0);
			});
		});
	}

	// ===== Edição in-place

	function startEdit(pageId) {
		var root = document.getElementById(LAYOUT_ROOT_ID) || document.getElementById(CONTENT_ID);
		if (!root || !pageId) { return; }
		var url = dashboardAjaxUrl() +
			'?ajax=1&ajaxOpcao=site-toolbar-render&page_id=' + encodeURIComponent(pageId);

		fetch(url, { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (json) {
				if (!json || json.status !== 'Ok' || !json.data) {
					window.alert((json && json.message) ? json.message : 'Falha ao carregar o editor da página.');
					return;
				}
				editLayoutId = json.data.layout_id || '';
				// Editando o #c2f-layout-root: usa o layout+conteúdo com caixas; senão só o conteúdo.
				if (root.id === LAYOUT_ROOT_ID && json.data.layout_html) {
					root.innerHTML = json.data.layout_html;
				} else {
					root.innerHTML = json.data.html;
				}
				activateEditor(root);
			})
			.catch(function () { window.alert('Erro ao carregar o editor da página.'); });
	}

	function decodeMarker(b64) {
		if (!b64) { return ''; }
		try { return decodeURIComponent(escape(window.atob(b64))); }
		catch (e) { try { return window.atob(b64); } catch (e2) { return ''; } }
	}

	// Reconstrói o HTML original: cada caixa de destaque volta ao seu marcador
	// (`@[[var]]@` / `<!-- widgets#... -->`); o restante é o HTML editado pelo editor visual.
	function reconstructOriginal(container) {
		var clone = container.cloneNode(true);
		var boxes = clone.querySelectorAll('.c2f-dyn-box');
		var map = {};
		var i = 0;
		Array.prototype.forEach.call(boxes, function (box) {
			var marker = decodeMarker(box.getAttribute('data-c2f-marker') || '');
			var token = 'C2FBOX' + (i++) + 'X';
			map[token] = marker;
			var t = document.createTextNode(token);
			if (box.parentNode) { box.parentNode.replaceChild(t, box); }
		});
		var html = clone.innerHTML;
		Object.keys(map).forEach(function (token) { html = html.split(token).join(map[token]); });
		return html;
	}

	function saveEdit(pageId) {
		var root = document.getElementById(LAYOUT_ROOT_ID) || document.getElementById(CONTENT_ID);
		if (!root || !pageId) { return; }

		// HTML limpo do editor visual (remove a UI do editor; widgets-wrapper→comentário).
		var cleanHtml = (c2fEditor && typeof c2fEditor.getCleanHtml === 'function')
			? c2fEditor.getCleanHtml()
			: root.innerHTML;

		var parsed = new DOMParser().parseFromString(cleanHtml, 'text/html');

		var contentHtml, layoutHtml = '';
		var pageContent = parsed.getElementById(CONTENT_ID);

		if (root.id === LAYOUT_ROOT_ID && pageContent) {
			// Conteúdo = #c2f-page-content → paginas; Layout = corpo com #c2f-page-content
			// trocado pelo marcador @[[pagina#corpo]]@ → layouts.
			contentHtml = reconstructOriginal(pageContent);
			var marker = parsed.createTextNode('__C2F_CORPO__');
			pageContent.parentNode.replaceChild(marker, pageContent);
			layoutHtml = reconstructOriginal(parsed.body).split('__C2F_CORPO__').join('@[[pagina#corpo]]@');
		} else {
			contentHtml = reconstructOriginal(parsed.body);
		}

		var body =
			'ajax=1&ajaxOpcao=site-toolbar-save' +
			'&page_id=' + encodeURIComponent(pageId) +
			'&html=' + encodeURIComponent(contentHtml);
		if (layoutHtml && editLayoutId) {
			body += '&layout_id=' + encodeURIComponent(editLayoutId) +
				'&layout_html=' + encodeURIComponent(layoutHtml);
		}

		fetch(dashboardAjaxUrl(), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body
		})
			.then(function (r) { return r.json(); })
			.then(function (json) {
				if (json && json.status === 'Ok') { window.location.reload(); }
				else { window.alert((json && json.message) ? json.message : 'Falha ao salvar a página.'); }
			})
			.catch(function () { window.alert('Erro ao salvar a página.'); });
	}

	function cancelEdit() {
		// Recarrega para descartar as edições e limpar a UI do editor (reset limpo).
		window.location.reload();
	}

	// Preview responsivo (screenPagina): redimensiona a área editável para simular a largura
	// do dispositivo. Alvo: #c2f-layout-root (quando editando layout) ou #c2f-page-content.
	function setEditScreen(width) {
		var root = document.getElementById('c2f-layout-root') || document.getElementById(CONTENT_ID);
		if (!root) { return; }
		var w = String(width || '100%');
		if (w === '100%' || w === '100') {
			root.style.maxWidth = '';
			root.style.width = '';
			root.style.margin = '';
			root.style.boxShadow = '';
			root.style.transition = '';
		} else {
			root.style.maxWidth = '100%';
			root.style.width = w;
			root.style.margin = '0 auto';
			root.style.boxShadow = '0 0 0 1px rgba(0,0,0,.12)';
			root.style.transition = 'width .2s';
		}
	}

	// ===== Painel "+" (adicionar elemento ou widget) — reusa os endpoints da lib html-editor.

	var addPanel = null;
	var widgetsCache = {};
	var ELEMENTOS = [
		{ type: 'p', label: 'Parágrafo' }, { type: 'h1', label: 'Título H1' }, { type: 'h2', label: 'Título H2' },
		{ type: 'h3', label: 'Título H3' }, { type: 'img', label: 'Imagem' }, { type: 'a', label: 'Link' },
		{ type: 'button', label: 'Botão' }, { type: 'div', label: 'Bloco' }, { type: 'section', label: 'Seção' }
	];

	function ajaxJson(url, cb) {
		fetch(url, { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb(null); });
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	function closeAddPanel() { if (addPanel) { addPanel.style.display = 'none'; } }

	function insertElement(type) {
		if (c2fEditor && typeof c2fEditor.enterInsertMode === 'function') {
			c2fEditor.enterInsertMode({ kind: 'element', elementType: String(type) });
		}
	}

	function insertWidget(module, slug, name) {
		if (c2fEditor && typeof c2fEditor.enterInsertMode === 'function') {
			c2fEditor.enterInsertMode({ kind: 'widget', widgetModule: String(module), widgetSlug: String(slug), widgetName: String(name || slug) });
		}
	}

	function toggleWidgetGroup(head) {
		var group = head.parentNode;
		var list = group.querySelector('.c2f-add-widget-list');
		var open = list.style.display !== 'block';
		list.style.display = open ? 'block' : 'none';
		if (open) {
			var module = group.getAttribute('data-module');
			if (!widgetsCache[module]) {
				list.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:2px 8px;">Carregando…</div>';
				ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-widgets-list&params[module]=' + encodeURIComponent(module), function (json) {
					var items = (json && json.data) ? json.data : [];
					widgetsCache[module] = items;
					if (!items.length) { list.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:2px 8px;">Vazio</div>'; return; }
					var h = '';
					items.forEach(function (it) {
						var nome = it.nome || it.id;
						h += '<div class="c2f-add-widget-item" data-module="' + esc(module) + '" data-slug="' + esc(it.id) +
							'" data-name="' + esc(nome) + '" style="padding:5px 8px;border-radius:6px;cursor:pointer;">' + esc(nome) + '</div>';
					});
					list.innerHTML = h;
				});
			}
		}
	}

	function loadWidgetCategories() {
		var groups = addPanel.querySelector('.c2f-add-widget-groups');
		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-widget-types', function (json) {
			var cats = (json && json.data) ? json.data : [];
			if (!cats.length) { groups.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Nenhum widget</div>'; return; }
			var h = '';
			cats.forEach(function (cat) {
				h += '<div class="c2f-add-widget-group" data-module="' + esc(cat.id) + '">' +
					'<div class="c2f-add-widget-head" style="padding:6px 8px;border-radius:6px;cursor:pointer;font-weight:600;">' + esc(cat.name) + '</div>' +
					'<div class="c2f-add-widget-list" style="padding-left:16px;display:none;"></div></div>';
			});
			groups.innerHTML = h;
		});
	}

	function buildAddPanel() {
		if (addPanel) { return addPanel; }
		addPanel = document.createElement('div');
		addPanel.id = 'c2f-add-panel';
		addPanel.style.cssText = 'position:fixed;z-index:2147483645;min-width:240px;max-width:300px;max-height:70vh;overflow:auto;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 28px rgba(0,0,0,.22);padding:8px;display:none;font:14px system-ui,sans-serif;color:#0f172a;';
		var h = '<div style="font:600 11px sans-serif;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:4px 0;">Elementos HTML</div>';
		ELEMENTOS.forEach(function (e) {
			h += '<div class="c2f-add-el" data-el="' + e.type + '" style="padding:6px 8px;border-radius:6px;cursor:pointer;">' + e.label + '</div>';
		});
		h += '<div style="border-top:1px solid #e2e8f0;margin:6px 0;"></div>';
		h += '<div style="font:600 11px sans-serif;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:4px 0;">Widgets do Sistema</div>';
		h += '<div class="c2f-add-widget-groups"><div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Carregando…</div></div>';
		addPanel.innerHTML = h;
		document.body.appendChild(addPanel);
		addPanel.addEventListener('mouseover', function (e) { var it = e.target.closest && e.target.closest('.c2f-add-el,.c2f-add-widget-head,.c2f-add-widget-item'); if (it) { it.style.background = '#f1f5f9'; } });
		addPanel.addEventListener('mouseout', function (e) { var it = e.target.closest && e.target.closest('.c2f-add-el,.c2f-add-widget-head,.c2f-add-widget-item'); if (it) { it.style.background = ''; } });
		addPanel.addEventListener('click', function (e) {
			var el = e.target.closest('.c2f-add-el');
			if (el) { insertElement(el.getAttribute('data-el')); closeAddPanel(); return; }
			var head = e.target.closest('.c2f-add-widget-head');
			if (head) { toggleWidgetGroup(head); return; }
			var item = e.target.closest('.c2f-add-widget-item');
			if (item) { insertWidget(item.getAttribute('data-module'), item.getAttribute('data-slug'), item.getAttribute('data-name')); closeAddPanel(); return; }
		});
		return addPanel;
	}

	function openAddPanel(x, y) {
		buildAddPanel();
		var px = Math.max(8, Math.min(parseInt(x, 10) || 8, window.innerWidth - 310));
		addPanel.style.left = px + 'px';
		addPanel.style.top = ((parseInt(y, 10) || 40) + 4) + 'px';
		addPanel.style.display = 'block';
		loadWidgetCategories();
	}

	// Fecha o painel "+" ao clicar fora.
	document.addEventListener('mousedown', function (e) {
		if (addPanel && addPanel.style.display === 'block' && (!e.target.closest || !e.target.closest('#c2f-add-panel'))) {
			closeAddPanel();
		}
	});

	// ===== Painel de Backups (restaurar versão do conteúdo) — ponto 5.

	var backupPanel = null;

	function closeBackupPanel() { if (backupPanel) { backupPanel.style.display = 'none'; } }

	function restoreBackup(id) {
		var content = document.getElementById(CONTENT_ID);
		if (!content || !id) { return; }
		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-backup-get&id=' + encodeURIComponent(id), function (json) {
			if (json && json.status === 'Ok' && json.data && typeof json.data.html === 'string') {
				content.innerHTML = json.data.html;
			} else {
				window.alert((json && json.message) ? json.message : 'Falha ao carregar o backup.');
			}
		});
	}

	function buildBackupPanel() {
		if (backupPanel) { return backupPanel; }
		backupPanel = document.createElement('div');
		backupPanel.id = 'c2f-backup-panel';
		backupPanel.style.cssText = 'position:fixed;z-index:2147483645;min-width:240px;max-width:340px;max-height:70vh;overflow:auto;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 28px rgba(0,0,0,.22);padding:8px;display:none;font:14px system-ui,sans-serif;color:#0f172a;';
		document.body.appendChild(backupPanel);
		backupPanel.addEventListener('mouseover', function (e) { var it = e.target.closest && e.target.closest('.c2f-backup-item'); if (it) { it.style.background = '#f1f5f9'; } });
		backupPanel.addEventListener('mouseout', function (e) { var it = e.target.closest && e.target.closest('.c2f-backup-item'); if (it) { it.style.background = ''; } });
		backupPanel.addEventListener('click', function (e) {
			var it = e.target.closest('.c2f-backup-item');
			if (it) { restoreBackup(it.getAttribute('data-id')); closeBackupPanel(); }
		});
		return backupPanel;
	}

	function openBackupPanel(x, y, pageId) {
		buildBackupPanel();
		var px = Math.max(8, Math.min(parseInt(x, 10) || 8, window.innerWidth - 350));
		backupPanel.style.left = px + 'px';
		backupPanel.style.top = ((parseInt(y, 10) || 40) + 4) + 'px';
		backupPanel.style.display = 'block';
		backupPanel.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Carregando…</div>';
		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-backups&page_id=' + encodeURIComponent(pageId || ''), function (json) {
			var backups = (json && json.data) ? json.data : [];
			if (!backups.length) { backupPanel.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Nenhum backup</div>'; return; }
			var h = '<div style="font:600 11px sans-serif;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:4px 0;">Restaurar backup do conteúdo</div>';
			backups.forEach(function (b) {
				h += '<div class="c2f-backup-item" data-id="' + esc(b.id) + '" style="padding:6px 8px;border-radius:6px;cursor:pointer;">v' + esc(b.versao) + ' — ' + esc(b.data) + '</div>';
			});
			backupPanel.innerHTML = h;
		});
	}

	// Fecha o painel de backups ao clicar fora.
	document.addEventListener('mousedown', function (e) {
		if (backupPanel && backupPanel.style.display === 'block' && (!e.target.closest || !e.target.closest('#c2f-backup-panel'))) {
			closeBackupPanel();
		}
	});

	// ===== Mensagens da toolbar (iframe)

	window.addEventListener('message', function (ev) {
		if (ev.origin !== window.location.origin) { return; }
		var data = ev.data || {};
		if (!data || !data.type) { return; }

		switch (data.type) {
			case 'c2f-toolbar:resize':
				setToolbarHeight(data.height);
				if (typeof data.offset !== 'undefined') { setPageOffset(data.offset); }
				break;
			case 'c2f-toolbar:edit-start':
				startEdit(data.page_id);
				break;
			case 'c2f-toolbar:edit-save':
				saveEdit(data.page_id);
				break;
			case 'c2f-toolbar:edit-cancel':
				cancelEdit();
				break;
			case 'c2f-toolbar:edit-undo':
				if (c2fEditor && typeof c2fEditor.undo === 'function') { c2fEditor.undo(); }
				break;
			case 'c2f-toolbar:edit-redo':
				if (c2fEditor && typeof c2fEditor.redo === 'function') { c2fEditor.redo(); }
				break;
			case 'c2f-toolbar:edit-insert':
				if (c2fEditor && typeof c2fEditor.enterInsertMode === 'function' && data.elementType) {
					c2fEditor.enterInsertMode({ kind: 'element', elementType: String(data.elementType) });
				}
				break;
			case 'c2f-toolbar:edit-screen':
				setEditScreen(data.width);
				break;
			case 'c2f-toolbar:edit-add':
				openAddPanel(data.x, data.y);
				break;
			case 'c2f-toolbar:edit-backups':
				openBackupPanel(data.x, data.y, data.page_id);
				break;
			default:
				break;
		}
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', applyOffset);
	} else {
		applyOffset();
	}
})();
