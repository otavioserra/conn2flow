# 📊 Layout Dashboard - Painel Administrativo

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** layout, dashboard, admin, painel, gestao

## 📋 Descrição
Cria um layout para painel administrativo (dashboard) com barra lateral de navegação e área de conteúdo principal.

## 🎯 Objetivo
Gerar um layout HTML completo para um painel administrativo com sidebar fixa, barra superior e área de conteúdo dinâmica.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Nome do Sistema**: Nome do sistema ou painel administrativo

### Opcionais:
- **Estilo Visual**: moderno, corporativo, minimalista, escuro
- **Cores**: Paleta de cores (primária e secundária)
- **Sidebar**: Fixa ou retrátil
- **Itens do Menu**: Lista de itens de navegação principal

## 🏗️ Estrutura do Layout

### Barra Superior
```
┌─────────────────────────────────────┐
│ ☰ Nome do Sistema    🔔 👤 Admin   │
└─────────────────────────────────────┘
```

### Sidebar + Conteúdo
```
┌──────┬──────────────────────────────┐
│      │                              │
│ Menu │    @[[pagina#corpo]]@        │
│      │                              │
│ Item1│                              │
│ Item2│                              │
│ Item3│                              │
│      │                              │
└──────┴──────────────────────────────┘
```

## 🎨 Estilo Esperado
- Sidebar fixa à esquerda com navegação principal
- Barra superior com informações do usuário e notificações
- Área de conteúdo flexível para receber o corpo da página
- Design responsivo com sidebar retrátil em mobile
- Variáveis do sistema: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
