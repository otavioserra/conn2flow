# Módulo: publicador

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `publicador` |
| **Nome** | Publicador - Definições |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo de Conteúdo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco` |

## 🎯 Propósito

O módulo **publicador** gerencia as **definições e configurações do sistema de publicação** no Conn2Flow. Ele define os tipos de conteúdo, campos personalizados e configurações de publicação que serão usados pelo módulo `publicador-paginas`.

## 🏗️ Funcionalidades Principais

### 📋 **Tipos de Conteúdo**
- **Criar tipos**: Definir novos tipos de publicação
- **Campos personalizados**: Adicionar campos específicos
- **Templates**: Vincular templates de exibição
- **Workflows**: Definir fluxos de aprovação

### ⚙️ **Configurações**
- **URL patterns**: Padrões de URL amigável
- **SEO**: Configurações de metadados
- **Categorização**: Taxonomias e tags
- **Relacionamentos**: Vínculo entre tipos

### 📊 **Taxonomias**
- **Categorias**: Hierarquia de categorias
- **Tags**: Marcadores livres
- **Campos**: Taxonomias personalizadas

## 🗄️ Estrutura do Banco de Dados

### Tabela: `publicador_tipos`
```sql
CREATE TABLE publicador_tipos (
    id_publicador_tipos INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    campos JSON,                         -- Definição de campos
    template_id VARCHAR(255),            -- Template padrão
    url_pattern VARCHAR(255),            -- Padrão de URL
    configuracoes JSON,                  -- Configurações adicionais
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Tabela: `publicador_taxonomias`
```sql
CREATE TABLE publicador_taxonomias (
    id_publicador_taxonomias INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(50),                    -- categoria, tag, custom
    hierarquica CHAR(1) DEFAULT 'N',     -- S = Hierárquica
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/publicador/
├── publicador.php               # Controlador principal
├── publicador.js                # Funcionalidade client-side
├── publicador.json              # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── modal-tipo/
    │   │   └── editor-campos/
    │   └── pages/
    │       ├── publicador/
    │       ├── publicador-tipos/
    │       └── publicador-taxonomias/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Definição de Campos

### Tipos de Campo Disponíveis
```json
{
    "tipos": [
        "texto",           // Input text simples
        "textarea",        // Área de texto
        "editor",          // Editor WYSIWYG
        "numero",          // Campo numérico
        "data",            // Seletor de data
        "datetime",        // Data e hora
        "selecao",         // Dropdown/select
        "checkbox",        // Checkbox simples
        "checkboxes",      // Múltiplos checkboxes
        "radio",           // Radio buttons
        "imagem",          // Upload de imagem
        "arquivo",         // Upload de arquivo
        "galeria",         // Múltiplas imagens
        "relacionamento",  // Link para outro tipo
        "repeater"         // Grupo repetível
    ]
}
```

### Exemplo de Definição de Campos
```json
{
    "campos": [
        {
            "id": "titulo",
            "tipo": "texto",
            "label": "Título",
            "obrigatorio": true,
            "max_length": 200
        },
        {
            "id": "conteudo",
            "tipo": "editor",
            "label": "Conteúdo",
            "obrigatorio": true
        },
        {
            "id": "imagem_destaque",
            "tipo": "imagem",
            "label": "Imagem de Destaque",
            "dimensoes": {
                "largura": 1200,
                "altura": 630
            }
        },
        {
            "id": "autor",
            "tipo": "relacionamento",
            "label": "Autor",
            "relacionar_com": "usuarios"
        }
    ]
}
```

## 🎨 Interface do Usuário

### Lista de Tipos
- Cards com tipos de conteúdo
- Contagem de publicações
- Campos definidos
- Ações rápidas

### Editor de Tipo
- **Nome**: Nome do tipo de conteúdo
- **URL Pattern**: Padrão de URL (ex: `/blog/{slug}`)
- **Template**: Seleção de template
- **Campos**: Editor drag-and-drop de campos

### Configurador de Campos
- Interface visual para adicionar campos
- Ordenação por drag-and-drop
- Configurações específicas por tipo de campo
- Preview em tempo real

## 🔄 Padrões de URL

### Variáveis Disponíveis
```
{id}        - ID numérico
{slug}      - Slug da publicação
{ano}       - Ano de publicação
{mes}       - Mês de publicação
{categoria} - Categoria principal
{tipo}      - Tipo de conteúdo
```

### Exemplos
```
/blog/{slug}                    -> /blog/meu-primeiro-post
/noticias/{ano}/{mes}/{slug}   -> /noticias/2024/01/noticia
/produtos/{categoria}/{slug}    -> /produtos/eletronicos/smartphone
```

## 💡 Boas Práticas

### Design de Tipos
- Crie tipos para casos de uso distintos
- Use herança para tipos similares
- Mantenha campos mínimos necessários

### Campos
- Nomeie de forma clara e consistente
- Agrupe campos relacionados
- Documente campos obrigatórios

### SEO
- Configure URL patterns amigáveis
- Inclua campos de meta description
- Planeje estrutura de categorias

## 🔗 Módulos Relacionados
- `publicador-paginas`: Gerenciamento de publicações
- `admin-templates`: Templates de exibição
- `admin-categorias`: Sistema de categorias
