$(document).ready(function () {
    function adminAtualizacoesMain() {
        const root = $('#admin-atualizacoes-root');
        if (!root.length) return;
        const endpoint = gestor.raiz + gestor.moduloCaminho + '/';
        const statusBox = $('#atualizacoes-status');
        let currentSid = null, currentExecId = null, currentModo = null, polling = null;

        let liveCm = null;
        let liveBufferInitialized = false;
        function initLiveCmIfNeeded() {
            if (liveCm) return;
            const ta = document.getElementById('atualizacoes-log-textarea');
            if (!ta || typeof CodeMirror === 'undefined') return;
            try {
                liveCm = CodeMirror.fromTextArea(ta, {
                    lineNumbers: true,
                    lineWrapping: true,
                    styleActiveLine: true,
                    readOnly: true,
                    theme: 'tomorrow-night-bright',
                    mode: { name: 'javascript', json: false }
                });
                liveCm.setSize('100%', 400);
            } catch (e) { /* fail silent */ }
        }
        function log(msg) {
            const now = new Date().toLocaleTimeString();
            // Mantém statusBox oculto para não duplicar visual; pode ser usado para futura extração.
            initLiveCmIfNeeded();
            atualizarProgressoPorEvento(msg);
            if (liveCm) {
                const doc = liveCm.getDoc();
                const lineText = now + ' - ' + msg;
                if (!liveBufferInitialized && doc.lineCount() <= 1 && doc.getLine(0).trim() === '') {
                    doc.setValue(lineText + '\n');
                    liveBufferInitialized = true;
                } else {
                    // Apêndice no final (última linha real)
                    const lastLine = doc.lastLine();
                    const lastCh = doc.getLine(lastLine).length;
                    doc.replaceRange('\n' + lineText, { line: lastLine, ch: lastCh });
                }
                // Scroll para o fim
                const info = liveCm.getScrollInfo();
                liveCm.scrollTo(null, info.height);
            } else {
                // fallback pre
                const pre = $('.fallback-log');
                if (pre.length) {
                    pre.show();
                    pre.append(document.createTextNode(now + ' - ' + msg + "\n"));
                }
            }
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
            const bar = $('#atualizacoes-progress-bar');
            if (bar.length) {
                if (!bar.data('inited')) { bar.progress({ percent: p }); bar.data('inited', 1); bar.show(); }
                else { bar.progress('set percent', p); }
            }
        }
        function atualizarProgressoPorEvento(msg) {
            for (const k in progressMap) { if (msg.indexOf(k) === 0 || msg.indexOf(k) >= 0) { setProgress(progressMap[k]); break; } }
        }
        // Loader inline
        function ensureInlineLoader() { if (!$('#atualizacoes-inline-loader').length) { $('#atualizacoes-cancel-btn').after('<div id="atualizacoes-inline-loader" class="ui inline loader" style="margin-left:8px;display:none;"></div>'); } }
        function setLoading(on) {
            ensureInlineLoader();
            const ld = $('#atualizacoes-inline-loader');
            if (on) { ld.addClass('active').show(); }
            else { ld.removeClass('active').hide(); }
        }
        function ajax(params) {
            return $.ajax({
                type: 'POST',
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
            statusBox.show().empty();
            log('Iniciando sessão (' + modo + ').');
            setLoading(true);
            $('#atualizacoes-start-btn').addClass('disabled').prop('disabled', true);
            const adv = collectAdvanced(root);
            adv.acao = 'start'; adv.modo = modo;
            // confirmação adicional se usuário marcou --wipe (perigoso)
            if (adv.wipe) {
                const ok = window.confirm('The --wipe option will remove non-protected files. Are you sure you want to continue?');
                if (!ok) { setLoading(false); $('#atualizacoes-start-btn').removeClass('disabled').prop('disabled', false); log('Operação cancelada pelo usuário (wipe não confirmado)'); return; }
            }
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
            }).fail(() => { setLoading(false); log('Falha comunicação start'); $('#atualizacoes-start-btn').removeClass('disabled').prop('disabled', false); });
        }
        function doDeploy() { log('Deploy: executando arquivos + merge .env'); setLoading(true); ajax({ acao: 'deploy', sid: currentSid }).done(resp => { setLoading(false); if (resp.status !== 'ok') { log('Erro deploy: ' + (resp.erro || '')); return; } const data = resp.data; if (data.error) { log('Erro deploy: ' + data.error); return; } if (!currentExecId && data.exec_id) currentExecId = data.exec_id; log('Deploy concluído.'); next(data.next); }).fail(() => { setLoading(false); log('Falha deploy'); }); }
        function doDb() { log('Banco: iniciando'); setLoading(true); ajax({ acao: 'db', sid: currentSid }).done(resp => { setLoading(false); if (resp.status !== 'ok') { log('Erro banco: ' + (resp.erro || '')); return; } const data = resp.data; if (data.error) { log('Erro banco: ' + data.error); } else if (data.skipped) { log('Banco ignorado.'); } else { log('Banco concluído.'); } if (!currentExecId && data.exec_id) currentExecId = data.exec_id; next(data.next); }).fail(() => { setLoading(false); log('Falha banco'); }); }
        function doFinalize() { log('Finalizando sessão'); setLoading(true); ajax({ acao: 'finalize', sid: currentSid }).done(resp => { setLoading(false); if (resp.status !== 'ok') { log('Erro finalize: ' + (resp.erro || '')); return; } const data = resp.data; if (data.error) { log('Erro finalize: ' + data.error); return; } if (!currentExecId && data.exec_id) currentExecId = data.exec_id; log('Sessão finalizada.'); startPolling(); }).fail(() => { setLoading(false); log('Falha finalize'); }); }
        function startPolling() { if (polling) clearInterval(polling); polling = setInterval(() => { ajax({ acao: 'status', sid: currentSid }).done(resp => { if (resp.status !== 'ok') { log('Erro status: ' + (resp.erro || '')); clearInterval(polling); $('#atualizacoes-start-btn').removeClass('disabled').prop('disabled', false); return; } const data = resp.data; if (data.error) { log('Erro status: ' + data.error); clearInterval(polling); $('#atualizacoes-start-btn').removeClass('disabled').prop('disabled', false); return; } if (data.progress_percent !== undefined) { setProgress(data.progress_percent); } if (data.state && data.state.finished) { clearInterval(polling); $('#atualizacoes-cancel-btn').hide(); log('Processo completo.'); $('#atualizacoes-mode-label').text((currentModo || selectedMode || '?') + ' (finalizado)'); $('#atualizacoes-start-btn').removeClass('disabled').prop('disabled', false); setProgress(100); } }); }, 3000); }
        // Cancelar (futuro: endpoint cancel). Exposto para uso
        function cancelar() { if (!currentSid) return; log('Solicitando cancelamento...'); ajax({ acao: 'cancel', sid: currentSid }).done(resp => { if (resp.status === 'ok' && resp.data && resp.data.canceled) { log('Cancelado.'); if (polling) clearInterval(polling); $('#atualizacoes-cancel-btn').hide(); $('#atualizacoes-start-btn').removeClass('disabled').prop('disabled', false); } else { log('Falha ao cancelar'); } }); }
        // Expor algumas funções para extensões futuras (opcional)
        window.adminAtualizacoes = {
            restart: () => { if (polling) clearInterval(polling); currentSid = null; currentExecId = null; statusBox.empty(); },
            status: () => ajax({ acao: 'status', sid: currentSid }),
            execId: () => currentExecId,
            cancelar
        };
        let selectedMode = null;
        root.on('click', '.upd-mode-btn', function () {
            if (currentSid) { log('Sessão em andamento. Aguarde ou cancele.'); return; }
            $('.upd-mode-btn').removeClass('primary');
            $(this).addClass('primary');
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

    // Detalhe: inicializar CodeMirror se presente
    if ($('.codemirror-log').length) {
        $('.codemirror-log').each(function () {
            // Evita inicializar duas vezes a textarea de log vivo (data-live="1")
            if ($(this).data('live')) return;
            var ta = this;
            var mode = $(ta).data('mode') || 'text';
            try {
                var cm = CodeMirror.fromTextArea(ta, {
                    lineNumbers: true,
                    lineWrapping: true,
                    styleActiveLine: true,
                    matchBrackets: true,
                    readOnly: true,
                    theme: 'tomorrow-night-bright',
                    mode: mode === 'application/json' ? { name: 'javascript', json: true } : (mode === 'text' ? 'javascript' : mode)
                });
                cm.setSize('100%', 500);
                $(ta).siblings('.fallback-log').hide();
            } catch (e) {
                // fallback silencioso
            }
        });
    }
});
