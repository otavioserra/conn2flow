$(document).ready(function () {

    // ===== Ajax Default

    var ajaxDefault = {
        type: 'POST',
        url: gestor.raiz + gestor.moduloCaminho + '/',
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

        // Mostrar loading
        $('#modelos-cards').hide();
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
        var normalizedQuery = searchQuery.toLowerCase().trim();

        var cards = cardsContainer.querySelectorAll('.modelo-card');
        var visibleCount = 0;

        cards.forEach(function (card) {
            var header = card.querySelector('.header');
            var meta = card.querySelector('.meta');
            var modeloId = card.getAttribute('data-modelo-id') || '';

            var headerText = header ? header.textContent.toLowerCase() : '';
            var metaText = meta ? meta.textContent.toLowerCase() : '';

            var matches = normalizedQuery === '' ||
                headerText.includes(normalizedQuery) ||
                metaText.includes(normalizedQuery) ||
                modeloId.toLowerCase().includes(normalizedQuery);

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

    // Event listener para input de busca de modelos (debounced)
    $(document).on('input', '#modelos-search-input', function () {
        var input = this;
        clearTimeout(input._debounceTimer);
        input._debounceTimer = setTimeout(function () {
            modelosFiltrar(input.value);
        }, 150);
    });

    // Event listener para tecla Escape no campo de busca
    $(document).on('keydown', '#modelos-search-input', function (e) {
        if (e.key === 'Escape') {
            this.value = '';
            modelosFiltrar('');
            this.blur();
        }
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
                if (gestor.editorHtmlAtivo) {
                    if (codeHtmlChanged) {
                        valor = indentHtml(valor);

                        CodeMirrorHtml.getDoc().setValue(valor);
                        CodeMirrorHtml.refresh();
                    } else {
                        tinymce.activeEditor.setContent(valor, { format: 'raw' });
                    }
                } else {
                    CodeMirrorHtml.getDoc().setValue(valor);
                    CodeMirrorHtml.refresh();
                }
                break;
            case 'html-extra-head':
                if (typeof CodeMirrorHtmlExtraHead !== 'undefined') {
                    CodeMirrorHtmlExtraHead.getDoc().setValue(valor);
                    CodeMirrorHtmlExtraHead.refresh();
                }
                break;
            case 'css':
                CodeMirrorCss.getDoc().setValue(valor);
                CodeMirrorCss.refresh();
                break;
            case 'css_compiled':
                CodeMirrorCssCompiled.getDoc().setValue(valor);
                CodeMirrorCssCompiled.refresh();
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

    // Função para gerar o conteúdo da página do editor HTML visual.
    function editorHtmlVisualConteudo(htmlDoUsuario, cssDoUsuario, framework = 'fomantic-ui') {
        // Incluir o script e variáveis do editor HTML
        const { htmlEditorModalHtml, htmlEditorVars, htmlEditorScriptPath } = window.HtmlEditorHelper.variablesEnvironment();

        // Incluir o CSS do usuário, se existir
        if (cssDoUsuario && cssDoUsuario.length > 0) {
            cssDoUsuario = `<style>${cssDoUsuario}</style>`;
        } else {
            cssDoUsuario = '';
        }

        let iframeTitle = 'Fomantic UI Preview';
        let tailwindConfigScript = '';

        if (framework === 'tailwindcss') {
            tailwindConfigScript = `<!-- CDN do TailwindCSS v4 -->
				<script src="https://unpkg.com/@tailwindcss/browser@4"></script>`;
            tailwindConfigScript += `\n<link rel="stylesheet" type="text/css" media="all" href="${gestor.raiz}tailwindcss/system-output.css?v=${gestor.versao}" />`;

            let incluirJSPrimeiro = false;
            if ('projectTailwindcssConfig' in gestor.html_editor) {
                if ('incluirJSPrimeiro' in gestor.html_editor.projectTailwindcssConfig) {
                    if (gestor.html_editor.projectTailwindcssConfig.incluirJSPrimeiro) {
                        incluirJSPrimeiro = gestor.html_editor.projectTailwindcssConfig.incluirJSPrimeiro;
                    }
                }
            }

            let projectCssTailwindcss = '';
            if ('projectCssTailwindcss' in gestor.html_editor) {
                projectCssTailwindcss = gestor.html_editor.projectCssTailwindcss;
            }
            let projectJavascriptTailwindcss = '';
            if ('projectJavascriptTailwindcss' in gestor.html_editor) {
                projectJavascriptTailwindcss = gestor.html_editor.projectJavascriptTailwindcss;
            }

            if (incluirJSPrimeiro) {
                tailwindConfigScript += `${projectJavascriptTailwindcss.length > 0 ? "\n" + projectJavascriptTailwindcss : ''}${projectCssTailwindcss.length > 0 ? "\n" + projectCssTailwindcss : ''}`;
            } else {
                tailwindConfigScript += `${projectCssTailwindcss.length > 0 ? "\n" + projectCssTailwindcss : ''}${projectJavascriptTailwindcss.length > 0 ? "\n" + projectJavascriptTailwindcss : ''}`;
            }

            iframeTitle = 'Tailwind CSS Preview';
        }

        const publisherPage = ('publisherPage' in gestor.html_editor ? true : false);
        const publisherQuillClassDetected = ('publisherQuillClassDetected' in gestor && gestor.publisherQuillClassDetected ? true : false);

        if (publisherPage || publisherQuillClassDetected) {
            tailwindConfigScript += `
                <link rel="stylesheet" type="text/css" media="all" href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" />
                <style>
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

        // CodeMirror CDN - mesma versão usada em html-editor.php
        const codemirrorVersion = '5.65.20';
        const codemirrorIncludes = `
            <!-- CodeMirror CSS -->
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/codemirror.min.css" />
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/theme/tomorrow-night-bright.css" />
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/addon/dialog/dialog.css" />
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/addon/display/fullscreen.css" />
            <!-- CodeMirror JS -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/codemirror.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/addon/selection/active-line.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/addon/edit/matchbrackets.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/addon/edit/closetag.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/addon/edit/closebrackets.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/addon/display/fullscreen.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/mode/xml/xml.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/mode/css/css.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/mode/javascript/javascript.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/${codemirrorVersion}/mode/htmlmixed/htmlmixed.js"></script>
        `;

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
                ${tailwindConfigScript}
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.css">
                <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"><\/script>
                <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.js"><\/script>
                ${codemirrorIncludes}
                ${codemirrorInitScript}
                ${htmlEditorVars}
                ${htmlEditorScriptPath}
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
				${tailwindConfigScript}
				<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.css">
				<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
				<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.js"></script>
				${codemirrorInitScript}
				${codemirrorIncludes}
				${htmlEditorVars}
				${htmlEditorScriptPath}
				${cssDoUsuario}
			</head>
			<body>
				${htmlDoUsuario}
				${htmlEditorModalHtml}
			</body>
			</html>
		`;
    }

    function editorHtmlVisual() {
        const iframe = $('#iframe-preview');
        const alvo = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

        iframe.parent().find('.ui.dimmer').addClass('active');

        // Remover o dimmer quando o iframe terminar de carregar
        iframe.on('load', function () {
            iframe.parent().find('.ui.dimmer').removeClass('active');
        });

        let htmlDoUsuario;
        const cssDoUsuario = CodeMirrorCss.getDoc().getValue().trim();

        if (alvo === 'layouts') {
            // Para layouts, manter o HTML completo (documento inteiro).
            // Armazenar head e atributos originais para reconstrução no save.
            const fullHtml = CodeMirrorHtml.getDoc().getValue();
            htmlDoUsuario = fullHtml.trim();

            const headMatch = fullHtml.match(/<head[^>]*>([\s\S]*?)<\/head>/i);
            window._layoutOriginalHead = headMatch ? headMatch[0] : '<head></head>';

            const htmlMatch = fullHtml.match(/<html([^>]*)>/i);
            window._layoutHtmlAttrs = htmlMatch ? htmlMatch[1] : '';

            const doctypeMatch = fullHtml.match(/<!DOCTYPE[^>]*>/i);
            window._layoutDoctype = doctypeMatch ? doctypeMatch[0] : '<!DOCTYPE html>';
        } else {
            // Para páginas/componentes, filtrar apenas o conteúdo do <body>
            htmlDoUsuario = filtrarHtmlBody(CodeMirrorHtml.getDoc().getValue()).trim();

            // Atualizar o CodeMirror com o HTML filtrado.
            CodeMirrorHtml.getDoc().setValue(htmlDoUsuario);
        }

        const idFramework = frameworkCSS();

        iframe.attr('srcdoc', editorHtmlVisualConteudo(htmlDoUsuario, cssDoUsuario, idFramework));

        // Configurar e mostrar modal com suporte a múltiplos modais (para o imagepick)
        $('.previsualizar.modal')
            .modal({
                allowMultiple: true,
                observeChanges: true
            })
            .modal('show');

        // Atualizar o código CSS no conteúdo do CodeMirror
        if (idFramework === 'tailwindcss') {
            updateCSSCompiled(iframe);
        } else {
            updateCSSCompiled(iframe, true);
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
                '#html-editor-tailwind-styler,.conn2flow-dnd-placeholder,.ui.dimmer.modals';
            iframeDoc.querySelectorAll(sistemaSel).forEach(el => el.remove());
            bodyContent = iframeDoc.body ? iframeDoc.body.innerHTML : '';
        }

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

        $.formSubmitNormal();
    });

    function updateCSSCompiled(iframe, clean = false) {
        if (clean) {
            CodeMirrorCssCompiled.getDoc().setValue('');
            return;
        }

        var iframeObject = iframe[0];
        setTimeout(function () {
            const iframeDoc = iframeObject.contentDocument || iframeObject.contentWindow.document;

            // Função recursiva para coletar seletores de qualquer folha de estilo (inclusive dentro de @layer, @media, etc.)
            function collectSelectors(rules, selectorSet) {
                if (!rules) return;
                for (let j = 0; j < rules.length; j++) {
                    const rule = rules[j];
                    if (rule.type === CSSRule.STYLE_RULE) {
                        selectorSet.add(rule.selectorText);
                    } else if (rule.cssRules) {
                        collectSelectors(rule.cssRules, selectorSet);
                    }
                }
            }

            // 1. Coleta seletores globais já existentes nas folhas de estilos core
            const systemSelectors = new Set();
            for (let i = 0; i < iframeDoc.styleSheets.length; i++) {
                const sheet = iframeDoc.styleSheets[i];
                try {
                    // Mapeia tanto system-output.css quanto output.css
                    if (sheet.href && (sheet.href.indexOf('system-output.css') !== -1 || sheet.href.indexOf('output.css') !== -1)) {
                        collectSelectors(sheet.cssRules || sheet.rules, systemSelectors);
                    }
                } catch (e) {
                    console.warn("Nao foi possivel ler a folha de estilo do sistema para filtragem:", e);
                }
            }

            // Função recursiva para filtrar regras redundantes, preservando a estrutura de @media, @layer, etc.
            function filterRules(rules, selectorSet) {
                let css = "";
                if (!rules) return css;
                for (let i = 0; i < rules.length; i++) {
                    const rule = rules[i];
                    if (rule.type === CSSRule.STYLE_RULE) {
                        if (!selectorSet.has(rule.selectorText)) {
                            css += rule.cssText + "\n";
                        }
                    }
                    else if (rule.type === CSSRule.MEDIA_RULE) {
                        const mediaContent = filterRules(rule.cssRules, selectorSet);
                        if (mediaContent.trim()) {
                            css += `@media ${rule.media.mediaText} {\n${mediaContent}}\n`;
                        }
                    }
                    else if (rule.constructor.name === "CSSLayerBlockRule" || rule.type === 17) {
                        const layerContent = filterRules(rule.cssRules, selectorSet);
                        if (layerContent.trim()) {
                            const layerName = rule.name ? ` ${rule.name}` : "";
                            css += `@layer${layerName} {\n${layerContent}}\n`;
                        }
                    }
                    else if (rule.type === CSSRule.SUPPORTS_RULE) {
                        const supportsContent = filterRules(rule.cssRules, selectorSet);
                        if (supportsContent.trim()) {
                            css += `@supports ${rule.conditionText} {\n${supportsContent}}\n`;
                        }
                    }
                    else {
                        // Mantém outras diretivas e regras (@theme, @keyframes etc.)
                        css += rule.cssText + "\n";
                    }
                }
                return css;
            }

            // 2. Localiza a tag de estilo gerada pelo Tailwind CDN (normalmente a última tag <style> no head)
            const allStyleTags = iframeDoc.querySelectorAll('head > style');
            const tailwindStyleElement = allStyleTags[allStyleTags.length - 1];

            if (tailwindStyleElement) {
                let generatedCss = "";
                const sheet = tailwindStyleElement.sheet;

                // 3. Extrai e filtra as regras estruturadas (suporta Tailwind v3 e v4)
                if (sheet && sheet.cssRules && sheet.cssRules.length > 0) {
                    generatedCss = filterRules(sheet.cssRules, systemSelectors);
                } else {
                    // Fallback se não conseguir ler o objeto sheet (Tailwind v3 innerHTML bruto)
                    generatedCss = tailwindStyleElement.innerHTML;
                }

                // 4. Atualiza o editor CodeMirror com as classes exclusivas filtradas
                CodeMirrorCssCompiled.getDoc().setValue(generatedCss.trim());
            }
        }, 750);
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
            var url = g.raiz + g.moduloCaminho + '/';
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
                    if (jq) {
                        jq.ajax({
                            type: 'POST', url: url, dataType: 'json', data: data,
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
    const WIDGET_SCRIPT_MODULES = { 'galleries': true, 'publisher-index': true, 'menus': true, 'forms': true };

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
            if (!assinaturas.length) return '';

            const raiz = (typeof gestor !== 'undefined' && gestor.raiz) ? gestor.raiz : '';
            const versao = (typeof gestor !== 'undefined' && gestor.versao) ? gestor.versao : '';

            // (a) variável widgetsToAjax (replica o que o PHP gera no page load do site real).
            const listaAjax = assinaturas.join('<#;>');
            let includes = '<script>\n' +
                'window.gestor = Object.assign({}, window.parent.gestor || {});\n' +
                'window.gestor.widgetsToAjax = ' + JSON.stringify(listaAjax) + ';\n' +
                '<\/script>\n';

            // (b) scripts controladores por módulo (mapa fixo; uma única tag por módulo).
            const incluidos = {};
            assinaturas.forEach((sig) => {
                const modulo = sig.split('->')[0].trim();
                if (!WIDGET_SCRIPT_MODULES[modulo] || incluidos[modulo]) return;
                incluidos[modulo] = true;
                includes += '<script src="' + raiz + modulo + '/widget.js?v=' + versao + '"><\/script>\n';
            });
            return includes;
        }
    }

    function previewHtmlConteudo(htmlDoUsuario, cssDoUsuario, framework = 'fomantic-ui', extraParams = {}) {
        // req-040: script que renderiza os widgets (comentários) dentro do pré-visualizador.
        const widgetPreviewScript = `<script>(${widgetPreviewBootstrap.toString()})();<\/script>`;
        // req-044 §3/§4: includes de cabeçalho dos widgets presentes (widgetsToAjax + *.widget.js).
        const widgetAssetsHead = montarWidgetAssetsHead(htmlDoUsuario, extraParams.widgetsToAjax || null);

        // Incluir o CSS do usuário, se existir
        if (cssDoUsuario && cssDoUsuario.length > 0) {
            cssDoUsuario = `<style>${cssDoUsuario}</style>`;
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
            tailwindConfigScript = `<!-- CDN do TailwindCSS v4 -->
				<script src="https://unpkg.com/@tailwindcss/browser@4"></script>`;
            tailwindConfigScript += `\n<link rel="stylesheet" type="text/css" media="all" href="${gestor.raiz}tailwindcss/system-output.css?v=${gestor.versao}" />`;

            let incluirJSPrimeiro = false;
            if ('projectTailwindcssConfig' in gestor.html_editor) {
                if ('incluirJSPrimeiro' in gestor.html_editor.projectTailwindcssConfig) {
                    if (gestor.html_editor.projectTailwindcssConfig.incluirJSPrimeiro) {
                        incluirJSPrimeiro = gestor.html_editor.projectTailwindcssConfig.incluirJSPrimeiro;
                    }
                }
            }

            let projectCssTailwindcss = '';
            if ('projectCssTailwindcss' in gestor.html_editor) {
                projectCssTailwindcss = gestor.html_editor.projectCssTailwindcss;
            }
            let projectJavascriptTailwindcss = '';
            if ('projectJavascriptTailwindcss' in gestor.html_editor) {
                projectJavascriptTailwindcss = gestor.html_editor.projectJavascriptTailwindcss;
            }

            if (incluirJSPrimeiro) {
                tailwindConfigScript += `${projectJavascriptTailwindcss.length > 0 ? "\n" + projectJavascriptTailwindcss : ''}${projectCssTailwindcss.length > 0 ? "\n" + projectCssTailwindcss : ''}`;
            } else {
                tailwindConfigScript += `${projectCssTailwindcss.length > 0 ? "\n" + projectCssTailwindcss : ''}${projectJavascriptTailwindcss.length > 0 ? "\n" + projectJavascriptTailwindcss : ''}`;
            }

            iframeTitle = 'Tailwind CSS Preview';
        }

        const publisherPage = ('publisherPage' in gestor.html_editor ? true : false);
        const publisherQuillClassDetected = ('publisherQuillClassDetected' in gestor && gestor.publisherQuillClassDetected ? true : false);

        if (publisherPage || publisherQuillClassDetected) {
            tailwindConfigScript += `
                <link rel="stylesheet" type="text/css" media="all" href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" />
                <style>
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
            let layoutIncludes = tailwindConfigScript + '\n';
            layoutIncludes += `<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.css">\n`;
            layoutIncludes += `<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"><\/script>\n`;
            layoutIncludes += `<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.js"><\/script>\n`;
            layoutIncludes += cssDoUsuario + '\n';
            // req-044 §3/§4: widgetsToAjax + scripts controladores dos widgets presentes.
            layoutIncludes += widgetAssetsHead;
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
				${tailwindConfigScript}
				<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.css">
				<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
				<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.js"></script>
				${widgetPreviewScript}
				${widgetAssetsHead}
				${cssDoUsuario}
			</head>
			<body>
				${htmlDoUsuario}
			</body>
			</html>
		`;
    }

    function previewHtml() {
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

        // Substituir as variáveis do template ou simulação, se necessário
        htmlDoUsuario = publisherVariablesOrSimulation(htmlDoUsuario);

        // Substituir as variáveis do template ou valores, se necessário
        htmlDoUsuario = publisherVariablesOrValues(htmlDoUsuario);

        // Incluir o HTML e CSS do usuário no conteúdo do iframe
        iframe.attr('srcdoc', previewHtmlConteudo(htmlDoUsuario, cssDoUsuario, idFramework));

        // Atualizar o código CSS no conteúdo do CodeMirror
        if (idFramework === 'tailwindcss') {
            updateCSSCompiled(iframe);
        } else {
            updateCSSCompiled(iframe, true);
        }
    }

    function getUpdatedHtmlWithValues() {
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
