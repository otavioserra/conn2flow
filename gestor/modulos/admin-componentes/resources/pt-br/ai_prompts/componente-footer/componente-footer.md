# 🔻 Componente Footer - Rodapé Completo

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** componente, footer, rodape, links, contato

## 📋 Descrição
Cria um componente de rodapé (footer) completo com colunas de informações, links e dados de contato.

## 🎯 Objetivo
Gerar um componente de footer organizado em colunas com links úteis, informações de contato e redes sociais.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Nome da Empresa**: Nome ou marca da empresa

### Opcionais:
- **Colunas**: Número de colunas de conteúdo (2-4)
- **Links**: Lista de links organizados por categoria
- **Redes Sociais**: Links para redes sociais
- **Cores**: Cores de fundo e texto
- **Copyright**: Texto de copyright personalizado

## 🏗️ Estrutura do Componente

### Footer Desktop
```
┌─────────────────────────────────────┐
│ Sobre     Links      Links   Contato│
│                                     │
│ Descrição  Link 1    Link 1  Email  │
│ da empresa Link 2    Link 2  Tel    │
│            Link 3    Link 3  End    │
├─────────────────────────────────────┤
│ © 2025 Empresa | 🔗 🔗 🔗          │
└─────────────────────────────────────┘
```

### Footer Mobile
```
┌─────────────────────────────────────┐
│ Sobre                               │
│ Descrição da empresa                │
│                                     │
│ Links                               │
│ Link 1, Link 2, Link 3             │
│                                     │
│ Contato                             │
│ Email, Tel, End                     │
├─────────────────────────────────────┤
│ © 2025 Empresa | 🔗 🔗 🔗          │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Layout em colunas responsivas
- Seção inferior com copyright e redes sociais
- Ícones para redes sociais (SVG inline ou Font Awesome via html-extra-head)
- Cores geralmente escuras ou contrastantes
- Links com hover state definido
