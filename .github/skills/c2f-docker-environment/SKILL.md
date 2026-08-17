---
name: c2f-docker-environment
description: Use ao interagir com o ambiente de containers Docker do Conn2Flow: container conn2flow-app, verificação/truncamento de php_errors.log e comandos CLI.
user-invocable: false
---

# Ambiente Docker Conn2Flow (`CONN2FLOW-DOCKER-ENVIRONMENT.md`)

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
