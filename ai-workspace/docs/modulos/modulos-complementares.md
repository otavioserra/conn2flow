# Lote de Documentação - Módulos Complementares

## Módulo: testes

### 🎯 Propósito
O módulo **testes** fornece funcionalidades para testar e validar componentes, páginas e configurações do sistema.

### 📁 Arquivos Principais
- **`testes.php`** - Controlador de funcionalidades de teste
- **`testes.json`** - Configurações de testes disponíveis
- **`testes.js`** - Interface de execução de testes

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: Tabelas temporárias de teste
- Dados de teste e validação

### 🔧 Principais Funções
- **`testes_executar()`** - Executa bateria de testes
- **`testes_validar_configuracao()`** - Valida configurações
- **`testes_gerar_relatorio()`** - Gera relatório de testes

---

## Módulo: modulos-grupos

### 🎯 Propósito
O módulo **modulos-grupos** organiza módulos em grupos funcionais para melhor gestão e navegação.

### 📁 Arquivos Principais
- **`modulos-grupos.php`** - Controlador de agrupamento de módulos
- **`modulos-grupos.json`** - Definições de grupos
- **`modulos-grupos.js`** - Interface de organização

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: `modulos_grupos`
- Agrupamento e categorização de módulos

### 🔧 Principais Funções
- **`modulos_grupos_criar()`** - Cria novo grupo de módulos
- **`modulos_grupos_organizar()`** - Organiza módulos em grupos
- **`modulos_grupos_exibir()`** - Exibe grupos na interface

---

## Módulo: modulos-operacoes

### 🎯 Propósito
O módulo **modulos-operacoes** gerencia operações globais que podem ser executadas em múltiplos módulos.

### 📁 Arquivos Principais
- **`modulos-operacoes.php`** - Controlador de operações globais
- **`modulos-operacoes.json`** - Configurações de operações
- **`modulos-operacoes.js`** - Interface de execução

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: Log de operações
- Histórico de operações executadas

### 🔧 Principais Funções
- **`modulos_operacoes_executar()`** - Executa operação em lote
- **`modulos_operacoes_agendar()`** - Agenda operações
- **`modulos_operacoes_monitorar()`** - Monitora execução

---

## Módulo: usuarios-gestores-perfis

### 🎯 Propósito
O módulo **usuarios-gestores-perfis** define perfis e permissões específicas para usuários gestores do sistema.

### 📁 Arquivos Principais
- **`usuarios-gestores-perfis.php`** - Controlador de perfis de gestores
- **`usuarios-gestores-perfis.json`** - Definições de perfis
- **`usuarios-gestores-perfis.js`** - Interface de gestão

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `usuarios_gestores_perfis`
- Perfis e permissões de gestores

### 🔧 Principais Funções
- **`gestores_perfis_criar()`** - Cria novo perfil de gestor
- **`gestores_perfis_definir_permissoes()`** - Define permissões do perfil
- **`gestores_perfis_aplicar()`** - Aplica perfil a usuário

---

## Módulo: usuarios-hospedeiro-perfis

### 🎯 Propósito
O módulo **usuarios-hospedeiro-perfis** gerencia perfis específicos para usuários hospedeiros/proprietários de hosts.

### 📁 Arquivos Principais
- **`usuarios-hospedeiro-perfis.php`** - Controlador de perfis de hospedeiros
- **`usuarios-hospedeiro-perfis.json`** - Configurações de perfis
- **`usuarios-hospedeiro-perfis.js`** - Interface específica

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `usuarios_hospedeiro_perfis`
- Perfis de proprietários de hosts

### 🔧 Principais Funções
- **`hospedeiro_perfis_criar()`** - Cria perfil de hospedeiro
- **`hospedeiro_perfis_configurar_limites()`** - Configura limites de recursos
- **`hospedeiro_perfis_monitorar()`** - Monitora uso conforme perfil

---

## Módulo: usuarios-hospedeiro-perfis-admin

### 🎯 Propósito
O módulo **usuarios-hospedeiro-perfis-admin** oferece gestão administrativa dos perfis de hospedeiros.

### 📁 Arquivos Principais
- **`usuarios-hospedeiro-perfis-admin.php`** - Controlador administrativo
- **`usuarios-hospedeiro-perfis-admin.json`** - Configurações administrativas
- **`usuarios-hospedeiro-perfis-admin.js`** - Interface administrativa

### 🗄️ Tabela do Banco de Dados
**Tabela relacionada**: `usuarios_hospedeiro_perfis`
- Gestão administrativa de perfis

### 🔧 Principais Funções
- **`admin_hospedeiro_perfis_gerenciar()`** - Gerencia perfis administrativamente
- **`admin_hospedeiro_perfis_auditar()`** - Auditoria de perfis
- **`admin_hospedeiro_perfis_relatorio()`** - Relatórios de uso

---

## Módulo: usuarios-perfis

### 🎯 Propósito
O módulo **usuarios-perfis** gerencia perfis gerais de usuários do sistema com diferentes níveis de acesso.

### 📁 Arquivos Principais
- **`usuarios-perfis.php`** - Controlador de perfis de usuários
- **`usuarios-perfis.json`** - Definições de perfis padrão
- **`usuarios-perfis.js`** - Interface de gestão

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `usuarios_perfis`
- Perfis gerais de usuários

### 🔧 Principais Funções
- **`usuarios_perfis_criar()`** - Cria novo perfil de usuário
- **`usuarios_perfis_associar()`** - Associa perfil a usuário
- **`usuarios_perfis_validar_acesso()`** - Valida acesso baseado no perfil

---

## Módulo: usuarios-planos

### 🎯 Propósito
O módulo **usuarios-planos** gerencia planos de assinatura e recursos disponíveis para diferentes tipos de usuários.

### 📁 Arquivos Principais
- **`usuarios-planos.php`** - Controlador de planos de usuários
- **`usuarios-planos.json`** - Definições de planos
- **`usuarios-planos.js`** - Interface de gestão

### 🗄️ Tabela do Banco de Dados
**Tabela principal**: `usuarios_planos`
- Planos e recursos disponíveis

### 🔧 Principais Funções
- **`usuarios_planos_criar()`** - Cria novo plano
- **`usuarios_planos_contratar()`** - Contrata plano para usuário
- **`usuarios_planos_monitorar_uso()`** - Monitora uso de recursos do plano
