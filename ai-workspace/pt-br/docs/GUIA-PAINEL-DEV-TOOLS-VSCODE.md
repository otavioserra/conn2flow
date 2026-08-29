# 🛠️ Guia Completo do Painel Conn2Flow Dev Tools (Extensão VS Code)

Este documento é o manual oficial de referência para a extensão **Conn2Flow Dev Tools (`conn2flow-tools`)**. Ele detalha o funcionamento, a arquitetura e a operação de cada seção e botão da barra lateral.

---

## 📑 Índice
1. [Visão Geral e Barra de Atividades](#-visão-geral)
2. [🎛️ Modos de Operação & Autonomia](#1-modos-de-operação--autonomia)
3. [🏛️ SDD & Governança Viva](#2-sdd--governança-viva)
4. [🐳 Docker & Logs em Tempo Real (com Toggle Inteligente)](#3-docker--logs-em-tempo-real)
5. [🛠️ Manager & Core Framework](#4-manager--core-framework)
6. [🗃️ Projetos & Environment (Deploy, Scaffold e Clonagem)](#5-projetos--environment)
7. [⭐ Ações Customizadas do Projeto (Plug & Play `.c2f/actions.json`)](#6-ações-customizadas-do-projeto)
8. [📚 AI Workspace Hub & Validação de 36 Skills](#7-ai-workspace-hub)
9. [📊 Itens da Barra de Status (Rodapé)](#8-itens-da-barra-de-status)

---

## 🌟 Visão Geral

A extensão **Conn2Flow Dev Tools** transforma o VS Code / Cursor em uma estação de comando integrada para o desenvolvimento com Conn2Flow. Ela elimina a necessidade de decorar comandos de terminal longos, automatiza deploys e integrações com o Docker, gerencia múltiplos repositórios e projetos satélites, e sincroniza os agentes de inteligência artificial de acordo com a metodologia **Spec-Driven Development (SDD)**.

* **Ícone Oficial**: Diamante estilizado com fluxo contínuo na Activity Bar esquerda;
* **Painel Lateral**: Dividido em 7 seções modulares retráteis;
* **Terminal Integrado Dedicado**: Os comandos são executados em terminais nomeados (`Conn2Flow Dev Terminal`, `Conn2Flow: Logs Apache`, etc.), sem poluir seu terminal principal.

---

## 1. 🎛️ Modos de Operação & Autonomia

Esta seção controla a topologia da equipe de agentes de IA e o nível de liberdade concedido a eles:

### Topologias de Agentes:
* **🏛️ Tríade de Agentes (Arquiteto + Executor + Revisor Técnico)**:
  - *Modo Enterprise / Rigor Máximo*;
  - O **Arquiteto** planeja a fatia no SDD (`SPEC.md`, `req-XXX.md`);
  - O **Executor** implementa código e compila recursos;
  - O **Revisor Técnico** audita os diffs do Git, roda testes de segurança e valida os contratos antes da homologação final.
* **👥 Duplo Agente (Arquiteto + Executor)**:
  - *Modo Ágil / Didático*;
  - Fluxo rápido focado em prototipagem e tarefas diretas com aprovação humana direta.

### Níveis de Autonomia:
* **🛡️ Nível 1: Supervisionado**:
  - A IA pode sugerir código e rodar testes locais;
  - **Nenhum commit Git ou deploy** é executado sem aval humano prévio explícito.
* **👁️ Nível 2: Autônomo Monitorado**:
  - A IA executa a esteira de forma ininterrupta mantendo uma Live Todo List na tela;
  - Permite testes automatizados e deploys exclusivos no ambiente local de testes Docker (`sites/localhost/`).
* **🤖 Nível 3: Autônomo Headless**:
  - Execução autônoma em segundo plano utilizando Git Worktrees isoladas e conectores MCP.

---

## 2. 🏛️ SDD & Governança Viva

A governança do Conn2Flow é orientada a especificações vivas (Spec-Driven Development):

* **Abrir CURRENT.md (Preview)**:
  - Abre o briefing da requisição ativa em formato renderizado (utilizando a extensão Markdown Preview Enhanced ou o preview nativo do VS Code).
  - Mostra o ponteiro da tarefa atual, a topologia selecionada e o status da esteira.
* **Abrir SPEC.md (Preview)**:
  - Abre a especificação normativa geral do Core Framework formatada com tabelas e diagramas.
* **Abrir Checklist de Validação**:
  - Abre o [`VALIDATION-CHECKLIST.md`](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-ai-workspace/sdd/validation/VALIDATION-CHECKLIST.md) contendo os critérios de aceite e histórico de lotes fechados.

---

## 3. 🐳 Docker & Logs em Tempo Real

Gerencia a saúde do container Docker `conn2flow-app`:

* **Status dos Containers**: Executa `docker ps` no terminal para verificar portas (80, 443, 3306) e status de saúde.
* **Logs Apache (Follow) — Toggle Inteligente**:
  - **1º Clique**: Abre o terminal dedicado `Conn2Flow: Logs Apache` e começa a transmitir em tempo real os acessos HTTP (`docker logs --follow`); o botão fica verde: `🟢 Logs Apache (Ao Vivo - Clique p/ Parar)`.
  - **2º Clique**: Envia automaticamente `Ctrl + C` para interromper o follow e **libera o terminal na mesma hora**! O botão volta ao normal: `▶️ Logs Apache (Follow)`.
* **Logs PHP (Follow) — Toggle Inteligente**:
  - Monitora o arquivo `/var/log/php_errors.log` dentro do container via `tail -f`.
  - Possui o mesmo comportamento de toggle (1 clique inicia, 2º clique encerra e solta o terminal).
* **Limpar Logs PHP**: Executa `truncate -s 0` no log de erros do PHP para iniciar sessões limpas de depuração.

---

## 4. 🛠️ Manager & Core Framework

Comandos de alto nível para manutenção e compilação do núcleo open-source:

* **Update All (Sistema)**:
  - Executa `./c2f manager:update-all` no terminal;
  - Roda a esteira canônica de 4 etapas:
    1. Compilação do Core PHP;
    2. Sincronização de Recursos da autoria para o banco SQL;
    3. Atualização de arquivos estáticos;
    4. Reconstrução completa do CSS Tailwind (`css:rebuild`).
* **Sincronizar Recursos (`c2f resources:sync`)**:
  - Sincroniza layouts, páginas, componentes e variáveis da pasta `resources/` para as tabelas do banco de dados SQL (fonte da verdade em runtime).
* **CSS Rebuild (`c2f css:rebuild`)**:
  - Analisa as classes Tailwind do banco de dados e compila o `css_precompiled` e `css_compiled`.
* **CSS Audit (`c2f css:audit`)**:
  - Audita o código em busca de classes órfãs, inconsistências ou dívidas técnicas de estilo.

---

## 5. 🗃️ Projetos & Environment

O coração do desenvolvimento multi-projetos no Conn2Flow:

* **🎯 Projeto Alvo Ativo: `[nome-do-projeto]`**:
  - Exibe qual projeto está configurado como alvo no `environment.json` (`devEnvironment.projectTarget`).
  - Ao clicar nele, abre um menu flutuante para você alternar o projeto padrão instantaneamente!
* **🚀 Deploy Projeto Alvo**:
  - Dispara o deploy no projeto alvo ativo com **1 clique**, sem fazer perguntas nem pedir ID.
* **🎯 Deploy Escolhendo Projeto...**:
  - Abre a lista com todos os projetos cadastrados no `environment.json` para você escolher qual deseja enviar para o ambiente de testes ou produção.
* **🔄 Update All Projeto Alvo**:
  - Executa o pipeline completo de 6 etapas no projeto ativo (`./c2f project:update-all <target>`).
* **💻 Update All Escolhendo Projeto...**:
  - Seleciona interativamente qual projeto satélite receberá o pipeline de 6 etapas.
* **➕ Cadastrar Novo Projeto no Environment**:
  - Abre um assistente visual para registrar um novo projeto em `devProjects` no `environment.json` (solicitando slug, nome, URL e modo Docker local).
* **✨ Provisionar Novo Projeto Satélite (Scaffold)**:
  - Cria fisicamente as pastas do projeto (`gestor/modulos/`, `gestor/assets/`, `gestor/resources/`, `docs/`, `README.md`) e já cadastra no `environment.json` em um único fluxo automatizado!
* **📥 Clonar Repositórios Oficiais**:
  - Verifica se algum dos 4 repositórios (`conn2flow`, `lumix`, `transformamp`, `conn2flow-site`) está faltando na pasta pai (`Documentos/GIT/`) e oferece opções para clonar individualmente ou **clonar todos os faltantes de uma vez** via Git!
* **🔄 Sincronizar com Template do Core**:
  - Compara o `dev-environment/data/environment.json` com o template canônico `dev-environment/templates/environment/environment.json`;
  - Faz o merge de quaisquer novas variáveis ou seções criadas no Core, **preservando 100% dos seus tokens, URLs e projetos já cadastrados**.
* **📋 Abrir Template do Environment**: Abre o template canônico para edição de esquema.
* **📄 Abrir environment.json (Ativo)**: Abre o arquivo real de configuração para inspeção direta.

---

## 6. ⭐ Ações Customizadas do Projeto (Plug & Play)

Permite que cada projeto tenha suas próprias automações particulares sem alterar o código da extensão ou vazar scripts privados para o Git público:

* **Como Funciona**:
  - Basta criar uma pasta `.c2f/` na raiz do seu projeto com o arquivo `actions.json`;
  - O formato é simples e declarativo:
    ```json
    {
      "title": "⚡ Automações Pessoais",
      "actions": [
        {
          "label": "🌿 Sincronizar Worktrees",
          "icon": "git-branch",
          "type": "terminal",
          "command": "powershell -File ./scripts/sync-worktrees.ps1"
        }
      ]
    }
    ```
* **Hot Reload em Tempo Real**: Qualquer alteração no `actions.json` atualiza o painel do VS Code na mesma fração de segundo!
* **Botão de Inicialização**: O botão `✨ Criar Ações Customizadas (.c2f/actions.json)` cria o modelo inicial pronto para preencher.

---

## 7. 📚 AI Workspace Hub

Gerenciamento das 36 skills normativas da Tríade de IAs:

* **Sincronizar Skills (1-Click)**: Executa `sync-all-repos.ps1`, propagando as 36 skills em todos os repositórios conectados.
* **Validar 36 Skills (`c2f ai:sync`)**: Roda a auditoria rigorosa de integridade e contratos nos 5 toolkits (`.claude`, `.cursor`, `.gemini`, `.github`, `.codex`).
* **Abrir Playbook Multi-Agentes**: Abre o guia prático de transição e orquestração entre Claude Code, OpenAI Codex e Google Antigravity.
* **Abrir Catálogo de Skills**: Lista detalhada de todas as 29 Skills do Core e 7 Skills SDD.

---

## 8. 📊 Itens da Barra de Status (Rodapé)

No rodapé inferior do VS Code, 3 itens fornecem feedback contínuo:
1. **`$(organization) Tríade | Supervisionado`**: Mostra a topologia e autonomia ativas. Clique para alterar via seletor rápido;
2. **`$(git-commit) SDD: REQ-033`**: Mostra o número da requisição ativa em `CURRENT.md`. Clique para abrir o preview;
3. **`$(server) Conn2Flow Docker`**: Clique rápido para verificar a saúde dos containers Docker.
