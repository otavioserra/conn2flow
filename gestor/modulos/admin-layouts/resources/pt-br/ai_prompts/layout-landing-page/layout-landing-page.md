# 🎯 Layout Landing Page - Página de Conversão

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** layout, landing-page, conversao, marketing, vendas

## 📋 Descrição
Cria um layout otimizado para landing pages de alta conversão com foco em CTA (Call to Action).

## 🎯 Objetivo
Gerar um layout HTML completo focado em conversão, com navegação mínima, blocos de destaque e chamadas para ação proeminentes.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Nome/Marca**: Nome da empresa ou produto
- **CTA Principal**: Texto do botão principal de conversão

### Opcionais:
- **Estilo Visual**: moderno, minimalista, corporativo, ousado
- **Cores**: Paleta de cores (primária e secundária)
- **Navegação**: Com ou sem menu de navegação

## 🏗️ Estrutura do Layout

### Header Mínimo
```
┌─────────────────────────────────────┐
│ Logo              CTA Button        │
└─────────────────────────────────────┘
```

### Conteúdo Full-Width
```
┌─────────────────────────────────────┐
│                                     │
│         @[[pagina#corpo]]@          │
│                                     │
└─────────────────────────────────────┘
```

### Footer Mínimo
```
┌─────────────────────────────────────┐
│ © 2025 | Termos | Privacidade       │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Design focado em conversão
- Navegação mínima para evitar distrações
- Botões de CTA grandes e visíveis
- Layout full-width para impacto visual
- Variáveis do sistema: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
