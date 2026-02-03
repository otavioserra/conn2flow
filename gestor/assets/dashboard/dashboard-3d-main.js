/**
 * Dashboard 3D - Orquestrador Principal
 * Coordena todos os módulos do Dashboard 3D
 * 
 * Dependências (devem ser carregados antes deste arquivo):
 * - dashboard-3d-config.js - Configurações
 * - dashboard-3d-camera.js - Controles de câmera
 * - dashboard-3d-geometry.js - Geometria 3D
 * - dashboard-3d-cards.js - Cards dos módulos
 * - dashboard-3d-ui.js - Interface e interações
 */

(function (global) {
    'use strict';

    // =========================================
    // Estado Global
    // =========================================

    var modulesData = [];
    var groupsData = [];
    var scene = null;
    var mainCamera = null;

    // =========================================
    // Inicialização
    // =========================================

    function init() {
        console.log('Dashboard 3D: Inicializando orquestrador...');

        // Referências aos módulos
        var CONFIG = global.Dashboard3DConfig;
        var Camera = global.Dashboard3DCamera;
        var Geometry = global.Dashboard3DGeometry;
        var Cards = global.Dashboard3DCards;
        var UI = global.Dashboard3DUI;

        // Verificar se todos os módulos estão carregados
        if (!CONFIG || !Camera || !Geometry || !Cards || !UI) {
            console.error('Dashboard 3D: Um ou mais módulos não foram carregados');
            console.log('Módulos:', {
                CONFIG: !!CONFIG,
                Camera: !!Camera,
                Geometry: !!Geometry,
                Cards: !!Cards,
                UI: !!UI
            });
            hideLoading();
            return;
        }

        scene = document.querySelector('a-scene');

        if (!scene) {
            console.error('Dashboard 3D: Cena A-Frame não encontrada');
            hideLoading();
            return;
        }

        console.log('Dashboard 3D: Cena encontrada, verificando se carregou...');

        // Timeout de fallback caso o evento loaded nunca dispare
        var fallbackTimeout = setTimeout(function () {
            console.warn('Dashboard 3D: Timeout - forçando inicialização');
            if (!scene._dashboard3dLoaded) {
                onSceneLoaded();
            }
        }, 3000);

        if (scene.hasLoaded) {
            console.log('Dashboard 3D: Cena já estava carregada');
            clearTimeout(fallbackTimeout);
            onSceneLoaded();
        } else {
            console.log('Dashboard 3D: Aguardando evento loaded...');
            scene.addEventListener('loaded', function () {
                console.log('Dashboard 3D: Evento loaded recebido');
                clearTimeout(fallbackTimeout);
                onSceneLoaded();
            });
        }
    }

    function onSceneLoaded() {
        // Evitar execução dupla
        if (scene._dashboard3dLoaded) {
            console.log('Dashboard 3D: Já foi carregado, ignorando');
            return;
        }
        scene._dashboard3dLoaded = true;

        console.log('Dashboard 3D: Cena A-Frame carregada, criando elementos...');

        // Referências aos módulos
        var CONFIG = global.Dashboard3DConfig;
        var Camera = global.Dashboard3DCamera;
        var Geometry = global.Dashboard3DGeometry;
        var Cards = global.Dashboard3DCards;
        var UI = global.Dashboard3DUI;

        // Guardar referência da câmera
        mainCamera = document.getElementById('main-camera');

        try {
            // Carregar dados dos módulos
            loadModulesData();
            console.log('Dashboard 3D: Dados carregados');

            // Registrar componente custom-line
            Geometry.registerCustomLineComponent();

            // Registrar componente billboard-camera (mais robusto que look-at)
            Geometry.registerBillboardComponent();

            // Registrar componente prism-geometry (para debug de volumes prismáticos)
            Geometry.registerPrismGeometryComponent();

            // Inicializar módulo de câmera
            Camera.init(mainCamera);
            console.log('Dashboard 3D: Câmera inicializada');

            // Criar geometria 3D
            Geometry.createCentralSphere(groupsData);
            console.log('Dashboard 3D: Esfera central criada');

            Geometry.createRingSegments(groupsData);
            console.log('Dashboard 3D: Anel criado');

            // Criar cards dos módulos
            Cards.createCards(modulesData, groupsData);
            console.log('Dashboard 3D: Cards criados');

            // Criar quadros/painéis de grupo (lousas 3D)
            Geometry.createGroupBoards(groupsData);
            console.log('Dashboard 3D: Quadros de grupo criados');

            Cards.createConnections(groupsData);
            console.log('Dashboard 3D: Conexões criadas');

            // Criar partículas de fundo
            Geometry.createParticles();
            console.log('Dashboard 3D: Partículas criadas');

            // Criar logo 3D Conn2Flow
            Geometry.createLogo3D();
            console.log('Dashboard 3D: Logo 3D criado');

            // Inicializar UI com dados
            UI.init(modulesData, groupsData);
            UI.createLegend();
            console.log('Dashboard 3D: Legenda criada');

            // Configurar event listeners
            UI.setupEventListeners();
            console.log('Dashboard 3D: Event listeners configurados');

            // Inicializar orbit-controls após tudo carregar
            Camera.initializeOrbitControls();

            setTimeout(function () {
                UI.hideLoading();
                console.log('Dashboard 3D: Loading escondido - PRONTO!');
            }, 300);

        } catch (error) {
            console.error('Dashboard 3D: Erro durante inicialização:', error);
            hideLoading();
        }
    }

    // =========================================
    // Carregar Dados
    // =========================================

    function loadModulesData() {
        var dataEl = document.getElementById('dashboard-3d-data');
        if (dataEl) {
            try {
                var data = JSON.parse(dataEl.textContent.trim());
                modulesData = data.modules || [];
                groupsData = data.groups || [];
                console.log('Dashboard 3D: Dados carregados', { modules: modulesData.length, groups: groupsData.length });
            } catch (e) {
                console.error('Dashboard 3D: Erro ao carregar dados:', e);
                createSampleData();
            }
        } else {
            console.warn('Dashboard 3D: Elemento de dados não encontrado');
            createSampleData();
        }
    }

    function createSampleData() {
        groupsData = [
            { id: 'admin-sistema', nome: 'Administração de Sistema', descricao: 'Configurações globais' },
            { id: 'admin-usuarios', nome: 'Administração de Usuários', descricao: 'Contas e permissões' },
            { id: 'publicador', nome: 'Publicador', descricao: 'Gerenciar conteúdo' },
            { id: 'relatorios', nome: 'Relatórios', descricao: 'Analytics e dashboards' },
            { id: 'configuracoes', nome: 'Configurações', descricao: 'Parâmetros do sistema' },
            { id: 'integracoes', nome: 'Integrações', descricao: 'APIs e webhooks' }
        ];

        modulesData = [
            { id: 'arquivos', nome: 'Arquivos', grupo: 'admin-sistema', link: '#', icon: 'folder', descricao: 'Gerenciar arquivos' },
            { id: 'plugins', nome: 'Plugins', grupo: 'admin-sistema', link: '#', icon: 'box', descricao: 'Instalar plugins' },
            { id: 'usuarios', nome: 'Usuários', grupo: 'admin-usuarios', link: '#', icon: 'users', descricao: 'Gerenciar usuários' },
            { id: 'perfis', nome: 'Perfis', grupo: 'admin-usuarios', link: '#', icon: 'user', descricao: 'Perfis de acesso' }
        ];
    }

    // =========================================
    // Utilitário
    // =========================================

    function hideLoading() {
        var overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(function () {
                overlay.classList.add('hidden');
            }, 500);
        }
    }

    // =========================================
    // Iniciar
    // =========================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window);
