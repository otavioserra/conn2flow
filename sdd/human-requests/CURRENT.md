# Current Human Request

- **Intake ativo**: [req-034.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-034.md)

- **Status**: BATCH-034 (Aprimoramento do Editor HTML Visual) **implementado e validado estaticamente** (php -l / node --check / JSON OK). Lógica da janela pai isolada no novo `html-editor-visual-controls.js`; editor do iframe (`html-editor.js`) reescrito com overlays, toolbar, DnD, undo/redo, breadcrumb, Tailwind styler e wrappers de widget. Ver DEC-047 (design) e DEC-048 (execução).

- **Pendências**: Deploy (`🗃️ Projects - Update => Core`, que compila o `VariaveisData.json` a partir do `variables.json`) e validação runtime no navegador pelo operador — checklist em `sdd/implementation/batch-034-visual-editor.md` e `VALIDATION-CHECKLIST.md#batch-034`.
