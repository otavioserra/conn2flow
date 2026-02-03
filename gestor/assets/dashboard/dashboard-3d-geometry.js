/**
 * Dashboard 3D - Geometria 3D
 * Cria elementos 3D: quadros de grupo, partículas
 */

(function (global) {
    'use strict';

    const Dashboard3DGeometry = {

        /**
         * Cria a esfera central com anéis decorativos
         * DESABILITADO - Navegação simplificada (apenas rotação e zoom)
         * @param {Array} groupsData - Dados dos grupos
         */
        createCentralSphere: function (groupsData) {
            const CONFIG = global.Dashboard3DConfig;

            // Verificar se esfera está habilitada no config
            if (!CONFIG.sphere || !CONFIG.sphere.enabled) {
                console.log('Dashboard3DGeometry: Esfera central desabilitada');
                return;
            }

            const container = document.getElementById('ring-container');
            if (!container) return;

            const sphereY = CONFIG.sphere.radius * 2;

            // Esfera central
            const sphere = document.createElement('a-sphere');
            sphere.setAttribute('radius', CONFIG.sphere.radius);
            sphere.setAttribute('position', '0 ' + sphereY + ' 0');
            sphere.setAttribute('material',
                'color: #1a1a3e; ' +
                'emissive: #4a9eff; ' +
                'emissiveIntensity: 0.4; ' +
                'metalness: 0.8; ' +
                'roughness: 0.2; ' +
                'opacity: 0.9; ' +
                'transparent: true'
            );
            sphere.setAttribute('animation',
                'property: rotation; ' +
                'to: 0 360 0; ' +
                'dur: 20000; ' +
                'loop: true; ' +
                'easing: linear'
            );
            sphere.setAttribute('id', 'central-sphere');
            container.appendChild(sphere);

            // Anel interno decorativo
            const innerRing = document.createElement('a-torus');
            innerRing.setAttribute('radius', 1.8);
            innerRing.setAttribute('radius-tubular', 0.03);
            innerRing.setAttribute('position', '0 ' + sphereY + ' 0');
            innerRing.setAttribute('rotation', '90 0 0');
            innerRing.setAttribute('material', 'color: #4a9eff; emissive: #4a9eff; emissiveIntensity: 0.8; opacity: 0.6');
            innerRing.setAttribute('animation',
                'property: rotation; ' +
                'to: 90 360 0; ' +
                'dur: 15000; ' +
                'loop: true; ' +
                'easing: linear'
            );
            container.appendChild(innerRing);

            // Segundo anel perpendicular
            const innerRing2 = document.createElement('a-torus');
            innerRing2.setAttribute('radius', 2.0);
            innerRing2.setAttribute('radius-tubular', 0.02);
            innerRing2.setAttribute('position', '0 ' + sphereY + ' 0');
            innerRing2.setAttribute('rotation', '0 0 0');
            innerRing2.setAttribute('material', 'color: #6dd400; emissive: #6dd400; emissiveIntensity: 0.6; opacity: 0.4');
            innerRing2.setAttribute('animation',
                'property: rotation; ' +
                'to: 360 0 0; ' +
                'dur: 25000; ' +
                'loop: true; ' +
                'easing: linear'
            );
            container.appendChild(innerRing2);

            // Criar tubos verticais
            this.createVerticalTubes(container, sphereY, groupsData);

            console.log('Dashboard3DGeometry: Esfera central criada');
        },

        /**
         * Cria os tubos verticais que conectam a esfera ao anel base
         * @param {Element} container - Container A-Frame
         * @param {number} sphereY - Posição Y da esfera
         * @param {Array} groupsData - Dados dos grupos
         */
        createVerticalTubes: function (container, sphereY, groupsData) {
            return;
            const CONFIG = global.Dashboard3DConfig;
            const segmentCount = Math.min(groupsData.length, CONFIG.segmentColors.length);
            const anglePerSegment = (Math.PI * 2) / segmentCount;

            // Raio dinâmico baseado em quantidade de grupos
            const ringRadius = CONFIG.ring.getRadius ?
                CONFIG.ring.getRadius(groupsData.length) :
                (CONFIG.ring.baseRadius || CONFIG.ring.radius || 6);

            // Raio onde os tubos conectam na esfera
            const sphereRadius = CONFIG.sphere.radius;
            const tubeConnectionRadius = sphereRadius * 2;

            for (var i = 0; i < segmentCount; i++) {
                var color = CONFIG.segmentColors[i];
                var angle = (i * anglePerSegment) - (Math.PI / 2) + (anglePerSegment / 2);

                // Posição do tubo no anel base (usando raio dinâmico)
                var baseX = Math.cos(angle) * ringRadius;
                var baseZ = Math.sin(angle) * ringRadius;

                // Posição onde o tubo conecta na esfera
                var topX = Math.cos(angle) * tubeConnectionRadius;
                var topZ = Math.sin(angle) * tubeConnectionRadius;

                // Calcular posição central e altura do tubo
                var centerX = (baseX + topX) / 2;
                var centerZ = (baseZ + topZ) / 2;
                var tubeHeight = Math.sqrt(
                    Math.pow(baseX - topX, 2) +
                    Math.pow(sphereY, 2) +
                    Math.pow(baseZ - topZ, 2)
                );

                // Calcular rotação do tubo
                var dx = topX - baseX;
                var dy = sphereY;
                var dz = topZ - baseZ;

                var rotY = Math.atan2(dx, dz) * (180 / Math.PI);
                var horizontalDist = Math.sqrt(dx * dx + dz * dz);
                var rotX = Math.atan2(horizontalDist, dy) * (180 / Math.PI);

                // Criar o tubo vertical
                var tube = document.createElement('a-cylinder');
                tube.setAttribute('radius', CONFIG.ring.tubeRadius * 0.6);
                tube.setAttribute('height', tubeHeight);
                tube.setAttribute('position', centerX + ' ' + (sphereY / 2) + ' ' + centerZ);
                tube.setAttribute('rotation', rotX + ' ' + rotY + ' 0');
                tube.setAttribute('material',
                    'color: ' + color + '; ' +
                    'emissive: ' + color + '; ' +
                    'emissiveIntensity: 0.4; ' +
                    'metalness: 0.4; ' +
                    'roughness: 0.6; ' +
                    'opacity: 0.85; ' +
                    'transparent: true'
                );
                tube.setAttribute('class', 'vertical-tube');
                tube.setAttribute('data-group-index', i);

                container.appendChild(tube);
            }

            console.log('Dashboard3DGeometry: Tubos verticais criados');
        },

        /**
         * Cria os segmentos do anel
         * @param {Array} groupsData - Dados dos grupos
         */
        createRingSegments: function (groupsData) {
            const container = document.getElementById('ring-container');
            const CONFIG = global.Dashboard3DConfig;

            if (!container) return;

            const segmentCount = Math.min(groupsData.length, CONFIG.segmentColors.length);
            const anglePerSegment = (Math.PI * 2) / segmentCount;

            // Raio dinâmico baseado em quantidade de grupos
            const ringRadius = (CONFIG.ring.getRadius ?
                CONFIG.ring.getRadius(groupsData.length) :
                (CONFIG.ring.baseRadius || CONFIG.ring.radius || 6));

            for (var i = 0; i < segmentCount; i++) {
                var group = groupsData[i] || { id: 'grupo-' + i, nome: 'Grupo ' + (i + 1), descricao: '' };
                var color = CONFIG.segmentColors[i];
                var arcDegrees = (360 / segmentCount) - 3;

                const tubesEnabled = false;

                if (tubesEnabled) {
                    // Segmento do anel (usando raio dinâmico)
                    var segment = document.createElement('a-torus');
                    segment.setAttribute('radius', ringRadius);
                    segment.setAttribute('radius-tubular', CONFIG.ring.tubeRadius);
                    segment.setAttribute('segments-radial', 16);
                    segment.setAttribute('segments-tubular', 32);
                    segment.setAttribute('arc', arcDegrees);
                    segment.setAttribute('rotation', '0 ' + ((i * 360 / segmentCount) - 90) + ' 0');
                    segment.setAttribute('material',
                        'color: ' + color + '; ' +
                        'emissive: ' + color + '; ' +
                        'emissiveIntensity: 0.3; ' +
                        'metalness: 0.3; ' +
                        'roughness: 0.7'
                    );
                    segment.setAttribute('class', 'ring-segment');
                    segment.setAttribute('data-group', group.id);

                    container.appendChild(segment);
                }

                // Label do grupo (posição também dinâmica)
                var labelAngle = (i * anglePerSegment) - (Math.PI / 2) + (anglePerSegment / 2);
                var labelDistance = ringRadius + 4.5;
                var labelX = Math.cos(labelAngle) * labelDistance;
                var labelZ = Math.sin(labelAngle) * labelDistance;

                var label = document.createElement('a-entity');
                label.setAttribute('position', labelX + ' 0.5 ' + labelZ);
                label.setAttribute('troika-text',
                    'value: ' + group.nome + '; ' +
                    'color: ' + color + '; ' +
                    'align: center; ' +
                    'fontSize: 0.4; ' +
                    'maxWidth: 6; ' +
                    'outlineWidth: 0.015; ' +
                    'outlineColor: #000'
                );
                label.setAttribute('billboard-camera', '');
                container.appendChild(label);

                if (group.descricao) {
                    var descLabel = document.createElement('a-entity');
                    descLabel.setAttribute('position', labelX + ' 0 ' + labelZ);
                    descLabel.setAttribute('troika-text',
                        'value: ' + group.descricao + '; ' +
                        'color: #888888; ' +
                        'align: center; ' +
                        'fontSize: 0.2; ' +
                        'maxWidth: 4; ' +
                        'outlineWidth: 0.008; ' +
                        'outlineColor: #000'
                    );
                    descLabel.setAttribute('billboard-camera', '');
                    container.appendChild(descLabel);
                }
            }

            console.log('Dashboard3DGeometry: Anel criado com', segmentCount, 'segmentos');
        },

        /**
         * Cria partículas de fundo
         */
        createParticles: function () {
            const container = document.getElementById('particles-container');

            if (!container) return;

            for (var i = 0; i < 80; i++) {
                var particle = document.createElement('a-sphere');
                particle.setAttribute('radius', 0.02 + Math.random() * 0.03);
                particle.setAttribute('position',
                    ((Math.random() - 0.5) * 40) + ' ' +
                    (Math.random() * 15) + ' ' +
                    ((Math.random() - 0.5) * 40)
                );
                particle.setAttribute('material',
                    'color: #4a9eff; ' +
                    'emissive: #4a9eff; ' +
                    'emissiveIntensity: 0.5; ' +
                    'opacity: ' + (0.3 + Math.random() * 0.4)
                );
                container.appendChild(particle);
            }

            console.log('Dashboard3DGeometry: Partículas criadas');
        },

        /**
         * Cria os quadros/painéis 3D para cada grupo
         * Cada grupo tem um quadro estilizado com a cor do grupo e título
         * @param {Array} groupsData - Dados dos grupos
         */
        createGroupBoards: function (groupsData) {
            const container = document.getElementById('cards-container');
            const CONFIG = global.Dashboard3DConfig;

            if (!container || !CONFIG.groupBoard || !CONFIG.groupBoard.enabled) {
                console.log('Dashboard3DGeometry: Quadros de grupo desabilitados');
                return;
            }

            const segmentCount = Math.min(groupsData.length, CONFIG.segmentColors.length);
            const anglePerSegment = (Math.PI * 2) / segmentCount;

            // Raio dinâmico baseado em quantidade de grupos
            const ringRadius = CONFIG.ring.getRadius ?
                CONFIG.ring.getRadius(groupsData.length) :
                (CONFIG.ring.baseRadius || CONFIG.ring.radius || 6);

            const prismLength = CONFIG.prism.getLength ?
                CONFIG.prism.getLength(groupsData.length) :
                CONFIG.prism.length;

            const board = CONFIG.groupBoard;

            for (var i = 0; i < segmentCount; i++) {
                var group = groupsData[i] || { id: 'grupo-' + i, nome: 'Grupo ' + (i + 1) };
                var color = CONFIG.segmentColors[i];
                var groupCenterAngle = (i * anglePerSegment) - (Math.PI / 2) + (anglePerSegment / 2);

                // Posição do quadro (atrás dos prismas/cards)
                var centerDistance = ringRadius + prismLength - 5.20; // Adicionado offset para ficar atrás dos cards
                var boardX = Math.cos(groupCenterAngle) * centerDistance;
                var boardZ = Math.sin(groupCenterAngle) * centerDistance;

                // Altura centralizada
                var heightMin = CONFIG.ring.height + CONFIG.prism.heightMin;
                var heightMax = CONFIG.ring.height + CONFIG.prism.heightMax;
                var boardY = (heightMin + heightMax) / 2;

                // Rotação para ficar de frente para o centro
                var rotationY = (-groupCenterAngle * 180 / Math.PI) + 90;

                // Container do quadro
                var boardContainer = document.createElement('a-entity');
                boardContainer.setAttribute('position', boardX + ' ' + boardY + ' ' + boardZ);
                boardContainer.setAttribute('rotation', '0 ' + rotationY + ' 0');
                boardContainer.setAttribute('class', 'group-board');
                boardContainer.setAttribute('data-group-id', group.id);

                // === Fundo do quadro (painel principal) ===
                var backPanel = document.createElement('a-box');
                backPanel.setAttribute('width', board.width);
                backPanel.setAttribute('height', board.height);
                backPanel.setAttribute('depth', board.depth * 0.5);
                backPanel.setAttribute('position', '0 0 -' + (board.depth * 0.3));
                backPanel.setAttribute('material',
                    'color: #0a0a15; ' +
                    'opacity: ' + board.backgroundOpacity + '; ' +
                    'transparent: true'
                );
                boardContainer.appendChild(backPanel);

                // === Moldura/Borda do quadro ===
                // Borda superior
                var borderTop = document.createElement('a-box');
                borderTop.setAttribute('width', board.width + board.borderWidth);
                borderTop.setAttribute('height', board.borderWidth);
                borderTop.setAttribute('depth', board.depth);
                borderTop.setAttribute('position', '0 ' + (board.height / 2) + ' 0');
                borderTop.setAttribute('material',
                    'color: ' + color + '; ' +
                    'emissive: ' + color + '; ' +
                    'emissiveIntensity: ' + board.borderEmissive + '; ' +
                    'metalness: 0.6; ' +
                    'roughness: 0.3'
                );
                boardContainer.appendChild(borderTop);

                // Borda inferior
                var borderBottom = document.createElement('a-box');
                borderBottom.setAttribute('width', board.width + board.borderWidth);
                borderBottom.setAttribute('height', board.borderWidth);
                borderBottom.setAttribute('depth', board.depth);
                borderBottom.setAttribute('position', '0 ' + (-board.height / 2) + ' 0');
                borderBottom.setAttribute('material',
                    'color: ' + color + '; ' +
                    'emissive: ' + color + '; ' +
                    'emissiveIntensity: ' + board.borderEmissive + '; ' +
                    'metalness: 0.6; ' +
                    'roughness: 0.3'
                );
                boardContainer.appendChild(borderBottom);

                // Borda esquerda
                var borderLeft = document.createElement('a-box');
                borderLeft.setAttribute('width', board.borderWidth);
                borderLeft.setAttribute('height', board.height);
                borderLeft.setAttribute('depth', board.depth);
                borderLeft.setAttribute('position', (-board.width / 2) + ' 0 0');
                borderLeft.setAttribute('material',
                    'color: ' + color + '; ' +
                    'emissive: ' + color + '; ' +
                    'emissiveIntensity: ' + board.borderEmissive + '; ' +
                    'metalness: 0.6; ' +
                    'roughness: 0.3'
                );
                boardContainer.appendChild(borderLeft);

                // Borda direita
                var borderRight = document.createElement('a-box');
                borderRight.setAttribute('width', board.borderWidth);
                borderRight.setAttribute('height', board.height);
                borderRight.setAttribute('depth', board.depth);
                borderRight.setAttribute('position', (board.width / 2) + ' 0 0');
                borderRight.setAttribute('material',
                    'color: ' + color + '; ' +
                    'emissive: ' + color + '; ' +
                    'emissiveIntensity: ' + board.borderEmissive + '; ' +
                    'metalness: 0.6; ' +
                    'roughness: 0.3'
                );
                boardContainer.appendChild(borderRight);

                // === Cantos decorativos (esferas nos cantos) ===
                var corners = [
                    { x: -board.width / 2, y: board.height / 2 },
                    { x: board.width / 2, y: board.height / 2 },
                    { x: -board.width / 2, y: -board.height / 2 },
                    { x: board.width / 2, y: -board.height / 2 }
                ];

                corners.forEach(function (corner) {
                    var cornerSphere = document.createElement('a-sphere');
                    cornerSphere.setAttribute('radius', board.borderWidth * 0.8);
                    cornerSphere.setAttribute('position', corner.x + ' ' + corner.y + ' ' + (board.depth * 0.5));
                    cornerSphere.setAttribute('material',
                        'color: ' + color + '; ' +
                        'emissive: ' + color + '; ' +
                        'emissiveIntensity: ' + (board.borderEmissive + 0.2) + '; ' +
                        'metalness: 0.8; ' +
                        'roughness: 0.2'
                    );
                    boardContainer.appendChild(cornerSphere);
                });

                // === Título do grupo (no topo do quadro) ===
                var titleY = (board.height / 2) - board.titleOffsetY;
                var titleEntity = document.createElement('a-entity');
                titleEntity.setAttribute('position', '0 ' + titleY + ' ' + (board.depth * 0.6));
                titleEntity.setAttribute('troika-text',
                    'value: ' + group.nome + '; ' +
                    'color: ' + color + '; ' +
                    'align: center; ' +
                    'fontSize: ' + board.titleFontSize + '; ' +
                    'maxWidth: ' + (board.width - 0.5) + '; ' +
                    'outlineWidth: 0.02; ' +
                    'outlineColor: #000; ' +
                    'fontWeight: bold'
                );
                boardContainer.appendChild(titleEntity);

                // === Linha decorativa abaixo do título ===
                var titleLineY = titleY - 0.35;
                var titleLine = document.createElement('a-box');
                titleLine.setAttribute('width', board.width * 0.6);
                titleLine.setAttribute('height', 0.02);
                titleLine.setAttribute('depth', 0.02);
                titleLine.setAttribute('position', '0 ' + titleLineY + ' ' + (board.depth * 0.5));
                titleLine.setAttribute('material',
                    'color: ' + color + '; ' +
                    'emissive: ' + color + '; ' +
                    'emissiveIntensity: 0.8; ' +
                    'opacity: 0.8'
                );
                boardContainer.appendChild(titleLine);

                // === Efeito de brilho sutil (glow) ===
                var glow = document.createElement('a-plane');
                glow.setAttribute('width', board.width + 0.5);
                glow.setAttribute('height', board.height + 0.5);
                glow.setAttribute('position', '0 0 -' + (board.depth * 0.8));
                glow.setAttribute('material',
                    'color: ' + color + '; ' +
                    'emissive: ' + color + '; ' +
                    'emissiveIntensity: ' + board.glowIntensity + '; ' +
                    'opacity: 0.15; ' +
                    'transparent: true; ' +
                    'side: double'
                );
                boardContainer.appendChild(glow);

                container.appendChild(boardContainer);
            }

            console.log('Dashboard3DGeometry: Quadros de grupo criados');
        },

        /**
         * Registra o componente custom-line se não existir
         */
        registerCustomLineComponent: function () {
            return;
            if (typeof AFRAME !== 'undefined' && !AFRAME.components['custom-line']) {
                AFRAME.registerComponent('custom-line', {
                    schema: {
                        start: { type: 'vec3', default: { x: 0, y: 0, z: 0 } },
                        end: { type: 'vec3', default: { x: 0, y: 0, z: 0 } },
                        color: { type: 'color', default: '#ffffff' },
                        opacity: { type: 'number', default: 1 }
                    },

                    init: function () {
                        var data = this.data;
                        var geometry = new THREE.BufferGeometry();
                        var positions = new Float32Array([
                            data.start.x, data.start.y, data.start.z,
                            data.end.x, data.end.y, data.end.z
                        ]);
                        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

                        var material = new THREE.LineBasicMaterial({
                            color: new THREE.Color(data.color),
                            transparent: true,
                            opacity: data.opacity
                        });

                        this.el.setObject3D('line', new THREE.Line(geometry, material));
                    }
                });

                console.log('Dashboard3DGeometry: Componente custom-line registrado');
            }
        },

        /**
         * Registra o componente billboard-camera (mais robusto que look-at)
         * Este componente faz o objeto sempre olhar para a câmera ativa
         * 
         * IMPORTANTE: O orbit-controls move a câmera THREE.js (getObject3D('camera')),
         * NÃO o object3D do A-Frame. Por isso precisamos usar getObject3D('camera').
         */
        registerBillboardComponent: function () {
            if (typeof AFRAME !== 'undefined' && !AFRAME.components['billboard-camera']) {
                AFRAME.registerComponent('billboard-camera', {
                    schema: {
                        // Eixos que devem rotacionar (true = rotaciona, false = mantém fixo)
                        lockX: { type: 'boolean', default: false },
                        lockY: { type: 'boolean', default: false },
                        lockZ: { type: 'boolean', default: false }
                    },

                    init: function () {
                        this.cameraEl = null;
                        this.threeCamera = null;
                        this.cameraPosition = new THREE.Vector3();

                        // Buscar referências uma única vez no init
                        var self = this;
                        setTimeout(function () {
                            self.findCamera();
                        }, 100);
                    },

                    findCamera: function () {
                        // Buscar elemento da câmera A-Frame
                        this.cameraEl = document.querySelector('#main-camera') ||
                            document.querySelector('[camera]') ||
                            document.querySelector('a-camera');

                        // IMPORTANTE: Obter a câmera THREE.js, não o object3D do A-Frame
                        // O orbit-controls move a posição de getObject3D('camera')
                        if (this.cameraEl) {
                            this.threeCamera = this.cameraEl.getObject3D('camera');
                        }
                    },

                    tick: function () {
                        // Se não temos câmera THREE.js, tentar encontrar
                        if (!this.threeCamera) {
                            this.findCamera();
                            if (!this.threeCamera) return;
                        }

                        var object3D = this.el.object3D;

                        // IMPORTANTE: Obter posição diretamente da câmera THREE.js
                        // O orbit-controls modifica .position desta câmera
                        // A posição é relativa ao parent (object3D do A-Frame que está em 0,0,0)
                        // então a posição local É a posição mundial
                        this.cameraPosition.copy(this.threeCamera.position);

                        // Salvar rotação original se tiver locks
                        var originalRotation = null;
                        if (this.data.lockX || this.data.lockY || this.data.lockZ) {
                            originalRotation = {
                                x: object3D.rotation.x,
                                y: object3D.rotation.y,
                                z: object3D.rotation.z
                            };
                        }

                        // Fazer o objeto olhar para a câmera
                        object3D.lookAt(this.cameraPosition);

                        // Restaurar rotações bloqueadas
                        if (originalRotation) {
                            if (this.data.lockX) object3D.rotation.x = originalRotation.x;
                            if (this.data.lockY) object3D.rotation.y = originalRotation.y;
                            if (this.data.lockZ) object3D.rotation.z = originalRotation.z;
                        }
                    }
                });

                console.log('Dashboard3DGeometry: Componente billboard-camera registrado');
            }
        },

        /**
         * Cria os volumes prismáticos para debug
         * Prisma triangular 3D: dois triângulos equiláteros (base e topo)
         * com as pontas apontando para o tubo do grupo
         * @param {Array} groupsData - Dados dos grupos
         */
        createDebugPrisms: function (groupsData) {
            const container = document.getElementById('ring-container');
            const CONFIG = global.Dashboard3DConfig;

            if (!container || !CONFIG.prism || !CONFIG.prism.debug) return;

            const segmentCount = Math.min(groupsData.length, CONFIG.segmentColors.length);
            const anglePerSegment = (Math.PI * 2) / segmentCount;

            const ringRadius = CONFIG.ring.getRadius ?
                CONFIG.ring.getRadius(groupsData.length) :
                (CONFIG.ring.baseRadius || CONFIG.ring.radius || 6);

            const prismLength = CONFIG.prism.getLength ?
                CONFIG.prism.getLength(groupsData.length) :
                CONFIG.prism.length;

            for (var i = 0; i < segmentCount; i++) {
                var color = CONFIG.segmentColors[i];
                var groupCenterAngle = (i * anglePerSegment) - (Math.PI / 2) + (anglePerSegment / 2);

                // Calcular geometria do prisma triangular
                var baseWidth = CONFIG.prism.getBaseWidth ?
                    CONFIG.prism.getBaseWidth(groupsData.length) :
                    CONFIG.prism.baseWidth;

                // Alturas do prisma
                var heightMin = CONFIG.prism.heightMin;
                var heightMax = CONFIG.prism.heightMax;
                var baseY = CONFIG.ring.height + heightMin;
                var topY = CONFIG.ring.height + heightMax;

                // Posição do tubo (ponta do prisma aponta aqui)
                var tipX = Math.cos(groupCenterAngle) * ringRadius;
                var tipZ = Math.sin(groupCenterAngle) * ringRadius;

                // Base do prisma (longe do tubo)
                var baseDistance = ringRadius + prismLength;
                var baseCenterX = Math.cos(groupCenterAngle) * baseDistance;
                var baseCenterZ = Math.sin(groupCenterAngle) * baseDistance;

                // Calcular os 3 vértices do triângulo da base
                // Triângulo perpendicular à direção radial
                var perpAngle = groupCenterAngle + Math.PI / 2;
                var halfWidth = baseWidth / 2;

                // Vértices do triângulo inferior (na base, Y = baseY)
                var v1 = {
                    x: baseCenterX + Math.cos(perpAngle) * halfWidth,
                    y: baseY,
                    z: baseCenterZ + Math.sin(perpAngle) * halfWidth
                };
                var v2 = {
                    x: baseCenterX - Math.cos(perpAngle) * halfWidth,
                    y: baseY,
                    z: baseCenterZ - Math.sin(perpAngle) * halfWidth
                };
                var v3 = {
                    x: tipX,
                    y: baseY,
                    z: tipZ
                };

                // Vértices do triângulo superior (no topo, Y = topY)
                var v4 = {
                    x: baseCenterX + Math.cos(perpAngle) * halfWidth,
                    y: topY,
                    z: baseCenterZ + Math.sin(perpAngle) * halfWidth
                };
                var v5 = {
                    x: baseCenterX - Math.cos(perpAngle) * halfWidth,
                    y: topY,
                    z: baseCenterZ - Math.sin(perpAngle) * halfWidth
                };
                var v6 = {
                    x: tipX,
                    y: topY,
                    z: tipZ
                };

                // Criar geometria do prisma usando THREE.js
                var prismEntity = document.createElement('a-entity');
                prismEntity.setAttribute('class', 'debug-prism');
                prismEntity.setAttribute('data-group-index', i);

                // Adicionar componente personalizado para criar a geometria
                prismEntity.setAttribute('prism-geometry',
                    'v1: ' + v1.x + ' ' + v1.y + ' ' + v1.z + '; ' +
                    'v2: ' + v2.x + ' ' + v2.y + ' ' + v2.z + '; ' +
                    'v3: ' + v3.x + ' ' + v3.y + ' ' + v3.z + '; ' +
                    'v4: ' + v4.x + ' ' + v4.y + ' ' + v4.z + '; ' +
                    'v5: ' + v5.x + ' ' + v5.y + ' ' + v5.z + '; ' +
                    'v6: ' + v6.x + ' ' + v6.y + ' ' + v6.z + '; ' +
                    'color: ' + color + '; ' +
                    'opacity: ' + CONFIG.prism.debugOpacity
                );

                container.appendChild(prismEntity);
            }

            console.log('Dashboard3DGeometry: Prismas de debug criados');
        },

        /**
         * Registra o componente prism-geometry para criar prismas triangulares
         */
        registerPrismGeometryComponent: function () {
            if (typeof AFRAME !== 'undefined' && !AFRAME.components['prism-geometry']) {
                AFRAME.registerComponent('prism-geometry', {
                    schema: {
                        v1: { type: 'vec3', default: { x: 0, y: 0, z: 0 } },
                        v2: { type: 'vec3', default: { x: 1, y: 0, z: 0 } },
                        v3: { type: 'vec3', default: { x: 0.5, y: 0, z: 1 } },
                        v4: { type: 'vec3', default: { x: 0, y: 1, z: 0 } },
                        v5: { type: 'vec3', default: { x: 1, y: 1, z: 0 } },
                        v6: { type: 'vec3', default: { x: 0.5, y: 1, z: 1 } },
                        color: { type: 'color', default: '#ffffff' },
                        opacity: { type: 'number', default: 0.1 }
                    },

                    init: function () {
                        var data = this.data;

                        // Criar geometria do prisma triangular
                        var geometry = new THREE.BufferGeometry();

                        // 6 vértices do prisma
                        var vertices = new Float32Array([
                            // Triângulo inferior (v1, v2, v3)
                            data.v1.x, data.v1.y, data.v1.z,
                            data.v2.x, data.v2.y, data.v2.z,
                            data.v3.x, data.v3.y, data.v3.z,
                            // Triângulo superior (v4, v5, v6)
                            data.v4.x, data.v4.y, data.v4.z,
                            data.v5.x, data.v5.y, data.v5.z,
                            data.v6.x, data.v6.y, data.v6.z
                        ]);

                        // Índices para formar as 8 faces (2 triângulos para cada face retangular + 2 tampas triangulares)
                        var indices = new Uint16Array([
                            // Tampa inferior
                            0, 1, 2,
                            // Tampa superior
                            3, 5, 4,
                            // Face lateral 1 (v1-v2-v5-v4)
                            0, 3, 4,
                            0, 4, 1,
                            // Face lateral 2 (v2-v3-v6-v5)
                            1, 4, 5,
                            1, 5, 2,
                            // Face lateral 3 (v3-v1-v4-v6)
                            2, 5, 3,
                            2, 3, 0
                        ]);

                        geometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
                        geometry.setIndex(new THREE.BufferAttribute(indices, 1));
                        geometry.computeVertexNormals();

                        var material = new THREE.MeshBasicMaterial({
                            color: new THREE.Color(data.color),
                            transparent: true,
                            opacity: data.opacity,
                            side: THREE.DoubleSide,
                            wireframe: false
                        });

                        var mesh = new THREE.Mesh(geometry, material);
                        this.el.setObject3D('mesh', mesh);

                        // Adicionar wireframe para melhor visualização
                        var wireframeMaterial = new THREE.LineBasicMaterial({
                            color: new THREE.Color(data.color),
                            transparent: true,
                            opacity: data.opacity * 3
                        });
                        var wireframeGeometry = new THREE.WireframeGeometry(geometry);
                        var wireframe = new THREE.LineSegments(wireframeGeometry, wireframeMaterial);
                        this.el.setObject3D('wireframe', wireframe);
                    }
                });

                console.log('Dashboard3DGeometry: Componente prism-geometry registrado');
            }
        },

        /**
         * Remove os prismas de debug
         */
        removeDebugPrisms: function () {
            const prisms = document.querySelectorAll('.debug-prism');
            prisms.forEach(function (prism) {
                prism.parentNode.removeChild(prism);
            });
        },

        /**
         * Toggle para mostrar/esconder prismas de debug
         */
        toggleDebugPrisms: function (groupsData) {
            const CONFIG = global.Dashboard3DConfig;
            if (!CONFIG.prism) return;

            CONFIG.prism.debug = !CONFIG.prism.debug;

            if (CONFIG.prism.debug) {
                this.createDebugPrisms(groupsData);
            } else {
                this.removeDebugPrisms();
            }

            return CONFIG.prism.debug;
        },

        /**
         * Cria o logo 3D Conn2Flow com extrusão e animações
         */
        createLogo3D: function () {
            const CONFIG = global.Dashboard3DConfig;

            // Verificar se logo está habilitado
            if (!CONFIG.logo || !CONFIG.logo.enabled) {
                console.log('Dashboard3DGeometry: Logo 3D desabilitado');
                return;
            }

            const scene = document.getElementById('dashboard-3d-scene');
            if (!scene) return;

            // Container do logo
            const logoContainer = document.createElement('a-entity');
            logoContainer.setAttribute('id', 'logo-3d-container');
            logoContainer.setAttribute('position',
                CONFIG.logo.position.x + ' ' +
                CONFIG.logo.position.y + ' ' +
                CONFIG.logo.position.z
            );

            // Aplicar billboard se habilitado
            if (CONFIG.logo.billboard && CONFIG.logo.billboard.enabled) {
                logoContainer.setAttribute('billboard-camera',
                    'lockY: ' + (CONFIG.logo.billboard.lockY ? 'true' : 'false')
                );
            }

            // Criar grupo principal do logo
            const logoGroup = document.createElement('a-entity');
            logoGroup.setAttribute('id', 'logo-3d-group');
            logoGroup.setAttribute('scale',
                CONFIG.logo.size.scale + ' ' +
                CONFIG.logo.size.scale + ' ' +
                CONFIG.logo.size.scale
            );

            // === PLANO DE FUNDO (Backplane) ===
            if (CONFIG.logo.extrusion && CONFIG.logo.extrusion.enabled) {
                const backplane = document.createElement('a-box');
                backplane.setAttribute('width', CONFIG.logo.size.width + 0.4);
                backplane.setAttribute('height', CONFIG.logo.size.height + 0.2);
                backplane.setAttribute('depth', CONFIG.logo.extrusion.backplaneDepth);
                backplane.setAttribute('position', '0 0 ' + CONFIG.logo.extrusion.backplaneOffset);
                backplane.setAttribute('material',
                    'color: ' + CONFIG.logo.colors.background + '; ' +
                    'metalness: 0.3; ' +
                    'roughness: 0.7; ' +
                    'opacity: 0.95'
                );
                backplane.setAttribute('class', 'logo-backplane');

                // Bordas arredondadas simuladas com radius
                backplane.setAttribute('radius', '0.1');

                logoGroup.appendChild(backplane);
            }

            // === IMAGEM DO LOGO (SVG) ===
            const logoImage = document.createElement('a-image');
            logoImage.setAttribute('id', 'logo-3d-image');
            logoImage.setAttribute('src', (typeof gestor !== 'undefined' ? gestor.raiz : '') + CONFIG.logo.imagePath);
            logoImage.setAttribute('width', CONFIG.logo.size.width);
            logoImage.setAttribute('height', CONFIG.logo.size.height);
            logoImage.setAttribute('position', '0 0 ' + (CONFIG.logo.extrusion ? CONFIG.logo.extrusion.depth / 2 : 0.1));
            logoImage.setAttribute('material',
                'shader: flat; ' +
                'transparent: true; ' +
                'alphaTest: 0.5'
            );
            logoImage.setAttribute('class', 'logo-image');
            logoGroup.appendChild(logoImage);

            // === ELEMENTOS DE EXTRUSÃO 3D ===
            if (CONFIG.logo.extrusion && CONFIG.logo.extrusion.enabled) {
                // Criar bordas 3D extrudadas
                this.createLogoExtrusion(logoGroup, CONFIG);
            }

            // === APLICAR ANIMAÇÕES ===
            // Passa o logoContainer para animações que precisam dele (como float)
            this.applyLogoAnimations(logoGroup, logoContainer, CONFIG);

            // Adicionar grupo ao container
            logoContainer.appendChild(logoGroup);

            // Adicionar à cena
            scene.appendChild(logoContainer);

            console.log('Dashboard3DGeometry: Logo 3D criado com sucesso');
        },

        /**
         * Cria os elementos de extrusão do logo
         */
        createLogoExtrusion: function (logoGroup, CONFIG) {
            const ext = CONFIG.logo.extrusion;
            const size = CONFIG.logo.size;
            const colors = CONFIG.logo.colors;

            // Borda superior
            const topBorder = document.createElement('a-box');
            topBorder.setAttribute('width', size.width + 0.4);
            topBorder.setAttribute('height', 0.08);
            topBorder.setAttribute('depth', ext.depth);
            topBorder.setAttribute('position', '0 ' + (size.height / 2 + 0.1) + ' 0');
            topBorder.setAttribute('material',
                'color: ' + colors.symbol + '; ' +
                'emissive: ' + colors.symbol + '; ' +
                'emissiveIntensity: ' + colors.emissiveIntensity + '; ' +
                'metalness: ' + colors.metalness + '; ' +
                'roughness: ' + colors.roughness
            );
            logoGroup.appendChild(topBorder);

            // Borda inferior
            const bottomBorder = document.createElement('a-box');
            bottomBorder.setAttribute('width', size.width + 0.4);
            bottomBorder.setAttribute('height', 0.08);
            bottomBorder.setAttribute('depth', ext.depth);
            bottomBorder.setAttribute('position', '0 ' + (-size.height / 2 - 0.1) + ' 0');
            bottomBorder.setAttribute('material',
                'color: ' + colors.symbol + '; ' +
                'emissive: ' + colors.symbol + '; ' +
                'emissiveIntensity: ' + colors.emissiveIntensity + '; ' +
                'metalness: ' + colors.metalness + '; ' +
                'roughness: ' + colors.roughness
            );
            logoGroup.appendChild(bottomBorder);

            // Borda esquerda
            const leftBorder = document.createElement('a-box');
            leftBorder.setAttribute('width', 0.08);
            leftBorder.setAttribute('height', size.height + 0.2);
            leftBorder.setAttribute('depth', ext.depth);
            leftBorder.setAttribute('position', (-size.width / 2 - 0.2) + ' 0 0');
            leftBorder.setAttribute('material',
                'color: ' + colors.text + '; ' +
                'emissive: ' + colors.text + '; ' +
                'emissiveIntensity: ' + colors.emissiveIntensity * 0.5 + '; ' +
                'metalness: ' + colors.metalness + '; ' +
                'roughness: ' + colors.roughness
            );
            logoGroup.appendChild(leftBorder);

            // Borda direita
            const rightBorder = document.createElement('a-box');
            rightBorder.setAttribute('width', 0.08);
            rightBorder.setAttribute('height', size.height + 0.2);
            rightBorder.setAttribute('depth', ext.depth);
            rightBorder.setAttribute('position', (size.width / 2 + 0.2) + ' 0 0');
            rightBorder.setAttribute('material',
                'color: ' + colors.text + '; ' +
                'emissive: ' + colors.text + '; ' +
                'emissiveIntensity: ' + colors.emissiveIntensity * 0.5 + '; ' +
                'metalness: ' + colors.metalness + '; ' +
                'roughness: ' + colors.roughness
            );
            logoGroup.appendChild(rightBorder);

            // Cantos decorativos (esferas pequenas)
            const corners = [
                { x: -size.width / 2 - 0.2, y: size.height / 2 + 0.1 },
                { x: size.width / 2 + 0.2, y: size.height / 2 + 0.1 },
                { x: -size.width / 2 - 0.2, y: -size.height / 2 - 0.1 },
                { x: size.width / 2 + 0.2, y: -size.height / 2 - 0.1 }
            ];

            corners.forEach(function (corner, index) {
                const sphere = document.createElement('a-sphere');
                sphere.setAttribute('radius', 0.06);
                sphere.setAttribute('position', corner.x + ' ' + corner.y + ' ' + (ext.depth / 2));
                sphere.setAttribute('material',
                    'color: ' + (index < 2 ? colors.symbol : colors.text) + '; ' +
                    'emissive: ' + (index < 2 ? colors.symbol : colors.text) + '; ' +
                    'emissiveIntensity: 0.5; ' +
                    'metalness: 0.8; ' +
                    'roughness: 0.2'
                );
                logoGroup.appendChild(sphere);
            });
        },

        /**
         * Aplica animações ao logo baseado no animation.type
         * O tipo define qual animação aplicar, as configs definem os parâmetros
         */
        applyLogoAnimations: function (logoGroup, logoContainer, CONFIG) {
            const anim = CONFIG.logo.animation;
            if (!anim || !anim.enabled) return;

            // Usa o animation.type para decidir qual animação aplicar
            const animationType = anim.type || 'pulse';
            console.log('Dashboard3DGeometry: Aplicando animação do tipo:', animationType);

            switch (animationType) {
                case 'pulse':
                    this.applyPulseAnimation(logoGroup, CONFIG);
                    break;
                case 'glow':
                    this.applyGlowAnimation(logoGroup, CONFIG);
                    break;
                case 'float':
                    this.applyFloatAnimation(logoGroup, logoContainer, CONFIG);
                    break;
                case 'rotate':
                    this.applyRotateAnimation(logoGroup, CONFIG);
                    break;
                case 'shimmer':
                    this.applyShimmerAnimation(logoGroup, CONFIG);
                    break;
                default:
                    console.log('Dashboard3DGeometry: Tipo de animação desconhecido:', animationType);
            }
        },

        /**
         * Aplica animação Pulse (escala pulsante)
         */
        applyPulseAnimation: function (logoGroup, CONFIG) {
            const pulse = CONFIG.logo.animation.pulse;
            if (!pulse) return;

            logoGroup.setAttribute('animation__pulse',
                'property: scale; ' +
                'from: ' + (CONFIG.logo.size.scale * pulse.minScale) + ' ' +
                (CONFIG.logo.size.scale * pulse.minScale) + ' ' +
                (CONFIG.logo.size.scale * pulse.minScale) + '; ' +
                'to: ' + (CONFIG.logo.size.scale * pulse.maxScale) + ' ' +
                (CONFIG.logo.size.scale * pulse.maxScale) + ' ' +
                (CONFIG.logo.size.scale * pulse.maxScale) + '; ' +
                'dur: ' + pulse.duration + '; ' +
                'dir: alternate; ' +
                'loop: true; ' +
                'easing: ' + pulse.easing
            );
        },

        /**
         * Aplica animação Glow (brilho pulsante)
         */
        applyGlowAnimation: function (logoGroup, CONFIG) {
            const glow = CONFIG.logo.animation.glow;
            if (!glow) return;

            // Criar plano de glow
            const glowPlane = document.createElement('a-plane');
            glowPlane.setAttribute('id', 'logo-glow-anim');
            glowPlane.setAttribute('width', CONFIG.logo.size.width + 1);
            glowPlane.setAttribute('height', CONFIG.logo.size.height + 0.5);
            glowPlane.setAttribute('position', '0 0 ' + (CONFIG.logo.extrusion ? CONFIG.logo.extrusion.backplaneOffset - 0.1 : -0.2));
            glowPlane.setAttribute('material',
                'color: ' + glow.color + '; ' +
                'emissive: ' + glow.color + '; ' +
                'emissiveIntensity: ' + glow.minIntensity + '; ' +
                'opacity: 0.3; ' +
                'transparent: true; ' +
                'shader: flat'
            );

            // Animação de intensidade do glow
            glowPlane.setAttribute('animation__glow',
                'property: material.emissiveIntensity; ' +
                'from: ' + glow.minIntensity + '; ' +
                'to: ' + glow.maxIntensity + '; ' +
                'dur: ' + glow.duration + '; ' +
                'dir: alternate; ' +
                'loop: true; ' +
                'easing: easeInOutSine'
            );

            // Animação de opacidade
            glowPlane.setAttribute('animation__opacity',
                'property: material.opacity; ' +
                'from: 0.2; ' +
                'to: 0.5; ' +
                'dur: ' + glow.duration + '; ' +
                'dir: alternate; ' +
                'loop: true; ' +
                'easing: easeInOutSine'
            );

            logoGroup.appendChild(glowPlane);
            console.log('Dashboard3DGeometry: Animação glow aplicada');
        },

        /**
         * Aplica animação Float (flutuação suave)
         */
        applyFloatAnimation: function (logoGroup, logoContainer, CONFIG) {
            const float = CONFIG.logo.animation.float;
            if (!float) return;

            const axis = float.axis || 'y';
            const pos = CONFIG.logo.position;
            const amplitude = float.amplitude || 0.3;
            const fromPos = pos.x + ' ' + pos.y + ' ' + pos.z;
            let toPos;

            switch (axis) {
                case 'x':
                    toPos = (pos.x + amplitude) + ' ' + pos.y + ' ' + pos.z;
                    break;
                case 'z':
                    toPos = pos.x + ' ' + pos.y + ' ' + (pos.z + amplitude);
                    break;
                default: // y
                    toPos = pos.x + ' ' + (pos.y + amplitude) + ' ' + pos.z;
            }

            // Aplica animação diretamente no container passado como parâmetro
            logoContainer.setAttribute('animation__float',
                'property: position; ' +
                'from: ' + fromPos + '; ' +
                'to: ' + toPos + '; ' +
                'dur: ' + (float.duration || 4000) + '; ' +
                'dir: alternate; ' +
                'loop: true; ' +
                'easing: easeInOutSine'
            );
            console.log('Dashboard3DGeometry: Animação float aplicada');
        },

        /**
         * Aplica animação Rotate (rotação)
         */
        applyRotateAnimation: function (logoGroup, CONFIG) {
            const rotate = CONFIG.logo.animation.rotate;
            if (!rotate) return;

            if (rotate.oscillate) {
                const maxAngle = rotate.maxAngle;
                let fromRot, toRot;

                switch (rotate.axis) {
                    case 'x':
                        fromRot = (-maxAngle) + ' 0 0';
                        toRot = maxAngle + ' 0 0';
                        break;
                    case 'z':
                        fromRot = '0 0 ' + (-maxAngle);
                        toRot = '0 0 ' + maxAngle;
                        break;
                    default: // y
                        fromRot = '0 ' + (-maxAngle) + ' 0';
                        toRot = '0 ' + maxAngle + ' 0';
                }

                logoGroup.setAttribute('animation__rotate',
                    'property: rotation; ' +
                    'from: ' + fromRot + '; ' +
                    'to: ' + toRot + '; ' +
                    'dur: ' + (1000 / rotate.speed) + '; ' +
                    'dir: alternate; ' +
                    'loop: true; ' +
                    'easing: easeInOutSine'
                );
            } else {
                // Rotação contínua
                let toRot;
                switch (rotate.axis) {
                    case 'x':
                        toRot = '360 0 0';
                        break;
                    case 'z':
                        toRot = '0 0 360';
                        break;
                    default:
                        toRot = '0 360 0';
                }

                logoGroup.setAttribute('animation__rotate',
                    'property: rotation; ' +
                    'to: ' + toRot + '; ' +
                    'dur: ' + (360000 / rotate.speed) + '; ' +
                    'loop: true; ' +
                    'easing: linear'
                );
            }
        },

        /**
         * Aplica animação Shimmer (brilho percorrendo)
         */
        applyShimmerAnimation: function (logoGroup, CONFIG) {
            const shimmer = CONFIG.logo.animation.shimmer;
            if (!shimmer) return;

            const shimmerPlane = document.createElement('a-plane');
            shimmerPlane.setAttribute('id', 'logo-shimmer');
            shimmerPlane.setAttribute('width', 0.3);
            shimmerPlane.setAttribute('height', CONFIG.logo.size.height + 0.5);
            shimmerPlane.setAttribute('position', (-CONFIG.logo.size.width / 2 - 1) + ' 0 0.2');
            shimmerPlane.setAttribute('material',
                'color: ' + shimmer.color + '; ' +
                'opacity: ' + shimmer.intensity + '; ' +
                'transparent: true; ' +
                'shader: flat'
            );
            shimmerPlane.setAttribute('animation__shimmer',
                'property: position; ' +
                'from: ' + (-CONFIG.logo.size.width / 2 - 1) + ' 0 0.2; ' +
                'to: ' + (CONFIG.logo.size.width / 2 + 1) + ' 0 0.2; ' +
                'dur: ' + shimmer.speed + '; ' +
                'loop: true; ' +
                'easing: linear'
            );
            shimmerPlane.setAttribute('animation__shimmeropacity',
                'property: material.opacity; ' +
                'from: 0; ' +
                'to: ' + shimmer.intensity + '; ' +
                'dur: ' + (shimmer.speed / 4) + '; ' +
                'dir: alternate; ' +
                'loop: true; ' +
                'easing: easeInOutQuad'
            );

            logoGroup.appendChild(shimmerPlane);
        },

        /**
         * Remove o logo 3D da cena
         */
        removeLogo3D: function () {
            const logoContainer = document.getElementById('logo-3d-container');
            if (logoContainer) {
                logoContainer.parentNode.removeChild(logoContainer);
                console.log('Dashboard3DGeometry: Logo 3D removido');
            }
        },

        /**
         * Atualiza a posição do logo 3D
         */
        updateLogoPosition: function (x, y, z) {
            const logoContainer = document.getElementById('logo-3d-container');
            if (logoContainer) {
                logoContainer.setAttribute('position', x + ' ' + y + ' ' + z);
            }
        },

        /**
         * Atualiza a escala do logo 3D
         */
        updateLogoScale: function (scale) {
            const logoGroup = document.getElementById('logo-3d-group');
            if (logoGroup) {
                logoGroup.setAttribute('scale', scale + ' ' + scale + ' ' + scale);
            }
        }
    };

    // Exportar para o namespace global
    global.Dashboard3DGeometry = Dashboard3DGeometry;

})(window);
