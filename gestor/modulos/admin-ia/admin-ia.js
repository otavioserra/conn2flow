$(document).ready(function () {
    // ===== Fomantic UI =====
    $('.ui.dropdown').dropdown();

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
                button.addClass('loading').prop('disabled', true);
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                button.removeClass('loading').prop('disabled', false);

                switch (dados.status) {
                    case 'success':
                        $.toast({
                            class: 'success',
                            message: dados.message
                        });
                        break;
                    case 'error':
                        $.toast({
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
                button.removeClass('loading').prop('disabled', false);

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - testar_conexao - Dados:');
                        console.log(txt);
                        $.toast({
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
                button.addClass('loading').prop('disabled', true);
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                button.removeClass('loading').prop('disabled', false);

                switch (dados.status) {
                    case 'success':
                        $.toast({
                            class: 'success',
                            message: dados.message
                        });
                        // Reload da página após 1.5 segundos
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                        break;
                    case 'error':
                        $.toast({
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
                button.removeClass('loading').prop('disabled', false);

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - ativar - Dados:');
                        console.log(txt);
                        $.toast({
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
                button.addClass('loading').prop('disabled', true);
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                button.removeClass('loading').prop('disabled', false);

                switch (dados.status) {
                    case 'success':
                        $.toast({
                            class: 'success',
                            message: dados.message
                        });
                        // Reload da página após 1.5 segundos
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                        break;
                    case 'error':
                        $.toast({
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
                button.removeClass('loading').prop('disabled', false);

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - ativar - Dados:');
                        console.log(txt);
                        $.toast({
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

        // Confirmação antes de excluir
        if (!confirm('Tem certeza que deseja excluir este servidor IA? Esta ação não pode ser desfeita.')) {
            return;
        }

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
                button.addClass('loading').prop('disabled', true);
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                button.removeClass('loading').prop('disabled', false);

                switch (dados.status) {
                    case 'success':
                        $.toast({
                            class: 'success',
                            message: dados.message
                        });
                        // Redirecionar para a página de listagem após 1.5 segundos
                        setTimeout(function () {
                            window.location.href = gestor.raiz + 'admin-ia/listar/';
                        }, 1500);
                        break;
                    case 'error':
                        $.toast({
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
                button.removeClass('loading').prop('disabled', false);

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - excluir - Dados:');
                        console.log(txt);
                        $.toast({
                            class: 'error',
                            message: 'Erro na comunicação com o servidor'
                        });
                        $('#gestor-listener').trigger('carregar_fechar');
                }
            }
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
                $('.ui.button.primary').addClass('loading').prop('disabled', true);
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                $('.ui.button.primary').removeClass('loading').prop('disabled', false);

                switch (dados.status) {
                    case 'success':
                        $.toast({
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
                        $.toast({
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
                $('.ui.button.primary').removeClass('loading').prop('disabled', false);

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - salvar - Dados:');
                        console.log(txt);
                        $.toast({
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
            padrao: formData.find(item => item.name === 'padrao').value ? 'on' : 'off'
        };

        $.ajax({
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $('.ui.button.primary').addClass('loading').prop('disabled', true);
                $('#gestor-listener').trigger('carregar_abrir');
            },
            success: function (dados) {
                $('.ui.button.primary').removeClass('loading').prop('disabled', false);

                switch (dados.status) {
                    case 'success':
                        $.toast({
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
                        $.toast({
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
                $('.ui.button.primary').removeClass('loading').prop('disabled', false);

                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - editar - Dados:');
                        console.log(txt);
                        $.toast({
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
                        $.toast({
                            class: 'success',
                            message: 'Conexão testada: ' + dados.message
                        });
                        break;
                    case 'error':
                        $.toast({
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
                        $.toast({
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
                        $.toast({
                            class: 'success',
                            message: 'Conexão testada: ' + dados.message
                        });
                        break;
                    case 'error':
                        $.toast({
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
                        $.toast({
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
                $('#historico-testes').html('<p class="text-muted"><i class="spinner loading icon"></i> Carregando histórico...</p>');
            },
            success: function (dados) {
                if (dados.status === 'success') {
                    if (dados.historico.length > 0) {
                        var html = '<div class="ui relaxed divided list">';

                        dados.historico.forEach(function (teste) {
                            var statusIcon = teste.sucesso ?
                                '<i class="check circle green icon"></i>' :
                                '<i class="times circle red icon"></i>';

                            var statusText = teste.sucesso ? 'Sucesso' : 'Erro';
                            var statusClass = teste.sucesso ? 'positive' : 'negative';

                            var erroInfo = teste.mensagem_erro ?
                                '<br><small class="text-muted">Erro: ' + teste.mensagem_erro + '</small>' : '';

                            html += '<div class="item">' +
                                '<div class="content">' +
                                '<div class="header">' + statusIcon + ' ' + statusText + ' - ' + teste.data + '</div>' +
                                '<div class="description">' +
                                'Tempo de resposta: ' + teste.tempo_resposta +
                                erroInfo +
                                '</div>' +
                                '</div>' +
                                '</div>';
                        });

                        html += '</div>';
                        $('#historico-testes').html(html);
                    } else {
                        $('#historico-testes').html('<p class="text-muted">Nenhum teste realizado ainda.</p>');
                    }
                } else {
                    $('#historico-testes').html('<p class="text-red">Erro ao carregar histórico: ' + dados.message + '</p>');
                }
            },
            error: function (txt) {
                console.log('ERROR AJAX - historico_testes - Dados:');
                console.log(txt);
                $('#historico-testes').html('<p class="text-red">Erro na comunicação com o servidor</p>');
            }
        });
    }

});
