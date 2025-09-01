# Lote de Documentação - Módulos Funcionais

## Módulo: interface-hosts

### 🎯 Propósito
O módulo **interface-hosts** gerencia a interface específica para administração de hosts no sistema multi-tenant.

### 📁 Arquivos Principais
- **`interface-hosts.php`** - Controlador de interface de hosts
- **`interface-hosts.json`** - Configurações de interface
- **`interface-hosts.js`** - Funcionalidades específicas da interface

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: `hosts` e tabelas de configuração
- Interface personalizada por host

### 🔧 Principais Funções
- **`interface_hosts_personalizar()`** - Personaliza interface do host
- **`interface_hosts_aplicar_tema()`** - Aplica tema específico
- **`interface_hosts_configurar_menu()`** - Configura menu do host

---

## Módulo: plugins-hosts

### 🎯 Propósito
O módulo **plugins-hosts** gerencia plugins específicos instalados em cada host individualmente.

### 📁 Arquivos Principais
- **`plugins-hosts.php`** - Controlador de plugins por host
- **`plugins-hosts.json`** - Configurações específicas
- **`plugins-hosts.js`** - Interface de gestão

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_plugins`
- Plugins ativos por host específico

### 🔧 Principais Funções
- **`plugins_hosts_ativar()`** - Ativa plugin para host específico
- **`plugins_hosts_configurar()`** - Configura plugin do host
- **`plugins_hosts_sincronizar()`** - Sincroniza configurações

---

## Módulo: host-configuracao-manual

### 🎯 Propósito
O módulo **host-configuracao-manual** permite configurações manuais avançadas que não são cobertas pela configuração automática.

### 📁 Arquivos Principais
- **`host-configuracao-manual.php`** - Controlador de configurações manuais
- **`host-configuracao-manual.json`** - Opções de configuração
- **`host-configuracao-manual.js`** - Interface de configuração avançada

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: `hosts_configuracoes`
- Configurações manuais específicas

### 🔧 Principais Funções
- **`host_config_manual_definir()`** - Define configuração manual
- **`host_config_manual_validar()`** - Valida configurações customizadas
- **`host_config_manual_aplicar()`** - Aplica configurações

---

## Módulo: pagina-inicial

### 🎯 Propósito
O módulo **pagina-inicial** gerencia especificamente a página inicial/home do site, com configurações e componentes específicos.

### 📁 Arquivos Principais
- **`pagina-inicial.php`** - Controlador da página inicial
- **`pagina-inicial.json`** - Configurações e componentes
- **`pagina-inicial.js`** - Funcionalidades específicas

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: `hosts_paginas` (tipo específico)
- Configurações da página inicial

### 🔧 Principais Funções
- **`pagina_inicial_configurar()`** - Configura layout da home
- **`pagina_inicial_componentes()`** - Gerencia componentes da home
- **`pagina_inicial_seo()`** - Otimizações SEO específicas

---

## Módulo: paginas-secundarias

### 🎯 Propósito
O módulo **paginas-secundarias** gerencia páginas auxiliares como Sobre, Contato, Política de Privacidade, etc.

### 📁 Arquivos Principais
- **`paginas-secundarias.php`** - Controlador de páginas auxiliares
- **`paginas-secundarias.json`** - Templates de páginas padrão
- **`paginas-secundarias.js`** - Interface específica

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: `hosts_paginas`
- Páginas auxiliares do site

### 🔧 Principais Funções
- **`paginas_secundarias_criar_padrao()`** - Cria páginas padrão
- **`paginas_secundarias_personalizar()`** - Personaliza conteúdo
- **`paginas_secundarias_vincular()`** - Vincula páginas ao menu

---

## Módulo: loja-configuracoes

### 🎯 Propósito
O módulo **loja-configuracoes** gerencia configurações específicas para funcionalidades de e-commerce.

### 📁 Arquivos Principais
- **`loja-configuracoes.php`** - Controlador de configurações da loja
- **`loja-configuracoes.json`** - Opções de e-commerce
- **`loja-configuracoes.js`** - Interface de configuração

### 🗄️ Tabela do Banco de Dados
**Tabelas relacionadas**: `hosts_configuracoes`, `hosts_carrinho`
- Configurações específicas de loja

### 🔧 Principais Funções
- **`loja_config_geral()`** - Configurações gerais da loja
- **`loja_config_pagamento()`** - Configura métodos de pagamento
- **`loja_config_entrega()`** - Configura opções de entrega

---

## Módulo: comunicacao-configuracoes

### 🎯 Propósito
O módulo **comunicacao-configuracoes** gerencia configurações de comunicação como SMTP, APIs de email, notificações.

### 📁 Arquivos Principais
- **`comunicacao-configuracoes.php`** - Controlador de comunicação
- **`comunicacao-configuracoes.json`** - Configurações de canais
- **`comunicacao-configuracoes.js`** - Interface de configuração

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: `hosts_configuracoes`
- Configurações de comunicação por host

### 🔧 Principais Funções
- **`comunicacao_config_smtp()`** - Configura servidor SMTP
- **`comunicacao_config_apis()`** - Configura APIs de terceiros
- **`comunicacao_testar_conexao()`** - Testa configurações
