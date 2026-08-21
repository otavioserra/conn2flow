/**
 * Runtime do layout administrativo Tailwind (req-118 / BATCH-119).
 *
 * JavaScript nativo, sem jQuery e sem Fomantic. Substitui, para o layout `layout-administrativo-
 * tailwind`, o bloco de menu do `global.js` — que depende das classes `.menuComputerCont`/
 * `.paginaCont` e do jQuery. Os dois nunca convivem: `gestor_pagina_menu()` injeta um OU outro,
 * conforme o framework resolvido a partir de layout + página.
 *
 * Responsabilidades: abrir/fechar (com comportamento distinto em mobile), redimensionar a barra por
 * arraste com limites e persistência, e filtrar módulos em tempo real.
 */
(function () {
    'use strict';

    var CHAVE_LARGURA = 'gestor-menu-width';
    var CHAVE_FECHADO = 'gestor-menu-closed';
    var QUEBRA_MOBILE = 1024;

    function normalizar(texto) {
        // Fonte 100% ASCII: o range de marcas combinantes é montado por código para sobreviver a
        // qualquer normalização Unicode que um editor aplique ao arquivo (regra do BATCH-103).
        var marcas = new RegExp('[' + String.fromCharCode(0x0300) + '-' + String.fromCharCode(0x036f) + ']', 'g');
        return String(texto || '').normalize('NFD').replace(marcas, '').toLowerCase();
    }

    function lerNumero(valor, padrao) {
        var n = parseInt(valor, 10);
        return isNaN(n) ? padrao : n;
    }

    function armazenamento() {
        // localStorage indisponível (modo privado, cota, iframe sem permissão) não pode derrubar o
        // menu: sem ele o layout apenas deixa de lembrar a preferência.
        try {
            var teste = '__c2f__';
            window.localStorage.setItem(teste, teste);
            window.localStorage.removeItem(teste);
            return window.localStorage;
        } catch (e) {
            return null;
        }
    }

    // req-124 F2: o menu Tailwind escreve os ícones dos módulos como `<i data-lucide="…">` — o
    // vocabulário que `modulos.icone_tailwind` guarda. Quem troca esses marcadores por SVG é o
    // `lucide.createIcons()`. O layout carrega o pacote com `defer`, então ele já executou quando o
    // DOMContentLoaded chega aqui; o listener de `load` é a rede de segurança para o caso do CDN
    // demorar ou o script ser injetado async por um projeto derivado. Sem Lucide o menu perde só os
    // ícones, nunca a navegação.
    var LUCIDE_NOME = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

    // req-125 F4: segunda camada do saneamento. `gestor_pagina_menu_icone_lucide_atributo()` já não
    // emite `data-lucide` para nome que o Lucide não consegue endereçar, mas o menu não é a única
    // origem possível de marcação no painel — um componente sobrescrito por projeto, um widget ou um
    // módulo distribuído podem escrever o atributo por conta própria, com o nome composto do
    // Fomantic dentro. `createIcons()` reclama uma vez POR ELEMENTO, então bastam alguns itens para
    // encher o console. Remover o atributo antes da chamada mantém o `<i>` intacto e deixa a folha
    // do Fomantic desenhar pela classe, que é o fallback já existente.
    function sanearIcones(raiz) {
        var alvos = (raiz || document).querySelectorAll('[data-lucide]');

        for (var i = 0; i < alvos.length; i++) {
            var nome = alvos[i].getAttribute('data-lucide');
            if (!nome || !LUCIDE_NOME.test(nome.trim())) alvos[i].removeAttribute('data-lucide');
        }
    }

    function desenharIcones() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            sanearIcones(document);
            window.lucide.createIcons();
            return true;
        }
        return false;
    }

    function iniciar() {
        var shell = document.getElementById('c2f-admin-shell');
        if (!shell) return;

        if (!desenharIcones()) window.addEventListener('load', desenharIcones);

        var sidebar = shell.querySelector('[data-admin-sidebar]');
        var conteudo = shell.querySelector('[data-admin-conteudo]');
        var overlay = shell.querySelector('[data-admin-overlay]');
        var handle = shell.querySelector('[data-admin-resize]');
        var btnAbrir = shell.querySelector('[data-admin-abrir]');
        var btnFechar = shell.querySelector('[data-admin-fechar]');
        var btn3d = shell.querySelector('[data-admin-dashboard3d]');
        var principal = shell.querySelector('[data-admin-main]');

        if (!sidebar || !conteudo) return;

        // req-124 F3: a largura de leitura (`max-w-7xl`) faz sentido enquanto a barra ocupa a
        // esquerda. Com o menu recolhido ela deixaria de novo uma faixa vazia — agora nos dois lados,
        // porque o `mx-auto` centraliza a coluna. O `maxWidth` inline vence a utility sem depender da
        // ordem em que o Tailwind emitiu `max-w-7xl` e `max-w-none`.
        var larguraLeituraPadrao = principal ? principal.style.maxWidth : '';

        function aplicarLarguraConteudo(expandido) {
            if (!principal) return;
            principal.style.maxWidth = expandido ? 'none' : larguraLeituraPadrao;
        }

        var store = armazenamento();
        var larguraMin = lerNumero(shell.getAttribute('data-menu-largura-min'), 220);
        var larguraMax = lerNumero(shell.getAttribute('data-menu-largura-max'), 450);
        var larguraPadrao = lerNumero(shell.getAttribute('data-menu-largura-padrao'), 260);

        function ehMobile() {
            return window.innerWidth <= QUEBRA_MOBILE;
        }

        function larguraSalva() {
            var valor = store ? store.getItem(CHAVE_LARGURA) : null;
            return Math.min(larguraMax, Math.max(larguraMin, lerNumero(valor, larguraPadrao)));
        }

        // req-124 F3: `marginLeft = ''` NÃO zera o recuo — apenas devolve o controle à utility
        // `lg:ml-[260px]` que o layout aplica para o conteúdo já nascer no lugar certo em desktop. Era
        // essa utility, sobrevivendo ao inline style removido, que deixava a faixa vazia de 260px ao
        // recolher o menu. Zerar explicitamente é o que faz o conteúdo alcançar a borda esquerda.
        function recuarConteudo(margem) {
            conteudo.style.marginLeft = margem === 0 ? '0px' : margem + 'px';
        }

        function aplicarLargura(largura) {
            largura = Math.min(larguraMax, Math.max(larguraMin, largura));
            sidebar.style.width = largura + 'px';
            // Em mobile a barra é overlay: empurrar o conteúdo deixaria a página rolando na horizontal.
            var semRecuo = (ehMobile() || estaFechado());
            recuarConteudo(semRecuo ? 0 : largura);
            aplicarLarguraConteudo(semRecuo);
            return largura;
        }

        function estaFechado() {
            return sidebar.classList.contains('-translate-x-full');
        }

        // req-125 F3: só o botão CONTEXTUAL fica em tela — "abrir" enquanto o menu está recolhido,
        // "fechar" enquanto está expandido. Antes os dois coexistiam no desktop, cada um num canto,
        // e a barra oferecia duas ações contraditórias ao mesmo tempo.
        //
        // A classe `hidden` sozinha NÃO esconde estes dois botões. Ambos são `inline-flex`, e no
        // bundle do layout `.inline-flex` é emitida DEPOIS de `.hidden` — mesma especificidade,
        // mesma camada, ganha a última: o botão seguiria visível com a classe aplicada. Quem decide
        // é o atributo booleano `hidden`, que o preflight do Tailwind serve como
        // `display:none!important` em `@layer base` — e `!important` inverte a ordem das camadas,
        // então ele vence qualquer utility. A classe continua sendo escrita porque é o estado que a
        // marcação declara e o que os testes leem.
        function alternarVisibilidade(elemento, oculto) {
            if (!elemento) return;
            elemento.classList.toggle('hidden', !!oculto);
            elemento.hidden = !!oculto;
        }

        function sincronizarBotoes(fechado) {
            alternarVisibilidade(btnAbrir, !fechado);
            alternarVisibilidade(btnFechar, !!fechado);
        }

        function abrir() {
            sidebar.classList.remove('-translate-x-full');
            sincronizarBotoes(false);
            if (ehMobile()) {
                if (overlay) overlay.classList.remove('hidden');
                // Overlay: o conteúdo fica onde está, mas segue sem recuo herdado da utility.
                recuarConteudo(0);
                aplicarLarguraConteudo(true);
            } else {
                recuarConteudo(larguraSalva());
                aplicarLarguraConteudo(false);
                if (store) store.setItem(CHAVE_FECHADO, 'false');
            }
        }

        function fechar() {
            sidebar.classList.add('-translate-x-full');
            sincronizarBotoes(true);
            if (overlay) overlay.classList.add('hidden');
            recuarConteudo(0);
            aplicarLarguraConteudo(true);
            if (!ehMobile() && store) store.setItem(CHAVE_FECHADO, 'true');
        }

        function alternar() {
            if (estaFechado()) { abrir(); } else { fechar(); }
        }

        // ===== Estado inicial
        //
        // O HTML nasce com `-translate-x-full lg:translate-x-0`: fechado no mobile e aberto no
        // desktop já na primeira pintura, sem salto. A classe utilitária é removida assim que o
        // estado real é conhecido, para o controle passar a ser só do JS.

        sidebar.classList.remove('lg:translate-x-0');

        // req-125 F3: o botão "abrir" nasce com `lg:hidden` pela mesma razão — no desktop o menu
        // nasce expandido, e sem isso o botão apareceria por um quadro ao lado do "fechar". A partir
        // daqui quem decide é `sincronizarBotoes()`, e a utility PRECISA sair: `lg:hidden` mora numa
        // media query emitida depois de `.inline-flex`, então ela venceria o JS no desktop e o botão
        // nunca reapareceria ao recolher o menu.
        if (btnAbrir) btnAbrir.classList.remove('lg:hidden');

        aplicarLargura(larguraSalva());

        if (ehMobile()) {
            fechar();
        } else if (store && store.getItem(CHAVE_FECHADO) === 'true') {
            fechar();
        } else {
            abrir();
        }

        if (btnAbrir) btnAbrir.addEventListener('click', alternar);
        if (btnFechar) btnFechar.addEventListener('click', fechar);
        if (overlay) overlay.addEventListener('click', fechar);

        if (btn3d) {
            btn3d.addEventListener('click', function () {
                var raiz = (window.gestor && window.gestor.raiz) ? window.gestor.raiz : '/';
                window.location.href = raiz + 'dashboard/?dashboard3d=sim';
            });
        }

        window.addEventListener('resize', function () {
            if (ehMobile() || estaFechado()) {
                recuarConteudo(0);
                aplicarLarguraConteudo(true);
            } else {
                recuarConteudo(larguraSalva());
                aplicarLarguraConteudo(false);
            }
        });

        // ===== Redimensionamento por arraste

        if (handle) {
            var arrastando = false;
            var xInicial = 0;
            var larguraInicial = 0;

            function posicaoX(evento) {
                if (evento.touches && evento.touches.length) return evento.touches[0].clientX;
                return evento.clientX;
            }

            function mover(evento) {
                if (!arrastando) return;
                // Sem o preventDefault o Chrome inicia o drag nativo do elemento e o arraste "trava".
                if (evento.cancelable) evento.preventDefault();
                aplicarLargura(larguraInicial + (posicaoX(evento) - xInicial));
            }

            function soltar() {
                if (!arrastando) return;
                arrastando = false;
                document.body.classList.remove('c2f-admin-redimensionando');
                if (store) store.setItem(CHAVE_LARGURA, String(sidebar.offsetWidth));
            }

            function pegar(evento) {
                if (ehMobile()) return;
                arrastando = true;
                xInicial = posicaoX(evento);
                larguraInicial = sidebar.offsetWidth;
                document.body.classList.add('c2f-admin-redimensionando');
                if (evento.cancelable) evento.preventDefault();
            }

            handle.addEventListener('mousedown', pegar);
            handle.addEventListener('touchstart', pegar, { passive: false });
            document.addEventListener('mousemove', mover);
            document.addEventListener('touchmove', mover, { passive: false });
            document.addEventListener('mouseup', soltar);
            document.addEventListener('touchend', soltar);

            // Duplo clique devolve a largura de fábrica — atalho herdado do menu legado.
            handle.addEventListener('dblclick', function () {
                if (store) store.setItem(CHAVE_LARGURA, String(larguraPadrao));
                aplicarLargura(larguraPadrao);
            });
        }

        // ===== Filtro de módulos

        var filtro = document.getElementById('gestor-menu-filtro');

        if (filtro) {
            var vazio = shell.querySelector('[data-menu-vazio]');
            var itens = Array.prototype.slice.call(shell.querySelectorAll('[data-menu-item]'));
            var grupos = Array.prototype.slice.call(shell.querySelectorAll('[data-menu-grupo]'));

            function aplicarFiltro() {
                var termo = normalizar(filtro.value).trim();
                var visiveis = 0;

                itens.forEach(function (item) {
                    var rotuloEl = item.querySelector('[data-menu-item-nome]');
                    var rotulo = normalizar(rotuloEl ? rotuloEl.textContent : item.textContent);
                    var casa = (termo === '' || rotulo.indexOf(termo) !== -1);

                    item.classList.toggle('hidden', !casa);
                    if (casa) visiveis++;
                });

                // O bloco do grupo some junto com o cabeçalho: título de categoria sozinho na tela
                // parece um grupo vazio, não um resultado filtrado.
                grupos.forEach(function (grupo) {
                    var algum = grupo.querySelector('[data-menu-item]:not(.hidden)');
                    grupo.classList.toggle('hidden', !algum);
                });

                if (vazio) vazio.classList.toggle('hidden', visiveis > 0);
            }

            filtro.addEventListener('input', aplicarFiltro);

            filtro.addEventListener('keydown', function (evento) {
                if (evento.key === 'Escape') {
                    filtro.value = '';
                    aplicarFiltro();
                    return;
                }

                if (evento.key === 'ArrowDown') {
                    evento.preventDefault();
                    var primeiro = shell.querySelector('[data-menu-item]:not(.hidden)');
                    if (primeiro) primeiro.focus();
                }
            });

            // Navegação por teclado entre os resultados (contrato do BATCH-105): sem ciclo no fim e
            // com retorno ao campo quando se sobe além do primeiro.
            shell.addEventListener('keydown', function (evento) {
                if (evento.key !== 'ArrowDown' && evento.key !== 'ArrowUp') return;

                var alvo = evento.target;
                if (!alvo || !alvo.hasAttribute || !alvo.hasAttribute('data-menu-item')) return;

                var visiveis = Array.prototype.slice.call(shell.querySelectorAll('[data-menu-item]:not(.hidden)'));
                var indice = visiveis.indexOf(alvo);
                if (indice < 0) return;

                evento.preventDefault();

                if (evento.key === 'ArrowDown') {
                    if (indice + 1 < visiveis.length) visiveis[indice + 1].focus();
                } else if (indice === 0) {
                    filtro.focus();
                } else {
                    visiveis[indice - 1].focus();
                }
            });

            aplicarFiltro();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }

    window.gestorAdminTailwind = { iniciar: iniciar };
})();
