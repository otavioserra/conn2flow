# Atualização Dados Recursos.

- Definições de toda a infraestrutura de programação que serão usados pelos agentes de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- Os agentes usarão este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar qualquer parte a qualquer momento. O usuário ficará atento e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto os agentes de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelos agentes e usuários comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 Agente de IA
- **Agente de Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação.
- **Agente GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto. Para isso crie/modifique o arquivo dentro da pasta com todas as modificações para criação das mensagens: `ai-workspace\git\arquitetura\`. Quando precisar gerar o commit, use esse script: `ai-workspace\git\scripts\commit.sh`
- **Agente Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada.

## ⚙️ Configurações da Implementação
- Nome dessa implementação: $nomeImplementacao = `Atualização Dados Recursos`
- Caminho base: $base = `gestor\controladores\agents\arquitetura`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `atualizacao-dados-recursos.php`.
- Caminho da pasta de backups caso necessário: $backupPath = `backups\arquitetura`.
- Caminho da pasta de logs: $logsPath = `gestor\logs\arquitetura`.
- Caminho da pasta de linguagens: $linguagensPath = `gestor\controladores\agents\arquitetura\lang`.
- Linguagens suportadas: $linguagensSuportadas = [`pt-br`, `en`].
- Linguagens de dicionário serão armazenadas em arquivo .JSON.
- Todos os textos de informação/logs deverão ter multilinguas. Escapados usando função helper `_()`;
- O código fonte deverá **ser bem comentado (padrão DocBlock), seguir os padrões de design definidos e ser modular.** Todas as orientações deverão constar nos comentários do código.

## 🧪 Ambiente de Testes
- Existe uma infraestrutura de testes prontas e funcional. As configurações do ambiente estão no arquivo `docker\dados\docker-compose.yml`
- O ambiente de testes está na pasta `docker\dados\sites\localhost\conn2flow-gestor`. Que é executado pelo gestor via navegador assim: `http://localhost/instalador/` . O mesmo está na pasta: `docker\dados\sites\localhost\public_html\instalador`
- Para atualizar o ambiente e refletir as mudanças do repositório, segue o arquivo para sincronização: `docker\utils\sincroniza-gestor.sh checksum`

## 📖 Bibliotecas
- Geração de logs: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Pode alterar se necessário.
- Funções de lang: `gestor\bibliotecas\lang.php`: `_()` > Necessário definir.

## 🎯 Contexto Inicial
1. Criei com outro agente o seguinte script para atualizar os dados dos recursos sendo este o orquestrador: `gestor\resources\generate.multilingual.seeders.php` e esse o gerador dos dados propriamente dito: `ai-workspace\scripts\arquitetura\generate_seeds_data.php`. Agora vamos integrar os dois scripts em um único script no $nomeArquivoImplementacao .
2. Nestes 2 scripts foi criada a lógica para gerar os recursos baseados na origem dos dados para `paginas`, `layouts` e `componentes` e todas as suas peculiaridades estão toda implementadas lá. Precisamos adaptar a nova estrutura de código.
3. 

## 📝 Orientações para o Agente

## 🧭 Estrutura do código-fonte
```
main():
    // Lógica principal do script
    

main()
```

## 🤔 Dúvidas e 📝 Sugestões

# ✅ Progresso da Implementação
- [] item do progresso

---
**Data:** dataAtual()
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow versao()