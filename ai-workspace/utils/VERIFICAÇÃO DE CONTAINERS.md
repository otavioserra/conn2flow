## VERIFICAÇÃO DE CONTAINERS

1. VERIFICAR STATUS DOS CONTAINERS
docker ps

2. LOGS DO CONTAINER PRINCIPAL (Apache/PHP)
docker logs conn2flow-app --tail 50 # Últimas 50 linhas
docker logs conn2flow-app --tail 50 --follow # Acompanhar em tempo real

3. LOGS PHP DE ERROS (MAIS IMPORTANTE)
docker exec conn2flow-app bash -c "tail -50 /var/log/php_errors.log"
docker exec conn2flow-app bash -c "tail -f /var/log/php_errors.log" # Tempo real

4. LOGS DO MYSQL
docker logs conn2flow-mysql --tail 30

5. LOGS DO APACHE (DENTRO DO CONTAINER)
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/error.log"

6. ACESSO SHELL PARA INVESTIGAÇÃO MANUAL
docker exec -it conn2flow-app bash

7. VERIFICAR LOGS DE INSTALAÇÃO (se existir)
docker exec conn2flow-app bash -c "ls -la /var/www/sites/localhost/conn2flow-gestor/"
docker exec conn2flow-app bash -c "find /var/www/sites/localhost/public_html/ -name '*.log' -exec tail -20 {} +"