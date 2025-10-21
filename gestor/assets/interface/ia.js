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
            loadAIDimmer(true);
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

            loadAIDimmer(false);
        },
        error: function (txt) {
            switch (txt.status) {
                case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                default:
                    console.log('ERROR AJAX - ' + this.ajaxOpcao + ' - Dados:');
                    console.log(txt);
                    loadAIDimmer(false);
            }
        },
        successCallback: function (response) { },
        successNotOkCallback: function (response) { }
    };

    // ===== Initial Data 

    var dataRequest = {};

    $('#gestor-listener').on('ia-data', function (e, p) {
        iaInitialData(p);
    });

    function loadAIDimmer(show = true) {
        if (show) {
            $('.ai-conteiner .dimmer').addClass('active');
        } else {
            $('.ai-conteiner .dimmer').removeClass('active');
        }
    }

    function iaInitialData(p = {}) {
        dataRequest.requestsCallback = ('requestsCallback' in p ? p.requestsCallback : function () { });
        dataRequest.requestsData = ('requestsData' in p ? p.requestsData : function () { });
    }

    // ===== Codemirror 

    var ai_codemirrors_instances = new Array();

    var ai_prompt_textarea = document.getElementsByClassName("ai-prompt-textarea");

    if (ai_prompt_textarea.length > 0) {
        for (var i = 0; i < ai_prompt_textarea.length; i++) {
            var codeMirrorPrompt = CodeMirror.fromTextArea(ai_prompt_textarea[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "markdown",
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

            codeMirrorPrompt.setSize('100%', 500);
            ai_codemirrors_instances.push(codeMirrorPrompt);
        }
    }

    var ai_mode_textarea = document.getElementsByClassName("ai-mode-textarea");

    if (ai_mode_textarea.length > 0) {
        for (var i = 0; i < ai_mode_textarea.length; i++) {
            var codeMirrorMode = CodeMirror.fromTextArea(ai_mode_textarea[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "markdown",
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

            codeMirrorMode.setSize('100%', 500);
            ai_codemirrors_instances.push(codeMirrorMode);
        }
    }

    var ai_return_textarea = document.getElementsByClassName("ai-return-textarea");

    if (ai_return_textarea.length > 0) {
        for (var i = 0; i < ai_return_textarea.length; i++) {
            var codeMirrorReturn = CodeMirror.fromTextArea(ai_return_textarea[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "markdown",
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

            codeMirrorReturn.setSize('100%', 500);
            ai_codemirrors_instances.push(codeMirrorReturn);
        }
    }

    // ===== Fomantic UI

    var AITabActive = localStorage.getItem(gestor.moduloId + 'AITabActive');

    if (AITabActive !== null) {
        $('.AIMenu .item').tab('change tab', AITabActive);

        switch (AITabActive) {
            case 'prompt':
                codeMirrorPrompt.refresh();
                break;
            case 'mode':
                codeMirrorMode.refresh();
                break;
        }
    }


    $('.AIMenu .item').tab({
        onLoad: function (tabPath, parameterArray, historyEvent) {
            switch (tabPath) {
                case 'prompt':
                    codeMirrorPrompt.refresh();
                    break;
                case 'mode':
                    codeMirrorMode.refresh();
                    break;
                case 'config':
                    codeMirrorReturn.refresh();
                    break;
            }

            localStorage.setItem(gestor.moduloId + 'AITabActive', tabPath);
        }
    });

    // ===== Prompt Events

    let actualPromptId = null;

    codeMirrorPrompt.on("change", function (instance, changeObj) {
        var newContent = instance.getValue();

        if (newContent.length > 0) {
            $('.ai-prompt-clear').removeClass('disabled');
            if (actualPromptId !== null) $('.ai-prompt-edit').removeClass('disabled');
            if (actualPromptId !== null) $('.ai-prompt-del').removeClass('disabled');
            $('.ai-prompt-new').removeClass('disabled');
        } else {
            $('.ai-prompt-clear').addClass('disabled');
            if (actualPromptId !== null) $('.ai-prompt-edit').addClass('disabled');
            if (actualPromptId !== null) $('.ai-prompt-del').addClass('disabled');
            $('.ai-prompt-new').addClass('disabled');
        }
    });

    // ===== Limpar Prompt

    $(document.body).on('mouseup tap', '.ai-prompt-clear', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        codeMirrorPrompt.getDoc().setValue('');
        codeMirrorPrompt.refresh();
        $('.ui.ai-prompt-select').dropdown('clear', true);
    });

    // ===== Editar Prompt

    $(document.body).on('mouseup tap', '.ai-prompt-edit', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const ajax = ajaxDefault;

        ajax.ajaxOpcao = 'ia-prompt-edit';
        ajax.data.ajaxOpcao = ajax.ajaxOpcao;
        ajax.data.params = {
            target: gestor.ia.alvo,
            prompt_id: actualPromptId,
            prompt: codeMirrorPrompt.getDoc().getValue()
        };
        ajax.successCallback = function (response) {

        };
        ajax.successNotOkCallback = function (response) {
            if (response !== undefined && 'status' in response)
                if (response.status === 'error') {
                    msg_erro_mostrar(response.message);
                }
        };

        $.ajax(ajax);
    });

    // ===== Salvar Novo Prompt

    $(document.body).on('mouseup tap', '.ai-prompt-new', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        $('.ai-prompt-save-modal').modal('show');
    });

    function enviarPromptSalvar(nome) {
        const ajax = ajaxDefault;
        ajax.ajaxOpcao = 'ia-prompt-new';
        ajax.data.ajaxOpcao = ajax.ajaxOpcao;
        ajax.data.params = {
            target: gestor.ia.alvo,
            prompt: codeMirrorPrompt.getDoc().getValue(),
            nome
        };
        ajax.successCallback = function (response) {
            $('.ui.ai-prompt-select').find('select').append(response.option);
            $('.ui.ai-prompt-select').dropdown('refresh');
            setTimeout(function () {
                $('.ui.ai-prompt-select').dropdown('set selected', response.id, true);
                actualPromptId = response.id;
                if (actualPromptId !== null) {
                    $('.ai-prompt-del').removeClass('disabled');
                    $('.ai-prompt-edit').removeClass('disabled');
                }
            });
        };
        ajax.successNotOkCallback = function (response) {
            if (response !== undefined && 'status' in response)
                if (response.status === 'error') {
                    msg_erro_mostrar(response.message);
                }
        };

        $.ajax(ajax);
    }

    $('.ai-prompt-save-modal').modal({
        onApprove: function () {
            const form = $('.ai-prompt-save-form');

            form.form('validate form');

            if (form.form('is valid')) {
                enviarPromptSalvar(form.form('get value', 'prompt-nome'));
                form.form('reset');
                return true;
            }

            return false;
        }
    });

    $('.ai-prompt-save-form')
        .form({
            inline: true,
            fields: {
                name: {
                    identifier: 'name',
                    rules: [
                        {
                            type: 'empty',
                            prompt: gestor.ia.msgs.prompt_name_empty
                        }
                    ]
                },
            }
        });

    // Deletar Prompt

    $(document.body).on('mouseup tap', '.ai-prompt-del', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        $('.ai-prompt-del-modal').modal('show');
    });

    function enviarPromptDeletar() {
        if (actualPromptId === null) return;

        const ajax = ajaxDefault;
        ajax.ajaxOpcao = 'ia-prompt-del';
        ajax.data.ajaxOpcao = ajax.ajaxOpcao;
        ajax.data.params = {
            target: gestor.ia.alvo,
            prompt_id: actualPromptId
        };
        ajax.successCallback = function (response) {
            $('.ui.ai-prompt-select').dropdown('clear', true);
            $('.ui.ai-prompt-select').find('select').find('option[value="' + actualPromptId + '"]').remove();
            $('.ui.ai-prompt-select').dropdown('refresh');
            actualPromptId = null;
            $('.ai-prompt-del').addClass('disabled');
            $('.ai-prompt-edit').addClass('disabled');
        };
        ajax.successNotOkCallback = function (response) {
            if (response !== undefined && 'status' in response)
                if (response.status === 'error') {
                    msg_erro_mostrar(response.message);
                }
        };

        $.ajax(ajax);
    }

    $('.ai-prompt-del-modal').modal({
        onApprove: function () {
            enviarPromptDeletar();
        }
    });

    // ===== Buscar Prompts

    $(document.body).on('change', '.ai-prompt-select', function (e) {
        var ia = ('ia' in gestor ? gestor.ia : null);
        if (ia === null) return;

        var prompt_id = $(this).dropdown('get value');

        const ajax = ajaxDefault;

        ajax.ajaxOpcao = 'ia-prompts';
        ajax.data.ajaxOpcao = ajax.ajaxOpcao;
        ajax.data.params = {
            target: gestor.ia.alvo,
            prompt_id
        };
        ajax.successCallback = function (response) {
            codeMirrorPrompt.getDoc().setValue(response.prompt);
            codeMirrorPrompt.refresh();
            actualPromptId = prompt_id;
            if (actualPromptId !== null) {
                $('.ai-prompt-del').removeClass('disabled');
                $('.ai-prompt-edit').removeClass('disabled');
            }
        };

        $.ajax(ajax);
    });

    // ===== Buscar Modos

    $(document.body).on('change', '.ai-mode-select', function (e) {
        var ia = ('ia' in gestor ? gestor.ia : null);
        if (ia === null) return;

        var mode_id = $(this).dropdown('get value');

        const ajax = ajaxDefault;

        ajax.ajaxOpcao = 'ia-modos';
        ajax.data.ajaxOpcao = ajax.ajaxOpcao;
        ajax.data.params = {
            target: gestor.ia.alvo,
            mode_id
        };
        ajax.successCallback = function (response) {
            codeMirrorMode.getDoc().setValue(response.prompt);
            codeMirrorMode.refresh();
        };

        $.ajax(ajax);
    });

    // ===== Enviar Prompt

    $(document.body).on('mouseup tap', '.ai-send-prompt', function (e) {
        if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

        const ia = ('ia' in gestor ? gestor.ia : null);
        if (ia === null) return;

        const target = ('alvo' in ia ? ia.alvo : null);
        const prompt = codeMirrorPrompt.getDoc().getValue().trim();
        const mode = codeMirrorMode.getDoc().getValue().trim();
        const server_id = $('.ui.ai-connection-select').dropdown('get value');
        const model = $('.ui.ai-model-select').dropdown('get value');
        const requestsData = dataRequest.requestsData();

        const ajax = ajaxDefault;

        ajax.ajaxOpcao = ('ajaxOpcao' in requestsData ? requestsData.ajaxOpcao : '');
        ajax.data.ajaxOpcao = ajax.ajaxOpcao;
        ajax.data.target = target;
        ajax.data.prompt = prompt;
        ajax.data.mode = mode;
        ajax.data.server_id = server_id;
        ajax.data.model = (model.length > 0 ? model : null);
        ajax.data.data = ('data' in requestsData ? requestsData.data : '');

        ajax.successCallback = function (response) {
            if (response.data !== undefined && 'status' in response.data)
                if (response.data.status === 'error') {
                    msg_erro_mostrar(response.data.message);
                }

            // Colocar todos os dados retornados na textarea
            var returnRaw = '';

            // Pegar todos os dados de dados.data e incluir na variável returnRaw
            if (response.data && typeof response.data === 'object') {
                for (var key in response.data) {
                    if (response.data.hasOwnProperty(key)) {
                        var value = response.data[key];
                        if (typeof value === 'object') {
                            value = JSON.stringify(value, null, 2);
                        }
                        returnRaw += key + ': ' + value + '\n';
                    }
                }
            }

            $(`.ai-return-raw`).removeClass('hidden');
            codeMirrorReturn.getDoc().setValue(returnRaw);
            codeMirrorReturn.refresh();

            dataRequest.requestsCallback({
                status: 'success',
                data: response.data
            });
        };
        ajax.successNotOkCallback = function (response) {
            if (response !== undefined && 'status' in response)
                if (response.status === 'error') {
                    msg_erro_mostrar(response.message);
                }
        };

        $.ajax(ajax);
    });

    $(document.body).on('mouseup tap', '.ai-conteiner .ui.negative.message .close.icon', function (e) {
        e.preventDefault();
        e.stopPropagation();

        msg_erro_resetar();
    });

    function msg_erro_resetar() {
        $('.ai-conteiner .ui.negative.message').attr('style', '').addClass('hidden');
    }

    function msg_erro_mostrar(mensagem) {
        $('.ai-conteiner .ui.negative.message .ai-error-message').html(mensagem);
        $('.ai-conteiner .ui.negative.message').removeClass('hidden');
        $('.ai-conteiner .ui.negative.message').transition('bounce');
    }

});