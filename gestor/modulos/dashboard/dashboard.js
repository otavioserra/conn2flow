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
		var widgetsGrid = document.getElementById('dashboard-widgets-grid');
		var emptyState = document.getElementById('dashboard-widgets-empty');
		var addBtn = document.getElementById('dashboard-btn-add-widget');
		var resetBtn = document.getElementById('dashboard-btn-reset-widgets');
		var modal = document.getElementById('dashboard-widgets-modal');
		var modalList = document.getElementById('dashboard-widgets-modal-list');

		if (!widgetsGrid) {
			return;
		}

		var storageKey = 'dashboard_widgets_layout';
		var widgetsList = [];
		var catalogCache = null;
		var activeSwitchMenu = null;
		var replacementTargetId = null;

		var labels = {
			switchWidget: widgetsGrid.getAttribute('data-label-switch') || 'Trocar widget',
			active: widgetsGrid.getAttribute('data-label-active') || 'Ativo no painel',
			available: widgetsGrid.getAttribute('data-label-available') || 'Disponível',
			add: widgetsGrid.getAttribute('data-label-add') || 'Adicionar',
			select: widgetsGrid.getAttribute('data-label-select') || 'Selecionar',
			resize: widgetsGrid.getAttribute('data-label-resize') || 'Redimensionar widget'
		};

		function escapeWidgetText(value) {
			return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
			});
		}

		// Carrega widgets salvos do backend ou localStorage
		if (typeof gestor !== 'undefined' && gestor.dashboard_user_prefs && Array.isArray(gestor.dashboard_user_prefs.widgets_layout) && gestor.dashboard_user_prefs.widgets_layout.length > 0) {
			widgetsList = gestor.dashboard_user_prefs.widgets_layout;
		} else {
			var localSaved = getLocalStorage(storageKey);
			if (localSaved && Array.isArray(localSaved)) {
				widgetsList = localSaved;
			}
		}

		// Normaliza dimensões dos widgets (snap em 4, 6, 8, 12 colunas e altura 1x ou 2x)
		widgetsList = widgetsList.map(function (w) {
			var widthCols = 4;
			if (typeof w.width === 'string') {
				if (w.width === 'col-span-full' || w.width === 'col-span-12') widthCols = 12;
				else if (w.width === 'col-span-2' || w.width === 'col-span-6') widthCols = 6;
				else if (w.width === 'col-span-8') widthCols = 8;
				else widthCols = 4;
			} else if (typeof w.width === 'number') {
				if (w.width >= 10) widthCols = 12;
				else if (w.width >= 7) widthCols = 8;
				else if (w.width >= 5) widthCols = 6;
				else widthCols = 4;
			}
			var heightUnits = (Number(w.height) === 2) ? 2 : 1;
			return {
				id: w.id,
				name: w.name,
				width: widthCols,
				height: heightUnits
			};
		});

		function saveWidgetsLayout() {
			setLocalStorage(storageKey, widgetsList, 43200);
			dashboardSalvarPreferenciaBackend('dashboard_widgets_layout', widgetsList);
		}

		function applyWidgetGeometry(card, widget) {
			var cols = [4, 6, 8, 12].indexOf(Number(widget.width)) !== -1 ? Number(widget.width) : 4;
			var h = (Number(widget.height) === 2) ? 2 : 1;

			card.classList.remove('col-span-4', 'col-span-6', 'col-span-8', 'col-span-12', 'h-1x', 'h-2x');
			card.classList.add('col-span-' + cols, 'h-' + h + 'x');
			card.setAttribute('data-widget-cols', cols);
			card.setAttribute('data-widget-height', h);
		}

		function closeSwitchMenu() {
			if (activeSwitchMenu) {
				activeSwitchMenu.remove();
				activeSwitchMenu = null;
			}
		}

		document.addEventListener('click', function (e) {
			if (activeSwitchMenu && !e.target.closest('.dashboard-widget-switch-menu') && !e.target.closest('.dashboard-widget-switch-btn')) {
				closeSwitchMenu();
			}
		});

		function renderWidgetsGrid() {
			closeSwitchMenu();
			if (!widgetsList.length) {
				widgetsGrid.innerHTML = '';
				if (emptyState) emptyState.classList.remove('hidden');
				return;
			}

			if (emptyState) emptyState.classList.add('hidden');
			widgetsGrid.innerHTML = '';

			widgetsList.forEach(function (widget, index) {
				var card = document.createElement('div');
				card.className = 'dashboard-widget-card relative flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm overflow-visible transition-shadow hover:shadow-md';
				card.setAttribute('data-widget-id', widget.id);
				card.setAttribute('data-widget-index', index);
				applyWidgetGeometry(card, widget);

				card.innerHTML =
					'<div class="dashboard-widget-card-header flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-2.5 rounded-t-xl">' +
						'<div class="flex items-center gap-2 min-w-0 flex-1 mr-2">' +
							'<div class="dashboard-widget-drag-handle cursor-grab text-slate-400 hover:text-slate-600 shrink-0" aria-label="Arraste para mover" title="Arraste para mover">' +
								'<i data-lucide="grip-vertical" class="size-4"></i>' +
							'</div>' +
							'<span class="dashboard-widget-title font-semibold text-sm text-slate-800 truncate" title="' + escapeWidgetText(widget.name || widget.id) + '">' + escapeWidgetText(widget.name || widget.id) + '</span>' +
						'</div>' +
						'<div class="flex items-center gap-1.5 shrink-0 relative">' +
							'<button type="button" class="dashboard-widget-switch-btn inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-600 shadow-sm hover:bg-slate-50 hover:text-sky-600 hover:border-sky-300 transition-colors" title="' + escapeWidgetText(labels.switchWidget) + '" aria-label="' + escapeWidgetText(labels.switchWidget) + '">' +
								'<i data-lucide="arrow-left-right" class="size-3.5 text-sky-600"></i>' +
								'<span class="hidden sm:inline">' + escapeWidgetText(labels.switchWidget) + '</span>' +
							'</button>' +
							'<button type="button" class="dashboard-widget-remove-btn rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Remover widget" aria-label="Remover widget">' +
								'<i data-lucide="x" class="size-4"></i>' +
							'</button>' +
						'</div>' +
					'</div>' +
					'<div class="dashboard-widget-card-body flex-1 p-4 flex flex-col justify-center items-center text-slate-500 text-sm overflow-hidden" id="widget-body-' + escapeWidgetText(widget.id) + '-' + index + '">' +
						'<i data-lucide="loader" class="size-5 animate-spin text-sky-600 mb-2"></i>' +
						'<span>Carregando widget...</span>' +
					'</div>' +
					'<button type="button" class="dashboard-widget-resize-handle" aria-label="' + escapeWidgetText(labels.resize) + '" title="' + escapeWidgetText(labels.resize) + '">' +
						'<i data-lucide="move-diagonal-2" class="size-3.5"></i>' +
					'</button>';

				widgetsGrid.appendChild(card);

				// Carrega conteúdo dinâmico do widget via AJAX
				loadWidgetContent(widget.id, 'widget-body-' + widget.id + '-' + index);
			});

			if (typeof lucide !== 'undefined' && lucide.createIcons) {
				lucide.createIcons();
			}

			initWidgetsSortable();
		}

		function loadWidgetContent(widgetId, containerId) {
			var params = new URLSearchParams({
				opcao: 'inicio',
				ajax: 'sim',
				ajaxOpcao: 'widget-render',
				widget_id: widgetId
			});

			var baseUrl = (typeof gestor !== 'undefined' && gestor.raiz ? gestor.raiz : '/') + 'dashboard/';

			fetch(baseUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params
			})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				var container = document.getElementById(containerId);
				if (!container) return;

				if (json && json.status === 'Ok' && json.data && json.data.html) {
					container.innerHTML = json.data.html;
				} else {
					container.innerHTML = 
						'<div class="text-center py-4 text-slate-400">' +
							'<i data-lucide="layout" class="size-6 mx-auto mb-1"></i>' +
							'<p class="text-xs">Widget operacional ativo</p>' +
						'</div>';
				}
				if (typeof lucide !== 'undefined' && lucide.createIcons) {
					lucide.createIcons();
				}
			})
			.catch(function () {
				var container = document.getElementById(containerId);
				if (container) {
					container.innerHTML = '<span class="text-xs text-slate-400">Widget ativo</span>';
				}
			});
		}

		function initWidgetsSortable() {
			if (typeof Sortable === 'undefined') return;

			if (widgetsGrid._dashboardSortable) widgetsGrid._dashboardSortable.destroy();
			widgetsGrid._dashboardSortable = new Sortable(widgetsGrid, {
				animation: 200,
				handle: '.dashboard-widget-drag-handle',
				ghostClass: 'sortable-ghost',
				chosenClass: 'sortable-chosen',
				dragClass: 'sortable-drag',
				onEnd: function () {
					var reordered = [];
					var cards = widgetsGrid.querySelectorAll('.dashboard-widget-card');
					cards.forEach(function (card) {
						var wid = card.getAttribute('data-widget-id');
						var wmatch = widgetsList.find(function (w) { return w.id === wid; });
						if (wmatch) {
							reordered.push(wmatch);
						}
					});
					if (reordered.length === widgetsList.length) {
						widgetsList = reordered;
						saveWidgetsLayout();
					}
				}
			});
		}

		// Carregar catálogo de widgets com cache
		function fetchCatalog(callback) {
			if (catalogCache) {
				callback(catalogCache);
				return;
			}
			var params = new URLSearchParams({
				opcao: 'inicio',
				ajax: 'sim',
				ajaxOpcao: 'widgets-catalogo'
			});
			var baseUrl = (typeof gestor !== 'undefined' && gestor.raiz ? gestor.raiz : '/') + 'dashboard/';
			fetch(baseUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params
			})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (json && json.status === 'Ok' && Array.isArray(json.data)) {
					catalogCache = json.data;
				} else {
					catalogCache = [];
				}
				callback(catalogCache);
			})
			.catch(function () {
				catalogCache = [];
				callback(catalogCache);
			});
		}

		// Trocar widget de um bloco
		function replaceWidget(targetWidgetId, newWidgetId, newWidgetName) {
			var targetIndex = widgetsList.findIndex(function (w) { return w.id === targetWidgetId; });
			if (targetIndex === -1) return;

			widgetsList[targetIndex].id = newWidgetId;
			widgetsList[targetIndex].name = newWidgetName;
			saveWidgetsLayout();

			var card = widgetsGrid.querySelector('.dashboard-widget-card[data-widget-id="' + targetWidgetId + '"]');
			if (card) {
				card.setAttribute('data-widget-id', newWidgetId);
				var titleEl = card.querySelector('.dashboard-widget-title');
				if (titleEl) {
					titleEl.textContent = newWidgetName;
					titleEl.setAttribute('title', newWidgetName);
				}
				var bodyEl = card.querySelector('.dashboard-widget-card-body');
				if (bodyEl) {
					var bodyId = 'widget-body-' + newWidgetId + '-' + targetIndex;
					bodyEl.id = bodyId;
					bodyEl.innerHTML = '<i data-lucide="loader" class="size-5 animate-spin text-sky-600 mb-2"></i><span>Carregando widget...</span>';
					if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
					loadWidgetContent(newWidgetId, bodyId);
				}
			} else {
				renderWidgetsGrid();
			}
		}

		// Abre menu suspenso rápido para trocar o widget do card
		function openSwitchDropdown(card, switchBtn) {
			closeSwitchMenu();
			var currentWidgetId = card.getAttribute('data-widget-id');
			var menu = document.createElement('div');
			menu.className = 'dashboard-widget-switch-menu';
			menu.innerHTML = '<div class="py-2 text-center text-xs text-slate-500"><i data-lucide="loader" class="size-4 animate-spin mx-auto text-sky-600 mb-1"></i> Carregando...</div>';
			switchBtn.parentElement.appendChild(menu);
			activeSwitchMenu = menu;
			if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

			fetchCatalog(function (catalog) {
				if (!activeSwitchMenu || activeSwitchMenu !== menu) return;
				menu.innerHTML = '';
				if (!catalog || !catalog.length) {
					menu.innerHTML = '<p class="py-2 text-center text-xs text-slate-500">Nenhum widget disponível.</p>';
					return;
				}
				var headerEl = document.createElement('div');
				headerEl.className = 'px-2 py-1 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100 mb-1';
				headerEl.textContent = labels.switchWidget;
				menu.appendChild(headerEl);

				catalog.forEach(function (widget) {
					var isCurrent = (widget.id === currentWidgetId);
					var item = document.createElement('button');
					item.type = 'button';
					item.className = 'dashboard-widget-switch-item' + (isCurrent ? ' active' : '');
					item.innerHTML =
						'<i data-lucide="puzzle" class="size-4 shrink-0 ' + (isCurrent ? 'text-sky-600' : 'text-slate-400') + '"></i>' +
						'<span class="truncate flex-1">' + escapeWidgetText(widget.name || widget.id) + '</span>' +
						(isCurrent ? '<i data-lucide="check" class="size-3.5 text-sky-600 shrink-0"></i>' : '');

					item.addEventListener('click', function (ev) {
						ev.stopPropagation();
						closeSwitchMenu();
						if (!isCurrent) {
							replaceWidget(currentWidgetId, widget.id, widget.name || widget.id);
						}
					});
					menu.appendChild(item);
				});

				if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
			});
		}

		// Redimensionamento Suave com Snap (pointerdown, pointermove, pointerup, pointercancel)
		var resizeState = null;

		widgetsGrid.addEventListener('pointerdown', function (e) {
			var handle = e.target.closest('.dashboard-widget-resize-handle');
			if (!handle || (e.pointerType === 'mouse' && e.button !== 0)) return;

			var card = handle.closest('.dashboard-widget-card');
			var widget = card && widgetsList.find(function (item) { return item.id === card.getAttribute('data-widget-id'); });
			if (!card || !widget) return;

			e.preventDefault();
			var rect = card.getBoundingClientRect();
			var gridRect = widgetsGrid.getBoundingClientRect();
			var gridWidth = gridRect.width || 1;

			resizeState = {
				handle: handle,
				pointerId: e.pointerId,
				card: card,
				widget: widget,
				startX: e.clientX,
				startY: e.clientY,
				startCols: Number(card.getAttribute('data-widget-cols')) || 4,
				startHeight: Number(card.getAttribute('data-widget-height')) || 1,
				startWidthPx: rect.width,
				startHeightPx: rect.height,
				gridWidth: gridWidth
			};

			card.classList.add('is-resizing');
			handle.setPointerCapture(e.pointerId);
		});

		widgetsGrid.addEventListener('pointermove', function (e) {
			if (!resizeState || resizeState.pointerId !== e.pointerId) return;

			var deltaX = e.clientX - resizeState.startX;
			var deltaY = e.clientY - resizeState.startY;
			var currentPxWidth = resizeState.startWidthPx + deltaX;
			var currentPxHeight = resizeState.startHeightPx + deltaY;

			// Snap de colunas (4, 6, 8, 12)
			var colFraction = (currentPxWidth / resizeState.gridWidth) * 12;
			var targetCols = 4;
			if (colFraction >= 10) {
				targetCols = 12;
			} else if (colFraction >= 7) {
				targetCols = 8;
			} else if (colFraction >= 5) {
				targetCols = 6;
			} else {
				targetCols = 4;
			}

			// Snap de altura (1x = 220px, 2x = 460px; limiar em 340px)
			var targetHeight = (currentPxHeight >= 340) ? 2 : 1;

			if (targetCols !== resizeState.tempCols || targetHeight !== resizeState.tempHeight) {
				resizeState.tempCols = targetCols;
				resizeState.tempHeight = targetHeight;
				applyWidgetGeometry(resizeState.card, { width: targetCols, height: targetHeight });
			}
		});

		function finishWidgetResize(e) {
			if (!resizeState || (e && resizeState.pointerId !== e.pointerId)) return;
			resizeState.card.classList.remove('is-resizing');
			if (e && resizeState.handle.hasPointerCapture(e.pointerId)) {
				resizeState.handle.releasePointerCapture(e.pointerId);
			}

			if (resizeState.tempCols != null) {
				resizeState.widget.width = resizeState.tempCols;
			}
			if (resizeState.tempHeight != null) {
				resizeState.widget.height = resizeState.tempHeight;
			}
			saveWidgetsLayout();
			resizeState = null;
		}

		widgetsGrid.addEventListener('pointerup', finishWidgetResize);
		widgetsGrid.addEventListener('pointercancel', finishWidgetResize);

		// Delegação de cliques em botões do grid
		widgetsGrid.addEventListener('click', function (e) {
			var switchBtn = e.target.closest('.dashboard-widget-switch-btn');
			if (switchBtn) {
				var switchCard = switchBtn.closest('.dashboard-widget-card');
				if (switchCard) openSwitchDropdown(switchCard, switchBtn);
				return;
			}

			var removeBtn = e.target.closest('.dashboard-widget-remove-btn');
			if (removeBtn) {
				var card = removeBtn.closest('.dashboard-widget-card');
				if (!card) return;
				var wid = card.getAttribute('data-widget-id');
				widgetsList = widgetsList.filter(function (w) { return w.id !== wid; });
				saveWidgetsLayout();
				renderWidgetsGrid();
			}
		});

		// Modal de catálogo de widgets
		function openCatalogModal(widgetId) {
			if (!modal) return;
			replacementTargetId = widgetId || null;
			modal.classList.remove('hidden');

			if (modalList) {
				modalList.innerHTML =
					'<div class="text-center py-8 text-sm text-slate-500">' +
						'<i data-lucide="loader" class="size-6 animate-spin mx-auto text-sky-600 mb-2"></i>' +
						'Carregando catálogo de widgets...' +
					'</div>';
				if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
			}

			fetchCatalog(function (catalog) {
				if (!modalList) return;
				modalList.innerHTML = '';

				if (catalog && catalog.length > 0) {
					catalog.forEach(function (widget) {
						var isAdded = widgetsList.some(function (w) { return w.id === widget.id; });
						var isTarget = replacementTargetId && replacementTargetId === widget.id;
						var item = document.createElement('div');
						item.className = 'flex items-center justify-between rounded-lg border border-slate-200 p-3 hover:bg-slate-50 transition-colors';
						item.innerHTML =
							'<div class="flex items-center gap-3">' +
								'<div class="flex size-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600">' +
									'<i data-lucide="puzzle" class="size-5"></i>' +
								'</div>' +
								'<div>' +
									'<p class="text-sm font-semibold text-slate-900">' + escapeWidgetText(widget.name || widget.id) + '</p>' +
									'<p class="text-xs text-slate-500 font-mono">' + escapeWidgetText(widget.id) + '</p>' +
								'</div>' +
							'</div>' +
							'<div>' +
								(replacementTargetId
									? (isTarget
										? '<span class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-full"><i data-lucide="check" class="size-3.5"></i> ' + escapeWidgetText(labels.active) + '</span>'
										: '<button type="button" class="dashboard-catalog-select-btn c2fc-botao c2fc-botao-primario c2fc-botao-pequeno" data-widget-id="' + escapeWidgetText(widget.id) + '" data-widget-name="' + escapeWidgetText(widget.name || widget.id) + '"><i data-lucide="arrow-left-right" class="size-3.5"></i> ' + escapeWidgetText(labels.select) + '</button>'
									)
									: (isAdded
										? '<span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full"><i data-lucide="check" class="size-3.5"></i> ' + escapeWidgetText(labels.active) + '</span>'
										: '<button type="button" class="dashboard-catalog-add-btn c2fc-botao c2fc-botao-primario c2fc-botao-pequeno" data-widget-id="' + escapeWidgetText(widget.id) + '" data-widget-name="' + escapeWidgetText(widget.name || widget.id) + '"><i data-lucide="plus" class="size-3.5"></i> ' + escapeWidgetText(labels.add) + '</button>'
									)
								) +
							'</div>';
						modalList.appendChild(item);
					});
				} else {
					modalList.innerHTML = '<p class="text-center py-6 text-sm text-slate-500">Nenhum widget registrado no sistema.</p>';
				}

				if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
			});
		}

		function closeCatalogModal() {
			if (modal) modal.classList.add('hidden');
			replacementTargetId = null;
		}

		if (addBtn) addBtn.addEventListener('click', function () { openCatalogModal(null); });
		document.querySelectorAll('.dashboard-btn-open-catalog').forEach(function (btn) {
			btn.addEventListener('click', function () { openCatalogModal(null); });
		});
		document.querySelectorAll('.dashboard-widgets-modal-close').forEach(function (btn) {
			btn.addEventListener('click', closeCatalogModal);
		});

		// Adiciona ou substitui widget a partir do catálogo modal
		if (modalList) {
			modalList.addEventListener('click', function (e) {
				var addBtnClick = e.target.closest('.dashboard-catalog-add-btn');
				var selectBtnClick = e.target.closest('.dashboard-catalog-select-btn');

				if (selectBtnClick && replacementTargetId) {
					var targetId = replacementTargetId;
					var newId = selectBtnClick.getAttribute('data-widget-id');
					var newName = selectBtnClick.getAttribute('data-widget-name');
					replaceWidget(targetId, newId, newName);
					closeCatalogModal();
					return;
				}

				if (addBtnClick) {
					var wid = addBtnClick.getAttribute('data-widget-id');
					var wname = addBtnClick.getAttribute('data-widget-name');
					if (wid && !widgetsList.some(function (w) { return w.id === wid; })) {
						widgetsList.push({
							id: wid,
							name: wname,
							width: 4,
							height: 1
						});
						saveWidgetsLayout();
						renderWidgetsGrid();
						closeCatalogModal();
					}
				}
			});
		}

		// Resetar widgets
		if (resetBtn) {
			resetBtn.addEventListener('click', function (e) {
				e.preventDefault();
				widgetsList = [];
				saveWidgetsLayout();
				renderWidgetsGrid();
			});
		}

		// Renderiza estado inicial
		renderWidgetsGrid();
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
