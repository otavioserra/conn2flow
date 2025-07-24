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
   
   **Option 1 - Direct Download (Current Version):**
   ```bash
   # Linux/macOS
   wget https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.0.8/instalador.zip
   
   # Windows PowerShell
   Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.0.8/instalador.zip" -OutFile "instalador.zip"
   ```
   
   **Option 2 - Always Latest Installer:**
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
- **Content Management**: Full-featured content creation and editing
- **User Management**: Role-based access control and user authentication
- **Multi-site Support**: Manage multiple domains from single installation
- **Plugin System**: Extensible architecture with custom plugins
- **Security**: OpenSSL encryption, secure authentication, and access controls

### Technical Features
- **Modern PHP**: Built for PHP 8.0+ with modern coding standards
- **Database**: MySQL/MariaDB with migration system
- **Automated Updates**: Built-in system update mechanism
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
gestor-plugins/       # Plugin ecosystem
```

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