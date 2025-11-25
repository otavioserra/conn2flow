$(document).ready(function () {

    $('.ui.dropdown')
        .dropdown()
        ;

    // Codmirror para logs de tests de envio de emails em modo debug

    var codemirrors_instances = new Array();

    var codemirror_logs = document.getElementsByClassName("codemirror-logs");

    if (codemirror_logs.length > 0) {
        for (var i = 0; i < codemirror_logs.length; i++) {
            var CodeMirrorLogs = CodeMirror.fromTextArea(codemirror_logs[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "xml",
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

            CodeMirrorLogs.setSize('100%', 500);
            codemirrors_instances.push(CodeMirrorLogs);
        }
    }

    // Inicializar tabs do Fomantic-UI

    var tabActive = localStorage.getItem(gestor.moduloId + 'tabActive');
    if (tabActive !== null) {
        $('.menu .item').tab('change tab', tabActive);
    }

    $('.menu .item').tab({
        onLoad: function (tabPath, parameterArray, historyEvent) {
            localStorage.setItem(gestor.moduloId + 'tabActive', tabPath);
        }
    });

    // Checkbox toggle para valores booleanos
    $('.ui.checkbox').checkbox({
        onChecked: function () {
            $(this).val('true');
        },
        onUnchecked: function () {
            $(this).val('false');

            if ($(this).attr('id') === 'testar-email-debug' && !$('.debug-logs').hasClass('hidden')) {
                $('.debug-logs').addClass('hidden');
            }
        }
    });

    // Botão Salvar
    $('#btn-salvar').click(function () {
        var data = {
            ajax: 'sim',
            ajaxOpcao: 'salvar',
            usuario_recaptcha_active: $('#usuario_recaptcha_active').parent().checkbox('is checked') ? 'true' : 'false',
            usuario_recaptcha_site: $('#usuario_recaptcha_site').val(),
            usuario_recaptcha_server: $('#usuario_recaptcha_server').val(),
            email_active: $('#email_active').parent().checkbox('is checked') ? 'true' : 'false',
            email_host: $('#email_host').val(),
            email_user: $('#email_user').val(),
            email_pass: $('#email_pass').val(),
            email_secure: $('#email_secure').parent().checkbox('is checked') ? 'true' : 'false',
            email_port: $('#email_port').val(),
            email_from: $('#email_from').val(),
            email_from_name: $('#email_from_name').val(),
            email_reply_to: $('#email_reply_to').val(),
            email_reply_to_name: $('#email_reply_to_name').val(),
            language_default: $('#language_default').val(),
            language_widget_active: $('#language_widget_active').parent().checkbox('is checked') ? 'true' : 'false',
            language_auto_detect: $('#language_auto_detect').parent().checkbox('is checked') ? 'true' : 'false'
        };

        $.ajax({
            type: 'POST',
            url: window.location.href,
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                switch (dados.status) {
                    case 'success':
                        showMessage('success', dados.message);
                        break;
                    case 'error':
                        showMessage('error', dados.message);
                        break;
                    default:
                        console.log('ERROR - salvar - ' + dados.status);
                        showMessage('error', 'Erro desconhecido na resposta do servidor.');
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - salvar - Dados:');
                        console.log(txt);
                        showMessage('error', 'Erro na comunicação com o servidor.');
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    });

    // Botão Testar reCAPTCHA
    $('#btn-testar-recaptcha').click(function () {
        var siteKey = $('#usuario_recaptcha_site').val();
        var serverKey = $('#usuario_recaptcha_server').val();

        if (!siteKey || !serverKey) {
            showMessage('error', 'Preencha tanto a Site Key quanto a Server Key do reCAPTCHA.');
            return;
        }

        $(this).addClass('loading');

        // Verificar se a chave mudou e remover script antigo se necessário
        var currentScript = document.querySelector('script[src*="recaptcha/api.js"]');
        var currentSiteKey = currentScript ? currentScript.src.match(/render=([^&]+)/) : null;
        currentSiteKey = currentSiteKey ? currentSiteKey[1] : null;

        if (currentScript && currentSiteKey !== siteKey) {
            // Remover script antigo
            currentScript.remove();
            // Limpar grecaptcha da memória se possível
            if (typeof grecaptcha !== 'undefined') {
                delete window.grecaptcha;
            }
        }

        // Carregar o script do reCAPTCHA se ainda não foi carregado ou foi removido
        if (typeof grecaptcha === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://www.google.com/recaptcha/api.js?render=' + siteKey;
            script.onload = function () {
                executeRecaptcha();
            };
            script.onerror = function () {
                $('#btn-testar-recaptcha').removeClass('loading');
                showMessage('error', 'Erro ao carregar o script do reCAPTCHA.');
                $('#gestor-listener').trigger('carregar_fechar');
            };
            document.head.appendChild(script);
        } else {
            executeRecaptcha();
        }

        function executeRecaptcha() {
            try {
                grecaptcha.ready(function () {
                    // Adicionar timeout para o caso de falha
                    var timeoutId = setTimeout(function () {
                        $('#btn-testar-recaptcha').removeClass('loading');
                        showMessage('error', 'Timeout ao executar reCAPTCHA. Verifique se a Site Key é válida.');
                        $('#gestor-listener').trigger('carregar_fechar');
                    }, 3000); // 3 segundos timeout

                    grecaptcha.execute(siteKey, { action: 'submit' }).then(function (token) {
                        clearTimeout(timeoutId); // Cancelar timeout se sucesso

                        // Enviar token para o servidor
                        var data = {
                            ajax: 'sim',
                            ajaxOpcao: 'testar-recaptcha',
                            recaptcha_token: token,
                            server_key: serverKey
                        };

                        $.ajax({
                            type: 'POST',
                            url: window.location.href,
                            data: data,
                            dataType: 'json',
                            beforeSend: function () {
                                $('#gestor-listener').trigger('carregar_abrir');
                            },
                            success: function (dados) {
                                $('#btn-testar-recaptcha').removeClass('loading');

                                switch (dados.status) {
                                    case 'success':
                                        showMessage('success', dados.message);
                                        break;
                                    case 'error':
                                        showMessage('error', dados.message);
                                        break;
                                    default:
                                        console.log('ERROR - testar-recaptcha - ' + dados.status);
                                        showMessage('error', 'Erro desconhecido na resposta do servidor.');
                                }

                                $('#gestor-listener').trigger('carregar_fechar');
                            },
                            error: function (txt) {
                                $('#btn-testar-recaptcha').removeClass('loading');

                                switch (txt.status) {
                                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                                    default:
                                        console.log('ERROR AJAX - testar-recaptcha - Dados:');
                                        console.log(txt);
                                        showMessage('error', 'Erro na comunicação com o servidor.');
                                        $('#gestor-listener').trigger('carregar_fechar');
                                }
                            }
                        });
                    }).catch(function (error) {
                        clearTimeout(timeoutId); // Cancelar timeout se erro capturado

                        // Tratar erro do reCAPTCHA (chave inválida, etc.)
                        $('#btn-testar-recaptcha').removeClass('loading');
                        console.log('ERROR reCAPTCHA execute:', error);

                        if (error.message && error.message.includes('Invalid site key')) {
                            showMessage('error', 'Site Key do reCAPTCHA é inválida.');
                        } else {
                            showMessage('error', 'Erro ao executar reCAPTCHA: ' + error.message);
                        }

                        $('#gestor-listener').trigger('carregar_fechar');
                    });
                });
            } catch (globalError) {
                // Capturar qualquer erro global que possa escapar
                $('#btn-testar-recaptcha').removeClass('loading');
                console.log('ERROR global reCAPTCHA:', globalError);
                showMessage('error', 'Erro crítico no reCAPTCHA. Verifique as chaves configuradas.');
                $('#gestor-listener').trigger('carregar_fechar');
            }
        }
    });

    // Botão Testar Email
    $('#btn-testar-email').click(function () {
        var data = {
            ajax: 'sim',
            ajaxOpcao: 'testar-email',
            email_debug: $('#testar-email-debug').parent().checkbox('is checked') ? 'true' : 'false',
            email_host: $('#email_host').val(),
            email_user: $('#email_user').val(),
            email_pass: $('#email_pass').val(),
            email_secure: $('#email_secure').parent().checkbox('is checked') ? 'true' : 'false',
            email_port: $('#email_port').val(),
            email_from: $('#email_from').val(),
            email_from_name: $('#email_from_name').val(),
            email_reply_to: $('#email_reply_to').val(),
            email_reply_to_name: $('#email_reply_to_name').val()
        };

        $(this).addClass('loading');

        $.ajax({
            type: 'POST',
            url: window.location.href,
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                $('#btn-testar-email').removeClass('loading');

                switch (dados.status) {
                    case 'success':
                        showMessage('success', dados.message);
                        break;
                    case 'error':
                        showMessage('error', dados.message);
                        break;
                    default:
                        console.log('ERROR - testar-email - ' + dados.status);
                        showMessage('error', 'Erro desconhecido na resposta do servidor.');
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                $('#btn-testar-email').removeClass('loading');

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        if ($('#testar-email-debug').parent().checkbox('is checked')) {
                            $('.debug-logs').removeClass('hidden');
                            if (codemirrors_instances.length > 0) {
                                codemirrors_instances[0].getDoc().setValue(txt.responseText);
                                codemirrors_instances[0].refresh();
                            }
                        } else {
                            console.log('ERROR AJAX - testar-email - Dados:');
                            console.log(txt);
                            showMessage('error', 'Erro na comunicação com o servidor.');
                            $('#gestor-listener').trigger('carregar_fechar');
                        }
                }
            }
        });
    });

    // Função para mostrar mensagens
    function showMessage(type, message) {
        var $message = $('#status-message');
        var className = type === 'success' ? 'ui positive message' : 'ui negative message';

        $message.removeClass().addClass(className).html('<i class="' + (type === 'success' ? 'check' : 'times') + ' icon"></i> ' + message).show();

        // Auto-hide after 5 seconds
        setTimeout(function () {
            $message.fadeOut();
        }, 5000);
    }
});
