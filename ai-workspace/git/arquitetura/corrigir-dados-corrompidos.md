# Instruções para Agente GIT - Script Corrigir Dados Corrompidos

## Objetivo
Registrar e versionar a implementação do script `corrigir-dados-corrompidos.php` (auditoria e correção de inconsistências entre banco legado, resources JSON e seeds *Data.json) e manter rastreabilidade das atualizações futuras.

## Escopo Atual
- Script localizado em: `gestor/controladores/agents/arquitetura/corrigir-dados-corrompidos.php`
- Especificação viva: `ai-workspace/prompts/arquitetura/corrigir-dados-corrompidos.md`
- Sem diffs detectados na execução dry-run atual (consistência validada).

## Próximos Commits (Modelo)
Quando houver alteração relevante use mensagens estruturadas:
```
feat(arquitetura/corrigir-dados): <resumo curto>

Contexto:
- <porque da mudança>
Alterações:
- <lista sucinta>
Validação:
- dry-run sem erros / diffs aplicados / etc.
Refs:
- tarefa / issue / data
```
Tipos aceitos: feat, fix, refactor, docs, chore, test.

## Checklist para Commit Atual
- [x] Especificação atualizada (checklists marcadas e resoluções registradas)
- [x] Script sincronizado no container (dry-run executado)
- [x] Adição de seção "Instruções para Agente GIT" na especificação
- [ ] Commit e push branch main (ou abrir PR se política exigir)

## Passos Recomendados
1. Verificar diff: `git status` / `git diff`
2. Adicionar arquivos: `git add gestor/controladores/agents/arquitetura/corrigir-dados-corrompidos.php ai-workspace/prompts/arquitetura/corrigir-dados-corrompidos.md ai-workspace/git/arquitetura/corrigir-dados-corrompidos.md`
3. Commit: usar modelo acima.
4. Push: `git push origin main` (ou criar branch/PR se fluxo GitFlow).
5. Registrar no changelog (se existente) em seção Added.

## Política de Versionamento
- Incrementar versão minor quando adicionar nova flag ou tipo de diff.
- Incrementar patch para correções internas sem mudar comportamento externo.

## Observações Futuras
- Implementação futura da flag `--preserve-extra-option` deverá ser registrada como `feat`.
- Adição dos arquivos de idioma completos: `feat(i18n)`.
- Integração pipeline seeds: `chore(pipeline)`.

--
Última atualização: 2025-08-12
