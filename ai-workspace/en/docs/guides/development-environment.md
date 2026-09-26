---
title: "Set up the development environment"
description: "Local Docker, PHP 8.5 option and Cloudflare Tunnel according to repository files."
section: guides
sources:
  - dev-environment/docker/docker-compose.yml
  - dev-environment/docker/Dockerfile.php85
  - dev-environment/docker/docker-compose.php85-mysql.yml
  - dev-environment/docker/docker-compose.php85-pgsql.yml
  - cli/src/Commands/ManagerUpdateAllCommand.php
verified_at: e5b61f8e
---

# Set up the development environment

The test environment lives in `dev-environment/docker/`. Default Compose mounts `../data/sites/localhost/public_html` as the `app` container DocumentRoot, starts MySQL 8 and phpMyAdmin, and offers Redis/Memcached under the `optional` profile. YAML default local ports are HTTP 80, MySQL 3306 and phpMyAdmin 8081. Check port conflicts before starting.

```sh
cd dev-environment/docker
docker compose up -d
docker compose ps
```

`docker-compose.php85-mysql.yml` uses `Dockerfile.php85` (`php:8.5-apache`); a PostgreSQL variant also exists. These are alternative stacks publishing the same HTTP port, so run one at a time. The 8.5 Dockerfile installs `pdo_mysql`, `pdo_pgsql`, `mysqli`, `mbstring`, `gd`, `zip` and `xml`. Old comments in those variants mention `banco-v2.php`; that library was removed from Core.

## Optional tunnel

The `cloudflared` service uses the `tunnel` profile and reads `../data/cloudflared` as read only configuration. Keep credentials outside the repository and start with `docker compose --profile tunnel up -d`; inspect `docker compose logs -f cloudflared`. The image is distroless and has no shell. Do not expose an environment with production credentials.

## Synchronize Core

Compose mounts the test mirror, but editing Core authoring files does not update the database. Run `php cli/c2f.php manager:update-all` at the Core root to compile resources, sync files and update the database. Run pipelines sequentially. See [resources](../concepts/resources.md) and [manager CLI](../reference/cli/manager.md).
