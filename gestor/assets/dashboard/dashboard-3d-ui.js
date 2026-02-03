/**
 * Dashboard 3D - UI e Interações
 * Gerencia interface, eventos, modais, tooltips e menu pizza
 */

(function (global) {
    'use strict';

    const Dashboard3DUI = {
        // Estado
        selectedCard: null,
        mousePosition: { x: 0, y: 0 },
        modulesData: [],
        groupsData: [],
        initialIframeUrl: null,

        /**
         * Inicializa com os dados
         * @param {Array} modules - Dados dos módulos
         * @param {Array} groups - Dados dos grupos
         */
        init: function (modules, groups) {
            this.modulesData = modules;
            this.groupsData = groups;
        },

        /**
         * Configura todos os event listeners
         */
        setupEventListeners: function () {
            const self = this;
            const Camera = global.Dashboard3DCamera;
            const CONFIG = global.Dashboard3DConfig;

            // Cards - aguardar um pouco para garantir que foram criados
            setTimeout(function () {
                document.querySelectorAll('.module-card').forEach(function (card) {
                    card.addEventListener('mouseenter', function (e) { self.onCardHover(e); });
                    card.addEventListener('mouseleave', function (e) { self.onCardLeave(e); });
                    card.addEventListener('click', function (e) { self.onCardClick(e); });
                });
            }, 100);

            // Controles de zoom
            var btnZoomIn = document.getElementById('btn-zoom-in');
            if (btnZoomIn) {
                btnZoomIn.addEventListener('click', function () {
                    Camera.zoomCamera(CONFIG.camera.zoomStep);
                });
            }

            var btnZoomOut = document.getElementById('btn-zoom-out');
            if (btnZoomOut) {
                btnZoomOut.addEventListener('click', function () {
                    Camera.zoomCamera(-CONFIG.camera.zoomStep);
                });
            }

            var btnResetView = document.getElementById('btn-reset-view');
            if (btnResetView) {
                btnResetView.addEventListener('click', function () {
                    Camera.resetView();
                    self.closeActionModal();
                });
            }

            var btnFullscreen = document.getElementById('btn-fullscreen');
            if (btnFullscreen) {
                btnFullscreen.addEventListener('click', function () {
                    Camera.toggleFullscreen();
                });
            }

            var btnToggleRotation = document.getElementById('btn-toggle-rotation');
            if (btnToggleRotation) {
                btnToggleRotation.addEventListener('click', function () {
                    Camera.toggleAutoRotation();
                });
            }

            // Pizza Menu
            var btnMenuPizza = document.getElementById('btn-menu-pizza');
            if (btnMenuPizza) {
                btnMenuPizza.addEventListener('click', function () {
                    self.togglePizzaMenu();
                });
            }

            var btnPizzaClose = document.getElementById('btn-pizza-close');
            if (btnPizzaClose) {
                btnPizzaClose.addEventListener('click', function () {
                    self.closePizzaMenu();
                });
            }

            var pizzaBackdrop = document.querySelector('.pizza-backdrop');
            if (pizzaBackdrop) {
                pizzaBackdrop.addEventListener('click', function () {
                    self.closePizzaMenu();
                });
            }

            // Toggle Groups Legend
            var btnToggleGroups = document.getElementById('btn-toggle-groups');
            if (btnToggleGroups) {
                btnToggleGroups.addEventListener('click', function () {
                    self.toggleGroupsLegend();
                });
            }

            // Toggle Shortcuts
            var btnToggleShortcuts = document.getElementById('btn-toggle-shortcuts');
            if (btnToggleShortcuts) {
                btnToggleShortcuts.addEventListener('click', function () {
                    self.toggleShortcuts();
                });
            }

            // Modal
            var btnCloseModal = document.getElementById('btn-close-modal');
            if (btnCloseModal) {
                btnCloseModal.addEventListener('click', function () {
                    self.closeActionModal();
                });
            }

            var modalBackdrop = document.querySelector('.modal-backdrop');
            if (modalBackdrop) {
                modalBackdrop.addEventListener('click', function () {
                    self.closeActionModal();
                });
            }

            document.querySelectorAll('.modal-btn[data-action]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    self.executeAction(e.currentTarget.dataset.action);
                });
            });

            // Module container
            var btnModuleBack = document.getElementById('btn-module-back');
            if (btnModuleBack) {
                btnModuleBack.addEventListener('click', function () {
                    self.closeModuleView();
                });
            }

            var btnModuleExpand = document.getElementById('btn-module-expand');
            if (btnModuleExpand) {
                btnModuleExpand.addEventListener('click', function () {
                    self.expandModuleInNewTab();
                });
            }

            // Keyboard
            document.addEventListener('keydown', function (e) { self.onKeyDown(e); });

            // Mouse tracking para tooltip
            document.addEventListener('mousemove', function (e) {
                self.mousePosition.x = e.clientX;
                self.mousePosition.y = e.clientY;

                var tooltip = document.getElementById('card-tooltip');
                if (tooltip && !tooltip.classList.contains('hidden')) {
                    tooltip.style.left = Math.min(e.clientX + 15, window.innerWidth - 300) + 'px';
                    tooltip.style.top = Math.min(e.clientY + 15, window.innerHeight - 150) + 'px';
                }
            });

            // Botão voltar do módulo
            // Listener para mensagens do iframe
            window.addEventListener('message', function (event) {
                if (event.data === 'back-from-iframe') {
                    self.closeModuleView();
                }
            });

            console.log('Dashboard3DUI: Event listeners configurados');
        },

        /**
         * Cria a legenda de grupos
         */
        createLegend: function () {
            const legendItems = document.getElementById('legend-items');
            const CONFIG = global.Dashboard3DConfig;
            const Camera = global.Dashboard3DCamera;
            const self = this;

            if (!legendItems) return;

            legendItems.innerHTML = '';
            const segmentCount = Math.min(this.groupsData.length, CONFIG.segmentColors.length);

            this.groupsData.slice(0, segmentCount).forEach(function (group, index) {
                var color = CONFIG.segmentColors[index];
                var moduleCount = self.modulesData.filter(function (m) {
                    return m.grupo === group.id;
                }).length;

                var item = document.createElement('div');
                item.className = 'legend-item';
                item.setAttribute('data-group', group.id);
                item.innerHTML =
                    '<div class="legend-color" style="background: ' + color + '"></div>' +
                    '<span class="legend-name">' + group.nome + '</span>' +
                    '<span class="legend-count">' + moduleCount + '</span>';

                item.addEventListener('click', function () {
                    Camera.focusOnGroup(group.id, index, self.groupsData);
                });
                legendItems.appendChild(item);
            });

            console.log('Dashboard3DUI: Legenda criada');
        },

        // =========================================
        // Card Interactions
        // =========================================

        onCardHover: function (event) {
            const CONFIG = global.Dashboard3DConfig;
            var card = event.target.closest('.module-card') || event.target;
            if (!card || !card.classList.contains('module-card')) return;

            card.setAttribute('animation__hover', {
                property: 'scale',
                to: CONFIG.cards.hoverScale + ' ' + CONFIG.cards.hoverScale + ' ' + CONFIG.cards.hoverScale,
                dur: 200,
                easing: 'easeOutQuad'
            });

            this.showTooltip(event, card);
        },

        onCardLeave: function (event) {
            var card = event.target.closest('.module-card') || event.target;
            if (!card || !card.classList.contains('module-card') || card === this.selectedCard) return;

            card.setAttribute('animation__hover', {
                property: 'scale',
                to: '1 1 1',
                dur: 200,
                easing: 'easeOutQuad'
            });

            this.hideTooltip();
        },

        onCardClick: function (event) {
            const Camera = global.Dashboard3DCamera;
            var card = event.target.closest('.module-card') || event.target;
            if (!card || !card.classList.contains('module-card')) return;

            this.selectedCard = card;
            this.hideTooltip();
            Camera.zoomToCard(card);
            this.showActionModal(card);
        },

        // =========================================
        // Tooltip
        // =========================================

        showTooltip: function (event, card) {
            var tooltip = document.getElementById('card-tooltip');
            if (!tooltip) return;

            var name = card.getAttribute('data-module-name') || '';
            var group = card.getAttribute('data-group-name') || '';
            var description = card.getAttribute('data-module-description') || '';
            var icon = global.Dashboard3DCards.getModuleIcon(card.getAttribute('data-module-icon'));

            var tooltipIcon = tooltip.querySelector('.tooltip-icon');
            var tooltipTitle = tooltip.querySelector('.tooltip-title');
            var tooltipGroup = tooltip.querySelector('.tooltip-group');
            var tooltipDesc = tooltip.querySelector('.tooltip-description');

            if (tooltipIcon) tooltipIcon.textContent = icon;
            if (tooltipTitle) tooltipTitle.textContent = name;
            if (tooltipGroup) tooltipGroup.textContent = group;
            if (tooltipDesc) tooltipDesc.textContent = description;

            var x = event.clientX || this.mousePosition.x || 100;
            var y = event.clientY || this.mousePosition.y || 100;

            tooltip.style.left = Math.min(x + 15, window.innerWidth - 300) + 'px';
            tooltip.style.top = Math.min(y + 15, window.innerHeight - 150) + 'px';

            tooltip.classList.remove('hidden');
        },

        hideTooltip: function () {
            var tooltip = document.getElementById('card-tooltip');
            if (tooltip) tooltip.classList.add('hidden');
        },

        // =========================================
        // Modal de Ações
        // =========================================

        showActionModal: function (card) {
            var modal = document.getElementById('module-action-modal');
            if (!modal) return;

            var i18n = document.getElementById('i18n-texts').dataset;

            var name = card.getAttribute('data-module-name') || '';
            var description = card.getAttribute('data-module-description') || '';
            var icon = global.Dashboard3DCards.getModuleIcon(card.getAttribute('data-module-icon'));
            var color = card.getAttribute('data-group-color') || '#4a9eff';

            var modalTitle = document.getElementById('modal-title');
            var modalDesc = document.getElementById('modal-description');
            var modalIcon = document.querySelector('.modal-icon');

            if (modalTitle) modalTitle.textContent = name;
            if (modalDesc) modalDesc.textContent = description;
            if (modalIcon) {
                modalIcon.textContent = icon;
                modalIcon.style.background = color;
            }

            // Ativa/desativa botão Adicionar conforme atributo do módulo
            document.querySelectorAll('.modal-btn[data-action]').forEach(function (btn) {
                if (btn.dataset.action === 'adicionar') {
                    var canAdd = card.getAttribute('data-module-add') === 'true';
                    if (canAdd) {
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                    return;
                }
            });

            var customActions = document.getElementById('custom-actions');
            if (customActions) {
                customActions.innerHTML = '';

                var moduleData = this.modulesData.find(function (m) {
                    return m.id === card.getAttribute('data-module-id');
                });

                if (moduleData && moduleData.acoes) {
                    var self = this;
                    moduleData.acoes.forEach(function (acao) {
                        var btn = document.createElement('button');
                        btn.className = 'modal-btn modal-btn-custom';
                        btn.textContent = acao.label;
                        btn.addEventListener('click', function () {
                            self.openModuleView(acao.link, name);
                        });
                        customActions.appendChild(btn);
                    });
                }

                // Adicionar botões Doc e Manual
                var docBtn = document.createElement('button');
                docBtn.className = 'modal-btn modal-btn-doc';
                docBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                        <span>${i18n.docText}</span>
                    `;
                docBtn.addEventListener('click', function () {
                    var docLink = card.getAttribute('data-module-doc');
                    if (docLink) {
                        window.open(docLink, '_blank');
                    } else {
                        alert(i18n.docUnavailable);
                    }
                });
                customActions.appendChild(docBtn);

                var manualBtn = document.createElement('button');
                manualBtn.className = 'modal-btn modal-btn-manual';
                manualBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        <span>${i18n.manualText}</span>
                    `;
                manualBtn.addEventListener('click', function () {
                    var manualLink = card.getAttribute('data-module-manual');
                    if (manualLink) {
                        window.open(manualLink, '_blank');
                    } else {
                        alert(i18n.manualUnavailable);
                    }
                });
                customActions.appendChild(manualBtn);
            }

            modal.classList.remove('hidden');
        },

        closeActionModal: function () {
            var modal = document.getElementById('module-action-modal');
            if (modal) modal.classList.add('hidden');
            this.selectedCard = null;

            // Restaurar controles de câmera após fechar o modal
            const Camera = global.Dashboard3DCamera;
            if (Camera && Camera.restoreControls) {
                // Pequeno delay para garantir que o modal fechou
                setTimeout(function () {
                    Camera.restoreControls();
                }, 50);
            }
        },

        executeAction: function (action) {
            if (!this.selectedCard) return;

            var link = this.selectedCard.getAttribute('data-module-link') || '#';
            var name = this.selectedCard.getAttribute('data-module-name') || '';

            var url = link;
            if (action === 'adicionar') {
                var add = this.selectedCard.getAttribute('data-module-add') || '';
                if (add == 'true') {
                    url = link + (link.endsWith('/') ? '' : '/') + 'adicionar/';
                }
            }

            this.closeActionModal();
            this.openModuleView(url, name);
        },

        // =========================================
        // Module View
        // =========================================

        openModuleView: function (url, title) {
            var container = document.getElementById('module-container');
            var iframe = document.getElementById('module-iframe');
            var moduleTitle = document.getElementById('module-title');

            if (!container || !iframe) return;

            iframe.src = url;
            this.initialIframeUrl = url;

            if (moduleTitle) moduleTitle.textContent = title;
            container.classList.remove('hidden');
            container.setAttribute('data-current-url', url);

            // Injetar script no iframe após carregamento
            iframe.onload = function () {
                try {
                    // Enviar URL inicial para o iframe
                    iframe.contentWindow.postMessage({ type: 'init', initialUrl: self.initialIframeUrl }, '*');

                    // Injetar script apenas se não foi injetado antes
                    if (!iframe.contentWindow.dashboardBackListenerInjected) {
                        iframe.contentWindow.dashboardBackListenerInjected = true;

                        var script = iframe.contentDocument.createElement('script');
                        script.textContent = `
                window.addEventListener('message', function (event) {
                    if (event.data.type === 'init') {
                        var initialUrl = event.data.initialUrl;
                        document.addEventListener('mousedown', function (e) {
                            if (e.button === 3 && window.location.href === initialUrl) {
                                window.parent.postMessage('back-from-iframe', '*');
                                e.preventDefault();
                            }
                        });
                    }
                });
            `;
                        iframe.contentDocument.head.appendChild(script);
                    }
                } catch (e) {
                    console.warn('Não foi possível injetar script no iframe (possivelmente CORS):', e);
                }
            };
        },

        closeModuleView: function () {
            var container = document.getElementById('module-container');
            var iframe = document.getElementById('module-iframe');

            if (container) container.classList.add('hidden');
            if (iframe) iframe.src = '';
        },

        expandModuleInNewTab: function () {
            var container = document.getElementById('module-container');
            var url = container ? container.getAttribute('data-current-url') : null;
            if (url) window.open(url, '_blank');
        },

        // =========================================
        // Pizza Menu
        // =========================================

        togglePizzaMenu: function () {
            var menu = document.getElementById('pizza-menu');
            if (menu) {
                if (menu.classList.contains('hidden')) {
                    this.createPizzaItems();
                    menu.classList.remove('hidden');
                } else {
                    this.closePizzaMenu();
                }
            }
        },

        closePizzaMenu: function () {
            var menu = document.getElementById('pizza-menu');
            if (menu) menu.classList.add('hidden');
        },

        createPizzaItems: function () {
            var container = document.querySelector('.pizza-items');
            if (!container) return;

            var baseUrl = (typeof gestor !== 'undefined' && gestor.raiz)
                ? gestor.raiz
                : '/';

            var lang = (typeof gestor !== 'undefined' && gestor.language)
                ? gestor.language
                : 'pt-br';

            var menuItems = [
                { icon: '🏠', label: 'Dashboard 2D', link: baseUrl + 'dashboard/' },
                { icon: '👤', label: 'Meu Perfil', link: baseUrl + 'perfil-usuario/' },
                { icon: '⚙️', label: 'Configurações', link: baseUrl + 'admin-environment/' },
                { icon: '❓', label: 'Ajuda', link: 'https://github.com/otavioserra/conn2flow/tree/main/ai-workspace/' + lang + '/docs' },
                { icon: '🔍', label: 'Buscar', action: 'search' },
                {
                    icon: '📤', label: 'Sair', link: (typeof gestor !== 'undefined' && gestor.raiz)
                        ? gestor.raiz + 'signout/'
                        : '/signout/'
                }
            ];

            container.innerHTML = '';
            var radius = 120;
            var angleStep = (Math.PI * 2) / menuItems.length;

            menuItems.forEach(function (item, index) {
                var angle = angleStep * index - Math.PI / 2;
                var x = Math.cos(angle) * radius;
                var y = Math.sin(angle) * radius;

                var el = document.createElement('button');
                el.className = 'pizza-item';
                el.style.setProperty('--x', x + 'px');
                el.style.setProperty('--y', y + 'px');
                el.innerHTML = '<span class="pizza-icon">' + item.icon + '</span>' +
                    '<span class="pizza-label">' + item.label + '</span>';

                el.addEventListener('click', function () {
                    var menu = document.getElementById('pizza-menu');
                    if (menu) menu.classList.add('hidden');
                    if (item.link) window.location.href = item.link;
                });

                container.appendChild(el);
            });
        },

        // =========================================
        // Toggle UI Elements
        // =========================================

        toggleGroupsLegend: function () {
            var legend = document.getElementById('groups-legend');
            var btn = document.getElementById('btn-toggle-groups');

            if (legend) {
                legend.classList.toggle('hidden');
                if (btn) btn.classList.toggle('active', !legend.classList.contains('hidden'));
            }
        },

        toggleShortcuts: function () {
            var shortcuts = document.getElementById('keyboard-shortcuts');
            var btn = document.getElementById('btn-toggle-shortcuts');

            if (shortcuts) {
                shortcuts.classList.toggle('hidden');
                if (btn) btn.classList.toggle('active', !shortcuts.classList.contains('hidden'));
            }
        },

        // =========================================
        // Keyboard
        // =========================================

        onKeyDown: function (event) {
            const Camera = global.Dashboard3DCamera;
            const Geometry = global.Dashboard3DGeometry;
            const CONFIG = global.Dashboard3DConfig;
            const self = this;

            // Ignorar se estiver em input
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                return;
            }

            switch (event.key) {
                case 'Escape':
                    this.closeActionModal();
                    this.closePizzaMenu();
                    this.closeModuleView();
                    break;
                case 'r':
                case 'R':
                    if (!event.ctrlKey && !event.metaKey) {
                        Camera.resetView();
                        this.closeActionModal();
                    }
                    break;
                case 'p':
                case 'P':
                    // Toggle debug prismas
                    if (Geometry && Geometry.toggleDebugPrisms) {
                        var isActive = Geometry.toggleDebugPrisms(self.groupsData);
                        console.log('Debug prismas:', isActive ? 'ativado' : 'desativado');
                    }
                    break;
                case '+':
                case '=':
                    Camera.zoomCamera(CONFIG.camera.zoomStep);
                    break;
                case '-':
                case '_':
                    Camera.zoomCamera(-CONFIG.camera.zoomStep);
                    break;
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                    var groupIndex = parseInt(event.key) - 1;
                    if (groupIndex < self.groupsData.length) {
                        Camera.focusOnGroup(self.groupsData[groupIndex].id, groupIndex, self.groupsData);
                    }
                    break;
            }
        },

        // =========================================
        // Loading
        // =========================================

        hideLoading: function () {
            var overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(function () {
                    overlay.classList.add('hidden');
                }, 500);
            }
        }
    };

    // Exportar para o namespace global
    global.Dashboard3DUI = Dashboard3DUI;

})(window);
