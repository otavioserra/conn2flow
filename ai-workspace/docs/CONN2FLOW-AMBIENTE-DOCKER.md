# CONN2FLOW - Ambiente Docker de Desenvolvimento

## 📋 Visão Geral

O projeto Conn2Flow inclui um **ambiente Docker completo e maduro** desenvolvido especificamente para desenvolvimento, testes e demonstrações. Este ambiente oferece uma infraestrutura multi-domínio robusta que simula condições reais de produção.

## 🏗️ Arquitetura do Sistema

### Componentes Principais

```yaml
services:
  app:          # Aplicação PHP 8.3 + Apache
  mysql:        # MySQL 8.0 com dados iniciais
  phpmyadmin:   # Interface web para banco
  ftp:          # Servidor FTP multi-domínio (ProFTPD)
```

### Estrutura Multi-Domínio

```
docker/dados/sites/
├── localhost/              # Domínio principal de desenvolvimento
│   ├── conn2flow-gestor/   # Sistema principal (sincronizado)
│   ├── public_html/        # Arquivos web públicos
│   │   └── instalador/     # Instalador web
│   └── home/               # Arquivos privados
├── site1.local/            # Site de teste 1
│   ├── public_html/        # Arquivos web públicos
│   └── home/               # Arquivos privados
└── site2.local/            # Site de teste 2
    ├── public_html/        # Arquivos web públicos
    └── home/               # Arquivos privados
```

## 🚀 Configuração e Uso

### Pré-requisitos
- Docker Desktop instalado e configurado
- Git Bash ou terminal compatível
- Portas disponíveis: 80, 3306, 8081, 21

### Inicialização Rápida

```bash
# Navegar para o diretório Docker
cd docker/dados/

# Construir e iniciar todos os serviços
docker-compose up --build -d

# Verificar status dos containers
docker ps
```

### Acesso aos Serviços

| Serviço | URL/Endereço | Credenciais |
|---------|--------------|-------------|
| **Aplicação Principal** | http://localhost | - |
| **Instalador** | http://localhost/instalador/ | - |
| **phpMyAdmin** | http://localhost:8081 | root / root123 |
| **MySQL** | localhost:3306 | conn2flow_user / conn2flow_pass |
| **FTP** | localhost:21 | Ver seção FTP |

## 💾 Configuração de Banco de Dados

### Credenciais de Desenvolvimento
```env
# Para aplicação
MYSQL_HOST=mysql
MYSQL_DATABASE=conn2flow
MYSQL_USER=conn2flow_user
MYSQL_PASSWORD=conn2flow_pass

# Para administração
MYSQL_ROOT_USER=root
MYSQL_ROOT_PASSWORD=root123
```

### Inicialização Automática
- Schema SQL automaticamente carregado na inicialização
- Dados de exemplo e estrutura completa
- Reset disponível via `docker-compose down -v` (CUIDADO: apaga dados)

## 🌐 Sistema Multi-Domínio

### Configuração de Hosts (Opcional)
Para testar múltiplos domínios no navegador, adicione ao arquivo hosts:

**Windows:** `C:\Windows\System32\drivers\etc\hosts`
**Linux/macOS:** `/etc/hosts`

```
127.0.0.1 site1.local
127.0.0.1 site2.local
```

### Teste via curl
```bash
# Domínio principal
curl "http://localhost"

# Sites de teste
curl -H "Host: site1.local" "http://localhost"
curl -H "Host: site2.local" "http://localhost"
```

### Gerenciamento de Sites
```bash
# Script utilitário para gerenciar sites
cd docker/utils/

# Listar sites existentes
bash gerenciar-sites.sh listar

# Criar novo site
bash gerenciar-sites.sh criar novosite.local

# Copiar instalador para um site
bash gerenciar-sites.sh copiar-instalador site1.local
```

## 📁 Sistema FTP Multi-Domínio

### Características
- **ProFTPD** com usuários virtuais
- **Mapeamento direto**: pasta do domínio = raiz FTP do usuário
- **Detecção automática** de novos domínios
- **Integração completa** com sistema web

### Usuários FTP Configurados
| Usuário | Senha | Raiz FTP |
|---------|-------|----------|
| localhost | localhost123 | /sites/localhost/ |
| site1.local | site1.local123 | /sites/site1.local/ |
| site2.local | site2.local123 | /sites/site2.local/ |

### Teste de Conectividade FTP
```bash
# Via linha de comando
ftp localhost

# Via cliente gráfico
# Host: localhost
# Porta: 21
# Usuário: localhost
# Senha: localhost123
```

### Correção de Permissões
```bash
# Script de gerenciamento FTP
bash docker/dados/gerenciar-ftp-sistema.sh

# Opção 3: Corrigir permissões de arquivos web

# Ou manualmente
docker exec conn2flow-app chmod 644 /var/www/sites/DOMINIO/public_html/arquivo.ext
```

## 🔄 Sincronização de Desenvolvimento

### Script de Sincronização
```bash
# Sincronizar alterações do gestor para Docker
cd docker/utils/

# Modo padrão (baseado em data/hora)
bash sincroniza-gestor.sh

# Modo checksum (compara conteúdo)
bash sincroniza-gestor.sh checksum

# Modo força (sobrescreve tudo)
bash sincroniza-gestor.sh forcar
```

### Fluxo de Desenvolvimento
1. **Editar código** na pasta `gestor/`
2. **Sincronizar** com `sincroniza-gestor.sh checksum`
3. **Testar** no ambiente Docker via navegador
4. **Repetir** conforme necessário

## 📊 Monitoramento e Logs

### Comandos de Monitoramento
```bash
# Status dos containers
docker ps

# Logs em tempo real da aplicação
docker logs conn2flow-app --tail 50 --follow

# Logs de PHP (erros)
docker exec conn2flow-app tail -f /var/log/php_errors.log

# Logs específicos por domínio
docker exec conn2flow-app tail -f /var/log/apache2/localhost-access.log
docker exec conn2flow-app tail -f /var/log/apache2/site1-access.log
```

### Acesso Shell para Diagnóstico
```bash
# Entrar no container da aplicação
docker exec -it conn2flow-app bash

# Verificar configuração PHP
docker exec conn2flow-app php -v
docker exec conn2flow-app php -m

# Verificar estrutura de arquivos
docker exec conn2flow-app ls -la /var/www/sites/
```

## 🛠️ Configurações Técnicas

### Dockerfile da Aplicação
- **Base**: PHP 8.3 Apache oficial
- **Extensões**: PDO, MySQL, GD, ZIP, XML, OpenSSL
- **Configurações**: Multi-domínio, mod_rewrite habilitado
- **Permissões**: Configuradas automaticamente

### Docker Compose
- **Orquestração**: 4 serviços interconectados
- **Volumes**: Persistência de dados MySQL
- **Networks**: Rede isolada para comunicação interna
- **Ports**: Mapeamento para acesso do host

### Configurações PHP
```ini
# php.ini customizado
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

### Configurações Apache
- **Virtual Hosts**: Configuração multi-domínio
- **Logs separados**: Por domínio para melhor diagnóstico
- **Rewrite rules**: Habilitado para URLs amigáveis

## 🔧 Solução de Problemas

### Problemas Comuns

#### Container não inicia
```bash
# Verificar logs de erro
docker-compose logs app

# Rebuildar do zero
docker-compose down
docker-compose up --build -d
```

#### Permissões de arquivo
```bash
# Corrigir permissões web
docker exec conn2flow-app chown -R www-data:www-data /var/www/sites

# Corrigir permissões específicas
docker exec conn2flow-app chmod 644 /var/www/sites/localhost/public_html/index.php
```

#### Problemas de conectividade MySQL
```bash
# Verificar logs do MySQL
docker logs conn2flow-mysql

# Resetar dados (CUIDADO: apaga dados)
docker-compose down -v
docker-compose up -d
```

#### FTP não conecta
```bash
# Verificar logs FTP
docker logs conn2flow-ftp

# Reiniciar apenas o serviço FTP
docker-compose restart ftp
```

### Scripts de Diagnóstico
```bash
# Verificar instalação
php docker/utils/verificar_dados.php

# Testar instalador
php docker/utils/teste-instalador.php

# Comandos completos disponíveis
cat docker/utils/comandos-docker.md
```

## 🚀 Vantagens do Ambiente Docker

### ✅ **Benefícios Técnicos**
- **Isolamento completo**: Não interfere no sistema host
- **Reprodutibilidade**: Funciona identicamente em qualquer máquina
- **Versionamento**: Configuração versionada junto com código
- **Multi-domínio**: Testa múltiplas instalações simultaneamente
- **OpenSSL funcional**: Ambiente Linux com configurações corretas

### ✅ **Benefícios de Desenvolvimento**
- **Setup rápido**: Uma linha de comando para ambiente completo
- **Logs centralizados**: Fácil diagnóstico de problemas
- **Hot reload**: Sincronização automática de alterações
- **Ambiente limpo**: Reset fácil quando necessário
- **Produção similar**: Simula ambiente de servidor real

### ✅ **Benefícios de Demonstração**
- **Múltiplos sites**: Demonstra capacidade multi-tenant
- **Instalador funcional**: Processo completo de instalação
- **FTP integrado**: Workflow completo de deploy
- **Banco configurado**: Dados de exemplo prontos

## 📈 Evolução e Histórico

### Versões Implementadas
- **v1.0**: Ambiente básico com PHP + MySQL
- **v2.0**: Sistema multi-domínio implementado
- **v3.0**: FTP integrado com usuários virtuais
- **v4.0**: Scripts de gerenciamento e automação
- **v5.0**: Documentação completa e estabilização

### Melhorias Futuras
- **SSL/TLS**: Certificados automáticos para HTTPS
- **Monitoramento**: Dashboard de métricas
- **Backup automático**: Rotina de backup agendada
- **CI/CD**: Integração com pipeline de deploy

## 📚 Arquivos de Referência

### Documentação Essencial
- `docker/dados/DOCKER-README.md` - Guia completo de uso
- `docker/dados/STATUS-FTP-FINAL.md` - Status do sistema FTP
- `docker/utils/comandos-docker.md` - Comandos úteis
- `docker/dados/README-FTP-SISTEMA.md` - Manual detalhado FTP

### Scripts Utilitários
- `docker/utils/sincroniza-gestor.sh` - Sincronização de código
- `docker/dados/gerenciar-sites.sh` - Gerenciamento de sites
- `docker/dados/gerenciar-ftp-sistema.sh` - Gerenciamento FTP
- `docker/utils/verificar_dados.php` - Diagnóstico do sistema

### Configurações
- `docker/dados/docker-compose.yml` - Orquestração dos serviços
- `docker/dados/Dockerfile` - Container da aplicação
- `docker/dados/Dockerfile.ftp` - Container FTP
- `docker/dados/sites.conf` - Configuração Apache multi-domínio

---

## 🎯 Conclusão

O ambiente Docker do Conn2Flow representa uma **solução madura e completa** para desenvolvimento, testes e demonstrações. Com mais de 4 iterações de melhorias, oferece:

- **🏗️ Infraestrutura robusta** com 4 serviços integrados
- **🌐 Capacidade multi-domínio** para testes complexos  
- **📁 Sistema FTP completo** com usuários virtuais
- **🔄 Ferramentas de automação** para produtividade
- **📊 Monitoramento avançado** com logs detalhados
- **🛠️ Debugging facilitado** com acesso shell completo

**Status**: ✅ **Produção - Estável e Documentado**  
**Última atualização**: Agosto 2025  
**Desenvolvido por**: Otavio Serra + Agentes IA
