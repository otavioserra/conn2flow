/**
 * Runtime da interface administrativa em Tailwind (req-118 / BATCH-119).
 *
 * Substitui o `interface.js` legado nas requisições resolvidas como Tailwind puro. O legado chama
 * `$.fn.modal` do Fomantic em 16 pontos, sem guarda: com o Fomantic fora da página, ele quebraria
 * no primeiro alerta ou no modal de Área Restrita — que é uma trava de credenciais.
 *
 * **O contrato de dados é o MESMO.** Ele lê `gestor.interface.regrasValidacao` no formato do
 * validador do Fomantic (`{campo:{rules:[{type,prompt}]}}`) e responde aos mesmos eventos de
 * `#gestor-listener`. Por isso nenhuma função PHP que monta regra de validação precisou mudar —
 * `interface_formulario_validacao()` continua emitindo exatamente o que emitia.
 *
 * jQuery continua presente na página (é incluído incondicionalmente pelo core) e é usado apenas para
 * INTEROPERAR com quem dispara `$('#gestor-listener').trigger(...)`; nenhuma UI depende dele.
 */
(function () {
    'use strict';

    // ===================================================================================
    // Modais
    // ===================================================================================

    function modal(nome) {
        return document.querySelector('[data-c2f-modal="' + nome + '"]');
    }

    function abrirModal(el) {
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function fecharModal(el) {
        if (!el) return;
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    var carregandoAtivo = 0;

    function carregarAbrir() {
        carregandoAtivo++;
        abrirModal(modal('carregando'));
    }

    function carregarFechar() {
        // Contador em vez de booleano: duas chamadas AJAX simultâneas fechariam o loader na
        // primeira resposta, deixando a segunda sem indicação de progresso.
        carregandoAtivo = Math.max(0, carregandoAtivo - 1);
        if (carregandoAtivo === 0) fecharModal(modal('carregando'));
    }

    function alerta(p) {
        p = p || {};

        var el = modal('alerta');
        if (!el) return;

        var mensagem = el.querySelector('[data-c2f-modal-mensagem]');
        if (mensagem && p.msg) mensagem.innerHTML = p.msg;

        abrirModal(el);
    }

    function deletarConfirmacao() {
        var el = modal('delecao');
        if (!el) return;
        abrirModal(el);
    }

    function ligarModais() {
        var modais = document.querySelectorAll('[data-c2f-modal]');

        Array.prototype.forEach.call(modais, function (el) {
            var nome = el.getAttribute('data-c2f-modal');
            var obrigatorio = el.hasAttribute('data-c2f-modal-obrigatorio');

            var aprovar = el.querySelector('[data-c2f-modal-aprovar]');
            var negar = el.querySelector('[data-c2f-modal-negar]');

            if (aprovar) {
                aprovar.addEventListener('click', function () {
                    if (nome === 'delecao' && window.gestor && gestor.interface && gestor.interface.excluir_url) {
                        window.open(gestor.interface.excluir_url, '_self');
                        return;
                    }
                    fecharModal(el);
                });
            }

            if (negar) negar.addEventListener('click', function () { fecharModal(el); });

            if (obrigatorio) return;

            // Clique no backdrop e Esc fecham — exceto o modal obrigatório (Área Restrita), onde
            // sair sem escolher equivaleria a burlar a trava.
            el.addEventListener('mousedown', function (evento) {
                if (evento.target === el) fecharModal(el);
            });
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key !== 'Escape') return;

            Array.prototype.forEach.call(document.querySelectorAll('[data-c2f-modal]:not(.hidden)'), function (el) {
                if (!el.hasAttribute('data-c2f-modal-obrigatorio')) fecharModal(el);
            });
        });
    }

    // ===================================================================================
    // Validação de formulário — mesmo dicionário de regras do validador do Fomantic
    // ===================================================================================

    /**
     * Interpreta um `type` de regra e devolve se o valor passa.
     *
     * Os tipos cobertos são exatamente os que `interface_formulario_validacao()` emite hoje:
     * notEmpty, minLength[n], maxLength[n], email, match[campo] e regExp[/…/flags].
     */
    function regraValida(tipo, valor, formulario) {
        valor = (valor === null || valor === undefined) ? '' : String(valor);

        if (tipo === 'notEmpty' || tipo === 'empty') return valor.trim() !== '';
        if (tipo === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valor);

        var minimo = tipo.match(/^minLength\[(\d+)\]$/);
        if (minimo) return valor.length >= parseInt(minimo[1], 10);

        var maximo = tipo.match(/^maxLength\[(\d+)\]$/);
        if (maximo) return valor.length <= parseInt(maximo[1], 10);

        var igual = tipo.match(/^match\[(.+)\]$/);
        if (igual) {
            var outro = formulario.querySelector('[name="' + igual[1] + '"]');
            return outro ? valor === outro.value : true;
        }

        var diferente = tipo.match(/^different\[(.+)\]$/);
        if (diferente) {
            var comparado = formulario.querySelector('[name="' + diferente[1] + '"]');
            return comparado ? valor !== comparado.value : true;
        }

        var expressao = tipo.match(/^regExp\[\/(.*)\/([a-z]*)\]$/);
        if (expressao) {
            try {
                return new RegExp(expressao[1], expressao[2]).test(valor);
            } catch (e) {
                // Regex inválida vinda do PHP não pode reprovar um campo correto: o defeito é do
                // cadastro da regra, e barrar o usuário aqui seria um falso negativo silencioso.
                return true;
            }
        }

        // Tipo desconhecido é ignorado deliberadamente — o legado tem regras que este runtime ainda
        // não precisa, e reprovar por desconhecimento travaria formulários válidos.
        return true;
    }

    /**
     * Valida o formulário inteiro contra as regras declaradas.
     *
     * @return {Array} lista de mensagens de erro, na ordem em que os campos aparecem nas regras.
     */
    function validarFormulario(formulario, regras) {
        var erros = [];

        Object.keys(regras || {}).forEach(function (campo) {
            var definicao = regras[campo] || {};
            var lista = definicao.rules || [];
            var elemento = formulario.querySelector('[name="' + campo + '"]')
                || formulario.querySelector('#' + campo);

            // Campo declarado mas ausente do DOM é o caso normal do perfil: cada bloco de alteração
            // só é renderizado sob a querystring correspondente.
            if (!elemento) return;
            if (elemento.disabled) return;

            var invalido = false;

            for (var i = 0; i < lista.length; i++) {
                if (!regraValida(String(lista[i].type || ''), elemento.value, formulario)) {
                    erros.push(lista[i].prompt || '');
                    invalido = true;
                    break;
                }
            }

            elemento.setAttribute('aria-invalid', invalido ? 'true' : 'false');
            elemento.classList.toggle('border-red-500!', invalido);
        });

        return erros;
    }

    function mostrarErros(formulario, erros) {
        var caixa = formulario.querySelector('[data-c2f-form-erros]');
        if (!caixa) return;

        if (!erros.length) {
            caixa.classList.add('hidden');
            caixa.innerHTML = '';
            return;
        }

        caixa.innerHTML = '<ul class="list-disc space-y-1 pl-5">'
            + erros.map(function (erro) { return '<li>' + erro + '</li>'; }).join('')
            + '</ul>';
        caixa.classList.remove('hidden');
    }

    function ligarFormularios() {
        var regras = (window.gestor && gestor.interface && gestor.interface.regrasValidacao)
            ? gestor.interface.regrasValidacao
            : null;

        var formularios = document.querySelectorAll('form[data-c2f-form="tailwind"]');

        Array.prototype.forEach.call(formularios, function (formulario) {
            // Contrato herdado do legado: a query string do momento do envio viaja com o POST.
            var campo = formulario.querySelector('input[name="_c2f_query_string_before_submit"]');

            if (!campo) {
                campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = '_c2f_query_string_before_submit';
                formulario.appendChild(campo);
            }

            campo.value = window.location.search;

            if (!regras) return;

            formulario.addEventListener('submit', function (evento) {
                var erros = validarFormulario(formulario, regras);

                if (erros.length) {
                    evento.preventDefault();
                    mostrarErros(formulario, erros);
                    return;
                }

                mostrarErros(formulario, []);
                carregarAbrir();
            });
        });
    }

    // ===================================================================================
    // Boot
    // ===================================================================================

    function iniciar() {
        ligarModais();
        ligarFormularios();

        // O modal de Área Restrita nasce no HTML quando a autorização provisória expirou; abri-lo é
        // o que efetivamente bloqueia a tela.
        var autorizacao = modal('autorizacao-provisoria');
        if (autorizacao) abrirModal(autorizacao);

        if (window.gestor && gestor.interface) {
            if (gestor.interface.alerta) alerta(gestor.interface.alerta);
            if (gestor.interface.alert) alerta(gestor.interface.alert);
        }

        // Interoperabilidade com quem já dispara os eventos por jQuery (módulos e widgets legados).
        if (window.jQuery) {
            window.jQuery('#gestor-listener')
                .on('carregar_abrir', function () { carregarAbrir(); })
                .on('carregar_fechar', function () { carregarFechar(); })
                .on('alerta', function (e, p) { alerta(p); });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }

    window.gestorInterfaceTailwind = {
        iniciar: iniciar,
        carregarAbrir: carregarAbrir,
        carregarFechar: carregarFechar,
        alerta: alerta,
        deletarConfirmacao: deletarConfirmacao,
        validarFormulario: validarFormulario,
        regraValida: regraValida
    };
})();
