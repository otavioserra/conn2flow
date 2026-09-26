---
title: "Understand CI releases"
description: "Triggers, tests and artifacts in the Core and installer release workflows."
section: guides
sources:
  - .github/workflows/release-gestor.yml
  - .github/workflows/release-instalador.yml
verified_at: e5b61f8e
---

# Understand CI releases

Two workflows live in `.github/workflows/`. `release-gestor.yml` reacts to `gestor-v*` tags and manual dispatch; `release-instalador.yml` reacts to `instalador-v*` and manual dispatch. Both check README, CHANGELOG and matching versions before creating artifacts.

The manager release sets up PHP 8.4 and Node 22, installs Composer dependencies, starts a MySQL 8 service, runs Phinx migrations, PHPUnit, Vitest and Playwright. It then removes source resources and development dependencies from the package, creates `gestor.zip` and its checksum. The installer release archives `gestor-instalador/`, creates `instalador.zip` and `instalador.zip.sha256`. Both publish artifacts as GitHub Releases.

> [!IMPORTANT]
> Both workflows set `make_latest: true`. The generic `releases/latest` URL may point to either series. Follow the [installation guide](installation.md), which filters the installer series.

Releasing is separate from project deployment. Deployment has its own [`project:deploy` command](../reference/cli/project.md).
