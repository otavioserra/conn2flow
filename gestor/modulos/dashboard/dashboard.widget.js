/**
 * Controlador público do widget "Lousa" (REQ-252).
 *
 * No modo lousa, calcula quantas colunas cabem na largura do contêiner e dá coluna e linha a cada item,
 * com as mesmas medidas e a mesma regra de arranjo da área de widgets do Dashboard (`arrange` em
 * `dashboard.js`): quem tem posição tenta ficar nela, encosta na borda se não couber e desce se o lugar
 * estiver ocupado; abaixo de 640 px, uma coluna. No modo grade quem posiciona é a folha do componente.
 * Também desenha os ícones dos objetos, carregando o Lucide quando a página ainda não tem.
 */
(function () {
	var CELL = 90, GAP = 20, ROW = 20, MIN_COLS = 2, MAX_COLS = 24;
	var lucideLoading = false;

	function columns(board) {
		var width = board.clientWidth;
		if (!width) return 12;
		if (width < 640) return 1;
		return Math.max(MIN_COLS, Math.min(MAX_COLS, Math.floor((width + GAP) / (CELL + GAP))));
	}

	// Linhas ocupadas: a altura do item mais uma linha, que é a distância para o de baixo.
	function rows(height) { return Math.round((height || 220) / ROW) + 1; }

	function arrange(list, cols) {
		var taken = {}, places = {};
		function free(x, y, w, h) { for (var i = x; i < x + w; i++) for (var j = y; j < y + h; j++) if (taken[i + ':' + j]) return false; return true; }
		list.map(function (item, index) { return {item: item, index: index, placed: item.x !== null && item.y !== null}; }).sort(function (a, b) {
			if (a.placed !== b.placed) return a.placed ? -1 : 1;
			if (!a.placed) return a.index - b.index;
			return (a.item.y - b.item.y) || (a.item.x - b.item.x) || (a.index - b.index);
		}).forEach(function (entry) {
			var item = entry.item, w = Math.max(1, Math.min(item.width, cols)), h = rows(item.height), x = 0, y = 0;
			if (entry.placed) { x = Math.max(0, Math.min(item.x, cols - w)); y = item.y; while (!free(x, y, w, h)) y++; }
			else { search: for (y = 0; ; y++) for (x = 0; x + w <= cols; x++) if (free(x, y, w, h)) break search; }
			for (var i = x; i < x + w; i++) for (var j = y; j < y + h; j++) taken[i + ':' + j] = true;
			places[entry.index] = {x: x, y: y, w: w, h: h};
		});
		// Vão entre itens fica; faixa vazia acima de todos não.
		var top = Object.keys(places).reduce(function (min, key) { return Math.min(min, places[key].y); }, Infinity);
		if (top > 0 && top !== Infinity) Object.keys(places).forEach(function (key) { places[key].y -= top; });
		return places;
	}

	function cell(value) { var n = Number(value); return value === null || value === '' || !isFinite(n) || n < 0 ? null : Math.floor(n); }

	function apply(board) {
		// Item escondido pela folha (largura da janela) não ocupa lugar.
		var items = Array.prototype.filter.call(board.children, function (el) {
			return el.classList.contains('c2f-lousa-item') && window.getComputedStyle(el).display !== 'none';
		});
		var list = items.map(function (el) {
			return {x: cell(el.getAttribute('data-x')), y: cell(el.getAttribute('data-y')), width: Number(el.getAttribute('data-w')) || 4, height: Number(el.getAttribute('data-h')) || 220};
		});
		var cols = columns(board), places = arrange(list, cols);
		board.style.setProperty('--c2f-cols', cols);
		board.setAttribute('data-cols', cols);
		items.forEach(function (el, index) {
			var place = places[index];
			el.style.gridColumn = (place.x + 1) + ' / span ' + place.w;
			el.style.gridRow = (place.y + 1) + ' / span ' + place.h;
		});
		board.classList.add('is-arranged');
	}

	function icons(board) {
		if (!board.querySelector('[data-lucide]')) return;
		if (window.lucide && window.lucide.createIcons) { window.lucide.createIcons(); return; }
		var url = board.getAttribute('data-lucide-url');
		if (!url || lucideLoading) return;
		lucideLoading = true;
		var script = document.createElement('script');
		script.src = url;
		script.onload = function () { if (window.lucide && window.lucide.createIcons) window.lucide.createIcons(); };
		document.head.appendChild(script);
	}

	function start() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-c2f-lousa]'), function (board) {
			if (board._c2fLousa) return;
			board._c2fLousa = true;
			icons(board);
			if (board.getAttribute('data-mode') !== 'lousa') return;
			var seen = '';
			function refresh() {
				// Só refaz quando muda a largura do contêiner ou da janela (esta decide o que fica escondido).
				var now = board.clientWidth + ':' + window.innerWidth;
				if (now === seen) return;
				seen = now;
				apply(board);
			}
			refresh();
			window.addEventListener('resize', refresh);
			if (typeof ResizeObserver !== 'undefined') new ResizeObserver(refresh).observe(board);
		});
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
