---
title: "CLI: Assets"
description: "Comandos c2f da família assets, argumentos e opções declarados no código."
section: reference
order: 30
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/AssetsFontsCommand.php
  - cli/src/Commands/AssetsMinifyCommand.php
  - cli/src/Commands/AssetsPublishCommand.php
  - cli/src/Commands/AssetsVendorCommand.php
verified_at: e5b61f8e
---

# CLI: Assets

A família `assets:*` tem 4 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `assets:fonts`

Código: `cli/src/Commands/AssetsFontsCommand.php`.

```text
Usage: c2f assets:fonts --project=ID [--url=CSS_URL] [--subsets=latin,latin-ext] [--listar]

Downloads the Google Fonts families a project declares and rewrites them as a local
stylesheet under contents/project/fonts/, so no visitor request reaches Google.

Options:
  --project=ID  Project whose resources are scanned (and where files are written).
  --url=URL     Use this Google Fonts CSS URL instead of scanning the resources.
  --subsets=..  Comma-separated subsets to keep (default: latin,latin-ext).
  --todos       Keep every subset, including cyrillic/greek/vietnamese.
  --listar      Report what would be downloaded, without writing anything.
```

## `assets:minify`

Código: `cli/src/Commands/AssetsMinifyCommand.php`.

```text
Usage: c2f assets:minify [--verificar] [--forcar] [--listar]

Generates <name>.min.js next to each own JavaScript file and records provenance in
gestor/assets/minify-manifest.json. The static controller prefers the minified file
when DEVELOPMENT_ENV is false.

Options:
  --verificar  Only report which derivatives are stale (exit 1 if any). Writes nothing.
  --forcar     Regenerate every derivative, even the ones already current.
  --listar     Show what would be processed, without writing anything.
```

## `assets:publish`

Código: `cli/src/Commands/AssetsPublishCommand.php`.

```text
Usage: c2f assets:publish [--project=ID] [--public=PATH] [--dev] [--clean] [--dry-run]

Copies the core and module assets to <public>/dist/, keeping the published path
identical to the public URL, and writes <public>/dist/.manifest.json with the SHA-1
of every published file (used as the cache-busting token in the HTML tags).

Options:
  --project=ID   Resolve the DocumentRoot from environment.json (devProjects). With
                 deploy_mode="ssh", publishes locally and rsyncs into ssh_public_path.
  --public=PATH  DocumentRoot to publish into. Defaults to PUBLIC_PATH in gestor/.env.
  --dev          Publish the authored JavaScript instead of the .min.js derivative.
  --clean        Remove files under dist/ that are no longer in the manifest.
  --dry-run      Report what would be published, writing nothing.
  --opcional     Used by pipelines: a missing DocumentRoot is reported, not an error.
  --confirmar-remoto  Required to push dist/ to a VM over SSH.
  --simular-remoto    Print the rsync command line without executing it.
```

## `assets:vendor`

Código: `cli/src/Commands/AssetsVendorCommand.php`.

```text
Usage: c2f assets:vendor [--lib=NAME] [--forcar] [--listar]

Downloads every file declared in assets_externos_registro() into
gestor/assets/vendor/<lib>/<version>/. Existing files are kept unless --forcar.

Options:
  --lib=NAME  Only this library (default: all registered).
  --forcar    Re-download files that already exist.
  --listar    Show what would be downloaded, without writing anything.
```
