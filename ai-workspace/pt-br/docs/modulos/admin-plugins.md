# Módulo: admin-plugins

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-plugins` |
| **Nome** | Administração de Plugins |
| **Versão** | `1.0.1` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🔴 Alta |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `arquivos` |

## 🎯 Propósito

O módulo **admin-plugins** gerencia a **instalação, atualização e remoção de plugins** no Conn2Flow. Plugins são extensões modulares que adicionam novas funcionalidades ao CMS sem modificar o código core. Este módulo lida com o ciclo de vida completo dos plugins, do download à ativação.

## 🏗️ Funcionalidades Principais

### 📦 **Instalação de Plugins**
- **Descoberta**: Navegar plugins disponíveis no marketplace
- **Download**: Buscar pacotes de plugins de repositórios
- **Extração**: Descompactar e validar estrutura do plugin
- **Instalação**: Executar migrações e setup do plugin

### 🔄 **Gerenciamento de Ciclo de Vida**
- **Ativação**: Habilitar funcionalidade do plugin
- **Desativação**: Desabilitar sem remover
- **Atualização**: Aplicar novas versões
- **Remoção**: Desinstalar completamente (com limpeza de dados)

### 📊 **Monitoramento**
- **Verificação de versão**: Checar atualizações disponíveis
- **Saúde do plugin**: Monitorar status e erros
- **Gerenciamento de dependências**: Rastrear interdependências

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `plugins`
```sql
CREATE TABLE plugins (
    id_plugins INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    versao VARCHAR(50),                  -- Versão atual instalada
    autor VARCHAR(255),
    repositorio VARCHAR(255),            -- URL do repositório
    ativo CHAR(1) DEFAULT 'N',           -- S = Ativo, N = Inativo
    status CHAR(1) DEFAULT 'A',
    data_instalacao DATETIME,
    data_atualizacao DATETIME,
    versao_reg INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

### Arquivos do Módulo
```
gestor/modulos/admin-plugins/
├── admin-plugins.php            # Controlador principal
├── admin-plugins.js             # Funcionalidade client-side
├── admin-plugins.json           # Configuração do módulo
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-plugins/
    │       └── admin-plugins-detalhe/
    └── en/
        └── ... (mesma estrutura)
```

### Estrutura de um Plugin
```
gestor/plugins/{plugin-id}/
├── manifest.json               # Metadados e configuração
├── {plugin-id}.php             # Controlador principal
├── {plugin-id}.js              # JavaScript client-side
├── assets/                     # Arquivos estáticos
├── db/
│   ├── migrations/             # Migrações Phinx
│   └── data/                   # Arquivos JSON de dados
├── modulos/                    # Módulos do plugin
└── resources/                  # Layouts, páginas, componentes
```

## 🔧 Formato do Manifest

### manifest.json
```json
{
    "id": "meu-plugin",
    "nome": "Meu Plugin Incrível",
    "versao": "1.0.0",
    "descricao": "Adiciona funcionalidades incríveis ao Conn2Flow",
    "autor": "Nome do Desenvolvedor",
    "repositorio": "https://github.com/dev/meu-plugin",
    "dependencias": {
        "gestor": ">=1.5.0"
    },
    "modulos": [
        "modulo-do-plugin"
    ],
    "migrações": true,
    "recursos": true
}
```

## 🎨 Interface do Usuário

### Lista de Plugins
- Grade de cards com info do plugin
- Indicador de status (ativo/inativo)
- Badge de versão
- Botões de ação rápida

### Detalhe do Plugin
- Descrição completa
- Histórico de versões
- Controles de configuração
- Opções de desativação/remoção

## 🔧 Fluxo de Instalação

### 1. Download
```php
// Buscar release do repositório
$release = buscarUltimaRelease($repositorioUrl);
$pacote = baixarPacote($release['download_url']);
```

### 2. Extração e Validação
```php
// Extrair e verificar estrutura
$extraido = extrairZip($pacote, $diretorioTemp);
$valido = validarEstrutura($extraido, ['manifest.json']);
```

### 3. Instalação
```php
// Mover para pasta de plugins
moverPasta($extraido, "gestor/plugins/{$id}");

// Executar migrações
executarMigracoes("gestor/plugins/{$id}/db/migrations");

// Carregar dados
carregarDados("gestor/plugins/{$id}/db/data");
```

### 4. Ativação
```php
// Ativar plugin
atualizarBanco('plugins', ['ativo' => 'S'], ['id' => $id]);

// Carregar recursos
processarRecursos("gestor/plugins/{$id}/resources");
```

## ⚠️ Considerações de Segurança

### Instalação
- Verificar assinatura do pacote
- Validar manifest.json
- Escanear código malicioso
- Executar em sandbox primeiro

### Permissões
- Apenas admins podem instalar
- Logs de auditoria para mudanças
- Backup antes de atualizar/remover

## 🔗 Módulos Relacionados
- `admin-atualizacoes`: Atualizações do sistema
- `modulos`: Configuração de módulos (incluindo de plugins)
