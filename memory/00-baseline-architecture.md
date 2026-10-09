# System Master Index — conn2flow

Projeto: conn2flow. Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.
Papel: Core PHP Framework Conn2Flow: CLI c2f, Gestor e Runtime.
Fundação MDD adotada pela REQ-068 / BATCH-070 (matriz conn2flow-ai-workspace), 2026-10-09. Histórico SDD preservado por git mv.

## Entrada econômica

1. Leia este router, [mecânica](01-general-memory.md) e [política](02-policy.md).
2. Leia [CURRENT](human-requests/CURRENT.md) quando existir e a requisição apontada; confirme aprovação, lote e autonomia.
3. Use o index.md da área antes de carregar documentos extensos. Consulte somente as fontes necessárias ao slice.
4. Confira [README](README.md) e o [índice geral](index.md).

## Mapa do repositório

| Área | Responsabilidade |
| --- | --- |
| memory/ | Governança persistente (MDD): requisições, lotes, decisões, validação, relatórios e índices |
| AGENTS.md | Arquivo do projeto |
| ai-workspace/ | Diretório do projeto |
| c2f | Arquivo do projeto |
| c2f.bat | Arquivo do projeto |
| c2f.cmd | Arquivo do projeto |
| c2f.ps1 | Arquivo do projeto |
| CHANGELOG-PT-BR.md | Arquivo do projeto |
| CHANGELOG.md | Arquivo do projeto |
| CLAUDE.md | Arquivo do projeto |
| cli/ | Diretório do projeto |
| CODEX.md | Arquivo do projeto |
| composer.json | Arquivo do projeto |
| composer.lock | Arquivo do projeto |
| debug.log | Arquivo do projeto |
| dev-environment/ | Diretório do projeto |
| dev-plugins/ | Diretório do projeto |
| GEMINI.md | Arquivo do projeto |
| gestor/ | Diretório do projeto |
| gestor-instalador/ | Diretório do projeto |
| LICENSE | Arquivo do projeto |
| output/ | Diretório do projeto |
| package-lock.json | Arquivo do projeto |
| package.json | Arquivo do projeto |
| phpunit.xml | Arquivo do projeto |
| playwright.config.js | Arquivo do projeto |
| README-PT-BR.md | Arquivo do projeto |
| README.md | Arquivo do projeto |
| temp/ | Diretório do projeto |
| tests/ | Diretório do projeto |
| vitest.config.js | Arquivo do projeto |

## Autoridade e preservação

Código, schemas e configuração vigentes prevalecem sobre memórias históricas. Decisões e contratos aprovados governam requisitos; intake não substitui contrato normativo. A governança canônica vive na matriz conn2flow-ai-workspace; skills canônicas são propagadas por scripts/skills/sync-skills.cjs e skills locais deste repositório são preservadas.

## Baseline anterior (preservada)

﻿# 00 Baseline Architecture

## Objetivo

Este arquivo registra o estado atual aprovado do ecossistema `conn2flow` antes de batches funcionais novos. Ele existe para evitar que a adoção do SDD apague, simplifique em excesso ou reescreva sem necessidade uma base de código extensa que já está em uso.

## Cláusula de preservação do legado

O código legado e a estrutura operacional documentados neste baseline são considerados funcionais e aprovados. Qualquer mudança futura deve partir desta base como referência primária e deve descrever explicitamente o delta pretendido, em vez de assumir que a base atual pode ser descartada.

## Estrutura de alto nível do repositório

- `gestor/`: núcleo principal do CMS e runtime de administração.
- `gestor-instalador/`: instalador web automatizado e multilingual.
- `dev-environment/`: ambiente local de desenvolvimento com `data/`, `docker/`, `docs/` e `templates/`.
- `ai-workspace/`: documentação, prompts, scripts, templates e recursos usados por agentes e por automações do fluxo de desenvolvimento.
- `dev-plugins/`: estrutura de desenvolvimento, build, deploy e testes de plugins públicos e privados.
- `.vscode/`: tasks usadas para operação local de Docker, sincronização, build, release e deploy de projetos.
- `.github/`: workflows GitHub e a camada de customização do Copilot.
- `.claude/`: camada de customização do Claude Code para o fluxo SDD.
- `temp/`: artefatos temporários, utilitários e espaços de trabalho locais.

## Funcionamento dos scripts locais e das tasks do VS Code

O repositório expõe boa parte da operação local por tasks do VS Code e por scripts shell/PHP chamados por essas tasks.

### Tasks do VS Code

O arquivo `.vscode/tasks.json` já concentra rotas operacionais recorrentes, incluindo:

- status de containers e logs Docker
- sincronização, build e release do instalador
- sincronização, build, update e release do gestor
- sincronização, build, resources e release de plugins públicos e privados
- deploy e update de projetos cadastrados

Essas tasks não são detalhe cosmético. Elas são parte do baseline operacional atual e devem continuar sendo tratadas como entrypoints válidos para o ambiente local.

### Scripts locais

Os scripts chamados por essas tasks se concentram principalmente em:

- `ai-workspace/en/scripts/commits/`
- `ai-workspace/en/scripts/dev-environment/`
- `ai-workspace/en/scripts/projects/`
- `ai-workspace/en/scripts/releases/`
- `ai-workspace/en/scripts/resources/`
- `ai-workspace/en/scripts/tests/`
- `ai-workspace/en/scripts/updates/`
- `dev-plugins/plugins/**/scripts/`
- `scripts/hooks/` para lembretes e bootstrap de sessão SDD

Na prática, o `conn2flow` já opera com automação local por Bash, PowerShell, PHP e Docker. Qualquer batch que altere esse fluxo precisa preservar a compatibilidade operacional ou registrar uma mudança normativa clara antes da troca.

## `environment.json` como concentrador de configurações

O arquivo `dev-environment/data/environment.json` é um concentrador operacional do ambiente local.

Ele hoje organiza, em um único ponto:

- mapeamentos `source`, `target`, `dockerPath` e `accessURL`
- variantes do ambiente de desenvolvimento e ambiente de testes
- configuração do instalador local
- cadastro de projetos em `devProjects`
- caminhos de cada projeto, URLs local/remota, rotas de teste e comandos auxiliares como build de Tailwind
- metadados de integração usados pela automação local

Consequências práticas:

- mudanças em scripts e tasks frequentemente dependem de `environment.json`
- chaves, paths e nomes de projeto devem permanecer coerentes entre scripts, tasks e ambiente Docker
- esse arquivo pode conter dados sensíveis de integração; sdd e batches não devem duplicar valores secretos nele contidos

## Implicação normativa para batches futuros

Qualquer spec ou batch futuro deve assumir como baseline:

- que `gestor/`, `gestor-instalador/`, `dev-environment/`, `ai-workspace/` e `dev-plugins/` já fazem parte do produto real e não são descartáveis
- que o fluxo via tasks do VS Code e scripts locais é parte da operação normal
- que `environment.json` é o principal ponto de acoplamento do ambiente local
- que alterações amplas de arquitetura ou consolidação de scripts precisam ser justificadas e validadas explicitamente
