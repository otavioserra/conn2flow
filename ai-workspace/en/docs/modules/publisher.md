# Module: publisher

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `publisher` |
| **Name** | Publisher Definitions |
| **Version** | `1.0.0` |
| **Category** | Content Module |
| **Complexity** | 🔴 High |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **publisher** module manages **dynamic content publishing definitions** in Conn2Flow. It defines the structure and configuration for publishable content types (like news, blog posts, products). Each publisher definition creates a new content type with custom fields and templates.

## 🏗️ Main Features

### 📰 **Publisher Definitions**
- **Create definitions**: Define new content types
- **Custom fields**: Configure content structure
- **Template association**: Link to templates
- **Category support**: Organize content

### 🔧 **Configuration Options**
- **Field definitions**: Custom content fields
- **Template selection**: Output templates
- **URL patterns**: Custom URL structure
- **AI integration**: AI-assisted content

### 🎨 **Templates**
Publisher comes with pre-built templates:
- News templates
- Blog post templates
- Product templates

## 🗄️ Database Structure

### Main Table: `publisher`
```sql
CREATE TABLE publisher (
    id_publisher INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    campos TEXT,                         -- Field definitions (JSON)
    template_id VARCHAR(255),            -- Associated template
    caminho_padrao VARCHAR(500),         -- Default URL path
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/publisher/
├── publisher.php                # Main module controller
├── publisher.js                 # Client-side functionality
├── publisher.json               # Module configuration
└── resources/
    ├── pt-br/
    │   ├── templates/
    │   │   ├── noticias-simples/
    │   │   └── noticias-imagem-destaque/
    │   └── pages/
    │       ├── publisher/
    │       ├── publisher-adicionar/
    │       └── publisher-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Field Definition Example

### News Publisher Definition
```json
{
    "campos": [
        {
            "id": "titulo",
            "tipo": "texto",
            "label": "Título",
            "obrigatorio": true
        },
        {
            "id": "resumo",
            "tipo": "textarea",
            "label": "Resumo"
        },
        {
            "id": "conteudo",
            "tipo": "editor",
            "label": "Conteúdo"
        },
        {
            "id": "imagem",
            "tipo": "arquivo",
            "label": "Imagem Destaque"
        },
        {
            "id": "data_publicacao",
            "tipo": "data",
            "label": "Data de Publicação"
        }
    ]
}
```

## 🎨 User Interface

### Publisher List
- Table of all definitions
- Template indicator
- Content count
- Edit/Delete actions

### Add/Edit Definition Form
- **Name**: Definition name
- **ID**: Unique identifier
- **Fields**: Dynamic field builder
  - Add/remove fields
  - Configure field types
  - Set requirements
- **Template**: Template selection
- **URL Pattern**: Path configuration

## 🔧 Field Types

| Type | Description |
|------|-------------|
| `texto` | Single line text |
| `textarea` | Multi-line text |
| `editor` | Rich text editor |
| `arquivo` | File upload |
| `data` | Date picker |
| `select` | Dropdown selection |
| `checkbox` | Boolean toggle |
| `numero` | Numeric input |

## 🤖 AI Integration

### AI-Assisted Content
- Generate content from prompts
- Auto-fill fields
- Content suggestions
- SEO optimization

## 🔗 Related Modules
- `publisher-pages`: Actual published content
- `admin-templates`: Content templates
- `admin-ia`: AI server configuration
