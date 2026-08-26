# BATCH-139 — Limpeza pós-sucesso de releases antigas

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-136.md`
- **Data de abertura**: 2026-08-26
- **Classificação**: implementação incremental / scripts de release / GitHub Actions

## Objetivo

Preservar a última release válida durante toda a preparação e publicação de uma nova versão, removendo
a exclusão antecipada de tags/releases dos scripts locais e transferindo a limpeza da mesma série para
o fim bem-sucedido dos workflows do GitHub Actions.

## Slice aprovado

1. Remover a exclusão local antecipada de tags e releases dos cinco scripts indicados no intake.
2. Substituir os pushes separados de branch e tags por um único `git push --atomic` da branch atual e
   da nova tag.
3. Disponibilizar o histórico completo de tags nos workflows de Gestor e Instalador.
4. Adicionar, imediatamente após `Create Release`, a limpeza das tags/releases anteriores da mesma
   série `major.minor`, restrita explicitamente aos padrões `gestor-v${TAG_SERIES}.*` e
   `instalador-v${TAG_SERIES}.*`, preservando a tag recém-publicada.
5. Proteger a limpeza com `if: success()` e manter tolerância idempotente para releases/tags antigas
   que já não existam.

## Fora do escopo

- Alterar incremento de versão, empacotamento, conteúdo das notas ou testes de produto dos workflows.
- Executar release real, criar/excluir tags reais ou chamar a API do GitHub durante a validação local.
- Alterar workflows de release de plugins, inexistentes no escopo explícito do intake.
- Fazer commit, push ou deploy.

## Contrato de validação

- `bash -n` limpo nos cinco scripts alterados.
- YAML dos dois workflows parseado com sucesso.
- Verificação estática confirma ausência de pré-exclusão local e presença de push único com `--atomic`.
- Verificação estática confirma que a limpeza ocorre depois de `Create Release`, usa `if: success()`,
  preserva a tag atual e não alcança outras séries ou famílias de tags.
- `git diff --check` limpo.

## Evidências

- `bash -n`: **5/5** scripts aprovados.
- Parse YAML com `yaml@2`: **2/2** workflows aprovados.
- Verificação estática: **5/5** scripts sem `OLD_TAGS`, `git push --delete` ou `git push --tags`,
  cada um com exatamente um `git push --atomic origin` para branch atual + nova tag.
- Verificação estática: **2/2** workflows com histórico completo de tags (`fetch-depth: 0`), cleanup
  depois de `Create Release`, `if: success()` e exclusão explícita da tag atual.
- Simulação local com múltiplas famílias/séries:
  - `gestor-v2.9.40` selecionou somente `gestor-v2.9.38` e `gestor-v2.9.39`;
  - `instalador-v1.5.6` selecionou somente `instalador-v1.5.5`;
  - `gestor-v2.8.*`, `gestor-v3.0.*`, `instalador-v1.4.*` e `plugin-*` foram preservadas.
- `git diff --check`: limpo.
- Review findings-first: sem findings.
- Memória de execução: **3.820 bytes / 49 linhas**, abaixo do alerta; nenhuma poda necessária.
- Nível 1 respeitado: nenhum commit, push, deploy, criação/exclusão de tag real ou chamada mutável à
  API do GitHub.
