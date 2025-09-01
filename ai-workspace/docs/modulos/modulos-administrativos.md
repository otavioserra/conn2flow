# Lote de Documentação - Módulos Administrativos

## Módulo: admin-hosts

### 🎯 Propósito
O módulo **admin-hosts** gerencia hosts/tenants no sistema multi-tenant, permitindo criar, configurar e monitorar diferentes instâncias.

### 📁 Arquivos Principais
- **`admin-hosts.php`** - Controlador principal de gestão de hosts
- **`admin-hosts.json`** - Configurações de tipos de host
- **`admin-hosts.js`** - Interface administrativa de hosts

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts`
- Informações básicas dos hosts/tenants

### 🔧 Principais Funções
- **`admin_hosts_criar()`** - Cria novo host/tenant
- **`admin_hosts_configurar()`** - Configura host existente
- **`admin_hosts_monitorar()`** - Monitora status e uso do host

---

## Módulo: admin-plugins

### 🎯 Propósito
O módulo **admin-plugins** gerencia plugins instalados no sistema, permitindo ativar, desativar e configurar extensões.

### 📁 Arquivos Principais
- **`admin-plugins.php`** - Controlador de gestão de plugins
- **`admin-plugins.json`** - Catálogo de plugins disponíveis
- **`admin-plugins.js`** - Interface de configuração

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_plugins`
- Status e configurações de plugins por host

### 🔧 Principais Funções
- **`admin_plugins_instalar()`** - Instala plugin selecionado
- **`admin_plugins_ativar()`** - Ativa plugin para host
- **`admin_plugins_configurar()`** - Configura parâmetros do plugin

---

## Módulo: admin-templates

### 🎯 Propósito
O módulo **admin-templates** oferece gestão administrativa de templates, incluindo instalação, customização e distribuição.

### 📁 Arquivos Principais
- **`admin-templates.php`** - Controlador administrativo de templates
- **`admin-templates.json`** - Configurações de templates
- **`admin-templates.js`** - Interface administrativa

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `templates` (global)
- Templates disponíveis no sistema

### 🔧 Principais Funções
- **`admin_templates_importar()`** - Importa novos templates
- **`admin_templates_distribuir()`** - Distribui template para hosts
- **`admin_templates_versionar()`** - Controla versões de templates

---

## Módulo: usuarios-gestores

### 🎯 Propósito
O módulo **usuarios-gestores** gerencia usuários com privilégios administrativos no sistema gestor.

### 📁 Arquivos Principais
- **`usuarios-gestores.php`** - Controlador de usuários gestores
- **`usuarios-gestores.json`** - Configurações de perfis
- **`usuarios-gestores.js`** - Interface de gestão

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `usuarios_gestores`
- Usuários com acesso ao painel administrativo

### 🔧 Principais Funções
- **`usuarios_gestores_criar()`** - Cria novo usuário gestor
- **`usuarios_gestores_definir_permissoes()`** - Define permissões específicas
- **`usuarios_gestores_autenticar()`** - Autentica acesso administrativo

---

## Módulo: usuarios-hospedeiro

### 🎯 Propósito
O módulo **usuarios-hospedeiro** gerencia usuários proprietários de hosts/tenants no sistema.

### 📁 Arquivos Principais
- **`usuarios-hospedeiro.php`** - Controlador de usuários hospedeiro
- **`usuarios-hospedeiro.json`** - Configurações específicas
- **`usuarios-hospedeiro.js`** - Interface de gestão

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `usuarios_hospedeiro`
- Usuários proprietários de hosts

### 🔧 Principais Funções
- **`usuarios_hospedeiro_associar_host()`** - Associa usuário a host
- **`usuarios_hospedeiro_gerenciar_plano()`** - Gerencia plano contratado
- **`usuarios_hospedeiro_monitorar_uso()`** - Monitora uso de recursos

---

## Módulo: postagens

### 🎯 Propósito
O módulo **postagens** gerencia sistema de blog/notícias, permitindo criar, editar e publicar conteúdo temporal.

### 📁 Arquivos Principais
- **`postagens.php`** - Controlador principal de postagens
- **`postagens.json`** - Configurações de tipos de post
- **`postagens.js`** - Interface de edição

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_postagens`
- Postagens e artigos por host

### 🔧 Principais Funções
- **`postagens_criar()`** - Cria nova postagem
- **`postagens_publicar()`** - Publica postagem com agendamento
- **`postagens_categorizar()`** - Organiza postagens por categoria
