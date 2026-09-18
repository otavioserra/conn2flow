# BATCH-155 — Transporte SSH do pipeline de projeto e bootstrap CLI por host (req-153 / REQ-034)

- **Status**: implemented-pending-review
- **Intake**: `sdd/human-requests/req-153.md` e `conn2flow-site/sdd/human-requests/host-manager/req-034-...md`
- **Data**: 2026-09-02
- **Classificação**: pipeline de projeto, transporte de deploy, bootstrap CLI
- **Lote irmão**: `BATCH-027` no `conn2flow-site`

---

## 1. Diagnóstico causal

A migração do ambiente de desenvolvimento de Docker para **VM Ubuntu + HestiaCP**
(`192.168.1.108`) removeu o único destino que o pipeline sabia alcançar. `sync-core-to-project.sh`,
`synchronize-project.sh` e `updates-manager-database.sh` liam `devProjects.<id>.target` ou
`.path_tests` e escreviam num diretório do sistema de arquivos local. Para `conn2flow-site-local` o
Gestor publicado passou a viver em `/home/admin/web/conn2flow.local/conn2flow-gestor/` **dentro da
VM**, e nenhum caminho do Windows aponta para lá: o pipeline morria em
`Target path for project '<id>' not defined` antes de tocar em qualquer arquivo.

Dois defeitos independentes apareceram durante a homologação:

1. **`config.php` fixava `SERVER_NAME = 'localhost'` no ramo CLI**, descartando o host que o
   chamador já havia declarado. O efeito não era só o `.env`: o sufixo de cookie e sessão nasce de
   `basename($domainBase)`, então uma sessão gerada por CLI para `conn2flow.local` saía nomeada
   `_C2FCID_421AA90E` (md5 de `localhost`) enquanto o site lia `_C2FCID_05B11065`. O cookie chegava
   e era ignorado — redirecionamento para `/signin`, sem erro em lugar nenhum. É a mesma família
   que a req-032 corrigiu no ramo do cron, e sobreviveu no ramo genérico.
2. **`auth:cookie` exigia um `config.php` local**, o que impedia homologar qualquer rota
   autenticada de projeto publicado por SSH.

## 2. Implementação

### Transporte declarativo

Nova biblioteca `ai-workspace/en/scripts/lib/project-transport.sh`, fonte única da resolução:
`deploy_mode: "ssh"` mais `ssh_host`, `ssh_user`, `ssh_target_path` e as opcionais `ssh_port`,
`ssh_identity`, `ssh_sudo`, `ssh_chown`, `ssh_run_as`. Sem a chave — ou com `"local"` — nada muda:
`PT_RSYNC_OPTS` fica vazio e as linhas de rsync são exatamente as anteriores, preservando os modos
Docker e bare-metal da req-152.

Guardas que existem porque o destino é outra máquina:

- `ssh_target_path` relativo cairia no home da conta SSH; `/` alcançaria a raiz do convidado. Ambos
  recusados na resolução, não no servidor.
- `BatchMode=yes` e `ConnectTimeout`: sem eles, chave ausente vira prompt de senha e o pipeline fica
  pendurado até o timeout do chamador.
- `project_transport_remote_exec` cita cada argumento com `printf %q`. O `ssh` concatena e o servidor
  entrega ao shell dele — interpolar a linha inteira faria qualquer valor do `environment.json`
  virar comando remoto.
- `--rsync-path="sudo rsync"` mais `chown` de encerramento: a conta SSH não é dona do docroot, e sem
  a devolução de posse os arquivos novos ficam `root:root` e o pool PHP-FPM do tenant perde a leitura.

`updates-manager-database.sh` ganhou `EXECUTION_MODE="ssh"` ao lado de `docker` e `host`, com o
caminho do PHP **relativo** à raiz do Gestor remoto, onde o `cd` do transporte já colocou o processo
(contrato de bootstrap da req-152).

### CLI

- `ProjectEnvironmentResolver` publica `deployMode` e `ssh` (usuário, host, porta, caminho, `runAs`,
  `sudo`), com erro explícito quando `deploy_mode: "ssh"` vem sem endereço.
- `CssRebuildCommand` reconhece o projeto SSH e diz que o Gestor está na VM, em vez de deixar o
  agente PHP falhar adiante com `ERRO: .env do gestor nao encontrado` — que aponta para um arquivo
  ausente quando o que falta é o transporte.
- `AuthCookieCommand` ganhou `generateOverSsh()`: mesma coreografia do caminho Docker (copia o
  gerador, executa, lê o JSON de volta) trocando `docker cp`/`docker exec` por `scp`/`ssh`, sob o
  usuário dono do docroot. O gerador e o JSON carregam credencial de sessão e são removidos da VM
  no `finally`.

### Bootstrap

`gestor/config.php`: no ramo CLI, `localhost` voltou a ser **padrão** e não imposição — só é aplicado
quando o chamador não declarou `SERVER_NAME`.

### req-153

`gestor/modulos/admin-cron/admin-cron.json` com `checksum.html` e `checksum.combined` zerados.
**Ver a ressalva na seção 4**: a causa registrada no intake não se confirmou.

## 3. Evidências

### Automatizadas

- `bash -n` nos quatro scripts: aprovado.
- Novo `tests/Unit/PHP/ProjectSshDeployReq034Test.php`: **19/19**, 64 asserções.
- PHPUnit completa: **1071/1071**, 4.717 asserções, 4 skips esperados, **0 falhas**.
- `php -l` em `config.php`, `ProjectEnvironmentResolver.php`, `CssRebuildCommand.php` e
  `AuthCookieCommand.php`: aprovado.

### Runtime no Lab HestiaCP (`conn2flow-site-local` → `192.168.1.108`)

- `project:update-all` executado duas vezes, etapas 1 a 5 em `SUCCESS`:
  core sincronizado (17 MB), banco `+166 ~247 =2434` e depois `+5 ~6 =2952` com `TRANSACAO_COMMIT`,
  recursos, arquivos e validação final.
- `auth:cookie --project=conn2flow-site-local` gerando sessão pela VM, com o sufixo de cookie
  correto (`_C2FCID_05B11065`) após a correção do bootstrap.
- Etapas 6 e 8 (`css:rebuild`, `assets:publish`) avisam e o pipeline segue — limite conhecido do
  transporte, registrado na seção 4.

## 4. Ressalvas para o Arquiteto

1. **A causa registrada na req-153 não se confirmou.** O checksum não foi escrito à mão:
   `md5_file(admin-cron.html)` é exatamente `387ee81b1f9dd115a8d96c7ca2b92d72`, o valor que o teste
   rejeita. Quem o grava é `atualizacao-dados-recursos.php`, que registra `ORIGIN_UPDATE_MODULE` e
   documenta os checksums em origem como "histórico incremental" para o versionamento. O campo foi
   zerado conforme pedido e o CI passa, mas **qualquer `project:update-all` ou `manager:update-all`
   o preenche de novo** — o teste e o compilador estão em conflito direto. O invariante que o teste
   diz proteger ("não pode ser escrito à mão") seria melhor expresso como "o checksum precisa
   coincidir com o md5 do HTML real". Isso muda um critério de aceite e não foi aplicado
   unilateralmente.
2. **`css:rebuild` e `assets:publish` não alcançam a VM.** Ambos operam sobre um Gestor em disco
   local. O CSS derivado de recurso editado online fica stale até alguém rodar o comando na VM,
   onde hoje não há `tailwindcss` nem `terser` instalados.

## 5. Arquivos tocados

- `ai-workspace/en/scripts/lib/project-transport.sh` (novo)
- `ai-workspace/en/scripts/projects/sync-core-to-project.sh`
- `ai-workspace/en/scripts/projects/synchronize-project.sh`
- `ai-workspace/en/scripts/dev-environment/updates-manager-database.sh`
- `cli/src/Support/ProjectEnvironmentResolver.php`
- `cli/src/Commands/CssRebuildCommand.php`
- `cli/src/Commands/AuthCookieCommand.php`
- `gestor/config.php`
- `gestor/modulos/admin-cron/admin-cron.json`
- `tests/Unit/PHP/ProjectSshDeployReq034Test.php` (novo)
- `.claude/`, `.cursor/`, `.gemini/`, `.codex/` e `.github/skills/`: `c2f-dev-scripts`,
  `c2f-docker-environment`, `c2f-environment-configuration`, `c2f-shell-and-windows-traps`
