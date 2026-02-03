/**
 * Dashboard 3D - Configurações
 * Arquivo de configurações globais do Dashboard 3D
 */

(function (global) {
    'use strict';

    const Dashboard3DConfig = {

        // Cores dos segmentos do anel (16 cores para escalabilidade)
        segmentColors: [
            '#4a9eff', // Azul
            '#6dd400', // Verde
            '#a855f7', // Roxo
            '#f97316', // Laranja
            '#14b8a6', // Ciano/Teal
            '#ec4899', // Rosa
            '#eab308', // Amarelo
            '#ef4444', // Vermelho
            '#06b6d4', // Cyan claro
            '#8b5cf6', // Violeta
            '#22c55e', // Verde esmeralda
            '#f43f5e', // Rosa vermelho
            '#0ea5e9', // Azul céu
            '#d946ef', // Fúcsia
            '#84cc16', // Lima
            '#64748b'  // Cinza azulado
        ],

        // Layout do anel - configuração dinâmica baseada em quantidade de grupos
        ring: {
            baseRadius: 6,        // Raio base para até 6 grupos
            radiusPerGroup: 0.5,  // Aumento de raio por grupo extra (acima de 6)
            tubeRadius: 0.3,
            height: 1.5,
            // Função para calcular raio baseado em quantidade de grupos
            getRadius: function (groupCount) {
                if (groupCount <= 6) return this.baseRadius;
                return this.baseRadius + ((groupCount - 6) * this.radiusPerGroup);
            }
        },

        // Esfera central (DESABILITADA - navegação simplificada)
        sphere: {
            enabled: false,     // Desabilitado para navegação simplificada
            radius: 1.2,
            segments: 32
        },

        // Cards
        cards: {
            width: 2.2,
            height: 1.6,
            depth: 0.08,
            depthMarginBoard: 0.8,
            baseDistanceFromRing: 4,
            noiseAmount: 0.3,
            hoverScale: 1.15,
            // Limites de texto
            text: {
                titleMaxLength: 20,       // Máximo de caracteres no título
                titleMaxWidth: 1.8,       // Largura máxima para quebra de linha
                descriptionMaxLength: 50, // Máximo de caracteres na descrição
                descriptionMaxWidth: 2.0, // Largura máxima para quebra de linha
                lineBreakChar: 15         // Caracteres por linha antes de quebrar
            },
            // Imagem destaque
            thumbnail: {
                enabled: false, // Desabilitado por padrão (ativar quando houver imagens reais)
                width: 1.8,
                height: 0.8,
                yOffset: 0.25,
                // Usar placehold.co que suporta requisições HEAD
                defaultUrl: 'https://placehold.co/200x100/1a1a2e/4a9eff?text='
            },
            // Botões de ação
            buttons: {
                enabled: true,
                height: 0.2,
                spacing: 0.05
            },
            // Função para calcular distância baseado em quantidade de grupos
            getDistanceFromRing: function (groupCount) {
                if (groupCount <= 6) return this.baseDistanceFromRing;
                // Aumentar distância para grupos maiores
                return this.baseDistanceFromRing + ((groupCount - 6) * 0.3);
            }
        },

        // Câmera - ajuste dinâmico baseado em grupos (target fixo no centro)
        camera: {
            initialPosition: { x: 0, y: 3, z: -25 },
            initialTarget: { x: 0, y: 6, z: 0 },  // Target fixo no centro
            minDistance: 0.0000001,
            maxDistance: 10,
            autoRotateSpeed: 0.003,
            zoomStep: 1000,  // Valores < 1 = zoom agressivo (0.1 = 10x força)
            // Função para calcular posição inicial baseado em grupos
            getInitialZ: function (groupCount) {
                if (groupCount <= 6) return -20;
                return -20 - ((groupCount - 6) * 2);
            }
        },

        // Animações
        animation: {
            duration: 800
        },

        // Escalabilidade de grupos
        scalability: {
            maxGroupsPerRing: 16,       // Máximo de grupos em um único anel
            enableDoubleRing: true,     // Habilitar segundo anel para >16 grupos
            doubleRingInnerRadius: 4,   // Raio do anel interno (se double ring)
            doubleRingOuterRadius: 10   // Raio do anel externo (se double ring)
        },

        // Volumes prismáticos para distribuição de cards
        prism: {
            enabled: true,              // Usar distribuição prismática
            debug: false,               // Mostrar volumes dos prismas
            debugOpacity: 0.05,         // Opacidade do prisma em debug
            baseWidth: 5,               // Largura da base do prisma (aumentado)
            tipOffset: 0,               // Offset lateral da ponta (0 = centralizado)
            length: 11,                 // Comprimento do prisma (distância radial) - 3x
            heightMin: 0.5,             // Altura mínima do prisma
            heightMax: 10,              // Altura máxima do prisma - 3x (era 4)
            verticalLayers: 5,          // Camadas verticais para distribuir cards
            horizontalCards: 4,         // Cards por linha horizontal no plano
            cardSpacing: 0.3,           // Espaçamento entre cards na mesma camada
            // Calcula comprimento baseado em grupos
            getLength: function (groupCount) {
                if (groupCount <= 6) return this.length;
                return this.length + ((groupCount - 6) * 0.8);
            },
            // Calcula largura da base baseado em quantidade de grupos
            getBaseWidth: function (groupCount) {
                const anglePerSegment = (Math.PI * 2) / groupCount;
                // Base proporcional ao arco do segmento
                return Math.max(3, anglePerSegment * 5);
            }
        },

        // Plano/Quadro do grupo (lousa 3D)
        groupBoard: {
            enabled: true,              // Mostrar quadro do grupo
            width: 12,                   // Largura do quadro
            height: 8,                  // Altura do quadro
            depth: 0.25,                // Profundidade (espessura)
            borderWidth: 0.15,          // Largura da borda
            cornerRadius: 0.2,          // Raio dos cantos (visual)
            titleFontSize: 0.25,        // Tamanho da fonte do título
            titleOffsetY: 0.3,          // Offset Y do título (do topo)
            backgroundOpacity: 0.85,    // Opacidade do fundo
            borderEmissive: 0.6,        // Intensidade emissiva da borda
            glowIntensity: 0.4          // Intensidade do brilho
        },

        // Ícones dos módulos
        icons: {
            'file': '📄', 'users': '👥', 'user': '👤', 'cog': '⚙️', 'folder': '📁',
            'database': '🗄️', 'globe': '🌐', 'lock': '🔒', 'envelope': '✉️', 'chart': '📊',
            'box': '📦', 'code': '💻', 'image': '🖼️', 'video': '🎬', 'calendar': '📅',
            'clock': '⏰', 'star': '⭐', 'heart': '❤️', 'flag': '🚩', 'tag': '🏷️',
            'link': '🔗', 'upload': '📤', 'download': '📥', 'settings': '🔧', 'default': '📋'
        },

        // Logo 3D Conn2Flow
        logo: {
            enabled: true,                      // Habilitar/desabilitar logo
            imagePath: 'images/Logomarca.svg',  // Caminho relativo à raiz

            // Posicionamento
            position: {
                x: 0,                           // Posição X (0 = centralizado)
                y: 16,                          // Posição Y (altura - acima do quadro)
                z: 17                            // Posição Z (profundidade)
            },

            // Dimensões
            size: {
                width: 8,                       // Largura do logo
                height: 3.5,                    // Altura do logo
                scale: 1.0                      // Escala geral
            },

            // Extrusão 3D
            extrusion: {
                enabled: true,                  // Habilitar extrusão
                depth: 0.3,                     // Profundidade da extrusão principal
                backplaneDepth: 0.1,            // Profundidade do plano de fundo
                backplaneOffset: -0.2,          // Offset Z do plano de fundo
                bevelEnabled: true,             // Habilitar bisel
                bevelThickness: 0.02,           // Espessura do bisel
                bevelSize: 0.02                 // Tamanho do bisel
            },

            // Cores e materiais
            colors: {
                symbol: '#1babc6',              // Cor do símbolo (ciano)
                text: '#1e2d4a',                // Cor do texto (azul escuro)
                background: '#e6e6e6',          // Cor do fundo do SVG
                emissiveIntensity: 0.3,         // Intensidade emissiva
                metalness: 0.6,                 // Metalicidade
                roughness: 0.3                  // Rugosidade
            },

            // Billboard (sempre de frente para câmera)
            billboard: {
                enabled: true,                  // Habilitar billboard
                lockY: false                    // Travar rotação no eixo Y apenas
            },

            // Animações
            animation: {
                enabled: true,                  // Habilitar animações
                type: 'pulse',                  // Tipo: 'pulse', 'glow', 'float', 'rotate', 'shimmer'

                // Animação Pulse (escala pulsante)
                pulse: {
                    enabled: true,
                    minScale: 0.98,             // Escala mínima
                    maxScale: 1.02,             // Escala máxima 
                    duration: 2000,             // Duração em ms
                    easing: 'easeInOutSine'     // Tipo de easing
                },

                // Animação Glow (brilho pulsante)
                glow: {
                    enabled: false,
                    minIntensity: 0.2,          // Intensidade mínima
                    maxIntensity: 0.5,          // Intensidade máxima
                    duration: 3000,             // Duração em ms
                    color: '#4a9eff'            // Cor do glow
                },

                // Animação Float (flutuar suavemente)
                float: {
                    enabled: false,
                    amplitude: 0.1,             // Amplitude do movimento
                    duration: 4000,             // Duração em ms
                    axis: 'y'                   // Eixo do movimento
                },

                // Animação Rotate (rotação suave)
                rotate: {
                    enabled: true,
                    axis: 'y',                  // Eixo de rotação
                    speed: 0.5,                 // Velocidade (graus por frame)
                    oscillate: true,            // Oscilar ao invés de rotação contínua
                    maxAngle: 5                 // Ângulo máximo se oscillate=true
                },

                // Animação Shimmer (brilho percorrendo)
                shimmer: {
                    enabled: true,
                    speed: 2000,                // Velocidade do shimmer
                    color: '#ffffff',           // Cor do shimmer
                    intensity: 0.8              // Intensidade do shimmer
                }
            },

            // Sombra
            shadow: {
                enabled: true,                  // Habilitar sombra
                opacity: 0.3,                   // Opacidade da sombra
                blur: 0.5,                      // Blur da sombra
                offsetX: 0.1,                   // Offset X
                offsetY: -0.1                   // Offset Y
            }
        }
    };

    // Exportar para o namespace global
    global.Dashboard3DConfig = Dashboard3DConfig;

})(window);
