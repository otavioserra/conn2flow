# Módulo: admin-ia

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-ia` |
| **Nome** | Administração de IA - Alvos |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-ia** gerencia **alvos de IA** no Conn2Flow. Alvos definem onde e como o conteúdo gerado por IA será aplicado no sistema - seja uma página específica, componente, template ou outro recurso. Este módulo é parte do sistema de geração de conteúdo por IA do Conn2Flow.

## 🏗️ Funcionalidades Principais

### 🎯 **Gerenciamento de Alvos**
- **Criar alvos**: Definir onde o conteúdo de IA será aplicado
- **Editar alvos**: Modificar configurações de destino
- **Vincular recursos**: Conectar alvos a páginas, componentes, etc.
- **Controle de status**: Ativar/desativar alvos

### 🔗 **Tipos de Alvo**
- **Páginas**: Conteúdo gerado para páginas específicas
- **Componentes**: Conteúdo para componentes reutilizáveis
- **Templates**: Conteúdo base para templates
- **Variáveis**: Valores dinâmicos de variáveis

### 📊 **Integração**
- **Modos de IA**: Alvos associados a modos específicos
- **Prompts**: Instruções para geração de conteúdo
- **Publisher**: Publicação de conteúdo gerado

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `alvos_ia`
```sql
CREATE TABLE alvos_ia (
    id_alvos_ia INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo VARCHAR(50),                    -- pagina, componente, template, variavel
    recurso_id VARCHAR(255),             -- ID do recurso alvo
    configuracao JSON,                   -- Configurações específicas
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-ia/
├── admin-ia.php                 # Controlador principal
├── admin-ia.js                  # Funcionalidade client-side
├── admin-ia.json                # Configuração do módulo
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-ia/
    │       ├── admin-ia-adicionar/
    │       └── admin-ia-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Configuração de Alvo

### Exemplo de Configuração JSON
```json
{
    "tipo": "pagina",
    "recurso_id": "landing-page-produto",
    "campos": [
        "titulo",
        "descricao",
        "corpo"
    ],
    "restricoes": {
        "max_tokens": 2000,
        "idioma": "pt-br"
    }
}
```

## 🎨 Interface do Usuário

### Lista de Alvos
- Tabela com alvos cadastrados
- Tipo e recurso vinculado
- Status de ativação
- Ações rápidas (editar, excluir)

### Formulário de Edição
- **Nome**: Nome identificador do alvo
- **Descrição**: Descrição do propósito
- **Tipo**: Seleção do tipo de recurso
- **Recurso**: Seleção do recurso específico
- **Configuração**: Campos JSON avançados

## 🔄 Fluxo de Uso

### 1. Criar Alvo
1. Definir nome e descrição
2. Selecionar tipo de recurso
3. Vincular recurso específico
4. Configurar campos de destino

### 2. Associar a Modo de IA
- Alvo é selecionado em `admin-modos-ia`
- Modo define comportamento da geração

### 3. Usar com Prompt
- Prompt de IA referencia o alvo
- Conteúdo gerado é aplicado automaticamente

## 🔗 Módulos Relacionados
- `admin-modos-ia`: Modos de geração de IA
- `admin-prompts-ia`: Prompts de instrução
- `admin-paginas`: Páginas como alvos
- `admin-componentes`: Componentes como alvos

## 💡 Boas Práticas

### Organização
- Nomeie alvos descritivamente
- Agrupe por tipo de conteúdo
- Documente configurações complexas

### Uso
- Defina campos específicos para edição
- Configure restrições adequadas
- Teste antes de usar em produção
