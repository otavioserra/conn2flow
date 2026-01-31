# Module: publisher-pages

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `publisher-pages` |
| **Name** | Publisher Pages |
| **Version** | `1.0.0` |
| **Category** | Content Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html`, `html-editor` |

## 🎯 Purpose

The **publisher-pages** module manages **published content instances** in Conn2Flow. While the `publisher` module defines content types (like "News"), this module handles the actual content items (like individual news articles). It provides a streamlined interface for content creation and management.

## 🏗️ Main Features

### 📝 **Content Management**
- **Create content**: Add new published items
- **Edit content**: Modify existing content
- **Delete content**: Remove published items
- **Preview**: View content before publishing

### 🎨 **Editor Features**
- **Rich text editing**: WYSIWYG editor
- **Custom fields**: Based on publisher definition
- **Media integration**: Insert images/files
- **Template rendering**: Preview with template

### 📊 **Organization**
- **Filter by publisher**: View by content type
- **Search content**: Find specific items
- **Status management**: Draft/Published

## 🗄️ Database Structure

Uses the `paginas` table with publisher type:
```sql
-- Pages marked as publisher content
tipo = 'publisher'
id_publisher INT -- Reference to publisher definition
dados_publisher TEXT -- Custom field values (JSON)
```

## 📁 File Structure

```
gestor/modulos/publisher-pages/
├── publisher-pages.php          # Main module controller
├── publisher-pages.js           # Client-side functionality
├── publisher-pages.json         # Module configuration
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── publisher-fields/
    │   │   └── lista-pagina-ou-sistema-ou-publisher/
    │   └── pages/
    │       ├── publisher-pages/
    │       ├── publisher-pages-adicionar/
    │       └── publisher-pages-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Content Creation Flow

### 1. Select Publisher
Choose the content type (publisher definition):
- News
- Blog Posts
- Products
- etc.

### 2. Fill Custom Fields
Dynamic form based on publisher definition:
- Title
- Content
- Featured Image
- Categories
- Custom fields

### 3. Save/Publish
- Save as draft
- Publish immediately
- Schedule publication

## 🎨 User Interface

### Content List
- Filter by publisher type
- Table of content items
- Status indicators
- Quick actions (edit, delete, preview)

### Add/Edit Content Form
- Dynamic fields from publisher
- Rich text editor for content
- Media picker for images
- Category selector
- Publish options

### Publisher Fields Component
Renders appropriate input for each field type:
```html
<!-- Text field -->
<div class="field">
    <label>Title</label>
    <input type="text" name="titulo">
</div>

<!-- Editor field -->
<div class="field">
    <label>Content</label>
    <textarea class="editor" name="conteudo"></textarea>
</div>

<!-- File field -->
<div class="field">
    <label>Featured Image</label>
    <div class="file-picker" data-field="imagem"></div>
</div>
```

## 🤖 AI Integration

### AI Content Generation
- Generate from prompts
- Auto-fill based on title
- Content suggestions
- Optimize for SEO

### AI Workflow
1. Enter title or topic
2. Select AI prompt/mode
3. Generate content
4. Review and edit
5. Publish

## 📊 Content Structure

### Stored Data Example
```json
{
    "titulo": "Breaking News Story",
    "resumo": "Brief summary of the story",
    "conteudo": "<p>Full content here...</p>",
    "imagem": "contents/files/2024/01/featured.jpg",
    "data_publicacao": "2024-01-31",
    "categorias": ["news", "featured"]
}
```

## 🔗 Related Modules
- `publisher`: Content type definitions
- `admin-templates`: Content templates
- `admin-arquivos`: Media management
- `admin-ia`: AI content generation
