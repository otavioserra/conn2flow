```markdown
## CONTAINER VERIFICATION

1. CHECK CONTAINER STATUS
docker ps

2. MAIN CONTAINER LOGS (Apache/PHP)
docker logs conn2flow-app --tail 50 # Last 50 lines
docker logs conn2flow-app --tail 50 --follow # Follow in real time

3. PHP ERROR LOGS (MOST IMPORTANT)
docker exec conn2flow-app bash -c "tail -50 /var/log/php_errors.log"
docker exec conn2flow-app bash -c "tail -f /var/log/php_errors.log" # Real time

4. MYSQL LOGS
docker logs conn2flow-mysql --tail 30

5. APACHE LOGS (INSIDE CONTAINER)
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/error.log"

6. SHELL ACCESS FOR MANUAL INVESTIGATION
docker exec -it conn2flow-app bash

7. CHECK INSTALLATION LOGS (if exists)
docker exec conn2flow-app bash -c "ls -la /var/www/sites/localhost/conn2flow-gestor/"
docker exec conn2flow-app bash -c "find /var/www/sites/localhost/public_html/ -name '*.log' -exec tail -20 {} +"
```