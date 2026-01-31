# Module: admin-updates

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-atualizacoes` |
| **Name** | System Updates |
| **Version** | `1.0.2` |
| **Category** | Administrative Module |
| **Complexity** | 🔴 High |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-updates** module is responsible for **managing system updates** in Conn2Flow. It provides a centralized interface for checking, downloading, and applying updates to the CMS, ensuring the system stays up-to-date with the latest features and security patches.

## 🏗️ Main Features

### 🔄 **Update Management**
- **Version checking**: Automatic detection of available updates from GitHub
- **Update execution**: One-click update process
- **Log viewing**: Detailed execution logs for troubleshooting
- **Rollback support**: Ability to revert to previous versions if needed

### 📊 **Update History**
- **Execution tracking**: Records of all update attempts
- **Status monitoring**: Success/failure status for each update
- **Timestamp logging**: When updates were applied

### 🔐 **Permission Control**
- **Admin-only access**: Only host administrators can view and execute updates
- **Version comparison**: Smart comparison between local and remote versions

## 🗄️ Database Structure

### Main Table: `atualizacoes_execucoes`
```sql
CREATE TABLE atualizacoes_execucoes (
    id_atualizacoes_execucoes INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    versao_origem VARCHAR(50),           -- Source version
    versao_destino VARCHAR(50),          -- Target version
    status CHAR(1) DEFAULT 'A',
    log TEXT,                            -- Execution log
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/admin-atualizacoes/
├── admin-atualizacoes.php       # Main module controller
├── admin-atualizacoes.js        # Client-side functionality
├── admin-atualizacoes.json      # Module configuration
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── atualizacoes-lista/
    │   │   └── atualizacoes-detalhe-comp/
    │   └── pages/
    │       ├── admin-atualizacoes/
    │       └── admin-atualizacoes-detalhe/
    └── en/
        └── ... (same structure)
```

## 🔧 Core Functions

### `descobrirUltimaTagGestor()`
Fetches the latest release tag from GitHub API to compare with local version.

### Update Check Flow
1. User accesses update module
2. System calls GitHub API to get latest release
3. Compares remote version with local `$_GESTOR['gestor-cliente']['versao']`
4. Displays available update or "up-to-date" message

## 🎨 User Interface

### Update List Page
- Shows current system version
- Displays available updates (if any)
- "Execute Update" button for applying updates
- History of previous update executions

### Update Detail Page
- Detailed log of update execution
- Timestamp information
- Status (success/failure)

## 🔗 Related Modules
- `dashboard`: Shows update notifications
- `modulos`: System modules affected by updates

## ⚠️ Important Notes
- Always backup before updating
- Updates require administrator privileges
- Internet connection required for version checking
