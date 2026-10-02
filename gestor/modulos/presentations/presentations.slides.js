/**
 * presentations.slides.js — quadro de slides da tela de adicionar, editar e clonar (req-209).
 *
 * O deck é um HTML só: cada `<section data-slide>` é um slide. O quadro lê essas seções do editor,
 * mostra um cartão por slide e reescreve o HTML a cada operação: incluir (em HTML ou de imagem),
 * editar, duplicar, reordenar (arrastando ou pelas setas) e excluir. Como o conteúdo continua no
 * editor, o editor visual, a IA e o CSS compilado valem para todos os slides.
 *
 * As seções são localizadas no texto, sem passar o HTML por um analisador: reescrever o documento
 * inteiro mudaria comentários de bloco, variáveis `[[...]]` e a formatação que o autor escreveu.
 *
 * `window.c2fSlidesBoard` expõe `parse` e `build` para teste.
 */
(function () {
    'use strict';

    // ===== Leitura das seções

    /** Posições de todos os comentários, para ignorar marcação citada dentro deles. */
    function comentarios(html) {
        var faixas = [];
        var re = /<!--[\s\S]*?-->/g;
        var m;
        while ((m = re.exec(html)) !== null) faixas.push([m.index, m.index + m[0].length]);
        return faixas;
    }

    function dentro(faixas, pos) {
        for (var i = 0; i < faixas.length; i++) {
            if (pos >= faixas[i][0] && pos < faixas[i][1]) return true;
        }
        return false;
    }

    function atributo(tag, nome) {
        var m = new RegExp('\\s' + nome + '\\s*=\\s*"([^"]*)"', 'i').exec(tag);
        return m ? m[1] : null;
    }

    function temAtributo(tag, nome) {
        return new RegExp('\\s' + nome + '(?=[\\s=>/])', 'i').test(tag);
    }

    /**
     * Slides do HTML: seções de primeiro nível com `data-slide`. Cada item traz as posições no texto,
     * a tag de abertura e o miolo. Seção sem fechamento é ignorada (HTML em edição, incompleto).
     */
    function parse(html) {
        html = String(html || '');
        var faixas = comentarios(html);
        var re = /<section\b[^>]*>|<\/section\s*>/gi;
        var pilha = [];
        var slides = [];
        var m;
        while ((m = re.exec(html)) !== null) {
            if (dentro(faixas, m.index)) continue;
            if (m[0].charAt(1) !== '/') {
                pilha.push({ start: m.index, open: m[0] });
            } else if (pilha.length) {
                var ini = pilha.pop();
                // Só a seção que não está dentro de outro slide conta.
                var aninhada = pilha.some(function (p) { return temAtributo(p.open, 'data-slide'); });
                if (temAtributo(ini.open, 'data-slide') && !aninhada) {
                    slides.push({
                        start: ini.start,
                        end: m.index + m[0].length,
                        open: ini.open,
                        inner: html.substring(ini.start + ini.open.length, m.index)
                    });
                }
            }
        }
        slides.sort(function (a, b) { return a.start - b.start; });
        return slides;
    }

    function semTags(texto) {
        return String(texto || '').replace(/<!--[\s\S]*?-->/g, ' ').replace(/<[^>]+>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function descrever(slide) {
        var tipo = atributo(slide.open, 'data-slide-type') === 'image' ? 'image' : 'html';
        var img = /<img\b[^>]*>/i.exec(slide.inner);
        var titulo = atributo(slide.open, 'data-title');
        if (!titulo) {
            var h = /<h[1-6]\b[^>]*>([\s\S]*?)<\/h[1-6]>/i.exec(slide.inner);
            titulo = h ? semTags(h[1]) : (img ? (atributo(img[0], 'alt') || '') : semTags(slide.inner).substring(0, 60));
        }
        return {
            type: tipo,
            title: titulo || '',
            classes: atributo(slide.open, 'class') || '',
            src: img ? (atributo(img[0], 'src') || '') : '',
            alt: img ? (atributo(img[0], 'alt') || '') : '',
            fit: img && atributo(img[0], 'data-fit') === 'cover' ? 'cover' : 'contain',
            inner: slide.inner
        };
    }

    // ===== Escrita

    function esc(valor) {
        return String(valor === null || typeof valor === 'undefined' ? '' : valor).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /** HTML de um slide a partir dos dados do cartão. O miolo do slide em HTML passa como o autor escreveu. */
    function build(dados) {
        var titulo = dados.title ? ' data-title="' + esc(dados.title) + '"' : '';
        if (dados.type === 'image') {
            return '<section data-slide data-slide-type="image"' + titulo + ' class="' + esc(dados.classes || '') + '">\n'
                + '            <img src="' + esc(dados.src) + '" alt="' + esc(dados.alt || '') + '" class="c2f-slide-image" data-fit="' + (dados.fit === 'cover' ? 'cover' : 'contain') + '">\n'
                + '        </section>';
        }
        return '<section data-slide' + titulo + ' class="' + esc(dados.classes || '') + '">' + dados.inner + '</section>';
    }

    function trocar(html, slide, novo) {
        return html.substring(0, slide.start) + novo + html.substring(slide.end);
    }

    /** Onde entra um slide novo: depois do último; sem slides, no início do palco. */
    function inserir(html, novo) {
        var slides = parse(html);
        if (slides.length) {
            var ultimo = slides[slides.length - 1];
            return html.substring(0, ultimo.end) + '\n\n        ' + novo + html.substring(ultimo.end);
        }
        var palco = /<[a-z0-9]+\b[^>]*\sdata-c2f-deck-stage(?=[\s=>/])[^>]*>/i.exec(html);
        if (!palco) return null;
        var pos = palco.index + palco[0].length;
        return html.substring(0, pos) + '\n\n        ' + novo + '\n' + html.substring(pos);
    }

    function reordenar(html, ordem) {
        var slides = parse(html);
        if (ordem.length !== slides.length) return html;
        var textos = slides.map(function (s) { return html.substring(s.start, s.end); });
        var saida = '';
        var cursor = 0;
        slides.forEach(function (s, i) {
            saida += html.substring(cursor, s.start) + textos[ordem[i]];
            cursor = s.end;
        });
        return saida + html.substring(cursor);
    }

    window.c2fSlidesBoard = { parse: parse, build: build, describe: descrever, insert: inserir, reorder: reordenar };

    // ===== Tela

    if (typeof $ === 'undefined') return;

    $(document).ready(function () {
        var $quadro = $('#slides-board');
        if ($quadro.length === 0) return;

        var cfg = (typeof gestor !== 'undefined' && gestor.presentationsAdmin) ? gestor.presentationsAdmin : {};
        var textos = cfg.textos || {};
        var imagepick = cfg.imagepick || {};
        var sortable = null;
        var ultimoHtml = null;
        var alvoImagem = null;     // null: imagem escolhida vira slide novo; função: troca a imagem do slide em edição

        function t(id) { return textos[id] || ''; }

        function htmlAtual() {
            return (typeof window.html_editor_get_html === 'function') ? (window.html_editor_get_html() || '') : '';
        }

        function aplicar(html) {
            if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(html);
            ultimoHtml = null;
            desenhar();
            if (typeof window.updatedCodeMirrorHtml === 'function') window.updatedCodeMirrorHtml();
        }

        function aviso(mensagem) {
            var $a = $('#slides-board-message');
            if (!mensagem) { $a.addClass('hidden').text(''); return; }
            $a.removeClass('hidden').text(mensagem);
        }

        function raiz() {
            return (typeof gestor !== 'undefined' && gestor.raiz) ? String(gestor.raiz) : '/';
        }

        // ----- Cartões

        function desenhar() {
            var html = htmlAtual();
            if (html === ultimoHtml) return;
            ultimoHtml = html;

            var slides = parse(html);
            $quadro.empty();
            $('#slides-count').text(t('js-slide-count').replace('%n', String(slides.length)));

            if (!slides.length) {
                $quadro.append($('<div class="c2f-slides-empty"></div>').text(t('js-slide-empty')));
                return;
            }

            slides.forEach(function (slide, i) {
                var d = descrever(slide);
                var $cartao = $('<div class="c2f-slide-card"></div>').attr('data-index', i);
                var $miniatura = $('<div class="c2f-slide-thumb"></div>');
                if (d.type === 'image' && d.src) $miniatura.append($('<img alt="">').attr('src', d.src));
                else $miniatura.append($('<span></span>').text(semTags(d.inner).substring(0, 110)));
                $cartao.append(
                    $('<div class="c2f-slide-head"></div>').append(
                        $('<span class="c2f-slide-number"></span>').text(String(i + 1)),
                        $('<span class="ui mini label"></span>').addClass(d.type === 'image' ? 'blue' : 'teal').text(t(d.type === 'image' ? 'js-slide-type-image' : 'js-slide-type-html')),
                        $('<i class="grip vertical icon c2f-slide-handle"></i>')
                    ),
                    $miniatura,
                    $('<div class="c2f-slide-title"></div>').text(d.title || t('js-slide-untitled')),
                    $('<div class="c2f-slide-actions ui mini icon buttons"></div>').append(
                        $('<button type="button" class="ui button" data-slide-action="left"><i class="arrow left icon"></i></button>').attr('title', t('js-slide-move-left')).prop('disabled', i === 0),
                        $('<button type="button" class="ui button" data-slide-action="right"><i class="arrow right icon"></i></button>').attr('title', t('js-slide-move-right')).prop('disabled', i === slides.length - 1),
                        $('<button type="button" class="ui blue button" data-slide-action="edit"><i class="edit icon"></i></button>').attr('title', t('js-slide-edit')),
                        $('<button type="button" class="ui teal button" data-slide-action="duplicate"><i class="clone icon"></i></button>').attr('title', t('js-slide-duplicate')),
                        $('<button type="button" class="ui red button" data-slide-action="delete"><i class="trash icon"></i></button>').attr('title', t('js-slide-delete'))
                    )
                );
                $quadro.append($cartao);
            });

            if (!sortable && typeof Sortable !== 'undefined') {
                sortable = new Sortable($quadro.get(0), {
                    animation: 150,
                    handle: '.c2f-slide-handle',
                    draggable: '.c2f-slide-card',
                    onEnd: function () {
                        var ordem = $quadro.children('.c2f-slide-card').map(function () { return parseInt($(this).attr('data-index'), 10); }).get();
                        aplicar(reordenar(htmlAtual(), ordem));
                    }
                });
            }
        }

        function mover(indice, passo) {
            var total = parse(htmlAtual()).length;
            var destino = indice + passo;
            if (destino < 0 || destino >= total) return;
            var ordem = [];
            for (var i = 0; i < total; i++) ordem.push(i);
            ordem[indice] = destino;
            ordem[destino] = indice;
            aplicar(reordenar(htmlAtual(), ordem));
        }

        $quadro.on('click', '[data-slide-action]', function () {
            var $botao = $(this);
            var indice = parseInt($botao.closest('.c2f-slide-card').attr('data-index'), 10);
            var html = htmlAtual();
            var slide = parse(html)[indice];
            if (!slide) return;

            switch ($botao.attr('data-slide-action')) {
                case 'left': mover(indice, -1); break;
                case 'right': mover(indice, 1); break;
                case 'edit': editar(indice); break;
                case 'duplicate':
                    aplicar(html.substring(0, slide.end) + '\n\n        ' + html.substring(slide.start, slide.end) + html.substring(slide.end));
                    break;
                case 'delete':
                    // Dois cliques: o primeiro arma, o segundo exclui. Passados três segundos, desarma.
                    if (!$botao.data('armado')) {
                        $botao.data('armado', true).addClass('inverted').attr('title', t('js-slide-delete-confirm')).html('<i class="check icon"></i>');
                        setTimeout(function () { ultimoHtml = null; desenhar(); }, 3000);
                        return;
                    }
                    // Leva junto o espaço em branco antes do slide, para não sobrar linha vazia a cada exclusão.
                    var antes = html.substring(0, slide.start).replace(/[ \t]*\n?[ \t]*$/, '');
                    aplicar(antes + html.substring(slide.end));
                    break;
            }
        });

        // ----- Inclusão

        $('#btn-slide-add-html').on('click', function () {
            var novo = build({
                type: 'html', title: t('js-slide-new-title'), classes: 'flex-col items-center justify-center text-center p-6 md:p-12',
                inner: '\n            <div class="w-full max-w-5xl mx-auto">\n'
                    + '                <h2 class="text-4xl md:text-5xl font-black text-white">' + esc(t('js-slide-new-heading')) + '</h2>\n'
                    + '                <p class="mt-6 text-xl text-gray-300">' + esc(t('js-slide-new-text')) + '</p>\n'
                    + '            </div>\n        '
            });
            var html = inserir(htmlAtual(), novo);
            if (html === null) { aviso(t('js-slide-no-stage')); return; }
            aviso('');
            aplicar(html);
        });

        function abrirSeletor(destino) {
            var $modal = $('.ui.modal.iframePagina');
            if ($modal.length === 0 || !imagepick.url) { aviso(t('js-slide-modal-unavailable')); return; }
            alvoImagem = destino || null;
            if (imagepick.head) $modal.find('.header').html(imagepick.head);
            if (imagepick.cancel) $modal.find('.cancel.button').html(imagepick.cancel);
            $modal.find('iframe').attr('src', imagepick.url);
            $modal.modal({ allowMultiple: true }).modal('show');
        }

        $('#btn-slide-add-image').on('click', function () { abrirSeletor(null); });

        // Imagem escolhida no gerenciador de arquivos. Em inclusão o modal fica aberto, para escolher várias.
        window.addEventListener('message', function (e) {
            var data;
            try { data = JSON.parse(e.data); } catch (err) { return; }
            if (!data || (data.moduloId !== 'admin-arquivos' && data.moduloId !== 'arquivos')) return;
            var dados;
            try { dados = JSON.parse(decodeURI(data.data)); } catch (err) { return; }
            if (!dados || !dados.tipo || !/image\//.test(dados.tipo)) { aviso(t('js-slide-not-image')); return; }

            var imagem = { src: raiz() + String(dados.caminho || '').replace(/^\/+/, ''), nome: String(dados.nome || '') };
            if (typeof alvoImagem === 'function') {
                alvoImagem(imagem);
                $('.ui.modal.iframePagina').modal('hide');
                return;
            }
            var html = inserir(htmlAtual(), build({ type: 'image', title: imagem.nome, classes: '', src: imagem.src, alt: imagem.nome, fit: 'contain' }));
            if (html === null) { aviso(t('js-slide-no-stage')); return; }
            aviso('');
            aplicar(html);
        });

        // ----- Edição

        function campo(rotulo, $controle) {
            return $('<div class="field"></div>').append($('<label></label>').text(rotulo), $controle);
        }

        function editar(indice) {
            var slide = parse(htmlAtual())[indice];
            if (!slide) return;
            var d = descrever(slide);

            var $titulo = $('<input type="text">').val(d.title);
            var $classes = $('<input type="text">').val(d.classes);
            var $form = $('<div class="ui form"></div>').append(campo(t('js-slide-field-title'), $titulo), campo(t('js-slide-field-classes'), $classes));
            var $html, $alt, $fit, $previa;
            var src = d.src;

            if (d.type === 'image') {
                $previa = $('<img alt="" class="c2f-slide-modal-image">').attr('src', src);
                $alt = $('<input type="text">').val(d.alt);
                $fit = $('<select class="ui dropdown"></select>').append(
                    $('<option value="contain"></option>').text(t('js-slide-fit-contain')),
                    $('<option value="cover"></option>').text(t('js-slide-fit-cover'))
                ).val(d.fit);
                $form.append(
                    campo(t('js-slide-field-image'), $('<div></div>').append($previa, $('<button type="button" class="ui button"></button>').text(t('js-slide-change-image')).on('click', function () {
                        abrirSeletor(function (imagem) {
                            src = imagem.src;
                            $previa.attr('src', src);
                            if (!$alt.val()) $alt.val(imagem.nome);
                        });
                    }))),
                    campo(t('js-slide-field-alt'), $alt),
                    campo(t('js-slide-field-fit'), $fit)
                );
            } else {
                $html = $('<textarea rows="16" class="c2f-slide-modal-code" spellcheck="false"></textarea>').val(d.inner);
                $form.append(campo(t('js-slide-field-html'), $html));
            }

            var $modal = $('<div class="ui large modal c2f-slide-modal"></div>').append(
                $('<div class="header"></div>').text(t('js-slide-modal-title') + ' ' + (indice + 1)),
                $('<div class="scrolling content"></div>').append($form),
                $('<div class="actions"></div>').append(
                    $('<button type="button" class="ui cancel button"></button>').text(t('js-slide-cancel')),
                    $('<button type="button" class="ui primary approve button"></button>').text(t('js-slide-save'))
                )
            );
            $('body').append($modal);
            $modal.modal({
                allowMultiple: true,
                closable: false,
                onApprove: function () {
                    var html = htmlAtual();
                    var atual = parse(html)[indice];
                    if (!atual) return true;
                    aplicar(trocar(html, atual, build({
                        type: d.type, title: $titulo.val(), classes: $classes.val(),
                        inner: $html ? $html.val() : '', src: src, alt: $alt ? $alt.val() : '', fit: $fit ? $fit.val() : 'contain'
                    })));
                    return true;
                },
                onHidden: function () { $modal.remove(); }
            }).modal('show');
        }

        // ----- O HTML também muda pelo editor, pela IA e pela troca de modelo.

        $(document).on('c2f:widget-html-changed', desenhar);
        setInterval(desenhar, 1500);
        setTimeout(desenhar, 600);
    });
})();
