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

	// req-117: as demais camadas de código da página em edição, entregues pelo `site-toolbar-render`.
	// Alimentam o painel "Código" e o salvamento — antes daqui a Editbar só conhecia o `html`, e por
	// isso o `css_compiled` da página nunca era atualizado por uma edição ao vivo.
	var pageCode = { css: '', css_compiled: '', html_extra_head: '', framework_css: '' };
	var pageCodeDirty = {}; // campos efetivamente editados no painel (só eles vão no POST).

	function languageCode() {
		var language = (window.gestor && window.gestor.language) ? window.gestor.language : '';
		if (!language) {
			try {
				if (window.parent && window.parent !== window && window.parent.gestor) {
					language = window.parent.gestor.language || '';
				}
			} catch (e) { /* cross-origin parent: keep the local/default language */ }
		}
		return String(language || '').toLowerCase();
	}

	function t(portuguese, english) {
		return languageCode().indexOf('en') === 0 ? english : portuguese;
	}

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
				c2fEditor = new window.HtmlEditorClass({ contentRoot: content, raiz: getRaiz(), language: languageCode() });
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

	// req-112: cache-bust do motor. Era uma string FIXA (`?v=c2fNN`) que precisava ser lembrada e
	// bumpada à mão a cada alteração do `html-editor.js` — e não foi, então a correção do painel de
	// Configurações ficou uma rodada inteira sem chegar ao navegador. Agora segue a versão da
	// biblioteca `html-editor` (`gestor/bibliotecas/html-editor.php`), a MESMA que versiona este
	// arquivo e o motor no editor clássico: um número só governa os três.
	function versaoHtmlEditor() {
		if (window.gestor && window.gestor.htmlEditorVersao) { return window.gestor.htmlEditorVersao; }
		// Sem a variável (contexto antigo), a versão do sistema ainda é melhor que nenhuma.
		if (window.gestor && window.gestor.versao) { return window.gestor.versao; }
		return '';
	}

	// req-117: runtime do Tailwind na PÁGINA PÚBLICA durante a edição.
	//
	// A página pública nasce só com o CSS estático pré-compilado. Sem o runtime, uma classe nova
	// digitada no Styler ou no CodeMirror não existe em folha nenhuma: o elemento não muda na tela e
	// o usuário conclui que o editor está quebrado. Com o runtime, a classe é compilada na hora — e
	// é a MESMA folha que o `performSave` extrai para gravar em `paginas.css_compiled`.
	//
	// O contrato (`@theme static`) e a versão vêm do `site-toolbar-render`, não da página pública:
	// só quem entra em edição precisa deles. Sem `@import "tailwindcss"` no contrato o runtime
	// prefixa o import sozinho — é o comportamento documentado do @tailwindcss/browser v4.
	//
	// Efeito colateral conhecido: o runtime emite o Preflight. Em página cujo layout já traz o
	// pré-compilado (o caso normal) as regras são idênticas e nada muda visualmente; em página sem
	// nenhuma cascata Tailwind offline, entrar em edição pode alterar a aparência — que é
	// exatamente a aparência que ela terá depois de salva.
	function ensureTailwindRuntime(dados) {
		if (!dados || dados.framework_css !== 'tailwindcss') { return; }
		if (document.getElementById('c2f-tw-browser')) { return; }

		var contrato = dados.tailwind_browser_contract || '';
		if (contrato) {
			var estilo = document.createElement('style');
			estilo.id = 'c2f-tw-contract';
			estilo.type = 'text/tailwindcss';
			estilo.setAttribute('data-c2f-tailwind-role', 'browser-contract');
			estilo.textContent = contrato;
			document.head.appendChild(estilo);
		}

		var versao = dados.tailwind_browser_version || '4.3.0';
		loadScriptOnce('https://unpkg.com/@tailwindcss/browser@' + encodeURIComponent(versao), 'c2f-tw-browser', function () { });
	}

	function activateEditor(content) {
		ensureJQuery(function () {
			// Impede o auto-init sobre document.body; instanciamos escopado ao conteúdo.
			window.__c2fHtmlEditorNoAutoInit = true;
			var motor = getRaiz() + 'interface/html-editor.js?v=' + encodeURIComponent(versaoHtmlEditor());
			loadScriptOnce(motor, 'c2f-he-script', function () {
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
					window.alert((json && json.message) ? json.message : t('Falha ao carregar o editor da página.', 'Failed to load the page editor.'));
					return;
				}
				editLayoutId = json.data.layout_id || '';

				// req-117: guarda as demais camadas de código e liga o runtime do Tailwind ANTES de
				// instanciar o editor — a compilação é assíncrona e a primeira classe digitada já
				// encontra o compilador de pé.
				pageCode = {
					css: json.data.css || '',
					css_compiled: json.data.css_compiled || '',
					html_extra_head: json.data.html_extra_head || '',
					framework_css: json.data.framework_css || ''
				};
				pageCodeDirty = {};
				ensureTailwindRuntime(json.data);

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
			.catch(function () { window.alert(t('Erro ao carregar o editor da página.', 'Error loading the page editor.')); });
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

		// Normaliza variáveis/widgets DIGITADOS pelo usuário no editor. No template a notação é
		// `[[var]]` (openText/closeText); no banco é `@[[var]]@` (open/close, com cerco `@`). As
		// variáveis que vieram das caixas já são reconstruídas com o cerco (via data-c2f-marker); as
		// que o usuário digitou à mão (ex.: `[[pagina#url-raiz]]`, `[[widgets#slug]]`) ficam sem o
		// cerco. Este passe final garante `@[[…]]@` para TODAS (idempotente: `@[[x]]@`→`@[[x]]@`).
		html = html.replace(/@?\[\[([\s\S]+?)\]\]@?/g, '@[[$1]]@');
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
				'<span>' + t('Salvando página…', 'Saving page...') + '</span>' +
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
			window.alert(t('O editor ainda está carregando. Aguarde um instante e tente salvar novamente.', 'The editor is still loading. Wait a moment and try saving again.'));
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

	// req-117: extrai a folha que o Tailwind Browser gerou nesta página, usando a MESMA lógica do
	// editor clássico (`window.HtmlEditorCssCapture`, definida no motor `html-editor.js`).
	//
	// O baseline sai do próprio DOM: os `<style data-tailwind-role="...">` que o PHP emitiu são
	// exatamente a cascata pré-compilada que a página recebe, então o que sobra é o delta.
	//
	// Janela de 2 s (contra os 4 s do editor clássico) porque aqui o usuário está esperando o
	// salvamento. Esgotada sem resultado, o callback recebe `null` e o campo NÃO vai no POST — o
	// backend só grava o que chega, então o valor do banco fica preservado.
	function capturarCssCompiled(callback, tentativa) {
		tentativa = tentativa || 0;

		if (pageCode.framework_css !== 'tailwindcss' || !window.HtmlEditorCssCapture) { callback(null); return; }

		var resultado = window.HtmlEditorCssCapture.extract(document);
		if (resultado.ready) { callback(resultado.css); return; }

		if (tentativa >= 20) {
			window.console && console.warn('CSS compilado nao ficou pronto a tempo (' + resultado.motivo + '); o valor gravado foi preservado.');
			callback(null);
			return;
		}

		setTimeout(function () { capturarCssCompiled(callback, tentativa + 1); }, 100);
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
			window.alert(t('Erro ao preparar o salvamento da página.', 'Error preparing the page save.'));
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

		// req-117: campos do painel "Código". Só entram no POST quando foram efetivamente editados —
		// `dashboard_ajax_site_toolbar_save` grava tudo o que chega, então mandar um campo intocado
		// geraria versão e backup à toa a cada salvamento.
		['css', 'html_extra_head'].forEach(function (campo) {
			if (pageCodeDirty[campo]) { body += '&' + campo + '=' + encodeURIComponent(pageCode[campo] || ''); }
		});

		function saveFalhou(msg) {
			hideSaveLoader();
			if (c2fEditor && typeof c2fEditor.enable === 'function') { c2fEditor.enable(); }
			window.alert(msg);
		}

		function enviar(cssCompiled) {
			if (cssCompiled !== null && cssCompiled !== undefined) {
				body += '&css_compiled=' + encodeURIComponent(cssCompiled);
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
					saveFalhou(t('Falha ao salvar a página.', 'Failed to save the page.') + (trecho ? '\n\n' + t('Resposta do servidor:', 'Server response:') + '\n' + trecho : ''));
				})
				.catch(function () { saveFalhou(t('Erro de rede ao salvar a página.', 'Network error while saving the page.')); });
		}

		capturarCssCompiled(enviar);
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

	// Preview responsivo (screenPagina): SIMULA a viewport do dispositivo renderizando o conteúdo
	// dentro de um IFRAME com a largura do device. Só assim as media queries (@media), unidades de
	// viewport (vw/vh) e o evento window.resize respondem como num dispositivo real — mudar apenas
	// a largura de um elemento NÃO dispara media queries (elas usam a viewport, não o elemento).
	// Desktop (100%) volta à edição in-place; Tablet/Mobile é preview fiel (o editor fica desabilitado).
	var deviceWrap = null;   // contêiner centralizado do preview.
	var deviceIframe = null; // iframe que renderiza a página real na largura do device.

	// URL da PRÓPRIA página com o parâmetro que desativa a toolbar (evita recursão do iframe da barra)
	// mas mantém o layout + CSS + JS reais do site — assim media queries, vw/vh E as interações JS
	// (menu hambúrguer, carrosséis, etc.) funcionam de verdade no preview. Reflete a versão SALVA
	// (recarrega do servidor); as edições não salvas ficam preservadas no DOM real ao voltar ao Desktop.
	function devicePreviewUrl() {
		var loc = window.location;
		var qs = loc.search ? (loc.search + '&') : '?';
		return loc.origin + loc.pathname + qs + 'c2f-device-preview=1';
	}

	function enterDevicePreview(width) {
		var root = document.getElementById(LAYOUT_ROOT_ID) || document.getElementById(CONTENT_ID);
		if (!root) { return; }

		// Preview já visível → só troca a largura do iframe (media queries re-avaliam sem recarregar).
		if (deviceIframe && deviceWrap && deviceWrap.style.display === 'flex') {
			deviceIframe.style.width = width;
			return;
		}

		// Editor visual desabilitado durante o preview (fiel, não editável). req-106 rodada 3: os
		// painéis fixos (Sidebar de CSS / Barra de Navegação) PERMANECEM — trocar a largura de
		// visualização não é sair do modo de edição.
		if (typeof c2fEditor !== 'undefined' && c2fEditor) {
			if (typeof c2fEditor.deselectAll === 'function') { c2fEditor.deselectAll(); }
			if (typeof c2fEditor.disable === 'function') { c2fEditor.disable({ manterPaineis: true }); }
		}

		if (!deviceWrap) {
			deviceWrap = document.createElement('div');
			deviceWrap.id = 'c2f-device-preview';
			deviceWrap.style.cssText = 'width:100%;display:flex;justify-content:center;background:#334155;padding:16px 0;box-sizing:border-box;';
			deviceIframe = document.createElement('iframe');
			deviceIframe.setAttribute('title', 'Preview do dispositivo');
			deviceIframe.style.cssText = 'border:0;background:#fff;box-shadow:0 6px 24px rgba(0,0,0,.35);height:82vh;max-width:100%;transition:width .2s;';
			deviceIframe.src = devicePreviewUrl(); // carrega a página real 1x; depois só a largura muda.
			deviceWrap.appendChild(deviceIframe);
		}
		if (!deviceWrap.parentNode && root.parentNode) { root.parentNode.insertBefore(deviceWrap, root.nextSibling); }
		deviceIframe.style.width = width;
		root.style.display = 'none';
		deviceWrap.style.display = 'flex';
	}

	function exitDevicePreview() {
		var root = document.getElementById(LAYOUT_ROOT_ID) || document.getElementById(CONTENT_ID);
		if (deviceWrap) { deviceWrap.style.display = 'none'; }
		if (root) { root.style.display = ''; }
		if (typeof c2fEditor !== 'undefined' && c2fEditor && typeof c2fEditor.enable === 'function') { c2fEditor.enable(); }
	}

	function setEditScreen(width) {
		var w = String(width || '100%');
		if (w === '100%' || w === '100') { exitDevicePreview(); }
		else { enterDevicePreview(w); }
	}

	// ===== Painel "+" (adicionar elemento ou widget) — BATCH-081 §5/§6: duas colunas
	//       (Elementos | Widgets), com Widgets em subcolunas (Grupos | Itens), busca autocomplete
	//       e paginação "Carregar mais". Reusa os endpoints da lib html-editor via dashboard.

	var addPanel = null;
	var addActiveGroup = '';   // grupo/categoria selecionado (vazio = busca cross-grupo).
	var addWidgetPage = 1;     // página corrente da lista de itens (paginação).
	var addSearchTimer = null; // debounce da busca.
	var ELEMENTOS = [
		{ type: 'p', label: 'Parágrafo', labelEn: 'Paragraph' }, { type: 'h1', label: 'Título H1', labelEn: 'Heading H1' }, { type: 'h2', label: 'Título H2', labelEn: 'Heading H2' },
		{ type: 'h3', label: 'Título H3', labelEn: 'Heading H3' }, { type: 'img', label: 'Imagem', labelEn: 'Image' }, { type: 'a', label: 'Link', labelEn: 'Link' },
		{ type: 'button', label: 'Botão', labelEn: 'Button' }, { type: 'div', label: 'Bloco', labelEn: 'Block' }, { type: 'section', label: 'Seção', labelEn: 'Section' },
		// req-097 item 6: mídia/documento embutido — o motor envolve o elemento inserido e abre o modal
		// de embed (fonte, dimensões, motor de PDF) logo em seguida.
		{ type: 'object', label: 'Objeto / PDF', labelEn: 'Object / PDF' }, { type: 'iframe', label: 'Iframe', labelEn: 'Iframe' },
		{ type: 'embed', label: 'Embed', labelEn: 'Embed' }, { type: 'video', label: 'Vídeo', labelEn: 'Video' },
		{ type: 'audio', label: 'Áudio', labelEn: 'Audio' }
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
		groups.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Carregando…', 'Loading...') + '</div>';
		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-widget-types', function (json) {
			var cats = (json && json.data) ? json.data : [];
			if (!cats.length) { groups.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Nenhum widget', 'No widgets') + '</div>'; return; }
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
			itemsBox.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Selecione um grupo ou busque um widget.', 'Select a group or search for a widget.') + '</div>';
			moreBtn.style.display = 'none';
			return;
		}

		if (reset) { addWidgetPage = 1; itemsBox.innerHTML = '<div class="c2f-add-loading" style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Carregando…', 'Loading...') + '</div>'; }
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
				itemsBox.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Nenhum widget encontrado.', 'No widgets found.') + '</div>';
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

		var elementosHtml = titulo(t('Elementos HTML', 'HTML Elements'));
		ELEMENTOS.forEach(function (e) {
			elementosHtml += '<div class="c2f-add-el" data-el="' + e.type + '" style="padding:6px 8px;border-radius:6px;cursor:pointer;">' + t(e.label, e.labelEn) + '</div>';
		});
		// BATCH-081 §5: opção de código customizado (CodeMirror livre → insertCustomHtml).
		elementosHtml += '<div style="border-top:1px solid #e2e8f0;margin:6px 0;"></div>';
		elementosHtml += '<div class="c2f-add-el" data-el="__custom__" style="padding:6px 8px;border-radius:6px;cursor:pointer;font-weight:600;color:#4338ca;">' + t('Código Customizado', 'Custom Code') + '</div>';

		var h =
			'<div class="c2f-add-cols" style="display:flex;gap:12px;align-items:flex-start;">' +
			'<div class="c2f-add-col-el" style="flex:0 0 160px;min-width:150px;">' + elementosHtml + '</div>' +
			'<div class="c2f-add-col-widgets" style="flex:1 1 auto;min-width:0;border-left:1px solid #e2e8f0;padding-left:12px;">' +
			titulo('Widgets') +
			'<input type="text" class="c2f-add-widget-search" placeholder="' + t('Buscar widgets...', 'Search widgets...') + '" autocomplete="off" ' +
			'style="width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:6px;padding:6px 8px;font:13px sans-serif;color:#0f172a;margin-bottom:8px;">' +
			'<div style="display:flex;gap:10px;align-items:flex-start;">' +
			'<div class="c2f-add-widget-groups" style="flex:0 0 42%;max-height:44vh;overflow:auto;"></div>' +
			'<div style="flex:1 1 auto;min-width:0;border-left:1px solid #eef2f7;padding-left:10px;">' +
			'<div class="c2f-add-widget-items" style="max-height:40vh;overflow:auto;"></div>' +
			'<button type="button" class="c2f-add-widget-more" style="display:none;width:100%;margin-top:8px;padding:6px;border:1px solid #cbd5e1;border-radius:6px;background:#f8fafc;color:#334155;cursor:pointer;font:12px sans-serif;">' + t('Carregar mais', 'Load more') + '</button>' +
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
		if (viewOptionsPanel && viewOptionsPanel.style.display === 'block' &&
			(!e.target.closest || !e.target.closest('#c2f-view-options-panel'))) {
			closeViewOptionsPanel();
		}
	});

	// ===== Painel de Opções de Exibição (req-106 / BATCH-106)
	//
	// Mesmo padrão visual do painel "+": caixa flutuante ancorada ao botão da Editbar. Os toggles
	// ligam/desligam a Sidebar Lateral de CSS e a Barra de Navegação de Elementos DIRETAMENTE na
	// instância do motor (que roda nesta mesma página, no Live Editor) — sem postMessage.

	var viewOptionsPanel = null;

	var VIEW_OPTIONS = [
		{ key: 'cssSidebar', label: 'Estilização de Elementos', labelEn: 'Element Styling' },
		{ key: 'elementNavbar', label: 'Navegação de Elementos', labelEn: 'Element Navigation' }
	];

	function closeViewOptionsPanel() { if (viewOptionsPanel) { viewOptionsPanel.style.display = 'none'; } }

	function buildViewOptionsPanel() {
		if (viewOptionsPanel) { return viewOptionsPanel; }
		viewOptionsPanel = document.createElement('div');
		viewOptionsPanel.id = 'c2f-view-options-panel';
		viewOptionsPanel.style.cssText = 'position:fixed;z-index:2147483645;width:300px;max-width:94vw;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 28px rgba(0,0,0,.22);padding:10px;display:none;font:14px system-ui,sans-serif;color:#0f172a;';
		var h = '<div style="font:600 11px sans-serif;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:2px 0 6px;">' +
			t('Opções de Exibição', 'Display Options') + '</div>';
		VIEW_OPTIONS.forEach(function (o) {
			h += '<label class="c2f-view-option" style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;">' +
				'<input type="checkbox" data-view-option="' + esc(o.key) + '" style="width:16px;height:16px;cursor:pointer;">' +
				'<span>' + esc(t(o.label, o.labelEn)) + '</span></label>';
		});
		viewOptionsPanel.innerHTML = h;
		document.body.appendChild(viewOptionsPanel);

		viewOptionsPanel.addEventListener('mouseover', function (e) { var it = e.target.closest && e.target.closest('.c2f-view-option'); if (it) { it.style.background = '#f1f5f9'; } });
		viewOptionsPanel.addEventListener('mouseout', function (e) { var it = e.target.closest && e.target.closest('.c2f-view-option'); if (it) { it.style.background = ''; } });
		viewOptionsPanel.addEventListener('change', function (e) {
			var campo = e.target.closest && e.target.closest('[data-view-option]');
			if (!campo) { return; }
			if (c2fEditor && typeof c2fEditor.setViewOption === 'function') {
				c2fEditor.setViewOption(campo.getAttribute('data-view-option'), campo.checked);
			}
		});
		return viewOptionsPanel;
	}

	function syncViewOptionsPanel() {
		if (!viewOptionsPanel) { return; }
		var campos = viewOptionsPanel.querySelectorAll('[data-view-option]');
		Array.prototype.forEach.call(campos, function (campo) {
			var on = false;
			if (c2fEditor && typeof c2fEditor.getViewOption === 'function') {
				on = !!c2fEditor.getViewOption(campo.getAttribute('data-view-option'));
			}
			campo.checked = on;
		});
	}

	function openViewOptionsPanel(x, y) {
		buildViewOptionsPanel();
		var px = Math.max(8, Math.min(parseInt(x, 10) || 8, window.innerWidth - 310));
		viewOptionsPanel.style.left = px + 'px';
		viewOptionsPanel.style.top = ((parseInt(y, 10) || 40) + 4) + 'px';
		viewOptionsPanel.style.display = 'block';
		syncViewOptionsPanel(); // o estado real é o do motor (recuperado do localStorage no boot)
	}

	// ===== Painel de Configurações da Página (req-110 / BATCH-110)
	//
	// Permite editar título social, descrição social e imagem de destaque sem sair do Live Editor.
	// Vive na página hospedeira pelo mesmo motivo dos demais painéis: é aqui que o seletor de
	// arquivos (`admin-arquivos` em iframe) pode ser sobreposto ao conteúdo — dentro do iframe da
	// Editbar ele ficaria confinado à altura da barra.

	var pageConfigPanel = null;
	var pageConfigPageId = '';
	var pageConfigImagem = '';
	var pageConfigPickerOverlay = null;

	function closePageConfigPanel() { if (pageConfigPanel) { pageConfigPanel.style.display = 'none'; } }

	function buildPageConfigPanel() {
		if (pageConfigPanel) { return pageConfigPanel; }
		pageConfigPanel = document.createElement('div');
		pageConfigPanel.id = 'c2f-page-config-panel';
		// req-112: `pointer-events:auto` e `isolation:isolate` são explícitos porque o painel é
		// injetado na página EDITADA — que pode ter regras próprias de `pointer-events` herdadas, e
		// cujo motor de edição escuta o documento inteiro.
		pageConfigPanel.style.cssText = 'position:fixed;z-index:2147483646;isolation:isolate;pointer-events:auto;width:380px;max-width:94vw;max-height:80vh;overflow:auto;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 28px rgba(0,0,0,.22);padding:12px;display:none;font:14px system-ui,sans-serif;color:#0f172a;';
		document.body.appendChild(pageConfigPanel);

		// req-112: reforço contra vazamento de evento para a página editada. A correção PRINCIPAL é o
		// painel entrar em `isEditorOwned()` no motor (html-editor.js) — sem isso o hover realçava o
		// elemento ATRÁS do painel e o primeiro clique era consumido pela seleção em vez do botão.
		//
		// A propagação é barrada na fase de BOLHA, nunca na de captura: em captura o
		// `stopPropagation()` impediria o evento de chegar ao próprio botão dentro do painel. E como
		// estes listeners são registrados ANTES do handler de clique abaixo, no mesmo elemento e na
		// mesma fase, ele continua rodando (`stopPropagation` não afeta o mesmo nó).
		['mousedown', 'mouseup', 'click', 'mousemove', 'mouseover', 'mouseout', 'dblclick', 'contextmenu']
			.forEach(function (evento) {
				pageConfigPanel.addEventListener(evento, function (e) { e.stopPropagation(); });
			});

		pageConfigPanel.addEventListener('click', function (e) {
			var alvo = e.target.closest && e.target.closest('[data-page-config-action]');
			if (!alvo) { return; }
			e.preventDefault();
			var acao = alvo.getAttribute('data-page-config-action');
			if (acao === 'pick') { openPageConfigPicker(); }
			if (acao === 'clear') { pageConfigImagem = ''; renderPageConfigImagem(); }
			if (acao === 'save') { savePageConfig(); }
		});

		return pageConfigPanel;
	}

	function pageConfigCampo(id, rotulo, valor, multilinha, dica) {
		var campo = multilinha
			? '<textarea id="' + id + '" rows="3" style="width:100%;box-sizing:border-box;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;resize:vertical;">' + esc(valor) + '</textarea>'
			: '<input type="text" id="' + id + '" value="' + esc(valor) + '" style="width:100%;box-sizing:border-box;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;">';

		return '<div style="margin-bottom:10px;">' +
			'<label for="' + id + '" style="display:block;font:600 12px sans-serif;color:#475569;margin-bottom:4px;">' + esc(rotulo) + '</label>' +
			campo +
			(dica ? '<div style="font-size:11px;color:#94a3b8;margin-top:3px;">' + esc(dica) + '</div>' : '') +
			'</div>';
	}

	function renderPageConfigImagem() {
		if (!pageConfigPanel) { return; }
		var alvo = pageConfigPanel.querySelector('.c2f-page-config-image');
		if (!alvo) { return; }

		if (pageConfigImagem) {
			alvo.innerHTML = '<img src="' + esc(getRaiz() + String(pageConfigImagem).replace(/^\/+/, '')) + '" alt="" ' +
				'style="max-width:100%;max-height:120px;border-radius:6px;border:1px solid #e2e8f0;display:block;margin-bottom:6px;">' +
				'<div style="font-size:11px;color:#64748b;word-break:break-all;">' + esc(pageConfigImagem) + '</div>';
		} else {
			alvo.innerHTML = '<div style="font-size:12px;color:#94a3b8;padding:8px 0;">' +
				t('Nenhuma imagem escolhida — o compartilhamento usa o padrão do site.',
					'No image selected — sharing falls back to the site default.') + '</div>';
		}

		var limpar = pageConfigPanel.querySelector('[data-page-config-action="clear"]');
		if (limpar) { limpar.style.display = pageConfigImagem ? 'inline-block' : 'none'; }
	}

	// O gerenciador de arquivos posta a seleção como STRING JSON
	// (`{moduloId, moduloOpcao, data: "<json>"}`) — mesmo contrato do picker do editor.
	function openPageConfigPicker() {
		if (!pageConfigPickerOverlay) {
			pageConfigPickerOverlay = document.createElement('div');
			pageConfigPickerOverlay.id = 'c2f-page-config-picker';
			// req-112: acima do painel (2147483646) e com pointer-events explícito.
			pageConfigPickerOverlay.style.cssText = 'position:fixed;inset:0;z-index:2147483647;isolation:isolate;pointer-events:auto;display:none;';
			pageConfigPickerOverlay.innerHTML =
				'<div class="c2f-pcp-backdrop" style="position:absolute;inset:0;background:rgba(15,23,42,.6);"></div>' +
				'<div style="position:relative;width:920px;max-width:96vw;height:80vh;margin:7vh auto;background:#fff;border-radius:10px;box-shadow:0 20px 50px rgba(0,0,0,.4);display:flex;flex-direction:column;overflow:hidden;">' +
				'<div style="padding:10px 14px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;flex:0 0 auto;">' +
				'<span style="font-weight:600;color:#0f172a;">' + t('Selecionar imagem', 'Select image') + '</span>' +
				'<button type="button" class="c2f-pcp-close" style="border:0;background:#e2e8f0;border-radius:6px;padding:6px 12px;cursor:pointer;color:#0f172a;">' + t('Fechar', 'Close') + '</button>' +
				'</div>' +
				'<iframe class="c2f-pcp-frame" style="flex:1 1 auto;border:0;width:100%;"></iframe>' +
				'</div>';
			document.body.appendChild(pageConfigPickerOverlay);
			['mousedown', 'mouseup', 'click', 'mousemove', 'mouseover', 'mouseout', 'dblclick']
				.forEach(function (evento) {
					pageConfigPickerOverlay.addEventListener(evento, function (e) { e.stopPropagation(); });
				});
			pageConfigPickerOverlay.querySelector('.c2f-pcp-backdrop').addEventListener('click', closePageConfigPicker);
			pageConfigPickerOverlay.querySelector('.c2f-pcp-close').addEventListener('click', closePageConfigPicker);
		}

		pageConfigPickerOverlay.querySelector('.c2f-pcp-frame').src = getRaiz() + 'admin-arquivos/?paginaIframe=sim';
		pageConfigPickerOverlay.style.display = 'block';
	}

	function closePageConfigPicker() {
		if (!pageConfigPickerOverlay) { return; }
		pageConfigPickerOverlay.style.display = 'none';
		var f = pageConfigPickerOverlay.querySelector('.c2f-pcp-frame');
		if (f) { f.src = 'about:blank'; }
	}

	function aplicarSelecaoDeImagem(payload) {
		if (!pageConfigPickerOverlay || pageConfigPickerOverlay.style.display !== 'block') { return false; }
		if (!payload || !payload.caminho) { return false; }
		pageConfigImagem = String(payload.caminho);
		renderPageConfigImagem();
		closePageConfigPicker();
		return true;
	}

	function savePageConfig() {
		if (!pageConfigPanel || !pageConfigPageId) { return; }
		var titulo = pageConfigPanel.querySelector('#c2f-pc-og-titulo');
		var descricao = pageConfigPanel.querySelector('#c2f-pc-og-descricao');
		var metaDescricao = pageConfigPanel.querySelector('#c2f-pc-meta-descricao');
		var metaKeywords = pageConfigPanel.querySelector('#c2f-pc-meta-keywords');
		var status = pageConfigPanel.querySelector('.c2f-page-config-status');

		if (status) { status.textContent = t('Salvando…', 'Saving...'); status.style.color = '#64748b'; }

		var corpo = new URLSearchParams();
		corpo.set('page_id', pageConfigPageId);
		corpo.set('og_titulo', titulo ? titulo.value : '');
		corpo.set('og_descricao', descricao ? descricao.value : '');
		corpo.set('meta_descricao', metaDescricao ? metaDescricao.value : '');
		corpo.set('meta_keywords', metaKeywords ? metaKeywords.value : '');
		corpo.set('imagem_destaque', pageConfigImagem || '');

		fetch(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-page-config-save', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: corpo.toString()
		}).then(function (r) { return r.json(); }).then(function (json) {
			if (!status) { return; }
			if (json && json.status === 'Ok') {
				status.textContent = t('Salvo.', 'Saved.');
				status.style.color = '#16a34a';
			} else {
				status.textContent = (json && json.message) ? json.message : t('Falha ao salvar.', 'Failed to save.');
				status.style.color = '#dc2626';
			}
		}).catch(function () {
			if (status) { status.textContent = t('Falha ao salvar.', 'Failed to save.'); status.style.color = '#dc2626'; }
		});
	}

	function openPageConfigPanel(x, y, pageId) {
		buildPageConfigPanel();
		pageConfigPageId = pageId || '';

		var px = Math.max(8, Math.min(parseInt(x, 10) || 8, window.innerWidth - 390));
		pageConfigPanel.style.left = px + 'px';
		pageConfigPanel.style.top = ((parseInt(y, 10) || 40) + 4) + 'px';
		pageConfigPanel.style.display = 'block';
		pageConfigPanel.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Carregando…', 'Loading...') + '</div>';

		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-page-config&page_id=' + encodeURIComponent(pageConfigPageId), function (json) {
			if (!json || json.status !== 'Ok' || !json.data) {
				pageConfigPanel.innerHTML = '<div style="color:#dc2626;font-size:12px;padding:4px 8px;">' +
					esc((json && json.message) ? json.message : t('Falha ao carregar.', 'Failed to load.')) + '</div>';
				return;
			}

			var d = json.data;
			pageConfigImagem = d.imagem_destaque || '';

			pageConfigPanel.innerHTML =
				'<div style="font:600 11px sans-serif;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:2px 0 8px;">' +
				t('Configurações da Página', 'Page Settings') + '</div>' +
				'<div style="font-size:12px;color:#94a3b8;margin-bottom:10px;word-break:break-all;">' + esc(d.caminho || '') + '</div>' +
				pageConfigCampo('c2f-pc-og-titulo', t('Título social', 'Social title'), d.og_titulo || '', false,
					t('Vazio usa o nome da página: ', 'Empty uses the page name: ') + (d.nome || '')) +
				pageConfigCampo('c2f-pc-og-descricao', t('Descrição social', 'Social description'), d.og_descricao || '', true,
					t('Resumo exibido no card do link.', 'Summary shown on the link card.')) +
				// req-112: meta tags clássicas, para buscador.
				pageConfigCampo('c2f-pc-meta-descricao', t('Meta descrição (Google)', 'Meta description (Google)'), d.meta_descricao || '', true,
					t('Vazio usa a descrição social ou o padrão do site.', 'Empty falls back to the social description or the site default.')) +
				pageConfigCampo('c2f-pc-meta-keywords', t('Palavras-chave', 'Keywords'), d.meta_keywords || '', false,
					t('Separadas por vírgula.', 'Comma separated.')) +
				'<div style="margin-bottom:10px;">' +
				'<label style="display:block;font:600 12px sans-serif;color:#475569;margin-bottom:4px;">' +
				t('Imagem de destaque', 'Featured image') + '</label>' +
				'<div class="c2f-page-config-image"></div>' +
				'<div style="display:flex;gap:6px;margin-top:6px;">' +
				'<button type="button" data-page-config-action="pick" style="border:0;background:#e2e8f0;border-radius:6px;padding:6px 12px;cursor:pointer;color:#0f172a;font:inherit;">' +
				t('Escolher…', 'Choose...') + '</button>' +
				'<button type="button" data-page-config-action="clear" style="border:0;background:#fee2e2;border-radius:6px;padding:6px 12px;cursor:pointer;color:#b91c1c;font:inherit;display:none;">' +
				t('Remover', 'Remove') + '</button>' +
				'</div></div>' +
				'<div style="display:flex;align-items:center;gap:10px;margin-top:12px;">' +
				'<button type="button" data-page-config-action="save" style="border:0;background:#16a34a;color:#fff;border-radius:6px;padding:7px 16px;cursor:pointer;font:inherit;">' +
				t('Salvar', 'Save') + '</button>' +
				'<span class="c2f-page-config-status" style="font-size:12px;"></span>' +
				'</div>';

			renderPageConfigImagem();
		});
	}

	// Fecha o painel de configurações ao clicar fora (mas não quando o seletor está por cima).
	document.addEventListener('mousedown', function (e) {
		if (!pageConfigPanel || pageConfigPanel.style.display !== 'block') { return; }
		if (pageConfigPickerOverlay && pageConfigPickerOverlay.style.display === 'block') { return; }
		if (e.target.closest && e.target.closest('#c2f-page-config-panel')) { return; }
		closePageConfigPanel();
	});

	// ===== Painel de Backups (restaurar versão do conteúdo) — ponto 5.

	var backupPanel = null;
	var backupPageId = ''; // página do contexto (valida a propriedade no backup-get — req-082 §4).

	function closeBackupPanel() { if (backupPanel) { backupPanel.style.display = 'none'; } }

	// Restauração de backup SERVER-SIDE (BATCH-085): em vez de injetar o HTML no cliente (o que
	// quebrava DOM/scripts), sinalizamos o backup escolhido ao backend (grava variável de sessão) e
	// recarregamos. No reload, o roteador (`gestor_site_toolbar_backup_aplicar`) substitui a página/
	// layout pela versão do backup e a renderiza pelo pipeline normal; o editbar reentra sozinho no
	// modo de edição (flag `gestor.siteToolbarBackupRestaurado`). Ao salvar, já é o valor do backup.
	function restoreBackup(id, type) {
		if (!id) { return; }
		var url = dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-backup-restore' +
			'&id=' + encodeURIComponent(id) +
			'&type=' + encodeURIComponent(type || 'page') +
			'&page_id=' + encodeURIComponent(backupPageId || '');
		ajaxJson(url, function (json) {
			if (!json || json.status !== 'Ok') {
				window.alert((json && json.message) ? json.message : t('Falha ao restaurar o backup.', 'Failed to restore the backup.'));
				return;
			}
			window.location.reload();
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
			h += '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Nenhum backup', 'No backups') + '</div>';
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
		backupPanel.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:4px 8px;">' + t('Carregando…', 'Loading...') + '</div>';
		ajaxJson(dashboardAjaxUrl() + '?ajax=1&ajaxOpcao=site-toolbar-backups&page_id=' + encodeURIComponent(pageId || ''), function (json) {
			var data = (json && json.data) ? json.data : {};
			var pageB = data.page_backups || [];
			var layoutB = data.layout_backups || [];
			backupPanel.innerHTML =
				'<div style="display:flex;gap:12px;align-items:flex-start;">' +
				backupColumn(t('Backups da Página', 'Page Backups'), pageB, 'page') +
				backupColumn(t('Backups do Layout', 'Layout Backups'), layoutB, 'layout') +
				'</div>';
		});
	}

	// Fecha o painel de backups ao clicar fora.
	document.addEventListener('mousedown', function (e) {
		if (backupPanel && backupPanel.style.display === 'block' && (!e.target.closest || !e.target.closest('#c2f-backup-panel'))) {
			closeBackupPanel();
		}
	});

	// ===== Painel de Código da Página (req-117 / BATCH-117)
	//
	// Equivalente ao "Código" do editor clássico (`data-tab="visualizacao-codigo"`), que expõe as
	// quatro camadas de conteúdo de uma página. Vive na hospedeira porque precisa ler o DOM em
	// edição e a folha gerada pelo Tailwind Browser lá — dentro do iframe da Editbar ficaria
	// confinado aos 30px de altura da barra.

	var codePanel = null;
	var codeEditors = {};        // aba -> instância CodeMirror (ou o <textarea> em degradação)
	var codeActiveTab = 'html';
	var codeApplyTimer = null;

	var CODE_TABS = [
		{ id: 'html', rotulo: ['HTML', 'HTML'], modo: 'htmlmixed', leitura: false },
		{ id: 'html_extra_head', rotulo: ['HTML Extra Head', 'Extra Head HTML'], modo: 'htmlmixed', leitura: false },
		{ id: 'css', rotulo: ['CSS', 'CSS'], modo: 'css', leitura: false },
		{ id: 'css_compiled', rotulo: ['CSS Compilado', 'Compiled CSS'], modo: 'css', leitura: true }
	];

	function closeCodePanel() { if (codePanel) { codePanel.style.display = 'none'; } }

	// HTML que SERÁ salvo: o DOM em edição já revertido aos marcadores originais. É o mesmo caminho
	// do `performSave`, para o painel nunca mostrar algo diferente do que vai para o banco.
	function codeHtmlAtual() {
		var root = document.getElementById(LAYOUT_ROOT_ID) || document.getElementById(CONTENT_ID);
		if (!root || !c2fEditor || typeof c2fEditor.getCleanHtml !== 'function') { return ''; }
		try {
			var parsed = new DOMParser().parseFromString(c2fEditor.getCleanHtml(), 'text/html');
			var conteudo = parsed.getElementById(CONTENT_ID);
			return reconstructOriginal((root.id === LAYOUT_ROOT_ID && conteudo) ? conteudo : parsed.body);
		} catch (e) { return ''; }
	}

	// CSS Compilado é um espelho de leitura: mostra o que a captura gravaria SE o salvamento
	// acontecesse agora. Enquanto o Tailwind não terminar, informa isso em vez de mostrar vazio —
	// campo vazio seria lido como "a página não tem utilities", que é o engano do req-117.
	function codeCssCompiledAtual() {
		if (pageCode.framework_css !== 'tailwindcss') {
			return t('/* A página não usa Tailwind CSS: não há CSS compilado. */',
				'/* This page does not use Tailwind CSS: there is no compiled CSS. */');
		}
		if (!window.HtmlEditorCssCapture) {
			return t('/* O motor de edição ainda está carregando… */', '/* The editor engine is still loading... */');
		}
		var resultado = window.HtmlEditorCssCapture.extract(document);
		if (resultado.ready) { return resultado.css; }
		return t('/* Compilando (' + resultado.motivo + ')… o valor gravado é preservado até terminar. */',
			'/* Compiling (' + resultado.motivo + ')... the stored value is preserved until it finishes. */');
	}

	function codeValorDaAba(tab) {
		if (tab === 'html') { return codeHtmlAtual(); }
		if (tab === 'css_compiled') { return codeCssCompiledAtual(); }
		return pageCode[tab] || '';
	}

	// CSS autoral aplicado ao vivo: reaproveita a folha que o PHP emitiu (`data-c2f-css-role`) para o
	// resultado na tela ser o mesmo da página publicada, inclusive na ordem da cascata.
	function codeAplicarCss(valor) {
		var folha = document.querySelector('style[data-c2f-css-role="authored"]');
		if (!folha) {
			folha = document.createElement('style');
			folha.setAttribute('data-c2f-css-role', 'authored');
			document.head.appendChild(folha);
		}
		folha.textContent = valor;
	}

	// O HTML é aplicado por AÇÃO EXPLÍCITA, não a cada tecla: reescrever `#c2f-page-content` recria
	// os nós e derruba as anotações do mapeamento in-place (`data-c2f-variable`, `.c2f-dyn-box`), o
	// que a cada caractere destruiria a edição. Depois de aplicar, o HTML digitado passa a ser
	// também o original de referência, e as variáveis aparecem como marcador até o recarregamento.
	function codeAplicarHtml(valor) {
		var alvo = document.getElementById(CONTENT_ID);
		if (!alvo) { return false; }

		alvo.innerHTML = valor;

		var backup = document.getElementById(BACKUP_ID);
		if (backup) {
			backup.innerHTML = valor;
			varMap = {}; varSeq = 0; mapRoot = alvo;
			try { mapTree(alvo, backup); } catch (e) { window.console && console.error('Mapeamento in-place:', e); }
		}

		if (c2fEditor && typeof c2fEditor.afterDomMutation === 'function') { c2fEditor.afterDomMutation(); }
		return true;
	}

	function codeCriarEditor(tab) {
		var area = codePanel.querySelector('[data-code-area="' + tab + '"]');
		if (!area || codeEditors[tab]) { return; }

		var config = CODE_TABS.filter(function (c) { return c.id === tab; })[0];

		if (!window.CodeMirror) { codeEditors[tab] = area; return; } // degradação: textarea puro

		var cm = window.CodeMirror.fromTextArea(area, {
			lineNumbers: true,
			lineWrapping: true,
			styleActiveLine: true,
			matchBrackets: true,
			mode: config.modo,
			htmlMode: config.modo === 'htmlmixed',
			indentUnit: 4,
			readOnly: config.leitura,
			theme: 'tomorrow-night-bright'
		});
		cm.setSize('100%', '100%');

		if (!config.leitura) {
			cm.on('change', function () {
				pageCode[tab] = cm.getValue();
				pageCodeDirty[tab] = true;
				if (tab !== 'css') { return; }
				// Só o CSS é aplicado ao vivo — é barato e não destrói nós do DOM.
				if (codeApplyTimer) { clearTimeout(codeApplyTimer); }
				codeApplyTimer = setTimeout(function () { codeAplicarCss(pageCode.css); }, 400);
			});
		}

		codeEditors[tab] = cm;
	}

	function codeDefinirValor(tab, valor) {
		var editor = codeEditors[tab];
		if (!editor) { return; }
		if (editor.getDoc) { editor.getDoc().setValue(valor); editor.refresh(); }
		else { editor.value = valor; }
	}

	function codeSelecionarAba(tab) {
		codeActiveTab = tab;

		Array.prototype.forEach.call(codePanel.querySelectorAll('[data-code-tab]'), function (botao) {
			var on = botao.getAttribute('data-code-tab') === tab;
			botao.style.background = on ? '#0f172a' : 'transparent';
			botao.style.color = on ? '#f8fafc' : '#475569';
		});
		Array.prototype.forEach.call(codePanel.querySelectorAll('[data-code-body]'), function (corpo) {
			corpo.style.display = corpo.getAttribute('data-code-body') === tab ? 'block' : 'none';
		});

		var aplicar = codePanel.querySelector('[data-code-action="apply-html"]');
		if (aplicar) { aplicar.style.display = (tab === 'html') ? 'inline-block' : 'none'; }

		codeCriarEditor(tab);

		// HTML e CSS Compilado são derivados do DOM VIVO: recarregam a cada abertura de aba, senão o
		// painel mostraria o estado de quando foi montado.
		if (tab === 'html' || tab === 'css_compiled') { codeDefinirValor(tab, codeValorDaAba(tab)); }
		else if (codeEditors[tab] && codeEditors[tab].refresh) { codeEditors[tab].refresh(); }
	}

	// Estilos do painel, espelhando os do `c2f-ai-panel` do motor (`.c2f-he-live-*`): overlay de tela
	// cheia com backdrop e a caixa CENTRALIZADA. Escritos aqui, e não reusando as classes do motor,
	// porque o CSS delas só é injetado quando um painel do motor abre pela primeira vez — o painel de
	// Código pode ser o primeiro a aparecer.
	function ensureCodePanelStyles() {
		if (document.getElementById('c2f-code-panel-styles')) { return; }
		var st = document.createElement('style');
		st.id = 'c2f-code-panel-styles';
		st.setAttribute('data-c2f-tailwind-role', 'editor-ui'); // req-117: fora da captura.
		st.textContent =
			'#c2f-code-panel{position:fixed;inset:0;z-index:1000003;display:none;isolation:isolate;' +
			'pointer-events:auto;font:14px system-ui,sans-serif !important;color:#0f172a !important;}' +
			'#c2f-code-panel .c2f-code-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.55);}' +
			'#c2f-code-panel .c2f-code-box{position:relative;width:900px;max-width:96vw;height:78vh;' +
			'min-width:360px;min-height:280px;margin:7vh auto;background:#fff !important;border-radius:10px;' +
			'box-shadow:0 20px 50px rgba(0,0,0,.4);display:flex;flex-direction:column;overflow:hidden;resize:both;}' +
			'#c2f-code-panel .c2f-code-head{padding:10px 14px;border-bottom:1px solid #e5e7eb;display:flex;' +
			'align-items:center;gap:6px;flex:0 0 auto;}' +
			'#c2f-code-panel .c2f-code-body{flex:1 1 auto;min-height:0;overflow:hidden;}' +
			'#c2f-code-panel .c2f-code-foot{padding:6px 14px;border-top:1px solid #e5e7eb;font-size:11px;' +
			'color:#94a3b8;flex:0 0 auto;}' +
			'#c2f-code-panel .CodeMirror{height:100%;}';
		document.head.appendChild(st);
	}

	function buildCodePanel() {
		if (codePanel) { return codePanel; }

		ensureCodePanelStyles();

		codePanel = document.createElement('div');
		codePanel.id = 'c2f-code-panel';

		var abas = '';
		var corpos = '';
		CODE_TABS.forEach(function (tab) {
			abas += '<button type="button" data-code-tab="' + tab.id + '" ' +
				'style="border:0;background:transparent;color:#475569;padding:6px 12px;border-radius:6px;cursor:pointer;font:600 12px sans-serif;">' +
				esc(t(tab.rotulo[0], tab.rotulo[1])) + '</button>';
			corpos += '<div data-code-body="' + tab.id + '" style="display:none;height:100%;">' +
				'<textarea data-code-area="' + tab.id + '"></textarea></div>';
		});

		codePanel.innerHTML =
			'<div class="c2f-code-backdrop"></div>' +
			'<div class="c2f-code-box">' +
			'<div class="c2f-code-head">' +
			'<strong style="font-size:13px;margin-right:6px;">' + esc(t('Código da página', 'Page code')) + '</strong>' +
			abas +
			'<span style="margin-left:auto;"></span>' +
			'<button type="button" data-code-action="apply-html" ' +
			'style="border:0;background:#2563eb;color:#fff;border-radius:6px;padding:6px 12px;cursor:pointer;font:600 12px sans-serif;display:none;">' +
			esc(t('Aplicar ao conteúdo', 'Apply to content')) + '</button>' +
			'<button type="button" data-code-action="close" ' +
			'style="border:0;background:#e2e8f0;border-radius:6px;padding:6px 10px;cursor:pointer;color:#0f172a;">✕</button>' +
			'</div>' +
			'<div class="c2f-code-body">' + corpos + '</div>' +
			'<div class="c2f-code-foot">' +
			esc(t('CSS é aplicado ao vivo. O HTML é aplicado pelo botão. Tudo é gravado ao salvar a página.',
				'CSS applies live. HTML applies through the button. Everything is stored when you save the page.')) +
			'</div>' +
			'</div>';

		document.body.appendChild(codePanel);

		// Barreira na fase de BOLHA (nunca em captura): em captura o `stopPropagation` impediria o
		// evento de chegar aos próprios botões e ao CodeMirror do painel. A blindagem principal
		// continua sendo `isEditorOwned()` no motor — ver req-112 §M5.
		['mousedown', 'mouseup', 'click', 'mousemove', 'mouseover', 'mouseout', 'dblclick', 'contextmenu', 'keydown']
			.forEach(function (evento) {
				codePanel.addEventListener(evento, function (e) { e.stopPropagation(); });
			});

		codePanel.addEventListener('click', function (e) {
			// Clique no backdrop fecha, como em qualquer modal do editor. Com o overlay de tela cheia
			// este é o "clique fora": nenhum clique chega à página atrás.
			if (e.target.classList && e.target.classList.contains('c2f-code-backdrop')) {
				e.preventDefault();
				closeCodePanel();
				return;
			}

			var aba = e.target.closest && e.target.closest('[data-code-tab]');
			if (aba) { e.preventDefault(); codeSelecionarAba(aba.getAttribute('data-code-tab')); return; }

			var acao = e.target.closest && e.target.closest('[data-code-action]');
			if (!acao) { return; }
			e.preventDefault();

			if (acao.getAttribute('data-code-action') === 'close') { closeCodePanel(); return; }

			if (acao.getAttribute('data-code-action') === 'apply-html') {
				var editor = codeEditors.html;
				var valor = editor ? (editor.getDoc ? editor.getDoc().getValue() : editor.value) : '';
				if (!codeAplicarHtml(valor)) {
					window.alert(t('Não foi possível aplicar: o conteúdo da página não foi encontrado.',
						'Could not apply: the page content was not found.'));
				}
			}
		});

		// Rede de segurança para o clique fora: com o overlay de tela cheia quem responde é o
		// backdrop (acima), mas se o painel for exibido sem cobrir a viewport inteira — outro
		// contexto, CSS do site sobrescrevendo o overlay — o clique na página ainda o fecha.
		// Sem isso, o painel só fechava pelo ✕ ou clicando na Editbar, ao contrário do resto da UI.
		document.addEventListener('mousedown', function (e) {
			if (!codePanel || codePanel.style.display === 'none') { return; }
			if (e.target.closest && e.target.closest('#c2f-code-panel')) { return; }
			// O seletor de arquivos e os modais do motor sobem acima do painel; fechar por baixo
			// deles derrubaria o que o usuário está fazendo em cima.
			if (e.target.closest && e.target.closest('#c2f-he-imagepick-overlay, #c2f-he-embed-modal, #html-editor-modal')) { return; }
			closeCodePanel();
		});

		return codePanel;
	}

	// As coordenadas do botão são ignoradas de propósito: o painel é CENTRALIZADO, no padrão do
	// `c2f-ai-panel`. Ancorado ao botão — que fica no canto direito da Editbar — ele nascia deslocado
	// para a borda da tela. A assinatura mantém `x`/`y` porque a mensagem da barra os envia, como
	// fazem os demais painéis.
	function openCodePanel(x, y) {
		if (!c2fEditor) {
			window.alert(t('Entre no modo de edição para abrir o código da página.',
				'Enter edit mode to open the page code.'));
			return;
		}

		buildCodePanel();

		codePanel.style.display = 'block';

		// O CodeMirror mede o contêiner ao nascer; criar os editores com o painel já visível evita a
		// caixa de 1px de altura que aparecia nos painéis montados escondidos (req-106 rodada 3).
		ensureCodeMirror(function () {
			CODE_TABS.forEach(function (tab) {
				codeCriarEditor(tab.id);
				codeDefinirValor(tab.id, codeValorDaAba(tab.id));
			});
			codeSelecionarAba(codeActiveTab);
		});
	}

	// req-106 rodada 2: fecha TODA a UI flutuante de uma vez. Usado pelo aviso
	// `c2f-toolbar:ui-dismiss`, postado pela barra a cada clique dentro do iframe da Editbar — de
	// onde o `mousedown` desta página nunca chega e nenhum backdrop é atingido. Cobre os painéis
	// desta página (Opções, "+", Backups) e os do motor (Modelos, IA, Código Customizado, modais de
	// edição/embed e o seletor de arquivos), que já fechavam ao clicar fora na área editável.
	function dismissHostPanels() {
		// req-110: com o seletor de arquivos aberto, um clique na Editbar não deve derrubar o painel
		// de configurações por baixo dele — o usuário perderia o que já digitou.
		if (pageConfigPickerOverlay && pageConfigPickerOverlay.style.display === 'block') { return; }

		closeAddPanel();
		closeBackupPanel();
		closeViewOptionsPanel();
		closePageConfigPanel();
		closeCodePanel(); // req-117
		if (c2fEditor && typeof c2fEditor.dismissFloatingUi === 'function') { c2fEditor.dismissFloatingUi(); }
	}

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
		// req-110: seleção do gerenciador de arquivos para a Imagem de Destaque. O `admin-arquivos`
		// posta `{moduloId, moduloOpcao, data: "<json>"}` — mesmo contrato do picker do editor.
		if (data && data.moduloId === 'admin-arquivos' && typeof data.data === 'string') {
			var selecao;
			try { selecao = JSON.parse(data.data); } catch (erro) { selecao = null; }
			if (aplicarSelecaoDeImagem(selecao)) { return; }
		}

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
			case 'c2f-toolbar:edit-code': // req-117
				openCodePanel(data.x, data.y);
				break;
			case 'c2f-toolbar:edit-view-options':
				openViewOptionsPanel(data.x, data.y);
				break;
			case 'c2f-toolbar:page-config':
				openPageConfigPanel(data.x, data.y, data.page_id);
				break;
			case 'c2f-toolbar:ui-dismiss':
				dismissHostPanels();
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
