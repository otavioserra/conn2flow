# Library: ftp.php

> 📁 FTP operations for file transfer

## Overview

The `ftp.php` library provides functions for FTP (File Transfer Protocol) operations, allowing connection, upload, and download of files to/from remote FTP servers.

**Location**: `gestor/bibliotecas/ftp.php`  
**Version**: 1.0.0  
**Total Functions**: 4

## Dependencies

- **PHP Extension**: FTP (native)
- **Global Variables**: `$_GESTOR`

## Global Variables

```php
$_GESTOR['biblioteca-ftp'] = Array(
    'versao' => '1.0.0',
);

// Connection state
$_GESTOR['ftp-conexao'] // Active FTP connection resource
$_GESTOR['ftp-erro']    // Error message of last operation
$_GESTOR['ftp-conexao-nao-passiva'] // If defined, disables passive mode
```

---

## Main Functions

### ftp_conectar()

Establishes connection to FTP server.

**Signature:**
```php
function ftp_conectar($params = false)
```

**Parameters (Associative Array):**
- `usuario` (string) - **Required** - FTP account user
- `senha` (string) - **Required** - FTP account password
- `host` (string) - **Required** - FTP server host
- `secure` (bool) - **Optional** - If true, uses SSL connection (ftp_ssl_connect)

**Return:**
- (bool) - true if connected successfully, false in case of error

**Usage Example:**
```php
// Basic FTP connection
$connected = ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'my_user',
    'senha' => 'my_password'
));

if ($connected) {
    echo "Connected successfully!";
} else {
    global $_GESTOR;
    echo "Error: " . $_GESTOR['ftp-erro'];
}

// FTP connection with SSL
$connected = ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'my_user',
    'senha' => 'my_password',
    'secure' => true
));
```

**Behavior:**
- Tries `ftp_ssl_connect()` if `secure=true` and function exists
- Otherwise, uses standard `ftp_connect()`
- Activates passive mode automatically (unless `$_GESTOR['ftp-conexao-nao-passiva']` is defined)
- Stores connection in `$_GESTOR['ftp-conexao']`
- Stores errors in `$_GESTOR['ftp-erro']`

**Notes:**
- Passive mode is enabled by default for better firewall compatibility
- SSL connection requires OpenSSL extension in PHP

---

### ftp_fechar_conexao()

Closes the active FTP connection.

**Signature:**
```php
function ftp_fechar_conexao($params = false)
```

**Parameters:**
- None (empty array or false)

**Return:**
- (void)

**Usage Example:**
```php
// Connect
ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'user',
    'senha' => 'password'
));

// Do operations...

// Close connection
ftp_fechar_conexao();
```

**Notes:**
- Clears `$_GESTOR['ftp-conexao']`
- Safe to call even if there is no active connection

---

### ftp_colocar_arquivo()

Sends (upload) local file to FTP server.

**Signature:**
```php
function ftp_colocar_arquivo($params = false)
```

**Parameters (Associative Array):**
- `local` (string) - **Required** - Local file path
- `remoto` (string) - **Required** - Destination path on FTP server
- `modoFTP` (const) - **Optional** - FTP_ASCII or FTP_BINARY (default: FTP_BINARY)

**Return:**
- (bool) - true if upload successful, false otherwise

**Usage Example:**
```php
// Connect
ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'user',
    'senha' => 'password'
));

// Image upload (binary)
$success = ftp_colocar_arquivo(Array(
    'local' => '/var/www/uploads/photo.jpg',
    'remoto' => '/public_html/images/photo.jpg'
));

if ($success) {
    echo "Upload completed!";
}

// Text file upload (ASCII)
$success = ftp_colocar_arquivo(Array(
    'local' => '/var/www/data/config.txt',
    'remoto' => '/config/config.txt',
    'modoFTP' => FTP_ASCII
));

// Close connection
ftp_fechar_conexao();
```

**FTP Modes:**
- `FTP_BINARY` (default): For binary files (images, PDFs, executables)
- `FTP_ASCII`: For text files (converts line endings)

**Notes:**
- Requires active connection via `ftp_conectar()`
- Returns false if connection is not active

---

### ftp_pegar_arquivo()

Downloads file from FTP server.

**Signature:**
```php
function ftp_pegar_arquivo($params = false)
```

**Parameters (Associative Array):**
- `remoto` (string) - **Required** - File path on FTP server
- `local` (string) - **Required** - Local destination path
- `modoFTP` (const) - **Optional** - FTP_ASCII or FTP_BINARY (default: FTP_BINARY)

**Return:**
- (bool) - true if download successful, false otherwise

**Usage Example:**
```php
// Connect
ftp_conectar(Array(
    'host' => 'ftp.example.com',
    'usuario' => 'user',
    'senha' => 'password'
));

// Backup download
$success = ftp_pegar_arquivo(Array(
    'remoto' => '/backups/database.sql.gz',
    'local' => '/var/backups/database.sql.gz'
));

if ($success) {
    echo "Backup downloaded successfully!";
}

// Log download (ASCII)
$success = ftp_pegar_arquivo(Array(
    'remoto' => '/logs/access.log',
    'local' => '/tmp/access.log',
    'modoFTP' => FTP_ASCII
));

// Close
ftp_fechar_conexao();
```

**Notes:**
- Requires active connection via `ftp_conectar()`
- Overwrites local file if it already exists

---

## Common Use Cases

### 1. Automatic Backup to FTP

```php
function backup_to_ftp() {
    // Generate backup file
    $backup_file = '/tmp/backup-' . date('Y-m-d') . '.sql.gz';
    exec("mysqldump --all-databases | gzip > $backup_file");
    
    // Connect FTP
    $connected = ftp_conectar(Array(
        'host' => 'backup.example.com',
        'usuario' => 'backup_user',
        'senha' => 'secure_password'
    ));
    
    if ($connected) {
        // Send backup
        $sent = ftp_colocar_arquivo(Array(
            'local' => $backup_file,
            'remoto' => '/backups/' . basename($backup_file)
        ));
        
        // Close connection
        ftp_fechar_conexao();
        
        // Clean local file
        if ($sent) {
            unlink($backup_file);
            return true;
        }
    }
    
    return false;
}

// Execute
if (backup_to_ftp()) {
    echo "Backup sent successfully!";
}
```

### 2. Synchronize Files

```php
function sync_uploads() {
    $connected = ftp_conectar(Array(
        'host' => 'cdn.example.com',
        'usuario' => 'cdn_user',
        'senha' => 'password'
    ));
    
    if (!$connected) {
        return false;
    }
    
    $uploads_dir = '/var/www/uploads/';
    $files = scandir($uploads_dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $local = $uploads_dir . $file;
        $remote = '/public/' . $file;
        
        if (is_file($local)) {
            ftp_colocar_arquivo(Array(
                'local' => $local,
                'remoto' => $remote
            ));
        }
    }
    
    ftp_fechar_conexao();
    return true;
}
```

### 3. Download Reports

```php
function download_daily_reports() {
    $connected = ftp_conectar(Array(
        'host' => 'reports.example.com',
        'usuario' => 'reports',
        'senha' => 'password'
    ));
    
    if (!$connected) {
        return Array();
    }
    
    $reports = Array(
        'sales.csv',
        'inventory.csv',
        'clients.csv'
    );
    
    $downloaded = Array();
    
    foreach ($reports as $report) {
        $local = '/var/reports/' . $report;
        $remote = '/daily/' . $report;
        
        if (ftp_pegar_arquivo(Array(
            'remoto' => $remote,
            'local' => $local,
            'modoFTP' => FTP_ASCII
        ))) {
            $downloaded[] = $report;
        }
    }
    
    ftp_fechar_conexao();
    return $downloaded;
}
```

### 4. Upload with Retry

```php
function upload_ftp_with_retry($local_file, $remote_file, $max_attempts = 3) {
    for ($i = 0; $i < $max_attempts; $i++) {
        // Connect
        $connected = ftp_conectar(Array(
            'host' => 'ftp.example.com',
            'usuario' => 'user',
            'senha' => 'password'
        ));
        
        if (!$connected) {
            sleep(2); // Wait before retrying
            continue;
        }
        
        // Try upload
        $success = ftp_colocar_arquivo(Array(
            'local' => $local_file,
            'remoto' => $remote_file
        ));
        
        ftp_fechar_conexao();
        
        if ($success) {
            return true;
        }
        
        sleep(2);
    }
    
    return false;
}

// Use
if (upload_ftp_with_retry('/tmp/file.pdf', '/docs/file.pdf')) {
    echo "Upload successful!";
} else {
    echo "Failed after multiple attempts";
}
```

### 5. Check and Create Directories

```php
function upload_with_structure($file, $full_path) {
    global $_GESTOR;
    
    $connected = ftp_conectar(Array(
        'host' => 'ftp.example.com',
        'usuario' => 'user',
        'senha' => 'password'
    ));
    
    if (!$connected) {
        return false;
    }
    
    // Create directories if necessary
    $parts = explode('/', dirname($full_path));
    $path = '';
    
    foreach ($parts as $part) {
        if (empty($part)) continue;
        
        $path .= '/' . $part;
        
        // Try to create directory (ignore error if already exists)
        @ftp_mkdir($_GESTOR['ftp-conexao'], $path);
    }
    
    // File upload
    $success = ftp_colocar_arquivo(Array(
        'local' => $file,
        'remoto' => $full_path
    ));
    
    ftp_fechar_conexao();
    return $success;
}
```

---

## Patterns and Best Practices

### Connection Management

```php
// ✅ GOOD - Always close connection
ftp_conectar($params);
// ... operations ...
ftp_fechar_conexao();

// ✅ BETTER - Use try/finally (PHP 5.5+)
try {
    if (ftp_conectar($params)) {
        ftp_colocar_arquivo($file_params);
    }
} finally {
    ftp_fechar_conexao();
}
```

### Error Handling

```php
// ✅ Check connection
$connected = ftp_conectar($params);
if (!$connected) {
    global $_GESTOR;
    error_log("FTP Error: " . $_GESTOR['ftp-erro']);
    return false;
}

// ✅ Check operations
if (!ftp_colocar_arquivo($params)) {
    error_log("Upload failed");
}
```

### Appropriate FTP Mode

```php
// ✅ Use FTP_BINARY for binary files
ftp_colocar_arquivo(Array(
    'local' => 'image.jpg',
    'remoto' => '/images/image.jpg',
    'modoFTP' => FTP_BINARY  // Default
));

// ✅ Use FTP_ASCII for text
ftp_colocar_arquivo(Array(
    'local' => 'config.ini',
    'remoto' => '/config.ini',
    'modoFTP' => FTP_ASCII
));
```

---

## Limitations and Considerations

### Security

- FTP credentials are sent in plain text (use FTPS when possible)
- FTP_SSL requires OpenSSL support
- Consider SFTP (SSH) for higher security

### Performance

- Passive mode may be slower on some networks
- To disable: set `$_GESTOR['ftp-conexao-nao-passiva'] = true` before connecting

### Timeout

- FTP connections may time out on long transfers
- Configure `default_socket_timeout` in php.ini if necessary

### Firewall

- Active FTP requires open inbound ports
- Passive FTP works better with firewalls
- FTPS may require additional firewall configuration

---

## See Also

- [PHP FTP Functions](https://www.php.net/manual/en/book.ftp.php) - Official documentation
- [LIBRARY-HOST.md](./LIBRARY-HOST.md) - Host configuration

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
