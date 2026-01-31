# Module: user-profiles

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `usuarios-perfis` |
| **Name** | User Profiles Administration |
| **Version** | `1.0.0` |
| **Category** | User Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html`, `usuario` |

## 🎯 Purpose

The **user-profiles** module manages **user roles and permissions** in Conn2Flow. Profiles define what modules and operations users can access. This is the core of the role-based access control (RBAC) system.

## 🏗️ Main Features

### 👔 **Profile Management**
- **Create profiles**: Define new user roles
- **Edit profiles**: Modify permissions
- **Delete profiles**: Remove unused profiles
- **Clone profiles**: Duplicate existing profiles

### 🔐 **Permission Configuration**
- **Module access**: Which modules are accessible
- **Operation access**: Which operations within modules
- **Bulk selection**: Select all/none
- **Granular control**: Fine-tuned permissions

## 🗄️ Database Structure

### Main Table: `usuarios_perfis`
```sql
CREATE TABLE usuarios_perfis (
    id_usuarios_perfis INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Permissions Table: `usuarios_perfis_permissoes`
```sql
CREATE TABLE usuarios_perfis_permissoes (
    id_usuarios_perfis INT NOT NULL,
    id_modulos_operacoes INT NOT NULL,
    PRIMARY KEY (id_usuarios_perfis, id_modulos_operacoes),
    FOREIGN KEY (id_usuarios_perfis) REFERENCES usuarios_perfis(id_usuarios_perfis),
    FOREIGN KEY (id_modulos_operacoes) REFERENCES modulos_operacoes(id_modulos_operacoes)
);
```

## 📁 File Structure

```
gestor/modulos/usuarios-perfis/
├── usuarios-perfis.php          # Main module controller
├── usuarios-perfis.js           # Client-side functionality
├── usuarios-perfis.json         # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── usuarios-perfis/
    │       ├── usuarios-perfis-adicionar/
    │       └── usuarios-perfis-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Permission Matrix

### Profile Permissions Structure
```
Profile: Admin
├── dashboard
│   └── inicio ✓
├── usuarios
│   ├── listar ✓
│   ├── adicionar ✓
│   ├── editar ✓
│   └── excluir ✓
├── admin-paginas
│   ├── listar ✓
│   ├── adicionar ✓
│   ├── editar ✓
│   └── excluir ✗
└── ...
```

## 🎨 User Interface

### Profile List
- Table of all profiles
- User count per profile
- Edit/Delete actions

### Add/Edit Profile Form
- **Name**: Profile display name
- **Modules Section**: 
  - List of all modules
  - Checkbox for each operation
  - Select All / Deselect All buttons
  - Expandable module groups

### Permission Interface
```
[x] Módulo: Dashboard
    [x] inicio
    
[x] Módulo: Usuários
    [x] listar
    [x] adicionar
    [x] editar
    [ ] excluir

[ ] Módulo: Admin Plugins
    [ ] listar
    [ ] adicionar
    [ ] editar
    [ ] executar
```

## 💡 Common Profiles

| Profile | Description |
|---------|-------------|
| `Administrador` | Full system access |
| `Editor` | Content management only |
| `Visualizador` | Read-only access |
| `Moderador` | User and content management |

## 🔐 Permission Check Flow

### How Permissions Work
1. User logs in
2. System loads user's profile
3. Profile permissions are cached
4. Each page/action checks permissions
5. Unauthorized access is blocked

### PHP Permission Check
```php
// Check module access
if (!temPermissaoModulo('admin-paginas')) {
    redirect('sem-permissao');
}

// Check specific operation
if (temPermissao('admin-paginas', 'editar')) {
    // Show edit button
}
```

## 🔗 Related Modules
- `usuarios`: User management
- `modulos`: Module definitions
- `modulos-operacoes`: Operation definitions
