<?php

class Installer
{
    private $data;
    private $baseDir;
    private $tempDir;

    public function __construct(array $postData)
    {
        $this->data = $postData;
        $this->baseDir = dirname(__DIR__); // Diretório do instalador
        $this->tempDir = $this->baseDir . '/temp';
    }

    public function runStep(string $step)
    {
        if (method_exists($this, $step)) {
            return $this->$step();
        }
        throw new Exception(__('error_invalid_step', "Etapa de instalação inválida."));
    }

    private function validate_input()
    {
        // Validação básica do lado do servidor
        $required = ['db_host', 'db_name', 'db_user', 'domain', 'admin_name', 'admin_email', 'admin_pass'];
        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                throw new Exception(__('error_field_required', "Todos os campos são obrigatórios."));
            }
        }

        if ($this->data['admin_pass'] !== $this->data['admin_pass_confirm']) {
            throw new Exception(__('error_passwords_mismatch_server'));
        }

        // Testa conexão com o banco de dados
        $this->testDatabaseConnection();

        return [
            'status' => 'success',
            'message' => __('progress_validating'),
            'next_step' => 'download_files'
        ];
    }

    private function download_files() 
    {
        // Cria diretório temporário se não existir
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        // URL do release do gestor no GitHub
        $gestorUrl = 'https://github.com/otavioserra/conn2flow/releases/latest/download/gestor.zip';
        $gestorZipPath = $this->tempDir . '/gestor.zip';

        // Download do arquivo gestor.zip
        $this->downloadFile($gestorUrl, $gestorZipPath);

        return [
            'status' => 'success',
            'message' => __('progress_downloading'),
            'next_step' => 'unzip_files'
        ];
    }

    private function unzip_files() 
    {
        $gestorZipPath = $this->tempDir . '/gestor.zip';
        
        // Descompacta o gestor.zip na pasta pai do instalador
        $this->extractZip($gestorZipPath, dirname($this->baseDir));
        
        // Configura os arquivos do sistema
        $this->configureSystem();
        
        return [
            'status' => 'success',
            'message' => __('progress_unzipping'),
            'next_step' => 'run_migrations'
        ];
    }

    private function run_migrations()
    {
        // Tenta executar as migrações e seeders do Phinx primeiro
        $phinxSuccess = $this->tryPhinxMigrations();
        
        if (!$phinxSuccess) {
            // Se Phinx falhou, usa SQL alternativo
            $this->runSqlMigrations();
        }
        
        // Copia o index.php do public-access para a raiz
        $this->setupPublicAccess();
        
        // Remove todos os arquivos do instalador
        $this->cleanupInstaller();
        
        return [
            'status' => 'finished',
            'message' => __('progress_configuring'),
            'redirect_url' => './?success=true&lang=' . ($this->data['lang'] ?? 'pt-br')
        ];
    }

    /**
     * Tenta executar migrações via Phinx (retorna true se sucesso, false se falhou)
     */
    private function tryPhinxMigrations()
    {
        try {
            $this->runPhinxMigrations();
            $this->runPhinxSeeders();
            return true;
        } catch (Exception $e) {
            // Log do erro para debug (opcional)
            error_log('Phinx failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Executa migrações via SQL puro (fallback)
     */
    private function runSqlMigrations()
    {
        $gestorPath = dirname($this->baseDir) . '/gestor';
        $sqlPath = $gestorPath . '/db/conn2flow-schema.sql';
        
        // Verifica se o arquivo SQL existe
        if (!file_exists($sqlPath)) {
            throw new Exception(__('error_sql_schema_not_found', 'Arquivo de schema SQL não encontrado: ') . $sqlPath);
        }
        
        // Executa o SQL
        $this->executeSqlFile($sqlPath);
    }

    /**
     * Executa as migrações do Phinx
     */
    private function runPhinxMigrations()
    {
        $gestorPath = dirname($this->baseDir) . '/gestor';
        $phinxConfigPath = $gestorPath . '/utilitarios/phinx.php';
        $phinxBinPath = $gestorPath . '/vendor/bin/phinx';
        
        // Verifica se o Phinx está instalado
        if (!file_exists($phinxBinPath)) {
            throw new Exception(__('error_phinx_not_found', 'Phinx não encontrado. Execute composer install primeiro.'));
        }
        
        if (!file_exists($phinxConfigPath)) {
            throw new Exception(__('error_phinx_config_not_found', 'Arquivo de configuração do Phinx não encontrado.'));
        }
        
        // Executa as migrações - o config.php já carrega automaticamente do .env
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - usando PowerShell
            $command = "powershell -Command \"Set-Location '$gestorPath'; & '$phinxBinPath' migrate -c '$phinxConfigPath'\"";
        } else {
            // Linux/Unix
            $command = "cd \"$gestorPath\" && \"$phinxBinPath\" migrate -c \"$phinxConfigPath\" 2>&1";
        }
        
        $output = [];
        $returnVar = 0;
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $errorOutput = implode("\n", $output);
            throw new Exception(__('error_migration_failed', 'Falha ao executar migrações: ') . $errorOutput);
        }
    }

    /**
     * Executa os seeders do Phinx
     */
    private function runPhinxSeeders()
    {
        $gestorPath = dirname($this->baseDir) . '/gestor';
        $phinxConfigPath = $gestorPath . '/utilitarios/phinx.php';
        $phinxBinPath = $gestorPath . '/vendor/bin/phinx';
        
        // Executa todos os seeders - o config.php já carrega automaticamente do .env
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - usando PowerShell
            $command = "powershell -Command \"Set-Location '$gestorPath'; & '$phinxBinPath' seed:run -c '$phinxConfigPath'\"";
        } else {
            // Linux/Unix
            $command = "cd \"$gestorPath\" && \"$phinxBinPath\" seed:run -c \"$phinxConfigPath\" 2>&1";
        }
        
        $output = [];
        $returnVar = 0;
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $errorOutput = implode("\n", $output);
            throw new Exception(__('error_seeder_failed', 'Falha ao executar seeders: ') . $errorOutput);
        }
    }

    /**
     * Executa um arquivo SQL diretamente via PDO
     */
    private function executeSqlFile($sqlPath)
    {
        try {
            // Conecta ao banco de dados
            $dsn = "mysql:host={$this->data['db_host']};dbname={$this->data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->data['db_user'], $this->data['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Lê o arquivo SQL
            $sql = file_get_contents($sqlPath);
            
            if ($sql === false) {
                throw new Exception(__('error_read_sql_file', 'Erro ao ler arquivo SQL: ') . $sqlPath);
            }

            // Divide o SQL em statements individuais
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            // Executa cada statement
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
        } catch (PDOException $e) {
            throw new Exception(__('error_sql_execution', 'Erro ao executar SQL: ') . $e->getMessage());
        }
    }

    /**
     * Testa a conexão com o banco de dados
     */
    private function testDatabaseConnection()
    {
        try {
            $dsn = "mysql:host={$this->data['db_host']};dbname={$this->data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->data['db_user'], $this->data['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return true;
        } catch (PDOException $e) {
            throw new Exception(__('error_database_connection', 'Erro na conexão com o banco de dados: ') . $e->getMessage());
        }
    }

    /**
     * Faz download de um arquivo
     */
    private function downloadFile($url, $destination)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Conn2Flow-Installer/1.0');
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || $data === false) {
            throw new Exception(__('error_download_failed', 'Falha no download do arquivo: ') . $url);
        }
        
        if (file_put_contents($destination, $data) === false) {
            throw new Exception(__('error_save_file', 'Erro ao salvar arquivo: ') . $destination);
        }
    }

    /**
     * Extrai um arquivo ZIP
     */
    private function extractZip($zipPath, $destination)
    {
        if (!file_exists($zipPath)) {
            throw new Exception(__('error_zip_not_found', 'Arquivo ZIP não encontrado: ') . $zipPath);
        }

        $zip = new ZipArchive();
        $result = $zip->open($zipPath);
        
        if ($result !== TRUE) {
            throw new Exception(__('error_zip_open', 'Erro ao abrir arquivo ZIP: ') . $result);
        }
        
        if (!$zip->extractTo($destination)) {
            throw new Exception(__('error_zip_extract', 'Erro ao extrair arquivo ZIP'));
        }
        
        $zip->close();
    }

    /**
     * Configura os arquivos do sistema
     */
    private function configureSystem()
    {
        $gestorPath = dirname($this->baseDir) . '/gestor';
        
        // Remove o arquivo config.php antigo se existir (não vamos mais usar)
        $oldConfigPath = $gestorPath . '/config.php';
        if (file_exists($oldConfigPath)) {
            unlink($oldConfigPath);
        }
        
        // Cria arquivos de autenticação a partir dos exemplos
        $this->setupAuthenticationFiles($gestorPath);
    }

    /**
     * Configura o arquivo .env
     */
    private function configureEnvFile($domainDir)
    {
        $envPath = $domainDir . '/.env';
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            // Substitui as variáveis do banco de dados
            $envContent = preg_replace('/^DB_HOST=.*$/m', 'DB_HOST=' . $this->data['db_host'], $envContent);
            $envContent = preg_replace('/^DB_DATABASE=.*$/m', 'DB_DATABASE=' . $this->data['db_name'], $envContent);
            $envContent = preg_replace('/^DB_USERNAME=.*$/m', 'DB_USERNAME=' . $this->data['db_user'], $envContent);
            $envContent = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD=' . ($this->data['db_pass'] ?? ''), $envContent);
            
            // Substitui o domínio em todas as ocorrências
            $domain = $this->data['domain'];
            $envContent = str_replace('dominio', $domain, $envContent);
            
            // Gera senhas aleatórias para segurança
            $opensslPassword = bin2hex(random_bytes(16));
            $envContent = preg_replace('/^OPENSSL_PASSWORD=.*$/m', 'OPENSSL_PASSWORD=' . $opensslPassword, $envContent);
            
            $userHashPassword = bin2hex(random_bytes(16));
            $envContent = preg_replace('/^USUARIO_HASH_PASSWORD=.*$/m', 'USUARIO_HASH_PASSWORD=' . $userHashPassword, $envContent);
            
            // Gera chaves aleatórias para reCAPTCHA (placeholder)
            $recaptchaSite = bin2hex(random_bytes(20));
            $recaptchaServer = bin2hex(random_bytes(20));
            $envContent = preg_replace('/^USUARIO_RECAPTCHA_SITE=.*$/m', 'USUARIO_RECAPTCHA_SITE=' . $recaptchaSite, $envContent);
            $envContent = preg_replace('/^USUARIO_RECAPTCHA_SERVER=.*$/m', 'USUARIO_RECAPTCHA_SERVER=' . $recaptchaServer, $envContent);
            
            // Configura email básico (pode ser configurado depois)
            $envContent = preg_replace('/^EMAIL_HOST=.*$/m', 'EMAIL_HOST=' . $domain, $envContent);
            $envContent = preg_replace('/^EMAIL_USER=.*$/m', 'EMAIL_USER=noreply@' . $domain, $envContent);
            $envContent = preg_replace('/^EMAIL_PASS=.*$/m', 'EMAIL_PASS=', $envContent);
            $envContent = preg_replace('/^EMAIL_FROM=.*$/m', 'EMAIL_FROM=noreply@' . $domain, $envContent);
            $envContent = preg_replace('/^EMAIL_REPLY_TO=.*$/m', 'EMAIL_REPLY_TO=noreply@' . $domain, $envContent);
            
            // Salva o arquivo modificado
            if (file_put_contents($envPath, $envContent) === false) {
                throw new Exception(__('error_create_env', 'Erro ao criar arquivo .env'));
            }
        } else {
            throw new Exception(__('error_env_template_not_found', 'Arquivo .env de template não encontrado'));
        }
    }

    /**
     * Gera as chaves OpenSSL usando a função do gestor
     */
    private function generateSSLKeys($domainDir)
    {
        $chavesDir = $domainDir . '/chaves/gestor';
        
        if (!is_dir($chavesDir)) {
            mkdir($chavesDir, 0755, true);
        }
        
        // Carrega a função do gestor para gerar as chaves
        $gestorPath = dirname($this->baseDir) . '/gestor';
        $autenticacaoLibPath = $gestorPath . '/bibliotecas/autenticacao.php';
        
        if (file_exists($autenticacaoLibPath)) {
            require_once $autenticacaoLibPath;
            
            // Gera as chaves RSA usando a função específica da plataforma
            $chaves = autenticacao_openssl_gerar_chaves(['tipo' => 'RSA']);
            
            if ($chaves && isset($chaves['publica']) && isset($chaves['privada'])) {
                // Salva a chave pública
                $publicaPath = $chavesDir . '/publica.key';
                file_put_contents($publicaPath, $chaves['publica']);
                
                // Salva a chave privada
                $privadaPath = $chavesDir . '/privada.key';
                file_put_contents($privadaPath, $chaves['privada']);
            } else {
                throw new Exception(__('error_generate_keys', 'Erro ao gerar chaves de segurança'));
            }
        } else {
            throw new Exception(__('error_missing_auth_lib', 'Biblioteca de autenticação não encontrada'));
        }
    }

    /**
     * Configura arquivos de autenticação
     */
    private function setupAuthenticationFiles($gestorPath)
    {
        $authExampleDir = $gestorPath . '/autenticacoes.exemplo';
        $authDir = $gestorPath . '/autenticacoes';
        $domain = $this->data['domain'];
        
        if (is_dir($authExampleDir)) {
            // Cria a pasta de autenticações
            if (!is_dir($authDir)) {
                mkdir($authDir, 0755, true);
            }
            
            // Cria a pasta específica do domínio
            $domainDir = $authDir . '/' . $domain;
            if (!is_dir($domainDir)) {
                mkdir($domainDir, 0755, true);
            }
            
            // Copia o conteúdo da pasta exemplo/dominio para autenticacoes/{domain}
            $exampleDomainDir = $authExampleDir . '/dominio';
            if (is_dir($exampleDomainDir)) {
                $this->copyDirectory($exampleDomainDir, $domainDir);
            }
            
            // Configura o arquivo .env
            $this->configureEnvFile($domainDir);
            
            // Gera as chaves OpenSSL
            $this->generateSSLKeys($domainDir);
        }
    }

    /**
     * Copia um diretório recursivamente
     */
    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Configura o public-access na raiz
     */
    private function setupPublicAccess()
    {
        $gestorPath = dirname($this->baseDir) . '/gestor';
        $publicAccessPath = $gestorPath . '/public-access';
        $rootPath = dirname($this->baseDir);
        
        if (is_dir($publicAccessPath)) {
            // Copia o index.php do public-access para a raiz
            $sourceIndex = $publicAccessPath . '/index.php';
            $targetIndex = $rootPath . '/index.php';
            
            if (file_exists($sourceIndex)) {
                copy($sourceIndex, $targetIndex);
            }
            
            // Copia o .htaccess se existir
            $sourceHtaccess = $publicAccessPath . '/.htaccess';
            $targetHtaccess = $rootPath . '/.htaccess';
            
            if (file_exists($sourceHtaccess)) {
                copy($sourceHtaccess, $targetHtaccess);
            }
        }
    }

    /**
     * Remove todos os arquivos do instalador
     */
    private function cleanupInstaller()
    {
        // Remove diretório temporário
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        
        // Lista de arquivos e pastas do instalador para remover
        $itemsToRemove = [
            $this->baseDir . '/src',
            $this->baseDir . '/views', 
            $this->baseDir . '/assets',
            $this->baseDir . '/lang'
        ];
        
        foreach ($itemsToRemove as $item) {
            if (is_dir($item)) {
                $this->removeDirectory($item);
            } elseif (file_exists($item)) {
                unlink($item);
            }
        }
    }

    /**
     * Remove um diretório recursivamente
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        rmdir($dir);
    }
}