# Conn2Flow - Gestor Documentação Técnica Detalhada

## 📋 Índice
- [🏗️ Arquitetura Geral](#🏗️-arquitetura-geral)
  - [Estrutura de Diretórios](#estrutura-de-diretórios)
  - [Coração do Sistema (gestor.php)](#coração-do-sistema-gestorphp)
  - [Sistema de Camadas](#sistema-de-camadas)
  - [Fluxo de Processamento](#fluxo-de-processamento)
- [📚 Sistema de Recursos](#📚-sistema-de-recursos)
  - [Estrutura de Recursos](#estrutura-de-recursos)
  - [Recursos Globais](#recursos-globais)
  - [Recursos por Módulo](#recursos-por-módulo)
  - [Formatação de Recursos](#formatação-de-recursos)
  - [Arquivos Físicos](#arquivos-físicos)
  - [Dinâmica de Criação/Consumo](#dinâmica-de-criação-consumo)
- [💾 Banco de Dados](#💾-banco-de-dados)
  - [Estrutura de Dados](#estrutura-de-dados)
  - [Sistema de Migrações](#sistema-de-migrações)
  - [Tabelas Principais](#tabelas-principais)
- [🔧 Sistema de Configuração](#🔧-sistema-de-configuração)
  - [config.php](#configphp)
  - [Variáveis de Ambiente](#variáveis-de-ambiente)
  - [Multi-tenant](#multi-tenant)
- [📦 Sistema de Plugins](#📦-sistema-de-plugins)
  - [Arquitetura de Plugins](#arquitetura-de-plugins)
  - [Processo de Instalação](#processo-de-instalação)
- [🔐 Segurança](#🔐-segurança)
  - [Autenticação](#autenticação)
  - [Autorização](#autorização)
- [🌐 Sistema Web](#🌐-sistema-web)
  - [Roteamento](#roteamento)
  - [Cache e Performance](#cache-e-performance)
- [📝 Sistema de Templates](#📝-sistema-de-templates)
  - [Variáveis Dinâmicas](#variáveis-dinâmicas)
  - [Processamento](#processamento)
- [🎮 Controladores](#🎮-controladores)
  - [Controladores do Sistema](#controladores-do-sistema)
  - [Controladores de Módulos](#controladores-de-módulos)
- [📚 Bibliotecas](#📚-bibliotecas)
  - [Bibliotecas Core](#bibliotecas-core)
  - [Bibliotecas Especializadas](#bibliotecas-especializadas)
- [🔍 Desenvolvimento](#🔍-desenvolvimento)
  - [Ambiente de Dev](#ambiente-de-dev)
  - [Debugging](#debugging)
  - [Ferramentas](#ferramentas)
- [📖 Referências Rápidas](#📖-referências-rápidas)
  - [Funções Importantes](#funções-importantes)
  - [Variáveis Globais](#variáveis-globais)
  - [Estruturas de Dados](#estruturas-de-dados)

---

## 🏗️ Arquitetura Geral

### Estrutura de Diretórios
```
conn2flow/
	├── gestor/                         # 🏠 Núcleo principal do sistema
	│   ├── config.php                  # ⚙️ Configurações centrais e .env
	│   ├── gestor.php                  # ❤️ CORAÇÃO DO SISTEMA - Roteador principal
	│   ├── modulos/                    # 📦 Módulos do sistema
	│   ├── bibliotecas/                # 📚 30+ bibliotecas do sistema
	│   ├── controladores/              # 🎮 Controladores específicos
	│   ├── db/                         # 💾 Banco de dados
	│   │   ├── data/                   # 📄 Dados iniciais (JSON)
	│   │   └── migrations/             # 🔄 Migrações Phinx
	│   ├── assets/                     # 🎨 Arquivos estáticos
	│   ├── contents/                   # 📝 Conteúdo gerenciado
	│   ├── logs/                       # 📋 Logs do sistema
	│   ├── resources/                  # 📚 Recursos globais
	│   └── vendor/                     # 📦 Dependências Composer
	├── gestor-instalador/              # 📦 Instalador do sistema
	├── dev-environment/                # 🐳 Ambiente Docker
	└── ai-workspace/                   # 🤖 Ferramentas de desenvolvimento
```

### Coração do Sistema (gestor.php)

O **`gestor.php`** é o **CORAÇÃO** absoluto do sistema Conn2Flow:

#### 🎯 Funcionalidades Principais:
- **🛣️ Roteador Principal**: Processa TODAS as requisições HTTP
- **📁 Gerenciador de Arquivos Estáticos**: CSS, JS, imagens com cache otimizado
- **🚀 Iniciador de Processo**: Ponto de entrada da aplicação web
- **🔗 Conectador de Componentes**: Liga layouts, páginas, módulos e componentes
- **🔐 Sistema de Sessões**: Gerencia autenticação e estado do usuário
- **🔄 Processador de Variáveis**: Substitui `@[[variavel]]@` dinamicamente

#### ⚡ Processo de Inicialização:
1. **Carrega configurações** (`config.php`)
2. **Processa URL** e identifica rota
3. **Verifica autenticação** e permissões
4. **Carrega layout** da página solicitada
5. **Processa variáveis** dinâmicas
6. **Inclui componentes** necessários
7. **Renderiza HTML** final

### Sistema de Camadas

O sistema usa uma arquitetura de **4 camadas** inteligente:

#### 1. 🏗️ **LAYOUTS** (Tabela: `layouts`)
- **Função**: Estrutura que se repete (header/footer)
- **Conteúdo**: HTML completo com variáveis dinâmicas
- **Variável Crítica**: `@[[pagina#corpo]]@` - onde conteúdo é inserido
- **Campos**: `id`, `html`, `css`, `framework_css`, `id_layouts`
- **Inclusão**: Automática em toda página

#### 2. 📄 **PÁGINAS** (Tabela: `paginas`)
- **Função**: Conteúdo específico que vai no "corpo" da página
- **Vinculação**: Cada página tem layout associado (`id_layouts`)
- **Roteamento**: Campo `caminho` define URL no navegador
- **Conteúdo**: HTML específico (vai no `@[[pagina#corpo]]@`)
- **Campos**: `id`, `html`, `css`, `caminho`, `id_layouts`, `titulo`

#### 3. 🧩 **COMPONENTES** (Tabela: `componentes`)
- **Função**: Elementos reutilizáveis de interface
- **Exemplos**: Alertas, formulários, modais, botões, menus
- **Uso**: Incluídos via `@[[componente#nome]]@`
- **Campos**: `id`, `html`, `css`, `modulo`, `id_componentes`
- **Inclusão**: Dinâmica por variáveis ou programática

#### 4. 📦 **MÓDULOS** (Diretório: `gestor/modulos/`)
- **Função**: Lógica de negócio e processamento específico
- **Estrutura**: Pasta própria com arquivos PHP/JS
- **Campos**: `id`, `nome`, `titulo`, `icone`, `modulo_grupo_id`, `plugin`
- **Integração**: Conectam layouts/páginas via variáveis

### Fluxo de Processamento

```
🌐 Requisição HTTP
       ↓
🏠 gestor.php (CORACÃO)
       ↓
🛣️ Roteamento → Identifica página por caminho
       ↓
📄 Busca Página → Tabela `paginas`
       ↓
🏗️ Busca Layout → Tabela `layouts` (vinculado)
       ↓
📦 Busca Módulo → `modulos/` (se vinculado)
       ↓
🔄 Processa Variáveis → Substitui @[[variáveis]]@
       ↓
🧩 Inclui Componentes → Tabela `componentes`
       ↓
🎨 Renderiza → HTML final para navegador
```

---

## 📚 Sistema de Recursos

### Estrutura de Recursos

O sistema possui **2 tipos de recursos**:

#### 🌍 **Recursos Globais** (`gestor/resources/`)
```
gestor/resources/
├── lang/                      # Pasta lang, para Português Brasil usar `pt-br`
│   ├── components/            # Componentes globais
│   ├── layouts/               # Layouts globais
│   ├── pages/                 # Páginas globais
│   ├── components.json        # Mapeamento componentes
│   ├── layouts.json           # Mapeamento layouts
│   ├── pages.json             # Mapeamento páginas
│   └── variables.json         # Variáveis globais
└── resources.map.php          # Mapeamento geral de cada linguagem
```
- resources.map.php:
```php
$resources = [
	'languages' => [
        'lang-slug' => [ // ex: 'pt-br', 'en-us', etc.
            'name' => 'Nome da Língua',
            'data' => [ // Localização dos arquivos JSON relativo a cada pasta `lang-slug`
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
                'variables' => 'variables.json',
            ],
            'version' => '1',
        ],
    ],
];
```

#### 📦 **Recursos por Módulo** (`modulos/{modulo-id}/resources/`)
```
modulos/{modulo-id}/resources/
├── {modulo-id}.json               # Configurações do módulo
├── resources/                     # Recursos específicos
│   └── lang/
│       ├── components/
│       ├── layouts/
│       └── pages/
```

### Formatação de Recursos

#### 📋 Estrutura Base dos JSONs:
```json
{
    "name": "nome",           // Campo 'nome' da tabela SQL
    "id": "id",              // Campo 'id' da tabela SQL
    "version": "1.0",        // Gerado automaticamente
    "checksum": {            // Gerado automaticamente
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 🏗️ Layout Específico:
```json
{
    "name": "nome",
    "id": "id",
    "version": "1.0",
    "checksum": {
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 📄 Página Específica:
```json
{
    "name": "nome",
    "id": "id",
    "layout": "layout-id",
    "path": "caminho/",
    "type": "system",        // "sistema" → "system", "pagina" → "page"
    "option": "opcao",       // OPCIONAL
    "root": true,            // Se "raiz" = '1', ou seja, num redirecionamento para a raiz, essa página será a raiz.
    "version": "1.0",
    "checksum": {
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 🧩 Componente Específico:
```json
{
    "name": "nome",
    "id": "id",
    "version": "1.0",
    "checksum": {
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 🔧 Variável Específica:
```json
{
    "id": "id",
    "valor": "valor",
    "tipo": "string"         // string, text, bool, number, etc.
}
```

### Arquivos Físicos

#### 📁 Estrutura de Armazenamento:
```
recurso_folder/                    # layouts, pages, components
├── {recurso-id}/                  # Pasta com ID do recurso
│   ├── {recurso-id}.html          # HTML do recurso (opcional)
│   └── {recurso-id}.css           # CSS do recurso (opcional)
```

#### ⚠️ Regras Importantes:
- **ID obrigatório**: Mesmo do campo `id` do JSON
- **Arquivos opcionais**: HTML e CSS podem existir separadamente
- **Processamento**: Sistema busca arquivo físico baseado no ID

### Dinâmica de Criação/Consumo

#### 🔄 Processo de Recursos:

1. **📝 Criação/Modificação**:
   - **Arquivos físicos**: HTML/CSS salvos em arquivos
   - **Metadados**: Armazenados nos arquivos JSON
   - **Variáveis**: Conteúdo completo no JSON

2. **⚙️ Processamento**:
   - **Script**: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
   - **GitHub Action**: Chamado automaticamente em releases
   - **Desenvolvimento**: Pode ser executado manualmente

3. **💾 Consumo**:
   - **Não direto**: JSONs e arquivos físicos não são consumidos diretamente
   - **Banco de dados**: Processados e armazenados nas tabelas específicas e portanto, consumidos via SQL
   - **Debug mode**: Exceção para desenvolvimento

4. **📊 Tabelas de Destino**:
   - `layouts`: Estruturas de página reutilizáveis
   - `paginas`: Conteúdo específico das páginas
   - `componentes`: Elementos de interface reutilizáveis
   - `variaveis`: Variáveis dinâmicas do sistema

---

## 💾 Banco de Dados

### Estrutura de Dados

#### 📂 Organização:
```
gestor/db/
├── data/                          # 📄 Dados iniciais/atualizações (JSON)
│   ├── ModulosData.json           # Dados dos módulos
│   ├── PaginasData.json           # Dados das páginas
│   └── ...
└── migrations/                    # 🔄 Migrações Phinx
    ├── 001_create_modulos_table.php
    └── ...
```

### Sistema de Migrações

#### 🛠️ Phinx Framework:
```php
final class CreateModulosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('modulos', ['id' => 'id_modulos']);
        $table->addColumn('id_modulos_grupos', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['limit' => 255])
              ->addColumn('id', 'string', ['limit' => 255])
              ->create();
    }
}
```

#### ✨ Funcionalidades:
- **📈 Versionamento**: Controle completo do schema
- **🔙 Rollback**: Reversão de mudanças
- **🌱 Seeds**: Dados iniciais via JSON. IMPORTANTE: atualizações também usam o mesmo formato.
- **⚡ Migrações**: Estrutura programática das tabelas

### Tabelas Principais

#### 🎨 **Sistema de Apresentação**:
- **`layouts`**: Templates principais (header/footer)
- **`paginas`**: Conteúdo específico das páginas
- **`componentes`**: Elementos reutilizáveis usados como blocos dentro das páginas e layouts.

#### 👥 **Usuários e Permissões**:
- **`usuarios`**: Dados dos usuários
- **`usuarios_perfis`**: Perfis de acesso
- **`usuarios_perfis_modulos`**: Permissões por módulo
- **`sessoes`**: Sessões ativas
- **`tokens`**: Tokens de API

#### 📦 **Módulos e Sistema**:
- **`modulos`**: Módulos disponíveis
- **`modulos_grupos`**: Agrupamento de módulos
- **`plugins`**: Plugins instalados

#### 🔧 **Outros**:
- **`variaveis`**: Variáveis do sistema
- **`historico`**: Log de ações
- **`arquivos`**: Gestão de arquivos

---

## 🔧 Sistema de Configuração

### config.php

#### ⚙️ Carregamento Inteligente:
```php
// Carrega .env baseado no domínio
$dotenv = Dotenv\Dotenv::createImmutable($_GESTOR['AUTH_PATH_SERVER']);
$dotenv->load();

// Configurações do banco via .env
$_BANCO = [
    'tipo'    => $_ENV['DB_CONNECTION'] ?? 'mysqli',
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'nome'    => $_ENV['DB_DATABASE'] ?? '',
    'usuario' => $_ENV['DB_USERNAME'] ?? '',
    'senha'   => $_ENV['DB_PASSWORD'] ?? '',
];
```

#### 🎯 Funcionalidades:
- **🌐 Detecção de domínio**: Configurações por ambiente
- **🔐 Segurança**: Carregamento seguro de credenciais
- **📦 Dependências**: Inclusão automática de bibliotecas
- **⚡ Performance**: Cache inteligente de configurações

### Variáveis de Ambiente

#### 📄 Estrutura do .env:
```env
# 🗄️ Banco de dados
DB_CONNECTION=mysqli
DB_HOST=localhost
DB_DATABASE=conn2flow
DB_USERNAME=root
DB_PASSWORD=senha

# 🔐 Segurança
OPENSSL_PASSWORD=senha_ssl
JWT_SECRET=chave_jwt

# ⚙️ Sistema
APP_ENV=local
DEBUG=true
```

#### 📂 Localização:
- Detecção automática baseada no domínio de acesso:
```
gestor/autenticacoes/
├── dominio1.com/
│   └── .env
├── dominio2.com/
│   └── .env
└── localhost/
    └── .env
```

### Multi-tenant

#### 🏢 Isolamento Completo:
- **🌐 Por domínio**: `$_SERVER['SERVER_NAME']`
- **📁 Arquivos .env**: Específicos por ambiente
- **🗄️ Banco**: Isolamento completo entre instâncias
- **🔧 Configurações**: Independentes por tenant

---

## 📦 Sistema de Plugins

### Arquitetura de Plugins

#### 📂 Estrutura:
```
plugins/
├── plugin-id/
│   ├── manifest.json          # 📋 Metadados do plugin
│   ├── controllers/           # 🎮 Controladores específicos
│   ├── db/                    # 💾 Banco de dados
│   │   ├── migrations/        # 🔄 Migrações do plugin
│   │   └── data/              # 📄 Dados iniciais
│   ├── modules/               # 📦 Módulos do plugin
│   ├── resources/             # 📚 Recursos visuais
│   └── assets/                # 🎨 Arquivos estáticos
```

#### 🔗 Integração:
- **📦 Isolamento**: Plugins completamente isolados
- **🔄 Migrações**: Automáticas na instalação/atualizações
- **📚 Recursos**: Layouts, páginas, componentes próprios
- **🎯 API**: Integração com sistema principal

### Processo de Instalação

#### 📋 Etapas:
1. **📥 Download**: ZIP do plugin
2. **📦 Extração**: Para diretório staging
3. **🔄 Migrações**: Execução automática
4. **📊 Dados**: Sincronização de dados
5. **✅ Ativação**: Plugin operacional

---

## 🔐 Segurança

### Autenticação

#### 🛡️ Mecanismos:
- **🔑 JWT**: Tokens seguros com expiração
- **🍪 Sessões**: Gerenciamento completo com garbage collector
- **🔒 Cookies**: HTTPOnly, Secure, SameSite
- **🔐 OpenSSL**: Criptografia de chaves privadas

### Autorização

#### 👥 Controle de Acesso:
- **👤 Perfis**: Controle granular por usuário
- **📦 Módulos**: Permissões específicas
- **🌐 Hosts**: Isolamento multi-tenant
- **🔧 Funções**: Controle fino de funcionalidades

---

## 🌐 Sistema Web

### Roteamento

#### 🛣️ Funcionalidades:
- **🔗 URLs limpas**: Sem query strings
- **📄 Baseado em páginas**: Campo `caminho` da tabela `paginas`
- **📁 Arquivos estáticos**: Suporte completo
- **🔄 Redirecionamentos**: 301 automáticos

### Cache e Performance

#### ⚡ Otimizações:
- **🏷️ ETags**: Cache inteligente de arquivos estáticos
- **🗜️ Compressão**: Automática de conteúdo
- **🎨 Assets**: Otimização automática
- **📈 Performance**: Cache de consultas frequentes

---

## 📝 Sistema de Templates

### Variáveis Dinâmicas

#### 🔄 Formato:
```html
@[[categoria#variavel]]@
```

#### 📋 Exemplos Comuns:
```html
@[[pagina#url-raiz]]@        <!-- URL base do sistema -->
@[[pagina#corpo]]@           <!-- CONTEÚDO DA PÁGINA (CRÍTICO!) -->
@[[usuario#nome]]@           <!-- Nome do usuário logado -->
@[[pagina#titulo]]@          <!-- Título da página -->
@[[componente#menu]]@        <!-- Menu do sistema -->
```

#### ⚠️ Variável CRÍTICA:
**`@[[pagina#corpo]]@`** - Esta é a mais importante!
- **📍 Local**: Onde conteúdo da página é inserido no layout
- **🔧 Uso**: Deve estar presente em TODOS os layouts
- **⚙️ Processo**: gestor.php substitui pelo conteúdo da tabela `paginas`

### Processamento

#### 🔄 Funcionalidades:
- **⚡ Tempo real**: Substituição dinâmica
- **🔀 Condicionais**: Suporte a lógica condicional
- **📦 Por módulo**: Variáveis específicas
- **💾 Cache**: Inteligente para performance

---

## 🎮 Controladores

### Controladores do Sistema

#### 📂 Localização: `gestor/controladores/`

#### 🔧 Principais:
- **`arquivo-estatico.php`**: Serve arquivos estáticos com cache
- **`atualizacao-plugin.php`**: Instalação/atualização de plugins
- **`atualizacoes-banco-de-dados.php`**: Migrações e atualizações do sistema
- **`plataforma-gateways.php`**: Processamento de pagamentos

#### 🎯 Funcionalidades:
- **🔗 URLs especiais**: `_gateways`, webhooks, etc.
- **🌐 APIs REST**: Endpoints para integrações
- **📨 Webhooks**: Recebimento de notificações externas
- **⏰ CRON jobs**: Tarefas agendadas

### Controladores de Módulos

#### 📂 Localização: `modulos/{modulo-id}/`

#### 📋 Estrutura Típica:
```
modulo-nome/
├── modulo-nome.php           # 🔧 Lógica backend (PHP)
├── modulo-nome.js            # 🎨 Lógica frontend (JavaScript)
├── modulo-nome.json          # ⚙️ Configurações e metadados
└── resources/                # 📚 Recursos visuais
```

#### 🔄 Processo:
1. **🔗 Vinculação**: Página referencia módulo
2. **📦 Inclusão**: gestor.php inclui automaticamente
3. **⚙️ Inicialização**: Função `start()` executada
4. **🔧 Processamento**: Lógica específica do módulo

---

## 📚 Bibliotecas

### Bibliotecas Core

#### 💾 **banco.php** - Camada de Dados:
```php
// Conexão automática e reconexão
// CRUD completo (select, insert, update, delete)
// Tratamento de erros e debug
// Funções utilitárias (escape, stripslashes, etc.)
```

#### 🏠 **gestor.php** - Sistema Principal:
```php
gestor_componente()           // Carrega componentes
gestor_layout()              // Carrega layouts
gestor_variaveis()           // Sistema de variáveis
// Sistema de sessões e autenticação
```

#### 📝 **modelo.php** - Templates:
```php
// Substituição de variáveis
// Manipulação de tags HTML
// Funções de processamento de texto
```

#### 👤 **usuario.php** - Autenticação:
```php
usuario_gerar_token_autorizacao()  // JWT
// Criptografia OpenSSL
// Autenticação e autorização
```

### Bibliotecas Especializadas

#### 🛠️ Utilitários:
- **`html.php`**: Manipulação DOM com XPath
- **`comunicacao.php`**: APIs e comunicação externa
- **`formulario.php`**: Processamento de formulários
- **`log.php`**: Sistema de logging

#### 📊 Especializadas:
- **`pdf.php`**: Geração de PDFs (FPDF)
- **`ftp.php`**: Transferência de arquivos
- **`paypal.php`**: Integração PayPal

---

## 🔍 Desenvolvimento

### Ambiente de Dev

#### 🐳 Docker Environment:
- **📂 Localização**: `dev-environment/docker/`
- **🔄 Sincronização**: Scripts automatizados
- **📊 Logs**: Apache e PHP integrados
- **💾 Banco**: MySQL containerizado

#### 🔧 Configurações:
- **📁 Carregamento**: Arquivos ao invés do banco
- **🔄 Hot reload**: Automático
- **🐛 Debug**: Detalhado
- **📋 Logs**: Estruturados

### Debugging

#### 🛠️ Ferramentas:
- **📊 Logs estruturados**: Sistema integrado
- **🐛 Debug mode**: Carregamento de arquivos
- **📈 Profiling**: Performance analysis
- **🔍 Inspeção**: Estado das variáveis

### Ferramentas

#### 🤖 AI Workspace:
- **📂 Localização**: `ai-workspace/`
- **📚 Documentação**: Guias e referências
- **🔧 Scripts**: Automação de tarefas
- **📋 Templates**: Estruturas padrão

#### 🔄 Sincronização:
- **🐳 Docker**: Ambiente containerizado
- **📦 Scripts**: Automação de sincronização
- **🧪 Testes**: Estrutura de testes
- **📊 Monitoramento**: Performance e logs

---

## 📖 Referências Rápidas

### Funções Importantes

#### 👤 Autenticação:
```php
usuario_gerar_token_autorizacao($dados)  // JWT
gestor_usuario()                        // Dados do usuário logado
```

#### 💾 Banco de Dados:
```php
banco_select(Array(...))                // SELECT
banco_insert(Array(...))                // INSERT
banco_update(Array(...))                // UPDATE
banco_delete(Array(...))                // DELETE
```

#### 🎨 Interface:
```php
gestor_componente(Array(...))           // Incluir componente
interface_toast(Array(...))             // Notificações
```

#### ⚙️ Sistema:
```php
gestor_incluir_bibliotecas()            // Carregar bibliotecas
gestor_pagina_javascript_incluir()      // Incluir JS
```

### Variáveis Globais

#### 🌐 Sistema:
```php
$_GESTOR['url-raiz']                    // URL base do sistema
$_GESTOR['usuario-id']                  // ID usuário logado
$_GESTOR['modulo-id']                   // ID módulo atual
```

#### 📄 Página:
```php
$_GESTOR['pagina']                      // Conteúdo da página
$_GESTOR['layout']                      // Layout atual
```

### Estruturas de Dados

#### 📊 Arrays de Configuração:
```php
$_BANCO = [...]                         // Configurações banco
$_CONFIG = [...]                        // Configurações sistema
$_GESTOR = [...]                        // Variáveis globais
```

#### 📋 Estrutura de Módulo:
```php
$_GESTOR['modulo-id'] = 'dashboard';
function dashboard_start() { ... }       // Inicialização
function dashboard_pagina_inicial() { ... } // Lógica específica
```

---

## 🎯 Conclusão da Arquitetura

O **Conn2Flow Gestor** é um sistema **extremamente bem arquiteturado** que combina:

### 🏆 Pontos Fortes:
1. **🧩 Separação clara de responsabilidades** (MVC-like)
2. **💾 Armazenamento de HTML no banco** (inovador)
3. **📦 Sistema de plugins robusto**
4. **🔐 Segurança enterprise-grade**
5. **🌐 Escalabilidade multi-tenant**
6. **⚡ Desenvolvimento facilitado**

### 🚀 Capacidades:
- **📦 Módulos independentes**
- **🧩 Componentes reutilizáveis**
- **🔄 Sistema de variáveis dinâmicas**
- **📊 Migrações automatizadas**
- **🔌 Plugins isolados**

### 📈 Resultado:
Sistema **production-ready** com foco em **manutenibilidade** e **escalabilidade**.

---

**📋 Documento mantido por**: GitHub Copilot IA  
**📅 Última atualização**: Setembro 2025  
**🏷️ Versão**: 2.0.0  

> Base de conhecimento completa do sistema Conn2Flow Gestor, atualizada com todas as descobertas da análise técnica profunda.
