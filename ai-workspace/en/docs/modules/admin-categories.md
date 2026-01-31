# Module: admin-categories

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-categorias` |
| **Name** | Category Administration |
| **Version** | `1.0.0` |
| **Category** | Administrative Module |
| **Complexity** | 🟢 Low |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-categories** module manages the **hierarchical category system** in Conn2Flow. Categories are used to organize content, files, and other resources across the CMS. The module supports nested categories (parent-child relationships) for flexible content organization.

## 🏗️ Main Features

### 🗂️ **Category Management**
- **Create categories**: Add new categories with name and optional parent
- **Edit categories**: Modify existing category information
- **Delete categories**: Remove categories (with dependency checks)
- **Hierarchical structure**: Support for parent-child relationships

### 🌳 **Nested Categories**
- **Parent categories**: Top-level organizational groups
- **Child categories**: Sub-categories under parents
- **Unlimited depth**: Multiple levels of nesting supported
- **Tree visualization**: Hierarchical display in list view

### 🔗 **Integration**
- **Files module**: Categorize uploaded files
- **Content modules**: Organize pages and posts
- **Publisher**: Tag published content

## 🗄️ Database Structure

### Main Table: `categorias`
```sql
CREATE TABLE categorias (
    id_categorias INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    id_categorias_pai INT NULL,           -- Parent category reference
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_categorias_pai) REFERENCES categorias(id_categorias)
);
```

## 📁 File Structure

```
gestor/modulos/admin-categorias/
├── admin-categorias.php         # Main module controller
├── admin-categorias.js          # Client-side functionality
├── admin-categorias.json        # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-categorias/
    │       ├── admin-categorias-adicionar/
    │       ├── admin-categorias-editar/
    │       └── admin-categorias-adicionar-filho/
    └── en/
        └── ... (same structure)
```

## 🔧 Core Operations

### CRUD Operations
- **List**: Display all categories with hierarchy
- **Add**: Create new root or child category
- **Edit**: Modify category name and parent
- **Add Child**: Quick action to add subcategory
- **Delete**: Remove category (checks for dependencies)

## 🎨 User Interface

### Category List
- Tree-view display of categories
- Indent for child categories
- Quick action buttons (edit, add child, delete)
- Search/filter functionality

### Add/Edit Form
- Name field (required)
- Parent category dropdown (optional)
- Status toggle

## 🔗 Related Modules
- `admin-arquivos`: File categorization
- `publisher`: Content categorization

## 💡 Best Practices
- Use descriptive category names
- Plan hierarchy before creating categories
- Avoid deep nesting (max 3-4 levels recommended)
- Check dependencies before deleting
