# Conn2Flow - Ambiente Completo de Desenvolvimento CMS

> 📖 **Disponível em múltiplos idiomas**: [🇺🇸 English](README.md) | 🇧🇷 Português (este arquivo)

O Conn2Flow é um Sistema de Gestão de Conteúdo (CMS) modular e de alta performance construído em PHP 8.3+, Tailwind CSS moderno e arquitetura desacoplada, projetado para implantações multi-inquilino (multi-tenant), edição visual de páginas e desenvolvimento assistido por IA.

---

## 🆕 Última Versão

**v2.10.0 (Agosto de 2026)** *(Base atual: v2.9.51)*

- **Dependências de Sistema Automáticas no Tailwind**: Modais e dimmers (`interface_alerta`, modais de confirmação) são automaticamente reconhecidos e estilizados em todas as páginas Tailwind, sem necessidade de declaração manual de dependência.
- **Assets Externos Locais & Zero CDN**: Todas as bibliotecas de frontend (jQuery, SortableJS, DataTables, Quill, Lucide) e fontes Google Fonts são hospedadas localmente no disco, eliminando riscos de supply-chain e latência de rede externa.
- **Minificação de Assets no Build**: Minificação nativa via Terser/CSS integrada ao comando `./c2f manager:update-all` e aos fluxos de release, reduzindo o tráfego de scripts e estilos em mais de 50%.
- **Tabelas Administrativas Responsivas**: Rolagem horizontal contida em viewports largos (>=1200px), evitando armadilhas de rolagem vertical nas páginas administrativas.
- **Segurança Reforçada & Recuperação de Sessão**: Validação rigorosa de CSRF com tokens CSPRNG, cabeçalhos de segurança HTTP, recuperação limpa de sessão expirada em `/signin/` e Tokens de Acesso Pessoal (PAT) com escopos dedicados.
- **Compatibilidade com o Instalador Web v2**: Integração total com a distribuição automatizada do [Instalador v2.0.0](https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.0.0/instalador.zip).

Para ver o registro completo de mudanças, consulte o [CHANGELOG-PT-BR.md](CHANGELOG-PT-BR.md).

---

## ⚡ Instalação Rápida

O Conn2Flow disponibiliza um assistente web automatizado de instalação que configura o banco de dados, popula tabelas essenciais e gera as chaves de criptografia.

### 1. Download e Extração
Baixe o pacote oficial da release `instalador-v2.0.0`:

```bash
# Linux/macOS
curl -L -o instalador.zip https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.0.0/instalador.zip
unzip instalador.zip -d /var/www/html/

# Windows PowerShell
Invoke-WebRequest -Uri "https://github.com/otavioserra/conn2flow/releases/download/instalador-v2.0.0/instalador.zip" -OutFile "instalador.zip"
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

## 👥 Comunidade, Suporte & Roadmap

- **Issues no GitHub**: Reporte problemas ou sugira novas melhorias arquiteturais.
- **Contato Profissional**: Conecte-se com o mantenedor no [LinkedIn](https://www.linkedin.com/in/otaviocserra/).
- **AI Workspace**: Explore diretrizes de colaboração multi-agente em [ai-workspace/](ai-workspace/README.md).
- **Licença**: Distribuído sob a licença de código aberto MIT.
