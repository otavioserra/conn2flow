---
title: "Atualizações do sistema"
description: "Bootstrap, integridade do pacote, aplicação e etapas web."
section: concepts
order: 110
sources:
  - gestor/controladores/atualizacoes/atualizacoes-migracoes.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: 2d9a8c71
---

# Atualizações do sistema

`atualizacoes-sistema.php` aceita execução CLI e fluxo web. O modo completo baixa ou usa um artefato local, confere SHA-256 quando há arquivo de checksum, extrai para staging e valida arquivos críticos. Primeiro substitui o próprio script de atualização e o executa novamente na versão nova; depois aplica arquivos e atualiza o banco. No fluxo web, as etapas são `start`, `deploy`, `db` e `finalize`, vinculadas por um identificador de sessão.

As pastas `contents/`, `logs/`, `backups/`, `temp/` e `autenticacoes/` são protegidas da sobreposição de arquivos. `--dry-run` simula, `--backup` cria backup antes da alteração, `--only-files` e `--only-db` executam partes específicas; essas duas últimas são mutuamente exclusivas. O atualizador de banco aplica as regras declaradas em `schema-metadata.json`, incluindo preservação de campos alterados pelo usuário. Veja [recursos](resources.md).

Antes de mover o staging, as migrações do core que o artefato não traz mais saem de `db/migrations` (req-194, `atualizacoes-migracoes.php`). A pasta tem dois donos — core e projeto —, cada um com seu manifesto (`db/.c2f-migrations-core.json`, `db/.c2f-migrations-projeto.json`); a atualização do core nunca apaga migração do projeto e registra no log os choques de versão ou classe.

> [!WARNING]
> Executar só a etapa de arquivos pode deixar recursos SQL na versão antiga. Para mudanças de páginas ou layouts, conclua também atualização de banco e reconstrução do CSS derivado.
