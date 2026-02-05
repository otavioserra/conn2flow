# Arquitetura de Plugins do Conn2Flow

## Visão Geral

O sistema de plugins do Conn2Flow permite estender as funcionalidades do CMS de forma modular, sem modificar o código core. Plugins são extensões independentes que podem adicionar novos módulos, recursos, layouts, páginas e componentes.

## 📁 Estrutura de um Plugin

### Estrutura de Diretórios

```
gestor/plugins/{plugin-id}/
├── manifest.json               # Metadados e configuração do plugin
├── {plugin-id}.php             # Controlador principal (opcional)
├── {plugin-id}.js              # JavaScript client-side (opcional)
├── assets/                     # Arquivos estáticos (CSS, JS, imagens)
│   ├── css/
│   ├── js/
│   └── images/
├── db/
│   ├── migrations/             # Migrações Phinx para tabelas
│   └── data/                   # Arquivos JSON de dados
│       ├── ModulosData.json
│       ├── PaginasData.json
│       ├── LayoutsData.json
│       ├── ComponentesData.json
│       └── VariaveisData.json
├── modulos/                    # Módulos do plugin
│   └── {modulo-id}/
│       ├── {modulo-id}.php
│       ├── {modulo-id}.js
│       ├── {modulo-id}.json
│       └── resources/
└── resources/                  # Layouts, páginas, componentes
    ├── pt-br/
    │   ├── layouts/
    │   ├── pages/
    │   └── components/
    └── en/
        ├── layouts/
        ├── pages/
        └── components/
```

### Arquivo manifest.json

O `manifest.json` é o coração do plugin, contendo todos os metadados e configurações:

```json
{
    "id": "meu-plugin",
    "nome": "Meu Plugin Incrível",
    "versao": "1.0.0",
    "descricao": "Adiciona funcionalidades incríveis ao Conn2Flow",
    "autor": "Nome do Desenvolvedor",
    "repositorio": "https://github.com/dev/meu-plugin",
    "dependencias": {
        "gestor": ">=1.5.0",
        "php": ">=7.4"
    },
    "modulos": [
        "modulo-do-plugin"
    ],
    "migracoes": true,
    "recursos": true
}
```

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | string | Identificador único do plugin (slug) |
| `nome` | string | Nome de exibição |
| `versao` | string | Versão semântica (SemVer) |
| `descricao` | string | Descrição breve |
| `autor` | string | Nome do autor/empresa |
| `repositorio` | string | URL do repositório GitHub |
| `dependencias` | object | Dependências de versão |
| `modulos` | array | Lista de módulos incluídos |
| `migracoes` | boolean | Se possui migrações de banco |
| `recursos` | boolean | Se possui recursos (layouts, páginas, etc.) |

---

## 🗄️ Banco de Dados

### Tabela Principal: `plugins`

```sql
CREATE TABLE plugins (
    id_plugins INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,     -- Slug do plugin
    nome VARCHAR(255) NOT NULL,          -- Nome de exibição
    descricao TEXT,                      -- Descrição
    versao VARCHAR(50),                  -- Versão atual instalada
    autor VARCHAR(255),                  -- Autor
    repositorio VARCHAR(255),            -- URL do repositório
    ativo CHAR(1) DEFAULT 'N',           -- S = Ativo, N = Inativo
    status CHAR(1) DEFAULT 'A',          -- Status geral
    data_instalacao DATETIME,            -- Data de instalação
    data_atualizacao DATETIME,           -- Última atualização
    versao_reg INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Arquivos Data.json

Os plugins utilizam arquivos `*Data.json` para sincronizar dados com o banco:

| Arquivo | Tabela | Descrição |
|---------|--------|-----------|
| `ModulosData.json` | `modulos` | Configuração de módulos |
| `PaginasData.json` | `paginas` | Definição de páginas |
| `LayoutsData.json` | `layouts` | Definição de layouts |
| `ComponentesData.json` | `componentes` | Definição de componentes |
| `VariaveisData.json` | `variaveis` | Variáveis do sistema |

---

## 🔧 Ciclo de Vida do Plugin

### Estados do Plugin

| Status | Constante | Descrição |
|--------|-----------|-----------|
| Inativo | `PLG_STATUS_IDLE` | Sistema em repouso |
| Instalando | `PLG_STATUS_INSTALANDO` | Plugin sendo instalado |
| Atualizando | `PLG_STATUS_ATUALIZANDO` | Plugin sendo atualizado |
| Erro | `PLG_STATUS_ERRO` | Operação falhou |
| OK | `PLG_STATUS_OK` | Operação concluída |

### Códigos de Saída

| Código | Constante | Descrição |
|--------|-----------|-----------|
| 0 | `PLG_EXIT_OK` | Sucesso |
| 10 | `PLG_EXIT_PARAMS_OR_FILE` | Erro de parâmetros/arquivo |
| 11 | `PLG_EXIT_VALIDATE` | Falha na validação |
| 12 | `PLG_EXIT_MOVE` | Falha ao mover arquivos |
| 20 | `PLG_EXIT_DOWNLOAD` | Falha no download |
| 21 | `PLG_EXIT_ZIP_INVALID` | ZIP inválido |
| 22 | `PLG_EXIT_CHECKSUM` | Falha no checksum |

---

## 📦 Fluxo de Instalação

### Pipeline Completo

1. **Validação** - Verificação de parâmetros e origem
2. **Download/Cópia** - Obter pacote para staging (`temp/plugins/<slug>/`)
3. **Extração** - Descompactar ZIP em staging
4. **Validação de Manifest** - Verificar `manifest.json` e estrutura
5. **Backup** - Backup da instalação anterior (se existir)
6. **Movimentação** - Mover arquivos para `plugins/<slug>/`
7. **Migrações** - Executar migrações de banco (se habilitadas)
8. **Detecção de Data.json** - Detectar automaticamente todos os `*Data.json`
9. **Sincronização de Recursos** - Sincronizar dados para cada arquivo
10. **Sincronização de Módulos** - Processar `modules/*/module-id.json`
11. **Limpeza** - Remover pasta `db/` do plugin instalado
12. **Permissões** - Correção de permissões (chown recursivo)
13. **Persistência** - Atualizar metadados na tabela `plugins`
14. **Logging** - Log final e código de saída

### Origens Suportadas

| Origem | Descrição |
|--------|-----------|
| `upload` | ZIP local via upload |
| `github_publico` | Repositório GitHub público |
| `github_privado` | Repositório GitHub privado (com token) |
| `local_path` | Caminho local no servidor |

### Download do GitHub

#### Repositórios Públicos
```
https://github.com/{owner}/{repo}/releases/download/{tag}/gestor-plugin.zip
```

#### Repositórios Privados
Utiliza API REST de assets com autenticação:
```http
Authorization: token YOUR_TOKEN
Accept: application/octet-stream
User-Agent: Conn2Flow-Plugin-Manager/1.0
```

**Verificação de Integridade SHA256:**
- Download do arquivo `gestor-plugin.zip.sha256`
- Cálculo do hash do ZIP baixado
- Comparação e validação antes de prosseguir

---

## 🔌 Bibliotecas do Sistema

### plugins.php

Template base para funções de plugins.

**Localização**: `gestor/bibliotecas/plugins.php`

```php
function plugin_validar($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Parâmetros:
    // plugin_id - Int - Obrigatório - ID do plugin.
    // ===== 
    
    if(!isset($plugin_id)){
        return false;
    }
    
    // Validação do plugin...
    return true;
}
```

### plugins-installer.php

Sistema completo de instalação e gerenciamento.

**Localização**: `gestor/bibliotecas/plugins-installer.php`  
**Total de Funções**: 43

#### Principais Funções

| Categoria | Funções |
|-----------|---------|
| **Instalação** | `plugins_installer_install()`, `plugins_installer_download()`, `plugins_installer_extract()` |
| **Atualização** | `plugins_installer_update()`, `plugins_installer_verificar_versao()`, `plugins_installer_backup_antes_update()` |
| **Desinstalação** | `plugins_installer_uninstall()`, `plugins_installer_remover_arquivos()`, `plugins_installer_remover_tabelas()` |
| **Ativação** | `plugins_installer_activate()`, `plugins_installer_deactivate()`, `plugins_installer_verificar_ativo()` |
| **Dependências** | `plugins_installer_verificar_dependencias()`, `plugins_installer_resolver_conflitos()` |

### plugins-consts.php

Constantes e códigos de status.

**Localização**: `gestor/bibliotecas/plugins-consts.php`

```php
// Códigos de Saída
define('PLG_EXIT_OK', 0);
define('PLG_EXIT_PARAMS_OR_FILE', 10);
define('PLG_EXIT_VALIDATE', 11);
define('PLG_EXIT_MOVE', 12);
define('PLG_EXIT_DOWNLOAD', 20);
define('PLG_EXIT_ZIP_INVALID', 21);
define('PLG_EXIT_CHECKSUM', 22);

// Status de Execução
define('PLG_STATUS_IDLE', 'idle');
define('PLG_STATUS_INSTALANDO', 'instalando');
define('PLG_STATUS_ATUALIZANDO', 'atualizando');
define('PLG_STATUS_ERRO', 'erro');
define('PLG_STATUS_OK', 'ok');
```

---

## 🎨 Módulo Administrativo

### admin-plugins

O módulo `admin-plugins` gerencia a interface de administração de plugins.

**Localização**: `gestor/modulos/admin-plugins/`

#### Funcionalidades

| Recurso | Descrição |
|---------|-----------|
| **Descoberta** | Navegar plugins disponíveis no marketplace |
| **Download** | Buscar pacotes de repositórios |
| **Instalação** | Executar migrações e setup |
| **Ativação** | Habilitar/desabilitar funcionalidade |
| **Atualização** | Aplicar novas versões |
| **Remoção** | Desinstalar completamente |
| **Monitoramento** | Verificar versão e saúde |

#### Interface do Usuário

- **Lista de Plugins**: Grade de cards com info, status e ações
- **Detalhe do Plugin**: Descrição, versões, configurações

---

## ⚠️ Segurança

### Validações de Instalação

- ✅ Verificar assinatura do pacote
- ✅ Validar estrutura do `manifest.json`
- ✅ Verificar checksum SHA256
- ✅ Escanear código malicioso
- ✅ Executar em sandbox primeiro

### Permissões

- Apenas administradores podem instalar plugins
- Logs de auditoria para todas as mudanças
- Backup automático antes de atualizar/remover

---

## 📍 Localizações Importantes

| Arquivo/Diretório | Descrição |
|-------------------|-----------|
| `gestor/plugins/` | Diretório de plugins instalados |
| `gestor/bibliotecas/plugins-installer.php` | Código principal do instalador |
| `gestor/controladores/plugins/atualizacao-plugin.php` | Orquestração CLI |
| `gestor/logs/plugins/installer.log` | Logs de instalação |
| `gestor/plugins/_backups/` | Backups de versões anteriores |
| `gestor/temp/plugins/` | Staging de instalação |

---

## 🔗 Documentação Relacionada

- [Fluxo do Instalador de Plugins](./CONN2FLOW-PLUGIN-INSTALADOR-FLUXO.md) - Detalhes do pipeline de instalação
- [Biblioteca plugins.php](./bibliotecas/BIBLIOTECA-PLUGINS.md) - Template de funções
- [Biblioteca plugins-installer.php](./bibliotecas/BIBLIOTECA-PLUGINS-INSTALLER.md) - Sistema de instalação
- [Biblioteca plugins-consts.php](./bibliotecas/BIBLIOTECA-PLUGINS-CONSTS.md) - Constantes e códigos
- [Módulo admin-plugins](./modulos/admin-plugins.md) - Interface administrativa

---

## 🚀 Guia Rápido de Desenvolvimento

### 1. Criar Estrutura Básica

```bash
mkdir -p gestor/plugins/meu-plugin/{assets,db/{migrations,data},modulos,resources/{pt-br,en}}
```

### 2. Criar manifest.json

```json
{
    "id": "meu-plugin",
    "nome": "Meu Plugin",
    "versao": "1.0.0",
    "descricao": "Descrição do plugin",
    "autor": "Seu Nome",
    "repositorio": "https://github.com/seu-usuario/meu-plugin",
    "dependencias": {
        "gestor": ">=1.5.0"
    },
    "modulos": [],
    "migracoes": false,
    "recursos": true
}
```

### 3. Adicionar Recursos

Crie arquivos `*Data.json` em `db/data/` para sincronizar recursos.

### 4. Empacotar

```bash
cd gestor/plugins/meu-plugin
zip -r gestor-plugin.zip .
sha256sum gestor-plugin.zip > gestor-plugin.zip.sha256
```

### 5. Distribuir

Faça upload como release no GitHub ou distribua diretamente.

---

**Última Atualização**: Fevereiro 2026  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
