/*
 * BATCH-090 (req-090): Gerenciador de arquivos por árvore física de diretórios.
 *
 * Duas telas convivem neste arquivo (ativadas por presença de container):
 *  - Listagem (#c2f-files-app): navegação física, 4 modos de visualização,
 *    breadcrumb, seleção em lote, miniaturas lazy em lote, CRUD de pastas.
 *  - Upload (#fileupload): drag & drop, seletor de tamanho de pré-visualização
 *    e escolha/criação da pasta de destino.
 */

// ===== Helpers puros (compartilhados)

function adminArquivosFormatBytes(a, b) {
	b = (typeof b === 'undefined') ? 2 : b;
	if (0 === a) return '0 Bytes';
	var c = 0 > b ? 0 : b, d = Math.floor(Math.log(a) / Math.log(1024));
	return parseFloat((a / Math.pow(1024, d)).toFixed(c)) + ' ' + ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][d];
}

function adminArquivosEsc(s) {
	return String(s === null || typeof s === 'undefined' ? '' : s)
		.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// Junta um diretório relativo com um nome de item, evitando barras duplicadas.
function adminArquivosJoin(dir, nome) {
	if (!dir) return nome;
	return dir.replace(/\/+$/, '') + '/' + nome;
}

// Extensões executáveis bloqueadas no cliente (espelha o backend; a autoridade é o servidor).
function adminArquivosExtensaoPerigosa(nome) {
	var perigosas = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar',
		'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'ksh', 'zsh', 'com', 'exe', 'bat', 'cmd', 'msi',
		'dll', 'so', 'jar', 'asp', 'aspx', 'jsp', 'jspx', 'htaccess', 'htpasswd', 'ht', 'shtml'];
	var base = String(nome).toLowerCase().split('/').pop().split('\\').pop();
	if (base === '.htaccess' || base === '.htpasswd' || base === '.user.ini') return true;
	var partes = base.split('.');
	partes.shift();
	for (var i = 0; i < partes.length; i++) {
		if (perigosas.indexOf(partes[i]) !== -1) return true;
	}
	return false;
}

function adminArquivosTipoPorNome(nome) {
	var ext = String(nome).toLowerCase().split('.').pop();
	if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'tiff'].indexOf(ext) !== -1) return 'image';
	if (['mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv', 'm4v', 'wmv', 'flv', '3gp'].indexOf(ext) !== -1) return 'video';
	if (['mp3', 'wav', 'ogg', 'oga', 'flac', 'aac', 'm4a', 'wma', 'opus'].indexOf(ext) !== -1) return 'audio';
	return 'file';
}

// Exposição para testes (Node) sem quebrar o browser.
if (typeof module !== 'undefined' && module.exports) {
	module.exports = {
		adminArquivosFormatBytes: adminArquivosFormatBytes,
		adminArquivosEsc: adminArquivosEsc,
		adminArquivosJoin: adminArquivosJoin,
		adminArquivosExtensaoPerigosa: adminArquivosExtensaoPerigosa,
		adminArquivosTipoPorNome: adminArquivosTipoPorNome
	};
}

$(document).ready(function () {

	if (typeof gestor === 'undefined' || !gestor.adminArquivos) return;

	var cfg = gestor.adminArquivos;
	var i18n = cfg.i18n || {};
	var t = function (id) { return typeof i18n[id] !== 'undefined' ? i18n[id] : id; };
	var iconePadrao = {
		image: gestor.raiz + 'images/imagem-padrao.png',
		video: gestor.raiz + 'images/video-padrao.png',
		audio: gestor.raiz + 'images/audio-padrao.png',
		file: gestor.raiz + 'images/file-padrao.png'
	};

	// =====================================================================
	// TELA DE LISTAGEM
	// =====================================================================

	if ($('#c2f-files-app').length > 0) {

		var LS_DIR = 'adminArquivosDir';

		// Restaura a última pasta acessada (cache), exceto no modo picker (iframe).
		var dirInicial = cfg.dirInicial || '';
		if (!cfg.paginaIframe) {
			var dirSalvo = localStorage.getItem(LS_DIR);
			if (dirSalvo !== null) dirInicial = dirSalvo;
		}

		var estado = {
			dir: dirInicial,
			pagina: 0,
			totalPaginas: 0,
			view: localStorage.getItem('adminArquivosView') || 'large',
			dados: null,
			selecionados: {}
		};

		// Mantém o href do botão "Adicionar" apontando para a pasta atual.
		function atualizarAddHref() {
			var href = gestor.raiz + gestor.moduloId + '/adicionar/?dir=' + encodeURIComponent(estado.dir);
			if (cfg.paginaIframe) href += '&paginaIframe=sim';
			$('.c2f-folder-tools a[data-id="adicionar"]').attr('href', href);
		}

		var $app = $('#c2f-files-app');
		var $lista = $('#c2f-files-list');
		var $breadcrumb = $('#c2f-breadcrumb');
		var $barra = $('#c2f-thumbs-bar');
		var $more = $('#c2f-more');

		// ===== Filtros server-side (calendário + dropdowns Fomantic)
		var textCal = {
			days: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
			months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
			monthsShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
			today: 'Hoje', now: 'Agora', am: 'AM', pm: 'PM'
		};
		if ($('#rangestart').length) $('#rangestart').calendar({ text: textCal, type: 'month', endCalendar: $('#rangeend') });
		if ($('#rangeend').length) $('#rangeend').calendar({ text: textCal, type: 'month', startCalendar: $('#rangestart') });
		$('.ui.dropdown').dropdown();
		$('.c2f-filter-accordion').accordion();
		$('#c2f-files-app .ui.checkbox').checkbox();
		$('.segment .button').popup({ delay: { show: 150, hide: 0 }, position: 'top right', variation: 'inverted' });

		// ===== Busca instantânea (client-side) sobre os itens já carregados
		var $busca = $('#c2f-search');
		var $buscaIcon = $('.c2f-search-icon');
		var $buscaClear = $('.c2f-search-clear');

		function aplicarBusca() {
			if (!$busca.length) return;
			var q = ($busca.val() || '').trim().toLowerCase();
			$buscaClear.toggleClass('hidden', q === '');
			$buscaIcon.toggleClass('hidden', q !== '');
			$lista.find('.c2f-item').each(function () {
				var nome = ($(this).attr('data-nome') || '').toLowerCase();
				$(this).toggleClass('c2f-hidden-search', q !== '' && nome.indexOf(q) === -1);
			});
		}

		$busca.on('input', aplicarBusca);
		$buscaClear.on('click', function () { $busca.val(''); aplicarBusca(); $busca.trigger('focus'); });

		function coletarFiltros() {
			var filtros = {};
			if ($('#rangestart').length && $('#rangestart').calendar('get date')) filtros.dataDe = $('#rangestart').calendar('get date');
			if ($('#rangeend').length && $('#rangeend').calendar('get date')) filtros.dataAte = $('#rangeend').calendar('get date');
			if ($('#categories').length && $('#categories').dropdown('get value').length > 0) filtros.categorias = $('#categories').dropdown('get value');
			if ($('#order').length) filtros.order = $('#order').dropdown('get value');
			return filtros;
		}

		// ===== Modo de visualização

		function aplicarView() {
			$app.removeClass('view-large view-list view-small view-medium').addClass('view-' + estado.view);
			$('.c2f-view-btn').removeClass('active');
			$('.c2f-view-btn[data-view="' + estado.view + '"]').addClass('active');
		}

		$('.c2f-view-btn').on('click', function () {
			estado.view = $(this).attr('data-view');
			localStorage.setItem('adminArquivosView', estado.view);
			aplicarView();
			if (estado.dados) renderLista(estado.dados, false);
		});

		// ===== Breadcrumb

		function renderBreadcrumb(itens) {
			var html = '';
			for (var i = 0; i < itens.length; i++) {
				var it = itens[i];
				var nome = it.raiz ? t('breadcrumb-root') : it.nome;
				if (i > 0) html += '<i class="right angle icon divider"></i>';
				if (i === itens.length - 1) {
					html += '<div class="active section">' + adminArquivosEsc(nome) + '</div>';
				} else {
					html += '<a class="section c2f-crumb" data-dir="' + adminArquivosEsc(it.caminho) + '">' + adminArquivosEsc(nome) + '</a>';
				}
			}
			$breadcrumb.html(html);
		}

		$breadcrumb.on('click', '.c2f-crumb', function () {
			navegar($(this).attr('data-dir'));
		});

		// ===== Templates de item por modo de visualização

		function templatePasta(p) {
			var cam = adminArquivosEsc(p.caminho);
			return '' +
				'<div class="c2f-item c2f-folder" data-tipo="pasta" data-caminho="' + cam + '" data-nome="' + adminArquivosEsc(p.nome) + '">' +
				'  <div class="c2f-check"><div class="ui checkbox"><input type="checkbox" class="c2f-sel"><label></label></div></div>' +
				'  <div class="c2f-thumb c2f-folder-open"><i class="folder icon huge yellow"></i></div>' +
				'  <div class="c2f-meta">' +
				'    <div class="c2f-name" title="' + adminArquivosEsc(p.nome) + '">' + adminArquivosEsc(p.nome) + '</div>' +
				'    <div class="c2f-sub c2f-col-date">' + adminArquivosEsc(p.data) + '</div>' +
				'    <div class="c2f-sub c2f-col-type">' + t('col-type') + ': —</div>' +
				'    <div class="c2f-sub c2f-col-size">—</div>' +
				'  </div>' +
				'  <div class="c2f-actions">' +
				'    <button class="ui mini icon button c2f-rename" title="' + adminArquivosEsc(t('folder-rename')) + '"><i class="edit icon"></i></button>' +
				'    <button class="ui mini icon red button c2f-del" title="' + adminArquivosEsc(t('list-button-del')) + '"><i class="trash icon"></i></button>' +
				'  </div>' +
				'</div>';
		}

		function templateArquivo(a) {
			var cam = adminArquivosEsc(a.caminho);
			var img = a.imgSrc || iconePadrao[a.tipo] || iconePadrao.file;
			var acaoSelecionar = cfg.paginaIframe
				? '<button class="ui mini icon blue button c2f-select" title="' + adminArquivosEsc(t('list-button-select')) + '"><i class="check icon"></i></button>'
				: '<button class="ui mini icon button c2f-copy" title="' + adminArquivosEsc(t('list-button-copy')) + '"><i class="linkify icon"></i></button>';
			return '' +
				'<div class="c2f-item c2f-file" data-tipo="arquivo" data-caminho="' + cam + '" data-nome="' + adminArquivosEsc(a.nome) + '"' +
				'     data-url="' + adminArquivosEsc(a.url) + '" data-mime="' + adminArquivosEsc(a.mime) + '" data-tipoarq="' + adminArquivosEsc(a.tipo) + '">' +
				'  <div class="c2f-check"><div class="ui checkbox"><input type="checkbox" class="c2f-sel"><label></label></div></div>' +
				'  <div class="c2f-thumb"><img class="c2f-img" data-caminho="' + cam + '" src="' + adminArquivosEsc(img) + '" alt="' + adminArquivosEsc(a.nome) + '"></div>' +
				'  <div class="c2f-meta">' +
				'    <div class="c2f-name" title="' + adminArquivosEsc(a.nome) + '">' + adminArquivosEsc(a.nome) + '</div>' +
				'    <div class="c2f-sub c2f-col-date">' + adminArquivosEsc(a.data) + '</div>' +
				'    <div class="c2f-sub c2f-col-type">' + adminArquivosEsc(a.tipo) + '</div>' +
				'    <div class="c2f-sub c2f-col-size">' + adminArquivosEsc(a.sizeFmt) + '</div>' +
				'  </div>' +
				'  <div class="c2f-actions">' +
				acaoSelecionar +
				'    <button class="ui mini icon button c2f-rename" title="' + adminArquivosEsc(t('folder-rename')) + '"><i class="edit icon"></i></button>' +
				'    <button class="ui mini icon red button c2f-del" title="' + adminArquivosEsc(t('list-button-del')) + '"><i class="trash icon"></i></button>' +
				'  </div>' +
				'</div>';
		}

		// ===== Render principal

		function renderLista(dados, append) {
			estado.dados = dados;
			renderBreadcrumb(dados.breadcrumb || []);

			var html = '';
			var pastas = dados.pastas || [];
			var arquivos = dados.arquivos || [];

			for (var i = 0; i < pastas.length; i++) html += templatePasta(pastas[i]);
			for (var j = 0; j < arquivos.length; j++) html += templateArquivo(arquivos[j]);

			if (append) {
				$lista.append(html);
			} else {
				$lista.html(html);
			}

			$lista.find('.ui.checkbox').checkbox();

			// Estado vazio
			var vazio = pastas.length === 0 && arquivos.length === 0 && estado.pagina === 0;
			$('#c2f-empty').toggleClass('hidden', !vazio);

			// Paginação
			estado.totalPaginas = parseInt(dados.totalPaginas, 10) || 0;
			if (estado.pagina < estado.totalPaginas - 1) $more.removeClass('hidden');
			else $more.addClass('hidden');

			// Miniaturas faltantes
			if (dados.thumbsMissing && dados.thumbsMissing.length > 0) {
				processarMiniaturas(dados.thumbsMissing.slice());
			}

			atualizarBarraSelecao();

			// Reaplica a busca instantânea aos itens recém-renderizados.
			aplicarBusca();
		}

		// ===== Miniaturas lazy em lote

		function processarMiniaturas(fila) {
			var lote = cfg.loteMiniaturas || 5;
			$barra.removeClass('hidden');

			function proximo() {
				if (fila.length === 0) {
					$barra.addClass('hidden');
					return;
				}
				var atual = fila.splice(0, lote);
				$.ajax({
					type: 'POST',
					url: gestor.raiz + gestor.moduloId + '/',
					data: { opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao: 'miniaturas', arquivos: JSON.stringify(atual) },
					dataType: 'json',
					success: function (resp) {
						if (resp && resp.resultados) {
							for (var k = 0; k < resp.resultados.length; k++) {
								var r = resp.resultados[k];
								if (r.ok && r.miniUrl) {
									$lista.find('.c2f-img[data-caminho="' + r.caminho.replace(/"/g, '\\"') + '"]').attr('src', r.miniUrl);
								}
							}
						}
						proximo();
					},
					error: function () { proximo(); }
				});
			}
			proximo();
		}

		// ===== Carregamento / navegação

		function carregar(append) {
			var filtros = coletarFiltros();
			$('#gestor-listener').trigger('carregar_abrir');
			$.ajax({
				type: 'POST',
				url: gestor.raiz + gestor.moduloId + '/',
				data: {
					opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao: 'navegar',
					dir: estado.dir, pagina: estado.pagina, filtros: JSON.stringify(filtros)
				},
				dataType: 'json',
				success: function (dados) {
					if (dados.status === 'Ok') {
						estado.dir = dados.dir;
						if (!cfg.paginaIframe) localStorage.setItem(LS_DIR, estado.dir);
						atualizarAddHref();
						renderLista(dados, append);
					} else {
						console.log('ERROR - listar - ' + dados.status);
					}
					$('#gestor-listener').trigger('carregar_fechar');
				},
				error: function (txt) {
					if (txt.status === 401) { window.open(gestor.raiz + ((txt.responseJSON && txt.responseJSON.redirect) ? txt.responseJSON.redirect : 'signin/'), '_self'); return; }
					console.log('ERROR AJAX - listar'); console.log(txt);
					$('#gestor-listener').trigger('carregar_fechar');
				}
			});
		}

		function navegar(dir) {
			estado.dir = dir || '';
			estado.pagina = 0;
			estado.selecionados = {};
			if ($busca && $busca.length) $busca.val(''); // busca é por pasta
			carregar(false);
		}

		$more.on('click', function () {
			estado.pagina++;
			carregar(true);
		});

		$('#c2f-filter').on('click', function () { estado.pagina = 0; carregar(false); });
		$('#c2f-clear').on('click', function () {
			if ($('#rangestart').length) $('#rangestart').calendar('clear');
			if ($('#rangeend').length) $('#rangeend').calendar('clear');
			if ($('#categories').length) $('#categories').dropdown('clear');
			if ($('#order').length) $('#order').dropdown('set selected', 'alphabetical-asc');
			estado.pagina = 0; carregar(false);
		});

		// ===== Cliques nos itens

		$lista.on('click', '.c2f-folder .c2f-thumb, .c2f-folder .c2f-name', function () {
			navegar($(this).closest('.c2f-item').attr('data-caminho'));
		});

		// ===== Clique em arquivo: imagem abre a galeria; demais abrem em nova aba
		$lista.on('click', '.c2f-file .c2f-thumb, .c2f-file .c2f-name', function () {
			var $item = $(this).closest('.c2f-item');
			if ($item.attr('data-tipoarq') === 'image') abrirGaleria($item);
			else window.open($item.attr('data-url'), '_blank');
		});

		// ===== Galeria de imagens (modal) — coleta as imagens da pasta atual carregadas
		var galeria = { itens: [], idx: 0 };

		function coletarImagens() {
			var arr = [];
			$lista.find('.c2f-file[data-tipoarq="image"]').each(function () {
				var $i = $(this);
				arr.push({
					caminho: $i.attr('data-caminho'),
					url: $i.attr('data-url'),
					nome: $i.attr('data-nome'),
					mime: $i.attr('data-mime'),
					data: $i.find('.c2f-col-date').first().text(),
					thumb: $i.find('.c2f-img').attr('src')
				});
			});
			return arr;
		}

		function galeriaItemAtual() { return galeria.itens[galeria.idx]; }

		function garantirModalGaleria() {
			if ($('#c2f-gallery-modal').length) return;

			// Ações do item exibido (mesma lógica da listagem): selecionar (picker) OU
			// copiar URL, além de renomear e excluir.
			var acoes = '';
			if (cfg.paginaIframe) {
				acoes += '<button class="ui blue button c2f-gal-select"><i class="check icon"></i> ' + adminArquivosEsc(t('list-button-select')) + '</button>';
			} else {
				acoes += '<button class="ui button c2f-gal-copy"><i class="linkify icon"></i> ' + adminArquivosEsc(t('list-button-copy')) + '</button>';
			}
			acoes += '<a class="ui button c2f-gal-open" target="_blank" rel="noopener"><i class="external alternate icon"></i> ' + adminArquivosEsc(t('open-new-tab')) + '</a>';
			acoes += '<button class="ui button c2f-gal-rename"><i class="edit icon"></i> ' + adminArquivosEsc(t('folder-rename')) + '</button>';
			acoes += '<button class="ui red button c2f-gal-del"><i class="trash icon"></i> ' + adminArquivosEsc(t('list-button-del')) + '</button>';

			var html =
				'<div class="ui fullscreen modal c2f-gallery-modal" id="c2f-gallery-modal">' +
				'  <i class="close icon"></i>' +
				'  <div class="header"><span class="c2f-gallery-name"></span> <span class="c2f-gallery-counter"></span></div>' +
				'  <div class="content c2f-gallery-content">' +
				'    <button class="ui circular icon button c2f-gallery-prev"><i class="chevron left icon"></i></button>' +
				'    <div class="c2f-gallery-stage"><img class="c2f-gallery-img" src=""></div>' +
				'    <button class="ui circular icon button c2f-gallery-next"><i class="chevron right icon"></i></button>' +
				'  </div>' +
				'  <div class="c2f-gallery-strip"></div>' +
				'  <div class="actions c2f-gallery-actions">' + acoes + '</div>' +
				'</div>';
			$(html).appendTo('body');

			var $m = $('#c2f-gallery-modal');
			$m.on('click', '.c2f-gallery-prev', function (e) { e.stopPropagation(); passoGaleria(-1); });
			$m.on('click', '.c2f-gallery-next', function (e) { e.stopPropagation(); passoGaleria(1); });
			$m.on('click', '.c2f-gallery-strip img', function () {
				galeria.idx = parseInt($(this).attr('data-idx'), 10) || 0;
				mostrarGaleria();
			});

			// ===== Ações do item exibido

			$m.on('click', '.c2f-gal-select', function () {
				var it = galeriaItemAtual(); if (!it) return;
				var dados = { id: it.caminho, caminho: it.caminho, imgSrc: it.thumb, nome: it.nome, data: it.data, tipo: it.mime };
				window.parent.postMessage(JSON.stringify({ moduloId: gestor.moduloId, moduloOpcao: gestor.moduloOpcao, data: JSON.stringify(dados) }), '*');
				$m.modal('hide');
			});

			$m.on('click', '.c2f-gal-copy', function () {
				var it = galeriaItemAtual(); if (!it) return;
				navigator.clipboard.writeText(it.url);
				$(this).popup({ content: t('copied'), on: 'manual' }).popup('show');
				var self = this;
				setTimeout(function () { try { $(self).popup('hide'); } catch (err) {} }, 900);
			});

			$m.on('click', '.c2f-gal-rename', function () {
				var it = galeriaItemAtual(); if (!it) return;
				var novo = window.prompt(t('folder-rename'), it.nome);
				if (novo === null) return;
				novo = novo.trim();
				if (!novo || novo === it.nome) return;
				$.ajax({
					type: 'POST', url: gestor.raiz + gestor.moduloId + '/', dataType: 'json',
					data: { opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao: 'renomear', caminho: it.caminho, nome: novo },
					success: function (resp) {
						if (resp.status === 'Ok' && resp.item) {
							it.caminho = resp.item.caminho;
							it.url = resp.item.url;
							it.nome = resp.item.nome;
							if (resp.item.imgSrc) it.thumb = resp.item.imgSrc;
							galeria.dirty = true;
							mostrarGaleria();
						} else {
							alertaSimples(resp.status);
						}
					}
				});
			});

			$m.on('click', '.c2f-gal-del', function () {
				var it = galeriaItemAtual(); if (!it) return;
				if (!window.confirm(t('delete-confirm'))) return;
				$.ajax({
					type: 'POST', url: gestor.raiz + gestor.moduloId + '/', dataType: 'json',
					data: { opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao: 'excluir', itens: JSON.stringify([{ caminho: it.caminho, tipo: 'arquivo' }]), recursivo: 'false' },
					success: function () {
						galeria.dirty = true;
						galeria.itens.splice(galeria.idx, 1);
						if (galeria.itens.length === 0) { $m.modal('hide'); return; }
						if (galeria.idx >= galeria.itens.length) galeria.idx = galeria.itens.length - 1;
						mostrarGaleria();
					}
				});
			});
		}

		function passoGaleria(delta) {
			if (galeria.itens.length === 0) return;
			galeria.idx = (galeria.idx + delta + galeria.itens.length) % galeria.itens.length;
			mostrarGaleria();
		}

		function mostrarGaleria() {
			var it = galeria.itens[galeria.idx];
			if (!it) return;
			var $m = $('#c2f-gallery-modal');
			$m.find('.c2f-gallery-img').attr('src', it.url).attr('alt', it.nome);
			$m.find('.c2f-gal-open').attr('href', it.url);
			$m.find('.c2f-gallery-name').text(it.nome);
			$m.find('.c2f-gallery-counter').text('(' + (galeria.idx + 1) + ' / ' + galeria.itens.length + ')');
			var strip = '';
			for (var i = 0; i < galeria.itens.length; i++) {
				strip += '<img data-idx="' + i + '" src="' + adminArquivosEsc(galeria.itens[i].thumb) + '"' + (i === galeria.idx ? ' class="active"' : '') + '>';
			}
			$m.find('.c2f-gallery-strip').html(strip);
			var ativo = $m.find('.c2f-gallery-strip img.active').get(0);
			if (ativo && ativo.scrollIntoView) ativo.scrollIntoView({ inline: 'center', block: 'nearest' });
		}

		function abrirGaleria($item) {
			garantirModalGaleria();
			galeria.itens = coletarImagens();
			var alvo = $item.attr('data-caminho');
			galeria.idx = 0;
			for (var i = 0; i < galeria.itens.length; i++) {
				if (galeria.itens[i].caminho === alvo) { galeria.idx = i; break; }
			}
			if (galeria.itens.length === 0) return;
			mostrarGaleria();
			$('#c2f-gallery-modal').modal({
				onHidden: function () {
					$(document).off('keydown.c2fgal');
					// Reflete rename/exclusão feitos dentro da galeria ao fechar.
					if (galeria.dirty) { galeria.dirty = false; carregar(false); }
				}
			}).modal('show');
			$(document).off('keydown.c2fgal').on('keydown.c2fgal', function (e) {
				if (e.key === 'ArrowLeft') passoGaleria(-1);
				else if (e.key === 'ArrowRight') passoGaleria(1);
				else if (e.key === 'Escape') $('#c2f-gallery-modal').modal('hide');
			});
		}

		$lista.on('click', '.c2f-copy', function (e) {
			e.stopPropagation();
			var url = $(this).closest('.c2f-item').attr('data-url');
			navigator.clipboard.writeText(url);
			$(this).popup({ content: t('copied'), on: 'manual' }).popup('show');
			var self = this;
			setTimeout(function () { try { $(self).popup('hide'); } catch (err) {} }, 900);
		});

		$lista.on('click', '.c2f-select', function (e) {
			e.stopPropagation();
			var $item = $(this).closest('.c2f-item');
			var dados = {
				id: $item.attr('data-caminho'),
				caminho: $item.attr('data-caminho'),
				imgSrc: $item.find('.c2f-img').attr('src'),
				nome: $item.attr('data-nome'),
				data: $item.find('.c2f-col-date').text(),
				tipo: $item.attr('data-mime')
			};
			var messageParent = { moduloId: gestor.moduloId, moduloOpcao: gestor.moduloOpcao, data: JSON.stringify(dados) };
			window.parent.postMessage(JSON.stringify(messageParent), '*');
		});

		$lista.on('click', '.c2f-del', function (e) {
			e.stopPropagation();
			var $item = $(this).closest('.c2f-item');
			excluirItens([{ caminho: $item.attr('data-caminho'), tipo: $item.attr('data-tipo') }]);
		});

		$lista.on('click', '.c2f-rename', function (e) {
			e.stopPropagation();
			var $item = $(this).closest('.c2f-item');
			var atual = $item.attr('data-nome');
			var novo = window.prompt(t('folder-rename'), atual);
			if (novo === null) return;
			novo = novo.trim();
			if (!novo || novo === atual) return;
			$.ajax({
				type: 'POST', url: gestor.raiz + gestor.moduloId + '/', dataType: 'json',
				data: { opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao: 'renomear', caminho: $item.attr('data-caminho'), nome: novo },
				success: function (resp) {
					if (resp.status === 'Ok') carregar(false);
					else alertaSimples(resp.status);
				}
			});
		});

		// ===== Seleção em lote

		$lista.on('change', '.c2f-sel', function () {
			var $item = $(this).closest('.c2f-item');
			var cam = $item.attr('data-caminho');
			if (this.checked) estado.selecionados[cam] = { caminho: cam, tipo: $item.attr('data-tipo') };
			else delete estado.selecionados[cam];
			atualizarBarraSelecao();
		});

		$('#c2f-select-all').on('change', function () {
			var marcar = this.checked;
			$lista.find('.c2f-sel').each(function () {
				$(this).prop('checked', marcar).trigger('change');
			});
		});

		function atualizarBarraSelecao() {
			var n = Object.keys(estado.selecionados).length;
			$('#c2f-selection-count').text(n);
			$('#c2f-selection-bar').toggleClass('hidden', n === 0);
		}

		$('#c2f-delete-selected').on('click', function () {
			var itens = [];
			for (var k in estado.selecionados) if (estado.selecionados.hasOwnProperty(k)) itens.push(estado.selecionados[k]);
			if (itens.length === 0) return;
			excluirItens(itens);
		});

		// ===== Exclusão (individual/lote), com tratamento de pasta não-vazia

		function excluirItens(itens, recursivo) {
			$('.ui.modal.confirm').modal({
				onApprove: function () {
					$.ajax({
						type: 'POST', url: gestor.raiz + gestor.moduloId + '/', dataType: 'json',
						data: { opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao: 'excluir', itens: JSON.stringify(itens), recursivo: recursivo ? 'true' : 'false' },
						success: function (resp) {
							var naoVazias = [];
							if (resp && resp.resultados) {
								for (var k = 0; k < resp.resultados.length; k++) {
									if (resp.resultados[k].status === 'NotEmpty') naoVazias.push(resp.resultados[k].caminho);
								}
							}
							estado.selecionados = {};
							if (naoVazias.length > 0 && !recursivo) {
								if (window.confirm(t('folder-not-empty-confirm'))) {
									var reenviar = [];
									for (var j = 0; j < naoVazias.length; j++) reenviar.push({ caminho: naoVazias[j], tipo: 'pasta' });
									excluirItens(reenviar, true);
									return;
								}
							}
							carregar(false);
						}
					});
				}
			}).modal('show');
		}

		// ===== Nova pasta

		$('#c2f-new-folder').on('click', function () {
			var nome = window.prompt(t('folder-name-placeholder'), '');
			if (nome === null) return;
			nome = nome.trim();
			if (!nome) return;
			$.ajax({
				type: 'POST', url: gestor.raiz + gestor.moduloId + '/', dataType: 'json',
				data: { opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao: 'pasta-criar', dir: estado.dir, nome: nome },
				success: function (resp) {
					if (resp.status === 'Ok') carregar(false);
					else alertaSimples(resp.status);
				}
			});
		});

		function alertaSimples(msg) {
			if (typeof alerta === 'function') alerta({ msg: msg });
			else console.log('admin-arquivos: ' + msg);
		}

		// ===== Boot da listagem
		aplicarView();
		carregar(false);
	}

	// =====================================================================
	// TELA DE UPLOAD (drag & drop, tamanho de preview, pasta de destino)
	// =====================================================================

	var fileUploadSelector = '#fileupload';
	if ($(fileUploadSelector).length > 0) {

		// Pasta de destino: vem da URL (botão "Adicionar" da listagem) ou, na sua
		// ausência, da última pasta acessada em cache (localStorage). Sem navegação aqui.
		var upDir = cfg.dirExplicito ? (cfg.dirInicial || '') : (localStorage.getItem('adminArquivosDir') || '');
		var upState = { dir: upDir, total: 0, subTotal: 0, files: [] };

		$('#c2f-dest-path').text(upState.dir === '' ? '/' : '/' + upState.dir);

		// Seletor de tamanho de pré-visualização
		$('.c2f-preview-size').on('click', function () {
			var size = $(this).attr('data-size');
			$('.c2f-preview-size').removeClass('active');
			$(this).addClass('active');
			$('#files-cont').removeClass('preview-s preview-m preview-l preview-xl').addClass('preview-' + size);
		});
		$('#files-cont').addClass('preview-m');

		$(fileUploadSelector).fileupload({
			url: gestor.raiz + gestor.moduloId + '/',
			dataType: 'json',
			sequentialUploads: true,
			dropZone: $('#c2f-dropzone').length ? $('#c2f-dropzone') : $(document),
			formData: function () {
				var data = [
					{ name: 'opcao', value: 'upload' },
					{ name: 'ajax', value: 'sim' },
					{ name: 'ajaxOpcao', value: 'uploadFile' },
					{ name: 'dir', value: upState.dir }
				];
				var categorias = $('#categories').length ? $('#categories').dropdown('get value') : '';
				if (categorias && categorias.length > 0) data.push({ name: 'categorias', value: categorias });
				return data;
			},
			change: function () { upState.subTotal = 0; },
			drop: function () { upState.subTotal = 0; $('#c2f-dropzone').removeClass('c2f-drag'); },
			add: function (e, data) {
				var file = data.files[0];
				var originalFile = data.originalFiles[upState.subTotal];

				// Bloqueio client-side de extensões perigosas (a autoridade é o servidor).
				if (adminArquivosExtensaoPerigosa(file.name)) {
					if (typeof alerta === 'function') alerta({ msg: t('upload-error-extension') });
					else console.log(t('upload-error-extension'));
					upState.subTotal++;
					return;
				}

				var tipo = adminArquivosTipoPorNome(file.name);
				var imgSrc = iconePadrao[tipo] || iconePadrao.file;

				var limitText = 40, limitTextEnd = 10, textSep = '...';
				var fileName = file.name;
				if (fileName.length > limitText) fileName = fileName.substr(0, limitText - limitTextEnd) + textSep + fileName.substr(-limitTextEnd, limitTextEnd);

				var fileCel = gestor.arquivosCel;
				fileCel = fileCel.replace('#file-img-id#', 'file-' + upState.total);
				fileCel = fileCel.replace('#file-img-src#', imgSrc);
				fileCel = fileCel.replace('#file-name#', adminArquivosEsc(fileName));
				fileCel = fileCel.replace('#file-last-modified#', (new Date(file.lastModified)).toLocaleString());
				fileCel = fileCel.replace('#file-size#', adminArquivosFormatBytes(file.size));
				fileCel = fileCel.replace('#file-type#', adminArquivosEsc(file.type || tipo));

				var fileObj = $(fileCel).prependTo('#files-cont');

				fileObj.find('.fileSend').click(function () {
					fileObj.find('.fileProgress').removeClass('hidden');
					fileObj.find('.fileWait').remove();
					$('.fileProgressAll').removeClass('hidden');
					$('.fileWaitAll').addClass('hidden');
					data.submit();
					$(this).remove();
				});

				fileObj.find('.fileCancel').click(function () {
					if (!data.done) fileObj.remove();
					data.cancelar = true;
					data.abort();
				});

				data.context = fileObj;

				$('.fileButtonsAll').removeClass('hidden');
				$('.fileWaitAll').removeClass('hidden');
				$('.filesHeader').removeClass('hidden');
				$('.fileProgressAll').addClass('hidden');

				// Pré-visualização real para imagens
				if (tipo === 'image' && originalFile) {
					originalFile.id = '#file-' + upState.total;
					var reader = new FileReader();
					reader.onload = (function (fb) { return function (ev) { $(fb.id).attr('src', ev.target.result); }; })(originalFile);
					reader.readAsDataURL(originalFile);
				}

				data.id = upState.total;
				upState.files.push({ id: '#file-' + upState.total, fileObj: fileObj, size: file.size });
				upState.total++;
				upState.subTotal++;
				progressAll(0);
			},
			fail: function (e, data) {
				var result = data.jqXHR;
				if (result && result.status === 401) { window.open(gestor.raiz + (result.redirect ? result.redirect : 'signin/'), '_self'); return; }
				console.log('ERROR - jQuery File Upload - uploadFile'); console.log(data);
			},
			progress: function (e, data) {
				var progress = parseInt((data.loaded / data.total) * 100, 10);
				if (progress >= 100) {
					data.context.find('.fileProgress').find('.progress').progress({ percent: 100, text: { active: gestor.arquivosProcessando } });
				} else {
					data.context.find('.fileProgress').find('.progress').progress({ percent: progress });
				}
				progressAll(data.loaded);
			},
			done: function (e, data) {
				var result = data.result;
				if (result && result.warning_msg) console.log('WARNING! uploadFile: ' + result.warning_msg);

				if (result && result.error) {
					data.context.find('.fileError').find('.fileErrorBody').html(result.error);
					data.context.find('.fileError').removeClass('hidden');
					data.context.find('.fileProgress').find('.progress').progress('set error', gestor.arquivosErro);
				} else {
					data.context.find('.fileProgress').find('.progress').progress({ percent: 100, text: { active: gestor.arquivosConcluido, success: gestor.arquivosConcluido } });
					data.context.find('.fileDone').removeClass('hidden');

					if (data.context.find('.fileDone').find('.fileCopyClipboard').length > 0) {
						var urlFile = result.url;
						data.context.find('.fileDone').find('.fileCopyClipboard').click(function () { navigator.clipboard.writeText(urlFile); });
					}

					if (data.context.find('.fileDone').find('.fileSelect').length > 0) {
						var payload = { id: result.caminho, caminho: result.caminho, imgSrc: result.imgSrc, nome: result.nome, data: result.data, tipo: result.mime };
						data.context.find('.fileDone').find('.fileSelect').click(function () {
							var messageParent = { moduloId: gestor.moduloId, moduloOpcao: gestor.moduloOpcao, data: JSON.stringify(payload) };
							window.parent.postMessage(JSON.stringify(messageParent), '*');
						});
					}
				}

				data.context.find('.fileCancel').remove();

				var doneAll = true, id = '#file-' + data.id;
				for (var i = 0; i < upState.files.length; i++) {
					if (id === upState.files[i].id) { upState.files[i].done = true; if (result && result.error) upState.files[i].error = true; }
					else if (!upState.files[i].done && !upState.files[i].cancelar) doneAll = false;
				}
				if (doneAll) { $('.fileButtonsAll').addClass('hidden'); $('.fileProgressAll').addClass('hidden'); }
				progressAll(data.files[0].size, true);
			}
		});

		// Realce visual do dropzone
		$(document).on('dragover', '#c2f-dropzone', function () { $(this).addClass('c2f-drag'); });
		$(document).on('dragleave drop', '#c2f-dropzone', function () { $(this).removeClass('c2f-drag'); });

		function progressAll(fileLoaded, done) {
			var total = 0, loaded = 0;
			for (var i = 0; i < upState.files.length; i++) {
				if (!upState.files[i].error && !upState.files[i].cancelar) {
					total += parseInt(upState.files[i].size, 10);
					if (upState.files[i].done) loaded += parseInt(upState.files[i].size, 10);
				}
			}
			if (!done) loaded += fileLoaded;
			var pct = total > 0 ? parseInt((loaded / total) * 100, 10) : 0;
			if (pct >= 100) {
				pct = 100;
				$('.fileProgressAll').find('.progress').progress({ percent: pct, text: { active: gestor.arquivosConcluido, success: gestor.arquivosConcluido } });
			} else {
				$('.fileProgressAll').find('.progress').progress({ percent: pct });
			}
		}

		$('.fileSendAll').click(function () { $('.fileSend').trigger('click'); });
		$('.fileCancelAll').click(function () {
			$('.fileCancel').trigger('click');
			$('.fileButtonsAll').addClass('hidden');
			$('.fileProgressAll').addClass('hidden');
		});

		$('.ui.dropdown').dropdown();
	}

});
