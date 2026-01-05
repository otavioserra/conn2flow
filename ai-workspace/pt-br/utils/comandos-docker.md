# Comandos Docker - Ambiente Conn2Flow (Multi-Domínio)

## 1. Verificar status dos containers
```bash
docker ps
```

## 2. Executar o PHP
```bash
docker exec conn2flow-app bash -c "php -v"
```

## 3. Logs do container principal (Apache/PHP)
```bash
docker logs conn2flow-app --tail 50                    # Últimas 50 linhas
docker logs conn2flow-app --tail 50 --follow           # Acompanhar em tempo real
```

## 4. Logs PHP de erros (MAIS IMPORTANTE)
```bash
docker exec conn2flow-app bash -c "tail -50 /var/log/php_errors.log"
docker exec conn2flow-app bash -c "tail -f /var/log/php_errors.log"    # Tempo real
```

## 5. Logs do MySQL
```bash
docker logs conn2flow-mysql --tail 30
```

## 6. Logs do Apache (dentro do container) - Multi-domínio
```bash
# Logs gerais
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/error.log"

# Logs específicos por site
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/localhost-access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/localhost-error.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site1-access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site1-error.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site2-access.log"
docker exec conn2flow-app bash -c "tail -30 /var/log/apache2/site2-error.log"
```

## 7. Acesso shell para investigação manual
```bash
docker exec -it conn2flow-app bash
```

## 8. Verificar logs de instalação (se existir)
```bash
docker exec conn2flow-app bash -c "find /var/www/sites -name '*.log' -exec tail -20 {} +"
```

## 9. Gerenciamento de Sites (NOVO)
```bash
# Listar todos os sites
bash ./gerenciar-sites.sh listar

# Criar novo site
bash ./gerenciar-sites.sh criar meusite.local

# Limpar conteúdo de um site
bash ./gerenciar-sites.sh limpar site1.local

# Copiar instalador para um site
bash ./gerenciar-sites.sh copiar-instalador site2.local
```

## 9. Estrutura de Pastas Multi-Domínio
```
sites/
├── localhost/              # Domínio principal
│   ├── home/              # Pasta segura (conn2flow)
│   └── public_html/       # Pasta web pública
├── site1.local/           # Site de teste 1
│   ├── home/
│   └── public_html/
└── site2.local/           # Site de teste 2
    ├── home/
    └── public_html/
```
