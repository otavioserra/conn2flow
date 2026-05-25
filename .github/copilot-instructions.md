# Spec-Driven Project Guidelines

- Este repositÃ³rio deve tratar `sdd/README.md` e os sdd numerados como fonte normativa.
- Antes de editar cÃ³digo ou sdd, leia `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch atual, `sdd/validation/VALIDATION-CHECKLIST.md` e `sdd/decisions/DECISION-LOG.md`.
- Use `sdd/human-requests/` apenas como intake humano nÃ£o normativo. Se a demanda vier como caminho de arquivo Markdown ou como a prÃ³pria pasta, leia esse material primeiro e depois classifique a demanda no artefato SDD correto.
- Classifique a demanda cedo: change request, implementaÃ§Ã£o de batch, review ou validaÃ§Ã£o.
- NÃ£o reescreva os sdd numerados para comentÃ¡rios pequenos de review.
- Edite sdd numerados apenas quando requisito, contrato, critÃ©rio de aceite ou decisÃ£o aprovada realmente mudar.
- Mantenha o trabalho em batches pequenos com alvo de validaÃ§Ã£o explÃ­cito.
- Para decidir o artefato correto dentro do fluxo SDD, use a skill [sdd-workflow](./skills/sdd-workflow/SKILL.md).
- Para validaÃ§Ã£o local do projeto, ajuste e use a skill [project-validation](./skills/project-validation/SKILL.md).
- O hook [sdd-session-start.json](./hooks/sdd-session-start.json) injeta um lembrete curto de SDD no inÃ­cio da sessÃ£o; mantenha esse hook pequeno e previsÃ­vel.
