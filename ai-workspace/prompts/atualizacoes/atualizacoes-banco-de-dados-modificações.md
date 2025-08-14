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
- [ ] Gerar commit e versão

## ☑️ Processo Pós-Implementação
- [x] Executar o script gerado para ver se funciona corretamente.
- [ ] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções 1.0

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** dataAtual()
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow versao()