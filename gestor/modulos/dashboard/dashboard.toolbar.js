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
			base + 'mode/javascript/javascript.js',
			base + 'mode/markdown/markdown.js',
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
				// `raiz` habilita o image-picker autônomo do modal (item 3) — o motor monta um
				// iframe → admin-arquivos e preenche o #element-src com o arquivo selecionado.
				c2fEditor = new window.HtmlEditorClass({ contentRoot: content, raiz: getRaiz() });
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
			loadScriptOnce(getRaiz() + 'interface/html-editor.js?v=c2f10', 'c2f-he-script', function () {
				instantiateEditor(content, 0);
			});
		});
	}

	// ===== Motor de mapeamento inteligente (BATCH-077)
	//
	// Em vez de substituir destrutivamente o innerHTML da página viva (o que quebra scripts,
	// CSS e estado JS do site), PRESERVAMOS o DOM VIVO e apenas o ANOTAMOS. O HTML ORIGINAL
	// da página/layout (com os marcadores `@[[var]]@` e `<!-- widgets#... -->` intactos) é
	// guardado num container oculto `#paginaHTMLAntesEdicao`; percorremos as duas árvores em
	// paralelo (viva × original) e:
	//   - variável em ATRIBUTO  → marca a tag viva com `data-c2f-variable="ID_VAR_N"` e guarda
	//     `{param, variable, valor}` em `varMap` (o valor resolvido continua visível no editor);
	//   - variável em TEXTO      → envolve o trecho vivo numa caixa protegida (`.c2f-var-box`);
	//   - WIDGET                 → envolve a expansão viva numa caixa atômica (`.c2f-widget-box`).
	// As caixas guardam o marcador original em base64 (`data-c2f-marker`). No salvar, atributos
	// e caixas são reconstruídos de volta às notações originais.

	var BACKUP_ID = 'paginaHTMLAntesEdicao';
	var varMap = {};   // ID_VAR_N -> { param, variable (template cru do atributo), valor (resolvido) }
	var varSeq = 0;
	var mapRoot = null; // raiz editável passada ao mapTree (guarda p/ o mapeamento no pai — item 1).

	function b64encode(str) {
		try { return window.btoa(unescape(encodeURIComponent(str))); }
		catch (e) { try { return window.btoa(str); } catch (e2) { return ''; } }
	}

	function escapeRe(s) { return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

	// Marcadores `@[[...]]@` / `[[...]]` (arrobas de cerco opcionais). Novo objeto por chamada
	// para não vazar `lastIndex` entre usos concorrentes.
	function markerRe() { return /@?\[\[[\s\S]+?\]\]@?/g; }
	function hasMarker(s) { return markerRe().test(String(s == null ? '' : s)); }

	function isWidgetOpen(node) {
		return node && node.nodeType === 8 && /^\s*widgets#(.+?)\s*<\s*$/.test(node.nodeValue || '');
	}
	function widgetSig(node) {
		var m = (node.nodeValue || '').match(/^\s*widgets#(.+?)\s*<\s*$/);
		return m ? m[1] : '';
	}
	function isWidgetClose(node, sig) {
		if (!node || node.nodeType !== 8) { return false; }
		var m = (node.nodeValue || '').match(/^\s*widgets#(.+?)\s*>\s*$/);
		return !!(m && m[1] === sig);
	}
	// Extrai módulo (type) e slug da assinatura do widget `MODULO->FUNCAO({"grupo_slug":"x"})`
	// (espelha parseWidgetSignature do html-editor.js) — usados nos atributos data-widget-*.
	function parseWidgetSignature(signature) {
		var m = String(signature || '').match(/^(.+?)->(\w+)\((.*)\)$/);
		var type = signature, slug = '';
		if (m) {
			type = m[1].trim();
			try { var params = JSON.parse(m[3]); slug = params.grupo_slug || ''; } catch (e) { /* não-JSON */ }
		}
		return { type: type, slug: slug };
	}
	function nodeToHtml(node) {
		if (!node) { return ''; }
		if (node.nodeType === 1) { return node.outerHTML; }
		if (node.nodeType === 3) { return node.nodeValue; }
		if (node.nodeType === 8) { return '<!--' + (node.nodeValue || '') + '-->'; }
		return '';
	}

	function makeBox(tipo, marker) {
		var box = document.createElement(tipo === 'widget' ? 'div' : 'span');
		box.className = 'c2f-dyn-box c2f-' + tipo + '-box';
		box.setAttribute('data-c2f-marker', b64encode(marker));
		box.setAttribute('contenteditable', 'false');
		return box;
	}

	// Marca os atributos do elemento vivo cujo valor original contém variável(is).
	function mapAttributes(liveEl, rawEl) {
		if (!rawEl.attributes) { return; }
		for (var i = 0; i < rawEl.attributes.length; i++) {
			var a = rawEl.attributes[i];
			if (!hasMarker(a.value)) { continue; }
			var id = 'ID_VAR_' + (++varSeq);
			var prev = liveEl.getAttribute('data-c2f-variable');
			liveEl.setAttribute('data-c2f-variable', prev ? prev + ' ' + id : id);
			varMap[id] = {
				param: a.name,
				variable: a.value,                  // template cru do atributo (com @[[...]]@)
				valor: liveEl.getAttribute(a.name)  // valor resolvido exibido no editor
			};
		}
	}

	// Quebra um nó de texto vivo em: texto literal + caixa(s) de variável, usando o template cru.
	function annotateTextVars(parent, liveTextNode, rawTemplate) {
		var re = markerRe(), m, last = 0, reStr = '^';
		var markers = [];
		while ((m = re.exec(rawTemplate)) !== null) {
			reStr += escapeRe(rawTemplate.slice(last, m.index)) + '([\\s\\S]*?)';
			markers.push(m[0]);
			last = m.index + m[0].length;
			if (m.index === re.lastIndex) { re.lastIndex++; }
		}
		reStr += escapeRe(rawTemplate.slice(last)) + '$';
		var mm;
		try { mm = new RegExp(reStr).exec(String(liveTextNode.nodeValue)); } catch (e) { mm = null; }
		if (!mm) { return; } // não casou → mantém o vivo intacto (best-effort)

		var frag = document.createDocumentFragment();
		re = markerRe(); last = 0; var gi = 1, mk;
		while ((mk = re.exec(rawTemplate)) !== null) {
			var lit = rawTemplate.slice(last, mk.index);
			if (lit) { frag.appendChild(document.createTextNode(lit)); }
			var box = makeBox('var', mk[0]);
			box.appendChild(document.createTextNode(mm[gi++] != null ? mm[gi - 1] : ''));
			frag.appendChild(box);
			last = mk.index + mk[0].length;
			if (mk.index === re.lastIndex) { re.lastIndex++; }
		}
		var tail = rawTemplate.slice(last);
		if (tail) { frag.appendChild(document.createTextNode(tail)); }
		parent.replaceChild(frag, liveTextNode);
	}

	// Varredura paralela viva × original. Widgets são delimitados pelos PRÓPRIOS comentários
	// `<!-- widgets#SIG < --> … <!-- widgets#SIG > -->`, que o modo de edição preserva no DOM vivo
	// (ver gestor_pagina_widgets). Variáveis (atributo/texto) são mapeadas por alinhamento estrutural.
	function mapTree(liveParent, rawParent) {
		var live = Array.prototype.slice.call(liveParent.childNodes);
		var raw = Array.prototype.slice.call(rawParent.childNodes);
		var li = 0, ri = 0;

		while (ri < raw.length) {
			var rnode = raw[ri];

			// ----- Widget: delimitado pelos comentários `<!-- widgets#SIG < --> … > -->` que o modo
			//   de edição preserva TAMBÉM no DOM vivo. A fronteira é EXATA (sem heurística): casamos o
			//   comentário de abertura vivo pela mesma assinatura (a partir de `li`, o que separa
			//   naturalmente widgets duplicados/consecutivos) e marcamos os nós entre open/close.
			//   Modo-pai (item 1): se o widget é o ÚNICO conteúdo do contêriner, marca o PAI.
			if (rnode.nodeType === 8 && isWidgetOpen(rnode)) {
				var sig = widgetSig(rnode);
				var rClose = ri + 1;
				while (rClose < raw.length && !isWidgetClose(raw[rClose], sig)) { rClose++; }

				// Marcador cru (open + mockup + close) — preserva o mockup do designer no save.
				var markerHtml = nodeToHtml(rnode);
				for (var k = ri + 1; k < rClose && k < raw.length; k++) { markerHtml += nodeToHtml(raw[k]); }
				if (rClose < raw.length) { markerHtml += nodeToHtml(raw[rClose]); }

				// Fronteira EXATA no vivo: 1º comentário de abertura com a mesma assinatura a partir de li.
				var lOpen = -1;
				for (var w = li; w < live.length; w++) {
					if (live[w].nodeType === 8 && isWidgetOpen(live[w]) && widgetSig(live[w]) === sig) { lOpen = w; break; }
				}
				if (lOpen >= 0) {
					var lClose = lOpen + 1;
					while (lClose < live.length && !isWidgetClose(live[lClose], sig)) { lClose++; }

					var rootEls = [];
					for (var b = lOpen + 1; b < lClose && b < live.length; b++) {
						if (live[b] && live[b].nodeType === 1) { rootEls.push(live[b]); }
					}

					var parsed = parseWidgetSignature(sig);
					var wgid = 'ID_WIDGET_' + (++varSeq);

					// O widget é o único conteúdo do contêiner? (nenhum elemento/texto/outro widget
					// fora da faixa [lOpen,lClose]). Nesse caso marcamos o PAI (item 1).
					var otherContent = false;
					for (var c = 0; c < live.length; c++) {
						if (c >= lOpen && c <= lClose) { continue; }
						var nn = live[c];
						if (!nn) { continue; }
						if (nn.nodeType === 1) { otherContent = true; break; }
						if (nn.nodeType === 3 && String(nn.nodeValue).trim() !== '') { otherContent = true; break; }
						if (nn.nodeType === 8 && isWidgetOpen(nn)) { otherContent = true; break; }
					}
					var parentOk = liveParent.nodeType === 1 && liveParent !== mapRoot && liveParent !== document.body
						&& liveParent.id !== 'c2f-page-content' && liveParent.id !== 'c2f-layout-root'
						&& liveParent.id !== 'c2f-raw-content';

					if (rootEls.length > 1 && !otherContent && parentOk) {
						// Modo-pai: o innerHTML do pai é trocado pelo marcador no save (preserva a tag).
						liveParent.setAttribute('data-c2f-widget-id', wgid);
						liveParent.setAttribute('data-c2f-widget-parent', '1');
						liveParent.setAttribute('data-c2f-widget-root', '1');
						liveParent.setAttribute('data-c2f-marker', b64encode(markerHtml));
						liveParent.setAttribute('data-widget-type', parsed.type);
						liveParent.setAttribute('data-widget-slug', parsed.slug);
						liveParent.setAttribute('contenteditable', 'false');
						try {
							var pp = window.getComputedStyle(liveParent).position;
							if (pp === 'static' || !pp || pp === '') { liveParent.style.position = 'relative'; }
						} catch (e) { /* noop */ }
					} else if (rootEls.length) {
						// Marca os próprios elementos-raiz (sem wrapper — BATCH-078 r2): todos ganham
						// `data-c2f-widget-id`; o 1º recebe marcador + tipo/slug + widget-root.
						for (var ei = 0; ei < rootEls.length; ei++) {
							var wnode = rootEls[ei];
							wnode.setAttribute('data-c2f-widget-id', wgid);
							wnode.setAttribute('contenteditable', 'false');
							if (ei === 0) {
								wnode.setAttribute('data-c2f-widget-root', '1');
								wnode.setAttribute('data-c2f-marker', b64encode(markerHtml));
								wnode.setAttribute('data-widget-type', parsed.type);
								wnode.setAttribute('data-widget-slug', parsed.slug);
								try {
									var pos = window.getComputedStyle(wnode).position;
									if (pos === 'static' || !pos || pos === '') { wnode.style.position = 'relative'; }
								} catch (e) { /* noop */ }
							}
						}
					} else {
						// Expansão sem elemento-raiz (só texto) → caixa atômica (legado).
						var startNode = live[lOpen + 1];
						var wbox = makeBox('widget', markerHtml);
						if (startNode && startNode.parentNode === liveParent) {
							liveParent.insertBefore(wbox, startNode);
							for (var b2 = lOpen + 1; b2 < lClose; b2++) {
								if (live[b2] && live[b2].parentNode === liveParent) { wbox.appendChild(live[b2]); }
							}
						}
					}

					// Remove os comentários do widget do DOM vivo (fronteira já consumida).
					if (live[lOpen] && live[lOpen].parentNode) { live[lOpen].parentNode.removeChild(live[lOpen]); }
					if (live[lClose] && live[lClose].parentNode) { live[lClose].parentNode.removeChild(live[lClose]); }

					li = lClose + 1;
				}
				ri = (rClose < raw.length) ? rClose + 1 : raw.length;
				continue;
			}

			// ----- Elemento: mapeia atributos + recursa nos filhos.
			if (rnode.nodeType === 1) {
				var lel = -1;
				for (var e = li; e < live.length; e++) { if (live[e].nodeType === 1) { lel = e; break; } }
				if (lel >= 0 && live[lel].tagName === rnode.tagName) {
					mapAttributes(live[lel], rnode);
					mapTree(live[lel], rnode);
					li = lel + 1;
				}
				ri++;
				continue;
			}

			// ----- Texto com variável: quebra em literal + caixa(s).
			if (rnode.nodeType === 3 && hasMarker(rnode.nodeValue)) {
				var lt = -1;
				for (var t = li; t < live.length; t++) {
					if (live[t].nodeType === 3 && String(live[t].nodeValue).trim() !== '') { lt = t; break; }
					if (live[t].nodeType === 1) { break; }
				}
				if (lt >= 0) { annotateTextVars(liveParent, live[lt], String(rnode.nodeValue)); }
				ri++;
				continue;
			}

			ri++;
		}
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

				// HTML ORIGINAL (cru) correspondente ao que está VIVO no root:
				//  - editando o layout (#c2f-layout-root) → body-inner cru (com o conteúdo cru embutido);
				//  - editando só o conteúdo (#c2f-page-content) → paginas.html cru.
				var editingLayout = (root.id === LAYOUT_ROOT_ID);
				var rawHtml = editingLayout ? (json.data.layout_raw || '') : (json.data.content_raw || '');

				// Guarda o original num container oculto e ANOTA o DOM vivo (sem substituí-lo).
				if (rawHtml) {
					var backup = document.getElementById(BACKUP_ID);
					if (!backup) {
						backup = document.createElement('div');
						backup.id = BACKUP_ID;
						backup.style.display = 'none';
						document.body.appendChild(backup);
					}
					backup.innerHTML = rawHtml;

					varMap = {}; varSeq = 0; mapRoot = root;
					try {
						mapTree(root, backup);
					} catch (e) {
						window.console && console.error('Mapeamento in-place:', e);
					}
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

	// Remove todos os atributos/estilos de anotação do live editor de um elemento (usado no
	// fallback em que o widget marcado não pôde ser reconstruído por falta de marcador).
	function stripWidgetAnnotations(el) {
		if (!el || !el.removeAttribute) { return; }
		['data-c2f-widget-id', 'data-c2f-widget-root', 'data-c2f-widget-parent', 'data-c2f-marker',
			'data-widget-type', 'data-widget-slug', 'contenteditable'].forEach(function (a) { el.removeAttribute(a); });
		if (el.style && el.style.position === 'relative') { el.style.position = ''; }
		if (el.getAttribute && el.getAttribute('style') === '') { el.removeAttribute('style'); }
	}

	// Reconstrói o HTML original a partir do DOM editado:
	//   1) atributos marcados (`data-c2f-variable`) voltam à notação `@[[...]]@` do banco
	//      — só quando o valor não foi alterado pelo usuário (senão preserva a edição);
	//   2) widgets marcados SEM wrapper (`[data-c2f-widget-id]`): o 1º elemento do grupo volta
	//      ao marcador `<!-- widgets#... -->` (do `data-c2f-marker`) e os demais irmãos do grupo
	//      são removidos (o marcador cobre toda a expansão);
	//   3) caixas de destaque (`.c2f-dyn-box`) voltam ao marcador original (`@[[var]]@` etc.).
	// O restante é o HTML editado pelo editor visual.
	function reconstructOriginal(container) {
		var clone = container.cloneNode(true);
		var map = {};
		var i = 0;

		// 1) Atributos com variável.
		var marked = clone.querySelectorAll('[data-c2f-variable]');
		Array.prototype.forEach.call(marked, function (el) {
			var ids = (el.getAttribute('data-c2f-variable') || '').split(/\s+/);
			ids.forEach(function (id) {
				var info = varMap[id];
				if (!info) { return; }
				var cur = el.getAttribute(info.param);
				if (cur === info.valor) { el.setAttribute(info.param, info.variable); }
			});
			el.removeAttribute('data-c2f-variable');
		});

		// 2) Widgets marcados sem wrapper — agrupa por data-c2f-widget-id.
		var widgetGroups = {};
		Array.prototype.forEach.call(clone.querySelectorAll('[data-c2f-widget-id]'), function (el) {
			var gid = el.getAttribute('data-c2f-widget-id');
			(widgetGroups[gid] = widgetGroups[gid] || []).push(el);
		});
		Object.keys(widgetGroups).forEach(function (gid) {
			var els = widgetGroups[gid];
			var rootEl = null;
			for (var k = 0; k < els.length; k++) { if (els[k].getAttribute('data-c2f-marker')) { rootEl = els[k]; break; } }
			if (!rootEl) { rootEl = els[0]; }
			var marker = decodeMarker(rootEl.getAttribute('data-c2f-marker') || '');
			var isParent = rootEl.getAttribute('data-c2f-widget-parent') === '1';

			// Item 1: o marcador vive no elemento PAI → substitui o innerHTML dele pelo marcador cru
			// (preserva a tag externa <nav>/<ul> + seus atributos), em vez de trocar o próprio nó.
			if (isParent) {
				if (marker) {
					var ptoken = 'C2FBOX' + (i++) + 'X';
					map[ptoken] = marker;
					rootEl.textContent = ptoken;
				} else {
					while (rootEl.firstChild) { rootEl.removeChild(rootEl.firstChild); }
				}
				stripWidgetAnnotations(rootEl);
				return;
			}

			// Remove os irmãos secundários do grupo (o marcador do root recompõe a expansão inteira).
			els.forEach(function (el) { if (el !== rootEl && el.parentNode) { el.parentNode.removeChild(el); } });
			if (marker) {
				var token = 'C2FBOX' + (i++) + 'X';
				map[token] = marker;
				if (rootEl.parentNode) { rootEl.parentNode.replaceChild(document.createTextNode(token), rootEl); }
			} else {
				stripWidgetAnnotations(rootEl); // best-effort: sem marcador, mantém o conteúdo limpo.
			}
		});

		// 3) Caixas de destaque (variáveis de texto) — via token para não escapar `<`/`>` do marcador.
		Array.prototype.forEach.call(clone.querySelectorAll('.c2f-dyn-box'), function (box) {
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

	// ===== Loader bloqueante de salvamento (overlay que cobre a tela toda).

	function showSaveLoader() {
		if (!document.getElementById('c2f-save-loader-style')) {
			var st = document.createElement('style');
			st.id = 'c2f-save-loader-style';
			st.textContent = '@keyframes c2f-save-spin{to{transform:rotate(360deg)}}';
			document.head.appendChild(st);
		}
		var ov = document.getElementById('c2f-save-loader');
		if (!ov) {
			ov = document.createElement('div');
			ov.id = 'c2f-save-loader';
			// z-index máximo → cobre inclusive o iframe da toolbar; bloqueia todo clique/interação.
			ov.style.cssText = 'position:fixed;inset:0;z-index:2147483647;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;cursor:progress;';
			ov.innerHTML =
				'<div style="display:flex;flex-direction:column;align-items:center;gap:16px;background:#fff;padding:28px 44px;border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,.35);font:600 15px system-ui,sans-serif;color:#0f172a;">' +
					'<div style="width:44px;height:44px;border:4px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:c2f-save-spin .8s linear infinite;"></div>' +
					'<span>Salvando página…</span>' +
				'</div>';
			// Captura e impede qualquer interação por baixo enquanto salva.
			['click', 'mousedown', 'mouseup', 'keydown', 'wheel', 'touchstart'].forEach(function (ev) {
				ov.addEventListener(ev, function (e) { e.stopPropagation(); e.preventDefault(); }, true);
			});
			document.body.appendChild(ov);
		}
		ov.style.display = 'flex';
	}

	function hideSaveLoader() {
		var ov = document.getElementById('c2f-save-loader');
		if (ov) { ov.style.display = 'none'; }
	}

	function saveEdit(pageId) {
		var root = document.getElementById(LAYOUT_ROOT_ID) || document.getElementById(CONTENT_ID);
		if (!root || !pageId) { return; }

		// Guarda de prontidão: "Editar Página" inicia um fluxo ASSÍNCRONO (fetch do render +
		// mapeamento + carga do html-editor.js). Se o usuário clica "Salvar" antes de o editor
		// instanciar, salvaríamos o DOM cru sem anotações (perde variáveis/widgets). Aborta com aviso.
		if (!c2fEditor || typeof c2fEditor.getCleanHtml !== 'function') {
			window.alert('O editor ainda está carregando. Aguarde um instante e tente salvar novamente.');
			return;
		}

		// 1) Deseleção determinística + desabilita o editor: some a caixa azul tracejada de seleção,
		//    o hover que segue o mouse e demais overlays (não interferem no mapeamento/reconstrução).
		if (typeof c2fEditor.deselectAll === 'function') { c2fEditor.deselectAll(); }
		if (typeof c2fEditor.disable === 'function') { c2fEditor.disable(); }

		// 2) Loader bloqueante cobrindo a tela toda.
		showSaveLoader();

		// 3) Deixa o DOM assentar (deseleção aplicada + loader pintado) ANTES de serializar/enviar —
		//    sem esse respiro a extração podia pegar um estado transitório e o salvamento falhava.
		setTimeout(function () { performSave(pageId, root); }, 500);
	}

	function performSave(pageId, root) {
		var contentHtml, layoutHtml = '';

		try {
			// HTML limpo do editor visual (remove a UI do editor; widgets-wrapper→comentário).
			var cleanHtml = c2fEditor.getCleanHtml();
			var parsed = new DOMParser().parseFromString(cleanHtml, 'text/html');
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
		} catch (e) {
			hideSaveLoader();
			if (c2fEditor && typeof c2fEditor.enable === 'function') { c2fEditor.enable(); }
			window.alert('Erro ao preparar o salvamento da página.');
			return;
		}

		// Roteamento (ajax/ajaxOpcao/ids) na QUERY STRING e conteúdo grande (html/layout_html) no
		// corpo. NOTA: o bug do "302 → home" NÃO era roteamento (o request_order do servidor usa o
		// fallback variables_order=EGPCS, que inclui POST em $_REQUEST) — era o redirect do histórico
		// no backend (ver id_numerico_manual em dashboard_ajax_site_toolbar_save). Mantido como
		// convenção defensiva (robusto mesmo se algum ambiente excluir POST de $_REQUEST).
		var url = dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-save' +
			'&page_id=' + encodeURIComponent(pageId);
		var body = 'html=' + encodeURIComponent(contentHtml);
		if (layoutHtml && editLayoutId) {
			url += '&layout_id=' + encodeURIComponent(editLayoutId);
			body += '&layout_html=' + encodeURIComponent(layoutHtml);
		}

		function saveFalhou(msg) {
			hideSaveLoader();
			if (c2fEditor && typeof c2fEditor.enable === 'function') { c2fEditor.enable(); }
			window.alert(msg);
		}

		fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body
		})
			// Lê como TEXTO e faz parse tolerante; em falha real mostra um trecho do corpo p/ diagnóstico.
			.then(function (r) { return r.text(); })
			.then(function (text) {
				var json = parseJsonLoose(text);
				// Sucesso → reload (o loader some sozinho com a nova página).
				if (json && json.status === 'Ok') { window.location.reload(); return; }
				if (json && json.message) { saveFalhou(json.message); return; }
				var trecho = (text || '').replace(/\s+/g, ' ').trim().slice(0, 300);
				saveFalhou('Falha ao salvar a página.' + (trecho ? '\n\nResposta do servidor:\n' + trecho : ''));
			})
			.catch(function () { saveFalhou('Erro de rede ao salvar a página.'); });
	}

	// Parse tolerante: tenta JSON.parse direto; se falhar (corpo com prefixo espúrio), tenta
	// recortar do primeiro "{" ao último "}". Retorna null se realmente não houver JSON.
	function parseJsonLoose(text) {
		if (typeof text !== 'string' || !text) { return null; }
		try { return JSON.parse(text); } catch (e) { /* tenta recortar */ }
		var i = text.indexOf('{'), j = text.lastIndexOf('}');
		if (i !== -1 && j !== -1 && j > i) {
			try { return JSON.parse(text.slice(i, j + 1)); } catch (e2) { /* sem JSON */ }
		}
		return null;
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

	// ===== Painel "+" (adicionar elemento ou widget) — BATCH-081 §5/§6: duas colunas
	//       (Elementos | Widgets), com Widgets em subcolunas (Grupos | Itens), busca autocomplete
	//       e paginação "Carregar mais". Reusa os endpoints da lib html-editor via dashboard.

	var addPanel = null;
	var addActiveGroup = '';   // grupo/categoria selecionado (vazio = busca cross-grupo).
	var addWidgetPage = 1;     // página corrente da lista de itens (paginação).
	var addSearchTimer = null; // debounce da busca.
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

	// Carrega os GRUPOS (categorias) de widget na subcoluna esquerda. Seleciona o 1º por padrão.
	function loadWidgetCategories() {
		var groups = addPanel.querySelector('.c2f-add-widget-groups');
		groups.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Carregando…</div>';
		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-widget-types', function (json) {
			var cats = (json && json.data) ? json.data : [];
			if (!cats.length) { groups.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Nenhum widget</div>'; return; }
			var h = '';
			cats.forEach(function (cat, idx) {
				h += '<div class="c2f-add-widget-group" data-module="' + esc(cat.id) + '" ' +
					'style="padding:6px 8px;border-radius:6px;cursor:pointer;font-weight:600;' + (idx === 0 ? 'background:#e0e7ff;' : '') + '">' + esc(cat.name) + '</div>';
			});
			groups.innerHTML = h;
			// Seleciona o primeiro grupo e carrega seus itens.
			addActiveGroup = cats[0].id;
			loadWidgetItems(true);
		});
	}

	// Marca visualmente o grupo ativo (ou nenhum, durante uma busca cross-grupo).
	function highlightActiveGroup() {
		if (!addPanel) { return; }
		var groups = addPanel.querySelectorAll('.c2f-add-widget-group');
		Array.prototype.forEach.call(groups, function (g) {
			g.style.background = (g.getAttribute('data-module') === addActiveGroup && addActiveGroup) ? '#e0e7ff' : '';
		});
	}

	// Carrega/pagina os ITENS na subcoluna direita. Com busca preenchida, pesquisa cross-grupo
	// (module vazio); senão lista o grupo ativo. `reset=true` recomeça na página 1.
	function loadWidgetItems(reset) {
		if (!addPanel) { return; }
		var itemsBox = addPanel.querySelector('.c2f-add-widget-items');
		var moreBtn = addPanel.querySelector('.c2f-add-widget-more');
		var busca = (addPanel.querySelector('.c2f-add-widget-search').value || '').trim();
		var module = busca ? '' : (addActiveGroup || '');

		if (busca) { addActiveGroup = ''; }
		highlightActiveGroup();

		if (!busca && !module) {
			itemsBox.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Selecione um grupo ou busque um widget.</div>';
			moreBtn.style.display = 'none';
			return;
		}

		if (reset) { addWidgetPage = 1; itemsBox.innerHTML = '<div class="c2f-add-loading" style="color:#94a3b8;font-size:12px;padding:4px 8px;">Carregando…</div>'; }
		else { addWidgetPage++; }

		var url = dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-widgets-list' +
			'&params[module]=' + encodeURIComponent(module) +
			'&params[busca]=' + encodeURIComponent(busca) +
			'&params[pagina]=' + addWidgetPage;

		ajaxJson(url, function (json) {
			var data = (json && json.data) ? json.data : {};
			var items = data.items || [];
			var loading = itemsBox.querySelector('.c2f-add-loading');
			if (loading) { loading.remove(); }
			if (reset) { itemsBox.innerHTML = ''; }
			if (reset && !items.length) {
				itemsBox.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Nenhum widget encontrado.</div>';
				moreBtn.style.display = 'none';
				return;
			}
			var h = '';
			items.forEach(function (it) {
				var nome = it.nome || it.id;
				h += '<div class="c2f-add-widget-item" data-module="' + esc(it.module) + '" data-slug="' + esc(it.id) +
					'" data-name="' + esc(nome) + '" style="padding:5px 8px;border-radius:6px;cursor:pointer;">' + esc(nome) + '</div>';
			});
			itemsBox.insertAdjacentHTML('beforeend', h);
			moreBtn.style.display = data.tem_mais ? 'block' : 'none';
		});
	}

	function buildAddPanel() {
		if (addPanel) { return addPanel; }
		addPanel = document.createElement('div');
		addPanel.id = 'c2f-add-panel';
		addPanel.style.cssText = 'position:fixed;z-index:2147483645;width:560px;max-width:94vw;max-height:72vh;overflow:auto;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 28px rgba(0,0,0,.22);padding:10px;display:none;font:14px system-ui,sans-serif;color:#0f172a;';
		var titulo = function (t) { return '<div style="font:600 11px sans-serif;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:2px 0 6px;">' + t + '</div>'; };

		var elementosHtml = titulo('Elementos HTML');
		ELEMENTOS.forEach(function (e) {
			elementosHtml += '<div class="c2f-add-el" data-el="' + e.type + '" style="padding:6px 8px;border-radius:6px;cursor:pointer;">' + e.label + '</div>';
		});
		// BATCH-081 §5: opção de código customizado (CodeMirror livre → insertCustomHtml).
		elementosHtml += '<div style="border-top:1px solid #e2e8f0;margin:6px 0;"></div>';
		elementosHtml += '<div class="c2f-add-el" data-el="__custom__" style="padding:6px 8px;border-radius:6px;cursor:pointer;font-weight:600;color:#4338ca;">Código Customizado</div>';

		var h =
			'<div class="c2f-add-cols" style="display:flex;gap:12px;align-items:flex-start;">' +
			'<div class="c2f-add-col-el" style="flex:0 0 160px;min-width:150px;">' + elementosHtml + '</div>' +
			'<div class="c2f-add-col-widgets" style="flex:1 1 auto;min-width:0;border-left:1px solid #e2e8f0;padding-left:12px;">' +
			titulo('Widgets') +
			'<input type="text" class="c2f-add-widget-search" placeholder="Buscar widgets..." autocomplete="off" ' +
			'style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:6px;padding:6px 8px;font:13px sans-serif;color:#0f172a;margin-bottom:8px;">' +
			'<div style="display:flex;gap:10px;align-items:flex-start;">' +
			'<div class="c2f-add-widget-groups" style="flex:0 0 42%;max-height:44vh;overflow:auto;"></div>' +
			'<div style="flex:1 1 auto;min-width:0;border-left:1px solid #eef2f7;padding-left:10px;">' +
			'<div class="c2f-add-widget-items" style="max-height:40vh;overflow:auto;"></div>' +
			'<button type="button" class="c2f-add-widget-more" style="display:none;width:100%;margin-top:8px;padding:6px;border:1px solid #cbd5e1;border-radius:6px;background:#f8fafc;color:#334155;cursor:pointer;font:12px sans-serif;">Carregar mais</button>' +
			'</div>' +
			'</div>' +
			'</div>' +
			'</div>';
		addPanel.innerHTML = h;
		document.body.appendChild(addPanel);

		addPanel.addEventListener('mouseover', function (e) { var it = e.target.closest && e.target.closest('.c2f-add-el,.c2f-add-widget-group,.c2f-add-widget-item'); if (it && !(it.classList.contains('c2f-add-widget-group') && it.getAttribute('data-module') === addActiveGroup)) { it.style.background = '#f1f5f9'; } });
		addPanel.addEventListener('mouseout', function (e) { var it = e.target.closest && e.target.closest('.c2f-add-el,.c2f-add-widget-group,.c2f-add-widget-item'); if (it && !(it.classList.contains('c2f-add-widget-group') && it.getAttribute('data-module') === addActiveGroup)) { it.style.background = ''; } });

		addPanel.addEventListener('click', function (e) {
			var el = e.target.closest('.c2f-add-el');
			if (el) {
				var tipo = el.getAttribute('data-el');
				if (tipo === '__custom__') {
					if (c2fEditor && typeof c2fEditor.openCustomCodePanel === 'function') { c2fEditor.openCustomCodePanel(); }
					closeAddPanel();
					return;
				}
				insertElement(tipo); closeAddPanel(); return;
			}
			var group = e.target.closest('.c2f-add-widget-group');
			if (group) {
				addActiveGroup = group.getAttribute('data-module');
				var search = addPanel.querySelector('.c2f-add-widget-search');
				if (search) { search.value = ''; } // clicar num grupo limpa a busca (mostra o grupo inteiro).
				loadWidgetItems(true);
				return;
			}
			var item = e.target.closest('.c2f-add-widget-item');
			if (item) { insertWidget(item.getAttribute('data-module'), item.getAttribute('data-slug'), item.getAttribute('data-name')); closeAddPanel(); return; }
			var more = e.target.closest('.c2f-add-widget-more');
			if (more) { loadWidgetItems(false); return; }
		});

		// Busca autocomplete (debounce 300ms → AJAX cross-grupo).
		var search = addPanel.querySelector('.c2f-add-widget-search');
		search.addEventListener('input', function () {
			clearTimeout(addSearchTimer);
			addSearchTimer = setTimeout(function () { loadWidgetItems(true); }, 300);
		});

		return addPanel;
	}

	function openAddPanel(x, y) {
		buildAddPanel();
		var px = Math.max(8, Math.min(parseInt(x, 10) || 8, window.innerWidth - 570));
		addPanel.style.left = px + 'px';
		addPanel.style.top = ((parseInt(y, 10) || 40) + 4) + 'px';
		addPanel.style.display = 'block';
		var search = addPanel.querySelector('.c2f-add-widget-search');
		if (search) { search.value = ''; }
		addActiveGroup = '';
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
	var backupPageId = ''; // página do contexto (valida a propriedade no backup-get — req-082 §4).

	function closeBackupPanel() { if (backupPanel) { backupPanel.style.display = 'none'; } }

	// Re-anota o DOM restaurado a partir do HTML CRU (`raw`): injeta o cru no container oculto de
	// mapeamento e re-roda o `mapTree` (req-082 §3). O `raw` do CONTEÚDO vai para #c2f-raw-content
	// (quando editando layout) ou para o próprio backup oculto (quando editando só o conteúdo); o do
	// LAYOUT substitui o backup oculto inteiro (o slot de conteúdo fica vazio, então o conteúdo vivo
	// já anotado NÃO é reprocessado). `varMap` NÃO é zerado de propósito: preserva o mapeamento
	// cruzado layout×conteúdo; só a subárvore restaurada é re-anotada (entradas antigas viram lixo
	// inofensivo). Ao final, reativa o chrome do editor.
	function remapAfterRestore(liveRoot, raw, isLayout) {
		var backup = document.getElementById(BACKUP_ID);
		if (!backup) {
			backup = document.createElement('div');
			backup.id = BACKUP_ID;
			backup.style.display = 'none';
			document.body.appendChild(backup);
		}
		var rawTarget;
		if (isLayout) {
			backup.innerHTML = raw || '';
			rawTarget = backup;
		} else {
			rawTarget = backup.querySelector('#c2f-raw-content') || backup;
			rawTarget.innerHTML = raw || '';
		}
		mapRoot = document.getElementById(LAYOUT_ROOT_ID) || document.getElementById(CONTENT_ID);
		try { mapTree(liveRoot, rawTarget); }
		catch (e) { window.console && console.error('Re-mapeamento pós-restauração:', e); }
		if (typeof c2fEditor !== 'undefined' && c2fEditor && typeof c2fEditor.restoreChrome === 'function') {
			c2fEditor.restoreChrome();
		}
	}

	// Restaura só o conteúdo da página (#c2f-page-content) a partir de um backup de PÁGINA e re-anota
	// os widgets/variáveis do conteúdo restaurado (req-082 §3).
	function restorePageBackup(html, raw) {
		var content = document.getElementById(CONTENT_ID);
		if (!content) { return; }
		if (typeof c2fEditor !== 'undefined' && c2fEditor && typeof c2fEditor.deselectAll === 'function') {
			c2fEditor.deselectAll();
		}
		content.innerHTML = html;
		remapAfterRestore(content, raw, false);
	}

	// Restaura o LAYOUT (item 8) preservando o conteúdo vivo: injeta o body-inner do backup do
	// layout no #c2f-layout-root, re-encaixa o #c2f-page-content atual no slot de conteúdo e re-anota
	// os widgets/variáveis do layout restaurado (o conteúdo vivo mantém seu mapeamento — req-082 §3).
	function restoreLayoutBackup(html, raw) {
		var layoutRoot = document.getElementById(LAYOUT_ROOT_ID);
		if (!layoutRoot) { window.alert('O layout não está em edição nesta página (edite pelo layout para restaurá-lo).'); return; }
		if (typeof c2fEditor !== 'undefined' && c2fEditor && typeof c2fEditor.deselectAll === 'function') {
			c2fEditor.deselectAll();
		}
		var liveContent = document.getElementById(CONTENT_ID);
		if (liveContent && liveContent.parentNode) { liveContent.parentNode.removeChild(liveContent); }
		layoutRoot.innerHTML = html;
		var slot = layoutRoot.querySelector('#' + CONTENT_ID);
		if (slot && liveContent) { slot.parentNode.replaceChild(liveContent, slot); }
		else if (liveContent) { layoutRoot.appendChild(liveContent); }
		remapAfterRestore(layoutRoot, raw, true);
	}

	function restoreBackup(id, type) {
		if (!id) { return; }
		var url = dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-backup-get&id=' + encodeURIComponent(id) +
			'&type=' + encodeURIComponent(type || 'page') +
			'&page_id=' + encodeURIComponent(backupPageId || '');
		ajaxJson(url, function (json) {
			if (!json || json.status !== 'Ok' || !json.data || typeof json.data.html !== 'string') {
				window.alert((json && json.message) ? json.message : 'Falha ao carregar o backup.');
				return;
			}
			var raw = (typeof json.data.raw === 'string') ? json.data.raw : '';
			if (type === 'layout') { restoreLayoutBackup(json.data.html, raw); }
			else { restorePageBackup(json.data.html, raw); }
		});
	}

	function buildBackupPanel() {
		if (backupPanel) { return backupPanel; }
		backupPanel = document.createElement('div');
		backupPanel.id = 'c2f-backup-panel';
		backupPanel.style.cssText = 'position:fixed;z-index:2147483645;min-width:440px;max-width:600px;max-height:70vh;overflow:auto;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 28px rgba(0,0,0,.22);padding:8px;display:none;font:14px system-ui,sans-serif;color:#0f172a;';
		document.body.appendChild(backupPanel);
		backupPanel.addEventListener('mouseover', function (e) { var it = e.target.closest && e.target.closest('.c2f-backup-item'); if (it) { it.style.background = '#f1f5f9'; } });
		backupPanel.addEventListener('mouseout', function (e) { var it = e.target.closest && e.target.closest('.c2f-backup-item'); if (it) { it.style.background = ''; } });
		backupPanel.addEventListener('click', function (e) {
			var it = e.target.closest('.c2f-backup-item');
			if (it) { restoreBackup(it.getAttribute('data-id'), it.getAttribute('data-type')); closeBackupPanel(); }
		});
		return backupPanel;
	}

	// Monta uma coluna do painel de backups (Página ou Layout).
	function backupColumn(titulo, backups, type) {
		var h = '<div style="flex:1 1 0;min-width:200px;">' +
			'<div style="font:600 11px sans-serif;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:4px 0;padding-bottom:4px;border-bottom:1px solid #e2e8f0;">' + esc(titulo) + '</div>';
		if (!backups || !backups.length) {
			h += '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Nenhum backup</div>';
		} else {
			backups.forEach(function (b) {
				h += '<div class="c2f-backup-item" data-id="' + esc(b.id) + '" data-type="' + esc(type) +
					'" style="padding:6px 8px;border-radius:6px;cursor:pointer;">v' + esc(b.versao) + ' — ' + esc(b.data) + '</div>';
			});
		}
		return h + '</div>';
	}

	function openBackupPanel(x, y, pageId) {
		buildBackupPanel();
		backupPageId = pageId || ''; // guarda p/ validar a propriedade da página no backup-get (§4).
		var px = Math.max(8, Math.min(parseInt(x, 10) || 8, window.innerWidth - 610));
		backupPanel.style.left = px + 'px';
		backupPanel.style.top = ((parseInt(y, 10) || 40) + 4) + 'px';
		backupPanel.style.display = 'block';
		backupPanel.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">Carregando…</div>';
		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-backups&page_id=' + encodeURIComponent(pageId || ''), function (json) {
			var data = (json && json.data) ? json.data : {};
			var pageB = data.page_backups || [];
			var layoutB = data.layout_backups || [];
			backupPanel.innerHTML =
				'<div style="display:flex;gap:12px;align-items:flex-start;">' +
				backupColumn('Backups da Página', pageB, 'page') +
				backupColumn('Backups do Layout', layoutB, 'layout') +
				'</div>';
		});
	}

	// Fecha o painel de backups ao clicar fora.
	document.addEventListener('mousedown', function (e) {
		if (backupPanel && backupPanel.style.display === 'block' && (!e.target.closest || !e.target.closest('#c2f-backup-panel'))) {
			closeBackupPanel();
		}
	});

	// ===== Ponte de renderização de widget (motor html-editor.js → backend → motor) — req-082 §1.
	//
	// Ao inserir um widget, o motor posta (string JSON) `c2f-he:widget-render` para window.parent —
	// na página hospedeira top, `window.parent === window`. Aqui interceptamos, pedimos o HTML
	// renderizado ao backend (rota `site-toolbar-widget-render` → `html_editor_ajax_widget_render`)
	// e devolvemos `c2f-he:widget-rendered` (string JSON) para a própria window → o motor chama
	// `applyWidgetRender`. Sem isso, o widget fica preso em "Carregando widget…".
	function handleEngineWidgetRender(signature, wrapperId) {
		if (!signature || !wrapperId) { return; }
		var url = dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-widget-render';
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: 'params[signature]=' + encodeURIComponent(signature)
		})
			.then(function (r) { return r.json(); })
			.then(function (json) {
				var html = (json && json.status === 'Ok' && json.data && typeof json.data.html === 'string')
					? json.data.html : '';
				try {
					window.postMessage(JSON.stringify({
						action: 'c2f-he:widget-rendered', wrapperId: wrapperId, html: html
					}), window.location.origin);
				} catch (e) { /* noop */ }
			})
			.catch(function () { /* silencioso: o placeholder permanece, sem quebrar a edição */ });
	}

	// Listener dedicado às mensagens STRING do motor (o listener principal abaixo trata os objetos
	// `c2f-toolbar:*` postados pelo iframe da toolbar).
	window.addEventListener('message', function (ev) {
		if (ev.origin !== window.location.origin) { return; }
		if (typeof ev.data !== 'string') { return; }
		var data;
		try { data = JSON.parse(ev.data); } catch (e) { return; }
		if (!data || data.action !== 'c2f-he:widget-render') { return; }
		handleEngineWidgetRender(data.signature, data.wrapperId);
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
			case 'c2f-toolbar:edit-templates':
				if (c2fEditor && typeof c2fEditor.openTemplatesPanel === 'function') { c2fEditor.openTemplatesPanel(); }
				break;
			case 'c2f-toolbar:edit-ai':
				if (c2fEditor && typeof c2fEditor.openAiPanel === 'function') { c2fEditor.openAiPanel(); }
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
