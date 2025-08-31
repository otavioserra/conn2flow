# Módulo: admin-paginas

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-paginas` |
| **Nome** | Administração de Páginas |
| **Versão** | `1.1.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🔴 Alta |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-paginas** é o **sistema central de criação e gerenciamento de páginas** do Conn2Flow CMS. Permite criar, editar e gerenciar páginas estáticas e dinâmicas com editor visual integrado, preview em tempo real e suporte a múltiplos frameworks CSS.

## 🏗️ Funcionalidades Principais

### 📝 **Editor de Páginas Completo**
- **Editor HTML visual**: Interface WYSIWYG integrada
- **Editor de código**: CodeMirror com syntax highlighting
- **Preview em tempo real**: Visualização instantânea das alterações
- **Suporte multi-framework**: TailwindCSS e FomanticUI
- **Templates dinâmicos**: Sistema de variáveis e placeholders
- **Validação automática**: Verificação de sintaxe HTML/CSS

### 🎨 **Sistema de Preview Responsivo**
- **Preview desktop**: Visualização para telas grandes
- **Preview tablet**: Simulação para tablets
- **Preview mobile**: Simulação para smartphones
- **Modal responsivo**: Interface adaptável
- **Hot reload**: Atualização automática do preview

### 🔧 **Gerenciamento Avançado**
- **Sistema de layouts**: Integração com módulo de layouts
- **Roteamento dinâmico**: Configuração de URLs personalizadas
- **Controle de permissões**: Sistema granular de acesso
- **Versionamento**: Controle de versões das páginas
- **SEO integrado**: Meta tags e otimização automática

### 🌐 **Multi-framework CSS**
- **TailwindCSS**: Utility-first CSS framework
- **FomanticUI**: Component-based CSS framework
- **Seleção por página**: Framework CSS específico por página
- **Compilação automática**: Build automático dos estilos
- **Purge automático**: Remoção de CSS não utilizado

## 🗄️ Estrutura de Banco de Dados

### Tabela Principal: `paginas`
```sql
CREATE TABLE paginas (
    id_paginas INT AUTO_INCREMENT PRIMARY KEY,
    id_usuarios INT NOT NULL,                 -- Criador da página
    nome VARCHAR(255) NOT NULL,               -- Nome da página
    id VARCHAR(255) UNIQUE NOT NULL,          -- Identificador único
    layout_id VARCHAR(255),                   -- Layout associado (string)
    tipo ENUM('sistema','pagina') DEFAULT 'pagina', -- Tipo da página
    framework_css ENUM('tailwindcss','fomantic-ui') DEFAULT 'fomantic-ui',
    modulo VARCHAR(100),                      -- Módulo associado (se tipo=sistema)
    opcao VARCHAR(100),                       -- Opção do módulo
    caminho VARCHAR(500) NOT NULL,            -- URL da página
    html LONGTEXT,                            -- Conteúdo HTML
    css LONGTEXT,                             -- CSS customizado
    raiz BOOLEAN DEFAULT FALSE,               -- Página raiz do site
    sem_permissao BOOLEAN DEFAULT FALSE,      -- Página sem verificação de permissão
    status CHAR(1) DEFAULT 'A',               -- Status (A=Ativo, D=Deletado)
    versao INT DEFAULT 1,                     -- Controle de versão
    data_criacao DATETIME DEFAULT NOW(),      -- Data de criação
    data_modificacao DATETIME DEFAULT NOW(),   -- Última modificação
    
    INDEX idx_status (status),
    INDEX idx_caminho (caminho),
    INDEX idx_layout (layout_id),
    INDEX idx_tipo (tipo),
    INDEX idx_framework (framework_css),
    FOREIGN KEY (id_usuarios) REFERENCES usuarios(id_usuarios)
);
```

### Relacionamentos
```sql
-- Relacionamento com layouts (string-based)
SELECT p.*, l.nome as layout_nome 
FROM paginas p 
LEFT JOIN layouts l ON p.layout_id = l.id 
WHERE p.status = 'A';

-- Relacionamento com módulos (para páginas tipo=sistema)
SELECT p.*, m.nome as modulo_nome 
FROM paginas p 
LEFT JOIN modulos m ON p.modulo = m.identificador 
WHERE p.tipo = 'sistema';
```

## 📁 Estrutura de Arquivos e Recursos

### Organização de Resources
```
admin-paginas/
├── admin-paginas.php           # Controlador principal
├── admin-paginas.js            # JavaScript frontend
├── admin-paginas.json          # Configurações e metadados
└── resources/                  # Recursos por idioma
    └── pt-br/
        ├── components/         # Componentes reutilizáveis
        │   └── modal-pagina.html
        ├── pages/              # Templates de páginas
        │   ├── admin-paginas.html        # Listagem
        │   ├── admin-paginas-editar.html # Edição
        │   └── admin-paginas-adicionar.html # Criação
        └── assets/             # CSS/JS específicos
```

## 🔧 Funcionalidades Técnicas Core

### 📝 **Função: `admin_paginas_adicionar()`**
Controlador principal para criação de novas páginas.

**Funcionalidades:**
- Validação de campos obrigatórios
- Verificação de URLs únicas
- Processamento de variáveis globais
- Inserção no banco de dados
- Redirecionamento para edição

```php
function admin_paginas_adicionar() {
    global $_GESTOR;
    
    if (isset($_GESTOR['adicionar-banco'])) {
        // Validação de campos obrigatórios
        interface_validacao_campos_obrigatorios([
            'campos' => [
                [
                    'regra' => 'texto-obrigatorio',
                    'campo' => 'pagina-nome',
                    'label' => 'Nome da Página'
                ],
                [
                    'regra' => 'texto-obrigatorio',
                    'campo' => 'paginaCaminho',
                    'label' => 'Caminho da Página',
                    'min' => 1
                ]
            ]
        ]);
        
        // Verificar se caminho já existe
        $existe = interface_verificar_campos([
            'campo' => 'caminho',
            'valor' => banco_escape_field($_REQUEST['paginaCaminho'])
        ]);
        
        if ($existe) {
            interface_alerta([
                'redirect' => true,
                'msg' => 'Este caminho já está em uso'
            ]);
            return;
        }
        
        // Processar variáveis globais
        $html = $_REQUEST['html'];
        $css = $_REQUEST['css'];
        
        // Converter variáveis de template
        $html = preg_replace("/{{(.+?)}}/", "{{$1}}", $html);
        $css = preg_replace("/{{(.+?)}}/", "{{$1}}", $css);
        
        // Inserir no banco
        $campos = [
            ['id_usuarios', gestor_usuario()['id_usuarios']],
            ['nome', banco_escape_field($_REQUEST['pagina-nome'])],
            ['id', banco_identificador([...])],
            ['layout_id', obter_layout_id($_REQUEST['layout'])],
            ['tipo', banco_escape_field($_REQUEST['tipo'])],
            ['framework_css', banco_escape_field($_REQUEST['framework_css'])],
            ['modulo', banco_escape_field($_REQUEST['modulo'])],
            ['opcao', banco_escape_field($_REQUEST['pagina-opcao'])],
            ['caminho', banco_escape_field($_REQUEST['paginaCaminho'])],
            ['html', banco_escape_field($html)],
            ['css', banco_escape_field($css)],
            ['raiz', isset($_REQUEST['raiz']) ? '1' : '0'],
            ['sem_permissao', isset($_REQUEST['sem_permissao']) ? '1' : '0']
        ];
        
        banco_insert_name($campos, 'paginas');
        
        gestor_redirecionar("admin-paginas/editar/?id={$id}");
    }
}
```

### ✏️ **Função: `admin_paginas_editar()`**
Controlador para edição de páginas existentes.

**Funcionalidades:**
- Carregamento de dados existentes
- Atualização de campos
- Versionamento automático
- Backup automático antes da edição
- Preview em tempo real

```php
function admin_paginas_editar() {
    global $_GESTOR;
    
    $id = banco_escape_field($_REQUEST['id']);
    
    if (isset($_GESTOR['editar-banco'])) {
        // Carregar dados atuais
        $pagina_atual = banco_select_name(
            "html, css, versao",
            "paginas",
            "WHERE id = '{$id}' AND status = 'A'"
        );
        
        if (!$pagina_atual) {
            gestor_erro('Página não encontrada');
            return;
        }
        
        // Criar backup se houve mudanças significativas
        $html_atual = $pagina_atual[0]['html'];
        $html_novo = banco_escape_field($_REQUEST['html']);
        
        if (strlen($html_novo) != strlen($html_atual)) {
            criar_backup_pagina($id, $pagina_atual[0]);
        }
        
        // Atualizar dados
        $campos_update = [
            ['nome', banco_escape_field($_REQUEST['pagina-nome'])],
            ['layout_id', obter_layout_id($_REQUEST['layout'])],
            ['framework_css', banco_escape_field($_REQUEST['framework_css'])],
            ['html', $html_novo],
            ['css', banco_escape_field($_REQUEST['css'])],
            ['versao', (int)$pagina_atual[0]['versao'] + 1],
            ['data_modificacao', 'NOW()']
        ];
        
        banco_update_name($campos_update, 'paginas', "WHERE id = '{$id}'");
        
        gestor_redirecionar("admin-paginas/editar/?id={$id}&sucesso=1");
    }
    
    // Carregar dados para formulário
    carregar_dados_pagina($id);
}
```

### 📋 **Função: `admin_paginas_listar()`**
Interface de listagem com filtros e busca.

**Funcionalidades:**
- Listagem paginada
- Filtros por tipo, framework, status
- Busca por nome e conteúdo
- Ordenação múltipla
- Ações em lote

```php
function admin_paginas_listar() {
    global $_GESTOR;
    
    // Parâmetros de filtro
    $filtros = obter_filtros_listagem();
    $ordenacao = $_REQUEST['ordem'] ?? 'data_modificacao DESC';
    $pagina = (int)($_REQUEST['pagina'] ?? 1);
    $por_pagina = 20;
    
    // Construir WHERE
    $where = "WHERE status = 'A'";
    
    if ($filtros['tipo']) {
        $where .= " AND tipo = '{$filtros['tipo']}'";
    }
    
    if ($filtros['framework']) {
        $where .= " AND framework_css = '{$filtros['framework']}'";
    }
    
    if ($filtros['busca']) {
        $busca = banco_escape_field($filtros['busca']);
        $where .= " AND (nome LIKE '%{$busca}%' OR html LIKE '%{$busca}%')";
    }
    
    // Contar total
    $total = banco_select_count('paginas', $where);
    
    // Buscar dados
    $paginas = banco_select_name(
        "id_paginas, nome, id, caminho, tipo, framework_css, data_modificacao",
        "paginas",
        "{$where} ORDER BY {$ordenacao} LIMIT " . 
        (($pagina - 1) * $por_pagina) . ", {$por_pagina}"
    );
    
    // Renderizar listagem
    renderizar_listagem_paginas($paginas, $total, $pagina, $por_pagina);
}
```

## 🎨 Interface de Usuário

### 📝 **Editor de Páginas**
```html
<div class="page-editor">
    <!-- Tabs de navegação -->
    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="visual">Editor Visual</a>
        <a class="item" data-tab="codigo-html">Código HTML</a>
        <a class="item" data-tab="css">CSS</a>
        <a class="item" data-tab="configuracoes">Configurações</a>
    </div>
    
    <!-- Tab: Editor Visual -->
    <div class="ui bottom attached tab segment active" data-tab="visual">
        <div class="ui form">
            <div class="field">
                <label>Conteúdo da Página</label>
                <div id="editor-visual" class="html-editor">
                    <!-- CKEditor será carregado aqui -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tab: Código HTML -->
    <div class="ui bottom attached tab segment" data-tab="codigo-html">
        <div class="field">
            <label>Código HTML</label>
            <textarea class="codemirror-html" name="html">
                {{conteudo_html}}
            </textarea>
        </div>
    </div>
    
    <!-- Tab: CSS -->
    <div class="ui bottom attached tab segment" data-tab="css">
        <div class="field">
            <label>CSS Customizado</label>
            <textarea class="codemirror-css" name="css">
                {{css_customizado}}
            </textarea>
        </div>
    </div>
    
    <!-- Tab: Configurações -->
    <div class="ui bottom attached tab segment" data-tab="configuracoes">
        <div class="ui form">
            <div class="three fields">
                <div class="field">
                    <label>Nome da Página</label>
                    <input type="text" name="pagina-nome" value="{{nome}}" required>
                </div>
                <div class="field">
                    <label>Caminho (URL)</label>
                    <input type="text" name="paginaCaminho" value="{{caminho}}" required>
                </div>
                <div class="field">
                    <label>Layout</label>
                    <select name="layout" class="ui dropdown">
                        <option value="">Selecione um layout</option>
                        {{opcoes_layouts}}
                    </select>
                </div>
            </div>
            
            <div class="three fields">
                <div class="field">
                    <label>Tipo</label>
                    <select name="tipo" class="ui dropdown">
                        <option value="pagina">Página</option>
                        <option value="sistema">Sistema</option>
                    </select>
                </div>
                <div class="field">
                    <label>Framework CSS</label>
                    <select name="framework_css" class="ui dropdown">
                        <option value="fomantic-ui">Fomantic-UI</option>
                        <option value="tailwindcss">TailwindCSS</option>
                    </select>
                </div>
                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" name="raiz">
                        <label>Página Raiz</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botões de ação -->
    <div class="ui actions">
        <button type="button" class="ui button preview-button">
            <i class="eye icon"></i>
            Pré-visualizar
        </button>
        <button type="submit" class="ui primary button">
            <i class="save icon"></i>
            Salvar Página
        </button>
        <a href="admin-paginas/" class="ui button">
            <i class="arrow left icon"></i>
            Voltar
        </a>
    </div>
</div>
```

### 🔍 **Modal de Preview Responsivo**
```html
<div class="ui modal" id="preview-modal">
    <div class="header">
        <i class="eye icon"></i>
        Pré-Visualização da Página
    </div>
    <div class="content">
        <!-- Seletores de dispositivo -->
        <div class="ui secondary menu">
            <a class="item active" data-device="desktop">
                <i class="desktop icon"></i>
                Desktop
            </a>
            <a class="item" data-device="tablet">
                <i class="tablet icon"></i>
                Tablet
            </a>
            <a class="item" data-device="mobile">
                <i class="mobile icon"></i>
                Mobile
            </a>
        </div>
        
        <!-- Container do preview -->
        <div class="preview-container">
            <iframe id="preview-frame" 
                    src="about:blank" 
                    width="100%" 
                    height="600px"
                    frameborder="0">
            </iframe>
        </div>
    </div>
    <div class="actions">
        <button class="ui button" onclick="$('#preview-modal').modal('hide')">
            Fechar
        </button>
        <button class="ui primary button" onclick="salvarPagina()">
            <i class="save icon"></i>
            Salvar
        </button>
    </div>
</div>
```

## 🖥️ JavaScript Core

### 🔧 **Inicialização do CodeMirror**
```javascript
$(document).ready(function() {
    var codemirrors_instances = [];
    
    // Configurar editor CSS
    var css_editors = document.getElementsByClassName("codemirror-css");
    for (var i = 0; i < css_editors.length; i++) {
        var cssEditor = CodeMirror.fromTextArea(css_editors[i], {
            lineNumbers: true,
            lineWrapping: true,
            styleActiveLine: true,
            matchBrackets: true,
            mode: "css",
            theme: "tomorrow-night-bright",
            indentUnit: 4,
            extraKeys: {
                "F11": function(cm) {
                    cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                },
                "Esc": function(cm) {
                    if (cm.getOption("fullScreen")) {
                        cm.setOption("fullScreen", false);
                    }
                }
            }
        });
        
        cssEditor.setSize('100%', 500);
        codemirrors_instances.push(cssEditor);
    }
    
    // Configurar editor HTML
    var html_editors = document.getElementsByClassName("codemirror-html");
    for (var i = 0; i < html_editors.length; i++) {
        var htmlEditor = CodeMirror.fromTextArea(html_editors[i], {
            lineNumbers: true,
            lineWrapping: true,
            styleActiveLine: true,
            matchBrackets: true,
            mode: "htmlmixed",
            theme: "tomorrow-night-bright",
            indentUnit: 4,
            autoCloseTags: true,
            extraKeys: {
                "F11": function(cm) {
                    cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                },
                "Esc": function(cm) {
                    if (cm.getOption("fullScreen")) {
                        cm.setOption("fullScreen", false);
                    }
                }
            }
        });
        
        htmlEditor.setSize('100%', 500);
        codemirrors_instances.push(htmlEditor);
    }
});
```

### 📱 **Sistema de Preview Responsivo**
```javascript
// Controle de preview responsivo
function initPreviewSystem() {
    $('.preview-button').click(function() {
        var html = getEditorContent('html');
        var css = getEditorContent('css');
        var framework = $('select[name="framework_css"]').val();
        
        generatePreview(html, css, framework);
        $('#preview-modal').modal('show');
    });
    
    // Alternar entre dispositivos
    $('.preview-device-selector .item').click(function() {
        var device = $(this).data('device');
        
        $('.preview-device-selector .item').removeClass('active');
        $(this).addClass('active');
        
        updatePreviewDevice(device);
    });
}

function generatePreview(html, css, framework) {
    var previewHtml = buildPreviewHTML(html, css, framework);
    
    // Criar blob URL para preview
    var blob = new Blob([previewHtml], {type: 'text/html'});
    var url = URL.createObjectURL(blob);
    
    $('#preview-frame').attr('src', url);
    
    // Limpar URL após carregamento
    $('#preview-frame').on('load', function() {
        URL.revokeObjectURL(url);
    });
}

function buildPreviewHTML(content, css, framework) {
    var framework_css = '';
    
    if (framework === 'tailwindcss') {
        framework_css = '<link href="https://cdn.tailwindcss.com" rel="stylesheet">';
    } else {
        framework_css = '<link href="/fomantic-ui/semantic.min.css" rel="stylesheet">';
    }
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Preview</title>
            ${framework_css}
            <style>${css}</style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `;
}

function updatePreviewDevice(device) {
    var iframe = $('#preview-frame');
    
    switch(device) {
        case 'desktop':
            iframe.css({
                'width': '100%',
                'max-width': 'none'
            });
            break;
        case 'tablet':
            iframe.css({
                'width': '768px',
                'max-width': '100%'
            });
            break;
        case 'mobile':
            iframe.css({
                'width': '375px',
                'max-width': '100%'
            });
            break;
    }
}
```

### 🔄 **Sistema de Backup Automático**
```javascript
// Sistema de backup automático
function initAutoBackup() {
    var backupInterval = 30000; // 30 segundos
    var lastBackup = Date.now();
    
    setInterval(function() {
        if (hasContentChanged() && (Date.now() - lastBackup > backupInterval)) {
            createAutoBackup();
            lastBackup = Date.now();
        }
    }, 5000); // Verificar a cada 5 segundos
}

function hasContentChanged() {
    var currentHtml = getEditorContent('html');
    var currentCss = getEditorContent('css');
    
    var lastHtml = localStorage.getItem('page_backup_html');
    var lastCss = localStorage.getItem('page_backup_css');
    
    return (currentHtml !== lastHtml) || (currentCss !== lastCss);
}

function createAutoBackup() {
    var html = getEditorContent('html');
    var css = getEditorContent('css');
    var timestamp = Date.now();
    
    localStorage.setItem('page_backup_html', html);
    localStorage.setItem('page_backup_css', css);
    localStorage.setItem('page_backup_timestamp', timestamp);
    
    // Notificar usuário discretamente
    showBackupNotification();
}

function restoreFromBackup() {
    var backupHtml = localStorage.getItem('page_backup_html');
    var backupCss = localStorage.getItem('page_backup_css');
    var timestamp = localStorage.getItem('page_backup_timestamp');
    
    if (backupHtml && backupCss && timestamp) {
        if (confirm('Deseja restaurar o backup automático?')) {
            setEditorContent('html', backupHtml);
            setEditorContent('css', backupCss);
            
            clearBackup();
        }
    }
}
```

## ⚙️ Configurações e Parâmetros

### 📋 **Configurações do Módulo (JSON)**
```json
{
    "versao": "1.1.0",
    "bibliotecas": ["interface", "html"],
    "tabela": {
        "nome": "paginas",
        "id": "id",
        "id_numerico": "id_paginas",
        "status": "status",
        "versao": "versao",
        "data_criacao": "data_criacao",
        "data_modificacao": "data_modificacao"
    },
    "selectDadosTipo": [
        {"texto": "Sistema", "valor": "sistema"},
        {"texto": "Página", "valor": "pagina"}
    ],
    "selectDadosFrameworkCSS": [
        {"texto": "Fomantic-UI", "valor": "fomantic-ui"},
        {"texto": "TailwindCSS", "valor": "tailwindcss"}
    ],
    "preview": {
        "devices": {
            "desktop": {"width": "100%", "height": "600px"},
            "tablet": {"width": "768px", "height": "600px"},
            "mobile": {"width": "375px", "height": "600px"}
        }
    }
}
```

### 🎛️ **Configurações de Editor**
```php
// Configurações do CodeMirror
$codemirror_config = [
    'theme' => 'tomorrow-night-bright',
    'lineNumbers' => true,
    'lineWrapping' => true,
    'styleActiveLine' => true,
    'matchBrackets' => true,
    'autoCloseTags' => true,
    'indentUnit' => 4,
    'height' => 500
];

// Configurações de validação
$validation_rules = [
    'nome_minimo' => 3,
    'nome_maximo' => 255,
    'caminho_minimo' => 1,
    'caminho_maximo' => 500,
    'html_maximo' => 1000000, // 1MB
    'css_maximo' => 100000    // 100KB
];
```

## 🔌 Integração com Outros Módulos

### 🎨 **Módulo Layouts**
Integração bidirecional para gerenciamento de layouts:

```php
// Obter layouts disponíveis
function obter_layouts_disponiveis() {
    return banco_select_name(
        "id, id_layouts, nome",
        "layouts",
        "WHERE status = 'A' ORDER BY nome ASC"
    );
}

// Associar página a layout
function associar_layout_pagina($pagina_id, $layout_id) {
    banco_update_name(
        [['layout_id', $layout_id]],
        'paginas',
        "WHERE id = '{$pagina_id}'"
    );
}
```

### 🧩 **Módulo Componentes**
Integração para inserção de componentes nas páginas:

```php
// Renderizar componentes na página
function processar_componentes_pagina($html) {
    // Buscar padrões de componentes: {{componente:nome}}
    return preg_replace_callback(
        '/{{componente:([^}]+)}}/',
        function($matches) {
            return gestor_componente(['id' => $matches[1]]);
        },
        $html
    );
}
```

### 🔀 **Sistema de Roteamento**
Integração com sistema de URLs:

```php
// Registrar rotas das páginas
function registrar_rotas_paginas() {
    $paginas = banco_select_name(
        "id, caminho, tipo, modulo, opcao",
        "paginas",
        "WHERE status = 'A' AND raiz = 1"
    );
    
    foreach ($paginas as $pagina) {
        gestor_rota_registrar([
            'caminho' => $pagina['caminho'],
            'modulo' => $pagina['tipo'] === 'sistema' ? $pagina['modulo'] : 'paginas',
            'opcao' => $pagina['opcao'] ?: 'visualizar',
            'parametros' => ['id' => $pagina['id']]
        ]);
    }
}
```

## 🛡️ Segurança e Validação

### 🔒 **Validações de Entrada**
```php
// Sanitização de HTML
function sanitizar_html_pagina($html) {
    // Permitir apenas tags seguras
    $allowed_tags = '<p><div><span><h1><h2><h3><h4><h5><h6><strong><em><u><a><img><ul><ol><li><table><tr><td><th>';
    $html = strip_tags($html, $allowed_tags);
    
    // Remover scripts e eventos JavaScript
    $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
    $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
    
    return $html;
}

// Validação de CSS
function validar_css($css) {
    // Remover @import e @font-face inseguros
    $css = preg_replace('/@import\s+url\([^)]*\);?/i', '', $css);
    $css = preg_replace('/expression\s*\([^)]*\)/i', '', $css);
    $css = preg_replace('/javascript\s*:/i', '', $css);
    
    return $css;
}

// Verificação de permissões
function verificar_permissao_pagina($acao, $pagina_id = null) {
    if (!gestor_usuario_logado()) {
        return false;
    }
    
    $usuario = gestor_usuario();
    
    // Verificar permissão geral
    if (!gestor_usuario_permissao('admin-paginas', $acao)) {
        return false;
    }
    
    // Verificar propriedade da página (se editando)
    if ($pagina_id && $acao === 'editar') {
        $pagina = banco_select_name(
            "id_usuarios",
            "paginas",
            "WHERE id = '{$pagina_id}' AND status = 'A'"
        );
        
        if ($pagina && $pagina[0]['id_usuarios'] != $usuario['id_usuarios']) {
            // Verificar se é admin
            if (!gestor_usuario_admin()) {
                return false;
            }
        }
    }
    
    return true;
}
```

### 🛡️ **Prevenção de Ataques**
```php
// Proteção contra XSS
function proteger_xss_pagina($html) {
    // Escapar variáveis de template
    $html = preg_replace('/{{([^}]+)}}/', '{{htmlspecialchars($1)}}', $html);
    
    // Validar URLs em links e imagens
    $html = preg_replace_callback(
        '/(href|src)=["\']([^"\']+)["\']/i',
        function($matches) {
            $url = $matches[2];
            if (filter_var($url, FILTER_VALIDATE_URL) || strpos($url, '/') === 0) {
                return $matches[0];
            }
            return $matches[1] . '="#"';
        },
        $html
    );
    
    return $html;
}

// Validação de caminhos de URL
function validar_caminho_url($caminho) {
    // Deve começar e terminar com /
    if (!preg_match('/^\/.*\/$/', $caminho)) {
        return false;
    }
    
    // Não pode conter caracteres perigosos
    if (preg_match('/[<>"]/', $caminho)) {
        return false;
    }
    
    // Não pode conter .. (path traversal)
    if (strpos($caminho, '..') !== false) {
        return false;
    }
    
    return true;
}
```

## 📈 Performance e Otimização

### ⚡ **Estratégias de Performance**
- **Cache de páginas**: Sistema de cache inteligente
- **Minificação automática**: HTML e CSS otimizados
- **Lazy loading**: Carregamento sob demanda do editor
- **Compressão Gzip**: Redução de tamanho de transferência
- **CDN ready**: Preparado para uso com CDN

### 🗃️ **Sistema de Cache**
```php
// Cache de páginas renderizadas
function cache_pagina_renderizada($pagina_id, $html_renderizado) {
    $cache_key = "pagina_{$pagina_id}";
    $cache_time = 3600; // 1 hora
    
    gestor_cache_set($cache_key, [
        'html' => $html_renderizado,
        'timestamp' => time(),
        'checksum' => md5($html_renderizado)
    ], $cache_time);
}

function obter_pagina_cache($pagina_id) {
    $cache_key = "pagina_{$pagina_id}";
    $cached = gestor_cache_get($cache_key);
    
    if ($cached && is_valid_cache($cached)) {
        return $cached['html'];
    }
    
    return false;
}

// Invalidação inteligente de cache
function invalidar_cache_pagina($pagina_id) {
    $cache_key = "pagina_{$pagina_id}";
    gestor_cache_delete($cache_key);
    
    // Invalidar também cache de páginas relacionadas
    $paginas_relacionadas = obter_paginas_relacionadas($pagina_id);
    foreach ($paginas_relacionadas as $relacionada) {
        gestor_cache_delete("pagina_{$relacionada['id']}");
    }
}
```

## 🧪 Testes e Validação

### ✅ **Casos de Teste**
- **Criação de página**: Nova página com todos os campos
- **Edição de conteúdo**: Modificação de HTML e CSS
- **Preview responsivo**: Teste em diferentes resoluções
- **Validação de formulário**: Campos obrigatórios e formato
- **Sistema de layouts**: Associação e mudança de layouts
- **Framework CSS**: Alternância entre TailwindCSS e FomanticUI
- **Permissões**: Controle de acesso por perfil

### 🐛 **Problemas Conhecidos**
- **CodeMirror refresh**: Necessário refresh manual em tabs
- **Preview iframe**: Limitações de segurança cross-origin
- **Backup automático**: Pode consumir localStorage
- **Editor CKEditor**: Conflitos ocasionais com CodeMirror

## 📊 Métricas e Analytics

### 📈 **KPIs do Módulo**
- **Total de páginas**: Quantidade de páginas no sistema
- **Páginas por framework**: Distribuição TailwindCSS vs FomanticUI
- **Taxa de edição**: Frequência de modificações
- **Performance de carregamento**: Tempo de resposta do editor
- **Uso de features**: Quais funcionalidades são mais utilizadas

### 📋 **Logs de Auditoria**
```php
// Log de operações críticas
function log_operacao_pagina($acao, $pagina_id, $detalhes = []) {
    $usuario = gestor_usuario();
    
    banco_insert_name([
        ['modulo', 'admin-paginas'],
        ['acao', $acao],
        ['pagina_id', $pagina_id],
        ['usuario_id', $usuario['id_usuarios']],
        ['detalhes', json_encode($detalhes)],
        ['ip', $_SERVER['REMOTE_ADDR']],
        ['user_agent', $_SERVER['HTTP_USER_AGENT']],
        ['timestamp', 'NOW()']
    ], 'auditoria_log');
}
```

## 🚀 Roadmap e Melhorias

### ✅ **Implementado (v1.1.0)**
- Editor visual e código integrados
- Preview responsivo em modal
- Suporte TailwindCSS e FomanticUI
- Sistema de layouts
- Controle de permissões
- Backup automático

### 🚧 **Em Desenvolvimento (v1.2.0)**
- Editor colaborativo em tempo real
- Versionamento avançado com diff visual
- Templates de página pré-definidos
- AI-powered content suggestions
- Otimização SEO automática
- PWA preview

### 🔮 **Planejado (v2.0.0)**
- Editor drag & drop visual
- Componentização avançada
- A/B testing integrado
- Análise de performance automática
- Integração com headless CMS
- API GraphQL para páginas

## 📖 Conclusão

O módulo **admin-paginas** representa o coração do sistema de gestão de conteúdo do Conn2Flow, oferecendo uma interface completa e profissional para criação e edição de páginas. Com seu editor duplo (visual/código), sistema de preview responsivo e integração com múltiplos frameworks CSS, proporciona uma experiência de desenvolvimento moderna e eficiente.

**Características principais:**
- ✅ **Editor híbrido** visual e código
- ✅ **Preview responsivo** em tempo real
- ✅ **Multi-framework CSS** (TailwindCSS/FomanticUI)
- ✅ **Sistema de layouts** integrado
- ✅ **Controle granular** de permissões
- ✅ **Performance otimizada** com cache inteligente

**Status**: ✅ **Produção - Maduro e Estável**  
**Mantenedores**: Equipe Core Conn2Flow  
**Última atualização**: 31 de agosto, 2025
