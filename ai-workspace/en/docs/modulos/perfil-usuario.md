# Module: user-profile

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `perfil-usuario` |
| **Name** | User Profile |
| **Version** | `1.2.4` |
| **Category** | User Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html`, `usuario` |

## 🎯 Purpose

The **user-profile** module provides **authentication and user self-service** functionality in Conn2Flow. It handles login/logout, password management, profile editing, and OAuth integration. This is distinct from `usuarios-perfis` which manages roles.

## 🏗️ Main Features

### 🔐 **Authentication**
- **Sign In**: User login with username/password
- **Sign Out**: Secure logout
- **OAuth 2.0**: Third-party authentication
- **Session management**: JWT tokens

### 👤 **Profile Management**
- **Edit profile**: Update personal information
- **Change password**: Self-service password update
- **Avatar management**: Profile picture

### 🔑 **Password Recovery**
- **Forgot password**: Email-based recovery
- **Password reset**: Secure reset flow
- **Email validation**: Verify user identity

## 🗄️ Database Integration

Uses the same `usuarios` table as the users module:
```sql
-- User authentication fields
usuario VARCHAR(100) UNIQUE NOT NULL,
senha VARCHAR(255) NOT NULL,
token_recuperacao VARCHAR(255),
token_expiracao DATETIME
```

## � File Structure

```
gestor/modulos/perfil-usuario/
├── perfil-usuario.php           # Main module controller
├── perfil-usuario.js            # Client-side functionality
├── perfil-usuario.json          # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── acessar-sistema/     (signin)
    │       ├── sair-sistema/        (signout)
    │       ├── perfil-usuario/
    │       ├── validar-usuario/
    │       ├── recuperar-senha/
    │       └── oauth-authenticate/
    └── en/
        └── ... (same structure)
```

## 🔧 Authentication Pages

| Page | Path | Description |
|------|------|-------------|
| Sign In | `/signin/` | Login page |
| Sign Out | `/signout/` | Logout handler |
| Profile | `/perfil-usuario/` | Edit user profile |
| Recover Password | `/recuperar-senha/` | Password recovery |
| Validate User | `/validar-usuario/` | Email validation |
| OAuth | `/oauth-authenticate/` | OAuth 2.0 flow |

## 🔐 Authentication Flow

### Login Process
1. User enters credentials
2. System validates username/password
3. Creates JWT session token
4. Stores session in database
5. Redirects to dashboard

### Logout Process
1. User clicks logout
2. Session token invalidated
3. Database session removed
4. Redirects to login page

### OAuth Flow
1. User clicks OAuth provider
2. Redirect to provider authorization
3. Provider callback with token
4. System validates and creates session
5. Redirects to dashboard

## 🎨 User Interface

### Login Page
- Username field
- Password field
- Remember me checkbox
- Login button
- Forgot password link
- OAuth provider buttons

### Profile Edit Page
- Name field
- Email field
- Phone field
- Current password
- New password (with confirmation)
- Save button

## 🔐 Security Features

### Password Security
- Bcrypt hashing
- Minimum length requirements
- No password hints stored

### Session Security
- JWT tokens
- Secure cookies
- Session expiration
- IP verification (optional)

### Rate Limiting
- Login attempt limits
- Lockout after failures
- CAPTCHA after failures

## 💡 Configuration

### Session Settings
```php
$_CONFIG['session_duration'] = 3600; // 1 hour
$_CONFIG['remember_duration'] = 604800; // 1 week
$_CONFIG['max_login_attempts'] = 5;
```

## 🔗 Related Modules
- `usuarios`: User management (admin)
- `usuarios-perfis`: Role management
- `admin-environment`: OAuth testing
