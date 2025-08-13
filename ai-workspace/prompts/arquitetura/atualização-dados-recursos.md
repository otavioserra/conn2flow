# Prompt Interactive Programming - Atualização Dados Recursos.

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
- Todos os comandos para executar no ambiente de testes estão no arquivo: `docker\utils\comandos-docker.md`

## 🗃️ Repositório GIT
- Existe um script feito com todas as operações necessárias internas para gerenciar o repositório: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`
- Dentro desse script é feito o versionamento automático do projeto, commit e push. Portanto, não faça os comandos manualmente. Apenas execute o script quando for alterar o repositório.

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
- [x] Gerar mensagem detalhada e executar commit/push no repositório (script commit.sh não existente, usado git manualmente)

## ♻️ Alterações e Correções v1.10.7
1. Vi que vc criou uma nova função na linha #37 do script dentro do mesmo. Mas, como te disse, vc podia mudar a biblioteca. Portanto, coloque a nova função na biblioteca correspondente em vez de deixar no código.
2. Eu não vi uma rotina para comparar os `id` para encontrar duplicidade. Criar uma rotina para isso. Caso haja alguma duplicidade, o sistema deve marcar o recurso original com um campo `error` igual a `true`, e também o campo `error_msg` com uma mensagem apropriada.
- Caso haja duplicidade de `id` em `languages` diferentes não tem problema. Apenas ignorar neste caso.
- Para o caso de recurso `paginas`, a mesma lógica deve ser implementada para o campo `path` ('caminho'). Ou seja, só pode ter um único caminho, uma vez que o caminho é literalmente a URI da página. E por isso têm que ser única.
3. Para o caso de recurso `variaveis`, o `id` tem que ser único dentro do mesmo módulo. Podendo ter `id` iguais em módulos diferentes.
4. Vi que vc usou na linha #700 a função `nl2br()` para converter quebras de linha em tags <br>. Mas isso não é necessário uma vez que esse script é executado usando CMD e é uma rotina não executada no navegador em si. Olha como ficou o terminal:
```bash
otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
$ php gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
Relatório Final de Atualização de Recursos<br />
========================================<br />
Layouts: 14<br />
Páginas: 188<br />
Componentes: 84<br />
Variáveis: 1308<br />
TOTAL: 1594<br />

otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
```
5. Alterar o relatório e incluir mensagem de erro caso haja duplicidade de `id`. Bem como quais recursos foram atualizados referenciando o tipo (global, módulo, plugin), e o `id` e mais informações relevantes.
6. Alterar o relatório e incluir emojis como ✅, 📝 e etc para melhorar a visualização.

## ✅ Progresso da Implementação das Alterações e Correções
- [x] Remover função interna de log e migrar para biblioteca `log.php`
- [x] Adicionar defaults em `log.php` evitando warnings
- [x] Implementar validação de duplicidade para páginas (id, caminho) e variáveis (id por módulo+idioma)
- [x] Marcar recursos duplicados com `error` e `error_msg`
- [x] Remover uso de `nl2br()` no relatório CLI
- [x] Melhorar relatório com emojis e sumarização
- [ ] Ajustar mensagens de duplicidade para internacionalização completa (futuro)

## ☑️ Processo Pós Alterações e Correções
- [x] Executar o script gerado para ver se funciona corretamente.
- [x] Gerar mensagem detalhada e usar script de commit (quando existir) ou procedimento manual temporário

## ♻️ Alterações e Correções v1.10.8
INFO: Eu removi manualmente as entradas duplicadas. Só ficou as das variáveis com `group` definido.
```bash
⚠️ Erros de duplicidade:
  - paginas (caminho): modulos/sincronizar-bancos/
```
1. Regra adicional de variáveis implementada: duplicidades de `id` dentro do mesmo módulo e idioma são permitidas SE todos os registros possuem `group` definido e os grupos são distintos (>1). Caso contrário, marca erro.
2. Campos `error` e `error_msg` foram removidos dos arquivos Data (`gestor/db/data/*.json`) e agora são adicionados exclusivamente nas fontes de origem (globais/módulos/plugins) conforme solicitado.
3. Marcação de duplicidade agora não persiste em Data.json evitando problemas nos seeders.
4. Origem `modulos/sincronizar-bancos/` permanece sinalizada em `gestor/modulos/modulos/modulos.json`.

## ✅ Progresso da Implementação das Alterações e Correções
- [x] Ajustar regra variáveis (permitir múltiplos ids com groups distintos)
- [x] Remover error/error_msg de Data.json
- [x] Adicionar error/error_msg nos arquivos de origem corretos
- [x] Reexecutar script e validar relatório

## ☑️ Processo Pós Alterações e Correções
- [x] Executar novamente o script para garantir consistência após qualquer ajuste residual
- [x] Executar commit automatizado com mensagem detalhada

## ♻️ Alterações e Correções v1.10.11
1. Encontrei um problema de duplicidade de `id_variaveis`=1235 no arquivo: `gestor\db\data\VariaveisData.json`. Acredito que é aquele caso de `id` iguais em `group` diferentes. Pelo que entendi está sendo computado como 2 recursos iguais.
```json
{
        "id_variaveis": "1235",
        "linguagem_codigo": "pt-br",
        "modulo": "_sistema",
        "id": "novo",
        "valor": "<span class=\"ui grey label\">Novo<\/span>",
        "tipo": "string",
        "grupo": "pedidos-status", // Grupo diferente
        "descricao": null
    },
    {
        "id_variaveis": "1235",
        "linguagem_codigo": "pt-br",
        "modulo": "_sistema",
        "id": "novo",
        "valor": "<span class=\"ui grey label\">Novo<\/span>",
        "tipo": "string",
        "grupo": "pedidos-voucher-status", // Grupo diferente
        "descricao": null
    },
```
2. Corrija esse problema no script.
3. Execute o mesmo para poder ver se tem problemas.
Executar o script e corrigir erros: `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`


## ✅ Progresso da Implementação das Alterações e Correções
Correção aplicada: ajuste da geração de `VariaveisData.json` para atribuir novos `id_variaveis` quando existir o mesmo (linguagem, módulo, id) com grupos distintos, evitando reutilização do mesmo identificador numérico (caso do `id_variaveis=1235`). A segunda ocorrência recebeu novo identificador e o relatório não apresenta duplicidades.

## ☑️ Processo Pós Alterações e Correções
- [x] Executar o script gerado para ver se funciona corretamente.
- [x] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" e executar (quando existir) script de commit: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"` (script ainda não presente; utilizar fluxo manual ou criar script futuramente)

---
**Data:** 12/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow Gestor v1.10.11