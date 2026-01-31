# Módulo: admin-layouts

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-layouts` |
| **Nome** | Administração de Layouts |
| **Versão** | `1.0.1` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-layouts** gerencia **templates de layout de página** no Conn2Flow. Layouts definem a estrutura geral das páginas incluindo cabeçalhos, rodapés, navegação e a área de conteúdo principal. Toda página no sistema usa um layout como seu template base.

## 🏗️ Funcionalidades Principais

### 🎨 **Gerenciamento de Layouts**
- **Criar layouts**: Projetar novas estruturas de página
- **Editar layouts**: Modificar HTML e CSS com editor de código
- **Suporte a frameworks**: Fomantic-UI e TailwindCSS
- **Controle de versão**: Rastrear mudanças no layout

### 📐 **Estrutura do Template**
- **Documento HTML completo**: Estrutura completa `<html>`, `<head>`, `<body>`
- **Placeholder do corpo da página**: `@[[pagina#corpo]]@` para conteúdo da página
- **Seção head**: Scripts, estilos, meta tags
- **Integração de variáveis**: Conteúdo dinâmico via variáveis

### 🔄 **Variável Crítica**
A variável mais importante nos layouts:
```html
@[[pagina#corpo]]@
```
Este placeholder é onde o conteúdo específico da página é inserido.

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `layouts`
```sql
CREATE TABLE layouts (
    id_layouts INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    html TEXT,                           -- Documento HTML completo
    css TEXT,                            -- CSS adicional
    framework_css VARCHAR(50),           -- fomantic-ui ou tailwindcss
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-layouts/
├── admin-layouts.php            # Controlador principal
├── admin-layouts.js             # Funcionalidade client-side
├── admin-layouts.json           # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── modal-layout/
    │   └── pages/
    │       ├── admin-layouts/
    │       ├── admin-layouts-adicionar/
    │       └── admin-layouts-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Exemplo de Estrutura de Layout

### Template de Layout Básico
```html
<!DOCTYPE html>
<html lang="@[[pagina#idioma]]@">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@[[pagina#titulo]]@</title>
    @[[pagina#head]]@
</head>
<body>
    <!-- Componente de Cabeçalho -->
    @[[componente#site-header]]@
    
    <!-- Área de Conteúdo Principal -->
    <main class="container">
        @[[pagina#corpo]]@
    </main>
    
    <!-- Componente de Rodapé -->
    @[[componente#site-footer]]@
    
    @[[pagina#scripts]]@
</body>
</html>
```

## 🎨 Interface do Usuário

### Lista de Layouts
- Visualização em tabela com nomes dos layouts
- Data da última modificação
- Contagem de páginas associadas
- Ações rápidas de editar/excluir

### Formulário de Edição
- **Nome**: Nome de exibição do layout
- **ID**: Identificador único
- **HTML**: Editor de código do documento completo
- **CSS**: Folha de estilo adicional
- **Framework**: Seleção de framework CSS

## 🔧 Layouts Integrados

### `layout-administrativo-do-gestor`
O layout administrativo principal usado por todos os módulos do backend. Inclui:
- Sidebar de navegação do admin
- Cabeçalho superior com info do usuário
- Área de conteúdo principal
- Sistema de notificações toast

### `layout-pagina-sem-permissao`
Um layout mínimo para páginas que não requerem autenticação:
- Páginas de login
- Páginas de erro públicas
- Fluxos OAuth

## 💡 Boas Práticas

### Estrutura
- Sempre inclua o placeholder `@[[pagina#corpo]]@`
- Use componentes para seções reutilizáveis
- Inclua meta tags e viewport adequados
- Adicione `@[[pagina#head]]@` para conteúdo head específico da página

### Performance
- Minimize estilos inline
- Use seção de arquivo CSS para estilos
- Adie scripts não críticos
- Otimize para mobile first

## 🔗 Módulos Relacionados
- `admin-componentes`: Componentes reutilizáveis nos layouts
- `admin-paginas`: Páginas que usam layouts
- `admin-templates`: Templates de conteúdo
