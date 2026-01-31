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
		 * Salva a ordem dos cards no localStorage
		 * @param {Array} order Array com IDs dos módulos
		 */
		function saveOrder(order) {
			setLocalStorage(storageKey, order, storageExpireMinutes);
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

});
