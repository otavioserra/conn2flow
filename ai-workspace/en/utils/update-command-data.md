```markdown
# Command Data Updates

## Command Sequence
1. Update data files coming from resources: `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`.
2. Synchronize data in test environment: `bash docker/utils/sincroniza-gestor.sh checksum`
3. Update data in test environment: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff"`
```