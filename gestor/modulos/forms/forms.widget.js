$(document).ready(function () {
    function initForms() {
        $('.conn2flow-form').each(function () {
            var $form = $(this);
            if ($form.data('c2fFormsReady')) return;
            $form.data('c2fFormsReady', true);

            $form.on('submit', function (e) {
                var invalid = false;
                $form.find('[required]').each(function () {
                    var $field = $(this);
                    if (($field.val() || '').trim() === '') {
                        invalid = true;
                        $field.addClass('error');
                    } else {
                        $field.removeClass('error');
                    }
                });

                if (invalid) {
                    e.preventDefault();
                    showMessage($form, false, 'Preencha os campos obrigatórios.');
                    return false;
                }

                var action = $form.attr('action') || $form.data('action') || '';
                if (!action) return true;

                e.preventDefault();
                submitAjax($form, action);
                return false;
            });
        });
    }

    function showMessage($form, ok, text) {
        var $msg = $form.find('.conn2flow-form-message').first();
        if ($msg.length === 0) return;
        $msg.removeClass('hidden positive negative success error');
        $msg.addClass(ok ? 'positive success' : 'negative error');
        $msg.text(text || '');
    }

    function submitAjax($form, action) {
        var $button = $form.find('[type="submit"]').first();
        $button.addClass('loading disabled');

        $.ajax({
            type: 'POST',
            url: action,
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                $button.removeClass('loading disabled');
                if (response && response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                showMessage($form, !response || response.status !== 'Erro', (response && response.message) ? response.message : 'Mensagem enviada.');
            },
            error: function () {
                $button.removeClass('loading disabled');
                showMessage($form, false, 'Não foi possível enviar o formulário.');
            }
        });
    }

    if (window.self !== window.parent && window.location.href === 'about:srcdoc') {
        setTimeout(initForms, 500);
    } else {
        initForms();
    }
});
