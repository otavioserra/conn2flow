---
title: "Atualizações do sistema"
description: "Bootstrap, integridade do pacote, aplicação e etapas web."
section: concepts
order: 110
sources:
  - gestor/controladores/atualizacoes/atualizacoes-migracoes.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: eb96c5c7
---

# Atualizações do sistema

`atualizacoes-sistema.php` aceita execução CLI e fluxo web. O modo completo baixa ou usa um artefato local, confere SHA-256 quando há arquivo de checksum, extrai para staging e valida arquivos críticos. Primeiro substitui o próprio script de atualização e o executa novamente na versão nova; depois aplica arquivos e atualiza o banco. No fluxo web, as etapas são `start`, `deploy`, `db` e `finalize`, vinculadas por um identificador de sessão.

As pastas `contents/`, `logs/`, `backups/`, `temp/` e `autenticacoes/` são protegidas da sobreposição de arquivos. `--dry-run` simula, `--backup` cria backup antes da alteração, `--only-files` e `--only-db` executam partes específicas; essas duas últimas são mutuamente exclusivas. O atualizador de banco aplica as regras declaradas em `schema-metadata.json`, incluindo preservação de campos alterados pelo usuário. Veja [recursos](resources.md).

Antes de mover o staging, as migrações do core que o artefato não traz mais saem de `db/migrations` (req-194, `atualizacoes-migracoes.php`). A pasta tem dois donos — core e projeto —, cada um com seu manifesto (`db/.c2f-migrations-core.json`, `db/.c2f-migrations-projeto.json`); a atualização do core nunca apaga migração do projeto e registra no log os choques de versão ou classe.

Depois do banco, `db/` fica no lugar, no CLI e no web (req-197): a pasta tem as migrações dos dois donos e os manifestos acima.

**Trava de deploy (req-197).** Toda atualização, menos `--dry-run`, roda com a trava do ambiente em `temp/deploy.lock`, a mesma do deploy de projeto por API. Com outro deploy em execução, o CLI recusa com código de saída 8 e o web recusa o `start` com `locked`; a mensagem diz quem está com a trava. Trava vencida (processo que morreu) é assumida com aviso no log. No CLI, o processo do bootstrap pega a trava e passa o token ao filho (`--lock-token`), que a adota. No web, `start` pega e `finalize`/`cancel` liberam. `--backup` copia a instalação inteira para `backups/atualizacoes/full/<data>/`, sem as pastas protegidas, antes de mexer em qualquer arquivo.

> [!WARNING]
> Executar só a etapa de arquivos pode deixar recursos SQL na versão antiga. Para mudanças de páginas ou layouts, conclua também atualização de banco e reconstrução do CSS derivado.
