---
id: "BATCH-305"
title: "REQ-297: hierarquia de usuários pai/filho, cotas por perfil e aba Equipe"
status: "blocked"
date: "2026-10-30"
author: "executor"
target_repo: "conn2flow"
summary_short: "Pai/filho, cota max_sub_users, bloqueio herdado e aba Equipe prontos; Dev será atualizado por release e painel após homologação."
summary_medium: "PHPUnit 1.947 sem falhas, Vitest 700 e E2E 23/23 no Lab.\nO Dev não é alcançável pelos comandos autorizados: o caminho decidido é release e painel, após homologação."
request: "REQ-297"
topology: "solo"
autonomy: "autonomo_monitorado"
---

# BATCH-305 — Hierarquia de usuários pai/filho e cotas por perfil

Branch `feat/req-297-usuarios-pai-filho`, worktree `conn2flow-req297`, a partir da `main` (`252c3b4b`). Sem push.

## Entregue

| Item da REQ | Como ficou |
| --- | --- |
| `id_usuarios_pai` | Migração `20261030100000`: índice, FK `RESTRICT` e `ON UPDATE CASCADE`. A coluna copia o sinal de `id_usuarios`, que é `int unsigned` nas instalações: `INT` com sinal, como a REQ descreve, seria recusado pelo banco. |
| Metadado do schema | O `schema-metadata.json` não lista colunas; a migração é o registro equivalente previsto na REQ. |
| `usuario_organizacao_id()` | Em `gestor/bibliotecas/usuario.php`. Sem argumento usa `$_GESTOR['usuario-id']`, que é o identificador real (a REQ citava `$_SESSION`). |
| `max_sub_users` | Migração `20261030100010` e campo no formulário de perfis. `0` = sem equipe (padrão), `-1` = sem limite, `N` = assentos. Vale para todas as linhas de idioma do perfil. |
| Bloqueio pelo pai | `usuario_hierarquia_bloqueado()`, calculado a cada verificação, sem alterar o status do filho. Quando o titular volta, o acesso volta. |
| Interface | Aba "Equipe" em `/perfil-usuario/`, em Tailwind, pt-br e en. Aparece para titular com cota ou com membros. |

### Barreiras do bloqueio

Sessão web aberta (`gestor_permissao_token_processar`), emissão de sessão web (`usuario_gerar_token_autorizacao`, última barreira de senha, 2FA, social e distribuído), telas de login com mensagem de conta inativa, emissão e renovação OAuth, validação OAuth, token pessoal e login do aplicativo.

## Decisões

[DEC-137](../decisions/DECISION-LOG.md): membro herda o perfil do titular, login do membro é o e-mail, hierarquia de um nível, bloqueio calculado e trava `GET_LOCK` por titular contra corrida de assentos.

## Validação

- **PHPUnit:** 1.947 testes, 21.858 asserções, zero falhas, 9 skips (Windows, PHP 8.5.8).
- **Novos testes:** `UsuarioHierarquiaReq297Test`, 34 casos. Quatro mutantes na regra (cota, status do filho, dono do membro e status do pai) foram todos reprovados.
- **Vitest:** 700/700 em 63 arquivos.
- **Lab `conn2flow-v31-local`:** MariaDB com core defasado (última migração em 08/10). `project:sync-core` e `project:sync-db --core-resources` com saída 0, quatro migrações aplicadas, FK `int unsigned`, `usuarios_perfis` `~0` e tabelas de identidade idênticas antes e depois.
- **Navegador:** 23/23 no roteiro real: cota pelo formulário, criação pela aba, recusa acima da cota, login do membro, queda de sessão web e de token com o titular inativo, retorno automático, desativar e ativar, 390 px sem rolagem, nenhum erro de JavaScript. Membro de teste removido e cota zerada ao final.
- **Defeito achado pelo roteiro e corrigido:** `abaDaUrl()` não aceitava `#equipe`; depois de salvar, a página voltava para outra aba.

### Sem validação real

Token pessoal: só teste de fonte. O caminho OAuth, com o mesmo bloqueio, foi exercitado no navegador. MySQL 8: não executado; as migrações usam SQL comum aos dois bancos.

## Bloqueio: atualização do Dev (`snapphoton-dev`)

Critério da REQ pendente. Nada foi enviado ao Dev; só houve leitura pela API.

- `project:update-all snapphoton-dev` não alcança o Dev: o projeto não tem `deploy_mode: ssh`, `target` nem `path_tests`, e o `sync-core-to-project.sh` aborta na etapa 2/8. Antes disso, a manutenção escreveria `temp/maintenance.json` no fonte do `lumix`, onde há trabalho sem commit da REQ-118.
- `manager:update-all` atua no Docker local, não no Dev.
- `update:core` não funciona: o Dev responde que só conhece `start, deploy, db, finalize, status, cancel`, ou seja, é anterior à REQ-201 (30/09) e nem informa a versão. Além disso, `update:core` baixa releases, e este lote está em branch.
- Acesso anônimo com 302 para `/signin/` é o bloqueio intencional.

**Decisão do Engenheiro Chefe (2026-10-30):** o Dev será atualizado por release e pelo painel. Ordem: homologação humana, merge na `main`, release `gestor-v3.0.16` e atualização pelo `admin-atualizacoes` do Dev, autenticado com perfil válido. Merge e release aguardam o ok humano. Depois da atualização, conferir no Dev: migrações, aba Equipe e dados do projeto preservados. Publicar o projeto só com `project:deploy`.

## Limites conhecidos

- O administrador pode reativar um membro pelo módulo `usuarios` sem passar pela cota; a cota vale na aba Equipe.
- Trocar o perfil do titular não troca o dos membros.
- Membros além do limite depois de um plano menor continuam ativos; só novos cadastros e ativações são recusados.
- `DELETE` físico de titular com filhos falha pela FK `RESTRICT`. O `reset-lab-customer.sh` e rotinas parecidas precisam remover os filhos antes.
- "Convidar" é criação direta com senha inicial, sem e-mail de convite.
- Ficaram fora, por estarem só no BL-032: perfis filhos permitidos, redefinir senha, alternar papel e auditoria por membro.
- Os módulos ainda não usam `usuario_organizacao_id()`: a adoção fica com cada módulo.

## Handoff

Projeto `conn2flow`, raiz `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req297`, REQ-297, BATCH-305, topologia solo, autonomia `autonomo_monitorado`. Próxima ação: homologação humana e ok para merge e release; depois, atualização do Dev pelo painel. Antes de remover a worktree, desfazer as junctions de `vendor` e `node_modules`.
