/**
 * Painel de rotinas automáticas (REQ-032 / BATCH-026).
 *
 * A tabela é desenhada pelo cliente a partir do dataset que o PHP entrega em
 * `data-cron-tarefas`, e redesenhada com a lista que cada endpoint AJAX devolve. Manter o
 * desenho num único lugar evita a classe de bug que dominou o BATCH-024: contrato divergente
 * entre marcadores do template e ids lidos pelo JavaScript.
 *
 * Todo texto visível vem de atributos data-* preenchidos com variáveis do sistema — nenhum
 * literal de interface mora neste arquivo.
 */
(function () {
    'use strict';

    var painel = document.getElementById('admin-cron-painel');
    if (!painel) return;

    // ===== Estado

    var estado = {
        tarefas: [],
        modulos: [],
        filtroBusca: '',
        filtroFrequencia: '',
        filtroModulo: '',
        editando: null
    };

    var rotulos = painel.dataset;

    var el = {
        corpo: document.getElementById('cron-tabela-corpo'),
        mensagem: document.getElementById('cron-mensagem'),
        statTotal: document.getElementById('cron-stat-total'),
        statAtivas: document.getElementById('cron-stat-ativas'),
        statExecucoes: document.getElementById('cron-stat-execucoes'),
        filtroBusca: document.getElementById('cron-filtro-busca'),
        filtroFrequencia: document.getElementById('cron-filtro-frequencia'),
        filtroModulo: document.getElementById('cron-filtro-modulo'),
        btnSync: document.getElementById('cron-btn-sync'),
        btnNovo: document.getElementById('cron-btn-new'),
        modal: document.getElementById('cron-modal'),
        modalTitulo: document.getElementById('cron-modal-titulo'),
        modalFechar: document.getElementById('cron-modal-fechar'),
        form: document.getElementById('cron-form'),
        formAviso: document.getElementById('cron-form-aviso'),
        formCancelar: document.getElementById('cron-form-cancelar'),
        formSalvar: document.getElementById('cron-form-salvar'),
        logs: document.getElementById('cron-logs'),
        logsFechar: document.getElementById('cron-logs-fechar'),
        logsTarefa: document.getElementById('cron-logs-tarefa'),
        logsDisparo: document.getElementById('cron-logs-disparo'),
        logsDuracao: document.getElementById('cron-logs-duracao'),
        logsStatus: document.getElementById('cron-logs-status'),
        logsSaida: document.getElementById('cron-logs-saida')
    };

    var campos = {
        id: document.getElementById('cron-form-id'),
        nome: document.getElementById('cron-form-nome'),
        descricao: document.getElementById('cron-form-descricao'),
        frequencia: document.getElementById('cron-form-frequencia'),
        expressao: document.getElementById('cron-form-expressao'),
        callback: document.getElementById('cron-form-callback'),
        modulo: document.getElementById('cron-form-modulo'),
        parametros: document.getElementById('cron-form-parametros'),
        ativo: document.getElementById('cron-form-ativo')
    };

    // ===== Utilidades

    function jsonDoDataset(valor) {
        if (!valor) return [];
        try {
            var lido = JSON.parse(valor);
            return Array.isArray(lido) ? lido : [];
        } catch (e) {
            return [];
        }
    }

    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto === null || texto === undefined ? '' : String(texto);
        return div.innerHTML;
    }

    function rotuloFrequencia(frequencia) {
        var mapa = {
            minutario: rotulos.labelFreqMinutario,
            horario: rotulos.labelFreqHorario,
            diario: rotulos.labelFreqDiario,
            mensal: rotulos.labelFreqMensal,
            customizado: rotulos.labelFreqCustomizado
        };
        return mapa[frequencia] || frequencia;
    }

    function rotuloStatus(status) {
        var mapa = {
            sucesso: rotulos.labelStatusSucesso,
            erro: rotulos.labelStatusErro,
            aviso: rotulos.labelStatusAviso
        };
        return mapa[status] || '';
    }

    function classeStatus(status) {
        if (status === 'sucesso') return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
        if (status === 'erro') return 'bg-rose-50 text-rose-700 ring-rose-600/20';
        if (status === 'aviso') return 'bg-amber-50 text-amber-800 ring-amber-600/20';
        return 'bg-slate-100 text-slate-600 ring-slate-500/20';
    }

    function formatarDuracao(ms) {
        if (ms === null || ms === undefined || ms === '') return '—';
        var valor = parseInt(ms, 10);
        if (isNaN(valor)) return '—';
        if (valor < 1000) return valor + ' ms';
        return (valor / 1000).toFixed(2) + ' s';
    }

    /**
     * A visibilidade é controlada SEMPRE pela classe `hidden` do Tailwind, nunca pelo atributo
     * `hidden` do HTML: como ambos resolvem em `display:none`, remover só o atributo deixaria a
     * classe do template continuar escondendo o elemento.
     */
    function mostrarMensagem(texto, erro) {
        if (!el.mensagem) return;
        el.mensagem.textContent = texto;
        el.mensagem.className = erro
            ? 'mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800'
            : 'mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800';
    }

    function limparMensagem() {
        if (!el.mensagem) return;
        el.mensagem.textContent = '';
        el.mensagem.className = 'mb-4 hidden rounded-lg border px-4 py-3 text-sm';
    }

    // ===== Transporte

    /**
     * Requisição AJAX no contrato do Gestor: sem `ajax: 'sim'` o núcleo trata a chamada como
     * submissão de formulário e responde 403 por CSRF.
     */
    function chamar(ajaxOpcao, dados) {
        var params = new URLSearchParams({
            opcao: 'painel',
            ajax: 'sim',
            ajaxOpcao: ajaxOpcao
        });

        if (dados) {
            Object.keys(dados).forEach(function (chave) {
                var valor = dados[chave];
                params.set(chave, typeof valor === 'object' && valor !== null ? JSON.stringify(valor) : valor);
            });
        }

        return fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        }).then(function (resposta) {
            if (resposta.status === 401) {
                window.location.href = (window.gestor && window.gestor.raiz ? window.gestor.raiz : '/') + 'signin/';
                throw new Error('unauthorized');
            }
            if (!resposta.ok) throw new Error('HTTP ' + resposta.status);
            return resposta.json();
        }).then(function (json) {
            if (!json || json.status !== 'Ok') {
                throw new Error((json && json.message) || rotulos.msgGenericError);
            }
            return json;
        });
    }

    function aplicarRetorno(json) {
        if (json.data && Array.isArray(json.data.tarefas)) {
            estado.tarefas = json.data.tarefas;
            atualizarModulos();
            renderizar();
        }
        if (json.data && json.data.estatisticas) {
            atualizarEstatisticas(json.data.estatisticas);
        }
        if (json.message) mostrarMensagem(json.message, false);
    }

    function tratarErro(erro) {
        if (erro && erro.message === 'unauthorized') return;
        mostrarMensagem((erro && erro.message) || rotulos.msgGenericError, true);
    }

    // ===== Renderização

    function atualizarEstatisticas(stats) {
        if (el.statTotal) el.statTotal.textContent = stats.total;
        if (el.statAtivas) el.statAtivas.textContent = stats.ativas;
        if (el.statExecucoes) el.statExecucoes.textContent = stats.execucoes;
    }

    function atualizarModulos() {
        var vistos = [];
        estado.tarefas.forEach(function (t) {
            if (t.modulo && vistos.indexOf(t.modulo) === -1) vistos.push(t.modulo);
        });
        vistos.sort();
        estado.modulos = vistos;

        if (!el.filtroModulo) return;

        var selecionado = el.filtroModulo.value;
        // A primeira opção é o "todos", renderizado pelo template com a variável do sistema.
        while (el.filtroModulo.options.length > 1) el.filtroModulo.remove(1);
        vistos.forEach(function (modulo) {
            var opt = document.createElement('option');
            opt.value = modulo;
            opt.textContent = modulo;
            el.filtroModulo.appendChild(opt);
        });
        if (vistos.indexOf(selecionado) !== -1) el.filtroModulo.value = selecionado;
    }

    function filtrar() {
        var busca = estado.filtroBusca.toLowerCase();
        return estado.tarefas.filter(function (t) {
            if (estado.filtroFrequencia && t.frequencia !== estado.filtroFrequencia) return false;
            if (estado.filtroModulo && t.modulo !== estado.filtroModulo) return false;
            if (!busca) return true;
            return (t.id + ' ' + t.nome + ' ' + t.funcao_callback).toLowerCase().indexOf(busca) !== -1;
        });
    }

    function linhaHtml(t) {
        var statusTexto = t.ultimo_status ? rotuloStatus(t.ultimo_status) : rotulos.msgNeverRun;
        var pausada = !t.ativo
            ? '<span class="ml-2 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-500/20 ring-inset">' + escapar(rotulos.msgPaused) + '</span>'
            : '';
        var origem = t.origem === 'manual' ? rotulos.labelOriginManual : rotulos.labelOriginModulo;

        return '' +
            '<tr class="hover:bg-slate-50">' +
            '<td class="px-4 py-3 align-top">' +
                '<div class="font-medium text-slate-900">' + escapar(t.nome) + pausada + '</div>' +
                '<div class="font-mono text-xs text-slate-400">' + escapar(t.id) + '</div>' +
                '<div class="text-xs text-slate-400">' + escapar(origem) + '</div>' +
            '</td>' +
            '<td class="px-4 py-3 align-top text-slate-600">' + escapar(t.modulo || '—') + '</td>' +
            '<td class="px-4 py-3 align-top">' +
                '<div class="text-slate-700">' + escapar(rotuloFrequencia(t.frequencia)) + '</div>' +
                '<div class="font-mono text-xs text-slate-400">' + escapar(t.expressao_cron) + '</div>' +
            '</td>' +
            '<td class="px-4 py-3 align-top text-slate-600">' + escapar(t.ultimo_disparo || '—') + '</td>' +
            '<td class="px-4 py-3 align-top text-slate-600">' + escapar(formatarDuracao(t.ultima_duracao_ms)) + '</td>' +
            '<td class="px-4 py-3 align-top">' +
                '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ' + classeStatus(t.ultimo_status) + '">' +
                    escapar(statusTexto) +
                '</span>' +
            '</td>' +
            '<td class="px-4 py-3 text-right align-top">' +
                '<div class="inline-flex flex-wrap justify-end gap-1">' +
                    botaoHtml('run', t.id, rotulos.labelRun) +
                    botaoHtml('toggle', t.id, t.ativo ? rotulos.labelPause : rotulos.labelActivate) +
                    botaoHtml('edit', t.id, rotulos.labelEdit) +
                    botaoHtml('logs', t.id, rotulos.labelLogs) +
                    (t.origem === 'manual' ? botaoHtml('delete', t.id, rotulos.labelDelete) : '') +
                '</div>' +
            '</td>' +
            '</tr>';
    }

    /**
     * `type="button"` é obrigatório: um `<button>` sem type dentro de qualquer form submete a
     * página e produz o recarregamento involuntário corrigido no BATCH-024.
     */
    function botaoHtml(acao, id, rotulo) {
        return '<button type="button" data-acao="' + acao + '" data-id="' + escapar(id) + '" ' +
            'class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400">' +
            escapar(rotulo) + '</button>';
    }

    function renderizar() {
        if (!el.corpo) return;

        var lista = filtrar();

        if (!lista.length) {
            el.corpo.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">' +
                escapar(rotulos.msgEmpty) + '</td></tr>';
            return;
        }

        el.corpo.innerHTML = lista.map(linhaHtml).join('');
    }

    // ===== Modal

    function tarefaPorId(id) {
        for (var i = 0; i < estado.tarefas.length; i++) {
            if (estado.tarefas[i].id === id) return estado.tarefas[i];
        }
        return null;
    }

    function abrirModal(tarefa) {
        estado.editando = tarefa;

        var deModulo = !!tarefa && tarefa.origem === 'modulo';

        el.modalTitulo.textContent = tarefa ? rotulos.titleEdit : rotulos.titleNew;
        el.formAviso.classList.toggle('hidden', !deModulo);

        campos.id.value = tarefa ? tarefa.id : '';
        campos.nome.value = tarefa ? tarefa.nome : '';
        campos.descricao.value = tarefa ? tarefa.descricao : '';
        campos.frequencia.value = tarefa ? tarefa.frequencia : 'diario';
        campos.expressao.value = tarefa ? tarefa.expressao_cron : '';
        campos.callback.value = tarefa ? tarefa.funcao_callback : '';
        campos.modulo.value = tarefa ? tarefa.modulo : '';
        campos.parametros.value = tarefa ? tarefa.parametros : '';
        campos.ativo.checked = tarefa ? !!tarefa.ativo : true;

        // Numa tarefa de módulo a autoria vive no arquivo versionado; só o agendamento é editável.
        campos.id.disabled = deModulo || !!tarefa;
        campos.nome.disabled = deModulo;
        campos.descricao.disabled = deModulo;
        campos.callback.disabled = deModulo;
        campos.modulo.disabled = deModulo;

        el.modal.classList.remove('hidden');
        el.modal.classList.add('flex');
    }

    function fecharModal() {
        estado.editando = null;
        el.modal.classList.add('hidden');
        el.modal.classList.remove('flex');
    }

    function abrirLogs(tarefa) {
        el.logsTarefa.textContent = tarefa.nome;
        el.logsDisparo.textContent = tarefa.ultimo_disparo || '—';
        el.logsDuracao.textContent = formatarDuracao(tarefa.ultima_duracao_ms);
        el.logsStatus.textContent = tarefa.ultimo_status ? rotuloStatus(tarefa.ultimo_status) : rotulos.msgNeverRun;
        el.logsSaida.textContent = tarefa.ultimo_log || rotulos.msgLogsEmpty;

        el.logs.classList.remove('hidden');
        el.logs.classList.add('flex');
    }

    function fecharLogs() {
        el.logs.classList.add('hidden');
        el.logs.classList.remove('flex');
    }

    // ===== Ações

    function comBloqueio(botao, promessa) {
        if (botao) botao.disabled = true;
        return promessa.then(aplicarRetorno).catch(tratarErro).then(function () {
            if (botao) botao.disabled = false;
        });
    }

    function acaoLinha(evento) {
        var botao = evento.target.closest('button[data-acao]');
        if (!botao) return;

        var id = botao.dataset.id;
        var tarefa = tarefaPorId(id);
        if (!tarefa) return;

        limparMensagem();

        switch (botao.dataset.acao) {
            case 'run':
                comBloqueio(botao, chamar('disparar', { id: id }));
                break;
            case 'toggle':
                comBloqueio(botao, chamar('alternar', { id: id }));
                break;
            case 'edit':
                abrirModal(tarefa);
                break;
            case 'logs':
                abrirLogs(tarefa);
                break;
            case 'delete':
                if (window.confirm(rotulos.msgConfirmDelete)) {
                    comBloqueio(botao, chamar('excluir', { id: id }));
                }
                break;
        }
    }

    function salvar(evento) {
        evento.preventDefault();
        limparMensagem();

        var dados = {
            id: campos.id.value.trim(),
            nome: campos.nome.value.trim(),
            descricao: campos.descricao.value.trim(),
            frequencia: campos.frequencia.value,
            expressao_cron: campos.expressao.value.trim(),
            funcao_callback: campos.callback.value.trim(),
            modulo: campos.modulo.value.trim(),
            parametros: campos.parametros.value.trim(),
            ativo: campos.ativo.checked ? 1 : 0,
            id_original: estado.editando ? estado.editando.id : ''
        };

        // Numa tarefa de módulo os campos de autoria chegam desabilitados e portanto vazios; o
        // backend os ignora, mas reenviá-los preenchidos mantém a validação satisfeita.
        if (estado.editando && estado.editando.origem === 'modulo') {
            dados.id = estado.editando.id;
            dados.nome = estado.editando.nome;
            dados.descricao = estado.editando.descricao;
            dados.funcao_callback = estado.editando.funcao_callback;
            dados.modulo = estado.editando.modulo;
        }

        el.formSalvar.disabled = true;
        chamar('salvar', dados).then(function (json) {
            aplicarRetorno(json);
            fecharModal();
        }).catch(tratarErro).then(function () {
            el.formSalvar.disabled = false;
        });
    }

    // ===== Ligações

    estado.tarefas = jsonDoDataset(painel.dataset.cronTarefas);
    estado.modulos = jsonDoDataset(painel.dataset.cronModulos);

    atualizarModulos();
    renderizar();

    if (el.corpo) el.corpo.addEventListener('click', acaoLinha);

    if (el.filtroBusca) {
        el.filtroBusca.addEventListener('input', function () {
            estado.filtroBusca = this.value.trim();
            renderizar();
        });
    }

    if (el.filtroFrequencia) {
        el.filtroFrequencia.addEventListener('change', function () {
            estado.filtroFrequencia = this.value;
            renderizar();
        });
    }

    if (el.filtroModulo) {
        el.filtroModulo.addEventListener('change', function () {
            estado.filtroModulo = this.value;
            renderizar();
        });
    }

    if (el.btnSync) {
        el.btnSync.addEventListener('click', function () {
            limparMensagem();
            comBloqueio(el.btnSync, chamar('sincronizar'));
        });
    }

    if (el.btnNovo) {
        el.btnNovo.addEventListener('click', function () {
            limparMensagem();
            abrirModal(null);
        });
    }

    if (el.modalFechar) el.modalFechar.addEventListener('click', fecharModal);
    if (el.formCancelar) el.formCancelar.addEventListener('click', fecharModal);
    if (el.form) el.form.addEventListener('submit', salvar);
    if (el.logsFechar) el.logsFechar.addEventListener('click', fecharLogs);

    // Clique no fundo escurecido fecha; clique dentro do painel não deve fechar.
    if (el.modal) {
        el.modal.addEventListener('click', function (evento) {
            if (evento.target === el.modal) fecharModal();
        });
    }
    if (el.logs) {
        el.logs.addEventListener('click', function (evento) {
            if (evento.target === el.logs) fecharLogs();
        });
    }

    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape') return;
        if (!el.modal.classList.contains('hidden')) fecharModal();
        if (!el.logs.classList.contains('hidden')) fecharLogs();
    });
})();
