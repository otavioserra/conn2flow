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

            // Mostrar seção V2 quando V3 é ativado
            if ($(this).attr('id') === 'usuario_recaptcha_active') {
                $('#recaptcha-v2-section').removeClass('hidden');
            }
        },
        onUnchecked: function () {
            $(this).val('false');

            if ($(this).attr('id') === 'testar-email-debug' && !$('.debug-logs').hasClass('hidden')) {
                $('.debug-logs').addClass('hidden');
            }

            // Ocultar seção V2 e desativar V2 quando V3 é desativado
            if ($(this).attr('id') === 'usuario_recaptcha_active') {
                $('#recaptcha-v2-section').addClass('hidden');
                $('#usuario_recaptcha_v2_active').parent().checkbox('uncheck');
            }
        }
    });

    // Botão Salvar
    $('#btn-salvar').click(function () {
        var data = {
            ajax: 'sim',
            ajaxOpcao: 'salvar',
            site_name: $('#site_name').val(),
            usuario_recaptcha_active: $('#usuario_recaptcha_active').parent().checkbox('is checked') ? 'true' : 'false',
            usuario_recaptcha_site: $('#usuario_recaptcha_site').val(),
            usuario_recaptcha_server: $('#usuario_recaptcha_server').val(),
            usuario_recaptcha_v2_active: $('#usuario_recaptcha_v2_active').parent().checkbox('is checked') ? 'true' : 'false',
            usuario_recaptcha_v2_site: $('#usuario_recaptcha_v2_site').val(),
            usuario_recaptcha_v2_server: $('#usuario_recaptcha_v2_server').val(),
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
            language_auto_detect: $('#language_auto_detect').parent().checkbox('is checked') ? 'true' : 'false',
            paypal_default: $('#paypal_default').val(),
            paypal_client_id: $('#paypal_client_id').val(),
            paypal_secret: $('#paypal_secret').val(),
            paypal_mode: $('#paypal_mode').val(),
            paypal_webhook_id: $('#paypal_webhook_id').val()
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

    // Botão Testar reCAPTCHA V2
    $('#btn-testar-recaptcha-v2').click(function () {
        var siteKey = $('#usuario_recaptcha_v2_site').val();
        var serverKey = $('#usuario_recaptcha_v2_server').val();

        if (!siteKey || !serverKey) {
            showMessage('error', 'Preencha tanto a Site Key quanto a Server Key do reCAPTCHA V2.');
            return;
        }

        $(this).addClass('loading');

        // Mostrar container do widget V2
        $('#recaptcha-v2-container-wrapper').removeClass('hidden');
        $('#recaptcha-v2-widget').empty();

        // Remover scripts antigos do reCAPTCHA (V3 ou V2 anteriores)
        var currentScripts = document.querySelectorAll('script[src*="recaptcha/api.js"]');
        currentScripts.forEach(function (s) { s.remove(); });

        // Limpar grecaptcha da memória
        if (typeof grecaptcha !== 'undefined') {
            delete window.grecaptcha;
        }

        // Remover iframes e badges antigos do reCAPTCHA
        document.querySelectorAll('.grecaptcha-badge').forEach(function (el) { el.remove(); });
        document.querySelectorAll('iframe[src*="recaptcha"]').forEach(function (el) { el.remove(); });

        // Definir callback global para onload do V2
        window.onRecaptchaV2Load = function () {
            try {
                grecaptcha.render('recaptcha-v2-widget', {
                    'sitekey': siteKey,
                    'callback': function (token) {
                        // Token recebido - enviar para o servidor
                        var data = {
                            ajax: 'sim',
                            ajaxOpcao: 'testar-recaptcha-v2',
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
                                $('#btn-testar-recaptcha-v2').removeClass('loading');

                                switch (dados.status) {
                                    case 'success':
                                        showMessage('success', dados.message);
                                        break;
                                    case 'error':
                                        showMessage('error', dados.message);
                                        break;
                                    default:
                                        console.log('ERROR - testar-recaptcha-v2 - ' + dados.status);
                                        showMessage('error', 'Erro desconhecido na resposta do servidor.');
                                }

                                // Ocultar widget após o teste
                                $('#recaptcha-v2-container-wrapper').addClass('hidden');
                                $('#gestor-listener').trigger('carregar_fechar');
                            },
                            error: function (txt) {
                                $('#btn-testar-recaptcha-v2').removeClass('loading');

                                switch (txt.status) {
                                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                                    default:
                                        console.log('ERROR AJAX - testar-recaptcha-v2 - Dados:');
                                        console.log(txt);
                                        showMessage('error', 'Erro na comunicação com o servidor.');
                                        $('#gestor-listener').trigger('carregar_fechar');
                                }
                            }
                        });
                    },
                    'expired-callback': function () {
                        showMessage('error', 'reCAPTCHA V2 expirou. Clique novamente em Testar.');
                        $('#btn-testar-recaptcha-v2').removeClass('loading');
                        $('#recaptcha-v2-container-wrapper').addClass('hidden');
                    },
                    'error-callback': function () {
                        showMessage('error', 'Erro no reCAPTCHA V2. Verifique sua conexão.');
                        $('#btn-testar-recaptcha-v2').removeClass('loading');
                        $('#recaptcha-v2-container-wrapper').addClass('hidden');
                    }
                });

                $('#btn-testar-recaptcha-v2').removeClass('loading');
            } catch (e) {
                console.log('ERROR reCAPTCHA V2 render:', e);
                showMessage('error', 'Erro ao renderizar reCAPTCHA V2. Verifique a Site Key.');
                $('#btn-testar-recaptcha-v2').removeClass('loading');
                $('#recaptcha-v2-container-wrapper').addClass('hidden');
            }
        };

        // Carregar script do reCAPTCHA V2 com render=explicit
        var script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaV2Load&render=explicit';
        script.async = true;
        script.defer = true;
        script.onerror = function () {
            $('#btn-testar-recaptcha-v2').removeClass('loading');
            showMessage('error', 'Erro ao carregar o script do reCAPTCHA V2.');
            $('#recaptcha-v2-container-wrapper').addClass('hidden');
        };
        document.head.appendChild(script);
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

    // Toggle PayPal integration mode
    $('#paypal_default').on('change', function () {
        var mode = $(this).val();
        if (mode === 'gateway') {
            $('#paypal-padrao-section').addClass('hidden');
        } else {
            $('#paypal-padrao-section').removeClass('hidden');
        }
    });

    // Botão Testar PayPal
    $('#btn-testar-paypal').click(function () {
        var clientId = $('#paypal_client_id').val();
        var secret = $('#paypal_secret').val();
        var mode = $('#paypal_mode').val();
        var webhookId = $('#paypal_webhook_id').val();

        $(this).addClass('loading');

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'testar-paypal',
            paypal_client_id: clientId,
            paypal_secret: secret,
            paypal_mode: mode,
            paypal_webhook_id: webhookId
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
                $('#btn-testar-paypal').removeClass('loading');

                switch (dados.status) {
                    case 'success':
                        showMessage('success', dados.message);
                        break;
                    case 'error':
                        showMessage('error', dados.message);
                        break;
                    default:
                        console.log('ERROR - testar-paypal - ' + dados.status);
                        showMessage('error', 'Erro desconhecido na resposta do servidor.');
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                $('#btn-testar-paypal').removeClass('loading');

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - testar-paypal - Dados:');
                        console.log(txt);
                        showMessage('error', 'Erro na comunicação com o servidor.');
                        $('#gestor-listener').trigger('carregar_fechar');
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
