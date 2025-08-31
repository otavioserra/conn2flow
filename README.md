# Conn2Flow - Modern Open Source CMS

**Welcome to Conn2Flow CMS!**

Conn2Flow is a modern, lightweight and flexible open-source Content Management System (CMS) built using LAMP technology (Linux, Apache, MySQL, and PHP). Originally developed as a proprietary CMS named B2make, Conn2Flow is now being released to the open-source community to foster collaboration and innovation.

**Online Demos**

You will be able to experience Conn2Flow in demo versions (**coming soon**):

* Latest Version (app.conn2flow.com): [app.conn2flow.com](http://app.conn2flow.com) (coming soon) - This will showcase the current version of Conn2Flow with all modern features.
* Legacy Showcase (v1.conn2flow.com): [v1.conn2flow.com](http://v1.conn2flow.com) (coming soon) - Historical reference of the B2make legacy system.

## Repository Structure

This repository contains the modern Conn2Flow system with automated installation and management:

* **gestor/**: The main server system - core CMS with all management features
* **gestor-cliente/**: Distributed client system for multi-site management  
* **gestor-cliente-update/**: Automated update system for distributed clients
* **gestor-instalador/**: Web-based automated installer with multilingual support
* **gestor-plugins/**: Plugin ecosystem for extending functionality
* **cpanel/**: cPanel integration libraries (optional - system also works via FTP)
* **ai-workspace/**: Development environment and comprehensive technical documentation

### Documentation

For detailed technical information and development guides, see:

- **[📚 Technical Documentation](ai-workspace/docs/README.md)** - Complete technical docs organized by system area
- **[📋 Changelog](CHANGELOG.md)** - Industry-standard changelog following Keep a Changelog format
- **[📊 Full Development History](ai-workspace/docs/CONN2FLOW-CHANGELOG-HISTORY.md)** - Detailed commit-by-commit evolution

### Legacy Branches
* **b2make-legacy**: Complete legacy system preserved for reference
* **v0-legacy**: Original 2012 version
* **v1-legacy**: 2015 version

The legacy b2make-* folder structure has been modernized and is now available in the `b2make-legacy` branch for historical reference. 

## Quick Installation

Conn2Flow features a modern **automated web installer** that simplifies the installation process to just a few clicks. No complex manual configuration required!

### Prerequisites

- **Web Server**: Apache or Nginx with PHP support
- **PHP**: Version 8.0 or higher with required extensions (curl, zip, pdo_mysql, openssl)
- **MySQL**: Version 5.7 or higher (or MariaDB equivalent)
- **Write Permissions**: Web server must have write access to installation directory

### Installation Steps

1. **Download the Installer**
   
**Linux/macOS:**
```bash
curl -L -o instalador.zip https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.0.10/instalador.zip
```

**Windows PowerShell:**
```powershell
Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.0.10/instalador.zip" -OutFile "instalador.zip"
```   **Option 2 - Always Latest Installer:**
   ```bash
   # Linux/macOS
   LATEST=$(gh release list --repo otavioserra/conn2flow | grep "instalador-v" | head -n1 | awk '{print $3}')
   wget "https://github.com/otavioserra/conn2flow/releases/download/${LATEST}/instalador.zip"
   
   # Windows PowerShell
   $latest = (gh release list --json tagName | ConvertFrom-Json | Where-Object { $_.tagName -like "instalador-v*" } | Select-Object -First 1).tagName
   Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/$latest/instalador.zip" -OutFile "instalador.zip"
   ```
   
   **Option 3 - Manual Download:**
   Go to the [releases page](https://github.com/otavioserra/conn2flow/releases) and download the latest **Instalador** release (look for `instalador-v*` tags, not the "Latest" badge which points to the Gestor system).

2. **Extract to Your Web Directory**
   ```bash
   unzip instalador.zip -d /path/to/your/webroot/
   ```

3. **Run the Web Installer**
   - Open your browser and navigate to: `http://yourdomain.com/gestor-instalador/`
   - The installer supports **Portuguese (BR)** and **English (US)**
   - Follow the step-by-step guided installation

4. **Configure Your Installation**
   The web installer will ask for:
   - **Database credentials** (host, name, username, password)
   - **Installation path** (can be outside public folder for security)
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
   - Import schema from `gestor/db/conn2flow-schema.sql`
   - Or use Phinx migrations: `vendor/bin/phinx migrate`

5. **Web Server Configuration**
   - Point your web server to the `public-access` files
   - Ensure proper permissions and PHP extensions

## System Features

### Core CMS Features
- **Content Management**: Full-featured content creation and editing with TailwindCSS preview
- **Multi-Framework CSS**: Choose between TailwindCSS and FomanticUI per resource
- **Advanced Admin Modules**: Modern interface with real-time preview capabilities
- **User Management**: Role-based access control and user authentication
- **Multi-site Support**: Manage multiple domains from single installation
- **Plugin System**: Extensible architecture with custom plugins
- **Security**: OpenSSL encryption, secure authentication, and access controls

### Technical Features
- **Modern PHP**: Built for PHP 8.0+ with modern coding standards
- **Database**: MySQL/MariaDB with migration system and framework_css support
- **Automated Updates**: Built-in system update mechanism with integrity verification
- **Preview System**: Real-time TailwindCSS/FomanticUI preview for visual resources
- **Modal Components**: Advanced modal system with CodeMirror integration
- **Distributed Architecture**: Support for client-server configurations
- **FTP Integration**: Direct file management capabilities
- **cPanel Integration**: Optional cPanel API integration (not required)

### Installation Benefits
- **One-Click Installation**: Web-based installer with guided setup
- **Multilingual Support**: Portuguese and English interface
- **Flexible Deployment**: Install anywhere, not just public folders
- **Automatic Configuration**: All security keys and settings generated automatically
- **Clean Installation**: Self-removing installer leaves no traces

## Development & Architecture

### System Update Mechanism (Automated Updates)

Conn2Flow inclui um orquestrador de atualização do núcleo em `gestor/controladores/atualizacoes/atualizacoes-sistema.php` com suporte CLI e execução incremental via web (AJAX). Principais características:

- Download de artefato `gestor.zip` por tag (ex: `gestor-v1.15.0`) ou uso de artefato local (`--local-artifact`)
- Verificação opcional de integridade SHA256 (`--no-verify` para ignorar)
- Wipe seletivo preservando diretórios críticos: `contents/`, `logs/`, `backups/`, `temp/`, `autenticacoes/`
- Deploy otimizado (rename fallback para copy) com estatísticas de arquivos removidos / movidos
- Merge aditivo de `.env` (novas variáveis adicionadas com bloco `# added-by-update`, variáveis deprecadas apenas logadas)
- Script unificado de banco: `atualizacoes-banco-de-dados.php` (aplica migrações/dados e remove pasta `gestor/db/` após sucesso para reduzir superfície)
- Exportação de plano JSON + logs estruturados em `logs/atualizacoes/`
- Persistência das execuções na tabela `atualizacoes_execucoes` (status, stats, links de log/plano)
- Housekeeping (retenção configurável, padrão 14 dias) de logs e diretórios temporários

Flags principais (CLI):
```
--tag=gestor-vX.Y.Z  --local-artifact  --only-files  --only-db  --no-db  \
--dry-run  --backup  --download-only  --no-verify  --force-all  --tables=... \
--log-diff  --logs-retention-days=N  --debug
```

Execução Web (incremental):
```
?action=start -> deploy -> db -> finalize (status para polling, cancel para cancelar)
```
Estado de sessão: `temp/atualizacoes/sessions/<sid>.json` + `<sid>.log`.

Documentação completa: `ai-workspace/docs/CONN2FLOW-ATUALIZACOES-SISTEMA.md`.

### File Ownership & Permissions

Para evitar falhas silenciosas de `rename()`/`unlink()` durante deploy (principalmente em containers), garanta que o owner dos diretórios da instalação e artefatos seja o mesmo usuário do processo PHP (ex: `www-data`). Exemplo pós extração / antes de executar atualização:
```bash
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-gestor
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-github
```
Falhas de permissão resultarão em avisos de não remoção de pastas antigas e arquivos não atualizados.

### Modern Development Stack
- **PHP 8.0+**: Modern PHP features and performance
- **Composer**: Dependency management and autoloading
- **Phinx**: Database migrations and schema management
- **GitHub Actions**: Automated builds and releases
- **Modular Design**: Clean separation of concerns

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

cpanel/               # cPanel integration (optional)
gestor-cliente/       # Distributed client system
```

## Current Versions

### Latest Stable Releases

**Gestor (Core System)**: `v1.16.0` *(Latest)*
- ✅ **Sistema de Preview TailwindCSS**: Pré-visualização em tempo real para recursos visuais
- ✅ **Suporte Multi-Framework CSS**: Escolha entre TailwindCSS e FomanticUI por recurso
- ✅ **Módulos Admin Modernizados**: admin-layouts, admin-paginas e admin-componentes atualizados
- ✅ **Sistema Modal Avançado**: Modals responsivos com CodeMirror integrado
- ✅ **Padrões Técnicos Otimizados**: gestor_componente() e modelo_var_troca() corrigidos
- ✅ **Documentação Técnica Completa**: Guias e templates para desenvolvedores

**Instalador (Installer)**: `v1.4.0` *(Latest)*
- ✅ **Suporte Framework CSS**: Instalação preparada para novos recursos v1.16.0
- ✅ **Charset UTF-8 Robusto**: Compatibilidade total com caracteres especiais
- ✅ **getPdo() Unificado**: Método único para todas conexões de banco
- ✅ **Detecção URL Robusta**: Funcionamento garantido em subpasta ou raiz
- ✅ **Auto-login Aprimorado**: Configuração automática pós-instalação
- ✅ **Logs Detalhados**: Rastreamento completo do processo

### Version History
- **Gestor v1.16.0**: Sistema de preview TailwindCSS, suporte multi-framework CSS, módulos admin modernizados, padrões técnicos otimizados.
- **Gestor v1.15.0**: Sistema de atualização automática consolidado, correção de permissões, documentação técnica.
- **Gestor v1.11.0**: Versionamento automático recursos módulos/plugins; melhorias checksum e remoção definitiva de seeders na atualização.
- **Gestor v1.10.x**: Correções de duplicidade, internacionalização, unificação geração de recursos, campos de controle *updated*.
- **Instalador v1.4.0**: Suporte framework CSS, charset UTF-8 robusto, getPdo() unificado, preparação para preview system.
- **Instalador v1.3.3**: Refatoração robusta com charset utf8mb4, correção de acentuação, instalação em ambientes diversos.
- **Instalador v1.1.0**: Refatoração para usar script de atualização central; ajustes RewriteBase e criação admin.

### Development Environment
- **Docker**: Complete development stack with PHP 8.3 + Apache + MySQL 8.0
- **Local PHP**: 8.4.8 CLI for utility scripts and development tools
- **Database**: Verified schema with 75 tables and comprehensive seeders
- **Testing**: Migration and seeder verification scripts included

# Plugin ecosystem
gestor-plugins/

## Documentation & Development

### Technical Documentation

Conn2Flow includes comprehensive technical documentation for developers and system administrators:

- **[📚 System Knowledge](ai-workspace/docs/CONN2FLOW-SISTEMA-CONHECIMENTO.md)** - Complete system overview and architecture
- **[🛠️ Installation Guide](ai-workspace/docs/CONN2FLOW-INSTALADOR-DETALHADO.md)** - Detailed installation and configuration
- **[🎨 Layouts & Components](ai-workspace/docs/CONN2FLOW-LAYOUTS-PAGINAS-COMPONENTES.md)** - UI structure and component system
- **[🔧 Modules Development](ai-workspace/docs/CONN2FLOW-MODULOS-DETALHADO.md)** - How to develop custom modules
- **[🔀 Routing System](ai-workspace/docs/CONN2FLOW-ROTEAMENTO-DETALHADO.md)** - URL routing and request handling
- **[⚡ Automation](ai-workspace/docs/CONN2FLOW-AUTOMACAO-EXPORTACAO.md)** - Resource export automation
- **[🎨 CSS Frameworks](ai-workspace/docs/CONN2FLOW-FRAMEWORK-CSS.md)** - TailwindCSS and FomanticUI integration
- **[📱 Preview System](ai-workspace/docs/CONN2FLOW-SISTEMA-PREVIEW-MODALS.md)** - Modal preview system with CodeMirror
- **[🔄 System Updates](ai-workspace/docs/CONN2FLOW-ATUALIZACOES-SISTEMA.md)** - Automated update mechanism
- **[🐳 Docker Environment](ai-workspace/docs/CONN2FLOW-AMBIENTE-DOCKER.md)** - Complete development and testing environment
- **[🌐 Multilingual System](ai-workspace/docs/CONN2FLOW-SISTEMA-HIBRIDO-MULTILANGUE-CONCLUIDO.md)** - Multi-language support

### Change History

- **[📋 Standard Changelog](CHANGELOG.md)** - Industry-standard changelog following semantic versioning
- **[📊 Development History](ai-workspace/docs/CONN2FLOW-CHANGELOG-HISTORY.md)** - Complete commit history with context and insights

### Development Resources

The `ai-workspace/` directory contains all development tools and documentation:
- Scripts for testing, validation, and migration
- Detailed prompts for AI-assisted development
- Complete system knowledge base for contributors

## AI-Powered Development Methodology

### 🤖 Collaborative Development with AI Agents

Conn2Flow pioneered a comprehensive **AI-assisted development methodology** over 12 months of active collaboration with AI agents (GitHub Copilot, Claude, ChatGPT, Gemini). The `ai-workspace/` directory represents a mature framework for human-AI collaborative software development.

#### **What Makes This Special**
- **📚 15 Technical Documents**: Comprehensive system knowledge preserved across sessions
- **🤖 50+ Agent Conversations**: Critical development sessions documented and preserved  
- **🔧 20+ Automated Scripts**: Tools created by AI agents for validation, testing, and deployment
- **📝 Proven Templates**: Standardized prompts that consistently produce quality results
- **⚡ 90% Efficiency Gain**: Dramatic reduction in context-setting time for new AI sessions

#### **Key Innovations**
- **Knowledge Persistence**: Technical knowledge survives between AI sessions
- **Template-Driven Development**: Consistent, high-quality AI interactions
- **Automated Workflows**: AI-created scripts that automate repetitive tasks
- **Historical Context**: Preserved solutions prevent re-solving the same problems
- **Scalable Methodology**: Framework that improves with each interaction

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

### Upcoming Features
- **Enhanced Plugin System**: More powerful plugin architecture
- **REST API**: Full API for headless CMS usage
- **Mobile App**: React Native companion app (future release)
- **Multi-language Content**: Built-in translation management
- **Performance Optimization**: Caching and optimization features

### Migration from Legacy
Users of the legacy B2make system can find migration tools and documentation in the `b2make-legacy` branch.

---

**Conn2Flow - Modern CMS. Simple Installation. Powerful Features.**

*Transforming the legacy B2make system into a modern, community-driven open-source CMS.*