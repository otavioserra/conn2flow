/** Submission actions share the panel controls and same-origin CSRF transport. */
document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('#_gestor-interface-visualizar-dados');
    if (!root) return;
    var messages = root.querySelector('.req222-page');
    var id = window.gestor && gestor.formsSubmissions && gestor.formsSubmissions.idNumerico;
    var text = function (key) { return messages.getAttribute('data-' + key) || ''; };
    var notify = function (message) { return window.c2fControles.dialogo.alerta(message); };

    async function send(button, action, values) {
        if (!id) { await notify(text('js-id-missing')); return null; }
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        try {
            var body = new URLSearchParams(Object.assign({
                ajax: 'sim', ajaxOpcao: action,
                opcao: gestor.moduloOpcao || 'visualizar', id_numerico: id
            }, values));
            var response = await fetch(window.location.pathname, { method: 'POST', body: body });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            var result = await response.json();
            if (result.status !== 'success') {
                await notify(result.message || text(action === 'reply' ? 'js-reply-error' : 'js-status-error'));
                return null;
            }
            return result;
        } catch (error) {
            await notify(text('js-communication-error'));
            return null;
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }

    var save = root.querySelector('#btn-save-status');
    if (save) save.addEventListener('click', async function (event) {
        event.preventDefault();
        var status = root.querySelector('#form-status-select').value;
        var result = await send(save, 'update-status', { form_status: status });
        if (!result) return;
        var badge = save.closest('td').querySelector('[data-submission-status]');
        if (badge && result.form_status_label) badge.textContent = result.form_status_label;
        if (badge) {
            badge.classList.toggle('c2fc-selo-ativo', status === 'responded');
            badge.classList.toggle('c2fc-selo-inativo', status !== 'responded');
        }
        window.c2fControles.aviso(result.message, 'sucesso');
    });

    var reply = root.querySelector('#btn-send-reply');
    if (reply) reply.addEventListener('click', async function (event) {
        event.preventDefault();
        if (!id) { await notify(text('js-id-missing')); return; }
        var field = root.querySelector('#reply-message');
        var message = field.value.trim();
        var email = root.querySelector('#reply-to-email').value.trim();
        field.setAttribute('aria-invalid', message ? 'false' : 'true');
        if (!message) { field.focus(); return; }
        if (!email) { await notify(text('js-email-missing')); return; }
        reply.disabled = true;
        var accepted;
        try {
            accepted = await window.c2fControles.dialogo.confirmar(text('js-reply-confirm').replace('{email}', email));
        } finally { reply.disabled = false; }
        if (!accepted) return;
        var result = await send(reply, 'reply', { reply_message: message, reply_email: email });
        if (!result) return;
        reply.textContent = text('js-reply-sent');
        field.value = '';
        field.disabled = true;
        reply.disabled = true;
        window.location.reload();
    });
});
