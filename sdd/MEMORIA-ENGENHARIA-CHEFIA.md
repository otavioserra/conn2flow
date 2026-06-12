# Memória de Engenharia — Chefia

> **Propósito**: Este diário de bordo é reservado ao registro de diretrizes, decisões de design, preferências e restrições técnicas estabelecidas pelo **Engenheiro Chefe Humano** durante o desenvolvimento do projeto Conn2Flow. Ele serve como canal de persistência de contexto e memória de pair programming entre chats.

---

## Preferências de Design & Estilo

- **Consistência de Editores (CodeMirror)**:
  - Editores CodeMirror adicionados em abas ou containers extras (como a aba "Código do Widget") devem herdar a mesma identidade do Editor HTML principal.
  - **Especificações de Layout**: Tema `'tomorrow-night-bright'`, modo `'htmlmixed'` (com `htmlMode: true`), indentação de `4` espaços, e altura fixada em `800px` (`setSize('100%', 800)`).
  - **Dedup de Editores**: Sempre verificar a existência de um contêiner `.CodeMirror` irmão adjacente no DOM antes de re-inicializar a biblioteca para prevenir duplicação de instâncias ao alternar abas.

---

## Convenções de Código

- **Fluxo de Versionamento (Commits & Pushes)**:
  - **REGRA CRÍTICA**: Nunca realizar commits (`git commit`) ou envios (`git push`) de forma autônoma ou automática sob nenhuma circunstância.
  - Sempre aguardar a autorização/solicitação explícita do Engenheiro Chefe Humano antes de executar comandos que alterem o histórico remoto do Git.

- **Geração de Mensagens de Commit, Tags e Notas de Release**:
  - **REGRA CRÍTICA**: Ao propor textos para commits ou notas de tags que serão repassados pelo usuário como argumentos para scripts no terminal, **sempre use aspas simples** para destacar termos internamente (ex: `'Adicionar todos os campos'`). Isso evita conflitos com as aspas duplas delimitadoras do comando e previne a quebra do parsing no Bash/PowerShell, impedindo commits truncados ou com nomes genéricos (como "todos").

---

## Restrições Técnicas

- **Placeholder e Notação de Variáveis**:
  - A formatação oficial de placeholders de itens no template do módulo de destaques é `[[item#NOME_DA_VARIAVEL]]` (sem arrobas nas pontas).
  - As expressões regulares (regex) no backend (PHP) e frontend (JS) de renderização ou extração de variáveis devem sempre refletir esta notação para que os valores reais das publicações sejam preenchidos em runtime.

---

## Regras de Negócio e Gestão do SDD

- **Tratamento de Brainstorms & Ideias Futuras**:
  - Funcionalidades, ideias e conceitos de segurança discutidos em brainstorms (como o planejamento de 2FA e Social Login) **não** devem ser colocados em arquivos de requisições de intake ativos (`human-requests`).
  - Devem ser mantidos apenas como tópicos/linhas marcados em status `blocked` (ou `planejado`) no **[BATCH-INDEX.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/BATCH-INDEX.md)**, evitando desvios ou ambiguidades em relação ao escopo das tarefas ativas.

- **Divisão de Papéis (Chefia vs Execução)**:
  - O agente no modo planejador/arquiteto atua como Engenheiro Chefe. Ele não deve realizar modificações diretas em arquivos de código-fonte da aplicação (PHP, HTML, JS, CSS).
  - Suas responsabilidades são limitadas a criar e atualizar especificações em `sdd/human-requests/`, documentar decisões técnicas no `DECISION-LOG.md`, gerenciar o `BATCH-INDEX.md` e registrar planos. As alterações de código são papel exclusivo do Engenheiro Executor.

- **Arquivamento Histórico do SDD (Otimização de Contexto)**:
  - Para evitar o crescimento descontrolado dos arquivos do SDD (que resulta em alto consumo de tokens e perda de eficiência no contexto de processamento de IA), adota-se a limpeza periódica.
  - Devem ser mantidos apenas os últimos 10 itens correntes em cada arquivo. Os itens mais antigos serão movidos para subpastas `/archive/` dentro de cada conceito (ex: `sdd/decisions/archive/decisions-001-030.md`).
  - Os arquivos correntes principais devem manter índices tabulares descritivos de 1 linha por item arquivado apontando para o arquivo histórico correspondente.

---

## Restrições Técnicas

- **Placeholder e Notação de Variáveis**:
  - A formatação oficial de placeholders de itens no template do módulo de destaques é `[[item#NOME_DA_VARIAVEL]]` (sem arrobas nas pontas).
  - As expressões regulares (regex) no backend (PHP) e frontend (JS) de renderização ou extração de variáveis devem sempre refletir esta notação para que os valores reais das publicações sejam preenchidos em runtime.

- **Preservação de Templates Modificados e Framework CSS**:
  - Quando um template possui alterações customizadas no banco de dados, gera-se a opção sufixada `[template_id]-modificado`. O JS faz cache dos códigos e bloqueia a chamada AJAX de template padrão.
  - Para evitar a quebra do pré-visualizador (`widget-preview`), o PHP deve sempre expor o `framework_css` nos options de modelo através do atributo `data-framework` e o JS deve ler e sincronizar este atributo síncronamente no `ready` e no evento `change`.
  - No Highlights, o JS deve extrair as variáveis `[[item#X]]` localmente via RegExp a partir do `initialHtml` para manter a aba lateral de mapeamento de campos povoada na carga do template customizado.

- **Injeção de Recursos de Widgets e Desduplicação**:
  - Para evitar a inserção de tags `<style>` e links de cabeçalhos misturados ao corpo do conteúdo da página, os widgets devem delegar a inclusão de CSS, CSS compilado e HTML extra head para a helper `gestor_pagina_recursos_incluir()` na biblioteca comum `gestor.php`.
  - A helper realiza a desduplicação dos recursos em tempo de execução calculando hashes MD5 e controlando-os via `$_GESTOR['recursos-incluidos-hashes']`.
  - Os widgets públicos devem retornar exclusivamente o HTML estrutural puro do bloco.

- **Arquitetura AJAX Pública de Widgets**:
  - Requisições dinâmicas de widgets no site publicado (como a busca e paginação do `publisher-index`) são processadas acionando o roteador de widgets por meio dos parâmetros HTTP `ajax = true` e `ajaxWidgets`.
  - O backend do widget deve expor a função `<prefixedFunc>_ajax` (ex: `publisher_index_render_ajax`) para capturar parâmetros do request, consultar dados do banco de dados (como busca textual e paginação por offset) e retornar a resposta JSON correspondente em `$_GESTOR['ajax-json']`.

---

## Diretrizes de Testes Automatizados (PHPUnit, Vitest, Playwright)

- **Estratégia de Implementação Incremental (Não testar tudo)**:
  - Não tentar escrever testes para toda a base de código legada de uma só vez (custo muito elevado de desenvolvimento).
  - Adotar uma estratégia incremental: novas funcionalidades, CRUDs de novos módulos, novos endpoints de API e novos widgets públicos devem obrigatoriamente nascer com cobertura de testes unitários ou de integração.
- **Blindagem de Bugs (Regression Testing)**:
  - Ao identificar e reportar um bug no sistema, deve-se primeiro criar um teste automatizado que reproduza o bug (gerando falha). Após a implementação da correção, o teste deve passar. Isso evita regressão de bugs clássicos.
- **Foco em Caminhos Críticos no E2E (Playwright)**:
  - Limitar testes de navegador ponta a ponta (E2E) para as operações vitais do sistema (Login de Administrador, Fluxo de Edição de Perfil, Criação de Destaque e sua correta renderização pública). Evitar escrever fluxos E2E complexos para pequenos CRUDs secundários.
- **Integração Automática no CI/CD**:
  - Todo pull request ou push direcionado para `main` deve executar a suíte completa de testes automatizados via GitHub Actions (`.github/workflows/run-tests.yml`).

---

## Notas Gerais

- As notas deste arquivo foram registradas pelo agente IA sob autorização expressa do Engenheiro Chefe Humano na sessão de 04/06/2026 para reter aprendizados de relacionamento de pair programming.
- Adicionado item sobre a divisão de papéis em 05/06/2026.
- Adicionadas regras de arquivamento do SDD e preservação de customizações de templates em 10/06/2026.
- Adicionadas diretrizes de injeção desduplicada de recursos de widgets e AJAX público de publicações em 11/06/2026.
- Adicionada regra sobre escape de aspas em strings propostas para terminal em 12/06/2026.
- Adicionadas as diretrizes estratégicas de testes (cobertura incremental, blindagem de bugs e caminhos críticos) em 12/06/2026.

