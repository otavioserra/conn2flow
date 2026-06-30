/**
 * forms.widget.js — Controlador do widget de formulários no PREVIEW do Editor HTML (req-070).
 *
 * No site publicado o formulário é regido diretamente pela biblioteca de formulários
 * (formulario.php injeta gestor.form[id] no page load + formulario.js — ver BATCH-069). No
 * preview do Editor HTML, porém, o iframe é um srcdoc estático gerado por JS: o backend não roda
 * formulario_controlador, então gestor.form fica vazio e o formulário não se inicializa.
 *
 * Este script roda DENTRO do iframe de preview. Ele varre os formulários renderizados
 * (.conn2flow-form / ._forms-submissions-controller), recupera via AJAX dedicado
 * (ajaxOpcao=forms-render-editor-html) as mesmas variáveis que o backend injetaria em
 * gestor.form[id] e, em seguida, carrega interface.js + formulario.js para ligar os eventos de
 * submissão — reproduzindo o comportamento do site real dentro do preview.
 *
 * Como cada módulo declara seu próprio widget_js_include (req-070 §1), este arquivo só é incluído
 * no preview do editor de formulários, onde gestor.moduloCaminho === 'forms'.
 */
(function () {
    var $ = window.jQuery || window.$;
    var gestor = window.gestor || (window.parent && window.parent.gestor) || null;
    if (!$ || !gestor || !gestor.raiz) return;

    var SELECTOR = 'form._forms-submissions-controller, form.conn2flow-form, ._forms-submissions-controller, .conn2flow-form';

    var configRequested = {}; // form_id -> true (config já solicitada)
    var pendingFetches = 0;   // requisições AJAX em voo
    var scriptsLoaded = false;

    function carregarScript(src, callback) {
        var s = document.createElement('script');
        s.src = src;
        s.onload = function () { if (callback) callback(); };
        s.onerror = function () { if (callback) callback(); };
        document.head.appendChild(s);
    }

    // Carrega a biblioteca de formulários uma única vez. A interface.js entra antes do
    // formulario.js (mesma ordem do site publicado). O start() do formulario.js roda no ready;
    // como o documento já está carregado, ele dispara de imediato e lê gestor.form já populado.
    function carregarBiblioteca() {
        if (scriptsLoaded) return;
        scriptsLoaded = true;
        var versao = gestor.versao || '1.0.0';
        carregarScript(gestor.raiz + 'interface/interface.js?v=' + versao, function () {
            carregarScript(gestor.raiz + 'interface/formulario.js?v=' + versao);
        });
    }

    function solicitarConfig(formId) {
        if (configRequested[formId]) return;
        configRequested[formId] = true;
        pendingFetches++;

        $.ajax({
            type: 'POST',
            url: gestor.raiz + 'forms/',
            dataType: 'json',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'forms-render-editor-html',
                params: { form_id: formId }
            },
            success: function (resp) {
                if (resp && resp.status === 'Ok' && resp.forms_js_vars) {
                    gestor.form = gestor.form || {};
                    gestor.form[formId] = resp.forms_js_vars;
                }
            },
            complete: function () {
                pendingFetches--;
                if (pendingFetches <= 0) carregarBiblioteca();
            }
        });
    }

    // Coleta os IDs dos formulários presentes no DOM (deduplicados). Retorna a quantidade encontrada.
    function varrerFormularios() {
        var encontrados = 0;
        $(SELECTOR).each(function () {
            var formId = $(this).attr('data-form-id') || $(this).attr('id') || '';
            if (formId) {
                encontrados++;
                solicitarConfig(formId);
            }
        });
        return encontrados;
    }

    // Os widgets do preview são renderizados de forma assíncrona (AJAX html-editor-widget-render),
    // então o formulário pode ainda não estar no DOM quando este script roda. Faz polling curto até
    // estabilizar a contagem de formulários (ou atingir o limite de tentativas).
    function iniciar() {
        var tentativas = 0;
        var maxTentativas = 40; // ~10s a 250ms
        var ultimaContagem = -1;
        var estavel = 0;

        var timer = setInterval(function () {
            tentativas++;
            var contagem = varrerFormularios();

            if (contagem > 0 && contagem === ultimaContagem) {
                estavel++;
            } else {
                estavel = 0;
            }
            ultimaContagem = contagem;

            // Para de varrer quando a contagem estabiliza (2 ciclos) ou esgota as tentativas.
            if ((contagem > 0 && estavel >= 2) || tentativas >= maxTentativas) {
                clearInterval(timer);
                // Se nenhum fetch ficou pendente mas há formulários, garante o carregamento da lib.
                if (contagem > 0 && pendingFetches <= 0) carregarBiblioteca();
            }
        }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
