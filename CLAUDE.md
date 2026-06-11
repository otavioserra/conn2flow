# Projeto Spec-Driven Development

- Trate `sdd/README.md` e os sdd numerados como fonte normativa.
- Antes de editar código ou sdd, leia `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch atual, `sdd/validation/VALIDATION-CHECKLIST.md` e `sdd/decisions/DECISION-LOG.md`.
- Use `sdd/human-requests/` apenas como intake humano não normativo. Se a demanda vier como caminho de arquivo Markdown ou como a própria pasta, leia esse material primeiro e depois classifique a demanda no artefato SDD correto.
- **Memórias de Engenharia**: No início de cada sessão, leia obrigatoriamente `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` e `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` para alinhar contexto antes de qualquer alteração.
- **Manutenção da Memória de Execução**: Ao término de cada tarefa, atualize `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` com novos aprendizados, bugs resolvidos e particularidades do ambiente. Nunca modifique `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` sem instrução explícita do usuário humano.
- Classifique a demanda cedo: change request, implementação de batch, review ou validação.
- Não reescreva os sdd numerados para comentários pequenos de review.
- Edite sdd numerados apenas quando requisito, contrato, critério de aceite ou decisão aprovada realmente mudar.
- Mantenha o trabalho em batches pequenos com alvo de validação explícito.

## Skills principais

- Use `/start-sdd-slice` para nova demanda ou entrada em `sdd/human-requests/`.
- Use `/continue-sdd-batch` para retomar um batch em andamento.
- Use `/review-current-batch` para review findings-first do batch atual.
- Use `/raise-spec-change` para rodada de mudança normativa.

## Skills automáticas

- `sdd-workflow`: decidir o artefato certo e manter o batch alinhado ao fluxo.
- `project-validation`: escolher a menor validação executável para o slice atual.

## Otimização de Contexto e Arquivamento

- Mantenha `sdd/decisions/DECISION-LOG.md`, `sdd/implementation/BATCH-INDEX.md` e `sdd/validation/VALIDATION-CHECKLIST.md` com no máximo 10 itens correntes ou ativos.
- Mantenha também `sdd/human-requests/` enxuto, preservando no máximo 10 requisições correntes ou recentes fora de `archive/`.
- Mova históricos antigos para a subpasta `archive/` correspondente: `sdd/decisions/archive/`, `sdd/human-requests/archive/`, `sdd/implementation/archive/` ou `sdd/validation/archive/`.
- Nos arquivos principais, substitua o histórico arquivado por tabelas Markdown resumidas com 1 linha por item e link direto para o arquivo em `archive/`.
- Ao carregar contexto inicial, priorize os arquivos principais e abra itens em `archive/` apenas quando o batch, a requisição ativa ou um link de rastreabilidade exigir.
