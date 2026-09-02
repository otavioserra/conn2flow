---
name: c2f-docker-environment
description: "LEIA ANTES de decidir ONDE um site do Gestor roda e como alcançá-lo: containers Docker (lumix-api, mysql, redis) ou a VM Ubuntu + HestiaCP em 192.168.1.108, que substituiu o conn2flow-app para conn2flow.local e snapphoton.local. Se não ler: comandos docker exec falham em container inexistente, portas entram em conflito ou o open_basedir do pool PHP-FPM faz arquivos existentes parecerem ausentes."
user-invocable: false
---

# Ambiente Docker Conn2Flow (`CONN2FLOW-DOCKER-ENVIRONMENT.md`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Subir, descer, reiniciar ou executar comandos dentro dos containers Docker do ambiente de desenvolvimento.
- **SKIP APENAS SE**: Ambientes nativos locais sem contêineres ou tarefas documentais puras.
- **CONSEQUÊNCIA DE IGNORAR**: Falhas de conexão com o banco de dados, execução em versão incorreta de runtime (PHP 8.2 vs 8.5) ou perda de persistência nos volumes locais.

---

Consulte e aplique as seguintes convenções para operar no ambiente de containerização local:

## 1. Estrutura dos Containers

* **Container Principal**: `conn2flow-app` (Roda Apache + PHP 8.x).
* **Portas**: HTTP `80` (redirecionada localmente para `8080` ou porta configurada no docker-compose).

---

## 2. Inspeção de Logs de Erro do PHP

* **Visualizar Últimas 50 Linhas do Erro PHP**:
  ```bash
  docker exec conn2flow-app bash -c "tail -50 /var/log/php_errors.log"
  ```
* **Acompanhar Logs PHP em Tempo Real**:
  ```bash
  docker exec conn2flow-app bash -c "tail -f /var/log/php_errors.log"
  ```
* **Limpar/Truncar Arquivo de Erro PHP**:
  ```bash
  docker exec conn2flow-app bash -c "truncate -s 0 /var/log/php_errors.log"
  ```

---

## 3. Execução de Comandos PHP no Container

* **Verificar Versão do PHP**:
  ```bash
  docker exec conn2flow-app bash -c "php -v"
  ```
* **Executar Linter PHP**:
  ```bash
  docker exec conn2flow-app bash -c "php -l /caminho/do/arquivo.php"
  ```

---

## 4. ⚠️ O ambiente mudou: VM Ubuntu + HestiaCP (req-034)

> [!IMPORTANT]
> **`conn2flow-app` não existe mais para os tenants do Gestor.** Os sites de
> desenvolvimento migraram do container para uma VM VirtualBox
> (**Ubuntu 22.04 + HestiaCP + MariaDB + Redis**) em `192.168.1.108`,
> resolvida pelo `hosts` do Windows como `lab.conn2flow.local`.
> Antes de escrever `docker exec conn2flow-app ...`, **confirme se o container existe**:
> `docker ps --format '{{.Names}}'`. Se não estiver na lista, o caminho é SSH.

### Onde cada tenant vive na VM

| Domínio | Usuário HestiaCP | Raiz do Gestor |
| --- | --- | --- |
| `conn2flow.local` | `admin` | `/home/admin/web/conn2flow.local/conn2flow-gestor/` |
| `snapphoton.local` | `snapphoton` | `/home/snapphoton/web/snapphoton.local/conn2flow-gestor/` |

O `public_html/` guarda apenas o `index.php` que faz `require` do Gestor no
diretório irmão — não é lá que o código fica.

### Equivalências de comando

| Antes (Docker) | Agora (VM) |
| --- | --- |
| `docker exec conn2flow-app php -v` | `ssh otavio@192.168.1.108 'php -v'` |
| `docker exec conn2flow-app php -l <arq>` | `ssh otavio@192.168.1.108 'php -l <caminho-na-vm>'` |
| `tail -f /var/log/php_errors.log` | `sudo tail -f /var/log/nginx/domains/<dominio>.error.log` |

O acesso SSH por chave já está publicado para `otavio`, que tem `sudo` sem senha.
O usuário do pool PHP-FPM (`admin`, `snapphoton`) **não** tem sudo — para rodar
PHP com as permissões do tenant use `sudo -u admin php ...`, a partir da raiz do
Gestor (o `config.php` exige esse diretório de trabalho).

### O que ainda é Docker

Os containers do **Lumix/Photon** (`lumix-api`, `lumix-vector-db`, `lumix-redis`,
`pgadmin`) e o `conn2flow-mcp-hub` continuam no Docker do Windows. A migração
atingiu o Gestor PHP, não esse conjunto.

### `open_basedir` é a armadilha nova

Cada pool PHP-FPM do HestiaCP tem `open_basedir` restrito ao próprio tenant. Isso
NÃO bloqueia `proc_open`/`exec`, mas faz `is_file()`, `is_dir()` e
`is_executable()` devolverem `false` para qualquer caminho de fora — inclusive
`/usr/local/hestia/bin/`. Uma checagem prévia de existência ali reporta "arquivo
não encontrado" para um arquivo que existe. Confira a cerca antes de concluir
ausência:

```bash
sudo grep open_basedir /etc/php/8.5/fpm/pool.d/<dominio>.conf
```
