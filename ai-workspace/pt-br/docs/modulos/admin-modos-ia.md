# Módulo: admin-modos-ia

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-modos-ia` |
| **Nome** | Administração de IA - Modos |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-modos-ia** gerencia os **modos operacionais da IA** no Conn2Flow. Modos definem como a IA se comporta durante a geração de conteúdo - incluindo configurações de modelo, tom de voz, formato de saída e outras características comportamentais.

## 🏗️ Funcionalidades Principais

### ⚙️ **Gerenciamento de Modos**
- **Criar modos**: Definir novos comportamentos de IA
- **Editar modos**: Ajustar configurações existentes
- **Duplicar modos**: Criar variações de modos
- **Ativar/Desativar**: Controle de disponibilidade

### 🎭 **Configurações de Comportamento**
- **Tom de voz**: Formal, casual, técnico, etc.
- **Formato de saída**: Markdown, HTML, texto puro
- **Comprimento**: Curto, médio, longo
- **Criatividade**: Nível de temperatura do modelo

### 🔗 **Integrações**
- **Alvos de IA**: Onde o conteúdo será aplicado
- **Prompts**: Instruções base para geração
- **Modelos**: Seleção de modelo de linguagem

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `modos_ia`
```sql
CREATE TABLE modos_ia (
    id_modos_ia INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    modelo VARCHAR(100),                 -- gpt-4, claude-3, etc.
    temperatura DECIMAL(3,2),            -- 0.0 a 1.0
    max_tokens INT,
    tom_voz VARCHAR(50),
    formato_saida VARCHAR(50),
    configuracao JSON,                   -- Configurações adicionais
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-modos-ia/
├── admin-modos-ia.php           # Controlador principal
├── admin-modos-ia.js            # Funcionalidade client-side
├── admin-modos-ia.json          # Configuração do módulo
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-modos-ia/
    │       ├── admin-modos-ia-adicionar/
    │       └── admin-modos-ia-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Exemplos de Modos

### Modo: Redação Formal
```json
{
    "id": "redacao-formal",
    "nome": "Redação Formal",
    "modelo": "gpt-4",
    "temperatura": 0.3,
    "max_tokens": 2000,
    "tom_voz": "formal",
    "formato_saida": "html",
    "configuracao": {
        "estilo": "corporativo",
        "evitar": ["gírias", "coloquialismo"],
        "incluir": ["citações", "referências"]
    }
}
```

### Modo: Conteúdo Criativo
```json
{
    "id": "conteudo-criativo",
    "nome": "Conteúdo Criativo",
    "modelo": "gpt-4",
    "temperatura": 0.8,
    "max_tokens": 1500,
    "tom_voz": "casual",
    "formato_saida": "markdown",
    "configuracao": {
        "estilo": "narrativo",
        "emojis": true,
        "humor": "leve"
    }
}
```

### Modo: Documentação Técnica
```json
{
    "id": "doc-tecnica",
    "nome": "Documentação Técnica",
    "modelo": "claude-3",
    "temperatura": 0.2,
    "max_tokens": 3000,
    "tom_voz": "tecnico",
    "formato_saida": "markdown",
    "configuracao": {
        "estrutura": "secoes",
        "codigo": true,
        "exemplos": true
    }
}
```

## 🎨 Interface do Usuário

### Lista de Modos
- Cards com preview de configuração
- Indicador de modelo usado
- Badge de temperatura
- Ações rápidas

### Formulário de Edição
- **Nome**: Nome do modo
- **Descrição**: Propósito do modo
- **Modelo**: Seleção do LLM
- **Temperatura**: Slider de criatividade
- **Max Tokens**: Limite de resposta
- **Tom de Voz**: Dropdown de opções
- **Formato**: HTML, Markdown, Texto
- **Configuração JSON**: Opções avançadas

## 🔄 Uso no Sistema

### Seleção de Modo
1. Usuário acessa interface de geração de IA
2. Seleciona modo desejado
3. Sistema aplica configurações do modo
4. Conteúdo é gerado conforme modo

### Herança de Configurações
- Modos podem herdar de outros modos
- Configurações específicas sobrescrevem herdadas
- Permite reutilização de configurações base

## 🔗 Módulos Relacionados
- `admin-ia`: Alvos de IA
- `admin-prompts-ia`: Prompts de instrução

## 💡 Boas Práticas

### Configuração
- Use temperatura baixa para conteúdo factual
- Use temperatura alta para conteúdo criativo
- Defina max_tokens adequado ao uso

### Organização
- Crie modos para casos de uso específicos
- Documente propósito de cada modo
- Teste antes de usar em produção
