# Module: admin-ai-prompts

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-prompts-ia` |
| **Name** | AI Prompts Administration |
| **Version** | `1.0.0` |
| **Category** | AI Module |
| **Complexity** | 🟢 Low |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-ai-prompts** module manages **AI prompt templates** in Conn2Flow. Prompts are pre-defined text instructions that users can quickly apply when generating AI content. They work in conjunction with AI modes to produce consistent, high-quality generated content.

## 🏗️ Main Features

### 📝 **Prompt Management**
- **Create prompts**: Define reusable AI instructions
- **Edit prompts**: Modify prompt content
- **Set default**: Choose default prompt per target
- **Target association**: Link prompts to modules

### 💡 **Prompt Features**
- **Template text**: The actual prompt content
- **Target module**: Which module uses this prompt
- **Default flag**: Is this the default for its target
- **Variable support**: Dynamic placeholders in prompts

## 🗄️ Database Structure

### Main Table: `prompts_ia`
```sql
CREATE TABLE prompts_ia (
    id_prompts_ia INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    alvo VARCHAR(100) NOT NULL,          -- Target module
    prompt TEXT,                         -- Prompt content
    padrao TINYINT(1) DEFAULT 0,         -- Is default prompt
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/admin-prompts-ia/
├── admin-prompts-ia.php         # Main module controller
├── admin-prompts-ia.js          # Client-side functionality
├── admin-prompts-ia.json        # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-prompts-ia-listar/
    │       ├── admin-prompts-ia-adicionar/
    │       └── admin-prompts-ia-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Prompt Examples

### Page Generation Prompt
```
Create a landing page about {{topic}} with:
- Hero section with headline and CTA
- 3 feature blocks with icons
- Testimonial section
- Contact form
- Footer with links

Use {{framework}} styling conventions.
Target audience: {{audience}}
Tone: {{tone}}
```

### Product Description Prompt
```
Write a compelling product description for:
Product: {{product_name}}
Category: {{category}}
Key Features: {{features}}

Include:
- Attention-grabbing headline
- 2-3 benefit-focused paragraphs
- Bullet list of specifications
- Call-to-action
```

## 🎨 User Interface

### Prompt List
- Table of configured prompts
- Target indicator
- Default prompt badge
- Edit/Delete actions

### Add/Edit Prompt Form
- **Name**: Prompt display name
- **ID**: Unique identifier
- **Target**: Target module dropdown
- **Prompt**: Large text area for prompt content
- **Default**: Set as default toggle

## 🎯 Available Targets

| Target | Description |
|--------|-------------|
| `paginas` | Page content generation |
| `publisher` | Published content |
| `componentes` | Component generation |

## 💡 Best Practices

### Writing Effective Prompts
- Use placeholders for dynamic content: `{{variable}}`
- Be specific about desired output format
- Include examples when helpful
- Specify any constraints or requirements
- Keep prompts focused on one task

### Prompt Organization
- Name prompts descriptively
- Group related prompts by target
- Document placeholder variables
- Test prompts before marking as default

## 🔗 Related Modules
- `admin-ia`: AI server configuration
- `admin-modos-ia`: AI generation modes
- `admin-paginas`: Page creation with AI
