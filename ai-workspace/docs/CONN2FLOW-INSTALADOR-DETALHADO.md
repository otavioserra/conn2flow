# Conn2Flow - Sistema de Instalação (Gestor-Instalador)

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Arquitetura do Instalador](#arquitetura-do-instalador)
- [Processo de Instalação Completo](#processo-de-instalação-completo)
- [Classe Installer.php](#classe-installerphp)
- [Sistema de Auto-Login](#sistema-de-auto-login)
- [Configuração de Ambiente](#configuração-de-ambiente)
- [Sistema de Páginas de Sucesso](#sistema-de-páginas-de-sucesso)
- [Logging e Debugging](#logging-e-debugging)
- [Troubleshooting](#troubleshooting)
- [Histórico de Desenvolvimento](#histórico-de-desenvolvimento)

---

## 🎯 Visão Geral

### O que é o Gestor-Instalador
O **Gestor-Instalador** é um sistema automatizado de instalação do Conn2Flow que:
- **Verifica ambiente** do servidor (PHP, extensões, permissões)
- **Configura banco de dados** automaticamente
- **Executa migrations e seeds** para estrutura inicial
- **Configura autenticação** com chaves SSL
- **Cria usuário administrador** com auto-login
- **Gera página de sucesso** com remoção automática

### Localização e Estrutura
```
gestor-instalador/
├── index.php              # Ponto de entrada da instalação
├── installer.log          # Log da instalação atual
├── teste-seguranca.txt    # Arquivo de teste de segurança
├── src/
│   └── Installer.php      # ❤️ Classe principal do instalador
├── assets/                # CSS, JS, imagens do instalador
├── lang/                  # Arquivos de idioma
├── public-access/         # Arquivos públicos
└── views/                 # Templates de interface
```

---

## 🏗️ Arquitetura do Instalador

### Padrão de Design
- **Classe Única**: `Installer.php` centraliza toda lógica
- **Logging Integrado**: Sistema próprio de logs com níveis
- **Tratamento de Erros**: Try/catch em todas operações críticas
- **Interface Web**: Templates HTML para feedback visual
- **Validação Robusta**: Verificações em cada etapa

### Dependências e Requisitos
#### Requisitos de Sistema
- **PHP**: 7.4+ (verificado automaticamente)
- **Extensões PHP**: MySQLi/PDO, OpenSSL, JSON, mbstring
- **Permissões**: Escrita em diretórios específicos
- **Banco MySQL**: Conexão e permissões de criação

#### Dependências do Conn2Flow
- **Gestor Core**: Estrutura principal em `/gestor`
- **Migrations**: Sistema Phinx para estrutura DB
- **Seeds**: Dados iniciais (usuários, layouts, páginas)
- **Bibliotecas**: Sistema de autenticação e banco

---

## 🔄 Processo de Instalação Completo

### Fluxo de 8 Etapas

#### 1. **Verificação de Ambiente** (`checkSystemRequirements()`)
```php
- Versão PHP >= 7.4
- Extensões: mysqli, pdo, openssl, json, mbstring
- Permissões de escrita em diretórios críticos
- Verificação de segurança básica
```

#### 2. **Configuração de Banco** (`setupDatabase()`)
```php
- Teste de conexão com credenciais fornecidas
- Criação de banco se não existir
- Verificação de permissões de usuário
- Configuração de charset UTF-8
```

#### 3. **Extração de Arquivos** (`extract_files()`)
```php
- Descompactação do gestor.zip
- Criação da estrutura de diretórios
- Configuração inicial dos arquivos
- Criação do arquivo .env com configurações
```

#### 4. **Execução de Migrations** (`runMigrations()`)
```php
- Carregamento do Phinx
- Execução sequencial de migrations
- Criação de estrutura de tabelas
- Índices e relacionamentos
```

#### 5. **Execução de Seeds** (`runSeeds()`)
```php
- Dados iniciais obrigatórios
- Layouts padrão (ID 1, 23, etc.)
- Módulos do sistema
- Páginas básicas
- Configurações padrão
```

#### 6. **Auto-Login e Configuração** (`createAdminAutoLogin()`) ⚠️ **ORDEM CORRIGIDA**
```php
- ✅ Executado APÓS criação do .env e usuários
- Geração de token JWT
- Definição de cookie persistente
- Configuração de autenticação automática
```

#### 7. **Geração de Chaves SSL** (`generateSSLKeys()`)
```php
- Criação de chaves privada/pública
- Proteção por senha via OPENSSL_PASSWORD
- Configuração para JWT
- Armazenamento seguro
```

#### 8. **Página de Sucesso e Limpeza** (`createSuccessPage()`, `cleanupInstaller()`)
```php
- Criação de página informativa
- Configuração de remoção automática
- Limpeza de arquivos temporários
- Redirecionamento final
```

---

## 🔧 Classe Installer.php

### Propriedades Principais
```php
class Installer {
    private $data = [];           // Dados de configuração da instalação
    private $logFile;            // Arquivo de log atual
    private $gestorPath;         // Caminho para o gestor
    private $config = [];        // Configurações carregadas
    
    // Métodos públicos
    public function install($data)              // Processo principal
    public function getInstallationStatus()    // Status atual
    public function getLogContents()          // Conteúdo dos logs
}
```

### Métodos Críticos

#### `install($data)` - Método Principal
```php
public function install($data) {
    $this->data = $data;
    $this->log("Iniciando instalação do Conn2Flow v1.8.4+");
    
    try {
        $this->checkSystemRequirements();
        $this->setupDatabase();
        $this->runMigrations();
        $this->runSeeds();
        $this->setupGestorEnvironment();
        $this->generateSSLKeys();
        $this->createAdminUser();
        $this->createAdminAutoLogin();
        $this->createSuccessPage();
        $this->cleanupInstallerFiles();
        
        return ['success' => true, 'message' => 'Instalação concluída!'];
    } catch (Exception $e) {
        $this->log("ERRO: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

#### `setupGestorEnvironment()` - Configuração Nativa
```php
private function setupGestorEnvironment() {
    // Usa config.php nativo do sistema ao invés de criar $_CONFIG manual
    require_once($this->gestorPath . '/config.php');
    
    // Configurações específicas da instalação
    $_CONFIG['url-raiz'] = $this->data['url_raiz'];
    $_CONFIG['banco']['host'] = $this->data['db_host'];
    $_CONFIG['banco']['nome'] = $this->data['db_name'];
    // ... outras configurações
}
```

#### `generateSSLKeys($senha)` - Chaves com Proteção
```php
private function generateSSLKeys($senha) {
    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    
    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privateKey, $senha); // ⚠️ SENHA OBRIGATÓRIA
    
    $publicKey = openssl_pkey_get_details($res);
    $publicKey = $publicKey["key"];
    
    // Armazena as chaves com proteção
}
```

---

## 🔐 Sistema de Auto-Login

### Implementação Completa

#### `createAdminAutoLogin()` - Geração de Token
```php
private function createAdminAutoLogin() {
    // 1. Carrega bibliotecas de autenticação
    require_once($this->gestorPath . '/bibliotecas/autenticacao.php');
    
    // 2. Prepara dados do usuário
    $userData = [
        'id_usuarios' => 1,
        'email' => $this->data['admin_email'],
        'nome' => $this->data['admin_nome'],
        'permanecer_logado' => true
    ];
    
    // 3. Gera token JWT usando função nativa do sistema
    $token = usuario_gerar_token_autorizacao($userData);
    
    // 4. Define cookie persistente (30 dias)
    setcookie('auth_token', $token, time() + (30 * 24 * 60 * 60), '/');
    
    $this->log("Auto-login configurado com sucesso");
}
```

### Processo de Autenticação
1. **Token JWT gerado** usando `usuario_gerar_token_autorizacao()`
2. **Cookie persistente** definido por 30 dias
3. **Redirecionamento automático** para dashboard após instalação
4. **Validação nativa** pelo sistema de autenticação do gestor

---

## ⚙️ Configuração de Ambiente

### Sistema de Configuração Nativo

#### Antes (Problemático)
```php
// ❌ ERRADO - Criação manual de $_CONFIG
$_CONFIG = array();
$_CONFIG['banco']['host'] = $host;
$_CONFIG['banco']['nome'] = $database;
// Valores hardcoded, não integrado ao sistema
```

#### Depois (Correto)
```php
// ✅ CORRETO - Usa config.php nativo
require_once($this->gestorPath . '/config.php');
// Sistema carrega automaticamente as configurações
// Integrado com .env e sistema nativo
```

### Variáveis de Ambiente (.env)
```env
# Configurações geradas automaticamente
DB_HOST=localhost
DB_NAME=conn2flow
DB_USER=root
DB_PASS=senha_banco

# Segurança
OPENSSL_PASSWORD=senha_chaves_ssl
JWT_SECRET=chave_jwt_gerada

# Sistema
APP_ENV=production
DEBUG=false
URL_RAIZ=https://exemplo.com/

# Paths
GESTOR_PATH=/caminho/completo/gestor
```

---

## 📄 Sistema de Páginas de Sucesso

### Implementação Completa

#### `createSuccessPage()` - Criação da Página
```php
private function createSuccessPage() {
    // ⚠️ IMPORTANTE: Usa tabela 'paginas' (não 'hosts_paginas')
    // ⚠️ IMPORTANTE: Usa layout ID 23 (externo, sem menu admin)
    
    $insertQuery = "
        INSERT INTO paginas (
            id_usuarios, id_layouts, nome, id, caminho, tipo, 
            html, css, status, versao, data_criacao, data_modificacao
        ) VALUES (
            1, 23, 'Instalação Concluída', 'instalacao-sucesso', 'instalacao-sucesso', 'pagina',
            :html, :css, 'A', 1, NOW(), NOW()
        )";
    
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([
        'html' => $this->getSuccessPageHtml(),
        'css' => $this->getSuccessPageCss()
    ]);
}
```

#### `getSuccessPageHtml()` - Conteúdo da Página
```html
<div class="ui main container">
    <div class="ui centered grid">
        <div class="twelve wide column">
            <!-- Mensagem de Sucesso -->
            <div class="ui positive message">
                <div class="header">
                    <i class="checkmark icon"></i>
                    Instalação Concluída com Sucesso!
                </div>
                <p>O Conn2Flow foi instalado e configurado com sucesso em seu servidor.</p>
            </div>
            
            <!-- Próximos Passos -->
            <div class="ui segment">
                <div class="ui header">Próximos Passos</div>
                <div class="ui ordered steps">
                    <div class="step">Acesse o Painel</div>
                    <div class="step">Configure o Sistema</div>
                    <div class="step">Personalize o Design</div>
                    <div class="step">Comece a Usar</div>
                </div>
            </div>
            
            <!-- Botão de Acesso -->
            <div class="ui center aligned segment">
                <a href="@[[pagina#url-raiz]]@dashboard" class="ui huge primary button">
                    <i class="sign in icon"></i>
                    Acessar Painel Administrativo
                </a>
            </div>
            
            <!-- Nota Final -->
            <div class="ui info message">
                <div class="header">Nota</div>
                <p>Esta página será removida automaticamente quando você acessar o painel administrativo pela primeira vez.</p>
            </div>
        </div>
    </div>
</div>
```

### Remoção Automática
- **Implementada em**: `gestor/modulos/dashboard/dashboard.php`
- **Função**: `dashboard_remover_pagina_instalacao_sucesso()`
- **Momento**: Primeiro acesso ao dashboard após instalação
- **Feedback**: Toast informativo para o usuário

---

## 📝 Logging e Debugging

### Sistema de Logs Integrado

#### `log($message, $level)` - Função Principal
```php
private function log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Grava em arquivo
    file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Pode também exibir em tempo real para interface web
    echo $logEntry;
    flush();
}
```

#### Níveis de Log
- **INFO**: Informações gerais do processo
- **WARNING**: Avisos não críticos
- **ERROR**: Erros que impedem continuação
- **DEBUG**: Informações detalhadas para desenvolvimento

#### Exemplos de Uso
```php
$this->log("Iniciando verificação de sistema");
$this->log("Extensão OpenSSL não encontrada", 'WARNING');
$this->log("Falha na conexão com banco: " . $e->getMessage(), 'ERROR');
$this->log("Query executada: " . $sql, 'DEBUG');
```

---

## 🚨 Troubleshooting

### Problemas Comuns e Soluções

#### 1. Erro de Conexão com Banco
**Sintomas**:
- "Connection refused" ou "Access denied"
- Instalação para na etapa 2

**Soluções**:
```php
// Verificar credenciais
$this->data['db_host'] = 'localhost'; // ou IP correto
$this->data['db_user'] = 'root';      // usuário com permissões
$this->data['db_pass'] = 'senha';     // senha correta

// Verificar permissões MySQL
GRANT ALL PRIVILEGES ON *.* TO 'usuario'@'localhost';
FLUSH PRIVILEGES;
```

#### 2. Erro "Configuration file (.env) not found" ⚠️ **PROBLEMA RESOLVIDO**
**Sintomas**:
- Erro 503 com mensagem sobre .env não encontrado
- Falha durante auto-login

**Causa**:
- Auto-login sendo executado antes da criação completa do arquivo .env
- Ordem incorreta de execução no instalador

**Solução Implementada**:
```php
// ✅ ORDEM CORRETA (corrigida em Julho 2025)
1. extract_files() → Cria estrutura e .env
2. run_migrations() → Executa migrations
3. runSeeds() → Cria usuários no banco
4. createAdminAutoLogin() → AGORA pode usar .env + usuários existentes
```

#### 3. Extensões PHP Faltando
**Sintomas**:
- "Extension not found" nos logs
- Falha na verificação de sistema

**Soluções**:
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli php-pdo php-openssl php-json php-mbstring

# CentOS/RHEL
sudo yum install php-mysqli php-pdo php-openssl php-json php-mbstring
```

#### 3. Problemas com Permissões
**Sintomas**:
- "Permission denied" ao criar arquivos
- Falha na geração de chaves SSL

**Soluções**:
```bash
# Definir permissões corretas
chmod 755 gestor/
chmod 644 gestor/config.php
chmod 600 gestor/autenticacoes/*/chaves/*
```

#### 4. Layout Incorreto na Página de Sucesso
**Sintomas**:
- Página de sucesso com menu administrativo
- Layout inconsistente

**Solução**:
```php
// Verificar se está usando layout ID 23 (externo)
$layoutId = 23; // Layout sem menu administrativo
```

#### 5. Auto-Login Não Funciona
**Sintomas**:
- Redirecionamento para login após instalação
- Cookie não definido

**Verificações**:
```php
// Verificar se função existe
if (function_exists('usuario_gerar_token_autorizacao')) {
    // Verificar se bibliotecas foram carregadas
    require_once($this->gestorPath . '/bibliotecas/autenticacao.php');
    
    // Verificar se cookie está sendo definido
    setcookie('auth_token', $token, time() + (30 * 24 * 60 * 60), '/');
}
```

---

## 📚 Histórico de Desenvolvimento

### Versão 1.0 (Base Original)
- **Funcionalidades**: Instalação básica com migrations e seeds
- **Configuração**: Manual via formulário web
- **Autenticação**: Login manual após instalação
- **Problemas**: Valores hardcoded, sem integração com .env

### Versão 1.1 (Auto-Login)
**Data**: Julho 2025
**Melhorias**:
- ✅ Implementação de auto-login com JWT
- ✅ Integração com `usuario_gerar_token_autorizacao()`
- ✅ Cookie persistente por 30 dias
- ✅ Redirecionamento automático para dashboard

### Versão 1.2 (Configuração Nativa)
**Data**: Julho 2025
**Melhorias**:
- ✅ Uso do `config.php` nativo do sistema
- ✅ Remoção de valores hardcoded
- ✅ Integração com sistema .env existente
- ✅ Senha SSL via `OPENSSL_PASSWORD`

### Versão 1.3 (Página de Sucesso)
**Data**: Julho 2025
**Melhorias**:
- ✅ Criação de página informativa pós-instalação
- ✅ Layout ID 23 (externo, sem menu administrativo)
- ✅ Remoção automática no primeiro acesso ao dashboard
- ✅ Toast informativo para usuário
- ✅ Interface responsiva com Semantic UI

### Versão 1.4 (Correção de Ordem de Execução)
**Data**: Julho 2025
**Problema**: Erro 503 - "Configuration file (.env) not found for domain: localhost"
**Causa**: Auto-login executando antes do arquivo .env estar completamente configurado
**Melhorias**:
- ✅ Correção da ordem de execução no instalador
- ✅ Auto-login movido para após criação do .env E inserção de usuários
- ✅ Ordem correta: extract_files → run_migrations → seeds → auto-login
- ✅ Documentação atualizada com troubleshooting específico
- ✅ Prevenção de erros de configuração não encontrada

### Melhorias Futuras Planejadas
- **Instalação Multi-Idioma**: Suporte a português, inglês, espanhol
- **Backup Automático**: Backup antes de modificações críticas
- **Rollback**: Capacidade de desfazer instalação
- **Verificação de Integridade**: Hash check dos arquivos core
- **Instalação Silenciosa**: Via linha de comando
- **Progress Bar**: Indicador visual mais detalhado

---

## 🔧 Configurações Avançadas

### Personalização da Instalação

#### Configurações Opcionais
```php
// Configurações que podem ser personalizadas
$customConfig = [
    'auto_login_duration' => 30,        // dias de cookie persistente
    'success_page_layout' => 23,        // ID do layout para página sucesso
    'ssl_key_bits' => 4096,            // tamanho da chave SSL
    'log_level' => 'INFO',             // nível de logging
    'cleanup_installer' => true,        // limpar arquivos do instalador
];
```

#### Hooks Disponíveis
```php
// Pontos onde é possível adicionar código customizado
private function beforeInstall() { /* Hook antes da instalação */ }
private function afterDatabase() { /* Hook após configuração DB */ }
private function afterMigrations() { /* Hook após migrations */ }
private function afterSeeds() { /* Hook após seeds */ }
private function beforeCleanup() { /* Hook antes da limpeza */ }
private function afterInstall() { /* Hook após instalação completa */ }
```

### Integração com Sistemas Externos

#### cPanel/WHM
```php
// Integração automática com cPanel se disponível
if ($this->isCpanelEnvironment()) {
    $this->configureCpanelIntegration();
    $this->createCpanelAccount();
}
```

#### Docker
```php
// Configurações específicas para ambiente Docker
if ($this->isDockerEnvironment()) {
    $this->configureDockerPaths();
    $this->setDockerPermissions();
}
```

---

**Documento técnico completo do Gestor-Instalador**
**Versão**: 1.3.0
**Última atualização**: Julho 2025
**Mantido por**: GitHub Copilot IA

> Este documento contém todos os detalhes técnicos, implementações e histórico do sistema de instalação do Conn2Flow. Para referência rápida, consulte o documento principal de conhecimento.
