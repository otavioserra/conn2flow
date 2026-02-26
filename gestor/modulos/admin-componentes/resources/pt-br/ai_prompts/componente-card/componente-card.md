# 🃏 Componente Card - Cartão de Conteúdo

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** componente, card, cartao, conteudo, produto

## 📋 Descrição
Cria um componente de card (cartão) para exibição de conteúdo como produtos, serviços ou artigos.

## 🎯 Objetivo
Gerar um componente de card modular com imagem, título, descrição e ação, reutilizável em diferentes contextos.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Tipo do Card**: produto, serviço, artigo, perfil, depoimento

### Opcionais:
- **Com Imagem**: Se inclui imagem de destaque
- **Estilo Visual**: elevado (sombra), plano (flat), com borda
- **Ação**: Botão ou link de ação (ex: "Ver mais", "Comprar")
- **Badge/Tag**: Etiqueta de destaque (ex: "Novo", "Promoção")

## 🏗️ Estrutura do Componente

### Card com Imagem
```
┌─────────────────────────────────────┐
│          [Imagem/Thumb]             │
│  Badge                              │
├─────────────────────────────────────┤
│  Título do Card                     │
│  Descrição breve do conteúdo        │
│  do card com texto resumido.        │
│                                     │
│  [Ação/Botão]                       │
└─────────────────────────────────────┘
```

### Card sem Imagem
```
┌─────────────────────────────────────┐
│  🎯 Ícone                          │
│  Título do Card                     │
│  Descrição breve do conteúdo        │
│  do card com texto resumido.        │
│                                     │
│  [Ação/Botão]                       │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Cantos arredondados
- Sombra sutil para efeito de elevação
- Hover com transição suave
- Imagem com aspect ratio consistente
- Botão de ação alinhado ao rodapé do card
- Responsivo (empilhável em grids)
