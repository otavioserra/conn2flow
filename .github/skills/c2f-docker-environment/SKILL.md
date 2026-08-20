---
name: c2f-docker-environment
description: "LEIA ANTES de interagir com containers Docker (conn2flow-app, mysql, redis, etc.). Se não ler: portas entram em conflito, dados locais não sincronizam ou comandos rodam no host com versões incompatíveis de PHP/MySQL."
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
