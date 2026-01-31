# Module: users

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `usuarios` |
| **Name** | User Administration |
| **Version** | `1.0.1` |
| **Category** | User Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html`, `usuario` |

## 🎯 Purpose

The **users** module manages **system users** in Conn2Flow. It provides full CRUD functionality for user accounts, including profile assignment, password management, and user status control.

## 🏗️ Main Features

### 👥 **User Management**
- **Create users**: Add new system users
- **Edit users**: Modify user information
- **Delete users**: Remove user accounts
- **Password management**: Set/reset passwords

### 👤 **User Properties**
- **Personal info**: Name, email, phone
- **Credentials**: Username, password
- **Profile assignment**: User role/permissions
- **Status control**: Active/inactive

### 🔐 **Security Features**
- **Password hashing**: Secure password storage
- **Profile integration**: Role-based access
- **Audit trail**: Track user changes

## 🗄️ Database Structure

### Main Table: `usuarios`
```sql
CREATE TABLE usuarios (
    id_usuarios INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    usuario VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,         -- Hashed password
    id_usuarios_perfis INT,              -- Profile reference
    telefone VARCHAR(50),
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_usuarios_perfis) REFERENCES usuarios_perfis(id_usuarios_perfis)
);
```

## 📁 File Structure

```
gestor/modulos/usuarios/
├── usuarios.php                 # Main module controller
├── usuarios.js                  # Client-side functionality
├── usuarios.json                # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── usuarios/
    │       ├── usuarios-adicionar/
    │       └── usuarios-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 User Properties

| Property | Description |
|----------|-------------|
| `nome` | Full name |
| `email` | Email address |
| `usuario` | Username for login |
| `senha` | Password (hashed) |
| `id_usuarios_perfis` | Assigned profile |
| `telefone` | Phone number |
| `status` | Active/Inactive |

## 🎨 User Interface

### User List
- Table of all users
- Profile filter
- Status filter
- Search by name/email
- Edit/Delete actions

### Add/Edit User Form
- **Name**: Full user name
- **Email**: Email address
- **Username**: Login username
- **Password**: Password (with confirmation)
- **Profile**: Role assignment
- **Phone**: Optional phone number
- **Status**: Active/Inactive

## 🔐 Password Security

### Password Requirements
- Minimum length (configured in system)
- Hash algorithm: Secure PHP password_hash
- No plain text storage

### Password Reset Flow
1. Admin accesses user edit
2. Enters new password
3. System hashes and stores
4. User logs in with new password

## 💡 Best Practices

### User Management
- Use unique usernames
- Assign appropriate profiles
- Regularly review inactive users
- Document user purposes

### Security
- Use strong passwords
- Review user access periodically
- Disable unused accounts
- Track user activity

## 🔗 Related Modules
- `usuarios-perfis`: User profiles/roles
- `perfil-usuario`: User self-service
- `modulos-operacoes`: Operation permissions
