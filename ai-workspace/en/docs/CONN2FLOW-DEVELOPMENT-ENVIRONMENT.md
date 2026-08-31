# Conn2Flow - Development Environment & Repository Architecture

This technical guide provides comprehensive documentation of the Conn2Flow development environment, complete directory tree, VS Code integration, automated testing suite, and core subsystems.

---

## Table of Contents

- [Repository Directory Structure](#repository-directory-structure)
- [Complete Development Environment](#complete-development-environment)
- [Quick Start for Developers](#quick-start-for-developers)
- [Automated Test Suite](#automated-test-suite)
- [VS Code Pre-configured Tasks](#vs-code-pre-configured-tasks)
- [System Update Mechanism](#system-update-mechanism)
- [File Ownership & Permissions](#file-ownership--permissions)
- [Plugin Development Framework](#plugin-development-framework)

---

## Repository Directory Structure

```
conn2flow/
├── gestor/                 # Main CMS core system
│   ├── assets/             # Global CSS, JS, vendor libraries and fonts
│   ├── autenticacoes/      # Domain-specific and host configurations
│   ├── bibliotecas/        # Core PHP libraries (gestor, interface, banco, etc.)
│   ├── controladores/      # MVC controllers and maintenance scripts
│   ├── db/                 # Phinx migrations, data seeders and schemas
│   ├── modulos/            # System modules (admin-paginas, publisher, etc.)
│   ├── public-access/      # Public entry points and router
│   └── vendor/             # Composer backend dependencies
│
├── gestor-instalador/      # Web-based automated installer
│   ├── assets/             # Installer CSS, JS and icons
│   ├── lang/               # Multilingual translations (pt-br, en)
│   ├── src/                # Installer business logic and checks
│   └── views/              # Installation wizard templates
│
├── ai-workspace/           # AI development environment & knowledge base
│   ├── en/                 # English documentation, scripts, prompts
│   │   ├── docs/           # Technical specification documents
│   │   ├── prompts/        # Standardized prompt templates
│   │   └── scripts/        # Automation and dev environment scripts
│   └── pt-br/              # Portuguese documentation, scripts, prompts
│
├── dev-plugins/            # Plugin development framework
│   ├── plugins/            # Active plugin development (private/public)
│   ├── templates/          # Boilerplate plugin templates & skeletons
│   └── tests/              # Plugin test environments
│
├── tests/                  # Root-level automated test suite
│   ├── E2E/                # Playwright end-to-end browser tests
│   ├── Integration/        # Phinx migrations and integration tests
│   └── Unit/               # Unit tests (PHPUnit for PHP, Vitest for JS)
│
├── sdd/                    # Spec-Driven Development (SDD/STD)
│   ├── human-requests/     # Human intake requests (req-XXX.md)
│   ├── implementation/     # Batch implementation logs (BATCH-XXX.md)
│   ├── decisions/          # Architectural Decision Records (DECISION-LOG.md)
│   └── validation/         # Validation checklists & test evidence
│
├── dev-environment/        # Docker development stack
│   ├── docker/             # Dockerfile and compose configurations
│   └── data/               # Configuration templates (environment.json)
│
├── cli/                    # Object-oriented CLI architecture (`c2f`)
├── .github/                # GitHub Actions workflows & automation
└── .vscode/                # Editor tasks (tasks.json) and configurations
```

---

## Complete Development Environment

Conn2Flow provides a full-stack development ecosystem designed for both core CMS engineering and modular plugin development.

### What's Included

**Core System Development:**
- **Full CMS Source Code**: Complete `gestor/` system with all management features.
- **Automated Web Installer**: Production-ready web installer under `gestor-instalador/`.
- **Database Migrations & Seeders**: Complete Phinx schema and declarative data system.
- **Testing Environment**: Docker-based development stack with PHP 8.3/8.4 + Apache + MySQL 8.0.

**Plugin Development Framework:**
- **Templates Directory** (`dev-plugins/templates/`): Ready-to-use development templates and environment files.
- **Active Development** (`dev-plugins/plugins/`): Workspaces for private or public plugins.
- **Environment Setup**: Copy `templates/environment/` files to `plugins/private/` or `plugins/public/`.
- **Automated Scripts**: Pre-built tools for scaffolding, synchronization, and packaging.

**AI-Assisted Development:**
- **Knowledge Base**: 20+ specialized technical documents under `ai-workspace/en/docs/`.
- **Agent Conversations**: Documented AI agent collaboration history.
- **Standardized Templates**: Consistent prompt engineering workflows.

---

## Quick Start for Developers

1. **Clone the Repository**
   ```bash
   git clone https://github.com/otavioserra/conn2flow.git
   cd conn2flow
   ```

2. **Install Root and Core Dependencies**
   ```bash
   composer install
   npm install
   cd gestor && composer install && cd ..
   ```

3. **Configure Local Environment**
   ```bash
   cp dev-environment/templates/environment/environment.json dev-environment/data/environment.json
   ```
   Edit `environment.json` with your local paths (`source`, `target`, `dockerPath`).

4. **Start Docker Environment (Optional)**
   ```bash
   cd dev-environment
   docker compose up -d
   ```

---

## Automated Test Suite

Conn2Flow has a comprehensive test suite covering backend unit tests, frontend JS component unit tests, database migrations, and end-to-end browser flows.

### 1. Setup Local Environment

Ensure required PHP extensions are enabled in your `php.ini`:
```ini
extension=curl
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=zip
```

Install Playwright browser binaries:
```bash
npx playwright install --with-deps chromium
```

### 2. Running Tests

* **PHPUnit Backend Tests**:
  ```bash
  composer test
  ```
  Runs unit tests for core libraries, security shims, routing, and Phinx database migrations.

* **Vitest Frontend Tests**:
  ```bash
  npm run test
  ```
  Runs unit tests for JavaScript components (e.g. `html-editor`, `admin-tailwind.js`, `interface-tailwind.js`) with DOM simulation.

* **Playwright E2E Tests**:
  ```bash
  npm run test:e2e
  ```
  Launches E2E browser tests for critical user flows like administrator login, profile adjustments, and visual editor rendering.

---

## VS Code Pre-configured Tasks

The repository includes pre-configured tasks in `.vscode/tasks.json`. Access them via the **Task Explorer** extension or by pressing `Ctrl+P` / `Cmd+P` and typing `task [Task Name]`.

| Category | Task Name | Command / Target | Description |
|---|---|---|---|
| **Docker** | `📦 Docker - Container Status` | `docker ps` | Lists active Docker containers. |
| **Docker** | `📦 Docker - Apache Logs > Real Time` | `docker logs ... --follow` | Streams Apache container logs in real time. |
| **Docker** | `📦 Docker - PHP Logs > Real Time` | `tail -f /var/log/...` | Streams PHP error logs inside the container. |
| **Core CMS** | `🛠️ Manager - Synchronize => Resources - Local` | `atualizacao-dados-recursos.php` | Regenerates database resource contract (`schema-metadata.json`). |
| **Core CMS** | `🛠️ Manager - Synchronize => Database - Test Environment` | `updates-manager-database.sh` | Synchronizes local database schema with migrations/seeders. |
| **Core CMS** | `🛠️ Manager - Synchronize => Files - Test Environment` | `synchronize-manager.sh` | Syncs physical PHP/JS/CSS files to the local Docker volume. |
| **Core CMS** | `🛠️ Manager - Update => All - Test Environment` | Sequence of 3 tasks above | Full sync of resources, files, and database to Docker. |
| **Core CMS** | `🛠️ Manager - GIT Release` | `release.sh` | Standardized release bump, CSS compilation, and tag creation. |
| **Core CMS** | `🛠️ Manager - Create Module` | `create-new-module.sh` | Scaffolds a new Gestor admin module. |
| **Plugins** | `🧩 Public/Private Plugins - Synchronize Active Plugin` | `synchronizes.sh` | Syncs active plugin files to the development environment. |
| **Plugins** | `🧩 Public/Private Plugins - Plugin Resources` | `update-data-resources-plugin.php` | Regenerates resource catalog specifically for the active plugin. |
| **Projects** | `🗃️ Projects - Update => All - Core & Project` | Sequence of project syncs | Deploys core features and updates directly to a specific target project. |

---

## System Update Mechanism

Conn2Flow includes a core update orchestrator in `gestor/controladores/atualizacoes/atualizacoes-sistema.php` with CLI and incremental web (AJAX) support.

### Key Capabilities
- **Artifact Download**: Fetches release packages (`gestor.zip`) by tag or uses local artifacts (`--local-artifact`).
- **Integrity Validation**: Computes and compares SHA-256 checksums (`gestor.zip.sha256`).
- **Selective Wipe**: Safely preserves critical customer data directories: `contents/`, `logs/`, `backups/`, `temp/`, and `autenticacoes/`.
- **Additive Environment Merge**: Seamlessly merges `.env` templates without overwriting custom local variables.
- **Unified Database Script**: Applies Phinx migrations and data seeds in an atomic inline execution.

CLI Example:
```bash
./c2f manager:update-all
```

---

## File Ownership & Permissions

To prevent deployment permission errors during file synchronization or extraction:
```bash
# Set appropriate ownership for web server user
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-gestor
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-github
```

---

## Plugin Development Framework

For comprehensive plugin development guides and architecture rules, refer to:
- [CONN2FLOW-PLUGIN-ARCHITECTURE.md](CONN2FLOW-PLUGIN-ARCHITECTURE.md)
- [CONN2FLOW-PLUGIN-INSTALLER-FLOW.md](CONN2FLOW-PLUGIN-INSTALLER-FLOW.md)
