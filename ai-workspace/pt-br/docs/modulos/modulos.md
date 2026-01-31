# Módulo: modulos

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `modulos` |
| **Nome** | Administração de Módulos |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Core |
| **Complexidade** | 🔴 Alta |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco` |

## 🎯 Propósito

O módulo **modulos** é o **gerenciador central de módulos** do Conn2Flow. Ele controla a visibilidade, ordenação e organização de todos os módulos do sistema no menu administrativo. Também gerencia grupos de módulos para melhor organização.

## 🏗️ Funcionalidades Principais

### 📦 **Gerenciamento de Módulos**
- **Listar módulos**: Ver todos os módulos disponíveis
- **Editar módulos**: Modificar configurações de exibição
- **Ordenar**: Arrastar e soltar para reordenar
- **Agrupar**: Organizar módulos em grupos

### 👁️ **Controle de Visibilidade**
- **Mostrar/Ocultar**: Controlar visibilidade no menu
- **Por perfil**: Visibilidade baseada em perfil de usuário
- **Por permissão**: Controle granular de acesso

### 🗂️ **Grupos de Módulos**
- **Criar grupos**: Organização lógica de módulos
- **Ícones**: Personalizar ícones dos grupos
- **Ordenação**: Ordem dos grupos no menu

## 🗄️ Estrutura do Banco de Dados

### Tabela: `modulos`
```sql
CREATE TABLE modulos (
    id_modulos INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    icone VARCHAR(100),                  -- Classe do ícone
    id_grupo VARCHAR(255),               -- Grupo pai
    ordem INT DEFAULT 0,                 -- Posição no menu
    visivel CHAR(1) DEFAULT 'S',         -- S = Visível, N = Oculto
    plugin VARCHAR(255),                 -- ID do plugin (se for de plugin)
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Tabela: `modulos_grupos`
```sql
CREATE TABLE modulos_grupos (
    id_modulos_grupos INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    icone VARCHAR(100),
    ordem INT DEFAULT 0,
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/modulos/
├── modulos.php                  # Controlador principal
├── modulos.js                   # Funcionalidade client-side
├── modulos.json                 # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── modal-modulo/
    │   │   ├── modal-grupo/
    │   │   └── lista-ordenavel/
    │   └── pages/
    │       ├── modulos/
    │       ├── modulos-editar/
    │       └── modulos-grupos/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Estrutura de um Módulo

### Arquivos Obrigatórios
```
gestor/modulos/{modulo-id}/
├── {modulo-id}.php              # Controlador principal
├── {modulo-id}.js               # JavaScript client-side
├── {modulo-id}.json             # Configuração e metadados
└── resources/                   # Recursos do módulo
    ├── pt-br/
    │   ├── components/          # Componentes
    │   └── pages/               # Páginas
    └── en/
        └── ...
```

### Arquivo {modulo-id}.json
```json
{
    "id": "meu-modulo",
    "nome": "Meu Módulo",
    "versao": "1.0.0",
    "descricao": "Descrição do módulo",
    "icone": "box",
    "grupo": "administracao",
    "ordem": 10,
    "permissoes": ["visualizar", "editar", "excluir"],
    "dependencias": ["interface", "html"]
}
```

## 🎨 Interface do Usuário

### Lista de Módulos
- Visualização em árvore (grupos > módulos)
- Drag-and-drop para reordenar
- Toggle de visibilidade
- Link rápido para configurações

### Formulário de Edição
- **Nome**: Nome de exibição
- **Ícone**: Seletor de ícone
- **Grupo**: Dropdown de grupos
- **Ordem**: Posição numérica
- **Visibilidade**: Toggle mostrar/ocultar

### Gerenciador de Grupos
- Lista de grupos existentes
- Criar/editar/excluir grupos
- Ordenar grupos

## 🔄 Fluxo de Carregamento

### 1. Inicialização
```php
// gestor.php carrega módulos ativos
$modulos = listar('modulos', ['visivel' => 'S', 'status' => 'A']);
```

### 2. Renderização do Menu
```php
// Agrupa módulos por grupo
$grupos = [];
foreach ($modulos as $modulo) {
    $grupos[$modulo['id_grupo']][] = $modulo;
}

// Ordena e renderiza menu
ordenarPorCampo($grupos, 'ordem');
```

### 3. Verificação de Permissões
```php
// Antes de exibir, verifica permissão
if (temPermissao($usuarioId, $moduloId, 'visualizar')) {
    renderizarItemMenu($modulo);
}
```

## 🔗 Módulos Relacionados
- `modulos-grupos`: Gerenciamento de grupos
- `modulos-operacoes`: Operações de módulos
- `usuarios-perfis`: Perfis que controlam acesso

## 💡 Boas Práticas

### Organização
- Agrupe módulos relacionados
- Use ícones descritivos
- Mantenha ordem lógica

### Nomenclatura
- IDs em kebab-case: `meu-modulo`
- Nomes descritivos e concisos
- Prefixe por função: `admin-`, `user-`
