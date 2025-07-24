# Guia de Desenvolvimento Docker - conn2flow

## Pré-requisitos
- Docker Desktop instalado
- Docker Compose (já incluído no Docker Desktop)

## Comandos Básicos

### 1. Construir e iniciar os containers
```bash
docker-compose up --build -d
```

### 2. Parar os containers
```bash
docker-compose down
```

### 3. Ver logs em tempo real
```bash
# Todos os serviços
docker-compose logs -f

# Apenas a aplicação
docker-compose logs -f app

# Apenas o MySQL
docker-compose logs -f mysql
```

### 4. Executar comandos no container da aplicação
```bash
# Entrar no container
docker-compose exec app bash

# Executar comando específico
docker-compose exec app php -v
```

### 5. Reiniciar apenas um serviço
```bash
docker-compose restart app
```

## Acesso aos Serviços

- **Aplicação conn2flow**: http://localhost:8080
- **Instalador**: http://localhost:8080/gestor-instalador
- **phpMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306

## Configurações do Banco de Dados

```
Host: mysql (dentro do container) ou localhost:3306 (do host)
Database: conn2flow
Usuário: conn2flow_user
Senha: conn2flow_pass
Usuário Root: root
Senha Root: root123
```

## Desenvolvimento

### Arquivos que são sincronizados em tempo real:
- `./gestor/` → `/var/www/html/gestor/`
- `./gestor-instalador/` → `/var/www/html/gestor-instalador/`
- `./cpanel/` → `/var/www/html/cpanel/`
- `./gestor-cliente/` → `/var/www/html/gestor-cliente/`
- `./index.php` → `/var/www/html/index.php`

### Para aplicar mudanças no Dockerfile ou configurações:
```bash
docker-compose down
docker-compose up --build -d
```

## Resolução de Problemas

### Problema com permissões:
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html
```

### Limpar volumes do banco (CUIDADO - apaga dados):
```bash
docker-compose down -v
docker-compose up --build -d
```

### Ver status dos containers:
```bash
docker-compose ps
```

### Ver uso de recursos:
```bash
docker stats
```

## Vantagens do Ambiente Docker

✅ **OpenSSL funcionará perfeitamente** (ambiente Linux com configurações corretas)
✅ **PHP 8.3** mais recente e otimizado
✅ **Ambiente isolado** não interfere no sistema host
✅ **Fácil de replicar** em qualquer máquina
✅ **Simula ambiente de produção** Linux
✅ **MySQL configurado automaticamente**
✅ **phpMyAdmin incluído** para administração
✅ **Logs centralizados** e fáceis de acessar

## Migração dos Dados Existentes

Se você tem dados no XAMPP que quer migrar:

1. Exporte o banco do phpMyAdmin do XAMPP
2. Importe no phpMyAdmin do Docker (http://localhost:8081)
3. Ajuste as configurações de conexão se necessário

## Próximos Passos

1. Execute `docker-compose up --build -d`
2. Acesse http://localhost:8080/gestor-instalador
3. Configure usando:
   - Host do banco: `mysql`
   - Database: `conn2flow`
   - Usuário: `conn2flow_user`
   - Senha: `conn2flow_pass`
