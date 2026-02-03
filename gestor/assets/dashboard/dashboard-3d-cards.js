/**
 * Dashboard 3D - Cards
 * Cria e gerencia os cards dos módulos
 */

(function (global) {
    'use strict';

    const Dashboard3DCards = {

        /**
         * Normaliza texto removendo caracteres problemáticos
         * e garantindo compatibilidade com fontes SDF
         * @param {string} text - Texto original
         * @returns {string} Texto normalizado
         */
        normalizeText: function (text) {
            if (!text) return '';

            // Mapa de substituição para caracteres acentuados
            // A fonte roboto (MSDF) do A-Frame suporta caracteres latinos básicos
            // mas alguns podem não renderizar corretamente
            return text
                .normalize('NFD')  // Normaliza para forma decomposta
                .replace(/[\u0300-\u036f]/g, ''); // Remove diacríticos (acentos)
        },

        /**
         * Preserva acentos se a fonte suportar (troika-text suporta)
         * @param {string} text - Texto original
         * @returns {string} Texto com acentos preservados
         */
        preserveAccents: function (text) {
            if (!text) return '';
            // troika-text suporta UTF-8 completo com acentos
            return String(text);
        },

        /**
         * Trunca texto para um tamanho máximo (usando config)
         * @param {string} text - Texto original
         * @param {number} maxLength - Tamanho máximo (opcional, usa config se não fornecido)
         * @returns {string} Texto truncado
         */
        truncateText: function (text, maxLength) {
            if (!text) return '';
            const CONFIG = global.Dashboard3DConfig;
            const limit = maxLength || (CONFIG.cards.text ? CONFIG.cards.text.descriptionMaxLength : 50);
            return text.length > limit ? text.substring(0, limit) + '...' : text;
        },

        /**
         * Formata texto para título com limite de caracteres
         * @param {string} text - Texto original
         * @returns {string} Texto formatado para título
         */
        formatTitle: function (text) {
            if (!text) return '';
            const CONFIG = global.Dashboard3DConfig;
            const maxLength = CONFIG.cards.text ? CONFIG.cards.text.titleMaxLength : 20;
            return this.truncateText(text, maxLength);
        },

        /**
         * Formata texto para descrição com limite de caracteres
         * @param {string} text - Texto original
         * @returns {string} Texto formatado para descrição
         */
        formatDescription: function (text) {
            if (!text) return '';
            const CONFIG = global.Dashboard3DConfig;
            const maxLength = CONFIG.cards.text ? CONFIG.cards.text.descriptionMaxLength : 50;
            return this.truncateText(text, maxLength);
        },

        /**
         * Calcula a posição de um card dentro de um plano (lousa) no volume prismático 3D
         * O plano é centralizado no prisma e sempre fica de frente para o observador
         * Os cards são distribuídos em grid dentro do plano
         * 
         * @param {number} moduleIndex - Índice do módulo no grupo
         * @param {number} moduleCount - Total de módulos no grupo
         * @param {number} groupCenterAngle - Ângulo central do grupo
         * @param {number} ringRadius - Raio do anel
         * @param {Object} CONFIG - Configurações
         * @returns {Object} Posição {x, y, z}
         */
        calculatePrismPosition: function (moduleIndex, moduleCount, groupCenterAngle, ringRadius, CONFIG) {
            const prism = CONFIG.prism;
            const prismLength = prism.getLength ? prism.getLength(6) : prism.length;
            const baseWidth = prism.getBaseWidth ? prism.getBaseWidth(6) : prism.baseWidth;

            // Dimensões do card para calcular espaçamento
            const cardWidth = CONFIG.cards.width + prism.cardSpacing;
            const cardHeight = CONFIG.cards.height + prism.cardSpacing;

            // Calcular grid de distribuição
            const horizontalCards = prism.horizontalCards || 4;
            const verticalLayers = prism.verticalLayers || 4;

            // Posição no grid
            const col = moduleIndex % horizontalCards;
            const row = Math.floor(moduleIndex / horizontalCards);

            // Centralizar o grid
            const totalCols = Math.min(horizontalCards, moduleCount);
            const totalRows = Math.ceil(moduleCount / horizontalCards);

            // Offset para centralizar horizontalmente
            const colOffset = (col - (totalCols - 1) / 2) * cardWidth;

            // Offset para centralizar verticalmente
            const rowOffset = (row - (totalRows - 1) / 2) * cardHeight;

            // Altura base do plano (centralizado entre heightMin e heightMax)
            const heightMin = CONFIG.ring.height + prism.heightMin;
            const heightMax = CONFIG.ring.height + prism.heightMax;
            const centerHeight = (heightMin + heightMax) / 2;
            const cardY = centerHeight + rowOffset;

            // Distância do centro (meio do prisma)
            const centerDistance = ringRadius + (prismLength / 2) - CONFIG.cards.depthMarginBoard;

            // Calcular posição base no centro do prisma
            const baseX = Math.cos(groupCenterAngle) * centerDistance;
            const baseZ = Math.sin(groupCenterAngle) * centerDistance;

            // O plano está orientado tangencialmente ao anel
            // Calcular offset lateral perpendicular ao raio
            const perpAngle = groupCenterAngle + Math.PI / 2;
            const offsetX = Math.cos(perpAngle) * colOffset;
            const offsetZ = Math.sin(perpAngle) * colOffset;

            // Pequeno ruído para naturalidade
            const noise = CONFIG.cards.noiseAmount * 0.1;
            const noiseX = (Math.random() - 0.5) * noise;
            const noiseY = (Math.random() - 0.5) * noise;
            const noiseZ = (Math.random() - 0.5) * noise;

            return {
                x: baseX + offsetX + noiseX,
                y: cardY + noiseY,
                z: baseZ + offsetZ + noiseZ
            };
        },

        /**
         * Calcula posição legada (sem prismas) - fallback
         */
        calculateLegacyPosition: function (moduleIndex, moduleCount, groupCenterAngle, cardDistance, cardSpread, CONFIG) {
            const cardAngle = groupCenterAngle - (cardSpread / 2) + (cardSpread * (moduleIndex + 0.5) / Math.max(moduleCount, 1));

            const noise = CONFIG.cards.noiseAmount;
            const noiseX = (Math.random() - 0.5) * noise;
            const noiseY = (Math.random() - 0.5) * noise * 0.5;
            const noiseZ = (Math.random() - 0.5) * noise;

            return {
                x: Math.cos(cardAngle) * cardDistance + noiseX,
                y: CONFIG.ring.height + noiseY + (moduleIndex % 2) * 0.3,
                z: Math.sin(cardAngle) * cardDistance + noiseZ
            };
        },

        /**
         * Obtém o emoji do ícone do módulo
         * @param {string} iconName - Nome do ícone
         * @returns {string} Emoji correspondente
         */
        getModuleIcon: function (iconName) {
            const CONFIG = global.Dashboard3DConfig;
            return CONFIG.icons[iconName] || CONFIG.icons['default'];
        },

        /**
         * Cria os cards dos módulos
         * @param {Array} modulesData - Dados dos módulos
         * @param {Array} groupsData - Dados dos grupos
         */
        createCards: function (modulesData, groupsData) {
            const container = document.getElementById('cards-container');
            const CONFIG = global.Dashboard3DConfig;
            const self = this;

            if (!container) return;

            // Agrupar módulos por grupo
            const modulesByGroup = {};
            modulesData.forEach(function (module) {
                var groupId = module.grupo || 'outros';
                if (!modulesByGroup[groupId]) modulesByGroup[groupId] = [];
                modulesByGroup[groupId].push(module);
            });

            const segmentCount = Math.min(groupsData.length, CONFIG.segmentColors.length);
            const anglePerSegment = (Math.PI * 2) / segmentCount;

            // Raio e distância dinâmicos baseados em quantidade de grupos
            const ringRadius = CONFIG.ring.getRadius ?
                CONFIG.ring.getRadius(groupsData.length) :
                (CONFIG.ring.baseRadius || CONFIG.ring.radius || 6);

            const cardDistanceFromRing = CONFIG.cards.getDistanceFromRing ?
                CONFIG.cards.getDistanceFromRing(groupsData.length) :
                (CONFIG.cards.baseDistanceFromRing || CONFIG.cards.distanceFromRing || 4);

            groupsData.forEach(function (group, groupIndex) {
                if (groupIndex >= segmentCount) return;

                var modules = modulesByGroup[group.id] || [];
                var color = CONFIG.segmentColors[groupIndex];
                var groupCenterAngle = (groupIndex * anglePerSegment) - (Math.PI / 2) + (anglePerSegment / 2);

                // Usar valores dinâmicos calculados
                var cardDistance = ringRadius + cardDistanceFromRing;
                var cardSpread = anglePerSegment * 0.7;

                // Verificar se usa distribuição prismática
                var usePrism = CONFIG.prism && CONFIG.prism.enabled;

                modules.forEach(function (module, moduleIndex) {
                    var moduleCount = modules.length;
                    var cardPos;

                    if (usePrism) {
                        // Usar distribuição prismática
                        cardPos = self.calculatePrismPosition(
                            moduleIndex,
                            moduleCount,
                            groupCenterAngle,
                            ringRadius,
                            CONFIG
                        );
                    } else {
                        // Usar distribuição legada
                        cardPos = self.calculateLegacyPosition(
                            moduleIndex,
                            moduleCount,
                            groupCenterAngle,
                            cardDistance,
                            cardSpread,
                            CONFIG
                        );
                    }

                    var card = document.createElement('a-entity');
                    card.setAttribute('class', 'module-card clickable');
                    card.setAttribute('position', cardPos.x + ' ' + cardPos.y + ' ' + cardPos.z);
                    // Billboard: usar componente customizado mais robusto
                    card.setAttribute('billboard-camera', '');

                    // Dados do módulo preservando acentos
                    var moduleName = self.preserveAccents(module.nome);
                    var moduleDesc = self.preserveAccents(module.descricao || '');
                    var groupName = self.preserveAccents(group.nome);

                    card.setAttribute('data-module-id', module.id);
                    card.setAttribute('data-module-name', moduleName);
                    card.setAttribute('data-module-link', module.link || '#');
                    card.setAttribute('data-module-add', module.adicionar || '');
                    card.setAttribute('data-module-description', moduleDesc);
                    card.setAttribute('data-module-icon', module.icon || 'box');
                    card.setAttribute('data-group-name', groupName);
                    card.setAttribute('data-group-color', color);
                    card.setAttribute('data-module-doc', module.docsLink || '');
                    card.setAttribute('data-module-manual', module.manualLink || '');

                    // Background do card
                    var cardBg = document.createElement('a-box');
                    cardBg.setAttribute('width', CONFIG.cards.width);
                    cardBg.setAttribute('height', CONFIG.cards.height);
                    cardBg.setAttribute('depth', CONFIG.cards.depth);
                    cardBg.setAttribute('material', 'color: #1a1a2e; opacity: 0.95; transparent: true');
                    cardBg.setAttribute('class', 'card-bg clickable');
                    card.appendChild(cardBg);

                    // Borda colorida (glow effect)
                    var cardBorder = document.createElement('a-box');
                    cardBorder.setAttribute('width', CONFIG.cards.width + 0.05);
                    cardBorder.setAttribute('height', CONFIG.cards.height + 0.05);
                    cardBorder.setAttribute('depth', CONFIG.cards.depth - 0.02);
                    cardBorder.setAttribute('position', '0 0 -0.02');
                    cardBorder.setAttribute('material',
                        'color: ' + color + '; ' +
                        'emissive: ' + color + '; ' +
                        'emissiveIntensity: 0.5; ' +
                        'opacity: 0.8'
                    );
                    card.appendChild(cardBorder);

                    // Imagem destaque (thumbnail) - se configurado
                    if (CONFIG.cards.thumbnail && CONFIG.cards.thumbnail.enabled) {
                        var thumbnailUrl = module.thumbnail ||
                            (CONFIG.cards.thumbnail.defaultUrl + module.id);

                        var thumbnail = document.createElement('a-plane');
                        thumbnail.setAttribute('position', '0 ' + CONFIG.cards.thumbnail.yOffset + ' 0.05');
                        thumbnail.setAttribute('width', CONFIG.cards.thumbnail.width);
                        thumbnail.setAttribute('height', CONFIG.cards.thumbnail.height);
                        thumbnail.setAttribute('material',
                            'src: url(' + thumbnailUrl + '); ' +
                            'side: front; ' +
                            'transparent: true'
                        );
                        thumbnail.setAttribute('class', 'card-thumbnail');
                        card.appendChild(thumbnail);
                    }

                    // Ícone (posição ajustada se tiver thumbnail)
                    var iconEmoji = self.getModuleIcon(module.icon);
                    var iconY = CONFIG.cards.thumbnail && CONFIG.cards.thumbnail.enabled ? -0.25 : 0.35;
                    var iconText = document.createElement('a-entity');
                    iconText.setAttribute('position', '-0.7 ' + iconY + ' 0.05');
                    iconText.setAttribute('troika-text',
                        'value: ' + iconEmoji + '; ' +
                        'align: center; ' +
                        'fontSize: 0.18; ' +
                        'color: ' + color + '; ' +
                        'outlineWidth: 0.01; ' +
                        'outlineColor: #000'
                    );
                    card.appendChild(iconText);

                    // Nome do módulo (centralizado no card) - com limite de caracteres do config
                    var nameY = CONFIG.cards.thumbnail && CONFIG.cards.thumbnail.enabled ? -0.25 : 0.35;
                    var formattedName = self.formatTitle(moduleName);
                    var titleMaxWidth = CONFIG.cards.text ? CONFIG.cards.text.titleMaxWidth : 1.8;
                    var nameText = document.createElement('a-entity');
                    nameText.setAttribute('position', '0.15 ' + nameY + ' 0.05');
                    nameText.setAttribute('troika-text',
                        'value: ' + formattedName + '; ' +
                        'align: center; ' +
                        'fontSize: 0.12; ' +
                        'maxWidth: ' + titleMaxWidth + '; ' +
                        'color: white; ' +
                        'outlineWidth: 0.005; ' +
                        'outlineColor: #000'
                    );
                    card.appendChild(nameText);

                    // Descrição - com limite de caracteres do config
                    var descY = CONFIG.cards.thumbnail && CONFIG.cards.thumbnail.enabled ? -0.5 : -0.1;
                    var formattedDesc = self.formatDescription(moduleDesc);
                    var descMaxWidth = CONFIG.cards.text ? CONFIG.cards.text.descriptionMaxWidth : 2.0;
                    var descText = document.createElement('a-entity');
                    descText.setAttribute('position', '0 ' + descY + ' 0.05');
                    descText.setAttribute('troika-text',
                        'value: ' + formattedDesc + '; ' +
                        'align: center; ' +
                        'fontSize: 0.08; ' +
                        'maxWidth: ' + descMaxWidth + '; ' +
                        'color: #888888; ' +
                        'outlineWidth: 0.003; ' +
                        'outlineColor: #000'
                    );
                    card.appendChild(descText);

                    // Barra de ações simples (sem botões - ações movidas para modal)
                    var actionsBar = document.createElement('a-plane');
                    actionsBar.setAttribute('position', '0 -0.65 0.05');
                    actionsBar.setAttribute('width', CONFIG.cards.width - 0.2);
                    actionsBar.setAttribute('height', 0.15);
                    actionsBar.setAttribute('material', 'color: ' + color + '; opacity: 0.3');
                    card.appendChild(actionsBar);

                    container.appendChild(card);
                });
            });

            console.log('Dashboard3DCards: Cards criados');
        },

        /**
         * Cria as conexões entre cards e o anel
         * @param {Array} groupsData - Dados dos grupos para calcular raio dinâmico
         */
        createConnections: function (groupsData) {
            const container = document.getElementById('connections-container');
            const CONFIG = global.Dashboard3DConfig;

            if (!container) return;

            // Raio dinâmico baseado em quantidade de grupos
            const groupCount = groupsData ? groupsData.length : 6;
            const ringRadius = CONFIG.ring.getRadius ?
                CONFIG.ring.getRadius(groupCount) :
                (CONFIG.ring.baseRadius || CONFIG.ring.radius || 6);

            const cards = document.querySelectorAll('.module-card');

            cards.forEach(function (card) {
                var pos = card.getAttribute('position');
                var color = card.getAttribute('data-group-color') || '#4a9eff';

                if (!pos) return;

                var cardPos = AFRAME.utils.coordinates.parse(pos);
                var angle = Math.atan2(cardPos.z, cardPos.x);

                // Usar raio dinâmico para posição de conexão no anel
                var ringX = Math.cos(angle) * ringRadius;
                var ringZ = Math.sin(angle) * ringRadius;

                var line = document.createElement('a-entity');
                line.setAttribute('custom-line',
                    'start: ' + ringX + ' ' + CONFIG.ring.height + ' ' + ringZ + '; ' +
                    'end: ' + cardPos.x + ' ' + cardPos.y + ' ' + cardPos.z + '; ' +
                    'color: ' + color + '; ' +
                    'opacity: 0.3'
                );
                container.appendChild(line);
            });

            console.log('Dashboard3DCards: Conexões criadas');
        }
    };

    // Exportar para o namespace global
    global.Dashboard3DCards = Dashboard3DCards;

})(window);
