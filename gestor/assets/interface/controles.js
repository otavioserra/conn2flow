/**
 * Controles do painel (req-219 / BATCH-227) — `window.c2fControles`.
 *
 * Sem framework: vale igual nas páginas Fomantic, Tailwind e híbridas. Textos de `gestor.controlesTextos`
 * (variáveis `controles-*` do core), com o padrão em inglês só como último recurso.
 *
 * PADRÃO DOS CONTROLES
 * --------------------
 * Todo controle é uma classe que estende `c2fControles.Controle` e é registrada por tipo:
 *
 *   class MeuControle extends c2fControles.Controle {
 *       montar() { ... }              // chamado pelo construtor; monta o DOM e liga eventos
 *       valor() { ... }               // valor atual
 *       definir(valor, silencioso) {} // muda o valor; sem `silencioso`, emite 'mudou'
 *       habilitar(sim) { ... }
 *       destruir() { ... }            // devolve o DOM original
 *   }
 *   c2fControles.registrar('meu-tipo', MeuControle);
 *
 * Eventos: `controle.on('mudou', fn)` e, no elemento, `c2f:mudou` (CustomEvent, `detail` = {valor, controle}).
 * HTML: `<elemento data-c2f-controle="tipo">` é montado sozinho por `c2fControles.iniciar(raiz)` (também
 * no carregamento da página). `c2fControles.criar(tipo, elemento, opcoes)` cria à mão; `c2fControles.de(elemento)`
 * devolve a instância.
 *
 * Tipos: `select` (busca, múltiplo, opções por AJAX), `chave` (liga/desliga), `abas`.
 * Serviços: `dialogo.alerta|confirmar|perguntar` (Promise), `aviso`, `carregando`.
 *
 * Ponte: quando o Fomantic não está na página e o jQuery está, `$.fn.dropdown`, `checkbox`, `tab`, `modal`,
 * `dimmer` e `popup` passam a usar estes controles, com a parte da API do Fomantic que o core usa. Com o
 * Fomantic carregado, a ponte não entra e nada muda.
 */
(function (global) {
    'use strict';

    if (global.c2fControles) return;

    var PADRAO = { ok: 'OK', cancelar: 'Cancel', buscar: 'Search…', semResultado: 'No results', carregando: 'Loading…',
        fechar: 'Close', selecione: 'Select…', confirmar: 'Confirm', remover: 'Remove', atencao: 'Attention' };

    function texto(chave) {
        var t = (global.gestor && global.gestor.controlesTextos) || {};
        return t[chave] || PADRAO[chave] || chave;
    }

    function el(tag, attrs, filhos) {
        var n = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) {
            var v = attrs[k];
            if (v === null || v === undefined || v === false) return;
            if (k === 'text') n.textContent = v;
            else if (k === 'class') n.className = v;
            else if (k.indexOf('on') === 0) n.addEventListener(k.slice(2), v);
            else n.setAttribute(k, v === true ? '' : v);
        });
        (filhos || []).forEach(function (f) { if (f) n.appendChild(typeof f === 'string' ? document.createTextNode(f) : f); });
        return n;
    }

    function opcaoNova(rotulo, valor) {
        var o = document.createElement('option');
        o.value = String(valor);
        o.textContent = String(rotulo);
        return o;
    }

    function disparar(alvo, tipo, detalhe) {
        var evento;
        try {
            evento = detalhe !== undefined ? new CustomEvent(tipo, { bubbles: true, detail: detalhe }) : new Event(tipo, { bubbles: true });
        } catch (e) {
            evento = document.createEvent('CustomEvent');
            evento.initCustomEvent(tipo, true, true, detalhe);
        }
        alvo.dispatchEvent(evento);
    }

    function semAcento(t) {
        t = String(t || '').toLowerCase();
        return t.normalize ? t.normalize('NFD').replace(/[̀-ͯ]/g, '') : t;
    }

    function idNovo(prefixo) { return prefixo + Math.random().toString(36).slice(2); }

    // =========================================================================== base e registro

    var instancias = typeof WeakMap === 'function' ? new WeakMap() : null;
    var tipos = {};

    /** Base de todo controle: elemento, opções, eventos e registro da instância. */
    function Controle(elemento, opcoes) {
        this.elemento = elemento;
        this.opcoes = opcoes || {};
        this.ouvintes = {};
        if (instancias) instancias.set(elemento, this);
        this.montar();
    }
    Controle.prototype.montar = function () {};
    Controle.prototype.valor = function () { return null; };
    Controle.prototype.definir = function () { return this; };
    Controle.prototype.habilitar = function () { return this; };
    Controle.prototype.destruir = function () { if (instancias) instancias.delete(this.elemento); };
    Controle.prototype.on = function (evento, fn) { (this.ouvintes[evento] = this.ouvintes[evento] || []).push(fn); return this; };
    Controle.prototype.emitir = function (evento) {
        var args = Array.prototype.slice.call(arguments, 1);
        var self = this;
        (this.ouvintes[evento] || []).forEach(function (fn) { try { fn.apply(self, args); } catch (e) { if (global.console) console.error(e); } });
        disparar(this.elemento, 'c2f:' + evento, { valor: args[0], controle: this });
        return this;
    };

    /** Herança sem `class` (funciona em qualquer navegador do painel). */
    function estender(Pai, metodos) {
        function Filho(elemento, opcoes) { Pai.call(this, elemento, opcoes); }
        Filho.prototype = Object.create(Pai.prototype);
        Filho.prototype.constructor = Filho;
        Object.keys(metodos).forEach(function (k) { Filho.prototype[k] = metodos[k]; });
        return Filho;
    }

    function registrar(tipo, Classe) { tipos[tipo] = Classe; return Classe; }
    function de(elemento) { return instancias && elemento ? instancias.get(elemento) || null : null; }
    function criar(tipo, elemento, opcoes) {
        if (!elemento || !tipos[tipo]) return null;
        return de(elemento) || new tipos[tipo](elemento, opcoes);
    }

    // =========================================================================== diálogos

    var focoAnterior = [];

    // Mensagem com ênfase vinda das variáveis do sistema (`<b>NÃO</b>`): só as tags de ênfase desta lista
    // viram elemento, e sem atributo nenhum; o resto da marcação entra como texto.
    var TAGS_FORMATADAS = { B: 1, STRONG: 1, I: 1, EM: 1, U: 1, BR: 1, CODE: 1 };
    function formatado(destino, mensagem) {
        var origem = new DOMParser().parseFromString('<body>' + String(mensagem === undefined || mensagem === null ? '' : mensagem) + '</body>', 'text/html').body;
        (function copiar(de, para) {
            Array.prototype.forEach.call(de.childNodes, function (no) {
                if (no.nodeType === 3) { para.appendChild(document.createTextNode(no.nodeValue)); return; }
                if (no.nodeType !== 1) return;
                if (/^(SCRIPT|STYLE|IFRAME|OBJECT|EMBED|TEMPLATE)$/.test(no.tagName)) return;
                if (!TAGS_FORMATADAS[no.tagName]) { copiar(no, para); return; }
                var limpo = document.createElement(no.tagName.toLowerCase());
                para.appendChild(limpo);
                copiar(no, limpo);
            });
        })(origem, destino);
        return destino;
    }

    function dialogo(tipo, mensagem, valor, opcoes) {
        opcoes = opcoes || {};
        return new Promise(function (resolver) {
            var entrada = tipo === 'perguntar' ? el('input', { type: 'text', 'class': 'c2fc-entrada', value: valor === undefined || valor === null ? '' : String(valor) }) : null;
            var corpo = el('div', { 'class': 'c2fc-dialogo-corpo' });
            var paragrafo = el('p');
            if (opcoes.html) paragrafo.innerHTML = String(mensagem);
            else if (opcoes.formatado) formatado(paragrafo, mensagem);
            else paragrafo.textContent = String(mensagem === undefined ? '' : mensagem);
            corpo.appendChild(paragrafo);
            if (entrada) corpo.appendChild(entrada);
            var titulo = opcoes.titulo || (tipo === 'alerta' ? texto('atencao') : texto('confirmar'));
            var idTitulo = idNovo('c2fc-t-');
            var caixa = el('div', { 'class': 'c2fc-dialogo', role: tipo === 'alerta' ? 'alertdialog' : 'dialog', 'aria-modal': 'true', 'aria-labelledby': idTitulo, tabindex: '-1' }, [
                el('h2', { 'class': 'c2fc-dialogo-titulo', id: idTitulo, text: titulo }), corpo]);
            var acoes = el('div', { 'class': 'c2fc-dialogo-acoes' });
            var cancelar = tipo !== 'alerta' ? el('button', { type: 'button', 'class': 'c2fc-botao', 'data-c2fc-cancelar': true, text: opcoes.cancelar || texto('cancelar') }) : null;
            var ok = el('button', { type: 'button', 'class': 'c2fc-botao ' + (opcoes.perigo ? 'c2fc-botao-perigo' : 'c2fc-botao-primario'), 'data-c2fc-ok': true, text: opcoes.ok || texto('ok') });
            if (cancelar) acoes.appendChild(cancelar);
            acoes.appendChild(ok);
            caixa.appendChild(acoes);
            var fundo = el('div', { 'class': 'c2fc-overlay', 'data-c2fc-dialogo': tipo }, [caixa]);
            focoAnterior.push(document.activeElement);

            function fechar(resultado) {
                document.removeEventListener('keydown', teclas, true);
                if (fundo.parentNode) fundo.parentNode.removeChild(fundo);
                var anterior = focoAnterior.pop();
                if (anterior && anterior.focus) { try { anterior.focus(); } catch (e) { /* elemento saiu da página */ } }
                resolver(resultado);
            }
            function aprovar() { fechar(tipo === 'perguntar' ? entrada.value : (tipo === 'confirmar' ? true : undefined)); }
            function negar() { fechar(tipo === 'perguntar' ? null : (tipo === 'confirmar' ? false : undefined)); }
            function teclas(ev) {
                var abertos = document.querySelectorAll('.c2fc-overlay[data-c2fc-dialogo]');
                if (abertos[abertos.length - 1] !== fundo) return;
                if (ev.key === 'Escape') { ev.preventDefault(); ev.stopPropagation(); negar(); }
                else if (ev.key === 'Enter' && (ev.target === entrada || ev.target === ok || ev.target === caixa)) { ev.preventDefault(); aprovar(); }
                else if (ev.key === 'Tab') {
                    // Foco preso no diálogo.
                    var focaveis = [entrada, cancelar, ok].filter(Boolean);
                    var i = focaveis.indexOf(document.activeElement);
                    ev.preventDefault();
                    focaveis[(i + (ev.shiftKey ? -1 : 1) + focaveis.length) % focaveis.length].focus();
                }
            }
            ok.addEventListener('click', aprovar);
            if (cancelar) cancelar.addEventListener('click', negar);
            fundo.addEventListener('mousedown', function (ev) { if (ev.target === fundo && tipo !== 'alerta') negar(); });
            document.addEventListener('keydown', teclas, true);
            document.body.appendChild(fundo);
            (entrada || ok).focus();
            if (entrada) entrada.select();
        });
    }

    var dialogos = {
        alerta: function (mensagem, opcoes) { return dialogo('alerta', mensagem, null, opcoes); },
        confirmar: function (mensagem, opcoes) { return dialogo('confirmar', mensagem, null, opcoes); },
        perguntar: function (mensagem, valor, opcoes) { return dialogo('perguntar', mensagem, valor, opcoes); }
    };

    // =========================================================================== avisos e carregando

    function aviso(mensagem, tipo, ms) {
        var area = document.querySelector('.c2fc-avisos');
        if (!area) { area = el('div', { 'class': 'c2fc-avisos', 'aria-live': 'polite' }); document.body.appendChild(area); }
        var item = el('div', { 'class': 'c2fc-aviso' + (tipo && tipo !== 'info' ? ' c2fc-aviso-' + tipo : ''), role: tipo === 'erro' ? 'alert' : 'status' },
            [el('span', { text: String(mensagem) })]);
        var fechar = function () { if (item.parentNode) item.parentNode.removeChild(item); };
        item.appendChild(el('button', { type: 'button', 'aria-label': texto('fechar'), text: '×', onclick: fechar }));
        area.appendChild(item);
        if (ms !== 0) setTimeout(fechar, ms || (tipo === 'erro' ? 8000 : 4000));
        return fechar;
    }

    var carregandoPagina = 0;

    function carregando(alvo, ligar) {
        if (!alvo) {
            carregandoPagina = Math.max(0, carregandoPagina + (ligar === false ? -1 : 1));
            var existente = document.querySelector('.c2fc-carregando.c2fc-pagina');
            if (carregandoPagina > 0 && !existente) document.body.appendChild(el('div', { 'class': 'c2fc-carregando c2fc-pagina', role: 'status', 'aria-label': texto('carregando') }, [el('div', { 'class': 'c2fc-giro' })]));
            if (carregandoPagina === 0 && existente) existente.parentNode.removeChild(existente);
            return;
        }
        var camada = null;
        Array.prototype.forEach.call(alvo.children, function (c) { if (c.classList.contains('c2fc-carregando')) camada = c; });
        if (ligar === false) { if (camada) camada.parentNode.removeChild(camada); return; }
        if (camada) return;
        alvo.classList.add('c2fc-carregando-alvo');
        alvo.appendChild(el('div', { 'class': 'c2fc-carregando', role: 'status', 'aria-label': texto('carregando') }, [el('div', { 'class': 'c2fc-giro' })]));
    }

    // =========================================================================== select

    /**
     * Select acessível sobre um <select> nativo, que continua sendo a fonte de verdade (o formulário envia
     * o nativo e o evento `change` é disparado nele). Opções: busca (bool), ajax ({url, opcao, parametros,
     * minimo}), aoMudar (fn). Atributos equivalentes: data-c2f-busca, data-c2f-ajax-opcao, data-c2f-ajax-url,
     * data-c2f-ajax-minimo, data-c2f-placeholder.
     */
    var Select = estender(Controle, {
        montar: function () {
            var self = this;
            var nativo = this.nativo = this.elemento;
            var op = this.opcoes;
            this.multiplo = nativo.multiple;
            this.ajax = op.ajax || (nativo.getAttribute('data-c2f-ajax-opcao') ? { url: nativo.getAttribute('data-c2f-ajax-url') || global.location.pathname,
                opcao: nativo.getAttribute('data-c2f-ajax-opcao'), minimo: parseInt(nativo.getAttribute('data-c2f-ajax-minimo') || '2', 10) } : null);
            var comBusca = op.busca !== undefined ? !!op.busca : (nativo.hasAttribute('data-c2f-busca') || !!this.ajax || nativo.options.length > 8);
            var idLista = idNovo('c2fc-l-');
            this.raiz = el('div', { 'class': 'c2fc-select' + (nativo.disabled ? ' c2fc-select-desabilitado' : '') });
            this.raiz.c2fcViva = true;
            this.gatilho = el('div', { 'class': 'c2fc-select-gatilho', role: 'combobox', tabindex: nativo.disabled ? '-1' : '0', 'aria-haspopup': 'listbox',
                'aria-expanded': 'false', 'aria-controls': idLista, 'aria-label': nativo.getAttribute('aria-label') || nativo.getAttribute('name') || '' });
            this.painel = el('div', { 'class': 'c2fc-select-painel c2fc-oculto' });
            this.busca = comBusca ? el('input', { type: 'search', 'class': 'c2fc-select-busca', placeholder: texto('buscar'), 'aria-label': texto('buscar') }) : null;
            this.lista = el('ul', { 'class': 'c2fc-select-lista', role: 'listbox', id: idLista, 'aria-multiselectable': this.multiplo ? 'true' : null });
            if (this.busca) this.painel.appendChild(this.busca);
            this.painel.appendChild(this.lista);
            nativo.parentNode.insertBefore(this.raiz, nativo);
            this.raiz.appendChild(nativo);
            this.raiz.appendChild(this.gatilho);
            this.raiz.appendChild(this.painel);
            nativo.classList.add('c2fc-nativo');
            nativo.setAttribute('tabindex', '-1');
            nativo.setAttribute('aria-hidden', 'true');
            this.ativa = -1;
            this.visiveis = [];
            if (op.aoMudar) this.on('mudou', op.aoMudar);

            this.gatilho.addEventListener('click', function () { if (self.painel.classList.contains('c2fc-oculto')) self.abrir(); else self.fechar(true); });
            var navegar = function (ev) { self._teclado(ev); };
            this.gatilho.addEventListener('keydown', navegar);
            if (this.busca) {
                this.busca.addEventListener('keydown', navegar);
                this.busca.addEventListener('input', function () { if (self.ajax) self._buscarRemoto(self.busca.value); else self._desenharLista(self.busca.value); });
            }
            this._foraDeFoco = function (ev) { if (!self.raiz.contains(ev.target)) self.fechar(false); };
            document.addEventListener('mousedown', this._foraDeFoco);
            // Quem muda o nativo por fora (script antigo) vê o controle acompanhar.
            nativo.addEventListener('change', function () { self._desenharGatilho(); });
            // REQ-243: script que escreve `select.value = x` (ou `selectedIndex`, ou troca as opções) não
            // dispara `change`; sem acompanhar, o gatilho continuava mostrando a opção antiga.
            ['value', 'selectedIndex'].forEach(function (prop) {
                var original = global.HTMLSelectElement && Object.getOwnPropertyDescriptor(global.HTMLSelectElement.prototype, prop);
                if (!original || !original.set || !original.get) return;
                Object.defineProperty(nativo, prop, {
                    configurable: true,
                    enumerable: original.enumerable,
                    get: function () { return original.get.call(this); },
                    set: function (v) { original.set.call(this, v); self._desenharGatilho(); }
                });
            });
            if (global.MutationObserver) {
                this._observador = new global.MutationObserver(function () { self._desenharGatilho(); });
                this._observador.observe(nativo, { childList: true, subtree: true });
            }
            this._desenharGatilho();
        },

        _selecionados: function () { return Array.prototype.filter.call(this.nativo.options, function (o) { return o.selected && o.value !== ''; }); },

        _desenharGatilho: function () {
            var self = this;
            var g = this.gatilho;
            while (g.firstChild) g.removeChild(g.firstChild);
            var marcados = this._selecionados();
            if (!marcados.length) {
                var vazio = Array.prototype.filter.call(this.nativo.options, function (o) { return o.value === ''; })[0];
                g.appendChild(el('span', { 'class': 'c2fc-select-vazio', text: (vazio && vazio.textContent) || this.nativo.getAttribute('data-c2f-placeholder') || texto('selecione') }));
                return;
            }
            if (!this.multiplo) { g.appendChild(el('span', { text: marcados[0].textContent })); return; }
            marcados.forEach(function (o) {
                g.appendChild(el('span', { 'class': 'c2fc-ficha' }, [o.textContent, el('button', { type: 'button', 'aria-label': texto('remover') + ' ' + o.textContent, text: '×',
                    onclick: function (ev) { ev.stopPropagation(); o.selected = false; self._mudou(); } })]));
            });
        },

        _desenharLista: function (filtro) {
            var self = this;
            var lista = this.lista;
            while (lista.firstChild) lista.removeChild(lista.firstChild);
            this.visiveis = [];
            var termo = semAcento(filtro);
            Array.prototype.forEach.call(this.nativo.children, function (filho) {
                var grupo = filho.tagName === 'OPTGROUP' ? filho : null;
                var achadas = (grupo ? Array.prototype.slice.call(grupo.children) : [filho]).filter(function (o) {
                    return !termo || semAcento(o.textContent).indexOf(termo) >= 0;
                });
                if (!achadas.length) return;
                if (grupo) lista.appendChild(el('li', { 'class': 'c2fc-select-grupo', role: 'presentation', text: grupo.label }));
                achadas.forEach(function (o) {
                    var item = el('li', { 'class': 'c2fc-select-opcao', role: 'option', id: idNovo('c2fc-o-'), 'aria-selected': o.selected && o.value !== '' ? 'true' : 'false',
                        'aria-disabled': o.disabled ? 'true' : null, 'data-valor': o.value, text: o.textContent });
                    // A escolha fecha o painel já no mousedown; sem isolar, o mouseup e o click do mesmo gesto
                    // caem no que estava atrás do painel (um botão, um link) e o acionam.
                    item.addEventListener('mousedown', function (ev) {
                        ev.preventDefault(); ev.stopPropagation();
                        if (ev.isTrusted && !self.multiplo) self._engolirClique();
                        self._escolher(o);
                    });
                    item.addEventListener('click', function (ev) { ev.preventDefault(); ev.stopPropagation(); });
                    lista.appendChild(item);
                    self.visiveis.push({ opcao: o, item: item });
                });
            });
            if (!this.visiveis.length) {
                var curto = this.ajax && String(filtro || '').length < (this.ajax.minimo || 0);
                lista.appendChild(el('li', { 'class': 'c2fc-select-nada', role: 'presentation', text: curto ? texto('buscar') : texto('semResultado') }));
            }
            var marcada = -1;
            this.visiveis.forEach(function (v, i) { if (marcada < 0 && v.opcao.selected) marcada = i; });
            this.ativa = Math.max(0, marcada);
            this._marcarAtiva();
        },

        _marcarAtiva: function () {
            var self = this;
            this.visiveis.forEach(function (v, i) { v.item.classList.toggle('c2fc-ativa', i === self.ativa); });
            var atual = this.visiveis[this.ativa];
            if (!atual) return;
            this.gatilho.setAttribute('aria-activedescendant', atual.item.id);
            var item = atual.item;
            if (item.offsetTop < this.lista.scrollTop) this.lista.scrollTop = item.offsetTop;
            else if (item.offsetTop + item.offsetHeight > this.lista.scrollTop + this.lista.clientHeight) this.lista.scrollTop = item.offsetTop + item.offsetHeight - this.lista.clientHeight;
        },

        _teclado: function (ev) {
            var aberto = !this.painel.classList.contains('c2fc-oculto');
            if (ev.key === 'ArrowDown') { ev.preventDefault(); if (!aberto) this.abrir(); else { this.ativa = Math.min(this.visiveis.length - 1, this.ativa + 1); this._marcarAtiva(); } }
            else if (ev.key === 'ArrowUp') { ev.preventDefault(); this.ativa = Math.max(0, this.ativa - 1); this._marcarAtiva(); }
            else if (ev.key === 'Enter' || (ev.key === ' ' && ev.target === this.gatilho)) { ev.preventDefault(); if (!aberto) this.abrir(); else if (this.visiveis[this.ativa]) this._escolher(this.visiveis[this.ativa].opcao); }
            else if (ev.key === 'Escape' && aberto) { ev.preventDefault(); ev.stopPropagation(); this.fechar(true); }
            else if (ev.key === 'Tab' && aberto) this.fechar(false);
        },

        // Segura o restante do gesto do mouse (pointerup, mouseup e click) na fase de captura, até o botão ser
        // solto. O mouseup entra na conta: boa parte dos botões antigos do painel age em `mouseup`.
        _engolirClique: function () {
            var parar = function (ev) { ev.preventDefault(); ev.stopPropagation(); };
            var soltar = function (ev) {
                parar(ev);
                document.removeEventListener('mouseup', soltar, true);
                setTimeout(function () {
                    document.removeEventListener('click', parar, true);
                    document.removeEventListener('pointerup', parar, true);
                }, 0);
            };
            document.addEventListener('click', parar, true);
            document.addEventListener('pointerup', parar, true);
            document.addEventListener('mouseup', soltar, true);
        },

        _escolher: function (o) {
            if (o.disabled) return;
            if (this.multiplo) { o.selected = !o.selected; this._mudou(); this._desenharLista(this.busca ? this.busca.value : ''); if (this.busca) this.busca.focus(); return; }
            var mudouValor = !o.selected;
            Array.prototype.forEach.call(this.nativo.options, function (x) { x.selected = x === o; });
            this.fechar(true);
            if (mudouValor) this._mudou(); else this._desenharGatilho();
        },

        _mudou: function (silencioso) {
            this._desenharGatilho();
            if (silencioso) return;
            disparar(this.nativo, 'change');
            this.emitir('mudou', this.valor(), this.rotulo());
        },

        _buscarRemoto: function (termo) {
            var self = this;
            var ajax = this.ajax;
            clearTimeout(this._temporizador);
            if (termo.length < (ajax.minimo || 0)) { this._desenharLista(termo); return; }
            this._temporizador = setTimeout(function () {
                var corpo = new URLSearchParams({ ajax: 'sim', ajaxOpcao: ajax.opcao, q: termo });
                Object.keys(ajax.parametros || {}).forEach(function (k) { corpo.set(k, ajax.parametros[k]); });
                carregando(self.painel, true);
                fetch(ajax.url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: corpo })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        var itens = (j && j.data && (j.data.items || j.data.results)) || (j && (j.items || j.results)) || [];
                        var mantidos = self._selecionados();
                        Array.prototype.slice.call(self.nativo.options).forEach(function (o) { if (!o.selected && o.value !== '') self.nativo.removeChild(o); });
                        itens.forEach(function (it) {
                            var valor = String(it.value !== undefined ? it.value : it.id);
                            if (mantidos.some(function (m) { return m.value === valor; })) return;
                            self.nativo.appendChild(opcaoNova(it.text !== undefined ? it.text : (it.name || valor), valor));
                        });
                        self._desenharLista('');
                    })
                    .catch(function () { self._desenharLista(termo); })
                    .then(function () { carregando(self.painel, false); });
            }, 250);
        },

        abrir: function () {
            if (this.nativo.disabled || !this.painel.classList.contains('c2fc-oculto')) return this;
            this.painel.classList.remove('c2fc-oculto');
            this.raiz.classList.add('c2fc-select-aberto');
            this.gatilho.setAttribute('aria-expanded', 'true');
            this._posicionar();
            if (this._recortado()) {
                var self = this;
                this._reposicionar = function (ev) { if (!ev || !ev.target || !self.painel.contains(ev.target)) self._posicionar(); };
                global.addEventListener('scroll', this._reposicionar, true);
                global.addEventListener('resize', this._reposicionar);
            }
            if (this.busca) { this.busca.value = ''; this.busca.focus({ preventScroll: true }); }
            this._desenharLista('');
            return this;
        },

        // Algum ancestral recorta o que sai dele (tabela com rolagem, cartão com overflow)? Aí o painel
        // absoluto seria cortado e o container ganharia barra de rolagem.
        _recortado: function () {
            for (var no = this.raiz.parentElement; no && no !== document.body && no !== document.documentElement; no = no.parentElement) {
                var estilo = global.getComputedStyle(no);
                if (/(auto|scroll|hidden|clip)/.test(estilo.overflow + ' ' + estilo.overflowX + ' ' + estilo.overflowY)) return true;
            }
            return false;
        },

        // Abre para cima quando falta espaço embaixo. Dentro de ancestral que recorta, o painel passa a
        // `position: fixed` na medida do gatilho e acompanha a rolagem enquanto estiver aberto.
        _posicionar: function () {
            var caixa = this.raiz.getBoundingClientRect();
            var acima = caixa.bottom + 300 > global.innerHeight && caixa.top > 300;
            var flutua = this._recortado();
            var estilo = this.painel.style;
            this.painel.classList.toggle('c2fc-acima', acima);
            this.painel.classList.toggle('c2fc-flutuante', flutua);
            if (!flutua) { estilo.left = estilo.top = estilo.bottom = estilo.width = ''; return; }
            estilo.left = Math.round(caixa.left) + 'px';
            estilo.width = Math.round(caixa.width) + 'px';
            estilo.top = acima ? 'auto' : Math.round(caixa.bottom + 4) + 'px';
            estilo.bottom = acima ? Math.round(global.innerHeight - caixa.top + 4) + 'px' : 'auto';
        },

        fechar: function (focar) {
            if (this.painel.classList.contains('c2fc-oculto')) return this;
            this.painel.classList.add('c2fc-oculto');
            this.raiz.classList.remove('c2fc-select-aberto');
            this.gatilho.setAttribute('aria-expanded', 'false');
            if (this._reposicionar) {
                global.removeEventListener('scroll', this._reposicionar, true);
                global.removeEventListener('resize', this._reposicionar);
                this._reposicionar = null;
            }
            if (focar === true) this.gatilho.focus();
            return this;
        },

        valor: function () { var v = this._selecionados().map(function (o) { return o.value; }); return this.multiplo ? v : (v[0] || ''); },
        rotulo: function () { var v = this._selecionados().map(function (o) { return o.textContent; }); return this.multiplo ? v : (v[0] || ''); },

        definir: function (valor, silencioso) {
            var lista = Array.isArray(valor) ? valor.map(String)
                : (this.multiplo && typeof valor === 'string' && valor.indexOf(',') >= 0 ? valor.split(',') : [String(valor === null || valor === undefined ? '' : valor)]);
            var antes = this.valor().toString();
            Array.prototype.forEach.call(this.nativo.options, function (o) { o.selected = lista.indexOf(o.value) >= 0; });
            if (!this.multiplo && !this._selecionados().length) {
                var vazio = Array.prototype.filter.call(this.nativo.options, function (o) { return o.value === ''; })[0];
                if (vazio) vazio.selected = true;
            }
            this._mudou(silencioso || antes === this.valor().toString());
            return this;
        },
        limpar: function (silencioso) { return this.definir(this.multiplo ? [] : '', silencioso); },
        atualizar: function () { this._desenharGatilho(); if (!this.painel.classList.contains('c2fc-oculto')) this._desenharLista(this.busca ? this.busca.value : ''); return this; },
        definirOpcoes: function (lista, manterValor) {
            var self = this;
            var atual = this.valor();
            Array.prototype.slice.call(this.nativo.options).forEach(function (o) { if (o.value !== '' || self.multiplo) self.nativo.removeChild(o); });
            (lista || []).forEach(function (it) { self.nativo.appendChild(opcaoNova(it.text !== undefined ? it.text : it.name, it.value)); });
            if (manterValor) this.definir(atual, true); else this._desenharGatilho();
            return this;
        },
        habilitar: function (sim) {
            this.nativo.disabled = !sim;
            this.raiz.classList.toggle('c2fc-select-desabilitado', !sim);
            this.gatilho.setAttribute('tabindex', sim ? '0' : '-1');
            return this;
        },
        aoMudar: function (fn) { return this.on('mudou', fn); },
        destruir: function () {
            this.fechar(false);
            document.removeEventListener('mousedown', this._foraDeFoco);
            if (this._observador) this._observador.disconnect();
            delete this.nativo.value;
            delete this.nativo.selectedIndex;
            this.raiz.parentNode.insertBefore(this.nativo, this.raiz);
            this.raiz.parentNode.removeChild(this.raiz);
            this.nativo.classList.remove('c2fc-nativo');
            this.nativo.removeAttribute('aria-hidden');
            this.nativo.removeAttribute('tabindex');
            Controle.prototype.destruir.call(this);
        }
    });

    // =========================================================================== chave

    /** Chave liga/desliga sobre um checkbox nativo; aceita o input sozinho ou já dentro de `label.c2fc-chave`. */
    var Chave = estender(Controle, {
        montar: function () {
            var self = this;
            var input = this.input = this.elemento.tagName === 'INPUT' ? this.elemento : this.elemento.querySelector('input[type="checkbox"]');
            if (!input.closest('.c2fc-chave')) {
                var rotulo = el('label', { 'class': 'c2fc-chave' });
                input.parentNode.insertBefore(rotulo, input);
                rotulo.appendChild(input);
                rotulo.appendChild(el('span', { 'class': 'c2fc-chave-trilho', 'aria-hidden': 'true' }));
                if (this.opcoes.rotulo) rotulo.appendChild(el('span', { text: this.opcoes.rotulo }));
            }
            if (this.opcoes.aoMudar) this.on('mudou', this.opcoes.aoMudar);
            input.addEventListener('change', function () { self.emitir('mudou', input.checked); });
        },
        valor: function () { return this.input.checked; },
        definir: function (valor, silencioso) {
            var novo = !!valor;
            if (this.input.checked === novo) return this;
            this.input.checked = novo;
            if (!silencioso) { disparar(this.input, 'change'); }
            return this;
        },
        alternar: function () { return this.definir(!this.input.checked); },
        habilitar: function (sim) { this.input.disabled = !sim; return this; }
    });

    // =========================================================================== abas

    /** Abas: botões [data-c2f-aba="x"] e painéis [data-c2f-painel="x"] dentro do elemento. */
    var Abas = estender(Controle, {
        montar: function () {
            var self = this;
            this.botoes = Array.prototype.slice.call(this.elemento.querySelectorAll('[data-c2f-aba]'));
            this.paineis = Array.prototype.slice.call(this.elemento.querySelectorAll('[data-c2f-painel]'));
            this.botoes.forEach(function (b, i) {
                b.setAttribute('role', 'tab');
                b.classList.add('c2fc-aba');
                b.addEventListener('click', function () { self.definir(b.getAttribute('data-c2f-aba')); });
                b.addEventListener('keydown', function (ev) {
                    if (ev.key !== 'ArrowRight' && ev.key !== 'ArrowLeft') return;
                    var j = (i + (ev.key === 'ArrowRight' ? 1 : -1) + self.botoes.length) % self.botoes.length;
                    self.definir(self.botoes[j].getAttribute('data-c2f-aba'));
                    self.botoes[j].focus();
                });
            });
            if (this.botoes[0] && this.botoes[0].parentNode) { this.botoes[0].parentNode.setAttribute('role', 'tablist'); this.botoes[0].parentNode.classList.add('c2fc-abas-lista'); }
            this.paineis.forEach(function (p) { p.setAttribute('role', 'tabpanel'); });
            var inicial = this.botoes.filter(function (b) { return b.getAttribute('aria-selected') === 'true'; })[0] || this.botoes[0];
            if (inicial) this.definir(inicial.getAttribute('data-c2f-aba'), true);
        },
        valor: function () { var a = this.botoes.filter(function (b) { return b.getAttribute('aria-selected') === 'true'; })[0]; return a ? a.getAttribute('data-c2f-aba') : null; },
        definir: function (nome, silencioso) {
            var antes = this.valor();
            this.botoes.forEach(function (b) {
                var sim = b.getAttribute('data-c2f-aba') === nome;
                b.setAttribute('aria-selected', sim ? 'true' : 'false');
                b.setAttribute('tabindex', sim ? '0' : '-1');
            });
            this.paineis.forEach(function (p) { p.hidden = p.getAttribute('data-c2f-painel') !== nome; });
            if (!silencioso && antes !== nome) this.emitir('mudou', nome);
            return this;
        }
    });

    registrar('select', Select);
    registrar('chave', Chave);
    registrar('abas', Abas);

    function iniciar(raiz) {
        raiz = raiz || document;
        Array.prototype.forEach.call(raiz.querySelectorAll('[data-c2f-controle]'), function (no) { criar(no.getAttribute('data-c2f-controle'), no); });
        // Atalhos antigos desta mesma fatia.
        Array.prototype.forEach.call(raiz.querySelectorAll('select[data-c2f-select]'), function (no) { criar('select', no); });
        Array.prototype.forEach.call(raiz.querySelectorAll('[data-c2f-abas]'), function (no) { criar('abas', no); });
    }

    global.c2fControles = {
        Controle: Controle, estender: estender, registrar: registrar, criar: criar, de: de, iniciar: iniciar,
        tipos: tipos, dialogo: dialogos, aviso: aviso, carregando: carregando, texto: texto, formatado: formatado,
        // Atalhos.
        select: function (nativo, opcoes) { return nativo && nativo.tagName === 'SELECT' ? criar('select', nativo, opcoes) : null; },
        instancia: de
    };

    // =========================================================================== ponte Fomantic

    function ponte($) {
        if (!$ || !$.fn || $.fn.dropdown) return;

        /** Div de dropdown do Fomantic (input escondido + .menu .item) vira um <select> nativo equivalente. */
        function selectDoDiv(div) {
            if (div.c2fPonteSelect) return div.c2fPonteSelect;
            var existente = div.querySelector('select');
            if (existente) { div.c2fPonteSelect = existente; return existente; }
            var oculto = null;
            var padrao = null;
            Array.prototype.forEach.call(div.children, function (c) {
                if (c.tagName === 'INPUT' && c.type === 'hidden') oculto = c;
                if (c.classList.contains('text')) padrao = c;
            });
            var nativo = el('select', { 'class': 'c2fc-ponte-select', name: oculto ? oculto.getAttribute('name') : null });
            if (div.classList.contains('multiple')) nativo.multiple = true;
            nativo.appendChild(opcaoNova(padrao ? padrao.textContent.trim() : texto('selecione'), ''));
            Array.prototype.forEach.call(div.querySelectorAll('.menu .item'), function (item) {
                var valor = item.hasAttribute('data-value') ? item.getAttribute('data-value') : item.textContent.trim();
                nativo.appendChild(opcaoNova(item.getAttribute('data-text') || item.textContent.trim(), valor));
            });
            var atuais = oculto && oculto.value ? oculto.value.split(',') : [];
            Array.prototype.forEach.call(nativo.options, function (o) { o.selected = atuais.indexOf(o.value) >= 0; });
            Array.prototype.forEach.call(div.children, function (c) { c.style.display = 'none'; });
            if (oculto) oculto.removeAttribute('name');
            div.appendChild(nativo);
            div.c2fPonteSelect = nativo;
            nativo.addEventListener('change', function () {
                if (oculto) oculto.value = Array.prototype.filter.call(nativo.options, function (o) { return o.selected; }).map(function (o) { return o.value; }).join(',');
            });
            return nativo;
        }

        function controleDe(no, configuracao) {
            var nativo = no.tagName === 'SELECT' ? no : selectDoDiv(no);
            var api = criar('select', nativo, { busca: no.classList.contains('search') ? true : undefined });
            if (configuracao && typeof configuracao === 'object' && !api.ponteConfigurada) {
                api.ponteConfigurada = true;
                api.on('mudou', function (valor, rotulo) {
                    var v = Array.isArray(valor) ? valor.join(',') : valor;
                    if (configuracao.onChange) configuracao.onChange.call(no, v, Array.isArray(rotulo) ? rotulo.join(',') : rotulo, $(no));
                });
            }
            return api;
        }

        $.fn.dropdown = function (comando) {
            var args = Array.prototype.slice.call(arguments, 1);
            var retorno;
            this.each(function () {
                var api = controleDe(this, typeof comando === 'object' ? comando : null);
                if (typeof comando !== 'string') return;
                switch (comando) {
                    case 'get value': retorno = Array.isArray(api.valor()) ? api.valor().join(',') : api.valor(); break;
                    case 'get text': retorno = Array.isArray(api.rotulo()) ? api.rotulo().join(',') : api.rotulo(); break;
                    case 'set selected': case 'set exactly': case 'set value': api.definir(args[0], args[1] === true); break;
                    case 'clear': case 'restore defaults': api.limpar(args[0] === true); break;
                    case 'refresh': api.atualizar(); break;
                    case 'show': api.abrir(); break;
                    case 'hide': api.fechar(); break;
                    case 'destroy': api.destruir(); break;
                    default: break;
                }
            });
            return retorno !== undefined ? retorno : this;
        };

        $.fn.checkbox = function (comando) {
            var retorno;
            this.each(function () {
                var caixa = this;
                var input = caixa.tagName === 'INPUT' ? caixa : caixa.querySelector('input[type="checkbox"], input[type="radio"]');
                if (!input) return;
                caixa.classList.add('c2fc-ponte-checkbox');
                if (!caixa.c2fPonte) {
                    caixa.c2fPonte = { ao: [] };
                    input.addEventListener('change', function () { caixa.c2fPonte.ao.forEach(function (f) { f(input.checked); }); });
                    var rotulo = caixa.querySelector('label');
                    if (rotulo && !rotulo.getAttribute('for')) rotulo.addEventListener('click', function (ev) { if (ev.target !== input) { ev.preventDefault(); input.click(); } });
                }
                if (comando && typeof comando === 'object') {
                    caixa.c2fPonte.ao.push(function (marcado) {
                        if (comando.onChange) comando.onChange.call(input);
                        if (marcado && comando.onChecked) comando.onChecked.call(input);
                        if (!marcado && comando.onUnchecked) comando.onUnchecked.call(input);
                    });
                    return;
                }
                var mudar = function (valor, silencioso) { if (input.checked === valor) return; input.checked = valor; if (!silencioso) disparar(input, 'change'); };
                switch (comando) {
                    case 'is checked': retorno = input.checked; break;
                    case 'is unchecked': retorno = !input.checked; break;
                    case 'check': mudar(true); break;
                    case 'uncheck': mudar(false); break;
                    case 'set checked': mudar(true, true); break;
                    case 'set unchecked': mudar(false, true); break;
                    case 'toggle': mudar(!input.checked); break;
                    case 'enable': case 'set enabled': input.disabled = false; break;
                    case 'disable': case 'set disabled': input.disabled = true; break;
                    default: break;
                }
            });
            return retorno !== undefined ? retorno : this;
        };

        // Abas: cada grupo é o conjunto de irmãos (itens no mesmo menu, painéis no mesmo pai), para
        // que abas aninhadas (o editor tem o grupo do código dentro de um painel) não se desliguem.
        function irmaosComAba(no) {
            return no && no.parentNode ? Array.prototype.filter.call(no.parentNode.children, function (c) { return c.hasAttribute('data-tab'); }) : [];
        }
        function abaAtivar(nome, raiz) {
            raiz = raiz || document;
            var seletor = '[data-tab="' + String(nome).replace(/["\\]/g, '\\$&') + '"]';
            var item = raiz.querySelector('.item' + seletor) || document.querySelector('.item' + seletor);
            var painel = raiz.querySelector('.tab' + seletor) || document.querySelector('.tab' + seletor);
            irmaosComAba(item).forEach(function (i) { i.classList.toggle('active', i === item); });
            irmaosComAba(painel).forEach(function (p) { if (p.classList.contains('tab')) { p.classList.add('c2fc-ponte-tab'); p.classList.toggle('active', p === painel); } });
            var configuracao = (item && item.c2fPonteTab) || {};
            if (configuracao.onLoad) configuracao.onLoad.call(painel, nome, [], false);
            if (configuracao.onVisible) configuracao.onVisible.call(painel, nome);
        }

        $.fn.tab = function (comando, aba) {
            if (comando === 'change tab') { abaAtivar(aba, null); return this; }
            var configuracao = typeof comando === 'object' && comando ? comando : {};
            var ativo = null;
            this.each(function () {
                var item = this;
                var painel = document.querySelector('.tab[data-tab="' + item.getAttribute('data-tab') + '"]');
                irmaosComAba(painel).forEach(function (p) { if (p.classList.contains('tab')) p.classList.add('c2fc-ponte-tab'); });
                if (item.classList.contains('active')) ativo = item;
                var novo = !item.c2fPonteTab;
                item.c2fPonteTab = configuracao;
                if (!novo) return;
                item.addEventListener('click', function (ev) { ev.preventDefault(); abaAtivar(item.getAttribute('data-tab'), item.c2fPonteTab.context ? $(item.c2fPonteTab.context)[0] : null); });
            });
            // Como o Fomantic, a inicialização carrega a aba que já está ativa.
            if (ativo && (configuracao.onLoad || configuracao.onVisible)) abaAtivar(ativo.getAttribute('data-tab'), null);
            return this;
        };

        function prepararModal(modal) {
            if (modal.c2fPonte) return modal.c2fPonte;
            var fundo = el('div', { 'class': 'c2fc-overlay c2fc-oculto', 'data-c2fc-ponte-modal': true });
            // Como o Fomantic, o modal vai para o body: no editor ele mora dentro de um pai escondido.
            document.body.appendChild(fundo);
            fundo.appendChild(modal);
            modal.classList.add('c2fc-ponte-modal');
            modal.style.display = 'block';
            var estado = { fundo: fundo, config: {}, aberto: false };
            estado.fechar = function () {
                if (!estado.aberto) return;
                estado.aberto = false;
                fundo.classList.add('c2fc-oculto');
                if (estado.config.onHide) estado.config.onHide.call(modal);
                if (estado.config.onHidden) estado.config.onHidden.call(modal);
            };
            modal.addEventListener('click', function (ev) {
                var aprovar = ev.target.closest('.actions .approve, .actions .ok, .actions .positive');
                var negar = ev.target.closest('.actions .deny, .actions .cancel, .actions .negative, .close');
                if (aprovar) { if (!estado.config.onApprove || estado.config.onApprove.call(modal, $(aprovar)) !== false) estado.fechar(); }
                else if (negar) { if (!estado.config.onDeny || estado.config.onDeny.call(modal, $(negar)) !== false) estado.fechar(); }
            });
            fundo.addEventListener('mousedown', function (ev) { if (ev.target === fundo && estado.config.closable !== false) estado.fechar(); });
            document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && estado.aberto && estado.config.closable !== false) estado.fechar(); });
            modal.c2fPonte = estado;
            return estado;
        }

        $.fn.modal = function (comando, chave, valor) {
            if (comando === 'is active') return !!(this[0] && this[0].c2fPonte && this[0].c2fPonte.aberto);
            this.each(function () {
                var estado = prepararModal(this);
                if (comando && typeof comando === 'object') { Object.keys(comando).forEach(function (k) { estado.config[k] = comando[k]; }); return; }
                if (comando === 'setting') { if (typeof chave === 'object') Object.keys(chave).forEach(function (k) { estado.config[k] = chave[k]; }); else estado.config[chave] = valor; return; }
                if (comando === 'show' || (comando === 'toggle' && !estado.aberto)) {
                    estado.aberto = true;
                    estado.fundo.classList.remove('c2fc-oculto');
                    if (estado.config.onShow) estado.config.onShow.call(this);
                    if (estado.config.onVisible) estado.config.onVisible.call(this);
                } else if (comando === 'hide' || comando === 'toggle') {
                    estado.fechar();
                }
            });
            return this;
        };

        $.fn.dimmer = function (comando) {
            this.each(function () {
                var alvo = this.classList.contains('dimmer') ? this : null;
                if (!alvo) Array.prototype.forEach.call(this.children, function (c) { if (c.classList.contains('dimmer')) alvo = c; });
                if (!alvo) { carregando(this, comando !== 'hide'); return; }
                alvo.classList.add('c2fc-ponte-dimmer');
                if (comando === 'show') alvo.classList.add('active');
                else if (comando === 'hide') alvo.classList.remove('active');
            });
            return this;
        };

        $.fn.popup = function () {
            this.each(function () {
                var dica = this.getAttribute('data-content') || this.getAttribute('data-tooltip') || this.getAttribute('title');
                if (dica) { this.setAttribute('data-c2f-dica', dica); this.removeAttribute('title'); }
            });
            return this;
        };

        // Formulário: só o que os módulos usam do Fomantic (regras de vazio, valores, reset), com erro inline.
        function formCampo(form, id) {
            return form.querySelector('[data-validate="' + id + '"]') || form.querySelector('#' + id) || form.querySelector('[name="' + id + '"]');
        }
        function formValidar(form) {
            var estado = form.c2fPonteForm || { campos: {} };
            var valido = true;
            Array.prototype.forEach.call(form.querySelectorAll('.c2fc-ponte-form-erro'), function (e) { e.remove(); });
            Object.keys(estado.campos).forEach(function (chave) {
                var campo = estado.campos[chave];
                var entrada = formCampo(form, campo.identifier || chave);
                if (!entrada) return;
                (campo.rules || []).some(function (regra) {
                    var vazio = String(entrada.value || '').trim() === '';
                    if ((regra.type === 'empty' || regra.type === 'notEmpty') && vazio) {
                        valido = false;
                        entrada.setAttribute('aria-invalid', 'true');
                        var erro = el('p', { 'class': 'c2fc-campo-erro c2fc-ponte-form-erro', role: 'alert' });
                        erro.textContent = regra.prompt || texto('atencao');
                        entrada.insertAdjacentElement('afterend', erro);
                        return true;
                    }
                    entrada.removeAttribute('aria-invalid');
                    return false;
                });
            });
            estado.valido = valido;
            form.c2fPonteForm = estado;
            return valido;
        }
        if (!$.fn.form) $.fn.form = function (comando, chave, valor) {
            var retorno;
            this.each(function () {
                var form = this;
                if (!form.c2fPonteForm) form.c2fPonteForm = { campos: {}, valido: true };
                if (comando && typeof comando === 'object') { form.c2fPonteForm.campos = comando.fields || {}; return; }
                switch (comando) {
                    case 'validate form': retorno = formValidar(form); break;
                    case 'is valid': retorno = formValidar(form); break;
                    case 'get value': { var c = formCampo(form, chave); retorno = c ? c.value : undefined; break; }
                    case 'get values': retorno = {}; Array.prototype.forEach.call(form.querySelectorAll('[name]'), function (c) { retorno[c.name] = c.value; }); break;
                    case 'set value': { var d = formCampo(form, chave); if (d) d.value = valor; break; }
                    case 'reset': case 'clear':
                        if (typeof form.reset === 'function') form.reset();
                        Array.prototype.forEach.call(form.querySelectorAll('.c2fc-ponte-form-erro'), function (e) { e.remove(); });
                        break;
                    default: break;
                }
            });
            return retorno !== undefined ? retorno : this;
        };

        // Toast: o aviso da biblioteca, com título, classe (success/error/warning) e ações com clique.
        if (!$.fn.toast) $.fn.toast = function (cfg) {
            cfg = cfg || {};
            var classe = String(cfg['class'] || '');
            var tipo = /error|red|negative/.test(classe) ? 'erro' : (/success|green|positive/.test(classe) ? 'sucesso' : (/warning|orange|yellow/.test(classe) ? 'alerta' : 'info'));
            var tempo = cfg.displayTime === 0 || cfg.displayTime === '0' ? 0 : (cfg.displayTime === 'auto' || cfg.displayTime === undefined ? undefined : parseInt(cfg.displayTime, 10));
            if (cfg.actions && cfg.actions.length && tempo === undefined) tempo = 0;
            var mensagem = (cfg.title ? cfg.title + ' — ' : '') + String(cfg.message || '').replace(/<[^>]*>/g, '');
            var fechar = aviso(mensagem, tipo, tempo);
            var itens = document.querySelectorAll('.c2fc-avisos .c2fc-aviso');
            var item = itens[itens.length - 1];
            (cfg.actions || []).forEach(function (acao) {
                if (!item) return;
                var botao = el('button', { type: 'button', 'class': 'c2fc-aviso-acao', text: String(acao.text || '').replace(/<[^>]*>/g, '') });
                botao.addEventListener('click', function () {
                    var manter = typeof acao.click === 'function' ? acao.click.call(botao) === false : false;
                    if (!manter) fechar();
                });
                item.insertBefore(botao, item.lastChild);
            });
            return this;
        };

        $.fn.search = function () { return this; };
        if (!$.fn.transition) {
            $.fn.transition = function (animacao) {
                this.each(function () {
                    if (/out/.test(String(animacao)) || animacao === 'hide') this.style.display = 'none';
                    else if (/in/.test(String(animacao)) || animacao === 'show') this.style.display = '';
                });
                return this;
            };
        }
        global.c2fControles.ponteAtiva = true;
        // Sem o Fomantic, as dicas `data-tooltip`/`data-position` dele passam a ser desenhadas por controles.css.
        document.documentElement.classList.add('c2fc-sem-fomantic');
    }

    // =========================================================================== campo imagepick
    // O campo de imagem do interface (`widget-imagem`) era ligado pelo `interface.js` legado, que não vai
    // às páginas Tailwind. Mesmo contrato: `gestor.interface.imagepick`, modal `.iframePagina` com o
    // admin-arquivos e a resposta por `postMessage`. Só liga com a ponte ativa (sem o legado na página).

    function imagemValor(caixa, seletor, valor) {
        var alvo = caixa.querySelector(seletor);
        if (!alvo) return;
        var span = alvo.querySelector('.widgetImage-valor');
        if (span) { span.textContent = valor; return; }
        var icone = alvo.querySelector('.icon');
        if (icone && icone.nextSibling) icone.nextSibling.remove();
        alvo.appendChild(document.createTextNode(valor));
    }

    function imagemPreencher(caixa, dados) {
        var id = caixa.querySelector('input._gestor-widgetImage-file-id');
        var caminho = caixa.querySelector('input._gestor-widgetImage-file-caminho');
        var img = caixa.querySelector('.widgetImage-image');
        var nome = caixa.querySelector('.widgetImage-nome');
        if (id) id.value = dados.fileId !== undefined ? dados.fileId : dados.id;
        if (caminho) caminho.value = dados.caminho || '';
        var url = caixa.querySelector('[data-c2f-imagem-url]');
        if (url) url.value = dados.caminho || '';
        if (img) img.setAttribute('src', dados.imgSrc || '');
        if (nome) nome.textContent = dados.nome || '';
        imagemValor(caixa, '.widgetImage-data', dados.data || '');
        imagemValor(caixa, '.widgetImage-tipo', dados.tipo || '');
    }

    function imagemSeletor() {
        var $ = global.jQuery;
        var config = global.gestor && global.gestor.interface && global.gestor.interface.imagepick;
        if (!global.c2fControles.ponteAtiva || !$ || !config || global.c2fControles.imagemLigada || !document.querySelector('._gestor-widgetImage-cont')) return;
        global.c2fControles.imagemLigada = true;
        var atual = null;
        document.addEventListener('input', function (ev) {
            if (!ev.target.matches('[data-c2f-imagem-url]')) return;
            var url = ev.target.value.trim();
            if (url && !/^(https?:\/\/|\/|[^:]+$)/i.test(url)) return;
            imagemPreencher(ev.target.closest('._gestor-widgetImage-cont'), { fileId: '-1', caminho: url, imgSrc: url });
        });
        document.addEventListener('click', function (ev) {
            var adicionar = ev.target.closest('._gestor-widgetImage-btn-add');
            var remover = ev.target.closest('._gestor-widgetImage-btn-del');
            if (remover) { ev.preventDefault(); imagemPreencher(remover.closest('._gestor-widgetImage-cont'), config.padroes || {}); return; }
            if (!adicionar) return;
            ev.preventDefault();
            var modal = document.querySelector('.ui.modal.iframePagina');
            if (!modal) return;
            atual = adicionar.closest('._gestor-widgetImage-cont');
            var cabeca = modal.querySelector('.header');
            var cancelar = modal.querySelector('.cancel');
            if (cabeca && config.modal) cabeca.textContent = config.modal.head || '';
            if (cancelar && config.modal) cancelar.textContent = config.modal.cancel || '';
            var quadro = modal.querySelector('iframe');
            var $modal = $(modal);
            $modal.dimmer('show');
            quadro.onload = function () { $modal.dimmer('hide'); };
            quadro.setAttribute('src', config.modal ? config.modal.url : 'about:blank');
            $modal.modal('show');
        });
        global.addEventListener('message', function (ev) {
            var quadro = document.querySelector('.ui.modal.iframePagina iframe');
            if (!atual || ev.origin !== global.location.origin || !quadro || ev.source !== quadro.contentWindow) return;
            var dados;
            try {
                var msg = JSON.parse(ev.data);
                if (msg.moduloId !== 'admin-arquivos') return;
                dados = JSON.parse(decodeURI(msg.data));
            } catch (e) { return; }
            if (!/^image\//.test(String(dados.tipo || ''))) {
                dialogo('alerta', (config.alertas && config.alertas.naoImagem) || texto('atencao'), null, { formatado: true });
                return;
            }
            imagemPreencher(atual, dados);
            atual = null;
            $('.ui.modal.iframePagina').modal('hide');
        });
    }
    global.c2fControles.imagemPreencher = imagemPreencher;

    // A ponte entra já (para quem chama `$.fn.dropdown` no próprio `ready`) e de novo no carregamento.
    // Com o Fomantic, que carrega antes deste arquivo, `$.fn.dropdown` existe e a ponte não entra.
    ponte(global.jQuery);
    // req-219: o `interface.js` legado marcava os checkboxes de `data-checked="checked"` (o PHP troca o
    // marcador por "checked" ou vazio). Na página Tailwind ele não existe; a biblioteca faz o mesmo.
    function marcarDataChecked(raiz) {
        if (!global.c2fControles.ponteAtiva) return;
        Array.prototype.forEach.call((raiz || document).querySelectorAll('input[data-checked]'), function (input) {
            if (input.c2fDataChecked) return;
            input.c2fDataChecked = true;
            if (input.getAttribute('data-checked') === 'checked') input.checked = true;
        });
    }
    global.c2fControles.marcarDataChecked = marcarDataChecked;

    // REQ-243: select com `data-c2f-select` que entra na página depois da carga (linha clonada de um
    // <template>, bloco vindo por AJAX) também vira controle. O que já está dentro de um controle fica de fora.
    function observarSelects() {
        if (!global.MutationObserver || !document.body) return;
        var montar = function (no) {
            if (no.closest('template')) return;
            var casca = no.closest('.c2fc-select');
            // Cópia de um controle já montado (linha ou caixa clonada do DOM, como a de "Adicionar variável"):
            // a casca veio junto, mas sem o controle por trás, e o select não respondia. Sai a casca copiada
            // e o select é montado de novo.
            if (casca) {
                // A marca é propriedade do elemento, não atributo: `cloneNode` não a leva, e é assim que se
                // distingue a casca viva da copiada.
                if (casca.c2fcViva) return;
                casca.parentNode.insertBefore(no, casca);
                casca.parentNode.removeChild(casca);
                no.classList.remove('c2fc-nativo');
                no.removeAttribute('aria-hidden');
                no.removeAttribute('tabindex');
            }
            criar('select', no);
        };
        new global.MutationObserver(function (registros) {
            registros.forEach(function (registro) {
                Array.prototype.forEach.call(registro.addedNodes, function (no) {
                    if (no.nodeType !== 1) return;
                    // Com o atributo, ou já dentro de uma casca de controle (cópia de controle montado por código).
                    var serve = function (s) { return s.hasAttribute('data-c2f-select') || (s.parentElement && s.parentElement.classList.contains('c2fc-select')); };
                    if (no.tagName === 'SELECT' && serve(no)) montar(no);
                    Array.prototype.filter.call(no.querySelectorAll('select'), serve).forEach(montar);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    function preparar() { ponte(global.jQuery); marcarDataChecked(document); iniciar(document); imagemSeletor(); observarSelects(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', preparar); else preparar();
})(window);
