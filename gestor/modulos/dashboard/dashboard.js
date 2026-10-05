$(document).ready(function () {
	// ===== localStorage < =====

	function localStorageExpires() {
		/**
		* Função para limpar itens no localStorage
		*/

		var toRemove = [],                      //Itens para serem removidos
			currentDate = new Date().getTime(); //Data atual em milissegundos

		for (var i = 0, j = localStorage.length; i < j; i++) {
			var key = localStorage.key(i),
				itemValue = localStorage.getItem(key);

			//Verifica se o formato do item para evitar conflitar com outras aplicações
			if (itemValue && /^\{(.*?)\}$/.test(itemValue)) {

				//Decodifica de volta para JSON
				var current = JSON.parse(itemValue);

				//Checa a chave expires do item especifico se for mais antigo que a data atual ele salva no array
				if (current.expires && current.expires <= currentDate) {
					toRemove.push(key);
				}
			}
		}

		// Remove itens que já passaram do tempo
		// Se remover no primeiro loop isto poderia afetar a ordem,
		// pois quando se remove um item geralmente o objeto ou array são reordenados
		for (var i = toRemove.length - 1; i >= 0; i--) {
			localStorage.removeItem(toRemove[i]);
		}
	}

	function setLocalStorage(chave, valor, minutos) {
		/**
		* Função para adicionar itens no localStorage
		* @param {string} chave Chave que será usada para obter o valor posteriormente
		* @param {*} valor Quase qualquer tipo de valor pode ser adicionado, desde que não falhe no JSON.stringify
		* @param {number} minutos Tempo de vida do item
		*/

		var expirarem = new Date().getTime() + (60000 * minutos);

		localStorage.setItem(chave, JSON.stringify({
			"value": valor,
			"expires": expirarem
		}));
	}

	function getLocalStorage(chave) {
		/**
		* Função para obter itens do localStorage que ainda não expiraram
		* @param {string} chave Chave para obter o valor associado
		* @return {*} Retorna qualquer valor, se o item tiver expirado irá retorna undefined
		*/

		localStorageExpires(); //Limpa itens

		var itemValue = localStorage.getItem(chave);

		if (itemValue && /^\{(.*?)\}$/.test(itemValue)) {

			//Decodifica de volta para JSON
			var current = JSON.parse(itemValue);

			return current.value;
		}
	}

	// ===== localStorage > =====

	if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {

	}

	if (typeof gestor.toasts !== typeof undefined && gestor.toasts !== false) {
		var toasts = gestor.toasts;
		var toasts_options = gestor.toasts_options;
		var opcoes_padroes = toasts_options.opcoes_padroes;
		var transition = 0;

		for (toast in toasts) {
			// ===== Verifica se há regra específica, caso haja disparar regra.

			var regra = false;

			if (typeof toasts[toast].regra !== typeof undefined && toasts[toast].regra !== false) {
				regra = toasts[toast].regra;
			}

			var toastObj = {};
			var toastObjAux = {};

			// ===== Alterar opções padrões.

			if (typeof opcoes_padroes !== typeof undefined && opcoes_padroes !== false) {
				for (opcaoPadrao in opcoes_padroes) {
					toastObj[opcaoPadrao] = opcoes_padroes[opcaoPadrao];
				}
			}

			// ===== Popular objeto do toast com todas as opções definidas no servidor.

			if (typeof toasts[toast].opcoes !== typeof undefined && toasts[toast].opcoes !== false) {
				for (opcao in toasts[toast].opcoes) {
					toastObj[opcao] = toasts[toast].opcoes[opcao];
				}
			}

			// ===== Popular objeto do toastObjAux com todos os botões definidos no servidor.

			if (typeof toasts[toast].botoes !== typeof undefined && toasts[toast].botoes !== false) {
				for (botao in toasts[toast].botoes) {
					toastObjAux[botao] = toasts[toast].botoes[botao];
				}
			}

			// ===== Mostrar este toast.

			var showToast = true;

			switch (regra) {
				case 'update':
					var updateNotShowToast = getLocalStorage('updateNotShowToast');

					if (typeof updateNotShowToast !== typeof undefined && updateNotShowToast !== false) {
						showToast = false;
					}
					break;
			}

			if (showToast) {
				toast_show(toastObj, toastObjAux, regra);
			}

			// ===== Próximo toast que seja disparado com um período entre eles definido por "troca_time" em milisegundos.

			transition = transition + parseInt(toasts_options.troca_time);
		}

		// ===== Toast Click Functions

		var botaoObjClick = {};

		function toastClickUpdatePositivo() {
			var timeLimit = 2000;

			if (typeof botaoObjClick['update-positivo']['displayTime'] !== typeof undefined && botaoObjClick['update-positivo']['displayTime'] !== false) {
				timeLimit = botaoObjClick['update-positivo']['displayTime'];
			}

			$('body').toast(botaoObjClick['update-positivo']);
			setTimeout(function () {
				window.open(gestor.raiz + 'admin-atualizacoes/', '_self');
			}, timeLimit);
		}

		function toastClickUpdateNegativo() {
			setLocalStorage('updateNotShowToast', 'yes', parseInt(toasts_options.updateNotShowToastTime));
			$('body').toast(botaoObjClick['update-negativo']);
		}

		// ===== Toast Show

		function toast_show(obj = {}, objExtra = {}, rule = false) {
			setTimeout(function () {
				if (Object.keys(objExtra).length !== 0) {
					var toastActionObj = [];

					for (id in objExtra) {
						var botaoObj = {};

						if (typeof objExtra[id] !== typeof undefined && objExtra[id] !== false) {
							for (botao in objExtra[id]) {
								if (botao == 'click') {
									botaoObjClick[id] = objExtra[id]['click'];

									switch (id) {
										case 'update-positivo':
											botaoObj[botao] = toastClickUpdatePositivo;
											break;
										case 'update-negativo':
											botaoObj[botao] = toastClickUpdateNegativo;
											break;
									}

									if (typeof objExtra[id]['click']['displayTime'] !== typeof undefined && objExtra[id]['click']['displayTime'] !== false) {
										objExtra[id]['click']['displayTime'] = parseInt(objExtra[id]['click']['displayTime']);
									}

									botaoObjClick[id] = objExtra[id]['click'];
								} else {
									botaoObj[botao] = objExtra[id][botao];
								}
							}
						}

						toastActionObj.push(botaoObj);
					}

					toastObj['actions'] = toastActionObj;
				}

				$('body')
					.toast(toastObj);
			}, transition);
		}


	}

	// ===== Update Notification Box < =====

	/**
	 * Inicializa a caixa de notificação de atualização
	 */
	function initUpdateNotification() {
		var notificationBox = document.getElementById('dashboard-update-notification');

		if (!notificationBox) {
			return;
		}

		var storageKey = 'dashboard_update_dismissed';
		var dismissMinutes = 10080; // 7 dias

		// Verifica se há atualização disponível via variável do servidor
		if (typeof gestor !== 'undefined' &&
			typeof gestor.update_available !== 'undefined' &&
			gestor.update_available === true) {

			// Verifica se o usuário não dispensou a notificação recentemente
			var dismissed = getLocalStorage(storageKey);

			if (!dismissed) {
				notificationBox.style.display = '';
			}
		}

		// Handler para o botão de fechar (X)
		var closeBtn = notificationBox.querySelector('.close.icon');
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				notificationBox.style.display = 'none';
				setLocalStorage(storageKey, true, dismissMinutes);
			});
		}

		// Handler para o botão "Lembrar Depois"
		var dismissBtn = notificationBox.querySelector('.dashboard-update-dismiss');
		if (dismissBtn) {
			dismissBtn.addEventListener('click', function () {
				notificationBox.style.display = 'none';
				setLocalStorage(storageKey, true, dismissMinutes);
			});
		}
	}

	// Inicializar notificação de atualização
	initUpdateNotification();

	// ===== Update Notification Box > =====

	// ===== Dashboard Cards Sortable < =====

	/**
	 * Inicializa o sistema de drag-and-drop para os cards do dashboard
	 * usando SortableJS e persistindo a ordem no localStorage
	 */
	function initDashboardCards() {
		var cardsContainer = document.getElementById('dashboard-sortable-cards');

		if (!cardsContainer) {
			return;
		}

		// Verifica se SortableJS está disponível
		if (typeof Sortable === 'undefined') {
			console.warn('SortableJS não está carregado. Drag-and-drop desabilitado.');
			return;
		}

		var storageKey = 'dashboard_cards_user_order';
		var storageExpireMinutes = 43200; // 30 dias

		/**
		 * Obtém a ordem padrão dos cards definida pelo PHP
		 * @returns {Array} Array com IDs dos módulos na ordem padrão
		 */
		function getDefaultOrder() {
			if (typeof gestor !== 'undefined' &&
				typeof gestor.dashboard_cards_order !== 'undefined' &&
				Array.isArray(gestor.dashboard_cards_order)) {
				return gestor.dashboard_cards_order;
			}
			return [];
		}

		/**
		 * Obtém a ordem atual dos cards no DOM
		 * @returns {Array} Array com IDs dos módulos na ordem atual
		 */
		function getCurrentOrder() {
			var order = [];
			var cards = cardsContainer.querySelectorAll('.dashboard-module-card');

			cards.forEach(function (card) {
				var moduleId = card.getAttribute('data-module-id');
				if (moduleId) {
					order.push(moduleId);
				}
			});

			return order;
		}

		/**
		 * Salva a ordem dos cards no localStorage e sincroniza com o backend (req-226)
		 * @param {Array} order Array com IDs dos módulos
		 */
		function saveOrder(order) {
			setLocalStorage(storageKey, order, storageExpireMinutes);
			dashboardSalvarPreferenciaBackend('dashboard_cards_order', order);
		}

		/**
		 * Carrega a ordem salva do localStorage
		 * @returns {Array|null} Array com IDs dos módulos ou null se não existir
		 */
		function loadSavedOrder() {
			var savedOrder = getLocalStorage(storageKey);

			if (savedOrder && Array.isArray(savedOrder)) {
				return savedOrder;
			}

			return null;
		}

		/**
		 * Reordena os cards no DOM baseado na ordem salva
		 * @param {Array} order Array com IDs dos módulos na ordem desejada
		 */
		function reorderCards(order) {
			if (!order || !Array.isArray(order) || order.length === 0) {
				return;
			}

			var fragment = document.createDocumentFragment();
			var cardsMap = {};
			var unmappedCards = [];

			// Mapeia todos os cards por ID
			var allCards = cardsContainer.querySelectorAll('.dashboard-module-card');
			allCards.forEach(function (card) {
				var moduleId = card.getAttribute('data-module-id');
				if (moduleId) {
					cardsMap[moduleId] = card;
				}
			});

			// Adiciona cards na ordem salva
			order.forEach(function (moduleId) {
				if (cardsMap[moduleId]) {
					fragment.appendChild(cardsMap[moduleId]);
					delete cardsMap[moduleId];
				}
			});

			// Adiciona cards que não estavam na ordem salva (novos módulos)
			for (var moduleId in cardsMap) {
				if (cardsMap.hasOwnProperty(moduleId)) {
					fragment.appendChild(cardsMap[moduleId]);
				}
			}

			// Limpa o container e adiciona os cards reordenados
			cardsContainer.innerHTML = '';
			cardsContainer.appendChild(fragment);
		}

		/**
		 * Inicializa o SortableJS no container de cards
		 */
		function initSortable() {
			new Sortable(cardsContainer, {
				animation: 200,
				easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
				handle: '.dashboard-card-drag-handle',
				ghostClass: 'sortable-ghost',
				chosenClass: 'sortable-chosen',
				dragClass: 'sortable-drag',
				forceFallback: false,
				fallbackTolerance: 3,
				delay: 100,
				delayOnTouchOnly: true,
				touchStartThreshold: 5,

				// Scroll options
				scroll: true,
				scrollSensitivity: 80,
				scrollSpeed: 12,
				bubbleScroll: true,
				forceAutoScrollFallback: true,

				// Callback quando o drag termina
				onEnd: function (evt) {
					var newOrder = getCurrentOrder();
					saveOrder(newOrder);

					// Adiciona feedback visual
					var item = evt.item;
					item.classList.add('card-dropped');

					setTimeout(function () {
						item.classList.remove('card-dropped');
					}, 300);
				},

				// Callback quando começa o drag
				onStart: function (evt) {
					document.body.classList.add('is-dragging');
				},

				// Callback quando termina qualquer movimento
				onUnchoose: function (evt) {
					document.body.classList.remove('is-dragging');
				}
			});
		}

		/**
		 * Adiciona botão para resetar a ordem dos cards
		 */
		function addResetButton() {
			var resetBtn = document.getElementById('dashboard-reset-order');

			if (resetBtn) {
				resetBtn.addEventListener('click', function (e) {
					e.preventDefault();

					// Remove a ordem salva
					localStorage.removeItem(storageKey);

					// Reordena para a ordem padrão
					var defaultOrder = getDefaultOrder();
					reorderCards(defaultOrder);

					// Feedback visual
					$(this).transition('pulse');

					// Toast de confirmação (se disponível)
					if (typeof $.fn.toast !== 'undefined') {
						$('body').toast({
							class: 'success',
							message: gestor.lang && gestor.lang.dashboard_order_reset
								? gestor.lang.dashboard_order_reset
								: 'Ordem dos cards restaurada!',
							showProgress: 'bottom',
							displayTime: 2000
						});
					}
				});
			}
		}

		// Inicialização
		(function init() {
			// Carrega a ordem salva ou usa a padrão
			var savedOrder = loadSavedOrder();

			if (savedOrder) {
				reorderCards(savedOrder);
			}

			// Inicializa o SortableJS
			initSortable();

			// Adiciona handler para o botão de reset
			addResetButton();

			// Adiciona classe indicando que o sistema está pronto
			cardsContainer.classList.add('sortable-ready');
		})();
	}

	// Inicializa os cards do dashboard
	initDashboardCards();

	// ===== Dashboard Cards Sortable > =====

	// ===== Dashboard Search < =====

	/**
	 * Inicializa o sistema de busca para filtrar os cards do dashboard
	 */
	function initDashboardSearch() {
		var searchInput = document.getElementById('dashboard-search-input');
		var resetBtn = document.getElementById('dashboard-search-reset');
		var cardsContainer = document.getElementById('dashboard-sortable-cards');

		if (!searchInput || !cardsContainer) {
			return;
		}

		var STORAGE_KEY = 'dashboard-search-filter';
		var noResultsId = 'dashboard-no-results-message';
		var noResultsMessage = gestor.lang && gestor.lang.search_no_results
			? gestor.lang.search_no_results
			: 'Nenhum módulo encontrado';

		/**
		 * Salva o filtro no localStorage
		 * @param {string} value - Valor do filtro
		 */
		function saveFilter(value) {
			try {
				if (value && value.trim() !== '') {
					localStorage.setItem(STORAGE_KEY, value.trim());
				} else {
					localStorage.removeItem(STORAGE_KEY);
				}
			} catch (e) {
				// localStorage indisponível
			}
		}

		/**
		 * Carrega o filtro do localStorage
		 * @returns {string} Valor salvo ou string vazia
		 */
		function loadFilter() {
			try {
				return localStorage.getItem(STORAGE_KEY) || '';
			} catch (e) {
				return '';
			}
		}

		/**
		 * Filtra os cards baseado na query de busca
		 * @param {string} query - Texto de busca
		 * @param {boolean} saveToStorage - Se deve salvar no localStorage (default: true)
		 */
		function filterCards(query, saveToStorage) {
			if (typeof saveToStorage === 'undefined') {
				saveToStorage = true;
			}

			var cards = cardsContainer.querySelectorAll('.dashboard-module-card');
			var normalizedQuery = query.toLowerCase().trim();
			var visibleCount = 0;

			// Salvar no localStorage
			if (saveToStorage) {
				saveFilter(query);
			}

			cards.forEach(function (card) {
				var title = card.querySelector('.dashboard-card-title');
				var description = card.querySelector('.dashboard-card-description');
				var category = card.querySelector('.dashboard-card-meta .category');
				var moduleId = card.getAttribute('data-module-id') || '';

				var titleText = title ? title.textContent.toLowerCase() : '';
				var descText = description ? description.textContent.toLowerCase() : '';
				var categoryText = category ? category.textContent.toLowerCase() : '';

				var matches = normalizedQuery === '' ||
					titleText.includes(normalizedQuery) ||
					descText.includes(normalizedQuery) ||
					categoryText.includes(normalizedQuery) ||
					moduleId.toLowerCase().includes(normalizedQuery);

				if (matches) {
					card.classList.remove('search-hidden');
					visibleCount++;
				} else {
					card.classList.add('search-hidden');
				}
			});

			// Mostrar/ocultar mensagem de nenhum resultado
			var existingNoResults = document.getElementById(noResultsId);

			if (visibleCount === 0 && normalizedQuery !== '') {
				if (!existingNoResults) {
					var noResults = document.createElement('div');
					noResults.id = noResultsId;
					noResults.className = 'dashboard-no-results';
					noResults.innerHTML = '<i class="search icon"></i>' + noResultsMessage;
					cardsContainer.appendChild(noResults);
				}
			} else if (existingNoResults) {
				existingNoResults.remove();
			}
		}

		/**
		 * Reseta a busca
		 */
		function resetSearch() {
			searchInput.value = '';
			filterCards('');
			searchInput.focus();
		}

		// Carregar filtro salvo ao inicializar
		var savedFilter = loadFilter();
		if (savedFilter) {
			searchInput.value = savedFilter;
			filterCards(savedFilter, false);
		}

		// Event listener para input de busca (debounced)
		var debounceTimer;
		searchInput.addEventListener('input', function () {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(function () {
				filterCards(searchInput.value);
			}, 150);
		});

		// Event listener para tecla Enter e Escape
		searchInput.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				resetSearch();
			}
		});

		// Event listener para botão de reset
		if (resetBtn) {
			resetBtn.addEventListener('click', function (e) {
				e.preventDefault();
				resetSearch();
			});
		}
	}

	// Inicializa a busca do dashboard
	initDashboardSearch();

	// ===== Dashboard Search > =====

	// ===== Sincronização de Preferências no Backend (req-226) < =====

	/**
	 * Envia preferência do usuário para persistência no backend via AJAX
	 * @param {string} chave - Nome da preferência
	 * @param {*} valor - Valor da preferência
	 * @returns {Promise}
	 */
	function dashboardSalvarPreferenciaBackend(chave, valor) {
		var params = new URLSearchParams({
			opcao: 'inicio',
			ajax: 'sim',
			ajaxOpcao: 'salvar-preferencias',
			chave: chave,
			valor: typeof valor === 'object' && valor !== null ? JSON.stringify(valor) : valor
		});

		var baseUrl = (typeof gestor !== 'undefined' && gestor.raiz ? gestor.raiz : '/') + 'dashboard/';

		return fetch(baseUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params
		})
		.then(function (res) { return res.json(); })
		.catch(function (err) {
			console.warn('Dashboard: Erro ao salvar preferência "' + chave + '" no backend:', err);
		});
	}

	// ===== Sincronização de Preferências no Backend > =====

	// ===== Seletor de Densidade de Módulos P / M / G (req-226 CA-2) < =====

	function initDashboardDensity() {
		var selectorContainer = document.getElementById('dashboard-density-selector');
		var cardsContainer = document.getElementById('dashboard-sortable-cards');

		if (!selectorContainer || !cardsContainer) {
			return;
		}

		var storageKey = 'dashboard_density';
		var defaultDensity = 'm';

		// Prioridade: preferência do backend > localStorage > padrão 'm'
		var savedDensity = defaultDensity;
		if (typeof gestor !== 'undefined' && gestor.dashboard_user_prefs && gestor.dashboard_user_prefs.densidade) {
			savedDensity = gestor.dashboard_user_prefs.densidade;
		} else {
			var localSaved = getLocalStorage(storageKey);
			if (localSaved && ['p', 'm', 'g'].indexOf(localSaved) !== -1) {
				savedDensity = localSaved;
			}
		}

		function applyDensity(density, persist) {
			if (['p', 'm', 'g'].indexOf(density) === -1) {
				density = 'm';
			}

			// Atualiza classes do container de cartões
			cardsContainer.classList.remove('density-p', 'density-m', 'density-g');
			cardsContainer.classList.add('density-' + density);

			// Atualiza botões no seletor
			var buttons = selectorContainer.querySelectorAll('.dashboard-density-btn');
			buttons.forEach(function (btn) {
				var btnDensity = btn.getAttribute('data-density');
				if (btnDensity === density) {
					btn.classList.add('active', 'bg-white', 'text-sky-700', 'shadow-sm');
					btn.classList.remove('text-slate-600');
				} else {
					btn.classList.remove('active', 'bg-white', 'text-sky-700', 'shadow-sm');
					btn.classList.add('text-slate-600');
				}
			});

			if (persist) {
				setLocalStorage(storageKey, density, 43200); // 30 dias
				dashboardSalvarPreferenciaBackend('dashboard_densidade', density);
			}

			// Recria ícones Lucide caso necessário
			if (typeof lucide !== 'undefined' && lucide.createIcons) {
				lucide.createIcons();
			}
		}

		// Event listener para cliques nos botões de densidade
		selectorContainer.addEventListener('click', function (e) {
			var btn = e.target.closest('.dashboard-density-btn');
			if (!btn) return;

			var density = btn.getAttribute('data-density');
			if (density) {
				applyDensity(density, true);
			}
		});

		// Aplica a densidade inicial
		applyDensity(savedDensity, false);
	}

	initDashboardDensity();

	// ===== Seletor de Densidade de Módulos > =====

	// ===== Sistema de Abas do Dashboard (req-226 CA-3) < =====

	function initDashboardTabs() {
		var tabButtons = document.querySelectorAll('.dashboard-tab-trigger');
		var tabPanels = document.querySelectorAll('.dashboard-tab-panel');

		if (!tabButtons.length || !tabPanels.length) {
			return;
		}

		var storageKey = 'dashboard_active_tab';
		var defaultTab = 'dashboard-tab-modulos';

		// Lê preferência do backend ou localStorage
		var activeTabId = defaultTab;
		if (typeof gestor !== 'undefined' && gestor.dashboard_user_prefs && gestor.dashboard_user_prefs.aba_ativa) {
			activeTabId = gestor.dashboard_user_prefs.aba_ativa;
		} else {
			var localTab = getLocalStorage(storageKey);
			if (localTab && document.getElementById(localTab)) {
				activeTabId = localTab;
			}
		}

		function switchTab(targetId, persist) {
			var targetPanel = document.getElementById(targetId);
			if (!targetPanel) return;

			tabButtons.forEach(function (btn) {
				var target = btn.getAttribute('data-tab-target');
				if (target === targetId) {
					btn.classList.add('active', 'border-sky-600', 'text-sky-600');
					btn.classList.remove('border-transparent', 'text-slate-500');
					btn.setAttribute('aria-selected', 'true');
				} else {
					btn.classList.remove('active', 'border-sky-600', 'text-sky-600');
					btn.classList.add('border-transparent', 'text-slate-500');
					btn.setAttribute('aria-selected', 'false');
				}
			});

			tabPanels.forEach(function (panel) {
				if (panel.id === targetId) {
					panel.classList.remove('hidden');
				} else {
					panel.classList.add('hidden');
				}
			});

			if (persist) {
				setLocalStorage(storageKey, targetId, 43200);
				dashboardSalvarPreferenciaBackend('dashboard_aba_ativa', targetId);
			}

			if (typeof lucide !== 'undefined' && lucide.createIcons) {
				lucide.createIcons();
			}
		}

		tabButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var targetId = this.getAttribute('data-tab-target');
				if (targetId) {
					switchTab(targetId, true);
				}
			});
		});

		// Aplica a aba inicial
		switchTab(activeTabId, false);
	}

	initDashboardTabs();

	// ===== Sistema de Abas do Dashboard > =====

	// ===== Grid Flexível de Widgets (req-226 / req-233) < =====

	function initDashboardWidgets() {
		var grid = document.getElementById('dashboard-widgets-grid');
		if (!grid) return;
		var modal = document.getElementById('dashboard-widgets-modal');
		var list = document.getElementById('dashboard-widgets-modal-list');
		var empty = document.getElementById('dashboard-widgets-empty');
		var editButton = document.getElementById('dashboard-edit-mode');
		var labels = {};
		['type', 'record', 'loading', 'error', 'empty', 'remove', 'drag', 'more', 'resize', 'switch', 'select'].forEach(function (key) {
			labels[key] = grid.getAttribute('data-label-' + key) || '';
		});
		var stored = typeof gestor !== 'undefined' && gestor.dashboard_user_prefs ? gestor.dashboard_user_prefs.widgets_layout : null;
		var widgets = Array.isArray(stored) ? stored : (getLocalStorage('dashboard_widgets_layout') || []);
		if (!Array.isArray(widgets)) widgets = [];
		widgets = widgets.map(function (w, index) {
			var width = Number(w.width);
			if (![4, 6, 8, 12].includes(width)) width = /full|12/.test(w.width) ? 12 : (/8/.test(w.width) ? 8 : (/2|6/.test(w.width) ? 6 : 4));
			var pixels = Number(w.height_px) || (Number(w.height) === 2 ? 460 : 220);
			return Object.assign({}, w, {width: width, height_px: Math.max(180, Math.min(780, pixels)), height: Number(w.height) === 2 ? 2 : 1, instance_id: w.instance_id || 'saved-' + index, registro_id: w.registro_id || '', params: w.params || {}});
		});
		var editing = false;
		try { editing = sessionStorage.getItem('dashboard_widgets_editing') === 'true'; } catch (_) {}
		var target = null, selectedType = null, generation = 0, resize = null, returnFocus = null, pickerReset = false;
		var search = document.getElementById('dashboard-widget-search');
		var resetSelection = document.getElementById('dashboard-widget-reset-selection');
		var selection = document.getElementById('dashboard-widget-selection');
		function filterChoices() {
			var query = search ? search.value.toLocaleLowerCase().trim() : '';
			list.querySelectorAll('[data-widget-choice]').forEach(function (button) { button.hidden = button.textContent.toLocaleLowerCase().indexOf(query) === -1; });
		}
		if (search) search.addEventListener('input', filterChoices);
		if (resetSelection) resetSelection.addEventListener('click', function () { pickerReset = true; if (search) search.value = ''; if (selection) selection.textContent = ''; showTypes(); });
		function escape(value) { return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) { return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]; }); }
		function request(action, data) {
			var params = new URLSearchParams({opcao: 'inicio', ajax: 'sim', ajaxOpcao: action});
			Object.keys(data || {}).forEach(function (key) { params.set(key, typeof data[key] === 'object' ? JSON.stringify(data[key]) : data[key]); });
			return fetch((typeof gestor !== 'undefined' && gestor.raiz ? gestor.raiz : '/') + 'dashboard/', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:params})
				.then(function (response) { if (!response.ok) throw Error(labels.error); return response.json(); })
				.then(function (json) { if (!json || json.status !== 'Ok') throw Error(labels.error); return json.data; });
		}
		function save() { setLocalStorage('dashboard_widgets_layout', widgets, 43200); dashboardSalvarPreferenciaBackend('dashboard_widgets_layout', widgets); }
		function geometry(card, widget) {
			card.setAttribute('data-widget-cols', widget.width);
			card.setAttribute('data-widget-height', widget.height);
			card.style.height = (widget.height_px || (widget.height === 2 ? 460 : 220)) + 'px';
			card.style.minHeight = '180px';
		}
		function setEditing(value) {
			if (!value && resize) {
				geometry(resize.card, resize.widget);
				resize.card.classList.remove('is-resizing');
				if (resize.handle.hasPointerCapture(resize.pointer)) resize.handle.releasePointerCapture(resize.pointer);
				resize = null;
			}
			editing = value;
			grid.classList.toggle('is-editing', editing);
			if (editButton) {
				editButton.setAttribute('aria-checked', String(editing));
				var label = editButton.querySelector('[data-edit-label]');
				if (label) label.textContent = editButton.getAttribute(editing ? 'data-label-on' : 'data-label-off');
			}
			if (!editing) grid.classList.remove('is-interacting');
			if (grid._dashboardSortable) grid._dashboardSortable.option('disabled', !editing);
			try { sessionStorage.setItem('dashboard_widgets_editing', String(editing)); } catch (_) {}
		}
		if (editButton) editButton.addEventListener('click', function () { setEditing(!editing); });
		function render() {
			if (grid._dashboardSortable) { grid._dashboardSortable.destroy(); grid._dashboardSortable = null; }
			grid.innerHTML = '';
			if (empty) empty.classList.toggle('hidden', widgets.length > 0);
			widgets.forEach(function (widget) {
				var card = document.createElement('div');
				card.className = 'dashboard-widget-card rounded-xl border border-slate-200 bg-white shadow-sm';
				card.dataset.widgetInstance = widget.instance_id;
				card.dataset.widgetId = widget.id;
				geometry(card, widget);
				card.innerHTML = '<div class="dashboard-widget-card-header flex min-w-0 items-center gap-2 border-b border-slate-100 px-4 py-3">' +
					'<button type="button" class="dashboard-widget-drag-handle c2fc-botao c2fc-botao-icone" aria-label="'+escape(labels.drag)+'"><i data-lucide="grip-vertical" class="size-4"></i></button>' +
					'<span class="dashboard-widget-title min-w-0 flex-1 truncate font-semibold">'+escape(widget.name || widget.id)+'</span>' +
					'<button type="button" class="dashboard-widget-switch-btn c2fc-botao c2fc-botao-icone" aria-label="'+escape(labels.switch)+'"><i data-lucide="settings-2" class="size-4"></i></button>' +
					'<button type="button" class="dashboard-widget-remove-btn c2fc-botao c2fc-botao-icone" aria-label="'+escape(labels.remove)+'"><i data-lucide="x" class="size-4"></i></button></div>' +
					'<div class="dashboard-widget-card-body min-w-0 flex-1 overflow-auto p-4">'+escape(labels.loading)+'</div>';
				['se'].forEach(function (corner) {
					var handle = document.createElement('button'); handle.type = 'button'; handle.className = 'dashboard-widget-resize-handle'; handle.dataset.corner = corner; handle.setAttribute('aria-label', labels.resize); handle.innerHTML = '<i data-lucide="move-diagonal-2" class="size-3.5"></i>'; card.appendChild(handle);
				});
				grid.appendChild(card);
				var body = card.querySelector('.dashboard-widget-card-body');
				request('widget-render', {widget_id:widget.id, registro_id:widget.registro_id, instance_id:widget.instance_id, params:widget.params}).then(function (data) {
					if (!card.isConnected) return;
					var frame = document.createElement('iframe');
					frame.className = 'dashboard-widget-frame';
					frame.title = widget.name || widget.id;
					frame.setAttribute('sandbox', 'allow-scripts');
					var jquery = document.querySelector('script[src*="jquery"]');
					frame.srcdoc = '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><base href="'+escape(location.origin + ((typeof gestor !== 'undefined' && gestor.raiz) || '/'))+'">'+(data.css || '')+'<style>html,body{margin:0;max-width:100%;overflow-x:hidden}body{font-family:system-ui,sans-serif}</style>'+(jquery ? jquery.outerHTML : '')+'</head><body>'+(data.html || escape(labels.empty))+(data.scripts || '')+'</body></html>';
					body.replaceChildren(frame);
					icons();
				}).catch(function () { if (card.isConnected) body.textContent = labels.error; });
			});
			if (typeof Sortable !== 'undefined') grid._dashboardSortable = new Sortable(grid, {animation:200, disabled:!editing, handle:'.dashboard-widget-drag-handle', onStart:function () { grid.classList.add('is-interacting'); }, onEnd:function () {
				grid.classList.remove('is-interacting');
				widgets = Array.from(grid.children).map(function (card) { return widgets.find(function (w) { return w.instance_id === card.dataset.widgetInstance; }); }); save();
			}});
			setEditing(editing); icons();
		}
		function icons() { if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons(); }
		function close() { generation++; if (modal) modal.classList.add('hidden'); target = null; selectedType = null; if (returnFocus && returnFocus.isConnected) returnFocus.focus(); }
		function showRecords(type, page, append) {
			selectedType = type;
			var current = ++generation;
			if (!append) list.innerHTML = '<p class="text-sm font-semibold">'+escape(labels.record)+'</p><p>'+escape(labels.loading)+'</p>';
			request('widgets-registros', {widget_id:type.id, pagina:page}).then(function (data) {
				if (current !== generation) return;
				if (!append) list.innerHTML = '<button type="button" class="dashboard-widget-types c2fc-botao">'+escape(labels.type)+'</button><p class="text-sm font-semibold">'+escape(labels.record)+' — '+escape(type.name)+'</p>';
				var assigned = !pickerReset && widgets.find(function (w) { return w.instance_id === target; });
				var records = (data.items || []).slice();
				if (!append && assigned && assigned.id === type.id && !records.some(function(r){return r.id===assigned.registro_id;})) records.unshift({id:assigned.registro_id,nome:(assigned.name || assigned.registro_id).split(' / ').pop()});
				records.forEach(function (record) {
					var button = document.createElement('button'); button.type='button'; button.className='c2fc-botao w-full'; button.textContent=record.nome || record.id;
					button.dataset.widgetChoice = record.id;
					button.setAttribute('aria-pressed', String(!!assigned && assigned.id === type.id && assigned.registro_id === record.id));
					button.addEventListener('click', function () {
						var config = {id:type.id, name:(type.name || type.id) + ' / ' + (record.nome || record.id), registro_id:record.id, params:{grupo_slug:record.id}};
						var old = widgets.find(function (w) { return w.instance_id === target; });
						if (old) Object.assign(old, config); else widgets.push(Object.assign(config,{instance_id:'widget-'+Date.now()+'-'+Math.random().toString(36).slice(2),width:4,height:1}));
						save(); render(); close();
					}); list.appendChild(button);
				});
				if (!(data.items || []).length && !append) { var p=document.createElement('p');p.textContent=labels.empty;list.appendChild(p); }
				if (data.tem_mais) { var more=document.createElement('button');more.type='button';more.className='c2fc-botao';more.textContent=labels.more;more.addEventListener('click',function(){more.remove();showRecords(type,page+1,true);});list.appendChild(more); }
				filterChoices();
			}).catch(function () { if (current === generation) list.textContent=labels.error; });
		}
		function showTypes() {
			selectedType = null;
			var current = ++generation;
			list.textContent = labels.loading;
			request('widgets-catalogo').then(function (types) {
				if (current !== generation) return;
				list.innerHTML = '<p class="text-sm font-semibold">'+escape(labels.type)+'</p>';
				types.forEach(function (type) { var button=document.createElement('button');button.type='button';button.className='c2fc-botao w-full';button.textContent=type.name || type.id;button.dataset.widgetChoice=type.id;var assigned=!pickerReset && widgets.find(function(w){return w.instance_id===target;});button.setAttribute('aria-pressed',String(!!assigned && assigned.id===type.id));button.addEventListener('click',function(){if(search)search.value='';showRecords(type,1,false);});list.appendChild(button); });
				if (!types.length) list.textContent=labels.empty;
				filterChoices();
			}).catch(function () { if (current === generation) list.textContent=labels.error; });
		}
		function open(instance) { if (!modal || !list) return; returnFocus=document.activeElement;target=instance || null;pickerReset=false;modal.classList.remove('hidden');if(search)search.value='';var assigned=widgets.find(function(w){return w.instance_id===target;});if(selection)selection.textContent=assigned ? assigned.name || assigned.id : '';if(assigned)showRecords({id:assigned.id,name:(assigned.name || assigned.id).split(' / ')[0]},1,false);else showTypes();(search || modal.querySelector('.dashboard-widgets-modal-close')).focus(); }
		if (list) list.addEventListener('click',function(e){if(e.target.closest('.dashboard-widget-types'))showTypes();});
		document.querySelectorAll('#dashboard-btn-add-widget, .dashboard-btn-open-catalog').forEach(function(b){b.addEventListener('click',function(){open(null);});});
		document.querySelectorAll('.dashboard-widgets-modal-close').forEach(function(b){b.addEventListener('click',close);});
		if (modal) modal.addEventListener('click',function(e){if(e.target===modal)close();});
		document.addEventListener('keydown',function(e){
			if(e.key==='Escape'){close();var options=document.getElementById('dashboard-options');if(options)options.open=false;}
			if(e.key==='Tab' && modal && !modal.classList.contains('hidden')){
				var buttons=Array.from(modal.querySelectorAll('button:not([disabled]), input:not([disabled])')).filter(function(b){return !b.hidden;});var first=buttons[0],last=buttons[buttons.length-1];
				if(e.shiftKey && document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey && document.activeElement===last){e.preventDefault();first.focus();}
			}
		});
		document.addEventListener('click',function(e){var options=document.getElementById('dashboard-options');if(options && !options.contains(e.target))options.open=false;});
		grid.addEventListener('click',function(e){
			if(!editing)return;var card=e.target.closest('.dashboard-widget-card');if(!card)return;
			if(e.target.closest('.dashboard-widget-switch-btn'))open(card.dataset.widgetInstance);
			if(e.target.closest('.dashboard-widget-remove-btn')){widgets=widgets.filter(function(w){return w.instance_id!==card.dataset.widgetInstance;});save();render();}
		});
		var resetButton=document.getElementById('dashboard-btn-reset-widgets');
		if(resetButton)resetButton.addEventListener('click',function(){widgets=[];save();render();});
		grid.addEventListener('pointerdown',function(e){
			var handle=e.target.closest('.dashboard-widget-resize-handle');if(!editing || !handle)return;
			var card=handle.closest('.dashboard-widget-card'),widget=widgets.find(function(w){return w.instance_id===card.dataset.widgetInstance;});
			var columns=grid.getBoundingClientRect().width,rect=card.getBoundingClientRect();
			resize={handle:handle,card:card,widget:widget,pointer:e.pointerId,x:e.clientX,y:e.clientY,width:rect.width,height:rect.height,gridWidth:columns,corner:handle.dataset.corner};
			card.classList.add('is-resizing');grid.classList.add('is-interacting');handle.setPointerCapture(e.pointerId);e.preventDefault();
		});
		grid.addEventListener('pointermove',function(e){
			if(!resize || resize.pointer!==e.pointerId)return;
			var dx=e.clientX-resize.x,dy=e.clientY-resize.y;
			var fraction=((resize.width+dx)/resize.gridWidth)*12;
			resize.cols=[4,6,8,12].reduce(function(a,b){return Math.abs(b-fraction)<Math.abs(a-fraction)?b:a;});resize.rows=Math.max(180,Math.min(780,180+Math.round((resize.height+dy-180)/60)*60));
			geometry(resize.card,{width:resize.cols,height_px:resize.rows});
		});
		function finish(e){if(!resize || resize.pointer!==e.pointerId)return;resize.card.classList.remove('is-resizing');grid.classList.remove('is-interacting');if(resize.handle.hasPointerCapture(e.pointerId))resize.handle.releasePointerCapture(e.pointerId);if(e.type==='pointercancel'){geometry(resize.card,resize.widget);}else if(resize.cols){resize.widget.width=resize.cols;resize.widget.height_px=resize.rows;save();}resize=null;}
		grid.addEventListener('pointerup',finish);grid.addEventListener('pointercancel',finish);
		render();
	}

	initDashboardWidgets();

	// ===== Grid Flexível de Widgets > =====

	// ===== Dashboard 3D < =====

	/**
	 * Inicializa o Dashboard 3D carregando os módulos dinamicamente na ordem correta
	 * Os scripts só são carregados quando o container do dashboard-3d está presente
	 */
	function initDashboard3D() {
		var dashboard3DWrapper = document.getElementById('dashboard-3d-wrapper');

		if (!dashboard3DWrapper) {
			return;
		}

		// Lista de módulos a carregar em ordem
		var modules = [
			'dashboard/dashboard-3d-config.js',      // 1. Configurações
			'dashboard/dashboard-3d-camera.js',      // 2. Controles de câmera
			'dashboard/dashboard-3d-geometry.js',    // 3. Geometria 3D
			'dashboard/dashboard-3d-cards.js',       // 4. Cards dos módulos
			'dashboard/dashboard-3d-ui.js',          // 5. Interface e interações
			'dashboard/dashboard-3d-main.js'         // 6. Orquestrador principal (deve ser último)
		];

		var loadedCount = 0;

		/**
		 * Carrega um script e chama callback quando pronto
		 */
		function loadScript(src, callback) {
			var script = document.createElement('script');
			script.src = gestor.raiz + src;
			script.async = false; // Garantir ordem de execução

			script.onload = function () {
				console.log('Dashboard 3D: Módulo carregado -', src);
				callback();
			};

			script.onerror = function () {
				console.error('Dashboard 3D: Erro ao carregar módulo -', src);
				showLoadError();
			};

			document.head.appendChild(script);
		}

		/**
		 * Mostra erro de carregamento
		 */
		function showLoadError() {
			var loading = document.getElementById('loading-overlay');
			if (loading) {
				loading.innerHTML = '<div style="color: #ff4444; text-align: center;"><p>Erro ao carregar Dashboard 3D</p><a href="' + gestor.raiz + 'dashboard/" style="color: #4a9eff;">Voltar ao Dashboard 2D</a></div>';
			}
		}

		/**
		 * Carrega o próximo módulo da lista
		 */
		function loadNextModule() {
			if (loadedCount >= modules.length) {
				console.log('Dashboard 3D: Todos os módulos carregados');
				return;
			}

			loadScript(modules[loadedCount], function () {
				loadedCount++;
				loadNextModule();
			});
		}

		// Iniciar carregamento sequencial
		console.log('Dashboard 3D: Iniciando carregamento de', modules.length, 'módulos...');
		loadNextModule();
	}

	// Inicializa o Dashboard 3D se estiver na página correta
	initDashboard3D();

	// ===== Dashboard 3D > =====

});
