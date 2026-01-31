# Module: contacts

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `contatos` |
| **Name** | Contact Forms |
| **Version** | `1.0.0` |
| **Category** | Content Module |
| **Complexity** | 🟢 Low |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **contacts** module manages **form submissions** in Conn2Flow. It stores and organizes data submitted through website contact forms, newsletter signups, and other form-based interactions.

## 🏗️ Main Features

### 📬 **Submission Management**
- **View submissions**: List all form entries
- **Filter submissions**: By form type, date
- **Export data**: Download submissions
- **Delete submissions**: Remove entries

### 📊 **Form Types**
- Contact forms
- Newsletter signups
- Inquiry forms
- Custom forms

## 🗄️ Database Structure

### Main Table: `formularios`
```sql
CREATE TABLE formularios (
    id_formularios INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) NOT NULL,
    tipo VARCHAR(100),                   -- Form type
    dados TEXT,                          -- Form data (JSON)
    ip VARCHAR(50),                      -- Submitter IP
    user_agent TEXT,                     -- Browser info
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/contatos/
├── contatos.php                 # Main module controller
├── contatos.js                  # Client-side functionality
├── contatos.json                # Module configuration
└── resources/
    └── (minimal resources)
```

## 🔧 Form Data Structure

### Contact Form Submission
```json
{
    "tipo": "contato",
    "dados": {
        "nome": "John Doe",
        "email": "john@example.com",
        "telefone": "(11) 99999-9999",
        "mensagem": "Hello, I would like more information...",
        "assunto": "General Inquiry"
    },
    "ip": "192.168.1.1",
    "data_criacao": "2024-01-31 15:30:00"
}
```

## 🎨 User Interface

### Submission List
- Table of all submissions
- Form type filter
- Date range filter
- View/Delete actions

### Submission Detail
- All form fields displayed
- Timestamp information
- IP address and user agent
- Delete option

## 🔐 Security

### reCAPTCHA Integration
- Protect forms from spam
- Score validation
- Bot detection

### Data Protection
- IP address logging
- Rate limiting
- Form validation

## 📧 Email Notifications

### Automatic Notifications
When configured, form submissions can:
- Send email to admin
- Send confirmation to user
- Trigger webhooks

## 💡 Best Practices

### Form Implementation
- Always use reCAPTCHA
- Validate input server-side
- Sanitize all data
- Log submission attempts

### Data Management
- Regularly review submissions
- Export important data
- Clean old submissions
- Comply with privacy laws

## 🔗 Related Modules
- `admin-environment`: reCAPTCHA testing
- `perfil-usuario`: Form authentication
