# Decision Log (Archive: DEC-031 to DEC-040)

> As decisões correntes mais recentes permanecem em `sdd/decisions/DECISION-LOG.md`. Este arquivo guarda o histórico movido para manter o log corrente enxuto (≤10 itens).

## DEC-031 - 2026-06-05 - accepted

Registro de Alvo de IA, Variáveis Globais e Modos de IA para o Módulo de Galerias (req-019 / BATCH-019). Decisões desta rodada:
1. **Registro do Alvo `galleries`**: Adicionar `ai_prompts_targets` e `ai_modes` em `galleries.json` para que a rotina `atualizacao-dados-recursos.php` compile e registre o alvo `galleries` nos arquivos `AlvosIaData.json` e `ModosIaData.json` em ambos os idiomas (`pt-br` e `en`).
2. **Criação de Prompts/Modos de IA**: Criar os arquivos de prompt/modo em markdown para o alvo `galleries` (`galleries.md` em `pt-br` e `en`) contendo as regras de estruturação (repetição de items via `<!-- item < -->`, controle condicional de setas/pontinhos, repetição de `dot-item` e atributos `data-*` do contêiner).
3. **Mapeamento de Variáveis Globais vs Itens**: No html-editor, unificar a aba de variáveis. A função `galleries_variaveis_template()` em `galleries.php` retornará tanto variáveis de item quanto variáveis globais de controle (como `show_arrows`, `show_dots`, `autoplay`, `autoplay_speed`, `loop`), estas últimas marcadas com `'global' => true`.
4. **Tratamento no HTML Editor e Ajax IA**: No `html-editor.php`, se a variável possuir `'global' => true`, o mapeamento do template gerará o placeholder `[[VAR_ID]]` (sem o prefixo `item#`). No AJAX de IA (`html_editor_ajax_ia_requests`), processar `menus` e `galleries` injetando a lista de variáveis correspondentes na substituição do marcador `{{variables}}` no prompt do Modo de IA.

## DEC-032 - 2026-06-08 - accepted

Integração de Compilação Tailwind CSS CLI para o Core do Sistema e Pipeline de Release (req-020 / BATCH-020). Decisões desta rodada:
1. **Pasta de Compilação do Core**: Definir a pasta `gestor/assets/tailwindcss/` para abrigar a estrutura do compilador Tailwind CSS v4 para o core/manager. O arquivo de entrada será `input.css` contendo a diretiva `@import "tailwindcss";` e a diretiva `@config "../../../tailwind.config.js";`, e o output compilado será gerado em `output.css` via npx.
2. **Integração em Ambientes Locais**: Atualizar os scripts de sincronização de desenvolvimento `synchronize-manager.sh` (sincronização do manager com Docker local) e `sync-core-to-project.sh` (sincronização do core com projetos de testes) para ler a variável de configuração `devEnvironment.tailwindcss/cli` do arquivo `environment.json`. Caso configurada, os scripts deverão executar o compilador na pasta de origem do core antes de realizar a transmissão (rsync), tratando erros de compilação.
3. **Pipeline de Release**: Modificar o workflow de release `release-gestor.yml` do GitHub Actions para configurar o Node.js v20 e rodar o compilador do Tailwind CSS CLI antes de gerar o pacote ZIP, garantindo que o `output.css` seja incluído no commit de release e no pacote compactado distribuído.

## DEC-033 - 2026-06-08 - accepted

Adoção de Estrutura de Testes Unitários e E2E centralizada na raiz do repositório (req-022 / BATCH-022). Decisões desta rodada:
1. **Pasta de Testes Centralizada**: Criar a pasta `tests/` na raiz do repositório (`conn2flow/tests/`), separando as suítes de teste em `Unit/` (algoritmos PHP/JS puros e isolados), `Integration/` (fluxos que requerem banco ou Docker) e `E2E/` (fluxos de navegação e interface real). A pasta será excluída dos pacotes de release ZIP do gestor.
2. **Frameworks e Configuração**: Adotar o PHPUnit para testes backend em PHP (com arquivo `bootstrap.php` para carregar dependências do core e `phpunit.xml`), o Vitest para testes rápidos de JS frontend, e o Playwright para testes funcionais E2E simulando as interfaces CRUD e visualizadores em navegadores headless.
3. **Atalhos no Workspace**: Registrar tarefas de automação no arquivo `.vscode/tasks.json` para facilitar a execução rápida de cada suíte de testes por parte de operadores humanos e agentes autônomos.

## DEC-034 - 2026-06-08 - accepted

Correção HTML, Automação de Campos e Lançamento v2.8.0 (req-021 / BATCH-021). Decisões desta rodada:
1. **Inicialização do Quill em publisher-pages**: No carregamento do formulário de edição do publicador páginas, inicializar o input hidden do campo HTML com o valor corrente do Quill editor para garantir o envio correto dos dados mesmo se o usuário salvar sem editar o texto.
2. **Botão de Automação de Campos em publisher**: Incluir o botão "Adicionar todos os campos" que insere em lote as variáveis do template que não foram associadas ainda. O nome amigável do campo é derivado via remoção de underlines e capitalização de cada termo.
3. **Preenchimento Automático de Prompts**: Ao adicionar um campo associado ao template, pré-preencher o seletor correspondente. Se o campo adicionado for dinâmico e não possuir mapeamento de prompt legado, gerar o marcador `[[publisher#tipo#id]]` como fallback padrão no input prompt.
