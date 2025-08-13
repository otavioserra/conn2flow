# Prompt Interactive Programming - NOME

- Definições de toda a infraestrutura de programação que serão usados pelos agentes de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- Os agentes usarão este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar qualquer parte a qualquer momento. O usuário ficará atento e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto os agentes de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelos agentes e usuários comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 Agente de IA - Responsabilidades
- **Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação.
- **GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto.
- **Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada.

## 🧪 Ambiente de Testes
- Existe uma infraestrutura de testes prontas e funcional. As configurações do ambiente estão no arquivo `docker\dados\docker-compose.yml`
- O ambiente de testes está na pasta `docker\dados\sites\localhost\conn2flow-gestor`. Que é executado pelo gestor via navegador assim: `http://localhost/instalador/` . O mesmo está na pasta: `docker\dados\sites\localhost\public_html\instalador`
- Para atualizar o ambiente e refletir as mudanças do repositório, segue o arquivo para sincronização: `docker\utils\sincroniza-gestor.sh checksum`

## 🗃️ Repositório GIT
- Existe um script feito com todas as operações necessárias internas para gerenciar o repositório: `./ai-workspace/scripts/commit.sh "MensagemDetalhadaAqui"`
- Dentro desse script é feito o versionamento automático do projeto, commit e push. Portanto, não faça os comandos manualmente. Apenas execute o script quando for alterar o repositório.

## ⚙️ Configurações da Implementação
- Caminho base: $base = `PATH`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `NOME_ARQUIVO`.
- Caminho da pasta de backups caso necessário: $backupPath = `PATH_BACKUP`.
- Caminho da pasta de logs: $logsPath = `PATH_LOGS`.
- Caminho da pasta de linguagens: $linguagensPath = `PATH_LINGUAGENS`.
- Linguagens suportadas: $linguagensSuportadas = [`pt-br`, `en`].
- Linguagens de dicionário serão armazenadas em arquivo .JSON.
- Todos os textos de informação/logs deverão ter multilinguas. Escapados usando função helper `_()`;
- O código fonte deverá **ser bem comentado (padrão DocBlock), seguir os padrões de design definidos e ser modular.** Todas as orientações deverão constar nos comentários do código.

## 📖 Bibliotecas

## 🎯 Contexto Inicial

## 📝 Orientações para o Agente

## 🧭 Estrutura do código-fonte
```
main():
    // Lógica principal do script
    

main()
```

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [] item do progresso

## ☑️ Processo Pós-Implementação
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, subistituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, subistituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** dataAtual()
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow versao()