# CONN2FLOW - Docker Development Environment

## 📋 Overview

The Conn2Flow project includes a **complete and mature Docker environment** developed specifically for development, testing, and demonstrations. This environment offers a robust multi-domain infrastructure that simulates real production conditions.

## 🏗️ System Architecture

### Main Components

```yaml
services:
  app:          # PHP 8.3 + Apache Application
  mysql:        # MySQL 8.0 with initial data
  phpmyadmin:   # Web interface for database
  ftp:          # Multi-domain FTP server (ProFTPD)
```

### Multi-Domain Structure (Current - External Repository)

Now the Docker environment has been moved to a dedicated repository:

`../conn2flow-docker-test-environment/dados/`

Main site structure:

```
../conn2flow-docker-test-environment/dados/sites/
├── localhost/
│   ├── conn2flow-gestor/        # Copy/synchronization of manager for testing
│   ├── conn2flow-gestor-v1/     # Snapshot/previous version (example)
│   ├── conn2flow-github/        # Local build artifacts (gestor.zip)
│   ├── public_html/
│   │   ├── instalador/          # Web installer
│   │   └── gestor-v1/           # Example of exposed version
│   ├── home/                    # Reserved space
│   └── ...                      # Other internal folders
├── site1.local/
│   ├── public_html/
│   └── home/
└── site2.local/
  ├── public_html/
  └── home/
```

> LEGACY: Old references to `docker/dados/` within this main repository should now be interpreted as `../conn2flow-docker-test-environment/dados/`.

## 🚀 Configuration and Usage

### Prerequisites
- Docker Desktop installed and configured
- Git Bash or compatible terminal
- Available ports: 80, 3306, 8081, 21

### Quick Start

```bash
# Navigate to external DOCKER directory
cd ../conn2flow-docker-test-environment/dados

# Build and start (compose v2 already accepts "docker compose")
docker compose up --build -d

# Check status
docker compose ps
```

### Accessing Services

| Service | URL/Address | Credentials |
|---------|--------------|-------------|
| **Main Application** | http://localhost | - |
| **Installer** | http://localhost/instalador/ | - |
| **phpMyAdmin** | http://localhost:8081 | root / root123 |
| **MySQL** | localhost:3306 | conn2flow_user / conn2flow_pass |
| **FTP** | localhost:21 | See FTP section |

## 💾 Database Configuration

### Development Credentials
```env
# For application
MYSQL_HOST=mysql
MYSQL_DATABASE=conn2flow
MYSQL_USER=conn2flow_user
MYSQL_PASSWORD=conn2flow_pass

# For administration
MYSQL_ROOT_USER=root
MYSQL_ROOT_PASSWORD=root123
```

### Automatic Initialization
- SQL Schema automatically loaded on startup
- Sample data and complete structure
- Reset available via `docker-compose down -v` (WARNING: deletes data)

## 🌐 Multi-Domain System

### Hosts Configuration (Optional)
To test multiple domains in the browser, add to the hosts file:

**Windows:** `C:\Windows\System32\drivers\etc\hosts`
**Linux/macOS:** `/etc/hosts`

```
127.0.0.1 site1.local
127.0.0.1 site2.local
```

### Test via curl
```bash
# Main domain
curl "http://localhost"

# Test sites
curl -H "Host: site1.local" "http://localhost"
curl -H "Host: site2.local" "http://localhost"
```

### Site Management
```bash
# Utility script (external docker repository)
cd ../conn2flow-docker-test-environment/utils/

# List existing sites
bash gerenciar-sites.sh listar

# Create new site
bash gerenciar-sites.sh criar newsite.local

# Copy installer to a site
bash gerenciar-sites.sh copiar-instalador site1.local
```

## 📁 Multi-Domain FTP System

### Features
- **ProFTPD** with virtual users
- **Direct mapping**: domain folder = user FTP root
- **Automatic detection** of new domains
- **Complete integration** with web system

### Configured FTP Users
| User | Password | FTP Root |
|---------|-------|----------|
| localhost | localhost123 | /sites/localhost/ |
| site1.local | site1.local123 | /sites/site1.local/ |
| site2.local | site2.local123 | /sites/site2.local/ |

### FTP Connectivity Test
```bash
# Via command line
ftp localhost

# Via graphical client
# Host: localhost
# Port: 21
# User: localhost
# Password: localhost123
```

### Permission Fix
```bash
# FTP management script
bash docker/dados/gerenciar-ftp-sistema.sh

# Option 3: Fix web file permissions

# Or manually
docker exec conn2flow-app chmod 644 /var/www/sites/DOMAIN/public_html/file.ext
```

## 🔄 Development Synchronization

### Synchronization Script
```bash
# Main repository (this one): edit code in gestor/
# Docker repository: run synchronization scripts

# Navigate to external Docker utilities
cd ../conn2flow-docker-test-environment/utils/

# Synchronize manager → docker environment (checksum recommended)
bash sincroniza-gestor.sh checksum

# Synchronize installer
bash sincroniza-gestor-instalador.sh checksum

# Synchronize test project (TARGET example)
TARGET=teste-tailwind-php bash sincroniza-teste.sh checksum
```

### Development Flow
1. **Edit code** in `gestor/` folder
2. **Synchronize** with `sincroniza-gestor.sh checksum`
3. **Test** in Docker environment via browser
4. **Repeat** as necessary

## 📊 Monitoring and Logs

### Monitoring Commands
```bash
# Container status
docker ps

# Real-time application logs
docker logs conn2flow-app --tail 50 --follow

# PHP logs (errors)
docker exec conn2flow-app tail -f /var/log/php_errors.log

# Specific logs by domain
docker exec conn2flow-app tail -f /var/log/apache2/localhost-access.log
docker exec conn2flow-app tail -f /var/log/apache2/site1-access.log
```

### Shell Access for Diagnosis
```bash
# Enter application container
docker exec -it conn2flow-app bash

# Check PHP configuration
docker exec conn2flow-app php -v
docker exec conn2flow-app php -m

# Check file structure
docker exec conn2flow-app ls -la /var/www/sites/
```

## 🛠️ Technical Configurations

### Application Dockerfile
- **Base**: Official PHP 8.3 Apache
- **Extensions**: PDO, MySQL, GD, ZIP, XML, OpenSSL
- **Configurations**: Multi-domain, mod_rewrite enabled
- **Permissions**: Automatically configured

### Docker Compose
- **Orchestration**: 4 interconnected services
- **Volumes**: MySQL data persistence
- **Networks**: Isolated network for internal communication
- **Ports**: Mapping for host access

### PHP Configurations
```ini
# Custom php.ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

### Apache Configurations
- **Virtual Hosts**: Multi-domain configuration
- **Separate logs**: By domain for better diagnosis
- **Rewrite rules**: Enabled for friendly URLs

## 🔧 Troubleshooting

### Common Problems

#### Container does not start
```bash
# Check error logs
docker-compose logs app

# Rebuild from scratch
docker-compose down
docker-compose up --build -d
```

#### File permissions
```bash
# Fix web permissions
docker exec conn2flow-app chown -R www-data:www-data /var/www/sites

# Fix specific permissions
docker exec conn2flow-app chmod 644 /var/www/sites/localhost/public_html/index.php
```

#### MySQL connectivity problems
```bash
# Check MySQL logs
docker logs conn2flow-mysql

# Reset data (WARNING: deletes data)
docker-compose down -v
docker-compose up -d
```

#### FTP does not connect
```bash
# Check FTP logs
docker logs conn2flow-ftp

# Restart only FTP service
docker-compose restart ftp
```

### Diagnostic Scripts
```bash
# Verify installation
php docker/utils/verificar_dados.php

# Test installer
php docker/utils/teste-instalador.php

# Complete commands available
cat docker/utils/comandos-docker.md
```

## 🚀 Docker Environment Advantages

### ✅ **Technical Benefits**
- **Complete isolation**: Does not interfere with host system
- **Reproducibility**: Works identically on any machine
- **Versioning**: Configuration versioned along with code
- **Multi-domain**: Tests multiple installations simultaneously
- **Functional OpenSSL**: Linux environment with correct configurations

### ✅ **Development Benefits**
- **Quick setup**: One command line for complete environment
- **Centralized logs**: Easy problem diagnosis
- **Hot reload**: Automatic synchronization of changes
- **Clean environment**: Easy reset when necessary
- **Similar production**: Simulates real server environment

### ✅ **Demonstration Benefits**
- **Multiple sites**: Demonstrates multi-tenant capability
- **Functional installer**: Complete installation process
- **Integrated FTP**: Complete deploy workflow
- **Configured database**: Sample data ready

## 📈 Evolution and History

### Implemented Versions
- **v1.0**: Basic environment with PHP + MySQL
- **v2.0**: Multi-domain system implemented
- **v3.0**: Integrated FTP with virtual users
- **v4.0**: Management and automation scripts
- **v5.0**: Complete documentation and stabilization

### Future Improvements
- **SSL/TLS**: Automatic certificates for HTTPS
- **Monitoring**: Metrics dashboard
- **Automatic backup**: Scheduled backup routine
- **CI/CD**: Integration with deploy pipeline

## 📚 Reference Files

### Essential Documentation
- `../conn2flow-docker-test-environment/dados/DOCKER-README.md` - Complete usage guide
- `../conn2flow-docker-test-environment/dados/STATUS-FTP-FINAL.md` - FTP Status
- `../conn2flow-docker-test-environment/utils/comandos-docker.md` - Useful commands
- `../conn2flow-docker-test-environment/dados/README-FTP-SISTEMA.md` - FTP Manual

### Utility Scripts
- `../conn2flow-docker-test-environment/utils/sincroniza-gestor.sh`
- `../conn2flow-docker-test-environment/utils/sincroniza-gestor-instalador.sh`
- `../conn2flow-docker-test-environment/utils/sincroniza-teste.sh`
- `../conn2flow-docker-test-environment/dados/gerenciar-sites.sh`
- `../conn2flow-docker-test-environment/dados/gerenciar-ftp-sistema.sh`
- `../conn2flow-docker-test-environment/utils/verificar_dados.php`

### Configurations
- `../conn2flow-docker-test-environment/dados/docker-compose.yml`
- `../conn2flow-docker-test-environment/dados/Dockerfile`
- `../conn2flow-docker-test-environment/dados/Dockerfile.ftp`
- `../conn2flow-docker-test-environment/dados/sites.conf`

---

## 🎯 Conclusion

The Conn2Flow Docker environment represents a **mature and complete solution** for development, testing, and demonstrations. With more than 4 iterations of improvements, it offers:

- **🏗️ Robust infrastructure** with 4 integrated services
- **🌐 Multi-domain capability** for complex tests  
- **📁 Complete FTP system** with virtual users
- **🔄 Automation tools** for productivity
- **📊 Advanced monitoring** with detailed logs
- **🛠️ Easy debugging** with complete shell access

**Status**: ✅ **Production - Stable and Documented**  
**Last update**: September 2025  
**Developed by**: Otavio Serra + AI Agents
