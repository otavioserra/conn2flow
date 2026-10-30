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

## Rodada 2: `snapphoton-local` (2026-10-30)

Pedido do Engenheiro Chefe: publicar no Lab do Photon e conferir o logo gigante da dashboard. O código do core não mudou nesta rodada.

- **Publicação:** `project:update-all snapphoton-local`, saída 0. O caminho só de core (`sync-core` + `sync-db`) foi descartado, porque as sementes do core sobrescreveriam as telas que o `lumix` sobrepõe. Os arquivos sem commit do `lumix` foram conferidos por hash e já eram iguais aos do Lab.
- **Página sobreposta:** o `lumix` mantém cópia própria de `perfil-usuario.html`, sem a aba. Foram acrescentados aba, painel e paleta Tailwind inerte (`py-0.5`, `hover:border-slate-400`), no `lumix`, sem commit. A dependência declarada não funciona: o compilador de projeto não enxerga componente de módulo do core.
- **Causa do logo:** em 17/09 uma sincronização carimbou registros do core com `project=snapphoton-local`. A proteção de projeto passou a impedir o core de atualizá-los. O `layout-administrativo-tailwind` ficou congelado, com logo `h-8`, enquanto a página nova era compilada para o layout atual, sem essa regra.
- **Correção no Lab:** 68 registros soltos (8 layouts, 32 páginas e 28 componentes), `user_modified=0`, com backup em `/tmp/req297-photon-antes-de-soltar.sql.gz` no Lab. Ficaram marcados 11 componentes que o `lumix` tem em `resources/` sem entregar nos dados (`form-ui` e três modais), que seguem desatualizados.
- **Validação:** navegador 34/34 em `snapphoton.local`, com a titular `local@conn2flow.com`: fluxo completo, toda classe da aba com regra CSS, logo com 44 px e HTTP 200 em dez telas, sem erro de JavaScript. Membro de teste removido. O perfil `academico` ficou com limite 3 para a homologação.

### Pendente desta rodada

- O Dev pode ter os mesmos registros presos. Não dá para medir sem acesso ao banco, e a correção no Lab foi manual: a release não a resolve sozinha. Falta uma solução permanente no sincronizador, que não foi feita.
- A aba Equipe no Photon depende do commit no `lumix` e de `project:deploy snapphoton-dev`.
- Só pt-br tem a cópia do `lumix`; a página `en` do Photon usa a do core.
- O topo do painel do Photon mostra o logo da Conn2Flow: o `admin-topbar-tailwind` usa `images/logo-principal.png`, não `project/logo-principal.png`.

## Rodada 3: módulo Equipe e dados da organização (2026-10-30)

Pedido do Engenheiro Chefe, que escolheu a opção A e adiou o release para depois dos testes dele. Substitui a aba MVP e o trecho "o membro herda o perfil" dos limites abaixo.

- **Core:** migração `20261030100020` (`sub_users_active`, `id_usuarios_dono`), módulo `equipe` (abas Membros e Perfis), chave no perfil e filtro `gestor_perfil_permissao_sql()` em dez leituras de permissão (menu, acesso ao módulo, `gestor_acesso`, operações, quatro no dashboard, API e acesso distribuído).
- **`lumix` (sem commit):** `gestor_organizacao_id()` em pacientes, prontuário, agenda, anamnese, automações, equipamentos, protocolos, calculadora e consumo do plano. Plano por perfil do membro passa a ser o do titular. Lista de perfis dos planos sem perfis de conta. Continuam por usuário: histórico do chat, buscas frequentes e configurações pessoais.
- **Validação:** PHPUnit 1.972 sem falhas, Vitest 700/700, 59 casos da hierarquia com 5 mutantes novos reprovados, 27 testes PHP do `lumix`. No `snapphoton.local`: 30/30 do módulo e prova de dados, com paciente do titular visto pelo membro e vice-versa, com plano herdado.
- **Defeito achado no teste real:** membro de titular com plano por perfil caía no plano padrão.
- **Estado do Lab:** `administradores` com limite 3 e chave ligada, para o teste humano. Pacientes de teste arquivados.

### Pendente desta rodada

- **Agenda:** `professional_user_id` é a conta; a agenda é única por conta. Agenda por profissional é funcionalidade nova: exige tela de escolha do profissional e vários ajustes em `agenda.py`.
- **Busca clínica:** não calcula plano no Gestor; não foi exercitada com membro.
- **Isolamento entre contas no navegador:** a conta de comparação foi barrada antes, por não ter o módulo. A separação em si é a do backend, que não mudou.
- **Capa do módulo `equipe`:** provisória, cópia da de `usuarios-perfis`. A capa própria fica com o agente de imagens.

## Rodada 4: autoria por usuário no Photon (2026-10-30)

Aceita pelo Engenheiro Chefe, que informou não haver usuários reais e liberou adaptar o que fosse preciso. Código só no `lumix`, sem commit; o core não mudou nesta rodada.

- **Contrato:** `user_id` segue sendo a organização. O autor real vai em `X-Photon-Actor`, enviado por `photon_oauth_authorization_headers()`, ponto único dos módulos. No backend, `request_actor.py` (middleware ASGI e `actor_id()`), com a organização como reserva em rotinas e páginas públicas.
- **Coberto:** pacientes, histórico, anamnese, atendimento, adendo, arquivos, relatórios, termos, equipamentos, automações e auditoria clínica.
- **Plano do membro no chat:** corrigido nos dois módulos de chat, pelo titular.
- **Páginas públicas:** termo e anamnese mostram quem enviou o link (`professional_id`), não o titular.
- **Validação:** backend 822/822, PHP do `lumix` 27/27, e 14/14 no `snapphoton.local` com consulta direta ao banco clínico (titular 2, membro 41): paciente, histórico, atendimento e auditoria no membro, com a organização no titular. Teste de guarda reprova autor igual à organização.
- **API local:** roda de imagem (`lumix-api`), não do espelho WSL: a REQ-118 trocou o compose. Procedimento em `docker-compose.local-image.yml`. Imagem anterior guardada como `lumix-api:antes-req297`.

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
