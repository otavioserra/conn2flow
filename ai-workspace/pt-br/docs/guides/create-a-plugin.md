---
title: "Crie um plugin"
description: "Manifesto, recursos, build e instalação do plugin conforme o instalador."
section: guides
sources:
  - dev-plugins/templates/plugin/manifest.json
  - gestor/bibliotecas/plugins-installer.php
  - cli/src/Commands/PluginBuildCommand.php
  - cli/src/Commands/PluginResourcesCommand.php
verified_at: e5b61f8e
---

# Crie um plugin

O modelo de autoria está em `dev-plugins/templates/plugin/`. O pacote usa `manifest.json`, `modules/`, `resources/`, `db/data/` e `db/migrations/`. O instalador procura o manifesto na raiz, na subpasta `plugin/` ou recursivamente após extrair o ZIP. No manifesto, `id`, `name` e `version` são obrigatórios; `version` deve ter forma `x.y.z` e `id` aceita minúsculas, dígitos, hífen e sublinhado.

Crie módulos e recursos no pacote, mantendo pares de idioma necessários. Compile os recursos pelo fluxo do plugin e confira os `*Data.json` antes de empacotar. `php cli/c2f.php plugin:build private` chama o script de build local; `plugin:resources` aciona o compilador do plugin ativo. Confira [CLI de plugins](../reference/cli/plugin.md) para sintaxe.

A biblioteca `plugins-installer.php` obtém pacotes locais ou GitHub, calcula checksum SHA-256, valida manifesto, usa staging e substitui a pasta final. O fluxo pode fazer backup de uma instalação existente. Valide instalação e desinstalação em ambiente local com dados descartáveis; não presuma que um `plugin.json` usado por outros ecossistemas seja aceito aqui: o manifesto buscado é `manifest.json`.
