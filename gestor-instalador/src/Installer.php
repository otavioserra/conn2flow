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
        $this->baseDir = realpath(dirname(__DIR__)); // Diretório do instalador robusto
        $this->tempDir = $this->baseDir . DIRECTORY_SEPARATOR . 'temp';
        $this->logFile = $this->baseDir . DIRECTORY_SEPARATOR . 'installer.log';
        
        // Inicia o log
        $this->log("=== Iniciando instalação em " . date('Y-m-d H:i:s') . " ===");
    }

    /**
     * Instancia PDO garantindo charset utf8mb4 em todas operações
     */
    private function getPdo()
    {
        $dsn = "mysql:host={$this->data['db_host']};dbname={$this->data['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $this->data['db_user'], $this->data['db_pass'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET NAMES utf8mb4");
        return $pdo;
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
        $this->log("SSL habilitado: " . ($this->data['ssl_enabled'] ?? 'não informado'));
        
        // Validação básica do lado do servidor
        $required = ['db_host', 'db_name', 'db_user', 'domain', 'install_path', 'admin_name', 'admin_email', 'admin_pass'];
        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                $this->log("Campo obrigatório vazio: {$field}", 'ERROR');
                throw new Exception(__('error_field_required', "Todos os campos são obrigatórios."));
            }
        }

        // ssl_enabled é opcional, mas se não estiver definido, assume como desabilitado
        if (!isset($this->data['ssl_enabled'])) {
            $this->data['ssl_enabled'] = '0';
        }

        // clean_install é opcional, mas se não estiver definido, assume como desabilitado
        if (!isset($this->data['clean_install'])) {
            $this->data['clean_install'] = '0';
        }
        
        // Log das opções selecionadas
        $this->log("Instalação limpa solicitada: " . ($this->data['clean_install'] === '1' ? 'SIM' : 'NÃO'));

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
    $gestorZipPath = $this->tempDir . DIRECTORY_SEPARATOR . 'gestor.zip';

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
        $installPath = isset($this->data['install_path']) ? realpath($this->data['install_path']) ?: $this->data['install_path'] : null;
        $this->log("Descompactando arquivos para: {$installPath}");

        // Cria o diretório de instalação se não existir
        if (!is_dir($installPath)) {
            $this->log("Criando diretório de instalação: {$installPath}");
            mkdir($installPath, 0755, true);
        }

        // Verifica se deve pular a extração do ZIP (aceita SKIP_UNZIP ou skip_unzip)
        $skipUnzip = (
            (!empty($this->data['SKIP_UNZIP']) && $this->data['SKIP_UNZIP'] == '1') ||
            (!empty($this->data['skip_unzip']) && $this->data['skip_unzip'] == '1')
        );
        if ($skipUnzip) {
            $this->log("SKIP_UNZIP ativado: pulando extração do ZIP, mas executando correção de permissões e configuração do sistema");
        } else {
            // Descompacta o gestor.zip DENTRO do caminho especificado (não um nível acima)
            $this->extractZip($gestorZipPath, $installPath);
        }

        // Corrige permissões do Phinx após descompactação (ou após pular)
        $this->fixPhinxPermissions();

        // Configura os arquivos do sistema
        $this->configureSystem();

        $this->log("Descompactação e configuração concluídas");
        return [
            'status' => 'success',
            'message' => __('progress_unzipping'),
            'next_step' => 'run_update_steps'
        ];
    }

    private function run_update_steps()
    {
        $this->log("=== INICIANDO PROCESSO DE ATUALIZAÇÃO (MIGRAÇÕES e DADOS) ===");

        // 1. Limpeza opcional do banco
        if (!empty($this->data['clean_install'])) {
            $this->cleanDatabase();
        }

        // 2. Executa script de atualização centralizado do sistema
        $this->runUpdateScript();

        // 3. Garante usuário administrador conforme dados fornecidos
        $this->ensureAdminUser();

        // 4. Configura login automático do administrador
        $this->createAdminAutoLogin();

        // 5. Página de sucesso
        $this->createSuccessPage();

        // 6. Public access (index.php + .htaccess com RewriteBase corrigido)
        $this->setupPublicAccess();

        // 7. Limpeza installer final
        $this->cleanupInstaller();

        // Instalação sucesso!
        $this->log("✅ Instalação concluída com sucesso! ✅");

        return [
            'status' => 'finished',
            'message' => __('progress_configuring'),
            'redirect_url' => './instalacao-sucesso?lang=' . ($this->data['lang'] ?? 'pt-br')
        ];
    }

    /**
     * Limpa o banco de dados antes da instalação (apenas se usuário optou por instalação limpa)
     */
    private function cleanDatabase()
    {
        $this->log("=== LIMPEZA DO BANCO DE DADOS ===");
        $this->log("⚠️  ATENÇÃO: Usuário optou por instalação limpa - removendo todas as tabelas!");
        
        try {
            $pdo = $this->getPdo();
            // Desabilita verificação de chaves estrangeiras temporariamente
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Lista todas as tabelas do banco
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($tables) > 0) {
                $this->log("Encontradas " . count($tables) . " tabelas para remoção");
                
                // Remove todas as tabelas
                foreach ($tables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS `$table`");
                    $this->log("  ✅ Tabela removida: $table");
                }
            } else {
                $this->log("✅ Banco de dados já está vazio");
            }
            
            // Reabilita verificação de chaves estrangeiras
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->log("✅ Limpeza do banco concluída com sucesso!");
        } catch (PDOException $e) {
            $this->log("❌ Erro ao limpar banco de dados: " . $e->getMessage(), 'ERROR');
            throw new Exception("Falha ao limpar banco de dados: " . $e->getMessage());
        }
    }

    /**
     * Cria login automático para o usuário administrador criado
     */
    private function createAdminAutoLogin()
    {
        $this->log("=== CONFIGURANDO LOGIN AUTOMÁTICO DO ADMINISTRADOR ===");
        
        try {
            // Simular ambiente do gestor para usar as bibliotecas
            $this->setupGestorEnvironment();
            
            // Carrega manualmente as bibliotecas essenciais do gestor,
            // pois o instalador não executa o bootstrap completo do gestor.php.
            // A ordem é importante para resolver as dependências.
            $gestorPath = $this->getGestorPath();
            require_once $gestorPath . '/bibliotecas/banco.php';
            require_once $gestorPath . '/bibliotecas/gestor.php';
            require_once $gestorPath . '/bibliotecas/ip.php'; // Dependência de usuario.php
            require_once $gestorPath . '/bibliotecas/usuario.php';
            
            $this->log("📝 Gerando token de autorização para o usuário administrador (ID: 1)");
            
            // ID do usuário administrador criado (sempre 1 pelo seeder)
            $adminUserId = 1;
            
            // Gerar token de autorização com "permanecer logado" = true
            // Usa todas as configurações corretas do $_CONFIG carregado do .env
            $tokenResult = usuario_gerar_token_autorizacao([
                'id_usuarios' => $adminUserId
                // Não passa 'sessao' => true, para manter logado (cookie persistente)
            ]);

            global $_CONFIG;
            
            if ($tokenResult) {
                $this->log("✅ Login automático configurado com sucesso! Usuário administrador estará logado após instalação.");
                $this->log("🔑 Token de autorização gerado usando configurações do .env");
                $this->log("🍪 Cookie configurado: " . $_CONFIG['cookie-authname'] . " por " . ($_CONFIG['cookie-lifetime'] / 86400) . " dias");
            } else {
                $this->log("⚠️ Falha ao configurar login automático, mas instalação pode continuar", 'WARNING');
            }
            
        } catch (Exception $e) {
            $this->log("⚠️ Erro ao configurar login automático: " . $e->getMessage(), 'WARNING');
            // Não interrompemos a instalação por causa do login automático
            // Mas vamos registrar o erro detalhado para debug
            $this->log("Detalhes do erro: " . $e->getFile() . ':' . $e->getLine(), 'WARNING');
        }
    }

    /**
     * Configura ambiente mínimo do gestor para usar bibliotecas
     */
    private function setupGestorEnvironment()
    {
        global $_GESTOR, $_CONFIG, $_BANCO, $_INDEX;
        
        $gestorPath = $this->getGestorPath();

        // Define a variável que o config.php espera para o ROOT_PATH.
        // Sem isso, o caminho para a pasta 'autenticacoes' e, consequentemente,
        // para o .env, não é encontrado corretamente.
        if (!isset($_INDEX)) {
            $_INDEX = [];
        }
        $_INDEX['sistemas-dir'] = $gestorPath . '/';

        // Incluir o config.php do gestor que já carrega tudo do .env
        require_once $gestorPath . '/config.php';
        
        // O config.php já populou $_GESTOR, $_CONFIG e $_BANCO corretamente do .env
        // Só precisamos garantir algumas variáveis específicas para o contexto do instalador
        
        // Garantir que REQUEST_URI existe para detectUrlRaiz
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = $this->detectUrlRaiz();
        }
        
        // Verificar se o ambiente foi configurado corretamente
        $this->log("🔧 Ambiente configurado - URL_RAIZ: " . $_GESTOR['url-raiz']);
        $this->log("🔧 Ambiente configurado - OpenSSL Path: " . $_GESTOR['openssl-path']);
        $this->log("🔧 Ambiente configurado - Cookie Name: " . $_CONFIG['cookie-authname']);
    }

    /**
     * Corrige permissões do Phinx após descompactação
     */
    private function fixPhinxPermissions()
    {
        $gestorPath = $this->getGestorPath();
        $phinxBinPath = $gestorPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phinx';
        
        if (file_exists($phinxBinPath)) {
            chmod($phinxBinPath, 0755);
            $this->log("Permissões do Phinx corrigidas: {$phinxBinPath}");
        } else {
            $this->log("Arquivo Phinx não encontrado para correção de permissões: {$phinxBinPath}", 'WARNING');
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
        return isset($this->data['install_path']) ? realpath($this->data['install_path']) ?: $this->data['install_path'] : dirname($this->baseDir) . DIRECTORY_SEPARATOR + 'gestor';
    }

    /**
     * Testa a conexão com o banco de dados
     */
    private function testDatabaseConnection()
    {
        try {
            $pdo = $this->getPdo();
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
            
            // Detecta se estamos numa subpasta e configura URL_RAIZ
            $urlRaiz = $this->detectUrlRaiz();
            $this->log("Configurando URL_RAIZ detectada: {$urlRaiz}");
            $envContent = preg_replace('/^URL_RAIZ=.*$/m', 'URL_RAIZ=' . $urlRaiz, $envContent);
            
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
                // Lê a senha do arquivo .env já configurado
                $envPath = $domainDir . '/.env';
                $opensslPassword = null;
                
                if (file_exists($envPath)) {
                    $envContent = file_get_contents($envPath);
                    if (preg_match('/^OPENSSL_PASSWORD=(.*)$/m', $envContent, $matches)) {
                        $opensslPassword = trim($matches[1]);
                        $this->log("🔑 Usando senha OpenSSL do .env para gerar chaves");
                    }
                }
                
                // Gera as chaves RSA usando a função específica da plataforma COM SENHA
                $this->log("Tentando gerar chaves OpenSSL com senha...");
                $chaves = autenticacao_openssl_gerar_chaves([
                    'tipo' => 'RSA',
                    'senha' => $opensslPassword // USA A SENHA DO .ENV
                ]);
                
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
        $this->log("Configurando acesso público...");
        
        // Os arquivos processados ficam na própria pasta do instalador
        $targetPath = $this->baseDir;
        
        $this->log("Pasta do instalador (onde ficarão os arquivos finais): {$targetPath}");
        
        // A pasta public-access está local no instalador
        $publicAccessPath = $this->baseDir . '/public-access';
        
        if (is_dir($publicAccessPath)) {
            // Processa e salva o index.php na pasta do instalador
            $sourceIndex = $publicAccessPath . '/index.php';
            $targetIndex = $targetPath . '/index.php';
            
            if (file_exists($sourceIndex)) {
                $indexContent = file_get_contents($sourceIndex);

                global $_CONFIG;
                
                // Pega os valores do formulário para substituir no template
                $serverName = $this->data['domain'] ?? 'localhost';
                $gestorFullPath = $this->data['install_path'] . '/';
                
                $this->log("Server name: {$serverName}");
                $this->log("Caminho completo do gestor: {$gestorFullPath}");
                
                // Substitui os placeholders no template
                $indexContent = str_replace('"dominio"', '"' . $serverName . '"', $indexContent);
                $indexContent = str_replace("'caminho'", "'" . $gestorFullPath . "'", $indexContent);
                
                $this->log("Template processado - domínio: {$serverName}, caminho: {$gestorFullPath}");
                
                // Sobrescreve o index.php do instalador com o processado
                file_put_contents($targetIndex, $indexContent);
                $this->log("Index.php processado e salvo em {$targetIndex}");
            } else {
                $this->log("Arquivo source index.php não encontrado: {$sourceIndex}", 'ERROR');
            }

            // Processa e salva o .htaccess na pasta do instalador
            $sourceHtaccess = $publicAccessPath . '/.htaccess';
            $targetHtaccess = $targetPath . '/.htaccess';
            
            if (file_exists($sourceHtaccess)) {
                $htaccessContent = file_get_contents($sourceHtaccess);

                // Nova detecção robusta de subpasta
                $rewriteBase = $this->detectUrlRaiz(); // já retorna com barra final ou '/'
                if ($rewriteBase !== '/') {
                    $this->log("Detectada instalação em subpasta: {$rewriteBase}");
                    // Remove RewriteBase existente
                    $htaccessContent = preg_replace('/^\s*RewriteBase\s+.*$/mi', '', $htaccessContent);
                    // Garante linha RewriteEngine On seguida de RewriteBase correta
                    if (preg_match('/RewriteEngine\s+On/i', $htaccessContent)) {
                        $htaccessContent = preg_replace('/(RewriteEngine\s+On)/i', "$1\n\tRewriteBase {$rewriteBase}", $htaccessContent, 1);
                    } else {
                        // Caso excepcional: não encontrou RewriteEngine On
                        $htaccessContent = "RewriteEngine On\n\tRewriteBase {$rewriteBase}\n" . $htaccessContent;
                    }
                } else {
                    $this->log("Instalação na raiz - mantendo .htaccess padrão");
                }
                
                // Se SSL não está habilitado, remove as linhas de redirect HTTPS
                if (empty($this->data['ssl_enabled']) || $this->data['ssl_enabled'] == '0') {
                    $this->log("SSL não habilitado - removendo redirect HTTPS do .htaccess");
                    
                    // Remove as linhas que forçam redirect para HTTPS
                    $htaccessContent = preg_replace('/^\s*RewriteCond\s+%\{HTTPS\}\s+off.*$/m', '', $htaccessContent);
                    $htaccessContent = preg_replace('/^\s*RewriteRule\s+\^\(\.\*\)\$\s+https:\/\/.*$/m', '', $htaccessContent);
                    
                    // Remove linhas vazias múltiplas
                    $htaccessContent = preg_replace('/\n\s*\n\s*\n/', "\n\n", $htaccessContent);
                } else {
                    $this->log("SSL habilitado - mantendo redirect HTTPS no .htaccess");
                }
                
                // Normaliza quebras de linha múltiplas
                $htaccessContent = preg_replace("/\n{3,}/", "\n\n", $htaccessContent);

                file_put_contents($targetHtaccess, $htaccessContent);
                $this->log("Arquivo .htaccess processado e salvo em {$targetHtaccess}");
            }
            
            // Remove a pasta public-access após processar os arquivos
            $this->removeDirectory($publicAccessPath);
            $this->log("Pasta public-access removida: {$publicAccessPath}");
            
            // Remove todos os arquivos do instalador exceto index.php e .htaccess
            $this->cleanupInstallerFiles();
            
        } else {
            $this->log("Diretório public-access não encontrado: {$publicAccessPath}", 'ERROR');
        }
    }

    /**
     * Remove todos os arquivos do instalador exceto index.php, .htaccess e installer.log
     */
    private function cleanupInstallerFiles()
    {
        $this->log("Removendo arquivos do instalador, mantendo apenas index.php, .htaccess e installer.log...");
        
        // Lista de pastas para remover completamente
        $foldersToRemove = [
            'src',
            'views', 
            'assets',
            'lang'
        ];
        
        // Lista de arquivos para remover (installer.log será preservado para debug)
        $filesToRemove = [
            'teste-seguranca.txt'
        ];
        
        // Remove pastas
        foreach ($foldersToRemove as $folder) {
            $folderPath = $this->baseDir . '/' . $folder;
            if (is_dir($folderPath)) {
                $this->removeDirectory($folderPath);
                $this->log("Pasta removida: {$folderPath}");
            }
        }
        
        // Remove arquivos
        foreach ($filesToRemove as $file) {
            $filePath = $this->baseDir . '/' . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
                $this->log("Arquivo removido: {$filePath}");
            }
        }
        
        $this->log("Limpeza concluída. Restam apenas index.php, .htaccess e installer.log na pasta do instalador.");
    }
    
    /**
     * Remove todos os arquivos do instalador
     */
    private function cleanupInstaller()
    {
        // Remove diretório temporário
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
            $this->log("Diretório temporário removido: {$this->tempDir}");
        }
        
        // A limpeza dos arquivos do instalador é feita em cleanupInstallerFiles()
        // chamada pelo setupPublicAccess(), deixando apenas index.php e .htaccess
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
            $this->log("Iniciando criação/atualização da página de sucesso...");
            $pdo = $this->getPdo();
            // Verifica existência
            $stmt = $pdo->query("SELECT COUNT(*) as c FROM paginas WHERE id='instalacao-sucesso'");
            $exists = (int)$stmt->fetch()['c'] > 0;

            if ($exists) {
                $this->log('Página existente, sobrescrevendo HTML/CSS...');
                $sql = "UPDATE paginas SET html=:html, css=:css, data_modificacao=NOW() WHERE id='instalacao-sucesso'";
                $up = $pdo->prepare($sql);
                $up->execute([
                    'html' => $this->getSuccessPageHtml(),
                    'css'  => $this->getSuccessPageCss()
                ]);
                $this->log('Página de sucesso atualizada.');
            } else {
                $this->log('Criando nova página de sucesso...');
                $sql = "INSERT INTO paginas (id, html, css, data_criacao, data_modificacao) VALUES ('instalacao-sucesso', :html, :css, NOW(), NOW())";
                $ins = $pdo->prepare($sql);
                $ins->execute([
                    'html' => $this->getSuccessPageHtml(),
                    'css'  => $this->getSuccessPageCss()
                ]);
                $this->log('Página de sucesso criada.');
            }
        } catch (Exception $e) {
            $this->log('Falha ao criar/atualizar página de sucesso: ' . $e->getMessage(), 'WARNING');
        }
    }

    /**
     * Retorna o HTML da página de sucesso
     */
     private function getSuccessPageHtml()
     {
         return '
<div class="ui main container">
    <div class="ui centered grid">
        <div class="twelve wide column">
            <!-- Mensagem de Sucesso -->
            <div class="ui positive message">
                <div class="header">
                    <i class="exclamation triangle icon"></i>
                    Instalação Concluída com Sucesso!
                </div>
                <p>O Conn2Flow foi instalado e configurado com sucesso em seu servidor.</p>
            </div>
            
            <!-- Próximos Passos -->
            <div class="ui segment">
                <div class="ui header">
                    <i class="list icon"></i>
                    <div class="content">
                        Próximos Passos
                        <div class="sub header">Siga estas etapas para começar a usar o Conn2Flow</div>
                    </div>
                </div>
                
                <div class="ui ordered steps">
                    <div class="step">
                        <i class="user icon"></i>
                        <div class="content">
                            <div class="title">Acesse o Painel</div>
                            <div class="description">Entre no painel administrativo do seu site</div>
                        </div>
                    </div>
                    <div class="step">
                        <i class="settings icon"></i>
                        <div class="content">
                            <div class="title">Configure o Sistema</div>
                            <div class="description">Ajuste suas preferências de sistema</div>
                        </div>
                    </div>
                    <div class="step">
                        <i class="paint brush icon"></i>
                        <div class="content">
                            <div class="title">Personalize o Design</div>
                            <div class="description">Customize o visual e conteúdo</div>
                        </div>
                    </div>
                    <div class="step">
                        <i class="rocket icon"></i>
                        <div class="content">
                            <div class="title">Comece a Usar</div>
                            <div class="description">Aproveite todas as funcionalidades!</div>
                        </div>
                    </div>
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
                <div class="header">
                    <i class="info circle icon"></i>
                    Nota
                </div>
                <p>Esta página será removida automaticamente quando você acessar o painel administrativo pela primeira vez.</p>
            </div>
        </div>
    </div>
</div>';
     }

    /**
     * Retorna o CSS da página de sucesso
     */
    private function getSuccessPageCss()
    {
        return '
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}';
    }

    /**
     * Busca a URL do release mais recente do gestor usando GitHub API
     */
    private function getLatestGestorReleaseUrl()
    {
        $this->log("Buscando release mais recente do gestor via GitHub API...");
        
        // Tenta usar a API do GitHub para buscar releases
        $apiUrl = 'https://api.github.com/repos/otavioserra/conn2flow/releases';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Conn2Flow-Installer/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log detalhado para debug
        $this->log("API Response HTTP Code: {$httpCode}");
        if (!empty($curlError)) {
            $this->log("CURL Error: {$curlError}", 'WARNING');
        }
        
        if ($httpCode === 200 && $response !== false) {
            $releases = json_decode($response, true);
            
            if (is_array($releases)) {
                $this->log("Encontrados " . count($releases) . " releases no repositório");
                
                // Procura pelo release mais recente do gestor
                foreach ($releases as $release) {
                    if (isset($release['tag_name']) && strpos($release['tag_name'], 'gestor-v') === 0) {
                        // Encontrou um release do gestor
                        $tag = $release['tag_name'];
                        $url = "https://github.com/otavioserra/conn2flow/releases/download/{$tag}/gestor.zip";
                        $this->log("✅ Release do gestor encontrado automaticamente: {$tag}");
                        $this->log("URL do download: {$url}");
                        return $url;
                    }
                }
                
                $this->log("Nenhum release do gestor encontrado na API", 'WARNING');
            } else {
                $this->log("Resposta da API inválida - não é um array", 'WARNING');
            }
        } else {
            $this->log("Falha na requisição da API: HTTP {$httpCode}", 'WARNING');
        }
        
        // Se chegou até aqui, a API falhou ou não encontrou releases
        $this->log("❌ Falha ao buscar releases via API do GitHub", 'ERROR');
        throw new Exception(__('error_github_api_failed', 'Não foi possível acessar os releases do GitHub. Verifique sua conexão com a internet e tente novamente.'));
    }

    // Métodos de seeders removidos.

    /**
     * Executa o script de atualização central (substitui migrações/seeders)
     */
    private function runUpdateScript()
    {
        $gestorPath = $this->getGestorPath();
        $scriptPath = $gestorPath . '/controladores/atualizacoes/atualizacoes-banco-de-dados.php';
        $this->log("Executando script de atualização: {$scriptPath}");

        if (!file_exists($scriptPath)) {
            throw new Exception('Script de atualização não encontrado: ' . $scriptPath);
        }

        try {
            global $GLOBALS;

            $GLOBALS['CLI_OPTS'] = [
                'env-dir' => $this->data['domain'],
                'installing' => true,
                'db' => [
                    'host' => $this->data['db_host'],
                    'name' => $this->data['db_name'],
                    'user' => $this->data['db_user'],
                    'pass' => $this->data['db_pass'] ?? '',
                ]
            ];

            $this->setupGestorEnvironment();
            $this->log('Executando script de atualização (migrando banco)...');
            require $scriptPath;
            $this->log('✅ Script de atualização executado.');
        } catch (Exception $e) {
            $this->log('❌ Falha ao executar script de atualização: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }

        $dbDir = $gestorPath . '/db';
        if (is_dir($dbDir)) {
            $this->removeDirectory($dbDir);
            $this->log('Pasta db removida após atualização: ' . $dbDir);
        }
    }

    /**
     * Remove um diretório e todo o seu conteúdo recursivamente
     */
    private function removeDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    /**
     * Garante existência/atualização do usuário administrador conforme dados fornecidos
     */
    private function ensureAdminUser()
    {
        $this->log('Garantindo usuário administrador...');
        try {
            $pdo = $this->getPdo();
            $adminName = $this->data['admin_name'];
            $adminEmail = $this->data['admin_email'];
            $adminPass = $this->data['admin_pass'];

            if (defined('PASSWORD_ARGON2I')) {
                $hash = password_hash($adminPass, PASSWORD_ARGON2I, ['cost' => 9]);
            } else {
                $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            // Processar nome
            $adminName = preg_replace('/\s+/', ' ', trim($adminName));
            $nomes = explode(' ',$adminName);

            if(count($nomes) > 2){
                for($i=0;$i<count($nomes);$i++){
                    if($i==0){
                        $primeiro_nome = $nomes[$i];
                    } else if($i==count($nomes) - 1){
                        $ultimo_nome = $nomes[$i];
                    } else {
                        $nome_do_meio .= (isset($nome_do_meio) ? ' ':'') . $nomes[$i];
                    }
                }
            } else if(count($nomes) > 1){
                $primeiro_nome = $nomes[0];
                $ultimo_nome = $nomes[1];
            } else {
                $primeiro_nome = $nomes[0];
            }

            $sql = "INSERT INTO usuarios (id_usuarios, nome, nome_conta, usuario, senha, email, status".
                (isset($primeiro_nome) ? ', primeiro_nome' : '').
                (isset($nome_do_meio) ? ', nome_do_meio' : '').
                (isset($ultimo_nome) ? ', ultimo_nome' : '').
                ") VALUES (1, :nome, :nome_conta, :email, :senha, :email, 'A'".
                (isset($primeiro_nome) ? ', :primeiro_nome' : '').
                (isset($nome_do_meio) ? ', :nome_do_meio' : '').
                (isset($ultimo_nome) ? ', :ultimo_nome' : '').
                ") ON DUPLICATE KEY UPDATE nome = VALUES(nome), nome_conta = VALUES(nome_conta), usuario = VALUES(email), senha = VALUES(senha), email = VALUES(email)".
                (isset($primeiro_nome) ? ', primeiro_nome = VALUES(primeiro_nome)' : '').
                (isset($nome_do_meio) ? ', nome_do_meio = VALUES(nome_do_meio)' : '').
                (isset($ultimo_nome) ? ', ultimo_nome = VALUES(ultimo_nome)' : '').
                ";";

            $params = [
                'nome' => $adminName,
                'nome_conta' => $adminName,
                'senha' => $hash,
                'email' => $adminEmail
            ];
            if (isset($primeiro_nome)) {
                $params['primeiro_nome'] = $primeiro_nome;
            }
            if (isset($nome_do_meio)) {
                $params['nome_do_meio'] = $nome_do_meio;
            }
            if (isset($ultimo_nome)) {
                $params['ultimo_nome'] = $ultimo_nome;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $this->log('✅ Usuário administrador garantido/atualizado.');
        } catch (Exception $e) {
            $this->log('⚠️  Falha ao garantir usuário administrador: ' . $e->getMessage(), 'WARNING');
        }
    }

    /**
     * Detecta automaticamente o URL_RAIZ baseado no caminho atual do instalador
     */
    private function detectUrlRaiz()
    {
        $this->log("=== Iniciando detecção de URL_RAIZ (baseada no arquivo principal) ===");
        // Log de variáveis relevantes
        $serverVars = [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'não definido',
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'não definido',
            'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'não definido',
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'não definido',
            'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'não definido'
        ];
        foreach ($serverVars as $var => $value) {
            $this->log("Variável {$var}: {$value}");
        }

        // Preferencialmente usar SCRIPT_FILENAME (arquivo principal em execução)
        $mainFile = $_SERVER['SCRIPT_FILENAME'] ?? null;
        $mainName = $_SERVER['SCRIPT_NAME'] ?? null;
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;

        if ($mainFile && $docRoot) {
            $mainDir = dirname(realpath($mainFile));
            $docRootReal = realpath($docRoot);
            $this->log("SCRIPT_FILENAME: {$mainFile}");
            $this->log("DOCUMENT_ROOT: {$docRootReal}");
            $this->log("Diretório do arquivo principal: {$mainDir}");

            // Se está na raiz física
            if ($mainDir === $docRootReal) {
                $this->log("✅ Arquivo principal está na raiz física, retornando '/'");
                return '/';
            }
            // Calcula caminho relativo
            if (strpos($mainDir, $docRootReal) === 0) {
                $relativePath = substr($mainDir, strlen($docRootReal));
                $relativePath = str_replace('\\', '/', $relativePath);
                $this->log("Caminho relativo do arquivo principal: {$relativePath}");
                if (!empty($relativePath) && $relativePath !== '/') {
                    $urlRaiz = $relativePath . '/';
                    $this->log("✅ Subpasta detectada via arquivo principal: {$urlRaiz}");
                    return $urlRaiz;
                }
            }
        }

        // Fallback: usar SCRIPT_NAME
        if ($mainName) {
            $dirPath = dirname($mainName);
            $this->log("Diretório do SCRIPT_NAME: {$dirPath}");
            if ($dirPath !== '/' && !empty($dirPath) && $dirPath !== '.') {
                $urlRaiz = $dirPath . '/';
                $this->log("✅ Subpasta detectada via SCRIPT_NAME: {$urlRaiz}");
                return $urlRaiz;
            }
        }

        // Fallback: usar REQUEST_URI
        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            $this->log("Analisando REQUEST_URI: {$requestUri}");
            $path = parse_url($requestUri, PHP_URL_PATH);
            $dirPath = dirname($path);
            $this->log("Diretório do caminho: {$dirPath}");
            if ($dirPath !== '/' && !empty($dirPath) && $dirPath !== '.') {
                $urlRaiz = $dirPath . '/';
                $this->log("✅ Subpasta detectada via REQUEST_URI: {$urlRaiz}");
                return $urlRaiz;
            }
        }

        // Fallback: padrões conhecidos de pastas
        $possiblePaths = ['instalador', 'install', 'setup', 'installer'];
        $mainDirName = $mainFile ? basename(dirname($mainFile)) : '';
        $parentDirName = $mainFile ? basename(dirname(dirname($mainFile))) : '';
        $this->log("Nome do diretório principal: {$mainDirName}");
        $this->log("Nome do diretório pai do principal: {$parentDirName}");
        foreach ($possiblePaths as $folder) {
            if ($mainDirName === $folder || $parentDirName === $folder) {
                $urlRaiz = '/' . $folder . '/';
                $this->log("✅ Subpasta detectada por nome de diretório principal: {$urlRaiz}");
                return $urlRaiz;
            }
        }

        // Padrão: raiz
        $this->log("❌ Nenhuma subpasta detectada, usando raiz: /");
        return '/';
    }
}