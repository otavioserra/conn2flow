# Conn2Flow - Ambiente de Desenvolvimento & Arquitetura do Repositório

Este guia técnico fornece documentação abrangente sobre o ambiente de desenvolvimento do Conn2Flow, árvore completa de diretórios, integração com tarefas do VS Code, suíte de testes automatizados e subsistemas centrais.

---

## Índice

- [Estrutura de Diretórios do Repositório](#estrutura-de-diretórios-do-repositório)
- [Ambiente Completo de Desenvolvimento](#ambiente-completo-de-desenvolvimento)
- [Início Rápido para Desenvolvedores](#início-rápido-para-desenvolvedores)
- [Suíte de Testes Automatizados](#suíte-de-testes-automatizados)
- [Tarefas Pré-configuradas do VS Code](#tarefas-pré-configuradas-do-vs-code)
- [Mecanismo de Atualização do Sistema](#mecanismo-de-atualização-do-sistema)
- [Propriedade de Arquivos & Permissões](#propriedade-de-arquivos--permissões)
- [Framework de Desenvolvimento de Plugins](#framework-de-desenvolvimento-de-plugins)

---

## Estrutura de Diretórios do Repositório

```
conn2flow/
├── gestor/                 # Sistema CMS principal (núcleo)
│   ├── assets/             # CSS global, JS, bibliotecas vendor e fontes
│   ├── autenticacoes/      # Configurações de hosts e domínios
│   ├── bibliotecas/        # Bibliotecas PHP do core (gestor, interface, banco, etc.)
│   ├── controladores/      # Controladores MVC e rotinas de manutenção
│   ├── db/                 # Migrações Phinx, seeders de dados e schemas
│   ├── modulos/            # Módulos do sistema (admin-paginas, publisher, etc.)
│   ├── public-access/      # Ponto de entrada público e roteador
│   └── vendor/             # Dependências Composer de backend
│
├── gestor-instalador/      # Instalador web automatizado
│   ├── assets/             # CSS, JS e ícones do instalador
│   ├── lang/               # Traduções multilíngues (pt-br, en)
│   ├── src/                # Lógica de negócio e checagens do instalador
│   └── views/              # Templates do assistente de instalação
│
├── ai-workspace/           # Ambiente de desenvolvimento com IA & base de conhecimento
│   ├── en/                 # Documentação, scripts e prompts em inglês
│   └── pt-br/              # Documentação, scripts e prompts em português
│       ├── docs/           # Documentos técnicos e especificações
│       ├── prompts/        # Templates padronizados de prompts
│       └── scripts/        # Scripts de automação e ambiente dev
│
├── dev-plugins/            # Framework de desenvolvimento de plugins
│   ├── plugins/            # Ambiente de desenvolvimento ativo (private/public)
│   ├── templates/          # Templates base e skeletons de plugins
│   └── tests/              # Ambientes de teste para plugins
│
├── tests/                  # Suíte de testes automatizados na raiz
│   ├── E2E/                # Testes funcionais no navegador (Playwright)
│   ├── Integration/        # Migrações Phinx e testes de integração
│   └── Unit/               # Testes unitários (PHPUnit para PHP, Vitest para JS)
│
├── sdd/                    # Spec-Driven Development (SDD/STD)
│   ├── human-requests/     # Requisições humanas formais (req-XXX.md)
│   ├── implementation/     # Registros de lotes implementados (BATCH-XXX.md)
│   ├── decisions/          # Registros de Decisões Arquiteturais (DECISION-LOG.md)
│   └── validation/         # Checklists de validação e evidências
│
├── dev-environment/        # Stack de desenvolvimento em Docker
│   ├── docker/             # Dockerfile e configurações compose
│   └── data/               # Templates de configuração (environment.json)
│
├── cli/                    # Arquitetura CLI orientada a objetos (`c2f`)
├── .github/                # Workflows do GitHub Actions & automação
└── .vscode/                # Tarefas do editor (tasks.json) e configurações
```

---

## Ambiente Completo de Desenvolvimento

O Conn2Flow fornece um ecossistema completo voltado tanto para o desenvolvimento do CMS núcleo quanto para o desenvolvimento modular de plugins.

### O Que Está Incluído

**Desenvolvimento do Sistema Core:**
- **Código-Fonte Completo do CMS**: Sistema `gestor/` com todos os recursos administrativos.
- **Instalador Web Automatizado**: Assistente de instalação em `gestor-instalador/`.
- **Migrações e Seeders de Banco de Dados**: Schema Phinx completo e sincronização declarativa.
- **Ambiente de Testes**: Stack Docker com PHP 8.3/8.4 + Apache + MySQL 8.0.

**Framework de Desenvolvimento de Plugins:**
- **Diretório de Templates** (`dev-plugins/templates/`): Templates prontos para inicialização rápida.
- **Desenvolvimento Ativo** (`dev-plugins/plugins/`): Espaço para plugins públicos ou privados.
- **Configuração de Ambiente**: Arquivos em `templates/environment/` para parametrização.
- **Scripts Automatizados**: Ferramentas para scaffolding, sincronização e empacotamento.

**Desenvolvimento Assistido por IA:**
- **Base de Conhecimento**: Documentos técnicos especializados em `ai-workspace/pt-br/docs/`.
- **Histórico de Agentes**: Registro de sessões de colaboração multi-agente.
- **Templates Padronizados**: Prompts e diretrizes consistentes de engenharia.

---

## Início Rápido para Desenvolvedores

1. **Clonar o Repositório**
   ```bash
   git clone https://github.com/otavioserra/conn2flow.git
   cd conn2flow
   ```

2. **Instalar Dependências da Raiz e do Core**
   ```bash
   composer install
   npm install
   cd gestor && composer install && cd ..
   ```

3. **Configurar o Ambiente Local**
   ```bash
   cp dev-environment/templates/environment/environment.json dev-environment/data/environment.json
   ```
   Edite `environment.json` com os caminhos locais da sua máquina (`source`, `target`, `dockerPath`).

4. **Iniciar Ambiente Docker (Opcional)**
   ```bash
   cd dev-environment
   docker compose up -d
   ```

---

## Suíte de Testes Automatizados

O Conn2Flow possui uma suíte abrangente cobrindo testes unitários de backend, componentes JS de frontend, migrações de banco e fluxos de ponta a ponta no navegador.

### 1. Configuração do Ambiente Local

Certifique-se de que as seguintes extensões PHP estão habilitadas no seu `php.ini`:
```ini
extension=curl
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=zip
```

Instale os binários de navegadores do Playwright:
```bash
npx playwright install --with-deps chromium
```

### 2. Executando os Testes

* **Testes de Backend (PHPUnit)**:
  ```bash
  composer test
  ```
  Executa testes de bibliotecas centrais, shims de segurança, roteador e migrações Phinx.

* **Testes de Frontend (Vitest)**:
  ```bash
  npm run test
  ```
  Executa testes unitários de JavaScript (ex.: `html-editor`, `admin-tailwind.js`, `interface-tailwind.js`) com emulação de DOM.

* **Testes E2E (Playwright)**:
  ```bash
  npm run test:e2e
  ```
  Dispara testes funcionais em navegador real para fluxos críticos de autenticação, perfil e edição visual.

---

## Tarefas Pré-configuradas do VS Code

O repositório disponibiliza tarefas prontas em `.vscode/tasks.json`. Acesse via extensão **Task Explorer** ou pressionando `Ctrl+P` / `Cmd+P` e digitando `task [Nome da Tarefa]`.

| Categoria | Nome da Tarefa | Comando / Alvo | Descrição |
|---|---|---|---|
| **Docker** | `📦 Docker - Container Status` | `docker ps` | Lista os contêineres Docker ativos. |
| **Docker** | `📦 Docker - Apache Logs > Real Time` | `docker logs ... --follow` | Exibe logs do Apache em tempo real. |
| **Docker** | `📦 Docker - PHP Logs > Real Time` | `tail -f /var/log/...` | Exibe logs de erro do PHP no contêiner. |
| **Core CMS** | `🛠️ Manager - Synchronize => Resources - Local` | `atualizacao-dados-recursos.php` | Regenera o contrato de recursos do banco (`schema-metadata.json`). |
| **Core CMS** | `🛠️ Manager - Synchronize => Database - Test Environment` | `updates-manager-database.sh` | Sincroniza o schema local com migrações e seeders. |
| **Core CMS** | `🛠️ Manager - Synchronize => Files - Test Environment` | `synchronize-manager.sh` | Sincroniza arquivos PHP/JS/CSS com o volume do Docker. |
| **Core CMS** | `🛠️ Manager - Update => All - Test Environment` | Sequência das 3 tarefas acima | Sincronização completa de recursos, arquivos e banco. |
| **Core CMS** | `🛠️ Manager - GIT Release` | `release.sh` | Incrementa versão (patch/minor/major) e compila CSS. |
| **Core CMS** | `🛠️ Manager - Create Module` | `create-new-module.sh` | Cria a estrutura inicial para um novo módulo administrativo. |
| **Plugins** | `🧩 Public/Private Plugins - Synchronize Active Plugin` | `synchronizes.sh` | Sincroniza os arquivos do plugin ativo com o ambiente de testes. |
| **Plugins** | `🧩 Public/Private Plugins - Plugin Resources` | `update-data-resources-plugin.php` | Regenera o catálogo de recursos específico do plugin ativo. |
| **Projetos** | `🗃️ Projects - Update => All - Core & Project` | Sequência de sincronização | Realiza o deploy e atualizações de core para um projeto-alvo. |

---

## Mecanismo de Atualização do Sistema

O Conn2Flow inclui um orquestrador de atualização em `gestor/controladores/atualizacoes/atualizacoes-sistema.php` compatível com CLI e chamadas web incrementais (AJAX).

### Principais Capacidades
- **Download de Artefatos**: Baixa pacotes de release (`gestor.zip`) por tag ou consome artefatos locais (`--local-artifact`).
- **Validação de Integridade**: Compara hashes SHA-256 (`gestor.zip.sha256`).
- **Wipe Seletivo Seguro**: Preserva permanentemente pastas críticas do usuário: `contents/`, `logs/`, `backups/`, `temp/` e `autenticacoes/`.
- **Merge Aditivo de Ambiente**: Realiza merge em `.env` adicionando novas chaves sem sobrescrever configurações existentes.
- **Script Unificado de Banco**: Aplica migrações e seeds de dados em execução inline atômica.

Exemplo CLI:
```bash
./c2f manager:update-all
```

---

## Propriedade de Arquivos & Permissões

Para evitar falhas de permissão ao sincronizar ou extrair arquivos em ambientes Linux/Docker:
```bash
# Definir proprietário adequado para o processo do servidor web
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-gestor
chown -R www-data:www-data /var/www/sites/localhost/conn2flow-github
```

---

## Framework de Desenvolvimento de Plugins

Para guias detalhados de desenvolvimento e regras de plugins, consulte:
- [CONN2FLOW-PLUGIN-ARCHITECTURE.md](CONN2FLOW-PLUGIN-ARCHITECTURE.md)
- [CONN2FLOW-PLUGIN-INSTALADOR-FLUXO.md](CONN2FLOW-PLUGIN-INSTALADOR-FLUXO.md)
