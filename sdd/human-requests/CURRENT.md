# Current Human Request

- **Intake ativo**: [req-040.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-040.md)

- **Status**: BATCH-040 (Ajustes Finais no Pré-visualizador de Widgets e Elemento Fantasma do Cursor) **implementado e validado estaticamente** (`node --check` OK). (1) Widgets agora renderizam no pré-visualizador da página (`#iframe-visualizacao-pagina`) via `widgetPreviewBootstrap` + AJAX `html-editor-widget-render`; (2) o ghost do cursor mostra o elemento/widget REAL renderizado num contêiner flutuante limpo. Ver DEC-054. Antecessores concluídos: BATCH-034 a BATCH-039 (DEC-047 a DEC-053).

- **Pendências**: Deploy (`🗃️ Projects - Update => Core`) e validação runtime no navegador pelo operador — checklists em `sdd/implementation/batch-040-widgets-and-ghost-adjustments.md` e `VALIDATION-CHECKLIST.md#batch-040`.
