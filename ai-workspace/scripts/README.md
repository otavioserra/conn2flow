# 🔧 Scripts Utilitários - Conn2Flow

Esta pasta contém scripts PHP e outras ferramentas utilitárias para desenvolvimento e manutenção.

## 📋 Scripts Disponíveis

### 🛠️ Instalação e Verificação
- **`check-installation.php`** - Verifica estado da instalação
- **`test-url-detection.php`** - Testa detecção de URLs

### 🔧 Desenvolvimento *(planejados)*
- **`backup-database.php`** - Script para backup do banco
- **`clear-cache.php`** - Limpa cache do sistema
- **`update-permissions.php`** - Atualiza permissões de arquivos
- **`validate-config.php`** - Valida configurações do sistema

## 🎯 Como Usar os Scripts

### Execução Local
```bash
php ai-workspace/scripts/check-installation.php
```

### Execução via Browser
```
http://localhost/conn2flow/ai-workspace/scripts/test-url-detection.php
```

### Execução com Parâmetros
```bash
php script.php --env=development --verbose
```

## 📝 Diretrizes para Novos Scripts

### 1. Estrutura Básica
```php
<?php
/**
 * Script: [Nome do Script]
 * Descrição: [O que faz]
 * Autor: Otavio Serra
 * Data: [Data de criação]
 */

// Configurações
require_once __DIR__ . '/../../gestor/config.php';

// Validações de segurança
if (!defined('GESTOR_INSTALADO')) {
    die('Sistema não configurado');
}

// Lógica principal
function main() {
    // Código aqui
}

// Execução
if (php_sapi_name() === 'cli') {
    main();
} else {
    // Validações web se necessário
    main();
}
?>
```

### 2. Boas Práticas
- **Documentar** propósito e uso do script
- **Validar** inputs e permissões
- **Logar** operações importantes
- **Testar** em ambiente seguro primeiro

### 3. Segurança
- Nunca expor credenciais
- Validar origem das requisições
- Usar sanitização adequada
- Implementar rate limiting se necessário

---
**Última atualização:** 30 de julho, 2025
**Estrutura:** ai-workspace/scripts/
