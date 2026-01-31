# Módulo: modulos-grupos

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `modulos-grupos` |
| **Nome** | Grupos de Módulos |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Core |
| **Complexidade** | 🟢 Baixa |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **modulos-grupos** gerencia os **grupos organizacionais de módulos** no Conn2Flow. Grupos ajudam a organizar módulos relacionados em seções lógicas no menu administrativo, melhorando a navegabilidade e a experiência do usuário.

## 🏗️ Funcionalidades Principais

### 🗂️ **Gerenciamento de Grupos**
- **Criar grupos**: Adicionar novos grupos organizacionais
- **Editar grupos**: Modificar nome e ícone
- **Ordenar grupos**: Definir ordem no menu
- **Excluir grupos**: Remover grupos (reassocia módulos)

### 🎨 **Personalização**
- **Ícones**: Escolher ícone representativo
- **Cores**: Personalização visual (opcional)
- **Expansão**: Estado inicial (expandido/colapsado)

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `modulos_grupos`
```sql
CREATE TABLE modulos_grupos (
    id_modulos_grupos INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    icone VARCHAR(100),                  -- Classe do ícone Fomantic-UI
    ordem INT DEFAULT 0,                 -- Posição no menu
    expandido CHAR(1) DEFAULT 'S',       -- S = Expandido por padrão
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/modulos-grupos/
├── modulos-grupos.php           # Controlador principal
├── modulos-grupos.js            # Funcionalidade client-side
├── modulos-grupos.json          # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── modal-grupo/
    │   └── pages/
    │       ├── modulos-grupos/
    │       ├── modulos-grupos-adicionar/
    │       └── modulos-grupos-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Grupos Padrão do Sistema

### Grupos Integrados
```json
[
    {
        "id": "administracao",
        "nome": "Administração",
        "icone": "cog",
        "ordem": 1
    },
    {
        "id": "conteudo",
        "nome": "Conteúdo",
        "icone": "file alternate",
        "ordem": 2
    },
    {
        "id": "usuarios",
        "nome": "Usuários",
        "icone": "users",
        "ordem": 3
    },
    {
        "id": "configuracoes",
        "nome": "Configurações",
        "icone": "settings",
        "ordem": 4
    }
]
```

## 🎨 Interface do Usuário

### Lista de Grupos
- Tabela ordenável com drag-and-drop
- Ícone de cada grupo
- Contagem de módulos no grupo
- Ações rápidas (editar, excluir)

### Formulário de Edição
- **Nome**: Nome de exibição do grupo
- **ID**: Identificador único (gerado automaticamente)
- **Ícone**: Seletor de ícone visual
- **Ordem**: Posição numérica
- **Expandido**: Toggle estado inicial

## 🔄 Comportamento no Menu

### Renderização
```html
<!-- Grupo no sidebar -->
<div class="item grupo" data-grupo="administracao">
    <i class="cog icon"></i>
    <span>Administração</span>
    <i class="dropdown icon"></i>
    
    <div class="menu">
        <!-- Módulos do grupo -->
        <a class="item" href="/modulo-1">Módulo 1</a>
        <a class="item" href="/modulo-2">Módulo 2</a>
    </div>
</div>
```

### Estados
- **Expandido**: Módulos visíveis ao carregar
- **Colapsado**: Clique para expandir
- **Memória**: Estado salvo por usuário (opcional)

## 💡 Boas Práticas

### Organização
- Limite a 5-7 grupos principais
- Use nomes curtos e descritivos
- Agrupe por função, não por técnica

### Ícones
- Escolha ícones intuitivos
- Mantenha consistência visual
- Use biblioteca Fomantic-UI

### Ordenação
- Grupos mais usados primeiro
- Configurações geralmente no final
- Considere fluxo de trabalho do usuário

## 🔗 Módulos Relacionados
- `modulos`: Gerenciamento de módulos
- `modulos-operacoes`: Operações CRUD
