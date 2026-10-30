---
id: "RELATORIO-REQ-297"
title: "REQ-297: relatório ao Arquiteto-Chefe"
status: "ready-for-review"
date: "2026-10-30"
author: "executor"
target_repo: "conn2flow"
summary_short: "A REQ-297 cresceu para módulo Equipe, perfis por conta, dados por organização e autoria clínica no Photon."
summary_medium: "Decisões tomadas com o Engenheiro Chefe durante a execução, estado por ambiente e passos para regularizar.\nNada foi para Dev ou produção. Release é a última etapa, depois da homologação humana."
---

# REQ-297: relatório ao Arquiteto-Chefe

## Resumo

A REQ-297 pedia hierarquia pai/filho, cota por perfil e atualização do Core no Dev. Durante a execução, o Engenheiro Chefe ampliou o escopo. O resultado funciona no `snapphoton.local`, com testes verdes, e aguarda a homologação humana.

**O que precisa da sua ação:** regularizar o escopo em requisição, integrar os commits e conduzir a release, nesta ordem, na seção "Próximos passos".

**Nada foi publicado em Dev ou produção por este lote.** A release foi adiada pelo Engenheiro Chefe para depois dos testes.

## O que mudou em relação à REQ-297

| Tema | REQ-297 original | Como ficou, por decisão do Engenheiro Chefe |
| --- | --- | --- |
| Perfil do membro | Não definido; o BL-032 citava perfis filhos | O titular cria perfis da própria conta e só distribui o que tem, com validação no servidor |
| Liberação | `max_sub_users` | Chave `sub_users_active` **e** limite diferente de `0`. Fora disso, nenhum membro entra, mesmo cadastrado. `-1` = sem limite |
| Interface | Tela de equipe | Módulo novo `equipe` (Membros e Perfis). A aba em `/perfil-usuario/` foi só MVP e saiu |
| Permissão efetiva | Não definida | Perfil do membro **e** perfil do titular: quem perde um módulo leva os membros junto |
| Dados | Só o helper | Módulos clínicos do Photon passaram a usar a organização |
| Autoria | Não prevista | Registro clínico no nome de quem fez, dentro da conta do titular |
| Atualização do Dev | `update-all` pelo agente | Inviável. Caminho decidido: release e painel, como última etapa |

Todos os pontos estão em DEC-137.

## Estado por ambiente

| Ambiente | Situação |
| --- | --- |
| `snapphoton.local` | Versão completa. `administradores` com limite 3 e chave ligada, para o teste humano |
| `v3.1-conn2flow.local` | Versão antiga, da aba MVP. Sem as migrações e o módulo novos |
| `conn2flow.local` | Sem nada desta entrega |
| `dev.snapphoton.com` | Sem nada desta entrega. O core é anterior a 30/09 |
| Produção | Sem nada desta entrega |

## Entrega no core

Branch `feat/req-297-usuarios-pai-filho`, a partir de `252c3b4b`, worktree `conn2flow-req297`. Commits `a26a9d6c`, `e5034f86`, `26746993`, `a0cf6a8a`, sem push. Há registros em `memory/` sem commit, por instrução de deixar o commit para o final.

- **Migrações:** `20261030100000` (`usuarios.id_usuarios_pai`, FK `RESTRICT`, sinal lido de `id_usuarios`, que é `int unsigned`), `20261030100010` (`max_sub_users`) e `20261030100020` (`sub_users_active`, `id_usuarios_dono`).
- **Biblioteca `usuario.php`:** `usuario_organizacao_id()`, `usuario_hierarquia_bloqueado()`, `usuario_equipe_*`. Trava `GET_LOCK` por titular.
- **`gestor.php` (biblioteca):** `gestor_organizacao_id()`, `gestor_organizacao_perfil_slug()`, `gestor_perfil_permissao_sql()`.
- **Bloqueio:** sessão web aberta, emissão de sessão, telas de login, OAuth, token pessoal e login do aplicativo.
- **Filtro de permissão:** dez leituras (menu, módulo, `gestor_acesso`, operações, quatro no dashboard, API, distribuído).
- **Módulos:** `equipe` novo; `usuarios-perfis` com chave e limite, propagados a todos os idiomas. A chave concede e retira o módulo `equipe`. Perfis de conta ficam fora das listas do sistema.

## Entrega no `lumix`

Na árvore `lumix`, **sem commit**, por regra do projeto. Registro em `memory/implementation/BATCH-266-equipe-organizacao.md`.

- **Organização:** `gestor_organizacao_id()` em pacientes, prontuário, agenda, anamnese, automações, equipamentos, protocolos, calculadora e consumo do plano. Continuam por usuário: histórico do chat, buscas frequentes e configurações pessoais.
- **Autoria:** `user_id` segue sendo a organização. O autor vai em `X-Photon-Actor`, por `photon_oauth_authorization_headers()`, ponto único. No backend, `src/photon_atlas/request_actor.py`. Sem o cabeçalho, o autor é a organização.
- **Plano:** o membro usa o do titular, por perfil ou assinatura, em pacientes e chat.
- **Páginas públicas:** termo e anamnese mostram quem enviou o link (`professional_id`).
- **Código de `lumix` alterado:** 16 arquivos em `gestor/modulos`, 7 em `src/photon_atlas`, `src/photon_atlas/request_actor.py` e `tests/unit/test_request_actor.py` novos. O `git status` mostra também derivados gerados pelo pipeline (`*.json` de módulos, `db/data`, manifesto Tailwind).

## Achados que você precisa conhecer

1. **Registros do core presos ao projeto (risco no Dev e em outros projetos).** Em 17/09, uma sincronização carimbou registros do core com `project=snapphoton-local`. A proteção de projeto passou a impedir o core de atualizá-los. No Lab do Photon eram 79, entre layouts, páginas e componentes. Foi a causa do logo gigante: layout congelado em setembro com a página compilada para o layout atual. Soltei 68 direto no banco do Lab; 11 ficaram porque o `lumix` tem pasta de autoria (`form-ui`, três modais), e seguem desatualizados. **A release não resolve isso sozinha.** O Dev não pôde ser medido, e o `conn2flow-site-local` tem 10 layouts marcados, sem análise. Recomendo uma solução no sincronizador, como requisição própria.
2. **Página sobreposta não recebe marcação nova do core.** Foi o que escondeu a primeira versão da aba no Photon. Vale para qualquer tela nova em projeto com sobreposição.
3. **Comandos da REQ não alcançam o Dev.** `project:update-all snapphoton-dev` aborta na etapa 2/8, por não haver SSH nem `path_tests`; `manager:update-all` atua no Docker local; `update:core` não existe no core do Dev.
4. **O deploy do Photon no Dev está em rollback.** A REQ-118 registrou `Incorrect integer value: '' for column paginas.raiz`, por causa do core antigo. Depende da mesma release.
5. **API local do Photon roda de imagem.** Não é mais o espelho WSL. Imagem anterior guardada como `lumix-api:antes-req297`.

## Validação

| Verificação | Resultado |
| --- | --- |
| PHPUnit do core | 1.972 testes, sem falhas, 9 skips |
| Vitest do core | 700/700 |
| Backend do Photon | 822/822 |
| PHP do `lumix` | 27/27 |
| Quebras propositais da regra | 9 de 9 reprovadas |
| Módulo Equipe em `snapphoton.local` | 30/30 |
| Dados entre titular e membro | Aprovado |
| Autoria, com consulta ao banco clínico | 14/14 |

### Sem validação real

- Token pessoal bloqueado: só teste de fonte.
- Migrações em MySQL 8: só MariaDB.
- Isolamento entre contas no navegador: a conta de comparação foi barrada antes, por não ter o módulo. A separação é a do backend, que não mudou.
- Anexos, adendos, relatórios e termos de um membro: a autoria usa o mesmo ponto, mas a prova cobriu paciente, histórico, atendimento e auditoria.
- Autoria de automações disparadas por rotina: ficam na organização, por não haver usuário.

## Pendências funcionais

- **Agenda:** é única por conta. Agenda por profissional é funcionalidade nova, com tela de escolha e ajustes em `agenda.py`.
- **Módulo `usuarios`:** o administrador ainda reativa um membro sem passar pela cota.
- **FK `RESTRICT`:** `DELETE` físico de titular com filhos falha. O `reset-lab-customer.sh` precisa remover os filhos antes.
- **Convite:** criação direta com senha inicial, sem e-mail.
- **`en` no Photon:** a página do perfil em inglês segue a do core.

## Backlog criado

Rascunhos na `main`, sem commit, em `memory/backlog/`:

- **BL-046:** logo configurável na aba Site do `admin-environment`, para as telas do layout principal.
- **BL-047:** topo do painel diferente entre projetos: 64 px em `conn2flow.local` e 45 px em `snapphoton.local`, com `h-16` sem efeito. Causa não confirmada.

## Capa do módulo Equipe: tarefa para o agente de imagens

A capa atual é **provisória**, cópia de `usuarios-perfis`. O Engenheiro Chefe vai repassar a geração ao agente com ferramenta de imagem.

- **Arquivo:** `gestor/assets/modulos/covers/equipe.webp`, WebP 1024×1024, qualidade 90, até 120 KB.
- **Manifesto:** atualizar `bytes` e `sha256` da entrada `equipe` em `gestor/assets/modulos/covers/manifest.json`.

```text
Use case: stylized-concept. Asset type: Conn2Flow module cover for equipe, square 1024x1024.
Style Reference: Cohesive design system for Conn2Flow module covers: deep midnight blue seamless background, cyan and violet luminous accents, pearl frosted glass and soft rounded ceramic materials, orthographic 3D isometric camera, soft studio key light from upper left, subtle luminous edges, gentle ground contact shadow, sophisticated polished product render.
Center the complete object group inside the middle 70% with generous margins for thumbnail cropping, clean restrained composition, readable as a small thumbnail.
Subject: Three rounded frosted-glass identity badges arranged around one larger ceramic badge, joined by thin luminous cyan connection lines, suggesting a small team linked to one account.
Strict constraints: No lettering, no text, no numbers, no logos, no watermarks, no people, no borders.
```

## Próximos passos

1. **Regularizar o escopo:** registrar as ampliações em requisição, pois `req-297.md` e `CURRENT.md` não foram alterados pelo Executor.
2. **Homologação humana** no `snapphoton.local`, em andamento. Roteiro em `conn2flow-site/memory/PENDENCIAS-HUMANAS.md`, sem commit.
3. **Commits:** do `lumix` (BATCH-266) e dos registros restantes do core.
4. **Merge e release** `gestor-v3.0.16`. A `main` local tinha 9 commits sem push no início desta sessão.
5. **Dev, nesta ordem:** atualizar o core pelo `admin-atualizacoes`; rodar `project:deploy snapphoton-dev`; atualizar a API em `/opt/photon-dev`; conferir o logo e medir registros presos.
6. **Decidir** a solução definitiva para registros presos, a agenda por profissional e o BL-046/047.

## Rastreabilidade

- Relatório de execução: `memory/implementation/BATCH-305.md`, na branch.
- Decisão: DEC-137. Validação: `memory/validation/VALIDATION-CHECKLIST.md`.
- Lote do projeto: `lumix/memory/implementation/BATCH-266-equipe-organizacao.md`.
- Backup do Lab antes de soltar os registros: `/tmp/req297-photon-antes-de-soltar.sql.gz`, em `ssh lab`.
