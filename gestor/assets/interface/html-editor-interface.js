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
    let publisher_table_tr_skeleton = null;

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
        const framework_css = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');

        return framework_css ?? 'fomantic-ui';
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

        // Não precisa de refresh para cards
    }

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
        contentPageTabChange('visualizacao-pagina');
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

            CodeMirrorCss.setSize('100%', 500);
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

            CodeMirrorCssCompiled.setSize('100%', 500);
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

            CodeMirrorHtml.setSize('100%', 500);
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

            CodeMirrorHtmlExtraHead.setSize('100%', 500);
            codemirrors_instances.push(CodeMirrorHtmlExtraHead);
        }
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

    contentPageTabHandler();

    function contentPageTabChange(tabID = null) {
        if (tabID !== null) {
            $('.menuContainerPagina .item').tab('change tab', tabID);
        }
    }

    $('.menuContainerPagina .item').tab({
        onLoad: function (tabPath, parameterArray, historyEvent) {
            switch (tabPath) {
                case 'visualizacao-pagina':
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

    // ===== Backup Campo Mudar

    $('#gestor-listener').on('adminPaginasBackupCampo', function (e, p) {
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
                CodeMirrorHtmlExtraHead.getDoc().setValue(valor);
                CodeMirrorHtmlExtraHead.refresh();
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
            tailwindConfigScript = `<!-- CDN do TailwindCSS -->
				<script src="https://cdn.tailwindcss.com"></script>`;
            iframeTitle = 'Tailwind CSS Preview';
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

        iframe.parent().find('.ui.dimmer').addClass('active');

        // Remover o dimmer quando o iframe terminar de carregar
        iframe.on('load', function () {
            iframe.parent().find('.ui.dimmer').removeClass('active');
        });
        // Pegar o HTML do usuário e filtrar o que está dentro do <body>
        const htmlDoUsuario = filtrarHtmlBody(CodeMirrorHtml.getDoc().getValue()).trim();
        const cssDoUsuario = filtrarHtmlBody(CodeMirrorCss.getDoc().getValue()).trim();

        // Atualizar o CodeMirror com o HTML filtrado.
        CodeMirrorHtml.getDoc().setValue(htmlDoUsuario);

        const idFramework = frameworkCSS();

        iframe.attr('srcdoc', editorHtmlVisualConteudo(htmlDoUsuario, cssDoUsuario, idFramework));

        $('.previsualizar.modal')
            .modal('show')
            ;

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

    $(document.body).on('mouseup tap', '.publisherVariablesOrSimulation', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        toggleActiveButton($(this));
        previewHtml();
    });

    // Botões de mudança do Editor HTML Visual.
    $(document.body).on('mouseup tap', '.screenPagina', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        $('.previsualizar.modal').removeClass('longer tiny fullscreen');

        switch ($(this).data('option')) {
            case 'desktop':
                $('.previsualizar.modal').addClass('fullscreen');
                break;
            case 'tablet':
                $('.previsualizar.modal').addClass('longer');
                break;
            case 'mobile':
                $('.previsualizar.modal').addClass('tiny');
                break;
        }
    });

    $(document.body).on('mouseup tap', '.previsualizarConfirmar, .previsualizarVoltar', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const iframe = $('#iframe-preview')[0];
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

        // Remover elementos de sistema adicionados pelo Fomantic UI ou Tailwind CSS ou Editor HTML
        // Garantir que todas as ocorrências sejam removidas
        while ($(iframeDoc).find('#html-editor-modal').length > 0) {
            $(iframeDoc).find('#html-editor-modal').remove();
        }
        while ($(iframeDoc).find('#html-editor-overlay').length > 0) {
            $(iframeDoc).find('#html-editor-overlay').remove();
        }
        while ($(iframeDoc).find('.ui.dimmer.modals').length > 0) {
            $(iframeDoc).find('.ui.dimmer.modals').remove();
        }

        // Atualizar o código HTML no conteúdo do CodeMirror
        const body = $(iframeDoc).find('body');
        const bodyElement = body[0];

        let updatedHtml = bodyElement.innerHTML;

        // Remover linhas em branco no início e fim do código.
        // E também remover linhas que estejam completamente em branco no meio do código.
        updatedHtml = cleanCodeString(updatedHtml);

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

            const allStyleTags = $(iframeDoc).find('head > style');

            const tailwindStyleElement = allStyleTags[allStyleTags.length - 1];

            if (tailwindStyleElement) {
                const generatedCss = tailwindStyleElement.innerHTML;

                CodeMirrorCssCompiled.getDoc().setValue(generatedCss);
            }
        }, 750);
    }

    // Função para gerar o conteúdo da página do pré-visualizador.
    function previewHtmlConteudo(htmlDoUsuario, cssDoUsuario, framework = 'fomantic-ui') {
        // Incluir o CSS do usuário, se existir
        if (cssDoUsuario && cssDoUsuario.length > 0) {
            cssDoUsuario = `<style>${cssDoUsuario}</style>`;
        } else {
            cssDoUsuario = '';
        }

        let iframeTitle = 'Fomantic UI Preview';
        let tailwindConfigScript = '';

        if (framework === 'tailwindcss') {
            tailwindConfigScript = `<!-- CDN do TailwindCSS -->
				<script src="https://cdn.tailwindcss.com"></script>`;
            iframeTitle = 'Tailwind CSS Preview';
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

        // Substituir as variáveis do publisher ou simulação, se necessário
        htmlDoUsuario = publisherVariablesOrSimulation(htmlDoUsuario);

        // Incluir o HTML e CSS do usuário no conteúdo do iframe
        iframe.attr('srcdoc', previewHtmlConteudo(htmlDoUsuario, cssDoUsuario, idFramework));

        // Atualizar o código CSS no conteúdo do CodeMirror
        if (idFramework === 'tailwindcss') {
            updateCSSCompiled(iframe);
        } else {
            updateCSSCompiled(iframe, true);
        }
    }

    // ===== Publisher Options

    // Substituição de Variáveis do Publisher ou Simulação no Preview

    function publisherVariablesOrSimulation(html = '') {
        const alvo = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

        if (alvo == 'publisher') {
            const simulacao = $('.publisherVariablesOrSimulation[data-id="simulation"]').hasClass('active');

            if (simulacao) {
                const framework = frameworkCSS();
                const designMode = $('.publisher-design-mode-simulation').length > 0 ? $('.publisher-design-mode-simulation').dropdown('get value') : 'simple';

                // Regex para encontrar variáveis no formato [[publisher#TIPO#ID]]
                const regex = /\[\[publisher#(.+?)#(.+?)\]\]/g;

                html = html.replace(regex, function (match, tipo, id, offset, fullString) {
                    // Check context: Are we inside an HTML tag attribute?
                    let isInsideTag = false;

                    // Look backwards for the nearest opening '<' or closing '>'
                    let i = offset - 1;
                    while (i >= 0) {
                        if (fullString[i] === '>') {
                            // We found a closing tag before an opening one, so we are OUTSIDE a tag
                            isInsideTag = false;
                            break;
                        }
                        if (fullString[i] === '<') {
                            // We found an opening tag without a closing one in between, so we are INSIDE a tag
                            isInsideTag = true;
                            break;
                        }
                        i--;
                    }

                    // Buscar valores de simulação baseados no modo de design
                    let simulationItems;

                    // Force simple mode if inside a tag (to avoid breaking attributes like alt="", src="")
                    const effectiveMode = isInsideTag ? 'simple' : designMode;

                    if (effectiveMode === 'sophisticated') {
                        simulationItems = $(`.hep-simulation-${tipo}.hep-sophisticated.${framework} .item`);
                    } else {
                        // Modo simples: buscar genéricos explicitamente
                        simulationItems = $(`.hep-simulation-${tipo}.hep-simple .item`);
                    }

                    // Fallback: Tenta pegar qualquer um do tipo se a busca específica falhar
                    if (simulationItems.length === 0) {
                        simulationItems = $(`.hep-simulation-${tipo} .item`);
                    }

                    if (simulationItems.length > 0) {
                        // Sortear um valor aleatório
                        const randomIndex = Math.floor(Math.random() * simulationItems.length);
                        // Usar html() para pegar o conteúdo exato (incluindo entidades HTML) e inserir de volta no HTML
                        const randomValue = simulationItems.eq(randomIndex).html().trim();
                        return randomValue;
                    }
                    // Se não encontrar valores, retornar a variável original
                    return match;
                });
            }
        }

        return html;
    }

    // Variáveis controles.

    function publisherVariablesSearch() {
        if (!publisher_fields_schema.template_map) return;

        setTimeout(function () {
            let html = CodeMirrorHtml.getDoc().getValue();

            // Regex para encontrar variáveis no formato [[publisher#TIPO#ID]]
            const regex = /\[\[publisher#([^#]+)#([^\]]+)\]\]/g;
            let foundVariables = new Set();
            let match;

            while ((match = regex.exec(html)) !== null) {
                foundVariables.add(match[0]);
            }

            // Mapear dados para a tabela
            let tableData = publisher_fields_schema.template_map.map(item => {
                // Encontrar definição do campo se existir
                let fieldDef = publisher_fields_schema.fields ? publisher_fields_schema.fields.find(f => f.id === item.id) : null;

                // Extrair tipo do variable se não tiver fieldDef (caso variables do template não linkadas)
                let type = fieldDef ? fieldDef.type : 'text';
                if (!fieldDef) {
                    let parts = item.variable.split('#');
                    if (parts.length >= 2) type = parts[1];
                }

                return {
                    id: item.id,
                    variable: item.variable,
                    type: type,
                    label: fieldDef ? fieldDef.label : item.id,
                    found: foundVariables.has(item.variable)
                };
            });

            publisherTableVariables(tableData);
        }, 100);
    }

    function publisherTableVariables(data) {
        let table = $('.hep-variables-table');
        let tableBody = table.find('tbody');

        // Guardar skeleton inicial se ainda não tiver
        if (!publisher_table_tr_skeleton) {
            let tr = tableBody.find('tr').first();
            if (tr.length > 0) {
                publisher_table_tr_skeleton = tr.clone();
            }
        }

        if (!publisher_table_tr_skeleton) return;

        tableBody.empty();

        let countFound = 0;
        let countTotal = data.length;

        data.forEach(item => {
            let row = publisher_table_tr_skeleton.clone();

            // Substituir placeholders no HTML do row
            let html = row.html();
            html = html.replace(/#val-label#/g, item.label);
            html = html.replace(/#val-type#/g, item.type);
            html = html.replace(/#val-id#/g, item.id);
            row.html(html);

            // Controle de visibilidade dos ícones
            if (item.found) {
                countFound++;
                row.find('.hep-val-found-check').removeClass('hep-initially-hidden');
                row.find('.hep-val-found-times').addClass('hep-initially-hidden');

                row.find('.hep-val-options-buttons').addClass('hep-initially-hidden');
                row.find('.hep-val-options-ok').removeClass('hep-initially-hidden');
            } else {
                row.find('.hep-val-found-check').addClass('hep-initially-hidden');
                row.find('.hep-val-found-times').removeClass('hep-initially-hidden');

                row.find('.hep-val-options-buttons').removeClass('hep-initially-hidden');
                row.find('.hep-val-options-ok').addClass('hep-initially-hidden');
            }

            tableBody.append(row);
        });

        // Mensagens de Status
        $('.hep-all-found-msg, .hep-some-missing-msg, .hep-all-missing-msg').addClass('hep-initially-hidden');
        $('.remove-all-variables').addClass('hep-initially-hidden');

        if (countTotal === 0) {
            $('.hep-all-missing-msg').removeClass('hep-initially-hidden');
        } else if (countFound === countTotal) {
            $('.hep-all-found-msg').removeClass('hep-initially-hidden');
            $('.remove-all-variables').removeClass('hep-initially-hidden');
        } else {
            $('.hep-some-missing-msg').removeClass('hep-initially-hidden');
            if (countFound > 0) {
                $('.remove-all-variables').removeClass('hep-initially-hidden');
            }
        }

        // Mostrar Tabela
        if (countTotal > 0) {
            table.removeClass('hep-initially-hidden');
        } else {
            table.addClass('hep-initially-hidden');
        }
    }

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

            // Substituir variável
            let variable = `[[publisher#${type}#${id}]]`;
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

        // Regex para variable: [[publisher#TIPO#ID]]
        const regexStr = `\\[\\[publisher#${type}#${id}\\]\\]`;
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

        // Regex para todas as variáveis: [[publisher#TIPO#ID]]
        const regex = /\[\[publisher#[^#]+#[^\]]+\]\]/g;

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

            if (sessoes.find(sessao => sessao.id === currentValue)) {
                select.dropdown('set selected', currentValue, true);
            } else {
                select.dropdown('set selected', sessoes[0].id, true);
            }

            select.parent().removeClass('disabled');
        }
    }

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
    });

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

        if (sectionId && sectionId.length > 0) {
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
            contentPageTabChange('visualizacao-pagina');
        }
    });

    $('.ui.dropdown.page-modification-target-select')
        .dropdown({
            onChange: function (value, text, $selectedItem) {
                menuPages(value, { alertar: true });
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

        return {
            ajaxOpcao: 'html-editor-ia-requests', data: {
                html: html,
                css: css,
                framework_css: $('#framework-css').dropdown('get value'),
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
});