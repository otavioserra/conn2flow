# 🧩 Componentes - Geração de Componente

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** componente, reutilizavel, modular, html

## 📋 Descrição
Prompt padrão para criação de componentes HTML reutilizáveis.

## 🎯 Objetivo
Gerar um componente HTML modular e reutilizável que pode ser incluído em qualquer página ou layout do sistema.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Tipo do Componente**: Tipo de componente a ser criado (ex: card, formulário, seção, navegação)

### Opcionais:
- **Estilo Visual**: moderno, minimalista, corporativo
- **Cores**: Paleta de cores do componente
- **Responsivo**: Se deve adaptar-se a diferentes telas

## 🏗️ Estrutura do Componente

### Componente Básico
```
┌─────────────────────────────────────┐
│                                     │
│          Conteúdo HTML              │
│                                     │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Componente encapsulado e independente
- HTML semântico e acessível
- CSS com escopo (usar classes específicas)
- Se necessitar de recursos no `<head>`, usar bloco ```html-extra-head ```
- Foco em reutilização e modularidade
