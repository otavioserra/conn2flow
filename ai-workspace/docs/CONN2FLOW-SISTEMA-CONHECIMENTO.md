# Conn2Flow - Sistema de Conhecimento e Documentação Técnica

## 📋 Índice
- [Arquivos de Conhecimento Detalhado](#arquivos-de-conhecimento-detalhado)
- [Visão Geral do Sistema](#visão-geral-do-sistema)
- [Arquitetura e Estrutura](#arquitetura-e-estrutura)
- [Sistema de Instalação (Resumo)](#sistema-de-instalação-resumo)
- [Sistema de Autenticação](#sistema-de-autenticação)
- [Sistema de Layouts](#sistema-de-layouts)
- [Banco de Dados](#banco-de-dados)
- [Módulos e Funcionalidades](#módulos-e-funcionalidades)
- [Configurações e Ambiente](#configurações-e-ambiente)
- [Desenvolvimento e Debugging](#desenvolvimento-e-debugging)
- [Histórico de Implementações](#histórico-de-implementações)

---

## 📚 Arquivos de Conhecimento Detalhado

### Documentação Modular por Área

#### 🛠️ CONN2FLOW-INSTALADOR-DETALHADO.md
**Área**: Sistema de Instalação (Gestor-Instalador)
**Conteúdo**: 
- Arquitetura completa do instalador com classe `Installer.php`
- Processo de 8 etapas detalhado (verificação → banco → migrations → seeds → SSL → auto-login)
- Sistema de auto-login com JWT e cookies persistentes
- Configuração nativa via `config.php` e integração .env
- Páginas de sucesso com remoção automática no dashboard
- Logging avançado e troubleshooting específico

**Quando usar**: Desenvolvimento, manutenção ou debug do sistema de instalação

#### 📋 Futuros Arquivos Planejados
- `CONN2FLOW-AUTENTICACAO-DETALHADO.md` - Sistema completo de autenticação
- `CONN2FLOW-MODULOS-DETALHADO.md` - Desenvolvimento e estrutura de módulos  
- `CONN2FLOW-BANCO-DETALHADO.md` - Estrutura completa de banco de dados
- `CONN2FLOW-CPANEL-DETALHADO.md` - Integração cPanel/WHM completa
- `CONN2FLOW-API-DETALHADO.md` - APIs e integrações externas

### Como Usar Esta Documentação
1. **Este arquivo**: Resumo geral e referência rápida
2. **Arquivos detalhados**: Informações técnicas específicas por área
3. **Contexto futuro**: Agentes especializados poderão consultar arquivos específicos
4. **Estrutura modular**: Evita sobrecarga de contexto em conversações

---

## 🎯 Visão Geral do Sistema

### O que é o Conn2Flow
O **Conn2Flow** é um CMS (Content Management System) complexo e robusto desenvolvido em PHP que funciona como:
- **Núcleo Central**: Um servidor central que gerencia múltiplos hosts distribuídos
- **Sistema Multi-Host**: Cada host tem sua própria instância do gestor-cliente
- **Plataforma Modular**: Sistema baseado em módulos e plugins extensíveis

### Versões do Sistema
- **Conn2Flow Core**: v1.8.4+ (sistema principal)
- **Gestor-Cliente**: Sistema distribuído que roda nos hosts
- **Gestor-Instalador**: Sistema de instalação automatizada

---

## 🏗️ Arquitetura e Estrutura

### Estrutura de Diretórios Principal
```
conn2flow/
├── gestor/                     # Núcleo principal do sistema
│   ├── config.php             # Configurações centrais
│   ├── gestor.php            # ❤️ CORAÇÃO DO SISTEMA - Roteador principal
│   ├── modulos/              # Módulos do sistema
│   │   └── dashboard/        # Módulo principal (sempre carregado no login)
│   ├── bibliotecas/          # Bibliotecas do sistema
│   ├── db/seeds/            # Seeds do banco de dados
│   └── vendor/              # Dependências Composer
├── gestor-cliente/           # Sistema distribuído para hosts
├── gestor-instalador/        # Sistema de instalação
│   └── src/Installer.php    # Classe principal de instalação
├── cpanel/                   # Integrações cPanel
├── docker/                   # Configurações Docker
└── utilitarios/             # Arquivos de documentação e utilitários
```

### 🎯 Arquitetura Central: O Coração do Sistema (gestor.php)

#### O que é o gestor.php
O **`gestor.php`** é o **CORAÇÃO** de todo o sistema Conn2Flow:
- **Roteador Principal**: Processa todas as requisições
- **Gerenciador de Arquivos Estáticos**: Lida com CSS, JS, imagens
- **Iniciador de Processo**: Ponto de entrada para toda aplicação
- **Conectador de Componentes**: Liga layouts, páginas, módulos e componentes

#### Filosofia de Estruturação: HTML no Banco de Dados
O sistema foi projetado para armazenar **TUDO relacionado a conteúdo no banco de dados**:
- Layouts completos
- Páginas específicas
- Componentes reutilizáveis
- Programação específica (módulos)

### 🧩 Sistema de Camadas: A Estrutura Fundamental

#### 1. **LAYOUTS** (Tabela: `layouts`)
- **Função**: Estrutura que se repete (como header/footer do WordPress)
- **Conteúdo**: HTML completo com variáveis dinâmicas
- **Variáveis**: `@[[pagina#url-raiz]]@`, `@[[usuario#nome]]@`, etc.
- **Variável Crítica**: `@[[pagina#corpo]]@` - onde o conteúdo da página é inserido

#### 2. **PÁGINAS** (Tabela: `paginas`)
- **Função**: Conteúdo específico que vai no "meio" da página
- **Vinculação**: Cada página tem um layout associado
- **Roteamento**: Campo `caminho` define a URL no navegador
- **Conteúdo**: HTML específico da página (vai no `@[[pagina#corpo]]@`)

#### 3. **COMPONENTES** (Tabela: `componentes`)
- **Função**: Pedaços reutilizáveis de interface
- **Exemplos**: Alertas, formulários, modais, botões
- **Uso**: Incluídos dentro de páginas ou layouts
- **Vantagem**: Reutilização e padronização

#### 4. **MÓDULOS** (Diretório: `gestor/modulos/`)
- **Função**: Programação específica para páginas
- **Vinculação**: Páginas podem ter ou não um módulo associado
- **Processo**: gestor.php verifica vinculação e inclui módulo automaticamente
- **Exemplo**: `dashboard` tem layout + página + componentes + módulo

### 🔄 Fluxo de Processamento Completo

```
1. Requisição → gestor.php (CORAÇÃO)
       ↓
2. Roteamento → Identifica página pelo caminho
       ↓
3. Busca Página → Tabela `paginas` 
       ↓
4. Busca Layout → Tabela `layouts` (vinculado à página)
       ↓
5. Busca Módulo → Diretório `modulos/` (se vinculado)
       ↓
6. Processa Variáveis → Substitui @[[variáveis]]@ 
       ↓
7. Inclui Componentes → Tabela `componentes` (se necessário)
       ↓
8. Renderiza → HTML final para o navegador
```

### Diferenciação Crítica: Tabelas Core vs Host

#### ⚠️ IMPORTANTE: Nomenclatura de Tabelas
- **Tabelas Core** (sem prefixo): `paginas`, `usuarios`, `modulos`, `layouts`
  - São do gestor principal/central
  - Gerenciam o núcleo do sistema
  
- **Tabelas Host** (prefixo `hosts_`): `hosts_paginas`, `hosts_usuarios`, `hosts_layouts`
  - São para hosts distribuídos externos
  - Cada host tem suas próprias cópias de dados

**Esta diferenciação é FUNDAMENTAL para não confundir onde criar/buscar dados!**

---

## �️ Sistema de Instalação (Resumo)

### Gestor-Instalador
**📖 Documentação detalhada**: `CONN2FLOW-INSTALADOR-DETALHADO.md`

#### Processo Automatizado (8 Etapas)
1. **Verificação de Ambiente** - PHP 7.4+, extensões, permissões
2. **Configuração de Banco** - Conexão e criação automática  
3. **Execução de Migrations** - Estrutura completa das tabelas
4. **Execução de Seeds** - Dados iniciais obrigatórios
5. **Configuração Nativa** - Integração com `config.php` e `.env`
6. **Geração de Chaves SSL** - Proteção por `OPENSSL_PASSWORD`
7. **Criação de Usuário Admin** - Com auto-login JWT
8. **Página de Sucesso** - Layout externo + remoção automática

#### Características Principais
- **Auto-Login**: Token JWT + cookie persistente (30 dias)
- **Configuração Nativa**: Usa `config.php` do sistema (não hardcoded)
- **Página de Sucesso**: Layout ID 23 (externo), removida automaticamente no dashboard
- **Logging Avançado**: Sistema completo de logs com níveis
- **Tratamento de Erros**: Try/catch em todas operações críticas

#### Arquivos Principais
- `gestor-instalador/src/Installer.php` - Classe principal
- `gestor-instalador/index.php` - Interface web
- `gestor/modulos/dashboard/dashboard.php` - Remoção automática da página

---

## 🔐 Sistema de Autenticação

### Configuração de Autenticação
- **Arquivo**: `gestor/autenticacoes/localhost/autenticacao.php`
- **Sistema**: JWT com cookies persistentes
- **Função Principal**: `usuario_gerar_token_autorizacao()`

### Processo de Login
1. Validação de credenciais
2. Geração de token JWT
3. Definição de cookie persistente
4. Redirecionamento para dashboard

### Chaves SSL
- **Geração**: Automatizada durante instalação
- **Proteção**: Senha via variável `OPENSSL_PASSWORD`
- **Localização**: Definida nas configurações de ambiente

---

## 🎨 Sistema de Layouts

### Layouts Principais Identificados

#### Layout ID 1 - Administrativo
- **Nome**: "Layout Administrativo do Gestor"
- **Uso**: Páginas internas com sidebar e menu completo
- **Características**: Menu lateral, topo com perfil, navegação completa

#### Layout ID 23 - Página Externa
- **Nome**: "Layout Página Sem Permissão"
- **Uso**: Páginas externas sem interface administrativa
- **Características**: Apenas logo e conteúdo, sem menu administrativo
- **Ideal para**: Páginas de instalação, erro, informativas

### Quando Usar Cada Layout
- **Layout 1**: Dashboard, módulos administrativos, páginas internas
- **Layout 23**: Páginas de sucesso, erro, instalação, informativas

---

## 💾 Banco de Dados

### Sistema de Migrations e Seeds
- **Localização**: `gestor/db/`
- **Ferramenta**: Phinx (migrations PHP)
- **Seeds**: Dados iniciais automáticos

### Tabelas Principais Identificadas

#### 🎨 Sistema de Apresentação
- **`layouts`**: Templates principais com estrutura completa (header/footer)
- **`paginas`**: Conteúdo específico de cada página (vai no `@[[pagina#corpo]]@`)
- **`componentes`**: Pedaços reutilizáveis (alertas, formulários, modais)

#### 👥 Usuários e Permissões
- `usuarios`: Usuários do sistema
- `usuarios_perfis`: Perfis de acesso
- `usuarios_perfis_modulos`: Permissões por módulo

#### 🔧 Módulos e Sistema
- `modulos`: Módulos disponíveis (programação específica)
- `modulos_grupos`: Agrupamento de módulos
- `hosts`: Hosts distribuídos gerenciados

#### 🌐 Versões Host (hosts_*)
- `hosts_paginas`: Páginas dos hosts distribuídos
- `hosts_layouts`: Layouts dos hosts distribuídos
- `hosts_usuarios`: Usuários dos hosts externos

### 🔧 Sistema de Variáveis Dinâmicas

#### Formato das Variáveis
```php
@[[categoria#variavel]]@
```

#### Exemplos de Variáveis Comuns
```php
@[[pagina#url-raiz]]@       // URL base do sistema
@[[pagina#corpo]]@          // Conteúdo da página (CRÍTICO nos layouts)
@[[pagina#titulo]]@         // Título da página
@[[usuario#nome]]@          // Nome do usuário logado
@[[pagina#css]]@            // CSS específico da página
@[[pagina#js]]@             // JavaScript específico da página
@[[pagina#menu]]@           // Menu do sistema
```

#### ⚠️ Variável CRÍTICA nos Layouts
**`@[[pagina#corpo]]@`** - Esta é a variável mais importante!
- **Função**: Local onde o conteúdo da página é inserido no layout
- **Uso**: Deve estar presente em TODOS os layouts
- **Processo**: gestor.php substitui por conteúdo da tabela `paginas`

---

## 📦 Módulos e Funcionalidades

### Dashboard (Módulo Principal)
- **Arquivo**: `gestor/modulos/dashboard/dashboard.php`
- **Função**: Sempre carregado no login, ponto de entrada principal
- **Funcionalidades**:
  - Menu de módulos dinâmico
  - Sistema de toasts (notificações)
  - Verificação de atualizações
  - **Remoção automática de página de instalação**

#### Função Crítica: dashboard_pagina_inicial()
```php
function dashboard_pagina_inicial(){
    // 1. Remove página de instalação se existir
    dashboard_remover_pagina_instalacao_sucesso();
    
    // 2. Carrega componentes de interface
    // 3. Inclui JavaScript do sistema
    // 4. Gera menu dinâmico
    // 5. Exibe toasts de sistema
}
```

### 🔗 Sistema de Vinculação Página-Módulo

#### Como Funciona a Vinculação
1. **Página** tem um campo que referencia um **Módulo** (opcional)
2. **gestor.php** verifica se página tem módulo vinculado
3. Se tem, **inclui automaticamente** o arquivo do módulo
4. Módulo executa **programação específica** da página

#### Exemplo Prático: Dashboard
```
Página "dashboard" →  Vinculada ao módulo "dashboard"
       ↓
gestor.php detecta vinculação
       ↓
Inclui automaticamente: gestor/modulos/dashboard/dashboard.php
       ↓
Executa: dashboard_pagina_inicial() e outras funções
```

#### Módulos que Utilizam cPanel
- **`host-configuracao`**: Módulo principal de configuração de hosts
  - Usa bibliotecas cPanel para criar/gerenciar contas
  - Criação de usuários FTP
  - Configuração de domínios
  - Gerenciamento de bases de dados
- **Outros módulos** podem usar as funções cPanel conforme necessário

#### Estrutura de um Módulo
```php
// Exemplo: gestor/modulos/dashboard/dashboard.php

// 1. Configuração do módulo
$_GESTOR['modulo-id'] = 'dashboard';

// 2. Funções específicas do módulo
function dashboard_pagina_inicial() { ... }
function dashboard_menu() { ... }
function dashboard_toast() { ... }

// 3. Ponto de entrada/inicialização
function dashboard_start() { ... }
dashboard_start(); // Executado automaticamente
```

### Sistema de Toasts
- **Função**: Notificações na interface
- **Tipos**: Sucesso, erro, informação, atualização
- **Configuração**: Tempo, classe CSS, progresso
- **Uso**: Feedback para usuário sobre ações do sistema

---

## ⚙️ Configurações e Ambiente

### Arquivo config.php
- **Localização**: `gestor/config.php`
- **Função**: Carregamento de configurações centrais
- **Formato**: Usa sistema $_CONFIG nativo
- **Importante**: NÃO usar valores hardcoded, sempre via .env

### Variáveis de Ambiente (.env)
```env
# Banco de dados
DB_HOST=localhost
DB_NAME=conn2flow
DB_USER=root
DB_PASS=senha

# Segurança
OPENSSL_PASSWORD=senha_chaves_ssl
JWT_SECRET=chave_jwt

# Sistema
APP_ENV=local
DEBUG=true
```

### ⚠️ Regra Importante: Configurações
**SEMPRE usar configurações via .env/config.php, NUNCA valores hardcoded!**

---

## 🔧 Desenvolvimento e Debugging

### Ambiente de Desenvolvimento
- **Docker**: Configuração disponível em `/docker`
- **cPanel**: Integrações em `/cpanel`
- **Logs**: Sistema de logging integrado

### Estrutura de Roteamento
- **Arquivo Principal**: `gestor/gestor.php`
- **Sistema**: Baseado em caminhos e módulos
- **Controladores**: Por módulo em `gestor/modulos/`

### Bibliotecas do Sistema
- **Localização**: `gestor/bibliotecas/`
- **Principais**: banco.php, autenticacao.php, formulario.php, **cpanel/**
- **Carregamento**: Automático via `gestor_incluir_bibliotecas()`

### 🔧 Integração cPanel
- **Localização**: `gestor/bibliotecas/cpanel/` (movida da raiz)
- **Função**: Conexão entre Conn2Flow e servidores cPanel/WHM
- **Principais Funcionalidades**:
  - Criação de contas de usuário
  - Criação de bancos de dados  
  - Gerenciamento de FTP
  - Configuração de domínios
  - Criação e atualizações Git
  - Suspensão/reativação de contas

#### Arquivos da Integração cPanel
- **`cpanel-functions.php`**: Funções principais (whm_query, cpanel_query, logs)
- **`cpanel-config.php`**: Configurações de conexão
- **`cpanel-createacct.php`**: Criação de contas
- **`cpanel-createdb.php`**: Criação de bancos de dados
- **`cpanel-ftp-*.php`**: Gerenciamento de FTP
- **`cpanel-git-*.php`**: Operações Git
- **`logs/`**: Logs das operações cPanel

---

## 📚 Histórico de Implementações

### Correção de Ordem de Execução no Instalador (Concluída)
**Data**: Julho 2025
**Problema**: Erro 503 - "Configuration file (.env) not found for domain: localhost"
**Causa**: Auto-login executando antes do arquivo .env estar totalmente configurado
**Solução**:
- Reordenação da execução: extract_files → migrations → seeds → auto-login
- Auto-login movido para após criação completa do .env e usuários no banco
- Documentação atualizada com troubleshooting específico
- Prevenção de erros de configuração não encontrada

### Criação de Sistema de Documentação Modular (Concluída)
**Data**: Julho 2025
**Objetivo**: Estruturar conhecimento em arquivos específicos por área
**Implementação**:
- Criação de `CONN2FLOW-INSTALADOR-DETALHADO.md` com documentação completa do instalador
- Reestruturação do arquivo principal como índice e resumos
- Planejamento de arquivos futuros por área (autenticação, módulos, banco, cPanel, API)
- Sistema modular para evitar sobrecarga de contexto
- Referências cruzadas entre documentos

### Reorganização da Biblioteca cPanel (Concluída)
**Data**: Julho 2025
**Objetivo**: Mover integração cPanel para estrutura organizada
**Implementação**:
- Movimentação de `/cpanel` para `gestor/bibliotecas/cpanel/`
- Atualização da documentação com detalhes de integração
- Registro de funções principais (whm_query, cpanel_query, cpanel_log)
- Documentação de módulos que usam cPanel (host-configuracao)

### Implementação de Auto-Login (Concluída)
**Data**: Julho 2025
**Objetivo**: Login automático após instalação
**Implementação**:
- Adicionada função `createAdminAutoLogin()` no instalador
- Integração com `usuario_gerar_token_autorizacao()`
- Cookie persistente para manter sessão
- Configuração via ambiente nativo

### Correção do Sistema de Configuração (Concluída)
**Problema**: Valores hardcoded no instalador
**Solução**:
- Migração para uso do `config.php` nativo
- Remoção de criação manual de $_CONFIG
- Integração com sistema de .env existente
- Correção da senha SSL via `OPENSSL_PASSWORD`

### Implementação de Página de Sucesso (Concluída)
**Objetivo**: Página informativa pós-instalação com remoção automática
**Implementação**:
- Criação na tabela `paginas` (não `hosts_paginas`)
- Uso do Layout ID 23 (sem menu administrativo)
- Função `dashboard_remover_pagina_instalacao_sucesso()`
- Toast informativo na remoção
- Integração no `dashboard_pagina_inicial()`

### Correção de Layout ID (Concluída)
**Problema**: Uso incorreto do Layout ID 1 (administrativo)
**Solução**: Alteração para Layout ID 23 (externo sem menu)
**Justificativa**: Página de sucesso é externa, não deve ter interface administrativa

### Reorganização da Biblioteca cPanel (Concluída)
**Data**: Julho 2025
**Objetivo**: Melhor organização e integração das funcionalidades cPanel
**Implementação**:
- Movida pasta `cpanel/` da raiz para `gestor/bibliotecas/cpanel/`
- Integração com sistema de bibliotecas do gestor
- Manutenção de todas as funcionalidades existentes
- Logs preservados em `gestor/bibliotecas/cpanel/logs/`

**Funcionalidades cPanel Organizadas**:
- Criação e gerenciamento de contas
- Criação de bancos de dados
- Operações FTP (criar usuário, alterar senha)
- Operações Git (criar repositório, updates)
- Suspensão/reativação de contas
- Mudança de planos/pacotes

---

## 🎯 Pontos de Atenção para Desenvolvimento

### 1. Diferenciação de Tabelas
- **Core**: `paginas`, `usuarios`, `modulos` (sem prefixo)
- **Host**: `hosts_paginas`, `hosts_usuarios` (com prefixo `hosts_`)
- **Sempre verificar qual tabela usar conforme contexto!**

### 2. Layout Selection
- **ID 1**: Interface administrativa completa
- **ID 23**: Páginas externas sem menu
- **Verificar propósito da página antes de escolher layout**

### 3. Sistema de Configuração
- **Usar**: `config.php` e variáveis .env
- **Evitar**: Valores hardcoded em qualquer lugar
- **Verificar**: Se configuração existe antes de usar

### 4. Dashboard como Ponto Central
- **Sempre carregado**: No login do usuário
- **Local ideal**: Para operações automáticas de sistema
- **Função principal**: `dashboard_pagina_inicial()`

### 5. Toasts para Feedback
- **Usar sempre**: Para informar ações ao usuário
- **Configurar adequadamente**: Tempo, classe, mensagem
- **Não falhar**: Em caso de erro, capturar exceção

### 6. ⚠️ Estrutura de Camadas (CRÍTICO)
- **Layout** contém `@[[pagina#corpo]]@` (obrigatório)
- **Página** tem conteúdo que vai no corpo
- **Módulo** é opcional, mas executa programação específica
- **Componentes** são reutilizáveis e incluídos quando necessário

### 7. Relacionamento das Tabelas
```
layouts (1) ←→ (N) paginas ←→ (0..1) modulos
                ↓
            componentes (reutilizáveis)
```

### 8. ⚠️ Variável @[[pagina#corpo]]@ 
- **OBRIGATÓRIA** em todos os layouts
- Local onde conteúdo da página é inserido
- Sem ela, a página não renderiza conteúdo

---

## 📝 Notas de Desenvolvimento

### Padrões de Código Identificados
- **Nomenclatura**: Snake_case para funções, camelCase para variáveis
- **Estrutura**: Comentários com separadores `// =====`
- **Arrays**: Formato `Array()` (PHP antigo)
- **Banco**: Uso de bibliotecas próprias (`banco_select`, `banco_delete`)

### Sistema de Logging
```php
$this->log("Mensagem", 'NIVEL'); // WARNING, ERROR, INFO
```

### Sistema de Redirecionamento
- **Dashboard**: Sempre `dashboard/` (com barra final)
- **Módulos**: Baseado em caminho do módulo
- **Páginas**: Usando campo `caminho` da tabela

---

## 🚨 Troubleshooting Comum

### Problema: Página não encontrada
- **Verificar**: Se está na tabela correta (`paginas` vs `hosts_paginas`)
- **Conferir**: Campo `status = 'A'` (ativo)
- **Validar**: Caminho correto na URL

### Problema: Layout incorreto
- **ID 1**: Para páginas administrativas internas
- **ID 23**: Para páginas externas/informativas
- **Verificar**: Se layout existe na tabela `layouts`

### Problema: Configuração não funciona
- **Verificar**: Se .env está sendo carregado
- **Conferir**: Se config.php está incluído
- **Validar**: Se variável existe no $_CONFIG

### Problema: Toast não aparece
- **Verificar**: Se dashboard_toast foi chamado corretamente
- **Conferir**: Se 'opcoes' está definido
- **Validar**: Se JavaScript está incluído

---

## 📖 Referências Rápidas

### Funções Importantes do Sistema
```php
// Autenticação
usuario_gerar_token_autorizacao($dados)
gestor_usuario() // Retorna dados do usuário logado

// Banco de dados
banco_select(Array(...))
banco_delete(Array(...))
banco_campos_virgulas(Array(...))

// Interface
dashboard_toast(Array(...))
interface_componentes_incluir(Array(...))

// Sistema
gestor_incluir_bibliotecas()
gestor_pagina_javascript_incluir($script)

// cPanel/WHM Integration
whm_query($params) // Executa comandos WHM via API
cpanel_query($params) // Executa comandos cPanel via API
cpanel_log($txt) // Sistema de logs específico cPanel
cpanel_find_error($xml) // Tratamento de erros cPanel
```

### Funções cPanel Específicas
```php
// Principais operações disponíveis:
// - cpanel-createacct.php: Criação de contas
// - cpanel-createdb.php: Criação de bancos de dados
// - cpanel-ftp-add.php: Adição de usuários FTP
// - cpanel-ftp-passwd.php: Alteração de senhas FTP
// - cpanel-changepackage.php: Mudança de planos
// - cpanel-suspendacct.php: Suspensão de contas
// - cpanel-unsuspendacct.php: Reativação de contas
// - cpanel-removeacct.php: Remoção de contas
// - cpanel-git-create.php: Criação de repositórios Git
// - cpanel-git-updates.php: Atualizações Git
```

### Variáveis Globais Importantes
```php
$_GESTOR['url-raiz']          // URL base do sistema
$_GESTOR['usuario-id']        // ID do usuário logado
$_GESTOR['host-id']           // ID do host atual
$_GESTOR['modulo-id']         // ID do módulo atual
$_GESTOR['pagina']            // Conteúdo da página atual
```

---

**Documento mantido por**: GitHub Copilot IA
**Última atualização**: Julho 2025
**Versão**: 1.0.0

> Este documento deve ser atualizado constantemente conforme novos conhecimentos sobre o sistema Conn2Flow são adquiridos. É a base de conhecimento para continuidade entre diferentes sessões de desenvolvimento.
