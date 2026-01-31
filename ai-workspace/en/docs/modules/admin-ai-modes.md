# Module: admin-ai-modes

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-modos-ia` |
| **Name** | AI Modes Administration |
| **Version** | `1.0.0` |
| **Category** | AI Module |
| **Complexity** | 🟢 Low |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-ai-modes** module manages **AI generation modes** in Conn2Flow. Modes define how AI generates content for different contexts - for example, a "blog post" mode vs a "product description" mode. Each mode has specific instructions that guide the AI's output style and format.

## 🏗️ Main Features

### 🎛️ **Mode Management**
- **Create modes**: Define new AI generation behaviors
- **Edit modes**: Modify mode instructions
- **Set default**: Choose default mode per target
- **Target association**: Link modes to specific modules

### 📝 **Mode Configuration**
- **Name**: Descriptive mode name
- **Target**: Which module uses this mode
- **Instructions**: Detailed AI instructions (Markdown)
- **Default flag**: Is this the default for its target

## 🗄️ Database Structure

### Main Table: `modos_ia`
```sql
CREATE TABLE modos_ia (
    id_modos_ia INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    alvo VARCHAR(100) NOT NULL,          -- Target module
    modo TEXT,                           -- Mode instructions (Markdown)
    padrao TINYINT(1) DEFAULT 0,         -- Is default mode
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/admin-modos-ia/
├── admin-modos-ia.php           # Main module controller
├── admin-modos-ia.js            # Client-side functionality
├── admin-modos-ia.json          # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-modos-ia-listar/
    │       ├── admin-modos-ia-adicionar/
    │       └── admin-modos-ia-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Mode Instructions Example

### Blog Post Mode
```markdown
# Blog Post Generation Mode

You are an expert content writer. Generate blog posts with:

## Format
- Engaging headline
- Introduction paragraph
- 3-5 main sections with subheadings
- Conclusion with call-to-action

## Style
- Conversational yet professional
- Use short paragraphs
- Include bullet points where appropriate
- Optimize for SEO

## Length
Target 800-1200 words.
```

## 🎨 User Interface

### Mode List
- Table of configured modes
- Target indicator
- Default mode badge
- Edit/Delete actions

### Add/Edit Mode Form
- **Name**: Mode display name
- **ID**: Unique identifier
- **Target**: Target module dropdown
- **Mode Instructions**: Markdown editor
- **Default**: Set as default toggle

## 🎯 Available Targets

| Target | Description |
|--------|-------------|
| `paginas` | Page content generation |
| `publisher` | Published content generation |
| `componentes` | Component generation |

## 💡 Best Practices

### Writing Mode Instructions
- Be specific about format expectations
- Include examples when helpful
- Specify length guidelines
- Define tone and style clearly
- List any restrictions or requirements

## 🔗 Related Modules
- `admin-ia`: AI server configuration
- `admin-prompts-ia`: Specific prompts
- `admin-paginas`: Page creation with AI
