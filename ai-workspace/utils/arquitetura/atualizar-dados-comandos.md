# Atualizações De Dados Comandos

## Sequência de Comandos
1. Atualizar os arquivos de dados vindo dos recursos: `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`.
2. Sincronizar os dados no ambiente de testes: `bash docker/utils/sincroniza-gestor.sh checksum`
3. Atualizar os dados no ambiente de testes: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff"`