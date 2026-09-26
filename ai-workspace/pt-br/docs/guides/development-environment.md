---
title: "Prepare o ambiente de desenvolvimento"
description: "Docker local, opção PHP 8.5 e túnel Cloudflare conforme os arquivos do repositório."
section: guides
sources:
  - dev-environment/docker/docker-compose.yml
  - dev-environment/docker/Dockerfile.php85
  - dev-environment/docker/docker-compose.php85-mysql.yml
  - dev-environment/docker/docker-compose.php85-pgsql.yml
  - cli/src/Commands/ManagerUpdateAllCommand.php
verified_at: e5b61f8e
---

# Prepare o ambiente de desenvolvimento

O ambiente de teste fica em `dev-environment/docker/`. O Compose padrão monta `../data/sites/localhost/public_html` no DocumentRoot do contêiner `app`, liga MySQL 8, phpMyAdmin e oferece Redis/Memcached sob o perfil `optional`. Portas locais padrão no YAML: HTTP 80, MySQL 3306 e phpMyAdmin 8081. Confira conflitos de porta antes de subir a pilha.

```sh
cd dev-environment/docker
docker compose up -d
docker compose ps
```

O arquivo `docker-compose.php85-mysql.yml` usa `Dockerfile.php85` (`php:8.5-apache`); há também a variante PostgreSQL. São pilhas alternativas que publicam a mesma porta HTTP, portanto execute uma de cada vez. O Dockerfile 8.5 instala `pdo_mysql`, `pdo_pgsql`, `mysqli`, `mbstring`, `gd`, `zip` e `xml`. Comentários antigos dessas variantes citam `banco-v2.php`; a biblioteca foi removida do Core.

## Túnel opcional

O serviço `cloudflared` está no perfil `tunnel` e lê `../data/cloudflared` como configuração somente leitura. Configure suas credenciais fora do repositório e inicie com `docker compose --profile tunnel up -d`; acompanhe com `docker compose logs -f cloudflared`. A imagem é distroless: não há shell no contêiner. Não exponha o ambiente com credenciais de produção.

## Sincronize o Core

O Compose monta o espelho de testes, mas os arquivos de autoria do Core não chegam ao banco por edição direta. Use `php cli/c2f.php manager:update-all` na raiz do Core para compilar recursos, sincronizar arquivos e atualizar o banco. Execute pipelines sequencialmente. Veja [recursos](../concepts/resources.md) e [CLI do gestor](../reference/cli/manager.md).
