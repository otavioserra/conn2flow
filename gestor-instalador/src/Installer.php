<?php

class Installer
{
    private $data;
    private $baseDir;
    private $tempDir;
    private $logFile;

    public function __construct(array $postData)
    {
        $this->data = $postData;
        $this->baseDir = dirname(__DIR__); // Diretório do instalador
        $this->tempDir = $this->baseDir . '/temp';
        $this->logFile = $this->baseDir . '/installer.log';
        
        // Inicia o log
        $this->log("=== Iniciando instalação em " . date('Y-m-d H:i:s') . " ===");
    }

    /**
     * Registra mensagens no log do instalador
     */
    private function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function runStep(string $step)
    {
        $this->log("Executando etapa: {$step}");
        
        if (method_exists($this, $step)) {
            try {
                $result = $this->$step();
                $this->log("Etapa {$step} concluída com sucesso");
                return $result;
            } catch (Exception $e) {
                $this->log("Erro na etapa {$step}: " . $e->getMessage(), 'ERROR');
                throw $e;
            }
        }
        
        $this->log("Etapa inválida: {$step}", 'ERROR');
        throw new Exception(__('error_invalid_step', "Etapa de instalação inválida."));
    }

    private function validate_input()
    {
        $this->log("Iniciando validação dos dados de entrada");
        $this->log("Caminho de instalação solicitado: " . ($this->data['install_path'] ?? 'não informado'));
        
        // Validação básica do lado do servidor
        $required = ['db_host', 'db_name', 'db_user', 'domain', 'install_path', 'admin_name', 'admin_email', 'admin_pass'];
        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                $this->log("Campo obrigatório vazio: {$field}", 'ERROR');
                throw new Exception(__('error_field_required', "Todos os campos são obrigatórios."));
            }
        }

        if ($this->data['admin_pass'] !== $this->data['admin_pass_confirm']) {
            $this->log("Senhas do administrador não coincidem", 'ERROR');
            throw new Exception(__('error_passwords_mismatch_server'));
        }

        // Valida o caminho de instalação
        $this->validateInstallPath($this->data['install_path']);

        // Testa conexão com o banco de dados
        $this->testDatabaseConnection();

        $this->log("Validação dos dados concluída com sucesso");
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

        // Busca a URL do release mais recente do gestor usando GitHub API
        $gestorUrl = $this->getLatestGestorReleaseUrl();
        $gestorZipPath = $this->tempDir . '/gestor.zip';

        $this->log("URL do gestor detectada: {$gestorUrl}");

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
        
        // Usa o caminho de instalação personalizado
        $installPath = $this->data['install_path'];
        $this->log("Descompactando arquivos para: {$installPath}");
        
        // Cria o diretório de instalação se não existir
        if (!is_dir($installPath)) {
            $this->log("Criando diretório de instalação: {$installPath}");
            mkdir($installPath, 0755, true);
        }
        
        // Descompacta o gestor.zip DENTRO do caminho especificado (não um nível acima)
        $this->extractZip($gestorZipPath, $installPath);
        
        // Configura os arquivos do sistema
        $this->configureSystem();
        
        $this->log("Descompactação e configuração concluídas");
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
        
        // Cria a página de sucesso no gestor
        $this->createSuccessPage();
        
        // Copia o index.php do public-access para a raiz
        $this->setupPublicAccess();
        
        // Remove todos os arquivos do instalador
        $this->cleanupInstaller();
        
        return [
            'status' => 'finished',
            'message' => __('progress_configuring'),
            'redirect_url' => './instalacao-sucesso?lang=' . ($this->data['lang'] ?? 'pt-br')
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
        $gestorPath = $this->getGestorPath();
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
        $gestorPath = $this->getGestorPath();
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
        $gestorPath = $this->getGestorPath();
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
            
            $successCount = 0;
            $errorCount = 0;
            
            // Executa cada statement
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                        $successCount++;
                    } catch (PDOException $e) {
                        $errorCount++;
                        
                        // Ignora erros de colunas inexistentes em INSERTs (schema desatualizado)
                        if (stripos($statement, 'INSERT') === 0 && 
                            (strpos($e->getMessage(), 'Unknown column') !== false ||
                             strpos($e->getMessage(), 'Column not found') !== false)) {
                            $this->log("INSERT ignorado (coluna inexistente): " . substr($statement, 0, 100) . "...", 'WARNING');
                            continue;
                        }
                        
                        // Para outros erros, registra mas continua
                        $this->log("Erro SQL ignorado: " . $e->getMessage() . " - Statement: " . substr($statement, 0, 100) . "...", 'WARNING');
                    }
                }
            }
            
            $this->log("SQL executado com {$successCount} sucessos e {$errorCount} erros ignorados");
            
        } catch (PDOException $e) {
            throw new Exception(__('error_sql_execution', 'Erro ao executar SQL: ') . $e->getMessage());
        }
    }

    /**
     * Valida o caminho de instalação (cria pasta automaticamente como em hospedagem real)
     */
    private function validateInstallPath($installPath)
    {
        // Verifica se o caminho não está vazio
        if (empty($installPath)) {
            throw new Exception(__('error_install_path_required', 'O caminho de instalação é obrigatório.'));
        }

        // Normaliza o caminho (remove barras duplas, etc.)
        $installPath = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $installPath), DIRECTORY_SEPARATOR);
        
        // Verifica se o caminho parece válido
        if (!preg_match('/^[a-zA-Z]:[\\\\\/]/', $installPath) && !preg_match('/^\//', $installPath)) {
            throw new Exception(__('error_install_path_invalid', 'O caminho de instalação informado não é válido.'));
        }

        // Verifica o diretório pai (ex: /home/usuario)
        $parentDir = dirname($installPath);
        if (!is_dir($parentDir)) {
            throw new Exception(__('error_install_path_invalid', 'O diretório pai do caminho de instalação não existe: ' . $parentDir));
        }

        // Verifica se é possível escrever no diretório pai
        if (!is_writable($parentDir)) {
            throw new Exception(__('error_install_path_not_writable', 'Não é possível escrever no diretório pai: ' . $parentDir));
        }

        // Cria a pasta de instalação se não existir (como hospedagem real)
        if (!is_dir($installPath)) {
            $this->log("Criando pasta de instalação: {$installPath}");
            
            if (!mkdir($installPath, 0755, true)) {
                throw new Exception(__('error_create_install_dir', 'Não foi possível criar a pasta de instalação: ' . $installPath));
            }
            
            // Define permissões corretas (755 = rwxr-xr-x)
            chmod($installPath, 0755);
            $this->log("Pasta criada com sucesso e permissões definidas (755)");
        }
        
        // Verifica se é possível escrever na pasta de instalação
        if (!is_writable($installPath)) {
            // Tenta corrigir permissões automaticamente
            $this->log("Corrigindo permissões da pasta de instalação");
            chmod($installPath, 0755);
            
            if (!is_writable($installPath)) {
                throw new Exception(__('error_install_path_not_writable', 'Não é possível escrever no caminho de instalação: ' . $installPath));
            }
        }

        $this->log("Caminho de instalação validado: {$installPath}");
        return true;
    }

    /**
     * Retorna o caminho de instalação do gestor
     */
    private function getGestorPath()
    {
        return $this->data['install_path'] ?? dirname($this->baseDir) . '/gestor';
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
        $gestorPath = $this->getGestorPath();
        
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
        $gestorPath = $this->getGestorPath();
        $autenticacaoLibPath = $gestorPath . '/bibliotecas/autenticacao.php';
        
        if (file_exists($autenticacaoLibPath)) {
            require_once $autenticacaoLibPath;
            
            try {
                // Gera as chaves RSA usando a função específica da plataforma
                $this->log("Tentando gerar chaves OpenSSL...");
                $chaves = autenticacao_openssl_gerar_chaves(['tipo' => 'RSA']);
                
                if ($chaves && isset($chaves['publica']) && isset($chaves['privada'])) {
                    // Salva a chave pública
                    $publicaPath = $chavesDir . '/publica.key';
                    file_put_contents($publicaPath, $chaves['publica']);
                    $this->log("Chave pública salva em: {$publicaPath}");
                    
                    // Salva a chave privada
                    $privadaPath = $chavesDir . '/privada.key';
                    file_put_contents($privadaPath, $chaves['privada']);
                    $this->log("Chave privada salva em: {$privadaPath}");
                } else {
                    throw new Exception("Função retornou dados inválidos");
                }
            } catch (Exception $e) {
                $this->log("Erro na geração de chaves OpenSSL: " . $e->getMessage(), 'ERROR');
                $this->log("Tentando fallback para chaves pré-geradas...", 'WARNING');
                
                // Fallback: criar chaves de exemplo para instalação funcionar
                $this->generateFallbackKeys($chavesDir);
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
        $gestorPath = $this->getGestorPath();
        $publicAccessPath = $gestorPath . '/public-access';
        
        // A pasta pública é onde está o instalador (pasta atual)
        $publicPath = dirname($this->baseDir);
        
        $this->log("Configurando acesso público - Gestor em: {$gestorPath}");
        $this->log("Pasta pública detectada: {$publicPath}");
        
        if (is_dir($publicAccessPath)) {
            // Copia o index.php do public-access para a pasta pública (onde está o instalador)
            $sourceIndex = $publicAccessPath . '/index.php';
            $targetIndex = $publicPath . '/index.php';
            
            $this->log("Copiando index.php de {$sourceIndex} para {$targetIndex}");
            
            if (file_exists($sourceIndex)) {
                // Modifica o index.php para apontar para o caminho correto do gestor
                $indexContent = file_get_contents($sourceIndex);
                
                // Calcula o caminho relativo do public para o gestor
                $relativePath = $this->getRelativePath($publicPath, $gestorPath);
                $this->log("Caminho relativo calculado: {$relativePath}");
                
                // Substitui o caminho no index.php
                $indexContent = str_replace('../gestor/', $relativePath . '/', $indexContent);
                $indexContent = str_replace('../gestor\\', $relativePath . '\\', $indexContent);
                
                file_put_contents($targetIndex, $indexContent);
                $this->log("Index.php configurado com sucesso");
            } else {
                $this->log("Arquivo source index.php não encontrado: {$sourceIndex}", 'ERROR');
            }

            // Copia o .htaccess se existir
            $sourceHtaccess = $publicAccessPath . '/.htaccess';
            $targetHtaccess = $publicPath . '/.htaccess';
            
            if (file_exists($sourceHtaccess)) {
                copy($sourceHtaccess, $targetHtaccess);
                $this->log("Arquivo .htaccess copiado");
            }
        } else {
            $this->log("Diretório public-access não encontrado: {$publicAccessPath}", 'ERROR');
        }
    }

    /**
     * Calcula o caminho relativo entre dois diretórios
     */
    private function getRelativePath($from, $to) 
    {
        $from = rtrim(str_replace('\\', '/', $from), '/');
        $to = rtrim(str_replace('\\', '/', $to), '/');
        
        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);
        
        // Remove partes comuns
        while (count($fromParts) && count($toParts) && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }
        
        // Adiciona "../" para cada parte restante em $from
        $relativeParts = array_fill(0, count($fromParts), '..');
        
        // Adiciona as partes restantes de $to
        $relativeParts = array_merge($relativeParts, $toParts);
        
        return implode('/', $relativeParts);
    }

    /**
     * Cria uma página de sucesso da instalação no gestor
     */
    private function createSuccessPage()
    {
        try {
            // Conecta ao banco de dados
            $dsn = "mysql:host={$this->data['db_host']};dbname={$this->data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->data['db_user'], $this->data['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Busca um layout básico (usamos o layout simples se existir)
            $layoutQuery = "SELECT id_hosts_layouts FROM hosts_layouts WHERE status = 'A' AND (id = 'layout-pagina-simples' OR id = 'layout-pagina-padrao') ORDER BY id = 'layout-pagina-simples' DESC LIMIT 1";
            $layoutResult = $pdo->query($layoutQuery);
            $layout = $layoutResult->fetch();
            
            if (!$layout) {
                $this->log("Nenhum layout encontrado para página de sucesso, pulando criação", 'WARNING');
                return;
            }
            
            $layoutId = $layout['id_hosts_layouts'];
            $this->log("Layout encontrado para página de sucesso: ID {$layoutId}");
            
            // HTML da página de sucesso
            $successHtml = '
<div style="text-align: center; padding: 40px; font-family: Arial, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
            <h1 style="color: #155724; margin: 0 0 15px 0;">
                🎉 Instalação Concluída com Sucesso!
            </h1>
            <p style="color: #155724; margin: 0; font-size: 16px;">
                O Conn2Flow foi instalado e configurado com sucesso em seu servidor.
            </p>
        </div>
        
        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
            <h3 style="color: #333; margin-top: 0;">Próximos Passos:</h3>
            <ol style="text-align: left; color: #666;">
                <li>Acesse o painel administrativo do seu site</li>
                <li>Configure suas preferências de sistema</li>
                <li>Personalize o design e conteúdo</li>
                <li>Comece a usar o Conn2Flow!</li>
            </ol>
        </div>
        
        <div>
            <a href="./" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                Acessar Painel Administrativo
            </a>
        </div>
        
        <p style="margin-top: 20px; color: #666; font-size: 14px;">
            Esta página será removida automaticamente após o primeiro acesso ao painel.
        </p>
    </div>
</div>';

            // CSS da página
            $successCss = '
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}';

            // Insere a página de sucesso na tabela hosts_paginas
            $insertQuery = "
                INSERT INTO hosts_paginas (
                    id_hosts, id_usuarios, id_hosts_layouts, nome, id, caminho, tipo, 
                    html, css, status, versao, data_criacao, data_modificacao
                ) VALUES (
                    1, 1, :layout_id, 'Instalação Concluída', 'instalacao-sucesso', 'instalacao-sucesso', 'pagina',
                    :html, :css, 'A', 1, NOW(), NOW()
                )";
            
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute([
                'layout_id' => $layoutId,
                'html' => $successHtml,
                'css' => $successCss
            ]);
            
            $this->log("Página de sucesso criada: instalacao-sucesso");
            
        } catch (PDOException $e) {
            $this->log("Erro ao criar página de sucesso: " . $e->getMessage(), 'WARNING');
            // Não falha a instalação por causa disso
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

    /**
     * Gera chaves de fallback quando OpenSSL falha
     */
    private function generateFallbackKeys($chavesDir)
    {
        $this->log("Gerando chaves de fallback...");
        
        // Tenta um método mais simples de geração de chaves
        $config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        
        $privateKey = openssl_pkey_new($config);
        
        if ($privateKey !== false) {
            // Exporta a chave privada
            openssl_pkey_export($privateKey, $privateKeyPem);
            
            // Obtém a chave pública
            $details = openssl_pkey_get_details($privateKey);
            $publicKeyPem = $details['key'];
            
            // Salva as chaves
            $publicaPath = $chavesDir . '/publica.key';
            $privadaPath = $chavesDir . '/privada.key';
            
            file_put_contents($publicaPath, $publicKeyPem);
            file_put_contents($privadaPath, $privateKeyPem);
            
            $this->log("Chaves de fallback geradas com sucesso");
        } else {
            // Se ainda falhar, cria chaves de exemplo (não seguras, apenas para instalação funcionar)
            $this->log("OpenSSL completamente indisponível, gerando chaves de exemplo", 'WARNING');
            
            $examplePrivate = "-----BEGIN PRIVATE KEY-----\n" .
                "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7VJTUt9Us8cKB\n" .
                "wjKquxdBNqsWlg2Q8h0F4eEU5ej6zRvvZ3x5nVZWJ9Z6W8sU9VHG9a8Q7d8X7q6Q\n" .
                "-----END PRIVATE KEY-----\n";
                
            $examplePublic = "-----BEGIN PUBLIC KEY-----\n" .
                "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu1SU1L7VLPHCgcIyqrsX\n" .
                "QTarFpYNkPIdBeHhFOXo+s0b72d8eZ1WVifWelvLFPVRxvWvEO3fF+6ukMWfI5Q6\n" .
                "-----END PUBLIC KEY-----\n";
            
            file_put_contents($chavesDir . '/publica.key', $examplePublic);
            file_put_contents($chavesDir . '/privada.key', $examplePrivate);
            
            $this->log("ATENÇÃO: Chaves de exemplo criadas. SUBSTITUA por chaves reais após a instalação!", 'WARNING');
        }
    }

    /**
     * Busca a URL do release mais recente do gestor usando GitHub API
     */
    private function getLatestGestorReleaseUrl()
    {
        $this->log("Buscando release mais recente do gestor...");
        
        // Tenta usar a API do GitHub para buscar releases
        $apiUrl = 'https://api.github.com/repos/otavioserra/conn2flow/releases';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Conn2Flow-Installer/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response !== false) {
            $releases = json_decode($response, true);
            
            if (is_array($releases)) {
                // Procura pelo release mais recente do gestor
                foreach ($releases as $release) {
                    if (isset($release['tag_name']) && strpos($release['tag_name'], 'gestor-v') === 0) {
                        // Encontrou um release do gestor
                        $tag = $release['tag_name'];
                        $url = "https://github.com/otavioserra/conn2flow/releases/download/{$tag}/gestor.zip";
                        $this->log("Release do gestor encontrado: {$tag}");
                        return $url;
                    }
                }
            }
        }
        
        $this->log("Não foi possível buscar via API, usando fallback para gestor-v1.0.3", 'WARNING');
        
        // Fallback para versão conhecida se a API falhar
        return 'https://github.com/otavioserra/conn2flow/releases/download/gestor-v1.0.3/gestor.zip';
    }
}