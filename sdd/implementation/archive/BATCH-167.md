# BATCH-167 — Escape do cgroup do PHP-FPM no disparo desacoplado (REQ-040, Pilar 4)

- **Status**: implementado-validado-aguardando-homologacao
- **Intake**: `conn2flow-site` → `sdd/human-requests/host-manager/req-040-ssl-painel-pma-sso-desacoplamento-cgroup-e-host-url.md`
- **Data de abertura**: 2026-09-03
- **Classificação**: resiliência de infraestrutura / correção de rumo do BATCH-166
- **Modo de autonomia**: supervisionado
- **Lote irmão**: `BATCH-033` no `conn2flow-site` (Pilares 1, 2, 3 e 5)

## Objetivo

Fazer o disparo manual de uma rotina de provisionamento sobreviver ao `systemctl restart
php8.5-fpm` que o próprio HestiaCP executa no meio da esteira.

## Diagnóstico: o BATCH-166 resolveu a metade errada

O lote anterior tirou o processo da **sessão** do worker, com `setsid`. A conta do Transforma MP
continuou congelada em `provisioning`.

**Sessão não é cgroup.** `setsid` cria uma sessão nova e um novo grupo de processos, mas o filho
permanece no cgroup `php8.5-fpm.service`. Quando o systemd reinicia essa unidade, ele encerra
*todo o cgroup* — o instalador morre no meio de `v-add-web-domain`, exatamente como antes. O
`setsid` desacoplou de verdade; só não do que importava.

Resposta à pergunta do operador ("*ele executa e marca para a segunda execução, ou está parado por
erro?*"): **nem um nem outro no momento da morte.** O processo é morto sem chance de gravar nada, e
a conta fica reservada em `provisioning` sem ninguém executando. Quem a resgata é o reaper entregue
no BATCH-032: passados `stale_after_minutes` (30), ele faz rollback dos recursos parciais e
re-enfileira enquanto `tentativas < max_tentativas`. Ou seja, a conta se recupera sozinha — só
depois de meia hora, e sem que o clique do operador tenha produzido efeito.

## Implementação

Três estratégias, da mais isolada para a menos, **escolhidas por sondagem** e não por suposição:

| Estratégia | Isola cgroup | Como |
| --- | --- | --- |
| `systemd-run` | sim | `--scope` cria um cgroup próprio sob `system-cron.slice` |
| `ssh` | sim | o processo nasce sob o cgroup de sessão do `sshd` |
| `setsid` | **não** | apenas sessão nova; último recurso, e declarado como tal |

## Decisões tomadas na implementação

1. **Sondar, não supor.** `systemd-run --scope` depende de autorização do systemd, e o pool roda
   como usuário sem privilégio: numa instalação típica ele é *negado*. Anunciar isolamento sem
   verificar reproduziria a falha silenciosa que este lote existe para acabar. Cada candidata é
   exercitada com um comando trivial (`/bin/true`, `command -v`) antes de ser adotada, e a sonda
   usa **o mesmo prefixo** do disparo real — sondar com flags diferentes aprovaria uma
   configuração que falharia adiante, em background, sem ninguém ver.
2. **`setsid` continua na lista, mas não mente.** `admin_cron_disparo_isola_cgroup()` devolve
   `false` para ele, e a resposta da tela troca a mensagem por
   `msg-run-detached-no-isolation`, dizendo ao operador que um reinício de serviço pode
   interromper a tarefa e que o agendamento retoma. Remover a estratégia seria pior: ela funciona
   na maioria dos disparos, que não reiniciam serviço nenhum.
3. **Montar e verificar viraram funções separadas.** `admin_cron_disparo_systemd_montar()` e
   `admin_cron_disparo_ssh_montar()` são puras; a disponibilidade do binário fica na sondagem.
   Sem essa separação, nada disso seria verificável num host de desenvolvimento sem systemd.
4. **A escolha forçada é lida antes do cache.** Memorizar a sondagem por requisição faz sentido
   (o ambiente não muda no meio de um disparo); mascarar a configuração do operador com um
   resultado anterior, não.
5. **A configuração passou a vir do `.env`.** Achado durante a implementação: o núcleo **não
   popula `$_GESTOR['config']`** — essa chave é uma convenção que o config-loader do Host Manager
   cria para si. As opções do BATCH-166 (`cron_php_binary`, `cron_tarefas_desacopladas`) e as
   novas deste lote estavam lendo de um lugar que ninguém preenche: seriam inertes, sem erro
   visível. `admin_cron_config()` lê `$_ENV` → `getenv()` → `$_GESTOR['config']`.
6. **Envelope de retorno uniforme.** Achado pelo próprio teste: três retornos de erro precoces não
   declaravam `estrategia`/`isolado`. O chamador atual só os lê no ramo de sucesso, mas a
   assimetria é um footgun barato de eliminar.

## Configuração disponível ao operador

| Variável | Efeito |
| --- | --- |
| `CRON_DISPATCH_STRATEGY` | força `systemd-run`, `ssh` ou `setsid`, pulando a sondagem |
| `CRON_DISPATCH_SLICE` | slice do `systemd-run` (padrão `system-cron.slice`) |
| `CRON_DISPATCH_SSH_HOST` / `_USER` / `_PORT` / `_IDENTITY` | alvo da estratégia SSH |
| `CRON_PHP_BINARY` | binário do PHP CLI (BATCH-166, agora efetivo) |
| `CRON_TAREFAS_DESACOPLADAS` | lista de ids forçados a desacoplar (BATCH-166, agora efetivo) |

## Arquivos tocados

| Arquivo | Mudança |
| --- | --- |
| `gestor/modulos/admin-cron/includes/admin-cron-dispatch.php` | Estratégias, sondagem, `admin_cron_config()`, envelope uniforme |
| `gestor/modulos/admin-cron/admin-cron.php` | Resposta informa `estrategia`/`isolado` e troca a mensagem sem isolamento |
| `gestor/modulos/admin-cron/admin-cron.json` | Variável `msg-run-detached-no-isolation` (`pt-br` e `en`) |
| `tests/Unit/PHP/AdminCronReq040Test.php` | **Novo** — 16 testes |

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `php -l` nos 2 arquivos PHP tocados | OK |
| `AdminCronReq040Test` | 16/16, 39 asserções |
| `AdminCronReq039Test` (não regrediu) | 13/13 |
| Suíte PHPUnit completa | **1.158/1.158**, 7.730 asserções, 4 skipped |
| Suíte Vitest completa | **417/417** |
| `c2f resources:sync` | 2.848 recursos, 0 problemas |

### Guarda validada por mutação

| Mutação aplicada | Teste que acusou |
| --- | --- |
| `setsid` marcado como isolante de cgroup | `testSetsidNaoContaComoIsolamentoDeCgroup` |

## Pendências

- Homologação na VM Lab: disparar `host-manager-provisionamento` em `/admin-cron/` e verificar,
  na resposta, **qual estratégia foi escolhida**. Se vier `setsid`, o isolamento não está
  disponível e o caminho a habilitar é o SSH (`CRON_DISPATCH_SSH_HOST=127.0.0.1` com chave
  pública instalada para o usuário do pool) — é a rota que não pede privilégio de systemd.
- Confirmar em seguida que o provisionamento atravessa o `systemctl restart php8.5-fpm` sem
  interrupção, com a conta chegando a `active` sem passar pelo reaper.

## Nota de concorrência

Outro agente segue alterando arquivos fora do escopo nesta árvore
(`cli/src/Commands/CssRebuildCommand.php`, `gestor/controladores/agents/arquitetura/*.php`,
`ai-workspace/en/scripts/projects/sync-core-to-project.sh` e testes correlatos). Não foram tocados
nem revertidos; o staging deste lote deve listar apenas os quatro arquivos da tabela acima, mais
`gestor/db/data/VariaveisData.json`, `gestor/db/data/schema-metadata.json` e os arquivos `sdd/`.
