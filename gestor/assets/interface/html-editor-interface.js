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

    function modelosCarregar(forcar = false) {
        if (modelos_carregando && !forcar) return;

        modelos_carregando = true;

        // Mostrar loading
        $('#modelos-cards').hide();
        $('#modelos-loading').show();

        const framework_css = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');

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
                    break;
                case 'visualizacao-codigo':
                    codeTabHandler();
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
				<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.css">
				<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
				<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.js"></script>
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

        const idFramework = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');

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

    $(document.body).on('mouseup tap', '.editorHtmlVisual.button', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        editorHtmlVisual();
    });

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

        const idFramework = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');
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
        updatedHtml = updatedHtml.split('\n').filter(line => line.trim() !== '').join('\n').trim();

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
				<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.css">
				<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
				<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.js"></script>
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
        const htmlDoUsuario = CodeMirrorHtml.getDoc().getValue();
        const cssDoUsuario = CodeMirrorCss.getDoc().getValue();

        const idFramework = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');

        iframe.attr('srcdoc', previewHtmlConteudo(htmlDoUsuario, cssDoUsuario, idFramework));

        // Atualizar o código CSS no conteúdo do CodeMirror
        if (idFramework === 'tailwindcss') {
            updateCSSCompiled(iframe);
        } else {
            updateCSSCompiled(iframe, true);
        }
    }

    // ===== Controles de modificação de página toda ou por sessão

    let total_sessoes = 0;

    function totalDeSessoes() {
        let total = 0;
        const html = CodeMirrorHtml.getDoc().getValue();

        // Contar a quantidade total de tag sessions no HTML e retornar esse valor.
        const regex = /<session\b[^>]*>([\s\S]*?)<\/session>/gi;
        let match;
        while ((match = regex.exec(html)) !== null) {
            total++;
        }

        return total;
    }

    function menuDeSessoes() {
        const html = CodeMirrorHtml.getDoc().getValue();

        const regex = /<session\b[^>]*>([\s\S]*?)<\/session>/gi;
        let match;

        let sessoes = [];

        while ((match = regex.exec(html)) !== null) {
            const sessionTag = match[0];
            const idMatch = sessionTag.match(/data-id=["']([^"']+)["']/i);
            const nomeMatch = sessionTag.match(/data-title=["']([^"']+)["']/i);

            const id = idMatch ? idMatch[1] : null;
            const nome = nomeMatch ? nomeMatch[1] : 'Sem nome';

            if (id) {
                sessoes.push({ id: id, nome: nome });
            }
        }

        const select = $('.ui.dropdown.page-modification-session-select');
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

    function menuPages(opcao, alertar = false) {
        total_sessoes = totalDeSessoes();

        if (opcao === 'sessao') {
            if (total_sessoes > 0) {
                menuDeSessoes();
                $('.page-modification-container').removeClass('hidden');
            } else {
                if (alertar) $('#gestor-listener').trigger('alerta', { msg: 'Não foram detectadas sessões. Crie uma página nova e adicione sessões para utilizar esse recurso.' });
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
        const sessionId = $('.ui.dropdown.page-modification-session-select').dropdown('get value');
        return sessionId ?? null;
    }

    function pageModificationContainerMove(target) {
        var pageModificationContainer = $('.page-modification-wrapper');

        $('.menu-pagina-conteudo[data-id="' + target + '"]').prepend(pageModificationContainer);
    }

    function sessaoOpcao() {
        let sessao_opcao = null;

        const sessao_options = ['target', 'new-before', 'new-after'];

        sessao_options.forEach(function (opcao) {
            const checkbox = $('input[name="page-modification-session-option"][value="' + opcao + '"]').parent();
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
            html_completo = html_completo.replace(new RegExp(`(<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*)>`, 'i'), '$1 data-menu-alvo="true">');

            switch (sessao_opcao) {
                case 'target':
                    // Extrair o outerHTML da sessão.
                    const regex = new RegExp(`<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/session>`, 'i');
                    const match = html_completo.match(regex);

                    if (match && match[0]) {
                        // Substituir a sessão no HTML completo
                        const novo_html_completo = html_completo.replace(regex, html_gerado);

                        html_gerado = novo_html_completo;
                    }
                    break;
                case 'new-before':
                    // Colocar o html_gerado logo antes da sessão alvo
                    const regexBefore = new RegExp(`(<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/session>)`, 'i');
                    html_gerado = html_completo.replace(regexBefore, html_gerado + '\n$1');
                    break;
                case 'new-after':
                    // Colocar o html_gerado logo depois da sessão alvo
                    const regexAfter = new RegExp(`(<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/session>)`, 'i');
                    html_gerado = html_completo.replace(regexAfter, '$1\n' + html_gerado);
                    break;
            }
        }

        // Remover linhas em branco no início e fim do código.
        // E também remover linhas que estejam completamente em branco no meio do código.
        html_gerado = html_gerado.split('\n').filter(line => line.trim() !== '').join('\n').trim();
        css_gerado = css_gerado.split('\n').filter(line => line.trim() !== '').join('\n').trim();

        // Atualizar os `data-id` das sessões para evitar duplicidade. Começar sempre no `1` e ir somando.
        let sessionCounter = 1;
        let oldIds = [];
        html_gerado = html_gerado.replace(/<session\b[^>]*>/gi, function (match) {
            const idMatch = match.match(/data-id=["']([^"']+)["']/i);
            const oldId = idMatch ? idMatch[1] : null;
            oldIds.push(oldId);
            // Substituir ou adicionar data-id
            if (match.includes('data-id=')) {
                return match.replace(/data-id=["'][^"']*["']/i, 'data-id="' + sessionCounter++ + '"');
            } else {
                return match.replace('<session', '<session data-id="' + sessionCounter++ + '"');
            }
        });

        // Atualizar os CodeMirror com o código gerado.
        CodeMirrorHtml.getDoc().setValue(html_gerado);
        CodeMirrorCss.getDoc().setValue(css_gerado);

        CodeMirrorHtml.refresh();
        CodeMirrorCss.refresh();

        // Agora, após o menu ser atualizado pelo evento change, selecionar a sessão alvo e remover o atributo
        const htmlAtual = CodeMirrorHtml.getDoc().getValue();
        const alvoMatch = htmlAtual.match(/<session\b[^>]*data-menu-alvo="true"[^>]*>/i);
        if (alvoMatch) {
            const alvoTag = alvoMatch[0];
            const idMatch = alvoTag.match(/data-id=["']([^"']+)["']/i);
            if (idMatch) {
                const alvoId = idMatch[1];
                $('.ui.dropdown.page-modification-session-select').dropdown('set selected', alvoId, true);
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

    $(document.body).on('mouseup tap', '.page-modification-session-delete', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const sessionId = pageSessionID();

        if (sessionId && sessionId.length > 0) {
            let html = CodeMirrorHtml.getDoc().getValue();

            // Remover a sessão do HTML
            const regex = new RegExp(`<session\\b[^>]*data-id=["']${sessionId}["'][^>]*>[\\s\\S]*?<\\/session>`, 'i');
            html = html.replace(regex, '');

            // Remover linhas em branco no início e fim do código.
            // E também remover linhas que estejam completamente em branco no meio do código.
            html = html.split('\n').filter(line => line.trim() !== '').join('\n').trim();

            // Atualizar o CodeMirror com o HTML atualizado.
            CodeMirrorHtml.getDoc().setValue(html);

            // Mudar para a aba de visualização da página
            contentPageTabChange('visualizacao-pagina');
        }
    });

    $('.ui.dropdown.page-modification-target-select')
        .dropdown({
            onChange: function (value, text, $selectedItem) {
                menuPages(value, true);
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

        // Abrir o preview da página.
        contentPageTabChange('visualizacao-pagina');
    }

    function iaRequestsData(p = {}) {
        const tipo_modificacao = tipoModificationPage();

        let html = '';
        let css = CodeMirrorCss.getDoc().getValue();
        let sessao_id = '';
        let sessao_opcao = '';

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
                    const regex = new RegExp(`<session\\b[^>]*data-id=["']${id_sessao}["'][^>]*>([\\s\\S]*?)<\\/session>`, 'i');
                    const match = html_completo.match(regex);

                    if (match && match[0]) {
                        html = match[0].trim();
                    }
                }
            }
        } else {
            html = CodeMirrorHtml.getDoc().getValue();
        }

        return {
            ajaxOpcao: 'html-editor-ia-requests', data: {
                html: html,
                css: css,
                framework_css: $('#framework-css').dropdown('get value'),
                sessao_id,
                sessao_opcao
            }
        };
    }

    $('#gestor-listener').trigger('ia-data', {
        requestsCallback: iaRequestsCallback,
        requestsData: iaRequestsData
    });
});