# BL-013 — Poda e modularização de documentação raiz (README e CHANGELOG) para eficiência de contexto de IA

- **Tipo**: Documentation / Architecture / AI-Workflow
- **Status**: OPEN
- **Severidade sugerida**: MÉDIA (eficiência de janela de contexto e prevenção de dispersão de agentes)
- **Origem**: diretiva do Engenheiro Chefe em 2026-08-31
- **Componentes**: `README.md`, `README-PT-BR.md`, `CHANGELOG.md`, `CHANGELOG-PT-BR.md`, `ai-workspace/`

## Contexto observado

1. `README.md` e `README-PT-BR.md` da raiz cresceram substancialmente com instruções, tabelas e detalhamentos que poluem a porta de entrada do projeto.
2. `CHANGELOG.md` e `CHANGELOG-PT-BR.md` guardam histórico cumulativo completo de todas as versões passadas.
3. Ao inspecionar esses arquivos, os agentes de IA consomem dezenas de milhares de tokens desnecessários de sua janela de contexto, dispersando a atenção e aumentando a chance de alucinações durante tarefas de manutenção e release.

## Proposta de melhoria

1. **Modularização dos READMEs**:
   - Manter na raiz apenas um resumo executivo de alta fidelidade (identidade do projeto, quickstart, tabela resumida de versões e status).
   - Transferir os detalhes aprofundados, manuais de comandos e diretrizes de desenvolvimento para a pasta `ai-workspace/` (subdividido em `en/` e `pt-br/`), referenciando via links.
2. **Arquivamento e Poda Incremental dos CHANGELOGs**:
   - Manter no changelog da raiz apenas a linha de versão atual e a imediatamente anterior.
   - Criar histórico incremental em `ai-workspace/docs/changelogs/` (ex.: `CHANGELOG-archive-v1.md`, `CHANGELOG-archive-v2-legacy.md`), definindo política periódica de poda.
3. **Paridade com a extensão de IA**:
   - Assegurar que a regra de verificação de release da extensão (`inspectReleaseDocumentContents`) continue validando a versão mais recente sem exigir o arquivo histórico completo na raiz.

## Critérios de aceite (rascunho)

- `README.md` e `README-PT-BR.md` concisos (<150 linhas) com links para aprofundamento em `ai-workspace/`.
- Versões legadas do `CHANGELOG` preservadas em arquivos arquivados na pasta `ai-workspace/`.
- Gates de release da extensão continuam verdes.
- Economia comprovada de tokens em prompts de agentes.

> Item de backlog — congelado em `sdd/backlog/` até decisão de planejamento e promoção para implementação.
