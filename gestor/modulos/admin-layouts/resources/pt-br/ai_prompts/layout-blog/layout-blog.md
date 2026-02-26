# 🎯 Layout Blog - Conteúdo com Sidebar

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** layout, blog, sidebar, artigos, conteudo

## 📋 Descrição
Cria um layout para blog com header, área de conteúdo principal, sidebar lateral e footer.

## 🎯 Objetivo
Gerar um layout HTML completo otimizado para blogs e portais de conteúdo, com sidebar para widgets, categorias e informações complementares.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Nome do Blog**: Nome que aparecerá no header
- **Itens do Menu**: Lista de itens de navegação

### Opcionais:
- **Posição da Sidebar**: left, right (padrão: right)
- **Widgets da Sidebar**: busca, categorias, posts recentes, tags
- **Largura da Sidebar**: narrow, medium, wide

## 🏗️ Estrutura do Layout

### Header
```
┌─────────────────────────────────────┐
│ Logo Blog     Menu de Navegação     │
└─────────────────────────────────────┘
```

### Área Principal + Sidebar
```
┌──────────────────────┬──────────────┐
│                      │   Sidebar    │
│  @[[pagina#corpo]]@  │  - Busca     │
│                      │  - Categorias│
│                      │  - Recentes  │
└──────────────────────┴──────────────┘
```

### Footer
```
┌─────────────────────────────────────┐
│ Sobre | Contato | Redes Sociais     │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Design otimizado para leitura
- Sidebar com widgets funcionais
- Tipografia clara e legível
- Layout responsivo (sidebar colapsa em mobile)
- Variáveis do sistema: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
