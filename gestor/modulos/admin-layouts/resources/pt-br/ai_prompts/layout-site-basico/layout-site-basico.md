# 🎯 Layout Site Básico - Estrutura Institucional

**Versão:** 1.0.0
**Data:** 2025-10-28
**Autor:** Sistema Conn2Flow
**Tags:** layout, site, institucional, basico, header, footer

## 📋 Descrição
Cria um layout completo para site institucional com header de navegação, área de conteúdo principal e footer.

## 🎯 Objetivo
Gerar um layout HTML completo com estrutura profissional para sites institucionais, incluindo navegação responsiva, área de conteúdo dinâmico e rodapé informativo.

## 📝 Parâmetros de Entrada

### Obrigatórios:
- **Nome do Site**: Nome que aparecerá no header (máx. 40 caracteres)
- **Itens do Menu**: Lista de itens de navegação (3 a 6 itens)

### Opcionais:
- **Logo**: URL ou texto do logotipo
- **Cor Primária**: Cor principal do site
- **Rodapé**: Informações adicionais para o footer
- **Redes Sociais**: Links para perfis

## 🏗️ Estrutura do Layout

### Header (Navegação)
```
┌─────────────────────────────────────┐
│ Logo    Menu Item 1 | Item 2 | ...  │
└─────────────────────────────────────┘
```

### Conteúdo Principal
```
┌─────────────────────────────────────┐
│                                     │
│        @[[pagina#corpo]]@           │
│                                     │
└─────────────────────────────────────┘
```

### Footer
```
┌─────────────────────────────────────┐
│ © 2025 Nome do Site. Direitos...    │
└─────────────────────────────────────┘
```

## 🎨 Estilo Esperado
- Design limpo e profissional
- Navegação responsiva com menu hamburger em mobile
- Footer com informações de contato e copyright
- Variáveis do sistema: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
