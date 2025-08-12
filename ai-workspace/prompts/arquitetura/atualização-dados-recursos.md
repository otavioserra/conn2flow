# Prompt Interactive Programming - Atualização Dados Recursos.

- Definições de toda a infraestrutura de programação que serão usados pelos agentes de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- Os agentes usarão este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar qualquer parte a qualquer momento. O usuário ficará atento e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto os agentes de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelos agentes e usuários comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 Agente de IA - Responsabilidades
- **Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação.
- **GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto.
- **Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada.

## ⚙️ Configurações da Implementação
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
2. Nestes 2 scripts foi criada a lógica para gerar os recursos baseados na origem dos dados para `paginas`, `layouts` e `componentes` e todas as suas peculiaridades estão toda implementadas lá. Precisamos adaptar a nova estrutura de código-fonte.
3. Criei um novo recurso chamado `variaveis` e já está mapeado de forma similar aos outros recursos. Será necessário integrar esse novo recurso na lógica de geração de dados.

## 📝 Orientações para o Agente
1. Os recurso `variaveis` já está mapeado e deve ser integrado na lógica de geração de dados.
2. Leia os scripts originais e procure usar a estrutura abaixo para adaptar a nova forma.

## 🧭 Estrutura do código-fonte
```
carregarMapeamentoGlobal():
    > Lógica para carregar mapeamento principal de idiomas, data files e etc. Armazenar na variável $dadosMapeamentoGlobal
    <$dadosMapeamentoGlobal

carregarDadosExistentes():
    > Lógica para carregar dados existentes (para manter IDs estáveis), armazenar na variável $dadosExistentes
    /*
        $dadosExistentes = [
            'paginas' => [],
            'layouts' => [],
            'componentes' => [],
            'variaveis' => [],
        ];
    */
    <$dadosExistentes

coletarRecursos():
    > Lógica para coletar recursos de cada tipo global, módulos e plugins. Armazenar na variável $recursos
    <$recursos

atualizarDados($dadosExistentes, $recursos):
    > Lógica para atualizar os dados existentes com os novos recursos coletados

main():
    $dadosMapeamentoGlobal = carregarMapeamentoGlobal()
    $dadosExistentes = carregarDadosExistentes()
    $recursos = coletarRecursos($dadosExistentes $dadosMapeamentoGlobal)
    atualizarDados($dadosExistentes, $recursos)
    reporteFinal()

main()
```

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [x] Integração dos scripts anteriores em um único arquivo `atualizacao-dados-recursos.php` com recurso variaveis
- [x] Adição de chaves de linguagem para logs e mensagens
- [x] Execução inicial do script sem erros fatais (warnings eliminados)

## ☑️ Processo Pós-Implementação
- [x] Executar o script gerado para ver se funciona corretamente.
- [ ] Gerar mensagem detalhada e executar o script do GIT para gerar a versão, commit e push automáticos já implementados no script à seguir: `./ai-workspace/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** 12/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow Gestor v1.10.6