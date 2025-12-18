# CONN2FLOW - GitHub Actions and CI/CD Automation

## 📋 Overview

The Conn2Flow project features a **complete CI/CD automation system** through GitHub Actions, developed to fully automate the release processes for both the **Manager** and the **Installer**. This system represents a mature DevOps implementation for PHP projects.

## 🏗️ Automation Structure

```
.github/
├── workflows/              # GitHub Actions Workflows
│   ├── release-gestor.yml      # Manager release automation
│   └── release-instalador.yml  # Installer release automation
├── scripts/                # Utility scripts
│   └── get-latest-installer.sh # Fetches latest installer version
└── chatmodes/              # AI Agent configurations
    └── 4.1-Beast.chatmode.md  # Advanced development mode
```

## 🚀 Release Workflows

### 1. Manager Release (`release-gestor.yml`)

**Trigger:** Tags starting with `gestor-v*` (e.g., `gestor-v1.16.0`)

#### Automated Process:
```yaml
1. Source code checkout
2. PHP 8.2 setup + necessary extensions
3. Composer installation (production)
4. Automatic resource generation
5. Commit updates
6. Cleanup of unnecessary files
7. Creation of gestor.zip + SHA256 checksum
8. Release creation on GitHub
9. Asset upload
```

#### Technical Characteristics:
- **Size Optimization**: Removes physical resources (keeps JSON)
- **Security**: Removes sensitive `.env` files
- **Integrity**: Generates automatic SHA256 checksum
- **Cleanup**: Removes cache, node_modules, temporary files
- **Automatic Versioning**: Pre-release resource update

#### Generated Assets:
- `gestor.zip` - Complete system optimized for production
- `gestor.zip.sha256` - Checksum for integrity verification

### 2. Installer Release (`release-instalador.yml`)

**Trigger:** Tags starting with `instalador-v*` (e.g., `instalador-v1.4.0`)

#### Automated Process:
```yaml
1. Source code checkout
2. Creation of instalador.zip
3. Deletion of development files
4. Release creation on GitHub
5. Asset upload
```

#### Technical Characteristics:
- **Pure Installer**: Only files necessary for installation
- **Multi-language**: PT-BR and EN support preserved
- **Automatic Cleanup**: Removes logs, cache, temporary files
- **Compatibility**: Prepared for all Manager versions

#### Generated Assets:
- `instalador.zip` - Complete multilingual web installer

## 🔧 Utility Scripts

### get-latest-installer.sh
```bash
#!/bin/bash
# Automatically fetches the latest installer URL
# Filters only "instalador-v*" releases
# Returns direct download URL
```

**Usage:**
```bash
# Get latest installer URL
bash .github/scripts/get-latest-installer.sh

# Output: https://github.com/otavioserra/conn2flow/releases/download/instalador-vX.Y.Z/instalador.zip
```

**Application:**
- Deploy automation
- Installation scripts
- Dynamic documentation
- Integration with other systems

## 🤖 AI-Assisted Development

### Beast Mode Chat Configuration
**File:** `.github/chatmodes/4.1-Beast.chatmode.md`

Advanced configuration for AI agents (GitHub Copilot, Claude, ChatGPT) with:

#### Characteristics:
- **Total Autonomy**: Solves problems completely before returning
- **Mandatory Research**: Always searches for updated information on the web
- **Continuous Iteration**: Does not stop until the problem is 100% resolved
- **Rigorous Testing**: Complete validation of all changes
- **Detailed Planning**: Structured todo-lists for tracking

#### Development Workflow:
```markdown
1. Fetch URLs provided by the user
2. Deeply understand the problem
3. Investigate the codebase
4. Research on the internet
5. Develop detailed plan
6. Implement incrementally
7. Debug when necessary
8. Test frequently
9. Iterate until complete resolution
10. Validate and reflect on result
```

#### Practical Application:
- **Feature Development**: Complete and robust implementation
- **Bug Fixing**: Deep investigation and definitive solution
- **Refactoring**: Structural improvements with comprehensive tests
- **Documentation**: Detailed and updated creation

## ⚙️ Configuration and Operation

### Complete Release Process

#### For the Manager:
```bash
# 1. Local development
git add .
git commit -m "feature: new functionality"

# 2. Create release tag
git tag gestor-v1.17.0
git push origin gestor-v1.17.0

# 3. GitHub Actions automatically:
# - Generates updated resources
# - Creates optimized ZIP
# - Publishes release
# - Makes available for download
```

#### For the Installer:
```bash
# 1. Local development
git add gestor-instalador/
git commit -m "feat: installer improvement"

# 2. Create release tag
git tag instalador-v1.5.0
git push origin instalador-v1.5.0

# 3. GitHub Actions automatically:
# - Creates installer ZIP
# - Publishes release
# - Makes available for download
```

### Integration with Update System

GitHub Actions works integrated with the automatic update system:

```php
// System automatically searches on GitHub
$latest_gestor = "https://github.com/otavioserra/conn2flow/releases/download/gestor-v1.16.0/gestor.zip";
$checksum = "https://github.com/otavioserra/conn2flow/releases/download/gestor-v1.16.0/gestor.zip.sha256";

// Automatic download and verification
download_and_verify($latest_gestor, $checksum);
```

## 📊 Automation Benefits

### ✅ **For Developers**
- **Zero Manual Configuration**: Automatic release via tag
- **Guaranteed Quality**: Automatic tests and validations
- **Consistent Versioning**: Rigorous naming standards
- **Immediate Feedback**: Success/failure notifications

### ✅ **For Users**
- **Reliable Releases**: Standardized and tested process
- **Verified Integrity**: Automatic SHA256 checksums
- **Immediate Availability**: Assets published automatically
- **Clear Versioning**: Organized tags and releases

### ✅ **For the Project**
- **Mature DevOps**: Complete and documented CI/CD
- **Code Quality**: Automated standards
- **Scalability**: Easy addition of new workflows
- **Traceability**: Complete release history

## 🔄 Specific Workflows per Release

### Manager Release v1.16.0 (Current)
```yaml
Automatic features:
✅ TailwindCSS/FomanticUI resource generation
✅ Compilation of admin-layouts, admin-components modules
✅ Cleanup of development files
✅ Optimization for production
✅ Automatic release notes documentation
✅ Asset upload with checksums
```

### Installer Release v1.4.0 (Current)
```yaml
Automatic features:
✅ Complete installer packaging
✅ Preservation of multilingual files
✅ Cleanup of temporary files
✅ Compatibility with Manager v1.16.0+
✅ Automatic resource documentation
✅ Direct upload for download
```

## 🛠️ Technical Configurations

### Permissions and Security
```yaml
permissions:
  contents: write  # Necessary to create releases
  
env:
  GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}  # Automatic token
```

### Build Environment
```yaml
runs-on: ubuntu-latest  # Standardized Linux environment
php-version: '8.2'      # Modern and compatible PHP
extensions: zip, curl, mbstring, openssl  # Necessary extensions
composer: --no-dev --optimize-autoloader  # Optimized production
```

### Automatic Versioning
```yaml
# Supported tags:
gestor-v*.*.* → Manager Release
instalador-v*.*.* → Installer Release

# Examples:
gestor-v1.16.0, gestor-v1.17.0-beta
instalador-v1.4.0, instalador-v1.5.0-rc1
```

## 📈 Evolution and Improvements

### Implemented Versions
- **v1.0**: Basic release workflow
- **v2.0**: Resource generation automation
- **v3.0**: Checksums and integrity verification
- **v4.0**: Automatic cleanup and optimization
- **v5.0**: Automatic documentation and detailed release notes

### Future Planned Improvements
- **Automated Tests**: PHPUnit integrated into workflows
- **Automatic Deploy**: Direct deploy to demo servers
- **Notifications**: Webhook for Discord/Slack
- **Multi-environment**: Staging/production deploy
- **Security Scanning**: Automatic vulnerability analysis

## 🔧 Troubleshooting

### Common Problems

#### Workflow fails in resource generation
```bash
# Verify if file exists
php gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php

# Verify write permissions
ls -la gestor/db/data/
ls -la gestor/resources/
```

#### Release is not created
```bash
# Verify tag format
git tag --list | grep -E "(gestor|instalador)-v"

# Verify tag push
git ls-remote --tags origin
```

#### Assets are not uploaded
```bash
# Verify if files were created
ls -la gestor.zip*
ls -la instalador.zip*

# Check workflow logs on GitHub
```

### Workflow Debugging
```yaml
# Add debug step
- name: Debug Environment
  run: |
    echo "Working directory: $(pwd)"
    echo "Files in directory:"
    ls -la
    echo "PHP version:"
    php -v
```

## 📚 Reference Files

### Official Documentation
- `.github/workflows/release-gestor.yml` - Main Manager Workflow
- `.github/workflows/release-instalador.yml` - Installer Workflow
- `.github/scripts/get-latest-installer.sh` - Automatic search script
- `.github/chatmodes/4.1-Beast.chatmode.md` - Advanced AI configuration

### Logs and Monitoring
- GitHub Actions → [Conn2Flow Project](https://github.com/otavioserra/conn2flow/actions)
- Releases → [Releases Page](https://github.com/otavioserra/conn2flow/releases)
- Tags → [Project Tags](https://github.com/otavioserra/conn2flow/tags)

### Integration with Documentation
- [Update System](CONN2FLOW-SYSTEM-UPDATES.md) - How the system uses releases
- [Detailed Installer](CONN2FLOW-INSTALLER-DETAILED.md) - How the installer downloads releases
- [Docker Environment](CONN2FLOW-DOCKER-ENVIRONMENT.md) - Integration with local development

---

## 🎯 Conclusion

The Conn2Flow GitHub Actions system represents a **mature and complete DevOps solution** for PHP projects. With total release automation, integrity verification, production optimization, and automatic documentation, it offers:

- **🚀 Complete Automation** of releases via tags
- **🔒 Guaranteed Security** with checksums and automatic cleanup
- **⚡ Maximum Efficiency** with optimized workflows
- **📊 Consistent Quality** through automated standards
- **🛠️ Advanced Tools** for AI-assisted development
- **📈 Scalability** for future expansions

**Status**: ✅ **Production - Stable and Documented**  
**Last Update**: August 2025  
**Developed by**: Otavio Serra + GitHub Copilot AI  
**Integration**: Complete CI/CD system for Conn2Flow
