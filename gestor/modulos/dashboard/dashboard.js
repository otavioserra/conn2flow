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
				// req-242: o estado visual vem só de `.active` (folha do componente), igual nas duas abas.
				btn.classList.toggle('active', target === targetId);
				btn.setAttribute('aria-selected', String(target === targetId));
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

	// req-242: capa OU ícone. Imagem de capa que não carrega devolve o card ao ícone, sem sobrepor os dois.
	function initDashboardCoverFallback() {
		var cards = document.getElementById('dashboard-sortable-cards');
		if (!cards) return;
		function semCapa(img) {
			var card = img.closest('.dashboard-module-card');
			if (!card) return;
			card.classList.remove('has-cover');
			card.classList.add('no-cover');
		}
		cards.addEventListener('error', function (e) {
			if (e.target && e.target.classList && e.target.classList.contains('dashboard-module-cover')) semCapa(e.target);
		}, true);
		Array.prototype.forEach.call(cards.querySelectorAll('img.dashboard-module-cover'), function (img) {
			if (img.complete && img.naturalWidth === 0 && img.getAttribute('loading') !== 'lazy') semCapa(img);
		});
	}

	initDashboardCoverFallback();

	// ===== Sistema de Abas do Dashboard > =====

	// ===== Grid Flexível de Widgets (req-226 / req-233) < =====

	function initDashboardWidgets() {
		var grid = document.getElementById('dashboard-widgets-grid');
		if (!grid) return;
		var modal = document.getElementById('dashboard-widgets-modal');
		var list = document.getElementById('dashboard-widgets-modal-list');
		var empty = document.getElementById('dashboard-widgets-empty');
		var editButton = document.getElementById('dashboard-edit-mode');
		// req-242: altura em passos de 20 px (a malha do redimensionamento), de 120 a 960 px.
		var MIN_HEIGHT = 120, MAX_HEIGHT = 960, STEP_HEIGHT = 20;
		var labels = {};
		['type', 'record', 'loading', 'error', 'empty', 'remove', 'drag', 'more', 'resize', 'switch', 'select', 'config', 'duplicate', 'edit', 'empty-profile', 'object', 'object-empty', 'not-image'].forEach(function (key) {
			labels[key] = grid.getAttribute('data-label-' + key) || '';
		});
		// Configurações por widget: gravadas com o layout, em `options`. Valor fora da lista volta ao padrão.
		var PADDINGS = ['none', 'small', 'medium', 'large'], REFRESH = [0, 60, 300, 900];
		// REQ-250: famílias do Google Fonts oferecidas (a mesma lista vale no servidor), limiares de largura para
		// esconder um item e tipos de objeto livre. Objeto é um item do layout com o identificador reservado `objeto`.
		var FONTS = ['Inter', 'Roboto', 'Open Sans', 'Montserrat', 'Poppins', 'Lato', 'Raleway', 'Playfair Display', 'Merriweather', 'Oswald', 'Bebas Neue', 'Dancing Script', 'Roboto Mono'];
		var HIDE = {sm: 640, md: 1024, lg: 1280}, OBJECT_ID = 'objeto', OBJECT_TYPES = ['text', 'shape', 'image', 'icon', 'button'];
		function safeColor(value, fallback) { return /^#[0-9a-f]{6}$/i.test(value || '') ? String(value).toLowerCase() : fallback; }
		// Imagem: só caminho do próprio painel, sem `..` e com extensão de imagem (entra em `url("…")` e em `src`).
		function safeImage(value) { value = String(value == null ? '' : value); return /^\/[A-Za-z0-9_\-.\/%~]+\.(png|jpe?g|gif|webp|avif|svg)$/i.test(value) && value.indexOf('..') === -1 && value.indexOf('//') === -1 ? value : ''; }
		// Destino do botão: endereço http(s) ou caminho do painel; nada de `javascript:` nem `//host`.
		function safeLink(value) { value = String(value == null ? '' : value).trim(); return /^(https?:\/\/|\/(?!\/))[^\s"'<>\\]*$/i.test(value) ? value.slice(0, 500) : ''; }
		function normalizeObject(o) {
			o = o && typeof o === 'object' ? o : {};
			var size = Math.round(Number(o.size));
			return {
				type: OBJECT_TYPES.indexOf(o.type) !== -1 ? o.type : 'text',
				text: String(o.text == null ? '' : o.text).slice(0, 2000),
				font: FONTS.indexOf(o.font) !== -1 ? o.font : '',
				size: size >= 10 && size <= 160 ? size : 28,
				weight: Number(o.weight) === 400 ? 400 : 700,
				align: ['left', 'center', 'right'].indexOf(o.align) !== -1 ? o.align : 'center',
				color: safeColor(o.color, '#0f172a'),
				fill: safeColor(o.fill, '#0ea5e9'),
				shape: ['rect', 'rounded', 'circle', 'line'].indexOf(o.shape) !== -1 ? o.shape : 'rounded',
				src: safeImage(o.src),
				fit: o.fit === 'contain' ? 'contain' : 'cover',
				alt: String(o.alt == null ? '' : o.alt).slice(0, 160),
				icon: /^[a-z0-9-]{1,40}$/.test(o.icon || '') ? o.icon : 'star',
				href: safeLink(o.href),
				newTab: o.newTab === true
			};
		}
		function normalizeOptions(o) {
			o = o && typeof o === 'object' ? o : {};
			return {
				header: o.header !== false,
				frame: o.frame !== false,
				title: String(o.title == null ? '' : o.title).trim().slice(0, 80),
				background: /^#[0-9a-f]{6}$/i.test(o.background || '') ? String(o.background).toLowerCase() : '',
				padding: PADDINGS.indexOf(o.padding) !== -1 ? o.padding : 'none',
				refresh: REFRESH.indexOf(Number(o.refresh)) !== -1 ? Number(o.refresh) : 0,
				// REQ-250
				hide: HIDE[o.hide] ? o.hide : '',
				bgImage: safeImage(o.bgImage),
				bgOpacity: o.bgOpacity === null || o.bgOpacity === undefined || o.bgOpacity === '' || !isFinite(Number(o.bgOpacity)) ? 100 : Math.max(0, Math.min(100, Math.round(Number(o.bgOpacity)))),
				bgFit: ['cover', 'contain', 'repeat'].indexOf(o.bgFit) !== -1 ? o.bgFit : 'cover',
				titleFont: FONTS.indexOf(o.titleFont) !== -1 ? o.titleFont : ''
			};
		}
		function applyOptions(card, widget) {
			var o = widget.options = normalizeOptions(widget.options);
			var name = o.title || widget.name || widget.id;
			// Sem cabeçalho ele some só fora do modo de edição (regra na folha do componente).
			card.classList.toggle('is-headerless', !o.header);
			card.classList.toggle('is-frameless', !o.frame);
			card.style.backgroundColor = o.background;
			var dark = false;
			if (o.background) { var rgb = parseInt(o.background.slice(1), 16); dark = (0.299 * (rgb >> 16) + 0.587 * ((rgb >> 8) & 255) + 0.114 * (rgb & 255)) < 140; }
			card.setAttribute('data-widget-tone', dark ? 'dark' : 'light');
			var body = card.querySelector('.dashboard-widget-card-body'), title = card.querySelector('.dashboard-widget-title'), frame = card.querySelector('iframe');
			if (body) body.setAttribute('data-widget-padding', o.padding);
			if (title) title.textContent = name;
			if (frame) frame.title = name;
			// REQ-250: esconder por largura, imagem de fundo e fonte do título.
			if (o.hide) card.setAttribute('data-widget-hide', o.hide); else card.removeAttribute('data-widget-hide');
			card.classList.toggle('has-bg-image', !!o.bgImage);
			['--widget-bg-image', '--widget-bg-opacity', '--widget-bg-size', '--widget-bg-repeat'].forEach(function (name) { card.style.removeProperty(name); });
			if (o.bgImage) {
				card.style.setProperty('--widget-bg-image', 'url("' + o.bgImage + '")'); card.style.setProperty('--widget-bg-opacity', String(o.bgOpacity / 100));
				card.style.setProperty('--widget-bg-size', o.bgFit === 'repeat' ? 'auto' : o.bgFit); card.style.setProperty('--widget-bg-repeat', o.bgFit === 'repeat' ? 'repeat' : 'no-repeat');
			}
			if (title) title.style.fontFamily = o.titleFont ? '"' + o.titleFont + '", sans-serif' : '';
			useFonts();
			clearInterval(card._refreshTimer); card._refreshTimer = null;
			// Recarrega sozinho só com a aba visível e fora do modo de edição.
			if (o.refresh) card._refreshTimer = setInterval(function () {
				if (!card.isConnected) { clearInterval(card._refreshTimer); return; }
				if (!document.hidden && !editing && card._load) card._load();
			}, o.refresh * 1000);
		}
		var stored = typeof gestor !== 'undefined' && gestor.dashboard_user_prefs ? gestor.dashboard_user_prefs.widgets_layout : null;
		var widgets = Array.isArray(stored) ? stored : (getLocalStorage('dashboard_widgets_layout') || []);
		if (!Array.isArray(widgets)) widgets = [];
		// REQ-248: dois modos. Grade: malha de 12 colunas, widgets em ordem, arrasto reordena. Lousa: células
		// que acompanham a largura, largura do widget em células (2 a 24) e posição livre (`x` = coluna,
		// `y` = linha de 20 px). Valor antigo de largura em texto cai nos degraus de antes.
		var MIN_COLS = 2, MAX_COLS = 24, GRID_COLS = 12, CELL = 90, GAP = 20, ROW = 20, MAX_ROW = 4000;
		function cell(value, max) { var n = Number(value); return value !== null && value !== '' && Number.isInteger(n) && n >= 0 && n <= max ? n : null; }
		function normalizeWidget(w, index) {
			var width = Math.round(Number(w.width));
			if (!(width >= MIN_COLS && width <= MAX_COLS)) width = /full|12/.test(w.width) ? 12 : (/8/.test(w.width) ? 8 : (/2|6/.test(w.width) ? 6 : 4));
			var pixels = Number(w.height_px) || (Number(w.height) === 2 ? 460 : 220);
			return Object.assign({}, w, {width: width, height_px: Math.max(MIN_HEIGHT, Math.min(MAX_HEIGHT, pixels)), height: Number(w.height) === 2 ? 2 : 1, instance_id: w.instance_id || 'saved-' + index, registro_id: w.registro_id || '', params: w.params || {}, options: normalizeOptions(w.options), x: cell(w.x, MAX_COLS - 1), y: cell(w.y, MAX_ROW)}, w.id === OBJECT_ID ? {object: normalizeObject(w.object), registro_id: ''} : {});
		}
		function instanceId() { return 'widget-' + Date.now() + '-' + Math.random().toString(36).slice(2); }
		function copyLayout(list) { return JSON.parse(JSON.stringify(list || [])); }
		widgets = widgets.map(normalizeWidget);
		// REQ-247: quem administra edita o próprio layout ou vê o padrão do perfil; quem só visualiza recebe o do perfil.
		var access = (typeof gestor !== 'undefined' && gestor.dashboard_user_prefs && gestor.dashboard_user_prefs.widgets) || {};
		var canEdit = access.pode_editar !== false;
		var ownWidgets = widgets, profileWidgets = (Array.isArray(access.layout_perfil) ? access.layout_perfil : []).map(normalizeWidget);
		var savedLayouts = Array.isArray(access.salvos) ? access.salvos.filter(function (l) { return l && l.id && Array.isArray(l.widgets); }) : [];
		var source = canEdit && access.fonte !== 'perfil' ? 'own' : 'profile';
		widgets = source === 'own' ? ownWidgets : profileWidgets;
		function editable() { return canEdit && source === 'own'; }
		// O modo acompanha o layout: o do usuário no próprio layout, o publicado no padrão do perfil.
		var ownMode = access.modo === 'lousa' ? 'board' : 'grid', profileMode = access.modo_perfil === 'lousa' ? 'board' : 'grid';
		function board() { return (source === 'own' ? ownMode : profileMode) === 'board'; }
		// REQ-249: a aba de widgets pode vir antes da de módulos; a escolha acompanha o layout, como o modo.
		var ownFirst = access.primeiro === true, profileFirst = access.primeiro_perfil === true;
		function widgetsFirst() { return source === 'own' ? ownFirst : profileFirst; }
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
		function save() { if (!editable()) return; ownWidgets = widgets; setLocalStorage('dashboard_widgets_layout', widgets, 43200); dashboardSalvarPreferenciaBackend('dashboard_widgets_layout', widgets); }
		function geometry(card, widget) {
			card.setAttribute('data-widget-cols', board() ? widget.width : Math.min(GRID_COLS, widget.width));
			card.setAttribute('data-widget-height', widget.height);
			card.style.height = (widget.height_px || (widget.height === 2 ? 460 : 220)) + 'px';
			card.style.minHeight = MIN_HEIGHT + 'px';
		}
		// ----- Objetos livres, fontes e esconder por largura (REQ-250)
		// Uma folha só do Google Fonts, com as famílias em uso no layout; some quando nenhuma é usada.
		function useFonts() {
			var used = {};
			widgets.forEach(function (w) { var font = normalizeOptions(w.options).titleFont; if (font) used[font] = true; if (w.id === OBJECT_ID && w.object && FONTS.indexOf(w.object.font) !== -1) used[w.object.font] = true; });
			var names = Object.keys(used).sort(), link = document.getElementById('dashboard-google-fonts');
			if (!names.length) { if (link) link.remove(); return; }
			// Peso 700 só onde a família tem; pedir um peso que não existe invalida a folha inteira.
			var href = 'https://fonts.googleapis.com/css2?' + names.map(function (name) { return 'family=' + encodeURIComponent(name).replace(/%20/g, '+') + (name === 'Bebas Neue' ? '' : ':wght@400;700'); }).join('&') + '&display=swap';
			if (!link) { link = document.createElement('link'); link.id = 'dashboard-google-fonts'; link.rel = 'stylesheet'; document.head.appendChild(link); }
			if (link.getAttribute('href') !== href) link.setAttribute('href', href);
		}
		function viewport() { return (typeof window !== 'undefined' && window.innerWidth) || document.documentElement.clientWidth || 0; }
		// Fora do modo de edição, o item marcado para sumir abaixo de uma largura não aparece nem ocupa lugar.
		function hiddenNow(widget) { var hide = normalizeOptions(widget.options).hide; return !editing && !!hide && viewport() < HIDE[hide]; }
		function drawObject(card, widget) {
			var o = widget.object = normalizeObject(widget.object), body = card.querySelector('.dashboard-widget-card-body');
			var box = document.createElement('div'), font = o.font ? '"' + o.font + '", sans-serif' : '';
			box.className = 'dashboard-object dashboard-object-' + o.type;
			card.setAttribute('data-object-type', o.type);
			// Tudo por texto e atributo: nada do que o usuário escreve vira HTML.
			if (o.type === 'text') {
				box.textContent = o.text || labels['object-empty']; box.classList.toggle('is-placeholder', !o.text);
				box.style.fontFamily = font; box.style.fontSize = o.size + 'px'; box.style.fontWeight = o.weight; box.style.textAlign = o.align; box.style.color = o.color;
			} else if (o.type === 'shape') {
				var shape = document.createElement('div'); shape.className = 'dashboard-object-figure'; shape.setAttribute('data-shape', o.shape); shape.style.backgroundColor = o.fill; box.appendChild(shape);
			} else if (o.type === 'image') {
				if (o.src) { var image = document.createElement('img'); image.src = o.src; image.alt = o.alt; image.loading = 'lazy'; image.style.objectFit = o.fit; box.appendChild(image); }
				else { box.textContent = labels['object-empty']; box.classList.add('is-placeholder'); }
			} else if (o.type === 'icon') {
				var icon = document.createElement('i'); icon.setAttribute('data-lucide', o.icon); box.style.color = o.color; box.appendChild(icon);
			} else {
				var link = document.createElement('a'); link.className = 'dashboard-object-action'; link.textContent = o.text || labels['object-empty'];
				if (o.href) link.href = o.href;
				if (o.newTab) { link.target = '_blank'; link.rel = 'noopener'; }
				link.style.backgroundColor = o.fill; link.style.color = o.color; link.style.fontFamily = font; link.style.fontSize = o.size + 'px'; link.style.fontWeight = o.weight;
				box.appendChild(link);
			}
			body.replaceChildren(box);
			icons(); useFonts();
		}
		// ----- Lousa (REQ-248)
		// Colunas que cabem na largura disponível; abaixo de 640 px, uma só. Sem medida (teste), 12.
		function boardCols() {
			var width = grid.clientWidth;
			if (!width) return 12;
			if (width < 640) return 1;
			return Math.max(MIN_COLS, Math.min(MAX_COLS, Math.floor((width + GAP) / (CELL + GAP))));
		}
		// Linhas ocupadas: a altura do card mais uma linha, que é a distância para o de baixo.
		function boardRows(widget) { return Math.round((widget.height_px || 220) / ROW) + 1; }
		// Onde cada widget fica numa lousa de `cols` colunas. Quem tem posição guardada tenta ficar nela
		// (encosta na borda se não couber e desce se o lugar estiver ocupado); quem não tem vai para o
		// primeiro vão livre. `first` é o widget que o usuário acabou de soltar: ele escolhe o lugar antes.
		function arrange(list, cols, first) {
			var taken = {}, places = {};
			function free(x, y, w, h) { for (var i = x; i < x + w; i++) for (var j = y; j < y + h; j++) if (taken[i + ':' + j]) return false; return true; }
			list.map(function (widget, index) { return {widget: widget, index: index, placed: widget.x !== null && widget.y !== null && widget.x !== undefined && widget.y !== undefined}; }).sort(function (a, b) {
				if (first && (a.widget === first) !== (b.widget === first)) return a.widget === first ? -1 : 1;
				if (a.placed !== b.placed) return a.placed ? -1 : 1;
				if (!a.placed) return a.index - b.index;
				return (a.widget.y - b.widget.y) || (a.widget.x - b.widget.x) || (a.index - b.index);
			}).forEach(function (entry) {
				var widget = entry.widget, w = Math.max(1, Math.min(widget.width, cols)), h = boardRows(widget), x = 0, y = 0;
				if (entry.placed) { x = Math.max(0, Math.min(widget.x, cols - w)); y = widget.y; while (!free(x, y, w, h)) y++; }
				else { search: for (y = 0; ; y++) for (x = 0; x + w <= cols; x++) if (free(x, y, w, h)) break search; }
				for (var i = x; i < x + w; i++) for (var j = y; j < y + h; j++) taken[i + ':' + j] = true;
				places[widget.instance_id] = {x: x, y: y, w: w, h: h};
			});
			// Vão entre widgets fica; faixa vazia acima de todos não: o conjunto sobe até a primeira linha.
			var top = Object.keys(places).reduce(function (min, id) { return Math.min(min, places[id].y); }, Infinity);
			if (top > 0 && top !== Infinity) Object.keys(places).forEach(function (id) { places[id].y -= top; });
			return places;
		}
		// Aplica o arranjo nos cards que já estão na tela: nenhum iframe recarrega.
		function layout(first) {
			grid.classList.toggle('is-board', board());
			Array.from(grid.children).forEach(function (card) { var w = widgets.find(function (x) { return x.instance_id === card.dataset.widgetInstance; }); if (w) card.classList.toggle('is-hidden-now', hiddenNow(w)); });
			if (!board()) {
				// Grade: quem posiciona é a folha de estilo, pela largura em colunas e pela ordem.
				grid.style.removeProperty('--board-cols'); grid.removeAttribute('data-board-cols');
				Array.from(grid.children).forEach(function (card) { card.style.gridColumn = ''; card.style.gridRow = ''; card.removeAttribute('data-board-x'); card.removeAttribute('data-board-y'); });
				return {};
			}
			var cols = boardCols(), places = arrange(widgets.filter(function (w) { return !hiddenNow(w); }), cols, first || null);
			grid.style.setProperty('--board-cols', cols); grid.setAttribute('data-board-cols', cols);
			Array.from(grid.children).forEach(function (card) {
				var place = places[card.dataset.widgetInstance]; if (!place) return;
				card.style.gridColumn = (place.x + 1) + ' / span ' + place.w; card.style.gridRow = (place.y + 1) + ' / span ' + place.h;
				card.setAttribute('data-board-x', place.x); card.setAttribute('data-board-y', place.y);
			});
			return places;
		}
		// Depois de uma edição: o arranjo que está na tela vira a posição guardada de cada widget.
		function commit(first) {
			if (!board()) { save(); return; }
			var places = layout(first);
			widgets.forEach(function (widget) { var place = places[widget.instance_id]; if (place) { widget.x = place.x; widget.y = place.y; } });
			save();
		}
		function setEditing(value) {
			if (!value && resize) {
				geometry(resize.card, resize.widget);
				resize.card.classList.remove('is-resizing');
				if (resize.handle.hasPointerCapture(resize.pointer)) resize.handle.releasePointerCapture(resize.pointer);
				resize = null;
			}
			editing = value && editable();
			grid.classList.toggle('is-editing', editing);
			if (editButton) {
				editButton.setAttribute('aria-checked', String(editing));
				var label = editButton.querySelector('[data-edit-label]');
				if (label) label.textContent = editButton.getAttribute(editing ? 'data-label-on' : 'data-label-off');
				editButton.disabled = !editable();
			}
			if (!editing) grid.classList.remove('is-interacting');
			if (grid._dashboardSortable) grid._dashboardSortable.option('disabled', !editing);
			// Entrar ou sair da edição muda o que está escondido por largura.
			if (grid.children.length) layout();
			try { sessionStorage.setItem('dashboard_widgets_editing', String(editing)); } catch (_) {}
		}
		if (editButton) editButton.addEventListener('click', function () { setEditing(!editing); });
		function render() {
			if (grid._dashboardSortable) { grid._dashboardSortable.destroy(); grid._dashboardSortable = null; }
			Array.from(grid.children).forEach(function (old) { clearInterval(old._refreshTimer); });
			grid.innerHTML = '';
			if (empty) {
				empty.classList.toggle('hidden', widgets.length > 0);
				var emptyText = empty.querySelector('[data-empty-text]');
				if (emptyText && !editable() && labels['empty-profile']) emptyText.textContent = labels['empty-profile'];
			}
			widgets.forEach(function (widget) {
				var card = document.createElement('div');
				card.className = 'dashboard-widget-card flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm';
				card.dataset.widgetInstance = widget.instance_id;
				card.dataset.widgetId = widget.id;
				geometry(card, widget);
				card.innerHTML = '<div class="dashboard-widget-card-header flex min-w-0 items-center gap-2 border-b border-slate-100 px-4 py-3">' +
					'<button type="button" class="dashboard-widget-drag-handle c2fc-botao c2fc-botao-icone c2fc-botao-fantasma" aria-label="'+escape(labels.drag)+'" data-c2f-dica="'+escape(labels.drag)+'" data-c2f-dica-pos="bottom left"><i data-lucide="grip-vertical" class="size-4"></i></button>' +
					'<span class="dashboard-widget-title min-w-0 flex-1 truncate font-semibold">'+escape(widget.name || widget.id)+'</span>' +
					'<button type="button" class="dashboard-widget-switch-btn c2fc-botao c2fc-botao-icone c2fc-botao-fantasma" aria-label="'+escape(labels.switch)+'" data-c2f-dica="'+escape(labels.switch)+'" data-c2f-dica-pos="bottom right"><i data-lucide="arrow-left-right" class="size-4"></i></button>' +
					'<button type="button" class="dashboard-widget-config-btn c2fc-botao c2fc-botao-icone c2fc-botao-fantasma" aria-label="'+escape(labels.config)+'" data-c2f-dica="'+escape(labels.config)+'" data-c2f-dica-pos="bottom right"><i data-lucide="settings-2" class="size-4"></i></button>' +
					'<button type="button" class="dashboard-widget-duplicate-btn c2fc-botao c2fc-botao-icone c2fc-botao-fantasma" aria-label="'+escape(labels.duplicate)+'" data-c2f-dica="'+escape(labels.duplicate)+'" data-c2f-dica-pos="bottom right"><i data-lucide="copy" class="size-4"></i></button>' +
					'<a class="dashboard-widget-edit-btn c2fc-botao c2fc-botao-icone c2fc-botao-fantasma" target="_blank" rel="noopener" hidden aria-label="'+escape(labels.edit)+'" data-c2f-dica="'+escape(labels.edit)+'" data-c2f-dica-pos="bottom right"><i data-lucide="square-pen" class="size-4"></i></a>' +
					'<button type="button" class="dashboard-widget-remove-btn c2fc-botao c2fc-botao-icone c2fc-botao-fantasma" aria-label="'+escape(labels.remove)+'" data-c2f-dica="'+escape(labels.remove)+'" data-c2f-dica-pos="bottom right"><i data-lucide="x" class="size-4"></i></button></div>' +
					'<div class="dashboard-widget-card-body flex-1 w-full min-w-0 min-h-0 overflow-hidden relative p-0">'+escape(labels.loading)+'</div>';
				['se'].forEach(function (corner) {
					var handle = document.createElement('button'); handle.type = 'button'; handle.className = 'dashboard-widget-resize-handle'; handle.dataset.corner = corner; handle.setAttribute('aria-label', labels.resize); handle.innerHTML = '<i data-lucide="move-diagonal-2" class="size-3.5"></i>'; card.appendChild(handle);
				});
				grid.appendChild(card);
				var body = card.querySelector('.dashboard-widget-card-body');
				if (widget.id === OBJECT_ID) card.classList.add('is-object');
				card._load = widget.id === OBJECT_ID ? function () { drawObject(card, widget); } : function () { request('widget-render', {widget_id:widget.id, registro_id:widget.registro_id, instance_id:widget.instance_id, params:widget.params}).then(function (data) {
					if (!card.isConnected) return;
					// Atalho para a edição do registro: só endereço do próprio painel.
					var editLink = card.querySelector('.dashboard-widget-edit-btn'), editUrl = null;
					try { editUrl = data.edit_url ? new URL(data.edit_url, location.origin) : null; } catch (_) { editUrl = null; }
					if (editLink) { editLink.hidden = !(editUrl && editUrl.origin === location.origin); if (!editLink.hidden) editLink.href = editUrl.href; }
					var frame = document.createElement('iframe');
					frame.className = 'dashboard-widget-frame w-full h-full border-0 block min-h-0';
					frame.title = (widget.options && widget.options.title) || widget.name || widget.id;
					// REQ-248: link ou formulário de dentro do widget abre na página de fora, e só por clique do usuário.
					// O documento continua de origem opaca: não lê cookie nem a página do painel.
					// REQ-249: `allow-forms` porque sem ele o navegador barra todo envio de formulário (a busca não funcionava);
					// o envio também sai para a página de fora.
					frame.setAttribute('sandbox', 'allow-scripts allow-forms allow-top-navigation-by-user-activation');
					// req-242: o documento isolado pode pedir tela cheia (apresentações); o resto do isolamento não muda.
					frame.setAttribute('allow', 'fullscreen');
					frame.setAttribute('allowfullscreen', '');
					var jquery = document.querySelector('script[src*="jquery"]');
					var rootUrl = new URL((typeof gestor !== 'undefined' && gestor.raiz) || '/', location.origin).href;
					var theme = String(data.theme_styles || '').replace(/<\/style/gi, '<\\/style');
					var compiler = data.tailwind_compiler_url ? '<script src="'+escape(new URL(data.tailwind_compiler_url, rootUrl).href)+'"><\/script>' : '';
					var lucideScript = data.lucide_url ? '<script src="'+escape(new URL(data.lucide_url, rootUrl).href)+'"><\/script>' : '';
					var styles = String(data.css || '');
					var layoutStyles = styles.match(/^<style data-tailwind-role="layout-precompiled">[\s\S]*?<\/style>/);
					var layoutCss = layoutStyles ? layoutStyles[0] : '';
					if (layoutCss) styles = styles.slice(layoutCss.length);
					// As folhas pré-compiladas do widget são parciais (template, registro): depois da folha completa,
					// a utility simples delas (`grid-cols-1`) venceria a responsiva (`md:grid-cols-3`). O compilador
					// entra por último: a folha que ele gera cobre o documento inteiro e fecha a camada de utilities.
					// CSS de autoria não tem camada e continua vencendo, como na página pública.
					// O iframe mantém origem opaca: inicialização acontece dentro do documento isolado.
					var initScript = '<script>window.addEventListener("load",function(){if(window.lucide)window.lucide.createIcons();window.dispatchEvent(new Event("resize"));});' +
						// Âncora interna (`#secao`) fica dentro do widget. Todo outro link e todo formulário saem para a página
						// de fora, mesmo com `target` próprio (`_self`, `_blank`): REQ-249.
						'function c2fFora(u){try{window.top.location.href=u;}catch(e){}}' +
						'document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("a[href]");if(!a)return;var h=a.getAttribute("href");' +
						'if(h.charAt(0)==="#"){e.preventDefault();var t=h.length>1&&document.getElementById(h.slice(1));if(t)t.scrollIntoView();return;}' +
						'if(!/^javascript:/i.test(h))a.target="_top";},true);' +
						'document.addEventListener("submit",function(e){if(e.target&&e.target.tagName==="FORM")e.target.target="_top";},true);' +
						// Navegação feita por script (`location.href = …`) também vai para fora, onde o navegador deixa interceptar.
						'if(window.navigation&&navigation.addEventListener)navigation.addEventListener("navigate",function(e){if(!e.cancelable||e.hashChange||e.downloadRequest||e.navigationType==="reload")return;' +
						'var u=e.destination&&e.destination.url;if(!u||/^about:/i.test(u))return;e.preventDefault();c2fFora(u);});<\/script>';
					frame.srcdoc = '<!doctype html><html data-c2f-dashboard-widget><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><base href="'+escape(rootUrl)+'" target="_top">'+
						'<style>html,body{margin:0;padding:0;width:100%;height:100%;overflow-x:hidden}body{font-family:var(--font-sans,system-ui,-apple-system,sans-serif)}</style>'+
						layoutCss+(theme ? '<style type="text/tailwindcss" data-c2f-tailwind-role="browser-contract">'+theme+'</style>' : '')+styles+compiler+
						'<script>window.gestor='+JSON.stringify({raiz:rootUrl,dashboardWidget:true}).replace(/</g, '\\u003c')+';<\/script>'+(jquery ? jquery.outerHTML : '')+lucideScript+
						'</head><body>'+(data.html || escape(labels.empty))+(data.scripts || '')+initScript+'</body></html>';
					body.replaceChildren(frame);
					icons();
				}).catch(function () { if (card.isConnected) body.textContent = labels.error; }); };
				applyOptions(card, widget);
				card._load();
			});
			layout();
			if (!board() && typeof Sortable !== 'undefined') grid._dashboardSortable = new Sortable(grid, {animation:200, disabled:!editing, handle:'.dashboard-widget-drag-handle', onStart:function () { grid.classList.add('is-interacting'); }, onEnd:function () {
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
						if (old) { Object.assign(old, config); if (old.options) old.options.title = ''; } else widgets.push(normalizeWidget(Object.assign(config,{instance_id:instanceId(),width:4,height:1}), widgets.length));
						render(); commit(); close();
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
		function open(instance) { if (!modal || !list || !editable()) return; returnFocus=document.activeElement;target=instance || null;pickerReset=false;modal.classList.remove('hidden');if(search)search.value='';var assigned=widgets.find(function(w){return w.instance_id===target;});if(selection)selection.textContent=assigned ? assigned.name || assigned.id : '';if(assigned)showRecords({id:assigned.id,name:(assigned.name || assigned.id).split(' / ')[0]},1,false);else showTypes();(search || modal.querySelector('.dashboard-widgets-modal-close')).focus(); }
		if (list) list.addEventListener('click',function(e){if(e.target.closest('.dashboard-widget-types'))showTypes();});
		document.querySelectorAll('#dashboard-btn-add-widget, .dashboard-btn-open-catalog').forEach(function(b){b.addEventListener('click',function(){open(null);});});
		document.querySelectorAll('.dashboard-widgets-modal-close').forEach(function(b){b.addEventListener('click',close);});
		if (modal) modal.addEventListener('click',function(e){if(e.target===modal)close();});
		document.addEventListener('keydown',function(e){
			if(e.key==='Escape'){var picking=pickerModal && !pickerModal.classList.contains('hidden');if(picking){closePicker();return;}var popup=[modal,configModal,layoutsModal].some(function(m){return m && !m.classList.contains('hidden');});close();closeConfig();closeLayouts();if(!popup && inWindow())setWindow(false);}
			if(e.key==='Tab' && modal && !modal.classList.contains('hidden')){
				var buttons=Array.from(modal.querySelectorAll('button:not([disabled]), input:not([disabled])')).filter(function(b){return !b.hidden;});var first=buttons[0],last=buttons[buttons.length-1];
				if(e.shiftKey && document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey && document.activeElement===last){e.preventDefault();first.focus();}
			}
		});
		grid.addEventListener('click',function(e){
			if(!editing)return;var card=e.target.closest('.dashboard-widget-card');if(!card)return;
			if(e.target.closest('.dashboard-widget-switch-btn'))open(card.dataset.widgetInstance);
			if(e.target.closest('.dashboard-widget-config-btn'))openConfig(card.dataset.widgetInstance);
			if(e.target.closest('.dashboard-widget-duplicate-btn')){
				var position=widgets.findIndex(function(w){return w.instance_id===card.dataset.widgetInstance;});
				if(position!==-1){var copy=copyLayout([widgets[position]])[0];copy.instance_id=instanceId();copy.x=copy.y=null;widgets.splice(position+1,0,copy);render();commit();}
			}
			if(e.target.closest('.dashboard-widget-remove-btn')){widgets=widgets.filter(function(w){return w.instance_id!==card.dataset.widgetInstance;});save();render();}
		});
		// ----- Pop-up de configurações do widget
		var configModal = document.getElementById('dashboard-widget-config-modal'), configTarget = null;
		function option(name) { return configModal.querySelector('[data-widget-option="' + name + '"]'); }
		function fillConfig(o) {
			option('header').checked = o.header; option('frame').checked = o.frame; option('title').value = o.title;
			option('background-custom').checked = !!o.background; option('background').value = o.background || '#ffffff'; option('background').disabled = !o.background;
			option('padding').value = o.padding; option('refresh').value = String(o.refresh);
			if (option('hide')) { option('hide').value = o.hide; option('bgImage').value = o.bgImage; option('bgOpacity').value = String(o.bgOpacity); option('bgFit').value = o.bgFit; option('titleFont').value = o.titleFont; }
		}
		function objectField(name) { return configModal.querySelector('[data-object-option="' + name + '"]'); }
		// Cada campo do objeto diz em `data-object-for` os tipos que o usam; só esses ficam à vista.
		function showObjectFields(type) {
			configModal.querySelectorAll('[data-object-for]').forEach(function (row) { row.hidden = row.getAttribute('data-object-for').split(' ').indexOf(type) === -1; });
		}
		function fillObject(o) {
			var section = configModal.querySelector('[data-object-section]'); if (!section) return;
			section.hidden = !o;
			if (!o) return;
			['type', 'text', 'font', 'align', 'color', 'fill', 'shape', 'src', 'fit', 'alt', 'icon', 'href'].forEach(function (name) { objectField(name).value = o[name]; });
			objectField('size').value = String(o.size); objectField('weight').value = String(o.weight); objectField('newTab').checked = o.newTab;
			showObjectFields(o.type);
		}
		function readObject() {
			var o = {};
			['type', 'text', 'font', 'size', 'weight', 'align', 'color', 'fill', 'shape', 'src', 'fit', 'alt', 'icon', 'href'].forEach(function (name) { o[name] = objectField(name).value; });
			o.newTab = objectField('newTab').checked;
			return normalizeObject(o);
		}
		function openConfig(instance) {
			var widget = widgets.find(function (w) { return w.instance_id === instance; });
			if (!configModal || !widget) return;
			returnFocus = document.activeElement; configTarget = instance;
			configModal.querySelector('[data-widget-config-name]').textContent = widget.name || widget.id;
			option('title').placeholder = widget.name || widget.id;
			fillConfig(normalizeOptions(widget.options));
			fillObject(widget.id === OBJECT_ID ? normalizeObject(widget.object) : null);
			configModal.classList.remove('hidden'); option('header').focus();
		}
		function closeConfig() {
			if (!configModal || configModal.classList.contains('hidden')) return;
			configModal.classList.add('hidden'); configTarget = null;
			if (returnFocus && returnFocus.isConnected) returnFocus.focus();
		}
		if (configModal) {
			option('background-custom').addEventListener('change', function () { option('background').disabled = !this.checked; });
			if (objectField('type')) objectField('type').addEventListener('change', function () { showObjectFields(this.value); });
			configModal.addEventListener('click', function (e) {
				if (e.target === configModal || e.target.closest('.dashboard-widget-config-close')) { closeConfig(); return; }
				if (e.target.closest('.dashboard-widget-config-reset')) { fillConfig(normalizeOptions(null)); return; }
				var pick = e.target.closest('[data-pick-image]');
				if (pick) { openPicker(configModal.querySelector(pick.getAttribute('data-pick-image'))); return; }
				var clear = e.target.closest('[data-clear-image]');
				if (clear) { configModal.querySelector(clear.getAttribute('data-clear-image')).value = ''; return; }
				if (!e.target.closest('.dashboard-widget-config-save')) return;
				var instance = configTarget, widget = widgets.find(function (w) { return w.instance_id === instance; });
				if (widget) {
					widget.options = normalizeOptions({header: option('header').checked, frame: option('frame').checked, title: option('title').value,
						background: option('background-custom').checked ? option('background').value : '', padding: option('padding').value, refresh: option('refresh').value,
						hide: option('hide') ? option('hide').value : '', bgImage: option('bgImage') ? option('bgImage').value : '', bgOpacity: option('bgOpacity') ? option('bgOpacity').value : 100, bgFit: option('bgFit') ? option('bgFit').value : 'cover', titleFont: option('titleFont') ? option('titleFont').value : ''});
					if (widget.id === OBJECT_ID) widget.object = readObject();
					// Aplica no card que já está na tela: o iframe não recarrega.
					var card = Array.from(grid.children).find(function (c) { return c.dataset.widgetInstance === instance; });
					if (card) { applyOptions(card, widget); if (widget.id === OBJECT_ID) drawObject(card, widget); }
					layout(); save();
				}
				closeConfig();
			});
		}
		// ----- REQ-247: fonte do layout (próprio ou padrão do perfil), menu e layouts salvos/publicados
		var sourceButton = document.getElementById('dashboard-widgets-source'), sourceNotice = document.getElementById('dashboard-widgets-source-notice');
		function prefer(key, value) { if (canEdit) dashboardSalvarPreferenciaBackend(key, value); }
		var modeButton = document.getElementById('dashboard-widgets-mode');
		function setMode(next) {
			if (!editable() || board() === (next === 'board')) return;
			// Da lousa para a grade, a ordem passa a ser a de leitura do que está na tela.
			if (board()) { var places = arrange(widgets, boardCols(), null); widgets.sort(function (a, b) { var pa = places[a.instance_id], pb = places[b.instance_id]; return (pa.y - pb.y) || (pa.x - pb.x); }); }
			// A largura muda de unidade (12 avos na grade, células na lousa): cada widget mantém a proporção que tinha.
			// Para a lousa arredonda para baixo (quem cabia lado a lado continua cabendo); de volta, para cima.
			var cells = boardCols(), from = board() ? cells : GRID_COLS, to = board() ? GRID_COLS : cells;
			widgets.forEach(function (w) { w.width = Math.max(Math.min(MIN_COLS, to), Math.min(to, board() ? Math.ceil(w.width * to / from) : Math.floor(w.width * to / from))); });
			ownMode = next === 'board' ? 'board' : 'grid';
			prefer('dashboard_widgets_modo', ownMode === 'board' ? 'lousa' : 'grade');
			render(); commit(); syncSource();
		}
		if (modeButton) modeButton.addEventListener('click', function () { setMode(board() ? 'grid' : 'board'); });
		// Grade: atalho que põe N widgets por linha, dando a todos a mesma largura. Cada um ainda pode ser redimensionado.
		var perRow = document.getElementById('dashboard-widgets-per-row');
		if (perRow) perRow.addEventListener('click', function (e) {
			var button = e.target.closest('[data-widgets-per-row]'); if (!button || !editable() || board()) return;
			var width = Math.round(GRID_COLS / Number(button.getAttribute('data-widgets-per-row')));
			if (!(width >= MIN_COLS && width <= GRID_COLS)) return;
			widgets.forEach(function (w) { w.width = width; });
			Array.from(grid.children).forEach(function (card) { var w = widgets.find(function (x) { return x.instance_id === card.dataset.widgetInstance; }); if (w) geometry(card, w); });
			save();
		});
		var firstButton = document.getElementById('dashboard-widgets-first');
		function syncTabs() {
			var widgetsTab = document.getElementById('dashboard-tab-btn-widgets'), modulesTab = document.getElementById('dashboard-tab-btn-modulos');
			if (widgetsTab && modulesTab && widgetsTab.parentNode === modulesTab.parentNode) {
				if (widgetsFirst()) widgetsTab.parentNode.insertBefore(widgetsTab, modulesTab); else widgetsTab.parentNode.insertBefore(modulesTab, widgetsTab);
			}
			if (firstButton) { firstButton.setAttribute('aria-checked', String(widgetsFirst())); firstButton.disabled = !editable(); }
		}
		if (firstButton) firstButton.addEventListener('click', function () {
			if (!editable()) return;
			ownFirst = !ownFirst;
			prefer('dashboard_widgets_primeiro', ownFirst ? '1' : '0');
			syncTabs();
			// A aba que passou para a frente é a que se abre.
			var tab = document.getElementById(ownFirst ? 'dashboard-tab-btn-widgets' : 'dashboard-tab-btn-modulos'); if (tab) tab.click();
		});
		function syncSource() {
			syncTabs();
			if (modeButton) { modeButton.setAttribute('aria-checked', String(board())); modeButton.disabled = !editable(); }
			if (perRow) perRow.querySelectorAll('button').forEach(function (b) { b.disabled = !editable() || board(); });
			if (sourceButton) sourceButton.setAttribute('aria-checked', String(source === 'profile'));
			if (sourceNotice) sourceNotice.classList.toggle('hidden', !(canEdit && source === 'profile'));
			document.querySelectorAll('#dashboard-btn-add-widget, #dashboard-btn-add-object, #dashboard-btn-reset-widgets, #dashboard-btn-toggle-headers').forEach(function (b) { b.disabled = !editable(); });
		}
		function setSource(next) {
			if (!canEdit) return;
			if (source === 'own') ownWidgets = widgets;
			source = next === 'profile' ? 'profile' : 'own';
			widgets = source === 'own' ? ownWidgets : profileWidgets;
			prefer('dashboard_widgets_fonte', source === 'profile' ? 'perfil' : 'proprio');
			render(); syncSource();
		}
		// Passa um layout para o próprio do usuário, com instâncias novas, e volta a editar o próprio.
		function adopt(list, mode, first) {
			if (!canEdit) return;
			if (typeof first === 'boolean') { ownFirst = first; prefer('dashboard_widgets_primeiro', ownFirst ? '1' : '0'); }
			if (mode) { ownMode = mode === 'board' ? 'board' : 'grid'; prefer('dashboard_widgets_modo', ownMode === 'board' ? 'lousa' : 'grade'); }
			ownWidgets = copyLayout(list).map(function (w, index) { w.instance_id = instanceId() + '-' + index; return normalizeWidget(w, index); });
			source = 'own'; widgets = ownWidgets;
			prefer('dashboard_widgets_fonte', 'proprio');
			render(); commit(); syncSource();
		}
		if (sourceButton) sourceButton.addEventListener('click', function () { setSource(source === 'own' ? 'profile' : 'own'); });
		var copyProfile = document.getElementById('dashboard-btn-copy-profile');
		if (copyProfile) copyProfile.addEventListener('click', function () { adopt(profileWidgets, profileMode, profileFirst); });
		var headersButton = document.getElementById('dashboard-btn-toggle-headers');
		if (headersButton) headersButton.addEventListener('click', function () {
			if (!editable() || !widgets.length) return;
			// Com algum cabeçalho à mostra, esconde todos; com todos escondidos, mostra todos.
			var show = !widgets.some(function (w) { return normalizeOptions(w.options).header; });
			widgets.forEach(function (w) { w.options = normalizeOptions(w.options); w.options.header = show; });
			Array.from(grid.children).forEach(function (card) { var w = widgets.find(function (x) { return x.instance_id === card.dataset.widgetInstance; }); if (w) applyOptions(card, w); });
			save();
		});
		// REQ-248: janela cheia. A lousa cobre toda a área do navegador, por cima do menu lateral e do topo.
		var windowButton = document.getElementById('dashboard-btn-widgets-window');
		function setWindow(on) {
			var panel = document.getElementById('dashboard-tab-widgets'), tab = document.getElementById('dashboard-tab-btn-widgets');
			if (!panel) return;
			if (on && tab && panel.classList.contains('hidden')) tab.click();
			panel.classList.toggle('is-window', on);
			document.documentElement.classList.toggle('dashboard-window-open', on);
			var toggle = document.getElementById('dashboard-btn-widgets-window'); if (toggle) toggle.setAttribute('aria-checked', String(on));
			layout();
		}
		function inWindow() { var panel = document.getElementById('dashboard-tab-widgets'); return !!panel && panel.classList.contains('is-window'); }
		if (windowButton) windowButton.addEventListener('click', function () { setWindow(!inWindow()); });
		var layoutsModal = document.getElementById('dashboard-widgets-layouts-modal'), profilesData = null;
		function layoutLabel(key) { return layoutsModal.getAttribute('data-label-' + key) || ''; }
		function layoutStatus(text) { var box = document.getElementById('dashboard-widgets-layouts-status'); if (box) box.textContent = text || ''; }
		function layoutRow(name, detail) {
			var row = document.createElement('div'); row.className = 'dashboard-layout-row';
			var title = document.createElement('span'); title.className = 'dashboard-layout-name'; title.textContent = name; row.appendChild(title);
			if (detail) { var info = document.createElement('span'); info.className = 'dashboard-layout-detail'; info.textContent = detail; row.appendChild(info); }
			return row;
		}
		function layoutButton(row, label, attribute, value, danger) {
			var button = document.createElement('button'); button.type = 'button'; button.className = 'c2fc-botao' + (danger ? ' c2fc-botao-perigo' : ''); button.textContent = label; button.setAttribute(attribute, value); row.appendChild(button);
		}
		function drawSaved() {
			var box = document.getElementById('dashboard-widgets-saved-list'); if (!box) return;
			box.innerHTML = '';
			if (!savedLayouts.length) { var none = document.createElement('p'); none.className = 'dashboard-layout-empty'; none.textContent = layoutLabel('none'); box.appendChild(none); return; }
			savedLayouts.forEach(function (item) {
				var row = layoutRow(item.name, item.widgets.length + ' ' + layoutLabel('count'));
				layoutButton(row, layoutLabel('apply'), 'data-layout-apply', item.id); layoutButton(row, layoutLabel('delete'), 'data-layout-delete', item.id, true);
				box.appendChild(row);
			});
		}
		function drawProfiles(data) {
			profilesData = data;
			var box = document.getElementById('dashboard-widgets-profile-list'); if (!box) return;
			box.innerHTML = '';
			[{id: '*', nome: layoutLabel('all'), publicado: data.todos && data.todos.publicado, total: data.todos ? data.todos.total : 0}].concat(data.perfis || []).forEach(function (profile) {
				var row = layoutRow(profile.nome || profile.id, profile.publicado ? layoutLabel('published') + ' · ' + profile.total + ' ' + layoutLabel('count') : '');
				var check = document.createElement('input'); check.type = 'checkbox'; check.value = profile.id; check.className = 'dashboard-layout-check'; check.setAttribute('aria-label', profile.nome || profile.id); row.insertBefore(check, row.firstChild);
				if (profile.publicado) layoutButton(row, layoutLabel('remove'), 'data-layout-unpublish', profile.id, true);
				box.appendChild(row);
			});
		}
		function loadProfiles() { return request('widgets-layouts').then(drawProfiles).catch(function () { layoutStatus(labels.error); }); }
		// O padrão que vale para mim mudou: o do meu perfil ou, sem ele, o de todos.
		function refreshProfileLayout(published, layout, mode, first) {
			if (!profilesData) return;
			var mine = profilesData.perfil_atual, own = (profilesData.perfis || []).some(function (p) { return p.id === mine && p.publicado; });
			if (published.indexOf(mine) !== -1 || (published.indexOf('*') !== -1 && !own)) { profileWidgets = copyLayout(layout).map(normalizeWidget); profileMode = mode; profileFirst = first; if (source === 'profile') { widgets = profileWidgets; render(); syncTabs(); } }
		}
		// ----- REQ-251: lousas nomeadas (registro do sistema), duplicar e versões
		function modeLabel(mode) { return layoutLabel(mode === 'lousa' ? 'mode-board' : 'mode-grid'); }
		function drawBoards(list) {
			var box = document.getElementById('dashboard-boards-list'); if (!box) return;
			box.innerHTML = '';
			if (!list.length) { var none = document.createElement('p'); none.className = 'dashboard-layout-empty'; none.textContent = layoutLabel('board-none'); box.appendChild(none); return; }
			list.forEach(function (board) {
				var group = document.createElement('div'); group.className = 'dashboard-board'; group.setAttribute('data-board', board.id);
				var row = layoutRow(board.nome, modeLabel(board.modo) + ' · ' + board.total + ' ' + layoutLabel('board-items') + ' · ' + layoutLabel('board-version') + ' ' + board.versao);
				layoutButton(row, layoutLabel('board-open'), 'data-board-open', board.id); layoutButton(row, layoutLabel('board-update'), 'data-board-update', board.id);
				layoutButton(row, layoutLabel('board-duplicate'), 'data-board-duplicate', board.id); layoutButton(row, layoutLabel('board-versions'), 'data-board-versions', board.id);
				layoutButton(row, layoutLabel('delete'), 'data-board-delete', board.id, true);
				group.appendChild(row);
				var versions = document.createElement('div'); versions.className = 'dashboard-board-versions'; versions.hidden = true; group.appendChild(versions);
				box.appendChild(group);
			});
		}
		function loadBoards() { return request('lousas-listar').then(function (data) { drawBoards((data && data.lousas) || []); }).catch(function () { layoutStatus(labels.error); }); }
		function drawVersions(box, data) {
			box.innerHTML = ''; box.hidden = false;
			if (!(data.versoes || []).length) { var none = document.createElement('p'); none.className = 'dashboard-layout-empty'; none.textContent = layoutLabel('board-no-versions'); box.appendChild(none); return; }
			data.versoes.forEach(function (version) {
				var row = layoutRow(layoutLabel('board-version') + ' ' + version.versao, modeLabel(version.modo) + ' · ' + version.total + ' ' + layoutLabel('board-items') + ' · ' + version.data);
				var button = document.createElement('button'); button.type = 'button'; button.className = 'c2fc-botao'; button.textContent = layoutLabel('board-restore');
				button.setAttribute('data-board-restore', data.id); button.setAttribute('data-board-version', version.versao); row.appendChild(button);
				box.appendChild(row);
			});
		}
		// Ações de lousa do pop-up de layouts. Devolve `true` quando o clique era de uma delas.
		function boardAction(hit) {
			var button, done = function () { layoutStatus(layoutLabel('done')); return loadBoards(); }, fail = function () { layoutStatus(labels.error); };
			var mode = function () { return board() ? 'lousa' : 'grade'; };
			if (hit('#dashboard-board-create')) {
				var input = document.getElementById('dashboard-board-name'), name = input.value.trim().slice(0, 120);
				if (!name) { layoutStatus(layoutLabel('name')); input.focus(); return true; }
				request('lousa-salvar', {nome: name, layout: copyLayout(widgets), modo: mode()}).then(function () { input.value = ''; return done(); }).catch(fail);
				return true;
			}
			if ((button = hit('[data-board-open]'))) {
				request('lousa-obter', {id: button.getAttribute('data-board-open')}).then(function (data) { adopt(data.widgets || [], data.modo === 'lousa' ? 'board' : 'grid'); layoutStatus(layoutLabel('done')); }).catch(fail);
				return true;
			}
			if ((button = hit('[data-board-update]'))) { request('lousa-salvar', {id: button.getAttribute('data-board-update'), layout: copyLayout(widgets), modo: mode()}).then(done).catch(fail); return true; }
			if ((button = hit('[data-board-duplicate]'))) { request('lousa-duplicar', {id: button.getAttribute('data-board-duplicate')}).then(done).catch(fail); return true; }
			if ((button = hit('[data-board-delete]'))) { request('lousa-excluir', {id: button.getAttribute('data-board-delete')}).then(done).catch(fail); return true; }
			if ((button = hit('[data-board-versions]'))) {
				var box = button.closest('.dashboard-board').querySelector('.dashboard-board-versions');
				if (!box.hidden) { box.hidden = true; return true; }
				request('lousa-versoes', {id: button.getAttribute('data-board-versions')}).then(function (data) { drawVersions(box, data); }).catch(fail);
				return true;
			}
			if ((button = hit('[data-board-restore]'))) { request('lousa-restaurar', {id: button.getAttribute('data-board-restore'), versao: button.getAttribute('data-board-version')}).then(done).catch(fail); return true; }
			return false;
		}
		function openLayouts() { if (!layoutsModal || !canEdit) return; returnFocus = document.activeElement; layoutStatus(''); drawSaved(); layoutsModal.classList.remove('hidden'); loadProfiles(); loadBoards(); var name = document.getElementById('dashboard-widgets-layout-name'); if (name) name.focus(); }
		function closeLayouts() { if (!layoutsModal || layoutsModal.classList.contains('hidden')) return; layoutsModal.classList.add('hidden'); if (returnFocus && returnFocus.isConnected) returnFocus.focus(); }
		var layoutsButton = document.getElementById('dashboard-btn-layouts');
		if (layoutsButton) layoutsButton.addEventListener('click', openLayouts);
		if (layoutsModal) layoutsModal.addEventListener('click', function (e) {
			var hit = function (selector) { return e.target.closest(selector); }, button;
			if (e.target === layoutsModal || hit('.dashboard-widgets-layouts-close')) { closeLayouts(); return; }
			if (boardAction(hit)) return;
			if (hit('#dashboard-widgets-layout-save')) {
				var input = document.getElementById('dashboard-widgets-layout-name'), name = input.value.trim().slice(0, 60);
				if (!name) { layoutStatus(layoutLabel('name')); input.focus(); return; }
				// Mesmo nome substitui; no máximo 20 layouts guardados.
				savedLayouts = savedLayouts.filter(function (l) { return l.name !== name; });
				savedLayouts.unshift({id: 'layout-' + Date.now(), name: name, mode: board() ? 'board' : 'grid', first: widgetsFirst(), widgets: copyLayout(widgets)});
				savedLayouts = savedLayouts.slice(0, 20);
				prefer('dashboard_widgets_salvos', savedLayouts); input.value = ''; drawSaved(); layoutStatus(layoutLabel('done')); return;
			}
			if ((button = hit('[data-layout-apply]'))) {
				var chosen = savedLayouts.find(function (l) { return l.id === button.getAttribute('data-layout-apply'); });
				if (chosen) { adopt(chosen.widgets, chosen.mode === 'board' ? 'board' : 'grid', chosen.first === true); layoutStatus(layoutLabel('done')); }
				return;
			}
			if ((button = hit('[data-layout-delete]'))) {
				savedLayouts = savedLayouts.filter(function (l) { return l.id !== button.getAttribute('data-layout-delete'); });
				prefer('dashboard_widgets_salvos', savedLayouts); drawSaved(); return;
			}
			if (hit('#dashboard-widgets-layout-publish')) {
				var chosenProfiles = Array.from(layoutsModal.querySelectorAll('.dashboard-layout-check:checked')).map(function (c) { return c.value; });
				if (!chosenProfiles.length) { layoutStatus(layoutLabel('pick')); return; }
				var layout = copyLayout(widgets), publishedMode = board() ? 'board' : 'grid', publishedFirst = widgetsFirst();
				request('widgets-layout-publicar', {perfis: chosenProfiles, layout: layout, modo: publishedMode === 'board' ? 'lousa' : 'grade', primeiro: publishedFirst ? '1' : '0'}).then(function () { refreshProfileLayout(chosenProfiles, layout, publishedMode, publishedFirst); layoutStatus(layoutLabel('done')); return loadProfiles(); }).catch(function () { layoutStatus(labels.error); });
				return;
			}
			if ((button = hit('[data-layout-unpublish]'))) {
				request('widgets-layout-remover', {perfil: button.getAttribute('data-layout-unpublish')}).then(function () { layoutStatus(layoutLabel('done')); return loadProfiles(); }).catch(function () { layoutStatus(labels.error); });
			}
		});
		syncSource();
		// ----- REQ-250: novo objeto e seletor de imagem
		var objectButton = document.getElementById('dashboard-btn-add-object');
		if (objectButton) objectButton.addEventListener('click', function () {
			if (!editable()) return;
			// Nasce como texto, sem cabeçalho nem moldura, e já abre nas configurações para escolher o tipo.
			var created = normalizeWidget({id: OBJECT_ID, name: labels.object, instance_id: instanceId(), width: 4, height: 1, height_px: 160, object: {type: 'text'}, options: {header: false, frame: false}}, widgets.length);
			widgets.push(created); render(); commit();
			if (!editing) setEditing(true);
			openConfig(created.instance_id);
		});
		var pickerModal = document.getElementById('dashboard-image-picker'), pickerTarget = null;
		function pickerPath(file) {
			var candidates = [file.caminho ? '/' + String(file.caminho).replace(/^\/+/, '') : '', file.imgSrc || ''];
			for (var i = 0; i < candidates.length; i++) {
				try { var url = new URL(candidates[i], location.origin); if (url.origin === location.origin && safeImage(url.pathname)) return url.pathname; } catch (_) {}
			}
			return '';
		}
		function openPicker(input) {
			if (!pickerModal || !input) return;
			pickerTarget = input;
			pickerModal.querySelector('iframe').src = ((typeof gestor !== 'undefined' && gestor.raiz) || '/') + 'admin-arquivos/?paginaIframe=sim';
			pickerModal.classList.remove('hidden');
		}
		function closePicker() {
			if (!pickerModal || pickerModal.classList.contains('hidden')) return;
			pickerModal.classList.add('hidden'); pickerModal.querySelector('iframe').removeAttribute('src'); pickerTarget = null;
		}
		if (pickerModal) {
			pickerModal.addEventListener('click', function (e) { if (e.target === pickerModal || e.target.closest('.dashboard-image-picker-close')) closePicker(); });
			// O gerenciador de arquivos avisa por mensagem; só vale a que vem do próprio painel, com o seletor aberto.
			window.addEventListener('message', function (e) {
				if (!pickerTarget || !pickerTarget.isConnected || e.origin !== location.origin) return;
				var data, file;
				try { data = JSON.parse(e.data); } catch (_) { return; }
				if (!data) return;
				if (data.moduloId === 'admin-arquivos-seletor') { if (data.acao === 'concluir' || data.acao === 'cancelar') closePicker(); return; }
				if (data.moduloId !== 'admin-arquivos' && data.moduloId !== 'arquivos') return;
				try { file = JSON.parse(decodeURI(data.data)); } catch (_) { return; }
				var path = file && /^image\//.test(file.tipo || '') ? pickerPath(file) : '';
				var note = pickerModal.querySelector('[data-picker-status]');
				if (!path) { if (note) note.textContent = labels['not-image']; return; }
				pickerTarget.value = path; closePicker();
			});
		}
		var resetButton=document.getElementById('dashboard-btn-reset-widgets');
		if(resetButton)resetButton.addEventListener('click',function(){if(!editable())return;widgets=[];save();render();});
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
			var cols=boardCols(),step=(resize.gridWidth+GAP)/cols;
			resize.cols=board()?Math.max(Math.min(MIN_COLS,cols),Math.min(cols,Math.round((resize.width+dx+GAP)/step))):Math.max(MIN_COLS,Math.min(GRID_COLS,Math.round(((resize.width+dx)/resize.gridWidth)*GRID_COLS)));resize.rows=Math.max(MIN_HEIGHT,Math.min(MAX_HEIGHT,Math.round((resize.height+dy)/STEP_HEIGHT)*STEP_HEIGHT));
			geometry(resize.card,{width:resize.cols,height_px:resize.rows});
			if(board()){resize.card.style.gridColumnEnd='span '+resize.cols;resize.card.style.gridRowEnd='span '+boardRows({height_px:resize.rows});}
			resize.card.setAttribute('data-resize-size', resize.cols + ' × ' + resize.rows + ' px');
		});
		function finish(e){if(!resize || resize.pointer!==e.pointerId)return;resize.card.classList.remove('is-resizing');resize.card.removeAttribute('data-resize-size');grid.classList.remove('is-interacting');if(resize.handle.hasPointerCapture(e.pointerId))resize.handle.releasePointerCapture(e.pointerId);var done=resize;resize=null;if(e.type==='pointercancel'||!done.cols){geometry(done.card,done.widget);layout();}else{done.widget.width=done.cols;done.widget.height_px=done.rows;commit(done.widget);}}
		grid.addEventListener('pointerup',finish);grid.addEventListener('pointercancel',finish);
		// REQ-248: arrastar pela alça solta o widget em qualquer célula; a sombra tracejada mostra onde ele cai.
		var drag = null;
		grid.addEventListener('pointerdown', function (e) {
			var handle = e.target.closest('.dashboard-widget-drag-handle'); if (!editing || !board() || !handle || drag) return;
			var card = handle.closest('.dashboard-widget-card'), widget = widgets.find(function (w) { return w.instance_id === card.dataset.widgetInstance; });
			if (!widget) return;
			var rect = card.getBoundingClientRect(), ghost = document.createElement('div');
			ghost.className = 'dashboard-board-ghost'; ghost.style.gridColumn = card.style.gridColumn; ghost.style.gridRow = card.style.gridRow; grid.appendChild(ghost);
			drag = {card: card, widget: widget, handle: handle, pointer: e.pointerId, ghost: ghost, startX: e.clientX, startY: e.clientY, offsetX: e.clientX - rect.left, offsetY: e.clientY - rect.top, x: Number(card.getAttribute('data-board-x')) || 0, y: Number(card.getAttribute('data-board-y')) || 0, moved: false};
			card.classList.add('is-dragging'); grid.classList.add('is-interacting');
			if (handle.setPointerCapture) handle.setPointerCapture(e.pointerId);
			e.preventDefault();
		});
		grid.addEventListener('pointermove', function (e) {
			if (!drag || drag.pointer !== e.pointerId) return;
			var box = grid.getBoundingClientRect(), cols = boardCols(), span = Math.max(1, Math.min(drag.widget.width, cols)), step = ((box.width || 1) + GAP) / cols;
			drag.moved = true;
			drag.card.style.transform = 'translate(' + (e.clientX - drag.startX) + 'px,' + (e.clientY - drag.startY) + 'px)';
			drag.x = Math.max(0, Math.min(cols - span, Math.round((e.clientX - drag.offsetX - box.left) / step)));
			drag.y = Math.max(0, Math.min(MAX_ROW, Math.round((e.clientY - drag.offsetY - box.top) / ROW)));
			drag.ghost.style.gridColumn = (drag.x + 1) + ' / span ' + span; drag.ghost.style.gridRow = (drag.y + 1) + ' / span ' + boardRows(drag.widget);
		});
		function drop(e) {
			if (!drag || drag.pointer !== e.pointerId) return;
			var done = drag; drag = null;
			done.ghost.remove(); done.card.style.transform = ''; done.card.classList.remove('is-dragging'); grid.classList.remove('is-interacting');
			if (done.handle.hasPointerCapture && done.handle.hasPointerCapture(e.pointerId)) done.handle.releasePointerCapture(e.pointerId);
			if (e.type === 'pointercancel' || !done.moved) { layout(); return; }
			done.widget.x = done.x; done.widget.y = done.y;
			commit(done.widget);
		}
		grid.addEventListener('pointerup', drop); grid.addEventListener('pointercancel', drop);
		// A lousa acompanha a largura: recolher o menu lateral ou estreitar a janela refaz o arranjo, sem gravar.
		if (typeof ResizeObserver !== 'undefined') new ResizeObserver(function () { if (!drag && !resize) layout(); }).observe(grid);
		else if (typeof window !== 'undefined') window.addEventListener('resize', function () { if (!drag && !resize) layout(); });
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
