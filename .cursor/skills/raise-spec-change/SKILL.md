---
name: raise-spec-change
description: "LEIA ANTES de alterar qualquer regra normativa, requisito funcional ou contrato em sdd/SPEC.md. Se não ler: mudanças não autorizadas violam a governança SDD e geram retrabalho estrutural."
user-invocable: true
---

# Mudança normativa

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Detectar que a implementação exige mudança de contrato, novo modelo de dados, alteração de requisito ou quebra de premissa em `sdd/SPEC.md`.
- **SKIP APENAS SE**: A mudança for apenas de detalhe técnico de implementação dentro dos limites da especificação vigente.
- **CONSEQUÊNCIA DE IGNORAR**: Violação do fluxo normativo SDD, edição ilegal de arquivos restritos da Chefia e desvio do escopo do projeto.

---

Trate `$ARGUMENTS` como um pedido de mudança normativa.

## Procedimento

1. Carregue `sdd-workflow`.
2. Confirme o impacto em `sdd/`, decisions, implementation e validation.
3. Registre a mudança em `sdd/change-requests/` antes de consolidar no sdd numerado.
4. Só implemente depois que a mudança normativa ficar explícita.

## Pedido atual

$ARGUMENTS
