# Módulo: usuarios-perfis

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `usuarios-perfis` |
| **Nome** | Perfis de Usuários |
| **Versão** | `1.0.1` |
| **Categoria** | Módulo Core |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco` |

## 🎯 Propósito

O módulo **usuarios-perfis** gerencia os **perfis de usuário e suas permissões** no Conn2Flow. Perfis são conjuntos pré-definidos de permissões que podem ser atribuídos a usuários, simplificando o gerenciamento de acesso ao sistema.

## 🏗️ Funcionalidades Principais

### 👥 **Gerenciamento de Perfis**
- **Criar perfis**: Definir novos conjuntos de permissões
- **Editar perfis**: Modificar permissões existentes
- **Duplicar perfis**: Criar variações de perfis
- **Excluir perfis**: Remover perfis não utilizados

### 🔐 **Gerenciamento de Permissões**
- **Por módulo**: Permissões específicas por módulo
- **Por operação**: Controle granular de ações
- **Herança**: Perfis podem herdar de outros
- **Matriz visual**: Interface de grid para permissões

### 📊 **Relatórios**
- **Usuários por perfil**: Contagem de usuários
- **Comparação**: Comparar permissões entre perfis
- **Auditoria**: Histórico de mudanças

## 🗄️ Estrutura do Banco de Dados

### Tabela: `usuarios_perfis`
```sql
CREATE TABLE usuarios_perfis (
    id_usuarios_perfis INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    permissoes JSON,                     -- Objeto de permissões
    perfil_pai VARCHAR(255),             -- Herança de perfil
    nivel INT DEFAULT 0,                 -- Nível hierárquico
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Estrutura de Permissões (JSON)
```json
{
    "modulos": {
        "usuarios": {
            "visualizar": true,
            "adicionar": true,
            "editar": true,
            "excluir": false
        },
        "admin-paginas": {
            "visualizar": true,
            "adicionar": true,
            "editar": true,
            "excluir": true
        }
    }
}
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/usuarios-perfis/
├── usuarios-perfis.php          # Controlador principal
├── usuarios-perfis.js           # Funcionalidade client-side
├── usuarios-perfis.json         # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── matriz-permissoes/
    │   │   └── modal-perfil/
    │   └── pages/
    │       ├── usuarios-perfis/
    │       ├── usuarios-perfis-adicionar/
    │       └── usuarios-perfis-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Perfis Padrão do Sistema

### Super Administrador
```json
{
    "id": "super-admin",
    "nome": "Super Administrador",
    "nivel": 100,
    "permissoes": {
        "modulos": {
            "*": {
                "*": true
            }
        }
    }
}
```

### Administrador
```json
{
    "id": "admin",
    "nome": "Administrador",
    "nivel": 80,
    "permissoes": {
        "modulos": {
            "usuarios": {
                "visualizar": true,
                "adicionar": true,
                "editar": true,
                "excluir": false
            },
            "admin-*": {
                "*": true
            }
        }
    }
}
```

### Editor
```json
{
    "id": "editor",
    "nome": "Editor",
    "nivel": 50,
    "permissoes": {
        "modulos": {
            "publisher": {
                "*": true
            },
            "publisher-paginas": {
                "*": true
            }
        }
    }
}
```

## 🎨 Interface do Usuário

### Lista de Perfis
- Cards ou tabela de perfis
- Contagem de usuários
- Nível hierárquico
- Ações rápidas

### Matriz de Permissões
- Grid módulos × operações
- Checkboxes para ativar/desativar
- Seleção em massa por linha/coluna
- Visualização de herança

### Formulário de Perfil
- **Nome**: Nome do perfil
- **Descrição**: Propósito do perfil
- **Nível**: Hierarquia numérica
- **Perfil Pai**: Herança (opcional)
- **Permissões**: Matriz interativa

## 🔄 Sistema de Herança

### Como Funciona
```php
function obterPermissoesEfetivas($perfilId) {
    $perfil = buscar('usuarios_perfis', ['id' => $perfilId]);
    
    // Se tem pai, herda permissões
    if ($perfil['perfil_pai']) {
        $permissoesPai = obterPermissoesEfetivas($perfil['perfil_pai']);
        $permissoes = array_merge_recursive(
            $permissoesPai,
            $perfil['permissoes']
        );
    } else {
        $permissoes = $perfil['permissoes'];
    }
    
    return $permissoes;
}
```

### Regras
- Permissões do filho sobrescrevem do pai
- Profundidade máxima recomendada: 3 níveis
- Evitar dependências circulares

## 🔐 Verificação de Permissões

### Função de Verificação
```php
function temPermissao($usuarioId, $moduloId, $operacao) {
    // Obter perfil do usuário
    $usuario = buscar('usuarios', ['id' => $usuarioId]);
    $permissoes = obterPermissoesEfetivas($usuario['id_perfil']);
    
    // Verificar wildcard (super admin)
    if (isset($permissoes['modulos']['*']['*']) 
        && $permissoes['modulos']['*']['*'] === true) {
        return true;
    }
    
    // Verificar permissão específica
    return isset($permissoes['modulos'][$moduloId][$operacao])
        && $permissoes['modulos'][$moduloId][$operacao] === true;
}
```

## 💡 Boas Práticas

### Design de Perfis
- Crie perfis baseados em funções
- Use herança para evitar duplicação
- Mantenha granularidade adequada

### Segurança
- Revise permissões periodicamente
- Documente propósito de cada perfil
- Limite usuários com acesso total

### Manutenção
- Evite muitos perfis (máximo 10-15)
- Use nomes descritivos
- Mantenha hierarquia clara

## 🔗 Módulos Relacionados
- `usuarios`: Usuários que usam perfis
- `modulos-operacoes`: Operações controladas
- `modulos`: Módulos com permissões
