/**
 * presentations.widget.js — comportamento público do widget de apresentações (req-208).
 *
 * Para cada contêiner `[data-c2f-deck]`:
 *   - cada `[data-slide]` é um slide; um só fica visível (`.is-active`), com a transição escolhida;
 *   - setas `[data-c2f-deck-prev]` / `[data-c2f-deck-next]`, pontos `[data-c2f-deck-dot="N"]`,
 *     contador `[data-c2f-deck-current]`, progresso `[data-c2f-deck-progress]`, tela cheia
 *     `[data-c2f-deck-fullscreen]` e saltos `[data-c2f-deck-goto="N"]` (N começa em 0) em qualquer slide;
 *   - teclado (setas, espaço, PageUp/PageDown, Home, End, F), deslize no toque e, em tela cheia,
 *     pinça para ampliar e arrasto para mover;
 *   - em telas pequenas o palco `[data-c2f-deck-stage]` é desenhado maior e reduzido por escala, para
 *     o slide manter a proporção de uma tela larga;
 *   - `#slide-3` no endereço abre no terceiro slide (`data-hash="true"`).
 *
 * Opções pelos atributos do contêiner: data-mode, data-transition, data-loop, data-keyboard,
 * data-touch, data-hash, data-autoplay, data-speed. `?transition=zoom&loop=true` no endereço
 * sobrepõe as duas primeiras, como na apresentação original feita à mão.
 *
 * API: `elemento.c2fDeck` = { next, prev, goTo, current, total, setTransition }.
 * Evento: `c2f:deck:change` no contêiner, com `detail = { index, total }`.
 */
(function () {
    'use strict';

    var ANIMS = ['c2f-anim-fade', 'c2f-anim-zoom', 'c2f-anim-slide-up', 'c2f-anim-slide-left', 'c2f-anim-slide-right'];
    var ALEATORIAS = ['fade', 'zoom', 'slide-up', 'slide-horizontal'];
    var decks = [];
    var ativo = null;      // deck que recebe o teclado

    function bool(el, nome, padrao) {
        var v = el.getAttribute(nome);
        if (v === null || v === '') return padrao;
        return v === 'true' || v === '1';
    }

    function digitando(alvo) {
        if (!alvo || !alvo.tagName) return false;
        var tag = alvo.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || alvo.isContentEditable;
    }

    function Deck(root) {
        var self = this;
        var params = new URLSearchParams(window.location.search);
        var reduzido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.root = root;
        this.stage = root.querySelector('[data-c2f-deck-stage]');
        this.slides = Array.prototype.slice.call(root.querySelectorAll('[data-slide]'));
        this.dots = Array.prototype.slice.call(root.querySelectorAll('[data-c2f-deck-dot]'));
        this.total = this.slides.length;
        this.index = 0;
        this.modo = root.getAttribute('data-mode') === 'embedded' ? 'embedded' : 'fullscreen';
        this.preview = bool(root, 'data-preview', false);
        this.cfg = {
            transition: params.get('transition') || root.getAttribute('data-transition') || 'random',
            loop: params.has('loop') ? params.get('loop') === 'true' : bool(root, 'data-loop', false),
            keyboard: bool(root, 'data-keyboard', true),
            touch: bool(root, 'data-touch', true),
            hash: bool(root, 'data-hash', true) && !this.preview,
            autoplay: bool(root, 'data-autoplay', false),
            speed: Math.max(1000, parseInt(root.getAttribute('data-speed'), 10) || 8000)
        };

        // Escala e ampliação
        this.baseScale = 1;
        this.zoomScale = 1;
        this.tx = 0;
        this.ty = 0;
        this.timer = null;

        if (this.total === 0) return;

        function mostrar(direcao, efeito) {
            var escolhido = efeito || self.cfg.transition;
            if (escolhido === 'random') escolhido = ALEATORIAS[Math.floor(Math.random() * ALEATORIAS.length)];

            var classe = 'c2f-anim-slide-up';
            if (reduzido) classe = '';
            else if (escolhido === 'fade') classe = 'c2f-anim-fade';
            else if (escolhido === 'zoom') classe = 'c2f-anim-zoom';
            else if (escolhido === 'slide-horizontal') classe = direcao === 'prev' ? 'c2f-anim-slide-right' : 'c2f-anim-slide-left';

            self.slides.forEach(function (slide, i) {
                ANIMS.forEach(function (a) { slide.classList.remove(a); });
                if (i === self.index) {
                    slide.classList.add('is-active');
                    slide.removeAttribute('aria-hidden');
                    void slide.offsetWidth;   // reinicia a animação
                    if (classe) slide.classList.add(classe);
                    slide.scrollTop = 0;
                } else {
                    slide.classList.remove('is-active');
                    slide.setAttribute('aria-hidden', 'true');
                }
            });

            self.dots.forEach(function (dot) {
                var atual = parseInt(dot.getAttribute('data-c2f-deck-dot'), 10) === self.index;
                if (atual) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current');
            });

            Array.prototype.forEach.call(root.querySelectorAll('[data-c2f-deck-current]'), function (el) {
                el.textContent = String(self.index + 1);
            });
            Array.prototype.forEach.call(root.querySelectorAll('[data-c2f-deck-progress]'), function (el) {
                el.style.width = (((self.index + 1) / self.total) * 100) + '%';
            });

            if (!self.cfg.loop) {
                Array.prototype.forEach.call(root.querySelectorAll('[data-c2f-deck-prev]'), function (b) { b.disabled = self.index === 0; });
                Array.prototype.forEach.call(root.querySelectorAll('[data-c2f-deck-next]'), function (b) { b.disabled = self.index === self.total - 1; });
            }

            if (self.cfg.hash && window.history && window.history.replaceState) {
                var destino = self.index === 0 ? window.location.pathname + window.location.search : '#slide-' + (self.index + 1);
                try { window.history.replaceState(null, '', destino); } catch (e) { }
            }

            root.dispatchEvent(new CustomEvent('c2f:deck:change', { bubbles: true, detail: { index: self.index, total: self.total } }));
        }

        this.next = function () {
            if (self.index >= self.total - 1 && !self.cfg.loop) return;
            self.index = (self.index + 1) % self.total;
            mostrar('next');
        };

        this.prev = function () {
            if (self.index <= 0 && !self.cfg.loop) return;
            self.index = (self.index - 1 + self.total) % self.total;
            mostrar('prev');
        };

        this.goTo = function (i, efeito) {
            i = parseInt(i, 10);
            if (isNaN(i) || i < 0 || i >= self.total) return;
            var direcao = i >= self.index ? 'next' : 'prev';
            self.index = i;
            mostrar(direcao, efeito || null);
        };

        this.current = function () { return self.index; };
        this.setTransition = function (tipo) { self.cfg.transition = tipo; };

        // ----- Escala: o slide mantém a proporção de tela larga em telas pequenas.

        function aplicarEscala() {
            if (!self.stage) return;
            var total = self.baseScale * self.zoomScale;
            self.stage.style.transform = (total === 1 && self.tx === 0 && self.ty === 0) ? '' : 'translate(' + self.tx + 'px, ' + self.ty + 'px) scale(' + total + ')';
        }

        function ajustar() {
            if (!self.stage) return;
            var w = root.clientWidth;
            var h = root.clientHeight;
            if (!w || !h) return;
            var retrato = h > w;

            if (w <= 768 && retrato) self.baseScale = 0.7;
            else if (w <= 1280 && !retrato) self.baseScale = Math.min(0.8, Math.max(0.4, h / 600));
            else self.baseScale = 1;

            if (self.baseScale === 1) {
                self.stage.style.width = '';
                self.stage.style.height = '';
            } else {
                self.stage.style.width = (w / self.baseScale) + 'px';
                self.stage.style.height = (h / self.baseScale) + 'px';
            }
            aplicarEscala();
        }

        function zerarZoom() {
            self.zoomScale = 1;
            self.tx = 0;
            self.ty = 0;
        }

        ajustar();
        window.addEventListener('resize', ajustar);
        if (typeof ResizeObserver !== 'undefined') new ResizeObserver(ajustar).observe(root);

        // ----- Tela cheia

        function telaCheia() { return document.fullscreenElement === root; }

        this.toggleFullscreen = function () {
            if (!document.fullscreenElement) {
                if (root.requestFullscreen) root.requestFullscreen().catch(function () { });
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        };

        document.addEventListener('fullscreenchange', function () {
            root.classList.toggle('is-fullscreen', telaCheia());
            zerarZoom();
            setTimeout(ajustar, 50);
        });

        if (this.modo === 'fullscreen' && !this.preview && screen.orientation && screen.orientation.addEventListener) {
            screen.orientation.addEventListener('change', function () {
                // Aparelho deitado: tenta tela cheia. O navegador pode recusar sem gesto do usuário.
                if (screen.orientation.type.indexOf('landscape') !== -1) {
                    if (!document.fullscreenElement && root.requestFullscreen) root.requestFullscreen().catch(function () { });
                } else if (telaCheia() && document.exitFullscreen) {
                    document.exitFullscreen();
                }
                zerarZoom();
                setTimeout(ajustar, 100);
            });
        }

        // ----- Cliques

        root.addEventListener('click', function (e) {
            var alvo = e.target.closest('[data-c2f-deck-prev],[data-c2f-deck-next],[data-c2f-deck-dot],[data-c2f-deck-goto],[data-c2f-deck-fullscreen]');
            if (!alvo || !root.contains(alvo)) return;
            e.preventDefault();
            reiniciarAutoplay();
            if (alvo.hasAttribute('data-c2f-deck-prev')) self.prev();
            else if (alvo.hasAttribute('data-c2f-deck-next')) self.next();
            else if (alvo.hasAttribute('data-c2f-deck-dot')) self.goTo(alvo.getAttribute('data-c2f-deck-dot'));
            else if (alvo.hasAttribute('data-c2f-deck-goto')) self.goTo(alvo.getAttribute('data-c2f-deck-goto'));
            else self.toggleFullscreen();
        });

        root.addEventListener('pointerdown', function () { ativo = self; });
        root.addEventListener('mouseenter', function () { ativo = self; });

        // ----- Toque: deslize para navegar; em tela cheia, pinça para ampliar e arrasto para mover.

        var inicioX = 0;
        var ultimoX = 0;
        var ultimoY = 0;
        var pinca = false;
        var distInicial = 0;
        var escalaInicial = 1;
        var quadro = null;

        function pedirQuadro() {
            if (quadro) return;
            quadro = requestAnimationFrame(function () { aplicarEscala(); quadro = null; });
        }

        function limitar() {
            var minX = root.clientWidth * (1 - self.zoomScale);
            var minY = root.clientHeight * (1 - self.zoomScale);
            if (self.zoomScale > 1) {
                self.tx = Math.max(minX, Math.min(0, self.tx));
                self.ty = Math.max(minY, Math.min(0, self.ty));
            } else {
                self.tx = 0;
                self.ty = 0;
            }
        }

        root.addEventListener('touchstart', function (e) {
            ativo = self;
            if (e.touches.length === 1 && !pinca) {
                inicioX = e.changedTouches[0].screenX;
                ultimoX = e.touches[0].clientX;
                ultimoY = e.touches[0].clientY;
            } else if (telaCheia() && e.touches.length === 2 && !pinca) {
                pinca = true;
                distInicial = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
                escalaInicial = self.zoomScale;
            }
        }, { passive: true });

        root.addEventListener('touchmove', function (e) {
            if (pinca && e.touches.length === 2) {
                e.preventDefault();
                var dist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
                var cx = (e.touches[0].clientX + e.touches[1].clientX) / 2;
                var cy = (e.touches[0].clientY + e.touches[1].clientY) / 2;
                var nova = Math.max(1, Math.min(3, escalaInicial * (dist / distInicial)));
                // O ponto sob os dedos fica parado enquanto a escala muda.
                self.tx = cx - (cx - self.tx) * (nova / self.zoomScale);
                self.ty = cy - (cy - self.ty) * (nova / self.zoomScale);
                self.zoomScale = nova;
                limitar();
                pedirQuadro();
            } else if (!pinca && self.zoomScale > 1 && e.touches.length === 1) {
                e.preventDefault();
                self.tx += e.touches[0].clientX - ultimoX;
                self.ty += e.touches[0].clientY - ultimoY;
                ultimoX = e.touches[0].clientX;
                ultimoY = e.touches[0].clientY;
                limitar();
                pedirQuadro();
            }
        }, { passive: false });

        root.addEventListener('touchend', function (e) {
            if (pinca) {
                pinca = false;
            } else if (e.changedTouches.length === 1 && inicioX !== 0) {
                var fimX = e.changedTouches[0].screenX;
                var ampliado = (window.visualViewport && window.visualViewport.scale > 1) || self.zoomScale > 1;
                if (self.cfg.touch && !ampliado) {
                    if (fimX < inicioX - 50) { reiniciarAutoplay(); self.next(); }
                    if (fimX > inicioX + 50) { reiniciarAutoplay(); self.prev(); }
                }
            }
            inicioX = 0;
        }, { passive: true });

        // ----- Reprodução automática

        function pararAutoplay() {
            if (self.timer) { clearInterval(self.timer); self.timer = null; }
        }

        function reiniciarAutoplay() {
            pararAutoplay();
            if (!self.cfg.autoplay || self.total < 2) return;
            self.timer = setInterval(function () {
                if (self.index >= self.total - 1 && !self.cfg.loop) { pararAutoplay(); return; }
                self.next();
            }, self.cfg.speed);
        }

        root.addEventListener('mouseenter', pararAutoplay);
        root.addEventListener('mouseleave', reiniciarAutoplay);

        // ----- Início

        if (this.modo === 'fullscreen' && !this.preview) document.documentElement.classList.add('c2f-deck-lock');

        function slideDoEndereco() {
            var m = /^#slide-(\d+)$/.exec(window.location.hash || '');
            if (!m) return -1;
            var pedido = parseInt(m[1], 10) - 1;
            return (pedido >= 0 && pedido < self.total) ? pedido : -1;
        }

        if (this.cfg.hash) {
            var inicial = slideDoEndereco();
            if (inicial !== -1) this.index = inicial;
            // Link para #slide-N na mesma página, ou o botão de voltar do navegador.
            window.addEventListener('hashchange', function () {
                var pedido = slideDoEndereco();
                if (pedido !== -1 && pedido !== self.index) self.goTo(pedido);
            });
        }

        root.classList.add('is-ready');
        mostrar('next', 'fade');
        reiniciarAutoplay();
    }

    function iniciar() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-c2f-deck]'), function (root) {
            if (root.c2fDeck) return;
            // Modelo cru, com as opções ainda como variáveis (editor HTML): não é uma apresentação
            // renderizada. O CSS do modelo mostra os slides empilhados e nada é iniciado.
            var modo = root.getAttribute('data-mode');
            if (modo !== 'fullscreen' && modo !== 'embedded') return;
            var deck = new Deck(root);
            root.c2fDeck = deck;
            decks.push(deck);
        });
        // O teclado vai para a apresentação de tela inteira; sem ela, para a primeira da página.
        if (!ativo) {
            ativo = decks.filter(function (d) { return d.modo === 'fullscreen'; })[0] || decks[0] || null;
        }
    }

    document.addEventListener('keydown', function (e) {
        var deck = ativo;
        if (!deck || !deck.total || !deck.cfg.keyboard) return;
        if (e.altKey || e.ctrlKey || e.metaKey || digitando(e.target)) return;
        // Apresentação embutida só responde depois que o visitante interage com ela ou em tela cheia.
        if (deck.modo === 'embedded' && document.fullscreenElement !== deck.root && !deck.root.matches(':hover') && !deck.root.contains(document.activeElement)) return;

        switch (e.key) {
            case 'ArrowRight':
            case 'PageDown':
            case ' ':
                e.preventDefault();
                deck.next();
                break;
            case 'ArrowLeft':
            case 'PageUp':
                e.preventDefault();
                deck.prev();
                break;
            case 'Home':
                e.preventDefault();
                deck.goTo(0);
                break;
            case 'End':
                e.preventDefault();
                deck.goTo(deck.total - 1);
                break;
            case 'f':
            case 'F':
                deck.toggleFullscreen();
                break;
        }
    });

    window.c2fDecks = decks;

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();

    // Apresentação que chega depois da carga (prévia do editor de páginas, conteúdo trazido por AJAX).
    if (typeof MutationObserver !== 'undefined') {
        var agendado = null;
        var observar = function () {
            new MutationObserver(function () {
                if (agendado) return;
                agendado = setTimeout(function () { agendado = null; iniciar(); }, 50);
            }).observe(document.body, { childList: true, subtree: true });
        };
        if (document.body) observar(); else document.addEventListener('DOMContentLoaded', observar);
    }
})();
