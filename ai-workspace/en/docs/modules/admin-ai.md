# Module: admin-ai

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-ia` |
| **Name** | AI Server Administration |
| **Version** | `1.1.0` |
| **Category** | AI Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html`, `banco` |

## 🎯 Purpose

The **admin-ai** module manages **AI server configurations** in Conn2Flow. It allows administrators to configure connections to AI services like Google Gemini and OpenAI for content generation, code assistance, and other AI-powered features.

## 🏗️ Main Features

### 🤖 **AI Server Management**
- **Add servers**: Configure new AI service connections
- **Edit settings**: Modify API keys and endpoints
- **Set default**: Choose default AI server
- **Test connections**: Verify API connectivity

### 🔑 **Supported Providers**
- **Google Gemini**: Latest Gemini models
- **OpenAI**: GPT models (coming soon)

### 📊 **Configuration Options**
- **API Key**: Authentication credentials
- **Model selection**: Choose AI model
- **Default server**: Primary server for AI requests

## 🗄️ Database Structure

### Main Table: `servidores_ia`
```sql
CREATE TABLE servidores_ia (
    id_servidores_ia INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,           -- gemini, openai
    api_key VARCHAR(500),
    modelo VARCHAR(100),
    padrao TINYINT(1) DEFAULT 0,         -- Is default server
    status CHAR(1) DEFAULT 'A',
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/admin-ia/
├── admin-ia.php                 # Main module controller
├── admin-ia.js                  # Client-side functionality
├── admin-ia.json                # Module configuration
├── gemini/                      # Gemini-specific handlers
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── form-servidor-ia/
    │   └── pages/
    │       ├── admin-ia-listar/
    │       ├── admin-ia-adicionar/
    │       └── admin-ia-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 API Configuration

### Gemini Configuration
```json
{
    "apis": {
        "gemini": {
            "urlGenerateContent": "https://generativelanguage.googleapis.com/v1beta/{MODEL}:generateContent?key={API_KEY}",
            "defaultModel": "models/gemini-3-flash-preview"
        }
    }
}
```

### Making AI Requests
```php
// Example AI request flow
$server = getDefaultAIServer();
$response = callAI($server, [
    'prompt' => $userPrompt,
    'model' => $server['modelo']
]);
```

## 🎨 User Interface

### Server List
- Table of configured AI servers
- Server type indicator (Gemini/OpenAI)
- Default server badge
- Edit/Delete actions

### Add/Edit Server Form
- **Name**: Descriptive name
- **Type**: AI provider selection
- **API Key**: Secure key input
- **Model**: Model selection dropdown
- **Default**: Set as default toggle

## 🔐 Security

- API keys are stored securely
- Keys are never exposed in frontend
- Only admins can manage AI servers
- Connection tests don't log sensitive data

## 🔗 Related Modules
- `admin-modos-ia`: AI generation modes
- `admin-prompts-ia`: AI prompts
- `admin-paginas`: AI-assisted page creation
- `publisher`: AI content generation
