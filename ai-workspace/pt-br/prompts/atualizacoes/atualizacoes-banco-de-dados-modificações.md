# Prompt Interactive Programming - Atualizações Banco De Dados Modificações

## 🤖 Agente de IA - Responsabilidades
- **Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação.
- **GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto.
- **Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada.

## 🎯 Contexto Inicial
- Definições de toda a infraestrutura de programação que serão usados pelo agente de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- O agente usará este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar este arquivo qualquer parte a qualquer momento. O usuário ficará atento a este arquivo e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto o agente de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelo agente e/ou usuário comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🧪 Ambiente de Testes
- Existe uma infraestrutura de testes prontas e funcional. As configurações do ambiente estão no arquivo `docker\dados\docker-compose.yml`
- O ambiente de testes está na pasta `docker\dados\sites\localhost\conn2flow-gestor`. Que é executado pelo gestor via navegador assim: `http://localhost/instalador/` . O mesmo está na pasta: `docker\dados\sites\localhost\public_html\instalador`
- Para atualizar o ambiente e refletir as mudanças do repositório, segue o arquivo para sincronização: `docker\utils\sincroniza-gestor.sh checksum`
- Todos os comandos para executar no ambiente de testes estão no arquivo: `docker\utils\comandos-docker.md`
- Se precisar executar o PHP lá, exemplo: `docker exec conn2flow-app bash -c "php -v"`

## 🗃️ Repositório GIT
- Existe um script feito com todas as operações necessárias internas para gerenciar o repositório: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`
- Dentro desse script é feito o versionamento automático do projeto, commit e push. Portanto, não faça os comandos manualmente. Apenas execute o script quando for alterar o repositório.

## ⚙️ Configurações da Implementação
- Caminho base: $base = `gestor\controladores\atualizacoes\`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `atualizacoes-banco-de-dados.php`.
- Caminho da pasta de backups caso necessário: $backupPath = `backups\atualizacoes`.
- Caminho da pasta de logs: $logsPath = `gestor\logs\atualizacoes`.
- Caminho da pasta de linguagens: $linguagensPath = `gestor\controladores\atualizacoes\lang\`.
- Linguagens suportadas: $linguagensSuportadas = [`pt-br`, `en`].
- Linguagens de dicionário serão armazenadas em arquivo .JSON.
- Todos os textos de informação/logs deverão ter multilinguas. Escapados usando função helper `_()`;
- O código fonte deverá **ser bem comentado (padrão DocBlock), seguir os padrões de design definidos e ser modular.** Todas as orientações deverão constar nos comentários do código.

## 📖 Bibliotecas
- Geração de logs: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Pode alterar se necessário.
- Funções de lang: `gestor\bibliotecas\lang.php`: `_()` > Pode alterar se necessário.

## 📝 Orientações para o Agente
1. Vamos alterar `gestor\controladores\atualizacoes\atualizacoes-banco-de-dados.php` para remover a função `seeders()`. Uma vez que os seeders não serão mais executados nas atualizações, irão apenas se executados numa instalação, que é feita em outro contexto.
2. Depois que alterar tudo, vamos fazer os testes no ambiente de testes. Para isso primeiro sincronize os dados. `docker\utils\sincroniza-gestor.sh checksum`.
3. Em seguida, execute os testes no ambiente de testes para garantir que tudo esteja funcionando corretamente. Exemplo: `docker exec conn2flow-app bash -c "php -v"`
4. Caso tudo fique resolvido, vamos gerar a versão e as operações do GIT executando o script de commit: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## 🧭 Estrutura do código-fonte
```
migracoes():
    > Lógica para rodar as migrações

seeders(): // Remover
    > Lógica para rodar os seeders

comparacaoDados():
    > Lógica para comparar os dados

relatorioFinal():
    > Lógica para gerar o relatório final

main():
    migracoes()
    seeders() // Remover
    comparacaoDados()
    relatorioFinal()

main()
```

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [x] Remover função seeders() e chamada em main do arquivo `gestor\\controladores\\atualizacoes\\atualizacoes-banco-de-dados.php`
- [x] Testar sincronização e execução no ambiente de testes (dry-run executado sem seeders)
- [x] Gerar commit e versão

## ☑️ Processo Pós-Implementação
- [x] Executar o script gerado para ver se funciona corretamente.
- [x] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções v1.10.15
1. Houve um problema na tabela `paginas` no campo `tipo`. Os recursos originais têm o campo do arquivo .JSON chamado `type`. Este campo ou tem o valor `page` ou `system`. O problema que o gestor usa o valor em português `tipo` com os valores em português: `pagina` e `sistema`. Por isso precisamos atualizar o script para poder fazer essa conversão corretamente.
2. As seguintes tabelas têm o campo `user_modified`: `paginas`, `layouts`, `componentes` e `variaveis`. Quando esse valor está definido no registro dentro do banco de dados, ou seja `1`, não podemos atualizar os campos `html` e `css` das tabelas: `paginas`, `layouts` e `componentes`. E não podemos atualizar o campo `valor` da tabela `variaveis`. Lembrando que esses valores vêm dos recursos dos arquivos `TabelaData.json` da pasta: `gestor/db/data`. Exemplo: `gestor\db\data\PaginasData.json`
3. Quando isso ocorrer, ou seja, quando rodarmos o script para atualizar os dados no banco, iremos manter os dados das colunas `html` e `css` das tabelas `paginas`, `layouts` e `componentes`, e o campo `valor` da tabela `variaveis`. Mas, em contrapartida vamos marcar cada registro que isso ocorrer o campo `system_updated` como `1`. E incluir o valor do `html` e `css` dos recursos atualizados nos campos `html_updated` e `css_updated` das tabelas: `paginas`, `layouts` e `componentes`. E o valor do `valor` da tabela `variaveis` no campo `value_updated`.
4. Em seguida, execute os testes no ambiente de testes para garantir que tudo esteja funcionando corretamente. Exemplo: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
5. Caso tudo fique resolvido, vamos gerar a versão e as operações do GIT executando o script de commit: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## 🧭 Estrutura do código-fonte
```
migracoes():
    > Lógica para rodar as migrações

comparacaoDados():
    > Lógica para comparar os dados
    > Corrigir a comparação entre os campos `tipo` e `type`.
    > Manter os dados das colunas `html` e `css` das tabelas `paginas`, `layouts` e `componentes`, e o campo `valor` da tabela `variaveis`.
    > Marcar o campo `system_updated` como `1` quando os dados forem mantidos.
    > Incluir os valores atualizados nos campos `html_updated`, `css_updated` e `value_updated`.

relatorioFinal():
    > Lógica para gerar o relatório final

main():
    migracoes()
    comparacaoDados()
    relatorioFinal()

main()
```

## ✅ Progresso da Implementação das Alterações e Correções
- [x] Implementar conversão type->tipo (page/system => pagina/sistema)
- [x] Implementar preservação html/css/valor quando user_modified=1
- [x] Preencher *_updated e system_updated conforme regras
- [x] Testar script (dry-run) em ambiente docker
- [x] Commitar versão v1.10.15

## ☑️ Processo Pós Alterações e Correções
- [x] Executar o script gerado para ver se funciona corretamente.
- [x] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções v1.10.16
1. Fiz a seguinte alteração na tabela `paginas` por mim mesmo usando o PHPMyAdmin. Acessei o registro de `id`=`teste-variavel-global`, modifiquei o campo `html` para um novo valor. E alterei o campo `user_modified` para `1`. SQL do registro para conferência:
```sql
(3, 1, 2, 'Teste Variável Global 2', 'teste-variavel-global', 'pt-br', 'teste-variavel-global/', 'page', NULL, NULL, NULL, NULL, '<p>Teste novo porra @[[variavel-global]]@ que deve ser @[[variavel-novo]]@ como deve ser.</p>\n<p>Mas será que dá certo @[[variavel-nova]]@ , sei lá</p>\n<p>Dinovo!!!</p>\nTeste', 'p{\n    width:@[[variavel-nova]]@;\n}', 'A', 1, '2025-08-13 19:30:53', '2025-08-14 13:34:04', 1, 0, NULL, NULL, '1.2', '{\"html\":\"541933857364212ec6a4925c86d75feb\",\"css\":\"635794792273cbaa101a544f44d917e0\",\"combined\":\"79695fd8d837dda4d0306ecc03953f05\"}');

```
2. Rodei por mim mesmo no `$ docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --dry-run 2>&1 | tail -n 60"`
3. Fui olhar a tabela `paginas` e confirmei que as alterações NÃO foram aplicadas corretamente. Além de não modificar o campo `system_updated`. Também não alterou os campos `html_updated` e `css_updated` conforme esperado.
4. Também pude ver que em todos os registros da tabela `paginas` os campos `tipo` continuam com seus valores em inglês `system` e `page`.
5. Em seguida, execute os testes no ambiente de testes para garantir que tudo esteja funcionando corretamente. Exemplo: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
6. Caso tudo fique resolvido, vamos gerar a versão e as operações do GIT executando o script de commit: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** 14/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.10.16