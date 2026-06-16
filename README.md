# Conn2Flow - Complete CMS Development Environment

> 📖 **Available in multiple languages**: [🇧🇷 Portuguese](README-PT-BR.md) | 🇺🇸 English (this file)

Conn2Flow is a complete, open-source Content Management System (CMS) built with modern PHP, featuring a revolutionary plugin architecture and comprehensive development tools. This repository contains everything needed to develop, test, and deploy both the core CMS and custom plugins.

**Welcome to Conn2Flow - Complete Open Source CMS Development Environment!**

Conn2Flow is a modern, lightweight and flexible open-source Content Management System (CMS) built using LAMP technology (Linux, Apache, MySQL, and PHP). This repository provides a **complete development environment** that includes:

- ✅ **Full CMS System** (gestor/) - Core CMS with all management features
- ✅ **Automated Web Installer** (gestor-instalador/) - One-click installation with multilingual support
- ✅ **Development Tools** (ai-workspace/) - Complete development environment with AI-assisted workflows
- ✅ **Plugin Development Framework** (dev-plugins/) - Full plugin creation and testing environment

Originally developed as a proprietary CMS named B2make, Conn2Flow is now being released to the open-source community to foster collaboration and innovation.

## Table of Contents

- [📚 Documentation](#documentation)
- [🆕 Latest Version](#latest-version)
- [⚡ Quick Installation](#quick-installation)
- [📖 Learning Resources](#learning-resources)
- [🤖 GitHub Copilot Agents](#github-copilot-agents)
- [📁 Repository Structure](#repository-structure)
- [⚙️ System Features](#system-features)
- [🛠️ Complete Development Environment](#complete-development-environment)
- [📚 Documentation & Development](#documentation--development)
- [🤖 AI-Powered Development Methodology](#ai-powered-development-methodology)
- [👥 Community & Support](#community--support)
- [📄 License](#license)
- [🗺️ Roadmap](#roadmap)

## Documentation

For detailed technical information and development guides, see:

- **[📚 Technical Documentation](ai-workspace/en/docs/README.md)** - Complete technical docs organized by system area
- **[📋 Changelog](CHANGELOG.md)** - Industry-standard changelog following Keep a Changelog format
- **[📊 Full Development History](ai-workspace/en/docs/CONN2FLOW-CHANGELOG-HISTORY.md)** - Detailed commit-by-commit evolution

## Latest Version

**v2.9.0 (June 16, 2026)**

**🎯 Key Features & Highlights:**
- **Advanced Visual HTML Editor**: Floating toolbar, advanced 20-group styler panels, circular color palettes, visual drag-and-drop placeholders with ghost tracking, internal clipboard (Ctrl+C/V), element wrapping (Wrap), and real widget rendering.
- **Security & Authentication**: Two-factor authentication (2FA via app/email), passwordless email-based OTP login, API Access Key profiles with 2FA gating, and interactive OAuth integration helpers.
- **Unified Testing Suite**: Out-of-the-box support for PHPUnit, Vitest, and Playwright E2E browser tests with Actions workflow CI/CD.
- **Publisher Index & Manual Curation**: Live diacritics-insensitive publication search, manual curatorial ordering, dynamic count metrics, and automated dynamic image URL root prefixing.
- **Code Modularization**: Cleaned up the visual editor scope by externalizing simulation engines to `html-editor-modules.js`.

For full changelog, see [CHANGELOG.md](CHANGELOG.md).

## Quick Installation

Conn2Flow features a modern **automated web installer** that simplifies the installation process to just a few clicks. No complex manual configuration required!

### Prerequisites

- **Web Server**: Apache or Nginx with PHP support
- **PHP**: Version 8.0 or higher with required extensions (curl, zip, pdo_mysql, openssl, mbstring, pdo_sqlite)
- **MySQL**: Version 5.7 or higher (or MariaDB equivalent)
- **Node.js & NPM**: Node.js (v20+) and NPM (v10+) required for local testing compilation, vitest, playwright and Tailwind CSS CLI v4 compilation
- **Write Permissions**: Web server must have write access to installation directory

### Installation Steps

1. **Download the Installer**

   **Direct Download:**
   - Click in the next link to download the `instalador.zip`: [Download Installer v1.5.2](https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.5.2/instalador.zip)

   **Linux/macOS:**
   ```bash
   curl -L -o instalador.zip https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.5.2/instalador.zip
   ```

   **Windows PowerShell:**
   ```powershell
   Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.5.2/instalador.zip" -OutFile "instalador.zip"
   ```
   **Always Latest Installer:**
   ```bash
   # Linux/macOS
   LATEST=$(gh release list --repo otavioserra/conn2flow | grep "instalador-v" | head -n1 | awk '{print $3}')
   wget "https://github.com/otavioserra/conn2flow/releases/download/${LATEST}/instalador.zip"

   # Windows PowerShell
   $latest = (gh release list --json tagName | ConvertFrom-Json | Where-Object { $_.tagName -like "instalador-v*" } | Select-Object -First 1).tagName
   Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/$latest/instalador.zip" -OutFile "instalador.zip"
   ```
   **Releases Page:**
   - Go to the [releases page](https://github.com/otavioserra/conn2flow/releases) and download the latest **Instalador** release (look for `instalador-v*` tags, not the "Latest" badge which points to the Gestor system).

2. **Extract to Your Web Directory**
   ```bash
   unzip instalador.zip -d /path/to/your/webroot/gestor-instalador/
   # Or install directly to webroot. The installer is smart enough to detect whether it is in the root or sub-folders.
   unzip instalador.zip -d /path/to/your/webroot/
   ```

3. **Run the Web Installer**
   - Open your browser and navigate to: `http://yourdomain.com/gestor-instalador/` or `http://yourdomain.com/`
   - The installer supports **Portuguese (BR)** and **English (US)**
   - Follow the step-by-step guided installation

4. **Configure Your Installation**
   The web installer will ask for:
   - **Database credentials** (host, name, username, password)
   - **Installation path** (must be outside public folder for security)
   - **Domain name** for your site
   - **Administrator account** details

5. **Automatic Setup**
   The installer will automatically:
   - Download the latest Conn2Flow system
   - Create database tables and initial data
   - Configure authentication and security keys
   - Set up proper file permissions
   - Configure public access files
   - Clean up installation files

6. **Access Your CMS**
   After installation, access your new CMS at the configured domain.

### Security Features

- **Flexible Installation Paths**: Install the system outside your public web folder for enhanced security
- **Automatic Key Generation**: RSA keys and security tokens generated automatically
- **Secure Cleanup**: Installer removes itself after successful installation
- **Detailed Logging**: Complete installation log for troubleshooting

### Manual Installation (Advanced Users)

For advanced users who prefer manual installation or need custom configurations:

1. **Clone the Repository**
   ```bash
   git clone https://github.com/otavioserra/conn2flow.git
   cd conn2flow
   ```

2. **Install Dependencies**
   ```bash
   cd gestor
   composer install
   ```

3. **Configure Environment**
   - Copy configuration examples from `autenticacoes.exemplo/`
   - Set up database credentials and domain-specific settings
   - Generate OpenSSL keys for security

4. **Database Setup**
   - Run the unified update script: `php controladores/atualizacoes/atualizacoes-banco-de-dados.php --env-dir=your-domain`

5. **Web Server Configuration**
   - Point your web server to the `public-access` files
   - Ensure proper permissions and PHP extensions

## Learning Resources

- **[🤖 AI Development Methodology](ai-workspace/README.md)** - How we built this with AI assistance
- **[🛠️ Plugin Development Guide](ai-workspace/en/docs/CONN2FLOW-PLUGIN-ARCHITECTURE.md)** - Complete plugin creation guide
- **[🏗️ System Architecture](ai-workspace/en/docs/CONN2FLOW-KNOWLEDGE-SYSTEM.md)** - Deep technical knowledge
- **[⚙️ Development Workflows](ai-workspace/en/docs/CONN2FLOW-GITHUB-ACTIONS.md)** - CI/CD and automation
- **[📚 Complete System Documentation](ai-workspace/en/docs/CONN2FLOW-MANAGER-DETAILS.md)** - Detailed system architecture and components

## GitHub Copilot Agents

We provide specialized AI agents to assist with different aspects of development. Use these files to configure your GitHub Copilot workspace:

- **[🤖 Conn2Flow General Agent](.github/agents/Conn2Flow.agent.md)** - Top-notch coding agent for general tasks
- **[⚡ Conn2Flow Without Tests](.github/agents/Conn2Flow-Without-Tests.agent.md)** - Speed-focused coding agent (skips test creation)
- **[🔧 Resources Generator](.github/agents/Conn2Flow-Resources.agent.md)** - Specialized in creating and managing system resources
- **[🎨 Image Generator](.github/agents/Conn2Flow-Image-Generator.agent.md)** - Creates images using Gemini 3 Pro (via script)

## Repository Structure

This repository provides a **complete development environment** for Conn2Flow CMS:

* **gestor/**: The main CMS system - core with all management features, plugins V2, and automated updates
* **gestor-instalador/**: Web-based automated installer with multilingual support (Portuguese/English)
* **ai-workspace/**: Complete development environment with AI-assisted workflows, documentation, and automation tools
* **dev-plugins/**: Full plugin development framework with templates, scripts, and testing environment
* **tests/**: Root-level automated test suite including unit (PHPUnit/Vitest), integration, and browser end-to-end tests (Playwright)
* **dev-environment/**: Docker-based development environment with PHP 8.3 + Apache + MySQL 8.0
* **.github/**: GitHub Actions workflows for automated releases, CI/CD, and test runners

### Legacy Branches
* **gestor-v1.16**: Latest stable release before v2.0.0
* **b2make-legacy**: Complete legacy system preserved for reference
* **v0-legacy**: Original 2012 version
* **v1-legacy**: 2015 version

The legacy b2make-* folder structure has been modernized and is now available in the `b2make-legacy` branch for historical reference.

## System Features

### Core CMS Features
- **Content Management**: Full-featured content creation and editing with TailwindCSS preview.
- **Gemini AI Assistant**: Geração assistida de código e conteúdo no Editor HTML com modos técnicos e flexíveis.
- **Visual Forms & Submissions**: Visual form builder with anti-spam security (reCAPTCHA v2/v3, FingerprintJS v4, character limits, and IP blocking).
- **Dynamic Menus**: WordPress-style drag-and-drop hierarchical menu tree editor based on vanilla Pointer Events.
- **Image Galleries**: Batch file selection, Sortable.js drag-and-drop reordering, and public carousel/slider/grid/masonry layouts with individual image links.
- **Publisher Highlights & Index**: Content curatorship modules with real simulation mode, AJAX load-more pagination, live search, and sorting.
- **PayPal Integration**: Recurring subscriptions and payment gateway settings.
- **Multi-Framework CSS**: Choose between TailwindCSS and FomanticUI per resource.
- **Advanced Admin Modules**: Modern interface with real-time preview capabilities.
- **Plugin System V2**: Revolutionary plugin architecture with dynamic detection and automated templates.
- **User Management**: Role-based access control and user authentication.
- **Multi-site Support**: Manage multiple domains from single installation.
- **Security**: OpenSSL encryption, secure authentication, session JWT control, and access controls.
- **Responsive Admin Panel**: Resizable sidebar width (200px to 450px) with Ctrl+B shortcut and localStorage state persistence.

### Development Environment Features
- **Complete Development Stack**: Docker environment with PHP 8.3 + Apache + MySQL 8.0
- **AI-Assisted Development**: Comprehensive ai-workspace with 15 technical docs and 50+ agent conversations
- **Plugin Development Framework**: Full dev-plugins environment with automated templates and scripts
- **Automated Workflows**: GitHub Actions for releases, testing, and deployment
- **Technical Documentation**: 15+ detailed guides covering all system aspects
- **Testing & Validation**: Automated testing framework (PHPUnit, Vitest, Playwright) and migration/seeder verification scripts
- **VS Code Integration**: Pre-configured tasks for Docker, Git operations, and development workflows
- **Ready-to-Use Scripts**: Functional automation scripts for commits, releases, and synchronization

### Installation Benefits
- **One-Click Installation**: Web-based installer with guided setup
- **Multilingual Support**: Portuguese and English interface
- **Flexible Deployment**: Install anywhere, not just public folders
- **Automatic Configuration**: All security keys and settings generated automatically
- **Clean Installation**: Self-removing installer leaves no traces

## Complete Development Environment

Conn2Flow provides a **complete development environment** that goes beyond just the CMS - it's a full development ecosystem designed for both the core system and plugin development.

### 🎯 What's Included

**Core System Development:**
- ✅ **Full CMS Source Code** - Complete gestor/ system with all features
- ✅ **Automated Installer** - Production-ready web installer
- ✅ **Database Migrations** - Complete schema and data migration system
- ✅ **Testing Environment** - Docker-based development stack

**Plugin Development Framework:**
- ✅ **Templates Directory** (`dev-plugins/templates/`) - Ready-to-use development templates and environment files
- ✅ **Active Development** (`dev-plugins/plugins/`) - Where plugins are actually developed (private/public repos)
- ✅ **Environment Setup** - Copy `templates/environment/` files to `plugins/private/` or `plugins/public/`
- ✅ **Automated Scripts** - Pre-built scripts for plugin development, commits, releases, and synchronization
- ✅ **VS Code Integration** - Tasks in `.vscode/tasks.json` for development automation
- ✅ **Documentation** - Complete guides for plugin development

**AI-Assisted Development:**
- ✅ **Knowledge Base** - 15 technical documents preserving system knowledge
- ✅ **Agent Conversations** - 50+ documented AI development sessions
- ✅ **Automation Scripts** - AI-created tools for development workflow
- ✅ **Standardized Templates** - Consistent prompts for quality AI interactions

### 🚀 Quick Start for Developers

1. **Clone the Repository**
   ```bash
   git clone https://github.com/otavioserra/conn2flow.git
   cd conn2flow
   ```

2. **Install VS Code Extensions** (Recommended)
   - **Task Explorer**: `https://github.com/spmeesseman/vscode-taskexplorer` - For easy access to development tasks
   - This extension provides a visual interface for the pre-configured tasks in `.vscode/tasks.json`

3. **Configure Development Environment**
   ```bash
   # Copy and configure environment settings
   cp dev-environment/templates/environment/environment.json dev-environment/data/environment.json
   
   # Edit the file with your local paths:
   # - source: Path to your local Conn2Flow installation
   # - target: Path where Docker will mount the files
   # - dockerPath: Internal Docker container path
   ```

4. **Configure Development of Plugins** (if developing plugins)
   ```bash
   # Copy environment files to plugin directories
   cp -r dev-plugins/templates/environment/* dev-plugins/plugins/private/
   cp -r dev-plugins/templates/environment/* dev-plugins/plugins/public/
   
   # Configure environment.json files in both directories with correct paths
   # These files are essential for plugin development scripts to work properly
   ```

3. **Start Development Environment**
   ```bash
   # Using Docker (recommended)
   cd dev-environment
   docker-compose up -d
   
   # Or use local development scripts
   bash ai-workspace/en/scripts/dev-environment/setup.sh
   ```

4. **Develop Plugins**
   ```bash
   # Use automated templates
   bash dev-plugins/scripts/create-plugin.sh my-plugin
   
   # Development workflow
   cd dev-plugins/plugins/private/my-plugin
   bash scripts/dev/synchronizes.sh checksum
   ```

5. **Contribute to Core**
   ```bash
   # Use AI-assisted development
   # Check ai-workspace/en/prompts/ for standardized templates
   # Follow documented workflows in ai-workspace/en/docs/
   ```

### System Update Mechanism (Automated Updates)

Conn2Flow includes a core update orchestrator in `gestor/controladores/atualizacoes/atualizacoes-sistema.php` with CLI and incremental web (AJAX) support. Main features:

- Artifact download `gestor.zip` by tag (e.g.: `gestor-v1.15.0`) or local artifact usage (`--local-artifact`)
- Optional SHA256 integrity verification (`--no-verify` to skip)
- Selective wipe preserving critical directories: `contents/`, `logs/`, `backups/`, `temp/`, `autenticacoes/`
- Optimized deployment (rename fallback to copy) with statistics of removed/moved files
- Additive merge of `.env` (new variables added with `# added-by-update` block, deprecated variables only logged)
- Unified database script: `atualizacoes-banco-de-dados.php` (applies migrations/data and removes `gestor/db/` folder after success to reduce attack surface)
- JSON plan export + structured logs in `logs/atualizacoes/`
- Execution persistence in `atualizacoes_execucoes` table (status, stats, log/plan links)
- Housekeeping (configurable retention, default 14 days) of logs and temporary directories

Main CLI flags:
```
--tag=gestor-vX.Y.Z  --local-artifact  --only-files  --only-db  --no-db  \
--dry-run  --backup  --download-only  --no-verify  --force-all  --tables=... \
--log-diff  --logs-retention-days=N  --debug
```

Web execution (incremental):
```
?action=start -> deploy -> db -> finalize (status for polling, cancel to cancel)
```
Session state: `temp/atualizacoes/sessions/<sid>.json` + `<sid>.log`.

Complete documentation: `ai-workspace/en/docs/CONN2FLOW-SYSTEM-UPDATES.md`.

### File Ownership & Permissions

To avoid silent failures of `rename()`/`unlink()` during deployment (especially in containers), ensure that the owner of installation directories and artifacts is the same user as the PHP process (e.g.: `www-data`). Example after extraction / before running update:
```bash
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-gestor
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-github
```
Permission failures will result in warnings about not removing old folders and files not being updated.

### Modern Development Stack
- **PHP 8.0+**: Modern PHP features and performance
- **Composer**: Dependency management and autoloading
- **Phinx**: Database migrations and schema management
- **GitHub Actions**: Automated builds, releases, and test runners
- **Modular Design**: Clean separation of concerns

### Automated Test Suite

Conn2Flow has a comprehensive test suite covering backend unit tests, frontend JS component unit tests, database integrations, and end-to-end user flows.

#### 1. Setup local environment
Before running the tests, install the dependencies at the root level of the repository:
```bash
composer install
npm install
npx playwright install --with-deps
```

Ensure that in your `C:\tools\php84\php.ini` (or local `php.ini` equivalent), the following extensions are enabled:
```ini
extension=mbstring
extension=pdo_mysql
extension=pdo_sqlite
```

#### 2. Running Tests
You can trigger the tests using the following commands:
*   **PHPUnit Backend Tests**:
    ```bash
    composer test
    ```
    This runs unit tests for core libraries (e.g. OpenSSL RSA keys generation, MD5 resource deduplication) and integration tests for Phinx database migrations.
*   **Vitest Frontend Tests**:
    ```bash
    npm run test
    ```
    This runs unit tests for JS components (e.g. `publisher-highlights.js` and `publisher-index.widget.js`) with DOM simulation.
*   **Playwright E2E Tests**:
    ```bash
    npm run test:e2e
    ```
    This launches E2E functional browser tests for critical user flows like administrator login, profile changes, and AJAX components rendering.

### VS Code Pre-configured Tasks

To streamline development, the repository includes several pre-configured tasks in `.vscode/tasks.json`. You can access them in VS Code via the **Task Explorer** extension or by pressing `Ctrl+P` / `Cmd+P` and typing `task [Task Name]`.

| Category | Task Name | Command / Script | Description |
| --- | --- | --- | --- |
| **Docker** | `📦 Docker - Container Status` | `docker ps` | Lists active Docker containers. |
| **Docker** | `📦 Docker - Apache Logs > Real Time` | `docker logs ... --follow` | Streams Apache container logs in real time. |
| **Docker** | `📦 Docker - PHP Logs > Real Time` | `tail -f /var/log/...` | Streams PHP error logs inside the container. |
| **Core CMS** | `🛠️ Manager - Synchronize => Resources - Local` | `atualizacao-dados-recursos.php` | Regenerates database resource contract (`schema-metadata.json`). |
| **Core CMS** | `🛠️ Manager - Synchronize => Database - Test Environment` | `updates-manager-database.sh` | Synchronizes local database schema with migrations/seeders. |
| **Core CMS** | `🛠️ Manager - Synchronize => Files - Test Environment` | `synchronize-manager.sh` | Syncs physical PHP/JS/CSS files to the local Docker volume. |
| **Core CMS** | `🛠️ Manager - Update => All - Test Environment` | Sequence of 3 tasks above | Full sync of resources, files, and database to Docker. |
| **Core CMS** | `🛠️ Manager - GIT Release` | `release.sh` | Automates release bump (major/minor/patch) and commits compiled CSS. |
| **Core CMS** | `🛠️ Manager - Create Module` | `create-new-module.sh` | Automates boilerplate creation for a new Gestor admin module. |
| **Plugins** | `🧩 Public/Private Plugins - Synchronize Active Plugin` | `synchronizes.sh` | Syncs active plugin files to the development environment. |
| **Plugins** | `🧩 Public/Private Plugins - Plugin Resources` | `update-data-resources-plugin.php` | Regenerates resource catalog specifically for the plugin. |
| **Projects** | `🗃️ Projects - Update => All - Core & Project` | Sequence of project syncs | Deploys core features and updates directly to a specific target project. |

### Directory Structure
```
gestor/                 # Main CMS system
├── bibliotecas/        # Core libraries
├── controladores/      # MVC controllers
├── modulos/           # System modules
├── autenticacoes/     # Domain-specific configurations
├── db/               # Database migrations and schema
├── public-access/    # Public web files
└── vendor/           # Composer dependencies

gestor-instalador/     # Web installer
├── src/              # Installer logic
├── views/            # Installation interface
├── lang/             # Multilingual support
└── assets/           # CSS, JS, images

ai-workspace/          # Development environment
├── docs/             # Technical documentation (15+ guides)
├── scripts/          # Automation scripts
├── prompts/          # AI development templates
├── agents-history/   # 50+ AI agent conversations
└── utils/            # Development utilities

dev-plugins/           # Plugin development framework
├── templates/        # Ready-to-use development templates
│   ├── environment/  # Environment files to copy to plugin folders
│   │   ├── .github/  # Automated release workflows
│   │   ├── scripts/  # Development scripts for plugins
│   │   └── environment.json # Plugin mapping and development config
│   ├── plugin/       # Basic plugin template to copy
│   └── plugin-skeleton/ # Advanced plugin template with examples
├── plugins/          # Active plugin development environment
│   ├── private/      # Private repository plugins (require token)
│   └── public/       # Public repository plugins (no token needed)
└── tests/            # Plugin testing environment

tests/                 # Root-level Automated Test Suite [NEW]
├── Unit/             # Unit tests (PHP/PHPUnit, JS/Vitest)
├── Integration/      # Integration tests (DB migrations, AJAX widget routing)
└── E2E/              # End-to-End browser tests (Playwright)

sdd/                   # Spec-Driven Development (SDD/STD) normative files, decision logs, and human requests [NEW]

.vscode/              # VS Code development configuration
└── tasks.json        # Pre-configured tasks for development automation

dev-environment/       # Docker development stack
├── docker/           # Docker configurations
├── data/             # Sample data and configurations
└── tests/            # Integration tests

.github/               # GitHub Actions workflows
└── workflows/        # CI/CD automation and test runners

phpunit.xml           # PHPUnit configuration file [NEW]
vitest.config.js      # Vitest configuration file [NEW]
playwright.config.js  # Playwright configuration file [NEW]
package.json          # Node.js dependencies and script mappings [NEW]
composer.json         # PHPUnit dependency definition [NEW]
```

## Documentation & Development

### Technical Documentation

Conn2Flow includes comprehensive technical documentation for developers and system administrators:

- **[📚 System Knowledge](ai-workspace/en/docs/CONN2FLOW-KNOWLEDGE-SYSTEM.md)** - Complete system overview and architecture
- **[🛠️ Installation Guide](ai-workspace/en/docs/CONN2FLOW-INSTALLER-DETAILED.md)** - Detailed installation and configuration
- **[🎨 Layouts & Components](ai-workspace/en/docs/CONN2FLOW-LAYOUTS-PAGES-COMPONENTS.md)** - UI structure and component system
- **[🔧 Modules Development](ai-workspace/en/docs/CONN2FLOW-MODULES-DETAILED.md)** - How to develop custom modules
- **[🔀 Routing System](ai-workspace/en/docs/CONN2FLOW-ROUTING-DETAILED.md)** - URL routing and request handling
- **[⚡ Automation](ai-workspace/en/docs/CONN2FLOW-EXPORT-AUTOMATION.md)** - Resource export automation
- **[🎨 CSS Frameworks](ai-workspace/en/docs/CONN2FLOW-CSS-FRAMEWORK.md)** - TailwindCSS and FomanticUI integration
- **[📱 Preview System](ai-workspace/en/docs/CONN2FLOW-PREVIEW-MODALS-SYSTEM.md)** - Modal preview system with CodeMirror
- **[🔄 System Updates](ai-workspace/en/docs/CONN2FLOW-SYSTEM-UPDATES.md)** - Automated update mechanism
- **[🚀 Project Deployment System](ai-workspace/en/docs/CONN2FLOW-PROJECTS-SYSTEM.md)** - OAuth-based project deployment via API
- **[🐳 Docker Environment](ai-workspace/en/docs/CONN2FLOW-DOCKER-ENVIRONMENT.md)** - Complete development and testing environment
- **[⚙️ GitHub Actions](ai-workspace/en/docs/CONN2FLOW-GITHUB-ACTIONS.md)** - Complete CI/CD automation via GitHub Actions
- **[🌐 Multilingual System](ai-workspace/en/docs/CONN2FLOW-HYBRID-MULTILINGUAL-SYSTEM-COMPLETED.md)** - Multi-language support

### Development Resources

The `ai-workspace/` directory contains all development tools and documentation:
- Scripts for testing, validation, and migration
- Detailed prompts for AI-assisted development
- Complete system knowledge base for contributors

## AI-Powered & Spec-Driven Development Methodology

### 🤖 Collaborative Development with AI Agents & SDD

Conn2Flow pioneered a comprehensive **AI-assisted development methodology** over 12 months of active collaboration with AI agents. The repository utilizes a **Spec-Driven Development (SDD/STD)** framework managed inside the `sdd/` directory. SDD ensures that all changes follow structured specifications (intakes), transacted batches, decision logs, and automated validation checklists before merging.

#### **Collaborative AI Agent Architecture**
The development ecosystem operates through a collaborative human-AI team structure utilizing the **Ray** context platform:
*   **Chief Engineer (Engenheiro Chefe)**: **Antigravity** (powered by Gemini Flash 3.5 / Gemini Pro running on the Ray context), acting as the central software architect within VS Code, orchestrating the implementation plans, reviewing code, and ensuring overall architectural alignment.
*   **Executing Engineer (Engenheiro Executor)**: **Claude Code** (powered by Claude Opus 4.8 running on the Ray context), executing code changes in narrow slices, resolving bugs, and running the local test suites.
*   **Supporting Partners**: **ChatGPT / Codex** (powered by GPT 5.5 running on the Ray context) and other specialized agents assisting with test coverage, validations, and automated code reviews.
*   **Human Partner**: Reviews the specifications, executes runtime checks in the test environments, resolves edge-case requirements, and provides final approvals.

#### **What Makes This Special**
- **📋 Spec-Driven Development (SDD/STD)**: Normative specifications in `sdd/` control how changes are split into manageable batches.
- **📚 15 Technical Documents**: Comprehensive system knowledge preserved across sessions in `ai-workspace/`.
- **🤖 50+ Agent Conversations**: Critical development sessions documented and preserved.
- **🔧 20+ Automated Scripts**: Tools created by AI agents for validation, testing, and deployment.
- **⚡ 90% Efficiency Gain**: Dramatic reduction in context-setting time for new AI sessions.

#### **Key Innovations**
- **Structured AI Collaboration**: Dynamic handoff and cooperation between different AI models (Gemini, Claude, GPT).
- **Knowledge Persistence**: Technical knowledge survives between AI sessions.
- **Template-Driven Development**: Consistent, high-quality AI interactions.
- **Automated Workflows**: AI-created scripts that automate repetitive tasks.
- **Historical Context**: Preserved solutions prevent re-solving the same problems.
- **Scalable Methodology**: Framework that improves with each interaction.

#### **For AI Researchers & Developers**
The `ai-workspace/` methodology demonstrates:
- How to maintain context across multiple AI sessions
- Techniques for preserving and transferring technical knowledge
- Templates that consistently produce high-quality code
- Integration of AI assistance into professional development workflows
- Practical solutions for the "context window" problem in long-term projects

**Explore**: [`ai-workspace/README.md`](ai-workspace/README.md) for complete methodology details

---

*This represents one of the most comprehensive real-world applications of AI-assisted development methodology in an active production system.*

## Community & Support

### Contributing

We welcome contributions! Here's how you can help:

- **Report Issues**: Use GitHub Issues to report bugs or suggest features
- **Submit Pull Requests**: Contribute code improvements and new features
- **Documentation**: Help improve documentation and translations
- **Testing**: Test new releases and provide feedback

### Development Guidelines

1. **Fork the Repository**: Create your own fork for development
2. **Create Feature Branch**: Work on features in dedicated branches
3. **Follow Standards**: Use PSR coding standards and existing patterns
4. **Write Tests**: Include tests for new functionality
5. **Document Changes**: Update documentation for new features

### Getting Help

- **GitHub Issues**: For bug reports and feature requests
- **Discussions**: For general questions and community support
- **LinkedIn**: Connect with the founder at [https://www.linkedin.com/in/otaviocserra/](https://www.linkedin.com/in/otaviocserra/)

## License

Conn2Flow is released under an open-source license to ensure freedom of use, modification, and distribution. License details will be finalized soon with community input.

## Roadmap

### ✅ Recently Completed
- **Automated Testing Suite**: Root-level unit (PHPUnit, Vitest) and E2E browser tests (Playwright) integrated in CI/CD pipeline.
- **Dynamic Content Modules**: Rich components including Menus (WordPress-style drag-and-drop), Galleries (Sortable curation + links), and Publisher Index (AJAX live search/pagination).
- **Plugin System V2**: Revolutionary plugin architecture with dynamic detection and automated templates.
- **Complete Development Environment**: Full-stack development tools with AI assistance.
- **Automated Workflows**: GitHub Actions for releases, testing, and deployment.
- **Technical Documentation**: 15+ comprehensive guides and knowledge base.

### Upcoming Features
- **Enhanced Plugin Marketplace**: Plugin discovery and installation system
- **REST API**: Full API for headless CMS usage and integrations
- **Mobile App**: React Native companion app for content management
- **Advanced Multi-language**: Built-in translation management and workflows
- **Performance Optimization**: Advanced caching and optimization features
- **Online Demos**: Live demonstration environments for all features

### Migration from Legacy
Users of the legacy B2make system can find migration tools and documentation in the `b2make-legacy` branch.

---

**Conn2Flow - Complete CMS Development Environment. One Repository, Full Stack.**

*From legacy B2make to modern open-source CMS with revolutionary plugin system and AI-assisted development methodology.*
