# Conn2Flow - Projects System (Project Deployment System)

## Overview
Complete system for automated project deployment via OAuth 2.0 API, with secure inline processing and automatic token renewal.

## Architecture
- **API Endpoint**: `/api/project/update` - Receives ZIP uploads via multipart/form-data
- **Authentication**: OAuth 2.0 with automatic token renewal
- **Processing**: Inline execution (no shell_exec) for production safety
- **Deployment**: Direct extraction to system root (`$_GESTOR['ROOT_PATH']`)
- **Database**: Inline update via `atualizacoes-banco-de-dados.php`

## Main Components

### 1. API Endpoint (`gestor/controladores/api/api.php`)
```php
function api_project_update(){
    // Complete processing:
    // - OAuth validation
    // - ZIP Upload
    // - Direct extraction to root
    // - Inline database execution
    // - Temporary cleanup
}
```

### 2. Automation Scripts

#### `compactar-projeto.sh` - Compression and Upload
- Compresses project into ZIP
- Uploads via API with OAuth
- Automatic token renewal in case of 401
- Dynamic URLs based on environment.json

#### `renovar-token.sh` - OAuth Renewal
- Validates current token
- Automatically renews via refresh_token
- Updates environment.json
- Error handling and logs

#### `teste-integracao.sh` - Complete Tests
- Configuration validation
- Directory structure test
- Resource update
- Compression and upload
- Token renewal
- API connectivity

### 3. Configuration (`environment.json`)
```json
{
  "api_url": "https://api.conn2flow.com",
  "oauth": {
    "client_id": "...",
    "client_secret": "...",
    "access_token": "...",
    "refresh_token": "..."
  },
  "project": {
    "id": "conn2flow-gestor",
    "version": "1.0.0"
  }
}
```

## Workflow

### 1. Preparation
```bash
# Configure environment.json with OAuth credentials
# Validate project structure
```

### 2. Automated Deployment
```bash
# Execute compression and upload
./ai-workspace/scripts/projects/compactar-projeto.sh
```

### 3. API Processing
- ✅ OAuth token validation
- ✅ ZIP file upload
- ✅ Direct extraction to system root
- ✅ Inline execution of database update
- ✅ Cleanup of temporary files

## Implemented Security

### Inline Execution
- **Before**: `shell_exec("php atualizacoes-banco-de-dados.php")`
- **Now**: Direct include and inline execution
- **Reason**: Production servers disable shell_exec

### Automatic Token Renewal
- Automatic detection of 401 error
- Transparent renewal via refresh_token
- Automatic retry of operation
- No interruption in flow

### Direct Root Deployment
- **Before**: Extraction in subdirectory with project validation
- **Now**: Direct extraction in `$_GESTOR['ROOT_PATH']`
- **Reason**: Simplified and direct architecture

## Support Scripts

### Resource Update
- `atualizacao-dados-recursos.php` - Main script
- `atualizacao-dados-recursos.sh` - Automation with dynamic parameters
- Support for `--project-path` for custom paths

## Integration Tests
System validated with 6 main tests:
1. ✅ environment.json configuration
2. ✅ Project directory structure
3. ✅ Resource update
4. ✅ Project compression
5. ✅ OAuth token renewal
6. ✅ API connectivity

## Logs and Monitoring
- Detailed logs in `/logs/atualizacoes/`
- Execution persistence in `atualizacoes_execucoes`
- Deployment statistics (files copied/removed)
- Complete error handling

## Production Usage

### Prerequisites
- Valid OAuth credentials in `environment.json`
- Write permissions in system root
- PHP with file functions enabled

### Deployment Command
```bash
cd /path/to/project
./ai-workspace/scripts/projects/compactar-projeto.sh
```

### Verification
```bash
# Check logs
tail -f /logs/atualizacoes/$(date +%Y%m%d).log

# Check API status
curl -H "Authorization: Bearer <token>" https://api.conn2flow.com/api/project/status
```

## Maintenance
- OAuth tokens are automatically renewed
- Automatic cleanup of temporary files (>24h)
- Configurable log retention (default 14 days)
- Optional backup before deployment

## Troubleshooting

### Error 401 Unauthorized
- Check if OAuth token expired
- System attempts automatic renewal
- If persists, check credentials in environment.json

### Permissions Error
- Ensure root directory has www-data:www-data permissions
- Check if PHP can write files

### ZIP Extraction Failure
- Check if ZIP file is not corrupted
- Check available disk space
- Detailed logs in `/logs/atualizacoes/`

---
System implemented and tested successfully
Last update: 2025-01-27
