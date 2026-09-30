---
title: "Atualizações do sistema"
description: "Bootstrap, integridade do pacote, aplicação e etapas web."
section: concepts
order: 110
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
  - gestor/modulos/admin-atualizacoes/admin-atualizacoes.php
  - gestor/controladores/atualizacoes/atualizacoes-migracoes.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: c8db1447
---

# Atualizações do sistema

`atualizacoes-sistema.php` aceita execução CLI e fluxo web. O modo completo baixa ou usa um artefato local, confere SHA-256 quando há arquivo de checksum, extrai para staging e valida arquivos críticos. Primeiro substitui o próprio script de atualização e o executa novamente na versão nova; depois aplica arquivos e atualiza o banco. No fluxo web, as etapas são `start`, `deploy`, `db` e `finalize`, vinculadas por um identificador de sessão.

As pastas `contents/`, `logs/`, `backups/`, `temp/` e `autenticacoes/` são protegidas da sobreposição de arquivos. `--dry-run` simula, `--backup` cria backup antes da alteração, `--only-files` e `--only-db` executam partes específicas; essas duas últimas são mutuamente exclusivas. O atualizador de banco aplica as regras declaradas em `schema-metadata.json`, incluindo preservação de campos alterados pelo usuário. Veja [recursos](resources.md).

Antes de mover o staging, as migrações do core que o artefato não traz mais saem de `db/migrations` (req-194, `atualizacoes-migracoes.php`). A pasta tem dois donos — core e projeto —, cada um com seu manifesto (`db/.c2f-migrations-core.json`, `db/.c2f-migrations-projeto.json`); a atualização do core nunca apaga migração do projeto e registra no log os choques de versão ou classe.

Depois do banco, `db/` fica no lugar, no CLI e no web (req-197): a pasta tem as migrações dos dois donos e os manifestos acima.

**Trava de deploy (req-197).** Toda atualização, menos `--dry-run`, roda com a trava do ambiente em `temp/deploy.lock`, a mesma do deploy de projeto por API. Com outro deploy em execução, o CLI recusa com código de saída 8 e o web recusa o `start` com `locked`; a mensagem diz quem está com a trava. Trava vencida (processo que morreu) é assumida com aviso no log. No CLI, o processo do bootstrap pega a trava e passa o token ao filho (`--lock-token`), que a adota. No web, `start` pega e `finalize`/`cancel` liberam. `--backup` copia a instalação inteira para `backups/atualizacoes/full/<data>/`, sem as pastas protegidas, antes de mexer em qualquer arquivo.

**Camadas e choques (req-198).** Cada entrega grava o que entregou em `installation/manifests/<camada>.json` (caminho e sha256), e a precedência é `projeto` > `plugin:<id>` > `core`. Antes de mover o staging, a atualização do core:

- **não sobrescreve** um arquivo que o projeto ou um plugin sobrepõe: a versão nova vai para `backups/overrides/<versão>/` e vira choque `sobreposto`;
- **preserva** um arquivo mudado no servidor sem que nenhuma camada tenha entregue aquele conteúdo (choque `editado`);
- **retira** o que o core deixou de entregar, se estiver intacto; editado, vira choque `retirado-editado`.

A primeira entrega, sem manifesto, se comporta como antes e grava a linha de base. Os choques ficam em `installation/choques/` e, depois da etapa de banco, na tabela `atualizacoes_choques` (o mesmo choque ainda pendente não vira outra linha a cada atualização); a aba "Choques das entregas" do `admin-atualizacoes` mostra a lista e o diff, e no detalhe fica a decisão por arquivo: sobrescrever, manter ou mesclar (req-199; também pela API e por `c2f update:conflicts` / `update:resolve`). "Manter" vira regra para as próximas entregas do mesmo arquivo, e a camada que entrega o mesmo conteúdo de antes não gera choque. `installation/` é pasta protegida.

**Snapshot, verificação e rollback (req-198).** Antes de aplicar, a atualização guarda em `backups/atualizacoes/snapshots/exec-<id>/`:
- só os arquivos que vão ser sobrescritos ou removidos, a lista dos novos e os manifestos;
- o dump do banco (`banco.sql.gz`, pelo `mysqldump`), antes da etapa de banco.

Depois, verifica se há erro fatal novo no `logs/php-error.log` e se a raiz do site responde abaixo de 500:
- a requisição vai para `https://<domínio>/`, pelo DNS normal e, se não conectar, por `127.0.0.1`;
- `--health-url=<url>` e `--health-ip=<ip>` (ou `ATUALIZACOES_SAUDE_URL` e `ATUALIZACOES_SAUDE_IP` no `.env`) apontam outro endereço, por exemplo um nginx numa porta interna;
- sem conexão em nenhuma tentativa, o HTTP fica como aviso e não reprova (o servidor pode escutar só no IP público);
- espera 3 s antes da requisição, porque o OPcache do PHP-FPM ainda serve o código antigo por alguns segundos.
 Se a verificação falhar, os arquivos voltam sozinhos (código de saída 6). O banco só volta com decisão do operador: `--rollback=exec-<id> --com-banco`. `--rollback=exec-<id>` sozinho volta só os arquivos; `--no-health` e `--no-rollback` desligam a verificação ou a volta automática. Ficam os 5 snapshots mais recentes. O deploy de projeto por API faz o mesmo (snapshots `api-…`), e o rollback também pode ser disparado da máquina de desenvolvimento com [`c2f update:rollback`](../reference/cli/update.md) ou por `POST /_api/project/rollback` (BATCH-204). A atualização completa também pode ser disparada pela API, em segundo plano, com as mesmas opções e acompanhamento por `run-status` ([API do sistema](../reference/api/system.md), req-201).

> [!WARNING]
> Executar só a etapa de arquivos pode deixar recursos SQL na versão antiga. Para mudanças de páginas ou layouts, conclua também atualização de banco e reconstrução do CSS derivado.
