$(document).ready(function () {
    if ($('#_gestor-interface-visualizar-dados').length > 0) {

        // ===== Inicializar componentes Fomantic UI
        $('.ui.accordion').accordion();

        // ===== Referências aos dados injetados via gestor_js_variavel_incluir
        var idNumerico = (typeof gestor !== 'undefined' && gestor.formsSubmissions) ? gestor.formsSubmissions.idNumerico : null;

        // ===== Salvar status do contato
        $('#btn-save-status').on('click', function () {
            var btn = $(this);
            var newStatus = $('#form-status-select').val();

            if (!idNumerico) {
                alert('Error: Submission ID not found.');
                return;
            }

            btn.addClass('loading disabled');

            $.ajax({
                url: window.location.pathname,
                type: 'POST',
                data: {
                    ajax: true,
                    ajaxOpcao: 'update-status',
                    id_numerico: idNumerico,
                    form_status: newStatus
                },
                dataType: 'json',
                success: function (response) {
                    btn.removeClass('loading disabled');
                    if (response.status === 'success') {
                        // Feedback visual de sucesso
                        btn.addClass('green').find('.icon').removeClass('save').addClass('check');
                        // Atualizar badge se possível
                        if (response.form_status_label) {
                            var $badge = btn.closest('td').find('.ui.label').first();
                            if ($badge.length) {
                                $badge.text(response.form_status_label);
                            }
                        }
                        setTimeout(function () {
                            btn.removeClass('green').find('.icon').removeClass('check').addClass('save');
                        }, 2500);
                    } else {
                        alert(response.message || 'Error updating status.');
                    }
                },
                error: function () {
                    btn.removeClass('loading disabled');
                    alert('Error communicating with server.');
                }
            });
        });
        // ===== Enviar resposta ao contato
        $('#btn-send-reply').on('click', function () {
            var btn = $(this);
            var message = $('#reply-message').val().trim();
            var replyEmail = $('#reply-to-email').val().trim();

            if (!idNumerico) {
                alert('Error: Submission ID not found.');
                return;
            }

            if (!message) {
                $('#reply-message').closest('.field').addClass('error');
                $('#reply-message').focus();
                return;
            }
            $('#reply-message').closest('.field').removeClass('error');

            if (!replyEmail) {
                alert('Error: No recipient email found.');
                return;
            }

            // Confirmação antes de enviar
            if (!confirm('Send reply to ' + replyEmail + '?')) {
                return;
            }

            btn.addClass('loading disabled');

            $.ajax({
                url: window.location.pathname,
                type: 'POST',
                data: {
                    ajax: true,
                    ajaxOpcao: 'reply',
                    id_numerico: idNumerico,
                    reply_message: message,
                    reply_email: replyEmail
                },
                dataType: 'json',
                success: function (response) {
                    btn.removeClass('loading disabled');
                    if (response.status === 'success') {
                        btn.addClass('green');
                        btn.html('<i class="check icon"></i> Sent!');
                        $('#reply-message').val('').prop('disabled', true);
                        setTimeout(function () {
                            location.reload();
                        }, 1800);
                    } else {
                        alert(response.message || 'Error sending reply.');
                    }
                },
                error: function () {
                    btn.removeClass('loading disabled');
                    alert('Error communicating with server.');
                }
            });

        });

    }
});
