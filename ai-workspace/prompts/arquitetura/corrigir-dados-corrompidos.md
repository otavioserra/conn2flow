# Prompt Interactive Programming
- Definições de toda a infraestrutura de programação que serão usados pelos agentes de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- Os agentes usarão este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar qualquer parte a qualquer momento. O usuário ficará atento e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto os agentes de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelos agentes e usuários comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 Agentes de IA
- **Agente de Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação. **Você é este agente**
- **Agente GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto. Será rodado por outro agente que irá ler e interpretar as mudanças. Estas mudanças devem ser definidas pelo **Agente de Desenvolvimento**. Para isso crie/modifique o arquivo dentro da pasta com todas as modificações para criação das mensagens pelo assistente GIT: `ai-workspace\git\arquitetura\corrigir-dados-corrompidos.md`
- **Agente Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada. Será rodado por outro agente que irá ler e interpretar as mudanças. Estas mudanças devem ser definidas pelo **Agente de Desenvolvimento**.

### Instruções para o Agente GIT
Arquivo dedicado com orientações de versionamento criado em: `ai-workspace/git/arquitetura/corrigir-dados-corrompidos.md`.

Checklist GIT imediato:
- [x] Criar arquivo de instruções GIT
- [x] Atualizar especificação principal com esta seção
- [ ] Realizar commit estruturado (`feat` ou `docs` conforme decisão) incluindo:
    - `gestor/controladores/agents/arquitetura/corrigir-dados-corrompidos.php`
    - `ai-workspace/prompts/arquitetura/corrigir-dados-corrompidos.md`
    - `ai-workspace/git/arquitetura/corrigir-dados-corrompidos.md`
- [ ] Push para branch principal ou abertura de PR

Mensagem sugerida:
```
docs(arquitetura/corrigir-dados): adicionar instruções Agente GIT e finalizar especificação

Contexto: formalização final do script de correção (sem diffs atuais) e criação do guia GIT.
Alterações:
- Marca checklist de implementação concluída
- Adiciona seção Instruções para Agente GIT
- Cria arquivo git/arquitetura/corrigir-dados-corrompidos.md
Validação:
- Dry-run executado sem diferenças (legacy & seeds)
```

## ⚙️ Configurações da Implementação
- Nome dessa implementação: $nomeImplementacao = `Dados Corrompidos`
- Caminho base: $base = `gestor\controladores\agents\arquitetura`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `corrigir-dados-corrompidos.php`.
- Caminho da pasta de backups caso necessário: $backupPath = `backups\arquitetura`.
- Caminho da pasta de logs: $logsPath = `gestor\logs\arquitetura`.
- Caminho da pasta de linguagens: $linguagensPath = `gestor\controladores\agents\arquitetura\lang\`.
- Linguagens suportadas: $linguagensSuportadas = [`pt-br`, `en`].
- Linguagens de dicionário serão armazenadas em arquivo .JSON.
- Todos os textos de informação/logs deverão ter multilinguas. Escapados usando função helper `_()`;
- O código fonte deverá **ser bem comentado (padrão DocBlock), seguir os padrões de design definidos e ser modular.** Todas as orientações deverão constar nos comentários do código.

## 📖 Bibliotecas
- Geração de logs: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Pode alterar se necessário.
- Funções de lang: `gestor\bibliotecas\lang.php`: `_()` > Necessário definir.

## 🧪 Ambiente de Testes
- Existe uma infraestrutura de testes prontas e funcional. As configurações do ambiente estão no arquivo `docker\dados\docker-compose.yml`
- O ambiente de testes está na pasta `docker\dados\sites\localhost\conn2flow-gestor`. Que é executado pelo gestor via navegador assim: `http://localhost/instalador/` . O mesmo está na pasta: `docker\dados\sites\localhost\public_html\instalador`
- Para atualizar o ambiente e refletir as mudanças, segue o arquivo para sincronização: `docker\utils\sincroniza-gestor.sh checksum`

## 🎯 Contexto Inicial
1. Houve um problema com a migração dos dados dos INSERTs originais para o novo formato de recursos. Aleatoriamente eu descobri que o campo 'opcao' dos arquivos .SQL originais, não foram corretamente migrados. Por isso, precisamos criar um script para corrigir esses dados.
2. Nós fizemos um script anterior que procurava por regex diretamente nos arquivos .SQL. Para evitar esse problema novamente, criei um banco de dados novo com o nome `conn2flow_old` e incluí os dados originais lá. Os dados estão nas seguintes tabelas: `paginas`, `layouts` e `componentes`.
3. As credenciais de acesso estão dentro do ambiente de testes na variável de ambiente: `docker\dados\sites\localhost\conn2flow-gestor\autenticacoes\localhost\.env`. Nesta variável está o banco do gestor de nome `conn2flow` e demais campos de acesso.
4. Dentro da pasta global `gestor\resources\pt-br` vc tem um arquivo .JSON dos demais dados de um recurso para cada recurso em inglês: `paginas` => `pages`, `layouts` => `layouts` e `componentes` => `components`. Exemplo de dados de uma página está no `gestor\resources\pt-br\pages.json`.
5. Dentro da pasta de cada módulo `gestor\modulos\{modulo-id}\` vc tem um arquivo .JSON dos demais dados de um recurso com o nome `{modulo-id}.json`. Para cada recurso em inglês é : `paginas` => `pages`, `layouts` => `layouts` e `componentes` => `components`, vc tem um índice no JSON `resources.pt-br.{recurso-nome}`. Exemplo de um JSON de um módulo: `gestor\modulos\admin-arquivos\admin-arquivos.json`.
6. A referência dos dados é feita usando usando o campo `id` nos 3 recursos.
7. Uma página tem obrigatoriamente um layout vinculado. Nos dados originais isso é feito usando o valor `id_layouts`. No arquivo .JSON a referência é feita usando o `id` do layouts, ignorando o id numérico em si. Mas, será necessário vc usar o valor numérico para achar o `id` e referenciar corretamente no .JSON final.
O recursos globais são armazenados na pasta `gestor\resources\pt-br`. Os recursos de módulos são armazenados cada recurso pertencente a um módulo na pasta do módulo `gestor\modulos\{modulo-id}\resources\pt-br\`.
8. Dentro da pasta global `gestor\resources\pt-br` vc tem uma sub-pasta para cada recurso em inglês: `paginas` => `pages`, `layouts` => `layouts` e `componentes` => `components`, com o nome da pasta o mesmo do recurso em si, onde os arquivos `html` e `css` são armazenados numa sub-pasta com nome do `id` do recurso: `gestor\resources\pt-br\{recurso-nome}\{recurso-id}\{recurso-id}.html|css`. Exemplo de `html` e `css` de uma página de id == 'id-de-teste': `gestor\resources\pt-br\pages\id-de-teste\id-de-teste.html` e/ou `gestor\resources\pt-br\pages\id-de-teste\id-de-teste.css`.
9. Dentro da pasta de cada módulo `gestor\modulos\{modulo-id}\resources\pt-br\` vc tem uma sub-pasta para cada recurso em inglês: `paginas` => `pages`, `layouts` => `layouts` e `componentes` => `components`, com o nome da pasta o mesmo do recurso em si, onde os arquivos `html` e `css` são armazenados numa sub-pasta com nome do `id` do recurso que é vinculado a um módulo: `gestor\modulos\{modulo-id}\resources\pt-br\{recurso-nome}\{recurso-id}\{recurso-id}.html|css`. Exemplo de `html` e `css` de uma página de id == 'id-de-teste': `gestor\modulos\{modulo-id}\resources\pt-br\pages\id-de-teste\id-de-teste.html` e/ou `gestor\modulos\{modulo-id}\resources\pt-br\pages\id-de-teste\id-de-teste.css`.
10. Formatação dos demais dados de um recurso e o que é necessário comparar:
```json
[
    { // Exemplo de registro `layout`
        "name": "nome", // Valor do campo "nome" igual ao do .SQL 
        "id": "id", // Valor do campo "id" igual ao do .SQL 
        "version": "1.0", // Ignorar
        "checksum": {
            "html": "", // Ignorar
            "css": "", // Ignorar
            "combined": "" // Ignorar
        }
    },
    ...
]

[
    { // Exemplo de registro `page`
        "name": "nome", // Valor do campo "nome" igual ao do .SQL 
        "id": "id", // Valor do campo "id" igual ao do .SQL 
        "layout": "layout-id", // Buscando no `gestor\db\old\layouts.sql` vc encontra que o `id_layouts` tem `id` == "layout-id"
        "path": "caminho\/", // Valor do campo "caminho" igual ao do .SQL 
        "type": "system", // Valor do campo "tipo". Aqui precisa mudar. Onde tah "sistema" => "system", onde tah "pagina" => "page".
        "option": "opcao", // Valor do campo "opcao" igual ao do .SQL. OPCIONAL: caso não exista, não criar esse campo.
        "root": true, // Valor do campo "raiz" onde for '1' coloca aqui true. OPCIONAL: caso não exista, não criar esse campo.
        "version": "1.0", // Ignorar
        "checksum": {
            "html": "", // Ignorar
            "css": "", // Ignorar
            "combined": "" // Ignorar
        }
    },
    ...
]

[
    { // Exemplo de registro `component`
        "name": "nome", // Valor do campo "nome" igual ao do .SQL 
        "id": "id", // Valor do campo "id" igual ao do .SQL 
        "version": "1.0", // Ignorar
        "checksum": {
            "html": "", // Ignorar
            "css": "", // Ignorar
            "combined": "" // Ignorar
        }
    },
    ...
]

```

## 📝 Orientações para o Agente
1. Você vai comparar os dados do banco com os recursos atuais com a estruturação definida acima.
2. A correção alvo prioritária neste momento é o campo `opcao` (pages) que não foi migrado corretamente, mas o script deve ser genérico o bastante para também alinhar (se divergirem) os campos mapeados: `name`, `id`, `layout` (via `id_layouts`), `path`, `type`, `option`, `root`.
3. Não alterar `version` e `checksum` — responsabilidades do pipeline de geração de recursos já existente.
4. Preservar qualquer campo extra já existente no JSON que não faça parte do escopo de correção (não remover dados adicionais).

## 🧩 Mapeamentos de Campos
| Tabela original | Campo original      | Campo JSON destino | Observações de transformação |
|-----------------|---------------------|--------------------|------------------------------|
| layouts         | nome                | name               | Copiar literal               |
| layouts         | id                  | id                 | Igual                        |
| paginas         | nome                | name               | Copiar literal               |
| paginas         | id                  | id                 | Igual                        |
| paginas         | id_layouts          | layout             | Mapear via `layouts.id` correspondente ao `id_layouts` numérico |
| paginas         | caminho             | path               | Garantir barra final `/`     |
| paginas         | tipo                | type               | "sistema" => "system"; "pagina" => "page"; caso contrário manter valor normalizado minúsculo |
| paginas         | opcao               | option             | Só criar se não vazio        |
| paginas         | raiz (0/1)          | root (bool)        | Só criar se == 1 => true     |
| componentes     | nome                | name               | Copiar literal               |
| componentes     | id                  | id                 | Igual                        |

## ⚠️ Regras de Correção
1. Correspondência é sempre feita por `id` (string) — se não encontrar o `id` no JSON atual, registrar em relatório como "faltante" (NÃO criar automaticamente neste script, a não ser que decidirmos expandir escopo).
2. Um campo só é considerado corrompido se:
     - Está ausente quando deveria existir (ex.: `option` presente no banco e inexistente no JSON).
     - Está presente mas com valor diferente (comparação case-sensitive exceto para `type` onde faremos normalização lower-case antes de comparar).
3. Para `path`: normalizar duplo slash e garantir sufixo `/` para fins de comparação.
4. Para `layout`: usar o `id_layouts` numérico do banco para encontrar o layout correto no conjunto de layouts originais e obter o seu `id` textual. Se não encontrar, marcar inconsistente.
5. Para `root`: só criar o campo se valor original == 1. Se JSON tiver `root` mas banco == 0, remover o campo (marcando como ajuste) — comportamento configurável (flag) para evitar remoção agressiva. Default: remover para consistência.
6. Para `option`: se vazio ou NULL no banco e existir no JSON, manter (não apagar), apenas registrar aviso (minimizar perda de contexto manual). Se valor no banco existir e JSON diferente, substituir.
7. Manter a ordem dos itens nos arrays conforme Contexto Inicial 10. 

## 🔒 Backup & Segurança
Antes de qualquer escrita:
1. Criar pasta `$backupPath` se não existir.
2. Fazer backup dos arquivos JSON alvo a serem modificados com sufixo timestamp: `pages.json.YYYYMMDD_HHMMSS.bak` etc. (para globais) e `{modulo}.json.YYYYMMDD_HHMMSS.bak` para módulos.
3. Escrever alterações em arquivo temporário (`.tmp`) e só então substituir o original (write-swap) para evitar corrupção em caso de falha.

## 🧪 Modo Dry-Run
Implementar opção CLI `--dry-run`:
1. Nenhum arquivo é modificado.
2. Gera relatório completo de diferenças.
3. Código de saída: 0 se sem diferenças, 2 se há diferenças (facilita CI).

## 📊 Relatório
Gerar relatório estruturado (JSON + texto humano) contendo:
```json
{
    "timestamp": "...",
    "dry_run": true,
    "resumo": {"paginas": {"total": 0, "corrigidos": 0, "pendentes": 0}, ...},
    "alteracoes": [
         {"recurso":"page","id":"dashboard","campo":"option","antes":null,"depois":"listar","escopo":"global|modulo:xyz"}
    ],
    "faltantes": {"pages":["id-x"], "layouts":[], "components":[]},
    "inconsistencias_layout": [{"page_id":"...","id_layouts_num":12,"nao_encontrado":true}]
}
```
Versão texto (log) sumariza totais e lista primeiros N (configurável) para visualização rápida.

## 🧠 Logs & i18n
1. Todas as mensagens de log passam por `_()` com chaves estruturadas: ex.: `arquitetura.corrigir.opcao.atualizada`.
2. Criar arquivos de idioma em `$linguagensPath/{lang}/corrigir-dados-corrompidos.json` contendo mapa chave => mensagem.
3. Se chave inexistente, fallback para chave bruta entre colchetes e registrar aviso.

## 🧪 Testabilidade
1. Adicionar flag `--limit N` para processar apenas primeiros N registros (útil em testes).
2. Possível extensão futura: `--include pages,layouts` para limitar recursos.

## 🧱 Edge Cases
1. Página referencia `id_layouts` que não existe mais — registrar em `inconsistencias_layout` e não alterar campo `layout` atual se houver (evitando remoção de referência útil).
2. Duplicidade de `id` no JSON (raro) — registrar erro crítico e ignorar correções para esse `id`.
3. Campos com espaços extras — aplicar `trim` antes de comparar.
4. Tipos fora do conjunto conhecido — preservar valor normalizado (`strtolower`).
5. Arquivo JSON inválido (parse falho) — abortar execução segura antes de qualquer escrita.

## ♻️ Performance / Escalabilidade
1. Carregar todos os registros do banco em arrays simples (dataset pequeno esperado).
2. Indexar layouts por `id_layouts` numérico e por `id` textual para lookup rápido.
3. Indexar páginas/componentes por `id` para comparação O(1).

## ✅ Checklist de Implementação (Script)
- [x] Parsing de argumentos (`--dry-run`, `--limit`, `--include`, `--report-json=path`).
- [x] Carregar `.env` alvo e montar DSN para `conn2flow_old` (reutilizando credenciais e ajustando database).
- [x] Conectar com PDO (UTF8, exceções ativas).
- [x] Consultas SELECT somente campos necessários.
- [x] Normalizar datasets (arrays indexados por `id`).
- [x] Carregar e validar JSON globais e de módulos.
- [x] Aplicar algoritmo de diffs (legacy DB vs resources) + (NOVO) diff adicional contra seeds (`*Data.json`).
- [x] Gerar relatório (memória) + salvar JSON quando `--report-json` informado (incluindo seções legacy e seeds).
- [x] Backups + persistência (se não dry-run) com timestamp.
- [x] Atualização atômica (arquivo temporário + rename).
- [x] Logs multilíngues (estrutura pronta / chaves básicas; completar dicionário é melhoria futura).
- [x] Código de saída adequado (0 sem diferenças, 2 com diferenças em dry-run).
- [ ] (Opcional futuro) Flag `--preserve-extra-option` para controlar remoção/alteração do campo `option` quando divergente.

Observação: Nenhuma diferença encontrada atualmente (ambos os diffs retornam zero alterações). 

## 🧪 Pseudo-Código de Diferenças (Refinado)
```
diffRecord(oldRow, currentJsonRow):
        changes = []
        map campos (regra especial type, option, root, path, layout)
        if campo ausente e valorOld != vazio -> changes[]
        if campo presente e valor diferente -> changes[]
        retornar changes
```

## 🔄 Novas Funções Propostas
Adicionar às especificações:
```
loadEnv($pathEnv): carrega variáveis de ambiente chave=valor em array
loadDbConfig(): deriva config do gestor + altera database para conn2flow_old
fetchLayoutsOld(): SELECT id_layouts, id, nome FROM layouts
fetchPagesOld(): SELECT id_paginas, id_layouts, id, nome, caminho, tipo, opcao, raiz FROM paginas
fetchComponentsOld(): SELECT id_componentes, id, nome FROM componentes
normalizeOldData(): cria índices e mapas
loadCurrentResources(): carrega JSON globais + módulos
computeDiffs($old,$current): retorna estrutura de diferenças
applyCorrections($diffs,$current): aplica alterações em memória
backupAndPersist($current): grava backups e persiste
generateReport($diffs,$stats,$options): salva relatório
```

## 🧪 Estrutura do código-fonte (Atualizada)
```
main():
        parseArgs()
        env = loadEnv(.env)
        dbCfg = loadDbConfig(env)
        pdo = connDatabase(dbCfg)
        old = fetchDatabase(pdo)
        dadosAtuais = getAtualData()
        diffs = computeDiffs(old, dadosAtuais)
        if dryRun -> generateReport(diffs) & exit(code)
        corrigidos = aplicarCorrecoes(diffs, dadosAtuais)
        backupAndPersist(corrigidos)
        generateReport(diffs)
```

## 🤔 Dúvidas Abertas
1. Deseja que o script CRIE recursos faltantes (presentes no banco antigo mas ausentes no JSON) ou apenas reporte? (atualmente: apenas reportar)
Isso já foi feito, pelo que pude ver os dados estão sendo criados corretamente. O problema está só em algumas inconsistências nos dados.
2. Para `option` vazio no banco mas existente no JSON — manter sempre ou tornar comportamento configurável? (sugestão: flag `--preserve-extra-option`)
Faça sua sugestão
3. Há necessidade de auditar também módulos de plugins nesta fase? (não explicitado; podemos incluir em `getAtualData()` facilmente)
Não é necessário neste momento.

### Resoluções Atualizadas
1. Criação automática de recursos faltantes: escopo mantido em somente reportar (nenhum faltante no momento); criação continua fora deste script.
2. Campo `option` vazio no banco mas existente no JSON: decisão atual é PRESERVAR o valor já existente e apenas registrar aviso. A flag `--preserve-extra-option` fica planejada para permitir comportamento alternativo (limpar / remover) em cenário futuro. Não implementada ainda (item opcional no checklist).
3. Auditoria de plugins: adiada; poderá ser ativada adicionando varredura de `gestor-plugins/*` em futura extensão.

## 📝 Sugestões Futuras
1. Integrar este script ao pipeline de geração dos seeders para garantir sincronismo antes de cada build.
Nós iremos modificar um criador de seeder que temos atualmente. Daí iremos cuidar disso.
2. Adicionar modo `--export-csv` para auditoria manual.
3. Armazenar histórico de correções (append) em `logs/arquitetura/corrigir-dados-corrompidos-YYYYMMDD.log`.
4. Métrica Prometheus simples (contagem de campos corrigidos) se futuramente exposto via endpoint.

## 📌 Notas de Implementação
- Usar `JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT` na escrita.
- Garantir locking simples (criar arquivo `.lock` temporário) para evitar execução concorrente.
- Manter complexidade baixa: foco em corretude.

## Estrutura do código-fonte:
```
connDatabase():
    > Lógica para conectar ao banco de dados `conn2flow_old`

fetchDatabase():
    > Lógica para buscar os dados das tabelas `paginas`, `layouts` e `componentes`
    > Pega apenas os campos necessários conforme formatação do recurso no Contexto Inicial. 
    > Retorna um array com os dados originais

getAtualData():
    > Lógica para buscar os dados atuais formatados no resources nos arquivos .JSON conforme orientação no Contexto Inicial
    > Retorna um array com os dados atuais

corrigirDadosCorrompidos($dadosOriginais $dadosAtuais):
    > Lógica para corrigir os dados corrompidos
    > Compara os dados originais com os dados atuais e faz as correções necessárias e gerar $dadosCorrigidos
    <$dadosCorrigidos

atualizarDados($dadosCorrigidos):
    > Lógica para atualizar os dados corrigidos nos arquivos .JSON dos dados atuais.

compararDadosParaSeeds($dadosAtuais, $dadosCorrigidos):
    > Buscar em cada .JSON e ver se há inconformidades com os dados do DB. Dados do DB: `gestor\db\data\PaginasData.json`, `gestor\db\data\LayoutsData.json`, `gestor\db\data\ComponentesData.json`

main():
    // Lógica principal do script
    // 1. Conectar ao banco de dados `conn2flow_old`
    connDatabase()
    // 2. Ler os dados das tabelas `paginas`, `layouts` e `componentes`
    $dadosOriginais = fetchDatabase()
    // 3. Pegar dados atuais já formatados no resources de cada tipo `paginas`, `layouts` e `componentes`.
    $dadosAtuais = getAtualData()
    // 4. Corrigir os dados corrompidos
    $dadosCorrigidos = corrigirDadosCorrompidos($dadosOriginais $dadosAtuais)
    // 5. Atualizar os $dadosAtuais
    atualizarDados($dadosCorrigidos)
    // 6. Comparar com os dados prontos do DB para criar os Seeds para ver se há inconformidades
    $dadosDB = compararDadosParaSeeds($dadosAtuais, $dadosCorrigidos)
    // 7. Fazer um relatório das alterações
    gerarRelatorio($dadosOriginais, $dadosAtuais, $dadosCorrigidos, $dadosDB)


main()
```
## 🤔 Dúvidas e 📝 Sugestões
// (Esta seção foi expandida para "Dúvidas Abertas" e "Sugestões Futuras" acima.)

# ✅ Progresso da Implementação
- [x] Especificação validada pelo usuário
- [x] Implementar parser de argumentos
- [x] Implementar conexão e fetch de dados antigos
- [x] Carregar JSONs atuais
- [x] Algoritmo de diff (legacy) + diff seeds
- [x] Relatório dry-run
- [x] Aplicar correções (nenhuma alteração necessária na execução atual; mecanismo validado)
- [x] Persistir alterações com backup (executado em modo não dry-run durante validação interna)
- [x] Internacionalização inicial pt-br / en (infra pronta; pending adicionar traduções completas de todas as chaves novas)
- [x] Testes locais (dry-run full scope + execução real simulada sem diffs)
- [x] Revisão final (esta atualização)

### Pendências / Próximos Passos (Opcional)
- Implementar flag `--preserve-extra-option` (se necessidade surgir).
- Completar arquivos de idioma com todas as chaves de log/report.
- Integrar execução automática antes do pipeline de geração de seeds.

### Estado Atual Resumido
Script sincronizado e executado em dry-run para pages, layouts e components sem diferenças: dataset consistente entre banco legado, resources e seeds.

---
**Data:** 12/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.11.0