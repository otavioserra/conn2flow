# Prompt Interactive Programming - Atualização Banco de Dados e Outros

## 🎯 Contexto Inicial
- FUNDAMENTAL: Analise o contexto anterior antes de seguir com as orientações abaixo na classe original da instalação : `gestor-instalador\src\Installer.php`.

## 📝 Orientações para o Agente
1. Vamos remover as operações para gerar as migrações e seeders definidas nessa classe e executar o mesmo script do sistema de atualização definida aqui no lugar dessa lógica: `gestor\controladores\atualizacoes\atualizacoes-banco-de-dados.php`. Este script sempre virá dentro do `gestor.zip`, então têm que ser depois de descompactar o arquivo do Gestor.
2. Vamos abandonar o uso dos seeders, uma vez que temos uma rotina de atualização dos dados que verifica se existe, atualiza quando necessário, senão existe, simplesmente inclui os dados. Fazendo com que os seeders não sejam mais necessários. Inclusive já detelei a pasta `gestor\db\seeds` e seus arquivos antes de executar esse prompt com vc. Portanto, remova qualquer referência aos seeders na instalação.
3. Depois de atualizar o banco de dados corretamente, não será mais necessário a pasta `gestor\db`. Portanto, depois do banco ter sido atualizado com sucesso, pode remover completamente esta pasta.
4. Há um problema na modificação do arquivo `.htaccess` que é processado na linha 951 do `gestor-instalador\src\Installer.php`. No meu teste, onde instalei na pasta `public_html/instalador/`, ele não modificou corretamente o `RewriteBase`, ficando assim o arquivo:
```
<IfModule mod_rewrite.c>
	RewriteEngine On
	
	RewriteCond %{SCRIPT_FILENAME} !-f
	RewriteCond %{SCRIPT_FILENAME} !-d
	RewriteRule ^(.*)$ index.php?_gestor-caminho=$1&%{QUERY_STRING}
</IfModule>
```
- Mas o correto seria ter ficado assim:
```
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase /instalador/
	RewriteCond %{SCRIPT_FILENAME} !-f
	RewriteCond %{SCRIPT_FILENAME} !-d
	RewriteRule ^(.*)$ index.php?_gestor-caminho=$1&%{QUERY_STRING}
</IfModule>
```
5. Gerar mensagem detalhada e resumo da tag, substituir as mensagens do script e executar o script do GIT à seguir: `./ai-workspace/scripts/release-instalador.sh minor "Resumo para a Tag" "Mensagem detalhada para o Commit"`. Esse script faz todas as operações necessárias para criar a tag. Portanto, é só executar ele. Analise ele caso queira entender com mais profundidade.

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [x] Remoção migrações/seeders e criação de fluxo de atualização central
- [x] Remoção referências a seeders e métodos relacionados
- [x] Remoção automática da pasta gestor/db após atualização
- [x] Correção da lógica RewriteBase no .htaccess
- [x] Execução script de release com resumo e commit detalhado (instalador-v1.1.0)

---
**Data:** 18/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.1.0