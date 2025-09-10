# Conn2Flow - Layouts, Páginas e Componentes

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Estrutura de Dados](#estrutura-de-dados)
- [Layouts](#layouts)
- [Páginas](#páginas)
- [Componentes](#componentes)
- [Exportação e Versionamento](#exportação-e-versionamento)
- [Boas Práticas](#boas-práticas)
- [Exemplos Práticos](#exemplos-práticos)
- [Histórico de Decisões](#histórico-de-decisões)

---

## 🎯 Visão Geral

O sistema Conn2Flow utiliza um modelo centralizado de layouts, páginas e componentes para garantir flexibilidade, reutilização e padronização da interface. Todo o conteúdo visual é armazenado no banco de dados, mas pode ser exportado para arquivos versionáveis para facilitar manutenção e deploy.

---

## 🏗️ Estrutura de Dados

- **Layouts**: Estruturas de página (header, footer, slots dinâmicos)
- **Páginas**: Conteúdo específico, vinculado a um layout
- **Componentes**: Blocos reutilizáveis (alertas, formulários, etc)

Tabelas principais: `layouts`, `paginas`, `componentes`.

---

## 🖼️ Layouts
- Definem a estrutura base de páginas.
- Possuem variáveis dinâmicas, principalmente `@[[pagina#corpo]]@`.
- Exemplo: Layout administrativo (ID 1), layout externo (ID 23).

---

## 📄 Páginas
- Conteúdo específico exibido ao usuário.
- Sempre associada a um layout.
- Possui campo `caminho` para roteamento.

---

## 🧩 Componentes
- Blocos de interface reutilizáveis.
- Incluídos em layouts ou páginas.
- Exemplo: modais, botões, alertas.

---

## 🚀 Exportação e Versionamento

- Exportação automatizada dos recursos para estrutura de arquivos:
  - `gestor/resources/layouts/{id}/`
  - `gestor/resources/paginas/{id}/`
  - `gestor/resources/componentes/{id}/`
- Layouts e componentes sempre globais.
- Páginas exportadas para módulos reais ou global.
- Versionamento via Git.

---

## ✅ Boas Práticas
- Sempre usar variáveis dinâmicas nos layouts.
- Manter componentes genéricos e reutilizáveis.
- Validar existência de módulos antes de exportar páginas.
- Documentar decisões e padrões adotados.

---

## 💡 Exemplos Práticos
- Layout administrativo: sidebar, topo, slot de conteúdo.
- Página dashboard: vinculada ao layout 1, caminho `/dashboard`.
- Componente alerta: incluído em várias páginas.

---

## 📜 Histórico de Decisões
- Exportação automatizada implementada em agosto/2025.
- Separação clara entre recursos globais e de módulos.
- Estrutura de arquivos espelhada do gestor para o gestor-cliente.
- Versionamento obrigatório para todos os recursos visuais.
