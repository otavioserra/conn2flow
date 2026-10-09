# BL-026 — Listagem do `interface` em Tailwind

- **Tipo**: Feature / Interface
- **Status**: IN-DISCUSSION
- **Prioridade sugerida**: MÉDIA (prevista para a versão 3.0)
- **Origem**: Humano, 2026-09-28, no e-commerce do conn2flow-site: "pode manter a listagem só ainda em Fomantic-UI… depois as listagens a gente vem e muda tudo". Pode virar requisição executada pelo mesmo agente.
- **Relacionado**: fatia concreta do BL-012 (substituição integral do Fomantic no painel, Linha 3.0), que hoje só existe como linha do `BACKLOG-INDEX.md`, sem arquivo.
- **Componentes**: `gestor/bibliotecas/interface.php` (`interface_listar_*`, `interface_listar_ajax`), `gestor/assets/interface/interface.js` (DataTables + Fomantic), componentes `interface-listar*`.

## Contexto

1. Desde a [req-190](../human-requests/archive/req-190.md), incluir, editar e os botões do `interface` têm variante Tailwind. A listagem não: ela depende do DataTables com o tema do Fomantic e do `interface.js` legado (tooltips, dropdowns, modais do Fomantic).
2. Um módulo com as telas de formulário em Tailwind precisa manter a página da listagem em `layout-administrativo-do-gestor`; o painel fica com dois visuais.

## Proposta (rascunho)

1. Componente `interface-listar-tailwind` e tabela sem DataTables: paginação, busca e ordenação no servidor, reaproveitando `interface_listar_ajax()` (já seguro desde a req-189: colunas e ordenação só da sessão).
2. Ações por linha (editar, ativar/desativar, excluir) com os mesmos ícones Lucide e o modal de deleção do `interface-tailwind.js`.
3. Seleção de variante por `interface_componente_variante()`, como os formulários.

## Critérios de aceite (rascunho)

- Listagem de um módulo Tailwind sem nenhum asset do Fomantic na página.
- Busca, ordenação, paginação, status e exclusão equivalentes aos de hoje.
- Módulos Fomantic sem mudança.
