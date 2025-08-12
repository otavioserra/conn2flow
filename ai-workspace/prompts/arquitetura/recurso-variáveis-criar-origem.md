# Prompt Interactive Programming - Recurso Variáveis Criar Origem

- Definições de toda a infraestrutura de programação que serão usados pelos agentes de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- Os agentes usarão este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar qualquer parte a qualquer momento. O usuário ficará atento e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto os agentes de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelos agentes e usuários comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 Agente de IA
- **Agente de Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação.
- **Agente GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto. Para isso crie/modifique o arquivo dentro da pasta com todas as modificações para criação das mensagens: `ai-workspace\git\arquitetura\`. Quando precisar gerar o commit, use esse script: `ai-workspace\git\scripts\commit.sh`
- **Agente Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada.

## ⚙️ Configurações da Implementação
- Nome dessa implementação: $nomeImplementacao = `Recurso Variáveis Criar Origem`
- Caminho base: $base = `gestor\controladores\agents\arquitetura`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `recurso-variaveis-criar-origem.php`.
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
1. Vamos criar um novo recurso de variáveis para a origem dos dados. Atualmente, a origem dos dados está no seed da tabela `variaveis`: `gestor\db\data\VariaveisData.json`.
2. O destino de cada variável será o respectivo recurso usando os seguintes locais:
- Globais: gestor/resources/{lang}/variables.json
- Módulos: gestor/modulos/{module}/{module}.json -> resources.{lang}.{variables}
- Plugins: gestor-plugins/{plugin}/local/modulos/{module}/{module}.json -> resources.{lang}.{variables}
3. O processo inverso já foi feito para as `paginas`, `layouts` e `componentes` numa outra rotina. E para criar os seeds usa-se esse script `ai-workspace\scripts\arquitetura\generate_seeds_data.php`. No futuro iremos incluir as `variaveis` neste processo. Então use este script como referência do processo inverso.

## 📝 Orientações para o Agente
1. 

## 🧭 Estrutura do código-fonte
```
lerVariaveis():
    > Fazer lógica para ler todos os registros do `gestor\db\data\VariaveisData.json` e colocar na variável $registros passando para o formato array.
    <$registros

modulosIDs():
    > Fazer lógica para ler todos os registros do `gestor\modulos` e colocar na variável $modulosIDs passando para o formato array.
    <$modulosIDs

modulosPluginsIDs():
    > Fazer lógica para ler todos os registros do `gestor-plugins` e colocar na variável $modulosPluginsIDs passando para o formato array.
    <$modulosPluginsIDs

formatarVars($variaveis, $modulosIDs, $modulosPluginsIDs):
    > Fazer a formatação das variáveis para o formato:
    /*
        Origem Exemplo: 

        {
            "id_variaveis": "1647", // Ignorar, pois o mesmo será controlado pelo processo inverso de criar os Seeds.
            "linguagem_codigo": "pt-br",
            "modulo": "host-configuracao", // Esse valor vai definir o módulo e precisa buscar para colocar num dos 3 tipos. Caso não tenha valor, é global. Senão precisa fazer a busca nos módulos e plugins.
            "id": "recaptcha-v3-tooltip",
            "valor": "Clique para mudar para o Google reCAPTCHA v3",
            "tipo": "string",
            "grupo": null, // Se for null ignorar
            "descricao": null // Se for null ignorar
        }

        Novo Formato:

        {
            "id": "recaptcha-v3-tooltip",
            "value": "Clique para mudar para o Google reCAPTCHA v3",
            "type": "string"
        }
    */
    > Vincular os 3 tipos a variável de retorno $varsFormatadasPorTipo baseado nos IDs dos módulos e plugins da sequinte forma:
    /*
        $varsFormatadasPorTipo = [
            'modulos' => [],
            'plugins' => [],
            'globais' => []
        ];

        foreach ($variaveis as $var) {
            if (in_array($var['modulo'], $modulosIDs)) {
                $varsFormatadasPorTipo['modulos'][] = $var;
            } elseif (in_array($var['modulo'], $modulosPluginsIDs)) {
                $varsFormatadasPorTipo['plugins'][] = $var;
            } else {
                $varsFormatadasPorTipo['globais'][] = $var;
            }
        }
    */
    <$varsFormatadasPorTipo

guardarVarsNosResources($varsFormatadasPorTipo):
    > Fazer a lógica para salvar os dados formatados no arquivo `gestor/resources/{lang}/variables.json` e nos arquivos de cada módulo `gestor/modulos/{module}/{module}.json -> resources.{lang}.{variables}` e módulo de plugin `gestor-plugins/{plugin}/local/modulos/{module}/{module}.json -> resources.{lang}.{variables}`.

main():
    $variaveis = lerVariaveis()
    $modulosIDs = modulosIDs()
    $modulosPluginsIDs = modulosPluginsIDs()
    $varsFormatadasPorTipo = formatarVars($variaveis, $modulosIDs, $modulosPluginsIDs)
    guardarVarsNosResources($varsFormatadasPorTipo)
    reportarMudancas($varsFormatadasPorTipo)

main()
```

## 🤔 Dúvidas e 📝 Sugestões

# ✅ Progresso da Implementação
- [x] Criar o arquivo principal da implementação em `gestor\controladores\agents\arquitetura\recurso-variaveis-criar-origem.php`.
- [x] Implementar a função `lerVariaveis()` para ler os dados de `gestor\db\data\VariaveisData.json`.
- [x] Implementar a função `modulosIDs()` para listar os módulos de `gestor\modulos`.
- [x] Implementar a função `modulosPluginsIDs()` para listar os módulos de `gestor-plugins`.
- [x] Implementar a função `formatarVars()` para transformar e categorizar as variáveis.
- [x] Implementar a função `guardarVarsNosResources()` para salvar as variáveis nos arquivos de recurso corretos.
- [x] Implementar a função `reportarMudancas()` para registrar as alterações.
- [x] Implementar a função `main()` para orquestrar todo o processo.
- [x] Adicionar a lógica de internacionalização para mensagens de log/relatório.
- [x] Revisar e refatorar o código para garantir a qualidade, comentários e aderência aos padrões.

---
**Data:** 12/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow Gestor v1.10.4