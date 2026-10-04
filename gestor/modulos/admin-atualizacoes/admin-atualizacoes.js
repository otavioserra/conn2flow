$(document).ready(function () {
    // req-199 / BATCH-205: decisão sobre um choque (detalhe/?choque=<id>).
    (function choqueDecisao() {
        const box = $('[data-choque-decisao]');
        if (!box.length) return;
        const url = gestor.raiz + String(gestor.moduloCaminho).replace(/\/+$/, '') + '/';
        const msg = box.find('[data-choque-msg]');
        function mostrar(tipo, texto) {
            const elemento = msg[0];
            if (!elemento) return;
            elemento.textContent = texto;
            elemento.classList.remove('hidden', 'border-emerald-300', 'bg-emerald-50', 'text-emerald-900', 'border-rose-300', 'bg-rose-50', 'text-rose-900');
            elemento.classList.add(tipo === 'positive' ? 'border-emerald-300' : 'border-rose-300');
            elemento.classList.add(tipo === 'positive' ? 'bg-emerald-50' : 'bg-rose-50');
            elemento.classList.add(tipo === 'positive' ? 'text-emerald-900' : 'text-rose-900');
        }
        function resolver(acao, conteudo) {
            const dados = { opcao: gestor.moduloOpcao, ajax: 'sim', ajaxOpcao: 'choque-resolver', id: box.data('choque-id'), acao: acao };
            if (conteudo !== undefined) dados.conteudo = conteudo;
            box.find('button').prop('disabled', true);
            $.ajax({ type: 'POST', url: url, data: dados, dataType: 'json' })
                .done(function (r) {
                    if (r && r.status === 'Ok') { mostrar('positive', box.data('msg-ok')); setTimeout(function () { window.location.reload(); }, 900); }
                    else { box.find('button').prop('disabled', false); mostrar('negative', (r && r.message) || 'Erro'); }
                })
                .fail(function (x) {
                    box.find('button').prop('disabled', false);
                    if (x.status === 401) { window.location.href = gestor.raiz + 'signin/'; return; }
                    mostrar('negative', 'HTTP ' + x.status);
                });
        }
        box.on('click', '[data-choque-acao]', function () {
            const acao = $(this).data('choque-acao');
            if (acao === 'mesclar') {
                const mergeForm = box.find('[data-choque-mescla]')[0];
                if (mergeForm) mergeForm.classList.toggle('hidden');
                return;
            }
            window.c2fControles.dialogo.confirmar(box.data('msg-confirmar'), { perigo: acao === 'sobrescrever' }).then(function (confirmado) {
                if (confirmado) resolver(acao);
            });
        });
        box.on('click', '[data-choque-mesclar-salvar]', function () {
            window.c2fControles.dialogo.confirmar(box.data('msg-confirmar')).then(function (confirmado) {
                if (confirmado) resolver('mesclar', box.find('[data-choque-conteudo]').val());
            });
        });
    })();

    function adminAtualizacoesMain() {
        const root = $('#admin-atualizacoes-root');
        if (!root.length) return;
        const endpoint = gestor.raiz + gestor.moduloCaminho + '/';
        const statusBox = $('#atualizacoes-status');
        let currentSid = null, currentExecId = null, currentModo = null, polling = null;

        function log(msg) {
            const now = new Date().toLocaleTimeString();
            atualizarProgressoPorEvento(msg);
            const pre = document.querySelector('#admin-atualizacoes-root .fallback-log');
            if (!pre) return;
            pre.classList.remove('hidden');
            pre.append(document.createTextNode(now + ' - ' + msg + '\n'));
            pre.scrollTop = pre.scrollHeight;
        }
        // ---- Progresso ----
        const progressMap = {
            'Iniciando sessão': 5,
            'Sessão criada': 10,
            'Deploy: executando arquivos': 25,
            'Deploy concluído': 55,
            'Banco: iniciando': 60,
            'Banco concluído': 85,
            'Banco ignorado': 70,
            'Finalizando sessão': 90,
            'Sessão finalizada': 95,
            'Processo completo': 100
        };
        let lastProgress = 0;
        function setProgress(p) {
            if (p <= lastProgress) return;
            lastProgress = p;
            const bar = document.getElementById('atualizacoes-progress-bar');
            if (!bar) return;
            bar.classList.remove('hidden');
            bar.setAttribute('aria-valuenow', String(p));
            const fill = bar.querySelector('[data-progress-fill]');
            if (fill) fill.style.width = p + '%';
        }
        function atualizarProgressoPorEvento(msg) {
            for (const k in progressMap) { if (msg.indexOf(k) === 0 || msg.indexOf(k) >= 0) { setProgress(progressMap[k]); break; } }
        }
        function setLoading(on) {
            const loader = document.getElementById('atualizacoes-inline-loader');
            if (loader) loader.classList.toggle('hidden', !on);
        }
        function ajax(params, method) {
            return $.ajax({
                type: method || 'POST',
                url: endpoint,
                data: { opcao: gestor.moduloOpcao, ajax: 'sim', ajaxOpcao: 'update', params: params },
                dataType: 'json',
                beforeSend: function () { $.carregar_abrir && $.carregar_abrir(); },
                complete: function () { $.carregar_fechar && $.carregar_fechar(); }
            });
        }
        function next(step) { if (step === 'deploy_files' || step === 'deploy') { doDeploy(); } else if (step === 'database' || step === 'db') { doDb(); } else if (step === 'finalize') { doFinalize(); } }
        function collectAdvanced(rootEl) {
            const out = {};
            rootEl.find('.upd-flag:checked').each(function () { const fl = $(this).data('flag'); out[fl] = 1; });
            // campos
            const tag = rootEl.find('#upd-tag, #upd-tag-d').first().val(); if (tag) out.tag = tag;
            const domain = rootEl.find('#upd-domain, #upd-domain-d').first().val(); if (domain) out.domain = domain;
            const tables = rootEl.find('#upd-tables, #upd-tables-d').first().val(); if (tables) out.tables = tables;
            const logsRet = rootEl.find('#upd-logs-retention-days, #upd-logs-retention-days-d').first().val(); if (logsRet) out.logs_retention_days = logsRet;
            return out;
        }
        function doStart(modo) {
            statusBox.empty();
            log('Iniciando sessão (' + modo + ').');
            const adv = collectAdvanced(root);
            adv.acao = 'start'; adv.modo = modo; adv.csrf_capable = 1;
            const startButton = $('#atualizacoes-start-btn');
            function iniciar() {
                setLoading(true);
                startButton.prop('disabled', true);
                ajax(adv).done(resp => {
                    setLoading(false);
                    if (resp.status !== 'ok') { log('Erro start: ' + (resp.erro || '')); return; }
                    const data = resp.data;
                    if (data.error) { log('Erro start: ' + data.error); return; }
                    currentSid = data.sid; currentExecId = data.exec_id || null; currentModo = modo;
                    $('#atualizacoes-cancel-btn').show(); setProgress(10);
                    $('#atualizacoes-mode-label').text(currentModo + ' (iniciado)');
                    log('Sessão criada: ' + data.sid + ' exec_id=' + (currentExecId || '?') + ' tag=' + data.release_tag + ' modo=' + currentModo);
                    next(data.next);
                }).fail(() => { setLoading(false); log('Falha comunicação start'); $('#atualizacoes-start-btn').prop('disabled', false); });
            }
            if (adv.wipe) {
                startButton.prop('disabled', true);
                window.c2fControles.dialogo.confirmar(root[0].dataset.msgWipeConfirm, { perigo: true }).then(function (confirmado) {
                    if (confirmado) iniciar();
                    else {
                        startButton.prop('disabled', false);
                        log('Operação cancelada pelo usuário (wipe não confirmado)');
                    }
                }).catch(function () { startButton.prop('disabled', false); });
                return;
            }
            iniciar();
        }
        function doDeploy() { log('Deploy: executando arquivos + merge .env'); setLoading(true); ajax({ acao: 'deploy', sid: currentSid }).done(resp => { setLoading(false); if (resp.status !== 'ok') { log('Erro deploy: ' + (resp.erro || '')); return; } const data = resp.data; if (data.error) { log('Erro deploy: ' + data.error); return; } if (!currentExecId && data.exec_id) currentExecId = data.exec_id; log('Deploy concluído.'); next(data.next); }).fail(() => { setLoading(false); log('Falha deploy'); }); }
        function doDb() { log('Banco: iniciando'); setLoading(true); ajax({ acao: 'db', sid: currentSid }).done(resp => { setLoading(false); if (resp.status !== 'ok') { log('Erro banco: ' + (resp.erro || '')); return; } const data = resp.data; if (data.error) { log('Erro banco: ' + data.error); } else if (data.skipped) { log('Banco ignorado.'); } else { log('Banco concluído.'); } if (!currentExecId && data.exec_id) currentExecId = data.exec_id; next(data.next); }).fail(() => { setLoading(false); log('Falha banco'); }); }
        function concluirInterface() {
            if (polling) clearInterval(polling);
            polling = null;
            $('#atualizacoes-cancel-btn').hide();
            log('Processo completo.');
            $('#atualizacoes-mode-label').text((currentModo || selectedMode || '?') + ' (finalizado)');
            $('#atualizacoes-start-btn').prop('disabled', false);
            setProgress(100);
        }
        function doFinalize() {
            log('Finalizando sessão');
            setLoading(true);
            ajax({ acao: 'finalize', sid: currentSid }).done(resp => {
                setLoading(false);
                if (resp.status !== 'ok') { log('Erro finalize: ' + (resp.erro || '')); return; }
                const data = resp.data;
                if (data.error) { log('Erro finalize: ' + data.error); return; }
                if (!currentExecId && data.exec_id) currentExecId = data.exec_id;
                log('Sessão finalizada.');
                if (data.finished || data.already) { concluirInterface(); return; }
                startPolling();
            }).fail(() => { setLoading(false); log('Falha finalize'); });
        }
        function startPolling() {
            if (polling) clearInterval(polling);
            polling = setInterval(() => {
                ajax({ acao: 'status', sid: currentSid }, 'GET').done(resp => {
                    if (resp.status !== 'ok') {
                        log('Erro status: ' + (resp.erro || ''));
                        clearInterval(polling);
                        polling = null;
                        $('#atualizacoes-start-btn').prop('disabled', false);
                        return;
                    }
                    const data = resp.data;
                    if (data.error) {
                        log('Erro status: ' + data.error);
                        clearInterval(polling);
                        polling = null;
                        $('#atualizacoes-start-btn').prop('disabled', false);
                        return;
                    }
                    if (data.progress_percent !== undefined) setProgress(data.progress_percent);
                    if (data.state && data.state.finished) concluirInterface();
                }).fail(xhr => {
                    clearInterval(polling);
                    polling = null;
                    log('Falha status: HTTP ' + (xhr.status || '?'));
                    $('#atualizacoes-start-btn').prop('disabled', false);
                });
            }, 3000);
        }
        // Cancelar (futuro: endpoint cancel). Exposto para uso
        function cancelar() { if (!currentSid) return; log('Solicitando cancelamento...'); ajax({ acao: 'cancel', sid: currentSid }).done(resp => { if (resp.status === 'ok' && resp.data && resp.data.canceled) { log('Cancelado.'); if (polling) clearInterval(polling); $('#atualizacoes-cancel-btn').hide(); $('#atualizacoes-start-btn').prop('disabled', false); } else { log('Falha ao cancelar'); } }); }
        // Expor algumas funções para extensões futuras (opcional)
        window.adminAtualizacoes = {
            restart: () => { if (polling) clearInterval(polling); currentSid = null; currentExecId = null; statusBox.empty(); },
            status: () => ajax({ acao: 'status', sid: currentSid }, 'GET'),
            execId: () => currentExecId,
            cancelar
        };
        let selectedMode = null;
        root.on('click', '.upd-mode-btn', function () {
            if (currentSid) { log('Sessão em andamento. Aguarde ou cancele.'); return; }
            root.find('.upd-mode-btn').attr('aria-pressed', 'false').removeAttr('data-selected');
            $(this).attr('aria-pressed', 'true').attr('data-selected', 'true');
            selectedMode = $(this).data('modo');
            $('#atualizacoes-mode-label').text(selectedMode + ' (selecionado)');
        });
        root.on('click', '#atualizacoes-start-btn', function () {
            if (currentSid) { log('Já existe sessão em andamento: ' + currentSid); return; }
            if (!selectedMode) { log('Selecione um modo antes de iniciar.'); return; }
            doStart(selectedMode);
        });
        root.on('click', '#atualizacoes-cancel-btn', function () { cancelar(); });
    }

    adminAtualizacoesMain();

});
