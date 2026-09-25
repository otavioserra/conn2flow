# BATCH-185: Documentação dos Módulos do Core (onda 3, em paralelo)

Execução da [req-180](../human-requests/req-180.md), pelo **segundo agente**. As regras de convivência estão na req-180, §4.

## Progresso

| Módulo | pt-br | en | Legado apagado | Commit | Achados do código |
|---|---|---|---|---|---|
| `publisher` | concluído | concluído | sim | `562ef169` | Legado inventava tabelas e aprovação; exclusividade de template só no seletor; renomear slug não propaga referências; schema sem validação estrutural completa e injetado em script sem JSON_HEX_TAG. |
| `publisher-index` | concluído | concluído | não havia | `562ef169` | Contagem manual inclui slugs inativos/ausentes; widget não verifica ACL/agendamento; cliente descarta busca durante request e não restaura página em erro; count vestigial. |
| `publisher-highlights` | concluído | concluído | não havia | `562ef169` | Ramo com item perde CSS compilado/head; replacement interpreta backreferences; arrobas residuais; LEFT JOIN inclui páginas sem publicação; sem ACL/agendamento no widget. |

## Checklist vivo

- [x] Ler req-180, req-179, contrato, piloto e governança aplicável.
- [x] Executar auditoria inicial: 32 módulos ausentes; piloto menus com score 0 nos dois idiomas.
- [ ] Migrar páginas/publicação (publisher, publisher-index e publisher-highlights concluídos; admin-paginas, publisher-pages e pages-index pendentes).
- [ ] Migrar formulários e galerias.
- [ ] Migrar usuários, perfis e permissões.
- [ ] Migrar demais módulos.
- [ ] Remover legados correspondentes e índices antigos autorizados.
- [ ] Auditar todos os módulos e registrar commits/push por grupos de 3–5.

Escopo exclusivo: docs de módulos e este arquivo. Sem pipeline ou deploy, conforme req-180 §4.

## Validação

- 2026-09-25: `php cli/c2f.php docs:audit --json` após redação de `publisher`: pt-br/en com score 0 e nenhuma issue. Fontes conferidas em `33ce53d9`. Isso valida metadados/fontes/links; não substitui revisão semântica nem encerra o lote.
- Auditoria final do conjunto: pendente.
- Primeiro grupo: `docs:audit --json` confirmou score 0 e nenhuma issue nas seis docs (três módulos, dois idiomas). `git diff --check` sem erros. Revisão estática de controladores, widgets, JS, metadados e migrations; sem execução runtime/deploy, conforme o escopo. Nenhum código alterado.
- Git: `562ef169` enviado a `origin/main`. O `pull --rebase` pré-commit recusou a árvore com alterações concorrentes; `fetch origin main` + `rev-list HEAD...origin/main` confirmou `0 0` antes do commit. Commit limitado aos 11 caminhos próprios com `--only`; alterações do outro agente preservadas.
