# Conn2Flow - CMS, Backend Framework, and Content Automation Platform

> 📖 **Available in multiple languages**: [🇧🇷 Portuguese](README-PT-BR.md) | 🇺🇸 English (this file)

Conn2Flow is a high-performance, modular Content Management System (CMS) built with PHP 8.3+, modern Tailwind CSS, and a decoupled architecture for multi-tenant deployments, visual page editing, and automated delivery. It is evolving into a **backend framework designed to be operated from inside the IDE** (VS Code, Antigravity, Cursor, Claude Code, Codex), giving humans and AI agents controlled access to the same content, operations, and validation contracts.

---

## Table of Contents

- [🧭 Vision: From CMS to Backend-for-Agents](#-vision-from-cms-to-backend-for-agents)
- [🆕 Latest Version](#-latest-version)
- [⚙️ System Features](#️-system-features)
- [📁 Repository Structure](#-repository-structure)
- [🤖 AI-Powered & Spec-Driven Development Methodology](#-ai-powered--spec-driven-development-methodology)
- [⚡ Quick Start & Installation](#-quick-start--installation)
- [📚 Technical Documentation & Knowledge Base](#-technical-documentation--knowledge-base)
- [🧪 Testing & Developer Commands](#-testing--developer-commands)
- [🗺️ Roadmap](#️-roadmap)
- [👥 Community, Support & License](#-community-support--license)

---

## 🧭 Vision: From CMS to AMS

Conn2Flow started as a Content Management System. It is becoming an **Agent
Management System (AMS)**: a backend where content operations — create, edit,
review, publish, deploy — are a surface shared between humans and AI agents.
The goal is not to manage autonomous agents for their own sake. It is to give
AI-assisted content work the controls a mature CMS already gives human editors:
identity, permission, scope, validation, and an audit trail.

- **Content as a controlled API surface**: `_api/` endpoints expose content,
  authentication, and RBAC workflows through scoped Personal Access Tokens
  (rate-limited, revocable, profile-restricted) — an agent authenticates like
  a user, never like a script holding a shared secret.
- **The `c2f` CLI is the automation contract**: a 30+ command binary that a
  human runs by hand and an agent dispatches through the [Conn2Flow AI
  Workspace](https://github.com/otavioserra/conn2flow-ai-workspace) MCP Hub.
- **Triad governance, not a single autonomous agent**: an Architect, an
  Executor, and a Reviewer share one source of truth (`sdd/`), so autonomy
  has explicit, auditable boundaries at every step.
- **One governance fleet, many repositories**: the same skill catalog and
  agent topology propagate — with independent local memory per repository —
  across the core, [Conn2Flow Nexus](https://github.com/otavioserra/conn2flow-nexus)
  (an emerging, vendor-agnostic AI Gateway), a full-stack mobile agent
  architecture, and every production project built on top of this core.

**Read the full vision**: [ai-workspace/en/docs/vision/](ai-workspace/en/docs/vision/README.md).

---

## 🔄 How It Works

1. **Create and govern content** in the visual CMS: pages, layouts, widgets,
  variables, media, forms, menus, publications, users, and permissions.
2. **Operate from the IDE** through the VS Code extension, the `c2f` CLI, or
  controlled `_api/` endpoints. A developer and an authorized AI workflow
  use the same system boundaries rather than bypassing the CMS database.
3. **Plan, execute, and review work** through the SDD Triad: an Architect
  defines intent, an Executor changes the smallest viable slice, and a
  Reviewer checks the evidence before delivery.
4. **Validate and deliver** using Docker, PHPUnit, Vitest, Playwright,
  resource synchronization, migrations, and CI/CD release workflows.

This makes Conn2Flow useful both as a full CMS for editors and as an
operational backend for teams building IDE-native, API-driven, AI-assisted
content workflows.

---

## 🆕 Latest Version

**v2.10.3 (September 2026)** *(Current base: v2.9.51)*

- **Tailwind Template Preview Cascading Fix**: Isolated competing legacy Fomantic CSS rules inside Tailwind live editor previews and preserved page baseline utility styles during section insertions (`req-154` / `BATCH-156`).
- **Resource Integrity Validation Fix**: Zeromanual checksums in `admin-cron` resource metadata ensuring deterministic automated computation during resource compilation (`req-153` / `BATCH-155`).
- **Cron Tasks Engine (`admin-cron`)**: Complete cron execution engine (`cron.php`) and administration CRUD for automated tasks (`cron_tarefas` table).

### Previous 2.10.x Releases

- **v2.10.2**: Windows TLS/SSL `--ssl-no-revoke` compatibility and preliminary cron integration.
- **v2.10.1**: Zero-prompt agent bootstrap, explicit repository identification, and streamlined root documentation.
- **v2.10.0**: Automatic Tailwind system dependencies, self-hosted external assets and Google Fonts, build-time asset minification, responsive administrative tables, hardened security, and Web Installer v2 compatibility.

For complete version details, see [CHANGELOG.md](CHANGELOG.md).

---

## ⚙️ System Features

### Content & Admin
- **Visual HTML Editor**: Floating toolbar (Editbar), live-DOM element mapping, drag-and-drop insertion, internal clipboard, and a CSS sidebar with a `getComputedStyle()` inspector.
- **Pages, Publications & Reusable Content**: Layouts, templates, components, variables, pages, and publication types assembled through a resource system that keeps authorship separate from generated runtime artifacts.
- **Widgets & Modules**: Galleries, hierarchical Menus, Forms, Publisher Index/Highlights, and Pages Index — each with public AJAX rendering and administrative CRUD.
- **Forms & Media**: Visual forms with submission handling and anti-spam controls, plus a physical file manager with directories, picker uploads, bulk selection, galleries, and media streaming.
- **SEO & Sitemap**: Per-page/publication metadata (Open Graph, meta description/keywords), automatic `sitemap.xml` and `robots.txt`.
- **Payment Gateways**: Native PayPal (transparent checkout, Card Fields) and Stripe (Payment Element, Billing, Webhooks) libraries.
- **Multi-Framework CSS**: Tailwind CSS v4 (per-resource compilation with authorship-vs-derived governance) alongside legacy Fomantic UI, chosen per layout/page.
- **Multi-Site & Plugins**: A domain-aware core with a plugin framework, development templates, and separate private/public plugin workflows.

### Agent & API Layer
- **Personal Access Tokens**: Scoped, rate-limited, revocable API tokens with 2FA recovery codes.
- **`_api/` Endpoints**: Programmatic access to supported content, authentication, and RBAC workflows — the same controlled surface a mobile or agent client consumes.
- **`c2f` CLI**: 30+ commands for resources, database, project synchronization, releases, Docker, and CI, dispatchable by IDE agents through the MCP Hub.
- **IDE Operations**: Conn2Flow Dev Tools brings SDD requests, Docker diagnostics, Manager/Project operations, releases, and AI Hub controls into VS Code.
- **HTML/JS Sanitization on Delivery**: Configurable gate (`HTML_SANITIZE`) that strips authoring comments before content reaches the public visitor.

### Development, Delivery & Safety
- **Docker Development Stack**: PHP 8.3 + Apache + MySQL 8.0, orchestrated via VS Code tasks.
- **Local-First Assets**: Third-party JS/CSS/fonts vendored to disk, with build-time minification — zero runtime CDN dependency.
- **Unified Test Suite**: PHPUnit (backend), Vitest (frontend components), and Playwright (E2E) wired into CI.
- **Web Installer v2**: Guided 4-step setup with concurrency-lock protection and automated server detection.
- **Release & Update Pipeline**: Versioned artifacts, integrity checks, migrations, resource synchronization, deterministic asset cache tokens, and atomic branch-and-tag publishing.

---

## 📁 Repository Structure

* **`gestor/`** — the core CMS: management features, resource compiler, plugin system, and automated updates.
* **`gestor-instalador/`** — the multilingual, automated web installer.
* **`cli/`** — the object-oriented `c2f` CLI subsystem.
* **`ai-workspace/`** — bilingual documentation, automation scripts, and the [Vision](ai-workspace/en/docs/vision/README.md) knowledge base.
* **`dev-plugins/`** — plugin development framework (templates, scripts, private/public plugin trees).
* **`dev-environment/`** — the Docker-based local development stack.
* **`sdd/`** — the Spec-Driven Development governance layer: specs, batches, decisions, and validation.
* **`tests/`** — root-level PHPUnit, Vitest, and Playwright suites.
* **`.github/`** — CI/CD workflows and the multi-agent (Copilot/Claude/Codex) customization layer.

---

## 🤖 AI-Powered & Spec-Driven Development Methodology

Conn2Flow is developed under a **Spec-Driven Development (SDD)** framework: every
change is anchored on `sdd/` as the single source of truth and flows through a
**Triad** of roles rather than a single autonomous agent —

- **Architect**: turns intent into normative specs, decision records, and formal requests (`sdd/human-requests/req-XXX.md`). Never commits code directly.
- **Executor**: implements the smallest reviewable slice, runs the tests, and records evidence in `sdd/implementation/` and `sdd/validation/`.
- **Reviewer**: audits the diff findings-first — spec drift, batch drift, missing validation — before a batch is considered closed.
- **Human-in-the-Loop**: directs the Architect and inspects the Executor's diff before anything is consolidated.

This same governance shape — plus a shared catalog of Core Skills (product and
infrastructure knowledge distilled into versioned, on-demand skill files) — is
propagated, with independent local memory, across every repository in the
Conn2Flow ecosystem via the [Conn2Flow AI Workspace](https://github.com/otavioserra/conn2flow-ai-workspace)
framework and its MCP Hub.

**Explore**: [ai-workspace/README.md](ai-workspace/README.md) for the full Double-Agent/Triad methodology, and [ai-workspace/en/docs/vision/02-triad-governance.md](ai-workspace/en/docs/vision/02-triad-governance.md) for the governance deep dive.

---

## ⚡ Quick Start & Installation

Conn2Flow features a modern automated web installer that prepares database schemas, seeds initial administration data, and generates encryption keys.

### 1. Download & Extract
Download the official `instalador-v2.1.2` distribution:

```bash
# Linux/macOS
curl -L -o instalador.zip https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.1.2/instalador.zip
unzip instalador.zip -d /path/to/webroot

# Windows (PowerShell)
Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.1.2/instalador.zip" -OutFile "instalador.zip"
Expand-Archive -Path "instalador.zip" -DestinationPath "C:\path\to\webroot"
```

### 2. Run the Web Installer
Navigate to `http://localhost/gestor-instalador/` (or your domain root) and follow the 4-step wizard:
1. **System & Requirements Verification**: PHP 8.1+, required extensions (PDO, OpenSSL, cURL, Zip).
2. **Database Configuration**: MySQL/MariaDB credentials with automated connection test.
3. **Paths & Security Setup**: Automatic root URL detection and RSA keypair generation.
4. **Initial Administrator Account**: Creation of master administrative credentials.

---

## 📚 Technical Documentation & Knowledge Base

Detailed architectural manuals and technical guides have been modularized into `ai-workspace/en/docs/`:

| Topic / Area | Documentation Guide |
|---|---|
| **Product Vision & Roadmap** | [Vision: From CMS to Backend-for-Agents](ai-workspace/en/docs/vision/README.md) |
| **Development & Repo Structure** | [CONN2FLOW-DEVELOPMENT-ENVIRONMENT.md](ai-workspace/en/docs/CONN2FLOW-DEVELOPMENT-ENVIRONMENT.md) |
| **Knowledge Base Index** | [Technical Documentation Catalog](ai-workspace/en/docs/README.md) |
| **Complete System Architecture** | [CONN2FLOW-MANAGER-DETAILS.md](ai-workspace/en/docs/CONN2FLOW-MANAGER-DETAILS.md) |
| **Docker Multi-Domain Setup** | [CONN2FLOW-DOCKER-ENVIRONMENT.md](ai-workspace/en/docs/CONN2FLOW-DOCKER-ENVIRONMENT.md) |
| **Database & Resource Engine** | [CONN2FLOW-RESOURCES-SYSTEM.md](ai-workspace/en/docs/CONN2FLOW-RESOURCES-SYSTEM.md) |
| **Plugin Framework Architecture** | [CONN2FLOW-PLUGIN-ARCHITECTURE.md](ai-workspace/en/docs/CONN2FLOW-PLUGIN-ARCHITECTURE.md) |
| **Spec-Driven Development (SDD)** | [CONN2FLOW-KNOWLEDGE-SYSTEM.md](ai-workspace/en/docs/CONN2FLOW-KNOWLEDGE-SYSTEM.md) |

---

## 🧪 Testing & Developer Commands

The project includes unit, integration, and E2E browser test suites:

```bash
# Run backend PHPUnit suite (libraries, controllers, migrations)
composer test

# Run frontend Vitest suite (JavaScript UI components)
npm run test

# Run Playwright end-to-end browser tests
npm run test:e2e

# Synchronize resources and local system files via CLI
./c2f manager:update-all
```

---

## �️ Roadmap

### ✅ Recently Completed
- Tailwind CSS v4 administrative and public layouts, available alongside the established Fomantic UI compatibility layer.
- Personal Access Tokens with scoped profiles, rate limiting, and 2FA recovery codes.
- SEO metadata, automatic sitemap/robots, and HTML/JS sanitization on delivery.
- Native PayPal and Stripe payment gateway libraries.
- Local-first third-party assets (zero runtime CDN) with build-time minification.
- The modern, 30+ command `c2f` CLI subsystem.

### 🚧 In Progress
- **[Conn2Flow Nexus](https://github.com/otavioserra/conn2flow-nexus)**: an emerging, vendor-agnostic AI Gateway microservice (Kafka + LiteLLM + LangGraph) that queues and routes agent work across LLM providers.
- **Mobile Agent Architecture**: a full-stack companion app that mirrors RBAC dynamically and clones administrative web modules into native screens.
- Broader `_api/` coverage for headless, agent-driven content operations.

### 🔮 Upcoming
- Enhanced plugin marketplace with discovery and one-click installation.
- Expanded multilingual authoring workflows.
- Performance/caching layer and public live-demo environments.

---

## 👥 Community, Support & License

- **GitHub Issues**: Report bugs or propose architectural improvements via GitHub Issues.
- **Professional Inquiries**: Connect with the creator on [LinkedIn](https://www.linkedin.com/in/otaviocserra/).
- **AI Workspace**: Explore agent collaboration prompts and methodology under [ai-workspace/](ai-workspace/README.md).
- **License**: Released under the open-source MIT License.
