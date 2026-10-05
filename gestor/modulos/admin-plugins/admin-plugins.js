function adminPluginsOrigemSelecionar(source, tab) {
	if (!['arquivo', 'publico', 'privado'].includes(tab)) return;

	source.querySelectorAll('[data-plugin-source-tab]').forEach(button => {
		const active = button.getAttribute('data-plugin-source-tab') === tab;
		// req-240: o estado ativo é da folha c2fc-aba, que lê aria-selected.
		button.setAttribute('aria-selected', active ? 'true' : 'false');
	});

	source.querySelectorAll('[data-plugin-source-panel]').forEach(panel => {
		const active = panel.getAttribute('data-plugin-source-panel') === tab;
		panel.hidden = !active;
		panel.classList.toggle('hidden', !active);
	});

	const selectedInput = source.querySelector('#origem_selecionada');
	if (selectedInput) selectedInput.value = tab;
}

function adminPluginsOrigemInicializar(source) {
	const selected = source.getAttribute('data-plugin-source-initial');
	const initialTab = selected === 'github_publico' ? 'publico' : selected === 'github_privado' ? 'privado' : 'arquivo';

	source.addEventListener('click', event => {
		const button = event.target.closest('[data-plugin-source-tab]');
		if (button && source.contains(button)) {
			adminPluginsOrigemSelecionar(source, button.getAttribute('data-plugin-source-tab'));
		}
	});

	adminPluginsOrigemSelecionar(source, initialTab);
}

if (typeof module !== 'undefined' && module.exports) {
	module.exports = { adminPluginsOrigemInicializar };
}

$(document).ready(function () {

	document.querySelectorAll('[data-admin-plugins-source]').forEach(adminPluginsOrigemInicializar);

	// Plugin Execution Interface
	function adminPluginsExecMain() {
		const root = $('#admin-plugins-exec-root');
		if (!root.length) return;

		const endpoint = gestor.raiz + gestor.moduloCaminho + '/';
		const pluginId = root.find('#plugin-id-display').text();
		let currentAction = null;

		function log(msg) {
			const now = new Date().toLocaleTimeString();
			const output = root.find('#plugin-log-output');
			output.removeClass('hidden').append(document.createTextNode(now + ' - ' + msg + '\n'));
			output.scrollTop(output[0].scrollHeight);
		}

		function setProgress(percent) {
			const bar = root.find('#plugin-progress-bar');
			if (bar.length) {
				const value = Math.max(0, Math.min(100, Number(percent) || 0));
				bar.removeClass('hidden').attr('aria-valuenow', value);
				bar.find('[data-progress-fill]').css('width', value + '%');
			}
		}

		function setLoading(on) {
			root.find('button[data-action]').prop('disabled', on).attr('aria-busy', on ? 'true' : 'false');
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
				log(root.attr('data-log-action-running') + ' ' + currentAction);
				return;
			}

			currentAction = action;
			log(root.attr('data-log-action-starting') + ' ' + action);
			setLoading(true);
			setProgress(10);

			ajax({
				acao: action,
				id: pluginId
			}).done(resp => {
				setLoading(false);
				if (resp.status !== 'ok') {
					log(root.attr('data-log-action-error') + ' ' + action + ': ' + (resp.erro || root.attr('data-log-unknown-error')));
					setProgress(0);
					currentAction = null;
					return;
				}

				const data = resp.data;
				log('Ação ' + action + ' ' + root.attr('data-log-action-success'));

				if (data.saida) {
					log(root.attr('data-log-output') + ' ' + data.saida);
				}

				if (data.log) {
					log(root.attr('data-log-detail') + ' ' + data.log);
				}

				setProgress(100);
				currentAction = null;

				// Atualizar status após execução
				setTimeout(() => {
					updateStatus();
				}, 1000);

			}).fail(() => {
				setLoading(false);
				log(root.attr('data-log-communication-error') + ' ' + action);
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
					statusEl.removeClass('bg-red-100 text-red-800 bg-emerald-100 text-emerald-800 bg-amber-100 text-amber-900 bg-slate-100 text-slate-700');

					switch (data.status) {
						case 'A': statusEl.addClass('bg-emerald-100 text-emerald-800').text(root.attr('data-status-active')); break;
						case 'I': statusEl.addClass('bg-amber-100 text-amber-900').text(root.attr('data-status-inactive')); break;
						default: statusEl.addClass('bg-slate-100 text-slate-700').text(root.attr('data-status-unknown')); break;
					}

					if (data.ultima_atualizacao) {
						log(root.attr('data-log-last-update') + ' ' + data.ultima_atualizacao);
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