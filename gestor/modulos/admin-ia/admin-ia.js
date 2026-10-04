function adminIaMensagem(id) {
    var mensagens = document.getElementById('admin-ia-mensagens');
    return mensagens ? mensagens.getAttribute('data-' + id) || '' : '';
}

function adminIaToast(config) {
    if (!window.c2fControles) return;

    var classe = String(config && config.class || '');
    var tipo = /error|red|negative/.test(classe) ? 'erro' : (/success|green|positive/.test(classe) ? 'sucesso' : (/warning|orange|yellow/.test(classe) ? 'alerta' : 'info'));
    window.c2fControles.aviso(config && config.message || '', tipo);
}

function adminIaHistoricoRenderizar(lista, template, historico, mensagemVazia) {
    lista.replaceChildren();
    if (!Array.isArray(historico) || historico.length === 0) {
        lista.textContent = mensagemVazia;
        return;
    }

    historico.forEach(function (teste) {
        var item = template.content.firstElementChild.cloneNode(true);
        var sucesso = teste.sucesso === true || teste.sucesso === 1 || teste.sucesso === '1';
        item.querySelector('[data-history-success]').classList.toggle('hidden', !sucesso);
        item.querySelector('[data-history-error]').classList.toggle('hidden', sucesso);
        item.querySelector('[data-history-date]').textContent = teste.data || '';
        item.querySelector('[data-history-response]').textContent = teste.tempo_resposta || '';
        item.querySelector('[data-history-error-detail]').classList.toggle('hidden', !teste.mensagem_erro);
        item.querySelector('[data-history-error-message]').textContent = teste.mensagem_erro || '';
        lista.appendChild(item);
    });
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { adminIaHistoricoRenderizar: adminIaHistoricoRenderizar };
}

$(document).ready(function () {

    // ===== Página Listar =====
    $('.testar-conexao').click(function () {
        var id = $(this).data('id');
        var button = $(this);

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'testar_conexao',
            id: id
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                button.prop('disabled', true).attr('aria-busy', 'true');
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                button.prop('disabled', false).removeAttr('aria-busy');

                switch (dados.status) {
                    case 'success':
                        adminIaToast({
                            class: 'success',
                            message: dados.message
                        });
                        break;
                    case 'error':
                        adminIaToast({
                            class: 'error',
                            message: dados.message
                        });
                        break;
                    default:
                        console.log('ERROR - testar_conexao - ' + dados.status);
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                button.prop('disabled', false).removeAttr('aria-busy');

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - testar_conexao - Dados:');
                        console.log(txt);
                        adminIaToast({
                            class: 'error',
                            message: 'Erro na comunicação com o servidor'
                        });
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    });

    $('.ativar-conexao').click(function () {
        var id = $(this).data('id');
        var button = $(this);

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'ativar',
            id: id
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                button.prop('disabled', true).attr('aria-busy', 'true');
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                button.prop('disabled', false).removeAttr('aria-busy');

                switch (dados.status) {
                    case 'success':
                        adminIaToast({
                            class: 'success',
                            message: dados.message
                        });
                        // Reload da página após 1.5 segundos
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                        break;
                    case 'error':
                        adminIaToast({
                            class: 'error',
                            message: dados.message
                        });
                        break;
                    default:
                        console.log('ERROR - ativar - ' + dados.status);
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                button.prop('disabled', false).removeAttr('aria-busy');

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - ativar - Dados:');
                        console.log(txt);
                        adminIaToast({
                            class: 'error',
                            message: 'Erro na comunicação com o servidor'
                        });
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    });


    $('.desativar-conexao').click(function () {
        var id = $(this).data('id');
        var button = $(this);

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'desativar',
            id: id
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                button.prop('disabled', true).attr('aria-busy', 'true');
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                button.prop('disabled', false).removeAttr('aria-busy');

                switch (dados.status) {
                    case 'success':
                        adminIaToast({
                            class: 'success',
                            message: dados.message
                        });
                        // Reload da página após 1.5 segundos
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                        break;
                    case 'error':
                        adminIaToast({
                            class: 'error',
                            message: dados.message
                        });
                        break;
                    default:
                        console.log('ERROR - ativar - ' + dados.status);
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                button.prop('disabled', false).removeAttr('aria-busy');

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - ativar - Dados:');
                        console.log(txt);
                        adminIaToast({
                            class: 'error',
                            message: 'Erro na comunicação com o servidor'
                        });
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    });


    $('.excluir-servidor').click(function () {
        var id = $(this).data('id');
        var button = $(this);

        window.c2fControles.dialogo.confirmar(adminIaMensagem('confirm-delete'), { perigo: true }).then(function (confirmado) {
            if (!confirmado) return;

            var data = {
                ajax: 'sim',
                ajaxOpcao: 'excluir',
                id: id
            };

            $.ajax({
                type: 'POST',
                url: gestor.raiz + gestor.moduloCaminho + '/',
                data: data,
                dataType: 'json',
                beforeSend: function () {
                    button.prop('disabled', true).attr('aria-busy', 'true');
                    $('#gestor-listener').trigger('carregar_abrir');
                },
                success: function (dados) {
                    button.prop('disabled', false).removeAttr('aria-busy');

                    switch (dados.status) {
                        case 'success':
                            adminIaToast({
                                class: 'success',
                                message: dados.message
                            });
                            // Redirecionar para a página de listagem após 1.5 segundos
                            setTimeout(function () {
                                window.location.href = gestor.raiz + 'admin-ia/listar/';
                            }, 1500);
                            break;
                        case 'error':
                            adminIaToast({
                                class: 'error',
                                message: dados.message
                            });
                            break;
                        default:
                            console.log('ERROR - excluir - ' + dados.status);
                    }

                    $('#gestor-listener').trigger('carregar_fechar');
                },
                error: function (txt) {
                    button.prop('disabled', false).removeAttr('aria-busy');

                    switch (txt.status) {
                        case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                        default:
                            console.log('ERROR AJAX - excluir - Dados:');
                            console.log(txt);
                            adminIaToast({
                                class: 'error',
                                message: 'Erro na comunicação com o servidor'
                            });
                            $('#gestor-listener').trigger('carregar_fechar');
                    }
                }
            });
        });
    });

    // ===== Página Adicionar =====
    $('#form-servidor-ia').submit(function (e) {
        e.preventDefault();

        var formData = $(this).serializeArray();
        var testarConexao = $('input[name="testar_conexao"]').is(':checked');

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'salvar',
            nome: formData.find(item => item.name === 'nome').value,
            tipo: formData.find(item => item.name === 'tipo').value,
            chave_api: formData.find(item => item.name === 'chave_api').value,
            padrao: formData.find(item => item.name === 'padrao') ? 'on' : 'off'
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $('.js-submit').prop('disabled', true).attr('aria-busy', 'true');
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                $('.js-submit').prop('disabled', false).removeAttr('aria-busy');

                switch (dados.status) {
                    case 'success':
                        adminIaToast({
                            class: 'success',
                            message: dados.message
                        });

                        if (testarConexao) {
                            // Testar conexão após salvar
                            setTimeout(function () {
                                testarConexaoAposSalvar(dados.id);
                            }, 1000);
                        } else {
                            setTimeout(function () {
                                window.location.href = gestor.raiz + 'admin-ia/listar/';
                            }, 1500);
                        }
                        break;
                    case 'error':
                        adminIaToast({
                            class: 'error',
                            message: dados.message
                        });
                        break;
                    default:
                        console.log('ERROR - salvar - ' + dados.status);
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                $('.js-submit').prop('disabled', false).removeAttr('aria-busy');

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - salvar - Dados:');
                        console.log(txt);
                        adminIaToast({
                            class: 'error',
                            message: 'Erro na comunicação com o servidor'
                        });
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    });

    // ===== Página Editar =====
    // Carregar histórico de testes
    if ($('#historico-testes').length) {
        carregarHistoricoTestes();
    }

    $('#form-servidor-ia-edit').submit(function (e) {
        e.preventDefault();

        var formData = $(this).serializeArray();
        var testarConexao = $('input[name="testar_conexao"]').is(':checked');

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'editar',
            id: formData.find(item => item.name === 'id').value,
            nome: formData.find(item => item.name === 'nome').value,
            tipo: formData.find(item => item.name === 'tipo').value,
            chave_api: formData.find(item => item.name === 'chave_api').value,
            padrao: formData.find(item => item.name === 'padrao') ? 'on' : 'off'
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $('.js-submit').prop('disabled', true).attr('aria-busy', 'true');
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                $('.js-submit').prop('disabled', false).removeAttr('aria-busy');

                switch (dados.status) {
                    case 'success':
                        adminIaToast({
                            class: 'success',
                            message: dados.message
                        });

                        if (testarConexao) {
                            // Testar conexão após salvar
                            setTimeout(function () {
                                testarConexaoAtual();
                            }, 1000);
                        }
                        break;
                    case 'error':
                        adminIaToast({
                            class: 'error',
                            message: dados.message
                        });
                        break;
                    default:
                        console.log('ERROR - editar - ' + dados.status);
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                $('.js-submit').prop('disabled', false).removeAttr('aria-busy');

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - editar - Dados:');
                        console.log(txt);
                        adminIaToast({
                            class: 'error',
                            message: 'Erro na comunicação com o servidor'
                        });
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    });

    function testarConexaoAtual(id = null) {
        if (!id) {
            id = $('input[name="id"]').val();
        }

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'testar_conexao',
            id: id
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                switch (dados.status) {
                    case 'success':
                        adminIaToast({
                            class: 'success',
                            message: 'Conexão testada: ' + dados.message
                        });
                        break;
                    case 'error':
                        adminIaToast({
                            class: 'warning',
                            message: 'Erro no teste de conexão: ' + dados.message
                        });
                        break;
                    default:
                        console.log('ERROR - testar_conexao_atual - ' + dados.status);
                }

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - testar_conexao_atual - Dados:');
                        console.log(txt);
                        adminIaToast({
                            class: 'warning',
                            message: 'Erro na comunicação para teste de conexão'
                        });
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    }

    function testarConexaoAposSalvar(id) {
        var data = {
            ajax: 'sim',
            ajaxOpcao: 'testar_conexao',
            id: id
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                switch (dados.status) {
                    case 'success':
                        adminIaToast({
                            class: 'success',
                            message: 'Conexão testada: ' + dados.message
                        });
                        break;
                    case 'error':
                        adminIaToast({
                            class: 'warning',
                            message: 'Servidor salvo, mas erro no teste: ' + dados.message
                        });
                        break;
                    default:
                        console.log('ERROR - testar_conexao_apos_salvar - ' + dados.status);
                }

                setTimeout(function () {
                    window.location.href = gestor.raiz + 'admin-ia/listar/';
                }, 2000);

                $('#gestor-listener').trigger('carregar_fechar');
            },
            error: function (txt) {
                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - testar_conexao_apos_salvar - Dados:');
                        console.log(txt);
                        adminIaToast({
                            class: 'warning',
                            message: 'Servidor salvo, mas erro na comunicação para teste'
                        });
                        setTimeout(function () {
                            window.location.href = gestor.raiz + 'admin-ia/listar/';
                        }, 2000);
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
        });
    }

    function carregarHistoricoTestes() {
        var id = $('input[name="id"]').val();
        var lista = document.getElementById('historico-testes');
        var template = document.getElementById('admin-ia-history-template');
        if (!lista || !template) return;

        var data = {
            ajax: 'sim',
            ajaxOpcao: 'historico_testes',
            id: id
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                lista.setAttribute('aria-busy', 'true');
            },
            success: function (dados) {
                lista.removeAttribute('aria-busy');
                if (dados.status === 'success') {
                    adminIaHistoricoRenderizar(lista, template, dados.historico, adminIaMensagem('history-empty'));
                } else {
                    lista.replaceChildren();
                    lista.textContent = adminIaMensagem('error-history') + (dados.message ? ' ' + dados.message : '');
                }
            },
            error: function (txt) {
                lista.removeAttribute('aria-busy');
                console.log('ERROR AJAX - historico_testes - Dados:');
                console.log(txt);
                lista.textContent = adminIaMensagem('error-history');
            }
        });
    }

});

// ===== Salvar Modelos Globais =====
function salvarModelosGlobais() {
    var enabledModels = [];
    $('#form-global-models input[name="global_model[]"]:checked').each(function () {
        enabledModels.push($(this).val());
    });

    var data = {
        ajax: 'sim',
        ajaxOpcao: 'salvar_modelos_globais',
        enabled_models: JSON.stringify(enabledModels)
    };

    var btn = $('#btn-save-global-models');

    $.ajax({
        type: 'POST',
        url: gestor.raiz + gestor.moduloCaminho + '/',
        data: data,
        dataType: 'json',
        beforeSend: function () {
            btn.prop('disabled', true).attr('aria-busy', 'true');
        },
        success: function (dados) {
            btn.prop('disabled', false).removeAttr('aria-busy');
            if (dados.status === 'success') {
                adminIaToast({ class: 'success', message: dados.message });
            } else {
                adminIaToast({ class: 'error', message: dados.message || 'Erro desconhecido' });
            }
        },
        error: function (txt) {
            btn.prop('disabled', false).removeAttr('aria-busy');
            if (txt.status === 401 && txt.responseJSON && txt.responseJSON.redirect) {
                window.open(gestor.raiz + txt.responseJSON.redirect, '_self');
            } else {
                adminIaToast({ class: 'error', message: 'Erro na comunicação com o servidor' });
            }
        }
    });
}
