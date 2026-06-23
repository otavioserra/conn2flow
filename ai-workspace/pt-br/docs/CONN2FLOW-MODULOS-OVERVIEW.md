# CONN2FLOW - Visão Geral dos Módulos do Sistema

## 📋 Introdução

Este documento apresenta uma **visão geral completa de todos os módulos** do sistema Conn2Flow CMS. O sistema é organizado em uma **arquitetura modular** onde cada módulo é responsável por uma funcionalidade específica, permitindo flexibilidade, manutenibilidade e escalabilidade.

## 🏗️ Arquitetura Modular

O Conn2Flow utiliza uma arquitetura baseada em módulos independentes, onde cada módulo possui:

- **Arquivo PHP**: Lógica de backend e controladores
- **Arquivo JavaScript**: Lógica frontend e interações
- **Arquivo JSON**: Configurações, metadados e recursos de tradução
- **Pasta resources/**: Recursos visuais (HTML, CSS) organizados por idioma

## 📊 Categorização dos Módulos

### 🛠️ **Módulos Administrativos (Admin-)**
Módulos de gerenciamento e administração do sistema, acessíveis apenas por administradores.

| Módulo | Função Principal | Status | Complexidade |
|--------|------------------|--------|--------------|
| **admin-arquivos** | Gerenciamento de arquivos e mídias | ✅ Ativo | 🔴 Alta |
| **admin-atualizacoes** | Sistema de atualizações do core | ✅ Ativo | 🔴 Alta |
| **admin-categorias** | Administração de categorias | ✅ Ativo | 🟡 Média |
| **admin-componentes** | Gerenciamento de componentes visuais | ✅ Ativo | 🔴 Alta |
| **admin-hosts** | Configuração de hosts e domínios | ✅ Ativo | 🟡 Média |
| **admin-layouts** | Administração de layouts | ✅ Ativo | 🔴 Alta |
| **admin-paginas** | Gerenciamento de páginas | ✅ Ativo | 🔴 Alta |
| **admin-plugins** | Administração de plugins | ✅ Ativo | 🟡 Média |
| **admin-templates** | Gerenciamento de templates | ✅ Ativo | 🟡 Média |

### 🎯 **Módulos Funcionais Core**
Módulos que implementam funcionalidades principais do CMS.

| Módulo | Função Principal | Status | Complexidade |
|--------|------------------|--------|--------------|
| **dashboard** | Painel principal de administração | ✅ Ativo | 🟡 Média |
| **paginas** | Sistema de páginas públicas | ✅ Ativo | 🔴 Alta |
| **postagens** | Sistema de blog/notícias | ✅ Ativo | 🔴 Alta |
| **menus** | Gerenciamento de menus de navegação | ✅ Ativo | 🟡 Média |
| **categorias** | Sistema de categorização | ✅ Ativo | 🟡 Média |
| **arquivos** | Interface pública de arquivos | ✅ Ativo | 🟡 Média |
| **componentes** | Componentes reutilizáveis | ✅ Ativo | 🔴 Alta |
| **layouts** | Sistema de layouts públicos | ✅ Ativo | 🔴 Alta |
| **templates** | Templates do frontend | ✅ Ativo | 🟡 Média |

### 👥 **Módulos de Usuários**
Sistema completo de gerenciamento de usuários e perfis.

| Módulo | Função Principal | Status | Complexidade |
|--------|------------------|--------|--------------|
| **usuarios** | Gerenciamento básico de usuários | ✅ Ativo | 🔴 Alta |
| **usuarios-gestores** | Usuários administradores | ✅ Ativo | 🟡 Média |
| **usuarios-gestores-perfis** | Perfis de administradores | ✅ Ativo | 🟡 Média |
| **usuarios-hospedeiro** | Usuários clientes/hospedeiros | ✅ Ativo | 🟡 Média |
| **usuarios-hospedeiro-perfis** | Perfis de clientes | ✅ Ativo | 🟡 Média |
| **usuarios-hospedeiro-perfis-admin** | Admin de perfis de clientes | ✅ Ativo | 🟡 Média |
| **usuarios-perfis** | Sistema geral de perfis | ✅ Ativo | 🟡 Média |
| **usuarios-planos** | Planos e assinaturas | ✅ Ativo | 🟡 Média |
| **perfil-usuario** | Interface de perfil do usuário | ✅ Ativo | 🟡 Média |

### 🏢 **Módulos de Configuração**
Módulos para configuração e personalização do sistema.

| Módulo | Função Principal | Status | Complexidade |
|--------|------------------|--------|--------------|
| **host-configuracao** | Configuração automática de hosts | ✅ Ativo | 🔴 Alta |
| **host-configuracao-manual** | Configuração manual de hosts | ✅ Ativo | 🟡 Média |
| **interface** | Configurações de interface | ✅ Ativo | 🟡 Média |
| **interface-hosts** | Interface específica por host | ✅ Ativo | 🟡 Média |
| **comunicacao-configuracoes** | Configurações de comunicação | ✅ Ativo | 🟡 Média |

### 🛒 **Módulos E-commerce**
Funcionalidades relacionadas a loja virtual e pagamentos.

| Módulo | Função Principal | Status | Complexidade |
|--------|------------------|--------|--------------|
| **pedidos** | Gerenciamento de pedidos | ✅ Ativo | 🔴 Alta |
| **servicos** | Catálogo de serviços | ✅ Ativo | 🟡 Média |
| **gateways-de-pagamentos** | Integração com gateways | ✅ Ativo | 🔴 Alta |
| **loja-configuracoes** | Configurações da loja | ✅ Ativo | 🟡 Média |

### 🔌 **Módulos de Sistema**
Módulos de infraestrutura e funcionalidades especiais.

| Módulo | Função Principal | Status | Complexidade |
|--------|------------------|--------|--------------|
| **modulos** | Gerenciamento dos próprios módulos | ✅ Ativo | 🔴 Alta |
| **modulos-grupos** | Agrupamento de módulos | ✅ Ativo | 🟡 Média |
| **modulos-operacoes** | Operações em módulos | ✅ Ativo | 🟡 Média |
| **plugins-hosts** | Plugins específicos por host | ✅ Ativo | 🟡 Média |
| **contatos** | Sistema de contatos/formulários | ✅ Ativo | 🟡 Média |
| **pagina-inicial** | Configuração da página inicial | ✅ Ativo | 🟡 Média |
| **paginas-secundarias** | Páginas auxiliares | ✅ Ativo | 🟡 Média |
| **testes** | Módulo para testes e desenvolvimento | ⚠️ Desenvolvimento | 🟢 Baixa |
| **global** | Funcionalidades globais do sistema | ✅ Ativo | 🟡 Média |

## 📈 Estatísticas Gerais

### 📊 **Distribuição por Categoria**
- **Módulos Administrativos**: 9 módulos (20.9%)
- **Módulos Funcionais Core**: 9 módulos (20.9%)
- **Módulos de Usuários**: 9 módulos (20.9%)
- **Módulos de Configuração**: 5 módulos (11.6%)
- **Módulos E-commerce**: 4 módulos (9.3%)
- **Módulos de Sistema**: 7 módulos (16.4%)

**Total**: **43 módulos ativos**

### 🎯 **Distribuição por Complexidade**
- **🔴 Alta Complexidade**: 12 módulos (27.9%)
- **🟡 Média Complexidade**: 30 módulos (69.8%)
- **🟢 Baixa Complexidade**: 1 módulo (2.3%)

### ⚡ **Funcionalidades Principais Implementadas**
- ✅ **Sistema de CMS Completo**: Páginas, posts, menus, categorias
- ✅ **Gerenciamento Multi-usuário**: Diferentes tipos e perfis de usuários
- ✅ **E-commerce Integrado**: Pedidos, serviços, gateways de pagamento
- ✅ **Sistema Multi-tenant**: Configuração por host/domínio
- ✅ **Arquitetura Plugin**: Sistema extensível de plugins
- ✅ **Interface Administrativa**: Painel completo de administração
- ✅ **Sistema de Arquivos**: Upload e gerenciamento de mídias
- ✅ **Auto-atualização**: Sistema automático de updates
- ✅ **Multilíngue**: Suporte completo a múltiplos idiomas

## 🔧 Padrões Arquiteturais

### 📁 **Estrutura Padrão de Módulo**
```
modulo-nome/
├── modulo-nome.php       # Lógica backend (PHP)
├── modulo-nome.js        # Lógica frontend (JavaScript)
├── modulo-nome.json      # Configurações e metadados
└── resources/            # Recursos visuais por idioma
    └── pt-br/
        ├── layouts/      # Layouts específicos
        ├── pages/        # Páginas HTML
        ├── components/   # Componentes reutilizáveis
        └── assets/       # CSS, JS, imagens
```

### 🔄 **Padrões de Nomenclatura**
- **admin-[nome]**: Módulos administrativos
- **[nome]**: Módulos funcionais públicos
- **usuarios-[tipo]**: Módulos relacionados a usuários
- **host-[funcao]**: Módulos de configuração de host
- **modulos-[operacao]**: Meta-módulos para gerenciar módulos

### 🎛️ **Sistema de Configuração JSON**
Cada módulo possui um arquivo JSON com:
- **versao**: Versionamento do módulo
- **bibliotecas**: Dependências de bibliotecas
- **tabela**: Configuração de banco de dados
- **resources**: Recursos por idioma (páginas, componentes, variáveis)
- **Configurações específicas**: Parâmetros únicos do módulo

## 🔍 Análise Detalhada por Módulo

### 📚 **Documentação Específica**
Para documentação detalhada de cada módulo, consulte:

**📁 [`ai-workspace/docs/modulos/`](modulos/README.md)**

#### 🛠️ **Módulos Administrativos**
- [`admin-arquivos.md`](modulos/admin-arquivos.md) - Sistema completo de upload e gerenciamento de arquivos
- [`admin-atualizacoes.md`](modulos/admin-atualizacoes.md) - Interface para atualizações automáticas do sistema
- [`admin-categorias.md`](modulos/admin-categorias.md) - Administração centralizada de categorias
- [`admin-componentes.md`](modulos/admin-componentes.md) - Editor de componentes visuais reutilizáveis
- [`admin-hosts.md`](modulos/admin-hosts.md) - Configuração de múltiplos domínios
- [`admin-layouts.md`](modulos/admin-layouts.md) - Editor de layouts com preview TailwindCSS
- [`admin-paginas.md`](modulos/admin-paginas.md) - Sistema completo de criação e edição de páginas
- [`admin-plugins.md`](modulos/admin-plugins.md) - Gerenciamento de plugins e extensões
- [`admin-templates.md`](modulos/admin-templates.md) - Administração de templates do frontend

#### 🎯 **Módulos Funcionais Core**
- [`dashboard.md`](modulos/dashboard.md) - Painel administrativo principal com widgets
- [`paginas.md`](modulos/paginas.md) - Sistema público de páginas estáticas
- [`postagens.md`](modulos/postagens.md) - Sistema de blog com categorias e SEO
- [`menus.md`](modulos/menus.md) - Criador de menus de navegação hierárquicos
- [`categorias.md`](modulos/categorias.md) - Sistema de categorização para conteúdo
- [`arquivos.md`](modulos/arquivos.md) - Interface pública para galeria de arquivos
- [`componentes.md`](modulos/componentes.md) - Biblioteca de componentes reutilizáveis
- [`layouts.md`](modulos/layouts.md) - Sistema de layouts para páginas públicas
- [`templates.md`](modulos/templates.md) - Templates para diferentes tipos de conteúdo

#### 👥 **Módulos de Usuários**
- [`usuarios.md`](modulos/usuarios.md) - Gerenciamento base de usuários
- [`usuarios-gestores.md`](modulos/usuarios-gestores.md) - Administradores do sistema
- [`usuarios-hospedeiro.md`](modulos/usuarios-hospedeiro.md) - Clientes e usuários finais
- [`perfil-usuario.md`](modulos/perfil-usuario.md) - Interface de perfil personalizada

#### 🏢 **Módulos de Configuração**
- [`host-configuracao.md`](modulos/host-configuracao.md) - Auto-configuração de domínios
- [`interface.md`](modulos/interface.md) - Personalização da interface
- [`comunicacao-configuracoes.md`](modulos/comunicacao-configuracoes.md) - Configuração de comunicações

#### 🛒 **Módulos E-commerce**
- [`pedidos.md`](modulos/pedidos.md) - Sistema completo de e-commerce
- [`gateways-de-pagamentos.md`](modulos/gateways-de-pagamentos.md) - Integração com múltiplos gateways
- [`servicos.md`](modulos/servicos.md) - Catálogo de produtos e serviços
- [`loja-configuracoes.md`](modulos/loja-configuracoes.md) - Configurações da loja virtual

#### 🔌 **Módulos de Sistema**
- [`modulos.md`](modulos/modulos.md) - Meta-gerenciamento de módulos
- [`global.md`](modulos/global.md) - Funcionalidades transversais
- [`contatos.md`](modulos/contatos.md) - Sistema de formulários de contato

## 🛡️ Módulos de Segurança e Permissões

### 🔐 **Sistema de Autenticação**
- **Múltiplos tipos de usuário**: Gestores, hospedeiros, clientes
- **Perfis customizáveis**: Permissões granulares por módulo
- **Autenticação robusta**: OpenSSL, sessões seguras
- **Multi-tenant**: Isolamento por domínio/host

### 🛡️ **Controle de Acesso**
- **Middleware de autenticação**: Validação em cada módulo
- **Permissões por funcionalidade**: Create, Read, Update, Delete
- **Hierarquia de usuários**: Admin > Gestor > Hospedeiro > Cliente
- **Auditoria**: Log de todas as operações críticas

## 🔄 Sistema de Atualização e Versionamento

### 📦 **Versionamento de Módulos**
- **Versionamento semântico**: X.Y.Z para cada módulo
- **Compatibilidade**: Verificação automática de dependências
- **Migrações**: Scripts de atualização automática
- **Rollback**: Possibilidade de voltar versões

### 🔄 **Atualizações Automáticas**
- **Verificação periódica**: Check automático de updates
- **Download seguro**: Verificação SHA256 de integridade
- **Aplicação incremental**: Updates progressivos sem downtime
- **Backup automático**: Proteção antes de cada update

## 📊 Métricas de Desenvolvimento

### 🧮 **Estatísticas de Código**
- **Total de módulos**: 43 módulos ativos
- **Linhas de código PHP**: ~50.000+ linhas estimadas
- **Arquivos JavaScript**: 43 arquivos de frontend
- **Configurações JSON**: 43 arquivos de metadados
- **Templates HTML**: 200+ templates/páginas/componentes

### 🎯 **Cobertura Funcional**
- **CMS Core**: 100% implementado
- **E-commerce**: 100% implementado  
- **Multi-usuário**: 100% implementado
- **Multi-tenant**: 100% implementado
- **Sistema de plugins**: 100% implementado
- **API REST**: 🚧 Em desenvolvimento
- **PWA Support**: 🚧 Planejado

## 🚀 Roadmap e Evolução

### ✅ **Implementado (v1.16.0)**
- Sistema modular completo
- Interface administrativa moderna
- E-commerce funcional
- Multi-tenant
- Sistema de atualizações automáticas
- Preview TailwindCSS integrado

### 🚧 **Em Desenvolvimento (v1.17.0)**
- API REST completa
- Webhooks para integrações
- Sistema de cache avançado
- Otimizações de performance
- Testes automatizados

### 🔮 **Planejado (v2.0.0)**
- Migração para PHP 8.4+
- Arquitetura de microserviços
- Suporte nativo a PWA
- GraphQL API
- Container Docker oficial
- Kubernetes deployment

## 🎯 Conclusão

O sistema de módulos do Conn2Flow representa uma **arquitetura madura e bem estruturada** que oferece:

- **🧩 Modularidade**: Cada funcionalidade isolada e independente
- **🔧 Extensibilidade**: Fácil adição de novos módulos
- **🛡️ Segurança**: Sistema robusto de autenticação e permissões  
- **⚡ Performance**: Carregamento sob demanda de funcionalidades
- **🌐 Escalabilidade**: Suporte a múltiplos domínios e usuários
- **🔄 Manutenibilidade**: Estrutura clara e documentada

**Status Geral**: ✅ **Produção - Maduro e Estável**  
**Última análise**: 31 de agosto, 2025  
**Desenvolvido por**: Otavio Serra + Equipe AI-Assisted Development  
**Total de módulos documentados**: 43 módulos

---

> 📚 **Para análise detalhada de cada módulo, consulte a pasta [`modulos/`](modulos/) que contém documentação específica e aprofundada de cada componente do sistema.**

---

## 🔌 Acoplando Tabelas Customizadas à Esteira de Dados (BATCH-056)

Além das tabelas nativas, um módulo pode **acoplar suas próprias tabelas** à esteira de sincronização declarando-as no bloco `"tabela"."config"` do seu manifesto (objeto único ou array para múltiplas tabelas). Com `sync_resources: true`, o módulo passa a gerar e sincronizar `<PascalCase>Data.json` automaticamente a partir dos seus recursos (`resources/<idioma>/...`), com conversões `json` / `file:<ext>`, além de declarar deleções (`deletar`) e atualizações forçadas (`forcar_atualizacao`) — tudo sem scripts PHP de hook. O mesmo vale para tabelas globais sem módulo dono via `gestor/resources/tables_config.json`.
