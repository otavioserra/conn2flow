# Módulo: publicador-paginas

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `publicador-paginas` |
| **Nome** | Publicador - Páginas |
| **Versão** | `1.0.1` |
| **Categoria** | Módulo de Conteúdo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco`, `publicador` |

## 🎯 Propósito

O módulo **publicador-paginas** é o **gerenciador de conteúdo publicado** no Conn2Flow. Ele permite criar, editar, publicar e gerenciar páginas e posts de conteúdo usando os tipos definidos no módulo `publicador`.

## 🏗️ Funcionalidades Principais

### 📝 **Gerenciamento de Conteúdo**
- **Criar páginas**: Adicionar novo conteúdo
- **Editar páginas**: Modificar conteúdo existente
- **Preview**: Visualizar antes de publicar
- **Versionamento**: Histórico de versões

### 📅 **Publicação**
- **Publicar**: Tornar conteúdo público
- **Agendar**: Publicação futura
- **Despublicar**: Remover do ar
- **Rascunhos**: Salvar sem publicar

### 🏷️ **Organização**
- **Categorizar**: Associar categorias
- **Tags**: Adicionar marcadores
- **Ordenar**: Definir ordem de exibição
- **Filtrar**: Buscar e filtrar conteúdo

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `publicador_paginas`
```sql
CREATE TABLE publicador_paginas (
    id_publicador_paginas INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    tipo_id VARCHAR(255) NOT NULL,       -- Tipo de conteúdo
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    conteudo TEXT,                       -- Conteúdo principal
    campos JSON,                         -- Campos personalizados
    meta_title VARCHAR(255),
    meta_description TEXT,
    imagem_destaque VARCHAR(255),
    autor_id VARCHAR(255),
    publicado CHAR(1) DEFAULT 'N',       -- S = Publicado
    data_publicacao DATETIME,
    data_agendamento DATETIME,
    ordem INT DEFAULT 0,
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Tabela de Relacionamentos
```sql
CREATE TABLE publicador_paginas_taxonomias (
    id_pp_taxonomias INT AUTO_INCREMENT PRIMARY KEY,
    id_pagina VARCHAR(255) NOT NULL,
    id_taxonomia VARCHAR(255) NOT NULL,
    id_termo VARCHAR(255) NOT NULL       -- ID da categoria/tag
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/publicador-paginas/
├── publicador-paginas.php       # Controlador principal
├── publicador-paginas.js        # Funcionalidade client-side
├── publicador-paginas.json      # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── editor-conteudo/
    │   │   ├── seletor-categorias/
    │   │   └── painel-publicacao/
    │   └── pages/
    │       ├── publicador-paginas/
    │       ├── publicador-paginas-adicionar/
    │       └── publicador-paginas-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🎨 Interface do Usuário

### Lista de Páginas
- Tabela com paginação e busca
- Filtros por tipo, status, categoria
- Indicadores de publicação
- Ações em massa

### Editor de Página
Layout em duas colunas:

**Coluna Principal:**
- Título
- Editor de conteúdo (WYSIWYG)
- Campos personalizados do tipo

**Coluna Lateral:**
- **Publicação**: Status, data, botões
- **Categorias**: Checkboxes hierárquicos
- **Tags**: Input com autocomplete
- **Imagem Destaque**: Upload
- **SEO**: Meta title, description

## 🔧 Fluxo de Publicação

### Estados de Publicação
```
Rascunho (publicado='N', data_agendamento=NULL)
    ↓
Agendado (publicado='N', data_agendamento=FUTURO)
    ↓
Publicado (publicado='S', data_publicacao=DATA)
    ↓
Despublicado (publicado='N', data_publicacao=DATA)
```

### Lógica de Publicação
```php
function publicar($paginaId) {
    atualizar('publicador_paginas', [
        'publicado' => 'S',
        'data_publicacao' => date('Y-m-d H:i:s')
    ], ['id' => $paginaId]);
    
    // Limpar cache se houver
    limparCache("pagina_{$paginaId}");
    
    // Notificar sistemas (sitemap, etc)
    disparar('pagina_publicada', $paginaId);
}

function agendar($paginaId, $dataAgendamento) {
    atualizar('publicador_paginas', [
        'publicado' => 'N',
        'data_agendamento' => $dataAgendamento
    ], ['id' => $paginaId]);
}
```

## 🔍 SEO e Meta Tags

### Campos SEO
```html
<!-- Meta tags geradas -->
<title>@[[pagina#meta_title]]@ | @[[variavel#site-nome]]@</title>
<meta name="description" content="@[[pagina#meta_description]]@">
<meta property="og:title" content="@[[pagina#titulo]]@">
<meta property="og:image" content="@[[pagina#imagem_destaque]]@">
<link rel="canonical" href="@[[pagina#url]]@">
```

### URLs Amigáveis
- Geração automática de slug a partir do título
- Detecção de slugs duplicados
- Redirecionamentos para slugs alterados

## 📊 Versionamento

### Histórico de Versões
```php
// Ao salvar, cria versão
function salvarComVersao($paginaId, $dados) {
    // Buscar versão atual
    $versaoAtual = buscar('publicador_paginas', ['id' => $paginaId]);
    
    // Salvar histórico
    inserir('publicador_paginas_versoes', [
        'id_pagina' => $paginaId,
        'dados' => json_encode($versaoAtual),
        'versao' => $versaoAtual['versao']
    ]);
    
    // Atualizar com novos dados
    $dados['versao'] = $versaoAtual['versao'] + 1;
    atualizar('publicador_paginas', $dados, ['id' => $paginaId]);
}
```

## 💡 Boas Práticas

### Conteúdo
- Use títulos descritivos
- Otimize imagens antes de upload
- Preencha campos SEO
- Revise antes de publicar

### Organização
- Categorize adequadamente
- Use tags com moderação
- Mantenha hierarquia clara

### SEO
- Meta description entre 150-160 caracteres
- Títulos únicos por página
- URLs curtas e descritivas

## 🔗 Módulos Relacionados
- `publicador`: Definições de tipos
- `admin-templates`: Templates de exibição
- `admin-categorias`: Categorias
- `admin-arquivos`: Gerenciamento de mídia
