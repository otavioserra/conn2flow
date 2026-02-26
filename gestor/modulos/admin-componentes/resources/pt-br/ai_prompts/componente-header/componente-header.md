# 🔝 Componente Header - Cabeçalho com Navegação

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** componente, header, cabecalho, navegacao, menu

## 📋 Descrição
Cria um componente de cabeçalho (header) com logotipo e menu de navegação responsivo.

## 🎯 Objetivo
Gerar um componente de header completo com logo, menu de navegação e versão mobile com hamburger menu.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Nome/Logo**: Nome da marca ou texto do logo

### Opcionais:
- **Itens do Menu**: Lista de links de navegação
- **Estilo Visual**: transparente, sólido, com sombra, fixo no topo
- **Cores**: Cores do fundo e texto
- **CTA**: Botão de destaque no header (ex: "Contato", "Começar")

## 🏗️ Estrutura do Componente

### Header Desktop
```
┌─────────────────────────────────────┐
│ Logo    Menu1 Menu2 Menu3   [CTA]   │
└─────────────────────────────────────┘
```

### Header Mobile
```
┌─────────────────────────────────────┐
│ Logo                          ☰     │
├─────────────────────────────────────┤
│ Menu1                               │
│ Menu2                               │
│ Menu3                               │
│ [CTA]                               │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Navegação responsiva com menu hamburger para mobile
- Logo à esquerda, navegação à direita
- Suporte a menu fixo (sticky) opcional
- CSS com classes específicas para evitar conflitos
- Se usar JavaScript para toggle do menu, incluir no bloco ```html-extra-head ``` ou inline
