# Guia Docker Multi-Domínio - conn2flow

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

# Logs específicos por domínio
docker-compose exec app tail -f /var/log/apache2/localhost-access.log
docker-compose exec app tail -f /var/log/apache2/site1-access.log
docker-compose exec app tail -f /var/log/apache2/site2-access.log
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

- **Aplicação Principal**: http://localhost
- **Instalador**: http://localhost/instalador/
- **Site 1**: http://localhost (com Host: site1.local)
- **Site 2**: http://localhost (com Host: site2.local)
- **phpMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306

## Estrutura Multi-Domínio

O ambiente agora suporta múltiplos domínios com isolamento completo:

```
sites/
├── localhost/
│   ├── home/
│   │   └── conn2flow-gestor/    # Sistema principal
│   └── public_html/
│       ├── index.html           # Página inicial
│       └── instalador/          # Instalador do sistema
├── site1.local/
│   ├── home/
│   └── public_html/
│       └── index.html           # Site de teste 1
└── site2.local/
    ├── home/
    └── public_html/
        └── index.html           # Site de teste 2
```

## Gerenciamento de Sites

Use o script `gerenciar-sites.sh` para gerenciar os sites:

```bash
# Listar sites existentes
bash gerenciar-sites.sh listar

# Criar um novo site
bash gerenciar-sites.sh criar novosite.local

# Limpar conteúdo de um site
bash gerenciar-sites.sh limpar site1.local

# Copiar instalador para um site
bash gerenciar-sites.sh copiar-instalador site1.local
```

## Configurações do Banco de Dados

```
Host: mysql (dentro do container) ou localhost:3306 (do host)
Database: conn2flow
Usuário: conn2flow_user
Senha: conn2flow_pass
Usuário Root: root
Senha Root: root123
```

## Teste dos Domínios

### Via curl (linha de comando):
```bash
# Domínio principal
curl "http://localhost"

# Site 1
curl -H "Host: site1.local" "http://localhost"

# Site 2  
curl -H "Host: site2.local" "http://localhost"
```

### Para teste no navegador:
Adicione as seguintes linhas ao arquivo hosts do sistema:
- **Windows**: `C:\Windows\System32\drivers\etc\hosts`
- **Linux/Mac**: `/etc/hosts`

```
127.0.0.1 site1.local
127.0.0.1 site2.local
```

## Vantagens do Ambiente Multi-Domínio

✅ **Teste de múltiplas instalações** simultaneamente
✅ **Isolamento completo** entre sites
✅ **Logs separados** por domínio
✅ **Compatibilidade total** com instalação existente
✅ **Fácil criação** de novos sites de teste
✅ **OpenSSL funcionando** perfeitamente
✅ **PHP 8.3** otimizado
✅ **MySQL configurado** automaticamente

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
