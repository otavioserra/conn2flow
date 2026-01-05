# Biblioteca: ftp.php

> 📁 Operações FTP para transferência de arquivos

## Visão Geral

A biblioteca `ftp.php` fornece funções para operações FTP (File Transfer Protocol), permitindo conexão, upload e download de arquivos de/para servidores FTP remotos.

**Localização**: `gestor/bibliotecas/ftp.php`  
**Versão**: 1.0.0  
**Total de Funções**: 4

## Dependências

- **Extensão PHP**: FTP (nativa)
- **Variáveis Globais**: `$_GESTOR`

## Variáveis Globais

```php
$_GESTOR['biblioteca-ftp'] = Array(
    'versao' => '1.0.0',
);

// Estado da conexão
$_GESTOR['ftp-conexao'] // Resource da conexão FTP ativa
$_GESTOR['ftp-erro']    // Mensagem de erro da última operação
$_GESTOR['ftp-conexao-nao-passiva'] // Se definido, desativa modo passivo
```

---

## Funções Principais

### ftp_conectar()

Estabelece conexão com servidor FTP.

**Assinatura:**
```php
function ftp_conectar($params = false)
```

**Parâmetros (Array Associativo):**
- `usuario` (string) - **Obrigatório** - Usuário da conta FTP
- `senha` (string) - **Obrigatório** - Senha da conta FTP
- `host` (string) - **Obrigatório** - Host do servidor FTP
- `secure` (bool) - **Opcional** - Se true, usa conexão SSL (ftp_ssl_connect)

**Retorno:**
- (bool) - true se conectado com sucesso, false em caso de erro

**Exemplo de Uso:**
```php
// Conexão FTP básica
$conectado = ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'meu_usuario',
    'senha' => 'minha_senha'
));

if ($conectado) {
    echo "Conectado com sucesso!";
} else {
    global $_GESTOR;
    echo "Erro: " . $_GESTOR['ftp-erro'];
}

// Conexão FTP com SSL
$conectado = ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'meu_usuario',
    'senha' => 'minha_senha',
    'secure' => true
));
```

**Comportamento:**
- Tenta `ftp_ssl_connect()` se `secure=true` e função existir
- Caso contrário, usa `ftp_connect()` padrão
- Ativa modo passivo automaticamente (a menos que `$_GESTOR['ftp-conexao-nao-passiva']` esteja definido)
- Armazena conexão em `$_GESTOR['ftp-conexao']`
- Armazena erros em `$_GESTOR['ftp-erro']`

**Notas:**
- Modo passivo é ativado por padrão para melhor compatibilidade com firewalls
- Conexão SSL requer extensão OpenSSL no PHP

---

### ftp_fechar_conexao()

Fecha a conexão FTP ativa.

**Assinatura:**
```php
function ftp_fechar_conexao($params = false)
```

**Parâmetros:**
- Nenhum (array vazio ou false)

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Conectar
ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'usuario',
    'senha' => 'senha'
));

// Fazer operações...

// Fechar conexão
ftp_fechar_conexao();
```

**Notas:**
- Limpa `$_GESTOR['ftp-conexao']`
- Seguro chamar mesmo se não houver conexão ativa

---

### ftp_colocar_arquivo()

Envia (upload) arquivo local para servidor FTP.

**Assinatura:**
```php
function ftp_colocar_arquivo($params = false)
```

**Parâmetros (Array Associativo):**
- `local` (string) - **Obrigatório** - Caminho do arquivo local
- `remoto` (string) - **Obrigatório** - Caminho de destino no servidor FTP
- `modoFTP` (const) - **Opcional** - FTP_ASCII ou FTP_BINARY (padrão: FTP_BINARY)

**Retorno:**
- (bool) - true se upload bem-sucedido, false caso contrário

**Exemplo de Uso:**
```php
// Conectar
ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'usuario',
    'senha' => 'senha'
));

// Upload de imagem (binário)
$sucesso = ftp_colocar_arquivo(Array(
    'local' => '/var/www/uploads/foto.jpg',
    'remoto' => '/public_html/images/foto.jpg'
));

if ($sucesso) {
    echo "Upload concluído!";
}

// Upload de arquivo texto (ASCII)
$sucesso = ftp_colocar_arquivo(Array(
    'local' => '/var/www/data/config.txt',
    'remoto' => '/config/config.txt',
    'modoFTP' => FTP_ASCII
));

// Fechar conexão
ftp_fechar_conexao();
```

**Modos FTP:**
- `FTP_BINARY` (padrão): Para arquivos binários (imagens, PDFs, executáveis)
- `FTP_ASCII`: Para arquivos de texto (converte line endings)

**Notas:**
- Requer conexão ativa via `ftp_conectar()`
- Retorna false se conexão não estiver ativa

---

### ftp_pegar_arquivo()

Baixa (download) arquivo do servidor FTP.

**Assinatura:**
```php
function ftp_pegar_arquivo($params = false)
```

**Parâmetros (Array Associativo):**
- `remoto` (string) - **Obrigatório** - Caminho do arquivo no servidor FTP
- `local` (string) - **Obrigatório** - Caminho de destino local
- `modoFTP` (const) - **Opcional** - FTP_ASCII ou FTP_BINARY (padrão: FTP_BINARY)

**Retorno:**
- (bool) - true se download bem-sucedido, false caso contrário

**Exemplo de Uso:**
```php
// Conectar
ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'usuario',
    'senha' => 'senha'
));

// Download de backup
$sucesso = ftp_pegar_arquivo(Array(
    'remoto' => '/backups/database.sql.gz',
    'local' => '/var/backups/database.sql.gz'
));

if ($sucesso) {
    echo "Backup baixado com sucesso!";
}

// Download de log (ASCII)
$sucesso = ftp_pegar_arquivo(Array(
    'remoto' => '/logs/access.log',
    'local' => '/tmp/access.log',
    'modoFTP' => FTP_ASCII
));

// Fechar
ftp_fechar_conexao();
```

**Notas:**
- Requer conexão ativa via `ftp_conectar()`
- Sobrescreve arquivo local se já existir

---

## Casos de Uso Comuns

### 1. Backup Automático para FTP

```php
function fazer_backup_ftp() {
    // Gerar arquivo de backup
    $backup_file = '/tmp/backup-' . date('Y-m-d') . '.sql.gz';
    exec("mysqldump --all-databases | gzip > $backup_file");
    
    // Conectar FTP
    $conectado = ftp_conectar(Array(
        'host' => 'backup.example.com',
        'usuario' => 'backup_user',
        'senha' => 'senha_segura'
    ));
    
    if ($conectado) {
        // Enviar backup
        $enviado = ftp_colocar_arquivo(Array(
            'local' => $backup_file,
            'remoto' => '/backups/' . basename($backup_file)
        ));
        
        // Fechar conexão
        ftp_fechar_conexao();
        
        // Limpar arquivo local
        if ($enviado) {
            unlink($backup_file);
            return true;
        }
    }
    
    return false;
}

// Executar
if (fazer_backup_ftp()) {
    echo "Backup enviado com sucesso!";
}
```

### 2. Sincronizar Arquivos

```php
function sincronizar_uploads() {
    $conectado = ftp_conectar(Array(
        'host' => 'cdn.example.com',
        'usuario' => 'cdn_user',
        'senha' => 'senha'
    ));
    
    if (!$conectado) {
        return false;
    }
    
    $uploads_dir = '/var/www/uploads/';
    $arquivos = scandir($uploads_dir);
    
    foreach ($arquivos as $arquivo) {
        if ($arquivo === '.' || $arquivo === '..') continue;
        
        $local = $uploads_dir . $arquivo;
        $remoto = '/public/' . $arquivo;
        
        if (is_file($local)) {
            ftp_colocar_arquivo(Array(
                'local' => $local,
                'remoto' => $remoto
            ));
        }
    }
    
    ftp_fechar_conexao();
    return true;
}
```

### 3. Download de Relatórios

```php
function baixar_relatorios_diarios() {
    $conectado = ftp_conectar(Array(
        'host' => 'reports.example.com',
        'usuario' => 'reports',
        'senha' => 'senha'
    ));
    
    if (!$conectado) {
        return Array();
    }
    
    $relatorios = Array(
        'vendas.csv',
        'estoque.csv',
        'clientes.csv'
    );
    
    $baixados = Array();
    
    foreach ($relatorios as $relatorio) {
        $local = '/var/reports/' . $relatorio;
        $remoto = '/daily/' . $relatorio;
        
        if (ftp_pegar_arquivo(Array(
            'remoto' => $remoto,
            'local' => $local,
            'modoFTP' => FTP_ASCII
        ))) {
            $baixados[] = $relatorio;
        }
    }
    
    ftp_fechar_conexao();
    return $baixados;
}
```

### 4. Upload com Retry

```php
function upload_ftp_com_retry($arquivo_local, $arquivo_remoto, $max_tentativas = 3) {
    for ($i = 0; $i < $max_tentativas; $i++) {
        // Conectar
        $conectado = ftp_conectar(Array(
            'host' => 'ftp.example.com',
            'usuario' => 'usuario',
            'senha' => 'senha'
        ));
        
        if (!$conectado) {
            sleep(2); // Aguardar antes de tentar novamente
            continue;
        }
        
        // Tentar upload
        $sucesso = ftp_colocar_arquivo(Array(
            'local' => $arquivo_local,
            'remoto' => $arquivo_remoto
        ));
        
        ftp_fechar_conexao();
        
        if ($sucesso) {
            return true;
        }
        
        sleep(2);
    }
    
    return false;
}

// Usar
if (upload_ftp_com_retry('/tmp/arquivo.pdf', '/docs/arquivo.pdf')) {
    echo "Upload bem-sucedido!";
} else {
    echo "Falha após várias tentativas";
}
```

### 5. Verificar e Criar Diretórios

```php
function upload_com_estrutura($arquivo, $caminho_completo) {
    global $_GESTOR;
    
    $conectado = ftp_conectar(Array(
        'host' => 'ftp.example.com',
        'usuario' => 'usuario',
        'senha' => 'senha'
    ));
    
    if (!$conectado) {
        return false;
    }
    
    // Criar diretórios se necessário
    $partes = explode('/', dirname($caminho_completo));
    $caminho = '';
    
    foreach ($partes as $parte) {
        if (empty($parte)) continue;
        
        $caminho .= '/' . $parte;
        
        // Tentar criar diretório (ignora erro se já existir)
        @ftp_mkdir($_GESTOR['ftp-conexao'], $caminho);
    }
    
    // Upload do arquivo
    $sucesso = ftp_colocar_arquivo(Array(
        'local' => $arquivo,
        'remoto' => $caminho_completo
    ));
    
    ftp_fechar_conexao();
    return $sucesso;
}
```

---

## Padrões e Melhores Práticas

### Gerenciamento de Conexão

```php
// ✅ BOM - Sempre fechar conexão
ftp_conectar($params);
// ... operações ...
ftp_fechar_conexao();

// ✅ MELHOR - Usar try/finally (PHP 5.5+)
try {
    if (ftp_conectar($params)) {
        ftp_colocar_arquivo($arquivo_params);
    }
} finally {
    ftp_fechar_conexao();
}
```

### Tratamento de Erros

```php
// ✅ Verificar conexão
$conectado = ftp_conectar($params);
if (!$conectado) {
    global $_GESTOR;
    error_log("Erro FTP: " . $_GESTOR['ftp-erro']);
    return false;
}

// ✅ Verificar operações
if (!ftp_colocar_arquivo($params)) {
    error_log("Falha no upload");
}
```

### Modo FTP Apropriado

```php
// ✅ Usar FTP_BINARY para arquivos binários
ftp_colocar_arquivo(Array(
    'local' => 'imagem.jpg',
    'remoto' => '/images/imagem.jpg',
    'modoFTP' => FTP_BINARY  // Padrão
));

// ✅ Usar FTP_ASCII para texto
ftp_colocar_arquivo(Array(
    'local' => 'config.ini',
    'remoto' => '/config.ini',
    'modoFTP' => FTP_ASCII
));
```

---

## Limitações e Considerações

### Segurança

- Credenciais FTP são enviadas em texto plano (use FTPS quando possível)
- FTP_SSL requer suporte OpenSSL
- Considere SFTP (SSH) para maior segurança

### Performance

- Modo passivo pode ser mais lento em algumas redes
- Para desativar: defina `$_GESTOR['ftp-conexao-nao-passiva'] = true` antes de conectar

### Timeout

- Conexões FTP podem expirar em transferências longas
- Configure `default_socket_timeout` no php.ini se necessário

### Firewall

- FTP ativo requer portas de entrada abertas
- FTP passivo funciona melhor com firewalls
- FTPS pode requerer configuração adicional de firewall

---

## Veja Também

- [PHP FTP Functions](https://www.php.net/manual/pt_BR/book.ftp.php) - Documentação oficial
- [BIBLIOTECA-HOST.md](./BIBLIOTECA-HOST.md) - Configuração de hosts

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
