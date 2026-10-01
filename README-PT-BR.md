# Conn2Flow

**Plataforma PHP open source para construir e operar produtos digitais com IA — e um Agent Management System (AMS) para os agentes que ajudam nesse trabalho.**

[![Licença: MIT](https://img.shields.io/badge/Licen%C3%A7a-MIT-blue.svg)](LICENSE)
[![Versão](https://img.shields.io/badge/vers%C3%A3o-v2.10.13-1daac6.svg)](CHANGELOG-PT-BR.md)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](ai-workspace/pt-br/docs/guides/installation.md)

[🇺🇸 English](README.md) · 🇧🇷 Português · [Site](https://conn2flow.com/) · [Plataforma](https://conn2flow.com/plataforma/) · [Conn2Flow Pro](https://conn2flow.com/pro/)

---

## O que é

O Conn2Flow nasceu como CMS. Hoje é a base sobre a qual sites, lojas, produtos por assinatura, portais de cliente e documentação são construídos, publicados e atualizados — por pessoas e por agentes de IA trabalhando sob as mesmas regras.

Três coisas o separam de um CMS com um plugin de IA:

- **Tudo é recurso.** Páginas, layouts, componentes, templates, variáveis, prompts e modos de IA vivem como arquivos no repositório, são compilados e sincronizados com o banco. O que um agente escreve pode ser revisado num diff antes de chegar à produção.
- **Um contrato de automação só.** O CLI `c2f` (mais de 50 comandos) é o que o desenvolvedor roda à mão e o que o agente dispara. Os endpoints `/_api/` expõem as mesmas operações a clientes remotos, com tokens de acesso pessoais.
- **A governança faz parte do produto.** O trabalho é especificado, executado e revisado por artefatos versionados em `sdd/`, com um catálogo de skills compartilhado por cinco ferramentas de agentes. A autonomia tem limites explícitos e auditáveis.

É esse último ponto que chamamos de **Agent Management System**: não um lugar para rodar agentes autônomos por rodar, e sim os controles que um CMS maduro já dá ao editor humano — identidade, permissão, escopo, validação e trilha de auditoria — estendidos ao trabalho assistido por IA.

## O que tem dentro

| Área | O que você recebe |
|---|---|
| **Conteúdo** | Editor HTML visual com barra de edição ao vivo, páginas, layouts, componentes, templates, variáveis, menus, galerias, formulários, publicações com índices e busca, metadados de SEO, sitemap |
| **Agentes e IA** | Biblioteca de IA com modos e prompts como recursos, assistente de IA no editor, catálogo de skills para Claude Code, Codex, Cursor, Gemini e GitHub Copilot, fluxo de Spec-Driven Development |
| **API** | Endpoints `/_api/` de autenticação, OAuth, projetos, atualização do sistema e módulos, com tokens de acesso pessoais de escopo limitado e revogáveis |
| **CLI** | `c2f`: recursos, CSS, assets, banco, projetos, deploy, atualização com rollback, documentação, Docker, inspeção de páginas |
| **Entrega** | Pipeline de projeto, trava de deploy, manifesto por camada, snapshot, verificação de saúde e rollback automático, resolução de choques de arquivo no painel, na API e no CLI |
| **Front-end** | Tailwind CSS v4 compilado por recurso, ao lado do Fomantic UI; assets de terceiros servidos do disco, com minificação no build |
| **Segurança** | Proteção CSRF com renovação silenciosa do token, 2FA, gestão de sessões, bloqueio de acesso com prazo informado, reCAPTCHA e Turnstile, modo de acesso restrito |
| **Pagamentos** | Bibliotecas de Stripe (Payment Element, assinaturas, webhooks) e PayPal |
| **Extensão** | Mais de 30 módulos e 40 bibliotecas no núcleo, hooks, widgets, plugins, multilíngue, projetos que sobrepõem o núcleo sem fazer fork |

## Como pessoas e agentes trabalham nele

```text
       intenção               menor fatia                 evidência
  ┌──────────────┐        ┌───────────────────┐       ┌────────────────┐
  │  Arquiteto   │ ─────▶ │     Executor      │ ────▶ │    Revisor     │
  │ specs, reqs  │        │ código, testes    │       │ achados antes  │
  └──────────────┘        └───────────────────┘       └────────────────┘
          ▲                         │                          │
          └─────── humano no circuito: aprova e redireciona ───┘
```

- `sdd/` guarda as requisições, os lotes, as decisões e os registros de validação. É a fonte única do que foi pedido e do que foi provado.
- `.claude/`, `.codex/`, `.cursor/`, `.gemini/` e `.github/` carregam as mesmas skills, então toda ferramenta de agente começa com o mesmo conhecimento do produto.
- Três níveis de autonomia — supervisionado, monitorado e headless — definem até onde o agente vai antes de um humano olhar.
- O agente alcança uma instalação em execução como um usuário: pelo CLI, ou pela API com um token que pode ser limitado e revogado.

O processo é uma regra do projeto, não uma garantia imposta pelo runtime PHP. Leia [a visão](ai-workspace/pt-br/docs/concepts/vision.md) e [segurança de IA](ai-workspace/pt-br/docs/concepts/ai-security.md) para saber o que está implementado.

## Começar

**Instalar num servidor** (PHP 8.1+, MySQL ou MariaDB, Apache ou Nginx):

```bash
# Localiza o último lançamento do instalador, seja qual for a versão
TAG=$(curl -s "https://api.github.com/repos/otavioserra/conn2flow/releases?per_page=100"   | grep -o '"tag_name": *"instalador-v[^"]*"' | head -1 | cut -d'"' -f4)
curl -L -o instalador.zip "https://github.com/otavioserra/conn2flow/releases/download/$TAG/instalador.zip"
unzip instalador.zip -d /caminho/da/raiz-web
```

Prefere um botão? A página [Comece a construir](https://conn2flow.com/comece-a-construir/) sempre aponta para o último instalador.

Abra o site no navegador e siga os quatro passos: requisitos, banco de dados, caminhos e chaves, conta de administrador. Detalhes no [guia de instalação](ai-workspace/pt-br/docs/guides/installation.md).

**Desenvolver localmente:**

```bash
git clone https://github.com/otavioserra/conn2flow.git
cd conn2flow
./c2f help                 # todos os comandos, com os apelidos
./c2f manager:update-all   # core → recursos → arquivos → banco
composer test              # PHPUnit
npm run test               # Vitest
```

A stack Docker e as tasks do VS Code estão no [guia do ambiente de desenvolvimento](ai-workspace/pt-br/docs/guides/development-environment.md).

## Documentação

A documentação é Markdown, versionada com o código, conferida contra ele e gerada por `c2f docs:build`. Cada página registra o commit em que foi verificada.

| Se você quer | Comece por |
|---|---|
| Entender o sistema | [Arquitetura](ai-workspace/pt-br/docs/concepts/architecture.md) · [Recursos](ai-workspace/pt-br/docs/concepts/resources.md) · [Visão](ai-workspace/pt-br/docs/concepts/vision.md) |
| Instalar e rodar | [Instalação](ai-workspace/pt-br/docs/guides/installation.md) · [Ambiente de desenvolvimento](ai-workspace/pt-br/docs/guides/development-environment.md) |
| Construir em cima | [Criar um módulo](ai-workspace/pt-br/docs/guides/create-a-module.md) · [Criar um plugin](ai-workspace/pt-br/docs/guides/create-a-plugin.md) · [Publicar um projeto](ai-workspace/pt-br/docs/guides/deploy-a-project.md) |
| Automatizar | [Referência do CLI](ai-workspace/pt-br/docs/reference/cli/index.md) · [Referência da API](ai-workspace/pt-br/docs/reference/api/index.md) |
| Consultar | [Bibliotecas](ai-workspace/pt-br/docs/reference/libraries/index.md) · [Módulos](ai-workspace/pt-br/docs/reference/modules/) |
| Atualizar com segurança | [Atualizações do sistema](ai-workspace/pt-br/docs/concepts/system-updates.md) |
| Ver o que mudou | [Novidades](ai-workspace/pt-br/docs/whats-new/index.md) · [Changelog](CHANGELOG-PT-BR.md) |

Índice completo: [ai-workspace/pt-br/docs/](ai-workspace/pt-br/docs/index.md).

## Mapa do repositório

| Pasta | Conteúdo |
|---|---|
| `gestor/` | O núcleo: roteador, bibliotecas, módulos, recursos, migrações |
| `gestor-instalador/` | O instalador web |
| `cli/` | A linha de comando `c2f` |
| `ai-workspace/` | Documentação em português e inglês, e scripts de automação |
| `sdd/` | Requisições, lotes, decisões e registros de validação |
| `dev-environment/` | A stack Docker de desenvolvimento |
| `dev-plugins/` | Templates e ferramentas de plugins |
| `tests/` | Suítes PHPUnit, Vitest e Playwright |

## Ecossistema

- **[conn2flow.com](https://conn2flow.com/)** — o site do projeto, feito com o próprio Conn2Flow. Veja a [visão geral da plataforma](https://conn2flow.com/plataforma/).
- **[Conn2Flow Pro](https://conn2flow.com/pro/)** — hospedagem gerenciada e planos para quem quer a plataforma sem cuidar dos servidores.
- **[Conn2Flow AI Workspace](https://github.com/otavioserra/conn2flow-ai-workspace)** — o framework que leva as skills e a governança a cada repositório construído sobre o núcleo.
- **[Conn2Flow Nexus](https://github.com/otavioserra/conn2flow-nexus)** — um gateway de IA em desenvolvimento. É uma direção, não uma dependência: nada neste repositório exige o Nexus.

## Situação

Versão atual: **v2.10.13**. Trabalho recente, na ordem em que chegou:

- Atualização do sistema pela API, em segundo plano, com estado e rollback
- Resolução de choques de arquivo, uma decisão por arquivo, no painel, na API e no CLI
- Atualização segura: trava de deploy, snapshot, verificação de saúde, rollback automático
- Página inicial e layout por perfil de usuário
- Documentação reescrita a partir do código, com comandos de auditoria e de geração
- Renovação silenciosa do token CSRF

Detalhes por versão em [Novidades](ai-workspace/pt-br/docs/whats-new/index.md).

## Contribuição e licença

Bugs e propostas são bem-vindos nas [Issues do GitHub](https://github.com/otavioserra/conn2flow/issues). Antes de um pull request, leia como a [documentação](ai-workspace/pt-br/docs/guides/documentation.md) e o fluxo do `sdd/` são mantidos, para a mudança chegar com a evidência dela.

Distribuído sob a [Licença MIT](LICENSE).
