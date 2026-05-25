# 00 Baseline Architecture

## Objetivo

Este arquivo registra o estado atual aprovado do ecossistema `conn2flow` antes de batches funcionais novos. Ele existe para evitar que a adoÃ§Ã£o do SDD apague, simplifique em excesso ou reescreva sem necessidade uma base de cÃ³digo extensa que jÃ¡ estÃ¡ em uso.

## ClÃ¡usula de preservaÃ§Ã£o do legado

O cÃ³digo legado e a estrutura operacional documentados neste baseline sÃ£o considerados funcionais e aprovados. Qualquer mudanÃ§a futura deve partir desta base como referÃªncia primÃ¡ria e deve descrever explicitamente o delta pretendido, em vez de assumir que a base atual pode ser descartada.

## Estrutura de alto nÃ­vel do repositÃ³rio

- `gestor/`: nÃºcleo principal do CMS e runtime de administraÃ§Ã£o.
- `gestor-instalador/`: instalador web automatizado e multilingual.
- `dev-environment/`: ambiente local de desenvolvimento com `data/`, `docker/`, `docs/` e `templates/`.
- `ai-workspace/`: documentaÃ§Ã£o, prompts, scripts, templates e recursos usados por agentes e por automaÃ§Ãµes do fluxo de desenvolvimento.
- `dev-plugins/`: estrutura de desenvolvimento, build, deploy e testes de plugins pÃºblicos e privados.
- `.vscode/`: tasks usadas para operaÃ§Ã£o local de Docker, sincronizaÃ§Ã£o, build, release e deploy de projetos.
- `.github/`: workflows GitHub e a camada de customizaÃ§Ã£o do Copilot.
- `.claude/`: camada de customizaÃ§Ã£o do Claude Code para o fluxo SDD.
- `temp/`: artefatos temporÃ¡rios, utilitÃ¡rios e espaÃ§os de trabalho locais.

## Funcionamento dos scripts locais e das tasks do VS Code

O repositÃ³rio expÃµe boa parte da operaÃ§Ã£o local por tasks do VS Code e por scripts shell/PHP chamados por essas tasks.

### Tasks do VS Code

O arquivo `.vscode/tasks.json` jÃ¡ concentra rotas operacionais recorrentes, incluindo:

- status de containers e logs Docker
- sincronizaÃ§Ã£o, build e release do instalador
- sincronizaÃ§Ã£o, build, update e release do gestor
- sincronizaÃ§Ã£o, build, resources e release de plugins pÃºblicos e privados
- deploy e update de projetos cadastrados

Essas tasks nÃ£o sÃ£o detalhe cosmÃ©tico. Elas sÃ£o parte do baseline operacional atual e devem continuar sendo tratadas como entrypoints vÃ¡lidos para o ambiente local.

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
- `scripts/hooks/` para lembretes e bootstrap de sessÃ£o SDD

Na prÃ¡tica, o `conn2flow` jÃ¡ opera com automaÃ§Ã£o local por Bash, PowerShell, PHP e Docker. Qualquer batch que altere esse fluxo precisa preservar a compatibilidade operacional ou registrar uma mudanÃ§a normativa clara antes da troca.

## `environment.json` como concentrador de configuraÃ§Ãµes

O arquivo `dev-environment/data/environment.json` Ã© um concentrador operacional do ambiente local.

Ele hoje organiza, em um Ãºnico ponto:

- mapeamentos `source`, `target`, `dockerPath` e `accessURL`
- variantes do ambiente de desenvolvimento e ambiente de testes
- configuraÃ§Ã£o do instalador local
- cadastro de projetos em `devProjects`
- caminhos de cada projeto, URLs local/remota, rotas de teste e comandos auxiliares como build de Tailwind
- metadados de integraÃ§Ã£o usados pela automaÃ§Ã£o local

ConsequÃªncias prÃ¡ticas:

- mudanÃ§as em scripts e tasks frequentemente dependem de `environment.json`
- chaves, paths e nomes de projeto devem permanecer coerentes entre scripts, tasks e ambiente Docker
- esse arquivo pode conter dados sensÃ­veis de integraÃ§Ã£o; sdd e batches nÃ£o devem duplicar valores secretos nele contidos

## ImplicaÃ§Ã£o normativa para batches futuros

Qualquer spec ou batch futuro deve assumir como baseline:

- que `gestor/`, `gestor-instalador/`, `dev-environment/`, `ai-workspace/` e `dev-plugins/` jÃ¡ fazem parte do produto real e nÃ£o sÃ£o descartÃ¡veis
- que o fluxo via tasks do VS Code e scripts locais Ã© parte da operaÃ§Ã£o normal
- que `environment.json` Ã© o principal ponto de acoplamento do ambiente local
- que alteraÃ§Ãµes amplas de arquitetura ou consolidaÃ§Ã£o de scripts precisam ser justificadas e validadas explicitamente
