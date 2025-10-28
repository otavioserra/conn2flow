# 🎯 Página de Serviços - Cards Interativos

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** pagina, servicos, cards, interativo, portfolio

## 📋 Descrição
Cria uma página de serviços com cards interativos apresentando diferentes soluções ou produtos oferecidos.

## 🎯 Objetivo
Gerar uma página dinâmica que apresenta serviços de forma atrativa, com cards que podem ter hover effects, animações e chamadas para ação.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Título da Página**: Título principal da seção de serviços
- **Serviços**: Lista de 3-6 serviços com título, descrição e ícone/imagem

### Opcionais:
- **Layout**: grid, masonry, carousel
- **Animações**: fade, slide, scale, rotate
- **Filtros**: Categorias para filtrar serviços
- **Preços**: Valores ou "Consultar" para cada serviço

## 🏗️ Estrutura da Página

### Header + Grid de Serviços
```
┌─────────────────────────────────────┐
│      [TÍTULO DOS SERVIÇOS]         │
│                                     │
│   [DESCRIÇÃO INTRODUTÓRIA]         │
└─────────────────────────────────────┘

┌─────────────┬─────────────┬─────────────┐
│   SERVIÇO   │   SERVIÇO   │   SERVIÇO   │
│      1      │      2      │      3      │
│             │             │             │
│ [ÍCONE]     │ [ÍCONE]     │ [ÍCONE]     │
│             │             │             │
│ [TÍTULO]    │ [TÍTULO]    │ [TÍTULO]    │
│             │             │             │
│ [DESCRIÇÃO] │ [DESCRIÇÃO] │ [DESCRIÇÃO] │
│             │             │             │
│ [PREÇO]     │ [PREÇO]     │ [PREÇO]     │
│             │             │             │
│ [BOTÃO]     │ [BOTÃO]     │ [BOTÃO]     │
└─────────────┴─────────────┴─────────────┘
```

## 📋 Instruções de Criação

1. **Cards Consistentes**: Mesmo tamanho e estilo para todos
2. **Interatividade**: Hover effects e transições suaves
3. **Responsividade**: Grid adaptável (1-2-3 colunas)
4. **Performance**: Lazy loading para imagens e animações

## 🎨 Exemplo Prático

**Serviços de Desenvolvimento:**
1. **Web Design** - "Interfaces modernas e responsivas"
2. **Desenvolvimento** - "Aplicações robustas e escaláveis"
3. **Consultoria** - "Otimização e estratégia digital"
4. **Manutenção** - "Suporte contínuo e atualizações"

**Resultado Esperado:**
Página atrativa com cards animados que destacam cada serviço oferecido.

## ⚙️ Metadados Técnicos

- **Framework CSS**: Card components e grid system
- **Dependências**: Sistema de animações do Conn2Flow
- **Limitações**: Máximo 12 serviços por página
- **Compatibilidade**: CSS animations e transitions

---

*Prompt ideal para portfólios e apresentação de catálogo de serviços*