# Módulo: dashboard

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `dashboard` |
| **Nome** | Painel de Controle |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Funcional Core |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **dashboard** é o **centro de controle principal** do sistema Conn2Flow CMS. Funciona como o ponto de entrada para administradores após o login, oferecendo uma visão geral do sistema, widgets informativos, notificações e acesso rápido às funcionalidades mais utilizadas.

## 🏗️ Funcionalidades Principais

### 🏠 **Painel Principal**
- **Página inicial administrativa**: Landing page pós-login
- **Widgets de resumo**: Estatísticas em tempo real
- **Acesso rápido**: Links para módulos mais utilizados
- **Notificações centralizadas**: Sistema de toasts e alertas
- **Status do sistema**: Informações de saúde e performance

### 🔔 **Sistema de Notificações (Toasts)**
- **Toasts inteligentes**: Notificações contextuais
- **Tempo configurável**: Duração personalizável
- **Botões de ação**: Interações diretas nas notificações
- **Regras específicas**: Lógica customizada por tipo
- **Persistência de preferências**: Lembrar escolhas do usuário

### 🔄 **Sistema de Atualizações**
- **Verificação automática**: Check de versões disponíveis
- **Notificação de updates**: Alertas não intrusivos
- **Gestão de permissões**: Apenas admins veem updates
- **Comparação de versões**: Controle inteligente de versionamento
- **Redirecionamento automatizado**: Fluxo guiado de atualização

### 🧪 **Ambiente de Testes**
- **Dashboard de testes**: Ambiente isolado para desenvolvimento
- **Pré-publicação**: Área de staging
- **Validação de recursos**: Testes antes do deploy

## 📊 Interface de Usuário

### 🏠 **Layout Principal**
```html
<div class="dashboard-container">
    <!-- Header com informações do usuário -->
    <div class="dashboard-header">
        <h1>Bem-vindo ao Conn2Flow</h1>
        <div class="user-info">
            <span class="username">{{nome_usuario}}</span>
            <a href="#" class="logout">Sair</a>
        </div>
    </div>
    
    <!-- Grid de widgets -->
    <div class="dashboard-widgets">
        <div class="widget-row">
            <div class="widget statistics">
                <h3>Estatísticas Gerais</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="number">{{total_paginas}}</span>
                        <span class="label">Páginas</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">{{total_posts}}</span>
                        <span class="label">Posts</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">{{total_arquivos}}</span>
                        <span class="label">Arquivos</span>
                    </div>
                </div>
            </div>
            
            <div class="widget quick-actions">
                <h3>Ações Rápidas</h3>
                <div class="actions-grid">
                    <a href="admin-paginas/adicionar/" class="action-button">
                        <i class="plus icon"></i>
                        Nova Página
                    </a>
                    <a href="postagens/adicionar/" class="action-button">
                        <i class="edit icon"></i>
                        Novo Post
                    </a>
                    <a href="admin-arquivos/" class="action-button">
                        <i class="upload icon"></i>
                        Upload Arquivo
                    </a>
                </div>
            </div>
        </div>
        
        <div class="widget-row">
            <div class="widget recent-activity">
                <h3>Atividade Recente</h3>
                <div class="activity-list">
                    <!-- Lista de atividades recentes -->
                </div>
            </div>
            
            <div class="widget system-status">
                <h3>Status do Sistema</h3>
                <div class="status-indicators">
                    <div class="status-item">
                        <i class="green circle icon"></i>
                        Sistema Online
                    </div>
                    <div class="status-item">
                        <i class="blue circle icon"></i>
                        Versão v1.16.0
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 🔔 **Sistema de Toasts**
```html
<div class="ui toast-container">
    <div class="ui toast update-toast" data-toast-id="update">
        <div class="content">
            <div class="header">
                <i class="download icon"></i>
                Atualização Disponível
            </div>
            <div class="description">
                Há uma atualização disponível. Deseja atualizar agora?
            </div>
        </div>
        <div class="actions">
            <button class="ui mini positive button update-now">
                Atualizar Agora
            </button>
            <button class="ui mini button update-later">
                Não Atualizar
            </button>
        </div>
    </div>
</div>
```

## 🔧 Funcionalidades Técnicas

### 📡 **Sistema de Toasts**

#### Função: `dashboard_toast($params)`
Gerenciador central de notificações toast.

**Parâmetros:**
- `id` (string): Identificador único do toast
- `opcoes` (array): Configurações de exibição
- `botoes` (array): Botões de ação
- `regra` (string): Regra específica de comportamento

**Configurações disponíveis:**
```php
$toast_config = [
    'troca_time' => 5000,                    // Tempo de exibição (ms)
    'updateNotShowToastTime' => 10080,       // Tempo para não mostrar novamente (min)
    'opcoes_padroes' => [
        'displayTime' => 10000,              // Tempo padrão de display
        'class' => 'black'                   // Classe CSS padrão
    ]
];
```

#### Exemplo de uso:
```php
dashboard_toast([
    'id' => 'welcome',
    'opcoes' => [
        'title' => 'Bem-vindo!',
        'message' => 'Sistema carregado com sucesso.',
        'class' => 'success',
        'displayTime' => 5000
    ],
    'botoes' => [
        'positive' => [
            'text' => 'Entendi',
            'action' => 'dismiss'
        ]
    ]
]);
```

### 🔄 **Sistema de Verificação de Atualizações**

#### Função: `dashboard_toast_atualizacoes()`
Verifica e notifica sobre atualizações disponíveis.

**Lógica de funcionamento:**
1. **Verificação de privilégios**: Apenas admins veem notificações
2. **Comparação de versões**: Versão local vs. versão disponível
3. **Exibição condicional**: Toast apenas se nova versão disponível
4. **Gestão de preferências**: Lembra escolha do usuário

```php
function dashboard_toast_atualizacoes() {
    global $_GESTOR;
    
    // Verificar se usuário é admin
    $host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
    
    if(isset($host_verificacao['privilegios_admin'])) {
        // Obter versão atual do sistema
        $versao_atual = $hosts[0]['gestor_cliente_versao_num'];
        $versao_disponivel = $_GESTOR['gestor-cliente']['versao_num'];
        
        // Comparar versões
        if($versao_disponivel > (int)$versao_atual) {
            // Exibir toast de atualização
            dashboard_toast([
                'id' => 'update',
                'regra' => 'update',
                'opcoes' => [
                    'title' => 'Atualização Disponível',
                    'message' => 'Há uma atualização disponível. Deseja atualizar agora?'
                ],
                'botoes' => [
                    'positive' => [
                        'text' => 'Atualizar Agora',
                        'action' => 'redirect',
                        'url' => 'admin-atualizacoes/'
                    ],
                    'negative' => [
                        'text' => 'Não Atualizar',
                        'action' => 'dismiss_with_delay'
                    ]
                ]
            ]);
        }
    }
}
```

## 📱 JavaScript Core

### 🔔 **Gerenciador de Toasts**
```javascript
// Configuração global de toasts
var toastConfig = {
    troca_time: gestor.toasts_options.troca_time,
    updateNotShowToastTime: gestor.toasts_options.updateNotShowToastTime,
    opcoes_padroes: gestor.toasts_options.opcoes_padroes
};

// Função para exibir toast
function showToast(toastId, options) {
    var toast = gestor.toasts[toastId];
    
    if (toast && toast.regra !== 'dismissed') {
        $('body').toast({
            title: toast.opcoes.title,
            message: toast.opcoes.message,
            displayTime: toast.opcoes.displayTime || toastConfig.opcoes_padroes.displayTime,
            class: toast.opcoes.class || toastConfig.opcoes_padroes.class,
            
            // Configurar botões se existirem
            actions: toast.botoes ? formatToastButtons(toast.botoes) : undefined,
            
            // Callback de fechamento
            onHide: function() {
                if (toast.regra === 'update') {
                    // Lógica específica para toast de atualização
                    handleUpdateToastClose(toastId);
                }
            }
        });
    }
}

// Formatação de botões para toasts
function formatToastButtons(botoes) {
    var actions = [];
    
    if (botoes.positive) {
        actions.push({
            text: botoes.positive.text,
            class: 'positive',
            click: function() {
                handleToastAction(botoes.positive.action, botoes.positive);
            }
        });
    }
    
    if (botoes.negative) {
        actions.push({
            text: botoes.negative.text,
            class: 'negative',
            click: function() {
                handleToastAction(botoes.negative.action, botoes.negative);
            }
        });
    }
    
    return actions;
}

// Manipulador de ações de toast
function handleToastAction(action, buttonConfig) {
    switch(action) {
        case 'redirect':
            if (buttonConfig.url) {
                window.location.href = buttonConfig.url;
            }
            break;
            
        case 'dismiss':
            // Apenas fechar
            break;
            
        case 'dismiss_with_delay':
            // Fechar e lembrar por X tempo
            localStorage.setItem('toast_dismissed_' + Date.now(), 
                JSON.stringify({
                    timestamp: Date.now(),
                    duration: toastConfig.updateNotShowToastTime * 60 * 1000
                })
            );
            break;
    }
}
```

### 📊 **Widgets Dinâmicos**
```javascript
// Carregamento de dados para widgets
function loadDashboardWidgets() {
    // Widget de estatísticas
    $.get('dashboard/?ajax=get_statistics', function(data) {
        $('.widget.statistics .stats-grid').html(data.html);
    });
    
    // Widget de atividade recente
    $.get('dashboard/?ajax=get_recent_activity', function(data) {
        $('.widget.recent-activity .activity-list').html(data.html);
    });
    
    // Widget de status do sistema
    $.get('dashboard/?ajax=get_system_status', function(data) {
        $('.widget.system-status .status-indicators').html(data.html);
    });
}

// Auto-refresh de widgets
setInterval(function() {
    loadDashboardWidgets();
}, 30000); // Atualizar a cada 30 segundos
```

## 🗺️ Roteamento e Páginas

### 📄 **Páginas Disponíveis**
| Rota | Opção | Função | Acesso |
|------|-------|--------|--------|
| `dashboard/` | `inicio` | Painel principal | Admin/Gestor |
| `dashboard-testes/` | `listar` | Ambiente de testes | Desenvolvimento |
| `octavio-pagina/` | `dashboard-teste` | Pré-publicação | Desenvolvimento |

### 🔀 **Sistema de Roteamento**
```php
function dashboard_start() {
    global $_GESTOR;
    
    gestor_incluir_bibliotecas();
    
    if ($_GESTOR['ajax']) {
        switch ($_GESTOR['ajax-opcao']) {
            case 'get_statistics': dashboard_ajax_statistics(); break;
            case 'get_recent_activity': dashboard_ajax_activity(); break;
            case 'get_system_status': dashboard_ajax_status(); break;
        }
    } else {
        switch ($_GESTOR['opcao']) {
            case 'inicio': dashboard_main(); break;
            case 'listar': dashboard_tests(); break;
            case 'dashboard-teste': dashboard_preview(); break;
            default: dashboard_main();
        }
    }
}
```

## 📊 Widgets Disponíveis

### 📈 **Widget de Estatísticas**
```php
function dashboard_widget_statistics() {
    // Contar páginas
    $total_paginas = banco_select_count('paginas', "WHERE status='A'");
    
    // Contar posts
    $total_posts = banco_select_count('postagens', "WHERE status='A'");
    
    // Contar arquivos
    $total_arquivos = banco_select_count('arquivos', "WHERE status='A'");
    
    // Contar usuários
    $total_usuarios = banco_select_count('usuarios', "WHERE status='A'");
    
    return [
        'paginas' => $total_paginas,
        'posts' => $total_posts,
        'arquivos' => $total_arquivos,
        'usuarios' => $total_usuarios
    ];
}
```

### 📋 **Widget de Atividade Recente**
```php
function dashboard_widget_recent_activity() {
    $atividades = banco_select_name(
        "tipo, descricao, data_criacao, id_usuarios",
        "atividades",
        "WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
         ORDER BY data_criacao DESC 
         LIMIT 10"
    );
    
    $html = '';
    foreach ($atividades as $atividade) {
        $html .= '<div class="activity-item">';
        $html .= '<i class="icon ' . $atividade['tipo'] . '"></i>';
        $html .= '<span class="description">' . $atividade['descricao'] . '</span>';
        $html .= '<span class="time">' . format_time_ago($atividade['data_criacao']) . '</span>';
        $html .= '</div>';
    }
    
    return $html;
}
```

### 🔧 **Widget de Status do Sistema**
```php
function dashboard_widget_system_status() {
    $status = [
        'servidor' => check_server_health(),
        'banco' => check_database_health(),
        'storage' => check_storage_health(),
        'versao' => $_GESTOR['versao'],
        'updates' => check_available_updates()
    ];
    
    return $status;
}
```

## ⚙️ Configurações JSON

### 📋 **Estrutura do dashboard.json**
```json
{
    "versao": "1.0.0",
    "bibliotecas": ["interface", "html"],
    "toasts": {
        "troca_time": 5000,
        "updateNotShowToastTime": 10080,
        "opcoes_padroes": {
            "displayTime": 10000,
            "class": "black"
        }
    },
    "resources": {
        "pt-br": {
            "pages": [
                {
                    "name": "Dashboard",
                    "id": "dashboard",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "dashboard/",
                    "type": "system",
                    "option": "inicio",
                    "root": true
                }
            ],
            "variables": [
                {
                    "id": "logout-grup",
                    "value": "Sair",
                    "type": "string"
                },
                {
                    "id": "toast-update-title",
                    "value": "Atualização Disponível",
                    "type": "string"
                }
            ]
        }
    }
}
```

## 🛡️ Segurança e Permissões

### 🔐 **Controle de Acesso**
- **Autenticação obrigatória**: Apenas usuários logados
- **Verificação de perfil**: Admin/Gestor/Hospedeiro
- **Sessão validada**: Verificação contínua de autenticidade
- **Timeout automático**: Logout por inatividade

### 🛡️ **Validações de Segurança**
```php
// Verificar se usuário está autenticado
if (!gestor_usuario_logado()) {
    gestor_redirecionar('login/');
    exit;
}

// Verificar permissões específicas
if (!gestor_usuario_permissao('dashboard', 'visualizar')) {
    gestor_erro('Acesso negado ao dashboard');
    exit;
}

// Validar sessão ativa
if (!gestor_sessao_valida()) {
    gestor_logout();
    gestor_redirecionar('login/?erro=sessao_expirada');
    exit;
}
```

## 📈 Performance e Otimização

### ⚡ **Estratégias de Performance**
- **Cache de widgets**: Resultados em cache por 5 minutos
- **Lazy loading**: Carregamento assíncrono de dados não críticos
- **Consultas otimizadas**: Índices em todas as queries
- **Compressão**: Gzip para recursos estáticos
- **CDN ready**: Preparado para CDN externa

### 🗃️ **Sistema de Cache**
```php
// Cache de estatísticas do dashboard
function dashboard_get_cached_stats() {
    $cache_key = 'dashboard_stats_' . $_GESTOR['usuario-id'];
    $cache_time = 300; // 5 minutos
    
    $cached = gestor_cache_get($cache_key);
    if ($cached && (time() - $cached['timestamp']) < $cache_time) {
        return $cached['data'];
    }
    
    $stats = dashboard_widget_statistics();
    gestor_cache_set($cache_key, [
        'timestamp' => time(),
        'data' => $stats
    ]);
    
    return $stats;
}
```

## 🔗 Integração com Outros Módulos

### 🔄 **Sistema de Atualizações**
Integração direta com `admin-atualizacoes`:
```php
// Verificar atualizações disponíveis
if (module_exists('admin-atualizacoes')) {
    $updates = admin_atualizacoes_check_available();
    if ($updates) {
        dashboard_toast_atualizacoes();
    }
}
```

### 👥 **Sistema de Usuários**
Integração com módulos de usuários:
```php
// Informações do usuário logado
$usuario = gestor_usuario();
$permissoes = gestor_usuario_permissoes();
$preferencias = usuario_get_preferences($usuario['id_usuarios']);
```

### 📊 **Módulos de Conteúdo**
Estatísticas de módulos de conteúdo:
```php
// Integração com vários módulos para estatísticas
$stats = [
    'paginas' => paginas_count_all(),
    'posts' => postagens_count_all(),
    'arquivos' => admin_arquivos_count_all(),
    'usuarios' => usuarios_count_all()
];
```

## 🧪 Testes e Desenvolvimento

### ✅ **Ambiente de Testes**
- **Dashboard de testes**: Área isolada para desenvolvimento
- **Simulação de dados**: Mock data para testes
- **Debug integrado**: Logs detalhados de operações
- **Performance monitoring**: Métricas em tempo real

### 🔍 **Debugging**
```php
// Debug de toasts
if ($_GESTOR['debug']) {
    error_log('Dashboard toast criado: ' . json_encode($toast));
}

// Debug de widgets
if ($_GESTOR['debug']) {
    $render_time = microtime(true) - $start_time;
    error_log("Widget renderizado em {$render_time}ms");
}
```

## 🚀 Roadmap

### ✅ **Implementado (v1.0.0)**
- Dashboard básico funcional
- Sistema de toasts
- Verificação de atualizações
- Widgets de estatísticas
- Interface responsiva

### 🚧 **Em Desenvolvimento (v1.1.0)**
- Dashboard personalizável
- Widgets drag & drop
- Gráficos interativos
- Notificações push
- Tema dark mode

### 🔮 **Planejado (v2.0.0)**
- AI-powered insights
- Dashboards por perfil
- Widgets de terceiros
- API de widgets
- Mobile app integration

## 📖 Conclusão

O módulo **dashboard** serve como o coração do sistema administrativo Conn2Flow, oferecendo uma experiência centralizada e intuitiva para gerenciamento do CMS. Com seu sistema robusto de notificações, widgets informativos e integração profunda com outros módulos, representa um ponto de controle essencial para administradores.

**Características principais:**
- ✅ **Interface centralizada** para administração
- ✅ **Sistema de notificações** inteligente
- ✅ **Widgets informativos** em tempo real
- ✅ **Verificação automática** de atualizações
- ✅ **Performance otimizada** com cache

**Status**: ✅ **Produção - Estável**  
**Mantenedores**: Equipe Core Conn2Flow  
**Última atualização**: 31 de agosto, 2025
