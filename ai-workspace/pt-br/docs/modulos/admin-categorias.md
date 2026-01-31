# Módulo: admin-categorias

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-categorias` |
| **Nome** | Administração de Categorias |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟢 Baixa |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-categorias** gerencia o **sistema hierárquico de categorias** no Conn2Flow. Categorias são usadas para organizar conteúdo, arquivos e outros recursos pelo CMS. O módulo suporta categorias aninhadas (relações pai-filho) para organização flexível de conteúdo.

## 🏗️ Funcionalidades Principais

### 🗂️ **Gerenciamento de Categorias**
- **Criar categorias**: Adicionar novas categorias com nome e pai opcional
- **Editar categorias**: Modificar informações de categorias existentes
- **Excluir categorias**: Remover categorias (com verificação de dependências)
- **Estrutura hierárquica**: Suporte a relações pai-filho

### 🌳 **Categorias Aninhadas**
- **Categorias pai**: Grupos organizacionais de nível superior
- **Categorias filho**: Sub-categorias sob pais
- **Profundidade ilimitada**: Múltiplos níveis de aninhamento
- **Visualização em árvore**: Exibição hierárquica na lista

### 🔗 **Integração**
- **Módulo de arquivos**: Categorizar arquivos enviados
- **Módulos de conteúdo**: Organizar páginas e posts
- **Publisher**: Marcar conteúdo publicado

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `categorias`
```sql
CREATE TABLE categorias (
    id_categorias INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    id_categorias_pai INT NULL,           -- Referência à categoria pai
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_categorias_pai) REFERENCES categorias(id_categorias)
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-categorias/
├── admin-categorias.php         # Controlador principal
├── admin-categorias.js          # Funcionalidade client-side
├── admin-categorias.json        # Configuração do módulo
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-categorias/
    │       ├── admin-categorias-adicionar/
    │       ├── admin-categorias-editar/
    │       └── admin-categorias-adicionar-filho/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Operações Principais

### Operações CRUD
- **Listar**: Exibir todas as categorias com hierarquia
- **Adicionar**: Criar nova categoria raiz ou filha
- **Editar**: Modificar nome e pai da categoria
- **Adicionar Filho**: Ação rápida para adicionar subcategoria
- **Excluir**: Remover categoria (verifica dependências)

## 🎨 Interface do Usuário

### Lista de Categorias
- Exibição em árvore das categorias
- Indentação para categorias filhas
- Botões de ação rápida (editar, adicionar filho, excluir)
- Funcionalidade de busca/filtro

### Formulário de Adicionar/Editar
- Campo de nome (obrigatório)
- Dropdown de categoria pai (opcional)
- Toggle de status

## 🔗 Módulos Relacionados
- `admin-arquivos`: Categorização de arquivos
- `publisher`: Categorização de conteúdo

## 💡 Boas Práticas
- Use nomes de categoria descritivos
- Planeje a hierarquia antes de criar categorias
- Evite aninhamento profundo (máximo 3-4 níveis recomendado)
- Verifique dependências antes de excluir
