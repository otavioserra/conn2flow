$(document).ready(function () {

    // req-109 §7: `gestor.moduloCaminho` já chega do backend com a barra final. Somar outra barra
    // gerava `admin-paginas/editar//`, rota que o gestor não resolve (403/404 no AJAX do editor).
    // Esta função é a fonte única da URL do módulo em todo o arquivo.
    function moduloUrl() {
        var url = String(gestor.raiz || '') + String(gestor.moduloCaminho || '').replace(/^\/+/, '');
        url = url.replace(/([^:])\/{2,}/g, '$1/');
        if (url.charAt(url.length - 1) !== '/') url += '/';
        return url;
    }

    window.htmlEditorModuloUrl = moduloUrl; // exposta para teste e para os painéis do editor.

    // ===== Ajax Default

    var ajaxDefault = {
        type: 'POST',
        url: moduloUrl(),
        ajaxOpcao: 'ajaxOpcao',
        data: {
            opcao: gestor.moduloOpcao,
            ajax: 'sim'
        },
        dataType: 'json',
        beforeSend: function () {
            loadDimmer(true);
            msg_erro_resetar();
        },
        success: function (dados) {
            switch (dados.status) {
                case 'Ok':
                    this.successCallback(dados);
                    break;
                default:
                    this.successNotOkCallback(dados);
                    console.log('ERROR - ' + this.ajaxOpcao + ' - ' + dados.status);

            }

            loadDimmer(false);
        },
        error: function (txt) {
            switch (txt.status) {
                case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                default:
                    console.log('ERROR AJAX - ' + this.ajaxOpcao + ' - Dados:');
                    console.log(txt);
                    loadDimmer(false);
            }
        },
        successCallback: function (response) { },
        successNotOkCallback: function (response) { }
    };

    // ===== Variáveis Globais
    let publisher_fields_schema = gestor.html_editor.publisher_fields_schema ?? {};

    // ===== Utilitários

    function cleanCodeString(str, type = 'html') {
        if (!str) return '';

        let lines = str.split('\n').filter(line => line.trim() !== '').map(l => l.trim());
        if (lines.length === 0) return '';

        const indentUnit = '    ';
        let formatted = '';
        let indentLevel = 0;



        if (type === 'html') {
            const voidTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr', '!doctype'];
            let inTagDefinition = false;
            let currentDefinitionTagName = '';

            lines.forEach(line => {
                let contentOnly = line.replace(/<!--[\s\S]*?-->/g, '');
                let safeLine = contentOnly.replace(/"[^"]*"/g, '""').replace(/'[^']*'/g, "''");

                let isClosingTagStart = contentOnly.trim().startsWith('</');


                let printIndent = indentLevel;
                if (isClosingTagStart) {
                    printIndent = Math.max(0, indentLevel - 1);
                } else if (inTagDefinition) {
                    // If the tag definition is split across lines:
                    // - For non-void tags (e.g. <div>), indentLevel was already incremented when <div was found.
                    //   Attributes should align with that new level (or just be indented once relative to parent).
                    //   Current indentLevel is parent+1. So we print at indentLevel.
                    // - For void tags (e.g. <img>), indentLevel was NOT incremented.
                    //   We want attributes indented relative to the tag. So indentLevel+1.

                    if (currentDefinitionTagName && !voidTags.includes(currentDefinitionTagName)) {
                        printIndent = indentLevel;
                    } else {
                        printIndent = indentLevel + 1;
                    }
                }

                formatted += indentUnit.repeat(printIndent) + line + '\n';

                // Logic Processing

                let processLine = safeLine;

                // If we were inside a definition, look for the closing >
                if (inTagDefinition) {
                    const closeIndex = safeLine.indexOf('>');
                    if (closeIndex > -1) {
                        inTagDefinition = false;

                        // Check if it was self-closing />
                        // We check safeLine at closeIndex-1
                        if (closeIndex > 0 && safeLine[closeIndex - 1] === '/') {
                            if (currentDefinitionTagName && !voidTags.includes(currentDefinitionTagName)) {
                                indentLevel = Math.max(0, indentLevel - 1);
                            }
                        }
                        currentDefinitionTagName = '';
                        // Process remaining content on this line
                        processLine = safeLine.substring(closeIndex + 1);
                    } else {
                        processLine = ''; // Still inside definition
                    }
                }

                // Scan processLine for new tags if any content is left
                if (processLine.length > 0) {
                    // 1. Open tags <tag
                    const openTagRegex = /<([a-zA-Z0-9-!]+)/g;
                    let match;
                    while ((match = openTagRegex.exec(processLine)) !== null) {
                        let tagName = match[1].toLowerCase();
                        if (!voidTags.includes(tagName) && !tagName.startsWith('!')) {
                            indentLevel++;
                        }
                    }

                    // 2. Closing tags </tag
                    const closeTagRegex = /<\/([a-zA-Z0-9-]+)/g;
                    let closeMatches = processLine.match(closeTagRegex) || [];
                    indentLevel -= closeMatches.length;

                    // 3. Self-closing correction <tag ... /> on same line
                    const selfClosingRegex = /<([a-zA-Z0-9-!]+)(?:[^>]*?)\/>/g;
                    while ((match = selfClosingRegex.exec(processLine)) !== null) {
                        let tagName = match[1].toLowerCase();
                        if (!voidTags.includes(tagName) && !tagName.startsWith('!')) {
                            indentLevel--;
                        }
                    }

                    // 4. Update inTagDefinition for next line
                    let lastOpen = processLine.lastIndexOf('<');
                    let lastClose = processLine.lastIndexOf('>');

                    if (lastOpen > lastClose) {
                        inTagDefinition = true;
                        let lastTagMatch = processLine.match(/<([a-zA-Z0-9-!]+)[^>]*$/);
                        if (lastTagMatch) {
                            currentDefinitionTagName = lastTagMatch[1].toLowerCase();
                        }
                    }
                }

                if (indentLevel < 0) indentLevel = 0;
            });

        } else if (type === 'css') {
            lines.forEach(line => {
                let printIndent = indentLevel;
                if (line.startsWith('}')) {
                    printIndent = Math.max(0, indentLevel - 1);
                }

                formatted += indentUnit.repeat(printIndent) + line + '\n';

                const openBraces = (line.match(/\{/g) || []).length;
                const closeBraces = (line.match(/\}/g) || []).length;

                indentLevel = Math.max(0, indentLevel + openBraces - closeBraces);
            });
        }

        return formatted.trim();
    }

    // Expor globalmente para uso em iframes e outros contextos
    window.cleanCodeString = cleanCodeString;

    // ===== Toggle Active Button

    function toggleActiveButton(obj = null) {
        if (typeof obj !== 'object' || obj === null) return false;
        if (!obj.hasClass('active')) {
            obj.parent().find('.button').removeClass('active');
            obj.addClass('active');

            return true;
        }

        return false;
    }

    // ===== Dimmer Loading

    function loadDimmer(show = true) {
        if (show) {
            $('#modelos-loading .dimmer').addClass('active');
        } else {
            $('#modelos-loading .dimmer').removeClass('active');
        }
    }

    // ===== Modelos de Páginas

    let modelos = {};
    let modelos_pagina = 1;
    let modelos_carregando = false;
    let modelos_tem_mais = false;

    function frameworkCSS() {
        const $framework = $('#framework-css');
        const framework_css = $framework.length ? $framework.parent().find('.menu').find('.item.active.selected').data('value') : null;
        const framework_css_2 = $framework.length ? $framework.dropdown('get value') : null;
        const framework_css_3 = 'framework_css' in gestor.html_editor ? gestor.html_editor.framework_css : null;

        return framework_css || framework_css_2 || framework_css_3 || 'fomantic-ui';
    }

    function modelosCarregar(forcar = false) {
        if (modelos_carregando && !forcar) return;

        modelos_carregando = true;

        // BATCH-103: em "Carregar Mais" (página > 1) a lista NÃO é escondida. Esconder `#modelos-cards`
        // remove toda a altura já renderizada, a página encolhe e o navegador joga a rolagem de volta
        // ao topo — era o salto reclamado ao paginar. O loading fica abaixo dos cards, então aparecer
        // ali não desloca o que o usuário está vendo. Na primeira página a lista é substituída, então
        // esconder continua correto.
        var modelosPrimeiraPagina = (modelos_pagina === 1);
        if (modelosPrimeiraPagina) $('#modelos-cards').hide();
        $('#modelos-loading').show();

        const framework_css = frameworkCSS();

        const ajax = ajaxDefault;
        ajax.ajaxOpcao = 'html-editor-templates-load';
        ajax.data.ajaxOpcao = ajax.ajaxOpcao;
        ajax.data.params = {
            pagina: modelos_pagina,
            limite: 20,
            alvo: ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas'),
            alvos_modelos: ('alvos_modelos' in gestor.html_editor ? gestor.html_editor.alvos_modelos : ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas')),
            framework_css
        };

        ajax.successCallback = function (response) {
            if (response.data && response.data.modelos) {
                modelosRenderizar(response.data.modelos, response.data.tem_mais);

                if (response.data.tem_mais) {
                    $('#modelos-load-more').show();
                } else {
                    $('#modelos-load-more').hide();
                }
            }

            modelos_carregando = false;
            $('#modelos-loading').hide();
            $('#modelos-cards').show();
        };

        ajax.successNotOkCallback = function (response) {
            modelos_carregando = false;
            $('#modelos-loading').hide();

            if (response !== undefined && 'status' in response && response.status === 'error') {
                msg_erro_mostrar(response.message);
            } else {
                msg_erro_mostrar('Erro ao carregar modelos de página.');
            }
        };

        $.ajax(ajax);
    }

    function modelosRenderizar(novos_modelos, tem_mais) {
        const container = $('#modelos-cards');
        const template = $('#modelo-card-template').html();

        if (modelos_pagina === 1) {
            container.empty();
            modelos = {};
        }

        if (novos_modelos.length === 0 && modelos_pagina === 1) {
            $('#modelos-empty').show();
            return;
        } else {
            $('#modelos-empty').hide();
        }

        novos_modelos.forEach(function (modelo) {
            modelos[modelo.id] = modelo;

            let html = template;
            html = html.replace(/\{\{id\}\}/g, modelo.id);
            html = html.replace(/\{\{nome\}\}/g, modelo.nome);
            html = html.replace(/\{\{thumbnail\}\}/g, modelo.thumbnail);
            html = html.replace(/\{\{target\}\}/g, modelo.target);
            html = html.replace(/\{\{language\}\}/g, modelo.language);

            container.append(html);
        });

        modelos_tem_mais = tem_mais;

        // Aplicar filtro se houver busca ativa
        modelosFiltrar();
    }

    // ===== Filtro de Modelos de Páginas

    // BATCH-103 (correção): marcas combinantes (U+0300-U+036F) montadas por código — mantém o fonte
    // ASCII e imune a um editor que normalize o arquivo. Mesmo helper usado no filtro do menu do
    // painel (`global/admin.js`) e no da Editbar (`dashboard.iframe-toolbar.js`).
    var RE_ACENTOS_BUSCA = new RegExp('[' + String.fromCharCode(0x300) + '-' + String.fromCharCode(0x36f) + ']', 'g');

    /** Minúsculas e sem acentos: 'pa' encontra 'Páginas', 'usuarios' encontra 'Usuários'. */
    function htmlEditorNormalizarBusca(texto) {
        var valor = String(texto == null ? '' : texto).toLowerCase().trim();
        if (!valor.normalize) return valor;
        return valor.normalize('NFD').replace(RE_ACENTOS_BUSCA, '');
    }

    /**
     * Filtra os cards de modelos baseado na query de busca
     * @param {string} query - Texto de busca (opcional, usa o valor do input se não fornecido)
     */
    function modelosFiltrar(query) {
        var searchInput = document.getElementById('modelos-search-input');
        var cardsContainer = document.getElementById('modelos-cards');
        var noResultsMessage = document.getElementById('modelos-no-results');
        var loadMoreBtn = document.getElementById('modelos-load-more');

        if (!searchInput || !cardsContainer) return;

        // Usar query fornecida ou valor do input
        var searchQuery = (typeof query !== 'undefined') ? query : searchInput.value;
        // BATCH-103 (correcao): comparacao sem acento e sem caixa - digitar 'pa' precisa encontrar
        // 'Paginas' (o segundo caractere do texto e acentuado, entao o `includes` cru falhava).
        var normalizedQuery = htmlEditorNormalizarBusca(searchQuery);

        var cards = cardsContainer.querySelectorAll('.modelo-card');
        var visibleCount = 0;

        cards.forEach(function (card) {
            var header = card.querySelector('.header');
            var meta = card.querySelector('.meta');
            var modeloId = card.getAttribute('data-modelo-id') || '';

            var headerText = header ? htmlEditorNormalizarBusca(header.textContent) : '';
            var metaText = meta ? htmlEditorNormalizarBusca(meta.textContent) : '';

            var matches = normalizedQuery === '' ||
                headerText.includes(normalizedQuery) ||
                metaText.includes(normalizedQuery) ||
                htmlEditorNormalizarBusca(modeloId).includes(normalizedQuery);

            if (matches) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Mostrar/ocultar mensagem de nenhum resultado
        if (noResultsMessage) {
            if (visibleCount === 0 && normalizedQuery !== '' && cards.length > 0) {
                noResultsMessage.style.display = 'block';
            } else {
                noResultsMessage.style.display = 'none';
            }
        }

        // Esconder botão "Carregar Mais" quando há filtro ativo
        if (loadMoreBtn) {
            if (normalizedQuery !== '' && modelos_tem_mais) {
                // Se há filtro ativo e ainda tem mais para carregar, mostrar dica
                loadMoreBtn.style.display = 'block';
            } else if (normalizedQuery === '' && modelos_tem_mais) {
                loadMoreBtn.style.display = 'block';
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }
    }

    // BATCH-103: alterna o ícone de lupa com o "x" de limpar, no mesmo padrão do gerenciador de
    // arquivos (`.c2f-search-icon` / `.c2f-search-clear`) — o campo de modelos era o único sem o
    // atalho para limpar a busca.
    function modelosBuscaSincronizarIcones(valor) {
        var vazio = String(valor == null ? '' : valor).trim() === '';
        $('.modelos-search-clear').toggleClass('hidden', vazio);
        $('.modelos-search-icon').toggleClass('hidden', !vazio);
    }

    // Event listener para input de busca de modelos (debounced)
    $(document).on('input', '#modelos-search-input', function () {
        var input = this;
        modelosBuscaSincronizarIcones(input.value); // ícone acompanha a digitação, sem debounce
        clearTimeout(input._debounceTimer);
        input._debounceTimer = setTimeout(function () {
            modelosFiltrar(input.value);
        }, 150);
    });

    // Event listener para tecla Escape no campo de busca
    $(document).on('keydown', '#modelos-search-input', function (e) {
        if (e.key === 'Escape') {
            this.value = '';
            modelosBuscaSincronizarIcones('');
            modelosFiltrar('');
            this.blur();
        }
    });

    // Clique no "x": limpa a busca, restaura a lista e devolve o foco ao campo.
    $(document).on('click', '.modelos-search-clear', function () {
        var input = document.getElementById('modelos-search-input');
        if (!input) return;
        input.value = '';
        clearTimeout(input._debounceTimer);
        modelosBuscaSincronizarIcones('');
        modelosFiltrar('');
        input.focus();
    });

    function modeloSelecionar(modelo_id) {
        if (!modelos[modelo_id]) {
            msg_erro_mostrar('Modelo não encontrado.');
            return;
        }

        const tipo_modificacao = tipoModificationPage();
        const id_sessao = pageSessionID();
        const modelo = modelos[modelo_id];

        var html_gerado = modelo.html ? modelo.html : '';
        var css_gerado = modelo.css ? modelo.css : '';
        var sessao_id = id_sessao ? id_sessao : '';
        var sessao_opcao = '';


        // Se for sessão, validar se uma sessão foi selecionada.
        if (tipo_modificacao === 'sessao') {
            sessao_opcao = sessaoOpcao();
        }

        modificarPaginaConteudo({
            html_gerado,
            css_gerado,
            sessao_id,
            sessao_opcao
        });

        if (typeof CodeMirrorHtmlExtraHead !== 'undefined') {
            CodeMirrorHtmlExtraHead.getDoc().setValue(modelo.html_extra_head ?? '');
        }

        if (typeof CodeMirrorCssCompiled !== 'undefined') {
            CodeMirrorCssCompiled.getDoc().setValue(modelo.css_compiled ?? '');
        }
        window.htmlEditorCssPrecompiled = htmlEditorCssPrecompiledAtualizar(
            modelo.css_precompiled ?? '',
            tipo_modificacao === 'sessao'
        );

        // Mudar para a aba de visualização da página
        const autoPreview = $('.page-modification-auto-preview').checkbox('is checked');
        if (tipo_modificacao == 'sessao') {
            // Alterar a ordem do menu de sessões conforme opção selecionada
            setTimeout(() => {
                const select = $('.ui.dropdown.page-modification-section-select');
                const options = select.find('select option');
                let index = 0;

                switch (sessao_opcao) {
                    case 'new-before':
                        index = 0; // Selecionar a primeira sessão (recém incluída acima)
                        break;
                    case 'new-after':
                        index = options.length - 1; // Selecionar a última sessão (recém incluída abaixo)
                        break;
                }

                // Definir a seleção baseada no index
                if (options.length > 0 && index >= 0 && index < options.length) {
                    select.dropdown('set selected', options.eq(index).val(), true);
                }
            }, 100);

            if (autoPreview) {
                contentPageTabChange('visualizacao-pagina');
            }
        } else {
            contentPageTabChange('visualizacao-pagina');
        }
    }

    function msg_sucesso_mostrar(mensagem) {

    }

    function msg_erro_mostrar(mensagem) {
        alert('Erro: ' + mensagem);
    }

    function msg_erro_resetar() {

    }

    $(document.body).on('mouseup tap', '.modeloSelecionar', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const modelo_id = $(this).data('modelo-id');
        modeloSelecionar(modelo_id);
    });

    $(document.body).on('click', '#btn-load-more', function (e) {
        e.preventDefault();
        modelos_pagina++;
        modelosCarregar();
    });

    // ===== Codemirror 

    var codemirrors_instances = new Array();
    const codermirrorHeight = 800;

    var codemirror_css = document.getElementsByClassName("codemirror-css");

    if (codemirror_css.length > 0) {
        for (var i = 0; i < codemirror_css.length; i++) {
            var CodeMirrorCss = CodeMirror.fromTextArea(codemirror_css[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "css",
                htmlMode: true,
                indentUnit: 4,
                theme: "tomorrow-night-bright",
                extraKeys: {
                    "F11": function (cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                    },
                    "Esc": function (cm) {
                        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                    }
                }
            });

            CodeMirrorCss.setSize('100%', codermirrorHeight);
            codemirrors_instances.push(CodeMirrorCss);
        }
    }

    var codemirror_css_compiled = document.getElementsByClassName("codemirror-css-compiled");

    if (codemirror_css_compiled.length > 0) {
        for (var i = 0; i < codemirror_css_compiled.length; i++) {
            var CodeMirrorCssCompiled = CodeMirror.fromTextArea(codemirror_css_compiled[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "css",
                htmlMode: true,
                indentUnit: 4,
                theme: "tomorrow-night-bright",
                extraKeys: {
                    "F11": function (cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                    },
                    "Esc": function (cm) {
                        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                    }
                }
            });

            CodeMirrorCssCompiled.setSize('100%', codermirrorHeight);
            codemirrors_instances.push(CodeMirrorCssCompiled);
        }
    }

    var codemirror_html = document.getElementsByClassName("codemirror-html");

    if (codemirror_html.length > 0) {
        for (var i = 0; i < codemirror_html.length; i++) {
            var CodeMirrorHtml = CodeMirror.fromTextArea(codemirror_html[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "htmlmixed",
                htmlMode: true,
                indentUnit: 4,
                theme: "tomorrow-night-bright",
                extraKeys: {
                    "F11": function (cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                    },
                    "Esc": function (cm) {
                        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                    }
                }
            });

            CodeMirrorHtml.setSize('100%', codermirrorHeight);
            codemirrors_instances.push(CodeMirrorHtml);
        }

        // req-160: só faz sentido guardar o salvamento onde existe editor de HTML.
        htmlEditorInterceptarSubmitParaGerarCss();
        htmlEditorObservarTrocaDeLayout();
    }

    var codemirror_html_extra_head = document.getElementsByClassName("codemirror-html-extra-head");

    if (codemirror_html_extra_head.length > 0) {
        for (var i = 0; i < codemirror_html_extra_head.length; i++) {
            var CodeMirrorHtmlExtraHead = CodeMirror.fromTextArea(codemirror_html_extra_head[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "htmlmixed",
                htmlMode: true,
                indentUnit: 4,
                theme: "tomorrow-night-bright",
                extraKeys: {
                    "F11": function (cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                    },
                    "Esc": function (cm) {
                        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                    }
                }
            });

            CodeMirrorHtmlExtraHead.setSize('100%', codermirrorHeight);
            codemirrors_instances.push(CodeMirrorHtmlExtraHead);
        }
    }

    // ===== req-044 §5.2: expor no escopo global as instâncias/metadados e auxiliares de que as
    // simulações de módulo (movidas para html-editor-modules.js) precisam. As funções movidas
    // referenciam estes nomes nus, que resolvem via window quando chamadas em runtime.
    window.CodeMirrorHtml = (typeof CodeMirrorHtml !== 'undefined') ? CodeMirrorHtml : undefined;
    window.CodeMirrorHtmlExtraHead = (typeof CodeMirrorHtmlExtraHead !== 'undefined') ? CodeMirrorHtmlExtraHead : undefined;
    window.publisher_fields_schema = publisher_fields_schema;
    window.frameworkCSS = frameworkCSS;
    window.previewHtml = previewHtml;
    window.regexVariaveisGlobal = regexVariaveisGlobal;
    window.alvoUsaItemVars = alvoUsaItemVars;

    // ===== API pública: atualizar conteúdo dos editores (usado por publisher-highlights.js
    // ao carregar um template via AJAX template-load).
    window.html_editor_set_html = function (html) {
        if (typeof CodeMirrorHtml !== 'undefined' && CodeMirrorHtml) {
            CodeMirrorHtml.getDoc().setValue(html || '');
            CodeMirrorHtml.refresh();
            // req-018: ao trocar o template_id, o CodeMirror pode não fazer relayout se a aba/editor
            // estava com foco ou oculta. Um refresh agendado garante a atualização visual correta.
            setTimeout(function () { CodeMirrorHtml.refresh(); }, 0);
        }
    };
    window.html_editor_set_css = function (css) {
        if (typeof CodeMirrorCss !== 'undefined' && CodeMirrorCss) {
            CodeMirrorCss.getDoc().setValue(css || '');
            CodeMirrorCss.refresh();
            setTimeout(function () { CodeMirrorCss.refresh(); }, 0);
        }
    };

    window.html_editor_refresh_preview = function () {
        previewHtml();
    };

    // req-007 item 4: APIs públicas para o painel de highlights ler/escrever conteúdo no iframe.
    window.html_editor_get_html = function () {
        return (typeof CodeMirrorHtml !== 'undefined' && CodeMirrorHtml) ? CodeMirrorHtml.getDoc().getValue() : '';
    };
    window.html_editor_get_css = function () {
        return (typeof CodeMirrorCss !== 'undefined' && CodeMirrorCss) ? CodeMirrorCss.getDoc().getValue() : '';
    };
    window.html_editor_set_iframe_html = function (html) {
        var iframe = $('#iframe-visualizacao-pagina');
        if (iframe.length === 0) return;
        iframe.parent().find('.ui.dimmer').addClass('active');
        iframe.on('load', function () {
            iframe.parent().find('.ui.dimmer').removeClass('active');
        });
        var idFramework = frameworkCSS();
        iframe.attr('srcdoc', previewHtmlConteudo(html || '', '', idFramework));
    };

    // req-008 item 2: manter todas as 5 sub-abas internas do html-editor intactas.
    // O ocultamento da req-007 item 4 foi revertido — as abas externas "Pré-Visualização"
    // e "Editor HTML" vivem no template da página de edição, fora deste componente.
    // O seletor de estilo de simulação continua oculto para destaques (item 4 deste req).
    if (('alvo' in gestor.html_editor) && (gestor.html_editor.alvo === 'publisher-highlights' || gestor.html_editor.alvo === 'menus' || gestor.html_editor.alvo === 'publisher-index')) {
        $('.publisher-design-mode-simulation').hide();
    }

    // ===== Semantic UI

    const tabIdCode = 'tabCodeActive';

    function codeTabHandler() {
        const tabActive = localStorage.getItem(gestor.moduloId + tabIdCode);

        if (tabActive !== null) {
            $('.menuPaginas .item').tab('change tab', tabActive);

            switch (tabActive) {
                case 'codigo-html':
                    CodeMirrorHtml.refresh();
                    break;
                case 'html-extra-head':
                    CodeMirrorHtmlExtraHead.refresh();
                    break;
                case 'css':
                    CodeMirrorCss.refresh();
                    break;
                case 'css-compiled':
                    CodeMirrorCssCompiled.refresh();
                    break;
            }
        }
    }

    $('.menuPaginas .item').tab({
        onLoad: function (tabPath, parameterArray, historyEvent) {
            switch (tabPath) {
                case 'codigo-html':
                    CodeMirrorHtml.refresh();
                    break;
                case 'html-extra-head':
                    CodeMirrorHtmlExtraHead.refresh();
                    break;
                case 'css':
                    CodeMirrorCss.refresh();
                    break;
                case 'css-compiled':
                    CodeMirrorCssCompiled.refresh();
                    break;
            }

            localStorage.setItem(gestor.moduloId + tabIdCode, tabPath);
        }
    });

    const tabIdContent = 'tabContentPageActive';

    function contentPageTabHandler() {
        const tabActive = localStorage.getItem(gestor.moduloId + tabIdContent);

        if (tabActive !== null) {
            $('.menuContainerPagina .item').tab('change tab', tabActive);

            switch (tabActive) {
                case 'visualizacao-pagina':
                    pageModificationContainerMove(tabActive);
                    previewHtml();
                    break;
                case 'modelos':
                    modelosCarregar();
                    pageModificationContainerMove(tabActive);
                    break;
                case 'assistente-ia':
                    pageModificationContainerMove(tabActive);
                    break;
                case 'visualizacao-codigo':
                    codeTabHandler();
                    break;
                case 'publisher-variables':
                    publisherVariablesSearch();
                    break;
            }
        }
    }

    // req-045: NÃO chamar contentPageTabHandler() aqui (síncrono, no meio do arquivo). Ele
    // cascateia em previewHtml()/montarWidgetAssetsHead(), que leem const/let declaradas mais
    // abaixo (ex.: WIDGET_SCRIPT_MODULES, total_sessoes) — ainda na Temporal Dead Zone. O kickoff
    // foi movido para o final do ready, após todas as declarações locais.
    window.contentPageTabHandler = contentPageTabHandler; // Expor globalmente para ser chamada após ações que modificam o conteúdo, como seleção de modelo.

    function contentPageTabChange(tabID = null) {
        if (tabID !== null) {
            $('.menuContainerPagina .item').tab('change tab', tabID);
        }
    }

    // req-045: a inicialização do tab `.menuContainerPagina` foi movida para o FIM do ready.
    // O Fomantic dispara `onLoad` de forma SÍNCRONA ao inicializar o tab; aqui (no meio do
    // arquivo) o onLoad chamaria previewHtml()/pageModificationContainerMove() lendo const/let
    // declaradas mais abaixo (WIDGET_SCRIPT_MODULES, total_sessoes) ainda na Temporal Dead Zone.

    // ===== Backup Campo Mudar

    const backupCallbackMap = {
        'paginas': 'adminPaginasBackupCampo',
        'layouts': 'adminLayoutsBackupCampo',
        'componentes': 'adminComponentesBackupCampo',
        'publisher': 'adminPaginasBackupCampo',
        'publisher-highlights': 'adminPaginasBackupCampo',
        'menus': 'adminPaginasBackupCampo',
        'forms': 'adminPaginasBackupCampo',
        // req-041 §3.1: alvo publisher-index reaproveita o mesmo callback de backup de páginas.
        'publisher-index': 'adminPaginasBackupCampo',
    };
    const backupCallbackName = backupCallbackMap[gestor.html_editor.alvo] || 'adminPaginasBackupCampo';

    // ===== Helpers de regex de variáveis sensíveis ao alvo
    function alvoAtual() {
        return ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');
    }
    function isHighlightsAlvo() {
        return alvoAtual() === 'publisher-highlights';
    }
    // req-017 item 1 / req-018 / req-041 §3.1: `publisher-highlights`, `menus`, `galleries` e
    // `publisher-index` usam a família de variáveis `[[item#X]]` (em vez de `[[publisher#TIPO#ID]]`).
    // Este helper unifica essa detecção.
    function alvoUsaItemVars() {
        var a = alvoAtual();
        return a === 'publisher-highlights' || a === 'menus' || a === 'galleries' || a === 'publisher-index' || a === 'forms';
    }
    // Regex global para encontrar todas as variáveis (suporta publisher, publisher-highlights e menus)
    function regexVariaveisGlobal() {
        return alvoUsaItemVars()
            ? /\[\[item#([a-zA-Z0-9_\-]+)\]\]/g
            : /\[\[publisher#([^#]+)#([^\]]+)\]\]/g;
    }

    $('#gestor-listener').on(backupCallbackName, function (e, p) {
        var campo = p.campo;
        var valor = p.valor;

        switch (campo) {
            case 'html':
                // req-067: só manipular o editor se a instância existir (telas sem editor).
                if (typeof CodeMirrorHtml !== 'undefined' && CodeMirrorHtml) {
                    if (gestor.editorHtmlAtivo) {
                        if (codeHtmlChanged) {
                            valor = indentHtml(valor);

                            CodeMirrorHtml.getDoc().setValue(valor);
                            CodeMirrorHtml.refresh();
                        } else {
                            // req-142: o editor de texto passou a ser o Quill, exposto pela
                            // biblioteca compartilhada. A guarda evita quebrar a tela quando este
                            // caminho é alcançado numa página que não carregou o editor.
                            if (window.EditorTexto && window.EditorTexto.instancias.length > 0) {
                                var alvo = window.EditorTexto.instancias[0].textarea;
                                window.EditorTexto.definirValor(alvo, valor);
                            } else {
                                console.warn('Editor de texto indisponível: valor não aplicado.');
                            }
                        }
                    } else {
                        CodeMirrorHtml.getDoc().setValue(valor);
                        CodeMirrorHtml.refresh();
                    }
                }
                break;
            case 'html-extra-head':
                if (typeof CodeMirrorHtmlExtraHead !== 'undefined' && CodeMirrorHtmlExtraHead) {
                    CodeMirrorHtmlExtraHead.getDoc().setValue(valor);
                    CodeMirrorHtmlExtraHead.refresh();
                }
                break;
            case 'css':
                // req-067: idem para o editor de CSS.
                if (typeof CodeMirrorCss !== 'undefined' && CodeMirrorCss) {
                    CodeMirrorCss.getDoc().setValue(valor);
                    CodeMirrorCss.refresh();
                }
                break;
            case 'css_compiled':
                // req-067: idem para o editor de CSS compilado.
                if (typeof CodeMirrorCssCompiled !== 'undefined' && CodeMirrorCssCompiled) {
                    CodeMirrorCssCompiled.getDoc().setValue(valor);
                    CodeMirrorCssCompiled.refresh();
                }
                break;
        }
    });

    // ===== Dropdown

    $('.frameworkCSS')
        .dropdown({
            onChange: function (value, text, $choice) {
                setTimeout(function () {
                    contentPageTabHandler();
                }, 100);
            }
        });

    $('.publisher-design-mode-simulation')
        .dropdown({
            onChange: function (value, text, $choice) {
                // Ao mudar o modo de simulação, atualizar o preview se a simulação estiver ativa
                if ($('.publisherVariablesOrSimulation[data-id="simulation"]').hasClass('active')) {
                    previewHtml();
                }
            }
        });

    $('.publisher-design-mode-variables')
        .dropdown();

    // ===== Editor HTML Visual e Pré-visualização.

    // Função para filtrar o HTML e apenas devolver o que tah dentro do <body>, caso o <body> exista. Senão retornar o HTML completo.
    function filtrarHtmlBody(html) {
        const bodyMatch = html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
        return bodyMatch ? bodyMatch[1] : html;
    }

    // req-114: contrato de tema compartilhado entre o CLI offline e o browser.
    function htmlEditorDecodeBase64(value) {
        if (!value) return '';
        try {
            const binary = atob(value);
            const bytes = Uint8Array.from(binary, character => character.charCodeAt(0));
            return new TextDecoder('utf-8').decode(bytes);
        } catch (error) {
            console.warn('Nao foi possivel carregar o contrato Tailwind do editor:', error);
            return '';
        }
    }

    // req-154: uma seção traz somente as utilities do próprio fragmento. Ao inseri-la em uma
    // página existente, substituir o baseline fazia todo o restante depender do build assíncrono
    // do Tailwind Browser. Preservamos a cascata já conhecida e acrescentamos o sidecar da seção.
    function htmlEditorCssPrecompiledAtualizar(novoCss, preservarAtual) {
        const editorConfig = gestor.html_editor || {};
        const inicial = htmlEditorDecodeBase64(editorConfig.cssPrecompiledBase64 || '');
        const atual = (typeof window.htmlEditorCssPrecompiled === 'string')
            ? window.htmlEditorCssPrecompiled
            : inicial;
        const novo = (typeof novoCss === 'string') ? novoCss : '';

        if (!preservarAtual) return novo;
        if (!atual) return novo;
        if (!novo || atual === novo || atual.endsWith('\n' + novo)) return atual;

        return atual + '\n' + novo;
    }


    // req-154: o output pré-compilado do Tailwind usa cascade layers. O Fomantic é uma folha sem
    // camada e, se carregado no mesmo iframe, vence utilities como padding, background e shadow.
    // O preview deve carregar exatamente um framework visual por vez.
    function htmlEditorPreviewFrameworkIncludes(framework) {
        const scripts = htmlEditorBaseScripts();

        // jQuery e os plugins permanecem disponíveis para scripts/widgets legados; somente a folha
        // visual concorrente precisa sair do iframe Tailwind.
        if (framework === 'tailwindcss') return scripts;

        return `<link rel="stylesheet" href="${htmlEditorAssetUrl('fomantic-ui', 'semantic.min.css')}">
            ${scripts}`;
    }

    // req-156: nomes das camadas do output do Tailwind v4, na ordem em que o próprio framework as
    // emite. O chrome do editor entra ANTES de todas para nunca reger o conteúdo do usuário.
    const HTML_EDITOR_CHROME_LAYER = 'c2f-editor-chrome';
    const HTML_EDITOR_TAILWIND_LAYERS = ['properties', 'theme', 'base', 'components', 'utilities'];

    // req-156: URL de uma biblioteca de terceiro para dentro do iframe, pelo registro de assets.
    //
    // O BATCH-146 tirou o gestor do CDN, mas o inventário daquele lote varreu as tags montadas no
    // PHP — e as do iframe nascem AQUI, no cliente, o que as deixou de fora. `assetsUrls` chega do
    // `html_editor_assets_urls()`, que resolve cada arquivo por `assets_externos_url()`: disco
    // primeiro, CDN apenas quando o arquivo local não existe (DEC-122).
    //
    // O fallback devolve string vazia em vez de uma URL de CDN escrita à mão: uma tag vazia falha de
    // modo visível e rastreável, enquanto um CDN embutido aqui recriaria em silêncio exatamente a
    // dependência que este trabalho remove.
    function htmlEditorAssetUrl(biblioteca, arquivo) {
        // Fonte única: `window.gestorAssets` (global.js), alimentado por `gestor.assetsUrls`. O
        // fallback direto ao objeto cobre o caso de o editor ser carregado sem o global — e evita
        // que uma ausência do helper derrube a montagem inteira do iframe por TypeError.
        if (window.gestorAssets && typeof window.gestorAssets.url === 'function') {
            return window.gestorAssets.url(biblioteca, arquivo);
        }

        const mapa = (typeof gestor !== 'undefined' && gestor.assetsUrls) ? gestor.assetsUrls : {};
        const url = (mapa[biblioteca] || {})[arquivo];

        if (typeof url === 'string' && url !== '') return url;

        console.warn('html-editor: asset nao resolvido pelo registro: ' + biblioteca + '/' + arquivo);
        return '';
    }

    // req-156: tags do CodeMirror para o iframe, na ordem de carga do registro.
    // `codemirror.min.js` define o objeto que todo addon estende (DEC-122, item 5).
    const HTML_EDITOR_CODEMIRROR_CSS = [
        'codemirror.min.css',
        'theme/tomorrow-night-bright.css',
        'addon/dialog/dialog.css',
        'addon/display/fullscreen.css',
    ];
    const HTML_EDITOR_CODEMIRROR_JS = [
        'codemirror.min.js',
        'addon/selection/active-line.js',
        'addon/edit/matchbrackets.js',
        'addon/edit/closetag.js',
        'addon/edit/closebrackets.js',
        'addon/display/fullscreen.js',
        'mode/xml/xml.js',
        'mode/css/css.js',
        'mode/javascript/javascript.js',
        'mode/htmlmixed/htmlmixed.js',
    ];

    function htmlEditorCodemirrorIncludes() {
        const css = HTML_EDITOR_CODEMIRROR_CSS
            .map(f => `<link rel="stylesheet" type="text/css" href="${htmlEditorAssetUrl('codemirror', f)}" />`)
            .join('\n            ');
        const js = HTML_EDITOR_CODEMIRROR_JS
            .map(f => `<script src="${htmlEditorAssetUrl('codemirror', f)}"><\/script>`)
            .join('\n            ');

        return `<!-- CodeMirror CSS -->\n            ${css}\n            <!-- CodeMirror JS -->\n            ${js}`;
    }

    // req-156: jQuery e o JS do Fomantic seguem no iframe para compatibilidade com widgets e
    // scripts legados — o que muda é a procedência: registro, não CDN.
    function htmlEditorBaseScripts() {
        return `<script src="${htmlEditorAssetUrl('jquery', 'jquery.min.js')}"><\/script>
            <script src="${htmlEditorAssetUrl('fomantic-ui', 'semantic.min.js')}"><\/script>`;
    }

    // req-156: fixa a ordem da cascata por declaração, e não por posição das folhas no `<head>`.
    //
    // Uma cascade layer é ordenada pela PRIMEIRA vez que seu nome aparece. Sem esta linha, a ordem
    // passaria a depender de o Fomantic ser injetado antes ou depois do baseline — e o Tailwind
    // Browser ainda registra camadas de forma assíncrona, quando compila. Declarando a ordem no topo
    // do documento, mover qualquer folha deixa de ter efeito sobre quem vence.
    function htmlEditorLayerOrderDeclaration(framework) {
        if (framework !== 'tailwindcss') return '';

        const ordem = [HTML_EDITOR_CHROME_LAYER].concat(HTML_EDITOR_TAILWIND_LAYERS).join(', ');

        return `<style data-c2f-css-role="layer-order">@layer ${ordem};</style>`;
    }

    // req-156: includes de framework do EDITOR VISUAL (`editorHtmlVisual`).
    //
    // Difere do preview por uma razão de produto: o editor injeta a própria interface DENTRO do
    // iframe (`#html-editor-modal` é um modal Fomantic, e `html-editor.js` chama `.modal()` sobre
    // ele). Simplesmente remover a folha, como o preview faz, deixaria o modal de edição de texto,
    // imagem e código sem estilo. A folha precisa ficar — mas parar de reger o conteúdo.
    //
    // Medido em Chromium sobre a página real, contra o preview: são DUAS contaminações distintas, e
    // cada uma exige um mecanismo próprio.
    //
    //   1. CONFLITO DE CASCATA. Folha sem camada vence utilities em `@layer`, independentemente da
    //      ordem: título 72px -> 24px, peso 900 -> 700, texto do CTA branco -> rgb(65,131,196).
    //      Resolvido importando o Fomantic dentro de `@layer c2f-editor-chrome`.
    //
    //   2. UNIDADE `rem` REDEFINIDA. O Fomantic declara `html{font-size:14px}`. O Tailwind v4
    //      dimensiona espaçamento, tipografia e raio em `rem`, então TODA medida encolhe por
    //      exatamente 14/16 = 0,875: 72->63px, 128->112px, 48->42px, 16->14px. Nenhuma camada
    //      corrige isso, porque não existe regra do Tailwind concorrendo por `html { font-size }` —
    //      a do Fomantic vence por ausência de disputa. É preciso restaurar a raiz explicitamente.
    //
    // O reset fica FORA de camada de propósito: assim vence o Fomantic sem depender de ordem, e
    // ainda perde para o CSS autoral do usuário, que é injetado depois e também sem camada.
    function htmlEditorVisualFrameworkIncludes(framework) {
        const scripts = htmlEditorBaseScripts();
        const fomanticCss = htmlEditorAssetUrl('fomantic-ui', 'semantic.min.css');

        if (framework !== 'tailwindcss') {
            return `<link rel="stylesheet" href="${fomanticCss}">
            ${scripts}`;
        }

        // `@import ... layer()` em vez de inlinar a folha: 1,7 MB por abertura do editor custaria
        // mais que o problema que resolve, e o arquivo já vem do disco do próprio projeto.
        return `<style data-c2f-css-role="editor-chrome">@import url("${fomanticCss}") layer(${HTML_EDITOR_CHROME_LAYER});</style>
            <style data-c2f-css-role="editor-rem-reset">html{font-size:16px}</style>
            ${scripts}`;
    }

    function tailwindPreviewIncludes() {
        const editorConfig = gestor.html_editor || {};
        const contract = htmlEditorDecodeBase64(editorConfig.tailwindBrowserContractBase64 || '');
        const initialBaseline = htmlEditorDecodeBase64(editorConfig.cssPrecompiledBase64 || '');
        const baseline = (typeof window.htmlEditorCssPrecompiled === 'string')
            ? window.htmlEditorCssPrecompiled
            : initialBaseline;
        const escapeStyleEnd = value => String(value || '').replace(/<\/style/gi, '<\\/style');
        const projectJavascript = editorConfig.projectJavascriptTailwindcss || '';

        // req-160: o baseline é emitido em DUAS folhas, e a distinção decide o que é gravado.
        //
        // `baseline` marca o que o runtime público realmente entrega (layout + `css_precompiled` do
        // recurso, ambos vindos do banco). `HtmlEditorCssCapture` filtra a captura contra ESSA folha:
        // o que já está nela não precisa ser regravado em `css_compiled`.
        //
        // O sidecar de um template inserido na sessão é outra coisa. O BATCH-156 passou a somá-lo ao
        // baseline para o editor renderizar certo — mas com isso ele entrou no filtro, e as regras do
        // template deixaram de ser gravadas: sumiam do `css_compiled` sem estar em lugar nenhum que o
        // runtime receba. Medido na página publicada: 8 utilities aplicadas sem regra alguma
        // (`border-b-2`, `bg-gradient-to-t`, `list-none`, `ml-auto`…), todas fornecidas por sidecar.
        //
        // Separando as folhas, o iframe continua com a mesma cascata (as duas estão no documento, na
        // mesma ordem) e a captura volta a gravar o que o runtime não tem. `css_precompiled` segue
        // sendo DERIVADO e escrito só pelo compilador — o contrato do CR-002 fica intacto.
        // req-160: o CSS AUTORAL do layout, que o runtime serve e o editor não recebia.
        //
        // Fica FORA do baseline de propósito. Duas razões: o baseline é o conjunto contra o qual a
        // captura filtra, e o autoral não é derivado — incluí-lo ali faria a captura descartar
        // regras achando que já existem, que é o mesmo erro do sidecar de template. E a posição
        // importa: no runtime ele entra DEPOIS do pré-compilado, e é essa ordem que se reproduz.
        //
        // `window.htmlEditorLayoutCssAutoral` é atualizada quando o operador troca o layout no
        // formulário — o baseline entregue na abertura deixa de valer naquele instante.
        const layoutAutoral = (typeof window.htmlEditorLayoutCssAutoral === 'string')
            ? window.htmlEditorLayoutCssAutoral
            : htmlEditorDecodeBase64(editorConfig.layoutCssAutoralBase64 || '');

        const overlaySessao = (baseline && initialBaseline && baseline.startsWith(initialBaseline))
            ? baseline.slice(initialBaseline.length)
            : (baseline === initialBaseline ? '' : baseline);
        const baselineRuntime = overlaySessao ? initialBaseline : baseline;

        return `<!-- Tailwind browser usa o mesmo tema do build offline -->
            ${baselineRuntime ? `<style data-c2f-tailwind-role="baseline">${escapeStyleEnd(baselineRuntime)}</style>` : ''}
            ${overlaySessao.trim() ? `<style data-c2f-css-role="session-overlay">${escapeStyleEnd(overlaySessao)}</style>` : ''}
            ${layoutAutoral.trim() ? `<style data-c2f-css-role="layout-authored">${escapeStyleEnd(layoutAutoral)}</style>` : ''}
            ${contract ? `<style type="text/tailwindcss" data-c2f-tailwind-role="browser-contract">${escapeStyleEnd(contract)}</style>` : ''}
            <script src="${htmlEditorAssetUrl('tailwindcss-browser', 'dist/index.global.js')}"><\/script>
            ${projectJavascript}`;
    }

    // Função para gerar o conteúdo da página do editor HTML visual.
    function editorHtmlVisualConteudo(htmlDoUsuario, cssDoUsuario, framework = 'fomantic-ui') {
        // Incluir o script e variáveis do editor HTML
        const { htmlEditorModalHtml, htmlEditorVars, htmlEditorScriptPath } = window.HtmlEditorHelper.variablesEnvironment();

        // Incluir o CSS do usuário, se existir
        if (cssDoUsuario && cssDoUsuario.length > 0) {
            cssDoUsuario = `<style data-c2f-tailwind-role="authored">${cssDoUsuario}</style>`;
        } else {
            cssDoUsuario = '';
        }

        let iframeTitle = 'Fomantic UI Preview';
        let tailwindConfigScript = '';

        if (framework === 'tailwindcss') {
            tailwindConfigScript = tailwindPreviewIncludes();

            iframeTitle = 'Tailwind CSS Preview';
        }

        const publisherPage = ('publisherPage' in gestor.html_editor ? true : false);
        const publisherQuillClassDetected = ('publisherQuillClassDetected' in gestor && gestor.publisherQuillClassDetected ? true : false);

        if (publisherPage || publisherQuillClassDetected) {
            tailwindConfigScript += `
                <link rel="stylesheet" type="text/css" media="all" href="${htmlEditorAssetUrl('quill', 'quill.snow.css')}" data-c2f-css-role="quill" />
                <style data-c2f-css-role="quill">
                    .ql-editor {
                        font-family: Lato, system-ui, -apple-system, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                        font-size: 16px !important;
                        line-height: 1.5rem !important;
                        overflow-y: hidden !important;
                        color: rgba(0, 0, 0, 0.8);
                        border: none !important;
                        padding: 0 !important;
                        margin: 0 !important;
                    }
                    .ql-container.ql-snow{
                        border: none !important;
                        padding: 0 !important;
                        margin: 0 !important;
                    }
                </style>`;
        }

        // req-156: as tags do CodeMirror saem do registro de assets (disco primeiro), na mesma
        // ordem de carga que o resto do gestor usa. A versão deixou de ser um literal aqui: era a
        // segunda cópia do número, e cópia é o que envelhece sem ninguém notar.
        const codemirrorIncludes = htmlEditorCodemirrorIncludes();

        // Altura do CodeMirror no modal do editor HTML visual (pode ser ajustada)
        const codermirrorHtmlEditorHeight = 600;

        // Script para inicializar o CodeMirror e utilitários dentro do iframe
        const codemirrorInitScript = `
            <script>
                // Função para formatar código HTML/CSS (copiada do pai)
                window.cleanCodeString = function(str, type) {
                    type = type || 'html';
                    if (!str) return '';

                    var lines = str.split('\\n').filter(function(line) { return line.trim() !== ''; }).map(function(l) { return l.trim(); });
                    if (lines.length === 0) return '';

                    var indentUnit = '    ';
                    var formatted = '';
                    var indentLevel = 0;

                    if (type === 'html') {
                        var voidTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr', '!doctype'];
                        var inTagDefinition = false;
                        var currentDefinitionTagName = '';

                        lines.forEach(function(line) {
                            var contentOnly = line.replace(/<!--[\\s\\S]*?-->/g, '');
                            var safeLine = contentOnly.replace(/"[^"]*"/g, '""').replace(/'[^']*'/g, "''");
                            var isClosingTagStart = contentOnly.trim().startsWith('</');

                            var printIndent = indentLevel;
                            if (isClosingTagStart) {
                                printIndent = Math.max(0, indentLevel - 1);
                            } else if (inTagDefinition) {
                                if (currentDefinitionTagName && !voidTags.includes(currentDefinitionTagName)) {
                                    printIndent = indentLevel;
                                } else {
                                    printIndent = indentLevel + 1;
                                }
                            }

                            formatted += indentUnit.repeat(printIndent) + line + '\\n';

                            var processLine = safeLine;

                            if (inTagDefinition) {
                                var closeIndex = safeLine.indexOf('>');
                                if (closeIndex > -1) {
                                    inTagDefinition = false;
                                    if (closeIndex > 0 && safeLine[closeIndex - 1] === '/') {
                                        if (currentDefinitionTagName && !voidTags.includes(currentDefinitionTagName)) {
                                            indentLevel = Math.max(0, indentLevel - 1);
                                        }
                                    }
                                    currentDefinitionTagName = '';
                                    processLine = safeLine.substring(closeIndex + 1);
                                } else {
                                    processLine = '';
                                }
                            }

                            if (processLine.length > 0) {
                                var openTagRegex = /<([a-zA-Z0-9-!]+)/g;
                                var match;
                                while ((match = openTagRegex.exec(processLine)) !== null) {
                                    var tagName = match[1].toLowerCase();
                                    if (!voidTags.includes(tagName) && !tagName.startsWith('!')) {
                                        indentLevel++;
                                    }
                                }

                                var closeTagRegex = /<\\/([a-zA-Z0-9-]+)/g;
                                var closeMatches = processLine.match(closeTagRegex) || [];
                                indentLevel -= closeMatches.length;

                                var selfClosingRegex = /<([a-zA-Z0-9-!]+)(?:[^>]*?)\\/>/g;
                                while ((match = selfClosingRegex.exec(processLine)) !== null) {
                                    var tagName = match[1].toLowerCase();
                                    if (!voidTags.includes(tagName) && !tagName.startsWith('!')) {
                                        indentLevel--;
                                    }
                                }

                                var lastOpen = processLine.lastIndexOf('<');
                                var lastClose = processLine.lastIndexOf('>');

                                if (lastOpen > lastClose) {
                                    inTagDefinition = true;
                                    var lastTagMatch = processLine.match(/<([a-zA-Z0-9-!]+)[^>]*$/);
                                    if (lastTagMatch) {
                                        currentDefinitionTagName = lastTagMatch[1].toLowerCase();
                                    }
                                }
                            }

                            if (indentLevel < 0) indentLevel = 0;
                        });

                    } else if (type === 'css') {
                        lines.forEach(function(line) {
                            var printIndent = indentLevel;
                            if (line.startsWith('}')) {
                                printIndent = Math.max(0, indentLevel - 1);
                            }

                            formatted += indentUnit.repeat(printIndent) + line + '\\n';

                            var openBraces = (line.match(/\\{/g) || []).length;
                            var closeBraces = (line.match(/\\}/g) || []).length;

                            indentLevel = Math.max(0, indentLevel + openBraces - closeBraces);
                        });
                    }

                    return formatted.trim();
                };

                $(document).ready(function() {
                    // Configuração do CodeMirror (mesmas opções do editor principal)
                    var codermirrorHtmlEditorHeight = ${codermirrorHtmlEditorHeight};
                    var codemirrorHtmlEditorElement = document.getElementById("element-code");
                    
                    if (codemirrorHtmlEditorElement) {
                        window.CodeMirrorHtmlEditor = CodeMirror.fromTextArea(codemirrorHtmlEditorElement, {
                            lineNumbers: true,
                            lineWrapping: true,
                            styleActiveLine: true,
                            matchBrackets: true,
                            mode: "htmlmixed",
                            htmlMode: true,
                            indentUnit: 4,
                            theme: "tomorrow-night-bright",
                            extraKeys: {
                                "F11": function(cm) {
                                    cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                                },
                                "Esc": function(cm) {
                                    if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                                }
                            }
                        });
                        
                        window.CodeMirrorHtmlEditor.setSize('100%', codermirrorHtmlEditorHeight);
                    }

                    // ===== ImagePick - Seletor de imagem do servidor =====
                    
                    if (typeof html_editor !== 'undefined' && html_editor.imagepick) {
                        var imagepickConfig = html_editor.imagepick;
                        
                        // Handler para o botão de seleção de imagem
                        $('._html-editor-imagepick-btn').on('click', function(e) {
                            e.preventDefault();
                            
                            // Comunicar com o pai para abrir o modal de seleção
                            window.parent.postMessage(JSON.stringify({
                                action: 'html-editor-imagepick-open',
                                config: imagepickConfig
                            }), '*');
                        });
                        
                        // Handler para limpar a seleção
                        $('._html-editor-imagepick-clear').on('click', function(e) {
                            e.preventDefault();
                            
                            // Limpar campo de URL
                            $('#element-src').val('');
                            
                            // Esconder preview
                            $('._html-editor-imagepick-preview').hide();
                            
                            // Limpar dados do imagepicker armazenados
                            window._imagepickerData = null;
                        });
                        
                        // Listener para receber a imagem selecionada do pai
                        window.addEventListener('message', function(e) {
                            try {
                                var data = JSON.parse(e.data);
                                
                                if (data.action === 'html-editor-imagepick-selected') {
                                    var imageData = data.imageData;
                                    
                                    // Construir URL completa com a raiz do gestor
                                    var raiz = (typeof html_editor !== 'undefined' && html_editor.raiz) ? html_editor.raiz : '/';
                                    var caminhoCompleto = raiz + imageData.caminho;
                                    
                                    // Atualizar campo de URL com caminho completo
                                    $('#element-src').val(caminhoCompleto);
                                    
                                    // Mostrar preview
                                    $('._html-editor-imagepick-preview').show();
                                    $('._html-editor-imagepick-image').attr('src', imageData.imgSrc);
                                    $('._html-editor-imagepick-nome .content').text(imageData.nome);
                                    $('._html-editor-imagepick-tipo .content').text(imageData.tipo);
                                    
                                    // Armazenar dados do imagepicker para uso posterior no saveChanges
                                    window._imagepickerData = {
                                        url: imageData.imgSrc,
                                        nome: imageData.nome,
                                        tipo: imageData.tipo
                                    };
                                }
                            } catch (error) {
                                // Ignorar mensagens não JSON
                            }
                        });
                    }
                });
            </script>
        `;

        // ===== Modo Layout: injetar ferramentas do editor no documento HTML completo
        const alvoEditor = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

        if (alvoEditor === 'layouts') {
            let fullHtml = htmlDoUsuario;

            // Includes para injetar no <head> do layout
            const editorHeadIncludes = `
                <!-- html-editor-injected-start -->
                ${htmlEditorLayerOrderDeclaration(framework)}
                ${tailwindConfigScript}
                ${htmlEditorVisualFrameworkIncludes(framework)}
                ${codemirrorIncludes}
                ${codemirrorInitScript}
                ${htmlEditorVars}
                ${htmlEditorScriptPath}
                ${montarPdfViewerHead(htmlDoUsuario)}
                ${cssDoUsuario}
                <!-- html-editor-injected-end -->
            `;

            // Injetar no <head> antes de </head>
            if (fullHtml.match(/<\/head>/i)) {
                fullHtml = fullHtml.replace(/<\/head>/i, editorHeadIncludes + '\n</head>');
            }

            // Injetar o modal do editor no <body> antes de </body>
            if (fullHtml.match(/<\/body>/i)) {
                fullHtml = fullHtml.replace(/<\/body>/i, htmlEditorModalHtml + '\n</body>');
            }

            return fullHtml;
        }

        return `
			<!DOCTYPE html>
			<html lang="pt-br">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>${iframeTitle}</title>
				${htmlEditorLayerOrderDeclaration(framework)}
				${tailwindConfigScript}
				${htmlEditorVisualFrameworkIncludes(framework)}
				${codemirrorInitScript}
				${codemirrorIncludes}
				${htmlEditorVars}
				${htmlEditorScriptPath}
				${montarPdfViewerHead(htmlDoUsuario)}
				${cssDoUsuario}
			</head>
			<body>
				${htmlDoUsuario}
				${htmlEditorModalHtml}
			</body>
			</html>
		`;
    }

    // req-093: pede ao backend (rota `html-editor-render-vars`) as duas versões renderizadas do HTML:
    // `data.boxes` (variáveis globais em caixas `.c2f-var-box` + `data-c2f-marker` + widgets renderizados,
    // para o EDITOR VISUAL) e `data.values` (globais resolvidas para valor, para o PREVIEW). Em qualquer
    // falha, chama `cb(null)` → o chamador usa o HTML cru (fluxo antigo preservado, sem regressão).
    function htmlEditorRenderVars(html, cb) {
        try {
            $.ajax({
                type: 'POST',
                url: moduloUrl(),
                data: {
                    opcao: gestor.moduloOpcao,
                    ajax: 'sim',
                    ajaxOpcao: 'html-editor-render-vars',
                    ajaxRegistroId: (('moduloRegistroId' in gestor) ? gestor.moduloRegistroId : false),
                    params: { html: html }
                },
                dataType: 'json',
                success: function (dados) {
                    cb(dados && dados.status === 'Ok' && dados.data ? dados.data : null);
                },
                error: function () { cb(null); }
            });
        } catch (e) { cb(null); }
    }

    // req-093: reverte as caixas de variável global (`.c2f-dyn-box`/`.c2f-var-box` com
    // `data-c2f-marker`) ao MARCADOR original (o texto exato que entrou, ex.: `[[pagina#url-raiz]]`),
    // para o save gravar as variáveis no banco em vez do valor renderizado. Espelha o
    // `reconstructOriginal` da Editbar, mas SÓ para variáveis (os widgets já são revertidos a
    // comentários pelo `htmlEditorGetCleanHtml` do motor). Early-return sem caixas → no-op seguro.
    function htmlEditorReconstructVars(html) {
        if (typeof html !== 'string' || html.indexOf('data-c2f-marker') === -1) return html;
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        var boxes = tmp.querySelectorAll('[data-c2f-marker]');
        var map = {};
        var i = 0;
        Array.prototype.forEach.call(boxes, function (box) {
            var b64 = box.getAttribute('data-c2f-marker');
            if (!b64) return;
            var marker;
            try { marker = decodeURIComponent(escape(window.atob(b64))); }
            catch (e) { try { marker = window.atob(b64); } catch (e2) { return; } }
            var token = '__C2FVAR' + (i++) + '__';
            map[token] = marker;
            if (box.parentNode) { box.parentNode.replaceChild(document.createTextNode(token), box); }
        });
        var out = tmp.innerHTML;
        Object.keys(map).forEach(function (token) { out = out.split(token).join(map[token]); });
        return out;
    }
    window.htmlEditorReconstructVars = htmlEditorReconstructVars;

    // Monta o srcdoc do editor visual e abre o modal (reuso entre o fluxo síncrono de layouts e o
    // assíncrono de páginas/componentes que primeiro renderiza as caixas de variável — req-093).
    function abrirEditorVisualSrcdoc(htmlParaEditor, cssDoUsuario, iframe) {
        const idFramework = frameworkCSS();
        iframe.attr('srcdoc', editorHtmlVisualConteudo(htmlParaEditor, cssDoUsuario, idFramework));
        $('.previsualizar.modal')
            .modal({ allowMultiple: true, observeChanges: true })
            .modal('show');
        if (idFramework === 'tailwindcss') { updateCSSCompiled(iframe); } else { updateCSSCompiled(iframe, true); }
    }

    function editorHtmlVisual() {
        const iframe = $('#iframe-preview');
        const alvo = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

        iframe.parent().find('.ui.dimmer').addClass('active');

        // Remover o dimmer quando o iframe terminar de carregar
        iframe.on('load', function () {
            iframe.parent().find('.ui.dimmer').removeClass('active');
        });

        const cssDoUsuario = CodeMirrorCss.getDoc().getValue().trim();

        if (alvo === 'layouts') {
            // Para layouts, manter o HTML completo (documento inteiro).
            // Armazenar head e atributos originais para reconstrução no save.
            const fullHtml = CodeMirrorHtml.getDoc().getValue();
            const htmlDoUsuario = fullHtml.trim();

            const headMatch = fullHtml.match(/<head[^>]*>([\s\S]*?)<\/head>/i);
            window._layoutOriginalHead = headMatch ? headMatch[0] : '<head></head>';

            const htmlMatch = fullHtml.match(/<html([^>]*)>/i);
            window._layoutHtmlAttrs = htmlMatch ? htmlMatch[1] : '';

            const doctypeMatch = fullHtml.match(/<!DOCTYPE[^>]*>/i);
            window._layoutDoctype = doctypeMatch ? doctypeMatch[0] : '<!DOCTYPE html>';

            abrirEditorVisualSrcdoc(htmlDoUsuario, cssDoUsuario, iframe);
        } else {
            // Para páginas/componentes, filtrar apenas o conteúdo do <body>
            const htmlDoUsuario = filtrarHtmlBody(CodeMirrorHtml.getDoc().getValue()).trim();

            // Atualizar o CodeMirror com o HTML filtrado.
            CodeMirrorHtml.getDoc().setValue(htmlDoUsuario);

            // req-093: renderiza as variáveis globais em caixas no backend antes de abrir o editor
            // visual (fallback ao HTML cru em caso de falha).
            htmlEditorRenderVars(htmlDoUsuario, function (data) {
                abrirEditorVisualSrcdoc((data && data.boxes) || htmlDoUsuario, cssDoUsuario, iframe);
            });
        }
    }

    // Botões da Pré-visualização.
    $(document.body).on('mouseup tap', '.editorHtmlVisual.button', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        editorHtmlVisual();
    });

    $(document.body).on('mouseup tap', '.publisherVariablesOrSimulation,.publisherVariablesOrValues', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        toggleActiveButton($(this));
        previewHtml();
    });

    // req-034: os botões de tela (desktop/tablet/mobile) agora ajustam a LARGURA do frame
    // interno do preview (`.iframe-preview-frame`), mantendo o modal sempre em fullscreen.
    // Essa lógica vive em `html-editor-visual-controls.js` (handler `.previsualizar .screenPagina`),
    // junto às alças de redimensionamento. O modal permanece com a classe `fullscreen` do markup.

    $(document.body).on('mouseup tap', '.previsualizarConfirmar, .previsualizarVoltar', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const iframe = $('#iframe-preview')[0];
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        const iframeWin = iframe.contentWindow;
        const alvoSave = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

        // req-034: o editor visual do iframe expõe `htmlEditorGetCleanHtml()`, que remove toda a
        // UI do editor (overlays de hover/seleção, barra flutuante, breadcrumb, styler, placeholders)
        // e reconverte os wrappers virtuais de widget em comentários <!-- widgets#...->render(...) -->.
        // Fallback (editor visual indisponível): limpeza manual dos elementos de sistema.
        let bodyContent;
        if (iframeWin && typeof iframeWin.htmlEditorGetCleanHtml === 'function') {
            bodyContent = iframeWin.htmlEditorGetCleanHtml();
        } else {
            const sistemaSel = '#html-editor-modal,#html-editor-overlay,#html-editor-hover-overlay,' +
                '#html-editor-selection-overlay,#html-editor-floating-toolbar,#html-editor-selection-breadcrumb,' +
                '#html-editor-selection-children,#html-editor-breadcrumb-hover-overlay,' +
                '#html-editor-parent-highlight-overlay,#html-editor-insert-ghost,#html-editor-wrap-menu,' +
                // req-106: painéis fixos de exibição (hospedam styler/breadcrumbs quando ligados).
                '#c2f-he-css-sidebar,#c2f-he-element-navbar,' +
                '#html-editor-tailwind-styler,.conn2flow-dnd-placeholder,.ui.dimmer.modals';
            iframeDoc.querySelectorAll(sistemaSel).forEach(el => el.remove());
            bodyContent = iframeDoc.body ? iframeDoc.body.innerHTML : '';
        }

        // req-093: reverte as caixas de variável global (.c2f-var-box + data-c2f-marker) ao marcador
        // original ([[var]]) — o banco grava as variáveis, não os valores renderizados. No-op se não
        // houver caixas (fluxo antigo intacto).
        bodyContent = htmlEditorReconstructVars(bodyContent);

        let updatedHtml;

        if (alvoSave === 'layouts') {
            // Para layouts: reconstruir o documento completo com o head original e o body editado
            const cleanBody = cleanCodeString(bodyContent);

            // Reconstruir o documento completo com head original (preservado antes de abrir o editor)
            const doctype = window._layoutDoctype || '<!DOCTYPE html>';
            const htmlAttrs = window._layoutHtmlAttrs || '';
            const originalHead = window._layoutOriginalHead || '<head></head>';

            updatedHtml = `${doctype}\n<html${htmlAttrs}>\n${originalHead}\n<body>\n${cleanBody}\n</body>\n</html>`;
            updatedHtml = cleanCodeString(updatedHtml);
        } else {
            // Para páginas/componentes: atualizar apenas o conteúdo do body
            updatedHtml = cleanCodeString(bodyContent);
        }

        // Atualizar o CodeMirror com o HTML atualizado.
        CodeMirrorHtml.getDoc().setValue(updatedHtml);

        // Fechar o modal de pré-visualização se o botão clicado for o de voltar.
        if ($(this).hasClass('previsualizarVoltar')) {
            previewHtml();
            return;
        }

        // req-109 §8: o salvamento é um POST de formulário disparado por código. Nem o evento
        // `submit` nativo nem o handler delegado do jQuery cobrem TODOS os caminhos até o envio,
        // então o token é anexado aqui, explicitamente, antes de `$.formSubmitNormal()`.
        if (!htmlEditorAplicarCsrfNoFormulario()) return;

        $.formSubmitNormal();
    });

    // req-109 §8: anexa o token CSRF ao formulário padrão do gestor e avisa o usuário de forma
    // amigável quando ele não existe. Sem isto o backend responde 403 com o JSON cru
    // `{"status":"error","message":"Token CSRF inválido ou ausente."}`, que o navegador exibe na
    // tela inteira — o trabalho do editor parece perdido, embora só falte o token.
    function htmlEditorAplicarCsrfNoFormulario() {
        const $form = $('.ui.form.interfaceFormPadrao');
        if (!$form.length) return true;

        const token = (typeof gestor !== 'undefined' && gestor.csrfToken)
            ? gestor.csrfToken
            : ($('meta[name="csrf-token"]').attr('content') || '');

        if (!token) {
            const emIngles = htmlEditorIdiomaIngles();
            alert(emIngles
                ? 'Your session expired and the security token is no longer available. Open a new tab, sign in again and copy your changes before saving.'
                : 'Sua sessão expirou e o token de segurança não está mais disponível. Abra outra aba, entre novamente e copie suas alterações antes de salvar.');
            return false;
        }

        let $campo = $form.find('input[name="_csrf_token"]');
        if (!$campo.length) {
            $campo = $('<input type="hidden" name="_csrf_token">').appendTo($form);
        }
        $campo.val(token);

        return true;
    }

    function htmlEditorIdiomaIngles() {
        const lang = String((typeof gestor !== 'undefined' && gestor.language) ? gestor.language : '').toLowerCase();
        return lang.indexOf('en') === 0;
    }

    window.htmlEditorAplicarCsrfNoFormulario = htmlEditorAplicarCsrfNoFormulario; // exposta para teste.

    // req-117: número de tentativas e intervalo do polling da compilação (4 s no total). O
    // @tailwindcss/browser resolve em ~100–300 ms num documento comum; a folga cobre máquina lenta
    // e primeira carga do script pelo CDN.
    const CSS_COMPILED_TENTATIVAS = 40;
    const CSS_COMPILED_INTERVALO = 100;

    // req-160: o layout pode ser TROCADO no editor a qualquer momento.
    //
    // O baseline e o CSS autoral chegam resolvidos na abertura da tela, para o layout que a página
    // tinha naquele instante. Trocar o layout no select muda a cascata inteira: sem recarregar as
    // duas camadas, o operador continuaria vendo a página sobre o layout ANTIGO — e, pior, salvaria
    // um `css_compiled` derivado de uma cascata que não é a da página.
    //
    // As duas camadas voltam separadas da rota e vão para lugares diferentes: `precompiled` para o
    // baseline (contra o qual a captura filtra) e `autoral` para fora dele, como no runtime.
    function htmlEditorObservarTrocaDeLayout() {
        const campo = $('select[name="layout"]');
        if (!campo.length || campo.data('c2fLayoutCssBound')) return;
        campo.data('c2fLayoutCssBound', true);

        campo.on('change', function () {
            const layoutId = $(this).val();
            if (!layoutId) return;

            $.ajax({
                type: 'POST',
                url: moduloUrl(),
                data: {
                    opcao: gestor.moduloOpcao,
                    ajax: 'sim',
                    ajaxOpcao: 'html-editor-layout-css',
                    ajaxRegistroId: (('moduloRegistroId' in gestor) ? gestor.moduloRegistroId : false),
                    params: { layout_id: layoutId }
                },
                dataType: 'json',
                success: function (dados) {
                    if (!dados || dados.status !== 'Ok' || !dados.data) return;

                    window.htmlEditorLayoutCssAutoral = dados.data.autoral || '';

                    // O baseline é `layout + recurso`: troca-se a parte do layout preservando o que
                    // a sessão acumulou (sidecars de template já inseridos).
                    const editorConfig = gestor.html_editor || {};
                    const anterior = htmlEditorDecodeBase64(editorConfig.cssPrecompiledBase64 || '');
                    const atual = (typeof window.htmlEditorCssPrecompiled === 'string')
                        ? window.htmlEditorCssPrecompiled
                        : anterior;
                    const daSessao = atual.startsWith(anterior) ? atual.slice(anterior.length) : '';

                    const novoBaseline = htmlEditorCssPrecompiledConcatenar(dados.data.precompiled || '', daSessao);
                    window.htmlEditorCssPrecompiled = novoBaseline;
                    editorConfig.cssPrecompiledBase64 = htmlEditorEncodeBase64(novoBaseline);

                    // Remonta a visualização já sob o layout novo.
                    try { previewHtml(); } catch (error) { /* editor ainda montando */ }
                },
                error: function () { /* mantém a cascata anterior: melhor que zerar */ }
            });
        });
    }

    // Espelha `html_editor_css_precompiled_concatenar()` do PHP: o layout vem primeiro.
    function htmlEditorCssPrecompiledConcatenar(layout, recurso) {
        layout = String(layout || '');
        recurso = String(recurso || '');
        if (layout === '') return recurso;
        if (recurso === '') return layout;
        return layout + '\n' + recurso;
    }

    function htmlEditorEncodeBase64(texto) {
        try {
            const bytes = new TextEncoder().encode(String(texto || ''));
            let binario = '';
            bytes.forEach(b => { binario += String.fromCharCode(b); });
            return window.btoa(binario);
        } catch (error) { return ''; }
    }

    // req-160: HTML que está no editor agora. Usado para saber se o CSS derivado ainda corresponde.
    function htmlEditorHtmlAtual() {
        try {
            if (typeof CodeMirrorHtml !== 'undefined' && CodeMirrorHtml) return CodeMirrorHtml.getDoc().getValue();
        } catch (error) { /* editor ainda não instanciado */ }
        return null;
    }

    // req-160: o `css_compiled` vigente foi derivado do HTML que está na tela?
    //
    // Três situações distintas, e só a última justifica intervir:
    //   - não há editor de HTML nesta tela  -> nada a verificar;
    //   - o HTML não tem classe nenhuma     -> CSS vazio é legítimo (framework não-Tailwind, HTML puro);
    //   - o HTML mudou depois da captura    -> o CSS é de outro conteúdo.
    function htmlEditorCssCompiledDesatualizado() {
        const html = htmlEditorHtmlAtual();
        if (html === null) return false;
        if (frameworkCSS() !== 'tailwindcss') return false;

        // Sem classe no HTML não há utility a compilar; exigir CSS aqui travaria um save legítimo.
        if (!/class\s*=\s*["'][^"']*[^\s"']/.test(html)) return false;

        let compiled = '';
        try { compiled = CodeMirrorCssCompiled.getDoc().getValue(); } catch (error) { return false; }

        if (!compiled.trim()) return true;

        // Havendo CSS, ele precisa ser DESTE HTML. `htmlEditorCssCompiledOrigem` é gravada pela
        // captura; quando ela é nula (página recém-aberta, CSS vindo do banco) o valor do banco é
        // aceito — quem o gravou foi uma captura anterior sobre o mesmo conteúdo.
        if (typeof window.htmlEditorCssCompiledOrigem !== 'string') return false;

        return window.htmlEditorCssCompiledOrigem !== html;
    }

    // req-160: em vez de recusar o save, gerar o que falta e então enviar.
    //
    // O defeito que isto fecha: a captura só acontece quando o iframe de visualização é montado.
    // Quem criava a página pelos modelos, conferia noutra aba e salvava gravava `css_compiled`
    // VAZIO — e a página publicada saía sem CSS nenhum, enquanto o CRUD parecia perfeito. Medido
    // numa página criada do zero: 360 classes aplicadas, 0 byte gravado.
    //
    // Bloquear com mensagem resolveria, mas empurra para o operador um passo que a máquina sabe
    // fazer: a compilação leva menos de um segundo. Então o submit é adiado, não cancelado — troca
    // para a aba de visualização, aguarda a captura AVISAR que terminou (não um tempo fixo) e
    // reenvia. Falhando a captura, o save prossegue: preservar o valor anterior já é o
    // comportamento seguro, e travar a tela seria pior que gravar o que se tinha.
    function htmlEditorInterceptarSubmitParaGerarCss() {
        const formulario = document.querySelector('form.ui.form') || document.querySelector('form');
        if (!formulario || formulario.dataset.c2fCssGuard === '1') return;
        formulario.dataset.c2fCssGuard = '1';

        formulario.addEventListener('submit', function (evento) {
            // Reentrada: este é o envio que nós mesmos disparamos depois de gerar.
            if (formulario.dataset.c2fCssGerando === 'concluido') {
                formulario.dataset.c2fCssGerando = '';
                return;
            }
            if (formulario.dataset.c2fCssGerando === '1') { evento.preventDefault(); evento.stopImmediatePropagation(); return; }
            if (!htmlEditorCssCompiledDesatualizado()) return;

            // Capture phase + stopImmediatePropagation: o handler de submit do `formulario.js` é
            // registrado por jQuery (fase de bubble) e não chega a rodar.
            evento.preventDefault();
            evento.stopImmediatePropagation();
            formulario.dataset.c2fCssGerando = '1';

            const reenviar = () => {
                formulario.dataset.c2fCssGerando = 'concluido';
                const botao = $(formulario).data('clickedButton');
                if (botao && botao.length) botao.trigger('click');
                $(formulario).trigger('submit');
            };

            try {
                // A troca de aba é o que monta o iframe — é ela que dispara a compilação.
                $('.menuContainerPagina .item[data-tab="visualizacao-pagina"]').trigger('click');
                previewHtml();
                updateCSSCompiled($('#iframe-visualizacao-pagina'), false, reenviar);
            } catch (error) {
                console.warn('Nao foi possivel gerar o CSS antes do salvamento:', error);
                reenviar();
            }
        }, true);
    }

    // req-160: `aoConcluir` avisa quem esperava a captura terminar.
    //
    // Existe para a trava de salvamento: em vez de recusar o save quando o CSS não foi gerado, o
    // editor gera e SÓ ENTÃO envia. Sem um aviso de conclusão restaria cravar um tempo de espera —
    // e tempo fixo erra nos dois sentidos: sobra na página pequena e falta na grande.
    // Recebe `true` quando a captura gravou, `false` quando a janela esgotou.
    function updateCSSCompiled(iframe, clean = false, aoConcluir = null) {
        const avisar = ok => { if (typeof aoConcluir === 'function') aoConcluir(ok); };

        if (clean) {
            CodeMirrorCssCompiled.getDoc().setValue('');
            avisar(true);
            return;
        }

        const iframeObject = iframe[0];

        // A lógica de captura vive no motor (`html-editor.js`), que roda DENTRO do iframe — uma
        // implementação só, compartilhada com a Editbar. O `srcdoc` herda a origem, então a janela
        // pai alcança o objeto normalmente; o try/catch cobre o caso de o documento ainda estar
        // trocando de conteúdo.
        function capturaApi() {
            try {
                const janela = iframeObject.contentWindow;
                if (janela && janela.HtmlEditorCssCapture) return janela.HtmlEditorCssCapture;
            } catch (error) { /* documento em transição: a próxima tentativa resolve */ }
            return null;
        }

        function capture(attempt = 0) {
            let iframeDoc = null;
            try { iframeDoc = iframeObject.contentDocument || iframeObject.contentWindow.document; }
            catch (error) { iframeDoc = null; }

            const api = capturaApi();
            const resultado = (iframeDoc && api) ? api.extract(iframeDoc) : { ready: false, motivo: 'sem-motor', css: '' };

            if (resultado.ready) {
                CodeMirrorCssCompiled.getDoc().setValue(resultado.css);
                // req-160: marca DE QUE HTML este CSS foi derivado. É essa marca que permite saber,
                // no save, se o CSS ainda corresponde ao conteúdo — sem ela só se sabe que existe
                // algum CSS, não se ele é o certo.
                window.htmlEditorCssCompiledOrigem = htmlEditorHtmlAtual();
                avisar(true);
                return;
            }

            if (attempt < CSS_COMPILED_TENTATIVAS) {
                setTimeout(() => capture(attempt + 1), CSS_COMPILED_INTERVALO);
                return;
            }

            // Janela esgotada: PRESERVA o valor que veio do banco. Gravar uma captura incompleta é
            // o defeito que este bloco existe para impedir — foi assim que páginas inteiras
            // perderam as utilities do Tailwind (req-117).
            console.warn('CSS compilado nao ficou pronto a tempo (' + resultado.motivo + '); o valor anterior foi preservado.');
            avisar(false);
        }

        setTimeout(() => capture(), CSS_COMPILED_INTERVALO);
    }

    // Função para gerar o conteúdo da página de pré-visualização fora do editor HTML.
    function previewExternalHtmlConteudo(params = {}) {
        const htmlDoUsuario = params.htmlDoUsuario || '';
        const cssDoUsuario = params.cssDoUsuario || '';
        const framework = params.framework || 'fomantic-ui';
        const extraParams = params.extraParams || {};

        return previewHtmlConteudo(htmlDoUsuario, cssDoUsuario, framework, extraParams);
    }

    window.previewExternalHtmlConteudo = previewExternalHtmlConteudo; // Expor globalmente para ser usada na pré-visualização fora do editor HTML.

    // Função para gerar o conteúdo da página do pré-visualizador.
    // req-040: rotina autocontida injetada no iframe de pré-visualização (#iframe-visualizacao-pagina).
    // Varre os comentários de widget (<!-- widgets#sig < --> ... <!-- widgets#sig > -->), substitui o
    // intervalo por um contêiner neutro e renderiza cada widget via AJAX `html-editor-widget-render`
    // (rota/credenciais lidas de window.parent.gestor). Injetada via .toString() para preservar as regex.
    function widgetPreviewBootstrap() {
        // req-044 §2.1: descarrega entidades HTML (&gt; → >, &quot; → ", &amp; → &) que o navegador
        // injeta ao serializar a assinatura no DOM (incl. duplo escape &amp;gt;). Definida aqui dentro
        // porque a função é injetada no iframe via .toString() (sem acesso ao escopo do editor).
        function unescapeEntities(s) {
            if (!s || s.indexOf('&') === -1) return s || '';
            var ta = document.createElement('textarea');
            var out = s, prev, guard = 0;
            do { prev = out; ta.innerHTML = out; out = ta.value; guard++; }
            while (out !== prev && out.indexOf('&') !== -1 && guard < 3);
            return out;
        }
        function renderWidgets() {
            // req-043 §4.1: variáveis de widget inline ([[widgets#...]] ou @[[widgets#...]]@) viram
            // blocos de comentário equivalentes antes da varredura, para serem renderizadas como widgets.
            // req-044 §2.1: a assinatura capturada passa por unescape antes de virar comentário.
            var bodyHtml = document.body.innerHTML;
            var varRe = /@?\[\[widgets#(.+?)\]\]@?/gi;
            if (varRe.test(bodyHtml)) {
                document.body.innerHTML = bodyHtml.replace(varRe, function (_m, sig) {
                    var s = unescapeEntities(sig);
                    return '<!-- widgets#' + s + ' < --><!-- widgets#' + s + ' > -->';
                });
            }
            var P = window.parent;
            if (!P || !P.gestor) return;
            var g = P.gestor;
            // req-109 §7: `moduloCaminho` já vem do backend com a barra final
            // (gestor.php: `rtrim($caminho,'/').'/'`). Concatenar outra barra produzia
            // `admin-paginas/editar//`, uma rota que o gestor não resolve.
            var url = String(g.raiz || '').replace(/\/+$/, '/') + String(g.moduloCaminho || '').replace(/^\/+/, '');
            url = url.replace(/([^:])\/{2,}/g, '$1/');
            if (url.charAt(url.length - 1) !== '/') url += '/';
            // req-109 §7: o iframe é `srcdoc` — herda a origem, mas não o <head> da página
            // hospedeira, então não tem a <meta name="csrf-token"> nem o global.js. Sem o token
            // explícito, todo POST daqui volta 403 "Token CSRF inválido ou ausente.".
            var csrf = (g.csrfToken || '');
            var openRe = /^\s*widgets#(.+?)\s*<\s*$/i;
            var closeRe = /^\s*widgets#\s*(.+?)\s*>\s*$/i;
            var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_COMMENT, null);
            var comments = [], n;
            while ((n = walker.nextNode())) comments.push(n);
            for (var i = 0; i < comments.length; i++) {
                var c = comments[i];
                if (!c.parentNode) continue;
                var mo = c.data.match(openRe);
                if (!mo) continue;
                // req-044 §2.1: unescape da assinatura antes de buscar o par e disparar o AJAX.
                var signature = unescapeEntities(mo[1].trim());
                var close = null;
                for (var j = i + 1; j < comments.length; j++) {
                    var mc = comments[j].data.match(closeRe);
                    if (mc && unescapeEntities(mc[1].trim()) === signature) { close = comments[j]; break; }
                }
                if (!close || close.parentNode !== c.parentNode) continue;
                var box = document.createElement('div');
                box.className = 'c2f-preview-widget';
                box.style.display = 'contents';
                c.parentNode.insertBefore(box, c);
                var node = c.nextSibling;
                while (node && node !== close) { var next = node.nextSibling; node.parentNode.removeChild(node); node = next; }
                c.parentNode.removeChild(c);
                if (close.parentNode) close.parentNode.removeChild(close);
                (function (boxEl, sig) {
                    var jq = window.jQuery || window.$;
                    var data = { opcao: g.moduloOpcao, ajax: 'sim', ajaxOpcao: 'html-editor-widget-render', params: { signature: sig } };
                    // Token vai no corpo E no cabeçalho: `seguranca_csrf_token_requisicao()` aceita
                    // os dois, e o cabeçalho sobrevive a proxies que reescrevem o corpo.
                    if (csrf) data._csrf_token = csrf;
                    if (jq) {
                        jq.ajax({
                            type: 'POST', url: url, dataType: 'json', data: data,
                            headers: csrf ? { 'X-CSRF-Token': csrf } : {},
                            success: function (resp) { if (resp && resp.status === 'Ok' && resp.data) boxEl.innerHTML = resp.data.html || ''; },
                            error: function () { }
                        });
                    }
                })(box, signature);
            }
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderWidgets);
        else renderWidgets();
    }

    // req-044 §3/§4: mapa de módulos que possuem script controlador de widget público (*.widget.js).
    // req-070 §1.2: a lista passa a ser parametrizada por módulo via gestor.html_editor.widget_js_include
    // (injetada pelo backend em html_editor_componente). Quando ausente, mantém o fallback com os 4
    // módulos do core para retrocompatibilidade.
    const WIDGET_SCRIPT_MODULES = (gestor.html_editor && gestor.html_editor.widget_js_include)
        ? gestor.html_editor.widget_js_include
        : { 'galleries': true, 'publisher-index': true, 'menus': true, 'forms': true };

    // req-044 §3/§4: extrai as assinaturas de widgets (comentários e variáveis inline) presentes no
    // HTML do usuário, desduplicadas e na ordem de aparição. Espelha a detecção que o PHP faz no
    // page load do site real, mas aqui sobre o HTML estático do editor (que gera o srcdoc).
    function extrairAssinaturasWidgets(html) {
        const assinaturas = [];
        const vistos = {};
        if (!html) return assinaturas;
        const push = (sig) => {
            sig = (sig || '').trim();
            if (!sig || vistos[sig]) return;
            vistos[sig] = true;
            assinaturas.push(sig);
        };
        let m;
        // const reComentario = /<!--\s*widgets#([\s\S]+?)\s*<\s*-->/gi;
        const reComentario = /<!--\s*widgets#([\s\S]*?)\s*<\s*-->([\s\S]?)<!--\s*widgets#\1\s*>\s-->/gi;
        while ((m = reComentario.exec(html)) !== null) push(m[1]);
        const reVariavel = /@?\[\[widgets#([\s\S]+?)\]\]@?/gi;
        while ((m = reVariavel.exec(html)) !== null) push(m[1]);
        return assinaturas;
    }

    // req-044 §3/§4: monta os includes de cabeçalho do preview para os widgets presentes:
    //  (a) declara window.gestor.widgetsToAjax com as assinaturas (divisor <#;>), pois o srcdoc é
    //      gerado estaticamente pelo JS e o PHP não cria essa variável no contexto do iframe;
    //  (b) injeta os scripts controladores de widget (*.widget.js) de forma desduplicada.
    function montarWidgetAssetsHead(htmlDoUsuario, manualWidgetsToAjax = null) {
        if (manualWidgetsToAjax !== null) {
            let includes = '<script>\n' +
                'window.gestor = Object.assign({}, window.parent.gestor || {});\n' +
                'window.gestor.widgetsToAjax = ' + JSON.stringify(manualWidgetsToAjax) + ';\n' +
                '<\/script>\n';

            return includes;
        } else {
            const assinaturas = extrairAssinaturasWidgets(htmlDoUsuario);
            // req-070 §1: scripts declarados explicitamente pelo módulo (gestor.html_editor.widget_js_include)
            // são injetados mesmo sem assinatura de widget presente — ex.: o editor de forms, cujo conteúdo
            // do preview É o próprio formulário (.conn2flow-form), não um wrapper [[widgets#...]]. Quando o
            // backend não informa a chave (editores de página/layout/componente), mantém-se a injeção
            // apenas por assinatura (retrocompatibilidade).
            const explicitInclude = (typeof gestor !== 'undefined' && gestor.html_editor && gestor.html_editor.widget_js_include)
                ? gestor.html_editor.widget_js_include : null;
            if (!assinaturas.length && !explicitInclude) return '';

            const raiz = (typeof gestor !== 'undefined' && gestor.raiz) ? gestor.raiz : '';
            const versao = (typeof gestor !== 'undefined' && gestor.versao) ? gestor.versao : '';

            // (a) variável widgetsToAjax (replica o que o PHP gera no page load do site real).
            const listaAjax = assinaturas.join('<#;>');
            let includes = '<script>\n' +
                'window.gestor = Object.assign({}, window.parent.gestor || {});\n' +
                'window.gestor.widgetsToAjax = ' + JSON.stringify(listaAjax) + ';\n' +
                '<\/script>\n';

            // (b) scripts controladores por módulo (uma única tag por módulo): por assinatura presente
            // (filtrados por WIDGET_SCRIPT_MODULES) e, adicionalmente, os declarados em widget_js_include.
            const incluidos = {};
            const injetar = (modulo) => {
                modulo = (modulo || '').trim();
                if (!modulo || incluidos[modulo]) return;
                incluidos[modulo] = true;
                includes += '<script src="' + raiz + modulo + '/widget.js?v=' + versao + '"><\/script>\n';
            };
            assinaturas.forEach((sig) => {
                const modulo = sig.split('->')[0].trim();
                if (WIDGET_SCRIPT_MODULES[modulo]) injetar(modulo);
            });
            if (explicitInclude) {
                Object.keys(explicitInclude).forEach((modulo) => {
                    if (explicitInclude[modulo]) injetar(modulo);
                });
            }
            return includes;
        }
    }

    // req-096 (BATCH-096): quando o HTML editado usa o motor PDF.js (`<div class="conn2flow-pdfjs">`),
    // o srcdoc do preview/editor visual precisa do inicializador do leitor — no site publicado esse
    // include vem de `gestor_pagina_pdf_viewer()`, que não roda no iframe estático montado por JS.
    // O próprio `pdf-viewer.js` carrega a lib do PDF.js sob demanda, então basta uma tag.
    function montarPdfViewerHead(htmlDoUsuario) {
        if (!htmlDoUsuario || htmlDoUsuario.indexOf('conn2flow-pdfjs') === -1) return '';
        const raiz = (typeof gestor !== 'undefined' && gestor.raiz) ? gestor.raiz : '';
        const versao = (typeof gestor !== 'undefined' && gestor.versao) ? gestor.versao : '';
        return `<script src="${raiz}interface/pdf-viewer.js?v=${versao}"><\/script>\n`;
    }

    function previewHtmlConteudo(htmlDoUsuario, cssDoUsuario, framework = 'fomantic-ui', extraParams = {}) {
        // req-040: script que renderiza os widgets (comentários) dentro do pré-visualizador.
        const widgetPreviewScript = `<script>(${widgetPreviewBootstrap.toString()})();<\/script>`;
        // req-160: o motor de captura precisa existir NESTE iframe.
        //
        // `updateCSSCompiled()` é chamado tanto pelo preview quanto pelo editor visual, mas
        // `HtmlEditorCssCapture` vive em `html-editor.js`, que só era injetado no editor visual. No
        // preview a captura falhava sempre com `motivo: 'sem-motor'` e, pela regra de preservação,
        // mantinha o valor anterior — vazio numa página nova. Quem montasse a página pelos modelos,
        // conferisse no preview e salvasse gravava `css_compiled` VAZIO, e a página publicada saía
        // sem CSS nenhum. Medido: página criada do zero com 360 classes aplicadas e 0 bytes gravados.
        //
        // `__c2fHtmlEditorNoAutoInit` (BATCH-075) impede o motor de instanciar a UI de edição sobre
        // o body — é o mesmo mecanismo que a Editbar usa. O preview segue sendo só visualização; o
        // que passa a existir é a API de captura.
        const capturaScript = `<script>window.__c2fHtmlEditorNoAutoInit = true;<\/script>
            ${window.HtmlEditorHelper.variablesEnvironment().htmlEditorScriptPath}`;
        // req-044 §3/§4: includes de cabeçalho dos widgets presentes (widgetsToAjax + *.widget.js).
        const widgetAssetsHead = montarWidgetAssetsHead(htmlDoUsuario, extraParams.widgetsToAjax || null);

        // Incluir o CSS do usuário, se existir
        if (cssDoUsuario && cssDoUsuario.length > 0) {
            cssDoUsuario = `<style data-c2f-tailwind-role="authored">${cssDoUsuario}</style>`;
        } else {
            cssDoUsuario = '';
        }

        // Incluir JS customizados
        const customScripts = extraParams.customScripts || false;

        if (customScripts) {
            let scriptsIncludes = '';
            customScripts.forEach(script => {
                if (script.src) {
                    scriptsIncludes += `<script src="${script.src}"><\/script>\n`;
                } else if (script.content) {
                    scriptsIncludes += `<script>${script.content}<\/script>\n`;
                }
            });
            cssDoUsuario += scriptsIncludes;
        }

        let iframeTitle = 'Fomantic UI Preview';
        let tailwindConfigScript = '';

        if (framework === 'tailwindcss') {
            tailwindConfigScript = tailwindPreviewIncludes();

            iframeTitle = 'Tailwind CSS Preview';
        }

        const publisherPage = ('publisherPage' in gestor.html_editor ? true : false);
        const publisherQuillClassDetected = ('publisherQuillClassDetected' in gestor && gestor.publisherQuillClassDetected ? true : false);

        if (publisherPage || publisherQuillClassDetected) {
            tailwindConfigScript += `
                <link rel="stylesheet" type="text/css" media="all" href="${htmlEditorAssetUrl('quill', 'quill.snow.css')}" data-c2f-css-role="quill" />
                <style data-c2f-css-role="quill">
                    .ql-editor {
                        font-family: Lato, system-ui, -apple-system, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                        font-size: 16px !important;
                        line-height: 1.5rem !important;
                        overflow-y: hidden !important;
                        color: rgba(0, 0, 0, 0.8);
                        border: none !important;
                        padding: 0 !important;
                        margin: 0 !important;
                    }
                    .ql-container.ql-snow{
                        border: none !important;
                        padding: 0 !important;
                        margin: 0 !important;
                    }
                </style>`;
        }

        // Layout mode: o HTML do usuário já é um documento completo, apenas injetar frameworks
        const alvoPreview = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');
        if (alvoPreview === 'layouts') {
            let fullHtml = htmlDoUsuario;
            // req-156: a mesma declaração de ordem de camadas do editor visual, para que os dois
            // ambientes resolvam a cascata pela mesma regra e não pela posição das folhas.
            let layoutIncludes = htmlEditorLayerOrderDeclaration(framework) + '\n';
            layoutIncludes += tailwindConfigScript + '\n';
            layoutIncludes += htmlEditorPreviewFrameworkIncludes(framework) + '\n';
            // req-160: o preview de layout captura pelo mesmo caminho e precisa do mesmo motor.
            layoutIncludes += capturaScript + '\n';
            layoutIncludes += cssDoUsuario + '\n';
            // req-044 §3/§4: widgetsToAjax + scripts controladores dos widgets presentes.
            layoutIncludes += widgetAssetsHead;
            // req-096: leitor PDF.js quando o conteúdo usa o motor B.
            layoutIncludes += montarPdfViewerHead(htmlDoUsuario);
            if (fullHtml.includes('<!-- pagina#css -->')) {
                fullHtml = fullHtml.replace('<!-- pagina#css -->', layoutIncludes + '<!-- pagina#css -->');
            } else if (fullHtml.match(/<\/head>/i)) {
                fullHtml = fullHtml.replace(/<\/head>/i, layoutIncludes + '</head>');
            }
            // req-040: renderizar os widgets também no preview do layout.
            if (fullHtml.match(/<\/body>/i)) {
                fullHtml = fullHtml.replace(/<\/body>/i, widgetPreviewScript + '\n</body>');
            }
            return fullHtml;
        }

        return `
			<!DOCTYPE html>
			<html lang="pt-br">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>${iframeTitle}</title>
				${htmlEditorLayerOrderDeclaration(framework)}
				${tailwindConfigScript}
				${htmlEditorPreviewFrameworkIncludes(framework)}
				${capturaScript}
				${widgetPreviewScript}
				${widgetAssetsHead}
				${montarPdfViewerHead(htmlDoUsuario)}
				${cssDoUsuario}
			</head>
			<body>
				${htmlDoUsuario}
			</body>
			</html>
		`;
    }

    function previewHtml() {
        // req-067: não tentar gerar a prévia se os editores de HTML/CSS ainda não foram instanciados.
        if (typeof CodeMirrorHtml === 'undefined' || !CodeMirrorHtml || typeof CodeMirrorCss === 'undefined' || !CodeMirrorCss) return;
        const iframe = $('#iframe-visualizacao-pagina');

        iframe.parent().find('.ui.dimmer').addClass('active');

        // Remover o dimmer quando o iframe terminar de carregar
        iframe.on('load', function () {
            iframe.parent().find('.ui.dimmer').removeClass('active');
        });

        // Pegar o HTML do usuário e filtrar o que está dentro do <body>
        let htmlDoUsuario = CodeMirrorHtml.getDoc().getValue();
        const cssDoUsuario = CodeMirrorCss.getDoc().getValue();

        const idFramework = frameworkCSS();

        // Substituir as variáveis LOCAIS do template ou simulação, se necessário
        htmlDoUsuario = publisherVariablesOrSimulation(htmlDoUsuario);

        // Substituir as variáveis do template ou valores, se necessário
        htmlDoUsuario = publisherVariablesOrValues(htmlDoUsuario);

        const montar = function (html) {
            // Incluir o HTML e CSS do usuário no conteúdo do iframe
            iframe.attr('srcdoc', previewHtmlConteudo(html, cssDoUsuario, idFramework));
            // Atualizar o código CSS no conteúdo do CodeMirror
            if (idFramework === 'tailwindcss') {
                updateCSSCompiled(iframe);
            } else {
                updateCSSCompiled(iframe, true);
            }
        };

        // req-093: resolve também as variáveis GLOBAIS (valor real, sem caixas) no preview — as LOCAIS
        // já foram tratadas acima (simulação); as globais desconhecidas do backend ficam literais.
        // Fallback ao HTML local em caso de falha do AJAX (preview nunca quebra).
        htmlEditorRenderVars(htmlDoUsuario, function (data) {
            montar((data && typeof data.values === 'string') ? data.values : htmlDoUsuario);
        });
    }

    function getUpdatedHtmlWithValues() {
        // req-067: sem editor de HTML instanciado, não há conteúdo do usuário para processar.
        if (typeof CodeMirrorHtml === 'undefined' || !CodeMirrorHtml) return '';
        // Pegar o HTML do usuário.
        let htmlDoUsuario = CodeMirrorHtml.getDoc().getValue();

        // Substituir as variáveis do template ou valores, se necessário
        htmlDoUsuario = publisherVariablesOrValues(htmlDoUsuario, true);

        return htmlDoUsuario;
    }

    window.getUpdatedHtmlWithValues = getUpdatedHtmlWithValues;

    // ===== publisher-highlights: API pública para o módulo notificar mudanças nas variáveis
    window.publisher_highlights_update_target_variables = function (vars) {
        if (!isHighlightsAlvo()) return;
        if (!Array.isArray(vars)) vars = [];

        publisher_fields_schema.template_map = vars.map(function (v) {
            const id = (v && typeof v === 'object') ? v.id : String(v);
            return { id: id, variable: '[[item#' + id + ']]', label: id, type: 'text' };
        });

        publisherVariablesSearch();
    };

    // ===== publisher-index: API pública para o módulo notificar mudanças nas variáveis (req-041 §3.1)
    window.publisher_index_update_target_variables = function (vars) {
        if (alvoAtual() !== 'publisher-index') return;
        if (!Array.isArray(vars)) vars = [];

        publisher_fields_schema.template_map = vars.map(function (v) {
            const id = (v && typeof v === 'object') ? v.id : String(v);
            return { id: id, variable: '[[item#' + id + ']]', label: id, type: 'text' };
        });

        publisherVariablesSearch();
    };


    function addVariableSkeleton(type, id, label) {
        const framework = frameworkCSS();
        const designMode = $('.publisher-design-mode-variables').length > 0 ? $('.publisher-design-mode-variables').dropdown('get value') : 'simple';

        // Encontrar wrapper de skeletons
        let wrapper = $('.hep-skeletons-wrapper');
        let typeContainer;

        if (designMode === 'sophisticated') {
            typeContainer = wrapper.find(`.hep-skeletons-${type}.hep-sophisticated.${framework}`);
        } else {
            typeContainer = wrapper.find(`.hep-skeletons-${type}.hep-simple`);
        }

        if (typeContainer.length === 0) {
            // Fallback Genérico: tenta o simpes se falhou o sofisticado, ou qualquer um se falhou o simples
            typeContainer = wrapper.find(`.hep-skeletons-${type}.hep-simple`);

            if (typeContainer.length === 0) {
                typeContainer = wrapper.find(`.hep-skeletons-${type}`);
            }
        }

        // Fallback especial para texto se for genericamente "text" e não achou
        if (typeContainer.length === 0 && type === 'text') {
            typeContainer = wrapper.find('.hep-skeletons-text');
        }

        // Pegar item aleatorio
        let items = typeContainer.find('.item');
        if (items.length > 0) {
            let randomItem = items.eq(Math.floor(Math.random() * items.length));
            let htmlSkeleton = randomItem.html();

            // Substituir variável (formato sensível ao alvo)
            let variable = alvoUsaItemVars() ? `[[item#${id}]]` : `[[publisher#${type}#${id}]]`;
            htmlSkeleton = htmlSkeleton.replace(/#variavel#/g, variable);

            // Criar nova ID de seção
            let total = totalDeSessoes() + 1;

            // Wrapper de Section
            // Se for sophisticated, usa padding, se for simples, section limpa ou com container minimo?
            // Manter consistencia com o framework escolhido para o wrapper outer
            let sectionContentClass = (framework === 'tailwindcss') ? 'container mx-auto px-4' : 'ui container';

            let sectionHtml = `<section data-id="${total}" data-title="${label}">
    <div class="${sectionContentClass}">
${htmlSkeleton.split('\n').map(line => line.trim()).join('\n')}
    </div>
</section>`;

            // Inserir no CodeMirror
            let currentHtml = CodeMirrorHtml.getDoc().getValue();

            // Se tiver </body>, inserir antes. Se não, no final.
            if (currentHtml.indexOf('</body>') > -1) {
                currentHtml = currentHtml.replace('</body>', sectionHtml + '\n</body>');
            } else {
                currentHtml += '\n' + sectionHtml;
            }

            currentHtml = cleanCodeString(currentHtml);

            CodeMirrorHtml.getDoc().setValue(currentHtml);

            // Atualizar lista de variáveis e sessões
            publisherVariablesSearch();
            menuDeSessoes();

            // Mudar para a aba de visualização da página
            contentPageTabChange('visualizacao-pagina');

            msg_sucesso_mostrar('Variável adicionada com sucesso!');
        }
    }

    // Listeners para botões de adicionar variáveis
    $(document.body).on('mouseup tap', '.add-variable-skeleton', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        let type = $(this).data('type');
        let id = $(this).data('id');
        let label = $(this).closest('tr').find('strong').text().trim();

        addVariableSkeleton(type, id, label);
    });

    $(document.body).on('mouseup tap', '#add-all-variables-skeleton', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        let missingRows = $('.hep-variables-table tbody tr').filter(function () {
            return !$(this).find('.hep-val-found-times').hasClass('hep-initially-hidden');
        });

        missingRows.each(function (index) {
            let btn = $(this).find('.add-variable-skeleton');
            let type = btn.data('type');
            let id = btn.data('id');
            let label = $(this).find('strong').text().trim();

            addVariableSkeleton(type, id, label);
        });

        msg_sucesso_mostrar('Todas as variáveis ausentes foram adicionadas!');
    });

    $(document.body).on('mouseup tap', '.remove-variable-skeleton', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        let type = $(this).data('type');
        let id = $(this).data('id');

        let html = CodeMirrorHtml.getDoc().getValue();

        // Regex para variable (formato sensível ao alvo)
        const regexStr = alvoUsaItemVars()
            ? `\\[\\[item#${id}\\]\\]`
            : `\\[\\[publisher#${type}#${id}\\]\\]`;
        const regex = new RegExp(regexStr, 'g');

        html = html.replace(regex, ' ');

        html = cleanCodeString(html);

        CodeMirrorHtml.getDoc().setValue(html);

        publisherVariablesSearch();
        contentPageTabChange('visualizacao-pagina');

        msg_sucesso_mostrar('Variável removida com sucesso!');
    });

    $(document.body).on('mouseup tap', '.remove-all-variables', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        if (!confirm('Tem certeza que deseja remover TODAS as variáveis do template?')) return false;

        let html = CodeMirrorHtml.getDoc().getValue();

        // Regex para todas as variáveis (sensível ao alvo)
        const regex = regexVariaveisGlobal();

        html = html.replace(regex, ' ');
        html = cleanCodeString(html);

        CodeMirrorHtml.getDoc().setValue(html);

        publisherVariablesSearch();
        contentPageTabChange('visualizacao-pagina');

        msg_sucesso_mostrar('Todas as variáveis foram removidas do template!');
    });

    $(document.body).on('mouseup tap', '.copy-to-clipboard', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const text = $(this).text().trim();
        navigator.clipboard.writeText(text).then(() => {
            // Flash effect or toast
            let originalColor = $(this).css('background-color');
            $(this).css('background-color', '#21ba45').css('color', 'white'); // green

            setTimeout(() => {
                $(this).css('background-color', '').css('color', '');
            }, 1000);

            if (typeof msg_sucesso_mostrar === 'function') {
                msg_sucesso_mostrar('Variável copiada para a área de transferência!');
            }
        }).catch(err => {
            console.error('Failed to copy: ', err);
            if (typeof msg_erro_mostrar === 'function') {
                msg_erro_mostrar('Erro ao copiar variável.');
            }
        });
    });

    // ===== IA Variables Control

    if (!gestor.html_editor.publisher_variables) gestor.html_editor.publisher_variables = [];

    // Validar se existe o template no HTML, se sim, guardar e remover do DOM.
    let publisherVariableTemplate = '';
    const publisherContainer = $('.page-modification-publisher .ui.labels');
    if (publisherContainer.find('.ui.label').length > 0) {
        publisherVariableTemplate = publisherContainer.find('.ui.label')[0].outerHTML;
        publisherContainer.empty();
    }

    function updatePublisherVariablesUI() {
        var container = $('.page-modification-publisher .ui.labels');
        container.empty();

        if (gestor.html_editor.publisher_variables && gestor.html_editor.publisher_variables.length > 0) {

            if (publisherVariableTemplate) {
                gestor.html_editor.publisher_variables.forEach(function (v, index) {
                    let html = publisherVariableTemplate;
                    html = html.replace(/#name#/g, v.name);
                    html = html.replace(/#type#/g, v.type);
                    html = html.replace(/#index#/g, index);

                    container.append(html);
                });
            }

            $('.page-modification-publisher').removeClass('hidden');
        } else {
            $('.page-modification-publisher').addClass('hidden');
        }

        // Atualizar menu de sessões.
        menuPages('sessao', { add_after: true, alertar: true });
    }

    $(document.body).on('mouseup tap', '.page-modification-publisher .delete.icon', function (e) {
        var index = $(this).data('index');
        gestor.html_editor.publisher_variables.splice(index, 1);
        updatePublisherVariablesUI();
    });

    $(document.body).on('mouseup tap', '.add-variable-ai', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        var variavel = $(this).attr('data-id');
        var tipo = $(this).attr('data-type');

        if (variavel && tipo) {
            var exists = gestor.html_editor.publisher_variables.find(v => v.name === variavel && v.type === tipo);
            if (!exists) {
                gestor.html_editor.publisher_variables.push({
                    name: variavel,
                    type: tipo
                });
                updatePublisherVariablesUI();
            }

            contentPageTabChange('assistente-ia');
        }
    });

    $(document.body).on('mouseup tap', '#add-all-variables-ai', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        let missingRows = $('.hep-variables-table tbody tr').filter(function () {
            return !$(this).find('.hep-val-found-times').hasClass('hep-initially-hidden');
        });

        missingRows.each(function () {
            let btn = $(this).find('.add-variable-ai');
            var variavel = btn.attr('data-id');
            var tipo = btn.attr('data-type');

            if (variavel && tipo) {
                var exists = gestor.html_editor.publisher_variables.find(v => v.name === variavel && v.type === tipo);
                if (!exists) {
                    gestor.html_editor.publisher_variables.push({
                        name: variavel,
                        type: tipo
                    });
                }
            }
        });

        updatePublisherVariablesUI();
        contentPageTabChange('assistente-ia');
    });

    // ===== Controles de modificação de página toda ou por sessão

    let total_sessoes = 0;

    function totalDeSessoes() {
        let total = 0;
        // req-067: sem editor de HTML instanciado, não há sessões a contar.
        if (typeof CodeMirrorHtml === 'undefined' || !CodeMirrorHtml) return total;
        const html = CodeMirrorHtml.getDoc().getValue();

        // Contar a quantidade total de tag sections no HTML e retornar esse valor.
        const regex = /<section\b[^>]*>([\s\S]*?)<\/section>/gi;
        let match;
        while ((match = regex.exec(html)) !== null) {
            total++;
        }

        return total;
    }

    function gestorPageModificationSectionUp(index, total) {
        if (index > 0) {
            $('.page-modification-section-up').removeClass('disabled');
        } else {
            $('.page-modification-section-up').addClass('disabled');
        }

        if (index < total - 1) {
            $('.page-modification-section-down').removeClass('disabled');
        } else {
            $('.page-modification-section-down').addClass('disabled');
        }
    }

    function menuDeSessoes() {
        // req-067: sem editor de HTML instanciado, não há menu de sessões a montar.
        if (typeof CodeMirrorHtml === 'undefined' || !CodeMirrorHtml) return;
        const html = CodeMirrorHtml.getDoc().getValue();

        const regex = /<section\b[^>]*>([\s\S]*?)<\/section>/gi;
        let match;

        let sessoes = [];

        while ((match = regex.exec(html)) !== null) {
            const sectionTag = match[0];
            const idMatch = sectionTag.match(/data-id=["']([^"']+)["']/i);
            const nomeMatch = sectionTag.match(/data-title=["']([^"']+)["']/i);

            const id = idMatch ? idMatch[1] : null;
            const nome = nomeMatch ? nomeMatch[1] : 'Sem nome';

            if (id) {
                sessoes.push({ id: id, nome: nome });
            }
        }

        const select = $('.ui.dropdown.page-modification-section-select');
        const currentValue = select.dropdown('get value');

        select.find('select').find('option').remove();

        if (sessoes.length > 0) {
            sessoes.forEach(function (sessao) {
                const option = $('<option></option>').attr('value', sessao.id).text(sessao.nome);
                select.find('select').append(option);
            });

            select.dropdown('refresh');

            let selectedIndex = 0;

            if (sessoes.find(sessao => sessao.id === currentValue)) {
                select.dropdown('set selected', currentValue, true);
                selectedIndex = sessoes.findIndex(sessao => sessao.id === currentValue);
            } else {
                select.dropdown('set selected', sessoes[0].id, true);
                selectedIndex = 0;
            }

            gestorPageModificationSectionUp(selectedIndex, sessoes.length);
        } else {
            select.dropdown('refresh');
            select.parent().addClass('disabled');
        }
    }

    $('.ui.dropdown.page-modification-section-select').dropdown({
        onChange: function (value, text, $selectedItem) {
            // Update buttons state based on new selection
            const select = $('.ui.dropdown.page-modification-section-select');
            const options = select.find('select option');
            let index = 0;

            options.each(function (i) {
                if ($(this).val() === value) {
                    index = i;
                    return false;
                }
            });

            gestorPageModificationSectionUp(index, options.length);
        }
    });

    function menuPages(opcao, params = {}) {
        total_sessoes = totalDeSessoes();

        if (opcao === 'sessao') {
            if (total_sessoes > 0) {
                menuDeSessoes();
                $('.page-modification-container').removeClass('hidden');

                if (params.add_after) {
                    setTimeout(function () {
                        $('.ui.dropdown.page-modification-target-select').dropdown('set selected', 'sessao', true);

                        const checkbox = $('input[name="page-modification-section-option"][value="new-after"]').parent();
                        checkbox.checkbox('check');
                    }, 1);
                }
            } else {
                if (params.alertar) $('#gestor-listener').trigger('alerta', { msg: 'Não foram detectadas sessões. Crie uma página nova e adicione sessões para utilizar esse recurso.' });
                $('.page-modification-container').addClass('hidden');
                setTimeout(function () {
                    $('.ui.dropdown.page-modification-target-select').dropdown('set selected', 'tudo', true);
                });
            }
        } else {
            $('.page-modification-container').addClass('hidden');
        }
    }

    function tipoModificationPage() {
        const tipo_modificacao = $('.ui.dropdown.page-modification-target-select').dropdown('get value');

        return tipo_modificacao ?? 'tudo';
    }

    function pageSessionID() {
        const sectionId = $('.ui.dropdown.page-modification-section-select').dropdown('get value');
        return sectionId ?? null;
    }

    function pageModificationContainerMove(target) {
        var pageModificationContainer = $('.page-modification-wrapper');

        $('.menu-pagina-conteudo[data-id="' + target + '"]').prepend(pageModificationContainer);

        if (gestor.html_editor.page_modification_auto_preview === undefined) {
            gestor.html_editor.page_modification_auto_preview = {};
        }

        switch (target) {
            case 'visualizacao-pagina':
                setTimeout(function () {
                    total_sessoes = totalDeSessoes();

                    if (total_sessoes > 0) {
                        pageModificationContainer.find('.page-modification-target-select').dropdown('set selected', 'sessao');
                    }

                    pageModificationContainer.find('.page-modification-target-select').addClass('disabled');
                    pageModificationContainer.find('.page-modification-section-options').addClass('hidden');
                    pageModificationContainer.find('.page-modification-auto-preview').addClass('hidden');

                    if (gestor.html_editor.page_modification_auto_preview[target] === undefined) {
                        gestor.html_editor.page_modification_auto_preview[target] = true;
                    }

                    pageModificationContainer.find('.page-modification-auto-preview').checkbox('check');

                }, 1);
                break;
            default:
                pageModificationContainer.find('.page-modification-target-select').removeClass('disabled');
                pageModificationContainer.find('.page-modification-section-options').removeClass('hidden');
                pageModificationContainer.find('.page-modification-auto-preview').removeClass('hidden');

                if (gestor.html_editor.page_modification_auto_preview[target] === undefined) {
                    gestor.html_editor.page_modification_auto_preview[target] = false;
                }

                if (gestor.html_editor.page_modification_auto_preview[target]) {
                    pageModificationContainer.find('.page-modification-auto-preview').checkbox('check');
                } else {
                    pageModificationContainer.find('.page-modification-auto-preview').checkbox('uncheck');
                }
        }

        gestor.html_editor.page_modification_current_target = target;
    }

    function sessaoOpcao() {
        let sessao_opcao = null;

        const sessao_options = ['target', 'new-before', 'new-after'];

        sessao_options.forEach(function (opcao) {
            const checkbox = $('input[name="page-modification-section-option"][value="' + opcao + '"]').parent();
            if (checkbox.checkbox('is checked')) {
                sessao_opcao = opcao;
                return false;
            }
        });

        return sessao_opcao ?? '';
    }

    function modificarPaginaConteudo(data = {}) {
        var html_gerado = data.html_gerado ? data.html_gerado : '';
        var css_gerado = data.css_gerado ? data.css_gerado : '';
        var sessao_id = data.sessao_id ? data.sessao_id : '';
        var sessao_opcao = data.sessao_opcao ? data.sessao_opcao : '';

        if (sessao_id && sessao_id.length > 0 && sessao_opcao && sessao_opcao.length > 0) {
            // Pegar o HTML completo atual
            let html_completo = CodeMirrorHtml.getDoc().getValue();

            // Marcar sessão alvo com data-menu-alvo="true" para manter a seleção
            html_completo = html_completo.replace(new RegExp(`(<section\\b[^>]*data-id=["']${sessao_id}["'][^>]*)>`, 'i'), (match, p1) => p1 + ' data-menu-alvo="true">');

            switch (sessao_opcao) {
                case 'target':
                    // Extrair o outerHTML da sessão.
                    const regex = new RegExp(`<section\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/section>`, 'i');
                    const match = html_completo.match(regex);

                    if (match && match[0]) {
                        // Substituir a sessão no HTML completo
                        const novo_html_completo = html_completo.replace(regex, html_gerado);

                        html_gerado = novo_html_completo;
                    }
                    break;
                case 'new-before':
                    // Colocar o html_gerado logo antes da sessão alvo
                    const regexBefore = new RegExp(`(<section\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/section>)`, 'i');
                    html_gerado = html_completo.replace(regexBefore, (match, p1) => html_gerado + '\n' + p1);
                    break;
                case 'new-after':
                    // Colocar o html_gerado logo depois da sessão alvo
                    const regexAfter = new RegExp(`(<section\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/section>)`, 'i');
                    html_gerado = html_completo.replace(regexAfter, (match, p1) => p1 + '\n' + html_gerado);
                    break;
            }
        }

        // Remover linhas em branco no início e fim do código.
        // E também remover linhas que estejam completamente em branco no meio do código.
        html_gerado = cleanCodeString(html_gerado, 'html');
        css_gerado = cleanCodeString(css_gerado, 'css');

        // Atualizar os `data-id` das sessões para evitar duplicidade. Começar sempre no `1` e ir somando.
        let sectionCounter = 1;
        let oldIds = [];
        html_gerado = html_gerado.replace(/<section\b[^>]*>/gi, function (match) {
            const idMatch = match.match(/data-id=["']([^"']+)["']/i);
            const oldId = idMatch ? idMatch[1] : null;
            oldIds.push(oldId);
            // Substituir ou adicionar data-id
            if (match.includes('data-id=')) {
                return match.replace(/data-id=["'][^"']*["']/i, 'data-id="' + sectionCounter++ + '"');
            } else {
                return match.replace('<section', '<section data-id="' + sectionCounter++ + '"');
            }
        });

        // Atualizar os CodeMirror com o código gerado.
        CodeMirrorHtml.getDoc().setValue(html_gerado);
        CodeMirrorCss.getDoc().setValue(css_gerado);

        CodeMirrorHtml.refresh();
        CodeMirrorCss.refresh();

        // Agora, após o menu ser atualizado pelo evento change, selecionar a sessão alvo e remover o atributo
        const htmlAtual = CodeMirrorHtml.getDoc().getValue();
        const alvoMatch = htmlAtual.match(/<section\b[^>]*data-menu-alvo="true"[^>]*>/i);
        if (alvoMatch) {
            const alvoTag = alvoMatch[0];
            const idMatch = alvoTag.match(/data-id=["']([^"']+)["']/i);
            if (idMatch) {
                const alvoId = idMatch[1];
                $('.ui.dropdown.page-modification-section-select').dropdown('set selected', alvoId, true);
                // Remover o atributo data-menu-alvo
                const htmlSemAlvo = htmlAtual.replace(/ data-menu-alvo="true"/gi, '');
                CodeMirrorHtml.getDoc().setValue(htmlSemAlvo);
                CodeMirrorHtml.refresh();
            }
        }
    }

    // req-067: o listener só pode ser registrado se o CodeMirror de HTML existir. Em telas
    // que não renderizam o editor (ex.: listar/visualizar do módulo forms), `.codemirror-html`
    // não está no DOM e `CodeMirrorHtml` fica undefined — registrar `.on` aqui quebraria a página.
    if (typeof CodeMirrorHtml !== 'undefined' && CodeMirrorHtml) {
        CodeMirrorHtml.on("change", function (instance, changeObj) {
            //var newContent = instance.getValue();

            const total_atual = totalDeSessoes();

            if (total_atual != total_sessoes) {
                total_sessoes = total_atual;
                menuDeSessoes();

                const tipo_modificacao = tipoModificationPage();

                menuPages(tipo_modificacao);
            }

            updatedCodeMirrorHtml();
        });
    }

    function updatedCodeMirrorHtml() {
        if ('updatedCodeMirrorHtml' in window && typeof window.updatedCodeMirrorHtml === 'function') {
            window.updatedCodeMirrorHtml();
        }
    }

    $(document.body).on('mouseup tap', '.page-modification-section-rename', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const sectionId = pageSessionID();

        if (sectionId && sectionId.length > 0) {
            // Get current title
            const html = CodeMirrorHtml.getDoc().getValue();
            const regex = new RegExp(`<section\\b[^>]*data-id=["']${sectionId}["'][^>]*data-title=["']([^"']*)["'][^>]*>`, 'i');
            const match = html.match(regex);
            let currentTitle = '';
            if (match && match[1]) {
                currentTitle = match[1];
            }

            // Set value in modal
            $('.page-modification-rename-modal input[name="new-session-name"]').val(currentTitle);

            $('.page-modification-rename-modal').modal({
                closable: false,
                onApprove: function () {
                    const newName = $('.page-modification-rename-modal input[name="new-session-name"]').val();

                    if (newName && newName.trim() !== '') {
                        let html = CodeMirrorHtml.getDoc().getValue();
                        // Update Title using robust regex replacement
                        const regexReplace = new RegExp(`(<section\\b[^>]*data-id=["']${sectionId}["'][^>]*data-title=["'])([^"']*)(["'][^>]*>)`, 'i');

                        if (regexReplace.test(html)) {
                            html = html.replace(regexReplace, `$1${newName}$3`);
                            CodeMirrorHtml.getDoc().setValue(html);

                            // Force menu update
                            menuDeSessoes();
                        }
                    }
                }
            }).modal('show');
        }
    });

    $(document.body).on('mouseup tap', '.page-modification-section-delete', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const sectionId = pageSessionID();

        if (!sectionId || sectionId.length === 0) return false;

        if (confirm('Tem certeza que deseja deletar permanentemente esta sessão?')) {
            let html = CodeMirrorHtml.getDoc().getValue();

            // Remover a sessão do HTML
            const regex = new RegExp(`<section\\b[^>]*data-id=["']${sectionId}["'][^>]*>[\\s\\S]*?<\\/section>`, 'i');
            html = html.replace(regex, '');

            // Remover linhas em branco no início e fim do código.
            // E também remover linhas que estejam completamente em branco no meio do código.
            html = cleanCodeString(html);

            // Atualizar o CodeMirror com o HTML atualizado.
            CodeMirrorHtml.getDoc().setValue(html);

            // Mudar para a aba de visualização da página
            const autoPreview = $('.page-modification-auto-preview').checkbox('is checked');
            if (autoPreview) {
                contentPageTabChange('visualizacao-pagina');
            }
        }
    });

    // Funcao generica para mover sessao

    function moverSessao(direcao) {
        const sectionId = pageSessionID();
        if (!sectionId) return;

        let html = CodeMirrorHtml.getDoc().getValue();

        // Encontrar todas as sessoes com seus IDs
        const regex = /<section\b[^>]*data-id=["']([^"']+)["'][^>]*>([\s\S]*?)<\/section>/gi;
        let matches = [];
        let match;
        while ((match = regex.exec(html)) !== null) {
            matches.push({
                full: match[0],
                id: match[1],
                index: match.index,
                length: match[0].length
            });
        }

        const currentIndex = matches.findIndex(m => m.id === sectionId);
        if (currentIndex === -1) return;

        let targetIndex = -1;
        if (direcao === 'up') {
            if (currentIndex > 0) targetIndex = currentIndex - 1;
        } else {
            if (currentIndex < matches.length - 1) targetIndex = currentIndex + 1;
        }

        if (targetIndex !== -1) {
            // Precisamos dos indices de inicio e fim de Current e Target
            const current = matches[currentIndex];
            const target = matches[targetIndex];

            // Garantir ordem (primeiro bloco, segundo bloco)
            const firstBlock = (direcao === 'up') ? target : current;
            const secondBlock = (direcao === 'up') ? current : target;

            // Texto entre eles (se houver)
            const middleStart = firstBlock.index + firstBlock.length;
            const middleEnd = secondBlock.index;
            const middleText = html.substring(middleStart, middleEnd);

            // Texto antes do primeiro
            const beforeText = html.substring(0, firstBlock.index);

            // Texto depois do segundo
            const afterText = html.substring(secondBlock.index + secondBlock.length);

            // Reconstroi invertendo first e second
            const newHtml = beforeText + secondBlock.full + middleText + firstBlock.full + afterText;

            CodeMirrorHtml.getDoc().setValue(newHtml);

            // Atualizar menu (IDs podem mudar? Nao o data-id)
            // Mas a ordem muda.
            menuDeSessoes();

            // Manter selecao
            $('.ui.dropdown.page-modification-section-select').dropdown('set selected', sectionId);

            const autoPreview = $('.page-modification-auto-preview').checkbox('is checked');
            if (autoPreview) {
                contentPageTabChange('visualizacao-pagina');
            }
        }
    }

    $(document.body).on('mouseup tap', '.page-modification-section-up', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
        if ($(this).hasClass('disabled')) return false;
        moverSessao('up');
    });

    $(document.body).on('mouseup tap', '.page-modification-section-down', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
        if ($(this).hasClass('disabled')) return false;
        moverSessao('down');
    });

    $('.ui.dropdown.page-modification-target-select')
        .dropdown({
            onChange: function (value, text, $selectedItem) {
                menuPages(value, { alertar: true });
            }
        });

    $('.page-modification-auto-preview').checkbox({
        onChecked: function () {
            gestor.html_editor.page_modification_auto_preview[gestor.html_editor.page_modification_current_target ?? 'default'] = true;
        },
        onUnchecked: function () {
            gestor.html_editor.page_modification_auto_preview[gestor.html_editor.page_modification_current_target ?? 'default'] = false;
        }
    });

    // ===== IA Interface

    // Configurar o callback e data para as requests de IA

    function iaRequestsCallback(p = {}) {
        var html_gerado = p.data.html_gerado ? p.data.html_gerado : '';
        var css_gerado = p.data.css_gerado ? p.data.css_gerado : '';
        var sessao_id = p.data.sessao_id ? p.data.sessao_id : '';
        var sessao_opcao = p.data.sessao_opcao ? p.data.sessao_opcao : '';

        modificarPaginaConteudo({
            html_gerado,
            css_gerado,
            sessao_id,
            sessao_opcao
        });

        // Resetar variáveis do publisher após a modificação
        const alvo = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

        switch (alvo) {
            case 'publisher':
                gestor.html_editor.publisher_variables = [];
                $('.page-modification-publisher .ui.labels').empty();
                $('.page-modification-publisher').addClass('hidden');
                break;
        }

        // Abrir o preview da página.
        contentPageTabChange('visualizacao-pagina');
    }

    function iaRequestsData(p = {}) {
        const tipo_modificacao = tipoModificationPage();

        let html = '';
        let css = CodeMirrorCss.getDoc().getValue();
        let sessao_id = '';
        let sessao_opcao = '';
        let publisher_variables = null;

        // Se for sessão, validar se uma sessão foi selecionada.
        if (tipo_modificacao === 'sessao') {
            const id_sessao = pageSessionID();
            sessao_opcao = sessaoOpcao();

            // Se não tiver sessão selecionada, retornar sem enviar a request.
            if (id_sessao && id_sessao.length > 0) {
                sessao_id = id_sessao;
                if (sessao_opcao == 'target') {
                    const html_completo = CodeMirrorHtml.getDoc().getValue();
                    // Extrair o outerHTML da sessão.
                    const regex = new RegExp(`<section\\b[^>]*data-id=["']${id_sessao}["'][^>]*>([\\s\\S]*?)<\\/section>`, 'i');
                    const match = html_completo.match(regex);

                    if (match && match[0]) {
                        html = match[0].trim();
                    }
                }
            }
        } else {
            html = CodeMirrorHtml.getDoc().getValue();
        }

        // Coletar variáveis do publisher, se houver
        const alvo = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

        switch (alvo) {
            case 'publisher':
                publisher_variables = gestor.html_editor.publisher_variables ? gestor.html_editor.publisher_variables : null;
                break;
        }

        const framework_css = frameworkCSS();

        return {
            ajaxOpcao: 'html-editor-ia-requests', data: {
                html: html,
                css: css,
                framework_css: framework_css,
                sessao_id,
                publisher_variables,
                sessao_opcao
            }
        };
    }

    $('#gestor-listener').trigger('ia-data', {
        requestsCallback: iaRequestsCallback,
        requestsData: iaRequestsData
    });

    // ===== ImagePick - Comunicação com o iframe do editor visual =====

    (function initImagePickHandler() {
        // Variável para armazenar a configuração atual do imagepick
        let imagepickConfig = null;
        let imagepickModalInitialized = false;

        // Listener para receber mensagens do iframe
        window.addEventListener('message', function (e) {
            try {
                const data = JSON.parse(e.data);

                // Mensagem do iframe pedindo para abrir o modal de seleção de imagem
                if (data.action === 'html-editor-imagepick-open') {
                    imagepickConfig = data.config;
                    openImagePickModal(imagepickConfig);
                }

                // Mensagem do iframe de arquivos com a imagem selecionada
                if (data.moduloId === 'admin-arquivos' || data.moduloId === 'arquivos') {
                    // Verificar se temos uma configuração ativa do imagepick do html-editor
                    if (!imagepickConfig) return;

                    const dados = JSON.parse(decodeURI(data.data));

                    // Corrigido: match retorna array, usar test() ou verificar se match não é null
                    if (dados.tipo && /^image\//.test(dados.tipo)) {
                        // Preparar dados da imagem selecionada
                        const imageData = {
                            id: dados.id,
                            caminho: dados.caminho,
                            imgSrc: dados.imgSrc,
                            nome: dados.nome,
                            tipo: dados.tipo,
                            data: dados.data
                        };

                        // Enviar para o iframe do editor visual
                        const previewIframe = document.getElementById('iframe-preview');
                        if (previewIframe && previewIframe.contentWindow) {
                            previewIframe.contentWindow.postMessage(JSON.stringify({
                                action: 'html-editor-imagepick-selected',
                                imageData: imageData
                            }), '*');
                        }

                        // Fechar modal de seleção de arquivos
                        $('.ui.modal.iframePagina').modal('hide');

                        // Limpar configuração após uso
                        imagepickConfig = null;
                    } else if (imagepickConfig && imagepickConfig.alertas) {
                        // Usar o sistema de alerta do gestor se disponível
                        if (typeof alerta === 'function') {
                            alerta({ msg: imagepickConfig.alertas.naoImagem });
                        } else {
                            $('#gestor-listener').trigger('alerta', { msg: imagepickConfig.alertas.naoImagem });
                        }
                    }
                }
            } catch (error) {
                // Ignorar mensagens não JSON
            }
        });

        // Função para abrir o modal de seleção de imagem
        function openImagePickModal(config) {
            if (!config || !config.modal) return;

            // Configurar o modal para permitir múltiplos modais
            const modal = $('.ui.modal.iframePagina');

            // Inicializar o modal com allowMultiple apenas uma vez
            if (!imagepickModalInitialized) {
                modal.modal({
                    allowMultiple: true,
                    observeChanges: true,
                    onHidden: function () {
                        // Limpar configuração quando o modal for fechado manualmente
                        // (mas manter se foi fechado por seleção de imagem)
                    }
                });
                imagepickModalInitialized = true;
            }

            modal.find('.header').html(config.modal.head);
            modal.find('.cancel.button').html(config.modal.cancel);

            // Limpar e configurar o iframe
            const iframe = modal.find('iframe');
            try {
                iframe.get(0).contentWindow.document.write('<body></body>');
            } catch (e) {
                // Ignorar erro de cross-origin se ocorrer
            }
            iframe.attr('src', config.modal.url);

            // Mostrar loader e abrir modal
            iframe.off('load').on('load', function () {
                modal.dimmer('hide');
            });

            modal.dimmer('show');
            modal.modal('show');
        }
    })();

    // req-045: kickoff da aba ativa E inicialização do tab `.menuContainerPagina` rodam SÓ aqui,
    // no fim do $(document).ready — depois de TODAS as const/let/function locais estarem
    // inicializadas (fora da Temporal Dead Zone). Há dois gatilhos síncronos que chamam
    // previewHtml()/pageModificationContainerMove() (→ WIDGET_SCRIPT_MODULES ~L1544 /
    // total_sessoes ~L2056): (1) contentPageTabHandler() e (2) o `onLoad` que o Fomantic dispara
    // SÍNCRONAMENTE ao inicializar o `.tab()`. Ambos precisam vir após essas declarações.
    // Ordem preservada do original (handler antes do init do tab).
    contentPageTabHandler();

    $('.menuContainerPagina .item').tab({
        onLoad: function (tabPath, parameterArray, historyEvent) {
            switch (tabPath) {
                case 'visualizacao-pagina':
                    pageModificationContainerMove(tabPath);
                    previewHtml();
                    break;
                case 'modelos':
                    modelosCarregar();
                    pageModificationContainerMove(tabPath);
                    break;
                case 'assistente-ia':
                    pageModificationContainerMove(tabPath);
                    if (gestor.ai.activated) {
                        window.AITabActiveHandler();
                    }
                    break;
                case 'visualizacao-codigo':
                    codeTabHandler();
                    break;
                case 'publisher-variables':
                    publisherVariablesSearch();
                    break;
            }

            localStorage.setItem(gestor.moduloId + tabIdContent, tabPath);
        }
    });
});
