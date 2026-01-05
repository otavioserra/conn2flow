````markdown
# Docker Commands - Conn2Flow Environment (Multi-Domain)

## 1. Check container status
```bash
docker ps
```

## 2. Execute PHP
```bash
docker exec conn2flow-app bash -c "php -v"
```

## 3. Main container logs (Apache/PHP)
```bash
docker logs conn2flow-app --tail 50                    # Last 50 lines
docker logs conn2flow-app --tail 50 --follow           # Follow in real time
```

## 4. PHP error logs (MOST IMPORTANT)
```bash
docker exec conn2flow-app bash -c "tail -50 /var/log/php_errors.log"
docker exec conn2flow-app bash -c "tail -f /var/log/php_errors.log"    # Real time
```

## 5. MySQL Logs
```bash
docker logs conn2flow-mysql --tail 30
```

## 6. Apache Logs (inside container) - Multi-domain
```bash
# General logs
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/error.log"

# Specific logs by site
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/localhost-access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/localhost-error.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site1-access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site1-error.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site2-access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site2-error.log"
```

## 7. Shell access for manual investigation
```bash
docker exec -it conn2flow-app bash
```

## 8. Check installation logs (if exists)
```bash
docker exec conn2flow-app bash -c "find /var/www/sites -name '*.log' -exec tail -20 {} +"
```

## 9. Site Management (NEW)
```bash
# List all sites
bash ./gerenciar-sites.sh listar

# Create new site
bash ./gerenciar-sites.sh criar meusite.local

# Clean site content
bash ./gerenciar-sites.sh limpar site1.local

# Copy installer to a site
bash ./gerenciar-sites.sh copiar-instalador site2.local
```

## 9. Multi-Domain Folder Structure
```
sites/
├── localhost/              # Main domain
│   ├── home/              # Secure folder (conn2flow)
│   └── public_html/       # Public web folder
├── site1.local/           # Test site 1
│   ├── home/
│   └── public_html/
└── site2.local/           # Test site 2
    ├── home/
    └── public_html/
```

````