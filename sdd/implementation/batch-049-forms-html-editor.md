# BATCH-049 - Refatoração Dinâmica do Módulo Forms com html-editor

## Origem

- Intake humano: `sdd/human-requests/req-049.md`
- Classificação: implementação incremental aprovada para execução

## Escopo

- Adicionar suporte de template HTML/CSS ao módulo `forms`.
- Integrar `forms` ao `html_editor_componente` e ao popup de widgets do editor visual.
- Substituir a edição manual de JSON por controles visuais para metadados e CRUD de campos.
- Criar renderizador público `forms.widget.php` e controlador `forms.widget.js`.
- Registrar alvo/modo de IA para `forms`.

## Validação Planejada

- Lint PHP nos arquivos alterados/criados.
- `node --check` nos JS alterados/criados.
- Validação JSON dos manifests/data files tocados.
- `composer test` quando o ambiente local permitir.

## Status de Execução

- Implementado em 2026-06-22.
- Validação estática e suítes automatizadas registradas em `sdd/validation/VALIDATION-CHECKLIST.md#batch-049`.
- Pendências: `Update => Core`, migração Phinx no ambiente alvo e validação runtime no navegador.
