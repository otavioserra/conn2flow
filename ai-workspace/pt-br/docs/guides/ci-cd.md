---
title: "Entenda os releases no CI"
description: "Gatilhos, testes e artefatos dos workflows de release do Core e instalador."
section: guides
sources:
  - .github/workflows/release-gestor.yml
  - .github/workflows/release-instalador.yml
verified_at: e5b61f8e
---

# Entenda os releases no CI

Há dois workflows em `.github/workflows/`. `release-gestor.yml` reage a tags `gestor-v*` e despacho manual; `release-instalador.yml`, a `instalador-v*` e despacho manual. Ambos conferem README, CHANGELOG e versões antes de criar artefatos.

O release do gestor prepara PHP 8.4 e Node 22, instala dependências Composer, sobe MySQL 8 de serviço, roda migrações Phinx, PHPUnit, Vitest e Playwright. Depois remove fontes de recursos e dependências de desenvolvimento do pacote, cria `gestor.zip` e checksum. O release do instalador compacta `gestor-instalador/`, cria `instalador.zip` e `instalador.zip.sha256`. Ambos publicam o artefato como GitHub Release.

> [!IMPORTANT]
> Os dois workflows marcam seu release como `make_latest: true`. A URL genérica `releases/latest` pode apontar para qualquer uma das séries. Para instalar o produto, siga o [guia de instalação](installation.md), que filtra a série do instalador.

Não confunda publicação do release com deploy de projeto. O deploy é um comando separado, [`project:deploy`](../reference/cli/project.md).
