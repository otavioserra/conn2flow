$(document).ready(function () {

	if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {

	}

	// Plugin Execution Interface
	function adminPluginsExecMain() {
		const root = $('#admin-plugins-exec-root');
		if (!root.length) return;

		const endpoint = gestor.raiz + gestor.moduloCaminho + '/';
		const pluginId = root.find('#plugin-id-display').text();
		let currentAction = null;

		let liveCm = null;
		let liveBufferInitialized = false;

		function initLiveCmIfNeeded() {
			if (liveCm) return;
			const ta = document.getElementById('plugin-log-textarea');
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
			initLiveCmIfNeeded();

			if (liveCm) {
				const doc = liveCm.getDoc();
				const lineText = now + ' - ' + msg;
				if (!liveBufferInitialized && doc.lineCount() <= 1 && doc.getLine(0).trim() === '') {
					doc.setValue(lineText + '\n');
					liveBufferInitialized = true;
				} else {
					const lastLine = doc.lastLine();
					const lastCh = doc.getLine(lastLine).length;
					doc.replaceRange('\n' + lineText, { line: lastLine, ch: lastCh });
				}
				const info = liveCm.getScrollInfo();
				liveCm.scrollTo(null, info.height);
			} else {
				const pre = $('.fallback-log');
				if (pre.length) {
					pre.show();
					pre.append(document.createTextNode(now + ' - ' + msg + "\n"));
				}
			}
		}

		function setProgress(percent) {
			const bar = $('#plugin-progress-bar');
			if (bar.length) {
				if (!bar.data('inited')) {
					bar.progress({ percent: percent });
					bar.data('inited', 1);
					bar.show();
				} else {
					bar.progress('set percent', percent);
				}
			}
		}

		function setLoading(on) {
			const buttons = root.find('.ui.button');
			if (on) {
				buttons.addClass('loading disabled');
			} else {
				buttons.removeClass('loading disabled');
			}
		}

		function ajax(params) {
			return $.ajax({
				type: 'POST',
				url: endpoint,
				data: {
					opcao: gestor.moduloOpcao,
					ajax: 'sim',
					ajaxOpcao: 'update',
					params: params
				},
				dataType: 'json',
				beforeSend: function () {
					if ($.carregar_abrir) $.carregar_abrir();
				},
				complete: function () {
					if ($.carregar_fechar) $.carregar_fechar();
				}
			});
		}

		function executeAction(action) {
			if (currentAction) {
				log('Ação já em andamento: ' + currentAction);
				return;
			}

			currentAction = action;
			log('Iniciando ação: ' + action);
			setLoading(true);
			setProgress(10);

			ajax({
				acao: action,
				id: pluginId
			}).done(resp => {
				setLoading(false);
				if (resp.status !== 'ok') {
					log('Erro na ação ' + action + ': ' + (resp.erro || 'Erro desconhecido'));
					setProgress(0);
					currentAction = null;
					return;
				}

				const data = resp.data;
				log('Ação ' + action + ' executada com sucesso');

				if (data.saida) {
					log('Saída: ' + data.saida);
				}

				if (data.log) {
					log('Log detalhado: ' + data.log);
				}

				setProgress(100);
				currentAction = null;

				// Atualizar status após execução
				setTimeout(() => {
					updateStatus();
				}, 1000);

			}).fail(() => {
				setLoading(false);
				log('Falha na comunicação para ação: ' + action);
				setProgress(0);
				currentAction = null;
			});
		}

		function updateStatus() {
			ajax({
				acao: 'status',
				id: pluginId
			}).done(resp => {
				if (resp.status === 'ok' && resp.data) {
					const data = resp.data;
					const statusEl = $('#plugin-status-display');
					statusEl.removeClass('red green yellow blue');

					switch (data.status) {
						case 'A': statusEl.addClass('green').text('Ativo'); break;
						case 'I': statusEl.addClass('yellow').text('Inativo'); break;
						default: statusEl.addClass('grey').text('Desconhecido'); break;
					}

					if (data.ultima_atualizacao) {
						log('Última atualização: ' + data.ultima_atualizacao);
					}
				}
			});
		}

		// Event handlers
		root.on('click', '#plugin-install-btn', function () {
			executeAction('instalar');
		});

		root.on('click', '#plugin-update-btn', function () {
			executeAction('atualizar');
		});

		root.on('click', '#plugin-reprocess-btn', function () {
			executeAction('reprocessar');
		});

		// Initialize
		updateStatus();
	}

	adminPluginsExecMain();

});