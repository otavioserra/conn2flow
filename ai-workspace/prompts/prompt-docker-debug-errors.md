# Gestor Docker Debug - Conn2Flow

## 🎯 Contexto Inicial
- Você está trabalhando no projeto Conn2Flow no controle de erros do ambiente de testes usando Docker. Outros agentes de ia estão rodando outras tarefas e a sua é única e exclusivamente nas tarefas do Docker.
- **Erros de instalação:** O arquivo de log do instalador está localizado em: `docker\dados\public_html\instalador\installer.log`

## 📋 Sequência de Comandos
- **Pasta do Docker:** Verifique se vc está na pasta certa: `docker/dados`.
```
pwd
```
Senão estiver acesse a pasta:
```
cd docker/dados
```
- **Verificar erros de instalação:**
```
docker compose exec app bash -c "cat /var/www/html/instalador/installer.log"
```
- **Verificar erros do Apache:**
```
docker compose logs app --tail=100
```
- **Verificar erros do PHP:**
```
docker compose exec app bash -c "tail -n 100 /var/log/php_errors.log"
```

## 🌐 Resultado:
- Analise os logs e me dê as opções de correção.

## 🔧 Caso tenha dúvidas
Se tiver alguma dúvida, pode me perguntar e fazemos em mais de uma requisição.

