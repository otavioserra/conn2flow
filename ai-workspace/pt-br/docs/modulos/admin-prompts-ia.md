# Módulo: admin-prompts-ia

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-prompts-ia` |
| **Nome** | Administração de IA - Prompts |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-prompts-ia** gerencia os **prompts de instrução para IA** no Conn2Flow. Prompts são as instruções textuais que guiam a IA na geração de conteúdo. Um prompt bem elaborado é essencial para obter resultados de qualidade.

## 🏗️ Funcionalidades Principais

### 📝 **Gerenciamento de Prompts**
- **Criar prompts**: Escrever novas instruções de IA
- **Editar prompts**: Refinar instruções existentes
- **Versionamento**: Rastrear mudanças nos prompts
- **Templates**: Prompts reutilizáveis

### 🎯 **Tipos de Prompt**
- **Sistema**: Instruções de comportamento base
- **Usuário**: Instruções específicas de tarefa
- **Contexto**: Informações de background
- **Exemplo**: Few-shot learning com exemplos

### 🔗 **Variáveis em Prompts**
- **Dinâmicas**: Substituição em tempo de execução
- **Contextuais**: Dados do sistema
- **Personalizadas**: Definidas pelo usuário

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `prompts_ia`
```sql
CREATE TABLE prompts_ia (
    id_prompts_ia INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo VARCHAR(50),                    -- sistema, usuario, contexto, exemplo
    conteudo TEXT NOT NULL,              -- Texto do prompt
    variaveis JSON,                      -- Variáveis utilizadas
    id_modo_ia VARCHAR(255),             -- Modo de IA associado
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-prompts-ia/
├── admin-prompts-ia.php         # Controlador principal
├── admin-prompts-ia.js          # Funcionalidade client-side
├── admin-prompts-ia.json        # Configuração do módulo
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-prompts-ia/
    │       ├── admin-prompts-ia-adicionar/
    │       └── admin-prompts-ia-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Exemplos de Prompts

### Prompt de Sistema
```markdown
Você é um redator de conteúdo especializado em {{nicho}}.
Seu objetivo é criar conteúdo envolvente e informativo.

Diretrizes:
- Use tom {{tom_voz}}
- Escreva em {{idioma}}
- Mantenha parágrafos curtos (3-4 frases)
- Use subtítulos para organização

Evite:
- Jargão excessivo
- Afirmações sem fundamentação
- Conteúdo promocional direto
```

### Prompt de Tarefa
```markdown
Crie um artigo sobre "{{titulo}}" seguindo esta estrutura:

1. Introdução (2 parágrafos)
   - Apresente o tema
   - Indique o que será abordado

2. Desenvolvimento (3-4 seções)
   - Use subtítulos descritivos
   - Inclua exemplos práticos

3. Conclusão (1 parágrafo)
   - Resuma pontos principais
   - Call-to-action

Palavras-chave: {{palavras_chave}}
Público-alvo: {{publico}}
```

### Prompt com Exemplos (Few-shot)
```markdown
Gere descrições de produtos no estilo abaixo:

Exemplo 1:
Produto: Cadeira Ergonômica
Descrição: "Trabalhe com conforto durante horas. 
Nossa cadeira ergonômica se adapta ao seu corpo, 
oferecendo suporte lombar ajustável e braços 
reguláveis para a postura perfeita."

Exemplo 2:
Produto: Mouse Sem Fio
Descrição: "Liberdade sem cabos, precisão sem limites. 
Design ambidestro com sensor de alta precisão 
e bateria que dura meses."

Agora, crie uma descrição para:
Produto: {{produto}}
```

## 🎨 Interface do Usuário

### Lista de Prompts
- Tabela com prompts cadastrados
- Tipo e modo associado
- Ações rápidas (testar, editar, duplicar)

### Editor de Prompt
- **Nome**: Identificador do prompt
- **Tipo**: Seleção do tipo
- **Modo de IA**: Associação a modo
- **Conteúdo**: Editor de texto completo
- **Variáveis**: Lista de variáveis utilizadas
- **Preview**: Visualização com substituição

## 🔄 Processamento de Variáveis

### Sintaxe de Variáveis
```
{{variavel}}           - Variável simples
{{variavel|default}}   - Com valor padrão
{{variavel:upper}}     - Com transformação
```

### Variáveis do Sistema
- `{{data_atual}}` - Data atual
- `{{hora_atual}}` - Hora atual
- `{{usuario_nome}}` - Nome do usuário
- `{{site_nome}}` - Nome do site
- `{{idioma}}` - Idioma atual

## 💡 Boas Práticas

### Escrita de Prompts
- Seja específico e claro
- Divida instruções complexas
- Forneça exemplos quando possível
- Defina formato esperado de saída

### Organização
- Nomeie descritivamente
- Agrupe por propósito
- Versione prompts importantes
- Documente variáveis usadas

### Testes
- Teste com diferentes entradas
- Valide saída gerada
- Ajuste iterativamente
- Mantenha histórico de versões

## 🔗 Módulos Relacionados
- `admin-ia`: Alvos de IA
- `admin-modos-ia`: Modos de geração

## ⚠️ Notas Importantes
- Prompts muito longos podem reduzir qualidade
- Balance instrução com liberdade criativa
- Considere limites de tokens do modelo
