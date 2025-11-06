# Conn2Flow - Ambiente Completo de Desenvolvimento CMS

> 📖 **Disponível em múltiplos idiomas**: 🇧🇷 Português (este arquivo) | [🇺🇸 English](README.md)

**Bem-vindo ao Conn2Flow - Ambiente Completo de Desenvolvimento CMS Open Source!**

Conn2Flow é um Sistema de Gerenciamento de Conteúdo (CMS) moderno, leve e flexível open-source construído usando tecnologia LAMP (Linux, Apache, MySQL e PHP). Este repositório fornece um **ambiente de desenvolvimento completo** que inclui:

- ✅ **Sistema CMS Completo** (gestor/) - CMS core com todos os recursos de gerenciamento
- ✅ **Instalador Web Automatizado** (gestor-instalador/) - Instalação com um clique com suporte multilíngue
- ✅ **Ferramentas de Desenvolvimento** (ai-workspace/) - Ambiente de desenvolvimento completo com fluxos de trabalho assistidos por IA
- ✅ **Framework de Desenvolvimento de Plugins** (dev-plugins/) - Ambiente completo de criação e teste de plugins

Originalmente desenvolvido como um CMS proprietário chamado B2make, Conn2Flow agora está sendo lançado para a comunidade open-source para promover colaboração e inovação.

## Estrutura do Repositório

Este repositório fornece um **ambiente de desenvolvimento completo** para o CMS Conn2Flow:

* **gestor/**: O sistema CMS principal - núcleo com todos os recursos de gerenciamento, plugins V2 e atualizações automatizadas
* **gestor-instalador/**: Instalador web automatizado com suporte multilíngue (Português/Inglês)
* **ai-workspace/**: Ambiente de desenvolvimento completo com fluxos de trabalho assistidos por IA, documentação e ferramentas de automação
* **dev-plugins/**: Framework completo de desenvolvimento de plugins com templates, scripts e ambiente de testes
* **dev-environment/**: Ambiente de desenvolvimento baseado em Docker com PHP 8.3 + Apache + MySQL 8.0
* **.github/**: Workflows do GitHub Actions para releases automatizados e CI/CD

### Documentação

Para informações técnicas detalhadas e guias de desenvolvimento, consulte:

- **[📚 Documentação Técnica](ai-workspace/docs/README.md)** - Documentação técnica completa organizada por área do sistema
- **[📋 Changelog](CHANGELOG.md)** - Changelog padrão da indústria seguindo o formato Keep a Changelog
- **[📊 Histórico Completo de Desenvolvimento](ai-workspace/docs/CONN2FLOW-CHANGELOG-HISTORY.md)** - Evolução completa commit-por-commit

### Última Versão: v2.4.0 (6 Novembro 2025)

**🎯 Novas Funcionalidades:**
- **Sistema Completo de Deploy de Projetos via API OAuth**: Sistema completo de deploy automatizado de projetos com autenticação OAuth 2.0 e renovação automática de tokens
- **Implementação de Servidor OAuth 2.0**: Servidor OAuth 2.0 completo com validação JWT, renovação de tokens e endpoints de API seguros
- **Deploy One-Click de Projetos**: Workflow automatizado para atualizações de projetos, compressão e deploy com integração de API
- **Gerenciamento Avançado de Projetos**: Deploy direto na raiz do sistema com atualizações automáticas de recursos e sincronização de banco
- **Tratamento Robusto de Erros**: Tratamento abrangente de erros com rollback automático e mecanismos de retry
- **Melhorias de Segurança**: Autenticação OAuth 2.0 obrigatória, validação segura de ZIP e execução inline para ambientes de produção

**🔧 Melhorias Técnicas:**
- Novo sistema de autenticação OAuth 2.0 com renovação automática de tokens
- Endpoint completo de API de deploy de projetos (`/_api/project/update`)
- Sincronização automática de recursos antes do deploy
- Atualizações de banco inline (sem shell_exec para segurança de produção)
- Detecção e validação de estrutura ZIP (máx. 100MB, verificações de segurança)
- Suite completa de testes (6/6 testes passando)

**📦 O Que Há de Novo:**
- Implementação de servidor OAuth 2.0 com validação JWT
- Sistema completo de deploy de projetos com integração de API
- Renovação automática de tokens e recuperação de erros
- Segurança aprimorada com execução inline e validação
- Documentação abrangente para sistema de deploy

### Branches Legadas
* **gestor-v1.16**: Última versão estável antes da v2.0.0
* **b2make-legacy**: Sistema legado completo preservado para referência
* **v0-legacy**: Versão original de 2012
* **v1-legacy**: Versão de 2015

A estrutura de pastas legada b2make-* foi modernizada e agora está disponível na branch `b2make-legacy` para referência histórica. 

## Instalação Rápida

Conn2Flow apresenta um **instalador web automatizado moderno** que simplifica o processo de instalação para apenas alguns cliques. Nenhuma configuração manual complexa necessária!

### Pré-requisitos

- **Servidor Web**: Apache ou Nginx com suporte a PHP
- **PHP**: Versão 8.0 ou superior com extensões necessárias (curl, zip, pdo_mysql, openssl)
- **MySQL**: Versão 5.7 ou superior (ou equivalente MariaDB)
- **Permissões de Escrita**: Servidor web deve ter acesso de escrita ao diretório de instalação

### Passos de Instalação

1. **Baixe o Instalador**

   **Download Direto:**
   - Clique no próximo link para baixar o `instalador.zip`: [Download Instalador v1.5.1](https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.5.1/instalador.zip)

      
   **Linux/macOS:**
   ```bash
   curl -L -o instalador.zip https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.5.1/instalador.zip
   ```

   **Windows PowerShell:**
   ```powershell
   Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v1.5.1/instalador.zip" -OutFile "instalador.zip"
   ```

   **Sempre o Último Instalador:**
   ```bash
   # Linux/macOS
   LATEST=$(gh release list --repo otavioserra/conn2flow | grep "instalador-v" | head -n1 | awk '{print $3}')
   wget "https://github.com/otavioserra/conn2flow/releases/download/${LATEST}/instalador.zip

   # Windows PowerShell
   $latest = (gh release list --json tagName | ConvertFrom-Json | Where-Object { $_.tagName -like "instalador-v*" } | Select-Object -First 1).tagName
   Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/$latest/instalador.zip" -OutFile "instalador.zip"
   ```

   **Página de Lançamentos:**
   - Vá para a [página de releases](https://github.com/otavioserra/conn2flow/releases) e baixe a última release do **Instalador** (procure por tags `instalador-v*`, não o badge "Latest" que aponta para o sistema Gestor).

2. **Extraia para Seu Diretório Web**
   ```bash
   unzip instalador.zip -d /path/to/your/webroot/gestor-instalador/
   # Ou instale diretamente no webroot. O instalador é inteligente o suficiente para detectar se está na raiz ou sub-pastas.
   unzip instalador.zip -d /path/to/your/webroot/
   ```

3. **Execute o Instalador Web**
   - Abra seu navegador e navegue para: `http://yourdomain.com/gestor-instalador/` ou `http://yourdomain.com/`
   - O instalador suporta **Português (BR)** e **Inglês (US)**
   - Siga a instalação guiada passo-a-passo

4. **Configure Sua Instalação**
   O instalador web irá perguntar por:
   - **Credenciais do banco de dados** (host, nome, usuário, senha)
   - **Caminho de instalação** (tem que ser fora da pasta pública por segurança)
   - **Nome do domínio** para seu site
   - **Detalhes da conta administrador**

5. **Configuração Automática**
   O instalador irá automaticamente:
   - Baixar o sistema Conn2Flow mais recente
   - Criar tabelas do banco de dados e dados iniciais
   - Configurar chaves de autenticação e segurança
   - Definir permissões adequadas de arquivo
   - Configurar arquivos de acesso público
   - Limpar arquivos de instalação

6. **Acesse Seu CMS**
   Após a instalação, acesse seu novo CMS no domínio configurado.

### Recursos de Segurança

- **Caminhos de Instalação Flexíveis**: Instale o sistema fora da pasta web pública para segurança aprimorada
- **Geração Automática de Chaves**: Chaves RSA e tokens de segurança gerados automaticamente
- **Limpeza Segura**: Instalador remove-se após instalação bem-sucedida
- **Logs Detalhados**: Log completo de instalação para solução de problemas

### Instalação Manual (Usuários Avançados)

Para usuários avançados que preferem instalação manual ou precisam de configurações personalizadas:

1. **Clone o Repositório**
   ```bash
   git clone https://github.com/otavioserra/conn2flow.git
   cd conn2flow
   ```

2. **Instale Dependências**
   ```bash
   cd gestor
   composer install
   ```

3. **Configure Ambiente**
   - Copie exemplos de configuração de `autenticacoes.exemplo/`
   - Configure credenciais do banco de dados e configurações específicas do domínio
   - Gere chaves OpenSSL para segurança

4. **Configuração do Banco de Dados**
   - Execute o script unificado de atualização: `php controladores/atualizacoes/atualizacoes-banco-de-dados.php --env-dir=your-domain`

5. **Configuração do Servidor Web**
   - Aponte seu servidor web para os arquivos `public-access`
   - Garanta permissões adequadas e extensões PHP

## Recursos do Sistema

### Recursos Core do CMS
- **Gerenciamento de Conteúdo**: Criação e edição completa de conteúdo com preview TailwindCSS
- **Multi-Framework CSS**: Escolha entre TailwindCSS e FomanticUI por recurso
- **Módulos Admin Avançados**: Interface moderna com capacidades de preview em tempo real
- **Sistema de Plugins V2**: Arquitetura revolucionária de plugins com detecção dinâmica e templates automatizados
- **Gerenciamento de Usuários**: Controle de acesso baseado em papéis e autenticação de usuários
- **Suporte Multi-site**: Gerencie múltiplos domínios de uma única instalação
- **Segurança**: Criptografia OpenSSL, autenticação segura e controles de acesso

### Recursos do Ambiente de Desenvolvimento
- **Stack Completo de Desenvolvimento**: Ambiente Docker com PHP 8.3 + Apache + MySQL 8.0
- **Desenvolvimento Assistido por IA**: Ai-workspace abrangente com 15 docs técnicos e 50+ conversas de agentes
- **Framework de Desenvolvimento de Plugins**: Ambiente dev-plugins completo com templates automatizados e scripts
- **Fluxos de Trabalho Automatizados**: GitHub Actions para releases, testes e deployment
- **Documentação Técnica**: 15+ guias detalhados cobrindo todos os aspectos do sistema
- **Testes e Validação**: Scripts automatizados para verificação de migração e seeder
- **Integração VS Code**: Tarefas pré-configuradas para Docker, operações Git e fluxos de trabalho de desenvolvimento
- **Scripts Prontos para Uso**: Scripts funcionais de automação para commits, releases e sincronização

### Benefícios da Instalação
- **Instalação com Um Clique**: Instalador web-based com configuração guiada
- **Suporte Multilíngue**: Interface em português e inglês
- **Deployment Flexível**: Instale em qualquer lugar, não apenas em pastas públicas
- **Configuração Automática**: Todas as chaves de segurança e configurações geradas automaticamente
- **Instalação Limpa**: Instalador auto-remove deixa nenhum rastro

## Ambiente Completo de Desenvolvimento

Conn2Flow fornece um **ambiente de desenvolvimento completo** que vai além de apenas o CMS - é um ecossistema completo de desenvolvimento projetado tanto para o sistema core quanto para desenvolvimento de plugins.

### 🎯 O Que Está Incluído

**Desenvolvimento do Sistema Core:**
- ✅ **Código Fonte Completo do CMS** - Sistema gestor/ completo com todos os recursos
- ✅ **Instalador Automatizado** - Instalador web de produção
- ✅ **Migrações de Banco de Dados** - Sistema completo de migração de schema e dados
- ✅ **Ambiente de Testes** - Stack de desenvolvimento baseado em Docker

**Framework de Desenvolvimento de Plugins:**
- ✅ **Diretório de Templates** (`dev-plugins/templates/`) - Templates prontos para desenvolvimento e arquivos de ambiente
- ✅ **Desenvolvimento Ativo** (`dev-plugins/plugins/`) - Onde os plugins são realmente desenvolvidos (repositórios private/public)
- ✅ **Configuração de Ambiente** - Copie arquivos de `templates/environment/` para `plugins/private/` ou `plugins/public/`
- ✅ **Scripts Automatizados** - Scripts pré-construídos para desenvolvimento, commits, releases e sincronização de plugins
- ✅ **Integração VS Code** - Tarefas em `.vscode/tasks.json` para automação de desenvolvimento
- ✅ **Documentação** - Guias completos para desenvolvimento de plugins

**Desenvolvimento Assistido por IA:**
- ✅ **Base de Conhecimento** - 15 documentos técnicos preservando conhecimento do sistema
- ✅ **Conversas de Agentes** - 50+ sessões de desenvolvimento de IA documentadas
- ✅ **Scripts de Automação** - Ferramentas criadas por IA para fluxo de trabalho de desenvolvimento
- ✅ **Templates Padronizados** - Prompts consistentes para interações de IA de qualidade

### 🚀 Início Rápido para Desenvolvedores

1. **Clone o Repositório**
   ```bash
   git clone https://github.com/otavioserra/conn2flow.git
   cd conn2flow
   ```

2. **Instale Extensões do VS Code** (Recomendado)
   - **Task Explorer**: `https://github.com/spmeesseman/vscode-taskexplorer` - Para acesso fácil às tarefas de desenvolvimento
   - Esta extensão fornece uma interface visual para as tarefas pré-configuradas em `.vscode/tasks.json`

3. **Configure Ambiente de Desenvolvimento**
   ```bash
   # Copie e configure configurações de ambiente
   cp dev-environment/templates/environment/environment.json dev-environment/data/environment.json
   
   # Edite o arquivo com seus caminhos locais:
   # - source: Caminho para sua instalação local Conn2Flow
   # - target: Caminho onde Docker irá montar os arquivos
   # - dockerPath: Caminho interno do container Docker
   ```

4. **Configure Desenvolvimento de Plugins** (se desenvolvendo plugins)
   ```bash
   # Copie arquivos de ambiente para diretórios de plugin
   cp -r dev-plugins/templates/environment/* dev-plugins/plugins/private/
   cp -r dev-plugins/templates/environment/* dev-plugins/plugins/public/
   
   # Configure arquivos environment.json em ambos os diretórios com caminhos corretos
   # Esses arquivos são essenciais para que os scripts de desenvolvimento de plugins funcionem corretamente
   ```

3. **Inicie Ambiente de Desenvolvimento**
   ```bash
   # Usando Docker (recomendado)
   cd dev-environment
   docker-compose up -d
   
   # Ou use scripts de desenvolvimento local
   bash ai-workspace/scripts/dev-environment/setup.sh
   ```

4. **Desenvolva Plugins**
   ```bash
   # Use templates automatizados
   bash dev-plugins/scripts/create-plugin.sh my-plugin
   
   # Fluxo de trabalho de desenvolvimento
   cd dev-plugins/plugins/private/my-plugin
   bash scripts/dev/synchronizes.sh checksum
   ```

5. **Contribua para o Core**
   ```bash
   # Use desenvolvimento assistido por IA
   # Verifique ai-workspace/prompts/ para templates padronizados
   # Siga fluxos de trabalho documentados em ai-workspace/docs/
   ```

### 📚 Recursos de Aprendizado

- **[Metodologia de Desenvolvimento com IA](ai-workspace/README.md)** - Como construímos isso com assistência de IA
- **[Guia de Desenvolvimento de Plugins](ai-workspace/docs/CONN2FLOW-PLUGIN-ARCHITECTURE.md)** - Guia completo para criação de plugins
- **[Arquitetura do Sistema](ai-workspace/docs/CONN2FLOW-SISTEMA-CONHECIMENTO.md)** - Conhecimento técnico profundo
- **[Fluxos de Trabalho de Desenvolvimento](ai-workspace/docs/CONN2FLOW-GITHUB-ACTIONS.md)** - CI/CD e automação
- **[Documentação Completa do Sistema](ai-workspace/docs/CONN2FLOW-GESTOR-DETALHAMENTO.md)** - Arquitetura e componentes do sistema detalhados
- **[Agente GitHub Copilot](.github/chatmodes/Conn2Flow-v2.0.chatmode.md)** - Agente de IA especializado para desenvolvimento Conn2Flow

### Mecanismo de Atualização do Sistema (Atualizações Automatizadas)

Conn2Flow inclui um orquestrador de atualização do núcleo em `gestor/controladores/atualizacoes/atualizacoes-sistema.php` com suporte CLI e execução incremental via web (AJAX). Principais características:

- Download de artefato `gestor.zip` por tag (ex: `gestor-v1.15.0`) ou uso de artefato local (`--local-artifact`)
- Verificação opcional de integridade SHA256 (`--no-verify` para ignorar)
- Wipe seletivo preservando diretórios críticos: `contents/`, `logs/`, `backups/`, `temp/`, `autenticacoes/`
- Deploy otimizado (rename fallback para copy) com estatísticas de arquivos removidos / movidos
- Merge aditivo de `.env` (novas variáveis adicionadas com bloco `# added-by-update`, variáveis deprecadas apenas logadas)
- Script unificado de banco: `atualizacoes-banco-de-dados.php` (aplica migrações/dados e remove pasta `gestor/db/` após sucesso para reduzir superfície)
- Exportação de plano JSON + logs estruturados em `logs/atualizacoes/`
- Persistência das execuções na tabela `atualizacoes_execucoes` (status, stats, links de log/plano)
- Housekeeping (retenção configurável, padrão 14 dias) de logs e diretórios temporários

Flags principais (CLI):
```
--tag=gestor-vX.Y.Z  --local-artifact  --only-files  --only-db  --no-db  \
--dry-run  --backup  --download-only  --no-verify  --force-all  --tables=... \
--log-diff  --logs-retention-days=N  --debug
```

Execução Web (incremental):
```
?action=start -> deploy -> db -> finalize (status para polling, cancel para cancelar)
```
Estado de sessão: `temp/atualizacoes/sessions/<sid>.json` + `<sid>.log`.

Documentação completa: `ai-workspace/docs/CONN2FLOW-ATUALIZACOES-SISTEMA.md`.

### Propriedade de Arquivos & Permissões

Para evitar falhas silenciosas de `rename()`/`unlink()` durante deploy (principalmente em containers), garanta que o owner dos diretórios da instalação e artefatos seja o mesmo usuário do processo PHP (ex: `www-data`). Exemplo pós extração / antes de executar atualização:
```bash
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-gestor
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-github
```
Falhas de permissão resultarão em avisos de não remoção de pastas antigas e arquivos não atualizados.

### Stack de Desenvolvimento Moderno
- **PHP 8.0+**: Recursos e performance modernos do PHP
- **Composer**: Gerenciamento de dependências e autoloading
- **Phinx**: Migrações de banco de dados e gerenciamento de schema
- **GitHub Actions**: Builds e releases automatizados
- **Design Modular**: Separação limpa de responsabilidades

### Estrutura de Diretórios
```
gestor/                 # Sistema CMS principal
├── bibliotecas/        # Bibliotecas core
├── controladores/      # Controladores MVC
├── modulos/           # Módulos do sistema
├── autenticacoes/     # Configurações específicas do domínio
├── db/               # Migrações e schema do banco de dados
├── public-access/    # Arquivos web públicos
└── vendor/           # Dependências Composer

gestor-instalador/     # Instalador web
├── src/              # Lógica do instalador
├── views/            # Interface de instalação
├── lang/             # Suporte multilíngue
└── assets/           # CSS, JS, imagens

ai-workspace/          # Ambiente de desenvolvimento
├── docs/             # Documentação técnica (15+ guias)
├── scripts/          # Scripts de automação
├── prompts/          # Templates de desenvolvimento IA
├── agents-history/   # 50+ conversas de agentes IA
└── utils/            # Utilitários de desenvolvimento

dev-plugins/           # Framework de desenvolvimento de plugins
├── templates/        # Templates prontos para desenvolvimento
│   ├── environment/  # Arquivos de ambiente para copiar para pastas de plugin
│   │   ├── .github/  # Workflows de release automatizado
│   │   ├── scripts/  # Scripts de desenvolvimento para plugins
│   │   └── environment.json # Mapeamento de plugins e configuração de desenvolvimento
│   ├── plugin/       # Template básico de plugin para copiar
│   └── plugin-skeleton/ # Template avançado de plugin com exemplos
├── plugins/          # Ambiente ativo de desenvolvimento de plugins
│   ├── private/      # Plugins de repositório privado (requer token)
│   └── public/       # Plugins de repositório público (sem token necessário)
└── tests/            # Ambiente de testes de plugins

.vscode/              # Configuração de desenvolvimento VS Code
└── tasks.json        # Tarefas pré-configuradas para automação de desenvolvimento

dev-environment/       # Stack de desenvolvimento Docker
├── docker/           # Configurações Docker
├── data/             # Dados de exemplo e configurações
└── tests/            # Testes de integração

.github/               # Workflows GitHub Actions
└── workflows/        # Automação CI/CD
```

## Versões Atuais

### Últimas Versões Estáveis

**Gestor (Sistema Core)**: `v2.3.0` *(Mais Recente)*
- ✅ **Sistema de IA Integrado Completo**: Sistema completo de IA integrado ao admin-paginas para geração assistida de conteúdo
- ✅ **Integração API Gemini**: Geração de conteúdo via API Gemini com tratamento robusto de erros
- ✅ **Sistema Dual de Prompts**: Modos técnicos (templates estruturados) + Prompts de usuário (necessidades flexíveis)
- ✅ **Interface Avançada**: Edição de conteúdo baseada em CodeMirror com gerenciamento de sessão
- ✅ **Múltiplos Modelos de IA**: Suporte para múltiplos servidores e configurações de modelos de IA
- ✅ **Gerenciamento de Sessão**: Rastreamento de geração de conteúdo e inserção posicional
- ✅ **Sistema de Plugins V2**: Arquitetura completamente refatorada com detecção dinâmica
- ✅ **Templates de Desenvolvimento**: Scripts automatizados para criação de plugins
- ✅ **Rastreio Completo de Dados**: Injeção automática de slug em tabelas com coluna plugin
- ✅ **Resolução Dinâmica**: Environment.json dinâmico em todos os scripts de automação
- ✅ **IDs Textuais**: Migração completa para formato textual em campos de referência
- ✅ **Limpeza Ampla**: Desabilitação de ferramentas legadas e estrutura simplificada

**Instalador (Installer)**: `v1.5.1` *(Mais Recente)*
- ✅ **Suporte ao Sistema de IA**: Instalação preparada para novos recursos de IA v2.3.0
- ✅ **Suporte Framework CSS**: Instalação preparada para novos recursos v2.0.0
- ✅ **Charset UTF-8 Robusto**: Compatibilidade total com caracteres especiais
- ✅ **getPdo() Unificado**: Método único para todas conexões de banco
- ✅ **Detecção URL Robusta**: Funcionamento garantido em subpasta ou raiz
- ✅ **Auto-login Aprimorado**: Configuração automática pós-instalação
- ✅ **Logs Detalhados**: Rastreamento completo do processo

### Histórico de Versões
- **Gestor v2.3.0**: Sistema de IA integrado completo com API Gemini, sistema dual de prompts, interface avançada CodeMirror, gerenciamento de sessão para geração de conteúdo, suporte a múltiplos modelos de IA.
- **Gestor v2.0.0**: Sistema de plugins V2 com arquitetura refatorada, templates automatizados, rastreio completo de dados, IDs textuais, limpeza ampla do sistema.
- **Gestor v1.16.0**: Sistema de preview TailwindCSS, suporte multi-framework CSS, módulos admin modernizados, padrões técnicos otimizados.
- **Gestor v1.15.0**: Sistema de atualização automática consolidado, correção de permissões, documentação técnica.
- **Gestor v1.11.0**: Versionamento automático recursos módulos/plugins; melhorias checksum e remoção definitiva de seeders na atualização.
- **Gestor v1.10.x**: Correções de duplicidade, internacionalização, unificação geração de recursos, campos de controle *updated*.
- **Instalador v1.5.1**: Suporte ao sistema de IA para recursos v2.3.0, compatibilidade aprimorada e robustez de instalação.
- **Instalador v1.4.0**: Suporte framework CSS, charset UTF-8 robusto, getPdo() unificado, preparação para preview system.
- **Instalador v1.3.3**: Refatoração robusta com charset utf8mb4, correção de acentuação, instalação em ambientes diversos.
- **Instalador v1.1.0**: Refatoração para usar script de atualização central; ajustes RewriteBase e criação admin.

### Ambiente de Desenvolvimento
- **Docker**: Stack completo de desenvolvimento com PHP 8.3 + Apache + MySQL 8.0
- **PHP Local**: 8.4.8 CLI para scripts utilitários e ferramentas de desenvolvimento
- **Banco de Dados**: Schema verificado com 75 tabelas e seeders abrangentes
- **Testes**: Scripts de verificação de migração e seeder incluídos
- **Integração VS Code**: Tarefas pré-configuradas em `.vscode/tasks.json` para automação de desenvolvimento
- **Arquivos de Ambiente**: Arquivos `environment.json` devidamente configurados para desenvolvimento do core e plugins

## Documentação & Desenvolvimento

### Documentação Técnica

Conn2Flow inclui documentação técnica abrangente para desenvolvedores e administradores de sistemas:

- **[📚 Conhecimento do Sistema](ai-workspace/docs/CONN2FLOW-SISTEMA-CONHECIMENTO.md)** - Visão geral completa do sistema e arquitetura
- **[🛠️ Guia de Instalação](ai-workspace/docs/CONN2FLOW-INSTALADOR-DETALHADO.md)** - Instalação e configuração detalhadas
- **[🎨 Layouts & Componentes](ai-workspace/docs/CONN2FLOW-LAYOUTS-PAGINAS-COMPONENTES.md)** - Estrutura UI e sistema de componentes
- **[🔧 Desenvolvimento de Módulos](ai-workspace/docs/CONN2FLOW-MODULOS-DETALHADO.md)** - Como desenvolver módulos personalizados
- **[🔀 Sistema de Roteamento](ai-workspace/docs/CONN2FLOW-ROTEAMENTO-DETALHADO.md)** - Roteamento de URL e manipulação de requisições
- **[⚡ Automação](ai-workspace/docs/CONN2FLOW-AUTOMACAO-EXPORTACAO.md)** - Automação de exportação de recursos
- **[🎨 Frameworks CSS](ai-workspace/docs/CONN2FLOW-FRAMEWORK-CSS.md)** - Integração TailwindCSS e FomanticUI
- **[📱 Sistema Preview](ai-workspace/docs/CONN2FLOW-SISTEMA-PREVIEW-MODALS.md)** - Sistema modal de preview com CodeMirror
- **[🔄 Atualizações do Sistema](ai-workspace/docs/CONN2FLOW-ATUALIZACOES-SISTEMA.md)** - Mecanismo de atualização automatizada
- **[🚀 Sistema de Deploy de Projetos](ai-workspace/docs/CONN2FLOW-SISTEMA-PROJETOS.md)** - Deploy de projetos via API OAuth
- **[🐳 Ambiente Docker](ai-workspace/docs/CONN2FLOW-AMBIENTE-DOCKER.md)** - Ambiente completo de desenvolvimento e testes
- **[⚙️ GitHub Actions](ai-workspace/docs/CONN2FLOW-GITHUB-ACTIONS.md)** - Automação completa CI/CD via GitHub Actions
- **[🌐 Sistema Multilíngue](ai-workspace/docs/CONN2FLOW-SISTEMA-HIBRIDO-MULTILANGUE-CONCLUIDO.md)** - Suporte multilíngue

### Histórico de Mudanças

- **[📋 Changelog Padrão](CHANGELOG.md)** - Changelog padrão da indústria seguindo versionamento semântico
- **[📊 Histórico de Desenvolvimento](ai-workspace/docs/CONN2FLOW-CHANGELOG-HISTORY.md)** - Histórico completo de commits com contexto e insights

### Recursos de Desenvolvimento

O diretório `ai-workspace/` contém todas as ferramentas de desenvolvimento e documentação:
- Scripts para testes, validação e migração
- Prompts detalhados para desenvolvimento assistido por IA
- Base completa de conhecimento do sistema para contribuidores

## Metodologia de Desenvolvimento com IA

### 🤖 Desenvolvimento Colaborativo com Agentes IA

Conn2Flow pioneirou uma **metodologia abrangente de desenvolvimento assistido por IA** ao longo de 12 meses de colaboração ativa com agentes IA (GitHub Copilot, Claude, ChatGPT, Gemini). O diretório `ai-workspace/` representa uma framework madura para desenvolvimento colaborativo humano-IA.

#### **O Que Torna Isso Especial**
- **📚 15 Documentos Técnicos**: Conhecimento abrangente do sistema preservado entre sessões
- **🤖 50+ Conversas de Agentes**: Sessões críticas de desenvolvimento documentadas e preservadas  
- **🔧 20+ Scripts Automatizados**: Ferramentas criadas por agentes IA para validação, testes e deployment
- **📝 Templates Comprovados**: Prompts padronizados que produzem consistentemente qualidade
- **⚡ Ganho de Eficiência de 90%**: Redução dramática no tempo de configuração de contexto para sessões novas de IA

#### **Inovações Chave**
- **Persistência de Conhecimento**: Conhecimento técnico sobrevive entre sessões de IA
- **Desenvolvimento Orientado por Templates**: Interações consistentes e de alta qualidade com IA
- **Fluxos de Trabalho Automatizados**: Scripts criados por IA que automatizam tarefas repetitivas
- **Contexto Histórico**: Soluções preservadas impedem re-resolver os mesmos problemas
- **Metodologia Escalável**: Framework que melhora com cada interação

#### **Para Pesquisadores de IA & Desenvolvedores**
A metodologia `ai-workspace/` demonstra:
- Como manter contexto através de múltiplas sessões de IA
- Técnicas para preservar e transferir conhecimento técnico
- Templates que produzem consistentemente código de alta qualidade
- Integração de assistência IA em fluxos de trabalho profissionais de desenvolvimento
- Soluções práticas para o problema da "janela de contexto" em projetos de longo prazo

**Explore**: [`ai-workspace/README.md`](ai-workspace/README.md) para detalhes completos da metodologia

---

*Isso representa uma das aplicações mais abrangentes de metodologia de desenvolvimento assistido por IA em um sistema ativo de produção.*

## Comunidade & Suporte

### Contribuindo

Nós acolhemos contribuições! Aqui está como você pode ajudar:

- **Reportar Issues**: Use GitHub Issues para reportar bugs ou sugerir recursos
- **Enviar Pull Requests**: Contribua com melhorias de código e novos recursos
- **Documentação**: Ajude a melhorar documentação e traduções
- **Testes**: Teste novas releases e forneça feedback

### Diretrizes de Desenvolvimento

1. **Fork o Repositório**: Crie seu próprio fork para desenvolvimento
2. **Crie Branch de Recurso**: Trabalhe em recursos em branches dedicados
3. **Siga Padrões**: Use padrões PSR de codificação e padrões existentes
4. **Escreva Testes**: Inclua testes para nova funcionalidade
5. **Documente Mudanças**: Atualize documentação para novos recursos

### Obtendo Ajuda

- **GitHub Issues**: Para bugs e solicitações de recursos
- **Discussões**: Para perguntas gerais e suporte da comunidade
- **LinkedIn**: Conecte-se com o fundador em [https://www.linkedin.com/in/otaviocserra/](https://www.linkedin.com/in/otaviocserra/)

## Licença

Conn2Flow é lançado sob uma licença open-source para garantir liberdade de uso, modificação e distribuição. Detalhes da licença serão finalizados em breve com entrada da comunidade.

## Roadmap

### ✅ Concluído Recentemente
- **Sistema de Plugins V2**: Arquitetura revolucionária de plugins com detecção dinâmica e templates automatizados
- **Ambiente Completo de Desenvolvimento**: Ferramentas completas de desenvolvimento com assistência IA
- **Fluxos de Trabalho Automatizados**: GitHub Actions para releases, testes e deployment
- **Documentação Técnica**: 15+ guias abrangentes e base de conhecimento

### Próximos Recursos
- **Marketplace de Plugins Aprimorado**: Sistema de descoberta e instalação de plugins
- **API REST**: API completa para uso headless CMS e integrações
- **App Mobile**: App React Native para gerenciamento de conteúdo
- **Multilíngue Avançado**: Gerenciamento integrado de tradução e fluxos de trabalho
- **Otimização de Performance**: Recursos avançados de caching e otimização
- **Demos Online**: Ambientes de demonstração ao vivo para todos os recursos

### Migração do Sistema Legacy
Usuários do sistema legado B2make podem encontrar ferramentas e documentação de migração na branch `b2make-legacy`.

---

**Conn2Flow - Ambiente Completo de Desenvolvimento CMS. Um Repositório, Stack Completo.**

*Do legado B2make para CMS open-source moderno com sistema revolucionário de plugins e metodologia de desenvolvimento assistido por IA.*