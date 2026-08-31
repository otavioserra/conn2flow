# Conn2Flow - Complete CMS Development Environment

> 📖 **Available in multiple languages**: [🇧🇷 Portuguese](README-PT-BR.md) | 🇺🇸 English (this file)

Conn2Flow is a high-performance, modular Content Management System (CMS) built with PHP 8.3+, modern Tailwind CSS, and a decoupled architecture designed for multi-tenant deployments, visual page editing, and AI-driven development.

---

## 🆕 Latest Version

**v2.10.0 (August 2026)** *(Current base: v2.9.51)*

- **Automatic Tailwind System Dependencies**: Modals and dimmers (`interface_alerta`, confirmation modals) are automatically recognized and styled in all Tailwind pages without manual dependency declarations.
- **Self-Hosted External Assets & Zero CDN**: All frontend libraries (jQuery, SortableJS, DataTables, Quill, Lucide) and Google Fonts are hosted locally from disk, eliminating supply-chain vulnerabilities and external network latency.
- **Build-Time Asset Minification**: Automated Terser/CSS minification integrated into `./c2f manager:update-all` and release workflows, reducing asset sizes by over 50%.
- **Responsive Admin Tables & Workspaces**: Contained horizontal table scrolling on wide viewports (>=1200px) and resizable sidebar navigation with localStorage persistence.
- **Hardened Security & Session Recovery**: CSRF validation using CSPRNG tokens, strict HTTP headers, seamless expired session recovery on `/signin/`, and scoped Personal Access Tokens (PAT).
- **Web Installer v2 Compatibility**: Full integration with the automated [Web Installer v2.0.0](https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.0.0/instalador.zip).

For complete version details, see [CHANGELOG.md](CHANGELOG.md).

---

## ⚡ Quick Start & Installation

Conn2Flow features a modern automated web installer that prepares database schemas, seeds initial administration data, and generates encryption keys.

### 1. Download & Extract
Download the official `instalador-v2.0.0` distribution:

```bash
# Linux/macOS
curl -L -o instalador.zip https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.0.0/instalador.zip
unzip instalador.zip -d /var/www/html/

# Windows PowerShell
Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.0.0/instalador.zip" -OutFile "instalador.zip"
Expand-Archive -Path "instalador.zip" -DestinationPath "C:\xampp\htdocs\"
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

## 👥 Community, Support & Roadmap

- **GitHub Issues**: Report bugs or propose architectural improvements via GitHub Issues.
- **Professional Inquiries**: Connect with the creator on [LinkedIn](https://www.linkedin.com/in/otaviocserra/).
- **AI Workspace**: Explore agent collaboration prompts and methodology under [ai-workspace/](ai-workspace/README.md).
- **License**: Released under the open-source MIT License.
