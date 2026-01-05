# 📝 Guia de Criação de Prompts por Tipo - Conn2Flow

## 🎯 Visão Geral

Este documento serve como **receita principal** para a criação sistemática de prompts prontos no ecossistema Conn2Flow. Ele orienta agentes de IA na construção de exemplos de prompts que usuários podem utilizar como base para suas necessidades específicas.

### 🎨 Propósito
- **Padronização**: Garantir consistência na criação de prompts
- **Eficiência**: Acelerar o desenvolvimento de novos prompts
- **Qualidade**: Manter alto padrão de usabilidade e completude
- **Escalabilidade**: Facilitar expansão para novos tipos de prompts

---

## 🏗️ Estrutura Geral dos Prompts

### 📋 Componentes Essenciais

Cada prompt deve conter obrigatoriamente:

#### 1. **Cabeçalho Identificador**
```markdown
# 🎯 [Tipo do Prompt] - [Nome Descritivo]

**Versão:** 1.0.0
**Data:** YYYY-MM-DD
**Autor:** [Nome/Autor]
**Tags:** [tag1, tag2, tag3]
```

#### 2. **Descrição Executiva**
- **Objetivo**: O que o prompt faz (1-2 frases)
- **Contexto**: Quando e por que usar
- **Resultado Esperado**: O que será gerado

#### 3. **Parâmetros de Entrada**
- **Obrigatórios**: Campos essenciais
- **Opcionais**: Campos complementares
- **Validações**: Regras de negócio

#### 4. **Estrutura do Prompt**
- **Instruções**: Passos claros e sequenciais
- **Exemplos**: Casos práticos
- **Templates**: Estruturas reutilizáveis

#### 5. **Metadados Técnicos**
- **Dependências**: Recursos necessários
- **Limitações**: Restrições conhecidas
- **Testes**: Cenários de validação

---

## 📂 Organização por Categorias

### 🎨 **Interface e UX**
- Layouts responsivos
- Componentes interativos
- Temas e estilos
- Navegação e menus

### 📄 **Conteúdo e Páginas**
- Páginas estáticas
- Formulários dinâmicos
- Landing pages
- Dashboards administrativos

### 🔧 **Funcionalidades**
- Módulos de negócio
- APIs e integrações
- Processos automatizados
- Validações e regras

### 📦 **Plugins e Extensões**
- Plugins personalizados
- Integrações externas
- Funcionalidades avançadas
- Customizações específicas

### 🤖 **Automação e IA**
- Geração de conteúdo
- Processos inteligentes
- Análise de dados
- Recomendações automáticas

---

## 🔄 Fluxo de Criação

### 📝 **Fase 1: Planejamento**
1. **Identificar Necessidade**: Analisar demanda do usuário
2. **Categorizar Tipo**: Classificar na estrutura acima
3. **Definir Escopo**: Delimitar funcionalidades

### ✍️ **Fase 2: Desenvolvimento**
1. **Estruturar Base**: Seguir template padrão
2. **Adicionar Exemplos**: Incluir casos reais
3. **Documentar Parâmetros**: Detalhar entradas/saídas

### ✅ **Fase 3: Validação**
1. **Testar Funcionamento**: Executar prompt completo
2. **Verificar Consistência**: Validar com padrões
3. **Documentar Limitações**: Registrar restrições

### 🚀 **Fase 4: Publicação**
1. **Versionar Arquivo**: Aplicar controle de versão
2. **Indexar no Sistema**: Adicionar aos repositórios
3. **Comunicar Disponibilidade**: Notificar usuários

---

## �️ Estrutura de Arquivos no Conn2Flow

### 📁 **Localização dos Prompts**
Cada módulo pode criar prompts organizados na estrutura:
```
gestor/modulos/{modulo}/resources/{lang}/ai_prompts/
```

**Exemplo prático:**
```
gestor/modulos/admin-paginas/resources/pt-br/ai_prompts/
```

### 📂 **Organização Hierárquica**
1. **Pasta do Tipo**: Identificador do recurso
   ```
   ai_prompts/paginas/
   ```

2. **Arquivo do Prompt**: Nome do arquivo = nome da pasta
   ```
   ai_prompts/paginas/paginas.md
   ```

3. **Metadados**: Registro no JSON principal do módulo
   ```
   resources.{lang}.ai_prompts[]
   ```

### 📋 **Estrutura do Metadado JSON**
```json
{
    "id": "paginas",
    "name": "Páginas",
    "target": "paginas",
    "default": true,
    "version": "1.2",
    "checksum": {
        "md": "f46d272c361f52f77f7033eaf3780cd7"
    }
}
```

### 🌍 **Suporte Multilíngue**
- **Estrutura por idioma**: `resources/{lang}/ai_prompts/`
- **Metadados separados**: `resources.{lang}.ai_prompts[]`
- **Idiomas suportados**: pt-br, en, etc.

**Exemplo completo:**
```
gestor/modulos/admin-paginas/
├── admin-paginas.json (metadados)
└── resources/
    ├── pt-br/
    │   └── ai_prompts/
    │       └── paginas/
    │           └── paginas.md
    └── en/
        └── ai_prompts/
            └── paginas/
                └── paginas.md
```

---

## ⚠️ Convenções de Metadados

### 🔧 **Tratamento do Campo "default"**
**IMPORTANTE**: O sistema de atualização do banco de dados (`atualizacoes-banco-de-dados.php`) trata automaticamente o campo "default" para a tabela `prompts_ia`:

#### ✅ **Quando é padrão (default: true)**
```json
{
    "id": "paginas",
    "name": "Páginas",
    "target": "paginas",
    "default": true,
    "version": "1.2",
    "checksum": {
        "md": "f46d272c361f52f77f7033eaf3780cd7"
    }
}
```

#### ✅ **Quando NÃO é padrão (default: false ou omitido)**
**Ambas as formas são válidas e equivalentes:**
```json
// Opção 1: Campo explícito
{
    "id": "pagina-simples-uma-sessao",
    "name": "Página Simples - Uma Sessão",
    "target": "paginas",
    "default": false,
    "version": "1.0",
    "checksum": {
        "md": "a1b2c3d4e5f67890123456789012345"
    }
}

// Opção 2: Campo omitido (recomendado)
{
    "id": "pagina-simples-uma-sessao",
    "name": "Página Simples - Uma Sessão",
    "target": "paginas",
    "version": "1.0",
    "checksum": {
        "md": "a1b2c3d4e5f67890123456789012345"
    }
}
```

#### 🤖 **Processamento Automático**
- **Sistema**: Converte automaticamente `true` → `1` e `false` → `0` para compatibilidade com MySQL
- **Ausência**: Quando o campo `default` não está presente, o sistema define automaticamente como `false` (0)
- **Upsert**: Permite tanto atualização de registros existentes quanto inserção de novos

#### 🚨 **Motivo da Implementação**
- **Compatibilidade**: Evita erros `SQLSTATE[HY000]: General error: 1366 Incorrect integer value`
- **Flexibilidade**: Permite omissão do campo para reduzir verbosidade JSON
- **Confiabilidade**: Garante que todos os registros tenham valor válido para a coluna `padrao`

---

## ��📊 Métricas de Qualidade

### ✅ **Critérios de Aceitação**
- [ ] Prompt executa sem erros
- [ ] Resultado atende expectativa
- [ ] Documentação completa
- [ ] Exemplos funcionais
- [ ] Parâmetros validados

### 📈 **Indicadores de Performance**
- **Taxa de Sucesso**: % de execuções bem-sucedidas
- **Tempo Médio**: Duração típica de execução
- **Satisfação**: Feedback dos usuários
- **Reutilização**: Frequência de uso

---

## 🔧 Manutenção e Evolução

### 📅 **Revisões Periódicas**
- **Mensal**: Verificar métricas e feedback
- **Trimestral**: Atualizar com novas funcionalidades
- **Semestral**: Revisar estrutura geral

### 🔄 **Processo de Atualização**
1. **Coletar Feedback**: Análise de uso real
2. **Identificar Melhorias**: Pontos de otimização
3. **Implementar Mudanças**: Atualizar versão
4. **Testar Alterações**: Validar compatibilidade

---

## 📚 Referências Técnicas

### 🔗 **Recursos Relacionados**
- [Documentação do Conn2Flow](./CONN2FLOW-GESTOR-DETALHAMENTO.md)
- [Estrutura de Plugins](./CONN2FLOW-PLUGIN-INSTALADOR-FLUXO.md)
- [Sistema de Templates](./CONN2FLOW-LAYOUTS-PAGINAS-COMPONENTES.md)

### 🏷️ **Convenções de Nomenclatura**
- **Arquivos**: `tipo-funcionalidade-versao.md`
- **Tags**: `categoria, subtipo, funcionalidade`
- **Versões**: `MAJOR.MINOR.PATCH`

---

## 🎯 Próximos Passos

Este documento será expandido com:
- **Templates específicos** por categoria
- **Exemplos práticos** de implementação
- **Casos de uso** reais do Conn2Flow
- **Diretrizes avançadas** de otimização

*Documento vivo - Atualizado conforme evolução do sistema*
