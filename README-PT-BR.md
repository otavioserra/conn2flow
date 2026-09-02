# Conn2Flow - CMS, Framework de Backend e Plataforma de Automação de Conteúdo

> 📖 **Disponível em múltiplos idiomas**: [🇺🇸 English](README.md) | 🇧🇷 Português (este arquivo)

O Conn2Flow é um Sistema de Gestão de Conteúdo (CMS) modular e de alta performance construído em PHP 8.3+, Tailwind CSS moderno e arquitetura desacoplada para implantações multi-inquilino (multi-tenant), edição visual de páginas e entrega automatizada. Ele está evoluindo para um **framework de backend projetado para ser operado de dentro do IDE** (VS Code, Antigravity, Cursor, Claude Code, Codex), dando a humanos e agentes de IA acesso controlado aos mesmos contratos de conteúdo, operação e validação.

---

## Sumário

- [🧭 Visão: Do CMS ao Backend-para-Agentes](#-visão-do-cms-ao-backend-para-agentes)
- [🆕 Última Versão](#-última-versão)
- [⚙️ Funcionalidades do Sistema](#️-funcionalidades-do-sistema)
- [📁 Estrutura do Repositório](#-estrutura-do-repositório)
- [🤖 Metodologia de Desenvolvimento com IA & SDD](#-metodologia-de-desenvolvimento-com-ia--sdd)
- [⚡ Instalação Rápida](#-instalação-rápida)
- [📚 Documentação Técnica & Base de Conhecimento](#-documentação-técnica--base-de-conhecimento)
- [🧪 Testes Automatizados & Comandos Úteis](#-testes-automatizados--comandos-úteis)
- [🗺️ Roadmap](#️-roadmap)
- [👥 Comunidade, Suporte & Licença](#-comunidade-suporte--licença)

---

## 🧭 Visão: Do CMS ao AMS

O Conn2Flow começou como um Sistema de Gestão de Conteúdo. Ele está se
tornando um **Agent Management System (AMS)**: um backend no qual operações
de conteúdo — criar, editar, revisar, publicar, implantar — são uma superfície
compartilhada entre humanos e agentes de IA. A meta não é gerenciar agentes
autônomos como um fim em si. É dar ao trabalho de conteúdo assistido por IA os
controles que um CMS maduro já dá aos editores humanos: identidade, permissão,
escopo, validação e trilha de auditoria.

- **Conteúdo como superfície de API controlada**: endpoints `_api/` expõem
  fluxos de conteúdo, autenticação e RBAC por Personal Access Tokens
  escopados (com rate limit, revogáveis, restritos por perfil) — um agente se
  autentica como um usuário, nunca como um script segurando um segredo
  compartilhado.
- **O CLI `c2f` é o contrato de automação**: um binário com mais de 30
  comandos que um humano roda manualmente e um agente despacha através do
  MCP Hub do [Conn2Flow AI Workspace](https://github.com/otavioserra/conn2flow-ai-workspace).
- **Governança em Tríade, não um único agente autônomo**: um Arquiteto, um
  Executor e um Revisor compartilham uma única fonte da verdade (`sdd/`),
  de modo que a autonomia tenha fronteiras explícitas e auditáveis em cada
  etapa.
- **Uma só frota de governança, muitos repositórios**: o mesmo catálogo de
  skills e a mesma topologia de agentes se propagam — com memória local
  independente por repositório — pelo core, pelo [Conn2Flow Nexus](https://github.com/otavioserra/conn2flow-nexus)
  (um gateway de IA emergente e independente de fornecedor), por uma
  arquitetura de agente mobile full-stack e por todo projeto em produção
  construído sobre este core.

**Leia a visão completa**: [ai-workspace/pt-br/docs/visao/](ai-workspace/pt-br/docs/visao/README.md).

---

## 🔄 Como Funciona

1. **Crie e governe conteúdo** no CMS visual: páginas, layouts, widgets,
  variáveis, mídia, formulários, menus, publicações, usuários e permissões.
2. **Opere pelo IDE** usando a extensão VS Code, o CLI `c2f` ou endpoints
  `_api/` controlados. Um desenvolvedor e um fluxo de IA autorizado usam as
  mesmas fronteiras do sistema, em vez de contornar o banco do CMS.
3. **Planeje, execute e revise o trabalho** pela Tríade SDD: um Arquiteto
  define a intenção, um Executor altera a menor fatia viável e um Revisor
  confere as evidências antes da entrega.
4. **Valide e entregue** com Docker, PHPUnit, Vitest, Playwright,
  sincronização de recursos, migrações e fluxos de release no CI/CD.

Isso faz o Conn2Flow servir tanto como CMS completo para editores quanto como
backend operacional para equipes que constroem fluxos de conteúdo nativos de
IDE, orientados por API e assistidos por IA.

---

## 🆕 Última Versão

**v2.10.7 (Setembro de 2026)** *(Base atual: v2.9.51)*

- **Atualização do GitHub Actions para Node 24**: Atualizadas as actions (`checkout@v5`, `setup-node@v5`, `cache@v5`, `action-gh-release@v2.2.1`) nos workflows `release-gestor.yml` e `release-instalador.yml` para suporte nativo ao Node 24 (`req-157` / `BATCH-160`).
- **Otimização da Esteira CI/CD**: Remoção de etapas redundantes de compilação remota de recursos Tailwind no GitHub Actions, consumindo diretamente os artefatos pré-compilados e validados no release local (`req-156` / `BATCH-159`).
- **Paridade Visual Estrita no Tailwind CSS**: Documentação da governança de paridade visual entre Páginas Públicas, Pré-Visualizador e Editor HTML Visual (`req-156` / `BATCH-158`).

### Releases 2.10.x Anteriores

- **v2.10.6**: Otimização do pipeline CI/CD e remoção de etapas redundantes.
- **v2.10.5**: Documentação de paridade visual estrita do Tailwind e atualização dos limiares de memory gardening.
- **v2.10.4**: Idempotência de checksum MD5 multiplataforma e normalização de finais de linha.
- **v2.10.3**: Correção da cascata no preview de templates e ajustes iniciais nos checksums de recursos.
- **v2.10.2**: Compatibilidade TLS/SSL com `--ssl-no-revoke` no Windows e integração inicial do motor cron.
- **v2.10.1**: Inicialização do fluxo de agentes sem prompt, identificação explícita do repositório e enxugamento da documentação.
- **v2.10.0**: Dependências automáticas de sistema no Tailwind, assets externos e Google Fonts hospedados localmente, minificação de assets no build, tabelas administrativas responsivas, segurança e recuperação de sessão reforçadas e compatibilidade com o Instalador Web v2.

Para ver o registro completo de mudanças, consulte o [CHANGELOG-PT-BR.md](CHANGELOG-PT-BR.md).

---

## ⚙️ Funcionalidades do Sistema

### Conteúdo & Administração
- **Editor HTML Visual**: barra flutuante (Editbar), mapeamento reativo do DOM ao vivo, inserção por arrastar-e-soltar, área de transferência interna e barra lateral de CSS com inspetor `getComputedStyle()`.
- **Páginas, Publicações & Conteúdo Reutilizável**: layouts, templates, componentes, variáveis, páginas e tipos de publicação montados por um sistema de recursos que separa autoria de artefatos de runtime gerados.
- **Widgets & Módulos**: Galerias, Menus hierárquicos, Formulários, Publisher Index/Highlights e Pages Index — cada um com renderização pública via AJAX e CRUD administrativo.
- **Formulários & Mídia**: formulários visuais com tratamento de submissões e controles anti-spam, além de gerenciador físico de arquivos com diretórios, upload no seletor, seleção em lote, galerias e streaming de mídia.
- **SEO & Sitemap**: metadados por página/publicação (Open Graph, meta description/keywords), `sitemap.xml` e `robots.txt` automáticos.
- **Gateways de Pagamento**: bibliotecas nativas de PayPal (checkout transparente, Card Fields) e Stripe (Payment Element, Billing, Webhooks).
- **CSS Multi-Framework**: Tailwind CSS v4 (compilação por recurso com governança autoria-vs-derivado) ao lado do legado Fomantic UI, escolhido por layout/página.
- **Multi-Site & Plugins**: core ciente de domínio, com framework de plugins, templates de desenvolvimento e fluxos separados para plugins privados/públicos.

### Camada de Agentes & API
- **Personal Access Tokens**: tokens de API escopados, com rate limit, revogáveis e com códigos de recuperação de 2FA.
- **Endpoints `_api/`**: acesso programático a fluxos suportados de conteúdo, autenticação e RBAC — a mesma superfície controlada consumida por um cliente mobile ou por um agente.
- **CLI `c2f`**: mais de 30 comandos para recursos, banco de dados, sincronização de projetos, releases, Docker e CI, despacháveis por agentes de IDE através do MCP Hub.
- **Operações no IDE**: Conn2Flow Dev Tools leva requisições SDD, diagnósticos Docker, operações de Gestor/Projetos, releases e controles do Hub de IA para o VS Code.
- **Sanitização de HTML/JS na Entrega**: portão configurável (`HTML_SANITIZE`) que remove comentários de autoria antes do conteúdo chegar ao visitante público.

### Desenvolvimento, Entrega & Segurança
- **Stack Docker de Desenvolvimento**: PHP 8.3 + Apache + MySQL 8.0, orquestrada via tarefas do VS Code.
- **Assets Locais em Primeiro Lugar**: JS/CSS/fontes de terceiros hospedados em disco, com minificação no build — zero dependência de CDN em runtime.
- **Suíte de Testes Unificada**: PHPUnit (backend), Vitest (componentes de frontend) e Playwright (E2E) integrados ao CI.
- **Instalador Web v2**: assistente guiado em 4 etapas com proteção contra concorrência e detecção automática de servidor.
- **Pipeline de Release & Atualização**: artefatos versionados, checagens de integridade, migrações, sincronização de recursos, tokens determinísticos de cache e publicação atômica de branch e tag.

---

## 📁 Estrutura do Repositório

* **`gestor/`** — o CMS core: funcionalidades de gestão, compilador de recursos, sistema de plugins e atualizações automáticas.
* **`gestor-instalador/`** — o instalador web multilíngue e automatizado.
* **`cli/`** — o subsistema orientado a objetos do CLI `c2f`.
* **`ai-workspace/`** — documentação bilíngue, scripts de automação e a base de conhecimento de [Visão](ai-workspace/pt-br/docs/visao/README.md).
* **`dev-plugins/`** — framework de desenvolvimento de plugins (templates, scripts, árvores privada/pública).
* **`dev-environment/`** — a stack Docker de desenvolvimento local.
* **`sdd/`** — a camada de governança Spec-Driven Development: specs, batches, decisões e validação.
* **`tests/`** — suítes PHPUnit, Vitest e Playwright na raiz.
* **`.github/`** — workflows de CI/CD e a camada de customização multi-agente (Copilot/Claude/Codex).

---

## 🤖 Metodologia de Desenvolvimento com IA & SDD

O Conn2Flow é desenvolvido sob um framework de **Spec-Driven Development
(SDD)**: toda mudança é ancorada em `sdd/` como fonte única da verdade e
flui por uma **Tríade** de papéis, em vez de um único agente autônomo —

- **Arquiteto**: traduz a intenção em especificações normativas, registros de decisão e requisições formais (`sdd/human-requests/req-XXX.md`). Nunca realiza commit de código diretamente.
- **Executor**: implementa a menor fatia revisável, roda os testes e registra evidência em `sdd/implementation/` e `sdd/validation/`.
- **Revisor**: audita o diff de forma findings-first — desvio de especificação, desvio de lote, validação ausente — antes de o lote ser considerado fechado.
- **Humano-no-Loop**: direciona o Arquiteto e inspeciona o diff do Executor antes de qualquer consolidação.

Essa mesma forma de governança — somada a um catálogo compartilhado de Core
Skills (conhecimento de produto e infraestrutura destilado em arquivos de
skill versionados e sob demanda) — é propagada, com memória local
independente, por todo repositório do ecossistema Conn2Flow através do
framework [Conn2Flow AI Workspace](https://github.com/otavioserra/conn2flow-ai-workspace)
e seu MCP Hub.

**Explore**: [ai-workspace/README-PT-BR.md](ai-workspace/README-PT-BR.md) para a metodologia completa de Agente Duplo/Tríade, e [ai-workspace/pt-br/docs/visao/02-governanca-triade.md](ai-workspace/pt-br/docs/visao/02-governanca-triade.md) para o aprofundamento da governança.

---

## ⚡ Instalação Rápida

O Conn2Flow disponibiliza um assistente web automatizado de instalação que configura o banco de dados, popula tabelas essenciais e gera as chaves de criptografia.

### 1. Download e Extração
Baixe o pacote oficial da release `instalador-v2.1.2`:

```bash
# Linux/macOS
curl -L -o instalador.zip https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.1.2/instalador.zip
unzip instalador.zip -d /var/www/html/

# Windows (PowerShell)
Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.1.2/instalador.zip" -OutFile "instalador.zip"
Expand-Archive -Path "instalador.zip" -DestinationPath "C:\xampp\htdocs\"
```

### 2. Executar o Instalador Web
Acesse `http://localhost/gestor-instalador/` (ou a raiz do seu domínio) e complete as 4 etapas do assistente:
1. **Verificação de Requisitos**: PHP 8.1+, extensões obrigatórias (PDO, OpenSSL, cURL, Zip).
2. **Configuração de Banco de Dados**: Credenciais MySQL/MariaDB com teste em tempo real.
3. **Caminhos & Segurança**: Detecção de URL raiz e geração do par de chaves RSA.
4. **Conta Administrativa**: Criação do usuário administrador mestre.

---

## 📚 Documentação Técnica & Base de Conhecimento

Os manuais detalhados de arquitetura e guias de operação foram modularizados em `ai-workspace/pt-br/docs/`:

| Tópico / Área | Guia de Documentação |
|---|---|
| **Visão de Produto & Roteiro** | [Visão: Do CMS ao Backend-para-Agentes](ai-workspace/pt-br/docs/visao/README.md) |
| **Desenvolvimento & Estrutura do Repo** | [CONN2FLOW-AMBIENTE-DESENVOLVIMENTO.md](ai-workspace/pt-br/docs/CONN2FLOW-AMBIENTE-DESENVOLVIMENTO.md) |
| **Índice da Base de Conhecimento** | [Catálogo de Documentação Técnica](ai-workspace/pt-br/docs/README.md) |
| **Arquitetura Completa do Sistema** | [CONN2FLOW-GESTOR-DETALHAMENTO.md](ai-workspace/pt-br/docs/CONN2FLOW-GESTOR-DETALHAMENTO.md) |
| **Ambiente Docker Multi-Domínio** | [CONN2FLOW-AMBIENTE-DOCKER.md](ai-workspace/pt-br/docs/CONN2FLOW-AMBIENTE-DOCKER.md) |
| **Motor de Recursos e Banco de Dados** | [CONN2FLOW-SISTEMA-RECURSOS.md](ai-workspace/pt-br/docs/CONN2FLOW-SISTEMA-RECURSOS.md) |
| **Arquitetura do Sistema de Plugins** | [CONN2FLOW-PLUGIN-ARCHITECTURE.md](ai-workspace/pt-br/docs/CONN2FLOW-PLUGIN-ARCHITECTURE.md) |
| **Engenharia Dirigida por Especificações** | [CONN2FLOW-SISTEMA-CONHECIMENTO.md](ai-workspace/pt-br/docs/CONN2FLOW-SISTEMA-CONHECIMENTO.md) |

---

## 🧪 Testes Automatizados & Comandos Úteis

O projeto inclui suíte completa de testes unitários, testes de integração e testes funcionais E2E no navegador:

```bash
# Executar suíte PHPUnit no backend (bibliotecas, controllers, migrações)
composer test

# Executar suíte Vitest no frontend (componentes JavaScript)
npm run test

# Executar testes funcionais Playwright no navegador
npm run test:e2e

# Sincronizar recursos do banco e arquivos locais via CLI
./c2f manager:update-all
```

---

## �️ Roadmap

### ✅ Concluído Recentemente
- Layouts administrativos e públicos em Tailwind CSS v4, disponíveis ao lado da camada de compatibilidade estabelecida do Fomantic UI.
- Personal Access Tokens com perfis escopados, rate limit e códigos de recuperação de 2FA.
- Metadados de SEO, sitemap/robots automáticos e sanitização de HTML/JS na entrega.
- Bibliotecas nativas de gateway de pagamento PayPal e Stripe.
- Assets de terceiros locais (zero CDN em runtime) com minificação no build.
- O subsistema CLI moderno `c2f`, com mais de 30 comandos.

### 🚧 Em Andamento
- **[Conn2Flow Nexus](https://github.com/otavioserra/conn2flow-nexus)**: um microsserviço de Gateway de IA emergente e independente de fornecedor (Kafka + LiteLLM + LangGraph) que enfileira e roteia trabalho de agentes entre provedores de LLM.
- **Arquitetura de Agente Mobile**: um aplicativo complementar full-stack que espelha o RBAC dinamicamente e clona módulos administrativos web para telas nativas.
- Cobertura mais ampla de `_api/` para operações de conteúdo headless, dirigidas por agentes.

### 🔮 Próximos Passos
- Marketplace de plugins aprimorado, com descoberta e instalação em um clique.
- Fluxos de autoria multilíngue expandidos.
- Camada de performance/cache e ambientes públicos de demonstração ao vivo.

---

## 👥 Comunidade, Suporte & Licença

- **Issues no GitHub**: Reporte problemas ou sugira novas melhorias arquiteturais.
- **Contato Profissional**: Conecte-se com o mantenedor no [LinkedIn](https://www.linkedin.com/in/otaviocserra/).
- **AI Workspace**: Explore diretrizes de colaboração multi-agente em [ai-workspace/](ai-workspace/README.md).
- **Licença**: Distribuído sob a licença de código aberto MIT.
