# Lote de Documentação - Módulos Core 3

## Módulo: modulos

### 🎯 Propósito
O módulo **modulos** gerencia os próprios módulos do sistema, permitindo ativar, desativar e configurar módulos instalados no Conn2Flow.

### 📁 Arquivos Principais
- **`modulos.php`** - Controlador principal com gestão de módulos
- **`modulos.json`** - Configurações e metadados dos módulos
- **`modulos.js`** - Interface para ativação/desativação de módulos

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_modulos` (inferida)
- Campos relacionados ao status e configuração dos módulos por host

### 🔧 Principais Funções
- **`modulos_listar()`** - Lista módulos disponíveis no sistema
- **`modulos_ativar()`** - Ativa um módulo para um host específico
- **`modulos_desativar()`** - Desativa módulo mantendo configurações
- **`modulos_configurar()`** - Gerencia configurações específicas de módulos

---

## Módulo: host-configuracao

### 🎯 Propósito
O módulo **host-configuracao** gerencia as configurações específicas de cada host/tenant no sistema multi-tenant.

### 📁 Arquivos Principais
- **`host-configuracao.php`** - Controlador principal de configurações de host
- **`host-configuracao.json`** - Configurações e opções disponíveis
- **`host-configuracao.js`** - Interface de configuração

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_configuracoes`
- Configurações específicas por host e módulo

### 🔧 Principais Funções
- **`host_configuracao_salvar()`** - Salva configurações do host
- **`host_configuracao_carregar()`** - Carrega configurações existentes
- **`host_configuracao_validar()`** - Valida configurações antes de salvar

---

## Módulo: templates

### 🎯 Propósito
O módulo **templates** gerencia templates de sistema, permitindo instalar, configurar e personalizar templates padrão.

### 📁 Arquivos Principais
- **`templates.php`** - Controlador principal de templates
- **`templates.json`** - Catálogo de templates disponíveis
- **`templates.js`** - Interface para instalação e configuração

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `hosts_templates` (inferida)
- Informações sobre templates instalados por host

### 🔧 Principais Funções
- **`templates_instalar()`** - Instala template selecionado
- **`templates_personalizar()`** - Personaliza template existente
- **`templates_exportar()`** - Exporta template personalizado

---

## Módulo: gateways-de-pagamentos

### 🎯 Propósito
O módulo **gateways-de-pagamentos** integra diferentes gateways de pagamento como PayPal, Stripe, PagSeguro para processamento de transações.

### 📁 Arquivos Principais
- **`gateways-de-pagamentos.php`** - Controlador principal de pagamentos
- **`gateways-de-pagamentos.json`** - Configurações de gateways
- **`gateways-de-pagamentos.js`** - Interface de configuração

### 🗄️ Tabela do Banco de Dados
**Tabelas relacionadas**: `hosts_paypal`, `hosts_pagamentos` (conforme migrações)
- Configurações e histórico de transações

### 🔧 Principais Funções
- **`gateway_processar_pagamento()`** - Processa pagamento via gateway
- **`gateway_configurar()`** - Configura credenciais do gateway
- **`gateway_validar_transacao()`** - Valida transação recebida

---

## Módulo: servicos

### 🎯 Propósito
O módulo **servicos** gerencia catálogo de serviços oferecidos, incluindo descrições, preços e variações.

### 📁 Arquivos Principais
- **`servicos.php`** - Controlador principal de serviços
- **`servicos.json`** - Configurações de tipos de serviço
- **`servicos.js`** - Interface de gestão de serviços

### 🗄️ Tabela do Banco de Dados
**Tabelas relacionadas**: `hosts_servicos`, `hosts_servico_variacoes`
- Catálogo de serviços e suas variações

### 🔧 Principais Funções
- **`servicos_criar()`** - Cria novo serviço
- **`servicos_editar()`** - Edita serviço existente
- **`servicos_gerenciar_variacoes()`** - Gerencia variações de serviços

---

## Módulo: pedidos

### 🎯 Propósito
O módulo **pedidos** gerencia o processo de pedidos, desde a criação até a conclusão, incluindo status e histórico.

### 📁 Arquivos Principais
- **`pedidos.php`** - Controlador principal de pedidos
- **`pedidos.json`** - Configurações de status e workflow
- **`pedidos.js`** - Interface de gestão de pedidos

### 🗄️ Tabela do Banco de Dados
**Tabelas relacionadas**: `hosts_pedidos`, `hosts_pedidos_servicos`
- Pedidos e itens de pedidos

### 🔧 Principais Funções
- **`pedidos_criar()`** - Cria novo pedido
- **`pedidos_atualizar_status()`** - Atualiza status do pedido
- **`pedidos_calcular_total()`** - Calcula valor total do pedido
