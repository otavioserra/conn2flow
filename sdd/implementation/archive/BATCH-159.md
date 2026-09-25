# BATCH-159 — Eliminação da recompilação redundante de recursos Tailwind no release do Gestor (req-156)

- **Status**: implemented-pending-review
- **Intake**: `sdd/human-requests/req-156.md`
- **Data**: 2026-09-02
- **Classificação**: DevOps, otimização de CI/CD, governança de build

---

## 1. Escopo aprovado

O release local (`./c2f manager:release`) é responsável por gerar e versionar os recursos derivados,
incluindo CSS Tailwind por recurso e manifestos de build. O workflow remoto deve testar esse estado
versionado e empacotá-lo diretamente, sem gerar um artefato posterior aos testes.

Este batch remove apenas as etapas `Generate resources and per-resource Tailwind CSS` e `Commit
Resources Updates` de `.github/workflows/release-gestor.yml`. O estágio seguinte preserva a remoção
dos arquivos-fonte de autoria e a criação de `gestor.zip` a partir dos arquivos derivados commitados.

## 2. Hipótese e validação planejada

- A recompilação remota invalida o cache entre Windows e Linux e pode gerar conteúdo não coberto pelas
  suítes já concluídas no workflow.
- A ausência dos dois nomes de etapa e da chamada ao compilador no workflow do Gestor confirma a remoção.
- O parse de YAML e as suítes `composer test` e `npm run test` cobrem a sintaxe do workflow e a integridade
  local exigida pelo intake.
- `release-instalador.yml` será verificado para confirmar que não contém compilação de recursos.

## 3. Arquivos previstos

- `.github/workflows/release-gestor.yml`
- `sdd/implementation/BATCH-INDEX.md`
- `sdd/implementation/BATCH-159.md`
- `sdd/validation/VALIDATION-CHECKLIST.md`
- `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md`

## 4. Implementação e evidências

- Removidas as etapas `Generate resources and per-resource Tailwind CSS` e `Commit Resources
  Updates` de `.github/workflows/release-gestor.yml`.
- O job segue de Playwright diretamente para `Remove resource files` e `Create gestor.zip + checksum`.
  Portanto, o ZIP parte exclusivamente dos derivados que já foram gerados, versionados e testados no
  release local.
- `.github/workflows/release-instalador.yml` foi conferido e não contém compilação de recursos nem
  commit de recursos no runner.
- `npx --yes yaml-lint .github/workflows/release-gestor.yml .github/workflows/release-instalador.yml`:
  sucesso nos dois workflows.
- Busca negativa por `Generate resources and per-resource Tailwind CSS`, `Commit Resources Updates`
  e `atualizacao-dados-recursos.php`: nenhuma ocorrência nos dois workflows.
- `composer test`: **1.073/1.073** testes, **7.418** asserções, 4 skips esperados e 2 deprecações
  reportadas pelo PHPUnit, sem falhas.
- `npm run test`: **27/27** arquivos e **382/382** testes aprovados. A suite emite avisos conhecidos
  de conexão recusada para `127.0.0.1:3000` durante o teardown de iframes do happy-dom, mas encerra
  com exit code 0.
- `git diff --check`: aprovado.

## 5. Review e pendências

Não foram identificados bugs funcionais, regressões, spec drift ou batch drift neste diff. O lote fica
pronto para review humano; não houve commit, push, deploy ou release nesta execução.