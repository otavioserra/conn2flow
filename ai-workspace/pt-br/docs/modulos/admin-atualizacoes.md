# Módulo: admin-atualizacoes

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-atualizacoes` |
| **Nome** | Atualizações do Sistema |
| **Versão** | `1.0.2` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🔴 Alta |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-atualizacoes** é responsável pelo **gerenciamento de atualizações do sistema** no Conn2Flow. Ele fornece uma interface centralizada para verificar, baixar e aplicar atualizações ao CMS, garantindo que o sistema permaneça atualizado com os últimos recursos e correções de segurança.

## 🏗️ Funcionalidades Principais

### 🔄 **Gerenciamento de Atualizações**
- **Verificação de versão**: Detecção automática de atualizações disponíveis no GitHub
- **Execução de atualização**: Processo de atualização com um clique
- **Visualização de logs**: Logs detalhados de execução para troubleshooting
- **Suporte a rollback**: Capacidade de reverter para versões anteriores

### 📊 **Histórico de Atualizações**
- **Rastreamento de execução**: Registros de todas as tentativas de atualização
- **Monitoramento de status**: Status de sucesso/falha para cada atualização
- **Registro de timestamps**: Quando as atualizações foram aplicadas

### 🔐 **Controle de Permissões**
- **Acesso apenas para admins**: Somente administradores do host podem ver e executar atualizações
- **Comparação de versões**: Comparação inteligente entre versões local e remota

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `atualizacoes_execucoes`
```sql
CREATE TABLE atualizacoes_execucoes (
    id_atualizacoes_execucoes INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    versao_origem VARCHAR(50),           -- Versão de origem
    versao_destino VARCHAR(50),          -- Versão de destino
    status CHAR(1) DEFAULT 'A',
    log TEXT,                            -- Log de execução
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-atualizacoes/
├── admin-atualizacoes.php       # Controlador principal
├── admin-atualizacoes.js        # Funcionalidade client-side
├── admin-atualizacoes.json      # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── atualizacoes-lista/
    │   │   └── atualizacoes-detalhe-comp/
    │   └── pages/
    │       ├── admin-atualizacoes/
    │       └── admin-atualizacoes-detalhe/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Funções Principais

### `descobrirUltimaTagGestor()`
Busca a última tag de release na API do GitHub para comparar com a versão local.

### Fluxo de Verificação de Atualização
1. Usuário acessa o módulo de atualização
2. Sistema chama a API do GitHub para obter última release
3. Compara versão remota com `$_GESTOR['gestor-cliente']['versao']` local
4. Exibe atualização disponível ou mensagem "atualizado"

## 🎨 Interface do Usuário

### Página de Lista de Atualizações
- Mostra versão atual do sistema
- Exibe atualizações disponíveis (se houver)
- Botão "Executar Atualização" para aplicar
- Histórico de execuções anteriores

### Página de Detalhe da Atualização
- Log detalhado da execução
- Informações de timestamp
- Status (sucesso/falha)

## 🔗 Módulos Relacionados
- `dashboard`: Mostra notificações de atualização
- `modulos`: Módulos do sistema afetados por atualizações

## ⚠️ Notas Importantes
- Sempre faça backup antes de atualizar
- Atualizações requerem privilégios de administrador
- Conexão com internet necessária para verificação de versão
