# Conn2Flow

**An open-source PHP platform for building and operating digital products with AI — and an Agent Management System (AMS) for the agents that help you do it.**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Release](https://img.shields.io/badge/release-v2.10.13-1daac6.svg)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](ai-workspace/en/docs/guides/installation.md)

🇺🇸 English · [🇧🇷 Português](README-PT-BR.md) · [Website](https://conn2flow.com/) · [Platform](https://conn2flow.com/plataforma/) · [Conn2Flow Pro](https://conn2flow.com/pro/)

---

## What it is

Conn2Flow started as a CMS. Today it is the base on which sites, stores, subscription products, customer portals and documentation are built, deployed and updated — by people and by AI agents working under the same rules.

Three things make it different from a CMS with an AI plugin:

- **Everything is a resource.** Pages, layouts, components, templates, variables, AI prompts and modes live as files in the repository, are compiled, and are synchronised to the database. What an agent writes is reviewable in a diff before it reaches production.
- **One automation contract.** The `c2f` CLI (50+ commands) is what a developer runs by hand and what an agent dispatches. The `/_api/` endpoints expose the same operations to remote clients, behind personal access tokens.
- **Governance is part of the product.** Work is specified, executed and reviewed through versioned artefacts in `sdd/`, with a catalogue of skills shared by five agent tools. Autonomy has explicit, auditable limits.

That last point is what we call an **Agent Management System**: not a place to run autonomous agents for their own sake, but the controls a mature CMS already gives human editors — identity, permission, scope, validation and an audit trail — extended to AI-assisted work.

## What is inside

| Area | What you get |
|---|---|
| **Content** | Visual HTML editor with a live editing bar, pages, layouts, components, templates, variables, menus, galleries, forms, publications with searchable indexes, SEO metadata, sitemap |
| **Agents and AI** | AI library with modes and prompts as resources, an AI assistant in the editor, skill catalogue for Claude Code, Codex, Cursor, Gemini and GitHub Copilot, Spec-Driven Development workflow |
| **API** | `/_api/` endpoints for authentication, OAuth, projects, system updates and modules, with scoped, revocable personal access tokens |
| **CLI** | `c2f`: resources, CSS, assets, database, projects, deploy, updates with rollback, documentation, Docker, page inspection |
| **Delivery** | Project pipeline, deploy lock, per-layer manifest, snapshot, health check and automatic rollback, file-conflict resolution in the panel, the API and the CLI |
| **Front end** | Tailwind CSS v4 compiled per resource, alongside Fomantic UI; third-party assets served from disk, with build-time minification |
| **Security** | CSRF protection with silent token renewal, 2FA, session management, access lockout with a stated deadline, reCAPTCHA and Turnstile, restricted-access mode |
| **Payments** | Stripe (Payment Element, subscriptions, webhooks) and PayPal libraries |
| **Extensibility** | 30+ core modules, 40+ libraries, hooks, widgets, plugins, multi-language, projects that override the core without forking it |

## How people and agents work on it

```text
        intent                 smallest slice               evidence
  ┌──────────────┐        ┌───────────────────┐       ┌────────────────┐
  │  Architect   │ ─────▶ │     Executor      │ ────▶ │    Reviewer    │
  │ specs, reqs  │        │ code, tests, docs │       │ findings first │
  └──────────────┘        └───────────────────┘       └────────────────┘
          ▲                         │                          │
          └──────── human in the loop: approves, redirects ────┘
```

- `sdd/` holds the requests, batches, decisions and validation records. It is the single source of truth for what was asked and what was proven.
- `.claude/`, `.codex/`, `.cursor/`, `.gemini/` and `.github/` carry the same skills, so every agent tool starts with the same knowledge of the product.
- Three autonomy levels — supervised, monitored and headless — decide how far an agent goes before a human looks.
- Agents reach a running installation the way a user does: through the CLI, or through the API with a token that can be scoped and revoked.

The process is a rule of the project, not a guarantee enforced by the PHP runtime. Read [the vision](ai-workspace/en/docs/concepts/vision.md) and [AI security](ai-workspace/en/docs/concepts/ai-security.md) for what is implemented.

## Quick start

**Install on a server** (PHP 8.1+, MySQL or MariaDB, Apache or Nginx):

```bash
# Finds the latest installer release, whatever its version
TAG=$(curl -s "https://api.github.com/repos/otavioserra/conn2flow/releases?per_page=100"   | grep -o '"tag_name": *"instalador-v[^"]*"' | head -1 | cut -d'"' -f4)
curl -L -o instalador.zip "https://github.com/otavioserra/conn2flow/releases/download/$TAG/instalador.zip"
unzip instalador.zip -d /path/to/webroot
```

Prefer a button? The [Start building](https://conn2flow.com/en/comece-a-construir/) page always links to the latest installer.

Open the site in the browser and follow the four steps: requirements, database, paths and keys, administrator account. Details in the [installation guide](ai-workspace/en/docs/guides/installation.md).

**Develop locally:**

```bash
git clone https://github.com/otavioserra/conn2flow.git
cd conn2flow
./c2f help                 # every command, with aliases
./c2f manager:update-all   # core → resources → files → database
composer test              # PHPUnit
npm run test               # Vitest
```

The Docker stack and the VS Code tasks are described in the [development environment guide](ai-workspace/en/docs/guides/development-environment.md).

## Documentation

The documentation is Markdown, versioned with the code, checked against it and rebuilt by `c2f docs:build`. Every page records the commit it was verified at.

| If you want to | Start here |
|---|---|
| Understand the system | [Architecture](ai-workspace/en/docs/concepts/architecture.md) · [Resources](ai-workspace/en/docs/concepts/resources.md) · [Vision](ai-workspace/en/docs/concepts/vision.md) |
| Install and run it | [Installation](ai-workspace/en/docs/guides/installation.md) · [Development environment](ai-workspace/en/docs/guides/development-environment.md) |
| Build on it | [Create a module](ai-workspace/en/docs/guides/create-a-module.md) · [Create a plugin](ai-workspace/en/docs/guides/create-a-plugin.md) · [Deploy a project](ai-workspace/en/docs/guides/deploy-a-project.md) |
| Automate it | [CLI reference](ai-workspace/en/docs/reference/cli/index.md) · [API reference](ai-workspace/en/docs/reference/api/index.md) |
| Look something up | [Libraries](ai-workspace/en/docs/reference/libraries/index.md) · [Modules](ai-workspace/en/docs/reference/modules/) |
| Update safely | [System updates](ai-workspace/en/docs/concepts/system-updates.md) |
| See what changed | [What's new](ai-workspace/en/docs/whats-new/index.md) · [Changelog](CHANGELOG.md) |

Full index: [ai-workspace/en/docs/](ai-workspace/en/docs/index.md).

## Repository map

| Folder | Contents |
|---|---|
| `gestor/` | The core: router, libraries, modules, resources, migrations |
| `gestor-instalador/` | The web installer |
| `cli/` | The `c2f` command line |
| `ai-workspace/` | Documentation in English and Portuguese, and automation scripts |
| `sdd/` | Requests, batches, decisions and validation records |
| `dev-environment/` | The Docker development stack |
| `dev-plugins/` | Plugin templates and tooling |
| `tests/` | PHPUnit, Vitest and Playwright suites |

## Ecosystem

- **[conn2flow.com](https://conn2flow.com/)** — the project site, built on Conn2Flow itself. See the [platform overview](https://conn2flow.com/plataforma/).
- **[Conn2Flow Pro](https://conn2flow.com/pro/)** — managed hosting and plans for those who want the platform without running the servers.
- **[Conn2Flow AI Workspace](https://github.com/otavioserra/conn2flow-ai-workspace)** — the framework that carries the skills and the governance to every repository built on the core.
- **[Conn2Flow Nexus](https://github.com/otavioserra/conn2flow-nexus)** — an AI gateway in development. It is a direction, not a dependency: nothing in this repository requires it.

## Status

Current release: **v2.10.13**. Recent work, in the order it landed:

- System update through the API, in the background, with status and rollback
- File-conflict resolution, one decision per file, in the panel, the API and the CLI
- Safe update: deploy lock, snapshot, health check, automatic rollback
- Home page and layout per user profile
- Documentation rewritten from the code, with audit and build commands
- Silent CSRF token renewal

Details per version in [What's new](ai-workspace/en/docs/whats-new/index.md).

## Contributing and license

Bugs and proposals are welcome in [GitHub Issues](https://github.com/otavioserra/conn2flow/issues). Before a pull request, read how the [documentation](ai-workspace/en/docs/guides/documentation.md) and the `sdd/` workflow are kept, so the change arrives with its evidence.

Released under the [MIT License](LICENSE).
