# Módulo: modulos-operacoes

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `modulos-operacoes` |
| **Nome** | Operações de Módulos |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Core |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco` |

## 🎯 Propósito

O módulo **modulos-operacoes** gerencia as **operações (ações) disponíveis nos módulos** do Conn2Flow. Operações são ações específicas que usuários podem realizar em cada módulo, como "visualizar", "editar", "excluir", etc. Este módulo é fundamental para o sistema de permissões granulares.

## 🏗️ Funcionalidades Principais

### ⚡ **Gerenciamento de Operações**
- **Listar operações**: Ver operações de cada módulo
- **Criar operações**: Adicionar novas ações
- **Editar operações**: Modificar configurações
- **Excluir operações**: Remover ações

### 🔐 **Integração com Permissões**
- **Base para permissões**: Operações definem o que pode ser controlado
- **Por perfil**: Vincular operações a perfis de usuário
- **Auditoria**: Rastrear uso de operações

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `modulos_operacoes`
```sql
CREATE TABLE modulos_operacoes (
    id_modulos_operacoes INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    id_modulo VARCHAR(255) NOT NULL,     -- Módulo pai
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    codigo VARCHAR(100),                 -- Código para verificação
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_modulo) REFERENCES modulos(id)
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/modulos-operacoes/
├── modulos-operacoes.php        # Controlador principal
├── modulos-operacoes.js         # Funcionalidade client-side
├── modulos-operacoes.json       # Configuração do módulo
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── modulos-operacoes/
    │       └── modulos-operacoes-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Operações Padrão

### Operações Comuns
```json
[
    {
        "codigo": "visualizar",
        "nome": "Visualizar",
        "descricao": "Permite visualizar registros"
    },
    {
        "codigo": "adicionar",
        "nome": "Adicionar",
        "descricao": "Permite criar novos registros"
    },
    {
        "codigo": "editar",
        "nome": "Editar",
        "descricao": "Permite modificar registros existentes"
    },
    {
        "codigo": "excluir",
        "nome": "Excluir",
        "descricao": "Permite remover registros"
    }
]
```

### Operações Especiais
```json
[
    {
        "codigo": "exportar",
        "nome": "Exportar",
        "descricao": "Permite exportar dados"
    },
    {
        "codigo": "importar",
        "nome": "Importar",
        "descricao": "Permite importar dados"
    },
    {
        "codigo": "configurar",
        "nome": "Configurar",
        "descricao": "Permite alterar configurações"
    }
]
```

## 🎨 Interface do Usuário

### Lista de Operações
- Agrupado por módulo
- Código e nome da operação
- Descrição
- Ações de edição

### Formulário de Edição
- **Módulo**: Módulo pai (readonly se editando)
- **Código**: Identificador para verificação
- **Nome**: Nome de exibição
- **Descrição**: Explicação da operação

## 🔄 Uso no Sistema de Permissões

### Verificação de Permissão
```php
// Verificar se usuário pode executar operação
function podeExecutar($usuarioId, $moduloId, $operacaoCodigo) {
    $perfil = obterPerfilUsuario($usuarioId);
    $permissoes = obterPermissoesPerfil($perfil);
    
    return isset($permissoes[$moduloId][$operacaoCodigo]) 
        && $permissoes[$moduloId][$operacaoCodigo] === true;
}

// Uso
if (podeExecutar($usuarioId, 'usuarios', 'editar')) {
    // Permitir edição
}
```

### Definição no Módulo
```json
// {modulo-id}.json
{
    "id": "meu-modulo",
    "nome": "Meu Módulo",
    "operacoes": [
        {
            "codigo": "visualizar",
            "nome": "Visualizar"
        },
        {
            "codigo": "editar",
            "nome": "Editar"
        },
        {
            "codigo": "aprovar",
            "nome": "Aprovar",
            "descricao": "Operação customizada para aprovar itens"
        }
    ]
}
```

## 💡 Boas Práticas

### Nomenclatura
- Use verbos no infinitivo: "visualizar", "editar"
- Códigos em minúsculas sem espaços
- Nomes claros e concisos

### Granularidade
- Não crie operações demais
- Agrupe ações relacionadas
- Mantenha consistência entre módulos

### Documentação
- Documente operações customizadas
- Explique casos de uso
- Mantenha lista atualizada

## 🔗 Módulos Relacionados
- `modulos`: Gerenciamento de módulos
- `usuarios-perfis`: Perfis que usam operações
- `usuarios`: Usuários com permissões
