/** Listagem do painel (req-220): servidor pagina/ordena/busca; sem jQuery/DataTables. */
(function (global) {
    'use strict';

    // Clique simples: só esta coluna (asc, depois desc). Com Ctrl/⌘/Shift: soma a coluna às outras;
    // na que já está na lista, asc → desc → sai.
    function proximaOrdem(ordem, indice, somar) {
        var atual = ordem.find(function (o) { return Number(o[0]) === indice; });
        if (!somar) return [[indice, atual && atual[1] === 'asc' ? 'desc' : 'asc']];
        if (!atual) return ordem.concat([[indice, 'asc']]);
        if (atual[1] === 'asc') return ordem.map(function (o) { return Number(o[0]) === indice ? [indice, 'desc'] : o; });
        var resto = ordem.filter(function (o) { return Number(o[0]) !== indice; });
        return resto.length ? resto : [[indice, 'asc']];
    }

    function iniciar(raiz, config) {
        if (!raiz || raiz.c2fLista) return raiz && raiz.c2fLista;
        var estado = { inicio: Number(config.displayStart) || 0, quantidade: Number(config.pageLength) || 25,
            ordem: config.order || [], busca: '', total: 0, sequencia: 0, carregando: false };
        var mensagens = raiz.dataset;
        var timer, abortar;
        function q(seletor) { return raiz.querySelector(seletor); }
        function clonar(tipo) {
            var conteudo = q('template[data-lista-' + tipo + ']').content;
            return (conteudo.querySelector('th, td') || conteudo.firstElementChild).cloneNode(true);
        }
        var anterior = q('[data-lista-anterior]'), proxima = q('[data-lista-proxima]');
        var corpo = q('[data-lista-linhas]'), cabecalho = q('[data-lista-colunas]');
        var colunas = config.columns.map(function (coluna, i) { return { coluna: coluna, indice: i }; })
            .filter(function (item) { return item.coluna.visible !== false && (item.indice !== 0 || Object.keys(config.opcoes || {}).length); });

        function urlAcao(opcao, id) {
            var base = new URL(config.url, new URL(global.gestor.raiz, global.location.href));
            var url = new URL(opcao.url || '', base);
            if (url.origin !== global.location.origin || !/^https?:$/.test(url.protocol)) return null;
            // The interface routes receive `id`, even when the SQL primary key has another name.
            url.searchParams.set('id', id);
            if (!opcao.url) url.searchParams.set('opcao', opcao.opcao);
            if (opcao.opcao === 'status') url.searchParams.set('status', opcao.status_mudar);
            if (opcao.opcao === 'excluir' || opcao.opcao === 'status') {
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || global.gestor.csrfToken;
                if (!token) return null;
                url.searchParams.set('_csrf_token', token);
            }
            return url.href;
        }

        // req-219: a cor de cada opção (`cor` no PHP, vocabulário do Fomantic: "basic blue", "red"…) vira
        // classe `c2fc-cor-*` de controles.css; excluir sem cor declarada fica vermelho.
        var CORES = ['blue', 'teal', 'green', 'olive', 'yellow', 'orange', 'brown', 'red', 'pink', 'violet', 'purple', 'grey', 'black'];
        function corDe(opcao, excluir) {
            var palavras = String(opcao.cor || '').toLowerCase().split(/\s+/);
            var cor = CORES.filter(function (c) { return palavras.indexOf(c) !== -1; })[0];
            return cor || (excluir ? 'red' : 'blue');
        }

        // req-219: HTML de formatador declarado no servidor (ex.: `encapsular` do caminho no admin-paginas).
        // Nada de atributos ou eventos do HTML recebido: só o texto, links para o mesmo site (rótulo-link) e
        // rótulos/textos do Fomantic (`ui label`, `ui … text`) traduzidos para classes da biblioteca.
        function celulaSegura(destino, html) {
            var modelo = document.createElement('template');
            modelo.innerHTML = String(html == null ? '' : html);
            (function copiar(origem, alvo) {
                Array.prototype.forEach.call(origem.childNodes, function (no) {
                    if (no.nodeType === 3) { alvo.appendChild(document.createTextNode(no.nodeValue)); return; }
                    if (no.nodeType !== 1) return;
                    var tag = no.tagName.toLowerCase();
                    var classes = ' ' + (no.getAttribute('class') || '') + ' ';
                    var novo = null;
                    if (tag === 'a') {
                        try {
                            var url = new URL(no.getAttribute('href') || '', global.location.href);
                            if (url.origin === global.location.origin && /^https?:$/.test(url.protocol)) {
                                novo = document.createElement('a');
                                novo.href = url.href;
                                novo.className = 'c2fc-rotulo';
                            }
                        } catch (e) { novo = null; }
                    } else if (/ (label|c2fc-rotulo) /.test(classes)) {
                        // a cor passa só se for da paleta (`ui green label` ou `c2fc-rotulo c2fc-cor-green`)
                        var cor = (classes.match(/ (?:c2fc-cor-)?(red|orange|yellow|olive|green|teal|blue|violet|purple|pink|brown|grey|black) /) || [])[1];
                        novo = document.createElement('span');
                        novo.className = 'c2fc-rotulo' + (cor ? ' c2fc-cor-' + cor : '');
                    } else if (/ text /.test(classes)) {
                        novo = document.createElement('span');
                        novo.className = 'c2fc-texto-suave';
                    }
                    if (novo) { novo.textContent = no.textContent; alvo.appendChild(novo); }
                    else copiar(no, alvo);
                });
            })(modelo.content, destino);
        }

        function acoes(linha) {
            var grupo = clonar('acoes');
            Object.keys(config.opcoes || {}).forEach(function (chave) {
                var opcao = config.opcoes[chave];
                if (opcao.opcao === 'status' && opcao.status_atual !== linha[config.status]) return;
                var excluir = opcao.opcao === 'excluir', botao = clonar(excluir ? 'excluir' : 'acao');
                var url = urlAcao(opcao, linha[config.acoesId]);
                if (!url) return;
                botao.className = 'c2fc-acao c2fc-cor-' + corDe(opcao, excluir);
                // dica da biblioteca (data-tooltip + posição, como no Fomantic), não o title nativo
                if (opcao.tooltip) { botao.setAttribute('data-tooltip', opcao.tooltip); botao.setAttribute('data-position', 'top left'); }
                botao.setAttribute('aria-label', opcao.tooltip || '');
                botao.dataset.listaAcao = chave;
                var icone = document.createElement('i');
                icone.setAttribute('data-lucide', opcao.lucide || 'circle');
                icone.setAttribute('aria-hidden', 'true');
                botao.appendChild(icone);
                if (!excluir) botao.href = url;
                else botao.addEventListener('click', async function () {
                    if (botao.disabled) return;
                    botao.disabled = true;
                    try {
                        var sim = await global.c2fControles.dialogo.confirmar(mensagens.mensagemExcluir,
                            { perigo: true, titulo: mensagens.tituloExcluir, ok: mensagens.confirmar, cancelar: mensagens.cancelar });
                        if (sim) {
                            // Token lido novamente depois do diálogo (pode ter sido renovado).
                            var destino = urlAcao(opcao, linha[config.acoesId]);
                            if (destino) global.location.assign(destino);
                        }
                    } finally { botao.disabled = false; }
                });
                grupo.appendChild(botao);
            });
            return grupo;
        }

        function atualizarPaginacao() {
            anterior.disabled = estado.carregando || estado.inicio === 0;
            proxima.disabled = estado.carregando || estado.inicio + estado.quantidade >= estado.total;
        }

        function renderizar(dados) {
            estado.total = Number(dados.recordsFiltered) || 0;
            corpo.replaceChildren();
            (dados.data || []).forEach(function (linha) {
                var tr = document.createElement('tr');
                colunas.forEach(function (item) {
                    var td = clonar('td');
                    if (item.indice === 0) td.appendChild(acoes(linha));
                    else {
                        var valor = linha[item.coluna.data];
                        // Formatadores legados podem envolver rótulos em HTML. A listagem nova exibe
                        // seu texto, sem interpretar atributos/eventos ou HTML de dados do usuário.
                        if (item.coluna.html) celulaSegura(td, valor);
                        else td.textContent = valor == null ? '' : String(valor);
                    }
                    tr.appendChild(td);
                });
                corpo.appendChild(tr);
            });
            q('[data-lista-mensagem]').textContent = dados.data.length ? '' : mensagens.vazia;
            q('[data-lista-resumo]').textContent = mensagens.resumo
                .replace('{inicio}', estado.total ? estado.inicio + 1 : 0)
                .replace('{fim}', Math.min(estado.inicio + dados.data.length, estado.total)).replace('{total}', estado.total);
            desenharOrdem();
            if (global.lucide) global.lucide.createIcons({ root: raiz });
        }

        // Seta e, com mais de uma coluna, a prioridade (↑1, ↓2…). Coluna sem ordem fica vazia; o CSS mostra
        // uma seta fraca no hover para indicar que dá para ordenar por ela.
        function desenharOrdem() {
            colunas.forEach(function (item, i) {
                var posicao = estado.ordem.findIndex(function (o) { return Number(o[0]) === item.indice; });
                var ordem = posicao >= 0 ? estado.ordem[posicao] : null;
                cabecalho.children[i].setAttribute('aria-sort', ordem ? (ordem[1] === 'asc' ? 'ascending' : 'descending') : 'none');
                cabecalho.children[i].querySelector('[data-lista-direcao]').textContent = ordem
                    ? (ordem[1] === 'asc' ? '↑' : '↓') + (estado.ordem.length > 1 ? String(posicao + 1) : '') : '';
            });
        }

        async function carregar() {
            var sequencia = ++estado.sequencia;
            if (abortar) abortar.abort();
            abortar = new AbortController();
            estado.carregando = true;
            raiz.setAttribute('aria-busy', 'true');
            atualizarPaginacao();
            q('[data-lista-mensagem]').textContent = mensagens.carregando;
            var params = new URLSearchParams({ ajax: 'sim', opcao: 'listar', ajaxOpcao: 'listar', draw: sequencia,
                start: estado.inicio, length: estado.quantidade, 'search[value]': estado.busca });
            estado.ordem.forEach(function (ordem, i) {
                params.set('order[' + i + '][column]', ordem[0]);
                params.set('order[' + i + '][dir]', ordem[1]);
            });
            try {
                var resposta = await global.fetch(new URL(config.url, new URL(global.gestor.raiz, global.location.href)).href,
                    { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params, signal: abortar.signal });
                if (sequencia !== estado.sequencia) return;
                if (resposta.status === 401) { global.location.assign(new URL('signin/', new URL(global.gestor.raiz, global.location.href)).href); return; }
                if (!resposta.ok) throw new Error('http');
                var dados = await resposta.json();
                if (sequencia !== estado.sequencia) return;
                // Listagem usa o envelope DataTables do endpoint existente, não `status: Ok`.
                if (!dados || !Array.isArray(dados.data) || !Number.isFinite(Number(dados.recordsFiltered))) throw new Error('response');
                if (estado.inicio > 0 && estado.inicio >= Number(dados.recordsFiltered)) { estado.inicio = 0; return carregar(); }
                renderizar(dados);
            } catch (erro) {
                if (sequencia === estado.sequencia && erro.name !== 'AbortError') q('[data-lista-mensagem]').textContent = mensagens.erro;
            } finally {
                if (sequencia === estado.sequencia) {
                    estado.carregando = false;
                    raiz.setAttribute('aria-busy', 'false');
                    atualizarPaginacao();
                }
            }
        }

        colunas.forEach(function (item) {
            var th = clonar('th'), botao = th.querySelector('button');
            botao.querySelector('span').textContent = item.indice === 0 ? mensagens.opcoes : item.coluna.name;
            botao.disabled = item.coluna.orderable === false;
            botao.addEventListener('click', function (evento) {
                estado.ordem = proximaOrdem(estado.ordem, item.indice, evento.ctrlKey || evento.metaKey || evento.shiftKey);
                estado.inicio = 0; desenharOrdem(); carregar();
            });
            cabecalho.appendChild(th);
        });
        function buscar() { clearTimeout(timer); estado.busca = q('[data-lista-busca]').value; estado.inicio = 0; carregar(); }
        q('[data-lista-busca]').addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(buscar, 300); });
        q('[data-lista-busca]').addEventListener('keydown', function (evento) { if (evento.key === 'Enter') { evento.preventDefault(); buscar(); } });
        q('[data-lista-quantidade]').value = String(estado.quantidade);
        // o select de quantidade pode já ter virado controle da biblioteca: relê o valor nativo
        var ctlQuantidade = global.c2fControles && typeof global.c2fControles.de === 'function' && global.c2fControles.de(q('[data-lista-quantidade]'));
        if (ctlQuantidade) ctlQuantidade.atualizar();
        q('[data-lista-quantidade]').addEventListener('change', function () { estado.quantidade = Number(this.value); estado.inicio = 0; carregar(); });
        anterior.addEventListener('click', function () { estado.inicio = Math.max(0, estado.inicio - estado.quantidade); carregar(); });
        proxima.addEventListener('click', function () { estado.inicio += estado.quantidade; carregar(); });
        raiz.c2fLista = { carregar: carregar, estado: estado };
        carregar();
        return raiz.c2fLista;
    }

    global.c2fListaTailwind = { iniciar: iniciar, proximaOrdem: proximaOrdem };
    function boot() {
        var config = global.gestor && global.gestor.interface && global.gestor.interface.lista;
        if (config) iniciar(document.querySelector('[data-c2f-listar]'), config);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})(window);
