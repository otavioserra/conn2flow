# 🎯 Página com Colunas - Layout em Grade

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** pagina, colunas, grid, cards, destaque

## 📋 Descrição
Cria uma página com uma única sessão contendo 3 colunas, onde cada coluna apresenta título, descrição e imagem de destaque.

## 🎯 Objetivo
Gerar uma página organizada em formato de grade que apresenta múltiplas informações ou serviços de forma visualmente atrativa e comparável.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Título da Sessão**: Título principal da página (máx. 60 caracteres)
- **Coluna 1**: Título, descrição e imagem
- **Coluna 2**: Título, descrição e imagem
- **Coluna 3**: Título, descrição e imagem

### Opcionais:
- **Altura das Colunas**: auto, equal, custom
- **Espaçamento**: small, medium, large
- **Alinhamento**: top, center, bottom
- **Hover Effects**: Efeitos visuais ao passar mouse

## 🏗️ Estrutura da Página

### Sessão Principal com 3 Colunas
```
┌─────────────────────────────────────┐
│        [TÍTULO DA SESSÃO]          │
└─────────────────────────────────────┘
┌─────────────┬─────────────┬─────────────┐
│   COLUNA 1  │   COLUNA 2  │   COLUNA 3  │
│             │             │             │
│ [IMAGEM]    │ [IMAGEM]    │ [IMAGEM]    │
│             │             │             │
│ [TÍTULO]    │ [TÍTULO]    │ [TÍTULO]    │
│             │             │             │
│ [DESCRIÇÃO] │ [DESCRIÇÃO] │ [DESCRIÇÃO] │
│             │             │             │
│ [BOTÃO]     │ [BOTÃO]     │ [BOTÃO]     │
└─────────────┴─────────────┴─────────────┘
```

## 📋 Instruções de Criação

1. **Layout Responsivo**: Colunas se adaptam em mobile (1 coluna) e desktop (3 colunas)
2. **Consistência Visual**: Mesmas proporções e estilos para todas as colunas
3. **Hierarquia de Informação**: Títulos > Imagens > Descrições > Ações
4. **Acessibilidade**: Estrutura semântica e navegação por teclado

## 🎨 Exemplo Prático

**Cenário: Página de Serviços**
- **Coluna 1**: Desenvolvimento Web - "Criamos sites modernos" + imagem de código
- **Coluna 2**: Design UX/UI - "Interfaces intuitivas" + imagem de mockup
- **Coluna 3**: Consultoria - "Otimização de processos" + imagem de reunião

**Resultado Esperado:**
Três cards lado a lado apresentando serviços de forma organizada e visual.

## ⚙️ Metadados Técnicos

- **Framework CSS**: Grid system do Fomantic-UI ou TailwindCSS
- **Dependências**: Sistema de imagens e botões do Conn2Flow
- **Limitações**: Fixo em 3 colunas por sessão
- **Compatibilidade**: Layout responsivo para todos os dispositivos

---

*Prompt perfeito para apresentação de serviços, produtos ou recursos*