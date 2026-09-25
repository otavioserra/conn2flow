# BATCH-166 — Disparo desacoplado do "Disparar agora" no Admin Cron (REQ-039, Pilar 5)

- **Status**: implementado-validado-aguardando-homologacao
- **Intake**: `conn2flow-site` → `sdd/human-requests/host-manager/req-039-correcoes-sso-perfil-admin-crud-servidor-e-restart-fpm.md`
- **Data de abertura**: 2026-09-03
- **Classificação**: resiliência de infraestrutura / bugfix
- **Modo de autonomia**: supervisionado
- **Lote irmão**: `BATCH-032` no `conn2flow-site` (Pilares 1, 2, 3, 4, 6 e 7)

## Objetivo

Impedir que uma rotina agendada que reinicia serviços do sistema derrube o próprio worker web que
a disparou.

## Diagnóstico

A esteira de provisionamento do HestiaCP chama `systemctl restart php8.5-fpm`. Disparada pelo botão
"Disparar agora" do `/admin-cron/`, ela roda **dentro do pool FPM**: a reinicialização mata o worker
que atende a requisição, o nginx devolve `502 Bad Gateway` e o provisionamento morre no meio,
deixando um tenant parcialmente criado no painel sem ninguém para desfazê-lo.

O caminho agendado nunca teve o problema — o tick do cron já roda no CLI. O defeito é exclusivo do
disparo manual pela tela.

## Implementação

`admin_cron_ajax_disparar()` passou a consultar `admin_cron_tarefa_desacoplada()` antes de executar.
Quando a tarefa pede desacoplamento, ela é iniciada como processo CLI independente e a resposta
JSON `Ok` volta imediatamente para o navegador.

As funções de decisão e execução foram extraídas para
`gestor/modulos/admin-cron/includes/admin-cron-dispatch.php`, um include **sem efeito colateral**.
Não é organização cosmética: `admin-cron.php` termina em `admin_cron_start()`, que abre a interface
— qualquer `require` dele para alcançar as funções dispara a renderização do painel (foi
exatamente o que o primeiro teste deste lote provocou: `Call to undefined function hook_do_action()`
a partir de `interface_finalizar()`). É a mesma doutrina que levou o Host Manager a extrair o
domínio dele no BATCH-028.

## Decisões tomadas na implementação

1. **A decisão vem de DECLARAÇÃO, não de lista fixa no núcleo.** A REQ nomeava
   `host-manager-provisionamento`, mas quem sabe que uma rotina reinicia serviço é o módulo dono
   dela — e essa tarefa vive no repositório do site. O núcleo lê
   `parametros.execucao = "desacoplada"` (ou `parametros.background`). Um teste guarda a ausência
   do nome da tarefa no código do núcleo.
2. **O manifesto do módulo vale como segunda fonte.** `parametros` só é ressincronizado quando
   `user_modified` está vazio (regra D-036): basta o operador ter pausado a tarefa **uma vez** para
   congelar a versão antiga e reexpor o 502 em silêncio. Como a declaração aqui é de **segurança**,
   `admin_cron_parametros_do_manifesto()` relê `modulos/<modulo>/<modulo>.json` de forma pontual
   (pelo campo `modulo` da própria linha, sem varrer o diretório) e o nome do módulo é validado por
   regex antes de virar caminho.
3. **`setsid` é a peça central, não `nohup`.** Sem uma sessão nova, o filho continua no grupo de
   processos do pool e o `systemctl restart php8.5-fpm` o mata junto com o pai — exatamente o que
   este caminho existe para evitar. O `&` faz o `sh -c` retornar de imediato, então `proc_close()`
   não bloqueia a resposta.
4. **`PHP_BINARY` não serve.** Sob PHP-FPM ele aponta para o binário do **pool** (`php-fpm`), e o
   cron rodaria sob o SAPI errado. A resolução é `config.cron_php_binary` → `PHP_BINDIR . '/php'`
   executável → `PHP_BINARY` apenas se o processo já for CLI → `php` no PATH.
5. **Nenhum resultado é gravado no caminho desacoplado.** Quem registra duração e status é o
   processo CLI ao terminar; escrever um placeholder agora sobrescreveria o resultado real. A
   mensagem devolvida (`msg-run-detached`) diz ao operador que a listagem atualiza no fim.
6. **A indisponibilidade do CLI degrada, não recusa.** Windows, `proc_open` bloqueado ou `cron.php`
   ausente caem no caminho síncrono anterior, com o motivo registrado em log. Recusar o disparo
   seria uma regressão em ambientes onde ele funcionava.

## Arquivos tocados

| Arquivo | Mudança |
| --- | --- |
| `gestor/modulos/admin-cron/admin-cron.php` | Ramo desacoplado em `admin_cron_ajax_disparar()` + `require` do include |
| `gestor/modulos/admin-cron/includes/admin-cron-dispatch.php` | **Novo** — decisão e execução desacoplada (6 funções) |
| `gestor/modulos/admin-cron/admin-cron.json` | Variável `msg-run-detached` (`pt-br` e `en`) |
| `tests/Unit/PHP/AdminCronReq039Test.php` | **Novo** — 13 testes |

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `php -l` nos 2 arquivos PHP tocados | OK |
| `AdminCronReq039Test` | 13/13, 24 asserções |
| Suíte PHPUnit completa | 1.142/1.142, 7.690 asserções, 4 skipped |
| Suíte Vitest completa | 417/417 |
| `c2f resources:sync` | 2.846 recursos, 0 problemas |

### Guarda validada por mutação

| Mutação aplicada | Teste que acusou |
| --- | --- |
| Fallback de manifesto removido | `testManifestoDoModuloValeQuandoOBancoEstaDesatualizado` |

## Pendências

- Homologação na VM Lab: disparar `host-manager-provisionamento` pelo `/admin-cron/` e confirmar
  resposta imediata sem `502`, com o resultado aparecendo na listagem ao término do processo CLI.
- O comportamento só é observável com o `BATCH-032` do `conn2flow-site` implantado: é ele que
  declara `execucao: "desacoplada"` na tarefa.

## Nota de concorrência

Durante este lote, outro agente alterou em paralelo, na mesma árvore, arquivos fora do escopo
(`cli/src/Commands/CssRebuildCommand.php`, `gestor/controladores/agents/arquitetura/*.php`,
`ai-workspace/en/scripts/projects/sync-core-to-project.sh` e três testes). Não foram tocados nem
revertidos. O staging deste lote deve listar explicitamente apenas os quatro arquivos da tabela
acima, mais `gestor/db/data/VariaveisData.json` e `gestor/db/data/schema-metadata.json` (derivados
do `resources:sync`).
