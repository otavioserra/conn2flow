/**
 * publisher-index.widget.js — comportamento público do widget Publicador Índice (req-028 / DEC-041).
 *
 * Incluído no site quando uma página renderiza o widget `publisher-index`. Para cada
 * contêiner `.conn2flow-publisher-index`, gerencia a interface dinâmica de listagem:
 *   - Busca textual com debounce (300ms) em `.publisher-index-search`.
 *   - Ordenação em tempo de execução em `.publisher-index-sort`.
 *   - Paginação "Carregar Mais" em `.publisher-index-load-more` (append incremental).
 *
 * As requisições são roteadas para o próprio endereço da página (window.location.href)
 * acionando o roteador de widgets do gestor via `ajax=sim` + `ajaxWidgets`. O backend
 * (publisher_index_render_ajax) devolve `{ status, html, tem_mais }`.
 */
$(document).ready(function () {
    // Detecta se está em um iframe e se a URL atual é 'about:srcdoc'
    let targetUrl = window.location.href;
    let isInIframe = false;
    if (window.self !== window.parent && targetUrl === 'about:srcdoc') {
        isInIframe = true;
        try {
            // Usa a URL do pai (localhost)
            targetUrl = window.parent.location.href;
        } catch (e) {
            // Fallback de segurança caso o acesso ao pai seja bloqueado
            targetUrl = '/';
        }
    }

    function preInitPublisherIndex() {
        $('.conn2flow-publisher-index').each(function () {
            initPublisherIndex(this);
        });
    }

    if (isInIframe) {
        // Se estiver em um iframe, dispara um setTimeout para garantir que o conteúdo do iframe esteja totalmente carregado antes de iniciar o widget.
        setTimeout(function () {
            preInitPublisherIndex();
        }, 500);
    } else {
        // Se não estiver em um iframe, inicia imediatamente
        preInitPublisherIndex();
    }

    function initPublisherIndex(el) {
        var $root = $(el);
        if ($root.data('c2fIndexReady')) return; // evita dupla inicialização
        $root.data('c2fIndexReady', true);

        var grupoSlug = $root.attr('data-grupo-slug') || '';
        var ordenacaoInicial = $root.attr('data-ordenacao') || 'date_desc';

        var estado = {
            pagina: 1,
            busca: '',
            ordenacao: ordenacaoInicial
        };

        var $items = $root.find('.publisher-index-items').first();
        var $search = $root.find('.publisher-index-search').first();
        var $sort = $root.find('.publisher-index-sort').first();
        var $loadMore = $root.find('.publisher-index-load-more').first();

        var carregando = false;
        var debounceTimer = null;

        // req-041 §1.4: atualiza os contadores de métricas "Exibindo X de Y". O page_count vem da
        // contagem física dos itens renderizados na listagem (cresce no "Carregar mais"); o
        // page_total vem do backend (total de publicações casadas com a busca atual).
        function atualizarMetricas(total) {
            // Conta os filhos reais da listagem, ignorando o bloco vazio (no-item).
            var count = $items.children().not('.publisher-index-empty').length;
            $root.find('[data-page-count]').text(count);
            if (typeof total !== 'undefined' && total !== null && !isNaN(parseInt(total, 10))) {
                $root.find('[data-page-total]').text(parseInt(total, 10));
            }
        }

        // ===== Busca (debounce de 300ms): reseta a página e substitui a lista.
        if ($search.length) {
            $search.on('input', function () {
                var valor = $(this).val() || '';
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    estado.busca = valor;
                    estado.pagina = 1;
                    requisitar(true);
                }, 300);
            });
        }

        // ===== Ordenação: reseta a página e substitui a lista.
        if ($sort.length) {
            $sort.val(ordenacaoInicial);
            $sort.on('change', function () {
                estado.ordenacao = $(this).val() || 'date_desc';
                estado.pagina = 1;
                requisitar(true);
            });
        }

        // ===== Carregar mais: incrementa a página e anexa os novos itens.
        if ($loadMore.length) {
            $loadMore.on('click', function (e) {
                e.preventDefault();
                if (carregando) return;
                estado.pagina += 1;
                requisitar(false);
            });
        }

        function requisitar(substituir) {
            if (carregando) return;
            carregando = true;

            var widgetsToAjax = (typeof gestor !== 'undefined' && gestor.widgetsToAjax) ? gestor.widgetsToAjax : '';

            var requestData = 'ajax=sim' +
                '&ajaxOpcao=publisher-index-load' +
                '&ajaxRegistroId=' + encodeURIComponent(grupoSlug) +
                (widgetsToAjax ? '&ajaxWidgets=' + encodeURIComponent(widgetsToAjax) : '') +
                '&params%5Bbusca%5D=' + encodeURIComponent(estado.busca) +
                '&params%5Bpagina%5D=' + encodeURIComponent(estado.pagina) +
                '&params%5Bordenacao%5D=' + encodeURIComponent(estado.ordenacao);

            if ($loadMore.length) $loadMore.addClass('loading');

            $.ajax({
                url: targetUrl,
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: function (res) {
                    if (res && res.status === 'Ok') {
                        if (substituir) {
                            $items.html(res.html || '');
                        } else {
                            $items.append(res.html || '');
                        }

                        if (res.tem_mais) {
                            $loadMore.removeClass('hidden').show();
                        } else {
                            $loadMore.addClass('hidden').hide();
                        }

                        // req-041 §1.4: refletir as métricas "Exibindo X de Y" após nova busca,
                        // ordenação ou "Carregar mais".
                        atualizarMetricas(res.total);
                    }
                },
                complete: function () {
                    carregando = false;
                    if ($loadMore.length) $loadMore.removeClass('loading');
                }
            });
        }
    }
});
