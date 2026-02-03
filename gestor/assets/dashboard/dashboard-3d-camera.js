/**
 * Dashboard 3D - Controles de Câmera
 * Gerencia zoom, reset, focus e animações de câmera
 */

(function (global) {
    'use strict';

    const Dashboard3DCamera = {
        // Referências
        mainCamera: null,      // Elemento A-Frame da câmera
        threeCamera: null,     // Câmera THREE.js (movida pelo orbit-controls)
        autoRotate: false,

        /**
         * Inicializa as referências da câmera
         * @param {Element} [camera] - Elemento da câmera (opcional)
         */
        init: function (camera) {
            this.mainCamera = camera || document.getElementById('main-camera');
            // IMPORTANTE: orbit-controls move getObject3D('camera'), não object3D
            if (this.mainCamera) {
                this.threeCamera = this.mainCamera.getObject3D('camera');
            }
            console.log('Dashboard3DCamera: Inicializado', this.threeCamera ? '(THREE camera OK)' : '(THREE camera NOT FOUND)');
        },

        /**
         * Obtém a câmera THREE.js (a que é realmente movida pelo orbit-controls)
         */
        getThreeCamera: function () {
            if (!this.threeCamera && this.mainCamera) {
                this.threeCamera = this.mainCamera.getObject3D('camera');
            }
            return this.threeCamera;
        },

        /**
         * Obtém o componente orbit-controls da câmera
         */
        getOrbitControls: function () {
            if (!this.mainCamera) return null;
            const component = this.mainCamera.components['orbit-controls'];
            return component ? component.controls : null;
        },

        /**
         * Inicializa os orbit-controls e salva o estado inicial
         */
        initializeOrbitControls: function () {
            const self = this;
            const CONFIG = global.Dashboard3DConfig;

            // Aguardar um pouco para garantir que o componente foi inicializado
            setTimeout(function () {
                const controls = self.getOrbitControls();
                if (controls) {
                    // Configurar limites
                    controls.minDistance = CONFIG.camera.minDistance;
                    controls.maxDistance = CONFIG.camera.maxDistance;

                    // Salvar estado inicial para reset funcionar corretamente
                    controls.saveState();

                    // Listener para atualizar indicador de zoom
                    controls.addEventListener('change', function () {
                        self.updateZoomIndicator();
                    });

                    // Atualizar indicador inicial
                    self.updateZoomIndicator();

                    console.log('Dashboard3DCamera: OrbitControls inicializado e estado salvo');
                } else {
                    console.warn('Dashboard3DCamera: OrbitControls não encontrado');
                }
            }, 100);
        },

        /**
         * Restaura os controles de órbita ao estado funcional
         * Útil após animações ou fechamento de modais
         */
        restoreControls: function () {
            const controls = this.getOrbitControls();
            if (!controls) {
                console.warn('Dashboard3DCamera: OrbitControls não encontrado para restaurar');
                return;
            }

            // Garantir que controles estão habilitados
            controls.enabled = true;

            // Forçar atualização do estado interno
            controls.update();

            // Atualizar indicador
            this.updateZoomIndicator();

            console.log('Dashboard3DCamera: Controles restaurados');
        },

        /**
         * Zoom da câmera (aproximar/afastar)
         * @param {number} delta - Valor positivo para aproximar, negativo para afastar
         */
        zoomCamera: function (delta) {
            const controls = this.getOrbitControls();
            const threeCamera = this.getThreeCamera();
            const CONFIG = global.Dashboard3DConfig;

            console.log('zoomCamera');

            if (!controls || !threeCamera) {
                console.warn('Dashboard3DCamera: OrbitControls ou câmera THREE não encontrados');
                return;
            }

            // Obter posição atual da câmera THREE.js e target
            const cameraPos = threeCamera.position;
            const target = controls.target;

            // Calcular distância atual
            const currentDistance = cameraPos.distanceTo(target);

            // Algoritmo de zoom inteligente: quanto menor o zoomStep, maior o efeito
            // Valores < 1 têm efeito exponencialmente maior
            let zoomFactor;
            if (Math.abs(CONFIG.camera.zoomStep) < 1) {
                // Valores pequenos = zoom muito agressivo
                zoomFactor = delta > 0 ?
                    CONFIG.camera.zoomStep : // Zoom in: usar o próprio valor (0.1 = 10% da distância)
                    1 / CONFIG.camera.zoomStep; // Zoom out: inverso (0.1 -> 10x a distância)
            } else {
                // Valores normais = zoom padrão
                zoomFactor = delta > 0 ? 0.7 : 1.4;
            }

            const newDistance = Math.max(
                CONFIG.camera.minDistance,
                Math.min(CONFIG.camera.maxDistance, currentDistance * zoomFactor)
            );

            // Se a distância não mudou significativamente, sair
            if (Math.abs(newDistance - currentDistance) < CONFIG.camera.minDistance) {
                console.log('Dashboard3DCamera: Limite de zoom atingido');
                return;
            }

            // Calcular direção da câmera para o target
            const direction = new THREE.Vector3();
            direction.subVectors(cameraPos, target).normalize();

            // Definir nova posição da câmera
            const newPos = new THREE.Vector3();
            newPos.copy(target).add(direction.multiplyScalar(newDistance));

            // Aplicar nova posição na câmera THREE.js
            cameraPos.copy(newPos);

            // Atualizar controles
            controls.update();

            // Atualizar indicador de zoom
            this.updateZoomIndicator();

            console.log('Dashboard3DCamera: Zoom', delta > 0 ? 'in' : 'out', 'distance:', newDistance.toFixed(2));
        },

        /**
         * Reseta a view para a posição inicial
         */
        resetView: function () {
            const controls = this.getOrbitControls();
            const threeCamera = this.getThreeCamera();
            const CONFIG = global.Dashboard3DConfig;
            const self = this;

            if (!controls || !threeCamera) {
                console.warn('Dashboard3DCamera: Controles ou câmera THREE não encontrados para reset');
                return;
            }

            // Desativar auto-rotação se estiver ativa
            if (this.autoRotate) {
                this.toggleAutoRotation();
            }

            const initialTarget = CONFIG.camera.initialTarget;
            const targetPos = CONFIG.camera.initialPosition;

            if (typeof gsap !== 'undefined') {
                // Clonar valores atuais da câmera THREE.js para animação
                const currentCamPos = {
                    x: threeCamera.position.x,
                    y: threeCamera.position.y,
                    z: threeCamera.position.z
                };
                const currentTarget = {
                    x: controls.target.x,
                    y: controls.target.y,
                    z: controls.target.z
                };

                // Desabilitar controles durante animação
                controls.enabled = false;

                // Timeline GSAP para coordenar animações
                const tl = gsap.timeline({
                    onUpdate: function () {
                        // Atualizar posição da câmera THREE.js
                        threeCamera.position.set(currentCamPos.x, currentCamPos.y, currentCamPos.z);
                        // Atualizar target
                        controls.target.set(currentTarget.x, currentTarget.y, currentTarget.z);
                        controls.update();
                    },
                    onComplete: function () {
                        // Reativar controles após animação
                        controls.enabled = true;
                        controls.update();

                        // Salvar estado final como o novo estado padrão
                        controls.saveState();

                        // Atualizar indicador
                        self.updateZoomIndicator();

                        console.log('Dashboard3DCamera: View resetada - controles reativados');
                    }
                });

                // Animar câmera
                tl.to(currentCamPos, {
                    x: targetPos.x,
                    y: targetPos.y,
                    z: targetPos.z,
                    duration: 0.8,
                    ease: 'power2.out'
                }, 0);

                // Animar target
                tl.to(currentTarget, {
                    x: initialTarget.x,
                    y: initialTarget.y,
                    z: initialTarget.z,
                    duration: 0.8,
                    ease: 'power2.out'
                }, 0);

            } else {
                // Sem GSAP, aplicar diretamente na câmera THREE.js
                threeCamera.position.set(targetPos.x, targetPos.y, targetPos.z);
                controls.target.set(initialTarget.x, initialTarget.y, initialTarget.z);
                controls.update();
                controls.saveState();
                this.updateZoomIndicator();
            }

            console.log('Dashboard3DCamera: Iniciando reset de view');
        },

        /**
         * Foca em um card específico - Rotaciona e dá zoom até o card
         * O target permanece fixo no centro, apenas a câmera se move
         * @param {Element} card - Elemento do card
         */
        zoomToCard: function (card) {
            const controls = this.getOrbitControls();
            const threeCamera = this.getThreeCamera();
            const CONFIG = global.Dashboard3DConfig;
            const self = this;

            if (!controls || !threeCamera) return;

            const pos = card.getAttribute('position');
            if (!pos) return;

            const cardPos = AFRAME.utils.coordinates.parse(pos);

            // Calcular ângulo do card em relação ao centro
            const angle = Math.atan2(cardPos.z, cardPos.x);

            // Distância do card ao centro (para calcular posição da câmera)
            const cardDistanceFromCenter = Math.sqrt(cardPos.x * cardPos.x + cardPos.z * cardPos.z);

            // A câmera deve ficar atrás do centro, olhando para o card
            // Posição oposta ao card, com zoom aproximado
            const cameraDistance = cardDistanceFromCenter + CONFIG.camera.minDistance;
            return;

            // Nova posição da câmera (do lado oposto ao card)
            const newCamPos = {
                x: -Math.cos(angle) * cameraDistance,
                y: cardPos.y + 2,  // Um pouco acima do card
                z: -Math.sin(angle) * cameraDistance
            };

            // Target permanece fixo no centro (navegação simplificada)
            const fixedTarget = CONFIG.camera.initialTarget || { x: 0, y: 6, z: 0 };

            if (typeof gsap !== 'undefined') {
                // Pegar posição da câmera THREE.js
                const currentCamPos = {
                    x: threeCamera.position.x,
                    y: threeCamera.position.y,
                    z: threeCamera.position.z
                };

                // Desabilitar controles durante animação
                controls.enabled = false;

                // Timeline GSAP
                const tl = gsap.timeline({
                    onUpdate: function () {
                        // Atualizar câmera THREE.js
                        threeCamera.position.set(currentCamPos.x, currentCamPos.y, currentCamPos.z);
                        // Manter target fixo no centro
                        controls.target.set(fixedTarget.x, fixedTarget.y, fixedTarget.z);
                        controls.update();
                    },
                    onComplete: function () {
                        controls.enabled = true;
                        controls.update();
                        self.updateZoomIndicator();
                        console.log('Dashboard3DCamera: Rotação até card completa');
                    }
                });

                tl.to(currentCamPos, {
                    x: newCamPos.x,
                    y: newCamPos.y,
                    z: newCamPos.z,
                    duration: 0.8,
                    ease: 'power2.inOut'
                }, 0);

            } else {
                threeCamera.position.set(newCamPos.x, newCamPos.y, newCamPos.z);
                controls.target.set(fixedTarget.x, fixedTarget.y, fixedTarget.z);
                controls.update();
            }
        },

        /**
         * Foca em um grupo específico
         * @param {string} groupId - ID do grupo
         * @param {number} groupIndex - Índice do grupo
         * @param {Array} groupsData - Dados dos grupos
         */
        focusOnGroup: function (groupId, groupIndex, groupsData) {
            const controls = this.getOrbitControls();
            const threeCamera = this.getThreeCamera();
            const CONFIG = global.Dashboard3DConfig;
            const self = this;

            if (!controls || !threeCamera) return;

            const segmentCount = Math.min(groupsData.length, CONFIG.segmentColors.length);
            const anglePerSegment = (Math.PI * 2) / segmentCount;
            const groupAngle = (groupIndex * anglePerSegment) - (Math.PI / 2) + (anglePerSegment / 2);

            // Posição do grupo (para calcular ângulo)
            const ringRadius = CONFIG.ring.getRadius ? CONFIG.ring.getRadius(groupsData.length) : CONFIG.ring.baseRadius;
            const groupX = Math.cos(groupAngle) * (ringRadius + 2);
            const groupZ = Math.sin(groupAngle) * (ringRadius + 2);

            // Distância do grupo ao centro
            const groupDistanceFromCenter = Math.sqrt(groupX * groupX + groupZ * groupZ);

            // Posição da câmera: oposta ao grupo, com zoom aproximado
            const cameraDistance = 25;
            const newCamPos = {
                x: -Math.cos(groupAngle) * cameraDistance,
                y: 3,  // Altura fixa
                z: -Math.sin(groupAngle) * cameraDistance
            };

            // Target permanece fixo no centro (sem movimento)
            const fixedTarget = CONFIG.camera.initialTarget;

            if (typeof gsap !== 'undefined') {
                const currentCamPos = {
                    x: threeCamera.position.x,
                    y: threeCamera.position.y,
                    z: threeCamera.position.z
                };

                controls.enabled = false;

                const tl = gsap.timeline({
                    onUpdate: function () {
                        threeCamera.position.set(currentCamPos.x, currentCamPos.y, currentCamPos.z);
                        // Manter target fixo
                        controls.target.set(fixedTarget.x, fixedTarget.y, fixedTarget.z);
                        controls.update();
                    },
                    onComplete: function () {
                        controls.enabled = true;
                        controls.update();
                        self.updateZoomIndicator();
                        console.log('Dashboard3DCamera: Focus on group (rotação apenas) completo');
                    }
                });

                tl.to(currentCamPos, {
                    x: newCamPos.x,
                    y: newCamPos.y,
                    z: newCamPos.z,
                    duration: 0.8,
                    ease: 'power2.out'
                }, 0);

            } else {
                threeCamera.position.set(newCamPos.x, newCamPos.y, newCamPos.z);
                controls.target.set(fixedTarget.x, fixedTarget.y, fixedTarget.z);
                controls.update();
            }

            console.log('Dashboard3DCamera: Focus on group (rotação)', groupId);
        },

        /**
         * Toggle auto-rotação
         */
        toggleAutoRotation: function () {
            this.autoRotate = !this.autoRotate;
            const CONFIG = global.Dashboard3DConfig;

            const controls = this.getOrbitControls();
            if (!controls) return;

            if (this.autoRotate) {
                // Iniciar rotação automática
                const rotate = () => {
                    if (!this.autoRotate) return; // Parar se desativado

                    const threeCamera = this.getThreeCamera();
                    const controls = this.getOrbitControls();
                    if (!threeCamera || !controls) return;

                    const target = controls.target;
                    const cameraPos = threeCamera.position.clone().sub(target); // Posição relativa ao target

                    // Calcular novo ângulo
                    const currentAngle = Math.atan2(cameraPos.z, cameraPos.x);
                    const newAngle = currentAngle + CONFIG.camera.autoRotateSpeed; // Velocidade de rotação (aumente se necessário)

                    // Manter distância
                    const distance = cameraPos.length();

                    // Nova posição
                    const newX = Math.cos(newAngle) * distance;
                    const newZ = Math.sin(newAngle) * distance;

                    threeCamera.position.set(target.x + newX, threeCamera.position.y, target.z + newZ);
                    controls.update();

                    requestAnimationFrame(rotate);
                };
                rotate();
            }
            // Se desativado, apenas para (não faz nada extra)

            // Atualizar botão
            const btn = document.getElementById('btn-toggle-rotation');
            if (btn) btn.classList.toggle('active', this.autoRotate);

            console.log('Dashboard3DCamera: Auto-rotação', this.autoRotate ? 'ON' : 'OFF');
        },

        /**
         * Toggle fullscreen
         */
        toggleFullscreen: function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        },

        /**
         * Atualiza o indicador de zoom
         */
        updateZoomIndicator: function () {
            const controls = this.getOrbitControls();
            const CONFIG = global.Dashboard3DConfig;

            if (!controls) return;

            const indicator = document.getElementById('zoom-level');
            if (!indicator) return;

            const distance = controls.getDistance();
            const minDist = CONFIG.camera.minDistance;
            const maxDist = CONFIG.camera.maxDistance;

            // Calcular percentual (invertido: mais perto = maior zoom)
            const zoomPercent = Math.round(100 * (1 - (distance - minDist) / (maxDist - minDist)));
            indicator.textContent = Math.max(10, Math.min(200, zoomPercent + 50)) + '%';
        }
    };

    // Exportar para o namespace global
    global.Dashboard3DCamera = Dashboard3DCamera;

})(window);
